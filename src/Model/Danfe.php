<?php

namespace App\Model;

use NFePHP\NFe\Complements;
use NFePHP\DA\NFe\Danfe as NFeDanfe;
use NFePHP\DA\NFe\Daevento;
use ZipArchive;

date_default_timezone_set('America/Bahia');

class Danfe
{
    private $corpoRequisicao;

    public function __construct($dados)
    {
        $this->corpoRequisicao = $dados->corpoRequisicao;
    }

    public function gerarDanfe()
    {
        try {
            if (empty($this->corpoRequisicao['xml'])) {
                emitirErro("O campo 'xml' (contendo o XML em base64) é obrigatório.", 400);
            }
            if (empty($this->corpoRequisicao['chave']) || empty($this->corpoRequisicao['cnpj_emitente'])) {
                emitirErro("Os campos 'chave' e 'cnpj_emitente' são obrigatórios (para nomear o PDF salvo).", 400);
            }

            $xml = base64_decode($this->corpoRequisicao['xml']);
            if ($xml === false) {
                emitirErro("O XML fornecido não é um base64 válido.", 400);
            }

            $danfe = new NFeDanfe($xml);
            $danfe->setGerarInformacoesAutomaticas(true);

            $espacos = str_repeat(chr(160), 260);
            $creditos = $espacos . 'Giusoft Tecnologia www.giusoft.com.br';

            $danfe->creditsIntegratorFooter($creditos, false);

            $logotipo = $this->obterCaminhoLogo($this->corpoRequisicao['cnpj_emitente']);
            $pdf = $danfe->render($logotipo);

            $pdfBase64 = base64_encode($pdf);

            emitirSucesso(
                "DANFE gerado com sucesso",
                200,
                ['pdf_base64' => $pdfBase64]
            );

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }


    public function gerarDanfeCce()
    {
        try {
            $erros = [];
            if (empty($this->corpoRequisicao['xml'])) {
                $erros[] = "O campo 'xml' (contendo o XML em base64) é obrigatório.";
            }

            if (empty($this->corpoRequisicao['chave'])) {
                $erros[] = "O campo 'chave' é obrigatório.";
            }

            if (empty($this->corpoRequisicao['cnpj_emitente'])) {
                $erros[] = "O campo 'cnpj_emitente' é obrigatório (necessário para nomear o PDF salvo).";
            }

            if (!empty($erros)) {
                emitirErro(implode("\n", $erros), 400);
                return;
            }

            $xml = base64_decode($this->corpoRequisicao['xml']);

            $dadosEmitente = [
                'razao' => $this->corpoRequisicao['empresa']['razaosocial'] ?? '',
                'logradouro' => $this->corpoRequisicao['empresa']['logradouro'] ?? '',
                'numero' => $this->corpoRequisicao['empresa']['numero'] ?? '',
                'bairro' => $this->corpoRequisicao['empresa']['bairro'] ?? '',
                'CEP' => soNumeros($this->corpoRequisicao['empresa']['cep']) ?? '',
                'municipio' => $this->corpoRequisicao['empresa']['xmun'] ?? '',
                'UF' => $this->corpoRequisicao['empresa']['siglaUF'] ?? '',
                'telefone' => soNumeros($this->corpoRequisicao['empresa']['fone']) ?? '',
                'email' => $this->corpoRequisicao['empresa']['email'] ?? ''
            ];

            $daEvento = new Daevento($xml, $dadosEmitente);
            $espacos = str_repeat(chr(160), 260); // <-- Ajuste este número
            $creditos = $espacos . 'Giusoft Tecnologia www.giusoft.com.br';

            $daEvento->creditsIntegratorFooter($creditos, false);

            $logotipo = $this->obterCaminhoLogo($this->corpoRequisicao['cnpj_emitente']);
            $pdf = $daEvento->render($logotipo);

            emitirSucesso(
                "DANFE CC-e gerado com sucesso",
                200,
                ['pdf_base64' => base64_encode($pdf)]
            );

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }


    public function gerarDanfeEmLote()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        try {
            $cnpj = soNumeros($this->corpoRequisicao['cnpj_emitente'] ?? '');
            $documentos = $this->corpoRequisicao['documentos'] ?? [];
            $idLote = $this->corpoRequisicao['id_lote'] ?? uniqid('lote_');
            $acao = $this->corpoRequisicao['acao'] ?? 'processar';

            // Capture as flags
            $comPdf = isset($this->corpoRequisicao['com_pdf']) ? (bool)$this->corpoRequisicao['com_pdf'] : true;
            $comXml = isset($this->corpoRequisicao['com_xml']) ? (bool)$this->corpoRequisicao['com_xml'] : true;

            $tempDir = sys_get_temp_dir() . "/{$idLote}";

            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            // --- FASE 1: PROCESSAMENTO ---
            if ($acao === 'processar') {
                $countSucesso = 0;
                $countErro = 0;

                foreach ($documentos as $doc) {
                    $chave = $doc['chave'] ?? 'sem_chave';
                    $xmlBase64 = $doc['xml'] ?? '';
                    $tipo = $doc['tipo'] ?? 'nfe';

                    if (empty($xmlBase64)){
                        continue;
                    }

                    $xmlContent = base64_decode($xmlBase64);

                    if (!$xmlContent) {
                        continue;
                    }

                    try {
                        $sufixoXml = "-nfe.xml";
                        $sufixoPdf = "-nfe.pdf";

                        if ($tipo === 'xml_cancelamento') {
                            $sufixoXml = "-xml_cancelamento.xml";
                        }

                        // 1. Salva o XML SOMENTE se com_xml estiver marcado
                        if ($comXml) {
                            file_put_contents("{$tempDir}/{$chave}{$sufixoXml}", $xmlContent);
                        }

                        // 2. Gera o PDF SOMENTE se com_pdf estiver marcado E não for xml_cancelamento
                        if ($comPdf && $tipo !== 'xml_cancelamento') {
                            $pdfContent = null;

                            if ($tipo === 'nfe') {
                                $danfe = new NFeDanfe($xmlContent);
                                $logotipo = $this->obterCaminhoLogo($this->corpoRequisicao['cnpj_emitente']);
                                $pdfContent = $danfe->render($logotipo);
                            }

                            if ($pdfContent) {
                                file_put_contents("{$tempDir}/{$chave}{$sufixoPdf}", $pdfContent);
                            }
                        }

                        $countSucesso++;
                    } catch (\Exception $ex) {
                        $countErro++;
                    }
                }

                emitirSucesso("Lote parcial processado", 200, [
                    'sucessos' => $countSucesso,
                    'erros' => $countErro
                ]);
                return;
            }

            // --- FASE 2: FINALIZAÇÃO (Gera ZIP e limpa temp) ---
            if ($acao === 'finalizar') {

                $baseOutputDir = __DIR__ . "/../storage/output";
                $userOutputDir = "{$baseOutputDir}/{$cnpj}";

                if (!is_dir($userOutputDir)) mkdir($userOutputDir, 0777, true);

                $zipFilename = "nfe" . date('Ymd_His') . ".zip";
                $zipPath = "{$userOutputDir}/{$zipFilename}";

                $zip = new ZipArchive();
                if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    emitirErro("Não foi possível criar o arquivo ZIP.", 500);
                }

                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($tempDir),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                $countTotal = 0;
                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($tempDir) + 1);
                        $zip->addFile($filePath, $relativePath);
                        $countTotal++;
                    }
                }
                $zip->close();

