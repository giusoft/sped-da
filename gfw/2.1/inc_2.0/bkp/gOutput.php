<?
/* Definiï¿½ï¿½o de constantes */
// Tabelas
define("gI_TINY",0);		// Para imagens: Tamanho pequeno
define("gI_MEDIUM",1);		// Para imagens: Tamanho médio
define("gI_BIG",2);		// Para imagens: Tamanho grande
define("gI_SUPERBIG",3);		// Para imagens: Tamanho grande


define(gT_DEFAULT,0);                                         //Tabela de tamanho pequeno
define(gT_TINY,1);                                        //Tabela de tamanho pequeno
define(gT_MEDIUM,2);                                  //Tabela de tamanho mï¿½dio
define(gT_BIG,3);                                         //Tabela de tamanho pequeno
define(gT_HEADER,0);
define(gT_SUBTITLE,1);
define(gT_DETAIL,2);
define(gT_FOOTER,3);
define(gT_SUMMARY,4);
define(gT_TOTAL,5);
define(gT_TOOL,6);
define(gT_ALERT,7);
define(gT_RED,8);
define(gT_DISABLE,9);
define(gT_ENABLE,10);
define(gT_GRIDDETAIL,11);
define(gT_TAB,12);

// Mensagens
define(gM_TINY,1);                                       //Mensagem de tamanho pequeno
define(gM_MEDIUM,3);                                 //Mensagem de tamanho mï¿½dio
define(gM_BIG,5);                                        //Mensagem de tamanho grande
define(gM_NORMAL,0);                                //Mensagem normal

//include $gPathDefault."gStart.php";

/** Classe responsável pela geração de código HTML para objetos de saída de dados
* @package gOutput
* @author Giuliano Nascimento
* @version 2.0
*/
class gOutput
{
	var $buffer,$page_first;
	var $filter;
	var $table_content;
	var $table_count;
	var $table_controls;
	var $report_title, $report_subtitle, $report_filter;
	var $tab_count;
	var $tab_actual;
	var $tab_selected;
	var $tabs;
	var $table_stack;

	function gOutput()
	{
		$buffer="";
		$this->page_first=true;
		$this->table_count=0;
		$this->table_controls=false;
		$this->tabs="";
		$this->tab_count=0;
		$this->tab_actual=0;
		$this->tab_selected=-1;
		$this->table_stack="";
	}

	function gOut($txt)
	{
/*
Funcionalidade: Gera uma saída na pï¿½gina(somente se não estiver exportando dados).
Parï¿½metros:
    $txt       = texto que vai ser gerado como saída da pï¿½gina.
Criado por: Giuliano
*/
		if ($this->_getFilter()=="")
		{
			//$buffer=$buffer.$txt;
			echo $txt;
		}
	}

	function gEcho($txt)
	{
/*
Funcionalidade: Gera uma saída na pï¿½gina(somente se não estiver exportando dados).
Parï¿½metros:
    $txt       = texto que vai ser gerado como saída da pï¿½gina.
Criado por: Giuliano
*/
		echo $txt;
	}

	function gBeginJS()
	{
	}

	function _setFilter($filter='')
	{
		$this->filter=strtoupper($filter);
	}
	function _getFilter()
	{
		return($this->filter);
	}

	function gBegin($filter='')
	{
/*
Funcionalidade: Gera o cabeï¿½alho da pï¿½gina.
Parï¿½metros:
    Nenhum
Criado por: Giuliano
*/
		global $gDebug;
		global $http_img;
		
		$this->_setFilter($filter);
		if ($filter!='')
		{
			session_cache_limiter("nocache");
			$filter=strtoupper($filter);
			$type = "application/force-download";
			$disp = "attachment";
			$name="export.".strtolower($filter);
			header("Content-disposition: ".$disp."; filename=$name");
			header("Content-type: ".$type);
			header("Connection: close");
			header("Expires: 0");
			header("Pragma: public");
			header("Cache-Control: private");
			set_time_limit(0);
			if ($this->_getFilter()=='HTM')
			{
				$this->gEcho('<html><head><title>'.gVar("global.site").'</title></head><body>');
			}
		}
		else
		{
			if ($gDebug>0)
			{
				gLog("gOutput.php	\033[34;1mgBegin");
			}
			$this->gOut(gTag("page.header_start"));
			if (gVar("global.css")=="true")
			{
				$this->gOut(gTag("page.css"));
				$this->gOut("<link href='$http_img/schema/".gVar("global.schema")."/body.css' rel='stylesheet' type='text/css'>");
			}

			$this->gOut(gTag("page.header_end"));
			$this->gBeginJS();
			
		}
		if (gVar("global.align")<>"")
			$this->gOut("<div align='".gVar("global.align")."'>");
	}

	function gEnd()
	{
/*
Funcionalidade: Gera o rodapï¿½ da pï¿½gina.
Parï¿½metros:
    Nenhum
Criado por: Giuliano
*/
		global $gDebug;
		$this->gOut(gTag("page.footer"));
		echo $buffer;

		if ($gDebug>0)
		{
			gLog("gOutput.php	\033[34;1mgEnd");
		}
		if (gVar("global.align")<>"")
			$this->gOut("</div>");
		if ($this->_getFilter()=='HTM')
		{
			$this->gEcho('</body></html>');
		}
		$this->_setFilter('');
	}

