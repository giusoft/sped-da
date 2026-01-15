<?php

include_once $gPathDefault . "gUI.php";

$permission = ($usrId <= 2) ? "SIU" : "SU";
$ui = new gUI("{title: Cidades; table: enderecos_cidades; permissions: {$permission}; dontDuplicateColumns: {codigo_ibge}}");
$ui->addDictionary("{name: id_enderecos_estados; fieldLabel: Estado; type: combo; items: " . $sp['combo_estados'] . "}");
$ui->run($o, $html);