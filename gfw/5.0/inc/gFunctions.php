:<?php /**
 *  ESTE ARQUIVO NÃO EXIGE A INCLUSÃO DO setup.php NEM DO gStart.php !!!
 *
 * @author	giuliano
 * @version	1.0 21-03-2013 10:59
 */
/*
  Para compatibilidade total com UTF8, subtitua as funções por:

  mail()		-> mb_send_mail()
  strlen()	-> mb_strlen()
  strpos()	-> mb_strpos()
  strrpos()	-> mb_strrpos()
  substr()	-> mb_substr()
  strtolower()	-> mb_strtolower()
  strtoupper()	-> mb_strtoupper()
  substr_count()	-> mb_substr_count()
  ereg()		-> mb_ereg()
  eregi()		-> mb_eregi()
  ereg_replace()	-> mb_ereg_replace()
  eregi_replace()	-> mb_eregi_replace()
  split()		-> mb_split()

 */

if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
	define('gAPP_FILE', "/gApp_");
	setlocale(LC_ALL, 'POSIX');
	define('gLogPath', "/");
} else {
	define('gAPP_FILE', "/tmp/gApp_");
	setlocale(LC_ALL, 'english');

	if (file_exists("/var/www/log")) {
		define('gLogPath', "/var/www/log/");
	} else {
		define('gLogPath', "/var/log/");
	}

}

define('NL', "\n");

if (!function_exists('gVar')) {

	if ($gLang == "en") {
		$_gVar['global.dateformat'] = "mm-dd-yy";
		$_gVar['global.numformat']  = "0,000.00";
	} else {
		$_gVar['global.dateformat'] = "dd-mm-yy";
		$_gVar['global.numformat']  = "0.000,00";
	}

	$_gVar['global.datenull'] = "0000-00-00";
	$_gVar['global.language'] = "pt_BR";
	$_gVar['global.logfile']  = "gfw.log";
	$_gVar['global.debug']    = 1;
	$_gVar['global.logcolor'] = "true";

	function gVar($par, $new = "")
	{
		global $gLang, $_gVar;

		if ($new != "") {
			$_gVar[$par]=$new;
		}

		return($_gVar[$par]);
	}
}

if (!function_exists('gLng')) {

	function gLng($par): string
	{
		$sai = '';
		return (match ($par) {
			"message.yes.short" => "Sim",
			"message.no.short" => "Não",
			default => $sai,
		});
	}

}

if (!function_exists('cssEncode')) {

	/**
	 * Recebe um array e retorna uma string formatada CSS
	 * @author	giuliano
	 * @version	1.0 17-07-2009 10:08
	 * @param array $mtz Array com elementos do CSS
	 * @param array $default Array com valores padrao
	 * @return string $sai
	 */
	function cssEncode($mtz, $default = ""): void
	{
		$sai = "";
		$new = cssMerge($mtz, $default);
		if ($new != "") {
			foreach ($new as $key => $value) {
				if (($key != "") && ($value != "")) {

					if (is_array($value)) {
						$value = json_encode($value);
						$value = str_replace('"',"",$value);
						$value = substr($value,1,strlen($value)-2);
						$el = $key . ": " . $value . "; ";
					} else {
						$el = $key . ": " . $value . "; ";
					}

					$sai .= $el;
				}
			}

			if ($sai !== "") {
				$sai = "{" . substr(trim($sai), 0, strlen($sai) - 2) . "}";
			}
		}
	}
}

if (!function_exists('cssDecode')) {

	/**
	 * Transforma uma string CSS em um array
	 * @author	giuliano
	 * @version	1.0 12-06-2009 16:21
	 * @param string $css String CSS
	 * @param boolean $format Remove \n e \t ?
	 * @return mixed $mtz Descrição da variável
	 */
	function cssDecode($css)
	{
		$sai = "";
		$css = html_entity_decode($css, ENT_NOQUOTES, 'UTF-8');
		if (str_contains($css, "[")) {
			$b = strpos($css, "[") + 1;
			for ($a = $b; $a < strlen($css); $a++) {

				if ($css[$a] === "{") {
					$css[$a] = "^";
				}

				if ($css[$a] === "}") {
					$css[$a] = "~";
				}

				if ($css[$a] === "]") {
					break;
				}

			}
		}

		$items = "";
		if (str_contains($css, "items:")) {
			$i = explode("items:", $css);
			if (str_starts_with(trim($i[1]), "'")) {
				$items = substr(trim($i[1]), 1);
				$ini   = strpos($items, "'");
				$fim   = strrpos($items, "'");
				$css   = $i[0] . substr($items, $ini + 2) . "}";
				$items = substr($items, 0, $ini);
				$items = str_replace("\"", "'", $items);
			} elseif (strtolower(substr(trim($i[1]), 0, 6)) === "select") {
				$items = str_replace("}", "", trim($i[1]));
				if (str_contains($items, ";")) {
					$items = substr($items, 0, strpos($items, ";"));
				}
			}
		}
		$css = str_replace("{", "", $css);
		$css = str_replace("}", "", $css);
		$array = explode(";", $css);

		foreach ($array as $value) {
			$value = trim($value);
			$key   = substr($value, 0, strpos($value, ":"));
			$value = trim(str_replace("'", "", substr($value, strpos($value, ":") + 1)));
			$new   = [$key, $value];
			if (trim($new[0]) !== "") {
				$val = trim($new[1]);
				$val = str_replace("`", "'", $val);
				$val = str_replace("^", "{", $val);
				$val = str_replace("~", "}", $val);
				if (substr($val, 0, 1) == "[" && str_contains($val, "|")) {
					$val = explode("|", substr($val, 1, strlen($val - 3)));
				}

				$sai[trim($new[0])] = $val;
			}
		}

		if ($items != "") {
			$sai['items'] = trim($items);
		}

		foreach ($sai as $key => $item) {
			if (str_starts_with($item, "--(encode)")) {
				$sai[$key] = base64_decode(substr($item, 10));
			}
		}

		return ($sai);
	}
}


if (!function_exists('superTrim')) {
	function superTrim($txt): string
	{
		return trim(str_replace(chr(194).chr(160),'',$txt));
	}
}


/**
 * Evita SQL Injection em valor contendo Tags HTML
 * @author	giuliano
 * @param string $var Texto
 * @version	4.0 24-01-2014 10:17
 */
function gCleanHTMLContent($content, $perm = ''): string
{
	return (strip_tags(gCleanField($content), $perm));
}


/**
 * Versão compatível com UTF8 do htmlentities
 * @author	giuliano
 * @param string $var Texto
 * @version	4.0 23-12-2013 10:50
 */
function gHtmlEntities($var): string
{
	return htmlentities($var, ENT_QUOTES, 'UTF-8');
}


/**
 * Abraça um conteúdo em uma TAG HTML
 * @author	giuliano
 * @param string $tag Nome da Tag
 * @param string $content Conteúdo que ficará dentro da tag
 * @param array $parameters Array associativo de parametros pra inserir na tag
 * @version	4.0 01-12-2013 10:50
 */
function tagMe($tag, $content = "", $parameters = ""): string
{
	$sai = '';
	if ($content != "") {
		$sai .= "<$tag";
		if (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				if ($value != "") {
					$sai .= " " . $key . '="' . $value . '"';
				} else {
					$sai .= " " . $key;
				}
			}
		} elseif ($parameters != '') {
			$sai .= " " . $parameters;
		}

		$sai .= ">";
		$sai .= $content;
		$sai .= "</$tag>";
	} else {
		$sai = "<$tag />";
	}

	return($sai);
}


/**
 *
 * @global type $gTags
 * @param type $t_parameter
 * @return type
 */
function gTag($t_parameter)
{
	global $gTags;

	return $gTags[$t_parameter];
}


/**
 *
 * @param type $txt
 * @param type $style
 * @param type $arq
 */
function gLog($txt, $style = 0, $arq = ""): void
{
	global $debug,$DB;

	if (
		str_contains((string) $_SERVER["HTTP_HOST"],"localhost")
		|| str_contains((string) $_SERVER["HTTP_HOST"],"127.0.0.1")
		|| file_exists("/tmp/gLogEnabled")
	) {
		//if ($debug!==false)
		$logfile 	= gLogPath . gVar("global.logfile");
		$sqllogfile = gLogPath . gVar("global.sqllogfile");
		$loglevel 	= gVar("global.debug");

		if ($arq != "") {
			$logfile = gLogPath . $arq;
		}

		// Se o arquivo não existir, não gera log
		if ($logfile !== "") {

			$txt = trim($txt);
			$d = debug_backtrace();
			if (gVar("global.logcolor") == "true") {
				$cini = "\033[0;00;33m";
				$cpre = "\033[0;40;37m";
				$spre = "\033[0;00;37m";
				$cpos = "\033[0;00;37m";
				$lpre = "\033[0;00;36m";
				$lpos = "\033[0;00;33m";
				if (($style == LOG_ERROR)) {
					$cpre = "\033[1;00;31m";
					$spre = "\033[1;00;31m";
				}
			}

			if ($fp = fopen($logfile, "a")) {
				$d = array_reverse($d, true);
				array_pop($d);
				array_pop($d);
				foreach ($d as $dlin) {
					$deb[] = basename($dlin['file']) . ":" . $dlin['function'] . ":" . $dlin['line'];
				}

				if (is_array($deb)) {
					$deb = implode("=> ", $deb);
				}

				$faz = false;
				if ((stripos(($txt), "select") !== false)) {
					$faz = true;
				}

				if (
					(
						(stripos(($txt), "update") !== false)
						|| (stripos(($txt), "insert") !== false)
						|| (stripos(($txt), "delete") !== false)
					)
				) {
					$faz = true;
				}

				if ($faz) {
					file_put_contents($sqllogfile, date("y-m-d H:i:s") . "\t" . $txt . "\n", FILE_APPEND);
					// Banco de dados
					fwrite($fp, $cini . date("y-m-d H:i:s") . " " . $_SERVER["REMOTE_ADDR"] . "	" . $_SESSION['usrLogin'] . "(" . $_SESSION['usrId'] . ")	" . basename((string) $_SERVER["PHP_SELF"])."&g=".$_REQUEST['g']."&gPage=" . $_REQUEST['gPage'] );
					$t = explode("):", $txt);
					fwrite($fp, "\t" . $t[0] . "):");
					fwrite($fp, "\t" . $lpre . $deb . $lpos);
					fwrite($fp, "\t" . $spre . str_replace(NL, ' ', str_replace("\t", " ", $t[1])) . $cpos . NL);
				} else {
					// Outras mensagens
					$cini = "\033[0;00;35m";
					fwrite($fp, $cini . date("y-m-d H:i:s") . " " . $_SERVER["REMOTE_ADDR"] . "	" . $_SESSION['usrLogin'] . "(" . $_SESSION['usrId'] . ")	" . basename((string) $_SERVER["PHP_SELF"])."&g=".$_REQUEST['g']."gPage=" . $_REQUEST['gPage']);
					fwrite($fp, '	LOG:');
					fwrite($fp, "\t" . $lpre . $deb . $lpos);
					fwrite($fp, "\t" . $cpre . $txt . $cpos . NL);
				}

				fclose($fp);
			}

		}
	}
}


/**
 *
 * @param type $st
 * @param type $tam
 * @return type
 */
function right($st, $tam): string
{
	return (substr($st, strlen($st) - $tam));
}


/**
 *
 * @param type $data
 * @return type
 */
function gDateOk($data): bool
{
	// formato brasileiro
	$d = explode('-', $data);
	return checkdate($d[1], $d[0], "20" . $d[2]);
}


/**
 *
 * @param type $data
 * @return type
 */
function gDBDateOk($data)
{
	// formato DB
	return gDateOk(gDBDate($data));
}


/**
 *
 * @param type $t_parameter
 * @return type
 */
function gHTMLCompact($t_parameter): array|string
{
	$gsTmp = str_replace(chr(9), "", $t_parameter);
	$gsTmp = str_replace(chr(8), "", $gsTmp);
	$gsTmp = str_replace(chr(10), "", $gsTmp);
	$gsTmp = str_replace(chr(13), "", $gsTmp);
	return str_replace("  ", "", $gsTmp);
}


/**
 * Calcula a diferenca entre duas datas
 * Formato em global.dateformat
 * @param type $from
 * @param type $to
 * @param type $negativo
 * @return type
 */
function gDateDiff($from, $to, $negativo = 0): float
{
	$fmt = strtolower((string) gVar("global.dateformat"));
	$charf = '-';
	if (strpos($fmt, "-") > 0) {
		$charf = '-';
	}

	if (strpos($fmt, "/") > 0) {
		$charf = '/';
	}

	$mto = explode($charf, $to);
	$mfrom = explode($charf, $from);
	$mfmt = explode($charf, $fmt);

	$contador = count($mfmt);
	for ($t = 0; $t < $contador; $t++) {
		$dat = $mfmt[$t];
		if ($dat[0] === "d") {
			$from_day = $mfrom[$t];
			$to_day = $mto[$t];
		}

		if ($dat[0] === "m") {
			$from_month = $mfrom[$t];
			$to_month = $mto[$t];
		}

		if ($dat[0] === "y") {
			$from_year = $mfrom[$t];
			$to_year = $mto[$t];
		}
	}

	if (strlen($from_year) < 4) {

		if ($from_year < 50) {
			$from_year = "20" . $from_year;
		} else {
			$from_year = "19" . $from_year;
		}

	}

	if (strlen($to_year) < 4) {

		if ($to_year < 50) {
			$to_year = "20" . $to_year;
		} else {
			$to_year = "19" . $to_year;
		}

	}

	$from_date = mktime(0, 0, 0, $from_month, $from_day, $from_year);
	$to_date = mktime(0, 0, 0, $to_month, $to_day, $to_year);
	$days = ($to_date - $from_date) / 86400;

	/* Adicionado o ceil($days) para garantir que o resultado seja sempre um numero inteiro */
	if ($days < 0 && $negativo == 0) {
		$days = $days * -1;
	}

	return ceil($days);
}


/**
 * Processa intervalo entre data e hora especifico
 *
 * @param start {Date|String} valor data-hora inicial
 * @param end {Date|String} valor data-hora final (padrão é a data e hora atual)
 * @param params {array}
 * @param format {String} tipo de retorno desejado
 * @return {integer} valor segundo o formato informado
 *
 * @example
 * echo gDateTimeDiff("05/01/2012 23:55",
 *   "06/01/2012 00:18",
 *   array("format" =>"minutes)"
 * ));
 * @author Erique Bomfim May.21.2012
 */
function gDateTimeDiff($start, $end = "", $params = null): array
{
	$start = trim((string) $start);
	$end = trim((string) $end);

	$_dateTimeStart = new DateTime($start);
	$_dateTimeEnd   = new DateTime($end);
	$_dateTimeDiff  = $_dateTimeStart->diff($_dateTimeEnd);
	$_gDateTime = [];

	$_absolute = ($_dateTimeDiff->format("%d") * 1440) + ($_dateTimeDiff->format("%h") * 60) + $_dateTimeDiff->format("%i");
	$_gDateTime["absolute"] = $_absolute;

	if (isset($params["format"])) {
		if ($params["format"] == "minutes") {
			$_gDateTime["formatted"] = $_absolute . "m";
		} elseif ($params["format"] == "hours") {
			$_hour = ($_dateTimeDiff->format("%d") * 24) + $_dateTimeDiff->format("%h");
			$_time = ($_hour == 0 ? "" : $_hour . "h") . $_dateTimeDiff->format("%i") . "m";
			$_gDateTime["formatted"] = $_time;
		}
	}
	return $_gDateTime;
}


/**
 * Soma uma quantidade de dias a data atual retorna padrêo brasileiro
 * @param type $from
 * @param type $days
 * @param type $month
 * @param type $year
 * @return type
 */
function gDateAdd($from, $days = 1, $month = 0, $year = 0)
{
	$fmt = strtolower((string) gVar("global.dateformat"));
	$charf = '-';
	if (strpos($from, "-") > 0) {
		$charf = '-';
	}

	if (strpos($from, "/") > 0) {
		$charf = '/';
	}

	$mfrom = explode($charf, $from);
	$charf = '-';
	if (strpos($fmt, "-") > 0) {
		$charf = '-';
	}

	if (strpos($fmt, "/") > 0) {
		$charf = '/';
	}

	$mfmt = explode($charf, $fmt);

	$contador = count($mfmt);
	for ($t = 0; $t < $contador; $t++) {
		$dat = $mfmt[$t];
		if ($dat[0] === "d") {
			$from_day = $mfrom[$t];
		}

		if ($dat[0] === "m") {
			$from_month = $mfrom[$t];
		}

		if ($dat[0] === "y") {
			$from_year = $mfrom[$t];
		}
	}

	if (strlen($from_year) < 4) {
		if ($from_year < 50) {
			$from_year = "20" . $from_year;
		} else {
			$from_year = "19" . $from_year;
		}
	}

	return gDate(date("Y-m-d", mktime(0, 0, 0, $from_month + $month, $from_day + $days, $from_year + $year)));
}


/**
 *
 * @param type $hoje
 * @return type
 */
function today($hoje): string
{
	$sem = [];
	$sem[] = gT("sun.long");
	$sem[] = gT("mon.long");
	$sem[] = gT("tue.long");
	$sem[] = gT("wed.long");
	$sem[] = gT("thu.long");
	$sem[] = gT("fri.long");
	$sem[] = gT("sat.long");

	$meses[] = '';
	$meses[] = gT('jan.long');
	$meses[] = gT('feb.long');
	$meses[] = gT('mar.long');
	$meses[] = gT('apr.long');
	$meses[] = gT('may.long');
	$meses[] = gT('jun.long');
	$meses[] = gT('jul.long');
	$meses[] = gT('aug.long');
	$meses[] = gT('sep.long');
	$meses[] = gT('oct.long');
	$meses[] = gT('nov.long');
	$meses[] = gT('dec.long');

	$tHoje = strtotime($hoje);
	return ucfirst((string) $sem[date("w", $tHoje)]) . ", " . date("d", $tHoje) . gT("monthyearseparator") . $meses[date("n", $tHoje)] . gT("monthyearseparator") . date("Y", $tHoje);
}


function gDBMemo($content): array|string
{
	$content = str_replace('"','“', $content);
	return str_replace("'",'‘', $content);
}


function gMemo($content): array|string
{
	$content = str_replace('“', '"', $content);
	return str_replace('‘', "'",$content);
}


/**
 * Retorna o dia da semana de uma data em formato do BD
 * @param type $date
 * @return type
 */
function gWeekDay($date)
{
	$days = '';
	$days[1] = gT('Segunda');
	$days[2] = gT('Terça');
	$days[3] = gT('Quarta');
	$days[4] = gT('Quinta');
	$days[5] = gT('Sexta');
	$days[6] = gT('Sábado');
	$days[7] = gT('Domingo');
	return($days[date('N', strtotime($date))]);
}


function gTime($value): string|int|float|false|null
{
	if ($value != '') {
		// Remove caracteres inválidos
		$old   = $value;
		$value = '';
		for ($i = 0; $i < strlen((string) $old); $i++) {
			if (is_numeric($old[$i])) {
				$value .= $old[$i];
			}
		}

		if (strlen($value) == 1) {
			$value = "0".$value."00";
		}

		if (strlen($value) == 2) {
			$value .= "00";
		}

		if (strlen($value) == 3) {
			$value = "0".$value;
		}

		if (strlen($value) == 4) {
			$value = substr($value,0,2).":".substr($value,2,2);
		}

		// Se for um horário inválido
		if (substr($value,0,2) > 23) {
			$value = '23:'.substr($value,2);
		}

		if (substr($value,3,2) > 59) {
			$value = substr($value,0,3).'59';
		}

	}

	return($value);
}


function gDBTime($value)
{
	return (gTime($value));
}


/**
 * Padroniza o formato da data, convertendo-a do BD para o do usuário
 * @global type $gDevice
 * @param type $t_parameter
 * @return type
 */
function gDate($t_parameter, $nullDateString = "")
{
	global $gDevice;

	$fmt = strtolower((string) gVar("global.dateformat"));
	if (gVar("global.datenull") == "") {
		gVar("global.datenull", "0000-00-00");
	}

	if (($t_parameter == gVar("global.datenull")) || ($t_parameter == "")) {
		$t_parameter = $nullDateString;
	} else {
		// $t_parameter=strftime($fmt,strtotime($t_parameter)); // Apresentou problemas com datas ateriores 1970
		$sep = "/";
		if (strpos($fmt, "-") > 0) {
			$sep = "-";
		}

		$t_p = explode("-", $t_parameter);
		$t_f = explode($sep, $fmt);
		$dia = intval($t_p[2]);
		$mes = intval($t_p[1]);
		$ano = intval($t_p[0]);
		$data = "";
		for ($t = 0; $t < 3; $t++) {
			if (str_starts_with($t_f[$t], "d")) {
				$data.=sprintf("%0" . strlen($t_f[$t]) . "s", $dia);
			}

			if (str_starts_with($t_f[$t], "m")) {
				$data.=sprintf("%0" . strlen($t_f[$t]) . "s", $mes);
			}

			if (str_starts_with($t_f[$t], "y")) {
				$data.=right(sprintf("%0" . strlen($t_f[$t]) . "s", $ano), strlen($t_f[$t]));
			}

			$data.=$sep;
		}

		$t_parameter = substr($data, 0, strlen($data) - 1);
	}

	if ($gDevice == "plan") {
		return str_replace("-", "/", $t_parameter);
	}

	return($t_parameter);
}


/**
 *
 * @global type $gDevice
 * @param type $t_parameter
 * @return type
 */
function gDateTime($t_parameter, $nullDateString="")
{
	global $gDevice;
	if ($gDevice != "plan") {

		if (gVar("global.datenull") == "") {
			gVar("global.datenull", "0000-00-00");
		}

		if ((str_starts_with($t_parameter, (string) gVar("global.datenull"))) || ($t_parameter == "")) {
			$sai = $nullDateString;
		} else {
			$data = str_contains($t_parameter, "T") ? explode("T", $t_parameter) : explode(" ", $t_parameter);
			$time = $data[1];
			$sai  = gDate($t_parameter) . " " . $time;
		}

	} else {
		$sai = $t_parameter;
	}

	return($sai);
}


