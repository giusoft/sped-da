<?php
/**
 *  gOutput.php
 *
 * @author	Giuliano Nascimento
 * @version	3.0 07-04-2009 14:08
 */

// Tamanhos
define( 'gTINY', 0 );
define( 'gMEDIUM', 1 );
define( 'gBIG', 2 );
define( 'gAUTO', -1 );

// Estilos
define( 'gNORMAL', "normal" );
define( 'gTITLE', "title" );
define( 'gSUBTITLE', "subtitle" );
define( 'gMINITITLE', "minititle" );
define( 'gMAXITITLE', "maxititle" );
define( 'gFILTER', "filter" );
define( 'gFOOTER', "footer" );
define( 'gALERT', "alert" );
define( 'gERROR', "error" );
define( 'gHTML', "html" );

define( 'gRENDER_REMOTE', 0 );
define( 'gRENDER_LOCAL', 1 );
define( 'gRENDER_DOWNLOAD', 2 );
define( 'gRENDER_EMAIL', 3 );

define( 'gIDE_AUTO', 0 );
define( 'gIDE_HTML', 1 );
define( 'gIDE_CSS', 2 );
define( 'gIDE_SQL', 3 );


$gContainers = "";

function echoBuffer( $out )
{
	global $gDevice, $htmlBuffer, $useHtmlBuffer;
	if ( $useHtmlBuffer )
	{
		$htmlBuffer .= $out;
	}
	else
	{
		echo $out;
	}
}


/** Class que cria um componente Container (DIV)
 * @package	gContainer
 * @author	Giuliano Nascimento
 * @version	1.0 14-07-2009 16:13
 */
class gContainer
{
	var $buffer = " ";
	private $tag = 'div';
	function __construct( $json = "" )
	{
		$mtz = cssDecode( $json );
		$tag = tagAdd( $this->tag, "id", $mtz[ 'id' ] );
		$mtz = cssRemove( $mtz, "id" );
		$tag = tagAdd( $tag, "class", $mtz[ 'class' ] );
		$mtz = cssRemove( $mtz, "class" );
		$tag = tagAdd( $tag, "style", cssEncode( $mtz ) );
		$this->buffer .= $tag . NL;
	}

	function add( $tag )
	{
		if ( $this->buffer <> '' )
			$this->buffer .= $tag;
	}

	function get( )
	{
		$sai = $this->buffer;
		$sai .= tagClose( $this->tag ) . NL;
		return ( $sai );
	}

	function render( $to = "" )
	{
		global $gContainers, $htmlBuffer, $gDevice;
		if ( $to <> "" )
		{
			$gContainers[ $to ] = $gContainers[ $to ] . $this->get();
		}
		else
		{
			echoBuffer( $this->get() );
		}
		$this->buffer = "";
	}
}

/** Classe que cria um componente ToolBar (tabela de uma linha)
 * @package	Nome da classe
 * @author	Giuliano Nascimento
 * @version	1.0 14-07-2009 16:13
 */
class gToolBar
{
	var $cols;
	var $buffer = "";

	function __construct( $json = "" )
	{
		$mtz = cssDecode( $json );
		$tag = tagAdd( "table", "id", $mtz[ 'id' ] );
		$mtz = cssRemove( $mtz, "id" );
		$tag = tagAdd( $tag, "class", $mtz[ 'class' ], "g-toolbar" );
		$mtz = cssRemove( $mtz, "class" );
		$tag = tagAdd( $tag, "style", cssEncode( $mtz ) );
		$this->buffer .= $tag . NL;
	}

	function add( $el = "" )
	{
		$this->cols[ ] = $el;
	}

	function get( )
	{
		if ( is_array( $this->cols ) )
		{
			$sai = $this->buffer . "<tr>" . NL;
			foreach ( $this->cols as $col )
			{
				$sai .= "<td class='g-toolbar'>";
				$sai .= $col;
				$sai .= "</td>";
				//$sai.="<div style='top: 0;float:left;'>$col</div>";
			}
			$sai .= "</tr></table>";
		}
		return ( $sai );
	}
	function render( $to = "" )
	{
		global $gContainers;
		if ( $to <> "" )
		{
			$gContainers[ $to ] = $gContainers[ $to ] . $this->get();
		}
		else
		{
			echoBuffer( $this->get() );
		}
		$this->buffer = "";
	}
}

/** Classe que cria um componente Menu (usando ul li)
 * @package	Nome da classe
 * @author	Giuliano Nascimento
 * @version	1.0 14-07-2009 16:13
 */
class gMenu
{
	var $cols;
	var $buffer = "";

	var $css;

	function __construct( $json )
	{
		$mtz                      = cssDecode( $json );
		$default                  = "";
		$default[ 'style' ]       = 'round';
		$default[ 'border' ]      = '3px none';
		$default[ 'background' ]  = 'black';
		$default[ 'orientation' ] = 'landscape';
		$default[ 'color' ]       = '#6060ff';
		$default[ 'font-color' ]  = 'white';
		$default[ 'width' ]       = '0';
		$default[ 'height' ]      = '0';
		$mtz                      = cssMerge( $mtz, $default );
		$this->css                = $mtz;
	}

	function add( $json )
	{
		$mtz           = cssDecode( $json );
		$nmtz          = "";
		$nmtz          = $mtz;
		/*
		if ($setup->get("global.charset")<>"UTF-8")
		{
		foreach ($msetz as $key=>$el)
		$nmtz[$key]=utf8_decode($el);
		}
		*/
		$this->cols[ ] = $nmtz;
	}

	function get( )
	{
		$css         = $this->css;
		$style       = $css[ 'style' ];
		$font        = $css[ 'font' ];
		$font_family = $css[ 'font_family' ];
		$font_color  = $css[ 'font_color' ];
		$font_size   = $css[ 'font_size' ];
		$color       = $css[ 'color' ];
		$width       = $css[ 'width' ];
		$height      = $css[ 'height' ];
		$border      = $css[ 'border' ];
		$background  = $css[ 'background' ];
		$orientation = $css[ 'orientation' ];
		$url         = $css[ 'url' ];
		$url_active  = $css[ 'url-active' ];
		$url_hover   = $css[ 'url-hover' ];

		if ( strtolower( $orientation ) == "portrait" )
			$orient = "";
		else
			$orient = "float: left;";

		if ( $style == "roundblock" )
		{
			if ( $height == 0 )
				$height = "5em";
			if ( $width == 0 )
				$width = "10em";
			$sai = "<style type='text/css'>" . NL;
			$sai .= ".g-link-menu:hover { display: block; color: $font_color}" . NL;
			$sai .= "ul.g-menu { position: relative; clear: both;list-style-type: none;}" . NL;
			$sai .= "ul.g-menu li { $orient position: relative; z-index: 100; color: #eee; background: " . $background . "; text-align: center; margin: 3px; line-height: " . $height . "; width: " . $width . "; height: " . $height . "; -webkit-border-radius: 3px; -moz-border-radius: 3px; border: " . $border . ";}" . NL;
			$sai .= "ul.g-menu li:hover { color: " . $color . ";}" . NL;
			$sai .= "ul.g-menu div.ahover { background: " . $color . "; padding: 3px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border: 1px solid #555; position: absolute; z-index: 99;}" . NL;
			$sai .= "</style>" . NL;
		}
		elseif ( $style == "block" )
		{
			if ( $height == 0 )
				$height = "5em";
			if ( $width == 0 )
				$width = "10em";
			$sai = "<style type='text/css'>" . NL;
			$sai .= ".g-link-menu:hover { $font $font_family $font_color $font_size display: block; color: " . $background . "}" . NL;
			$sai .= "ul.g-menu { position: relative; clear: both;list-style-type: none; }" . NL;
			$sai .= "ul.g-menu li { float: left;position: relative; z-index: 100; color: #eee; background: " . $background . "; text-align: center; margin: 0px; line-height: " . $height . "; width: " . $width . "; height: " . $height . "; border: " . $border . ";}" . NL;
			$sai .= "ul.g-menu li:hover { background: " . $color . "; color: " . $color . ";}" . NL;
			$sai .= "ul.g-menu div.ahover { background: " . $color . "; padding-top: 3px; padding-bottom: 3px; border: 0px; position: absolute; z-index: 99;}" . NL;
			$sai .= "</style>" . NL;
		}
		elseif ( $style == "iphone" )
		{
			if ( $height == 0 )
				$height = "5em";
			if ( $width == 0 )
				$width = "6em";
			$sai = "<style type='text/css'>" . NL;
			$sai .= ".g-link-menu:hover { $font $font_family $font_color $font_size display: block; color: " . $background . "}" . NL;
			$sai .= "ul.g-menu { position: relative; clear: both;list-style-type: none;}" . NL;
			$sai .= "ul.g-menu li { float: left;position: relative; z-index: 100; color: #eee; background: " . $background . "; text-align: center; margin: 0px; line-height: " . $height . "; width: " . $width . "; height: " . $height . "; border: " . $border . ";}" . NL;
			$sai .= "ul.g-menu li:hover { background: " . $color . "; color: " . $color . ";}" . NL;
			$sai .= "ul.g-menu div.ahover { background: " . $color . "; padding-top: 3px; padding-bottom: 3px; border: 0px; position: absolute; z-index: 99;}" . NL;
			$sai .= "</style>" . NL;
		}
		elseif ( $style == "image" )
		{
			if ( $height == 0 )
				$height = "5em";
			if ( $width == 0 )
				$width = "6em";
			$sai = "<style type='text/css'>" . NL;
			$sai .= ".g-link-menu:hover { $font $font_family $font_color $font_size display: block; background-image: url(" . $url_hover . ")}" . NL;
			$sai .= "ul.g-menu { margin: 0; border: 0; padding: 0; position: relative; clear: both;list-style-type: none;}" . NL;
			$sai .= "ul.g-menu li { float: left;z-index: 100; color: $color; background-image: url('$url'); text-align: center; margin: 0px; line-height: " . $height . "; width: " . $width . "; height: " . $height . "; border: " . $border . ";}" . NL;
			//$sai.="ul.g-menu li { float: left;position: relative; z-index: 100; color: $color; background-image: url('$url'); text-align: center; margin: 0px; line-height: ".$height."; width: ".$width."; height: ".$height."; border: ".$border.";}".NL;
			$sai .= "ul.g-menu li:hover { background-image: url('$url_hover');}" . NL;
			$sai .= "ul.g-menu div.ahover { background: $color; border: 0px; position: absolute; z-index: 99;}" . NL;
			$sai .= "</style>";
		}
		else
		{
			if ( $height == 0 )
				$height = "1.8em";
			if ( $width == 0 )
				$width = "10em";
			$sai = "<style type='text/css'>" . NL;
			$sai .= ".g-link-menu:hover { $font $font_family $font_color $font_size display: block; color: " . $color . "}" . NL;
			$sai .= "ul.g-menu { position: relative; clear: both;list-style-type: none;}" . NL;
			$sai .= "ul.g-menu li { " . $orientation . " position: relative; z-index: 100; color: #eee; background: " . $background . "; text-align: center; margin: 3px; line-height: " . $height . "; width: " . $width . "; height: " . $height . "; -webkit-border-radius: 3px; -moz-border-radius: 3px; border: " . $border . ";}" . NL;
			$sai .= "ul.g-menu li:hover { color: " . $color . ";}" . NL;
			$sai .= "ul.g-menu div.ahover { background: " . $color . "; padding: 3px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border: 0px solid #555; position: absolute; z-index: 99;}" . NL;
			$sai .= "</style>";
		}
		//$sai="";
		$sai .= "<ul class='g-menu'>" . NL;
		$idmenu = 0;
		foreach ( $this->cols as $col )
		{
			$sai .= "<li>";
			$idmenu++;
			if ( !is_array( $col ) )
			{
				$sai .= $col;
			}
			else
			{
				$render = $col[ 'renderTo' ];
				$href   = $col[ 'href' ];
				if ( $href == "" )
					$sai .= $col[ "caption" ];
				else
				{
					$run = "";
					if ( $render <> "" )
						$run .= "ajaxLoadPage(\"$href\",\"$render\");";
					else
						$run .= "location.href=\"$href\";";
					//$run.="$(\"#$render\").load(\"$href\",\"\", function() {"."$(\"#$render\").fadeIn(\"slow\");});";
					//if ($url<>"")
					$run .= "$(\".g-link-menu\").css(\"background-image\",\"url($url)\");";
					//if ($url_active<>"")
					$run .= "$(\"#gitemmenu$idmenu\").css(\"background-image\",\"url($url_active)\")";
					$sai .= "<a id='gitemmenu$idmenu' class='g-link-menu' onclick='$run'>" . $col[ "caption" ] . "</a>";
				}
				//$sai.="<a class='g-link-menu' href='".$col['href']."'>".$col["caption"]."</a>";

			}
			$sai .= "</li>" . NL;
		}
		$sai .= "</ul>";
		//$sai.="<script language='javascript'>$('ul.g-menu li').ahover({toggleEffect: 'width'});</script>".NL;

		/*
		$sai.="<script language='javascript'>$('ul.g-menu li').ahover({moveSpeed: 200, hoverEffect: function() {".NL;

		$sai.="$(this).animate({opacity: 0.50}, 750).animate({opacity: 1.0}, 750).dequeue();".NL;
		$sai.="$(this).queue(arguments.callee);".NL;

		//if( $this->cols["id"] != "" and  $this->cols["div_destino"] != ""){

		//}

		$sai.="}});";
		$sai.="</script>".NL;
		*
		*/

		return ( $sai );
	}

