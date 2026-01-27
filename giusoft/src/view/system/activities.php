<?php

$html .= $o->msgTitle('Relatório de atividades');

$gPage = $_REQUEST['gPage'];

switch ($gPage) {
	case 1:
		$where = [];
  		$filtros = [];
  		if ($data_de != "") {
			$where[] = " AND (date) >= '" . gDBDateTime($data_de) . ":01' ";
			$filtros[] = 'de ' . $data_de;
		}

		if ($data_ate != "") {
			$where[] = " AND (date) <= '" . gDBDateTime($data_ate) . ":59' ";
			$filtros[] = 'até ' . $data_ate;
		}

		if ($details != ""){
			$where[] = " AND details LIKE '%" . $details . "%'";
			$filtros[] = $details;
		}

		if ((int) $id_gfw_users > 0) {
			$where[] = sprintf(' AND id_gfw_users=%s ', $id_gfw_users);
			$rs = dbQuery('SELECT pessoas.nome FROM pessoas WHERE id = ' . $id_gfw_users);
			$filtros[] = "Usuário: " . $rs[0]['nome'];
		}

		$html .= $o->msgFilter(ucfirst(implode(" • ",$filtros)));
		$sql = "SELECT
					gfw_log.*,
					gfw_users.nickname,
					gfw_menus.title,
					gfw_menus.content
				FROM gfw_log
				LEFT JOIN gfw_users ON gfw_users.id=gfw_log.id_gfw_users
				LEFT JOIN gfw_menus ON gfw_menus.id=gfw_log.id_gfw_menus
				WHERE gfw_log.id > 0 " . implode("",$where) . "";
		$rs = dbQuery($sql);
		if ($rs) {
			$mtz = ["<-Opções", "<-Usuário", "Data/Hora", "<-Atividade", "<-Detalhes"];
			$html .= $o->tableBegin("big",true);
			$html .= $o->tableRow($mtz,"header");
			foreach ($rs as $row) {
				$mtz = [];
				$mtz[] = "<-".$o->button("{icon: search; caption: Detalhes; size: small; href: ".$o->page."&gPage=2&gId=".$row['id']."}");
				$mtz[] = "<-".strtoupper((string) $row['nickname']);
				$mtz[] = "".gDateTime($row['date']);
				$mtz[] = "<-".$row['title'];
				$mtz[] = "<-".$row['details'];
				$html .= $o->tableRow($mtz,"detail");
			}

			$html .= $o->tableEnd();
		} else {
			$html .= $o->msgAlert("Nenhum registro encontrado");
		}

		break;

	case 2:
		$sql = "SELECT
					gfw_log.*,
					gfw_users.nickname,
					gfw_menus.title,
					gfw_menus.content
				FROM gfw_log
				LEFT JOIN gfw_users ON gfw_users.id=gfw_log.id_gfw_users
				LEFT JOIN gfw_menus ON gfw_menus.id=gfw_log.id_gfw_menus
				WHERE gfw_log.id = " . $gId;
		$rs = dbQuery($sql);
		$row = $rs[0];
		$html .= $o->tableBegin("big", true);
		$mtz = [];
		$mtz[] = "<-Data e hora<br><b>".gDateTime($row['date'])."<br>&nbsp;</b>";
		$mtz[] = "<-Usuário<br><b>".strtoupper((string) $row['nickname'])."<br>&nbsp;</b>";
		$mtz[] = "<-Menu<br><b>".$row['title']."<br>".$o->small($row['content'])."</b>";
		$mtz[] = "<-Detalhes<br><b>".$row['details']."</b><br>&nbsp;";
		$html .= $o->tableRow($mtz, "header");
		$html .= $o->tableEnd();

		$html .= $o->msgSubTitle("Dados utilizados");
		$request = unserialize(base64_decode((string) $row['request']));
		$html .= $o->tableBegin("big", true);
		$mtz = [];
		$mtz[] = "->Nº";
		$mtz[] = "<-Campo";
		$mtz[] = "<-Valor";
		$html .= $o->tableRow($mtz, "header");
		$n = 0;
		foreach ($request as $key=>$value) {
			if ($key != 'gId' && $key != 'gIdd' && $key != 'gPage' && $key != 'g' && $key != 'submit_default') {
				$n++;
				$mtz = [];
				$mtz[] = "->".$n;
				$mtz[] = "<-".$key;
				$mtz[] = "<-".$value;
				$html .= $o->tableRow($mtz, "detail");
			}
		}

		$html.=$o->tableEnd();

		break;

	default:
		$frm = new gForm("{columns: 2}");
		$frm->add("{name: gPage; type: hidden; value: 1}");
		$frm->add(sprintf('{name: id_gfw_users; fieldLabel: Usuário; type: combo; value:%s; items: ', $id).$sp['combo_funcionarios']."}");
		$frm->add("{name: data_de; fieldLabel: Data de; type: dateTime; allowBlank: true; value:'".date("d-m-y")."00:00'"."}");
		$frm->add("{name: data_ate; fieldLabel: Data até; type: dateTime; allowBlank: true;value:'".date("d-m-y")."23:59'"." }");
		$frm->add("{name: details; fieldLabel: Detalhes; type: text; allowBlank: true; }");
		$html.=$frm->render($o);
		break;
}
