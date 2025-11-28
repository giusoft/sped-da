<?php

namespace App\Model;

use NFePHP\NFe\MakeDev;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Factories\Contingency;
use NFePHP\NFe\Common\Standardize;
use NFePHP\Common\Certificate;
use NFePHP\Common\Validator;

class Nfe
{
    private $corpoRequisicao;
    private $tools;
    private $default;

    public function __construct($dados)
    {
        $this->corpoRequisicao = $dados->corpoRequisicao;
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
        $this->default['tpNF'] = 1; // Tipo de Operação (Entrada = 0 | Saída = 1)
        $this->default['tipoImpressao'] = 1;
        $this->default['tipoEmissao'] = 1;
        $this->default['finNFe'] = 1;
        $this->default['cnjpAutorizadoSefaz'] = '13937073000156';
        $this->default['codigoPais'] = 1058; // Código do Brasil = 1058
        $this->default['modoContingencia'] = [6, 7];
    }


    public function enviar()
    {
        try {

            $retorno = 'XML submetido com sucesso para processamento';
            if (in_array($this->corpoRequisicao['modoOperacao'], $this->default['modoContingencia'])) {
                $dadosContingencia = json_encode([
                    "motive" => "SEFAZ fora do AR", // Temos que passar o motivo que entrou em Contingencia
                    "timestamp" => strtotime($this->corpoRequisicao['dataHoraContingencia']),
                    "tpEmis" => $this->corpoRequisicao['modoOperacao'],
                    "type" => "SVCRS" // Pegar esse dado dinamicamente
                ]);

                $this->tools->contingency = new Contingency($dadosContingencia);
            }

            $retorno .= '| ' . [
                1 => 'Emissão normal',
                2 => 'Contingência FS-IA',
                3 => 'Contingência SCAN',
                4 => 'Contingência DPEC',
                5 => 'Contingência FS-DA',
                6 => 'Contingência SVC-AN',
                7 => 'Contingência SVC-RS'
            ][$this->corpoRequisicao['modoOperacao']] . '  ativado';

            //MONTAR XML
            $xmlMontado = $this->montarXML($this->corpoRequisicao);
            $retorno .= '|Estrutura do XML criada com sucesso';

            //ASSINAR XML
            $xmlAssinado = $this->tools->signNFe($xmlMontado);
            $retorno .= '|XML assinado digitalmente com sucesso';

            //VALIDAR XML
            $xsd = __DIR__ . "/../Lib/sped-nfe/schemes/PL_010_V1.30/nfe_v4.00.xsd";
            try {
                Validator::isValid($xmlAssinado, $xsd);
            } catch (ValidatorException $e) {
                emitirErro(
                    $e->getMessage(),
                    400,
                    [
                        'situacao' => 'Reprovada',
                        'andamento' => $retorno,
                        'xml' => base64_encode($xmlAssinado)
                    ]
                );
            }

            $retorno .= '|XML validado e pronto para envio a SEFAZ';

            // 3. ENVIA PARA SEFAZ (modo síncrono - indSinc=1)
            $idLote = str_pad(time(), 15, '0', STR_PAD_LEFT);

            $xmlsRetornados = array();
            // ESSE PARAMETRO 1 DEVE SER PEGO DO BD (O modo deve ser passado pelo banco de dados)
            // O método sefazEnviaLote ajusta automaticamente o XML para contingência e retorna os XMLs ajustados em $xmlsRetornados
            $retorno .= '|Enviando XML para a SEFAZ';
            $response = $this->tools->sefazEnviaLote(
                [$xmlAssinado],
                $idLote,
                1, // modo síncrono
                false,
                $xmlsRetornados
            );

            $retorno .= '|O XML foi enviado com sucesso para a SEFAZ';

            // IMPORTANTE: Se foi contingência, usar o XML retornado ajustado
            if (!empty($xmlsRetornados)) {
                $xmlAssinado = $xmlsRetornados[0];
            }

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
                    [
                        'situacao' => 'Reprovada',
                        'mensagem' => 'Erro ao processar lote',
                        'codigoSituacaoNF' => $std->cStat,
                        'andamento' => $retorno,
                        'xml' => base64_encode($xmlAssinado)
                    ]
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
                        [
                            'situacao' => 'Reprovada',
                            'mensagem' => 'Nota rejeitada',
                            'codigoSituacaoNF' => $cStat,
                            'andamento' => $retorno,
                            'xml' => base64_encode($xmlAssinado)
                        ]
                    );
                }

                // 100: Autorizado, 150: Autorizado fora de prazo
                $protocolo = $std->protNFe->infProt->nProt;
                $chave = $std->protNFe->infProt->chNFe;

