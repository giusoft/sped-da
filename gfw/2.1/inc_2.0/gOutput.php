<?
/* Definicao de constantes */
// Tabelas
define("gI_TINY",0);		// Para imagens: Tamanho pequeno
define("gI_MEDIUM",1);		// Para imagens: Tamanho medio
define("gI_BIG",2);		// Para imagens: Tamanho grande
define("gI_SUPERBIG",3);		// Para imagens: Tamanho grande

define(gT_DEFAULT,0);                                         //Tabela de tamanho pequeno
define(gT_TINY,1);                                        //Tabela de tamanho pequeno
define(gT_MEDIUM,2);                                  //Tabela de tamanho medio
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
define(gT_GREEN,14);
define(gT_BLUE,15);
define(gT_YELLOW,16);
define(gT_ORANGE,17);
define(gT_DISABLE,9);
define(gT_ENABLE,10);
define(gT_GRIDDETAIL,11);
define(gT_TAB,12);
define(gT_GROUP,13);

// Mensagens
define(gM_TINY,1);                                       //Mensagem de tamanho pequeno
define(gM_MEDIUM,3);                                 //Mensagem de tamanho medio
define(gM_BIG,5);                                        //Mensagem de tamanho grande
define(gM_NORMAL,0);                                //Mensagem normal


if (strtoupper($_REQUEST["special"])=='PDF')
{
	include_once $gPathDefault."gPDF.php";
}

/** Classe responsavel pela geracao de codigo HTML para objetos de saida de dados
 * @package gOutput
 * @author Giuliano Nascimento
 * @version 2.0
 */
class gOutput
{
	var $buffer,$page_first;
	var $bufferize=false;
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
	var $table_corners;
	var $tableDefaultDisplay;
	var $table_firstrow;
	
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
		$this->table_firstrow=true;
		if (gVar("table.corners")<>"")
			$this->table_corners=gVar("table.corners");
		else
			$this->table_corners="round";
		$this->tableDefaultDisplay='block';
	}

/** Exibe texto na tela HTML (nï¿½o sai nada se $this->filter<>"")
 * @author	giuliano
 * @param mixed $var Texto
 * @return mixed $sai O proprio texto
 */
	function gOut($txt)
	{
		if ($this->_getFilter()=="")
		{
			//$buffer=$buffer.$txt;
			if ($this->bufferize)
				$this->buffer.=$txt;
			else
				echo $txt;
		}
	}

