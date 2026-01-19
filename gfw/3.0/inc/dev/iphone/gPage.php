<?
/** Este arquivo contém a estrutura padrão para apresentação ao usuário
 *  de algum conteúdo para web (estação de trabalho)
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */


 class gPage extends g_Page
{

	function gBegin($par)
	{
		extjsDo($this->js);
		parent::gBegin($par);
	}

/** Evento a ser executado ao selecionar um registro da tabela
	 * @author	giuliano
	 * @version	1.0 11-12-2010 15:05
	 * @param string $id Id do elemento selecionado
	 */
	function onSelect($id,$msg="",$moreJs="")
	{
		$title=str_replace("'",'',$this->showPageParams['title']);

		if ($msg=="")
			$msg=gT("Registro selecionado!");
		//$msg=gT($msg);
		$js="
	Ext.Msg.alert('$title','$msg');
        setTimeout(function(){
            Ext.MessageBox.hide();
				$moreJs
        }, 4000);
		";
		extjsEndDo($js);
		return (true);
	}

/** Evento a ser executado depois de adicionar registro no banco
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $fields Array com campos que foram adicionados
	 */
	function onAfterAdd($fields,$msg="",$moreJs="")
	{
		$mtz=cssDecode($this->json);
		parent::parentConstruct($this->json);
		$command=$_REQUEST['process'];
		if (!$this->masterdetail)
		{
			if ($msg<>"")
			{
				if ($moreJs=="")
					$moreJs="document.location.href='".$this->page."';";
				$js="
			Ext.Msg.alert('$title','$msg');
				  setTimeout(function(){
						Ext.MessageBox.hide();
						$moreJs
				  }, 4000);
				";
			}
			else
			{
				$msg=gT("Registro salvo com sucesso. Criar outro?");
				$js="
					Ext.Msg.show({
						title:'".$mtz['title']."',
						msg: '$msg',
						buttons: Ext.Msg.YESNOCANCEL,
						fn: function(btn){
								if ((btn == 'ok') || (btn == 'yes')){
									history.back(-1);
								}
								if ((btn == 'no') || (btn == 'no')){
									document.location.href='".$this->page."';
								}
							}
					});
				";
			}
		} else
		{
			// Formulário filho?
			if ($_REQUEST['i']<>"")
				$js="document.location.href='".$this->page."&gId=".$_REQUEST['i']."&process=formEdit';";
			else
				$js="document.location.href='".$this->page."&gId=".$fields['id']."&process=formEdit';";
		}
		extjsDo($js);
		$this->gBegin();
		$this->gEnd();
		//return "{success: true}";
	}


	/** Evento a ser executado depois de alterar registro no banco
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $fields Array com campos que foram alterados
	 */
	function onAfterUpdate($fields,$msg=true)
	{
		if ($msg)
		{
			//parent::__construct($this->json);
			parent::parentConstruct($this->json);
			$mtz=cssDecode($this->json);
			if (!$this->masterdetail)
			{
				/*
				$js="
					Ext.Msg.alert('".$mtz['title']."','".gT("Registro salvo com sucesso.Retornar?")."', function(btn){
						if ((btn == 'ok') || (btn == 'sim')){
							document.location.href='".$this->page."';
						}
					});
				";
				 *
				 */
				$js="
					document.location.href='".$this->page."';
				";
			} else
			{
				// Formulário filho?
				if ($_REQUEST['i']<>"")
					$js="document.location.href='".$this->page."&gId=".$_REQUEST['i']."&process=formEdit';";
				else
					$js="document.location.href='".$this->page."&gId=".$fields['id']."&process=formEdit';";
			}
			extjsDo($js);
			$this->gBegin();
			$this->gEnd();
		}
		return "{success: true}";
	}

	function showAlert($titulo)
	{
		if ($this->message<>'')
		{
			parent::__construct($this->json);
			$mtz=cssDecode($this->json);
			$js="
				Ext.Msg.alert('".gT($titulo)."','".$this->message."', function(btn){
					if ((btn == 'ok') || (btn == 'sim')){
						document.location.href='".$this->page."';
					}
				});
			";
			extjsDo($js);
			$this->gBody();
			exit;
		}
	}








