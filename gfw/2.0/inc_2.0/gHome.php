<?
/** Objetos e Métodos para Página Inicial (Home)
 * @author	giuliano
 * @version	1.0 23-09-2008 14:50
 */
define(gH_VERTICAL,0);
define(gH_HORIZONTAL,1);

define(gH_WAIT,8000);
define(gH_PAGE_WIDTH,"810px");
define(gH_PAGE_HEIGHT,"600px");

// Estilos de página
define(gH_STYLE_SITE,"apple");
define(gH_STYLE_BLOG_LEFT,"blogleft");
define(gH_STYLE_BLOG_RIGHT,"blogright");
define(gH_STYLE_PORTAL,"portal");
define(gH_STYLE_APPLE,"apple");

// Estilos de DIV
define(gH_TINY,"tiny,190px,140px");
define(gH_MEDIUM,"medium,390px,140px");
define(gH_BIG,"big,590px,140px");
define(gH_TOTAL,"total,100%,140px");

define(gH_TINYFREE,"tinyfree,190px,20");
define(gH_MEDIUMFREE,"mediumfree,390px,20");
define(gH_BIGFREE,"bigfree,590px,20");
define(gH_TOTALFREE,"totalfree,100%,20");

define(gH_TINYFULL,"tinyfull,190px,100%");
define(gH_MEDIUMFULL,"mediumfull,390px,100%");
define(gH_BIGFULL,"bigfull,590px,100%");
define(gH_TOTALFULL,"totalfull,100%,100%");

define(gH_FULL,"full,100%,100%");
define(gH_FREE,"free,0,0");

define(gH_DIV_WPAD,10);
define(gH_DIV_HPAD,10);

// Tipos de texto
define(gH_NONE,"");
define(gH_TITLE,"title");
define(gH_SUBTITLE,"subtitle");
define(gH_MINITITLE,"minititle");
define(gH_NORMAL,"normal");
define(gH_LINK,"link");
define(gH_TINYLINK,"tinylink");
define(gH_MORE,"more");
define(gH_MINI,"mini");
define(gH_LIST,"list");
define(gH_BLOCKQUOTE,"blockquote");
define(gH_NEWSPAPER,"newspaper");

// Alinhamentos
define (gH_LEFT,"left");
define (gH_CENTER,"center");
define (gH_RIGHT,"right");
define (gH_JUSTIFY,"justify");

// Efeitos de texto
define(gH_BOLD,"bold");
define(gH_ITALIC,"italic");
define(gH_SMALLCAPS,"smallcaps");
define(gH_UNDERLINE,"underline");
define(gH_OVERLINE,"overline");
define(gH_BLINK,"blink");
define(gH_LINETHROUGH,"linethrough");
define(gH_CAPITALIZE,"capitalize");
define(gH_UPPERCASE,"uppercase");
define(gH_LOWERCASE,"lowercase");
			
/*  Esquemas de cores 
	0 - Cor das linhas
	1 - Cor dos títulos
	2 - Cor dos sub-títulos
	3 - Cor dos mini-títulos
	4 - Cor normal
	5 - Cor dos links
	6 - Cor dos mini-links
	7 - Cor do mais...
 	8 - Cor da lista
 	9 - Cor da citação (Blockquote)
	10 - Cor do jornal (newspaper)
*/
define(gH_BLUE,"0e4fa3,2e6fc3,808080,2e6fc3,202020,7a9b32,7a9b32,606060,808080,404040,606060,606060");
define(gH_RED,"a00100,a00100,a00100,808080,202020,6060a0,6060a0,606060,808080,404040,606060,606060");
define(gH_GREEN,"4fa34f,2f832f,808080,2f832f,202020,6060a0,6060a0,606060,808080,404040,606060,606060");
define(gH_GREY,"a0a0a0,d0d0d0,808080,2e2e2e,202020,6060a0,6060a0,606060,808080,404040,606060,606060");
define(gH_ORANGE,"ff6000,cf6000,808080,cf6000,202020,6060a0,6060a0,606060,808080,404040,606060,606060");

include $gPathDefault."gInput.php";

/** Classe responsavel pela geracao de codigo HTML para tela inicial
 * @package gHome
 * @author Giuliano Nascimento
 * @version 2.0
 */
class gHome extends gInput
{
	var $content="";
	var $align=gH_LEFT;
	var $cache=false;
	var $layout=gH_NONE;
	var $http_schema="";
	var $schema="";
	var $style=1;
	var $slides_header="";
	var $slides_footer="";
	var $div_style=gH_TINY;
	var $div_left=0;
	var $div_top=gH_DIV_HPAD;
	var $div_width=0;
	var $div_height=0;
	var $div_position="relative";
	var $page_width=gH_PAGE_WIDTH;
	var $page_height=gH_PAGE_HEIGHT;
	var $display_count=0;

