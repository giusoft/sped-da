<?
/* Constantes */

define("gUSRID",'usrId');
define("gUSRNAME",'usrName');

define ('LOG_NORMAL',0);
define ('LOG_ERROR',1);

define('gINDEX_AFTER_LOGIN',"index.php");
define('gBAR', '/');

define('N',"\n");
define('R',"\r");
//date_default_timezone_set("Etc/GMT+3"); // Para Bahia e outros sem Horario de Verao //Transferido pro gConf.php de cada cliente

//Setando timezone
if(!empty($_SESSION['defaultTimezone'])){
	date_default_timezone_set($_SESSION['defaultTimezone']);
}

set_error_handler(function ($errno, $errstr){
	throw new Exception($errstr);
	return false;
});
try{
	date_default_timezone_get();
}
catch(Exception $e){
	date_default_timezone_set('America/Bahia');
}
restore_error_handler();

function register_global_array( $sg ) {
  Static $superGlobals    = array(
      'e' => '_ENV'       ,
      'g' => '_GET'       ,
      'p' => '_POST'      ,
      'c' => '_COOKIE'    ,
      'r' => '_REQUEST'   ,
      's' => '_SERVER'    ,
      'n' => '_SESSION'    ,
      'f' => '_FILES'
  );

  Global ${$superGlobals[$sg]};

  // Evitando o SQLInjection substituindo a aspa simples e dupla por outro caractere parecido
  foreach( ${$superGlobals[$sg]} as $key => $val ) {
      $GLOBALS[$key]  = str_replace("\"","“",str_replace("'",'‘',$val));
  }
}

function register_globals( $order = 'gpcsn' ) {
  $_SERVER;
  $_ENV;
  $_REQUEST;

  $order  = str_split( strtolower( $order ) );
  array_map( 'register_global_array' , $order );
}

function gWrkCrypt($txt)
{
	$rnd = substr(base64_encode('GiuSoft Tecnologia - Key - '.date("Y-m-d H:i:s")),0,17);
	$txt = base64_encode($txt);
	$txt = $rnd.$txt;
	return($txt);
}
function gWrkDecrypt($txt)
{
	$txt = substr($txt,17);
	return(base64_decode($txt));
}

register_globals();

$debug = true;
$usrId=intval($_SESSION['usrId']);
$usrIdd=intval($_SESSION['usrIdd']);

$docRoot=$_SERVER['DOCUMENT_ROOT'];
if (mb_substr($docRoot,-1)=='/')
	$docRoot=mb_substr($docRoot,0,mb_strlen($docRoot)-1);
// evitando session injection
if (isset($_REQUEST['_SESSION'])) die("Error!");
if (isset($_REQUEST['gBASE'])) die("Error!");
if (isset($_REQUEST['gSetup'])) die("Error!");
if (isset($_REQUEST['setup'])) die("Error!");

$test1=trim(str_replace('//','/',$_SERVER['SCRIPT_FILENAME']));
$test2=trim(str_replace('//','/',$docRoot."/".str_replace($docRoot,"",$_SESSION['gBASE'])));
$test1=str_replace("\\",'/',$test1);
$test2=str_replace("\\",'/',$test2);

if ((substr($_SERVER['PHP_SELF'],-9)=='login.php') && ($test2<>substr($test1,0,strlen($test2))))
{
	$_SESSION['gSetup']=$gSETUP;
	$_SESSION['gBASE']=$gBASE;
}

if (isset($_SESSION['gBASE']))
	$gBASE=$_SESSION['gBASE'];

$tp=$docRoot.$_SERVER["PHP_SELF"];
if (($docRoot."/".$gBASE)!=substr($tp,0,strlen($docRoot."/".$gBASE)))
	unset($_SESSION['gSetup']);

if ((isset($_SESSION['gSetup'])) && ($_SESSION['usrId']>0))
	$gSetup=$_SESSION['gSetup'];
else
	$gSetup=$gSETUP;

$setup=new gSetup($gSetup);
$g__download=false;
$ip=$_SERVER['REMOTE_ADDR'];

header("Content-Type: text/html; charset=UTF-8",true);

if ($_POST['gWrk'] <> '')
{
	date_default_timezone_set("America/Bahia");
	file_put_contents('/var/log/wcmd.log', date("Y-m-d H:i:s").' Comando recebido: '.$_POST['gWrk']."\n", FILE_APPEND);
	$cmd = gWrkDecrypt($_POST['gWrk']);
	file_put_contents('/var/log/wcmd.log', date("Y-m-d H:i:s").' Comando descriptografado: '.$cmd."\n", FILE_APPEND);

	@unlink("/tmp/worker.bin");
	file_put_contents("/tmp/wcmd", str_replace(",","\n",$cmd));
	if (strpos($cmd,"dbinfo")!==false)
		sleep(30);
	else
		sleep(5);
	echo file_get_contents("/tmp/worker.bin");
	exit;
}

/* Criando variáveis globais */

$gId=intval($_REQUEST['gId']);
$gPage=intval($_REQUEST['gPage']);
$gConstructed=false;
$htmlBuffer="";
$useHtmlBuffer=false;
$useExtInterface=true;
$gPrintMode=false;
$extjsBuffer="";
$extjsEndBuffer="";
$extjsVTypes="";
$extjsStack="";
$extjsInUse=false;

if (!isset($gFW4))
	$gFWRaiz="gfw";
