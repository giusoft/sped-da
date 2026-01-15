<?
include_once $gPathDefault . "gUI.php";

$ui = new gUI("{title: CFOPs; table: cfops; permissions: SIU;}");
$ui->addDictionary("name: codigo; fieldLabel: Código; type: number; allowBlank: false;");
$ui->run($o, $html);
