<?php

namespace App\Model;

use NFePHP\NFe\MakeDev;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;

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
        $dados = $this->corpoRequisicao;
        $nfe = new MakeDev('PL_010_V1.30');

        // ===== IDENTIFICAÇÃO DA NFe =====
        $std = new \stdClass();
        $std->versao = '4.00';
        $nfe->taginfNFe($std);

        $std = new \stdClass();
        $std->cUF = $this->config['cUF'];
        $std->cNF = sprintf('%08d', rand(1, 99999999));
        $std->natOp = $dados['naturezaOperacao'] ?? 'VENDA DE MERCADORIA';
        $std->mod = 55;
        $std->serie = $dados['serie'] ?? 1;
        $std->nNF = $dados['numero'];
        $std->dhEmi = date('Y-m-d\TH:i:sP');
        $std->dhSaiEnt = date('Y-m-d\TH:i:sP');
        $std->tpNF = 1;

        $ufEmitente = $this->config['siglaUF'];
        $ufDestinatario = $dados['cliente']['uf'];
        if ($ufEmitente == $ufDestinatario) {
            $std->idDest = 1; // Operação interna
        } else {
            $std->idDest = 2; // Operação interestadual
        }

        $std->cMunFG = $this->config['cmun'];
        $std->tpImp = 1;
        $std->tpEmis = 1;
        $std->cDV = 0;
        $std->tpAmb = $this->config['tpAmb'];
        $std->finNFe = 1;
        $std->indFinal = 1;
        $std->indPres = 1;
        $std->procEmi = 0;
        $std->verProc = 'API GNotas 1.0';
        $nfe->tagide($std);

        // ===== EMITENTE =====
        $std = new \stdClass();
        $std->xNome = $this->config['razaosocial'];
        $std->xFant = $this->config['razaosocial'];
        $std->IE = $this->config['ie'];
        $std->CRT = $this->config['regime'];
        $std->CNPJ = preg_replace('/[^0-9]/', '', $this->config['cnpj']);
        $nfe->tagemit($std);

        $std = new \stdClass();
        $std->xLgr = $this->config['logradouro'];
        $std->nro = $this->config['numero'];
        $std->xBairro = $this->config['bairro'];
        $std->cMun = $this->config['cmun'];
        $std->xMun = $this->config['xmun'];
        $std->UF = $this->config['siglaUF'];
        $std->CEP = preg_replace('/[^0-9]/', '', $this->config['cep']);
        $std->cPais = 1058;
        $std->xPais = 'BRASIL';
        $std->fone = preg_replace('/[^0-9]/', '', $this->config['fone'] ?? '');
        $nfe->tagenderEmit($std);

        // ===== DESTINATÁRIO =====
        $cli = $dados['cliente'];
        $std = new \stdClass();
        $std->xNome = $cli['nome'];
        if (!empty($cli['cnpj'])) {
            $std->CNPJ = preg_replace('/[^0-9]/', '', $cli['cnpj']);
        } else {
            $std->CPF = preg_replace('/[^0-9]/', '', $cli['cpf'] ?? '00000000000');
        }
        $std->indIEDest = 9;
        $nfe->tagdest($std);

        $std = new \stdClass();
        $std->xLgr = $cli['endereco'];
        $std->nro = $cli['numero'];
        $std->xBairro = $cli['bairro'];
        $std->cMun = $cli['codigoMunicipio'];
        $std->xMun = $cli['municipio'];
        $std->UF = $cli['uf'];
        $std->CEP = preg_replace('/[^0-9]/', '', $cli['cep']);
        $std->cPais = $cli['cPais'] ?? 1058;
        $std->xPais = 'BRASIL';
        $nfe->tagenderDest($std);

        // ===== PRODUTOS =====
        $totalProdutos = 0;
        $totalIS = 0;
        $totalIBS = 0;
        $totalCBS = 0;
        $totalBC_IBSCBS = 0;

        foreach ($dados['produtos'] as $i => $prod) {
            $item = $i + 1;
            $quantidade = (float)($prod['quantidade'] ?? 1);
            $valorUnitario = (float)($prod['valorUnitario'] ?? 0);
            $vProd = $quantidade * $valorUnitario;
            $totalProdutos += $vProd;

            // TAG PRODUTO
            $std = new \stdClass();
            $std->item = $item;
            $std->cProd = $prod['codigo'] ?? 'SEMPROD';
            $std->cEAN = $prod['cEAN'] ?? 'SEM GTIN';
            $std->xProd = $prod['descricao'] ?? 'PRODUTO SEM DESCRICAO';
            $std->NCM = preg_replace('/[^0-9]/', '', $prod['ncm'] ?? '00000000');
            $std->CFOP = $prod['cfop'] ?? '5102';
            $std->uCom = $prod['unidade'] ?? 'UN';
            $std->qCom = $quantidade;
            $std->vUnCom = number_format($valorUnitario, 2, '.', '');
            $std->vProd = number_format($vProd, 2, '.', '');
            $std->cEANTrib = $prod['cEANTrib'] ?? 'SEM GTIN';
            $std->uTrib = $prod['unidade'] ?? 'UN';
            $std->qTrib = $quantidade;
            $std->vUnTrib = number_format($valorUnitario, 2, '.', '');
            $std->indTot = 1;
            $nfe->tagprod($std);

            // TAG IMPOSTO (container principal)
            $std = new \stdClass();
            $std->item = $item;
            $nfe->tagimposto($std);

            $impostos = $prod['impostos'] ?? [];

            // ICMS
            $icms = $impostos['icms'] ?? [];
            $icmsAliquota = (float)($icms['aliquota'] ?? 18);
            $icmsRedBC = (float)($icms['pRedBC'] ?? 0);
            $bcICMS = $vProd * (1 - $icmsRedBC/100);
            $vICMS = $bcICMS * $icmsAliquota/100;

            if ($icms) {
                $std = new \stdClass();
                $std->item = $item;
                $std->orig = $icms['orig'] ?? 0;
                $std->CST = str_pad($icms['CST'] ?? '00', 2, '0', STR_PAD_LEFT);
                $std->modBC = 3;
                $std->vBC = number_format($bcICMS, 2, '.', '');
                $std->pICMS = number_format($icmsAliquota, 2, '.', '');
                $std->vICMS = number_format($vICMS, 2, '.', '');
                $std->pRedBC = number_format($icmsRedBC, 2, '.', '');
                $nfe->tagICMS($std);
            }

            // PIS
            $pis = $impostos['pis'] ?? [];
            $pisAliquota = (float)($pis['aliquota'] ?? 0.65);
            $vPIS = $vProd * $pisAliquota/100;
            if ($pis) {
                $std = new \stdClass();
                $std->item = $item;
                $std->CST = str_pad($pis['CST'] ?? '06', 2, '0', STR_PAD_LEFT);
                $std->vBC = number_format($vProd, 2, '.', '');
                $std->pPIS = number_format($pisAliquota, 2, '.', '');
                $std->vPIS = number_format($vPIS, 2, '.', '');
                $nfe->tagPIS($std);
            }

            // COFINS
            $cofins = $impostos['cofins'] ?? [];
            $cofinsAliquota = (float)($cofins['aliquota'] ?? 3.00);
            $vCOFINS = $vProd * $cofinsAliquota/100;
            if ($cofins) {
                $std = new \stdClass();
                $std->item = $item;
                $std->CST = str_pad($cofins['CST'] ?? '06', 2, '0', STR_PAD_LEFT);
                $std->vBC = number_format($vProd, 2, '.', '');
                $std->pCOFINS = number_format($cofinsAliquota, 2, '.', '');
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
                $nfe->tagIS($std);
                $totalIS += $vIS;
            }

            // IBS/CBS (Reforma Tributária)
            $ibs = $impostos['ibscbs'] ?? [];
            $vBC_IBSCBS = (float)($ibs['vBC'] ?? $vProd);
            $gIBSUF_vIBSUF = $vBC_IBSCBS * (($ibs['gIBSUF_pAliqEfet'] ?? 0.5)/100);
            $gIBSMun_vIBSMun = $vBC_IBSCBS * (($ibs['gIBSMun_pAliqEfet'] ?? 0.5)/100);
            $gCBS_vCBS = $vBC_IBSCBS * (($ibs['gCBS_pAliqEfet'] ?? 0.5)/100);

            if ($ibs) {
                $std = new \stdClass();
                $std->item = $item;
                $std->CST = str_pad($ibs['CST'] ?? '200', 3, '0', STR_PAD_LEFT);
                $std->cClassTrib = str_pad($ibs['cClassTrib'] ?? '200003', 6, '0', STR_PAD_LEFT);
                $std->indDoacao = (int)($ibs['indDoacao'] ?? 0);

                // Grupo gIBSCBS (tributação regular)
                $std->vBC = number_format($vBC_IBSCBS, 2, '.', '');

                // --- IBS UF ---
                $std->gIBSUF_pIBSUF = number_format((float)($ibs['gIBSUF_pIBSUF'] ?? 0), 4, '.', '');
                $std->gIBSUF_pRedAliq = number_format((float)($ibs['gIBSUF_pRedAliq'] ?? 0), 2, '.', '');
                $std->gIBSUF_pAliqEfet = number_format((float)($ibs['gIBSUF_pAliqEfet'] ?? 0.5), 2, '.', '');
                $std->gIBSUF_vIBSUF = number_format($gIBSUF_vIBSUF, 2, '.', '');

                // --- IBS Municipal ---
                $std->gIBSMun_pIBSMun = number_format((float)($ibs['gIBSMun_pIBSMun'] ?? 0), 4, '.', '');
                $std->gIBSMun_pRedAliq = number_format((float)($ibs['gIBSMun_pRedAliq'] ?? 0), 2, '.', '');
                $std->gIBSMun_pAliqEfet = number_format((float)($ibs['gIBSMun_pAliqEfet'] ?? 0.5), 2, '.', '');
                $std->gIBSMun_vIBSMun = number_format($gIBSMun_vIBSMun, 2, '.', '');

                // --- CBS ---
                $std->gCBS_pCBS = number_format((float)($ibs['gCBS_pCBS'] ?? 0), 4, '.', '');
                $std->gCBS_pRedAliq = number_format((float)($ibs['gCBS_pRedAliq'] ?? 0), 2, '.', '');
                $std->gCBS_pAliqEfet = number_format((float)($ibs['gCBS_pAliqEfet'] ?? 0.5), 2, '.', '');
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

        // ===== TOTAIS (ICMS) =====
        // A biblioteca MakeDev calcula automaticamente os totais de ICMS

        // ===== TRANSPORTE =====
        $std = new \stdClass();
        $std->modFrete = 9; // Sem transporte
        $nfe->tagtransp($std);

        // ===== PAGAMENTO =====
        $totalNota = $totalProdutos + $totalIS + $totalIBS + $totalCBS;
        $std = new \stdClass();
        $std->vTroco = 0.00;
        $nfe->tagpag($std);

        $std = new \stdClass();
        $std->tPag = '01'; // Dinheiro
        $std->vPag = number_format($totalNota, 2, '.', '');
        $nfe->tagdetPag($std);

        // ===== INFORMAÇÕES ADICIONAIS =====
        $std = new \stdClass();
        $std->infCpl = $dados['informacoesAdicionais'] ??
            'DOCUMENTO EMITIDO SOB O NOVO REGIME TRIBUTARIO (IBS/CBS/IS)';
        $nfe->taginfAdic($std);

        // ===== AUTORIZAÇÃO XML (OBRIGATÓRIO PARA BA) =====
        $std = new \stdClass();
        $std->CNPJ = '13937073000156'; // SEFAZ-BA
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