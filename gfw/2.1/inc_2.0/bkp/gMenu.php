<?

include $gPathDefault."gInput.php";
if (gVar("global.showcalendar")=="true")
	include "../online/i00_uti/agenda.php";

function g_Menu_sniffer()
{
?>
<script language='javascript'>
//<!--
var agt=navigator.userAgent.toLowerCase();
var is_major = parseInt(navigator.appVersion);
var is_minor = parseFloat(navigator.appVersion);
var is_nav  = ((agt.indexOf('mozilla')!=-1) && (agt.indexOf('spoofer')==-1)
			 && (agt.indexOf('compatible') == -1) && (agt.indexOf('opera')==-1)
			 && (agt.indexOf('webtv')==-1) && (agt.indexOf('hotjava')==-1));
var is_nav2 = (is_nav && (is_major == 2));
var is_nav3 = (is_nav && (is_major == 3));
var is_nav4 = (is_nav && (is_major == 4));
var is_nav4up = (is_nav && (is_major >= 4));
var is_navonly      = (is_nav && ((agt.indexOf(";nav") != -1) ||
						  (agt.indexOf("; nav") != -1)) );
var is_nav6 = (is_nav && (is_major == 5));
var is_nav6up = (is_nav && (is_major >= 5));
var is_gecko = (agt.indexOf('gecko') != -1);
var is_ie     = ((agt.indexOf("msie") != -1) && (agt.indexOf("opera") == -1));
var is_ie3    = (is_ie && (is_major < 4));
var is_ie4    = (is_ie && (is_major == 4) && (agt.indexOf("msie 4")!=-1) );
var is_ie4up  = (is_ie && (is_major >= 4));
var is_ie5    = (is_ie && (is_major == 4) && (agt.indexOf("msie 5.0")!=-1) );
var is_ie5_5  = (is_ie && (is_major == 4) && (agt.indexOf("msie 5.5") !=-1));
var is_ie5up  = (is_ie && !is_ie3 && !is_ie4);
var is_ie5_5up =(is_ie && !is_ie3 && !is_ie4 && !is_ie5);
var is_ie6    = (is_ie && (is_major == 4) && (agt.indexOf("msie 6.")!=-1) );
var is_ie6up  = (is_ie && !is_ie3 && !is_ie4 && !is_ie5 && !is_ie5_5);
var is_aol   = (agt.indexOf("aol") != -1);
var is_aol3  = (is_aol && is_ie3);
var is_aol4  = (is_aol && is_ie4);
var is_aol5  = (agt.indexOf("aol 5") != -1);
var is_aol6  = (agt.indexOf("aol 6") != -1);

var is_opera = (agt.indexOf("opera") != -1);
var is_opera2 = (agt.indexOf("opera 2") != -1 || agt.indexOf("opera/2") != -1);
var is_opera3 = (agt.indexOf("opera 3") != -1 || agt.indexOf("opera/3") != -1);
var is_opera4 = (agt.indexOf("opera 4") != -1 || agt.indexOf("opera/4") != -1);
var is_opera5 = (agt.indexOf("opera 5") != -1 || agt.indexOf("opera/5") != -1);
var is_opera5up = (is_opera && !is_opera2 && !is_opera3 && !is_opera4);

var is_webtv = (agt.indexOf("webtv") != -1);

var is_TVNavigator = ((agt.indexOf("navio") != -1) || (agt.indexOf("navio_aoltv") != -1));
var is_AOLTV = is_TVNavigator;

var is_hotjava = (agt.indexOf("hotjava") != -1);
var is_hotjava3 = (is_hotjava && (is_major == 3));
var is_hotjava3up = (is_hotjava && (is_major >= 3));

var is_js;
if (is_nav2 || is_ie3) is_js = 1.0;
else if (is_nav3) is_js = 1.1;
else if (is_opera5up) is_js = 1.3;
else if (is_opera) is_js = 1.1;
else if ((is_nav4 && (is_minor <= 4.05)) || is_ie4) is_js = 1.2;
else if ((is_nav4 && (is_minor > 4.05)) || is_ie5) is_js = 1.3;
else if (is_hotjava3up) is_js = 1.4;
else if (is_nav6 || is_gecko) is_js = 1.5;
else if (is_nav6up) is_js = 1.5;
else if (is_ie5up) is_js = 1.3

else is_js = 0.0;

var is_win   = ( (agt.indexOf("win")!=-1) || (agt.indexOf("16bit")!=-1) );
var is_win95 = ((agt.indexOf("win95")!=-1) || (agt.indexOf("windows 95")!=-1));

var is_win16 = ((agt.indexOf("win16")!=-1) ||
			(agt.indexOf("16bit")!=-1) || (agt.indexOf("windows 3.1")!=-1) ||
			(agt.indexOf("windows 16-bit")!=-1) );

var is_win31 = ((agt.indexOf("windows 3.1")!=-1) || (agt.indexOf("win16")!=-1) ||
				  (agt.indexOf("windows 16-bit")!=-1));

var is_winme = ((agt.indexOf("win 9x 4.90")!=-1));
var is_win2k = ((agt.indexOf("windows nt 5.0")!=-1));
var is_win98 = ((agt.indexOf("win98")!=-1) || (agt.indexOf("windows 98")!=-1));
var is_winnt = ((agt.indexOf("winnt")!=-1) || (agt.indexOf("windows nt")!=-1));
var is_win32 = (is_win95 || is_winnt || is_win98 ||
				  ((is_major >= 4) && (navigator.platform == "Win32")) ||
				  (agt.indexOf("win32")!=-1) || (agt.indexOf("32bit")!=-1));

var is_os2   = ((agt.indexOf("os/2")!=-1) ||
				  (navigator.appVersion.indexOf("OS/2")!=-1) ||
				  (agt.indexOf("ibm-webexplorer")!=-1));

var is_mac    = (agt.indexOf("mac")!=-1);
if (is_mac && is_ie5up) is_js = 1.4;
var is_mac68k = (is_mac && ((agt.indexOf("68k")!=-1) ||
								 (agt.indexOf("68000")!=-1)));
var is_macppc = (is_mac && ((agt.indexOf("ppc")!=-1) ||
								  (agt.indexOf("powerpc")!=-1)));

var is_sun   = (agt.indexOf("sunos")!=-1);
var is_sun4  = (agt.indexOf("sunos 4")!=-1);
var is_sun5  = (agt.indexOf("sunos 5")!=-1);
var is_suni86= (is_sun && (agt.indexOf("i86")!=-1));
var is_irix  = (agt.indexOf("irix") !=-1);    // SGI
var is_irix5 = (agt.indexOf("irix 5") !=-1);
var is_irix6 = ((agt.indexOf("irix 6") !=-1) || (agt.indexOf("irix6") !=-1));
var is_hpux  = (agt.indexOf("hp-ux")!=-1);
var is_hpux9 = (is_hpux && (agt.indexOf("09.")!=-1));
var is_hpux10= (is_hpux && (agt.indexOf("10.")!=-1));
var is_aix   = (agt.indexOf("aix") !=-1);      // IBM
var is_aix1  = (agt.indexOf("aix 1") !=-1);
var is_aix2  = (agt.indexOf("aix 2") !=-1);
var is_aix3  = (agt.indexOf("aix 3") !=-1);
var is_aix4  = (agt.indexOf("aix 4") !=-1);
var is_linux = (agt.indexOf("inux")!=-1);
var is_sco   = (agt.indexOf("sco")!=-1) || (agt.indexOf("unix_sv")!=-1);
var is_unixware = (agt.indexOf("unix_system_v")!=-1);
var is_mpras    = (agt.indexOf("ncr")!=-1);
var is_reliant  = (agt.indexOf("reliantunix")!=-1);
var is_dec   = ((agt.indexOf("dec")!=-1) || (agt.indexOf("osf1")!=-1) ||
	  (agt.indexOf("dec_alpha")!=-1) || (agt.indexOf("alphaserver")!=-1) ||
	  (agt.indexOf("ultrix")!=-1) || (agt.indexOf("alphastation")!=-1));
var is_sinix = (agt.indexOf("sinix")!=-1);
var is_freebsd = (agt.indexOf("freebsd")!=-1);
var is_bsd = (agt.indexOf("bsd")!=-1);
var is_unix  = ((agt.indexOf("x11")!=-1) || is_sun || is_irix || is_hpux ||
			  is_sco ||is_unixware || is_mpras || is_reliant ||
			  is_dec || is_sinix || is_aix || is_linux || is_bsd || is_freebsd);

var is_vms   = ((agt.indexOf("vax")!=-1) || (agt.indexOf("openvms")!=-1));

//--> end hide JavaScript
</script>
<?
}

