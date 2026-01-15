<?php
/**
 *  gFunctions.php
 *
 * @author	Giuliano Nascimento
 * @version	3.0 07-04-2009 14:08
 */

if ( strtoupper( substr( PHP_OS, 0, 3 ) ) == 'WIN' )
{
	define( 'gAPP_FILE', "/gApp_" );
	setlocale( LC_ALL, 'POSIX' );
	define( 'gLogPath', "/" );
}
else
{
	define( 'gAPP_FILE', "/tmp/gApp_" );
	setlocale( LC_ALL, 'english' );
	define( 'gLogPath', "/var/log/" );
}
define( 'NL', "\n" );


/**
 *  ESTE ARQUIVO NÃO EXIGE A INCLUSÃO DO setup.php NEM DO gStart.php !!!
 *
 * @author	Giuliano Nascimento
 * @version	1.0 21-03-2013 10:59
 */


if ( !function_exists( 'gVar' ) )
{
	function gVar( $par )
	{
		$sai = "";
		switch ( $par )
		{
			case "global.dateformat":
				$sai = "dd-mm-yy";
				break;
			case "global.datenull":
				$sai = "0000-00-00";
				break;
			case "global.language":
				$sai = "pt_BR";
				break;
			case "global.numformat":
				$sai = "0.000,00";
				break;
			case "global.logfile":
				$sai = "gfw.log";
				break;
			case "global.debug":
				$sai = 1;
				break;
			case "global.logcolor":
				$sai = "true";
				break;
		}
		return ( $sai );
	}
}

if ( !function_exists( 'gLng' ) )
{


	function gLng( $par )
	{
		$sai = '';
		switch ( $par )
		{
			case "message.yes.short":
				$sai = "Sim";
				break;
			case "message.no.short":
				$sai = "Não";
				break;
		}
		return ( $sai );
	}
}





if ( !function_exists( 'cssEncode' ) )
{
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
}


if ( !function_exists( 'cssDecode' ) )
{
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

		//echo "JSON: $css <br>";

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
		//echo "JSON: $css - items: $items<br>";

		//echo "css: $css <br><br>\n\n<pre>";
		//$array= preg_explode("/[;]*\\\"([^\\\"]+)\\\"[;]*|" . "[;]*'([^']+)'[;]*|" . "[;]+/", $css, 0, PREG_explode_NO_EMPTY | PREG_explode_DELIM_CAPTURE);
		//$array= preg_explode("/;+/", $css, 0, PREG_explode_NO_EMPTY);
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
}



function gTag( $t_parameter )
{
	global $gTags;
	// * Retorna o TAG constante na variêvel "parameter", da matriz "gschema" (variêvel de aplicaêêo)
	/* Desativado...
	global $gDebug;
	gRead();
	$gschema=gSessionLoad("gschema");
	$ga=0;
	$gttl=count($gschema);
	while ($ga<$gttl)
	{
	if ($gschema[$ga][0]==strtolower($t_parameter))
	{
	$gTag=$gschema[$ga][1];
	$ga=$gttl;
	}
	$ga=$ga+1;
	}
	*/
	//if (($gTag=="") && ($gDebug>0))	{gLog("gStart.php	gTag(" . $t_parameter . ")	\033[31;1mTAG not found !");}
	//elseif ($gDebug==0)	{		$gTag=gHTMLcompact($gTag);	}
	$gTag = $gTags[ $t_parameter ];
	return ( $gTag );
}

function gLog( $txt, $style = 0, $arq = "" )
{
	global $usrId, $usrLogin;

	$logfile    = gLogPath . gVar( "global.logfile" );
	$sqllogfile = '';
	if (gVar( "global.sqllogfile" )<>'')
		$sqllogfile = gLogPath . gVar( "global.sqllogfile" );
	$loglevel   = gVar( "global.debug" );

	if ( $arq <> "" )
		$logfile = gLogPath . $arq;

	// Se o arquivo não existir, não gera log
	if ( $logfile <> "" )
	{
		//$debug=gVar("global.debug");
		$txt = trim( $txt );
		//if ((strlen($txt)<1000) && (strpos($txt,"geral_online")===false) && (strpos($txt,"geral_acessos")===false) && (strpos($txt,"from links")===false) && (strpos($txt,"from geral_links")===false))
		{
			$d = debug_backtrace();
			if ( gVar( "global.logcolor" ) == "true" )
			{
				$cini = "\033[0;00;33m";
				$cpre = "\033[0;40;37m";
				$spre = "\033[0;00;37m";
				$cpos = "\033[0;00;37m";
				$lpre = "\033[0;00;36m";
				$lpos = "\033[0;00;33m";
				if ( ( $style == LOG_ERROR ) )
				{
					$cpre = "\033[1;00;31m";
					$spre = "\033[1;00;31m";
				}
			}
			if ( $fp = fopen( $logfile, "a" ) )
			{
				$d = array_reverse( $d, true );
				array_pop( $d );
				array_pop( $d );
				foreach ( $d as $dlin )
				{
					$deb[ ] = basename( $dlin[ 'file' ] ) . ":" . $dlin[ 'function' ] . ":" . $dlin[ 'line' ];
				}
				if ( is_array( $deb ) )
					$deb = implode( "=> ", $deb );
				$faz = false;
				if ( ( stripos( ( $txt ), "select" ) !== false ) )
					$faz = true;
				if ( ( ( stripos( ( $txt ), "update" ) !== false ) || ( stripos( ( $txt ), "insert" ) !== false ) || ( stripos( ( $txt ), "delete" ) !== false ) ) )
					$faz = true;

				//if (($style==LOG_ERROR) || ($loglevel==1))
				{
					if ( $faz )
					{
						if ( $sqllogfile <> '' )
						{
							file_put_contents( $sqllogfile, date( "y-m-d H:i:s" ) . "\t" . $txt . "\n", FILE_APPEND );
						}
						// Banco de dados
						fputs( $fp, $cini . date( "y-m-d H:i:s" ) . " " . $_SERVER[ "REMOTE_ADDR" ] . "	" . $usrLogin . "(" . $usrId . ")	" . basename( $_SERVER[ "PHP_SELF" ] ) );
						$t = explode( "):", $txt );
						fputs( $fp, "\t" . $t[ 0 ] . "):" );
						fputs( $fp, "\t" . $lpre . $deb . $lpos );
						fputs( $fp, "\n" . $usrLogin . "\t" . $spre . str_replace( NL, ' ', str_replace( "\t", " ", $t[ 1 ] ) ) . $cpos . NL );
					}
					else
					{
						// Outras mensagens
						$cini = "\033[0;00;35m";
						fputs( $fp, $cini . date( "y-m-d H:i:s" ) . " " . $_SERVER[ "REMOTE_ADDR" ] . "	" . $usrLogin . "(" . $usrId . ")	" . basename( $_SERVER[ "PHP_SELF" ] ) );
						fputs( $fp, "\t" . "LOG:" );
						fputs( $fp, "\t" . $lpre . $deb . $lpos );
						fputs( $fp, "\n" . $usrLogin . "\t" . $cpre . $txt . $cpos . NL );
					}

				}
				fclose( $fp );
			}
		}
	}
}

function right( $st, $tam )
{
	return ( substr( $st, strlen( $st ) - $tam ) );
}

function gDateOk( $data )
{
	// formato brasileiro
	$d   = explode( '-', $data );
	$sai = checkdate( $d[ 1 ], $d[ 0 ], "20" . $d[ 2 ] );
	return ( $sai );
}

function gDBDateOk( $data )
{
	// formato DB
	return gDateOk( gDBDate( $data ) );
}


function gHTMLCompact( $t_parameter )
{
	$gsTmp = str_replace( chr( 9 ), "", $t_parameter );
	$gsTmp = str_replace( chr( 8 ), "", $gsTmp );
	$gsTmp = str_replace( chr( 10 ), "", $gsTmp );
	$gsTmp = str_replace( chr( 13 ), "", $gsTmp );
	$gsTmp = str_replace( "  ", "", $gsTmp );
	return ( $gsTmp );
}

function gDateDiff( $from, $to, $negativo = 0 )
{
	/*
	Calcula a diferenca entre duas datas
	Formato em global.dateformat
	*/
	$fmt   = strtolower( gVar( "global.dateformat" ) );
	$charf = '-';
	if ( strpos( $fmt, "-" ) > 0 )
		$charf = '-';
	if ( strpos( $fmt, "/" ) > 0 )
		$charf = '/';
	$mto   = explode( $charf, $to );
	$mfrom = explode( $charf, $from );
	$mfmt  = explode( $charf, $fmt );
	for ( $t = 0; $t < count( $mfmt ); $t++ )
	{
		$dat = $mfmt[ $t ];
		if ( $dat[ 0 ] == "d" )
		{
			$from_day = $mfrom[ $t ];
			$to_day   = $mto[ $t ];
		}
		if ( $dat[ 0 ] == "m" )
		{
			$from_month = $mfrom[ $t ];
			$to_month   = $mto[ $t ];
		}
		if ( $dat[ 0 ] == "y" )
		{
			$from_year = $mfrom[ $t ];
			$to_year   = $mto[ $t ];
		}
	}
	if ( strlen( $from_year ) < 4 )
	{
		if ( $from_year < 50 )
			$from_year = "20" . $from_year;
		else
			$from_year = "19" . $from_year;
	}
	if ( strlen( $to_year ) < 4 )
	{
		if ( $to_year < 50 )
			$to_year = "20" . $to_year;
		else
			$to_year = "19" . $to_year;
	}
	$from_date = mktime( 0, 0, 0, $from_month, $from_day, $from_year );
	$to_date   = mktime( 0, 0, 0, $to_month, $to_day, $to_year );
	$days      = ( $to_date - $from_date ) / 86400;

	/*Adicionado o ceil($days) para garantir que o resultado seja sempre um numero inteiro */
	if ( $days < 0 && $negativo == 0 )
	{
		$days = $days * -1;
	}
	return ceil( $days );
}

/**
 * Processa intervalo entre data e hora especifico
 *
 * @param start {Date|String} valor data-hora inicial
 * @param end {Date|String} valor data-hora final (padrão é a data e hora atual)
 * @param params {array}
 * @param format {String} tipo de retorno desejado
 * @return {integer} valor segundo o formato informado
 */
function gDateTimeDiff( $start, $end = "", $params = null )
{
	$start = trim( $start );
	$end   = trim( $end );

	$_dateTimeStart = new DateTime( $start );
	$_dateTimeEnd   = new DateTime( $end );
	$_dateTimeDiff  = $_dateTimeStart->diff( $_dateTimeEnd );
	$_gDateTime     = array( );

	$_absolute                = ( $_dateTimeDiff->format( "%d" ) * 1440 ) + ( $_dateTimeDiff->format( "%h" ) * 60 ) + $_dateTimeDiff->format( "%i" );
	$_gDateTime[ "absolute" ] = $_absolute;

	if ( isset( $params[ "format" ] ) )
	{
		if ( $params[ "format" ] == "minutes" )
		{
			$_gDateTime[ "formatted" ] = $_absolute . "m";
		}
		else if ( $params[ "format" ] == "hours" )
		{
			$_hour                     = ( $_dateTimeDiff->format( "%d" ) * 24 ) + $_dateTimeDiff->format( "%h" );
			$_time                     = ( $_hour == 0 ? "" : $_hour . "h" ) . $_dateTimeDiff->format( "%i" ) . "m";
			$_gDateTime[ "formatted" ] = $_time;
		}
	}
	return $_gDateTime;
}

function gDateAdd( $from, $days = 1, $month = 0, $year = 0 )
{
	/*
	Soma uma quantidade de dias a data atual
	retorna padrêo brasileiro
	*/
	$fmt   = strtolower( gVar( "global.dateformat" ) );
	$charf = '-';
	if ( strpos( $from, "-" ) > 0 )
		$charf = '-';
	if ( strpos( $from, "/" ) > 0 )
		$charf = '/';
	$mfrom = explode( $charf, $from );
	$charf = '-';
	if ( strpos( $fmt, "-" ) > 0 )
		$charf = '-';
	if ( strpos( $fmt, "/" ) > 0 )
		$charf = '/';
	$mfmt = explode( $charf, $fmt );
	for ( $t = 0; $t < count( $mfmt ); $t++ )
	{
		$dat = $mfmt[ $t ];
		if ( $dat[ 0 ] == "d" )
		{
			$from_day = $mfrom[ $t ];
		}
		if ( $dat[ 0 ] == "m" )
		{
			$from_month = $mfrom[ $t ];
		}
		if ( $dat[ 0 ] == "y" )
		{
			$from_year = $mfrom[ $t ];
		}
	}
	if ( strlen( $from_year ) < 4 )
	{
		if ( $from_year < 50 )
			$from_year = "20" . $from_year;
		else
			$from_year = "19" . $from_year;
	}
	$to_date = gDate( date( "Y-m-d", mktime( 0, 0, 0, $from_month + $month, $from_day + $days, $from_year + $year ) ) );
	return $to_date;
}

function today( $hoje )
{
	$sem    = '';
	$sem[ ] = gT( "domingo" );
	$sem[ ] = gT( "segunda" );
	$sem[ ] = gT( "terça" );
	$sem[ ] = gT( "quarta" );
	$sem[ ] = gT( "quinta" );
	$sem[ ] = gT( "sexta" );
	$sem[ ] = gT( "sábado" );

	$meses[ ] = '';
	$meses[ ] = gT( 'janeiro' );
	$meses[ ] = gT( 'fevereiro' );
	$meses[ ] = gT( 'março' );
	$meses[ ] = gT( 'abril' );
	$meses[ ] = gT( 'maio' );
	$meses[ ] = gT( 'junho' );
	$meses[ ] = gT( 'julho' );
	$meses[ ] = gT( 'agosto' );
	$meses[ ] = gT( 'setembro' );
	$meses[ ] = gT( 'outubro' );
	$meses[ ] = gT( 'novembro' );
	$meses[ ] = gT( 'dezembro' );

	$tHoje = strtotime( $hoje );
	$dia   = ucfirst( $sem[ date( "w", $tHoje ) ] ) . ", " . date( "d", $tHoje ) . gT( " de " ) . $meses[ date( "n", $tHoje ) ] . gT( " de " ) . date( "Y", $tHoje );
	return ( $dia );
}

