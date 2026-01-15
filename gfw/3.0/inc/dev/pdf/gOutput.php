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

include_once $gPathDefault."gPdf.php";


class gOutput extends g_Output
{	
	public $pdf;
	public $tabBuffer="";
	
	function __construct($renderTo=gRENDER_REMOTE)
	{		
		$this->renderFile='relatorio.pdf';
		$this->renderType='pdf';
		if ($renderTo==gRENDER_REMOTE)
			$renderTo=gRENDER_DOWNLOAD;
			
		//parent::__construct($renderTo);

		gVar("pdf.headersize",10);
		$this->pdf=new gPdf("{charset: utf8}");
		$this->pdf->detailfontsize=10;
		if ($_REQUEST['process']=="showReport")
		{
			$this->pdf->logomark=true;
			$this->pdf->footer=true;
			$this->pdf->header=true;
		} else
		{
			$this->pdf->logomark=false;
			$this->pdf->footer=false;
			$this->pdf->header=false;
		}

		$this->pdf->Open();
			
		$this->gBegin();
	}
	
	/** Exibe mensagem com formatação de erro
	 * @author	giuliano
	 * @version	1.0 07-04-2009 13:44
	 * @param string $title Título
	 * @param string $content Conteúdo
	 */
	function gOnScreenError($title="",$content="")
	{
		$this->pdf->MultiCell(0,4,$title,"TB","L");
		$this->pdf->MultiCell(0,4,json_encode($content),"LR","L");
		$this->pdf->MultiCell(0,4,$_SERVER['REMOTE_ADDR']." - ".$_SERVER["PHP_SELF"]." - ".gDateTime(date("Y-m-d H:i:s")),"TB","L");
	}
		
	/** Monta todo o código de início da página
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function gBegin()
	{
		// só executa este código uma vez!
		//$sai=parent::gBegin();
		$sai=$this->statusBegin;
		if (!$sai)
		{
			$this->statusBegin=true;
			$this->pdf->SetTitle($this->reportTitle, $this->reportSubTitle, $this->reportFilter);
			$this->pdf->AddPage();
		}
		return($sai);
	}
	
	/** Monta todo o código de final da página
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function gEnd()
	{
		// só executa este código uma vez!
		if (!$this->statusEnd)
		{
			// monta código de saída
			if($this->pdf->state<3)
				$this->pdf->Close();
			$this->pdf->Output($this->renderFile,"D");
		}
		//$sai=parent::gEnd();
		return($sai);
	}
	
	/** Efetua um salto de linha
	 * @author	giuliano
	 * @version	1.0 07-04-2009 13:44
	 * @param integer $qtt Quantidade
	 */
	function gBr($qtt)
	{
		for ($f=0; $f<count($f); $f++)
			$this->pdf->nl();
	}
	
	
	/** Exibe um texto formatado ou não
	 * @author	giuliano
	 * @version	1.0 07-04-2009 13:44
	 * @param string $content Conteúdo
	 * @param string $style Estilo de apresentação
	 */
	function gMsg($content,$style=gNORMAL)
	{
		$salto=2;
		if ($style==gALERT)
		{
			$this->pdf->SetFont("arial",'','10');
			$salto=0;
		} elseif ($style==gTITLE)
		{
			$this->pdf->title=$content;
			$this->pdf->SetFont("arial",'B','14');
		} elseif ($style==gSUBTITLE)
		{
			$this->pdf->subtitle=$content;
			$this->pdf->SetFont("arial",'B','12');
		} elseif ($style==gMINITITLE)
		{
			$this->pdf->SetFont("arial",'B','10');
			$content=strtoupper($content);
			$salto=1;
		} elseif ($style==gFILTER)
		{
			$this->pdf->filter=$content;
			$this->pdf->SetFont("times",'I','11');
		} elseif ($style==gFOOTER)
		{
			$this->pdf->SetFont("times",'I','10');
		} else
		{
			$this->pdf->SetFont("arial",'','10');
			$salto=0;
		}
		$this->pdf->buffer.=$this->styleMessagePrefix[$style];
		if ($salto>0)
		{
			$this->pdf->MultiCell(0,3,$content,0,strtoupper(substr(gVar("global.align"),0,1)));
			$this->pdf->y+=$salto;
		} else
		{
			$this->pdf->Cell($this->pdf->w,4,$content,0);
		}
		$this->pdf->buffer.=$this->styleMessageSufix[$style];
		//parent::gMsg($content,$style);
	}

	function gTableBegin($style, $border)
	{
		$this->tabBuffer.=parent::gTableBegin($style, $border);
	}

	function gTableEnd()
	{
		$this->tabBuffer.=parent::gTableEnd();
		$this->pdf->WriteHTML($this->tabBuffer);
		$this->tabBuffer="";
	}

	function gTableRow($matrix, $style)
	{
		$this->tabBuffer.=parent::gTableRow($matrix, $style);
	}

	function gTable()
	{
	}

	function gImage()
	{
		
	}
	/** Concui o objeto
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
	
}


 ?>
