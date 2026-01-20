<?php

$html = $o->msgTitle("Traduções");

$exit = false;
$locales = '';
$langs = explode(",", str_replace(" ", "", gVar("global.languages")));
foreach ($langs as $l) {
	$locales[$l] = $l;
}

// ações de editar e adicionar nova tradução
$id_translation = intval($_REQUEST['id']);

switch ($gPage) {

	case 0:
		$html .= $o->button("{style: default; icon: plus; title: Adicionar; url: " . $o->page . "&gPage=1}");
		$html .= $o->button("{style: default; icon: thumbs-up; title: Ativar; url: " . $o->page . "&gCmd=createTranslationFiles}");
		$html .= "<br>&nbsp;";

		$sql = "SELECT gfw_i18n.*
				FROM ".gVar('database.i18n')."
				WHERE gfw_i18n.locale='".$langs[0]."'
				ORDER BY keyword";
		$rs = dbFastQuery($sql);

		$sql = "SELECT * FROM " . gVar('database.i18n') ;
		$rsi = dbFastQuery($sql);

		if (!$rs) {
			$js = "var keyword='';\nfunction confirmado() {	document.location.href='" . $o->page . "&gPage=3&tnl=delete&keyword='+keyword }";
			$o->addJavaScript($js);
			$o->out($o->modal("{title: Confirme; content: Excluir este registro?; okCaption: Excluir agora; name: confirm; url: confirmado()}"), gLOC_INLINE, 999);

	        // Obtém os idiomas ativos
			$tab = '';
			$tab.=$o->tableBegin('big', true, true, true); // tamanho, borda, zebra, ordenável
			$mtz = [];
			$mtz[] = '<-'.gT('Opções');
			$mtz[] = '<-'.gT('Chave');
			foreach ($locales as $key => $value) {
				$mtz[] = '<-'.$value;
			}

			$mtz[]="<-".gT('Fonte');
			$tab.=$o->tableRow($mtz, 'header');
			foreach ($rs as $field) {
				$mtz = [];
				$mtz[] = '<-'.$o->button("{style: danger; icon: trash; hint: Remover; size: tiny; openModal: confirm}", "javascript:keyword='" . $field['keyword'] . "'");
				$mtz[] = '<-'.$field['keyword'];
				foreach ($locales as $key => $value) {
					if ($key == $langs[0]) {
							$mtz[] = '<-<small><a href="index.php?g=translations&id=' . $field['id'] . '&locale=en&gPage=1">' . gCleanField($field['text']) . '</a></small>';
					} elseif ($rsi) {
						foreach ($rsi as $fld) {
							if ($fld['keyword'] == $field['keyword'] && $fld['locale'] == $key) {
								if ($fld['text'] == '') {
									$mtz[] = $o->button("{style: info; size: tiny; title: Traduzir; url: " . $o->page . '&locale=' . $fld['locale'] . "&id=" . $fld['id'] . "&gPage=1}");
								} else {
									$sty=$fld['revised'] ? '&nbsp;<span class="fal fa-check"></span>' : "";
									$mtz[] = '<-<small><a href="index.php?g=translations&id=' . $fld['id'] . '&locale=' . $fld['locale'] . '&gPage=1">' . gCleanField($fld['text']) . '</a></small>'.$sty.' ';
								}
							}
						}
					} else {
						$mtz[] = $o->button("{style: info; size: tiny; title: Traduzir; url: " . $o->page . "&gPage=1}");
					}
				}

				$mtz[] = "<-" . $field['source'];
				$tab .= $o->tableRow($mtz);
			}

			$tab .= $o->tableEnd();
		} else {
			$tab = $o->msgAlert("Nenhuma tradução");
		}

		$html.=$tab;
		break;

	case 1: // Form - Add e Edit
		if ($id_translation > 0) {

			if (!empty($_GET['update'])) {
				$html .= $o->msgSuccess('Tradução atualizada com sucesso');
			}

			//form de edição
			$sql = "SELECT i.* FROM " . gVar('database.i18n') . (' i WHERE i.id=' . $id_translation);
			$rs = dbFastQuery($sql);
			$frm = new gForm("{title: Editar tradução; url: " . $o->page . "}");
			$frm->add("{name: gPage; type: hidden; value: 2}");
			$frm->add("{name: id; type: hidden; value: " . $rs[0]['id'] . "}");
			$frm->add("{name: locale; type: hidden; value:  " . $rs[0]['locale'] . "; }");
			$frm->add("{name: locale_show; type: show; fieldLabel: Localização; value:  " . $rs[0]['locale'] . "; }");
			$frm->add("{name: keyword; type: hidden; fieldLabel: Chave; value: " . $rs[0]['keyword'] . "}");
			$frm->add("{name: keywordS; type: show; fieldLabel: Chave; value: " . $rs[0]['keyword'] . "}");
			$frm->add("{name: text; type: text; allowBlank: false; fieldLabel: Texto;value: " . $rs[0]['text'] . "}");
			$frm->setButtonNextCaption('Salvar');

			// Exibe botao para proxima se exibir chave para traduzir e armazena id em hidden para redirecionar
			$sql = "SELECT id FROM " . gVar('database.i18n') . " WHERE locale='".$rs[0]['locale']."' AND id > ".$rs[0]['id']." AND revised=0";
			$rs = dbFastQuery($sql);
			if ($rs) {
				$frm->add("{name: next_id; type: hidden; value: " . $rs[0]['id'] . "}");
				$frm->addButton("{type: submit; name:salvar_proxima; title: Salvar e traduzir próxima; style: danger;");
			}

			$out = $frm->render();
			$html.=$out[1];
			$o->out($out[0], gLOC_PRE);
			$o->out($out[2], gLOC_POS);
		} else {
			// form de adicionar
			$frm = new gForm("{title: Nova tradução; url: " . $o->page . "}");
			$frm->add("{name: gPage; type: hidden; value: 2}");
			$frm->add("{name: keyword; type: lowerText; fieldLabel: Chave; allowBlank: false;}");
			foreach ($locales as $key => $value) {
				$frm->add("{name: text_" . $key . "; fieldLabel: " . $value . " ;type: text;");
			}

			$out = $frm->render();
			$html.=$out[1];
			$o->out($out[0], gLOC_PRE);
			$o->out($out[2], gLOC_POS);
		}

		break;

	case 2: // Tunnel
		$id = gCleanField($_REQUEST['id']);
		$locale = gCleanField($_REQUEST['locale']);
		$keyword = gCleanField($_REQUEST['keyword']);
		$erros = '';

		$redirect = 'index.php?g=translations';

		if (!is_array($erros)) {
			// Tudo ok, então salva.
			if ($id) {
				$flds = '';
				$flds['text'] = gCleanField($_REQUEST['text']);
				$flds['revised'] = 1;
				dbUpdate(gVar('database.i18n'), $flds, $id);

			} else {
				foreach ($locales as $key => $value) {
					$text = gCleanField($_REQUEST['text_' . $key]);
					$flds = '';
					$flds['locale'] = $key;
					$flds['keyword'] = $keyword;
					$flds['text'] = $text;
					$flds['revised'] = 1;
					$sql = "SELECT * FROM ".gVar('database.i18n').sprintf(" WHERE locale='%s' and keyword='%s'", $locale, $keyword);
					$rs = dbFastQuery($sql);
					if (!$rs) {
						dbInsert(gVar('database.i18n'), $flds); // só insere se for novo...
					}
				}
			}

			redirect($redirect);
		} else {
			$msgErro = "Alguns erros foram encontrados:<br><br><ul>";
			foreach ($erros as $erro) {
				$msgErro.=tagMe("li", $erro);
			}

			$msgErro.="</ul>";
			$html.=$o->msgError($msgErro) . $backButton;
		}

		break;

	case 3: // Delete
		$keyword = gCleanField($_REQUEST['keyword']);
		$sql = sprintf("DELETE FROM gfw_i18n WHERE keyword = '%s'", $keyword);
		$rs = dbFastQuery($sql);
		redirect("index.php?g=translations");
		break;

	case 4: // Activate (desuso - usando o gCmd do gStart)
		$sql = "SELECT *
					FROM " . gVar('database.i18n') . "
					ORDER BY locale=".$langs[0]." desc, keyword";
		$rs = dbFastQuery($sql);

		foreach ($rs as $row) {
			// O padrão é o inglês
			if ($row['locale'] == $langs[0]) {
				$en[$row['keyword']] = $row['text'];
			}

			if ($row['text'] != '') {
				$tr[$row['locale']].='$gLngs["' . $row['keyword'] . '"]=array("' . $row['text'] . '");' . "\n";
			} else {
				// Não havendo tradução para este idioma, será usado o inglês
				$tr[$row['locale']].='$gLngs["' . $row['keyword'] . '"]=array("' . $en[$row['keyword']] . '");' . "\n";
			}
		}

		$pre = sys_get_temp_dir() . '/' . str_replace(" ", "_", gVar("global.site")) . '-';
		$pre = str_replace('//', '/', $pre);
		gLog('==> Salvando arquivo de tradução deste site: ' . $pre);
		foreach ($tr as $key => $value) {
			$arq = $pre . $key . ".php";
			file_put_contents($arq, "<?\n" . $value . "?>\n");
		}

		$html.=$o->msgInfo("Salvo") . $backButton;
		break;

}
