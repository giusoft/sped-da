<?
/*
------------------------------------------------------------------------
gFW - WEB Development Framework
------------------------------------------------------------------------
Copyleft (l) 2004  GiuSoft Tecnologia/Brazil

Licensed under GPL: www.fsf.org for further details

Site:           http://www.giusoft.com.br/kings
Description:    An abstract layer of oriented object programming code
Started in:     January, 2004
Started Author: Giuliano Nascimento & GiuSoft Team (giusoft@hotmail.com)
------------------------------------------------------------------------
*/

define(gQ_SELECT,1);
define(gQ_INSERT,2);
define(gQ_UPDATE,4);
define(gQ_DELETE,8);

// Mensagens de erro:

define(gE_SYNTAX,'Erro de sintaxe');
define(gE_DATABASE,'Erro no banco de dados');
define(gE_DATABASEQUERY,'Erro na expressêo de acesso ao banco de dados.');

if (gVar("global.url")=="")
	gVar("global.url",$HTTP_HOST);

$http="http";
if ($SERVER_PORT==443)
	$http="https";

$gurl=gVar("global.url");
if ((substr($_SERVER['SERVER_ADDR'],0,3)=="192") && (substr($_SERVER['SERVER_ADDR'],0,10)==substr($_SERVER['REMOTE_ADDR'],0,10)))
	$gurl=$_SERVER['SERVER_ADDR'];
$http_base= $gurl."/".gBASE ."/";
$http_base= "$http://" . str_replace("//","/",$http_base);
$http_img = $http_base ."img_".gVER;
$http_inc = $http_base ."inc_".gVER;

/* Variaveis globais */


$gTags="";
$gTags["page.header_start"]="<html><head><meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-1' />\n<meta name='keywords' content='".gVar("global.keywords")."' />\n<meta name='description' content='".gVar("global.keywords")."' />\n<title>".gVar("global.site")."</title>\n";
$gTags["page.header_end"]="</head><body>\n";
$gTags["page.css"]="";
$gTags["page.footer"]="</body></html>";

$gTags["menu.topbar_start"]="<html><head></head><body leftmargin=1 topmargin=1>\n";
$gTags["menu.topbar_end"]="</body></html>";
$gTags["menu.bottombar_start"]="<html><head></head><body leftmargin=0 topmargin=0 valign='top'>\n";
$gTags["menu.bottombar_end"]="</body></html>";
$gTags["menu.menu_start"]="<html><head></head><body leftmargin=1 topmargin=1>\n";
$gTags["menu.menu_end"]="</body></html>";


$st="<tr>";
$st.="<td width='3px' class='tab'><img src='$http_img/win_lt.gif'></td>";
$st.="<td class='tab' background='$http_img/win_t.gif'></td>";
//$st.="<td class='tab'><img width='100%' src='$http_img/win_t.gif'></td>";
$st.="<td width='3px' class='tab'><img src='$http_img/win_rt.gif'></td>";
$st.="</tr>";

$stm="<tr>";
$stm.="<td width='3px' class='tab' background='$http_img/win_l.gif'></td>";
//$stm.="<td width='3px' align='left' class='tab'><img src='$http_img/win_l.gif'></td>";
$stm.="<td class='tab' bgcolor='white'>";

$sbm="</td>";
$sbm.="<td width='3px' class='tab' background='$http_img/win_r.gif'></td>";
//$sbm.="<td width='3px' align='right' class='tab'><img src='$http_img/win_r.gif'></td>";
$sbm.="</tr>";

$sb="<tr>";
$sb.="<td width='3px' class='tab'><img src='$http_img/win_lb.gif'></td>";
$sb.="<td class='tab' background='$http_img/win_b.gif'></td>";
//$sb.="<td class='tab' ><img width='100%' src='$http_img/win_b.gif'></td>";
$sb.="<td width='3px' class='tab'><img src='$http_img/win_rb.gif'></td>";
$sb.="</tr>";

$sti="<tr>";
$sti.="<td width='3px' class='tab'><img src='$http_img/iwin_lt.gif'></td>";
$sti.="<td class='tab' background='$http_img/iwin_t.gif'></td>";
$sti.="<td width='3px' class='tab'><img src='$http_img/iwin_rt.gif'></td>";
$sti.="</tr>";

$stmi="<tr>";
$stmi.="<td width='3px' class='tab' background='$http_img/iwin_l.gif'></td>";
$stmi.="<td class='tab' bgcolor='white'>";

$sbmi="</td>";
$sbmi.="<td width='3px' class='tab' background='$http_img/iwin_r.gif'></td>";
$sbmi.="</tr>";

$sbi="<tr>";
$sbi.="<td width='3px' class='tab'><img src='$http_img/iwin_lb.gif'></td>";
$sbi.="<td class='tab' background='$http_img/iwin_b.gif'></td>";
$sbi.="<td width='3px' class='tab'><img src='$http_img/iwin_rb.gif'></td>";
$sbi.="</tr>";

/*
$gTags["table.header_nullborder_free_start"]="<table class='nullborder' border='0' width='";
$gTags["table.header_nullborder_free_end"]="'>$st $stm<table class='nullborder' border='0' width='100%' cellpadding='0' cellspacing='0'>";
$gTags["table.header_nullborder_tiny"]="<table width='400' class='nullborder' cellpadding='0' cellspacing='0'>$st $stm<table class='nullborder' border='0' width='100%'  cellpadding='0' cellspacing='0'>";
$gTags["table.header_nullborder_medium"]="<table width='780' class='nullborder' cellpadding='0' cellspacing='0'>$st $stm<table class='nullborder' border='0' width='100%'  cellpadding='0' cellspacing='0'>";
$gTags["table.header_nullborder_big"]="<table width='100%' class='nullborder' cellpadding='0' cellspacing='0'>$st $stm<table class='nullborder' border='0' width='100%'  cellpadding='0' cellspacing='0'>";
*/