/** Exibe texto na tela HTML
 * @author	giuliano
 * @param mixed $var Texto
 * @return mixed $sai O prï¿½prio texto
 */
	function gEcho($txt)
	{
		if ($this->bufferize)
			$this->buffer.=$txt;
		else
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

/** Gera cï¿½digo inicial HTML (<html>...<body>)
 * @author	giuliano
 * @param mixed $filter Tipo de exportaï¿½ï¿½o (PDF, DOC, XLS, ...)
 * @return mixed $sai Cï¿½digo HTML
 */
	function gBegin($filter='')
	{
		global $gDebug;
		global $http_img;
		$filter=strtoupper($filter);
		$this->_setFilter($filter);
		if ($filter!='')
		{
			session_cache_limiter("nocache");
			
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
				gLog("gOutput	gBegin");
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

	/** Gera cï¿½digo final HTML (</body></html>)
	 * @author	giuliano
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gEnd()
	{
		global $gDebug;
		global $http_img;
		$browser=$_SERVER["HTTP_USER_AGENT"];
		if  (!(strpos($browser,"iPhone")===false)) 
		{
			// mostra botï¿½o pra carregar o menu
		}
		
		//$this->gOut("<div id='gWait' style='position: absolute; left: 5%; top: 5%; width: 90%; height: 90%; display: none; padding: 2pt; vertical-align: middle; horizontal-align: center; z-index:100; '><table align='center' style='position: absolute; top: 50%; width:172px; height:40px; background-image: url(".$http_img."/aguarde.gif)'><tr><td align='center'>".gT("aguarde...")."</td></tr></table></div>");
                $this->gOut("<div id='gWait' style='display: none; margin:0px auto; z-index:100; '><table align='center' style='width:172px; height:40px; background-image: url(".$http_img."/aguarde.gif)'><tr><td align='center'>".gT("aguarde...")."</td></tr></table></div>");
		$this->gOut(gTag("page.footer"));
		echo $buffer;

		if ($gDebug>0)
		{
			gLog("gOutput	gEnd");
		}
		if (gVar("global.align")<>"")
			$this->gOut("</div>");
		if ($this->_getFilter()=='HTM')
		{
			$this->gEcho('</body></html>');
		}
		$this->_setFilter('');
	}

	/** Exibe mensagem na tela HTML
	 * @author	giuliano
	 * @param mixed $text Texto
	 * @param mixed $style Estilo do texto
	 * @param mixed $alert Se <true>, aparece realï¿½ado
	 * @return mixed $sai Codigo HTML
	 */
	function gMsg($text,$style=0,$alert=false)
	{
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

	/** Exibe mensagem do tipo titulo na tela HTML e seta titulo do PDF
	 * @author	giuliano
	 * @param mixed $text Texto
	 * @return mixed $sai Codigo HTML
	 */
	function gMsgTitle($text)
	{
		global $http_base,$http_inc, $http_img;
		
		if (($_SESSION['gfw_version']=="hybrid") && ($_SESSION['gDevice']<>'web'))
		{
			$link=$http_base."login.php";
			$this->gOut("<a href='$link' style='display: block;'><br>Menu Inicial</br>&nbsp;</a>");
		}
		if ($_REQUEST['gIdRel']<>"")
			gVar("global.helpdesk","false");
		
		if (gVar("global.helpdesk")=="true")
		{
			$pre="<table width='100%' border='0' style='background: none; border: none'><tr><td width='40'>&nbsp;</td><td align='center'>";
		}
		$pre.="<a href='#' style='text-decoration: none' onClick='javascript: window.location.reload();' title='Clique aqui para atualizar esta página'>";
		$pos="</a>";
		if (gVar("global.helpdesk")=="true")
		{
			// Descobrindo o link do menu
			$arq=basename($_SERVER["PHP_SELF"]);
			$cam=explode('/',$_SERVER["PHP_SELF"]);
			$arq=$cam[count($cam)-2].'/'.$arq;
			$sql="select * from geral_links where link like '$arq%'";
			$rs=gQuery($sql);
			$menu=$rs->fields['sigla'];
			$ajuda=$rs->fields['link_ajuda'];
			//$pos.="</td><td class='subtitle' width='20'><a style='display: block' title='Clique aqui para registrar uma solicitação de ajuda' href='$http_inc/gGiuSoft.php?l=".$this->page."&t=".$content."'> ? </a></td>";
			$pos.="</td><td width='40' align='right'>";
			//if ($ajuda<>"")
				//$pos.="<a title='Ajuda na Web' href='$ajuda'><img src='".$http_img."/icons/32x32/m_interrogacao.png' width='16' height='16' border='0'></a> ";
			//$pos.="<a title='Suporte GiuSoft - Clique aqui para solicitar ajuda' href='../../inc_2.0/gGiuSoft.php?gAction=new&l=$arq&t=".$text."&m=$menu'><img src='".$http_img."/icons/32x32/saude.png' width='16' height='16' border='0'></a> ";
			$pos.="</td></tr></table>";
		}
		$this->gMsg($pre.$text.$pos,2);
		$this->report_title=$text;
	}

	/** Exibe mensagem do tipo sub-tï¿½tulo na tela HTML e seta sub-tï¿½tulo do PDF
	 * @author	giuliano
	 * @param mixed $text Texto
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gMsgSubTitle($text)
	{
		$this->gMsg($text,3);
		$this->report_subtitle=$text;
	}

	/** Exibe mensagem do tipo filtro na tela HTML e seta filtro do PDF
	 * @author	giuliano
	 * @param mixed $text Texto
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gMsgFilter($text)
	{
		$this->gMsg($text);$this->gBr();
		$this->report_filter=$text;
	}

	/** Exibe mensagem do tipo erro na tela HTML 
	 * @author	giuliano
	 * @param mixed $text Texto
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gMsgError($text)
	{
		$this->gMsg($text,0,true);
	}

	/** Exibe mensagem do tipo alerta na tela HTML
	 * @author	giuliano
	 * @param mixed $text Texto
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gMsgAlert($text)
	{
		$this->gMsg($text,0,true);
	}

	/** Exibe um texto com um <br> no final
	 * @author	giuliano
	 * @param mixed $text Texto
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gTxt($text)
	{
		$this->gOut($text ."<br />");
	}

	/** Exibe <br />
	 * @author	giuliano
	 * @param mixed $quantity Quantidade de <br />
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gBr($quantity=1)
	{
		for ($a=0;$a<$quantity;$a++)
		{
			$this->gOut("<br />");
		}
	}

	/** Exibe &nbsp;
	 * @author	giuliano
	 * @param mixed $quantity Quantidade de <br />
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gSpc($quantity=1)
	{
		for ($a=0;$a<$quantity;$a++)
		{
			$this->gOut("&nbsp;");
		}
	}

	/** Monta o cabecalho da tabela de acordo com os paremetros
	 * @style	integer	tamanho da tabela.
	 * @param $border	boolean	true=com borda,false=sem borda
	 * @param $controls boolean true=exibe controles minimizar,maximizar e fechar
	 * @author Giuliano
	*/
  function gTableBegin($style=gT_DEFAULT,$border=false, $controls=false, $nome="")
	{
		global $http_inc;
		global $http_img;
		if ($this->_getFilter()=='')
		{
			$this->table_controls=$controls;
			$this->table_count++;
			$this->gOut("<div style='width: 100%; display: ".$this->tableDefaultDisplay.";' id='gTable".$this->table_count."'>");
			
			$pilha=$this->table_stack;
			$pilha[]=$border;
			$this->table_stack=$pilha;
			if (($this->table_corners=="") || ($this->table_corners=="round"))
			{
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
				elseif ($border==1)
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
				elseif ($border==3)
				{
					switch ($style)
					{
						case gT_BIG:
							$this->gOut(gTag("table.header_iborder_big"));
							break;
						case gT_MEDIUM:
							$this->gOut(gTag("table.header_iborder_medium"));
							break;
						case gT_TINY:
							$this->gOut(gTag("table.header_iborder_tiny"));
							break;
						default:
							$this->gOut(gTag("table.header_iborder_free_start").$style.gTag("table.header_iborder_free_end"));
					}				
				}
			} elseif ($this->table_corners=="square") // Usando CSS
			{
				if (($border=="") || ($border==0))
				{
					switch ($style)
					{
						case gT_BIG:
							$this->gOut("<div class='big'><div class='som'><div class='som2'><div class='bra'><div class='int'><table width='100%' class='nullborder'>");
							break;
						case gT_MEDIUM:
							$this->gOut("<div class='med'><div class='som'><div class='som2'><div class='bra'><div class='int'><table width='100%' class='nullborder'>");
							break;
						case gT_TINY:
							$this->gOut("<div class='tin'><div class='som'><div class='som2'><div class='bra'><div class='int'><table width='100%' class='nullborder'>");
							break;
						default:
							$this->gOut("<div class='big'><div class='som'><div class='som2'><div class='bra'><div class='int'><table width='100%' class='nullborder'>");
					}
				}
				elseif ($border==1)
				{
					switch ($style)
					{
						case gT_BIG:
							$this->gOut("<div class='big'><div class='som'><div class='som2'><div class='bra'><div class='int'><table width='100%' class='nullborder' id='$nome' name='$nome' >");
							break;
						case gT_MEDIUM:
							$this->gOut("<div class='med'><div class='som'><div class='som2'><div class='bra'><div class='int'><table width='100%' class='nullborder'>");
							break;
						case gT_TINY:
							$this->gOut("<div class='tin'><div class='som'><div class='som2'><div class='bra'><div class='int'><table width='100%' class='nullborder'>");
							break;
						default:
							$this->gOut("<div class='big'><div class='som'><div class='som2'><div class='bra'><div class='int'><table width='100%' class='nullborder'>");
					}
				} 
			} else // Nenhuma
			{
				switch ($style)
				{
					case gT_BIG:
						$this->gOut("<table width='100%' class='nullborder'>");
						break;
					case gT_MEDIUM:
						$this->gOut("<table width='500' class='nullborder'>");
						break;
					case gT_TINY:
						$this->gOut("<table width='300' class='nullborder'>");
						break;
					default:
						$this->gOut("<table width='100%' class='nullborder'>");
				}
			}
		} else
		{
			$this->table_content="";
		}
	}

	/** Gera cï¿½digo HTML de fechamento de tabela ou monta arquivo exportado
	 * @author	giuliano
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gTableEnd()
	{
		global $cr;
		
		if ($this->_getFilter()=='')
		{
			$pilha=$this->table_stack;
			$tstack=array_pop($pilha);
			$this->table_stack=$pilha;
			if (($this->table_corners=="") || ($this->table_corners=="round"))
			{			
				if ($tstack==0)
					$this->gOut(gTag("table.footer_nullborder"));
				elseif ($tstack==1)
					$this->gOut(gTag("table.footer"));
				elseif ($tstack==3)
					$this->gOut(gTag("table.ifooter"));
			} elseif ($this->table_corners=="square") // Usando CSS
			{
				$this->gOut("</table></div></div></div></div></div><div style='height: 4pt'></div>");
			} else
			{
				$this->gOut("</table>");
			}
			$this->gOut("</div>");
		}
		else
		{
			$table_content=$this->table_content;
			$width="";
			$wdt="";
			$maxlen=35; // tamanho meximo da coluna
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
					if ($cs==0)  // tamanho das colunas de linhas que nao usam colspan
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
					// Monta cabecalho e rodape
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
							if ($this->_getFilter()=='XLS')
							{
								if (trim(html2str($col[0]))<>'')
								{
									$tcol=html2str($col[0]);
									if ((substr($tcol,2,1)=="-") && (substr($tcol,5,1)=="-"))
									{
										$this->gEcho(gDBDateTime($tcol));
									} else
										$this->gEcho($col[0]);
								}
							} else
							{
								if (trim(html2str($col[0]))=='')
									$this->gEcho("&nbsp;");
								else
									$this->gEcho(html2str($col[0]));
							}
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
					// Determinando se a pagina deve ser landscape ou portrait
					$detailfontsize=10;
					if (strtolower(gVar("pdf.orientation"))=="autodetect")
					{
						// Tenta definir a orientacao da pagina automaticamente
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
                                        if (gVar("pdf.breakgroup")<>"false")
                                                $breakGroup=true;
                                        else
                                                $breakGroup=false;

					for ($aa=0;$aa<count($rows);$aa++)
					{
						if (($styles[$aa]==gT_DETAIL) || ($styles[$aa]==gT_GRIDDETAIL))
						{
							$opdf->SetSpacedata($wdt);
							$opdf->Detail($rows[$aa]);
						}
                                                elseif ($styles[$aa]==gT_GROUP)
                                                {
                                                        if ((!$prim) && ($breakGroup))
                                                                $opdf->AddPage();
                                                        $prim=false;
                                                        $opdf->SetSpacedata($wdt);
                                                        $opdf->SubDetail($rows[$aa]);
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

	/** Gera cï¿½digo HTML de inï¿½cio de linha (<tr>)
	 * @author	giuliano
	 * @param mixed $param Algum parï¿½metro adicional
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gTableRowBegin($param="")
	{
		$this->gOut("<tr $param>");
	}

	/** Gera cï¿½digo HTML de final de linha (</tr>)
	 * @author	giuliano
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gTableRowEnd()
	{
		$this->gOut("</tr>");
	}

	/** Gera cï¿½digo HTML de inï¿½cio de coluna (<td>)
	 * @author	giuliano
	 * @param mixed $param Algum parï¿½metro adicional
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gTableColBegin($param="")
	{
		$this->gOut("<td $param>");
	}

	/** Gera cï¿½digo HTML de final de coluna (</td>)
	 * @author	giuliano
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gTableColEnd()
	{
		$this->gOut("</td>");
	}

	/** Gera uma linha inteira de uma tabela em HTML
	 * @author	giuliano
	 * @param mixed $matrix Array com colunas a serem criadas
	 * @param mixed $style Tipo de linha gerada (cabeï¿½alho, detalhe, rodapï¿½,etc.)
	 * @param mixed $param Algum parï¿½metro adicional
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gTableRow($matrix,$style=gT_DETAIL,$param="")
	{
		global $gDebug;
		global $cr;
		global $http_img;
		$row="";
		$tstart="";
		$tend="";
		$controls=false;
		if (($style!=gT_TOOL) || ($this->_getFilter()==''))
		{
			$bkg="background='$http_img/aba_m.gif'";
			if ($_SESSION['usrId']<>"")
				$bkg="";
			if ($style==gT_HEADER)
			{
				$tstart="<thead $bkg>";
				$tend="</thead>";
				$start=gTag("table.col_header_start");
				$start=substr($start,0,strlen($start)-1)." $bkg'>";
				$end=gTag("table.col_header_end");
				if ($this->table_firstrow) $controls=true;
				$this->table_firstrow=false;
			} elseif ($style==gT_DETAIL)
			{
				$start=gTag("table.col_start");
				$end=gTag("table.col_end");
			} elseif ($style==gT_GRIDDETAIL)
			{
				$scrp="onclick='this.style.background=\"#ffffaa\";'";
				$tstart="<tbody class='detail'>";
				$tend="</tbody>";
				$start=gTag("table.col_start");
				$end=gTag("table.col_end");
				$start=substr($start,0,strlen($start)-1)." $scrp class='detail'>";
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
			}
			elseif ($style==gT_RED)
			{
				$start=gTag("table.col_start");
				$end=gTag("table.col_end");
				$start=substr($start,0,strlen($start)-1)." class='red'>";
			}
			elseif ($style==gT_GREEN)
			{
				$start=gTag("table.col_start");
				$end=gTag("table.col_end");
				$start=substr($start,0,strlen($start)-1)." class='green'>";
			}
			elseif ($style==gT_BLUE)
			{
				$start=gTag("table.col_start");
				$end=gTag("table.col_end");
				$start=substr($start,0,strlen($start)-1)." class='blue'>";
			}
			elseif ($style==gT_YELLOW)
			{
				$start=gTag("table.col_start");
				$end=gTag("table.col_end");
				$start=substr($start,0,strlen($start)-1)." class='yellow'>";
			}
			elseif ($style==gT_ORANGE)
			{
				$start=gTag("table.col_start");
				$end=gTag("table.col_end");
				$start=substr($start,0,strlen($start)-1)." class='orange'>";
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
			elseif ($style==gT_GROUP)
			{
				$start=gTag("table.col_start");
				$end=gTag("table.col_end");
				$start=substr($start,0,strlen($start)-1)." class='group'>";
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
				$start=substr($start,0,strlen($start)-1)." $bkg class='tool'>";
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
				if (substr($matrix[$a],strlen($matrix[$a])-1,1)=="@")
				{
					$wrap="";
					$matrix[$a]=substr($matrix[$a],0,strlen($matrix[$a])-1);
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
					if (($controls) && ($this->table_controls))
					{
						$this->gOut(" <a title='Clique aqui para fechar esta tabela' class='imagem' href='#' onClick='gShowHide(\"gControls".$this->table_count."\")'><img border='0' src='$http_img/close.gif'></a>");
					}
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

	/** Gera cï¿½digo HTML completo pra exibiï¿½ï¿½o de uma tabela
	 * @author	giuliano
	 * @param mixed $mtz Array com linhas e colunas a serem criadas
	 * @param mixed $style Tamanho da tabela
	 * @param boolean $border Define se a tabela ï¿½ com ou sem borda
	 * @param mixed $row_style Formato das linhas da tabela
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gTable($mtz,$style=gT_DEFAULT,$border=false,$row_style=0)
	{
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

	/** Gera cï¿½digo HTML completo pra exibiï¿½ï¿½o de uma tabela com base em query
	 * @author	giuliano
	 * @param mixed $mtz Array com linhas e colunas a serem criadas
	 * @param mixed $style Tamanho da tabela
	 * @param boolean $border Define se a tabela ï¿½ com ou sem borda
	 * @param mixed $row_style Formato das linhas da tabela
	 * @return mixed $sai Cï¿½digo HTML
	 */
	function gRSTable($sql,$style=gT_DEFAULT,$border=false,$row_style=0)
	{
		$rst=gQuery($sql,gD_DEFAULT,1);
		$prim=true;
		if (!$rst->EOF)
		{
			$this->gTableBegin($style,$border);
			while (!$rst->EOF)
			{
				$mtz="";
				for ($a=0; $a++; $a<$rst->FieldCount())
					$mtz[]=$rst->fields[$a];
				if ($prim)
					$this->gTableRow($mtz,gT_HEADER);
				else
					$this->gTableRow($mtz,$row_style);
				$prim=false;
				$rst->MoveNext();
			}
			$this->gTableEnd();
		}
	
	}

	/** Gera um tag para exibir uma imagem
	 * @author Giuliano Nascimento
	 * @param string $file nome do arquivo de imagem (localizado no img_2.0)
	 * @param string $param qualquer paremetro HTML extra
	 * @param string $link link para ser executado ao clicar na imagem
	 * @param boolean $exit_on_page Gera codigo HTML na pagina ou nao
	 * @param string $alt Texto para Hint
	 * @return string codigo HTML
	 */
	function gImage($file,$parm="",$link="",$exit_on_page=true,$alt="")
	{
		global $gPathImg;
		global $http_img;
		
		$pre="<span name='$file' id='$file'>";
		$pos="</span>";
		$title="";
		$txt="";
		if ($link<>"")
		{
			$txt="<br>".$alt;
			$pre.="<a class='imagem' href='$link'>";
			$pos="</a>".$pos;
			$title="title='$alt' alt='$alt' ";
		} elseif ($alt<>"")
			$title="title='$alt' alt='$alt' ";
		$s="$pre<img src='$http_img/$file' $title border='0' $parm>$txt".$pos;
		if ($exit_on_page==true)
			$this->gOut($s);
		return($s);
	}

	/** Gera um tag para exibir uma imagem contida na pasta do tema (schema)atual
	 * @author Giuliano Nascimento
	 * @param string $file nome do arquivo de imagem
	 * @param string $param qualquer paremetro HTML extra
	 * @param string $link link para ser executado ao clicar na imagem
	 * @param boolean $exit_on_page Gera codigo HTML na pagina ou nao
	 * @param string $alt Texto para Hint
	 * @return string codigo HTML
	 */
	function gImageSchema($file,$parm="",$link="",$exit_on_page=true,$alt="")
	{
		global $gPathImg;
		global $http_img;
		
		$pre="<span name='$file' id='$file'>";
		$pos="</span>";
		$title="";
		$txt="";
		if ($link<>"")
		{
			$txt="<br>".$alt;
			$pre.="<a class='imagem' href='$link'>";
			$pos="</a>".$pos;
			$title="title='$alt' alt='$alt' ";
		} elseif ($alt<>"")
			$title="title='$alt' alt='$alt' ";
		$s="$pre<img src='$http_img/schema/".gVar("global.schema")."/$file' $title border='0' $parm>$txt".$pos;
		if ($exit_on_page==true)
			$this->gOut($s);
		return($s);
	}

	/** Gera um tag para exibir um ï¿½cone
	 * @author Giuliano Nascimento
	 * @param string $file nome do arquivo de imagem (localizado no img_2.0)
	 * @param string $param qualquer paremetro HTML extra
	 * @param string $link link para ser executado ao clicar na imagem
	 * @param boolean $exit_on_page Gera codigo HTML na pagina ou nao
	 * @param string $alt Texto para Hint
	 * @return string codigo HTML
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
	 * @param boolean $controls Exibe ou nao controles maximizar e minimizar
	 * @return string codigo HTML
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
	 * @return string codigo HTML
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
	 * @param boolean $selected Tipo selecionada ou nao
	 * @return string codigo HTML
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
		$this->gOut("<span id='gTab$a' style='display: $display; '>");
		$this->tab_actual++;
	}
	
	/** Final da "Aba"
	 * @author Giuliano Nascimento
	 * @return string codigo HTML
	 */
	function gTabEnd()
	{
		$this->gOut("</span>");
	}
	
	/** Cria link HTML com base nos parï¿½metros
	 * @author Giuliano Nascimento
	 * @param mixed $text Texto
	 * @param mixed $link Link
	 * @param mixed $target Pï¿½gina alvo no frame
	 * @param mixed $hint Dica sobre o link
	 * @return string codigo HTML
	 */
	function gLink($text,$link,$target="",$hint="")
	{
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

	/** Mostra cï¿½digo fonte da pï¿½gina atual - DESATIVADO
	 * @author Giuliano Nascimento
	 * @return string codigo HTML
	 */
	function gSourceCode()
	{
		/*
		$file=$_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'];
		$this->gOut("<hr>");
		$this->gMsg("sourcecode.long",3);
		$this->gOut("<pre><font face='Courier New'>");
		$fp=fopen($file,"r");
		$this->gOut(htmlspecialchars(fread($fp,100000)));
		$this->gOut("</font></pre>");
		*/
	}
}
?>