	/** Construtor - inicializa tudo
	 * @author	giuliano
	 */	
	function gHome()
	{
		global $http_img;		
		$this->http_schema=$http_img.'/schema';		
		$this->slides_footer="<img src='".$this->http_schema."/".gVar("global.schema")."/mini_logo.png'>";
		$this->schema=gH_BLUE;
	}

	/** Retorna conteúdo do cache e limpa-o
	 * @author	giuliano
	 */	
	function gCache()
	{
		$sai=$this->content;
		$this->content="";
		//$this->cache=false;
		return ($sai);
	}
	
	/** Coloca conteúdo na tela ou no cache
	 * @author	giuliano
	 */	
	function gPut($txt)
	{
		$sai="";
		if ($this->cache)
		{
			$sai=$txt;
			$this->content.=$txt;
			//echo "<h1>entrou no cache: <pre>$txt</pre></h1>";
		} else
		{
			echo ($txt);
		}
		return ($sai);
	}
	
	/** Coloca conteúdo na tela ou no cache
	 * @author	giuliano
	 */	
	function gOut($txt)
	{
		return ($this->gPut($txt));
	}
	
	/** Muda cor 
	 * @author	giuliano
	 */	
	function gColorChange($color,$change=0)
	{
		$oct1=dechex(hexdec(substr($color,0,2))+intval($change));
		$oct2=dechex(hexdec(substr($color,2,2))+intval($change));
		$oct3=dechex(hexdec(substr($color,4,2))+intval($change));
		if (hexdec($oct1)>254) $oct1="ff";
		if (hexdec($oct2)>254) $oct2="ff";
		if (hexdec($oct3)>254) $oct3="ff";
		if (hexdec($oct1)<0) $oct1="00";
		if (hexdec($oct2)<0) $oct2="00";
		if (hexdec($oct3)<0) $oct3="00";
		$new=$oct1.$oct2.$oct3 ;
		return $new;
	}
	
	/** Move ponteiro de localização para a direita
	 * @author	giuliano
	 */	
	function gRight()
	{
		$s=explode(",",$this->div_style);
		$this->div_left+=intval($s[1]);
	}

	/** Move ponteiro de localização para baixo
	 * @author	giuliano
	 */	
	function gDown()
	{
		$s=explode(",",$this->div_style);
		if (strpos($this->div_style,"free")===false)
			$h=$s[2];
		else
		{
			$s=explode(",",gH_TINY);
			$h=$s[2];
		}
		$this->div_top+=intval($h)+gH_DIV_HPAD;
		$this->div_left=+gH_DIV_WPAD;
	}
	
	/** Gera código para início do DIV
	 * @author	giuliano
	 * @param mixed $style Estilo 
	 * @param mixed $param Parâmetros extra
	 */
	function gDivStart($style=gH_NONE, $cssstyle="",$param="")
	{
		if ($style=="in")
		{
			$this->gPut("<div class='in' $param>\n");
		} else
		{
			$this->div_style=$style;
			$s=explode(",",$style);
			//if (substr($style,0,5)=="total")
			//	$this->gPut("\n<div class='".$s[0]."' style='overflow: visible; position: ".$this->div_position."; top: ".$this->div_top."px; left: ".$this->div_left."px; $param'>\n");
			//else
				$this->gPut("\n<div class='".$s[0]."' style='text-align:".$this->align." ;position: ".$this->div_position."; top: ".$this->div_top."px; left: ".$this->div_left."px; $cssstyle' $param>\n");
			$w=$s[1];
			$h=$s[2];
			if ($w=="100%")
			{
				$this->div_left+=0;
				$this->div_top+=intval($h)+gH_DIV_HPAD;
				$this->div_position="relative";
			} else
			{
				$this->div_left+=intval($w)+gH_DIV_WPAD;
				$this->div_position="absolute";
			}
		}
	}

	/** Gera fechamento do DIV
	 * @author	giuliano
	 */
	function gDivEnd()
	{
		$this->gPut("</div>\n");
	}