                // Limpa temp após gerar o ZIP
                $this->removerDiretorioRecursivo($tempDir);

                emitirSucesso("Lote finalizado", 200, [
                    'arquivo' => $zipFilename,
                    'caminho_relativo' => "/storage/output/{$cnpj}/{$zipFilename}",
                    'total_processado' => $countTotal
                ]);
            }

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }


    public function gerarDanfeCancelamento()
    {
        try {
            if (empty($this->corpoRequisicao['xml'])) {
                emitirErro("O campo 'xml' (contendo do XML em base64) é obrigatório.", 400);
            }

            if (empty($this->corpoRequisicao['xml_cancelamento'])) {
                emitirErro("O campo 'xml_cancelamento' (contendo do XML em base64) é obrigatório.", 400);
            }

            if (empty($this->corpoRequisicao['chave']) || empty($this->corpoRequisicao['cnpj_emitente'])) {
                emitirErro("Os campos 'chave' e 'cnpj_emitente' são obrigatórios (para nomear o PDF salvo).", 400);
            }

            $xmlProtocolado = base64_decode($this->corpoRequisicao['xml']);
            if ($xmlProtocolado === false) {
                emitirErro("O XML fornecido não é um base64 válido.", 400);
            }

            $xmlCancelamento = base64_decode($this->corpoRequisicao['xml_cancelamento']);
            if ($xmlCancelamento === false) {
                emitirErro("O XML fornecido não é um base64 válido.", 400);
            }

            $xml = Complements::cancelRegister($xmlProtocolado, $xmlCancelamento);

            $danfe = new NFeDanfe($xml);
            $danfe->setGerarInformacoesAutomaticas(true);

            $espacos = str_repeat(chr(160), 260);
            $creditos = $espacos . 'Giusoft Tecnologia www.giusoft.com.br';

            $danfe->creditsIntegratorFooter($creditos, false);

            $logotipo = $this->obterCaminhoLogo($this->corpoRequisicao['cnpj_emitente']);
            $pdf = $danfe->render($logotipo);

            $pdfBase64 = base64_encode($pdf);

            emitirSucesso(
                "DANFE de cancelamento gerado com sucesso",
                200,
                ['pdf_base64' => $pdfBase64]
            );

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }


    public function removerDiretorioRecursivo($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->removerDiretorioRecursivo("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }


    public function obterCaminhoLogo($cnpj)
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);

        $diretorioLogos = __DIR__ . '/../storage/logos/'.$cnpjLimpo.'/';

        $caminhoJpg = $diretorioLogos . 'logo.jpg';
        if (file_exists($caminhoJpg)) {
            return $caminhoJpg;
        }

        $caminhoPng = $diretorioLogos . 'logo.png';
        if (file_exists($caminhoPng)) {
            return $caminhoPng;
        }

        return null;
    }
}