                $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $response);

                $motivo = 'Autorizada';
                if (isset($std->protNFe->infProt->xMotivo)) {
                    $motivo = $std->protNFe->infProt->xMotivo;
                }

                $dataHoraRecebimento = null;
                if (isset($std->protNFe->infProt->dhRecbto)) {
                    $dataHoraRecebimento = $std->protNFe->infProt->dhRecbto;

                    $data = new \DateTime($dataHoraRecebimento);
                    $dataHoraRecebimento = $data->format('Y-m-d H:i:s');
                }

                emitirSucesso(
                    $motivo,
                    200,
                    [
                        'situacao' => 'Aprovada',
                        'chave' => $chave,
                        'protocolo' => $protocolo,
                        'codigoSituacaoNF' => $cStat,
                        'dataHoraRecebimento' => $dataHoraRecebimento,
                        'andamento' => $retorno,
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
                        [
                            'situacao' => 'Reprovada',
                            'mensagem' => 'Erro ao processar lote',
                            'codigoSituacaoNF' => $std->cStat,
                            'andamento' => $retorno
                        ]
                    );
                }

                emitirSucesso(
                    $protocolo,
                    200,
                    [
                        'situacao' => 'Aprovada',
                        'andamento' => $retorno
                    ]
                );
            } else {

                $motivo = 'Resposta inesperada da SEFAZ';
                if (isset($std->xMotivo)) {
                    $motivo = $std->xMotivo;
                }

                // Erro no lote
                emitirErro(
                    $motivo,
                    400,
                    [
                        'situacao' => 'Reprovada',
                        'mensagem' => 'Erro ao processar lote',
                        'codigoSituacaoNF' => $std->cStat,
                        'andamento' => $retorno
                    ]
                );
            }
        } catch (\Exception $e) {
            emitirErro(
                $e->getMessage(),
                500,
                [
                    'situacao' => 'Reprovada',
                    'andamento' => $retorno
                ]
            );
        }
    }


    public function montarXML()
    {
        $nfe = new MakeDev('PL_010_V1.30');

        // ===== IDENTIFICAÇÃO DA NFe =====
        $std = new \stdClass();
        $std->versao = $this->corpoRequisicao['empresa']['versao'];
        $nfe->taginfNFe($std);

        $std = new \stdClass();
        $std->cUF = $this->corpoRequisicao['empresa']['cUF']; // Código da UF (Unidade da Federação) do emitente
        $std->cNF = $this->default['cNF']; // Código numérico da nota
        $std->natOp = $this->corpoRequisicao['naturezaOperacao']; // Natureza da operação
        $std->mod = $this->default['modelo']; // Modelo do documento (55 = NF-e (modelo eletrônico), 65 = NFC-e)

        $std->serie = $this->default['serie']; // Série da nota fiscal
        if (isset($this->corpoRequisicao['serie'])) {
            $std->serie = $this->corpoRequisicao['serie'];
        }

        $std->nNF = $this->corpoRequisicao['numeroNota'];   // Número da nota fiscal
        $std->dhEmi = $this->default['dataEmissao'];    // Data/hora de emissão
        $std->dhSaiEnt = $this->default['dataEmissao']; // Data/hora de saída ou entrada (Opcional — geralmente usada em operações com circulação de mercadoria)

        $std->tpNF = $this->default['tpNF'];
        if (isset($this->corpoRequisicao['tipoOperacao'])) {
            $std->tpNF = $this->corpoRequisicao['tipoOperacao']; // Tipo da NF (0 = Entrada, 1 = Saída)
        }

        // Define idDest baseado na UF do destinatário
        $ufEmitente = $this->corpoRequisicao['empresa']['siglaUF'];
        $ufDestinatario = $this->corpoRequisicao['cliente']['uf'];

        $paisDestinatario = $this->default['codigoPais'];
        if ($this->corpoRequisicao['cliente']['cPais']) {
            $paisDestinatario = $this->corpoRequisicao['cliente']['cPais'];
        }

        if (!isset($this->corpoRequisicao['idDest'])) {
            if ($paisDestinatario != 1058) {
                $std->idDest = 3; // Exterior
            } elseif ($ufEmitente === $ufDestinatario) {
                $std->idDest = 1; // Operação interna
            } else {
                $std->idDest = 2; // Operação interestadual
            }
        } else {
            $std->idDest = $this->corpoRequisicao['idDest'];
        }

        $std->cMunFG = $this->corpoRequisicao['empresa']['cmun']; // Código do município de ocorrência do fato gerador
        $std->tpImp = $this->default['tipoImpressao']; // Tipo de impressão do DANFE (1 = Retrato, 2 = Paisagem);

        $std->tpEmis = $this->default['tipoEmissao']; // Tipo de emissão da NF-e (1 = Normal, 2 = Contingência FS-IA, 3 = SCAN, 4 = DPEC, 5 = FS-DA, 6 = SVC-AN, 7 = SVC-RS, 9 = off-line)
        if (!empty($this->corpoRequisicao['modoOperacao'])) {
            $std->tpEmis = $this->corpoRequisicao['modoOperacao'];
        }

        if (in_array($this->corpoRequisicao['modoOperacao'], $this->default['modoContingencia'])) {
            $std->xJust = "Sefaz fora do ar"; // Aqui tem que ver a necessidade de passar o dado de fora ou não
            $std->dhCont = $this->corpoRequisicao['dataHoraContingencia'];
        }

        // $std->cDV = 0; // Dígito verificador da chave da NF-e;
        $std->tpAmb = $this->corpoRequisicao['empresa']['tpAmb']; // Tipo de ambiente (1 = PRODUÇÃO, 2 = HOMOLOGAÇÃO)

        $std->finNFe = $this->default['finNFe']; // Finalidade de emissão (1 = Normal, 2 = Complementar, 3 = Ajuste, 4 = Devolução)
        if (isset($this->corpoRequisicao['finNFe'])) {
            $std->finNFe = $this->corpoRequisicao['finNFe'];
        }

        $std->indFinal = 1; // Consumidor final (0 = Não, 1 = Sim)

        if (in_array($std->finNFe, [6])) {
            $std->tpNFDebito = '01';
        }

        if (in_array($std->finNFe, [4, 5])) {
            $std->tpNFCredito = '01';
        }

        $std->indPres = 1; // Indicador de presença do comprador (0 = Não se aplica, 1 = Presencial, 2 = Internet, 3 = Teleatendimento)
        $std->procEmi = 0; // Processo de emissão (0 = Emissão pelo próprio contribuinte, 1 = Avulsa Fisco, 2 = Avulsa contrib. com certificado, 3 = Aplicativo do Fisco)
        $std->verProc = 'API EmiteNota 1.0'; // Versão do aplicativo emissor
        $nfe->tagide($std);

        if (!empty($this->corpoRequisicao['chaveEstorno'])) {
            $stdRef = new \stdClass();
            $stdRef->refNFe = $this->corpoRequisicao['chaveEstorno'];
            $nfe->tagrefNFe($stdRef);
        }

        // ===== EMITENTE =====
        $std = new \stdClass();
        $std->xNome = $this->corpoRequisicao['empresa']['razaosocial'];         // Razão social / nome do emitente
        $std->xFant = $this->corpoRequisicao['empresa']['razaosocial'];         // Nome fantasia (Opcional)
        $std->IE = $this->corpoRequisicao['empresa']['ie'];                     // Inscrição estadual (Obrigatória (exceto isento))
        $std->CRT = $this->corpoRequisicao['empresa']['regime'];                // Regime tributário (No nosso caso passamos sempre 3)
        $std->CNPJ = soNumeros($this->corpoRequisicao['empresa']['cnpj']);      // Documento do emitente (Apenas um deve ser informado CNPJ || CPF) - Ver com thiago
        $nfe->tagemit($std);

        $std = new \stdClass();
        $std->xLgr = $this->corpoRequisicao['empresa']['logradouro'];           // Logradouro (rua)
        $std->nro = $this->corpoRequisicao['empresa']['numero'];                // Número
        $std->xBairro = $this->corpoRequisicao['empresa']['bairro'];            // Bairro
        $std->cMun = $this->corpoRequisicao['empresa']['cmun'];                 // Código IBGE do município
        $std->xMun = $this->corpoRequisicao['empresa']['xmun'];                 // Nome do município
        $std->UF = $this->corpoRequisicao['empresa']['siglaUF'];                // Sigla do estado
        $std->CEP = soNumeros($this->corpoRequisicao['empresa']['cep']);        // Código postal
        $std->cPais = $this->corpoRequisicao['empresa']['cPais'];               // Código do país
        $std->xPais = $this->corpoRequisicao['empresa']['xPais'];               // Nome do país
        $std->fone = soNumeros($this->corpoRequisicao['empresa']['fone']);      // Telefone do Emitente
        $nfe->tagenderEmit($std);

        // ===== DESTINATÁRIO =====
        $cli = $this->corpoRequisicao['cliente'];
        $std = new \stdClass();
        $std->xNome = $cli['nome']; // Nome / razão social
        $std->IE = $cli['ie'];

        if (!empty($cli['cnpj'])) {
            $std->CNPJ = soNumeros($cli['cnpj']); // Documento do destinatário
        } else {
            if (isset($cli['cpf'])) {
                $std->CPF = soNumeros($cli['cpf']);   // Documento do destinatário
            }
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
        $std->cPais = $this->corpoRequisicao['empresa']['cPais']; // Código do país (Vai vir nos dados do cliente)
        $std->xPais = $this->corpoRequisicao['empresa']['xPais']; // Nome do país (Vai vir nos dados do cliente)
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

            if ($icms && !$this->corpoRequisicao['empresa']['desativarImpostosAntigos']) {
                $std = new \stdClass();
                $std->item = $item;
                
                // Dados básicos do ICMS (sempre presentes)
                $std->orig = (int) ($icms['orig'] ?? 0);
                $std->CST = str_pad($icms['CST'] ?? '00', 2, '0', STR_PAD_LEFT);
                $std->modBC = (int) ($icms['modBC'] ?? 3);
                
                // Base de cálculo e alíquota (podem vir da API)
                if (isset($icms['vBC'])) {
                    $std->vBC = number_format($icms['vBC'], 2, '.', '');
                }
                if (isset($icms['aliquota'])) {
                    $std->pICMS = number_format($icms['aliquota'], 2, '.', '');
                }
                if (isset($icms['vBC']) && isset($icms['aliquota'])) {
                    $std->vICMS = number_format($icms['vBC'] * $icms['aliquota'] / 100, 2, '.', '');
                }
                
                // Redução de BC
                if (!empty($icms['pRedBC'])) {
                    $std->pRedBC = number_format($icms['pRedBC'], 2, '.', '');
                }
                
                // ICMS ST
                if (!empty($icms['modBCST'])) {
                    $std->modBCST = (int) $icms['modBCST'];
                }
                if (!empty($icms['pMVAST'])) {
                    $std->pMVAST = number_format($icms['pMVAST'], 2, '.', '');
                }
                if (!empty($icms['pRedBCST'])) {
                    $std->pRedBCST = number_format($icms['pRedBCST'], 2, '.', '');
                }
                if (!empty($icms['vBCST'])) {
                    $std->vBCST = number_format($icms['vBCST'], 2, '.', '');
                }
                if (!empty($icms['pICMSST'])) {
                    $std->pICMSST = number_format($icms['pICMSST'], 2, '.', '');
                }
                if (!empty($icms['vICMSST'])) {
                    $std->vICMSST = number_format($icms['vICMSST'], 2, '.', '');
                }
                if (!empty($icms['vICMSSTRet'])) {
                    $std->vICMSSTRet = number_format($icms['vICMSSTRet'], 2, '.', '');
                }
                
                // FCP
                if (!empty($icms['vBCFCP'])) {
                    $std->vBCFCP = number_format($icms['vBCFCP'], 2, '.', '');
                }
                if (!empty($icms['pFCP'])) {
                    $std->pFCP = number_format($icms['pFCP'], 2, '.', '');
                }
                if (!empty($icms['vBCFCPST'])) {
                    $std->vBCFCPST = number_format($icms['vBCFCPST'], 2, '.', '');
                }
                if (!empty($icms['pFCPST'])) {
                    $std->pFCPST = number_format($icms['pFCPST'], 2, '.', '');
                }
                
                $nfe->tagICMS($std);
            }

            // IPI 
            $ipi = $impostos['ipi'] ?? [];
            if (!empty($ipi['CST'])) {
                $std = new \stdClass();
                $std->item = $item;
                $std->cEnq = $ipi['cEnq'];
                $std->CST = str_pad($ipi['CST'], 2, '0', STR_PAD_LEFT);

                // Grupos de CST tributados (00, 49, 50, 99)
                if (in_array($std->CST, ['00', '49', '50', '99'])) {
                    $std->vBC = number_format($vProd, 2, '.', '');
                    $std->pIPI = number_format((float)$ipi['aliquota'], 2, '.', '');
                    $std->vIPI = number_format($vProd * ((float)$ipi['aliquota'] / 100), 2, '.', '');
                }
                
                $nfe->tagIPI($std);
            }
            

            // ===== PIS =====
            $pis = $impostos['pis'] ?? [];
            if (!empty($pis['CST'])) {
                $std = new \stdClass();
                $std->item = $item;
                $std->CST = str_pad($pis['CST'], 2, '0', STR_PAD_LEFT);

                // GRUPO PISAliq: Operação Tributável (CST 01 e 02) [cite: 1050]
                if (in_array($std->CST, ['01', '02'])) {
                    $std->vBC = number_format($vProd, 2, '.', '');
                    $std->pPIS = number_format((float)$pis['aliquota'], 2, '.', '');
                    $std->vPIS = number_format($vProd * ((float)$pis['aliquota'] / 100), 2, '.', '');
                    $nfe->tagPIS($std);
                }
                // GRUPO PISQtde: Tributação por Quantidade (CST 03) [cite: 1051]
                elseif ($std->CST == '03') {
                     $std->qBCProd = number_format($prod['quantidade'], 4, '.', '');
                     $std->vAliqProd = number_format((float)$pis['aliquota'], 4, '.', '');
                     $std->vPIS = number_format($prod['quantidade'] * $pis['aliquota'], 2, '.', '');
                     $nfe->tagPIS($std);
                }
                // GRUPO PISNT: Não Tributado (CST 04, 05, 06, 07, 08, 09) [cite: 1051]
                elseif (in_array($std->CST, ['04', '05', '06', '07', '08', '09'])) {
                    $nfe->tagPIS($std);
                }
                // GRUPO PISOutr: Outras Operações (CST 49 a 99) [cite: 1051]
                else {
                    if ((float)$pis['aliquota'] > 0) {
                        $std->vBC = number_format($vProd, 2, '.', '');
                        $std->pPIS = number_format((float)$pis['aliquota'], 2, '.', '');
                        $std->vPIS = number_format($vProd * ((float)$pis['aliquota'] / 100), 2, '.', '');
                    } else {
                        $std->vBC = '0.00';
                        $std->pPIS = '0.00';
                        $std->vPIS = '0.00';
                    }
                    $nfe->tagPIS($std); // Direciona para tagPISOutr
                }
            }

            // COFINS
            $cofins = $impostos['cofins'] ?? [];
            if (!empty($cofins['CST'])) {
                $std = new \stdClass();
                $std->item = $item;
                $std->CST = str_pad($cofins['CST'], 2, '0', STR_PAD_LEFT);

                // GRUPO COFINSAliq: Operação Tributável (CST 01 e 02) [cite: 1053]
                if (in_array($std->CST, ['01', '02'])) {
                    $std->vBC = number_format($vProd, 2, '.', '');
                    $std->pCOFINS = number_format((float)$cofins['aliquota'], 2, '.', '');
                    $std->vCOFINS = number_format($vProd * ((float)$cofins['aliquota'] / 100), 2, '.', '');
                    $nfe->tagCOFINS($std);
                }
                // GRUPO COFINSQtde: Tributação por Quantidade (CST 03) [cite: 1054]
                elseif ($std->CST == '03') {
                     $std->qBCProd = number_format($prod['quantidade'], 4, '.', '');
                     $std->vAliqProd = number_format((float)$cofins['aliquota'], 4, '.', '');
                     $std->vCOFINS = number_format($prod['quantidade'] * $cofins['aliquota'], 2, '.', '');
                     $nfe->tagCOFINS($std);
                }
                // GRUPO COFINSNT: Não Tributado (CST 04, 05, 06, 07, 08, 09) [cite: 1054]
                elseif (in_array($std->CST, ['04', '05', '06', '07', '08', '09'])) {
                    $nfe->tagCOFINS($std);
                }
                // GRUPO COFINSOutr: Outras Operações (CST 49 a 99) [cite: 1054]
                else {
                    if ((float)$cofins['aliquota'] > 0) {
                        $std->vBC = number_format($vProd, 2, '.', '');
                        $std->pCOFINS = number_format((float)$cofins['aliquota'], 2, '.', '');
                        $std->vCOFINS = number_format($vProd * ((float)$cofins['aliquota'] / 100), 2, '.', '');
                    } else {
                        $std->vBC = '0.00';
                        $std->pCOFINS = '0.00';
                        $std->vCOFINS = '0.00';
                    }
                    $nfe->tagCOFINS($std);
                }
            }

            if (!$this->corpoRequisicao['empresa']['usarContingenciaIbsCbs']
                && !in_array($this->corpoRequisicao['modoOperacao'], $this->default['modoContingencia'])
            ) {
                // IS (Imposto Seletivo)
                // $is = $impostos['is'] ?? [];
                // $vIS = (float)($is['vIS'] ?? 0);
                // if ($is) {
                //     $std = new \stdClass();
                //     $std->item = $item;
                //     $std->CSTIS = str_pad($is['CSTIS'] ?? '000', 3, '0', STR_PAD_LEFT);
                //     $std->cClassTribIS = str_pad($is['cClassTribIS'] ?? '000000', 6, '0', STR_PAD_LEFT);
                //     $std->vBCIS = number_format((float)($is['vBCIS'] ?? 0), 2, '.', '');
                //     $std->pIS = number_format((float)($is['pIS'] ?? 0), 2, '.', '');
                //     $std->vIS = number_format($vIS, 2, '.', '');
                //     $std->uTrib = $is['uTrib'] ?? 'UN';
                //     $std->qTrib = number_format((float)($is['qTrib'] ?? 0), 4, '.', '');
                //     $nfe->tagIS($std);
                //     $totalIS += $vIS;
                // }

               // IBS/CBS (Reforma Tributária)
               $ibs = $impostos['ibscbs'] ?? [];

               // Só processa se houver dados de IBS/CBS
               if ($ibs) {
                   $cst = str_pad($ibs['CST'] ?? '000', 3, '0', STR_PAD_LEFT);

                   $cstPadrao    = ['000', '010', '011', '200', '220', '221', '222', '510', '515', '550', '830'];
                   $cstMonofasico = ['620'];
                   $cstIsencao    = ['400', '410'];
                   $cstOutros     = ['800', '810', '811', '820'];

                   if (in_array($cst, $cstPadrao)) {

                       $vBC_IBSCBS = (float)($ibs['vBC'] ?? $vProd);

                       $std = new \stdClass();
                       $std->item = $item;
                       $std->CST = $cst;
                       $std->cClassTrib = str_pad($ibs['cClassTrib'] ?? '', 6, '0', STR_PAD_LEFT);
                       $std->indDoacao = (int)($ibs['indDoacao'] ?? 0);
                       $std->vBC = number_format($vBC_IBSCBS, 2, '.', '');

                       $std->gIBSUF_pIBSUF   = number_format((float)($ibs['gIBSUF_pIBSUF'] ?? 0), 4, '.', '');
                       $std->gIBSUF_vIBSUF   = number_format((float)($ibs['gIBSUF_vIBSUF'] ?? 0), 2, '.', '');
                       $std->gIBSMun_pIBSMun = number_format((float)($ibs['gIBSMun_pIBSMun'] ?? 0), 4, '.', '');
                       $std->gIBSMun_vIBSMun = number_format((float)($ibs['gIBSMun_vIBSMun'] ?? 0), 2, '.', '');
                       $std->gCBS_pCBS       = number_format((float)($ibs['gCBS_pCBS'] ?? 0), 4, '.', '');
                       $std->gCBS_vCBS       = number_format((float)($ibs['gCBS_vCBS'] ?? 0), 2, '.', '');

                       if (in_array($cst, ['011', '200', '515'])) {
                           $std->gIBSUF_pRedAliq   = number_format((float)($ibs['gIBSUF_pRedAliq'] ?? 0), 4, '.', '');
                           $std->gIBSUF_pAliqEfet  = number_format((float)($ibs['gIBSUF_pAliqEfet'] ?? 0), 4, '.', '');
                           $std->gIBSMun_pRedAliq  = number_format((float)($ibs['gIBSMun_pRedAliq'] ?? 0), 4, '.', '');
                           $std->gIBSMun_pAliqEfet = number_format((float)($ibs['gIBSMun_pAliqEfet'] ?? 0), 4, '.', '');
                           $std->gCBS_pRedAliq     = number_format((float)($ibs['gCBS_pRedAliq'] ?? 0), 4, '.', '');
                           $std->gCBS_pAliqEfet    = number_format((float)($ibs['gCBS_pAliqEfet'] ?? 0), 4, '.', '');
                       }

                       if ($cst === '515') {
                           $std->gIBSUF_pDif  = number_format((float)($ibs['gIBSUF_pDif'] ?? 0), 4, '.', '');
                           $std->gIBSUF_vDif  = number_format((float)($ibs['gIBSUF_vDif'] ?? 0), 2, '.', '');
                           $std->gIBSMun_pDif = number_format((float)($ibs['gIBSMun_pDif'] ?? 0), 4, '.', '');
                           $std->gIBSMun_vDif = number_format((float)($ibs['gIBSMun_vDif'] ?? 0), 2, '.', '');
                           $std->gCBS_pDif    = number_format((float)($ibs['gCBS_pDif'] ?? 0), 4, '.', '');
                           $std->gCBS_vDif    = number_format((float)($ibs['gCBS_vDif'] ?? 0), 2, '.', '');
                       }

                       if (!empty($ibs['gIBSUF_vDevTrib'])) {
                           $std->gIBSUF_vDevTrib = number_format((float)$ibs['gIBSUF_vDevTrib'], 2, '.', '');
                       }

                       if (!empty($ibs['gIBSMun_vDevTrib'])) {
                           $std->gIBSMun_vDevTrib = number_format((float)$ibs['gIBSMun_vDevTrib'], 2, '.', '');
                       }

                       if (!empty($ibs['gCBS_vDevTrib'])) {
                           $std->gCBS_vDevTrib = number_format((float)$ibs['gCBS_vDevTrib'], 2, '.', '');
                       }

                       $nfe->tagIBSCBS($std);

                       $totalIBS += (float)($ibs['gIBSUF_vIBSUF'] ?? 0) + (float)($ibs['gIBSMun_vIBSMun'] ?? 0);
                       $totalCBS += (float)($ibs['gCBS_vCBS'] ?? 0);
                       $totalBC_IBSCBS += $vBC_IBSCBS;

                       if ($cst == '222' && isset($ibs['pRedutorBC'])) {
                           $stdRed = new \stdClass();
                           $stdRed->item = $item;
                           $stdRed->pRedutorBC = number_format((float)$ibs['pRedutorBC'], 4, '.', '');
                           $nfe->tagIBSCBSRedBC($stdRed);
                       }

                   } elseif ($cst === '620') {
                       $stdMono = new \stdClass();
                       $stdMono->item = $item;
                       $stdMono->qBCMono   = number_format((float)($ibs['qBCMono'] ?? 0), 4, '.', '');
                       $stdMono->adRemIBS  = number_format((float)($ibs['adRemIBS'] ?? 0), 4, '.', '');
                       $stdMono->vIBSMono  = number_format((float)($ibs['vIBSMono'] ?? 0), 2, '.', '');
                       $stdMono->adRemCBS  = number_format((float)($ibs['adRemCBS'] ?? 0), 4, '.', '');
                       $stdMono->vCBSMono  = number_format((float)($ibs['vCBSMono'] ?? 0), 2, '.', '');

                       $nfe->tagIBSCBSMono($stdMono);
                       $totalIBS += (float)($ibs['vIBSMono'] ?? 0);
                       $totalCBS += (float)($ibs['vCBSMono'] ?? 0);
                   } elseif ($cst === '800') {
                       $stdTransf = new \stdClass();
                       $stdTransf->item = $item;
                       $stdTransf->vIBSTransf = number_format((float)($ibs['vIBSTransf'] ?? 0), 2, '.', '');
                       $stdTransf->vCBSTransf = number_format((float)($ibs['vCBSTransf'] ?? 0), 2, '.', '');
                       $nfe->tagIBSCBSTransf($stdTransf);
                   } elseif ($cst === '810') {
                       $stdZFM = new \stdClass();
                       $stdZFM->item = $item;
                       $stdZFM->tpCredPresIBSZFM = $ibs['tpCredPresIBSZFM'] ?? '';
                       $stdZFM->vCredPresIBSZFM  = number_format((float)($ibs['vCredPresIBSZFM'] ?? 0), 2, '.', '');
                       $nfe->tagIBSCBSZFM($stdZFM);
                   } elseif ($cst === '811') {
                       $stdAjuste = new \stdClass();
                       $stdAjuste->item = $item;
                       $stdAjuste->competApur = $ibs['competApur'] ?? '';
                       $stdAjuste->vIBSAjuste = number_format((float)($ibs['vIBSAjuste'] ?? 0), 2, '.', '');
                       $stdAjuste->vCBSAjuste = number_format((float)($ibs['vCBSAjuste'] ?? 0), 2, '.', '');

                       $nfe->tagIBSCBSAjuste($stdAjuste);
                   }
               }
           }
       }

       // Força a inclusão das Tags de Totais (IS, IBS, CBS)
       // A biblioteca pode omitir se forem zero, mas a SEFAZ exige.
       if (!$this->corpoRequisicao['empresa']['usarContingenciaIbsCbs']
           && !in_array($this->corpoRequisicao['modoOperacao'], $this->default['modoContingencia'])
       ) {

           if ($totalIBS > 0 || $totalCBS > 0 || $totalBC_IBSCBS > 0) {
               // 1. Total de IS
               // $stdISTot = new \stdClass();
               // $stdISTot->vIS = number_format($totalIS, 2, '.', '');
               // $nfe->tagISTot($stdISTot);

               // 2. Totais de IBS/CBS
               $stdIBSCBSTot = new \stdClass();
               $stdIBSCBSTot->vBCIBSCBS = number_format($totalBC_IBSCBS, 2, '.', '');
               $stdIBSCBSTot->gIBS_vIBS = number_format($totalIBS, 2, '.', '');
               $stdIBSCBSTot->gCBS_vCBS = number_format($totalCBS, 2, '.', '');
               $nfe->tagIBSCBSTot($stdIBSCBSTot);
           }
       }

       $totalIBS = $totalIBS ?? 0.00;
       $totalCBS = $totalCBS ?? 0.00;

       // $totalNota = $totalProdutos + $totalIS + $totalIBS + $totalCBS; // IS está comentado
       $totalNota = $totalProdutos + $totalIBS + $totalCBS;

       $stdTotal = new \stdClass();
       // Formatação do valor total da nota
       $stdTotal->vNFTot = number_format($totalNota, 2, '.', '');
       $nfe->tagtotal($stdTotal);

        // ===== TRANSPORTE =====
        // Modalidade do frete
        $std = new \stdClass();
        $std->modFrete = $this->corpoRequisicao['transporte']['modalidadeFrete']; // Modalidade do frete (0 = emitente, 1 = destinatário, 2 = terceiros, 9 = sem frete)
        $nfe->tagtransp($std);

        // Dados da transportadora
        $transp = $this->corpoRequisicao['transporte']['transportadora'];

        $temCnpj = !empty($transp['cnpj']);
        $temCpf = !empty($transp['cpf']);

        if (($temCnpj || $temCpf) && $std->modFrete <> 9) {
            $std = new \stdClass();
            $std->xNome = $transp['razaoSocial'] ?? '';
            $std->IE = $transp['inscricaoEstadual'] ?? '';
            $std->xEnder = $transp['endereco'] ?? '';
            $std->xMun = $transp['municipio'] ?? '';
            $std->UF = $transp['uf'] ?? '';

            if ($temCnpj) {
                $std->CNPJ = soNumeros($transp['cnpj']);
            } else {
                $std->CPF = soNumeros($transp['cpf']);
            }

            $nfe->tagtransporta($std);
        }

        // Veículos
        $veiculo = $this->corpoRequisicao['transporte']['veiculo'];

        if ($veiculo['placa']) {
            $std = new \stdClass();
            $std->placa = $veiculo['placa'] ?? '';
            $std->UF = $veiculo['uf'] ?? '';
            $std->RNTC = $veiculo['rntc'] ?? '';
            $nfe->tagveicTransp($std);
        }

        // Volumes
        $vol = $this->corpoRequisicao['transporte']['volumes'];

        $qtdInformada = (int) ($vol['quantidade'] ?? 0);
        $pesoL = (float) ($vol['pesoLiquido'] ?? 0);
        $pesoB = (float) ($vol['pesoBruto'] ?? 0);

        if ($qtdInformada > 0 || $pesoL > 0 || $pesoB > 0) {

            $std = new \stdClass();
            if ($qtdInformada > 0) {
                $std->qVol  = $qtdInformada;
                $std->esp   = $vol['especie'] ?? '';
                $std->marca = $vol['marca'] ?? '';
                $std->nVol  = $vol['numeracao'] ?? '';
            }

            $std->pesoL = number_format($pesoL, 3, '.', '');
            $std->pesoB = number_format($pesoB, 3, '.', '');

            $nfe->tagvol($std);
        }

        // ===== PAGAMENTO =====
        $std = new \stdClass();
        $std->vTroco = 0.00;
        $nfe->tagpag($std);

        $finalidade = $this->corpoRequisicao['finNFe'] ?? 1; // 1 = NF-e Normal por padrão

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


    public function cancelar()
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
                emitirErro("Os campos: chave, protocolo e justificativa são obrigatórios", 400);
            }

            if (strlen($justificativa) < 15) {
                emitirErro("A justificativa deve ter no mínimo 15 caracteres", 400);
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

                $mensagem = 'Cancelamento homologado';
                if (isset($std->retEvento->infEvento->xMotivo)) {
                    $mensagem = $std->retEvento->infEvento->xMotivo;
                }

                emitirSucesso(
                    [
                        'success' => true,
                        'mensagem' => $mensagem,
                        'codigo' => $std->retEvento->infEvento->cStat,
                        'protocolo' => $protocoloCancelamento,
                        'xml_cancelamento' => base64_encode($xmlProtocolado)
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


    public function inutilizar()
    {
        try {

            if (
                !isset($this->corpoRequisicao['serie'])
                || !isset($this->corpoRequisicao['numero_inicial'])
                || !isset($this->corpoRequisicao['numero_final'])
                || !isset($this->corpoRequisicao['justificativa'])
            ) {
                emitirErro('Os campos: cnpj_emitente, serie, numero_inicial, numero_final e justificativa são obrigatórios', 400);
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
                emitirErro("Os campos: chave e correção são obrigatórios", 400);
                return;
            }

            if (strlen($correcao) < 15) {
                emitirErro("A correçao deve ter no mínimo 15 caracteres", 400);

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
                    'data_evento' => $dataEvento,
                    'xml' => base64_encode($xmlProtocolado)
                ]
            );

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }


    public function consultar()
    {
        try {

            $chave = '';
            if (isset($this->corpoRequisicao['chave'])) {
                $chave = $this->corpoRequisicao['chave'];
            }

            if (!$chave || strlen($chave) != 44) {
                emitirErro("Chave de acesso válida é obrigatória", 400);
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


    public function estornar()
    {
        try {

            // 1. VALIDAÇÕES OBRIGATÓRIAS
            if (
                !isset($this->corpoRequisicao['chaveEstorno']) ||
                !isset($this->corpoRequisicao['produtos']) ||
                !isset($this->corpoRequisicao['numeroNota']) ||
                !isset($this->corpoRequisicao['cliente'])
            ) {
                emitirErro(
                    "Para estorno, os campos 'chaveEstorno', 'numeroNota', 'cliente' e 'produtos' são obrigatórios.",
                    400
                );
            }

            if (strlen($this->corpoRequisicao['chaveEstorno']) != 44) {
                emitirErro("A chave referenciada deve ter 44 dígitos.", 400);
            }

            // (OPCIONAL, MAS É BOM QUE EVITA ERROS, VAMOS VER SE VAI PRECISAR...)
            $consultaOriginal = $this->consultarNotaOriginal($this->corpoRequisicao['chaveEstorno']);
            if (!$consultaOriginal['autorizada']) {
                emitirErro(
                    "A NF-e original (chave: {$this->corpoRequisicao['chaveEstorno']}) não está autorizada. Estorno não permitido.",
                    400
                );
            }

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
}