	function gMsg($text,$style=0,$alert=false)
	{
/*
Funcionalidade: Gera um texto formatado na pï¿½gina.
Parï¿½metros:
    $text       = texto que vai aparecer formatado na pï¿½gina.
    $style      = estilo do objeto gerado.Style = 1 a 5
Criado por: Giuliano
*/
		global $cr;
		$sMsg=gLng($text);
		$style_end="<br />";
		if (($style<>"") && ($style>0))
		{
			$style_start="<H" .$style .">";
			$style_end="</H" .$style .">";
		}
		if ($this->_getFilter()=='')
		{
			if ($alert)
			{
				$style_start.="<font style='font-size: 10pt; font-weight: bold; color: #ff0000'>";
				$style_end="</font>".$style_end;
			}
			$this->gOut($style_start .$sMsg .$style_end);
		}
		elseif ($this->_getFilter()=='TXT')
		{
			if ($style>0)
				$this->gEcho(strtoupper($sMsg).$cr);
			else
				$this->gEcho($sMsg.$cr);
		}
		elseif ($this->_getFilter()=='PRN')
		{
			if ($style>0)
			{
				if ($style==2) $this->report_title=$sMsg;
				if ($style==3) $this->report_subtitle=$sMsg;
				if (($style>=4) || ($style==0)) $this->report_filter=$sMsg;
			}
			else
				$this->gEcho($sMsg.$cr);
		}
		elseif (($this->_getFilter()=='HTM') || ($this->_getFilter()=='DOC') || ($this->_getFilter()=='XLS'))
		{
			$this->gEcho($style_start .$sMsg .$style_end.$cr);
		}
	}

	function gMsgTitle($text)
	{
		$this->gMsg($text,2);
		$this->report_title=$text;
	}

	function gMsgSubTitle($text)
	{
		$this->gMsg($text,3);
		$this->report_subtitle=$text;
	}

	function gMsgFilter($text)
	{
		$this->gMsg($text);$this->gBr();
		$this->report_filter=$text;
	}

	function gMsgError($text)
	{
		$this->gMsg($text,0,true);
	}

	function gTxt($text)
	{
/*
Funcionalidade: Gera uma mensagem formatada e alguns botï¿½es.
Parï¿½metros:
    $text         = texto que vai ser gerado como saída da pï¿½gina.
Criado por: Giuliano
*/
		$this->gOut($text ."<br />");
	}

	function gBr($quantity=1)
	{
/*
Funcionalidade: Gera "quantity" <br>//s
Parï¿½metros:
    $quantity         = quantidade de quebras de pï¿½gina(<br>).
Criado por: Giuliano
*/
		for ($a=0;$a<$quantity;$a++)
		{
			$this->gOut("<br />");
		}
	}

	function gSpc($quantity=1)
	{
/*
Funcionalidade: Gera "quantity" <nbsp>//s
Parï¿½metros:
    $quantity         = quantidade de espaï¿½os(<nbsp>).
Criado por: Giuliano
*/
for ($a=0;$a<$quantity;$a++)
		{
			$this->gOut("&nbsp;");
		}
	}

/**
Monta o cabeï¿½alho da tabela de acordo com os parï¿½metros
@style	integer	tamanho da tabela.
@border	boolean	true=com borda,false=sem borda
@controls	boolean	true=exibe controles de minimizar, maximizar e fechar, false=não
Criado por: Giuliano
*/
  function gTableBegin($style=gT_DEFAULT,$border=false, $controls=false)
	{
		global $http_inc;
		if ($this->_getFilter()=='')
		{
			$this->table_controls=$controls;
			$this->table_count++;
			if ($this->table_controls)
			{
				$this->gOut("<div style='width: 100%; display: block;' id='gControls".$this->table_count."'>");
				$this->gOut("<a title='Clique aqui para minimizar esta tabela' class='imagem' href='#' onClick='gShowHide(\"gTable".$this->table_count."\")'><img border='0' src='$http_inc/imagesAlt/minus.gif'></a>");
				$this->gOut("<a title='Clique aqui para maximizar esta tabela' class='imagem' href='#' onClick='gShowHide(\"gTable".$this->table_count."\")'><img border='0' src='$http_inc/imagesAlt/plus.gif'></a>");
				$this->gOut("<a title='Clique aqui para fechar esta tabela' class='imagem' href='#' onClick='gShowHide(\"gControls".$this->table_count."\")'><img border='0' src='$http_inc/imagesAlt/close.gif'></a>");
			}
			//$this->gOut("<div style='width: 100%; display: block;' id='gTable".$this->table_count."'>");
			
			$pilha=$this->table_stack;
			$pilha[]=$border;
			$this->table_stack=$pilha;
			if (($border=="") || ($border==0))
			{
				switch ($style)
				{
					case gT_BIG:
						$this->gOut(gTag("table.header_nullborder_big"));
						break;
					case gT_MEDIUM:
						$this->gOut(gTag("table.header_nullborder_medium"));
						break;
					case gT_TINY:
						$this->gOut(gTag("table.header_nullborder_tiny"));
						break;
					default:
						$this->gOut(gTag("table.header_nullborder_free_start").$style.gTag("table.header_noborder_free_end"));
				}
			}
			else
			{
				switch ($style)
				{
					case gT_BIG:
						$this->gOut(gTag("table.header_border_big"));
						break;
					case gT_MEDIUM:
						$this->gOut(gTag("table.header_border_medium"));
						break;
					case gT_TINY:
						$this->gOut(gTag("table.header_border_tiny"));
						break;
					default:
						$this->gOut(gTag("table.header_border_free_start").$style.gTag("table.header_border_free_end"));
				}
			}
		} else
		{
			$this->table_content="";
		}
	}

