<?
$html=$o->msgTitle("Pages");

$exit=false;
// Obtendo idiomas disponíveis
$locales = '';
$langs = explode(",", str_replace(" ", "", gVar("global.languages")));
foreach ($langs as $l) {
	$locales[$l] = $l;
}
$sql = "SELECT * FROM gfw_locales";
$rs = dbFastQuery($sql);
foreach ($rs as $row) {
	if (!empty($locales[$row['locale']])) {
		$locales[$row['locale']] = $row['name'];
	}
}

// Ações
switch ($gPage)
{
	//-----------------------------------------------------------------------------------------------
	case 0: // Listagem de páginas e opção pra criar uma nova
		$html.=$o->button("{style: default; icon: plus; title: add; url: " . $o->page . "&gPage=1}");
		$html.="<br>&nbsp;";

		$sql="SELECT * FROM gfw_pages WHERE locale='".$gLang."' ORDER BY id DESC";
		$rs = dbFastQuery($sql);

		if (count($rs) > 0) {
			// Obtém os idiomas ativos
			$tab = '';
			$tab.=$o->tableBegin('big', true, false, true); // tamanho, borda, zebra, ordenável
			$mtz = array();
			$mtz[] = 'Options';
			$mtz[] = 'Creation';
			$mtz[] = 'Publication';
			$mtz[] = '<-Keyword';
			$mtz[] = '<-Title';
			$tab.=$o->tableRow($mtz, 'header');
			foreach ($rs as $field) {
				$mtz = array();
				$btns = '';
				$btns.= $o->button("{style: info; size: tiny; icon: pencil; hint: Editar; url: " . $o->page . '&gPage=1&gId=' . $field['id']);
				$btns.= $o->button("{style: default; size: tiny; icon: search; hint: See; url: " . $o->page . "&gPage=3&keyword=" . $field['keyword'] . "}");
				$mtz[] = $btns;
				$mtz[] = gDateTime($field['date_creation']);
				$mtz[] = gDateTime($field['date_publication']);
				$mtz[] = '<-'.$field['keyword'];
				$mtz[] = '<-'.$field['title'];
				$tab.=$o->tableRow($mtz);
			}
			$tab.=$o->tableEnd();
		} else {
			$tab = $o->msgAlert(gT('error_no_pages'));
		}
		$html.=$tab;
		break;

	//-----------------------------------------------------------------------------------------------
	case 1: // Nova página
		$rs='';
		$title='';
		$date_publication=date("Y-m-d H:i:s");
		$content='';
		$pageTitle='New';
		$keyword='';
		if ($gId>0)
		{
			$pageTitle='Edit';
			$sql="SELECT p.*, l.name localeName
					FROM gfw_pages p
					LEFT JOIN gfw_locales l on p.locale=l.locale
					WHERE p.id=$gId";
			$rs=dbFastQuery($sql);
			$title=$rs[0]['title'];
			$date_publication=$rs[0]['date_publication'];
			$locale=$rs[0]['locale'];
			$localeName=$rs[0]['localeName'];
			$content=base64_decode($rs[0]['content']);
			$keyword=$rs[0]['keyword'];
		}
		$frm=new gForm("{title: ".$pageTitle."; columns: 1}");
		$frm->setButtonBackCaption("back");

		$frm->add("{name: gPage; type: hidden; value: 2}");
		$frm->add("{name: gId; type: hidden; value: " . $gId . "}");
		$frm->add("{name: locale; type: hidden; value: pt_BR}");
		$frm->add("{name: keyword; type: lowerText; hint: Não utilize espaços nem acentos;value: $keyword}");
		$frm->add("{name: title; type: text; maxLength: 100; value: " . $title . "}");

		$frm->add("{name: content; type: memo; height: 320; indent: false; align: false; h1: false; value: '" . $content . "'}");
		$frm->add("{name: date_publication; fieldLabel: publication_date; type: dateTime; value: " . gDateTime($date_publication) . "}");
//		$frm->add("{type: show; fieldLabel: Keyword; value: " . $keyword . ";}");

		$form=$frm->render();
        $o->out($form[0],gLOC_PRE);
		$o->out($form[2],gLOC_POS);
		foreach ($form[3] as $js)
		$o->addJavascript($js);
		$html.=$form[1];
		break;
	//-----------------------------------------------------------------------------------------------
	case 2: // Tunnel (salvar/editar)
		$locale=gCleanField($_REQUEST['locale']);
		if (is_numeric($locale))
		{
			$sql = "SELECT * FROM gfw_locales where id=".$locale;
			$rs = dbFastQuery($sql);
			$locale=$rs[0]['locale'];
		}

		$flds='';
		$flds['title']=gCleanField($_REQUEST['title']);
		$flds['date_publication']=gDBDateTime($_REQUEST['date_publication']);
		//$flds['content']=gCleanHTMLContent(nl2br($_REQUEST['content']),'<br><p><h1><h2><h3><b><i><u><ul><ol><li><a>');
		$flds['content']=  base64_encode(addslashes($_REQUEST['content']));
		$flds['locale']=$locale;
		$flds['active']=1;
		$flds['keyword']=gCleanField($_REQUEST['keyword']);
		if ($gId == 0)
		{
			if ($_REQUEST['keyword']=='')
			{
				$flds['keyword']=$flds['title'];
				$flds['keyword']=str_replace(' ','',$flds['keyword']);
				$flds['keyword']=str_replace('&','',$flds['keyword']);
				$flds['keyword']=str_replace('.','',$flds['keyword']);
				$flds['keyword']=str_replace('/','',$flds['keyword']);
				$flds['keyword']=urlencode(strtolower(tiracentos($flds['keyword'])));				
			}
			// Checa se existe a keyword antes (highlander)
			$sql="SELECT * FROM gfw_pages where keyword='$keyword'";
			$tem=dbFastQuery($sql);
			if (count($tem)>0)
			{
				$html.=$o->msgError("error_keyword_exists");
			} else
			{
				$flds['idd']=$usrId;
				$flds['date_creation']=date("Y-m-d H:i:s");
				// Insere registro para o idioma informado
				dbInsert('gfw_pages',$flds);
				foreach ($locales as $key => $value) {
					if ($locale<>$key)
					{
						// Insere em branco para os outros idiomas
						$flds['locale']=$key;
						$flds['content']='';
						dbInsert('gfw_pages',$flds);
					}
				}
				redirect($o->page);
			}
		} else
		{
			dbUpdate('gfw_pages',$flds,$gId);
			redirect($o->page);
		}
		break;
	//-----------------------------------------------------------------------------------------------
	case 3: // Visualizar
		$locale = gCleanField($_REQUEST['locale']);
		$keyword = gCleanField($_REQUEST['keyword']);
		$lang = gCleanField($_REQUEST['lang']);

		$btns=$o->button("{title: back; style: default; url: " . $o->page ."}");
		foreach ($locales as $key=>$value)
		{
			$btns.=$o->button("{title: " . $value . "; style: info; url: " . $o->page ."&gPage=3&keyword=" . $keyword . "&lang=" . $key. "}");
		}
		$add=tagMe('div',"<br>".$btns,'class="container"');
		createDBPage($keyword, $add, $lang);
		$do=false;
		break;
	//-----------------------------------------------------------------------------------------------
	case 4: // Apagar (desativar)
		$keyword = gCleanField($_REQUEST['keyword']);
		$sql="UPDATE gfw_pages set active=1-active WHERE keyword='$keyword'";
		dbFastQuery($sql);
		redirect($o->page);
		break;
}



?>