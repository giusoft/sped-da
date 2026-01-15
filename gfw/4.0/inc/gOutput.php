<?php
/**
 * Este arquivo contém a estrutura padrão para apresentação ao usuário de algum conteúdo
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */
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

define('SENHA_NAO_MODIFICADA', '**_nao_mudou_**');

class g_Stdout
{

	public $debug = false;
	public $json = '';
	public $jarr = '';
	public $useBuffer = true;
	public $buffer = '';
	public $bufferPre = '';
	public $bufferPos = '';
	public $bufferJavascript = '';
	public $n = '';
	public $fa = 'fa';
	public $indent = 0;
	public $page = '';
	public $editCount = 0;
	public $formCount = 0;
	public $iconFont = 'fa';
	public $useAngular = false;
	public $angularLinks;
	public $pageTitle = '';
	public $formId='form';
	public $PDFEnabled = false;
	public $XLSEnabled = false;
	public $DOCEnabled = false;
	public $CSVEnabled = false;
	public $XMLEnabled = false;
	public $TXTEnabled = false;
	public $PrintEnabled = false;
	public $renderType = '';
	public $renderFile = 'file.bin';
	public $backButton;
	public $bootstrapVersao = 3;
	public $bootstrapTags = array();

	/**
	 * Prepara ambiente para geração de conteúdo
	 * @author	giuliano
	 * @param $json Parâmetros em formato JSON:
	 * 			 debug: true ou false (compacta a saída de dados ou não)
	 * 			 onlyBody: gera somente o código do meio da página (entre a tag <body>)
	 * @version	4.0 01-12-2013 10:50
	 */
	function __construct($json = '')
	{
		$jarr = cssDecode($json);
		$this->json = $json;
		$this->jarr = $jarr;
		$this->page = $_SERVER["PHP_SELF"] . "?g=" . $_REQUEST['g'];
		// Ativando modo de depuração
		if (($jarr['debug'] == 'on') || ($jarr['debug'] == 'true')) {
		}
		$this->debug = true;
		$this->n = "\n";
		$this->indent = 0;
		if (gVar("lib.font_awesome")<>'')
		{
			$this->iconFont='fa';
		}
		$this->bootstrapTags['hidden-print'] = "hidden-print";
		$this->bootstrapTags['hidden-sm'] = "hidden-sm";
		$this->bootstrapTags['hidden-xs'] = "hidden-xs";
		$this->bootstrapTags['hidden-md'] = "hidden-md";
		$this->bootstrapTags['hidden']    = "hidden";
		$this->bootstrapTags['visible-print-inline']    = "visible-print-inline";

		if (strpos(gVar("lib.bootstrap"),"-4")!==false)
		{
			$this->bootstrapVersao=4;
			$this->bootstrapTags['hidden-print'] = "d-print-none";
			$this->bootstrapTags['hidden-sm'] = "d-sm-none";
			$this->bootstrapTags['hidden-xs'] = "d-xs-none";
			$this->bootstrapTags['hidden-md'] = "d-md-none";
			$this->bootstrapTags['hidden']    = "d-none";
			$this->bootstrapTags['visible-print-inline'] = "d-none d-print-inline";
		}
		//echo "<h1>Boostrap versão: ".$this->bootstrapVersao."</h1>";
	}

	/**
	 * Retorna o valor do parâmetro fornecido se debug=true
	 * @author	giuliano
	 * @param string $msg Mensagem
	 * @version	4.0 01-12-2013 10:50
	 */
	function d($msg)
	{
		if ($this->debug) {
			return($msg);
		}
	}

	/**
	 * Gera saída na tela ou no buffer
	 * @author	giuliano
	 * @param string $content Dados
	 * @param string $location gLOC_PRE: no início da página, gLOC_INLINE: no meio, gLOC_POS: no final da página
	 * @param string $indent Se 1, da próxima vez indenta pra direita, se -1, remove um nível de indentação,
	 * 								 Se 0, nada faz. 999 ignora identação.
	 * @version	4.0 01-12-2013 10:50
	 */
	function out($content, $location = gLOC_INLINE, $indent = 0)
	{
		$pre = "";
		if ($this->debug) {
			if ($indent <> 999) {
				for ($a = 0; $a < $this->indent; $a++) {
					$pre.=" ";
				}
			}
		}
		if ($this->useBuffer) {
			if (is_array($content)) {
				if (strpos($this->bufferPre, $content[0]) === false) {
					$this->bufferPre.=$pre . $content[0] . $this->n;
				}
				$this->buffer.=$pre . $content[1] . $this->n;
				if (strpos($this->bufferPos, $content[2]) === false) {
					$this->bufferPos.=$pre . $content[2] . $this->n;
				}
				foreach ($content[3] as $js) {
					$this->bufferJavascript[] = $js;
				}
			} else {
				switch($location) {
					case gLOC_PRE:
						$this->bufferPre.=$pre . $content . $this->n;
						break;
					case gLOC_POS:
						$this->bufferPos.=$pre . $content . $this->n;
						break;
					default: // gLOC_INLINE
						$this->buffer.=$pre . $content . $this->n;
				}
			}
		} else {
			if (is_array($content)) {
				foreach ($content as $c) {
					echo $pre . $c;
				}
			} else {
				echo $pre . $content;
			}
		}
		$this->indent+=$indent * 3;
	}

	/**
	 * Acumula código javscript e apresenta todos juntos no final da página
	 * @author	giuliano
	 * @param string $js Código javascript
	 * @version	4.0 01-12-2013 10:50
	 */
	function addJavascript($js)
	{
		$this->bufferJavascript[] = $js;
	}

