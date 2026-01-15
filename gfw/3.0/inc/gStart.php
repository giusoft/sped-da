<?php
/**
 *  gStart.php
 *
 * @author	Giuliano Nascimento
 * @version	3.0 07-04-2009 14:08
 */

set_error_handler(function ($errno, $errstr){
	throw new Exception($errstr);
	return false;
});
try{
	date_default_timezone_get();
}
catch(Exception $e){
	date_default_timezone_set('UTC');
}
restore_error_handler();

define( 'n', "\n" );
define( 'r', "\r" );

function register_global_array( $sg )
{
	Static $superGlobals = array( 'e' => '_ENV', 'g' => '_GET', 'p' => '_POST', 'c' => '_COOKIE', 'r' => '_REQUEST', 's' => '_SERVER', 'n' => '_SESSION', 'f' => '_FILES' );

	Global ${$superGlobals[ $sg ]};

	foreach ( ${$superGlobals[ $sg ]} as $key => $val )
	{
		$GLOBALS[ $key ] = $val;
	}
}

function register_globals( $order = 'gpcsn' )
{
	$_SERVER;
	$_ENV;
	$_REQUEST;

	$order = str_split( strtolower( $order ) );
	array_map( 'register_global_array', $order );
}

register_globals();

//Setando timezone
if(!empty($_SESSION['defaultTimezone'])){
	date_default_timezone_set($_SESSION['defaultTimezone']);
}

// evitando session injection
if ( isset( $_REQUEST[ '_SESSION' ] ) )
	die( "Error!" );
if ( isset( $_REQUEST[ 'gBASE' ] ) )
	die( "Error!" );
if ( isset( $_REQUEST[ 'gSetup' ] ) )
	die( "Error!" );
if ( isset( $_REQUEST[ 'setup' ] ) )
	die( "Error!" );

$test1 = trim( str_replace( '//', '/', $_SERVER[ 'SCRIPT_FILENAME' ] ) );
$test2 = trim( str_replace( '//', '/', $_SERVER[ 'DOCUMENT_ROOT' ] . "/" . str_replace( $_SERVER[ 'DOCUMENT_ROOT' ], "", $_SESSION[ 'gBASE' ] ) ) );
$test1 = str_replace( "\\", '/', $test1 );
$test2 = str_replace( "\\", '/', $test2 );


if ( ( substr( $_SERVER[ 'PHP_SELF' ], -9 ) == 'login.php' ) && ( $test2 <> substr( $test1, 0, strlen( $test2 ) ) ) )
{
	$_SESSION[ 'gSetup' ] = $gSETUP;
	$_SESSION[ 'gBASE' ]  = $gBASE;
}

if ( isset( $_SESSION[ 'gBASE' ] ) )
	$gBASE = $_SESSION[ 'gBASE' ];

$tp = $_SERVER[ 'DOCUMENT_ROOT' ] . $_SERVER[ "PHP_SELF" ];
if ( ( $_SERVER[ 'DOCUMENT_ROOT' ] . "/" . $gBASE ) != substr( $tp, 0, strlen( $_SERVER[ 'DOCUMENT_ROOT' ] . "/" . $gBASE ) ) )
	unset( $_SESSION[ 'gSetup' ] );

if ( ( isset( $_SESSION[ 'gSetup' ] ) ) && ( $_SESSION[ 'usrId' ] > 0 ) )
	$gSetup = $_SESSION[ 'gSetup' ];
else
	$gSetup = $gSETUP;


// Includes automaticos
$FILE = $_SERVER[ 'DOCUMENT_ROOT' ] . "/" . $gBASE . "/res/sp.php";
if ( file_exists( $FILE ) )
	include_once $FILE;
$FILE = $_SERVER[ 'DOCUMENT_ROOT' ] . "/" . $gBASE . "/res/config.php";
if ( file_exists( $FILE ) )
	include_once $FILE;

$setup       = new gSetup( $gSetup );
$g__download = false;
$ip          = $_SERVER[ 'REMOTE_ADDR' ];

