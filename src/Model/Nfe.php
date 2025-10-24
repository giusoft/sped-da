<?php

namespace App\Model;

use NFePHP\NFe\Make;
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
            $xmlString = $this->montarXML($this->corpoRequisicao);

            ### DAR RETORNO QUE ELE FOI GERADO ###
            // 2. ASSINA O XML (já faz validação automática)
            $xmlAssinado = $this->tools->signNFe($xmlString);

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
                emitirErro(
                    $std->xMotivo ?: 'Erro desconhecido',
                    400,
                    'Erro ao processar lote',
                    ['codigoSituacaoNF' => $std->cStat]
                );
            }

            // Verifica se veio com protocolo (resposta síncrona)
            if (isset($std->protNFe->infProt)) {
                $cStat = $std->protNFe->infProt->cStat;

                if (!in_array($cStat, [100, 150])) {
                    // Nota rejeitada
                    emitirErro(
                        $std->protNFe->infProt->xMotivo ?: 'Erro desconhecido',
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

                emitirSucesso(
                    $std->protNFe->infProt->xMotivo ?: 'Autorizada',
                    200,
                    [
                        'chave' => $chave,
                        'protocolo' => $protocolo,
                        'codigoSituacaoNF' => $cStat,
                        'dhRecbto' => $std->protNFe->infProt->dhRecbto ?: null,
                        'xml' => base64_encode($xmlProtocolado)
                    ]
                );

            } elseif (isset($std->infRec->nRec)) {
                // Resposta assíncrona (fallback) - consulta o recibo
                $recibo = $std->infRec->nRec;
                $protocolo = $this->consultarProtocolo($recibo, $xmlAssinado);

                if ($protocolo['success'] != 200) {
                    emitirErro(
                        $std->xMotivo ?: 'Erro desconhecido',
                        400,
                        'Erro ao processar lote',
                        ['codigoSituacaoNF' => $std->cStat]
                    );
                }

                emitirSucesso($protocolo, 200);
            } else {
                // Erro no lote
                emitirErro(
                    $std->xMotivo ?: 'Resposta inesperada da SEFAZ',
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


    public function montarXML($dados)
    {
        $nfe = new Make();
        // ===== IDENTIFICAÇÃO DA NFe =====
        $std = new \stdClass();
        $std->versao = $this->default['versao']; // versão layout do XML
        $nfe->taginfNFe($std);

        $std = new \stdClass();
        $std->cUF = $this->config['cUF'];                                  // Código da UF (Unidade da Federação) do emitente
        $std->cNF = $this->default['cNF'];                                 // Código numérico da nota
        $std->natOp = $dados['naturezaOperacao'] ?: 'VENDA DE MERCADORIA'; // Natureza da operação
        $std->mod = $this->default['modelo'];                              // Modelo do documento (55 = NF-e (modelo eletrônico), 65 = NFC-e)
        $std->serie = $dados['serie'] ?: $this->default['serie'];          // Série da nota fiscal
        $std->nNF = $dados['numero'];                                      // Número da nota fiscal
        $std->dhEmi = $this->default['dataEmissao'];                       // Data/hora de emissão
        $std->dhSaiEnt = $this->default['dataEmissao'];                    // Data/hora de saída ou entrada (Opcional — geralmente usada em operações com circulação de mercadoria)
        $std->tpNF = $this->default['tpNF'];                               // Tipo da NF (0 = Entrada, 1 = Saída)

        // Define idDest baseado na UF do destinatário
        $ufEmitente = $this->config['siglaUF'];
        $ufDestinatario = $dados['cliente']['uf'];

        $paisDestinatario = $this->default['codigoPais'];
        if ($dados['cliente']['cPais']) {
            $paisDestinatario = $dados['cliente']['cPais'];
        }

        if ($paisDestinatario != 1058) {
            $std->idDest = 3; // Exterior
        } elseif ($ufEmitente === $ufDestinatario) {
            $std->idDest = 1; // Operação interna
        } else {
            $std->idDest = 2; // Operação interestadual
        }

        $std->cMunFG = $this->config['cmun'];               // Código do município de ocorrência do fato gerador
        $std->tpImp = $this->default['tipoImpressao'];      // Tipo de impressão do DANFE (1 = Retrato, 2 = Paisagem)
        $std->tpEmis = $this->default['tipoImpressao'];     // Tipo de emissão da NF-e (1 = Normal, 2 = Contingência FS-IA, 3 = SCAN, 4 = DPEC, 5 = FS-DA, 6 = SVC-AN, 7 = SVC-RS, 9 = off-line)
        // $std->cDV = 0;                                      // Dígito verificador da chave da NF-e
        $std->tpAmb = $this->config['tpAmb'];               // Tipo de ambiente (1 = PRODUÇÃO, 2 = HOMOLOGAÇÃO)
        $std->finNFe = $this->default['finalidadeEmissao']; // Finalidade de emissão (1 = Normal, 2 = Complementar, 3 = Ajuste, 4 = Devolução)
        $std->indFinal = 1;                                 // Consumidor final (0 = Não, 1 = Sim)
        $std->indPres = 1;                                  // Indicador de presença do comprador (0 = Não se aplica, 1 = Presencial, 2 = Internet, 3 = Teleatendimento)
        $std->procEmi = 0;                                  // Processo de emissão (0 = Emissão pelo próprio contribuinte, 1 = Avulsa Fisco, 2 = Avulsa contrib. com certificado, 3 = Aplicativo do Fisco)
        $std->verProc = 'API GNotas 1.0';                   // Versão do aplicativo emissor
        $nfe->tagide($std);

        // ===== EMITENTE =====
        $std = new \stdClass();
        $std->xNome = $this->config['razaosocial'];    // Razão social / nome do emitente
        $std->xFant = $this->config['razaosocial'];    // Nome fantasia (Opcional)
        $std->IE = $this->config['ie'];                // Inscrição estadual (Obrigatória (exceto isento))
        $std->CRT = $this->config['regime'];           // Regime tributário
        // CNPJ ver com thiago
        $std->CNPJ = soNumeros($this->config['cnpj']); // Documento do emitente (Apenas um deve ser informado CNPJ || CPF)
        $nfe->tagemit($std);

        $std = new \stdClass();
        $std->xLgr = $this->config['logradouro'];    // Logradouro (rua)
        $std->nro = $this->config['numero'];         // Número
        $std->xBairro = $this->config['bairro'];     // Bairro
        $std->cMun = $this->config['cmun'];          // Código IBGE do município
        $std->xMun = $this->config['xmun'];          // Nome do município
        $std->UF = $this->config['siglaUF'];         // Sigla do estado
        $std->CEP = soNumeros($this->config['cep']); // Código postal
        $std->cPais = $this->config['cPais'];        // Código do país
        $std->xPais = $this->config['xPais'];        // Nome do país
        $nfe->tagenderEmit($std);

        // ===== DESTINATÁRIO =====
        $cli = $dados['cliente'];
        $std = new \stdClass();
        $std->xNome = $cli['nome']; // Nome / razão social
        if (!empty($cli['cnpj'])) {
            $std->CNPJ = soNumeros($cli['cnpj']); // Documento do destinatário
            $std->indIEDest = 9; // (Vai vir nos dados do cliente) // Indicador IE destinatário (1 = Contribuinte, 2 = Isento, 9 = Não contribuinte)
        } else {
            $std->CPF = soNumeros($cli['cpf']);   // Documento do destinatário
            $std->indIEDest = 9; // (Vai vir nos dados do cliente) // Indicador IE destinatário (1 = Contribuinte, 2 = Isento, 9 = Não contribuinte)
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
        $std->cPais = $this->config['cPais']; // Código do país (Vai vir nos dados do cliente)
        $std->xPais = $this->config['xPais']; // Nome do país (Vai vir nos dados do cliente)
        $nfe->tagenderDest($std);

        // ===== PRODUTOS =====
        $totalProdutos = 0;
        foreach ($dados['produtos'] as $i => $prod) {
            $item = $i + 1;
            $vProd = (float)$prod['quantidade'] * (float)$prod['valorUnitario'];
            $totalProdutos += $vProd;

            $std = new \stdClass();
            $std->item = $item;                  // Número sequencial do item
            $std->cProd = $prod['codigo'];       // Código interno do produto
            $std->cEAN = 'SEM GTIN';             // Código de barras
            $std->xProd = $prod['descricao'];    // Descrição do produto
            $std->NCM = soNumeros($prod['ncm']); // Código NCM (classificação fiscal)
            $std->CFOP = $prod['cfop'];          // Código Fiscal da Operação
            $std->uCom = $prod['unidade'];       // Unidade
            $std->qCom = number_format($prod['quantidade'], 4, '.', ''); // Quantidade
            $std->vUnCom = number_format($prod['valorUnitario'], 10, '.', ''); // Valor Unitário
            $std->vProd = number_format($vProd, 2, '.', ''); // Valor Total
            $std->cEANTrib = 'SEM GTIN';    // Código de barras do produto para tributação
            $std->uTrib = $prod['unidade']; // Unidade de medida para tributação
            $std->qTrib = number_format($prod['quantidade'], 4, '.', '');       // Quantidade tributável
            $std->vUnTrib = number_format($prod['valorUnitario'], 10, '.', ''); // Valor unitário tributável
            $std->indTot = 1; // 1 = inclui no total da NF
            $nfe->tagprod($std);

            // Impostos (Simples Nacional)
            $std = new \stdClass();
            $std->item = $item;     // Número do item
            $nfe->tagimposto($std);

            $std = new \stdClass();
            $std->item = $item;     // Número do item
            $std->orig = 0;         // Origem da mercadoria (0 = Nacional)
            $std->CSOSN = '102';    // Código de situação SN (101, 102, 103...)
            $nfe->tagICMSSN($std);

            $std = new \stdClass();
            $std->item = $item;     // Número do item
            $std->CST = '07';       // Código de situação tributária (ex: 01, 07)
            $nfe->tagPIS($std);

            $std = new \stdClass();
            $std->item = $item;     // Número do item
            $std->CST = '07';       // Código de situação tributária (ex: 01, 07)
            $nfe->tagCOFINS($std);
        }

        // ===== TOTAIS =====
        $std = new \stdClass();
        $std->vBC = 0.00;           // Base de cálculo do ICMS (somatório dos itens tributáveis)
        $std->vICMS = 0.00;         // Valor total de ICMS da nota
        $std->vICMSDeson = 0.00;    // Valor total de ICMS desonerado
        $std->vFCP = 0.00;          // Valor total do Fundo de Combate à Pobreza (FCP)
        $std->vBCST = 0.00;         // Base de cálculo do ICMS ST (Substituição Tributária)
        $std->vST = 0.00;           // Valor total de ICMS retido por substituição tributária
        $std->vFCPST = 0.00;        // Valor total do FCP retido por ST
        $std->vFCPSTRet = 0.00;     // Valor total do FCP ST retido anteriormente
        $std->vProd = number_format($totalProdutos, 2, '.', ''); // Valor total dos produtos/serviços
        $std->vFrete = 0.00;        // Valor total do frete
        $std->vSeg = 0.00;          // Valor total do seguro
        $std->vDesc = 0.00;         // Valor total de descontos
        $std->vII = 0.00;           // Valor total de imposto de importação
        $std->vIPI = 0.00;          // Valor total de IPI
        $std->vIPIDevol = 0.00;     // Valor total de IPI devolvido
        $std->vPIS = 0.00;          // Valor total de PIS
        $std->vCOFINS = 0.00;       // Valor total de COFINS
        $std->vOutro = 0.00;        // Outros valores (ex: encargos)
        $std->vNF = number_format($totalProdutos, 2, '.', ''); // Valor total da NF (soma geral)
        $nfe->tagICMSTot($std);

        // ===== TRANSPORTE =====
        $std = new \stdClass();
        $std->modFrete = 9;         // Modalidade do frete (0 = emitente, 1 = destinatário, 2 = terceiros, 9 = sem frete)
        $nfe->tagtransp($std);

        // ===== PAGAMENTO =====
        $std = new \stdClass();
        $std->vTroco = 0.00;        // Valor do troco (obrigatório para NFC-e modelo 65)
        $nfe->tagpag($std);

        $std = new \stdClass();
        $std->tPag = '01';          // Tipo de pagamento (01 = dinheiro, 02 = cheque, 03 = cartão, 15 = PIX)
        $std->vPag = number_format($totalProdutos, 2, '.', ''); // Valor pago pelo cliente
        $nfe->tagdetPag($std);

        // ===== INFORMAÇÕES ADICIONAIS =====
        $std = new \stdClass();
        // infCpl = informações complementares ao contribuinte (mostra no DANFE)
        $std->infCpl = 'DOCUMENTO EMITIDO POR ME OU EPP OPTANTE PELO SIMPLES NACIONAL. NAO GERA DIREITO A CREDITO FISCAL DE IPI.';
        $nfe->taginfAdic($std);

        // ===== AUTORIZAÇÃO (OBRIGATÓRIO PARA BA) =====
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

                    // CORREÇÃO: Usar Complements::toAuthorize
                    $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $response);

                    // Salva XML
                    $this->salvarXML($chave, $xmlProtocolado);

                    return [
                        'success' => true,
                        'chave' => $chave,
                        'protocolo' => $protocolo,
                        'mensagem' => $std->protNFe->infProt->xMotivo ?: 'Autorizada',
                        'xml' => base64_encode($xmlProtocolado)
                    ];
                }
            }

            return [
                'success' => false,
                'erro' => $std->xMotivo ?: 'Erro desconhecido',
                'codigo' => $std->cStat ?: 'N/A'
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
            $dados = json_decode(file_get_contents('php://input'), true);

            if (empty($dados['cnpj_emitente'])) {
                throw new \Exception('O campo "cnpj_emitente" é obrigatório.');
            }

            // Carrega o contexto da empresa
            $this->carregarEmpresas($dados['cnpj_emitente']);

            $chave = $dados['chave'] ?: '';
            $protocolo = $dados['protocolo'] ?: '';
            $justificativa = $dados['justificativa'] ?: '';

            if (empty($chave) || empty($protocolo) || empty($justificativa)) {
                http_response_code(400);
                echo json_encode(['erro' => 'chave, protocolo e justificativa são obrigatórios.']);
                return;
            }
            if (strlen($justificativa) < 15) {
                http_response_code(400);
                echo json_encode(['erro' => 'A justificativa deve ter no mínimo 15 caracteres.']);
                return;
            }

            // Envia o cancelamento e captura tanto a requisição quanto a resposta
            $response = $this->tools->sefazCancela($chave, $justificativa, $protocolo);

            // Pega o XML do evento que foi enviado (está disponível após o envio)
            $xmlEvento = $this->tools->lastRequest;

            $stdCl = new Standardize();
            $std = $stdCl->toStd($response);

            if (isset($std->retEvento->infEvento) && $std->retEvento->infEvento->cStat == 135) {
                // Evento registrado com sucesso
                $protocoloCancelamento = $std->retEvento->infEvento->nProt ?: '';

                // Junta o evento enviado com a resposta recebida usando Complements
                $xmlProtocolado = Complements::toAuthorize($xmlEvento, $response);
                $this->salvarXMLCancelado($chave, $xmlProtocolado);

                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'mensagem' => $std->retEvento->infEvento->xMotivo ?: 'Cancelamento homologado',
                    'codigo' => $std->retEvento->infEvento->cStat,
                    'protocolo' => $protocoloCancelamento
                ]);
            } else {
                $motivo = $std->retEvento->infEvento->xMotivo ?: $std->xMotivo ?: 'Erro desconhecido';
                $codigo = $std->retEvento->infEvento->cStat ?: $std->cStat ?: 'N/A';

                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'erro' => $motivo,
                    'codigo' => $codigo
                ]);
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
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


    public function consultarNFe()
    {
        try {

            $chave = $this->corpoRequisicao['chave'] ?: '';
            if (empty($chave) || strlen($chave) != 44) {
                http_response_code(400);
                echo json_encode(['erro' => 'chave de acesso válida é obrigatória.']);
                return;
            }

            $response = $this->tools->sefazConsultaChave($chave);

            $stdCl = new Standardize();
            $std = $stdCl->toStd($response);

            http_response_code(200);
            echo json_encode([
                'situacao' => $std->xMotivo ?: 'Sem informação',
                'codigo' => $std->cStat ?: 'N/A',
                'protocolo' => $std->protNFe->infProt->nProt ?: null,
                'resposta_sefaz' => $std
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
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

        $ano = $infInut->ano ?: date('Y');
        $serie = $infInut->serie ?: 'NA';
        $nIni = $infInut->nNFIni ?: 'NA';
        $nFin = $infInut->nNFFin ?: 'NA';

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

}