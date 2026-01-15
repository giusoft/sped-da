<?
/** gFW - WEB Development Framework
------------------------------------------------------------------------
Copyleft (l) 2004  GiuSoft Tecnologia/Brazil.

Licensed under GPL: www.fsf.org for further details

Site:           http://www.giusoft.com.br/mediawiki
Description:    An abstract layer of oriented object programming code
Started in:     January, 2004
Started Author: Giuliano Nascimento & GiuSoft Team (giusoft@hotmail.com)
------------------------------------------------------------------------
*/
header("Content-Type: text/html; charset=ISO-8859-1",true); // resolve problema de acentuação do ajax
//Configurações
$gCfg="";
if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
{
	define("gBAR", '\\');
	define(gAPP_FILE, "\\gApp_");
	setlocale(LC_ALL, 'POSIX');
	$gCfg[]=array("debug.logfilename",      "/gs.log"); // Não precisa existir
}
else
{
	define("gBAR", '/');
	define(gAPP_FILE, "/tmp/gApp_");
	setlocale(LC_ALL, 'english');
	$gCfg[]=array("debug.logfilename",      "/var/log/gs.log"); // Já deve existir

}
// Pasta base do site (a partir do raiz do servidor web)
define(gBASE,"gs".gBAR."v6");

$gCfg["global.css"]				="true";
$gCfg["global.timeout"]			="300"; // em minutos
$gCfg["global.users"]			="1"; 
$gCfg["global.dateformat"]		="dd-mm-yy";
$gCfg["global.datenull"]		="0000-00-00"; // o padrão é "0000-00-00"
$gCfg["global.keywords"]		="tecnologia,sistemas,software,hardware,consultoria,assessoria,voip,asterisk,ura,erp,siscop,kings";
$gCfg["global.language"]		="pt_BR";
$gCfg["global.numformat"]		="0.000,00";
$gCfg["global.schema"]			="giusoft2008";
$gCfg["global.site"]				="GiuSoft Tecnologia";
$gCfg["global.phone"]			="71 2107 0330";

$gCfg["global.timeformat"]		="hh:nn:ss";
$gCfg["global.url"]				="www.giusoft.com.br";
$gCfg["global.urlbase"]			=gBASE;
$gCfg["global.showcalendar"]	="false";
$gCfg["global.userphoto"]		="true";
$gCfg["global.align"]			="center";

$gCfg["smtp.server"]				="200.254.1.134";
$gCfg["smtp.from"]				="suporte@giusoft.com.br";
$gCfg["smtp.fromname"]			="SAC GiuSoft";
$gCfg["smtp.user"]				="";
$gCfg["smtp.password"]			="";

$gCfg["jabber.user"]				="erp";
$gCfg["jabber.password"]		="web";
$gCfg["jabber.server"]			="200.254.1.135";

$gCfg["table.size"]				=2;
$gCfg["table.controls"]			="true";
$gCfg["table.corners"]			="square";
$gCfg["table.filterrows"]		=3;

$gCfg["csv.delimiter"]			=";";

$gCfg["database.maxcols"]		="8";
$gCfg["database.maxrows"]		="20";
$gCfg["database.name"]			="erp_belmonte";
$gCfg["database.user"]			="web";
$gCfg["database.password"]		="web";
$gCfg["database.port"]			="";
$gCfg["database.showcopy"]		="true";
$gCfg["database.showid"]		="true";
$gCfg["database.showcount"]		="false";
$gCfg["database.showsummary"]	="true";
$gCfg["database.autoshowtable"]	="true";
$gCfg["database.type"]			="mysqlt";
$gCfg["database.url"]			="localhost";
$gCfg["database.idd"]			="false";

$gCfg["debug.level"]			="1";
$gCfg["debug.logfilename"]		="/var/log/gs.log";
$gCfg["debug.loglevel"]			="1";

$gCfg["export.doc"]				="true";
$gCfg["export.csv"]				="false";
$gCfg["export.flash"]			="false";
$gCfg["export.htm"]				="false";
$gCfg["export.pdf"]				="true";
$gCfg["export.prn"]				="true";
$gCfg["export.rtf"]				="false";
$gCfg["export.txt"]				="true";
$gCfg["export.xls"]				="true";