	/** Gera painel que exibe conteúdo HTML
	 * @author	giuliano
	 * @param mixed $content Itens 
	 * @param mixed $size Tamanho
	 * @param mixed $style Estilo 
	 */
	function gBox($content, $size=gH_TINY, $style=0, $background="")
	{
		$p="";
		if (($size!=gH_FULL) && ($size!=gH_FREE) && ($size!=gH_TOTALFULL) && ($size!=gH_TOTALFREE) && ($style>0))
		{
			$png=str_pad($style, 2, "0", STR_PAD_LEFT);
			if (substr($size,0,1)=="t") $png.="01";
			if (substr($size,0,1)=="m") $png.="02";
			if (substr($size,0,1)=="b") $png.="03";
			if ($background!="")
				$png.=substr($background,1,6);
				//$png.=$background;
			else
				$png.="000000";
			$p="background: url(".$this->http_schema."/pix/box/$png.png) no-repeat top left;";
		}
		if (($background<>"") && ($style==0))
			$p="background: $background ;";
		$this->gDivStart($size, $p);
			$this->gDivStart("in");
				if (is_array($content))
				{
					$display="inline";
					$d=$this->display_count;
					$min=$d;
					$max=count($content);
					for ($a=0; $a<count($content); $a++)
					{
						$this->gPut("<div id='display".intval($d+$a)."' style='display: $display; width: 100%'>");
						$this->gPut($content[$a]);
						$this->gPut("</div>");
						$display="none";
					}
					//$this->gPut("<div class='displaypanelscroll'>");
					$this->gPut("<div id='displaypanel$d' class='displaypanel' onmouseover='gDisplayPanel($d,true)'  onmouseout='gDisplayPanel($d,false)'>");
					for ($a=0; $a<count($content); $a++)
					{
						if ($a==0)
							$this->gPut("<span id='displayspan".intval($d+$a)."' class='displayselected' onclick='javascript:gDisplayChange(".intval($d+$a).",$d,".intval($d+count($content)).");'>&nbsp;&nbsp;&nbsp;".intval($a+1)."&nbsp;&nbsp;&nbsp;</span>");
						else
							$this->gPut("<span id='displayspan".intval($d+$a)."' class='display' onclick='javascript:gDisplayChange(".intval($d+$a).",$d,".intval($d+count($content)).");'>&nbsp;&nbsp;&nbsp;".intval($a+1)."&nbsp;&nbsp;&nbsp;</span>");
						if ($a<count($content)) $this->gPut("|");
					}
					$this->display_count=$a;
					//$this->gPut("</div>");
					$this->gPut("</div>");
					if ($d==0)
					{
					?>

<script>

var displaypos=0;
var displayactual=0;
var interval;
var displaypanel=0;
var displaypanelmax=<?echo $max;?>;
var displaypanelmin=<?echo $min;?>;
var displayinterval;
var displayactive=true;
displayinterval=window.setInterval("gDisplayNext()",<?echo gH_WAIT;?>);

function gDisplayChange(d,min,max)
{
	for (a=min; a<max; a++)
	{
		if (a!=d)
		{
			document.getElementById("display"+a).style.display="none";
			document.getElementById("displayspan"+a).className="display";
		}
	}
	document.getElementById("display"+d).style.display="inline";
	document.getElementById("displayspan"+d).className="displayselected";
}

function gDisplayNext()
{
	if (displayactive)
	{
		displaypanel++;
		if (displaypanel>=displaypanelmax)
			displaypanel=displaypanelmin;
		gDisplayChange(displaypanel,displaypanelmin,displaypanelmax);
	}
}

function gDisplayPanel(d,show)
{
	displayactual=d;
	clearInterval(interval);
	if (show)
	{
		interval=window.setInterval("gDisplayShow()",30);
	} else
	{
		interval=window.setInterval("gDisplayHide()",30);
	}
}

function gDisplayShow(d)
{
	displayactive=false;
	if (displaypos<0.75)
		displaypos=displaypos+0.05;
	else
		clearInterval(interval);
	document.getElementById("displaypanel"+displayactual).style.opacity=displaypos;
	document.getElementById("displaypanel"+displayactual).style.filter="alpha(opacity="+(displaypos*100)+")";
}

function gDisplayHide(d)
{
	displayactive=true;
	if (displaypos>0)
		displaypos=displaypos-0.05;
	else
		clearInterval(interval);
	document.getElementById("displaypanel"+displayactual).style.opacity=displaypos;
	document.getElementById("displaypanel"+displayactual).style.filter="alpha(opacity="+(displaypos*100)+")";
}
<?

?>
</script>					
					<?
					}
				} else
				{
					$this->gPut($content);
				}
			$this->gDivEnd();
		$this->gDivEnd();
	}
	/** Exibe código de início da página
	 * @author	giuliano
	 */	
	function gHomeBegin($schema=gH_BLUE,$style=0,$header_content="")
	{
		global $http_img;
		$this->schema=$schema;
		$this->style=$style;
		$http_schema=$this->http_schema;
		$this->gPut("<html><head><meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-1' />\n<meta name='keywords' content='".gVar("global.keywords")."' />\n<meta name='language' content='pt-br' />\n<meta name='description' content='".gVar("global.keywords")."' />\n<link rel='shortcut icon' href='$http_img/gs.ico'>\n<title>".gVar("global.site")."</title>\n");
		$this->gPut("<link href='$http_img/schema/".gVar("global.schema")."/home.css' rel='stylesheet' type='text/css'>");
		$this->gPut("</head>\n");
		$schema=explode(",",$this->schema);
		
		$this->gPut("<style>");
		$this->gPut("img, div { behavior: url($http_img/iepngfix.htc); }");
		$this->gPut("ul li:before {color: ".$schema[1]."; }");
		$this->gPut("body {color: #".$schema[0].";} ");
		$this->gPut("span.".gH_TITLE."{color: #".$schema[1].";} ");
		$this->gPut("span.".gH_SUBTITLE."{color: #".$schema[2].";} ");
		$this->gPut("span.".gH_MINITITLE."{color: #".$schema[3].";} ");
		$this->gPut("span.".gH_NORMAL."{color: #".$schema[4].";} ");
		$this->gPut("span.".gH_LINK."{color: #".$schema[5].";} ");
		$this->gPut("span.".gH_TINYLINK."{color: #".$schema[6].";} ");
		$this->gPut("span.".gH_MORE."{color: #".$schema[7].";} ");
		$this->gPut("span.".gH_MINI."{color: #".$schema[8].";} ");
		$this->gPut("span.".gH_LIST."{color: #".$schema[9].";} ");
		$this->gPut("span.".gH_BLOCKQUOTE."{color: #".$schema[10].";} ");
		$this->gPut("span.".gH_NEWSPAPER."{color: #".$schema[11].";} ");
		$this->gPut("a {text-decoration: none; color: #".$schema[5].";} ");
		$this->gPut("a:hover {text-decoration: blink; color: #".$this->gColorChange($schema[5],-32).";} ");
		
		$s=explode(",",gH_TINY);
		$this->gPut("div.tiny { width: ".$s[1]."; height: ".$s[2]."; } ");
		$s=explode(",",gH_MEDIUM);
		$this->gPut("div.medium { width: ".$s[1]."; height: ".$s[2]."; } ");
		$s=explode(",",gH_BIG);
		$this->gPut("div.big { width: ".$s[1]."; height: ".$s[2]."; } ");
		$s=explode(",",gH_TOTAL);
		$this->gPut("div.total { width: ".$s[1]."; height: ".$s[2]."; } ");
		
		$s=explode(",",gH_TINYFREE);
		$this->gPut("div.tinyfree { width: ".$s[1]."; } ");
		$s=explode(",",gH_MEDIUMFREE);
		$this->gPut("div.mediumfree { width: ".$s[1]."; } ");
		$s=explode(",",gH_BIGFREE);
		$this->gPut("div.bigfree { width: ".$s[1]."; } ");
		$s=explode(",",gH_TOTALFREE);
		$this->gPut("div.totalfree { width: ".$s[1]."; } ");

		$s=explode(",",gH_TINYFULL);
		$this->gPut("div.tinyfull { width: ".$s[1]."; height: ".$s[2]."; } ");
		$s=explode(",",gH_MEDIUMFULL);
		$this->gPut("div.mediumfull { width: ".$s[1]."; height: ".$s[2]."; } ");
		$s=explode(",",gH_BIGFULL);
		$this->gPut("div.bigfull { width: ".$s[1]."; height: ".$s[2]."; } ");
		$s=explode(",",gH_TOTALFULL);
		$this->gPut("div.totalfull { width: ".$s[1]."; height: ".$s[2]."; } ");
		
		$s=explode(",",gH_FULL);
		$this->gPut("div.full { width: ".$s[1]."; height: ".$s[2]."; } ");
		$s=explode(",",gH_FREE);
		$this->gPut("div.free { width: ".$s[1]."; height: ".$s[2]."; } ");

		$this->gPut("</style>");
		
		$this->gPut("\n<body onsubmit='document.getElementById(\"gWait\").style.display=\"inline\";'>");
		$bkg=str_pad($style, 2, "0", STR_PAD_LEFT);
?>
<table align="center" width="100%" height="100%" class='bg' border="0" bgcolor='#c0c0c0' cellpadding="0" cellspacing="0">
  <tr>
    <td class='bg' style="background: url(<?echo $http_schema;?>/pix/background/<?echo $bkg?>_0_0.jpg) top right repeat-x; height: 30px; border-width: 0px;"></td>
    <td class='bg' style="background: url(<?echo $http_schema;?>/pix/background/<?echo $bkg?>_0_1.jpg) top center repeat-x; width: <?echo $this->page_width;?>;  height: 30px; border-width: 0px;"><?echo $header_content?></td>
    <td class='bg' style="background: url(<?echo $http_schema;?>/pix/background/<?echo $bkg?>_0_2.jpg) top left repeat-x; height: 30px; border-width: 0px;"></td>
</tr>
<tr>
    <td class='bg' style="background: url(<?echo $http_schema;?>/pix/background/<?echo $bkg?>_1_0.jpg) top right repeat-y; width: <?echo $this->page_width;?>;  height: <?echo $this->page_height;?>; border-width: 0px;"></td>
    <td class='bg' valign='top' style="background: url(<?echo $http_schema;?>/pix/background/<?echo $bkg?>_1_1.jpg) top repeat-x; width: <?echo $this->page_width;?>;  height: <?echo $this->page_height;?>; border-width: 0px;">
<?			
		$this->div_top=0;
		$this->gDivStart("","width: ".$this->page_width);
		
	}

