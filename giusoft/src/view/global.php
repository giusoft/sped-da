<?php
$backButton = $o->button('{title: Voltar; icon: arrow-left; url: javascript:history.back(-1)}');

/**
 * Cria menu da página
 * @global type $gLang
 * @global type $usrAdmin
 * @global type $usrId
 * @global type $debug
 * @param type $o
 * @return type
 */
function createMenu()
{
	global $o, $AMBIENTE_TESTE, $gLang, $usrAdmin, $usrId, $debug, $gPathImg, $gPath, $gBASE, $gParam, $debug, $gDevice, $usrCliente, $LOCALHOST;

	$oldDebug = $debug;
	$debug = false;

	if ($_REQUEST['g']<>'painel')
	{

		// Antes de gerar o menu, verifica se o acesso ao sistema está permitido...

		$acessoTotal = false;
		if ($usrId <= 2) {
			$acessoTotal = true;
		} else {
			$acessoTotal = in_array(1, $_SESSION['permissionsIdNames']);
		}

		if ($gDevice=="mobile")
		{
			if ($usrId==0)
			{
				$sai='';
			} else {
				$sai = "<div class='menu' onClick='window.location=\"index.php\"'>Menu</div>";
			}

			if ($_SERVER['HTTP_HOST']=='localhost')
			{
				$sai.=$o->label(" AMBIENTE LOCALHOST ",'danger');
			}

			$sai.=$o->label($_SESSION['armazemAtualDescricao'],'success');

			if ($AMBIENTE_TESTE)
			{
				$sai.=$o->label(" AMBIENTE TESTE ",'danger');
			}
			if (($usrId>0) && ($_REQUEST['g']=="" || $_REQUEST['g']=="login" || $_REQUEST['g']=="index"))
			{
				$perm=$_SESSION['permissions'];
				if($usrId > 2 && !$acessoTotal)
				{
					// 127 = pesquisa de satisfação
					if (count($_SESSION['permissionsIds'])>0)
						$where=" AND m.id in (".implode(",",$_SESSION['permissionsIds']).") ";
					else
						$where=" AND m.id=-1 ";
				}
				$sql = "SELECT m.*
							FROM gfw_menus m
							WHERE m.link <> ''
								AND (m.order1 = 20 OR
									(m.order1 = 30 AND m.order2 < 200)
								)
								AND m.locale='" . $gLang . "' AND m.active=1 $show $where
							ORDER BY m.order1, m.order2";
							//m.order1 = 20 -> menu operacoes
							//m.order1 = 30 AND m.order2 < 200 -> = menu ferramentas
				$rsi = dbQuery($sql);

				$html = '';
				$flds = '';
				$flds['owner'] = '';
				$flds['title'] = gT('Menu');
				$ultimoTipo='';
				foreach ($rsi as $row) {
					$title = $row['title'];

					if ($row['title'] == '') {
						$sql = "SELECT m.*
								FROM gfw_menus m
								WHERE m.keyword='".$row['keyword']."' AND m.title ORDER BY m.id";
						$rst = dbQuery($sql);
						$title=$rst[0]['title'];
					}
					$link = trim($row['link']);
					// É um link?
					if (($link <> '') && (stripos($link, '.') === false) && (stripos($link, '/') === false)) {
						if ($row['file']<>'')
						{
							$link = $_SERVER["PHP_SELF"] . '?g=' . $link.'&'.$row['add_parameters'];
						} else
						{
							// Não... então é uma página do banco de dados
							$link = $_SERVER["PHP_SELF"] . '?g=open&p=' . $link.'&'.$row['add_parameters'];
						}
					}
					$sai.="<div class='g-menu' onClick='window.location=\"".$link."\"'>".$title."</div>";
					//$menu->add("{title: " . urldecode($title) . "; type: " . $row['type'] . "; popover: " . $row['content'] . "; icon: " . $row['icon'] . "; url: " . $link . "}");
					$ultimoTipo=$row['type'];
				}
				$sai.="<div class='g-menu' onClick='window.location=\"index.php?g=logout\"'>Sair do sistema</div>";
			}
		} else {
			if ($_REQUEST['gPDF']<>1)
			{
				$autotranslate=gVar('global.auto_translate');
				$translate=gVar('global.translate');

				gVar('global.translate', 'false');
				gVar('global.auto_translate', 'false');

				$pre='';
				if ($usrId>0)
					$titulo=gVar("global.site");
				else
				{
					//$logo='<a href="'.substr($o->page,0,strpos($o->page,"?")).'"><img class="img-responsive" src="pub/img/logo.png" alt="GIUSOFT Logo"></a><br>';
					//$logo='<a href="'.substr($o->page,0,strpos($o->page,"?")).'"><h1>Informativo</h1></a><br>';

					$lang='<div class="text-right" style="margin-top: 16px; margin-right: 16px">';
					$lang.='<a href="'.$o->page.'&gLang=pt_BR"><img src="pub/img/Brazil.png" alt="Brazil flag"></a> ';
					$lang.='<a href="'.$o->page.'&gLang=en"><img src="pub/img/Canada.png" alt="Canada flag"></a>';
					$lang.='</div>';

					$pre=$o->n;
					$pre.='<div class="row" style="background-color: #f8f8f8">'.$o->n;
					$pre.='	<div class="col-xs-7 col-sm-9 col-md-6 col-lg-6">'.$o->n;
					$pre.=$logo.$o->n;
					$pre.='	</div>'.$o->n;
					$pre.='	<div class="col-xs-5 col-sm-3 col-md-6 col-lg-6">'.$o->n;
					$pre.=$lang.$o->n;
					$pre.='	</div>'.$o->n;
					$pre.='</div>'.$o->n;
					$titulo='Home';
				}

				$logo="logo.png";
				$dbname=gVar("database.name");
				if (file_exists($gPathImg."logo_".$dbname.".jpeg"))
					$logo="logo_".$dbname.".jpeg";
				if (file_exists($gPathImg."logo_".$dbname.".jpg"))
					$logo="logo_".$dbname.".jpg";
				if (file_exists($gPathImg."logo_".$dbname.".png"))
					$logo="logo_".$dbname.".png";
				if (file_exists($gPath."files/logo.jpg"))
					$logo="files/logo.jpg";
				if ($usrId == 0 || $_SESSION['edicaoAtiva']==1)
				{
					$menu=new gMenu("{title: ".$titulo."; logo: ".$logo."; maxHeight: 43px; debug: " . $debug . "; type: bar; fixed: true; url: index.php}");
					$show="and (show_at=0 or show_at=1)";
				} else
				{

					$menu=new gMenu("{title: ".$titulo."; logo: faviconw.png; maxHeight: 24px; debug: " . $debug . "; type: bar; fixed: true; url: index.php?g=index}");
					$show="and (show_at=0 or show_at=2)";
				}

				$perm=$_SESSION['permissions'];
				if($usrId > 2 && !$acessoTotal)
				{
					// 127 = pesquisa de satisfação
					if (count($_SESSION['permissionsIds'])>0)
						$where=" AND m.id in (".implode(",",$_SESSION['permissionsIds']).") ";
					else
						$where=" AND m.id=-1 ";
				}
				//gD($_SESSION['permissionsLinks']);
				$sql = "SELECT m.*
							FROM gfw_menus m
							WHERE m.locale='" . $gLang . "' and m.active=1 $show $where
							ORDER BY m.order1, m.order2";
				$rsi = dbQuery($sql);

				$html = '';
                if (!is_array($flds)) {
                    $flds = [];
                }
				$flds['owner'] = '';
				$flds['title'] = gT('Menu');
				$ultimoTipo='';
				foreach ($rsi as $row) {
					$title = $row['title'];

					if ($row['title'] == '') {
						$sql = "SELECT m.*
								FROM gfw_menus m
								WHERE m.keyword='".$row['keyword']."' AND m.title ORDER BY m.id";
						$rst = dbQuery($sql);
						$title=$rst[0]['title'];
					}
					$link = trim($row['link']);
					// É um link?
					if (($link <> '') && (stripos($link, '.') === false) && (stripos($link, '/') === false)) {
						if ($row['file']<>'')
						{
							$link = $_SERVER["PHP_SELF"] . '?g=' . $link.'&'.$row['add_parameters'];
						} else
						{
							// Não... então é uma página do banco de dados
							$link = $_SERVER["PHP_SELF"] . '?g=open&p=' . $link.'&'.$row['add_parameters'];
						}
					}

					if (($row['type']=="dropdownLink") && ($link=='') && $ultimoTipo=='dropdownLink')
						$menu->add("{title: sep; type: separator; }");
					$menu->add("{title: " . urldecode($title) . "; type: " . $row['type'] . "; popover: " . $row['content'] . "; icon: " . $row['icon'] . "; url: " . $link . "}");
					$ultimoTipo=$row['type'];
				}
				$t=array();

				$t['admin']           ="Administrar";
				$t['home']            ="Início";
				$t['profile']         ="Perfil";
				$t['users']           ="Usuários";
				$t['locales']         ="Localizações";
				$t['pages']           ="Páginas";
				$t['menus']           ="Menu";
				$t['translations']    ="Traduções";
				$t['posts']           ="Artigos";
				$t['homepage']        ="Página inicial";
				$t['sign_in']         ="Entrar";
				$t['logout']          ="Sair";
				$t['permissions']     ="Permissões";
				$t['dashboard']       ="Painel de controle";
				$t['representatives'] ="Representantes";
				$t['links']           ="Links";
				$t['parameters']      ="Parâmetros";
				$t['tools']           ="Ferramentas";
				$t['activities']      = "Log de atividades";

				if ($usrId) {
					// Menu quando o usuário está logado
					$menu->add("{icon: cog; type: dropdown; hint: User options; }");
					$menu->add("{title: Início; type: dropdownLink; icon: home; url: index.php?g=index}");
					$menu->add("{title: Perfil; type: dropdownLink; icon: user; url: index.php?g=profile}");
					if (!$_SESSION['usrCliente'])
					{
						$menu->add("{title: Mensagens; type: dropdownLink; icon: envelope; url: index.php?g=messages}");
						$menu->add("{title: Manual; type: dropdownLink; icon: books; url: https://wiki.giusoft.com.br/pt-br/home;}");
					}
					if ($usrId == 1)
					{
						$menu->add("{title: sep; type: separator; }");
						$menu->add("{title: ".$t["admin"]."; type: dropdownLink; }");
						$menu->add("{title: ".$t['users']."; type: dropdownLink; icon: users; url: index.php?g=users}");
						$menu->add("{title: ".$t['homepage']."; type: dropdownLink; icon: home; url: index.php?g=home}");
						$menu->add("{title: ".$t['pages']."; type: dropdownLink; icon: file-alt; url: index.php?g=pages}");
						$menu->add("{title: ".$t['menus']."; type: dropdownLink; icon: align-justify; url: index.php?g=menus}");
						$menu->add("{title: ".$t['translations']."; type: dropdownLink; icon: comment; url: index.php?g=translations}");
						$menu->add("{title: ".$t['links']."; type: dropdownLink; icon: link; url: index.php?g=links}");
						$menu->add("{title: ".$t['posts']."; type: dropdownLink; icon: rss; url: index.php?g=posts}");
						$menu->add("{title: ".$t['tools']."; type: dropdownLink; icon: toolbox; url: index.php?g=tools}");
					}
					if ($usrId==1 || $usrId ==2 )
					{
						$menu->add("{title: ".$t['permissions']."; type: dropdownLink; icon: key; url: index.php?g=permissions}");
						$menu->add("{title: ".$t['parameters']."; type: dropdownLink; icon: wrench; url: index.php?g=parameters}");
						$menu->add("{title: ".$t['menus']."; type: dropdownLink; icon: align-justify; url: index.php?g=menus}");
						$menu->add("{title: ".$t['tools']."; type: dropdownLink; icon: toolbox; url: index.php?g=tools}");
					}
					if (!$_SESSION['usrCliente'])
					{
						$menu->add("{title: sep; type: separator; }");
						$menu->add("{title: ".$t['activities']."; type: dropdownLink; icon: list; url: index.php?g=activities}");
					}
					$menu->add("{title: sep; type: separator; }");
					$menu->add("{title: Sair; type: dropdownLink; icon: power-off; url: index.php?g=logout}");

				} else
				{
					// Menu padrão (offline)
					if ($_SERVER['SERVER_ADDR']=='::1' || $_SERVER['SERVER_ADDR']=='127.0.0.1' || substr($_SERVER['REMOTE_ADDR'],0,3)=='192')
					{
						$menu->add("{title: ".$t['sign_in']."; type: link; url: index.php?g=signin}");
					} else
					{
						$h = "https:";
						if (gVar("global.ssl")=='false')
						{
							$menu->add("{title: ".$t['sign_in']."; type: link; url: index.php?g=signin}");
						} else {
							$menu->add("{title: ".$t['sign_in']."; type: link; url: ".$h."://".$_SERVER['HTTP_HOST'].'/'.$gBASE."/index.php?g=signin}");
						}
					}
				}

				$sai.=$btns;
				gVar('global.translate', $translate);
				gVar('global.auto_translate', $autotranslate);


				if ($gDevice=="mobile")
				{
					$css='<style>@media (min-width: 768px) {body { padding-top: 0px; }} @media (max-width: 767px) {body { padding-top: 0px; }}</style>';
				} else {
					if ($usrId == 0 || $_SESSION['edicaoAtiva']==1)
					{
						$css='<style>@media (min-width: 768px) {body { padding-top: 77px; }} .navbar {min-height: 74px} .navbar-nav{margin: 14px} @media (max-width: 767px) {body {padding-top: 36px;} a.navbar-brand img {height: 25px; width: 50px} .navbar {min-height: 40px} .navbar-nav{margin: 0px}}</style>';
					} else
					{
						$css='<style>@media (min-width: 768px) {body { padding-top: 36px; }} @media (max-width: 767px) {body { padding-top: 36px; }}</style>';
					}
				}
				$sai=$css.$sai;

				$local='';
				if ($_SERVER['HTTP_HOST']=='localhost')
				{
					$local.=$o->label("LOCALHOST",'danger')."&nbsp;";
					$LOCALHOST=true;
				}
				if ($AMBIENTE_TESTE)
				{
					$local.=$o->label("TESTE",'danger');
					$LOCALHOST=false;
				}

				if ($usrId > 0 && $usrCliente==1)
				{
					$bar='<div id="barraDeAtalhos" class="btn-toolbar hidden-xs hidden-sm" role="toolbar" style="float: left">';
					$bar.='<div id="barraDeAtalhos" class="btn-group hidden-xs hidden-sm" role="group" style="padding-left: 6px; padding-right: 6px">';
					$bar.=$o->button("{size: small; icon: calendar; style: info; caption: Programação; href: index.php?g=programacoes&gPage=15}");
					// $bar.=$o->button("{size: small; icon: monitor-heart-rate; style: info; caption: Painel; href: index.php?g=painel}");
					$bar.=$o->button("{size: small; icon: box; style: info; caption: Saldo; href: index.php?g=saldos&gPage=0&gTipo=1}");
					$bar.=$o->button("{size: small; icon: barcode; style: info; caption: Saldo UMAs; href: index.php?g=umas&gPage=20}");

					$bar.='</div>';
					$bar.='</div>';
				} else if ($usrId>0 && $gDevice<>"mobile")
				{
					
					$bar='<div id="barraDeAtalhos" class="btn-toolbar" role="toolbar" style="float: left">';
					$bar.= '
					<div class="btn-group btn-group-sm" role="group" id="botaoArmazens">
					<button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.$_SESSION['armazemAtualDescricao'].'
					<span class="caret"></span>
					</button>
					<ul class="dropdown-menu">';
					$sql = "SELECT id, descricao FROM armazens";
					$rsa = dbQuery($sql);
					foreach ($rsa as $rowa)
					{
						$bar.='<li><a href="index.php?g=index&mudaArmazemPara='.$rowa['id'].'&mudarDescArmazemPara='.$rowa['descricao'].'">'.$rowa['descricao'].'</a></li>';
					}

					$bar.='</ul></div>';
					if (
						strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false
						|| strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
						|| strpos($_SERVER['HTTP_USER_AGENT'], 'Iphone') !== false //premissas sao validadas assim por causa do retorno da funcao strpos
					) {
						$js = '
							var botaoArmazens = document.querySelector("#botaoArmazens");
							var logo = document.querySelector(".navbar-brand");
							var barraAtalhos = document.getElementById("barraDeAtalhos");

							botaoArmazens.classList.add("navbar-brand");
							var copiaBotao = botaoArmazens.outerHTML;
							logo.insertAdjacentHTML("afterend", copiaBotao);

							barraAtalhos.style.display = "none";
						';
						$o->addJavascript($js);
					}

					$bar .= '<div id="barraDeAtalhos" class="btn-group hidden-xs hidden-sm" role="group" style="padding-left: 6px; padding-right: 6px">';
					$bar .= $o->button("{size: small; icon: clock; style: info; caption: Programação; href: index.php?g=programacao}");
					$bar .= $o->button("{size: small; icon: calendar; style: info; caption: Agenda; href: index.php?g=agenda}");
					$bar .= $o->button("{size: small; icon: tasks; style: info; caption: Eventos; href: index.php?g=admin_eventos}");
					$bar .= $o->button("{size: small; icon: barcode-read; style: info; caption: Informações; href: index.php?g=informacoes}");
					// $bar .= $o->button("{size: small; icon: monitor-heart-rate; style: info; caption: Painel; href: index.php?g=painel}");
					$bar .= $o->button("{size: small; icon: map; style: info; caption: Mapa; href: index.php?g=mapa_umas}");
					$bar .= $o->button("{size: small; icon: box; style: info; caption: Saldo; href: index.php?g=saldos&gPage=0&gTipo=1}");
					$bar .= $o->button("{size: small; icon: barcode; style: info; caption: Saldo UMAs; href: index.php?g=umas&gPage=20}");

					if ($_SESSION['key_user'] && $usrId) {
						$bar .= $o->button("{id: toggleChatBtn; size: small; icon: " . ($_SESSION['chatAtivo'] ? 'comment' : 'comment-slash') . "; style: info; caption: " . ($_SESSION['chatAtivo'] ? 'Ocultar Chats' : 'Exibir Chats') . "; href: 'javascript:void(0);'}");
					}

					$bar .= '</div>';
					$bar .= '</div>';
				}
				$sai .= $menu->render($o, $bar.$local)."<br><br>";

				if ($_SESSION['key_user'] && $usrId && !empty($_SESSION['chatAtivo'])) {
					iniciarChatwoot();
					chatBot();
				}

				$js = "
					document.addEventListener('DOMContentLoaded', function() {
						const toggleBtn = document.getElementById('toggleChatBtn');
						if (toggleBtn) {
							toggleBtn.addEventListener('click', function(e) {
								e.preventDefault();
								fetch('index.php?action=toggleChat', {
									method: 'POST',
									headers: {
										'Content-Type': 'application/x-www-form-urlencoded',
									},
								})
								.then(response => response.text())
								.then(data => {
									location.reload();
								})
								.catch(error => console.error('Erro ao alternar o chat:', error));
							});
						}
					});
				";
				$o->addJavascript($js);
			}
			$debug = $oldDebug;

		}
	}
	return($sai);
}