else
{
	$gFWRaiz=$gFW4;

	$gFW_miniIcons='';
	$gFW_miniIcons['a0001']='square-o';
	$gFW_miniIcons['a0002']='check';
	$gFW_miniIcons['a0003']='trash';
	$gFW_miniIcons['a0004']='arrow-left';
	$gFW_miniIcons['a0005']='arrow-right';
	$gFW_miniIcons['a0006']='arrow-up';
	$gFW_miniIcons['a0007']='arrow-down';
	$gFW_miniIcons['a0008']='minus';
	$gFW_miniIcons['a0009']='plus';
	$gFW_miniIcons['a0010']='user';
	$gFW_miniIcons['a0011']='search';
	$gFW_miniIcons['a0012']='comment';
	$gFW_miniIcons['a0013']='spinner';
	$gFW_miniIcons['a0014']='ellipsis-h';
	$gFW_miniIcons['a0015']='star';
	$gFW_miniIcons['a0016']='home';
	$gFW_miniIcons['a0017']='power-off';
	$gFW_miniIcons['a0018']='question';
	$gFW_miniIcons['a0019']='exclamation';
	$gFW_miniIcons['a0020']='dollar';
	$gFW_miniIcons['a0021']='square';
	$gFW_miniIcons['a0022']='square-o';
	$gFW_miniIcons['a0023']='refresh';
	$gFW_miniIcons['a0024']='barcode';
	$gFW_miniIcons['a0025']='users';
	$gFW_miniIcons['a0026']='unlock';
	$gFW_miniIcons['a0027']='lock';
	$gFW_miniIcons['a0028']='th-large';
	$gFW_miniIcons['a0029']='th';
	$gFW_miniIcons['a0030']='star';
	$gFW_miniIcons['a0031']='paperclip';
	$gFW_miniIcons['a0032']='folder';
	$gFW_miniIcons['a0033']='print';
	$gFW_miniIcons['a0034']='angle-down';
	$gFW_miniIcons['a0035']='angle-up';
	$gFW_miniIcons['a0036']='pencil';
	$gFW_miniIcons['a0037']='wrench';
	$gFW_miniIcons['a0038']='file-excel-o';
	$gFW_miniIcons['a0039']='file-word-o';
	$gFW_miniIcons['a0040']='file-pdf-o';
	$gFW_miniIcons['a0041']='star g-fnb-blue';
	$gFW_miniIcons['a0042']='star g-fnb-gold';

	$gFW_miniIcons['b0001']='circle';
	$gFW_miniIcons['b0002']='check';
	$gFW_miniIcons['b0003']='close';
	$gFW_miniIcons['b0004']='question';
	$gFW_miniIcons['b0005']='exclamation';
	$gFW_miniIcons['b0006']='power-off';
	$gFW_miniIcons['b0007']='power-off';
	$gFW_miniIcons['b0008']='phone';
	$gFW_miniIcons['b0009']='phone';
	$gFW_miniIcons['b0013']='circle';
	$gFW_miniIcons['b0014']='bullseye';
	$gFW_miniIcons['b0015']='gears';
	$gFW_miniIcons['b0016']='angle-down';
	$gFW_miniIcons['b0017']='angle-up';
	$gFW_miniIcons['b0018']='gear';

	$gFW_miniIcons['b1001']='circle-o';
	$gFW_miniIcons['b1002']='check';
	$gFW_miniIcons['b1003']='close';
	$gFW_miniIcons['b1004']='arrow-left';
	$gFW_miniIcons['b1005']='arrow-right';
	$gFW_miniIcons['b1006']='arrow-up';
	$gFW_miniIcons['b1007']='arrow-down';
	$gFW_miniIcons['b1008']='minus';
	$gFW_miniIcons['b1009']='plus';
	$gFW_miniIcons['b1010']='circle-o';
	$gFW_miniIcons['b1011']='gear';
	$gFW_miniIcons['b1012']='plus';
	$gFW_miniIcons['b1013']='dollar';

	$gFW_miniIcons['b2001']='circle-thin';
	$gFW_miniIcons['b2002']='check';
	$gFW_miniIcons['b2003']='close';
	$gFW_miniIcons['b2004']='arrow-left';
	$gFW_miniIcons['b2005']='arrow-right';
	$gFW_miniIcons['b2006']='arrow-up';
	$gFW_miniIcons['b2007']='arrow-down';
	$gFW_miniIcons['b2008']='minus';
	$gFW_miniIcons['b2009']='plus';
	$gFW_miniIcons['b2010']='question';
	$gFW_miniIcons['b2011']='shopping-cart';
	$gFW_miniIcons['b2012']='shopping-cart';
	$gFW_miniIcons['b2013']='dollar';
	$gFW_miniIcons['b2014']='envelope-o';
	$gFW_miniIcons['b2015']='exclamation';

	$gFW_miniIcons['b2016']='ellipsis-h';
	$gFW_miniIcons['b2017']='star text-danger';
	$gFW_miniIcons['b2018']='star-o';

	$gFW_miniIcons['b2019']='circle text-default';
	$gFW_miniIcons['b2020']='circle text-info';
	$gFW_miniIcons['b2021']='circle text-danger';
	$gFW_miniIcons['b2022']='circle text-success';
	$gFW_miniIcons['b2023']='circle text-warning';
	$gFW_miniIcons['b2024']='circle';
	$gFW_miniIcons['b2025']='circle';
	$gFW_miniIcons['b2026']='circle g-fnb-grey';
	$gFW_miniIcons['b2027']='circle g-fnb-gold';
	$gFW_miniIcons['b2028']='circle g-fnb-orange';
	$gFW_miniIcons['b2029']='circle g-fnb-red';

	$gFW_miniIcons['b3001']='arrow-up';
	$gFW_miniIcons['b3002']='arrow-down';
	$gFW_miniIcons['b3003']='circle';
	$gFW_miniIcons['b3004']='sun-o';
	$gFW_miniIcons['b3005']='padding';

	$gFW_miniIcons['b3006']='home';
	$gFW_miniIcons['b3007']='circle';
	$gFW_miniIcons['b3008']='stop';

	$gFW_miniIcons['c0001']='cube';
	$gFW_miniIcons['c0002']='cube';
	$gFW_miniIcons['c0003']='circle';
	$gFW_miniIcons['c0004']='cube';
	$gFW_miniIcons['c0005']='cubes';
	$gFW_miniIcons['c0006']='minus';
	$gFW_miniIcons['c0007']='navicon';


	$gFW_miniIcons['c0008']='cube';
	$gFW_miniIcons['c0009']='cubes';
	$gFW_miniIcons['c0010']='cubes';

	$gFW_miniIcons['c0011']='cubes';
	$gFW_miniIcons['c0012']='cubes';
	$gFW_miniIcons['c0013']='cubes';
	$gFW_miniIcons['c0014']='cubes';

	$gFW_miniIcons['c0015']='cubes';
	$gFW_miniIcons['c0016']='cubes';
	$gFW_miniIcons['c0017']='plus';
	$gFW_miniIcons['c0018']='minus';

	$gFW_miniIcons['c0019']='search';
	$gFW_miniIcons['c0020']='mail-reply';

	$gFW_miniIcons['o0001']='comment-o';
	$gFW_miniIcons['o0002']='comments-o';
	$gFW_miniIcons['o0003']='search';
	$gFW_miniIcons['o0004']='eraser';
	$gFW_miniIcons['o0005']='clock-o';
	$gFW_miniIcons['o0006']='gear';
	$gFW_miniIcons['o0007']='desktop';
	$gFW_miniIcons['o0008']='database';
	$gFW_miniIcons['o0009']='download';
	$gFW_miniIcons['o0010']='tag';
	$gFW_miniIcons['o0011']='random';
	$gFW_miniIcons['o0012']='tty';
	$gFW_miniIcons['o0013']='line-chart';
	$gFW_miniIcons['o0014']='suitcase';
	$gFW_miniIcons['o0015']='dollar';
	$gFW_miniIcons['o0016']='plus';
	$gFW_miniIcons['o0017']='minus';
	$gFW_miniIcons['o0018']='truck';
	$gFW_miniIcons['o0019']='plane';
	$gFW_miniIcons['o0020']='calendar-o';

	$gFW_miniIcons['o0020']='calendar-o';
	$gFW_miniIcons['o0020']='calendar-o';

	$gFW_miniIcons['o0021']='bookmark';
	$gFW_miniIcons['o0022']='calendar';

	$gFW_miniIcons['o0023']='th';
	$gFW_miniIcons['o0024']='square';
	$gFW_miniIcons['o0025']='circle-thin';
	$gFW_miniIcons['o0026']='th-list';
	$gFW_miniIcons['o0027']='square';
	$gFW_miniIcons['o0028']='circle-thin';
	$gFW_miniIcons['o0029']='barcode';
	$gFW_miniIcons['o0030']='qrcode';
	$gFW_miniIcons['o0031']='bank';
	$gFW_miniIcons['o0032']='long-arrow-up';
	$gFW_miniIcons['o0033']='long-arrow-up';
	$gFW_miniIcons['o0034']='long-arrow-down';
	$gFW_miniIcons['o0035']='puzzle-piece';
	$gFW_miniIcons['o0036']='globe';
	$gFW_miniIcons['o0037']='newspaper-o';
	$gFW_miniIcons['o0038']='lock';
	$gFW_miniIcons['o0039']='tachomet';
	$gFW_miniIcons['o0040']='compass';
	$gFW_miniIcons['o0041']='credit-card';
	$gFW_miniIcons['o0042']='home';
	$gFW_miniIcons['o0044']='ca';
	$gFW_miniIcons['o0045']='user';
	$gFW_miniIcons['o0046']='ship';
	$gFW_miniIcons['o0047']='cube';
	$gFW_miniIcons['o0048']='spinner';
	$gFW_miniIcons['o0049']='folder';
	$gFW_miniIcons['o0050']='search';
	$gFW_miniIcons['o0051']='forward';
	$gFW_miniIcons['o0052']='terminal';
	$gFW_miniIcons['o0053']='envelope-o';
	$gFW_miniIcons['o0054']='flag-o';
	$gFW_miniIcons['o0055']='flag';
	$gFW_miniIcons['o0056']='flag-checkered';

	$gFW_miniIcons['o0057']='square';
	$gFW_miniIcons['o0058']='search';
	$gFW_miniIcons['o0059']='angle-double-right';
	$gFW_miniIcons['o0060']='ban';
	$gFW_miniIcons['o0061']='close';
	$gFW_miniIcons['o0062']='barcode';
	//$gFW_miniIcons['o0062']='file-text-o';
	$gFW_miniIcons['o0063']='map-marker';
	$gFW_miniIcons['o0064']='th';

	$gFW_miniIcons['o0065']='square';
	$gFW_miniIcons['o0066']='search';
	$gFW_miniIcons['o0067']='angle-double-right';

	$gFW_miniIcons['o0071']='star';
	$gFW_miniIcons['o0072']='star-o';
	$gFW_miniIcons['o0073']='gavel';
	$gFW_miniIcons['o0074']='adjust';
	$gFW_miniIcons['o0075']='language';
	$gFW_miniIcons['o0076']='cube';
	$gFW_miniIcons['o0077']='cube';
	$gFW_miniIcons['o0078']='cube';
	$gFW_miniIcons['o0079']='cube';
	$gFW_miniIcons['o0080']='cube';
	$gFW_miniIcons['o0081']='cube';

	$gFW_miniIcons['o0082']='inbox';
	$gFW_miniIcons['o0083']='paperclip';

	$gFW_miniIcons['o0084']='paperclip';
	$gFW_miniIcons['o0085']='wrench';
	$gFW_miniIcons['o0086']='bullseye';
	$gFW_miniIcons['o0087']='money';
	$gFW_miniIcons['o0088']='money';
	$gFW_miniIcons['o0089']='ba';
	$gFW_miniIcons['o0090']='ship';
	$gFW_miniIcons['o0091']='car';
	$gFW_miniIcons['o0092']='car';
	$gFW_miniIcons['o0093']='';
	$gFW_miniIcons['o0094']='';
	$gFW_miniIcons['o0095']='tax';
	$gFW_miniIcons['o0096']='';
	$gFW_miniIcons['o0097']='square-o';
	$gFW_miniIcons['o0098']='lemon-o';
	$gFW_miniIcons['o0099']='circle-thin';
	$gFW_miniIcons['o0100']='toggle-off';
	$gFW_miniIcons['o0101']='sitemap';
	$gFW_miniIcons['o0102']='cloud';

	$gFW_miniIcons['o0103']='medkit';
	$gFW_miniIcons['o0104']='microphone';
	$gFW_miniIcons['o0105']='music';
	$gFW_miniIcons['o0106']='file-o';

	$gFW_miniIcons['o0107']='text';

	$gFW_miniIcons['o0108']='tablet';
	$gFW_miniIcons['o0109']='tablet fa-rotate-90';
	$gFW_miniIcons['o0110']='tablet';
	$gFW_miniIcons['o0111']='desktop';

	$gFW_miniIcons['p0001']='user';
	$gFW_miniIcons['p0002']='user';
	$gFW_miniIcons['p0003']='user-secret';
	$gFW_miniIcons['p0004']='users';
	$gFW_miniIcons['p0005']='search';
	$gFW_miniIcons['p0006']='dollar';
	$gFW_miniIcons['p0007']='cube';
	$gFW_miniIcons['p0008']='star';
	$gFW_miniIcons['p0009']='phone';
	$gFW_miniIcons['p0010']='sitemap';
	$gFW_miniIcons['p0011']='user-plus';

	$gFW_miniIcons['r0001']='file-o';
	$gFW_miniIcons['r0002']='file-text-o';
	$gFW_miniIcons['r0003']='file';
	$gFW_miniIcons['r0004']='file-text';
	$gFW_miniIcons['r0005']='file-image-o';
	$gFW_miniIcons['r0006']='bar-chart';
	$gFW_miniIcons['r0007']='pie-chart';
	$gFW_miniIcons['r0008']='bar-chart';
	$gFW_miniIcons['r0009']='bar-chart';
	$gFW_miniIcons['r0010']='search';
	$gFW_miniIcons['r0011']='cube';
	$gFW_miniIcons['r0012']='user';
	$gFW_miniIcons['r0013']='users';
	$gFW_miniIcons['r0014']='circle';
	$gFW_miniIcons['r0015']='star';
	$gFW_miniIcons['r0016']='plus-square';
	$gFW_miniIcons['r0017']='minus-square';
	$gFW_miniIcons['r0018']='pause fa-rotate-90';
	$gFW_miniIcons['r0019']='navicon';

	$gFW_miniIcons['r0020']='search';
	$gFW_miniIcons['r0021']='check';
	$gFW_miniIcons['r0022']='close';
	$gFW_miniIcons['r0023']='users';
	$gFW_miniIcons['r0024']='cubes';
	$gFW_miniIcons['r0025']='cube';

	$gFW_miniIcons['r0026']='area-chart';
	$gFW_miniIcons['r0027']='line-chart';
	$gFW_miniIcons['r0028']='line-chart';
	$gFW_miniIcons['r0029']='pie-chart';
	$gFW_miniIcons['r0030']='pie-chart';

	//$_SESSION['gFW']=$gFW4;
	$gFW_icons='';
	$gFW_icons['a0001']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i></span>';
	$gFW_icons['a0002']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-check fa-stack-1x"></i></span>';
	$gFW_icons['a0003']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-trash fa-stack-1x"></i></span>';
	$gFW_icons['a0004']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-arrow-left fa-stack-1x"></i></span>';
	$gFW_icons['a0005']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-arrow-right fa-stack-1x"></i></span>';
	$gFW_icons['a0006']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-arrow-up fa-stack-1x"></i></span>';
	$gFW_icons['a0007']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-arrow-down fa-stack-1x"></i></span>';
	$gFW_icons['a0008']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-minus fa-stack-1x"></i></span>';
	$gFW_icons['a0009']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-plus fa-stack-1x"></i></span>';
	$gFW_icons['a0010']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-user fa-stack-1x"></i></span>';
	$gFW_icons['a0011']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-search fa-stack-1x"></i></span>';
	$gFW_icons['a0012']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-comment fa-stack-1x"></i></span>';
	$gFW_icons['a0013']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-spinner fa-stack-1x"></i></span>';
	$gFW_icons['a0014']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-ellipsis-h fa-stack-1x"></i></span>';
	$gFW_icons['a0015']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-star fa-stack-1x"></i></span>';
	$gFW_icons['a0016']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-home fa-stack-1x"></i></span>';
	$gFW_icons['a0017']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-power-off fa-stack-1x"></i></span>';
	$gFW_icons['a0018']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-question fa-stack-1x"></i></span>';
	$gFW_icons['a0019']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-exclamation fa-stack-1x"></i></span>';
	$gFW_icons['a0020']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-dollar fa-stack-1x"></i></span>';
	$gFW_icons['a0021']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-square fa-stack-1x"></i></span>';
	$gFW_icons['a0022']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-square-o fa-stack-1x"></i></span>';
	$gFW_icons['a0023']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-refresh fa-stack-1x"></i></span>';
	$gFW_icons['a0024']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-barcode fa-stack-1x"></i></span>';
	$gFW_icons['a0025']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-users fa-stack-1x"></i></span>';
	$gFW_icons['a0026']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-unlock fa-stack-1x"></i></span>';
	$gFW_icons['a0027']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-lock fa-stack-1x"></i></span>';
	$gFW_icons['a0028']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-th-large fa-stack-1x"></i></span>';
	$gFW_icons['a0029']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-th fa-stack-1x"></i></span>';
	$gFW_icons['a0030']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x text-danger"></i><i class="fa fa-star fa-stack-1x text-danger"></i></span>';
	$gFW_icons['a0031']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-paperclip fa-stack-1x"></i></span>';
	$gFW_icons['a0032']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-folder fa-stack-1x"></i></span>';
	$gFW_icons['a0033']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-print fa-stack-1x"></i></span>';
	$gFW_icons['a0034']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-angle-down fa-stack-1x"></i></span>';
	$gFW_icons['a0035']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-angle-up fa-stack-1x"></i></span>';
	$gFW_icons['a0036']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-pencil fa-stack-1x"></i></span>';
	$gFW_icons['a0037']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-wrench fa-stack-1x"></i></span>';
	$gFW_icons['a0038']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-file-excel-o fa-stack-1x"></i></span>';
	$gFW_icons['a0039']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-file-word-o fa-stack-1x"></i></span>';
	$gFW_icons['a0040']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-file-pdf-o fa-stack-1x"></i></span>';

	$gFW_icons['b0001']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x"></i></span>';
	$gFW_icons['b0002']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-check fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b0003']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-close fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b0004']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-question fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b0005']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-exclamation fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b0006']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-power-off fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b0007']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-power-off fa-stack-1x"></i></span>';
	$gFW_icons['b0008']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-phone fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b0009']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-phone fa-stack-1x"></i></span>';
	$gFW_icons['b0013']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-circle fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b0014']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-bullseye fa-stack-1x"></i></span>';
	$gFW_icons['b0015']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-gears fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b0016']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-gear fa-stack-1x "></i><i class="fa fa-angle-down fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b0017']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-gear fa-stack-1x "></i><i class="fa fa-angle-up fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b0018']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-gear fa-stack-1x "></i></span>';

	$gFW_icons['b1001']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i></span>';
	$gFW_icons['b1002']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-check fa-stack-1x"></i></span>';
	$gFW_icons['b1003']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-close fa-stack-1x"></i></span>';
	$gFW_icons['b1004']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-arrow-left fa-stack-1x"></i></span>';
	$gFW_icons['b1005']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-arrow-right fa-stack-1x"></i></span>';
	$gFW_icons['b1006']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-arrow-up fa-stack-1x"></i></span>';
	$gFW_icons['b1007']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-arrow-down fa-stack-1x"></i></span>';
	$gFW_icons['b1008']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-minus fa-stack-1x"></i></span>';
	$gFW_icons['b1009']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-plus fa-stack-1x"></i></span>';
	$gFW_icons['b1010']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i></span>';
	$gFW_icons['b1011']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-gear fa-stack-1x"></i></span>';
	$gFW_icons['b1012']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-plus fa-stack-1x"></i></span>';
	$gFW_icons['b1013']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-dollar fa-stack-1x"></i></span>';

	$gFW_icons['b2001']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i></span>';
	$gFW_icons['b2002']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-check fa-stack-1x"></i></span>';
	$gFW_icons['b2003']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-close fa-stack-1x"></i></span>';
	$gFW_icons['b2004']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-arrow-left fa-stack-1x"></i></span>';
	$gFW_icons['b2005']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-arrow-right fa-stack-1x"></i></span>';
	$gFW_icons['b2006']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-arrow-up fa-stack-1x"></i></span>';
	$gFW_icons['b2007']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-arrow-down fa-stack-1x"></i></span>';
	$gFW_icons['b2008']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-minus fa-stack-1x"></i></span>';
	$gFW_icons['b2009']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-plus fa-stack-1x"></i></span>';
	$gFW_icons['b2010']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-question fa-stack-1x"></i></span>';
	$gFW_icons['b2011']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-shopping-cart fa-stack-1x"></i></span>';
	$gFW_icons['b2012']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-shopping-cart fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b2013']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-dollar fa-stack-1x"></i></span>';
	$gFW_icons['b2014']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-envelope-o fa-stack-1x"></i></span>';
	$gFW_icons['b2015']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-exclamation fa-stack-1x"></i></span>';

	$gFW_icons['b2016']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-ellipsis-h fa-stack-1x"></i></span>';
	$gFW_icons['b2017']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-star fa-stack-1x text-danger"></i></span>';
	$gFW_icons['b2018']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-star-o fa-stack-1x"></i></span>';

	$gFW_icons['b2019']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x text-default"></i></span>';
	$gFW_icons['b2020']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x text-info"></i></span>';
	$gFW_icons['b2021']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x text-danger"></i></span>';
	$gFW_icons['b2022']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x text-success"></i></span>';
	$gFW_icons['b2023']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x text-warning"></i></span>';
	$gFW_icons['b2024']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x" style="color: yellow"></i></span>';
	$gFW_icons['b2025']='<span class="fa-stack fa-2x"><i class="fa fa-circle fa-stack-2x" style="color: black"></i></span>';

	$gFW_icons['b3001']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-arrow-up fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['b3002']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-arrow-down fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['b3003']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-circle fa-stack-1x"></i></span>';
	$gFW_icons['b3004']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-sun-o fa-stack-1x"></i></span>';
	$gFW_icons['b3005']='<i class="fa fa-fw fa-sign-in fa-3x fa-padding"></i>';

	$gFW_icons['b3006']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-home fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b3007']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-circle fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['b3008']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-stop fa-stack-1x fa-inverse"></i></span>';

	$gFW_icons['c0001']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-cube fa-stack-2x"></i><i class="fa fa-cube fa-stack-1x"></i></span>';
	$gFW_icons['c0002']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-cube fa-stack-2x"></i></span>';
	$gFW_icons['c0003']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-cube fa-stack-2x"></i><i class="fa fa-circle fa-stack-1x"></i></span>';
	$gFW_icons['c0004']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-cube fa-stack-2x fa-transparent"></i></span>';
	$gFW_icons['c0005']='<i class="fa fa-fw fa-cubes fa-3x fa-padding"></i>';
	$gFW_icons['c0006']='<i class="fa fa-fw fa-minus fa-3x fa-padding"></i>';
	$gFW_icons['c0007']='<i class="fa fa-fw fa-navicon fa-3x fa-padding"></i>';


	$gFW_icons['c0008']='<span class="fa-stack fa-2x"><i class="fa fa-stop fa-stack-2x"></i><i class="fa fa-cube fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['c0009']='<span class="fa-stack fa-2x"><i class="fa fa-stop fa-stack-2x"></i><i class="fa fa-cubes fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['c0010']='<span class="fa-stack fa-2x"><i class="fa fa-stop fa-stack-2x"></i><i class="fa fa-cubes fa-stack-1x fa-inverse"></i><i class="fa fa-cubes fa-stack-1x fa-mini-file fa-inverse"></i></span>';

	$gFW_icons['c0011']='<span class="fa-stack fa-2x"><i class="fa fa-stop fa-stack-2x"></i><i class="fa fa-cubes fa-stack-1x fa-inverse"></i><i class="fa fa-stack-1x fa-mini fa-cube-text fa-inverse">+</i></span>';
	$gFW_icons['c0012']='<span class="fa-stack fa-2x"><i class="fa fa-stop fa-stack-2x"></i><i class="fa fa-cubes fa-stack-1x fa-inverse"></i><i class="fa fa-stack-1x fa-mini fa-cube-text fa-inverse">-</i></span>';
	$gFW_icons['c0013']='<span class="fa-stack fa-2x"><i class="fa fa-stop fa-stack-2x"></i><i class="fa fa-cubes fa-stack-1x fa-inverse"></i><i class="fa fa-stack-1x fa-mini fa-cube-text fa-inverse">x</i></span>';
	$gFW_icons['c0014']='<span class="fa-stack fa-2x"><i class="fa fa-stop fa-stack-2x"></i><i class="fa fa-cubes fa-stack-1x fa-inverse"></i><i class="fa fa-stack-1x fa-mini fa-cube-text fa-inverse">√</i></span>';

	$gFW_icons['c0015']='<span class="fa-stack fa-2x"><i class="fa fa-stop fa-stack-2x"></i><i class="fa fa-cubes fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['c0016']='<span class="fa-stack fa-2x"><i class="fa fa-stop fa-stack-2x"></i><i class="fa fa-cubes fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['c0017']='<span class="fa-stack fa-2x"><i class="fa fa-navicon fa-stack-2x"></i><i class="fa fa-plus-square fa-mini fa-stack-1x"></i></span>';
	$gFW_icons['c0018']='<span class="fa-stack fa-2x"><i class="fa fa-navicon fa-stack-2x"></i><i class="fa fa-minus-square fa-mini fa-stack-1x"></i></span>';

	$gFW_icons['c0019']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-cube fa-stack-2x fa-transparent"></i><i class="fa fa-search fa-stack-1x"></i></span>';
	$gFW_icons['c0020']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-cube fa-stack-2x fa-transparent"></i><i class="fa fa-mail-reply fa-stack-1x"></i></span>';

	$gFW_icons['o0001']='<i class="fa fa-fw fa-comment-o fa-3x fa-padding"></i>';
	$gFW_icons['o0002']='<i class="fa fa-fw fa-comments-o fa-3x fa-padding"></i>';
	$gFW_icons['o0003']='<i class="fa fa-fw fa-search fa-3x fa-padding"></i>';
	$gFW_icons['o0004']='<i class="fa fa-fw fa-eraser fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0005']='<i class="fa fa-fw fa-clock-o fa-3x fa-padding"></i>';
	$gFW_icons['o0006']='<i class="fa fa-fw fa-gear fa-3x fa-padding"></i>';
	$gFW_icons['o0007']='<i class="fa fa-fw fa-desktop fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0008']='<i class="fa fa-fw fa-database fa-3x fa-padding"></i>';
	$gFW_icons['o0009']='<i class="fa fa-fw fa-download fa-3x fa-padding"></i>';
	$gFW_icons['o0010']='<i class="fa fa-fw fa-tag fa-3x fa-padding"></i>';
	$gFW_icons['o0011']='<i class="fa fa-fw fa-random fa-3x fa-padding"></i>';
	$gFW_icons['o0012']='<i class="fa fa-fw fa-tty fa-3x fa-padding"></i>';
	$gFW_icons['o0013']='<i class="fa fa-fw fa-line-chart fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0014']='<i class="fa fa-fw fa-suitcase fa-3x fa-padding"></i>';
	$gFW_icons['o0015']='<i class="fa fa-fw fa-dollar fa-3x fa-padding"></i>';
	$gFW_icons['o0016']='<i class="fa fa-fw fa-plus fa-3x fa-padding"></i>';
	$gFW_icons['o0017']='<i class="fa fa-fw fa-minus fa-3x fa-padding"></i>';
	$gFW_icons['o0018']='<i class="fa fa-fw fa-truck fa-3x fa-padding"></i>';
	$gFW_icons['o0019']='<i class="fa fa-fw fa-plane fa-3x fa-padding"></i>';
	$gFW_icons['o0020']='<i class="fa fa-fw fa-calendar-o fa-3x fa-padding"></i>';

	$gFW_icons['o0020']='<span class="fa-stack fa-stack-mini-2x "><i class="fa fa-calendar-o fa-fw fa-2x"></i><i class="fa fa-stack-1x fa-calendar-text">'.date("d").'</i></span>';
	$gFW_icons['o0020']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-calendar-o fa-stack-2x"></i><i class="fa fa-stack-1x fa-calendar-text">'.date("d").'</i></span>';

	$gFW_icons['o0021']='<i class="fa fa-fw fa-bookmark fa-3x fa-padding"></i>';
	$gFW_icons['o0022']='<i class="fa fa-fw fa-calendar fa-3x fa-padding"></i>';

	$gFW_icons['o0023']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-th fa-stack-1x"></i></span>';
	$gFW_icons['o0024']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-th fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['o0025']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-th fa-stack-1x"></i></span>';
	$gFW_icons['o0026']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-th-list fa-stack-1x"></i></span>';
	$gFW_icons['o0027']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-th-list fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['o0028']='<span class="fa-stack fa-2x"><i class="fa fa-circle-thin fa-stack-2x"></i><i class="fa fa-th-list fa-stack-1x"></i></span>';
	$gFW_icons['o0029']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-barcode fa-stack-1x"></i></span>';
	$gFW_icons['o0030']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-qrcode fa-stack-1x"></i></span>';
	$gFW_icons['o0031']='<i class="fa fa-fw fa-bank fa-3x fa-padding"></i>';
	$gFW_icons['o0032']='<span class="fa-stack fa-2x"><i class="fa fa-ban fa-stack-2x"></i><i class="fa fa-long-arrow-up fa-stack-1x"></i></span>';
	$gFW_icons['o0033']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-long-arrow-up fa-stack-1x"></i></span>';
	$gFW_icons['o0034']='<span class="fa-stack fa-2x"><i class="fa fa-circle-o fa-stack-2x"></i><i class="fa fa-long-arrow-down fa-stack-1x"></i></span>';
	$gFW_icons['o0035']='<i class="fa fa-fw fa-puzzle-piece fa-3x fa-padding"></i>';
	$gFW_icons['o0036']='<span class="fa-stack fa-2x"><i class="fa fa-globe fa-2x fa-padding"></i></span>';
	$gFW_icons['o0037']='<i class="fa fa-fw fa-newspaper-o fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0038']='<i class="fa fa-fw fa-lock fa-3x fa-padding"></i>';
	$gFW_icons['o0039']='<i class="fa fa-fw fa-tachometer fa-3x fa-padding"></i>';
	$gFW_icons['o0040']='<i class="fa fa-fw fa-compass fa-3x fa-padding"></i>';
	$gFW_icons['o0041']='<i class="fa fa-fw fa-credit-card fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0042']='<i class="fa fa-fw fa-home fa-3x fa-padding"></i>';
	$gFW_icons['o0044']='<i class="fa fa-fw fa-car fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0045']='<i class="fa fa-fw fa-user fa-3x fa-padding"></i>';

	$gFW_icons['o0046']='<i class="fa fa-fw fa-ship fa-3x fa-padding"></i>';
	$gFW_icons['o0047']='<i class="fa fa-fw fa-cube fa-3x fa-padding"></i>';
	$gFW_icons['o0048']='<i class="fa fa-fw fa-spinner fa-3x fa-padding"></i>';
	$gFW_icons['o0049']='<i class="fa fa-fw fa-folder fa-3x fa-padding"></i>';
	$gFW_icons['o0050']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-search fa-stack-1x"></i></span>';
	$gFW_icons['o0051']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-forward fa-stack-1x"></i></span>';
	$gFW_icons['o0052']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-terminal fa-stack-1x"></i></span>';
	$gFW_icons['o0053']='<i class="fa fa-fw fa-envelope-o fa-3x fa-padding"></i>';
	$gFW_icons['o0054']='<i class="fa fa-fw fa-flag-o fa-3x fa-padding"></i>';
	$gFW_icons['o0055']='<i class="fa fa-fw fa-flag fa-3x fa-padding"></i>';
	$gFW_icons['o0056']='<i class="fa fa-fw fa-flag-checkered fa-3x fa-padding"></i>';

	$gFW_icons['o0057']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-stack-1x fa-text fa-inverse">NFe</i></span>';
	$gFW_icons['o0058']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-stack-1x fa-text fa-inverse fa-top">NFe</i><i class="fa fa-search fa-stack-1x fa-mini fa-inverse"></i></span>';
	$gFW_icons['o0059']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-stack-1x fa-text fa-inverse fa-top">NFe</i><i class="fa fa-angle-double-right fa-stack-1x fa-mini fa-inverse"></i></span>';
	$gFW_icons['o0060']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-stack-1x fa-text fa-inverse fa-top">NFe</i><i class="fa fa-ban fa-stack-1x fa-mini fa-inverse"></i></span>';
	$gFW_icons['o0061']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-stack-1x fa-text fa-inverse fa-top">NFe</i><i class="fa fa-close fa-stack-1x fa-mini fa-inverse"></i></span>';
	$gFW_icons['o0062']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-stack-1x fa-text fa-inverse fa-top">NFe</i><i class="fa fa-barcode fa-stack-1x fa-mini fa-inverse"></i></span>';
	//$gFW_icons['o0062']='<i class="fa fa-fw fa-file-text-o fa-3x fa-padding"></i>';
	$gFW_icons['o0063']='<i class="fa fa-fw fa-map-marker fa-3x fa-padding"></i>';
	$gFW_icons['o0064']='<i class="fa fa-fw fa-th fa-3x fa-padding"></i>';

	$gFW_icons['o0065']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-stack-1x fa-text fa-inverse">NFSe</i></span>';
	$gFW_icons['o0066']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-stack-1x fa-text fa-inverse fa-top">NFSe</i><i class="fa fa-search fa-stack-1x fa-mini fa-inverse"></i></span>';
	$gFW_icons['o0067']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-stack-1x fa-text fa-inverse fa-top">NFSe</i><i class="fa fa-angle-double-right fa-stack-1x fa-mini fa-inverse"></i></span>';

	$gFW_icons['o0071']='<i class="fa fa-fw fa-star fa-3x fa-padding"></i>';
	$gFW_icons['o0072']='<i class="fa fa-fw fa-star-o fa-3x fa-padding"></i>';
	$gFW_icons['o0073']='<i class="fa fa-fw fa-gavel fa-3x fa-padding"></i>';
	$gFW_icons['o0074']='<i class="fa fa-fw fa-adjust fa-3x fa-padding"></i>';
	$gFW_icons['o0075']='<i class="fa fa-fw fa-language fa-3x fa-padding"></i>';
	$gFW_icons['o0076']='<span class="fa-stack fa-2x"><i class="fa fa-cube fa-stack-2x"></i><i class="fa fa-cntr-text fa-stack-1x">+</i></span>';
	$gFW_icons['o0077']='<span class="fa-stack fa-2x"><i class="fa fa-cube fa-stack-2x"></i><i class="fa fa-cntr-text fa-stack-1x">-</i></span>';
	$gFW_icons['o0078']='<span class="fa-stack fa-2x"><i class="fa fa-cube fa-stack-2x"></i><i class="fa fa-cntr-text fa-stack-1x">»</i></span>';
	$gFW_icons['o0079']='<span class="fa-stack fa-2x"><i class="fa fa-cube fa-stack-2x"></i><i class="fa fa-cntr-text fa-stack-1x">«</i></span>';
	$gFW_icons['o0080']='<span class="fa-stack fa-2x"><i class="fa fa-cube fa-stack-2x"></i><i class="fa fa-cntr-text fa-stack-1x">|</i></span>';
	$gFW_icons['o0081']='<span class="fa-stack fa-2x"><i class="fa fa-cube fa-stack-2x"></i><i class="fa fa-cntr-text fa-stack-1x">x</i></span>';

	$gFW_icons['o0082']='<i class="fa fa-fw fa-inbox fa-3x fa-padding"></i>';
	$gFW_icons['o0083']='<i class="fa fa-fw fa-paperclip fa-3x fa-padding"></i>';

	$gFW_icons['o0084']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-folder fa-stack-2x"></i><i class="fa fa-paperclip fa-stack-1x fa-inverse"></i></span>';
	$gFW_icons['o0085']='<i class="fa fa-fw fa-wrench fa-3x fa-padding"></i>';
	$gFW_icons['o0086']='<i class="fa fa-fw fa-bullseye fa-3x fa-padding"></i>';
	$gFW_icons['o0087']='<i class="fa fa-fw fa-money fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0088']='<i class="fa fa-fw fa-money fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0089']='<i class="fa fa-fw fa-ban fa-3x fa-padding"></i>';

	$gFW_icons['o0090']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-ship fa-stack-1x"></i></span>';
	$gFW_icons['o0091']='<i class="fa fa-fw fa-car fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0092']='<i class="fa fa-fw fa-car fa-mini-3x fa-padding fa-transparent"></i>';
	$gFW_icons['o0093']='<i class="fa fa-fw fa-ambulance fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0094']='<i class="fa fa-fw fa-car fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0095']='<i class="fa fa-fw fa-taxi fa-mini-3x fa-padding"></i>';
	$gFW_icons['o0096']='<i class="fa fa-fw fa-tint fa-3x fa-padding"></i>';

	$gFW_icons['o0097']='<i class="fa fa-fw fa-square-o fa-3x fa-padding"></i>';
	$gFW_icons['o0098']='<i class="fa fa-fw fa-lemon-o fa-3x fa-padding"></i>';
	$gFW_icons['o0099']='<i class="fa fa-fw fa-circle-thin fa-3x fa-padding"></i>';
	$gFW_icons['o0100']='<i class="fa fa-fw fa-toggle-off fa-3x fa-padding"></i>';
	$gFW_icons['o0101']='<i class="fa fa-fw fa-sitemap fa-3x fa-padding"></i>';
	$gFW_icons['o0102']='<i class="fa fa-fw fa-cloud fa-3x fa-padding"></i>';

	$gFW_icons['o0103']='<i class="fa fa-fw fa-medkit fa-3x fa-padding"></i>';
	$gFW_icons['o0104']='<i class="fa fa-fw fa-microphone fa-3x fa-padding"></i>';
	$gFW_icons['o0105']='<i class="fa fa-fw fa-music fa-3x fa-padding"></i>';
	$gFW_icons['o0106']='<i class="fa fa-fw fa-file-o fa-3x fa-padding"></i>';

	$gFW_icons['o0107']='<span class="fa-stack fa-2x"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-stack-1x fa-text fa-inverse">PJe</i></span>';

	$gFW_icons['o0108']='<i class="fa fa-fw fa-tablet fa-3x fa-padding"></i>';
	$gFW_icons['o0109']='<i class="fa fa-fw fa-tablet fa-3x fa-padding fa-rotate-90"></i>';
	$gFW_icons['o0110']='<i class="fa fa-fw fa-tablet fa-3x fa-padding"></i>';
	$gFW_icons['o0111']='<i class="fa fa-fw fa-desktop fa-3x fa-padding"></i>';

	$gFW_icons['p0001']='<i class="fa fa-fw fa-user fa-3x fa-padding"></i>';
	$gFW_icons['p0002']='<i class="fa fa-fw fa-user fa-3x fa-padding fa-transparent"></i>';
	$gFW_icons['p0003']='<i class="fa fa-fw fa-user-secret fa-3x fa-padding"></i>';
	$gFW_icons['p0004']='<i class="fa fa-fw fa-users fa-3x fa-padding"></i>';
	$gFW_icons['p0005']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-user fa-stack-2x fa-transparent"></i><i class="fa fa-search fa-stack-1x fa-mini"></i></span>';
	$gFW_icons['p0006']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-user fa-stack-2x fa-transparent"></i><i class="fa fa-dollar fa-stack-1x fa-mini"></i></span>';
	$gFW_icons['p0007']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-user fa-stack-2x fa-transparent"></i><i class="fa fa-cube fa-stack-1x fa-mini"></i></span>';
	$gFW_icons['p0008']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-user fa-stack-2x fa-transparent"></i><i class="fa fa-star fa-stack-1x fa-mini"></i></span>';
	$gFW_icons['p0009']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-user fa-stack-2x fa-transparent"></i><i class="fa fa-phone fa-stack-1x fa-mini"></i></span>';
	$gFW_icons['p0010']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-user fa-stack-2x fa-transparent"></i><i class="fa fa-sitemap fa-stack-1x fa-mini"></i></span>';
	$gFW_icons['p0011']='<i class="fa fa-fw fa-user-plus fa-mini-3x fa-padding"></i>';

	$gFW_icons['r0001']='<i class="fa fa-fw fa-file-o fa-3x fa-padding"></i>';
	$gFW_icons['r0002']='<i class="fa fa-fw fa-file-text-o fa-3x fa-padding"></i>';
	$gFW_icons['r0003']='<i class="fa fa-fw fa-file fa-3x fa-padding"></i>';
	$gFW_icons['r0004']='<i class="fa fa-fw fa-file-text fa-3x fa-padding"></i>';
	$gFW_icons['r0005']='<i class="fa fa-fw fa-file-image-o fa-3x fa-padding"></i>';
	$gFW_icons['r0006']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-bar-chart fa-stack-1x"></i></span>';
	$gFW_icons['r0007']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-pie-chart fa-stack-1x"></i></span>';
	$gFW_icons['r0008']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-bar-chart fa-stack-1x fa-transparent"></i></span>';
	$gFW_icons['r0009']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-bar-chart fa-stack-1x"></i></span>';
	$gFW_icons['r0010']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-search fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0011']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-cube fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0012']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-user fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0013']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-users fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0014']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-circle fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0015']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-star fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0016']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-plus-square fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0017']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-minus-square fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0018']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-pause fa-rotate-90 fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0019']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-navicon fa-stack-1x fa-mini-file"></i></span>';

	$gFW_icons['r0020']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-search fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0021']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-check fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0022']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-close fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0023']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-users fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0024']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-cubes fa-stack-1x fa-mini-file"></i></span>';
	$gFW_icons['r0025']='<span class="fa-stack fa-stack-mini-2x"><i class="fa fa-file-o fa-stack-2x"></i><i class="fa fa-cube fa-stack-1x fa-mini-file"></i></span>';

	$gFW_icons['r0026']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-area-chart fa-stack-1x"></i></span>';
	$gFW_icons['r0027']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-line-chart fa-stack-1x"></i></span>';
	$gFW_icons['r0028']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-line-chart fa-stack-1x fa-transparent"></i></span>';
	$gFW_icons['r0029']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-pie-chart fa-stack-1x"></i></span>';
	$gFW_icons['r0030']='<span class="fa-stack fa-2x"><i class="fa fa-square-o fa-stack-2x"></i><i class="fa fa-pie-chart fa-stack-1x fa-transparent"></i></span>';

}