	function render( $to = "" )
	{
		global $gContainers;
		if ( $to <> "" )
		{
			$gContainers[ $to ] = $gContainers[ $to ] . $this->get();
		}
		else
		{
			echoBuffer( $this->get() );
		}
		$this->buffer = "";
	}
}

/** Classe que cria um componente Slideshow
 * @package	Nome da classe
 * @author	Giuliano Nascimento
 * @version	1.0 14-07-2009 16:13
 */
class gSlideshow
{
	var $slides;
	var $width = "400px";
	var $height = "300px";
	var $timeout = 4000;
	var $caption_align = "bottom: 0";
	var $text_align = "left";

	function __construct( $json )
	{
		$mtz = cssDecode( $json );
		if ( $mtz[ 'text-valign' ] == "top" )
			$this->caption_align = "top: 0";
		if ( $mtz[ 'width' ] <> "" )
			$this->width = $mtz[ 'width' ];
		if ( $mtz[ 'height' ] <> "" )
			$this->height = $mtz[ 'height' ];
		if ( $mtz[ 'timeout' ] <> "" )
			$this->timeout = $mtz[ 'timeout' ];
		if ( $mtz[ 'text-align' ] <> "" )
			$this->text_align = $mtz[ "text-align" ];
	}

	function add( $json )
	{
		$mtz             = cssDecode( $json );
		$this->slides[ ] = array(
			 $mtz[ 'url' ],
			$mtz[ 'caption' ]
		);
	}

	function get( $style = "s3slider" )
	{
		if ( $style == "s3slider" )
		{
			$sai .= "
<style type='text/css'>
#s3slider {
   width: " . $this->width . ";
   height: " . $this->height . ";
   position: relative;
   margin: 0;
   padding: 0;
   border: 0 none;
   overflow: hidden;
}

#s3sliderContent {
   width: " . $this->width . ";
   position: absolute;
   left: 0px;
   top: 0;
   margin-left: 0;
}

.s3sliderImage {
   float: left;
   position: relative;
   display: none;
}

.s3sliderImage span {
   position: absolute;
   left: 0;
   font: 11px/15px Arial, Helvetica, sans-serif;
   padding: 10px 13px;
   width: " . $this->width . ";
   background-color: #000;
   filter: alpha(opacity=70);
   -moz-opacity: 0.7;
   -khtml-opacity: 0.7;
   opacity: 0.7;
   color: #fff;
   display: none;
	text-align: " . $this->text_align . ";
   " . $this->caption_align . ";
}

.s3sliderImage span b {
	font-size: 16px;
}

.clear {
   clear: both;
}
</style>
";
			$sai .= "<div id=\"s3slider\">" . NL;
			$sai .= "<ul id=\"s3sliderContent\">";
			foreach ( $this->slides as $slide )
			{
				$img     = $slide[ 0 ];
				$caption = $slide[ 1 ];
				$sai .= "<li class=\"s3sliderImage\"><img src='$img'><span>$caption</span></li>" . NL;
			}
			$sai .= "<div class=\"clear s3sliderImage\"></div></ul></div>" . NL;
			$sai .= "<script language='javascript'>$('#s3slider').s3Slider({timeOut: 3000});</script>";
		}
		return ( $sai );
	}
	function render( $to = "" )
	{
		global $gContainers;
		if ( $to <> "" )
		{
			$gContainers[ $to ] = $gContainers[ $to ] . $this->get();
		}
		else
		{
			echoBuffer( $this->get() );
		}
		$this->buffer = "";
	}
}

/** Class que cria um componente Calendário
 * @package	gCalendar
 * @author	Giuliano Nascimento
 * @version	1.0 08-02-2012 15:49
 */
class gCalendar
{
	var $buffer = "";
	public $events = '', $dayEvents = '';
	public $json = '', $today = '';
	public $url = '';
	public $mtz, $meses;

	function __construct( $json = "" )
	{
		$this->json = $json;
		$this->mtz  = cssDecode( $this->json );
		$this->url  = $_SERVER[ "PHP_SELF" ] . "?g=" . $_REQUEST[ 'g' ] . "&calShow=" . $_REQUEST[ 'showCal' ];

		$hoje = date( "Y-m-d H:i:s" );
		if ( !empty( $this->mtz[ 'today' ] ) )
			$hoje = $this->mtz[ 'today' ];
		if ( $_REQUEST[ 'date' ] <> '' )
			$hoje = date( "Y-m-d H:i:s", strtotime( $_REQUEST[ 'date' ] ) );
		$this->today    = $hoje;
		$this->meses[ ] = '';
		$this->meses[ ] = gT( 'janeiro' );
		$this->meses[ ] = gT( 'fevereiro' );
		$this->meses[ ] = gT( 'março' );
		$this->meses[ ] = gT( 'abril' );
		$this->meses[ ] = gT( 'maio' );
		$this->meses[ ] = gT( 'junho' );
		$this->meses[ ] = gT( 'julho' );
		$this->meses[ ] = gT( 'agosto' );
		$this->meses[ ] = gT( 'setembro' );
		$this->meses[ ] = gT( 'outubro' );
		$this->meses[ ] = gT( 'novembro' );
		$this->meses[ ] = gT( 'dezembro' );

	}

	function add( $evento )
	{

		$readOnly = false;
		if ( !empty( $mtz[ 'readOnly' ] ) )
			$readOnly = ( strtolower( $mtz[ 'readOnly' ] ) == "true" );

		$json                = cssDecode( $evento );
		$json[ 'dateStart' ] = substr( $json[ 'dateStart' ], 0, 16 );
		$json[ 'dateEnd' ]   = substr( $json[ 'dateEnd' ], 0, 16 );

		$img = '';
		if ( $json[ 'image' ] <> '' )
			$img = $json[ 'image' ];
		$url = "";
		if ( $json[ 'url' ] <> '' )
			$url = "&" . $json[ 'url' ];
		$url                                  = "<a href='" . $this->url . "edit&date=" . $json[ 'dateStart' ] . "&gId=" . $json[ 'id' ] . $json[ 'gId' ] . "$url' class='g-cal-day-url'>";
		$this->events[ $json[ 'dateStart' ] ] = array(
			 $json[ 'title' ],
			$json[ 'details' ],
			$img,
			$json[ 'dateEnd' ],
			$url
		);
	}

	function get( )
	{

		// Day

		$mtz = $this->mtz;

		$hIni = 8;
		$hFim = 18;
		$hSal = 60;

		$readOnly = false;
		if ( !empty( $mtz[ 'readOnly' ] ) )
			$readOnly = ( strtolower( $mtz[ 'readOnly' ] ) == "true" );
		if ( !empty( $mtz[ 'startTime' ] ) )
			$hIni = intval( $mtz[ 'startTime' ] );
		if ( !empty( $mtz[ 'endTime' ] ) )
			$hFim = intval( $mtz[ 'endTime' ] );
		if ( !empty( $mtz[ 'stepTime' ] ) )
			$hSal = intval( $mtz[ 'stepTime' ] );
		if ( ( $hIni < 0 ) || ( $hIni > 23 ) )
			$hIni = 8;
		if ( ( $hFim < $hIni ) || ( $hFim > 23 ) )
			$hIni = 23;
		if ( ( $hSal < 1 ) || ( $hSal > 60 ) )
			$hIni = 60;
		$purl = "";
		if ( $mtz[ 'url' ] <> '' )
			$purl = "&" . $mtz[ 'url' ];

		if ( !empty( $mtz[ 'startTime' ] ) )
		{
			for ( $h = $hIni; $h <= $hFim; $h++ )
			{
				$hora = str_pad( $h, 2, '0', STR_PAD_LEFT ) . ":";
				for ( $s = 0; $s < 60; $s += $hSal )
				{
					$horaMinuto = $hora . str_pad( $s, 2, '0', STR_PAD_LEFT );
					$url        = '';
					if ( !$readOnly )
					{
						if ( ( substr( $this->today, 0, 10 ) . " " . $horaMinuto ) >= date( "Y-m-d H:i" ) )
							$url = "<a href='" . $this->url . "new&date=" . substr( $this->today, 0, 10 ) . " " . $horaMinuto . "$purl' class='g-cal-day-url'>";
						else
							$url = "";
						//gLog("==================== ".($this->today." ".$horaMinuto));
					}
					$this->dayEvents[ $horaMinuto ] = array(
						 '',
						'',
						'',
						'',
						$url
					);
				}
			}
		}
		foreach ( $this->events as $diaHora => $evento )
		{
			if ( substr( $this->today, 0, 10 ) == substr( $diaHora, 0, 10 ) )
			{
				$hora                     = substr( $diaHora, 11, 5 );
				/*
				foreach ($this->dayEvents as $key=>$value)
				{
				if (($key>$hora) && ($key<substr($evento[3],11,5)))
				unset($this->dayEvents[$key]);
				}
				*
				*/
				$this->dayEvents[ $hora ] = $evento;
			}
		}
	}

	function calendar( $m, $checaHoje = true )
	{
		$sem    = '';
		$sem[ ] = gT( "DOM" );
		$sem[ ] = gT( "SEG" );
		$sem[ ] = gT( "TER" );
		$sem[ ] = gT( "QUA" );
		$sem[ ] = gT( "QUI" );
		$sem[ ] = gT( "SEX" );
		$sem[ ] = gT( "SÁB" );

		$sai .= "<div>";
		$sai .= "<table class='g-caljs'>";
		$sai .= "<tr>";
		if ( $checaHoje )
			$sai .= "<td></td>";
		for ( $ds = 0; $ds < 7; $ds++ )
		{
			$sai .= "<td class='g-caljs-ds'>";
			$sai .= $sem[ $ds ];
			$sai .= "</td>";
		}
		$sai .= "</tr>";
		$a       = 0;
		$dataIni = substr( $this->today, 0, 8 ) . "01";
		$dtt     = explode( "-", substr( $dataIni, 0, 10 ) );
		while ( date( "w", strtotime( $dataIni ) ) <> 0 )
		{
			$dt      = explode( "-", substr( $dataIni, 0, 10 ) );
			$dataIni = date( "Y-m-d", mktime( 0, 0, 0, $dt[ 1 ], $dt[ 2 ] - 1, $dt[ 0 ] ) );
		}
		//		echo "<h1>$dataIni</h1><br>";
		$lnk   = $this->url . "day&date=";
		$datas = '';
		foreach ( $this->events as $ev => $value )
			$datas[ substr( $ev, 0, 10 ) ] = "1";
		$outroMes = -2;
		$meses    = $this->meses;
		for ( $l = 0; $l < 6; $l++ )
		{
			$sai .= "<tr>";
			$a++;
			if ( $checaHoje )
			{
				$dataTmp = date( "Y-m-d", mktime( 0, 0, 0, $dtt[ 1 ] + $outroMes, $dtt[ 2 ], $dtt[ 0 ] ) );
				$sai .= "<td class='g-caljs-ds'>";
				$sai .= "<a href='$lnk$dataTmp' class='g-caljs-mes'>" . strtoupper( substr( $meses[ intval( substr( $dataTmp, 5, 2 ) ) ], 0, 3 ) ) . "</a>";
				$sai .= "</td>";
			}
			$outroMes++;
			for ( $ds = 0; $ds < 7; $ds++ )
			{
				$dt  = explode( "-", substr( $dataIni, 0, 10 ) );
				$css = "g-caljs-day";
				if ( date( "w", strtotime( $dataIni ) ) == 0 )
					$css = "g-caljs-we";
				if ( $dt[ 1 ] <> substr( $this->today, 5, 2 ) )
					$css = "g-caljs-out";
				if ( ( $dataIni == substr( $this->today, 0, 10 ) ) && $checaHoje )
					$css = "g-caljs-sel";
				if ( $dataIni == date( "Y-m-d" ) )
					$css = "g-caljs-hj";
				if ( $datas[ $dataIni ] == "1" )
					$css .= " g-caljs-ev";
				$sai .= "<td id='sp$m$a'>";
				$sai .= "<a href='$lnk$dataIni' class='$css'>" . intval( $dt[ 2 ] ) . "</a>";
				$sai .= "</td>";
				$dataIni = date( "Y-m-d", mktime( 0, 0, 0, $dt[ 1 ], $dt[ 2 ] + 1, $dt[ 0 ] ) );
				$a++;
			}
			$sai .= "</tr>";
		}
		$sai .= "<tr>";


		$sai .= "</tr>";
		$sai .= "</table>";

		$sai .= "</div>\n";
		return ( $sai );
	}

	function day( )
	{
		$this->get();
		$sai .= "\n<table class='g-cal-day'>";
		//$sai.="<tr class='g-cal-day-event'><td class='g-cal-day-time'></td><td class='g-msg-subtitle' >".today($this->today)."</td></tr>";
		foreach ( $this->dayEvents as $hora => $evento )
		{
			$horaFim = substr( $evento[ 3 ], 11, 5 );
			if ( ( $hora == $horaFim ) || ( $horaFim == "00:00" ) || ( $horaFim == "" ) )
				$horaFim = '';
			else
				$horaFim = gT( " às " ) . $horaFim;
			$sai .= "<tr class='g-cal-day-event'>";
			//$sai.="<td class='g-cal-day-time'>".$hora." ".$horaFim."</td>";
			$sai .= "<td class='g-cal-day-time'>" . $hora . "</td>";
			$sai .= "<td class='g-cal-day-detail'>";
			if ( $evento[ 4 ] <> '' )
				$sai .= $evento[ 4 ];
			$sai .= "<span class='g-cal-day-title'>";
			if ( $evento[ 2 ] <> '' )
				$sai .= "<img src='" . g_Output::gImagePath( $evento[ 2 ] ) . "' class='g-image' style='padding-right:3px'>" . $evento[ 0 ] . "&nbsp;";
			else
				$sai .= $evento[ 0 ] . "&nbsp;";
			$sai .= "</span>" . $evento[ 1 ] . "&nbsp;";
			if ( $evento[ 4 ] <> '' )
				$sai .= "</a>";
			$sai .= "</td>";
			$sai .= "</tr>";
		}
		$sai .= "<tr class='g-cal-day-event'><td class='g-cal-day-time'></td><td class='g-cal-day-detail' style='padding: 0px'></td></tr>";
		$sai .= "</table>";
		return ( $sai );
	}