/**
 * Busca no banco de dados pela palavra chave, usando o idioma atual, e monta a página usando um template (em /static/tpl/)
 * @global type $o
 * @global type $gLang
 * @global type $usrId
 * @param integer $o Objeto gPortal
 * @param string $keyword Palavra chave da página a ser mostrada
 * @return string $content Conteúdo HTML gerado
 */
function createDBPage($keyword, $addContent = '', $lang = '')
{
	global $o,$gLang, $usrId;

	if ($lang == '')
		$lang=$gLang;

	$sql = "SELECT p.*, u.name user
			FROM gfw_pages p
			LEFT JOIN gfw_users u on p.idd=u.id
			WHERE p.keyword='" . $keyword . "'
			ORDER BY p.id";
	$rsi = dbQuery($sql);
	$html = '';
	$title = '';
	$flds = '';
	if (count($rsi) > 0) {
		foreach ($rsi as $row) {
			if ($row['content'] <> '') {
				$style = "info";
				if (($row['locale'] == $lang) || ($lang == '')) {
					$id=$row['id'];
					$locale = $row['locale'];
					$flds['title'] = '<a href="">'.$row['title']."</a>";
					$flds['owner'] = gDate($row['date_creation']) . " - " . $row['user'];
					$style = 'warning';
					$html=base64_decode($row['content']);
					if ($html === false)
						$html = $row['content'];
				}
			}
		}
	} else {
		$html = $o->h1('error_404') . $o->msgError(gT("error_404_message"));
	}

	//$flds['topMenu']=createMenu($o);
	if ($usrId>0)
		$content=createOnlinePage($html, $flds);
	else
		$content=createOfflinePage($html, $flds);
	$content.=$addContent;
	$content=gReplaceWikiMacros($content);
	$o->out($content);
}

