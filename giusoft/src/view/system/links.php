<?php
include_once $gPathDefault . "gUI.php";

$ui = new gUI("{title: Links; table: gfw_links; permissions: SIUD; ajax: false}");
$ui->run($o, $html);