/**
 *
 * @param type $t_parameter
 * @return type
 */
function gDBDate($t_parameter)
{
	if ($t_parameter != "") {
		if (str_contains($t_parameter, "T")) {
			$t_parameter = substr($t_parameter, 0, 10);
		} else {
			$t_parameter = substr($t_parameter, 0, 10);
			$fmt = strtolower((string) gVar("global.dateformat"));

			$char = '-';
			if (strpos($t_parameter, "-") > 0) {
				$chard = '-';
			}

			if (strpos($t_parameter, "/") > 0) {
				$chard = '/';
			}

			if (strpos($fmt, "-") > 0) {
				$charf = '-';
			}

			if (strpos($fmt, "/") > 0) {
				$charf = '/';
			}

			$data = explode($chard, $t_parameter);
			$form = explode($charf, $fmt);

   			$counter = count($data);
			for ($t = 0; $t < $counter; $t++) {
				$dat = $form[$t];
				if ($dat[0] === "d") {
					$dia = $data[$t];
				}

				if ($dat[0] === "m") {
					$mes = $data[$t];
				}

				if ($dat[0] === "y") {
					$ano = $data[$t];
				}

			}

			if (strlen($ano) < 4) {

				if ((intval($ano) == 0) && (intval($mes) == 0) && (intval($dia) == 0)) {

					if ((gVar("global.datenull") == "") || (gVar("global.datenull") == "0000-00-00")) {
						$ano = "0000";
						$mes = "00";
						$dia = "00";
					} else {
						$ano = substr((string) gVar("global.datenull"), 0, 4);
						$mes = substr((string) gVar("global.datenull"), 5, 2);
						$dia = substr((string) gVar("global.datenull"), 8, 2);
					}

				} elseif ($ano < 50) {
					$ano = "20" . $ano;
				} else {
					$ano = "19" . $ano;
				}
			}

			$t_parameter = str_pad($ano, 4, "0", STR_PAD_LEFT) . "-" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "-" . str_pad($dia, 2, "0", STR_PAD_LEFT);
		}

	} else {
		$t_parameter = gVar("global.datenull");
	}

	return($t_parameter);
}


/**
 *
 * @param type $t_parameter
 * @return type
 */
function gDBDateTime($t_parameter): string
{
	$sai = $t_parameter;
	if (strlen($t_parameter) < 19) {

		if (!str_contains($t_parameter, "T")) {

			if ($t_parameter != "") {
				$data = explode(" ", $t_parameter);
				$time = $data[1];
				$sai = gDBDate($data[0]) . " " . $time;

				if (gDBDate($data[0]) == gVar("global.datenull")) {
					$sai = gDBDate($data[0]) . "T00:00:00";
				}

			} else {
				$sai = gVar("global.datenull") . "T00:00:00";
			}

			if (gVar("database.url") == "200.254.228.6") {
				$sai = str_replace("T", " ", $sai);
			}

		} else {
			$sai = str_replace("-03:00", "", $t_parameter);
		}

	}

	return(substr((string) $sai, 0, 19));
}


/**
 *
 * @param type $t_parameter
 * @return type
 */
function gDBFloat($t_parameter): float
{
	if ($t_parameter == '') {
		$t_parameter = '0';
	}

	// $dec = 2;
	$c1 = ',';
	$c2 = '.';
	$fmt = gVar("global.numformat");

	if (($fmt == "0000,00") || ($fmt == "0,00")) {
		$c1 = ',';
		$c2 = '';
	}

	if (($fmt == "0000.00") || ($fmt == "0.00")) {
		$c1 = '.';
		$c2 = '';
	}

	if ($fmt == "0,000.00") {
		$c1 = '.';
		$c2 = ',';
	}

	$t_parameter = str_replace($c2, "", $t_parameter);
	return(floatval(str_replace($c1, ".", $t_parameter)));
}


/**
 *
 * @global type $gDevice
 * @param type $t_parameter
 * @return type
 */
function gFloat($t_parameter): string|array
{
	global $gDevice;
	$fmt = gVar("global.numformat");
	$p1 = strpos((string) $fmt, ".");
	$p2 = strpos((string) $fmt, ",");
	if (($p1 > 0) && ($p2 > 0)) {
		if ($p1 < $p2) {
			$c1 = ',';
			$c2 = '.';
		} else {
			$c1 = '.';
			$c2 = ',';
		}
	} else {
		if ($p1 > 0) {
			$c1 = ',';
			$c2 = '';
		}

		if ($p2 > 0) {
			$c1 = '.';
			$c2 = '';
		}
	}

	$m = explode($c1, (string) $fmt);
	$dec = strlen($m[1]);

	$t_parameter = number_format($t_parameter, $dec, $c1, $c2);
	if ($gDevice == "plan") {

		$t_parameter = str_replace(".", "", $t_parameter);
		if ($_REQUEST['gExportTo'] == "Excel") {
			$t_parameter = str_replace(",", ".", $t_parameter);
		}

	}

	return($t_parameter);
}


/**
 *
 * @return type
 */
function gFieldRpl() : array
{
    return [
        ["zao", "zão"],
        ["gao", "gão"],
        ["hao", "hão"],
        ["ndereco", "ndereço"],
        ["email", "e-mail"],
        ["cao", "ção"],
        ["sao", "são"],
        ["ssoes", "ssões"],
        ["nao", "não"],
        ["mao", "mão"],
        ["coes", "ções"],
        ["tao", "tão"],
        ["toes", "tões"],
        ["odigo", "ódigo"],
        ["id_", ""],
        ["umero", "úmero"],
        ["ervico", "erviço"],
        //$rpl[] = ["Ip", "IP"];
        ["Tcp", "TCP"],
        ["Dns", "DNS"],
        ["Smtp", "SMTP"],
        ["Pop", "POP"],
        ["cpf", "CPF"],
        ["cnpj", "CNPJ"],
        ["inss", "INSS"],
        ["Iss", "ISS"],
        ["icms", "ICMS"],
        ["Cfc", "CFC"],
        //$rpl[] = ["Ipi", "IPI"];
        ["irpf", "IRPF"],
        ["irpj", "IRPJ"],
        ["giusoft", "GiuSoft"],
        //$rpl[] = ["Ir", "IR"];
        ["iof", "IOF"],
        ["veiculo", "veículo"],
        ["horario", "horário"],
        ["minimo", "mínimo"],
    ];
}


/**
 *
 * @param type $cpf
 * @return type
 */
function gCpf($cpf): string
{
	$cpf = gToNumbers($cpf);
	$cpf = substr(str_pad((string) $cpf, 11, "0", STR_PAD_LEFT),-11);
	return(substr($cpf, 0, 3) . "." . substr($cpf, 3, 3) . "." . substr($cpf, 6, 3) . "-" . substr($cpf, -2));
}


/**
 *
 * @param type $cnpj
 * @return type
 */
function gCnpj($cnpj): string
{
	$cnpj = gToNumbers($cnpj);
	$cnpj = substr(str_pad((string) $cnpj, 14, "0", STR_PAD_LEFT),-14);
	return(substr($cnpj, 0, 2) . "." . substr($cnpj, 2, 3) . "." . substr($cnpj, 5, 3) . "/" . substr($cnpj, 8, 4) . "-" . substr($cnpj, -2));
}


/**
 *
 * @param type $parameter
 * @return type
 */
function gString2Field($parameter)
{
	$parameter = strtolower($parameter);

	if (gVar("global.language") == "pt_BR") {
		$rpl = gFieldRpl();
		$rpl[] = ["a", "ã"];
		$rpl[] = ["a", "á"];
		$rpl[] = ["a", "à"];
		$rpl[] = ["a", "â"];
		$rpl[] = ["e", "é"];
		$rpl[] = ["e", "ê"];
		$rpl[] = ["e", "è"];
		$rpl[] = ["i", "í"];
		$rpl[] = ["o", "ó"];
		$rpl[] = ["o", "ô"];
		$rpl[] = ["o", "õ"];
		$rpl[] = ["o", "ò"];
		$rpl[] = ["u", "ú"];
		$rpl[] = ["u", "ü"];
		$rpl[] = ["c", "ç"];

		$rpl[] = ["A", "Ã"];
		$rpl[] = ["A", "Á"];
		$rpl[] = ["A", "À"];
		$rpl[] = ["A", "Â"];
		$rpl[] = ["E", "É"];
		$rpl[] = ["E", "Ê"];
		$rpl[] = ["E", "È"];
		$rpl[] = ["I", "Í"];
		$rpl[] = ["O", "Ó"];
		$rpl[] = ["O", "Ô"];
		$rpl[] = ["O", "Õ"];
		$rpl[] = ["O", "Ò"];
		$rpl[] = ["U", "Ú"];
		$rpl[] = ["U", "Ü"];
		$rpl[] = ["C", "Ç"];
		$rpl[] = ["_", " "];

		$rpl[] = ["e", "&"];
		$rpl[] = ["", "ª"];
		$rpl[] = ["", "º"];
		$rpl[] = ["", "°"];
		$rpl[] = ["", "*"];
		$rpl[] = ["", "%"];
		$rpl[] = ["", "#"];
		$rpl[] = ["", "\""];
		$rpl[] = ["", "\\"];
		$rpl[] = ["", "\/"];
		$rpl[] = ["", "@"];

  		$counter = count($rpl);
		for ($t = 0; $t < $counter; $t++) {
			$parameter = str_replace($rpl[$t][1], $rpl[$t][0], $parameter);
		}

	}

	$strlength = strlen($parameter);
	$retparameter = [];
	for ($i = 0; $i < $strlength; $i++) {

		if ((ord($parameter[$i]) >= 48 && ord($parameter[$i]) <= 57) ||
				  (ord($parameter[$i]) >= 65 && ord($parameter[$i]) <= 90) ||
				  (ord($parameter[$i]) >= 97 && ord($parameter[$i]) <= 122)) {
			$retparameter .= $parameter[$i];
		}

	}

	return($retparameter);
}


/**
 *
 * @param type $parameter
 * @return type
 */
function gField2String($parameter): string
{
	$parameter = str_replace("_", " ", $parameter);
	if (gVar("global.language") == "pt_BR") {
		$rpl = gFieldRpl();
  		$counter = count($rpl);
		for ($t = 0; $t < $counter; $t++) {
			$parameter = str_replace($rpl[$t][0], $rpl[$t][1], $parameter);
		}
	}

	$parameter = match ($parameter) {
		'termino' => 'término',
		'inicio' => 'início',
		'saida' => 'saída',
		default => $parameter,
	};
	return ucfirst($parameter);
}


/**
 *
 * @param type $name
 * @param type $max
 * @return type
 */
function gShortName($name, $max = 2): string
{
	$sai = '';
	$n = explode(" ", $name);
	for ($a = 0; $a < $max; $a++) {

		if (
			(strtolower($n[$a]) === "de")
			|| (strtolower($n[$a]) === "da")
			|| (strtolower($n[$a]) === "do")
			|| (strtolower($n[$a]) === "das")
			|| (strtolower($n[$a]) === "dos")
		) {
			$max++;
		}

		$sai.=$n[$a] . " ";
	}

	return(trim($sai));
}


/**
 *
 * @param type $texto
 * @return type
 */
function gHtml2str($texto): string
{
	$t = trim($texto);
	$t = str_replace('&nbsp;', ' ', $t);
	$t = str_replace('N<sup>o</sup>', 'Nê', $t);
	$trans = get_html_translation_table(HTML_ENTITIES);
	$trans = array_flip($trans);
	$t = strtr($t, $trans);
	return (trim(strip_tags($t)));
}


/**
 *
 * @param type $valor
 * @return type
 */
