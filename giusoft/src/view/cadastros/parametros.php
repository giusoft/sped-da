<?php
include_once $gPathDefault . "gUI.php";

if ($usrId > 1 && $gPage == 3) {
	if($gId > 0) {
		/*
		 Impede que usuários alterem o valor de parâmetros não editáveis.
		 Isso DEVE ser feito antes da instanciação da classe gUI

		 @author André Luiz em 18-05-2017 17:00
		 */
		$sql = "SELECT editavel,valor, ativo FROM parametros WHERE id = $gId";
		$rs = dbQuery($sql);

		if (intval($rs[0]['editavel']) == 0) {
			$_GET['valor'] = $rs[0]['valor'];
			$_POST['valor'] = $rs[0]['valor'];
			$_REQUEST['valor'] = $rs[0]['valor'];

			$_GET['editavel'] = $rs[0]['editavel'];
			$_POST['editavel'] = $rs[0]['editavel'];
			$_REQUEST['editavel'] = $rs[0]['editavel'];
		}
	}
}

if ($usrId > 2) {
	$html.=$o->msgDanger("Acesso negado a esta funcionalidade<br>Somente o administrador do sistema tem direito a acessar esta funcionalidade");
} else {
	if ($usrId==1) {
    	$ui = new gUI("{title: Parâmetros; columns: 2; table: parametros; permissions: SIUD; ajax: false}");
		$ui->addDictionary("{name: editavel; fieldLabel: Editável}");
	} else {
    	$ui = new gUI("{title: Parâmetros; columns: 2; table: parametros; permissions: SU; ajax: false}");
		$ui->addDictionary("{name: descricao; type: show}");
		$ui->addDictionary("{name: ajuda; type: show}");
		$ui->addDictionary("{name: editavel; fieldLabel: Editável; type: hidden}");
	}

	$ui->addButton("{icon: question; title: Gerar questionário; href: ".$o->page."&gPage=100}");
	$ui->run($o, $html);

	$sql = "SELECT * FROM parametros";
	$rsp = dbQuery($sql);
	$gParam = '';
	foreach ($rsp as $key=>$value) {
		unset($value[0]);
		unset($value[1]);
		unset($value[2]);
		unset($value[3]);
		unset($value[4]);
		unset($value[5]);
		$gParam[$value['chave']]=$value;
	}

	$_SESSION['gParam']=$gParam;

}

if ($gPage == 100) {
	$o->PDFEnabled = true;
	$o->CSVEnabled = true;
	$o->XLSEnabled = true;

	$html .= $o->msgTitle("Parâmetros");
	$html .= $o->msgSubTitle("Questionário para parametrização do sistema");

	$sql = "SELECT * FROM parametros order by chave";
	$rs = dbQuery($sql);
	$html .= $o->tableBegin("big", true);
	$mtz = [];
	$mtz[] = "->Id";
  	$mtz[] = "<-Chave";
	$mtz[] = "<-Item";
	$mtz[] = "<>Ativar";
 	$mtz[] = "<-Valor padrão";
	$mtz[] = "<-Resposta";
	$html .= $o->tableRow($mtz, "header");
	foreach ($rs as $row) {
		$mtz = [];
		$mtz[] = "->".$row["id"];
		$mtz[] = "<-".$row["chave"];
		if ($_REQUEST['gPDF'] == 1) {
			$mtz[] = "<-".$row['descricao'];
			$mtz[] = "<>".($row["ativo"]==1?'[  ]<b>SIM</b>   [  ]não':'[  ]Sim   [  ]<b>NÃO</b>');
			$mtz[] = "<-".$row["valor"]." ";
		} else {
      		$mtz[] = "<-".$row['descricao'];
			$mtz[] = "<><div style='display: block'>".($row["ativo"]==1?'[&nbsp;&nbsp;]<b>SIM</b>&nbsp;&nbsp;[&nbsp;&nbsp;]Não':'[&nbsp;&nbsp;]Sim&nbsp;&nbsp;[&nbsp;&nbsp;]<b>NÃO</b>').'</div>';
			$mtz[] = "<-".$o->small($row["valor"]).$o->br(2);
		}

    	$mtz[] = "<-";
		$html .= $o->tableRow($mtz, "detail");
	}

	$html .= $o->tableEnd();

}