	/** Exibe código de final da página
	 * @author	giuliano
	 */	
	function gHomeEnd()
	{
		$http_schema=$this->http_schema;
		$bkg=str_pad($this->style, 2, "0", STR_PAD_LEFT);
		$this->gDivEnd();
?>
	 </td>
    <td class='bg' style="background: url(<?echo $http_schema;?>/pix/background/<?echo $bkg?>_1_2.jpg) top left repeat-y; width: <?echo $this->page_width;?>;  height: <?echo $this->page_height;?>; border-width: 0px;"></td>
</tr>

  <tr>
    <td class='bg' style="background: url(<?echo $http_schema;?>/pix/background/<?echo $bkg?>_2_0.jpg) top right repeat-x; border-width: 0px;"></td>
    <td class='bg' style="background: url(<?echo $http_schema;?>/pix/background/<?echo $bkg?>_2_1.jpg) top center repeat-x; width: <?echo $this->page_width;?>;  border-width: 0px;"></td>
    <td class='bg' style="background: url(<?echo $http_schema;?>/pix/background/<?echo $bkg?>_2_2.jpg) top left repeat-x; border-width: 0px;"></td>

</tr>

</table>

<?	
		
		$this->gPut("<div id='gWait' style='display: none; margin:0px auto; z-index:100; '><table align='center' style='width:172px; height:40px; background-image: url(".$http_img."/aguarde.gif)'><tr><td align='center'>".gT("aguarde...")."</td></tr></table></div>");
		$this->gPut('</body></html>');
	}