if (isset($gUseFW3))
{
	$gFWRaiz='gfw/';
}

$gPath=$docRoot."/".$gBASE."/";
$gPath=str_replace('//','/',$gPath);
$gPathDefault=$gPath.$gFWRaiz."/inc/";
if (!file_exists($gPathDefault."gFunctions.php"))
{
	$gFWRaiz='gfw/4.0';
	$gPathDefault=$gPath.$gFWRaiz."/inc/";
}
$gPathImg=$gPath.$gFWRaiz."/img/";
$gPathLib=$gPath.$gFWRaiz."/inc/lib/";
$gPathCss=$gPath.$gFWRaiz."/css/";
$gPathTmp=$gPath."pub/tmp/";
$gPathUsrFiles=$gPath."files/".str_pad($_SESSION['usrIdd'],9,"0",STR_PAD_LEFT);
$gPathFiles=$gPath."files";
$gSystemPathCss=$gPath.$gFWRaiz."/css/";
$gSystemPathImg=$gPath.$gFWRaiz."/img/";

$cookie_file = "/tmp/".$gBASE."-$usrId-CURLCOOKIE";
$_SESSION['gCookie'] = $cookie_file;


/* Detectando OS e dispositivo */

$browser=strtolower($_SERVER['HTTP_USER_AGENT']);
//echo $browser."<BR>";
//$smartphones=array("opera mini", "lynx","windows ce","android","iphone","ipod","mobile","symbian","msie 6.0","opr","OPR/15" );
$smartphones=array("opera mini", "lynx","windows ce","symbian","msie 6.0","opr","OPR/15", "Trident/3.1","IEMobile",  "AUTOID", "SM-G611MT");
$tablets=array("ipad","xoom", "android 4");
$gOs='windows';
$gDevice="web";
foreach ($smartphones as $tipoNavegador)
	if (stripos($browser,$tipoNavegador)!==false)
		$gDevice="mobile";
