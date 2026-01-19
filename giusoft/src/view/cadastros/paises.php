<?php

define("EXCLUIR_PAIS", 99);

include_once $gPathDefault . "gUI.php";

$permission = ($usrId <= 2) ? "SIU" : "SU";
$ui = new gUI("{title: Países; table: enderecos_paises; permissions: {$permission}; dontDuplicateColumns: {nome}}");
$ui->addDictionary("{name: enderecos_paises; fieldLabel: Estado; type: combo; items: " . $sp['enderecos_paises'] . "}");
$ui->addRowButton("{style: danger; icon: trash; gPage: " . EXCLUIR_PAIS . "}");
$ui->run($o, $html);

switch ($gPage) {
    case EXCLUIR_PAIS:
        $rs1 = (bool) dbQuery("select id from enderecos_estados WHERE id_enderecos_paises = {$gId} LIMIT 1")[0]["id"];
        $rs2 = (bool) dbQuery("select id from pessoas_enderecos WHERE id_enderecos_paises = {$gId} LIMIT 1")[0]["id"];

        if ($rs1 || $rs2) {
            $html .= $o->msgTitle("Países");
            $html .= $o->msgDanger("Esse país é utilizado em outros cadastros, não poderá ser excluído");
            $html .= $backButton;
            break;
        }

        dbQuery("DELETE FROM enderecos_paises WHERE id = {$gId}");
        $html .= $o->msgTitle("Países");
        $html .= $o->msgSuccess("País excluído com sucesso.");
        $html .= $backButton;
        break;
}
