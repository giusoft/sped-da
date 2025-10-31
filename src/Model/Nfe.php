<?php

namespace App\Model;

use NFePHP\NFe\MakeDev;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Common\Standardize;
use NFePHP\Common\Certificate;
use NFePHP\Common\Validator;

class Nfe
{
    private $corpoRequisicao;
    private $config;
    private $tools;
    private $default;

    public function __construct($dados)
    {
        $this->corpoRequisicao = $dados->corpoRequisicao;
        $this->config = $dados->config;
        $this->tools = $dados->tools;
        $this->carregarDadosDefault();
    }


    public function carregarDadosDefault()
    {
        $this->default['versao'] = '4.00';
        $this->default['modelo'] = 55;
        $this->default['dataEmissao'] = date('Y-m-d\TH:i:sP');
        $this->default['dataSaidaEntrada'] = date('Y-m-d\TH:i:sP');
        $this->default['serie'] = 1;
        $this->default['cNF'] = sprintf('%08d', rand(1, 99999999));
        $this->default['tpNF'] = 1;
        $this->default['tipoImpressao'] = 1;
        $this->default['tipoEmissao'] = 1;
        $this->default['finalidadeEmissao'] = 1;
        $this->default['cnjpAutorizadoSefaz'] = '13937073000156';
        $this->default['codigoPais'] = 1058; // Código do Brasil = 1058
    }


