<?php
include_once $gPathDefault."gInput.php";

class gMultiPage extends gInput
{
	public $actualPage,$pages,$jsons;


	function add($json,$page)
	{
		if (is_object($page))
		{
			$mtz=cssDecode($json);
			$jsn=cssDecode($page->json);
			if ($mtz['title']<>'')
				$jsn['title']=$mtz['title'];
			$page->json=cssEncode($jsn);
		}
		$this->pages[]=$page;
		$this->jsons[]=$json;

	}


	function set($json,$page)
	{
		$mtz=cssDecode($json);
		foreach ($jsons as $j)
		{
			$jsn=cssDecode($j);
			if ($jsn['name']==$jsn['name'])
			{
				$this->pages[]=$page;
			}
		}

	}
	function render($fields)
	{
		global $gOs, $gDevice, $gBASE, $gApp;
		$dock="bottom" ;
		if ($gDevice=="tablet")
			$dock="top";
		$pag=intval($fields['gPage']);
		$jsn=cssDecode($this->jsons[$pag]);
		$nomeBotaoAvancar=gT('Avançar');
		if ($jsn['buttonNextCaption']<>'')
			$nomeBotaoAvancar=$jsn['buttonNextCaption'];
		$nomeBotaoVoltar=gT('Voltar');
		if ($jsn['buttonBackCaption']<>'')
			$nomeBotaoVoltar=$jsn['buttonBackCaption'];
		$voltar="-1";
		if ($jsn['buttonBack']<>'')
			$voltar=$jsn['buttonBack'];
		$html='';
		$instr=$jsn['instructions'];
		if (!is_object($this->pages[$pag]))
		{
			$html='';
			$form='';
			//if (($nomeBotaoVoltar<>'no') || ($nomeBotaoAvancar<>'no'))
			{
				//
				//<div id="ext-comp-1004" class=" x-panel x-form-fieldset"><div class="x-panel-body" id="ext-gen1016"><div id="ext-comp-1005" class=" x-panel" style="width: 1126px; height: 25px; "><div class="x-panel-body" id="ext-gen1017" style="left: 0px; top: 0px; "><span class="g-msg-subtitle">Nova demanda</span></div></div><div id="id_pessoas_solicitou" class=" x-field x-field-select x-landscape x-label-align-left"><div class="x-form-label" id="ext-gen1022"><span>Solicitante</span></div><div class="x-form-field-container"><input id="ext-gen1020" type="text" name="comboid_pessoas_solicitou" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1021"></div></div></div><div id="id_setores" class=" x-field x-field-select x-landscape x-field-required x-label-align-left"><div class="x-form-label" id="ext-gen1027"><span>Setor</span></div><div class="x-form-field-container"><input id="ext-gen1025" type="text" name="comboid_setores" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1026"></div></div></div><div id="id_tipos_demandas" class=" x-field x-field-select x-landscape x-field-required x-label-align-left"><div class="x-form-label" id="ext-gen1032"><span>Tipo de demanda</span></div><div class="x-form-field-container"><input id="ext-gen1030" type="text" name="comboid_tipos_demandas" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1031"></div></div></div><div id="solicitacao" class="x-field x-field-textarea x-label-align-left"><div class="x-form-label" id="ext-gen1036"><span>Solicitação</span></div><div class="x-form-field-container"><textarea id="ext-gen1035" type="" name="solicitacao" class="x-input-text" autocapitalize="off"></textarea></div></div><div id="gPage" class=" x-field x-field-hidden x-label-align-left"><div class="x-form-label" id="ext-gen1039"><span>GPage</span></div><div class="x-form-field-container"><input id="ext-gen1038" type="hidden" name="gPage" class="x-input-hidden" tabindex="-1" value="1"></div></div><div id="g" class=" x-field x-field-hidden x-label-align-left"><div class="x-form-label" id="ext-gen1042"><span>G</span></div><div class="x-form-field-container"><input id="ext-gen1041" type="hidden" name="g" class="x-input-hidden" tabindex="-1" value="3.000000001"></div></div><div id="ext-comp-1006" class=" x-button x-button-confirm" style="width: 140px; margin-top: 0.5em; margin-right: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; "><span class="x-button-label" id="ext-gen1045">Avançar</span></div><div id="ext-comp-1007" class=" x-button x-button-decline" style="width: 140px; margin-top: 0.5em; margin-right: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; "><span class="x-button-label" id="ext-gen1047">Voltar</span></div><input name="id_tipos_demandas" type="hidden" id="ext-gen1033" value="1"><input name="id_setores" type="hidden" id="ext-gen1028" value="1"><input name="id_pessoas_solicitou" type="hidden" id="ext-gen1023" value="0"></div><div class="x-form-fieldset-instructions" id="ext-gen1048">Informe os dados solicitados acima</div></div><div id="ext-comp-1004" class=" x-panel x-form-fieldset"><div class="x-panel-body" id="ext-gen1016"><div id="ext-comp-1005" class=" x-panel" style="width: 1126px; height: 25px; "><div class="x-panel-body" id="ext-gen1017" style="left: 0px; top: 0px; "><span class="g-msg-subtitle">Nova demanda</span></div></div><div id="id_pessoas_solicitou" class=" x-field x-field-select x-landscape x-label-align-left"><div class="x-form-label" id="ext-gen1022"><span>Solicitante</span></div><div class="x-form-field-container"><input id="ext-gen1020" type="text" name="comboid_pessoas_solicitou" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1021"></div></div></div><div id="id_setores" class=" x-field x-field-select x-landscape x-field-required x-label-align-left"><div class="x-form-label" id="ext-gen1027"><span>Setor</span></div><div class="x-form-field-container"><input id="ext-gen1025" type="text" name="comboid_setores" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1026"></div></div></div><div id="id_tipos_demandas" class=" x-field x-field-select x-landscape x-field-required x-label-align-left"><div class="x-form-label" id="ext-gen1032"><span>Tipo de demanda</span></div><div class="x-form-field-container"><input id="ext-gen1030" type="text" name="comboid_tipos_demandas" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1031"></div></div></div><div id="solicitacao" class="x-field x-field-textarea x-label-align-left"><div class="x-form-label" id="ext-gen1036"><span>Solicitação</span></div><div class="x-form-field-container"><textarea id="ext-gen1035" type="" name="solicitacao" class="x-input-text" autocapitalize="off"></textarea></div></div><div id="gPage" class=" x-field x-field-hidden x-label-align-left"><div class="x-form-label" id="ext-gen1039"><span>GPage</span></div><div class="x-form-field-container"><input id="ext-gen1038" type="hidden" name="gPage" class="x-input-hidden" tabindex="-1" value="1"></div></div><div id="g" class=" x-field x-field-hidden x-label-align-left"><div class="x-form-label" id="ext-gen1042"><span>G</span></div><div class="x-form-field-container"><input id="ext-gen1041" type="hidden" name="g" class="x-input-hidden" tabindex="-1" value="3.000000001"></div></div><div id="ext-comp-1006" class=" x-button x-button-confirm" style="width: 140px; margin-top: 0.5em; margin-right: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; "><span class="x-button-label" id="ext-gen1045">Avançar</span></div><div id="ext-comp-1007" class=" x-button x-button-decline" style="width: 140px; margin-top: 0.5em; margin-right: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; "><span class="x-button-label" id="ext-gen1047">Voltar</span></div><input name="id_tipos_demandas" type="hidden" id="ext-gen1033" value="1"><input name="id_setores" type="hidden" id="ext-gen1028" value="1"><input name="id_pessoas_solicitou" type="hidden" id="ext-gen1023" value="0"></div><div class="x-form-fieldset-instructions" id="ext-gen1048">Informe os dados solicitados acima</div></div><div id="ext-comp-1004" class=" x-panel x-form-fieldset"><div class="x-panel-body" id="ext-gen1016"><div id="ext-comp-1005" class=" x-panel" style="width: 1126px; height: 25px; "><div class="x-panel-body" id="ext-gen1017" style="left: 0px; top: 0px; "><span class="g-msg-subtitle">Nova demanda</span></div></div><div id="id_pessoas_solicitou" class=" x-field x-field-select x-landscape x-label-align-left"><div class="x-form-label" id="ext-gen1022"><span>Solicitante</span></div><div class="x-form-field-container"><input id="ext-gen1020" type="text" name="comboid_pessoas_solicitou" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1021"></div></div></div><div id="id_setores" class=" x-field x-field-select x-landscape x-field-required x-label-align-left"><div class="x-form-label" id="ext-gen1027"><span>Setor</span></div><div class="x-form-field-container"><input id="ext-gen1025" type="text" name="comboid_setores" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1026"></div></div></div><div id="id_tipos_demandas" class=" x-field x-field-select x-landscape x-field-required x-label-align-left"><div class="x-form-label" id="ext-gen1032"><span>Tipo de demanda</span></div><div class="x-form-field-container"><input id="ext-gen1030" type="text" name="comboid_tipos_demandas" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1031"></div></div></div><div id="solicitacao" class="x-field x-field-textarea x-label-align-left"><div class="x-form-label" id="ext-gen1036"><span>Solicitação</span></div><div class="x-form-field-container"><textarea id="ext-gen1035" type="" name="solicitacao" class="x-input-text" autocapitalize="off"></textarea></div></div><div id="gPage" class=" x-field x-field-hidden x-label-align-left"><div class="x-form-label" id="ext-gen1039"><span>GPage</span></div><div class="x-form-field-container"><input id="ext-gen1038" type="hidden" name="gPage" class="x-input-hidden" tabindex="-1" value="1"></div></div><div id="g" class=" x-field x-field-hidden x-label-align-left"><div class="x-form-label" id="ext-gen1042"><span>G</span></div><div class="x-form-field-container"><input id="ext-gen1041" type="hidden" name="g" class="x-input-hidden" tabindex="-1" value="3.000000001"></div></div><div id="ext-comp-1006" class=" x-button x-button-confirm" style="width: 140px; margin-top: 0.5em; margin-right: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; "><span class="x-button-label" id="ext-gen1045">Avançar</span></div><div id="ext-comp-1007" class=" x-button x-button-decline" style="width: 140px; margin-top: 0.5em; margin-right: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; "><span class="x-button-label" id="ext-gen1047">Voltar</span></div><input name="id_tipos_demandas" type="hidden" id="ext-gen1033" value="1"><input name="id_setores" type="hidden" id="ext-gen1028" value="1"><input name="id_pessoas_solicitou" type="hidden" id="ext-gen1023" value="0"></div><div class="x-form-fieldset-instructions" id="ext-gen1048">Informe os dados solicitados acima</div></div><div id="ext-comp-1004" class=" x-panel x-form-fieldset"><div class="x-panel-body" id="ext-gen1016"><div id="ext-comp-1005" class=" x-panel" style="width: 1126px; height: 25px; "><div class="x-panel-body" id="ext-gen1017" style="left: 0px; top: 0px; "><span class="g-msg-subtitle">Nova demanda</span></div></div><div id="id_pessoas_solicitou" class=" x-field x-field-select x-landscape x-label-align-left"><div class="x-form-label" id="ext-gen1022"><span>Solicitante</span></div><div class="x-form-field-container"><input id="ext-gen1020" type="text" name="comboid_pessoas_solicitou" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1021"></div></div></div><div id="id_setores" class=" x-field x-field-select x-landscape x-field-required x-label-align-left"><div class="x-form-label" id="ext-gen1027"><span>Setor</span></div><div class="x-form-field-container"><input id="ext-gen1025" type="text" name="comboid_setores" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1026"></div></div></div><div id="id_tipos_demandas" class=" x-field x-field-select x-landscape x-field-required x-label-align-left"><div class="x-form-label" id="ext-gen1032"><span>Tipo de demanda</span></div><div class="x-form-field-container"><input id="ext-gen1030" type="text" name="comboid_tipos_demandas" class="x-input-text" tabindex="-1"><div class="x-field-mask" id="ext-gen1031"></div></div></div><div id="solicitacao" class="x-field x-field-textarea x-label-align-left"><div class="x-form-label" id="ext-gen1036"><span>Solicitação</span></div><div class="x-form-field-container"><textarea id="ext-gen1035" type="" name="solicitacao" class="x-input-text" autocapitalize="off"></textarea></div></div><div id="gPage" class=" x-field x-field-hidden x-label-align-left"><div class="x-form-label" id="ext-gen1039"><span>GPage</span></div><div class="x-form-field-container"><input id="ext-gen1038" type="hidden" name="gPage" class="x-input-hidden" tabindex="-1" value="1"></div></div><div id="g" class=" x-field x-field-hidden x-label-align-left"><div class="x-form-label" id="ext-gen1042"><span>G</span></div><div class="x-form-field-container"><input id="ext-gen1041" type="hidden" name="g" class="x-input-hidden" tabindex="-1" value="3.000000001"></div></div><div id="ext-comp-1006" class=" x-button x-button-confirm" style="width: 140px; margin-top: 0.5em; margin-right: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; "><span class="x-button-label" id="ext-gen1045">Avançar</span></div><div id="ext-comp-1007" class=" x-button x-button-decline" style="width: 140px; margin-top: 0.5em; margin-right: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; "><span class="x-button-label" id="ext-gen1047">Voltar</span></div><input name="id_tipos_demandas" type="hidden" id="ext-gen1033" value="1"><input name="id_setores" type="hidden" id="ext-gen1028" value="1"><input name="id_pessoas_solicitou" type="hidden" id="ext-gen1023" value="0"></div><div class="x-form-fieldset-instructions" id="ext-gen1048">Informe os dados solicitados acima</div></div>
				// decline
				if (($gDevice=="mobile") || ($gOs=="ios"))
				{
					$url=$this->page;
					$name="gForm";
					$save='';
					$flds='';
					$s=$this->msgTitle($jsn['title']);
					if ($jsn['subTitle']<>'')
						$flds[]="{html: '".str_replace("'","\'",$this->msgSubTitle ($jsn['subTitle']))."'}";
					if ($jsn['error']<>'')
						$flds[]="{html: '".str_replace("'","\'",$this->msgError ($jsn['error']))."'}";
					$html=$this->pages[$pag];
					$html=str_replace(chr(13),'',str_replace("\n",'\\n',str_replace("'","\'",$html)))."<br><br>&nbsp;";
					//$html=str_replace("\t"," ",str_replace("\a","",str_replace("'","\"",str_replace("\n","",str_replace("'","\\'",$html)))));


					//$flds[]="{layout: 'fit', scroll: {direction: 'horizontal', eventTarget: 'parent'}, html: '".$html."'}";
					$flds[]="{html: '".$html."'}";
					$html='';
					$flds[]="{xtype: 'hiddenfield', id: 'gPage', name: 'gPage', value: '".($pag+1)."'}";
					foreach($fields as $key=>$value)
					{
						if (($key<>'gPage') && ($key<>'PHPSESSID'))
							$flds[]="{xtype: 'hiddenfield', id: '$key', name: '$key', value: '$value'}";
					}
					if ($pag>0)
					{
						if ($nomeBotaoVoltar<>'no')
						{
							//$btns[]="{xtype: 'button', ui: 'decline', style: 'width: 140px; margin: .5em;', text: '$nomeBotaoVoltar', handler: function(){history.go(-1)}}";
							$btns[]="{iconCls: 'arrow_left', text: '$nomeBotaoVoltar', handler: function(){history.go(-1)}}";
							//$btns[]="{iconCls: 'arrow_left', text: '$nomeBotaoVoltar', handler: function(){document.backForm.submit();}}";
						}

					}
					if ($pag<count($this->pages)-1)
					{
						if ($nomeBotaoAvancar<>'no')
						{
							$btns[]="{iconCls: 'arrow_right', text: '$nomeBotaoAvancar', handler: function() {doSave()}}";
							$save="
							var doSave = function()
							{
								var f = $name.getEl();

								f.dom.action = '$url';
								f.dom.method = 'POST';
								$name.submit();

							}			";
						}
					}

		// ======================= barras (menu)

				$this->senchaButtonsBottom=implode(',',$btns);
				$this->senchaItems=$flds;
				if ($save<>"")
					extjsDo($save);
				} else
				{
					if ($jsn['title']<>'')
						$html.=$this->gMsgTitle($jsn['title']);
					if ($jsn['subTitle']<>'')
						$html.=$this->gMsgSubTitle($jsn['subTitle']);
					if ($jsn['error']<>'')
						$html.=$this->gMsgError($jsn['error']);
					$html.=$this->pages[$pag];
					$fvoltar="<form name='backForm' style='display: inline' action=".$this->page." method='post'>";
					$fvoltar.="<input type='hidden' name='gPage' value='".($pag-1)."'>";
					$favancar="<form name='nextForm' style='display: inline' action=".$this->page." method='post'>";
					$favancar.="<input type='hidden' name='gPage' value='".($pag+1)."'>";
					$class="";
					foreach($fields as $key=>$value)
					{
						if (($key<>'gPage') && ($key<>'PHPSESSID'))
						{
							$fvoltar.="<input type='hidden' name='$key' value='$value'>";
							$favancar.="<input type='hidden' name='$key' value='$value'>";
						}
					}
					foreach ($_REQUEST as $key=>$value)
					{
						if (($key<>'gPage') && ($key<>'PHPSESSID') && (empty($fields[$key])))
						{
							$fvoltar.="<input type='hidden' name='$key' value='$value'>";
						}
					}
					$mostrouAvancar=false;
					$mostrouVoltar=false;
					if ($pag>0)
					{
						if ($nomeBotaoVoltar<>'no')
						{
							$mostrouVoltar=true;
							
							//if ($voltar==-1)
							//	$fvoltar.="<input type='button' value='$nomeBotaoVoltar' onClick='m=new Ext.LoadMask(Ext.getBody(),{msg:\"".gT("Carregando...")."\"});m.show();document.backForm.submit();'>&nbsp;&nbsp;&nbsp;";
							//else
							if ($fields['gPageBack']<>'')
								$fvoltar.="<input type='button' value='$nomeBotaoVoltar' onClick='m=new Ext.LoadMask(Ext.getBody(),{msg:\"".gT("Carregando...")."\"});m.show();window.location.href=\"".$this->page."&gPage=".($pag-1)."&".base64_decode($fields['gPageBack'])."\";'>&nbsp;&nbsp;&nbsp;";
							else
								$fvoltar.="<input type='button' value='$nomeBotaoVoltar' onClick='m=new Ext.LoadMask(Ext.getBody(),{msg:\"".gT("Carregando...")."\"});m.show();history.go($voltar)'>&nbsp;&nbsp;&nbsp;";
						}

					}
					if ($pag<count($this->pages)-1)
					{
						if ($nomeBotaoAvancar<>'no')
						{
							$mostrouAvancar=true;
							$favancar.="<input type='submit' onClick='m=new Ext.LoadMask(Ext.getBody(),{msg:\"".gT("Carregando...")."\"});m.show();' value='$nomeBotaoAvancar'>";
						}
					}
					$fvoltar.="</form>";
					$favancar.="</form>";
					if (($mostrouAvancar) || ($mostrouVoltar))
						$html.="<br><div style='width: 100%; border-top: 1px dotted #e0e0e0'></div><br>";
					$html.=$fvoltar.$favancar;
				}
			}
		} else
		{
			$frm=$this->pages[$pag];
			$mtz=cssDecode($frm->json);
			$instr=$mtz['instructions'];
			$this->pageTitle=$mtz['title'];
			$frm->add("{name: 'gPage'; type: hidden; value: '".($pag+1)."'}");
			foreach($fields as $key=>$value)
			{
				if (($key<>'gPage') && ($key<>'PHPSESSID'))
					$frm->add("{name: '$key'; type: hidden; value: '$value'}");
			}
			$frm->setButtonNextCaption($nomeBotaoAvancar);
			$frm->setButtonBackCaption($nomeBotaoVoltar);
			$frm->render();
			$this->senchaButtonsBottom=$frm->buttons;
		}
		$this->senchaInstructions=$instr;
		$this->gBody($html);
	}

	function onBeforeShowPage($fields)
	{
		$this->actualPage=$fields['gPage'];
		return($fields);
	}

	function showMultiPage()
	{
		global $gPage;
		$this->showToolBar=false;
		foreach($_GET as $key=>$value)
		{
			$fields[$key]=trim($value);
		}
		foreach($_POST as $key=>$value)
		{
			$fields[$key]=trim($value);
		}
		$fields=$this->onBeforeShowPage($fields,$gPage);
		$this->render($fields);
	}
}

?>
