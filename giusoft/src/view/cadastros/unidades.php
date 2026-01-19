<?
include_once $gPathDefault . "gUI.php";

$ui = new gUI("{title: Unidades; table: unidades; permissions: SIUD; dontDuplicateColumns: {sigla}}");
$ui->run($o, $html);