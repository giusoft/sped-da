<? 
/* ******** Não altere esta estrutura de cabeçalho !

[Cabeçalho]
Sistema      =
Módulo       =
Arquivo      = M:\giusoft\online\pdf\fpdf.php
Versão       = 01.1
Data Criação = 05-06-02
Modificado...= 19-06-02 (09:52:29) --> Giuliano --> Diminuição do tamanho do código
Criador      = Giuliano

*/ 
?>
<?php
set_time_limit(360);
define("gP_B",2);
define("gP_I",3);
define("gP_BI",4);
define("gP_T",5);
define('FPDF_VERSION','1.41');
define('FPDF_FONTPATH',$PathDefalut . 'font/');

class gPdf
{
//Private properties
var $page;               //current page number
var $n;                  //current object number
var $offsets;            //array of object offsets
var $buffer;             //buffer holding in-memory PDF
var $pages;              //array containing pages
var $state;              //current document state
var $compress;           //compression flag
var $DefOrientation;     //default orientation
var $CurOrientation;     //current orientation
var $OrientationChanges; //array indicating orientation changes
var $fwPt,$fhPt;         //dimensions of page format in points
var $fw,$fh;             //dimensions of page format in user unit
var $wPt,$hPt;           //current dimensions of page in points
var $w,$h;               //current dimensions of page in user unit
var $lMargin;            //left margin
var $tMargin;            //top margin
var $bMargin;            //page break margin
var $cMargin;            //cell margin
var $x,$y;               //current position in user unit for cell positionning
var $lasth;              //height of last cell printed
var $k;                  //scale factor (number of points in user unit)
var $LineWidth;          //line width in user unit
var $fontnames;          //array of Postscript (Type1) font names
var $fonts;              //array of used fonts
var $images;             //array of used images
var $FontFamily;         //current font family
var $FontStyle;          //current font style
var $FontSizePt;         //current font size in points
var $FontSize;           //current font size in user unit
var $DrawColor;          //commands for drawing color
var $FillColor;          //commands for filling color
var $TextColor;          //commands for text color
var $ColorFlag;          //indicates whether fill and text colors are different
var $ws;                 //word spacing
var $AutoPageBreak;      //automatic page breaking
var $PageBreakTrigger;   //threshold used to trigger page breaks
var $InFooter;           //flag set when processing footer
var $DisplayMode;        //display mode
var $title;              //title
var $subtitle;           //subtitle
var $filter;             //filter
var $headerdata;         //header data
var $detaildata;         //detail data
var $spacedata;          //space data
var $headerspacedata;	 //space data
var $detailfontsize;		 // detail font size
var $ydetail;		       // y detail default
var $subject;            //subject
var $author;             //author
var $keywords;           //keywords
var $creator;            //creator
var $AliasNbPages;       //alias for total number of pages
var $report;				 // determina se o PDF é do tipo relatório (com cabeçalho e rodapé formatados)
var $logomark;				 // determina se mostra ou não a logomarca e o rodapé
var $showfooter;			 // determina se mostra ou não o rodapé
var $txtfmt;	// formatação de texto para o comando Cell
var $txtbf;		// flag para bold do comando Cell
var $txtif;		// flag para italic do comando Cell
var $txtbfi;		// flag para bold do comando Cell
var $txtifi;		// flag para italic do comando Cell
var $txtini;
var $txttam;

/****************************************************************************
*                                                                           *
*                              Public methods                               *
*                                                                           *
****************************************************************************/
function gPdf($orientation='P',$unit='mm',$format='a4')
{
	//Initialization of properties
	$this->report=false;
	$this->logomark=true;
	$this->showfooter=true;
	$this->page=0;
	$this->n=2;
	$this->buffer='';
	$this->pages=array();
	$this->OrientationChanges=array();
	$this->state=0;
	$this->fonts=array();
	$this->images=array();
	$this->InFooter=false;
	$this->FontFamily='';
	$this->FontStyle='';
	$this->FontSizePt=10;
	$this->DrawColor='0 G';
	$this->FillColor='0 g';
	$this->TextColor='0 g';
	$this->ColorFlag=false;
	$this->ws=0;
	$this->tam=0;
	$this->detailfontsize=10;
	$this->wordwraplimit=30;
	//Font names
	$this->fontnames['courier']='Courier';
	$this->fontnames['courierB']='Courier-Bold';
	$this->fontnames['courierI']='Courier-Oblique';
	$this->fontnames['courierBI']='Courier-BoldOblique';
	$this->fontnames['helvetica']='Helvetica';
	$this->fontnames['helveticaB']='Helvetica-Bold';
	$this->fontnames['helveticaI']='Helvetica-Oblique';
	$this->fontnames['helveticaBI']='Helvetica-BoldOblique';
	$this->fontnames['times']='Times-Roman';
	$this->fontnames['timesB']='Times-Bold';
	$this->fontnames['timesI']='Times-Italic';
	$this->fontnames['timesBI']='Times-BoldItalic';
	$this->fontnames['symbol']='Symbol';
	$this->fontnames['zapfdingbats']='ZapfDingbats';
	$this->txtini=0;
	$this->txttam=65535;
	
	//Scale factor
	if($unit=='pt')
		$this->k=1;
	elseif($unit=='mm')
		$this->k=72/25.4;
	elseif($unit=='cm')
		$this->k=72/2.54;
	elseif($unit=='in')
		$this->k=72;
	else
		$this->Error('Incorrect unit: '.$unit);
	//Page format
	if(is_string($format))
	{
		$format=strtolower($format);
		if($format=='a3')
			$format=array(841.89,1190.55);
		elseif($format=='a4')
			$format=array(595.28,841.89);
		elseif($format=='a5')
			$format=array(420.94,595.28);
		elseif($format=='letter')
			$format=array(612,792);
		elseif($format=='legal')
			$format=array(612,1008);
		else
			$this->Error('Unknown page format: '.$format);
		$this->fwPt=$format[0];
		$this->fhPt=$format[1];
	}
	else
	{
		$this->fwPt=round($format[0]*$this->k,2);
		$this->fhPt=round($format[1]*$this->k,2);
	}
	$this->fw=round($this->fwPt/$this->k,2);
	$this->fh=round($this->fhPt/$this->k,2);
	//Page orientation
	$orientation=strtolower($orientation);
	if($orientation=='p' or $orientation=='portrait')
	{
		$this->DefOrientation='P';
		$this->wPt=$this->fwPt;
		$this->hPt=$this->fhPt;
	}
	elseif($orientation=='l' or $orientation=='landscape')
	{
		$this->DefOrientation='L';
		$this->wPt=$this->fhPt;
		$this->hPt=$this->fwPt;
	}
	else
		$this->Error('Incorrect orientation: '.$orientation);
	$this->CurOrientation=$this->DefOrientation;
	$this->w=round($this->wPt/$this->k,2);
	$this->h=round($this->hPt/$this->k,2);
	//Page margins (1 cm)
	$margin=round(28.35/$this->k,2);
	$this->SetMargins($margin,$margin);
	//Interior cell margin (1 mm)
	$this->cMargin=$margin/10;
	//Line width (0.2 mm)
	$this->LineWidth=round(.567/$this->k,3);
	//Automatic page break
	$this->SetAutoPageBreak(true,2*$margin);
	//Full width display mode
	$this->SetDisplayMode('fullpage');
	//Compression
	$this->SetCompression(true);
}

function SetMargins($left,$top)
{
	//Set left and top margins
	$this->lMargin=$left;
	$this->tMargin=$top;
}

function SetLeftMargin($margin)
{
	//Set left margin
	$this->lMargin=$margin;
}

function SetAutoPageBreak($auto,$margin=0)
{
	//Set auto page break mode and triggering margin
	$this->AutoPageBreak=$auto;
	$this->bMargin=$margin;
	$this->PageBreakTrigger=$this->h-$margin;
}

function SetDisplayMode($mode,$z=100)
{
	//Set display mode in viewer
	if($mode=='fullpage' or $mode=='fullwidth' or $mode=='real' or $mode=='default')
		$this->DisplayMode=$mode;
	elseif($mode=='zoom')
		$this->DisplayMode=$z;
	else
		$this->Error('Incorrect display mode: '.$mode);
}

function SetCompression($compress)
{
	//Set page compression
	if(function_exists('gzcompress'))
		$this->compress=$compress;
	else
		$this->compress=false;
}

function SetTitle($title,$subtitle,$filter)
{
	//Title of document
	$this->title=$title;
	//Subtitle of document
	$this->subtitle=$subtitle;
	//Filter of document
	$this->filter=$filter;
	//$this->author=end_site;
}

function SetHeaderdata($headerdata)
{
	//Header data of document
	$this->headerdata=$headerdata;
}

function SetSpacedata($spacedata)
{
	//Space between data of detail of document
	$this->spacedata=$spacedata;
}

function SetHeaderspacedata($spacedata)
{
	//Space between data of detail of document
	$this->headerspacedata=$spacedata;
}

function SetSubject($subject)
{
	//Subject of document
	$this->subject=$subject;
}

function SetAuthor($author)
{
	//Author of document
	$this->author=$author;
}

function SetKeywords($keywords)
{
	//Keywords of document
	$this->keywords=$keywords;
}

function SetCreator($creator)
{
	//Creator of document
	$this->creator=$creator;
}

function AliasNbPages($alias='{nb}')
{
	//Define an alias for total number of pages
	$this->AliasNbPages=$alias;
}

function Error($msg)
{
	//Fatal error
	die('<B>FPDF error: </B>'.$msg);
}

function Open()
{
	//Begin document
	$this->_begindoc();
}

function Close()
{
	//Terminate document
	if($page=$this->page==0)
		$this->Error('Document contains no page');
	//Page footer
	$this->InFooter=true;
	$this->Footer();
	$this->InFooter=false;
	//Close page
	$this->_endpage();
	//Close document
	$this->_enddoc();
}

function AddPage($orientation='')
{
	//Start a new page
	$family=$this->FontFamily;
	$style=$this->FontStyle;
	$size=$this->FontSizePt;
	$lw=$this->LineWidth;
	$dc=$this->DrawColor;
	$fc=$this->FillColor;
	$tc=$this->TextColor;
	$cf=$this->ColorFlag;
	if ($this->page>0)
	{
		//Page footer
		$this->InFooter=true;
		$this->Footer();
		$this->InFooter=false;
		//Close page
		$this->_endpage();
	}
	//Start new page
	$this->_beginpage($orientation);
	//Set line cap style to square
	$this->_out('2 J');
	//Set line width
	$this->_out($lw.' w');
	//Set font
	if($family)
		$this->SetFont($family,$style,$size);
	//Set colors
	if($dc!='0 G')
		$this->_out($dc);
	if($fc!='0 g')
		$this->_out($fc);
	$this->TextColor=$tc;
	$this->ColorFlag=$cf;
	//Page header
	$this->Header();
	//Restore line width
	if($this->LineWidth!=$lw)
	{
		$this->LineWidth=$lw;
		$this->_out($lw.' w');
	}
	//Restore font
	if($family)
		$this->SetFont($family,$style,$size);
	//Restore colors
	if($this->DrawColor!=$dc)
	{
		$this->DrawColor=$dc;
		$this->_out($dc);
	}
	if($this->FillColor!=$fc)
	{
		$this->FillColor=$fc;
		$this->_out($fc);
	}
	$this->TextColor=$tc;
	$this->ColorFlag=$cf;
}

function Header()
{
	$headerdata=$this->headerdata;
	$spacedata=$this->headerspacedata;
	$fonttam=$this->detailfontsize;

	global $gPathImg;
		$w=$this->w-$this->lMargin;
	if ($this->logomark)
	{
		if (gVar("pdf.logoalign")=="left")
		{
		// Logotipo
	$this->Image($gPathImg . gVar("pdf.logojpgfile"),$this->lMargin,9,intval(gVar("pdf.logowidth")),intval(gVar("pdf.logoheight")),"jpg");

		//Title
			$this->SetFont(gVar("pdf.font"),'B',$fonttam+5);
			//Calculate width of title and position
			$s=$this->GetStringWidth($this->title)+6;
			$this->x=$this->lMargin+($this->w-$this->lMargin)/2-$s/2;
			$this->Cell(0,7,$this->title,0,1,'L');
		// SubTitle
			//Arial bold 15
			$this->SetFont(gVar("pdf.font"),'B',$fonttam+3);
			//Calculate width of title and position
			$s=$this->GetStringWidth($this->subtitle)+6;
			$this->x=$this->lMargin+($this->w-$this->lMargin)/2-$s/2;
			$this->Cell(0,7,$this->subtitle,0,1,'L');
		// Filter
			//Arial bold 15
			$this->SetFont(gVar("pdf.font"),'I',$fonttam+1);
			//Calculate width of title and position
			$s=$this->GetStringWidth($this->filter)+6;
			$this->x=$this->lMargin+($this->w-$this->lMargin)/2-$s/2;
			$this->Cell(0,5,$this->filter,0,1,'L');
		}
		else
		{
		// Logotipo
		$this->Image($gPathImg . gVar("pdf.logojpgfile"),$w-intval(gVar("pdf.logowidth")),9,intval(gVar("pdf.logowidth")),intval(gVar("pdf.logoheight")),"jpg");
		//Title
			//Arial bold 15
			$this->SetFont(gVar("pdf.font"),'B',$fonttam+5);
			//$this->SetTextColor(0,0,254);
			//Calculate width of title and position
			$w=$this->GetStringWidth($this->title)+6;
			$this->Cell(0,7,$this->title,0,1,'L');
		// SubTitle
			//Arial bold 15
			$this->SetFont(gVar("pdf.font"),'B',$fonttam+3);
			//Calculate width of title and position
			$w=$this->GetStringWidth($this->subtitle)+6;
			$this->Cell(0,7,$this->subtitle,0,1,'L');
		// Filter
			//Arial bold 15
			$this->SetFont(gVar("pdf.font"),'I',$fonttam+1);
			//Calculate width of title and position
			$w=$this->GetStringWidth($this->filter)+6;
			$this->Cell(0,5,$this->filter,0,1,'L');
		}
		//Line break

		$this->Ln(3);
	}
		$car="";
		for ($f=0;$f<count($headerdata);$f++) $car.=$headerdata[$f]." ";
		$sai=false;
		$w=$this->w-$this->lMargin;
		while (!$sai)
		{
			$this->SetFont(gVar("pdf.font"),'B',$fonttam);
			if (($this->GetStringWidth($car)<$w) || ($fonttam<6))
				$sai=true;
			else
				$fonttam=$fonttam-1;
		}
		$tam=$this->GetStringWidth('0')+0.3;
		$this->tam=$tam;
		if ($this->report)
		{
			//Background color
			$this->SetFillColor(230,230,230);
			$this->Cell(0,5,'',0,1,'L',1);
			$y=$this->GetY()-1;
			$x=$this->GetX()+2;
			$h=5;
			//Header
			$cst=0;
			$yant=0;
			$maxy=0;
			$this->SetDrawColor(200,200,200);
			for($f=0;$f<count($headerdata);$f++)
			{
				$this->SetY($y);
				$txt=$headerdata[$f];
				$cs=0;
				$prox=$x+$spacedata[$f+$cst]*$tam;
				$xa=$x;
				if (substr($txt,0,1)=='~')
				{
					// Colspan
					$v=0;
					$cs=0+(substr($txt,1,1));
					for ($vv=1;$vv<($cs);$vv++)
						$v+=$spacedata[$f+$cst+$vv]*$tam;
					$cst+=$cs-1;
					$prox=$prox+$v;
					$txt=substr($txt,2,100);
					if ((substr($txt,0,2)<>'<-') and (substr($txt,0,2)<>'->') and (substr($txt,0,2)<>'<>'))
						$txt="<>".$txt;
				}
				if (substr($txt,0,2)=='->')
				{
					// Alinhado à direita
					$xa=$prox-$this->GetStringWidth($txt);
					$txt=substr($txt,2,100);
				}
				if (substr($txt,0,2)=='<-')
				{
					// Alinhado à esquerda
					$xa=$x;
					$txt=substr($txt,2,100);
				}
				if (substr($txt,0,2)=='<>')
				{
					// Centralizado
					$xa=$x+($prox-$x)/2-$this->GetStringWidth($txt)/2;
					$txt=substr($txt,2,100);
				}
				$txt=$this->tira_tags($txt);
				$txt=substr($txt,0,600);

				if (($x+$this->GetStringWidth($txt)>$prox))
				{
				// Início do código que costuma travar
					$yant=$y;
					$cnt=0;
					//while (strlen($txt)>0)
					{
						$txt2=$txt;
						$sai="nao";
						while (($x+$this->GetStringWidth($txt2)>$prox) && (strlen($txt2)>0) && ($sai=="nao"))
						{
							$p1=strlen($txt2)-strlen(strrchr($txt2," "));
							$p2=strlen($txt2)-strlen(strrchr($txt2,"#"));
							if (($p2<$p1) and ($p2>0))
								$p1=$p2;
							if ($p1>0)
							{
								$txt2=trim(substr($txt2,0,$p1-1));
								if (substr($txt2,strlen($txt2)-1,1)=="#") $txt2=substr($txt2,0,strlen($txt2)-2);
							}
							else
								$sai="sim";
								//$txt2=substr($txt2,0,strlen($txt2)-2);
						}
						$this->Text($xa,$y,$txt2);
						// Próxima linha
						$y=$y+$h-1;
						if($this->y+$h*4>$this->PageBreakTrigger)
						{
							//$x=$this->x;
						if (gVar("pdf.headersize")>0)
							$y=gVar("pdf.headersize");
						else
							$y=28;
							//$y=$this->ydetail+$h;

							$ws=$this->ws;
							if($ws>0)
								$this->_out('0 Tw');
							$this->AddPage($this->CurOrientation);
							$this->x=$x;
							$this->y=$y;

							if($ws>0)
								$this->_out($ws.' Tw');
						}

						$txt=substr($txt,strlen($txt2),600);
						$cnt++;
						if ($cnt>20) $txt="";
					}
					//$this->Line($x,$y-2,$prox,$y-2);
					if ($y>$maxy)
						$maxy=$y;
					$this->SetY($yant);
					$y=$yant;

				// Final do código que costuma travar
				} else
					$this->Text($xa,$y,$txt);
				$x=$prox;

			}
			//Line break
			$this->Ln(4);
			$w=$this->w-$this->lMargin;
			$this->Line($this->lMargin,$y+1,$w,$y+1);
			$this->ydetail=$y;
		}
		if (gVar("pdf.headersize")>0)
			$this->y=gVar("pdf.headersize");
		else
			$this->y=28;
}

function tira_tags($texto)
{

$texto=str_replace("<b>","",$texto)      ;$texto=str_replace("<B>","",$texto);
$texto=str_replace("</b>","",$texto)     ;$texto=str_replace("</B>","",$texto);
$texto=str_replace("<i>","",$texto)      ;$texto=str_replace("<I>","",$texto);
$texto=str_replace("</i>","",$texto)     ;$texto=str_replace("</I>","",$texto);
$texto=str_replace("<br>",chr(13),$texto);$texto=str_replace("<BR>",chr(13),$texto);
$texto=str_replace("&nbsp;"," ",$texto);
$texto=str_replace("&nbsp"," ",$texto);
$texto=str_replace(chr(13),"#",$texto);

return $texto;
}

function CalcFormat($table)
{
	// Em desenvolvimento !!!!

	// Ajusta o tamanho máximo dos campos
	$nspc="";
	$width="";
	for ($a=0; $a<count($table);$a++)
	{
		$row=$table[$a];
		$cs=0;
		for ($b=0;$b<count($row);$b++)
		{
			$col=$row[$b];
			$size=strlen(html2str($col[0]));
			if ($size<4) $size=4;
			//if ($size>$this->wordwraplimit) $size=$this->wordwraplimit;
			if (($width[$b+$cs]<$size) || ($width[$b+$cs]==''))
				$width[$b+$cs]=$size;
			if (strpos($col[2],'=')>0)
			{
				$nr=intval(str_replace("colspan=",'',str_replace("'",'',$col[2])));
				$cs=$cs+$nr-1;
			}
		}
	}

	$this->spacedata =$width;
}

function DrawTextLine($detaildata,$style=0)
{
/*
	$detaildata -> vetor com as colunas
	$style      -> tipo de linha:
				0 - Detalhe comum
				1 - Sumário (Negrito e itálico)
				2 - Negrito
*/
	global $ydetail;
	$headerdata=$this->headerdata;
	$spacedata=$this->spacedata;
	$fonttam=$this->detailfontsize;

	if (count($spacedata)==0)
	{
		for ($a=0; $a<count($detaildata); $a++)
		{
			$spacedata[]=strlen($detaildata[$a]);
		}
	}
	$h=4;
	$this->SetAutoPageBreak(0,0);
	$car="";
	for ($f=0;$f<count($headerdata);$f++) $car.=$headerdata[$f]." ";
	$sai=false;
	$w=$this->w-$this->lMargin;
	while (!$sai)
	{
		$this->SetFont(gVar("pdf.font"),'B',$fonttam);
	  	if (($this->GetStringWidth($car)<$w) || ($fonttam<6))
			$sai=true;
		else
		{
			$fonttam--;
			$h=$h-1;
		}
	}
	//$tam=$this->GetStringWidth('0')+0.3;
	$tam=$this->tam;
	if ($style==0)
		$this->SetFont(gVar("pdf.font"),'',$fonttam);
	elseif ($style==1)
		$this->SetFont(gVar("pdf.font"),'BI',$fonttam);
	elseif ($style==2)
		$this->SetFont(gVar("pdf.font"),'B',$fonttam);
	elseif ($style==3)
		$this->SetFont(gVar("pdf.font"),'I',$fonttam);
	elseif ($style==4)
		$this->SetFont(gVar("pdf.font"),'BI',$fonttam);
	elseif ($style==5)
		$this->SetFont(gVar("pdf.font"),'B',$fonttam+2);
	elseif ($style==6)
		$this->SetFont(gVar("pdf.font"),'',$fonttam-1);
	$y=$this->GetY()+1;
	$x=$this->GetX()+2;
	//Header

	if($this->y+$h*4>$this->PageBreakTrigger)
	{
		//$x=$this->x;
		//$y=$this->ydetail+$h;
		if (gVar("pdf.headersize")>0)
			$y=gVar("pdf.headersize");
		else
			$y=28;

		$ws=$this->ws;
		if($ws>0)
			$this->_out('0 Tw');
		$this->AddPage($this->CurOrientation);
		$this->x=$x;
		$this->y=$y;

		if($ws>0)
			$this->_out($ws.' Tw');
	}
	if (($style==1) || ($style==6))
	{
		$w=$this->w-$this->lMargin;
		$this->Line($this->lMargin,$y-3,$w,$y-3);
	}
	$cst=0;
	$yant=0;
	$maxy=0;
	$this->SetDrawColor(200,200,200);
	for($f=0;$f<count($detaildata);$f++)
	{
		$this->SetY($y);
		$txt=$detaildata[$f];
		$cs=0;
		$prox=$x+$spacedata[$f+$cst]*$tam;
		$xa=$x;

		if (substr($txt,0,1)=='~')
		{
			// Colspan

			$v=0;
			$cs=0+intval(substr($txt,1,2));


			for ($vv=1;$vv<($cs);$vv++)
				$v+=$spacedata[$f+$cst+$vv]*$tam;
			$cst+=$cs-1;
			$prox=$prox+$v;
			if ($cs<10)
				$txt=substr($txt,2);
			else
				$txt=substr($txt,3);
			if ((substr($txt,0,2)<>'<-') and (substr($txt,0,2)<>'->') and (substr($txt,0,2)<>'<>'))
				$txt="<>".$txt;

		}
		if (substr($txt,0,2)=='->')
		{
			// Alinhado à direita
			$xa=$prox-$this->GetStringWidth($txt);
			$txt=substr($txt,2);
		}
		if (substr($txt,0,2)=='<-')
		{
			// Alinhado à esquerda
			$xa=$x;
			$txt=substr($txt,2);
		}
		if (substr($txt,0,2)=='<>')
		{
			// Centralizado
			$xa=$x+($prox-$x)/2-$this->GetStringWidth($txt)/2;
			$txt=substr($txt,2);
		}


		$txt=$this->tira_tags($txt);
		$txt=substr($txt,0,600);
		$txt=str_replace("##","#",$txt);
		$txt=str_replace("##","#",$txt);
		$txt=str_replace("##","#",$txt);
		if ($x+$this->GetStringWidth($txt)>$prox)
		{
			$yant=$y;
			while (strlen($txt)>0)
			{
				$txt2=$txt;
				$p2=strpos($txt2,"#");
				if ($p2>0)
				{
					$txt2=substr($txt,0,$p2-1);
					$txt2=$txt2." ";
				}
				while (($x+$this->GetStringWidth($txt2)>$prox) and (strlen($txt2)>0))
				{
					if (!strrchr($txt2," "))
					{
						$p1=strlen($txt2)-1;
						$txt2=trim(substr($txt2,0,$p1));
					}
					else
					{
						$p1=strlen($txt2)-strlen(strrchr($txt2," "));
						$txt2=trim(substr($txt2,0,$p1));
					}
				}
				if (trim($txt2)!="")
				{
					$this->Text($xa,$y,trim($txt2));
					// Próxima linha
					$y=$y+$h-1;
					if($this->y+$h*4>$this->PageBreakTrigger)
					{
						//$x=$this->x;
//						$y=$this->ydetail+$h;
						if (gVar("pdf.headersize")>0)
							$y=gVar("pdf.headersize");
						else
							$y=28;

						$ws=$this->ws;
						if($ws>0)
							$this->_out('0 Tw');
						$this->AddPage($this->CurOrientation);
						$this->x=$x;
						$this->y=$y;

						if($ws>0)
							$this->_out($ws.' Tw');
					}
				}
				$txt=substr($txt,strlen($txt2)+1,600);
        if (substr($txt,strlen($txt)-1,1)=="#") $txt=substr($txt,0,strlen($txt)-2);
			}
			$this->Line($x,$y-2,$prox-$tam,$y-2);
			if ($y>$maxy)
				$maxy=$y;
			$this->SetY($yant);
			$y=$yant;

		} else
		{
			$txt=str_replace("#","",$txt);
			$this->Text($xa,$y,$txt);
		}
		$x=$prox;

	}
	//Line break
	if ($maxy>0)
		$this->SetY($maxy-$h+2);
	$this->Ln(3);
	$this->SetFont(gVar("pdf.font"),'',$fonttam);


}

function Detail($detaildata)
{
	$this->DrawTextLine($detaildata,0);
}

function SubDetail($detaildata)
{
	$this->DrawTextLine($detaildata,1);
}

function Footer()
{
	if ($this->showfooter)
	{
		if ($this->logomark)
		{
			//Position at 1.5 cm from bottom
			$this->SetY(-15);
			//Arial italic 8
			$this->SetFont(gVar("pdf.font"),'I',8);
			//Text color in gray
			$this->SetTextColor(128);
			$this->SetDrawColor(128);
			//Page number LineWidth
			$this->Cell(0,10,gLng('page_no.long').' '.$this->PageNo(),0,0,'R');
			$this->SetY(-15);
			$this->Cell(0,10,gVar("pdf.footer1"),0,0,'C');
			$this->SetY(-12);
			$this->Cell(0,10,gVar("pdf.footer2"),0,0,'C');
			$this->SetY(-9);
			$this->Cell(0,10,gVar("pdf.footer3"),0,0,'C');
			$this->SetY(-15);
			$fmt=gVar("global.dateformat");
			$fmt=str_replace('yyyy','Y',$fmt);
			$fmt=str_replace('yy','y',$fmt);
			$fmt=str_replace('mm','m',$fmt);
			$fmt=str_replace('dd','d',$fmt);
			$this->Cell(0,10,gLng('print_date.long')." ".date("$fmt H:m"),0,0,'L');
			$this->SetY(-15);
		} else
		{
			//Position at 1.5 cm from bottom
			$this->SetY(-20);
			//Arial italic 8
			$this->SetFont(gVar("pdf.font"),'',8);
			//Text color in gray
			$this->SetTextColor(128);
			$this->SetDrawColor(128);
			//Page number LineWidth
			$this->Cell(0,10,gVar("pdf.footer1"),0,0,'C');
			$this->SetY(-15);
			$this->Cell(0,10,gVar("pdf.footer2"),0,0,'C');
		}
	}
}

function PageNo()
{
	//Get current page number
	return $this->page;
}

function SetDrawColor($r,$g=-1,$b=-1)
{
	//Set color for all stroking operations
	if(($r==0 and $g==0 and $b==0) or $g==-1)
		$this->DrawColor=substr($r/255,0,5).' G';
	else
		$this->DrawColor=substr($r/255,0,5).' '.substr($g/255,0,5).' '.substr($b/255,0,5).' RG';
	if($this->page>0)
		$this->_out($this->DrawColor);
}

function SetFillColor($r,$g=-1,$b=-1)
{
	//Set color for all filling operations
	if(($r==0 and $g==0 and $b==0) or $g==-1)
		$this->FillColor=substr($r/255,0,5).' g';
	else
		$this->FillColor=substr($r/255,0,5).' '.substr($g/255,0,5).' '.substr($b/255,0,5).' rg';
	$this->ColorFlag=($this->FillColor!=$this->TextColor);
	if($this->page>0)
		$this->_out($this->FillColor);
}

function SetTextColor($r,$g=-1,$b=-1)
{
	//Set color for text
	if(($r==0 and $g==0 and $b==0) or $g==-1)
		$this->TextColor=substr($r/255,0,5).' g';
	else
		$this->TextColor=substr($r/255,0,5).' '.substr($g/255,0,5).' '.substr($b/255,0,5).' rg';
	$this->ColorFlag=($this->FillColor!=$this->TextColor);
}


function GetStringWidth($s)
{
	//Get width of a string in the current font
	global $fpdf_charwidths;

	$cw=&$fpdf_charwidths[$this->FontFamily.$this->FontStyle];
	$w=0;
	$l=strlen($s);
	for($i=0;$i<$l;$i++)
		$w+=$cw[$s[$i]];
	return $w*$this->FontSize/1000;
}

function GetStringWidthWiki($s)
{
	//Get width of a string in the current font
	global $fpdf_charwidths;

	$cww['X']=&$fpdf_charwidths[$this->FontFamily];
	$cww['B']=&$fpdf_charwidths[$this->FontFamily.'B'];
	$cww['I']=&$fpdf_charwidths[$this->FontFamily.'I'];
	$cww['BI']=&$fpdf_charwidths[$this->FontFamily.'BI'];
	$w=0;
	$l=strlen($s);
	$txtbf=$this->txtbfi;
	$txtif=$this->txtifi;
	for($i=0;$i<$l;$i++)
	{
		$style='';
		if ($this->txtfmt[$i+$this->txtini]=='B')
		{
			$txtbf=!$txtbf;
		}
		elseif ($this->txtfmt[$i+$this->txtini]=='I')
		{
			$txtif=!$txtif;
		}
		if ($txtbf) $style.='B';	
		if ($txtif) $style.='I';
		if ($style=='') $style='X';
		$cw=$cww[$style];
		$w+=$cw[$s[$i]];
	}
	return $w*$this->FontSize/1000;
}

function SetLineWidth($width)
{
	//Set line width
	$this->LineWidth=$width;
	if($this->page>0)
		$this->_out($width.' w');
}

function Line($x1,$y1,$x2,$y2)
{
	//Draw a line
	$this->_out($x1.' -'.$y1.' m '.$x2.' -'.$y2.' l S');
}

function Rect($x,$y,$w,$h,$style='')
{
	//Draw a rectangle
	if($style=='F')
		$op='f';
	elseif($style=='FD' or $style=='DF')
		$op='B';
	else
		$op='S';
	$this->_out($x.' -'.$y.' '.$w.' -'.$h.' re '.$op);
}

function SetFont($family,$style='',$size=0)
{
	//Select a font; size given in points
	if(!$this->_setfont($family,$style,$size))
		$this->Error('Incorrect font family or style: '.$family.' '.$style);
}

function SetFontSize($size)
{
	//Set font size in points
	$this->_setfontsize($size);
}

function Text($x,$y,$txt)
{
	//Output a string
	$txt=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
	$s='BT '.$x.' -'.$y.' Td ('.$txt.') Tj ET';
	if($this->ColorFlag)
		$s='q '.$this->TextColor.' '.$s.' Q';
	$this->_out($s);
}

function AcceptPageBreak()
{
	//Accept automatic page break or not
	return $this->AutoPageBreak;
}

function Cell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=0)
{
	//Output a cell
	$this->txtbfi=$txtbf;
	$this->txtifi=$txtif;
	if($this->y+$h>$this->PageBreakTrigger and !$this->InFooter and $this->AcceptPageBreak())
	{
		$x=$this->x;
		$ws=$this->ws;
		if($ws>0)
			$this->_out('0 Tw');
		$this->AddPage($this->CurOrientation);
		$this->x=$x;
		if($ws>0)
			$this->_out($ws.' Tw');
	}
	if($w==0)
		$w=$this->w-$this->lMargin-$this->x;
	$s='';
	if($fill==1 or $border==1)
	{
		$s.=$this->x.' -'.$this->y.' '.$w.' -'.$h.' re ';
		if($fill==1)
			$s.=($border==1) ? 'B ' : 'f ';
		else
			$s.='S ';
	}
	if(is_string($border))
	{
		$x=$this->x;
		$y=$this->y;
		if(is_int(strpos($border,'L')))
			$s.=$x.' -'.$y.' m '.$x.' -'.($y+$h).' l S ';
		if(is_int(strpos($border,'T')))
			$s.=$x.' -'.$y.' m '.($x+$w).' -'.$y.' l S ';
		if(is_int(strpos($border,'R')))
			$s.=($x+$w).' -'.$y.' m '.($x+$w).' -'.($y+$h).' l S ';
		if(is_int(strpos($border,'B')))
			$s.=$x.' -'.($y+$h).' m '.($x+$w).' -'.($y+$h).' l S ';
	}
	if($txt!='')
	{
		if($align=='R')
			$dx=$w-$this->cMargin-$this->GetStringWidthWiki($txt);
		elseif($align=='C')
			$dx=($w-$this->GetStringWidthWiki($txt))/2;
		else
			$dx=$this->cMargin;
		$txt=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
		if($this->ColorFlag)
			$s.='q '.$this->TextColor.' ';
		$s.='BT '.intval($this->x+$dx).' -'.intval($this->y+.5*$h+.3*$this->FontSize).' Td (';
		//$s.=$txt;
		// ----
		$rod=') Tj ET';
		if($this->ColorFlag)
			$rod.=' Q';
		$txt2="";
		$fonttam=$this->detailfontsize;
		$st=$this->FontStyle;
		for ($f=0; $f<strlen($txt); $f++)
		{
			if ($this->txtfmt[$f+$this->txtini]!='')
			{
				$this->FontStyle=$st;
				$dx2=$this->GetStringWidthWiki(substr($txt,0,$f));
				$style='';
				if ($this->txtfmt[$f+$this->txtini]=='B')
				{
					$this->txtbf=!$this->txtbf;
				}
				elseif ($this->txtfmt[$f+$this->txtini]=='I')
				{
					$this->txtif=!$this->txtif;
				}
				if ($this->txtbf) $style.='B';	
				if ($this->txtif) $style.='I';
				$font=$this->_setfont_sai($this->FontFamily,$style,$fonttam)."\n";
				$cab='BT '.intval($this->x+$dx+$dx2).' -'.intval($this->y+.5*$h+.3*$this->FontSize).' Td (';
				$txt2.=$rod."\n".$font.$cab;				
			}
			$txt2.=substr($txt,$f,1);
		}
		$s.=$txt2;
		// ----
	}
	$s.=$rod;
	gLog("---->\n\n".$s."\n");
	if($s)
		$this->_out($s);
	$this->lasth=$h;
	if($ln>0)
	{
		//Go to next line
		$this->y+=$h;
		if($ln==1)
			$this->x=$this->lMargin;
	}
	else
		$this->x+=$w;
}

