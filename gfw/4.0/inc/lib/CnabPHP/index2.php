<?
include 'vendor/autoload.php';
date_default_timezone_set("America/Bahia");
$codigo_banco = Cnab\Banco::BRADESCO;
$file="/var/www/html/CnabPHP/CB180101.RET";
$cnabFactory = new Cnab\Retorno\Cnab400\Arquivo($codigo_banco,$file);
$detalhes = $cnabFactory->listDetalhes();
echo "<pre>";
    var_dump($detalhes);
    exit;
foreach($detalhes as $detalhe) {
    echo "<pre>";
    var_dump($detalhe);
    exit;
}
?>