$gTags["table.header_noborder_free_start"]="<table border='0' width='";
$gTags["table.header_noborder_free_end"]="'>$st $stm<table border='0' width='100%' cellpadding='0' cellspacing='0'>";
$gTags["table.header_noborder_tiny"]="<table width='365' class='nullborder' cellpadding='0' cellspacing='0'>$st $stm<table border='0' width='100%'  cellpadding='1' cellspacing='1'>";
$gTags["table.header_noborder_medium"]="<table width='740' class='nullborder' cellpadding='0' cellspacing='0'>$st $stm<table border='0' width='100%'  cellpadding='1' cellspacing='1'>";
$gTags["table.header_noborder_big"]="<table width='100%' class='nullborder' cellpadding='0' cellspacing='0'>$st $stm<table border='0' width='100%'  cellpadding='1' cellspacing='1'>";

$gTags["table.header_nullborder_free_start"]="<table class='nullborder' border='0' width='";
$gTags["table.header_nullborder_free_end"]="'>";
$gTags["table.header_nullborder_tiny"]="<table width='365' class='nullborder' cellpadding='0' cellspacing='0'>";
$gTags["table.header_nullborder_medium"]="<table width='740' class='nullborder' cellpadding='0' cellspacing='0'>";
$gTags["table.header_nullborder_big"]="<table width='100%' class='nullborder' cellpadding='0' cellspacing='0'>";

$gTags["table.header_border_free_start"]="<table class='nullborder' cellpadding='0' cellspacing='0' width='";
$gTags["table.header_border_free_end"]="'>$st $stm<table class='nullborder' border='0' width='100%' cellpadding='1' cellspacing='1'>";
$gTags["table.header_border_tiny"]="<table width='365' class='nullborder' cellpadding='0' cellspacing='0'>$st $stm<table class='nullborder' border='0' width='100%' cellpadding='1' cellspacing='1'>";
$gTags["table.header_border_medium"]="<table width='740' class='nullborder' cellpadding='0' cellspacing='0'>$st $stm<table class='nullborder' border='0' width='100%' cellpadding='1' cellspacing='1'>";
$gTags["table.header_border_big"]="<table width='100%' class='nullborder' cellpadding='0' cellspacing='0'>$st $stm<table class='nullborder' border='0' width='100%' cellpadding='1' cellspacing='1'>";

$gTags["table.header_iborder_free_start"]="<table class='nullborder' cellpadding='0' cellspacing='0' width='";
$gTags["table.header_iborder_free_end"]="'>$sti $stmi<table class='nullborder' border='0' width='100%' cellpadding='0' cellspacing='0'>";
$gTags["table.header_iborder_tiny"]="<table width='365' class='nullborder' cellpadding='0' cellspacing='0'>$sti $stmi<table class='nullborder' border='0' width='100%' cellpadding='0' cellspacing='0'>";
$gTags["table.header_iborder_medium"]="<table width='740' class='nullborder' cellpadding='0' cellspacing='0'>$sti $stmi<table class='nullborder' border='0' width='100%' cellpadding='0' cellspacing='0'>";
$gTags["table.header_iborder_big"]="<table width='100%' class='nullborder' cellpadding='0' cellspacing='0'>$sti $stmi<table class='nullborder' border='0' width='100%' cellpadding='0' cellspacing='0'>";

$gTags["table.footer"]="</table>$sbm $sb</table>";
$gTags["table.footer_nullborder"]="</table>";
$gTags["table.ifooter"]="</table>$sbmi $sbi</table>";

$gTags["table.row_start"]="<tr valign='top'>";
$gTags["table.row_end"]="</tr>";
$gTags["table.col_start"]="<td>";
$gTags["table.col_end"]="</td>";
$gTags["table.col_end"]="</td>";
$gTags["table.col_header_start"]="<th>";
$gTags["table.col_header_end"]="</th>";


	
$gApplication = new gApplication(gBASE);