function MultiCell($w,$h,$txt,$border=0,$align='J',$fill=0)
{
	//Output text with automatic or explicit line breaks
	global $fpdf_charwidths;
	$this->SetAutoPageBreak(0,0);
	
	// convertendo o texto recebido em formato Wiki, separando texto de formatação
	$txtwiki=$txt;
	$txt=str_replace("__",'',$txt);
	$txt=str_replace("''",'',$txt);
	$txt=str_replace("&#9679;","\225",$txt);
	$txt=str_replace("#","\225",$txt);
	
	$this->txtfmt="";
	for ($a=0; $a<strlen($txtwiki)-1; $a++)
	{
		if (substr($txtwiki,$a,2)=='__')
		{
			$this->txtfmt[]='B';
			$a++;$a++;
		}
		elseif (substr($txtwiki,$a,2)=="''")
		{
			$this->txtfmt[]='I';			
			$a++;$a++;
		}
		else
			$this->txtfmt[]='';
	}
	
	
	$cw=&$fpdf_charwidths[$this->FontFamily.$this->FontStyle];
	$cww['']=&$fpdf_charwidths[$this->FontFamily];
	$cww['B']=&$fpdf_charwidths[$this->FontFamily.'B'];
	$cww['I']=&$fpdf_charwidths[$this->FontFamily.'I'];
	$cww['BI']=&$fpdf_charwidths[$this->FontFamily.'BI'];
	
	if($w==0)
		$w=$this->w-$this->lMargin-$this->x;
	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	$s=str_replace("\r",'',$txt);
	$nb=strlen($s);
	if($nb>0 and $s[$nb-1]=="\n")
		$nb--;
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$ns=0;
	$nl=1;
	$hh=4;
	$this->txtbf=false;
	$this->txtif=false;
	$txtbf=false;
	$txtif=false;
	while($i<$nb)
	{
		//Get next character
		$c=$s[$i];
		$style='';
		if ($this->txtfmt[$i]=='B')
		{
			$txtbf=!$txtbf;
		}
		elseif ($this->txtfmt[$i]=='I')
		{
			$txtif=!$txtif;
		}
		if ($txtbf) $style.='B';	
		if ($txtif) $style.='I';
	
		if($c=="\n")
		{
			//Explicit line break
			if($align=='J')
			{
				$this->ws=0;
				$this->_out('0 Tw');
			}
			$this->txtini=$j;
			$this->txttam=$i-$j;
			$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,$align,$fill);
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			$ns=0;
			if($border)
			{
				$x=$this->x;
				$y=$this->y-$h;
				if($nl==1)
					$this->_out($x.' -'.$y.' m '.($x+$w).' -'.$y.' l');
				$this->_out($x.' -'.$y.' m '.$x.' -'.$this->y.' l '.($x+$w).' -'.$y.' m '.($x+$w).' -'.$this->y.' l S');
			}
			$nl++;
			continue;
		}
		if($c==' ')
		{
			$sep=$i;
			$ls=$l;
			$ns++;
		}
		$CW=$cww[$style];
		$l+=$CW[$c];
		if($l>$wmax)
		{
			//Automatic line break
			if($sep==-1)
			{
				if($i==$j)
					$i++;
				if($align=='J')
				{
					$this->ws=0;
					$this->_out('0 Tw');
				}
				$this->txtini=$j;
				$this->txttam=$i-$j;
				$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,$align,$fill);
			}
			else
			{
				if($align=='J')
				{
					$this->ws=($ns>1) ? round(($wmax-$ls)/1000*$this->FontSize/($ns-1),3) : 0;
					$this->_out($this->ws.' Tw');
				}
				$this->txtini=$j;
				$this->txttam=$sep-$j;
				$this->Cell($w,$h,substr($s,$j,$sep-$j),0,2,$align,$fill);
				$i=$sep+1;
			}
			$sep=-1;
			$j=$i;
			$l=0;
			$ns=0;
			if($border)
			{
				$x=$this->x;
				$y=$this->y-$h;
				if($nl==1)
					$this->_out($x.' -'.$y.' m '.($x+$w).' -'.$y.' l');
				$this->_out($x.' -'.$y.' m '.$x.' -'.$this->y.' l '.($x+$w).' -'.$y.' m '.($x+$w).' -'.$this->y.' l S');
			}
			$nl++;
		}
		else
			$i++;
		if($this->y+$hh*4>$this->PageBreakTrigger )
		{
			$x=$this->lMargin;
			$ws=$this->ws;
			if($ws>0)
				$this->_out('0 Tw');
			$this->AddPage($this->CurOrientation);
			$y=$this->ydetail+$hh;
			if (gVar("pdf.headersize")>0)
				$y=gVar("pdf.headersize");
			else
				$y=28;
			$this->x=$x;
			$this->y=$y;
			//$h=$y;
			if($ws>0)
				$this->_out($ws.' Tw');
		}
	}
	//Last chunk
	if($align=='J')
	{
		$this->ws=0;
		$this->_out('0 Tw');
	}
	$this->Cell($w,$h,substr($s,$j,$i),0,2,$align,$fill);
	if($border)
	{
		$x=$this->x;
		$y=$this->y-$h;
		if($nl==1)
			$this->_out($x.' -'.$y.' m '.($x+$w).' -'.$y.' l');
		$this->_out($x.' -'.$y.' m '.$x.' -'.$this->y.' l '.($x+$w).' -'.$this->y.' l '.($x+$w).' -'.$y.' l S');
	}
	
	$this->x=$this->lMargin;
}

