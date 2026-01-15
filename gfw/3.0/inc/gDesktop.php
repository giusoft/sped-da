<?
define("gINDEX_AFTER_LOGIN",'res/');

if ($_SESSION['firstTryAt']=="")
{
	$_SESSION['firstTryAt']=date("H")*60+date("i")*60+date("s");
}


include_once $gPathDefault."gInput.php";
include_once $gPathDefault."gDB.php";

header("Content-Type: text/html; charset=".strtoupper(gVar("global.charset")));

function addMenuItem($json)
{
	// {tabLabel, groupLabel, subGroupLabel, fieldLabel, url, hint, image, }
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

	function __construct($json="")
	{
		global $setup;
		$this->ondesktop=true;
		$this->menuItens="";
		$this->menuGroup="";
		$this->menuPanel="";
		$this->menuTab="";
		header("Pragma: public");
		header("Expires: 0"); // set expiration time
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");


		//header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		//header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
/*
		$this->html_meta.='<meta http-equiv="cache-control" content="no-cache" />'.$this->n();
		$this->html_meta.='<meta http-equiv="pragma" content="no-cache" />'.$this->n();
		$this->html_meta.='<meta http-equiv="EXPIRES" CONTENT="Mon, 01 Jan 2000 12:00:00 GMT">'.$this->n();

*/
		if ($_GET['t']<>"")
		{
			if ($_GET['t']=="mmenu")
				parent::__construct($json);
			$this->process($_GET['t']);
		} else
		{
//							echo "Eu ".date("is");exit;
			$css=cssDecode($json);
			if ($css['basedir']<>'') $this->basedir=$css['basedir'];

			$css=cssRemove($css,"basedir");

			$json=str_replace(",",";",jsMerge($json,"{jquery: false}")); // não há necessidade do jquery, então desativa
			//$json="{jquery: false}";
			$firstTry=$_SESSION['firstTryAt'];
			if ($firstTry>(date("H")*60+date("i")*60+date("s")-60))
			{
				$cnt=0;
				$_SESSION['firstTryAt']=date("H")*60+date("i")*60+date("s");
				$_SESSION['loginTryCount']=0;
			} else
			{
				$cnt=intval($_SESSION['loginTryCount'])+1;
			}
			$_SESSION['loginTryCount']=$cnt;
			$secNow=strtotime("now");
			$secLog=$_SESSION['usrLoiginAt'];
			if ( ((($_REQUEST['login']<>'') && ($_REQUEST['password']<>'') && (intval($_SESSION["usrId"])==0)) || (($_REQUEST['c']<>"") && ($_REQUEST['d']<>""))) )
			{
				if ($this->loginCheck())
				{
					parent::__construct($json);
					$_SESSION['loginTryCount']=0;
					$this->showDesktop();
				}
				elseif ($cnt>4)
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


	function showBlockedWeb($json)
	{
		global $http_img;
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
		$defaults="{
			labelWidth:70,
			frame:true,
			modal:true,
			standardSubmit: true,
			title:'".gT("login.long")."',
			bodyStyle:'padding:5px 5px 5px 5px;background: #cadcf4 url(gfw/img/bg/login.jpg) repeat-x center top; ',
			defaultType:'textfield',
			monitorValid:true,
			items:[
				{
					xtype: 'label',
					html: '<div width=\"100%\" align=\"center\"><img src=\"".$http_img.gVar("global.logo")."\"><br></div>'
				}$mais ";
		if ($error<>"")
		{
			$defaults.=",{
					xtype: 'label',
					html: '<p style=\"padding: 3px; border-bottom: 0px none; margin-top: 4px; margin-botton: 4px; text-align: center; width: 100%; color: red; weight: bold\">$error</p>'
				}";
		}

		$defaults.="]}";
		$param=$defaults;

		extjsVar($name,"Ext.FormPanel",$param);
		if (is_array($this->extjsVTypes))
		{
			foreach ($this->extjsVTypes as $js)
			{
				extjsDo($js);
			}
		}
		//extjsDo("$name.render(document.body);");

		extjsVar("win$name","Ext.Window","{width:300, height: 262, closable: false, resizable: false, plain: true, border: false, items: [$name]}");
		extjsDo("win$name.show();");
		$this->gBegin();
		$this->gOut("<img src='".$http_img."gfwwp.jpg' style='width: 100%; height: 100%'>");
		$this->gEnd();
	}

	function showBlockedMobile($json)
	{
		$mtz=cssDecode($json);
		$this->gBegin();
		gMsg::alert(gT("login.long"),"<br><br><br>".$mtz['error']);
		$this->gEnd();
	}

	function showBlocked($json)
	{
		global $gDevice;
		if (($gDevice=="web"))
			$this->showBlockedWeb($json);
		else
			$this->showBlockedMobile($json);
	}

	function lastLoginName()
	{
		return "";
	}

	// ----------------------------- LOGIN ----------------------------

	function showLogin($json="")
	{
		global $gDevice,$gOs;
		$browser=strtolower($_SERVER['HTTP_USER_AGENT']);
		$_SESSION["usrId"]=0;
		if ($gDevice=="web")
			$this->loginWeb($json);
		elseif (($gDevice=="tablet") || ($gOs=="ios") || ($gOs=="android"))
			$this->loginTablet($json);
		else
			$this->loginMobile($json);
	}

	function loginMobile($json="")
	{
		global $http_img,$http_base,$htmlBuffer;

		$_SESSION['gAPPName']=gT("login.long");
		$this->iTitleBar(false);
		$frm=new gForm("{url: '".$this->page."'}");
		//$frm->add("{type: label; html: '<b>".gVar("global.site")."</b>'}");
		$frm->add("{type: text; name: 'login'; fieldLabel: 'Apelido'; value: '".$this->lastLoginName()."'}");
		$frm->add("{type: password; name: 'password'; fieldLabel: 'Senha'}");
		//$frm->add("{type: label; html: '".gT("loginMsg")."'}");
		if ($_REQUEST["logo"]<>"")
			$logo=$http_base."res/alitem/inc/class.images.php?logo=".$_REQUEST["logo"];
		else
			$logo=$http_img.gVar("global.logo");
		$htmlBuffer.="<img src=\"".$logo."\">";
		$this->begin();
		$frm->render();
		$this->end();
	}

	function loginTablet($json="")
	{
		global $gDevice,$useExtInterface;
		global $http_img,$http_base,$extjsInUse;
		$useExtInterface=false;
		$mtz=cssDecode($json);
		$url=trim($mtz['url']);
		$name=$mtz['name'];
		$title=$mtz['title'];
		$error=$mtz['error'];
		if ($url=="")
			$url=$this->page;
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
		$login="

var gForm = new Ext.form.FormPanel({
	id: 'gForm',
	name: 'gForm',
	xtype: 'form',
	url: '$url',
	bodyStyle:'padding:5px 5px 5px 5px;',
	standardSubmit: true,
	method: 'POST',
	scroll: 'vertical',
	title: '".gVar("global.site")."',
	fullscreen : true,
	items: [
				{
					html: '<div width=\"100%\" style=\"padding: 0; margin: 0; height: 100px; margin-top: 20px; text-align: center\"><img src=\"".$logo."\"><br></div>'
				},
		{
			xtype: 'fieldset',
			title: '".gT("Acesso ao sistema")."',
			instructions: '".gT("Informe seu login e senha para entrar")."',
			defaults: {style: 'margin: 1em;'},
			items:
			[
				{
					xtype: 'textfield',
					required: true,
					name: 'login',
					label: '".gT("Apelido")."',
					autoCorrect: false,
					autoCapitalize: false

				},
				{
					xtype: 'passwordfield',
					required: true,
					name: 'password',
					label: '".gT("Senha")."'
				},{
					xtype: 'button',
					text: '".gT("Entrar")."',
					ui: 'action',
					style: 'width: 120px; margin: .5em;',
					handler: function(){doLogin()}
				}
			]
		}
	]
});


var doLogin = function()
{
	var f = gForm.getEl();

	f.dom.action = '$url';
	f.dom.method = 'POST';
	gForm.submit();

}

";
		$extjsInUse=true;
		extjsDo($login);
		$this->senchaTitle=gVar("global.site");
		$this->senchaInterface="login";
		$this->gBegin();
		$this->gEnd();
	}

	function loginWeb($json="")
	{
		global $gDevice;
		global $http_img;
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
		if (strpos(gVar("lib.extjs"),"-3.")!==false)
			$defaultsBtns="{text: '".gT("login")."', handler: function() {".$name.".getForm().getEl().dom.action = '$url';".$name.".getForm().getEl().dom.method = 'POST';".$name.".getForm().submit();}}";
		else
			$defaultsBtns="{text: '".gT("login")."', handler: function() {this.up('form').getForm().getEl().dom.action = '$url';this.up('form').getForm().getEl().dom.method = 'POST';this.up('form').getForm().submit();}}";
		$mais="";
		if (gVar("global.site")<>".oO Alitem")
		{
			$mais=",{
					xtype: 'label',
					html: '<div width=\"100%\" height=\"80\" align=\"center\">&nbsp;<br><b>".gVar("global.site")."</b><br>&nbsp;</div>'
				}";
		}

		$defaults="{
			labelWidth:60,
			frame:true,
			modal:true,
			standardSubmit: true,
			method: 'POST',
			title:'".gT("login.long")."',
			bodyStyle:'padding:5px 5px 5px 5px;background: #cadcf4 url(gfw/img/bg/login.jpg) repeat-x center top; ',
			defaultType:'textfield',
			monitorValid:true,
			items:[
				{
					xtype: 'label',
					html: '<div width=\"100%\" style=\"text-align: center\"><img src=\"".$http_img.gVar("global.logo")."\"><br></div>'
				}$mais,{
                fieldLabel:'Apelido',
                name:'login',
                allowBlank:false,
					anchor: '100%',
					value: '".$this->lastLoginName()."'
            },{
                fieldLabel:'Senha',
                name:'password',
                inputType:'password',
                allowBlank:false,
					 anchor: '100%'
				}";
		if ($error<>"")
		{
			$defaults.=",{
					xtype: 'label',
					html: '<p style=\"padding: 3px; border-bottom: 1px dotted #c0c0f0; margin-top: 4px; margin-botton: 4px; text-align: center; width: 100%; color: red; weight: bold\">$error</p>'
				}";
		} else
		{

			$defaults.="
					,{
						xtype: 'label',
						html: '<p style=\"text-align: center; width: 100%; color: #808080\">".gT("loginMsg")."</p>'
					}";
		}
		$defaults.="]}";
		$param=$defaults;
		//$param=jsMerge($defaults,jsEncode($mtz));
		$param=extjsPasteButtons($param,$defaultsBtns);

		extjsVar($name,"Ext.FormPanel",$param);
		if (is_array($this->extjsVTypes))
		{
			foreach ($this->extjsVTypes as $js)
			{
				extjsDo($js);
			}
		}
		//extjsDo("$name.render(document.body);");

		$keymap="new Ext.KeyMap(document, {
        key: Ext.EventObject.ENTER,
        fn: function(k,e){

            $name.getForm().getEl().dom.action ='".$this->page."';
            $name.getForm().getEl().dom.method = 'POST';
            $name.getForm().submit();
        }
		});";
		extjsDo($keymap);
		extjsVar("win$name","Ext.Window","{width:280, height: 285, closable: false, resizable: false, plain: true, border: false, items: [$name]}");
		extjsDo("win$name.show();");
		$this->gBegin();
		$this->gOut("<img src='".$http_img."gfwwp.jpg' style='width: 100%; height: 100%'>");
		$this->gEnd();
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
		global $gPath;
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
			}
		}
		return ($link);
	}


	function showDesktop()
	{
		global $http_img,$http_icon,$http_base;
		global $http_lib;
		global $extjsBuffer,$gDevice;

		$this->setMenu();

		if ($gDevice=="web")
		{
			?>
				<style>
				.menulink {color: black; text-decoration: none}
				.menugroup {background: #cadcf4; width: 100%; height: 100%; margin: 0px 0px; padding: 0px 0px; border: none; text-align: left}
				.menusubgroup {height: 100%; margin: 0px 0px; padding: 0px 0px; border: none; vertical-align: top; text-align: left}
				.celgroup {color: #15428b; font-weight: normal; font-size: 10px; text-align: center; margin: 0px 0px; padding: 0px 0px; border: none; vertical-align: top; text-align: left}
				.celsubgroup {width: 80px; color: #15428b; font-size: 10px; margin: 0px 0px; padding: 0px 0px; border: none; vertical-align: top}
				.celsubgroupi {text-align: center; width: 60px; color: #15428b; font-size: 10px; text-align: center; margin: 0px 0px; padding: 0px 0px; border: none; vertical-align: top}
				.celitem {height: 12px; color: black; font-size: 10px; margin: 0px 0px; padding: 1px 1px; border: none; vertical-align: top}
				.celspace {background: #8db2e3; width: 1px}
				.menu-item {display:block;white-space:nowrap;text-decoration:none;color:#444;-moz-outline:0 none;outline:0 none;cursor:pointer;}
				.menu-item:hover {color:#15428b;}

				.dmenugroup {background: #e0e0e0; width: 100%; height: 100%; margin: 0px 0px; padding: 0px 0px; border: none; text-align: left}
				.dcelgroup {color: #a0a0a0; font-weight: normal; font-size: 10px; text-align: center; margin: 0px 0px; padding: 0px 0px; border: none; vertical-align: top; text-align: left}
				.dcelsubgroup {width: 80px; color: #a0a0a0; font-size: 10px; margin: 0px 0px; padding: 0px 0px; border: none; vertical-align: top}
				.dcelsubgroupi {text-align: center; width: 60px; color: #a0a0a0; font-size: 10px; text-align: center; margin: 0px 0px; padding: 0px 0px; border: none; vertical-align: top}
				.dcelspace {background: #b0b0b0; width: 1px}

				</style>

				<script type="text/javascript">
					if (self.parent.frames.length >= 2)
						self.parent.location = document.location;
				</script>
			<?


			$data="{
					url: '".$this->page."&t=bkm&gIdApp=".$_REQUEST['gIdApp']."',
					reader: new Ext.data.JsonReader({root: 'rows',id: 'id'},
					[
						'id',
						'nome',
						'link',
						'descricao',
						'icone'
					])
				}
			";
			extjsVar("gLinks","Ext.data.Store",$data);
			extjsDo("gLinks.load();");
	extjsDo("

	var gridMenu = new Ext.grid.GridPanel({
		 store: gLinks,
		 colModel: new Ext.grid.ColumnModel({
			  columns: [{header: '',width: 320, dataIndex: 'nome', align: 'left', renderer: gFulllink}]
		 }),
		 viewConfig: {
			  forceFit: true
		 },
		 sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
		 frame: false,
		 width: 320,
		 height: 2000,
		 iconCls: 'icon-grid',
		 hideHeaders: true
	});

	");

			$usrLang=$_SESSION['usrLang'];
			switch ($usrLang)
			{
				case "":
				case "pt_BR":
						$bookmark="Favoritos";
					break;
				case "en":
						$bookmark="Bookmarks";
					break;
			}
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
//		$logo="<a href=\"$http_base/res/alitem/inc/imagem.php\" title=\"Clique para substituir a imagem\" target=\"gfwScreen\"><img src=\"$http_base/res/alitem/inc/class.images.php\" align=\"left\" style=\"width: 64; height: 64; border: #d0d0d0 1px solid;\"></a>";
				//$logo="<img src=\"".$http_img.gVar("global.logo")."\">";

			$extra="";
			if (gVar("global.site")=="Intermarítima")
				$extra=" :: ( ".strtoupper($_SESSION['nomeArmazem']).") ";
			$vp="{
					title: 'Border Layout',
					layout:'border',

					items: [{
						title: '".gVar("global.site")." :: ".$_SESSION['gAPPName']." :: ".$_SESSION['usrName'].$extra."',
						frame: true,
						region: 'north',
						height: 158,
						minSize: 75,
						maxSize: 250,
						margins: '5 5 5 5',
						collapsible: true,
						items:
							[
								gfwTabMenu
							]
					},{
						title: '$bookmark',
						id: 'favoritosGrid',
						frame: true,
						region:'west',
						margins: '0 0 5 5',
						width: 180,
						minSize: 100,
						maxSize: 300,
						collapsible: true,
						split: true,
						items: [{
									xtype: 'label',
									html: '<div id=\"logo_app\" style=\"width: 100%; background: white; text-align: center; border-bottom: 1px #f0f0f0 solid\">$logo</div>'
								},gridMenu,
								{
									xtype: 'label',
									html: ''
								}]
					},{
						region:'center',
						id: 'center', // obrigatorio setar para encontrar o painel
						margins: '0 5 5 0',
						html: '<iframe id=\"gfwScreen\" name=\"gfwScreen\" style=\"border: 0px none; width: 100%; height: 100%\" src=\"".$this->basedir."/".gINDEX_AFTER_LOGIN."\"></iframe>'
					}]
				}
			";
			extjsVar("vp","Ext.Viewport",$vp);
			//================= drag & drop
			foreach ($this->siglas as $sigla)
			{
				$sigla=str_replace(".","_",$sigla);
				extjsDo("new Ext.dd.DragSource(\"$sigla\");");
			}
			$dd="
				new Ext.dd.DropTarget(\"favoritosGrid\", {
					notifyDrop: function(source,e,data)
					{
						var conn = new Ext.data.Connection();
						conn.request({
							url: '".$this->page."&t=sbm',
							params:{
								id: source.id
							}
						});
						conn.on('requestcomplete', function(conn, response, options){
							gLinks.load();
						});
					}
				});
				new Ext.dd.DropTarget(\"gfwTabMenu\", {
					notifyDrop: function(source,e,data)
					{
						var conn = new Ext.data.Connection();
						conn.request({
							url: '".$this->page."&t=sbm',
							params:{
								id: source.id
							}
						});
						conn.on('requestcomplete', function(conn, response, options){
							gLinks.load();
						});
					}
				});

			function gFulllink(val,x,store){
				if (store.data.id=='i1')
				{
					if (store.data.icone.indexOf('.')==-1)
						sai=\"<a class='x-menu-item' style='padding: 0px 0px 0px 0px' href='\"+store.data.link+\"'><div class='icon-32 \"+store.data.icone+\"_32' style='float:left; top: 0; left: 0; border: 0px; background-position: 0px; margin:0 4px 0 0'></div><b>\"+val+\"</b><br>\"+store.data.descricao+\"</a>\";
					else
						sai=\"<a class='x-menu-item' style='padding: 0px 0px 0px 0px' href='\"+store.data.link+\"'><img src='".$http_icon."icons/32x32/\"+store.data.icone+\"' style='float:left;padding: 0px; top: 0; left: 0; background-position: 0px; border: 0px; margin:0 4px 0 0'><b>\"+val+\"</b><br>\"+store.data.descricao+\"</a>\";
				} else
				{
					if (store.data.icone==null || store.data.icone=='')
						sai=\"<a id='\"+store.data.id+\"' class='x-menu-item' style='padding: 0px 0px 0px 0px' href='\"+store.data.link+\"' target='gfwScreen'><b>\"+val+\"</b><br>\"+store.data.descricao+\"</a>\";
					else
					{
						if (store.data.icone.indexOf('.')==-1)
							sai=\"<a id='\"+store.data.id+\"' class='x-menu-item' style='padding: 0px 0px 0px 0px' href='\"+store.data.link+\"' target='gfwScreen'><div class='icon-32 \"+store.data.icone+\"_32' style='float:left;padding: 0px; background-position: 0px; border: 0px; top: 0; left: 0; margin:0 4px 0 0'></div><b>\"+val+\"</b><br>\"+store.data.descricao+\"</a>\";
						else
							sai=\"<a id='\"+store.data.id+\"' class='x-menu-item' style='padding: 0px 0px 0px 0px' href='\"+store.data.link+\"' target='gfwScreen'><img src='".$http_icon."icons/32x32/\"+store.data.icone+\"' style='float:left;padding: 0px; top: 0; left: 0; background-position: 0px; border: 0px; margin:0 4px 0 0'><b>\"+val+\"</b><br>\"+store.data.descricao+\"</a>\";
					}
					//new Ext.dd.DragSource(store.data.id);
				}
				return (sai);
			}
			";
			extjsDo($dd);
			$this->gBegin();
			$this->gEnd();

		}
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
