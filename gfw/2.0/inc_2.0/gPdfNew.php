<?
/** Classe gPDF
 * @author	giuliano
 * @version	1.0 22-09-2009 15:58
 */
@include_once $gPathLib.gVar("lib.fpdf");
@include_once $gPathDefault.'fpdf16/fpdf.php';

	
	
//function hex2dec
//returns an associative array (keys: R,G,B) from
//a hex html code (e.g. #3FE5AA)
function hex2dec($couleur = "#000000"){
    $R = substr($couleur, 1, 2);
    $rouge = hexdec($R);
    $V = substr($couleur, 3, 2);
    $vert = hexdec($V);
    $B = substr($couleur, 5, 2);
    $bleu = hexdec($B);
    $tbl_couleur = array();
    $tbl_couleur['R']=$rouge;
    $tbl_couleur['G']=$vert;
    $tbl_couleur['B']=$bleu;
    return $tbl_couleur;
}

//conversion pixel -> millimeter at 72 dpi
function px2mm($px){
    return $px*25.4/72;
}

function txtentities($html){
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    return strtr($html, $trans);
}

class gPdf extends FPDF
{
	//variables of gFW 3.0
	private $header;
	private $footer;
	public $charset;
	public $title;
	public $subtitle;
	public $filter;
	public $detailFontSize;

	//variables of html parser
	var $B;
	var $I;
	var $U;
	var $HREF;
	var $fontList;
	var $issetfont;
	var $issetcolor;
	var $istable=false;
	var $ident=0;
	var $numlista=0;
	var $palign="L";
	var $p=false;
	
	//===================== gFW 3.0
		
	function __construct($json)
	{
		$mtz=cssDecode($json);
		$this->header=($mtz['header']=="true")?true:false;
		$this->footer=$mtz['footer']=="true"?true:false;
		$this->fullFooter=$mtz['fullfooter']=="false"?false:true;
		$this->title=$mtz['title'];
		$this->subtitle=$mtz['subTitle'];
		$this->filter=$mtz['filter'];
		$this->charset=$mtz['charset']==""?"utf8":$mtz['charset'];
		
		$orientation=$mtz['orientation']==""?"P":"L";
		$unit=$mtz['unit']==""?"mm":$mtz['unit'];
		$format=$mtz['format']==""?"A4":$mtz['format'];
		// Charset adjust
		if (strtolower($this->charset)=="utf8")
		{
			$this->title=utf8_decode($this->title);
			$this->subtitle=utf8_decode($this->subtitle);
			$this->filter=utf8_decode($this->filter);
		}
		//Call parent constructor
		parent::__construct($orientation,$unit,$format);
		//Initialization
		$this->detailFontSize=10;
		
		
		$this->FontFamily='arial';
		
		
		
		$this->B=0;
		$this->I=0;
		$this->U=0;
		$this->HREF='';
		$this->fontlist=array('arial', 'times', 'courier', 'helvetica', 'symbol');
		$this->issetfont=false;
		$this->issetcolor=false;

		$this->tableborder=0;
		$this->tdbegin=false;
		$this->tdwidth=0;
		$this->tdheight=0;
		$this->tdalign="X";
		$this->tdbgcolor=false;

		$this->oldx=0;
		$this->oldy=0;


		$this->SetTitle($this->title);
		//$this->SetAuthor($this->author);
	}