foreach ($tablets as $tipoNavegador)
	if (stripos($browser,$tipoNavegador)!==false)
		$gDevice="tablet";
		//$gDevice="web";
if (strpos($browser,'linux')!==false)
	$gOs="linux";
if (strpos($browser,'mac os')!==false)
	$gOs="mac";
if ((strpos($browser,'iphone')!==false)||(strpos($browser,'ipad')!==false)||(strpos($browser,'ipod')!==false))
	$gOs="ios";
if (strpos($browser,'android')!==false)
	$gOs="android";
if (strpos($browser,'symbian')!==false)
	$gOs="symbian";
$_SESSION['gDevice']=$gDevice;
/* Include de outros arquivos necessários ao framework */

$gLangR=trim($_REQUEST['gLang']);
$gLangS=trim($_SESSION['gLang']);

if(($gLangR=="") && ($gLangS==""))
{
    $lang = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2);
    switch($lang){
		case 'pt':
			$gLang="pt_BR";
			break;
		case 'es':
			$gLang="es";
			break;
		case 'en':
			$gLang="en";
			break;
		case 'fr':
			$gLang="fr";
			break;
		default:
			$gLang=gVar("global.language");
			break;
	}
    $gLangR=$gLang;
    $gLangS=$gLang;
}

if ($gLangR<>"")
{
	$gLang=$gLangR;
}else
{
	$gLang=$gLangS;
}
if (strpos(gVar("global.languages"),$gLang) === false)
	$gLang=gVar("global.language");

