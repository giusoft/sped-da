<?php
$html = $o->msgTitle("Menus");

$exit = false;
// Obtendo idiomas disponíveis
$locales = [];
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
switch($gPage) {

	case 0: // Listagem de páginas e opção pra criar uma nova
		$html.=$o->button("{style: default; icon: plus; title: add; url: " . $o->page . "&gPage=1}");
		$html.=$o->button("{style: default; icon: search; title: See; url: " . $o->page . "&gPage=3&keyword=" . $field['keyword'] . "}");
		$html.="<br>&nbsp;";

		$maxOrder1 = intval(dbQueryValue("SELECT max(order1) ttl  FROM gfw_menus"));
		$maxOrder2 = intval(dbQueryValue("SELECT max(order2) ttl  FROM gfw_menus WHERE order1=" . $maxOrder1));

		$sql = "SELECT m.*, p.title pageTitle
				FROM gfw_menus m
				LEFT JOIN gfw_pages p on (m.link=p.keyword and p.locale='" . $gLang . "')
				WHERE m.locale='" . $gLang . "'
				ORDER BY m.order1, m.order2";
		$rs = dbFastQuery($sql);

		if ($rs) {
			// Obtém os idiomas ativos
			$tab = '';
			$tab.=$o->tableBegin('big', true, false, true); // tamanho, borda, zebra, ordenável
			$mtz = [];
			$mtz[] = 'Options';
			$mtz[] = '<-Ord1';
			$mtz[] = '<-Ord2';
			$mtz[] = '<-Type';
			$mtz[] = '<-Title';
			$mtz[] = '<-Link';
			$mtz[] = '<-File';
			foreach ($locales as $key => $value) {
				$mtz[] = $value;
			}

			$tab .= $o->tableRow($mtz, 'header');
			foreach ($rs as $field) {
				$mtz = [];

				$btns = '';
				$keyword = $field['keyword'];
				$btns .= $o->button("{style: info; size: tiny; icon: pencil; hint: Editar; url: " . $o->page . '&gPage=1&gId=' . $field['id']);
				// Ativar/desativar
				if ($field['active'] == 1) {
					$btns .= $o->button("{style: success; size: tiny; icon: thumbs-up; hint: Deactivate; url: " . $o->page . "&keyword=" . $keyword . "&gPage=4&gId=" . $field['id'] . "}");
				} else {
					$btns .= $o->button("{style: danger; size: tiny; icon: thumbs-down; hint: Activate; url: " . $o->page . "&keyword=" . $keyword . "&gPage=4&gId=" . $field['id'] . "}");
				}

				// Setas
				if (($field['order1'] > 1) || ($field['order2'] > 0)) {
					$btns .= $o->button("{style: info; size: tiny; icon: arrow-up; hint: Para cima; url: " . $o->page . "&keyword=" . $keyword . "&gPage=5}");
				}

				$btns .= $o->button("{style: info; size: tiny; icon: arrow-down; hint: Para baixo; url: " . $o->page . "&keyword=" . $keyword . "&gPage=6}");

				// Novo item dentro do dropdown
				if (($field['type'] == "dropdown") || ($field['type'] == "submenu")) {
					$btns.= $o->button("{style: info; size: tiny; icon: plus; hint: Adicionar; url: " . $o->page . "&keyword=" . $keyword . "&gPage=1}");
				}

				$btns.= $o->button("{style: danger; size: tiny; icon: trash; hint: Remover; url: " . $o->page . "&keyword=" . $keyword . "&gPage=7}");
				$mtz[] = "<-".$btns;
				$mtz[] = '<-' . $field['order1'];
				$mtz[] = '<-' . $field['order2'];
				$mtz[] = '<-' . $field['type'];

				$spc = '';
				if ($field['type'] == 'dropdownLink') {
					$spc = ' » ';
				}

				if ($field['type'] == 'submenu') {
					$spc = ' »» ';
				}

				if ($field['type'] == 'submenuLink') {
					$spc = '&nbsp;&nbsp; »» ';
				}

				if ($field['order2'] == 0) {
					$tab .= $o->tableLine();
					$mtz[] = '<-' . $spc . '<b>' . $field['title'] . '</b>';
				} elseif ($field['type'] == 'separator') {
					$mtz[] = ' ';
				} else {
					$mtz[] = '<-' . $spc . $field['title'].$o->small("<br>&nbsp;&nbsp;".$field['content']);
				}

				if ($field['pageTitle'] == '') {
					$mtz[] = '<-' . $field['link'];
				} else {
					$mtz[] = '<-' . $field['pageTitle'];
				}

				$mtz[] = '<-' . $field['file'];
				foreach ($locales as $key => $value) {
					$sql = "SELECT * FROM gfw_menus where keyword='" . $field['keyword'] . sprintf("' and locale='%s'", $key);
					$rsi = dbFastQuery($sql);
					if ($rsi) {
						foreach ($rsi as $fld) {
							if ($fld['title'] == '') {
								$mtz[] = $o->button("{style: info; size: tiny; title: Translate; url: " . $o->page . '&gPage=1&gId=' . $fld['id']);
							} else {
								$mtz[] = $o->button("{style: warning; size: tiny; title: Edit; url: " . $o->page . '&gPage=1&gId=' . $fld['id']);
							}
						}
					} else {
						$mtz[] = $o->button("{style: info; size: tiny; title: Add; url: " . $o->page . '&gPage=1&keyword=' . $fld['keyword'] . '&gId=' . $fld['id']);
					}
				}

				if ($field['active'] == 1) {
					$tab .= $o->tableRow($mtz);
				} else {
					$tab .= $o->tableRow($mtz, "text-danger");
				}

			}

			$tab .= $o->tableEnd();
		} else {
			$tab = $o->msgAlert(gT('error_no_menus'));
		}

		$html .= $tab;
		break;

	case 1: // Nova página
		$rs = array();
		$title = '';
		$date_publication = date("Y-m-d H:i:s");
		$content = '';
		$pageTitle = 'New';
		$link = '';
		$icon = '';
		$file = '';
		$keyword = (gCleanField($_REQUEST['keyword']));
		if ($gId > 0) {
			$pageTitle = 'Edit';
			$sql = "SELECT p.*, l.name localeName
					FROM gfw_menus p
					LEFT JOIN gfw_locales l on p.locale=l.locale
					WHERE p.id = " . $gId;
			$rs = dbFastQuery($sql);
			$title = $rs[0]['title'];
			$locale = $rs[0]['locale'];
			$localeName = $rs[0]['localeName'];
			$content = str_ireplace('<br />', "", $rs[0]['content']);
			$keyword = $rs[0]['keyword'];
			$link = $rs[0]['link'];
			$icon = $rs[0]['icon'];
			$type = $rs[0]['type'];
			$file = $rs[0]['file'];
			$show_at = match ($rs[0]['show_at']) {
				1 => gT('offline'),
				2 => gT('online'),
				default => gT('both'),
			};
		}

		$tipos = ['link', 'dropdown', 'dropdownLink', 'separator', 'submenu', 'submenuLink'];

		$frm = new gForm("{title: " . $pageTitle . "; columns: 3; debug: on}");
		$frm->setButtonBackCaption("back");
		$frm->add("{name: gPage; type: hidden; value: 2}");
		$frm->add("{name: gId; type: hidden; value: " . $gId . "}");
		$frm->add("{name: keyword; type: hidden; value: " . $keyword . "}");
		$frm->add("{name: locale; type: hidden; value: pt_BR}");
		$frm->add("{name: show_at; fieldLabel: show_at; type: combo; value: " . $show_at . "; items: ".json_encode([gT("both"), gT("offline"), gT("online")])."}");
		$frm->add("{name: type; type: combo; value: " . $type . "}",$tipos);
		$frm->add("{name: icon; type: lowerText; value: " . $icon . "}");
		$frm->add("{name: title; type: text; maxLength: 100; value: " . $title . "}");
		$frm->add("{name: content; type: text; fieldLabel: Texto de ajuda; value: " . $content . "}");
		$frm->add("{name: link; type: text; value: " . $link . "}");
		if ($usrId > 1) {
			$frm->add("{name: ffile;type: hidden; value: " . $file . "}");
		} else {
			$frm->add("{name: ffile; fieldLabel: Arquivo; type: text; value: " . $file . "}");
		}

		$frm->add("{name: order1; type: Ordem 1; value: " . $rs[0]['order1'] . "}");
		$frm->add("{name: order2; type: Ordem 2; value: " . $rs[0]['order2'] . "}");
		$frm->add("{name: pageLink; type: combo; fieldLabel: page; allowBlank: true; value: " . $link . "; items: " . $sp['gfw_pages'] . "}");
			//$frm->add("{name: type; type: text; value: " . $type . "}");
		if ($keyword != '') {
			$type = "dropdownLink";
		}

		$html.=$frm->render($o);
		break;

	case 2: // Tunnel (salvar/editar)
		$_SESSION['gMenu']='';
		$keyword = (gCleanField($_REQUEST['keyword']));
		$locale = gCleanField($_REQUEST['locale']);
		$link = gCleanField($_REQUEST['link']);
		$file = gCleanField($_REQUEST['ffile']);
		$icon = gCleanField($_REQUEST['icon']);
		$type = gCleanField($_REQUEST['type']);
		if ($locale=='0') {
			$locale=$gLang;
		}

		if (is_numeric($locale)) {
			$locale=gFieldById('gfw_locales', $locale, 'locale');
		}

		$tipos = ['link', 'dropdown', 'dropdownLink', 'separator', 'submenu', 'submenuLink'];
		$type = $tipos[intval($type)];
		$pageLink = '';
		$flds = [];
		$flds['title'] = gCleanField($_REQUEST['title']);
		$flds['icon'] = $icon;
		$flds['content'] = gCleanHTMLContent(nl2br((string) $_REQUEST['content']), '<br><p><h1><h2><h3><b><i><u><ul><ol><li><a>');
		$flds['locale'] = $locale;
		$flds['type'] = $type;
		$flds['file'] = $file;
		$flds['order1'] = $order1;
		$flds['order2'] = $order2;
		$flds['show_at']=$_REQUEST['show_at'];
		if ((gCleanField($_REQUEST['pageLink']) != '') && (gCleanField($_REQUEST['pageLink']) != '0')) {
			$link = gCleanField($_REQUEST['pageLink']);
		}

		$flds['link'] = $link;
		if ($gId == 0) {
			$flds['keyword'] = $flds['title'];
			$flds['keyword'] = str_replace(' ', '', $flds['keyword']);
			$flds['keyword'] = str_replace('&', '', $flds['keyword']);
			$flds['keyword'] = str_replace('.', '', $flds['keyword']);
			$flds['keyword'] = str_replace('/', '', $flds['keyword']);
			$flds['keyword'] = (gUcwords(tiracentos($flds['keyword'])));

			$maxOrder1 = intval(dbQueryValue("SELECT max(order1) ttl  FROM gfw_menus")) + 1;
			$maxOrder2 = 0;

			if ($type === "dropdownLink") {
				if ($keyword != '') {
					gDR("SELECT max(order1) ttl  FROM gfw_menus where keyword='" . $keyword . "'");
					$maxOrder1 = intval(dbQueryValue("SELECT max(order1) ttl  FROM gfw_menus where keyword='" . urldecode($keyword) . "'"));
				} else {
					$maxOrder1 = intval(dbQueryValue("SELECT max(order1) ttl  FROM gfw_menus"));
				}

				$maxOrder2 = intval(dbQueryValue("SELECT max(order2) ttl  FROM gfw_menus WHERE order1=" . $maxOrder1)) + 1;
			}

			if ($type === "submenuLink") {
				if ($keyword != '') {
					$maxOrder1 = intval(dbQueryValue("SELECT max(order1) ttl  FROM gfw_menus where keyword='" . urldecode($keyword) . "'"));
				} else {
					$maxOrder1 = intval(dbQueryValue("SELECT max(order1) ttl  FROM gfw_menus"));
				}

				//$maxOrder2 = intval(dbQueryValue("SELECT max(order2) ttl  FROM gfw_menus WHERE order1=" . $maxOrder1)) + 1;
				$maxOrder2 = intval(dbQueryValue("SELECT max(order2) ttl  FROM gfw_menus WHERE keyword='" . urldecode($keyword) . "'")) + 1;
				// Renumera itens após o item atual
				dbFastQuery(sprintf('UPDATE gfw_menus set order2=order2+1 WHERE order1=%d and order2>=%d', $maxOrder1, $maxOrder2));
			}

			$flds['order1'] = $maxOrder1;
			$flds['order2'] = $maxOrder2;

			// Checa se existe a keyword antes (highlander)
			$sql = "SELECT * FROM gfw_menus where keyword='" . $flds['keyword'] . "'";
			$tem = dbFastQuery($sql);
			if (count($tem) > 0) {
				$flds['keyword'] .= random_int(10000, 99999);
			}

			$flds['idd'] = 0;
			// Insere registro para o idioma informado
			dbFastInsert('gfw_menus', $flds);
			if (gVar("global.auto_translate") == "true") {
				foreach ($locales as $key => $value) {
					if ($locale != $key) {
						// Insere em branco para os outros idiomas
						$flds['locale'] = $key;
						$flds['title'] = '';
						$flds['content'] = '';
						dbFastInsert('gfw_menus', $flds);
					}
				}
			}

			redirect($o->page);
		} else {
			dbUpdate('gfw_menus', $flds, $gId);

			$sql = "SELECT * FROM gfw_menus WHERE id=" . $gId;
			$rs = dbFastQuery($sql);
			$sql = "UPDATE gfw_menus SET show_at='" . $flds['show_at'] . "', file='" . $file . "', link='" . $link . "', icon='" . $icon . "', type='" . $type . "' WHERE keyword='" . $rs[0]['keyword'] . "'";
			dbFastQuery($sql);
			redirect($o->page);
		}

		break;

	case 3: // Visualizar
		$locale = gCleanField($_REQUEST['locale']);
		if ($locale == '') {
			$locale = $gLang;
		}

		$keyword = gCleanField($_REQUEST['keyword']);
		$btns .= $o->button("{title: back; style: default; url: " . $o->page . "}");

		$sql = "SELECT p.*, l.name localeName, u.name user
				FROM gfw_menus p
				LEFT JOIN gfw_users u on p.idd=u.id
				LEFT JOIN gfw_locales l on p.locale=l.locale
				WHERE p.locale='{$locale}'
				ORDER BY id";
		$rsi = dbFastQuery($sql);
		$html = '';
		$flds = '';
		$menu = new gMenu("{title: Menu; type: bar;}");
		$flds['owner'] = '';
		$flds['title'] = gT('Menu');
		foreach ($rsi as $row) {
			$title = $row['title'];

			if ($row['title'] == '') {
				$title = $row['keyword'];
			}

			$menu->add("{title: " . $title . "; type: " . $row['type'] . "; hint: " . $row['content'] . "; icon: " . $row['icon'] . "; url: " . $row['link'] . "}");
		}

		$html.=$menu->render($o);
		foreach ($locales as $key => $value) {
			$style = "default";
			if ($key == $locale) {
				$style = "warning";
			}

			$btns .= $o->button("{title: " . $value . "; style: " . $style . "; url: " . $o->page . "&gPage=3&locale=" . $key . "&keyword=" . $keyword . "}");
		}

		$o->out(template('page.html', $html . "<hr>" . $btns, $flds));
		$exit = true;
		break;

	case 4: // Apagar (desativar)
		$_SESSION['gMenu']='';
		if ($gId) {
			$sql = "UPDATE gfw_menus set active=1-active WHERE id=" . $gId;
			dbFastQuery($sql);
			redirect($o->page);
		}

		$keyword = (gCleanField($_REQUEST['keyword']));
		$sql = sprintf("SELECT * FROM gfw_menus where keyword='%s'", $keyword);
		$rs = dbFastQuery($sql);
		if ($rs[0]['order2'] == 0) {
			$sql = "UPDATE gfw_menus set active=1-active WHERE order1=" . $rs[0]['order1'];
		} else {
			$sql = sprintf("UPDATE gfw_menus set active=1-active WHERE keyword='%s'", $keyword);
		}

		dbFastQuery($sql);
		redirect($o->page);
		break;

	case 5: // Subir
		$_SESSION['gMenu']='';
		$keyword = (gCleanField($_REQUEST['keyword']));
		$sql = sprintf("SELECT * FROM gfw_menus where keyword='%s'", $keyword);
		$rs = dbFastQuery($sql);
		$order1 = $rs[0]['order1'];
		$order2 = $rs[0]['order2'];
		// Nível raiz
		if ($order2 == 0) {
      		if ($order1 > 1) {
   				$sql = "UPDATE gfw_menus set order1=999999 WHERE order1='" . $order1 . "'";
   				dbFastQuery($sql);
   				$sql = "UPDATE gfw_menus set order1=" . ($order1) . " WHERE order1=" . ($order1 - 1);
   				dbFastQuery($sql);
   				$sql = "UPDATE gfw_menus set order1=" . ($order1 - 1) . " WHERE order1=999999";
   				dbFastQuery($sql);
   			}
		} elseif ($order2 > 1) {
			$sql = "UPDATE gfw_menus set order2=999999 WHERE order1=" . $order1 . " and order2=" . $order2;
			dbFastQuery($sql);
			$sql = "UPDATE gfw_menus set order2=" . ($order2) . " WHERE order1='" . $order1 . "' and order2=" . ($order2 - 1);
			dbFastQuery($sql);
			$sql = "UPDATE gfw_menus set order2=" . ($order2 - 1) . " WHERE order1='" . $order1 . "' and order2=999999";
			dbFastQuery($sql);
		} else {
			// Virando nível raiz
			$sql = "UPDATE gfw_menus set order1=order1+1 WHERE order1>=" . $order1;
			dbFastQuery($sql);
			$sql = "UPDATE gfw_menus set type='link',order2=0, order1=" . ($order1) . " WHERE keyword='" . $keyword . "'";
			dbFastQuery($sql);
		}

		redirect($o->page);
		break;

	case 6: // Descer
		$_SESSION['gMenu'] = '';
		$keyword = (gCleanField($_REQUEST['keyword']));
		$sql = sprintf("SELECT * FROM gfw_menus where keyword='%s'", $keyword);
		$rs = dbFastQuery($sql);
		$order1 = $rs[0]['order1'];
		$order2 = $rs[0]['order2'];
		$maxOrder1 = intval(dbQueryValue("SELECT max(order1) ttl  FROM gfw_menus"));
		$maxOrder2 = intval(dbQueryValue("SELECT max(order2) ttl  FROM gfw_menus WHERE order1=" . $maxOrder1));
		$maxThisOrder2 = intval(dbQueryValue("SELECT max(order2) ttl  FROM gfw_menus WHERE order1=" . $order1));

		// Nível raiz
		if ($order2 == 0) {
      		if ($order1 < $maxOrder2) {
   				// Subindo
   				$sql = "UPDATE gfw_menus set order1=999999 WHERE order1='" . $order1 . "'";
   				dbFastQuery($sql);
   				$sql = "UPDATE gfw_menus set order1=" . ($order1) . " WHERE order1=" . ($order1 + 1);
   				dbFastQuery($sql);
   				$sql = "UPDATE gfw_menus set order1=" . ($order1 + 1) . " WHERE order1=999999";
   				dbFastQuery($sql);
   			}
		} elseif ($order2 < $maxThisOrder2) {
			$sql = "UPDATE gfw_menus set order2=999999 WHERE order1=" . $order1 . " and order2=" . $order2;
			dbFastQuery($sql);
			$sql = "UPDATE gfw_menus set order2=" . ($order2) . " WHERE order2=" . ($order2 + 1);
			dbFastQuery($sql);
			$sql = "UPDATE gfw_menus set order2=" . ($order2 + 1) . " WHERE order2=999999";
			dbFastQuery($sql);
		} else {
			// Virando nível raiz
			$sql = "UPDATE gfw_menus set order1=order1+1 WHERE order1>" . ($order1);
			dbFastQuery($sql);
			$sql = "UPDATE gfw_menus set type='link',order2=0, order1=" . ($order1 + 1) . " WHERE keyword='" . $keyword . "'";
			dbFastQuery($sql);
		}

		redirect($o->page);
		break;

	case 7: // Apagar
		$_SESSION['gMenu']='';
		$keyword = (gCleanField($_REQUEST['keyword']));
		$sql = sprintf("DELETE from gfw_menus WHERE keyword='%s'", $keyword);
		dbFastQuery($sql);
		redirect($o->page);
		break;
}