/**
 * Monta uma página usando o modelo online
 * @global type $o Objeto gPortal
 * @param string $keyword Palavra chave da página a ser mostrada
 * @param array $fields Array associativo contendo macros adicionais para serem substituídas
 * @return string $content Conteúdo HTML gerado
 */
function createNormalPage($content, $fields = '')
{
	return(createPage($content, $fields, pageStyle('online-normal')));
}

/**
 * Monta uma página usando o modelo online
 * @global type $o Objeto gPortal
 * @param string $keyword Palavra chave da página a ser mostrada
 * @param array $fields Array associativo contendo macros adicionais para serem substituídas
 * @return string $content Conteúdo HTML gerado
 */
function createOnlinePage($content, $fields = '')
{
	return(createPage($content, $fields, pageStyle('online-fullpage')));
}

/**
 * Monta uma página usando o modelo online
 * @global type $o Objeto gPortal
 * @param string $keyword Palavra chave da página a ser mostrada
 * @param array $fields Array associativo contendo macros adicionais para serem substituídas
 * @return string $content Conteúdo HTML gerado
 */
function createOnlineIndexPage($content, $fields = '')
{
	return(createPage($content, $fields, pageStyle('online-index')));
}

/**
 * Monta uma página usando o modelo offline
 * @global type $o Objeto gPortal
 * @param string $keyword Palavra chave da página a ser mostrada
 * @param array $fields Array associativo contendo macros adicionais para serem substituídas
 * @return string $content Conteúdo HTML gerado
 */
