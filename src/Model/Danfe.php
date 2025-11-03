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
            $chave = $this->corpoRequisicao['chave'];
            $cnpjLimpo = soNumeros($this->corpoRequisicao['cnpj_emitente']);
            $xmlPath = __DIR__ . "/../storage/notas/{$cnpjLimpo}/autorizadas/{$chave}-nfe.xml";

            if (!file_exists($xmlPath)) {
                emitirErro("XML não encontrado", 404);
            }

            $xml = file_get_contents($xmlPath);

            $danfe = new NFeDanfe($xml);
            $pdf = $danfe->render();

            $pdfPath = __DIR__ . "/../storage/notas/{$cnpjLimpo}/autorizadas/{$chave}-danfe.pdf";
            file_put_contents($pdfPath, $pdf);

            emitirSucesso(base64_encode($pdf), 200);

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }
}