	function showDay( )
	{
		global $gDevice;
		if ( $_REQUEST[ "calShow" ] == "year" )
			$sai = $this->showYear();
		elseif ( $_REQUEST[ "calShow" ] == "month" )
			$sai = $this->showMonth();
		else
		{
			$sai = "";
			$lnk = $this->url;
			$dt  = explode( gT( " de " ), today( $this->today ) );
			$ds  = explode( ",", $dt[ 0 ] );
			$sai .= "<div style='padding: 3px; background: white'><table width='100%'>";
			$sai .= "<tr valign='top'>";
			if ( $gDevice == "mobile" )
			{
				$sai .= "<td width='30%'><div class='g-cal-day-bignum'>";
				$sai .= intval( substr( $this->today, 8, 2 ) );
				$sai .= "</div><br>";
				$sai .= "<div class='g-cal-day-bigdate'>";
				$sai .= $ds[ 0 ] . "</a><br>";
				$sai .= "<a class='g-caljs-a' style='color: #000000' href='{$lnk}month&date={$this->today}'>" . ucfirst( $dt[ 1 ] ) . "</a><br>";
				$sai .= "<a class='g-caljs-a' style='color: #000000' href='{$lnk}year&date={$this->today}'>" . ucfirst( $dt[ 2 ] ) . "</a><br>";
				$sai .= "</div></td>";
			}
			else
			{
				$sai .= "<td width='30%' class='g-cal-day-bignum'>";
				$sai .= intval( substr( $this->today, 8, 2 ) );
				$sai .= "</td>";
				$sai .= "<td width='40%' align='left' class='g-cal-day-bigdate'>";
				$sai .= $ds[ 0 ] . "</a><br>";
				$sai .= "<a class='g-caljs-a' style='color: #000000' href='{$lnk}month&date={$this->today}'>" . ucfirst( $dt[ 1 ] ) . "</a><br>";
				$sai .= "<a class='g-caljs-a' style='color: #000000' href='{$lnk}year&date={$this->today}'>" . ucfirst( $dt[ 2 ] ) . "</a><br>";
				$sai .= "</td>";
			}
			$sai .= "<td width='30%' align='right'>";
			$sai .= $this->calendar( intval( substr( $this->today, 5, 2 ) ) );
			$sai .= "</td>";
			$sai .= "</table>";
			if ( $gDevice == "web" )
				$sai .= "<br>";
			$sai .= $this->day();
			$sai .= "</div>";
		}
		return ( $sai );
	}

	function showMonth( )
	{
		if ( $_REQUEST[ "calShow" ] == "year" )
			$sai = $this->showYear();
		elseif ( $_REQUEST[ "calShow" ] == "day" )
			$sai = $this->showDay();
		else
		{
			$sai .= "<div style='padding: 3px; background: white'><table width='100%'>";
			$this->get();
			$lnk = $this->url;
			$dt  = explode( gT( " de " ), today( $this->today ) );
			//$sai.="<h1>".$this->today."</h1>";
			$sai .= "<table class='g-cal-day'>";
			$sai .= "<tr><td colspan='3' class='g-cal-day-bigdate'>" . ucfirst( $dt[ 1 ] ) . gT( " de " );
			$sai .= "<a class='g-caljs-a' style='color: #000000' href='{$lnk}year&date={$this->today}'>" . ucfirst( $dt[ 2 ] ) . "</a><br>&nbsp;";

			$sai .= "</td></tr>";

			$day = '';
			$fez = false;
			foreach ( $this->events as $hora => $evento )
			{
				if ( substr( $hora, 0, 7 ) == substr( $this->today, 0, 7 ) )
				{
					$fez = true;
					if ( $day <> substr( $hora, 0, 10 ) )
					{
						$dt = explode( gT( " de " ), today( $hora ) );

						$sai .= "<tr class='g-cal-tsk-event'><td colspan='3' class='g-cal-tsk-day'>";
						$sai .= "<a class='g-caljs-a' style='color: #606060' href='{$lnk}day&date={$hora}'>" . ucfirst( $dt[ 0 ] ) . "</a>";
						$sai .= "</td></tr>";
					}
					$day = substr( $hora, 0, 10 );

					$sai .= "<tr class='g-cal-tsk-event'>";
					$sai .= "<td class='g-cal-tsk-detail'>";
					if ( $evento[ 4 ] <> '' )
						$sai .= $evento[ 4 ];
					$sai .= "<span class='g-cal-tsk-title'>";
					if ( $evento[ 2 ] <> '' )
						$sai .= "<img src='" . g_Output::gImagePath( $evento[ 2 ] ) . "' class='g-image' style='padding-right:3px'>" . $evento[ 0 ] . "&nbsp;";
					else
						$sai .= $evento[ 0 ] . "&nbsp;";
					$sai .= "</span>" . $evento[ 1 ] . "&nbsp;";
					if ( $evento[ 4 ] <> '' )
						$sai .= "</a>";
					$sai .= "</td>";
					$horaFim = substr( $evento[ 3 ], 11, 5 );
					if ( ( substr( $hora, 11, 5 ) == $horaFim ) || ( $horaFim == "00:00" ) )
						$horaFim = '';
					else
						$horaFim = gT( " às " ) . $horaFim;
					$sai .= "<td class='g-cal-tsk-time'>" . substr( $hora, 11, 5 ) . $horaFim . "</td>";
					$sai .= "</tr>";
				}
			}
			if ( !$fez )
			{
				$sai .= "<tr><td><span class='g-msg-error'>" . gT( "Nenhum evento encontrado" ) . "</span></td></tr>";
			}
			else
			{
				$sai .= "<tr class='g-cal-tsk-event'><td class='g-cal-tsk-detail' style='padding: 0px'></td><td class='g-cal-tsk-time' style='padding: 0px'></td></tr>";
			}
			$sai .= "</table>";
			$sai .= "</div>";
		}
		return ( $sai );
	}

	function showYear( )
	{
		if ( $_REQUEST[ "calShow" ] == "day" )
			$sai = $this->showDay();
		elseif ( $_REQUEST[ "calShow" ] == "month" )
			$sai = $this->showMonth();
		else
		{
			$lnk        = $this->url;
			$anoPassado = ( intval( substr( $this->today, 0, 4 ) ) - 1 ) . substr( $this->today, 4 );
			$anoProximo = ( intval( substr( $this->today, 0, 4 ) ) + 1 ) . substr( $this->today, 4 );
			$dt         = explode( gT( " de " ), today( $this->today ) );
			$ds         = explode( ",", $dt[ 0 ] );
			$sai .= "<div style='padding: 3px; background: white'><table width='100%'>";
			$sai .= "<table style='padding: 4px'>";
			$sai .= "<tr>";
			$sai .= "<td align='center' class='g-cal-day-bigdate'><a href='{$lnk}year&date=$anoPassado' class='g-caljs-a' style='color: #d0d0d0'>" . ( intval( substr( $this->today, 0, 4 ) ) - 1 ) . "</a></td>";
			$sai .= "<td align='center' class='g-cal-day-bigdate'>" . substr( $this->today, 0, 4 ) . "</td>";
			$sai .= "<td align='center' class='g-cal-day-bigdate'><a href='{$lnk}year&date=$anoProximo' class='g-caljs-a' style='color: #d0d0d0'>" . ( intval( substr( $this->today, 0, 4 ) ) + 1 ) . "</a></td>";
			$sai .= "</tr>";
			$m     = 0;
			$meses = $this->meses;
			$js    = "";
			$today = $this->today;
			$y     = substr( $today, 0, 4 );
			for ( $l = 0; $l < 4; $l++ )
			{
				$sai .= "<tr valign='top'>";
				for ( $c = 0; $c < 3; $c++ )
				{
					$m++;
					$this->today = $y . "-" . str_pad( $m, 2, "0", STR_PAD_LEFT ) . "-01";
					if ( substr( $today, 5, 2 ) == substr( $this->today, 5, 2 ) )
						$this->today = $today;
					$sai .= "<td width='33%' align='center'>";
					$sai .= "<a class='g-cal-month g-caljs-a' style='color: #000000' href='{$lnk}month&date={$this->today}'>" . ucfirst( $meses[ $m ] ) . "</a>";
					$sai .= $this->calendar( $m, false );
					$sai .= "<br></td>";
				}
				$sai .= "</tr>";
			}
			$sai .= "</table>";
			$sai .= "<br></div>";
			$this->today = $today;
		}
		return ( $sai );
	}
}


class bootstrap
{
	public $html;

	function add( $html, $class = "", $style = "" )
	{
		if ( $class == "" )
		{
			$this->html .= $html;
		}
		else
		{
			if ( $style == "" )
				$this->html .= "<div class='$class'>$html</div>";
			else
				$this->html .= "<div class='$class' style='$style'>$html</div>";
		}
	}

	function addSpan( $html, $class = "span4", $style = "" )
	{
		$this->add( $html, $class, $style );
	}

	function hug( $class = "", $style = "" )
	{
		if ( $style == "" )
			$this->html = "<div class='$class'>" . $this->html . "</div>";
		else
			$this->html = "<div class='$class' style='$style'>" . $this->html . "</div>";
	}

	function hugContainer( $class = "container", $style = "" )
	{
		$this->hug( $class, $style );
	}

	function hugRow( $class = "row", $style = "" )
	{
		$this->hug( $class, $style );
	}

	function hugSpan( $class = "span4", $style = "" )
	{
		$this->hug( $class, $style );
	}

	function get( )
	{
		return ( $this->html );
	}
	function render( )
	{
		echo $this->get();
	}
}


/** Classe responsável pela estrutura básica da apresentação visual
 * @package	gOutput
 * @author	Giuliano Nascimento <giusoft@hotmail.com>
 * @version	1.0 07-04-2009 10:50
 */
class g_Output
{
	var $http_base;
	var $http_inc;
	var $http_img;
	var $http_css;
	var $http_lib;
	var $gPath;
	var $gPathCss;
	var $gPathDefault;
	var $gPathImg;
	var $gPathLib;

	var $n = NL;
	var $parameters = "";
	var $renderTo = gRENDER_REMOTE; // pra onde será enviado o resultado
	var $renderFile = "out.html";
	var $renderType = "HTML";
	var $jsAfterSencha = "";
	var $stack = "";
	var $echo = true;
	var $tabcnt = 0;
	var $idcnt = 0;
	var $maps = false;
	var $extjs = true;
	var $sencha = false;
	var $jquery = true;
	var $editarea = false;
	var $codemirror = false;
	var $span = true;

	var $ace = false;
	var $effects = "";
	var $layout = "";
	var $tr;

	public $outputRecording = false;
	public $useBuffer = false;
	public $buffer = "";
	public $title = "";
	public $pagination = false;
	public $maxRows = 20;

	var $page = "";
	var $pageSetup = "";
	var $pageTitle = "";
	var $pageSubTitle = "";
	var $pageMiniTitle = "";
	var $pageFilter = "";

	var $styleMessagePrefix = '';
	var $styleMessageSufix = '';

	var $html_css = "";
	var $http_system_css = "";
	var $html_meta = "";
	var $css = "styleWeb.css";
	var $pageCss = "";

	var $statusBegin = false; // se já gerou o código de início
	var $statusEnd = false; // se já gerou o código de final

	public $reportTitle;
	public $reportSubTitle;
	public $reportFilter;

	public $iphoneOnline = false;
	public $bodyEvent = "";