	/** Gera painel que exibe um Box pré formatado
	 * @author	giuliano
	 * @param mixed $content Array contendo Título e conteúdo
	 * @param mixed $size Tamanho
	 * @param mixed $style Estilo 
	 */
	function gBoxMsg($content, $size=gH_TINY, $style=0, $background="")
	{
		$cache=$this->cache;
		$cachecontent=$this->content;
		$this->content="";
		$this->cache=true;		
		$title=$content[0];
		$text=$content[1];
		$link=$content[2];
		$this->gPrint($title,gH_SUBTITLE);
		$this->gPrint($text,gH_NORMAL);
		$this->gPrint($link,gH_LINK);
		$txt=$this->gCache();
		$this->gBox($txt,$size,$style,$background);
		if (!$cache)
			echo $this->gCache();
		else
			$this->content=$cachecontent.$this->gCache();
		$this->cache=$cache;
	}
	
	/** Gera menu
	 * @author	giuliano
	 * @param mixed $content Itens do menu no formato:
	 * @param mixed $style Estilo do menu (vertical, horizontal)
	 */
	function gMenuBar($content, $style=gH_VERTICAL)
	{
		$http_schema=$this->http_schema;
		$cor="";
		$cormini="";
		if ($style==1)
		{
			$h=22;
			$corfun="000";
			$corfre="f00";
			$this->gPut("<link rel='stylesheet' href='$http_schema/menu/pro_dropdown_6.css' />\n");
			$this->gPut("<style>");
			
			$this->gPut(".preload1 {background: url($http_schema/menu/six_".$corfun."a.gif);}");
			$this->gPut(".preload2 {background: url($http_schema/menu/six_".$corfre."a.gif);}");
			$this->gPut("#nav {background:#fff url($http_schema/menu/six_".$corfun.".gif) repeat-x;}");
			$this->gPut("#nav li a.top_link {background: url($http_schema/menu/six_".$corfun.".gif);}");
			$this->gPut("#nav li a.top_link span {background: url($http_schema/menu/six_".$corfun.".gif) right top no-repeat;}");
			$this->gPut("#nav li a.top_link span.down {background: url($http_schema/menu/six_".$corfun."a.gif) no-repeat right top;}");
			$this->gPut("#nav li:hover a.top_link,#nav a.top_link:hover{background: url($http_schema/menu/six_".$corfre.".gif) no-repeat;}");
			$this->gPut("#nav li:hover a.top_link span, #nav a.top_link:hover span{background:url($http_schema/menu/six_".$corfre.".gif) no-repeat right top;}");
			$this->gPut("#nav li:hover a.top_link span.down,#nav a.top_link:hover span.down{background:url($http_schema/menu/six_".$corfre."a.gif) no-repeat right top;}");

			$this->gPut("</style>");
			$this->gPut("<span class=\"preload1\"></span>");
			$this->gPut("<span class=\"preload2\"></span>");
			$this->gPut("<ul id=\"nav\">");
			for ($a=0; $a<count($content); $a++)
			{
				$item=$content[$a];
				$txt=$item[0];
				$lnk=$item[1];
				if (!is_array($lnk))
					$this->gPut("	<li class=\"top\"><a href=\"".$lnk."\" class=\"top_link\"><span>".$txt."</span></a></li>");
				else
				{
					$this->gPut("<li class=\"top\"><a href=\"#nogo2\" id=\"$txt\" class=\"top_link\"><span class=\"down\">$txt</span><!--[if gte IE 7]><!--></a><!--<![endif]--><!--[if lte IE 6]><table><tr><td><![endif]--><ul class=\"sub\">");
					for ($b=0; $b<count($lnk); $b++)
					{
						$item=$lnk[$b];
						$subtxt=$item[0];
						$sublnk=$item[1];
						$this->gPut("<li class=\"menu\"><a href=\"$sublnk\">$subtxt</a></li>");
					}
					echo "</ul><!--[if lte IE 6]></td></tr></table></a><![endif]-->";
				}
			}
			$this->gPut("</ul>");
			$this->div_top+=intval($h);

		}
	}
	
