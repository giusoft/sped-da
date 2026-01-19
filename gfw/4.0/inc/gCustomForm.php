<?php

include_once $gPathDefault . 'gInput.php';




/**
 * Classe responsável pela criação de um formulário
 * @package	gForm
 * @author	Giuliano Nascimento <giusoft@hotmail.com>
 * @version	4.0 01-12-2013 10:50
 */
class g_Form extends g_Stdout
{

	public $ajson;
	public $fields;
	public $lists;
	public $buttonNextCaption = '';
	public $buttonBackCaption = '';
	public $buttons;
	public $buttonsJavascript;

	/**
	 * Prepara ambiente para geração de conteúdo
	 * @author	giuliano
	 * @param $json Parâmetros em formato JSON: {debug: [true,false]; onlyBody: [true,false] (gera só código do meio da página)}
	 * @version	4.0 01-12-2013 10:50
	 */
	function __construct($json = '', $ajson = '')
	{
		global $http_lib, $http_css, $http_inc, $http_img, $gDevice, $gLang;
		parent::__construct($json);
		$jarr=$this->jarr;

		$bootstrapAddonsPath	= $http_lib . gVar("lib.bootstrap_addons");
		//$select					= $http_lib . gVar("lib.select");
		$selectize				= $http_lib . gVar("lib.selectize");
		$moment					= $http_lib . gVar("lib.moment");

		$lang = str_replace("_","-",$gLang);

		// CSS
		// DateTime picker
		$this->out('<link href="' . $bootstrapAddonsPath . 'bootstrap-datetimepicker-master/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css" media="screen">', gLOC_PRE, 2);
		// Multiselect
		$this->out('<link href="' . $bootstrapAddonsPath . 'bootstrap-multiselect/bootstrap-multiselect.css" rel="stylesheet" type="text/css" media="screen">', gLOC_PRE);
		// Select (mais bonito)
		if ($select)
			$this->out('<link href="' . $select . 'bootstrap-select.min.css" rel="stylesheet">', gLOC_PRE,2);
		if ($selectize)
			$this->out('<link href="' . $selectize . 'dist/css/selectize.bootstrap3.css" rel="stylesheet">', gLOC_PRE,2);

		// JS
		// Moment - biblioteca javascript para tratamento de datas - multi-idioma
		$this->out('<script src="' . $moment . '"></script>', gLOC_POS,-1);

		// DateTime picker
		$this->out('<script src="' . $bootstrapAddonsPath . 'bootstrap-datetimepicker-master/build/js/bootstrap-datetimepicker.min.js"></script>', gLOC_POS,-1);
		$this->out('<script src="' . $bootstrapAddonsPath . 'bootstrap-datetimepicker-master/src/js/locales/bootstrap-datetimepicker.' . $lang . '.js"></script>', gLOC_POS);
		// Multiselect
		$this->out('<script src="' . $bootstrapAddonsPath . 'bootstrap-multiselect/bootstrap-multiselect.js"></script>', gLOC_POS);
		// Touchspin
		$this->out('<script src="' . $bootstrapAddonsPath . 'bootstrap-touchspin-master/bootstrap-touchspin/bootstrap.touchspin.js"></script>', gLOC_POS);
		// Select
		if ($select)
			$this->out('<script src="' . $select . 'bootstrap-select.min.js"></script>', gLOC_POS);
		if ($selectize)
			$this->out('<script src="' . $selectize . 'dist/js/standalone/selectize.min.js"></script>', gLOC_POS);

		// Se $ajson existe, então os campos foram passados como um array... adiciona logo então...
		if (is_array($ajson)) {
			foreach ($ajson as $fld)
				$this->add($fld);
		}
	}

	/**
	 * Adiciona um botão ao formulário (sem exibí-lo)
	 * @author	giuliano
	 * @param string $json Parâmetros pra criação do campo em formato JSON
	 * @version	4.0 01-12-2013 10:50
	 */
	function addButton($json, $javascript='')
	{
		$this->buttons[] = cssDecode($json);
		$this->buttonsJavascript[]=$javascript;
	}

	/**
	 * Adiciona um campo ao formulário (sem exibí-lo)
	 * @author	giuliano
	 * @param string $json Parâmetros pra criação do campo em formato JSON
	 * @param string $list Utilizado somente para Combolist (select) - array de elementos
	 * @version	4.0 01-12-2013 10:50
	 */
	function add($json, $list = "")
	{
		if (trim($json) <> "") {
			$mtz = cssDecode($json);
			if ($mtz['type'] <> 'exclude') {
				$name = $mtz['name'];
				$fieldLabel = $mtz['fieldLabel'];
				$type = $mtz['type'];

				if ($name == "")
					$name = gString2Field($fieldLabel);
				if (($type <> 'label') && ($fieldLabel == ""))
					$fieldLabel = gField2String($name);
				$mtz['name'] = $name;
				$mtz['fieldLabel'] = gT($fieldLabel);
				$this->fields[] = $mtz;

				$this->lists[] = $list;
			}
		}
	}