function Image($file,$x,$y,$w,$h=0,$type='')
{
	//Put an image on the page
	if(!isset($this->images[$file]))
	{
		//First use of image, get info
		if($type=='')
		{
			$pos=strrpos($file,'.');
			if(!$pos)
				$this->Error('Image file has no extension and no type was specified: '.$file);
			$type=substr($file,$pos+1);
		}
		$type=strtolower($type);
		$mqr=get_magic_quotes_runtime();
		set_magic_quotes_runtime(0);
		if($type=='jpg' or $type=='jpeg')
			$info=$this->_parsejpg($file);
		elseif($type=='png')
			$info=$this->_parsepng($file);
		else
			$this->Error('Unsupported image file type: '.$type);
		set_magic_quotes_runtime($mqr);
		$info['n']=count($this->images)+1;
		$this->images[$file]=$info;
	}
	else
		$info=$this->images[$file];
	//Automatic width or height calculus
	if($w==0)
		$w=round($h*$info['w']/$info['h'],2);
	if($h==0)
		$h=round($w*$info['h']/$info['w'],2);
	$this->_out('q '.$w.' 0 0 '.$h.' '.$x.' -'.($y+$h).' cm /I'.$info['n'].' Do Q');
}
function nl($qtd=1)
{
	while ($qtd>0)
	{
		$this->Ln();
		$qtd--;
	}
}