	function Header()
	{
		global $gPathImg;
		if ($this->header)
		{
			$fonttam=$this->detailFontSize;
			$logo=gVar("pdf.logojpgfile");
			$low=gVar("pdf.logowidth");
			$loh=gVar("pdf.logoheight");
			$loa=gVar("pdf.logoalign");
			if (is_array($loa)) $loa=$loa[0];
			$lot=explode(".",$logo);
			$lot=$lot[1];
			if ($loa=="left")
			{
			// Logotipo
				if (is_array($logo))
				{
					$this->Image($gPathImg . $logo[0],$this->lMargin,9,intval($low[0]),intval($loh[0]),$lot);
					$this->Image($gPathImg . $logo[1],$w-intval($low[1]),9,intval($low[1]),intval($loh[1]),$lot);
				} else
				{
					$this->Image($gPathImg . $logo,$this->lMargin,9,intval($low),intval($loh),$lot);
					//$this->Image($gPathImg . gVar("pdf.logojpgfile"),$this->lMargin,9,intval(gVar("pdf.logowidth")),intval(gVar("pdf.logoheight")),"jpg");
				}
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
				if (is_array($logo))
				{
					$this->Image($gPathImg . $logo[0],$this->lMargin,9,intval($low[0]),intval($loh[0]),$lot);
					$this->Image($gPathImg . $logo[1],$w-intval($low[1]),9,intval($low[1]),intval($loh[1]),$lot);
				} else
				{
					$this->Image($gPathImg . $logo,$w-intval($low),9,intval($low),intval($loh),$lot);
					//$this->Image($gPathImg . gVar("pdf.logojpgfile"),$w-intval(gVar("pdf.logowidth")),9,intval(gVar("pdf.logowidth")),intval(gVar("pdf.logoheight")),"jpg");
				}
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

			//$this->Ln(3);
			$this->SetFont(gVar("pdf.font"),'',$fonttam);
		}
	}
	
	function SetTitle($title,$subtitle="",$filter="")
	{
		//Title of document
		$this->title=$title;
		//Subtitle of document
		$this->subtitle=$subtitle;
		//Filter of document
		$this->filter=$filter;
		//$this->author=end_site;
	}

	
	function Footer()
	{
		if ($this->footer)
		{
			$printDate=gT('print_date.long');
			$page=gT("page");
			$footer1=gVar("pdf.footer1");
			$footer2=gVar("pdf.footer2");
			$footer3=gVar("pdf.footer3");
			if (strtolower($this->charset)=="utf8")
			{
				$printDate=utf8_decode($printDate);
				$page=utf8_decode($page);
				$footer1=utf8_decode($footer1);
				$footer2=utf8_decode($footer2);
				$footer3=utf8_decode($footer3);
			}
			if ($this->fullFooter)
			{
				//Position at 1.5 cm from bottom
				$this->SetY(-15);
				//Arial italic 8
				$this->SetFont(gVar("pdf.font"),'',8);
				//Text color in gray
				$this->SetTextColor(128);
				$this->SetDrawColor(128);
				//Page number LineWidth
				$this->Cell(0,10,$page.' '.$this->PageNo(),0,0,'R');
				$this->SetY(-15);
				$this->Cell(0,10,$footer1,0,0,'C');
				$this->SetY(-12);
				$this->Cell(0,10,$footer2,0,0,'C');
				$this->SetY(-9);
				$this->Cell(0,10,$footer3,0,0,'C');
				$this->SetY(-15);
				$fmt=gVar("global.dateformat");
				$fmt=str_replace('yyyy','Y',$fmt);
				$fmt=str_replace('yy','y',$fmt);
				$fmt=str_replace('mm','m',$fmt);
				$fmt=str_replace('dd','d',$fmt);
				$this->Cell(0,10,$printDate." ".date("$fmt H:m"),0,0,'L');
				$this->SetY(-15);
			} else
			{
				//Position at 1.5 cm from bottom
				$this->SetY(-15);
				//Arial italic 8
				$this->SetFont('Arial','',9);
				//Text color in gray
				$this->SetTextColor(128);
				$this->SetDrawColor(128);
				//Page number
				$this->Cell(0,10,$page.' '.$this->PageNo(),0,0,'C');
			}
		} 
		
	}
	
	//===================== WriteHTML
	
	function WriteHTMLPages($html,$sep="~")
	{
		$htmls=explode($sep,$html);
		foreach ($htmls as $pag)
		{
			if (trim($pag)<>"");
			{
				$this->AddPage();
				$this->WriteHTML($pag);
			}
		}
		
	}
	
	function WriteHTML($html)
	{
		//$html=strip_tags($html,"<b><u><i><a><img><p><br><strong><em><font><tr><blockquote><hr><td><tr><table><sup>"); //remove all unsupported tags
		//$html=strip_tags($html,"<b><u><i><a><img><p><br><strong><em><tr><blockquote><td><tr><table><sup>"); //remove all unsupported tags
		$html=str_replace("\n",'',$html); //replace carriage returns by spaces
		$html=str_replace("\t",'',$html); //replace carriage returns by spaces
		$a=preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE); //explodes the string
		foreach($a as $i=>$e)
		{
			if($i%2==0)
			{
					//Text
					if($this->HREF)
						$this->PutLink($this->HREF,$e);
					elseif($this->tdbegin) {
						if(trim($e)!='' && $e!="&nbsp;") {
							$this->Cell($this->tdwidth,$this->tdheight,$e,$this->tableborder,'',$this->tdalign,$this->tdbgcolor);
						}
						elseif($e=="&nbsp;") {
							$this->Cell($this->tdwidth,$this->tdheight,'',$this->tableborder,'',$this->tdalign,$this->tdbgcolor);
						}
					}
					else
						if (!$this->istable)
						{
							if ($this->palign=="X")
								$this->Write(5,stripslashes(txtentities($e)));
							else
								$this->MultiCell(0,5,stripslashes(txtentities($e)),0,$this->palign);
						}
			}
			else
			{
				
					//Tag
					if($e[0]=='/')
						$this->CloseTag(strtoupper(substr($e,1)));
					else
					{
						//Extract attributes
						$e=str_replace("= ","=",$e);
						$e=str_replace(": ",":",$e);
						$e=str_replace("; ",";",$e);
						$a2=explode(' ',$e);
						$tag=strtoupper(array_shift($a2));
						$attr="";
						if (is_array($a2))
						{
							foreach($a2 as $v)
							{
								if (trim($v)<>'/')
								{
									//if(preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3))
									//		$attr[strtoupper($a3[1])]=$a3[2];
									$at=explode('=',$v);
									$attr[strtoupper($at[0])]=strtoupper(str_replace("\"","",str_replace("'","",$at[1])));
									
								}
							}
						}
						$this->OpenTag($tag,$attr);
					}
			}
		}
	}

