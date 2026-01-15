<?
/** Este arquivo contém a estrutura padrão para apresentação ao usuário
 *  de algum conteúdo para iPhone
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */

class gOutput extends g_Output
{
	public $showToolBar=true;

	function __construct($renderTo=gRENDER_REMOTE)
	{
		global $gOs;
		if (($_SESSION['usrId']>0) && ($gOs<>"ios"))
			$this->iphoneOnline=true;
		$this->css="styleTablet.css";
		$this->jquery=true;
		$this->extjs=false;
		//echo "<meta name='viewport' content='width=320, user-scalable=1'>".$this->n;
		//echo '<meta name="viewport" content="width=480; initial-scale=0.6666;maximum-scale=1.0; minimum-scale=0.6666" />';
		$this->html_meta.='<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=1"/>';
		$this->html_meta.='<meta name="format-detection" content="telephone=no">';

/*
	$this->html_meta="<meta http-equiv='content-type' content='text/html; charset=".gVar("global.charset")."'>".
								'<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>';
*/
		parent::__construct($renderTo);

		$pre["normal"]		="<span class='g-msg-normal'>";
		$pre["title"]		="<span class='g-msg-title'>";
		$pre["subtitle"]	="<span class='g-msg-subtitle'>";
		$pre["minititle"]	="<span class='g-msg-minititle'>";
		$pre["maxititle"]	="<span class='g-msg-maxititle'>";
		$pre["filter"]		="<span class='g-msg-filter'>";
		$pre["footer"]		="<span class='g-msg-footer'>";
		$pre["alert"]		="<span class='g-msg-alert'>";
		$pre["error"]		="<span class='g-msg-error'>";

		$pos["normal"]		="</span>";
		$pos["title"]		="</span>".$this->nl;
		$pos["subtitle"]	="</span>".$this->nl;
		$pos["minititle"]	="</span>".$this->nl;
		$pos["maxititle"]	="</span>".$this->nl;
		$pos["filter"]		="</span>".$this->nl;
		$pos["footer"]		="</span>".$this->nl;
		$pos["alert"]		="</span>";
		$pos["error"]		="</span><br>";

		$this->styleMessagePrefix=$pre;
		$this->styleMessageSufix=$pos;

	}

	/** Monta todo o código de início da página
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function gBegin()
	{
		// só executa este código uma vez!
		$sai=parent::gBegin();
		return($sai);
	}

	/** Monta todo o código de final da página
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function gEnd()
	{

		$sai=parent::gEnd();
		return($sai);
	}


	/** Monta todo o código HTML de final da página Web
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function __destruct()
	{
		// checa qual o tipo de página a ser gerada (Web, PDF, XLS, etc.)
		// monta código de saída
		$this->gEnd();
		parent::__destruct();

	}


	// Rotinas especificas para iPhone
	function iTitleBar($controls=true)
	{
		global $http_system_img;
		$login="/".$_SESSION['gBASE']."/login.php";
		$browser=$_SERVER['HTTP_USER_AGENT'];
		if (strpos($browser,"Windows CE")!==false)
		{
			$src=$login."?t=mgif&icon=icons/32/";
		} else
			$src="$http_system_img/icons/32/";

		$esq="<a href='$login'><img src='$src/o0006.png' border='0'></a>";
		$dir="<a href='$login?t=mmenu'><img src='$src/b3003.png' border='0'></a>";
		$gAPPName=$_SESSION['gAPPName'];
		if ($gAPPName=="")
		{
			$gAPPName=gVar("global.site");
		}
		$meio="<div class='g-msg-app'>".$gAPPName."</div>";
		//echo "<table style='padding: 0px 0px 0px 0px; margin: 0px 0px 0px 0px; border: 0px none; width: 100%'><tr><td align='left'>$esq</td><td align='center'>$meio</td><td align='right'>$dir</td></tr></table>";
		echo $meio;
		if ($controls)
		{
			echo "<div style='position: absolute; z-index: 10; left: 4px; top: 7px; height: 36px'>$esq</div>";
			echo "<div style='position: absolute; z-index: 9; left: -4px; top: 7px; width: 100%; height: 36px; text-align: right'>$dir</div>";
		}
	}


	function gMsg($content,$style=gNORMAL,$css="",$ahref=true)
	{
		$tag=parent::gMsg($content,$style,$css,$ahref);
		if ($style==gTITLE)
		{
			$tag='';
		}
		return ($tag);
	}


}
?>