	/** Gera login
	 * @author	giuliano
	 * @param mixed $style Estilo do menu (vertical, horizontal)
	 */
	function gLogin($style=gH_VERTICAL)
	{
	}

	/** Gera painel que exibe texto com ou sem fotos
	 * @author	giuliano
	 * @param mixed $content Itens 
	 * @param mixed $style Estilo 
	 * @param mixed $width Largura
	 * @param mixed $height Altura
	 */
	function gPrint($content, $style=gH_NORMAL,$effect=gH_NONE,$align=gH_LEFT)
	{
		$sai="";
		$extra="";
		if ($align<>gH_LEFT)
		{
			$extra.="text-align: ".$align;
		}
		if (($effect<>gH_NONE) || ($extra<>""))
		{
			if (strpos($effect,gH_BOLD)!==false) $extra.="font-weight: bold; ";
			if (strpos($effect,gH_ITALIC)!==false) $extra.="font-style: italic; ";
			if (strpos($effect,gH_SMALLCAPS)!==false) $extra.="font-variant: small-caps; ";
			if (strpos($effect,gH_UNDERLINE)!==false) $extra.="text-decoration: underline; ";
			if (strpos($effect,gH_OVERLINE)!==false) $extra.="text-decoration: overline; ";
			if (strpos($effect,gH_BLINK)!==false) $extra.="text-decoration: blink; ";
			if (strpos($effect,gH_LINETHROUGH)!==false) $extra.="text-decoration: line-through; ";
			if (strpos($effect,gH_CAPITALIZE)!==false) $extra.="text-transform: capitalize; ";
			if (strpos($effect,gH_UPPERCASE)!==false) $extra.="text-transform: uppercase; ";
			if (strpos($effect,gH_LOWERCASE)!==false) $extra.="text-transform: lowercase; ";
			$extra=" style='$extra'";
		}
		$sai="<span class='$style'$extra>".$content."</span>";
		$this->gPut($sai);
	}

	/** Gera painel que exibe notícias 
	 * @author	giuliano
	 * @param mixed $content Itens 
	 * @param mixed $style Estilo 
	 * @param mixed $width Largura
	 * @param mixed $height Altura
	 */
	function gNews($content, $style=gH_VERTICAL, $width=gH_DEFAULT_WIDTH,$height=gH_DEFAULT_HEIGHT)
	{
	}

	/** Mostra logomarca
	 * @author	giuliano
	 */
	function gLogo()
	{
	}

	/** Slide show (Apresentação)
	 * @author	giuliano
	 */
	function gSlideMaster($header="", $footer="", $controls="")
	{
		$this->slides_header=$header;
		$this->slides_footer=$footer;
	}
	