	function gTableEnd()
	{
     /*
Funcionalidade: Monta o rodapï¿½ da tabela.
Parï¿½metros:
      Nenhum
Criado por: Giuliano
*/
		global $cr;
		
		if ($this->_getFilter()=='')
		{
			$pilha=$this->table_stack;
			$tstack=array_pop($pilha);
			$this->table_stack=$pilha;
			
			if ($tstack==false)
				$this->gOut(gTag("table.footer_nullborder"));
			else
				$this->gOut(gTag("table.footer"));
			
			//$this->gOut("</div>");
			if ($this->table_controls)
			{
				$this->gOut("</div>");
			}

		}
		else
		{
			$table_content=$this->table_content;
			$width="";
			$wdt="";
			$maxlen=35; // tamanho mï¿½ximo da coluna
			if (is_array($table_content))
			{
				for ($a=0; $a<count($table_content);$a++)
				{
					$row=$table_content[$a];
					$cs=0;
					for ($b=0;$b<count($row);$b++)
					{
						$col=$row[$b];
						$size=strlen(trim(html2str($col[0])));
						if (($width[$b+$cs]<$size) || ($width[$b+$cs]==''))
							$width[$b+$cs]=$size;
						if (strpos($col[2],'=')>0)
						{
							$nr=intval(str_replace("colspan=",'',str_replace("'",'',$col[2])));
							$cs=$cs+$nr-1;
						} else
						{
						if ($width[$b+$cs]>$maxlen)
								$width[$b+$cs]=$maxlen;
						}
					}
					if ($cs==0)  // tamanho das colunas de linhas que não usam colspan
					{
						for ($b=0;$b<count($row);$b++)
						{
							$col=$row[$b];
							$size=strlen(trim(html2str($col[0])));
							if (($wdt[$b]<$size) || ($wdt[$b]==''))
							{
								$wdt[$b]=$size;
								if ($wdt[$b]>$maxlen)
										$wdt[$b]=$maxlen;
							}
						}
					}
				}
			}

			if (($this->_getFilter()=='TXT') )
			{
				$this->gEcho($cr);
				if (is_array($table_content))
				{
					for ($a=0; $a<count($table_content);$a++)
					{
						$row=$table_content[$a];
						$cs=0;
						$max=count($row);
						for ($b=0;$b<$max;$b++)
						{
							$col=$row[$b];
							$s=str_replace("&#9679;","*",html2str($col[0]));
							$pad=STR_PAD_RIGHT;
							if (strpos($col[1],'left')>0) $pad=STR_PAD_RIGHT;
							if (strpos($col[1],'right')>0) $pad=STR_PAD_LEFT;
							if (strpos($col[1],'center')>0) $pad=STR_PAD_BOTH;
							$size=$width[$b+$cs];
							if (strpos($col[2],'=')>0)
							{
								$nr=intval(str_replace("colspan=",'',str_replace("'",'',$col[2])));
								for ($c=2; $c<=$nr; $c++)
								{
									$size+=$width[($b+$c-1)];
									$size++;
									$cs++;
								}
							}
							$s=str_pad($s, $size, " ", $pad);
							$this->gEcho("$s ");
						}
						$this->gEcho($cr);
						if ($a==0)
						{
							for ($b=0;$b<count($width);$b++)
							{
								$size=$width[($b)];
								$this->gEcho(str_pad('', $size+1, "-"));
							}
							$this->gEcho($cr);
						}
					}
					$this->gEcho($cr);
					$fmt=gVar("global.dateformat");
					$fmt=str_replace("dd","d",$fmt);
					$fmt=str_replace("mm","m",$fmt);
					$fmt=str_replace("yy","y",$fmt);
					$this->gEcho(gLng("print_date.short")." ".date($fmt)." ".date("h:i:s").$cr);
				}
			}
			if (($this->_getFilter()=='PRN') )
			{
				$fmt=gVar("global.dateformat");
				$fmt=str_replace("dd","d",$fmt);
				$fmt=str_replace("mm","m",$fmt);
				$fmt=str_replace("yy","y",$fmt);
				$fmt=str_replace("yy","y",$fmt);
				if ($this->page_first)
				{
					// Monta cabeï¿½alho e rodapï¿½
					$this->gEcho(":linhas=66\n:condensado\n:cabecalho=".$this->report_title."\n:cabecalho=".$this->report_subtitle."\n:rodape=".gLng("print_date.short")." ".date($fmt)." ".date("h:i:s")."\n");
					$this->page_first=false;
				}
				//$this->gEcho($cr);
				if (is_array($table_content))
				{
					for ($a=0; $a<count($table_content);$a++)
					{
						$row=$table_content[$a];
						$cs=0;
						$max=count($row);
						for ($b=0;$b<$max;$b++)
						{
							$col=$row[$b];
							$s=str_replace("&#9679;","*",html2str($col[0]));
							$pad=STR_PAD_RIGHT;
							if (strpos($col[1],'left')>0) $pad=STR_PAD_RIGHT;
							if (strpos($col[1],'right')>0) $pad=STR_PAD_LEFT;
							if (strpos($col[1],'center')>0) $pad=STR_PAD_BOTH;
							$size=$width[$b+$cs];
							if (strpos($col[2],'=')>0)
							{
								$nr=intval(str_replace("colspan=",'',str_replace("'",'',$col[2])));
								for ($c=2; $c<=$nr; $c++)
								{
									$size+=$width[($b+$c-1)];
									$size++;
									$cs++;
								}
							}
							$s=str_pad($s, $size, " ", $pad);
							$this->gEcho("$s ");
						}
						$this->gEcho($cr);
						if ($a==0)
						{
							for ($b=0;$b<count($width);$b++)
							{
								$size=$width[($b)];
								$this->gEcho(str_pad('', $size+1, "-"));
							}
							$this->gEcho($cr);
						}
					}
					//$this->gEcho($cr);
					//$this->gEcho(gLng("print_date.short")." ".date($fmt)." ".date("h:i:s").$cr);
				}
			}
			elseif ($this->_getFilter()=='CSV')
			{
				if (is_array($table_content))
				{
					for ($a=0; $a<count($table_content);$a++)
					{
						$row=$table_content[$a];
						foreach ($row as $col)
						{
							$this->gEcho(str_replace("&#9679;","*",html2str($col[0])));
							$this->gEcho(gVar("csv.delimiter"));
							if (strpos($col[2],'=')>0)
							{
								$nr=intval(str_replace("colspan=",'',str_replace("'",'',$col[2])));
								for ($c=2; $c<=$nr; $c++) $this->gEcho(gVar("csv.delimiter"));
							}
						}
						$this->gEcho($cr);
					}
				}
			}
			elseif (($this->_getFilter()=='HTM') ||($this->_getFilter()=='XLS') || ($this->_getFilter()=='DOC'))
			{
				if (is_array($table_content))
				{
					$this->gEcho("<table border='1'>");
					for ($a=0; $a<count($table_content);$a++)
					{
						$row=$table_content[$a];
						$this->gEcho("<tr>");
						foreach ($row as $col)
						{
							if (strpos($col[2],'=')>0)
							{
								$nr=intval(str_replace("colspan=",'',str_replace("'",'',$col[2])));
								$this->gEcho("<td colspan='$nr'>");
							} else
							{
								$this->gEcho("<td>");
							}
							if (trim(html2str($col[0]))=='')
								$this->gEcho("&nbsp;");
							else
								$this->gEcho(html2str($col[0]));
							$this->gEcho("</td>");
							/*
							if (strpos($col[2],'=')>0)
							{
								$nr=intval(str_replace("colspan=",'',str_replace("'",'',$col[2])));
								for ($c=2; $c<=$nr; $c++) $this->gEcho("<td></td>");
							}
							*/
						}
						$this->gEcho("</tr>");
					}
					$this->gEcho("</table>");
				}
			}
			elseif ($this->_getFilter()=='PDF')
			{
				if (is_array($table_content))
				{
					// Determinando se a pï¿½gina deve ser landscape ou portrait
					$detailfontsize=10;
					if (strtolower(gVar("pdf.orientation"))=="autodetect")
					{
						// Tenta definir a orientaï¿½ï¿½o da pï¿½gina automaticamente
						$tam=0;
						foreach ($width as $col)
							$tam+=$col;
						if (($tam>140) || (count($width)>8))
						{
							$orientation="L";
							$detailfontsize=8;
							if ($tam>160)
									$detailfontsize=6;
						}
						else
						{
							$orientation="P";
							$detailfontsize=8;
						}
					}
					else
						$orientation=strtolower(gVar("pdf.orientation"));
					$orientation=$orientation[0];
					$opdf=new gPdf($orientation);
					$opdf->report=true;
					$opdf->detailfontsize=$detailfontsize;
					$opdf->Open();
					$styles="";
					$rows="";
					for ($a=0; $a<count($table_content);$a++)
					{
						$row=$table_content[$a];
						$cs=0;
						$max=count($row);
						$cols="";
						for ($b=0;$b<$max;$b++)
						{
							$col=$row[$b];
							$s='';
							if (($b==0) && ($a>0)) $styles[]=$col[3];
							if (strpos($col[1],'left')>0) $s.='<-';
							if (strpos($col[1],'right')>0) $s.='->';
							if (strpos($col[1],'center')>0) $s.='<>';
							if (strpos($col[2],'=')>0)
							{
								$nr=intval(str_replace("colspan=",'',str_replace("'",'',$col[2])));
								$s='~'.$nr.$s;
							}
							$s.=str_replace('&#9679;','*',html2str($col[0]));
							$cols[]=$s;
						}
						if ($a==0)
							$header=$cols;
						else
							$rows[]=$cols;
					}

					$title=html2str($this->report_title);
					$subtitle=html2str($this->report_subtitle);
					$filter=html2str($this->report_filter);
					$opdf->SetTitle($title,$subtitle,$filter);

					$opdf->SetHeaderdata($header);
					$opdf->CalcFormat($table_content);
					$opdf->SetHeaderspacedata($wdt);
					$opdf->AddPage();

					for ($aa=0;$aa<count($rows);$aa++)
					{
						if (($styles[$aa]==gT_DETAIL) || ($styles[$aa]==gT_GRIDDETAIL))
						{
							$opdf->SetSpacedata($wdt);
							$opdf->Detail($rows[$aa]);
						}
						else
						{
							$opdf->SetSpacedata($wdt);
							$opdf->SubDetail($rows[$aa]);
						}

					}
					$opdf->Output("doc.pdf",true);
				}
			}

		}
	}