$gCfg["page.index"]				=gBASE.gBAR."acesso.php";
$gCfg["page.login"]="login.php";
//$gCfg["page.login_error"]="index.php";
$gCfg["page.login_error"]		="https://www.giusoft.com.br/gs/v6/acesso.php";
$gCfg["page.logout"]			="https://www.giusoft.com.br/gs/v6/acesso.php";
$gCfg["page.session_expires"]	="session_expires.htm";

$gCfg["pdf.headersize"]			="48";
$gCfg["pdf.color"]				="blue";
$gCfg["pdf.font"]				="helvetica";
$gCfg["pdf.logojpgfile"]		="logo_pdf.jpg";
$gCfg["pdf.logowidth"]			="37";
$gCfg["pdf.logoheight"]			="10";
$gCfg["pdf.logoalign"]			="left";
$gCfg["pdf.orientation"]		="autodetect";
$gCfg["pdf.footer1"]				="Rua Visconde do Rosário, 3, salas 701/702. Comércio. Salvador/Bahia";
$gCfg["pdf.footer2"]				="TeleFax: 71 2107 0330 / E-mail: central@giusoft.com.br";
$gCfg["pdf.footer3"]				="www.giusoft.com.br";

$gCfg["rtf.font"]					="arial";

$gCfg["txt.font"]					="";
$gCfg["txt.height"]				="66";
$gCfg["txt.marginbottom"]		="2";
$gCfg["txt.marginleft"]			="2";
$gCfg["txt.marginright"]		="2";
$gCfg["txt.margintop"]			="2";
$gCfg["txt.showfooter"]			="true";
$gCfg["txt.showheader"]			="true";
$gCfg["txt.width"]				="132";

$gCfg["erp.ponto"]				="true";

$gCfg["alitem.db"]				="alitem";
$gCfg["alitem.dbhost"]			="200.254.1.200";
$gCfg["alitem.dbuser"]			="root";
$gCfg["alitem.dbpassword"]		="AproZide300";

$gCfg["openser.db"]				="openser";
$gCfg["openser.dbhost"]			="sip.alitem.com.br";
$gCfg["openser.dbuser"]			="root";
$gCfg["openser.dbpassword"]	="SHarpGif85";

$gCfg["asterisk.host"]			="200.254.1.135";
$gCfg["asterisk.login"]			="web";
$gCfg["asterisk.password"]		="web";
$gCfg["asterisk.port"]			="5038";

/* Variáveis globais */
$cr="\r\n";
$gConfigFileDate="01-01-1900";
$gExporting="";
/*
gDebug = 0 ==> Debug desativado, gera um código HTML compacto e pequeno
gDebug = 1 ==> Exibe status de abertura de arquivos, Banco de dados, gera HTML identado
dDebug = 2 ==> Exibe status de abertura de arquivos XML, gera HTML identado
*/
$gDebug=1;
$gPageSecurity=true;
$gError="";
$gsession="";
define(gVER,"2.0");

$gPathDefault=$_SERVER['DOCUMENT_ROOT'].gBAR.gBASE.gBAR."inc_".gVER.gBAR;
$gPathImg=$_SERVER['DOCUMENT_ROOT'].gBAR.gBASE.gBAR."img_".gVER.gBAR;

include_once $gPathDefault."gStart.php";
$flds="";
$flts="";

// Tratamento de includes automáticos
if (isset($gIncludes) && ($_REQUEST['gInclude']==''))
{
   if (is_array($gIncludes))
   {
      foreach ($gIncludes as $gI)
      {
         require_once $gPathDefault.$gI;
      }
   } else
   {
      require_once $gPathDefault.$gIncludes;
   }
   if (file_exists('../config.php')) require_once '../config.php';
   if (file_exists('../sql.php')) require_once '../sql.php';
	if (file_exists('../class.php')) require_once '../class.php';
   if (file_exists('config.php')) require_once 'config.php';   
}

?>
