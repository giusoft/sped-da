<?php

$url = "http://10.0.1.176:5000/";

$route = $_GET['route'];
if (strpos($route, 'route/v1') !== false) {
    $url.=$route;
} else {
    $url.="route/v1/".$route;
}
$url.="?";
foreach ($_GET as $key => $value) {
    if ($key != 'route') {
        $url.=$key."=".$value."&";
    }
}

$url = substr($url, 0, -1);
file_put_contents("/tmp/osrm.log", $url."\n", FILE_APPEND);

// ob_flush();
// ob_start();
// var_dump($_GET);
// file_put_contents("/tmp/osrm.log", ob_get_flush(), FILE_APPEND);

// Inicializa a sessão cURL
$ch = curl_init();

// Configura as opções do cURL para obter headers e corpo na resposta
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_HEADER, true);

// Executa a requisição
$body = curl_exec($ch);

if(curl_errno($ch)) {
    // Se ocorrer erro, exibe a mensagem e encerra
    echo "Erro: " . curl_error($ch);
    curl_close($ch);
    exit;
}
// file_put_contents("/tmp/osrm.log", $url."\n".$body."\n", FILE_APPEND);

// Limpa qualquer header já definido (importante para evitar conflitos)
header_remove();

header("Access-Control-Allow-Origin","*");
header("Access-Control-Allow-Methods","GET");
header("Access-Control-Allow-Headers","X-Requested-With, Content-Type");
header("Content-Type","application/json; charset=UTF-8");
header("Content-Disposition","inline; filename=\"response.json\"");
header("Content-Length",strlen($body));
header("Connection","keep-alive");
header("Keep-Alive","timeout=5, max=512");

// Por fim, exibe o corpo da resposta
echo $body;

