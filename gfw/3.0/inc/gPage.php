<?php
/** Este arquivo contém a estrutura padrão para apresentação de
 *  páginas renderizadas automaticamente
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */

include_once $gPathDefault."gInput.php";
include_once $gPathDefault."gDB.php";
header("Content-Type: text/html; charset=".strtoupper(gVar("global.charset")));

class g_Page extends gInput
{
	public $id;

	public $parameters;

	public $querys;
	public $gridQuerys;
	public $querysPermissions;
	public $querysTitles;
	public $actualQuery=0;

	public $json;
	public $names;
	public $relations;
	public $stores="";
	public $requestFields;
	public $showPageParams;
	public $showPageParamsJson;
	public $masterdetail;
	public $listeners;
	public $databaseEnabled;
	public $bQuerys;
	public $bGridQuerys;
	public $maxrows;
	public $message='';

	public $dictionarys;
	public $filters;
	public $linkParameters;
	public $permissions;
	public $enabledPermissions;
	public $defaultColumns;
	public $js="";
	public $reportFields;
	public $gridHtml;
	public $formHtml;
	public $tabHtml;
	public $retornoNaTela=true;
	private $processed;

	function __construct($json)
	{
		global $gDevice;
		$css=cssDecode($json);
		$this->id="id";
		$this->permissions="SIUD";
		$this->page=$_SERVER["PHP_SELF"]."?g=".$_REQUEST['g'];
		$this->querys="";
		$this->dictionarys="";
		$this->json=$json;
		$this->databaseEnabled=false;
		if ($css['title']<>'')
			$this->title=$css['title'];
		if ($css['editarea']=='true')
			$this->editarea=true;
		else
			$this->editarea=false;
		if ($css['codemirror']=='true')
			$this->codemirror=true;
		else
			$this->codemirror=false;
		if ($css['span']=='true')
			$this->span=true;
		if ($css['span']=='false')
			$this->span=false;
		if ($css['maps']=='true')
			$this->maps=true;

		if ($css['ace']=='true')
			$this->ace=true;
		else
			$this->ace=false;

		if ($gDevice=="web")
			$this->defaultColumns=1;
		else
			$this->defaultColumns=1;
	}

	function gBegin($par)
	{
		parent::gBegin($par);
	}

	function valueFromType($type,$value)
	{
		// SQL Injection
		$value=str_replace("'","`",$value);
		if ($type=="checkbox")
		{
			if ($value=="on")
				$value="1";
			else
				$value="0";
		} elseif ($type=="integer")
		{
			$value=intval(gDBFloat($value));
		} elseif ($type=="number")
		{
				$value=gDBFloat($value);
		} elseif ($type=="date")
		{
			$value="'".gDBDate($value)."'";
		} elseif ($type=="datetime")
		{
			$value="'".gDBDateTime($value)."'";
		} else
		{
			$value="'$value'";
		}
		return($value);
	}

	function insertFromFields($flds)
	{
		global $usrId;
		$sql="INSERT INTO ".$flds[0]['table']." ";
		foreach ($flds as $fld)
		{
			$value=$this->requestFields[$fld['name']];
			// Campos inseridos automaticamente
			/*
			if ($fld['name']=="idd")
			{
				$value=intval($_SESSION['usrIdd']);
			}
			 *
			 */
			if ($fld['name']=="id_geral_pessoas_criou")
			{
				$value=$usrId;
			}
			if ($fld['name']=="data_criacao")
			{
				$value=date("d-m-Y H:i:s");
			}
			if ((isset($this->requestFields[$fld['name']])) || ($value<>""))
			{
				$f[]=$fld['name'];
				$v[]=$this->valueFromType($fld['type'], $value);
			}
		}
		$sql.="(".implode(",",$f).") values (".implode(",",$v).")";
		return($sql);
	}

	function updateFromFields($flds)
	{
		global $usrId;
		$sql="UPDATE ".$flds[0]['table']." SET ";
		foreach ($flds as $fld)
		{
			$value=$this->requestFields[$fld['name']];
			// Campos inseridos automaticamente
			if ($fld['name']=="id_geral_pessoas_criou")
			{
				$value=$usrId;
			}
			if ($fld['name']=="data_criacao")
			{
				$value=date("d-m-Y H:i:s");
			}
			if ((isset($this->requestFields[$fld['name']])) || ($value<>""))
			{
				$f[]=$fld['name']."=".$this->valueFromType($fld['type'], $value);
			}
		}
		$sql.="".implode(",",$f)." where ".$this->id."=".$this->requestFields['gId'];
		return($sql);
	}

	/** Executa inclusão de registro do banco de dados
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $gId id do registro
	 */
	function dataAdd()
	{
		$query=$this->querys[$this->actualQuery];
		$sql=$query->query;
		$flds=$query->fields;
		$tmp=$this->onBeforeAdd($this->requestFields);
		// Só salva se retornar o array com campos (se retornar false, não salva)
		if (is_array($tmp))
		{
			$this->requestFields=$tmp;
			$sql=$this->insertFromFields($flds);
			//echo "Query: $sql <br><br>";
			$query->run($sql);
			$this->onAfterAdd($flds);
		}
	}

	/** Executa edição de registro do banco de dados
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $gId id do registro
	 */
	function dataEdit()
	{

	}

	/** Executa exclusão de registro do banco de dados
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $gId id do registro
	 */
	function dataDelete()
	{

	}

	/** Executa cancelamento de registro do banco de dados
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $gId id do registro
	 */
	function dataCancel()
	{

	}


	// Eventos

	function on($tipo)
	{
		return (true);
	}

	/** Evento a ser executado ao selecionar um registro da tabela
	 * @author	giuliano
	 * @version	1.0 11-12-2010 15:05
	 * @param string $id Id do elemento selecionado
	 */
	function onSelect($id,$msg="",$moreJs="")
	{
		return (true);
	}

	/** Evento a ser executado antes de adicionar registro no banco
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $fields Array associativo com campos a serem adicionados
	 */
	function onBeforeAdd($fields)
	{
		return ($fields);
	}