function g_Menu_treemenu()
{
?>
<script language='javascript'>
function TreeMenu(layer, iconpath, myname, linkTarget)
{
this.layer      = layer;
this.iconpath   = iconpath;
this.myname     = myname;
this.linkTarget = linkTarget;
this.n          = new Array();

this.branches       = new Array();
this.branchStatus   = new Array();
this.layerRelations = new Array();
this.childParents   = new Array();

this.drawMenu           = drawMenu;
this.toggleBranch       = toggleBranch;
this.swapImage          = swapImage;
this.doesMenu           = doesMenu;
this.doesPersistence    = doesPersistence;
this.getLayer           = getLayer;
this.saveExpandedStatus = saveExpandedStatus;
this.loadExpandedStatus = loadExpandedStatus;
this.resetBranches      = resetBranches;
}

function TreeNode(title, icon, link, hint, expanded)
{
this.title    = title;
this.icon     = icon;
this.link     = link;
this.hint     = hint;
this.expanded = expanded;
this.n        = new Array();
}

function preloadImages()
{
var plustop    = new Image; plustop.src    = this.iconpath + '/plustop.gif';
var plusbottom = new Image; plusbottom.src = this.iconpath + '/plusbottom.gif';
var plus       = new Image; plus.src       = this.iconpath + '/plus.gif';

var minustop    = new Image; minustop.src    = this.iconpath + '/minustop.gif';
var minusbottom = new Image; minusbottom.src = this.iconpath + '/minusbottom.gif';
var minus       = new Image; minus.src       = this.iconpath + '/minus.gif';
}

function drawMenu()
{
var output        = '';
var modifier      = '';
var layerID       = '';
var parentLayerID = '';

var nodes         = arguments[0] ? arguments[0] : this.n
var level         = arguments[1] ? arguments[1] : [];
var prepend       = arguments[2] ? arguments[2] : '';
var expanded      = arguments[3] ? arguments[3] : false;
var visibility    = arguments[4] ? arguments[4] : 'inline';
var parentLayerID = arguments[5] ? arguments[5] : null;

var currentlevel  = level.length;
var primeiro = 0;
for (var i=0; i<nodes.length; i++) {

	level[currentlevel] = i+1;
	layerID = this.layer + '_' + 'node_' + implode('_', level);

	this.childParents[layerID] = parentLayerID;

	if (i == 0 && parentLayerID == null) {
		modifier = nodes.length > 1 ? "top" : 'single';
	} else if(i == (nodes.length-1)) {
		modifier = "bottom";
	} else {
		modifier = "";
	}

	if (primeiro==0)
	{
		primeiro=1;
		expanded = true;
	} else
	{
		expanded = false;
	}
	if (!doesMenu() || (parentLayerID == null && nodes.length == 1)) {
		expanded = true;
	}
	if (nodes[i].n.length > 1) {
		this.branchStatus[layerID] = expanded;
		this.branches[this.branches.length] = layerID;
	}
	if (!this.layerRelations[parentLayerID]) {
		this.layerRelations[parentLayerID] = new Array();
	}
	this.layerRelations[parentLayerID][this.layerRelations[parentLayerID].length] = layerID;

	var gifname = nodes[i].n.length && this.doesMenu() ? (expanded ? 'minus' : 'plus') : 'branch';
	//var iconimg = nodes[i].icon ? sprintf('<img src="%s/%s" align="top">', this.iconpath, nodes[i].icon) : '';
	var iconimg = nodes[i].icon ? sprintf('<img src="'+this.iconpath+'/'+nodes[i].icon+'" align="top">') : '';
	var divTag    = sprintf('<div id="%s" style="display: %s; ">', layerID, visibility);
	var onClick   = doesMenu() && nodes[i].n.length ? sprintf('onclick="%s.toggleBranch(\'%s\', true)" style="cursor: pointer; cursor: hand"', this.myname, layerID) : '';
	var imgTag    = sprintf('<img src="%s/%s%s.gif" align="top" border="0" name="img_%s" %s />', this.iconpath, gifname, modifier, layerID, onClick);
	var linkStart = nodes[i].link ? sprintf('<a class="menu" title="%s" href="%s" target="screen">', nodes[i].hint, nodes[i].link) : '';
	var linkEnd   = nodes[i].link ? '</a>' : '';

	output = sprintf('%s<nobr>%s%s%s%s%s%s</nobr><br></div>',
							divTag,
					  prepend,
							parentLayerID == null && nodes.length == 1 ? '' : imgTag,
					  iconimg,
					  linkStart,
					  nodes[i].title,
					  linkEnd);
	if (this.doesMenu()) {
		this.getLayer(this.layer).innerHTML += output

	} else {
		document.write(output);
	}

	if (nodes[i].n.length) {
		if (parentLayerID == null && nodes.length == 1) {
			var newPrepend = '';

		} else if (i < (nodes.length - 1)) {
			var newPrepend = prepend + sprintf('<img src="%s/line.gif" align="top">', this.iconpath);

		} else {
			var newPrepend = prepend + sprintf('<img src="%s/linebottom.gif" align="top">', this.iconpath);
		}

		this.drawMenu(nodes[i].n,
						  explode('_', implode('_', level)), 
						  newPrepend,
						  nodes[i].expanded,
						  expanded ? 'inline' : 'none',
						  layerID);
	}
}
}

function toggleBranch(layerID, updateStatus) 
{
var currentDisplay = this.getLayer(layerID).style.display;
var newDisplay     = (this.branchStatus[layerID] && currentDisplay == 'inline') ? 'none' : 'inline'
for (var i=0; i<this.layerRelations[layerID].length; i++) {
	if (this.branchStatus[this.layerRelations[layerID][i]]) {
		this.toggleBranch(this.layerRelations[layerID][i], false);
	}
	this.getLayer(this.layerRelations[layerID][i]).style.display = newDisplay;
}
if (updateStatus) {
	this.branchStatus[layerID] = !this.branchStatus[layerID];
	if (this.doesPersistence() && !arguments[2]) {
		this.saveExpandedStatus(layerID, this.branchStatus[layerID]);
	}
	this.swapImage(layerID);
}
}

function swapImage(layerID)
{
imgSrc = document.images['img_' + layerID].src;

re = /^(.*)(plus|minus)(bottom|top|single)?.gif$/
if (matches = imgSrc.match(re)) {
	if (matches[2] == 'plus' )
	{
		x='minus'
	} else
	{
		x='plus';
	}
	//document.images['img_' + layerID].src = matches[1]+x+matches[3]+'.gif';
	document.images['img_' + layerID].src = matches[1]+x+'.gif';
}
}

function doesMenu()
{
return (is_ie5up || is_nav6up || is_gecko);
}

function doesPersistence()
{
return is_ie5up;
}

function getLayer(layerID)
{
if (document.getElementById(layerID)) {
	return document.getElementById(layerID);
} else if (document.all(layerID)) {
	return document.all(layerID);
}
}

function saveExpandedStatus(layerID, expanded)
{
document.all(layerID).setAttribute("expandedStatus", expanded);
document.all(layerID).save(layerID);
}

function loadExpandedStatus(layerID)
{
document.all(layerID).load(layerID);
if (val = document.all(layerID).getAttribute("expandedStatus")) {
	return val;
} else {
	return null;
}
}

function resetBranches()
{
if (!this.doesPersistence()) {
	return false;
}

for (var i=0; i<this.branches.length; i++) {
	var status = this.loadExpandedStatus(this.branches[i]);
	// Only update if it's supposed to be expanded and it's not already
	if (status == 'true' && this.branchStatus[this.branches[i]] != true) {
		if (this.childParents[this.branches[i]] == null || (in_array(this.childParents[this.branches[i]], this.branches) && this.branchStatus[this.childParents[this.branches[i]]])) {
			this.toggleBranch(this.branches[i], true, true);
		} else {
			this.branchStatus[this.branches[i]] = true;
			this.swapImage(this.branches[i]);
		}
	}
}
}

function sprintf(strInput)
{
var strOutput  = '';
var currentArg = 1;

for (var i=0; i<strInput.length; i++) {
	if (strInput.charAt(i) == '%' && i != (strInput.length - 1) && typeof(arguments[currentArg]) != 'undefined') {
		switch (strInput.charAt(++i)) {
			case 's':
				strOutput += arguments[currentArg];
				break;
			case '%':
				strOutput += '%';
				break;
		}
		currentArg++;
	} else {
		strOutput += strInput.charAt(i);
	}
}

return strOutput;
}

function explode(seperator, input)
{
var output = [];
var tmp    = '';
skipEmpty  = arguments[2] ? true : false;

for (var i=0; i<input.length; i++) {
	if (input.charAt(i) == seperator) {
		if (tmp == '' && skipEmpty) {
			continue;
		} else {
			output[output.length] = tmp;
			tmp = '';
		}
	} else {
		tmp += input.charAt(i);
	}
}
if (tmp != '' || !skipEmpty) {
	output[output.length] = tmp;
}

return output;
}

function implode(seperator, input)
{
var output = '';

for (var i=0; i<input.length; i++) {
	if (i == 0) {
		output += input[i];
	} else {
		output += seperator + input[i];
	}
}

return output;
}

function in_array(item, arr)
{
for (var i=0; i<arr.length; i++) {
	if (arr[i] == item) {
		return true;
	}
}

return false;
}
</script>
<?
}