function gDate( $t_parameter )
{
	global $gDevice;
	$fmt = strtolower( gVar( "global.dateformat" ) );
	if ( gVar( "global.datenull" ) == "" )
		gVar( "global.datenull", "0000-00-00" );
	if ( ( $t_parameter == gVar( "global.datenull" ) ) || ( $t_parameter == "" ) )
		$t_parameter = "";
	else
	{
		//		$t_parameter=strftime($fmt,strtotime($t_parameter)); // Apresentou problemas com datas ateriores 1970
		$sep = "/";
		if ( strpos( $fmt, "-" ) > 0 )
			$sep = "-";
		$t_p  = explode( "-", $t_parameter );
		$t_f  = explode( $sep, $fmt );
		$dia  = intval( $t_p[ 2 ] );
		$mes  = intval( $t_p[ 1 ] );
		$ano  = intval( $t_p[ 0 ] );
		$data = "";
		for ( $t = 0; $t < 3; $t++ )
		{
			if ( substr( $t_f[ $t ], 0, 1 ) == "d" )
				$data .= sprintf( "%0" . strlen( $t_f[ $t ] ) . "s", $dia );
			if ( substr( $t_f[ $t ], 0, 1 ) == "m" )
				$data .= sprintf( "%0" . strlen( $t_f[ $t ] ) . "s", $mes );
			if ( substr( $t_f[ $t ], 0, 1 ) == "y" )
				$data .= right( sprintf( "%0" . strlen( $t_f[ $t ] ) . "s", $ano ), strlen( $t_f[ $t ] ) );
			$data .= $sep;
		}
		$t_parameter = substr( $data, 0, strlen( $data ) - 1 );
	}
	if ( $gDevice == "plan" )
	{
		$t_parameter = str_replace( "-", "/", $t_parameter );
	}
	return ( $t_parameter );
}

function gDateTime( $t_parameter )
{
	global $gDevice;
	if ( $gDevice <> "plan" )
	{
		if ( gVar( "global.datenull" ) == "" )
			gVar( "global.datenull", "0000-00-00" );
		if ( ( substr( $t_parameter, 0, strlen( gVar( "global.datenull" ) ) ) == gVar( "global.datenull" ) ) || ( $t_parameter == "" ) )
			$sai = "";
		else
		{
			if ( strpos( $t_parameter, "T" ) === false )
				$data = explode( " ", $t_parameter );
			else
				$data = explode( "T", $t_parameter );
			$time = $data[ 1 ];
			$sai  = gDate( $t_parameter ) . " " . $time;
		}
	}
	else
		$sai = $t_parameter;
	return ( $sai );
}

function gDBDate( $t_parameter )
{
	if ( $t_parameter <> "" )
	{
		if ( strpos( $t_parameter, "T" ) !== false )
		{
			$t_parameter = substr( $t_parameter, 0, 10 );
		}
		else
		{
			$t_parameter = substr( $t_parameter, 0, 10 );
			$fmt         = strtolower( gVar( "global.dateformat" ) );
			$char        = '-';
			if ( strpos( $t_parameter, "-" ) > 0 )
				$chard = '-';
			if ( strpos( $t_parameter, "/" ) > 0 )
				$chard = '/';
			if ( strpos( $fmt, "-" ) > 0 )
				$charf = '-';
			if ( strpos( $fmt, "/" ) > 0 )
				$charf = '/';
			$data = explode( $chard, $t_parameter );
			$form = explode( $charf, $fmt );
			for ( $t = 0; $t < count( $data ); $t++ )
			{
				$dat = $form[ $t ];
				if ( $dat[ 0 ] == "d" )
					$dia = $data[ $t ];
				if ( $dat[ 0 ] == "m" )
					$mes = $data[ $t ];
				if ( $dat[ 0 ] == "y" )
					$ano = $data[ $t ];
			}
			if ( strlen( $ano ) < 4 )
			{
				if ( ( intval( $ano ) == 0 ) && ( intval( $mes ) == 0 ) && ( intval( $dia ) == 0 ) )
				{
					if ( ( gVar( "global.datenull" ) == "" ) || ( gVar( "global.datenull" ) == "0000-00-00" ) )
					{
						$ano = "0000";
						$mes = "00";
						$dia = "00";
					}
					else
					{
						$ano = substr( gVar( "global.datenull" ), 0, 4 );
						$mes = substr( gVar( "global.datenull" ), 5, 2 );
						$dia = substr( gVar( "global.datenull" ), 8, 2 );
					}
				}
				elseif ( $ano < 50 )
					$ano = "20" . $ano;
				else
					$ano = "19" . $ano;
			}
			$t_parameter = str_pad( $ano, 4, "0", STR_PAD_LEFT ) . "-" . str_pad( $mes, 2, "0", STR_PAD_LEFT ) . "-" . str_pad( $dia, 2, "0", STR_PAD_LEFT );
		}

	}
	else
	{
		$sai = gVar( "global.datenull" );
	}
	return ( $t_parameter );
}

function gDBDateTime( $t_parameter )
{
	$sai = $t_parameter;
	if ( strpos( $t_parameter, "T" ) === false )
	{
		if ( $t_parameter <> "" )
		{
			$data = explode( " ", $t_parameter );
			$time = $data[ 1 ];
			$sai  = gDBDate( $data[ 0 ] ) . " " . $time;
			if ( gDBDate( $data[ 0 ] ) == gVar( "global.datenull" ) )
			{
				$sai = gDBDate( $data[ 0 ] ) . "T00:00:00";
			}
		}
		else
		{
			$sai = gVar( "global.datenull" ) . "T00:00:00";
		}
		if ( gVar( "database.url" ) == "200.254.228.6" )
		{
			$sai = str_replace( "T", " ", $sai );
		}
	}
	else
	{
		$sai = str_replace( "-03:00", "", $t_parameter );
	}
	$sai = substr( $sai, 0, 19 );
	return ( $sai );
}

function gDBFloat( $t_parameter )
{
	$dec = 2;
	$c1  = ',';
	$c2  = '.';
	$fmt = gVar( "global.numformat" );
	if ( ( $fmt == "0000,00" ) || ( $fmt == "0,00" ) )
	{
		$c1 = ',';
		$c2 = '';
	}
	if ( ( $fmt == "0000.00" ) || ( $fmt == "0.00" ) )
	{
		$c1 = '.';
		$c2 = '';
	}
	if ( $fmt == "0,000.00" )
	{
		$c1 = '.';
		$c2 = ',';
	}
	$t_parameter = str_replace( $c2, "", $t_parameter );
	return ( floatval( str_replace( $c1, ".", $t_parameter ) ) );
}

function gFloat( $t_parameter )
{
	global $gDevice;
	$fmt = gVar( "global.numformat" );
	$p1  = strpos( $fmt, "." );
	$p2  = strpos( $fmt, "," );
	if ( ( $p1 > 0 ) && ( $p2 > 0 ) )
	{
		if ( $p1 < $p2 )
		{
			$c1 = ',';
			$c2 = '.';
		}
		else
		{
			$c1 = '.';
			$c2 = ',';
		}
	}
	else
	{
		if ( $p1 > 0 )
		{
			$c1 = ',';
			$c2 = '';
		}
		if ( $p2 > 0 )
		{
			$c1 = '.';
			$c2 = '';
		}
	}
	$m           = explode( $c1, $fmt );
	$dec         = strlen( $m[ 1 ] );
	$t_parameter = number_format( $t_parameter, $dec, $c1, $c2 );
	if ( $gDevice == "plan" )
	{
		$t_parameter = str_replace( ".", "", $t_parameter );
		if ( $_REQUEST[ 'gExportTo' ] == "Excel" )
			$t_parameter = str_replace( ",", ".", $t_parameter );
	}
	return ( $t_parameter );
}


function gFieldRpl( )
{
	$rpl    = "";
	$rpl[ ] = array(
		 "zao",
		"zão"
	);
	$rpl[ ] = array(
		 "gao",
		"gão"
	);
	$rpl[ ] = array(
		 "hao",
		"hão"
	);
	$rpl[ ] = array(
		 "ndereco",
		"ndereço"
	);
	$rpl[ ] = array(
		 "email",
		"e-mail"
	);
	$rpl[ ] = array(
		 "cao",
		"ção"
	);
	$rpl[ ] = array(
		 "sao",
		"são"
	);
	$rpl[ ] = array(
		 "ssoes",
		"ssões"
	);
	$rpl[ ] = array(
		 "nao",
		"não"
	);
	$rpl[ ] = array(
		 "mao",
		"mão"
	);
	$rpl[ ] = array(
		 "coes",
		"ções"
	);
	$rpl[ ] = array(
		 "tao",
		"tão"
	);
	$rpl[ ] = array(
		 "toes",
		"tões"
	);
	$rpl[ ] = array(
		 "odigo",
		"ódigo"
	);
	$rpl[ ] = array(
		 "id_",
		""
	);
	$rpl[ ] = array(
		 "umero",
		"úmero"
	);
	$rpl[ ] = array(
		 "ervico",
		"erviço"
	);
	$rpl[ ] = array(
		 "Ip",
		"IP"
	);
	$rpl[ ] = array(
		 "Tcp",
		"TCP"
	);
	$rpl[ ] = array(
		 "Dns",
		"DNS"
	);
	$rpl[ ] = array(
		 "Smtp",
		"SMTP"
	);
	$rpl[ ] = array(
		 "Pop",
		"POP"
	);
	$rpl[ ] = array(
		 "cpf",
		"CPF"
	);
	$rpl[ ] = array(
		 "cnpj",
		"CNPJ"
	);
	$rpl[ ] = array(
		 "inss",
		"INSS"
	);
	$rpl[ ] = array(
		 "Iss",
		"ISS"
	);
	$rpl[ ] = array(
		 "icms",
		"ICMS"
	);
	$rpl[ ] = array(
		 "Ipi",
		"IPI"
	);
	$rpl[ ] = array(
		 "Ir",
		"IR"
	);
	$rpl[ ] = array(
		 "iof",
		"IOF"
	);
	return ( $rpl );
}

function gCpf( $cpf )
{
	$cpf = str_pad( $cpf, 11, "0", STR_PAD_LEFT );
	$tmp = substr( $cpf, 0, 3 ) . "." . substr( $cpf, 3, 3 ) . "." . substr( $cpf, 6, 3 ) . "-" . substr( $cpf, -2 );
	$cpf = $tmp;
	return ( $cpf );
}

function gCnpj( $cnpj )
{
	$cnpj = str_pad( $cnpj, 14, "0", STR_PAD_LEFT );
	$tmp  = substr( $cnpj, 0, 2 ) . "." . substr( $cnpj, 2, 3 ) . "." . substr( $cnpj, 5, 3 ) . "/" . substr( $cnpj, 8, 4 ) . "-" . substr( $cnpj, -2 );
	$cnpj = $tmp;
	return ( $cnpj );
}


function gString2Field( $parameter )
{
	$parameter = strtolower( $parameter );

	if ( gVar( "global.language" ) == "pt_BR" )
	{
		$rpl    = gFieldRpl();
		$rpl[ ] = array(
			 "a",
			"ã"
		);
		$rpl[ ] = array(
			 "a",
			"á"
		);
		$rpl[ ] = array(
			 "a",
			"à"
		);
		$rpl[ ] = array(
			 "a",
			"â"
		);
		$rpl[ ] = array(
			 "e",
			"é"
		);
		$rpl[ ] = array(
			 "e",
			"ê"
		);
		$rpl[ ] = array(
			 "e",
			"è"
		);
		$rpl[ ] = array(
			 "i",
			"í"
		);
		$rpl[ ] = array(
			 "o",
			"ó"
		);
		$rpl[ ] = array(
			 "o",
			"ô"
		);
		$rpl[ ] = array(
			 "o",
			"õ"
		);
		$rpl[ ] = array(
			 "o",
			"ò"
		);
		$rpl[ ] = array(
			 "u",
			"ú"
		);
		$rpl[ ] = array(
			 "u",
			"ü"
		);
		$rpl[ ] = array(
			 "c",
			"ç"
		);

		$rpl[ ] = array(
			 "A",
			"Ã"
		);
		$rpl[ ] = array(
			 "A",
			"Á"
		);
		$rpl[ ] = array(
			 "A",
			"À"
		);
		$rpl[ ] = array(
			 "A",
			"Â"
		);
		$rpl[ ] = array(
			 "E",
			"É"
		);
		$rpl[ ] = array(
			 "E",
			"Ê"
		);
		$rpl[ ] = array(
			 "E",
			"È"
		);
		$rpl[ ] = array(
			 "I",
			"Í"
		);
		$rpl[ ] = array(
			 "O",
			"Ó"
		);
		$rpl[ ] = array(
			 "O",
			"Ô"
		);
		$rpl[ ] = array(
			 "O",
			"Õ"
		);
		$rpl[ ] = array(
			 "O",
			"Ò"
		);
		$rpl[ ] = array(
			 "U",
			"Ú"
		);
		$rpl[ ] = array(
			 "U",
			"Ü"
		);
		$rpl[ ] = array(
			 "C",
			"Ç"
		);
		$rpl[ ] = array(
			 "_",
			" "
		);

		$rpl[ ] = array(
			 "e",
			"&"
		);
		$rpl[ ] = array(
			 "",
			"ª"
		);
		$rpl[ ] = array(
			 "",
			"º"
		);
		$rpl[ ] = array(
			 "",
			"°"
		);
		$rpl[ ] = array(
			 "",
			"*"
		);
		$rpl[ ] = array(
			 "",
			"%"
		);
		$rpl[ ] = array(
			 "",
			"#"
		);
		$rpl[ ] = array(
			 "",
			"\""
		);
		$rpl[ ] = array(
			 "",
			"\\"
		);
		$rpl[ ] = array(
			 "",
			"\/"
		);
		$rpl[ ] = array(
			 "",
			"@"
		);
		for ( $t = 0; $t < count( $rpl ); $t++ )
		{
			$parameter = str_replace( $rpl[ $t ][ 1 ], $rpl[ $t ][ 0 ], $parameter );
		}
	}
	$strlength    = strlen( $parameter );
	$retparameter = "";
	for ( $i = 0; $i < $strlength; $i++ )
	{
		if ( ( ord( $parameter[ $i ] ) >= 48 && ord( $parameter[ $i ] ) <= 57 ) || ( ord( $parameter[ $i ] ) >= 65 && ord( $parameter[ $i ] ) <= 90 ) || ( ord( $parameter[ $i ] ) >= 97 && ord( $parameter[ $i ] ) <= 122 ) )
		{
			$retparameter .= $parameter[ $i ];
		}
	}
	$parameter = $retparameter;

	return ( $parameter );
}



