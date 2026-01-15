<?
include_once $gPathDefault . "gUI.php";

$ui = new gUI("{title: Links; table: geral_relatorios_views; permissions: SIUD; ajax: false; columns: 1}");
$ui->addDictionary("{name: query; type: code; rows: 10}");
$ui->run($o, $html);

?>
