<?php
include 'setup.php';
date_default_timezone_set("America/Bahia");
session_name("emitenota_".$EMPRESA);
session_start();
$gIncludes = array('gPortal.php', 'gEmail.php');

require_once 'gfw/inc/gStart.php';
// Variáveis principais passadas via GET
$g 			= gCleanField($_REQUEST['g']);
echo '<pre>';var_dump(232);exit;
$p 			= gCleanField($_REQUEST['p']);
$usrId 		= intval($_SESSION['usrId']);
$gPage		= intval($_REQUEST['gPage']);
$gId 		= intval($_REQUEST['gId']);
$gIdEnd		= intval($_REQUEST['gIdEnd']);

// Tratamentos especiais
$debug 	= 'true';
$data 	= date('Y-m-d H:i:s');
$macros	= '';
$do 	= true;

$id_armazens = (int) $_POST['id_armazens'] ?: 1; // Armazém padrão
$oldDebug = $debug;
$debug = false;

// Sempre atualiza os parâmetros
$sql="SELECT * FROM parametros";
$rsp=dbQuery($sql);
$gParam='';
foreach ($rsp as $key=>$value)
{
	unset($value[0]);
	unset($value[1]);
	unset($value[2]);
	unset($value[3]);
	unset($value[4]);
	unset($value[5]);
	$gParam[$value['chave']]=$value;
}

// Verificando se o acesso está bloqueado
if ($usrId>2 && $g<>'login' && $g<>'logout' && $gParam['ACESSO']['ativo']==0)
{
	$g="logout";
}

// Instanciando o objeto principal
if ($_REQUEST['gAjs']==1)
	$o = new gPortal('{debug: on; onlyBody: on}');
else
{

	$o = new gPortal('{debug: on}');
}
if ($gDevice=="mobile")
{
	include 'gfw/inc/gMinimal.php';
	$o = new gMinimal\gOutput();
	//$o->out('<link href="' . $http_css .'styleMinimal.css" rel="stylesheet">'."\n", gLOC_PRE);
	if ($usrId>2 && $g<>'login' && $g<>'logout' && $gParam['ACESSOCOL']['ativo']==0)
	{
		$g="logout";
		$gParam['ACESSO']['ativo']=0;
	}
} else {
	if ($usrId>2 && $g<>'login' && $g<>'logout' && $gParam['ACESSOWEB']['ativo']==0)
	{
		$g="logout";
		$gParam['ACESSO']['ativo']=0;
	}
}

if ($_SESSION['gTheme']<>'' && $gId>0){
	gVar("global.theme",$_SESSION['gTheme']);
	gVar("global.bodyfont",$_SESSION['gFontBody']);
	gVar("global.headersfont",$_SESSION['gFontHeaders']);
} else
{
	// Parametrizando o tema
	$rst=dbFastQuery("SELECT * FROM pessoas WHERE id=2");
	if ($rst[0]['theme']!="")
	{
		$_SESSION['gTheme']=$rst[0]['tema'];
		gVar("global.theme",$rst[0]['tema']);
	}
	if ($rst[0]['fonte_padrao']!="")
	{
		$_SESSION['gFontBody']=$rst[0]['fonte_padrao'];
		gVar("global.bodyfont",$rst[0]['fonte_padrao']);
	}
	if ($rst[0]['fonte_titulos']!="")
	{
		$_SESSION['gFontHeaders']=$rst[0]['fonte_titulos'];
		gVar("global.headersfont",$rst[0]['fonte_titulos']);
	}

}
// Funcionalidades específicas deste projeto
include_once 'res/global.php';

if ($gLang == "en") {
	gVar("global.dateformat", "mm-dd-yy");
	gVar("global.numformat", "0,000.00");
}

if ($_REQUEST['mudaArmazemPara']<>'')
{
	$_SESSION['armazemAtualId'] = intval($_REQUEST['mudaArmazemPara']);
	$_SESSION['armazemAtualDescricao'] = $_REQUEST['mudarDescArmazemPara'];
}

if ($_REQUEST['action'] == 'toggleChat') {
	$_SESSION['chatAtivo'] = (int) !$_SESSION['chatAtivo'];
	echo $_SESSION['chatAtivo'];
	exit;
}

$o->begin();

// ========================================== Login