function gField2String( $parameter )
{
	$parameter = ucfirst( str_replace( "_", " ", $parameter ) );
	if ( gVar( "global.language" ) == "pt_BR" )
	{
		$rpl = gFieldRpl();
		for ( $t = 0; $t < count( $rpl ); $t++ )
		{
			$parameter = str_replace( $rpl[ $t ][ 0 ], $rpl[ $t ][ 1 ], $parameter );
		}
	}
	return ( $parameter );
}

function gShortName( $name, $max = 2 )
{
	$sai = '';
	$n   = explode( " ", $name );
	for ( $a = 0; $a < $max; $a++ )
	{
		if ( ( strtolower( $n[ $a ] ) == "de" ) || ( strtolower( $n[ $a ] ) == "da" ) || ( strtolower( $n[ $a ] ) == "do" ) || ( strtolower( $n[ $a ] ) == "das" ) || ( strtolower( $n[ $a ] ) == "dos" ) )
		{
			$max++;
		}

		$sai .= $n[ $a ] . " ";
	}
	return ( trim( $sai ) );
}

function gHtml2str( $texto )
{
	$t     = trim( $texto );
	$t     = str_replace( '&nbsp;', ' ', $t );
	$t     = str_replace( 'N<sup>o</sup>', 'Nê', $t );
	$trans = get_html_translation_table( HTML_ENTITIES );
	$trans = array_flip( $trans );
	$t     = strtr( $t, $trans );
	$t     = trim( strip_tags( $t ) );
	return ( $t );
}

function gFloat2String( $valor = 0 )
{
	$singular = array(
		 "centavo",
		"real",
		"mil",
		"milhão",
		"bilhão",
		"trilhão",
		"quatrilhão"
	);
	$plural   = array(
		 "centavos",
		"reais",
		"mil",
		"milhões",
		"bilhões",
		"trilhões",
		"quatrilhÃµes"
	);

	$c   = array(
		 "",
		"cem",
		"duzentos",
		"trezentos",
		"quatrocentos",
		"quinhentos",
		"seiscentos",
		"setecentos",
		"oitocentos",
		"novecentos"
	);
	$d   = array(
		 "",
		"dez",
		"vinte",
		"trinta",
		"quarenta",
		"cinquenta",
		"sessenta",
		"setenta",
		"oitenta",
		"noventa"
	);
	$d10 = array(
		 "dez",
		"onze",
		"doze",
		"treze",
		"quatorze",
		"quinze",
		"dezesseis",
		"dezesete",
		"dezoito",
		"dezenove"
	);
	$u   = array(
		 "",
		"um",
		"dois",
		"três",
		"quatro",
		"cinco",
		"seis",
		"sete",
		"oito",
		"nove"
	);

	$z = 0;

	$valor   = number_format( $valor, 2, ".", "." );
	$inteiro = explode( ".", $valor );
	for ( $i = 0; $i < count( $inteiro ); $i++ )
		for ( $ii = strlen( $inteiro[ $i ] ); $ii < 3; $ii++ )
			$inteiro[ $i ] = "0" . $inteiro[ $i ];

	// $fim identifica onde que deve se dar junêêo de centenas por "e" ou por "," ;)
	$fim = count( $inteiro ) - ( $inteiro[ count( $inteiro ) - 1 ] > 0 ? 1 : 2 );
	for ( $i = 0; $i < count( $inteiro ); $i++ )
	{
		$valor = $inteiro[ $i ];
		$rc    = ( ( $valor > 100 ) && ( $valor < 200 ) ) ? "cento" : $c[ $valor[ 0 ] ];
		$rd    = ( $valor[ 1 ] < 2 ) ? "" : $d[ $valor[ 1 ] ];
		$ru    = ( $valor > 0 ) ? ( ( $valor[ 1 ] == 1 ) ? $d10[ $valor[ 2 ] ] : $u[ $valor[ 2 ] ] ) : "";

		$r = $rc . ( ( $rc && ( $rd || $ru ) ) ? " e " : "" ) . $rd . ( ( $rd && $ru ) ? " e " : "" ) . $ru;
		$t = count( $inteiro ) - 1 - $i;
		$r .= $r ? " " . ( $valor > 1 ? $plural[ $t ] : $singular[ $t ] ) : "";
		if ( $valor == "000" )
			$z++;
		elseif ( $z > 0 )
			$z--;
		if ( ( $t == 1 ) && ( $z > 0 ) && ( $inteiro[ 0 ] > 0 ) )
			$r .= ( ( $z > 1 ) ? " de " : "" ) . $plural[ $t ];
		if ( $r )
			$rt = $rt . ( ( ( $i > 0 ) && ( $i <= $fim ) && ( $inteiro[ 0 ] > 0 ) && ( $z < 1 ) ) ? ( ( $i < $fim ) ? ", " : " e " ) : " " ) . $r;
	}
	$rt = ucfirst( trim( $rt ) );
	return ( $rt ? $rt : "zero" );
}

function gPasswordSugest( )
{
	$cons = array(
		 "lh",
		"nh",
		"cr",
		"tr",
		"pr",
		"b",
		"c",
		"d",
		"f",
		"g",
		"h",
		"j",
		"k",
		"l",
		"m",
		"m",
		"p",
		"qu",
		"r",
		"s",
		"t",
		"v",
		"x",
		"z"
	);
	$vog  = array(
		 "a",
		"e",
		"i",
		"o",
		"u"
	);
	$sai  = "";
	$sai  = $cons[ rand( 0, 23 ) ] . $vog[ rand( 0, 4 ) ] . rand( 0, 99 ) . $cons[ rand( 0, 23 ) ] . $vog[ rand( 0, 4 ) ] . rand( 0, 99 );
	return ( $sai );
}

function gTrueFalse( $v )
{

	if ( $v == 1 )
		$sai = gLng( "message.yes.short" );
	else
		$sai = gLng( "message.no.short" );
	return ( $sai );
}

//-----------------------------------------
function gD( )
{
	//-----------------------------------------
	// Mostra o conteêdo de uma variêvel na janela cliente
	for ( $t = 0; $t < func_num_args(); $t++ )
	{
		echo "<pre>";
		print_r( func_get_args( $t ) );
		echo "</pre>";
	}
}


function revertCleanField( $valor )
{
	$sai = str_replace( "‘", "'", $valor );
	$sai = str_replace( "“", "\"", $sai );
	return ( $sai );
}

function gCleanField( $valor )
{
	if ( is_array( $valor ) )
	{
		foreach ( $valor as $key => $val )
		{
			$valor[ $key ] = gCleanField( $val );
		}
		$sai = $valor;
	}
	else
	{
		$sai = str_replace( "'", "‘", $valor );
		$sai = str_replace( "\"", "“", $sai );
		$sai = trim( $sai );
		//$sai=str_replace("\"","*",$sai);
		//$sai=str_replace('\\','',$sai);
		//$sai=str_replace("#"," ",$sai);
		//$sai=str_replace("%","°/o",$sai);
		//$sai=trim(strip_tags($sai)); // evita javascript/css injection
	}
	return ( $sai );
}



function gOcr( $img, $lang = "en" )
{
	$tmp = tempnam( '/tmp', 'ocr' );
	$par = "-l eng";
	switch ( $lang )
	{
		case "pt":
			$par = '-l por';
			break;
		case "it":
			$par = '-l ita';
			break;
		case "fr":
			$par = '-l fra';
			break;
		case "de":
			$par = '-l deu';
			break;
		case "sp":
			$par = '-l spa';
			break;
		case "ja":
			$par = '-l jpn';
			break;
		case "zh":
			$par = '-l chi';
			break;
	}
	if ( $lang == 'pt' )
		$cmd = "/usr/local/bin/tesseract $img $tmp $par 2>/tmp/tess.log";
	gLog( "==> cmd $cmd" );
	$sai = shell_exec( $cmd );
	$txt = file_get_contents( "$tmp.txt" );
	unlink( $tmp . ".txt" );
	$txt = str_replace( '"', "“", $txt );
	$txt = str_replace( '~', " ", $txt );
	$txt = str_replace( "\n\r", "\n", $txt );
	$txt = str_replace( "\r\n", "\n", $txt );
	$txt = str_replace( "\n\n", "\n", $txt );
	$txt = trim( $txt );
	return ( $txt );
}

/** Redimensiona imagem onde $arquivo = $_FILES["arquivo"]
 *
 */