class HTML_TreeMenu
{
	var $items;
	var $layer;
	var $images;
	var $menuobj;

	function HTML_TreeMenu($layer, $images, $linkTarget = '_self')
	{
		//$this->menuobj    = 'objTreeMenu';
		$this->menuobj    = 'o'.$layer;
		$this->layer      = $layer;
		$this->images     = $images;
		$this->linkTarget = $linkTarget;
	}

	function &addItem($menu)
	{
		$this->items[] = $menu;
		return $this->items[count($this->items) - 1];
	}

	function printMenu()
	{
		echo "\n";

 		echo '<script language="javascript" type="text/javascript">' . "\n\t";
		echo sprintf('%s = new TreeMenu("%s", "%s", "%s", "%s");',
		             $this->menuobj,
					 $this->layer,
					 $this->images,
					 $this->menuobj,
					 $this->linkTarget);

		echo "\n";

		if (isset($this->items)) {
			for ($i=0; $i<count($this->items); $i++) {
				$this->items[$i]->_printMenu($this->menuobj . ".n[$i]");
			}
		}

 		echo sprintf("%s.drawMenu();\n%s.resetBranches();\n</script>", $this->menuobj, $this->menuobj);
	}

} // HTML_TreeMenu

class HTML_TreeNode
{
	var $text;
	var $link;
	var $icon;
	var $hint;
	var $items;
	var $expanded;