if (($g == "login")) {

	if (isBruteForce() && false){
		$g="ip_locked";
	} else
	{
		$email = gCleanField($_REQUEST['email']);
		$senha = gCleanField($_REQUEST['password']);
		$equipamento = gCleanField($_REQUEST['equipamento']);

		if ($email=='' || $senha=='')
			$senha='senhaNaoInformada';
		$sql = "SELECT u.* FROM pessoas u WHERE u.situacao='Ativo' and (u.email='$email' or u.apelido='$email') and (u.senha='" . md5($senha) . "' or u.senha='" . $senha . "') LIMIT 1";
		$rs = dbFastQuery($sql);

		$acessoAoEquipamento = true;
		if ($rs[0]['id']) {
			if ($gParam['RESTRINGIR_ACESSO_POR_IP']['ativo']) {
				$ipsLiberados = dbFastQuery("SELECT ips FROM armazens WHERE id = {$id_armazens} LIMIT 1")[0]['ips'];
				if (
					!$rs[0]['acesso_remoto']
					&& $rs[0]['id'] >= 2
					&& stripos($ipsLiberados, getIpUser()) == false
				) {
					if (ini_get("session.use_cookies")) {
						$params = session_get_cookie_params();
						setcookie(session_name(), '', time() - 42000,
							$params["path"], $params["domain"],
							$params["secure"], $params["httponly"]
						);
					}
					session_destroy();

					dbFastQuery("
						INSERT INTO gfw_access (idd, date, try, ip, details)
						VALUES (" . $rs[0]['id'] . ", NOW(), 1, '" . getIpUser() . "',
							'Email: {$email} / Password: {$password} / Equip: {$equipamento}')");
					$txt  .= $o->msgTitle('Acesso');
					$txt  .= $o->msgError('IP não liberado. Solicite informações da TI para liberação.');
					$html .= $o->rowTags($txt);

					if ($do) {
						$o->out(createIndexPage($html));
						$o->out($footer, gLOC_INLINE, 999);
					}

					$o->end();

					return;
				}
			}


			// Se informou o equipamento, verifica se a pessoa tem acesso
			$usrEquip = 0;
			$usrEquipName = '';
			if ($equipamento<>"")
			{
				$acessoAoEquipamento = false;
				if (intval($rs[0]['id'])<=2)
				{
					$sql = "SELECT E.*
							FROM equipamentos E
							LEFT JOIN equipamentos_pessoas EP ON E.id=EP.id_equipamentos
							WHERE E.codigo_barras='".$equipamento."'";

				} else {
					$sql = "SELECT E.*
							FROM equipamentos E
							LEFT JOIN equipamentos_pessoas EP ON E.id=EP.id_equipamentos
							WHERE EP.id_pessoas=".intval($rs[0]['id'])." AND E.codigo_barras='".$equipamento."'";
				}
				$rst = dbQuery($sql);
				if (count($rst)>0)
				{
					$usrEquip = $rst[0]['id'];
					$usrEquipName = $rst[0]['descricao'];
					$acessoAoEquipamento = true;
				}
			}

			if ($acessoAoEquipamento)
			{
				$usrId = (int) $rs[0]['id'];
				$usrIdd = $usrId;
				$usrName = $rs[0]['nome'];
				$usrNickname = $rs[0]['apelido'];
				$usrCliente=$rs[0]["cliente"];
				$usrEmail = $rs[0]['email'];
				$usrDate = date("Y-m-d H:i:s");
				$_SESSION['defaultTimezone'] = "America/Bahia";
				$_SESSION['modalPicking']=true;
				$_SESSION['usrId'] = $usrId;
				$_SESSION['usrIdd'] = $usrIdd;
				$_SESSION['usrAdmin'] = $usrAdmin;
				$_SESSION['usrName'] = $usrName;
				$_SESSION['usrEquip'] = $usrEquip;
				$_SESSION['usrEquipName'] = $usrEquipName;
				$_SESSION['usrNickname'] = ucfirst($usrNickname);
				$_SESSION['usrEmail'] = $usrEmail;
				$_SESSION['usrDate'] = $usrDate;
				$_SESSION['usrCliente'] = intval($rs[0]['cliente']);
				$_SESSION['usrClient'] = intval($rs[0]['cliente']);
				$_SESSION['usrFuncionario'] = intval($rs[0]['funcionario']);
				$_SESSION['usrMotorista'] = intval($rs[0]['motorista']);
				$_SESSION['usrFornecedor'] = intval($rs[0]['fornecedor']);
				$_SESSION['gLang'] = "pt_BR";
				$_SESSION['key_user'] = ($rs[0]['key_user'] || $usrId == 1);

				//Armazens
				$usrArmazens = "";
				$sql="SELECT id_armazens FROM pessoas_armazens WHERE id_pessoas=$usrId AND cancelado=0";
				if($id_armazens > 0)
					$sql.=" AND id_armazens=".(int) $id_armazens;
				//echo $sql;exit;
				$rsA= dbQuery($sql);
				foreach ($rsA as $row) {
					$usrArmazens[] = $row['id_armazens'];
				}

				// equipamentos
				// Operador
				$sql = "SELECT E.*
						FROM equipamentos E
						INNER JOIN equipamentos_pessoas EP ON E.id=EP.id_equipamentos
						WHERE E.id=".$usrEquip;
				$rse = dbQuery($sql);
				$_SESSION['empilhadeira'] = $rse[0];

				// Operador
				$sql = "SELECT EP.*
						FROM equipamentos_posicoes EP
						WHERE EP.id_equipamentos=".$usrEquip;
				$rse = dbQuery($sql);
				$_SESSION['empilhadeiraPosicoes'] = $rse;

				// Definindo armazem
				if ($usrCliente==1)
				{
					$sql="SELECT
							A.id, A.descricao
						  FROM pessoas_armazens PA
						  LEFT JOIN armazens A ON A.id = PA.id_armazens
						  WHERE id_pessoas=".intval($_SESSION["usrId"]);
					$amz=dbQuery($sql);
				} else
				{
					if($id_armazens > 0)
						$amz=dbQuery("SELECT * FROM armazens WHERE id = $id_armazens");
					else
						$amz=dbQuery("SELECT * FROM armazens LIMIT 1");
				}

				$_SESSION['armazemAtualId'] = $amz[0]["id"];
				$_SESSION['armazemAtualDescricao'] = $amz[0]["descricao"];

				// echo "<pre>";
				// var_dump($id_armazens,$amz);exit;


				//Permissões
				$sql="SELECT * FROM gfw_menus WHERE active=1 AND locale='".$gLang."' ORDER BY order1 desc, order2 desc";
				$todosLinks=dbQuery($sql);

				$sql="SELECT DISTINCT p.id, p.name
						FROM gfw_permissions_users u
						inner join gfw_permissions p on p.id=u.id_gfw_permissions
						WHERE u.id_gfw_users=$usrId
					";
				$rs= dbQuery($sql);
				foreach ($rs as $field)
				{
					$permissionsIdNames[$field['id']]=$field['id'];
					$permissionsNames[$field['id']]=$field['name'];
				}
				$sql="SELECT l.id_gfw_menus, m.*
						FROM gfw_permissions_users u
						inner join gfw_permissions p on p.id=u.id_gfw_permissions
						inner join gfw_permissions_links l on l.id_gfw_permissions=p.id
						INNER JOIN gfw_menus m on l.id_gfw_menus=m.id
						WHERE u.id_gfw_users=$usrId
					";
				$rs= dbQuery($sql);
				$perms=array();
				$links=array();
				foreach ($rs as $field)
				{
					$id_gfw_menus=$field['id_gfw_menus'];
					$perms[]=$id_gfw_menus;
					if ($field['link']<>'')
						$links[$field['link']]=$field['link'];
					foreach ($todosLinks as $linkAtual)
					{
						$subNivel=false;
						if ($linkAtual['id']==$id_gfw_menus)
						{
							foreach ($todosLinks as $umLink)
							{
								if ($linkAtual['order1']==$umLink['order1'])
								{
									if ($umLink['order2']==0)
									{
										$perms[]=$umLink['id'];
										if ($umLink['link']<>'')
											$links[$umLink['link']]=$umLink['link'];
									}
									if (($umLink['order2']<$linkAtual['order2']) && (!$subNivel))
									{
										if ($umLink['link']=='')
										{
											$perms[]=$umLink['id'];
											$links[$umLink['link']]=$umLink['link'];
											$subNivel=true;
										}
									}
								}
							}
						}
					}

				}

				$_SESSION['permissionsIds']=$perms;
				$_SESSION['permissionsLinks']=$links;
				$_SESSION['permissionsIdNames']=$permissionsIdNames;
				$_SESSION['permissionsNames']=$permissionsNames;
				include_once $gPathDefault."tr/".$gLang.".php";

				dbQuery("INSERT INTO gfw_access (idd, date, login, ip) values ($usrId,'" . date("Y-m-d H:i:s") . "', 1, '".$_SERVER["REMOTE_ADDR"]."')");

				dbQuery("INSERT INTO gfw_log
					(id_gfw_users,id_equip, id_gfw_menus,date,details) VALUES
					($usrId, $usrEquip, 5,'".date("Y-m-d H:i:s")."','Login')");

			} else {
				$idd = 0;
				$g = "login_error";
				dbQuery("INSERT INTO gfw_access (idd, date, try, ip, details) values ($idd,'" . date("Y-m-d H:i:s") . "', 1, '".$_SERVER["REMOTE_ADDR"]."', 'Email: " . $email . " / Password: " . $password . " / Equip: ".$equipamento."')");
			}


		} else {
			$idd = 0;
			$g = "login_error";
			dbQuery("INSERT INTO gfw_access (idd, date, try, ip, details) values ($idd,'" . date("Y-m-d H:i:s") . "', 1, '".$_SERVER["REMOTE_ADDR"]."', 'Email: " . $email . " / Password: " . $password . " / Equip: ".$equipamento. "')");
		}
	}
}



// ========================================== ONLINE Interface
if ($usrId > 0) {
	if ($g == "logout") {
		dbQuery("INSERT INTO gfw_access (idd, date, logout, ip, details) values ($usrId,'" . date("Y-m-d H:i:s") . "', 1, '".$_SERVER["REMOTE_ADDR"]."', 'Email: ".$usrEmail." / usrId: ".$usrId." / equip: ".$usrEquip."')");
		unset($_SESSION["usrId"]);
		unset($_SESSION["permissionsIds"]);
		unset($_SESSION["permissionsIdNames"]);
		unset($_SESSION["permissionsNames"]);
		unset($_SESSION["permissionsLinks"]);
		unset($_SESSION["key_user"]);

		unset($_SESSION["gMenu"]);
		echo "<script>
			localStorage.clear();
			window.location.href = 'index.php';
		</script>";
		exit;

	} else {

		if (($g == '') || ($g == 'signin') || ($g == 'login')) {
			$g = "index";
			$_REQUEST['g']=$g;
		}

		/*

		Mecanismo de segurança de acesso

		Quando o usuário chama pelo navegador o link "index.php?g=linkQualquer"
		o sistema busca neste array associativo ($pag) se existe a chave (parâmetro g)
		o valor é o link real. Portanto, todas as páginas tem que estar neste array

		*/

		$pag = '';
		// Básicos
		$pag['index']        = "index.php";
		$pag['profile']      = "system/profile.php";
		$pag['logout']       = "index.php";

		// Admin
		$pag['users']        = "system/users.php";
		$pag['translations'] = "system/translations.php";
		$pag['pages']        = "system/pages.php";
		$pag['menus']        = "system/menus.php";
		$pag['links']        = "system/links.php";
		$pag['locales']      = "system/locales.php";
		$pag['posts']        = "system/posts.php";
		$pag['home']         = "system/home.php";
		$pag['messages']     = "system/messages.php";
		$pag['permissions']  = "system/permissions.php";
		$pag['parameters']   = "system/parameters.php";
		$pag['activities']   = "system/activities.php";
		$pag['tools']        = "system/tools.php";

		if ($_REQUEST['pp'] <> '') {
			$html.='<div class="container">';
			$html.=gPosts($o);
			$html.='</div>';
		} elseif ($pag[$g] <> '') {
			// Acessando um link
			$link = "res/" . $pag[$g];
			if($gDevice<>"mobile")
				include ($link);
		} elseif ($g == 'open') {
			// Montando a página a partir do banco de dados (gerenciador de conteúdo)
			$do = false;
			createDBPage($p);
		} else {
			// Checa permissão
			$acessoTotal = false;
			if ($usrId <= 2) {
				$acessoTotal = true;
			} else {
				$acessoTotal = in_array(1, $_SESSION['permissionsIdNames']);
			}

			$links = $_SESSION['permissionsLinks'];

			if ((!empty($links[$g])) || ($acessoTotal))
			{
				// Identifica quando a página é em php através do BD (campo arquivo)
				$rs=dbQuery("SELECT * FROM gfw_menus WHERE link='".$g."'");
				$debug = $oldDebug;
				if (count($rs))
				{
					gLog("---------");
					$link = "res/" . $rs[0]['file'];
					$arq=str_replace('//','/',$gPath . '/' . $link);
					$gMenuParameters['id_gfw_menus'] = $rs[0]['id'];
					$gMenuParameters['full_link'] = $arq;
					if (file_exists($arq))
					{
						$t = explode('/', $rs[0]['file']);
						$classe = "res/_classes/padrao/".$t[0].".php";
						if (file_exists($classe))
						{
							include ($classe);
						}
						include ($link);
					} else
					{
						$html.=$o->msgTitle('error_404');
						$html.=$o->msgError("error_404_message");
					}
				} else
				{
					$html.=$o->msgTitle('error_404');
					$html.=$o->msgError("error_404_message");
				}
			} else
			{
				$html.=$o->msgTitle('error');
				$html.=$o->msgError("error_access_denied");
			}
		}
		if ($do) {
			if ($_REQUEST['gAjs']==1)
				$o->out($html);
			else
			{
				$o->out(createNormalPage($html, $macros));
			}

		}
	}
} else {





// ========================================== OFFLINE Interface

	$page = 'offline-fullpage.html';
	$o->out('<meta name="robots" content="index, follow">',gLOC_PRE);
	if ($g=='')
		$g='signin';
	$msg = '';
	switch($g) {

		case "ip_locked":
			$txt.=$o->msgTitle('Acesso');
			$txt.=$o->msgError('IP bloqueado. Solicite informações da TI para liberação.');
			$html.=$o->rowTags($txt);
			dbFastQuery("
				INSERT INTO gfw_access (idd, date, try, ip, details)
				VALUES (0, NOW(), 1, '" . getIpUser() . "',
					'LK Email: {$email} / Password: {$password} / Equip: {$equipamento}')");
			break;

		case "login_error":
			$msg=$o->msgError('Apelido, senha ou equip. incorreto!');
		case "signin":
			// Purge de usuários pendentes antigos sem ativação
//			$sql = "DELETE FROM gfw_users where active=0 and date_signon<'" . date('Y-m-d H:i:s', strtotime("-1 week")) . "'";
//			dbQuery($sql);
			//echo " = >".$gDevice;exit;
			$o->setRowLayout();

			// Login
			if($gDevice<>"mobile")
			{
				$signin=$msg;
				$campoExtra .= '<div class="form-group">' . $o->n;
				$campoExtra .= '<label for="equipamento">' . gT("Equipamento") . '</label>';
				$campoExtra .= '<input type="text" id="equipamento" class="form-control" placeholder="' . gT("E-mail") . '" autofocus>' . $o->n;
				$campoExtra .= '</div>' . $o->n;

				$campoExtra = "{name: equipamento; type: text; allowBlank: true; hint: Equipamento; fieldLabel: Equipamento}";
				$signin.= $o->login("{url: index.php?g=login; forceSubmit: true; style: inline; esqueceuSenha:false; loginMethod:nickname;armazens:true}", $campoExtra);
			}
			else
			{
				$signin='<style>body {background-color: black; color: white}</style>';
				$signin.='<div style="text-align: center; background-color: black"><img width="100%" src="pub/img/login.jpg"></div>';
				$signin.=$msg;
				$frm=new gMinimal\gForm("{ name: login; action: index.php?g=login;}");
				$frm->add("{fieldLabel: APELIDO ; type: text; name:email}");
				$frm->add("{fieldLabel: SENHA; type: text; inputType:password; name:password;}");
				$sql="SELECT id,descricao FROM armazens ORDER BY descricao";
				$rsa=dbQuery($sql);
				if(count($rsa)>1)
					$frm->add("{fieldLabel: UNIDADE; type:combo; name:id_armazens; items:$sql}");
				$frm->add("{fieldLabel: EQUIPAMENTO ; type: text; name:equipamento}");
				$signin.=$frm->render();
			}



			$tag='<div class="row">';
			$tag.='<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">' . $signin . '</div>';
			//$tag.='<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">' . $signon . '</div>';
			$tag.='</div>';
			if($gDevice<>"mobile")
			{
				$html=$o->msgTitle('sign_in');
			}
			$html.=tagMe('div',$tag,'class="container"');
			break;

		case 'open':
			createDBPage($p);
			$do = false;
			break;

		case 'search':
			include ("res/search.php");
			break;

		case 'index':


// ========================================== Página inicial / START
			$menu=createMenu($o);
			if ($_REQUEST['pp']<>'')
			{
				$html.='<div class="container">';
				$html.=gPosts($o);
				$html.='</div>';
			} else
			{
			}

			$o->out($menu,gLOC_INLINE,999);
			$o->out($html,gLOC_INLINE,999);
			$do=false;
// ========================================== Página inicial / END
			break;
		default:
			// Identifica quando a página é em php através do BD (campo arquivo)
			$rs=dbQuery("SELECT * FROM gfw_menus WHERE link='".$g."' and show_at<2");

			if (count($rs))
			{
				$link = "res/" . $rs[0]['file'];
				$arq=str_replace('//','/',$gPath . '/' . $link);
				if (file_exists($arq))
					include ($link);
				else
				{
					$html.=$o->rowTags($o->msgTitle('error_404'));
					$html.=$o->rowTags($o->msgError("error_404_message"));
				}
			} else
			{
				$html.=$o->rowTags($o->msgTitle('error_404'));
				$html.=$o->rowTags($o->msgError("error_404_message"));
			}
		break;
	}

if ($do)
	$o->out(createIndexPage($html, $macros));

	$o->out($footer,gLOC_INLINE,999);
}


// Rodapé
$o->end();




function isBruteForce(){
	global $docRoot, $gPath;
	$sai=false;
	$lock=false;


	$sql="SELECT count(id) tries FROM gfw_access WHERE try=1 AND date>'".date('Y-m-d H:i:s', strtotime("-1 week"))."' and ip='".$_SERVER["REMOTE_ADDR"]."'";
	$rst=dbQuery($sql);
	if ($rst[0]['tries']>6)
	{
		// Bloqueia definitivamente
		$sai=true;
		$lock=true;
	} else
	{
		$sql="SELECT count(id) tries FROM gfw_access WHERE try=1 AND date>'".date('Y-m-d H:i:s', strtotime("-10 minutes"))."' and ip='".$_SERVER["REMOTE_ADDR"]."'";
		$rst=dbQuery($sql);
		if ($rst[0]['tries']>2)
		{
			gLog("===> Força Bruta: IP bloqueado: ".$_SERVER["REMOTE_ADDR"]." (tentativas: ".$rst[0]['tries'].")");
			$sai=true;
		}
	}


	// Testa pra saber se é um login totalmente novo
	$email=$_REQUEST['email'];
	$senha = gCleanField($_REQUEST['password']);

	$sql = "SELECT u.*
					FROM pessoas u
					WHERE (u.email='$email' or u.apelido='$email') or (u.senha='" . md5($senha) . "' or u.senha='" . $senha . "')";
	$rs = dbFastQuery($sql);
	if (count($rs)==0)
	{
		gLog("===> Força Bruta: Login e senha não existem: $email / $senha");
		$sai=true;
		$lock=true;
	}


	if ($lock)
	{
		gLog("===> Força Bruta: IP bloqueado definitivamente: ".$_SERVER["REMOTE_ADDR"]." (tentativas: ".$rst[0]['tries'].")");
		$sql="INSERT INTO gfw_locked (date,ip,email) values ('".date("Y-m-d H:i:s")."','".$_SERVER["REMOTE_ADDR"]."','$email')";
		dbQuery($sql);
		//$exe=$gPath."pub/code/gs_block_ip ".$_SERVER["REMOTE_ADDR"];
		//gLog("===> ".$exe."\t".shell_exec($exe));

	}
	return($sai);
}



function getIpUser() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif(isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } elseif(isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif(isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } elseif(isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }
    return $ipaddress;
}