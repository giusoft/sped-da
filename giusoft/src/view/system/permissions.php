<?php

include_once $gPathDefault . "gUI.php";

dbFastQuery("UPDATE gfw_permissions_links SET idd=0");

$ui = new gUI("{title: Permissões; table: gfw_permissions; permissions: SIUD; ajax: false}");

$ui->addDictionary("{name: locale; type: hidden; value: ".$gLang."}");
$ui->addDictionary("{name: id_gfw_users; fieldLabel: User; allowBlank: false; type: combo; items: SELECT id,name FROM gfw_users WHERE id>1 ORDER BY name}");
$ui->addDictionary("{name: id_gfw_menus; fieldLabel: Menu; allowBlank: false; type: combo; items: SELECT DISTINCT id,CONCAT(title,' (',link,')') title FROM gfw_menus WHERE (show_at=0 OR show_at=2) and link<>'' AND locale='".$gLang."' ORDER BY title}");

$ui->addTable("{title: Links; name: gfw_permissions_links; foreignKey: id_gfw_permissions; relationship: one-to-many; permissions: SIUD}");
$ui->addTable("{title: Users; name: gfw_permissions_users; foreignKey: id_gfw_permissions; relationship: one-to-many; permissions: SIUD}");

$ui->run($o, $html);

if ($gPage == 0) {
	$sql = "SELECT DISTINCT u.id,u.name nome
			FROM gfw_users u
			INNER JOIN gfw_permissions_users pu ON pu.id_gfw_users=u.id
			WHERE active=1 AND employee=1 ORDER BY name";
	$rsu = dbFastQuery($sql);
	if ($rsu) {
		$html.=$o->tableBegin('big', true);
		$mtz = [];
		$mtz[] = "<-Nome";
		$mtz[] = "<-Permissões";
		$html .= $o->tableRow($mtz,"header");
		foreach ($rsu as $row) {
			$perm=[];
			if ($row['id'] == $row['idd']) {
				$perm[]="Acesso total";
			} else {
				$sql = "SELECT p.*
						FROM gfw_permissions_users pu
						LEFT JOIN gfw_permissions p ON pu.id_gfw_permissions=p.id
						WHERE pu.id_gfw_users=".$row['id'];
				$rs = dbFastQuery($sql);
				foreach ($rs as $r) {
					$perm[]=$r['name'];
				}

			}

			$mtz = [];
			$mtz[] = "<-".$row['nome'];
			$mtz[] = "<-".implode(", ", $perm);
			$html.=$o->tableRow($mtz,"detail");
		}

		$html.=$o->tableEnd();
	}
}