function gFloat2String($valor = 0): string
{
	$singular = ["centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão"];
	$plural   = ["centavos", "reais", "mil", "milhões", "bilhões", "trilhões", "quatrilhÃµes"];

	$c 		  = ["", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"];
	$d 		  = ["", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"];
	$d10 	  = ["dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezesete", "dezoito", "dezenove"];
	$u 		  = ["", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove"];

	$z = 0;

	$valor = number_format($valor, 2, ".", ".");
	$inteiro = explode(".", $valor);
 	$counter = count($inteiro);
	for ($i = 0; $i < $counter; $i++) {
		for ($ii = strlen($inteiro[$i]); $ii < 3; $ii++) {
			$inteiro[$i] = "0" . $inteiro[$i];
		}
	}

	// $fim identifica onde que deve se dar junêêo de centenas por "e" ou por "," ;)
	$fim = count($inteiro) - ($inteiro[count($inteiro) - 1] > 0 ? 1 : 2);
 	$counter = count($inteiro);
	for ($i = 0; $i < $counter; $i++) {
		$valor = $inteiro[$i];
		$rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
		$rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
		$ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";
		$r  = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd && $ru) ? " e " : "") . $ru;
		$t  = count($inteiro) - 1 - $i;
		$r .= $r !== '' && $r !== '0' ? " " . ($valor > 1 ? $plural[$t] : $singular[$t]) : "";

		if ($valor === "000") {
			$z++;
		} elseif ($z > 0) {
			$z--;
		}

		if (($t == 1) && ($z > 0) && ($inteiro[0] > 0)) {
			$r .= (($z > 1) ? " de " : "") . $plural[$t];
		}

		if ($r !== '' && $r !== '0') {
			$rt = $rt . ((($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? ", " : " e ") : " ") . $r;
		}
	}
	$rt = ucfirst(trim($rt));
	return($rt ?: "zero");
}


/**
 *
 * @return type
 */
function gPasswordSugest(): string
{
	$cons = ["lh", "nh", "cr", "tr", "pr", "b", "c", "d", "f", "g", "h", "j", "k", "l", "m", "m", "p", "qu", "r", "s", "t", "v", "x", "z"];
	$vog  = ["a", "e", "i", "o", "u"];
	return($cons[random_int(0, 23)] . $vog[random_int(0, 4)] . random_int(0, 99) . $cons[random_int(0, 23)] . $vog[random_int(0, 4)] . random_int(0, 99));
}


/**
 *
 * @param type $v
 * @return type
 */
function gTrueFalse($v)
{
	if ($v == 1) {
		return gLng("message.yes.short");
	}

	return(gLng("message.no.short"));
}


function print_r_tree($data): void
{
    // capture the output of print_r
    $out = print_r($data, true);

    // replace something like '[element] => <newline> (' with <a href="javascript:toggleDisplay('...');">...</a><div id="..." style="display: none;">
    $out = preg_replace('/([ \t]*)(\[[^\]]+\][ \t]*\=\>[ \t]*[a-z0-9 \t_]+)\n[ \t]*\(/iUe',"'\\1<a href=\"javascript:toggleDisplay(\''.(\$id = substr(md5(rand().'\\0'), 0, 7)).'\');\">\\2</a><div id=\"'.\$id.'\" style=\"display: none;\">'", $out);

    // replace ')' on its own on a new line (surrounded by whitespace is ok) with '</div>
    $out = preg_replace('/^\s*\)\s*$/m', '</div>', $out);

    // print the javascript function toggleDisplay() and then the transformed output
    echo '<script language="Javascript">function toggleDisplay(id) { document.getElementById(id).style.display = (document.getElementById(id).style.display == "block") ? "none" : "block"; }</script>'."\n$out";
}


/**
 * Mostra o conteêdo de uma variêvel na janela cliente
 */
function gD($t, $exit = 0): void
{
	$backtrace = debug_backtrace();
  	echo $backtrace[0]['file'] . ':' . $backtrace[0]['line'];
	echo "<pre>";
	print_r_tree($t);
	echo "</pre>";
	if ($exit) {
		exit;
	}
}


/*
 * Mostra todas as caracteristicas de um objeto na janela do cliente
 */
function gDO($object, $exit = 0): void
{
	$backtrace = debug_backtrace();
  	echo $backtrace[0]['file'].':'.$backtrace[0]['line'];
	echo '<pre>';

	if (is_object($object)) {
		$class = $object::class;
		print_r_tree([
			'Class'      => $class,
			'Values'	 =>	(array) $object,
			'Methods'    => get_class_methods($class),
			'Attributes' => get_class_vars($class),
			'Parents'    => get_parent_class($class),
			'Directories includeds' => get_included_files()
		]);
	} else {
		gD($object);
	}

	echo '</pre>';
	if ($exit) {
		exit;
	}
}


function gStop($txt): void
{
	echo "<B>STOP</B><HR>".date("d-m-y H:i:s")."<br>".$_SERVER['SCRIPT_FILENAME']."<br><br>";
	echo "<hr>REQUEST:<br>";
	var_dump($_REQUEST);
	echo "<br>";

	if ($txt) {
		echo "<hr>";
		var_dump($txt);
	}

	exit;
}


$gDR = 0;
function gDR($t, $big=false): void
{
	global $usrId, $gDR;

	if ($usrId == 1) {
		if ($gDR == 0) {
			echo "<br><br><br><br>";
		}

		$gDR++;
		if (is_array($t) || is_object($t)) {
			echo "<b>[ $gDR ]</b><pre>";
			print_r_tree($t);
			echo "</pre>";
		} elseif ($big) {
			echo "<h3>[ $gDR ] $t</h3>";
		} else {
				echo "<b>[ $gDR ]</b><pre>".$t."</pre>";
		}
	}
}

function gDT($array): void
{
	echo "<hr><table border='1'>";
	foreach ($array as $row) {
		echo "<tr>";
		foreach ($row as $key => $col) {
			echo "<td>".$key."</td>";
		}

		echo "</tr>";
		break;
	}

	foreach ($array as $row) {
		echo "<tr>";
		foreach ($row as $col) {
			echo "<td>".$col."</td>";
		}
		echo "</tr>";

	}

	echo "</table>";
}


/**
 *
 * @param type $valor
 * @return type
 */
function revertCleanField($valor): array|string
{
	$sai = str_replace("‘", "'", $valor);
	return(str_replace("“", "\"", $sai));
}


/**
 *
 * @param type $valor
 * @return type
 */
function gCleanField($valor): string
{
	if (is_array($valor)) {
		foreach ($valor as $key => $val) {
			$valor[$key] = gCleanField($val);
		}

		return $valor;
	}


	$valor = autoencode($valor);
	$sai   = str_replace("'", "‘", $valor);
	$sai   = str_replace("\"", "“", $sai);
	$sai   = trim($sai);

	return $sai;
}

/**
 *
 * @param type $img
 * @param type $lang
 * @return type
 */
function gOcr($img, $lang = "en"): string
{
	$tmp = tempnam('/tmp', 'ocr');
	$par = "-l eng";
	switch($lang) {
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

	if ($lang == 'pt') {
		$cmd = "/usr/local/bin/tesseract $img $tmp $par 2>/tmp/tess.log";
	}

	gLog("==> cmd $cmd");
	shell_exec($cmd);
	$txt = file_get_contents("$tmp.txt");
	unlink($tmp . ".txt");
	$txt = str_replace('"', "“", $txt);
	$txt = str_replace('~', " ", $txt);
	$txt = str_replace("\n\r", "\n", $txt);
	$txt = str_replace("\r\n", "\n", $txt);
	$txt = str_replace("\n\n", "\n", $txt);
	return(trim($txt));
}


/**
 * Redimensiona imagem onde $arquivo = $_FILES["arquivo"]
 * @param type $arquivo
 * @param type $maxLargura
 * @param type $maxAltura
 * @param type $deleteFile
 * @return type
 */
function gImageResize($file, $resize_width = 150, $resize_height = 150, $deleteFile = true)
{
	$data = '';

	[$w, $h] = getimagesize($file);

	switch(exif_imagetype($file)){
		case 'IMAGETYPE_PNG':
			$image_create = imagecreatefrompng($file);
			$image_type = 'image/png';
			break;

		case 'IMAGETYPE_GIF':
			$image_create = imagecreatefromgif($file);
			$image_type = 'image/gif';
			break;

			case 'IMAGETYPE_JPEG':
		default:
			$image_create = imagecreatefromjpeg($file);
			$image_type = 'image/jpeg';
			break;
	}

	$image_tmp = imagecreatetruecolor($resize_width, $resize_height);

	imagecopyresampled($image_tmp, $image_create, 0, 0, 0, 0, $resize_width, $resize_height, $w, $h);

	$r = match (exif_imagetype($file)) {
		'IMAGETYPE_PNG' => imagegif($image_tmp,$file),
		'IMAGETYPE_GIF' => imagepng($image_tmp,$file),
		default => imagejpeg($image_tmp,$file),
	};

	imagedestroy($image_create);
	imagedestroy($image_tmp);

	if ($r) {
		$f = fopen($file, "rb");
		$image_data = base64_encode(fread($f, filesize($file)));
		$data['file'] = $image_data;
		$data['type'] = $image_type;
		$data['width'] = $resize_width;
		$data['height'] = $resize_height;

		//Apagando a imagem da pasta
		if ($deleteFile) {
			unlink($arq);
		}

	} else {
			$data['file'] = "/9j/4AAQSkZJRgABAQAAAQABAAD//gA8Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gMTAwCv/bAEMAAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAf/bAEMBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAf/AABEIAFoAWgMBIgACEQEDEQH/xAAfAAABBQEBAQEBAQAAAAAAAAAAAQIDBAUGBwgJCgv/xAC1EAACAQMDAgQDBQUEBAAAAX0BAgMABBEFEiExQQYTUWEHInEUMoGRoQgjQrHBFVLR8CQzYnKCCQoWFxgZGiUmJygpKjQ1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4eLj5OXm5+jp6vHy8/T19vf4+fr/xAAfAQADAQEBAQEBAQEBAAAAAAAAAQIDBAUGBwgJCgv/xAC1EQACAQIEBAMEBwUEBAABAncAAQIDEQQFITEGEkFRB2FxEyIygQgUQpGhscEJIzNS8BVictEKFiQ04SXxFxgZGiYnKCkqNTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqCg4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2dri4+Tl5ufo6ery8/T19vf4+fr/2gAMAwEAAhEDEQA/AP7+KKKKACiiigAoorhV+JHw8kvJtOTx34MfULeURzWCeKNDN9DNj5luLQX4kVskHBUc9ORwAd1RUaukiq6MGVxw6Hg+nTPHUe3Q1JQAUUUUAFFFFABRRRQAVGzpGrO7BVQcu54Hr1xx0Hv0FSVynjiZoPBfiy5jDvJB4Z1+aMJ1LLpd2Rj1K8H1GM+uAD8JP2zP26PF3xD13xF8L/hfqFz4c+Hek31/o2q6xp8tzBrfjW4sybS6VbsBRZaECxxYld2rDG4tgY/NHzJlkkm86Tzpf4xL+/H1qCOR5o0mmffNJ+9lk/6ePp09xin10bHB/E/prZ/fv/Vj9K/2Lv24vEHwy1jRfhl8UdWm1b4Zand21lpWu6ldeffeA7q8AwftZB+2+FhwdW0/H/ErXOqaRujeRH/f+GeG6hiuraaK4guI1lhnglEsM0RUFZYWUsrK24dCRjAy3Gf40m+ZXH1GfpjP9a/q1/ZSubu8/Zw+C9zqLStezfD7w+05nl86YkWgALMOScBR/ugdAAKzn0+f6HRQqXVr/wBef3d+l+p9DUUUVmbhRRRQAUUUUAFeEftM+M77wB8Avi14v0oRHUtD8D69cWImH7kXBtjZqSOgINzx6EcdK93qtNFDcxvFMkc0Mo8uWKSITRSgjoQeDj3zgj1ANAH8Zq/dTL78D/W+nvz+Xpn3p9fR/wC1r8LpvhF+0B8QfC3kmPSr3U5/FGgueIZdH8Sf6ZaA84yL/wC16ceTzx2r5wroPPGOu4OmP8nn19c+1f1P/sf+LX8a/s4fCzXH0qPR3j8N2umfZLfb5LDRybA3NsMZKX32bfy3Unp3/mE8J+Hbnxd4q8N+FbN/JufEmvaNo0Un/PH7ZdfY/tXHXrj8+/X+tT4YfD7RfhX4A8J/Dvw8J30jwno1to1pLcbftFz9lH+k3dy3d766LysMHAfAZl5rOp0+f6G+H2+X6RPQ6KKKzOkKKKKACiiigApCAcZ7dKytS1Ow0exutV1W/s9N0+wtprq/vtRureysbSBRlri7u7uQJaWgKHLk7Vxk5C4P43/tW/8ABSDzP7R8A/s83ny4uLDWfidJHlVOCLu18I2hHG0ZX/hIb8KGyx0hRkaoAznPkPBv+Cn+oC6/aPtrP/oF+BNBi+n2xr28yf8A9Qr85q1dc17W/E2pTaz4k1jVNf1W4/1uqaxf3V9fTcj/AJe70e/+Gayq6ErJLsrHLV6/4X+p6f8ABfWdK8P/ABe+Gmt63cx2ej6X438P3+qXlxxBZW5uv+Pq77Zx/U1/Wlpmq6brdlb6lpGoWeq6fdx+ba6jp11b3tjNFjIMd1akxkYPGCQDkdc1/G9XrXwu+OvxZ+DGqR6r8O/Gur6K/liOXSpZRf6FeQKdy2t3pN7/AMS/IIBHcEZ4NRPZPz/r8iqdRw/P5/ej+t0ADoKWvxx/Z9/4KfWmoTWnhv8AaC0230uaSTyYfH/h22YaUPm5PiHQRl7Lgn/TtO4JCj+yU5J/W7w/4i0LxXo9l4g8NaxYa/oupwiaw1XSrq3vbG7gOfmtbu0Yq4zzkEkEdAcEZHRTnz7f1+ffodBRRRQaBXy5+0Z+1X8N/wBm/RI5vE89xrXizVI7lvDvgnSJf+JtqZQspu7ycgx6JoytjdqV+MFtyaXHqLR7F9e+KPxB0f4V+APFvxD14j+yvCWjahq8saFTPeT2lsxtbK2AHN5qF4VsUB5JfoBur+UT4l/EbxP8WfHHiHx94vv5LzWtf1C4upfMlM1vpunj/j00qzA4+w6dYj+z6uCTvf8Ar+rGVSr7Ps/v/T5HsHx9/aw+Ln7Qt86+LNYGleE47lZNL8CaBNdWXh60xn7Fc3jHLa3fAkkahqROMk6R/ZeTn5noorU5AooooAKKKKACvevgZ+0r8V/2fNXF/wCA9ekk0eea2m1nwhq/2q98K6vjvd2nH2K+PQ6hp39l6oRkHIJFeC0UAf1PfsyftKeFf2lPAx8SaRbHRdf0u5GneLPCtzdpd3uiagSTb3NtcDAvtF1EBpdL1BERXGRgba+mq/kT+E/xg8ffBPxXbeMvh5rUmkapH9mjv7aX9/pevWAIK6XqtqObyyUgFWBBUgEYNftNo/8AwVL+C8uk6XLrnhvxBZ63Lp1jJrFpaxRT2trqj20Tahb20xQmaCC7M0UMpJMkaq5OTWTg+mv3fqzphU02/Hb/AIH3dTj/APgqj8YE0rwl4Q+CelXAF/4suz4s8URRSbfJ8P6NdBdJtbsZ+7qWuA6ghzynhu9yeK/D2vr/APb0ubm6/aw+MRubie4NlceHrayM8sk32S3/AOEMsP3Fr5jN9nh+Zv3UWxOT8vJr5AqobP1/RGc/4i9f/bmFFFFWZBRRRQAUUUUAFFFFABRRRQB//9k=";
			$data['type'] = "image/jpeg";
			$data['width'] = 150;
			$data['height'] = 150;
	}
	return($data);
}


/**
 * Returns 1 if $s1 comes after $s2 alphabetically, 0 if not.
 * @param type $s1
 * @param type $s2
 * @return type
 */
function comesafter(array $s1, array $s2): int
{
	/*
	 * We don't want to overstep the bounds of one of the strings and segfault,
	 * so let's see which one is shorter.
	 */
	$order = 1;

	if (strlen($s1) > strlen($s2)) {
		$temp = $s1;
		$s1 = $s2;
		$s2 = $temp;
		$order = 0;
	}

	for ($index = 0; $index < strlen($s1); $index++) {
		/*
		 * $s1 comes after $s2
		 */
		if ($s1[$index] > $s2[$index]) {
			return ($order);
		}

		/*
		 * $s1 comes before $s2
		 */
		if ($s1[$index] < $s2[$index]) {
			return (1 - $order);
		}
	}

	/*
	 * Special case in which $s1 is a substring of $s2
	 */
	return ($order);
}


/**
 * Sort a multi-dimensional array by a second-degree index. For instance, the 0th index
 * of the Ith member of both the group and user arrays is a string identifier. In the
 * case of a user array this is the username; with the group array it is the group name.
 * @param type $sortarray
 * @param type $index
 * @return type
 */
function gSortArray(array $sortarray, $index): array
{
	$lastindex = count($sortarray) - 1;
	for ($subindex = 0; $subindex < $lastindex; $subindex++) {
		$lastiteration = $lastindex - $subindex;
		for ($iteration = 0; $iteration < $lastiteration; $iteration++) {
			$nextchar = 0;
			if (comesafter($sortarray[$iteration][$index], $sortarray[$iteration + 1][$index])) {
				$sortarray[$iteration] = $sortarray[$iteration + 1];
				$sortarray[$iteration + 1] = $temp;
			}
		}
	}

	return ($sortarray);
}


/**
 *
 * @param type $date
 * @return type
 */
function gDateString($date = ''): string
{
	$meses[] = '';
	$meses[] = 'janeiro';
	$meses[] = 'fevereiro';
	$meses[] = 'março';
	$meses[] = 'abril';
	$meses[] = 'maio';
	$meses[] = 'junho';
	$meses[] = 'julho';
	$meses[] = 'agosto';
	$meses[] = 'setembro';
	$meses[] = 'outubro';
	$meses[] = 'novembro';
	$meses[] = 'dezembro';

	if ($date == '') {
		$date = date("Y-m-d");
	}

	$mes = $meses[intval(substr($date, 5, 2))];
	return(substr($date, 8, 2) . " de " . $mes . " de " . substr($date, 0, 4));
}


/**
 *
 * @param type $texto
 * @param type $macros
 * @param type $macroSeparators
 * @return type
 */
function gReplaceMacros($texto, $macros = "", $macroSeparators = "{}")
{
	if (is_array($macros)) {
		foreach ($macros as $key => $value) {
			$texto = str_ireplace(substr($macroSeparators, 0, 1) . $key . substr($macroSeparators, -1), $value, $texto);
		}
	}

	return($texto);
}

/** gWiki - transforma uma string com codigo Wiki em HTML
  Codigos Wiki:

  __ = negrito
  '' = italico
  #  = marcador

 */
function gWiki($texto, $macros = "", $bd = ""): string|array
{
	global $gId;
	$texto = nl2br((string) $texto);
	$texto = str_replace("(__", "(<b>", $texto);
	$texto = str_replace(" __", " <b>", $texto);
	$texto = str_replace("__ ", "</b> ", $texto);
	$texto = str_replace("__,", "</b>,", $texto);
	$texto = str_replace("__.", "</b>.", $texto);
	$texto = str_replace("__;", "</b>;", $texto);
	$texto = str_replace("__-", "</b>-", $texto);
	$texto = str_replace("__)", "</b>)", $texto);
	$texto = str_replace(" \'\'", "</b>", $texto);
	$texto = str_replace("\'\',", "</b>", $texto);
	$texto = str_replace("\'\'.", "</b>", $texto);
	$texto = str_replace("\'\';", "</b>", $texto);
	$texto = str_replace("\'\'-", "</b>", $texto);
	$texto = str_replace("\'\'", "</b>", $texto);
	$texto = str_replace("#", "<li style='margin-left: 16px'>", $texto);
	$texto = str_replace("@gId", $gId, $texto);
	if (is_array($macros)) {
		foreach ($macros as $macro) {
			$texto = str_replace('@' . $macro[0] . " ", $macro[1] . " ", $texto);
			$texto = str_replace('@' . $macro[0] . ".", $macro[1] . ".", $texto);
			$texto = str_replace('@' . $macro[0] . ",", $macro[1] . ",", $texto);
			$texto = str_replace('@' . $macro[0] . ";", $macro[1] . ";", $texto);
			$texto = str_replace('@' . $macro[0] . "/", $macro[1] . "/", $texto);
			$texto = str_replace('@' . $macro[0] . "-", $macro[1] . "-", $texto);
			$texto = str_replace('@' . $macro[0] . "'", $macro[1] . "'", $texto);
			$texto = str_replace('@' . $macro[0] . "<", $macro[1] . "<", $texto);
			$texto = str_replace('@' . $macro[0] . "\n", $macro[1] . "\n", $texto);
			$texto = str_replace('@' . $macro[0] . ")", $macro[1] . ")", $texto);
			$texto = str_replace('@' . $macro[0] . "]", $macro[1] . "]", $texto);
		}
	}

	if ($bd != "") {
		$campos = array_keys($bd->fields);
		foreach ($campos as $campo) {
			$texto = str_replace('@' . $campo . " ", $bd->fields[$campo] . " ", $texto);
			$texto = str_replace('@' . $campo . ".", $bd->fields[$campo] . ".", $texto);
			$texto = str_replace('@' . $campo . ",", $bd->fields[$campo] . ",", $texto);
			$texto = str_replace('@' . $campo . ";", $bd->fields[$campo] . ";", $texto);
			$texto = str_replace('@' . $campo . "/", $bd->fields[$campo] . "/", $texto);
			$texto = str_replace('@' . $campo . "-", $bd->fields[$campo] . "-", $texto);
			$texto = str_replace('@' . $campo . ":", $bd->fields[$campo] . ":", $texto);
			$texto = str_replace('@' . $campo . "'", $bd->fields[$campo] . "'", $texto);
			$texto = str_replace('@' . $campo . "<", $bd->fields[$campo] . "<", $texto);
			$texto = str_replace('@' . $campo . "\n", $bd->fields[$campo] . "\n", $texto);
			$texto = str_replace('@' . $campo . ")", $bd->fields[$campo] . ")", $texto);
			$texto = str_replace('@' . $campo . "]", $bd->fields[$campo] . "]", $texto);
		}
	}

	return($texto);
}


/**
 * Efetua cálculos passando a fórmula e um array contendo variáveis
 * @author Giuliano Nascimento
 * @version 2.0
 * @param string $formula Fórmula (usa a mesma sintaxe do bc do shell, ou seja, aceita IF e outros comandos)
 * @param array $matriz Matriz contendo variáveis
 */
function gSuperCalc($formula, $matriz): float|int
{
	$sai = 0;
	foreach ($matriz as $campo => $valor) {
		$formula = str_ireplace($campo, $valor, $formula);
		$sai = floatval(shell_exec("echo '$formula' | bc"));
	}

	return ($sai);
}

/**
 *
 * @param type $s
 * @return type
 */
function gPhone(array $s): string
{
	$t = '';
	for ($a = 0; $a < strlen($s); $a++) {
		if (($s[$a] == '0') || ($s[$a] > 0)) {
			$t.=$s[$a];
		}
	}

	// 2140637560
	if ($t[0] === '0') {
		$t = substr($t, 1);
	}

	if (strlen($t) == 8) {
		$t = substr($t, 0, 4) . ' ' . substr($t, 4);
	}

	if (strlen($t) == 9) {
		$t = substr($t, 0, 5) . ' ' . substr($t, 5);
	}

	if (strlen($t) == 10) {
		$t = substr($t, 0, 2) . ' ' . substr($t, 2, 4) . ' ' . substr($t, 6);
	}

	if (strlen($t) == 11) {
		return substr($t, 0, 2) . ' ' . substr($t, 2, 5) . ' ' . substr($t, 7);
	}

	return($t);
}


/**
 * Converte um valor de um campo checkbox para 0 ou 1 (ao invés de '' ou 'on')
 * @author Giuliano Nascimento
 * @version 2.0
 * @param string $c Valor do campo
 * @return integer 0 (desmarcado) ou 1 (marcado)
 */
function gDBCheck($c): int
{
	return (int) (
		($c === "on")
		|| ($c === "true")
		|| (intval($c) == 1)
		|| ($c === 'Sim')
	);
}


/**
 * Converte um valor de um campo checkbox para Sim ou Não (ao invés de '' ou 'on')
 * @param string $c Valor do campo
 * @return string "Sim" ou "Não"
 */
function gCheck($c, $inverterCores=false, $simnao=[]): string
{
	global $o,$gDevice;
	$sim = gT("Sim");
	$nao = gT("Não");
	if (count($simnao) > 0) {
		$sim = $simnao[0];
		$nao = $simnao[1];
	}

	if ($gDevice == "mobile") {
		$cores['Sim'] = "background-color: green; color: white";
		$cores['Não'] = "background-color: red; color: white";
		if ($inverterCores) {
			$cores['Sim']= "background-color: red; color: white";
			$cores['Não']= "background-color: green; color: white";
		}

		if (($c === "on") || ($c === "true") || (intval($c) == 1)) {
			$c = '<span style="'.$cores['Sim'].'">&nbsp;'.$sim.'&nbsp;</span>';
		} else {
			$c = '<span style="'.$cores['Não'].'">&nbsp;'.$nao.'&nbsp;</span>';
		}

	} else {
		$cores['Sim']= "success";
		$cores['Não']= "danger";
		if ($inverterCores) {
			$cores['Sim']= "danger";
			$cores['Não']= "success";
		}

		if (($c === "on") || ($c === "true") || (intval($c) == 1)) {
			if (
				$_REQUEST['gPDF'] == 1
				|| $_REQUEST['gCSV'] == 1
				|| $_REQUEST['gDOC'] == 1
				|| $_REQUEST['gXLS'] == 1
			) {
   				$c = "Sim";
   			} else {
   				$c = '<span class="label label-'.$cores['Sim'].'">'.$sim.'</span>';
   			}

		} elseif ($_REQUEST['gPDF']==1 || $_REQUEST['gCSV']==1 || $_REQUEST['gDOC']==1 || $_REQUEST['gXLS']==1) {
			$c = 'Não';
		} else {
			$c = '<span class="label label-'.$cores['Não'].'">'.$nao.'</span>';

		}

	}

	return $c;
}


/**
 *
 * @param type $script
 */
function javaScript($script): void
{
	if ($script != "") {
		echo "<script language='javascript'>\n";
		echo $script;
		echo "</script>\n";
	}
}


/**
 *
 * @global string $g__js
 * @param type $script
 */
function addJavaScript($script): void
{
	global $g__js;
	$g__js .= $script . "\n";
}


/**
 * Verifica resposta do usuario para o reCAPTCHA (v2) do Google
 * @author André Luiz
 * @version 1.0 17-05-2017 16:46
 * @param string $response resposta enviada no formulario ($_REQUEST['g-recaptcha-response'])
 * @return bool true ou false
 */
function recaptcha_v2_check_answer($response = '')
{
	$sai = false;

	$ch = curl_init();
	// informar URL e outras funções ao CURL
	curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// Dados a ser enviados
	$data = ['secret' => gVar('recaptcha.privateKey'), 'response' => $response];
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	// Acessar a URL e retornar a saída
	$output = curl_exec($ch);

	// liberar
	curl_close($ch);

	// Processando resposta do Google
	$output = json_decode($output);

	if ($output->success === true) {
		return true;
	}

	return $sai;
}

//======================================= funcoes JSON =======================================

/**
 * Versao melhorada do json_Decode para chaves e colchetes aninhados
 * @author	giuliano
 * @version	1.0 12-06-2009 16:21
 * @param mixed $json String JSON
 * @param string $sep Tipo de separador ; ou , (usado no css ou javascript)
 * @param boolean $keepQuote Mantém aspas encontradas dentro da string, ou remove-as
 * @return mixed $array Array com elementos
 */
function jsonDecode(mixed $json, $sep = ";", $keepQuote = true)
{
	if ($json == "") {
		$sai = "";
	} else {
		$css = $json;
		$cnt = 0;
		$arr = [];
		$new = [];
		$items = "";
		if (str_contains((string) $css, "items:")) {
			$i = explode("items:", (string) $json);
			if (str_starts_with(trim($i[1]), "'")) {
				$items = substr(trim($i[1]), 1);
				$ini   = strpos($items, "'");
				$css   = $i[0] . substr($items, $ini + 2) . "}";
				$items = substr($items, 0, $ini);
				$items = str_replace("\"", "'", $items);
				//gLog("\n======: \n$css \n$items");
			}
		}

		$sai = false;
		while(!$sai) {
			if (str_contains((string) $css, "}")) {
				$fim = strpos((string) $css, "}");
				$ini = strrpos(substr((string) $css, 0, $fim), "{");
				$val = substr((string) $css, $ini + 1, $fim - $ini - 1);
				$arr[] = $val;
				$css = substr((string) $css, 0, $ini) . "@" . (count($arr) - 1) . "@" . substr((string) $css, $fim + 1);
			} else {
				$sai = true;
			}
		}

		$ttl = count($arr);
		for ($a = 0; $a < $ttl; $a++) {
			$cssTemp = $arr[$a];
			$sai = false;
			while(!$sai) {
				if (str_contains($cssTemp, "]")) {
					$fim = strpos($cssTemp, "]");
					$ini = strrpos(substr($cssTemp, 0, $fim), "[");
					$val = substr($cssTemp, $ini + 1, $fim - $ini - 1);
					if (!$keepQuote) {
						$val = stripQuote($val);
					}
					$arr[] = $val;
					$cssTemp = substr($cssTemp, 0, $ini - 1) . "@" . (count($arr) - 1) . "@" . substr($cssTemp, $fim + 1);
				} else {
					$sai = true;
				}
			}
			$arr[$a] = $cssTemp;
		}

		$css = trim((string) $css);
  		$counter = count($arr);
		for ($a = 0; $a < $counter; $a++) {
			$elements = str_replace("$sep\n", $sep, trim($arr[$a]));

			if (!str_contains($elements, "\"")) {
				$ex = explode($sep, $elements);
			} else {
				$ex[] = $elements;
			}

			$newEl = [];
			foreach ($ex as $el) {
				if ((!str_contains($el, ":")) && (!str_starts_with($el, "@"))) {
					$newEl[] = "{" . $el . "}";
				} else {
					$fld = explode(":", $el);
					$newEl[trim($fld[0])] = trim($fld[1]);
				}
			}
			$new[] = $newEl;
		}

		$sai = false;
		while(!$sai) {
			$sai = true;
			$counter = count($new);
			for ($a = 0; $a < $counter; $a++) {
				$el = $new[$a];
				$el2 = "";
				$cnt = 0;
				foreach ($el as $key => $value) {
					if ((str_starts_with((string) $key, "@")) && ($value == "")) {
						$el2[$cnt] = $new[intval(substr((string) $key, 1, strlen((string) $key) - 2))];
						$cnt++;
						$sai = false;
					} else {

						if (!$keepQuote) {
							$value = stripQuote($value);
						}

						$el2[$key] = $value;
					}
				}

				$new[$a] = $el2;
			}
		}

		$sai = false;
		while(!$sai) {
			$sai = true;
   			$counter = count($new);
			for ($a = 0; $a < $counter; $a++) {
				$el = $new[$a];
				foreach ($el as $key => $value) {
					if (str_starts_with((string) $value, "@")) {
						$el[$key] = $new[intval(substr((string) $value, 1, strlen((string) $value) - 2))];
						$new[$a] = $el;
						$sai = false;
					}
				}
			}
		}
		$sai = $new[intval(substr($css, 1, strlen($css) - 2))];
	}

	if ($items != "") {
		$sai['items'] = trim($items);
	}

	return ($sai);
}


/**
 * Versao melhorada do json_encode para Array contendo Arrays
 * @author	giuliano
 * @version	1.0 12-06-2009 16:21
 * @param string $mtz Array de elementos
 * @param string $sep Tipo de separador ; ou , (usado no css ou javascript)
 * @param boolean $keepQuote Mantém aspas encontradas dentro da string, ou remove-as
 * @return mixed $json String JSON
 */
function jsonEncode($mtz, $sep = ";", $keepQuote = true): string
{
	$s .= "{";
	$n1 = [];
	foreach ($mtz as $key => $value) {
		if (is_array($value)) {
			//$s.=$key."[ ";
			$n3 = [];
			foreach ($value as $itens) {
				$n  = "{";
				$n2 = [];
				foreach ($itens as $item => $vitem) {
					if (!$keepQuote) {
						if (
							(is_numeric($vitem))
							|| (str_starts_with((string) $vitem, "'"))
							|| (str_starts_with((string) $vitem, '"'))
						) {
							$n2[] = $item . ": " . $vitem;
						} else {
							$n2[] = $item . ": '" . $vitem . "'";
						}
					} else {
						$n2[] = $item . ": " . $vitem;
					}
				}

				$n .= implode($sep, $n2);
				$n .= "}";
				$n3[] = $n;
			}

			$n1[] = "$key: [" . implode($sep, $n3) . "]";
		} elseif (!$keepQuote) {
			if (
				(is_numeric($value))
				|| (str_starts_with((string) $value, "'"))
				|| (str_starts_with((string) $value, '"'))
			) {
				$n1[] = $key . ": " . $value;
			} else {
				$n1[] = $key . ": '" . $value . "'";
			}

		} else {
			$n1[] = $key . ": " . $value;
		}
	}
	$s .= implode($sep, $n1);
	return ($s . "}");
}


/**
 * Junta duas strings json ou arrays resultando apenas um json
 * @author	giuliano
 * @version	1.0 12-06-2009 16:21
 * @param string $json1 Array de elementos ou string JSON
 * @param string $json2 Array de elementos ou string JSON
 * @param string $sepSrc Tipo de separador para origem ; ou , (usado no css ou javascript)
 * @param string $sepDst Tipo de separador para destino ; ou , (usado no css ou javascript)
 * @return mixed $json String JSON
 */
function jsonMerge($json1, $json2, $sepSrc = ";", $sepDst = ";")
{
	$mtz1 = is_array($json1) ? $json1 : jsonDecode($json1, $sepSrc);
	$mtz2 = is_array($json2) ? $json2 : jsonDecode($json2, $sepSrc);
	foreach ($mtz2 as $key => $value) {
		$mtz1[$key] = $value;
	}

	return(jsonEncode($mtz1, $sepDst));
}


/**
 * Remove um elemento de uma string ou matriz JSON
 * @author	giuliano
 * @version	1.0 12-06-2009 16:21
 * @param string $json Array de elementos ou string JSON
 * @param string $el Elemento a ser removido
 * @return mixed $mtz Matriz JSON
 */
function jsonRemove($json, $el)
{
	$mtz1 = is_array($json) ? $json : jsonDecode($json, $sepSrc);
	$mtz2 = "";
	foreach ($mtz1 as $key => $value) {
		if ($key != $el) {
			$mtz2[$key] = $value;
		}
	}
	return($mtz2);
}


/**
 * Versao melhorada do json_encode
 * @author	giuliano
 * @version	1.0 12-06-2009 16:21
 * @param mixed $mtz Descrição da variável
 * @return mixed $str String JSON
 */
function jsEncode(mixed $mtz)
{
	if (!is_array($mtz)) {
		$sai = $mtz;
	} else {
		$associative = count(array_diff(array_keys($mtz), array_keys(array_keys($mtz))));
		if ($associative !== 0) {
			$construct = [];
			foreach ($mtz as $key => $value) {
				//$value=trim($value);
				if (is_numeric($key)) {
					$key = "key_$key";
				}

				//$key = "'".addslashes($key)."'";
				$key = addslashes($key);
				// Format the value:
				if (is_bool($value)) {
					$value = $value ? "true" : "false";
				} elseif (is_array($value)) {
					$value = jsEncode($value);
				} elseif ($key === "validator") {
					//Alterado por Bruno, para corrigir o erro de validação, o botão confirma não estava aparecendo.
					$value = "function () {return " . $value . "(Ext.getCmp(\"@__me\"))}";
				} elseif (is_string($value)) {
					if (($value !== "false") && ($value !== "true")) {
						$value = "'" . addslashes($value) . "'";
					}
				}

				if (trim($key) !== '') {
					$construct[] = "$key: $value";
				}

			}

			$sai = "{ " . implode(", ", $construct) . " }";
		} else {
			$construct = [];
			foreach ($mtz as $value) {
				if (is_array($value)) {
					$value = jsEncode($value);
				} elseif (is_string($value)) {
					$value = "'" . addslashes($value) . "'";
				} elseif (is_bool($value)) {
					$value = $value ? "true" : "false";
				}

				if (trim((string) $value) !== '') {
					$construct[] = $value;
				}
			}

			$sai = "[ " . implode(", ", $construct) . " ]";
		}
	}

	return($sai);
}


/**
 * Versao melhorada do json_decode
 * @author	giuliano
 * @version	1.0 12-06-2009 16:21
 * @param string $json String JSON
 * @param boolean $format Remove \n e \t ?
 * @return mixed $mtz Descrição da variável
 */
function jsDecode($json, $format = false): mixed
{
	$str = "";
	// $el = "";
	$com = false;
	$json = str_replace(";}", "}", $json);
	$json = autoencode($json);
	if ($format) {
		$json = str_replace("\n", "", $json);
		$json = str_replace("\t", "", $json);
	}

	$json = preg_replace('/(^|,)([\\s\\t]*)([^:]*) (([\\s\\t]*)):(([\\s\\t]*))/s', '$1"$3"$4:', trim((string) $json));
	for ($i = 0; $i < strlen((string) $json); $i++) {
		if (!$com) {
			if (($json[$i] === "'")) {
				$com = true;
				$str .= '"';
			} else {
				if ($json[$i] === ":") {
					$str .= '"';
				}

				if ($json[$i] !== "\n" && $json[$i] !== "\t") {
					$str .= $json[$i];
				}

				if (
					(
						($json[$i] === "{")
						|| ($json[$i] === ",")
					)
					&& ($json[$i + 1] !== "{")
				) {
					$i++;
					while ((($json[$i] === " ") || ($json[$i] === "\n") || ($json[$i] === "\t")) && ($i <= strlen((string) $json))) {
						$i++;
					}
					$i--;
					$str .= '"';
				}
			}
		} elseif ($json[$i] === "'") {
			$com = false;
			$str .= '"';
		} else {
			$str .= $json[$i];
		}
	}

	$str = str_replace("\r", "", $str);
	$str = str_replace("\n", "\\n", $str);
	$str = str_replace("\t", "\\t", $str);
	return (json_decode($str, true));
}


/**
 *
 * @param type $json
 * @param type $field
 * @return type
 */
function jsValue($json, $field)
{
	$array = jsDecode($json);
	return ($array[$field]);
}


/**
 *
 * @param type $json
 * @param type $field
 * @param string $value
 * @return type
 */
function jsAdd($json, $field, $value): string
{
	if (!is_numeric($value)) {
		$value = "'" . $value . "'";
	}

	return("{" . $field . ": " . $value . ", " . substr($json, 1));
}


/**
 *
 * @param type $json
 * @param type $field
 * @return type
 */
function jsRemove($json, $field)
{
	$sai = $json;
	$array = jsDecode($json);
	if (array_key_exists($field, $array)) {
		$sai = "";
		foreach ($array as $key => $value) {
			if ($key != $field) {
				$sai[$key] = $value;
			}
		}

		if (!is_array($json)) {
			$sai = jsEncode($sai);
			$sai = mb_convert_encoding($sai, 'ISO-8859-1');
		}

	}

	return ($sai);
}


/**
 *
 * @param type $json
 * @param type $field
 * @param type $value
 * @return type
 */
function jsUpdate($json, $field, $value): string
{
	$sai = $json;
	$array = jsDecode($json);
	$array[$field] = $value;
	if (!is_array($json)) {
		$sai = jsEncode($array);
		$sai = mb_convert_encoding($sai, 'ISO-8859-1');
	}

	return ($sai);
}


/**
 *
 * @param type $set1
 * @param type $set2
 * @return type
 */
function jsMerge($set1 = "", $set2 = "")
{
	$sai = $set1;
	if (trim($set2) !== "") {
		$arr1 = jsDecode($set1);
		$arr2 = jsDecode($set2);
		foreach ($arr2 as $key => $value) {
			$arr1[$key] = $value;
		}

		if (!is_array($set1)) {
			$sai = jsEncode($arr1);
			$sai = autoencode($sai);
		}

	}

	return ($sai);
}

/**
 *
 * @param type $string
 * @return type
 */
function stripQuote($string)
{
	if (substr($string, 0, 1) == "'") {
		$string = substr($string, 1, strlen($string) - 2);
	}

	if (substr($string, 0, 1) == '"') {
		$string = substr($string, 1, strlen($string) - 2);
	}

	return($string);
}

/**
 *
 * @param type $str
 * @return boolean
 */
function check_utf8($str)
{
	$len = strlen($str);
	for ($i = 0; $i < $len; $i++) {
		$c = ord($str[$i]);
		if ($c > 128) {
			if ($c > 247) {
				return false;
			}

			if (($c > 239)) {
				$bytes = 4;
			} elseif ($c > 223) {
				$bytes = 3;
			} elseif ($c > 191) {
				$bytes = 2;
			} else {
				return false;
			}

			if (($i + $bytes) > $len) {
				return false;
			}

			while($bytes > 1) {
				$i++;
				$b = ord($str[$i]);
				if ($b < 128 || $b > 191) {
					return false;
				}
				$bytes--;
			}

		}
	}

	return true;
}


function is_utf8($str): bool
{
    return (bool) preg_match('//u', (string) $str);
}
// end of check_utf8


function autoencode($s)//encode if necessary
{
	//if (!mb_check_encoding($s,'UTF-8'))
	//if (!is_utf8($s))
	//if (mb_detect_encoding($t_word)<>'UTF-8')
	if (!check_utf8($s)) {
     	//if (!(preg_match('//u', $string)))
		return mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
	}

	return $s;
}
//======================================= funcoes para TAGs XHTML ===============================


/**
 * Adiciona um parâmetro e formata uma tag HTML
 * @author	giuliano
 * @version	1.0 17-07-2009 10:26
 * @param string $tag Nome da TAG
 * @param string $param Nome do parametro
 * @param string $value Valor
 * @param string $default Se valor="" então assume $default
 * @return mixed $tag TAG formatada
 */
function tagAdd($tag, $param, $value, $default = ""): string
{
	if (!str_starts_with($tag, "<")) {
		$tag = "<$tag>";
	}

	if (($value == "") && ($default != "")) {
		$value = $default;
	}

	if ($value != "") {
		if (str_starts_with($value, "{")) {
			$value = substr($value, 1, strlen($value) - 2);
		}
		$tag = substr($tag, 0, strlen($tag) - 1) . " $param=\"$value\"" . ">";
	}

	return($tag);
}


/**
 * Fecha uma tag HTML
 * @author	giuliano
 * @version	1.0 17-07-2009 10:26
 * @param string $tag Nome da TAG
 * @return mixed $tag TAG formatada
 */
function tagClose($tag): string
{
	return ("</$tag>");
}

//======================================= funcoes jQuery =======================================

function gEffect($element, $effect = "show"): void
{
	$this->gOut("$(\"#$element\").$effect(\"slow\");");
}


// SQL
function gSQLNow(): string
{
	if (
		(str_starts_with((string) gVar("database.type"), "mysql"))
		|| (str_starts_with((string) gVar("database.engine"), "mysql"))
	) {
		return "NOW()";
	}

	return("GETDATE()");
}

function gSQLLimit($query, $max = 10, $first = 0): string
{
	if (
		(str_starts_with((string) gVar("database.type"), "mysql"))
		|| (str_starts_with((string) gVar("database.engine"), "mysql"))
	) {
		// MySQL
		if ($first > 0) {
			$query .= " LIMIT $first, $max";
		} else {
			$query .= " LIMIT $max";
  		}

	} else {
		// SQLServer
		$query = "SELECT TOP $max " . substr((string) $query, 7);
	}

	return($query);
}


function gSQLConcat($fields): string
{
	if (
		(str_starts_with((string) gVar("database.type"), "mysql"))
		|| (str_starts_with((string) gVar("database.engine"), "mysql"))
	) {
		// MySQL
		return "CONCAT(" . implode(",' ',", $fields) . ") ";
	}

	// SQLServer
	return(implode("+' '+", $fields));
}


function gSQLCase($condition, $true, $false = ""): string
{
	if ($false == "") {
		return "CASE WHEN $condition THEN $true END ";
	}

	return("CASE WHEN $condition THEN $true ELSE $false END ");
}


function gSQLDate($campo): string
{
	if ((str_starts_with((string) gVar("database.type"), "mysql")) || (str_starts_with((string) gVar("database.engine"), "mysql"))) {
		return "DATE($campo)";
	}

	return("replace(convert(CHAR(10), $campo, 102),'.','-')");
}


function gJustNumbers($s): array|string
{
	return(str_replace(',','',str_replace(' ','',str_replace('-','',str_replace('.','',$s)))));
}


function gUcwords($s): string
{
	//$s=ucfirst(ucwords(strtolower($s)));
	$s = str_replace(".", ". ", $s);
	$s = str_replace("  ", " ", $s);
	$s = str_replace("  ", " ", $s);
	$s = str_replace("  ", " ", $s);
	$s = mb_convert_case($s, MB_CASE_TITLE, "UTF-8");
	$s = str_replace(" Da ", " da ", $s);
	$s = str_replace(" De ", " de ", $s);
	$s = str_replace(" Do ", " do ", $s);
	$s = str_replace(" Dos ", " dos ", $s);
	$s = str_replace(" Das ", " das ", $s);
	$s = str_replace("(A)", "(a)", $s);
	$s = str_replace("(À)", "(à)", $s);
	$s = str_replace(" A ", " a ", $s);
	$s = str_replace(" E ", " e ", $s);
	$s = str_replace(" O ", " o ", $s);
	$s = str_replace(" Sa ", " SA ", $s);
	$s = str_replace(" S.a.", ' S/A', $s);
	$s = str_replace(' s/A', ' S/A', $s);
	$s = str_replace(' S/a', ' S/A', $s);
	$s = str_replace(' s\/A', ' S\/A', $s);
	$s = str_replace(' S\/a', ' S\/A', $s);
	$s = str_replace(' S' . chr(47) . 'a', ' S/A', $s);
	//$s=str_replace('Ltda','LTDA',$s);
	$s = str_ireplace('rj', 'RJ', $s);
	$s = str_ireplace('mg', 'MG', $s);
	$s = str_replace('Oab', 'OAB', $s);
	$s = str_replace('Omb', 'OMB', $s);
	$s = str_replace('Crea', 'CREA', $s);
	$s = str_replace('Cremeb', 'CREMEB', $s);
	$s = str_replace('Tst', 'TST', $s);
	$s = str_replace('Trt', 'TRT', $s);
	$s = str_replace('Tre', 'TRE', $s);
	$s = str_replace('TjRJ', 'TJRJ', $s);
	$s = str_replace('Iss', 'ISS', $s);
	$s = str_replace('Icms', 'ICMS', $s);
	$s = str_replace('Ipi', 'IPI', $s);
	$s = str_replace('Cofins', 'COFINS', $s);
	$s = str_replace('Inss', 'INSS', $s);
	$s = str_replace(" E ", " e ", $s);
	$s = str_replace("Ç", "ç", $s);
	$s = str_replace("Ã", "ã", $s);
	$s = str_replace('Cfc', 'CFC', $s);
	return(str_replace('Giusoft', 'GiuSoft', $s));
}


/* Abrevia nome próprio */
function gCutWords($string, $maxWords = 3): string
{
	$string = str_replace("  "," ", $string);
	$w = explode(" ", $string);
	$cnt = 0;
	$feito = 0;
	$contador = count($w);
	while($cnt < $contador) {
		if (
			($feito < $maxWords)
			&& (strtoupper($w[$cnt]) !== "DE")
			&& (strtoupper($w[$cnt]) !== "DO")
			&& (strtoupper($w[$cnt]) !== "DA")
			&& (strtoupper($w[$cnt]) !== "DES")
			&& (strtoupper($w[$cnt]) !== "DOS")
			&& (strtoupper($w[$cnt]) !== "DAS")
			&& (strtoupper($w[$cnt]) !== "E")
			&& (strtoupper($w[$cnt]) !== "AND")
			&& (strtoupper($w[$cnt]) !== "OR")
		) {
			$sai[] = $w[$cnt];
			$feito++;
		}

		$cnt++;
	}

	return(implode(" ", $sai));
}


if (!function_exists("normalize")) {
	function normalize ($string): string
	{
		$table = [
			'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
			'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
			'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
			'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
			'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
			'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', '`' => ' ',"'" => ' ','~' => ' ', '˜'=>' '
		];

		return strtr($string, $table);
	}
}


if (!function_exists("tiracentos")) {
	function tiracentos($t): string
	{
		$t = strtr($t, mb_convert_encoding("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇºª", 'ISO-8859-1'), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCoa");
		return(strtr($t, ("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇºª"), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCoa"));
	}
}


function tiraacentos2($t): ?string
{
	$pa = ["a", "e", "i", "o", "u", "o", "o", "a", "e"];
	$de = ["á", "é", "í", "ó", "ú", "°", "º", "ª", "&"];
	$t  = str_replace($de, $pa, $t);
	$de = ["à", "è", "ì", "ò", "ù"];
	$t  = str_replace($de, $pa, $t);
	$de = ["â", "ê", "î", "ô", "û"];
	$t  = str_replace($de, $pa, $t);
	$t  = str_replace("ã", "a", $t);
	$t  = str_replace("õ", "o", $t);
	$t  = str_replace("ç", "c", $t);
	$t  = str_replace("Ç", "C", $t);
	$t  = autoencode($t);
	$pa = ["A", "E", "I", "O", "U"];
	$de = ["Á", "É", "Í", "Ó", "Ú"];
	$t  = str_replace($de, $pa, $t);
	$de = ["À", "È", "Ì", "Ò", "Ù"];
	$t  = str_replace($de, $pa, $t);
	$de = ["Â", "Ê", "Î", "Ô", "Û"];
	$t  = str_replace($de, $pa, $t);
	$de = ["Ã", "Õ", "Ç"];
	$pa = ["A", "O", "C"];
	$t  = str_replace($de, $pa, $t);
	$de = [""];
	$pa = ["E"];
	$t  = str_replace($de, $pa, $t);
	$t  = iconv('ISO-8859-1', 'ASCII//TRANSLIT//IGNORE', $t);
	return (preg_replace('/[^a-zA-Z0-9- ]/','',$t));
}


// alinha a direita
function padl($txt, $qtd, $char = " "): string
{
	return (str_pad(substr((string) $txt, 0, $qtd), $qtd, $char, STR_PAD_LEFT));
}


// alinha a esquerda (complementa com espacos a direita)
function padr($txt, $qtd, $char = " "): string
{
	return (str_pad(substr((string) $txt, 0, $qtd), $qtd, $char, STR_PAD_RIGHT));
}


function removeRN($string): array|string
{
	return(str_replace("\r", "", str_replace("\n", "", $string)));
}

//======================================= funcoes de upload e download =======================================

/**
 *
 * @param type $parm
 * @param type $filename
 * @return type
 */
function uploadedFileDetails($parm = "", $filename = "arquivo")
{
	//$details = isset($_FILES[$filename]) ? $_FILES[$filename] : FALSE;
	foreach (array_keys($_FILES) as $key) {
		$details = $_FILES[$key] ?? FALSE;
	}

	if ($parm != '') {
		return $details[$parm];
	}
	return($details);
}


/**
 *
 * @param type $textFile
 * @return type
 */
function uploadedFile($textFile = true): array|string
{
	$txt = $sai = '';
	if (!empty($_FILES)) {
     	$gBAR = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? "\\" : '/';
		// Prepara a variável do arquivo
		foreach (array_keys($_FILES) as $key) {
			$arquivo = $_FILES[$key] ?? FALSE;
		}

		//$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
		// Tamanho máximo do arquivo (em bytes)
		$config["tamanho"] = 106883000000;
		$erro = [];
		// Formulário postado... executa as ações
		if ($arquivo['tmp_name'] != '' && $arquivo["size"] > $config["tamanho"]) {
			$erro[] = "Arquivo em tamanho muito grande! Deve ser de no máximo " . $config["tamanho"] . " bytes. Envie outro arquivo";
		}

		if (!is_array($erro)) {
			$pDir = "";
			$arq  = $arquivo["tmp_name"];

			if (file_exists($gDir . $arq)) {
				$txt = file_get_contents($pDir . $arq);
				$txt = $textFile ? autoencode($txt) : base64_encode($txt);
			} else {
				$erro[] = "Arquivo não existe no servidor.";
			}

			@fclose($pont);
			//Apagando a imagem da pasta
			@unlink($pDir . $arq);
		}

		$sai['content'] = $txt;
		$sai['name']    = $arquivo['name'];
		$sai['type']    = $arquivo['type'];
		$sai['size']    = $arquivo['size'];
		$sai['errors']  = implode("<br>", $erro);
	}

	return($sai);
}


function saveUploadedFile($caminho, $novoNome = "", $tiposPermitidos = "", $tamanhoMaximo = 106883000000)
{
	global $gPathFiles;
	$erros = [];
	if ($tiposPermitidos === '') {
		$tiposPermitidos[] = 'pdf';
		$tiposPermitidos[] = 'jpg';
		$tiposPermitidos[] = 'jpeg';
		$tiposPermitidos[] = 'png';
	}

	foreach ($_FILES as $file) {
		if ($file['error'] != 0) {
			if ($file['name'] != '') {
				$erros[] = $file['error'];
			}

		} else {
			$path = $gPathFiles . '/' . $caminho . '/tmp';
			$path_parts = pathinfo($path);
			$caminho = $path_parts['dirname'];
			mkdir($caminho, 0777, true);
			$path_parts = pathinfo((string) $file['name']);
			if (in_array($path_parts['extension'], $tiposPermitidos)) {
				if ($file['size']<=$tamanhoMaximo) {
					$nome = $path_parts['filename'];
					if ($novoNome != '') {
						$nome = $novoNome;
					}

					$nome = $nome.'.'.$path_parts['extension'];
					move_uploaded_file($file['tmp_name'], $caminho. '/' . $nome);
					$erros = $caminho. '/' . $nome;
					gLog(">>>> Salvando arquivo: ".$erros);

				} else {
					$erros[] = "Tamanho do arquivo maior que o permitido";
				}
			} else {
				$erros[] = "Tipo de arquivo não permitido: " .$path_parts['extension']." (Permitidos: ".implode(', ', $tiposPermitidos).")" ;
			}
		}
	}

	return $erros;
}


function renameUploadedFile($caminho, string $nomeAntigo, string $nomeNovo): void
{
	global $gPathFiles;
	$path = $gPathFiles . '/' . $caminho . '/tmp';
	$path_parts = pathinfo($path);
	$caminho = $path_parts['dirname'] ;
	$tentarTipos = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ott', 'txt', 'zip'];
	foreach ($tentarTipos as $tentar) {
		if (file_exists( $caminho .'/'. $nomeAntigo.'.'.$tentar)) {
			rename($caminho. '/' . $nomeAntigo.'.'.$tentar, $caminho . '/' . $nomeNovo.'.'.$tentar);
		}
	}
	gLog(">>>> Renomeando arquivo: ".$nomeAntigo. ' para ' . $nomeNovo);
}


function linkUploadedFile(string $caminho, string $nome = ""): string
{
	global $gPathFiles, $http_files;
	$sai = '';

	$path = $gPathFiles . '/' . $caminho . '/tmp';
	$path_parts = pathinfo($path);
	$caminhoLocal = $path_parts['dirname'];
	$tentarTipos = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ott', 'txt', 'zip'];
	foreach ($tentarTipos as $tentar) {
		if (file_exists($caminhoLocal .'/'. $nome.'.'.$tentar)) {
			$sai = $http_files . '/'. $caminho . '/'. $nome.'.'.$tentar;
			break;
		}
	}

	return($sai);
}


/**
 * Se $i=0 retorna se foi informado algum arquivo no campo de "arquivo de download"
 *
 * Se $i>0 verifica se existe algum arquivo já salvo com este Id
 * @param int $i
 * @return boolean $sai
 */
function uploaded($i = 0)
{
	if ($i > 0) {
		$gIdApp = abs($_SESSION['gApp']);
		$sql = "SELECT id FROM alitem.arquivos WHERE id_aplicativo=$gIdApp AND id_associacao=$i";
		$rs = gQuery($sql);
		$sai = !$rs->EOF;
	} else {
		$sai = true;
		if (empty($_FILES)) {
			$sai = false;
		}

		foreach ($_FILES as $f) {
			if ($f['tmp_name'] == '') {
				$sai = false;
			}
		}

	}

	return($sai);
}

/**
 * Verifica o tipo de arquivo enviado por upload
 *
 * @param string $type Tipo de arquivo passado. Tipos suportados: image, jpg, png
 * @return type
 */
function uploadedType($type)
{
	foreach (array_keys($_FILES) as $key) {
		$arquivo = $_FILES[$key] ?? FALSE;
	}

	return(match ($type) {
		"image" => preg_match("#^image\\/(pjpeg|jpeg|png|gif)\$#mi", (string) $arquivo["type"]),
		"jpg" => preg_match("#^image\\/(pjpeg|jpeg)\$#mi", (string) $arquivo["type"]),
		"png" => preg_match("#^image\\/(png)\$#mi", (string) $arquivo["type"]),
		default => preg_match("^" . $type . "$", (string) $arquivo["type"]),
	});
}


/**
 * Salva arquivo upado no disco e no BD (opcional)
 *
 * Se id=0, não usa o banco de dados
 *
 * @param type $id
 * @param string $name
 * @param int $maxSize
 * @param string $fieldName
 * @return type
 */
function uploadedSave($id, $name = '', $maxSize = 106883000000, $fieldName = 'arquivo')
{
	global $gPathUsrFiles;
	$idArq = 0;
	if (!empty($_FILES)) {
		$ok = true;
		//======================= CARREGANDO ARQUIVO E MOSTRANDO FORMULARIO
		// Prepara a variável do arquivo
		//$arquivo = isset($_FILES[$fieldName]) ? $_FILES[$fieldName] : FALSE;
		foreach (array_keys($_FILES) as $key) {
			$arquivo = $_FILES[$key] ?? FALSE;
		}

		// Tamanho máximo do arquivo (em bytes)
		$config['tamanho'] = $maxSize;

		$erro = [];
		// Formulário postado... executa as ações
		// Verifica tamanho do arquivo
		if ($arquivo && $arquivo['size'] > $config['tamanho']) {
			$erro[] = 'Arquivo em tamanho muito grande! A imagem deve ser de no máximo ' . $config['tamanho'] . ' bytes. Envie outro arquivo';
			$ok = false;
		}

		if (!is_array($erro)) {
			$gIdApp = abs($_SESSION['gApp']);
			$gDir = '';
			$arq  = $arquivo['tmp_name'];
			$base = $_SERVER['DOCUMENT_ROOT'] . '/alitem/files/' . str_pad((string) $_SESSION['usrIdd'], 9, '0', STR_PAD_LEFT);
			$base = str_replace('//', '/', $base);

			if (!is_dir($base)) {
				mkdir($base, 0777, true);
			}

			$id   = intval($id);
			$name = trim(gCleanField($name));
			$nome = trim((string) $arquivo['name']);

			if ($name !== '') {
				$nome = $name;
			}

			$globalSite = gVar("global.site");
			if ($id > 0) {
				$n = explode('.', $nome);
				$nome  = $n[0];
				$agora = date('Y-m-d H:i:s');

				if ($globalSite == 'gJurídico') {
					$sql = "INSERT INTO arquivos (data_inclusao, id_aplicativo, id_associacao, nome, tipo)
							VALUES ('$agora',$gIdApp,$id,'$name','" . $arquivo['type'] . "')";
					$rs  = gQuery($sql);

					$sql = "SELECT id FROM arquivos
							WHERE id_aplicativo = $gIdApp
								AND nome = '$name'
								AND data_inclusao = '$agora'";
					$rs  = gQuery($sql);
				} else {
					$sql = "INSERT INTO alitem.arquivos (data_inclusao, id_aplicativo, id_associacao, nome, tipo)
							VALUES ('$agora',$gIdApp,$id,'$name','" . $arquivo['type'] . "')";
					$rs  = gQuery($sql);

					$sql = "SELECT id FROM alitem.arquivos
							WHERE id_aplicativo = $gIdApp
								AND nome = '$name'
								AND data_inclusao='$agora'";
					$rs = gQuery($sql);
				}

				$idArq   = intval($rs->fields['id']);
				$destino = $base . '/f' . str_pad($gIdApp, 9, '0', STR_PAD_LEFT) . '_' . str_pad($idArq, 9, '0', STR_PAD_LEFT) . '.bin';
			} else {
				$destino_dir = $gPathUsrFiles;
				mkdir($destino_dir, 0777, true);
				$destino = $destino_dir . '/' . $nome;
				if (dirname($destino) != $destino_dir) {
					mkdir(dirname($destino), 0777, true);
				}
			}

			if (file_exists($gDir . $arq)) {
				$ok = move_uploaded_file($gDir . $arq, $destino);
				if ($id > 0) {
					if ($globalSite == 'gJurídico') {
        				if (!$ok) {
   							$sql = "DELETE FROM arquivos WHERE id = $idArq";
   							gQuery($sql);
   						} else {
   							$sql = "UPDATE arquivos SET nome = '$nome',caminho = '$destino' WHERE id = $idArq";
   							gQuery($sql);
   						}
					} elseif (!$ok) {
						$sql = "DELETE FROM alitem.arquivos WHERE id=$idArq";
						gQuery($sql);
					} else {
						$sql = "UPDATE alitem.arquivos SET nome = '$nome',caminho = '$destino' WHERE id = $idArq";
						gQuery($sql);
					}

				}

			} else {
				$erro[] = 'Arquivo não existe no servidor.';
			}

			@fclose($pont);
			//Apagando a imagem da pasta
			@unlink($pDir . $arq);
		}

		$sai['id']     = $idArq;
		$sai['name']   = $nome;
		$sai['type']   = $arquivo['type'];
		$sai['size']   = $arquivo['size'];
		$sai['errors'] = implode('<br>', $erro);
	}

	return($sai);
}


function calculateUploadFileName(string $tableName, string $fieldName, string $id, $type): string|array
{
	global $http_files, $gPathFiles;

	$owner = '';
	if (gVar("global.imagesbydb") == "true") {
		$owner = gVar("database.name")."_";
	}

	$ext  = '.'.substr((string) $type,strpos((string) $type,'/')+1);
	$file = $gPathFiles.'/'.$tableName.'/'.$owner.$fieldName.'/'.$id.$ext;
	$fileLink = $http_files.'/'.$tableName.'/'.$owner.$fieldName.'/'.$id.$ext;
	$file = str_replace('//','/',$file);
	$fileLink = str_replace('//','/',$fileLink);
	$fileLink = str_replace("http:/",'http://',$fileLink);

	if (!file_exists($file)) {
		return "";
	}

	return(str_replace("https:/",'https://',$fileLink));
}

/**
* Upload de arquivos
*
* @param string $k indice do $_FILES para upload
* @param string $rename Novo nome para salvar localmente
* @param string $dest Pasta de destino
* @param string $type Extensao suportada separada por virgula
* @param array $resize Redimensionar imagem
* @param int $max_size Limite para o tamanho do arquivo em bytes
* @return string path arquivo
*/
function fileUpload($k = 0, ?string $rename = '', $dest = '', $type = '', array $resize = [], $max_size = '104857600'): false|string{

	$file = [];
	if (!empty($_FILES[$k]['tmp_name'])) {
		$file = $_FILES[$k];
	}

	if (is_array($k) && !empty($k['tmp_name'])) {
		$file = $k;
	}

	if (!$file) {
		return false;
	}

	// tipo
	$file_ext = strtolower(substr(strrchr(basename((string) $file['name']),"."),1));

	// Array com tipos suportados
	$type = $type ? explode(',',$type) : [];

	$error = $file['error'];

	$valid_file = true;

	// Verificando os tipos suportados
	if ($type && !in_array($file_ext,$type)) {
		$valid_file = false;
	}

	// Verificando tamanho
	if ($file['size'] > $max_size || $error>0){
		$valid_file = false;
	}

	if (
		$file_ext === 'html'
		|| $file_ext === 'htm'
		|| $file_ext === 'php'
		|| $file_ext === 'bin'
	) {
		$valid_file = false;
	}

   	if ($valid_file) {
   		// Redimensiona o arquivo, somente arquivo do tipo imagem
		if (
			stripos('png|jpeg|jpg|gif',$file_ext) !== false
			&& (
				count($resize) == 2
				&& preg_match("^image\/(".$perm.")$", (string) $file["type"])
			)
		) {
			[$w, $h] = getimagesize($file['tmp_name']);
			switch(exif_imagetype($file['tmp_name'])) {
				case 'IMAGETYPE_PNG':
					$image_create = imagecreatefrompng($file['tmp_name']);
					$image_type = 'image/png';
					break;

				case 'IMAGETYPE_GIF':
					$image_create = imagecreatefromgif($file['tmp_name']);
					$image_type = 'image/gif';
					break;

				case 'IMAGETYPE_JPEG':
				default:
					$image_create = imagecreatefromjpeg($file['tmp_name']);
					$image_type = 'image/jpeg';
				break;
			}

			$image_tmp = imagecreatetruecolor($resize[0], $resize[1]);
			imagecopyresampled($image_tmp, $image_create, 0, 0, 0, 0, $resize[0], $resize[1], $w, $h);
			$r = match (exif_imagetype($file['tmp_name'])) {
				'IMAGETYPE_PNG' => imagegif($image_tmp,$file['tmp_name']),
				'IMAGETYPE_GIF' => imagepng($image_tmp,$file['tmp_name']),
				default => imagejpeg($image_tmp,$file['tmp_name']),
			};

			imagedestroy($image_create);
			imagedestroy($image_tmp);
		}

		// Nome arquivo
		if ($rename != '') {
        	$file_name = str_contains($rename,'.') ? $rename : $rename . substr((string) $file['name'],strrpos((string) $file['name'], '.'));
    	} else {
			$file_name = $file['name'];
		}

	   	// Destino do arquivo
		$file_dir = dirname((string) $file['tmp_name']);
		if ($dest) {
			if (file_exists($dest)) {
				$file_dir = $dest;
			} else {
				$file_dir = mkdir($dest, 0777, true)? $dest : $file_dir;
			}

		}

		$file_dir = (str_ends_with($file_dir, '/')) ? $file_dir : $file_dir.'/';

		gLog("==> Salvando arquivo: [".$file_dir.$file_name."]");
		// Move o arquivo para o destino
		if (move_uploaded_file($file['tmp_name'], $file_dir.$file_name)) {
			chmod($file_dir.$file_name, 0644);
			return $file_dir.$file_name;
		}

    	return $file_dir . $file_name;
   }

   return '';
}


/**
* Multiplos Upload de arquivos
*
* @param string $k indice do $_FILES para upload
* @param string $rename Novo nome para salvar localmente
* @param string $dest Pasta de destino
* @param string $type Extensao suportada separada por virgula
* @param array $resize Redimensionar imagem
* @param int $max_size Limite para o tamanho do arquivo em bytes
* @return string path arquivo
*/
function fileMultUpload($files = [], string $rename = '', $dest = '', $type = '', $resize = [], $max_size = '104857600'): array
{
	$filename = [];
	$prepare  = [];

	if ($files) {
		foreach ($files as $name => $data) {
			foreach ($data as $index => $value) {
				$prepare[$index][$name] = $value;
			}

		}
	}

	if ($prepare) {
		foreach ($prepare as $i => $file){
			$filename[] = fileUpload($file, $rename.'_'.$i, $dest, $type, $resize,$max_size);
		}
	}

	return $filename;
}


function uploadedDownload($idAssoc = 0, $id = 0): void
{
	$gIdApp = abs($_SESSION['gApp']);
	if ($idAssoc == 0) {
		$sql = "SELECT * FROM alitem.arquivos
				WHERE id = {$id}
					AND id_aplicativo = {$gIdApp}";
	} else {
		$sql = "SELECT * FROM alitem.arquivos
				WHERE id_associacao = {$idAssoc}
					AND id_aplicativo = {$gIdApp}";
	}

	$rs = gQuery($sql);

	if (!$rs->EOF) {
		$nome = gString2Field($rs->fields['nome']);
		$conteudo = file_get_contents($rs->fields['caminho']);
		$type = $rs->fields['tipo'];

		if (stripos((string) $type, "word") !== false) {
			$nome .= ".doc";
		}

		if (stripos((string) $type, "excel") !== false) {
			$nome .= ".xls";
		}

		if (stripos((string) $type, "pdf") !== false) {
			$nome .= ".pdf";
		}

		download($nome, $conteudo, $type);
		exit;
	}
}


function uploadedDelete($id_associacao): void
{
	$gIdApp = abs($_SESSION['gApp']);
	$sql = "SELECT * FROM alitem.arquivos
			WHERE id_associacao = {$id_associacao}
				AND id_aplicativo = {$gIdApp}";
	$rs = gQuery($sql);

	if (!$rs->EOF) {
		unlink($rs->fields['caminho']);
		$sql = "DELETE FROM alitem.arquivos
				WHERE id_associacao = {$id_associacao}
					AND id_aplicativo = {$gIdApp}";
		gQuery($sql);
	}
}


function uploadedFilePointer(): string
{
	$sai = '';
	if (!empty($_FILES)) {
		//======================= CARREGANDO ARQUIVO E MOSTRANDO FORMULARIO
		$gBAR = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? "\\" : '/';
		// Prepara a variável do arquivo
		$arquivo = $_FILES["arquivo"] ?? false;
		// Tamanho máximo do arquivo (em bytes)
		$config["tamanho"] = 106883000000;
		$erro = [];
		// Formulário postado... executa as ações
		// Verifica tamanho do arquivo
		if ($arquivo && $arquivo["size"] > $config["tamanho"]) {
			$erro[] = "Arquivo em tamanho muito grande! A imagem deve ser de no máximo " . $config["tamanho"] . " bytes. Envie outro arquivo";
		}

		if (!is_array($erro)) {
			//ABRE ARQUIVO
			$pDir = "";
			$arq  = $arquivo["tmp_name"];
			$sai  = file_exists($gDir . $arq) ? $gDir . $arq : false;
		}

	}

	return($sai);
}


function download(string $nome, $conteudo, $type = ''): void
{
	global $g__download;

	$g__download = true;
	header('Content-Description: File Transfer');

	if ($type == '') {
		$type = "application/force-download";
	}

	header('Content-Type: $type');
	header('Content-Disposition: attachment; filename=' . $nome);
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . strlen((string) $conteudo));
	ob_end_clean();
	//flush();
	echo($conteudo);
	exit;
}


function downloadFile(string $nome, $file, $type = ''): void
{
	global $g__download;

	$g__download = true;
	header('Content-Description: File Transfer');

	if ($type == '') {
		$type = "application/force-download";
	}

	header('Content-Type: $type');
	header('Content-Disposition: attachment; filename=' . $nome);
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . filesize($file));
	ob_end_clean();
	//flush();
	readfile($file);
	exit;
}


if (!function_exists('d')) {
	/**
	 * Auxilia na depuracao de codigo, exibindo (dump) o conteudo de variaveis na tela.
	 *
	 * @param  mixed $var variavel a ser depurada
	 * @param string $msg uma mensagem/rotulo para variavel
	 * @param boolean $exit se true interrompe o prosseguimento do codigo
	 *
	 * @author André Luiz
	 * @versoin 1.0 31-05-2016
	 */
	function d(mixed $var = "", $msg = "", $exit = false): void
	{
		$color = str_pad( dechex( mt_rand( 100, 255 ) ), 2, '0', STR_PAD_LEFT) .
		str_pad( dechex( mt_rand( 100, 255 ) ), 2, '0', STR_PAD_LEFT) .
		str_pad( dechex( mt_rand( 100, 255 ) ), 2, '0', STR_PAD_LEFT);
		$size = count(debug_backtrace()) -1;
		$db   = debug_backtrace();

		echo "<br/> <div style='background-color:#{$color};'> <b>{$msg}</b></br><br/>";
		for ($i = 0; $i <= $size; $i++) {
			$who = $db[$i]['class'] !== '' ? "{$db[$i]['class']}->" : '';
			$who .= $db[$i]['function'] !== '' ? "{$db[$i]['function']}()" : '';

			if (is_bool($var)) {
				$var = $var ? 'TRUE' : 'FALSE';
			}

			if (is_numeric($var) && $var === 0) {
				$var = "0";
			}

			$var = $var != '' ? "<pre> ". print_r($var, true) . " </pre>" : '';

			echo "{$db[$i]['file']}:{$db[$i]['line']} {$who}  <br/> <b>$var</b>";
			$var = '';
		}

		echo "<br/> </br> </div>";

		if ($exit) {
			exit;
		}

	}
}


function refresh($seconds, $url): void
{
	header("refresh:$seconds;url=$url");
}


function redirect( string $page , $javascript = false): void
{
	if ($javascript) {
		echo '<script language="JavaScript">window.location="' . $page . '";</script>';
	} else {
		header( "location: $page" );
		exit;
	}
}


function is_client(): bool
{
	return (intval($_SESSION['usrClient']) > 0);
}


function is_developer(): bool
{
	return ($_SESSION['gDeveloper'] == "on") && (($_SESSION['gApp'] < 0) || ($_REQUEST['gIdApp'] < 0) || (str_starts_with((string) $_REQUEST['g'], "-1")));
}


function run(string $command): string|false|null
{
	global $gPath, $gPathUsrFiles;
	$run = $gPath . "res/run/" . $command;
	gLog("===> run: $run");

	return(shell_exec("cd $gPathUsrFiles;" . $run));
}

function gT($t_word, $force = false): string
{

	global $gLngs, $gLang,$gBASE, $usrId;

	$preTxt = "";
	$posTxt = "";
	$t_word = trim((string) $t_word);
	// Não traduz expressões de uma letra, e começadas por número
	if (
		(
			(gVar("global.translate") != "false")
			|| ($force)) && ($t_word !== "")
			&& (strlen($t_word) > 1)
			&& (!is_numeric($t_word[0])
		)
	) {
		if (strrpos($t_word, ">") !== false) {
			// Se tiver HTML na frente, só traduz o final
			$preTxt = substr($t_word,0,strrpos($t_word, ">")+1);
			$t_word = substr($t_word, strrpos($t_word, ">")+1);
			if (str_starts_with($t_word, ' ')) {
				$preTxt .= " ";
			}
		}

		if (str_contains($t_word, ":")) {
			// Se tiver :, só traduz até ele
			$posTxt = substr($t_word, strpos($t_word, ":"));
			$t_word = substr($t_word, 0, strpos($t_word, ":"));
		}

		$t_word = trim($t_word);
		$sai = gLang($t_word);
		if ($sai == "") {
			$aqui = "...";
			$sai  = $t_word;

			//Idiomas para tradução
			$langs = explode(",", str_replace(" ", "", gVar("global.languages")));
			//gLog("gLang: $gLang => ".$langs[0]." ");
			$idiomas = [];
			if (
				(gVar("global.auto_translate") == "true")
				&& (gVar("database.i18n") != "")
				&& (gVar("google.key") != "")
				&& ($gLang == $langs[0])
			) {
				require_once __DIR__ . "/gApi.php";
				$api = new gApi("{key: ".gVar("google.key")."}");

				foreach ($langs as $l) {
					//if ($l=="pt_BR")
					//	$l="pt";
					$idiomas[] = $l;
				}

				$idiomaAtual = $gLang;
				//if ($idiomaAtual=="pt_BR")
				//	$idiomaAtual="pt";
				foreach ($idiomas as $traduzirPara) {
					$text = "";
					$save = true;
					$gLangIndex = gLangIndex($t_word);

					if ($traduzirPara === $langs[0]) {
						$text = $t_word;
					} elseif ((gVar("google.key") != "") && ($gLangIndex != "")) {
						$sql = "SELECT * FROM ".gVar("database.i18n")." WHERE keyword='".$gLangIndex."' and locale='".$traduzirPara."'";
						$rst = dbFastQuery($sql);
						if (
							(!$rst && (trim($t_word) !== ''))
							|| ($rst[0]['text'] == "")
						) {
							$text = $api->googleTranslate(trim($t_word),$idiomaAtual,$traduzirPara);
						} else {
							$save = false;
							$text = $rst[0]['text'];
						}
					}

					if ($save && $gLangIndex != '') {
						$sql = "SELECT * FROM ".gVar("database.i18n")." WHERE keyword='".$gLangIndex."' and locale='".$traduzirPara."' ";
						$rst = dbFastQuery($sql);
						if (!$rst) {
							$source = $_SERVER['PHP_SELF']."?g=".$_REQUEST['g'];
							$sql = "INSERT IGNORE INTO ".gVar("database.i18n")." SET keyword='".$gLangIndex."', locale='".$traduzirPara."', text='".gCleanField($text)."', source='".$source."'";
							$rs  = dbFastQuery($sql);
						}
     				}
				}
			}
		}

	} else {
		$sai = $t_word;
	}
	$sai = $preTxt . $sai . $posTxt;
	//gLog("\n=======>>>>>> traduziu: [".$sai."] pre: [$preTxt] pos: [$posTxt]");
	//gLog("\n=======>>>>>> traduziu: $gLang ($aqui) [".$preTxt.$t_word.$posTxt."] para [".$sai."]");
	return ($sai);
}


/** Lê os dados de um arquivo
 * @author	giuliano
 * @version	1.0 24-07-2009 17:55
 * @param string $filename O arquivo a ser lido
 * @return mixed $sai Conteúdo do arquivo
 */
function gReadFile($filename): string|false
{
	if (file_exists($filename)) {
		return file_get_contents($filename);
	}

 	return("");
}


/* * Lê um arquivo .XML e converte-o em um array de dois elementos
 * sendo o 1ê = nome e 2ê = valor
 * @author Giuliano Nascimento
 * @version 2.0
 * @param string $t_filename O arquivo .XML a ser lido
 * @return array $fields Array contendo os elementos do arquivo .XML
 */
function gReadXML($t_filename)
{
	global $gDebug;

	if ($gDebug > 1) {
		gLog("gStart.php => gReadXML($t_filename)");
	}

	$txt = gReadFile($t_filename);
	$pnt = 1;
	$cntlev = -1;
	$cntfld = 0;
	$maxfld = 500;

	while($pnt < strlen((string) $txt)) {
		$pnt = strpos((string) $txt, "<", $pnt);
		if (($pnt !== false) && ($pnt < strlen((string) $txt))) {
			if (substr((string) $txt, $pnt + 1, 1) !== "?") {
				$pntEnd = strpos((string) $txt, ">", $pnt);
				if ($pntEnd !== false) {
					$Tag = substr((string) $txt, $pnt + 1, $pntEnd - $pnt - 1);
					if (str_starts_with($Tag, '/')) {
						if ($cntlev > -1) {
							$cntlev--;
							array_pop($level);
						} else {
							//*** Erro: Mais fechamento de TAGs do existem aberturas
						}
					} else {
						$pntNext = strpos((string) $txt, "<", $pntEnd + 1);
						if ($pntNext > 0) {
							$TagContent = trim(substr((string) $txt, $pntEnd + 1, $pntNext - $pntEnd - 1));
							$pntClose   = strpos((string) $txt, ">", $pntNext + 1);
							if ($pntClose > 0) {
								$TagClose = substr((string) $txt, $pntNext + 1, $pntClose - $pntNext - 1);
								if ($TagClose === '/' . $Tag) {
									$lvl = "";
									for ($a = 1; $a <= $cntlev; $a++) {
										$lvl = $lvl . $level[$a] . ".";
									}

									$fields[] = [strtolower($lvl . $Tag), $TagContent];
									$cntfld++;
									//===
									if ($gDebug > 1) {
										gLog($lvl . trim($Tag) . " = " . trim($TagContent));
									}

									$pnt = $pntClose;
									if ($cntfld > $maxfld) {
										//*** Erro: Mêximo de campos atingido
										$pnt = strlen((string) $txt) + 1;
									}
								} else {
									$cntlev++;
									$level[] = $Tag;
								}
							} else {
								$erro = true;
								//*** Erro: Na TAG de fechamento, falta um >"
							}
						} else {
							$erro = true;
							//*** Erro: Nêo existe o fechamento da TAG
						}
					}
				} else {
					$erro = true;
					//*** Erro: Iniciou a TAG mas nêo concluiu
				}
			} else {
				// TAG XML
			}
			$pnt++;
		} else {
			$pnt = strlen((string) $txt) + 1;
		}
	}

	return($fields);
}


/* * Obtém informações de fonte RSS
 * @author Giuliano Nascimento
 * @version 1.0
 * @param string $url Endereço da fonte RSS
 * @return string $feed array de notícias
 */
function gRss($url, $titleTag, $linkTag): array
{
	$rss = new DOMDocument();
	$rss->load($url);
	$feed = [];
	foreach ($rss->getElementsByTagName('item') as $node) {
		$item = [
			'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
			'description' => $node->getElementsByTagName('description')->item(0)->nodeValue,
			'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
			'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
		];
		$feed[] = $item;
	}

	return($feed);
}


/* * Criptografa uma string
 * @author Giuliano Nascimento
 * @version 2.0
 * @param string $plainText String a ser criptografada
 * @return string $output String criptografada
 */
function gEncrypt($plainText, $secret_key="giusoft"): string
{
	$output='';
	if ($plainText != '') {
		$encrypt_method = "AES-128-ECB";
		$key = hash('sha256', (string) $secret_key);
		$output = openssl_encrypt($plainText, $encrypt_method, $key);
		$output = pack('H*',$output);
	}

	return($output);
}


/* * Descriptografa uma string
 * @author Giuliano Nascimento
 * @version 2.0
 * @param string $cryptoText String a ser descriptografada
 * @return string $output String descriptografada
 */
function gDecrypt($cryptoText, $secret_key="giusoft"): string|false
{
	$output = '';
	if ($cryptoText != '') {
		$encrypt_method = "AES-128-ECB";
		$key = hash('sha256', (string) $secret_key);
		$output = openssl_decrypt(unpack('H*',(string) $cryptoText), $encrypt_method, $key);
	}

	return($output);
}


function gSalt(array $row): string
{
	return('gsx'.$row['apelido']);
}


function gSessionReplace($t_name)
{
	//$blq=array('_SESSION','_GLOBAL','gId','gIdd','usrId','usrIdd','gIdApp','gIdApps','appDevel','gDeveloper','gFW','usrClient','usrAppId','gAPPName','gSetup');
	foreach ($blq as $rem) {
		$t_name = str_ireplace($rem, "/* comando inválido */", $t_name);
	}

	return ($t_name);
}


/* * Registra uma nova sessão
 * @author Giuliano Nascimento
 * @version 2.0
 * @param string $t_name O nome da sessão
 * @param string $t_value Valor atribuido a variavel de sessão
 *
 */
function gSessionSave($t_name, $t_value): void
{
	$t_name = "g-$t_name";
	//@session_register($t_name);
	//$_SESSION[gSessionReplace($t_name)] = $t_value;
	$_SESSION[$t_name] = $t_value;
}


/* * Recupera o valor de uma variêvel de sessão se ela existir, caso nêo exista, redireciona para pêgina de erro
 * @author Giuliano Nascimento
 * @version 2.0
 * @param string $t_name
 * @return mixed O valor da variêvel de sessão
 */
function gSessionLoad($t_name)
{
	$t_name = "g-$t_name";
	//return $_SESSION[gSessionReplace($t_name)];
	return $_SESSION[$t_name];
}


/**
 * Calcula a distância entre 2 pontos gps (lat e lon)
 * @param type $lat1
 * @param type $lon1
 * @param type $lat2
 * @param type $lon2
 * @param type $unit
 * @return type
 */
function geoDistance($lat1, $lon1, $lat2, $lon2, $unit = "K"): float
{
	$theta = $lon1 - $lon2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	$miles = $dist * 60 * 1.1515;
	$unit = strtoupper($unit);
	if ($unit === "K") {
		return ($miles * 1.609344);
	}

	if ($unit === "N") {
		return ($miles * 0.8684);
	} else {
		return $miles;
	}
}

//======================================= compatibilidade retroativa =======================================

function extjsDo($txt): void {}


function gField($parameter)
{
	return(gField2String($parameter));
}


function gFieldReverse($txt)
{
	return gString2Field($txt);
}


function html2str($txt)
{
	return gHtml2str($txt);
}


function extenso($txt)
{
	return gFloat2String($txt);
}


function gGeraSenha()
{
	return gPasswordSugest();
}


function gGetLatitudeLongitude($address,$region="Brazil")
{

	if (is_array($address)) {
		$cep =  gToNumbers($address['cep']);
		unset($address['cep']);

		$address = implode(',', $address);
	}

	$address = str_replace("."," ",$address);
	$address = str_replace(" ","+",$address);
	$address = htmlentities(urlencode($address));

	$components = '';
	$reg = false;
	if ($region == 'Brazil') {
		$components .= "&components=country:BR";
		$reg = true;
	}

	if ($cep != '') {
		if ($reg) {
			$components .= "|postal_code:$cep";
		} else {
			$components .= "&components=postal_code:$cep";
		}
	}

	$url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}{$components}&key=".gVar('google.key');
	$ch  = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 4);
	$response = curl_exec($ch);
	curl_close($ch);
	//echo $url."<BR><BR><textarea>".$response."</textarea>";exit;
	// gLog(">>>>>>>>>>>>  " .$url);
	// gLog(">>>>>>>>>>>>  " .$response);
	$response_a = json_decode($response);
	$sai['latitude']=$response_a->results[0]->geometry->location->lat;
	$sai['longitude']=$response_a->results[0]->geometry->location->lng;
	return($sai);
}


function geoCheckIP($ip)
{
	return;
	//check, if the provided ip is valid
	// if(!filter_var($ip, FILTER_VALIDATE_IP))
	// {
	// 	throw new InvalidArgumentException("IP is not valid");
	// }

	// //contact ip-server
	// $response=@file_get_contents('http://www.netip.de/search?query='.$ip);
	// if (empty($response))
	// 	throw new InvalidArgumentException("Error contacting Geo-IP-Server");
	// //Array containing all regex-patterns necessary to extract ip-geoinfo from page
	// $patterns=array();
	// $patterns["domain"] = '#Domain: (.*?)&nbsp;#i';
	// $patterns["country"] = '#Country: (.*?)&nbsp;#i';
	// $patterns["state"] = '#State/Region: (.*?)<br#i';
	// $patterns["town"] = '#City: (.*?)<br#i';

	// //Array where results will be stored
	// $ipInfo=array();

	// //check response from ipserver for above patterns
	// foreach ($patterns as $key => $pattern)
	// {
    //          //store the result in array
	// 	$ipInfo[$key] = preg_match($pattern,$response,$value) && !empty($value[1]) ? $value[1] : 'not found';
	// }

	// return $ipInfo;
}


/**
 * Seta defaulttimezone
 *
 * @param int valor fuso horarios em horas
 * @return string date
 * */
function set_defaulttimezone($e = ''): string
{
	$e = intval($e);
	$e = 'Etc/GMT'.($e >= 0? '+' : '').$e;

	if (empty($_SESSION['defaultTimezone'])) {
		$_SESSION['defaultTimezone'] = $e;
		date_default_timezone_set($e);
	}

	return date('d-M-Y H:i:s');
}


function embedYoutube($matches): string
{
	$h = '300';
	$id = trim(substr((string) $matches[0], strpos((string) $matches[0], '=') + 1));
	if (str_contains($id,'&')) {
		$id = substr($id,0,strpos($id,'&'));
	}

	return('<!-- '.$matches[0].' --><iframe xwidth="100%" height="' . $h . '" src="http://www.youtube.com/embed/' . $id . '" frameborder="0" allowfullscreen></iframe>');
}


function embedVimeo($matches): string
{
	$h = '300';
	$id = substr((string) $matches[0], strrpos((string) $matches[0], '/') + 1);
	return('<iframe src="//player.vimeo.com/video/' . $id . '?title=0&amp;byline=0&amp;portrait=0&amp;color=ffffff" xwidth="100%" height="' . $h . '" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>');
}


function gReplaceWikiMacros($content)
{
	global $gLang, $usrId;
	$pre = gVar("database.system");
	if ($pre == '') {
		$pre = 'gfw_';
	}

	// Trata conteúdo
	$faz = true;
	while ($faz) {
		$postxt = '';
		$imagem = false;
		$p = strpos((string) $content, '{{');
		if ($p !== false) {
			$f = strpos((string) $content, '}}', $p+1);
			if ($f !== false) {
				$param=substr((string) $content, $p+2,$f-$p-2);
				if (str_contains($param,' ')) {
					$p2 = strpos($param,' ');
					$param = substr($param,0,$p2).'|'.substr($param,$p2+1);
					$param = explode("|", $param);
					$link = $param[0];
					$txt = $param[1];
					if (
						(str_contains($param[0],'.jpg'))
						|| (str_contains($param[0],'.jpeg'))
						|| (str_contains($param[0],'.png'))
					) {
						$imagem = true;
					} elseif (str_contains($param[0],'http')) {
						$link = $param[0];
						$target = 'target="_new"';
					} elseif (str_contains($param[0],'.php')) {
						$link = $param[0];
					} else {
						$sql = "SELECT * FROM " . $pre . "menus WHERE link = '" . $param[0] . "' AND file <> ''";
						$rst = dbQuery($sql);
						if ($rst) {
							$link=$page.'?g='.$param[0];
						} else {
							$sql = "SELECT * FROM " . $pre . "posts WHERE keyword = '" . $param[0] . "'";
							$rst = dbQuery($sql);
							if ($rst) {
								$link = $page . '?g=index&pp=' . $param[0];
							} else {
								$sql = "SELECT * FROM " . $pre . "pages WHERE keyword='" . $param[0] . "'";
								$rst = dbQuery($sql);
								if ($rst) {
									$link = $page . '?g=open&p=' . $param[0];
								} else {
									$link = $page . '?g=posts&gPage=1&gAction=wiki&keyword=' . $param[0];
									$postxt = ' <span class="label label-primary">' . gT('new') . '</span>';
								}
							}

						}
					}
				} else {
					$txt = $param;
					if (
						(
							str_contains($param,'.jpg'))
							|| (str_contains($param,'.jpeg'))
							|| (str_contains($param,'.png')
						)
					) {
						$imagem = true;
					} elseif (str_contains($param,'http')) {
						$link = $param;
						$target = 'target="_new"';
					} else {
						$sql = "SELECT * FROM " . $pre . "menus WHERE link='" . $param . "' AND file <> ''";
						$rst = dbQuery($sql);
						if ($rst) {
							$link = $page.'?g='.$param;
						} else {
							$sql = "SELECT * FROM " . $pre . "posts WHERE keyword='" . $param . "'";
							$rst = dbQuery($sql);
							if ($rst) {
								$link = $page . '?g=index&pp=' . $param;
							} else {
								$sql = "SELECT * FROM " . $pre . "pages WHERE keyword='" . $param . "'";
								$rst = dbQuery($sql);
								if ($rst) {
									$link=$page.'?g=open&p='.$param;
								} else {
									$link = $page . '?g=posts&gPage=1&gAction=wiki&keyword=' . $param;
									$postxt = ' <span class="label label-primary">' . gT('new') . '</span>';
								}
							}
						}
					}
				}

				if ($imagem) {
					$param = '<img class="img-responsive" src="' . $link . '" tag="' . $txt . '"><br>';
				} elseif ((str_contains($link, 'youtu.')) || (str_contains($link, 'youtube.'))) {
					$param = embedYoutube($link);
				} elseif (str_contains($link, 'vimeo.')) {
					$param = embedVimeo($link);
				} else {
					$param = '<a href="'.$link.'" '.$target.'>'.$txt.'</a>'.$postxt;
				}

				$content = substr((string) $content,0,$p) . $param . substr((string) $content, $f+2);

			} else {
				$faz = false;
			}
		} else {
			$faz = false;
		}
	}
	return($content);
}


/**
 * Formata o valor informado para exibição de acordo com o tipo de dados
 * @author	Giuliano Nascimento
 * @version	4.0 29/07/2014 10:16
 * @param string $type Tipo de dados (text, numeric, integer, date, etc.)
 * @param string $value Valor no formato do usuário
 * @return string $value
 */
function formatDBValueByType($type, $value)
{
	switch ($type) {
		case 'date':
			$value = gDBDate($value);
			break;

		case 'dateTime':
			$value = gDBDateTime($value);
			break;

		case 'checkbox':
			$value = gDBCheck($value);
			break;

		case 'number':
		case 'integer':
			$value = gDBFloat($value);
			break;

		case 'combo':
			if (is_numeric($value) || $value=='') {
				$value = intval($value);
			}
			break;

		case 'password':
			if ($value != SENHA_NAO_MODIFICADA) {
				$value = md5($value);
			}
			break;

		case 'plate':
			$value = strtoupper($value);
			break;
	}

	return($value);
}


/**
 * Formata o valor informado para exibição de acordo com o tipo de dados
 * @author	Giuliano Nascimento
 * @version	4.0 29/07/2014 10:16
 * @param string $type Tipo de dados (text, numeric, integer, date, etc.)
 * @param string $value Valor no formato do banco de dados
 * @return string $value
 */
function formatValueByType($name, $type, $value, &$combos="")
{
	switch ($type) {
		case 'date':
			$value = gDate($value);
			break;

		case 'datetime':
		case 'dateTime':
			$value = strlen($value) > 8 ? gDateTime($value) : substr($value,0,5);
			break;

		case 'combo':
			if (!empty($combos[$name])) {
				$combo = $combos[$name][$value];
				if ($combo != '') {
					$value = $combo;
				}
			}

			break;
		case 'checkbox':
			$value = gCheck($value);
			break;

		case 'number':
			$value = gFloat($value);
			break;

		case 'textarea':
		case 'code':
		case 'memo':
			$value=$value;
			break;

		case 'password':
			$value = SENHA_NAO_MODIFICADA;
			break;
	}

	return($value);
}


/**
 * Mostra interface Wiki com posts armazenados na tabela gfw_posts.
 *
 * @param object $o Se refere ao objeto gOutput, gInput ou gPortal.
 * @param string $json Parâmetros extra como "tags" e "expand"
 * @return string date
 * */
function gPosts(&$o, $json = ''): string|array
{
	global $gLang, $usrId;

	$sai  = '';
	$max  = 10;
	$soLista = true;
	$page = $o->page;
	$page = substr($page,0,strpos($page,'?g='));
	$jarr = cssDecode($json);
	$pre  = gVar("database.system");
	if ($pre == '') {
		$pre = 'gfw_';
	}

	$sql = "SELECT p.*, u.name
			FROM " . $pre . "posts p
			LEFT JOIN " . $pre . "users u ON u.id = p.id_users
			WHERE p.active = 1 AND locale = 'pt_BR' ";
	if (($_REQUEST['pp'] != '') && ($jarr['style'] != 'list')) {
		$soLista = false;
		$sql .= "AND p.keyword = '" . gCleanField($_REQUEST['pp']) . "'";
	}

	if (($_REQUEST['pt'] != '') && ($jarr['style'] != 'list')) {
		$soLista = true;
		$sql .= "AND p.tags like '%" . gCleanField($_REQUEST['pt']) . "%'";
	}

	$search = gCleanField($_REQUEST['gSearch']);
	if ($search != '') {
		$soLista=true;
		$sql.="AND (p.content like '%" . $search . "%' OR p.title LIKE '%".$search."%')";
	}

	if (is_array($jarr) || ($_REQUEST['pp'] == '')) {
		if (isset($jarr['tags'])) {
			$tags=explode(" ", (string) $jarr['tags']);
			$sql .= " AND (p.tags LIKE '%" . implode("%' OR p.tags LIKE '%", $tags) . "%')";
		}

		if ($jarr['expand'] == "true") {
			$soLista = false;
		}
	}

	if (
		(stripos((string) $jarr['tags'],'news') !== false)
		|| (stripos((string) $jarr['tags'],'notícias') !== false)
	) {
		$sql .= 'ORDER BY date_publication DESC ';
	} else {
		$sql .= 'ORDER BY date_modification DESC ';
	}

	$sql .= 'LIMIT '.$max;
	$rs = dbFastQuery($sql);
	if ($rs) {
		if ($soLista) {
			if ($jarr['style']=='list') {
				foreach ($rs as $row) {
					$item = '<a href="'.$page.'?g=index&pp=' . $row['keyword'] . '">' . $row['title'] . '</a><br />';
					$sai .= $item;
				}

			} else {
				$sai .= '<br><ul class="list-group">';
				foreach ($rs as $row) {
					$atags = explode(' ',(string) $row['tags']);
					$tags  = '';
					foreach ($atags as $t) {
						$tags .= '<a href="'.$page.'?g=index&pt='.$t.'"><span class="label label-default">'.$t.'</span></a> ';
					}

					$item = '<a href="'.$page.'?g=index&pp='.$row['keyword'].'">'.$row['title'].'</a><br />';
					$item .= tagMe('small', $row['name'].' - '.gDateTime($row['date_publication'])) .' '. $tags;
					$item .= '<br /><br />';
					$sai  .= '<li class="list-group-item">'.$item.'</li>';
				}

				$sai .= '</ul>';

			}

		} else {
			foreach ($rs as $row) {
				$atags 	= explode(' ',(string) $row['tags']);
				$target = '';
				$tags 	= '';
				//if (($_SESSION['usrAdmin']==1) && ($usrId>0))
				// {
				$btns=$o->button("{title: Editar; size: tiny; hint: Modificar conteúdo do artigo; href: ".$page."?g=posts&gPage=1&gAction=wiki&gId=".$row['id']."}");
				// }
				foreach ($atags as $t) {
					$tags.='<a href="'.$o->page.'&pt='.$t.'"><span class="label label-default">'.$t.'</span></a> ';
				}

				$sai .= tagMe('h2', $row['title']);
				$sai .= $btns.tagMe('small', $row['name'].' - '.gDateTime($row['date_publication'])) .' '.$tags;
				$sai .= '<br /><br />';
				$content = $row['content'];
				if (!str_contains((string) $content,' ')) {
					$content = base64_decode((string) $content);
				}

				$content = gReplaceWikiMacros($content);
				$sai .= $content;
				$sai .= '<hr />';
			}
		}
	} elseif ($_REQUEST['pp'] == '') {
		$sai .= '<br /><br />'.$o->msgError("Nenhum artigo encontrado");
	} else {
		$sai .= '<br /><br />'.$o->msgError("Artigo não encontrado: ".$_REQUEST['pp']);
	}

	if (str_contains($sai,'<pre>')) {
		$o->out('<script src="https://google-code-prettify.googlecode.com/svn/loader/run_prettify.js"></script>', gLOC_POS);
		$o->out('<style>pre.prettyprint {padding: 8px;border: 1px solid #ddd;font-size: 11px}</style>');
		$sai = str_replace('<pre>','<pre class="prettyprint lang-php">', $sai);
	}

	return($sai);
}


/**
* Lista os Arquivos de uma Pasta
* @param string Pasta a ser Listada
* @param string Recursivo, define busca Interna nas Pastas
* @param string Caminho de Pastas Anterior
* @param string Extensoes a serem Listadas
* @param string Listar: Arquivo ou Diretorio
*/
function listFiles(string $dir, $recursive = false, $extension = '', string $prev = '', $listar = 'file'): array
{
	$list_ext = [];
	if (!empty($extension)) {
		$list_ext = explode(',',(string) $extension);
	}

	$files = [];
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) {
			if (
				$file !== "."
				&& $file !== ".."
				&& !is_dir($dir."/".$file)
				&& $listar == 'file'
			) {
				$ext = explode('.',$file);
				if (
					(
						$list_ext
						&& in_array($ext[count($ext)-1], $list_ext)
					) || !$list_ext
				) {
					$files[$prev.$file] = $prev.$file;
				}
			}

			if ($file !== "." && $file !== ".." && is_dir($dir."/".$file)) {
				if ($listar == 'dir') {
					$files[$prev.$file.'/'] = $prev.$file.'/';
				}

				if ($recursive) {
					$temp  = listFiles($dir.'/'.$file, true, $extension, $prev.$file.'/', $listar);
					$files = array_merge($files,$temp);
				}
			}
		}
	}

	return $files;
}