    public function enviar()
    {
        try {
            // 1. MONTA O XML
            $xmlMontado = $this->montarXML($this->corpoRequisicao);

            ### DAR RETORNO QUE ELE FOI GERADO ###
            // 2. ASSINA O XML (já faz validação automática)
            $xmlAssinado = $this->tools->signNFe($xmlMontado);

            $xsd = __DIR__ . "/../Lib/sped-nfe/schemes/PL_010_V1.30/nfe_v4.00.xsd";
            $erroxsd = null;
            try {
                Validator::isValid($xmlAssinado, $xsd);
            } catch (ValidatorException $e) {
                emitirErro($e->getMessage(), 400);
            }

            ### DAR UM RETORNO PRO JS QUE O XML FOI ASSINADO ###
            // 3. ENVIA PARA SEFAZ (modo síncrono - indSinc=1)
            $idLote = str_pad(time(), 15, '0', STR_PAD_LEFT);

            // ESSE PARAMETRO 1 DEVE SER PEGO DO BD (O modo deve ser passado pelo banco de dados)
            $response = $this->tools->sefazEnviaLote([$xmlAssinado], $idLote, 1); // 1 = modo síncrono

            // 4. PROCESSA RESPOSTA
            $stdCl = new Standardize();
            $std = $stdCl->toStd($response);

            // Verifica se houve erro no lote primeiro
            if (isset($std->cStat) && !in_array($std->cStat, [100, 103, 104])) {
                $motivo = 'Erro desconhecido';
                if (isset($std->xMotivo)) {
                    $motivo = $std->xMotivo;
                }

                emitirErro(
                    $motivo,
                    400,
                    'Erro ao processar lote',
                    ['codigoSituacaoNF' => $std->cStat]
                );
            }

            // Verifica se veio com protocolo (resposta síncrona)
            if (isset($std->protNFe->infProt)) {
                $cStat = $std->protNFe->infProt->cStat;

                if (!in_array($cStat, [100, 150])) {

                    $motivo = 'Erro desconhecido';
                    if (isset($std->protNFe->infProt->xMotivo)) {
                        $motivo = $std->protNFe->infProt->xMotivo;
                    }

                    // Nota rejeitada
                    emitirErro(
                        $motivo,
                        400,
                        'Nota rejeitada',
                        ['codigoSituacaoNF' => $cStat]
                    );
                }

                // 100: Autorizado, 150: Autorizado fora de prazo
                $protocolo = $std->protNFe->infProt->nProt;
                $chave = $std->protNFe->infProt->chNFe;

                $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $response);

                // Salva XML
                $this->salvarXML($chave, $xmlProtocolado);

                $motivo = 'Autorizada';
                if (isset($std->protNFe->infProt->xMotivo)){
                    $motivo = $std->protNFe->infProt->xMotivo;
                }

                $dataHoraRecebimento = null;
                if (isset($std->protNFe->infProt->dhRecbto)) {
                    $dataHoraRecebimento = $std->protNFe->infProt->dhRecbto;
                }

                emitirSucesso(
                    $motivo,
                    200,
                    [
                        'chave' => $chave,
                        'protocolo' => $protocolo,
                        'codigoSituacaoNF' => $cStat,
                        'dhRecbto' => $dataHoraRecebimento,
                        'xml' => base64_encode($xmlProtocolado)
                    ]
                );

            } elseif (isset($std->infRec->nRec)) {
                // Resposta assíncrona (fallback) - consulta o recibo
                $recibo = $std->infRec->nRec;
                $protocolo = $this->consultarProtocolo($recibo, $xmlAssinado);

                $motivo = 'Erro desconhecido';
                if (isset($std->xMotivo)) {
                    $motivo = $std->xMotivo;
                }

                if ($protocolo['success'] != 200) {
                    emitirErro(
                        $motivo,
                        400,
                        'Erro ao processar lote',
                        ['codigoSituacaoNF' => $std->cStat]
                    );
                }

                emitirSucesso($protocolo, 200);
            } else {

                $motivo = 'Resposta inesperada da SEFAZ';
                if (isset($std->xMotivo)) {
                    $motivo = $std->xMotivo;
                }

                // Erro no lote
                emitirErro(
                    $motivo,
                    400,
                    'Erro ao processar lote',
                    ['codigoSituacaoNF' => $std->cStat]
                );
            }
        } catch (\Exception $e) {
            emitirErro(
                $e->getMessage(),
                500
            );
        }
    }


    public function montarXML()
    {
        $nfe = new MakeDev('PL_010_V1.30');

        // ===== IDENTIFICAÇÃO DA NFe =====
        $std = new \stdClass();
        $std->versao = $this->default['versao'];
        $nfe->taginfNFe($std);

        $std = new \stdClass();
        $std->cUF = $this->config['cUF']; // Código da UF (Unidade da Federação) do emitente
        $std->cNF = $this->default['cNF']; // Código numérico da nota
        $std->natOp = $this->corpoRequisicao['naturezaOperacao']; // Natureza da operação
        $std->mod = $this->default['modelo']; // Modelo do documento (55 = NF-e (modelo eletrônico), 65 = NFC-e)

        $std->serie = $this->default['serie']; // Série da nota fiscal
        if (isset($this->corpoRequisicao['serie'])) {
            $std->serie = $this->corpoRequisicao['serie'];
        }

        $std->nNF = $this->corpoRequisicao['numero']; // Número da nota fiscal
        $std->dhEmi = $this->default['dataEmissao']; // Data/hora de emissão
        $std->dhSaiEnt = $this->default['dataEmissao']; // Data/hora de saída ou entrada (Opcional — geralmente usada em operações com circulação de mercadoria)
        $std->tpNF = $this->default['tpNF'];
        if (isset($this->corpoRequisicao['tipoOperacao'])) {
            $std->tpNF = $this->corpoRequisicao['tipoOperacao']; // Tipo da NF (0 = Entrada, 1 = Saída)
        }

        // Define idDest baseado na UF do destinatário
        $ufEmitente = $this->config['siglaUF'];
        $ufDestinatario = $this->corpoRequisicao['cliente']['uf'];

        $paisDestinatario = $this->default['codigoPais'];
        if ($this->corpoRequisicao['cliente']['cPais']) {
            $paisDestinatario = $this->corpoRequisicao['cliente']['cPais'];
        }

        if ($paisDestinatario != 1058) {
            $std->idDest = 3; // Exterior
        } elseif ($ufEmitente === $ufDestinatario) {
            $std->idDest = 1; // Operação interna
        } else {
            $std->idDest = 2; // Operação interestadual
        }

        $std->cMunFG = $this->config['cmun'];               // Código do município de ocorrência do fato gerador
        $std->tpImp = $this->default['tipoImpressao'];      // Tipo de impressão do DANFE (1 = Retrato, 2 = Paisagem);
        $std->tpEmis = $this->default['tipoImpressao'];     // Tipo de emissão da NF-e (1 = Normal, 2 = Contingência FS-IA, 3 = SCAN, 4 = DPEC, 5 = FS-DA, 6 = SVC-AN, 7 = SVC-RS, 9 = off-line)
        // $std->cDV = 0;                                   // Dígito verificador da chave da NF-e;
        $std->tpAmb = $this->config['tpAmb'];               // Tipo de ambiente (1 = PRODUÇÃO, 2 = HOMOLOGAÇÃO)
        $std->finNFe = $this->default['finalidadeEmissao']; // Finalidade de emissão (1 = Normal, 2 = Complementar, 3 = Ajuste, 4 = Devolução)
        $std->indFinal = 1;                                 // Consumidor final (0 = Não, 1 = Sim)

        if (in_array($std->finNFe, [2, 3, 6])) {
            $std->tpNFDebito = '01';
        }

        if (in_array($std->finNFe, [4, 5])) {
            $std->tpNFCredito = '01';
        }

        $std->indPres = 1;                                  // Indicador de presença do comprador (0 = Não se aplica, 1 = Presencial, 2 = Internet, 3 = Teleatendimento)
        $std->procEmi = 0;                                  // Processo de emissão (0 = Emissão pelo próprio contribuinte, 1 = Avulsa Fisco, 2 = Avulsa contrib. com certificado, 3 = Aplicativo do Fisco)
        $std->verProc = 'API GNotas 1.0';                   // Versão do aplicativo emissor
        $nfe->tagide($std);

        if (!empty($this->corpoRequisicao['chaveReferenciada'])) {
            $stdRef = new \stdClass();
            $stdRef->refNFe = $this->corpoRequisicao['chaveReferenciada'];
            $nfe->tagrefNFe($stdRef);
        }

        // ===== EMITENTE =====
        $std = new \stdClass();
        $std->xNome = $this->config['razaosocial'];         // Razão social / nome do emitente
        $std->xFant = $this->config['razaosocial'];         // Nome fantasia (Opcional)
        $std->IE = $this->config['ie'];                     // Inscrição estadual (Obrigatória (exceto isento))
        $std->CRT = $this->config['regime'];                // Regime tributário (No nosso caso passamos sempre 3)
        $std->CNPJ = soNumeros($this->config['cnpj']);      // Documento do emitente (Apenas um deve ser informado CNPJ || CPF) - Ver com thiago
        $nfe->tagemit($std);

        $std = new \stdClass();
        $std->xLgr = $this->config['logradouro'];           // Logradouro (rua)
        $std->nro = $this->config['numero'];                // Número
        $std->xBairro = $this->config['bairro'];            // Bairro
        $std->cMun = $this->config['cmun'];                 // Código IBGE do município
        $std->xMun = $this->config['xmun'];                 // Nome do município
        $std->UF = $this->config['siglaUF'];                // Sigla do estado
        $std->CEP = soNumeros($this->config['cep']);        // Código postal
        $std->cPais = $this->config['cPais'];               // Código do país
        $std->xPais = $this->config['xPais'];               // Nome do país
        $std->fone = soNumeros($this->config['fone']);      // Telefone do Emitente
        $nfe->tagenderEmit($std);

        // ===== DESTINATÁRIO =====
        $cli = $this->corpoRequisicao['cliente'];
        $std = new \stdClass();
        $std->xNome = $cli['nome']; // Nome / razão social

        if (!empty($cli['cnpj'])) {
            $std->CNPJ = soNumeros($cli['cnpj']); // Documento do destinatário
        } else {
            $std->CPF = soNumeros($cli['cpf']);   // Documento do destinatário
        }

        $std->indIEDest = 9; // (Vai vir nos dados do cliente) // Indicador IE destinatário (1 = Contribuinte, 2 = Isento, 9 = Não contribuinte)
        $nfe->tagdest($std);

        $std = new \stdClass();
        $std->xLgr = $cli['endereco'];        // Rua
        $std->nro = $cli['numero'];           // Número
        $std->xBairro = $cli['bairro'];       // Bairro
        $std->cMun = $cli['codigoMunicipio']; // Código Município
        $std->xMun = $cli['municipio'];       // Município
        $std->UF = $cli['uf'];                // UF
        $std->CEP = soNumeros($cli['cep']);   // CEP
        $std->cPais = $this->config['cPais']; // Código do país (Vai vir nos dados do cliente)
        $std->xPais = $this->config['xPais']; // Nome do país (Vai vir nos dados do cliente)
        $nfe->tagenderDest($std);

        // ===== PRODUTOS =====
        $totalProdutos = 0;
        $totalIS = 0;
        $totalIBS = 0;
        $totalCBS = 0;
        $totalBC_IBSCBS = 0;

        foreach ($this->corpoRequisicao['produtos'] as $i => $prod) {
            $item = $i + 1;
            $quantidade = (float) ($prod['quantidade'] ?? 1);
            $valorUnitario = (float) ($prod['valorUnitario'] ?? 0);
            $vProd = $quantidade * $valorUnitario;
            $totalProdutos += $vProd;

            // TAG PRODUTO
            $std = new \stdClass();
            $std->item = $item; // Número sequencial do item
            $std->cProd = $prod['codigo'] ?? 'SEMPROD'; // Código interno do produto
            $std->cEAN = $prod['cEAN'] ?? 'SEM GTIN'; // Código de barras
            $std->xProd = $prod['descricao'] ?? 'PRODUTO SEM DESCRICAO'; // Descrição do produto
            $std->NCM = soNumeros($prod['ncm']); // Código NCM (classificação fiscal)
            $std->CFOP = $prod['cfop']; // Código Fiscal da Operação
            $std->uCom = $prod['unidade'] ?? 'UN'; // Unidade
            $std->qCom = number_format($prod['quantidade'], 4, '.', ''); // Quantidade
            $std->vUnCom = number_format($prod['valorUnitario'], 10, '.', ''); // Valor Unitário
            $std->vProd = number_format($vProd, 2, '.', ''); // Valor Total
            $std->cEANTrib = $prod['cEANTrib'] ?? 'SEM GTIN'; // Código de barras do produto para tributação
            $std->uTrib = $prod['unidade'] ?? 'UN'; // Unidade de medida para tributação
            $std->qTrib = number_format($prod['quantidade'], 4, '.', ''); // Quantidade tributável
            $std->vUnTrib = number_format($prod['valorUnitario'], 10, '.', ''); // Valor unitário tributável
            $std->indTot = 1; // 1 = inclui no total da NF
            $nfe->tagprod($std);

            // TAG IMPOSTO (container principal)
            $std = new \stdClass();
            $std->item = $item; // Número do item
            $nfe->tagimposto($std);

            $impostos = $prod['impostos'] ?? [];

            // ICMS
            $icms = $impostos['icms'] ?? [];
            $aliqICMS = (float)($icms['aliquota'] ?? 18);
            $redBC = (float)($icms['pRedBC'] ?? 0);
            $bcICMS = $vProd * (1 - $redBC / 100);
            $vICMS = $bcICMS * $aliqICMS / 100;
            if ($icms) {
                $std = new \stdClass();
                $std->item = $item;
                $std->orig = $icms['orig'] ?? 0;
                $std->CST = str_pad($icms['CST'] ?? '00', 2, '0', STR_PAD_LEFT);
                $std->modBC = 3;
                $std->vBC = number_format($bcICMS, 2, '.', '');
                $std->pICMS = number_format($aliqICMS, 2, '.', '');
                $std->vICMS = number_format($vICMS, 2, '.', '');
                $std->pRedBC = ($redBC > 0) ? number_format($redBC, 2, '.', '') : null;
                $nfe->tagICMS($std);
            }

            // PIS
            $pis = $impostos['pis'] ?? [];
            $pPIS = (float)($pis['aliquota'] ?? 0.00);
            $vPIS = $vProd * $pPIS / 100;
            if ($pis) {
                $std = new \stdClass();
                $std->item = $item; // Número do item
                $std->CST = str_pad($pis['CST'] ?? '06', 2, '0', STR_PAD_LEFT); // Código de situação tributária (ex: 01, 07)
                $std->vBC = number_format($vProd, 2, '.', '');
                $std->pPIS = number_format($pPIS, 4, '.', '');
                $std->vPIS = number_format($vPIS, 2, '.', '');
                $nfe->tagPIS($std);
            }

            // COFINS
            $cofins = $impostos['cofins'] ?? [];
            $pCOFINS = (float)($cofins['aliquota'] ?? 0.00);
            $vCOFINS = $vProd * $pCOFINS / 100;
            if ($cofins) {
                $std = new \stdClass();
                $std->item = $item; // Número do item
                $std->CST = str_pad($cofins['CST'] ?? '06', 2, '0', STR_PAD_LEFT); // Código de situação tributária (ex: 01, 07)
                $std->vBC = number_format($vProd, 2, '.', '');
                $std->pCOFINS = number_format($pCOFINS, 4, '.', '');
                $std->vCOFINS = number_format($vCOFINS, 2, '.', '');
                $nfe->tagCOFINS($std);
            }

            // IS (Imposto Seletivo)
            $is = $impostos['is'] ?? [];
            $vIS = (float)($is['vIS'] ?? 0);
            if ($is) {
                $std = new \stdClass();
                $std->item = $item;
                $std->CSTIS = str_pad($is['CSTIS'] ?? '000', 3, '0', STR_PAD_LEFT);
                $std->cClassTribIS = str_pad($is['cClassTribIS'] ?? '000000', 6, '0', STR_PAD_LEFT);
                $std->vBCIS = number_format((float)($is['vBCIS'] ?? 0), 2, '.', '');
                $std->pIS = number_format((float)($is['pIS'] ?? 0), 2, '.', '');
                $std->vIS = number_format($vIS, 2, '.', '');
                $std->uTrib = $is['uTrib'] ?? 'UN';
                $std->qTrib = number_format((float)($is['qTrib'] ?? 0), 4, '.', '');
                $nfe->tagIS($std);
                $totalIS += $vIS;
            }

            // IBS/CBS (Reforma Tributária)
            $ibs = $impostos['ibscbs'] ?? [];
            $vBC_IBSCBS = (float)($ibs['vBC'] ?? $vProd);

            if ((int)date('Y') >= 2026) {
                $ibs['gCBS_pAliqEfet'] = 0.9;
            }

            $gIBSUF_pAliqEfet = round((float)($ibs['gIBSUF_pAliqEfet'] ?? 0), 2);
            $gIBSMun_pAliqEfet = round((float)($ibs['gIBSMun_pAliqEfet'] ?? 0), 2);
            $gCBS_pAliqEfet = round((float)($ibs['gCBS_pAliqEfet'] ?? 0), 2);

            $gIBSUF_vIBSUF = round($vBC_IBSCBS * $gIBSUF_pAliqEfet / 100, 2);
            $gIBSMun_vIBSMun = round($vBC_IBSCBS * $gIBSMun_pAliqEfet / 100, 2);
            $gCBS_vCBS = round($vBC_IBSCBS * $gCBS_pAliqEfet / 100, 2);

            if ($ibs) {
                $std = new \stdClass();
                $std->item = $item;
                $std->CST = str_pad($ibs['CST'] ?? '200', 3, '0', STR_PAD_LEFT);
                $std->cClassTrib = str_pad($ibs['cClassTrib'] ?? '200003', 6, '0', STR_PAD_LEFT);
                $std->indDoacao = (int)($ibs['indDoacao'] ?? 0);
                $std->vBC = number_format($vBC_IBSCBS, 2, '.', '');

                // IBS Estadual
                $std->gIBSUF_pIBSUF = number_format((float)($ibs['gIBSUF_pIBSUF'] ?? 0), 4, '.', '');
                $std->gIBSUF_pRedAliq = number_format((float)($ibs['gIBSUF_pRedAliq'] ?? 0), 4, '.', '');
                $std->gIBSUF_pAliqEfet = number_format($gIBSUF_pAliqEfet, 4, '.', '');
                $std->gIBSUF_vIBSUF = number_format($gIBSUF_vIBSUF, 2, '.', '');

                // IBS Municipal
                $std->gIBSMun_pIBSMun = number_format((float)($ibs['gIBSMun_pIBSMun'] ?? 0), 4, '.', '');
                $std->gIBSMun_pRedAliq = number_format((float)($ibs['gIBSMun_pRedAliq'] ?? 0), 4, '.', '');
                $std->gIBSMun_pAliqEfet = number_format($gIBSMun_pAliqEfet, 4, '.', '');
                $std->gIBSMun_vIBSMun = number_format($gIBSMun_vIBSMun, 2, '.', '');

                // CBS Federal
                $std->gCBS_pCBS = number_format((float)($ibs['gCBS_pCBS'] ?? 0), 4, '.', '');
                $std->gCBS_pRedAliq = number_format((float)($ibs['gCBS_pRedAliq'] ?? 0), 4, '.', '');
                $std->gCBS_pAliqEfet = number_format($gCBS_pAliqEfet, 4, '.', '');
                $std->gCBS_vCBS = number_format($gCBS_vCBS, 2, '.', '');
                $nfe->tagIBSCBS($std);

                $totalIBS += $gIBSUF_vIBSUF + $gIBSMun_vIBSMun;
                $totalCBS += $gCBS_vCBS;
                $totalBC_IBSCBS += $vBC_IBSCBS;
            }
        }

        // Força a inclusão das Tags de Totais (IS, IBS, CBS)
        // A biblioteca pode omitir se forem zero, mas a SEFAZ exige.

        // 1. Total de IS
        $stdISTot = new \stdClass();
        $stdISTot->vIS = number_format($totalIS, 2, '.', '');
        $nfe->tagISTot($stdISTot);

        // 2. Totais de IBS/CBS
        $stdIBSCBSTot = new \stdClass();
        $stdIBSCBSTot->vBCIBSCBS = number_format($totalBC_IBSCBS, 2, '.', '');
        $stdIBSCBSTot->gIBS_vIBS = number_format($totalIBS, 2, '.', '');
        $stdIBSCBSTot->gCBS_vCBS = number_format($totalCBS, 2, '.', '');
        $nfe->tagIBSCBSTot($stdIBSCBSTot);

        $totalNota = $totalProdutos + $totalIS + $totalIBS + $totalCBS;
        $stdTotal = new \stdClass();
        $stdTotal->vNFTot = number_format($totalNota, 2, '.', '');
        $nfe->tagtotal($stdTotal);

        // ===== TRANSPORTE =====
        $std = new \stdClass();
        $std->modFrete = 9; // Modalidade do frete (0 = emitente, 1 = destinatário, 2 = terceiros, 9 = sem frete)
        $nfe->tagtransp($std);

        // ===== PAGAMENTO =====
        $std = new \stdClass();
        $std->vTroco = 0.00;
        $nfe->tagpag($std);

        $finalidade = $this->corpoRequisicao['finalidadeEmissao'] ?? 1; // 1 = NF-e Normal por padrão

        $std = new \stdClass();
        if (in_array($finalidade, [3, 4])) {
            // 3 = NF-e de Ajuste
            // 4 = NF-e de Devolução/Estorno
            $std->tPag = '90'; // 90 = Sem Pagamento
            $std->vPag = 0.00; // Valor do pagamento é zero
        } else {
            // 1 = NF-e Normal (Venda)
            $std->tPag = '01'; // Tipo de pagamento (01 = dinheiro, 02 = cheque, 03 = cartão, 15 = PIX)
            $std->vPag = number_format($totalProdutos, 2, '.', ''); // Valor pago pelo cliente
        }

        $nfe->tagdetPag($std);

        // ===== INFORMAÇÕES ADICIONAIS =====
        $std = new \stdClass();
        $std->infCpl = $this->corpoRequisicao['informacoesAdicionais'] ??
            'DOCUMENTO EMITIDO SOB O NOVO REGIME TRIBUTARIO (IBS/CBS/IS)';
        $nfe->taginfAdic($std);

        // ===== AUTORIZAÇÃO XML (OBRIGATÓRIO PARA BA) =====
        // Se não tiver contador, informar CNPJ da SEFAZ-BA
        $std = new \stdClass();
        $std->CNPJ = $this->default['cnjpAutorizadoSefaz']; // CNPJ autorizado a baixar o XML da NF-e (SEFAZ-BA)
        $nfe->tagautXML($std);

        return $nfe->getXML();
    }


    public function consultarProtocolo($recibo, $xmlAssinado)
    {
        try {
            $response = $this->tools->sefazConsultaRecibo($recibo);

            $stdCl = new Standardize();
            $std = $stdCl->toStd($response);

            if (isset($std->protNFe->infProt)) {
                $cStat = $std->protNFe->infProt->cStat;

                if (in_array($cStat, [100, 150])) {
                    $protocolo = $std->protNFe->infProt->nProt;
                    $chave = $std->protNFe->infProt->chNFe;

                    $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $response);

                    // Salva XML
                    $this->salvarXML($chave, $xmlProtocolado);

                    $mensagem = "Autorizada";
                    if (isset($std->protNFe->infProt->xMotivo)) {
                        $mensagem = $std->protNFe->infProt->xMotivo;
                    }

                    return [
                        'success' => true,
                        'chave' => $chave,
                        'protocolo' => $protocolo,
                        'mensagem' => $mensagem,
                        'xml' => base64_encode($xmlProtocolado)
                    ];
                }
            }
            $motivo = 'Erro desconhecido';
            if (isset($std->xMotivo)) {
                $motivo = $std->xMotivo;
            }

            $codigoSituacaoNF = 'N/A';
            if (isset($std->cStat)) {
                $codigoSituacaoNF = $std->cStat;
            }

            return [
                'success' => false,
                'erro' =>  $motivo,
                'codigo' => $codigoSituacaoNF
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'erro' => $e->getMessage(),
                'codigo' => 'EXCEPTION'
            ];
        }
    }


    public function cancelarNFe()
    {
        try {
            $chave = '';
            if (isset($this->corpoRequisicao['chave'])) {
                $chave = $this->corpoRequisicao['chave'];
            }

            $protocolo = '';
            if (isset($this->corpoRequisicao['protocolo'])) {
                $protocolo = $this->corpoRequisicao['protocolo'];
            }

            $justificativa = '';
            if (isset($this->corpoRequisicao['justificativa'])) {
                $justificativa = $this->corpoRequisicao['justificativa'];
            }

            if (!$chave || !$protocolo || !$justificativa) {
                emitirErro("Os campos: chave, protocolo e justificativa sao obrigatorios", 400);
            }

            if (strlen($justificativa) < 15) {
                emitirErro("A justificativa deve ter no minimo 15 caracteres", 400);
            }

            // Envia o cancelamento e captura tanto a requisição quanto a resposta
            $response = $this->tools->sefazCancela($chave, $justificativa, $protocolo);

            // Pega o XML do evento que foi enviado (está disponível após o envio)
            $xmlEvento = $this->tools->lastRequest;

            $stdCl = new Standardize();
            $std = $stdCl->toStd($response);

            if (isset($std->retEvento->infEvento) && $std->retEvento->infEvento->cStat == 135) {
                // Evento registrado com sucesso
                $protocoloCancelamento = '';
                if (isset($std->retEvento->infEvento->nProt)) {
                    $protocoloCancelamento = $std->retEvento->infEvento->nProt;
                }

                // Junta o evento enviado com a resposta recebida usando Complements
                $xmlProtocolado = Complements::toAuthorize($xmlEvento, $response);
                $this->salvarXMLCancelado($chave, $xmlProtocolado);

                $mensagem = 'Cancelamento homologado';
                if (isset($std->retEvento->infEvento->xMotivo)) {
                    $mensagem = $std->retEvento->infEvento->xMotivo;
                }

                emitirSucesso(
                    [
                        'success' => true,
                        'mensagem' => $mensagem,
                        'codigo' => $std->retEvento->infEvento->cStat,
                        'protocolo' => $protocoloCancelamento
                    ],
                    200
                );
            } else {
                $motivo = 'Erro desconhecido';
                if (!empty($std->retEvento->infEvento->xMotivo)) {
                    $motivo = $std->retEvento->infEvento->xMotivo;
                } elseif (!empty($std->xMotivo)) {
                    $motivo = $std->xMotivo;
                }

                $codigo = 'N/A';
                if (!empty($std->retEvento->infEvento->cStat)) {
                    $codigo = $std->retEvento->infEvento->cStat;
                } elseif (!empty($std->cStat)) {
                    $codigo = $std->cStat;
                }

                emitirErro($motivo, 400, $codigo);
            }

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }


    public function inutilizarNFe()
    {
        try {

            if (
                !isset($this->corpoRequisicao['serie'])
                || !isset($this->corpoRequisicao['numero_inicial'])
                || !isset($this->corpoRequisicao['numero_final'])
                || !isset($this->corpoRequisicao['justificativa'])
            ) {
                emitirErro('Os campos: cnpj_emitente, serie, numero_inicial, numero_final e justificativa sao obrigatorios', 400);
                return;
            }

            if (strlen($this->corpoRequisicao['justificativa']) < 15) {
                emitirErro('A justificativa deve ter no mínimo 15 caracteres', 400);
            }

            if ($this->corpoRequisicao['numero_inicial'] > $this->corpoRequisicao['numero_final']) {
                emitirErro('O "numero_inicial" não pode ser maior que o "numero_final"', 400);
            }

            $response = $this->tools->sefazInutiliza(
                            $this->corpoRequisicao['serie'],
                            $this->corpoRequisicao['numero_inicial'],
                            $this->corpoRequisicao['numero_final'],
                            $this->corpoRequisicao['justificativa'],
                            null,
                            null
                        );

            $stdCl = new Standardize();
            $std = $stdCl->toStd($response);

            if (isset($std->infInut->cStat) && $std->infInut->cStat == 102) {

                $protocolo = "";
                if (isset($std->infInut->nProt)) {
                    $protocolo =  $std->infInut->nProt;
                }

                $this->salvarXMLInutilizado($std->infInut, $response);

                $motivo = 'Inutilização homologada';
                if (isset($std->infInut->xMotivo)) {
                    $motivo = $std->infInut->xMotivo;
                }

                emitirSucesso(
                    [
                        'mensagem' => $motivo,
                        'codigo' => $std->infInut->cStat,
                        'protocolo' => $protocolo,
                        'xml' => base64_encode($response) // A resposta já é o XML protocolado
                    ],
                    200
                );
            } else {

                $motivo = 'Erro desconhecido na inutilização';
                if (isset($std->infInut->xMotivo) && $std->infInut->xMotivo) {
                    $motivo = $std->infInut->xMotivo;
                } elseif (isset($std->xMotivo) && $std->xMotivo) {
                    $motivo = $std->xMotivo;
                }

                $codigo = 'N/A';
                if (isset($std->infInut->cStat) && $std->infInut->cStat) {
                    $codigo = $std->infInut->cStat;
                } elseif (isset($std->cStat) && $std->cStat) {
                    $codigo = $std->cStat;
                }

                emitirErro(
                    [
                        'erro' => $motivo,
                        'codigo' => $codigo
                    ]
                    , 400
                );
            }

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }


    public function cartaCorrecao()
    {
        try {

            $chave = '';
            if (isset($this->corpoRequisicao['chave'])) {
                $chave = $this->corpoRequisicao['chave'];
            }

            $correcao = '';
            if (isset($this->corpoRequisicao['correcao'])) {
                $correcao = $this->corpoRequisicao['correcao'];
            }

            if (!$chave || !$correcao) {
                emitirErro("Os campos: chave e correcao sao obrigatorios", 400);
                return;
            }

            if (strlen($correcao) < 15) {
                emitirErro("A correcao deve ter no minimo 15 caracteres", 400);

            }

            $nSeqEvento = 1; // Sequência do evento (1 para primeira CC-e)
            if (isset($this->corpoRequisicao['sequencia'])) {
                $nSeqEvento = $this->corpoRequisicao['sequencia'];
            }

            $response = $this->tools->sefazCCe($chave, $correcao, $nSeqEvento);

            $xmlEvento = $this->tools->lastRequest;

            $stdCl = new Standardize();
            $std = $stdCl->toStd($response);

            if ($std->cStat != 128) { // 128 = Lote de Evento Processado
                emitirErro($std->xMotivo, 400, ['codigo' => $std->cStat]);
            }

            if ($std->retEvento->infEvento->cStat != 135) { // Evento Vinculado
                emitirErro($std->retEvento->infEvento->xMotivo, 400, ['codigo' => $std->retEvento->infEvento->cStat]);
            }

            $protocolo = $std->retEvento->infEvento->nProt;

            // 3. JUNTA OS DOIS XMLs (Requisição + Resposta)
            $xmlProtocolado = Complements::toAuthorize($xmlEvento, $response);

            $this->salvarXMLCCe($chave, $xmlProtocolado, $nSeqEvento);

            $dataEvento = null;
            if (isset($std->retEvento->infEvento->dhRegEvento)) {
                $dataEvento = $std->retEvento->infEvento->dhRegEvento;
            }

            emitirSucesso(
                $std->retEvento->infEvento->xMotivo,
                200,
                [
                    'protocolo' => $protocolo,
                    'sequencia' => $nSeqEvento,
                    'data_evento' => $dataEvento
                ]
            );

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }


    public function consultarNFe()
    {
        try {

            $chave = '';
            if (isset($this->corpoRequisicao['chave'])) {
                $chave = $this->corpoRequisicao['chave'];
            }

            if (!$chave || strlen($chave) != 44) {
                emitirErro("Chave de acesso valida eh obrigatoria", 400);
            }

            $response = $this->tools->sefazConsultaChave($chave);

            $stdCl = new Standardize();
            $std = $stdCl->toStd($response);

            $motivo = 'Sem informação';
            if (isset($std->xMotivo)) {
                $motivo = $std->xMotivo;
            }

            $codigoSituacaoNF = 'Sem informação';
            if (isset($std->cStat)) {
                $codigoSituacaoNF = $std->cStat;
            }

            $protocolo = null;
            if (isset($std->protNFe->infProt->nProt)) {
                $protocolo = $std->protNFe->infProt->nProt;
            }

            emitirSucesso(
                [
                    'situacao' => $motivo,
                    'codigo' => $codigoSituacaoNF,
                    'protocolo' => $protocolo,
                    'resposta_sefaz' => $std
                ],
                200
            );

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }


    public function estornarNFe()
    {
        try {
            $dadosEstorno = $this->corpoRequisicao;

            // 1. VALIDAÇÕES OBRIGATÓRIAS
            if (
                !isset($dadosEstorno['chaveReferenciada']) ||
                !isset($dadosEstorno['produtos']) ||
                !isset($dadosEstorno['numero']) ||
                !isset($dadosEstorno['cliente'])
            ) {
                emitirErro(
                    "Para estorno, os campos 'chaveReferenciada', 'numero', 'cliente' e 'produtos' são obrigatórios.",
                    400
                );
            }

            if (strlen($dadosEstorno['chaveReferenciada']) != 44) {
                emitirErro("A chave referenciada deve ter 44 dígitos.", 400);
            }

            // (OPCIONAL, MAS É BOM QUE EVITA ERROS, VAMOS VER SE VAI PRECISAR...)
            $consultaOriginal = $this->consultarNotaOriginal($dadosEstorno['chaveReferenciada']);
            if (!$consultaOriginal['autorizada']) {
                emitirErro(
                    "A NF-e original (chave: {$dadosEstorno['chaveReferenciada']}) não está autorizada. Estorno não permitido.",
                    400
                );
            }

            $this->corpoRequisicao['tipoOperacao'] = 0; // ENTRADA
            $this->corpoRequisicao['finalidadeEmissao'] = 3; // AJUSTE
            $this->corpoRequisicao['naturezaOperacao'] = '999 - ESTORNO DE NFE NAO CANCELADA NO PRAZO LEGAL'; // ESTÁ ASSIM EM UM PDF DE ESTORNO ENVIADO POR GONZAGAO

            // VALIDA PRODUTOS (TALVEZ ISSO NÃO PRECISE, IREMOS PASSAR DE LÁ DO WMS (VAMOS PEGAR TODOS ITENS DE LÁ) )
            foreach ($this->corpoRequisicao['produtos'] as &$prod) {
                $prod['cfop'] = '1905';

                // Valida quantidade
                if (!isset($prod['quantidade']) || $prod['quantidade'] <= 0) {
                    emitirErro("Produto com quantidade inválida ou não informada.", 400);
                }

                // Valida valor unitário
                if (!isset($prod['valorUnitario']) || $prod['valorUnitario'] <= 0) {
                    emitirErro("Produto com valor unitário inválido ou não informado.", 400);
                }

                // Define impostos padrões se não informados
                // (Isso já cobre a lógica que estava duplicada)
                if (empty($prod['impostos'])) {
                    $prod['impostos'] = [
                        'icms' => ['CST' => '41', 'orig' => 0], // Não tributado
                        'pis' => ['CST' => '49'], // Outras operações
                        'cofins' => ['CST' => '49'] // Outras operações
                    ];
                }
            }
            unset($prod);

            // $infoAdicional = sprintf(
            //     // AQUI DENTRO VAI PEGAR DE INFORMAÇÕES DA NOTA DE SAÍDA...
            //     // EXEMPLO QUE ESTÁ EM UMA NOTA DE ESTORNO QUE GONZAGAO ME MANDOU -> NAO INCIDE ICMS, DEC. 13.780/12-RICMS/BA, LEI No 7.014/1996-SUBSECAO II ARTIGO 3oRESPALDA A NAO-INCIDENCIA
            // );

            // $this->corpoRequisicao['informacoesAdicionais'] = $infoAdicional;

            $this->enviar();

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }


    public function consultarNotaOriginal($chave)
    {
        try {
            $response = $this->tools->sefazConsultaChave($chave);
            $stdCl = new Standardize();
            $std = $stdCl->toStd($response);

            // 100 = Autorizada, 101 = Cancelada, 110 = Uso Denegado
            $autorizada = isset($std->protNFe->infProt->cStat) &&
                        in_array($std->protNFe->infProt->cStat, [100, 101]);

            return [
                'autorizada' => $autorizada,
                'situacao' => $std->protNFe->infProt->cStat ?? null,
                'motivo' => $std->protNFe->infProt->xMotivo ?? 'Não consultada'
            ];

        } catch (\Exception $e) {
            // Se falhar a consulta, permite o estorno (pode estar offline)
            return [
                'autorizada' => true,
                'situacao' => null,
                'motivo' => 'Consulta não realizada: ' . $e->getMessage()
            ];
        }
    }


    ### SALVAR NO BANCO DE DADOS ###
    public function salvarXMLInutilizado($infInut, $xml)
    {
        $cnpjLimpo = soNumeros($this->config['cnpj']);
        $dir = __DIR__ . "/../storage/notas/{$cnpjLimpo}/inutilizadas";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ano = date('Y');
        if (isset($infInut->ano)) {
            $ano = $infInut->ano;
        }

        $serie = 'NA';
        if (isset($infInut->serie)) {
            $serie = $infInut->serie;
        }

        $nIni = 'NA';
        if (isset($infInut->nNFIni)) {
            $nIni = $infInut->nNFIni;
        }

        $nFin = 'NA';
        if (isset($infInut->nNFFin)) {
            $nFin = $infInut->nNFFin;
        }

        $nomeArquivo = "{$ano}-{$serie}-{$nIni}-{$nFin}-inut.xml";

        file_put_contents("{$dir}/{$nomeArquivo}", $xml);
    }


    ### SALVAR NO BANCO DE DADOS ###
    public function salvarXML($chave, $xml)
    {
        $cnpjLimpo = soNumeros($this->config['cnpj']);
        $dir = __DIR__ . "/../storage/notas/{$cnpjLimpo}/autorizadas";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents("{$dir}/{$chave}-nfe.xml", $xml);
    }


    ### SALVAR NO BANCO DE DADOS ###
    public function salvarXMLCancelado($chave, $xml)
    {
        $cnpjLimpo = soNumeros($this->config['cnpj']);
        $dir = __DIR__ . "/../storage/notas/{$cnpjLimpo}/canceladas";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $nomeArquivo = str_replace('-nfe', '', $chave) . '-canc.xml';
        file_put_contents("{$dir}/{$nomeArquivo}", $xml);
    }

    ### SALVAR NO BANCO DE DADOS ###
    public function salvarXMLCCe($chave, $xml, $sequencia)
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $this->config['cnpj']);
        $dir = __DIR__ . "/../storage/notas/{$cnpjLimpo}/cce";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $nomeArquivo = str_replace('-nfe', '', $chave) . "-cce-{$sequencia}.xml";
        file_put_contents("{$dir}/{$nomeArquivo}", $xml);
    }

}