<?php
class gAjax{

	private $combo = array();

	function requestData($func, $e,$param){
		$args = array('e' => $e) + $param;
		if(is_array($param) && count($param))
			$vars[0]=$args;
		else
			$vars=$args;
		//gLog("Paramsss =>>>>".json_encode($vars));
		return json_encode(call_user_func_array($func, $vars));
	}

	/**
	 * Select dinamico com ajax
	 *
	 * @param string $source Combo com valores absolutos
	 * @param string $target Combo com valores relativos
	 *
	 */
	function comboDynamicValues($source,$target,$function,$otherParameters){
        $otherParameters = $otherParameters <> "" ? explode(",",$otherParameters) : "" ;
		$this->combo[] = array(
								'uid' => uniqid(),
								'source_id' => $source,
								'target_id' => $target,
								'func_data' => $function,
                                'other_parameters' => $otherParameters
							);
	}

	/**
	 * Renderizando codigo para consulta ajax
	 */
	function render(&$o = '',$print = true){
		if(!empty($_REQUEST['gAjax']) && !empty($_REQUEST['target']) && function_exists($_REQUEST['target']))
		{
			$q = (!empty($_REQUEST['q']))? $_REQUEST['q'] : '';
			//$param = (!empty($_REQUEST['param']))? explode(',',base64_decode($_REQUEST['param'])) : array();
			$param = (!empty($_REQUEST['param'])) ? cssDecode($_REQUEST['param']) : array();
			echo $this->requestData($_REQUEST['target'], $q,$param);
			exit;
		}
		else
		{
			$script = '';

			if(count($this->combo)){
				foreach($this->combo as $combo){
					$script .= '
						$(document).ready(function(){
							$("#'.$combo['source_id'].'").change(function(){
								$("#'.$combo['target_id'].'")[0].selectize.clear();
								$("#'.$combo['target_id'].'")[0].selectize.clearOptions();
								$("#'.$combo['target_id'].'")[0].selectize.settings.placeholder = "Carregando...";
								$("#'.$combo['target_id'].'")[0].selectize.updatePlaceholder();
								var json = "";
								';
								if( is_array($combo['other_parameters']) && count($combo['other_parameters']) > 0 ){
									$script.="json ='{'; ";
									foreach ($combo['other_parameters'] as $params) {
										$script.="
													if( $('#".$params."').val() != '' &&  $('#".$params."').val()!= null)
														json= json +'".$params.":' + $('#".$params."').val()+';';
										";
									}
									$script.="json=json.substr(0,json.length -1);";
									$script.="json = json+ '}'; ";
								}
					$script .= '
								$.ajax({
									url: "' . $o->page . '&gAjax=1&target=' . $combo['func_data'] . '&q=" + this.value+"&param="+json,
									dataType: "json",
									type: "GET",
									success: function(data){
										if(data){
											$.each(data, function (i,v) {
												$("#'.$combo['target_id'].'")[0].selectize.addOption({value:i,text:v});
											});
										}
										$("#'.$combo['target_id'].'")[0].selectize.settings.placeholder = $("#'.$combo['target_id'].'").attr("placeholder");
										$("#'.$combo['target_id'].'")[0].selectize.updatePlaceholder();
									},
									error: function(){
										$("#'.$combo['target_id'].'")[0].selectize.settings.placeholder = $("#'.$combo['target_id'].'").attr("placeholder");
										$("#'.$combo['target_id'].'")[0].selectize.updatePlaceholder();
									}
								});
							});
						});';
				}
			}
			// script comprimido
			$script .= '(function($){$.fn.gAjax=function(){return this.each(function(){elem=$(this);action=actionEvent(elem);if(action){action.forEach(function(a){return $(elem).on(a.event,function(e){d=actionEvent($(this),e.type);q=getElementVal($(this),d.key);$.ajax({url:"' . $o->page . '&gAjax=1&target="+d.data+"&q="+q+"&param="+btoa(d.param),dataType:"json",type:"GET",success:function(data){if(data){var fn=window[d.act];fn(data)}},error:function(){}});return false})})}});function actionEvent(elem,type){e=["gajax-blur","gajax-change","gajax-click","gajax-focus","gajax-keydown","gajax-keypress","gajax-keyup"];if(type!=undefined){r="{}";e="gajax-"+type;if(elem.attr(e)!=undefined){attr=elem.attr(e);attr=attr.split(",");r={selector:elem.prop("tagName"),event:e.replace("gajax-","")};r.param=[];attr.forEach(function(v,k){switch(k){case 0:r.data=v;break;case 1:r.act=v;break;case 2:r.key=v;break;default:if(v.indexOf("js:")==0){v=eval(v.substr(3))}r.param.push(v);break}});console.log(r)}}else{r=[];e.forEach(function(e){if(elem.attr(e)!=undefined){attr=elem.attr(e);attr=attr.split(",");r.push({selector:elem.prop("tagName"),data:attr[0],act:attr[1],key:attr[2],event:e.replace("gajax-","")})}})}return r}function getElementVal(elem,attr){selector=elem.prop("tagName");if(attr==undefined){switch(selector){case"SELECT":case"INPUT":case"BUTTON":val=elem.val();break;case"A":val=elem.attr("href");break;default:val=elem.attr("id");break}}else{val=elem.attr(attr)}return val}return this}})(jQuery);$(document).ready(function(){$("[gajax-blur],[gajax-change],[gajax-click],[gajax-focus],[gajax-keydown],[gajax-keypress],[gajax-keyup]").gAjax()});';
			return ($o && $print)? $o->addJavascript($script) : $script;
		}
	}
}