	/** Monta todo o código HTML de cabeçalho da página Web
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 10:50
	 */
	function __construct( $json = "" )
	{
		global $http_base;
		global $http_inc;
		global $http_css, $http_system_css;
		global $http_img;
		global $http_lib;

		global $gPath;
		global $gPathCss;
		global $gPathDefault;
		global $gPathImg;
		global $gPathLib;
		global $gDevice, $gOs;

		global $gConstructed;

		global $extjsBuffer;

		if ( intval( gVar( "global.maxrows" ) ) > 0 )
		{
			$this->maxRows = intval( gVar( "global.maxrows" ) );
		}
		if ( !$gConstructed )
		{
			$css = cssDecode( $json );
			if ( $css[ 'pagination' ] == "true" )
			{
				$this->pagination = true;
			}
			if ( intval( $css[ 'maxRows' ] ) > 0 )
			{
				$this->maxRows = intval( $css[ 'maxRows' ] );
			}
			if ( $css[ 'renderTo' ] <> "" )
				$this->renderTo = $css[ 'renderTo' ];
			if ( $this->renderTo <> gRENDER_REMOTE )
				$this->useBuffer = true;

			if ( $css[ 'title' ] <> '' )
				$this->title = $css[ 'title' ];
			if ( $css[ 'maps' ] == 'true' )
				$this->maps = true;
			if ( $css[ 'jquery' ] == 'false' )
				$this->jquery = false;
			if ( $css[ 'jquery' ] == 'true' )
				$this->jquery = true;
			//echo "<h1>$json\n\n".$css['jquery']." === ".$this->gPathLib.gVar("lib.jquery")." === ".$this->jquery;exit;

			if ( $css[ 'extjs' ] == 'false' )
				$this->extjs = false;
			if ( $css[ 'extjs' ] == 'true' )
				$this->extjs = true;

			if ( $css[ 'codemirror' ] == 'true' )
				$this->codemirror = true;
			if ( $css[ 'codemirror' ] == 'false' )
				$this->codemirror = false;

			if ( $css[ 'span' ] == 'true' )
				$this->span = true;
			if ( $css[ 'span' ] == 'false' )
				$this->span = false;

			if ( $css[ 'bootstrap' ] == 'true' )
				$this->bootstrap = true;
			if ( $css[ 'bootstrap' ] == 'false' )
				$this->bootstrap = false;
			if ( $css[ 'scroll' ] <> "" )
				$this->senchaScroll = $css[ 'scroll' ];
			$this->parameters      = $css;
			$this->page            = $_SERVER[ "PHP_SELF" ] . "?g=" . $_REQUEST[ 'g' ];
			$this->http_base       = $http_base;
			$this->http_inc        = $http_inc;
			$this->http_css        = $http_css;
			$this->http_system_css = $http_system_css;
			$this->http_img        = $http_img;
			$this->http_lib        = $http_lib;

			$this->gPath        = $gPath;
			$this->gPathCss     = $gPathCss;
			$this->gPathDefault = $gPathDefault;
			$this->gPathImg     = $gPathImg;
			$this->gPathLib     = $gPathLib;

			$this->html_meta .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . strtoupper( gVar( "global.charset" ) ) . "\">";
			$this->html_css = $this->http_css . $this->css;

			$pre[ "normal" ]    = "<span class='g-msg-normal'>";
			$pre[ "title" ]     = "<span class='g-msg-title'>";
			$pre[ "subtitle" ]  = "<span class='g-msg-subtitle'>";
			$pre[ "maxititle" ] = "<span class='g-msg-maxititle'>";
			$pre[ "minititle" ] = "<span class='g-msg-minititle'>";
			$pre[ "filter" ]    = "<span class='g-msg-filter'>";
			$pre[ "footer" ]    = "<span class='g-msg-footer'>";
			$pre[ "footer_2" ]  = "<span class='g-msg-footer_2'>";
			$pre[ "alert" ]     = "<span class='g-msg-alert'>";
			$pre[ "error" ]     = "<span class='g-msg-error'>";

			$pos[ "normal" ]    = "</span>";
			$pos[ "title" ]     = "</span>";
			$pos[ "subtitle" ]  = "</span>";
			$pos[ "maxititle" ] = "</span>";
			$pos[ "minititle" ] = "</span>";
			$pos[ "filter" ]    = "</span>";
			$pos[ "footer" ]    = "</span>";
			$pos[ "footer_2" ]  = "</span>";
			$pos[ "alert" ]     = "</span>";
			$pos[ "error" ]     = "</span><br>";

			$this->styleMessagePrefix = $pre;
			$this->styleMessageSufix  = $pos;

			if ( $css[ 'header' ] == 'false' )
			{
				// Sem cabeçalho, não deve ficar no cache (fix para o IE)
				header( "Cache-Control: no-cache, must-revalidate" ); // HTTP/1.1
				header( "Expires: Sat, 26 Jul 1997 05:00:00 GMT" ); // Date in the past
				//header('Content-type: text/html; charset=UTF-8');
			}
			else
			{
				if ( $this->renderTo <> gRENDER_DOWNLOAD )
				{
					//$this->gOut("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">".NL);
					//header("Content-Type: text/html; charset=".strtoupper(gVar("global.charset")));
					$this->tabcnt++;
					//if (($gDevice=="tablet") || ($gDevice=="mobile"))
					if ( strpos( $_SERVER[ "PHP_SELF" ], "index.php" ) === false )
					{
						$this->gOut( "<!DOCTYPE html>\n" );
					}
					$this->gOut( "<html>\n" );
					$this->gOut( "<head>\n" );
					if ( $this->title == "" )
						$this->title = gVar( "global.site" );
					$this->gOut( "<title>" . $this->title . "</title>\n" );
					$this->gOut( $this->html_meta . $this->n() );
					//echo "entrou: \n".str_replace("<","",$this->html_meta);
					$this->gOut( "<link rel='shortcut icon' href='" . $this->http_img . "" . gVar( "global.icon" ) . "'>\n" );

					//$this->gOut('<meta name="description" content="Tutorial Básico de Tableless" />');
					//$this->gOut('<meta name="keywords" content="tutorial, tableless,XML, XHTML, HTML, CSS, javascript,acessibilidade" />');
					/*
					$this->gOut('<meta name="robots" content="ALL" />');
					$this->gOut('<meta name="rating" content="General" />');
					$this->gOut('<meta name="author" content="GiuSoft Tecnologia Ltda" />');
					$this->gOut('<meta name="language" content="pt-br" />'.$this->n());
					*
					*/
					$icon = gVar( "global.icon" );
					$icon = str_replace( "ico", "png", $icon );
					//$this->gOut('<link rel="apple-touch-icon-precomposed" href="'.$icon.'" />');
					//$this->gOut('<meta name="apple-mobile-web-app-capable" content="yes" />');
					if ( $gDevice == "mobile" )
					{

						//$this->gOut('<meta name="viewport" content="width=device-width,initial-scale=1.0, user-scalable=no">');
					}
					elseif ( $gDevice == "tablet" )
					{
						//$this->gOut('<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />');
					}
					$googleFonts = gVar( "global.googlefonts" );

					if ( $googleFonts <> "" )
					{
						$fonts = explode( ",", $googleFonts );
						foreach ( $fonts as $f )
							$this->gOut( "<link href='https://fonts.googleapis.com/css?family=$f' rel='stylesheet' type='text/css'>\n" );
					}

					if ( $this->maps )
					{
						$this->gOut( "<script type=\"text/javascript\" src=\"https://maps.google.com/maps/api/js?sensor=true\"></script>" );
						$this->gOut( "<style type=\"text/css\"> html { height: 100% } body { height: 100%; margin: 0px; padding: 0px } #divGoogleMap { height: 100% }</style>" );
						$this->gOut( "<script type=\"text/javascript\" >
 function initializeMap(txt, id, posLat,posLong) {
    var latlng = new google.maps.LatLng(posLat, posLong);
    var myOptions = {
      zoom: 17,
      center: latlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    var googleMap = new google.maps.Map(document.getElementById(id),
        myOptions);
	new google.maps.Marker({
		position: new google.maps.LatLng(posLat, posLong),
		map: googleMap,
		title: txt
	});
  }
</script>" );

					}
					if ( ( $this->editarea ) && ( file_exists( $this->gPathLib . gVar( "lib.editarea" ) ) ) )
					{
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.editarea" ) . "\"></script>\n" );
					}
					if ( ( $this->codemirror ) && ( file_exists( $this->gPathLib . gVar( "lib.codemirror" ) ) ) )
					{
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "lib/codemirror.js" . "\"></script>\n" );
						$this->gOut( "<link rel=\"stylesheet\" href=\"$http_lib" . gVar( "lib.codemirror" ) . "lib/codemirror.css" . "\">\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "mode/xml/xml.js" . "\"></script>\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "mode/javascript/javascript.js" . "\"></script>\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "mode/css/css.js" . "\"></script>\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "mode/clike/clike.js" . "\"></script>\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "mode/gfw/gfw.js" . "\"></script>\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "mode/mysql/mysql.js" . "\"></script>\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "mode/htmlmixed/htmlmixed.js" . "\"></script>\n" );

						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "lib/util/dialog.js" . "\"></script>\n" );
						$this->gOut( "<link rel=\"stylesheet\" href=\"$http_lib" . gVar( "lib.codemirror" ) . "lib/util/dialog.css" . "\">\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "lib/util/searchcursor.js" . "\"></script>\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "lib/util/search.js" . "\"></script>\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.codemirror" ) . "lib/util/foldcode.js" . "\"></script>\n" );
						$this->gOut( "<link rel=\"stylesheet\" href=\"$http_lib" . gVar( "lib.codemirror" ) . "theme/blackboard.css" . "\">\n" );
						$this->gOut( "<link rel=\"stylesheet\" href=\"$http_lib" . gVar( "lib.codemirror" ) . "theme/cobalt.css" . "\">\n" );
						$this->gOut( "<link rel=\"stylesheet\" href=\"$http_lib" . gVar( "lib.codemirror" ) . "theme/elegant.css" . "\">\n" );
						$this->gOut( "<link rel=\"stylesheet\" href=\"$http_lib" . gVar( "lib.codemirror" ) . "theme/vibrant-ink.css" . "\">\n" );

						//$this->gOut("<style>.CodeMirror-scroll {height: auto; overflow-y: visible; overflow-x: auto;}</style>");
						//$this->gOut("<style>.CodeMirror {border: 1px solid #eee;} .CodeMirror-scroll {height: auto;overflow-y: hidden;overflow-x: auto;width: 100%;}</style>");

						/*
						$this->gOut("<style>
						.CodeMirror {
						height: inherit;
						}

						.CodeMirror-scroll {
						height: auto;
						overflow: auto;
						width: 100%;
						height: inherit;
						}

						</style>");
						*/
					}
					if ( ( $this->jquery ) && ( file_exists( $this->gPathLib . gVar( "lib.jquery" ) ) ) )
					{
						$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.jquery" ) . "\"></script>\n" );
						$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.jquery_plugins" ) . "s3Slider.js\"></script>\n" );
						$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.jquery_plugins" ) . "jquery.dimensions.js\"></script>\n" );
						$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.jquery_plugins" ) . "jquery.ahover.js\"></script>\n" );
						$this->gOut( "<script type=\"text/javascript\">
						function ajaxLoadPage(pag,div) {
							$('#'+div).hide('slow', function () {
								$('#'+div).load(pag + '?' + Math.random()*99999, function() {
									$('#'+div).show('slow');
								});
							});
							 return false;
						};
						</script>\n" );
					}
					if ( ( $css[ 'editarea' ] == 'true' ) && ( file_exists( $this->gPathLib . gVar( "lib.editarea" ) ) ) )
					{
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.editarea" ) . "\"></script>\n" );
					}
					/*
					if (($this->codemirror) && (file_exists($this->gPathLib.gVar("lib.codemirror"))))
					{
					$this->gOut("<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib".gVar("lib.codemirror")."lib/codemirror.js\"></script>\n");
					$this->gOut("<link rel='stylesheet' type='text/css' href=\"$http_lib".gVar("lib.codemirror")."lib/codemirror.css\">\n");
					$this->gOut("<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib".gVar("lib.codemirror")."mode/xml/xml.js\"></script>\n");
					$this->gOut("<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib".gVar("lib.codemirror")."mode/clike/clike.js\"></script>\n");
					$this->gOut("<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib".gVar("lib.codemirror")."mode/javascript/javascript.js\"></script>\n");
					$this->gOut("<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib".gVar("lib.codemirror")."mode/css/css.js\"></script>\n");
					$this->gOut("<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib".gVar("lib.codemirror")."mode/php/php.js\"></script>\n");
					}
					*
					*/
					if ( ( $this->bootstrap ) && ( file_exists( $this->gPathLib . gVar( "lib.bootstrap" ) ) ) )
					{
						$this->gOut( "<link rel='stylesheet' type='text/css' href=\"$http_lib" . gVar( "lib.bootstrap" ) . "css/bootstrap.css\">\n" );
						$this->gOut( "<link rel='stylesheet' type='text/css' href=\"$http_lib" . gVar( "lib.bootstrap" ) . "css/bootstrap-responsive.css\">\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.bootstrap" ) . "js/bootstrap.js\"></script>\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.bootstrap" ) . "js/bootstrap-button.js\"></script>\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.bootstrap" ) . "js/bootstrap-collapse.js\"></script>\n" );
						$this->gOut( "<script language=\"javascript\" type=\"text/javascript\" src=\"$http_lib" . gVar( "lib.bootstrap" ) . "js/bootstrap-modal.js\"></script>\n" );

					}
					$usrLang = $_SESSION[ 'usrLangString' ];
					if ( $usrLang == "" )
						$usrLang = gVar( "global.language" );
					// Sencha
					//echo "gOS: [$gOs] - $gDevice - ".$this->extjs;
					//echo "<!--\n\n\n gDev: $gDevice ".$this->gPathLib.gVar("lib.sencha")."sencha-touch.js"." \n\n\n-->";

					//if ((($gDevice=="tablet") && ($this->extjs) && (file_exists($this->gPathLib.gVar("lib.sencha")."sencha-touch.js"))) || ($gOs=="ios") || ($gOs=="android"))

					if ( ( file_exists( $this->gPathLib . gVar( "lib.sencha" ) . "sencha-touch.js" ) ) && ( $css[ 'sencha' ] <> "false" ) )
					{
						if ( $gDevice == "tablet" )
							$this->sencha = true;
						else
						{
							if ( $gDevice == "mobile" )
							{
								if ( ( $gOs == "ios" ) || ( $gOs == "android" ) )
									$this->sencha = true;
							}
						}
					}
					if ( $css[ 'sencha' ] == "false" )
					{
						$this->sencha = false;
					}
					//echo "sencha: ".$this->sencha." extjs: ".$this->extjs."<pre>";print_r($css);exit;
					if ( ( $this->sencha ) || ( $this->extjs ) )
					{
						$funcoes = "
	function gWait(tipo)
	{
		if (tipo=='')
		{
			m=new Ext.LoadMask(Ext.getBody(),{msg:'" . gT( "Carregando..." ) . "'});m.show();
		} elseif (tipo==1)
		{
			Ext.getBody().fadeOut({ endOpacity: .25, duration: 4 });
		} elseif (tipo==2)
		{
			Ext.getBody().ghost();
		} elseif (tipo==3)
		{
			Ext.getBody().puff({ duration: 3 });
		}
	}
						";
					}
					if ( $this->sencha )
					{
						$this->extjs = true;
						$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.sencha" ) . "sencha-touch.js\"></script>\n" );
						$this->gOut( "<link href='" . $this->http_lib . gVar( "lib.sencha" ) . "resources/css/alitem.css' rel='stylesheet' type='text/css'>\n" );
						$extjsBuffer = "
