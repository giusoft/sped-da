<?php

namespace App\Controller;

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\DA\NFe\Danfe;
use NFePHP\NFe\Complements;

class NFeController
{
    private $tools;
    private $config;

    /**
     * Carrega o contexto da empresa (config + tools)
     *
     * @param string $cnpj O CNPJ da empresa emitente (somente números)
     * @return void
     * @throws \Exception
     */
    public function carregarEmpresas($cnpj)
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpjLimpo) != 14) {
            throw new \Exception("CNPJ inválido: {$cnpj}");
        }

        $configPath = __DIR__ . "/../../config/empresas/{$cnpjLimpo}.json";
        if (!file_exists($configPath)) {
            throw new \Exception("Arquivo de configuração não encontrado para o CNPJ: {$cnpjLimpo}");
        }
        $configJson = file_get_contents($configPath);
        $this->config = json_decode($configJson, true);

        $certNome = "certificado.pfx";
        $certSenha = $this->config['senhaCertificado'];
        $certPath = __DIR__ . "/../../storage/certificados/{$cnpjLimpo}/{$certNome}";
        if (!file_exists($certPath)) {
            throw new \Exception("Arquivo de certificado não encontrado: {$certPath}");
        }

        $certificate = Certificate::readPfx(
            file_get_contents($certPath),
            $certSenha
        );

        $this->tools = new Tools(json_encode($this->config), $certificate);
        $this->tools->model('55');
    }


    public function enviarNFe()
    {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);

            if (empty($dados['cnpj_emitente'])) {
                throw new \Exception('O campo "cnpj_emitente" é obrigatório.');
            }
            $this->carregarEmpresas($dados['cnpj_emitente']);

            // 1. MONTA O XML
            $nfe = $this->montarXML($dados);
            $xmlString = $nfe->getXML();

            // 2. ASSINA O XML (já faz validação automática)
            $xmlAssinado = $this->tools->signNFe($xmlString);

            // 3. ENVIA PARA SEFAZ (modo síncrono - indSinc=1)
            $idLote = str_pad(time(), 15, '0', STR_PAD_LEFT);
            $response = $this->tools->sefazEnviaLote([$xmlAssinado], $idLote, 1); // 1 = modo síncrono

            // 4. PROCESSA RESPOSTA
            $stdCl = new Standardize();
            $std = $stdCl->toStd($response);

            // Verifica se houve erro no lote primeiro
            if (isset($std->cStat) && !in_array($std->cStat, [100, 103, 104])) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'erro' => 'Erro ao processar lote',
                    'codigo' => $std->cStat,
                    'mensagem' => $std->xMotivo ?: 'Erro desconhecido'
                ]);
                return;
            }

            // Verifica se veio com protocolo (resposta síncrona)
            if (isset($std->protNFe->infProt)) {
                $cStat = $std->protNFe->infProt->cStat;

                if (in_array($cStat, [100, 150])) {
                    // 100: Autorizado, 150: Autorizado fora de prazo
                    $protocolo = $std->protNFe->infProt->nProt;
                    $chave = $std->protNFe->infProt->chNFe;

                    $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $response);

                    // Salva XML
                    $this->salvarXML($chave, $xmlProtocolado);

                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'chave' => $chave,
                        'protocolo' => $protocolo,
                        'mensagem' => $std->protNFe->infProt->xMotivo ?: 'Autorizada',
                        'cStat' => $cStat,
                        'dhRecbto' => $std->protNFe->infProt->dhRecbto ?: null,
                        'xml' => base64_encode($xmlProtocolado)
                    ]);
                } else {
                    // Nota rejeitada
                    http_response_code(400);
                    echo json_encode([
                        'sucesso' => false,
                        'erro' => 'Nota rejeitada',
                        'codigo' => $cStat,
                        'mensagem' => $std->protNFe->infProt->xMotivo ?: 'Erro desconhecido'
                    ]);
                }
            } elseif (isset($std->infRec->nRec)) {
                // Resposta assíncrona (fallback) - consulta o recibo
                $recibo = $std->infRec->nRec;
                sleep(3);
                $protocolo = $this->consultarProtocolo($recibo, $xmlAssinado);

                http_response_code($protocolo['success'] ? 200 : 400);
                echo json_encode($protocolo);
            } else {
                // Erro no lote
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'erro' => 'Erro ao processar lote',
                    'codigo' => $std->cStat ?: 'N/A',
                    'mensagem' => $std->xMotivo ?: 'Resposta inesperada da SEFAZ'
                ]);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
    }


    public function montarXML($dados)
    {
        $nfe = new Make();

        // ===== IDENTIFICAÇÃO DA NFe =====
        $std = new \stdClass();
        $std->versao = '4.00';
        $nfe->taginfNFe($std);

        $std = new \stdClass();
        $std->cUF = $this->config['cUF'];
        $std->cNF = sprintf('%08d', rand(1, 99999999));
        $std->natOp = $dados['naturezaOperacao'] ?: 'VENDA DE MERCADORIA';
        $std->mod = 55;
        $std->serie = $dados['serie'] ?: 1;
        $std->nNF = $dados['numero'];
        $std->dhEmi = date('Y-m-d\TH:i:sP');
        $std->dhSaiEnt = date('Y-m-d\TH:i:sP');
        $std->tpNF = 1;

        // Define idDest baseado na UF do destinatário
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
        $nfe->tagenderEmit($std);

        // ===== DESTINATÁRIO =====
        $cli = $dados['cliente'];
        $std = new \stdClass();
        $std->xNome = $cli['nome'];
        if (!empty($cli['cnpj'])) {
            $std->CNPJ = preg_replace('/[^0-9]/', '', $cli['cnpj']);
            $std->indIEDest = 9;
        } else {
            $std->CPF = preg_replace('/[^0-9]/', '', $cli['cpf']);
            $std->indIEDest = 9;
        }
        $nfe->tagdest($std);

        $std = new \stdClass();
        $std->xLgr = $cli['endereco'];
        $std->nro = $cli['numero'];
        $std->xBairro = $cli['bairro'];
        $std->cMun = $cli['codigoMunicipio'];
        $std->xMun = $cli['municipio'];
        $std->UF = $cli['uf'];
        $std->CEP = preg_replace('/[^0-9]/', '', $cli['cep']);
        $std->cPais = 1058;
        $std->xPais = 'BRASIL';
        $nfe->tagenderDest($std);

        // ===== PRODUTOS =====
        $totalProdutos = 0;
        foreach ($dados['produtos'] as $i => $prod) {
            $item = $i + 1;
            $vProd = (float)$prod['quantidade'] * (float)$prod['valorUnitario'];
            $totalProdutos += $vProd;

            $std = new \stdClass();
            $std->item = $item;
            $std->cProd = $prod['codigo'];
            $std->cEAN = 'SEM GTIN';
            $std->xProd = $prod['descricao'];
            $std->NCM = preg_replace('/[^0-9]/', '', $prod['ncm']);
            $std->CFOP = $prod['cfop'];
            $std->uCom = $prod['unidade'];
            $std->qCom = number_format($prod['quantidade'], 4, '.', '');
            $std->vUnCom = number_format($prod['valorUnitario'], 10, '.', '');
            $std->vProd = number_format($vProd, 2, '.', '');
            $std->cEANTrib = 'SEM GTIN';
            $std->uTrib = $prod['unidade'];
            $std->qTrib = number_format($prod['quantidade'], 4, '.', '');
            $std->vUnTrib = number_format($prod['valorUnitario'], 10, '.', '');
            $std->indTot = 1;
            $nfe->tagprod($std);

            // Impostos (Simples Nacional)
            $std = new \stdClass();
            $std->item = $item;
            $nfe->tagimposto($std);

            $std = new \stdClass();
            $std->item = $item;
            $std->orig = 0;
            $std->CSOSN = '102';
            $nfe->tagICMSSN($std);

            $std = new \stdClass();
            $std->item = $item;
            $std->CST = '07';
            $nfe->tagPIS($std);

            $std = new \stdClass();
            $std->item = $item;
            $std->CST = '07';
            $nfe->tagCOFINS($std);
        }

        // ===== TOTAIS =====
        $std = new \stdClass();
        $std->vBC = 0.00;
        $std->vICMS = 0.00;
        $std->vICMSDeson = 0.00;
        $std->vFCP = 0.00;
        $std->vBCST = 0.00;
        $std->vST = 0.00;
        $std->vFCPST = 0.00;
        $std->vFCPSTRet = 0.00;
        $std->vProd = number_format($totalProdutos, 2, '.', '');
        $std->vFrete = 0.00;
        $std->vSeg = 0.00;
        $std->vDesc = 0.00;
        $std->vII = 0.00;
        $std->vIPI = 0.00;
        $std->vIPIDevol = 0.00;
        $std->vPIS = 0.00;
        $std->vCOFINS = 0.00;
        $std->vOutro = 0.00;
        $std->vNF = number_format($totalProdutos, 2, '.', '');
        $nfe->tagICMSTot($std);

        // ===== TRANSPORTE =====
        $std = new \stdClass();
        $std->modFrete = 9;
        $nfe->tagtransp($std);

        // ===== PAGAMENTO =====
        $std = new \stdClass();
        $std->vTroco = 0.00;
        $nfe->tagpag($std);

        $std = new \stdClass();
        $std->tPag = '01';
        $std->vPag = number_format($totalProdutos, 2, '.', '');
        $nfe->tagdetPag($std);

        // ===== INFORMAÇÕES ADICIONAIS =====
        $std = new \stdClass();
        $std->infCpl = 'DOCUMENTO EMITIDO POR ME OU EPP OPTANTE PELO SIMPLES NACIONAL. NAO GERA DIREITO A CREDITO FISCAL DE IPI.';
        $nfe->taginfAdic($std);

        // ===== AUTORIZAÇÃO (OBRIGATÓRIO PARA BA) =====
        // Se não tiver contador, informar CNPJ da SEFAZ-BA
        $std = new \stdClass();
        $std->CNPJ = '13937073000156'; // CNPJ da SEFAZ-BA (padrão se não tiver contador)
        $nfe->tagautXML($std);

        return $nfe;
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


    public function salvarXML($chave, $xml)
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $this->config['cnpj']);
        $dir = __DIR__ . "/../../storage/notas/{$cnpjLimpo}/autorizadas";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents("{$dir}/{$chave}-nfe.xml", $xml);
    }


    public function gerarDanfe()
    {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);

            if (empty($dados['cnpj_emitente'])) {
                throw new \Exception('O campo "cnpj_emitente" é obrigatório.');
            }
            $this->carregarEmpresas($dados['cnpj_emitente']);

            $chave = $dados['chave'];
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $this->config['cnpj']);
            $xmlPath = __DIR__ . "/../../storage/notas/{$cnpjLimpo}/autorizadas/{$chave}-nfe.xml";

            if (!file_exists($xmlPath)) {
                http_response_code(404);
                echo json_encode(['erro' => 'XML não encontrado']);
                return;
            }

            $xml = file_get_contents($xmlPath);

            $danfe = new Danfe($xml);
            $pdf = $danfe->render();

            $pdfPath = __DIR__ . "/../../storage/notas/{$cnpjLimpo}/autorizadas/{$chave}-danfe.pdf";
            file_put_contents($pdfPath, $pdf);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'pdf' => base64_encode($pdf)
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
        }
    }


    public function cancelarNFe()
    {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);

            if (empty($dados['cnpj_emitente'])) {
                throw new \Exception('O campo "cnpj_emitente" é obrigatório.');
            }
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

            $response = $this->tools->sefazCancela($chave, $justificativa, $protocolo);

            $stdCl = new Standardize();
            $std = $stdCl->toStd($response);

            if (isset($std->retEvento->infEvento) && $std->retEvento->infEvento->cStat == 135) {
                // Evento registrado com sucesso
                $protocoloCancelamento = $std->retEvento->infEvento->nProt ?: '';

                // Salva XML do cancelamento
                $this->salvarXMLCancelado($chave, $response);

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


    public function consultarNFe()
    {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);

            if (empty($dados['cnpj_emitente'])) {
                throw new \Exception('O campo "cnpj_emitente" é obrigatório.');
            }
            $this->carregarEmpresas($dados['cnpj_emitente']);

            $chave = $dados['chave'] ?: '';
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


    public function salvarXMLCancelado($chave, $xml)
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $this->config['cnpj']);
        $dir = __DIR__ . "/../../storage/notas/{$cnpjLimpo}/canceladas";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $nomeArquivo = str_replace('-nfe', '', $chave) . '-canc.xml';
        file_put_contents("{$dir}/{$nomeArquivo}", $xml);
    }
}