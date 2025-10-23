<?php

header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../vendor/autoload.php';

use App\Controller\NFeController;

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

if (strpos($path, '/nfe/') === 0 || $path === '/status') {

    if (strpos($path, '/nfe/') === 0) {
        try {
            $controller = new NFeController();
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno ao iniciar API.', 'detalhe' => $e->getMessage()]);
            exit;
        }
    }

    switch ($path) {
        case '/status':
            if ($requestMethod === 'GET') {
                echo json_encode(['status' => 'API GNotas está online!', 'version' => '1.0.0']);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Método não permitido.']);
            }
            break;


        case '/nfe/enviar':
            if ($requestMethod === 'POST') {
                $controller->enviarNFe();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Método não permitido. Use POST.']);
            }
            break;


        case '/nfe/danfe':
            if ($requestMethod === 'POST') {
                $controller->gerarDanfe();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Método não permitido. Use POST.']);
            }
            break;


        case '/nfe/cancelar':
            if ($requestMethod === 'POST') {
                $controller->cancelarNFe();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Método não permitido. Use POST.']);
            }
            break;


        case '/nfe/consultar':
            if ($requestMethod === 'POST') {
                $controller->consultarNFe();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Método não permitido. Use POST.']);
            }
            break;


        default:
            http_response_code(404);
            echo json_encode(['error' => 'Rota não encontrada.']);
            break;
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Rota não encontrada.']);
}