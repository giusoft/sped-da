<?
/** Este arquivo contém a estrutura padrão para apresentação ao usuário
 *  de algum conteúdo para PDF
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */
/*
set_time_limit(360);
define(gPDF_B,2);
define(gPDF_I,3);
define(gPDF_BI,4);
define(gPDF_T,5);
define(FPDF_VERSION,'1.41');
define(FPDF_FONTPATH,$PathDefault . 'font/');
*/


class gOutput extends g_Output
{	
	public $pdf;
	public $tabBuffer="";
	
	function __construct($renderTo=gRENDER_DOWNLOAD)
	{		
		if ($_REQUEST['gExportTo']=="Excel")
		{
			$this->renderFile='planilha.xls';
			$this->renderType='XLS';
		} else
		{
			$this->renderFile='documento.doc';
			$this->renderType='DOC';
		}
		//$this->useBuffer=true;
			
		parent::__construct("{renderTo: ".gRENDER_DOWNLOAD."}");
	}

	function __destruct()
	{
		$this->buffer=utf8_decode($this->buffer);
		parent::__destruct();
	}
	
	/** Monta todo o código de início da página
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function gBegin()
	{
		global $http_css;
		global $gPathCss;
		global $gContainers;
		global $gDevice, $gOs;

		$layout=$this->layout;
		$sai=$this->statusBegin;
		$this->outputRecording=true;
		if (!$sai)
		{
			$bodyEvent=$this->bodyEvent;
			$this->tabcnt--;
			$this->gOut("<html><head>\n");
			if ($layout["layout"]<>"")
			{
				$this->gOut("<link href='{$http_css}layouts/".$layout["layout"].".css' rel='stylesheet' type='text/css'>\n");
			}
			$this->gOut("</head>\n");
			if ($this->pageCss<>'')
			{
				$this->gOut("<style>\n".$this->pageCss."\n</style>");
			}
			$css="";
			if ($layout['background']<>"")
				$css[]="background: ".$layout['background'];
			if ($layout['background-image']<>"")
				$css[]="background-image: ".$layout['background-image'];
			if ($layout['height']<>"")
				$css[]="height: ".$layout['height'];
			if (is_array($css))
				$css=" style='".implode(";",$css)."'";
			$this->gOut("<body id='body' $css $bodyEvent>\n");
		}
		$this->statusBegin=true;
		return ($sai);
	}

	/** Monta todo o código de final da página
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function gEnd()
	{
		global $g__download;
		global $g__js;
//		$this->outputRecording=false;
		if (!$g__download)
		{
			if ($this->parameters['header']<>"false")
			{
				if (!$this->statusEnd)
				{
					$this->gOut("</body>\n");
					$this->gOut("</html>");
				}
				$sai=$this->statusEnd;
				$this->statusEnd=true;
				return ($sai);
			}
		}
	}

	/** Monta todo o código de início e final da página
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function gBody($txt)
	{
		$this->gBegin();
		$this->gOut($txt);
		$this->gEnd();
	}
	
	function gImage()
	{
		
	}	
	
}


 ?>