	/** Slide show (Apresentação)
	 * @author	giuliano
	 */
	function gSlideShow($slides)
	{
		if (is_array($slides))
		{
			$http_schema=$this->http_schema;
			$schema=explode(",",$this->schema);
			$this->gPut("\n<link rel='stylesheet' href='$http_schema/slides.css' type='text/css' media='projection' id='slideProj' />\n");
			$this->gPut("<link rel='stylesheet' href='$http_schema/opera.css' type='text/css' media='projection' id='operaFix' />\n");
			$this->gPut("<link rel='stylesheet' href='$http_schema/print.css' type='text/css' media='print' id='slidePrint' />\n");
			$this->gPut("<style>\n");
			$this->gPut("div#header, div#footer {border-color: #".$schema[0]."; color: #".$schema[2].";}");
			$this->gPut("div#controls a {color: #".$schema[0].";}");
			$this->gPut("div#code {color: #".$schema[8].";}");
			$this->gPut("#slide0 h1{color: #".$schema[1].";}");
			$this->gPut("#slide0 h2{color: #".$schema[2].";}");
			$this->gPut("#slide0 h3{color: #".$schema[3].";}");
			$this->gPut("#slide0 li{color: #".$schema[8].";}");
			$this->gPut(".slide h1{color: #".$schema[1].";}");
			$this->gPut(".slide h2{color: #".$schema[2].";}");
			$this->gPut(".slide h3{color: #".$schema[3].";}");
			$this->gPut(".slide li{color: #".$schema[8].";}");
			$this->gPut("</style>\n");
			$this->gPut("<script>\n");
		?>
		
// S5 slides.js -- released under CC by-sa 2.0 license
//
// Please see http://www.meyerweb.com/eric/tools/s5/credits.html for information 
// about all the wonderful and talented contributors to this code!

var snum = 0;
var smax = 1;
var undef;
var slcss = 1;
var isIE = navigator.appName == 'Microsoft Internet Explorer' ? 1 : 0;
var isOp = navigator.userAgent.indexOf('Opera') > -1 ? 1 : 0;
var isGe = navigator.userAgent.indexOf('Gecko') > -1 && navigator.userAgent.indexOf('Safari') < 1 ? 1 : 0;
var slideCSS = document.getElementById('slideProj').href;

function isClass(object, className) {
	return (object.className.search('(^|\\s)' + className + '(\\s|$)') != -1);
}

function GetElementsWithClassName(elementName,className) {
	var allElements = document.getElementsByTagName(elementName);
	var elemColl = new Array();
	for (i = 0; i< allElements.length; i++) {
		if (isClass(allElements[i], className)) {
			elemColl[elemColl.length] = allElements[i];
		}
	}
	return elemColl;
}

function isParentOrSelf(element, id) {
	if (element == null || element.nodeName=='BODY') return false;
	else if (element.id == id) return true;
	else return isParentOrSelf(element.parentNode, id);
}

function nodeValue(node) {
	var result = "";
	if (node.nodeType == 1) {
		var children = node.childNodes;
		for ( i = 0; i < children.length; ++i ) {
			result += nodeValue(children[i]);
		}		
	}
	else if (node.nodeType == 3) {
		result = node.nodeValue;
	}
	return(result);
}

function slideLabel() {
	var slideColl = GetElementsWithClassName('div','slide');
	var list = document.getElementById('jumplist');
	smax = slideColl.length;
	for (n = 0; n < smax; n++) {
		var obj = slideColl[n];

		var did = 'slide' + n.toString();
		obj.setAttribute('id',did);
		if(isOp) continue;

		var otext = '';
 		var menu = obj.firstChild;
		if (!menu) continue; // to cope with empty slides
		while (menu && menu.nodeType == 3) {
			menu = menu.nextSibling;
		}
	 	if (!menu) continue; // to cope with slides with only text nodes

		var menunodes = menu.childNodes;
		for (o = 0; o < menunodes.length; o++) {
			otext += nodeValue(menunodes[o]);
		}
		list.options[list.length] = new Option(n+' : ' +otext,n);
	}
}

function currentSlide() {
	var cs;
	if (document.getElementById) {
		cs = document.getElementById('currentSlide');
	} else {
		cs = document.currentSlide;
	}
	cs.innerHTML = '<span id="csHere">' + snum + '<\/span> ' + 
		'<span id="csSep">\/<\/span> ' + 
		'<span id="csTotal">' + (smax-1) + '<\/span>';
	if (snum == 0) {
		cs.style.visibility = 'hidden';
	} else {
		cs.style.visibility = 'visible';
	}
}

function go(inc) {
	if (document.getElementById("slideProj").disabled) return;
	var cid = 'slide' + snum;
	if (inc != 'j') {
		snum += inc;
		lmax = smax - 1;
		if (snum > lmax) snum = 0;
		if (snum < 0) snum = lmax;
	} else {
		snum = parseInt(document.getElementById('jumplist').value);
	}
	var nid = 'slide' + snum;
	var ne = document.getElementById(nid);
	if (!ne) {
		ne = document.getElementById('slide0');
		snum = 0;
	}
	document.getElementById(cid).style.visibility = 'hidden';
	ne.style.visibility = 'visible';
	document.getElementById('jumplist').selectedIndex = snum;
	currentSlide();
}

function toggle() {
    var slideColl = GetElementsWithClassName('div','slide');
    var obj = document.getElementById('slideProj');
    if (!obj.disabled) {
        obj.disabled = true;
        for (n = 0; n < smax; n++) {
            var slide = slideColl[n];
            slide.style.visibility = 'visible';
        }
    } else {
        obj.disabled = false;
        for (n = 0; n < smax; n++) {
            var slide = slideColl[n];
            slide.style.visibility = 'hidden';
        }
        slideColl[snum].style.visibility = 'visible';
    }
}

function showHide(action) {
	var obj = document.getElementById('jumplist');
	switch (action) {
	case 's': obj.style.visibility = 'visible'; break;
	case 'h': obj.style.visibility = 'hidden'; break;
	case 'k':
		if (obj.style.visibility != 'visible') {
			obj.style.visibility = 'visible';
		} else {
			obj.style.visibility = 'hidden';
		}
	break;
	}
}

// 'keys' code adapted from MozPoint (http://mozpoint.mozdev.org/)
function keys(key) {
	if (!key) {
		key = event;
		key.which = key.keyCode;
	}
 	switch (key.which) {
		case 10: // return
		case 13: // enter
			if (window.event && isParentOrSelf(window.event.srcElement, "controls")) return;
			if (key.target && isParentOrSelf(key.target, "controls")) return;
		case 32: // spacebar
		case 34: // page down
		case 39: // rightkey
		case 40: // downkey
			go(1);
			break;
		case 33: // page up
		case 37: // leftkey
		case 38: // upkey
			go(-1);
			break;
		case 84: // t
			toggle();
			break;
		case 67: // c
			showHide('k');
			break;
	}
}

function clicker(e) {
	var target;
	if (window.event) {
		target = window.event.srcElement;
		e = window.event;
	} else target = e.target;
 	if (target.href != null || isParentOrSelf(target, 'controls')) return true;
	if (!e.which || e.which == 1) go(1);
}

function slideJump() {
	if (window.location.hash == null) return;
	var sregex = /^#slide(\d+)$/;
	var matches = sregex.exec(window.location.hash);
	var dest = null;
	if (matches != null) {
		dest = parseInt(matches[1]);
	} else {
		var target = window.location.hash.slice(1);
		var targetElement = null;
		var aelements = document.getElementsByTagName("a");
		for (i = 0; i < aelements.length; i++) {
			var aelement = aelements[i];
			if ( (aelement.name && aelement.name == target)
			 || (aelement.id && aelement.id == target) ) {
				targetElement = aelement;
				break;
			}
		}
		while(targetElement != null && targetElement.nodeName != "body") {
			if (targetElement.className == "slide") break;
			targetElement = targetElement.parentNode;
		}
		if (targetElement != null && targetElement.className == "slide") {
			dest = parseInt(targetElement.id.slice(1));
		}
	}
	if (dest != null)
		go(dest - snum);
 }
 
function createControls() {
	controlsDiv = document.getElementById("controls");
	if (!controlsDiv) return;
	controlsDiv.innerHTML = '<form action="#" id="controlForm">' +
	'<div>' +
	'<a accesskey="t" id="toggle" href="javascript:toggle();">&#216;<\/a>' +
	'<a accesskey="z" id="prev" href="javascript:go(-1);">&laquo;<\/a>' +
	'<a accesskey="x" id="next" href="javascript:go(1);">&raquo;<\/a>' +
	'<\/div>' +
	'<div onmouseover="showHide(\'s\');" onmouseout="showHide(\'h\');"><select id="jumplist" onchange="go(\'j\');"><\/select><\/div>' +
	'<\/form>';
}

function notOperaFix() {
	var obj = document.getElementById('slideProj');
	obj.setAttribute('media','screen');
	if (isGe) {
		obj.setAttribute('href','null');   // Gecko fix
		obj.setAttribute('href',slideCSS); // Gecko fix
	}
}

function startup() {
	if (!isOp) createControls();
	slideLabel();
	if (!isOp) {		
		notOperaFix();
		slideJump();
		document.onkeyup = keys;
		document.onclick = clicker;
	}
}

window.onload = startup;
		
		<?
			$this->gPut("</script>");

			// Slides...
			$this->gPut("<div class='layout'>\n");
			$this->gPut("<div id='currentSlide'></div>\n");
			$this->gPut("<div id='header'>".$this->slides_header."</div>\n");
			$this->gPut("<div id='footer'>".$this->slides_footer."\n");
			$this->gPut("<div id='controls'></div></div>\n");
			$this->gPut("</div>\n\n");

			// Capa
			$this->gPut("<div class='presentation'>\n");

			// Slides...
			for ($a=0; $a<count($slides); $a++)
			{
				$this->gPut("<div class='slide'>\n");
				$this->gPut($slides[$a]."\n");
				// $this->gPut("<div class="handout">[any material that should appear in print but not on the slide]</div>");
				$this->gPut("</div>\n");
			}
			$this->gPut("</div>\n");
		}
	}
}
?>