header( "Content-Type: text/html; charset=UTF-8", true );

/* Constantes */

define( "gUSRID", 'usrId' );
define( "gUSRNAME", 'usrName' );

define( 'LOG_NORMAL', 0 );
define( 'LOG_ERROR', 1 );

define( 'gINDEX_AFTER_LOGIN', "index.php" );
define( 'gBAR', '/' );

/* Criando variáveis globais */

$gConstructed    = false;
$htmlBuffer      = "";
$useHtmlBuffer   = false;
$useExtInterface = true;
$gPrintMode      = false;
$extjsBuffer     = "";
$extjsEndBuffer  = "";
$extjsVTypes     = "";
$extjsStack      = "";
$extjsInUse      = false;
$gPath           = $_SERVER[ 'DOCUMENT_ROOT' ] . "/" . $gBASE . "/";
$gPathDefault    = $gPath . "gfw/inc/";
$gPathImg        = $gPath . "gfw/img/";
$gPathLib        = $gPath . "gfw/inc/lib/";
$gPathCss        = $gPath . "gfw/css/";
$gPathTmp        = $gPath . "pub/tmp/";
$gPathUsrFiles   = $gPath . "files/" . str_pad( $_SESSION[ 'usrIdd' ], 9, "0", STR_PAD_LEFT );
$gSystemPathCss  = $gPath . "gfw/css/";
$gSystemPathImg  = $gPath . "gfw/img/";

$cookie_file           = "/tmp/alitem-$usrId-CURLCOOKIE-".$_SERVER['SERVER_ADDR'];
$_SESSION[ 'gCookie' ] = $cookie_file;

//---------------------- OS e Device -----------------------

$browser     = strtolower( $_SERVER[ 'HTTP_USER_AGENT' ] );
//echo $browser."<BR>";
$smartphones = array(
	 "opera mini",
	"lynx",
	"windows ce",
	"android",
	"iphone",
	"ipod",
	"mobile",
	"symbian",
	"msie 6.0",
	"opr",
	"OPR"
);
$tablets     = array(
	 "ipad",
	"xoom",
	"android 4"
);
$gOs         = 'windows';
$gDevice     = "web";
foreach ( $smartphones as $tipoNavegador )
	if ( strpos( $browser, $tipoNavegador ) !== false )
		$gDevice = "mobile";
foreach ( $tablets as $tipoNavegador )
	if ( strpos( $browser, $tipoNavegador ) !== false )
		$gDevice = "tablet";
//$gDevice="web";
if ( strpos( $browser, 'linux' ) !== false )
	$gOs = "linux";
if ( strpos( $browser, 'mac os' ) !== false )
	$gOs = "mac";
if ( ( strpos( $browser, 'iphone' ) !== false ) || ( strpos( $browser, 'ipad' ) !== false ) || ( strpos( $browser, 'ipod' ) !== false ) )
	$gOs = "ios";
if ( strpos( $browser, 'android' ) !== false )
	$gOs = "android";
if ( strpos( $browser, 'symbian' ) !== false )
	$gOs = "symbian";


if ( ( gVar( "global.site" ) == "Intermarítima" ) || ( gVar( "global.site" ) == "gsApp" ) || ( stripos( gVar( "global.site" ), "GS Aplicativos" ) !== false ) )
{
	if ( $gOs == 'ios' )
	{
		$gDevice = "mobile";
		$gOs     = "windows";

	}
	// Forçando o Opera para funcionar como coletor
	if ( strpos( $browser, "opera" ) !== false )
		$gDevice = "mobile";
}

// Forçando o Chrome para funcionar como tablet
//if (strpos($browser,"chrome")!==false)
//	$gDevice="tablet";

//$gDevice="mobile";
//$gOs="ios";$gDevice="mobile";
//echo "device: $gDevice - os: $gOs<br>";exit;

//---------------------- OS e Device -----------------------

if ( $_REQUEST[ 'gExportTo' ] == "PDF" )
	$gDevice = "pdf";