if ($gLang=="")
	$gLang=gVar("global.language");
else
	gVar("global.language",$gLang);

if ($gLang=="")
	$gLang="pt_BR";

include_once $gPathDefault."tr/".$gLang.".php";


// Carregando traduções geradas pelo BD para este site
if (gVar("global.tmp")<>"")
	$arq=$gPath.'/'.gVar("global.tmp").'/'.str_replace(" ","_",gVar("global.site")).'-'.$gLang.".php";
else
	$arq=sys_get_temp_dir().'/'.str_replace(" ","_",gVar("global.site")).'-'.$gLang.".php";
$arq=str_replace('//','/',$arq);

//if ((file_exists($arq)) && ($_REQUEST['gCmd']<>"createTranslationsFiles"))
if (file_exists($arq))
{
	include_once $arq;
}
$arq='';
if(isset($_REQUEST['gLang'])){
   $_SESSION['gLang'] = $_REQUEST['gLang'];
}


/* Tratamentos importantes */
$http="http";
if ($_SERVER['SERVER_PORT'] == 443 || $_SERVER['HTTP_X_FORWARDED_PORT'] == 443)
	$http="https";

if (is_null($gurl) || $gurl=="")
	$gurl=$_SERVER['HTTP_HOST'];
$setup->set("global.url",$gurl);
$gurl=trim($setup->get("global.url"));

$gNetwork="internet";
if (substr($_SERVER['REMOTE_ADDR'],0,8)=="192.168.")
	$gNetwork="local";
if ((substr($_SERVER['SERVER_ADDR'],0,3)=="127") || ((substr($_SERVER['SERVER_ADDR'],0,3)=="192") && (substr($_SERVER['SERVER_ADDR'],0,10)==substr($_SERVER['REMOTE_ADDR'],0,10))))
{
	$gNetwork="local";
}
if (substr($_SERVER['REMOTE_ADDR'],0,10)=="189.89.157.")
        $gNetwork="giusoft";
if (substr($_SERVER['REMOTE_ADDR'],0,12)=="200.254.228.")
        $gNetwork="intermaritima";

$http_base= $gurl.'/'.$gBASE .'/';
$http_base= "$http://" . str_replace("//","/",$http_base);

$http_img = $http_base .$gFWRaiz."/img/";
$http_system_img = $http_base .$gFWRaiz."/img/";
$http_css = $http_base .$gFWRaiz."/css/";
$http_system_css = $http_base .$gFWRaiz."/css/";
$http_inc = $http_base .$gFWRaiz."/inc/";
$http_lib = $http_base .$gFWRaiz."/inc/lib/";
$http_icon=$http_img;
$http_tmp = $http_base ."pub/tmp";
$http_usr_files = $http_base ."files/".str_pad($_SESSION['usrIdd'],9,"0",STR_PAD_LEFT);
$http_files = $http_base ."files";


if (file_exists($gPath."pub/css"))
{
	$gPathCss=$gPath."pub/css/";
	$http_css = $http_base ."pub/css/";
}
if (file_exists($gPath."pub/img"))
{
	$gPathImg=$gPath."pub/img/";
	$http_img = $http_base ."pub/img/";
}
$flds="";
$flts="";

$gPathUsrFiles.="/app".str_pad(intval($_SESSION['gIdApp']),9,"0",STR_PAD_LEFT)."/";
$http_usr_files.="/app".str_pad(intval($_SESSION['gIdApp']),9,"0",STR_PAD_LEFT)."/";

