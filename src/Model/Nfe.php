<?php

namespace App\Model;

use Exception;
use NFePHP\NFe\MakeDev;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Factories\Contingency;
use NFePHP\NFe\Common\Standardize;
use NFePHP\Common\Certificate;
use NFePHP\Common\Validator;

date_default_timezone_set('America/Bahia');

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
        $this->default['versaoLayoutNFe'] = 'PL_010_V1.30';
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
        $this->default['ufs_svc_rs'] = [
            '13', // AM - Amazonas
            '29', // BA - Bahia
            '52', // GO - Goiás
            '21', // MA - Maranhão
            '50', // MS - Mato Grosso do Sul
            '51', // MT - Mato Grosso
            '26', // PE - Pernambuco
            '41', // PR - Paraná
        ];
    }


    public function enviar()
    {
        try {

            $retorno = 'XML submetido com sucesso para processamento';
            if (in_array($this->corpoRequisicao['modoOperacao'], $this->default['modoContingencia'])) {

                $cUF = $this->corpoRequisicao['empresa']['cUF'];
                $tipoContingencia = in_array($cUF, $this->default['ufs_svc_rs']) ? 'SVCRS' : 'SVCAN';

                $dadosContingencia = json_encode([
                    "motive" => "SEFAZ fora do AR", // Temos que passar o motivo que entrou em Contingencia
                    "timestamp" => strtotime($this->corpoRequisicao['dataHoraContingencia']),
                    "tpEmis" => $this->corpoRequisicao['modoOperacao'],
                    "type" => $tipoContingencia
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
        } catch (Exception $e) {
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
        $nfe = new MakeDev($this->default['versaoLayoutNFe']);

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
        $std->indIEDest = (int) $cli['indIEDest'];

        if (!empty($cli['cnpj'])) {
            $std->CNPJ = soNumeros($cli['cnpj']); // Documento do destinatário
        } else {
            if (isset($cli['cpf'])) {
                $std->CPF = soNumeros($cli['cpf']);   // Documento do destinatário
            }
        }
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

         if ($this->corpoRequisicao['empresa']['cUF'] == 26) {
            // ===== RESPONSAVEL TECNICO =====
            $std = new \stdClass();
            $std->CNPJ = '01108339000179';
            $std->xContato = 'Setor desenvolvimento Giusoft';
            $std->email = 'sistemas@giusoft.com.br';
            $std->fone = '7134020123';
            $nfe->taginfRespTec($std);
        }

        // ===== PRODUTOS =====
        $totalProdutos = 0;
        $totalIs = 0;
        $totalIbs = 0;
        $totalCbs = 0;
        $totalBaseCalculoIbsCbs = 0;

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
            $std->qCom = formatarDecimal($prod['quantidade'], 4); // Quantidade
            $std->vUnCom = formatarDecimal($prod['valorUnitario'], 10); // Valor Unitário
            $std->vProd = formatarDecimal($vProd, 2); // Valor Total
            $std->cEANTrib = $prod['cEANTrib'] ?? 'SEM GTIN'; // Código de barras do produto para tributação
            $std->uTrib = $prod['unidade'] ?? 'UN'; // Unidade de medida para tributação
            $std->qTrib = formatarDecimal($prod['quantidade'], 4); // Quantidade tributável
            $std->vUnTrib = formatarDecimal($prod['valorUnitario'], 10); // Valor unitário tributável
            $std->indTot = 1; // 1 = inclui no total da NF
            $nfe->tagprod($std);

            if((int) $prod['nfEntrada'] > 0) {
                $std = new \stdClass();
                $std->item = $item;
                $std->infAdProd = "NF de cobertura: " . (int) $prod['nfEntrada'] . '/' . (int) $prod['serieEntrada'];
                $nfe->taginfAdProd($std);
            }

            // TAG IMPOSTO (container principal)
            $std = new \stdClass();
            $std->item = $item; // Número do item
            $nfe->tagimposto($std);

            $impostos = $prod['impostos'] ?? [];

            // ICMS
            $icms = $impostos['icms'] ?? [];
            if ($icms && !$this->corpoRequisicao['empresa']['desativarImpostosAntigos']) {
                $icmsTag = new \stdClass();

                $cst = str_pad($icms['CST'] ?? '00', 2, '0', STR_PAD_LEFT);
                $icmsTag->item = $item;
                $icmsTag->orig = (int) ($icms['orig'] ?? 0);
                $icmsTag->CST = $cst;

                if ($cst === '00') {
                    // Tributada integralmente - Grupo ICMS00
                    $icmsTag->modBC = (int) ($icms['modBC'] ?? 3);
                    if (isset($icms['vBC']) && isset($icms['aliquota'])) {
                        $icmsTag->vBC = formatarDecimal($icms['vBC'], 2);
                        $icmsTag->pICMS = formatarDecimal($icms['aliquota'], 2);
                        $icmsTag->vICMS = formatarDecimal($icms['vBC'] * $icms['aliquota'] / 100, 2);
                    }

                    // FCP (opcional) - Tags do FCP normal
                    // if (isset($icms['vBCFCP']) && isset($icms['pFCP'])) {
                    //     $icmsTag->vBCFCP = formatarDecimal($icms['vBCFCP'], 2);
                    //     $icmsTag->pFCP = formatarDecimal($icms['pFCP'], 2);
                    //     $icmsTag->vFCP = formatarDecimal($icms['vBCFCP'] * $icms['pFCP'] / 100, 2);
                    // }

                } elseif ($cst === '10') {
                    // Tributada e com cobrança do ICMS por substituição tributária - Grupo ICMS10
                    $icmsTag->modBC = (int) ($icms['modBC'] ?? 3);
                    if (isset($icms['vBC']) && isset($icms['aliquota'])) {
                        $icmsTag->vBC = formatarDecimal($icms['vBC'], 2);
                        $icmsTag->pICMS = formatarDecimal($icms['aliquota'], 2);
                        $icmsTag->vICMS = formatarDecimal($icms['vBC'] * $icms['aliquota'] / 100, 2);
                    }

                    // ICMS ST
                    $icmsTag->modBCST = (int) ($icms['modBCST'] ?? 4);
                    if (isset($icms['pMVAST'])) {
                        $icmsTag->pMVAST = formatarDecimal($icms['pMVAST'], 2);
                    }
                    if (isset($icms['pRedBCST'])) {
                        $icmsTag->pRedBCST = formatarDecimal($icms['pRedBCST'], 2);
                    }
                    if (isset($icms['vBCST']) && isset($icms['pICMSST'])) {
                        $icmsTag->vBCST = formatarDecimal($icms['vBCST'], 2);
                        $icmsTag->pICMSST = formatarDecimal($icms['pICMSST'], 2);
                        $icmsTag->vICMSST = formatarDecimal($icms['vICMSST'] ?? ($icms['vBCST'] * $icms['pICMSST'] / 100), 2);
                    }

                    // FCP (opcional) - Tags do FCP normal e FCP ST
                    // if (isset($icms['vBCFCP']) && isset($icms['pFCP'])) {
                    //     $icmsTag->vBCFCP = formatarDecimal($icms['vBCFCP'], 2);
                    //     $icmsTag->pFCP = formatarDecimal($icms['pFCP'], 2);
                    //     $icmsTag->vFCP = formatarDecimal($icms['vBCFCP'] * $icms['pFCP'] / 100, 2);
                    // }
                    // if (isset($icms['vBCFCPST']) && isset($icms['pFCPST'])) {
                    //     $icmsTag->vBCFCPST = formatarDecimal($icms['vBCFCPST'], 2);
                    //     $icmsTag->pFCPST = formatarDecimal($icms['pFCPST'], 2);
                    //     $icmsTag->vFCPST = formatarDecimal($icms['vBCFCPST'] * $icms['pFCPST'] / 100, 2);
                    // }

                } elseif ($cst === '20') {
                    // Com redução de base de cálculo - Grupo ICMS20
                    $icmsTag->modBC = (int) ($icms['modBC'] ?? 3);
                    if (isset($icms['pRedBC'])) {
                        $icmsTag->pRedBC = formatarDecimal($icms['pRedBC'], 2);
                    }
                    if (isset($icms['vBC']) && isset($icms['aliquota'])) {
                        $icmsTag->vBC = formatarDecimal($icms['vBC'], 2);
                        $icmsTag->pICMS = formatarDecimal($icms['aliquota'], 2);
                        $icmsTag->vICMS = formatarDecimal($icms['vBC'] * $icms['aliquota'] / 100, 2);
                    }

                    // FCP (opcional) - Tags do FCP normal
                    // if (isset($icms['vBCFCP']) && isset($icms['pFCP'])) {
                    //     $icmsTag->vBCFCP = formatarDecimal($icms['vBCFCP'], 2);
                    //     $icmsTag->pFCP = formatarDecimal($icms['pFCP'], 2);
                    //     $icmsTag->vFCP = formatarDecimal($icms['vBCFCP'] * $icms['pFCP'] / 100, 2);
                    // }

                } elseif ($cst === '30') {
                    // Isenta ou Não Tributada com cobrança do ICMS por substituição tributária - Grupo ICMS30
                    $icmsTag->modBCST = (int) ($icms['modBCST'] ?? 4);
                    if (isset($icms['pMVAST'])) {
                        $icmsTag->pMVAST = formatarDecimal($icms['pMVAST'], 2);
                    }

                    if (isset($icms['pRedBCST'])) {
                        $icmsTag->pRedBCST = formatarDecimal($icms['pRedBCST'], 2);
                    }

                    if (isset($icms['vBCST']) && isset($icms['pICMSST'])) {
                        $icmsTag->vBCST = formatarDecimal($icms['vBCST'], 2);
                        $icmsTag->pICMSST = formatarDecimal($icms['pICMSST'], 2);
                        $icmsTag->vICMSST = formatarDecimal($icms['vICMSST'] ?? ($icms['vBCST'] * $icms['pICMSST'] / 100), 2);
                    }

                    // FCP ST (opcional) - Tags do FCP ST
                    // if (isset($icms['vBCFCPST']) && isset($icms['pFCPST'])) {
                    //     $icmsTag->vBCFCPST = formatarDecimal($icms['vBCFCPST'], 2);
                    //     $icmsTag->pFCPST = formatarDecimal($icms['pFCPST'], 2);
                    //     $icmsTag->vFCPST = formatarDecimal($icms['vBCFCPST'] * $icms['pFCPST'] / 100, 2);
                    // }

                } elseif ($cst === '40' || $cst === '41' || $cst === '50') {
                    // Isenta, Não tributada ou Suspensão - Grupo ICMS40/41/50
                    if (isset($icms['vICMSDeson'])) {
                        $icmsTag->vICMSDeson = formatarDecimal($icms['vICMSDeson'], 2);
                    }

                    if (isset($icms['motDesICMS'])) {
                        $icmsTag->motDesICMS = (int) $icms['motDesICMS'];
                    }

                } elseif ($cst === '51') {
                    // Diferimento - Grupo ICMS51
                    $icmsTag->modBC = (int) ($icms['modBC'] ?? 3);
                    if (isset($icms['pRedBC'])) {
                        $icmsTag->pRedBC = formatarDecimal($icms['pRedBC'], 2);
                    }

                    if (isset($icms['vBC'])) {
                        $icmsTag->vBC = formatarDecimal($icms['vBC'], 2);
                    }
                    if (isset($icms['aliquota'])) {
                        $icmsTag->pICMS = formatarDecimal($icms['aliquota'], 2);
                    }

                    if (isset($icms['vBC']) && isset($icms['aliquota'])) {
                        $icmsTag->vICMS = formatarDecimal($icms['vICMS'] ?? ($icms['vBC'] * $icms['aliquota'] / 100), 2);
                    }

                    if (isset($icms['vICMSOp'])) {
                        $icmsTag->vICMSOp = formatarDecimal($icms['vICMSOp'], 2);
                    }
                    if (isset($icms['pDif'])) {
                        $icmsTag->pDif = formatarDecimal($icms['pDif'], 2);
                    }
                    if (isset($icms['vICMSDif'])) {
                        $icmsTag->vICMSDif = formatarDecimal($icms['vICMSDif'], 2);
                    }

                    // if (isset($icms['vBCFCP']) && isset($icms['pFCP'])) {
                    //     $icmsTag->vBCFCP = formatarDecimal($icms['vBCFCP'], 2);
                    //     $icmsTag->pFCP = formatarDecimal($icms['pFCP'], 2);
                    //     $icmsTag->vFCP = formatarDecimal($icms['vBCFCP'] * $icms['pFCP'] / 100, 2);
                    // }

                } elseif ($cst === '60') {
                    // ICMS cobrado anteriormente por substituição tributária - Grupo ICMS60
                    $icmsTag->vBCSTRet = formatarDecimal($icms['vBCSTRet'] ?? 0, 2);
                    $icmsTag->pST = formatarDecimal($icms['pST'] ?? 0, 2); 
                    $icmsTag->vICMSSTRet = formatarDecimal($icms['vICMSSTRet'] ?? 0, 2);

                    // if (isset($icms['vBCFCPSTRet']) && isset($icms['pFCPSTRet'])) {
                    //     $icmsTag->vBCFCPSTRet = formatarDecimal($icms['vBCFCPSTRet'], 2);
                    //     $icmsTag->pFCPSTRet = formatarDecimal($icms['pFCPSTRet'], 2);

                    //     $icmsTag->vFCPSTRet = formatarDecimal($icms['vFCPSTRet'] ?? ($icms['vBCFCPSTRet'] * $icms['pFCPSTRet'] / 100), 2);
                    // }

                } elseif ($cst === '70') {
                    // Com redução de base de cálculo e cobrança do ICMS por substituição tributária - Grupo ICMS70
                    $icmsTag->modBC = (int) ($icms['modBC'] ?? 3);
                    if (isset($icms['pRedBC'])) {
                        $icmsTag->pRedBC = formatarDecimal($icms['pRedBC'], 2);
                    }
                    if (isset($icms['vBC']) && isset($icms['aliquota'])) {
                        $icmsTag->vBC = formatarDecimal($icms['vBC'], 2);
                        $icmsTag->pICMS = formatarDecimal($icms['aliquota'], 2);
                        $icmsTag->vICMS = formatarDecimal($icms['vBC'] * $icms['aliquota'] / 100, 2);
                    }

                    $icmsTag->modBCST = (int) ($icms['modBCST'] ?? 4);
                    if (isset($icms['pMVAST'])) {
                        $icmsTag->pMVAST = formatarDecimal($icms['pMVAST'], 2);
                    }
                    if (isset($icms['pRedBCST'])) {
                        $icmsTag->pRedBCST = formatarDecimal($icms['pRedBCST'], 2);
                    }
                    if (isset($icms['vBCST']) && isset($icms['pICMSST'])) {
                        $icmsTag->vBCST = formatarDecimal($icms['vBCST'], 2);
                        $icmsTag->pICMSST = formatarDecimal($icms['pICMSST'], 2);
                        $icmsTag->vICMSST = formatarDecimal($icms['vICMSST'] ?? ($icms['vBCST'] * $icms['pICMSST'] / 100), 2);
                    }

                    // FCP (opcional) - Tags do FCP normal e FCP ST
                    // if (isset($icms['vBCFCP']) && isset($icms['pFCP'])) {
                    //     $icmsTag->vBCFCP = formatarDecimal($icms['vBCFCP'], 2);
                    //     $icmsTag->pFCP = formatarDecimal($icms['pFCP'], 2);
                    //     $icmsTag->vFCP = formatarDecimal($icms['vBCFCP'] * $icms['pFCP'] / 100, 2);
                    // }
                    // if (isset($icms['vBCFCPST']) && isset($icms['pFCPST'])) {
                    //     $icmsTag->vBCFCPST = formatarDecimal($icms['vBCFCPST'], 2);
                    //     $icmsTag->pFCPST = formatarDecimal($icms['pFCPST'], 2);
                    //     $icmsTag->vFCPST = formatarDecimal($icms['vBCFCPST'] * $icms['pFCPST'] / 100, 2);
                    // }

                } elseif ($cst === '90') {
                    // Outras - Grupo ICMS90 (Preenchido de forma robusta)
                    $icmsTag->modBC = (int) ($icms['modBC'] ?? 3);
                    if (isset($icms['pRedBC'])) {
                        $icmsTag->pRedBC = formatarDecimal($icms['pRedBC'], 2);
                    }
                    if (isset($icms['vBC']) && isset($icms['aliquota'])) {
                        $icmsTag->vBC = formatarDecimal($icms['vBC'], 2);
                        $icmsTag->pICMS = formatarDecimal($icms['aliquota'], 2);
                        $icmsTag->vICMS = formatarDecimal($icms['vBC'] * $icms['aliquota'] / 100, 2);
                    }

                    // ICMS ST
                    $icmsTag->modBCST = (int) ($icms['modBCST'] ?? 4);
                    if (isset($icms['pMVAST'])) {
                        $icmsTag->pMVAST = formatarDecimal($icms['pMVAST'], 2);
                    }
                    if (isset($icms['pRedBCST'])) {
                        $icmsTag->pRedBCST = formatarDecimal($icms['pRedBCST'], 2);
                    }
                    if (isset($icms['vBCST']) && isset($icms['pICMSST'])) {
                        $icmsTag->vBCST = formatarDecimal($icms['vBCST'], 2);
                        $icmsTag->pICMSST = formatarDecimal($icms['pICMSST'], 2);
                        $icmsTag->vICMSST = formatarDecimal($icms['vICMSST'] ?? ($icms['vBCST'] * $icms['pICMSST'] / 100), 2);
                    }

                    // Desoneração
                    if (isset($icms['vICMSDeson'])) {
                        $icmsTag->vICMSDeson = formatarDecimal($icms['vICMSDeson'], 2);
                    }
                    if (isset($icms['motDesICMS'])) {
                        $icmsTag->motDesICMS = (int) $icms['motDesICMS'];
                    }

                    // FCP (opcional) - FCP normal e FCP ST
                    // if (isset($icms['vBCFCP']) && isset($icms['pFCP'])) {
                    //     $icmsTag->vBCFCP = formatarDecimal($icms['vBCFCP'], 2);
                    //     $icmsTag->pFCP = formatarDecimal($icms['pFCP'], 2);
                    //     $icmsTag->vFCP = formatarDecimal($icms['vBCFCP'] * $icms['pFCP'] / 100, 2);
                    // }
                    // if (isset($icms['vBCFCPST']) && isset($icms['pFCPST'])) {
                    //     $icmsTag->vBCFCPST = formatarDecimal($icms['vBCFCPST'], 2);
                    //     $icmsTag->pFCPST = formatarDecimal($icms['pFCPST'], 2);
                    //     $icmsTag->vFCPST = formatarDecimal($icms['vBCFCPST'] * $icms['pFCPST'] / 100, 2);
                    // }
                }

                $nfe->tagICMS($icmsTag);
            }

            // IPI (ADD desativarImpostosAntigos)
            $ipi = $impostos['ipi'] ?? [];
            if (!empty($ipi['CST'])) {
                $std = new \stdClass();
                $std->item = $item;
                $std->cEnq = $ipi['cEnq'];
                $std->CST = str_pad($ipi['CST'], 2, '0', STR_PAD_LEFT);

                // Grupos de CST tributados (00, 49, 50, 99)
                if (in_array($std->CST, ['00', '49', '50', '99'])) {
                    $std->vBC = formatarDecimal($vProd, 2);
                    $std->pIPI = formatarDecimal((float)$ipi['aliquota'], 2);
                    $std->vIPI = formatarDecimal($vProd * ((float)$ipi['aliquota'] / 100), 2);
                }

                $nfe->tagIPI($std);
            }


            // ===== PIS =====
            $pis = $impostos['pis'] ?? [];
            if (!empty($pis['CST']) && !$this->corpoRequisicao['empresa']['desativarImpostosAntigos']) {
                $std = new \stdClass();
                $std->item = $item;
                $std->CST = str_pad(trim($pis['CST'] ?? ''), 2, '0', STR_PAD_LEFT);

                // GRUPO PISAliq: Operação Tributável com Alíquota Percentual (CST 01 e 02)
                if (in_array($std->CST, ['01', '02'])) {
                    $std->vBC = formatarDecimal($vProd, 2);
                    $std->pPIS = formatarDecimal($pis['aliquota'], 4);
                    $std->vPIS = formatarDecimal($vProd * ((float)$pis['aliquota'] / 100), 2);
                    $nfe->tagPIS($std);
                } elseif ($std->CST == '03') { // GRUPO PISQtde: Tributação por Quantidade (CST 03)
                    $std->qBCProd = formatarDecimal($prod['quantidade'], 4);
                    $std->vAliqProd = formatarDecimal((float) $pis['aliquota'], 4);
                    $std->vPIS = formatarDecimal($prod['quantidade'] * $pis['aliquota'], 2);
                    $nfe->tagPIS($std);
                } elseif (in_array($std->CST, ['04', '05', '06', '07', '08', '09'])) { // GRUPO PISNT: Não Tributado (CST 04 a 09)
                    $nfe->tagPIS($std);
                } elseif ($std->CST >= '49' && $std->CST <= '99') { // GRUPO PISOutr: Outras Operações (CST 49 a 99)
                    if ((float) $pis['aliquota'] > 0) {
                        $std->vBC = formatarDecimal($vProd, 2);
                        $std->pPIS = formatarDecimal($pis['aliquota'], 4);
                        $std->vPIS = formatarDecimal($vProd * ((float)$pis['aliquota'] / 100), 2);
                    }
                    $nfe->tagPIS($std);
                }
            }

            // COFINS (ADD desativarImpostosAntigos)
            $cofins = $impostos['cofins'] ?? [];
            if (!empty($cofins['CST']) && !$this->corpoRequisicao['empresa']['desativarImpostosAntigos']) {
                $std = new \stdClass();
                $std->item = $item;
                $std->CST = str_pad($cofins['CST'], 2, '0', STR_PAD_LEFT);

                // GRUPO COFINSAliq: Operação Tributável com Alíquota Percentual (CST 01 e 02)
                if (in_array($std->CST, ['01', '02'])) {
                    $std->vBC = formatarDecimal($vProd, 2);
                    $std->pCOFINS = formatarDecimal($cofins['aliquota'], 4);
                    $std->vCOFINS = formatarDecimal($vProd * ((float)$cofins['aliquota'] / 100), 2);
                    $nfe->tagCOFINS($std);
                } elseif ($std->CST == '03') { // GRUPO COFINSQtde: Tributação por Quantidade (CST 03)
                    $std->qBCProd = formatarDecimal($prod['quantidade'], 4);
                    $std->vAliqProd = formatarDecimal($cofins['aliquota'], 4);
                    $std->vCOFINS = formatarDecimal($prod['quantidade'] * $cofins['aliquota'], 2);
                    $nfe->tagCOFINS($std);
                } elseif (in_array($std->CST, ['04', '05', '06', '07', '08', '09'])) { // GRUPO COFINSNT: Não Tributado (CST 04 a 09)
                    $nfe->tagCOFINS($std);
                } elseif ($std->CST >= '49' && $std->CST <= '99') { // GRUPO COFINSOutr: Outras Operações (CST 49 a 99)
                    if ((float)$cofins['aliquota'] > 0) {
                        $std->vBC = formatarDecimal($vProd, 2);
                        $std->pCOFINS = formatarDecimal($cofins['aliquota'], 2);
                        $std->vCOFINS = formatarDecimal($vProd * ((float)$cofins['aliquota'] / 100), 2);
                    }
                    $nfe->tagCOFINS($std);
                }
            }

            // IBS/CBS (Reforma Tributária)
            $ibs = $impostos['ibscbs'] ?? [];

            // Só processa se houver dados de IBS/CBS
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
                //     $std->vBCIS = formatarDecimal(($is['vBCIS'] ?? 0), 2);
                //     $std->pIS = formatarDecimal(($is['pIS'] ?? 0), 2);
                //     $std->vIS = formatarDecimal($vIS, 2);
                //     $std->uTrib = $is['uTrib'] ?? 'UN';
                //     $std->qTrib = formatarDecimal(($is['qTrib'] ?? 0), 4);
                //     $nfe->tagIS($std);
                //     $totalIs += $vIS;
                // }

                // IBS/CBS (Reforma Tributária)
                $ibs = $impostos['ibscbs'] ?? [];

                // Só processa se houver dados de IBS/CBS
                if ($ibs) {
                    $cst = str_pad($ibs['CST'] ?? '000', 3, '0', STR_PAD_LEFT);

                    $cstPadrao = ['000', '200', '220', '221', '222', '510', '515', '550', '830'];

                    if (in_array($cst, $cstPadrao)) {

                        $valorBaseCalculoIbsCbs = (float)($ibs['vBC'] ?? $vProd);

                        $std = new \stdClass();
                        $std->item = $item;
                        $std->CST = $cst;
                        $std->cClassTrib = str_pad($ibs['cClassTrib'] ?? '', 6, '0', STR_PAD_LEFT);
                        $std->indDoacao = (int)($ibs['indDoacao'] ?? 0);
                        $std->vBC = formatarDecimal($valorBaseCalculoIbsCbs, 2);

                        $std->gIBSUF_pIBSUF   = formatarDecimal(($ibs['gIBSUF_pIBSUF'] ?? 0), 4);
                        $std->gIBSUF_vIBSUF   = formatarDecimal(($ibs['gIBSUF_vIBSUF'] ?? 0), 2);
                        $std->gIBSMun_pIBSMun = formatarDecimal(($ibs['gIBSMun_pIBSMun'] ?? 0), 4);
                        $std->gIBSMun_vIBSMun = formatarDecimal(($ibs['gIBSMun_vIBSMun'] ?? 0), 2);
                        $std->gCBS_pCBS       = formatarDecimal(($ibs['gCBS_pCBS'] ?? 0), 4);
                        $std->gCBS_vCBS       = formatarDecimal(($ibs['gCBS_vCBS'] ?? 0), 2);

                        if (in_array($cst, ['011', '200', '515'])) {
                            $std->gIBSUF_pRedAliq   = formatarDecimal(($ibs['gIBSUF_pRedAliq'] ?? 0), 4);
                            $std->gIBSUF_pAliqEfet  = formatarDecimal(($ibs['gIBSUF_pAliqEfet'] ?? 0), 4);
                            $std->gIBSMun_pRedAliq  = formatarDecimal(($ibs['gIBSMun_pRedAliq'] ?? 0), 4);
                            $std->gIBSMun_pAliqEfet = formatarDecimal(($ibs['gIBSMun_pAliqEfet'] ?? 0), 4);
                            $std->gCBS_pRedAliq     = formatarDecimal(($ibs['gCBS_pRedAliq'] ?? 0), 4);
                            $std->gCBS_pAliqEfet    = formatarDecimal(($ibs['gCBS_pAliqEfet'] ?? 0), 4);
                        }

                        if ($cst === '515') {
                            $std->gIBSUF_pDif  = formatarDecimal(($ibs['gIBSUF_pDif'] ?? 0), 4);
                            $std->gIBSUF_vDif  = formatarDecimal(($ibs['gIBSUF_vDif'] ?? 0), 2);
                            $std->gIBSMun_pDif = formatarDecimal(($ibs['gIBSMun_pDif'] ?? 0), 4);
                            $std->gIBSMun_vDif = formatarDecimal(($ibs['gIBSMun_vDif'] ?? 0), 2);
                            $std->gCBS_pDif    = formatarDecimal(($ibs['gCBS_pDif'] ?? 0), 4);
                            $std->gCBS_vDif    = formatarDecimal(($ibs['gCBS_vDif'] ?? 0), 2);
                        }

                        if (!empty($ibs['gIBSUF_vDevTrib'])) {
                            $std->gIBSUF_vDevTrib = formatarDecimal($ibs['gIBSUF_vDevTrib'], 2);
                        }

                        if (!empty($ibs['gIBSMun_vDevTrib'])) {
                            $std->gIBSMun_vDevTrib = formatarDecimal($ibs['gIBSMun_vDevTrib'], 2);
                        }

                        if (!empty($ibs['gCBS_vDevTrib'])) {
                            $std->gCBS_vDevTrib = formatarDecimal($ibs['gCBS_vDevTrib'], 2);
                        }

                        $nfe->tagIBSCBS($std);

                        $totalIbs += (float)($ibs['gIBSUF_vIBSUF'] ?? 0) + (float)($ibs['gIBSMun_vIBSMun'] ?? 0);
                        $totalCbs += (float)($ibs['gCBS_vCBS'] ?? 0);
                        $totalBaseCalculoIbsCbs += $valorBaseCalculoIbsCbs;

                        if ($cst === '550' && !empty($ibs['CST'])) {
                            $stdReg = new \stdClass();
                            $stdReg->item = $item;
                            $stdReg->CSTReg = str_pad($ibs['CST'], 3, '0', STR_PAD_LEFT);
                            $stdReg->cClassTribReg = str_pad($ibs['cClassTrib'], 6, '0', STR_PAD_LEFT);
                            $stdReg->pAliqEfetRegIBSUF = formatarDecimal($ibs['gIBSUF_pAliqEfet'], 4);
                            $stdReg->vTribRegIBSUF = formatarDecimal($ibs['gIBSUF_vDevTrib'], 2);
                            $stdReg->pAliqEfetRegIBSMun = formatarDecimal($ibs['gIBSMun_pAliqEfet'], 4);
                            $stdReg->vTribRegIBSMun = formatarDecimal($ibs['gIBSMun_vDevTrib'], 2);
                            $stdReg->pAliqEfetRegCBS = formatarDecimal($ibs['gCBS_pAliqEfet'], 4);
                            $stdReg->vTribRegCBS = formatarDecimal($ibs['gCBS_vDevTrib'], 2);
                            $nfe->tagIBSCBSTribRegular($stdReg);
                        }

                    } elseif ($cst === '410') {
                        // Imunidade e não incidência
                        $std = new \stdClass();
                        $std->item = $item;
                        $std->CST = $cst;
                        $std->cClassTrib = str_pad($ibs['cClassTrib'] ?? '', 6, '0', STR_PAD_LEFT);
                        $nfe->tagIBSCBS($std);

                        // Alguns cClassTrib específicos do CST 410 podem exigir crédito presumido
                    } elseif ($cst === '620') {
                        // Tributação Monofásica
                        $stdMono = new \stdClass();
                        $stdMono->item = $item;
                        $stdMono->qBCMono   = formatarDecimal(($ibs['qBCMono'] ?? 0), 4);
                        $stdMono->adRemIBS  = formatarDecimal(($ibs['adRemIBS'] ?? 0), 4);
                        $stdMono->vIBSMono  = formatarDecimal(($ibs['vIBSMono'] ?? 0), 2);
                        $stdMono->adRemCBS  = formatarDecimal(($ibs['adRemCBS'] ?? 0), 4);
                        $stdMono->vCBSMono  = formatarDecimal(($ibs['vCBSMono'] ?? 0), 2);
                        $stdMono->vTotIBSMonoItem = formatarDecimal(($ibs['vTotIBSMonoItem'] ?? $ibs['vIBSMono'] ?? 0), 2);
                        $stdMono->vTotCBSMonoItem = formatarDecimal(($ibs['vTotCBSMonoItem'] ?? $ibs['vCBSMono'] ?? 0), 2);

                        $nfe->tagIBSCBSMono($stdMono);
                        $totalIbs += (float)($stdMono->vTotIBSMonoItem ?? 0);
                        $totalCbs += (float)($stdMono->vTotCBSMonoItem ?? 0);

                    } elseif ($cst === '800') {
                        // Transferência de Crédito
                        $std = new \stdClass();
                        $std->item = $item;
                        $std->CST = $cst;
                        $std->cClassTrib = str_pad($ibs['cClassTrib'] ?? '', 6, '0', STR_PAD_LEFT);

                        $nfe->tagIBSCBS($std);

                        $stdTransf = new \stdClass();
                        $stdTransf->item = $item;
                        $stdTransf->vIBS = formatarDecimal(($ibs['vIBS'] ?? 0), 2);
                        $stdTransf->vCBS = formatarDecimal(($ibs['vCBS'] ?? 0), 2);
                        $nfe->taggTransfCred($stdTransf);

                    } elseif ($cst === '810') {
                        // Crédito Presumido ZFM
                        $std = new \stdClass();
                        $std->item = $item;
                        $std->CST = $cst;
                        $std->cClassTrib = str_pad($ibs['cClassTrib'] ?? '', 6, '0', STR_PAD_LEFT);

                        $nfe->tagIBSCBS($std);

                        $stdZFM = new \stdClass();
                        $stdZFM->item = $item;
                        $stdZFM->competApur = $ibs['competApur'];
                        $stdZFM->tpCredPresIBSZFM = $ibs['tpCredPresIBSZFM'] ?? '0';
                        $stdZFM->vCredPresIBSZFM = formatarDecimal(($ibs['vCredPresIBSZFM'] ?? 0), 2);
                        $nfe->taggCredPresIBSZFM($stdZFM);

                    } elseif ($cst === '811') {
                        // Ajuste de Competência
                        $std = new \stdClass();
                        $std->item = $item;
                        $std->CST = $cst;
                        $std->cClassTrib = str_pad($ibs['cClassTrib'] ?? '', 6, '0', STR_PAD_LEFT);

                        $nfe->tagIBSCBS($std);

                        $stdAjuste = new \stdClass();
                        $stdAjuste->item = $item;
                        $stdAjuste->competApur = $ibs['competApur'];
                        $stdAjuste->vIBS = formatarDecimal(($ibs['vIBSAjuste'] ?? 0), 2);
                        $stdAjuste->vCBS = formatarDecimal(($ibs['vCBSAjuste'] ?? 0), 2);

                        $nfe->taggAjusteCompet($stdAjuste);
                    }
                }
            }
        }

        // Força a inclusão das Tags de Totais (IS, IBS, CBS)
        // A biblioteca pode omitir se forem zero, mas a SEFAZ exige.
        if (!$this->corpoRequisicao['empresa']['usarContingenciaIbsCbs']
            && !in_array($this->corpoRequisicao['modoOperacao'], $this->default['modoContingencia'])
        ) {

            if ($totalIbs > 0 || $totalCbs > 0 || $totalBaseCalculoIbsCbs > 0) {
                // 1. Total de IS
                // $stdISTot = new \stdClass();
                // $stdISTot->vIS = formatarDecimal($totalIs, 2);
                // $nfe->tagISTot($stdISTot);

                // 2. Totais de IBS/CBS
                $stdTotalIbsCbs = new \stdClass();
                $stdTotalIbsCbs->vBCIBSCBS = formatarDecimal($totalBaseCalculoIbsCbs, 2);
                $stdTotalIbsCbs->gIBS_vIBS = formatarDecimal($totalIbs, 2);
                $stdTotalIbsCbs->gCBS_vCBS = formatarDecimal($totalCbs, 2);
                $nfe->tagIBSCBSTot($stdTotalIbsCbs);
            }
        }

        $totalIbs = $totalIbs ?? 0.00;
        $totalCbs = $totalCbs ?? 0.00;

        // $totalNota = $totalProdutos + $totalIs + $totalIbs + $totalCbs; // IS está comentado
        // $totalNota = $totalProdutos + $totalIbs + $totalCbs; // Comentado porque se não me engano em 2026 esse valor não vai ser acrescentado ao valor total da nota
        $totalNota = $totalProdutos;

        $stdTotal = new \stdClass();
        // Formatação do valor total da nota
        $stdTotal->vNFTot = formatarDecimal($totalNota, 2);
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

            $std->pesoL = formatarDecimal($pesoL, 3);
            $std->pesoB = formatarDecimal($pesoB, 3);

            $nfe->tagvol($std);
        }

        // ===== PAGAMENTO =====
        $std = new \stdClass();
        if ($this->corpoRequisicao['tPag'] != 90) {
            $std->vTroco = max($this->corpoRequisicao['vPag'] - $totalNota, 0);
        }
        $nfe->tagpag($std);

        $finalidade = $this->corpoRequisicao['finNFe'] ?? 1; // 1 = NF-e Normal por padrão

        $std = new \stdClass();
        $std->tPag = str_pad($this->corpoRequisicao['tPag'], 2, '0', STR_PAD_LEFT);
        $std->vPag = formatarDecimal($this->corpoRequisicao['vPag'], 2);

        if ($this->corpoRequisicao['tPag'] == 99) {
            $std->xPag = $this->corpoRequisicao['xPag'] ?? 'Outros';
        }

        $nfe->tagdetPag($std);

        // ===== INFORMAÇÕES ADICIONAIS =====
        $std = new \stdClass();
        $std->infCpl = $this->corpoRequisicao['informacoesContribuinte'];
        $std->infAdFisco = $this->corpoRequisicao['informacoesAdicionais'];
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
    
                    $dataHoraRecebimento = null;
                    if (isset($std->protNFe->infProt->dhRecbto)) {
                        $dataHoraRecebimento = $std->protNFe->infProt->dhRecbto;
    
                        $data = new \DateTime($dataHoraRecebimento);
                        $dataHoraRecebimento = $data->format('Y-m-d H:i:s');
                    }
    
                    emitirSucesso(
                        $mensagem,
                        200,
                        [
                            'situacao' => 'Aprovada',
                            'chave' => $chave,
                            'protocolo' => $protocolo,
                            'codigoSituacaoNF' => $cStat,
                            'dataHoraRecebimento' => $dataHoraRecebimento,
                            'xml' => base64_encode($xmlProtocolado)
                        ]
                    );
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
    
            emitirErro(
                $motivo,
                400,
                [
                    'situacao' => 'Reprovada',
                    'codigoSituacaoNF' => $codigoSituacaoNF
                ]
            );
    
        } catch (Exception $e) {
            emitirErro(
                $e->getMessage(),
                500,
                [
                    'situacao' => 'Reprovada'
                ]
            );
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

        } catch (Exception $e) {
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
                    $motivo,
                    200,
                    [
                        'codigo' => $std->infInut->cStat,
                        'protocolo' => $protocolo,
                        'xml' => base64_encode($response) // A resposta já é o XML protocolado
                    ],
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
                    $motivo,
                    400,
                    [
                        'codigo' => $codigo
                    ]
                );
            }

        } catch (Exception $e) {
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

            if (!isset($this->corpoRequisicao['sequencial'])) {
                emitirErro("O campo 'sequencial' é obrigatório", 400);
                return;
            }
            
            $nSeqEvento = (int) $this->corpoRequisicao['sequencial'];
            
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
                    'sequencial' => $nSeqEvento,
                    'data_evento' => $dataEvento,
                    'xml' => base64_encode($xmlProtocolado)
                ]
            );

        } catch (Exception $e) {
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

        } catch (Exception $e) {
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

        } catch (Exception $e) {
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

        } catch (Exception $e) {
            // Se falhar a consulta, permite o estorno (pode estar offline)
            return [
                'autorizada' => true,
                'situacao' => null,
                'motivo' => 'Consulta não realizada: ' . $e->getMessage()
            ];
        }
    }
}