function createOfflinePage($content, $fields = '')
{
	return(createPage($content, $fields, pageStyle('offline-fullpage')));
}

/**
 * Monta uma página usando o modelo offline
 * @global type $o Objeto gPortal
 * @param string $keyword Palavra chave da página a ser mostrada
 * @param array $fields Array associativo contendo macros adicionais para serem substituídas
 * @return string $content Conteúdo HTML gerado
 */
function createOfflineIndexPage($content, $fields = '')
{
	return(createPage($content, $fields, pageStyle('offline-index')));
}

function createIndexPage($content, $fields = '')
{
	if ($usrId>0)
		return(createOnlineIndexPage($content, $fields));
	else
		return(createOfflineIndexPage($content, $fields));

}


/**
 * Monta uma página usando um modelo
 * @global type $o Objeto gPortal
 * @param string $keyword Palavra chave da página a ser mostrada
 * @param array $fields Array associativo contendo macros adicionais para serem substituídas
 * @param string $template
 * @return string $content Conteúdo HTML gerado
 */
function createPage($content, $fields, $template)
{
    global $o, $usrId;

    if (!is_array($fields)) {
        $fields = [];
    }

    $fields['topMenu'] = createMenu($o);

    if ($usrId > 0 && $_REQUEST['p'] <> '') {
        $rs = dbFastQuery("SELECT * FROM gfw_pages WHERE keyword='" . $_REQUEST['p'] . "'");
        $fields['topMenu'] .= "<br>" . botaoEditar(
            "index.php?g=menu&gPage=1&gId=" . $rs[0]['id']
        );
    }

    $fields['bottomMenu'] = '';
    $fields['footer'] = '';

    return $o->n.template($template, $content, $fields);
}