if ( ( $_REQUEST[ 'gExportTo' ] == "Excel" ) || ( $_REQUEST[ 'gExportTo' ] == "Word" ) || ( $_REQUEST[ 'gExportTo' ] == "OpenOffice" ) )
	$gDevice = "office";
/* Include de outros arquivos necessários ao framework */

$gLangR = $_REQUEST[ 'gLang' ];
$gLangS = $_SESSION[ 'gLang' ];

if ( $gLangR <> "" )
{
	$gLang = $gLangR;
}
else
{
	$gLang = $gLangS;
}
if ( $gLang == "" )
	$gLang = gVar( "global.language" );
else
	gVar( "global.language", $gLang );

include_once $gPathDefault . "tr/" . $gLang . ".php";

/* Tratamentos importantes */
$http = "http";
if ($_SERVER['SERVER_PORT'] == 443 || $_SERVER['HTTP_X_FORWARDED_PORT'] == 443)
	$http = "https";

if ( $gurl == "" )
	$gurl = $_SERVER[ 'HTTP_HOST' ];
$setup->set( "global.url", $gurl );
$gurl = trim( $setup->get( "global.url" ) );

$gNetwork = "internet";
if ( substr( $_SERVER[ 'REMOTE_ADDR' ], 0, 8 ) == "192.168." )
	$gNetwork = "local";
if ( ( substr( $_SERVER[ 'SERVER_ADDR' ], 0, 3 ) == "127" ) || ( ( substr( $_SERVER[ 'SERVER_ADDR' ], 0, 3 ) == "192" ) && ( substr( $_SERVER[ 'SERVER_ADDR' ], 0, 10 ) == substr( $_SERVER[ 'REMOTE_ADDR' ], 0, 10 ) ) ) )
{
	$gNetwork = "local";
}
if ( substr( $_SERVER[ 'REMOTE_ADDR' ], 0, 10 ) == "200.254.1." )
	$gNetwork = "giusoft";
if ( substr( $_SERVER[ 'REMOTE_ADDR' ], 0, 12 ) == "200.254.228." )
	$gNetwork = "intermaritima";

$http_base = $gurl . '/' . $gBASE . '/';
$http_base = "$http://" . str_replace( "//", "/", $http_base );

$http_img        = $http_base . "gfw/img/";
$http_system_img = $http_base . "gfw/img/";
$http_css        = $http_base . "gfw/css/";
$http_system_css = $http_base . "gfw/css/";
$http_inc        = $http_base . "gfw/inc/";
$http_lib        = $http_base . "gfw/inc/lib/";
$http_icon       = $http_img;
$http_tmp        = $http_base . "pub/tmp";
$http_usr_files  = $http_base . "files/" . str_pad( $_SESSION[ 'usrIdd' ], 9, "0", STR_PAD_LEFT );

if ( file_exists( $gPath . "pub/css" ) )
{
	$gPathCss = $gPath . "pub/css/";
	$http_css = $http_base . "pub/css/";
}
if ( file_exists( $gPath . "pub/img" ) )
{
	$gPathImg = $gPath . "pub/img/";
	$http_img = $http_base . "pub/img/";
}
$flds  = "";
$flts  = "";
$g__js = "";


$gPathUsrFiles .= "/app" . str_pad( $_SESSION[ 'gIdApp' ], 9, "0", STR_PAD_LEFT ) . "/";
$http_usr_files .= "/app" . str_pad( $_SESSION[ 'gIdApp' ], 9, "0", STR_PAD_LEFT ) . "/";

/** Obtém ou altera o valor de um parâmetro de configuração do sistema
 * @author	Giuliano Nascimento
 * @version	1.0 24-07-2009 17:55
 * @param mixed $param Descrição da variável
 * @return mixed $sai
 */
function gVar( $param, $new = ":null:" )
{
	global $setup;
	$sai = '';
	if ( is_object( $setup ) )
	{
		if ( $new <> ":null:" )
		{
			$setup->set( $param, $new );
		}
		$sai = $setup->get( $param );
	}
	return ( $sai );
}