function gImageResize( $arquivo, $maxLargura = 150, $maxAltura = 150, $deleteFile = true )
{
	$sai = '';
	$arq = $arquivo[ 'tmp_name' ];
	if ( file_exists( $arq ) )
	{
		$imgtipo = $arquivo[ 'type' ];
		list( $largura, $altura ) = getimagesize( $arq );

		if ( ( ( $altura > $maxAltura ) || ( $largura > $maxLargura ) ) )
		{
			if ( $largura > $altura )
			{
				$nAltura  = intval( ( $maxLargura * $altura ) / $largura );
				$nLargura = $maxLargura;
			}
			else
			{
				$nLargura = intval( ( $maxAltura * $largura ) / $altura );
				$nAltura  = $maxAltura;
			}
			$thumb = imagecreatetruecolor( $nLargura, $nAltura );
			if ( stripos( $imgtipo, "jp" ) === false )
			{
				$source = ImageCreateFromPNG( $arq );
				ImageAlphaBlending( $thumb, false );
				imagecopyresampled( $thumb, $source, 0, 0, 0, 0, $nLargura, $nAltura, $largura, $altura );
				ImageSaveAlpha( $thumb, true );
				imagepng( $thumb, $arq );
			}
			else
			{
				$source = imagecreatefromjpeg( $arq );
				imagecopyresized( $thumb, $source, 0, 0, 0, 0, $nLargura, $nAltura, $largura, $altura );
				imagejpeg( $thumb, $arq );
			}
			$altura  = $nAltura;
			$largura = $nLargura;
		}
		$pont            = fopen( $arq, "rb" );
		$imgdados        = base64_encode( fread( $pont, filesize( $arq ) ) );
		$sai[ 'file' ]   = $imgdados;
		$sai[ 'type' ]   = $imgtipo;
		$sai[ 'width' ]  = $largura;
		$sai[ 'height' ] = $altura;

		//Apagando a imagem da pasta
		if ( $deleteFile )
			unlink( $arq );
	}
	else
	{
		$sai[ 'file' ]   = "/9j/4AAQSkZJRgABAQAAAQABAAD//gA8Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gMTAwCv/bAEMAAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAf/bAEMBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAf/AABEIAFoAWgMBIgACEQEDEQH/xAAfAAABBQEBAQEBAQAAAAAAAAAAAQIDBAUGBwgJCgv/xAC1EAACAQMDAgQDBQUEBAAAAX0BAgMABBEFEiExQQYTUWEHInEUMoGRoQgjQrHBFVLR8CQzYnKCCQoWFxgZGiUmJygpKjQ1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4eLj5OXm5+jp6vHy8/T19vf4+fr/xAAfAQADAQEBAQEBAQEBAAAAAAAAAQIDBAUGBwgJCgv/xAC1EQACAQIEBAMEBwUEBAABAncAAQIDEQQFITEGEkFRB2FxEyIygQgUQpGhscEJIzNS8BVictEKFiQ04SXxFxgZGiYnKCkqNTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqCg4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2dri4+Tl5ufo6ery8/T19vf4+fr/2gAMAwEAAhEDEQA/AP7+KKKKACiiigAoorhV+JHw8kvJtOTx34MfULeURzWCeKNDN9DNj5luLQX4kVskHBUc9ORwAd1RUaukiq6MGVxw6Hg+nTPHUe3Q1JQAUUUUAFFFFABRRRQAVGzpGrO7BVQcu54Hr1xx0Hv0FSVynjiZoPBfiy5jDvJB4Z1+aMJ1LLpd2Rj1K8H1GM+uAD8JP2zP26PF3xD13xF8L/hfqFz4c+Hek31/o2q6xp8tzBrfjW4sybS6VbsBRZaECxxYld2rDG4tgY/NHzJlkkm86Tzpf4xL+/H1qCOR5o0mmffNJ+9lk/6ePp09xin10bHB/E/prZ/fv/Vj9K/2Lv24vEHwy1jRfhl8UdWm1b4Zand21lpWu6ldeffeA7q8AwftZB+2+FhwdW0/H/ErXOqaRujeRH/f+GeG6hiuraaK4guI1lhnglEsM0RUFZYWUsrK24dCRjAy3Gf40m+ZXH1GfpjP9a/q1/ZSubu8/Zw+C9zqLStezfD7w+05nl86YkWgALMOScBR/ugdAAKzn0+f6HRQqXVr/wBef3d+l+p9DUUUVmbhRRRQAUUUUAFeEftM+M77wB8Avi14v0oRHUtD8D69cWImH7kXBtjZqSOgINzx6EcdK93qtNFDcxvFMkc0Mo8uWKSITRSgjoQeDj3zgj1ANAH8Zq/dTL78D/W+nvz+Xpn3p9fR/wC1r8LpvhF+0B8QfC3kmPSr3U5/FGgueIZdH8Sf6ZaA84yL/wC16ceTzx2r5wroPPGOu4OmP8nn19c+1f1P/sf+LX8a/s4fCzXH0qPR3j8N2umfZLfb5LDRybA3NsMZKX32bfy3Unp3/mE8J+Hbnxd4q8N+FbN/JufEmvaNo0Un/PH7ZdfY/tXHXrj8+/X+tT4YfD7RfhX4A8J/Dvw8J30jwno1to1pLcbftFz9lH+k3dy3d766LysMHAfAZl5rOp0+f6G+H2+X6RPQ6KKKzOkKKKKACiiigApCAcZ7dKytS1Ow0exutV1W/s9N0+wtprq/vtRureysbSBRlri7u7uQJaWgKHLk7Vxk5C4P43/tW/8ABSDzP7R8A/s83ny4uLDWfidJHlVOCLu18I2hHG0ZX/hIb8KGyx0hRkaoAznPkPBv+Cn+oC6/aPtrP/oF+BNBi+n2xr28yf8A9Qr85q1dc17W/E2pTaz4k1jVNf1W4/1uqaxf3V9fTcj/AJe70e/+Gayq6ErJLsrHLV6/4X+p6f8ABfWdK8P/ABe+Gmt63cx2ej6X438P3+qXlxxBZW5uv+Pq77Zx/U1/Wlpmq6brdlb6lpGoWeq6fdx+ba6jp11b3tjNFjIMd1akxkYPGCQDkdc1/G9XrXwu+OvxZ+DGqR6r8O/Gur6K/liOXSpZRf6FeQKdy2t3pN7/AMS/IIBHcEZ4NRPZPz/r8iqdRw/P5/ej+t0ADoKWvxx/Z9/4KfWmoTWnhv8AaC0230uaSTyYfH/h22YaUPm5PiHQRl7Lgn/TtO4JCj+yU5J/W7w/4i0LxXo9l4g8NaxYa/oupwiaw1XSrq3vbG7gOfmtbu0Yq4zzkEkEdAcEZHRTnz7f1+ffodBRRRQaBXy5+0Z+1X8N/wBm/RI5vE89xrXizVI7lvDvgnSJf+JtqZQspu7ycgx6JoytjdqV+MFtyaXHqLR7F9e+KPxB0f4V+APFvxD14j+yvCWjahq8saFTPeT2lsxtbK2AHN5qF4VsUB5JfoBur+UT4l/EbxP8WfHHiHx94vv5LzWtf1C4upfMlM1vpunj/j00qzA4+w6dYj+z6uCTvf8Ar+rGVSr7Ps/v/T5HsHx9/aw+Ln7Qt86+LNYGleE47lZNL8CaBNdWXh60xn7Fc3jHLa3fAkkahqROMk6R/ZeTn5noorU5AooooAKKKKACvevgZ+0r8V/2fNXF/wCA9ekk0eea2m1nwhq/2q98K6vjvd2nH2K+PQ6hp39l6oRkHIJFeC0UAf1PfsyftKeFf2lPAx8SaRbHRdf0u5GneLPCtzdpd3uiagSTb3NtcDAvtF1EBpdL1BERXGRgba+mq/kT+E/xg8ffBPxXbeMvh5rUmkapH9mjv7aX9/pevWAIK6XqtqObyyUgFWBBUgEYNftNo/8AwVL+C8uk6XLrnhvxBZ63Lp1jJrFpaxRT2trqj20Tahb20xQmaCC7M0UMpJMkaq5OTWTg+mv3fqzphU02/Hb/AIH3dTj/APgqj8YE0rwl4Q+CelXAF/4suz4s8URRSbfJ8P6NdBdJtbsZ+7qWuA6ghzynhu9yeK/D2vr/APb0ubm6/aw+MRubie4NlceHrayM8sk32S3/AOEMsP3Fr5jN9nh+Zv3UWxOT8vJr5AqobP1/RGc/4i9f/bmFFFFWZBRRRQAUUUUAFFFFABRRRQB//9k=";
		$sai[ 'type' ]   = "image/jpeg";
		$sai[ 'width' ]  = 150;
		$sai[ 'height' ] = 150;
	}
	return ( $sai );
}

/**
 ** comesafter ($s1, $s2)
 **
 ** Returns 1 if $s1 comes after $s2 alphabetically, 0 if not.
 **/

function comesafter( $s1, $s2 )
{
	/**
	 ** We don't want to overstep the bounds of one of the strings and segfault,
	 ** so let's see which one is shorter.
	 **/

	$order = 1;

	if ( strlen( $s1 ) > strlen( $s2 ) )
	{
		$temp  = $s1;
		$s1    = $s2;
		$s2    = $temp;
		$order = 0;
	}

	for ( $index = 0; $index < strlen( $s1 ); $index++ )
	{
		/**
		 ** $s1 comes after $s2
		 **/

		if ( $s1[ $index ] > $s2[ $index ] )
			return ( $order );

		/**
		 ** $s1 comes before $s2
		 **/

		if ( $s1[ $index ] < $s2[ $index ] )
			return ( 1 - $order );
	}

	/**
	 ** Special case in which $s1 is a substring of $s2
	 **/

	return ( $order );
}

/**
 ** asortbyindex ($sortarray, $index)
 **
 ** Sort a multi-dimensional array by a second-degree index. For instance, the 0th index
 ** of the Ith member of both the group and user arrays is a string identifier. In the
 ** case of a user array this is the username; with the group array it is the group name.
 ** asortby
 **/

function gSortArray( $sortarray, $index )
{
	$lastindex = count( $sortarray ) - 1;
	for ( $subindex = 0; $subindex < $lastindex; $subindex++ )
	{
		$lastiteration = $lastindex - $subindex;
		for ( $iteration = 0; $iteration < $lastiteration; $iteration++ )
		{
			$nextchar = 0;
			if ( comesafter( $sortarray[ $iteration ][ $index ], $sortarray[ $iteration + 1 ][ $index ] ) )
			{
				$sortarray[ $iteration ]     = $sortarray[ $iteration + 1 ];
				$sortarray[ $iteration + 1 ] = $temp;
			}
		}
	}
	return ( $sortarray );
}

function gDateString( $date = '' )
{
	$meses[ ] = '';
	$meses[ ] = 'janeiro';
	$meses[ ] = 'fevereiro';
	$meses[ ] = 'março';
	$meses[ ] = 'abril';
	$meses[ ] = 'maio';
	$meses[ ] = 'junho';
	$meses[ ] = 'julho';
	$meses[ ] = 'agosto';
	$meses[ ] = 'setembro';
	$meses[ ] = 'outubro';
	$meses[ ] = 'novembro';
	$meses[ ] = 'dezembro';
	if ( $date == '' )
		$date = date( "Y-m-d" );
	$mes = $meses[ intval( substr( $date, 5, 2 ) ) ];
	$sai = substr( $date, 8, 2 ) . " de " . $mes . " de " . substr( $date, 0, 4 );
	return ( $sai );
}

function gReplaceMacros( $texto, $macros = "", $macroSeparators = "{}" )
{
	if ( is_array( $macros ) )
	{
		foreach ( $macros as $key => $value )
		{
			$texto = str_ireplace( substr( $macroSeparators, 0, 1 ) . $key . substr( $macroSeparators, -1 ), $value, $texto );
		}
	}
	return ( $texto );
}

/** gWiki - transforma uma string com codigo Wiki em HTML
Codigos Wiki:

__ = negrito
'' = italico
#  = marcador

*/
function gWiki( $texto, $macros = "", $bd = "" )
{
	global $gId;
	$texto = nl2br( $texto );
	$texto = str_replace( "(__", "(<b>", $texto );
	$texto = str_replace( " __", " <b>", $texto );
	$texto = str_replace( "__ ", "</b> ", $texto );
	$texto = str_replace( "__,", "</b>,", $texto );
	$texto = str_replace( "__.", "</b>.", $texto );
	$texto = str_replace( "__;", "</b>;", $texto );
	$texto = str_replace( "__-", "</b>-", $texto );
	$texto = str_replace( "__)", "</b>)", $texto );
	$texto = str_replace( " \'\'", "</b>", $texto );
	$texto = str_replace( "\'\',", "</b>", $texto );
	$texto = str_replace( "\'\'.", "</b>", $texto );
	$texto = str_replace( "\'\';", "</b>", $texto );
	$texto = str_replace( "\'\'-", "</b>", $texto );
	$texto = str_replace( "\'\'", "</b>", $texto );
	$texto = str_replace( "#", "<li style='margin-left: 16px'>", $texto );
	$texto = str_replace( "@gId", $gId, $texto );
	if ( is_array( $macros ) )
	{
		foreach ( $macros as $macro )
		{
			$texto = str_replace( '@' . $macro[ 0 ] . " ", $macro[ 1 ] . " ", $texto );
			$texto = str_replace( '@' . $macro[ 0 ] . ".", $macro[ 1 ] . ".", $texto );
			$texto = str_replace( '@' . $macro[ 0 ] . ",", $macro[ 1 ] . ",", $texto );
			$texto = str_replace( '@' . $macro[ 0 ] . ";", $macro[ 1 ] . ";", $texto );
			$texto = str_replace( '@' . $macro[ 0 ] . "/", $macro[ 1 ] . "/", $texto );
			$texto = str_replace( '@' . $macro[ 0 ] . "-", $macro[ 1 ] . "-", $texto );
			$texto = str_replace( '@' . $macro[ 0 ] . "'", $macro[ 1 ] . "'", $texto );
			$texto = str_replace( '@' . $macro[ 0 ] . "<", $macro[ 1 ] . "<", $texto );
			$texto = str_replace( '@' . $macro[ 0 ] . "\n", $macro[ 1 ] . "\n", $texto );
			$texto = str_replace( '@' . $macro[ 0 ] . ")", $macro[ 1 ] . ")", $texto );
			$texto = str_replace( '@' . $macro[ 0 ] . "]", $macro[ 1 ] . "]", $texto );
		}
	}
	if ( $bd <> "" )
	{
		$campos = array_keys( $bd->fields );
		foreach ( $campos as $campo )
		{
			$texto = str_replace( '@' . $campo . " ", $bd->fields[ $campo ] . " ", $texto );
			$texto = str_replace( '@' . $campo . ".", $bd->fields[ $campo ] . ".", $texto );
			$texto = str_replace( '@' . $campo . ",", $bd->fields[ $campo ] . ",", $texto );
			$texto = str_replace( '@' . $campo . ";", $bd->fields[ $campo ] . ";", $texto );
			$texto = str_replace( '@' . $campo . "/", $bd->fields[ $campo ] . "/", $texto );
			$texto = str_replace( '@' . $campo . "-", $bd->fields[ $campo ] . "-", $texto );
			$texto = str_replace( '@' . $campo . ":", $bd->fields[ $campo ] . ":", $texto );
			$texto = str_replace( '@' . $campo . "'", $bd->fields[ $campo ] . "'", $texto );
			$texto = str_replace( '@' . $campo . "<", $bd->fields[ $campo ] . "<", $texto );
			$texto = str_replace( '@' . $campo . "\n", $bd->fields[ $campo ] . "\n", $texto );
			$texto = str_replace( '@' . $campo . ")", $bd->fields[ $campo ] . ")", $texto );
			$texto = str_replace( '@' . $campo . "]", $bd->fields[ $campo ] . "]", $texto );
		}
	}
	return ( $texto );
}

/** Efetua cálculos passando a fórmula e um array contendo variáveis
 *@author Giuliano Nascimento
 *@version 2.0
 *@param string $formula Fórmula (usa a mesma sintaxe do bc do shell, ou seja, aceita IF e outros comandos)
 *@param array $matriz Matriz contendo variáveis
 */
function gSuperCalc( $formula, $matriz )
{
	$sai = 0;
	foreach ( $matriz as $campo => $valor )
	{
		$formula = str_ireplace( $campo, $valor, $formula );
		$sai     = floatval( shell_exec( "echo '$formula' | bc" ) );
	}
	return ( $sai );
}


function gPhone( $s )
{
	$t = '';
	for ( $a = 0; $a < strlen( $s ); $a++ )
	{
		if ( ( $s[ $a ] == '0' ) || ( $s[ $a ] > 0 ) )
		{
			$t .= $s[ $a ];
		}
	}
	// 2140637560
	if ( $t[ 0 ] == '0' )
		$t = substr( $t, 1 );
	if ( strlen( $t ) == 8 )
	{
		$t = substr( $t, 0, 4 ) . ' ' . substr( $t, 4 );
	}
	if ( strlen( $t ) == 9 )
	{
		$t = substr( $t, 0, 5 ) . ' ' . substr( $t, 5 );
	}
	if ( strlen( $t ) == 10 )
	{
		$t = substr( $t, 0, 2 ) . ' ' . substr( $t, 2, 4 ) . ' ' . substr( $t, 6 );
	}
	if ( strlen( $t ) == 11 )
	{
		$t = substr( $t, 0, 2 ) . ' ' . substr( $t, 2, 5 ) . ' ' . substr( $t, 7 );
	}
	return ( $t );
}
/** Converte um valor de um campo checkbox para 0 ou 1 (ao invés de '' ou 'on')
 *@author Giuliano Nascimento
 *@version 2.0
 *@param string $c Valor do campo
 *@return integer 0 (desmarcado) ou 1 (marcado)
 */
function gDBCheck( $c )
{
	if ( ( $c === "on" ) || ( $c === "true" ) || ( intval( $c ) == 1 ) )
		$c = "1";
	else
		$c = "0";
	return $c;
}