/**
 * Retorna um código HTML completo para exibição de uma imagem
 * @global type $gBASE
 * @param integer $id Id do grupo
 * @param boolean $timer Se "true" inclui hora no tag da imagem, evitando ficar no cache do navegador
 * @return string $photo HTML para a imagem
 */
function tagImage($id = 0, $timer=false)
{
	global $gBASE, $gPathDefault;
	$imgPath = '/pub/img/upload/' . $id . ".jpg";
	$img='';
	if (file_exists(str_replace('//', '/', $gPathDefault . $imgPath))) {
		$img = '/' . $gBASE . $imgPath;
	}
	$img = str_replace('//', '/', $img);
	if ($timer)
		$img.="?".date("His");

	if ($img <> '') {
		$photo = '<img src="' . $img . '" class="img-responsive">';
	}
	return($photo);
}


function botaoEditar($link)
{
	global $usrId;
	if ($usrId) {
		return '<div class="text-center"><a role="button" class="btn btn-default btn-lg" href="' . $link . '"><span class="fal fa-pencil"></span> Editar</a></div>';
	}
	return '';
}

function paginaInterna()
{
	global $usrId, $html, $o;

	if ($usrId>0)
	{
		$html.=$o->msgError("Esta é uma página interna do sistema só pode ser modificada pela equipe da GiuSoft.");
	}
}