	/**
	 * Exibe um <textarea> como um editor Wysiwyg completo
	 * @param type $json Parâmetros: {name: nome; height: altura; value: conteudo; style: [true,false]; align: [true,false]; indent: [true,false]}
	 * @return type
	 */
	function wysiwyg($json, $complexValue="", $extraButtons="")
	{
		global $http_lib;

		$this->editCount++;
		$jarr = cssDecode($json);
		$content = '';
		$indent = true;
		$style = true;
		$align = true;
		$h1 = true;
		$h2 = true;
		$h3 = true;
		$h4 = true;
		$small = true;

		$id = 'gEditor' . $this->editCount;
		$formId='form';
		if (!empty($jarr["formId"]))
			$formId=$jarr["formId"];

		$height = '150';
		$background = '#f0f0f0';
		$name = "gTextArea" . $this->editCount;
		$code = false;
		$alerts = false;
		$image = true;
		if ($jarr["name"] <> '')
			$name = $jarr["name"];
		if ($jarr["id"] <> '')
			$id = $jarr["id"];
		if ($jarr['value'] <> '')
			$content = $jarr['value'];
		if ($complexValue<>'')
			$content = $complexValue;
		if ($jarr["height"] <> '')
			$height = intval($jarr["height"]);
		if ($jarr["background"] <> '')
			$background = $jarr["background"];
		if ($jarr["indent"] == 'false')
			$indent = false;
		if ($jarr["style"] == 'false')
			$style = false;
		if ($jarr["align"] == 'false')
			$align = false;
		if ($jarr["image"] == 'false')
			$image = false;
		if ($jarr["h1"] == 'false')
			$h1 = false;
		if ($jarr["h2"] == 'false')
			$h2 = false;
		if ($jarr["h3"] == 'false')
			$h3 = false;
		if ($jarr["h4"] == 'false')
			$h4 = false;
		if ($jarr["small"] == 'false')
			$small = false;
		if ($jarr["code"] == 'true')
			$code = true;
		if ($jarr["alerts"] == 'true')
			$alerts = true;

		$style = '	max-height: ' . $height . 'px; height: ' . $height . 'px; display: block; background-color: ' . $background . '; border-collapse: separate; border: 1px solid rgb(204, 204, 204); padding: 4px; box-sizing: content-box; -webkit-box-shadow: rgba(0, 0, 0, 0.0745098) 0px 1px 1px 0px inset; box-shadow: rgba(0, 0, 0, 0.0745098) 0px 1px 1px 0px inset;	border-top-right-radius: 3px; border-bottom-right-radius: 3px;	border-bottom-left-radius: 3px; border-top-left-radius: 3px; overflow: scroll; outline: none;';

		$wys = $http_lib . gVar("lib.bootstrap_addons") . 'bootstrap-wysiwyg-master/';


		$fontVersion=(gVar('lib.font_awesome5')<>'') ? 5 : 4;

		// Toolbar
		$sai.='<div id="alerts"></div><div id="modal"></div>';
		$sai.='<div style="display: inline; float: left; padding-top: 4px; padding-right: 4px"><a class="btn btn-default btn-sm" data-original-title="Source" title="Source" onClick="viewsource()">'.$this->chooseIcon('power-off').'</a></div>';
		$sai.='<div class="btn-toolbar" data-role="editor-toolbar" id="'. $id.'_toolbar" data-target="#' . $id . '_div" style="padding-bottom: 4px;">';
		$sai.='    <div class="btn-group" style="padding-top: 4px">';
		$sai.='        <a class="btn btn-default btn-sm" data-edit="undo" data-original-title="Undo (Ctrl/Cmd+Z)" title="Undo">'.$this->chooseIcon('undo').'</a>';
		if ($fontVersion==5)
			$sai.='        <a class="btn btn-default btn-sm" data-edit="redo" data-original-title="Redo (Ctrl/Cmd+Y)" title="Redo">'.$this->chooseIcon('redo').'</i></a>';
		else
			$sai.='        <a class="btn btn-default btn-sm" data-edit="redo" data-original-title="Redo (Ctrl/Cmd+Y)" title="Redo">'.$this->chooseIcon('rotate-right').'</a>';
		$sai.='        <a class="btn btn-default btn-sm" data-edit="removeFormat" data-original-title="Remove format" title="Remove format">'.$this->chooseIcon('eraser').'</a>';
		$sai.='    </div>';
		if ($style) {
			$sai.='    <div class="btn-group" style="padding-top: 4px">';
			$sai.='        <a class="btn btn-default btn-sm" data-edit="formatBlock p" data-original-title="Paragraph" title="Paragraph">P</a>';
			if ($h1)
				$sai.='        <a class="btn btn-default btn-sm" data-edit="formatBlock h2" data-original-title="Header" title="Header">H1</a>';
			if ($h2)
				$sai.='        <a class="btn btn-default btn-sm" data-edit="formatBlock h3" data-original-title="2nd-level header" title="2nd-level header">H2</a>';
			if ($h3)
				$sai.='        <a class="btn btn-default btn-sm" data-edit="formatBlock h4" data-original-title="3rd-level header" title="3rd-level header">H3</a>';
			if ($h4)
				$sai.='        <a class="btn btn-default btn-sm" data-edit="formatBlock h5" data-original-title="4rd-level header" title="4rd-level header">H4</a>';
			if ($small)
				$sai.='        <a class="btn btn-default btn-sm" data-edit="decreaseFontSize" data-original-title="Small font" title="Small font">'.$this->chooseIcon('compress').'</i></a>';
			if ($code)
				$sai.='        <a class="btn btn-default btn-sm" data-edit="formatBlock pre" data-original-title="Source code" title="Source code">'.$this->chooseIcon('code').'</a>';
			if ($alerts)
			{
				$sai.='        <a class="btn btn-default btn-sm" data-edit="insertHTML <div class=\'alert alert-success\' role=\'alert\'>Success</div>" data-original-title="Success alert" title="Success alert">'.$this->chooseIcon('check-circle').'</a>';
				$sai.='        <a class="btn btn-default btn-sm" data-edit="insertHTML <div class=\'alert alert-info\' role=\'alert\'>Info</div>" data-original-title="Info alert" title="Info alert">'.$this->chooseIcon('info-circle').'</i></a>';
				$sai.='        <a class="btn btn-default btn-sm" data-edit="insertHTML <div class=\'alert alert-warning\' role=\'alert\'>Warning</div>" data-original-title="Warning alert" title="Warning alert">'.$this->chooseIcon('exclamation-circle').'</a>';
				$sai.='        <a class="btn btn-default btn-sm" data-edit="insertHTML <div class=\'alert alert-danger\' role=\'alert\'>Danger</div>" data-original-title="Danger alert" title="Danger alert">'.$this->chooseIcon('bolt').'</i></a>';
			}

			$sai.='    </div>';
		}

		$sai.='    <div class="btn-group" style="padding-top: 4px">';
		$sai.='        <a class="btn btn-default btn-sm" data-edit="bold" data-original-title="Bold (Ctrl/Cmd+B)" title="Bold">'.$this->chooseIcon('bold').'</a>';
		$sai.='        <a class="btn btn-default btn-sm" data-edit="italic" data-original-title="Italic (Ctrl/Cmd+I)" title="Italic">'.$this->chooseIcon('italic').'</a>';
		$sai.='        <a class="btn btn-default btn-sm" data-edit="underline" data-original-title="Underline (Ctrl/Cmd+U)" title="Underline">'.$this->chooseIcon('underline').'</a>';
		$sai.='    </div>';

		$sai.='    <div class="btn-group" style="padding-top: 4px">';
		$sai.='        <a class="btn btn-default btn-sm" data-edit="insertunorderedlist" data-original-title="Bullet list" title="Bullet list">'.$this->chooseIcon('list-ul').'</a>';
		$sai.='        <a class="btn btn-default btn-sm" data-edit="insertorderedlist" data-original-title="Number list" title="Number list">'.$this->chooseIcon('list-ol').'</i></a>';
		if ($indent) {
			$sai.='        <a class="btn btn-default btn-sm" data-edit="outdent" data-original-title="Reduce indent (Shift+Tab)" title="Reduce indent">'.$this->chooseIcon('outdent').'</a>';
			$sai.='        <a class="btn btn-default btn-sm" data-edit="indent" data-original-title="Indent (Tab)" title="Indent">'.$this->chooseIcon('indent').'</a>';

		}
		$sai.='    </div>';

		if ($align) {
			$sai.='    <div class="btn-group" style="padding-top: 4px">';
			$sai.='        <a class="btn btn-default btn-sm" data-edit="justifyleft" data-original-title="Align Left (Ctrl/Cmd+L)" title="Align left">'.$this->chooseIcon('align-left').'</a>';
			$sai.='        <a class="btn btn-default btn-sm" data-edit="justifycenter" data-original-title="Center (Ctrl/Cmd+E)" title="Center">'.$this->chooseIcon('align-center').'</a>';
			$sai.='        <a class="btn btn-default btn-sm" data-edit="justifyright" data-original-title="Align Right (Ctrl/Cmd+R)" title="Align right">'.$this->chooseIcon('align-right').'</a>';
			$sai.='        <a class="btn btn-default btn-sm" data-edit="justifyfull" data-original-title="Justify (Ctrl/Cmd+J)" title="Justify">'.$this->chooseIcon('align-justify').'</a>';
			$sai.='        <a class="btn btn-default btn-sm" data-edit="insertHorizontalRule" data-original-title="Horizontal rule" title="Horizontal rule">'.$this->chooseIcon('arrows-h').'</a>';

			$sai.='    </div>';
		}

		if ($image)
		{
			$sai.='    <div class="btn-group" style="padding-top: 4px">';
			$sai.='        <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" data-original-title="Hyperlink" title="Hyperlink">'.$this->chooseIcon('link').'</a>';
			$sai.='        <div class="dropdown-menu input-append">';
			$sai.='            <input class="span2" placeholder="URL" type="text" data-edit="createLink">';
			$sai.='            <button class="btn" type="button">Add</button>';
			$sai.='        </div>';
			$sai.='        <a class="btn btn-default btn-sm" data-edit="unlink" data-original-title="Remove Hyperlink" title="Remove hyperlink">'.$this->chooseIcon('unlink').'</a>';
			$sai.='    </div>';

			$sai.='    <div class="btn-group" style="padding-top: 4px">';
			////$sai.='        <a class="btn btn-default btn-sm" id="pictureBtn" data-original-title="Insert picture (or just drag &amp; drop)" title="Insert image" href="javascript: $(\'#pictureChooser\').click();"><i class="' . $this->fa . ' ' . $this->iconFont . '-picture"></i><input type="file" data-role="magic-overlay" data-target="#pictureBtn" data-edit="insertImage" id="pictureChooser" style="visibility: hidden; position: absolute;"></a>';
			//$sai.='			<a id="pictureBtn" class="btn btn-default btn-sm" title="" data-original-title="Insert picture (or just drag & drop)"><i class="' . $this->fa . ' ' . $this->iconFont . '-picture-o"></i></a><input type="file" data-edit="insertImage" data-target="#pictureBtn" data-role="magic-overlay">';
			$sai.='        <a class="btn btn-default btn-sm" data-edit="insertHTML <table class=\'table table-bordered\'><thead><tr><td></td><td></td></tr></thead><tbody><tr><td></td><td></td></tr></tbody></table>" data-original-title="Table" title="Table">'.$this->chooseIcon('table').'</a>';
			$sai.='    </div>';
		}
		if ($extraButtons<>'')
		{
			$sai.='    <div class="btn-group" style="padding-top: 4px">';
			$sai.=$extraButtons;
			$sai.='    </div>';
		}
		$sai.='    <input type="text" data-edit="inserttext" id="voiceBtn" x-webkit-speech="" style="margin-left: 18px; width: 12px; color: transparent; background-color: transparent; transform: scale(2.0, 2.0); -webkit-transform: scale(2.0, 2.0); -moz-transform: scale(2.0, 2.0); border: transparent; cursor: pointer; box-shadow: none; -webkit-box-shadow: none;">';
		$sai.='</div>';

		if ($jarr['base64']=='true')
			$content=base64_decode($content);
		$sai.='<div id="' . $id . '_div" style="' . $style . '">' . $content . '</div><input type="hidden" id="' . $id . '" name="' . $name . '" value=\'' . $content . '\'> ';
//document.getElementById(\''. $id.'\').value=
		$sai.='<textarea onBlur="document.getElementById(\''. $id.'_div\').innerHTML=this.value;" id="' . $id . '_area" style="' . $style . '; position:absolute; left: 14px; top: 40px; width: 95%; visibility: hidden; z-index: 100">' . $content . '</textarea>';
		if ($this->editCount == 1) {
			$this->out('<script src="' . $wys . 'external/jquery.hotkeys.js"></script>' . $this->n, gLOC_POS);
			$this->out('<script src="' . $wys . 'bootstrap-wysiwyg.js"></script>' . $this->n, gLOC_POS);
			$js = "


			function showErrorAlert (reason, detail) {
				$('#modal .modal-title').text(reason);
				$('#modal .modal-body').text(detail);
				$('#modal').modal('show');
			};

			function execCommandOnElement(el, commandName, value) {
			if (typeof value == \"undefined\") {
				value = null;
			}

			if (typeof window.getSelection != \"undefined\") {
				// Non-IE case
				var sel = window.getSelection();

				// Save the current selection
				var savedRanges = [];
				for (var i = 0, len = sel.rangeCount; i < len; ++i) {
					savedRanges[i] = sel.getRangeAt(i).cloneRange();
			    }

			    // Temporarily enable designMode so that
			    // document.execCommand() will work
			    document.designMode = \"on\";

			    // Select the element's content
			    sel = window.getSelection();
			    var range = document.createRange();
			    range.selectNodeContents(el);
			    sel.removeAllRanges();
			    sel.addRange(range);

			    // Execute the command
			    document.execCommand(commandName, false, value);

			    // Disable designMode
			    document.designMode = \"off\";

			    // Restore the previous selection
			    sel = window.getSelection();
			    sel.removeAllRanges();
			    for (var i = 0, len = savedRanges.length; i < len; ++i) {
					sel.addRange(savedRanges[i]);
			    }
			} else if (typeof document.body.createTextRange != \"undefined\") {
			    // IE case
			    var textRange = document.body.createTextRange();
			    textRange.moveToElementText(el);
			    textRange.execCommand(commandName, false, value);
			}
			}

			var source=true;
			function viewsource()
			{
				var html;
				if (source) {

					document.getElementById(\"". $id."_div\").style.visibility=\"hidden\";
					document.getElementById(\"". $id."_area\").style.visibility=\"visible\";
					document.getElementById(\"". $id."_toolbar\").style.visibility=\"hidden\";
					document.getElementById(\"". $id."_area\").innerHTML=document.getElementById(\"". $id."_div\").innerHTML;
				} else {
					var editor=document.getElementById(\"". $id."_div\");
					execCommandOnElement(editor, \"selectAll\");
					execCommandOnElement(editor, \"bold\");
					execCommandOnElement(editor, \"insertHTML\",document.getElementById(\"". $id."_area\").value);
					document.getElementById(\"". $id."_div\").style.visibility=\"visible\";
					document.getElementById(\"". $id."_area\").style.visibility=\"hidden\";
					document.getElementById(\"". $id."_toolbar\").style.visibility=\"visible\";
				}
				source=!source;
			}

			$( document ).ready( function() {

			$('a[title]').tooltip({container:'body'});

			$('.dropdown-menu input').click(function() {
				return false;
			}).change(function () {
				$(this).parent('.dropdown-menu').siblings('.dropdown-toggle').dropdown('toggle');
			}).keydown('esc', function () {
				this.value='';$(this).change();
			});

			$('[data-role=magic-overlay]').each(function () {
				var overlay = $(this), target = $(overlay.data('target'));
				overlay.css('opacity', 0).css('position', 'absolute').offset(target.offset()).width(target.outerWidth()).height(target.outerHeight());
			});

			$('#" . $id . "_div').wysiwyg({
			hotKeys: {
                'ctrl+b meta+b': 'bold',
                'ctrl+i meta+i': 'italic',
                'ctrl+u meta+u': 'underline',
                'ctrl+z meta+z': 'undo',
                'ctrl+y meta+y meta+shift+z': 'redo',
                'meta+right':'end',
                'meta+left':'home'
            },
            dragAndDropImages: true,
				fileUploadError: showErrorAlert,
				enableInlineTableEditing: true,
				uploadScript: '/admin/upload.php'
			});

			$('" . '#' . $formId . "').submit(function(event) {
			    $('#" . $id . "').val($('#" . $id . "_div').cleanHtml());
			});

			});
			";
			$this->addJavascript($js);
		}

		return($sai);
	}

	function chooseIcon($icon, $iconFont="fa", $size="normal")
	{
		if ($icon <> '') {
			// Compatibilidade retroativa gFW 3.0
			$icons = '';
			$icons['a0001'] = 'stop';
			$icons['a0002'] = 'ok';
			$icons['a0003'] = 'trash';
			$icons['a0004'] = 'arrow-left';
			$icons['a0005'] = 'arrow-right';
			$icons['a0006'] = 'arrow-up';
			$icons['a0007'] = 'arrow-down';
			$icons['a0008'] = 'minus';
			$icons['a0009'] = 'plus';
			$icons['a0010'] = 'user';
			$icons['a0011'] = 'search';
			$icons['a0012'] = 'comment';
			$icons['a0013'] = 'stats';
			$icons['a0014'] = 'record';
			$icons['a0015'] = 'star';
			$icons['a0016'] = 'home';
			$icons['a0017'] = 'off';
			$icons['a0018'] = 'question-sign';
			$icons['a0019'] = 'exclamation-sign';
			$icons['a0020'] = 'usd';
			$icons['a0021'] = 'check';
			$icons['a0022'] = 'unchecked';
			$icons['a0023'] = 'repeat';
			$icons['a0024'] = 'barcode';
			$icons['a0025'] = 'globe';
			$icons['a0026'] = 'thumbs-up';
			$icons['a0027'] = 'thumbs-down';
			$icons['a0028'] = 'th-large';
			$icons['a0029'] = 'qr-code';
			$icons['a0030'] = 'star-empty';
			$icons['a0031'] = 'paperclip';
			$icons['a0032'] = 'folder-open';
			$icons['a0033'] = 'print';
			$icons['a0034'] = 'zoom-out';
			$icons['a0035'] = 'zoom-in';
			$icons['a0036'] = 'pencil';
			$icons['a0037'] = 'list-alt';
			$icons['a0038'] = 'th';
			$icons['a0039'] = 'file';
			$icons['a0040'] = 'save';
			if ($iconFont == 'fa')
			{
				$icons['trash']		= 'trash';
				$icons['off']			= 'power-off';
				$icons['off']			= 'power-off';
				$icons['file']			= 'file';
				$icons['bell']			= 'bell';
				$icons['bookmark']	= 'bookmark';
				$icons['files']		= 'files';
				$icons['clock']		= 'clock';
//				$icons['bar-chart']	= 'bar-chart-o';
			}
			if ($icons[$icon] <> '')
				$icon = $icons[$icon];
			$fa = 'fa';
			if (gVar("lib.font_awesome5")<>'')
			{
				$fa = 'fal';
			}
			$fa_size = '';
			$br = "";
			switch ($size)
			{
				case 'tiny':
					$fa_size=' fa-sm';
					break;
				case 'big':
				case 'large':
					$fa_size=' fa-3x';
					$br = "<br><br>";
					break;

			}
			$icon = '<i class="'.$fa. ' ' . $iconFont . ' ' . $iconFont . '-' . $icon . ' fa-fw'.$fa_size.'"></i> '.$br;
		}
		return($icon);
	}
}

/**
 * Classe responsável pela estrutura básica da apresentação visual
 * @package	gOutput
 * @author	Giuliano Nascimento <giusoft@hotmail.com>
 * @version	4.0 01-12-2013 10:50
 */
class g_Output extends g_Stdout
{

	public $onlyBody = false;
	public $tableFirstRow = true;
	public $tableSegment = '';
	public $rowLayout = '';
	public $pageData = '';
	public $googleKey = 'AIzaSyAFvZGR9LxG6MLGGxgYc47Jui-bclt6SWY';
	public $modalCount = 0;
	public $dropdownCount = 0;
	public $navCount = 0;
	public $tooltipA = false;
	public $tooltipButton = false;
	public $tooltipAcronym = false;
	public $tooltipInput = false;
	public $fontAwesome5 = false;
	public $tableSummaries;
	public $tableGroups;
	public $tableTotals;
	public $tableValues;
	public $tableTotalValues;
	public $lastColMatrix;
	public $minify = false;

	//Atributos da ABA
	private $gTabsId = 0;
	private $gTabs = array();

	private $initializeMaps = array();

	/**
	 * Prepara ambiente para geração de conteúdo
	 * @author	giuliano
	 * @param $json Parâmetros em formato JSON:
	 * 			 debug: true ou false (compacta a saída de dados ou não)
	 * 			 onlyBody: gera somente o código do meio da página (entre a tag <body>)
	 * @version	4.0 01-12-2013 10:50
	 */
	function __construct($json = '')
	{
		global $gFW4,$http, $http_lib, $http_css, $http_inc, $http_img, $gDevice;
		parent::__construct($json);
		$jarr = $this->jarr;
		$this->indent = 6;
		$this->backButton = $this->button('{title: Voltar; url: javascript:history.back(-1)}');



		if (gVar("lib.angularjs")<> '')
		{
			$this->useAngular=true;
		}
		// Ativando modo de depuração
		if (($jarr['onlyBody'] == 'on') || ($jarr['onlyBody'] == 'true')) {
			$this->onlyBody = true;
		}

		if (!$this->onlyBody) {
			$style = "styleWeb.css";
			if ($gDevice == "mobile") {
				$style = "styleIphone.css";
			}
			$i18n = strtolower(gVar('global.language'));
			// Bootstrap é requisito fundamental - tem que estar no setup.php
			$bootstrapPath = $http_lib . gVar("lib.bootstrap");
			$bootstrapMin = $http_lib . gVar("lib.bootstrap") . 'css/bootstrap.min.css';

			$bootstrapThemePath = $bootstrapMin;
			$jasny = $http_lib . gVar("lib.jasny");


			$parsley = $http_lib . gVar("lib.parsley");
			$jqueryPath = $http_lib . gVar("lib.jquery");
			if ($jarr['fakeCrop']=="on")
				$jqueryFakecrop = $http_lib . gVar("lib.jquery_fakecrop");
			//if ($jarr['facyBox']=="on")
                        if(gVar("lib.jquery_fancybox")<>"")
				$jqueryFancybox = $http_lib . gVar("lib.jquery_fancybox");
			if ($jarr['jCarousel']=="on")
				$jqueryJcarousel = $http_lib . gVar("lib.jquery_jcarousel");
			$angularjs = '';
			if (gVar("lib.angularjs")<> '')
			{
				$this->useAngular=true;
				$angularjs = $http_lib . gVar("lib.angularjs");
			}


			$sortable = $http_lib . gVar("lib.sortable");
			$ticker = $http_lib . gVar("lib.ticker");
			$fontAwesome = $http_lib . gVar("lib.font_awesome");
			$fontAwesome5 = $http_lib . gVar("lib.font_awesome5");
			$jssor = $http_lib . gVar("lib.jssor")."js/jssor.slider.min.js";

			//$combogrid = $http_lib . gVar("lib.jquery_combogrid");

			$theme = gVar("global.theme");
			if ($theme <> "") {
				$bootstrapThemePath = $http_lib . gVar("lib.bootstrap_themes") . $theme . '.min.css';
			}
			$this->out(tagMe('title', gVar("global.site")), gLOC_PRE);

			// META --------------------
			//$this->out('<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">',gLOC_PRE);
			$this->out('<meta name="viewport" content="width=device-width, initial-scale=1.0">', gLOC_PRE);
			$this->out("<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . strtoupper(gVar("global.charset")) . "\">", gLOC_PRE);
			if (gVar("global.description")<>'')
				$this->out('<meta content= "'.gVar("global.description").'" name="description">', gLOC_PRE);
			if (gVar("global.keywords")<>'')
				$this->out('<meta content= "'.gVar("global.keywords").'" name="keywords">', gLOC_PRE);

			// PRE ---------------------
			if (gVar("global.icon") <> "") {
				$this->out("<link rel='shortcut icon' href='" . $http_img . "" . gVar("global.icon") . "'>\n", gLOC_PRE);
			}
			// if (stripos($bootstrapMin, "bootstrap-3")!==false)
			// {
			// 	$this->out('<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">', gLOC_PRE);
			// } else {
				$this->out('<link href="' . $bootstrapMin . '" rel="stylesheet">', gLOC_PRE);
			// }
			if ($bootstrapMin<>$bootstrapThemePath)
				$this->out('<link href="' . $bootstrapThemePath . '" rel="stylesheet">', gLOC_PRE);
			if (gVar("lib.jasny")<>'' && $this->bootstrapVersao==3) {
				$this->out('<link href="' . $jasny . 'css/jasny-bootstrap.css" rel="stylesheet">', gLOC_PRE);
			}
			if (gVar("lib.sortable")<>'')
				$this->out('<link href="' . $sortable . 'css/sortable-theme-bootstrap.css" rel="stylesheet">', gLOC_PRE);

			//$this->out('<link href="' . $combogrid . 'css/smoothness/jquery.ui.combogrid.css" rel="stylesheet">', gLOC_PRE);
			//$this->out('<link href="' . $combogrid . 'css/smoothness/jquery-ui-1.10.1.custom.css" rel="stylesheet">', gLOC_PRE);
			if (gVar("lib.ticker")<>'')
				$this->out('<link href="' . $ticker . 'li-scroller.css" rel="stylesheet">', gLOC_PRE);
			if (!isset($gFW4) && $this->bootstrapVersao==3)
				$this->out('<link href="' . $http_css . $style . '" rel="stylesheet">', gLOC_PRE);
			if (gVar("lib.font_awesome5")<>'')
			{
				$this->fontAwesome5=true;
				$this->fa = 'fal';
				// $this->out('<script defer src="'.$fontAwesome5.'/js/packs/solid.js"></script>', gLOC_PRE);
				// $this->out('<script defer src="'.$fontAwesome5.'/js/packs/brands.js"></script>', gLOC_PRE);
				// $this->out('<script defer src="'.$fontAwesome5.'/js/v4-shims.js"></script>', gLOC_PRE);
				// $this->out('<script defer src="'.$fontAwesome5.'/js/fontawesome.js"></script>', gLOC_PRE);

				if (strpos(gVar("lib.font_awesome5"),'web')!==false)
				{
					$this->out('<script defer src="'.$fontAwesome5.'/js/all.js"></script>', gLOC_PRE);
				} else {
					$this->out('<script defer src="'.$fontAwesome5.'/svg-with-js/js/fa-brands.min.js"></script>', gLOC_PRE);
					$this->out('<script defer src="'.$fontAwesome5.'/svg-with-js/js/fa-light.min.js"></script>', gLOC_PRE);
					$this->out('<script defer src="'.$fontAwesome5.'/svg-with-js/js/fa-v4-shims.min.js"></script>', gLOC_PRE);
					$this->out('<script src="'.$fontAwesome5.'/svg-with-js/js/fontawesome.min.js"></script>', gLOC_PRE);
				}


			} else {
				if (gVar("lib.font_awesome")<>'')
				{
					if (strpos($_SERVER['HTTP_USER_AGENT'],"Safari")!==false)
					{
						$this->out('<link href="' . $fontAwesome . 'css/font-awesome.min.css?v='.session_id().'" rel="stylesheet">', gLOC_PRE);
					} else {
						$this->out('<link href="' . $fontAwesome . 'css/font-awesome.min.css" rel="stylesheet">', gLOC_PRE);
					}
				}
			}
			if ($angularjs)
			{
				$this->out('<link href="' . $angularjs . 'loading-bar.css" rel="stylesheet">', gLOC_PRE);
			}
			$this->out('<link href="' . $http_lib . gVar("lib.bootstrap_addons") . 'bootstrap-toggle-master/' . 'css/bootstrap-toggle.min.css" rel="stylesheet">', gLOC_PRE);

			$this->out('<!--[if lt IE 9]>', gLOC_PRE, 1);
			$this->out('<script src="http://cdnjs.cloudflare.com/ajax/libs/es5-shim/2.0.8/es5-shim.min.js"></script>',gLOC_PRE);
			$this->out('<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>', gLOC_PRE);
			$this->out('<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>', gLOC_PRE, -1);
			$this->out('<![endif]-->', gLOC_PRE);

			// POS ---------------------
			$this->indent = 6;
			$this->out('<script src="' . $jqueryPath . '"></script>', gLOC_POS);
			if ($this->bootstrapVersao==3)
			{
				$this->out('<script src="' . $bootstrapPath . 'js/bootstrap.min.js"></script>', gLOC_POS);
			} else {
				$this->out('<script src="' . $bootstrapPath . 'js/bootstrap.bundle.min.js"></script>', gLOC_POS);
			}


			if (gVar("lib.jasny")<>'') {
				$this->out('<script src="' . $jasny . 'js/jasny-bootstrap.min.js"></script>', gLOC_POS);
			}
            if (gVar("lib.treeview") <> '') {
                $this->out('<script src="' . $http_lib . gVar("lib.treeview") . 'src/js/bootstrap-treeview.js"></script>', gLOC_POS);
            }
			// JqueryUi
			if(gVar("lib.jquery_ui") <> ''){
				$this->out('<link href="'.$http_lib.gVar("lib.jquery_ui").'themes/base/minified/jquery-ui.min.css" rel="stylesheet" type="text/css">',gLOC_PRE);
				$this->out('<script src="'.$http_lib.gVar("lib.jquery_ui").'ui/minified/jquery-ui.min.js"></script>',gLOC_POS);
			}
			//jQuery Mask
			if(gVar("lib.jquery_mask") <> ""){
				$this->out('<script src="' . $http_lib . gVar("lib.jquery_mask") . 'jquery.mask.min.js"></script>', gLOC_POS);
			}
			// Plugin fancybox
			if ($jqueryFancybox<>"")
			{
				$this->out('<script src="' . $jqueryFancybox . 'jquery.fancybox.js"></script>', gLOC_POS);
				$this->out('<script src="' . $jqueryFancybox . 'default.js"></script>', gLOC_POS);
				$this->out('<link href="' . $jqueryFancybox . 'jquery.fancybox.css" rel="stylesheet">', gLOC_PRE);
			}
			if ($jqueryJcarousel)
			{
				$this->out('<script src="' . $jqueryJcarousel . 'jquery.jcarousel.min.js"></script>', gLOC_POS);
				$this->out('<link href="' . $jqueryJcarousel . 'jquery.jcarousel.css" rel="stylesheet">', gLOC_PRE);
			}
			if ($jqueryFakecrop)
			{
				$this->out('<script src="' . $jqueryFakecrop . 'jquery.fakecrop.js"></script>', gLOC_POS);
			}
			if (gVar("lib.jssor")<>'')
				$this->out('<script src="' . $jssor . '"></script>', gLOC_POS);
			if (gVar("lib.sortable")<>'')
				$this->out('<script src="' . $sortable . 'js/sortable.min.js"></script>', gLOC_POS);
			if (gVar("lib.ticker")<>'')
				$this->out('<script src="' . $ticker . 'jquery.li-scroller.1.0.js"></script>', gLOC_POS);
			if (gVar("lib.parsley")<>'')
			{
				$this->out('<script src="' . $parsley . 'i18n/messages.' . $i18n . '.js"></script>', gLOC_POS);
				$this->out('<script src="' . $parsley . 'parsley.js"></script>', gLOC_POS);
			}
			$this->out('<script src="' . $http_inc . 'gFunctions.js"></script>', gLOC_POS);
			if (file_exists($gPathDefault."pub/js/script.js"))
			{
				$this->out('<script src="' . $http . 'pub/js/script.js"></script>', gLOC_POS);
			}
			if ($angularjs)
			{
				$this->out('<script src="' . $angularjs . 'angular.min.js"></script>', gLOC_POS);
				$this->out('<script src="' . $angularjs . 'angular-route.min.js"></script>', gLOC_POS);
				$this->out('<script src="' . $angularjs . 'angular-resource.min.js"></script>', gLOC_POS);
				$this->out('<script src="' . $angularjs . 'angular-animate.min.js"></script>', gLOC_POS);
				$this->out('<script src="' . $angularjs . 'loading-bar.js"></script>', gLOC_POS);
			}
			$this->out('<script src="' . $http_lib . gVar("lib.bootstrap_addons") . 'bootstrap-toggle-master/' . 'js/bootstrap-toggle.min.js"></script>', gLOC_POS);
			if (gVar("lib.bootbox")<>'') {
				$this->out('<script src="' . $http_lib . gVar("lib.bootbox") . 'bootbox.min.js"></script>', gLOC_POS);
			}

			//$this->out('<script src="' . $combogrid . 'jquery-ui-1.10.4/jquery-ui-1.10.4.custom.min.js"></script>', gLOC_POS);
			//$this->out('<script src="' . $combogrid . 'plugin/jquery.widget.js"></script>', gLOC_POS);
			//$this->out('<script src="' . $combogrid . 'plugin/jquery.ui.combogrid-1.6.3.js"></script>', gLOC_POS);
			//$this->out('<script src="' . $combogrid . 'plugin/jquery.i18n.properties-1.0.9.js"></script>', gLOC_POS);
		}
	}

	/**
	 * Gera saída de um container HTML/Bootstrap na tela ou no buffer
	 * @author	giuliano
	 * @param string $content Dados
	 * @version	4.0 01-12-2013 10:50
	 */
	function container($content)
	{
		if (is_array($content)) {
			$row = $content[1] . $this->n;
			$row = tagMe('div', $row, 'class="container"') . $this->n;
			$content[1] = $row;
			$this->out($content);
		} else {
			$row = $content . $this->n;
			$row = tagMe('div', $row, 'class="container"') . $this->n;
			$this->out($row, gLOC_INLINE, 999);
		}
	}

	/**
	 * Permite definir um layout personalizado para as colunas de uma linha
	 * @author	giuliano
	 * @param string $content Classe bootstrap para coluna 1
	 * @param string $content Classe bootstrap para coluna 2
	 * @param string $content Classe bootstrap para coluna n...
	 * @version	4.0 01-12-2013 10:50
	 */
	function setRowLayout()
	{
		$this->rowLayout = '';
		$numargs = func_num_args();
		$arg_list = func_get_args();
		if ($numargs > 0) {
			if ($numargs == 1) {
				$arg = $arg_list[0];
				foreach ($arg as $argValue) {
					$this->rowLayout[] = $argValue;
				}
			} else {
				for ($i = 0; $i < $numargs; $i++) {
					$this->rowLayout[] = $arg_list[$i];
				}
			}
		}
	}

	/**
	 * Gera saída de uma linha HTML/Bootstrap na tela ou no buffer
	 * @author	giuliano
	 * @param string $content Coluna 1
	 * @param string $content Coluna 2
	 * @param string $content Coluna n...
	 * @version	4.0 01-12-2013 10:50
	 */
	function row()
	{
		$row = '';
		$numargs = func_num_args();
		$arg_list = func_get_args();
		$umaCol = 'col-xs-12 col-sm-12 col-md-12 col-lg-12';

		if ($numargs > 0) {
			if ($numargs == 1) {
				$content = $arg_list[0];
				if (is_array($content)) {
					$row = $content[1] . $this->n;
					$row = tagMe('div', $row, 'class="' . $umaCol . '"') . $this->n;
					$row = tagMe('div', $row, 'class="row"') . $this->n;
					$row = tagMe('div', $row, 'class="container"') . $this->n;
					$content[1] = $this->d('<!-- row (start) -->' . $this->n) . $row . $this->d('<!-- row (end) -->' . $this->n);
					$this->out($content);
				} else {
					$row.=$content . $this->n;
					$row = tagMe('div', $row, 'class="' . $umaCol . '"') . $this->n;
					$row = tagMe('div', $row, 'class="row"') . $this->n;
					$row = tagMe('div', $row, 'class="container"') . $this->n;
					$this->out($this->d('<!-- row (start) -->' . $this->n) . $row . $this->d('<!-- row (end) -->' . $this->n), gLOC_INLINE, 999);
				}
			} else {
				$col = '';
				if (is_array($this->rowLayout)) {
					$col = $this->rowLayout;
				} else {
					switch($numargs) {
						case 2:
							$col[0] = 'col-xs-12 col-sm-6 col-md-6 col-lg-6';
							$col[1] = 'col-xs-12 col-sm-6 col-md-6 col-lg-6';
							break;
						case 3:
							$col[0] = 'col-xs-12 col-sm-4 col-md-4 col-lg-4';
							$col[1] = 'col-xs-12 col-sm-4 col-md-4 col-lg-4';
							$col[2] = 'col-xs-12 col-sm-4 col-md-4 col-lg-4';
							break;
						case 4:
							$col[0] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							$col[1] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							$col[2] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							$col[3] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							break;
						case 5:
							$col[0] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							$col[1] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[2] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[3] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[4] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							break;
						case 6:
							$col[0] = 'col-xs-6 col-sm-2 col-md-2 col-lg-2';
							$col[1] = 'col-xs-6 col-sm-2 col-md-2 col-lg-2';
							$col[2] = 'col-xs-6 col-sm-2 col-md-2 col-lg-2';
							$col[3] = 'col-xs-6 col-sm-2 col-md-2 col-lg-2';
							$col[4] = 'col-xs-6 col-sm-2 col-md-2 col-lg-2';
							$col[5] = 'col-xs-6 col-sm-2 col-md-2 col-lg-2';
							break;
						case 7:
							$col[0] = 'col-xs-12 col-sm-1 col-md-1 col-lg-1';
							$col[1] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[2] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[3] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[4] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[5] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[6] = 'col-xs-12 col-sm-1 col-md-1 col-lg-1';
							break;
						case 8:
							$col[0] = 'col-xs-1 col-sm-1 col-md-1 col-lg-1';
							$col[1] = 'col-xs-1 col-sm-1 col-md-1 col-lg-1';
							$col[2] = 'col-xs-2 col-sm-2 col-md-2 col-lg-2';
							$col[3] = 'col-xs-2 col-sm-2 col-md-2 col-lg-2';
							$col[4] = 'col-xs-2 col-sm-2 col-md-2 col-lg-2';
							$col[5] = 'col-xs-2 col-sm-2 col-md-2 col-lg-2';
							$col[6] = 'col-xs-1 col-sm-1 col-md-1 col-lg-1';
							$col[7] = 'col-xs-1 col-sm-1 col-md-1 col-lg-1';
							break;
					}
				}
				$cols = '';
				for ($i = 0; $i < $numargs; $i++) {
					$rowEl = '';
					$content = $arg_list[$i];
					if (is_array($content)) {
						$rowEl.=$content[1] . $this->n;
					} else {
						$rowEl.=$content . $this->n;
					}
					$cols[] = tagMe('div', $rowEl, 'class="' . $col[$i] . '"') . $this->n;
				}
				$row = tagMe('div', implode($this->d('<!-- col sep -->' . $this->n), $cols), 'class="row"');
				$row = tagMe('div', $row, 'class="container"') . $this->n;
				$this->out($this->d('<!-- row (start) -->' . $this->n) . $row . $this->d('<!-- row (end) -->') . $this->n, gLOC_INLINE, 999);
			}
		}
	}

	function rowTags()
	{
		$row = '';
		$numargs = func_num_args();
		$arg_list = func_get_args();
		$umaCol = 'col-xs-12 col-sm-12 col-md-12 col-lg-12';

		if ($numargs > 0) {
			if ($numargs == 1) {
				$content = $arg_list[0];
				$row.=$content . $this->n;
				$row = tagMe('div', $row, 'class="' . $umaCol . '"') . $this->n;
				$row = tagMe('div', $row, 'class="row"') . $this->n;
				$row = tagMe('div', $row, 'class="container"') . $this->n;
				$html.=$this->d('<!-- row (start) -->' . $this->n) . $row . $this->d('<!-- row (end) -->' . $this->n);
			} else {
				$col = '';
				if (is_array($this->rowLayout)) {
					$col = $this->rowLayout;
				} else {
					switch($numargs) {
						case 2:
							$col[0] = 'col-xs-12 col-sm-6 col-md-6 col-lg-6';
							$col[1] = 'col-xs-12 col-sm-6 col-md-6 col-lg-6';
							break;
						case 3:
							$col[0] = 'col-xs-12 col-sm-4 col-md-4 col-lg-4';
							$col[1] = 'col-xs-12 col-sm-4 col-md-4 col-lg-4';
							$col[2] = 'col-xs-12 col-sm-4 col-md-4 col-lg-4';
							break;
						case 4:
							$col[0] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							$col[1] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							$col[2] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							$col[3] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							break;
						case 5:
							$col[0] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							$col[1] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[2] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[3] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[4] = 'col-xs-12 col-sm-3 col-md-3 col-lg-3';
							break;
						case 6:
							$col[0] = 'col-xs-6 col-sm-4 col-md-2 col-lg-2';
							$col[1] = 'col-xs-6 col-sm-4 col-md-2 col-lg-2';
							$col[2] = 'col-xs-6 col-sm-4 col-md-2 col-lg-2';
							$col[3] = 'col-xs-6 col-sm-4 col-md-2 col-lg-2';
							$col[4] = 'col-xs-6 col-sm-4 col-md-2 col-lg-2';
							$col[5] = 'col-xs-6 col-sm-4 col-md-2 col-lg-2';
							break;
						case 7:
							$col[0] = 'col-xs-12 col-sm-1 col-md-1 col-lg-1';
							$col[1] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[2] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[3] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[4] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[5] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[6] = 'col-xs-12 col-sm-1 col-md-1 col-lg-1';
							break;
						case 8:
							$col[0] = 'col-xs-12 col-sm-1 col-md-1 col-lg-1';
							$col[1] = 'col-xs-12 col-sm-1 col-md-1 col-lg-1';
							$col[2] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[3] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[4] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[5] = 'col-xs-12 col-sm-2 col-md-2 col-lg-2';
							$col[6] = 'col-xs-12 col-sm-1 col-md-1 col-lg-1';
							$col[7] = 'col-xs-12 col-sm-1 col-md-1 col-lg-1';
							break;
					}
				}
				$cols = '';
				for ($i = 0; $i < $numargs; $i++) {
					$rowEl = '';
					$content = $arg_list[$i];
					$rowEl.=$content . $this->n;
					$cols[] = tagMe('div', $rowEl, 'class="' . $col[$i] . '"') . $this->n;
				}
				$row = tagMe('div', implode($this->d('<!-- col sep -->' . $this->n), $cols), 'class="row"');
				//$row = tagMe('div', $row, 'class="container"') . $this->n;
				$html.=$this->d('<!-- row (start) -->' . $this->n) . $row . $this->d('<!-- row (end) -->') . $this->n;
			}
		}
		return($html);
	}

	/**
	 * Gera código de início da página
	 * @author	giuliano
	 * @param string $json Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function begin($json = "")
	{

	}

	/**
	 * Gera código de final da página
	 * @author	giuliano
	 * @param string $echo Sai direto na página? (true/false)
	 * @version	4.0 01-12-2013 10:50
	 */
	function end($echo=true)
	{
		global $usrId;
		global $gFWMute,$http_lib;
		//$js = "$('.panel').toggle('slow')";
		$js = "
		if ($('.panel').length > 0) {
			$('.panel').show('slow');
		}";
		//$this->addJavascript($js);
		if ($gFWMute===true)
		{
			$this->onlyBody=true;
			$echo=false;
		}
		// Renderiza todas chamadas gAjax
		global $gAjax;

		$gAjax->render($this);

		$this->setTimezone();

		//$this->addJavascript("function hideWait() { document.getElementById('gWait').style.display='none'; } function showWait() { document.getElementById('gWait').style.display='block'; setTimeout( function(){hideWait()},2500)} ");

		if ($_SESSION['gFW_MENU']<>'')
		{
			//echo "<h1>TEM GFW_MENU</H1>";
			$this->buffer=$_SESSION['gFW_MENU'].$this->buffer;
		} else
		{
		//echo "<h1>NÃO TEM GFW_MENU</H1>";
		}

		$this->indent = 6;
		// Final da página
		if ($this->bufferJavascript <> '') {
			$this->out('<script type="text/javascript">', gLOC_POS);


			//$this->out("function showWait() { document.getElementById('gWait').style.height='100%';document.getElementById('gWait').style.display='block';setTimeout( function(){hideWait()},5000) }\n",gLOC_POS);
			$js.="
			function hideWait() { document.getElementById('gWait').style.display='none'; }

			function showWait(self)
			{
				if (self)
				{
					self.setAttribute('disabled', 'disabled');
					setTimeout(function () {
						self.removeAttribute('disabled');
					}, 5000);
				}
				document.getElementById('gWait').style.height='150%';document.getElementById('gWait').style.display='block';
				return (true)
			}

			function showWaitAjax(self)
			{
				document.getElementById('gWait').style.height='150%';document.getElementById('gWait').style.display='block';
				return (true)
			}

			function c(el)
			{
				if ((el.style.backgroundColor==\"\") || (el.style.backgroundColor==\"\#ffffff\") || (el.style.backgroundColor.toLowerCase()==\"rgb(255, 255, 255)\"))
					el.style.backgroundColor=\"#ffffcc\";
				else
					el.removeAttribute('style');
			}
			";
			if ($this->useAngular)
			{
				if (is_array($this->angularLinks))
				{
					$js.='
			var app = angular.module(\'App\', [\'ngRoute\', \'ngResource\', \'angular-loading-bar\']);
			app.config(function ($routeProvider) { ';
					foreach ($this->angularLinks as $key=>$value)
					{

						$js.='
			    $routeProvider.when("/'.$key.'", {
        			templateUrl: "'.$value.'"
    			});';
					}
					$js.='
			});';
				}
			}

			foreach ($this->bufferJavascript as $jsBuff) {
				$js.=$jsBuff."\n";
			}

			if ($this->minify)
			{
				$js = minimizeJavascript($js);
			}
			$this->out($js, gLOC_POS);

			$this->out('</script>', gLOC_POS, -1);
		}

		$customFonts='';
		if (gVar("global.bodyfont")<>'')
		{	$css='';
			if (gVar("global.bodyfont")<>'Helvetica' && gVar("global.bodyfont")<>'Times'){
				// 400italic,700italic,400,700
				$customFonts.='@import url("//fonts.googleapis.com/css?family='.gVar("global.bodyfont").'");'.$this->n;
				$css.='body, input, .form-control, .item, .option {font-family: '.str_replace('+',' ',gVar("global.bodyfont")).'}'.$this->n;
			}
			if (gVar("global.headersfont")<>'Helvetica' && gVar("global.headersfont")<>'Times'){
				$customFonts.='@import url("//fonts.googleapis.com/css?family='.gVar("global.headersfont").'");'.$this->n;
				$css.='h1,h2,h3,h4,h5,h6 {font-family: '.str_replace('+',' ',gVar("global.headersfont")).'}'.$this->n;

			}
			$customFonts=$this->n.'<style>'.$this->n.$customFonts.$this->n.$css.$this->n.'</style>'.$this->n;
		}

		$sai='';
		$gWait='<div id="gWait" onclick="$(\'#gWait\').hide();" class="text-center" style="display:none;position:absolute;z-index:100000;top:0;left:0;width:100%;height:100%;background-color: rgba(0,0,0,0.3); align-items: center;"><span class="fa fa-3x fa-spinner fa-spin fa-fw" style="position: relative; top: 50%; left: 0em"></span></div>'.$this->n;
		if ($echo)
		{
			if (!$this->onlyBody)
			{
				echo '<!DOCTYPE html>'.$this->n;
				if ($this->useAngular)
				{
					echo '<html data-ng-app="App">'.$this->n;
				} else
				{
					echo '<html>'.$this->n;
				}
				echo '   <head>'.$this->n;
				echo $this->bufferPre;
				echo $customFonts;
				if ($this->bootstrapVersao==3)
				{
					echo '      <link href="' . $http_lib . gVar("lib.bootstrap_themes") .'bugfix.css" rel="stylesheet">'.$this->n;
				}
				echo '   </head>'.$this->n;
				echo '<body style="height:100%;">'.$this->n;
				echo $gWait;
			}
			echo $this->buffer;
			if (!$this->onlyBody)
			{
				echo $this->bufferPos;
				echo '<!-- '.gethostname().' -->'.$this->n;
				echo '</body>'.$this->n;
				echo '</html>'.$this->n;
			}
		} else
		{
			if (!$this->onlyBody)
			{
				$sai.='<!DOCTYPE html>'.$this->n;
				if ($this->useAngular)
				{
					$sai.='<html data-ng-app="App">'.$this->n;
				} else
				{
					$sai.='<html>'.$this->n;
				}
				$sai.='   <head>'.$this->n;
				$sai.=$this->bufferPre;
				$sai.=$customFonts;
				$sai.='   </head>'.$this->n;
				$sai.='<body>'.$this->n;
				$sai.=$gWait;
			}
			$sai.=$this->buffer;
			if (!$this->onlyBody)
			{
				$sai.=$this->bufferPos;
				echo '<!-- '.gethostname().' -->'.$this->n;
				$sai.='</body>'.$this->n;
				$sai.='</html>'.$this->n;
			}
		}
		return($sai);
	}

	function setTimezone(){
		if(trim($_SESSION['defaultTimezone']) == "" ){
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



	/**
	 *
	 * @param type $content
	 */
	function body($content)
	{
		$this->begin();
		$this->out($content);
		$this->end();
	}

	/**
	 * Embute código javscript
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param type $code
	 * @param type $location
	 */
	function javascript($code, $location = gLOC_INLINE)
	{
		$this->out(tagMe("script", $code, 'type="text/javascript"'), $location);
	}

	function pageBreak()
	{
		$this->out('<div style="page-break-before: always;"></div>');
	}


	/**
	 * Gera <BR />
	 * @author	giuliano
	 * @param type $qtd
	 * @version	4.0 01-12-2013 10:50
	 */
	function br($qtd = 1)
	{
		$sai = '';
		if ($_REQUEST['gPDF']==1)
		{
			for ($a = 0; $a < $qtd; $a++) {
				$sai.="\n" . $this->n;
			}
		} else {
			for ($a = 0; $a < $qtd; $a++) {
				$sai.="<br>" . $this->n;
			}
		}
		return($sai);
	}

	/**
	 * Gera <HR />
	 * @author	giuliano
	 * @param string $json Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function hr($style='default')
	{
		if ($style=="default")
			$sai='<hr>';
		else
			$sai='<hr style="border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));">';
		return($sai . $this->n);
	}

	/**
	 * Gera <span class="label label-danger">conteudo</span>
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $content Parâmetros em formato Json
	 * @param type $parm
	 * @return type
	 */
	function label($content, $style = "default", $attr="")
	{
		return('<span class="label label-'.$style.'" '.str_replace("'",'"',$attr).'>' . $content . '</span>' . $this->n);
	}

	/**
	 * Gera <p>conteúdo</p>
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $content Parâmetros em formato Json
	 * @param type $parm
	 * @return type
	 */
	function p($content, $parm = "")
	{
		return(tagMe("p", gT($content), $parm) . $this->n);
	}

	/**
	 * Mostra texto com tamanho 50% maior
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $content Parâmetros em formato Json
	 * @param type $parm
	 * @return type
	 */
	function big($content)
	{
		return('<span style="font-size: 150%">' . $content . '</span>' . $this->n);
	}

	/**
	 * Gera <ul><li>conteúdo</li></ul>
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param array $elem Elementos em formato array
	 * @return type
	 */
	function ul($elem, $parm = "")
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
		return(tagMe("ul", $content, $parm) . $this->n);
	}

	/**
	 * Gera badge
	 * @author	giuliano
	 * @version	4.0 27-01-2014 13:07
	 * @param string $content Parâmetros em formato Json
	 * @param type $parm
	 * @return type
	 */
	function badge($content)
	{
		return(tagMe("span", $content, 'class="badge"') . $this->n);
	}

	/**
	 * Gera text pequeno (<small>)
	 * @author	giuliano
	 * @version	4.0 27-01-2014 13:07
	 * @param string $content Parâmetros em formato Json
	 * @param type $parm
	 * @return type
	 */
	function small($content, $parm = "")
	{
		if (trim($content)<>"")
		{
			$content = tagMe("small", $content, $parm) . $this->n;
		}
		return($content);
	}

	/**
	 * Gera <H1 /> - Título da página
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $content Parâmetros em formato Json
	 * @param type $parm
	 * @return type
	 */
	function h1($content, $parm = "")
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
	function h2($content, $parm = "")
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
	function h3($content, $parm = "")
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
	function h4($content, $parm = "")
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
	function h5($content, $parm = "")
	{
		return(tagMe("h5", gT($content.' '), $parm) . $this->n);
	}

	/**
	 * Gera mensagem em formato de alerta
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $content Parâmetros em formato Json
	 * @param type $style
	 * @return type
	 */
	function alert($content, $style = 'info')
	{
		return(tagMe("div", gT($content), 'class="alert alert-' . $style . '"'));
	}

	/**
	 * Gera uma mensagem
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msg($content)
	{
		return(tagMe("p", gT($content)));
	}

	/**
	 * Gera mensagem em formato Jumbotron (bootstrap)
	 * @author	giuliano
	 * @param string $title texto
	 * @param string $content texto
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgJumbo($title, $content='', $tag='h1')
	{
		$txt=tagMe($tag,$title);
		$txt.=tagMe("p",$content);
		return(tagMe("div",$txt,'class="jumbotron"'));
	}

	/**
	 * Gera mensagem em formato Título
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @param string $param Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgTitle($content,$url = '', $param = '')
	{
		global $usrId, $usrIdd,$gLang, $CFC, $debug;
      $oldDebug = $debug;
		$param = (!empty($param))? jsDecode($param) : array();

		// Exibindo popover
		if(!empty($param['popover'])){

			$js = ' $(document).ready(function(){
						$(".page-header h1 a").popover({
							container: "body",
							html: true
						});';
			// iniciar automaticamente popover
			if(!empty($param['popover-auto'])){
				$js .= '$(".page-header h1 a").popover("show");';
				unset($param['popover-auto']);
			}

			// fecha automaticamente popover
			if(!empty($param['popover-timeout'])){
				$js .= '$(".page-header h1 a").on("shown.bs.popover", function () {
							setTimeout(function() {
								$(".page-header h1 a").popover("hide");
							}, '.intval($param['popover-timeout']).'000);
						});';
				unset($param['popover-timeout']);
			}
			$js .= '});';
			$this->addJavascript($js);

			// Setando parametros padrao
			if(empty($param['data-trigger'])){
				$param['data-trigger'] = 'hover';
			}

			if(empty($param['data-placement'])){
				$param['data-placement'] = 'right';
			}

			unset($param['popover']);
		}

		$this->pageData['title'] = gT($content);
		$this->pageTitle = gT($content);
		$help='<div class="pull-right '.$this->bootstrapTags['hidden-print'].' hiddenOnPrint">';

		// Armazenando parametro para o elemento A
		$param_a = '';
		if(count($param)){
			foreach($param_a as $key => $value){
				$param_a.=' '.$key.': "'.$value.'";';
			}
		}

		$urlExport = $_SERVER['REQUEST_URI'];

		$exp = array();
		foreach ($_POST as $key=>$value)
		{
			if (is_array($value))
			{
				foreach ($value as $k=>$v)
				{
					$exp[]=$key.'['.$k.']='.urlencode($v);
				}
			} else
			{
				$exp[]=$key.'='.urlencode($value);
			}
		}
		$urlExport.='&'.implode('&',$exp);

		$btns='';
		//$oSufix = '-o';
		$$oSufix = '';
		if (gVar('font_awesome5')<>'')
			$oSufix = '';
		if ($this->PDFEnabled)
		{
			if ($this->orientation=="L")
			{
				$urlExport.='&gPDFOrientation=L';
			}

			$btns.=$this->button("{icon: print$oSufix; title: Imprimir; hint: Imprimir; target: _blank; style: default; size: small; name:print; id:print; showWait:false}");
			$btns.=$this->button("{icon: file-pdf$oSufix; title: PDF; hint: Exportar para PDF; target: _blank; style: default; size: small; href: ".$urlExport."&gPDF=1}");
			$js .= "
					$('#print').click(function(){
					// $('body:not(a)').css({'font-family':'arial'});
					// $(':header').css({'font-family':'arial'});
					$('body').css({'padding-top': '1px'});

					// Tratamento para impressoras mini-impressoras
					var h1 = $('h1').html();
					if(document.querySelector('h1')!=null){
						if(h1.search('- Mini') > 0){
							$('h1').html(h1.replace('- Mini',''));
							$('*:not(a)').css({'font-size':'10px'});
							$('h1').css({'font-family':'arial','font-size':'18px'});
							$('h2').css({'font-family':'arial','font-size':'14px'});
							$('h4').css({'font-family':'arial','font-size':'12px'});
						}
					}

					if(document.querySelector('#gTable')!=null)
						$('#gTable').removeClass('table-responsive');

					//this.style.display = 'none';
					$('.hideIt').hide();
					window.print();
					$('body').css({'padding-top': '36px'});

				});";
			$this->addJavascript($js);

		}
		if ($this->XLSEnabled)
		{
			$btns.=$this->button("{icon: file-excel$oSufix; title: XLS; hint: Exportar para planilha editável; target: _blank; style: default; size: small; href: ".$urlExport."&gXLS=1}");
		}
		if ($this->XMLEnabled)
		{
			$btns.=$this->button("{icon: file-code$oSufix; title: XML; hint: Exportar para arquivo XML; target: _blank; style: default; size: small; href: ".$urlExport."&gXML=1}");
		}
		if ($this->DOCEnabled)
		{
			if ($this->orientation=="L")
			{
				$urlExport.='&gPDFOrientation=L';
			}

			$btns.=$this->button("{icon: file-word$oSufix; title: DOC; hint: Exportar para documento editável; target: _blank; style: default; size: small; href: ".$urlExport."&gDOC=1}");
		}
		if ($this->CSVEnabled)
		{
			$btns.=$this->button("{icon: file-alt$oSufix; title: CSV; hint: Exportar para CSV; target: _blank; style: default; size: small; href: ".$urlExport."&gCSV=1}");
		}
		if ($this->TXTEnabled)
		{
			$btns.=$this->button("{icon: file-alt$oSufix; title: TXT; hint: Exportar para TXT; target: _blank; style: default; size: small; href: ".$urlExport."&gTXT=1}");
		}
		if ($this->PrintEnabled)
		{
			$btns.=$this->button("{icon: print; hint: Imprimir; style: default; size: small; }", "window.print()");
		}
		if ($btns<>'')
		{
			$btns = '<span class="btn-group" style="font-size: 13px">'.$btns.'</span>';
		}
		$help.=$btns;

		if ( ($usrId>0) && !(isset($_SESSION['gFW4'])) && (gVar("global.help")=="true") )
		{
			$debug = false;
			// Busca por página do Wiki de Help
			$sql="SELECT * FROM gfw_posts WHERE keyword='Help_".$_REQUEST['g']."'";
			$rsh=dbFastQuery($sql);
			if (count($rsh)>0)
			{
				$help.='<a class="" data-toggle="tooltip" data-placement="left" title="Ajuda" href="index.php?g=index&pp=Help_'.$_REQUEST['g'].'"><span class="fa fa-question-circle"></span></a>';
			} else
			{
				//if (($usrId==1) || ($usrId==$usrIdd))
				if ($usrId==1)
				{
					$tag='';
					$content='';
					$order1=0;
					// Busca nível superior deste link somente pra pegar a Tag
					$sql="SELECT * FROM gfw_menus WHERE link='".$_REQUEST['g']."' AND locale='".$gLang."'";
					$rsm=dbQuery($sql);
					if (count($rsm)>0)
					{
						$order1=$rsm[0]['order1'];
						$content=$rsm[0]['content'];
						$sql="SELECT * FROM gfw_menus WHERE order1=".$order1." AND order2=0";
						$rsm=dbQuery($sql);
						$tags=$rsm[0]['title'];
					}
					$help.='<a class="" data-toggle="tooltip" data-placement="left" title="Criar/alterar instruções de ajuda para esta página" href="index.php?g=posts&gPage=1&gAction=wiki&title='.$this->pageData['title'].'&keyword=Help_'.$_REQUEST['g'].'&content='.$content.'&tags='.$tags.'"><span class="fa fa-question"></span></a>';
				}
			}
		}

		// if (gVar('global.chat') == 'true' && $_SESSION['usrId']>0 && strtoupper($_SESSION['uf'])<>"BA" )
		// {
		// 	$help.='&nbsp;<a id="acionar_chat" class="" data-toggle="tooltip" data-placement="left" title="Acionar o suporte on-line" href="#"><i class="'.$this->fa.' fa-comments"></i></a>&nbsp;';

		// 	$urlchat = "http://app.giusoft.com.br/gadmin/index.php?g=Chat&CFC=".$CFC.'&gPage=1&empresa='.$CFC.'&usuario='.$_SESSION['usrName'];

		// 	$dia = date("w");
		// 	$hora = intval(date("Hi"));
		// 	$chat_bloqueado = false;
		// 	if($dia > 0 && $dia < 6)
		// 	{
		// 		if( ($hora > 1159 && $hora < 1330) || $hora > 1759)
		// 			$chat_bloqueado = true;
		// 	}
		// 	else
		// 	{
		// 		$chat_bloqueado = true;
		// 	}
		// 	if($chat_bloqueado)
		// 	{
		// 		$chat_msg = '<p style="color:red"><b>O atendimento ao usuário via Chat não está disponivel agora.</b></p>Nosso horário de atendimento é de Segunda à Sexta, das 08:00 às 12:00hs e das 13:30 às 18:00hs<br><br>Você também pode solicitar atendimento pelo e-mail: <a href="mailto:suporte@giusoft.com.br">suporte@giusoft.com.br</a>';

		// 		$bootbox = "bootbox.alert({title: 'Suporte via Chat', message: '$chat_msg'})";
		// 	}
		// 	else
		// 	{
		// 		$chat_msg = 'O atendimento ao usuário via Chat está disponivel de Segunda à Sexta das 08:00 às 12:00hs e das 13:30 às 18:00hs <br/><br/><b>Estamos disponíveis para atendê-lo agora.</b><br><br>Deseja continuar?';

		// 		$bootbox = "bootbox.confirm({
		// 							title: 'Suporte via Chat',
		// 						    message: '$chat_msg',
		// 						    buttons: {
		// 						    	confirm: {
		// 						    		label: 'Sim',
		// 						    		className: 'btn-success'
		// 						    	},
		// 						    	cancel: {
		// 						    		label: 'Cancelar',
		// 						    		className: 'btn-default'
		// 						    	}
		// 						    },
		// 						    callback: function (result) {
		// 						        if(result)
		// 						        	window.open('$urlchat');
		// 						    }
		// 						});";
		// 	}

		// 	$jschat = "$('document').ready(function(){
		// 					$('#acionar_chat').click(function(e){
		// 						e.preventDefault();

		// 						$bootbox

		// 					});
		// 				});
		// 			";

		// 	$this->addJavascript($jschat);
		// }

		$help.='</div>';
		$debug = $oldDebug;

		$tituloComRecursos = $this->h1('<a class="'.$this->bootstrapTags['hidden-print'].'" data-toggle="tooltip" data-placement="right" title="'.gT('Clique para abrir esta página no início').'" href="'.($url? $url : $this->page).'"'.$param_a.' onClick="showWait();">'.$this->pageData['title'].'</a>'.$help);

		return(
			'<div class="page-header '.$this->bootstrapTags['visible-print-inline'].'">'.$this->h1($this->pageData['title']).'</div>'.
			'<div class="page-header '.$this->bootstrapTags['hidden-print'].'">'.$tituloComRecursos.'</div>'
		);
	}

	/**
	 * Gera mensagem em formato Sub Título
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgSubTitle($content, $par = "")
	{
		$this->pageData['subtitle'] = gT($content);
		return($this->h4($this->pageData['subtitle'], $par));
	}

	/**
	 * Gera mensagem em formato Título menor
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgMiniTitle($content, $par = "")
	{
		$this->pageData['minititle'] = gT($content);
		return($this->h5($this->pageData['minititle'], $par));
	}

	/**
	 * Gera mensagem em formato Título maior
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgMaxiTitle($content, $par = "")
	{
		$this->pageData['minititle'] = gT($content);
		return($this->h2($this->pageData['minititle'], $par));
	}

	/**
	 * Gera mensagem em formato filtro
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgFilter($content, $par = "")
	{
		$this->pageData['filter'] = gT($content);
		return($this->h4($this->pageData['filter'], $par));
	}

	/**
	 * Gera mensagem em formato rodapé
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgFooter($content, $par = "")
	{
		$this->pageData['footer'] = gT($content);
		return(tagMe('small', $this->pageData['footer'], $par));
	}

	/**
	 * Gera mensagem em formato Default!
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgDefault($content, $par = "")
	{
		$par['class'] = 'well well-sm';
		return(tagMe("div", gT($content), $par));
	}

	/**
	 * Gera mensagem em formato Alerta!
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgAlert($content, $par = "")
	{
		$par['class'] = 'alert alert-warning';
		return(tagMe("div", gT($content), $par));
	}

	/**
	 * Gera mensagem em formato Erro!
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgError($content, $par = "")
	{
		$par['class'] = 'alert alert-danger';
		return(tagMe("div", gT($content), $par));
	}

	/**
	 * Gera mensagem em formato Sucesso!
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgSuccess($content, $par = "")
	{
		$par['class'] = 'alert alert-success';
		return(tagMe("div", gT($content), $par));
	}

	/**
	 * Gera mensagem em formato Danger!
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgDanger($content, $par = "")
	{
		$par['class'] = 'alert alert-danger';
		return(tagMe("div", gT($content), $par));
	}

	/**
	 * Gera mensagem em formato Warning!
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgWarning($content, $par = "")
	{
		$par['class'] = 'alert alert-warning';
		return(tagMe("div", gT($content), $par));
	}

	/**
	 * Gera mensagem em formato Info!
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgInfo($content, $par = "")
	{
		$par['class'] = 'alert alert-info';
		return(tagMe("div", gT($content), $par));
	}

	/**
	 * Gera mensagem em formato Lead
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgLead($content, $par = "")
	{
		$par['class'] = 'lead';
		return(tagMe("p", gT($content), $par));
	}

	/**
	 * Gera mensagem em formato bloco de citação
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgBlockquote($content, $par = "")
	{
		return(tagMe("blockquote", gT($content),$par));
	}

	/**
	 * Gera mensagem em formato código de programação
	 * @author	giuliano
	 * @param string $content Parâmetros em formato Json
	 * @version	4.0 01-12-2013 10:50
	 */
	function msgCode($content)
	{
		return(tagMe("code", $content));
	}


	/**
	 * Monta estrutura de barra de progresso
	 * @author	giuliano
	 * @version	4.0 01-02-2015 12:56
	 * @param string $value Valor menor do que $max ou percentual para a barra
	 * @param string $max Valor máximo (opcional)
	 * @return type
	 */
	function progressBar($value, $max=0, $style="info", $percent=true)
	{
		if ($max>0)
		{
			$perc=intval(($value*100)/$max);
		} else {
			$max=$value;
		}
		if ($percent)
		{
			$progress='<div class="progress-bar progress-bar-'.$style.'" role="progressbar" aria-valuenow="'.$perc.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$perc.'%;">'.$perc.'%</div>';
		} else {
			$progress='<div class="progress-bar progress-bar-'.$style.'" role="progressbar" aria-valuenow="'.$value.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$perc.'%;">'.$value.'</div>';

		}
		return(tagMe("div", $progress, 'class="progress" style="height: 22px; padding: 0px; margin: 0px"'));
	}

	/**
	 * Monta estrutura de início da tabela
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $size Tamanho da tabela
	 * @param string $border Com ou sem borda?
	 * @param string $striped Mostra cores alternadas?
	 * @param string $sortable Ordenável pelos títulos das colunas?
	 * @return type
	 */
	function tableBegin($size, $border = false, $striped = false, $sortable = false, $hovered = true, $responsive = true)
	{

		$perc = "100%";
		$style = '';
		$class = '';
		$class[] = 'table';
		$responsiveClass = '';
		if ($responsive)
			$responsiveClass = "table-responsive";

		if ($hovered)
			$class[] = 'table-hover'; // ao passar com mouse, muda a cor
		$class[] = 'table-condensed'; // tabela comprimida

		if ($border) {
			$class[] = "table-bordered";
		}
		if ($striped) {
			$class[] = "table-striped";
		}

		if (strpos($size, "%") !== false) {
			$perc = $size;
			$style = ' style="width: $perc"';
		} else {
			switch($size) {
				case "big":
					$style = ' style="width: 100%"';
					break;
				case "medium":
					$style = ' style="width: 75%"';
					break;
				case "small":
					$style = ' style="width: 50%"';
					break;
				case "tiny":
					$style = ' style="width: 30%"';
					break;
			}
		}
		if ($sortable) {
			$style.="  data-sortable";
		}
		$sai = '<div id="gTable" class="'.$responsiveClass.'"><table class="' . implode(" ", $class) . '"' . $style . '>' . $this->n;
		$this->tableFirstRow = true;

		return($sai);
	}

	function tableGroup($col=-1)
	{
		if ($col>=0)
			$this->tableGroups[$col]=SENHA_NAO_MODIFICADA;
	}

	function tableTotal($json)
	{
		if ($col>=0)
		{
			$jarr=cssDecode($json);
			$col=intval($jarr['column']);
			$this->tableTotals[$col]=$jarr;
			$this->tableValues[$col]=0;
		}
	}

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
		$mtz = "";
		$removerCampos = $_REQUEST['gTableRemoveFields'];
		$newCols = "";
		for ($a = 0; $a<count($fields); $a++)
		{
			$achou = false;
			foreach ($removerCampos as $remover )
			{
				if ($remover == $fields[$a])
					$achou = true;
			}
			if (!$achou)
				$newCols[]=$colMatrix[$a];
		}
		return($this->tableRow($newCols, $style, $add, $event));
	}

	function tableLine($height=2, $color="black")
	{
		$sai = '<tr style="padding: 0px; margin: 0px; height:'.$height.'px"><td colspan="100" style="background-color: '.$color.'; padding: 0px; margin: 0px; height: '.$height.'px"></td></tr>';
		return($sai);
	}

	/**
	 * Monta estrutura de uma linha da tabela
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $colMatrix Array contendo as colunas
	 * @param string $style Estilo da linha
	 * @param string $add Parametros adicionais
	 * @param string $event Adicionar algum tratamento de evento via javascript
	 * @return type
	 */
	function tableRow($colMatrix, $style = "detail", $add = "", $event = "")
	{
		$sai = "";
		$tag = 'td';
		$parTr = '';
		$onclick = "";
		if (($this->tableSegment == 'thead') && ($style <> 'header')) {
			$sai.='</thead>' . $this->n;
			$this->tableFirstRow = true;
		}
		switch($style) {
			case "detail":
				$this->tableSegment = 'tbody';
				$style = '';
				$onclick = "c(this)";
				break;
			case "pointer":
				$tag = 'th';
				$this->tableSegment = 'tbody';
				$style = '';
				break;
			case "header":
				$tag = 'th';
				$this->tableSegment = 'thead';
				$style = 'info';
				break;
			case "header-fixed":
				$tag = 'th';
				$this->tableSegment = 'thead';
				$style = 'info';
				$js = "
					$('#gTable').css({'overflow': 'auto', 'height': '78vh'})
					$('#gTable').find('thead').css({'position': 'sticky', 'top': '0', 'z-index': '1', 'border':'0px'})
					$('#gTable').find('th').css({'position': 'sticky', 'top': '0', 'z-index': '1', 'border':'0px'})
				";
				$this->addJavascript($js);
				break;
			case "footer":
				$this->tableSegment = 'tbody';
				$style = 'active';
				$onclick = "c(this)";
				break;
			case "summary":
				$this->tableSegment = 'tbody';
				$style = 'success';
				$onclick = "c(this)";
				break;
			case "total":
				$this->tableSegment = 'tbody';
				$style = 'danger';
				$onclick = "c(this)";
				break;
			case "subtotal":
				$this->tableSegment = 'tbody';
				$style = 'warning';
				$onclick = "c(this)";
				break;
			case "danger":
				$this->tableSegment = 'tbody';
				//$style = 'text-danger';
				$classTr = ' class="danger"';
				$onclick = "c(this)";
				break;
			case "warning":
				$this->tableSegment = 'tbody';
				$style = 'warning';
				$onclick = "c(this)";
				break;
			case "primary":
			case "active":
				$this->tableSegment = 'tbody';
				$style = 'active';
				$onclick = "c(this)";
				break;
			case "grey":
				$this->tableSegment = 'tbody';
				$style = 'grey';
				$onclick = "c(this)";
				break;
			case "info":
				$this->tableSegment = 'tbody';
				$style = 'info';
				$onclick = "c(this)";
				break;
			case "success":
				$this->tableSegment = 'tbody';
				$style = 'success';
				$onclick = "c(this)";
				break;
			case "text-danger":
				$this->tableSegment = 'tbody';
				$style = 'text-danger';
				$onclick = "c(this)";
				break;
			case "text-warning":
				$this->tableSegment = 'tbody';
				$style = 'text-warning';
				$onclick = "c(this)";
				break;
			case "text-info":
				$this->tableSegment = 'tbody';
				$style = 'text-info';
				$onclick = "c(this)";
				break;
			case "text-primary":
				$this->tableSegment = 'tbody';
				$style = 'text-primary';
				$onclick = "c(this)";
				break;
			case "text-success":
				$this->tableSegment = 'tbody';
				$style = 'text-success';
				$onclick = "c(this)";
				break;
			case "bg-danger":
				$this->tableSegment = 'tbody';
				$style = 'bg-danger';
				$onclick = "c(this)";
				break;
			default:
				$this->tableSegment = 'tbody';
				//$onclick = "c(this)";
				break;
		}
		if (strpos($style,"detail")!==false)
		{
			$onclick = "c(this)";
		}

		if ($this->tableFirstRow) {
			$sai.='<' . $this->tableSegment . '>' . $this->n;
		}

		$row = '';
		$c = count($colMatrix);
		for ($a = 0; $a < $c; $a++) {
			$js = "";
			$wrap = "";
			$align = "";

			$par = '';

			// Parâmetros passados via array
			if (is_array($colMatrix[$a])) {
				$cont = $colMatrix[$a][0];
				$el = $colMatrix[$a];
				foreach ($el as $key=>$value)
				{
					if ($key<>'0')
					{
						if (intval($key)>0)
						{
							for ($b = 1; $b < count($el); $b++) {
								if (strpos($el[$b], ":") !== false) {
									$key = substr($el[$b], 0, strpos($el[$b], ":"));
									$value = substr($el[$b], strpos($el[$b], ":") + 1);
									$par[$key] = $value;
								}
							}
						} else
						{
							$par[$key]=$value;
							if (strtolower($key)=='onclick')
								$onclick='';
						}
					}

				}
			} else {
				$cont = $colMatrix[$a];
			}

			// Colspan
			if (substr($cont, 0, 1) == "~") {
				$colspan = substr($cont, 1, 1);
				if ((ord(substr($cont, 2, 1)) > 47) && (ord(substr($cont, 2, 1)) < 58)) {
					$colspan.=substr($cont, 2, 1);
					if ((ord(substr($cont, 3, 1)) > 47) && (ord(substr($cont, 3, 1)) < 58)) {
						$colspan.=substr($cont, 3, 1);
						$cont = substr($cont, 4);
					} else {
						$cont = substr($cont, 3);
					}
				} else {
					$cont = substr($cont, 2);
				}
				$par['colspan'] = $colspan;
			}

			// Alinhamento
			$align = "text-center";
			if (substr($cont, 0, 2) == "->") {
				$align = "text-right";
				$cont = substr($cont, 2);
			}
			if (substr($cont, 0, 2) == "<-") {
				$align = "text-left";
				$cont = substr($cont, 2);
			}
			if (substr($cont, 0, 2) == "<>") {
				$align = "text-center";
				$cont = substr($cont, 2);
			}

			if ($this->tableSegment == 'thead')
				$cont = gT($cont);
			$mostra=true;
			foreach ($this->tableGroups as $gKey=>$gValue)
			{
				if ($a==$gKey){
					$mostra=false;
				}
			}
			if ($mostra)
			{
				if ($onclick<>'' && ($a>0 || trim($cont)=='' || (stripos($cont,'href=')===false && stripos($cont,'<button ')===false )))
				{
					$par['onclick']=$onclick;
				}
				if ($cont <> "")
				{
					if ($align <> "") {

						if (!empty($par['class']))
						{
							$par['class'] = trim($par['class'] . ' ' . $align);
						}
						// else
						// {
						// 	$par['class'] = $align;
						// }

						$cont = tagMe("div", $cont, 'class="' . $align . '"');
					}
					$row.=tagMe($tag, $cont, $par);
				} else
				{
					if ($par['onclick']<>'')
						$row.='<td onClick="'.$par['onclick'].'"></td>';
					else
						$row.='<td></td>';
				}
			}
		}
		if ($style <> '')
			$parTr['class'] = $style;

		if($event <> ''){

			if(strpos($event, '=') != false){
				$pos=strpos($event, '=');

				$parTr[substr($event, 0,$pos)]=substr($event, $pos+1, strlen($event));
			}

		}

		if ($this->tableSegment <> 'thead'){
			// Verifica se tem agrupamento
			foreach ($this->tableGroups as $gKey=>$gValue)
			{
				if ($gValue<>$colMatrix[$gKey])
				{
					// Mostra totalizadores (sumário parcial)
					if (is_array($this->tableTotals) && $gValue<>SENHA_NAO_MODIFICADA)
					{
						$sumRow='';
						$mostrouLabel=false;
						for ($i=0; $i<count($colMatrix); $i++)
						{
							if (isset($this->tableValues[$i]))
							{
								if ($this->tableTotals[$i]['formula']=='count')
									$sumRow.='<td align="right">'.intval($this->tableValues[$i]).'</td>';
								else
									$sumRow.='<td align="right">'.gFloat($this->tableValues[$i]).'</td>';
							} else
							{
								if (!isset($this->tableGroups[$i]))
								{
									if (isset($this->tableTotals[($i+1)]) && (!$mostrouLabel))
									{
										$mostrouLabel=true;
										$sumRow.='<td align="right">'.gT("Sub-total").'</td>';
									}
									else
										$sumRow.='<td></td>';
								}
							}
						}
						$sai = $sai . tagMe('tr', $sumRow, 'class="success"') . $this->n;
						foreach ($this->tableValues as $sKey=>$sValue)
						{
							$this->tableValues[$sKey]=0;
						}
					}
					$gValue=$colMatrix[$gKey];
					$this->tableGroups[$gKey]=$gValue;
					$gValue=preg_replace('/^<-/','', $gValue);
					$gValue=preg_replace('/^->/','', $gValue);
					$gValue=preg_replace('/^<>/','', $gValue);
					$groupRow='';
					$groupRow.='<td colspan="99" align="center">'.$gValue.'</td>';
					$sai = $sai . tagMe('tr', $groupRow, 'class="success"') . $this->n;
				}
			}

			// Verifica se tem totalizadores
			if (is_array($this->tableTotals))
			{
				foreach ($this->tableTotals as $sKey=>$sValue)
				{
					$gValue=$colMatrix[$sKey];
					$gValue=preg_replace('/^<-/','', $gValue);
					$gValue=preg_replace('/^->/','', $gValue);
					$gValue=preg_replace('/^<>/','', $gValue);
					$gValue=strip_tags($gValue);
					$gValue=floatval(gDBFloat($gValue));

					switch ($sValue['formula'])
					{
						case 'sum':
							$this->tableValues[$sKey]+=$gValue;
							$this->tableTotalValues[$sKey]+=$gValue;
							break;

						case 'count':
							$this->tableValues[$sKey]+=1;
							$this->tableTotalValues[$sKey]+=1;
							break;

						case 'less':
							if ($gValue<$this->tableValues[$sKey])
							{
								$this->tableValues[$sKey]=$gValue;
								$this->tableTotalValues[$sKey]=$gValue;
							}
							break;

						case 'greater':
							if ($gValue>=$this->tableValues[$sKey])
							{
								$this->tableValues[$sKey]=$gValue;
								$this->tableTotalValues[$sKey]=$gValue;
							}
							break;
					}

				}
			}

		}
		$this->lastColMatrix=$colMatrix;
		$sai = $sai . tagMe('tr '.$add, $row, $parTr) . $this->n;
		$this->tableFirstRow = false;
		return($sai);
	}

	/**
	 * Monta estrutura de final da tabela
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @return type
	 */
	function tableEnd()
	{
		// Mostra totalizadores (sumário parcial)
		if (is_array($this->tableTotals))
		{
			if (is_array($this->tableGroups))
			{
				$mostrouLabel=false;
				$sumRow='';
				for ($i=0; $i<count($this->lastColMatrix); $i++)
				{

					if (isset($this->tableValues[$i]))
					{
						if ($this->tableTotals[$i]['formula']=='count')
							$sumRow.='<td align="right">'.intval($this->tableValues[$i]).'</td>';
						else
							$sumRow.='<td align="right">'.gFloat($this->tableValues[$i]).'</td>';
					} else
					{
						if (!isset($this->tableGroups[$i]))
						{
							if (isset($this->tableTotals[($i+1)]) && (!$mostrouLabel))
							{
								$mostrouLabel=true;
								$sumRow.='<td align="right">'.gT("Sub-total").'</td>';
							}
							else
								$sumRow.='<td></td>';

						}
					}
				}
				$sai = $sai . tagMe('tr', $sumRow, 'class="success"') . $this->n;
				foreach ($this->tableValues as $sKey=>$sValue)
				{
					$this->tableValues[$sKey]=0;
				}
			}
			$mostrouLabel=false;
			$sumRow='';
			for ($i=0; $i<count($this->lastColMatrix); $i++)
			{

				if (isset($this->tableTotalValues[$i]))
				{
					if ($this->tableTotalValues[$i]['formula']=='count')
						$sumRow.='<td align="right">'.intval($this->tableTotalValues[$i]).'</td>';
					else
						$sumRow.='<td align="right">'.gFloat($this->tableTotalValues[$i]).'</td>';
				} else
				{
					if (!isset($this->tableGroups[$i])){
						if (isset($this->tableTotals[($i+1)]) && (!$mostrouLabel))
						{
							$mostrouLabel=true;
							$sumRow.='<td align="right">'.gT("Total").'</td>';
						}
						else
							$sumRow.='<td></td>';
					}
				}
			}
			$sai = $sai . tagMe('tr', $sumRow, 'class="success"') . $this->n;
		}

		$sai.='</' . $this->tableSegment . '>';
		$sai.='</table></div>' . $this->n;
		$this->tableTotals=null;
		$this->tableValues=null;
		$this->tableTotalValues=null;
		$this->tableGroups=null;
		return($sai);
	}

	/**
	 * Adiciona itens a serem renderizados em uma conjunto de abas
	 * @author	bruno
	 * @version	4.0 03-12-2014 09:30
	 * @param string $tab Nome da aba
	 * @param string $content Conteudo a ser renderizado dentro da aba
	 * @return type
	 */
	function addTabItem($tab,$content)
	{
		$g = !empty($_REQUEST['g'])? gCleanField($_REQUEST['g']) : '';

		//$tabId=uniqid("gtab-");
		$tabId = "gtab-".$g.$gPage."-".$this->gTabsId.(count($this->gTabs));
		$this->gTabs[$tabId]['name']=$tab;
		$this->gTabs[$tabId]['content']=$content;
	}

	/**
	 * Retorna o conteudo html para renderização das abas
	 * @author	bruno
	 * @version	4.0 03-12-2014 09:30
	 * @param string $class Classe da aba
	 * @return type
	 */
	function tabRender($class="", $tab_cookie = true)
	{

		$g = !empty($_REQUEST['g'])? gCleanField($_REQUEST['g']) : '';

		if (($class<>"") && ($class=="left" || $class=="right" || $class=="below"))
			$class='tabs-'.$class;
		else
			$class="";

		$sai="
				<div id='gtab$this->gTabsId' class='tabbable $class'>
					<ul class='nav nav-tabs'>";
						$isFirst=true;
						foreach ($this->gTabs as $key => $value)
						{
							$f="";
							if($isFirst)
							{
								$f="class='active'";
								$isFirst=false;
							}
							$sai.="<li $f><a href='#$key' aria-controls='$key' role='tab' data-toggle='tab'>".$value['name']."</a></li>";
						}
		$sai.="		</ul>

					<div class='tab-content'>";
					$isFirst=true;
					foreach ($this->gTabs as $key => $value)
					{
						$f="";
						if($isFirst)
						{
							$f="active";
							$isFirst=false;
						}
						$sai.="<div class='tab-pane $f' id='$key'>".$value['content']."</div>";
					}
		$sai.="		</div>
				</div>
		";

		if($tab_cookie)
		{
			$this->addJavascript('
				$(document).ready(function() {
					var lastTab'.$this->gTabsId.' = getCookie("last_tab_'.$g.$gPage.$this->gTabsId.'");

					if(lastTab'.$this->gTabsId.' !== "") {
						$("#gtab'.$this->gTabsId.' ul.nav-tabs").children().removeClass("active");
						$("#gtab'.$this->gTabsId.' a[href="+ lastTab'.$this->gTabsId.' +"]").parents("li:first").addClass("active");
						$("#gtab'.$this->gTabsId.' div.tab-content").children().removeClass("active");
						$(lastTab'.$this->gTabsId.').addClass("active");
					}
				});

				$("#gtab'.$this->gTabsId.' a[data-toggle=\"tab\"]").on("shown.bs.tab", function (event) {
					event.preventDefault();
					setCookie("last_tab_'.$g.$gPage.$this->gTabsId.'", $(event.target).attr("href"),1);
				});
			');
		}

		$this->gTabs = array();
		$this->gTabsId++;

		return $sai;
	}

	/**
	 * Gera um elemento Accordion do Boostrap
	 *
	 * @author André Luiz
	 * @version 1.0 23-03-2017 11:42
	 * @param  array  $dados em cada posição deve ter um array com duas posições: 0 = títutlo , 1 = corpo
	 * @param  string $identificador diferencia dois ou mais acoordions numa mesma página
	 * @param  int $collapsed indica qual aba será exibida ao carregar
	 * @return string HTML
	 */
	public function accordion($dados = array(), $identificador = '', $collapsed = 0)
	{
		$sai = '';

		if(!empty($dados))
		{
			$sai .= "<div class='panel-group' id='accordion_{$identificador}'>";
			$aux = 0;
			foreach ($dados as $key => $value)
			{
				$in = $aux == $collapsed ? 'in' : '';
				$sai .= "<div class='panel panel-default'>
						   <div class='panel-heading'>
							 <h4 class='panel-title'>
							   <a data-toggle='collapse' data-parent='#accordion_{$identificador}' href='#collapse{$aux}_{$identificador}'>
							   {$value[0]}
							   </a>
							 </h4>
						   </div>
						   <div id='collapse{$aux}_{$identificador}' class='panel-collapse collapse $in'>
							 <div class='panel-body'>
							 {$value[1]}
							 </div>
						   </div>
						</div>";
				$aux++;
			}

			$sai .= "</div>";
		}

		return $sai;
	}

	// ============================= métodos não adaptados

	/**
	 *
	 * @param type $var
	 * @param type $txt
	 * @param type $default
	 * @return type
	 */
	static function _cssIf($var, $txt, $default = "")
	{
		if ($txt <> "") {
			$txt = "$var: '$txt'";
		} else {
			$txt = $default;
		}
		return($txt);
	}

	/**
	 *
	 * @param type $var
	 * @param type $txt
	 * @param type $default
	 * @return type
	 */
	static function _parIf($var, $txt, $default = "")
	{
		if ($txt <> "") {
			$txt = "$var='$txt'";
		} else {
			$txt = $default;
		}
		return($txt);
	}

	/**
	 *
	 * @param type $sep
	 * @param type $arr
	 * @return type
	 */
	static function _parImplode($sep, $arr)
	{
		$new = "";
		foreach ($arr as $el) {
			if ($el <> "") {
				$new[] = $el;
			}
		}
		$sai = implode($sep, $new);
		if ($sep == ";") {
			$sai = str_replace("'", "", $sai);
		}
		return ($sai);
	}

	/**
	 *
	 * @global type $gPathImg
	 * @global type $gPath
	 * @global type $gSystemPathImg
	 * @global type $http_base
	 * @global type $http_img
	 * @global type $http_system_img
	 * @param string $file
	 * @param type $replace
	 * @param type $idDono
	 * @return type
	 */
	static function imagePath($file, $replace = true, $idDono = 0)
	{
		global $gPathImg, $gPath, $gSystemPathImg, $http_base, $http_img, $http_system_img;

		if (strpos($file, ".") === false) {
			$file.=".png";
		}
		$gApp = $_SESSION['gApp'];
		$gApps = $_SESSION['gApps'];
		if ($idDono == 0) {
			$idDono = $_SESSION['usrAppId'];
		}
		$pasta[$gSystemPathImg . "/"] = $http_system_img . "/";
		$pasta[$gSystemPathImg . "/icons/"] = $http_system_img . "/icons/";
		$pasta[$gPath . "/"] = $http_base . "/";
		$pasta[$gPathImg . "/"] = $http_img . "/";
		$pasta[$gPathImg . "/icons/"] = $http_img . "/icons/";
		$pasta[$gPathImg . "usr/" . str_pad($gApps[$gApp]['id_pessoas_dono'], 9, "0", STR_PAD_LEFT) . "/"] = $http_base . "pub/img/usr/" . str_pad($gApps[$gApp]['id_pessoas_dono'], 9, "0", STR_PAD_LEFT) . "/";
		$pasta[$gPathImg . "usr/" . str_pad($idDono, 9, "0", STR_PAD_LEFT) . "/"] = $http_base . "pub/img/usr/" . str_pad($idDono, 9, "0", STR_PAD_LEFT) . "/";

		$sai = "";

		if (strpos($file,'/')===false)
		{
			$sai=$file;
		} else
		{
			$f=explode('/', $file);
			$sai=$f[count($f)-1];
		}
		if (strpos($sai, '.')!==false)
		{
			$f=explode('.', $sai);
			$sai=$f[0];
		}
		// gFW3
		// foreach ($pasta as $local => $http) {
		// 	if ((file_exists($local . $file)) && ($sai == "")) {
		// 		$sai = $http . $file;
		// 	} elseif ((file_exists($local . "16/" . $file)) && ($sai == "")) {
		// 		$sai = $http . "16/" . $file;
		// 	} elseif ((file_exists($local . "32/" . $file)) && ($sai == "")) {
		// 		$sai = $http . "32/" . $file;
		// 	} elseif ((file_exists($local . "64/" . $file)) && ($sai == "")) {
		// 		$sai = $http . "64/" . $file;
		// 	}
		// }
		// if ($sai == "") {
		// 	if ($replace) {
		// 		if (strpos($file, '32/') !== false) {
		// 			$sai = self::imagePath('32/b0001', false);
		// 		} elseif (strpos($file, '64/') !== false) {
		// 			$sai = self::imagePath('64/b0001', false);
		// 		} else {
		// 			$sai = self::imagePath('b0001', false);
		// 		}
		// 	} else {
		// 		$sai = $file;
		// 	}
		// }
		return($sai);
	}

	/**
	 * Gera uma TAG HTML de imagem, opcionalmente com link
	 * @author	giuliano
	 * @version	1.0 13-07-2009 18:37
	 * @param string $json Parametros: url, link, id
	 * @param type $idDono
	 * @return string $sai TAG HTML
	 */
	function image($json, $idDono = 0)
	{
		global $gFW_miniIcons;
		$mtz = cssDecode($json);
		$tag = "";
		$parm = "";
		$css = "";

		$tag = tagAdd("img", "id", $mtz['id']);
		$tag = tagAdd($tag, "border", "0");
		$mtz = cssRemove($mtz, "id");
		$tag = tagAdd($tag, "class", $mtz['class'], "img-responsive");
		$mtz = cssRemove($mtz, "class");
		$tag = tagAdd($tag, "alt", $mtz['alt']);
		$mtz = cssRemove($mtz, "alt");
		$target = $mtz['target'];
		$tag = tagAdd($tag, "target", $mtz['target']);
		$mtz = cssRemove($mtz, "target");

		$caption = $mtz['caption'];
		$description = gT($mtz['description']);
		$hint = gT($mtz['hint']);
		$url = $mtz["size"] . "/" . $mtz["url"];
		$href = $mtz["href"];
		$effect = $mtz["effect"];
		$mtz = cssRemove($mtz, "caption");
		$mtz = cssRemove($mtz, "description");
		$mtz = cssRemove($mtz, "hint");
		$mtz = cssRemove($mtz, "url");
		$mtz = cssRemove($mtz, "size");
		$mtz = cssRemove($mtz, "href");
		$mtz = cssRemove($mtz, "effect");

		//$tag = tagAdd($tag, "src", self::imagePath($url, true, $idDono));
		$tag = tagAdd($tag, "style", cssEncode($mtz));

		$ev = "onClick=\"gWait()\"";
		if ($effect <> "") {
			if (strtolower($effect) == "fadeout") {
				$ev = "onClick='gWait(1)'";
			}
			if (strtolower($effect) == "ghost") {
				$ev = "onClick='gWait(2)'";
			}
			if (strtolower($effect) == "puff") {
				$ev = "onClick='gWait(3)'";
			}
		}
		if ($href <> "") {
			if ($target <> '') {
				$target = "target='$target'";
			}
			if ((strpos($href, ".php") === false) && (strpos($href, ".html") === false) && (strpos($href, "www") === false) && (strpos($href, "http") === false)) {
				$tag = "<a class='btn btn-default' $target href='javascript:" . html_entity_decode($href) . "' data-toggle='tooltip' data-placement='bottom' title='$hint'>" . $tag;
			} else {
				$tag = "<a class='btn btn-default' $target href='" . html_entity_decode($href) . "' data-toggle='tooltip' data-placement='bottom' title='$hint' $ev>" . $tag;
			}
		}
		if ($caption <> "") {
			$style = "margin: 4px";
			$tag.="<br /><span class=\"g-img-caption\">" . $caption . "</span>";
		}
		if ($description <> "") {
			$style = "margin: 4px";
			$tag.="<br /><span class=\"g-img-description\">" . $description . "</span>";
		}
		$tag.='<span class="fa fa-fw fa-'.$gFW_miniIcons[self::imagePath($url, true, $idDono)].'"></span>';
		//$tag.=self::imagePath($url, true, $idDono);
		if ($href <> "") {
			$tag.="</a>";
		}

		return($tag);
	}

	/**
	 * Gera uma TAG HTML de ícone, opcionalmente com link
	 * @author	giuliano
	 * @version	1.0 13-07-2009 18:37
	 * @global type $http_system_img
	 * @global type $gSystemPathImg
	 * @global type $http_img
	 * @global type $gPathImg
	 * @param string $json Parametros: url, link, id
	 * @return string $sai TAG HTML
	 */
	static function icon($json)
	{
		global $http_system_img, $gSystemPathImg;
		global $http_img, $gPathImg;

		$mtz = cssDecode($json);
		$tag = "";
		$size = empty($mtz['size']) ? "32" : $mtz['size']; // default
		$parm = "";
		$css = "";
		$url = $size . "/" . $mtz['url'];
		/*
		  if (file_exists($gPathImg."usr/".$mtz['url']))
		  $url=$http_img."usr/".$mtz['url'];
		  $imagem="icons/$size/".$mtz['url'].".png";
		  if (file_exists($gPathImg.$imagem))
		  $url=$http_img.$imagem;
		  if (file_exists($gSystemPathImg.$imagem))
		  $url=$http_system_img.$imagem;
		 */

		$parm[] = self::_parIf("id", $mtz["id"]);
		$parm[] = self::_parIf("border", $mtz["border"], "border='0'");
		$parm[] = self::_parIf("class", $mtz["class"], "class='g-icon'");
		$parm[] = self::_parIf("alt", $mtz["alt"]);

		$css[] = self::_cssIf("float", $mtz['float']);
		if ($mtz["href"] <> "") {
			$tag.="<a class='g-link-icon' href='" . $mtz["href"] . "'>";
			$style.="margin: 4px";
		}
		if ($css <> "") {
			$parm[] = "style=\"" . self::_parImplode(";", $css) . "\"";
		}
		if (($mtz['type'] == 'glyphicon') || ($mtz['type'] == 'fa')) {
			$tag.=self::chooseIcon($mtz['url'], $mtz['type'], $size);
		} else {
			$tag.="<img src='" . self::imagePath($url) . "' " . self::_parImplode(" ", $parm) . " title='" . $mtz['title'] . "'/>";
		}

		if ($mtz["caption"] <> "") {
			$tag.="<br /><i class='g-icon-caption'>" . $mtz['caption'] . "</i>";
		}
		if ($mtz["href"] <> "") {
			$tag.="</a>";
		}
		return($tag);
	}

	function gIcon($json)
	{
		return(self::icon($json));
	}

	/**
	 * Gera uma TAG HTML de link para outra página
	 * @author	giuliano
	 * @version	1.0 13-07-2009 18:37
	 * @param string $json Parametros: link, id, caption, alt
	 * @return string $sai TAG HTML
	 */
	function link($json)
	{
		$http_img = $this->http_img;
		$mtz = cssDecode($json);

		$tag = tagAdd("a", "id", $mtz['id']);
		$mtz = cssRemove($mtz, "id");
		$tag = tagAdd($tag, "class", $mtz['class'], "g-link");
		$mtz = cssRemove($mtz, "class");
		$tag = tagAdd($tag, "alt", $mtz['alt']);
		$mtz = cssRemove($mtz, "alt");
		$tag = tagAdd($tag, "href", $mtz['href']);
		$mtz = cssRemove($mtz, "href");
		$caption = $mtz['caption'];
		$mtz = cssRemove($mtz, "caption");
		$url = $mtz['url'];
		$mtz = cssRemove($mtz, "url");
		$tag = tagAdd($tag, "style", cssEncode($mtz));
		if ($url) {
			if ($mtz["renderTo"] <> "") {
				//$tag=substr($tag,0,strlen($tag)-1)." href='#' onClick='$(\"#".$mtz['renderTo']."\").load(\"".$url."\");'>";
				$tag = substr($tag, 0, strlen($tag) - 1) . " href='#' onClick='ajaxLoadPage(\"$url\",\"" . $mtz['renderTo'] . "\");'>";
			} else {
				//$tag=substr($tag,0,strlen($tag)-1)." href='#' onClick='$(\"#g-body-box\").load(\"".$url."\");'>";
				$tag = substr($tag, 0, strlen($tag) - 1) . " href='#' onClick='ajaxLoadPage(\"$url\",\"g-body-box\");'>";
			}
		}
		$tag.="$caption</a>";

		return($tag);
	}


	/**
	 * Gera uma TAG HTML de separação entre links
	 * @author	giuliano
	 * @version	1.0 13-07-2009 18:37
	 * @param string $json Parametros: char
	 * @return string $sai TAG HTML
	 */
	function linkSeparator($json = "")
	{
		$http_img = $this->http_img;
		$mtz = jsDecode($json);
		$tag = "";
		$char = " | ";
		if ($mtz['char'] <> "") {
			$char = $mtz['char'];
		}
		$tag.="<span class='g-link-separator'>$char</span>";
		if ($this->useBuffer) {
			$this->buffer.=$tag;
			$tag = "";
		}
		return($tag);
	}

	/**
	 * Gera um botão
	 * @author	giuliano
	 * @version	1.0 08-12-2013
	 * @param json $json Parametros: caption, url, style (bootstrap)
	 * @param type $js
	 * @return string $sai TAG HTML
	 */
	function button($json, $js = '')
	{
		$jarr = cssDecode($json);
		$type = $jarr['type'];
		$tag = "a"; // o padrão é "a" e não "button"
		$active="";
		$caption = $jarr['title'];

		if ($jarr['caption']<>'')
			$caption=$jarr['caption'];
		$icon = '';
		if ($caption <> '') {
			$caption = gT($caption);
		}
		$style = trim($jarr['style']);
		if ($this->bootstrapVersao==3)
		{
			if ($style == '') {
				$style =  'default';
			}

		} else {
			if ($style == '' || $style=='default') {
				$style =  "outline-primary";
			}

		}
		$size = '';
		if ($jarr['size'] <> '') {
			$size = $jarr['size'];
		}
		if ($size<>'small')
		{
			$parm['style']='margin-bottom: 4px'; // Corrigindo bug de responsividade do bootstrap
		}
		if ($jarr['css'] <> "")
		{
			$parm['style'] = $jarr['css'];
		}
		if($jarr['disabled']=='true' || $jarr['disabled']=='disabled')
		$parm['disabled'] = 'disabled';
		if($jarr['showWait']<>"false" && !isset($jarr['target']))
		{
			if($jarr['showWait']=="ajax")
			{
				$parm['onClick']='showWaitAjax(this);';
			}
			else{
				$parm['onClick']='showWait(this);';
			}

		}

		if ($jarr['onClick']<>'')
		{
			$parm['onClick']=$parm['onClick'].$jarr['onClick'];
		}
		$parm['class'] = $this->bootstrapTags['hidden-print']." btn btn-" . $style;
		$parm['class'].=' '.$active;
		$parm['class']=trim($parm['class']);

		if ($jarr['block']=="true")
			$parm['class'].=' btn-block';

		switch ($size)
		{
			case 'big':
			case 'large':
				$parm['class'].=' btn-sm';
				break;

			case 'small':
				$parm['class'].=' btn-sm';
				break;

			case 'extra-small':
				$parm['class'].=' btn-xs';
			case 'tiny':
				$parm['class'].=' btn-xs';
				break;

		}
		if($jarr['active']=='true'){
			$parm['class']=$parm['class'].' active';
		}


		if ($jarr['block'] == "true") {
			$parm['class'].=' btn-block';
		}


		if ($jarr['tag'] <> "") {
			$tag = $jarr['tag'];
		}


		if ($jarr['name'] <> "") {
			$parm['name'] = $jarr['name'];
		}

		if ($jarr['openModal'] <> '') {
			unset($jarr['hint']);
			unset($parm['onClick']);
			$parm['data-toggle'] = "modal";
			$parm['data-target'] = '#' . $jarr['openModal'];
		}
		if($jarr['target']<>'')
		{
			$parm['target']=$jarr['target'];
		}

		if ($jarr['href'] <> "") {
			$jarr['href']=str_replace('"',"'", $jarr['href']);
			if ($jarr['href']=='back')
			{
				$parm['href'] =  'javascript:window.history.back()';
			} else
			{
				$parm['href'] = $jarr['href'];
			}
			$parm['role'] = 'button';
		} elseif ($jarr['url'] <> "") {
			$jarr['url']=str_replace('"',"'", $jarr['url']);
			$parm['role'] = 'button';
			//$tag = 'a';
			if ((strpos($jarr['url'], 'http') !== false) || (substr($jarr['url'], 0, 1) == '/')) {
				// URL
				//$parm['href'] = $jarr['url'];
				if ($tag == 'a')
					$parm['href'] = ($jarr['url']);
				else
					$parm['onClick'] = $parm['onClick'].'document.location.href=\''.$jarr['url'].'\'';
			} else {
				// Javascript
				if ($tag == 'a')
					$parm['href'] = ($jarr['url']);
				else
					$parm['onClick'] = $parm['onClick'].str_replace('javascript:','',$jarr['url']);
			}
		}
		if ($jarr['icon'] <> "") {
			$icon = self::icon("{url: " . $jarr['icon'] . "; size: ".$size."; type: fa}");
		}

        if ($jarr['id'] <> '') {
			$parm['id'] = $jarr['id'];
		}

		$jarr['confirm'] = !empty($jarr['confirm'])? (boolean) $jarr['confirm'] : false;
		if($jarr['confirm']){
			unset($jarr['hint']);
			if (isset($jarr['href']))
				$jarr['url']=$jarr['href'];
			unset($parm['onClick']);
			$btn_confirm_id = uniqid('');
			$this->out($this->modal('{title: '.(empty($jarr['confirmTitle'])? 'Confirme' :  $jarr['confirmTitle']).'; content: '.(empty($jarr['confirmContent'])? 'Tem certeza que deseja realizar esta operação?' :  $jarr['confirmContent']).'; name: modal-confirm-'.$btn_confirm_id.'; href: '.$jarr['url'].'}'),gLOC_POS);
			$parm['data-toggle'] = 'modal-confirm';
			$parm['data-target'] = 'modal-confirm-'.$btn_confirm_id;
		}
		if ($js <> "") {
			if (strpos($js, '.href')!==false || strpos($js, '.submit')!==false)
				$parm['onClick'] = $parm['onClick'].str_replace('javascript:','',$js);
			else
				$parm['onClick'] = $js;
		}

		if ($jarr['hint'] <> "") {
			$parm['data-toggle']="tooltip";
			$parm['data-placement']="bottom";
			$parm['title']=gT($jarr['hint']);
			//data-toggle="tooltip" data-placement="right" title="

			// $sai.='<div class="tooltip"><div class="tooltip-inner">' . $h . '</div><div class="tooltip-arrow"></div></div>';
			// //$parm['title'] = $h;
			// $parm['data-original-title']=$h;
			if (!$this->tooltipButton && $tag=="button")
			{
				$this->tooltipButton=true;
				$this->addJavascript("$('button').tooltip();");
			}
			if (!$this->tooltipA && $tag=="a")
			{
				$this->tooltipA=true;
				$this->addJavascript("$('a').tooltip();");
			}
			if (!$this->tooltipAcronym && $tag=="acronym")
			{
				$this->tooltipAcronym=true;
				$this->addJavascript("$('acronym').tooltip();");
			}
			if (!$this->tooltipInput && $tag=="a")
			{
				$this->tooltipInput=true;
				$this->addJavascript("$('input').tooltip();");
			}
		}
		if ($jarr['responsive']=='true')
		{
			$captionPre='<span class="'.$this->bootstrapTags['hidden-sm'].' '.$this->bootstrapTags['hidden-xs'].' '.$this->bootstrapTags['hidden-md'].' visible-lg-inline-block">';
			$captionPos='</span>';
		} else
		{
			$captionPre=$captionPos='';
		}
//echo "<pre>";print_r($parm);echo "</pre>";

		switch($type) {
			case 'submit':
				$sai.='<input type="' . $type . '" value="' .$caption . '" ';
				unset($parm['onClick']);
				if ($jarr['showWait'] <> "false")
					$this->addJavascript("$('#".$this->formId."').submit(function () { showWait(); return true;});");
				foreach ($parm as $key => $value) {
					$sai.=" " . $key . '="' . $value . '"';
				}
				$sai.='>';
				break;
			case 'a':
				$sai = tagMe($type, $icon . $captionPre.$caption.$captionPos, $parm);
				break;

			default:
				$sai = tagMe($tag, $icon . $captionPre.$caption.$captionPos, $parm);
				break;
		}
		if ($jarr['block'] == "true")
			return($sai );
		else
			return($sai . "&nbsp;");
	}


	function angularLink($url)
	{
		//index.php?g=xxxxx
		if ($this->useAngular && (strpos($url,'?g=')!==false))
		{
			$u=$url;
			$url=substr($url,strpos($url,'?g='));
			$url=str_replace('?g=','#/', $url);
			$parm=substr($url,strpos($url,'&'));
			//$url=substr($url,0,strpos($url,'&'));
			//$url.='/gPage';
			$this->angularLinks[str_replace('#/','',$url)]=$u."&gAjs=1";
		}
		return($url);
	}

	/**
	 * Gera um menu dropdown
	 * @author	giuliano
	 * @version	1.0 08-12-2013
	 * @param json $json Parametros: caption, url, style (bootstrap)
	 * @param type $js
	 * @return string $sai TAG HTML
	 */
	function dropdown($json, $links = "", $popover = '')
	{
		$sai = '';
		$this->dropdownCount++;
		$jarr = cssDecode($json);
		$caption = $jarr['title'];

		if ($caption == '') {
			$caption = '...';
		} else {
			$caption = gT($caption);
		}
		$style = "default";
		if ($jarr['style'] <> '') {
			$style = $jarr['style'];
		}
		if ($jarr['type'] <> '') {
			$style = $jarr['type'];
		}


		if ($this->bootstrapVersao==3)
		{
			if ($style == "default")
				$sai = "		<button id='dropDownGroup" . $this->dropdownCount . "' type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown'>" . $caption . " <span class='caret'></span></button>" . $this->n;
			else
				$sai = '		<li class="dropdown"><a href="" id="dropDownGroup' . $this->dropdownCount . '" class="dropdown-toggle" data-toggle="dropdown">' . $caption . ' <b class="caret"></b></a>' . $this->n;
			if (is_array($links)) {
				if ($jarr['type'] == 'bar')
					$sai.="			<ul class='dropdown-menu' role='menu' aria-labelledby='dropDownGroup" . $this->dropdownCount . "'>" . $this->n;
				else
					$sai.="			<ul class='dropdown-menu navmenu-nav' role='menu' aria-labelledby='dropDownGroup" . $this->dropdownCount . "'>" . $this->n;
				// role='menu' aria-labelledby='dropDownGroup1'

				foreach ($links as $key => $value) {
					$key = gT($key);
					if ($value <> "") {
						if ($value == "-")
							$sai.='				<li role="presentation" class="divider">';
						elseif (strpos($value,'dropdown-submenu')!==false)
							$sai.=$value;
						else{
							// angularLink
							$sai.='				<li'.(!empty($popover[$key])? ' data-toggle="popover" data-trigger="hover" data-placement="right" data-content="'.$popover[$key].'"' : '').'><a href="' . ($value) . '" onClick="showWait()">' . $key . '</a>';
						}
					} else {
						$sai.='				<li role="presentation" class="dropdown-header">';
						$sai.=$key;
					}
					$sai.="</li>" . $this->n;
				}
				$sai.="			</ul>" . $this->n;

			}
			if ($style == 'default') {
				$sai = tagMe("div", $sai, "class='btn-group'");
				$sai = tagMe("div", $sai, "class='btn-group-vertical'") . $this->n;
			} else {
				$sai.="		</li>";
			}

		} else {

			// if ($style == "default")
			//  	$sai = "		<button id='dropDownGroup" . $this->dropdownCount . "' type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown'>" . $caption . " <span class='caret'></span></button>" . $this->n;
			//  else
			// 	$sai = '		<li class="nav-item"><a href="" id="dropDownGroup' . $this->dropdownCount . '" class="dropdown-toggle" data-toggle="dropdown">' . $caption . ' <b class="caret"></b></a>' . $this->n;

			if (is_array($links)) {

				$primeiro = true;
				foreach ($links as $key => $value)
				{

					$key = gT($key);

					if ($primeiro)
					{
						$sai.="		<li class='nav-item dropdown'>" . $this->n;
						$sai.="			<a href='#' class='nav-link dropdown-toggle' role='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' id='dropDownGroup" . $this->dropdownCount . "'>" . $caption  . "</a>" . $this->n;
						$sai.="			<div class='dropdown-menu' aria-labelledby='dropDownGroup" . $this->dropdownCount . "'>" . $this->n;
					}

					$primeiro=false;

					if ($value <> "") {
						if ($value == "-")
							$sai.='				<div class="dropdown-divider"></div>' . $this->n;
						elseif (strpos($value,'dropdown-submenu')!==false)
							$sai.=$value;
						else{
							// angularLink
							$sai.='				<a class="dropdown-item" href="' . ($value) . '" onClick="showWait()">' . $key . '</a>' . $this->n;
						}
					} else {
						//$sai.='				<span class="navbar-text">'.$key.'</span>' . $this->n;
						$sai.='				<span>&nbsp;&nbsp;&nbsp;<small>'.$key . '</small></span>'.$this->n;
					}
				}

				$sai.="			</div>" . $this->n;
				$sai.="		</li>" . $this->n;

			}
			// $sai.="		</li>" . $this->n;
			// if ($style == 'default') {
			// 	$sai = tagMe("div", $sai, "class='btn-group'");
			// 	$sai = tagMe("div", $sai, "class='btn-group-vertical'") . $this->n;
			// } else {
			// 	$sai.="		</li>";
			// }
		}

		return($sai);
	}

	/**
	 * Gera um submenu
	 * @author	giuliano
	 * @version	1.0 08-04-2014
	 * @param json $json Parametros: caption, url, style (bootstrap)
	 * @param type $js
	 * @return string $sai TAG HTML
	 */
	function submenu($json, $links = "")
	{
		$sai = '';
		$this->dropdownCount++;
		$jarr = cssDecode($json);
		$caption = $jarr['title'];
		if ($caption == '') {
			$caption = '...';
		} else {
			$caption = gT($caption);
		}
		$style = "default";
		if ($jarr['style'] <> '') {
			$style = $jarr['style'];
		}
		if ($jarr['type'] <> '') {
			$style = $jarr['type'];
		}

		if ($this->bootstrapVersao==3)
		{

			if ($style == "default")
				$sai = "		<button id='dropDownGroup" . $this->dropdownCount . "' type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown'>" . $caption . " <span class='caret'></span></button>" . $this->n;
			else
				$sai = '<li class="dropdown-submenu"><a tabindex="-1" href="#">' . $caption . '</a>' . $this->n;
			if (is_array($links)) {
				if ($jarr['type'] == 'bar')
					$sai.="			<ul class='dropdown-menu'>" . $this->n;
				else
					$sai.="			<ul class='dropdown-menu navmenu-nav'>" . $this->n;
				// role='menu' aria-labelledby='dropDownGroup1'
				foreach ($links as $key => $value) {
					$key = gT($key);
					if ($value <> "") {
						if ($value == "-")
							$sai.='				<li class="divider">';
						else
							$sai.='				<li><a href="' . $value . '" onClick="showWait()">' . $key . '</a>';
					} else {
						$sai.='				<li>';
						$sai.=$key;
					}
					$sai.="</li>" . $this->n;
				}
				$sai.="			</ul>" . $this->n;
			}
			if ($style == 'default') {
				$sai = tagMe("div", $sai, "class='btn-group'");
				$sai = tagMe("div", $sai, "class='btn-group-vertical'") . $this->n;
			} else {
				$sai.="		</li>";
			}
		} else {
				$primeiro = true;
				// foreach ($links as $key => $value)
				// {

				// 	$key = gT($key);

				// 	if ($primeiro)
				// 	{
				// 		$sai.= $this->n;
				// 		$sai.= '		<div class="dropdown-divider"></div>' . $this->n;
				// 		$sai.= '		<small>'.$caption.'</small><br>' . $this->n;
				// 	}

				// 	$primeiro=false;

				// 	if ($value <> "") {
				// 		if ($value == "-")
				// 			$sai.='				<div class="dropdown-divider"></div>' . $this->n;
				// 		elseif (strpos($value,'dropdown-submenu')!==false)
				// 			$sai.=$value;
				// 		else{
				// 			$sai.='				<a class="dropdown-item" href="' . ($value) . '" onClick="showWait()"><small>' . $key . '</small></a>' . $this->n;
				// 		}
				// 	} else {
				// 		//$sai.='				<span class="navbar-text">'.$key.'</span>' . $this->n;
				// 		$sai.='				<span>&nbsp;&nbsp;&nbsp;<small>'.$key . '</small></span>'.$this->n;
				// 	}
				// }


		}
		return($sai);
	}

	function navLinks($links, $navType)
	{
		$sai = '';
		$first = true;

		if ($this->bootstrapVersao==3)
		{
			if (is_array($links)) {
				if (($navType == 'navbar') || ($navType == 'navmenu')) {
					$sai.='	<ul class="nav ' . $navType . '-nav">' . $this->n;
					//$sai.='	<ul class="dropdown-menu">' . $this->n;
				}
				$pos = '';
				foreach ($links as $key => $value) {
					$activeThis = '';
					//if (($first) && ($active == ''))
					//	$activeThis=' class="active"';
					if (is_array($value)) {
						$css = cssDecode($value[0]);
						$more = '';
						foreach ($css as $k => $v) {
							if ($k == 'layout') {
								if ($v == 'inline') {
									$pos = $value[1];
								}
							} else {
								$more.=' ' . $k . '="' . $v . '"';
							}
						}
						if ($pos == '') {
							$v = $value[1];
							$sai.='		<li ' . $more . $activeThis . '>' . $v . '</li>' . $this->n;
						}
					} else {
						if ((substr(trim($value), 0, 1) == '<')){
							$sai.=$value . $this->n;
						} elseif (substr(trim($value), 0, 1) == '{') { // Parâmetros em formato JSON
							$farr = cssDecode($value);
						} elseif ($value <> '') {
							if (stripos($this->page, $value) !== false)
								$activeThis = ' class="active"';
							$sai.='		<li' . $activeThis . '><a href="' . $value . '" onClick="showWait()">'. $key . '</a></li>' . $this->n;
						} else {

							$sai.='		<li' . $activeThis . '>' . $key . '</li>' . $this->n;
						}
					}
					$first = false;
				}
				if (($navType == 'navbar') || ($navType == 'navmenu'))
					$sai.='	</ul>' . $this->n;
			} else {
				$sai.=$links;
			}
			$sai.=$pos;

		} else {

			if (is_array($links)) {
				if (($navType == 'navbar') || ($navType == 'navmenu'))
				{
					$sai.='	<ul class="' . $navType . '-nav mr-auto">' . $this->n;
					//$sai.='	<ul class="dropdown-menu">' . $this->n;
				}
				$pos = '';
				foreach ($links as $key => $value)
				{
					$activeThis = '';
					//if (($first) && ($active == ''))
					//	$activeThis=' class="active"';
					if (is_array($value)) {
						$css = cssDecode($value[0]);
						$more = '';
						foreach ($css as $k => $v) {
							if ($k == 'layout') {
								if ($v == 'inline') {
									$pos = $value[1];
								}
							} else {
								$more.=' ' . $k . '="' . $v . '"';
							}
						}
						if ($pos == '') {
							$v = $value[1];
							$sai.='		<li ' . $more . $activeThis . '>' . $v . '</li>' . $this->n;
						}
					} else {
						if ((substr(trim($value), 0, 1) == '<')){
							$sai.=$value . $this->n;
						} elseif (substr(trim($value), 0, 1) == '{') { // Parâmetros em formato JSON
							$farr = cssDecode($value);
						} elseif ($value <> '') {
							if (stripos($this->page, $value) !== false)
								$activeThis = ' class="active"';
							$sai.='		<li' . $activeThis . '><a href="' . $value . '" onClick="showWait()">' . $key . '</a></li>' . $this->n;
						} else {

							$sai.='		<li' . $activeThis . '>' . $key . '</li>' . $this->n;
						}
					}
					$first = false;
				}
				if (($navType == 'navbar') || ($navType == 'navmenu'))
					$sai.='	</ul>' . $this->n;
			} else {
				$sai.=$links;
			}
			$sai.=$pos;

		}

		return($sai);
	}

	/**
	 * Gera uma barra de menu (vertical ou horizontal)
	 * @author	giuliano
	 * @version	1.0 23-01-2014
	 * @global type $http_img
	 * @param json $json Parametros: {title: título; type: [bar, menu, pills]; url: link; logo: .jpg; style: [default,inverse]; fixed: [top,bottom,left,right]; }
	 * @param array $links Links do menu em um array associativo ou array de parâmetros JSON: {title: texto;  url: link; type: [text,link,form,dropdown,separator]; class: []; }
	 * @return string $sai TAG HTML
	 */
	function nav($json, $links = "", $extraContent="")
	{
		global $http_img, $http_base;


		$sai = '';
		$jarr = cssDecode($json);
		$this->navCount++;
		if ($this->bootstrapVersao>3)
		{
			$style = 'light';
		} else {
			$style = 'default';
		}

		$navTag = 'nav';
		switch($jarr['type']) {
			case 'menu':
				$navType = 'navmenu';
				break;
			case 'bar':
				$navType = 'navbar';
				break;
			default:
				$navType = $jarr['type'];
				break;
		}
		$parm['class'] = $navType;
		//$parm['id'] 	= 'gNav'.$this->navCount;

		if ($this->bootstrapVersao>3)
		{
			$parm['class'] = $parm['class'].' '.$navType.'-expand-lg';
		}

		// Estilo
		if ($jarr['style'] <> '') {
			$style = $jarr['style'];
		}
		$parm['class'] = trim($parm['class']) . " $navType-" . $style;

		if ($this->bootstrapVersao>3)
		{
			$parm['class'] = $parm['class'].' bg-'.$style;
		}

		// Alinhamento
		if (($navType == 'navmenu') && (($jarr['fixed'] == 'left') || ($jarr['fixed'] == 'right') || ($jarr['fixed'] == ''))) {
			if ($jarr['fixed'] == '')
				$jarr['fixed'] = 'left';
			$parm['class'] = trim($parm['class']) . " $navType-fixed-" . $jarr['fixed'] . ' offcanvas-sm';
		}
		if (($navType == 'navbar') && (($jarr['fixed'] == 'top') || ($jarr['fixed'] == 'bottom') || ($jarr['fixed'] == 'true'))) {
			if ($jarr['fixed'] == 'bottom')
				$parm['class'] = trim($parm['class']) . " $navType-fixed-bottom";
			else
				$parm['class'] = trim($parm['class']) . " $navType-fixed-top";
		}

		// URL
		$url = $jarr['url'];
		if ($url <> '') {
			$url = ' href="' . $jarr['url'] . '"';
		}

		// Marca
		if ($jarr['logo'] <> '') {
			$addCss='';
			if ($jarr['maxHeight']<>'')
				$addCss.='style="height: '. $jarr['maxHeight'] . '"';
			if (strpos($jarr['logo'],'/')===false)
				$brand = $this->n . '	<a class="'.$this->bootstrapTags['hidden-xs'].' '.$this->bootstrapTags['hidden-sm'].' navbar-brand"' . $url . ' onClick="showWait()"><img src="' . $http_img . $jarr['logo'] . '" alt="' . $jarr['title'] . '" '.$addCss.'></a>' . $this->n;
			else
				$brand = $this->n . '	<a class="'.$this->bootstrapTags['hidden-xs'].' '.$this->bootstrapTags['hidden-sm'].' navbar-brand"' . $url . ' onClick="showWait()"><img src="' . $http_base . $jarr['logo'] . '" alt="' . $jarr['title'] . '" '.$addCss.'></a>' . $this->n;
		} else {
			if ($jarr['title'] <> '') {
				$brand = $this->n . '	<a class="'.$this->bootstrapTags['hidden-xs'].' '.$this->bootstrapTags['hidden-sm'].' navbar-brand"' . $url . ' onClick="showWait()">' . $jarr['title'] . '</a>' . $this->n;
			}
		}

		// Tipo de Nav
		switch($navType) {
			case "pills":
				$parm['class'] = "nav nav-pills";
				$navTag = 'ul';
				if (is_array($links)) {
					$sai.=$this->navLinks($links, $navType);
				} else {
					$sai = $links;
				}
				break;
			case "tabs":
				$parm['class'] = "nav nav-tabs";
				$navTag = 'ul';
				if (is_array($links)) {
					$sai.=$this->navLinks($links, $navType);
				} else {
					$sai = $links;
				}
				break;

			case "navbar":
				if ($this->bootstrapVersao==3)
				{
					$parm['role'] = "navigation";
					// Botão
					$tog = '<div class="' . $navType . '-header">' . $this->n;
					$tog.='	<button type="button" class="' . $navType . '-toggle" data-toggle="collapse" data-target="#gNav' . $this->navCount . '">' . $this->n;
					$tog.='		<span class="icon-bar"></span>' . $this->n;
					$tog.='		<span class="icon-bar"></span>' . $this->n;
					$tog.='		<span class="icon-bar"></span>' . $this->n;
					$tog.='	</button>' . $this->n;
					$tog.='	' . $brand;
					$tog.='</div>' . $this->n;
					$sai.=$tog;
					$sai.='<div class="' . $navType . '-collapse collapse" id="gNav' . $this->navCount . '">' . $this->n;
					// Itens do menu
					$sai.=$this->navLinks($links, $navType);
					$sai.='</div>' . $this->n;

				} else {
					// Botão
					//$sai.= '<nav class="navbar navbar-expand-lg '.$parm['class'].' bg-light">';
					$sai.= $this->n . $brand . $this->n;
					$sai.= '	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#gNav' . $this->navCount . '" aria-controls="gNav' . $this->navCount . '" aria-expanded="false" aria-label="Acesso ao menu"><span class="navbar-toggler-icon"></span></button>'. $this->n;

					$sai.='	<div class="collapse ' . $navType . '-collapse" id="gNav' . $this->navCount . '">' . $this->n;
					// Itens do menu
					$sai.=$this->navLinks($links, $navType);
					$sai.='	</div>' . $this->n;
					//$sai.='</nav>' . $this->n;
				}

				break;

			case "navmenu":
				if ($this->bootstrapVersao==3)
				{
					// Muda estilo
					$this->out("<style> html, body { height: 100%; } body { padding: 50px 0 0 0; } .navmenu { padding-top: 50px; } .navbar { text-align: center; } .navbar-brand { display: inline-block; float: none; } .navbar-toggle { float: left; margin-left: 15px; position: absolute;} @media (min-width: 992px) { body { padding: 0 0 0 300px; } .navmenu { padding-top: 0; } } </style>" . $this->n, gLOC_POS);
					$parm['role'] = "navigation";
					$tog = '<div class="navbar navbar-default navbar-fixed-top">' . $this->n;
					$tog.='	<button type="button" class="navbar-toggle visible-sm visible-xs" data-toggle="offcanvas" data-target="#' . 'gNav' . $this->navCount . '" data-canvas="body">' . $this->n;
					$tog.='		<span class="icon-bar"></span>' . $this->n;
					$tog.='		<span class="icon-bar"></span>' . $this->n;
					$tog.='		<span class="icon-bar"></span>' . $this->n;
					$tog.='	</button>' . $this->n;
					$tog.='	' . $brand;
					$tog.='</div>' . $this->n;

					if ($jarr['logo'] <> '') {
						$sai.='		<a class="navmenu-brand visible-md visible-lg"' . $url . ' onClick="showWait()"><img src="' . $http_img . $jarr['logo'] . '" alt="' . $jarr['title'] . '"></a>';
					} else {
						if ($jarr['title'] <> '') {
							$sai.='		<a class="navmenu-brand visible-md visible-lg" href="' . $url . '" onClick="showWait()">' . $jarr['title'] . '</a>';
						}
					}
					$posTag = $this->n . $tog . $this->n;


					$sai.=$this->n;
					// Itens do menu - em navmenu não precisa da DIV
					$sai.=$this->navLinks($links, $navType);

				} else {
					//$tog = $brand .'	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#gNav' . $this->navCount . '" aria-controls="gNav' . $this->navCount . '" aria-expanded="false" aria-label="Acesso ao menu"><span class="navbar-toggler-icon"></span></button>';
					// Botão
					//$sai.= '<nav class="navbar navbar-expand-lg '.$parm['class'].' bg-light">';

					$sai.= $this->n . $brand . $this->n;
					$sai.= '	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#gNav' . $this->navCount . '" aria-controls="gNav' . $this->navCount . '" aria-expanded="false" aria-label="Acesso ao menu"><span class="navbar-toggler-icon"></span></button>'. $this->n;

					$sai.='	<div class="collapse ' . $navType . '-collapse" id="gNav' . $this->navCount . '">' . $this->n;
					// Itens do menu
					$sai.=$this->navLinks($links, $navType);
					$sai.='	</div>' . $this->n;
					//$sai.='</nav>' . $this->n;
				}

				break;
		}

		if (($jarr['fixed'] == "top") || ($jarr['type'] == "menu"))
			$posTag.='<div class="container '.$this->bootstrapTags['hidden-sm'].' hidden-xs">&nbsp;</div><div class="container '.$this->bootstrapTags['hidden-sm'].' hidden-xs">&nbsp;</div>' . $this->n;


		$sai = tagMe($navTag, $sai.$extraContent, $parm) . $this->n . $posTag;
		if (gVar("global.menustyle"))
		{
			$sai.="<style>.navbar-default {".gVar("global.menustyle")."}</style>".$sai;
		}


		return($sai);
	}

	function navbar($json, $links = "")
	{
		return($this->nav($json, $links));
	}

	/**
	 * Gera uma barra de menu
	 * @author	giuliano
	 * @version	1.0 10-12-2013
	 * @global type $http_img
	 * @param json $json Parametros:  style (bootstrap)
	 * @brand text (texto ou imagem com a marca)
	 * @brandUrl text (link para href)
	 * @param array $links Links do menu em um array associativo
	 * @return string $sai TAG HTML
	 */
	function menu($json, $links = "")
	{
		// {title: texto; logo: .jpg; style: [default,inverse]; fixed: [true,false]; }
	}

	/**
	 * Gera uma item de dashboard
	 * @author	giuliano
	 * @version	1.0 29-05-2015
	 * @param json $json Parametros: icon, fieldLabel, value, link, size, style
	 * @return string $sai TAG HTML
	 */
	function notification($msg, $style='danger')
	{
		return('<div class="alert alert-'.$style.' alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'.gT($msg).'</div>');
	}

	/**
	 * Gera uma item de dashboard
	 * @author	giuliano
	 * @version	1.0 29-05-2015
	 * @param json $json Parametros: icon, fieldLabel, value, link, size, style
	 * @return string $sai TAG HTML
	 */
	function dashitem($json)
	{
		$jarr=cssDecode($json);
		$icon=$jarr['icon'];
		$fieldLabel=$jarr['fieldLabel'];
		$value=$jarr['value'];
		$link=$jarr['link'];
		$size=$jarr['size'];
		$style=$jarr['style'];
		if ($style=='')
			$style='default';

		switch ($size)
		{
			case 'tiny':
				$ico='';
				if ($icon<>'')
					$ico='<i class="fa fa-'.$icon.' fa-fw"></i> ';
				$url=$ico.$fieldLabel;
				if ($link<>'')
					$url='<a href="'.$link.'">'.$ico.$fieldLabel.'</a>';
				if ($value<>'')
				{
					$html='
					<div class="row">
						<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9 text-left">
							'.$url.'
						</div>
						<div class="col-lg-3 col-md-3 col-sm-3 col-xs-3 text-right">
							<span class="badge">'.$value.'</span>
						</div>
					</div>
		';
				} else
				{
					$html='
					<div class="row">
						<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 ">
							'.$url.'
						</div>
					</div>
		';
				}
				break;

			case 'small':
				$ico='';
				if ($icon<>'')
					$ico='<i class="fa fa-'.$icon.' fa-fw"></i> ';
				$url=$ico.$fieldLabel;
				if ($link<>'')
					$url='<a href="'.$link.'">'.$ico.$fieldLabel.'</a>';
				$html='
				<div class="panel panel-'.$style.'">
					<div class="panel-body">
						<div class="row">
							<div class="col-lg-8 col-md-8 col-sm-10 col-xs-10 text-left">
								'.$url.'
							</div>
							<div class="col-lg-4 col-md-4 col-sm-2 col-xs-2 text-right">
								<span class="badge">'.$value.'</span>
							</div>
						</div>

					</div>
				</div>
	';
				break;

			default:
				$html='
				<div class="panel panel-'.$style.'">
					<div class="panel-heading">
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 text-left">
								<i class="fa fa-'.$icon.' fa-4x"></i>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right">
								<h1 style="margin-top: 0"><b>'.$value.'</b></h1>
								<p>'.$fieldLabel.'</p>
							</div>
						</div>
					</div>
					<div class="panel-body">
						<a href="'.$link.'">'.gT("Ver detalhes").'</a>
					</div>
				</div>
	';
		}
		return($html);
	}

	/**
	 * Gera uma carrocel de de imagens e textos
	 * @author	giuliano
	 * @version	1.0 10-12-2013
	 * @param json $json Parametros:  interval (tempo em milissegundos)
	 * @param type $mtz
	 *          ??array $links Array associativo, onde a chave = link da imagem, valor = textos??
	 * @return string $sai TAG HTML
	 */
	function carousel($json, $mtz = "")
	{
		global $gPathLib;
		$interval = 4000;
		if (is_array($json)) {
			$mtz = $json;
		} else {
			$jarr = cssDecode($json);
			if (intval($jarr['interval']) > 0) {
				$interval = intval($jarr['interval']);
			}
		}
		$sai = '';
		if (gVar("lib.jssor")<>"")
		{
			$transitions[]="MCLIP|L";
			$transitions[]="CLIP|LR";
			$transitions[]="RTTL|BR";
			$transitions[]="RTT|2";
			$transitions[]="ZMF|10";
			$transitions[]="L";
			$transitions[]="R";
			$transitions[]="T";
			$transitions[]="B";
			$transition=0;
			$arrow=5; // de 1 a 5
			$bullet=4; // de 1 a 4
			$position='bottom';
			if (isset($jarr['transition']))
				$transition=intval($jarr['transition']);
			if (isset($jarr['arrow']))
				$arrow=intval($jarr['arrow']);
			if (isset($jarr['bullet']))
				$bullet=intval($jarr['bullet']);
			if (isset($jarr['position']))
				$position=intval($jarr['position']);

			$posicao="0.5";
			if ($position=="top")
				$posicao="-0.05";
			$sai.=$this->n.'<!-- Carousel -->' . $this->n. $this->n;
			$sai.='<div id="slider1_container" style="position: relative; top: 0px; left: 0px; width: 1280px; height: 400px;">' . $this->n;
			$sai.='	<div u="slides" style="cursor: move; position: absolute; overflow: hidden; left: 0px; top: 0px; width: 1280px; height: 400px;">' . $this->n;
			$img = '';
			$cnt = 0;
			foreach ($mtz as $key => $value) {
				$sai.='		<div>'.$this->n;
				if (strpos($key,'jpg')!==false || strpos($key,'jpeg')!==false || strpos($key,'png')!==false)
				{
					$sai.='			<img u="image" src="'.$key.'" />' . $this->n;
					//t="L"
					//t="L" t3="B" d3="500"
					//t="L" t3="B" y3="0.3" f3="1.7"
					if (is_array($value))
					{
						$pos=30;
						$delay=0;
						foreach ($value as $val)
						{
							$sai.='			<div u="caption" t="'.$transitions[$transition].'" t3="B" y3="'.$posicao.'" f3="1.0" d="'.$delay.'" style="position: absolute; top: '.$pos.'px; left: 30px; width: 800px;height: 90px;">'.$val.'</div>'.$this->n;
							$pos+=50;
							$delay+=300;
						}
					} else
					{
						$sai.='			<div u="caption" t="L" t3="B" y3="'.$posicao.'" f3="1.7" style="position: absolute; top: 30px; left: 30px; width: 800px;height: 60px;">'.$value.'</div>'.$this->n;
					}
				} else {
					$sai.=$value . $this->n;
					$sai.='			<div u="caption" t="L" t3="B" y3="'.$posicao.'" f3="1.7" style="position: absolute; top: 30px; left: 30px; width: 800px;height: 60px;">'.$key.'</div>'.$this->n;
				}

				$sai.='		</div>' . $this->n;
			}
			$sai.='	</div>' . $this->n;
			$sai.=file_get_contents($gPathLib.gVar("lib.jssor")."skin/arrow-0".$arrow.".html");
			$sai.=file_get_contents($gPathLib.gVar("lib.jssor")."skin/bullet-0".$bullet.".html");
			$sai.='</div>' . $this->n . $this->n;
			$sai.='<!-- End Carousel -->' . $this->n;

			$js="
jQuery(document).ready(function (\$) {

var _CaptionTransitions = [];

_CaptionTransitions['L'] = { \$Duration: 900, \$FlyDirection: 1, \$Easing: { \$Left: \$JssorEasing\$.\$EaseInOutSine }, \$ScaleHorizontal: 0.6, \$Opacity: 2 };
_CaptionTransitions['R'] = { \$Duration: 900, \$FlyDirection: 2, \$Easing: { \$Left: \$JssorEasing\$.\$EaseInOutSine }, \$ScaleHorizontal: 0.6, \$Opacity: 2 };
_CaptionTransitions['T'] = { \$Duration: 900, \$FlyDirection: 4, \$Easing: { \$Top: \$JssorEasing\$.\$EaseInOutSine }, \$ScaleVertical: 0.6, \$Opacity: 2 };
_CaptionTransitions['B'] = { \$Duration: 900, \$FlyDirection: 8, \$Easing: { \$Top: \$JssorEasing\$.\$EaseInOutSine }, \$ScaleVertical: 0.6, \$Opacity: 2 };
_CaptionTransitions['ZMF|10'] = { \$Duration: 900, \$Zoom: 11, \$Easing: { \$Zoom: \$JssorEasing\$.\$EaseOutQuad, \$Opacity: \$JssorEasing\$.\$EaseLinear }, \$Opacity: 2 };
_CaptionTransitions['RTT|10'] = { \$Duration: 900, \$Zoom: 11, \$Rotate: 1, \$Easing: { \$Zoom: \$JssorEasing\$.\$EaseOutQuad, \$Opacity: \$JssorEasing\$.\$EaseLinear, \$Rotate: \$JssorEasing\$.\$EaseInExpo }, \$Opacity: 2, \$Round: { \$Rotate: 0.8} };
_CaptionTransitions['RTT|2'] = { \$Duration: 900, \$Zoom: 3, \$Rotate: 1, \$Easing: { \$Zoom: \$JssorEasing\$.\$EaseInQuad, \$Opacity: \$JssorEasing\$.\$EaseLinear, \$Rotate: \$JssorEasing\$.\$EaseInQuad }, \$Opacity: 2, \$Round: { \$Rotate: 0.5} };
_CaptionTransitions['RTTL|BR'] = { \$Duration: 900, \$Zoom: 11, \$Rotate: 1, \$FlyDirection: 10, \$Easing: { \$Left: \$JssorEasing\$.\$EaseInCubic, \$Top: \$JssorEasing\$.\$EaseInCubic, \$Zoom: \$JssorEasing\$.\$EaseInCubic, \$Opacity: \$JssorEasing\$.\$EaseLinear, \$Rotate: \$JssorEasing\$.\$EaseInCubic }, \$ScaleHorizontal: 0.6, \$ScaleVertical: 0.6, \$Opacity: 2, \$Round: { \$Rotate: 0.8} };
_CaptionTransitions['CLIP|LR'] = { \$Duration: 900, \$Clip: 15, \$Easing: { \$Clip: \$JssorEasing\$.\$EaseInOutCubic }, \$Opacity: 2 };
_CaptionTransitions['MCLIP|L'] = { \$Duration: 900, \$Clip: 1, \$Move: true, \$Easing: { \$Clip: \$JssorEasing\$.\$EaseInOutCubic} };
_CaptionTransitions['MCLIP|R'] = { \$Duration: 900, \$Clip: 2, \$Move: true, \$Easing: { \$Clip: \$JssorEasing\$.\$EaseInOutCubic} };


	var options = {
		\$ArrowNavigatorOptions: {
			\$Class: \$JssorArrowNavigator\$,
			\$ChanceToShow: 2
		},
		\$BulletNavigatorOptions: {                                //[Optional] Options to specify and enable navigator or not
			\$Class: \$JssorBulletNavigator\$,                       //[Required] Class to create navigator instance
			\$ChanceToShow: 2,                               //[Required] 0 Never, 1 Mouse Over, 2 Always
			\$AutoCenter: 1,                                 //[Optional] Auto center navigator in parent container, 0 None, 1 Horizontal, 2 Vertical, 3 Both, default value is 0
			\$Steps: 1,                                      //[Optional] Steps to go for each navigation request, default value is 1
			\$Lanes: 1,                                      //[Optional] Specify lanes to arrange items, default value is 1
			\$SpacingX: 10,                                  //[Optional] Horizontal space between each item in pixel, default value is 0
			\$SpacingY: 10,                                  //[Optional] Vertical space between each item in pixel, default value is 0
			\$Orientation: 1                                 //[Optional] The orientation of the navigator, 1 horizontal, 2 vertical, default value is 1
		},
		\$CaptionSliderOptions: {
			\$Class: \$JssorCaptionSlider\$,
			\$CaptionTransitions: _CaptionTransitions,
			\$PlayInMode: 1,
			\$PlayOutMode: 3
		},
		\$Class: \$JssorCaptionSlider\$,
		\$FillMode: 2,
		\$AutoPlayInterval: $interval,
		\$AutoPlay: true
	};

	var jssor_slider1 = new \$JssorSlider\$('slider1_container', options);
	//responsive code begin
	//you can remove responsive code if you don't want the slider scales while window resizes
	function ScaleSlider() {
	    var parentWidth = jssor_slider1.\$Elmt.parentNode.clientWidth;
	    if (parentWidth)
	        jssor_slider1.\$SetScaleWidth(parentWidth);
	    else
	        \$JssorUtils\$.\$Delay(ScaleSlider, 30);
	}

	ScaleSlider();
	\$JssorUtils\$.\$AddEvent(window, 'load', ScaleSlider);

	if (!navigator.userAgent.match(/(iPhone|iPod|iPad|BlackBerry|IEMobile)/)) {
	    \$JssorUtils\$.\$OnWindowResize(window, ScaleSlider);
	}
	//responsive code end

});".$this->n;
$this->addJavascript($js);

		} else
		{
			$sai.='<style>.carousel-control.left { background: none;}.carousel-control.right { background: none;}</style>';

			$sai.='<div id="gCarousel" class="carousel slide" data-ride="carousel">' . $this->n;
			$sai.='   <ol class="carousel-indicators">' . $this->n;
			$class = ' class="active"';
			$classi = ' active';
			$img = '';
			$cnt = 0;
			if (isset($jarr['position']))
				$position=intval($jarr['position']);
			$posicao="";
			if ($position=="top")
				$posicao='style="top: 0px;"';
			foreach ($mtz as $key => $value) {
				if (strpos($key,'jpg')!==false || strpos($key,'jpeg')!==false || strpos($key,'png')!==false)
				{
					if (is_array($value))
					{
						$new='';
						foreach ($value as $val)
							$new.=$val;
						$value=$new;
					}
					$sai.='      <li data-target="#gCarousel" data-slide-to="' . $cnt . '"' . $class . '></li>' . $this->n;
					$img.='   <div class="item' . $classi . '">' . $this->n;
					$img.='      <img src="' . $key . '" class="img-responsive center-block" alt="Imagem ' . $cnt . '">' . $this->n;
					$img.='      <div '.$posicao.' class="carousel-caption">' . $this->n;
					$img.='          ' . $value . $this->n;
					$img.='      </div>' . $this->n;
					$img.='   </div>' . $this->n;
					$class = $classi = '';
				} else {
					$sai.='      <li data-target="#gCarousel" data-slide-to="' . $cnt . '"' . $class . '></li>' . $this->n;
					$img.='   <div class="item' . $classi . '">' . $this->n;
					$img.=$value . $this->n;
					// $img.='      <div '.$posicao.' class="carousel-caption">' . $this->n;
					// $img.='          ' . $key . $this->n;
					// $img.='      </div>' . $this->n;
					$img.='   </div>' . $this->n;
					$class = $classi = '';

				}
				$cnt++;
			}

			$sai.='   </ol>' . $this->n;
			$sai.='   <div class="carousel-inner">' . $this->n;
			$sai.=$img;
			$sai.='   </div>' . $this->n;
			$sai.='<a class="left carousel-control" href="#gCarousel" data-slide="prev"><span class="glyphicon glyphicon-chevron-left"></span></a>' . $this->n;
			$sai.='<a class="right carousel-control" href="#gCarousel" data-slide="next"><span class="glyphicon glyphicon-chevron-right"></span></a>' . $this->n;
			$sai.='</div>' . $this->n;
			$this->addJavascript("$('.carousel').carousel({ interval: $interval })");
		}


		return($sai);
	}

	function mapLeaflet($json, $marks = array())
	{
		// Mais informações sobre o Leaflet Routing Machine: 
		// https://leafletjs.com/examples.html
		// https://github.com/perliedman/leaflet-routing-machine
		global $http, $http_lib;
		
		if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1' || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) 
		{
			$urlRouterApi = "http://167.234.233.9:5000/route/v1";
		} else {
			$urlRouterApi = "gfw/inc/lib/osrm";
		}
		// gD($urlRouterApi);exit;
		$urlJsLeaflet = $http_lib . 'leaflet/leaflet.js';
		$urlCssLeaflet = $http_lib . 'leaflet/leaflet.css';

		$urlJsLeafletRM = $http_lib . 'leaflet-routing-machine/dist/leaflet-routing-machine.min.js';
		$urlCssLeafletRM = $http_lib . 'leaflet-routing-machine/dist/leaflet-routing-machine.css';

		$jarr = cssDecode($json);
		$lat = -12.880;
		$lon = -38.309; // GiuSoft Lauro ;-)
		$width = '500px';
		$height = '380px';
		$sensor = 'true';
		$zoom = 12;
		$showHere = true;
		$showInfo = false;
		$showRoute = 1;
		$mapNumber = 0;
		$circleRadius = 0;
		$circleColor = array(
			array('border' => '#3274A3', 'fill' => '2A81CB'),
			array('border' => '#982E40', 'fill' => '#CB2B3E')
		);
		$markersColors = array('blue', 'gold', 'red', 'green', 'orange', 'yellow', 'violet', 'grey', 'black');

		$id='leafletMap'.count($this->initializeMaps);

		if (floatval($jarr['latitude']) <> 0) {
			$lat = floatval($jarr['latitude']);
		}
		if (floatval($jarr['longitude']) <> 0) {
			$lon = floatval($jarr['longitude']);
		}
		$lat_fency[] = $lat;
		$lon_fency[] = $lon;

		if (floatval($jarr['latitude_fency']) <> 0) {
			if (strpos(",", $jarr['latitude_fency']) !== false) {
				$lat_fency[] = str_replace(",", ".", $jarr['latitude_fency']);
			} else {
				$lat_fency[] = floatval($jarr['latitude_fency']);
			}
		}
		if (floatval($jarr['longitude_fency']) <> 0) {
			if (strpos(",", $jarr['longitude_fency']) !== false) {
				$lon_fency[] = str_replace(",", ".", $jarr['longitude_fency']);
			} else {
				$lon_fency[] = floatval($jarr['longitude_fency']);
			}
		}

		if ($jarr['id'] <> "") {
			$id = $jarr['id'];
		}
		if ($jarr['zoom'] > 0) {
			$zoom = $jarr['zoom'];
		}
		if ($jarr['showHere'] == "false") {
			$showHere = false;
		}
		if ($jarr['width'] <> '') {
			$width = $jarr['width'];
		}
		if ($jarr['height'] <> '') {
			$height = $jarr['height'];
		}
		if ($jarr['showRoute'] == "false"){
			$showRoute = 0;
		}
		if ($jarr['circleRadius']){
			$circleRadius = $jarr['circleRadius'];
		}

		$this->out('<link rel="stylesheet" href="' . $urlCssLeaflet . '">', gLOC_PRE, 999);
		$this->out('<link rel="stylesheet" href="' . $urlCssLeafletRM . '">', gLOC_PRE, 999);

		$this->out('<script src="' . $urlJsLeaflet . '"></script>', gLOC_PRE, 999);
		$this->out('<script src="' . $urlJsLeafletRM . '"></script>', gLOC_PRE, 999);

		$sai = '<style>#'.$id.'{width:'.$width.';height:'.$height.';}</style>';
		$sai.= '<div class="img-thumbnail" id="'.$id.'" style="width:' . $width . ';height:' . $height . ';"></div>';

		$js = '
			var '.$id.' = L.map("'.$id.'").setView(['.$lat.', '.$lon.'], '.$zoom.');

			L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
				attribution: "© OpenStreetMap contributors"
			}).addTo('.$id.');
		';

		foreach ($markersColors as $color) {
			$js .= '
				var '.$color.'Icon = new L.Icon({
					iconUrl: "https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-'.$color.'.png",
					shadowUrl: "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.3.4/images/marker-shadow.png",
					iconSize: [25, 41],
					iconAnchor: [12, 41],
					popupAnchor: [1, -34],
					shadowSize: [41, 41]
				});
			';
		}

		if ($circleRadius && $lat_fency && $lon_fency) {
			foreach ($lat_fency as $key => $lat) {
				$js.= '
				const circle'.$key.' = L.circle(['.$lat.', '.$lon_fency[$key].'], {
					color: "'.$circleColor[$key]['border'].'",
					fillColor: "'.$circleColor[$key]['fill'].'",
					fillOpacity: 0.2,
					radius: '.$circleRadius.'
				}).addTo('.$id.').bindPopup("Cerca");
				';
			}
		}

		if ($marks) {
			$js.='
			var msgs = [';
			foreach($marks as $msg){
				$msgs[]='"'.$msg[2].'"';
			}
			$js.=implode(",", $msgs);
			$js.='];';

			$js .= 'var icons = [';
			foreach($marks as $c){
				if ($c[3]) {
					$cm[]= '{icon: '.$c[3].'Icon, draggable:false}';
				} else {
					$cm[]= '{icon: blueIcon, draggable:false}';
				}
			}
			$js.=implode(",", $cm);
			$js.='];';

			$js.= '
			L.Routing.control({
				show: false, 
				waypoints: [';
			$m = array();
			$msgs = array();
			foreach($marks as $mark){
				$m[]= 'L.latLng('.$mark[0].', '.$mark[1].')';
			}
			$js .= implode(",\n", $m);
			$js .= '
				],
				createMarker: function(i, wp, n) {
					// Cria o marcador padrão
					var marker = L.marker(wp.latLng, icons[i]);
					// Define a mensagem HTML para cada waypoint (pode ser customizada conforme necessário)
					var popupContent = "<span style=\"font-size: 150%\"><strong>" + (i+1) +". " + msgs[i] + "</span>"
					
					// Vincula o popup ao marcador
					marker.bindPopup(popupContent);
					
					// Adiciona o evento de clique para abrir o popup
					marker.on("click", function() {
					marker.openPopup();
					});
					
					return marker;
				},				
				lineOptions: {
					styles: [{
						color: "#6666ff",
						weight: 4,
						opacity: '.$showRoute.'
					}]
				},
				draggableWaypoints: true,
				autoRoute: true,
				serviceUrl: "'.$urlRouterApi.'"
			}).addTo('.$id.').route();						
			';
		}
		$this->addJavascript($js);
		return($sai);
	}


	/**
	 * Mostra o GoogleMaps e o marcador da posição especificada
	 * @author	giuliano
	 * @version	1.0 07-04-2009 13:44
	 * @global type $http
	 * @param json $json Parâmetros JSON: latitude, longitude, sensor (true/false)
	 * @param array $marks Array associativo de Marcadores
	 * @return type
	 */
	function map($json, $marks = '')
	{
		global $http;
		$jarr = cssDecode($json);
		$lat = -12.880;
		$lon = -38.309; // GiuSoft Lauro ;-)
		$width = '500px';
		$height = '380px';
		$sensor = 'true';
		$zoom = 7;
		$showHere = true;
		$showInfo = false;
		$showRoute = false;
		$mapNumber = 0;
		$circleRadius = 0;
		$id='googleMap'.count($this->initializeMaps);

		if (floatval($jarr['latitude']) <> 0) {
			if (strpos(",", $jarr['latitude']) !== false) {
				$lat = str_replace(",", ".", $jarr['latitude']);
			} else {
				$lat = floatval($jarr['latitude']);
			}
		}
		if (floatval($jarr['longitude']) <> 0) {
			if (strpos(",", $jarr['longitude']) !== false) {
				$lon = str_replace(",", ".", $jarr['longitude']);
			} else {
				$lon = floatval($jarr['longitude']);
			}
		}

		if (floatval($jarr['latitude_fency']) <> 0) {
			if (strpos(",", $jarr['latitude_fency']) !== false) {
				$lat_fency = str_replace(",", ".", $jarr['latitude_fency']);
			} else {
				$lat_fency = floatval($jarr['latitude_fency']);
			}
		}
		if (floatval($jarr['longitude_fency']) <> 0) {
			if (strpos(",", $jarr['longitude_fency']) !== false) {
				$lon_fency = str_replace(",", ".", $jarr['longitude_fency']);
			} else {
				$lon_fency = floatval($jarr['longitude_fency']);
			}
		}

		if ($jarr['id'] <> "") {
			$id = $jarr['id'];
		}
		if ($jarr['zoom'] > 0) {
			$zoom = $jarr['zoom'];
		}
		if ($jarr['sensor'] == "false") {
			$sensor = 'false';
		}
		if ($jarr['showInfo'] == "true") {
			$showInfo = true;
		}
		if (intval($jarr['zoom']) > 0) {
			$zoom = intval($jarr['zoom']);
		}
		if ($jarr['showHere'] == "false") {
			$showHere = false;
		}
		if ($jarr['width'] <> '') {
			$width = $jarr['width'];
		}
		if ($jarr['height'] <> '') {
			$height = $jarr['height'];
		}
		if ($jarr['showRoute'] == "true"){
			$showRoute = true;
		}
		if ($jarr['mapNumber'] <> ''){
			$mapNumber = $jarr['mapNumber'];
		}
		if ($jarr['circleRadius']){
			$circleRadius = $jarr['circleRadius'];
		}

		$this->out('<script src="' . $http . '://maps.googleapis.com/maps/api/js?key=' . $this->googleKey . '&sensor=' . $sensor . '"></script>', gLOC_PRE, 999);
		$sai = '<div class="img-thumbnail" id="'.$id.'" style="width:' . $width . ';height:' . $height . ';"></div>';
		if($showRoute){
			$sai .= '<p id="distanciaMaps'.$mapNumber.'"><b></b></p>';
		}

		$js = '
		var circleRadius = '.$circleRadius.'
		function initialize'.$id.'()
		{
		var mapProp = {
		center:new google.maps.LatLng(' . $lat . ',' . $lon . '),
		zoom:' . $zoom . ',
		mapTypeId:google.maps.MapTypeId.ROADMAP
		};
		var map = new google.maps.Map(document.getElementById("'.$id.'"), mapProp);
		';
		if ($marks) {
			$js .= '
				var directionsService = new google.maps.DirectionsService();
				var directionsRenderer = new google.maps.DirectionsRenderer({
					map: map,
					suppressMarkers: true
				});
	
				directionsRenderer.addListener("directions_changed", () => {
					const directions = directionsRenderer.getDirections();
	
					if (directions) {
						totalDistance(directions);
					}
				});
			';
		}

	if ($circleRadius && $lat_fency && $lon_fency) {
		$js .= "
			const geodesicCircle2 = new google.maps.Circle({
				strokeColor: '#FF0000',
				strokeOpacity: 0.6,
				strokeWeight: 2,
				fillColor: '#FF0000',
				fillOpacity: 0.1,
				map,
				center: {lat: ".$lat_fency." ,lng: ".$lon_fency."},
				radius: ".$circleRadius.",
			});
		";
	}

		$firstMark = '';
		$lastMark = '';
		$waypoints = '';
		if ($showHere) {
			$js.='	
			const principalMarkers = {
				sede: {
					lat: '.$lat.',
					lng: '.$lon.',
					center: { lat: '.$lat.', lng: '.$lon.' },
    				color: "#0008FF",
				},
			';

			if ($lat_fency && $lon_fency) {
				$js .= '
					cerca: {
						lat: '.$lat_fency.',
						lng: '.$lon_fency.',
						center: { lat: '.$lat_fency.', lng: '.$lon_fency.' },
    					color: "#FF0000",
					},
				';
			}
			
			$js .= "};";


			$js .= "
				var cont = 0;
				for (const pin in principalMarkers) {
					var point = new google.maps.LatLng(principalMarkers[pin].lat,principalMarkers[pin].lng);
					var marker = new google.maps.Marker({
						map: map,
						position: point,
					});

					const geodesicCircle = new google.maps.Circle({
						strokeColor: principalMarkers[pin].color,
						strokeOpacity: 0.6,
						strokeWeight: 2,
						fillColor: principalMarkers[pin].color,
						fillOpacity: 0.1,
						map,
						center: principalMarkers[pin].center,
						radius: circleRadius,
					});
				}

			";
		}
		if (is_array($marks)) {
			$countMark = count($marks) - 1;
			foreach ($marks as $key => $value) {

				$lat = $value[0];
				$lon = $value[1];
				$description = $value[2];
				$keyName = gString2Field($key);

				$js.='
					var point = new google.maps.LatLng(' . $lat . ',' . $lon . ');
					var marker' . $keyName . ' = new google.maps.Marker({
						map: map,
						position: point,
						title: "'.($key+1).'",
						label: {color: "#fff", fontSize: "15px", fontWeight: "500",
							text: "'.($key+1).'"}
					});';

				if ($showInfo && !empty($description)) {

					$js.='var infowindow'.$keyName.' = new google.maps.InfoWindow({ content:"' . $description . '" });
						marker'.$keyName.'.addListener("click", () => {
							infowindow'.$keyName.'.open({
							anchor: marker'.$keyName.',
							map,
							});
						});
					';
				}

				if($showRoute){
					if ($firstMark == '') {
						$firstMark = 'marker'.$keyName;
						$waypoints = '[';
					}
					else if ($key == $countMark) {
						$lastMark = 'marker'.$keyName;
						$waypoints .= ']';
					}
					else {
						$waypoints .= '{location: marker'.$keyName.'.getPosition(), stopover: false},';
					}
					if($countMark == 0){
						$js.='	var point = new google.maps.LatLng(-12.880, -38.309); var marker = new google.maps.Marker({	map: map, position: point, title: "' . ($key+1) . '" });';
						$firstMark = 'marker';
						$lastMark = 'marker0';
						$waypoints = '[]';
					}
				}
			}
			if($showRoute){
				$js .= '
				calculateAndDisplayRoute(directionsService, directionsRenderer);

 					function calculateAndDisplayRoute(directionsService, directionsRenderer) {
					directionsService
					.route({
						origin: '.$firstMark.'.getPosition(),
						destination: '.$lastMark.'.getPosition(),
						waypoints :'.$waypoints.',
						travelMode: google.maps.TravelMode.DRIVING,
					})
					.then((response) => {
						directionsRenderer.setDirections(response);
					})
					.catch((e) => window.alert("Directions request failed due to " + status));
				}

				function totalDistance(response){
					const route = response.routes[0];
					let total = 0;

					for (let i = 0; i < route.legs.length; i++) {
						total += route.legs[i].distance.value;
					}

					total = total / 1000;

					document.getElementById("distanciaMaps'.$mapNumber.'").innerHTML = "<b>Distância total: </b>"+total+" km";
				}
				';
			}

			$js.='map.setCenter(marker' . $keyName . '.getPosition());';
		}
		$js.='}';


	$this->initializeMaps[] = $id;
	$content = '';
	foreach($this->initializeMaps as $maps)
		$content .= "initialize$maps();";

	$js .= 'function initialize(){'.$content .'};';

	$js .= 'google.maps.event.addDomListener(window, "load", initialize);';
		$this->addJavascript($js);
		return($sai);
	}

	/**
	 * Exibe uma janela modal
	 * @param type $json
	 * @param string $content
	 * @return type
	 */
	function modal($json, $content = "")
	{
		$jarr = cssDecode($json);
		$name = "gModal";
		$okCaption = gT('Confirmar');
		$cancelCaption = gT('Cancelar');
		$cnt = "";
		if ($jarr['name'] <> '') {
			$name = $jarr['name'];
		} else {
			$cnt = $this->modalCnt;
		}
		if ($jarr['okCaption'] <> '') {
			$okCaption = gT($jarr['okCaption']);
		}
		if ($jarr['cancelCaption'] <> '') {
			$cancelCaption = gT($jarr['cancelCaption']);
		}
		if (isset($jarr['href']))
			$jarr['url']=$jarr['href'];
		$id = $name . $cnt;
		$idLabel = $name . "Label" . $cnt;
		$title = gT($jarr['title']);
		$close = true;
		$confirm = true;
		$size = '';
		if ($jarr['size']=='small' || $jarr['size']=='tiny' )
		{
			$size='modal-sm';
		}
		elseif ($jarr['size']=='big' || $jarr['size']=='large' )
		{
			$size='modal-lg';
		}
		if ($jarr['content'] <> '') {
			$content = gT($jarr['content']) . $content;
		}
		//echo "<textarea>$content</textarea>";
		//exit;
		if ($jarr['close'] == 'false' || $jarr['cancel'] == 'false') {
			$close = false;
		}
		if ($jarr['confirm'] == 'false') {
			$confirm = false;
		}
		$sai = '';
		if ($this->debug)
			$sai.= "\n\n\n<!-- Modal content / START -->\n\n";
		$sai.='<div class="modal fade" id="' . $id . '" tabindex="-1" role="dialog" aria-labelledby="' . $idLabel . '" aria-hidden="true">' . $this->n;
		$sai.='   <div class="modal-dialog '.$size.'">' . $this->n;
		$sai.='      <div class="modal-content">' . $this->n;
		$sai.='         <div class="modal-header">' . $this->n;
		if ($close)
			$sai.='            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' . $this->n;
		$sai.='               <h4 class="modal-title" id="' . $idLabel . '">' . $title . '</h4>' . $this->n;
		$sai.='               </div>' . $this->n;
		$sai.='               <div class="modal-body">' . $this->n;
		$sai.=$content . "\n";
		$sai.='               </div>' . $this->n;
		if ($close || $confirm)
		{
			$sai.='               <div class="modal-footer">' . $this->n;
			if ($close)
				$sai.='                  <button type="button" class="btn btn-default" data-dismiss="modal">' . $cancelCaption . '</button>' . $this->n;
	                if($confirm){
	                    //if ((strpos($jarr['url'], 'http') === false) && (substr($jarr['url'], 0, 1) != '/')) {

						if ((strpos($jarr['url'], 'http') === false) && (substr($jarr['url'], 0, 1) <> '/') && (strpos($jarr['url'], '.php')===false)) {
							// É javascript
							$sai.='<a type="button" data-dismiss="modal" role="button" class="btn btn-primary" onClick="javascript:'.$jarr['url'].'">' . $okCaption . '</a>' . $this->n;
	                    } else {
							// É href
							// $sai.='                  <a type="button" data-dismiss="modal" role="button" data-href="' . $jarr['url'] . '" class="btn btn-primary">' . $okCaption . '</a>' . $this->n;
							$sai.='<a type="button" data-dismiss="modal" role="button" class="btn btn-primary" onClick="javascript:window.location.href=\''.$jarr['url'].'\'">' . $okCaption . '</a>' . $this->n;
	                    }
	                }
	                $sai.='<!-- '. $jarr['url'].'-->';
			$sai.='               </div>' . $this->n;
		}
		$sai.='      </div>' . $this->n;
		$sai.='   </div>' . $this->n;
		$sai.='</div>' . $this->n;
		//echo "<textarea>$sai</textarea>";
		//exit;
		if ($this->debug)
			$sai.= "\n<!-- Modal content / END -->\n\n";
		$this->modalCnt++;
		return($sai);
	}

	/**
	 * Exibe um painel de mensagens que rolam de um lado para o outro
	 * @param type $json
	 * @param array $content Array associativo de mensagens, onde a chave é uma URL e o valor a mensagem
	 * @return type
	 */
	function ticker($json, $content = "")
	{
		$sai 		= "";
		$target 	= "";
		$cor		= "#000000";
		$jarr		= cssDecode($json);
		if ($jarr['color'] <> '') {
			$cor = $jarr['color'];
		}
		if ($jarr['target']<>'')
			$target=' target="' . $jarr['target'] . '"';
		if (is_array($content)) {
			$sai = '<ul id="gTicker">' . $this->n;
			foreach ($content as $key => $value) {
				if (intval($key) == 0) {
					$sai.='<li><a href="' . $key . '" style="color: ' . $cor . '"' . $target . '>' . gCleanField($value) . '</a></li>' . $this->n;
				} else {
					$sai.='<li>' . gCleanField($value) . '</li>' . $this->n;
				}
			}
			$sai.='</ul>' . $this->n;
			$this->addJavascript("$('ul#gTicker').liScroll();");
		}
		return($sai);
	}

	/**
	 * Painel bootstrap
	 * @param string Conteudo
	 * @param string Titulo
	 * @param string Estilo - opcoes: default, primary, success, info, warning e danger
	 */
	function panel($content = '', $title = '', $footer = '', $style = 'default', $table = ''){
		if ($content<>'')
		{
			$display='';
			if (strpos($content, 'svg')===false && strpos($content, 'gChart')===false && strpos($content, 'leafletMap')===false) // se tiver imagem ou mapa, não tem animação
				$display='style="display: none"';
			$sai = '<div class="panel panel-'.$style.'" '.$display.'>'.
						($title? '
						<div class="panel-heading">'.gT($title).'</div>': '').'
						<div class="panel-body">'.$content.'</div>
						'.$table.'
						'.($footer? '
						<div class="panel-footer">
							'.$footer.'
						</div>': '').'
					</div>';
		} else
		{
			$sai = '<div class="panel panel-'.$style.'" style="display: none">'.
						($title? '
						<div class="panel-heading">'.gT($title).'</div>': '').'
						'.$table.'
						'.($footer? '
						<div class="panel-footer">
							'.$footer.'
						</div>': '').'
					</div>';

		}
		return $sai;
	}

	function webcam($enviarPara)
	{
		global $http_lib, $gId;
		$html.=$this->msgInfo("Para coletar a foto pela sua Webcam:<br><br><ul><li>Você deve conceder a permissão para seu navegador acessar a sua Webcam</li><li>Dê preferência em usar o Chrome ou Firefox</li><Certifique-se de ter uma iluminação adequada</li><li>Certifique-se de dispor de um fundo claro e limpo</li></ul>");
		$this->out('<script src="' . $http_lib . gVar("lib.webcamjs"). 'webcam.min.js"></script>', gLOC_POS,2);
		$html.='
		<table>
			<tr>
				<td>
					<div id="my_camera" style="width:320px; height:240px;"></div>
				</td>
				<td>&nbsp;</td>
				<td>
					<div id="my_result" style="display: inline" class="img img-thumbnail"></div>
				</td>
			</tr>
		</table>';
		$html.=$this->button("{icon: camera; caption: Capturar imagem; showWait:false; }","javascript:take_snapshot()");
		$js = "
		Webcam.attach( '#my_camera' );
		function take_snapshot() {
			Webcam.snap( function(data_uri) {
				document.getElementById('my_result').innerHTML = '<img src=\"'+data_uri+'\"/>';
				Webcam.upload( data_uri, '".$this->page."&gPage=".$enviarPara."&gId=".$gId."', function(code, text) {
					bootbox.alert('Foto salva no cadastro da pessoa');
				} );
			} );
		}";
		$this->addJavascript($js);
		return($html);
	}

	function webcamSave($destination)
	{
		if (isset($_FILES['webcam']['tmp_name']))
		{
			move_uploaded_file($_FILES['webcam']['tmp_name'], $destination);
		}
		exit;

	}

	function barcodeScan($json='', $readers=array("code_128_reader"))
	{
		global $http_lib,$gDevice,$browser;
		/* Tratando dados */
		$mtz=array();
		foreach ($readers as $reader)
		{
			$mtz[]="'".$reader."'";
		}

		$atributos=cssDecode($json);

		$width=(isset($atributos["width"]))
			? $atributos["width"]
			: '320';

		$height=(isset($atributos["height"]))
			? $atributos["height"]
			: '240';
		$id=(isset($atributos["id"]))
			? $atributos["id"]
			: "id";
		$readers=implode(",", $mtz);
		$style="<style>
					canvas.drawing, canvas.drawingBuffer { position: absolute; left: 0; top: 0;}
					#scanner-container.viewport {
					    position: relative;
					}

					#scanner-container.viewport > canvas, #scanner-container.viewport > video {
					    max-width: 100%;
					    width: 100%;
					}
			        canvas.drawing, canvas.drawingBuffer {
			            position: absolute;
			            left: 0;
			            top: 0;
			        }
			    </style>";
    	$this->out($style, gLOC_PRE);
		$html.='<div id="scanner-container" class="viewport"></div>';
    	$html.="<button class='btn btn-default' type='button' id='btnBarcode' value='Liga/desliga scanner'>";
    	$html.="<i class='fa fa-barcode'></i>";
    	$html.="  <span>Scanner</span>";
    	$html.="</button>";
    	$quagga = $http_lib . gVar("lib.quagga");
    	// $widthHeight = "
	    //                     width: ".$width.",
	    //                     height: ".$height.",
    	// ";
    	if (stripos($browser, 'iphone')!==false)
    	{
    		$widthHeight='';
    	}
    	$scripts='<script src="'.$quagga.'"></script>';
		$this->out($scripts, gLOC_PRE);
    	$js="
    		var _scannerIsRunning = false;
			function startScanner() {
				console.log('here');
	            Quagga.init({
	                inputStream: {
	                    name: 'Live',
	                    type: 'LiveStream',
	                    target: document.querySelector('#scanner-container'),
	                    constraints: {
	                    	".$widthHeight."
	                        aspectRatio: {min: 1, max: 100},
	                        facingMode: 'environment'
	                    },
	                },
		            locator: {
		                patchSize: 'medium',
		                halfSample: true
		            },
		            frequency: 10,
	                decoder: {
	                    readers: [
	                        ".$readers."
	                    ],
       	                 debug: {
	                        showCanvas: true,
	                        showPatches: true,
	                        showFoundPatches: true,
	                        showSkeleton: true,
	                        showLabels: true,
	                        showPatchLabels: true,
	                        showRemainingPatchLabels: true,
	                        boxFromPatches: {
	                            showTransformed: true,
	                            showTransformedBox: true,
	                            showBB: true
	                        },
            			locate: true
	                    }
	                },

	            }, function (err) {
	                if (err) {
	                    console.log(err);
	                    return
	                }
	                console.log('Initialization finished. Ready to start');
	                Quagga.start();

	                // Set flag to is running
	                _scannerIsRunning = true;
	            });

	            Quagga.onProcessed(function (result) {
	                var drawingCtx = Quagga.canvas.ctx.overlay,
	                drawingCanvas = Quagga.canvas.dom.overlay;

	                if (result) {
	                    if (result.boxes) {
	                        drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute('width')), parseInt(drawingCanvas.getAttribute('height')));
	                        result.boxes.filter(function (box) {
	                            return box !== result.box;
	                        }).forEach(function (box) {
	                            Quagga.ImageDebug.drawPath(box, { x: 0, y: 1 }, drawingCtx, { color: 'green', lineWidth: 2 });
	                        });
	                    }

	                    if (result.box) {
	                        Quagga.ImageDebug.drawPath(result.box, { x: 0, y: 1 }, drawingCtx, { color: '#00F', lineWidth: 2 });
	                    }

	                    if (result.codeResult && result.codeResult.code) {
	                        Quagga.ImageDebug.drawPath(result.line, { x: 'x', y: 'y' }, drawingCtx, { color: 'red', lineWidth: 3 });
	                    }
	                }
	            });

	            Quagga.onDetected(function (result) {
	            	$('#scanner-container').hide();
	            	//document.getElementById('scanner-container').style.display='none';
	            	_scannerIsRunning = false;
	            	var id = '#".$id."';
	            	$(id).val(result.codeResult.code);
	            	//console.log('Barcode detected and processed : [' + result.codeResult.code + ']', result);
	            });
	        }

	        document.getElementById('btnBarcode').addEventListener('click', function () {
	            if (_scannerIsRunning) {
	            	$('#scanner-container').hide();
	            } else {
	            	$('#scanner-container').show();
	                startScanner();
	            }
	        }, false);
	    ";
    	$this->addJavascript($js);
    	return ($html);
	}

	function gHomePage()
	{
		global $o, $http_files, $gPath;
		$sql = "SELECT * FROM gfw_home ORDER BY section";
		$rs = dbFastQuery($sql);
		$html.="\n";
		$cntCol = 0;
		$section = 0;
		$carousel = '';
		foreach ($rs as $row) {
			if ($section<>$row['section'])
			{
				$cntCol = 0;
				$section=$row['section'];
			}
			if ($cntCol==0)
			{
				if ($row['section']==1)
				{
					$html.='		<div class="row">'."\n";
				} else {
					if ($row['style']=='Rodapé')
					{
						if ($gDevice=='mobile')
						{
							$html.='<div class="row" style="color: #fff; background-color: #2D3235; padding: 10px; padding-top: 10px">';
						} else {
							$html.='<div class="rdp row" style="color: #fff; background-color: #2D3235; padding: 20px; padding-top: 40px">';
						}
					} else {
						$html.='		<div class="row"><div class="container">'."\n";
					}
				}
			}



			if ($row['style']=='Carrocel')
			{
				$carousel[] = $row;
				$cntCol+=$row['columns'];
			} else {
				$href='';
				$hreffim='';
				if (strpos($row['link'],'http')!==false)
				{
					$href='<a href="'.$row['link'].'">';
					$hreffim='</a>';
				} else {
					if ($row['link']<>'')
					{
						$href='<a href="'.$_SERVER["PHP_SELF"].'?g='.$row['link'].'">';
						$hreffim='</a>';
					}
				}
				$cntCol+=$row['columns'];
				$img = '';
				$title = '';
				$align = '';
				switch ($row['style'])
				{
					case 'Imagem acima':
						$img = '';
						$title = '<h3>'.$row['title'].'</h3>';
						$align = 'text-center';
						break;
					case 'Imagem com borda':
						$title = '<h3>'.$row['title'].'</h3>';
						$img = 'img-thumbnail';
						break;
					case 'Imagem redonda acima':
						$title = '<h3>'.$row['title'].'</h3>';
						$img = 'img-circle';
						$align = 'text-center';
						break;
					case 'Imagem a esquerda':
						$title = '<h3>'.$row['title'].'</h3>';
						$img = 'float-right';
						$align = 'text-left';
						break;
					case 'Imagem a direita':
						$title = '<h3>'.$row['title'].'</h3>';
						$img = 'float-left';
						$align = 'text-right';
						break;
					default:
						$title = '<h3>'.$row['title'].'</h3>';
						break;
				}

				$html.='						<div class="'.$align.' col-lg-'.$row['columns'].' col-md-'.$row['columns'].' col-sm-'.$row['columns'].' col-xs-'.$row['columns'].'">'."\n";
				$item = '';
				if ($row['icon']<>'')
				{
					$item.='<span class="fa fa-3x fa-'.$row['icon'].'"></span>';
				}
				if ($row['id_gfw_images']>0)
				{
					$item.=$href.'<img src="'.$http_files.'/images/'.$row['id_gfw_images'].'.jpeg" class="img img-responsive '.$img.'">'.$hreffim."<br>";
				} else {

				}
				$item.=$title;
				$item.=base64_decode($row['text']);
				if ($row['link']<>'')
				{
					$item.='<br>'.$o->button("{caption: Saber mais...".$row['link'].";href: ".$row['link']."; }");
				}
				if ($row['border']==1)
				{
					$html.='<div class="panel panel-default"><div class="panel-body">';
					$html.=$item;
					$html.='</div></div>';
				} else {
					$html.=$item;
				}
				if ($row['link']<>'')
				{
				}
				$html.='						</div>'."\n";
			}





			if ($cntCol>=12)
			{
				if (is_array($carousel))
				{
					$mtz = '';
					foreach ($carousel as $c)
					{
						$mtz[$http_files.'/images/'.$c['id_gfw_images'].'.jpeg'] = '<h1 style="color: #fff; text-shadow: 0px 0px 14px rgba(0, 0, 0, 1);">'.$c['title'].'</h1>'.base64_decode($c['text']);
					}
					$html.=$o->carousel("{}", $mtz);
					$carousel = '';
				}
				if ($row['section']>1)
					$html.='						</div>'."\n";
				$html.='		</div>'."\n";
				$html.=$o->hr('soft');
				$cntCol = 0;
			}
		}
		if ($cntCol<12 and $cntCol>0)
		{
			$html.='		</div>'."\n";
		}
		return($html);
	}


	function videoControls($json)
	{
		global $o, $SITE;
		$mtz = cssDecode($json);
		if ($mtz['name']=="")
		{
			$mtz['name'] = 'videoConference';
		}

		// Youtube
		$html.="<div id='".$mtz['name']."'></div>";
		if ($mtz['youtube']=="true")
		{
			if ($mtz['style']=="moderator")
			{
				//$html.="<form class='form-inline'>";
				$html.="<div style='display: inline'>";
				//$html.="<input type='text' class='form-control' id='ytUrl' style='width: 300px' placeholder='https://www.youtube.com/watch?v=oHJ8qvmRLUQ&t=20s' value='https://www.youtube.com/watch?v=oHJ8qvmRLUQ&t=20s'>";
				$html.="&nbsp;<button id='btnAbrir' class='btn btn-success' disabled xonClick='ytAbrir()'>---</button>&nbsp;";

				$html.="<div id='controlesYoutube' class='' style='display: inline'>";
				$html.="<div class='btn-group' role='group' >";
				$html.="
					<button class='btn btn-default dropdown-toggle' type='button' id='dropdownMenu1' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true'>
					Selecione o vídeo
					<span class='caret'></span>
					</button>
					<ul class='dropdown-menu' aria-labelledby='dropdownMenu1'>";
				$html.="<li><a onClick='ytCarregar(\"4w4IT8cDYME\")'>Instruções para o instrutor</a></li>";
				$html.="<li role='separator' class='divider'></li>";
				$sql = "SELECT * FROM gfw_youtube_videos";
				$videos = dbQuery($sql);

				foreach ($videos as $video)
				{
					$html.="<li><a onClick='ytCarregar(\"".$video['video_id']."\")'>".$video['description']."</a></li>";
				}

				$html.="</ul>";
				$html.="</div>";

				$html.="<div class='btn-group' role='group' aria-label='...' >";
				// $html.="<button class='btn btn-default' aria-label='...' onClick='ytRetrocederInicio()'><span class='fal fa-step-backward'></span></button>";
				// $html.="<button class='btn btn-default' aria-label='...' onClick='ytRetroceder()'><span class='fal fa-backward'></span></button>";
				$html.="<button class='btn btn-default' aria-label='...' onClick='ytParar()'><span class='fal fa-stop'></span></button>";
				$html.="<button class='btn btn-default' aria-label='...' onClick='ytTocar()'><span class='fal fa-play'></span></button>";
				$html.="<button class='btn btn-default' aria-label='...' onClick='ytPausar()'><span class='fal fa-pause'></span></button>";
				// $html.="<button class='btn btn-default' aria-label='...' onClick='ytAvancar()'><span class='fal fa-forward'></span></button>";
				// $html.="<button class='btn btn-default' aria-label='...' onClick='ytAvancarFinal()'><span class='fal fa-step-forward'></span></button>";
				$html.="</div>";

				$html.="</div>";

				$html.="</div>";

				//$html.="</form>";
				$js = "

				$('#controlesYoutube').hide();$('#videoYoutube').hide();$('#".$mtz['name']."').show();

				var tag = document.createElement('script');
				tag.src = 'https://www.youtube.com/iframe_api';
				var firstScriptTag = document.getElementsByTagName('script')[0];
				firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
				  var player;
				function onYouTubeIframeAPIReady() {
				  player = new YT.Player('videoYoutube', {
					height: '600',
					width: '100%',
					videoId: 'oHJ8qvmRLUQ',
					playerVars: { 'controls': 0 },
					events: {
					  'onReady': onPlayerReady
					}
				  });
				}
				function onPlayerReady(event) { }
				function ytCarregar(url) { ytComandoRemoto('url:'+url);player.setVolume(10);player.loadVideoById({videoId: url}); }
				function ytTocar() { ytComandoRemoto('play');player.playVideo(); }
				function ytParar() { ytComandoRemoto('stop');player.stopVideo(); }
				function ytPausar() { ytComandoRemoto('pause');player.pauseVideo(); }

				function ytAbrir()
				{
					if($('#videoYoutube').is(':visible'))
					{
						$('#btnAbrir').html('Abrir Youtube');
						$('#controlesYoutube').hide();$('#videoYoutube').hide();$('#".$mtz['name']."').show();
						ytComandoRemoto('close');
					} else {
						$('#btnAbrir').html('Fechar Youtube');
						$('#controlesYoutube').show();$('#videoYoutube').show();$('#".$mtz['name']."').hide();
						ytComandoRemoto('open');
					}
				}

				function ytComandoRemoto(msg)
				{
					$.ajax({
						url: '".$o->page."&gAjax=1&cmd=ytSrvCmd&msg='+msg,
						context: document.body
					})
					.done(function(data,textStatus){
					});
				}
				";

			} else {
				//$temporizador = "setInterval(ytComandoRemoto, 200000);";
				// if ($SITE!="WEB•CFC - DETRAN")
				// {
				// 	// Identificando intervalo...
				// 	$sql = "SELECT count(id) ttl FROM gfw_youtube_videos";
				// 	$ttl = dbFastQuery($sql)[0]['ttl'];
				// 	if ($ttl>0)
				// 	{
				// 		$temporizador = "setInterval(ytComandoRemoto, 9000);";
				// 	}
				// }

				$js = "

				$('#controlesYoutube').hide();$('#videoYoutube').hide();$('#".$mtz['name']."').show();

				var tag = document.createElement('script');
				tag.src = 'https://www.youtube.com/iframe_api';
				var firstScriptTag = document.getElementsByTagName('script')[0];
				firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
				  var player;
				function onYouTubeIframeAPIReady() {
				  player = new YT.Player('videoYoutube', {
					height: '600',
					width: '100%',
					videoId: 'oHJ8qvmRLUQ',
					playerVars: { 'controls': 0 },
					events: {
					  'onReady': onPlayerReady
					}
				  });
				}

				function onPlayerReady(event) { }
				function ytCarregar(url) { player.loadVideoById({videoId: url});player.playVideo(); }
				function ytTocar() { player.playVideo(); }
				function ytParar() { player.stopVideo(); }
				function ytPausar() { player.pauseVideo(); }

				function ytAbrir()
				{
					$('#btnAbrir').html('Fechar Youtube');
					$('#controlesYoutube').show();$('#videoYoutube').show();$('#".$mtz['name']."').hide();
				}
				function ytFechar()
				{
					$('#btnAbrir').html('Abrir Youtube');
					$('#controlesYoutube').hide();$('#videoYoutube').hide();$('#".$mtz['name']."').show();
				}

				function ytComandoRemoto(msg)
				{
					$.ajax({
						url: '".$o->page."&gAjax=1&cmd=ytCliCmd&msg='+msg,
						context: document.body
					})
					.done(function(data,textStatus){
						if (data!='0')
						{
							js = JSON.parse(data);
							for (let i=0; i<js.length; i++)
							{
								cmd = js[i];
								fez = false;
								switch(cmd)
								{
									case 'open':
										fez = true;
										ytAbrir();
										break;
									case 'close':
										fez = true;
										ytFechar();
										break;
									case 'play':
										fez = true;
										ytTocar();
										break;
									case 'stop':
										fez = true;
										ytParar();
										break;
									case 'pause':
										fez = true;
										ytPausar();
										break;
								}
								if (!fez)
								{
									if (cmd.substring(0,3)=='url')
									{
										ytCarregar(cmd.substring(4));
									} else {
										if (ytCallback !== undefined)
										{
											ytCallback(cmd);
										}
									}
								}
							}
						}
					});
				}

				$temporizador

				";

			}
			$html.="<div id='videoYoutube'></div>";


			$o->addJavaScript($js);
		}
		return($html);
	}

	/**
	 * Vídeo Conferência
	 *
	 * @param string $json onde:
	 * 						name          = Nome do div que será transformado na video-conferencia
	 * 						room          = Nome da sala
	 * 						userName      = Nome do usuário
	 * 						style         = Perfil: moderator (acesso a todas as funcionalidades), normal (acesso normal), guest (convidado), viewonly (sem interação)
	 * 						width, height = Dimensões
	 * @return void
	 */
	function videoConference($json)
	{
		global $usrId, $DB,$http_lib;

		$mtz = cssDecode($json);
		if ($mtz['name']=="")
		{
			$mtz['name'] = 'videoConference';
		}
		if ($mtz['room']=="")
		{
			$mtz['room'] = $DB;
		}
		$room = $mtz['room'];

		if ($mtz['userName']=="")
		{
			$mtz['userName'] = ($_SESSION['usrName']);
		}
		if ($mtz['email']=="")
		{
			$mtz['email'] = $_SESSION['usrEmail'];
		}

		if ($mtz['width']=="")
		{
			$mtz['width'] = "'100%'";
		}
		if ($mtz['height']=="")
		{
			$mtz['height'] = '600';
		}
		if ($mtz['onLoad']<>'')
		{
			$onLoad = 'onload: function(){ '.$mtz['onLoad'].' },';
		}
		$audio = 'true';
		$video = 'true';

		if ($mtz['audio']=="" || $mtz['audio']=="true")
		{
			$audio = 'false';
		}
		if ($mtz['video']=="" || $mtz['video']=="true" )
		{
			$video = 'false';
		}
		$server = 'meet.jit.si';
		if ($_SESSION['jitsi_server']<>"")
		{
			$server = $_SESSION['jitsi_server'];
		}
		if ($mtz['server']<>'')
		{
			$server = $mtz['server'];
		}

		$resolution = "120";
		$style = "
		configOverwrite: {
			defaultLanguage: 'ptBR',
			disableDeepLinking: true,
			lockRoomGuestEnabled: false,
			roomPasswordNumberOfDigits: 100,
			disableThirdPartyRequests: false,
			resolution: $resolution,
			enableNoisyMicDetection: false,
			prejoinPageEnabled: false,
			enableCalendarIntegration: false,
			enableWelcomePage: false,
			startWithAudioMuted: ".$audio.",
			startWithVideoMuted: ".$video.",
			desktopSharing: 'ext',
			desktopSharingFrameRate: { min: 5, max: 15 },
			desktopSharingChromeSources: [ 'window', 'tab' ],
			remoteVideoMenu: { disableKick: true }
		},
		$onLoad
		disableThirdPartyRequests: false,
		prejoinPageEnabled: false,
		lockRoomGuestEnabled: false,
		roomPasswordNumberOfDigits: 100,
		defaultLanguage: 'ptBR',
		enableNoisyMicDetection: false,
		enableCalendarIntegration: false,
		enableWelcomePage: false,
		startWithAudioMuted: ".$audio.",
		startWithVideoMuted: ".$video.",
		desktopSharing: 'ext',
		desktopSharingFrameRate: { min: 5, max: 15 },
		desktopSharingChromeSources: [ 'window', 'tab' ],
		remoteVideoMenu: { disableKick: true },
		interfaceConfigOverwrite: {
			TOOLBAR_BUTTONS: [
				'microphone', 'camera', 'desktop', 'fullscreen',
				'fodeviceselection', 'hangup', 'profile', 'chat',
				'settings', 'raisehand', 'sharedvideo',
				'videoquality', 'filmstrip', 'shortcuts',
				'tileview', 'help', 'mute-everyone'
			],
			SETTINGS_SECTIONS: [ 'devices', 'moderator', 'profile' ],
			HIDE_KICK_BUTTON_FOR_GUESTS: true,
			DISABLE_DOMINANT_SPEAKER_INDICATOR: true,
			DEFAULT_REMOTE_DISPLAY_NAME: 'Pessoa',
			SHOW_JITSI_WATERMARK: false,
			SHOW_WATERMARK_FOR_GUESTS: false,
			VIDEO_LAYOUT_FIT: 'both',
			OPTIMAL_BROWSERS: [ 'chrome', 'chromium'],
			UNSUPPORTED_BROWSERS: [ 'firefox', 'nwjs', 'electron' ],
			DISABLE_VIDEO_BACKGROUND: false,
			LANG_DETECTION: true
		}
		";
		switch(strtolower($mtz['style']))
		{
			case 'moderator':
				$style = "
				configOverwrite: {
					disableDeepLinking: true,
					defaultLanguage: 'ptBR',
					disableThirdPartyRequests: false,
					disableRemoteMute: false,
					prejoinPageEnabled: false,
					lockRoomGuestEnabled: false,
					roomPasswordNumberOfDigits: 100,
					resolution: $resolution,
					enableNoisyMicDetection: false,
					enableCalendarIntegration: false,
					disableInviteFunctions: true,
					enableWelcomePage: false,
					startWithAudioMuted: ".$audio.",
					startWithVideoMuted: ".$video.",
					doNotStoreRoom: true,
					desktopSharing: 'ext',
					desktopSharingFrameRate: { min: 5, max: 15 },
					desktopSharingChromeSources: [ 'window', 'tab' ]
				},
				$onLoad
				disableThirdPartyRequests: false,
				prejoinPageEnabled: false,
				lockRoomGuestEnabled: false,
				roomPasswordNumberOfDigits: 100,
				defaultLanguage: 'ptBR',
				disableRemoteMute: false,
				enableNoisyMicDetection: false,
				enableCalendarIntegration: false,
				enableWelcomePage: false,
				disableInviteFunctions: true,
				startWithAudioMuted: ".$audio.",
				startWithVideoMuted: ".$video.",
				desktopSharing: 'ext',
				desktopSharingFrameRate: { min: 5, max: 15 },
				desktopSharingChromeSources: [ 'window', 'tab' ],
				doNotStoreRoom: true,
				interfaceConfigOverwrite: {
					TOOLBAR_BUTTONS: [ 'microphone', 'camera', 'desktop', 'fullscreen', 'fodeviceselection', 'profile', 'chat',  'settings', 'raisehand', 'videoquality', 'filmstrip', 'shortcuts', 'tileview', 'help', 'mute-everyone', 'sharedvideo'],
					SETTINGS_SECTIONS: [ 'devices', 'moderator', 'profile' ],
					HIDE_KICK_BUTTON_FOR_GUESTS: true,
					DISABLE_DOMINANT_SPEAKER_INDICATOR: true,
					DEFAULT_REMOTE_DISPLAY_NAME: 'Pessoa',
					SHOW_JITSI_WATERMARK: false,
					SHOW_WATERMARK_FOR_GUESTS: false,
					VIDEO_LAYOUT_FIT: 'both',
					OPTIMAL_BROWSERS: [ 'chrome', 'chromium'],
					UNSUPPORTED_BROWSERS: [ 'firefox', 'nwjs', 'electron' ],
					DISABLE_VIDEO_BACKGROUND: false,
					LANG_DETECTION: true
				}
				";
			break;

			case 'guest':
				$style = "
				configOverwrite: {
					disableDeepLinking: true,
					defaultLanguage: 'ptBR',
					disableThirdPartyRequests: false,
					lockRoomGuestEnabled: false,
					roomPasswordNumberOfDigits: 100,
					resolution: $resolution,
					prejoinPageEnabled: false,
					enableNoisyMicDetection: false,
					enableCalendarIntegration: false,
					enableWelcomePage: false,
					disableRemoteMute: true,
					disableInviteFunctions: true,
					startWithAudioMuted: ".$audio.",
					startWithVideoMuted: ".$video.",
					desktopSharing: 'ext',
					desktopSharingFrameRate: { min: 5, max: 15 },
					desktopSharingChromeSources: [ 'window', 'tab' ],
					remoteVideoMenu: { disableKick: true }
				},
				$onLoad
				disableThirdPartyRequests: false,
				lockRoomGuestEnabled: false,
				prejoinPageEnabled: false,
				roomPasswordNumberOfDigits: 100,
				defaultLanguage: 'ptBR',
				enableNoisyMicDetection: false,
				enableCalendarIntegration: false,
				enableWelcomePage: false,
				disableRemoteMute: true,
				disableInviteFunctions: true,
				startWithAudioMuted: ".$audio.",
				startWithVideoMuted: ".$video.",
				desktopSharing: 'ext',
				desktopSharingFrameRate: { min: 5, max: 15 },
				desktopSharingChromeSources: [ 'window', 'tab' ],
				remoteVideoMenu: { disableKick: true },
				disableInviteFunctions: true,
				doNotStoreRoom: true,
				interfaceConfigOverwrite: {
					TOOLBAR_BUTTONS: [ 'microphone', 'camera', 'chat', 'shortcuts','raisehand' ],
					SETTINGS_SECTIONS: [ 'devices', 'profile' ],
					HIDE_KICK_BUTTON_FOR_GUESTS: true,
					DISABLE_DOMINANT_SPEAKER_INDICATOR: true,
					DEFAULT_REMOTE_DISPLAY_NAME: 'Pessoa',
					SHOW_JITSI_WATERMARK: false,
					SHOW_WATERMARK_FOR_GUESTS: false,
					VIDEO_LAYOUT_FIT: 'both',
					OPTIMAL_BROWSERS: [ 'chrome', 'chromium'],
					UNSUPPORTED_BROWSERS: [ 'firefox', 'nwjs', 'electron' ],
					DISABLE_VIDEO_BACKGROUND: false,
					LANG_DETECTION: true
				}
				";

			break;

			case 'viewonly':
				$style = "
				configOverwrite: {
					disableDeepLinking: true,
					defaultLanguage: 'ptBR',
					disableThirdPartyRequests: false,
					disableRemoteMute: true,
					disableInviteFunctions: true,
					prejoinPageEnabled: false,
					lockRoomGuestEnabled: false,
					roomPasswordNumberOfDigits: 100,
					resolution: $resolution,
					enableNoisyMicDetection: false,
					enableCalendarIntegration: false,
					enableWelcomePage: false,
					startWithAudioMuted: true,
					startWithVideoMuted: false,
					doNotStoreRoom: true
				},
				$onLoad
				disableThirdPartyRequests: false,
				lockRoomGuestEnabled: false,
				roomPasswordNumberOfDigits: 100,
				defaultLanguage: 'ptBR',
				prejoinPageEnabled: false,
				disableRemoteMute: true,
				disableInviteFunctions: true,
				enableNoisyMicDetection: false,
				enableCalendarIntegration: false,
				enableWelcomePage: false,
				startWithAudioMuted: true,
				startWithVideoMuted: false,
				doNotStoreRoom: true,
				interfaceConfigOverwrite: {
					TOOLBAR_BUTTONS: [ 'fullscreen','fodeviceselection', 'shortcuts'],
					SETTINGS_SECTIONS: [ 'devices', 'profile' ],
					HIDE_KICK_BUTTON_FOR_GUESTS: true,
					DISABLE_DOMINANT_SPEAKER_INDICATOR: true,
					DEFAULT_REMOTE_DISPLAY_NAME: 'Pessoa',
					SHOW_JITSI_WATERMARK: false,
					SHOW_WATERMARK_FOR_GUESTS: false,
					VIDEO_LAYOUT_FIT: 'both',
					OPTIMAL_BROWSERS: [ 'chrome', 'chromium'],
					UNSUPPORTED_BROWSERS: [ 'firefox', 'nwjs', 'electron' ],
					DISABLE_VIDEO_BACKGROUND: false,
					LANG_DETECTION: true
				}
				";
			break;
		}
		$l = "https://meet.jit.si/external_api.js";
		if (strpos($DB, 'sestsenat')!==false)
		{
			$linkDaAPI[]= "https://meet.giusoft.com.br/external_api.js";
			$linkDaAPI[]= "https://meet-a.giusoft.com.br/external_api.js";
			$linkDaAPI[]= "https://meet-b.giusoft.com.br/external_api.js";
			$linkDaAPI[]= "https://meet-d.giusoft.com.br/external_api.js";
			$linkDaAPI[]= "https://meet-ss.giusoft.com.br/external_api.js";
			$r = rand(0,4);
			$l = $linkDaAPI[$r];
			if ($l=="")
			{
				$l = "https://meet.giusoft.com.br/external_api.js";
			}
			gLog("MEET SORTEADO: $DB - $l");
		} else {

			gLog("MEET SEM SORTEIO: $DB - $l");
		}

		$this->out("<script src='$l'></script>". $o->n, gLOC_POS);

		$avatar = "";
		if ($mtz['avatar']<>"")
		{
			// $avatar = "api.executeCommand('avatarUrl', '".$mtz['avatar']."');";
			$avatar = " api.addListener('videoConferenceJoined', () => { api.executeCommand('avatarUrl', '".$mtz['avatar']."') });";
		}
		if ($server<>"server")
		{
			$server = "'$server'";
		}
		if ($room<>"room" && $room<>'data')
		{
			$room = "'$room'";
		}
		$js = "
		// ".$mtz['style']."
		domain = ".$server.";
		options = {
			userInfo: {
				email: '".$mtz['email']."',
				displayName: '".$mtz['userName']."'
			},
			parentNode: document.querySelector('#".$mtz['name']."'),
			roomName: ".$room.",
			width: ".$mtz['width'].",
			height: ".$mtz['height'].",
			resolution: $resolution,
			".$style."
		};
		api = new JitsiMeetExternalAPI(domain, options);
		$avatar
		";
		if (strtolower($mtz['style'])!='moderator'){
			$js.="$('jitsi-icon').hide();
			";
		}
		return($js);
	}


    function treeview($json)
    {

    }

}

$device = $gDevice;
$device = "web";

if ($device == "web") {

	if ($_SESSION['gFW4']<>'')
	{
		class gOutput extends g_Output {
			function gBody($par="") { $this->body($par); }
			function gBegin($par="") { $this->begin($par); }
			function gEnd($par=true) { $this->end($par); }
			function gMsg($par) { $this->msg($par); }
			function gMsgTitle($par) { $this->msgTitle($par); }
			function gMsgSubTitle($par) { $this->msgSubTitle($par); }
			function gMsgError($par) { $this->msgError($par); }
		}
	} else
	{
		class gOutput extends g_Output {}
	}


} else {
	$inc = $gPathDefault . "dev" . gBAR . strtolower($device) . gBAR . "gOutput.php";
	if (file_exists($inc)) {
		include_once $inc;
	} else {
		$out = new g_Output();
		$out->gError("Erro", "dispositivo de acesso ao sistema não encontrado: <br>inc: $inc<br>$device");
	}
}


if ($_REQUEST['gAjax']==1)
{
	switch ($_REQUEST['cmd'])
	{
		case 'ytSrvCmd':
			$msg = gCleanField($_REQUEST['msg']);
			$id_room = intval($_SESSION['youtube_id_room']);
			//gLog("Youtube - ".$_REQUEST['cmd']." - room: ".$id_room." - msg: ".$msg);
			$sql = "INSERT INTO gfw_youtube (date,message, id_room) VALUES ('".date("Y-m-d H:i:s")."', '$msg', $id_room)";
			dbFastQuery($sql);
			echo "1";
			exit;
		break;

		case 'ytCliCmd':
			// $msg = gCleanField($_REQUEST['msg']);
			// $id_room = intval($_SESSION['youtube_id_room']);
			// //gLog("Youtube - ".$_REQUEST['cmd']." - room: ".$id_room." - msg: ".$msg);
			// $sql = "SELECT * FROM gfw_youtube_users WHERE DATE(date)='".date("Y-m-d")."' AND id_gfw_users=$usrId ORDER BY id desc LIMIT 1";
			// $row2 = dbFastQuery($sql)[0];
			// $sql = "SELECT * FROM gfw_youtube WHERE id_room=$id_room AND date>'".$row2['date']."' ";
			// $rs1 = dbFastQuery($sql);
			// $row1 = $rs1[0];
			// if ($row1['id']>0 && $row1['id']<>$row2['id_gfw_youtube'])
			// {
			// 	if($row2['id_gfw_youtube']=='')
			// 	{
			// 		$sql = "INSERT INTO gfw_youtube_users (id_gfw_users,date,id_gfw_youtube) VALUES ($usrId, '".date("Y-m-d H:i:s")."', ".intval($row1['id']).") ";
			// 	} else {
			// 		$sql = "UPDATE gfw_youtube_users SET date='".date("Y-m-d H:i:s")."', id_gfw_youtube=".intval($row1['id'])." WHERE id_gfw_users=".$usrId;
			// 	}
			// 	dbFastQuery($sql);
			// 	$msg = array();
			// 	foreach ($rs1 as $row1)
			// 	{
			// 		$msg[] = $row1['message'];
			// 	}
			// 	echo json_encode($msg);
			// } else {
			// 	if ($row2['id']==0)
			// 	{
			// 		$sql = "INSERT INTO gfw_youtube_users (id_gfw_users,date,id_gfw_youtube) VALUES ($usrId, '".date("Y-m-d H:i:s")."', 0) ";
			// 	} else {
			// 		$sql = "UPDATE gfw_youtube_users SET date='".date("Y-m-d H:i:s")."', id_gfw_youtube=0 WHERE id_gfw_users=".$usrId;
			// 	}
			// 	dbFastQuery($sql);
			// 	echo "0";
			// }
			echo "0";
			exit;

		break;
	}
}