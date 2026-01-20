<?php

define('NFE_ALTERNAR_MODO', 0);

if ($gPage === NFE_ALTERNAR_MODO) {
    $html = $o->msgTitle('Ativação de contigência');
    $contigencia = gDBCheck($_REQUEST['contigencia']);
    $ultimoNumeroNfe = dbFastQuery("SELECT numero FROM nfe ORDER BY id DESC LIMIT 1")[0]['numero'];
    $sql = "SELECT
                nfe_operacao.serie,
                pessoas.nome,
                nfe_operacao.data,
                modo_operacao
            FROM nfe_operacao
            JOIN pessoas ON pessoas.id = nfe_operacao.id_geral_pessoas_ativou
            ORDER BY nfe_operacao.id DESC LIMIT 1";
    $ultimaOperacao = dbFastQuery($sql)[0];
    if ($_REQUEST['id_geral_pessoas_ativou']) {
        $mtz = [];
        $mtz['id_geral_pessoas_ativou'] = $_REQUEST['id_geral_pessoas_ativou'];
        $mtz['data']  = date('Y-m-d H:i:s');
        $mtz['serie'] = dbQuery("SELECT serie FROM nfe_numeros WHERE id_armazens = {$armazemAtualId} AND serie > 0 LIMIT 1")[0]['serie'] ?: 1;
        $mtz['modo_operacao'] = ($contigencia) ? 7 : 1;//7=ativa contigencia; 1=desativa contigencia
        dbInsert('nfe_operacao', $mtz);
        $o->addJavaScript('bootbox.alert("Modo de operação alternado com sucesso!");');
    }
    $form = new gForm();
    $form->add('{name: contigencia; id: contigencia; fieldLabel: Modo de contigência; type: checkbox; value: ' . $contigencia . ';}');
    $form->add('{name: id_geral_pessoas_ativou; id: id_geral_pessoas_ativou;  type: hidden; value: ' . $_SESSION['usrId'] . ';}');
    $form->add('{name: serie; id: serie; fieldLabel: Última Série; type: show; value: ' . $ultimaOperacao['serie'] . ';}');
    $form->add('{name: data; id: data; fieldLabel: Data de última modifição; type: show; value: ' . gDateTime($ultimaOperacao['data']) . ';}');
    $form->add('{name: nomePessoa; id: nomePessoa; fieldLabel: Usuário; type: show; value: ' . $ultimaOperacao['nome'] . ';}');
    $form->add('{name: ultimoNumeroNfe; id: ultimoNumeroNfe; fieldLabel: Último número NFE; type: show; value: ' . $ultimoNumeroNfe . ';}');
    $form->add('{name: gPage; id: gPage; type: hidden; value: ' . NFE_ALTERNAR_MODO . ';}');
    $html .= $form->render($o);
}