<?php

namespace App\Model;

use NFePHP\DA\NFe\Danfe as NFeDanfe;

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
}
