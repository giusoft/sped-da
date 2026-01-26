<?php

include $gPath."gfw/inc/gChart.php";
include $gPath."res/_classes/classes.php";

if ($gParam['ACESSO']['ativo']==0 && $usrId>0)
{
	$html.=$o->msgTitle("Acesso desabilitado");
	$html.=$o->msgError("O administrador do sistema desabilitou o acesso ao mesmo pelos usuários, provavelmente para realizar alguma manutenção.<br>Aguarde alguns minutos e tente novamente.");
} else {
	$html.=$o->msgTitle("Bem-vindo!");
}

$hoje=date("Y-m-d");

// Mensagens
$sql="SELECT m.*,p.nome de
		FROM mensagens m
		LEFT JOIN pessoas p ON m.id_pessoas_de=p.id
		WHERE id_pessoas_para=".$usrId." AND m.lida=0";
$rs=dbQuery($sql);
if (count($rs)>0)
{
	$html.='<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Você possui mensagens não lidas</div>';
}

// ----------------- DASHBOARD -----------------

include_once $gPathDefault . "gDashboard.php";

$dash = new gDashboard($o);
$col = "<div style='padding: 2px; background-color: red'>Item 1</div>";
$graf = array(10,25,40,5,11);
$hint = "";
if ($_SESSION['usrId']<=2)
{
    if ($_SESSION['usrId']==1)
    {
        $hint.=$o->label("Root");
    } else {
        $hint.=$o->label("Administrador do sistema");
    }

} else {
    if ($_SESSION['usrClient']) {
        $hint .= $o->label("Cliente");
    } else {
        $hint .= $o->label("Colaborador");
    }
}

$filtroProprietario = "";
if ($_SESSION['usrClient']) // Se for cliente, filtra pelo seu código pra só mostrar suas OS
{
   	$filtroProprietario = "AND id_pessoas_proprietario=".$_SESSION['usrId'];
}

// Token
function gerarTokenAlexa($id)
{
    $numero = (int)$id;
    $mod = 1000000;

    // Chaves que mudam diariamente, baseadas no dia do ano e no ano.
    $chaveSoma = ((int)date('z') + 1) * 101;
    $chaveXOR = ((int)date('Y')) * 31;

    // --- Processo de Embaralhamento (4 Passos) ---
    // Cada passo é uma camada de "mistura" que é totalmente reversível.
    $numero = ($numero + $chaveSoma) % $mod;

    $numero = $numero ^ $chaveXOR;

    $numero = ($numero + $chaveSoma) % $mod;

    $numero = $numero ^ $chaveXOR;

    return str_pad($numero, 6, '0', STR_PAD_LEFT);
}

// $token = gerarTokenAlexa($usrId);


// ------------- Estrutura do dashboard -------------
$col1 = $dash->avatar("{id: $usrId; name: ".gShortName($usrName)." ".$o->label($gId,"success")."; token_alexa: $token; email: $usrEmail; phone: $usrPhone; hint: $hint; href: index.php?g=profile;}", $graf);

$dash->setColumnsWidth(1,1,1,1);
$dash->addRow($col1,$col2, $col3, $col4);

if (!$_SESSION['usrClient']) {
    $dash->setColumnsWidth(1,1,2);
    $col1 = $dash->card("{title: Alertas; titleStyle: danger}", $ocorrencias.$vencimentos);
    $col2 = '';
    $col3 = '';

	$dash->addRow($col1, $col2, $col3);

}

$html.=$dash->render();

