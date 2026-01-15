<?
/** Classe gPDF
 * @author	giuliano
 * @version	1.0 22-09-2009 15:58
 */

include_once $gPathLib . gVar("lib.fpdf") . "fpdf.php";

//function hex2dec
//returns an associative array (keys: R,G,B) from
//a hex html code (e.g. #3FE5AA)
function hex2dec($couleur = "#000000"): array{
    $R = substr((string) $couleur, 1, 2);
    $rouge = hexdec($R);
    $V = substr((string) $couleur, 3, 2);
    $vert = hexdec($V);
    $B = substr((string) $couleur, 5, 2);
    $bleu = hexdec($B);
    $tbl_couleur = [];
    $tbl_couleur['R']=$rouge;
    $tbl_couleur['G']=$vert;
    $tbl_couleur['B']=$bleu;
    return $tbl_couleur;
}

//conversion pixel -> millimeter at 72 dpi
function px2mm($px): float{
    return $px * 25.4 / 72;
}

function txtentities($html): string{
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    return strtr(str_replace('•','-',$html), $trans);
}

class gPdf extends FPDF
{
	//variables of gFW 3.0
	public $bd;
	public $header;
	public $footer;
	public $charset;
	public $title;
	public $subtitle;
	public $filter;
	public $detailFontSize=10;
	public $tableFontSize=7;
	public $giusoftLogo;
	public $n = "\n";

	//variables of html parser
	public $B;
	public $I;
	public $U;
	public $HREF;
	public $fontList;
	public $issetfont;
	public $issetcolor;
	public $started = false;
	public $y = 0;

	public $orientation='P';
	public $defaultAlign='L';

	public $tableLines=[];
	public $tableHeaders=[];
	public $tableSize='big';
	public $tableBorder=true;
	public $tableAlternateColor=false;

	public $cellsQtd = 0;
	public $cellsWidths = '';
	public $cellsX = '';

    public $legends;
    public $wLegend;
    public $sum;
    public $NbVal;

    public $firstTitle = true;
    public $firstSubTitle = true;
    public $firstFilter = true;

	public $footer1;
	public $footer2;
	public $footer3;

    public $page = '';
	public $colorArray = [];

	public $bdCFC = ''; // Gambiarra feita por Vinicius (05/10/2020)
    public $PDFEnabled = true;

	/** gFW 4.0
	**/

	function begin(): void
	{
		$this->SetAutoPageBreak(true, 20);
		//$this->AddPage();
	}

	function end(): void
	{
		if ($_REQUEST['download']) {
			$this->Output('D');
		} elseif ($_REQUEST['file']) {
			if (substr_count($_REQUEST['file'], '/') >= 2) { // 2 indica que se trata de um diretorio pois possui mais de uma /
				$arquivo = $_REQUEST['file'];
			} else {
				$arquivo = '/tmp/'.$_REQUEST['file'];
			}
			$this->Output('F', $arquivo.'.pdf');
		} else {
			$this->Output();
		}
	}
	/*
		//Title
		$this->SetFont(gVar("pdf.font"),'B',$fonttam+5);
		$this->x=0;
		$this->Cell(0,6,$this->title,0,1,'C');
		// SubTitle
		//Arial bold 15
		$this->SetFont(gVar("pdf.font"),'',$fonttam+3);
		$this->x=0;
		$this->Cell(0,6,$this->subtitle,0,1,'C');
		// Filter
		//Arial bold 15
		$this->SetFont(gVar("pdf.font"),'I',$fonttam);
		$this->x=0;
		$this->Cell(0,4,$this->filter,0,1,'C');
	*/
	function h2(){}
	function rowTags(){}
	function msgError(){}

	function msgTitle($msg): void
	{
		$msg = str_replace('•','-',$msg);
		$fonttam=$this->detailFontSize;
		$this->title=$msg;
		//$this->SetTitle($this->title, $this->subtitle, $this->filter);
		$align=$this->defaultAlign;
		if (!$this->firstTitle) {
			$this->msgForTitles($msg, $align, $fonttam+5 , 'B', 6);
		}
	}

	function msgSubTitle($msg): void
	{
		$msg = str_replace('•','-',$msg);
		$fonttam = $this->detailFontSize;
		$this->subtitle = $msg;
		//$this->SetTitle($this->title, $this->subtitle, $this->filter);
		$align=$this->defaultAlign;
		if (!$this->firstSubTitle) {
			$this->msgForTitles($msg, $align, $fonttam+3 , '', 6);
		}
	}

	function msgFilter($msg): void
	{
		$msg = str_replace('•','-',$msg);
		$fonttam = $this->detailFontSize;
		$this->filter = $msg;
		//$this->SetTitle($this->title, $this->subtitle, $this->filter);
		$align = $this->defaultAlign;
		if (!$this->firstFilter) {
			$this->msgForTitles($msg, $align, $fonttam , 'I', 6);
		}
	}

	function msgMiniTitle($msg): void
	{
		$msg = str_replace('•','-',$msg);
		$fonttam=$this->detailFontSize;
		$this->msgForTitles(strtoupper($msg), $this->defaultAlign, $fonttam , '', 6);
	}

	function msgInfo($msg): void
	{
		$msg = str_replace('•','-',$msg);
		$fonttam=$this->detailFontSize;
		$this->filter=$msg;
		//$this->SetTitle($this->title, $this->subtitle, $this->filter);
		$align=$this->defaultAlign;
		if (!$this->firstFilter) {
			$this->msgForTitles($msg, $align, $fonttam+2 , '', 6);
		}
	}

	function msgDanger($msg): void
	{
		$msg = str_replace('•','-',$msg);
		$fonttam = $this->detailFontSize;
		$this->filter = $msg;
		//$this->SetTitle($this->title, $this->subtitle, $this->filter);
		$align = $this->defaultAlign;
		if (!$this->firstFilter) {
			$this->msgForTitles($msg, $align, $fonttam+2 , '', 6);
		}
	}

	function msgWarning($msg): void
	{
		$msg = str_replace('•','-',$msg);
		$fonttam = $this->detailFontSize;
		$this->filter = $msg;
		//$this->SetTitle($this->title, $this->subtitle, $this->filter);
		$align = $this->defaultAlign;
		if (!$this->firstFilter) {
			$this->msgForTitles($msg, $align, $fonttam+2 , '', 6);
		}
	}

	// function out($msg, $align = 'L', $size=9, $type='', $height=4)
	// {
	// }


	// static function icon($json)
	// {
	// 	return('');
	// }

	function label($content, $style = "default")
	{
		switch ($style)
		{
			case "danger":
				$content="-".$content;
			break;
			case "success":
				$content="+".$content;
			break;
			case "warning":
				$content="~".$content;
			break;
		}
		return(str_replace('•','-'," ".$content." "));
	}


	function msgForTitles($msg, $align='L', $size=9, $type='', $height=4): void
	{
		$msg = str_replace('•','-',$msg);
		if (!$this->started) {
			$this->started=true;
			$this->AddPage();
		}
		$this->SetFont(gVar("pdf.font"),$type,$size);
		//Cell(float w [, float h [, string txt [, mixed border [, int ln [, string align [, boolean fill [, mixed link]]]]]]])
		$this->Cell(0,$height,$msg,0,1, $align);
	}

	function msg($msg): void
	{
		$msg = str_replace('•','-',$msg);
		if (!$this->started) {
			$this->started=true;
			$this->AddPage();
		}
		$msg = str_ireplace('<br>',"\n", $msg);
		$msg = str_ireplace('<br/>',"\n", $msg);
		$this->SetFont(gVar("pdf.font"),'',$this->detailFontSize);
		$this->MultiCell(0, 5, $msg, 0, 'J');
	}


	function msgAlert($msg): void
	{
		$msg = str_replace('•','-',$msg);
		$this->msg($msg);
	}


	function badge($txt): void{
		$msg = str_replace('•','-',$msg);
		$this->msg($msg);
	}


	function pageBreak(): void
	{
		$this->AddPage();
	}


	function br($ttl=1): void
	{
		if (!$this->started) {
			$this->started=true;
			$this->AddPage();
		}

		for ($a=0; $a < $ttl; $a++) {
			$this->Ln();
		}
	}

	function hr($type=''): void
	{
		if (!$this->started) {
			$this->started=true;
			$this->AddPage();
		}
		$this->Cell(0,4,' ','T',1);
	}