	function gTableRowBegin($param="")
	{
/*
Funcionalidade: Monta uma linha.
Parï¿½metros:
      $param      = algum parï¿½metro adicional (onClick, onMouseOver, ...)
Criado por: Giuliano
*/
		$this->gOut("<tr $param>");
	}

	function gTableRowEnd()
	{
/*
Funcionalidade: Fecha uma linha.
Parï¿½metros:
      $param      = algum parï¿½metro adicional (onClick, onMouseOver, ...)
Criado por: Giuliano
*/
		$this->gOut("</tr>");
		//$this->gOut(gTag("table.row_end"));
	}

	function gTableColBegin($param="")
	{
/*
Funcionalidade: Monta uma coluna.
Parï¿½metros:
      $param      = algum parï¿½metro adicional (onClick, onMouseOver, ...)
Criado por: Giuliano
*/
	//* Monta o rodapï¿½ da tabela
		$this->gOut("<td $param>");
	}

	function gTableColEnd()
	{
   /*
Funcionalidade: Fecha uma coluna.
Parï¿½metros:
      $param      = algum parï¿½metro adicional (onClick, onMouseOver, ...)
Criado por: Giuliano
*/
		$this->gOut("</td>");
	}

	function gTableRow($matrix,$style=gT_DETAIL,$param="")
	{
 /*
Funcionalidade: Monta uma linha da tabela, utilizando os dados da matriz como entrada.
Parï¿½metros:
      $matriz     = este parametro passa o valor da matriz que esta sendo utilizada na aplicaï¿½ï¿½o.
      $style      =
Criado por: Giuliano
*/
	global $gDebug;
	global $cr;
	global $http_img;
	$row="";
	$tstart="";
	$tend="";
	if (($style!=gT_TOOL) || ($this->_getFilter()==''))
	{
		if ($style==gT_HEADER)
		{
			$tstart="<thead background='$http_img/aba_m.gif'>";
			$tend="</thead>";
			$start=gTag("table.col_header_start");
			$start=substr($start,0,strlen($start)-1)." background='$http_img/aba_m.gif'>";
			$end=gTag("table.col_header_end");
		} elseif ($style==gT_DETAIL)
		{
			$start=gTag("table.col_start");
			$end=gTag("table.col_end");
		} elseif ($style==gT_GRIDDETAIL)
		{
			$tstart="<tbody class='detail'>";
			$tend="</tbody>";
			$start=gTag("table.col_start");
			$end=gTag("table.col_end");
			$start=substr($start,0,strlen($start)-1)." class='detail'>";
		} elseif ($style==gT_FOOTER)
		{
			$start=gTag("table.col_start");
			$end=gTag("table.col_end");
			$start=substr($start,0,strlen($start)-1)." class='footer'>";
		} elseif ($style==gT_SUMMARY)
		{
			$start=gTag("table.col_start");
			$end=gTag("table.col_end");
			$start=substr($start,0,strlen($start)-1)." class='summary'>";
		} elseif ($style==gT_RED)
		{
			$start=gTag("table.col_start");
			$end=gTag("table.col_end");
			$start=substr($start,0,strlen($start)-1)." class='red'>";
		}
		elseif ($style==gT_DISABLE)
		{
			$start=gTag("table.col_start");
			$end=gTag("table.col_end");
			$start=substr($start,0,strlen($start)-1)." class='disable'>";
		} 
		elseif ($style==gT_ENABLE)
		{
			$start=gTag("table.col_start");
			$end=gTag("table.col_end");
			$start=substr($start,0,strlen($start)-1)." class='enable'>";
		}
		elseif ($style==gT_TOTAL)
		{
			$start=gTag("table.col_start");
			$end=gTag("table.col_end");
			$start=substr($start,0,strlen($start)-1)." class='total'>";
		} elseif ($style==gT_TOOL)
		{
			$start=gTag("table.col_start");
			$end=gTag("table.col_end");
			$start=substr($start,0,strlen($start)-1)." background='$http_img/aba_m.gif' class='tool'>";
		} elseif ($style==gT_ALERT)
		{
			$start=gTag("table.col_start");
			$end=gTag("table.col_end");
			$start=substr($start,0,strlen($start)-1)." class='alert'>";
		} else
		{
			$start=gTag("table.col_start");
			$end=gTag("table.col_end");
		}
		$this->gOut($tstart);
		$this->gOut(gTag("table.row_start"));
		$cols="";
		for ($a=0; $a<count($matrix); $a++)
		{
			$js="";
			$wrap="";
			$align=" align='center'";
			if (strlen($matrix[$a])<=10)
			{
				$wrap=" nowrap ";
			}
			if (strlen($matrix[$a])==0)
			{
				$matrix[$a]="&nbsp;";
			}
			$cs="";
			$smtrz=$matrix[$a];
			$smtri=0;
			if (substr($matrix[$a],0,1)=="~")
			{
				$colspan=substr($matrix[$a],1,1);
				if ((ord(substr($matrix[$a],2,1))>47) && (ord(substr($matrix[$a],2,1))<58))
				{
					$colspan.=substr($matrix[$a],2,1);
					$matrix[$a] = substr($matrix[$a],3);
				} else {
					$matrix[$a] = substr($matrix[$a],2);
				}
				$cs=" colspan='" . $colspan . "' ";
				$smtri+=$colspan;
			}
			if (substr($matrix[$a],0,2)=="->")
			{
				$colspan=substr($matrix[$a],1,1);
				if ($pdf<>"sim")
					$align=" align='right'";
				$matrix[$a] = substr($matrix[$a],2);
				$smtri+=2;
			}
			if (substr($matrix[$a],0,2)=="<-")
			{
				$colspan=substr($matrix[$a],1,1);
				if ($pdf<>"sim")
					$align=" align='left'";
				$matrix[$a] = substr($matrix[$a],2);
				$smtri+=2;
			}
			if (substr($matrix[$a],0,2)=="<>")
			{
				$colspan=substr($matrix[$a],1,1);
				if ($pdf<>"sim")
					$align=" align='center'";
				$matrix[$a] = substr($matrix[$a],2);
				$smtri+=2;
			}
			if ($this->_getFilter()=='')
			{
				$this->gOut(substr($start,0,strlen($start)-1).$cs.$align.$wrap.$js.trim(" ".$param).">") ;
				$this->gOut($matrix[$a]);
				$this->gOut($end);
				if ($gDebug>0) $this->gOut($cr);
			}
			else
			{
				$cols[]=array($matrix[$a],$align,$cs,$style);
			}
		}
		if ($this->_getFilter()=='')
		{
			$this->gOut(gTag("table.row_end"));
		}
		else
		{
			$this->table_content[]=$cols;
		}
		$this->gOut($tend);
	}
}

function gTable($mtz,$style=gT_DEFAULT,$border=false,$row_style=0)
  {
/*
Funcionalidade: Monta o cabeï¿½alho da tabela de acordo com os parï¿½metros.
Parï¿½metros:
      $style       =tamanho da tabela
      $border      =true ou false(true=com borda,false=sem borda)
      $mtz         =este parametro passa o valor da matriz que esta sendo utilizada na aplicaï¿½ï¿½o.
      $line_style  =formataï¿½ï¿½o das linhas da tabela.???
Criado por: Giuliano
*/
    $this->gTableBegin($style,$border);
    $this->gTableRowBegin();
    $ini=0;
    if (count($mtz)>1)
    {
      $this->gTableRow($mtz[0],0);
      $ini++;
    }
    for($i=$ini;$i<count($mtz);$i++)
    {
      if(($row_style == 1) && (count($mtz)-1)  == $i )

        $this->gTableRow($mtz[$i],0);

      else
      $this->gTableRow($mtz[$i]);
    }
    $this->gTableRowEnd();
    $this->gTableEnd();
	}

