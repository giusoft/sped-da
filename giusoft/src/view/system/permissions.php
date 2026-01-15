<?php

include_once $gPathDefault . "gUI.php";

dbFastQuery("UPDATE gfw_permissions_links SET idd=0");

$ui = new gUI("{title: Permissões; table: gfw_permissions; permissions: SIUD; ajax: false}");

$ui->addDictionary("{name: locale; type: hidden; value: ".$gLang."}");
$ui->addDictionary("{name: id_gfw_users; fieldLabel: User; allowBlank: false; type: combo; items: SELECT id,name FROM gfw_users WHERE id>1 ORDER BY name}");
$ui->addDictionary("{name: id_gfw_menus; fieldLabel: Menu; allowBlank: false; type: combo; items: SELECT DISTINCT id,CONCAT(title,' (',link,')') title FROM gfw_menus WHERE (show_at=0 OR show_at=2) and link<>'' AND locale='".$gLang."' ORDER BY title}");
//$ui->addDictionary("{name: locale; fieldLabel: Locale; type: combo; items: ".$sp['gfw_locales']."}");

$ui->addTable("{title: Links; name: gfw_permissions_links; foreignKey: id_gfw_permissions; relationship: one-to-many; permissions: SIUD}");
$ui->addTable("{title: Users; name: gfw_permissions_users; foreignKey: id_gfw_permissions; relationship: one-to-many; permissions: SIUD}");

$ui->run($o, $html);


if ($gPage==0)
{
	$sql="SELECT DISTINCT u.id,u.name nome
			FROM gfw_users u
			INNER JOIN gfw_permissions_users pu ON pu.id_gfw_users=u.id
			WHERE active=1 AND employee=1 ORDER BY name";
	$rsu=dbFastQuery($sql);
	if (count($rsu)>0)
	{
		$html.=$o->tableBegin('big', true);
		$mtz = array();
		$mtz[]="<-Nome";
		$mtz[]="<-Permissões";
		$html.=$o->tableRow($mtz,"header");
		foreach ($rsu as $row)
		{
			$perm='';
			if ($row['id']==$row['idd'])
			{
				$perm[]="Acesso total";
			} else
			{
				$sql="SELECT p.*
						FROM gfw_permissions_users pu
						LEFT JOIN gfw_permissions p ON pu.id_gfw_permissions=p.id
						WHERE pu.id_gfw_users=".$row['id'];
				$rs=dbFastQuery($sql);
				foreach ($rs as $r)
					$perm[]=$r['name'];

			}
			$mtz = array();
			$mtz[]="<-".$row['nome'];
			$mtz[]="<-".implode(", ", $perm);
			$html.=$o->tableRow($mtz,"detail");
		}
		$html.=$o->tableEnd();
	}
}







