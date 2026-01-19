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
        }, 3000);
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
			parent::parentConstruct($this->json);
			//parent::__construct($this->json);
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
		$pos="";
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

		$qry=$this->querys[0];
		$tab=$qry->tables[0]['name'];
		$fk="id_$tab";

		// Store
		if (($this->actualQuery==0) && (count($this->gridQuerys)>0))
		//if (count($this->gridQuerys)>$this->actualQuery)
			$query=$this->gridQuerys[$this->actualQuery];
		else
			$query=$this->querys[$this->actualQuery];
		$campos="";
		foreach($query->fields as $campo)
		{
			if ((($campo['alias']<>'idd') && ($campo['alias']<>$fk)) || (is_developer()))
			{
				$campos[]="'$fprefix".$campo['name']."'";
				$rcampos[]="{name: '".$fprefix.$campo['name']."', type: 'string'}";
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
		}

		$outrosParm=implode("&",$outrosParm);
		$store="{url: '".$this->page."&process=gridData&w=0&x=$fprefix&i=".$_REQUEST['gId']."&q=".$this->actualQuery."&"."$outrosParm', reader: new Ext.data.JsonReader({root: 'rows',id: '".$fprefix."id', totalProperty: 'totalCount'},[$campos])}";
		extjsVar("gridStore".$this->actualQuery,"Ext.data.Store",$store);
		//extjsDo("gridStore.load();");
		$maxrows=gVar("database.maxrows");
		if ($this->actualQuery>0)
				 $maxrows=8;
		if (is_array($this->filters))
				$maxrows=10;
		$height=30*$maxrows;
		$this->maxrows=$maxrows;
		if ($this->enabledPermissions['gridupdate'])
		{
			extjsVar("editor","Ext.ux.grid.RowEditor","{saveText: '".gT("Salvar")."', cancelText: '".gT("Cancelar")."'}");
			extjsDo("
			var gridRow = Ext.data.Record.create([$record])");
			extjsDo("
			editor.on({
				scope: this,
				afteredit: function(roweditor, changes, record, rowIndex) {
				 Ext.Ajax.request({
					url   : '".$this->page."&process=formEditCommit&ajax=1&x=$fprefix&i=".$_REQUEST['gId']."&q=".$this->actualQuery."&id='+record.id,
					method: 'POST',
					params: changes,
					success: function(resp) {
					  gridStore".$this->actualQuery.".reload();
                      var jsonData = Ext.util.JSON.decode(resp.responseText);
                      var resultMessage = jsonData.message;
					  if ((resultMessage != null) && (resultMessage != ''))
						Ext.Msg.alert(\"$title\",resultMessage);
					}
				 });
				}
			});
			");
		}
		$linkUrl=str_replace("'","",$this->showPageParams['linkUrl']);
		if ($linkUrl=="")
			$linkUrl=$this->page;
		if ($this->showPageParams['linkLabel']<>"")
			$linkLabel=str_replace("'","",$this->showPageParams['linkLabel']);
		else
			$linkLabel=gT("Selecionar");
		// Grid
		//$defaults="{id: 'gfwGrid".$this->actualQuery."', title: '$title', store: gridStore".$this->actualQuery.", renderTo:document.body, frame: true, height: $height, width: '100%' }";
		//$defaults="{id: 'gfwGrid".$this->actualQuery."', title: '$title', store: gridStore".$this->actualQuery.", renderTo:document.body, frame: true, height: $height, width: '100%'}";
		$defaults="{id: 'gfwGrid".$this->actualQuery."', title: '$title', store: gridStore".$this->actualQuery.", renderTo:document.body, frame: true, height: $height, width: '100%'}";
		if ((count($mtz)>0) && ($mtz<>""))
			$param=jsonMerge($defaults,jsonEncode($mtz,",",false),",",",");
		else
			$param=$defaults;
		$param=substr($param,0,strlen($param)-1);
		// Selecionar apenas uma linha
		if ($this->enabledPermissions['formupdate'])
	  {
				$param.=", listeners:{celldblclick:
				function(me,row,col,e){
					var comp=Ext.getCmp(\"gfwGrid".$this->actualQuery."\");
					var sai = comp.getSelectionModel().getSelected();
					document.location.href=\"".$this->page."&gId=\"+sai.id+\"&process=formEdit&i=".$_REQUEST['gId']."&q=".$this->actualQuery."\";
				}}";
		} elseif ($this->enabledPermissions['windowupdate'])
		{
			$param.=", listeners:{celldblclick: winEditar".$this->actualQuery."}";
			$param.=", columns: \n[";
			$flds=$this->querys[$this->actualQuery];

			$frm=new gForm();
			$frm=$this->tabForm($flds,$frm,true);
			$fields=$frm->getFields();
			foreach ($fields as $f)
			{
				if (strpos($f,"listeners")!==false)
					$f=substr($f,0,strpos($f,"listeners"));
				$s=str_replace("{","",$f);
				$s=str_replace("}","",$s);
				$sm=explode(",",$s);
				foreach ($sm as $tmp)
				{
					//echo "$f<br>";
					$cmp=explode(":",trim($tmp));
					//$n="'$fprefix".str_replace("'","",trim($cmp[1]))."'";
					$n=trim($cmp[1]);
					if (trim($cmp[0]=="name"))
						$fname=$n;
					if (trim($cmp[0]=="fieldLabel"))
						$ffieldLabel=trim($cmp[1]);
					if (trim($cmp[0]=="hiddenName"))
						$fname=$n;
				}
				if (($fname<>"'".$fprefix."idd'") || (is_developer()))
				{
					$el[]="{id: $fname, header: $ffieldLabel, dataIndex: $fname, sortable: true }\n";
				}
			}
			$param.=implode(",",$el);
			$param.="]\n";
		} elseif ($this->enabledPermissions['gridupdate'])
		{
			$param.=", plugins: [editor]";
			//$param.=", columns: [new Ext.grid.RowNumberer(),\n";
			$param.=", columns: \n[";
			if (count($this->gridQuerys)>$this->actualQuery)
				$flds=$this->gridQuerys[$this->actualQuery];
			else
				$flds=$this->querys[$this->actualQuery];

			$frm=new gForm();
			$frm=$this->tabForm($flds,$frm,true);
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
					if (trim($cmp[0]=="name"))
						$fname=$n;
					if (trim($cmp[0]=="fieldLabel"))
						$ffieldLabel=trim($cmp[1]);
					if (trim($cmp[0]=="hiddenName"))
						$fname=$n;
				}
				if (($fname<>"'".$fprefix."idd'") || (is_developer()))
				{
					if ((strtolower($fname)=="'".$fprefix."id'") || (strtolower($fname)=="'".$fprefix."idd'"))
					{
						$el[]="{id: $fname, header: $ffieldLabel, dataIndex: $fname, width: 10, sortable: true }\n";
					} else
					{
						$el[]="{id: $fname, header: $ffieldLabel, dataIndex: $fname, width: 60, sortable: true, editor: $f }\n";
					}
				}
			}
				$param.=implode(",",$el);
			$param.="]\n";
		} elseif (($linkUrl<>"") && ($this->enabledPermissions['link']))
		{
				$param.=", listeners:{celldblclick: selecionar}";
		}
		$param.=", sm: new Ext.grid.RowSelectionModel({singleSelect: true}) ";
		$param.=", viewConfig: {forceFit:true, enableRowBody:true}";
		$param.=", tbar: new Ext.PagingToolbar({
		pageSize: $maxrows,
		store: gridStore".$this->actualQuery.",
		displayInfo: true,
		displayMsg: '".gT("Mostrando {0} a {1} de {2} registros")."',
		emptyMsg: '".gT("Nenhum registro encontrado")."',
		items:[
			'-'";
		if ($this->enabledPermissions['insert'])
		{
			if ($this->enabledPermissions['formupdate'])
			{
				$param.="

				,{width: 60, text: 'Novo',	iconCls: 'b2001_16', cls: 'x-btn-text',
					handler: function(){
						document.location.href=\"".$this->page."&process=formNew&i=".$_REQUEST['gId']."&q=".$this->actualQuery."\";
					}
				}";
			} elseif ($this->enabledPermissions['gridupdate'])
			{
				$param.="

				,{width: 60, text: 'Novo',	iconCls: 'b2001_16', cls: 'x-btn-text',
					handler: function(){
						editor.stopEditing();
						var newRow = new gridRow({});
						$name.store.insert(0, newRow);
						$name.getView().refresh();
						$name.getSelectionModel().selectRow(0);
						editor.startEditing(0);
					}
				}";
			} elseif ($this->enabledPermissions['windowupdate'])
			{
				$param.="

				,{width: 60, text: 'Novo',	iconCls: 'b2001_16', cls: 'x-btn-text',
					handler: function(){
						document.location.href=\"".$this->page."&process=formNew&i=".$_REQUEST['gId']."&q=".$this->actualQuery."\";
					}
				}";
			}
		}
		if ($this->enabledPermissions['windowupdate'])
		{
			$param.="
			,{width: 60, text: '".gT('Editar')."', iconCls: 'b2002_16', cls: 'x-btn-text',
				handler: winEditar".$this->actualQuery."
			}";

			$flds=$this->querys[$this->actualQuery];

			$frm=new gForm();
			$frm=$this->tabForm($flds,$frm);
			$fields=$frm->getFields();
			$items=implode(",",$fields);
			if (count($fields)>10)
				$maximized="maximized: true,";
			$pos="
				function winEditar".$this->actualQuery."()
				{
					var comp=Ext.getCmp(\"gfwGrid".$this->actualQuery."\");
					var sai = comp.getSelectionModel().getSelected();
					if(typeof sai == 'undefined')
					{
						Ext.Msg.alert(\"$title\",\"".gT("Selecione um registro primeiro!")."\");

					} else
					{

						var winForm = new Ext.FormPanel({
							frame: true,
							labelWidth: 120,
							width:'100%',
							submitEmptyText: true,
							waitMsgTarget: true,
							url: '".$this->page."&process=formEditCommit&w=1&ajax=1&x=$fprefix&i=".$_REQUEST['gId']."&q=".$this->actualQuery."&id='+sai.id,
							items:[
								$items
							]
						});

						var win = new Ext.Window({
							title: '".gT('Editar')."',
							layout:'form',
							modal: true,
							autoScroll:true,
							margins:'3 3 3 0',
							width:'400',
							autoHeight: true,
							maximizable: true,
							$maximized
							height:'300',
							closeAction:'close',
							plain: true,
							items:[
								winForm
							],
							buttons: [{
							  text:'".gT('Salvar')."',
								handler: function()
								{
									if (winForm.form.isValid())
									{
										winForm.form.submit(
										{
											waitMsg: '".gT("Aguarde...")."',
											clientValidation: true,
											success: function(form, action) {
												gridStore".$this->actualQuery.".reload();
												Ext.Msg.alert(\"$title\",\"".gT("Registro salvo!")."\");
												win.close();
											},
											failure: function(form, action) {
												Ext.Msg.alert(\"$title\",\"".gT("Falha ao salvar. Confira os dados informados, e verifique se tem permissão para esta ação...")."\");
											}
										});
									}
								}
							},{
							  text: '".gT('Cancelar')."',
							  handler: function(){
									win.close();
							  }
							}]

						 });
						win.show(this);
						winForm.form.load({
							url:'".$this->page."&process=gridData&w=1&x=$fprefix&i=".$_REQUEST['gId']."&q=".$this->actualQuery."&gwId='+sai.id,
							method:'GET',
							waitMsg:'".gT("Aguarde...")."'
						});

					}
				}
			";
		}
		if ($this->enabledPermissions['formupdate'])
		{
			$param.="
			,{width: 60, text: 'Editar', iconCls: 'b2002_16', cls: 'x-btn-text',
				handler: editar
			}";
		}
		if ($this->enabledPermissions['delete'])
		{
			$param.="
			,{width: 60, text: 'Apagar', iconCls: 'b2003_16', cls: 'x-btn-text',
				handler: function(){
					var comp=Ext.getCmp(\"gfwGrid".$this->actualQuery."\");
					var sai = comp.getSelectionModel().getSelected();
					if(typeof sai == 'undefined')
					{
						Ext.Msg.alert(\"$title\",\"".gT("Selecione um registro primeiro!")."\");
					} else
					{
						Ext.Msg.confirm(\"$title\",\"".gT("Confirma exclusão do registro selecionado?")."\", function(btn, text)
							{
								if (btn == 'yes'){
									Ext.Ajax.request({
										url: '".$this->page."',
										params: 'process=gridDelete&i=".$_REQUEST['gId']."&q=".$this->actualQuery."&id='+sai.id,
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
									gridStore".$this->actualQuery.".reload();
								}
							});
					}
				}
			}";
		}
		// $param.=",{width: 60, text: 'Exportar', iconCls: 'b2009_16', cls: 'x-btn-text'}";
		if (($linkUrl<>"") && ($this->enabledPermissions['link']))
		{
			$url=$this->page;

			if (is_array($this->linkParameters))
			{
				$frm=new gForm();
				foreach ($this->linkParameters as $p)
				{
					$cmps=$p->get();
					foreach ($cmps as $cmp)
						$frm->add($cmp);
				}
				$fields=$frm->getFields();
				$items=implode(",",$fields);
				$sep="?";
				if (strpos($linkUrl,"?")>0)
					$sep="&";

				$prm="

						var winParForm = new Ext.FormPanel({
							frame: true,
							labelWidth: 85,
							width:'100%',
							submitEmptyText: true,
							standardSubmit: true,
							waitMsgTarget: true,
							url:'$linkUrl".$sep."process=link&i=".$_REQUEST['gId']."&gId='+sai.id,
							items:[
								$items
							],
						});

						var winPar = new Ext.Window({
							title: '".gT("Parâmetros")."',
							layout:'form',
							modal: true,
							autoScroll:true,
							margins:'3 3 3 0',
							width:'400',
							autoHeight: true,
							maximizable: true,
							$maximized
							height:'300',
							closeAction:'close',
							plain: true,
							items:[
								winParForm
							],
							buttons: [{
							  text: '".gT('Cancelar')."',
							  handler: function(){
									winPar.close();
							  }
							},{
							  text:'".gT('Confirmar')."',
								handler: function()
								{
									if (winParForm.form.isValid())
									{
										winParForm.form.submit(
										{
											waitMsg: '".gT("Aguarde...")."',
											clientValidation: true,
											success: function(form, action) {
												gridStore".$this->actualQuery.".reload();
												winPar.close();
											},
											failure: function(form, action) {
												Ext.Msg.alert(\"$title\",\"".gT("Falha ao salvar! Confira os dados informados, e verifique se tem permissão para esta ação...")."\");
											}
										});
									}
								}
							}]

						 });
						winPar.show(this);






					";
			} else
			{
				$sep="?";
				if (strpos($linkUrl,"?")>0)
					$sep="&";

				$prm="document.location.href=\"$linkUrl".$sep."process=link&i=".$_REQUEST['gId']."&gId=\"+sai.id;";
			}
			extjsDo("
					function selecionar(){
						var comp=Ext.getCmp(\"gfwGrid".$this->actualQuery."\");
						var sai = comp.getSelectionModel().getSelected();
						if(typeof sai == 'undefined')
							Ext.Msg.alert(\"$title\",\"".gT("Selecione um registro primeiro!")."\");
						else
						{
							$prm
						}
					}
			");
			$param.="
			,{width: 60, text: '$linkLabel', iconCls: 'b2016_16', cls: 'x-btn-text',
				handler: selecionar
			}";
			if (!$this->enabledPermissions['formupdate'])
			{
				/*
				extjsDo("
						new Ext.KeyMap(document, {
						  key: Ext.EventObject.ENTER,
						  fn: selecionar
						});
					");
				 *
				 */
			}
		}
		if (is_developer())
		{
			$sep="?";
			if (strpos($linkUrl,"?")>0)
				$sep="&";

			$prm="document.location.href=\"$linkUrl".$sep."process=public&i=\"+sai.id+\"&gId=\"+sai.id;";
			extjsDo("
					function publico(){
						var comp=Ext.getCmp(\"gfwGrid".$this->actualQuery."\");
						var sai = comp.getSelectionModel().getSelected();
						if(typeof sai == 'undefined')
							Ext.Msg.alert(\"$title\",\"".gT("Selecione um registro primeiro!")."\");
						else
						{
							$prm
						}
					}
			");
			$param.="
			,{width: 60, text: '".gT("Público")."', iconCls: 'b2017_16', cls: 'x-btn-text',
				handler: publico
			}";
		}

		$param.="
			]
		}) ";
		$param.="}";

		extjsVar($name,"Ext.grid.EditorGridPanel",$param);
		if ($this->enabledPermissions['formupdate'])
		{
			$url=$this->page;
			extjsDo("
					function editar(){
						var comp=Ext.getCmp(\"gfwGrid".$this->actualQuery."\");
						var sai = comp.getSelectionModel().getSelected();
						if(typeof sai == 'undefined')
							Ext.Msg.alert(\"$title\",\"".gT("Selecione um registro primeiro!")."\");
						else
						{
							document.location.href=\"".$this->page."&gId=\"+sai.id+\"&x=$fprefix&process=formEdit&q=".$this->actualQuery."\";
						}
					}
			");
			if (!is_array($this->linkParameters))
			{
				extjsDo("
						new Ext.KeyMap(document, {
						  key: Ext.EventObject.ENTER,
						  fn: editar
						});
					");
			}
		}
		extjsDo($pos);
		extjsDo("var mask=new Ext.LoadMask(Ext.getBody(),{msg: '".gT("Carregando...")."', store:gridStore".$this->actualQuery."})");
		extjsDo("gridStore".$this->actualQuery.".load({params:{start:0, limit:$maxrows}});");
		if ($this->gridHtml<>"")
		{
			extjsVar("gridHtml".$this->actualQuery,"Ext.Panel","{frame: true,items:[{html:'".$this->gridHtml."'}]}");
			extjsDo("gridHtml".$this->actualQuery.".render(document.body);");
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

				if (($name<>"idd") || (is_developer()))
				{
					if ($name=="idd")
					{
						$header=gT("Público");
						$extra.="; width: 30";
					}
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
						$extra.="; width: 80";
					if ($type=='checkbox')
						$extra.="; width: 60";
					if (strtolower($fld['alias'])=='id')
						$extra.="; width: 40";
					if ((strtolower($fld['alias'])=='password')||(strtolower($fld['alias'])=='senha'))
						$extra.="; width: 40";
					if (($type<>"hidden") && ($type<>"exclude"))
						$columns[]="{header: '$header';dataIndex: '$dataIndex'; sortable : true $extra}";
				}
			}


			$param=extjsPaste($param,"columns",$columns);
			$param=str_replace(";;",";",$param);

		}
		//$columns=implode(",",$columns);
		//$param=cssEncode($mtz);
		$this->grid($param);

		// Caso exista(m) filtros(s), usa...
		if (($this->actualQuery==0) && (is_array($this->filters)))
		{
			$frm=new gForm("{title: '".gT("Filtros")."'; subTitle: ".gT("Informe os dados para filtragem")."; button: 'goFilter()'; frame: false; standardSubmit:true; columns: 2; collapsible: true}");
			$flds=$this->filters[0];
			$this->tabFilter($flds,$frm);
			$frm->render($json);

			//echo "<pre>";var_dump($flds->filter);exit;
			$filtros="";
			$f="";
			foreach ($flds->filter as $flt)
			{
				/*
				if ($flt['type']=="combo")
				{
					$filtros[]="combo".$flt['name'].": Ext.get('combo".$flt['name']."').getValue()\n";
					$f.="if (Ext.get('combo".$flt['name']."')) s.setBaseParam('combo".$flt['name']."', Ext.get('combo".$flt['name']."').getValue());\n";
				} else
				 *
				 */
				{
					$filtros[]=$flt['name'].": Ext.get('".$flt['name']."').getValue()\n";
					$f.="if (Ext.get('".$flt['name']."')) s.setBaseParam('".$flt['name']."', Ext.get('".$flt['name']."').getValue());\n";
				}
			}
			$filtros=",".implode(",",$filtros);
			/*
			$goFilter="
			function goFilter()
			{
				newOptions = gridStore".$this->actualQuery.".lastOptions;
				Ext.apply(newOptions.params, {
					 filter: 'on'
					 $filtros
				});

				gridStore".$this->actualQuery.".reload(newOptions);
			}";
			 *
			 */
			$goFilter="
			function goFilter()
			{
				gridStore".$this->actualQuery.".on('beforeload', function(s) {
					 s.setBaseParam('filter', 'on');
					 s.setBaseParam('start', 0);
					 s.setBaseParam('limit', ".$this->maxrows.");
					 $f
				});

				// This should work :
				gridStore".$this->actualQuery.".load();
				Ext.get('gfwGrid".$this->actualQuery."').update;
			}
			";
			extjsDo($goFilter);
		}

	}




}

 ?>