/**
 * Gera um array com um conjunto de cores para serem utilizadas em gráficos
 * @param  string $color Esquema de cores. Pode ser: color, vivid, red, green ou blue
 * @return array  Cores formato HTML
 */
function getGraphColors($color)
{
    return(match ($color) {
        'color' => ['#1395ba', '#c02e1d', '#ecaa38', '#117899', '#d94e1f', '#ebc844', '#0f5b78', '#f16c20', '#a2b86c', '#0d3c55', '#ef8b2c', '#5ca793'],
        'vivid' => ['#427dd7', '#d32030', '#f8ac29', '#9dc62d', '#5796f4', '#a71926', '#dd8e07', '#759422', '#27a3dd', '#b57506', '911621#', '5b731a#'],
        'red' => ['#911621', '#a71926', '#d32030', '#e34352', '#e9707b', '#f09ca4'],
        'green' => ['#5b731a', '#759422', '#9dc62d', '#b6d957', '#c6e17d', '#d7eaa2'],
        'blue' => ['#427dd7', '#5796f4', '#27a3dd', '#5cbae5', '#84caec', '#abdbf2'],
        default => ['#0B62A4', '#3980B5', '#679DC6', '#95BBD7', '#B0CCE1', '#095791', '#095085', '#083E67', '#052C48', '#042135'],
    });
}