/*
$html=$o->msgTitle("Permissões");

switch ($gPage)
{
	case 0:

		$html.=$o->button("{style: default; icon: plus; title: add; url: " . $o->page . "&gPage=1}");

		$sql="SELECT * FROM gfw_permissions ORDER BY name";
		$rs = dbFastQuery($sql);

		if (count($rs) > 0)
		{
			$tab = '';
			$tab.=$o->tableBegin('big', true, false, true); // tamanho, borda, zebra, ordenável
			$mtz = array();
			$mtz[] = 'Opções';
			$mtz[] = 'Id';
			$mtz[] = 'Name';
			$mtz[] = 'Ativo';
			$tab.=$o->tableRow($mtz, 'header');
			foreach ($rs as $field)
			{
				$mtz = array();
				$btns = '';
				$btns.= $o->button("{style: success; size: tiny; icon: edit; hint: Editar; url: " . $o->page . "&gPage=10&gId=" . $field['id'] . "}");
				if($field['active']==1)
					$btns.= $o->button("{style: danger; size: tiny; icon: times; hint: Desativar; url: " . $o->page . "&gPage=3&gId=" . $field['id'] . "}");
				else
					$btns.= $o->button("{style: success; size: tiny; icon: times; hint: Ativar; url: " . $o->page . "&gPage=3&gId=" . $field['id'] . "}");
				$mtz[] = $btns;
				$mtz[] = $field['id'];
				$mtz[] = $field['name'];
				$mtz[] = gCheck($field['active']);
				$tab.=$o->tableRow($mtz);
			}
			$tab.=$o->tableEnd();
		}
		else
		{
			$tab = $o->msgAlert(gT('error_no_fields'));
		}
		$html.=$tab;
		break;

	case 1:
		$frm=new gForm("{title: Permissões; style: 2column; }");
		$frm->setButtonBackCaption("Voltar");

		$frm->add("{name: gPage; type: hidden; value: 2}");
		$frm->add("{name: name; type: text;}");
		$html.=$frm->render($o);
		break;

	case 2:
		$fields=array();
		$fields['name']=$name;
		$fields['active']=1;
		dbInsert("gfw_permissions", $fields);
		redirect($o->page);
		break;

	case 3:
		$sql="UPDATE gfw_permissions SET active=1-active WHERE id=".$gId;
		gQuery($sql);
		redirect($o->page);
		break;


	case 10;
		//include_once $gPathDefault.'gCustomForm.php';
		$sql="SELECT * FROM gfw_permissions WHERE id=$gId";
		$rs=gQuery($sql);
		$form = new gForm('{name: form-permissions; title: Permissões; method:post;teste-form:ok}');
		$form->add("{name: gId; type:hidden; value: $gId }");

		$form->row(
				$form->add("{name: Id; type:show; value:$gId }"),
				$form->add("{name: Nome; type: text; value:".$rs->fields['name']." }")
		);
		$html.=$form->render($o);


		//Aba pessoas
		$abaPessoa="";
		$sql="SELECT id,nome FROM pessoas p WHERE p.id not in (SELECT id_user FROM gfw_permissions_users WHERE id_gfw_permissions=$gId)";
		$frm=new gForm("{title: Inserir pessoa; style: 2column; }");
		$frm->add("{name: id_pessoa; fieldLabel:Pessoas ;type: comboMultiSelection; items:$sql}");
		$frm->add("{name: gId; type:hidden; value:$gId}");
		$frm->add("{name: gPage; type:hidden; value:12}");
		$frm->add("{name: target; type:hidden; value:pessoas}");
		$abaPessoa.=$frm->render($o);

		$sql="SELECT g.id,p.nome
				FROM gfw_permissions_users g
				INNER JOIN pessoas p ON p.id=g.id_user
				WHERE g.id_gfw_permissions=$gId
				";
		$rs=gQuery($sql);

		if(!$rs->EOF)
		{
			$tab.=$o->tableBegin('big', true, false, true);
			$mtz=array("Excluir","Nome");
			$tab.=$o->tableRow($mtz, 'header');
			while(!$rs->EOF)
			{
				$mtz = array();
				$mtz[]=$o->button("{style: danger; size: tiny; icon: trash; hint: Exluir; url: " . $o->page . "&gPage=11&gId=$gId&idUser=" . $rs->fields['id'] . "&target=pessoas}");
				$mtz[]="<-".$rs->fields['nome'];
				$tab.=$o->tableRow($mtz, 'detail');
				$rs->MoveNext();
			}
			$tab.=$o->tableEnd();
		}
		$abaPessoa.=$tab;

		//Fim da ABA pessoas


		//Aba Links
		$abaLinks="";
		$sql="SELECT id,keyword,title, m.link
				from gfw_menus m
				where locale='pt_BR'
				and active=1 and show_at>1
				and m.id not in(SELECT id_gfw_menus FROM gfw_permissions_links WHERE id_gfw_permissions=$gId)
				ORDER BY m.id,m.type
		";
		$rs=gQuery($sql);
		$field="";
		while(!$rs->EOF)
		{
			$field[$rs->fields['id']]=autoencode($rs->fields['id']." - ".$rs->fields['keyword']." -> ".$rs->fields['title']." -> ".$rs->fields['link']);
			$rs->MoveNext();
		}
		$frm=new gForm("{title: Links; style: 2column; }");
		$frm->add("{name: id_link; fieldLabel:Links ;type: comboMultiSelection; allowBlank:false; disableSelectize:true}",$field);
		$frm->add("{name: read; fieldLabel: Ler; type:checkbox;}");
		$frm->add("{name: insert; fieldLabel: Inserir; type:checkbox;}");
		$frm->add("{name: edit; fieldLabel: Editar; type:checkbox;}");
		$frm->add("{name: delete; fieldLabel: Excluir; type:checkbox;}");
		$frm->add("{name: select; fieldLabel: Selecionar; type:checkbox;}");
		$frm->add("{name: gId; type:hidden; value:$gId}");
		$frm->add("{name: gPage; type:hidden; value:12}");
		$frm->add("{name: target; type:hidden; value:links}");
		$abaLinks.=$frm->render($o);

		$sql="SELECT l.id,m.keyword,m.title,m.id id_menu
			FROM gfw_permissions_links l
			INNER JOIN gfw_menus m on m.id=l.id_gfw_menus
			WHERE l.id_gfw_permissions=$gId
		";
		$rs=gQuery($sql);
		$tab="";
		if(!$rs->EOF)
		{
			$tab.=$o->tableBegin('big', true, false, true);
			$mtz=array("Excluir","<-Link");
			$tab.=$o->tableRow($mtz, 'header');
			while(!$rs->EOF)
			{
				$mtz = array();
				$mtz[]=$o->button("{style: danger; size: tiny; icon: trash; hint: Exluir; url: " . $o->page . "&gPage=11&gId=$gId&idLink=" . $rs->fields['id'] . "&target=link}");
				$mtz[]="<-".autoencode($rs->fields['id_menu']." - ".$rs->fields['keyword']." -> ".$rs->fields['title']);
				$tab.=$o->tableRow($mtz, 'detail');
				$rs->MoveNext();
			}
			$tab.=$o->tableEnd();
		}
		$abaLinks.=$tab;

		$tabs=array();
		$tabs[] = $o->addTabItem("Pessoas",$abaPessoa);
		$tabs[] = $o->addTabItem("Links",$abaLinks);
		$html.=$o->tabRender();
	break;

	//Deleta pessoas da permissão
	case 11:
		if($target=="pessoas")
			$sql="DELETE FROM gfw_permissions_users WHERE id=".$idUser;
		elseif($target=="link")
			$sql="DELETE FROM gfw_permissions_links WHERE id=$idLink";

		gQuery($sql);
		redirect($o->page."&gPage=10&gId=$gId");
		break;

	case 12:
		if($target=="pessoas")
		{
			if((is_array($id_pessoa)) && (count($id_pessoa) > 0))
			{
				$field="";
				$field['id_gfw_permissions']=$gId;
				foreach ($id_pessoa as $id)
				{
					$field['id_user']=$id;
					dbInsert("gfw_permissions_users", $field);
				}
			}
		}
		elseif($target=="links")
		{
			if((is_array($id_link)) && (count($id_link) > 0))
			{
				$field="";
				$field['id_gfw_permissions']=$gId;
				$field['read']=gDBCheck($read);
				$field['insert']=gDBCheck($insert);
				$field['edit']=gDBCheck($edit);
				$field['delete']=gDBCheck($delete);
				$field['select']=gDBCheck($select);
				foreach ($id_link as $id)
				{
					$field['id_gfw_menus']=$id;
					//dbInsert("gfw_permissions_links", $field);
					$sql="INSERT INTO gfw_permissions_links (`".implode("`,`",array_keys($field))."`) VALUES ('". implode("','",array_values($field))."')";
					gQuery($sql);
				}
			}
		}
		redirect($o->page."&gPage=10&gId=$gId");
		break;
}
*/
?>