	function HTML_TreeNode($text, $link,  $icon = null, $hint = "" , $expanded = false)
	{
		$this->text     = ($text == null) ? "" : $text;
		$this->link     = ($link == null) ? "" : $link;
		$this->icon     = ($icon == null) ? "" : $icon;
		$this->hint     = $hint;
		$this->expanded = $expanded;
	}

	function &addItem($node)
	{
		$this->items[] = $node;
		return $this->items[count($this->items) - 1];
	}

	function _printMenu($prefix)
	{
		echo sprintf("\t%s = new TreeNode('%s', %s, %s, %s, %s);\n",
		             $prefix,
		             $this->text,
		             !empty($this->icon) ? "'" . $this->icon . "'" : 'null',
		             !empty($this->link) ? "'" . $this->link . "'" : 'null',
		             !empty($this->hint) ? "'" . $this->hint. "'" : 'null',
					 $this->expanded ? 'true' : 'false');

		if (!empty($this->items)) {
			for ($i=0; $i<count($this->items); $i++) {
				$this->items[$i]->_printMenu($prefix . ".n[$i]");
			}
		}
	}
}


class gMenu extends gInput
{

	function gShowMenu($style=gM_DEFAULT)
	{
		global $PathDefault;
		global $usr_id;
		
		global $usr_funcionario;
		global $usr_cliente;
		global $usr_fornecedor;
		global $usr_terceirizado;
		global $usr_concorrente;

		global $empr_agente;
		global $empr_cliente;
		global $empr_fornecedor;
		global $empr_terceirizado;
		global $empr_concorrente;
		
		global $http_img;
		global $http_base;

		$thispage=$_SERVER["PHP_SELF"];
		$gParam=$_REQUEST["gParam"];
		$gProcess=$_REQUEST["gProcess"];
		$gSubProcess=$_REQUEST["gSubProcess"];
		
		$cli=$empr_cliente | $usr_cliente;
		$for=$empr_fornecedor | $usr_fornecedor;
		$ter=$empr_terceirizado | $usr_terceirizado;
		$con=$empr_concorrente | $usr_concorrente;
		$fun=$usr_funcionario | $usr_id==1;
	
		$browser=$_SERVER["HTTP_USER_AGENT"];
		if ($gParam=="")
		{
			$border=0;
			if (strpos($browser,"MSIE")>0)
			{
				$altura=27;
				//$altura=27;
			} else
			{
				$altura=25;
				//$altura=27;
			}

			if ($style==gM_DEFAULT)
			{

			}
			//$altura=62;
			?>
			<script>
if (self.parent.frames.length >= 2)
				self.parent.location = document.location;
			</script>
			<?
			$this->gOut("<html><head><title>".gVar("global.site")."</title></head>");
			$this->gOut("<frameset bgcolor='white' border=$border rows=0,$altura,$altura,*>");
			//$this->gOut("<frameset bgcolor='white' border=$border rows=0,$altura,*>");
			$this->gOut("  <frame name='hidden'>");
			$this->gOut("  <frame name='topbar' src='$thispage?gParam=topbar'>");
			$this->gOut("  <frame name='bottombar' src='$thispage?gParam=bottombar'>");
			$this->gOut("  <frameset border=$border cols=188,*>");
			$this->gOut("    <frame name='menu' src='$thispage?gParam=menu'>");
			$this->gOut("    <frame name='screen' src='index.php'>");
			$this->gOut("  </frameset>");
			$this->gOut("</frameset>");
			$this->gOut("</html>");
	// ************************
		} elseif ($gParam=="topbar")
		{
			$this->gOut(gTag("menu.topbar_start"));
			$this->gOut("<link href='$http_img/schema/".gVar("global.schema")."/topbar.css' rel='stylesheet' type='text/css'>");
			$admin="";
			//if (gSessionLoad("usr_id")==1) $admin="bgcolor='#800000'";
			$s="<table width='100%' border='0' $admin><tr><td width='35%' align='left'>".gSessionLoad("usr_nome")."</td><td width='30%' align='center'><b>".gVar("global.site")."</b></td><td width='35%' align='right'>".gSessionLoad("empr_nome")."</td></tr></table>";
			$this->gOut($s);
			$this->gOut(gTag("menu.topbar_end"));
	// ************************
		} elseif ($gParam=="bottombar")
		{
			$this->gOut(gTag("menu.bottombar_start"));
			$this->gOut("<link href='$http_img/schema/".gVar("global.schema")."/bottombar.css' rel='stylesheet' type='text/css'>");
			$sel="";
			$prs=gQuery("Select * from geral_configuracao");
			// Perfis: (F)luxo (I)ntegração (G)erência (1) Funcionário (2) Cliente (3) Fornecedor
			$uni=false;
			$filtro="";
			if ($cli) 
			{
				$gProcess="2";
				$filtro.=" sigla like 'C%' or ";
			}
			if ($for) 
			{
				$gProcess="3";
				$filtro.=" sigla like 'F%' or ";
			}
			if ($age) 
			{
				$gProcess="4";
				$filtro.=" sigla like 'A%' or ";
			}
			if ($filtro<>"")
				$filtro="(".substr($filtro,0,strlen($filtro)-3).") ";
			else
				$filtro.=" 1 ";
			//echo "--- $gProcess ----";
			if (($gProcess+0<=1) && (($prs->fields['mostrar_gerencia']+$prs->fields['mostrar_integracao']+$prs->fields['mostrar_fluxo'])==0))
				$uni=true;
			if (($prs->fields['mostrar_fluxo']==0) && ($gProcess=="F")) $gProcess="I";
			if (($prs->fields['mostrar_integracao']==0) && ($gProcess=="I")) $gProcess="G";
			if (($prs->fields['mostrar_gerencia']==0) && ($gProcess=="G")) $gProcess="1";
			$this->gOut("<table width='100%' cellpadding='0' cellspacing='0'><tr>");
			//$sql="Select * from geral_links where sigla like '___' and invisivel=0 and ((cliente & $cli) or (fornecedor & $for) or (terceirizado & $ter) or ($fun)) order by sigla";
			//$rst=gQuery($sql);
			if (strpos($browser,"MSIE")>0)
			{
				$this->gOut("<td class='menu' bgcolor='#e0e0e0' align='center' onClick=\"self.parent.location ='../".gVar("page.logout")."'\">");
				$this->gOut("<a class='menu' target='menu' href='#' onClick=\"self.parent.location ='../".gVar("page.logout")."'\">Sair</a>");
				$this->gOut("</td>");

			} else
			{
				$this->gOut("<td class='menu' bgcolor='#e0e0e0' align='center' onClick=\"self.parent.location ='../".gVar("page.logout")."'\">");
				$this->gOut("<acronym title='Sair do sistema'>Sair</acronym>");
				$this->gOut("</td>");
			}
			//$this->gOut("<td width='10'><acronym title='".gLng("logout.long")."'><input type='button' value='".gLng("logout.short")."' onclick=\"self.parent.location ='../".gVar("page.logout")."'\"></acronym></td>");
			//$this->gOut("<td width='2'></td>");

			if ($gProcess=="1")  $sel="class='sel'";
			if (!$uni)
			{
				if ($gProcess=="F")  $sel="class='sel'";
				if (($prs->fields['mostrar_fluxo']==1) && (!$cli) && (!$for))
				{
					$this->gOut("<td width='10'><input $sel type='button' value='".$prs->fields['nome_fluxo']."' onclick=javascript:window.location.href='$thispage?gParam=bottombar&gProcess=F'></td>");
					$this->gOut("<td width='2'></td>");
					$sel="";
				}
				if ($gProcess=="N") $sel="class='sel'";
				if (($prs->fields['mostrar_negocio']==1) && (!$cli) && (!$for))
				{
					$this->gOut("<td width='10'><input $sel type='button' value='".$prs->fields['nome_negocio']."' onclick=javascript:window.location.href='$thispage?gParam=bottombar&gProcess=N'></td>");
					$this->gOut("<td width='2'></td>");
					$sel="";
				}
				if ($gProcess=="I") $sel="class='sel'";
				if (($prs->fields['mostrar_integracao']==1) && (!$cli) && (!$for))
				{
					$this->gOut("<td width='10'><input $sel type='button' value='".$prs->fields['nome_integracao']."' onclick=javascript:window.location.href='$thispage?gParam=bottombar&gProcess=I'></td>");
					$this->gOut("<td width='2'></td>");
					$sel="";
				}
				if ($gProcess=="G") $sel="class='sel'";
				if (($prs->fields['mostrar_gerencia']==1) && (!$cli) && (!$for))
				{
	
					$this->gOut("<td width='10'><input $sel type='button' value='".$prs->fields['nome_gerencia']."' onclick=javascript:window.location.href='$thispage?gParam=bottombar&gProcess=G'></td>");
					$this->gOut("<td width='2'></td>");
				}
			} 
			//$this->gOut("<td>");
			if ($cli || $for)
			{
				$sql="SELECT sigla,nome,descricao from geral_links where invisivel=0 and LENGTH(sigla)=3 and $filtro order by sigla";
			} elseif ($usr_id<=3)
			{
				$sql="SELECT sigla,nome,descricao from geral_links where invisivel=0 and LENGTH(sigla)=3 order by sigla";
			} else
			{
				$sql="SELECT sigla,nome,descricao from geral_links where invisivel=0 and sigla=any (select distinct LEFT(sigla_geral_links,3) as sigla from geral_links_permissoes where (id_pes_funcoes=0 and id_pes_setores=0 ";
				$sql.="or id_pes_setores=any (Select s.id from pes_funcionalismo f left join pes_funcionalismo_setores fs on f.id=fs.id_pes_funcionalismo left join pes_setores s on fs.id_pes_setores=s.id where f.id_geral_pessoas=$usr_id)";
				$sql.="or id_pes_funcoes=any (Select s.id from pes_funcionalismo f left join pes_funcionalismo_funcoes fs on f.id=fs.id_pes_funcionalismo left join pes_funcoes s on fs.id_pes_funcoes=s.id where f.id_geral_pessoas=$usr_id))) order by sigla";
			}
			$rst=gQuery($sql);
			$tam=round(100/($rst->RecordCount()+1),0);
			while (!$rst->EOF)
			{
				
				//$rstp=gQuery("select id from geral_links_permissoes where and sigla_geral_links like '".$rst->fields['sigla']."%' ");
				//$rstp=gQuery($sql);
				
				//if ((strpos($rst->fields['sigla'],'.')==0) && (!$rstp->EOF || ($usr_id==1)))
				{
					if (strpos($browser,"MSIE")>0)
					{
						$this->gOut("<td class='menu' bgcolor='#e0e0e0' align='center' width='$tam%' onClick='window.open(\"$thispage?gParam=menu&gProcess=".$rst->fields['sigla']."\",\"menu\")'>");
						$this->gOut("<acronym title='".$rst->fields['descricao']."'><a class='menu' target='menu' href='$thispage?gParam=menu&gProcess=".$rst->fields['sigla']."'>".$rst->fields['nome']."</a></acronym>");
						$this->gOut("</td>");
	
					} else
					{
						$this->gOut("<td class='menu' bgcolor='#e0e0e0' align='center' width='$tam%' onClick='window.open(\"$thispage?gParam=menu&gProcess=".$rst->fields['sigla']."\",\"menu\")'>");
						//$this->gOut("<acronym title='".$rst->fields['descricao']."'>$ximg".$rst->fields['nome']."</acronym>");
						$this->gOut($rst->fields['nome']);
						$this->gOut("</td>");
					}
				}
				$rst->MoveNext();
			}
//			$this->gOut("</tr></table></td>");
			$this->gOut("</tr></table>");
			$this->gOut(gTag("menu.bottombar_end"));
	// ************************
		} elseif ($gParam=="menu")
		{
			$mcnt=0;
			$menucnt=0;
			$this->gOut(gTag("menu.menu_start"));
			$this->gOut("<link href='$http_img/schema/".gVar("global.schema")."/menu.css' rel='stylesheet' type='text/css'>");
			$usr_id=gSessionLoad("usr_id");
			$usr_setores=gSessionLoad("usr_setores");
			$usr_funcoes=gSessionLoad("usr_funcoes");
			if ($usr_id>0)
			{			
				$this->gOut("<form action='$thispage' method='post'>");				
				$this->gOut("<div class='menusel' align='center'>");
				//$this->gOut("<table width='170' cellpadding='1' cellspacing='1'><tr valign='middle'><td align='center' bgcolor='#3F87C2'>");
				$this->gOut("<img src='$http_img/schema/".gVar("global.schema")."/mini_logo.jpg' border='0'><br>");
				$sql="select imagem,imagem_altura,imagem_largura from geral_pessoas where id=$usr_id";
				$rsimg=gQuery($sql);

				if ($rsimg->fields["imagem"]!="")
				{
					$w=$rsimg->fields['imagem_largura'];
					$h=$rsimg->fields['imagem_altura'];
					$nw=$w;
					$nh=$h;
					$wm=$w>168?168:$w;
					if ($w>$wm)
					{
						$nw=$wm;
						$nh=($wm*$h)/$w;
					}
					$this->gOut("<img src='$http_base/online/i03_adm/cad_pessoas_img.php?gId=$usr_id' width='$nw' height='$nh' border='0'><br>");
				}
				//$this->gImage("minilogo.png");
				//$this->gOut("</td></tr><tr valign='middle'><td align='center' bgcolor='#e0e0e0'>");
				//$icon="folder.gif";
				g_Menu_sniffer();
				g_Menu_treemenu();
				$this->gOut("<input type='hidden' name='gParam' value='menu'>");
				//$this->gOut(gLng("global.menu.short"));$this->gBr();
				$prs=gQuery("Select * from geral_configuracao");
				if ($gProcess=="")
				{
					$gProcess="I00";
					if ($cli) $gProcess="C01";
					if ($for) $gProcess="F01";
				}
				/*
				if (($prs->fields['mostrar_fluxo']==0) && ($gProcess=="F01")) $gProcess="I01";
				if (($prs->fields['mostrar_integracao']==0) && ($gProcess=="I01")) $gProcess="G01";
				if (($prs->fields['mostrar_gerencia']==0) && ($gProcess=="G01")) $gProcess="C01";
				*/

 				// Preenche array com permissões
				if ($usr_id>3)
				{
					$sql="Select * from geral_links_permissoes where sigla_geral_links like '".substr($gProcess,0,3)."%' order by sigla_geral_links";
					$rst=gQuery($sql);
					$perm="";
					while (!$rst->EOF)
					{
						$faz=false;
						if (($rst->fields['id_pes_setores']==0) && ($rst->fields['id_pes_funcoes']==0))
						{
							$faz=true;
						} elseif (($rst->fields['id_pes_setores']<>0) && ($rst->fields['id_pes_funcoes']<>0))
						{
							foreach ($usr_setores as $usr_setor)
							{
								if ($rst->fields['id_pes_setores']==$usr_setor[0])  $faz=true;
							}
							if ($faz)
							{
								$faz=false;
								foreach ($usr_funcoes as $usr_funcao)
								{
									if ($rst->fields['id_pes_funcoes']==$usr_funcao[0])  $faz=true;
								}
							}
						} else
						{
							foreach ($usr_setores as $usr_setor)
							{
								if (($rst->fields['id_pes_setores']==$usr_setor[0]) && ($rst->fields['id_pes_funcoes']==0)) $faz=true;
							}
							foreach ($usr_funcoes as $usr_funcao)
							{
								if (($rst->fields['id_pes_funcoes']==$usr_funcao[0]) && ($rst->fields['id_pes_setores']==0)) $faz=true;
							}
						}
						if ($faz)
						{
							$perm[]=array(trim($rst->fields['sigla_geral_links']),$rst->fields['id_pes_funcoes'],$rst->fields['id_pes_setores'],$rst->fields['ler'],$rst->fields['inserir'],$rst->fields['editar'],$rst->fields['remover']);
						}
						$rst->MoveNext();
					}
				}
				//echo "<pre>";print_r($perm);echo "</pre>";
				if ($perm=="")
					$perm[]="";
				$sql="Select * from geral_links where (sigla like '".substr($gProcess,0,3).".__' or sigla='".substr($gProcess,0,3)."') and invisivel=0 order by sigla";
				$rst=gQuery($sql);
				if (!$rst->EOF)
				{
					$this->gMsg("<b><font size='1'>".$rst->fields['nome']."</font></b><br>");
					$rst->MoveNext();
					$sub="x";
					$s="";
					$primeiro="";
					$ssel="";
					while (!$rst->EOF)
					{
						if ($primeiro=="") $primeiro=$rst->fields['sigla'];
						$sel="";
						if ($rst->fields['sigla']==$gProcess)
						{
							$sel="selected ";
							$primeiro=$rst->fields['sigla'];
						}
						
						$criamenu=false;
						for ($p=0;$p<count($perm);$p++)
						{			
							if (substr($perm[$p][0],0,strlen($rst->fields['sigla']))==$rst->fields['sigla'])
							{
								$criamenu=true;
							}
						}
						if (($criamenu) || ($usr_id<=3) || $cli || $for)
						{
							$mcnt++;
							$s.="<option ".$sel."value='$mcnt'>".$rst->fields['nome']."</option>";
							$ssel.="sigla like '".$rst->fields['sigla']."%' or ";
						}
						$fez=true;
						$rst->MoveNext();
					}
					if ($primeiro=="") $primeiro=$gProcess;
					if ($s<>"")
					{
						$this->gOut("<select name='gProcess' onChange='AtualizaMenu()'>");
						$this->gOut($s);
						$this->gOut("</select>");
					}
					//$this->gBr(1);
					$this->gOut("</div>");
					if (gVar("global.showcalendar")=="true")
					{
						$this->gOut("<div class='calendar'>");
						calendario(date("d"),date("m"),date("Y"),"i00_uti/agenda_verdia.php");
						$this->gOut("</div>");
					}
					if ($ssel<>"") 
						$ssel=substr($ssel,0,strlen($ssel)-3);
					else 
					{
						$ssel="sigla like '$gProcess%'";
					}
					
					// Desenha menu em árvore
					//$sql="Select * from geral_links where sigla like '".substr($gProcess,0,3)."%' and invisivel=0 order by sigla";
					$sql="Select * from geral_links where ($ssel) and invisivel=0 order by sigla";
					$rst=gQuery($sql);
					if (!$rst->EOF)
					{
						$crioumenu=false;
						$ultmenu="x";
						$c=0;
						while (!$rst->EOF)
						{
							unset($menu);
							
							if (strlen($rst->fields['sigla'])==6)
							{
								
							// Item de menu
								$sub="x";
								$criamenu=false;
								for ($p=0;$p<count($perm);$p++)
								{			
									if (substr($perm[$p][0],0,strlen($rst->fields['sigla']))==$rst->fields['sigla'])
									{
										$criamenu=true;
									}
								}
								//gLog("-- sigla: ".$rst->fields['sigla']." -- criamenu: $criamenu cli: $cli for: $for");
								if (($criamenu) || ($usr_id<=3) || ($cli) || ($for))
								{
									$menucnt++;
									$menu  = new HTML_TreeMenu("menuLayer$menucnt", '../inc_2.0/imagesAlt');
									$crioumenu=true;
									$rst->MoveNext();
									while ((!$rst->EOF) && (strlen($rst->fields['sigla'])==9))
									{
									// Link
										$criasubmenu=false;
										$subler=0;
										$subins=0;
										$subedi=0;
										$subapa=0;
										$subtip=0; // 1 funcao, 2 setor
										for ($p=0;$p<count($perm);$p++)
										{
											
											if (substr($perm[$p][0],0,strlen($rst->fields['sigla']))==$rst->fields['sigla'])
											{
												$criasubmenu=true;
												/*  Ordem de prioridades para as permissões:
													 Acesso geral (sem definição de setor ou função) - baixa
													 Setor - média
													 Função - prioritária
												*/
												if (($subtip==0) || (($subtip==2) && ($perm[$p][1]>0)))
												{
													if ($perm[$p][2]>0) $subtip=2;
													if ($perm[$p][1]>0) $subtip=1;
													$subler=$perm[$p][3];
													$subins=$perm[$p][4];
													$subedi=$perm[$p][5];
													$subapa=$perm[$p][6];
												}
											}
										}
										if (($criasubmenu) || ($usr_id<=3) || ($cli) || ($for))
										{
											$desc="&nbsp;";
											if ($rst->fields['descricao']<>"") $desc=$rst->fields['descricao'];
											$node1 = new HTML_TreeNode($rst->fields['nome'], $thispage."?gParam=help&gProcess=".$rst->fields['sigla'], $icon,$desc);
											$rst->MoveNext();
											while ((!$rst->EOF) && (strlen($rst->fields['sigla'])==12))
											{
												for ($p=0;$p<count($perm);$p++)
												{
													if (substr($perm[$p][0],0,strlen($rst->fields['sigla']))==$rst->fields['sigla'])
													{
														$criasubmenu=true;
														/*  Ordem de prioridades para as permissões:
															Acesso geral (sem definição de setor ou função) - baixa
															Setor - média
															Função - prioritária
														*/
														if (($subtip==0) || (($subtip==2) && ($perm[$p][1]>0)))
														{
															if ($perm[$p][2]>0) $subtip=2;
															if ($perm[$p][1]>0) $subtip=1;
															$subler=$perm[$p][3];
															$subins=$perm[$p][4];
															$subedi=$perm[$p][5];
															$subapa=$perm[$p][6];
														}
													}
												}
												$lnk=$rst->fields['link'];
												$crialink=false;

												if ((strpos($lnk,"gAction=new")>0) && ($subins)) $crialink=true;
												if ((strpos($lnk,"gAction=edit")>0) && ($subedi || $subapa)) $crialink=true;
												if ((strpos($lnk,"gAction=perm")>0) && ($subedi || $subapa)) $crialink=true;
												if ((strpos($lnk,"gAction=prop")>0) && ($subedi || $subapa)) $crialink=true;
												if ((strpos($lnk,"gAction=list")>0) && ($subler)) $crialink=true;
												if (strpos($lnk,"gAction")==0) $crialink=true;
												$desc="&nbsp;";
												if ($rst->fields['descricao']<>"") $desc=$rst->fields['descricao'];
												if (($crialink) || ($usr_id<=3) || ($cli) || ($for))
													$node1->addItem(new HTML_TreeNode($rst->fields['nome'], $lnk, "seta.gif", $desc));
												$rst->MoveNext();
											}
											$menu->addItem($node1);
										} else
										{
											$rst->MoveNext();
											while ((!$rst->EOF) && (strlen($rst->fields['sigla'])<>9))
												$rst->MoveNext();
										}
									}
								}
							}
							//if (is_object($menu) && ($ultmenu<>$menucnt))
							//if (is_object($menu) && (($rst->EOF) || (strlen($rst->fields['sigla'])==6) ))
							if (isset($menu) )
							{
								//echo "<div id='menuLayer$menucnt' style='width: 0px; height: 0px; visibility: hidden; position:absolute; left:0px; top:0px; width:0px; z-index:30$menucnt'></div>";
								//if (gVar("global.showcalendar")=="true")
								//	echo "<div class='menuboxcal'  id='menuLayer$menucnt'></div>";
								//else
								echo "<div style='position: relative; padding: 0px'><div class='menubox'  id='menuLayer$menucnt'>";
								$menu->printMenu();
								echo "</div></div>";
							} else
							{
								$rst->MoveNext();
							}
						}
						if ($crioumenu)
						{
							//echo "<span id='menudinamico'></span>";
						} else
						{
							$this->gMsg("&nbsp;".gLng("nothing_to_show.long"));
						}
					}
					
				}
?>
<script language='javascript'>

function AtualizaMenu()
{
id=window.document.forms[0].gProcess.value;
<?
for ($a=1; $a<=$menucnt; $a++)
{
?>
	document.getElementById("menuLayer<?echo $a;?>").style.visibility='hidden';
	if (id==<?echo $a;?>) 
	{
		document.getElementById("menuLayer<?echo $a;?>").style.visibility='visible';
	}
<?
}
?>
}
AtualizaMenu();
</script>
<?				
			} else
			{
				gExpire();
			}
			$this->gOut(gTag("menu.menu_end"));
	// ************************
		} elseif ($gParam=="help")
		{
			$this->gBegin();
			$sql="Select * from geral_links where sigla like '$gProcess%' and invisivel=0 order by sigla";
			$rst=gQuery($sql);
			$this->gMsgTitle(gLng("help.long"));
			$this->gOut("<hr>");
			$this->gMsg($rst->fields['nome']." (".$rst->fields['sigla'].")<br>".$rst->fields['descricao']."",3);
			if ($rst->fields['ajuda']<>'')
				$this->gOut(nl2br($rst->fields['ajuda']));
			$rst->MoveNext();
			if (!$rst->EOF)
			{
				$this->gMsg("Sub-itens deste link:");
				$this->gOut("<ul>");
				while (!$rst->EOF)
				{
					$this->gMsg("<li><a class='help' href='".$rst->fields['link']."'><b>".$rst->fields['nome']." (".$rst->fields['sigla'].")</b><br>".$rst->fields['descricao']."</a>");
					if ($rst->fields['ajuda']<>'')
						$this->gOut("".nl2br($rst->fields['ajuda'])."<br>&nbsp;");
					$this->gOut("</li>");
					$rst->MoveNext();
				}
				$this->gOut("</ul>");
			}
			$this->gEnd();
		} elseif ($gParam=="exe")
		{
			$this->gBegin();
			$sql="Select * from geral_links where sigla like '$gProcess%' and invisivel=0 order by sigla";
			$rst=gQuery($sql);
			$id_processo=$_REQUEST['id_processo'];
			$id_roteiro=$_REQUEST['id_roteiro'];
			$this->gMsgTitle("Executando processo nº $id_processo");
			$this->gOut("<hr>");
			$this->gMsg($rst->fields['sigla']." - ".$rst->fields['nome']." (".$rst->fields['descricao'].")",3);
			if ($rst->fields['ajuda']<>'')
				$this->gOut(nl2br($rst->fields['ajuda']));
			$this->gBr();
			$rst->MoveNext();
			if (!$rst->EOF)
			{
				$this->gBr();
				$this->gMsg("Opções possíveis para esta etapa:");
				$this->gBr();
				$this->gOut("<ul>");
				while (!$rst->EOF)
				{
					$link=$rst->fields['link'];
					$sep="?";
					if (strpos($link,"?")>0)
						$sep="&";
					$link.=$sep."id_processo=$id_processo&id_roteiro=$id_roteiro";
					$this->gMsg("<li><a href='".$link."'><b>".$rst->fields['sigla']." - ".$rst->fields['nome']."</b> (".$rst->fields['descricao'].")</a>");
					if ($rst->fields['ajuda']<>'')
						$this->gOut("<i>".nl2br($rst->fields['ajuda'])."</i>");
					$this->gOut("</li>");
					$rst->MoveNext();
				}
				$this->gOut("</ul>");
			}
			$this->gEnd();
		}
	}
}
?>