	function tableBegin($size="big", $border=true, $alternateColor=false): void
	{
		if (!$this->started) {
			$this->started=true;
			$this->AddPage();
		}
		$this->tableSize = $size;
		$this->tableBorder = $border;
		$this->tableAlternateColor = $alternateColor;

		$this->tableLines='';
		$this->tableHeaders='';

		$this->cellsQtd = 0;
		$this->cellsWidths = '';
		$this->cellsX = '';

	}
    // function tableTotal()
    // {
    // }
	/**
	 * Monta estrutura de uma linha da tabela, removendo os campos informados no $_REQUEST['gTableRemoveFields']
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $fields Array contendo o nome/índice dos campos/colunas
	 * @param string $colMatrix Array contendo as colunas
	 * @param string $style Estilo da linha
	 * @param string $add Parametros adicionais
	 * @param string $event Adicionar algum tratamento de evento via javascript
	 * @return type
	 */
	function removeFieldsTableRow($fields, $colMatrix, $style = "detail", $add = "", $event = "")
	{
		$removerCampos = $_REQUEST['gTableRemoveFields'];
		$newCols = [];
		$contador = count($fields);
		for ($a = 0; $a < $contador; $a++) {
			$achou = false;
			foreach ($removerCampos as $remover ) {
				if ($remover == $fields[$a]) {
					$achou = true;
				}
			}

			if (!$achou) {
				$newCols[]=$colMatrix[$a];
			}
		}
		return($this->tableRow($newCols, $style));
	}

	function  tableLine(){}

	function tableRow($row, $type='detail'): void
	{
		if ($type=='header') {
			$this->tableHeaders[] = [$row, $type];
		} else {
			$this->tableLines[] = [$row, $type];
		}
	}


	function renderHeaders(): void
	{
		// Colors, line width and bold font
		$this->SetFillColor(160,160,160);
		$this->SetTextColor(0);
		$this->SetDrawColor(0,0,0);
		// $h = intval($this->GetPageHeight()) - 30;
		// $cnt=0;
		// Header
		foreach ($this->tableHeaders as $line) {
			$cells=$line[0];
			$type=$line[1];

			$ttl = $this->cellsQtd;
			$wCnt = 0;
			for ($i = 0; $i < $this->cellsQtd; $i++) {
				$cont = $cells[$i];
				// Colspan
				$colspan = 1;
				if (str_starts_with((string) $cont, "~")) {
					$colspan = substr((string) $cont, 1, 1);
					if ((ord(substr((string) $cont, 2, 1)) > 47) && (ord(substr((string) $cont, 2, 1)) < 58)) {
						$colspan.=substr((string) $cont, 2, 1);
						if ((ord(substr((string) $cont, 3, 1)) > 47) && (ord(substr((string) $cont, 3, 1)) < 58)) {
							$colspan.=substr((string) $cont, 3, 1);
							$cont = substr((string) $cont, 4);
						} else {
							$cont = substr((string) $cont, 3);
						}
					} else {
						$cont = substr((string) $cont, 2);
					}
					$colspan = intval($colspan);
				}

				// Alinhamento
				$align='L';
				if (str_starts_with((string) $cont, "->")) {
					$align = "R";
					$cont = substr((string) $cont, 2);
				}

				if (str_starts_with((string) $cont, "<-")) {
					$align = "L";
					$cont = substr((string) $cont, 2);
				}

				if (str_starts_with((string) $cont, "<>")) {
					$align = "C";
					$cont = substr((string) $cont, 2);
				}

				$w = 0;
				for ($cs=$wCnt; $cs<($wCnt+$colspan); $cs++) {
					$w+=$this->cellsWidths[$cs];
				}
				$wCnt+=$colspan;
				$ttl -= $colspan-1;
				$cont = str_ireplace('&nbsp;'," ",$cont);
				$cont = str_ireplace('<BR/>',"\n",$cont);
				$cont = str_ireplace('<BR>',"\n",$cont);
				$cont = str_ireplace('•',"-",$cont);
				$cont = trim($cont);
				$this->Cell($w, $line[2], $cont, $this->tableBorder, 0, $align, true);
				//Cell(float w [, float h [, string txt [, mixed border [, int ln [, string align [, boolean fill [, mixed link]]]]]]])
				//$this->Cell($this->cellsWidths[$i],$line[2],$cont,$this->tableBorder,0,$align,true);
			}
			$this->Ln();

		}
	}


	function renderLines(): void
	{
		// Colors, line width and bold font

		$this->SetTextColor(0);
		$this->SetDrawColor(0,0,0);

		$h = intval($this->GetPageHeight()) - 30;
		$y = intval($this->GetY());
		$cnt=0;
		$fill = false;
		foreach ($this->tableLines as $line) {
			$cells=$line[0];
			$type=$line[1];
			$ttl = $this->cellsQtd;
			$wCnt=0;

			$this->SetTextColor(0);
			$this->SetFillColor(255,255,255);
			if ($type == "image") {
				$pad = 0;
				$imgH = 0;
				foreach ($cells as $cell) {
					$img = explode('~',(string) $cell);
					if (intval($img[3]) > $imgH) {
						$imgH=intval($img[3]);
					}
				}

				if($this->y+$imgH>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
					// Automatic page break
					$x = $this->x;
					$ws = $this->ws;

					if($ws > 0) {
						$this->ws = 0;
						$this->_out('0 Tw');
					}

					$this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);
					$this->x = $x;

					if($ws > 0) {
						$this->ws = $ws;
						$this->_out(sprintf('%.3F Tw',$ws*$k));
					}
				}

				$this->y += 2;
				$cntCols = 0;
				$quebrouPag = false;
				foreach ($cells as $cell) {
					$cntCols++;
					// $pad = 0;
					// if ($nr>0)
					// {
					// 	for($a=0; $a<$nr; $a++)
					// 	{
					// 		$pad+=$this->tableLines[$a][0][2];
					// 	}
					// }
					// $this->Image($cells[0],$this->lMargin+$pad,$this->y,$cells[2],$cells[3]);
					// $this->SetFontSize(6);
					$img = explode('~',(string) $cell);
					$maxCols = intval($img[4]);

					if ($img[0] !== '') {
						file_put_contents("/var/www/log/tmp.log", date("Y-m-d H:i:s")." > "."0:".$img[0]." 1:".$img[1]." 2:".$img[2]." 3:".$img[3]." 4:".$img[4]."\n", FILE_APPEND);
						if ($cntCols>$maxCols && $maxCols>0) {
							$y=intval($this->GetY());
							$this->Ln();
							//$this->y = $this->y+$imgH+2;

							if ($img[1] !== "") {
								$this->y += 12;
							}

							$k = $this->k;

							if ($this->y > $this->PageBreakTrigger-15) {
								$quebrouPag = true;
								// Automatic page break
								$x = $this->x;
								$ws = $this->ws;

								if ($ws > 0) {
									$this->ws = 0;
									$this->_out('0 Tw');
								}

								$this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);
								$this->x = $x;

								if ($ws > 0) {
									$this->ws = $ws;
									$this->_out(sprintf('%.3F Tw',$ws*$k));
								}
							}

							$pad = $cntCols = 0;
						}
						$imgW = intval($img[2]);
						$imgH = intval($img[3]);
						$this->Image($img[0],$this->lMargin+$pad,$this->y,$imgW,$imgH);
						$this->Text($this->lMargin+$pad, $this->y+$imgH+4, $img[1]);
						$pad+=$imgW+2;
					}
				}
				$y = intval($this->GetY());
				$this->Ln();
				$this->y += 6;
				if ($quebrouPag) {
					$this->y += 2;
				}
				//$this->y = $this->y+$imgH+2;

			} else {
				switch ($type)
				{
					case 'footer':
					case 'summary':
						$this->SetFillColor(200,200,200);
						$fill=true;
						break;
					case 'info':
						$this->SetTextColor(0,0,254);
						break;
					case 'success':
						$this->SetTextColor(0,146,0);
						break;
					case 'warning':
						$this->SetTextColor(254,128,0);
						break;
					case 'danger':
						$this->SetTextColor(254,0,0);
						break;
					default:
						$this->SetFillColor(255,255,255);
				}

				for ($i = 0; $i < $ttl; $i++) {
					$cont = $cells[$i];
					// Colspan
					$colspan = 1;
					if (str_starts_with((string) $cont, "~")) {
						$colspan = substr((string) $cont, 1, 1);
						if ((ord(substr((string) $cont, 2, 1)) > 47) && (ord(substr((string) $cont, 2, 1)) < 58)) {
							$colspan.=substr((string) $cont, 2, 1);
							if ((ord(substr((string) $cont, 3, 1)) > 47) && (ord(substr((string) $cont, 3, 1)) < 58)) {
								$colspan.=substr((string) $cont, 3, 1);
								$cont = substr((string) $cont, 4);
							} else {
								$cont = substr((string) $cont, 3);
							}
						} else {
							$cont = substr((string) $cont, 2);
						}
						$colspan=intval($colspan);
					}

					// Alinhamento
					$align='L';
					if (str_starts_with((string) $cont, "->")) {
						$align = "R";
						$cont = substr((string) $cont, 2);
					}
					if (str_starts_with((string) $cont, "<-")) {
						$align = "L";
						$cont = substr((string) $cont, 2);
					}
					if (str_starts_with((string) $cont, "<>")) {
						$align = "C";
						$cont = substr((string) $cont, 2);
					}
					$w=0;
					for ($cs=$wCnt; $cs < ($wCnt+$colspan); $cs++) {
						$w+=$this->cellsWidths[$cs];
					}

					$wCnt+=$colspan;
					$ttl -= $colspan-1;
					$this->Cell($w, $line[2], str_replace('•','-',$cont), $this->tableBorder, 0, $align, $fill);
				}
			}