$funcoes
Ext.apply(Ext.util.Format, {
	defaultDateFormat: 'd/m/y'
});
Ext.setup({
	icon: '$icon',
	glossOnIcon: true,
	fullscreen: true,
	style: 'background-color: white',
	onReady: function() {

";

					}
					// Habilita o extjs (se existir, e não tiver carregado o sencha)
					if ( ( !$this->sencha ) && ( $this->extjs ) && ( file_exists( $this->gPathLib . gVar( "lib.extjs" ) . "resources/css/ext-all.css" ) ) )
					{
						$tema   = "resources/css/xtheme-blue.css";
						$temasb = "superboxselect.css";
						if ( $_SESSION[ 'appDevel' ] > 0 )
						{
							$tema   = "resources/css/xtheme-gray.css";
							$temasb = "superboxselect-gray-extend.css";
						}
						if ( strpos( gVar( "lib.extjs" ), "-3." ) !== false )
						{
							$this->gOut( "<link href='" . $this->http_lib . gVar( "lib.extjs" ) . "resources/css/ext-all-notheme.css' rel='stylesheet' type='text/css'>\n" );
							if ( gVar( "lib.extensible" ) <> '' )
								$this->gOut( "<link href='" . $this->http_lib . gVar( "lib.extensible" ) . "resources/css/extensible-all.css' rel='stylesheet' type='text/css'>\n" );
						}
						else
						{
							$this->gOut( "<link href='" . $this->http_lib . gVar( "lib.extjs" ) . "resources/css/ext-all.css' rel='stylesheet' type='text/css'>\n" );

						}
						$this->gOut( "<link href='" . $this->http_lib . gVar( "lib.extjs" ) . "$tema' rel='stylesheet' type='text/css'>\n" );


						//$this->gOut("<link href='".$this->http_lib.gVar("lib.extjs")."resources/css/xtheme-gray.css' rel='stylesheet' type='text/css'>\n");
						$this->gOut( "<link href='" . $this->http_lib . gVar( "lib.extjs" ) . "examples/shared/icons/silk.css' rel='stylesheet' type='text/css'>\n" );
						$this->gOut( "<link href='" . $this->http_lib . gVar( "lib.extjs" ) . "examples/ux/css/RowEditor.css' rel='stylesheet' type='text/css'>\n" );
						$this->gOut( "<link href='" . $this->http_lib . gVar( "lib.extjs" ) . "examples/ux/fileuploadfield/css/fileuploadfield.css' rel='stylesheet' type='text/css'>\n" );
						$this->gOut( "<link href='" . $this->http_lib . gVar( "lib.extjs" ) . "$temasb' rel='stylesheet' type='text/css'>\n" );



						if ( strpos( gVar( "lib.extjs" ), "-3." ) !== false )
						{
							$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extjs" ) . "adapter/ext/ext-base.js\"></script>\n" );
							$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extjs" ) . "ext-all.js\"></script>\n" );
							$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extjs" ) . "src/locale/ext-lang-" . $usrLang . ".js\"></script>\n" );
							if ( gVar( "lib.extensible" ) <> '' )
							{
								$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extensible" ) . "extensible-all.js\"></script>\n" );
								$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extensible" ) . "src/locale/extensible-lang-" . $usrLang . ".js\"></script>\n" );
							}
						}
						else
						{
							$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extjs" ) . "ext-all.js\"></script>\n" );
							$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extjs" ) . "examples/compatibility/ext3-core-compat.js\"></script>\n" );
							$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extjs" ) . "examples/compatibility/ext3-compat.js\"></script>\n" );
							$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extjs" ) . "src/locale/ext-lang-" . $usrLang . ".js\"></script>\n" );
						}
						$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extjs" ) . "DateTime.js\"></script>\n" );
						$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extjs" ) . "examples/ux/RowEditor.js\"></script>\n" );
						$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extjs" ) . "examples/ux/fileuploadfield/FileUploadField.js\"></script>\n" );
						$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_lib . gVar( "lib.extjs" ) . "SuperBoxSelect.js\"></script>\n" );
						//$this->gOut("<script type=\"text/javascript\" src=\"".$this->http_lib.gVar("lib.extjs")."examples/ux/DataView-more.js\"></script>\n");
						//<script type="text/javascript" src="data-view.js"></script>


						$extjsBuffer .= "Ext.BLANK_IMAGE_URL = '" . $this->http_lib . gVar( "lib.extjs" ) . "resources/images/default/s.gif';\n";
						$extjsBuffer .= "Ext.chart.Chart.CHART_URL = '" . $this->http_lib . gVar( "lib.extjs" ) . "resources/charts.swf';\n";
						$extjsBuffer .= "Ext.QuickTips.init();\n";
						$extjsBuffer .= "
$funcoes

