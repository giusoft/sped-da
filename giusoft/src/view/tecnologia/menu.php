<?
$html.=$o->msgTitle("Menu");
switch ($gPage)
{
	case 0:
		$_SESSION['edicaoAtiva']=1;	
		$html.=$o->button("{icon: plus; caption: Novo; style: info;  href: ".$o->page."&gPage=1}");
		$html.=$o->button("{icon: sign-out; caption: Retornar ao sistema; href: index.php?g=editar}");
		$html.='<br><br>';
		$sql="SELECT * FROM gfw_menus WHERE show_at=1 ORDER BY order1,order2";
		$rs=dbQuery($sql);
		$primeiro = true;
		$ultimo = count($rs)-1;
		if (count($rs)>0)
		{
			$o->out($o->modal("{title: Confirme; size: small; content: Excluir este menu?; okCaption: Excluir agora; name: confirmaExclusaoMenu; url: excluiMenu()}"), gLOC_INLINE, 999);
			$o->addJavascript('gIda=0;function excluiMenu(){document.location.href="'.$o->page."&gPage=3&gId=".'"+gIda;}');

			$html.=$o->tableBegin('big', true);
			$mtz = array();
			$mtz[]='<-Opções';
			$mtz[]='<-Título';
			$mtz[]='<-Ajuda';
			$html.=$o->tableRow($mtz, 'header');
			$cnt=0;
			foreach ($rs as $row)
			{
				$mtz = array();
				$btns='';

				if(apenasTelemetria() == 0)
				{
				
					if ($row['active']==1)
						$btns.=$o->button("{icon: thumbs-up; hint: Desativar; style: success; size: small; href: ".$o->page."&gPage=6&gId=".$row['id']."; }");
					else
						$btns.=$o->button("{icon: thumbs-down; hint: Ativar; style: danger; size: small; href: ".$o->page."&gPage=6&gId=".$row['id']."; }");
					if ($cnt <> $ultimo)
						$btns.=$o->button("{icon: caret-down; hint: Descer; style: info; size: small; href: ".$o->page."&gPage=5&gId=".$row['id']."}");
					if (!$primeiro)
						$btns.=$o->button("{icon: caret-up; hint: Subir; style: info; size: small; href: ".$o->page."&gPage=4&gId=".$row['id']."}");
					$primeiro = false;
	
					if ($row['file']=='')
					{
						$sql="SELECT * FROM gfw_pages WHERE keyword='".$row['link']."'";
						$rs2=dbQuery($sql);
						$btns.=$o->button("{icon: pencil; hint: Editar; size: small; href: ".$o->page."&gPage=1&gId=".$rs2[0]['id']."}");
						$btns.=$o->button("{icon: trash; hint: Excluir; style: danger; size: small; openModal: confirmaExclusaoMenu; }", "javascript:gIda='" . $row['id'] . "'");
					}
				}
				$mtz[]='<-'.$btns;
				$mtz[]='<-'.$row['title'];
				$mtz[]='<-'.$row['content'];
				$html.=$o->tableRow($mtz, 'detail');
				$cnt++;
			}
			$html.=$o->tableEnd();
		}

		break;
		
	case 1:
		$sql="SELECT * FROM gfw_pages WHERE id='".$gId."'";
		$rs2=dbQuery($sql);
		if ($rs2[0]['id']>0)
		{
			$pageContent = $rs2[0]['content'];
			$sql="SELECT * FROM gfw_menus WHERE link='".$rs2[0]['keyword']."'";
			$rs=dbQuery($sql);
			$row=$rs[0];
			$frm=new gForm();
			$frm->row(
				$frm->add("{name: title; fieldLabel: Título; type: text; value: ".$row['title']."}"),
				$frm->add("{name: content; fieldLabel: Ajuda; type: text; value: ".$row['content']."}")
			);
			$frm->add("{name: pageContent; fieldLabel: Conteúdo da página; type: memo; height: 300px; base64: true; value: ".$pageContent."}");
			$frm->add("{name: gPage; type: hidden; value: 2}");
			$frm->add("{name: gId; type: hidden; value: ".$row['id']."}");
			$html.=$frm->render($o);			
		} else
		{
			redirect($o->page);	
		}
		break;
		
	case 2:
		if(apenasTelemetria())
		{
			$html .= $o->msgDanger($_SESSION['msgApenasTelemetria']);
			$html .= $backButton;
		}
		else
		{
			$sql="SELECT * FROM gfw_menus WHERE id=".$gId;
			$rs=dbQuery($sql);
			$oldTitle = $rs[0]['title'];
			$link = $rs[0]['link'];
	
			$pageContent = gCleanField($_REQUEST['pageContent']);
			$title = gCleanField($_REQUEST['title']);
			$content = gCleanField($_REQUEST['content']);
	
			$flds = array(
				'title'		=> $title,
				'content'	=> $content
			);
			if ($gId>0)
			{
				dbUpdate('gfw_menus',$flds,$gId);
				dbUpdate('gfw_pages', array(
					'title'		=> $title,
					'content'	=> base64_encode($pageContent)
					), "keyword='$link'");
			} else
			{
				$keyword = urlencode(strtolower(gCleanField($_REQUEST['title'])));
				// Checa se existe a keyword antes (highlander)
				$sql = "SELECT * FROM gfw_menus where keyword='" . $flds['keyword'] . "'";
				$tem = dbFastQuery($sql);
				if (count($tem) > 0) {
					$keyword=$keyword.rand(10000,99999);
				}
				$sql = "SELECT max(order2) order2 FROM gfw_menus where order1=1";
				$ord = dbFastQuery($sql);
	
				$flds['active']			= '1';
				$flds['order1']			= '1';
				$flds['order2']			= ($ord[0]['order2']+1);
				$flds['show_at']		= '1';
				$flds['type']			= 'link';
				$flds['locale']			= 'pt_BR';
				$flds['link']			= $keyword;
				$flds['keyword']		= $keyword;
				$gId=dbInsert('gfw_menus', $flds, true);
	
				dbInsert('gfw_pages', array(
					'title'				=> $title,
					'keyword'			=> $keyword,
					'locale'			=> 'pt_BR',
					'active'			=> 1,
					'date_creation'		=> date('Y-m-d H:ï:s'),
					'date_publication'	=> date('Y-m-d H:ï:s'), 
					'content'			=> base64_encode($pageContent)
				), $gId);
			}
			redirect($o->page);
		}
		break;
		
	case 3:
	gLog("===> ...");
		if(apenasTelemetria())
		{
			$html .= $o->msgDanger($_SESSION['msgApenasTelemetria']);
			$html .= $backButton;
		}
		else
		{	
			$sql = "SELECT * FROM gfw_menus WHERE (file='' OR file IS NULL) AND id=".$gId;
			$rs = dbFastQuery($sql);
	
			if ($rs[0]['id']>0)
			{
				dbFastQuery("DELETE FROM gfw_pages WHERE keyword='".$rs[0]['link']."'");
				dbFastQuery("DELETE FROM gfw_menus WHERE id=$gId");
			}
			redirect($o->page);
		}
		break;

	case 4:
		if(apenasTelemetria())
		{
			$html .= $o->msgDanger($_SESSION['msgApenasTelemetria']);
			$html .= $backButton;
		}
		else
		{
			$sql = "SELECT * FROM gfw_menus where id=".$gId;
			$rs = dbFastQuery($sql);
			$keyword = $rs[0]['keyword'];
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
			} else {
				if ($order2 > 1) {
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
			}
			redirect($o->page);
		}
		break;

	case 5:
		if(apenasTelemetria())
		{
			$html .= $o->msgDanger($_SESSION['msgApenasTelemetria']);
			$html .= $backButton;
		}
		else
		{	
			$sql = "SELECT * FROM gfw_menus where id=".$gId;
			$rs = dbFastQuery($sql);
			$keyword = $rs[0]['keyword'];
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
			} else {
				if ($order2 < $maxThisOrder2) {
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
			}
			redirect($o->page);
		}
		break;

	case 6:
		if(apenasTelemetria())
		{
			$html .= $o->msgDanger($_SESSION['msgApenasTelemetria']);
			$html .= $backButton;
		}
		else
		{
			dbQuery("UPDATE gfw_menus SET active=1-active WHERE id=".$gId);
			redirect($o->page);
		}
		break;
}
?>