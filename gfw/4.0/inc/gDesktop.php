<?
define("gINDEX_AFTER_LOGIN",'res/');


include_once $gPathDefault."gPortal.php";

header("Content-Type: text/html; charset=".strtoupper(gVar("global.charset")));

function addMenuItem($json)
{
	$mtz=cssDecode($json);
	$this->menuItens[]=array($mtz);
}

class gDesktop extends gInput
{
	public $login;
	public $password;
	public $basedir="res";
	public $siglas="";
	public $desktopon=false;
	private $tabMenu;
	public $extraFields;

	function __construct($json="", $extraFields="")
	{
		global $setup;
		$this->ondesktop=true;
		$this->menuItens="";
		$this->menuGroup="";
		$this->menuPanel="";
		$this->menuTab="";
		$this->extraFields=$extraFields;

		header("Pragma: public");
		header("Expires: 0"); // set expiration time
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

		if ($_GET['t']<>"")
		{
			if ($_GET['t']=="mmenu")
				parent::__construct($json);
			$this->process($_GET['t']);
		} else
		{
			$css=cssDecode($json);
			if ($css['basedir']<>'') $this->basedir=$css['basedir'];

			$css=cssRemove($css,"basedir");

			$json=str_replace(",",";",jsMerge($json,"{jquery: false}")); // não há necessidade do jquery, então desativa
			//$json="{jquery: false}";
			$firstTry=$_SESSION['firstTryAt'];
			if (($_SESSION['loginTryCount']>0 && $firstTry<(intval(date("H")*3600+date("i")*60+date("s"))-60)) || $firstTry=="")
			{
				$cnt=0;
				$_SESSION['firstTryAt']=date("H")*3600+date("i")*60+date("s");
				$_SESSION['loginTryCount']=0;
			} else
			{
				$cnt=intval($_SESSION['loginTryCount'])+1;
			}
			$_SESSION['loginTryCount']=$cnt;
			$secNow=strtotime("now");
			$secLog=$_SESSION['usrLoiginAt'];

			//gD($_REQUEST);gD($_SESSION);
			if ( ((($_REQUEST['login']<>'') && ($_REQUEST['password']<>'') && (intval($_SESSION["usrId"])<=0)) || (($_REQUEST['c']<>"") && ($_REQUEST['d']<>""))) )
			{
				if ($this->loginCheck())
				{
					parent::__construct($json);
					$_SESSION['loginTryCount']=0;
					$this->showDesktop();
				}
				elseif ($cnt>6)
				{
					parent::__construct($json);
					$this->showBlocked("{error: '<b>".gT("loginBlocked")." ".date("H:i:s")."</b><br>".gT("loginBlocked.long")."'}");
				}
				elseif ($_GET['error']<>'')
				{
					// Login com erro
					parent::__construct($json);
					$this->showLogin("{error: '$error'}");
				}
				else
				{
					// Login sem erro
					parent::__construct($json);
					$this->showLogin("{error: '<b>".gT("loginError")."</b><br>".gT("loginError.long")."'}");
				}
			} elseif (intval($_SESSION["usrId"])<=0)
			{
				// Login normal
				parent::__construct($json);
				$_SESSION["usrLoginAt"]=strtotime("now");
				$this->showLogin();
			} else
			{
				parent::__construct($json);
				$_SESSION['loginTryCount']=0;
				$this->showDesktop();
			}
		}
	}


	// Processamento de AJAX
	function process()
	{

	}