function gCheck( $c )
{
	if ( ( $c === "on" ) || ( $c === "true" ) || ( intval( $c ) == 1 ) )
		$c = gT( "Sim" );
	else
		$c = gT( "Não" );
	return $c;
}

function javaScript( $script )
{
	if ( $script <> "" )
	{
		echo "<script language='javascript'>\n";
		echo $script;
		echo "</script>\n";
	}
}

function addJavaScript( $script )
{
	global $g__js;
	$g__js .= $script . "\n";
}

//======================================= funcoes JSON =======================================

/** Versao melhorada do json_Decode para chaves e colchetes aninhados
 * @author	Giuliano Nascimento
 * @version	1.0 12-06-2009 16:21
 * @param mixed $json String JSON
 * @param string $sep Tipo de separador ; ou , (usado no css ou javascript)
 * @param boolean $keepQuote Mantém aspas encontradas dentro da string, ou remove-as
 * @return mixed $array Array com elementos
 */
function jsonDecode( $json, $sep = ";", $keepQuote = true )
{
	//echo "\n\nJSON: $json<br>\n\n";
	if ( $json == "" )
	{
		$sai = "";
	}
	else
	{
		$css   = $json;
		$cnt   = 0;
		$arr   = "";
		$new   = "";
		$items = "";
		//echo "JSON(A): $json <br>";
		if ( strpos( $css, "items:" ) !== false )
		{
			$i = explode( "items:", $json );
			if ( substr( trim( $i[ 1 ] ), 0, 1 ) == "'" )
			{
				$items = substr( trim( $i[ 1 ] ), 1 );
				$ini   = strpos( $items, "'" );
				$css   = $i[ 0 ] . substr( $items, $ini + 2 ) . "}";
				$items = substr( $items, 0, $ini );
				$items = str_replace( "\"", "'", $items );
				//gLog("\n======: \n$css \n$items");
			}
		}
		//echo "JSON(D): $json === items: $items<br>";

		$sai = false;
		while ( !$sai )
		{
			if ( strpos( $css, "}" ) !== false )
			{
				$fim    = strpos( $css, "}" );
				$ini    = strrpos( substr( $css, 0, $fim ), "{" );
				$val    = substr( $css, $ini + 1, $fim - $ini - 1 );
				//if (!$keepQuote) $val=stripQuote($val);
				$arr[ ] = $val;
				$css    = substr( $css, 0, $ini ) . "@" . ( count( $arr ) - 1 ) . "@" . substr( $css, $fim + 1 );
			}
			else
				$sai = true;
		}
		$ttl = count( $arr );
		for ( $a = 0; $a < $ttl; $a++ )
		{
			$cssTemp = $arr[ $a ];
			$sai     = false;
			while ( !$sai )
			{
				if ( strpos( $cssTemp, "]" ) !== false )
				{
					$fim = strpos( $cssTemp, "]" );
					$ini = strrpos( substr( $cssTemp, 0, $fim ), "[" );
					$val = substr( $cssTemp, $ini + 1, $fim - $ini - 1 );
					if ( !$keepQuote )
						$val = stripQuote( $val );
					$arr[ ]  = $val;
					$cssTemp = substr( $cssTemp, 0, $ini - 1 ) . "@" . ( count( $arr ) - 1 ) . "@" . substr( $cssTemp, $fim + 1 );
				}
				else
					$sai = true;
			}
			$arr[ $a ] = $cssTemp;
		}
		$css = trim( $css );
		for ( $a = 0; $a < count( $arr ); $a++ )
		{
			$elements = str_replace( "$sep\n", $sep, trim( $arr[ $a ] ) );
			if ( strpos( $elements, "\"" ) === false )
				$ex = explode( $sep, $elements );
			else
				$ex[ ] = $elements;
			$newEl = "";
			foreach ( $ex as $el )
			{
				if ( ( strpos( $el, ":" ) === false ) && ( substr( $el, 0, 1 ) <> "@" ) )
				{
					$newEl[ ] = "{" . $el . "}";
				}
				else
				{
					$fld                        = explode( ":", $el );
					$newEl[ trim( $fld[ 0 ] ) ] = trim( $fld[ 1 ] );
				}
			}
			$new[ ] = $newEl;
		}

		$sai = false;
		while ( !$sai )
		{
			$sai = true;
			for ( $a = 0; $a < count( $new ); $a++ )
			{
				$el  = $new[ $a ];
				$el2 = "";
				$cnt = 0;
				foreach ( $el as $key => $value )
				{

					if ( ( substr( $key, 0, 1 ) == "@" ) && ( $value == "" ) )
					{
						$el2[ $cnt ] = $new[ intval( substr( $key, 1, strlen( $key ) - 2 ) ) ];
						$cnt++;
						//$new[$a]=$el;
						$sai = false;
					}
					else
					{
						if ( !$keepQuote )
							$value = stripQuote( $value );
						$el2[ $key ] = $value;
					}

				}
				$new[ $a ] = $el2;
			}
		}

		$sai = false;
		while ( !$sai )
		{
			$sai = true;
			for ( $a = 0; $a < count( $new ); $a++ )
			{
				$el = $new[ $a ];
				foreach ( $el as $key => $value )
				{
					if ( substr( $value, 0, 1 ) == "@" )
					{
						$el[ $key ] = $new[ intval( substr( $value, 1, strlen( $value ) - 2 ) ) ];
						$new[ $a ]  = $el;
						$sai        = false;
					}
				}
			}
		}
		$sai = $new[ intval( substr( $css, 1, strlen( $css ) - 2 ) ) ];
	}
	if ( $items <> "" )
		$sai[ 'items' ] = trim( $items );
	//echo "JSON: <br>$json<br><pre>";var_dump($sai);exit;
	return ( $sai );
}


/** Versao melhorada do json_encode para Array contendo Arrays
 * @author	Giuliano Nascimento
 * @version	1.0 12-06-2009 16:21
 * @param string $mtz Array de elementos
 * @param string $sep Tipo de separador ; ou , (usado no css ou javascript)
 * @param boolean $keepQuote Mantém aspas encontradas dentro da string, ou remove-as
 * @return mixed $json String JSON
 */
function jsonEncode( $mtz, $sep = ";", $keepQuote = true )
{
	$s .= "{";
	$n1 = "";
	foreach ( $mtz as $key => $value )
	{
		if ( is_array( $value ) )
		{
			//$s.=$key."[ ";
			$n3 = "";
			foreach ( $value as $itens )
			{
				$n  = "{";
				$n2 = "";
				foreach ( $itens as $item => $vitem )
				{
					if ( !$keepQuote )
					{
						if ( ( is_numeric( $vitem ) ) || ( substr( $vitem, 0, 1 ) == "'" ) || ( substr( $vitem, 0, 1 ) == '"' ) )
							$n2[ ] = $item . ": " . $vitem;
						else
							$n2[ ] = $item . ": '" . $vitem . "'";

					}
					else
					{
						$n2[ ] = $item . ": " . $vitem;
					}
				}
				$n .= implode( $sep, $n2 );
				$n .= "}";
				$n3[ ] = $n;
			}
			//$s.=implode($sep,$n3)."]";
			$n1[ ] = "$key: [" . implode( $sep, $n3 ) . "]";
		}
		else
		{
			if ( !$keepQuote )
			{
				if ( ( is_numeric( $value ) ) || ( substr( $value, 0, 1 ) == "'" ) || ( substr( $value, 0, 1 ) == '"' ) )
					$n1[ ] = $key . ": " . $value;
				else
					$n1[ ] = $key . ": '" . $value . "'";

			}
			else
			{
				$n1[ ] = $key . ": " . $value;
			}
		}
	}
	$s .= implode( $sep, $n1 );
	$s .= "}";
	return ( $s );
}


/** Junta duas strings json ou arrays resultando apenas um json
 * @author	Giuliano Nascimento
 * @version	1.0 12-06-2009 16:21
 * @param string $json1 Array de elementos ou string JSON
 * @param string $json2 Array de elementos ou string JSON
 * @param string $sepSrc Tipo de separador para origem ; ou , (usado no css ou javascript)
 * @param string $sepDst Tipo de separador para destino ; ou , (usado no css ou javascript)
 * @return mixed $json String JSON
 */
function jsonMerge( $json1, $json2, $sepSrc = ";", $sepDst = ";" )
{
	if ( !is_array( $json1 ) )
		$mtz1 = jsonDecode( $json1, $sepSrc );
	else
		$mtz1 = $json1;
	if ( !is_array( $json2 ) )
		$mtz2 = jsonDecode( $json2, $sepSrc );
	else
		$mtz2 = $json2;
	foreach ( $mtz2 as $key => $value )
	{
		$mtz1[ $key ] = $value;
	}
	return ( jsonEncode( $mtz1, $sepDst ) );

}

/** Remove um elemento de uma string ou matriz JSON
 * @author	Giuliano Nascimento
 * @version	1.0 12-06-2009 16:21
 * @param string $json Array de elementos ou string JSON
 * @param string $el Elemento a ser removido
 * @return mixed $mtz Matriz JSON
 */
function jsonRemove( $json, $el )
{
	if ( !is_array( $json ) )
		$mtz1 = jsonDecode( $json, $sepSrc );
	else
		$mtz1 = $json;
	$mtz2 = "";
	foreach ( $mtz1 as $key => $value )
	{
		if ( $key <> $el )
			$mtz2[ $key ] = $value;
	}
	return ( $mtz2 );
}



/** Versao melhorada do json_encode
 * @author	Giuliano Nascimento
 * @version	1.0 12-06-2009 16:21
 * @param mixed $mtz Descrição da variável
 * @return mixed $str String JSON
 */
function jsEncode( $mtz )
{
	if ( !is_array( $mtz ) )
	{
		$sai = $mtz;
	}
	else
	{
		$associative = count( array_diff( array_keys( $mtz ), array_keys( array_keys( $mtz ) ) ) );
		if ( $associative )
		{
			$construct = array( );
			foreach ( $mtz as $key => $value )
			{
				//$value=trim($value);
				if ( is_numeric( $key ) )
				{
					$key = "key_$key";
				}
				//$key = "'".addslashes($key)."'";
				$key = addslashes( $key );
				// Format the value:
				if ( is_bool( $value ) )
				{
					if ( $value )
						$value = "true";
					else
						$value = "false";
				}
				elseif ( is_array( $value ) )
				{
					$value = jsEncode( $value );
				}
				elseif ( $key == "validator" )
				{
					//Alterado por Bruno, para corrigir o erro de validação, o botão confirma não estava aparecendo.
					$value = "function () {return " . $value . "(Ext.getCmp(\"@__me\"))}";
				}
				elseif ( is_string( $value ) )
				{
					if ( ( $value <> "false" ) && ( $value <> "true" ) )
						$value = "'" . addslashes( $value ) . "'";
				}
				if ( trim( $key ) <> '' )
					$construct[ ] = "$key: $value";
			}
			$sai = "{ " . implode( ", ", $construct ) . " }";
		}
		else
		{
			$construct = array( );
			foreach ( $mtz as $value )
			{
				if ( is_array( $value ) )
				{
					$value = jsEncode( $value );
				}
				else if ( is_string( $value ) )
				{
					$value = "'" . addslashes( $value ) . "'";
				}
				elseif ( is_bool( $value ) )
				{
					if ( $value )
						$value = "true";
					else
						$value = "false";
				}
				if ( trim( $value ) <> '' )
					$construct[ ] = $value;
			}
			$sai = "[ " . implode( ", ", $construct ) . " ]";
		}
	}
	return ( $sai );
}

/** Versao melhorada do json_decode
 * @author	Giuliano Nascimento
 * @version	1.0 12-06-2009 16:21
 * @param string $json String JSON
 * @param boolean $format Remove \n e \t ?
 * @return mixed $mtz Descrição da variável
 */
function jsDecode( $json, $format = false )
{
	$str  = "";
	$el   = "";
	$com  = false;
	$json = str_replace( ";}", "}", $json );
	$json = autoencode( $json );
	if ( $format )
	{
		$json = str_replace( "\n", "", $json );
		$json = str_replace( "\t", "", $json );
	}
	$json = preg_replace( '/(^|,)([\\s\\t]*)([^:]*) (([\\s\\t]*)):(([\\s\\t]*))/s', '$1"$3"$4:', trim( $json ) );
	for ( $i = 0; $i < strlen( $json ); $i++ )
	{
		if ( !$com )
		{
			if ( ( $json[ $i ] == "'" ) )
			{
				$com = true;
				$str .= '"';
			}
			else
			{
				if ( $json[ $i ] == ":" )
					$str .= '"';
				if ( !( ( $json[ $i ] == "\n" ) || ( $json[ $i ] == "\t" ) ) )
					$str .= $json[ $i ];
				if ( ( ( $json[ $i ] == "{" ) || ( $json[ $i ] == "," ) ) && ( $json[ $i + 1 ] != "{" ) )
				{
					$i++;
					while ( ( ( $json[ $i ] == " " ) || ( $json[ $i ] == "\n" ) || ( $json[ $i ] == "\t" ) ) && ( $i <= strlen( $json ) ) )
					{
						$i++;
					}
					$i--;
					$str .= '"';
				}
			}
		}
		else
		{
			if ( ( $json[ $i ] == "'" ) )
			{
				$com = false;
				$str .= '"';
			}
			else
				$str .= $json[ $i ];
		}
	}
	$str = str_replace( "\r", "", $str );
	$str = str_replace( "\n", "\\n", $str );
	$str = str_replace( "\t", "\\t", $str );
	$sai = json_decode( $str, true );
	return ( $sai );
}

function jsValue( $json, $field )
{
	$array = jsDecode( $json );
	return ( $array[ $field ] );
}