/** Classe responsêvel por funêêes acessêrias para variêveis de sessêo e aplicaêêo
*@package gStart
*@author Giuliano Nascimento
*@version 2.0
*/
class gApplication {
	var $name=NULL;
	var $fp=NULL;
	var $locked=FALSE;

/** Abre um arquivo e inicia uma aplicaêêo
*@author Giuliano Nascimento
*@version 2.0
*@param string $appname arquivo a ser aberto
*/
	function gApplication($appname) {
		$this->name=str_replace("/","_",$appname);
		if (!$this->_openfile()) {
			$this->_error("*** Error opening ".gAPP_FILE." storage file", E_USER_WARNING);
		}
	}

/**Acrescenta dados ao arquivo
*@author Giuliano Nascimento
*@version 2.0
*@param mixed $var Valor a ser adicionado ao arquivo
*@param mixed $value Valor a ser adicionado ao arquivo
*/
	function set($var, $value=NULL) {
		if (!$this->fp) return FALSE;
		$ilocked=$this->_trylock();
		if ($ilocked===NULL) return FALSE;
		$vars=$this->_unserializeread();
		if (!is_array($vars)) $vars=array();
		$setvars=array();
		if (!is_array($var)) {
			$setvars[$var]=$value;
		} else {
			$setvars=$var;
		}
		foreach ($setvars as $k=>$v) {
			$vars[$k]=$v;
		}
		$this->_serializewrite($vars);
		if ($ilocked) {
			$this->unlock();
		}
	}

/**Retorna um dado especêfico do arquivo
*@author Giuliano Nascimento
*@version 2.0
*@param mixed $var Valor a ser procurado no arquivo
*@return mixed $ret Se $var nêo for um array, o valor de retorno serê o conteêdo de um array com a chave $var,
ou se $var for um array, este serê retornado.
*/
	function get($var) {
		$vars=$this->getall();
		if ($vars===FALSE) return FALSE;
		if (!is_array($var)) {
			$ret=$vars[$var];
		} else {
			$ret=array();
			foreach ($var as $v) {
				$ret[$v]=$vars[$v];
			}
		}
		return $ret;
	}

/**Retorna todo o conteêdo do arquivo
*@author Giuliano Nascimento
*@version 2.0
*@return mixed $vars Todo o conteêdo do arquivo, ou FALSE caso ocorra algum erro durante a leitura do arquivo
*/
function getall() {
		if (!$this->fp) return FALSE;
		$ilocked=$this->_trylock();
		if ($ilocked===NULL) return FALSE;
		$vars=$this->_unserializeread();
		if ($ilocked) {
			$this->unlock();
		}
		return $vars;
	}

/**Trava o arquivo de forma compartilhada (para leitura)
*@author Giuliano Nascimento
*@version 2.0
*@return bool $ret TRUE se o arquivo foi travado com sucesso, FALSE caso contrêrio
*/
function shared_lock() {
		if (!$this->fp) return FALSE;
		$ret=flock($this->fp, LOCK_SH);
		if ($ret) $this->locked=TRUE;
		return $ret;
	}

/**Trava o arquivo de forma exclusiva (para gravaêêo)
*@author Giuliano Nascimento
*@version 2.0
*@return bool $ret TRUE se o arquivo foi travado com sucesso, FALSE caso contrêrio
*/
function lock() {
		if (!$this->fp) return FALSE;
		$ret=flock($this->fp, LOCK_EX);
		if ($ret) $this->locked=TRUE;
		return $ret;
	}

/**Destrava o arquivo
*@author Giuliano Nascimento
*@version 2.0
*@return bool $ret TRUE se o arquivo foi destravado com sucesso, FALSE caso contrêrio
*/
	function unlock() {
		if (!$this->fp) return FALSE;
		$ret=flock($this->fp, LOCK_UN);
		$this->locked=FALSE;
		return $ret;
	}

/**Tenta travar o arquivo
*@author Giuliano Nascimento
*@version 2.0
*@return NULL se o arquivo nêo for vêlido, FALSE se o arquivo jê se estiver travado 
*ou TRUE se o arquivo for travado corretamente
*/
function _trylock() {
		if (!$this->fp) return NULL;
		/* if already locked the user wants to handle locking */
		if ($this->locked) return FALSE;
		if ($this->lock()) return TRUE;
		return NULL;
	}

/**Serializa os dados e escreve no arquivo
*@author Giuliano Nascimento
*@version 2.0
*@param mixed $vars Dados que serêo serializados e escritos em um arquivo
*/
	function _serializewrite($vars) {
		$this->_write(serialize($vars));
	}

/**Retorna os dados no seu formato original
*@author Giuliano Nascimento
*@version 2.0
*@return mixed $data Os dados no seu formato PHP original
*/
function _unserializeread() {
		$data=$this->_read();
		return unserialize($data);
	}

/**Lê dados de um arquivo
*@author Giuliano Nascimento
*@version 2.0
*@return string $buff O conteêdo do arquivo
*/
function _read() {
		rewind($this->fp);
		$buff="";
		while (($l=fread($this->fp, 1024))) {
			$buff.=$l;
		}
		return $buff;
	}

/**Escreve dados em um arquivo
*@author Giuliano Nascimento
*@version 2.0
*@param string $data Dados a serem acrescentados ao arquivo
*/
function _write($data) {
		ftruncate($this->fp, 0);
		rewind($this->fp);
		fwrite($this->fp, $data, strlen($data));
		fflush($this->fp);
	}

/**Abre o arquivo para leitura e escrita
*@author Giuliano Nascimento
*@version 2.0
*@return Integer $this->fp O ponteiro do arquivo, se ele tiver sido aberto corretamente
*ou false, caso o arquivo nêo tenha sido aberto corretamente
*/
	function _openfile() {
		$pumask=umask(gAPP_MASK);
		$this->fp=fopen($this->_file(), "a+");
		return $this->fp;
	}

/** Fecha o arquivo
*@author Giuliano Nascimento
*@version 2.0
*@return bool Se o arquivo foi fechado com sucesso
*/
	function _closefile() {
		return fclose($this->fp);
	}

/** Gera mensagem de erro
*@author Giuliano Nascimento
*@version 2.0
*@param string $err A mensagem a ser mostrada
*@param Integer $type O tipo do erro
*/
	function _error($err, $type) {
		trigger_error($err, $type);
	}

/** Retorna o caminho completo do arquivo
*@author Giuliano Nascimento
*@version 2.0
*@return string O nome do arquivo, com seu caminho completo
*/
	function _file() {
		return  gAPP_FILE . $this->name;
	}
}
// Fim de funêêes acessêrias para variêveis de sessêo e aplicaêêo

/**Lê os dados de um arquivo
*@author Giuliano Nascimento
*@version 2.0
*@param string $t_filename O arquivo a ser lido
*@return $s O conteêdo do arquivo lido
*/
function gReadFile($t_filename)
{
	global $gDebug;
	$s="";
	if (file_exists($t_filename))
	{
		$fp=fopen($t_filename,"r");
		$s=fread($fp,filesize($t_filename));
		fclose($fp);
	} else
	{
		if( $gDebug>0)	{gLog("gStart.php => gReadFile => \033[31;1mFile not found: $t_filename");}
	}
	return($s);
}

/**Lê um arquivo .XML e converte-o em um array de dois elementos
*sendo o 1ê = nome e 2ê = valor
*@author Giuliano Nascimento
*@version 2.0
*@param string $t_filename O arquivo .XML a ser lido
*@return array $fields Array contendo os elementos do arquivo .XML
*/
function gReadXML($t_filename)
{

	global $gDebug;
	if ($gDebug>1)	{gLog("gStart.php => gReadXML($t_filename)");	}
	$txt=gReadFile($t_filename);
	$pnt=1;
	$cntlev=-1;
	$cntfld=0;
	$maxfld=500;

	while ($pnt<strlen($txt))
	{
		$pnt = strpos($txt, "<",$pnt);
		if ((!($pnt === false)) && ($pnt<strlen($txt)))
		{
			if (substr($txt,$pnt+1,1)<>"?")
			{
				$pntEnd=strpos($txt,">",$pnt);
				if (!($pntEnd===false))
				{
					$Tag=substr($txt,$pnt+1,$pntEnd-$pnt-1);
					if (substr($Tag,0,1)== '/' )
					{
						if ($cntlev>-1)
						{
							$cntlev--;
							array_pop($level);
						} else
						{
							//*** Erro: Mais fechamento de TAGs do existem aberturas
						}
					} else
					{
						$pntNext=strpos($txt,"<",$pntEnd+1);
						if ($pntNext>0)
						{
							$TagContent=trim(substr($txt,$pntEnd+1,$pntNext-$pntEnd-1));
							$pntClose=strpos($txt,">",$pntNext+1);
							if ($pntClose>0)
							{
								$TagClose=substr($txt,$pntNext+1,$pntClose-$pntNext-1);
								if ($TagClose=='/'.$Tag)
								{
									$lvl="";
									for ($a=1; $a<=$cntlev; $a++)
									{
										$lvl=$lvl.$level[$a]. ".";
									}
									$fields[]=array(strtolower($lvl . $Tag),$TagContent);
									$cntfld++;
									//===
									if ($gDebug>1)	{gLog($lvl . trim($Tag) . " = " . trim($TagContent));}
									$pnt=$pntClose;
									if ($cntfld>$maxfld)
									{
										//*** Erro: Mêximo de campos atingido
										$pnt=strlen($txt)+1;
									}
								} else
								{
									$cntlev++;
									$level[]=$Tag;
								}
							} else
							{
								$erro=true;
								//*** Erro: Na TAG de fechamento, falta um >"
							}
						} else
						{
							$erro=true;
							//*** Erro: Nêo existe o fechamento da TAG
						}
					}
				}
				else
				{
					$erro=true;
					//*** Erro: Iniciou a TAG mas nêo concluiu
				}
			}
			else
			{
				// TAG XML
			}
			$pnt++;
		} else{
			$pnt=strlen($txt)+1;
		}
	}
	return($fields);
}