function gLang( $t_word )
{
	// * Retorna o valor da variavel "parameter", da matriz "glang" (variavel de aplicacao)
	global $gDebug;
	global $gLngs;
	$ac[ 'Á' ] = 'á';
	$ac[ 'É' ] = 'é';
	$ac[ 'Í' ] = 'í';
	$ac[ 'Ó' ] = 'ó';
	$ac[ 'Ú' ] = 'ú';
	$ac[ 'À' ] = 'à';
	$ac[ 'È' ] = 'è';
	$ac[ 'Ì' ] = 'ì';
	$ac[ 'Ò' ] = 'ò';
	$ac[ 'Ù' ] = 'ù';
	$ac[ 'Â' ] = 'â';
	$ac[ 'Ê' ] = 'ê';
	$ac[ 'Ô' ] = 'ô';
	$ac[ 'Ã' ] = 'ã';
	$ac[ 'Õ' ] = 'õ';
	$ac[ 'Ü' ] = 'ü';

	$ot_words = $t_word;
	$l_word   = str_replace( ".long", "", $t_word );
	$l_word   = trim( strtolower( str_replace( ".short", "", $l_word ) ) );
	$sai      = false;

	// Procura primeiro do jeito que está
	if ( array_key_exists( $l_word, $gLngs ) )
	{
		$agLng = $gLngs[ $t_word ];
		if ( !is_array( $agLng ) )
			$gLng = $agLng;
		else
			$gLng = $agLng[ 0 ];

		if ( $gLng == "" )
		{
			// Converte acentos em maiúscula pra minúcula
			for ( $a = 0; $a < strlen( $t_word ); $a++ )
			{
				if ( $ac[ $t_word[ $a ] ] <> "" )
					$t_word[ $a ] = $ac[ $t_word[ $a ] ];
				else
					$t_word[ $a ] = strtolower( $t_word[ $a ] );
			}
			if ( stripos( $t_word, ".long" ) !== false )
			{
				$t_words = explode( ".", $t_word );
				if ( count( $t_words ) > 2 ) // Se tiver 3 parametros separados por ponto, ignora o primeiro
					$t_words = array(
						 $t_words[ 1 ],
						$t_words[ 2 ]
					);
				if ( $t_words[ 1 ] == "" ) // Se nao tiver a especificacao do tamanho, como padrao sera SHORT
					$t_words[ 1 ] == "short";

				$agLng = $gLngs[ $t_words[ 0 ] ];
				if ( !is_array( $agLng ) )
					$gLng = $agLng;
				else
				{
					if ( $t_words[ 1 ] == "long" )
					{
						if ( count( $t_words ) > 1 )
							$gLng = $agLng[ 1 ];
						else
							$gLng = $agLng[ 0 ];
					}
					else
					{
						$gLng = $agLng[ 0 ];
					}
				}
			}
			else
			{
				$agLng = $gLngs[ $t_word ];
				if ( !is_array( $agLng ) )
					$gLng = $agLng;
				else
					$gLng = $agLng[ 0 ];
			}
		}
		$sai = $gLng;
	}
	//gLog("========= $ot_words >> $t_word >> $gLng");

	$gTr = $_SESSION[ 'gTr' ];
	if ( ( ( $t_word == $sai ) || ( $sai == "" ) ) && ( $gTr[ $t_word ] <> "" ) )
	{
		$sai = $gTr[ gCleanField( $t_word ) ][ 0 ];
	}
	//gLog("====> tr ".$_SESSION['usrLang']."-> [$t_word] = [$sai]");
	return ( $sai );
}

function gLng( $t_word )
{
	$gLng = gLang( $t_word );
	if ( $gLng == "" )
	{
		$gLng = $t_word;
	}

	return ( $gLng );
}

//======================================= funcoes CSS =======================================

/** Recebe um array com CSS e outro com valores padrões e une os dois (priorizando o CSS)
 * @author	Giuliano Nascimento
 * @version	1.0 17-07-2009 10:08
 * @param array $array Array com elementos do CSS
 * @param array $default Array com valores padrao
 * @return array $sai Array com o somatório dos dois
 */
