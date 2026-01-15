<?php
include_once "gConf.php";
include_once "gPage.php";
include_once 'xmpphp/XMPPHP/XMPP.php';
session_start();
gVar("global.helpdesk","false");

$l=$_REQUEST['l'];
$t=$_REQUEST['t'];
$m=$_REQUEST['m'];
$usr_id=$_SESSION["usr_id"];
$usr_nome=$_SESSION["usr_nome"];
$usr_apelido=$_SESSION["usr_apelido"];
$CFC=$_SESSION['CFC'];

// Obtém cliente na base GiuSoft
gVar("database.url","www.giusoft.com.br");
gVar("database.name","erp_gs");

$sql="select id,nome from geral_pessoas where apelido='$CFC'";
$rs=gQuery($sql);
$id=$rs->fields['id'];

$flds[]=array(gI_SELECT,array("Tipo","tipo"),array("Dúvida","Solicitação","Sugestão","Reclamação"));
$flds[]=array(gI_HIDDEN,"link",$l);
$flds[]=array(gI_HIDDEN,"title",$t);
$flds[]=array(gI_HIDDEN,"menu",$m);
$flds[]=array(gI_HIDDEN,"id_geral_pessoas_solicitou","0");
$flds[]=array(gI_HIDDEN,"id_geral_pessoas_criou","0");
$flds[]=array(gI_HIDDEN,"id_pes_setores","0");
$flds[]=array(gI_HIDDEN,"data_criacao","");
$flds[]=array(gI_HIDDEN,"data_previsao","");
$flds[]=array(gI_HIDDEN,"solicitante",$usrName);
$flds[]=array(gI_MEMO,"descricao","",100);
$out=new gPage();
$permissoes='SI';
if ($gAction=="new_tnl")
{
	$dias=1;
	$_POST['id_geral_pessoas_solicitou']=$id;
	$_POST['id_geral_pessoas_criou']=$id;
	$_POST['id_geral_pessoas_direcionou']=$id;
        $_POST['direcionada']="1";
	$_POST['id_pes_setores']="3";
	$_POST['solicitante']=$usr_nome;
	$_POST['data_criacao']=date("Y-m-d H:i:s");
	$_POST['data_previsao']=gDBDateTime(gDateAdd(gDate(date("Y-m-d")),$dias)." ".date("H:i:s"));
	$_POST['data_previsao_conclusao']=gDBDateTime(gDateAdd(gDate(date("Y-m-d")),$dias)." ".date("H:i:s"));
	$_POST['descricao']="CFC $CFC\nUsuário: $usr_nome ($usr_id)\nIP: ".$_SERVER['REMOTE_ADDR']." às ".gDateTime(date("Y-m-d H:i:s"))."\nTítulo: $title\nLink: $link\nMenu: $menu\n\n".$_POST['descricao'];
}
$sql="SELECT id,tipo,id_pes_setores,data_criacao,data_previsao,solicitante,id_geral_pessoas_criou,id_geral_pessoas_solicitou,descricao from ope_os where id_geral_pessoas_solicitou=$id and concluida=0 order by data_criacao,id";
$out->gShowPage($sql,"Suporte GiuSoft",$permissoes,$flds,'','',false);

if ($gAction=="new_tnl")
{
        $sql="select * from ope_os where id_geral_pessoas_solicitou=$id order by id desc limit 1";
        $rsOS=gQuery($sql);
        
        $sql="select * from geral_pessoas where apelido='sindauto' and funcionario=1";
        $rsP=gQuery($sql);
        
        $id_ope_os=$rsOS->fields['id'];
        $id_geral_pessoas_atendente=$rsP->fields['id'];
        $situacao="";
        $agora=date("Y-m-d h:i:s");
        $sql ="insert into ope_os_horarios ";
	$sql.="(id_ope_os,id_geral_pessoas_atendente,inicio,termino,situacao) values";
	$sql.="($id_ope_os,$id_geral_pessoas_atendente,'$agora','$agora','$situacao') ";
        gQuery($sql);
        
	$msg="Nova OS do ERPCFC!\n\n".gFieldById("geral_pessoas",$id,"nome")." ($CFC)\n";
	$msg.=strip_tags($_POST['descricao']);

//	$conn = new XMPPHP_XMPP(gVar("jabber.server"), 5222, gVar("jabber.user"), gVar("jabber.password"), 'xmpphp', gVar("jabber.server"), $printlog=False, $loglevel=LOGGING_INFO);
//	$conn->connect();
//	$conn->processUntil('session_start');
//	$conn->message("Suporte@broadcast.".gVar("jabber.server"), $msg);
//	$conn->disconnect();

}

?>