/**Criptografa uma string
*@author Giuliano Nascimento
*@version 2.0
*@param string $t_text String a ser criptografada
*@return string $_text String criptografada
*/
function gCrypt($t_text)
{
	//?
	return($t_text);
}

/**Descriptografa uma string
*@author Giuliano Nascimento
*@version 2.0
*@param string $t_text String a ser descriptografada
*@return string $t_text String descriptografada
*/
function gDecrypt($t_text)
{
	//?
	return($t_text);
}

/**Registra uma nova sessêo
*@author Giuliano Nascimento
*@version 2.0
*@param string $t_name O nome da sessêo
*@param string $t_value Valor atribuêdo ê variêvel de sessêo
*
*/
function gSessionSave($t_name,$t_value)
{
	@session_register($t_name);
	$_SESSION[$t_name] = $t_value;
}

/**Recupera o valor de uma variêvel de sessêo se ela existir, caso nêo exista, redireciona para pêgina de erro
*@author Giuliano Nascimento
*@version 2.0
*@param string $t_name
*@return mixed O valor da variêvel de sessêo
*/
function gSessionLoad($t_name)
{
	//?
	return $_SESSION[$t_name];
}

/**Salva valor em variêvel global de aplicaêêo
*@author Giuliano Nascimento
*@version 2.0
*@param string $t_name
*@param string $t_value
*?
*/
function gAppSave($t_name,$t_value)
{
	// * Salva valor em variêvel global de aplicaêêo
	//application(t_name)=gCrypt(t_value)
	//setcookie($t_name,gcrypt($t_value));
	global $gApplication;
	$gApplication->lock();
	$gApplication->set($t_name,$t_value);
	$gApplication->unlock();
}

/**Recupera o valor de variêvel de aplicaêêo
*@author Giuliano Nascimento
*@version 2.0
*@param string $t_name O nome da variêvel a ser recuperada
*@return mixed O valor de variêvel de aplicaêêo
*/
function gAppLoad($t_name)
{
	// * Recupera o valor de variêvel de aplicaêêo
	//gAppLoad=gDecrypt(application(t_name))
	global $gApplication;
	return($gApplication->get($t_name));

}

function gReadSchema($t_name)
{
	global $gCfg;
	global $gPathDefault;
	// * Lê Tema e Dicionêrio para a sessêo atual
	$gxml="xml/gSchema_" . $t_name . ".xml";
  // Carrega o arquivo XML de linguagem e seta as variêveis
	$gschema=gReadXML($gPathDefault . $gxml );
	for ($a=0;$a<count($gschema);$a++)
	{
			$gschema[$a][1]=str_replace("[","<",$gschema[$a][1]);
			$gschema[$a][1]=str_replace("]",">",$gschema[$a][1]);
			$pnt=strpos($gschema[$a][1],"#");
			while (($pnt>0) && ($pnt<strlen($gschema[$a][1])))
			{
				$pnt2=strpos($gschema[$a][1],"#",$pnt+1);
				if ($pnt2>0)
				{
					$tvar=substr($gschema[$a][1],$pnt+1,$pnt2-$pnt-1);
					for ($b=0;$b<count($gCfg);$b++)
					{
						if (strtolower($tvar)==$gCfg[$b][0])
						{
							$gschema[$a][1]=substr($gschema[$a][1],0,$pnt) . $gCfg[$b][1] . substr($gschema[$a][1],$pnt2+1);
						}
					}
					$pnt=strpos($gschema[$a][1],"#",$pnt2+1);
				} else
				{
					$pnt=strlen($gschema[$a][1]);
				}
			}
	}
	gSessionSave("gschema",$gschema);
}

function gReadLanguage($t_name)
{
	// * Lê Tema e Dicionêrio para a sessêo atual
	/*
	global $gPathDefault;
	$gxml="xml/gDict_" . $t_name . ".xml";
	// Carrega o arquivo XML de linguagem e seta as variêveis
	$gslang=gReadXML($gPathDefault . $gxml );
	for ($a=0;$a<count($gslang);$a++)
	{
			$gslang[$a][1]=str_replace("[","<",$gslang[$a][1]);
			$gslang[$a][1]=str_replace("]",">",$gslang[$a][1]);
			$pnt=strpos($gslang[$a][1],"#");
			while (($pnt>0) && ($pnt<strlen($gslang[$a][1])))
			{
				$pnt2=strpos($gslang[$a][1],"#",$pnt+1);
				if ($pnt2>0)
				{
					$tvar=substr($gslang[$a][1],$pnt+1,$pnt2-$pnt-1);
					for ($b=0;$b<count($gCfg);$b++)
					{
						if (strtolower($tvar)==$gCfg[$b][0])
						{
							$gslang[$a][1]=substr($gslang[$a][1],0,$pnt) . $gCfg[$b][1] . substr($gslang[$a][1],$pnt2+1);
						}
					}
					$pnt=strpos($gslang[$a][1],"#",$pnt2+1);
				} else
				{
					$pnt=strlen($gslang[$a][1]);
				}
			}
	}
	*/
	gSessionSave("glang",$gslang);
}