function cssMerge( $array, $default = "" )
{
	$quitarray = true;
	if ( !is_array( $array ) )
	{
		$quitarray = false;
		$array     = cssDecode( $array );
	}
	if ( !is_array( $default ) )
		$default = cssDecode( $default );
	foreach ( $array as $key => $value )
	{
		$default[ $key ] = $value;
	}
	$new = "";
	foreach ( $default as $key => $value )
	{
		$new[ $key ] = $value;
	}
	//if (!$quitarray)
	//	$new=cssEncode($new);
	return ( $new );
}

/** Recebe um array e remove  a chave passada como segundo parâmetro
 * @author	Giuliano Nascimento
 * @version	1.0 17-07-2009 10:08
 * @param array $mtz Array com elementos do CSS
 * @param array $field Campo a ser removido
 * @return string $sai Novo array sem o campo
 */
function cssRemove( $array, $field )
{
	$sai = $array;
	if ( is_array( $array ) )
	{
		if ( array_key_exists( $field, $array ) )
		{
			$sai = "";
			foreach ( $array as $key => $value )
			{
				if ( $key <> $field )
				{
					$sai[ $key ] = $value;
				}
			}
		}
	}
	return ( $sai );
}

function putQuotation( $mtz )
{
	foreach ( $mtz as $key => $value )
	{

		if ( ( substr( $value, 0, 1 ) <> "'" ) && ( substr( $value, 0, 1 ) <> '"' ) )
		{
			if ( ( !is_numeric( $value ) ) && ( strtolower( $value ) <> "true" ) && ( strtolower( $value ) <> "false" ) )
			{
				$mtz[ $key ] = "'" . $value . "'";
			}
		}
	}
	return ( $mtz );
}

/** Recebe um array e retorna uma string formatada CSS
 * @author	Giuliano Nascimento
 * @version	1.0 17-07-2009 10:08
 * @param array $mtz Array com elementos do CSS
 * @param array $default Array com valores padrao
 * @return string $sai
 */
function cssEncode( $mtz, $default = "" )
{
	$sai = "";
	$new = cssMerge( $mtz, $default );
	if ( $new <> "" )
	{
		foreach ( $new as $key => $value )
		{
			if ( ( $key <> "" ) && ( $value <> "" ) )
			{
				$el = $key . ": " . $value . "; ";
				$sai .= $el;
			}
		}
		if ( $sai <> "" )
			$sai = "{" . substr( trim( $sai ), 0, strlen( $sai ) - 2 ) . "}";
	}
	return ( $sai );
}

/** Transforma uma string CSS em um array
 * @author	Giuliano Nascimento
 * @version	1.0 12-06-2009 16:21
 * @param string $css String CSS
 * @param boolean $format Remove \n e \t ?
 * @return mixed $mtz Descrição da variável
 */