function jsAdd( $json, $field, $value )
{
	if ( !is_numeric( $value ) )
		$value = "'" . $value . "'";
	$sai = "{" . $field . ": " . $value . ", " . substr( $json, 1 );
	return ( $sai );
}

function jsRemove( $json, $field )
{
	$sai   = $json;
	$array = jsDecode( $json );
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
		if ( !is_array( $json ) )
		{
			$sai = jsEncode( $sai );
			$sai = utf8_decode( $sai );
		}
	}
	return ( $sai );
}

function jsUpdate( $json, $field, $value )
{
	$sai             = $json;
	$array           = jsDecode( $json );
	$array[ $field ] = $value;
	if ( !is_array( $json ) )
	{
		$sai = jsEncode( $array );
		$sai = utf8_decode( $sai );
	}
	return ( $sai );
}

function jsMerge( $set1 = "", $set2 = "" )
{
	$sai = $set1;
	if ( trim( $set2 ) <> "" )
	{
		$arr1 = jsDecode( $set1 );
		$arr2 = jsDecode( $set2 );
		foreach ( $arr2 as $key => $value )
		{
			$arr1[ $key ] = $value;
		}
		if ( !is_array( $set1 ) )
		{
			$sai = jsEncode( $arr1 );
			$sai = autoencode( $sai );
		}
	}
	return ( $sai );
}

function stripQuote( $string )
{
	if ( substr( $string, 0, 1 ) == "'" )
	{
		$string = substr( $string, 1, strlen( $string ) - 2 );
	}
	if ( substr( $string, 0, 1 ) == '"' )
	{
		$string = substr( $string, 1, strlen( $string ) - 2 );
	}
	return ( $string );
}

function check_utf8( $str )
{
	$len = strlen( $str );
	for ( $i = 0; $i < $len; $i++ )
	{
		$c = ord( $str[ $i ] );
		if ( $c > 128 )
		{
			if ( ( $c > 247 ) )
				return false;
			elseif ( $c > 239 )
				$bytes = 4;
			elseif ( $c > 223 )
				$bytes = 3;
			elseif ( $c > 191 )
				$bytes = 2;
			else
				return false;
			if ( ( $i + $bytes ) > $len )
				return false;
			while ( $bytes > 1 )
			{
				$i++;
				$b = ord( $str[ $i ] );
				if ( $b < 128 || $b > 191 )
					return false;
				$bytes--;
			}
		}
	}
	return true;
} // end of check_utf8

function autoencode( $s ) //encode if necessary
{
	//if (!mb_check_encoding($s,'UTF-8'))
	if ( !check_utf8( $s ) )
		$s = utf8_encode( $s );
	return $s;
}

//======================================= funcoes para TAGs XHTML ===============================

/** Adiciona um parâmetro e formata uma tag HTML
 * @author	Giuliano Nascimento
 * @version	1.0 17-07-2009 10:26
 * @param string $tag Nome da TAG
 * @param string $param Nome do parametro
 * @param string $value Valor
 * @param string $default Se valor="" então assume $default
 * @return mixed $tag TAG formatada
 */
function tagAdd( $tag, $param, $value, $default = "" )
{
	if ( substr( $tag, 0, 1 ) <> "<" )
		$tag = "<$tag>";
	if ( ( $value == "" ) && ( $default <> "" ) )
		$value = $default;
	if ( $value <> "" )
	{
		if ( substr( $value, 0, 1 ) == "{" )
			$value = substr( $value, 1, strlen( $value ) - 2 );
		$tag = substr( $tag, 0, strlen( $tag ) - 1 ) . " $param=\"$value\"" . ">";
	}
	return ( $tag );
}

/** Fecha uma tag HTML
 * @author	Giuliano Nascimento
 * @version	1.0 17-07-2009 10:26
 * @param string $tag Nome da TAG
 * @return mixed $tag TAG formatada
 */
function tagClose( $tag )
{
	return ( "</$tag>" );
}

//======================================= funcoes para uso do ExtJS =======================================

function extjsVar( $name, $obj, $param )
{
	global $extjsBuffer;
	$sai = "var $name = new $obj (";
	if ( substr_count( $param, ";" ) > substr_count( $param, "," ) )
	{
		/* TODO
		 * O codigo abaixo tenta identificar se o css passado eh
		 * javascript ou css (; ou ,)
		 * mas nao funciona em codigos complexos
		 * portanto a linha a seguir foi comentada
		 */
		//$sai.=jsEncode(cssDecode($param));
		$sai .= $param;
	}
	else
	{
		$sai .= $param;
	}
	$sai .= ");\n";
	$extjsBuffer .= $sai;
	return ( $sai );
}

function extjsNew( $obj )
{
	global $extjsBuffer;
	global $extjsStack;
	$sai = "new $obj (\n";
	$extjsBuffer .= $sai;
	$extjsStack[ ] = ");";
	return ( $sai );
}

function extjsAdd( $obj )
{
	global $extjsBuffer;
	global $extjsStack;
	$sai = "$obj.add(\n";
	$extjsBuffer .= $sai;
	$extjsStack[ ] = ");";
	return ( $sai );
}

function extjsDo( $code )
{
	global $extjsBuffer;
	global $extjsStack;
	$sai = $code;
	$extjsBuffer .= $sai . "\n";
	return ( $sai );
}

function extjsEndDo( $code )
{
	global $extjsEndBuffer;
	$sai = $code;
	$extjsEndBuffer .= $sai . "\n";
	return ( $sai );
}

function extjsPop( )
{
	global $extjsStack;
	$sai = "";
	if ( is_array( $extjsStack ) )
		foreach ( $extjsStack as $pop )
			$sai .= $pop . "\n";
	return ( $sai );
}

function extjsVTypes( $vtypes )
{
	global $extjsBuffer, $extjsVTypes;
	if ( is_array( $vtypes ) )
	{
		foreach ( $vtypes as $js )
		{
			if ( empty( $extjsVTypes[ $js ] ) )
			{
				$extjsBuffer .= $js;
				$extjsVTypes[ $js ] = "1";
			}
		}
	}

}
function extjsRender( $pos = "" )
{
	global $extjsBuffer, $extjsEndBuffer, $gDevice, $gOs;
	$sai = "";
	if ( $extjsBuffer <> "" )
	{
		$sai = "<script language='javascript'>\n";

		if ( $gDevice == "web" )
		{
			$sai .= "
	Ext.override(Ext.form.TextField, {

	 convertToUpperCase: false,
	 initComponent: Ext.form.TextField.prototype.initComponent.createSequence(function(){
		  if (this.convertToUpperCase) {
				var s = this.style;
				s = (s === null || s === undefined ? '' : s + ' ;');
				this.style = s + 'textTransform: uppercase';
		  }
		  if (this.convertToLowerCase) {
				var s = this.style;
				s = (s === null || s === undefined ? '' : s + ' ;');
				this.style = s + 'textTransform: lowercase';
		  }
		  return true;
	 }),

	 originalProcessValue: Ext.form.TextField.prototype.processValue,
	 processValue: function(value){
		  var v = value;
		  if (this.convertToUpperCase) {
				var v = v.toUpperCase();
				this.setRawValue(v);
		  }
		  if (this.convertToLowerCase) {
				var v = v.toLowerCase();
				this.setRawValue(v);
		  }
		  return this.originalProcessValue.call(this, v);
	 }

	});

	";
		}
		$sai .= $extjsBuffer . extjsPop();
		//if (substr($pos,-1)<>";") $pos.=";";$sai.=$pos."\n";
		$sai .= $extjsEndBuffer;

		if ( ( $gDevice == "tablet" ) || ( $gOs == "ios" ) || ( $gOs == "android" ) )
			$sai .= "}});\n";
		else
			$sai .= "});\n";
		$sai .= "</script>\n";

		echo $sai;

	}
	return ( $sai );
}

function extjsPaste( $param = "", $type = "", $items = "" )
{
	$sep = ",\n";
	if ( substr_count( $param, ";" ) >= substr_count( $param, "," ) )
		$sep = ";";
	if ( $items <> "" )
	{
		$end = "";
		if ( substr( $param, 0, 1 ) == "{" )
			$param = substr( $param, 1, strlen( $param ) - 1 );
		if ( substr( $param, -1 ) == "}" )
		{
			$param = substr( $param, 0, strlen( $param ) - 1 );
			$end .= "}";
		}
		if ( substr( $param, -1 ) == "]" )
		{
			$param = substr( $param, 0, strlen( $param ) - 1 );
			$end .= "]";
		}
		if ( substr( $param, -1 ) == "}" )
		{
			$param = substr( $param, 0, strlen( $param ) - 1 );
			$end .= "}";
		}

		$param = "{" . $param . "$sep $type: [";
		$newi  = "";
		foreach ( $items as $i )
		{
			if ( $i <> "" )
				$newi[ ] = $i;
		}
		$item = implode( $sep, $newi );
		$param .= $item;
		$param .= "]" . $end;
	}
	return $param;
}

function extjsPasteItems( $param = "", $items = "" )
{
	return ( extjsPaste( $param, "items", $items ) );
}

function extjsPasteButtons( $param = "", $buttons = "", $align = "left" )
{
	if ( $buttons <> "" )
	{
		$param   = jsEncode( $param );
		$buttons = jsEncode( $buttons );
		if ( substr( $param, 0, 1 ) == "{" )
			$param = substr( $param, 1, strlen( $param ) - 2 );
		$param = "{buttonAlign: '$align', " . $param . ", buttons: [" . $buttons . "]}";
	}
	return $param;
	//return ($this->_paste($param,"buttons",$buttons));
}

//======================================= funcoes jQuery =======================================

function gEffect( $element, $effect = "show" )
{
	$this->gOut( "$(\"#$element\").$effect(\"slow\");" );
}

// SQL
function gSQLNow( )
{
	if ( ( substr( gVar( "database.type" ), 0, 5 ) == "mysql" ) || ( substr( gVar( "database.engine" ), 0, 5 ) == "mysql" ) )
		$sai = "NOW()";
	else
		$sai = "GETDATE()";

	return ( $sai );
}

function gSQLLimit( $query, $max = 10, $first = 0 )
{
	if ( ( substr( gVar( "database.type" ), 0, 5 ) == "mysql" ) || ( substr( gVar( "database.engine" ), 0, 5 ) == "mysql" ) )
	{
		// MySQL
		$query .= " limit $max";
		if ( $first > 0 )
			$query .= " ,$first";
	}
	else
	{
		// SQLServer
		$query = "select top $max " . substr( $query, 7 );
	}
	return ( $query );
}
function gSQLConcat( $fields )
{
	$sai = "";
	if ( ( substr( gVar( "database.type" ), 0, 5 ) == "mysql" ) || ( substr( gVar( "database.engine" ), 0, 5 ) == "mysql" ) )
	{
		// MySQL
		$sai = "CONCAT(" . implode( ",' ',", $fields ) . ") ";
	}
	else
	{
		// SQLServer
		$sai = implode( "+' '+", $fields );
	}
	return ( $sai );
}
function gSQLCase( $condition, $true, $false = "" )
{
	if ( $false == "" )
		$sai = "CASE WHEN $condition THEN $true END ";
	else
		$sai = "CASE WHEN $condition THEN $true ELSE $false END ";
	return ( $sai );
}

function gSQLDate( $campo )
{
	if ( ( substr( gVar( "database.type" ), 0, 5 ) == "mysql" ) || ( substr( gVar( "database.engine" ), 0, 5 ) == "mysql" ) )
		$sai = "DATE($campo)";
	else
		$sai = "replace(convert(CHAR(10), $campo, 102),'.','-')";
	return ( $sai );
}


function gUcwords( $s )
{
	//$s=ucfirst(ucwords(strtolower($s)));
	$s = str_replace( ".", ". ", $s );
	$s = str_replace( "  ", " ", $s );
	$s = str_replace( "  ", " ", $s );
	$s = str_replace( "  ", " ", $s );
	$s = mb_convert_case( $s, MB_CASE_TITLE, "UTF-8" );
	$s = str_replace( " Da ", " da ", $s );
	$s = str_replace( " De ", " de ", $s );
	$s = str_replace( " Do ", " do ", $s );
	$s = str_replace( " Dos ", " dos ", $s );
	$s = str_replace( "(A)", "(a)", $s );
	$s = str_replace( "(À)", "(à)", $s );
	$s = str_replace( " A ", " a ", $s );
	$s = str_replace( " E ", " e ", $s );
	$s = str_replace( " O ", " o ", $s );
	$s = str_replace( " Sa ", " SA ", $s );
	$s = str_replace( " S.a.", ' S/A', $s );
	$s = str_replace( ' s/A', ' S/A', $s );
	$s = str_replace( ' S/a', ' S/A', $s );
	$s = str_replace( ' s\/A', ' S\/A', $s );
	$s = str_replace( ' S\/a', ' S\/A', $s );
	$s = str_replace( ' S' . chr( 47 ) . 'a', ' S/A', $s );
	//$s=str_replace('Ltda','LTDA',$s);
	$s = str_ireplace( 'rj', 'RJ', $s );
	$s = str_ireplace( 'mg', 'MG', $s );
	$s = str_replace( 'Oab', 'OAB', $s );
	$s = str_replace( 'Omb', 'OMB', $s );
	$s = str_replace( 'Crea', 'CREA', $s );
	$s = str_replace( 'Cremeb', 'CREMEB', $s );
	$s = str_replace( 'Tst', 'TST', $s );
	$s = str_replace( 'Trt', 'TRT', $s );
	//$s = str_replace( 'Tre', 'TRE', $s );
	$s = str_replace( 'TjRJ', 'TJRJ', $s );
	$s = str_replace( " E ", " e ", $s );
	$s = str_replace( "Ç", "ç", $s );
	$s = str_replace( "Ã", "ã", $s );
	return ( $s );
}

