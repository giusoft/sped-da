<?
namespace gMinimal;

// Tamanhos
define('gTINY', 0);
define('gMEDIUM', 1);
define('gBIG', 2);
define('gAUTO', -1);

// Estilos
define('gNORMAL', "normal");
define('gTITLE', "title");
define('gSUBTITLE', "subtitle");
define('gMINITITLE', "minititle");
define('gMAXITITLE', "maxititle");
define('gFILTER', "filter");
define('gFOOTER', "footer");
define('gALERT', "alert");
define('gERROR', "error");
define('gHTML', "html");

// Localização de código
define('gLOC_PRE', -1);
define('gLOC_INLINE', 0);
define('gLOC_POS', 1);
define('gLOC_JS', 2);

define('success',"g-msg-success");
define('alert',"g-msg-alert");

define('SENHA_NAO_MODIFICADA', '**_nao_mudou_**');


class gOutput
{

	public $onlyBody;
	public $indent;
	public $n;
	public $json;
	public $jarr;
	public $page;

	public $useBuffer = true;
	public $bufferPre = '';
	public $buffer = '';
	public $bufferPos = '';
	public $bufferJavascript = [];

	function __construct()
 	{
		global $gFW4, $http, $http_lib, $http_css, $http_inc, $http_img, $gDevice;
		$jarr = $this->jarr;
		$this->page = $_SERVER["PHP_SELF"] . "?g=" . $_REQUEST['g'];
		// Ativando modo de depuração
		if (($jarr['onlyBody'] == 'on') || ($jarr['onlyBody'] == 'true')) {
			$this->onlyBody = true;
		}
		if (!$this->onlyBody) {
			$style = "styleMinimal.css";
			$i18n = strtolower(gVar('global.language'));

			$jqueryPath = $http_lib . gVar("lib.jquery");

			$fontAwesome = $http_lib . gVar("lib.font_awesome");

			$this->out('<!DOCTYPE html>'."\n", gLOC_PRE);
			$this->out('<html>'."\n", gLOC_PRE, 1);
			$this->out('<head>'."\n", gLOC_PRE, 1);
			$this->out(tagMe('title', gVar("global.site")), gLOC_PRE);

			// META --------------------
			$this->out('<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n", gLOC_PRE);
			$this->out("<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . strtoupper(gVar("global.charset")) . "\">"."\n", gLOC_PRE);
			if (gVar("global.description") != '') {
				$this->out('<meta content= "'.gVar("global.description").'" name="description">'."\n", gLOC_PRE);
			}

			if (gVar("global.keywords") != '') {
				$this->out('<meta content= "'.gVar("global.keywords").'" name="keywords">'."\n", gLOC_PRE);
			}

			// PRE ---------------------
			if (gVar("global.icon") != "") {
				$this->out("<link rel='shortcut icon' href='" . $http_img . "" . gVar("global.icon") . "'>\n", gLOC_PRE);
			}

			if (!isset($gFW4)) {
				$this->out('<link href="' . $http_css . $style . '" rel="stylesheet">'."\n", gLOC_PRE);
			}

			// POS ---------------------
			$this->indent = 6;
			$this->out('<script src="' . $jqueryPath . '"></script>'."\n", gLOC_POS);
			// JqueryUi
			if(gVar("lib.jquery_ui") != ''){
				$this->out('<link href="'.$http_lib.gVar("lib.jquery_ui").'themes/base/minified/jquery-ui.min.css" rel="stylesheet" type="text/css">'."\n",gLOC_PRE);
				$this->out('<script src="'.$http_lib.gVar("lib.jquery_ui").'ui/minified/jquery-ui.min.js"></script>'."\n",gLOC_POS);
			}

			$this->out('<script src="' . $http_inc . 'gFunctions.js"></script>'."\n", gLOC_POS);
			if (file_exists($gPathDefault."pub/js/script.js")) {
				$this->out('<script src="' . $http . 'pub/js/script.js"></script>'."\n", gLOC_POS);
			}

			if (gVar("lib.parsley") != '') {
				$parsley = $http_lib . gVar("lib.parsley");
				$this->out('<script src="' . $parsley . 'i18n/messages.' . $i18n . '.js"></script>', gLOC_POS);
				$this->out('<script src="' . $parsley . 'parsley.js"></script>', gLOC_POS);
			}
		}
 	}

	/**
	 * Gera código de início da página
	 * @author	giuliano
	 * @param string $json Parâmetros em formato Json
	 * @version	4.0 10-08-2015 18:07
	 */
	function begin()
	{

	}


	function big(string $content): string
	{
		return('<span style="font-size: 150%">' . $content . '</span>' . $this->n);
	}

	/**
	 * Gera código de final da página
	 * @author	giuliano
	 * @param string $echo Sai direto na página? (true/false)
	 * @version	4.0 10-08-2015 18:07
	 */
	function end($echo=true): string
	{
		$this->setTimezone();
		if (!$this->onlyBody) {
			$this->out('<body>'."\n\n", gLOC_PRE, 1);
		}


		// Final da página
		if ($this->bufferJavascript != '') {
			$this->out('<script type="text/javascript">'."\n", gLOC_POS);
			foreach ($this->bufferJavascript as $js) {
				$this->out($js, gLOC_POS);
			}

			$this->out("
				function showWait(self){}
				function c(el) {
				if ((el.style.backgroundColor==\"\") || (el.style.backgroundColor==\"\#ffffff\") || (el.style.backgroundColor.toLowerCase()==\"rgb(255, 255, 255)\"))
					el.style.backgroundColor=\"#ffffcc\";
				else
					el.removeAttribute('style');
				}
				", gLOC_POS);

			$this->out('</script>'."\n", gLOC_POS, -1);
		}


		if (!$this->onlyBody) {
			$this->out(''."\n", gLOC_POS, -1);
			$this->out('</body>'."\n", gLOC_POS, -1);
			$this->out('</html>'."\n", gLOC_POS);
		}

		$sai='';
		if ($echo) {
			if (!$this->onlyBody) {
				echo $this->bufferPre;
			}

			echo $this->buffer."\n\n";
			if (!$this->onlyBody) {
				echo $this->bufferPos;
			}

		} else {
			if (!$this->onlyBody) {
				$sai.=$this->bufferPre;
			}

			$sai.=$this->buffer;
			if (!$this->onlyBody) {
				$sai.=$this->bufferPos;
			}

		}

		return($sai);
	}

	/**
	 * Gera saída na tela ou no buffer
	 * @author	giuliano
	 * @param string $content Dados
	 * @param string $location gLOC_PRE: no início da página, gLOC_INLINE: no meio, gLOC_POS: no final da página
	 * @param string $indent Se 1, da próxima vez indenta pra direita, se -1, remove um nível de indentação,
	 * 								 Se 0, nada faz. 999 ignora identação.
	 * @version	4.0 10-08-2015 18:07
	 */
	function out($content, $location = gLOC_INLINE, $indent = 0): void
	{
		if ($this->useBuffer) {
			if (is_array($content)) {
				if (!str_contains((string) $this->bufferPre, (string) $content[0])) {
					$this->bufferPre.=$content[0] . $this->n;
				}

				$this->buffer.=$content[1] . $this->n;
				if (!str_contains((string) $this->bufferPos, (string) $content[2])) {
					$this->bufferPos.=$content[2] . $this->n;
				}

				foreach ($content[3] as $js) {
					$this->bufferJavascript[] = $js;
				}
			} else {
				match ($location) {
					gLOC_PRE => $this->bufferPre.=$content . $this->n,
					gLOC_POS => $this->bufferPos.=$content . $this->n,
					default => $this->buffer.=$content . $this->n,
				};
			}

		} elseif (is_array($content)) {
			foreach ($content as $c) {
				echo $c;
			}
		} else {
			echo $content;
		}
	}

	/**
	 * Gera uma mensagem
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 10-08-2015 18:07
	 */
	function msg($content,$style="")
	{
		$cls = $style != "" ? ["class" => $style] : "" ;

		return(tagMe("span", gT($content),$cls));
	}

	/**
	 * Gera uma mensagem de Título
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 10-08-2015 18:07
	 */
	function msgTitle(string $content)
	{
		$sai = $this->h1('<a class="hidden-print" data-toggle="tooltip" data-placement="right" title="'.gT('Clique para abrir esta página no início').'" href="'.($url ?: $this->page).'"'.$param_a.' onClick="showWait();">'.$content.'</a>');
		return(tagMe("center", $sai));
	}

	/**
	 * Gera uma mensagem de Sub-título
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 10-08-2015 18:07
	 */
	function msgSubTitle($content)
	{
		return(tagMe("center",tagMe("h3", gT($content))));
	}

	function msgMiniTitle($content)
	{
		return(tagMe("center",tagMe("h4", gT($content))));
	}

	function msgError($msg)
	{
		return($this->msg($msg, "g-msg-alert"));
	}

	function msgInfo($msg)
	{
		return($this->msg($msg, "g-msg-info"));
	}

	function msgSuccess($msg)
	{
		return($this->msg($msg, "g-msg-success"));
	}

	function msgDanger($msg)
	{
		return($this->msg($msg, "g-msg-alert"));
	}

	function msgWarning($msg)
	{
		return($this->msg($msg, "g-msg-warning"));
	}

	function msgFilter($msg)
	{
		return($this->msg($msg, "g-msg-filter"));
	}

	function rowTags() {}
	/**
	 * Acumula código javscript e apresenta todos juntos no final da página
	 * @author	giuliano
	 * @param string $js Código javascript
	 * @version	4.0 10-08-2015 18:07
	 */
	function addJavascript($js): void
	{
		$this->bufferJavascript[] = $js;
	}

	function tableBegin($style = gT_DEFAULT, $border = false): string
	{
		global $o;
		$perc = "100%";
		$border = $border ? "g-border" : "g-noborder";

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
		$o->addJavaScript( $jvs );
		return ( $sai );
	}

	function tableEnd(): string
	{
		return ("</table>");
	}

	function tableRow($colMatrix, $style = "detail", $add = "", string $event = ""): string
	{
		$sai = "";
		if (($style != 'header' ) && ($style != 'none' )) {
			$trStart = "<tbody class='g'><tr>";
			$trEnd   = "</tr></tbody>";
		} else {
			$trStart = "<tr>";
			$trEnd   = "</tr>";
		}

		if (
			(stripos( $style, "detail" ) !== false)
			&& ($event == '')
		) {
			$scrp = "onclick='c(this)' ";
		} else {
			$scrp = $event . ' ';
		}

		if (str_contains((string) $style, ' ' )) {
			$s = explode(' ', (string) $style);
			$style = " class='";
			foreach ($s as $estilo) {
				$style .= "g-$estilo ";
			}
			$style .= "'";
		} else {
			$style = $style != "" ? " class='g-$style'" : "";
		}
		$start = "<td$style $add>";
		$end   = "</td>";
		$sai .= $trStart;
		$c = count( $colMatrix );
		for ($a = 0; $a < $c; $a++) {
			$js    = "";
			$wrap  = "";
			$align = " align='center'";
			if (is_array($colMatrix[$a])) {
				$matrix[$a] = $colMatrix[$a][0];
				$param = $colMatrix[$a][1];
			} else {
				$matrix[$a] = $colMatrix[$a];
			}

			if (strlen((string) $matrix[$a]) <= 10) {
				$wrap = " nowrap ";
			}

			if (strlen((string) $matrix[$a]) == 0) {
				$matrix[$a] = "&nbsp;";
			}

			if (substr((string) $matrix[$a], strlen((string) $matrix[$a]) - 1, 1 ) === "@") {
				$wrap = "";
				$matrix[$a] = substr((string) $matrix[$a], 0, strlen( (string) $matrix[$a]) - 1);
			}

			$cs = "";
			$smtrz = $matrix[$a];
			$smtri = 0;
			if (str_starts_with((string) $matrix[$a], "~")) {
				$colspan = substr((string) $matrix[$a], 1, 1);
				if (
					(ord(substr((string) $matrix[$a], 2, 1)) > 47)
					&& (ord(substr((string) $matrix[$a], 2, 1)) < 58)
				) {
					$colspan .= substr((string) $matrix[$a], 2, 1);
					if (
						(ord(substr((string) $matrix[$a], 3, 1)) > 47)
						&& (ord(substr((string) $matrix[$a], 3, 1)) < 58)
					) {
						$colspan .= substr((string) $matrix[$a], 3, 1);
						$matrix[$a] = substr((string) $matrix[$a], 4);
					} else {
						$matrix[$a] = substr((string) $matrix[$a], 3);
					}
				} else {
					$matrix[$a] = substr((string) $matrix[$a], 2);
				}
				$cs = " colspan='" . $colspan . "'";
				$smtri += $colspan;
			}

			if (str_starts_with((string) $matrix[$a], "->")) {
				$colspan = substr((string) $matrix[$a], 1, 1);
				if ($pdf != "sim") {
					$align = "align='right'";
				}
				$matrix[$a] = substr((string) $matrix[$a], 2);
				$smtri += 2;
			}

			if (str_starts_with((string) $matrix[$a], "<-")) {
				$colspan = substr((string) $matrix[$a], 1, 1);

				if ($pdf != "sim") {
					$align = "align='left'";
				}

				$matrix[$a] = substr((string) $matrix[$a], 2);
				$smtri += 2;
			}

			if (str_starts_with((string) $matrix[$a], "<>")) {
				$colspan = substr((string) $matrix[$a], 1, 1);

				if ($pdf != "sim") {
					$align = "align='center'";
				}

				$matrix[$a] = substr((string) $matrix[$a], 2);
				$smtri += 2;
			}

			$sai .= substr($start, 0, strlen($start ) - 1) . $cs . $scrp . $align . $wrap . $js . trim(" " . $param) . ">";
			$sai .= $matrix[$a];
			$sai .= $end;
		}
		$sai .= $trEnd;

		return ( $sai );
	}

	function small(string $txt): string
	{
		return('<small>'.$txt.'</small>');
	}

	function br($qtd=1): string
	{
		$sai = '';
		for($a = 0; $a < $qtd; $a++) {
			$sai .= '<br>';
		}
		return($sai);
	}

	/**
	 * Gera <H1 /> - Título da página
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $content Parâmetros em formato Json
	 * @param type $parm
	 * @return type
	 */
	function h1(string $content, $parm = ""): string
	{
		return(tagMe("h1", gT($content.' '), $parm) . $this->n);
	}

	/**
	 * Gera <H2 />
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $content Parâmetros em formato Json
	 * @param type $parm
	 * @return type
	 */
	function h2(string $content, $parm = ""): string
	{
		return(tagMe("h2", gT($content.' '), $parm) . $this->n);
	}

	/**
	 * Gera <H3 />
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $content Parâmetros em formato Json
	 * @param type $parm
	 * @return type
	 */
	function h3(string $content, $parm = ""): string
	{
		return(tagMe("h3", gT($content.' '), $parm) . $this->n);
	}

	/**
	 * Gera <H4 />
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $content Parâmetros em formato Json
	 * @param type $parm
	 * @return type
	 */
	function h4(string $content, $parm = ""): string
	{
		return(tagMe("h4", gT($content.' '), $parm) . $this->n);
	}

	/**
	 * Gera <HR />
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $content Parâmetros em formato Json
	 * @param type $parm
	 * @return type
	 */
	function h5(string $content, $parm = ""): string
	{
		return(tagMe("h5", gT($content.' '), $parm) . $this->n);
	}

	function hr(): string
	{
		return("<hr>");
	}

	function button($json): string
	{
		$jarr = cssDecode($json);
		if ($jarr['caption'] != '') {
			$jarr['title'] = $jarr['caption'];
		}

		//$sai = "<button onClick='window.location.href=\"".$jarr['href']."\"'>".$jarr['title']."</button>";
		$href=$jarr['href'];
		//$sai = "<button onClick=\"window.location.href=$href\">".$jarr['title']."</button>";
		$sai="<a href='$href' style='background-color: #f0f0f0;display: inline-block;text-align: center;text-decoration: none;color: black;padding: 4px 8px; border: 2px solid black;'>".$jarr['title']."</a>";
		return($sai);
	}


	function label(string $txt, $style=""): string
	{
		$class = "";
		switch ($style)
		{
			case 'info':
				$class="g-label-info";
			break;

			case 'success':
				$class="g-label-success";
			break;

			case 'warning':
				$class="g-label-warning";
			break;

			case 'danger':
				$class="g-label-danger";
			break;
		}
		return('<span class="g-label '.$class.'">'.$txt.'</span>');
	}


	function ul($elem, $parm = ""): string
	{
		$content = '';
		foreach ($elem as $el) {
			$par = '';
			if (is_array($el)) {
				$par = $el[1];
				$el = $el[0];
			}
			$content.=tagMe('li', gT($el), $par);
		}
		return('<div style="text-align: left">'.tagMe("ul", $content, $parm) . '</div>');
	}

	function setRowLayout() {}

	function nav($css, $items): void {
		foreach ($items as $key=>$value) {
			gD($key);
			gD($value);
		}
	}


	function dropdown() {}

	function chooseIcon($icon, $iconFont="fa", $size="normal") {}

	function setTimezone(): void{
		if (trim((string) $_SESSION['defaultTimezone']) === "" ) {
			$js = "
				var offset = (new Date().getTimezoneOffset() / 60);
				var timezone = 'Etc/GMT' + (offset >= 0 ? '+' : '-') + offset;

				$(document).ready(function(){
					$.ajax({
						url: '" . $this->page . "&gAjax=1&target=set_defaulttimezone&q=' + offset,
						dataType: 'json',
						type: 'GET',
						success: function(data){
							console.log(data);
						},
						error: function(){
							console.log(false);
						}
					});
				});";
			$this->addJavascript($js);
		}
	}


}


class gInput extends gOutput
{
	function __construct($json="")
	{
		parent::__construct($json);
	}
}

class gForm extends gInput
{
	private $fields = [];
	private $list = [];
	private $input_hidden = [];
	public $lists;
	public $size;
	public $showSubmit;
	public $class_element;
	public $class_offset;
	public $buttonNextCaption = '';
	public $buttonBackCaption = '';
	public $buttons;
	public $buttonsJavascript;
	private $name;
	private $id;
	//private $page = "";
	private $formJson;

	function __construct($json)
	{
		$mtz = cssDecode($json);
		$this->formJson = $json;
		//$this->page= $_SERVER[ "PHP_SELF" ];
		$this->name = gCleanField($mtz['name']);
		$this->id = gCleanField($mtz['id']) <> "" ? gCleanField($mtz['id']) : gCleanField($mtz['name']);
	}


	/**
	 * Adiciona um campo ao formulário (sem exibí-lo)
	 * @author	giuliano
	 * @param string $json Parâmetros pra criação do campo em formato JSON
	 * @param string $list Utilizado somente para Combolist (select) - array de elementos
	 * @version	4.0 10-08-2015 18:07
	 */
	public function add($json, $list = ""): void
	{
		if (trim($json) !== "") {
			$mtz = cssDecode($json);
			if ($mtz['type'] != 'exclude') {
				$name = $mtz['name'];
				$fieldLabel = $mtz['fieldLabel'];
				$type = $mtz['type'];

				if ($name == "") {
					$name = gString2Field($fieldLabel);
				}

				if (($type != 'label') && ($fieldLabel == "")) {
					$fieldLabel = gField2String($name);
				}

				if ($type=="html" || ($type=='show' && $name=='')) {
					$name=random_int(1000,9999);
					$fieldLabel='';
				}

				if ($mtz['type']=="") {
					$mtz['type']="text";
				}

				$mtz['name'] = $name;
				$mtz['fieldLabel'] = gT($fieldLabel);
				$this->fields[$name] = $mtz;
				$this->lists[$name] = $list;
			}

			//return $name;
		}
	}

	/**
	 * Adiciona um botão ao formulário (sem exibí-lo)
	 * @author	giuliano
	 * @param string $json Parâmetros pra criação do campo em formato JSON
	 * @version	4.0 01-12-2013 10:50
	 */
	function addButton($json, $javascript=''): void
	{
		$this->buttons[] = cssDecode($json);
		$this->buttonsJavascript[]=$javascript;
	}


	/**
	 * Renderiza botoes para o form
	 *
	 * @return string
	 */
	public function renderButtons(){
		global $o;
		if ($this->jarr['enabled'] == 'false') {
			$buttons = gOutput::msgWarning("Não é possível confirmar os dados no momento");
		} else {
			$buttons = '';

			// Titulo para o botao confirmar
			$submitBtn = $this->buttonNextCaption != '' ?  $this->buttonNextCaption : gT('Confirmar');

			// Botao voltar
			if ($this->buttonBackCaption != '') {
				if (is_object($o)) {
					$buttons.= $o->button("{icon: caret-left; type: button; title: " . $this->buttonBackCaption . "; size: $this->size; style: default; href:back");
				} else {
					$buttons.= gOutput::button("{icon: caret-left; type: button; title: " . $this->buttonBackCaption . "; size: $this->size; style: default; href:back");
				}
			}

			// Botao confirmar
			if ($this->showSubmit) {
				if (is_object($o)) {
					if ($this->jarr["onClickSubmit"]) {
						$click=$this->jarr["onClickSubmit"]."(this)";
						$buttons.= $o->button("{id: gSubmitButton; icon: check; type: button;  name: submit_default; hint: ".gT('Clique para enviar os dados').";title: " . $submitBtn . "; size: $this->size; style: primary}", $click);
					} else {
						$buttons.= $o->button("{id: gSubmitButton; icon: check; type: submit;  name: submit_default; hint: ".gT('Clique para enviar os dados').";title: " . $submitBtn . "; size: $this->size; style: primary}");
					}
				} else {
					$buttons.= gOutput::button("{id: gSubmitButton; icon: check; type: submit; name: submit_default; hint: ".gT('Clique para enviar os dados').";title: " . $submitBtn . "; size: $this->size; style: primary}");
				}

			}

			// Botoes adicionais
			if (is_array($this->buttons)) {
				foreach ($this->buttons as $indx=>$btn) {
					$btnStyle = "primary";
					$btnType = "button";
					$btnIcon = "";
					if ($btn['style'] != '') {
						$btnStyle = $btn['style'];
					}

					if ($btn['type'] != '') {
						$btnType = $btn['type'];
					}

					if ($btn['icon'] != '') {
						$btnIcon = 'icon: '.$btn['icon'].'; ';
					}

					if ($btn['onClick'] != '') {
						$btnOnClick = 'onClick: '.$btn['onClick'].'; ';
					}

					if ($btn['target'] != '') {
						$btnTarget = 'target: ' . $btn['target'] . ';';
					}

					$btnConfirm = '';
					//if ($btn['confirm'] <> '')
						//$btnConfirm = 'confirm: '.$btn['confirm'].'; ';
					if (is_object($o)) {
						$buttons.=$o->button("{".$btnIcon." ".$btnConfirm." ".$btnTarget. " " . $btnOnClick." type: $btnType; name: " . $btn['name'] . "; title: " . $btn['title'] .
											"; size: $this->size; style: $btnStyle; url: " . $btn['url'] . "; href: " . $btn['href'] ."}", $this->buttonsJavascript[$indx]);
					} else {
						$buttons.=gOutput::button("{".$btnIcon." ".$btnConfirm." ".$btnTarget. " " . $btnOnClick." type: $btnType; name: " . $btn['name'] . "; title: " . $btn['title'] .
											"; size: $this->size; style: $btnStyle; url: " . $btn['url'] . "; href: " . $btn['href'] ."}");
					}
				}
			}

			if ($this->jarr['style']!="inline") {
				if ($this->class_element) {
					$buttons = tagMe('div',$buttons,'class="'.$this->class_offset.' '.$this->class_element.'"');
				}

				$buttons = tagMe('div',$buttons,'class="form-group"');
			}
		}

		return $buttons;

	}


	function render(): string
	{
		global $htmlBuffer;
		$json=$this->formJson;
		$mtz=cssDecode($json);

		$url=$mtz['action'];
		$mtz['title']=gT($mtz['title']);
		$htmlBuffer.="<form style='padding: 0px; margin: 0px;' action='$url' name='".$mtz['name']."' method='POST'>\n";
		$htmlBuffer.="<table style='width: 100%; padding: 0px 4px 4px 4px;'>\n";
		$pri="";
		foreach ($this->fields as $fld) {
			$m=($fld);
			$extra=[];
			$m['id']=trim((string) $m['id']) !== "" ? trim((string) $m['id']) : trim((string) $m['name']);

			if (($pri === "") && ($m['type'] != 'hidden')) {
				$pri=$m['id'];
			}

			$defaultSize="10";
			$w="";
			if ($m['type']=="combo") {
				$w=" width='40%'";
			}

			if (($m['type'] != 'label') && ($m['type'] != 'hidden')) {
				$htmlBuffer.="<tr><td align='left' $w>".$m['fieldLabel']."</td><td align='left'>";
			}

			switch ($m['type'])
			{
				case 'show':
					$htmlBuffer.="<span style=\"font-weight: bold; \">".$m['value']."</span>";
					break;
				case 'text':
					$extra=[];
					if ($m['maxLength'] != "") {
						$extra[]=" maxlength='".$m['maxLength']."' ";
					}

					if (is_array($extra)) {
						$extra=implode(" ",$extra);
					}

					if ($m['inputType']=='password') {
						$htmlBuffer.="<input type='password' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' $extra>";
					} else {
						$htmlBuffer.="<input type='text' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' $extra>";
					}

					break;
				case 'barcode':
					$extra=[];
					if ($m['maxLength'] != "") {
						$extra[]=" maxlength='".$m['maxLength']."'";
					}

					if (is_array($extra)) {
						$extra=implode(" ",$extra);
					}

					if (gVar("global.hidebarcode")=="true") {
						$htmlBuffer.="<input type='password' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."'' style='width: 20px' $extra> [Leia Cód.Barras]";
					} else {
						$htmlBuffer.="<input type='text' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' $extra onBlur='this.value=this.value.toUpperCase();'>";
					}

					break;
				case 'upperText':
					$extra=[];
					if ($m['maxLength'] != "") {
						$extra[]=" maxlength='".$m['maxLength']."' size='".($m['maxLength']+3)."'";
					}

					if (is_array($extra)) {
						$extra=implode(" ",$extra);
					}

					if ($m['inputType']=='password') {
						$htmlBuffer.="<input type='password' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' $extra>";
					} else {
						$htmlBuffer.="<input type='text' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' $extra onBlur='this.value=this.value.toUpperCase();'>";
					}

					break;
				case 'number':
				case 'numeric':
				case 'integer':
					$extra=[];
					if ($m['maxLength'] != "") {
						$extra[]=" maxlength='".$m['maxLength']."' size='".($m['maxLength']+3)."'";
					}

					if (is_array($extra)) {
						$extra=implode(" ",$extra);
					}

					$htmlBuffer.="<input type='text' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' $extra onBlur='this.value=this.value.replace(\",\",\".\")'>";
					break;
				case 'date':
				case 'time':
					$extra=[];
					if (is_array($extra)) {
						$extra=implode(" ",$extra);
					}

					$htmlBuffer.="<input type='text' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' maxlength='8' size='10' $extra>";
					break;
				case 'datetime':
					$extra=[];
					if (is_array($extra)) {
						$extra=implode(" ",$extra);
					}

					$htmlBuffer.="<input type='text' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' maxlength='14'  size='16' $extra>";
					break;
				case 'checkbox':
					$extra=[];
					if (($m['value']=="1") || ($m['value']=="on") || ($m['value']=="true")) {
						$extra[]="checked";
					}

					if (is_array($extra)) {
						$extra=implode(" ",$extra);
					}

					$htmlBuffer.="<input type='checkbox' id='".$m['id']."' name='".$m['name']."' $extra>";
					break;
				case 'label':
					$txt=$m['text'];
					if ($m['html'] != "") {
						$txt = $m['html'];
					}

					$htmlBuffer.="<tr><td colspan='2' align='center'>".$txt."</td>";
					break;
				case 'hidden':
					$htmlBuffer.="<input type='hidden' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."'>";
					break;
				case 'combo':
					//$items=json_decode("{".$m['items']."}",true);
					//$items=json_decode("{".$m['items']."}",true);
					$items = is_array($extra) ? implode(" ",$extra) : jcombo2array($m['items']);
					$htmlBuffer.="<select id='".$m['id']."' name='".$m['name']."' $extra>";
					foreach ($items as $item => $val) {
						$sel="";
						//if ((trim($m['value'])==$val) )
						if (trim((string) $m['value']) == $item) {
							$sel=" selected";
						}

						$htmlBuffer.="<option $sel value='".$item."'>".autoencode($val)."</option>";
					}
					$htmlBuffer.="</select>";
					break;

			}
			$htmlBuffer.="</td></tr>\n";
		}
		if ($pri === "") {
			$pri="submitButton";
		}

		$htmlBuffer.="<tr><td align='center' colspan='2'>";
		if ($mtz['hiddenSubmit'] != "true") {
			$htmlBuffer.="<input id='submitButton' type='submit' value='".gT("Confirmar")."' onclick='this.disabled=true,this.form.submit();'>";
		}

		// Botoes
		$htmlBuffer.= $this->renderButtons();
		$htmlBuffer.="</td></tr>";
		$htmlBuffer.="  </table>\n";
		$htmlBuffer.="</form><br>\n";
		$htmlBuffer.="<script language='javascript'>\n";
		$htmlBuffer.="function focusIt(){\n	var el=document.getElementById('$pri');\n	el.focus();\n}\n";
		//$htmlBuffer.="onload = focusIt();";
		$htmlBuffer.="
					 var  oField;
					  if (document.forms.length > 0) {
					    for (var i=0; i < document.forms[0].elements.length; ++i) {
					      oField = document.forms[0].elements[i];
					      if ((oField.type != \"hidden\") && (oField.type != \"select-one\")) {
					        if (oField.disabled!=true) {
					          oField.focus();
					          break;
					        }
					      }
					    }
					  }			";
		$htmlBuffer.="focusIt()\n</script>\n";

		return $htmlBuffer;
	}

}
?>