/**
 * Retorna um código HTML com a estrutura (esqueleto) da página conforme o modelo escolhido
 * @global type $gBASE
 * @param string $type Tipo de esqueleto. Pode ser: offline-index, offline-fullpage, online-fullpage, online-index, online-normal
 * @return string $html Esqueleto da página em HTML
 */
function pageStyle($type)
{
	$pageStyles['offline-index']='
@topMenu

<!-- Dinamic content / START -->

<div id="bodyContent" class="container">
@content
</div>

<!-- Dinamic content / END -->

@footer
@bottomMenu
';

	$pageStyles['offline-fullpage']='
@topMenu
<!-- Title / START -->

<div class="container">
	<div class="page-header">
		<h1>@title</h1>
	</div>
	<small>@owner</small>
	<Br>&nbsp;<br>
</div>

<!-- Title / START -->


<!-- Dinamic content / START -->

<div class="container">
@content
</div>

<!-- Dinamic content / END -->

@footer
@bottomMenu';

	$pageStyles['online-index']='
@topMenu

<!-- Dinamic content / START -->

<div class="container">

@content
</div>

<!-- Dinamic content / END -->



@footer
@bottomMenu
';

	$pageStyles['online-fullpage']='
@topMenu
<!-- Title / START -->

<div class="container">
	<div class="page-header">
		<h1>@title</h1>
	</div>
	<small>@owner</small>
	<Br>&nbsp;<br>

</div>

<!-- Title / START -->

<!-- Dinamic content / START -->

<div class="container">
@content
</div>

<!-- Dinamic content / END -->

@footer
@bottomMenu
';

	$pageStyles['online-normal']='
@topMenu
<!-- Dinamic content / START -->

<div style="margin: 8px">
@content
</div>

<!-- Dinamic content / END -->

@footer
@bottomMenu
';

	return $pageStyles[$type];
}


function editarOuNovo()
{
	return $_REQUEST['gIdEnd'];
}


function formataUMA($uma)
{
	global $gParam;

	$digitosDaUma = ($gParam['DIGITOS_UMA']['ativo']) ? (int) $gParam['DIGITOS_UMA']['valor'] : 12;

	if ($gParam['USA_POSICAO_COMO_UMA']['ativo']) {
		return str_replace('UMA', '', $uma);
	}

	if ($uma<>"" && !strpos($uma, '.'))
	{
		if (is_numeric($uma) && $uma > 0) {
			return "UMA" . str_pad($uma, $digitosDaUma,'0', STR_PAD_LEFT);
		}

		if ((int) substr($uma, 3) > 0) {
			if (strlen($uma)<=$digitosDaUma+5) {
				return "UMA" . str_pad(intval(substr($uma,3)), $digitosDaUma,'0', STR_PAD_LEFT);
			} else {
				return strtoupper($uma);
			}
		}
	}

	return NULL;
}