	function grid($json)
	{
		global $gBASE, $gApp;

		$jsn=cssDecode($json);
		$name="gridHtml";
		$url="";

		$flds=$btns="";


		$maxrows=500;
		/*
		$maxrows=gVar("database.maxrows");
		if ($this->actualQuery>0)
				 $maxrows=8;
		if (is_array($this->filters))
				$maxrows=10;
		$height=30*$maxrows;
		 *
		 */
		$this->maxrows=$maxrows;

		$mtz=jsonDecode($json,";",true);
		$name=stripQuote($mtz['name']);
		$title=stripQuote($mtz['title']);
		if (($title=="") && ($this->actualQuery==0))
			$title="NoNameGrid";
		if ($name=="")
		{
			$name=gFieldReverse($title); // chapa o texto
		}

		$mtz=jsonRemove($mtz,'name');


		$fprefix="";
		if (($this->enabledPermissions['gridupdate']) || ($this->enabledPermissions['windowupdate']))
			$fprefix="gfrm".$this->actualQuery."_";
		$fk="id_$tab";


		if (count($this->gridQuerys)>$this->actualQuery)
			$flds=$this->gridQuerys[$this->actualQuery];
		else
			$flds=$this->querys[$this->actualQuery];
		$html='';
		$frm=new gForm();
		$frm=$this->tabForm($flds,$frm);
		$fields=$frm->getFields();
		foreach ($fields as $f)
		{
			$s=str_replace("{","",$f);
			$s=str_replace("}","",$s);
			$sm=explode(",",$s);
			foreach ($sm as $tmp)
			{
				//echo "$f<br>";
				$cmp=explode(":",trim($tmp));
				//$n="'$fprefix".str_replace("'","",trim($cmp[1]))."'";
				$n=trim($cmp[1]);
				if (trim($cmp[0]=="label"))
					$ffieldLabel=str_replace("'",'',trim($cmp[1]));
				if (trim($cmp[0]=="name"))
					$fname=str_replace("'",'',trim($cmp[1]));
				if (trim($cmp[0]=="hiddenName"))
					$fname=str_replace("'",'',trim($cmp[1]));
				if (trim($cmp[0]=="xtype"))
					$ftype=str_replace("'",'',trim($cmp[1]));
			}
			if ($fname<>"'".$fprefix."idd'")
			{
				if (strtolower($fname)=="'".$fprefix."id'")
				{
					$el[]=$ffieldLabel;

				} else
				{
					$el[]=$ffieldLabel;
				}
				$fname=str_replace($fprefix,'',$fname);
				$typ[$fname]=$ftype;
			}
		}


		$record=implode(",",$rcampos);
		$campos=implode(",",$campos);
		$outrosParm="";

		foreach ($_REQUEST as $nome=>$valor)
		{
			if (($nome<>"PHPSESSID") && ($nome<>"__utma") && ($nome<>"__utmz")
					&& ($nome<>"gId") && ($nome<>"process") && ($nome<>"i") && ($nome<>"q") && ($nome<>"x") && ($nome<>"g"))
				$outrosParm[]=$nome."=".$valor;
			//gLog("======================> $nome = $valor");
		}

		$outrosParm=implode("&",$outrosParm);
		$this->retornoNaTela=false;

		//url:'".$this->page."&process=gridData&w=1&x=$fprefix&i=".$_REQUEST['gId']."&q=".$this->actualQuery."&gwId='+sai.id,
		$_REQUEST['process']="gridData";
		$_REQUEST['w']='0';
		$_REQUEST['x']=$fprefix;
		$_REQUEST['i']=$_REQUEST['gId'];
		$_REQUEST['q']=$this->actualQuery;
		$_REQUEST['gwId']=0;
		$_REQUEST['start']=intval($_REQUEST['start']);
		$_REQUEST['limit']=$maxrows;
		$ret=$this->process();

		$html.=$this->tableBegin("normal",true);
		$html.=$this->tableRow($el,"header");
		$sim=gT("sim");
		$nao=gT("não");
		foreach ($ret as $linha)
		{
			$mtz='';
			$id='';
			foreach ($linha as $key=>$col)
			{
				if (($key<>"idd") && ($key<>$fprefix."idd"))
				{
					//$html.="<h1>$key = ".$typ[$key]."</h1>";
					switch ($typ[$key])
					{
						case "date":
							$col=gDate($col);
							break;
						case "datetime":
							$col=gDateTime($col);
							break;
						case "check":
							if ($col==1)
								$col=$sim;
							else
								$col=$nao;
							break;
						case "datetime":
							break;
						case "textfield":
							$col="<-".$col;
							break;
					}
					if (($key=="id") || ($key==$fprefix."id"))
					{
						/*
						$c='';
						$c[]=$this->image("{url: 32/b2016}"); // selecionar
						$c[]=$this->image("{url: 32/b2002}"); // selecionar
						$c[]=$this->image("{url: 32/b2003}"); // selecionar
						$col.="<br>".implode("",$c);
						*/
						$id=$col;
					}
					$mtz[]=$col;
				}
			}
			$event='';
			if ($id>0)
				$event="onClick='gridRun($id)'";
			$html.=$this->tableRow($mtz,"light",'',$event);
		}

		$html.=$this->tableEnd();

		//echo "<pre>";print_r($ret);exit;
		//$html=str_replace("\n",'\\n',str_replace("'","\'",$html));
		//echo "<pre>";print_r($html);exit;

		//$html=$ret;









		$linkUrl=str_replace("'","",$this->showPageParams['linkUrl']);
		if ($linkUrl=="")
			$linkUrl=$this->page;
		if ($this->showPageParams['linkLabel']<>"")
			$linkLabel=str_replace("'","",$this->showPageParams['linkLabel']);
		else
			$linkLabel=gT("Selecionar");
		$sep="?";
		if (strpos($linkUrl,"?")>0)
			$sep="&";

		$prm="document.location.href=\"$linkUrl".$sep."process=link&i=\"+id+\"&gId=\"+id;";


		$botoes="";

		if (strpos($this->permissions,"D")!==false)
		{
			$botoes[]="
						{
							text: '".gT("Excluir")."',
							ui: 'decline',
							handler : function(){
								Ext.Msg.confirm(\"$title\",\"".gT(" Confirma exclusão do registro selecionado?")."<br>\"+id, function(btn, text)
								{
									if (btn == 'yes'){
										Ext.Ajax.request({
											url: '".$this->page."',
											params: 'process=gridDelete&q=".$this->actualQuery."&id='+id,
											success: function(result, operation) {
												jsonData = Ext.util.JSON.decode(result.responseText);
												if (result.responseText==\"{success: true}\")
													Ext.Msg.alert(\"$title\",\"".gT("Registro excluído!")."\");
												else
													Ext.Msg.alert(\"$title\",\"".gT("Falha ao excluir.Confira os dados informados, e verifique se tem permissão para esta ação...")."\");

											},
											failure: function(result, operation) {
												Ext.Msg.alert(\"$title\",\"".gT("Falha na conexão.Não foi possível excluir...")."\");
											}
										});
										window.location.reload();
									}
								});
							}
						}";
		}
		if (strpos($this->permissions,"I")!==false)
		{
			$botoes[]="
						{
							text : 'Novo',
							handler : function(){
								document.location.href=\"".$this->page."&process=formNew&i=\"+id+\"&q=".$this->actualQuery."\";
							}
						}";
		}
		if ((strpos($this->permissions,"U")!==false)||(strpos($this->permissions,"F")!==false)||(strpos($this->permissions,"W")!==false))
		{
			$botoes[]="
						{
							text : '".gT("Editar")."',
							handler : function(){
								 document.location.href=\"".$this->page."&gId=\"+id+\"&process=formEdit&i=\"+id+\"&q=".$this->actualQuery."\";
							}
						}";
		}

		if (strpos($this->permissions,"L")!==false)
		{
			$botoes[]="
						{
							text : '$linkLabel',
							handler : function(){
								$prm
							}
						}";
		}

		$botoes[]="
						{
							text : '".gT("Voltar")."',
							scope : this,
							handler : function(){
								 this.actions.hide();
							}
						}";
		$js="

		function gridRun(id)
		{
            if (!this.actions) {
                this.actions = new Ext.ActionSheet({
                    items: [".implode(",",$botoes)."]
                });
            }


            this.actions.show();
			//document.location.href=\"".$this->page."&gId=\"+id+\"&process=formEdit&i=\"+id+\"&q=".$this->actualQuery."\";
		}

";

		//$this->screen($json,$url,$html,"{html:'Toque no item para agir'}");

		addJavaScript($js);
		/*
		$buttons[]="{text: 'Anterior', ui: 'back'}";
		$buttons[]="{xtype: 'spacer'}";
		$buttons[]="{text: 'Próxima', ui: 'forward'}";
		$this->senchaButtonsTop=$buttons;
		 *
		 */

		$msg="<div class=\\'g-msg-subtitle\\' style=\\'width: 100%; text-align: center\\'>Toque no item para agir</div>";
		$this->buffer=$html;
		$this->senchaTitle=$jsn['title'];
		$this->senchaItems="{html: '$msg'}";
	}







	function showFormPage($json)
	{
		global $extjsBuffer;
		$file=basename($_SERVER["PHP_SELF"]);
		$info=pathinfo($file);
		$file=basename($file,'.'.$info['extension']);
		// verifica se existe .js e carrega
		$fileJS=$file.".js";
		if (file_exists($fileJS))
		{
			$js.="\n";
			// Caso exista(m) dicionário(s), usa para criar Stores
			if (is_array($this->dictionarys))
			{
				$temDicionario=false;
				foreach ($this->dictionarys as $dictObj)
				{
					$dict=$dictObj->get();
					foreach ($dict as $key=>$parm)
					{
						$js.=gStore("store_".$key,$parm['items'])."\n";
					}
				}
			}

			/*
			foreach ($this->stores as $store)
			{
				$json=jsonDecode($store);
				$js.=gStore($json['name'],$json['query'])."\n";
			}
			 *
			 */
			$js.="\n";
			$js.=file_get_contents($fileJS);
			//$out=new gInput();
			$extjsBuffer.=$js;
			$this->gBody('<div id="frm"></div>');
			exit;
		} elseif (file_exists($fileHTML))
		{
			// verifica se existe .html e carrega
			$fileHTML=$file.".html";
			$HTML.=file_get_contents($fileHTML);
			$out=new gInput();
			$out->gBegin();
			$out->gOut($HTML);
			$out->gEnd();
		} else
		{


			if ((!$this->masterdetail) || (intval($_REQUEST['q'])>0))
			{
				// Formulario simples...
				$mtz=jsonDecode($json);
				$title=gT($mtz['title']);
				if ($title=="")
					$title=$this->querysTitles[0];
				$json=jsonMerge($mtz,"{columns: ".$this->defaultColumns."}",";",";");
				$json=$this->onBeforeShowForm($json);
				$frm=new gForm($json);
				$flds=$this->querys[$this->actualQuery];

				$this->tabForm($flds,$frm);
				$frm->render($json);
				$this->senchaTitle=str_replace("'",'',$title);
				$this->senchaButtonsBottom=$frm->buttons;
				if ($this->formHtml<>"")
				{
					extjsVar("formHtml".$this->actualQuery,"Ext.Panel","{frame: true,items:[{html:'".$this->formHtml."'}]}");
					extjsDo("formHtml".$this->actualQuery.".render(document.body);");
				}
			} else
			{
				// Formulario mestre x detalhe(s)
				$mtz=jsonDecode($json);
				$title=gT($mtz['title']);
				if ($title=="")
					$title=$this->querysTitles[0];
				//$mtz=jsonRemove($mtz,"title");
				$frms=new gTab("{title: '$title'; name: 'tabForm".$this->name."'}");
				$mtz['columns']="2";
				$mtz['frame']="true";
				$fjson=jsonEncode($mtz);
				$fjson=$this->onBeforeShowForm($fjson);
				$frm=new gForm($fjson);
				$flds=$this->querys[0];

				$this->tabForm($flds,$frm);
				$frm->render($json);
				$this->senchaTitle=str_replace("'",'',$title);
				$this->senchaButtonsBottom=$frm->buttons;

				if ($this->formHtml<>"")
				{
					extjsVar("formHtml".$this->actualQuery,"Ext.Panel","{frame: true,items:[{html:'".$this->formHtml."'}]}");
					extjsDo("formHtml".$this->actualQuery.".render(document.body);");
				}
				if (intval($_REQUEST['gId'])<>0)
				{
					if ($this->tabHtml<>"")
					{
					//	$frms->add(gT("Informações"),$this->tabHtml);
					}
					$i[]="{text: $title, pressed: true}";
					for ($a=1; $a<count($this->querys); $a++)
					{
						/*
						$this->actualQuery++;
						$this->enabledPermissions=$this->calcPermissions($this->querysPermissions[$a]);
						$tjson="{name: 'tabGrid$a'}";
						$this->showGridPage($tjson);
						$frms->add($this->querysTitles[$a],"tabGrid$a");
						 *
						 */
						$i[]="{text: '".$this->querysTitles[$a]."'}";
					}
					$this->senchaButtonsTop[]="{xtype: 'spacer'}";
					$this->senchaButtonsTop[]="{xtype: 'segmentedbutton', allowDepress: true, items: [".implode(",",$i)."]}";
					$this->senchaButtonsTop[]="{xtype: 'spacer'}";
					//$extjsBuffer=$extjsB;
					//$frms->render();
					//extjsDo("tabForm".$this->name.".render(document.body);");
				}

			}
		}
	}





	function showGridPage($json)
	{
		$mtz=jsonDecode($json);
		$mtz=cssRemove($mtz,'columns');
		$defaults="";
		$param=jsonMerge($defaults,jsonEncode($mtz));
		//$this->gDump($param);

		//if (strpos($this->permissions,"U")===false)
		{

			if (count($this->gridQuerys)>$this->actualQuery)
				$sqls=$this->gridQuerys[$this->actualQuery];
			else
				$sqls=$this->querys[$this->actualQuery];

			$sql=$sqls->query;
			$flds=$sqls->fields;

			$columns="";
			foreach ($flds as $fld)
			{
				$extra="";
				$header=$fld["fieldLabel"];
				$dataIndex=$fld["alias"];
				$name=$fld["name"];
				$type=$fld["type"];

				if ($name<>"idd")
				{
					// Caso exista(m) dicionário(s), usa...
					if (is_array($this->dictionarys))
					{
						foreach ($this->dictionarys as $dictObj)
						{
							$dict=$dictObj->get();
							foreach ($dict as $key=>$parms)
							{
								if ($name==$key)
								{
									if ($parms['fieldLabel']<>"") $header=$parms['fieldLabel'];
									if ($parms['type']<>"") $type=$parms['type'];
									break;
								}
							}
						}
					}
					if ($type=='datetime')
						$extra.="; width: 110";
					if ($type=='checkbox')
						$extra.="; width: 80";
					if (strtolower($fld['alias'])=='id')
						$extra.="; width: 65";
					if ((strtolower($fld['alias'])=='password')||(strtolower($fld['alias'])=='senha'))
						$extra.="; width: 67";
					if (($type<>"hidden") && ($type<>"exclude"))
						$columns[]="{header: '$header';dataIndex: '$dataIndex'; sortable : true $extra}";
				}
			}


			$param=extjsPaste($param,"columns",$columns);
			$param=str_replace(";;",";",$param);

		}
		//$columns=implode(",",$columns);
		//$param=cssEncode($mtz);

		//$this->grid($param);
		// Caso exista(m) filtros(s), usa...
		if (($_REQUEST['onFilter']=='') && ($this->actualQuery==0) && (is_array($this->filters)))
		{
			$f=$this->filters[0];
			$f->add("{type: hidden; name: onFilter; value: 1}");
			//echo "<pre>";print_r($this->filters[0]);exit;
			$frm=new gForm("{subTitle: '".gT('Selecione os filtros para busca')."'; frame: false; standardSubmit:true; columns: 1; collapsible: true}");
			$flds=$f;
			$this->tabFilter($flds,$frm);
			$frm->render($json);
			$this->senchaTitle=str_replace("'",'',$mtz['title']);
			$this->senchaButtonsBottom=$frm->buttons;

		} else
			$this->grid($param);
	}


	function showPage($json="")
	{
		$this->showToolBar=false;
		parent::showPage($json);
	}

}

?>