	function OpenTag($tag, $attr)
	{
		//Opening tag
		switch($tag){

			case 'SUP':
					if( !empty($attr['SUP']) ) {    
						//Set current font to 6pt     
						$this->SetFont('','',6);
						//Start 125cm plus width of cell to the right of left margin         
						//Superscript "1" 
						$this->Cell(2,2,$attr['SUP'],0,0,'L');
					}
					break;

			case 'TABLE': // TABLE-BEGIN
					if( $attr['BORDER']<>"" ) $this->tableborder=$attr['BORDER'];
					else $this->tableborder=1;
					//$this->tableborder=1;
					$this->Ln(9);
					$this->istable=true;
					break;
			case 'TBODY': //TR-BEGIN
					break;
			case 'TR': //TR-BEGIN
					$this->x=$this->lMargin;
					break;
			case 'TD': // TD-BEGIN
					if( !empty($attr['WIDTH']) ) $this->tdwidth=($attr['WIDTH']/2);
					else $this->tdwidth=30; // Set to your own width if you need bigger fixed cells
					if( !empty($attr['HEIGHT']) ) $this->tdheight=($attr['HEIGHT']/3);
					else $this->tdheight=5; // Set to your own height if you need bigger fixed cells
					if( !empty($attr['ALIGN']) ) {
						$align=$attr['ALIGN'];        
						if($align=='LEFT') $this->tdalign='L';
						if($align=='CENTER') $this->tdalign='C';
						if($align=='RIGHT') $this->tdalign='R';
					}
					else $this->tdalign='L'; // Set to your own
					if( !empty($attr['BGCOLOR']) ) {
						$coul=hex2dec($attr['BGCOLOR']);
							$this->SetFillColor($coul['R'],$coul['G'],$coul['B']);
							$this->tdbgcolor=true;
						}
					$this->tdbegin=true;
					break;
			case 'TH': // TH-BEGIN
					if( !empty($attr['WIDTH']) ) $this->tdwidth=($attr['WIDTH']/2);
					else $this->tdwidth=60; // Set to your own width if you need bigger fixed cells
					if( !empty($attr['HEIGHT']) ) $this->tdheight=($attr['HEIGHT']/3);
					else $this->tdheight=5; // Set to your own height if you need bigger fixed cells
					if( !empty($attr['ALIGN']) ) {
						$align=$attr['ALIGN'];        
						if($align=='LEFT') $this->tdalign='L';
						if($align=='CENTER') $this->tdalign='C';
						if($align=='RIGHT') $this->tdalign='R';
					}
					else $this->tdalign='L'; // Set to your own
					if( !empty($attr['BGCOLOR']) ) {
						$coul=hex2dec($attr['BGCOLOR']);
							$this->SetFillColor($coul['R'],$coul['G'],$coul['B']);
							$this->tdbgcolor=true;
						}
					$this->tdbegin=true;
					break;

			case 'HR':
					if( !empty($attr['WIDTH']) )
						$Width = $attr['WIDTH'];
					else
						$Width = $this->w - $this->lMargin-$this->rMargin;
					$x = $this->GetX();
					$y = $this->GetY();
					$this->SetLineWidth(0.2);
					$this->Line($x,$y,$x+$Width,$y);
					$this->SetLineWidth(0.2);
					$this->Ln(1);
					break;
			case 'H1':
				$this->Ln(2);
					$this->SetFont('','',20);
					
					break;
			case 'H2':
				$this->Ln(2);
					$this->SetFont('','',18);

					break;
			case 'H3':
				$this->Ln(2);
					$this->SetFont('','',16);
					break;
			case 'H4':
					$this->SetFont('','',14);
					break;
			case 'H5':
					$this->SetFont('','',10);
					break;
			case 'H6':
					$this->SetFont('','',7);
					break;
			case 'STRONG':
					$this->SetStyle('B',true);
					break;
			case 'EM':
					$this->SetStyle('I',true);
					break;
			case 'B':
					$this->SetStyle('B',true);
					break;
			case 'I':
					$this->SetStyle('I',true);
					break;
			case 'U':
					$this->SetStyle($tag,true);
					break;
			case 'A':
					$this->HREF=$attr['HREF'];
					break;
			case 'IMG':
					if(isset($attr['SRC']) && (isset($attr['WIDTH']) || isset($attr['HEIGHT']))) {
						if(!isset($attr['WIDTH']))
							$attr['WIDTH'] = 0;
						if(!isset($attr['HEIGHT']))
							$attr['HEIGHT'] = 0;
						$this->Image($attr['SRC'], $this->GetX(), $this->GetY(), px2mm($attr['WIDTH']), px2mm($attr['HEIGHT']));
					}
					break;
			case 'BLOCKQUOTE':
			case 'BR':
					if ((!$this->tdbegin) && (!$this->p))
						$this->Ln(5);
					$this->p=false;
					break;
			case 'P':
					if (isset($attr['STYLE']))
					{
						if ($attr['STYLE']=="TEXT-ALIGN:RIGHT;")
							$this->palign="R";
						if ($attr['STYLE']=="TEXT-ALIGN:LEFT;")
							$this->palign="L";
						if ($attr['STYLE']=="TEXT-ALIGN:CENTER;")
							$this->palign="C";
						if ($attr['STYLE']=="TEXT-ALIGN:JUSTIFY;")
							$this->palign="X"; // desativado
					}
					$this->Ln(5);
					$this->p=true;
					break;
			case 'UL':
					$this->Ln(5);
					$this->ident++;
					$this->numlista=0;
					break;
			case 'OL':
					$this->Ln(2);
					$this->ident++;
					$this->numlista=1;
					break;
			case 'LI':
					$this->Ln(2);
					$ff=$this->FontFamily;
					$fz=$this->FontSizePt;
					$fs=$this->FontStyle;
					
					$car[1]="\217";// 233
					$car[2]="\233";
					$car[3]="\243";
					$car[4]="\225";
					$car[5]="\233";
					for ($a=1; $a<$this->ident; $a++)
						$this->Write(5,"    ");
					if ($this->numlista>0)
					{
						//$this->SetFont('times','I','');
						$this->Write(5,"   ".$this->numlista.". ");
						$this->numlista++;
					} else
					{
						$this->SetFont('arial','B',18);
						$this->Write(5,"   ".$car[$this->ident]." ");
					}
					$this->SetFont($ff,$fs,$fz);
					break;
			case 'FONT':
					if (isset($attr['COLOR']) && $attr['COLOR']!='') {
						$coul=hex2dec($attr['COLOR']);
						$this->SetTextColor($coul['R'],$coul['G'],$coul['B']);
						$this->issetcolor=true;
					}
					if (isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontlist)) {
						$this->SetFont(strtolower($attr['FACE']));
						$this->issetfont=true;
					}
					if (isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontlist) && isset($attr['SIZE']) && $attr['SIZE']!='') {
						$this->SetFont(strtolower($attr['FACE']),'',$attr['SIZE']);
						$this->issetfont=true;
					}
					break;
		}
	}

	function CloseTag($tag)
	{
		//Closing tag
		if($tag=='SUP') {
		}

		if(($tag=='H1') || ($tag=='H2') || ($tag=='H3') || ($tag=='H4') || ($tag=='H5') || ($tag=='H6') ){
			$this->SetFont('','',10);
			$this->Ln(4);
		}
		
		if(($tag=='TD') || ($tag=='TH')) { // TD TH-END
			$this->tdbegin=false;
			$this->tdwidth=0;
			$this->tdheight=0;
			$this->tdalign="L";
			$this->tdbgcolor=false;
		}
		if($tag=='TR') { // TR-END
			$this->Ln();
		}
		if($tag=='TABLE') { // TABLE-END
			//$this->Ln();
			$this->istable=false;
			$this->tableborder=0;
		}

		if($tag=='TBODY') { // TBODY-END
		}

		if(($tag=='UL')||($tag=='OL'))
			$this->ident--;
		if($tag=='LI') { // LI-END
			$this->Ln(3);
		}
		if($tag=='STRONG')
			$tag='B';
		if($tag=='EM')
			$tag='I';
		if($tag=='B' || $tag=='I' || $tag=='U')
			$this->SetStyle($tag,false);
		if($tag=='A')
			$this->HREF='';
		if($tag=='P')
		{
			$this->Ln(5);
			$this->p=false;
			$this->palign='X';
		}
		if($tag=='FONT'){
			if ($this->issetcolor==true) {
					$this->SetTextColor(0);
			}
			if ($this->issetfont) {
					$this->SetFont('arial');
					$this->issetfont=false;
			}
		}
	}

	function SetStyle($tag, $enable)
	{
		//Modify style and select corresponding font
		$this->$tag+=($enable ? 1 : -1);
		$style='';
		foreach(array('B','I','U') as $s) {
			if($this->$s>0)
					$style.=$s;
		}
		$this->SetFont('',$style);
	}

	function PutLink($URL, $txt)
	{
		//Put a hyperlink
		$this->SetTextColor(0,0,255);
		$this->SetStyle('U',true);
		$this->Write(5,$txt,$URL);
		$this->SetStyle('U',false);
		$this->SetTextColor(0);
	}


	//===================== Cell e vCell
	
	function VCell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false)
	{
		//Output a cell
		$k=$this->k;
		if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
		{
			//Automatic page break
			$x=$this->x;
			$ws=$this->ws;
			if($ws>0)
			{
					$this->ws=0;
					$this->_out('0 Tw');
			}
			$this->AddPage($this->CurOrientation,$this->CurPageFormat);
			$this->x=$x;
			if($ws>0)
			{
					$this->ws=$ws;
					$this->_out(sprintf('%.3F Tw',$ws*$k));
			}
		}
		if($w==0)
			$w=$this->w-$this->rMargin-$this->x;
		$s='';
	// begin change Cell function 
		if($fill || $border>0)
		{
			if($fill)
					$op=($border>0) ? 'B' : 'f';
			else
					$op='S';
			if ($border>1) {
					$s=sprintf('q %.2F w %.2F %.2F %.2F %.2F re %s Q ',$border,
									$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
			}
			else
					$s=sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
		}
		if(is_string($border))
		{
			$x=$this->x;
			$y=$this->y;
			if(is_int(strpos($border,'L')))
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
			else if(is_int(strpos($border,'l')))
					$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
					
			if(is_int(strpos($border,'T')))
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			else if(is_int(strpos($border,'t')))
					$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			
			if(is_int(strpos($border,'R')))
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			else if(is_int(strpos($border,'r')))
					$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			
			if(is_int(strpos($border,'B')))
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			else if(is_int(strpos($border,'b')))
					$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
		}
		if(trim($txt)!='')
		{
			$cr=substr_count($txt,"\n");
			if ($cr>0) { // Multi line
					$txts = explode("\n", $txt);
					$lines = count($txts);
					for($l=0;$l<$lines;$l++) {
						$txt=$txts[$l];
						$w_txt=$this->GetStringWidth($txt);
						if ($align=='U')
							$dy=$this->cMargin+$w_txt;
						elseif($align=='D')
							$dy=$h-$this->cMargin;
						else
							$dy=($h+$w_txt)/2;
						$txt=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
						if($this->ColorFlag)
							$s.='q '.$this->TextColor.' ';
						$s.=sprintf('BT 0 1 -1 0 %.2F %.2F Tm (%s) Tj ET ',
							($this->x+.5*$w+(.7+$l-$lines/2)*$this->FontSize)*$k,
							($this->h-($this->y+$dy))*$k,$txt);
						if($this->ColorFlag)
							$s.=' Q ';
					}
			}
			else { // Single line
					$w_txt=$this->GetStringWidth($txt);
					$Tz=100;
					if ($w_txt>$h-2*$this->cMargin) {
						$Tz=($h-2*$this->cMargin)/$w_txt*100;
						$w_txt=$h-2*$this->cMargin;
					}
					if ($align=='U')
						$dy=$this->cMargin+$w_txt;
					elseif($align=='D')
						$dy=$h-$this->cMargin;
					else
						$dy=($h+$w_txt)/2;
					$txt=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
					if($this->ColorFlag)
						$s.='q '.$this->TextColor.' ';
					$s.=sprintf('q BT 0 1 -1 0 %.2F %.2F Tm %.2F Tz (%s) Tj ET Q ',
									($this->x+.5*$w+.3*$this->FontSize)*$k,
									($this->h-($this->y+$dy))*$k,$Tz,$txt);
					if($this->ColorFlag)
						$s.=' Q ';
			}
		}
	// end change Cell function 
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

	function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
	{
		//Output a cell
		$k=$this->k;
		if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
		{
			//Automatic page break
			$x=$this->x;
			$ws=$this->ws;
			if($ws>0)
			{
					$this->ws=0;
					$this->_out('0 Tw');
			}
			$this->AddPage($this->CurOrientation,$this->CurPageFormat);
			$this->x=$x;
			if($ws>0)
			{
					$this->ws=$ws;
					$this->_out(sprintf('%.3F Tw',$ws*$k));
			}
		}
		if($w==0)
			$w=$this->w-$this->rMargin-$this->x;
		$s='';
	// begin change Cell function
		if($fill || $border>0)
		{
			if($fill)
					$op=($border>0) ? 'B' : 'f';
			else
					$op='S';
			if ($border>1) {
					$s=sprintf('q %.2F w %.2F %.2F %.2F %.2F re %s Q ',$border,
						$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
			}
			else
					$s=sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
		}
		if(is_string($border))
		{
			$x=$this->x;
			$y=$this->y;
			if(is_int(strpos($border,'L')))
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
			else if(is_int(strpos($border,'l')))
					$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
					
			if(is_int(strpos($border,'T')))
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			else if(is_int(strpos($border,'t')))
					$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			
			if(is_int(strpos($border,'R')))
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			else if(is_int(strpos($border,'r')))
					$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			
			if(is_int(strpos($border,'B')))
					$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			else if(is_int(strpos($border,'b')))
					$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
		}
		if (trim($txt)!='') {
			$cr=substr_count($txt,"\n");
			if ($cr>0) { // Multi line
					$txts = explode("\n", $txt);
					$lines = count($txts);
					for($l=0;$l<$lines;$l++) {
						$txt=$txts[$l];
						$w_txt=$this->GetStringWidth($txt);
						if($align=='R')
							$dx=$w-$w_txt-$this->cMargin;
						elseif($align=='C')
							$dx=($w-$w_txt)/2;
						else
							$dx=$this->cMargin;

						$txt=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
						if($this->ColorFlag)
							$s.='q '.$this->TextColor.' ';
						$s.=sprintf('BT %.2F %.2F Td (%s) Tj ET ',
							($this->x+$dx)*$k,
							($this->h-($this->y+.5*$h+(.7+$l-$lines/2)*$this->FontSize))*$k,
							$txt);
						if($this->underline)
							$s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
						if($this->ColorFlag)
							$s.=' Q ';
						if($link)
							$this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$w_txt,$this->FontSize,$link);
					}
			}
			else { // Single line
					$w_txt=$this->GetStringWidth($txt);
					$Tz=100;
					if ($w_txt>$w-2*$this->cMargin) { // Need compression
						$Tz=($w-2*$this->cMargin)/$w_txt*100;
						$w_txt=$w-2*$this->cMargin;
					}
					if($align=='R')
						$dx=$w-$w_txt-$this->cMargin;
					elseif($align=='C')
						$dx=($w-$w_txt)/2;
					else
						$dx=$this->cMargin;
					$txt=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
					if($this->ColorFlag)
						$s.='q '.$this->TextColor.' ';
					$s.=sprintf('q BT %.2F %.2F Td %.2F Tz (%s) Tj ET Q ',
									($this->x+$dx)*$k,
									($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,
									$Tz,$txt);
					if($this->underline)
						$s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
					if($this->ColorFlag)
						$s.=' Q ';
					if($link)
						$this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$w_txt,$this->FontSize,$link);
			}
		}
	// end change Cell function
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
	

	//===================== setDash
	
	function SetDash($black=null, $white=null)
	{
		if($black!==null)
				$s=sprintf('[%.3F %.3F] 0 d',$black*$this->k,$white*$this->k);
		else
				$s='[] 0 d';
		$this->_out($s);
	}

	//===================== TextWithDirection e TextWithRotation

	function TextWithDirection($x, $y, $txt, $direction='R')
	{
		if ($direction=='R')
			$s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',1,0,0,1,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		elseif ($direction=='L')
			$s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',-1,0,0,-1,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		elseif ($direction=='U')
			$s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',0,1,-1,0,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		elseif ($direction=='D')
			$s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',0,-1,1,0,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		else
			$s=sprintf('BT %.2F %.2F Td (%s) Tj ET',$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		if ($this->ColorFlag)
			$s='q '.$this->TextColor.' '.$s.' Q';
		$this->_out($s);
	}

	function TextWithRotation($x, $y, $txt, $txt_angle, $font_angle=0)
	{
		$font_angle+=90+$txt_angle;
		$txt_angle*=M_PI/180;
		$font_angle*=M_PI/180;

		$txt_dx=cos($txt_angle);
		$txt_dy=sin($txt_angle);
		$font_dx=cos($font_angle);
		$font_dy=sin($font_angle);

		$s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',$txt_dx,$txt_dy,$font_dx,$font_dy,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		if ($this->ColorFlag)
			$s='q '.$this->TextColor.' '.$s.' Q';
		$this->_out($s);
	}
	//===================== DashedRect

	function DashedRect($x1, $y1, $x2, $y2, $width=1, $nb=15)
	{
		$this->SetLineWidth($width);
		$longueur=abs($x1-$x2);
		$hauteur=abs($y1-$y2);
		if($longueur>$hauteur) {
			$Pointilles=($longueur/$nb)/2; // length of dashes
		}
		else {
			$Pointilles=($hauteur/$nb)/2;
		}
		for($i=$x1;$i<=$x2;$i+=$Pointilles+$Pointilles) {
			for($j=$i;$j<=($i+$Pointilles);$j++) {
					if($j<=($x2-1)) {
						$this->Line($j,$y1,$j+1,$y1); // upper dashes
						$this->Line($j,$y2,$j+1,$y2); // lower dashes
					}
			}
		}
		for($i=$y1;$i<=$y2;$i+=$Pointilles+$Pointilles) {
			for($j=$i;$j<=($i+$Pointilles);$j++) {
					if($j<=($y2-1)) {
						$this->Line($x1,$j,$x1,$j+1); // left dashes
						$this->Line($x2,$j,$x2,$j+1); // right dashes
					}
			}
		}
	}

	//===================== Rounded Rectangle

	function RoundedRect($x, $y, $w, $h, $r, $style = '')
	{
		$k = $this->k;
		$hp = $this->h;
		if($style=='F')
		$op='f';
		elseif($style=='FD' || $style=='DF')
		$op='B';
		else
		$op='S';
		$MyArc = 4/3 * (sqrt(2) - 1);
		$this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
		$xc = $x+$w-$r ;
		$yc = $y+$r;
		$this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

		$this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
		$xc = $x+$w-$r ;
		$yc = $y+$h-$r;
		$this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
		$this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
		$xc = $x+$r ;
		$yc = $y+$h-$r;
		$this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
		$this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
		$xc = $x+$r ;
		$yc = $y+$r;
		$this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
		$this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
		$this->_out($op);
	}

	function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
	{
		$h = $this->h;
		$this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
		$x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
	}

	//===================== BarCode Start

	function EAN13($x, $y, $barcode, $h=16, $w=.35)
	{
		$this->Barcode($x,$y,$barcode,$h,$w,13);
	}

	function UPC_A($x, $y, $barcode, $h=16, $w=.35)
	{
		$this->Barcode($x,$y,$barcode,$h,$w,12);
	}

	function Code39($xpos, $ypos, $code, $baseline=0.5, $height=5)
	{
		$wide = $baseline;
		$narrow = $baseline / 3 ; 
		$gap = $narrow;

		$barChar['0'] = 'nnnwwnwnn';
		$barChar['1'] = 'wnnwnnnnw';
		$barChar['2'] = 'nnwwnnnnw';
		$barChar['3'] = 'wnwwnnnnn';
		$barChar['4'] = 'nnnwwnnnw';
		$barChar['5'] = 'wnnwwnnnn';
		$barChar['6'] = 'nnwwwnnnn';
		$barChar['7'] = 'nnnwnnwnw';
		$barChar['8'] = 'wnnwnnwnn';
		$barChar['9'] = 'nnwwnnwnn';
		$barChar['A'] = 'wnnnnwnnw';
		$barChar['B'] = 'nnwnnwnnw';
		$barChar['C'] = 'wnwnnwnnn';
		$barChar['D'] = 'nnnnwwnnw';
		$barChar['E'] = 'wnnnwwnnn';
		$barChar['F'] = 'nnwnwwnnn';
		$barChar['G'] = 'nnnnnwwnw';
		$barChar['H'] = 'wnnnnwwnn';
		$barChar['I'] = 'nnwnnwwnn';
		$barChar['J'] = 'nnnnwwwnn';
		$barChar['K'] = 'wnnnnnnww';
		$barChar['L'] = 'nnwnnnnww';
		$barChar['M'] = 'wnwnnnnwn';
		$barChar['N'] = 'nnnnwnnww';
		$barChar['O'] = 'wnnnwnnwn'; 
		$barChar['P'] = 'nnwnwnnwn';
		$barChar['Q'] = 'nnnnnnwww';
		$barChar['R'] = 'wnnnnnwwn';
		$barChar['S'] = 'nnwnnnwwn';
		$barChar['T'] = 'nnnnwnwwn';
		$barChar['U'] = 'wwnnnnnnw';
		$barChar['V'] = 'nwwnnnnnw';
		$barChar['W'] = 'wwwnnnnnn';
		$barChar['X'] = 'nwnnwnnnw';
		$barChar['Y'] = 'wwnnwnnnn';
		$barChar['Z'] = 'nwwnwnnnn';
		$barChar['-'] = 'nwnnnnwnw';
		$barChar['.'] = 'wwnnnnwnn';
		$barChar[' '] = 'nwwnnnwnn';
		$barChar['*'] = 'nwnnwnwnn';
		$barChar['$'] = 'nwnwnwnnn';
		$barChar['/'] = 'nwnwnnnwn';
		$barChar['+'] = 'nwnnnwnwn';
		$barChar['%'] = 'nnnwnwnwn';

		$this->SetFont('Arial','',10);
		$this->Text($xpos, $ypos + $height + 4, $code);
		$this->SetFillColor(0);

		$code = '*'.strtoupper($code).'*';
		for($i=0; $i<strlen($code); $i++){
			$char = $code[$i];
			if(!isset($barChar[$char])){
					$this->Error('Invalid character in barcode: '.$char);
			}
			$seq = $barChar[$char];
			for($bar=0; $bar<9; $bar++){
					if($seq[$bar] == 'n'){
						$lineWidth = $narrow;
					}else{
						$lineWidth = $wide;
					}
					if($bar % 2 == 0){
						$this->Rect($xpos, $ypos, $lineWidth, $height, 'F');
					}
					$xpos += $lineWidth;
			}
			$xpos += $gap;
		}
	}
	
	function GetCheckDigit($barcode)
	{
		//Compute the check digit
		$sum=0;
		for($i=1;$i<=11;$i+=2)
		$sum+=3*$barcode[$i];
		for($i=0;$i<=10;$i+=2)
		$sum+=$barcode[$i];
		$r=$sum%10;
		if($r>0)
		$r=10-$r;
		return $r;
	}

	function TestCheckDigit($barcode)
	{
		//Test validity of check digit
		$sum=0;
		for($i=1;$i<=11;$i+=2)
		$sum+=3*$barcode[$i];
		for($i=0;$i<=10;$i+=2)
		$sum+=$barcode[$i];
		return ($sum+$barcode[12])%10==0;
	}

	function Barcode($x, $y, $barcode, $h, $w, $len)
	{
		//Padding
		$barcode=str_pad($barcode,$len-1,'0',STR_PAD_LEFT);
		if($len==12)
		$barcode='0'.$barcode;
		//Add or control the check digit
		if(strlen($barcode)==12)
		$barcode.=$this->GetCheckDigit($barcode);
		elseif(!$this->TestCheckDigit($barcode))
		$this->Error('Incorrect check digit');
		//Convert digits to bars
		$codes=array(
		'A'=>array(
			'0'=>'0001101','1'=>'0011001','2'=>'0010011','3'=>'0111101','4'=>'0100011',
			'5'=>'0110001','6'=>'0101111','7'=>'0111011','8'=>'0110111','9'=>'0001011'),
		'B'=>array(
			'0'=>'0100111','1'=>'0110011','2'=>'0011011','3'=>'0100001','4'=>'0011101',
			'5'=>'0111001','6'=>'0000101','7'=>'0010001','8'=>'0001001','9'=>'0010111'),
		'C'=>array(
			'0'=>'1110010','1'=>'1100110','2'=>'1101100','3'=>'1000010','4'=>'1011100',
			'5'=>'1001110','6'=>'1010000','7'=>'1000100','8'=>'1001000','9'=>'1110100')
		);
		$parities=array(
		'0'=>array('A','A','A','A','A','A'),
		'1'=>array('A','A','B','A','B','B'),
		'2'=>array('A','A','B','B','A','B'),
		'3'=>array('A','A','B','B','B','A'),
		'4'=>array('A','B','A','A','B','B'),
		'5'=>array('A','B','B','A','A','B'),
		'6'=>array('A','B','B','B','A','A'),
		'7'=>array('A','B','A','B','A','B'),
		'8'=>array('A','B','A','B','B','A'),
		'9'=>array('A','B','B','A','B','A')
		);
		$code='101';
		$p=$parities[$barcode[0]];
		for($i=1;$i<=6;$i++)
		$code.=$codes[$p[$i-1]][$barcode[$i]];
		$code.='01010';
		for($i=7;$i<=12;$i++)
		$code.=$codes['C'][$barcode[$i]];
		$code.='101';
		//Draw bars
		for($i=0;$i<strlen($code);$i++)
		{
		if($code[$i]=='1')
			$this->Rect($x+$i*$w,$y,$w,$h,'F');
		}
		//Print text uder barcode
		$this->SetFont('Arial','',12);
		$this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
	}
}

?>
