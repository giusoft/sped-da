<?
include_once $gPathDefault . "gUI.php";
$ui = new gUI("{title: Informações; table: nfe_informacoes; permissions: SIUD}");
$ui->addDictionary("{name: nome; type: text}");
$ui->run($o, $html);