	/**
	 * Obtém array contendo os campos do formulário
	 * @author	giuliano
	 * @param string $js Código javascript
	 * @version	4.0 01-12-2013 10:50
	 */
	function get()
	{
		return($this->fields);
	}

	function setButtonNextCaption($caption)
	{
		$this->buttonNextCaption = $caption;
	}

	function setButtonBackCaption($caption)
	{
		$this->buttonBackCaption = $caption;
	}

	/**
	 * Gera saída formatada em HTML do formulário
	 * @author	giuliano
	 * @param $output class Objeto output a renderizar
	 * @version	4.0 01-12-2013 10:50
	 */
	function render(&$output = '')
	{
		global $gDevice,$gPathLib;


		$jarr = $this->jarr;

		$style = '2column';
		if ($jarr['style'] <> '') {
			$style = $jarr['style'];
		}
		$size = '';
		if ($jarr['size'] <> '')
			$size = $jarr['size'];

		$cont = $this->n;
		$this->formCount=$this->formCount+1;
		$formName = 'gForm' . $this->formCount;
		$parFrm = '';
		$parFrm['role'] = 'form';
		$parFrm['method'] = 'post';
		$parFrm['action'] = $_SERVER["PHP_SELF"] . "?g=" . $_REQUEST['g'];
		switch($style){
			case '2column':
				$parFrm['class'] = 'form-horizontal';
			break;
			case 'inline':
				$parFrm['class'] = 'form-inline';
			break;
		}

		$hidden = array();
		$temCombo = false;
		$hasFileMultiple = false;
		$dateLang = str_replace("_", "-", gVar('global.language'));
		$dateFormat = strtoupper(gVar("global.dateformat"));
		$dateTimeFormat = strtoupper(gVar("global.dateformat")) . " HH:mm";
		$timeFormat = "HH:mm:ss";

		$colLabel = 'col-xs-4 col-sm-3 col-md-2 col-lg-2';
		$colField = 'col-xs-8 col-sm-9 col-md-10 col-lg-10';

		if ($this->jarr['name'] <> '')
			$formName = $this->jarr['name'];
		$parFrm['name'] = $formName;
		$parFrm['id'] = $formName;

		if (($this->jarr['autoValidate'] == "false") || ($this->jarr['autoValidate'] == "off")) {
			$autoValidate = false;
		} else {
			$autoValidate = true;
			$parFrm['parsley-validate'] = "";
			$this->addJavascript("$( '#form' ).parsley();");
		}

		$script_gajax = array();

		//foreach ($this->flds as $fld)
		for ($n = 0; $n < count($this->fields); $n++) {
			$fld = $this->fields[$n];
			$list = $this->lists[$n];

			if ($fld['type'] == 'hidden') {
				$hidden[$fld['name']] = $fld['value'];
			} else {
				// Campo
				$id = trim($fld['name']);
				$name = $id;
				$value = trim($fld['value']);
				$maxLength = intval($fld['maxLength']);
				$minLength = intval($fld['minLength']);
				$class = "form-control";
				$help = '';
				$addMore = '';
				$preAddon = '';
				$posAddon = '';
				$frmFld = '';
				$help = '';
				$dpar = '';
				$type = 'text';
				$pType = '';
				$multiple = '';
				$pTrigger = 'focusout';
				$pRegexp = '';
				$par = '';
				$label = '';
				$maxItems = 1;
				if ($style == '0column' || $style == 'inline')
				{
					$fld['hint']=$fld['fieldLabel'];
				}
				// Label
				if ($fld['fieldLabel'] <> '') {
					$par['for'] = $id;
					if ($style == '2column')
						$par['class'] = $colLabel . ' control-label';
					elseif ($style == 'inline')
						$par['class'] = 'sr-only';

					if ($size == "small")
						$label = tagMe('label', '<p class="text-left">' . tagMe('small', $fld['fieldLabel']) . '</p>', $par);
					else
						$label = tagMe('label', '<p class="text-left">' . $fld['fieldLabel'] . '</p>', $par);
					$par = '';
				}

				switch($fld['type']) {
					case 'show':
						$p['class'] = 'text-left';
						$frmFld = tagMe('p', $value, $p);
						$fld['allowBlank']='true';
						break;
					case 'label':
						$p['class'] = 'text-left';
						$frmFld = tagMe('p', $value, $p);
						break;
					case 'text':
						break;
					case 'upperText':
						$addMore.=' onBlur="this.value=this.value.toUpperCase()"';
						break;
					case 'lowerText':
						$addMore.=' onBlur="this.value=this.value.toLowerCase()"';
						break;
					case 'upperFirstWordText':
						$addMore.=' onBlur="vUFWText(this)"';
						break;
					case 'upperFirstLetterText':
						$addMore.=' onBlur="vUFText(this)"';
						break;
					case 'password':
						//$par['class'] = 'input-group';
						//$posAddon='<span class="input-group-addon">*</span>';
						$type = 'password';
						break;
					case 'email':
						//$par['class'] = 'input-group';
						//$posAddon='<span class="input-group-addon">@</span>';
						$type = 'email';
						$pType = "email";
						break;
					case 'html':
						$frmFld = $value;
						break;
					case 'textarea':
						$css = '';
						if ($size == "small")
							$css = ' input-sm';
						$frmFld = '<textarea id="' . $id . '" name="' . $name . '" class="form-control' . $css . '" rows="3"' . $addMore . '>' . $value . '</textarea>';
						break;
					case 'recaptcha':
						$frmFld=recaptcha_get_html(gVar("recaptcha.publicKey"), $error);

						if(!empty($_SERVER['HTTPS']))
							$frmFld=str_replace('http','https',$frmFld);

						break;
					case 'memo':
						$css = '';
						if ($list<>'')
							$value=$list;
						if ($size == "small")
							$css = ' input-sm';
						$frmFld = '<textarea id="' . $id . '" name="' . $name . '" class="form-control' . $css . '" rows="3"' . $addMore . '>' . $value . '</textarea>';
						$height=150;
						$indent='';
						$h1=true;
						if ($fld['indent']<>'')
							$extra.='; indent: '.$fld['indent'];
						if ($fld['style']<>'')
							$extra.='; style: '.$fld['style'];
						if ($fld['align']<>'')
							$extra.='; align: '.$fld['align'];
						if ($fld['image']<>'')
							$extra.='; image: '.$fld['image'];
						if ($fld['h1']<>'')
							$extra.='; h1: '.$fld['h1'];
						if ($fld['h2']<>'')
							$extra.='; h2: '.$fld['h2'];
						if ($fld['h3']<>'')
							$extra.='; h3: '.$fld['h3'];
						if ($fld['h4']<>'')
							$extra.='; h4: '.$fld['h4'];
						if ($fld['code']<>'')
							$extra.='; code: '.$fld['code'];
						if ($fld['alerts']<>'')
							$extra.='; alerts: '.$fld['alerts'];

						if ($fld['height']>0)
							$height=intval($fld['height']);
						$frmFld = $this->wysiwyg("{name: $name; id: $id; height: $height $extra}", $value);
						break;
					case 'date':
						$type = "text";
						$dpar[] = "format: '" . $dateFormat . "'";
						$dpar[] = "language: '" . $dateLang . "'";
						$dpar[] = "pickTime: false";
						if ($fld['startDate'] <> '')
							$dpar[] = 'startDate:"' . $fld['startDate'] . '"';
						if ($fld['endDate'] <> '')
							$dpar[] = 'endDate:"' . $fld['endDate'] . '"';
						//$js="$(function () ".'{'."$('#".$id."').datetimepicker(".'{'.implode(",",$dpar).'}'.");".'}'.");";
						$js = "$('#" . $id . "').datetimepicker(" . '{' . implode(",", $dpar) . '}' . ");";
						$this->addJavascript($js);
						$par['class'] = 'input-group date';
						$posAddon = '<span class="input-group-addon"><span class="'.$this->iconFont.' '.$this->iconFont.'-calendar"></span></span>';
						break;
					case 'time':
						$type = "text";
						$dpar[] = "format: '" . $timeFormat . "'";
						$dpar[] = "language: '" . $dateLang . "'";
						$dpar[] = "pickDate: false";
						if ($fld['startDate'] <> '')
							$dpar[] = 'startDate:"' . $fld['startDate'] . '"';
						if ($fld['endDate'] <> '')
							$dpar[] = 'endDate:"' . $fld['endDate'] . '"';
						$js = "$('#" . $id . "').datetimepicker(" . '{' . implode(",", $dpar) . '}' . ");";
						$this->addJavascript($js);
						$par['class'] = 'input-group date';
						$posAddon = '<span class="input-group-addon"><span class="'.$this->iconFont.' '.$this->iconFont.'-calendar"></span></span>';
						break;
					case 'dateTime':
						$type = "text";
						$dpar[] = "format: '" . $dateTimeFormat . "'";
						$dpar[] = "language: '" . $dateLang . "'";
						if ($fld['startDate'] <> '')
							$dpar[] = 'startDate:"' . $fld['startDate'] . '"';
						if ($fld['endDate'] <> '')
							$dpar[] = 'endDate:"' . $fld['endDate'] . '"';
						$js = "$('#" . $id . "').datetimepicker(" . '{' . implode(",", $dpar) . '}' . ");";
						$this->addJavascript($js);
						$par['class'] = 'input-group date';
						$posAddon = '<span class="input-group-addon"><span class="'.$this->iconFont.' '.$this->iconFont.'-calendar"></span></span>';
						break;
					case 'url':
						$type = "url";
						$pType = "urlstrict";
						$par['class'] = 'input-group';
						//$preAddon='<span class="input-group-addon">http://</span>';
						break;
					case 'number':
						$type = "number";
						$pType = "number";
						if (substr(gVar("global.numformat"),0,8)=="0.000,00")
						{
							$type = "numberBr";
							$pType = "numberBr";
						}
						$addMore.=' step="any"';
						break;
					case 'integer':
						$type = "number";
						$pType = "number";
						if (substr(gVar("global.numformat"),0,8)=="0.000,00")
						{
							$type = "numberBr";
							$pType = "numberBr";
						}
						$max = 999999999;
						$min = -999999999;
						if ($fld['maxValue'] <> '')
							$max = floatval($fld['maxValue']);
						if ($fld['minValue'] <> '')
							$min = floatval($fld['minValue']);
						$dpar[] = "min: " . intval($min);
						$dpar[] = "max: " . intval($max);
						$dpar[] = "decimals: 0";
						$dpar[] = "boostat: 5";
						$dpar[] = "maxboostedstep: 100";
						$dpar[] = "step: 1";
						$js = "$('#" . $id . "').TouchSpin(" . '{' . implode(",", $dpar) . '}' . ");";
						$this->addJavascript($js);
						break;
					case 'percent':
						$type = "number";
						$dpar[] = "postfix: '%'";
						$dpar[] = "min: 0";
						$dpar[] = "max: 100";
						$dpar[] = "decimals: 2";
						$dpar[] = "boostat: 5";
						$dpar[] = "maxboostedstep: 100";
						$dpar[] = "step: 0.1";
						$js = "$('#" . $id . "').TouchSpin(" . '{' . implode(",", $dpar) . '}' . ");";
						$this->addJavascript($js);
						$addMore.=' step="any"';
						break;
					case 'search':
						$type = "search";
						break;
					case 'color':
						$type = "color";
						break;
					case 'phone':
						$type = "tel";
						$maxLength = 15;
						$addMore.=' onBlur="this.value=this.value.toUpperCase()"';
						break;
					case 'ip':
						$type = "text";
						$pRegexp = '([1-9][0-9]{0,1}|1[013-9][0-9]|12[0-689]|2[01][0-9]|22[0-3])([.]([1-9]{0,1}[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])){2}[.]([1-9][0-9]{0,1}|1[0-9]{2}|2[0-4][0-9]|25[0-4])';
						$maxLength = 15;
						break;
					case 'container':
						$type = "text";
						$pRegexp = '[A-Za-z]{4}[0-9]{6,7}';
						$maxLength = 11;
						break;
					case 'interpos':
						$type = "text";
						$pRegexp = '[0-9]{1}[A-Za-z]{1}[0-9]{2}[0-9]{2}[0-9]{1}';
						$maxLength = 11;
						break;
					case 'plate':
						$type = "text";
						$pRegexp = '[A-Fa-f]{3}[0-9]{4}';
						$maxLength = 7;
						//$addMore.=' onBlur="this.value=this.value.toUpperCase()"';
						break;
					case 'cpf':
						$type = "number";
						$maxLength = 11;
						break;
					case 'ncm':
						$type = "number";
						$maxLength = 8;
						$addMore.=' onBlur="vNCM(this)"';
						break;
					case 'cnpj':
						$type = "number";
						$maxLength = 14;
						$addMore.=' onBlur="this.value=this.value.toUpperCase()"';
						break;
					case 'cpfcnpj':
						$type = "number";
						$maxLength = 14;
						break;
					case 'file':
						$type = 'file';
						$class = '';
						$pTrigger = '';
						$parFrm['enctype'] = "multipart/form-data";
						$preAddon='<div class="fileinput fileinput-new" data-provides="fileinput"><span class="btn btn-default btn-file"><span class="fileinput-new">'.gT("select_file").'</span><span class="fileinput-exists">'.gT("change").'</span>';
						$posAddon='</span><span class="fileinput-filename"></span><a href="#" class="close fileinput-exists" data-dismiss="fileinput" style="float: none">&times;</a></div>';
						if($fld['multiple'] == 'true'){
							$hasFileMultiple = true;
							$posAddon.= '<p><a class="add-other-file" href="" data-input="'.$name.'" data-max="'.(!empty($fld['multipleMax'])? intval($fld['multipleMax']) : 0).'" data="'.htmlspecialchars('<div class="fileinput-multiple"><a href="" class="btn btn-default fileinput-remove"  data-input="'.$name.'"><span class="fal fa-eraser"></span></a> '.$preAddon.'<input type="file" name="'.$name.'[]" />'.$posAddon.'</div>').'">'.gT('add_other_file', ENT_QUOTES).'</a></p>';
							$name.= '[]';
						}
					break;
					case 'image':
						$type = 'file';
						$class = '';
						$parFrm['enctype'] = "multipart/form-data";
						$pTrigger = '';
						$preAddon='<div class="fileinput fileinput-new" data-provides="fileinput"><span class="btn btn-default btn-file"><span class="fileinput-new">'.gT("select_image").'</span><span class="fileinput-exists">'.gT("change").'</span>';
						$posAddon='</span><span class="fileinput-filename"></span><a href="#" class="close fileinput-exists" data-dismiss="fileinput" style="float: none">&times;</a></div>';
						break;
					case 'checkbox':
						$type = 'checkbox';
						$preAddon = '<p class="text-left">';
						$posAddon = '</p>';
						$class = '';
						$pTrigger = '';
						$addMore.=' parsley-group="gChkGroup"';
						if (gDBCheck($value)==1)
							$addMore.=' checked';
						$value='';
						$fld['allowBlank']='true';
						break;
					case 'comboMultiSelection':
						$multiple = 'multiple="multiple"';
						$maxItems = 100;
						$name.='[]';

						if(strpos($value,","))
							$value = jcombo2array($value);

					// este parâmetro foi deixado intencionalmente sem o "break" !!!
					case 'combo':
						if(!empty($value))
							$value = (is_array($value))? $value : array($value);
						else
							$value=array();

						$pTrigger = '';
						$temCombo = true;
						$css = 'form-control';
						if ($style == '2column')
							$css = "";
						if ($size == "small")
							$css.=' input-sm';
						$css = trim($css);
						if (gVar("lib.selectize")<>'' && empty($fld['disableSelectize']))
						{
							if ($fld['allowNew']=="true")
								$this->addJavascript("\$select_" . $id . " = \$(function() {\$('#" . $id . "').selectize({delimiter: ',', maxItems: $maxItems, sortField: 'text', persist: false, createOnBlur: true, create: true, onInitialize:function(){\$('#".$id."').next('.selectize-control').find('div').removeClass('required','parsley-validated');$('#".$id."').next('.selectize-control').find('input').removeAttr('required');} });})");
							else
								$this->addJavascript("\$select_" . $id . " = \$(function() {\$('#" . $id . "').selectize({delimiter: ',', maxItems: $maxItems, sortField: 'text', persist: false, onInitialize:function(){\$('#".$id."').next('.selectize-control').find('div').removeClass('required','parsley-validated');$('#".$id."').next('.selectize-control').find('input').removeAttr('required');}});})");
							//$css=trim($css." selectpicker");
						}else{
							$css .= ' form-control';
						}

						//<input type="text" id="input-tags" class="demo-default" value="awesome,neat">
						$placeholder = $fld['hint'];
						if ($placeholder=='')
							$placeholder=gT("select");
						$required='';
						$indiferente='';
						if (($fld['allowBlank'] == 'false') || ($fld['allowBlank'] == 'off')) {
							$required='required';
						} else
						{
							$indiferente='<option value="0">* '.gT("Indiferente").'</option>';
						}
						$frmFld = '<select id="' . $id . '" name="' . $name . '" class="' . $css . '" ' . $multiple . ' placeholder="' . $placeholder . '" '.$required.'>'.$indiferente;
						$items = $fld['items'];

						if ($items <> '') {
							$list = '';
							$list = jcombo2array($items);
						}

						if ($fld['remote'] == 'true') {
							// TODO: Ajax para combo
						} else {
							$cnt = 0;
							if ( ( ($fld['allowBlank'] == 'true') || ($fld['allowBlank'] == 'on') ) ) {
								$frmFld.="<option value=''></option>";
							}
							foreach ($list as $key => $val) {
								$sel = '';
								if (in_array($key,$value) || in_array($val,$value))
									$sel = ' selected';
								$frmFld.='<option value="' . $key . '"' . $sel . '>' . $val . '</option>';
								$cnt++;
							}
						}
						$frmFld.='</select>';
						$value='';

						if(!empty($fld['comboTarget']) && !empty($fld['comboTargetValues'])){
							global $gAjax;
							$gAjax->comboDynamicValues($id,$fld['comboTarget'],$fld['comboTargetValues']);
						}

					break;


					case 'timezonepicker':
						global $http_lib;
						$timZoneLib=$http_lib.'timezonepicker/';
						include $gPathLib.'timezonepicker/includes/parser.inc';
						$timezones = timezone_picker_parse_files(600, 300, $gPathLib.'timezonepicker/tz_world.txt', $gPathLib.'timezonepicker/tz_islands.txt');

						//$this->out('<link href="'.$timZoneLib.'timezonepicker.css" rel="stylesheet" type="text/css">', gLOC_PRE,2);
						$this->out('<script src="'.$timZoneLib.'lib/jquery.maphilight.min.js"></script>', gLOC_POS);
						$this->out('<script src="'.$timZoneLib.'lib/jquery.timezone-picker.min.js"></script>', gLOC_POS);

						$js="
						$(document).ready(function() {
						      $('#timezone-image').timezonePicker({
						        target: '#edit-date-default-timezone',
						        countryTarget: '#edit-site-default-country'
						      });

						      $('#timezone-detect').click(function() {
						        $('#timezone-image').timezonePicker('detectLocation');
						      });
						    });
						";
						/*
						 * $('#edit-date-default-timezone').on('focus',function(){
									 $('#timezone-picker').slideDown();
								});
								*/
						$this->addJavascript($js);

						$name=$fld['name']<>""? $fld['name'] : 'timezone';

						$indiferente="";
						if (($fld['allowBlank'] == 'false') || ($fld['allowBlank'] == 'off')) {
							$required='required';
						} else
						{
							$indiferente='<option value="0">* '.gT("Indiferente").'</option>';
						}

						$options=include $gPathLib.'timezonepicker/timezonedata.php';

						$frmFld="<select id='edit-date-default-timezone' name='$name'  class='form-control' $required>$indiferente";
						foreach($options as $opt => $txt)
						{
							$selected="";
							if($fld['value']==$opt)
								$selected="selected";
								$frmFld.="<option value='$opt' $selected>$txt</option>";
						}
						$frmFld.="</select>
						<br/>
						<div id='timezone-picker'>
							<img id='timezone-image' src='".$timZoneLib."images/blue-marble-600.jpg' width='600' height='300' usemap='#timezone-map' />
							<img class='timezone-pin' src='".$timZoneLib."images/pin.png' style='padding-top: 4px;' />
							<map name='timezone-map' id='timezone-map'>";
								foreach ($timezones as $timezone_name => $timezone)
								{
									foreach ($timezone['polys'] as $coords)
									{
										$frmFld.="<area data-timezone='$timezone_name' data-country='".$timezone['country']."' data-pin='".implode(',', $timezone['pin'])."' data-offset='".$timezone['offset']."'  shape='poly' coords='".implode(',', $coords)."' />";
									}
									foreach ($timezone['rects'] as $coords)
									{
										$frmFld.="<area data-timezone='$timezone_name' data-country='".$timezone['country']."' data-pin='".implode(',', $timezone['pin'])."' data-offset='".$timezone['offset']."' shape='rect' coords='".implode(',', $coords)." ' />";
									}
								}
						$frmFld.="
							</map>
							<br/>
						</div>";

						if ($fld['openModal']=="true")
						{
							$btn_title = $fld['value']? $fld['value'] : gT('Selecionar Fuso Horário');
							$frmFld = '<div class="modal fade" id="modal-timezone">
										<div class="modal-dialog" style="width:643px">
											<div class="modal-content">
												<div class="modal-header">
													<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
													<h4 class="modal-title">'.gT('Time Zone').'</h4>
												</div>
												<div class="modal-body">
													'.$frmFld.'
												</div>
												<div class="modal-footer">
													<button type="button" class="btn btn-default" data-dismiss="modal">'.gT('Cancelar').'</button>
													<button type="button" class="btn btn-primary btn-timezone-confirma"  data-dismiss="modal">'.gT('Confirmar').'</button>
												</div>
											</div>
										</div>
									</div>'.
									gOutput::button("{type: button; title: " . $btn_title . "; name: btn-timezone-modal; style: default; openModal:modal-timezone;}");

									$this->addJavascript('
										$(document).ready(function() {
											$(".btn-timezone-confirma").click(function(){
												if($("#edit-date-default-timezone").val()){
													$("[name=btn-timezone-modal]").html($("#edit-date-default-timezone").val());
												}
											});
										});
									');
						}
					break;

				}
				if ($size == "small")
					$class = trim($class) . ' input-sm';
				// Maxlength
				if ($maxLength > 0) {
					$addMore.=' maxlength="' . $maxLength . '"';
				}
				// Hint
				$placeholder = $fld['hint'];
				if ($placeholder <> '')
					$addMore.=' placeholder="' . $placeholder . '"';
				// Value
				if ($value <> '')
					$addMore.=' value="' . $value . '"';
				// Help
				if ($fld['help'] <> '') {
					$help = '<span class="help-block">' . $fld['help'] . '</span>';
				}
				// Validate
				if ($autoValidate) {
					if ($pTrigger <> '') {
						$addMore.=' parsley-trigger="' . $pTrigger . '"';
						if ($pRegexp <> '') {
							$addMore.=' parsley-regexp="' . $pRegexp . '"';
						} else {
							if ($pType <> '')
								$addMore.=' parsley-type="' . $pType . '"';
						}
					}
				}
				// AllowBlank
				if (($fld['allowBlank'] == 'false') || ($fld['allowBlank'] == 'off')) {
					$addMore.=' required';
				}
				if($fld['event']<>"")
				{
					$addMore.=" ".$fld['event'];
				}
				//$frmSize
				if ($frmFld == '')
					$frmFld = $preAddon . '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" class="' . $class . '"' . $addMore . ' />' . $posAddon . $help;
				$par['class'] = trim($par['class'] . ' ' . $colField);
				if ($style == '2column')
					$field = tagMe('div', $frmFld, $par);
				else
					$field = $frmFld;
				//echo "<textarea>$frmFld</textarea>";exit;

				// Linha
				$par['class'] = 'form-group';
				if ($style == '0column')
					$cont.=tagMe('div', $field, $par) . $this->n;
				else
					$cont.=tagMe('div', $label . $field, $par) . $this->n;
			}

		}

		foreach ($hidden as $key => $value)
			$cont.='<input type="hidden" name="' . $key . '" id="' . $key . '" value="' . $value . '">' . $this->n;

		// Parâmetros do formulário
		if ($this->jarr['method'] <> '')
			$parFrm['method'] = $this->jarr['method'];
		if ($this->jarr['action'] <> '')
			$parFrm['action'] = $this->jarr['action'];
		if ($this->jarr['url'] <> '')
			$parFrm['action'] = $this->jarr['url'];


		// Botões
		//$buttons='<button type="button" class="btn btn-primary">'.gT('confirm').'</button>';
		//$buttons='<input type="submit" value="'.gT('confirm').'" class="btn btn-primary" onclick="javascript:$('."'".'#'.$formName."'".').parsley( '."'".'validate'."'".' );">';
		$buttons = '';
		$submitBtn = gT('confirm');
		if ($this->buttonNextCaption <> '')
			$submitBtn = $this->buttonNextCaption;
		if ($this->buttonBackCaption <> '') {
			$buttons = gOutput::button("{type: button; title: " . $this->buttonBackCaption . "; size: $size; style: default; url: history.go(-1)").'<div class="hidden-lg hidden-md hidden-sm"><br /></div>';
		}
		$buttons.=gOutput::button("{type: submit; name: submit_default; title: " . $submitBtn . "; size: $size; style: primary}").'<div class="hidden-lg hidden-md hidden-sm"><br /></div>';
		if (is_array($this->buttons)) {
			foreach ($this->buttons as $indx=>$btn) {
				$btnStyle = "primary";
				$btnType = "button";
				$btnIcon = "";
				if ($btn['style'] <> '')
					$btnStyle = $btn['style'];
				if ($btn['type'] <> '')
					$btnType = $btn['type'];
				if ($btn['icon'] <> '')
					$btnIcon = 'icon: '.$btn['icon'].'; ';

				$buttons.=gOutput::button("{".$btnIcon." type: $btnType; name: " . $btn['name'] . "; title: " . $btn['title'] . "; size: $size; style: $btnStyle; url: " . $btn['url'] . "; href: " . $btn['href'] . "}", $this->buttonsJavascript[$indx]).'<div class="hidden-lg hidden-md hidden-sm" style="height: 1px"><br/></div>';
			}
		}
		if ($style == '2column') {
			$buttons = tagMe('div', $buttons, 'class="' . $colField . '"') . $this->n;
			$cont.=tagMe('div', '<label class="' . $colLabel . ' control-label"></label>' . $buttons, 'class="form-group"') . $this->n;
		}elseif($style == 'inline'){
			$cont.= $buttons;
		} else
			$cont.=tagMe('div', $buttons, 'class="form-group"') . $this->n;


		$sai = tagMe('form', $cont, $parFrm);


		if (!empty($this->jarr['confirm']))
		{
			$confirm_title = (!empty($this->jarr['titleConfirm']))? $this->jarr['titleConfirm'] : 'Confirmar operação';
			$confirm_text = (!empty($this->jarr['textConfirm']))? $this->jarr['textConfirm'] : 'Tem certeza que deseja executar este formulário?';
			$confirm_button_confirm = (!empty($this->jarr['btnConfirmYes']))? $confirm_dialog['btnConfirmYes'] : 'Sim';
			$confirm_button_cancel = (!empty($this->jarr['btnConfirmNo']))? $confirm_dialog['btnConfirmNo'] : 'Não';
			$js="	var submit_pedido_".$parFrm['id']." = false;
			function gFormConfirm".$parFrm['id']."()
			{
				submit_pedido_".$parFrm['id']." = true;
				$('#".$parFrm['id']."').submit();
			}
			$(document).ready(function(){
				$('#".$parFrm['id']."').submit(function(){
					$('#form-confirm-".$parFrm['id']."').modal();
					return (submit_pedido_".$parFrm['id'].")? true : false;
				});
			});";
			$sai .= g_Output::modal("{title: ".gT($confirm_title)."; content: ". gT($confirm_text)."; okCaption: Sim; name: form-confirm-".$parFrm['id']."; url: gFormConfirm".$parFrm['id']."()}");
			$this->addJavascript($js);
		}


		$footer = "";
		if ($this->jarr['footer'] <> '')
			$footer = tagMe('small', $this->jarr['footer']);
		if ($this->jarr['title'] <> '') {
			$subTitle = '';
			$title = tagMe('h1', gT($this->jarr['title']), 'class="panel-title"');
			if ($this->jarr['subTitle'] <> '')
				$subTitle = tagMe('small', gT($this->jarr['subTitle']));
			$header = tagMe('div', $title . $subTitle, 'class="panel-heading"');
			$body = tagMe('div', $sai, 'class="panel-body"');
			if ($footer <> "")
				$footer = tagMe('div', gT($footer), 'class="panel-footer"');
			$sai = tagMe('div', $header . $body . $footer, 'class="panel panel-default"');
		} else
			$sai.=$footer;

		if ($temCombo) {
			$this->addJavascript("$(document).ready(function() " . '{' . "$('.multiselect').multiselect(); " . '}' . ");");
		}


		if($hasFileMultiple){
			$this->addJavascript('
				$(document).ready(function() {
					mult_max = [];
					$(".add-other-file").on("click",function(e){
						e.preventDefault();
						input = $(this).attr("data-input");
						max = $(this).attr("data-max");
						data = $(this).attr("data");

						if(typeof mult_max[input] == "undefined"){
							mult_max[input] = 0;
						}

						if(max > 0 && mult_max[input] >= max){
							alert("'.gT('Limite máximo excedido').'");
							return false;
						}


						$(this).parent().before(data);
						mult_max[input] += 1;
					});
					$(".form-group").delegate(".fileinput-remove","click",function(e){
						e.preventDefault();
						input = $(this).attr("data-input");
						$(this).parent().remove();
						mult_max[input] -= 1;
					});
				});
			');
		}

		$saiArray = '';
		if (is_object($output))
		{
			$output->out($this->bufferPre,gLOC_PRE);
			$saiArray=$sai;
			$output->out($this->bufferPos,gLOC_POS);
			foreach ($this->bufferJavascript as $js)
				$output->addJavascript($js);
		} else
		{
			$saiArray[0] = $this->bufferPre;
			$saiArray[1] = $sai;
			$saiArray[2] = $this->bufferPos;
			$saiArray[3] = $this->bufferJavascript;

		}
		return($saiArray);
	}
}


