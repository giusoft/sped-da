<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Lib/utils.php';

use App\Controller\Api;

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path) {

    try {
        $controller = new Api();
    } catch (\Exception $e) {
        emitirErro("Erro interno ao iniciar API", 500, $e->getMessage());
    }

} else {
    emitirErro("Rota nao encontrada", 404);
}