function desformataUMA($uma)
{
	global $gParam;
	if ($gParam['USA_POSICAO_COMO_UMA']['ativo']) {
		return str_replace('UMA', '', $uma);
	}

	if (!is_numeric($uma)) {
		$uma = substr($uma, 3);
	}
	return (int) $uma;
}

function formataDataSemSeparadores($dataTxt)
{
	$dataTxt=str_replace(['-'],'',$dataTxt);
	$ano = "20" . substr($dataTxt,4,2);
	$mes = substr($dataTxt,2,2);
	$dia = substr($dataTxt,0,2);
	if ($dataTxt<>"" && checkdate(((int) $mes), ((int) $dia), ((int) $ano))) {
		return $ano ."-".$mes."-".substr($dataTxt,0,2);
	}
	return "0000-00-00";
}

function formataDataTirandoSeparadores($dataDB)
{
	return($dataDB<>"0000-00-00" ? substr($dataDB,8,2).substr($dataDB,5,2).substr($dataDB,2,2) : "");
}


function calculaSituacaoVeiculo($veiculo)
{
	$situacaoVeiculo = "Aguardando...";
	if ($veiculo['data_chegada']<>'0000-00-00 00:00:00')
	{
		$situacaoVeiculo = "Veículo chegou";
	}
	if ($veiculo['data_entrada']<>'0000-00-00 00:00:00')
	{
		$situacaoVeiculo = "Veículo entrou";
		if ($row['executada']==1)
		{
			if ($veiculo['data_autorizacao_saida']=='0000-00-00 00:00:00')
			{
				$situacaoVeiculo = "Aguardando autorização saída";
			} else {
				$situacaoVeiculo = "Aguardando saída";
			}

		}
		if ($veiculo['data_saida']<>'0000-00-00 00:00:00')
		{
			$situacaoVeiculo = "Veículo já saiu";
		}
	} else {
		if ($veiculo['data_autorizacao_entrada']=='0000-00-00 00:00:00')
		{
			$situacaoVeiculo = "Aguardando autorização entrada";
		} else {
			$situacaoVeiculo = "Aguardando entrada";
		}
	}
	return($situacaoVeiculo);
}


function estaImobilizada($umaFormatada)
{
	$sql   = "SELECT imobilizada FROM umas WHERE codigo_barras = '{$umaFormatada}' LIMIT 1";
	return (bool) dbFastQuery($sql)[0]['imobilizada'];
}


function formatarParaCabecalho($titulo, $dado)
{
	global $o;
	$dado = $dado ?: 'Indefinido';
	return $o->small($titulo) . '<br><b>' . $dado . '</b>&nbsp;';
}


function atualizarAtivacaoUMA()
{
	global $gParam;

	$idUmas = array_filter(func_get_args(), function($idUma) {
		return $idUma > 0;
	});

	if (!$idUmas) {
		return;
	}

	if (is_array($idUmas[0]) && !func_get_args()[1]) {
		$idUmas = array_filter($idUmas[0]);
	}

	$idUmas = implode(', ', $idUmas);
	if (!$idUmas) {
		return;
	}

	$sql = "
		SELECT
			umas.id,
			umas.ativo,
			umas.id_posicoes,
			IFNULL(SUM(umas_itens.quantidade), 0) quantidade
		FROM
			umas
		LEFT JOIN umas_itens ON (
			umas_itens.id_umas = umas.id
			AND umas_itens.cancelada = 0
		)
		WHERE
			umas.id IN ({$idUmas})
			AND EXISTS (SELECT 1 FROM umas_itens WHERE id_umas = umas.id LIMIT 1)
		GROUP BY umas.id";
	$umas  = dbFastQuery($sql);

	if (!$umas[0]['id']) {
		return;
	}

	$umasDesativar = '';
	$umasAtivar = '';

	foreach ($umas as $uma) {
		$idUmas = $uma['id'] . ',';
		if ($uma['quantidade'] == 0 && $uma['ativo']) {
			$umasDesativar .= $idUmas;
			continue;
		}

		if ($uma['quantidade'] != 0 && !$uma['ativo']) {
			$umasAtivar .= $idUmas;
			continue;
		}
	}

	$umasDesativar = substr($umasDesativar, 0, -1);
	$umasAtivar = substr($umasAtivar, 0, -1);

	if ($umasDesativar) {
		$sql = "UPDATE umas SET data_desativacao = NOW(), ativo = 0, posicionada = 0 WHERE id IN ({$umasDesativar})";
		dbFastQuery($sql);

		$sql = "INSERT INTO log_umas (id_umas, data, ativo)
				SELECT id, NOW(), 0 FROM umas WHERE id IN ({$umasDesativar})";
		dbFastQuery($sql);
	}

	if ($umasAtivar) {
		$sql = "UPDATE umas SET data_desativacao = '0000-00-00 00:00:00', ativo = 1, posicionada = (id_posicoes > 0) WHERE id IN ({$umasAtivar})";
		dbFastQuery($sql);

		$sql = "INSERT INTO log_umas (id_umas, data, ativo)
				SELECT id, NOW(), 1 FROM umas WHERE id IN ({$umasAtivar})";
		dbFastQuery($sql);
	}
}


