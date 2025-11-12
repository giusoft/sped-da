<?php

namespace App\Model;

use NFePHP\DA\NFe\Danfe as NFeDanfe;
use NFePHP\DA\NFe\Daevento;

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
            $pdf = $danfe->render();
            
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
            
            if (empty($this->corpoRequisicao['sequencia'])) {
                $erros[] = "O campo 'sequencia' é obrigatório.";
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

            $pdf = $daEvento->render();

            emitirSucesso(
                "DANFE CC-e gerado com sucesso", 
                200, 
                ['pdf_base64' => base64_encode($pdf)]
            );

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }
}
