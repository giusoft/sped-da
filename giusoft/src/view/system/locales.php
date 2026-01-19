<?
$html=$o->msgTitle("Locales");
$exit=false;

// Ações
switch ($gPage)
{
	//-----------------------------------------------------------------------------------------------
	case 0: // Listagem de páginas e opção pra criar uma nova
		$html.=$o->button("{style: default; icon: plus; title: add; url: " . $o->page . "&gPage=1}");
		$html.="<br>&nbsp;";

		$sql="SELECT * FROM gfw_locales ORDER BY id DESC";
		$rs = dbFastQuery($sql);

		if (count($rs) > 0) {
			// Obtém os idiomas ativos
			$tab = '';
			$tab.=$o->tableBegin('big', true, false, true); // tamanho, borda, zebra, ordenável
			$mtz = array();
			$mtz[] = 'Options';
			$mtz[] = 'Locale';
			$mtz[] = '<-Name';
			$tab.=$o->tableRow($mtz, 'header');
			foreach ($rs as $field) {
				$mtz = array();
				$btns = '';
				$btns.= $o->button("{style: danger; size: tiny; icon: trash; hint: Delete; url: " . $o->page . "&gPage=3&gId=" . $field['id'] . "}");
				$mtz[] = $btns;
				$mtz[] = $field['locale'];
				$mtz[] = '<-<a href="' . $o->page .'&gPage=1&gId=' . $field['id'] . '">'.$field['name'].'</a>';
				$tab.=$o->tableRow($mtz);
			}
			$tab.=$o->tableEnd();
		} else {
			$tab = $o->msgAlert(gT('error_no_fields'));
		}
		$html.=$tab;
		break;

	//-----------------------------------------------------------------------------------------------
	case 1: // Novo registro
		$rs='';
		$locale=$name='';
		$pageTitle='New';
		if ($gId>0)
		{
			$pageTitle='Edit';
			$sql="SELECT p.*
					FROM gfw_locales p
					WHERE p.id=$gId";
			$rs=dbFastQuery($sql);
			$locale=$rs[0]['locale'];
			$name=$rs[0]['name'];
		}
		$frm=new gForm("{title: ".$pageTitle."; style: 2column; }");
		$frm->setButtonBackCaption("back");

		$frm->add("{name: gPage; type: hidden; value: 2}");
		$frm->add("{name: gId; type: hidden; value: " . $gId . "}");
		$frm->add("{name: locale; type: text; value: " . $locale . "}");
		$frm->add("{name: name; type: text; value: " . $name . "}");

		$html.=$frm->render($o);
		break;
	//-----------------------------------------------------------------------------------------------
	case 2: // Tunnel (salvar/editar)

		$flds='';
		$flds['locale']=gCleanField($_REQUEST['locale']);
		$flds['name']=gCleanField($_REQUEST['name']);
		if ($gId == 0)
		{
			$flds['idd']=$usrId;
			// Insere registro para o idioma informado
			dbInsert('gfw_locales',$flds);
		} else
		{
			dbUpdate('gfw_locales',$flds,$gId);
		}
		redirect($o->page);
		break;

	//-----------------------------------------------------------------------------------------------
	case 3: // Apagar (desativar)
		$sql="DELETE gfw_locales  WHERE id=$gId";
		dbFastQuery($sql);
		redirect($o->page);
		break;
}



?>