/**
 * Verifica se e um e-mail valido
 *
 * @param string e-mail
 * @return boolean
 */
function isMail($email): bool
{
    $er = "/^(([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}){0,1}$/";
    return (bool) preg_match($er, (string) $email);
}


function gToNumbers($string): ?string
{
	return(preg_replace('#[^0-9]#','',strip_tags((string) $string)));
}


function gToUpper($str): string
{
	return(strtoupper(strtr($str ,"áéíóúâêôãõàèìòùç","ÁÉÍÓÚÂÊÔÃÕÀÈÌÒÙÇ")));
}


function gToLower($str): string
{
	return(strtolower(strtr($str ,"ÁÉÍÓÚÂÊÔÃÕÀÈÌÒÙÇ","áéíóúâêôãõàèìòùç")));
}


/**
* Altera em toda a string a ocorrência dos elementos da matriz associativa pa
* passada como parâmetro
* @author  giuliano
* @version 4.0 20-01-2014 18:35
* @param $xml string Texto (HTML, XML, etc.)
* @param $mtz array Matriz associativa
*/
function templateReplace($xml, $mtz)
{
	foreach ($mtz as $key => $value) {
		$xml = str_replace($key, $value, $xml);
	}

	return($xml);
}


/**
* Retorna um código HTML completo para exibição da página usando o template informado
* @author  giuliano
* @global type $gPath
* @global type $gLngs
* @param type $templateFile
* @param string $content Conteúdo em HTML
* @param type $fields
* @return $html string Centro da página HTML (sem cabeçalho nem rodapé)
* @version 4.0 24-01-2014 13:35
*/
function template($templateFile, string $content, $fields = [], $replace = ''): string|array
{
	global $gPath, $gLngs;
	$html='';
	if ($_REQUEST['gPDF'] == '') {
		$mtz = [];
		if (is_array($fields)) {
			foreach ($fields as $key => $value) {
				$mtz['@' . $key] = $value;
			}
		}

		$mtz['@content'] = $content;
		if (is_array($replace)) {
			foreach ($replace as $key => $value) {
				$mtz[$key]=$value;
			}
		}

		if (!str_contains($templateFile, "\n")) { // Se tiver salto de linha, foi fornecido o modelo ao invés do arquivo 
			$arq = $gPath . "static/tpl/" . $templateFile;
			if (file_exists($arq)) {
				$html = file_get_contents($arq);
				foreach ($mtz as $key => $value) {
					$html = str_ireplace($key, $value, $html);
				}
			} else {
				$html = "<h1>Template file not found</h1>" . $content;
			}

		} else {
			$html = $templateFile;
			foreach ($mtz as $key => $value) {
				$html = str_ireplace($key, $value, $html);
			}
		}

		// Translate
		foreach ($gLngs as $key => $value) {
			$html = str_replace("~$key~", $value[0], $html);
		}
	}
	return($html);
}