function cssDecode( $css )
{
	$sai = "";
	$css = html_entity_decode( $css, ENT_NOQUOTES, 'UTF-8' );
	//$css=utf8_encode($css);
	if ( strpos( $css, "[" ) !== false )
	{
		$b = strpos( $css, "[" ) + 1;
		for ( $a = $b; $a < strlen( $css ); $a++ )
		{
			if ( $css[ $a ] == "{" )
				$css[ $a ] = "^";
			if ( $css[ $a ] == "}" )
				$css[ $a ] = "`";
			if ( $css[ $a ] == "]" )
				break;
		}
	}

	$items = "";
	if ( strpos( $css, "items:" ) !== false )
	{
		$i = explode( "items:", $css );
		if ( substr( trim( $i[ 1 ] ), 0, 1 ) == "'" )
		{
			$items = substr( trim( $i[ 1 ] ), 1 );
			$ini   = strpos( $items, "'" );
			$fim   = strrpos( $items, "'" );
			$css   = $i[ 0 ] . substr( $items, $ini + 2 ) . "}";
			$items = substr( $items, 0, $ini );
			$items = str_replace( "\"", "'", $items );
			//echo "ini $ini fim $fim css: $items<br><Br>";
			//gLog(">>>>>>> $items");
		}
		elseif ( strtolower( substr( trim( $i[ 1 ] ), 0, 6 ) ) == "select" )
		{
			$items = str_replace( "}", "", trim( $i[ 1 ] ) );
			if ( strpos( $items, ";" ) !== false )
			{
				$items = substr( $items, 0, strpos( $items, ";" ) );
			}
		}
	}
	$css = str_replace( "{", "", $css );
	$css = str_replace( "}", "", $css );
	$array = explode( ";", $css );

	foreach ( $array as $value )
	{
		$value = trim( $value );
		$key   = substr( $value, 0, strpos( $value, ":" ) );
		$value = trim( str_replace( "'", "", substr( $value, strpos( $value, ":" ) + 1 ) ) );
		$new   = array(
			 $key,
			$value
		);
		if ( trim( $new[ 0 ] ) <> "" )
		{
			$val = trim( $new[ 1 ] );
			$val = str_replace( "^", "{", $val );
			$val = str_replace( "`", "}", $val );
			if ( substr( $val, 0, 1 ) == "[" )
			{
				if ( strpos( $val, "|" ) !== false )
					$val = explode( "|", substr( $val, 1, strlen( $val - 3 ) ) );
			}
			$sai[ trim( $new[ 0 ] ) ] = $val;
		}
	}
	if ( $items <> "" )
		$sai[ 'items' ] = trim( $items );
	foreach ( $sai as $key => $item )
	{
		//echo "($key)$item <br>";
		if ( substr( $item, 0, 10 ) == "--(encode)" )
			$sai[ $key ] = base64_decode( substr( $item, 10 ) );
	}

	return ( $sai );
}



/** Classe responsavel pelo tratamento da configuracao do sistema
 * @package	gSetup
 * @author	Giuliano Nascimento
 * @version	1.0 24-07-2009 17:19
 */
class gSetup
{
	private $classes;
	function __construct( $json )
	{
		// default {par1: valor; par2: valor;} database {par1: valor}

		$stp = trim( str_replace( "\n", "", $json ) );
		$mtz = explode( "}", $stp );
		$new = "";
		foreach ( $mtz as $el )
		{
			if ( strpos( $el, "{" ) !== false )
			{
				$class = trim( substr( $el, 0, strpos( $el, "{" ) ) );
				$parm  = substr( $el, strpos( $el, "{" ) + 1 );
				$parm  = trim( substr( $parm, 0, strlen( $parm ) - 1 ) );
				$parms = cssDecode( $parm );
				foreach ( $parms as $key => $value )
					$this->classes[ $class . "." . $key ] = $value;
			}
		}
	}

	/** Obtém o valor de um parâmetro do setup
	 * @author	Giuliano Nascimento
	 * @version	1.0 24-07-2009 17:35
	 * @param string $class  Parametro desejado (formato: classe.parametro)
	 * @return mixed $sai valor
	 */
	public function get( $class )
	{
		$sai = '';
		if (isset($this->classes[ $class ]))
			$sai = $this->classes[ $class ];
		return $sai;
	}

	public function getCss( )
	{
		$str = $tmp = "";
		foreach ( $this->classes as $key => $value )
		{
			$chave  = explode( ".", $key );
			$classe = $chave[ 0 ];
			$nomes  = "";
			for ( $a = 1; $a < count( $chave ); $a++ )
				$nomes[ ] = $chave[ $a ];
			$nome  = implode( ".", $nomes );
			$valor = $value;
			if ( $classe <> $tmp )
			{
				if ( $tmp <> "" )
					$str .= "}\n";
				$tmp = $classe;
				$str .= $classe . " {";
			}
			$str .= "\t$nome:$valor;\n";
		}
		$str .= "}";
		return ( $str );
	}
	/** Obtém o valor de todos os parâmetros do setup
	 * @author	Giuliano Nascimento
	 * @version	1.0 24-07-2009 17:35
	 * @return mixed $sai valor
	 */
	public function getAll( )
	{
		return ( $this->classes );
	}

