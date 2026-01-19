<?
include_once $gPathDefault . "gUI.php";

$ui = new gUI("{title: Página inicial; table: gfw_home; permissions: SIUD; ajax: false}");
$ui->setGridQuery("SELECT * FROM gfw_home order by keyword,locale");
$ui->hide("language");
$ui->addDictionary("{name: locale; type: hidden; value: pt_BR}");
$ui->addDictionary("{name: text; type: exclude}");
$ui->addDictionary("{name: keyword; fieldLabel: Ordem a exibir}");
$ui->addDictionary("{name: attachment1; fieldLabel: Imagem; type: file}");
$ui->addDictionary("{name: place1; fieldLabel: Aparecer no lugar 1}");
$ui->addDictionary("{name: place2; fieldLabel: Aparecer no lugar 2}");
$ui->addDictionary("{name: subtitle; fieldLabel: Sub-título}");
$ui->addDictionary("{name: short_text; fieldLabel: Texto curto}");

$ui->run($o, $html);

?>