			$y = intval($this->GetY());
			$this->Ln();
			if ($this->tableAlternateColor) {
				$fill = !$fill;
			}

			if ($y >= $h) {
				$cnt++;
				$this->renderHeaders();
			}

		}

	}

	function getPageWidth()
	{
		if ($this->orientation=='P') {
			return 190;
		}
		return(280);
	}

	function tableEnd(): void
	{
		// Calcula largura das células (em %), varrendo todas as linhas e tendando identificar as larguras
		$s = '';
		$maxW = $this->getPageWidth();
		if ($this->tableSize != 'big') {
			$maxW = $maxW / 2;
		}
		// $larguraFonte = 2;

		if (!is_array($this->cellsWidths)) {
			// Calculando largura das colunas pelo Cabeçalho
			$primeiroQtd=0;
			foreach ($this->tableHeaders as $key=>$line) {
				if (count($line[0])>$this->cellsQtd) {
					$this->cellsQtd=count($line[0]);
				}
			}

			foreach ($this->tableHeaders as $key=>$line) {
				$qtd = count($line[0]);
				if (!is_array($this->cellsWidths)) {
					$s='';
					for ($a=0; $a<$this->cellsQtd; $a++)
					{
						$s.=$line[0][$a];
						$this->cellsWidths[$a]=strlen((string) $line[0][$a]);
					}
				}
				$len=strlen($s);

				$this->cellsX[0]=0;
				$lineHeight=5;
				$ttl=0;
				for ($a = 0; $a < $this->cellsQtd; $a++) {
					$cont = $line[0][$a];
					$cont = str_ireplace("<br>", "\n", $cont);
					$cont = str_ireplace("<br />", "\n", $cont);
					$cont = str_ireplace("<small>", "\n", $cont);
					$cont = str_ireplace("</small>", "\n", $cont);
					$cont = str_ireplace("<b>", "", $cont);
					$cont = str_ireplace("</b>", "", $cont);
					if ($key == 0 || $qtd>$primeiroQtd) {
						$x = intval( ($maxW * strlen((string) $line[0][$a])) / $len );
						$this->cellsWidths[$a] = $a<$this->cellsQtd-1 ? $x : $maxW-$ttl;
						$this->cellsX[$a]=$this->cellsWidths[$a-1]+$this->cellsX[$a-1];
						$ttl+=$x;
					}
					$line[0][$a] = $cont;
					$val = 5*(substr_count($cont, "\n")+1);
					if ($val>$lineHeight) {
						$lineHeight=$val;
					}
				}

				if ($qtd>$primeiroQtd) {
					$primeiroQtd=$qtd;
				}

				$line[2]=$lineHeight;
				$this->tableHeaders[$key]=$line;
			}

			// Calculando largura das colunas pelo detalhe (se não houver cabeçalho)
			$primeiroQtd=0;
			foreach ($this->tableLines as $key=>$line) {
				if (count($line[0])>$this->cellsQtd) {
					$this->cellsQtd=count($line[0]);
				}
			}

			foreach ($this->tableLines as $key=>$line) {
				$qtd = count($line[0]);
				if (!is_array($this->cellsWidths)) {
					$s='';
					for ($a=0; $a<$this->cellsQtd; $a++)
					{
						$s.=$line[0][$a];
						$this->cellsWidths[$a]=strlen((string) $line[0][$a]);
					}
				}

				$len=strlen($s);

				$this->cellsX[0]=0;
				$lineHeight=5;
				$ttl=0;
				for ($a = 0; $a < $this->cellsQtd; $a++) {
					$cont = $line[0][$a];
					$cont = str_ireplace("<br>", "\n", $cont);
					$cont = str_ireplace("<br />", "\n", $cont);
					$cont = str_ireplace("<small>", "\n", $cont);
					$cont = str_ireplace("</small>", "\n", $cont);
					$cont = str_ireplace("<b>", "", $cont);
					$cont = str_ireplace("</b>", "", $cont);
					if (($key == 0 || $qtd>$primeiroQtd) && !is_array($this->tableHeaders)) {
						$x = intval( ($maxW * strlen((string) $line[0][$a])) / $len );
						$this->cellsWidths[$a] = $a<$this->cellsQtd-1 ? $x : $maxW-$ttl;
						$this->cellsX[$a]=$this->cellsWidths[$a-1]+$this->cellsX[$a-1];
						$ttl+=$x;
					}

					$line[0][$a] = $cont;
					$val = 5*(substr_count($cont, "\n")+1);
					if ($val>$lineHeight) {
						$lineHeight=$val;
					}
				}

				if ($qtd>$primeiroQtd) {
					$primeiroQtd=$qtd;
				}
				$line[2]=$lineHeight;
				$this->tableLines[$key]=$line;
			}

		}

		if ($this->cellsQtd == 1) {
			$this->cellsWidths[0]=$maxW;
		}

		$this->SetLineWidth(.1);
		$this->SetFont('Arial','', $this->tableFontSize);

		$this->renderHeaders();
		$this->renderLines();

		$this->SetLineWidth(.1);
		$this->SetFont('Arial','', $this->detailFontSize);
		$this->SetTextColor(0);
		$this->SetDrawColor(0,0,0);
		$this->Ln();
	}

	function small($txt="") {
		return($txt);
	}
	function button($txt="") {}
	function ul($txt="") {}
	function dropdown($txt="") {}
	function nav($txt="") {}
	function addJavascript($txt="") {}
	function modal(){}


	//===================== gFW 3.0

	function __construct($json)
	{
		$mtz=cssDecode($json);
		$this->page = $_SERVER["PHP_SELF"] . "?g=" . $_REQUEST['g'];
		$this->header=$mtz['header'] != "false";
		$this->footer=$mtz['footer'] != "false";
		$this->fullFooter=$mtz['fullfooter'] != "false";
		$this->charset=$mtz['charset']=="" ? "utf8" : strtolower((string) $mtz['charset']);
		$this->defaultAlign=$mtz['defaultAlign'] != "" ? $mtz['defaultAlign'] : "L";
		$orientation=$mtz['orientation'] != "L" ? "P" : "L";

		if (isset($_REQUEST['gPDFOrientation']) != '') {
			$orientation=$_REQUEST['gPDFOrientation'];
		}

		$unit=$mtz['unit']=="" ? "mm" : $mtz['unit'];
		$format=$mtz['format']=="" ? "A4" : $mtz['format'];
		$this->footer1=gVar("pdf.footer1");
		$this->footer2=gVar("pdf.footer2");
		$this->footer3=gVar("pdf.footer3");

		$this->title=$mtz['title'];
		$this->subtitle=$mtz['subTitle'];
		$this->filter=$mtz['filter'];

		$this->orientation = $orientation;
		//Call parent constructor
		parent::__construct($orientation,$unit,$format);
		//Initialization
		$this->FontFamily='arial';
		$this->B=0;
		$this->I=0;
		$this->U=0;
		$this->HREF='';
		$this->fontlist=['arial', 'times', 'courier', 'helvetica', 'symbol'];
		$this->issetfont=false;
		$this->issetcolor=false;

		$this->tableborder=0;
		$this->tdbegin=false;
		$this->tdwidth=0;
		$this->tdheight=0;
		$this->tdalign="L";
		$this->tdbgcolor=false;

		$this->oldx=0;
		$this->oldy=0;

		$this->SetTitle($this->title, $this->subtitle, $this->filter);
		$this->SetAuthor($_SESSION['usrName']);
	}

	function Header(): void
	{
		global $gPathImg, $gPath, $gBASE;

		if ($this->header) {
			$fonttam=$this->detailFontSize;
			$logo=gVar("pdf.logojpgfile");
			$low=gVar("pdf.logowidth");
			if (gVar("pdf.logojpgfile_landscape") != '' && $this->orientation == "L") {
				$logo = gVar("pdf.logojpgfile_landscape");
			}

			if (gVar("pdf.logowidth_landscape") != '' && $this->orientation == "L") {
				$low = gVar("pdf.logowidth_landscape");
			}
			$loh = gVar("pdf.logoheight");
			$loa = gVar("pdf.logoalign");
			// $loa='left';
			if (is_array($loa)) {
				$loa=$loa[0];
			}
			$lot=explode(".",$logo);
			$lot=$lot[1];

			if ($this->bdCFC) {
				$logo2 = '/var/www/html/' . str_replace('_', '/', $this->bdCFC) . '/files/logo.jpg';
				if (file_exists($logo2)) {
					$this->Image($logo2, 170, 9, 30, 10, 'jpg');
				} else {
					$logo2 = '/var/www/html/' . str_replace('_', '/', $this->bdCFC) . '/files/logo_pdf.jpg';
					$this->Image($logo2, 170, 9, 30, 10, 'jpg');
				}
			}

			if ($loa == "left") {
				 // Logotipo
				if ($this->giusoftLogo) {
					$gPathImg = '/var/www/html/webcfc/detran/pr/pub/img/';
					$logo = 'giusoft.jpg';
					$this->Image($gPathImg . $logo,$this->lMargin,9,intval($low),intval($loh),$lot);
				} elseif (is_array($logo)) {
					$this->Image($gPathImg . $logo[0],$this->lMargin,9,intval($low[0]),intval($loh[0]),$lot);
					$this->Image($gPathImg . $logo[1],$w-intval($low[1]),9,intval($low[1]),intval($loh[1]),$lot);
				} else {

					if (file_exists($gPath.'files/logo_pdf.jpg')) {
						$this->Image($gPath . 'files/logo_pdf.jpg',$this->lMargin,9,intval($low),intval($loh),$lot);
					} else {
						$this->Image($gPathImg . $logo,$this->lMargin,9,intval($low),intval($loh),$lot);
					}
					//$this->Image($gPathImg . gVar("pdf.logojpgfile"),$this->lMargin,9,intval(gVar("pdf.logowidth")),intval(gVar("pdf.logoheight")),"jpg");
				}

				//Data
				if (gVar("pdf.dateheader") == "true") {
					$printDate=gT('print_date.short');
					$fmt=gVar("global.dateformat");
					$fmt=str_replace('yyyy','Y',$fmt);
					$fmt=str_replace('yy','y',$fmt);
					$fmt=str_replace('mm','m',$fmt);
					$fmt=str_replace('dd','d',$fmt);
					$this->SetFont(gVar("pdf.font"),'',8);
					$this->Cell(0,-4,$printDate." ".date("$fmt H:m"),0,0,'R');
				}
				//Title
				$this->SetFont(gVar("pdf.font"),'B',$fonttam+5);
				$this->x=0;
				$this->Cell(0,6,$this->title,0,1,'C');
				// SubTitle
				//Arial bold 15
				$this->SetFont(gVar("pdf.font"),'',$fonttam+3);
				$this->x=0;
				$this->Cell(0,6,$this->subtitle,0,1,'C');
				// Filter
				//Arial bold 15
				$this->SetFont(gVar("pdf.font"),'I',$fonttam);
				$this->x=0;
				$this->Cell(0,4,$this->filter,0,1,'C');

			} else {
				// Logotipo
				if (is_array($logo)) {
					//$this->Image($gPathImg . $logo[0],$this->lMargin,9,intval($low[0]),intval($loh[0]),$lot);
					$this->Image($gPathImg . $logo[1],$w-intval($low[1]),9,intval($low[1]),intval($loh[1]),$lot);
				} else {
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
			$this->Ln(3);
			$this->SetFont(gVar("pdf.font"),'',$fonttam);
		}
	}

	function Footer(): void
	{
		if ($this->footer) {
			$printDate=gT('print_date.short');
			$page=gT("page");
			$footer1=$this->footer1;
			$footer2=$this->footer2;
			$footer3=$this->footer3;
			// 	if (strtolower($this->charset)=="utf8")
			// 	{
			// 		$printDate=utf8_decode($printDate);
			// 		$page=utf8_decode($page);
			// 		$footer1=utf8_decode($footer1);
			// 		$footer2=utf8_decode($footer2);
			// 		$footer3=utf8_decode($footer3);
			// 	}
			if ($this->fullFooter) {
				//Position at 1.5 cm from bottom
				$this->SetY(-23);
				//Arial italic 8
				$this->SetFont(gVar("pdf.font"),'',8);
				//Text color in gray
				$this->SetTextColor(128);
				$this->SetDrawColor(128);
				//Page number LineWidth
				$this->Cell(0,10,$page.' '.$this->PageNo(),0,0,'R');

				$this->SetFont(gVar("pdf.font"),'',8);
				$this->SetY(-23);
				$this->Cell(0,10,$footer1,0,0,'C');
				$this->SetY(-20);
				$this->Cell(0,10,$footer2,0,0,'C');
				$this->SetY(-17);
				$this->Cell(0,10,$footer3,0,0,'C');
				$this->SetY(-23);
				if (gVar("pdf.datefooter") != "false") {
					$fmt=gVar("global.dateformat");
					$fmt=str_replace('yyyy','Y',$fmt);
					$fmt=str_replace('yy','y',$fmt);
					$fmt=str_replace('mm','m',$fmt);
					$fmt=str_replace('dd','d',$fmt);
					$this->SetFont(gVar("pdf.font"),'',8);
					$this->Cell(0,10,$printDate." ".date("$fmt H:i"),0,0,'L');
					$this->SetY(-20);
				} else {
					$this->Cell(0,10,"",0,0,'L');
					$this->SetY(-20);
				}

			} else {
				//Position at 1.5 cm from bottom
				$this->SetY(-20);
				//Arial italic 8
				$this->SetFont('Arial','',8);
				//Text color in gray
				$this->SetTextColor(128);
				$this->SetDrawColor(128);
				//Page number
				$this->Cell(0,10,$page.' '.$this->PageNo(),0,0,'C');
			}
		}

	}

	function SetTitle($title,$subtitle="",$filter=""): void
	{
		//Title of document
		$this->title = $title;
		//Subtitle of document
		$this->subtitle = $subtitle;
		//Filter of document
		$this->filter = $filter;
		//$this->author=end_site;
	}

	//===================== WriteHTML

	function WriteHTMLPages($html,$sep="~"): void
	{
		$htmls = explode($sep,(string) $html);
		foreach ($htmls as $pag) {
			$this->WriteHTML($pag);
			$this->AddPage();
		}
	}

	function WriteHTML($html): void
	{
		if (!$this->started) {
			$this->started=true;
			$this->AddPage();
			$this->SetFont(gVar("pdf.font"),'',10);
		}
		$html=strip_tags((string) $html,"<b><u><i><a><img><p><br><strong><em><font><tr><blockquote><hr><td><tr><table><sup>"); //remove all unsupported tags
		$html=str_replace("\n",'',$html); //replace carriage returns by spaces
		$html=str_replace("\t",'',$html); //replace carriage returns by spaces
		$a=preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE); //explodes the string
		foreach ($a as $i => $e) {
			$e=str_replace("&nbsp;"," ",$e);
			$e=str_replace("&hellip;"," ",$e);
			if ($i%2 == 0) {
				//Text
				if ($this->HREF) {
					$this->PutLink($this->HREF,$e);
				} elseif($this->tdbegin) {
					if(trim($e) !== '' && $e!="&nbsp;") {
						$this->Cell($this->tdwidth,$this->tdheight,$e,$this->tableborder,'',$this->tdalign,$this->tdbgcolor);
					} elseif($e=="&nbsp;") {
						$this->Cell($this->tdwidth,$this->tdheight,'',$this->tableborder,'',$this->tdalign,$this->tdbgcolor);
					}
				} else {
					if ($this->charset=='utf8') {
						$e=mb_convert_encoding($e, 'ISO-8859-1');
					}

					$this->Write(5,stripslashes((string) txtentities($e)));
				}
			} elseif ($e[0] === '/') {
				//Tag
				$this->CloseTag(strtoupper(substr($e,1)));
			} else {
				//Extract attributes
				$a2=explode(' ',$e);
				$tag=strtoupper(array_shift($a2));
				$attr=[];
				foreach($a2 as $v) {
					if (preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3)) {
						$attr[strtoupper($a3[1])]=$a3[2];
					}
				}
				$this->OpenTag($tag,$attr);
			}
		}
	}

	function OpenTag($tag, $attr): void
	{
		//Opening tag
		switch($tag)
		{

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
				$this->tableborder = empty($attr['BORDER']) ? 0 : $attr['BORDER'];
				break;
			case 'TR': //TR-BEGIN
				break;
			case 'TD': // TD-BEGIN
				if( !empty($attr['WIDTH']) ) {
					$this->tdwidth = ($attr['WIDTH']/4);
				}  else {
					$this->tdwidth = 40; // Set to your own width if you need bigger fixed cells
				}

				if( !empty($attr['HEIGHT']) ) {
					$this->tdheight = ($attr['HEIGHT']/6);
				} else {
					$this->tdheight = 6; // Set to your own height if you need bigger fixed cells
				}

				if(!empty($attr['ALIGN']) ) {

					$align=$attr['ALIGN'];
					if ($align=='LEFT') {
						$this->tdalign='L';
					}

					if ($align=='CENTER') {
						$this->tdalign='C';
					}

					if ($align=='RIGHT') {
						$this->tdalign='R';
					}
				} else {
					$this->tdalign='L';
				} // Set to your own

				if (!empty($attr['BGCOLOR']) ) {
					$coul=hex2dec($attr['BGCOLOR']);
					$this->SetFillColor($coul['R'],$coul['G'],$coul['B']);
					$this->tdbgcolor=true;
				}
				$this->tdbegin=true;
				break;

			case 'HR':
				$Width = empty($attr['WIDTH']) ? $this->w - $this->lMargin-$this->rMargin : $attr['WIDTH'];
				$x = $this->GetX();
				$y = $this->GetY();
				$this->SetLineWidth(0.2);
				$this->Line($x,$y,$x+$Width,$y);
				$this->SetLineWidth(0.2);
				$this->Ln(1);
				break;
			case 'STRONG':
				$this->SetStyle('B',true);
				break;
			case 'EM':
				$this->SetStyle('I',true);
				break;
			case 'B':
			case 'I':
			case 'U':
				$this->SetStyle($tag,true);
				break;
			case 'A':
				$this->HREF=$attr['HREF'];
				break;
			case 'IMG':
				if(isset($attr['SRC']) && (isset($attr['WIDTH']) || isset($attr['HEIGHT']))) {
					if (!isset($attr['WIDTH'])) {
						$attr['WIDTH'] = 0;
					}

					if (!isset($attr['HEIGHT'])) {
						$attr['HEIGHT'] = 0;
					}

					$this->Image($attr['SRC'], $this->GetX(), $this->GetY(), px2mm($attr['WIDTH']), px2mm($attr['HEIGHT']));
				}
				break;
			case 'BLOCKQUOTE':
			case 'BR':
				$this->Ln(5);
				break;
			case 'P':
				$this->Ln(10);
				break;
			case 'FONT':
				if (isset($attr['COLOR']) && $attr['COLOR']!='') {
					$coul=hex2dec($attr['COLOR']);
					$this->SetTextColor($coul['R'],$coul['G'],$coul['B']);
					$this->issetcolor=true;
				}

				if (isset($attr['FACE']) && in_array(strtolower((string) $attr['FACE']), $this->fontlist)) {
					$this->SetFont(strtolower((string) $attr['FACE']));
					$this->issetfont=true;
				}

				if (isset($attr['FACE']) && in_array(strtolower((string) $attr['FACE']), $this->fontlist) && isset($attr['SIZE']) && $attr['SIZE']!='') {
					$this->SetFont(strtolower((string) $attr['FACE']),'',$attr['SIZE']);
					$this->issetfont=true;
				}
				break;
		}
	}

	function CloseTag($tag): void
	{
		//Closing tag
		// if($tag=='SUP') {
		// }
		if($tag == 'TD') { // TD-END
			$this->tdbegin = false;
			$this->tdwidth = 0;
			$this->tdheight = 0;
			$this->tdalign = "L";
			$this->tdbgcolor = false;
		}

		if($tag=='TR') { // TR-END
			$this->Ln();
		}

		if($tag=='TABLE') { // TABLE-END
			//$this->Ln();
			$this->tableborder=0;
		}

		if ($tag=='STRONG') {
			$tag='B';
		}

		if ($tag=='EM') {
			$tag='I';
		}

		if ($tag=='B' || $tag=='I' || $tag=='U') {
			$this->SetStyle($tag,false);
		}

		if ($tag=='A') {
			$this->HREF='';
		}
		if ($tag=='FONT') {
			if ($this->issetcolor==true) {
				$this->SetTextColor(0);
			}

			if ($this->issetfont) {
				$this->SetFont('arial');
				$this->issetfont=false;
			}
		}
	}

	function SetStyle($tag, $enable): void
	{
		//Modify style and select corresponding font
		$this->$tag+=($enable ? 1 : -1);
		$style='';
		foreach(['B', 'I', 'U'] as $s) {
			if ($this->$s>0) {
				$style.=$s;
			}
		}
		$this->SetFont('',$style);
	}

	function PutLink($URL, $txt): void
	{
		//Put a hyperlink
		$this->SetTextColor(0,0,255);
		$this->SetStyle('U',true);
		$this->Write(5,$txt,$URL);
		$this->SetStyle('U',false);
		$this->SetTextColor(0);
	}


	//===================== Cell e vCell

	function VCell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false): void
	{
		if ($this->charset=='utf8') {
			$txt=mb_convert_encoding($txt, 'ISO-8859-1');
		}
		//Output a cell
		$k = $this->k;
		if ($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
			//Automatic page break
			$x=$this->x;
			$ws=$this->ws;
			if ($ws > 0) {
					$this->ws=0;
					$this->_out('0 Tw');
			}
			$this->AddPage($this->CurOrientation,$this->CurPageFormat);
			$this->x=$x;

			if ($ws > 0) {
					$this->ws=$ws;
					$this->_out(sprintf('%.3F Tw',$ws*$k));
			}
		}

		if ($w == 0) {
			$w=$this->w-$this->rMargin-$this->x;
		}

		$s='';
		// begin change Cell function
		if ($fill || $border > 0) {
			if ($fill) {
				$op=($border>0) ? 'B' : 'f';
			} else {
				$op='S';
			}

			if ($border > 1) {
				$s=sprintf('q %.2F w %.2F %.2F %.2F %.2F re %s Q ',$border,
								$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
			} else {
				$s=sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
			}
		}

		if (is_string($border)) {
			$x = $this->x;
			$y = $this->y;
			if (is_int(strpos($border,'L'))) {
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
			} elseif (is_int(strpos($border,'l'))) {
				$s .= sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
			}

			if (is_int(strpos($border,'T'))) {
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			} elseif (is_int(strpos($border,'t'))) {
				$s .= sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			}

			if (is_int(strpos($border,'R'))) {
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			} elseif (is_int(strpos($border,'r'))) {
				$s .= sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			}

			if (is_int(strpos($border,'B'))) {
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			} elseif (is_int(strpos($border,'b'))) {
				$s .= sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			}
		}

		if(trim((string) $txt) !== '') {
			$cr = substr_count((string) $txt,"\n");
			if ($cr > 0) { // Multi line
				$txts = explode("\n", (string) $txt);
				$lines = count($txts);
				for ($l = 0;$l < $lines;$l++) {
					$txt=$txts[$l];
					$w_txt=$this->GetStringWidth($txt);
					if ($align=='U') {
						$dy=$this->cMargin+$w_txt;
					} elseif ($align=='D') {
						$dy=$h-$this->cMargin;
					} else {
						$dy=($h+$w_txt)/2;
					}

					$txt=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
					if ($this->ColorFlag) {
						$s.='q '.$this->TextColor.' ';
					}
					$s.=sprintf('BT 0 1 -1 0 %.2F %.2F Tm (%s) Tj ET ',
						($this->x+.5*$w+(.7+$l-$lines/2)*$this->FontSize)*$k,
						($this->h-($this->y+$dy))*$k,$txt);
					if ($this->ColorFlag) {
						$s.=' Q ';
					}
				}
			} else { // Single line
				$w_txt=$this->GetStringWidth($txt);
				$Tz=100;
				if ($w_txt>$h-2*$this->cMargin) {
					$Tz=($h-2*$this->cMargin)/$w_txt*100;
					$w_txt=$h-2*$this->cMargin;
				}

				if ($align=='U') {
					$dy=$this->cMargin+$w_txt;
				} elseif ($align=='D') {
					$dy=$h-$this->cMargin;
				} else {
					$dy=($h+$w_txt)/2;
				}

				$txt=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
				if ($this->ColorFlag) {
					$s.='q '.$this->TextColor.' ';
				}
				$s .= sprintf('q BT 0 1 -1 0 %.2F %.2F Tm %.2F Tz (%s) Tj ET Q ',
								($this->x+.5*$w+.3*$this->FontSize)*$k,
								($this->h-($this->y+$dy))*$k,$Tz,$txt);
				if ($this->ColorFlag) {
					$s.=' Q ';
				}
			}
		}
		// end change Cell function
		if ($s !== '' && $s !== '0') {
			$this->_out($s);
		}

		$this->lasth=$h;
		if ($ln > 0) {
			//Go to next line
			$this->y+=$h;
			if ($ln == 1) {
				$this->x=$this->lMargin;
			}
		} else {
			$this->x+=$w;
		}
	}

	function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link=''): void
	{
		//Output a cell
		if ($this->charset == 'utf8') {
			$txt=mb_convert_encoding($txt, 'ISO-8859-1');
		}
		$k=$this->k;
		if ($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
			//Automatic page break
			$x=$this->x;
			$ws=$this->ws;
			if ($ws > 0) {
					$this->ws=0;
					$this->_out('0 Tw');
			}
			$this->AddPage($this->CurOrientation,$this->CurPageFormat);
			$this->x=$x;
			if ($ws > 0) {
					$this->ws=$ws;
					$this->_out(sprintf('%.3F Tw',$ws*$k));
			}
		}
		if ($w == 0) {
			$w=$this->w-$this->rMargin-$this->x;
		}
		$s='';
		// begin change Cell function
		if ($fill || $border > 0) {
			if ($fill) {
				$op=($border>0) ? 'B' : 'f';
			} else {
				$op='S';
			}

			if ($border > 1) {
				$s=sprintf('q %.2F w %.2F %.2F %.2F %.2F re %s Q ',$border,
					$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
			} else {
				$s=sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
			}
		}

		if (is_string($border)) {
			$x=$this->x;
			$y=$this->y;

			if (is_int(strpos($border,'L'))) {
				$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
			} elseif (is_int(strpos($border,'l'))) {
				$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
			}

			if (is_int(strpos($border,'T'))) {
				$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			} elseif (is_int(strpos($border,'t'))) {
				$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			}

			if (is_int(strpos($border,'R'))) {
				$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			} elseif (is_int(strpos($border,'r'))) {
				$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			}

			if (is_int(strpos($border,'B'))) {
				$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			} elseif (is_int(strpos($border,'b'))) {
				$s.=sprintf('q 2 w %.2F %.2F m %.2F %.2F l S Q ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			}
		}

		if (trim((string) $txt) !== '') {
			$cr=substr_count((string) $txt,"\n");
			if ($cr > 0) { // Multi line
				$txts = explode("\n", (string) $txt);
				$lines = count($txts);
				for ($l = 0;$l < $lines;$l++) {
					$txt=$txts[$l];
					$w_txt=$this->GetStringWidth($txt);
					if ($align=='R') {
						$dx=$w-$w_txt-$this->cMargin;
					} elseif ($align=='C') {
						$dx=($w-$w_txt)/2;
					} else {
						$dx=$this->cMargin;
					}

					$txt = str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
					if ($this->ColorFlag) {
						$s.='q '.$this->TextColor.' ';
					}
					$s.=sprintf('BT %.2F %.2F Td (%s) Tj ET ',
						($this->x+$dx)*$k,
						($this->h-($this->y+.5*$h+(.7+$l-$lines/2)*$this->FontSize))*$k,
						$txt);
					if ($this->underline) {
						$s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
					}
					if ($this->ColorFlag) {
						$s.=' Q ';
					}
					if ($link) {
						$this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$w_txt,$this->FontSize,$link);
					}
				}
			} else { // Single line

				$w_txt=$this->GetStringWidth($txt);
				$Tz=100;

				if ($w_txt>$w-2*$this->cMargin) { // Need compression
					$Tz=($w-2*$this->cMargin)/$w_txt*100;
					$w_txt=$w-2*$this->cMargin;
				}

				if ($align=='R') {
					$dx=$w-$w_txt-$this->cMargin;
				} elseif ($align=='C') {
					$dx=($w-$w_txt)/2;
				} else {
					$dx=$this->cMargin;
				}
				$txt=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
				if ($this->ColorFlag) {
					$s.='q '.$this->TextColor.' ';
				}
				$s.=sprintf('q BT %.2F %.2F Td %.2F Tz (%s) Tj ET Q ',
								($this->x+$dx)*$k,
								($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,
								$Tz,$txt);
				if ($this->underline) {
					$s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
				}
				if ($this->ColorFlag) {
					$s.=' Q ';
				}
				if ($link) {
					$this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$w_txt,$this->FontSize,$link);
				}
			}
		}

		// end change Cell function
		if ($s) {
			$this->_out($s);
		}
		$this->lasth=$h;

		if ($ln > 0) {
			//Go to next line
			$this->y+=$h;
			if ($ln == 1) {
				$this->x=$this->lMargin;
			}
		} else {
			$this->x+=$w;
		}
	}


	//===================== setDash

	function SetDash($black=null, $white=null): void
	{
		$s = '[] 0 d';
		if($black) {
			$s = sprintf('[%.3F %.3F] 0 d',$black*$this->k,$white*$this->k);
		}

		$this->_out($s);
	}

	//===================== TextWithDirection e TextWithRotation

	function TextWithDirection($x, $y, $txt, $direction='R'): void
	{
		if ($direction=='R') {
			$s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',1,0,0,1,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		} elseif ($direction=='L') {
			$s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',-1,0,0,-1,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		} elseif ($direction=='U') {
			$s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',0,1,-1,0,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		} elseif ($direction=='D') {
			$s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',0,-1,1,0,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		} else {
			$s=sprintf('BT %.2F %.2F Td (%s) Tj ET',$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		}

		if ($this->ColorFlag) {
			$s='q '.$this->TextColor.' '.$s.' Q';
		}
		$this->_out($s);
	}

	function TextWithRotation($x, $y, $txt, $txt_angle, $font_angle=0): void
	{
		$font_angle+=90+$txt_angle;
		$txt_angle*=M_PI/180;
		$font_angle*=M_PI/180;

		$txt_dx=cos($txt_angle);
		$txt_dy=sin($txt_angle);
		$font_dx=cos($font_angle);
		$font_dy=sin($font_angle);

		$s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',$txt_dx,$txt_dy,$font_dx,$font_dy,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		if ($this->ColorFlag) {
			$s='q '.$this->TextColor.' '.$s.' Q';
		}
		$this->_out($s);
	}
	//===================== DashedRect

	function DashedRect($x1, $y1, $x2, $y2, $width=1, $nb=15): void
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

	function RoundedRect($x, $y, $w, $h, $r, $style = ''): void
	{
		$k = $this->k;
		$hp = $this->h;
		if ($style=='F') {
			$op='f';
		} elseif ($style=='FD' || $style=='DF') {
			$op='B';
		} else {
			$op='S';
		}
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

    function Sector($xc, $yc, $r, $a, $b, $style='FD', $cw=true, $o=90): void
    {
        $d0 = $a - $b;
        if($cw){
            $d = $b;
            $b = $o - $a;
            $a = $o - $d;
        }else{
            $b += $o;
            $a += $o;
        }
        while($a<0)
            $a += 360;
        while($a>360)
            $a -= 360;
        while($b<0)
            $b += 360;
        while($b>360)
            $b -= 360;
        if ($a > $b) {
            $b += 360;
        }
        $b = $b/360*2*M_PI;
        $a = $a/360*2*M_PI;
        $d = $b - $a;
        if ($d == 0 && $d0 != 0) {
            $d = 2*M_PI;
        }
        $k = $this->k;
        $hp = $this->h;
        $MyArc = 0;
		if (sin($d/2)) {
            $MyArc = 4/3*(1-cos($d/2))/sin($d/2)*$r;
		}
        //first put the center
        $this->_out(sprintf('%.2F %.2F m',($xc)*$k,($hp-$yc)*$k));
        //put the first point
        $this->_out(sprintf('%.2F %.2F l',($xc+$r*cos($a))*$k,(($hp-($yc-$r*sin($a)))*$k)));
        //draw the arc
        if ($d < M_PI/2) {
            $this->_Arc($xc+$r*cos($a)+$MyArc*cos(M_PI/2+$a),
                        $yc-$r*sin($a)-$MyArc*sin(M_PI/2+$a),
                        $xc+$r*cos($b)+$MyArc*cos($b-M_PI/2),
                        $yc-$r*sin($b)-$MyArc*sin($b-M_PI/2),
                        $xc+$r*cos($b),
                        $yc-$r*sin($b)
                        );
        } else {
            $b = $a + $d/4;
            $MyArc = 4/3*(1-cos($d/8))/sin($d/8)*$r;
            $this->_Arc($xc+$r*cos($a)+$MyArc*cos(M_PI/2+$a),
                        $yc-$r*sin($a)-$MyArc*sin(M_PI/2+$a),
                        $xc+$r*cos($b)+$MyArc*cos($b-M_PI/2),
                        $yc-$r*sin($b)-$MyArc*sin($b-M_PI/2),
                        $xc+$r*cos($b),
                        $yc-$r*sin($b)
                        );
            $a = $b;
            $b = $a + $d/4;
            $this->_Arc($xc+$r*cos($a)+$MyArc*cos(M_PI/2+$a),
                        $yc-$r*sin($a)-$MyArc*sin(M_PI/2+$a),
                        $xc+$r*cos($b)+$MyArc*cos($b-M_PI/2),
                        $yc-$r*sin($b)-$MyArc*sin($b-M_PI/2),
                        $xc+$r*cos($b),
                        $yc-$r*sin($b)
                        );
            $a = $b;
            $b = $a + $d/4;
            $this->_Arc($xc+$r*cos($a)+$MyArc*cos(M_PI/2+$a),
                        $yc-$r*sin($a)-$MyArc*sin(M_PI/2+$a),
                        $xc+$r*cos($b)+$MyArc*cos($b-M_PI/2),
                        $yc-$r*sin($b)-$MyArc*sin($b-M_PI/2),
                        $xc+$r*cos($b),
                        $yc-$r*sin($b)
                        );
            $a = $b;
            $b = $a + $d/4;
            $this->_Arc($xc+$r*cos($a)+$MyArc*cos(M_PI/2+$a),
                        $yc-$r*sin($a)-$MyArc*sin(M_PI/2+$a),
                        $xc+$r*cos($b)+$MyArc*cos($b-M_PI/2),
                        $yc-$r*sin($b)-$MyArc*sin($b-M_PI/2),
                        $xc+$r*cos($b),
                        $yc-$r*sin($b)
                        );
        }
        //terminate drawing
        if ($style == 'F') {
            $op = 'f';
        } elseif ($style == 'FD' || $style == 'DF') {
            $op = 'b';
        } else {
            $op = 's';
        }
        $this->_out($op);
    }

   	function _Arc($x1, $y1, $x2, $y2, $x3, $y3): void
	{
		$h = $this->h;
		$this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
		$x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
	}

	//===================== BarCode Start

	function EAN13($x, $y, $barcode, $h=16, $w=.35): void
	{
		$this->Barcode($x,$y,$barcode,$h,$w,13);
	}

	function UPC_A($x, $y, $barcode, $h=16, $w=.35): void
	{
		$this->Barcode($x,$y,$barcode,$h,$w,12);
	}

	function Code39($xpos, $ypos, $code, $baseline=0.5, $height=5): void
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

		$code = '*'.strtoupper((string) $code).'*';
		for($i=0; $i<strlen($code); $i++){
			$char = $code[$i];
			if(!isset($barChar[$char])){
					$this->Error('Invalid character in barcode: '.$char);
			}
			$seq = $barChar[$char];
			for($bar=0; $bar<9; $bar++){
				$lineWidth = $wide;
				if ($seq[$bar] == 'n') {
					$lineWidth = $narrow;
				}

				if ($bar % 2 == 0) {
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
		for($i=1;$i<=11;$i+=2) {
			$sum += 3 * $barcode[$i];
		}

		for($i=0;$i<=10;$i+=2) {
			$sum += $barcode[$i];
		}
		$r = $sum % 10;
		if ($r > 0) {
			return 10 - $r;
		}

		return $r;
	}

	function TestCheckDigit($barcode)
	{
		//Test validity of check digit
		$sum=0;
		for($i=1;$i<=11;$i+=2) {
			$sum += 3 * $barcode[$i];
		}
		for($i=0;$i<=10;$i+=2) {
			$sum += $barcode[$i];
		}

		return ($sum+$barcode[12])%10==0;
	}

	function Barcode($x, $y, $barcode, $h, $w, $len): void
	{
		//Padding
		$barcode=str_pad((string) $barcode,$len-1,'0',STR_PAD_LEFT);
		if ($len == 12) {
			$barcode='0'.$barcode;
		}
		//Add or control the check digit
		if (strlen($barcode) == 12) {
			$barcode.=$this->GetCheckDigit($barcode);
		} elseif (!$this->TestCheckDigit($barcode)) {
			$this->Error('Incorrect check digit');
		}
		//Convert digits to bars
		$codes = [
			'A' => [
				'0'=>'0001101','1'=>'0011001','2'=>'0010011','3'=>'0111101','4'=>'0100011',
				'5'=>'0110001','6'=>'0101111','7'=>'0111011','8'=>'0110111','9'=>'0001011',
			],
			'B' => [
				'0'=>'0100111','1'=>'0110011','2'=>'0011011','3'=>'0100001','4'=>'0011101',
				'5'=>'0111001','6'=>'0000101','7'=>'0010001','8'=>'0001001','9'=>'0010111',
			],
			'C' => [
				'0'=>'1110010','1'=>'1100110','2'=>'1101100','3'=>'1000010','4'=>'1011100',
				'5'=>'1001110','6'=>'1010000','7'=>'1000100','8'=>'1001000','9'=>'1110100'
			]
		];

		$parities = [
			'0' => ['A','A','A','A','A','A'],
			'1' => ['A','A','B','A','B','B'],
			'2' => ['A','A','B','B','A','B'],
			'3' => ['A','A','B','B','B','A'],
			'4' => ['A','B','A','A','B','B'],
			'5' => ['A','B','B','A','A','B'],
			'6' => ['A','B','B','B','A','A'],
			'7' => ['A','B','A','B','A','B'],
			'8' => ['A','B','A','B','B','A'],
			'9' => ['A','B','B','A','B','A']
		];
		$code = '101';
		$p = $parities[$barcode[0]];
		for ($i = 1;$i <= 6; $i++) {
			$code .= $codes[$p[$i-1]][$barcode[$i]];
			$code .= '01010';
		}

		for ($i = 7;$i <= 12; $i++) {
			$code .= $codes['C'][$barcode[$i]];
			$code .= '101';
		}
		//Draw bars
		for ($i = 0; $i < strlen($code); $i++) {
			if ($code[$i] === '1') {
				$this->Rect($x+$i*$w,$y,$w,$h,'F');
			}
		}
		//Print text uder barcode
		$this->SetFont('Arial','',12);
		$this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
	}



	/************************************************************
	*                                                           *
	*    MultiCell with bullet (array)                          *
	*                                                           *
	*    Requires an array with the following  keys:            *
	*                                                           *
	*        Bullet -> String or Number                         *
	*        Margin -> Number, space between bullet and text    *
	*        Indent -> Number, width from current x position    *
	*        Spacer -> Number, calls Cell(x), spacer=x          *
	*        Text -> Array, items to be bulleted                *
	*                                                           *
	************************************************************/

	function MultiCellBltArray($w, $h, $blt_array, $border=0, $align='J', $fill=false): void
	{
		if (!is_array($blt_array)) {
			die('MultiCellBltArray requires an array with the following keys: bullet,margin,text,indent,spacer');
		}

		//Save x
		$bak_x = $this->x;

		for ($i = 0; $i < sizeof($blt_array['text']); $i++) {
			//Get bullet width including margin
			$blt_width = $this->GetStringWidth($blt_array['bullet'] . $blt_array['margin'])+$this->cMargin*2;

			// SetX
			$this->SetX($bak_x);

			//Output indent
			if ($blt_array['indent'] > 0) {
				$this->Cell($blt_array['indent']);
			}

			//Output bullet
			$this->Cell($blt_width,$h,$blt_array['bullet'] . $blt_array['margin'],0,'',$fill);

			//Output text
			$this->MultiCell($w-$blt_width,$h,$blt_array['text'][$i],$border,$align,$fill);

			//Insert a spacer between items if not the last item
			if ($i !== count($blt_array['text'])-1) {
				$this->Ln($blt_array['spacer']);
			}

			//Increment bullet if it's a number
			if (is_numeric($blt_array['bullet'])) {
				$blt_array['bullet']++;
			}
		}

		//Restore x
		$this->x = $bak_x;
	}



    function PieChart($w, $h, $data, $format, $colors=null): void
    {
        $this->SetFont('Arial', '', 8);
        $this->SetLegends($data,$format);

        $XPage = $this->GetX();
        $YPage = $this->GetY();
        $margin = 2;
        $hLegend = 5;
        $radius = min($w - $margin * 4 - $hLegend - $this->wLegend, $h - $margin * 2);
        $radius = floor($radius / 2);
        $XDiag = $XPage + $margin + $radius;
        $YDiag = $YPage + $margin + $radius;
        if($colors == null) {
            for($i = 0; $i < $this->NbVal; $i++) {
                $gray = $i * intval(255 / $this->NbVal);
                $colors[$i] = [$gray, $gray, $gray];
            }
        }
        if (is_array($this->colorArray))
        {
        	$colors = $this->colorArray;
        }

        //Sectors
        $this->SetLineWidth(0.2);
        $angleStart = 0;
        $angleEnd = 0;
        $i = 0;
        foreach($data as $val) {
            $angle = ($val * 360) / floatval($this->sum);
            if ($angle != 0) {
                $angleEnd = $angleStart + $angle;
                $this->SetFillColor($colors[$i][0],$colors[$i][1],$colors[$i][2]);
                $this->Sector($XDiag, $YDiag, $radius, $angleStart, $angleEnd);
                $angleStart += $angle;
            }
            $i++;
        }

        //Legends
        $this->SetFont('Arial', '', 8);
        $x1 = $XPage + 2 * $radius + 4 * $margin;
        $x2 = $x1 + $hLegend + $margin;
        $y1 = $YDiag - $radius + (2 * $radius - $this->NbVal*($hLegend + $margin)) / 2;
        for($i=0; $i<$this->NbVal; $i++) {
            $this->SetFillColor($colors[$i][0],$colors[$i][1],$colors[$i][2]);
            $this->Rect($x1, $y1, $hLegend, $hLegend, 'DF');
            $this->SetXY($x2,$y1);
            $this->Cell(0,$hLegend,$this->legends[$i]);
            $y1+=$hLegend + $margin;
        }
    }

    function BarDiagram($w, $h, $data, $format, $color=null, $maxVal=0, $nbDiv=4): void
    {
        $this->SetFont('Arial', '', 8);
        $this->SetLegends($data,$format);

        $XPage = $this->GetX();
        $YPage = $this->GetY();
        $margin = 2;
        $YDiag = $YPage + $margin;
        $hDiag = floor($h - $margin * 2);
        $XDiag = $XPage + $margin * 2 + $this->wLegend;
        $lDiag = floor($w - $margin * 3 - $this->wLegend);

		if ($color == null) {
            $color = [155, 155, 155];
        }

		if ($maxVal == 0) {
            $maxVal = max($data);
        }

        $valIndRepere = ceil($maxVal / $nbDiv);
        $maxVal = $valIndRepere * $nbDiv;
        $lRepere = floor($lDiag / $nbDiv);
        $lDiag = $lRepere * $nbDiv;
        $unit = $lDiag / $maxVal;
        $hBar = floor($hDiag / ($this->NbVal + 1));
        $hDiag = $hBar * ($this->NbVal + 1);
        $eBaton = floor($hBar * 80 / 100);

        $this->SetLineWidth(0.2);
        $this->Rect($XDiag, $YDiag, $lDiag, $hDiag);

        $this->SetFont('Arial', '', 8);
        $this->SetFillColor($color[0],$color[1],$color[2]);
        $i=0;

        foreach($data as $val) {
            //Bar
            $xval = $XDiag;
            $lval = (int)($val * $unit);
            $yval = $YDiag + ($i + 1) * $hBar - $eBaton / 2;
            $hval = $eBaton;
            $this->Rect($xval, $yval, $lval, $hval, 'DF');
            //Legend
            $this->SetXY(0, $yval);
            $this->Cell($xval - $margin, $hval, $this->legends[$i],0,0,'R');
            $i++;
        }

        //Scales
        for ($i = 0; $i <= $nbDiv; $i++) {
            $xpos = $XDiag + $lRepere * $i;
            $this->Line($xpos, $YDiag, $xpos, $YDiag + $hDiag);
            $val = $i * $valIndRepere;
            $xpos = $XDiag + $lRepere * $i - $this->GetStringWidth($val) / 2;
            $ypos = $YDiag + $hDiag - $margin;
            $this->Text($xpos, $ypos, $val);
        }
    }

    function SetLegends($data, $format): void
    {
        $this->legends = [];
        $this->wLegend=0;
        $this->sum=array_sum($data);
        $this->NbVal=count($data);
        foreach($data as $l=>$val) {
        	if (!is_numeric($l)) {
	        	$v = intval($val)/$this->sum*100;
	            $p=sprintf('%.2f',$v).'%';
	            $legend=str_replace(['%l', '%v', '%p'],[$l, $val, $p],$format);
	            $this->legends[]=$legend;
	            $this->wLegend=max($this->GetStringWidth($legend),$this->wLegend);
        	}
        }
    }


    function setGraphColors($colorTheme): void
    {
        $colors = getGraphColors($colorTheme);
        $this->colorArray = '';
        foreach ($colors as $color) {
            $this->colorArray[] = [hexdec(substr($color,1,2)), hexdec(substr($color,3,2)), hexdec(substr($color,5,2))];
        }
    }

    function ColumnChart($w, $h, $data, $format, $color=null, $maxVal=0, $nbDiv=4,$y_label=""): void
    {

        // RGB for color 0
        $colors[0][0] = 155;
        $colors[0][1] = 75;
        $colors[0][2] = 155;

        // RGB for color 1
        $colors[1][0] = 0;
        $colors[1][1] = 155;
        $colors[1][2] = 0;

        // RGB for color 2
        $colors[2][0] = 75;
        $colors[2][1] = 155;
        $colors[2][2] = 255;

        // RGB for color 3
        $colors[3][0] = 75;
        $colors[3][1] = 0;
        $colors[3][2] = 155;

        if (is_array($this->colorArray))
        {
        	$colors = $this->colorArray;
        }

        $this->SetFont('Arial', '', 8);
        $this->SetLegends2($data,$format,$y_label);

        //***********************
        if ($y_label){
        	$XPage2 = $this->GetX();
	        $YPage2 = $this->GetY();

	        $XPage = $this->GetX() + 108;
	        $YPage = $this->GetY() - 14;

	        $margin = 1;
	        $hLegend = 5;
	        $radius = min($w - 4 - $hLegend - count($y_label), $h - 2);
	        $radius = floor($radius / 2);
	        $XDiag = $XPage + $margin + $radius;
	        $YDiag = $YPage + $margin + $radius;

	        $x1 = $XPage + 2 * $radius + 4;
	        $x2 = $x1 + $hLegend + $margin;
	        $y1 = $YDiag - $radius + (2 * $radius - count($y_label)*($hLegend + $margin)) / 2;
			$contador = count($y_label);
	        for ($i = 0; $i < $contador; $i++) {
	            $this->SetFillColor($colors[$i][0],$colors[$i][1],$colors[$i][2]);
	            $this->Rect($x1, $y1, $hLegend, $hLegend, 'DF');
	            $this->SetXY($x2,$y1);
	            $this->Cell(0,$hLegend,$y_label[$i]);
	            $y1+=$hLegend + $margin;
	        }
	        //$this->Cell($lval, 5, "teste",0,0,'C');
	        $this->SetXY($XPage2,$YPage2);
        }
        //***********************

        // Removendo as legendas (primeiro elemento)
    	$data2 = [];
    	foreach ($data as $d) {
    		$d2 = [];
			$contador = count($d);
    		for ($i = 1; $i < $contador; $i++) {
    			$d2[] = $d[$i];
			}

    		$data2[]=$d2;
    	}

		$data = $data2;

        // Starting corner (current page position where the chart has been inserted)
        $XPage = $this->GetX();
        $YPage = $this->GetY();
        $margin = 2;

        // Y position of the chart
        $YDiag = $YPage + $margin;

        // chart HEIGHT
        $hDiag = floor($h - $margin * 2);

        // X position of the chart
        $XDiag = $XPage + $margin;

        // chart LENGHT
        $lDiag = floor($w - $margin * 3 - $this->wLegend);

        if ($color == null) {
            $color=[155, 155, 155];
        }

        if ($maxVal == 0) {
            foreach ($data as $val) {
                if (max($val) > $maxVal) {
                    $maxVal = max($val);
                }
            }
        }

        // define the distance between the visual reference lines (the lines which cross the chart's internal area and serve as visual reference for the column's heights)
        $valIndRepere = ceil($maxVal / $nbDiv);

        // adjust the maximum value to be plotted (recalculate through the newly calculated distance between the visual reference lines)
        $maxVal = $valIndRepere * $nbDiv;

        // define the distance between the visual reference lines (in milimeters)
        $hRepere = floor($hDiag / $nbDiv);

        // adjust the chart HEIGHT
        $hDiag = $hRepere * $nbDiv;

        // determine the height unit (milimiters/data unit)
        $unit = $hDiag / $maxVal;

        // determine the bar's thickness
        $lBar = floor($lDiag / ($this->NbVal + 1));
        $lDiag = $lBar * ($this->NbVal + 1);
        $eColumn = floor($lBar * 80 / 100);

        $this->SetLineWidth(0.2);
        $this->Rect($XDiag, $YDiag, $lDiag, $hDiag);

        // draw the chart border
        $this->SetLineWidth(0.2);
        $this->Rect($XDiag, $YDiag, $lDiag, $hDiag);

        $this->SetFont('Arial', '', 8);
        $this->SetFillColor($color[0],$color[1],$color[2]);

        //Scales
        for ($i = 0; $i <= $nbDiv; $i++) {
            $ypos = $YDiag + $hRepere * $i;
            $this->Line($XDiag, $ypos, $XDiag + $lDiag, $ypos);
            $val = ($nbDiv - $i) * $valIndRepere;
            $ypos = $YDiag + $hRepere * $i;
            $xpos = $XDiag - $margin - $this->GetStringWidth($val);
            $this->Text($xpos, $ypos, $val);
        }

        $i=0;
        foreach ($data as $val) {
            //Column
            $yval = $YDiag + $hDiag;
            $xval = $XDiag + ($i + 1) * $lBar - $eColumn/2;
            $lval = floor($eColumn/(count($val)));
            $j=0;
            foreach ($val as $v) {
                $hval = (int)($v * $unit);
                $this->SetFillColor($colors[$j][0], $colors[$j][1], $colors[$j][2]);
                $this->Rect($xval+($lval*$j), $yval, $lval, -$hval, 'DF');
                $j++;
            }

            //Legend
            $this->SetXY($xval, $yval + $margin);
            $this->Cell($lval, 5, $this->legends[$i],0,0,'C');
            $i++;
        }

    }

    function SetLegends2($data, $format,$y_label): void
    {

    	$legends = [];
    	foreach ($data as $d) {
    		$legends[]=$d[0];
    	}
        $this->legends=$legends;
        if ($y_label) {
        	$this->wLegend = 22;
		} else {
        	$this->wLegend = 0;
		}

        $this->NbVal=count($data);
    }
}

?>
