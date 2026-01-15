<?
$html.=$o->msgTitle('Relatório de atividades');

$gPage = $_REQUEST['gPage'];

switch ($gPage) {
	case 1:
		$where=$filtros="";
		if($data_de <> ""){
			$where[]=" AND (date)>='".gDBDateTime($data_de).":01' ";
			$filtros[]="De $data_de";
		}
		if($data_ate <> ""){
			$where[]=" AND (date)<='".gDBDateTime($data_ate).":59' ";
			$filtros[]="Até $data_ate";
		}
		if((int) $id_pessoas > 0){
			$where[]=" AND id_pessoas=$id_pessoas ";
			$rs=dbQuery("SELECT CAST(AES_DECRYPT(UNHEX(geral_pessoas.nome),'".$AESKEY."') AS CHAR(150)) nome FROM geral_pessoas WHERE id=$id_pessoas");
			$filtros[]="Usuário: ".$rs[0]['nome'];
		}
		$html.=$o->msgFilter(implode("&nbsp;&nbsp;&nbsp;",$filtros));
		$sql="SELECT gfw_log.*,
				CAST(AES_DECRYPT(UNHEX(geral_pessoas.nome),'".$AESKEY."') AS CHAR(150)) nome,
				gfw_menus.title,
				gfw_menus.content
				FROM gfw_log
				LEFT JOIN geral_pessoas ON geral_pessoas.id=gfw_log.id_pessoas
				LEFT JOIN gfw_menus ON gfw_menus.id=gfw_log.id_gfw_menus
				WHERE gfw_log.id > 0 ".implode("",$where)."
				";
		$rs=dbQuery($sql);
		if($rs){
			$mtz=array("<-Usuário","Data/Hora","<-Link","<-Complemento");
			$html.=$o->tableBegin("big",true);
			$html.=$o->tableRow($mtz,"header");
			foreach ($rs as $row) {
				$mtz = array();
				$mtz[]="<-".$row['nome'];
				$mtz[]="".gDateTime($row['date']);
				$mtz[]="<-".$row['title']." - ".$row['content'];
				$mtz[]="<-".$row['full_link'];
				$html.=$o->tableRow($mtz,"detail");
			}
			$html.=$o->tableEnd();
		}
		else{
			$html.=$o->msgAlert("Nenhum registro encontrado");
		}
	break;

	default:
		$frm = new gForm("{columns: 2}");
		$frm->add("{name: gPage; type: hidden; value: 1}");
		$frm->add("{name: data_de; fieldLabel: Data de; type: dateTime; allowBlank: false; value:'".date("d-m-y")."00:00'"."}");
		$frm->add("{name: data_ate	; fieldLabel: Data até; type: dateTime; allowBlank: false;value:'".date("d-m-y")."23:59'"." }");
		$frm->add("{name: id_pessoas; fieldLabel: Usuário; type: combo; value:$id; items: ".$sp['combo_funcionarios']."}");
		$html.=$frm->render($o);
	break;
}
?>