function gVar($t_parameter,$t_newvalue=":null:")
{
	// * Retorna o valor da variêvel "parameter", da matriz "gcfg" (variêvel de aplicaêêo)
	global $gDebug;
	global $gCfg;
	if ($gCfg["global.site"]<>"")
	{ 
		// Formato novo...
		$gVar=$gCfg[$t_parameter];
		if ($t_newvalue<>":null:")
		{
			$gCfg[$t_parameter]=$t_newvalue;
		}
	} else
	{
		// Formato antigo
		$ga=0;
		$gttl=count($gCfg);
		while ($ga<$gttl)
		{
			if ($gCfg[$ga][0]==strtolower($t_parameter))
			{
				if ($t_newvalue<>":null:")
				{
					$gCfg[$ga][1]=$t_newvalue;
					$gVar=$gCfg[$ga][1];
					$ga=$gttl;
				} else
				{
					$gVar=$gCfg[$ga][1];
					$ga=$gttl;
				}
			}
			$ga=$ga+1;
		}
		if (($gVar=="") && ($gDebug>0))		{gLog("gStart.php	gVar(" . $t_parameter . ")	\033[31;1mVariable not found !");		}	
	}
	return($gVar);
}

function gLng($t_word)
{
	// * Retorna o valor da variavel "parameter", da matriz "glang" (variavel de aplicacao)
	global $gDebug;
	global $gLngs;
	$t_words=explode(".",$t_word);
	if (count($t_words)>2) // Se tiver 3 parametros separados por ponto, ignora o primeiro
		$t_words=array($t_words[1],$t_words[2]);
	if ($t_words[1]=="") // Se nao tiver a especificacao do tamanho, como padrao sera SHORT
		$t_words[1]=="short";
	$agLng=$gLngs[strtolower($t_words[0])];
	if (!is_array($agLng))
		$gLng=$agLng;
	else 
	{
		if ($t_words[1]=="long")
			$gLng=$agLng[1];
		else
			$gLng=$agLng[0];
	}
	if ($gLng=="")
	{
		$gLng=$t_word;
	}
	/*
	gRead();
	$glang=gSessionLoad("glang");
	$ga=0;
	$gttl=count($glang);
	$t_par=$t_parameter;
	$gLng="";
	if ($t_parameter<>"")
	{
		if ((strpos($t_parameter,".long")===false) && (strpos($t_parameter,".short")===false))
		{
			$t_par.=".long";
		}
		while ($ga<$gttl)
		{
			if (strpos($glang[$ga][0],strtolower($t_par))!==false)
			{
				$gLng=$glang[$ga][1];
				$ga=$gttl;
			}
			$ga=$ga+1;
		}
		if ($gLng=="")
		{
			$gLng=$t_parameter;
		}
	}
	*/
	return($gLng);
}

function gT($t_word)
{
	return(gLng($t_word));
}

function gTag($t_parameter)
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
	$gTag=$gTags[$t_parameter];
	return($gTag);
}

function gLog($txt)
{
	global $cr;
	global $usr_apelido;
	global $usr_id;
	$logfile=gVar("debug.logfilename");
	/*
	if (!file_exists($logfile))
	{
		$file=pathinfo($logfile);
		mkdir($file["dirname"],0660);
	}
	*/
	if ($fp=fopen($logfile,"a"))
	{
		if (gVar("debug.loglevel")>0)
			fputs($fp,date("Y-m-d H:i:s")." ".$_SERVER["REMOTE_ADDR"]."	$usr_apelido($usr_id)	".$_SERVER["PHP_SELF"]."	".$txt."\033[0m".$cr);
		else
			fputs($fp,date("H:i:s")."	".$txt."\033[0m".$cr);
		fclose($fp);
	}

}

function gRead()
{
	//* Lê o arquivo de configuraêêes do site e armazena em uma variêvel global (ou cookie)
	global $gPathDefault;
	global $gTransFile;

	$schema=gVar("global.schema");
	$langua=gVar("global.language");

	//$gtmpFileDate=filemtime($gPathDefault . "xml/gSchema_$schema.xml");
	//$gSchemaFileDate=gAppLoad("gSchemaFileDate");
	$gtmpFileDate2=filemtime($gPathDefault . "xml/gDict_$langua.xml");
	$gLanguageFileDate=gAppLoad("gLanguageFileDate");
/*
	if (($gSchemaFileDate<$gtmpFileDate) || (!session_is_registered("gschema")))
	{
		// Carrega o arquivo XML de linguagem e seta as variêveis
		//gReadSchema(gVar("global.schema"));
	}
*/
	if (($gLanguageFileDate<$gtmpFileDate2) || (!session_is_registered("glang")))
	{
		// Carrega o arquivo XML de linguagem e seta as variêveis
		if ($gTransFile=="")
			gReadLanguage(gVar("global.language"));
	}
	//gAppSave("gSchemaFileDate",$gtmpFileDate);
	gAppSave("gLanguageFileDate",$gtmpFileDate2);
}

function gHTMLCompact($t_parameter)
{
	$gsTmp=str_replace(chr(9),"",$t_parameter);
	$gsTmp=str_replace(chr(8),"",$gsTmp);
	$gsTmp=str_replace(chr(10),"",$gsTmp);
	$gsTmp=str_replace(chr(13),"",$gsTmp);
	$gsTmp=str_replace("  ","",$gsTmp);
	return($gsTmp);
}

function right($st,$tam)
{
	return (substr($st,strlen($st)-$tam));
}

