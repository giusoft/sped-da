<?php

include_once $gPathDefault . "gUI.php";

$ui = new gUI("{title: Tipos de ocorrências; table: tipos_ocorrencias; permissions: SIUD}");
$ui->run($o, $html);