	/** Evento a ser executado depois de adicionar registro no banco
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $fields Array associativo com campos que foram adicionados
	 */
	function onAfterAdd($fields,$msg="",$moreJs="")
	{
		$this->gBody();
	}

	function onAfterAdd2($fields)
	{
		return ($fields);
	}
	/** Evento a ser executado depois de alterar registro no banco
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $fields Array associativo com campos a serem alterados
	 */
	function onBeforeUpdate($fields)
	{
		return ($fields);
	}

	/** Evento a ser executado antes de calcular um campo para mostrar no Grid
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $fields Array associativo com campos a serem alterados
	 */
	function onBeforeCalculate($key,$fields)
	{
		return ($fields[$key]);
	}

	/** Evento a ser executado depois de alterar registro no banco
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $fields Array associativo com campos que foram alterados
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

	/** Evento a ser executado depois de excluir registro no banco
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $fields Array com campos do registro a ser excluído
	 */
	function onBeforeDelete($fields)
	{
		return (true);
	}

	/** Evento a ser executado depois de excluir registro no banco
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $fields Array com campos do registro que foi excluído
	 */
	function onAfterDelete($fields)
	{
		$mtz=cssDecode($this->json);
		parent::__construct($this->json);
		$this->gBegin();
		$this->gMsgTitle($mtz['title']);
		$this->gMsg(gT("Registro excluído com sucesso."));
		$this->gEnd();
	}

	function onBeforeRemoteCombo($items,$fields="")
	{
		return($items);
	}
	/** Evento a ser executado antes de mostrar os campos do formulario
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $fields Array com campos a serem adicionados
	 */
	function onBeforeShowFields($fields)
	{
		return ($fields);
	}

	/** Evento a ser executado antes de mostrar os campos do grid
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $fields Array com campos a serem adicionados
	 */
	function onBeforeShowGridFields($fields)
	{
		return ($fields);
	}

	/** Evento a ser executado antes de mostrar o formulario
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $json JSON com parâmetros
	 */
	function onBeforeShowForm($json)
	{
		return ($json);
	}


	function showAlert($titulo)
	{
	}


	function repairValue($value,$type)
	{
		$sep="'";
		//gLog("=== $type = $value");
		if ($type=='integer')
		{
			$sep="";
			$value=intval($value);
		}
		if ($type=='number')
		{
			if ($value=="")
				$value="0";
			$value=gDBFloat($value);
			$sep="";
		}
		if ($type=='date')
		{
			$value=gDBDate($value);
		}
		if ($type=='datetime')
		{
			$value=gDBDateTime($value);
		}
		if ($type=='checkbox')
		{
//			gLog("==============>>>>>>============== $value:".gDBCheck($value));
			$value=gDBCheck($value);
		}
		if ($type=='password')
		{
			$value=md5($value);
		}
		return($sep.$value.$sep);
	}

	function addFilterOnWhere($where,$table="")
	{
		if ($_REQUEST['filter']=='on')
		{
			if ($where<>"")
				$where="($where)";
			//$query=$this->querys[$this->actualQuery];
			//$fields=$query->fields;
			$flds="";
			//foreach ($fields as $fld)
			$filter=$this->filters[0];
			$flts=$filter->get();
			//echo "<pre>";print_r($_REQUEST);exit;
			foreach ($flts as $fld)
			{
				/*
				$fname=$fld['name'];
				$fname=str_replace("__",".",$fname);
				$fname=str_replace("from_","",$fname);
				$fname=str_replace("to_","",$fname);
				if (strpos($fname,".")!==false)
					$fname=substr($fname,strpos($fname,".")+1);
				 *
				 */
				foreach ($_REQUEST as $name=>$value)
				{
					$fullName=str_replace("__",".",$name);

					if ($name<>$fullName)
					{
						//$name=substr($fullName,strpos($fullName,".")+1);
					}
					$op="=";$dop="";
					if(substr($fullName,0,5)=="from_")
					{
						$op=">=";
						//$dop="";
						//$dop = gVar("database.engine") == "mysql" ? "" : " 00:00:01";
						$dop="";
						$fullName=str_replace("from_","",$fullName);
						//$name=str_replace("from_","",$name);

					}
					if(substr($fullName,0,3)=="to_")
					{
						$op="<=";
						//$dop = gVar("database.engine") == "mysql" ? "" : " 23:59:59";
						//$dop=" 23:59:59";
						$dop="";
						$dop="";
						$fullName=str_replace("to_","",$fullName);
						//$name=str_replace("to_","",$name);
					}

		//gLog("===> \ndop: $dop - full: $fullName filtro: ".$fld['name']."(".$fld['type'].") $name: $value  - ".$fld['database']);

					if (($fld['name']==$name) && ($fld['database']<>"no") && ($fld['database']<>"false") && ($value<>""))
					{
						switch ($fld['type'])
						{
							case 'text':
								if($op=="=")
									$flds[]="$fullName LIKE '%$value%'";
								else
									$flds[]="$fullName $op '$value'";
								break;
							case 'datetime':
								$flds[]="$fullName $op'".gDBDateTime($value)."$dop'";
								break;
							case 'date':
								if(gVar("database.engine") == "mysql")
									$flds[]="DATE($fullName) $op'".gDBDate($value)."$dop'";
								else
									$flds[]="$fullName $op'".gDBDate($value)."$dop'";
								break;
							case 'number':
								$flds[]="$fullName $op".gDBFloat($value)."";
								break;
							case 'checkbox':
								if ($value=="on")
									$flds[]="$fullName=1";
								else
									$flds[]="$fullName=0";
								break;
							case 'comboMultiSelection':
								if (is_numeric($value[0]))
									$flds[]="$fullName in (".implode(",",$value).")";

								break;
							default:
								$flds[]="$fullName='$value'";
						}
					}
					// Campos especiais: from_ e to_ para limitar entre uma faixa
					/*if (("from_".$fld['name']==$name) && ($value<>""))
					{
						$fullName=substr($fullName,5);
						switch ($fld['type'])
						{
							case 'number':
								$flds[]=$fullName.">=".gDBFloat($value)."";
								break;
							default:
								$flds[]=$fullName.">='$value'";
						}
					}
					if (("to_".$fld['name']==$name) && ($value<>""))
					{
						$fullName=substr($fullName,3);
						switch ($fld['type'])
						{
							case 'number':
								$flds[]=$fullName."<=".gDBFloat($value)."";
								break;
							default:
								$flds[]=$fullName."<='$value'";
						}
					}*/

				}
			}
			if (($where<>"") && (is_array($flds)))
			{
				$where.=" AND ";
			}
			$where.=implode(" AND ",$flds);
		}
		if ($where<>"")
			$where=" WHERE $where ";
		//gLog("============ $where");
		return($where);
	}