function gDateDiff($from, $to, $negativo = 0) {
	/*
		Calcula a diferenca entre duas datas
		Formato em global.dateformat
	*/
	$fmt=strtolower(gVar("global.dateformat"));
	$charf='-';
	if (strpos($fmt,"-")>0) $charf='-';
	if (strpos($fmt,"/")>0) $charf='/';
	$mto=split($charf,$to);
	$mfrom=split($charf,$from);
	$mfmt=split($charf,$fmt);
	for ($t=0;$t<count($mfmt);$t++)
	{
		$dat=$mfmt[$t];
		if ($dat[0]=="d")
		{
			$from_day=$mfrom[$t];
			$to_day=$mto[$t];
		}
		if ($dat[0]=="m")
		{
			$from_month=$mfrom[$t];
			$to_month=$mto[$t];
		}
		if ($dat[0]=="y")
		{
			$from_year=$mfrom[$t];
			$to_year=$mto[$t];
		}
	}
	if (strlen($from_year)<4)
	{
		if ($from_year<50)
			$from_year="20".$from_year;
		else
			$from_year="19".$from_year;
	}
	if (strlen($to_year)<4)
	{
		if ($to_year<50)
			$to_year="20".$to_year;
		else
			$to_year="19".$to_year;
	}
	$from_date = mktime(0,0,0,$from_month,$from_day,$from_year);
	$to_date = mktime(0,0,0,$to_month,$to_day,$to_year);
	$days = ($to_date - $from_date)/86400;

	/*Adicionado o ceil($days) para garantir que o resultado seja sempre um nêmero inteiro */
	if ($days<0 && $negativo==0) {
		$days=$days * -1;
	}
	return ceil($days);
}

function gDateAdd($from,$days=1,$month=0,$year=0) {
/*
  Soma uma quantidade de dias a data atual
  retorna padrêo brasileiro
*/
	$fmt=strtolower(gVar("global.dateformat"));
	$charf='-';
	if (strpos($from,"-")>0) $charf='-';
	if (strpos($from,"/")>0) $charf='/';
	$mfrom=split($charf,$from);
	$charf='-';
	if (strpos($fmt,"-")>0) $charf='-';
	if (strpos($fmt,"/")>0) $charf='/';
	$mfmt=split($charf,$fmt);
	for ($t=0;$t<count($mfmt);$t++)
	{
		$dat=$mfmt[$t];
		if ($dat[0]=="d")
		{
			$from_day=$mfrom[$t];
		}
		if ($dat[0]=="m")
		{
			$from_month=$mfrom[$t];
		}
		if ($dat[0]=="y")
		{
			$from_year=$mfrom[$t];
		}
	}
	if (strlen($from_year)<4)
	{
		if ($from_year<50)
			$from_year="20".$from_year;
		else
			$from_year="19".$from_year;
	}
	$to_date = gDate(date("Y-m-d",mktime(0,0,0,$from_month+$month,$from_day+$days,$from_year+$year)));
	return $to_date;
}


function gDate($t_parameter)
{
	$fmt=strtolower(gVar("global.dateformat"));
	/*
	$fmt=str_replace("dd","%d",$fmt);
	$fmt=str_replace("mm","%m",$fmt);
	$fmt=str_replace("yyyy","%Y",$fmt);
	$fmt=str_replace("yy","%y",$fmt);
	*/
	//$fmt=str_replace("-","/",$fmt);
	//$fmt=str_replace(".","/",$fmt);
	if (strpos($t_parameter,"-00")>0)
		$t_parameter="";
	else
	{
//		$t_parameter=strftime($fmt,strtotime($t_parameter)); // Apresentou problemas com datas ateriores 1970
		$sep="/";
		if (strpos($fmt,"-")>0) $sep="-";
		$t_p=explode("-",$t_parameter);
		$t_f=explode($sep,$fmt);
		$dia=intval($t_p[2]);
		$mes=intval($t_p[1]);
		$ano=intval($t_p[0]);
		$data="";
		for ($t=0; $t<3; $t++)
		{
			if (substr($t_f[$t],0,1)=="d") $data.=sprintf("%0".strlen($t_f[$t])."s",$dia);
			if (substr($t_f[$t],0,1)=="m") $data.=sprintf("%0".strlen($t_f[$t])."s",$mes);
			if (substr($t_f[$t],0,1)=="y") $data.=right(sprintf("%0".strlen($t_f[$t])."s",$ano),strlen($t_f[$t]));
			$data.=$sep;
		}
		$t_parameter=substr($data,0,strlen($data)-1);
	}
	return($t_parameter);
}

function gDateTime($t_parameter)
{
	if (strpos($t_parameter,"-00")>0)
	$sai="";
	else
	{
		$data=split(" ",$t_parameter);
		$time=$data[1];
		$sai=gDate($t_parameter). " " . $time;
	}
	return($sai);
}

function gDBDate($t_parameter)
{
	if ($t_parameter)
	{
		$fmt=strtolower(gVar("global.dateformat"));
		$char='-';
		if (strpos($t_parameter,"-")>0) $chard='-';
		if (strpos($t_parameter,"/")>0) $chard='/';
		if (strpos($fmt,"-")>0) $charf='-';
		if (strpos($fmt,"/")>0) $charf='/';
		$data=split($chard,$t_parameter);
		$form=split($charf,$fmt);
		for ($t=0;$t<count($data);$t++)
		{
			$dat=$form[$t];
			if ($dat[0]=="d") $dia=$data[$t];
			if ($dat[0]=="m") $mes=$data[$t];
			if ($dat[0]=="y") $ano=$data[$t];
		}
		if (strlen($ano)<4)
		{
			if ((intval($ano)==0) && (intval($mes)==0) && (intval($dia)==0))
				$ano="0000";
			elseif ($ano<50)
				$ano="20".$ano;
			else
				$ano="19".$ano;
		}
		$t_parameter=$ano."-".$mes."-".$dia;
	}
	return($t_parameter);
}

function gDBDateTime($t_parameter)
{
	$sai="";
	if ($t_parameter)
	{
		$data=split(" ",$t_parameter);
		$time=$data[1];
		$sai=gDBDate($data[0]). " " . $time;
	}

	return($sai);
}

function gDBFloat($t_parameter)
{
	$dec=2;
	$c1=',';
	$c2='.';
	$fmt=gVar("global.numformat");
	if (($fmt=="0000,00") || ($fmt=="0,00"))
	{
		$c1=',';
		$c2='';
	}
	if (($fmt=="0000.00") || ($fmt=="0.00"))
	{
		$c1='.';
		$c2='';
	}
	if ($fmt=="0,000.00")
	{
		$c1='.';
		$c2=',';
	}
	$t_parameter=str_replace($c2,"",$t_parameter);
	return(str_replace($c1,".",$t_parameter));
}