Ext.ns('Ext.ux.form');
Ext.ux.form.RCheckbox = Ext.extend(Ext.form.Checkbox, {
	 uncheckedValue:'off',checkedValue  :'on',
	 onRender:function() {
		  Ext.ux.form.RCheckbox.superclass.onRender.apply(this, arguments);
		  this.hiddenField = this.wrap.insertFirst({tag:'input', type:'hidden'});
		  this.hiddenField.dom.name = this.el.dom.name;
		  this.el.dom.name = '';
		  this.updateHidden();
	 },
	 setValue:function(v) {
		  Ext.ux.form.RCheckbox.superclass.setValue.apply(this, arguments);
		  this.updateHidden();
	 },
	 updateHidden:function(v) {
		  if(this.hiddenField) {
				this.hiddenField.dom.value = this.checked ? this.checkedValue : this.uncheckedValue;
		  }
	 }
});
Ext.reg('rcheckbox', Ext.ux.form.RCheckbox);
";
						$extjsBuffer .= "Ext.onReady(function() {\n";
						$this->extjs = true;
					}
					$this->gOut( "<link href='" . $this->html_css . "' rel='stylesheet' type='text/css'>\n" );
					$this->gOut( "<link href='" . $this->http_system_css . "icons.css' rel='stylesheet' type='text/css'>\n" );
					$this->gOut( "<script type=\"text/javascript\" src=\"" . $this->http_inc . "gFunctions.js\"></script>\n" );
				}
				else
				{
					/*
					$this->gOut("<style>");
					$filename=$gPathCss.$this->css;
					$this->gOut(file_get_contents($filename));
					$this->gOut("</style>");
					*
					*/
				}
			}
			$this->loadTranslation();
		}
		else
		{
			$gConstructed = true;
		}

	}

	/** Gera TAB de identação
	 * @author	Giuliano Nascimento
	 * @version	1.0 15-06-2009 18:17
	 */
	function t( $qtt = 1 )
	{
		$sai = "";
		for ( $a = 0; $a < $qtt; $a++ )
			$sai .= "\t";
		return ( $sai );
	}

	/** Gera um salto de linha (\n) e identa a proxima
	 * @author	Giuliano Nascimento
	 * @version	1.0 15-06-2009 18:17
	 */
	function n( $qtt = 1 )
	{
		$sai = "";
		for ( $a = 0; $a < $qtt; $a++ )
			$sai .= NL;
		$sai .= $this->t( $this->tabcnt );
		return ( $sai );
	}

	function loadTranslation( )
	{
	}

	/** Traduz um termo para a linguagem corrente
	 * @author	Giuliano Nascimento
	 * @version	1.0 15-06-2009 18:17
	 */
	function tr( $string )
	{
		$sai = "";
		//$sai=$this->tr[strtolower($string)];
		$sai = gT( $string . ".long" );
		return ( $sai );
	}


	/** Exibe conteúdo na saída atual
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 13:44
	 * @param content string Conteúdo
	 */
	function gOut( $content )
	{
		if ( ( $this->useBuffer ) && ( $this->outputRecording ) )
			$this->buffer .= $content;
		else
			echo $content;
	}

	function addCss( $css )
	{
		$this->pageCss .= $css;
	}
	/** Recarrega a página atual com parâmetros
	 *
	 * @param string $param
	 */
	function reload( $param = "" )
	{
		if ( $param <> "" )
			$param = "&" . $param;
		header( "location: " . $this->page . $param );
	}


	function sqlFormat( $sql )
	{
		$sai       = "";
		$elementos = explode( " ", $sql );
		foreach ( $elementos as $el )
		{
			$el_mai = strtoupper( $el );
			if ( ( $el_mai == "SELECT" ) || ( $el_mai == "INSERT" ) || ( $el_mai == "DELETE" ) || ( $el_mai == "UPDATE" ) || ( $el_mai == "FROM" ) || ( $el_mai == "ORDER" ) || ( $el_mai == "GROUP" ) || ( $el_mai == "WHERE" ) || ( $el_mai == "LIMIT" ) )
			{
				$sai .= NL . $el_mai . NL . "\t";
			}
			elseif ( ( $el_mai == "INNER" ) || ( $el_mai == "LEFT" ) || ( $el_mai == "OUTER" ) )
			{
				$sai .= NL . $el_mai . " ";
			}
			elseif ( $el_mai == "JOIN" )
			{
				$sai .= $el_mai . NL . "\t";
			}
			else
			{
				$sai .= $el . " ";
			}
		}
		return ( $sai );
	}

	/** Registra mensagem com formatação de erro em HTML
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 13:44
	 * @param string $title Título
	 * @param string $content Conteúdo
	 */
	function gOnScreenError( $title = "", $content = "" )
	{
		global $gLastQuery;
		global $gLastTransaction;

		echo "<br><div align='center'><div style='width: 90%; border: 1pt solid #909090; text-align:center'><div style='width: 100%; background: #d0d0d0;'>$title</div><pre style='text-align: left'>";
		print_r( $content );
		echo "</pre>";
		echo "<div style='border-top: 1pt solid grey; width: 100%; font-size: 8pt; text-align:center'>" . $_SERVER[ 'REMOTE_ADDR' ] . " - " . $_SERVER[ "PHP_SELF" ] . "<br>" . gDateTime( date( "Y-m-d H:i:s" ) ) . "</div>";
		echo "<div style='border-top: 1pt solid grey; width: 100%; font-size: 8pt; text-align:left'>";
		echo "<b>Última query executada (tipo de transação $gLastTransaction):</b><br><br><pre>" . $this->sqlFormat( $gLastQuery ) . "</pre><br>";
		$this->gIDELink( gIDE_SQL, "", $gLastQuery );
		echo "</div>";
		echo "</div>";

	}

	/** Exibe conteúdo na saída atual preformatada
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 13:44
	 * @param content string Conteúdo
	 */
	function gDump( $content )
	{
		echo "\n\n<br><pre style='padding: 6px; border: 1px solid #d0d0d0; text-align: left;'>\n\n";
		var_dump( $content );
		echo "\n\n</pre><br>\n\n";
	}

	/** Registra mensagem com formatação de erro no Log
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 13:44
	 * @param string $title Título
	 * @param string $content Conteúdo
	 */
	function gError( $title = "", $content = "" )
	{
		$this->gOnScreenError( $title, $content );
		$content = implode( "|", $content );
		gLog( "gOutput	gError	$title: $content" );
		exit;
	}

	/** Cria um formato HTML padrão para páginas
	 * @author	Giuliano Nascimento
	 * @version	1.0 13-07-2009 16:22
	 * @param array $cfg Parâmetros
	 */
	function setLayout( $cfg )
	{
		$http_css = $this->http_css;
		$layout   = cssDecode( $cfg );
		$js       = "";
		$div      = "";
		if ( $layout[ "width" ] == "0" )
			$layout[ "width" ] = "100%";
		if ( $layout[ "height" ] == "0" )
			$layout[ "height" ] = "100%";
		if ( $layout[ "width" ] <> "" )
			$div .= "width: " . $layout[ "width" ] . "; ";
		if ( $layout[ "height" ] <> "" )
			$div .= "height: " . $layout[ "height" ] . "; ";
		if ( $layout[ "background" ] <> "" )
			$div .= "background: " . $layout[ "background" ] . "; ";
		if ( $layout[ "align" ] == "center" )
		{
			$div .= "margin: 0px auto; ";
			gVar( 'global.align', 'center' );
		}
		if ( $layout[ "top" ] <> "" )
			$div .= "margin-top: " . $layout[ "top" ] . "; ";

		$background    = $layout[ "background" ];
		$backgroundimg = $layout[ "background-image" ];
		if ( substr( $background, 0, 3 ) == "bg#" )
		{
			$arq        = trim( substr( $background, 3 ) );
			$background = "url(gfw/img/bg/$arq.jpg) repeat-x #$arq";
		}


		if ( $js <> "" )
		{
			$this->gOut( "<script language='javascript'>$js</script>" );
		}
		/*
		$this->gOut("<style type='text/css' >\n");
		$css="";
		if ($background<>"")
		$css.="background: ".$background.";";
		if ($backgroundimg<>"")
		{
		if (strpos('/',$backgroundimg)===false)
		$css.="background-image: url(gfw/img/bg/".$backgroundimg.");";
		else
		$css.="background-image: url(".$backgroundimg.");";
		}
		if ($css<>"")
		$this->gOut("body {".$css."}\n");
		$this->gOut("#g-body {".$div."}\n");
		$this->gOut("</style>\n");
		*/
		$this->layout = $layout;
	}

	/** Monta todo o código de início da página
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 10:50
	 */
	function gBegin( )
	{
		global $http_css;
		global $gPathCss;
		global $gContainers;
		global $gDevice, $gOs;

		$layout = $this->layout;
		$sai    = $this->statusBegin;
		if ( !$sai )
		{
			$bodyEvent = $this->bodyEvent;
			$this->tabcnt--;
			if ( $layout[ "layout" ] <> "" )
			{
				$this->gOut( "<link href='{$http_css}layouts/" . $layout[ "layout" ] . ".css' rel='stylesheet' type='text/css'>\n" );
			}
			$this->gOut( "</head>\n" );
			if ( $this->pageCss <> '' )
			{
				$this->gOut( "<style>\n" . $this->pageCss . "\n</style>" );
			}
			$css = "";
			if ( $layout[ 'background' ] <> "" )
				$css[ ] = "background: " . $layout[ 'background' ];
			if ( $layout[ 'background-image' ] <> "" )
				$css[ ] = "background-image: " . $layout[ 'background-image' ];
			if ( $layout[ 'height' ] <> "" )
				$css[ ] = "height: " . $layout[ 'height' ];
			if ( is_array( $css ) )
				$css = " style='" . implode( ";", $css ) . "'";
			if ( $this->span )
				$this->gOut( "<body id='body' $css $bodyEvent>\n" . "<span id='beforeBody'></span>\n" . "<!-- gBegin -->\n" . "<span id='onBody'>" );
			else
				$this->gOut( "<body id='body' $css $bodyEvent>\n" );
			if ( $layout <> "" )
			{
				$this->gOut( $this->n( 2 ) );
				$this->gOut( "" );
				$html = file_get_contents( $gPathCss . "layouts/" . $layout[ 'layout' ] . ".html" );

				$html = gReplaceMacros( $html, $gContainers );
				$this->gOut( $html );
				//$this->gOut("<div id='g-body' style='text-align: ".gVar("global.align")."'>\n");
			}
		}
		$this->statusBegin     = true;
		$this->outputRecording = true;
		return ( $sai );
	}

	/** Monta todo o código de final da página
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 10:50
	 */
	function gEnd( )
	{
		global $g__download;
		global $g__js;
		$this->outputRecording = false;
		if ( !$g__download )
		{
			if ( $this->parameters[ 'header' ] <> "false" )
			{
				if ( !$this->statusEnd )
				{
					// monta código de saída
					//if ($this->layout<>"")
					{
						//$this->gOut("</div>\n");
						if ( $this->span )
							$this->gOut( "</span>\n" . "<!-- gEnd -->\n" );

					}
					//$this->gOut("function jQueryRun(){".NL);
					if ( ( gVar( "lib.jquery" ) <> "" ) && ( is_array( $this->effects ) ) )
					{
						$this->gOut( "<script language='javascript'>" . NL );
						foreach ( $this->effects as $txt )
							$this->gOut( $txt . NL );
						$this->gOut( "</script>" . NL );
					}

					//$this->gOut("}".NL);
					if ( $this->span )
						$this->gOut( "<span id='afterBody'></span>\n" );
					if ( $this->maps )
						$this->gOut( "<div id='divGoogleMap' style='width: 100%; height: 100%'></div>" );
					$this->gOut( "</body>\n" );
					javaScript( $g__js . "\n" . $this->jsAfterSencha );
					$this->gOut( "</html>" );
				}
				$sai             = $this->statusEnd;
				$this->statusEnd = true;
				return ( $sai );
			}
		}
	}

	/** Monta todo o código de início e final da página
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 10:50
	 */
	function gBody( $txt )
	{
		global $gDevice;
		if ( ( $txt <> "" ) && ( $gDevice == "web" ) )
		{
			extjsDo( "Ext.getBody().setStyle('background','white');" );
			extjsDo( "Ext.getBody().setStyle('padding','6px');" );
			extjsDo( "Ext.getBody().setStyle('margin-right','2px');" );
		}
		$this->gBegin();
		$this->gOut( $txt );
		$this->gEnd();
	}

	/** Retorna um ou mais espaços na tela
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 13:44
	 * @param integer qtt Quantidade
	 */
	function gSpc( $qtt = 1 )
	{
		$tag = '';
		for ( $a = 0; $a < $qtt; $a++ )
			$tag .= "&nbsp;";
		return ( $tag );
	}

	/** Efetua um ou mais saltos de linha
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 13:44
	 * @param integer qtt Quantidade
	 */
	function gBr( $qtt = 1 )
	{
		for ( $a = 0; $a < $qtt; $a++ )
			$tag .= "<br />";
		return ( $tag );
	}

	/** Mostra o GoogleMaps e o marcador da posição especificada
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 13:44
	 * @param string text Texto do marcador
	 * @param float latitude
	 * @param float longitude
	 */
	function map( $text, $latitude, $longitude )
	{
		$this->jsAfterSencha .= ( "initializeMap('$text','divGoogleMap'," . str_replace( ",", "", $latitude ) . "," . str_replace( ",", "", $longitude ) . ");" );
	}

	function _cssIf( $var, $txt, $default = "" )
	{
		if ( $txt <> "" )
			$txt = "$var: '$txt'";
		else
			$txt = $default;
		return ( $txt );
	}
	function _parIf( $var, $txt, $default = "" )
	{
		if ( $txt <> "" )
			$txt = "$var='$txt'";
		else
			$txt = $default;
		return ( $txt );
	}

	function _parImplode( $sep, $arr )
	{
		$new = "";
		foreach ( $arr as $el )
			if ( $el <> "" )
				$new[ ] = $el;
		$sai = implode( $sep, $new );
		if ( $sep == ";" )
			$sai = str_replace( "'", "", $sai );
		return ( $sai );
	}


	function gImagePath( $file, $replace = true, $idDono = 0 )
	{
		global $gPathImg, $gPath, $gSystemPathImg, $http_base, $http_img, $http_system_img;

		if ( strpos( $file, "." ) === false )
			$file .= ".png";
		$gApp  = $_SESSION[ 'gApp' ];
		$gApps = $_SESSION[ 'gApps' ];
		if ( $idDono == 0 )
			$idDono = $_SESSION[ 'usrAppId' ];
		$pasta[ $gSystemPathImg . "/" ]                                                                            = $http_system_img . "/";
		$pasta[ $gSystemPathImg . "/icons/" ]                                                                      = $http_system_img . "/icons/";
		$pasta[ $gPath . "/" ]                                                                                     = $http_base . "/";
		$pasta[ $gPathImg . "/" ]                                                                                  = $http_img . "/";
		$pasta[ $gPathImg . "/icons/" ]                                                                            = $http_img . "/icons/";
		$pasta[ $gPathImg . "usr/" . str_pad( $gApps[ $gApp ][ 'id_pessoas_dono' ], 9, "0", STR_PAD_LEFT ) . "/" ] = $http_base . "pub/img/usr/" . str_pad( $gApps[ $gApp ][ 'id_pessoas_dono' ], 9, "0", STR_PAD_LEFT ) . "/";
		$pasta[ $gPathImg . "usr/" . str_pad( $idDono, 9, "0", STR_PAD_LEFT ) . "/" ]                              = $http_base . "pub/img/usr/" . str_pad( $idDono, 9, "0", STR_PAD_LEFT ) . "/";


		$sai = "";
		foreach ( $pasta as $local => $http )
		{
			//echo "<br>$local".$file."<br>$http<hr>";
			if ( ( file_exists( $local . $file ) ) && ( $sai == "" ) )
			{
				$sai = $http . $file;
			}
			elseif ( ( file_exists( $local . "16/" . $file ) ) && ( $sai == "" ) )
			{
				$sai = $http . "16/" . $file;
			}
			elseif ( ( file_exists( $local . "32/" . $file ) ) && ( $sai == "" ) )
			{
				$sai = $http . "32/" . $file;
			}
			elseif ( ( file_exists( $local . "64/" . $file ) ) && ( $sai == "" ) )
			{
				$sai = $http . "64/" . $file;
			}

		}
		if ( $sai == "" )
		{
			if ( $replace )
			{
				if ( strpos( $file, '32/' ) !== false )
					$sai = self::gImagePath( '32/b0001', false );
				elseif ( strpos( $file, '64/' ) !== false )
					$sai = self::gImagePath( '64/b0001', false );
				else
					$sai = self::gImagePath( 'b0001', false );
			}
			else
			{
				$sai = $file;
			}
		}
		return ( $sai );
	}

	/** Gera uma TAG HTML de imagem, opcionalmente com link
	 * @author	Giuliano Nascimento
	 * @version	1.0 13-07-2009 18:37
	 * @param string $json Parametros: url, link, id
	 * @return string $sai TAG HTML
	 */
	function gImage( $json, $idDono = 0 )
	{
		$mtz  = cssDecode( $json );
		$tag  = "";
		$parm = "";
		$css  = "";

		$tag    = tagAdd( "img", "id", $mtz[ 'id' ] );
		$tag    = tagAdd( $tag, "border", "0" );
		$mtz    = cssRemove( $mtz, "id" );
		$tag    = tagAdd( $tag, "class", $mtz[ 'class' ], "g-image" );
		$mtz    = cssRemove( $mtz, "class" );
		$tag    = tagAdd( $tag, "alt", $mtz[ 'alt' ] );
		$mtz    = cssRemove( $mtz, "alt" );
		$target = $mtz[ 'target' ];
		$tag    = tagAdd( $tag, "target", $mtz[ 'target' ] );
		$mtz    = cssRemove( $mtz, "target" );

		$caption     = $mtz[ 'caption' ];
		$description = gT( $mtz[ 'description' ] );
		$hint        = gT( $mtz[ 'hint' ] );
		$url         = $mtz[ "size" ] . "/" . $mtz[ "url" ];
		$href        = $mtz[ "href" ];
		$effect      = $mtz[ "effect" ];
		$mtz         = cssRemove( $mtz, "caption" );
		$mtz         = cssRemove( $mtz, "description" );
		$mtz         = cssRemove( $mtz, "hint" );
		$mtz         = cssRemove( $mtz, "url" );
		$mtz         = cssRemove( $mtz, "size" );
		$mtz         = cssRemove( $mtz, "href" );
		$mtz         = cssRemove( $mtz, "effect" );
		$tag         = tagAdd( $tag, "src", self::gImagePath( $url, true, $idDono ) );
		$tag         = tagAdd( $tag, "style", cssEncode( $mtz ) );

		$ev = "onClick=\"gWait()\"";
		if ( $effect <> "" )
		{
			if ( strtolower( $effect ) == "fadeout" )
				$ev = "onClick='gWait(1)'";
			if ( strtolower( $effect ) == "ghost" )
				$ev = "onClick='gWait(2)'";
			if ( strtolower( $effect ) == "puff" )
				$ev = "onClick='gWait(3)'";
		}
		if ( $href <> "" )
		{
			if ( $target <> '' )
				$target = "target='$target'";
			if ( ( strpos( $href, ".php" ) === false ) && ( strpos( $href, ".html" ) === false ) && ( strpos( $href, "www" ) === false ) && ( strpos( $href, "http" ) === false ) )
				$tag = "<a class='g-link-img' $target href='javascript:" . html_entity_decode( $href ) . "' title='$hint'>" . $tag;
			else
				$tag = "<a class='g-link-img' $target href='" . html_entity_decode( $href ) . "' title='$hint' $ev>" . $tag;
		}
		if ( $caption <> "" )
		{
			$style = "margin: 4px";
			$tag .= "<br /><span class=\"g-img-caption\">" . $caption . "</span>";
		}
		if ( $description <> "" )
		{
			$style = "margin: 4px";
			$tag .= "<br /><span class=\"g-img-description\">" . $description . "</span>";
		}
		if ( $href <> "" )
			$tag .= "</a>";

		return ( $tag );
	}

	/** Gera uma TAG HTML de ícone, opcionalmente com link
	 * @author	Giuliano Nascimento
	 * @version	1.0 13-07-2009 18:37
	 * @param string $json Parametros: url, link, id
	 * @return string $sai TAG HTML
	 */
	function gIcon( $json )
	{
		global $http_system_img, $gSystemPathImg;
		global $http_img, $gPathImg;

		$mtz  = cssDecode( $json );
		$tag  = "";
		$size = empty( $mtz[ 'size' ] ) ? "32" : $mtz[ 'size' ]; // default
		$parm = "";
		$css  = "";
		$url  = $size . "/" . $mtz[ 'url' ];
		/*
		if (file_exists($gPathImg."usr/".$mtz['url']))
		$url=$http_img."usr/".$mtz['url'];
		$imagem="icons/$size/".$mtz['url'].".png";
		if (file_exists($gPathImg.$imagem))
		$url=$http_img.$imagem;
		if (file_exists($gSystemPathImg.$imagem))
		$url=$http_system_img.$imagem;
		*/

		$parm[ ] = $this->_parIf( "id", $mtz[ "id" ] );
		$parm[ ] = $this->_parIf( "border", $mtz[ "border" ], "border='0'" );
		$parm[ ] = $this->_parIf( "class", $mtz[ "class" ], "class='g-icon'" );
		$parm[ ] = $this->_parIf( "alt", $mtz[ "alt" ] );

		$css[ ] = $this->_cssIf( "float", $mtz[ 'float' ] );
		//$css[]=$this->_cssIf("float",$mtz['float'],"float: left");


		if ( $mtz[ "href" ] <> "" )
		{
			$tag .= "<a class='g-link-icon' href='" . $mtz[ "href" ] . "'>";
			$style .= "margin: 4px";
		}
		if ( $css <> "" )
			$parm[ ] = "style=\"" . $this->_parImplode( ";", $css ) . "\"";
		$tag .= "<img src='" . $this->gImagePath( $url ) . "' " . $this->_parImplode( " ", $parm ) . " title='" . $mtz[ 'title' ] . "'/>";
		if ( $mtz[ "caption" ] <> "" )
		{
			$tag .= "<br /><span class='g-icon-caption'>" . $mtz[ 'caption' ] . "</span>";
		}
		if ( $mtz[ "href" ] <> "" )
			$tag .= "</a>";
		return ( $tag );
	}

	/** Gera uma TAG HTML de link para outra página
	 * @author	Giuliano Nascimento
	 * @version	1.0 13-07-2009 18:37
	 * @param string $json Parametros: link, id, caption, alt
	 * @return string $sai TAG HTML
	 */
	function gLink( $json )
	{
		$http_img = $this->http_img;
		$mtz      = cssDecode( $json );

		$tag     = tagAdd( "a", "id", $mtz[ 'id' ] );
		$mtz     = cssRemove( $mtz, "id" );
		$tag     = tagAdd( $tag, "class", $mtz[ 'class' ], "g-link" );
		$mtz     = cssRemove( $mtz, "class" );
		$tag     = tagAdd( $tag, "alt", $mtz[ 'alt' ] );
		$mtz     = cssRemove( $mtz, "alt" );
		$tag     = tagAdd( $tag, "href", $mtz[ 'href' ] );
		$mtz     = cssRemove( $mtz, "href" );
		$caption = $mtz[ 'caption' ];
		$mtz     = cssRemove( $mtz, "caption" );
		$url     = $mtz[ 'url' ];
		$mtz     = cssRemove( $mtz, "url" );
		$tag     = tagAdd( $tag, "style", cssEncode( $mtz ) );
		if ( $url )
		{
			if ( $mtz[ "renderTo" ] <> "" )
			//$tag=substr($tag,0,strlen($tag)-1)." href='#' onClick='$(\"#".$mtz['renderTo']."\").load(\"".$url."\");'>";
				$tag = substr( $tag, 0, strlen( $tag ) - 1 ) . " href='#' onClick='ajaxLoadPage(\"$url\",\"" . $mtz[ 'renderTo' ] . "\");'>";

			else
			//$tag=substr($tag,0,strlen($tag)-1)." href='#' onClick='$(\"#g-body-box\").load(\"".$url."\");'>";
				$tag = substr( $tag, 0, strlen( $tag ) - 1 ) . " href='#' onClick='ajaxLoadPage(\"$url\",\"g-body-box\");'>";
		}
		$tag .= "$caption</a>";

		return ( $tag );
	}

	/** Gera uma TAG HTML de separação entre links
	 * @author	Giuliano Nascimento
	 * @version	1.0 13-07-2009 18:37
	 * @param string $json Parametros: char
	 * @return string $sai TAG HTML
	 */
	function gLinkSeparator( $json = "" )
	{
		$http_img = $this->http_img;
		$mtz      = jsDecode( $json );
		$tag      = "";
		$char     = " | ";
		if ( $mtz[ 'char' ] <> "" )
			$char = $mtz[ 'char' ];
		$tag .= "<span class='g-link-separator'>$char</span>";
		if ( $this->useBuffer )
		{
			$this->buffer .= $tag;
			$tag = "";
		}
		return ( $tag );
	}

	function _addEffect( $mtz )
	{
		// Animação
		if ( $mtz[ 'effect' ] <> "" )
		{
			$effect = $mtz[ 'effect' ];
			if ( ( $effect == "show" ) || ( $effect == "toggle" ) || ( $effect == "hide" ) || ( $effect == "slideUp" ) || ( $effect == "slideDown" ) || ( $effect == "showToggle" ) || ( $effect == "fadeIn" ) || ( $effect == "fadeOut" ) )
				$anim = "$(\"#" . $mtz[ "id" ] . "\").$effect('slow');";
			else
			{
			}
			$this->effects[ ] = $anim;
		}
		return ( $effect );
	}

	/** Gera um DIV com conteudo
	 * @author	Giuliano Nascimento
	 * @version	1.0 13-07-2009 18:37
	 * @param string $json Parametros: url, link, id
	 * @return string $sai TAG HTML
	 */
	function gBox( $json )
	{
		$mtz  = cssDecode( $json );
		$tag  = "";
		$parm = "";
		$css  = "";

		if ( $mtz[ 'id' ] == "" )
		{
			$this->idcnt++;
			$mtz[ 'id' ] = "gbox" . $this->idcnt;
		}

		$caption = $mtz[ 'caption' ];
		$effect  = $this->_addEffect( $mtz );
		if ( ( $effect == "show" ) || ( $effect == "fadeIn" ) )
			$css[ ] = "display: none"; // inicia oculto

		$tag = tagAdd( "div", "id", $mtz[ 'id' ] );
		$mtz = cssRemove( $mtz, "id" );
		$tag = tagAdd( $tag, "class", $mtz[ 'class' ], "g-box-internal" );
		$mtz = cssRemove( $mtz, "class" );
		$mtz = cssRemove( $mtz, "effect" );
		$mtz = cssRemove( $mtz, "caption" );

		$tag = tagAdd( $tag, "style", cssEncode( $mtz ) );

		$tag .= "<div class='g-box'>$caption</div></div>";
		return ( $tag );
	}

	/** Gera uma TAG HTML de botão, com link, opcionalmente com hover, e selected
	 * @author	Giuliano Nascimento
	 * @version	1.0 13-07-2009 18:37
	 * @param string $json Parametros: url, link, id
	 * @return string $sai TAG HTML
	 */
	function gButtom( $json )
	{
		global $http_system_img;
		$http_img = $this->http_img;
		$mtz      = jsDecode( $json );
		$tag      = "";
		$parm     = "";
		$css      = "";

		$parm[ ] = $this->_parIf( "id", $mtz[ "id" ] );
		$parm[ ] = $this->_parIf( "class", $mtz[ "class" ], "class='g-link-buttom'" );
		$parm[ ] = $this->_parIf( "alt", $mtz[ "alt" ] );
		$css[ ]  = $this->_cssIf( "background", $mtz[ 'background' ] );
		$css[ ]  = $this->_cssIf( "width", $mtz[ 'width' ] );
		$css[ ]  = $this->_cssIf( "height", $mtz[ 'height' ] );
		$css[ ]  = $this->_cssIf( "display", $mtz[ 'display' ], "display: block" );
		$css[ ]  = $this->_cssIf( "line-height", $mtz[ 'height' ] );
		$css[ ]  = $this->_cssIf( "text-align", $mtz[ 'text-align' ], "text-align: center" );
		$css[ ]  = $this->_cssIf( "font", $mtz[ 'font' ] );
		$css[ ]  = $this->_cssIf( "float", $mtz[ 'float' ] );
		//$css[]="display: inline";
		//$css[]="background: url(gfw/img/s.gif)";
		$url     = $mtz[ "url" ];
		if ( $url == "" )
			$url = $http_system_img . "s.gif";
		$style = "style=\"" . $this->_parImplode( ";", $css ) . "\"";
		if ( $mtz[ 'url' ] <> "" )
		{
			$tag .= "<div $style><a class='g-link-buttom' href='" . $mtz[ "href" ] . "' " . $this->_parImplode( " ", $parm ) . ">";
			$tag .= "<img class='g-buttom' src='$url'/>";
			$tag .= "</a></div>";
		}
		if ( $mtz[ "caption" ] <> "" )
		{
			$tag .= "<div $style><a class='g-link-buttom' href='" . $mtz[ "href" ] . "' " . $this->_parImplode( " ", $parm ) . ">" . $mtz[ 'caption' ] . "</a></div>";
		}
		return ( $tag );
	}

	/** Exibe um texto, que ao ser clicado (ou passar o mouse), surge outro texto embaixo
	 * @author	Giuliano Nascimento
	 * @version	1.0 17-06-2009 17:50
	 * @param string $title Texto sempre visível
	 * @param string $content Texto oculto
	 * @param string $event Tipo de evento que faz aparecer o texto oculto (click ou hover)
	 */
	function gMsgHide( $title, $content = "", $event = "hover" )
	{
		$this->idcnt++;
		$this->gOut( "<p id='gTh" . $this->idcnt . "' style='cursor: pointer'>" . $title . "</p>\n" );
		$this->idcnt++;
		$this->gOut( "<div id='gTh" . $this->idcnt . "' style='display:none'>$content</div>\n" );
		$this->gOut( "<script>\n" );
		if ( $event == "hover" )
		{
			$this->gOut( "$(\"#gTh" . ( $this->idcnt - 1 ) . "\").hover(function () {\n" );
			gEffect( "gTh" . $this->idcnt, "show" ) . "\n";
			$this->gOut( "},function () {\n" );
			gEffect( "gTh" . $this->idcnt, "hide" ) . "\n";
		}
		else
		{
			$this->gOut( "$(\"#gTh" . ( $this->idcnt - 1 ) . "\").click(function () {\n" );
			$this->gOut( "if ($(\"#gTh" . $this->idcnt . "\").css('display')=='none')\n" );
			gEffect( "gTh" . $this->idcnt, "show" ) . "\n";
			$this->gOut( "else\n" );
			gEffect( "gTh" . $this->idcnt, "hide" ) . "\n";
		}
		$this->gOut( "});\n" );
		$this->gOut( "</script>\n" );
	}

	/** Exibe um texto formatado ou não
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 13:44
	 * @param string $content Conteúdo
	 * @param string $style Estilo de apresentação
	 */
	function gMsg( $content, $style = gNORMAL, $css = "", $ahref = true )
	{
		global $http_inc, $gPrintMode, $gDevice;
		$tag = "";
		if ( $css <> "" )
			if ( substr( $css, 0, 1 ) == "{" )
				$css = substr( $css, 1, strlen( $css ) - 2 );
		if ( ( $style == gTITLE ) && ( $ahref ) )
		{
			$tag1 = "";
			if ( ( $gPrintMode ) && ( $gDevice == "web" ) )
			{
				$par = $this->page;
				foreach ( $_REQUEST as $key => $value )
				{
					if (is_array($value))
					{
						foreach ($value as $v)
						{
							if (intval($v)>0)
							{
								$par.= '&'.$key.'[]='.$v;
							}
						}

					} else
					{
						$par .= "&$key=$value";
					}
				}
				//gD($_REQUEST);echo $par;exit;
				$tag .= "<a href=\"JavaScript:gShowHide('gExportIcons');\" style=\"float: right;\" title=\"" . gT( "Exportar..." ) . "\">" . $this->gImage( "{url: 32/a0032;}" ) . "</a>";
				$tag .= "<div id=\"gExportIcons\" style=\"display: none\">";
				$tag .= "<a href=\"JavaScript:window.location='$par&gExportTo=Word';\" style=\"float: right;\" title=\"" . gT( "Exportar para texto" ) . "\">" . $this->gImage( "{url: 32/a0039;}" ) . "</a>";
				$tag .= "<a href=\"JavaScript:window.location='$par&gExportTo=Excel';\" style=\"float: right;\" title=\"" . gT( "Exportar para planilha" ) . "\">" . $this->gImage( "{url: 32/a0038;}" ) . "</a>";
				//$tag .= "<a href=\"JavaScript:window.location='$par&gExportTo=PDF';\" style=\"float: right;\" title=\"" . gT( "Exportar para PDF" ) . "\">" . $this->gImage( "{url: 32/a0040;}" ) . "</a>";
				$tag .= "</div>";
				$tag .= "<a href=\"JavaScript:imprimir();\" style=\"float: right;\" title=\"" . gT( "Imprimir" ) . "\">" . $this->gImage( "{url: 32/a0033; id: gPrintButton}" ) . "</a>";
				$tag .= "<a href=\"JavaScript:resizeText(1);\" style=\"float: right;\" title=\"" . gT( "Aumentar tamanho" ) . "\">" . $this->gImage( "{url: 32/a0035}" ) . "</a>";
				$tag .= "<a href=\"JavaScript:resizeText(-1);\" style=\"float: right;\" title=\"" . gT( "Diminuir tamanho" ) . "\">" . $this->gImage( "{url: 32/a0034}" ) . "</a>";
			}


			$tag .= "<a href=\"#\" class=\"g-msg-title\" onClick=\"javascript: window.location.reload();\" title=\"Clique aqui para atualizar esta página\">";
			$tag .= gT( $content );
			$tag .= "</a>";

		}
		else
		{
			$tag .= $this->styleMessagePrefix[ $style ];
			if ( $css <> "" )
				$tag .= "<span style=\"$css\">";
			if ( ( $style <> gNORMAL ) && ( $style <> '' ) )
				$tag .= gT( $content );
			else
				$tag .= $content;
			if ( $css <> "" )
				$tag .= "</span>";
			$tag .= $this->styleMessageSufix[ $style ];
		}
		if ( $style == gTITLE )
			$this->pageTitle = $content;
		if ( $style == gSUBTITLE )
			$this->pageSubTitle = $content;
		if ( $style == gMINITITLE )
			$this->pageMiniTitle = $content;
		if ( $style == gFILTER )
			$this->pageFilter = $content;

		$this->pageSetup[ $style ] = $content;
		return ( $tag );
	}

	/** Exibe um cabeçalho para a página, composto por título, subtítulo e filtros
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 13:44
	 * @param string $content Conteúdo
	 */
	function gHeader( $json )
	{
		$mtz = cssDecode( $json );
		global $gDevice;
		if ( ( $gDevice <> "iphone" ) && ( $gDevice <> "mobile" ) )
		{
			extjsDo( "Ext.getBody().setStyle('background','white');" );
			extjsDo( "Ext.getBody().setStyle('padding','6px');" );
			extjsDo( "Ext.getBody().setStyle('margin-right','2px');" );
		}
		$this->gBegin();
		if ( $mtz[ 'title' ] <> "" )
			$sai .= $this->gMsgTitle( $mtz[ 'title' ] );
		if ( $mtz[ 'subTitle' ] <> "" )
			$sai .= $this->gMsgSubTitle( $mtz[ 'subTitle' ] );
		if ( $mtz[ 'filter' ] <> "" )
			$sai .= $this->gMsgFilter( $mtz[ 'filter' ] );
		if ( $mtz[ 'alert' ] <> "" )
			$sai .= $this->gMsgAlert( $mtz[ 'alert' ] );
		reutn( $sai );
	}

	// Compatibilidade retroativa
	function gMsgTitle( $content, $css )
	{
		return ( $this->gMsg( $content, gTITLE, $css ) );
	}
	function gMsgSubTitle( $content, $css )
	{
		return ( $this->gMsg( $content, gSUBTITLE, $css ) );
	}
	function gMsgMiniTitle( $content, $css )
	{
		return ( $this->gMsg( $content, gMINITITLE, $css ) );
	}
	function gMsgMaxiTitle( $content, $css )
	{
		return ( $this->gMsg( $content, gMAXITITLE, $css ) );
	}
	function gMsgFilter( $content, $css )
	{
		return ( $this->gMsg( $content, gFILTER, $css ) );
	}
	function gMsgAlert( $content, $css )
	{
		return ( $this->gMsg( $content, gALERT, $css ) );
	}
	function gMsgError( $content, $css )
	{
		return ( $this->gMsg( $content, gERROR, $css ) );
	}
	function gMsgHtml( $content, $css )
	{
		return ( $this->gMsg( $content, gHTML, $css ) );
	}


	/** Monta o cabecalho da tabela de acordo com os paremetros
	 * @param $style	string	estilo da tabela.
	 * @param $border	boolean	true=com borda,false=sem borda
	 * @author Giuliano Nascimento
	 */
	function gTableBegin( $style = gT_DEFAULT, $border = false, $round = false )
	{
		global $gDevice;
		$perc = "100%";
		if ( $border )
			$border = "g-border";
		else
			$border = "g-noborder";
		if ( $round )
			$border = "g-roundborder";
		if ( strpos( $style, "%" ) !== false )
		{
			$perc  = $style;
			$style = "big";
		}
		if ( ( $border ) && ( ( $gDevice == "pdf" ) || ( $gDevice == "xls" ) ) )
			$sai = "<table width='$perc' border='1'>";
		else
			$sai = "<table width='$perc' class='g-table g-$style $border'>";
		$jvs = "
function c(el) {
//alert(el.style.backgroundColor);
if ((el.style.backgroundColor==\"\") || (el.style.backgroundColor==\"\#ffffff\") || (el.style.backgroundColor.toLowerCase()==\"rgb(255, 255, 255)\"))
	el.style.backgroundColor=\"#ffffcc\";
else
	el.style.backgroundColor=\"#ffffff\";
}
";
		addJavaScript( $jvs );
		return ( $sai );
	}

	function gTableEnd( )
	{
		$sai = "</table><br />";

		return ( $sai );
	}

	function gTableRow( $colMatrix, $style = "detail", $add = "", $event = "" )
	{
		$sai = "";
		if ( ( $style <> 'header' ) && ( $style <> 'none' ) )
		{
			$trStart = "<tbody class='g'><tr>";
			$trEnd   = "</tr></tbody>";
		}
		else
		{
			$trStart = "<tr>";
			$trEnd   = "</tr>";
		}

		if ( ( stripos( $style, "detail" ) !== false ) && ( $event == '' ) )
			$scrp = "onclick='c(this)' ";
		else
			$scrp = $event . ' ';
		if ( strpos( $style, ' ' ) !== false )
		{
			$s     = explode( ' ', $style );
			$style = " class='";
			foreach ( $s as $estilo )
			{
				$style .= "g-$estilo ";
			}
			$style .= "'";
		}
		else
			$style = $style <> "" ? " class='g-$style'" : "";
		$start = "<td$style $add>";
		$end   = "</td>";
		$sai .= $trStart;
		$c = count( $colMatrix );
		for ( $a = 0; $a < $c; $a++ )
		{
			$js    = "";
			$wrap  = "";
			$align = " align='center'";
			if ( is_array( $colMatrix[ $a ] ) )
			{
				$matrix[ $a ] = $colMatrix[ $a ][ 0 ];
				$param        = $colMatrix[ $a ][ 1 ];
			}
			else
			{
				$matrix[ $a ] = $colMatrix[ $a ];
			}
			if ( strlen( $matrix[ $a ] ) <= 10 )
			{
				$wrap = " nowrap ";
			}
			if ( strlen( $matrix[ $a ] ) == 0 )
			{
				$matrix[ $a ] = "&nbsp;";
			}
			if ( substr( $matrix[ $a ], strlen( $matrix[ $a ] ) - 1, 1 ) == "@" )
			{
				$wrap         = "";
				$matrix[ $a ] = substr( $matrix[ $a ], 0, strlen( $matrix[ $a ] ) - 1 );
			}
			$cs    = "";
			$smtrz = $matrix[ $a ];
			$smtri = 0;
			if ( substr( $matrix[ $a ], 0, 1 ) == "~" )
			{
				$colspan = substr( $matrix[ $a ], 1, 1 );
				if ( ( ord( substr( $matrix[ $a ], 2, 1 ) ) > 47 ) && ( ord( substr( $matrix[ $a ], 2, 1 ) ) < 58 ) )
				{
					$colspan .= substr( $matrix[ $a ], 2, 1 );
					if ( ( ord( substr( $matrix[ $a ], 3, 1 ) ) > 47 ) && ( ord( substr( $matrix[ $a ], 3, 1 ) ) < 58 ) )
					{
						$colspan .= substr( $matrix[ $a ], 3, 1 );
						$matrix[ $a ] = substr( $matrix[ $a ], 4 );
					}
					else
					{
						$matrix[ $a ] = substr( $matrix[ $a ], 3 );
					}
				}
				else
				{
					$matrix[ $a ] = substr( $matrix[ $a ], 2 );
				}
				$cs = " colspan='" . $colspan . "'";
				$smtri += $colspan;
			}
			if ( substr( $matrix[ $a ], 0, 2 ) == "->" )
			{
				$colspan = substr( $matrix[ $a ], 1, 1 );
				if ( $pdf <> "sim" )
					$align = "align='right'";
				$matrix[ $a ] = substr( $matrix[ $a ], 2 );
				$smtri += 2;
			}
			if ( substr( $matrix[ $a ], 0, 2 ) == "<-" )
			{
				$colspan = substr( $matrix[ $a ], 1, 1 );
				if ( $pdf <> "sim" )
					$align = "align='left'";
				$matrix[ $a ] = substr( $matrix[ $a ], 2 );
				$smtri += 2;
			}
			if ( substr( $matrix[ $a ], 0, 2 ) == "<>" )
			{
				$colspan = substr( $matrix[ $a ], 1, 1 );
				if ( $pdf <> "sim" )
					$align = "align='center'";
				$matrix[ $a ] = substr( $matrix[ $a ], 2 );
				$smtri += 2;
			}
			$sai .= substr( $start, 0, strlen( $start ) - 1 ) . $cs . $scrp . $align . $wrap . $js . trim( " " . $param ) . ">";
			$sai .= $matrix[ $a ];
			$sai .= $end;
		}
		$sai .= $trEnd;

		return ( $sai );
	}

	/**
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 13:44
	 * @param integer qtt Quantidade
	 */
	function gIDELink( $style = gIDE_HTML, $file = "", $content = "" )
	{
		$http_inc = $this->http_inc;
		$content  = urlencode( $content );
		$this->gOut( "<a href='" . $http_inc . "gIDE.php?style=$style&file=$file&content=$content'>" . gLng( "register_edit" ) . "</a>" );
	}


	function iTitleBar( $controls = true )
	{
	}

	function __destruct( )
	{
		$http_lib = $this->http_lib;
		$this->gEnd();
		if ( $this->renderTo == gRENDER_REMOTE )
		{
			if ( $this->useBuffer )
			{
				echo $this->buffer;
			}
		}
		elseif ( $this->renderTo == gRENDER_DOWNLOAD )
		{
			$HTTP_ENV_VARS = $_SERVER;
			if ( isset( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ] ) and strpos( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ], 'MSIE 6' ) )
			{
				header( 'Content-type: application/' . $this->renderType );
				header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
				header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" );
				header( "Pragma: public" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			}
			elseif ( isset( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ] ) and strpos( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ], 'MSIE 5.5' ) )
				Header( 'Content-Type: application/dummy' );
			else
				Header( 'Content-Type: application/octet-stream' );
			if ( headers_sent() )
				$this->gError( "Erro de download", 'Dados já foram enviados ao cliente, não é possível realizar o download.' );
			Header( 'Content-Length: ' . strlen( $this->buffer ) );
			Header( 'Content-disposition: attachment; filename=' . $this->renderFile );

			if ( strtoupper( $this->renderType ) == "HTML" )
			{
				echo "<style>";
				if ( file_exists( $this->gPathLib . gVar( "lib.extjs" ) . "resources/css/ext-all.css" ) )
					echo file_get_contents( $this->http_lib . gVar( "lib.extjs" ) . "resources/css/ext-all.css" );
				echo file_get_contents( $this->gPathCss . "styleWeb.css" );
				echo "</style>";
			}
			echo $this->buffer;
		}
		elseif ( $this->renderTo == gRENDER_EMAIL )
		{
		}
		elseif ( $this->renderTo == gRENDER_LOCAL )
		{
		}
	}






	// Novo padrão

	function begin( )
	{
		return $this->gBegin();
	}
	function end( )
	{
		return $this->gEnd();
	}
	function body( $html )
	{
		return $this->gBody( $html );
	}
	function out( $string )
	{
		return $this->gOut( $string );
	}
	function spc( $quantity )
	{
		return $this->gSpc( $quantity );
	}
	function br( $quantity )
	{
		return $this->gBr( $quantity );
	}
	function link( $link )
	{
		return $this->gLink( $link );
	}
	function box( $json )
	{
		return $this->gBox( $json );
	}

	function msg( $content, $style = '', $css = '', $href = '' )
	{
		return $this->gMsg( $content, $style, $css, $href );
	}
	function msgTitle( $content, $css )
	{
		return ( $this->gMsg( $content, gTITLE, $css ) );
	}
	function msgSubTitle( $content, $css )
	{
		return ( $this->gMsg( $content, gSUBTITLE, $css ) );
	}
	function msgMiniTitle( $content, $css )
	{
		return ( $this->gMsg( $content, gMINITITLE, $css ) );
	}
	function msgMaxiTitle( $content, $css )
	{
		return ( $this->gMsg( $content, gMAXITITLE, $css ) );
	}
	function msgFilter( $content, $css )
	{
		return ( $this->gMsg( $content, gFILTER, $css ) );
	}
	function msgAlert( $content, $css )
	{
		return ( $this->gMsg( $content, gALERT, $css ) );
	}
	function msgError( $content, $css )
	{
		return ( $this->gMsg( $content, gERROR, $css ) );
	}
	function msgHtml( $content, $css )
	{
		return ( $this->gMsg( $content, gHTML, $css ) );
	}


	function tableBegin( $size, $border, $round )
	{
		return $this->gTableBegin( $size, $border, $round );
	}
	function tableEnd( )
	{
		return $this->gTableEnd();
	}
	function tableRow( $cols, $style = '', $add = '', $event = '' )
	{
		return $this->gTableRow( $cols, $style, $add, $event );
	}
	function image( $json, $idDono )
	{
		return $this->gImage( $json, $idDono );
	}

}