function Ln($h='')
{
	//Line feed; default value is last cell height
	$this->x=$this->lMargin;
	if(is_string($h))
		$this->y+=$this->lasth;
	else
		$this->y+=$h;
}

function GetX()
{
	//Get x position
	return $this->x;
}

function SetX($x)
{
	//Set x position
	if($x>=0)
		$this->x=$x;
	else
		$this->x=$this->w+$x;
}

function GetY()
{
	//Get y position
	return $this->y;
}

function SetY($y)
{
	//Set y position and reset x
	$this->x=$this->lMargin;
	if($y>=0)
		$this->y=$y;
	else
		$this->y=$this->h+$y;
}

function SetXY($x,$y)
{
	//Set x and y positions
	$this->SetY($y);
	$this->SetX($x);
}

function Output($file='',$download=false)
{
    
    //Output PDF to file or browser
	$HTTP_ENV_VARS=$_SERVER;

	if($this->state<3)
		$this->Close();
	if($file=='')
	{
		//Send to browser
		Header('Content-Type: application/pdf');
		if(headers_sent())
			$this->Error('Some data has already been output to browser, can\'t send PDF file');
		Header('Content-Length: '.strlen($this->buffer));
		Header('Content-disposition: inline; filename=doc.pdf');
	echo $this->buffer;
	}
	else
	{
	
		if($download)
		{
            //Download file
			if(isset($HTTP_ENV_VARS['HTTP_USER_AGENT']) and strpos($HTTP_ENV_VARS['HTTP_USER_AGENT'],'MSIE 6'))
			{
				header('Content-type: application/pdf');
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
				header("Pragma: public");
        			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			}
			elseif(isset($HTTP_ENV_VARS['HTTP_USER_AGENT']) and strpos($HTTP_ENV_VARS['HTTP_USER_AGENT'],'MSIE 5.5'))
				 Header('Content-Type: application/dummy');
			else
				Header('Content-Type: application/octet-stream');
			if(headers_sent())
				$this->Error('Some data has already been output to browser, can\'t send PDF file 1');
			Header('Content-Length: '.strlen($this->buffer));
			Header('Content-disposition: attachment; filename='.$file);
            
			echo $this->buffer;
		}
		else
		{
			//Save file locally
			$f=fopen($file,'wb');
			if(!$f)
				$this->Error('Unable to create output file: '.$file);
			fwrite($f,$this->buffer,strlen($this->buffer));
			fclose($f);
		}
	}
}