function gCutWords( $string, $maxWords = 3 )
{
	$w     = explode( " ", $string );
	$cnt   = 0;
	$feito = 0;
	while ( $cnt < count( $w ) )
	{
		if ( ( $feito < $maxWords ) && ( strtoupper( $w[ $cnt ] ) <> "DE" ) && ( strtoupper( $w[ $cnt ] ) <> "DO" ) && ( strtoupper( $w[ $cnt ] ) <> "DA" ) && ( strtoupper( $w[ $cnt ] ) <> "DES" ) && ( strtoupper( $w[ $cnt ] ) <> "DOS" ) && ( strtoupper( $w[ $cnt ] ) <> "DAS" ) && ( strtoupper( $w[ $cnt ] ) <> "E" ) )
		{
			$sai[ ] = $w[ $cnt ];
			$feito++;
		}
		$cnt++;
	}
	$sai = implode( " ", $sai );
	return ( $sai );
}
function tiracentos( $t )
{
	$t = strtr( $t, utf8_decode( "áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇ" ), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcC" );
	$t = strtr( $t, ( "áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇ" ), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcC" );
	return ( $t );
}

// alinha a direita
function padl( $txt, $qtd, $char = " " )
{
	return ( str_pad( substr( $txt, 0, $qtd ), $qtd, $char, STR_PAD_LEFT ) );
}

// alinha a esquerda (complementa com espacos a direita)
function padr( $txt, $qtd, $char = " " )
{
	return ( str_pad( substr( $txt, 0, $qtd ), $qtd, $char, STR_PAD_RIGHT ) );
}

function removeRN( $string )
{
	return ( str_replace( "\r", "", str_replace( "\n", "", $string ) ) );
}
// ==================================== Compatibilidade retroativa
function gField( $parameter )
{
	return ( gField2String( $parameter ) );
}
function gFieldReverse( $txt )
{
	return gString2Field( $txt );
}
function html2str( $txt )
{
	return gHtml2str( $txt );
}
function extenso( $txt )
{
	return gFloat2String( $txt );
}
function gGeraSenha( )
{
	return gPasswordSugest();
}

function uploadedFileDetails( $parm = "", $filename = "arquivo" )
{
	$details = isset( $_FILES[ $filename ] ) ? $_FILES[ $filename ] : FALSE;
	if ( $parm <> '' )
		$details = $details[ $parm ];
	return ( $details );
}

function uploadedFile( $textFile = true )
{
	$txt = $sai = '';
	if ( !empty( $_FILES ) )
	{
		//======================= CARREGANDO ARQUIVO E MOSTRANDO FORMULARIO
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) == 'WIN' )
			$gBAR = "\\";
		else
			$gBAR = '/';

		// Prepara a variável do arquivo
		$arquivo             = isset( $_FILES[ "arquivo" ] ) ? $_FILES[ "arquivo" ] : FALSE;
		//		echo "<pre>";print_r($arquivo);echo "<br>".$_FILS['tmp_name'];echo "</pre>";
		// Tamanho máximo do arquivo (em bytes)
		$config[ "tamanho" ] = 106883000000;

		$erro = "";
		// Formulário postado... executa as ações
		if ( $arquivo[ 'tmp_name' ] <> '' )
		{

			// Verifica se o mime-type do arquivo é de imagem
			//if (!eregi("^text\/(plain|txt|edi)$", $arquivo["type"])) {
			//	$erro[] = "Arquivo em formato inválido! A imagem deve ser txt ou edi. Envie outro arquivo";
			//} else {
			// Verifica tamanho do arquivo
			if ( $arquivo[ "size" ] > $config[ "tamanho" ] )
			{
				$erro[ ] = "Arquivo em tamanho muito grande! A imagem deve ser de no máximo " . $config[ "tamanho" ] . " bytes. Envie outro arquivo";
			}
			//}
		}
		if ( !is_array( $erro ) )
		{
			//ABRE ARQUIVO
			$pDir = "";
			$arq  = $arquivo[ "tmp_name" ];
			if ( file_exists( $gDir . $arq ) )
			{
				$txt = file_get_contents( $pDir . $arq );
				if ( $textFile )
					$txt = autoencode( $txt );
				else
					$txt = base64_encode( $txt );
				//$_SESSION['arqImportado']=$txt;
			}
			else
			{
				$erro[ ] = "Arquivo não existe no servidor.";
			}
			@fclose( $pont );
			//Apagando a imagem da pasta
			@unlink( $pDir . $arq );
		}
		$sai[ 'content' ] = $txt;
		$sai[ 'name' ]    = $arquivo[ 'name' ];
		$sai[ 'type' ]    = $arquivo[ 'type' ];
		$sai[ 'size' ]    = $arquivo[ 'size' ];
		$sai[ 'errors' ]  = implode( "<br>", $erro );
	}
	return ( $sai );
}

/**
 * Se $i=0 retorna se foi informado algum arquivo no campo de "arquivo de download"
 * Se $i>0 verifica se existe algum arquivo já salvo com este Id
 * @param type $i
 * @return type
 */
function uploaded( $i = 0 )
{
	if ( $i > 0 )
	{
		$gIdApp = abs( $_SESSION[ 'gApp' ] );
		$sql    = "select id from alitem.arquivos where id_aplicativo=$gIdApp and id_associacao=$i";
		$rs     = gQuery( $sql );
		$sai    = !$rs->EOF;
	}
	else
	{
		$sai=false;
		foreach ($_FILES as $arq)
			if ($arq['tmp_name']<>'')
				$sai=true;
	}

	return ( $sai );
}

/**
 * Verifica o tipo de arquivo enviado por upload
 *
 * @param string $type Tipo de arquivo passado. Tipos suportados: image, jpg, png
 * @return type
 */
function uploadedType( $type )
{
	foreach ( $_FILES as $key => $value )
	{
		$arquivo = isset( $_FILES[ $key ] ) ? $_FILES[ $key ] : FALSE;
	}
	switch ( $type )
	{
		case "image":
			$sai = eregi( "^image\/(pjpeg|jpeg|png|gif)$", $arquivo[ "type" ] );
			break;
		case "jpg":
			$sai = eregi( "^image\/(pjpeg|jpeg)$", $arquivo[ "type" ] );
			break;
		case "png":
			$sai = eregi( "^image\/(png)$", $arquivo[ "type" ] );
			break;
		default:
			$sai = eregi( "^" . $type . "$", $arquivo[ "type" ] );
			break;
	}
	return ( $sai );
}


/**
 * uploadedSave - salva arquivo upado no disco e no BD (opcional)
 * Se id=0, não usa o banco de dados
 *
 *
 * @param type $id
 * @param type $name
 * @param type $maxSize
 * @param type $fieldName
 * @return type
 */
function uploadedSave( $id, $name = '', $maxSize = 306883000000, $fieldName = "arquivo" )
{
	global $gPathUsrFiles;

	$txt   = $sai = false;
	$idArq = 0;
	if ( !empty( $_FILES ) )
	{
		$ok = true;
		//======================= CARREGANDO ARQUIVO E MOSTRANDO FORMULARIO

		// Prepara a variável do arquivo
		//$arquivo = isset($_FILES[$fieldName]) ? $_FILES[$fieldName] : FALSE;
		// if ($fieldName=="arquivo")
		// {
		// 	foreach ( $_FILES as $key => $value )
		// 	{
		// 		$arquivo = isset( $_FILES[ $key ] ) ? $_FILES[ $key ] : FALSE;
		// 	}
		// } else
		// {

		// }
		$arquivo = isset($_FILES[$fieldName]) ? $_FILES[$fieldName] : FALSE;

		// Tamanho máximo do arquivo (em bytes)
		$config[ "tamanho" ] = $maxSize;

		$erro = "";
		// Formulário postado... executa as ações
		if ( $arquivo )
		{

			// Verifica se o mime-type do arquivo é de imagem
			//if (!eregi("^text\/(plain|txt|edi)$", $arquivo["type"])) {
			//	$erro[] = "Arquivo em formato inválido! A imagem deve ser txt ou edi. Envie outro arquivo";
			//} else {
			// Verifica tamanho do arquivo
			if ( $arquivo[ "size" ] > $config[ "tamanho" ] )
			{
				$erro[ ] = "Arquivo em tamanho muito grande! A imagem deve ser de no máximo " . $config[ "tamanho" ] . " bytes. Envie outro arquivo";
				$ok      = false;
			}
			//}
		}
		if ( !is_array( $erro ) )
		{
			//ABRE ARQUIVO
			$gIdApp = abs( $_SESSION[ 'gApp' ] );
			$pDir   = "";
			$arq    = $arquivo[ "tmp_name" ];
			$base   = $_SERVER[ 'DOCUMENT_ROOT' ] . "/alitem/files/" . str_pad( $_SESSION[ 'usrIdd' ], 9, "0", STR_PAD_LEFT );
			$base   = str_replace( '//', '/', $base );
			if ( !is_dir( $base ) )
			{
				mkdir( $base, 0777, true );
			}
			$id   = intval( $id );
			$name = trim( gCleanField( $name ) );
			$nome = trim( $arquivo[ 'name' ] );
			if ( $name <> '' )
				$nome = $name;
			if ( $id > 0 )
			{
				$n     = explode( ".", $nome );
				$nome  = $n[ 0 ];
				$agora = date( "Y-m-d H:i:s" );
				$sql   = "insert into alitem.arquivos
						(data_inclusao,id_aplicativo,id_associacao,nome,tipo) values
						('$agora',$gIdApp,$id,'$name','" . $arquivo[ 'type' ] . "')";
				$rs    = gQuery( $sql );
				$sql   = "select id from alitem.arquivos where id_aplicativo=$gIdApp and nome='$name' and data_inclusao='$agora'";
				$rs    = gQuery( $sql );
				$idArq = intval( $rs->fields[ 'id' ] );

				$destino = $base . "/f" . str_pad( $gIdApp, 9, "0", STR_PAD_LEFT ) . "_" . str_pad( $idArq, 9, "0", STR_PAD_LEFT ) . ".bin";
			} else
			{
				$destino_dir = $gPathUsrFiles;
				mkdir( $destino_dir, 0777, true );
				$destino = $destino_dir . "/" . $nome;
				if ( dirname( $destino ) <> $destino_dir )
				{
					mkdir( dirname( $destino ), 0777, true );
				}
			}
			if ( file_exists( $gDir . $arq ) )
			{
				$ok = move_uploaded_file( $gDir . $arq, $destino );
				gLog("===> Movendo de $gDir $arq para $destino");
				if ( $id > 0 )
				{
					if ( !$ok )
					{
						$sql = "delete from alitem.arquivos where id=$idArq";
						gQuery( $sql );
					}
					else
					{
						$sql = "update alitem.arquivos set nome='$nome',caminho='$destino' where id=$idArq";
						gQuery( $sql );
					}
				}
			}
			else
			{
				$erro[ ] = "Arquivo não existe no servidor.";
			}
			@fclose( $pont );
			//Apagando a imagem da pasta
			@unlink( $pDir . $arq );
		}
		$sai[ 'id' ]     = $idArq;
		$sai[ 'name' ]   = $nome;
		$sai[ 'type' ]   = $arquivo[ 'type' ];
		$sai[ 'size' ]   = $arquivo[ 'size' ];
		$sai[ 'errors' ] = implode( "<br>", $erro );
	}

	return ( $sai );
}

function uploadedDownload( $idAssoc = 0, $id = 0 )
{
	$gIdApp = abs( $_SESSION[ 'gApp' ] );
	if ( $idAssoc == 0 )
		$sql = "select * from alitem.arquivos where id=$id and id_aplicativo=$gIdApp";
	else
		$sql = "select * from alitem.arquivos where id_associacao=$idAssoc and id_aplicativo=$gIdApp";
	$rs = gQuery( $sql );
	if ( !$rs->EOF )
	{
		if ($idAssoc>0 && $id>0)
		{
			for ($a=0; $a<$id; $a++)
				$rs->MoveNext();
		}
		$nome     = gString2Field( $rs->fields[ 'nome' ] );
		$conteudo = file_get_contents( $rs->fields[ 'caminho' ] );
		$type     = $rs->fields[ 'tipo' ];
		if ( stripos( $type, "word" ) !== false )
			$nome .= ".doc";
		if ( stripos( $type, "excel" ) !== false )
			$nome .= ".xls";
		if ( stripos( $type, "pdf" ) !== false )
			$nome .= ".pdf";
		download( $nome, $conteudo, $type );
		exit;
	}
}

function uploadedDelete( $id_associacao, $index=0 )
{
	$gIdApp = abs( $_SESSION[ 'gApp' ] );
	$sql    = "select * from 	alitem.arquivos where id_associacao=$id_associacao and id_aplicativo=$gIdApp";
	$rs     = gQuery( $sql );
	if ( !$rs->EOF )
	{
		if ($index>0)
			for ($a=0; $a<$index; $a++)
			{
				$rs->MoveNext();
			}
		unlink( $rs->fields[ 'caminho' ] );
		$sql = "delete from alitem.arquivos where id=".$rs->fields['id'];
		gQuery( $sql );
	}

}

function uploadedFilePointer( )
{
	$sai = '';
	if ( !empty( $_FILES ) )
	{
		//======================= CARREGANDO ARQUIVO E MOSTRANDO FORMULARIO
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) == 'WIN' )
			$gBAR = "\\";
		else
			$gBAR = '/';

		// Prepara a variável do arquivo
		$arquivo             = isset( $_FILES[ "arquivo" ] ) ? $_FILES[ "arquivo" ] : FALSE;
		//		echo "<pre>";print_r($arquivo);echo "<br>".$_FILS['tmp_name'];echo "</pre>";
		// Tamanho máximo do arquivo (em bytes)
		$config[ "tamanho" ] = 106883000000;
		$erro                = "";
		// Formulário postado... executa as ações
		if ( $arquivo )
		{
			// Verifica se o mime-type do arquivo é de imagem
			//if (!eregi("^text\/(plain|txt|edi)$", $arquivo["type"])) {
			//	$erro[] = "Arquivo em formato inválido! A imagem deve ser txt ou edi. Envie outro arquivo";
			//} else {
			// Verifica tamanho do arquivo
			if ( $arquivo[ "size" ] > $config[ "tamanho" ] )
			{
				$erro[ ] = "Arquivo em tamanho muito grande! A imagem deve ser de no máximo " . $config[ "tamanho" ] . " bytes. Envie outro arquivo";
			}
			//}
		}
		if ( !is_array( $erro ) )
		{
			//ABRE ARQUIVO
			$pDir = "";
			$arq  = $arquivo[ "tmp_name" ];
			if ( file_exists( $gDir . $arq ) )
			{
				$sai = $gDir . $arq;
			}
			else
			{
				$sai = false;
			}
		}
	}
	return ( $sai );
}