/** Obtém ou altera o valor de um parâmetro de configuração do sistema
 * @author	giuliano
 * @version	1.0 24-07-2009 17:55
 * @param mixed $param Descrição da variável
 * @return mixed $sai
 */
function gVar($param,$new=":null:")
{
	global $setup;
	$sai='';
	if (is_object($setup))
	{
		if ($new<>":null:")
		{
			$setup->set($param,$new);
		}
		$sai=$setup->get($param);
	}
	return($sai);
}

function gLangIndex($text)
{
	$txt=substr($text,0,100);
	if ($txt<>$text)
		$txt=substr($txt,0,strrpos($txt," "));
	$txt=gCleanField($txt);
	$txt=trim(strtolower(autoencode($txt)));
	return($txt);
}

function gLang($t_word)
{
	// * Retorna o valor da variavel "parameter", da matriz "glang" (variavel de aplicacao)
	global $gDebug;
	global $gLngs;
	$ac['Á']='á';
	$ac['É']='é';
	$ac['Í']='í';
	$ac['Ó']='ó';
	$ac['Ú']='ú';
	$ac['À']='à';
	$ac['È']='è';
	$ac['Ì']='ì';
	$ac['Ò']='ò';
	$ac['Ù']='ù';
	$ac['Â']='â';
	$ac['Ê']='ê';
	$ac['Ô']='ô';
	$ac['Ã']='ã';
	$ac['Õ']='õ';
	$ac['Ü']='ü';

	$ot_words=$t_word;
	$l_word=str_replace(".long","",$t_word);
	$l_word=trim(strtolower(str_replace(".short","",$l_word)));
	$l_word=gLangIndex($l_word);
	$sai=false;
	if (array_key_exists($l_word,$gLngs))
	{
		$agLng=$gLngs[$t_word];
		if (!is_array($agLng))
			$gLng=$agLng;
		else
			$gLng=$agLng[0];

		if ($gLng=="")
		{
			// Converte acentos em maiúscula pra minúcula
			for ($a=0; $a<strlen($t_word); $a++)
			{
				if ($ac[$t_word[$a]]<>"")
					$t_word[$a]=$ac[$t_word[$a]];
				else
					$t_word[$a]=strtolower($t_word[$a]);
			}
			if (stripos($t_word,".long")!==false)
			{
				$t_words=explode(".",$t_word);
				if (count($t_words)>2) // Se tiver 3 parametros separados por ponto, ignora o primeiro
					$t_words=array($t_words[1],$t_words[2]);
				if ($t_words[1]=="") // Se nao tiver a especificacao do tamanho, como padrao sera SHORT
					$t_words[1]=="short";

				$agLng=$gLngs[$t_words[0]];
				if (!is_array($agLng))
				{
					$gLng=$agLng;
				}
				else
				{
					if ($t_words[1]=="long")
					{
						if (count($t_words)>1)
							$gLng=$agLng[1];
						else
							$gLng=$agLng[0];
					}
					else
					{
						$gLng=$agLng[0];
					}
				}
			} else
			{
				$agLng=$gLngs[$l_word];
				if (!is_array($agLng))
					$gLng=$agLng;
				else
					$gLng=$agLng[0];
			}
		}
		$sai=$gLng;
	}
//	gLog("========= [$l_word] $ot_words >> $t_word >> $gLng");

/*
	$gTr=$_SESSION['gTr'];
	if ((($t_word==$sai)|| ($sai=="")) && ($gTr[$t_word]<>""))
	{
		$sai=$gTr[gCleanField($t_word)][0];
	}
*/
	//gLog("====> tr ".$_SESSION['usrLang']."-> [$t_word] = [$sai]");
	return($sai);
}

function gLng($t_word)
{
	$gLng=gLang($t_word);
	if ($gLng=="")
	{
		$gLng=$t_word;
	}

	return($gLng);
}


//======================================= funcoes CSS =======================================

	/** Recebe um array com CSS e outro com valores padrões e une os dois (priorizando o CSS)
	 * @author	giuliano
	 * @version	1.0 17-07-2009 10:08
	 * @param array $array Array com elementos do CSS
	 * @param array $default Array com valores padrao
	 * @return array $sai Array com o somatório dos dois
	 */
	function cssMerge($array,$default="")
	{
		$quitarray=true;
		if (!is_array($array))
		{
			$quitarray=false;
			$array=cssDecode($array);
		}
		if (!is_array($default))
			$default=cssDecode($default);
		foreach ($array as $key=>$value)
		{
			$default[$key]=$value;
		}
		$new="";
		foreach ($default as $key=>$value)
		{
			$new[$key]=$value;
		}
		//if (!$quitarray)
		//	$new=cssEncode($new);
		return ($new);
	}

	/** Recebe um array e remove  a chave passada como segundo parâmetro
	 * @author	giuliano
	 * @version	1.0 17-07-2009 10:08
	 * @param array $mtz Array com elementos do CSS
	 * @param array $field Campo a ser removido
	 * @return string $sai Novo array sem o campo
	 */
	function cssRemove($array,$field)
	{
		$sai=$array;
		if (is_array($array))
		{
			if (array_key_exists($field,$array))
			{
				$sai="";
				foreach ($array as $key=>$value)
				{
					if ($key<>$field)
					{
						$sai[$key]=$value;
					}
				}
			}
		}
		return ($sai);
	}

	function putQuotation($mtz)
	{
		foreach ($mtz as $key=>$value)
		{

			if ((substr($value,0,1)<>"'") && (substr($value,0,1)<>'"'))
			{
				if ((!is_numeric($value)) && (strtolower($value)<>"true")&& (strtolower($value)<>"false"))
				{
					$mtz[$key]="'".$value."'";
				}
			}
		}
		return($mtz);
	}

	/** Recebe um array e retorna uma string formatada CSS
	 * @author	giuliano
	 * @version	1.0 17-07-2009 10:08
	 * @param array $mtz Array com elementos do CSS
	 * @param array $default Array com valores padrao
	 * @return string $sai
	 */
	function cssEncode($mtz,$default="")
	{
		$sai = "";
		$new = cssMerge($mtz, $default);
		if ($new <> "") {
			foreach ($new as $key => $value) {
				if (($key <> "") && ($value <> "")) {
					if (is_array($value))
					{
						$value=json_encode($value);
						$value=str_replace('"',"",$value);
						$value=substr($value,1,strlen($value)-2);
						$el = $key . ": " . $value . "; ";
					} else
					{
						$el = $key . ": " . $value . "; ";
					}
					$sai.=$el;
				}
			}
			if ($sai <> "") {
				$sai = "{" . substr(trim($sai), 0, strlen($sai) - 2) . "}";
			}
		}
		return ($sai);

	}

	/** Transforma uma string CSS em um array
	 * @author	giuliano
	 * @version	1.0 12-06-2009 16:21
	 * @param string $css String CSS
	 * @param boolean $format Remove \n e \t ?
	 * @return mixed $mtz Descrição da variável
	 */
	function cssDecode($css)
	{
		$sai="";
		$css=html_entity_decode($css,ENT_NOQUOTES,'UTF-8');
        if (strpos($css,"[")!==false)
		{
			$b=strpos($css,"[")+1;
			for($a=$b; $a<strlen($css); $a++)
			{
				if ($css[$a]=="{") $css[$a]="^";
				if ($css[$a]=="}") $css[$a]="‘";
				if ($css[$a]=="]") break;
			}
		}

        $items="";
		if (strpos($css,"items:")!==false)
		{
			$i=explode("items:",$css);
            if (substr(trim($i[1]),0,1)=="'")
			{
				$items=substr(trim($i[1]),1);
				$ini=strpos($items,"'");
				$fim=strrpos($items,"'");
				$css=$i[0].substr($items,$ini+2)."}";
				$items=substr($items,0,$ini);
				$items=str_replace("\"","'",$items);
			}
            elseif (strtolower(substr(trim($i[1]),0,6))=="select")
			{
				$items=str_replace("}","",trim($i[1]));
				if (strpos($items,";")!==false)
				{
					$items=substr($items,0,strpos($items,";"));
				}
			}
		}

        $vls="";
		if (strpos($css,"value:")!==false)
		{
            $i=explode("value:",$css);

            if (substr(trim($i[1]),0,1)=="'")
			{
				$vls=substr(trim($i[1]),1);
                $ini=strpos($vls,"'");
				$fim=strrpos($vls,"'");
				$css=$i[0].substr($vls,$ini+2)."}";

                $vls=substr($vls,0,$fim);

                $vls=str_replace("\"","'",$vls);
			}

		}


		$css=str_replace("{","",$css);
		$css=str_replace("}","",$css);
        $array=explode(";",$css);

        foreach ($array as $value)
		{
			$value=trim($value);
            $key=substr($value,0,strpos($value,":"));
			$value=trim(str_replace("'","",substr($value,strpos($value,":")+1)));
			$new=array($key,$value);
			if (trim($new[0])<>"")
			{
				$val=trim($new[1]);
				$val=str_replace("^","{",$val);
				$val=str_replace("‘","}",$val);
				$val=str_replace("`", "'", $val);

				if (substr($val,0,1)=="[")
				{
					if (strpos($val,"|")!==false)
						$val=explode("|",substr($val,1,strlen($val-3)));
				}
				$sai[trim($new[0])]=$val;
			}
		}

		if ($items<>"")
        {
			$sai['items']=trim($items);
        }
		if ($vls<>"")
        {
            if($mostrar)
            {
                //echo "gStart:".$vls."<BR><BR>";
            }
            $sai['value']=trim($vls);
        }
		foreach ($sai as $key=>$item)
		{
			if (substr($item,0,10)=="--(encode)")
				$sai[$key]=base64_decode(substr($item,10));
		}
		return ($sai);
	}



/** Classe responsavel pelo tratamento da configuracao do sistema
 * @package	gSetup
 * @author	giuliano
 * @version	1.0 24-07-2009 17:19
 */
class gSetup
{
	private $classes;
	function __construct($json)
	{
		// default {par1: valor; par2: valor;} database {par1: valor}

		$stp=trim(str_replace("\n","",$json));
		$mtz=explode("}",$stp);
		$new="";
		foreach ($mtz as $el)
		{
			if (strpos($el,"{")!==false)
			{
				$class=trim(substr($el,0,strpos($el,"{")));
				$parm=substr($el,strpos($el,"{")+1);
				$parm=trim(substr($parm,0,strlen($parm)-1));
				$parms=cssDecode($parm);
				foreach ($parms as $key=>$value)
					$this->classes[$class.".".$key]=$value;
			}
		}
	}

	/** Obtém o valor de um parâmetro do setup
	 * @author	giuliano
	 * @version	1.0 24-07-2009 17:35
	 * @param string $class  Parametro desejado (formato: classe.parametro)
	 * @return mixed $sai valor
	 */
	public function get($class)
	{
		return $this->classes[$class];
	}

