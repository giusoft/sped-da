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

        $diretorioCertificados = __DIR__ . "/../Certificados/{$cnpjLimpo}/";
        $caminhoCompletoCertificado = $diretorioCertificados . "certificado.pfx";

        if (file_exists($caminhoCompletoCertificado) || $this->corpoRequisicao['certificado']) {
            // Certificado
            if ($this->corpoRequisicao['certificado']) {
                $conteudoCertificado = base64_decode($this->corpoRequisicao['certificado']);
            } else {
                $conteudoCertificado = file_get_contents($caminhoCompletoCertificado);
            }

            $senhaCertificado = desencriptar($this->corpoRequisicao['senhaCertificado']);

            if (!openssl_pkcs12_read($conteudoCertificado, $dadosExtraidosCertificado, $senhaCertificado)) {
                emitirErro("A senha digitada está incorreta", 400);
            }

            $certificadoX509 = openssl_x509_read($dadosExtraidosCertificado['cert']);
            $informacoesCertificado = openssl_x509_parse($certificadoX509);

            $resposta['Arquivo'] = "certificado.pfx";
            $resposta['Empresa'] = $informacoesCertificado['subject']['CN'];

            if (isset($informacoesCertificado['subject']['emailAddress'])) {
                $resposta['E-mail'] = $informacoesCertificado['subject']['emailAddress'];
            }

            $resposta['País'] = $informacoesCertificado['subject']['C'];
            $resposta['Certificadora'] = $informacoesCertificado['subject']['O'];
            $resposta['Tipo de certificado'] = $informacoesCertificado['subject']['OU'][2];
            $resposta['Fornecedora'] = $informacoesCertificado['issuer']['OU'] . " / " . $informacoesCertificado['issuer']['CN'];

            $anoValidade = substr($informacoesCertificado['validTo'], 0, 2);
            $mesValidade = substr($informacoesCertificado['validTo'], 2, 2);
            $diaValidade = substr($informacoesCertificado['validTo'], 4, 2);

            // Obtém o timestamp da data de validade do certificado
            $dataValidadeFormatada = date("d-m-Y", gmmktime(0, 0, 0, $mesValidade, $diaValidade, $anoValidade));
            $resposta['Validade'] = $dataValidadeFormatada;

            emitirSucesso("Certificado válido", 200, $resposta);
        }

        emitirErro("Certificado não encontrado");
    }


    public function importarPfx()
    {
        // Decodificar o certificado base64
        $certificadoConteudo = base64_decode($this->corpoRequisicao['certificado']);

        // Definir diretórios
        $cnpjAtual = soNumeros($this->corpoRequisicao['cnpj_emitente']);
        $diretorioCerts = __DIR__ . "/../Certificados/{$cnpjAtual}/";
        $diretorioAntigos = $diretorioCerts . "CertificadosAntigos/";

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
