<?php

$html = $o->msgTitle("Posts");

$exit = false;

// Obtendo idiomas disponíveis
$pre = gVar("database.system");
if ($pre == '') {
	$pre = 'gfw_';
}

$locales = '';
$langs = explode(",", str_replace(" ", "", gVar("global.languages")));
foreach ($langs as $l) {
	$locales[$l] = $l;
}

$sql = "SELECT * FROM ".$pre."locales";
$rs = dbFastQuery($sql);
foreach ($rs as $row) {
	if (!empty($locales[$row['locale']])) {
		$locales[$row['locale']] = $row['name'];
	}
}

// Ações
switch ($gPage) {

	case 0: // Listagem de páginas e opção pra criar uma nova
		$html.=$o->button("{style: default; icon: plus; title: add; url: " . $o->page . "&gPage=1}");
		$html.="<br>&nbsp;";

		$sql="SELECT * FROM ".$pre."posts WHERE locale='".$gLang."' ORDER BY id DESC";
		$rs = dbFastQuery($sql);

		if (count($rs) > 0) {
			// Obtém os idiomas ativos
			$tab = '';
			$tab.=$o->tableBegin('big', true, false, true); // tamanho, borda, zebra, ordenável
			$mtz = [];
			$mtz[] = 'Options';
			$mtz[] = 'Creation';
			$mtz[] = 'Publication';
			$mtz[] = '<-Title';
			foreach ($locales as $key => $value) {
				$mtz[] = $value;
			}

			$tab.=$o->tableRow($mtz, 'header');
			foreach ($rs as $field) {
				$mtz = [];
				$btns = '';
				$btns.= $o->button("{style: default; size: tiny; icon: search; hint: See; url: " . $o->page . "&gPage=3&keyword=" . $field['keyword'] . "}");
				if ($field['active']==1) {
					$btns.= $o->button("{style: success; size: tiny; icon: thumbs-up; hint: Deactivate; url: " . $o->page . "&keyword=" . $field['keyword'] . "&gPage=4}");
				} else {
					$btns.= $o->button("{style: danger; size: tiny; icon: thumbs-down;hint: Activate; url: " . $o->page . "&keyword=" . $field['keyword'] . "&gPage=4}");
				}

				$mtz[] = $btns;
				$mtz[] = gDateTime($field['date_creation']);
				$mtz[] = gDateTime($field['date_publication']);

				$mtz[] = '<-'.$field['title'];
				foreach ($locales as $key => $value) {
					$sql = "SELECT * FROM ".$pre."posts where keyword='" . $field['keyword'] . sprintf("' and locale='%s'", $key);
					$rsi = dbFastQuery($sql);
					if (count($rsi) > 0) {
						foreach ($rsi as $fld) {
							if ($fld['content'] == '') {
								$mtz[] = $o->button("{style: info; size: tiny; title: Translate; url: " . $o->page . '&gPage=1&gId=' . $fld['id']);
							} else {
								$mtz[] = $o->button("{style: warning; size: tiny; title: Edit; url: " . $o->page . '&gPage=1&gId=' . $fld['id']);
							}
						}
					} else {
						$mtz[] = $o->button("{style: info; size: tiny; title: Add; url: " . $o->page . '&gPage=1&keyword=' . $fld['keyword'] . '&gId=' . $fld['id']);
					}
				}

				$tab.=$o->tableRow($mtz);
			}

			$tab.=$o->tableEnd();
		} else {
			$tab = $o->msgAlert(gT('error_no_pages'));
		}

		$html.=$tab;
		break;


	case 1: // Nova página
		$rs = '';
		$date_publication = date("Y-m-d H:i:s");
		$pageTitle = 'New';
		$keyword = gCleanField($_REQUEST['keyword']);
		$gAction = gCleanField($_REQUEST['gAction']);
		$title = empty($_REQUEST['title']) ? $keyword : gCleanField($_REQUEST['title']);

		$content = gCleanField($_REQUEST['content']);
		$tags = gCleanField($_REQUEST['tags']);

		if ($gId > 0) {
			$pageTitle = 'Edit';
			$sql = "SELECT p.*, l.name localeName
					FROM ".$pre."posts p
					LEFT JOIN ".$pre.('locales l on p.locale=l.locale
					WHERE p.id=' . $gId);
			$rs=dbFastQuery($sql);
			$title=$rs[0]['title'];
			$tags=$rs[0]['tags'];
			$date_publication=$rs[0]['date_publication'];
			$locale=$rs[0]['locale'];
			$localeName=$rs[0]['localeName'];
			$keyword=$rs[0]['keyword'];
			$content=$rs[0]['content'];
			if (!str_contains((string) $content,' ')) {
				$content=base64_decode((string) $content);
			}
		}

		$frm=new gForm("{title: ".$pageTitle."; style: 2column; }");
		$frm->setButtonBackCaption("back");

		$frm->add("{name: gPage; type: hidden; value: 2}");
		$frm->add("{name: gId; type: hidden; value: " . $gId . "}");
		$frm->add("{name: gAction; type: hidden; value: " . $gAction . "}");
		$frm->add("{name: keyword; type: hidden; value: " . $keyword . "}");
		if ($locale != '') {
			$frm->add("{name: locale; type: hidden; value: " . $locale . "}");
			$frm->add("{type: show; fieldLabel: locale; value: " . gT($localeName) . ";}");
		} else {
			$frm->add("{name: locale; type: combo; value: '".$gLang."'; items: ". $sp[$pre.'locales'] ."}");
		}

		$frm->row(
			$frm->add("{name: title; type: text; maxLength: 100; value: " . $title . "}"),
			$frm->add("{name: date_publication; fieldLabel: publication_date; type: dateTime; value: " . gDateTime($date_publication) . "}"),
			$frm->add("{name: tags; type: text; maxLength: 150; value: " . $tags . "}")
		);

		$frm->add("{name: content; type: memo; height: 320; style: true; alerts: true; code: true; align: false; h1: false; h2: false; }", ($content));
		$form=$frm->render();
        $o->out($form[0],gLOC_PRE);
		$o->out($form[2],gLOC_POS);
		foreach ($form[3] as $js)
		$o->addJavascript($js);

		$html.=$form[1];
		break;

	case 2: // Tunnel (salvar/editar)
		$locale=gCleanField($_REQUEST['locale']);
		$gAction=gCleanField($_REQUEST['gAction']);
		$page=$o->page;
		$page=substr($page,0,strpos($page,'?g='));
		if (is_numeric($locale)) {
			$sql="SELECT * FROM ".$pre.'locales WHERE id='.$locale;
			$rst=dbFastQuery($sql);
			$locale=$rst[0]['locale'];
		}

		if ($locale=='') {
			$locale=$gLang;
		}

		$flds='';
		$flds['title']=gCleanField($_REQUEST['title']);
		$flds['tags']=gCleanField($_REQUEST['tags']);
		$flds['date_publication']=gDBDateTime($_REQUEST['date_publication']);
		$flds['content']=  base64_encode(gDBMemo($_REQUEST['content']));
		$flds['locale']=$locale;
		$flds['id_users']=$usrId;
		$flds['date_modification']=date("Y-m-d H:i:s");
		$keyword=$flds['title'];
		if ($_REQUEST['keyword'] != '') {
			$keyword=gCleanField($_REQUEST['keyword']);
		}

		if ($gId == 0) {
			$flds['keyword']=$keyword;
			$flds['keyword']=str_replace(' ','',$flds['keyword']);
			$flds['keyword']=str_replace('&','',$flds['keyword']);
			$flds['keyword']=str_replace('.','',$flds['keyword']);
			$flds['keyword']=str_replace('/','',$flds['keyword']);
			$flds['keyword']=urlencode(gUcwords(tiracentos($flds['keyword'])));
			if ($gAction == "wiki") {
				$flds['active']='1';
			}

			// Checa se existe a keyword antes (highlander)
			$sql="SELECT * FROM ".$pre.sprintf("posts where keyword='%s'", $keyword);
			$tem=dbFastQuery($sql);
			if ($tem) {
				$html .= $o->msgError("error_keyword_exists");
			} else {
				$flds['idd']=$usrId;
				$flds['date_creation']=date("Y-m-d H:i:s");
				// Insere registro para o idioma informado
				dbInsert($pre.'posts',$flds);
				foreach ($locales as $key => $value) {
					if ($locale != $key) {
						// Insere em branco para os outros idiomas
						$flds['locale']=$key;
						$flds['content']='';
						dbInsert($pre.'posts',$flds);
					}
				}

				if ($gAction == 'wiki') {
					redirect($page.'?g=index&pp='.$flds['keyword']);
				} else {
					redirect($o->page);
				}
			}
		} else {
			dbUpdate($pre.'posts',$flds,$gId);
			if ($gAction == 'wiki') {
				redirect($page.'?g=index&pp='.$keyword);
			} else {
				redirect($o->page);
			}
		}

		break;

	case 3: // Visualizar
		$locale = gCleanField($_REQUEST['locale']);
		$keyword = gCleanField($_REQUEST['keyword']);
		$lang = gCleanField($_REQUEST['lang']);

		$btns = $o->button("{title: back; style: default; url: " . $o->page ."}");
		foreach ($locales as $key => $value) {
			$btns .= $o->button("{title: " . $value . "; style: info; url: " . $o->page ."&gPage=3&keyword=" . $keyword . "&lang=" . $key. "}");
		}

		$add = tagMe('div',"<br>".$btns,'class="container"');
		createDBPage($keyword, $add, $lang);
		$do = false;
		break;

	case 4: // Apagar (desativar)
		$keyword = gCleanField($_REQUEST['keyword']);
		$sql = "UPDATE " . $pre . sprintf("posts set active=1-active WHERE keyword='%s'", $keyword);
		dbFastQuery($sql);
		redirect($o->page);
		break;
}