	/** Altera o valor de um parâmetro do setup
	 * @author	Giuliano Nascimento
	 * @version	1.0 24-07-2009 17:35
	 * @param string $class  Parametro desejado (formato: classe.parametro)
	 * @param string $value  Novo valor
	 * @return mixed $sai valor
	 */
	public function set( $class, $value )
	{
		return $this->classes[ $class ] = $value;
	}
}

require_once $gPathDefault . "gFunctions.php";


if ( $gGo == "" )
{
	if ( $_SESSION[ 'gLngs' ] == "" )
	{
		if ( isset( $_SESSION[ 'gLanguage' ] ) )
		{
			$gL = $_SESSION[ 'gLanguage' ];
		}
		else
		{
			$gL = $gCfg[ "global.language" ];
			if ( $gL == "" )
			{
				$gL = "pt_BR";
			}
		}
		if ( $_SESSION[ 'usrLangString' ] <> "" )
		{
			$gL = $_SESSION[ 'usrLangString' ];
		}
		$s = $gPathDefault . "tr" . gBAR . $gL . ".php";
		if ( $gL == "en" )
			gVar( "global.dateformat", "mm-dd-yy" );
		include $s;

		$_SESSION[ 'gLngs' ] = $gLngs;
	}
	else
		$gLngs = $_SESSION[ 'gLngs' ];
}

include_once $gPathDefault . "gDB.php";

if ( isset( $gIncludes ) && ( $_REQUEST[ 'gIncludes' ] == '' ) )
{
	if ( is_array( $gIncludes ) )
	{
		foreach ( $gIncludes as $gI )
		{
			$file   = str_replace( "\\", "", $gI );
			$file   = str_replace( "/", "", $file );
			$file   = str_replace( ".php", "", $file ) . ".php";
			$pontos = explode( ".", $file );
			if ( ( file_exists( $gPathDefault . $file ) ) && ( count( $pontos ) == 2 ) )
				require_once $gPathDefault . $file;
		}
	}
	else
	{
		$file   = str_replace( "\\", "", $gIncludes );
		$file   = str_replace( "/", "", $file );
		$file   = str_replace( ".php", "", $file ) . ".php";
		$pontos = explode( ".", $file );
		if ( ( file_exists( $gPathDefault . $file ) ) && ( count( $pontos ) == 2 ) )
		{
			include_once $gPathDefault . $file;
		}
	}
	if ( file_exists( '../config.php' ) )
		require_once '../config.php';
	if ( file_exists( '../sql.php' ) )
		require_once '../sql.php';
	if ( file_exists( 'config.php' ) )
		require_once 'config.php';
	if ( file_exists( 'class.php' ) )
		require_once 'class.php';
}

// Segurança...

$usrId                = $_SESSION[ 'usrId' ];
$gDatabasePermissions = "SIUDL";
if ( ( isset( $_SESSION[ 'gLinks' ] ) ) && ( false ) )
{
	if ( $usrId <> 1 )
	{
		$perm = $_SESSION[ 'gLinks' ];
		$aqui = str_replace( "\\", '/', $_SERVER[ 'PHP_SELF' ] );
		$file = explode( '/', str_replace( '//', '/', $aqui ) );
		$max  = count( $file );
		if ( ( $max > 4 ) && ( $file[ $max - 2 ] <> 'inc' ) )
		{
			$f     = str_replace( ".php", "", $file[ $max - 1 ] );
			$dfile = $file[ $max - 2 ] . '/' . $f;
			$sql   = gSQLLimit( "select * from links where sigla in ($perm) and link like '$dfile%' ", 1 );
			$rs    = gQuery( $sql );
			if ( $rs->EOF )
			{
				echo gT( "Acesso negado. Experimente logar novamente." );
				exit;
			}
			// para ajustar as permissoes
			/*
			if (intval($_SESSION['usrId'])<>1)
			{
			$gDatabasePermissions="";
			if ($rs->fields['ler']==1) $gDatabasePermissions.="S";
			if ($rs->fields['inserir']==1) $gDatabasePermissions.="I";
			if ($rs->fields['editar']==1) $gDatabasePermissions.="U";
			if ($rs->fields['remover']==1) $gDatabasePermissions.="D";
			if ($rs->fields['selecionar']==1) $gDatabasePermissions.="L";
			}
			*/
		}
	}
}