function download( $nome, $conteudo, $type = '' )
{
	global $g__download;
	$g__download = true;
	header( 'Content-Description: File Transfer' );
	if ( $type == '' )
		$type = "application/force-download";
	header( 'Content-Type: $type' );
	header( 'Content-Disposition: attachment; filename=' . $nome );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Pragma: public' );
	header( 'Content-Length: ' . strlen( $conteudo ) );
	ob_end_clean();
	//flush();
	echo ( $conteudo );
	exit;
}

function downloadFile( $nome, $file, $type = '' )
{
	global $g__download;
	$g__download = true;
	header( 'Content-Description: File Transfer' );
	if ( $type == '' )
		$type = "application/force-download";

	header( 'Content-Type: $type' );
	header( 'Content-Disposition: attachment; filename=' . $nome );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Pragma: public' );
	header( 'Content-Length: ' . filesize( $file ) );
	ob_end_clean();
	//flush();
	readfile( $file );
	exit;
}

function refresh( $seconds, $url )
{
	header( "refresh:$seconds;url=$url" );
}

function redirect( $page , $javascript = true)
{
	if ($javascript)
	{
		echo '<script language="JavaScript">window.location="' . $page . '";</script>';
	} else
	{
		header( "location: $page" );
		exit;
	}
}

function is_client( )
{
	return ( intval( $_SESSION[ 'usrClient' ] ) > 0 );
}

function is_developer( )
{
	return ( $_SESSION[ 'gDeveloper' ] == "on" ) && ( ( $_SESSION[ 'gApp' ] < 0 ) || ( $_REQUEST[ 'gIdApp' ] < 0 ) || ( substr( $_REQUEST[ 'g' ], 0, 2 ) == "-1" ) );
}

function run( $command )
{
	global $gPath, $gPathUsrFiles;
	$run = $gPath . "res/run/" . $command;
	gLog( "===> run: $run" );

	return ( shell_exec( "cd $gPathUsrFiles;" . $run ) );
}



function gT( $t_word, $force = false )
{

	global $gLngs, $gBASE;
	$habilitado = false;
	$metodo     = "";
	$posTxt     = "";
	// Não traduz expressões de uma letra, e começadas por número
	if ( ( ( gVar( "global.translate" ) <> "false" ) || ( $force ) ) && ( $t_word <> "" ) && ( strlen( $t_word ) > 1 ) && ( !is_numeric( $t_word[ 0 ] ) ) )
	{

		if ( strpos( $t_word, ":" ) !== false )
		{
			// Se tiver :, só traduz até ele
			$posTxt = substr( $t_word, strpos( $t_word, ":" ) );
			$t_word = substr( $t_word, 0, strpos( $t_word, ":" ) );
		}
		$t_word = trim( $t_word );
		if ( ( gVar( "global.site" ) == "Alitem" ) && ( $habilitado ) )
		{
			//if (!empty($_SESSION['gLngs']))
			//	$gLngs=$_SESSION['gLngs'];
			$usrLang = intval( $_SESSION[ 'usrLang' ] );
			$gApp    = $_SESSION[ 'gApp' ];
			$bd      = "";
			if ( gVar( "database.name" ) == "alitem_ide" )
				$bd = "alitem_all.";
			$sai = gLang( $t_word );
			if ( $sai === false )
			{
				// Insere não encontrados no banco para tradução posterior
				$t_word = gCleanField( $t_word );

				/*
				// Proteção pra evitar que a tabela cresça infinitamente
				$sql="select count(id) ttl from {$bd}traducoes where id_idiomas=$usrLang";
				$rs=gFastQuery($sql);
				if ($rs->fields['ttl']<500)
				{
				*
				*/
				// Vê primeiro se não já está cadastrado
				$sql = "select * from {$bd}traducoes where original='$t_word' and id_idiomas=$usrLang";
				$rs  = gFastQuery( $sql );
				if ( $rs->EOF )
				{
					// Já que não encontrou, insere no BD para traduzir futuramente
					$sql = "insert into {$bd}traducoes
								(id_idiomas,original) values
								($usrLang,'$t_word')";
					gFastQuery( $sql );
					$gLngs[ $t_word ]    = array(
						 $t_word
					);
					$_SESSION[ 'gLngs' ] = $gLngs;
				}
				/*
				}
				*
				*/
			}
			elseif ( $sai == "" )
			{
				$sai = $t_word;
			}
		}
		else
		{
			$sai = gLang( $t_word );
		}

		if ( $sai == "" )
			$sai = $t_word;
	}
	else
	{
		$sai = $t_word;
	}
	$sai .= $posTxt;

	return ( $sai );
}


/** Lê os dados de um arquivo
 * @author	Giuliano Nascimento
 * @version	1.0 24-07-2009 17:55
 * @param string $filename O arquivo a ser lido
 * @return mixed $sai Conteúdo do arquivo
 */
function gReadFile( $filename )
{
	$s = "";
	if ( file_exists( $filename ) )
	{
		$s = file_get_contents( $filename );
	}
	return ( $s );
}

/**Lê um arquivo .XML e converte-o em um array de dois elementos
 *sendo o 1ê = nome e 2ê = valor
 *@author Giuliano Nascimento
 *@version 2.0
 *@param string $t_filename O arquivo .XML a ser lido
 *@return array $fields Array contendo os elementos do arquivo .XML
 */
function gReadXML( $t_filename )
{

	global $gDebug;
	if ( $gDebug > 1 )
	{
		gLog( "gStart.php => gReadXML($t_filename)" );
	}
	$txt    = gReadFile( $t_filename );
	$pnt    = 1;
	$cntlev = -1;
	$cntfld = 0;
	$maxfld = 500;

	while ( $pnt < strlen( $txt ) )
	{
		$pnt = strpos( $txt, "<", $pnt );
		if ( ( !( $pnt === false ) ) && ( $pnt < strlen( $txt ) ) )
		{
			if ( substr( $txt, $pnt + 1, 1 ) <> "?" )
			{
				$pntEnd = strpos( $txt, ">", $pnt );
				if ( !( $pntEnd === false ) )
				{
					$Tag = substr( $txt, $pnt + 1, $pntEnd - $pnt - 1 );
					if ( substr( $Tag, 0, 1 ) == '/' )
					{
						if ( $cntlev > -1 )
						{
							$cntlev--;
							array_pop( $level );
						}
						else
						{
							//*** Erro: Mais fechamento de TAGs do existem aberturas
						}
					}
					else
					{
						$pntNext = strpos( $txt, "<", $pntEnd + 1 );
						if ( $pntNext > 0 )
						{
							$TagContent = trim( substr( $txt, $pntEnd + 1, $pntNext - $pntEnd - 1 ) );
							$pntClose   = strpos( $txt, ">", $pntNext + 1 );
							if ( $pntClose > 0 )
							{
								$TagClose = substr( $txt, $pntNext + 1, $pntClose - $pntNext - 1 );
								if ( $TagClose == '/' . $Tag )
								{
									$lvl = "";
									for ( $a = 1; $a <= $cntlev; $a++ )
									{
										$lvl = $lvl . $level[ $a ] . ".";
									}
									$fields[ ] = array(
										 strtolower( $lvl . $Tag ),
										$TagContent
									);
									$cntfld++;
									//===
									if ( $gDebug > 1 )
									{
										gLog( $lvl . trim( $Tag ) . " = " . trim( $TagContent ) );
									}
									$pnt = $pntClose;
									if ( $cntfld > $maxfld )
									{
										//*** Erro: Mêximo de campos atingido
										$pnt = strlen( $txt ) + 1;
									}
								}
								else
								{
									$cntlev++;
									$level[ ] = $Tag;
								}
							}
							else
							{
								$erro = true;
								//*** Erro: Na TAG de fechamento, falta um >"
							}
						}
						else
						{
							$erro = true;
							//*** Erro: Nêo existe o fechamento da TAG
						}
					}
				}
				else
				{
					$erro = true;
					//*** Erro: Iniciou a TAG mas nêo concluiu
				}
			}
			else
			{
				// TAG XML
			}
			$pnt++;
		}
		else
		{
			$pnt = strlen( $txt ) + 1;
		}
	}
	return ( $fields );
}

/**Criptografa uma string
 *@author Giuliano Nascimento
 *@version 2.0
 *@param string $t_text String a ser criptografada
 *@return string $_text String criptografada
 */
function gCrypt( $t_text )
{
	return ( $t_text );
}

/**Descriptografa uma string
 *@author Giuliano Nascimento
 *@version 2.0
 *@param string $t_text String a ser descriptografada
 *@return string $t_text String descriptografada
 */
function gDecrypt( $t_text )
{
	return ( $t_text );
}


function gSessionReplace( $t_name )
{
	//$blq=array('_SESSION','_GLOBAL','gId','gIdd','usrId','usrIdd','gIdApp','gIdApps','appDevel','gDeveloper','gFW','usrClient','usrAppId','gAPPName','gSetup');
	foreach ( $blq as $rem )
	{
		$t_name = str_ireplace( $rem, "/* comando inválido */", $t_name );
	}
	return ( $t_name );
}
/**Registra uma nova sessão
 *@author Giuliano Nascimento
 *@version 2.0
 *@param string $t_name O nome da sessão
 *@param string $t_value Valor atribuido a variavel de sessão
 *
 */
function gSessionSave( $t_name, $t_value )
{
	$t_name              = "g-$t_name";
	//@session_register($t_name);
	//$_SESSION[gSessionReplace($t_name)] = $t_value;
	$_SESSION[ $t_name ] = $t_value;
}

/**Recupera o valor de uma variêvel de sessão se ela existir, caso nêo exista, redireciona para pêgina de erro
 *@author Giuliano Nascimento
 *@version 2.0
 *@param string $t_name
 *@return mixed O valor da variêvel de sessão
 */
function gSessionLoad( $t_name )
{
	$t_name = "g-$t_name";
	//return $_SESSION[gSessionReplace($t_name)];
	return $_SESSION[ $t_name ];
}


function gReadSchema( $t_name )
{
	global $gCfg;
	global $gPathDefault;
	// * Lê Tema e Dicionêrio para a sessão atual
	$gxml    = "xml/gSchema_" . $t_name . ".xml";
	// Carrega o arquivo XML de linguagem e seta as variêveis
	$gschema = gReadXML( $gPathDefault . $gxml );
	for ( $a = 0; $a < count( $gschema ); $a++ )
	{
		$gschema[ $a ][ 1 ] = str_replace( "[", "<", $gschema[ $a ][ 1 ] );
		$gschema[ $a ][ 1 ] = str_replace( "]", ">", $gschema[ $a ][ 1 ] );
		$pnt                = strpos( $gschema[ $a ][ 1 ], "#" );
		while ( ( $pnt > 0 ) && ( $pnt < strlen( $gschema[ $a ][ 1 ] ) ) )
		{
			$pnt2 = strpos( $gschema[ $a ][ 1 ], "#", $pnt + 1 );
			if ( $pnt2 > 0 )
			{
				$tvar = substr( $gschema[ $a ][ 1 ], $pnt + 1, $pnt2 - $pnt - 1 );
				for ( $b = 0; $b < count( $gCfg ); $b++ )
				{
					if ( strtolower( $tvar ) == $gCfg[ $b ][ 0 ] )
					{
						$gschema[ $a ][ 1 ] = substr( $gschema[ $a ][ 1 ], 0, $pnt ) . $gCfg[ $b ][ 1 ] . substr( $gschema[ $a ][ 1 ], $pnt2 + 1 );
					}
				}
				$pnt = strpos( $gschema[ $a ][ 1 ], "#", $pnt2 + 1 );
			}
			else
			{
				$pnt = strlen( $gschema[ $a ][ 1 ] );
			}
		}
	}
	gSessionSave( "gschema", $gschema );
}

function gReadLanguage( $t_name )
{
}

function gRead( )
{
	//* Lê o arquivo de configuraêêes do site e armazena em uma variêvel global (ou cookie)
	global $gPathDefault;
	global $gTransFile;

	$schema = gVar( "global.schema" );
	$langua = gVar( "global.language" );

	//$gtmpFileDate=filemtime($gPathDefault . "xml/gSchema_$schema.xml");
	//$gSchemaFileDate=gAppLoad("gSchemaFileDate");
	$gtmpFileDate2 = filemtime( $gPathDefault . "xml/gDict_$langua.xml" );
	//$gLanguageFileDate=gAppLoad("gLanguageFileDate");
	/*
	if (($gSchemaFileDate<$gtmpFileDate) || (!session_is_registered("gschema")))
	{
	// Carrega o arquivo XML de linguagem e seta as variêveis
	//gReadSchema(gVar("global.schema"));
	}
	*/
	if ( ( $gLanguageFileDate < $gtmpFileDate2 ) || ( !session_is_registered( "glang" ) ) )
	{
		// Carrega o arquivo XML de linguagem e seta as variêveis
		if ( $gTransFile == "" )
			gReadLanguage( gVar( "global.language" ) );
	}
	//gAppSave("gSchemaFileDate",$gtmpFileDate);
	//gAppSave("gLanguageFileDate",$gtmpFileDate2);
}
?>
