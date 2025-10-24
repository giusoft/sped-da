<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Lib/utils.php';

use App\Controller\NFeController;

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Verifica se o path tem nfe
if ($path) {

    if (strpos($path, '/nfe/') === 0) {
        try {
            $controller = new NFeController();
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno ao iniciar API.', 'detalhe' => $e->getMessage()]);
            exit;
        }
    }

    if (strpos($path, '/danfe/') === 0) {
        try {
            $controller = new DanfeModel();
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno ao iniciar API.', 'detalhe' => $e->getMessage()]);
            exit;
        }
    }

} else {
    http_response_code(404);
    echo json_encode(['error' => 'Rota não encontrada.']);
}