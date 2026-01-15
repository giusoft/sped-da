<?
$html.=$o->msgTitle("Aparência do Site");

switch ($gPage)
{
	case 0:
		$fonteTitulos=gVar("global.headersfont");
		$fontePagina=gVar("global.bodyfont");

		$rs=dbQuery("SELECT * FROM gfw_themes WHERE name='".gVar("global.theme")."'");
		if (count($rs)>0)
			$tema=$rs[0]['id'];
		else
			$tema=2; // giusoft

		$rs=dbQuery("SELECT * FROM gfw_fonts WHERE name='".gVar("global.bodyfont")."'");
		if (count($rs)>0)
			$fontePagina=$rs[0]['id'];
		$rs=dbQuery("SELECT * FROM gfw_fonts WHERE name='".gVar("global.headersfont")."'");
		if (count($rs)>0)
			$fonteTitulos=$rs[0]['id'];

		$frm = new gForm("{columns: 3}");
		$frm->add("{name: gPage; type: hidden; value: 1}");
		$frm->add("{name: tema; type: combo; allowBlank: false; value: ".$tema."; items: ".$sp['combo_gfw_themes']."}");
		$frm->add("{name: fonteTitulos; fieldLabel: Fonte dos títulos; type: combo; allowBlank: false; value: ".$fonteTitulos."; items: ".$sp['combo_gfw_fonts']."}");
		$frm->add("{name: fontePagina; fieldLabel: Fonte da página; type: combo; allowBlank: false; value: ".$fontePagina."; items: ".$sp['combo_gfw_fonts']."}");
		$frm->add("{name: arquivo; fieldLabel: Logomarca; type: file; hint: Arquivo JPG}");
		$frm->addButton("{icon: check; title: Restaurar padrão; style: default; href: ".$o->page."&gPage=2}");
		$html.=$frm->render($o);
		$html.=$o->hr('soft');

		$html.='<div class="row">';

		$html.='<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">';
		if (file_exists("files/logo.jpg"))
		{
			$html.='<img src="files/logo.jpg?'.date("His").'" style="max-height: 48px"><br>';
		}
		$html.=$o->image("{url: files/logo.jpg}");
		$html.="<h1><a href='#'>Título da página</a></h1>";
		$html.=$o->msgSubTitle("Sub-título da página");
		$html.="Texto normal da página.<br><br>";
		$html.=$o->msgInfo("Informação");
		$html.=$o->msgAlert("Informação importante");
		$html.=$o->msgDanger("Informação muito importante");
		$html.='</div>';

		$html.='<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">';

		$rs=dbQuery("SELECT * FROM geral_cidades LIMIT 5");
		$html.=$o->tableBegin('big', true);
		$mtz = array();
		$mtz[]='<-Opções';
		$mtz[]='->Id';
		$mtz[]='<-Nome';
		$html.=$o->tableRow($mtz, 'header');
		foreach ($rs as $row)
		{
			$mtz = array();
			$btns=$o->button("{icon: folder-open; caption: Abrir; style: default; size: small}");
			$btns.=$o->button("{icon: search; caption: Buscar; style: warning; size: small}");
			$btns.=$o->button("{icon: pencil; style: info; size: small}");
			$btns.=$o->button("{icon: trash; style: danger; size: small}");
			$mtz[]='<-'.$btns;
			$mtz[]='->'.$row['id'];
			$mtz[]='<-'.$row['descricao'];
			$html.=$o->tableRow($mtz, 'detail');
		}
		$html.=$o->tableEnd();
		$html.='</div>';

		$html.='</div>';
		break;

	case 1:
		$rs=dbQuery("SELECT * FROM gfw_themes WHERE id=".intval($_REQUEST['tema']));
		if ($rs[0]['id']>0)
		{
			dbQuery("UPDATE gfw_users SET theme='".$rs[0]['name']."' WHERE id=2");
			$_SESSION['gTheme']=$rs[0]['name'];
		}
		$rs=dbQuery("SELECT * FROM gfw_fonts WHERE id=".intval($_REQUEST['fontePagina']));
		if ($rs[0]['id']>0)
		{
			dbQuery("UPDATE gfw_users SET bodyfont='".$rs[0]['name']."' WHERE id=2");
			$_SESSION['gFontBody']=$rs[0]['name'];
		}
		$rs=dbQuery("SELECT * FROM gfw_fonts WHERE id=".intval($_REQUEST['fonteTitulos']));
		if ($rs[0]['id']>0)
		{
			dbQuery("UPDATE gfw_users SET headersfont='".$rs[0]['name']."' WHERE id=2");
			$_SESSION['gFontHeaders']=$rs[0]['name'];
		}

		$img = '';
		$imgName = 'logo.jpg';
		$erros='';
		$tamanhoMaximo=2000000;
		$gPathUsrFiles=$gPath."files/";
		$http_usr_files=$http_base."files/";

		$arquivo = isset($_FILES['arquivo']) ? $_FILES['arquivo'] : FALSE;

		if ($arquivo['name']<>'') {
			// Verifica tamanho do arquivo
			if ($arquivo['type'] != 'image/jpeg' && $arquivo['type'] != 'image/png')
				$erros[] = 'A imagem deve ser obrigatoriamente em formato JPG ou PNG';
			if ($arquivo['size'] > $tamanhoMaximo)
				$erros[] = 'Arquivo em tamanho muito grande! A imagem deve ser de no máximo ' . $tamanhoMaximo . ' bytes. Envie outro arquivo...';
			if (is_array($erros))
			{
				$msgErro = "Não foi possível salvar o arquivo de imagem.<br><br><ul>";
				foreach ($erros as $erro)
				{
					$msgErro.="<li>$erro</li>";
				}
				$msgErro.= '</ul>';
				$html.=$o->msgDanger($msgErro);
				$html.=$backButton;
			} else
			{
				$ok = move_uploaded_file($arquivo['tmp_name'], $gPathUsrFiles . $imgName);
				gLog("===> Imagem salva: ".$gPathUsrFiles . $imgName . " (".$arquivo['tmp_name'].")");
				chmod($gPathUsrFiles . $imgName, 0644); // evita ação de hackers
				redirect($o->page."&gPage=0");
			}
		} else
		{
			redirect($o->page."&gPage=0");
		}

		break;

	case 2:
		dbQuery("UPDATE gfw_users SET theme='giusoft' WHERE id=2");
		$_SESSION['gTheme']='giusoft';
		$_SESSION['gFontBody']='Metrophobic';
		$_SESSION['gFontHeaders']='Comfortaa';
		redirect($o->page."&gPage=0");
		break;
}

?>