	public function getCss()
	{
		$str=$tmp="";
		foreach ($this->classes as $key=>$value)
		{
			$chave=explode(".",$key);
			$classe=$chave[0];
			$nomes="";
			for ($a=1; $a<count($chave);$a++)
				$nomes[]=$chave[$a];
			$nome=implode(".",$nomes);
			$valor=$value;
			if ($classe<>$tmp)
			{
				if ($tmp<>"")
					$str.="}\n";
				$tmp=$classe;
				$str.=$classe." {";
			}
			$str.="\t$nome:$valor;\n";
		}
		$str.="}";
		return($str);
	}
	/** Obtém o valor de todos os parâmetros do setup
	 * @author	giuliano
	 * @version	1.0 24-07-2009 17:35
	 * @return mixed $sai valor
	 */
	public function getAll()
	{
		return ($this->classes);
	}

	/** Altera o valor de um parâmetro do setup
	 * @author	giuliano
	 * @version	1.0 24-07-2009 17:35
	 * @param string $class  Parametro desejado (formato: classe.parametro)
	 * @param string $value  Novo valor
	 * @return mixed $sai valor
	 */
	public function set($class,$value)
	{
		return $this->classes[$class]=$value;
	}
}

function gfw4icon($icon)
{
	global $gFW_icons;
	$sai=$icon;
	if (isset($gFW_icons[$icon]))
		$sai=$gFW_icons[$icon];
	return($sai);
}


include_once $gPathLib.'recaptcha/recaptchalib.php';
require_once $gPathDefault."gFunctions.php";
include_once $gPathDefault."gDB.php";

// Inclui e instancia a classe ajax

if (!isset($gUseFW3))
{
	include_once $gPathDefault."gAjax.php";
	$gAjax = new gAjax();
}

// Recria arquivos de cache das traduções
if ($_REQUEST['gCmd']=="createTranslationFiles")
{

	if (gVar("global.languages")<>"")
	{
		gLog("==> Criando arquivos de tradução");
		$langs = explode(",", str_replace(" ", "", gVar("global.languages")));
		$sql = "SELECT *
					FROM ".gVar("database.i18n")."
					ORDER BY locale='".$langs[0]."' desc, keyword";
		$rsTrTmp = dbQuery($sql);
		foreach ($rsTrTmp as $row) {
			// O padrão é o primeiro
			if ($row['locale'] == $langs[0]) {
				$en[$row['keyword']] = $row['text'];
			}
			if ($row['text'] <> '') {
				$tr[$row['locale']].='$gLngs["' . $row['keyword'] . '"]=array("' . $row['text'] . '");' . "\n";
			} else {
				// Não havendo tradução para este idioma, será usado o inglês
				$tr[$row['locale']].='$gLngs["' . $row['keyword'] . '"]=array("' . $en[$row['keyword']] . '");' . "\n";
			}
		}
		if (gVar("global.tmp")<>"")
			$pre = $gPath.'/'.gVar("global.tmp") . '/' . str_replace(" ", "_", gVar("global.site")) . '-';
		else
			$pre = sys_get_temp_dir() . '/' . str_replace(" ", "_", gVar("global.site")) . '-';
		$pre = str_replace('//', '/', $pre);
		foreach ($tr as $key => $value) {
			$arq = $pre . $key . ".php";
			//$html.="Gerando arquivo: $arq<br><code>".nl2br($value)."</code>";
			gLog("==> Salvando arquivo de tradução deste site: $arq");
			file_put_contents($arq, "<?\n" . $value . "?>\n");
		}
		// Carregando traduções geradas pelo BD para este site
		if (gVar("global.tmp")<>"")
			$arq=$gPath.'/'.gVar("global.tmp").'/'.str_replace(" ","_",gVar("global.site")).'-'.$gLang.".php";
		else
			$arq=sys_get_temp_dir().'/'.str_replace(" ","_",gVar("global.site")).'-'.$gLang.".php";
		$arq=str_replace('//','/',$arq);

		//if ((file_exists($arq)) && ($_REQUEST['gCmd']<>"createTranslationsFiles"))
		if (file_exists($arq))
		{
			include_once $arq;
		}

	}
}


if (isset($gIncludes) && ($_REQUEST['gIncludes']==''))
{
   if (is_array($gIncludes))
   {
      foreach ($gIncludes as $gI)
      {
			$file=str_replace("\\","",$gI);
			$file=str_replace("/","",$file);
			$file=str_replace(".php","",$file).".php";
			$pontos=explode(".",$file);
			if ((file_exists($gPathDefault.$file)) && (count($pontos)==2))
				require_once $gPathDefault.$file;
      }
   } else
   {
		$file=str_replace("\\","",$gIncludes);
		$file=str_replace("/","",$file);
		$file=str_replace(".php","",$file).".php";
		$pontos=explode(".",$file);
		if ((file_exists($gPathDefault.$file))&& (count($pontos)==2))
		{
			include_once $gPathDefault.$file;
		}
	}

	if (file_exists('../config.php')) require_once '../config.php';
	if (file_exists('../sql.php')) require_once '../sql.php';
	if (file_exists('config.php')) require_once 'config.php';
	if (file_exists('class.php')) require_once 'class.php';

	// Includes automaticos

	$FILE=$docRoot."/".$gBASE."/sp.php";
	if (file_exists($FILE))
		include_once $FILE;

	$FILE=$docRoot."/".$gBASE."/res/sp.php";
	if (file_exists($FILE))
		include_once $FILE;
	$FILE=$docRoot."/".$gBASE."/res/config.php";
	if (file_exists($FILE))
		include_once $FILE;

}

// Recria arquivos de cache do BD
if ($_REQUEST['gCmd']=="createDBCache")
{
	gLog("==> Forçando recriação de cache do DB");
	gLoadDBCache(true);
}




function faceRecognition($json)
{
	global $o,$http_inc, $http_base, $html;
	$os = getOS();
	$http_img="gfw/img/";
	$pv = true;
	$maxPv = 1;
	$qualidadeJpeg = 0.6;
	$href = 'window.location.href = "'.$o->page.'";';

	$jarr = cssDecode($json);
	$jarr['width']=320;
	$jarr['height']=240;
	if ($jarr['pv']=="false")
	{
		$pv = false;
	}
	if ($jarr['maxPv']<>"")
	{
		$maxPv = intval($jarr['maxPv'])*2 + 1;
	}
	if ($jarr['quality']<>"")
	{
		$qualidadeJpeg = floatval($jarr['quality']);
	}
	if ($jarr['href']<>"")
	{
		$href = gCleanField($jarr['href']);
	}
	
	$usrName = $_SESSION['usrName'];

	$o->out('<script>var usarPV = '.($pv ? "true": "false").';var maxPV = '.$maxPv.';var qualidadeJpeg='.$qualidadeJpeg.';</script>', gLOC_POS);	
	$o->out('<script src="' . $http_inc . 'lib/face-api/face-api.min.js"></script>', gLOC_POS);
	$o->out('<script src="' . $http_inc . 'gFacial.js?'.date('ms').'"></script>', gLOC_POS);
	$aditionalStyle = "";
	
	if ($os=="iPhone") {
		// Celular
		$aditionalStyle = '#mask1 {transform: translate(-50%,-0%);} #mask1 {transform: translate(-50%,-0%);}';
	}
	if ($os=="iPad" || $p['browser']=='Safari') {
		// Desktop ou tablet
		$aditionalStyle = '#mask0 {transform: translate(-50%,-0%);} #mask1 {transform: translate(-50%,-0%);}';
	}
	$html.='
	<style>
	canvas {
		position: absolute;
		-webkit-transform: scaleX(-1);
		transform: scaleX(-1);
	}
	#title {
		color: black;
		text-align: center;
	}
	#screenshot {
		display:none;
		width: '.$jarr['width'].'px;
		height: '.$jarr['height'].'px
		border: 1px solid #4cae4c;
	}
	#faceDetection{
		margin: 0;
		padding: 0;
	}
	video {
	}
	.screenshot {
		-webkit-transform: scaleX(-1);
		transform: scaleX(-1);
	}
	.close {
		color: #aaaaaa;
		float: right;
		font-size: 28px;
		font-weight: bold;
	}
	.close:hover,
	.close:focus {
		text-decoration: none;
		cursor: pointer;
	}
	.timer {
		font-size: 8px;
		display: inline-block;
		padding: 6px 6px 0px 6px;
		text-align: center;
	}
	.clock {
		font-size: 17px;
		text-align: center;
	}'
	.$aditionalStyle.'
	</style>
';

	$html.='
	<div style="width: 100%; margin: auto; padding: 20px">
	<div style="width: 350px; margin: auto; height: 480px;text-align: center; border: 1px solid #aaa; background-color: #eee; padding: 15px; border-radius: 10px; box-shadow: rgba(50, 50, 93, 0.25) 0px 50px 100px -20px, rgba(0, 0, 0, 0.3) 0px 30px 60px -30px, rgba(10, 37, 64, 0.35) 0px -2px 6px 0px inset;">
	<div id="faceDetection" style="margin: auto;">
		<img id="mask0" src="'.$http_img.'pv_mask0.png" style="display: none; position: absolute; margin: auto; z-index: 100">
		<img id="mask1" src="'.$http_img.'pv_mask1.png" style="display: none; position: absolute; margin: auto; z-index: 101">
		<video style="border: 1px solid #bbb" id="video" width="'.$jarr['width'].'" height="'.$jarr['height'].'" autoplay muted playsinline="true" webkit-playsinline="true"></video>
		<figure >
			<img id="screenshot" src="'.$http_img.'0.jpg" alt="screenshot" class="img img-rounded img-responsive">
		</figure>
	</div>
	<h4 id="title"></h4>
	<div id="pv0" style="display:none; padding-bottom: 6px"><img class="img img-rounded" width="80" height="80" src="'.$http_img.'pv0.jpg"></div>
	<div id="pv1" style="display:none; padding-bottom: 6px"><img class="img img-rounded" width="80" height="80" src="'.$http_img.'pv1.jpg"></div>
	<div id="pv2" style="display:none; padding-bottom: 6px"><img class="img img-rounded" width="80" height="80" src="'.$http_img.'pv2.jpg"></div>
	
	<div id="mensagemOk" style="display: none">
		<h3 style="color: #000">'.$usrName.'</h3>
		<h4 style="color: green">Identidade reconhecida!</h4><br>
	</div>

	<div id="erroProvaDeVida" style="display: none">
		<h3 style="color: #000">'.$usrName.'</h3>
		<span style="color: red">Não foi possível confirmar sua identidade!</span><br>
			<div class="text-left" style="margin: auto; width: 300px">
				<ul style="color: #999">
					<li>Retire óculos ou máscara</li>
					<li>Mantenha o rosto bem iluminado</li>
					<li>Afaste ou aproxime um pouco</li>
					<li>Imite as expressões faciais mostradas</li>
				</ul>
			</div>
		<button type="button" id="btnTentarNovamente" onClick="coletarBiometria()" class="btn btn-danger" style="display:none">Tentar novamente</button>
	</div>
	<div id="erroForaHorario" style="display: none">
		<h3>'.$usrName.'</h3>
		<span style="">Não é possível obter a foto, pois a aula já terminou!</span><br>
		<a id="btnErroSair" href="#" onClick="sairDaAula(false)" class="btn btn-danger" style="display:none">Sair</a>
	</div>
	<div id="erroNaoFoiPossivel" style="display: none">
		<h3>'.$usrName.'</h3>
		<span style="">Não é possível obter a foto neste momento!</span><br>
		<span style="">Motivos possíveis:</span><br>
		<a id="btnErroSairFora" href="#" onClick="sairDaAula(false)" class="btn btn-danger" style="display:none">Sair</a>
	</div>
	<div id="enviandoImagem" style="display: none">
		<span style=""><i class="fa fa-spinner fa-pulse fa-1x fa-fw"></i> Enviando imagem...</span><br>
	</div>
	<div id="faceDetectionCanvas"></div>

	<span id="log" style="color:white; font-size: 10px; text-align: left;padding: 20px"></span>
	</div>
	</div>
	';

	$js = '
	function callBackOk()
	{
		'.$href.'
	}

	fila = "coletarBiometria";
	startUp();
	requestPermissions();
	';
	$o->addJavascript($js);
}


