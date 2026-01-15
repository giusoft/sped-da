<?
/** Este arquivo contém a estrutura padrão para apresentação ao usuário
 *  de algum conteúdo para web (estação de trabalho)
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */


 class gPage extends g_Page
{
	function __construct($renderTo=gRENDER_REMOTE)
	{
		parent::__construct($renderTo);

	}



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
		parent::__construct($this->json);
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
		$this->gBody();
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
			parent::__construct($this->json);
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
			$this->gBody();
		}
		return "{success: true}";
	}












	function grid($json)
	{
		$pos="";
		$mtz=jsonDecode($json,";",true);
		$name=stripQuote($mtz['name']);
		$title=stripQuote($mtz['title']);
		$maxrows=gVar("database.maxrows");
		if ($this->actualQuery>0)
				 $maxrows=8;
		if (is_array($this->filters))
				$maxrows=10;
		$height=30*$maxrows;
		$this->maxrows=$maxrows;

		if (($title=="") && ($this->actualQuery==0))
			$title="NoNameGrid";
		if ($name=="")
		{
			$name=gFieldReverse($title); // chapa o texto
		}
		$mtz=jsonRemove($mtz,'name');

		$linkUrl=str_replace("'","",$this->showPageParams['linkUrl']);
		if ($linkUrl=="")
			$linkUrl=$this->page;

		$qry=$this->querys[0];
		$tab=$qry->tables[0]['name'];
		$fk="id_$tab";

		// Store
		$query=$this->querys[$this->actualQuery];
		$campos="";
		foreach($query->fields as $campo)
		{
			if (($campo['alias']<>'idd') && ($campo['alias']<>$fk))
			{
				$campos[]=$campo['name'];
				$labels[]=$campo['fieldLabel'];
			}
		}
		gMsg::alert($title);

		//$sql=$query->query;
		$sql="SELECT ".$query->sections['select']." FROM ".$query->sections['from'];
		$sql.=$this->addFilterOnWhere($query->sections['where']);
		$rs=gQuery($sql);


		$campos="";
		$tipos="";
		foreach($query->fields as $campo)
		{
			if (($campo['alias']<>'idd') && ($campo['alias']<>$fk))
			{
				$key=$campo['alias'];
				$nkey=$campo['fieldLabel'];
				$type=$campo['type'];
					// Caso exista(m) dicionário(s), usa...
					if (is_array($this->dictionarys))
					{
						foreach ($this->dictionarys as $dictObj)
						{
							$dict=$dictObj->get();
							foreach ($dict as $dkey=>$d)
							{
								if ($key==$dkey)
								{
									$nkey=$d['fieldLabel'];
									$type=$d['type'];
									break;
								}
							}
						}
					}

				$pre="";
				if ($type=="text")
					$pre="<-";
				if (($type=="integer") || (($type=="number")))
					$pre="->";
				if (($type<>"hidden") && ($type<>"exclude"))
				{
					$campos[]=$pre.$nkey;
					$tipos[$key]=$type;
				}
			}
		}
		$this->reportFields=$tipos;
		$arr="";
		echo $this->gTableBegin("big");
		echo $this->gTableRow($campos,"header");

		while (!$rs->EOF)
		{

			foreach ($rs->fields as $key=>$value)
			{

				if (!is_numeric($key))
				{
					$fld=$query->getField($key);
					$type=$fld['type'];
					// Caso exista(m) dicionário(s), usa...
					if (is_array($this->dictionarys))
					{
						foreach ($this->dictionarys as $dictObj)
						{
							$dict=$dictObj->get();
							foreach ($dict as $dkey=>$d)
							{
								if ($key==$dkey)
								{
									if ($d['type']<>"") $type=$d['type'];
									if ($d['type']=="combo")
									{
										$dados=jcombo2array($d['items']);
										foreach ($dados as $dadosEl)
										{
											$dadosEl=substr($dadosEl,2);
											$dadosEl=substr($dadosEl,0,strlen($dadosEl)-2);
											$el=explode("','",$dadosEl);
											if (($el[0]==$value) || ($el[1]==$value))
											{
												$value=$el[1];
												$rs->fields[$key]=$value;
												break;
											}
										}

									}
									break;
								}
							}
						}
					}
					if ($type=='datetime')
					{
						$rs->fields[$key]=gDateTime($value);
					}
					if ($type=='date')
					{
						$rs->fields[$key]=gDate($value);
					}
					if ($type=='number')
					{
						$rs->fields[$key]=gFloat($value);
					}
					if ($type=='checkbox')
					{
						if ($value==1)
							$rs->fields[$key]="X";
						else
							$rs->fields[$key]="&hellip;";
					}
					if ($type=='password')
					{
						$rs->fields[$key]="******";
					}
					if ($type=='cpf')
					{
						if ($value<>"")
							$rs->fields[$key]=substr($value,0,3).".".substr($value,3,3).".".substr($value,6,3)."-".substr($value,-2);
					}
					if (($key=="id") && ($linkUrl<>"") && ($this->enabledPermissions['link']))
					{
						$sep="?";
						if (strpos($linkUrl,"?")>0)
							$sep="&";
						$rs->fields[$key]="<a class='g-a' href='$linkUrl".$sep."process=link&gId=$value&i=$value' >".str_pad(intval($value), 8, "0", STR_PAD_LEFT)."</a>";
					}


					$ttl=$this->totals[$q];
					if (isset($ttl[$key]))
					{
						if ($ttl[$key]['formula']=="sum")
							$this->totals[$q][$key]['value']=$ttl[$key]['value']+$value;
						if ($ttl[$key]['formula']=="avg")
							$this->totals[$q][$key]['value']=$ttl[$key]['value']+$value;
						if ($ttl[$key]['formula']=="count")
							$this->totals[$q][$key]['value']=$ttl[$key]['value']+1;
						if ($ttl[$key]['formula']=="max")
						{
							if ($value>$ttl[$key]['value'])
								$this->totals[$q][$key]['value']=$value;
						}
					}

				} else
				{
					// excluir elemento com chave numerica
				}
			}
			if ($x<>"")
			{
				foreach ($rs->fields as $k=>$v)
				{
					if (!is_numeric($k))
					$rsf["$x$k"]=$v;
				}
				$arr[]=$rsf;
			} else
				$arr[]=$rs->fields;
			$rs->MoveNext();
		}
		foreach ($arr as $linhas)
		{
			$mtz="";

			if (is_array($linhas))
			{
				foreach ($linhas as $key=>$value)
				{
					if ((!is_numeric($key)) && ($key<>"idd"))
					{
						$pre="";
						if ($this->reportFields[$key]=="text")
							$pre="<-";
						if (($this->reportFields[$key]=="integer") || (($this->reportFields[$key]=="number")))
							$pre="->";
						$mtz[]=$pre.$value;
					}
				}
				echo "<tbody onfocus='alert()'>";
				echo $this->gTableRow($mtz,"bigdetail");
				echo "</tbody>";
			}
		}
		echo $this->gTableEnd();

		//echo "<Pre>";var_dump($query);
	}



	function showGridPage($json)
	{
		$mtz=jsonDecode($json);
		$mtz=cssRemove($mtz,'columns');
		$defaults="";
		$param=jsonMerge($defaults,jsonEncode($mtz));
		//$this->gDump($param);



		// Caso exista(m) filtros(s), usa...
		if (($this->actualQuery==0) && (is_array($this->filters)) && ($_REQUEST['filter']<>'on'))
		{
			$frm=new gForm("{title: '".gT("Opções de filtro")."'; button: 'goFilter()'; frame: false; standardSubmit:true; columns: 2; collapsible: true}");
			$flds=$this->filters[0];
			$this->tabFilter($flds,$frm);
			$frm->add("{name: filter; type: hidden; value: 'on'}");
			$frm->add("{name: start; type: hidden; value: '0'}");
			$frm->add("{name: limit; type: hidden; value: '".$this->maxrows."'}");

			$frm->render($json);

		}

		//if (strpos($this->permissions,"U")===false)
		if (($_REQUEST['filter']=='on') || (!is_array($this->filters)))
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
			$this->grid($param);

		}
		//$columns=implode(",",$columns);
		//$param=cssEncode($mtz);



	}


}

 ?>