class gRss
{
	var $buffer = "";

	function __construct( $json )
	{
		$mtz = cssDecode( $json );
		$arq = file_get_contents( $mtz[ 'link' ] );

		$this->buffer = "";
		if ( $arq )
		{
			$rss = new SimpleXmlElement( $arq );
			foreach ( $rss->channel->item as $entrada )
			{
				$htis->buffer[ ] = "title:" . $entrada->title . "," . "link:" . $entrada->link;
			}
		}
	}

	function get( )
	{
		$sai          = $this->buffer;
		$this->buffer = "";
		return implode( ";", $sai );
	}

}

$device = $gDevice;
if ( ( ( $gOs == "ios" ) || ( $gOs == "android" ) ) )
	$device = "iphone";
if ( ( $_SESSION[ 'gfw_version' ] == "hybrid" ) && ( strpos( $_SERVER[ "PHP_SELF" ], "login.php" ) === false ) && ( $gDevice <> "web" ) )
	$device = "web";
//echo $device;exit;
$inc = $gPathDefault . "dev" . gBAR . strtolower( $device ) . gBAR . "gOutput.php";
if ( file_exists( $inc ) )
{
	include_once $inc;
}
else
{
	$out = new g_Output();
	$out->gError( "Erro", "dispositivo de acesso ao sistema não encontrado: <br>inc: $inc<br>$device" );
}
?>
