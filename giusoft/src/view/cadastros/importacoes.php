<?
include_once $gPathDefault . "gUI.php";

define(DOWNLOAD_MODELO, 100);

$ui = new gUI("{title: Importações de dados; table: importacoes; permissions: SIUD; columns: 2}");
if ($gId) {
    $ui->addButton("{style: info; title: Baixar modelo CSV; hint: Baixar modelo de importação; icon: download; href: " . $o->page . '&gPage=' . DOWNLOAD_MODELO . "&gId=" . $gId . "}");
}
$ui->addTable("{title: Campos para o cabecalho; name: importacoes_cabecalho; foreignKey: id_importacoes; relationship: one-to-many; permissions: SIUD}");
$ui->addTable("{title: Campos para os registros; name: importacoes_registros; foreignKey: id_importacoes; relationship: one-to-many; permissions: SIUD}");
$ui->addDictionary("{name: tipo; fieldLabel: Tipo; type: combo; items: {'Texto','Texto maiúsculas','Número inteiro','Número decimal','Data','Data hora','Lógico', 'CNPJ','CPF','Carácter inicial','Unidade do Item'}}");
$ui->addDictionary("{name: validacao; fieldLabel: Validação; type: lowerText; allowBlank: true}");
$ui->addDictionary("{name: id_pessoas_proprietario; fieldLabel: Proprietário; type:combo; items: ".$sp['combo_proprietarios']."; allowBlank: true}");

//$grupos=array("progracao_entrada" => "Entrada","programacao_saida" => "Saída","Itens" => "itens");
$ui->addDictionary("{name: grupo; fieldLabel: Grupo; type:combo; items:'{programacao_entrada,programacao_saida,itens}'; allowBlank: false}");
$ui->run($o, $html);

switch($gPage) {
case DOWNLOAD_MODELO:
    downloadModeloImportacao('', $gId);
    break;
}