  function gRSTable($sql,$style=gT_DEFAULT,$border=false,$row_style=0)
  {
/*
Funcionalidade: Monta o cabeï¿½alho da tabela de acordo com os parï¿½metros.
Parï¿½metros:
      $style       =tamanho da tabela
      $border      =true ou false(true=com borda,false=sem borda)
      $mtz         =este parametro passa o valor da matriz que esta sendo utilizada na aplicaï¿½ï¿½o.
      $line_style  =formataï¿½ï¿½o das linhas da tabela.???
Criado por: Giuliano
*/
	$rst=gQuery($sql,gD_DEFAULT,1);
	if (!$rst->EOF)
	{
		$this->gTableBegin($style,$border);
		while (!$rst->EOF)
		{
			$mtz="";
			for ($a=0; $a++; $a<$rst->FieldCount())
				$mtz[]=$rst->fields[$a];
			$this->gTableRow($mtz,$row_style);
			$rst->MoveNext();
		}
		$this->gTableEnd();
	}
	
	}

/** Gera um tag para exibir uma imagem
* @author Giuliano Nascimento
* @param string $file nome do arquivo de imagem (deve estar localizado no img_2.0)
* @param string $param qualquer parï¿½metro HTML extra
* @param string $link link para ser executado ao clicar na imagem
* @param boolean $exit_on_page Gera código HTML na pï¿½gina ou não
* @param string $alt Texto para Hint
* @version 2.0
* @return string Código HTML
*/
	function gImage($file,$parm="",$link="",$exit_on_page=true,$alt="")
	{
		global $gPathImg;
		global $http_img;
		
		$pre="";
		$pos="";
		$title="";
		$txt="";
		if ($link<>"")
		{
			$txt="<br>".$alt;
			$pre="<a class='imagem' href='$link'>";
			$pos="</a>";
			$title="title='$alt' alt='$alt' ";
		} elseif ($alt<>"")
			$title="title='$alt' alt='$alt' ";
		$s="$pre<img src='$http_img/$file' $title border='0' $parm>$txt".$pos;
		if ($exit_on_page==true)
			$this->gOut($s);
		return($s);
	}

/** Gera um tag para exibir um ï¿½cone
* @author Giuliano Nascimento
* @param string $file nome do arquivo de imagem (deve estar localizado no img_2.0)
* @param string $param qualquer parï¿½metro HTML extra
* @param string $link link para ser executado ao clicar na imagem
* @param boolean $exit_on_page Gera código HTML na pï¿½gina ou não
* @param string $alt Texto para Hint
* @version 2.0
* @return string Código HTML
*/
	function gIcon($file,$style=gI_TINY, $alt="", $link="",$parm="",$exit_on_page=false)
	{
		if ($style==gI_TINY) $file="icons/16x16/".$file;
		if ($style==gI_MEDIUM) $file="icons/22x22/".$file;
		if ($style==gI_BIG) $file="icons/32x32/".$file;
		if ($style==gI_SUPERBIG) $file="icons/64x64/".$file;
		return($this->gImage($file,$parm,$link,$exit_on_page,$alt));
	}


/** Cria uma tabela do tipo "Aba"
* @author Giuliano Nascimento
* @param string $tabs Abas (com seus nomes)
* @param integer $style Tipo de tabela
* @param boolean $controls Exibe ou não controles maximizar e minimizar
* @version 2.0
* @return string Código HTML
*/
	function gTabTableBegin($tabs="", $style=gT_TINY,$controls=false)
	{
		$this->tab_actual=0;
		$this->tab_selected=$_REQUEST["gTab"]<>"" ? $_REQUEST["gTab"] : intval($_POST["gTab"]);
		$this->tab_count=count($tabs);
		$this->tabs=$tabs;
		$this->gOut("<font style='font-size: 4px'><br></font>");
		
		switch ($style)
		{
			case gT_BIG:
				$this->gOut("<table width='100%' class='nullborder' cellpadding='0' cellspacing='0'>");
				break;
			case gT_MEDIUM:
				$this->gOut("<table width='780' class='nullborder' cellpadding='0' cellspacing='0'>");
				break;
			case gT_TINY:
				$this->gOut("<table width='400' class='nullborder' cellpadding='0' cellspacing='0'>");
				break;
		}		
		$this->gOut("<tr><td>");
		$this->gOut("<input type='hidden' name='gTab' id='gTab' value='0'>");
		$mtz="";
		$dpl="";
		$this->gOut("<table width='100%' class='nullborder' border='0' cellspacing='0' cellpadding='0'>");
		$this->gOut("<tr>");
		$num=intval(100/$this->tab_count);
		for ($a=0; $a<count($tabs); $a++)
		{
			if (is_array($tabs[$a]))
			{
				$tab=$tabs[$a][0];
			}
			else
				$tab=$tabs[$a];
			$this->gOut("<td class='transp' width='$num%' id='gTabCap$a'></td>");
			//$this->gTabRow($tab,$a,$_REQUEST["gTab"]==$a);
		}
		$this->gOut("</tr>");
		$this->gOut("</table>");
		
		$this->gOut("</td></tr>");
		$this->gOut("<tr><td class='tabborder'>");
	}
	
	
/** Encerra tabela do tipo "Aba"
* @author Giuliano Nascimento
* @version 2.0
* @return string Código HTML
*/
	function gTabTableEnd()
	{
		global $http_img;
		$this->gOut("</td></tr>");
		$this->gOut("</table>");
		$js="<script language='Javascript'>function ShowTab(num){\n";
		$js.="ChangeTab(num);document.getElementById('gTab').value=num;\nnum='gTab'+num;\n";
		for ($a=0; $a<$this->tab_count; $a++)
		{
			$js.="document.getElementById('gTab$a').style.display='none';";
		}
		$js.="document.getElementById(num).style.display='block';\n";
		$js.="}\n</script>\n";
		$this->gOut($js);
	
		$aba="aba";
		$s="<table width='100%' class='nullborder' border='0' cellpadding='0' cellspacing='0'>";
		$s.="<tr>";
		$s.="<td width='3px' class='tab'><img src='$http_img/".$aba."_lt.gif'></td>";
		$s.="<td class='tab' background='$http_img/".$aba."_t.gif'></td>";
		$s.="<td width='3px' class='tab'><img src='$http_img/".$aba."_rt.gif'></td>";
		$s.="</tr>";
		$s.="<tr>";
		$s.="<td class='tab'><img src='$http_img/".$aba."_l.gif'></td>";
		$s.="<td class='tab' background='$http_img/".$aba."_m.gif'>";
		$e="</td>";
		$e.="<td class='tab'><img src='$http_img/".$aba."_r.gif'></td>";
		$e.="</tr>";
		$e.="<tr>";
		$e.="<td class='tab'><img src='$http_img/".$aba."_lb.gif'></td>";
		$e.="<td class='tab' background='$http_img/".$aba."_b.gif'></td>";
		$e.="<td class='tab'><img src='$http_img/".$aba."_rb.gif'></td>";
		$e.="</tr>";
		$e.="</table>";
		
		$aba="abas";
		$ss="<table width='100%' class='nullborder' border='0' cellpadding='0' cellspacing='0'>";
		$ss.="<tr>";
		$ss.="<td width='3px' class='tab'><img src='$http_img/".$aba."_lt.gif'></td>";
		$ss.="<td class='tab' background='$http_img/".$aba."_t.gif'></td>";
		$ss.="<td width='3px' class='tab'><img src='$http_img/".$aba."_rt.gif'></td>";
		$ss.="</tr>";
		$ss.="<tr>";
		$ss.="<td class='tab'><img src='$http_img/".$aba."_l.gif'></td>";
		$ss.="<td class='tab' background='$http_img/".$aba."_m.gif'>";
		$se="</td>";
		$se.="<td class='tab'><img src='$http_img/".$aba."_r.gif'></td>";
		$se.="</tr>";
		$se.="<tr>";
		$se.="<td class='tab'><img src='$http_img/".$aba."_lb.gif'></td>";
		$se.="<td class='tab' background='$http_img/".$aba."_b.gif'></td>";
		$se.="<td class='tab'><img src='$http_img/".$aba."_rb.gif'></td>";
		$se.="</tr>";
		$se.="</table>";
		$this->gOut("<script language='javascript'>\n");
		$this->gOut("var gT_ini=\"$s\";\n");
		$this->gOut("var gT_fim=\"$e\";\n");
		$this->gOut("var gTS_ini=\"$ss\";\n");
		$this->gOut("var gTS_fim=\"$se\";\n");
		$this->gOut("var gTabs=new Array();\n");
		$this->gOut("var gLnks=new Array();\n");
		$tabs=$this->tabs;
		for ($a=0; $a<count($tabs); $a++)
		{
			if (is_array($tabs[$a]))
			{
				$this->gOut("gTabs[$a]='".$tabs[$a][0]."';\n");
				$this->gOut("gLnks[$a]='".$tabs[$a][1]."';\n");
			} else
			{
				$this->gOut("gTabs[$a]='".$tabs[$a]."';\n");
				$this->gOut("gLnks[$a]='';\n");
			}
		}
		?>
function ChangeTab(num)
{
	for (a=0; a<<?echo $this->tab_count;?>; a++)
	{
		aba='gTabCap' + a;
		el=document.getElementById(aba);
		if (a==num)
			el.innerHTML=gTS_ini + "&nbsp;<b>" + gTabs[a] + "</b>&nbsp;" +gTS_fim;
		else
		{
			el.innerHTML=gT_ini + "<a class='tab' style='display: block;' href='#' onClick='ShowTab("+a+");"+ gLnks[a] +"'>&nbsp;" + gTabs[a] +"&nbsp;</a>" + gT_fim;
		}
	}
}		
		ShowTab(<?echo $this->tab_selected;?>);
		<?
		$this->gOut("</script>\n");
	
	
	
	}
	
/** Cria "Aba"
* @author Giuliano Nascimento
* @param boolean $selected Tipo selecionada ou não
* @version 2.0
* @return string Código HTML
*/
	function gTabBegin($selected=false)
	{
		if ($_REQUEST["gTab"]==$this->tab_actual)
			$selected=true;
		if (($selected) && ($this->tab_selected==-1))
		{
			$display="block"; 
			$this->tab_selected=$this->tab_actual;
		}
		else $display="none";
		$a=$this->tab_actual;
		$this->gOut("<span id='gTab$a' style='display: $display; z-index: -10;'>");
		$this->tab_actual++;
	}
	
/** Final da "Aba"
* @author Giuliano Nascimento
* @version 2.0
* @return string Código HTML
*/
	function gTabEnd()
	{
		$this->gOut("</span>");
	}
	
	function gLink($text,$link,$target="",$hint="")
	{
/*
Funcionalidade: Monta um link para outra pï¿½gina
Parï¿½metros:
      $text        =texto referente a uma tag no gConf.xml.
      $link        =path da pï¿½gina referente ao link.
      $target      =
      $hint        =
Criado por: Giuliano
*/
		$_pre="";$_pos="";$_target="";
		if ($hint<>"")
		{
			$_pre="<acronym title='".gLng($hint)."'>";
			$_pos="</acronym>";
		}
		if ($target<>"")
		{
			$_target=" target='$target'";
		}
		$this->gOut("$_pre<a href='$link'$target>".gLng($text)."</a>$_pos");
	}

	function gSourceCode()
	{
/*
Funcionalidade: Mostra o código fonte da pï¿½gina atual
Parï¿½metros:
      Nenhum
Criado por: Giuliano
*/
		$file=$_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'];
		$this->gOut("<hr>");
		$this->gMsg("sourcecode.long",3);
		$this->gOut("<pre><font face='Courier New'>");
		$fp=fopen($file,"r");
		$this->gOut(htmlspecialchars(fread($fp,100000)));
		$this->gOut("</font></pre>");
	}
}
?>
