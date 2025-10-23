<?php

namespace App\Controller;

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\DA\NFe\Danfe;
use NFePHP\NFe\Complements;

class DanfeModel
{

    private $tools;
    private $config;

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
            $xmlPath = __DIR__ . "/notas/{$cnpjLimpo}/autorizadas/{$chave}-nfe.xml";

            if (!file_exists($xmlPath)) {
                http_response_code(404);
                echo json_encode(['erro' => 'XML não encontrado']);
                return;
            }

            $xml = file_get_contents($xmlPath);

            $danfe = new Danfe($xml);
            $pdf = $danfe->render();

            $pdfPath = __DIR__ . "/storage/notas/{$cnpjLimpo}/autorizadas/{$chave}-danfe.pdf";
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

}