	/** Processa comandos enviados pela própria página
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $command Comando a ser processado
	 */
	function process()
	{
		global $http_system_img,$gSystemPathImg;
		global $http_img,$gPathImg;
		$retorno=false;
		$command=$_REQUEST['process'];
		$g=$_REQUEST['g'];
		$q=$_REQUEST['q'];
		$i=$_REQUEST['i'];
		$gId=$_REQUEST['gId'];
		$x=$_REQUEST['x'];
		$i=str_replace(",00","",$i);
		$this->actualQuery=intval($q);
		$processou=false;
		$sai="";
		$fields=gCleanField($_REQUEST);
		if ($command=="")
		{
			$query=$this->querys[$this->actualQuery];
			foreach ($fields as $campo=>$valor)
			{
				$achou=false;
				foreach ($query->fields as $campoBD)
				{
					if (($campo<>"id") && ($campoBD['name']==$campo))
					{
						$achou=true;
						break;
					}
				}
				if ($achou)
					$flds[]=$campo."=".$valor;
			}

			if ($achou)
			{
				if (intval($_REQUEST['gId'])>0)
					$command="formEditCommit";
				else
					$command="formNewCommit";
			}
		}

		// Deixando atributo parameters com valores obtidos
		$par="";
		$par['process']=$command;
		$par['query']=$q;
		$par['id']=$_REQUEST['gId']<>""?$_REQUEST['gId']:$_REQUEST['i'];
		$par['items']=$_REQUEST['itm'];
		$par['remote']=$_REQUEST['p'];
		$par['remoteField']=$_REQUEST['p'];
		$par['remoteValue']=$_REQUEST['v'];
		$this->parameters=$par;

		//gLog("AJAX.gPage:\t$command");



		// ----------------------------------------------------------


		if ($command=="link")
		{
			$this->onSelect($gId,"");
		}

		if ($command=="public")
		{
			$i=intval($i);
			if ((is_developer()) && ($i>0))
			{
				$this->loadQuerys();
				$query=$this->querys[$this->actualQuery];
				$sec=$query->sections;
				$sql="SELECT idd FROM ".$sec['from']." WHERE id=$i";
				$rst=gFastQuery($sql);
				$sql="UPDATE ".$sec['from']." SET idd=".$_SESSION['usrIdd']."-idd WHERE id=".$_REQUEST['gId'];
				gFastQuery($sql);

			}
		}



		// ----------------------------------------------------------


		if ($command=="remotecombo")
		{

			$par=$this->onBeforeRemoteCombo($_REQUEST['itm']);
			if (is_array($par))
			{
				$dados=jcombo2array($par['items']);

			} else
			{
				$dados=jcombo2array($par);
			}
			foreach ($dados as $el)
			{
				$el=str_replace("[","",$el);
				$el=str_replace("]","",$el);
				$els=explode("','",$el);
				$sai[]="{id: ".$els[0]."',text: '".$els[1]."}";
			}
			$dados=autoencode(implode(",",$sai));
			$sai="{rows: [".$dados."]}";
			$processou=true;
		}


		// ----------------------------------------------------------


		if (($command=="formEditCommit") && (isset($fields[$x."id"])) && ($fields[$x."id"]==''))
				 $command="formNewCommit";


		// ----------------------------------------------------------


		if ($command=="")
			return;



		// ----------------------------------------------------------


		if ($command=="formEdit")
		{
			$this->loadQuerys();
			$query=$this->querys[$this->actualQuery];
			$sec=$query->sections;
			$sql="SELECT ".$sec['select']." FROM ".$sec['from']." WHERE id=".intval($_REQUEST['gId']);
			$db=new gDB();
			$db->parseQueryOnServer($sql);
			//$this->querys="";
			$this->querys[$this->actualQuery]=$db;
			$json=$this->showPageParamsJson;
			$mtz=cssDecode($json);
			$mtz=cssRemove($mtz,'url');
			$mtz=cssRemove($mtz,'permissions');
			$mtz['url']=$this->page.'&process=formEditCommit&q='.$q;
			$mtz['standardSubmit']="true";
			$mtz=putQuotation($mtz);
			$json=cssEncode($mtz);
			parent::__construct($json);

			$this->showFormPage($json);
			$this->gBody();
			$processou=true;
		}


		// ----------------------------------------------------------


		if ($command=="formEditCommit")
		{
			$this->loadQuerys();
			$ajax=$_REQUEST['ajax'];
			//$fields=gCleanField($_REQUEST);
			//echo "<pre style='text-alignment: left'>";print_r($this->querys[0]);echo "</pre>";
			$fields=$this->onBeforeUpdate($fields);

			if (is_array($fields))
			{
				$query=$this->querys[$this->actualQuery];
				$sql="UPDATE ".$query->tables[0]['name']." SET ";
				$flds="";
				foreach ($fields as $campo=>$valor)
				{
					$s="";
					$achou=false;
					$campo=str_replace($x,"",$campo);
					foreach ($query->fields as $campoBD)
					{
						if (($campo<>"id") && ($campoBD['name']==$campo))
						{
							$achou=true;
							if ($campoBD['type']=="memo")
							{
								$valor="'".$_REQUEST[$campo]."'";
							} elseif ($campoBD['type']=="date")
							{
								$valor="'".gDBDate($valor)."'";
							} elseif ($campoBD['type']=="datetime")
							{
								$valor="'".str_replace("T"," ",substr($valor,0,19))."'";
							} elseif ($campoBD['type']=="checkbox")
							{
								$valor="'".gDBCheck($valor)."'";
							} else
								$valor=$this->repairValue($valor, $campoBD['type']);
							if ((substr(strtolower($campo),0,3)=="id_") && (intval($valor)==0))
								$achou=false;
//						gLog("==== campo: $campo (".$campoBD['type'].")= $valor");
							break;
						}
					}
					if ($achou)
						$flds[]=$campo."=".$valor;
				}
				$sql.=implode(", ",$flds);
				$id=$fields['id'];
				$sql.=" WHERE id=".intval($id);

				$query->run($sql);
				$sai="";
				$newFields="";
				foreach ($fields as $key=>$value)
				{
					$newFields[str_replace("gfrm0_","",$key)]=$value;
				}
				if (intval($ajax)==0)
				{
					$this->onAfterUpdate($newFields,true);
				} else
				{
					$sai=$this->onAfterUpdate($newFields,false);
					if ($sai=="")
						$sai="{success: true}";
					else
						$sai=str_replace(";",",",$sai);
				}
			} else
			{
				$sai="{success: false}";
			}
			$processou=true;
		}
		if ($command=="formNew")
		{
			$this->loadQuerys();
			/*
			$query=$this->querys[$this->actualQuery];
			$sql="INSERT INTO ".$query->tables[0]['name']." (id) VALUES (0)";
			$query->run($sql);
			$this->onAfterAdd($fields);
			$sai="{success: true}";
			$processou=true;
			 */
			$query=$this->querys[$this->actualQuery];
			$sec=$query->sections;
			$sql="SELECT ".$sec['select']." FROM ".$sec['from']." WHERE id=0";
			$db=new gDB();
			$db->parseQueryOnServer($sql,false);
			//$this->querys="";
			$this->querys[$this->actualQuery]=$db;
			$json=$this->showPageParamsJson;
			$mtz=cssDecode($json);
			$mtz=cssRemove($mtz,'url');
			$mtz=cssRemove($mtz,'permissions');
			$mtz['url']=$this->page.'&process=formNewCommit&i='.$i."&q=".$this->actualQuery;
			$mtz['standardSubmit']="true";
			$mtz=putQuotation($mtz);

			$json=cssEncode($mtz);
			parent::__construct($json);
			$this->showFormPage($json);
			$this->gBody();
			$processou=true;

		}


		// ----------------------------------------------------------


		if ($command=="formNewCommit")
		{

			$this->loadQuerys();
			//echo "<pre style='text-alignment: left'>";print_r($this->querys[$this->actualQuery]);echo "</pre>";exit;
			//echo "<pre>";print_r($_REQUEST);exit;
			//$fields=gCleanField($_REQUEST);
			$fields=$this->onBeforeAdd($fields);
			if (!empty($fields))
			{
				$query=$this->querys[$this->actualQuery];
				$sql="INSERT INTO ".$query->tables[0]['name']." ";
				$flds="";
				// mudando filtro para masterdetail
				if ($i<>"")
				{
					$qry=$this->querys[0];
					$tab=$qry->tables[0]['name'];
					$fk="id_$tab";
				}
				foreach ($fields as $campo=>$valor)
				{
					$s="";
					$achou=false;
					$campo=str_replace($x,"",$campo);
					$temIdd=false;

					foreach ($query->fields as $campoBD)
					{
						if (($campoBD['name']==$campo) && (strtolower($campo)<>"id"))
						{
							$achou=true;
							if ($campoBD['type']=="memo")
							{
								$valor="'".$_REQUEST[$campo]."'";
							} elseif ($campoBD['type']=="date")
							{
								$valor="'".substr(gDBDate($valor),0,10)."'";
							} elseif ($campoBD['type']=="datetime")
							{
								$valor="'".str_replace("T"," ",substr($valor,0,19))."'";
							} else
							$valor=$this->repairValue($valor, $campoBD['type']);
							if ((substr(strtolower($campo),0,2)=="id") && ($valor==""))
								$valor="0";
						}
						if (($campoBD['name']==$campo) && (strtolower($campo)=="idd"))
							$valor=intval($_SESSION['usrIdd']);
						if (($campoBD['name']==$campo) && (strtolower($campo)==$fk))
							$valor=intval($i);
						//if ($campoBD['name']=='idd')
						//	$temIdd=true;
					}
					if ($achou)
					{
						$flds[]=$campo;
						$vals[]=$valor;
					}
				}
				if ($temIdd)
				{
					$flds[]="idd";
					$vals[]=intval($_SESSION['usrIdd']);
				}
				if ($fk<>"")
				{
					$flds[]=$fk;
					$vals[]=$i;
				}
				$sql.="(".implode(", ",$flds).") VALUES ";
				$sql.="(".implode(", ",$vals).")";
				//echo $sql;

				$query->run($sql);
				$sai="";
				$sec=$query->sections;
				$sql="SELECT id FROM ".$sec['from']." ORDER BY id desc";
				$rs=gQuery(gSQLLimit($sql,1));
				$fields['id']=$rs->fields['id'];
				$this->onAfterAdd($fields);
				$this->onAfterAdd2($fields);
				$processou=true;
			} else
			{
				$this->showAlert("Erro");
				$processou=false;
			}
			
		}
		if ($command=="gridDelete")
		{
			$this->loadQuerys();
			$query=$this->querys[$this->actualQuery];
			// Obtendo total de registros...
			//$tab=$query->tables[0]["alias"];
			$w="";
			// mudando filtro para masterdetail
			if ($i<>"")
			{
				$qry=$this->querys[0];
				$tab=$qry->tables[0]['name'];
				$fk="id_$tab";
				$w=$fk."=$i AND ";
			}
			$id=intval(str_replace(",",".",$_REQUEST['id']));
			$par['table']=$query->tables[0]['name'];
			$par['id']=$id;
			if ($this->onBeforeDelete($par))
			{
				$sql="DELETE FROM ".$query->tables[0]['name']." WHERE $w id=".$id;
				$query->run($sql);
				$sai="{success: true}";
			} else
				$sai="{success: false}";
			//$this->onAfterDelete($param);

			$processou=true;
		}


		// ----------------------------------------------------------


		if ($command=="gridData")
		{
			$this->loadQuerys(false);
			$win=$_REQUEST['w'];
			$gwId=intval(str_replace(",00","",$_REQUEST['gwId']));
			if (($this->actualQuery==0) && (count($this->gridQuerys)>0))
				$query=$this->gridQuerys[$this->actualQuery];
			else
				$query=$this->querys[$this->actualQuery];
			// mudando filtro para masterdetail
			if ($this->actualQuery>0)
			{
				$qry=$this->querys[0];
				$tab=$qry->tables[0]['name'];
				$fk="id_$tab";
				//$fk=$this->querys[0][
				$w=$query->sections['where'];
				if ($w<>"")
					$w=$fk."=$i and $w";
				else
					$w=$fk."=$i";
				$query->sections['where']=$w;
			}
			$tab=$query->tables[0]["alias"];
			// Obtendo registros...
			$sql="SELECT ".$query->sections['select']." FROM ".$query->sections['from'];
			$gStart=1;
			$qLimit=1000000;
			$qCnt=0;

			if ($gwId==0)
			{
				$sql.=$this->addFilterOnWhere($query->sections['where']);
				if ($query->sections['group by']<>"")
					$sql.=" GROUP BY ".$query->sections['group by'];
				if ($query->sections['order by']<>"")
					$sql.=" ORDER BY ".$query->sections['order by'];
				if ($query->sections['having']<>"")
					$sql.=" HAVING ".$query->sections['having'];
				//echo $sql;
				/*
				$rs=$query->runLimit($sql,$_REQUEST['start'],$_REQUEST['limit'],0);
				$rsTmp=$query->run($sql);
				$ttl=$rsTmp->RecordCount();
				*/
				$rs=$query->run($sql);
				$qStart=intval($_REQUEST['start']);
				$qLimit=$qStart+intval($_REQUEST['limit']);
				//gLog("---- buscando dados de $qStart a $qLimit");
				$ttl=$rs->RecordCount();
				if ($qStart>1)
					while ($qCnt<$qStart)
					{
						$qCnt++;
						$rs->MoveNext();
					}
			} else
			{
				$sql.=" WHERE $tab.id=$gwId";
				$rs=$query->run($sql);
			}
			while ((!$rs->EOF) && ($qCnt<$qLimit))
			{
				$fields=$rs->fields;
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
								$dArray=$dictObj->arrays;
								foreach ($dict as $dkey=>$d)
								{
									if ($key==$dkey)
									{
										if ($d['type']<>"") $type=$d['type'];
										$size=$d['size'];
										$path=$d['path'];
										if ($d['calculated']=='true')
										{
											$v=$this->onBeforeCalculate($key,$fields);
											$rs->fields[$key]=$v;
											$value=$v;
										} elseif ($d['type']=="combo")
										{
											if ($dArray[$dkey]<>"")
											{
												$dados=jcombo2array($dArray[$dkey],true,$value);
											}
											else
											{
												$dados=jcombo2array($d['items'],true,$value);
											}
											//echo "Dados: ";print_r($d['items']);echo "\n = $value";exit;
											foreach ($dados as $dadosEl)
											{
												$dadosEl=substr($dadosEl,2);
												$dadosEl=substr($dadosEl,0,strlen($dadosEl)-2);
												$el=explode("','",$dadosEl);
												if ((intval($el[0])==intval($value)) || ($el[1]==$value))
												{

													if ($win=="1")
														$value=$el[0];
													else
														$value=$el[1];
//					gLog("===>>>win $win - value: $value");
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
						if ($win<>"1")
						{
							if (($type=='icon') || ($type=='image'))
							{
								$value=$this->gImage("{url: $size/$value}");
								$rs->fields[$key]=$value;
							} else
							{
								switch ($type)
								{
									case 'datetime':
										$rs->fields[$key]=gDateTime($value);
										break;
									case 'memo':
										$rs->fields[$key]=gCleanField(nl2br($value));
										break;
									case 'textarea':
										$rs->fields[$key]=gCleanField(nl2br($value));
										break;
									case 'date':
										$rs->fields[$key]=gDate($value);
										break;
									case 'number':
										$rs->fields[$key]=gFloat($value);
										break;
									case 'checkbox':
										if ($value==1)
											//$rs->fields[$key]="&diams;";
											$rs->fields[$key]=gT("sim");
										else
											//$rs->fields[$key]="&loz;";
											$rs->fields[$key]=gT("nao");
										break;
									case 'password':
										$rs->fields[$key]="******";
										break;
									case 'cpf':
										if ($value<>"")
											$rs->fields[$key]=substr($value,0,3).".".substr($value,3,3).".".substr($value,6,3)."-".substr($value,-2);
										break;
									default:
										$rs->fields[$key]=autoencode($value);
								}
							}
							if (($type=="hidden") || ($type=="exclude"))
								unset($rs->fields[$key]);
							if ($key=='id')
							{
								$rs->fields[$key]=str_replace(",00","",$value);
							}
							if ($key=='idd')
							{
								if ($value==0)
									$rs->fields[$key]=gT("Sim");
								else
									$rs->fields[$key]=gT("Não");
							}
						} else
						{
							if ($type=='date')
							{
								if ($value=="0000-00-00")
									$rs->fields[$key]="";
								else
									$rs->fields[$key]=gDate($value);
							}
						}
					} else
					{
						// excluir elemento com chave numerica
						unset($rs->fields[$key]);
					}
				}
				if ($x<>"")
				{
					foreach ($rs->fields as $k=>$v)
					{
						if (!is_numeric($k))
						$rsf["$x$k"]=trim($v);
//gLog("==== $x$k = $v");
					}
					$rsf=$this->onBeforeShowGridFields($rsf);
				} else
				{
					$rsf=$this->onBeforeShowGridFields($rs->fields);
				}

				$arr[]=$rsf;
				$qCnt++;
				$rs->MoveNext();
			}

			if ($this->retornoNaTela)
			{
				$rows=json_encode($arr);
				if ($gwId==0)
				{
					if ($rows=="null")
						$sai.="{success: true, totalCount: $ttl, rows: []}";
					else
						$sai.="{success: true, totalCount: $ttl, rows: $rows}";
				} else
				{
					$sai="{success: true, data: $rows}";
					$sai=str_replace("[","",$sai);
					$sai=str_replace("]","",$sai);
					$sai=str_replace("{\"","{",$sai);
					$sai=str_replace(",\"",",",$sai);
					$sai=str_replace("\":",":",$sai);
					//$sai=str_replace("_",'\_',$sai);
				}
				$processou=true;
			} else
			{
				$sai=$arr;
			}


		}
		//gLog("\n\n\n".$sai."\n\n\n");
		if ($this->retornoNaTela)
		{
			echo $sai;
			$this->processed=$processou;
		}
		else
		{
			$processou=$sai;
		}
		return ($processou);
	}

	function addStore($store)
	{
		$this->stores[]=$store;
	}

	function addJavascript($js)
	{
		$this->js.=$js;
	}

	function addListener($json,$par="")
	{
		$mtz=cssDecode($json);
		if ($mtz['action']=='filter')
		{
			if (isset($mtz['field']))
				$field=$mtz['field'];
			else
				$field=$mtz['name'];
			$fprefix.="combo"."@__prefix";
			$fromEl=$fprefix.$mtz['name'];
			$toEl=$fprefix.$mtz['source'];
			$event=$mtz['event'];
			//gLog("===:: event: ".$mtz['event']." remote: ".$mtz['remote']." from: $fromEl to: $toEl");
			if ($mtz['remote']=='true')
			{
				$func="


				var toEl=Ext.getCmp('$toEl');
				var fromEl=Ext.getCmp('$fromEl');
				var el=toEl.store.getAt(toEl.selectedIndex).id;
				fromEl.store.setBaseParam('p',el);
				fromEl.store.reload();
				";
				if ($par<>"")
					$func=$par;
				$j="
				listeners: {
					'$event':
					{
						scope: this,
						fn:function(combo, value)
						{
							$func
						}
					}
				}
";
			} else
			{
				if ($mtz['event']=='blur')
				{
					if ($mtz['target']<>'')
						$toEl=$fprefix.$mtz['target'];
					$func="
									var cmb= Ext.getCmp('$toEl');
									var vl=combo.getValue();
									if (vl=='')
									{
										Ext.Msg.alert('".gT("Atenção")."','".gT("É necessário definir valor para este campo")."');
										combo.focus();
									}
									else
									{
										var cmb= Ext.getCmp('$toEl');
										var ds = cmb.store;
										ds.snapshot = ds.realSnapshot;
										delete ds.realSnapshot;
										ds.clearFilter();
										ds.filter('$field', vl);
										ds.realSnapshot = ds.snapshot;
										ds.snapshot = ds.data;
									}
					";
					if ($par<>"")
						$func=$par;
					$j="
					listeners: {
						'$event':
							{
								scope: this,
								fn:function(combo, value)
								{
									$func
								}
							}
					}
					";
				} elseif ($mtz['event']=='focus')
				{
					if ($mtz['source']<>'')
						$fromEl=$fprefix.$mtz['source'];
					$func="
									var cmb= Ext.getCmp('$fromEl');
									var vl=cmb.getValue();
									if (vl=='')
									{
										Ext.Msg.alert('".gT("Atenção")."','".gT("É necessário definir valor para outro campo primeiro!")."');
										cmb.focus();
									}
									else
									{
										var ds = combo.store;
										ds.snapshot = ds.realSnapshot;
										delete ds.realSnapshot;
										ds.clearFilter();
										ds.filter('$field', vl);
										ds.realSnapshot = ds.snapshot;
										ds.snapshot = ds.data;
									}
					";
					if ($par<>"")
						$func=$par;
					$j="
					listeners: {
						'$event':
							{
								scope: this,
								fn:function(combo, value)
								{
									$func
								}
							}
					}
					";
				}

			}
			$this->listeners[$mtz['name']]=$j;

		}



		if ($mtz['action']=='get')
		{
			if (isset($mtz['field']))
				$field=$mtz['field'];
			else
				$field=$mtz['name'];
			$fprefix.="combo@__prefix";
			$fromEl=$fprefix.$mtz['source'];
			$toEl=$fprefix.$mtz['name'];
			$event=$mtz['event'];
			if ($mtz['remote']=='true')
			{
				$func="
								var fromEl=Ext.getCmp('$fromEl');
								var el=fromEl.getValue();
								combo.store.setBaseParam('p','".$mtz['source']."');
								combo.store.setBaseParam('v',el);
								combo.store.reload();
				";
				if ($par<>"")
					$func=$par;
				$j="
				listeners: {
					'$event':
						{
							scope: this,
							fn:function(combo, value)
							{ $func
							}
						}
				}
";
			} else
			{
				$func="
				var toEl=Ext.getCmp('$toEl');
				var fromEl=Ext.getCmp('$fromEl');
				if (fromEl==undefined)
					var fromEl=Ext.getCmp('combo$fromEl');
				if (toEl==undefined)
					var toEl=Ext.getCmp('combo$toEl');
				toEl.setValue(fromEl.getValue());
";
				if ($par<>"")
					$func=$par;
				$j="
				listeners: {
					'$event':
						{
							scope: this,
							fn:function()
							{
								$func
							}
						}
				}
";

			}
			$this->listeners[$mtz['name']]=$j;

		}

		return ($j);
	}

	function addDictionary($dict)
	{
		if ($dict<>"")
		{
			$this->dictionarys[]=$dict;
		}
	}

	function addFilter($filter)
	{
		if ($filter<>"")
		{
			$this->filters[]=$filter;
		}
	}

	function addParameters($parm)
	{
		if ($parm<>"")
			$this->linkParameters[]=$parm;
	}

	/** Adiciona consulta ao banco de dados à página
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $sql Query SQL
	 */
	function addQuery($sql,$parm="")
	{
		$this->bQuerys[]=array($sql,$parm);
	}

	/** Adiciona consulta ao banco de dados à página somente para o Grid
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $sql Query SQL
	 */
	function addGridQuery($sql)
	{
		$this->bGridQuerys[]=$sql;
	}

	function loadQuerys($needData=true)
	{
		//gLog("====>>>>> =====>>>>> loadQuerys - INICIO");
		foreach ($this->bQuerys as $d)
		{
			$sql=$d[0];
			$parm=$d[1];
			$db=new gDB();
			$db->parseQueryOnServer($sql,$needData);
			$this->querys[]=$db;
			if ($parm<>"")
			{
				$mtz=cssDecode($parm);
				$this->querysTitles[]=$mtz['title'];
				if (isset($mtz['permissions']))
				{
					$this->querysPermissions[]=$mtz['permissions'];
					if (count($this->querysPermissions)==1)
					{
						// se for masterdetail, substitui UpdateNoGrid por UpdateNoFormulario
						$this->querysPermissions[0]=str_replace('U','F',$this->querysPermissions[0]);
					}
				}
			} else
			{
					$this->querysTitles[]="";
					$this->querysPermissions[]="";
			}
		}
		foreach ($this->bGridQuerys as $sql)
		{
			$db=new gDB();
			$db->parseQueryOnServer($sql,$needData);
			$this->gridQuerys[]=$db;
		}
		//gLog("====>>>>> =====>>>>> loadQuerys - FINAL");
	}

	/** Monta uma tabela (grid)
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $name Nome do objeto
	 * @param string $param JSON ou matriz com parâmetros
	 * @param string $items JSON ou matriz com items
	 * @return mixed $sai
	 */
	function grid($json)
	{
	}


	function showGridPage($json)
	{

	}

	function tabFilter($flds,&$frm)
	{
			$filters=$flds->get();
			$arrays=$flds->getArrays();
			//echo "Arrays:<pre>";print_r($arrays);exit;
			foreach ($filters as $key=>$fld)
			{
				if (isset($fld['items']))
				{
					if (is_array($fld['items']))
						$fld['items']=$fld['items'][0];
				}
				if (is_array($arrays[$key]))
					$frm->add(cssEncode($fld),$arrays[$key]);
				else
					$frm->add(cssEncode($fld));
			}
			$mtz=jsonDecode($json);
			if ($mtz['columns']=="")
				$json=jsonMerge($mtz,"{columns: ".$this->defaultColumns."}",";",";");
			return($frm);
	}

	function tabForm($flds,&$frm,$showPublic=false)
	{
			$fprefix="";
			if (($this->enabledPermissions['gridupdate']) || ($this->enabledPermissions['windowupdate']))
				$fprefix="gfrm".$this->actualQuery."_";
			$qry=$this->querys[0];
			$tab=$qry->tables[0]['name'];
			$fk=$fprefix."id_$tab";
			$flds->fields=$this->onBeforeShowFields($flds->fields);

				//echo "<pre>";var_dump($flds->fields);
			foreach ($flds->fields as $fld)
			{

				$type=$fld['type'];
				$fieldLabel=$fld['fieldLabel'];
				$origName=$fld['name'];
				$name=$fprefix.$fld['name'];
				$title=gT($fld['title']);
				$maxLength=$fld['length'];
				$value=$fld['value'];
				$value=str_replace("‘","'",$value);
				$value=str_replace("“","\"",$value);

				$disabled="false";
				$readOnly='false';
				$extra="";
				if (($type=="date") && (substr($value,4,1)=="-"))
				{
					$value=gDate($value);
				}

				if ((($name<>$fprefix."idd")||is_developer()) && ($name<>$fk))
				{
					$extraItems="";
					if ($name==$this->id)
					{
						//$type="show";
						if (intval($value)==0)
							$type="hidden";
						$readOnly='true';
					}

					$temDicionario=false;
					// Caso exista(m) dicionário(s), usa...
					if (is_array($this->dictionarys))
					{
						$dictFld="";
						foreach ($this->dictionarys as $dictObj)
						{
							$dict=$dictObj->get();
							$dArray=$dictObj->arrays;
							if ($dictFld=="")
							{
								foreach ($dict as $key=>$parm)
								{
									//gLog("======= comparando: $name = ".$fprefix.$key);
									if ($name==$fprefix.$key)
									{
										//echo "=> encontrado: $name = $fprefix.$key <br>";

										$temDicionario=true;
										if (($_REQUEST['process']=="formNew") && ($parm['value']<>""))
											$value=$parm['value'];
										$parm['value']=$value;
										$parm['name']=$name;
										if ($dArray[$key]<>"")
										{
											$extraItems=$dArray[$key];
										} else
										{
											if (isset($parm['items']))
											{
												if (is_array($parm['items']))
													$parm['items']=$parm['items'][0];
											}
											//gLog("===A> ".$parm['validate']);
											if (strtolower(substr($parm['items'],0,7))=="select ")
												$parm['items']=str_replace("'","\"",$parm['items']);
											//gLog("===D> ".$parm['items']);
										}
										$dictFld=$parm;
										break;
									}
								}
							}
						}
					}
					if ($name==$fprefix."idd")
					{
						$readOnly='true';
						if (!$showPublic)
							$type="exclude";
						//$value=($value==0?"on":"off");
						$parm['fieldLabel']=gT("Público");
						$fieldLabel=gT("Público");
					}
					if ($type=='checkbox')
					{
						if (intval($value)==1)
							$extra.='; checked: true';
						else
							$extra.='; checked: false';
					}
					if ($type=='numeric')
					{
						$value=gFloat($value);
					}
					if (($type=='textarea') || ($type=='memo'))
					{
						// TODO: Tem que ser feito um tratamento em $value para remover caracteres inválidos que podem atrapalhar o cssDecode
						$extra='';
						$value="--(encode)".base64_encode($value);
						//$value=base64_encode($value);
					}
					if (strtolower($name)==$fprefix."id")
					{
						$readOnly=true;
					}
					$listener="";
					if (isset($this->listeners[$origName]))
					{
						$listener=$this->listeners[$origName];
						$listener=str_replace("@__prefix",$fprefix,$listener);
					}


					if ($temDicionario)
					{

						//if (($dictFld['type']<>"exclude") && ($dictFld['type']<>"hidden"))
						if (($dictFld['type']<>"exclude") )
						{
							$dictFld=cssEncode($dictFld);

						//gLog("====json $dictFld");
							$frm->add($dictFld,$extraItems,$listener);
						}
					} else
					{
						if ($value<>"")
							$frm->add("{type: $type; fieldLabel: $fieldLabel; name: $name; maxLength: $maxLength; disabled: $disabled; readOnly: $readOnly; value: '$value' $extra}",$extraItems,$listener);
						else
							$frm->add("{type: $type; fieldLabel: $fieldLabel; name: $name; maxLength: $maxLength; disabled: $disabled; readOnly: $readOnly; $extra}",$extraItems,$listener);
					}

				}
			}
			extjsVTypes($frm->extjsVTypes);
			$frm->extjsVTypes="";

			// TODO: acho que o codigo abaixo é inútil...
			$mtz=jsonDecode($json);
			if ($mtz['columns']=="")
				$json=jsonMerge($mtz,"{columns: ".$this->defaultColumns."}",";",";");


			$sai=$frm;
			return($sai);
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
						$frms->add(gT("Informações"),$this->tabHtml);
					}
					for ($a=1; $a<count($this->querys); $a++)
					{
						$this->actualQuery++;
						$this->enabledPermissions=$this->calcPermissions($this->querysPermissions[$a]);
						$tjson="{name: 'tabGrid$a'}";
						$this->showGridPage($tjson);
						$frms->add($this->querysTitles[$a],"tabGrid$a");
						/*
						$flds=$this->querys[$a];
						$json=jsonMerge($mtz,"{frame: false; columns: ".$this->defaultColumns."}",";",";");
						$frm=new gForm($json);
						$this->tabForm($flds,&$frm);
						$frms->add($this->names[$a],$frm->get(""));
						//$frms->add($this->names[$a],"{html: 'Teste $a'}");

						 */
					}
					//$extjsBuffer=$extjsB;
					$frms->render();
					extjsDo("tabForm".$this->name.".render(document.body);");
				}

			}
		}
	}

	function calcPermissions($perm)
	{
		global $gDatabasePermissions;
		$sai="";
		$sai['select']=false;
		$sai['insert']=false;
		$sai['delete']=false;
		$sai['link']=false;
		$sai['update']=false;
		$sai['gridupdate']=false;
		$sai['formupdate']=false;
		$sai['windowupdate']=false;
		if ((strpos($perm,"S")!==false) && (strpos($gDatabasePermissions,"S")!==false))
				$sai['select']=true;
		if ((strpos($perm,"I")!==false) && (strpos($gDatabasePermissions,"I")!==false))
				$sai['insert']=true;
		if ((strpos($perm,"D")!==false) && (strpos($gDatabasePermissions,"D")!==false))
				$sai['delete']=true;
		if ((strpos($perm,"L")!==false) && (strpos($gDatabasePermissions,"L")!==false))
				$sai['link']=true;
		if ((strpos($perm,"F")!==false) && (strpos($gDatabasePermissions,"U")!==false))
		{
			$sai['update']=true;
			$sai['formupdate']=true;
		}
		if ((strpos($perm,"W")!==false) && (strpos($gDatabasePermissions,"U")!==false))
		{
			$sai['update']=true;
			$sai['windowupdate']=true;
		}
		if ((strpos($perm,"U")!==false) && (strpos($gDatabasePermissions,"U")!==false))
		{
			$sai['update']=true;
			$sai['gridupdate']=true;
		}
		return($sai);
	}

	function showPage($json="")
	{
		$this->showPageParamsJson=$json;
		if (count($this->bQuerys)>1)
		{
			$this->masterdetail=true;
		} else
		{
			$this->masterdetail=false;

		}
		$mtz=cssDecode($json);
		$pageStyle=$mtz['style'];
		$perm=$mtz['permissions'];
		$mtz['title']=gT($mtz['title']);
		$this->formHtml=$mtz['formHtml'];
		$this->gridHtml=$mtz['gridHtml'];
		$this->tabHtml=$mtz['tabHtml'];
		$mtz=cssRemove($mtz,'permissions');
		$mtz=cssRemove($mtz,'style');
		$mtz=cssRemove($mtz,'formHtml');
		$mtz=cssRemove($mtz,'gridHtml');
		$mtz=cssRemove($mtz,'tabHtml');
		$mtz=putQuotation($mtz);
		$this->showPageParams=$mtz;
		$json=cssEncode($mtz);
		//echo "<pre>";var_dump($this->bQuerys);echo "\n\n".$this->masterdetail;exit;
		if (!$this->process())
		{
			$this->loadQuerys();
			parent::__construct($json);
			if (count ($this->bQuerys)>1)
			{
				$this->permissions=$this->querysPermissions[0];
			} else
			{
				$this->permissions=$perm<>""?$perm:$this->permissions;
			}
			$this->enabledPermissions=$this->calcPermissions($this->permissions);

			if (strpos($this->permissions,"S")!==false)
			{

				if (strpos($this->permissions,"F")!==false)
				{
					$this->enabledPermissions['update']=true;
					$this->enabledPermissions['formupdate']=true;
				}
				if (strpos($this->permissions,"W")!==false)
				{
					$this->enabledPermissions['update']=true;
					$this->enabledPermissions['windowupdate']=true;
				}
				$this->actualQuery=0;
				$this->showGridPage($json);

			} else
			{
				$this->showFormPage($json);
			}
			$this->gBegin();
			$this->gEnd();
		}
	}

	function parentConstruct()
	{
		parent::__construct();
	}

	function __destruct()
	{
		if (!$this->processed)
		{
			parent::__destruct();
		}
	}
}

$device=$gDevice;
if ((($gOs=="ios")||($gOs=="android")) && ($gDevice=="mobile"))
	$device="iphone";
$inc=$gPathDefault."dev".gBAR.strtolower($device).gBAR."gPage.php";


if (file_exists($inc))
{
	include_once $inc;
} else
{
	$out=new g_Output();
	$out->gError("Erro","dispositivo de acesso ao sistema não encontrado: <br>inc: $inc<br>$device");
}

?>
