<?
include_once $gPathDefault . "gUI.php";

$ui = new gUI("{title: Grupos; table: grupos; permissions: SIUD}");
$ui->run($o, $html);