function ribbonMenu($data): string
{
	global $setup;

	$a = 0;
	$counter = count($data);
	while ($a < $counter) {
		$row = $data[$a];
		if (strlen((string) $data[$a]['sigla']) == 6) {
			// Nova ABA
			$abacnt++;
			$nomeAba=autoencode($data[$a]['nome']);
			// Conteúdo da aba
			$html = "<div style=\"height: 3px\"></div>".'<table class="menugroup" style="display: inline"><tr>';
			//$html="<table class=\"x-tab-strip-text\"><tr>";
			$saiGrupo = false;
			$primeiravezGrupo = true;
			while (!$saiGrupo) {
				if ($primeiravezGrupo) {
					$a++;
				}

				if (($a>=count($data)) || (strlen((string) $data[$a]['sigla']) != 9)) {
					$saiGrupo = true;
				} else {
					$primeiravezGrupo=false;
					$label=autoencode($data[$a]['nome']);
					$html.="<td class=\"celgroup \">";
					//$html.='<div class="" style="display: inline; border: 1px solid black">'.$data[$a]['nome']."<br>";

					$html.="<table class=\"menusubgroup\" style=\"display: inline\"><tr>";
					$saiSubGrupo=false;
					$primeiravezSubGrupo=true;

					while (!$saiSubGrupo) {
						if ($primeiravezSubGrupo) {
							$a++;
						}

						if (($a >= count($data)) || (strlen((string) $data[$a]['sigla']) != 12)) {
							$saiSubGrupo = true;
						} else {
							//$html.=$data[$a]['nome'];
							$primeiravezSubGrupo = false;

							//$html.="<table class=\"menusubgroup\"><tr><td>";
							$saiItens=false;
							$cntgrp=0;
							if ($data[$a]['estilo'] == 0) {
								$cntlnk = 0;
								$maxlnk = 3;
								//$html.="<td class=\"celsubgroup\">".autoencode($data[$a]['nome'])."<br>";
								$html .= "<td class=\"celsubgroup\">";
								$um = true;
								while (!$saiItens) {
									if (!$um) {
										$a++;
									}

									$um=false;
									if (($a>=count($data)) || (strlen((string) $data[$a]['sigla']) != 12)) {
										$saiItens=true;
									} else {
										$cntgrp++;
										$cntlnk++;
										if ($cntlnk > $maxlnk) {
											$cntlnk = 1;
											$html .= "</td><td class=\"celsubgroup\">";
										}

										$desc = autoencode(str_replace("\n","<br>",$data[$a]['descricao']));
										$ajuda = autoencode(str_replace("\n","<br>",$data[$a]['ajuda']));
										$sigla = str_replace(".","_",$data[$a]['sigla']);
										$siglas[] = $sigla;
										$link = fullLink($linkBase."/".$data[$a]['link']);
										$html .= "<a id=\"".$sigla."\" class=\"menu-item\" href=\"$link\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"$ajuda\" target=\"gfwScreen\">".autoencode($data[$a]['nome'])."</a>";
										//$html.="<a id=\"".$sigla."\" class=\"menu-item\" href=\"$link\" target=\"gfwScreen\"><span id=\"".$sigla."\" ext:qwidth=\"200\" ext:qtitle=\"$desc\" ext:qtip=\"$ajuda\" style=\"display: inline\">".autoencode($data[$a]['nome'])."</span></a>";
										//$html.="<p class=\"celitem\">".$data[$a]['nome']."<p>";
									}
								}
							} else {
								//$html.="<td class=\"celsubgroupi\">".autoencode($data[$a]['nome'])."<br>";
								$html.="<td class=\"text-center celsubgroupi\"><div class=\"cel\">";
								$primeiravezItens=true;
								$um=true;
								while (!$saiItens)
								{
									if (!$um) {
										$a++;
									}
									$um = false;
									if (($a >= count($data)) || (strlen((string) $data[$a]['sigla']) != 12)) {
										$saiItens = true;
									} else {
										$cntgrp++;
										if (!$primeiravezItens) {
											$html.="</td><td class=\"text-center celsubgroupi\"><div class=\"cel\">";
										}

										$desc = autoencode(str_replace("\n","<br>",$data[$a]['descricao']));
										$ajuda = autoencode(str_replace("\n","<br>",$data[$a]['ajuda']));
										$sigla = str_replace(".","_",$data[$a]['sigla']);
										$siglas[] = $sigla;
										$link = fullLink($linkBase."/".$data[$a]['link']);

										if (!str_contains((string) $data[$a]['imagem'],".")) {
											$html .= "<a id=\"".$sigla."\" class=\"menu-item\" href=\"$link\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"$ajuda\" target=\"gfwScreen\">".gfw4icon($data[$a]['imagem']);
										} else {
											$html .= "<a id=\"".$sigla."\" class=\"menu-item\" href=\"$link\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"$ajuda\" target=\"gfwScreen\"><img src=\"".$http_img."gfw/img/icons/32x32/".$data[$a]['imagem']."\">";
										}

										$html .= "<span id=\"".$sigla."\" style=\"display: inline\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"$ajuda\"><br>".autoencode($data[$a]['nome'])."</span></a>";

									}

									$primeiravezItens = false;
								}
							}

							$html .= "</div></td>";
							$html .= "<tr><td class=\"text-center menulabel\" colspan=\"$cntgrp\">".$label."</td></tr>";
						}

					}
					$html .= "</tr></table></td><td class=\"celspace\"></td>";
				}

			}

			$html .= "</tr></table>";
			$abas[$nomeAba] = $html;

		} else {
			$a++;
		}
	}

	$css = "
		<style>
			body {height: 100%; overflow:hidden;background-color: #f9f9f9}

			.fa-stack-mini-2x {
				margin-top: 5px;
				margin-bottom: 6px;
				font-size: 1.6em;
			}

			.fa-mini-3x {font-size: 2.75em;}
			.fa-mini { top: .48em; font-size: .6em; padding-left: 16px; }

			.fa-mini-file { top: .3em; font-size: .9em; }

			.fa-top {top: -.5em;}
			.fa-text {
				font-size: .45em;
				font-family: 'Helvetica';
			}
			.fa-cube-text {
				margin-top: -24px;
				margin-left: 6px;
				font-size: .6em;
				font-family: 'Helvetica';
			}
			.fa-cntr-text {
				margin-top: .22em;
				margin-left: .5em;
				font-size: .8em;
				font-family: 'Helvetica';
			}
			.fa-calendar-text {
				margin-top: 6px;
				font-size: .8em;
				font-family: 'Helvetica';
				font-weight: 'bolder';
			}
			.fa-padding {
				padding-top: 6px; padding-bottom: 6px
			}
			.fa-transparent {
				-ms-filter:\"progid:DXImageTransform.Microsoft.Alpha(Opacity=50)\";
				filter: alpha(opacity=50);
				-moz-opacity:0.5;
				-khtml-opacity: 0.5;
				opacity: 0.5;
			}
			.menulink {color: black; text-decoration: none}
			.menugroup {border-collapse: collapse; margin: 0px ; padding: 0px; border: none; background: #cadcf4; width: 100%; height: 100%; text-align: left;}
			.menusubgroup {border-collapse: collapse; margin: 0px; padding: 0px; border: none; vertical-align: bottom; text-align: left; }
			.menulabel {margin: 0px; padding: 3px; border: none; font-size: 12px; background-color: #f0f0f0; color: #a0a0a0}

			.celgroup {padding: 0px; margin: 0px; color: #15428b; font-weight: normal; font-size: 12px; text-align: center; border: 1px solid #f0f0f0; vertical-align: top; text-align: left}
			.celsubgroupi {margin: 0px; border: none; padding: 0px; text-align: center; height: 90px; width: 76px; color: #15428b; font-size: 12px; text-align: center; vertical-align: top}
			.celsubgroup {margin: 0px; border: none; padding: 0px; height: 90px; width: 80px; color: #15428b; font-size: 12px; vertical-align: top}
			.celspace {background: #fff; width: 2px}

			.cel {margin: 0px; padding: 3px; width: 100%; vertical-align: bottom;}
			.menu-item {padding: 3px; border: 1px solid transparent; display:block;white-space:nowrap;text-decoration:none;-moz-outline:0 none;outline:0 none;cursor:pointer;}
			.menu-item:hover, .menu-item:active {padding: 3px; border: 1px solid #4E89D3; background-color: #d9f1ff;text-decoration:none;-moz-outline:0 none;outline:0 none;-moz-border-radius: 4px;border-radius: 4px;}

			.xcelitem {height: 12px; color: black; font-size: 10px; margin: 0px 0px; padding: 1px 1px; border: none; vertical-align: top}
			.xmenu-item:hover {color:#15428b;}

			.xdmenugroup {background: #e0e0e0; width: 100%; height: 100%; margin: 0px 0px; padding: 0px 0px; border: none; text-align: left}
			.xdcelgroup {color: #a0a0a0; font-weight: normal; font-size: 10px; text-align: center; margin: 0px 0px; padding: 0px 0px; border: none; vertical-align: top; text-align: left}
			.xdcelsubgroup {width: 80px; color: #a0a0a0; font-size: 10px; margin: 0px 0px; padding: 0px 0px; border: none; vertical-align: top}
			.xdcelsubgroupi {text-align: center; width: 60px; color: #a0a0a0; font-size: 10px; text-align: center; margin: 0px 0px; padding: 0px 0px; border: none; vertical-align: top}
			.xdcelspace {background: #b0b0b0; width: 1px}

		</style>

		<script type=\"text/javascript\">
			if (self.parent.frames.length >= 2) {
				self.parent.location = document.location;
			}
		</script>

	";

	$html = $css;

	$html .= "\n".'<!-- Menu inicio -->'."\n".'<div role="hidden-print hiddenOnPrint tabpanel">'."\n\n".'<ul class="nav nav-tabs" role="tablist">'."\n";
	$htmlAbas = "\n".'<!-- Abas -->'."\n".'<div class="tab-content">'."\n";
	$class = "active";
	foreach ($abas as $nome=>$aba) {
		if ($nome != '') {
			$nomeMin = strtolower($nome);
			$html .= '    <li role="presentation" class="'.$class.'"><a href="#'.$nomeMin.'" aria-controls="'.$nomeMin.'" role="tab" data-toggle="tab">'.$nome.'</a></li>'."\n";
			$htmlAbas .= '    <div role="tabpanel" class="tab-pane '.$class.'" id="'.$nomeMin.'">'."\n    ".$aba."\n".'   </div>'."\n";
			$class = "tab-pane";
		}
	}

	$htmlAbas .= '</div>'."\n";
	$html .= '</ul>'."\n".$htmlAbas;
	$html .= "\n".'</div>'."\n<!-- Menu fim -->\n\n";
	$html .= '<iframe id="gfwScreen" name="gfwScreen" style="display:block;overflow:hidden;position: absolute; height: 70%; width: 100%;border-top: 1px solid #ddd; border-bottom: none; border-left: none; border-right: none; " src="res/index.php"></iframe>';
	return ($html);
}


function fullLink($link)
{
	global $gPath,$http_base;
	// gFW 3.0
	//$basedir=$this->basedir."/";
	$basedir = $gBASE;
	// gFW 2.0
	$lnk = $link;
	if (intval(basename((string) $link))>0) {
		//$pastaHttp=str_pad($_SESSION['usrIdd'],9,"0",STR_PAD_LEFT);
		$link = $basedir.'go.php?g='.$_REQUEST['gIdApp'].".".str_replace(".php","",basename((string) $link));
	} elseif (str_contains((string) $link,"online")) {
		$link = substr((string) $link,strpos((string) $link,"online"));
	} else {
		if (str_contains((string) $link,"?")) {
			$lnk = substr((string) $link,0,strpos((string) $link,"?"));
		}

		if (!file_exists($gPath.$basedir.$lnk)) {
			$basedir = "online/";
		}

		if (!file_exists($gPath.$basedir.$lnk)) {
			$basedir = $this->basedir . "/";
		}
		$link = file_exists($gPath.$basedir.$lnk) ? $basedir.$link : $link;
		$link = $http_base.$link;
	}
	return ($link);
}

if (!function_exists("isBruteForce"))
{
	function isBruteForce()
	{
		global $docRoot, $gPath;

		$sai = false;
		$lock = false;
		/**
		 * 189.89.157.*   = ITS
		 * 201.157.199.33 = USE TELECOM
		 */
		if (
			$_SERVER['REMOTE_ADDR'] == "201.157.199.33"
			|| str_starts_with((string) $_SERVER['REMOTE_ADDR'], "189.89.157.")
			|| $_SERVER['REMOTE_ADDR'] == "127.0.0.1"
			|| $_SERVER['REMOTE_ADDR'] == "localhost"
		) {
			return false;
		}

		$sql = "SELECT count(id) tries FROM gfw_access WHERE try = 1 AND date > '".date('Y-m-d H:i:s', strtotime("-1 week"))."' AND ip = '".$_SERVER["REMOTE_ADDR"]."'";
		$rst = dbQuery($sql);
		if ($rst[0]['tries'] > 6) {
			// Bloqueia definitivamente
			$sai = true;
			$lock = true;
		} else {
			$sql = "SELECT count(id) tries FROM gfw_access WHERE try = 1 AND date > '".date('Y-m-d H:i:s', strtotime("-10 minutes"))."' AND ip = '".$_SERVER["REMOTE_ADDR"]."'";
			$rst = dbQuery($sql);
			if ($rst[0]['tries'] > 2) {
				gLog("===> Força Bruta: IP bloqueado: ".$_SERVER["REMOTE_ADDR"]." (tentativas: ".$rst[0]['tries'].")");
				$sai = true;
			}
		}

		// Testa pra saber se é um login totalmente novo
		$email = $_REQUEST['email'];
		$senha = gCleanField($_REQUEST['password']);

		$sql = "SELECT
					u.*,
					f.id id_countries,
					f.name country,
					f.iso2,
					f.locale
				FROM gfw_users u
				LEFT JOIN gfw_countries f ON u.id_countries = f.id
				WHERE (u.email = '$email' OR u.nickname = '$email') OR (u.password = '" . md5($senha) . "' OR u.password = '" . $senha . "')";
		$rs = dbFastQuery($sql);
		if (!$rs) {
			gLog("===> Força Bruta: Login e senha não existem: $email / $senha");
			$sai  = true;
			$lock = true;
		}

		if ($lock) {
			gLog("===> Força Bruta: IP bloqueado definitivamente: ".$_SERVER["REMOTE_ADDR"]." (tentativas: ".$rst[0]['tries'].")");
			$sql="INSERT INTO gfw_locked (date,ip,email) values ('".date("Y-m-d H:i:s")."','".$_SERVER["REMOTE_ADDR"]."','$email')";
			dbQuery($sql);
			//$exe=$gPath."pub/code/gs_block_ip ".$_SERVER["REMOTE_ADDR"];
			//gLog("===> ".$exe."\t".shell_exec($exe));
		}

		return($sai);
	}

}

if (!function_exists('mb_str_pad'))
{
	function mb_str_pad ($input, $pad_length, $pad_string, $pad_style, $encoding="UTF-8"): string
	{
	   return str_pad((string) $input, strlen((string) $input)-mb_strlen((string) $input,$encoding)+$pad_length, $pad_string, $pad_style);
	}
}

/**
 * Retorna o timezone apropriado para cada estado do Brasil
 * @param string $uf
 * @return string $timezone
 */
function timeZoneBR($uf = 'DF'): string
{
	return 'America/Bahia';
	// $sai = '';
	// switch (strtoupper($uf))
	// {
	// 	case 'AC':
	// 		$sai ='America/Rio_branco';
	// 		break;
	// 	case 'AL':
	// 		$sai = 'America/Maceio';
	// 		break;
	// 	case 'AP':
	// 		$sai = 'America/Belem';
	// 		break;
	// 	case 'AM':
	// 		$sai = 'America/Manaus';
	// 		break;
	// 	case 'BA':
	// 		$sai = 'America/Bahia';
	// 		break;
	// 	case 'CE':
	// 		$sai = 'America/Fortaleza';
	// 		break;
	// 	case 'ES':
	// 		$sai = 'America/Sao_Paulo';
	// 		break;
	// 	case 'GO':
	// 		$sai = 'America/Sao_Paulo';
	// 		break;
	// 	case 'MA':
	// 		$sai = 'America/Fortaleza';
	// 		break;
	// 	case 'MT':
	// 		$sai = 'America/Cuiaba';
	// 		break;
	// 	case 'MS':
	// 		$sai = 'America/Campo_Grande';
	// 		break;
	// 	case 'MG':
	// 		$sai = 'America/Sao_Paulo';
	// 		break;
	// 	case 'PR':
	// 		$sai = 'America/Sao_Paulo';
	// 		break;
	// 	case 'PB':
	// 		$sai = 'America/Fortaleza';
	// 		break;
	// 	case 'PA':
	// 		$sai = 'America/Belem';
	// 		break;
	// 	case 'PE':
	// 		$sai = 'America/Recife';
	// 		break;
	// 	case 'PI':
	// 		$sai = 'America/Fortaleza';
	// 		break;
	// 	case 'RJ':
	// 		$sai = 'America/Sao_Paulo';
	// 		break;
	// 	case 'RN':
	// 		$sai = 'America/Fortaleza';
	// 		break;
	// 	case 'RS':
	// 		$sai = 'America/Sao_Paulo';
	// 		break;
	// 	case 'RO':
	// 		$sai = 'America/Porto_Velho';
	// 		break;
	// 	case 'RR':
	// 		$sai = 'America/Boa_Vista';
	// 		break;
	// 	case 'SC':
	// 		$sai = 'America/Sao_Paulo';
	// 		break;
	// 	case 'SE':
	// 		$sai = 'America/Maceio';
	// 		break;
	// 	case 'SP':
	// 		$sai = 'America/Sao_Paulo';
	// 		break;
	// 	case 'TO':
	// 		$sai = 'America/Araguaia';
	// 		break;
	// 	case 'DF':
	// 	default:
	// 		$sai = 'America/Sao_Paulo';
	// 		break;
	// }
	// return $sai;
}


/**
 * Transforma uma string JSON (query, sp ou elementos) em uma query
 * @param type $par
 * @return type
 */
function jcombo2query($par)
{
	global $sp;

	if (stripos($par, "SELECT") === false) {
		if ($_SESSION['gFW4'] != '') { ### ISSO AQUI MUDA NO GFW5???
			$p = $par;
			$par = sp($par);
			if ($par == '') {
				$par = "SELECT * FROM $p";
			}
		} elseif ($sp[$par] != '') {
			$par = $sp[$par];
		} else {
			$par = "SELECT * FROM $par";
		}
	}

	return ($par);
}


/**
 * Transforma uma string JSON (query, sp ou elementos) em array
 * arrayJson = true  => retorna em formato JSON
 * 					false => retorna em array do PHP
 * @param type $par
 * @param type $limit
 * @param type $filter
 * @param type $arrayJson
 * @return type
 */
function jcombo2array($par, bool $limit = true, string $filter = '', bool $arrayJson = false): array
{
    global $sp, $dbConn;

    $dados = [];
    $displayField = 'id';
    $flds  = ["'id'", "'text'"];
    $aux   = str_replace("'", '"', $par);

    if (
        is_string($aux)
        && is_array(json_decode($aux, true))
        && (json_last_error() === JSON_ERROR_NONE)
    ) {
        $par = json_decode($aux, true);
    }

    if (is_array($par)) {
        // Parâmetro é um Array
        if ($par) {
            $e = strtr($par[0], '{}', '[]');
        } else {
            $displayField = 'text';
            foreach ($par as $key => $value) {

                if (
                    ($filter === '')
                    || ($filter === (string) $key)
                    || ($filter === (string) $value)
                ) {
                    $value = str_replace("'", "'", (string) $value);
                    if (!$arrayJson) {
                        $dados[$key] = $value;
                    } else {
                        $dados[] = "['$key','$value']";
                    }
                }
            }
        }

    } elseif (
        (str_contains($par, ','))
        && (strtoupper(substr($par, 0, 6)) !== 'SELECT')
        && (substr($par, 0, 1) !== '[')
    ) {
        // Parâmetro é uma lista separada por vírgulas
        if (str_contains($par, ',')) {
            $items = explode(',', $par);
        } else {
            $items = explode(';', $par);
        }

        foreach ($items as $item) {
            if (($filter === '') || ($filter === $item)) {
                $item = str_replace("'", "'", trim($item));
                if (!$arrayJson) {
                    $dados[$item] = $item;
                } else {
                    $dados[] = "['$item','$item']";
                }
            }
        }
    } else {
        if ((substr($par, 0, 1) === '{') || (substr($par, 0, 1) === '[')) {
            // Parâmetro é uma lista em formato JSON
            $par = str_replace("'", '', $par);
            $par = str_replace('[{', '{', $par);
            $par = str_replace('}]', '}', $par);
            $par = str_replace('}, {', '},{', $par);
            $mtz = explode('},{', $par);

            foreach ($mtz as $value) {
                $el = explode(',', $value);
                $el[0] = trim($el[0]);
                $el[1] = trim(str_replace("'", "'", $el[1]));

                if (
					($filter === '')
					|| ($filter === $el[0])
					|| ($filter === $el[1])
				) {
                    if (!$arrayJson) {
                        $dados[$el[0]] = $el[1];
                    } else {
                        $dados[] = "['" . $el[0] . "','" . $el[1] . "']";
                    }
                }
            }
        } else {
            // Parâmetro é uma palavra que será transformada em Query
            $par = jcombo2query($par);

            if ($filter !== '') {
                // Buscando nome ou alias da tabela...
                $parTmp = str_replace('FROM ', 'from ', $par);
                $parTmp = str_replace("\n", ' ', $parTmp);
                $parTmp = str_replace("\t", '', $parTmp);
                $parTmp = explode('from ', $parTmp);
                $parTmp = explode(' ', $parTmp[1]);
                $tab = $parTmp[0];
                $ttab = strtolower($parTmp[1]);

                if (strtolower($parTmp[1]) === 'as') {
                    $tab = $parTmp[2];
                } elseif (
                    ($ttab !== '')
                    && ($ttab !== 'inner')
                    && ($ttab !== 'left')
                    && ($ttab !== 'right')
                    && ($ttab !== 'outer')
                    && ($ttab !== 'where')
                    && ($ttab !== 'order')
                    && ($ttab !== 'group')
                    && ($ttab !== 'limit')
                ) {
                    $tab = $parTmp[1];
                }

                // Acrescentando "where id=filtro..."
                $ini = strlen($par);
                $where = ' where ';
                $and = '';

                if (str_contains(strtolower($par), 'where ')) {
                    $and = ' and ';
                    $where = '';
                    $ini = strpos(strtolower($par), 'where ') + 6;
                } elseif (str_contains(strtolower($par), 'order by ')) {
                    $ini = strpos(strtolower($par), 'order by ');
                }

                $id = substr(str_ireplace('distinct ', '', $par), 7);

                if (strpos($id, ' ') < strpos($id, ',')) {
                    $id = substr($id, 0, strpos($id, ' '));
                } else {
                    $id = substr($id, 0, strpos($id, ','));
                }

                if (str_contains($id, '.')) {
                    $m = explode('.', $id);
                    $tab = $m[0];
                    $id = $m[1];
                }

                if (trim($id) === '') {
                    $id = 'id';
                }

                $par = substr($par, 0, $ini) . $where . "$tab.$id='$filter' " . $and . substr($par, $ini);
            }

            if ($dbConn) {
                $rst = dbFastQuery($par);

                if (!$limit) {
                    $ttlFields = count($rst[0]) / 2;
                    $iddPos = -1;
                    unset($flds);

                    foreach ($row as $key => $value) {
                        if ($key !== 'idd') {
                            $flds[] = "'" . $key . "'";
                            if (($displayField === 'id')) {
                                $displayField = $key;
                            }
                        }
                    }

                    foreach ($rst as $row) {
                        $els = [];
                        $a = -1;

                        foreach ($row as $key => $value) {
                            if (!is_numeric($key)) {
                                $a++;
                                if ($key !== 'idd') {
                                    if ($a < 1) {
                                        $els[] = "'" . trim(str_replace("'", '.', (string) $value)) . "'";
                                    } elseif ($a === 1) {
                                        $els[] = "'" . str_replace("'", "'", (string) $value) . "'";
                                    } else {
                                        if (trim(substr($els[1], 0, strlen($els[1]) - 1)) !== '') {
                                            $els[1] = str_replace("'<br>", "'", "'" . substr($els[1], 1, strlen($els[1]) - 2) . '<br>' . str_replace("'", "'", (string) $value) . "'");
                                        } else {
                                            $els[1] = "'" . str_replace("'", "'", (string) $value) . "'";
                                        }
                                    }
                                }

                                if (!$arrayJson) {
                                    $dados[str_replace("'", '', $els[0])] = str_replace("'", '', $els[1]);
                                } else {
                                    $dados[] = '[' . implode(',', $els) . ']';
                                }
                            }
                        }
                    }
                } else {
                    $ttlFields = count($rst[0]) / 2;
                    $iddPos = -1;
                    unset($flds);

                    foreach ($row as $key => $value) {
                        if ($key !== 'idd') {
                            $flds[] = "'" . $key . "'";
                            if (($displayField === 'id')) {
                                $displayField = $key;
                            }
                        }
                    }

                    foreach ($rst as $row) {
                        $els = [];
                        $cnt = 0;

                        foreach ($row as $key => $value) {
                            if (!is_numeric($key)) {
                                if ($key !== 'idd') {
                                    if (!is_array($els)) {
                                        $els[] = "'" . trim(str_replace("'", '.', (string) $value)) . "'";
                                    } else {
                                        $els[1] = $els[1] . trim(str_replace("'", '.', (string) $value)) . ' ';
                                    }
                                }
                            }
                        }

                        $els[1] = "'" . $els[1] . "'";

                        if (!$arrayJson) {
                            $dados[str_replace("'", '', $els[0])] = str_replace("'", '', $els[1]);
                        } else {
                            $dados[] = '[' . implode(',', $els) . ']';
                        }
                    }
                }
            } else {
                $rst = gDB::run($par, 1);

                if (!$limit) {
                    $ttlFields = $rst->FieldCount();
                    $iddPos = -1;
                    unset($flds);

                    for ($g = 0; $g < $ttlFields; $g++) {
                        $fld = $rst->FetchField($g);

                        if ($fld->name !== 'idd') {
                            $flds[] = "'" . $fld->name . "'";

                            if (($displayField === 'id')) {
                                $displayField = $fld->name;
                            }

                        } else {
                            $iddPos = $g;
                        }

                    }

                    while (!$rst->EOF) {
                        $els = [];

                        for ($a = 0; $a < $ttlFields; $a++) {

                            if ($a !== $iddPos) {

								if ($a < 1) {
                                    $els[] = "'" . trim(str_replace("'", '.', (string) $rst->fields[$a])) . "'";
                                } elseif ($a === 1) {
                                    $els[] = "'" . str_replace("'", "'", (string) $rst->fields[$a]) . "'";
                                } else {

                                    if (trim(substr($els[1], 0, strlen($els[1]) - 1)) !== '') {
                                        $els[1] = str_replace("'<br>", "'", "'" . substr($els[1], 1, strlen($els[1]) - 2) . '<br>' . str_replace("'", "'", (string) $rst->fields[$a]) . "'");
                                    } else {
                                        $els[1] = "'" . str_replace("'", "'", (string) $rst->fields[$a]) . "'";
                                    }

								}

							}

						}

                        if (!$arrayJson) {
                            $dados[str_replace("'", '', $els[0])] = str_replace("'", '', $els[1]);
                        } else {
                            $dados[] = '[' . implode(',', $els) . ']';
                        }

                        $rst->MoveNext();
                    }

                } else {
                    $ttlFields = $rst->FieldCount();
                    $iddPos = -1;
                    unset($flds);

                    for ($g = 0; $g < $ttlFields; $g++) {
                        $fld = $rst->FetchField($g);

                        if ($fld->name !== 'idd') {
                            $flds[] = "'" . $fld->name . "'";

							if (($displayField === 'id')) {
                                $displayField = $fld->name;
                            }

						} else {
                            $iddPos = $g;
                        }

                    }

                    while (!$rst->EOF) {
                        $els = [];
                        $cnt = 0;

                        for ($a = 0; $a < $ttlFields; $a++) {
                            if ($a !== $iddPos) {
                                if (!is_array($els)) {
                                    $els[] = "'" . trim(str_replace("'", '.', (string) $rst->fields[$a])) . "'";
                                } else {
                                    $els[1] = $els[1] . trim(str_replace("'", '.', (string) $rst->fields[$a])) . ' ';
                                }
                            }
                        }

                        $els[1] = "'" . $els[1] . "'";

                        if (!$arrayJson) {
                            $dados[str_replace("'", '', $els[0])] = str_replace("'", '', $els[1]);
                        } else {
                            $dados[] = '[' . implode(',', $els) . ']';
                        }

                        $rst->MoveNext();
                    }
                }
            }
        }
    }

    if ((!$limit) && ($arrayJson)) {
        $ddos = autoencode(implode(',', $dados));
        unset($dados);
        $dados[] = $displayField;
        $dados[] = 'fields:[' . implode(',', $flds) . '],data:[$ddos]';
    }

    return $dados;
}


/**
 * Formata um texto em markdown
 * @param  String $texto - String original
 * @return String          String em formato HTML
 */
function gMarkdown($text): array|string
{
	global $gPathDefault;
	require_once $gPathDefault."lib/parsedown-master/Parsedown.php";
	require_once $gPathDefault."lib/parsedown-master/ParsedownExtra.php";
	$Parsedown = new ParsedownExtra();
	$text = $Parsedown->text($text);
	return(str_replace("img src", "img class=\"img img-rounded img-responsive\" src", $text));
}


/**
 * Remove acentos de uma string
 * @param  String $texto - String a ser limpa
 * @return String        String limpa
 */
function limpaString($texto): ?string
{
    $aFind = [
		'&', 'á', 'à', 'ã', 'â', 'é', 'ê',
        'í', 'ó', 'ô', 'õ', 'ú', 'ü', 'ç', 'Á', 'À', 'Ã', 'Â',
        'É', 'Ê', 'Í', 'Ó', 'Ô', 'Õ', 'Ú', 'Ü', 'Ç'
	];

    $aSubs = [
		'e', 'a', 'a', 'a', 'a', 'e', 'e',
        'i', 'o', 'o', 'o', 'u', 'u', 'c', 'A', 'A', 'A', 'A',
        'E', 'E', 'I', 'O', 'O', 'O', 'U', 'U', 'C'
	];

	$novoTexto = str_replace($aFind, $aSubs, $texto);

    return preg_replace('/[`^~\'"]/', null, iconv('UTF-8', 'ASCII//TRANSLIT', $novoTexto));

    //$novoTexto = preg_replace("/[^a-zA-Z0-9 @,-.;:\/_]/", "", $novoTexto);
    //return $novoTexto;
}

/**
* Gera um PDF a partir de um texto em ZPL (Impressora Zebra)
*
* @param string $zpl Comandos ZPL
* @return string PDF
*/
function zpl2pdf($zpl, string $tamanho = "4x5"): bool|string
{
	$curl = curl_init();
	// adjust print density (8dpmm), label width (4 inches), label height (6 inches), and label index (0) as necessary
	//$url = "http://api.labelary.com/v1/printers/12dpmm/labels/3.5x1.5/0/" .str_replace("\n","",$zpl);

	curl_setopt($curl, CURLOPT_URL, "http://api.labelary.com/v1/printers/8dpmm/labels/".$tamanho."/0/");
	curl_setopt($curl, CURLOPT_POST, TRUE);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $zpl);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded", "Accept: application/pdf"]); // omit this line to get PNG images back
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($curl, CURLOPT_TIMEOUT, 60);
	$result = curl_exec($curl);

	if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200) {
		$result="";
	}

	//     $file = fopen("/tmp/etiquetas.pdf", "w"); // change file name for PNG images
	//     fwrite($file, $result);
	//     fclose($file);
	// } else {
	//     gD("Error: $result");
	// }
	curl_close($curl);
	return($result);
}


function getOS(): string
{

	$user_agent = $_SERVER['HTTP_USER_AGENT'];

	$os_platform =   "Bilinmeyen İşletim Sistemi";
	$os_array = [
		'/windows nt 10/i'      =>  'Windows',
		'/windows nt 6.3/i'     =>  'Windows',
		'/windows nt 6.2/i'     =>  'Windows',
		'/windows nt 6.1/i'     =>  'Windows',
		'/windows nt 6.0/i'     =>  'Windows',
		'/windows nt 5.2/i'     =>  'Windows',
		'/windows nt 5.1/i'     =>  'Windows',
		'/windows xp/i'         =>  'Windows',
		'/windows nt 5.0/i'     =>  'Windows',
		'/windows me/i'         =>  'Windows',
		'/win98/i'              =>  'Windows',
		'/win95/i'              =>  'Windows',
		'/win16/i'              =>  'Windows',
		'/UP.Browser/i'         =>  'Windows CE',
		'/macintosh|mac os x/i' =>  'Mac OS',
		'/mac_powerpc/i'        =>  'Mac OS',
		'/linux/i'              =>  'Linux',
		'/ubuntu/i'             =>  'Linux Ubuntu',
		'/fedora/i'             =>  'Linux Fedora',
		'/centos/i'             =>  'Linux CentOS',
		'/debian/i'             =>  'Linux Debian',
		'/suse/i'               =>  'Linux SUSE',
		'/red hat/i'            =>  'Linux RedHat',
		'/kubuntu/i'            =>  'Linux Kubuntu',
		'/iphone/i'             =>  'iPhone',
		'/ipod/i'               =>  'iPod',
		'/ipad/i'               =>  'iPad',
		'/android/i'            =>  'Android',
		'/blackberry/i'         =>  'BlackBerry',
		'/webos/i'              =>  'Mobile'
	];

	foreach ($os_array as $regex => $value) {
		if (preg_match($regex, (string) $user_agent)) {
			$os_platform = $value;
		}
	}
	return $os_platform;
}


function getOSVersion(): string
{

	$user_agent = $_SERVER['HTTP_USER_AGENT'];

	$os_platform =   "Bilinmeyen İşletim Sistemi";
	$os_array = [
		'/windows nt 10/i'      =>  '10',
		'/windows nt 6.3/i'     =>  '8.1',
		'/windows nt 6.2/i'     =>  '8',
		'/windows nt 6.1/i'     =>  '7',
		'/windows nt 6.0/i'     =>  'Vista',
		'/windows nt 5.2/i'     =>  'Server 2003/XP x64',
		'/windows nt 5.1/i'     =>  'XP',
		'/windows xp/i'         =>  'XP',
		'/windows nt 5.0/i'     =>  '2000',
		'/windows me/i'         =>  'ME',
		'/win98/i'              =>  '98',
		'/win95/i'              =>  '95',
		'/win16/i'              =>  '3.11',
		'/UP.Browser/i'         =>  '?',
		'/macintosh|mac os x/i' =>  'X',
		'/mac_powerpc/i'        =>  '9',
		'/linux/i'              =>  '?',
		'/ubuntu/i'             =>  '?',
		'/fedora/i'             =>  '?',
		'/centos/i'             =>  '?',
		'/debian/i'             =>  '?',
		'/suse/i'               =>  '?',
		'/red hat/i'            =>  '?',
		'/kubuntu/i'            =>  '?',
		'/iphone/i'             =>  '?',
		'/ipod/i'               =>  '?',
		'/ipad/i'               =>  '?',
		'/android/i'            =>  '?',
		'/blackberry/i'         =>  '?',
		'/webos/i'              =>  '?'
	];

	foreach ($os_array as $regex => $value) {
		if (preg_match($regex, (string) $user_agent)) {
			$os_platform = $value;
		}
	}
	return $os_platform;
}


/**
 * Kullanicinin kullandigi internet tarayici bilgisini alir.
 *
 * @since 2.0
 */
function getBrowser(): string
{
	$user_agent = $_SERVER['HTTP_USER_AGENT'];

	$browser        = "Bilinmeyen Tarayıcı";
	$browser_array  = [
		'/msie|trident/i'       =>  'Internet Explorer',
		'/firefox/i'    =>  'Firefox',
		'/safari/i'     =>  'Safari',
		'/chrome/i'     =>  'Chrome',
		'/edge/i'       =>  'Edge',
		'/opera/i'      =>  'Opera',
		'/netscape/i'   =>  'Netscape',
		'/maxthon/i'    =>  'Maxthon',
		'/konqueror/i'  =>  'Konqueror',
		'/mobile/i'     =>  'Handheld Browser'
	];

	foreach ($browser_array as $regex => $value) {
		if (preg_match( $regex, (string) $user_agent)) {
			$browser = $value;
		}
	}

	return $browser;
}


function minimizeJavascript($javascript): array|string
{
	//return preg_replace(array("/\s+\n/", "/\n\s+/", "/ +/"), array("\n", "\n ", " "), $javascript);
	$sai = preg_replace(["/\s+\n/", "/\n\s+/", "/ +/"], ["\n", "\n ", " "], (string) $javascript);
	$sai = str_replace(",\n ",", ", $sai);

	$sai = str_replace(")\n {","){", $sai);
	$sai = str_replace("){\n ","){", $sai);

	$sai = str_replace(")\n }",")}", $sai);
	$sai = str_replace(" { ","{", $sai);
	$sai = str_replace(" } ","}", $sai);

	$sai = str_replace(" || ","||", $sai);
	$sai = str_replace(" && ","&&", $sai);
	$sai = str_replace("=false","=!1", $sai);
	$sai = str_replace("=true","=!0", $sai);
	$sai = str_replace("= false","=!1", $sai);

	// $sai = str_replace(";\n }",";}", $sai);
	// $sai = str_replace("});\n ","});", $sai);
	// $sai = str_replace(";\n","; ", $sai);
	// $sai = str_replace(";\n ","; ", $sai);

	return(str_replace("= true","=!0", $sai));
}