function gFloat($t_parameter)
{
	$fmt=gVar("global.numformat");
	$p1=strpos($fmt,".");
	$p2=strpos($fmt,",");
	if (($p1>0) && ($p2>0))
	{
		if ($p1<$p2)
		{
			$c1=',';
			$c2='.';
		} else
		{
			$c1='.';
			$c2=',';
		}
	} else
	{
		if ($p1>0)
		{
			$c1=',';
			$c2='';
		}
		if ($p2>0)
		{
			$c1='.';
			$c2='';
		}
	}
	$m=split($c1,$fmt);
	$dec=strlen($m[1]);
	$t_parameter=number_format($t_parameter, $dec, $c1, $c2);
	return($t_parameter);
}

function gFieldRpl()
{
	$rpl="";
	$rpl[]=array("cao","ção");
	$rpl[]=array("sao","são");
	$rpl[]=array("ssoes","ssões");
	$rpl[]=array("nao","não");
	$rpl[]=array("mao","mão");
	$rpl[]=array("coes","ções");
	$rpl[]=array("tao","tão");
	$rpl[]=array("toes","tões");
	$rpl[]=array("odigo","ódigo");
	$rpl[]=array("id_","");
	$rpl[]=array("umero","úmero");
	$rpl[]=array("ervico","erviço");
	return($rpl);
}

function gField($t_parameter)
{
	if (gVar("global.language")=="pt_BR")
	{
		$rpl=gFieldRpl();
		for ($t=0; $t<count($rpl); $t++)
		{
			$t_parameter=str_replace($rpl[$t][0],$rpl[$t][1],$t_parameter);
		}
	}
	$t_parameter=ucfirst(str_replace("_"," ",$t_parameter));
	return($t_parameter);
}

function gFieldReverse($t_parameter)
{
	$t_parameter=strtolower($t_parameter);
	if (gVar("global.language")=="pt_BR")
	{
		$rpl=gFieldRpl();
		$rpl[]=array("a","ê");
		$rpl[]=array("a","ê");
		$rpl[]=array("a","ê");
		$rpl[]=array("a","ê");
		$rpl[]=array("e","ê");
		$rpl[]=array("e","ê");
		$rpl[]=array("e","ê");
		$rpl[]=array("i","ê");
		$rpl[]=array("o","ê");
		$rpl[]=array("o","ê");
		$rpl[]=array("o","ê");
		$rpl[]=array("o","ê");
		$rpl[]=array("u","ê");
		$rpl[]=array("u","ê");
		$rpl[]=array("c","ê");
		$rpl[]=array("_"," ");
		for ($t=0; $t<count($rpl); $t++)
		{
			$t_parameter=str_replace($rpl[$t][1],$rpl[$t][0],$t_parameter);
		}
	}
	return($t_parameter);
}

function html2str($texto)
{
	$t=trim($texto);
	$t=str_replace('&nbsp;',' ',$t);
	$t=str_replace('N<sup>o</sup>','Nê',$t);
	$trans = get_html_translation_table (HTML_ENTITIES);
	$trans = array_flip ($trans);
	$t=strtr ($t, $trans);
	$t=trim(strip_tags($t));
	return ($t);
}