	function showBlocked($json)
	{
		global $http_img;
		$jarr=cssDecode($json);
		$error=$jarr['error'];
		$o=new gInput();

		$o->begin();

		$html.='<div class="container">&nbsp;';
		$html.='</div>';
		$html.='<div class="container">';
		$html.='	<div class="col-lg-4 col-md-3 hidden-sm hidden-xs"></div>';
		$html.='		<div class="text-center col-lg-4 col-md-6 col-sm-12 col-xs-12">';
		$html.='<img src="'.$http_img.gVar("global.logo").'"><br><br>';
		if ($error<>'')
		{
			$html.=$o->msgError($error);
		}
		$html.='		</div>';
		$html.='	<div class="col-lg-4 col-md-3 hidden-sm hidden-xs"></div>';
		$html.='</div>';
		$o->out($html);
		$o->end();


		$mtz=cssDecode($json);
		$url=$mtz['url'];
		$name=$mtz['name'];
		$title=$mtz['title'];
		$error=$mtz['error'];
		if ($url=="")
			$mtz['url']=$this->page;
		if ($title=="")
			$title="loginForm";
		if ($name=="")
		{
			$name=gFieldReverse($title); // chapa o texto
			$mtz['name']=$name;
		}
		$mais="";
		if ((gVar("global.site")<>".oO Alitem") && (gVar("global.site")<>"Alitem"))
		{
			$mais=",{
					xtype: 'label',
					html: '<div width=\"100%\" height=\"80\" align=\"center\">&nbsp;<br><b>".gVar("global.site")."</b><br>&nbsp;</div>'
				}";
		}
	}

	function lastLoginName()
	{
		return "";
	}

	// ----------------------------- LOGIN ----------------------------

	function showLogin($json="")
	{
		global $gDevice,$gOs,$http_img;

		$mtz=cssDecode($json);
		$url=trim($mtz['url']);
		$name=$mtz['name'];
		$title=$mtz['title'];
		$error=$mtz['error'];
		if ($url=="")
			$mtz['url']=$this->page;
		if ($title=="")
			$title="loginForm";
		if ($name=="")
		{
			$name=gFieldReverse($title); // chapa o texto
			$mtz['name']=$name;
		}

		if ($_REQUEST["logo"]<>"")
			$logo=$http_base."res/alitem/inc/class.images.php?logo=".$_REQUEST["logo"];
		else
			$logo=$http_img.gVar("global.logo");

		$o=new gInput();

		$frm=new gForm("{title: ".gT("Acesso ao sistema")."; columns: 1; inline: true}");
		$frm->setButtonNextCaption("Entrar");
		$frm->add("{type: html; value: <img src=\"".$http_img.gVar("global.logo")."\">}");
		$frm->add("{name: login; fieldLabel: Apelido; maxLength: 50; value: ".$this->lastLoginName()."}");
		$frm->add("{name: password; fieldLabel: Senha; type: password; maxLength: 50}");
		if (is_array($this->extraFields))
		{
			foreach ($this->extraFields as $fld)
				$frm->add($fld);
		}

		$frm->add("{type: html; value: ".gT("loginMsg")."}");

		$o->begin();

		$html.='<div class="container">&nbsp;';
		$html.='</div>';
		$html.='<div class="container">';
		$html.='	<div class="col-lg-4 col-md-3 hidden-sm hidden-xs"></div>';
		$html.='		<div class="text-center col-lg-4 col-md-6 col-sm-12 col-xs-12">';
		//$html.='<img src="'.$http_img.gVar("global.logo").'"><br><br>';
		// if (gVar("global.site")<>".oO Alitem")
		// 	$html.='<h1>'.gVar("global.site").'</h1>';
		$html.=$frm->render($o);
		if ($error<>'')
		{
			$html.=$o->msgError($error);
		}
		$html.='		</div>';
		$html.='	<div class="col-lg-4 col-md-3 hidden-sm hidden-xs"></div>';
		$html.='</div>';
		$o->out($html);
		$o->end();
	}


	function loginCheck()
	{
		if (($_POST['login']=='test') && ($_POST['password']=='test'))
			$res=true;
		else
			$res=false;
		return ($res);
	}

	function setTabMenu($tabMenu)
	{
		$this->tabMenu=$tabMenu;
	}

	function fullLink($link)
	{
		global $gPath,$http_base;
		// gFW 3.0
		$basedir=$this->basedir."/";
		// gFW 2.0
		$lnk=$link;
		if (intval(basename($link))>0)
		{
			//$pastaHttp=str_pad($_SESSION['usrIdd'],9,"0",STR_PAD_LEFT);
			$link=$basedir.'go.php?g='.$_REQUEST['gIdApp'].".".str_replace(".php","",basename($link));
		} else
		{
			if (strpos($link,"online")!==false)
			{
				$link=substr($link,strpos($link,"online"));
			} else
			{
				if (strpos($link,"?")!==false)
					$lnk=substr($link,0,strpos($link,"?"));
				if (!file_exists($gPath.$basedir.$lnk))
					$basedir="online/";
				if (!file_exists($gPath.$basedir.$lnk))
					$basedir=$this->basedir."/";
				if (!file_exists($gPath.$basedir.$lnk))
					$link=$link;
				else
					$link=$basedir.$link;
				$link=$http_base.$link;
			}
		}
		return ($link);
	}


	function showDesktop()
	{
		global $http_img,$http_icon,$http_base;
		global $http_lib;
		global $extjsBuffer,$gDevice;

		if ($_SESSION['gFW_MENU']=='')
		{
			if ((gVar("global.site")<>".oO Alitem") && (gVar("global.site")<>"Alitem"))
			{
				$logo="<img src=\"".$http_img.gVar("global.logo")."\">";
			} else
			{
				if ($_SESSION['usrClient']>0)
					$logo="<a href=\"$http_base/res/alitem/inc/imagem.php\" title=\"Clique para substituir a imagem\" target=\"gfwScreen\"><img src=\"$http_base/res/alitem/inc/class.images.php?i=".$_SESSION['usrIdd']."&t=".date("His")."\" style=\"width: 150;\"></a>";
				else
					$logo="<a href=\"$http_base/res/alitem/inc/imagem.php\" title=\"Clique para substituir a imagem\" target=\"gfwScreen\"><img src=\"$http_base/res/alitem/inc/class.images.php?t=".date("His")."\" style=\"width: 150;\"></a>";
			}
			$extra="";
			if (gVar("global.site")=="Intermarítima")
				$extra=" :: ( ".strtoupper($_SESSION['nomeArmazem']).") ";

			$title=gVar("global.site")." :: ".$_SESSION['gAPPName']." :: ".$_SESSION['usrName'].$extra;

			$html.=$this->setMenu();
			//$_SESSION['gFW_MENU']=$html;
		} else
		{
			$html=$_SESSION['gFW_MENU'];
		}
		$o=new gOutput();
		$o->begin();
		$divDate="<div class=\"text-left col-lg-4 col-md-4 col-sm-4 col-sx-4\">".gDateTime(date("Y-m-d H:i"))."</div>";
		$divTitle="<div class=\"text-center col-lg-4 col-md-4 col-sm-4 col-sx-4\">$title</div>";
		$divBtns="<div class=\"text-right col-lg-4 col-md-4 col-sm-4 col-sx-4\">";
		$divBtns.=$o->button("{size: tiny; style: success; icon: home; title: Início; href: ".$o->page."}");
		$divBtns.=$o->button("{size: tiny; style: danger; icon: power-off; title: Sair; href: ".$o->page."&t=out}");
		$divBtns.="</div>";
		$barra="<div style=\"background-color: #000; color: #fff; padding: 10px; height: 48px; margin-bottom: 10px\">".$divDate.$divTitle.$divBtns."</div>";
		$o->out($barra);
		$o->addJavascript("$('a').tooltip();");
		$o->out($html);
		//$o->out("<iframe id=\"gfwScreen\" name=\"gfwScreen\" style=\"border: 0px none; width: 100%; height: 100%\" src=\"".$this->basedir."/".gINDEX_AFTER_LOGIN."\"></iframe>");
		$o->end();
	}


	// Obtem itens do menu e retorna em array
	function setMenu()
	{

			$b1=new gButtonGroup("{name: gfwButtonGroup; title: 'Assistentes'}");
			$b1->add(gMenuItem::getIconText("{text: 'Menu'; icon: 'procurar.png'; tooltip: 'Editor de menu'; url: 'sys/wz_menu.php'}"));
			$b1->add(gMenuItem::getIconText("{text: 'Relatórios'; icon: 'papel_impresso.png'; tooltip: 'Editor de relatórios'; url: 'sys/report.php'}"));

			$b2=new gButtonGroup("{name: gfwButtonGroup; title: 'Páginas de exemplos'}");
			$b2->add(gMenuItem::getIconText("{text: 'Formulário'; icon: 'papel_escrito.png'; tooltip: 'Formulário'; url: 'sys/samples/form.php'}"));
			$b2->add(gMenuItem::getIconText("{text: 'Página '; icon: 'papel_ok.png'; tooltip: 'Página automática para formulário'; url: 'sys/samples/page.php?style=form'}"));
			$b2->add(gMenuItem::getIconText("{text: 'Página '; icon: 'agenda.png'; tooltip: 'Página automática para grid'; url: 'sys/samples/page.php?style=grid'}"));

			$p1=new gPanel("{name: Panel1}");
			$p1->add($b1->get());
			$p1->add($b2->get());


			$t1=new gTab("{name: gfwTabMenu}");
			$t1->add("Opções",$p1->get());
			$t1->render();
	}

	function __destruct()
	{
	}
}
?>
