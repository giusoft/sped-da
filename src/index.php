<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Lib/utils.php';

use App\Controller\Nfe;
use App\Controller\Danfe;
use App\Controller\Sefaz;

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Verifica se o path tem nfe
if ($path) {

    if (strpos($path, '/nfe/') === 0) {
        try {
            $controller = new Nfe();
        } catch (\Exception $e) {
            emitirErro("Erro interno ao iniciar API", 500, $e->getMessage());
        }
    }

    if (strpos($path, '/danfe/') === 0) {
        try {
            $controller = new Danfe();
        } catch (\Exception $e) {
            emitirErro("Erro interno ao iniciar API", 500, $e->getMessage());
        }
    }

    if (strpos($path, '/sefaz/') === 0) {
        try {
            $controller = new Sefaz();
        } catch (\Exception $e) {
            emitirErro("Erro interno ao iniciar API", 500, $e->getMessage());
        }
    }

} else {
    emitirErro("Rota nao encontrada", 404);
}