function Extenso($valor=0) {
	$singular = array("centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão");
	$plural = array("centavos", "reais", "mil", "milhões", "bilhões", "trilhões",
"quatrilhÃµes");

	$c = array("", "cem", "duzentos", "trezentos", "quatrocentos",
"quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
	$d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta",
"sessenta", "setenta", "oitenta", "noventa");
	$d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze",
"dezesseis", "dezesete", "dezoito", "dezenove");
	$u = array("", "um", "dois", "três", "quatro", "cinco", "seis",
"sete", "oito", "nove");

	$z=0;

	$valor = number_format($valor, 2, ".", ".");
	$inteiro = explode(".", $valor);
	for($i=0;$i<count($inteiro);$i++)
		for($ii=strlen($inteiro[$i]);$ii<3;$ii++)
			$inteiro[$i] = "0".$inteiro[$i];

	// $fim identifica onde que deve se dar junêêo de centenas por "e" ou por "," ;)
	$fim = count($inteiro) - ($inteiro[count($inteiro)-1] > 0 ? 1 : 2);
	for ($i=0;$i<count($inteiro);$i++) {
		$valor = $inteiro[$i];
		$rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
		$rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
		$ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";

		$r = $rc.(($rc && ($rd || $ru)) ? " e " : "").$rd.(($rd &&
$ru) ? " e " : "").$ru;
		$t = count($inteiro)-1-$i;
		$r .= $r ? " ".($valor > 1 ? $plural[$t] : $singular[$t]) : "";
		if ($valor == "000")$z++; elseif ($z > 0) $z--;
		if (($t==1) && ($z>0) && ($inteiro[0] > 0)) $r .= (($z>1) ? " de " : "").$plural[$t];
		if ($r) $rt = $rt . ((($i > 0) && ($i <= $fim) &&
($inteiro[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? ", " : " e ") : " ") . $r;
	}
	$rt=ucfirst(trim($rt));
	return($rt ? $rt : "zero");
}

function gGeraSenha()
{
	$cons=array("lh","nh","cr","tr","pr","b","c","d","f","g","h","j","k","l","m","m","p","qu","r","s","t","v","x","z");
	$vog=array("a","e","i","o","u");
	$sai="";
	$sai=$cons[rand(0,23)].$vog[rand(0,4)].rand(0,99).$cons[rand(0,23)].$vog[rand(0,4)].rand(0,99);
	return($sai);
}

function gTrueFalse($v)
{

	if ($v==1)
		$sai=gLng("message.yes.short");
	else
		$sai=gLng("message.no.short");
	return($sai);
}

//-----------------------------------------
function gD()
{
//-----------------------------------------
// Mostra o conteêdo de uma variêvel na janela cliente
	for ($t=0;$t<func_num_args();$t++)
	{
		echo "<pre>";print_r(func_get_args($t));echo "</pre>";
	}
}

function gCleanField($valor)
{
	$sai=str_replace("'"," ",$valor);
	$sai=str_replace("\""," ",$sai);
	$sai=str_replace("#"," ",$sai);
	$sai=str_replace("%"," ",$sai);
	return($sai);
}

/**
 ** comesafter ($s1, $s2)
 **
 ** Returns 1 if $s1 comes after $s2 alphabetically, 0 if not.
 **/

function comesafter ($s1, $s2) {
       /**
         ** We don't want to overstep the bounds of one of the strings and segfault,
         ** so let's see which one is shorter.
         **/

       $order = 1;

       if (strlen ($s1) > strlen ($s2)) {
               $temp = $s1;
               $s1 = $s2;
               $s2 = $temp;
               $order = 0;
       }

       for ($index = 0; $index < strlen ($s1); $index++) {
               /**
                 ** $s1 comes after $s2
                 **/

               if ($s1[$index] > $s2[$index]) return ($order);

               /**
                 ** $s1 comes before $s2
                 **/

               if ($s1[$index] < $s2[$index]) return (1 - $order);
       }
 
       /**
         ** Special case in which $s1 is a substring of $s2
         **/

       return ($order);
}

/**
 ** asortbyindex ($sortarray, $index)
 **
 ** Sort a multi-dimensional array by a second-degree index. For instance, the 0th index
 ** of the Ith member of both the group and user arrays is a string identifier. In the
 ** case of a user array this is the username; with the group array it is the group name.
 ** asortby
 **/

function gSortArray ($sortarray, $index) {
       $lastindex = count ($sortarray) - 1;
       for ($subindex = 0; $subindex < $lastindex; $subindex++) {
               $lastiteration = $lastindex - $subindex;
               for ($iteration = 0; $iteration < $lastiteration; $iteration++) {
                       $nextchar = 0;
                       if (comesafter ($sortarray[$iteration][$index], $sortarray[$iteration + 1][$index])) {
                               $sortarray[$iteration] = $sortarray[$iteration + 1];
                               $sortarray[$iteration + 1] = $temp;
                       }
               }
       }
       return ($sortarray);
} 
/** gWiki - transforma uma string com codigo Wiki em HTML
Codigos Wiki:

__ = negrito
'' = italico
#  = marcador

*/
function gWiki($texto,$macros="",$bd="")
{
	global $gId;
	$texto=nl2br($texto);
	$texto=str_replace("(__","(<b>",$texto);
	$texto=str_replace(" __"," <b>",$texto);
	$texto=str_replace("__ ","</b> ",$texto);
	$texto=str_replace("__,","</b>,",$texto);
	$texto=str_replace("__.","</b>.",$texto);
	$texto=str_replace("__;","</b>;",$texto);
	$texto=str_replace("__-","</b>-",$texto);
	$texto=str_replace("__)","</b>)",$texto);
	$texto=str_replace(" \'\'","</b>",$texto);
	$texto=str_replace("\'\',","</b>",$texto);
	$texto=str_replace("\'\'.","</b>",$texto);
	$texto=str_replace("\'\';","</b>",$texto);
	$texto=str_replace("\'\'-","</b>",$texto);
	$texto=str_replace("\'\'","</b>",$texto);
	$texto=str_replace("#","<li>",$texto);
	$texto=str_replace("@gId",$gId,$texto);
	foreach ($macros as $macro)
	{
		$texto=str_replace('@'.$macro[0]." ",$macro[1]." ",$texto);
		$texto=str_replace('@'.$macro[0].".",$macro[1].".",$texto);
		$texto=str_replace('@'.$macro[0].",",$macro[1].",",$texto);
		$texto=str_replace('@'.$macro[0].";",$macro[1].";",$texto);
		$texto=str_replace('@'.$macro[0]."/",$macro[1]."/",$texto);
		$texto=str_replace('@'.$macro[0]."-",$macro[1]."-",$texto);
		$texto=str_replace('@'.$macro[0]."'",$macro[1]."'",$texto);
		$texto=str_replace('@'.$macro[0]."<",$macro[1]."<",$texto);
		$texto=str_replace('@'.$macro[0]."\n",$macro[1]."\n",$texto);
		$texto=str_replace('@'.$macro[0].")",$macro[1].")",$texto);
		$texto=str_replace('@'.$macro[0]."]",$macro[1]."]",$texto);
	}
	if ($bd<>"")
	{
		$campos=array_keys($bd->fields);
		foreach ($campos as $campo)
		{
			$texto=str_replace('@'.$campo." ",$bd->fields[$campo]." ",$texto);
			$texto=str_replace('@'.$campo.".",$bd->fields[$campo].".",$texto);
			$texto=str_replace('@'.$campo.",",$bd->fields[$campo].",",$texto);
			$texto=str_replace('@'.$campo.";",$bd->fields[$campo].";",$texto);
			$texto=str_replace('@'.$campo."/",$bd->fields[$campo]."/",$texto);
			$texto=str_replace('@'.$campo."-",$bd->fields[$campo]."-",$texto);
			$texto=str_replace('@'.$campo.":",$bd->fields[$campo].":",$texto);
			$texto=str_replace('@'.$campo."'",$bd->fields[$campo]."'",$texto);
			$texto=str_replace('@'.$campo."<",$bd->fields[$campo]."<",$texto);
			$texto=str_replace('@'.$campo."\n",$bd->fields[$campo]."\n",$texto);
			$texto=str_replace('@'.$campo.")",$bd->fields[$campo].")",$texto);
			$texto=str_replace('@'.$campo."]",$bd->fields[$campo]."]",$texto);
		}
	}
	return($texto);
}





if ($gGo=="")
{
	gRead();
	if (isset($_SESSION['gLanguage']))
	{
		$s=$gPathDefault."translations".gBAR.$_SESSION['gLanguage'].".php";
	} else
	{
		$gL=$gCfg["global.language"];
		if ($gL=="")
		{
			$gL="pt_BR";
		}
		$s=$gPathDefault."translations".gBAR.$gL.".php";
	}
	include $s;
}

?>