/*BLOQUEAR COLAGEM DOS CAMPOS TIPO BARCODE NO COLETOR*/
$GLOBALS['pasteInBarcode'] = 1;
if ($GLOBALS['gParam']['BLOQUEAR_COLAGEM_NO_COLETOR']['ativo']) {
	$GLOBALS['pasteInBarcode'] = !(
		$_SESSION['usrId'] > 2 &&
		preg_match(
				"/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i",
				$_SERVER["HTTP_USER_AGENT"])
	);
}

if ($gParam['INTEGRACAO_WINTHOR']['ativo']) {
	define('ID_GA', 3098);
	define('ID_VETBR', 3384);
	$gParam['INTEGRACAO_WINTHOR']['valor'] = explode(',', $gParam['INTEGRACAO_WINTHOR']['valor']);
}


function chatBot()
{
	if (
		$_REQUEST['gXLS']
		|| $_REQUEST['gXML']
		|| $_REQUEST['gPDF']
		|| $_REQUEST['gDOC']
		|| $_REQUEST['gCSV']
	) return;

	if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
	    $ambiente = '/teste';
	}

	include_once $_SERVER['DOCUMENT_ROOT'] . $ambiente . '/emitenotaweb/giusoft/res/system/chatbot/chat.php';
}


function iniciarChatwoot() {
	global $o, $usrId, $gDevice;

	if (
		$_SESSION['key_user']
		&& $_REQUEST['g']
		&& !$_REQUEST['gXLS']
		&& !$_REQUEST['gXML']
		&& !$_REQUEST['gPDF']
		&& !$_REQUEST['gDOC']
		&& !$_REQUEST['gCSV']
		&& $gDevice == "mobile"
	) return;

	$nomeUsuario = $_SESSION['usrName']
		. ' - WMS - ' . $GLOBALS['EMPRESA']
		. '/ARM ' .	$_SESSION['armazemAtualDescricao'];

	$js = "
		var nome = '" . $nomeUsuario . "';
		var usrId = '" . $usrId . '_' . $GLOBALS['EMPRESA'] . "';
		(function(d,t) {
			let BASE_URL = 'https://chat.giusoft.com.br/';
			let g = d.createElement(t),s=d.getElementsByTagName(t)[0];
			g.src = BASE_URL+'/packs/js/sdk.js';
			g.defer = true;
			g.async = true;
			s.parentNode.insertBefore(g,s);
			g.onload=function(){
				window.chatwootSDK.run({
				websiteToken: 'WjnqcBFScD659F25CpPAsgqa',
				baseUrl: BASE_URL
			});
			var interval = setInterval(function() {
				if (window.\$chatwoot && window.\$chatwoot.setUser) {
					window.\$chatwoot.setUser(usrId, {
					    email: '',
						name: nome,
						avatar_url: '',
						phone_number: '',
						});

					clearInterval(interval);
				}
			}, 500);
		}
		})(document,'script');
	";

	$o->addJavascript($js);
}

$AESKEY = "gWms";
function dispararGatilho($momento, $dados) {
	global $EMPRESA;
	if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
	    $ambiente = '/teste';
	}
	require_once $_SERVER["DOCUMENT_ROOT"] . $ambiente . "/wms/giusoft/res/api/accesspoint.php";
	$persistencia = new PontoAcesso(['empresa' => $EMPRESA]);

	$resultado = $persistencia->acionarEventoMomento($momento, $dados);

	if ($dados['temRetorno']) {
		return $resultado;
	}

}

/* Verifica se o nome ou apelido do usuário é reservado e retorna erro se for, permitindo apenas nomes válidos */
function verificarNomeOuApelidoReservado()
{
	global $usrId;

	if ($usrId <= 2) {
		return;
	}

    $nome    = strtolower(gCleanField($_REQUEST['nome']));
    $apelido = strtolower(gCleanField($_REQUEST['apelido']));

    $nomesReservados    = array("administrador", "admin", "suporte gs", "root");
    $apelidosReservados = array("admin", "root");

	$erros = array();
    if (in_array($nome, $nomesReservados)) {
        $erros[] = "Você não pode usar o nome '" . $_REQUEST['nome'] . "'. Por favor, escolha outro nome.";
    }

    if (in_array($apelido, $apelidosReservados)) {
        $erros[] = "Você não pode usar o apelido '" . $_REQUEST['apelido'] . "'. Por favor, escolha outro apelido.";
    }

	if ($erros) {
		return $erros;
	}

    return false;
}