/*
	Alteração exclusiva para o ERPCFC
	-- Verifica se o registro do CFC está a vencer para colocar mensagem de aviso obrigatória na tela
 */
if($_SESSION['CFC']<>"" && (int) $_SESSION['visualizou']<> 1 && $_GET['t']=="")
{

		$sql="SELECT j.data_vencimento_credenciamento data
				FROM erpcfc_sindauto.geral_pessoas p
				INNER JOIN erpcfc_sindauto.geral_pessoas_juridicas j ON j.id_geral_pessoas=p.id
				WHERE p.apelido='".trim($_SESSION['CFC'])."'
				AND
					j.data_vencimento_credenciamento <> '0000-00-00'
				AND
					DATE(j.data_vencimento_credenciamento) > DATE(NOW())
				AND
					(j.data_vencimento_credenciamento < DATE(NOW()) OR (j.data_vencimento_credenciamento >= DATE(NOW()) AND DATE_SUB(j.data_vencimento_credenciamento, INTERVAL 90 DAY) <= DATE(NOW())))
				";
		$rs=gQuery($sql);
		if(!$rs->EOF){
			?>
			<style>
				.ladv-alert-warning{
					border:1px #faebcc solid;
					font-size: 14px;
					background-color: #fcf8e3;
					margin:40px;
					color:#8a6d3b;
				}
				.texto{
					margin-left: 10px;
					font-size: 18px;
				}
				.botao{
					font-family: Arial, Helvetica, sans-serif;
					font-size: 14px;
					color: #ff0000;
					padding: 10px 20px;
					background: -moz-linear-gradient(
						top,
						#ffffff 0%,
						#ffffff 50%,
						#d6d6d6);
					background: -webkit-gradient(
						linear, left top, left bottom,
						from(#ffffff),
						color-stop(0.50, #ffffff),
						to(#d6d6d6));
					-moz-border-radius: 10px;
					-webkit-border-radius: 10px;
					border-radius: 10px;
					border: 3px solid #ff0000;
					-moz-box-shadow:
						0px 1px 3px rgba(000,000,000,0.5),
						inset 0px 0px 3px rgba(255,255,255,1);
					-webkit-box-shadow:
						0px 1px 3px rgba(000,000,000,0.5),
						inset 0px 0px 3px rgba(255,255,255,1);
					box-shadow:
						0px 1px 3px rgba(000,000,000,0.5),
						inset 0px 0px 3px rgba(255,255,255,1);
					text-shadow:
						0px -1px 0px rgba(000,000,000,0.1),
						0px 1px 0px rgba(255,255,255,1);
				}
			</style>
			<div class="ladv-alert-warning">
				<h1 style="font-size:25px;color:#8a6d3b;text-align:center">Aviso importante!</h1>
				<p class="texto">Fique atento a sua data de renovação de registro junto ao DETRAN BA.</p>
				<p class="texto">O registro do CFC vence dia <font style="font-size:23px;color:#B22222"> <?php echo gDate($rs->fields['data']);?> </font></p>
				<p class="texto">Para saber o Check List de documentos, modelos de ofícios e links para retirar certidões <a href='http://www.sindautobahia.com.br/sindbahia/index.php?g=crt&gPage=1&gId=19&categoria=2' target='_blank'>CLIQUE AQUI</a> !!</p>
				<input type="button" class="botao" value="Acessar o sistema" onclick='window.location.href=window.location.href;'>
			</div>
			<?
			$_SESSION['visualizou']=1;
			exit;
		}
}
?>