/*
	Instrucoes:

	Deve ser acessado na porta 80
	Enviar requisicao POST em formato JSON com os seguintes parametros:

	{task: compare; img1: [caminho-imagem-1], img2: [caminho-imagem-2]}

	Tasks:

		compare     - Compara duas fotos com rostos e retorna se a pessoa foi reconhecida
		mark        - Identifica os rostos e marca com um quadrado
		get         - Identifica os rostos e retorna array com as imagens em base64
		count       - Retorna a quantidade de rostos na foto

	Os parametros img são os caminhos das imagens .jpg
	
	Resultado (em formato JSON)

	status: ok ou error
	result: 0..100 (percentual de correspondência)
*/
/**
 * @TODO Remover essa bangunça que fiz nesse método
 */
function gFacialService($json,$server='')
{
	$ch = curl_init();
	if($server=='')
		curl_setopt($ch, CURLOPT_URL,"http://127.0.0.1:8080");
	else{
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		if($server=="2"){
			curl_setopt($ch, CURLOPT_URL,"http://10.0.0.97:8000");
		}
		else{
			if($_SERVER['HTTP_HOST']=='127.0.0.1' || $_SERVER['HTTP_HOST']=="localhost")
				curl_setopt($ch, CURLOPT_URL,"http://168.138.128.2/facialservice/");
			else
				curl_setopt($ch, CURLOPT_URL,"http://10.0.0.97/facialservice/");
		}
	
	}
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	$info = curl_getinfo($ch);
	gLog(json_encode($info));
	$server_output = curl_exec($ch);
	curl_close ($ch);
	if ($server_output<>"")
	{
		$jarr = json_decode($server_output, true);	
	} else {
		$jarr = [];
	}	
	return($jarr);
}

function biocheck360($imageData) {
	
	// $url = "http://10.0.1.176:8080/v1";
	$url = "167.234.233.9:8080/v1";

	$FaceToMatch = array(
		"Id"           		  => $imageData['id'],
		"Label"         	  => $imageData['label'],
		"Db"         		  => $imageData['db'],
		"QualityCheck"  	  => $imageData['QualityCheck'] ?: true,
		"Spoofing"      	  => $imageData['Spoofing'] ?: true,
		"Normalize"     	  => $imageData['Normalize'] ?: false,
		"SmallFaces"		  => $imageData['SmallFaces'] ?: false,
		"FacesLocations"	  => $imageData['FacesLocations'] ?: false,
		"DrawMarks"     	  => $imageData['DrawMarks'] ?: true,
		"DrawSquares"   	  => $imageData['DrawSquares'] ?: true,
		"ImagesFound"   	  => $imageData['ImagesFound'] ?: false,
		"DebugImage"    	  => $imageData['DebugImage'] ?: false,
		"DebugEnabled"		  => $imageData['DebugEnabled'] ?: true,
		"FacesFound"		  => $imageData['FacesFound'] ?: false,
		"ImageToScan"		  => $imageData['ImageToScan']?:'',
		"FaceToScan"		  => $imageData['FaceToScan']?:'',
		"ImageSourceIsPath"	  => ($imageData['ImageSourceIsPath'] == false && isset($imageData['ImageSourceIsPath'])) ? false : true,
		"ConfidenceThreshold" => $imageData['ConfidenceThreshold'] ?: "0.5"
	);

	$request = array(
		"FaceToMatch" => $FaceToMatch
	);

	// Converter os dados em JSON
	$jsonData = json_encode($request);

	// Inicializa a sessão cURL
	$ch = curl_init($url);
	
	// Configurações da requisição cURL
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // Retorna a resposta como string
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',  // Define o tipo de conteúdo como JSON
		'Content-Length: ' . strlen($jsonData)  // Define o comprimento do conteúdo
	]);
	curl_setopt($ch, CURLOPT_POST, true);  // Define que a requisição será POST
	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);  // Define os dados a serem enviados
	curl_setopt($ch, CURLOPT_TIMEOUT, 4); 
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
	// Executa a requisição e obtém a resposta
	$response = curl_exec($ch);
	
	// Verifica se ocorreu algum erro
	if (curl_errno($ch)) {
		return 'Erro na requisição: ' . curl_error($ch);
	} else {
		// Exibe a resposta do servidor
		return json_decode($response, true);
	}
}

/**
 * Rotina de reconhecimento facial via Socket
 * 
 * @param string $cmd Comando a ser enviado para o socket
 * @return string Resposta do socket
 * 
 * Para substituir a função gFacialService por gFacialSocketService (exemplo de uso):
 * 
 * $gFacial = gFacialSocketService("c642 $cpf $img_evento");
 * 
 * 
 * Comandos possíveis:
 * 
 *		cnt1 [cpf] [caminho para a imagem.jpg]
 *
 * 		Retorna a quantidade de rostos encontrados (método mais rápido - melhor para fotos próximas). 
 * 		Exemplo de retorno: 0 2 
 * 		Onde 0 é o erro encontrado (0=ok, >0=erro) e 2 é a quantidade de rostos encontrados
 * 
 * 		cnt2 [cpf] [caminho para a imagem.jpg] 
 * 
 * 		Retorna a quantidade de rostos encontrados (método mais eficiente - melhor para fotos distantes)
 * 		Exemplo de retorno: 0 2 
 * 		Onde 0 é o erro encontrado (0=ok, >0=erro) e 2 é a quantidade de rostos encontrados
 * 
 * 		c641 [cpf] [caminho para a imagem.jpg]
 * 
 * 		Retorna a quantidade de rostos encontrados (método mais rápido - melhor para fotos próximas). 
 * 		Mesma função do cnt1, só que o retorno é um JSON, com os rostos encontrados em formato base64.
 * 		Exemplo de retorno:
 * 		{ "status": 0, "base64faces": [ {"id": 0, "base64": "base64 da face 1"}, {"id": 1, "base64": "base64 da face 2"} ] }
 * 
 * 		c642 [cpf] [caminho para a imagem.jpg]
 * 
 * 		Retorna a quantidade de rostos encontrados (método mais eficiente - melhor para fotos distantes).
 * 		Mesma função do cnt2, só que o retorno é um JSON, com os rostos encontrados em formato base64.
 * 		Exemplo de retorno:
 * 		{ "status": 0, "base64faces": [ {"id": 0, "base64": "base64 da face 1"}, {"id": 1, "base64": "base64 da face 2"} ] }
 * 
 * 		recf [cpf] [caminho para a imagem1.jpg] [caminho para a imagem2.jpg]
 * 
 * 		Faz a comparação entre as duas imagens e retorna o percentual de correspondência (é a pessoa correta).
 * 		Exemplo de retorno: 0 56
 * 		Onde 0 é o erro encontrado (0=ok, >0=erro) e 56 é o percentual que indica a semelhança entre as duas pessoas.
 * 
 * 
 * Códigos de erro:
 * 
 * 		ERR_IMAGENS_NAO_CARREGADAS          1
 * 		ERR_MAIS_DE_UMA_FACE_IMG_1          2
 * 		ERR_MAIS_DE_UMA_FACE_IMG_2          3
 * 		ERR_FACE_NAO_ENCONTRADA_IMG1        4
 * 		ERR_FACE_NAO_ENCONTRADA_IMG2        5
 * 		ERR_FACE_NAO_ENCONTRADA_APOS_AJUSTE 6
 * 		ERR_FALHA_AO_RECORTAR_FACE_1        7
 * 		ERR_FALHA_AO_RECORTAR_FACE_2        8
 * 		ERR_NUMERO_DE_ARGUMENTOS_INVALIDO   9
 * 		ERR_FALHA_AO_SALVAR_IMAGEM_FINAL    10
 * 		ERR_ARQUIVO_NAO_ENCONTRADO          11
 * 		ERR_TENTATIVA_DE_FRAUDE             12 (foto da foto)
 */
function gFacialSocketService($cmd)
{
	gLog("------> Validação via Socket: $cmd");
	$ip = '127.0.0.1'; // deve ser mudado para 10.0.0.69 quando for para produção
	
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	socket_connect($socket, $ip, 7007);
	socket_write($socket, $cmd." 0");
	$line = trim(socket_read($socket,3000000,PHP_NORMAL_READ));
	socket_close($socket);	
	// gLog($line);
	return($line);
}

$cmd=gCleanField($_REQUEST['cmd']);
if ($cmd<>"")
{
	switch($cmd)
	{
		case 'saveImage':
			$usrId = intval($_SESSION['usrId']);
			if ($usrId>0)
			{
				$data_uri = $_POST['data_uri'];
				$filaBiometria = $_REQUEST['filaBiometria'];
				if (preg_match('/^data:image\/(\w+);base64,/', $data_uri, $type))
				{
					$ano=date('Y');
					$fotoCadastrada = $gPathFiles."/geral_pessoas/".$usrId.'.jpg';
					$data = substr($data_uri, strpos($data_uri, ',') + 1);
					$data = base64_decode($data);
					
					//$mediaPath = $gPathFiles."/facial/$ano/"; 
					//mkdir($mediaPath, 0775, true);
					if (file_exists("/mnt/dados/tmp/"))
					{
						$mediaPath = "/mnt/dados/tmp/";
					} else {
						$mediaPath = "/tmp"; 	
					}
					
					$fotoObtida=$mediaPath."facial-".$usrId.".jpg";
					file_put_contents($fotoObtida, $data);
					
					// 
					/**
					 * 
					 * Consultando biometria facial
					 * 
					 * A consulta é realizada em um serviço que está rodando no mesmo host da aplicação
					 * Este serviço é o script facialService.py escrito em python (repositório github.com/giusoft/server)
					 * 
					 */

					$json = '{"task": "compare", "img1":"'.$fotoObtida.'", "img2":"'.$fotoCadastrada.'"}';
					$jarr = gFacialService($json,'n');
					if (count($jarr)>0)
					{
						// echo "\n\n";
						// var_dump($jarr);
						// echo "\n\n";
						if (intval($jarr['result'])>1)
						{
							echo intval($jarr['result']);
							$_SESSION['gScore'] = $jarr['result'];
						} else {
							$_SESSION['gScore'] = 0;
							echo "0";
						}
					} else 
					{
						echo "0";
					}
					unlink($fotoObtida);
				} else {
					echo "0";
				}	
			} else {
				echo "0";
			}
			exit;
		break;
	}
}
?>
