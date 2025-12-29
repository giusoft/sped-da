<?php

namespace App\Model;

class Certificado
{
    private $corpoRequisicao;

    public function __construct($dados)
    {
        $this->corpoRequisicao = $dados->corpoRequisicao;
    }

    public function buscarDadosCertificado()
    {
        $cnpjLimpo = soNumeros($this->corpoRequisicao['cnpj_emitente']);

        $diretorioCertificados = __DIR__ . "/../storage/certificados/{$cnpjLimpo}/";
        $caminhoCompletoCertificado = $diretorioCertificados . "certificado.pfx";

        if (file_exists($caminhoCompletoCertificado) || $this->corpoRequisicao['certificado']) {
            // Certificado
            if ($this->corpoRequisicao['certificado']) {
                $conteudoCertificado = base64_decode($this->corpoRequisicao['certificado'], true);

                if ($conteudoCertificado === false) {
                    $conteudoCertificado = base64_decode($this->corpoRequisicao['certificado']);
                }
            } else {
                $conteudoCertificado = file_get_contents($caminhoCompletoCertificado);
            }

            if (empty($conteudoCertificado)) {
                emitirErro("Conteúdo do certificado está vazio", 400);
            }

            if (!isset($this->corpoRequisicao['senhaCertificado'])) {
                emitirErro("Informe a senha do certificado", 400);
            }

            $senhaCertificado = desencriptar($this->corpoRequisicao['senhaCertificado']);

            $tempPem = tempnam(sys_get_temp_dir(), 'cert_');
            $tempPfx = tempnam(sys_get_temp_dir(), 'pfx_');

            try {
                // Salvar certificado original temporariamente
                file_put_contents($tempPfx, $conteudoCertificado);

                // Converter usando openssl CLI com flag -legacy
                $comandoExtracao = sprintf(
                    'openssl pkcs12 -in %s -out %s -nodes -password pass:%s -legacy 2>&1',
                    escapeshellarg($tempPfx),
                    escapeshellarg($tempPem),
                    escapeshellarg($senhaCertificado)
                );

                exec($comandoExtracao, $output, $returnCode);

                if ($returnCode !== 0) {
                    error_log("Erro ao converter certificado: " . implode("\n", $output));
                    throw new Exception("Não foi possível processar o certificado com algoritmos legados");
                }

                // Ler o PEM convertido
                $conteudoPem = file_get_contents($tempPem);

                // Extrair certificado e chave privada do PEM
                preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $conteudoPem, $matchesCert);
                preg_match('/-----BEGIN PRIVATE KEY-----.*?-----END PRIVATE KEY-----/s', $conteudoPem, $matchesKey);

                if (empty($matchesCert) || empty($matchesKey)) {
                    throw new Exception("Não foi possível extrair certificado ou chave do arquivo");
                }

                $dadosExtraidosCertificado = [
                    'cert' => $matchesCert[0],
                    'pkey' => $matchesKey[0]
                ];

                $certificadoX509 = openssl_x509_read($dadosExtraidosCertificado['cert']);
                if (!$certificadoX509) {
                    throw new Exception("Erro ao ler certificado X509");
                }

                $informacoesCertificado = openssl_x509_parse($certificadoX509);
                if (!$informacoesCertificado) {
                    throw new Exception("Erro ao extrair informações do certificado");
                }

                $resposta = [];
                $resposta['Arquivo'] = "certificado.pfx";
                $resposta['Empresa'] = $informacoesCertificado['subject']['CN'] ?? 'N/A';

                if (isset($informacoesCertificado['subject']['emailAddress'])) {
                    $resposta['E-mail'] = $informacoesCertificado['subject']['emailAddress'];
                }

                $resposta['País'] = $informacoesCertificado['subject']['C'] ?? 'N/A';
                $resposta['Certificadora'] = $informacoesCertificado['subject']['O'] ?? 'N/A';
                $resposta['Tipo de certificado'] = $informacoesCertificado['subject']['OU'][2] ?? ($informacoesCertificado['subject']['OU'] ?? 'N/A');

                $issuerOU = is_array($informacoesCertificado['issuer']['OU'] ?? null)
                    ? implode(', ', $informacoesCertificado['issuer']['OU'])
                    : ($informacoesCertificado['issuer']['OU'] ?? 'N/A');

                $resposta['Fornecedora'] = $issuerOU . " / " . ($informacoesCertificado['issuer']['CN'] ?? 'N/A');

                // Processar data de validade
                $validTo = $informacoesCertificado['validTo'];
                $anoValidade = substr($validTo, 0, 2);
                $mesValidade = substr($validTo, 2, 2);
                $diaValidade = substr($validTo, 4, 2);

                $dataValidadeFormatada = date("d-m-Y", gmmktime(0, 0, 0, $mesValidade, $diaValidade, $anoValidade));
                $resposta['Validade'] = $dataValidadeFormatada;

                emitirSucesso("Certificado válido", 200, $resposta);

            } catch (Exception $e) {
                error_log("Erro ao processar certificado: " . $e->getMessage());
                emitirErro("Erro ao processar certificado: " . $e->getMessage(), 400);
            } finally {
                // Limpar arquivos temporários
                if (file_exists($tempPem)) unlink($tempPem);
                if (file_exists($tempPfx)) unlink($tempPfx);
            }
        }

        emitirErro("Certificado não encontrado");
    }


    public function importarPfx()
    {
        // Decodificar o certificado base64
        $certificadoConteudo = base64_decode($this->corpoRequisicao['certificado']);

        // Definir diretórios
        $cnpjAtual = soNumeros($this->corpoRequisicao['cnpj_emitente']);
        $diretorioCerts = __DIR__ . "/../storage/certificados/{$cnpjAtual}/";
        $diretorioAntigos = $diretorioCerts . "certificadosAntigos/";

        // Criar diretórios se não existirem
        if (!is_dir($diretorioCerts)) {
            mkdir($diretorioCerts, 0777, true);
        }

        if (!is_dir($diretorioAntigos)) {
            mkdir($diretorioAntigos, 0777, true);
        }

        $uniqId = uniqid();
        $certificadosMovidos = [];
        $certificadosExistentes = glob($diretorioCerts . "/certificado*");

        // Se já existe um certificado, mover para antigos
        foreach ($certificadosExistentes as $certificado) {
            if (!is_file($certificado)) {
                continue;
            }

            $nomeCertificado = $diretorioAntigos
                . "/" . pathinfo($certificado, PATHINFO_FILENAME)
                . "_" . $uniqId
                . "." . pathinfo($certificado, PATHINFO_EXTENSION);

            $certificadosMovidos[] = [
                'antigo' => $nomeCertificado,
                'original' => $certificado
            ];

            rename($certificado, $nomeCertificado);
        }

        $destino = $diretorioCerts . "/certificado.pfx";

        if (!file_put_contents($destino, $certificadoConteudo)) {
            // Reverter movimentações se falhar
            foreach ($certificadosMovidos as $certificadoMovido) {
                if (is_file($certificadoMovido['antigo'])) {
                    rename($certificadoMovido['antigo'], $certificadoMovido['original']);
                }
            }
            emitirErro("Erro ao salvar o certificado");
        }

        chmod($destino, 0664);

        emitirSucesso("Certificado importado com sucesso");
    }
}