/****************************************************************************
*                                                                           *
*                              Private methods                              *
*                                                                           *
****************************************************************************/
function _begindoc()
{
	//Start document
	$this->state=1;
	$this->_out('%PDF-1.3');
}

function _enddoc()
{
	//Terminate document
	$nb=$this->page;
	if(!empty($this->AliasNbPages))
	{
		//Replace number of pages
		for($n=1;$n<=$nb;$n++)
			$this->pages[$n]=str_replace($this->AliasNbPages,$nb,$this->pages[$n]);
	}
	if($this->DefOrientation=='P')
	{
		$wPt=$this->fwPt;
		$hPt=$this->fhPt;
	}
	else
	{
		$wPt=$this->fhPt;
		$hPt=$this->fwPt;
	}
	$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
	for($n=1;$n<=$nb;$n++)
	{
		//Page
		$this->_newobj();
		$this->_out('<</Type /Page');
		$this->_out('/Parent 1 0 R');
		if(isset($this->OrientationChanges[$n]))
			$this->_out('/MediaBox [0 0 '.$hPt.' '.$wPt.']');
		$this->_out('/Resources 2 0 R');
		$this->_out('/Contents '.($this->n+1).' 0 R>>');
		$this->_out('endobj');
		//Page content
		$p=($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
		$this->_newobj();
		$this->_out('<<'.$filter.'/Length '.strlen($p).'>>');
		$this->_out('stream');
		$this->_out($p.'endstream');
		$this->_out('endobj');
	}
	//Fonts
	$nf=$this->n;
	reset($this->fonts);
	while(list($name)=each($this->fonts))
	{
		$this->_newobj();
		$this->_out('<</Type /Font');
		$this->_out('/Subtype /Type1');
		$this->_out('/BaseFont /'.$name);
		if($name!='Symbol' and $name!='ZapfDingbats')
			$this->_out('/Encoding /WinAnsiEncoding');
		$this->_out('>>');
		$this->_out('endobj');
	}
	//Images
	$ni=$this->n;
	reset($this->images);
	while(list($file,$info)=each($this->images))
	{
		$this->_newobj();
		$this->_out('<</Type /XObject');
		$this->_out('/Subtype /Image');
		$this->_out('/Width '.$info['w']);
		$this->_out('/Height '.$info['h']);
		if($info['cs']=='Indexed')
			$this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
		else
			$this->_out('/ColorSpace /'.$info['cs']);
		$this->_out('/BitsPerComponent '.$info['bpc']);
		$this->_out('/Filter /'.$info['f']);
		if(isset($info['parms']))
			$this->_out($info['parms']);
		if(isset($info['trns']) and is_array($info['trns']))
		{
			$trns='';
			for($i=0;$i<count($info['trns']);$i++)
				$trns.=$info['trns'][$i].' '.$info['trns'][$i].' ';
			$this->_out('/Mask ['.$trns.']');
		}
		$this->_out('/Length '.strlen($info['data']).'>>');
		$this->_out('stream');
		$this->_out($info['data']);
		$this->_out('endstream');
		$this->_out('endobj');
		//Palette
		if($info['cs']=='Indexed')
		{
			$this->_newobj();
			$this->_out('<</Length '.strlen($info['pal']).'>>');
			$this->_out('stream');
			$this->_out($info['pal']);
			$this->_out('endstream');
			$this->_out('endobj');
		}
	}
	//Pages root
	$this->offsets[1]=strlen($this->buffer);
	$this->_out('1 0 obj');
	$this->_out('<</Type /Pages');
	$kids='/Kids [';
	for($i=0;$i<$this->page;$i++)
		$kids.=(3+2*$i).' 0 R ';
	$this->_out($kids.']');
	$this->_out('/Count '.$this->page);
	$this->_out('/MediaBox [0 0 '.$wPt.' '.$hPt.']');
	$this->_out('>>');
	$this->_out('endobj');
	//Resources
	$this->offsets[2]=strlen($this->buffer);
	$this->_out('2 0 obj');
	$this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
	$this->_out('/Font <<');
	for($i=1;$i<=count($this->fonts);$i++)
		$this->_out('/F'.$i.' '.($nf+$i).' 0 R');
	$this->_out('>>');
	if(count($this->images))
	{
		$this->_out('/XObject <<');
		$nbpal=0;
		reset($this->images);
		while(list(,$info)=each($this->images))
		{
			$this->_out('/I'.$info['n'].' '.($ni+$info['n']+$nbpal).' 0 R');
			if($info['cs']=='Indexed')
				$nbpal++;
		}
		$this->_out('>>');
	}
	$this->_out('>>');
	$this->_out('endobj');
	//Info
	$this->_newobj();
	$this->_out('<</Producer (FPDF '.FPDF_VERSION.')');
	if(!empty($this->title))
		$this->_out('/Title ('.$this->_escape($this->title).')');
	if(!empty($this->subject))
		$this->_out('/Subject ('.$this->_escape($this->subject).')');
	if(!empty($this->author))
		$this->_out('/Author ('.$this->_escape($this->author).')');
	if(!empty($this->keywords))
		$this->_out('/Keywords ('.$this->_escape($this->keywords).')');
	if(!empty($this->creator))
		$this->_out('/Creator ('.$this->_escape($this->creator).')');
	$this->_out('/CreationDate (D:'.date('YmdHis').')>>');
	$this->_out('endobj');
	//Catalog
	$this->_newobj();
	$this->_out('<</Type /Catalog');
	if($this->DisplayMode=='fullpage')
		$this->_out('/OpenAction [3 0 R /Fit]');
	elseif($this->DisplayMode=='fullwidth')
		$this->_out('/OpenAction [3 0 R /FitH null]');
	elseif($this->DisplayMode=='real')
		$this->_out('/OpenAction [3 0 R /XYZ null null 1]');
	else
		$this->_out('/OpenAction [3 0 R /XYZ null null '.($this->DisplayMode/100).']');
	$this->_out('/Pages 1 0 R>>');
	$this->_out('endobj');
	//Cross-ref
	$o=strlen($this->buffer);
	$this->_out('xref');
	$this->_out('0 '.($this->n+1));
	$this->_out('0000000000 65535 f ');
	for($i=1;$i<=$this->n;$i++)
		$this->_out(sprintf('%010d 00000 n ',$this->offsets[$i]));
	//Trailer
	$this->_out('trailer');
	$this->_out('<</Size '.($this->n+1));
	$this->_out('/Root '.$this->n.' 0 R');
	$this->_out('/Info '.($this->n-1).' 0 R>>');
	$this->_out('startxref');
	$this->_out($o);
	$this->_out('%%EOF');
	$this->state=3;
}

function _beginpage($orientation)
{
	$this->page++;
	$this->pages[$this->page]='';
	$this->state=2;
	$this->x=$this->lMargin;
	$this->y=$this->tMargin;
	$this->lasth=0;
	$this->FontFamily='';
	//Page orientation
	if(!$orientation)
		$orientation=$this->DefOrientation;
	else
	{
		$orientation=strtoupper($orientation{0});
		if($orientation!=$this->DefOrientation)
			$this->OrientationChanges[$this->page]=true;
	}
	if($orientation!=$this->CurOrientation)
	{
		//Change orientation
		if($orientation=='P')
		{
			$this->wPt=$this->fwPt;
			$this->hPt=$this->fhPt;
			$this->w=$this->fw;
			$this->h=$this->fh;
		}
		else
		{
			$this->wPt=$this->fhPt;
			$this->hPt=$this->fwPt;
			$this->w=$this->fh;
			$this->h=$this->fw;
		}
		$this->PageBreakTrigger=$this->h-$this->bMargin;
		$this->CurOrientation=$orientation;
	}
	//Set transformation matrix
	$this->_out(round($this->k,6).' 0 0 '.round($this->k,6).' 0 '.$this->hPt.' cm');
}

function _endpage()
{
	//End of page contents
	$this->state=1;
}

function _newobj()
{
	//Begin a new object
	$this->n++;
	$this->offsets[$this->n]=strlen($this->buffer);
	$this->_out($this->n.' 0 obj');
}

function _setfont($family,$style,$size)
{
	global $fpdf_charwidths;

	$family=strtolower($family);
	if($family=='')
		$family=$this->FontFamily;
	if($family=='arial')
		$family='helvetica';
	if($family=='symbol' or $family=='zapfdingbats')
		$style='';
	$style=strtoupper($style);
	if($style=='IB')
		$style='BI';
	if($size==0)
		$size=$this->FontSizePt;
	//Test if font is already selected
	if($this->FontFamily==$family and $this->FontStyle==$style and $this->FontSizePt==$size)
		return true;
	//Retrieve Type1 font name
	if(!isset($this->fontnames[$family.$style]))
		return false;
	$fontname=$this->fontnames[$family.$style];
	//Test if used for the first time
	if(!isset($this->fonts[$fontname]))
	{
		$n=count($this->fonts);
		$this->fonts[$fontname]=$n+1;
		if(!isset($fpdf_charwidths[$family.$style]))
		{
			//include metric file
			$file=$family;
			if($family=='times' or $family=='helvetica')
				$file.=strtolower($style);
			$file.='.php';
			if(defined('FPDF_FONTPATH'))
				$file=FPDF_FONTPATH.$file;
			include($file);
			if(!isset($fpdf_charwidths[$family.$style]))
				$this->Error('Could not include font metric file');
		}
	}
	//Select it
	$this->FontFamily=$family;
	$this->FontStyle=$style;
	$this->FontSizePt=$size;
	$this->FontSize=round($size/$this->k,2);
	if($this->page>0)
		$this->_out('BT /F'.$this->fonts[$fontname].' '.$this->FontSize.' Tf ET');
	return true;
}

function _setfont_sai($family,$style,$size)
{
	global $fpdf_charwidths;

	$family=strtolower($family);
	if($family=='')
		$family=$this->FontFamily;
	if($family=='arial')
		$family='helvetica';
	if($family=='symbol' or $family=='zapfdingbats')
		$style='';
	$style=strtoupper($style);
	if($style=='IB')
		$style='BI';
	if($size==0)
		$size=$this->FontSizePt;
	//Retrieve Type1 font name
	if(!isset($this->fontnames[$family.$style]))
		return false;
	$fontname=$this->fontnames[$family.$style];
	//Test if used for the first time
	if(!isset($this->fonts[$fontname]))
	{
		$n=count($this->fonts);
		$this->fonts[$fontname]=$n+1;
		if(!isset($fpdf_charwidths[$family.$style]))
		{
			//include metric file
			$file=$family;
			if($family=='times' or $family=='helvetica')
				$file.=strtolower($style);
			$file.='.php';
			if(defined('FPDF_FONTPATH'))
				$file=FPDF_FONTPATH.$file;
			include($file);
			if(!isset($fpdf_charwidths[$family.$style]))
				$this->Error('Could not include font metric file');
		}
	}
	//Select it
	$this->FontFamily=$family;
	$this->FontStyle=$style;
	$this->FontSizePt=$size;
	$this->FontSize=round($size/$this->k,2);
	if($this->page>0)
		$sai='BT /F'.$this->fonts[$fontname].' '.$this->FontSize.' Tf ET';
	return $sai;
}

function _setfontsize($size)
{
	//Test if size already selected
	if($this->FontSizePt==$size)
		return;
	//Select it
	$fontname=$this->fontnames[$this->FontFamily.$this->FontStyle];
	$this->FontSizePt=$size;
	$this->FontSize=round($size/$this->k,2);
	if($this->page>0)
		$this->_out('BT /F'.$this->fonts[$fontname].' '.$this->FontSize.' Tf ET');
}

function _parsejpg($file)
{
	//Extract info from a JPEG file
	$a=GetImageSize($file);
	if(!$a)
		$this->Error('Missing or incorrect image file: '.$file);
	if($a[2]!=2)
		$this->Error('Not a JPEG file: '.$file);
	if(!isset($a['channels']) or $a['channels']==3)
		$colspace='DeviceRGB';
	elseif($a['channels']==4)
		$colspace='DeviceCMYK';
	else
		$colspace='DeviceGray';
	$bpc=isset($a['bits']) ? $a['bits'] : 8;
	//Read whole file
	$f=fopen($file,'rb');
	$data=fread($f,filesize($file));
	fclose($f);
	return array('w'=>$a[0],'h'=>$a[1],'cs'=>$colspace,'bpc'=>$bpc,'f'=>'DCTDecode','data'=>$data);
}

function _parsepng($file)
{
	//Extract info from a PNG file
	$f=fopen($file,'rb');
	if(!$f)
		$this->Error('Can\'t open image file: '.$file);
	//Check signature
	if(fread($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
		$this->Error('Not a PNG file: '.$file);
	//Read header chunk
	fread($f,4);
	if(fread($f,4)!='IHDR')
		$this->Error('Incorrect PNG file: '.$file);
	$w=$this->_freadint($f);
	$h=$this->_freadint($f);
	$bpc=ord(fread($f,1));
	if($bpc>8)
		$this->Error('16-bit depth not supported: '.$file);
	$ct=ord(fread($f,1));
	if($ct==0)
		$colspace='DeviceGray';
	elseif($ct==2)
		$colspace='DeviceRGB';
	elseif($ct==3)
		$colspace='Indexed';
	else
		$this->Error('Alpha channel not supported: '.$file);
	if(ord(fread($f,1))!=0)
		$this->Error('Unknown compression method: '.$file);
	if(ord(fread($f,1))!=0)
		$this->Error('Unknown filter method: '.$file);
	if(ord(fread($f,1))!=0)
		$this->Error('Interlacing not supported: '.$file);
	fread($f,4);
	$parms='/DecodeParms <</Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
	//Scan chunks looking for palette, transparency and image data
	$pal='';
	$trns='';
	$data='';
	do
	{
		$n=$this->_freadint($f);
		$type=fread($f,4);
		if($type=='PLTE')
		{
			//Read palette
			$pal=fread($f,$n);
			fread($f,4);
		}
		elseif($type=='tRNS')
		{
			//Read transparency info
			$t=fread($f,$n);
			if($ct==0)
				$trns=array(substr($t,1,1));
			elseif($ct==2)
				$trns=array(substr($t,1,1),substr($t,3,1),substr($t,5,1));
			else
			{
				$pos=strpos($t,chr(0));
				if(is_int($pos))
					$trns=array($pos);
			}
			fread($f,4);
		}
		elseif($type=='IDAT')
		{
			//Read image data block
			$data.=fread($f,$n);
			fread($f,4);
		}
		elseif($type=='IEND')
			break;
		else
			fread($f,$n+4);
	}
	while($n);
	if($colspace=='Indexed' and empty($pal))
		$this->Error('Missing palette in '.$file);
	fclose($f);
	return array('w'=>$w,'h'=>$h,'cs'=>$colspace,'bpc'=>$bpc,'f'=>'FlateDecode','parms'=>$parms,'pal'=>$pal,'trns'=>$trns,'data'=>$data);
}

function _freadint($f)
{
	//Read a 4-byte integer from file
	$i=ord(fread($f,1))<<24;
	$i+=ord(fread($f,1))<<16;
	$i+=ord(fread($f,1))<<8;
	$i+=ord(fread($f,1));
	return $i;
}

function _escape($s)
{
	//Add \ before \, ( and )
	return str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$s)));
}

function _out($s)
{
	//Add a line to the document
	if($this->state==2)
		$this->pages[$this->page].=$s."\n";
	else
		$this->buffer.=$s."\n";
}
//End of class
}

//Handle silly IE contype request
if(isset($HTTP_ENV_VARS['HTTP_USER_AGENT']) and $HTTP_ENV_VARS['HTTP_USER_AGENT']=='contype')
{
	Header('Content-Type: application/pdf');
	exit;
}

?>
