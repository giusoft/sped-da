<?

define( "gUI_FILTER",   0 );
define( "gUI_GRID",     1 );
define( "gUI_FORM",     2 );
define( "gUI_SAVE",     3 );
define( "gUI_DELETE",   4 );
define( "gUI_ACTIVATE", 5 );

class gUICore
{
	public $json;
	public $jarr;

	public $queries;
	public $filters;
	public $db;

	public $dictionaries;
	public $fields;
	public $rowButtons;
	public $ownButtons;
	public $combos;
	public $showOk=true;
	public $showFields;
	public $hideFields;

	public $tableMaster;
	public $tableName;
	public $tableFields;
	public $tableSql;
	public $tableSections;
	public $tableTables;
	public $tables;
	public $totalFields;
	public $conditionals;
	public $gridQuery='';
	public $isMaster=false;

	/**
	 * Evento a ser executado antes de adicionar registro no banco
	 *
	 * @param  $params array associativo de itens a serem adicionados
	 * @return  array associativo de itens a serem adicionados ou string vazia caso deseje cancelar a inserção
	 */
	function onBeforeAdd($params = "")
	{
		return $params;
	}

	/**
	 * Evento a ser executado após adicionar registro no banco
	 *
	 * @param int $params id do registro novo
	 * @return bool
	 */
	function onAfterAdd($params = "")
	{
		return true;
	}

	/**
	 * Evento a ser executado antes de atualizar registro no banco
	 *
	 * @param  $params array associativo de itens a serem alterados
	 * @return  array associativo de itens a serem alterados ou string vazia caso deseje cancelar a atualização
	 */
	function onBeforeUpdate($params = "")
	{
		return $params;
	}

	/**
	 * Evento a ser executado após atualizar registro no banco
	 *
	 * @param int $params id do registro atualizado
	 * @return bool
	 */
	function onAfterUpdate($params = "")
	{
		return true;
	}

	/**
	 * Evento a ser executado antes de apagar registro no banco
	 *
	 * @param string $params
	 * @return bool
	 */
	function onBeforeDelete($params = "")
	{
		return true;
	}

	/**
	 * Evento a ser executado após apagar registro no banco
	 *
	 * @param string $params
	 * @return bool
	 */
	function onAfterDelete($params = "")
	{
		return true;
	}

	/**
	 * Força a ocultação de campos específicos da tabela. Anula o método "hide"
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 * @param string $name Nome do campo a ser ocultado
	 */
	function hide($name)
	{
		$this->hideFields[$name]=true;
	}

	/**
	 * Força a exibição de campos específicos da tabela. Anula o método "hide"
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 * @param string $name Nome do campo a ser exibido
	 */
	function show($name)
	{
		$this->showFields[$name]=true;
	}

	/**
	 * Adiciona um botão que atua sobre todo o conteúdo
	 *
	 * @param string $json Parâmetros
	 *
	 * @author Giuliano
	 * @version 1.0 14/04/15 10:26
	 *
	 */
	function addButton($json)
	{
		$this->ownButtons[]=$json;
	}

	/**
	 * Adiciona um botão que será mostrado a cada registro da tabela
	 * @author	Giuliano Nascimento
	 * @param string $json
	 *                 title: Nome do campo que terá filtro
	 *                 style: Estilo bootstrap do botão
	 *                 icon: Ícone
	 *                 gPage: página a saltar quando clicado
	 *
	 * @version	4.0 29/07/2014 10:16
	 */
	function addRowButton($json)
	{
		$jarr=cssDecode($json);
		$this->rowButtons[]=$jarr;
	}

	/**
	 * Obtém valores para exibição dos campos do tipo "Combo" ou para apresentação na tabela inicial.
	 * Tem por objetivo criar um cache para acesso rápido aos valores
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 * @param string $name Nome do campo que utiliza estes registros
	 * @param string $sql Query para busca de valores em outra tabela
	 */
	function getQueryData($name, $sql)
	{
		global $sp;

		// if (stripos($sql,"SELECT ")===false)
		// {
		// 	if ($sp[$sql]<>'')
		// 	{
		// 		$sql=$sp[$sql];
		// 	} else
		// 	{
		// 		if ($_SESSION['gFW4']<>'') // compabibilidade com gFW3
		// 		{
		// 			$sql=sp($sql);
		// 			if ($sql=="")
		// 				$sql="SELECT * FROM $sql";
		// 		} else
		// 		{
		// 			$sql="SELECT * FROM $sql";
		// 		}
		// 	}
		// }
		// $rs=dbQuery($sql);
		// foreach ($rs as $row)
		// {
		// 	$this->combos[$name][$row['id']]=$row[1];
		// }
		$this->combos[$name]=jcombo2array($sql);
	}

	/**
	 * Adiciona um campo para filtragem na exibição da tabela com os registros
	 * @author	Giuliano Nascimento
	 * @param string $json
	 *                 name: Nome do campo que terá filtro
	 *                 value: Valor padrão
	 *                 allowBlank: Permite ou não valor em branco (padrão: permite)
	 * @version	4.0 29/07/2014 10:16
	 */
	function addFilter($json)
	{
		if (is_object($json))
		{
			// Compatibilidade com gFW3
			$flt=$json->get();

			foreach ($flt as $filtro){
				if($filtro['operator']=="range")
				{
					$jarr1=$filtro;
					$jarr2=$filtro;
					$jarr1['name']=$jarr1['name'].'__FROM';
					$jarr2['name']=$jarr2['name'].'__TO';

					$this->filters[$jarr1['name']]=$jarr1;
					$this->filters[$jarr2['name']]=$jarr2;
				}
				else
					$this->filters[$filtro['name']]=$filtro;
			}

		} else
		{
			if (strpos($json,"{")!==false)
			{
				$jarr=cssDecode($json);
				if ($jarr['operator']=='range')
				{
					$jarr1=$jarr;
					$jarr2=$jarr;
					$jarr1['name']=$jarr1['name'].'__FROM';
					$jarr2['name']=$jarr2['name'].'__TO';
					$this->filters[$jarr1['name']]=$jarr1;
					$this->filters[$jarr2['name']]=$jarr2;
				} else
				{
					$this->filters[$jarr['name']]=$jarr;
				}
			} else
			{
				$this->filters[$json]=Array("name"=>$json);
			}
		}
		$this->filter=true;
	}

	/**
	 * Adiciona limit na query atual
	 * @author	Giuliano Nascimento
	 * @version	4.0 25/05/2015 10:16
	 * @param string $filter Filtro(s) para adicionar na cláusula WHERE
	 */
	function addLimitOnQuery($sql)
	{
		$page=0;
		if ($this->gCmd=='ajax')
		{
			$page=intval($this->gPage)-1;
		}
		$maxrows=gVar('database.maxrows');
		if (stripos($sql,"LIMIT ")!==false)
			$sql=substr($sql,0,stripos($sql,"LIMIT "));
		if (isset($_REQUEST['gMaxrows']))
		{
			if (intval($_REQUEST['gMaxrows'])>0)
			{
				$maxrows=intval($_REQUEST['gMaxrows']);
				$sql=gSQLLimit($sql,$maxrows,$page*intval(gVar('database.maxrows')));
			}
		} else
		{
			$sql=gSQLLimit($sql,$maxrows,$page*intval(gVar('database.maxrows')));
		}
		return($sql);
	}

	/**
	 * Adiciona um tratamento diferenciado para um campo, ignorando o que está no Banco de Dados
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 * @param string $json Utiliza o mesmo formato dos campos de formulário
	 */
	function addDictionary($json)
	{
		global $gDB;
		if (is_object($json))
		{
			// Compatibilidade com gFW3
			$dic=$json->get();
			foreach ($dic as $name=>$jarr)
			{
				$jarr['name']=$name;
				$this->dictionaries[]=$jarr;
				if (isset($this->tableFields[$jarr['name']]))
				{
					foreach ($jarr as $el => $value)
					{
						if ($el=="type")
						{
							$align=gCheckAlignByType($this->tableFields[$jarr['name']]['type']);
							$this->tableFields[$jarr['name']]['align']=$align;
							$gDB[$this->tableAlias]['fields'][$jarr['name']]['align']=$align;
							switch ($value)
							{
								case "combo":
								case "comboMultiSelection":
									$arr=$this->getQueryData($jarr['name'],$jarr['items']);
									break;
							}
						}
					}
				}
			}
		} else
		{
			$jarr=cssDecode($json);
			$this->dictionaries[]=$jarr;
			if (isset($this->tableFields[$jarr['name']]))
			{
				foreach ($jarr as $el => $value)
				{
					$this->tableFields[$jarr['name']][$el]=$value;
					$gDB[$this->tableAlias]['fields'][$jarr['name']][$el]=$value;
				}

				foreach ($jarr as $el => $value)
				{
					if ($el=="type")
					{
						$align=gCheckAlignByType($this->tableFields[$jarr['name']]['type']);
						$this->tableFields[$jarr['name']]['align']=$align;
						$gDB[$this->tableAlias]['fields'][$jarr['name']]['align']=$align;
						switch ($value)
						{
							case "combo":
							case "comboMultiSelection":
								$arr=$this->getQueryData($jarr['name'],$gDB[$this->tableAlias]['fields'][$jarr['name']]['items']);
								break;
						}
					}
				}
			}
		}
	}

	function parseDictionaries()
	{
		foreach ($this->dictionaries as $jarr)
		{
			if (isset($this->tableFields[$jarr['name']]))
			{
				foreach ($jarr as $el => $value)
				{
					$this->tableFields[$jarr['name']][$el]=$value;
					$gDB[$this->tableAlias]['fields'][$jarr['name']][$el]=$value;
				}

				foreach ($jarr as $el => $value)
				{
					if ($el=="type")
					{
						$align=gCheckAlignByType($this->tableFields[$jarr['name']]['type']);
						$this->tableFields[$jarr['name']]['align']=$align;
						$gDB[$this->tableAlias]['fields'][$jarr['name']]['align']=$align;
						switch ($value)
						{
							case "combo":
							case "comboMultiSelection":
								$arr=$this->getQueryData($jarr['name'],$gDB[$this->tableAlias]['fields'][$jarr['name']]['items']);
								break;
						}
					}
				}
			}
		}
	}


}

/**
 * Classe para exibição de tabelas, formulários e ações de inclusão, alteração e exclusão de registros
 * @package	gUI
 * @author	Giuliano Nascimento
 * @version	4.0 29/07/2014 10:16
 */
class gUI extends gUICore {

	public $select = false;
	public $insert = false;
	public $update = false;
	public $delete = false;
	public $active = false;

	public $gUI_FILTER	=0;
	public $gUI_GRID 		=1;
	public $gUI_FORM		=2;
	public $gUI_SAVE		=3;
	public $gUI_DELETE	=4;
	public $gUI_ACTIVATE	=5;

	public $gCmd;
	public $gPage;
	public $gJson;

	public $filter=false;


	public $sqlAllRecords;
	public $ttlRecords=0;
	public $formMessage='';
	public $fancybox=false;

	public $extraParm='';

	public $forceFilter='';

	/**
	 * Método construtor
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 */
	function __construct( $json = "" )
	{
		$this->gCmd = gCleanField($_REQUEST['gCmd']);
		$this->gPage = intval($_REQUEST['gPage']);
		$this->gJson = gCleanField($_REQUEST['gJson']);
		foreach ($_REQUEST as $key=>$value)
		{
			if ($key<>'g' && $key<>'gId' && $key<>'gPage' && $key<>'gCmd')
				$this->extraParm.="&".$key.'='.urlencode($value);
		}

		$this->json=$json;
		$this->jarr=cssDecode($json);
		$this->dontDuplicate = (int) $this->jarr['dontDuplicate'];
		$this->dontDuplicateColumns = $this->jarr['dontDuplicateColumns'];

		if ($this->jarr['showOk']=='false')
			$this->showOk=false;
		if (stripos($this->jarr['permissions'],'S')!==false)
			$this->select=true;
		if (stripos($this->jarr['permissions'],'I')!==false)
			$this->insert=true;
		if (stripos($this->jarr['permissions'],'U')!==false)
			$this->update=true;
		if (stripos($this->jarr['permissions'],'D')!==false)
			$this->delete=true;
		if (stripos($this->jarr['permissions'],'A')!==false)
			$this->active=true;
		$this->tableMaster=$this->jarr['table'];
		$this->setTableDefs($this->jarr['table']);
		$this->hideFields['idd']=true;
		$numFormat=gVar("global.numformat");
		if (!empty($this->jarr['gPage']))
		{
			$this->gUI_FILTER=intval($this->jarr['gPage']);
			$this->gUI_GRID=intval($this->jarr['gPage'])+1;
			$this->gUI_FORM=intval($this->jarr['gPage'])+2;
			$this->gUI_SAVE=intval($this->jarr['gPage'])+3;
			$this->gUI_DELETE=intval($this->jarr['gPage'])+4;
			$this->gUI_ACTIVATE=intval($this->jarr['gPage'])+5;
		}
	}

	/**
	 * Define informações auxiliares para uso da tabela informada
	 * @author	Giuliano Nascimento
	 * @version	4.0 14/08/2014 11:16
	 * @param string $name Nome da tabela
	 */
	function setTableDefs($name)
	{
		global $gDB;
		$this->tableName   = $name;
		$this->tableAlias	 = $name;
		if (isset($gDB[$this->tableName]))
		{
			$this->tableName=$gDB[$this->tableName]['tables'][0]['name'];
		}
		foreach ($this->tables as $table)
		{
			if ($table['name']==$this->tableAlias)
			{
				$this->select=false;
				$this->insert=false;
				$this->update=false;
				$this->delete=false;
				if (stripos($table['permissions'],'S')!==false)
					$this->select=true;
				if (stripos($table['permissions'],'I')!==false)
					$this->insert=true;
				if (stripos($table['permissions'],'U')!==false)
					$this->update=true;
				if (stripos($table['permissions'],'D')!==false)
					$this->delete=true;
				if (stripos($table['permissions'],'A')!==false)
					$this->active=true;
			}
		}
		$this->tableFields   = $gDB[$this->tableAlias]['fields'];
		$this->tableSql      = $gDB[$this->tableAlias]['sql'];
		$this->tableSections = $gDB[$this->tableAlias]['sections'];
		$this->tableTables	= $gDB[$this->tableAlias]['tables'];
	}


	/**
	 * Define uma query diferenciada pra mostrar no Grid
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 * @param string $name Nome da tabela
	 */
	function setGridQuery($sql)
	{
		$this->gridQuery=$sql;
	}

	/**
	 * Adiciona tabelas para relacionamento entre tabelas
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 * @param string $name Nome da tabela
	 */
	function addTable($name)
	{
		$this->tables[]=cssDecode($name);
	}

	/**
	 * Adiciona queries para realcionamento entre tabelas
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 * @param string $sql Query
	 */
	function addQuery($sql, $json="")
	{
		$name="";
		if (count($this->queries)==0)
		{
			$name=$this->parseQuery($name, $sql);
			$this->queries[]=$sql;
			$this->tableMaster=$name;
			$this->setTableDefs($name);
		} else
		{
			$jarr=cssDecode($json);
			if ($jarr['relationship']=="")
				$jarr['relationship']="one-to-many";
			if ($jarr['foreignKey']=="")
				$jarr['foreignKey']="id_".$this->tableMaster;
			$name=$this->parseQuery($name, $sql);
			$jarr['name']=$name;
			$this->queries[]=$sql;
			$this->tables[]=$jarr;
			$this->setTableDefs($this->tableMaster);
		}
	}

	/**
	 * Adiciona query para mostrar os dados no grid
	 * @author	Giuliano Nascimento
	 * @version	4.0 14/05/2015 10:16
	 * @param string $sql Query
	 */
	function addGridQuery($sql)
	{

	}

	/**
	 * Adiciona condições para processar as permissões em cada registro da tabela
	 * @author	Giuliano Nascimento
	 * @version	4.0 21/01/2015 12:29
	 * @param string $sql Query
	 */
	function addConditional($json)
	{
		$this->conditionals[]=cssDecode($json);
	}

	/**
	 * Adiciona campos que serão totalizados na exibição
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 * @param string $name Nome do campo a ser totalizado
	 */
	function addTotal($json)
	{
		$jarr=cssDecode($json);
		$this->totalFields[$jarr['name']]=$jarr;
	}

	/**
	 * Adiciona campos que serão agrupados na exibição
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 * @param string $name Nome do campo a ser agrupado
	 */
	function addGroup($json)
	{
		$jarr=cssDecode($json);
		$this->totalFields[$jarr['name']]=$jarr;
	}



	/**
	 * Adiciona uma mensagem no topo do formulário
	 * @author	Giuliano Nascimento
	 * @version	4.0 02/09/2015 10:16
	 * @param string $txt Texto
	 */
	function addFormMessage($txt)
	{
		$this->formMessage=$txt.'<hr>';
		if (strpos($txt,'fancybox')!==false)
		{
			$this->fancybox=true;
		}
	}


	/**
	 * Varre a query e identifica seções (campos, tabelas, etc)
	 * @param string $name Nome da tabela
	 * @param string $sql Query
	 */
	function parseQuery($name, $sql)
	{
		global $gDB;

		$db=new gDatabase();

		$describe = $db->describe( $sql );
		$sections = parseQuerySections( $sql );
		$tables = parseQueryTables( $sql );
		if ($name=="")
		{
			//$name="table".date("u").rand(1000,9999);
			$name=$sections['from'];
		}
		if (strpos($name,' ')!==false)
			$name=trim(substr($name,0,strpos($name,' ')));

		$gDB[$name]['fields']=$describe;
		$gDB[$name]['sections']=$sections;
		$gDB[$name]['tables']=$tables;
		$gDB[$name]['sql']=$sql;
		$this->tableTables = $tables;
		$this->tableName = $this->tableTables[0]['name'];
		$this->tableAlias = $this->tableTables[0]['alias'];
		$this->tableFields = $gDB[$name]['fields'];
		$this->tableSql = $sql;
		$this->tableSections = $gDB[$name]['sections'];
		return($name);
	}

	/**
	 * Adiciona filtros na query atual
	 * @author	Giuliano Nascimento
	 * @param string $filter Filtro(s) para adicionar na cláusula WHERE
	 * @version	4.0 29/07/2014 10:16
	 */
	function addFilterOnQuery($filter='')
	{
		global $usrIdd;
		$w='';
		if (isset($this->tableSections['where']))
		{
			$w[]="(".$this->tableSections['where'].")";
		}
		if ($filter=='')
		{
			//echo "<pre>";

			foreach ($this->filters as $fltKey=>$fltValue)
			{
				$filtro='';
				$aspasIni="'";
				$aspasFim="'";
				$op='=';
				$valor=$this->filters[$fltKey]['value'];

				if(((trim($valor)<>"") && (trim($valor) <> "0")) || (is_array($valor)) )
				{
					$origKey=$fltKey;
					$origKey=str_ireplace('__FROM','',$origKey);
					$origKey=str_ireplace('__TO','',$origKey);

					$origKey=str_ireplace('from__','',$origKey);
					$origKey=str_ireplace('to__','',$origKey);

					//if($this->filters[$fltKey]['table']<>"")
					$tableRef=$this->filters[$fltKey]['table']<>"" ? $this->filters[$fltKey]['table']."." : "";

					$tableRefPos=strpos($origKey,'__');
					if ($tableRefPos!==false)
					{
						$tableRef=substr($origKey,0,$tableRefPos).".";
						$origKey=substr($origKey,$tableRefPos+2);
					}

					switch ($this->tableFields[$origKey]['type'])
					{
						case 'textarea':
						case 'text':
							$op=' like ';
							$aspasIni="'%";
							$aspasFim="%'";
							break;
						case 'date':
							$origKey="DATE(".$origKey.")";
							$valor=gDBDate($valor);
							break;
						case 'dateTime':
							$valor=gDBDateTime($valor);
							break;
						case 'number':
							$valor=gDBFloat($valor);
							break;
						case 'checkbox':
							$valor=gDBCheck($valor);
							break;
						case 'number':
							$valor=gDBFloat($valor);
							break;
					}

					if ($this->filters[$fltKey]['operator']=='range' || ((substr($fltKey,0,5)=='from_') || (substr($fltKey,0,4)=='to__') ))
					{
						if (stripos($fltKey,'__FROM')!==false || substr($fltKey,0,6)=='from__')
						{
							$op='>=';
						}
						if (stripos($fltKey,'__TO')!==false || substr($fltKey,0,4)=='to__')
						{
							$op='<=';
						}
					}
					if($this->filters[$fltKey]['type']=="comboMultiSelection")
					{
						$filtro=$tableRef.$origKey." IN ('".implode("','",$valor)."') ";
					}
					else
						$filtro=$tableRef.$origKey.$op.$aspasIni.$valor.$aspasFim;

					$w[]=$filtro;
				}
			}
		} else
		{
			$w[]=$filter;
		}
		if (gVar("global.idd")=="true")
		{
			$w[]='('.$this->tableTables[0]['alias'].'.idd=0 OR '.$this->tableTables[0]['alias'].'.idd='.$usrIdd.')';
		}
		$where=implode(" and ", $w);
		$sql="SELECT ".$this->tableSections['select'].' ';
		$sql.="FROM ".$this->tableSections['from'].' ';
		if ($where<>"")
			$sql.="WHERE ".$where.' ';
		if (isset($this->tableSections['group by']))
			$sql.="GROUP BY ".$this->tableSections['group by'].' ';
		if (isset($this->tableSections['order by']))
			$sql.="ORDER BY ".$this->tableSections['order by'].' ';
		if ((isset($this->tableSections['limit'])) && (intval($this->tableSections['limit'])>0))
			$sql.="LIMIT ".$this->tableSections['limit'].' ';

		$this->sqlAllRecords=$sql;

		return($sql);
	}


	function getGridTable(&$o, $sql, $show, $gTable='',$extra='')
	{
		global $gPathFiles, $http_files;
		$html='';
		$aligns='';
		$aligns['left']='<-';
		$aligns['right']='->';
		$aligns['center']='<>';
		// Início da tabela
		$rs=dbQuery($sql);
		if (count($rs)>0)
		{
			// Cabeçalho da tabela
			$html.='<div id="gProcessing" style="xdisplay: none; xbackground-color: transparent">';
			$html.=$o->tableBegin('big', true, false, true);
			$mtz='';
			if (($this->update || $this->delete || $this->active || (count($this->rowButtons)>0)))
			{
				//$mtz[]='<-<div class="hidden-print hiddenOnPrint">'.gT("Opções").'</div>';
				$mtz[]='<-'.gT("Opções");
			}
			$temAtivo="";
			foreach ($this->tableFields as $key => $value)
			{
				if ($this->tableFields[$key]['type']<>'hidden')
				{
					if ($show[$key]===true)
					{
						if ($this->tableFields[$key]['format']=='number' || $this->tableFields[$key]['format']=='numeric' || $this->tableFields[$key]['format']=='integer')
							$mtz[]=$aligns['right'].$this->tableFields[$key]['fieldLabel'];
						else
						{
							$txt = $this->tableFields[$key]['fieldLabel'];
							if ($txt == "Descrição" || $txt == "Observação" || $txt == "Observações" || $txt == "Detalhes")
								$txt.="                              ";
							if ($txt == "Id" || $txt == "Nº")
								$txt="    ".$txt;
							$mtz[]=$aligns[$this->tableFields[$key]['align']].$txt;
						}
					}
					if (($key=="active") || ($key=="ativo"))
					{
						$temAtivo=$key;
					}
				}
			}
			$html.=$o->tableRow($mtz, "header");

			// Mostra registros da tabela
			$ttl=0;
//			echo "<pre>";
//			var_dump($show);
//			exit;

			foreach ($rs as $row)
			{
				$mtz='';
				// Botões
				$btns='';
				if ($this->update || $this->delete || $this->active )
				{
					if ($this->update)
					{
						$mostraBtn=true;
						if ($row['idd']==0 && $usrId>1)
							$mostraBtn=false;
						if (is_array($this->conditionals))
						{
							foreach ($this->conditionals as $cond)
							{
								if (isset($row[$cond['field']]) && $cond['permission']=='U')
								{
									$mostraBtn=false;
									$f=$cond['field'];
									$v=$cond['value'];
									switch ($cond['conditional'])
									{
										case 'equal':
											if ($row[$f]==$v)
												$mostraBtn=true;
											break;

										case 'notEqual':
											if ($row[$f]<>$v)
												$mostraBtn=true;
											break;
										case 'greater':
											if ($row[$f]>$v)
												$mostraBtn=true;
											break;

										case 'less':
											if ($row[$f]<$v)
												$mostraBtn=true;
											break;

									}
								}
							}
						}
						if ($mostraBtn)
							$btns.=$o->button("{style: primary; icon: pencil; hint: Editar; size: tiny; url: ".$o->page.$this->extraParm."&gPage=".$this->gUI_FORM."&gId=".$row['id']."$extra}");
						else
							$btns.='<span class="fa-stack"><i class="fa fa-pencil fa-rotate-270 fa-stack-1x"></i><i class="fa fa-ban fa-stack-2x text-danger"></i></span>';
					}
					if ($this->delete)
					{

						$mostraBtn=true;
						if ($row['idd']==0 && $usrId>1)
							$mostraBtn=false;
						if (is_array($this->conditionals))
						{
							foreach ($this->conditionals as $cond)
							{
								if (isset($row[$cond['field']]) && $cond['permission']=='D')
								{
									$mostraBtn=false;
									$f=$cond['field'];
									$v=$cond['value'];
									switch ($cond['conditional'])
									{
										case 'equal':
											if ($row[$f]==$v)
												$mostraBtn=true;
											break;

										case 'notEqual':
											if ($row[$f]<>$v)
												$mostraBtn=true;
											break;
										case 'greater':
											if ($row[$f]>$v)
												$mostraBtn=true;
											break;

										case 'less':
											if ($row[$f]<$v)
												$mostraBtn=true;
											break;

									}
								}
							}
						}
						if ($mostraBtn)
						{
							$btns.= $o->button("{style: danger; icon: trash; size: tiny; openModal: confirm".$this->tableAlias."}", "javascript:gId='" . $row['id'] . "'");
							//$btns.= '<a role="button" class="btn btn-danger btn-xs" data-toogle="popover" data-placement="right" data-html="true" data-content="Outro conteúdo"><i class="fa fa-trash fa-fw"></i></a>';
						} else
							$btns.='<span class="fa-stack"><i class="fa fa-trash fa-stack-1x"></i><i class="fa fa-ban fa-stack-2x text-danger"></i></span>';
					}
					if ($temAtivo<>"")
					{
						if ($row[$temAtivo]==1)
							$btns.= $o->button("{style: success; icon: thumbs-up; hint: Desativar; size: tiny; url: ".$o->page."&gPage=".$this->gUI_ACTIVATE.$extra."&gAct=".$temAtivo."&gId=".$row['id']."}");
						else
							$btns.= $o->button("{style: danger; icon: thumbs-down; hint: Ativar; size: tiny; url: ".$o->page."&gPage=".$this->gUI_ACTIVATE.$extra."&gAct=".$temAtivo."&gId=".$row['id']."}");
					}
				}
				$btns.=' ';
				if (is_array($this->rowButtons) && $this->isMaster)
				{
					foreach ($this->rowButtons as $btn)
					{
						$btn['href']=$o->page.'&gPage='.intval($btn['gPage']).'&gId='.$row['id'];
						if($btn['extra']<>"")
							$btn['href'].="&".$btn['extra'];
						$btn['size']='tiny';
						$mostraBtn=true;
						if ($btn['conditional']<>'' && $btn['field']<>'')
						{
							$mostraBtn=false;
							$f=$btn['field'];
							$v=$btn['value'];
							switch ($btn['conditional'])
							{
								case 'equal':
									if ($row[$f]==$v)
										$mostraBtn=true;
									break;

								case 'notEqual':
									if ($row[$f]<>$v)
										$mostraBtn=true;
									break;
								case 'greater':
									if ($row[$f]>$v)
										$mostraBtn=true;
									break;

								case 'less':
									if ($row[$f]<$v)
										$mostraBtn=true;
									break;
							}
						}
						if ($mostraBtn)
						{
							$btn=cssEncode($btn);
							$btns.=$o->button($btn);
						}
					}
				}
				if (trim($btns)<>'')
				{
					$btns='<-<div class="hiddenOnPrint">'.$btns.'</div>';
					$mtz[]=$btns;
				}
				// Mostra registro atual respeitando o alinhamento e o tipo de dados
				foreach ($row as $key=>$value)
				{
					if ((!is_numeric($key)) && ($this->tableFields[$key]['type']<>'hidden'))
					{
						$value=formatValueByType($key, $this->tableFields[$key]['type'], $value, $this->combos);
						if (isset($this->tableFields[$key]['format']))
						{
							$value=formatValueByType($key, $this->tableFields[$key]['format'], $value, $this->combos);
						}
						// Só mostra se estiver prevista sua exibição
						if ($show[$key]===true)
						{
							if (($this->tableFields[$key]['type']=='textarea') || ($this->tableFields[$key]['type']=='memo') || ($this->tableFields[$key]['type']=='code'))
							{
								if (strlen($value)>60)
								{
									$value=trim(substr($value,0,strrpos($value," ")))."...";
								}
								$mtz[]=$aligns[$this->tableFields[$key]['align']].'<small>'.$value.'</small>';

							}
							elseif ($this->tableFields[$key]['type']=='file')
							{
								$fileLink=calculateUploadFileName($this->tableName, $key, $row['id'], $value);
								if ($fileLink<>"")
								{
									if (stripos($fileLink,'mpeg')!==false)
										$mtz[]='<audio controls><source src="'.$fileLink.'" type="audio/mpeg"></audio> '.$o->button("{icon: cloud-download; style: info; size: tiny; href: ".$fileLink."?".date("Hms")."}");
									elseif (stripos($fileLink,'wav')!==false)
										$mtz[]='<audio controls><source src="'.$fileLink.'" type="audio/wav"></audio> '.$o->button("{icon: cloud-download; style: info; size: tiny; href: ".$fileLink."?".date("Hms")."}");
									elseif (stripos($fileLink,'jpg')!==false || stripos($fileLink,'gif')!==false || stripos($fileLink,'png')!==false)
										$mtz[]="<a href='".$fileLink."?".date("Hms")."'><img class='img img-rounded img-responsive' style='max-width: 100px' src='".$fileLink."?".date("Hms")."'></a>";
									else
										$mtz[]="<a href='".$fileLink."?".date("Hms")."'><span class=\"fa fa-download\"></span></a>";
									//$mtz[]="<>".$o->button("{icon: cloud-download; style: info; size: tiny; href: ".$fileLink."?".date("Hms")."}");
								} else
								{
									$mtz[]='';
								}
							}
							else
							{
								if ($this->tableFields[$key]['format']=='number' || $this->tableFields[$key]['format']=='numeric' || $this->tableFields[$key]['format']=='integer')
									$mtz[]=$aligns['right'].$value;
								else
									$mtz[]=$aligns[$this->tableFields[$key]['align']].$value;
							}

						}
					}
				}

				$color='detail';

				if (is_array($this->conditionals))
				{
					foreach ($this->conditionals as $cond)
					{
						if (isset($row[$cond['field']]) && $cond['style']<>'')
						{
							$mostraBtn=false;
							$f=$cond['field'];
							$v=$cond['value'];
							switch ($cond['conditional'])
							{
								case 'equal':
									if ($row[$f]==$v)
										$color=$cond['style'];
									break;

								case 'notEqual':
									if ($row[$f]<>$v)
										$color=$cond['style'];
									break;
								case 'greater':
									if ($row[$f]>$v)
										$color=$cond['style'];
									break;

								case 'less':
									if ($row[$f]<$v)
										$color=$cond['style'];
									break;

							}
						}
					}
				}
				$html.=$o->tableRow($mtz, $color);
				$ttl++;
			}

			// Fim da tabela
			$html.=$o->tableEnd();
			$html.='</div>';

		} else
		{
			$html.=$o->msgAlert("Nenhum registro encontrado");
		}
		return($html);
	}

	/**
		GRID
	 * Mostra um grid completo com botões para edição e exclusão de registros
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 * @param string $o Objeto gOutput, gInput, gMultiPage ou gPortal
	 * @param string $sql Query com consulta no BD para exibição
	 * @param array $show Campos a mostrar (oculta os campos que não estiverem neste array)
	 */
	function showGrid(&$o, $sql, $show, $gTable='', $gFkName='id', $gFk=0, $flatTable='')
	{
		global $gPathFiles, $http_files,$http_lib;
		$master=($gFk==0);
		$this->isMaster=$master;
		$html='';
		$maxrows=gVar('database.maxrows');
		if (intval($_REQUEST['gMaxrows'])>0)
			$maxrows=intval($_REQUEST['gMaxrows']);

		if (isset($show['password']))
			unset($show['password']);
		if (isset($show['senha']))
			unset($show['senha']);
		$fields='';
		$extra='';
		if (!$master)
		{
			$this->setTableDefs($gTable);
			$this->parseDictionaries();
			if ($flatTable<>'' && $flatTable<>$gTable)
				$extra='&gTable='.$flatTable.'&gFk='.$gFk;
			else
				$extra='&gTable='.$this->tableAlias.'&gFk='.$gFk;
			$foreignKey="id_".$this->tableMaster;
			foreach ($this->tables as $table)
			{
				if ($table['name']==$gTable)
				{
					$foreignKey=$table['foreignKey'];
				}
			}
			if ($foreignKey<>'__')
				$this->tableFields[$foreignKey]['type']='hidden';
		} else
		{
			if ($this->gridQuery<>'')
			{
				$sql=$this->gridQuery;

				$sqlLimit = $this->addLimitOnQuery($sql);
				// $sqlLimit=$sql;
				// if (stripos($sql," LIMIT ")===false)
				// 	$sqlLimit=gSQLLimit($sqlLimit,0);
				$this->parseQuery("_tmpGridTable",$sql);
				$this->setTableDefs("_tmpGridTable");
			}
			$this->parseDictionaries();
		}
		//echo $this->gCmd."<==";exit;
		$addTable=$gTable;
		if ($flatTable<>'' && $flatTable<>$gTable)
			$addTable=$flatTable;
		if ($this->gCmd<>'ajax')
		{
			$html.='<div class="row">';
			$html.='<div class="col-lg-10 col-md-9 col-sm-8 col-xs-12">';
			$html.=$this->showAddButton(2,$master,$o, $addTable, $gFk);
			$html.='</div>';
			$html.='<div class="text-right col-lg-2 col-md-3 col-sm-4 col-xs-12">';
			$html.='<div id="gDisplay" class="well well-sm"></div>';
			$html.='</div>';
			$html.='</div>';
		}

		if ($master)
		{
			// Mostra filtros utilizados
			$this->sqlAllRecords=$sql;
			if ($this->filter)
			{
				$fltMsg='';
				if ($this->gCmd=="ajax")
				{
					foreach ($this->gParam as $key=>$value)
						$_REQUEST[$key]=$value;
				}
				foreach ($this->filters as $fltKey=>$fltValue)
				{
					$filtra=true;
					// Não filtra se veio valor vazio e o tipo é texto ou combo
					if ( ( ($_REQUEST[$fltKey]=='') || ($_RESQUEST[$fltKey] == '0') ) && ((strpos($this->tableFields[$fltKey]['type'],'text')!==false) || ($this->tableFields[$fltKey]['type']=='combo') || ($this->tableFields[$fltKey]['type']=='comboMultiSelection')) )
						$filtra=false;

					if ($filtra)
					{
						$this->filters[$fltKey]['value']=gCleanField($_REQUEST[$fltKey]);
						$value=formatValueByType($fltKey, $this->tableFields[$fltKey]['type'], $this->filters[$fltKey]['value'], $this->combos);
						$this->filters[$fltKey]['showValue']=$value;
						if( ($value<>"") && ($value<>'0'))
							$fltMsg[]='<span class="label label-default">'.$this->tableFields[$fltKey]['fieldLabel'].' '.$this->filters[$fltKey]['showValue'].'</span>';

					} else
					{
						unset($this->filters[$fltKey]);
					}
				}
				if (is_array($fltMsg))
				{
					if ($this->gCmd<>'ajax')
						if(method_exists($o, 'p'))
							$html.='<div class="alert alert-info" role="alert">'.$o->p('Filtros utilizados: '.implode(' ', $fltMsg)).'</div>';
				}
				$sql=$this->addFilterOnQuery();
				$sql=$this->addLimitOnQuery($sql);
			} else
			{
				$sql=$this->addLimitOnQuery($sql);
			}
		} else
		{
			$sql=$this->addFilterOnQuery($gFkName.'='.$gFk);
			//$sql=$this->addLimitOnQuery($sql);
		}

		if ($this->delete && $this->gCmd<>'ajax')
		{
			if ($master)
			{
				$js = "
			var gId=0;
			function deleteItem".$this->tableAlias."() {
				$.ajax({
					url: '".$o->page."&gPage=". $this->gUI_DELETE.$extra. "&gId='+gId,
					type: 'GET',
					async: true,
					context: jQuery('#gGrid-content'),
					success: function(data){
						//$('#row-'+data).remove();
						refreshItens();
					},
					error: function(){
						alert('Erro ao excluir!');
					}
				});
			}";

			} else
			{
				$js = "
			var gId=0;
			function deleteItem".$this->tableAlias."() {
				$.ajax({
					url: '".$o->page."&gPage=". $this->gUI_DELETE.$extra. "&gId='+gId,
					type: 'GET',
					async: true,
					context: jQuery('#gGrid-content'),
					success: function(data){
						//$('#row-'+data).remove();
						location.reload(true);
					},
					error: function(){
						alert('Erro ao excluir!');
					}
				});
			}";

			}
			$o->addJavaScript($js);
			$o->out($o->modal("{title: Confirme; size: small; content: Excluir este registro?; okCaption: Excluir agora; name: confirm".$this->tableAlias."; url: deleteItem".$this->tableAlias."()}"), gLOC_INLINE, 999);
		}
		$html.=$o->n.'<div id="gGrid-content">'.$o->n;
		$html.=$this->getGridTable($o, $sql, $show, $gTable,$extra);
		$html.=$o->n.'</div>'.$o->n;
		if ($master)
		{
			// Table master
			if ($this->gCmd<>'ajax')
			{
				$rsTTL=dbQuery($this->sqlAllRecords);
				$ttl=count($rsTTL);
				$max=$ttl/intval($maxrows);
				$display=$ttl." ".gT("itens encontrados");

				$html.='	<div id="gPage-selection" class="hidden-print hiddenOnPrint text-center"></div>'.$o->n;

				$js="
					var ttl=".$ttl.";
					var thisPage=1;
					var maxRows=".$maxrows.";
					var max=ttl/maxRows;
					if (max>parseInt(max))
						max=parseInt(max)+1;
					function refreshItens()
					{
						$.ajax({
							url: '".$o->page."&gCmd=ajax&gPage='+thisPage+'&gMaxrows='+maxRows+'&gParam=".json_encode($_REQUEST)."',
							type: 'GET',
							async: 'true',
							context: jQuery('#gGrid-content'),
							success: function(data){
								$('#gGrid-content').html(data);
							},
							complete: function(){
								$('#gWait').css('display','none');
							}
						});
					}";
				$o->addJavascript($js);

				if ($ttl > $maxrows)
				{
					$js="

					function showItens(num)
					{
						thisPage=1;
						maxRows=num;
						refreshItens();
						max=ttl/maxRows;
						if (maxRows>ttl)
							maxRows=ttl;
						if (maxRows==0)
						{
							$('#gPage-selection').hide();

						} else
						{
							$('#gPage-selection').show();
							$('#gPage-selection').bootpag({total: max, page: 1, maxVisible: 8});
						}
					}

					function goTo(num)
					{
						if (num==0)
							thisPage=1;
						else
							thisPage=".$max.";
						$('#gPage-selection').bootpag({total: max, page: thisPage, maxVisible: 8});
						refreshItens();
					}
					$('#gPage-selection').bootpag({
						   total: max,
						   maxVisible: 8
						}).on('page', function(event, num){
							thisPage=num;
							showWait();
							refreshItens();
					});
			        ";
				}
				$o->out('<script src="' . $http_lib . 'jquery-bootpag/jquery.bootpag.min.js"></script>', gLOC_POS);
				$js.="
					$('#gDisplay').html('".$display."');
				";
				$o->addJavascript($js);
			}

		}
		return($html);
	}


	/**
		FORM
	 * Mostra um form completo com botões para salvar e voltar
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 * @param string $o Objeto gOutput, gInput, gMultiPage ou gPortal
	 */
	function showForm(&$o)
	{
		global $gPathFiles, $http_files;

		$html='';
		$id=intval($_REQUEST['gId']);
		$gTable=gCleanField($_REQUEST['gTable']);
		$gFk=intval($_REQUEST['gFk']);
		if (isset($this->jarr['columns']))
			$frm = new gForm("{debug: on; columns: ".$this->jarr['columns']."}");
		else
			$frm = new gForm("{debug: on;}");
		$frm->add("{name: gPage; type: hidden; value: ".$this->gUI_SAVE."}");
		$frm->add("{name: gAction; type: hidden; value: 0}");
		$foreignKey="__";
		if ($gTable<>'')
		{
			$this->setTableDefs($gTable);
			$frm->add("{name: gTable; type: hidden; value: ".$gTable."}");
			$frm->add("{name: gFk; type: hidden; value: ".$gFk	."}");
			$this->parseDictionaries();
			foreach ($this->tables as $table)
			{
				if ($table['name']==$gTable)
				{
					$html.=$o->msgFilter($table['title']);
					$html.="<hr />";
					$foreignKey=$table['foreignKey'];
				}
			}
		}

		$pTable=$this->tableName;
		$fields=$this->tableFields;
		if ($id<>0)
		{
			// Mostra campos da tabela principal
			$sql="SELECT * FROM ".$this->tableName." WHERE id=$id";
			$rs=dbQuery($sql);
			foreach ($this->tableFields as $key=>$arr)
			{
				$value=$rs[0][$key];
				if (($this->tableFields[$key]['type']<>'checkbox') && ($this->tableFields[$key]['type']<>'combo'))
					$value=formatValueByType($key, $this->tableFields[$key]['type'], $value, $this->combos);
				$this->tableFields[$key]['value']=$value;
				if ($this->tableFields[$key]['type']=='file')
				{
					$fileLink=calculateUploadFileName($this->tableName, $key, $this->tableFields['id']['value'], $value);
					if ($fileLink<>"")
					{

						$this->tableFields[$key.'view']['name']=$key.'view';
						$this->tableFields[$key.'view']['fieldLabel']='&nbsp;';
						$this->tableFields[$key.'view']['type']='html';
						if ($this->tableFields[$key]['value']=='audio/mpeg' || $this->tableFields[$key]['value']=='audio/wav')
							$this->tableFields[$key.'view']['value']='<audio controls><source src="'.$fileLink.'" type="'.$this->tableFields[$key]['value'].'"></audio> '.$o->button("{icon: cloud-download; style: info; href: ".$fileLink."?".date("Hms")."}");
						elseif ($this->tableFields[$key]['value']=='image/jpeg' || $this->tableFields[$key]['value']=='image/png' || $this->tableFields[$key]['value']=='image/gif')
							$this->tableFields[$key.'view']['value']='<a href="'.$fileLink.'"><span class="fa fa-download"></span></a>';
						else{
							$this->tableFields[$key.'view']['value']=$o->button("{icon: cloud-download; target:_blank;style: info; href: ".$fileLink."?".date("Hms").";}");
						}
					}
				}

			}
			$fields=$this->tableFields;
			if ($gTable=='')
				$frm->addButton("{icon: check-square; title: Confirmar e editar próximo; style: default}", "javascript: document.getElementById('gAction').value=1;document.forms['".$frm->formId."'].submit()");
		} else
		{
			$frm->addButton("{icon: check-square; title: Confirmar e criar outro; style: default}", "javascript: document.getElementById('gAction').value=1;document.forms['".$frm->formId."'].submit()");
		}
		$frm->addButton("{title: Voltar sem salvar; style: default}", "javascript: document.location.href='" .$o->page."'");

		foreach ($this->hideFields as $hide=>$value){
			unset($fields[$hide]);
		}
		if (isset($_REQUEST['gLastPage']))
		{
			$fields['gLastPage']['name']='gLastPage';
			$fields['gLastPage']['type']='hidden';
			$fields['gLastPage']['value']=intval($_REQUEST['gLastPage']);
		}
		// if (isset($_REQUEST['gFk']))
		// {
		// 	$fields['gFk']['name']='gFk';
		// 	$fields['gFk']['type']='hidden';
		// 	$fields['gFk']['value']=intval($_REQUEST['gFk']);
		// }
		// if (isset($_REQUEST['gTable']))
		// {
		// 	$fields['gTable']['name']='gTable';
		// 	$fields['gTable']['type']='hidden';
		// 	$fields['gTable']['value']=gCleanField($_REQUEST['gTable']);
		// }
		// Se estiver no formulário inicial, procura por tabelas relacionadas para exibir
		if ($gTable=='')
		{
			// Se existirem relacionamentos one-to-one, mostra campos das outras tabelas relacionadas
			if (is_array($this->tables))
			{
				foreach ($this->tables as $table)
				{
					if ($table['relationship']=='one-to-one')
					{
						// Se for uma tabela relacionada
						$this->setTableDefs($table['name']);
						$this->parseDictionaries();
						$sql="SELECT * FROM ".$this->tableName." WHERE ".$table['foreignKey']."=$id";

						$rs=dbQuery($sql);
						foreach ($this->tableFields as $key=>$arr)
						{
							if ($key<>'id' && $key<>'idd')
							{
								$value=$rs[0][$key];
								if (($this->tableFields[$key]['type']<>'checkbox') && ($this->tableFields[$key]['type']<>'combo'))
									$value=formatValueByType($key, $this->tableFields[$key]['type'], $value, $this->combos);
								$this->tableFields[$key]['value']=$value;
								$this->tableFields[$key]['tableInfo']=$table;
								foreach ($this->dictionaries as $jarr)
								{
									if ($jarr['name']==$key)
									{
										foreach ($jarr as $el => $nvalue)
										{
											/*
											if ($el=="type")
											{
												$align=gCheckAlignByType($this->tableFields[$jarr['name']]['type']);
												$this->tableFields[$jarr['name']]['align']=$align;
												$gDB[$this->tableAlias]['fields'][$jarr['name']]['align']=$align;
												switch ($value)
												{
													case "combo":
													gD($jarr);exit;
														$arr=$this->getQueryData($jarr['name'],$gDB[$this->tableAlias]['fields'][$jarr['name']]['items']);
														break;
												}
											}
											*/
											$this->tableFields[$key][$el]=$nvalue;
										}

									}
								}

								if (!isset($fields[$key]) )
								{
									$fields[$key]=$this->tableFields[$key];
								}

								if ($this->tableFields[$key]['type']=='file')
								{
									$fileLink=calculateUploadFileName($pTable, $key, $id, $value);
									if ($fileLink<>"")
									{
										$fields[$key.'view']['name']=$key.'view';
										$fields[$key.'view']['fieldLabel']='&nbsp;';
										$fields[$key.'view']['type']='html';
										if ($this->tableFields[$key]['value']=='audio/mpeg' || $this->tableFields[$key]['value']=='audio/wav')
											$fields[$key.'view']['value']='<audio controls><source src="'.$fileLink.'" type="'.$this->tableFields[$key]['value'].'"></audio> '.$o->button("{icon: cloud-download; style: info; size: tiny; href: ".$fileLink."?".date("Hms")."}");
										elseif ($this->tableFields[$key]['value']=='image/jpeg' || $this->tableFields[$key]['value']=='image/png' || $this->tableFields[$key]['value']=='image/gif')
											$fields[$key.'view']['value']='<a href="'.$fileLink.'"><img class="img-responsive img-thumbnail" src="'.$fileLink.'?'.date("YmdHis").'"></a>';
										else
											$fields[$key.'view']['value']=$o->button("{icon: cloud-download; style: info; size: tiny; href: ".$fileLink."?".date("Hms")."}");
									}
								}

							}
						}
						$this->setTableDefs($this->tableMaster);
					}
				}
			}

		}

		// Ordena campos, deixando anexos pro final
		$newFields=array();
		$anexos=array();
		foreach ($fields as $key=>$value)
		{
			if ($value['type']=='file' || ($value['type']=='html' && substr($value['name'],-4)=='view' ))
				$anexos[$key]=$fields[$key];
			else
				$newFields[$key]=$value;
		}
		foreach ($anexos as $key=>$value)
		{
			$newFields[$key]=$value;
		}
		$fields=$newFields;
		//echo "<pre>";print_r($fields);exit;
		foreach ($fields as $key=>$value)
		{
			//if ($show[$key]===true)
			{
				unset($value['native_type']);
				unset($value['pdo_type']);
				unset($value['flags']);
				unset($value['len']);
				unset($value['precision']);
				unset($value['align']);
				$items=$value['items'];
				unset($value['items']);
				$value['items']=$items; // Items tem que ser o último parâmetro
				if (($key=='id') || ($key=='idd'))
				{
					$value['type']='hidden';
				}
				if (($gTable<>'') && ($value['name']==$foreignKey))
				{
					$value['type']='hidden';
				}

				if (isset($value['tableInfo']))
				{
					$value['name']='_'.$value['tableInfo']['name'].'__'.$value['name'];
				}
				if ($value['type']=='code' || $value['type']=='html')
				{
					$frm->add(cssEncode($value), $value['value']);

				} else
				{
					$frm->add(cssEncode($value));
				}
			}
		}
		$html.= $frm->render($o);
		return($html);
	}

	/**
	 * Adiciona à pagina botões para inclusão de novo registro e para impressão
	 * de acordo com as configurações da classe
	 *
	 * @param int $page numero da página a ser exibida
	 * @param boolean $master inserir botão de impressão
	 * @param string $html que armazena o conteúdo da página
	 * @param object $o referência para o gPortal
	 * @return conteudo html envolto em tag div
	 *
	 * @author André Luiz
	 * @version 1.0 18/12/14 15:11
	 *
	 */
	function showAddButton($page, $master, &$o, $gTable='', $gFk='')
	{
		$fez=false;
		// Se tiver a permissão de INSERT mostra botão "+ Adicionar"
		$html.='<div class="hidden-print hiddenOnPrint">';
		if ($this->insert)
		{
			$fez=true;
			if ($master)
			{
				$html.=$o->button("{icon: plus; style: default; title: Adicionar; hint: Adicionar um novo registro; url: ".$o->page.$this->extraParm."&gPage=".$this->gUI_FORM."}");
			} else
			{
				$html.=$o->button("{icon: plus; style: default; title: Adicionar; hint: Adicionar um novo registro; url: ".$o->page.$this->extraParm."&gPage=".$this->gUI_FORM."&gTable=".$gTable."&gFk=".$gFk."}");
			}
		}
		if (is_array($this->ownButtons))
		{
			$fez=true;
			foreach ($this->ownButtons as $btn)
			{
				$html.=$o->button($btn);
			}
		}
		if ($this->gCmd<>'ajax' && $this->gPage<>$this->gUI_FILTER && $this->isMaster)
		{
			$html.='
			<div class="dropdown" style="display: inline">
				<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" id="dropdownMostrar" aria-expanded="false" style="margin-bottom: 4px"><span class="fa fa-angle-down fa-fw"></span> '.gT("Mostrar").'</button>
				<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMostrar">
					<li role="presentation" class="dropdown-header">'.gT("Itens por página").'</li>
					<li><a href="#" onClick="showItens(6)">'.gT('6 itens').'</a></li>
					<li><a href="#" onClick="showItens(10)">'.gT('10 itens').'</a></li>
					<li><a href="#" onClick="showItens(50)">'.gT('50 itens').'</a></li>
					<li><a href="#" onClick="showItens(100)">'.gT('100 itens').'</a></li>
					<li><a href="#" onClick="showItens(0)">'.gT("Todos os itens").'</a></li>
					<li class="divider"></li>
					<li role="presentation" class="dropdown-header">'.gT("Ir para").'</li>
					<li><a href="#" onClick="goTo(0)">'.gT("Início").'</a></li>
					<li><a href="#" onClick="goTo(1)">'.gT("Final").'</a></li>
				</ul>
			</div>
			';
		}
		if ($fez)
		{
			$html.=$o->button("{icon: print; tag: button; style: default; hint: Preparar conteúdo para impressão; }", "javascript: imprimir()");
			$html.="<br /><br />";
		}
		$html.="</div>";

		return $html;
	}

	/**
	 * Executa ações específicas de acordo com os dados passados pelo código e pelo usuário
	 * @author	Giuliano Nascimento
	 * @version	4.0 29/07/2014 10:16
	 */
	function run( &$o, &$html )
	{
		global $usrId, $usrIdd, $gDB, $gPathFiles, $http_lib,$gFWMute;

		if ($this->fancybox)
		{
			$js = '	$(document).ready(function(){
						$(".fancybox-default").fancybox({
								type : "iframe"
						});
					});';
			$o->addJavaScript($js);
			$o->out('<script src="' . $http_lib . 'jquery-fancybox-2.1.5/jquery.fancybox.js"></script>', gLOC_POS);
			$o->out('<link href="' . $http_lib . 'jquery-fancybox-2.1.5/jquery.fancybox.css" rel="stylesheet">', gLOC_PRE);
		}
		$owner="";
		if (gVar("global.imagesbydb")=="true")
			$owner = gVar("database.name")."_";
		// Verifica se existe comando para ocultar ou mostrar campos, se existir age conforme
		$show='';
		if (is_array($this->showFields))
		{
			foreach ($this->tableFields as $key => $value)
			{
				if ($this->showFields[$key]===true)
				{
					$show[$key]=true;
				}
			}

		} elseif (is_array($this->hideFields))
		{
			foreach ($this->tableFields as $key => $value)
			{
				if ($this->hideFields[$key]!==true)
				{
					$show[$key]=true;
				}
			}
		} else
		{
			foreach ($this->tableFields as $key => $value)
			{
				$show[$key]=true;
			}
		}


		if ( $this->gCmd == "ajax" )
		{
			gLog("Ajax ==> gPage=".$this->gPage." gMaxrows=".$_REQUEST['gMaxrows']." gParam=".$_REQUEST['gParam']);
			$this->gParam=json_decode($_REQUEST['gParam']);
			echo $this->showGrid($o, $this->tableSql, $show);
			exit;
		} else
		{
			if (($this->jarr['forceFilter']<>'true') || ($this->forceFilter===true))
			{
				$sql="SELECT count(id) ttl FROM ".$this->tableName;
				$rst=dbQuery($sql);
				if ($rst[0]['ttl']<(gVar("global.maxrows")*3))
					$this->filter=false;
				if ( (!$this->filter ) && ($this->gPage == $this->gUI_FILTER) )
					$this->gPage=$this->gUI_GRID;
			}
			// Executa o tipo de evento de acordo com o valor da variável gPage
			switch ($this->gPage)
			{

				/**
				FILTRO
				*/
				case ($this->gUI_FILTER):

					$html.='<!-- START HEADER -->';
					$html.=$o->msgTitle($this->jarr['title']);
					$html.='<!-- END HEADER -->';
					$html.=$this->showAddButton(2, false, $o);
					$html.=$o->msgInfo('Informe abaixo os critérios para filtragem de registros');
					$filterColumns=2;
					if (!empty($this->jarr['filterColumns']))
						$filterColumns=$this->jarr['filterColumns'];
					$frm  = new gForm("{debug: on; columns: ".$filterColumns."; buttonNextCaption: Filtrar}");
					$frm->add("{name: gPage; type: hidden; value: ".$this->gUI_GRID."}");
//					echo "<pre>";
//					var_dump($this->filters);
//					exit;


					foreach ($this->filters as $key=>$fltvalue)
					{
						$fieldLabel=gField2String($key);
						$origKey='';
						if (stripos($key,"__FROM")!==false)
						{
							$origKey=$key;
							$key=str_ireplace('__FROM','',$key);
							$fieldLabel=$fieldLabel.' '.gT('de');
						}
						if (stripos($key,"__TO")!==false)
						{
							$origKey=$key;
							$key=str_ireplace('__TO','',$key);
							$fieldLabel=$fieldLabel.' '.gT('até');
						}

						// Compatibilidade gFW 3.0
						if (substr($key,0,6)=='from__')
						{
							$origKey=$key;
							$key=substr($key,6);

							if($this->filters['fieldLabel'] <> "")
								$fieldLabel=$this->filters['fieldLabel'];
							else
								$fieldLabel=$fieldLabel.' '.gT('de');
						}
						if (substr($key,0,4)=='to__')
						{
							$origKey=$key;
							$key=substr($key,4);
							if($this->filters['fieldLabel'] <> "")
								$fieldLabel=$this->filters['fieldLabel'];
							else
								$fieldLabel=$fieldLabel.' '.gT('de');
						}
						//echo "==>".$key;exit;

						if (strpos($key,'__')!==false){
							if($origKey=='')
								$origKey=$key;
							$key=substr($key,strpos($key,'__')+2);
							$fieldLabel=$fltvalue['fieldLabel'];
						}

						$value=$this->tableFields[$key];
						if($gFWMute)
						{
							if ($origKey<>"")
							{
								$tmpFilter = $this->filters[$origKey];
							} else
							{
								$tmpFilter = $this->filters[$key];
							}
							foreach ($tmpFilter as $k=>$v)
							{
								$value[$k] = $v;
							}
//							$value= $origKey<>"" ? $this->filters[$origKey] : $this->filters[$key];

						}
						if (is_array($value))
						{
							$value['fieldLabel']=$value['fieldLabel'];
							unset($value['native_type']);
							unset($value['pdo_type']);
							unset($value['flags']);
							unset($value['len']);
							unset($value['precision']);
							unset($value['align']);
							$items=$value['items'];
							unset($value['items']);
							unset($value['value']);
							$value['allowBlank']=$fltvalue['true'];
							$value['items']=$items; // Items tem que ser o último parâmetro
							if (($key=='id') || ($key=='idd'))
							{
								$value['type']='hidden';
							}
							if (isset($fltvalue['value']))
								$value['value']=$fltvalue['value'];
							if (isset($fltvalue['allowBlank']))
								$value['allowBlank']=$fltvalue['allowBlank'];
							if (isset($fltvalue['operator']))
								$value['operator']=$fltvalue['operator'];
							if ($origKey<>'')
							{
								$value['name']=$origKey;
							}

							$frm->add(cssEncode($value));
						}

					}
					$html.= $frm->render($o);
					break;

				/**
				GRID
				*/
				case ($this->gUI_GRID):

					$o->PDFEnabled = true;
					$o->DOCEnabled = true;
					$o->XLSEnabled = true;
					$o->CSVEnabled = true;

					$html.='<!-- START HEADER -->';
					$html.=$o->msgTitle($this->jarr['title']);
					$html.='<!-- END HEADER -->';
					$html.=$this->showGrid($o, $this->tableSql, $show);
					break;

				/**
				FORMULÁRIO
				*/
				case ($this->gUI_FORM):
					$html.='<!-- START HEADER -->';
					$html.=$o->msgTitle($this->jarr['title']);
					$html.='<!-- END HEADER -->';
					$html.=$this->formMessage;
					if($this->gUI_FORM == 2)
						$html.=$this->showForm($o);
					$id=intval($_REQUEST['gId']);
					//echo "<pre>";
					//var_dump($this->tables);
					//exit;
					// Se existirem tabelas relacionadas, mostra abas
					if ((!isset($_REQUEST['gTable'])) && (is_array($this->tables)))
					{
						$tem=false;
						foreach ($this->tables as $tabelas)
						{
							if ($tabelas['relationship']<>"one-to-one")
								$tem=true;
						}
						if ($id==0)
						{
							$tem=false;
						}

						if ($tem)
						{
							//$html.='<div class="panel-group" id="accordion">'.$o->n;
							//$html.='	<div class="panel panel-default">'.$o->n;
							$in='in';
							$active='active';
							$cnt=0;
							$html.='<ul class="nav nav-tabs" role="tablist" id="gTables">'.$o->n;
							foreach ($this->tables as $nr=>$table)
							{
								if($table['relationship']=="one-to-many")
								{
									$html.='<li role="presentation" class="'.$active.'"><a href="#'.gString2Field($table['title']).'" aria-controls="'.gString2Field($table['title']).'" role="tab" data-toggle="tab">'.($table['title']).'</a></li>'.$o->n;
									$active='';
								}
							}
							$html.='</ul>'.$o->n;
							$html.='<div class="tab-content">'.$o->n;
							$active='active';

							foreach ($this->tables as $nr=>$table)
							{
								switch ($table['relationship']) {
									case 'one-to-many':
										// Mostra grid
										$relTable=$table['name'];
										if (isset($table['showTable']))
										{
											$relTable=$table['showTable'];
										}

										$this->setTableDefs($relTable);
										$cnt++;

										// $html.='		<div class="panel-heading">'.$o->n;
										// $html.='			<h4 class="panel-title">'.$o->n;
										// $html.='				<a data-toggle="collapse" data-parent="#accordion" href="#collapse'.$cnt.'">'.gT($table['title']).'</a>'.$o->n;
										// $html.='			</h4>'.$o->n;
										// $html.='		</div>'.$o->n;
										// $html.='		<div id="collapse'.$cnt.'" class="panel-collapse collapse '.$in.'">'.$o->n;
										// $html.='			<div class="panel-body">'.$o->n;

										$html.='<div role="tabpanel" class="tab-pane fade in '.$active.'" id="'.gString2Field($table['title']).'"><br>';
										$active='';
										$show='';
										if (is_array($this->showFields))
										{
											foreach ($this->tableFields as $key => $value)
											{
												if ($this->showFields[$key]===true)
												{
													$show[$key]=true;
												}
											}

										} elseif (is_array($this->hideFields))
										{
											foreach ($this->tableFields as $key => $value)
											{
												if ($this->hideFields[$key]!==true)
												{
													$show[$key]=true;
												}
											}
										} else
										{
											foreach ($this->tableFields as $key => $value)
											{
												$show[$key]=true;
											}
										}
										$html.=$this->showGrid($o, $this->tableSql, $show, $relTable, $table['foreignKey'], $id, $table['name']);
										//$html.='			</div>'.$o->n;
										$html.='		</div>'.$o->n;
										$in='';

										break;

									default:
										# code...
										break;
								}
							}
							//$html.='	</div>'.$o->n;
							$html.='</div>'.$o->n;
						}
						$html.='<br>&nbsp;';
					}
					break;

				/**
				SALVAR
				*/

				case ($this->gUI_SAVE):
					//echo "<pre>";var_dump($_REQUEST);exit;
					$id=intval($_REQUEST['id']);
					$gTable=gCleanField($_REQUEST['gTable']);
					$gFk=gCleanField($_REQUEST['gFk']);
					$anexos=array();
					$gLastPage=intval($_REQUEST['gLastPage']);
					$fields='';
					$todosCamposEmBranco=true;

					if ($gTable<>'')
					{
						$this->setTableDefs($gTable);
						$this->parseDictionaries();
						$gFkName='id';
						foreach ($this->tables as $table)
						{
							if ($table['name']==$gTable)
							{
								$gFkName=$table['foreignKey'];
							}
						}
						$fields[$gFkName]=$gFk;
					}
					$pTable=$this->tableName;
					foreach ($this->tableFields as $key=>$arr)
					{
						if ($gTable=='' || $arr['name']<>$gFkName)
						{
							if ($arr['type']<>'code'){
								$value=gCleanField($_REQUEST[$key]);
							}
							else
							{
								$value=$_REQUEST[$key];
							}
								//$value=str_replace("'",'"',$_REQUEST[$key]);

							if ($value<>'')
								$todosCamposEmBranco=false;
							if ((($_REQUEST[$key]<>'')) || ($arr['type']=='combo') || ($arr['type']=='checkbox') || ($arr['type']=='dateTime') || ($arr['type']=='date') || ($arr['type']=='file') || ($arr['type']=='textarea') || ($arr['type']=='code') || ($arr['type']=='integer') || ($arr['type']=='plate'))
							{
								if ($arr['type']=='file')
								{
									$t=$_FILES[$key]['type'];
									if ($t=='')
										$t=strtolower(substr($_FILES[$key]['name'],strpos($_FILES[$key]['name'],".")));
									$anexos[$key]=$t;
									$value=$t;
								} else
								{
									$value=formatDBValueByType($arr['type'], $value);
								}
								$fields[$key]=$value;
								if (($arr['type']=='file') && (trim($_FILES[$key]['type'])==''))
								{
									unset($fields[$key]);
								}
								if (($arr['type']=='password') && ($value==SENHA_NAO_MODIFICADA))
								{
									unset($fields[$key]);
								}
								if ($arr['type']=='show')
								{
									unset($fields[$key]);
								}
							} else
							{
								if (($arr['type']=='dateTime') || ($arr['type']=='date') || ($arr['type']=='show'))
								{
									unset($fields[$key]);
								} else
								{
									if (($arr['type']=="integer") || ($arr['type']=="number"))
										$fields[$key]=0;
									else
										$fields[$key]='';
								}
							}
						}
					}

					unset($fields['id']);
					unset($fields['idd']);
					foreach ($fields as $f=>$value)
					{
						if ($this->hideFields[$f])
							unset($fields[$f]);
					}

					$uploadOk=true;
					if (count($anexos)>0)
					{
						// Se foi enviado um arquivo anexo, salva-o com o nome sendo o nome_da_tabela/id do registro atual
						foreach ($anexos as $nomeDoAnexo=>$tipo)
						{
							if (trim($tipo)<>"")
							{
								$anexosNomes[$nomeDoAnexo]=trim($usrId.'-'.rand(10000,99999).'.'.substr($tipo,strpos($tipo,'/')+1));   //
								$fileDownloaded=fileUpload($nomeDoAnexo, $anexosNomes[$nomeDoAnexo], $gPathFiles.'/'.$this->tableName.'/'.$owner.$nomeDoAnexo); //
								if ($fileDownloaded=='')
									$uploadOk=false;
							}
						}
					}

					if ($uploadOk)
					{
						if ( (int) $id >0)
						{
							$fields=$this->onBeforeUpdate($fields);
							if(!empty($fields))
							{
								// Editar
								$novoRegistro=false;
								dbUpdate($this->tableName, $fields, $id);
								$nId=$id;
								$fields['id']=$nId;
								$this->onAfterUpdate($fields);
							}
						} else
						{
							$fields=$this->onBeforeAdd($fields);
							if(!empty($fields))
							{
								// Inserir
								if ($this->dontDuplicate) {
									$camposDaTabela = array_keys($fields);
									$valoresDaTabela = array_values($fields);
									$campos = array();
									foreach ($camposDaTabela as $key => $campo) {
										$campos[] = $campo . " = '" . $valoresDaTabela[$key] . "'";
									}
								}

								if ($this->dontDuplicateColumns) {
									$camposParaFiltro = $this->dontDuplicateColumns;
									$camposParaFiltro = explode(',', $camposParaFiltro);
									$camposParaFiltro = array_intersect_key($fields, array_flip($camposParaFiltro));
									$campos = array();
									foreach ($camposParaFiltro as $key => $valores) {
										$campos[] = $key . " = '" . $valores . "'";
									}
								}

								if ($campos) {
									$campos = implode(" AND ", $campos);
									$sql = "SELECT id FROM {$this->tableName} WHERE {$campos}";
									$siglaExistente = dbQuery($sql);
									if ($siglaExistente) {
										$html  = $o->msgTitle("Erro");
										$html .= $o->msgError("Não foi possível prosseguir com este procedimento pois já existe um registro com estes dados");
										$html .= $o->button("{title: Voltar; href: back}");
										break;
									}
								}

								$novoRegistro = true;
								$nId = dbInsert($this->tableName, $fields, true);
								$fields['id'] = $nId;
								$this->onAfterAdd($fields);
							}
						}
						foreach ($anexosNomes as $nomeDoAnexo=>$nomeTmp) //
						{
							$novoNome=trim($nId.'.'.substr($nomeTmp,strpos($nomeTmp,'.')+1));
							rename($gPathFiles.'/'.$this->tableName.'/'.$owner.$nomeDoAnexo.'/'.$nomeTmp,$gPathFiles.'/'.$this->tableName.'/'.$owner.$nomeDoAnexo.'/'.$novoNome); //
							gLog("===> Renomeando $nomeTmp para $novoNome");
						}

						if ($gTable=='')
						{
							// Verifica se tem tabelas relacionadas pra salvar dados também
							if (is_array($this->tables))
							{
								foreach ($this->tables as $table)
								{
									if ($table['relationship']=='one-to-one')
									{
										// Se for uma tabela relacionada
										$this->setTableDefs($table['name']);
										$this->parseDictionaries();
										$fields=array();
										$anexos=array();
										foreach ($this->tableFields as $key=>$arr)
										{
											//$value['name']='_'.$value['tableInfo']['name'].'__'.$value['name'];
											$nkey='_'.$table['name'].'__'.$key;

											if ($arr['type']<>'code')
												$value=gCleanField($_REQUEST[$nkey]);
											else
												$value=$_REQUEST[$nkey];

											if ((!empty($_REQUEST[$nkey])) || ($arr['type']=='checkbox') || ($arr['type']=='password'))
											{
												//if (($this->tableFields[$key]['type']<>'checkbox') && ($this->tableFields[$key]['type']<>'combo'))
												if ($this->tableFields[$key]['type']<>'combo')
													$value=formatDBValueByType($this->tableFields[$key]['type'], $value);

												if ((!isset($fields[$key])) && (!is_numeric($key)))
												{
													$fields[$key]=$value;
												}
												if (($arr['type']=='password') && ($value==SENHA_NAO_MODIFICADA))
												{
													unset($fields[$key]);
												}
												if ($arr['type']=='show')
												{
													unset($fields[$key]);
												}
											} else
											{
												if (($arr['type']=='dateTime') || ($arr['type']=='date') || ($arr['type']=='show'))
												{
													unset($fields[$key]);
												} else
												{
													if ((!isset($fields[$key])) && (!is_numeric($key)))
													{
														if (($arr['type']=="integer") || ($arr['type']=="number"))
															$fields[$key]=0;
														else
														{
															$fields[$key]='';
															if ($arr['type']=='file')
															{
																$anexos[$nkey]=$_FILES[$nkey]['type'];
																$value=$_FILES[$nkey]['type'];
																$fields[$key]=$value;
															}
															if (($arr['type']=='file') && (trim($_FILES[$nkey]['type'])==''))
															{
																unset($fields[$key]);
															}
														}
													}
												}
											}
										}

										if (count($anexos)>0)
										{
											// Se foi enviado um arquivo anexo, salva-o com o nome sendo o nome_da_tabela/id do registro atual
											foreach ($anexos as $nomeDoAnexo=>$tipo)
											{
												if (trim($tipo)<>"")
												{
													$novoNome=trim($nId.'.'.substr($tipo,strpos($tipo,'/')+1));
													$campo=substr($nomeDoAnexo,strpos($nomeDoAnexo,'__')+2);
													$fileDownloaded=fileUpload($nomeDoAnexo, $novoNome, $gPathFiles.'/'.$pTable.'/'.$owner.$campo);
												}
											}
										}
										//echo "Upload: ".$uploadOk;exit;
										if (($fileDownloaded<>'') || ($uploadOk))
										{
											if ($id<>0)
											{
												// Editar
												$sql="SELECT * FROM ".$this->tableName." WHERE ".$table['foreignKey']."=".$id;
												$rsTmp=dbQuery($sql);
												if ((count($rsTmp)>0) )
												{
													if(count($fields)>0)
													{
														//Retirando o ID e IDD das campos a serem realizados o Update
														unset($fields['id']);
														unset($fields['idd']);
														$fields=$this->onBeforeUpdate($fields);
														dbUpdate($this->tableName, $fields, $table['foreignKey']."=".$id);
													}
												}
												else
												{
													if (gVar("global.idd")=="true")
														$fields['idd']=$usrIdd;
													$fields[$table['foreignKey']]=$id;
													$fields=$this->onBeforeUpdate($fields);
													dbInsert($this->tableName, $fields);
												}
											} else
											{
												// Inserir
												if (gVar("global.idd")=="true")
													$fields['idd']=$usrIdd;
												$fields[$table['foreignKey']]=$nId;
												$fields=$this->onBeforeUpdate($fields);
												dbInsert($this->tableName, $fields, true);
											}
											$this->onAfterUpdate($fields);
										} else
										{
											$html=$o->msgTitle("Erro");
											$html.=$o->msgError("Houve um erro ao salvar o arquivo anexo. Verifique se o tipo de arquivo ou o seu tamanho são permitidos.");
										}
										//$this->setTableDefs($this->tableMaster);


									}
								}

							}
						}
						if ($nId>0 && $id==0)
							$id=$nId;
						if ($gTable=='')
						{
							// Tabela principal
							if (intval($_REQUEST['gAction'])>0)
							{
								if (!$novoRegistro)
								{
									$sql=$this->tableSql;
									$rs=dbQuery($sql);
									$nextId=$id;
									foreach ($rs as $key=>$row)
									{
										if ($row['id']==$id)
										{
											$nextId=intval($rs[intval($key)+1]['id']);
										}
									}
									if ($gLastPage>0)
									{
										redirect($o->page."&gPage=".$gLastPage."&gId=".$id);
									} else
									{
										if ($nextId>0)
											redirect($o->page."&gPage=".$this->gUI_FORM."&gId=".$nextId);
										else
											redirect($o->page);
									}

								} else
								{
									redirect($o->page."&gPage=".$this->gUI_FORM);
								}
							} else
							{
								if ((!$novoRegistro) && ($gLastPage>0))
								{
									redirect($o->page."&gPage=".$gLastPage."&gId=".$id);
								} else
								{
									if (!$novoRegistro)
										redirect($o->page."&gPage=".$this->gUI_FORM."&gId=".$id);
									else
									{
										if (is_array($this->tables))
											redirect($o->page."&gPage=".$this->gUI_FORM."&gId=".$nId);
										else
										{
											if ($this->showOk)
											{
												$html.=$o->msgTitle($this->jarr['title']);
												$html.=$o->msgSuccess("Registro salvo");
												$html.=$o->button("{title: Voltar; href: ".$o->page."}");
											} else
											{
												redirect($o->page);
											}
										}
									}
								}
							}
						} else
						{
							//http://localhost/webcfc/rj/marcelle/index.php?g=horarios&gPage=2&gTable=aulas_horarios_horas&gFk=6

							$addUrl="&gId=".$gFk;
							if ($_REQUEST['gAction']==1 && $_REQUEST['gFk']<>'')
							{
								$addUrl="&gTable=".$_REQUEST['gTable']."&gFk=".$_REQUEST['gFk'];
							}
							// Tabelas relacionadas
							if ($gLastPage>0)
							{
								redirect($o->page."&gPage=".$gLastPage.$addUrl);
							} else
							{
								redirect($o->page."&gPage=".$this->gUI_FORM.$addUrl);
							}
						}
					} else
					{
						$html=$o->msgTitle("Erro");
						$html.=$o->msgError("Houve um erro ao salvar o arquivo anexo. Verifique se o tipo de arquivo ou o seu tamanho são permitidos.");
						$html.=$o->button("{title: Voltar; href: back}");
					}
					break;

				/**
				APAGAR
				*/
				case ($this->gUI_DELETE):
					$id=intval($_REQUEST['gId']);
					$gTable=gCleanField($_REQUEST['gTable']);
					$gFk=gCleanField($_REQUEST['gFk']);
					if ($id>0)
					{
						if ($gTable=='')
						{
							if($this->onBeforeDelete($id))
							{
								dbQuery("DELETE FROM ".$this->tableName." WHERE id=$id");
								$this->onAfterDelete($id);
							}
							// if ($this->showOk)
							// {
							// 	$html.=$o->msgTitle($this->jarr['title']);
							// 	$html.=$o->msgSuccess("Registro excluído");
							// 	$html.=$o->button("{title: Voltar; href: ".$o->page."}");
							// } else
							// 	redirect($o->page);
						} else
						{
							foreach ($this->tables as $table)
							{
								if ($table['name']==$gTable)
								{
									$gFkName=$table['foreignKey'];
								}
							}
							if($this->onBeforeDelete($id))
							{
								dbQuery("DELETE FROM ".$gTable." WHERE id=$id AND ".$gFkName.'='.$gFk);
								$this->onAfterDelete($id);
							}
							// if ($this->showOk)
							// {
							// 	$html.=$o->msgTitle($this->jarr['title']);
							// 	$html.=$o->msgSuccess("Registro excluído");
							// 	$html.=$o->button("{title: Voltar; href: ".$o->page."}");
							// } else
							// 	redirect($o->page."&gPage=".$this->gUI_FORM."&gId=".$gFk);
						}
					}
					break;

				/**
				ATIVAR
				*/
				case ($this->gUI_ACTIVATE):
					$id=intval($_REQUEST['gId']);
					$gAct=gCleanField($_REQUEST['gAct']);
					if ($gAct<>"ativo")
					{
						$gAct="active";
					}
					if ($id>0)
					{
						$dataAtivacao=false;
						$dataDesativacao=false;
						$extra='';
						foreach ($this->tableFields as $key=>$value)
						{
							if ($key=='data_ativacao')
								$dataAtivacao=true;
							if ($key=='data_desativacao')
								$dataDesativacao=true;
						}
						if ($dataAtivacao || $dataDesativacao)
						{
							// Descobrindo se é desativacao ou ativacao
							$rs=dbQuery("SELECT $gAct ativo FROM ".$this->tableName." WHERE id=$id");
							$valor=$rs[0]['ativo'];
							if ($valor==1)
							{
								// Está ativo, então vai desativar
								$extra=", data_desativacao='".date("Y-m-d H:i:s")."'";
							} else
							{
								// Está inativo, então vai ativar
								$extra=", data_ativacao='".date("Y-m-d H:i:s")."'";
							}
						}
						dbQuery("UPDATE ".$this->tableName." set $gAct=1-$gAct $extra WHERE id=$id");
						redirect($o->page);
					}
					break;
			}
		}

	}

}












/**
 * Classe para tratamento de tabelas mestre x detalhe
 * @package	gUI
 * @author	Giuliano Nascimento
 * @version	4.0 29/07/2014 10:16
 */

class gUIMD extends gUICore
{
	public $html = '';
	public $rs = '';
	public $details = '';
	public $sql = '';

	function __construct($json)
	{
		$this->json = $json;
		$this->jarr = cssDecode($json);
	}

	/**
	 * Adiciona uma consulta a banco de dados. A primeira adicionada será usada para quase todas as funções
	 *
	 * @author	Giuliano Nascimento
	 * @version	4.0 16/07/2016
	 * @param  string $sql Query
	 */
	function addQuery($sql)
	{
		$this->queries[]=$sql;
	}

	/**
	 * Adiciona um botão e funcionalidade de "detalhe"
	 *
	 * @author	Giuliano Nascimento
	 * @version	4.0 17/07/2016
	 * @param  string $json Parêmtros semelhantes aos do componente gOutput:button
	 */
	function addDetail($json)
	{
		$this->details[]=$json;
	}

	/**
	 * Desenha um quadro (célula) para um campo
	 *
	 * @author	Giuliano Nascimento
	 * @version	4.0 16/07/2016
	 * @param  array $flds Campos no formato de um array associativo
	 */
	function frame($key, $value)
	{
		global $o;
		if ($value=="")
			$value='&nbsp;';
		return('<-'. $o->small($key).'<br>'.$value);
	}

	/**
	 * Desenha um quadro (tabela) com os campos informados
	 *
	 * @author	Giuliano Nascimento
	 * @version	4.0 16/07/2016
	 * @param  array $flds Campos no formato de um array associativo
	 */
	function showHeader()
	{
		global $o;
		$this->html.=$o->tableBegin('big', true);
		if (isset($this->jarr['headerMaxColumns']))
		{
			$mtz='';
			$cnt=0;
			foreach ($this->tableFields as $key=>$value)
			{
				if (!isset($this->hideFields[$key]))
				{
					$cnt++;
					$mtz[]=$this->frame($this->tableFields[$key]['fieldLabel'], $value['formattedValue']);
					if ($cnt==$this->jarr['headerMaxColumns'])
					{
						$this->html.=$o->tableRow($mtz,'header');
						$mtz='';
						$cnt=0;
					}
				}
			}
			if (is_array($mtz))
			{
				for ($a=$cnt; $a<$this->jarr['headerMaxColumns']; $a++)
					$mtz[]='';
				$this->html.=$o->tableRow($mtz,'header');
			}

		} else
		{
			$mtz='';
			foreach ($this->tableFields as $key=>$value)
			{
				if (!isset($this->hideFields[$key]))
					$mtz[]=$this->frame($this->tableFields[$key]['fieldLabel'], $value['formattedValue']);
			}
			$this->html.=$o->tableRow($mtz,'header');

		}

		$this->html.=$o->tableEnd();
	}


	/**
	 * Monta barra de botões
	 *
	 * @author	Giuliano Nascimento
	 * @version	4.0 16/07/2016
	 * @param  array $flds Campos no formato de um array associativo
	 */
	function showButtons()
	{
		global $o, $gPage, $gId;

		$gParam = gCleanField($_REQUEST['gParam']);

		$btns='';
		$btns[]=$o->button("{active: ".$active0."; icon: home; style: info; hint: Capa; responsive: true; href: ".$o->page."&gPage=1&gId=".$gId."}");
		$btns[]=
			'<div class="btn-group" role="group" aria-label="...">'.
				$o->button("{icon: arrow-left; style: info; hint: Registro anterior; href: ".$o->page."&gPage=100&gId=".$gId."&gIdRel=".$gPage."}").
				$o->button("{icon: arrow-right; style: info; hint: Próximo registro; href: ".$o->page."&gPage=101&gId=".$gId."&gIdRel=".$gPage."}").
			'</div>';
		foreach ($this->details as $detail)
		{
			$arr = cssDecode($detail);
			if (isset($arr['gPage']))
			{
				$arr['href'] = $o->page.'&gPage='.$arr['gPage'].'&gId='.$gId;
			}
			$button=cssEncode($arr);
			$btns[]=$o->button($button);
		}
		$this->html.=implode(" ",$btns);
		$this->html.=$o->hr('soft');
	}

	/**
	 * Processa o tipo de página solicitada
	 *
	 * @author	Giuliano Nascimento
	 * @version	4.0 16/07/2016
	 * @return  string $html HTML da página
	 */
	function process()
	{
		global $gPage, $gId, $o;

		$aligns='';
		$aligns['left']='<-';
		$aligns['right']='->';
		$aligns['center']='<>';

		switch ($gPage)
		{
			case 0:
				/** Página inicial - Criar novo, pesquisar ou abrir um registro recente */

				$sql=$this->queries[0];
				$this->db = new gDB();
				$this->db->parseQueryOnServer($sql);

				if (count($_REQUEST)>1)
				{
					$this->db->parseQuerySections($sql);
					$flt='';
					foreach ($_REQUEST as $key=>$value)
					{
						if ($key<>'g' && $key<>'gPage' && $key<>'gId' && $key<>'submit_default')
						{
							$type=$this->db->associativeFields[$key]['type'];
							$name = str_replace('__','.',$key);
							if (isset($this->filters[$key]['where']))
								$name = $this->filters[$key]['where'];
							switch ($type)
							{
								case 'text':
								case 'textarea':
								case 'upperText':
								case 'lowerText':
								case 'firstWordUpperText':
								case 'upperFirstWordText':
									$flt[$key]=$name." LIKE '%".$value."%'";
									break;

								case 'date':
									if ($value<>'' && $value<>'0000-00-00')
										$flt[$key]=$name."='".formatDBValueByType($type,$value)."'";
									break;
								case 'dateTime':
								case 'datetime':
									if ($value<>'' && $value<>'0000-00-00 00:00:00')
										$flt[$key]=$name."='".formatDBValueByType($type,$value)."'";
									break;
								case 'combo':
									if ($value<>"0")
										$flt[$key]=$name."='".formatDBValueByType($type,$value)."'";
									break;

								default:
									if (substr($key,0,3)<>'id_' || $value<>'0')
										$flt[$key]=$name."='".formatDBValueByType($type,$value)."'";
									break;
							}
						}
					}
					if (is_array($flt))
					{
						$sql="SELECT ".$this->db->sections['select']."\nFROM ".$this->db->sections['from']."\nWHERE ".implode(" AND ",$flt);
						if ($this->db->sections['group by']<>'')
							$sql.="\nGROUP BY ".$this->db->sections['group by'];
						if ($this->db->sections['order by']<>'')
							$sql.="\nORDER BY ".$this->db->sections['order by'];
						$sql.="\nLIMIT 1000";
					}
				}

				$this->sql = $sql;
				break;

			/**
			 Avançar e voltar registros
			*/
			case 100:

				$gIdRel=(int)$_REQUEST['gIdRel'];
				$sql=$this->queries[0];
				$rs=dbQuery($sql);
				$max=count($rs);
				$cnt=0;$achou=false;$idAnt=$id=$gId;
				while (!$achou)
				{
					$row=$rs[$cnt];
					if ($rs[$cnt]['id']==$gId)
					{
						$achou=true;
						$id=$idAnt;
					}
					$idAnt=$rs[$cnt]['id'];
					++$cnt;
					if ($cnt>$max)
						$achou=true;
				}
				redirect($o->page."&gPage=".$gIdRel."&gId=".$id);
				break;

			case 101:

				$gIdRel=(int)$_REQUEST['gIdRel'];
				$sql=$this->queries[0];
				$rs=dbQuery($sql);
				$max=count($rs);
				$cnt=0;$achou=false;$id=$gId;
				while (!$achou)
				{
					$row=$rs[$cnt];
					if ($rs[$cnt]['id']==$gId)
					{
						$achou=true;
						++$cnt;
						$id=$rs[$cnt]['id'];
						if ($id==0)
							$id=$gId;
					}
					++$cnt;
					if ($cnt>$max)
						$achou=true;
				}
				redirect($o->page."&gPage=".$gIdRel."&gId=".$id);
				break;

			default:
				/** Página inicial - Mostra os detalhes do registro atual */

				$sql=$this->queries[0];
				$this->db = new gDB();
				$this->db->parseQueryOnServer($sql);
				$this->tableFields=$this->db->associativeFields;
				$this->parseDictionaries();

				$flt=($this->db->fields[0]['table']<>'' ? $this->db->fields[0]['table'].'.' : '') . $this->db->fields[0]['name'] . '=' . $gId;
				$sql="SELECT ".$this->db->sections['select']."\nFROM ".$this->db->sections['from']."\nWHERE ".$flt;
				if ($this->db->sections['group by']<>'')
					$sql.="\nGROUP BY ".$this->db->sections['group by'];
				$rs=dbQuery($sql);
				$flds='';
				foreach ($rs[0] as $key=>$value)
				{
					if (!is_numeric($key))
					{
						$this->tableFields[$key]['value']=$value;
						$value=formatValueByType($key, $this->db->associativeFields[$key]['type'], $value, $this->combos);
						$this->tableFields[$key]['formattedValue']=$value;
						$flds[$key]=$value;
					}
				}
				break;

		}

	}

	/**
	 * Processa toda a inteligência e retorna HTML
	 *
	 * @author	Giuliano Nascimento
	 * @version	4.0 16/07/2016
	 * @param  object $o Objeto gInput ou gOutput instanciado
	 * @return  string $html HTML da página
	 */
	function render(&$o)
	{
		global $gPage, $gId, $o;

		if (!$_REQUEST['gAjs']==1)
		{
			$this->html=$o->msgTitle($this->jarr['title']);


			$aligns='';
			$aligns['left']='<-';
			$aligns['right']='->';
			$aligns['center']='<>';

			switch ($gPage)
			{
				case 0:
					/** Página inicial - Criar novo, pesquisar ou abrir um registro recente */

					if (isset($this->jarr['message']))
					{
						$this->html.=$this->jarr['message']."<br><br>";
					}
					if (strpos($this->jarr['permissions'],'I')!==false)
						$this->html.=$o->button("{icon: plus; caption: Novo; hint: Incluir um novo registro; style: info; size: normal; href: ".$o->page."&gPage=3}");
					if (strpos($this->jarr['permissions'],'S')!==false)
						$this->html.=$o->button("{icon: search; caption: Pesquisar; hint: Pesquisar por um registro pré-existente; style: default; size: normal; href: ".$o->page."&gPage=2}");
					foreach ($this->buttons as $btn)
					{
						$this->html.=$o->button($btn);
					}


					$rs=dbQuery($this->sql);
					$this->html.='<br><br>';
					if (count($rs)>0)
					{
						$this->html.=$o->tableBegin('big', true, true);
						$row=$rs[0];
						$rows='';
						$rows[]='<-'.gT('options');
						foreach ($row as $key=>$value)
						{
							if (!is_numeric($key) && ($key<>'idd') && (!isset($this->hideFields[$key])))
							{
								$rows[] = $aligns[$this->db->associativeFields[$key]['align']].gField2String($key);
							}
						}
						$this->html.=$o->tableRow($rows,'header');
						$cnt=0;
						foreach ($rs as $row)
						{
							$cnt++;
							$rows='';
							$rows[]='<-'.$o->button("{icon: folder-open; title: Abrir; size: small; href: ".$o->page."&gPage=1&gId=".$row['id']."}");
							if  ($row['numerar_itens']==1)
							{
								$rows[]='->'.$cnt;
							}
							foreach ($row as $key=>$value)
							{
								if (!is_numeric($key) && ($key<>'idd') && (!isset($this->hideFields[$key])))
								{
									$rows[]=$aligns[$this->db->associativeFields[$key]['align']] . formatValueByType($key, $this->db->associativeFields[$key]['type'], $value, $this->combos);
								}
							}
							$this->html.=$o->tableRow($rows,'detail');
						}
						$this->html.=$o->tableEnd();

					} else
					{
						$this->html.=$o->msgWarning("Nenhum registro encontrado");
					}
					break;

				case 2:
					/** Pesquisar */

					$frm = new gForm("{columns: 2}");
					if (is_array($this->filters))
					{
						foreach ($this->filters as $flt)
						{
							$extra='';
							$name = $flt['name'];
							if (isset($flt['fieldLabel']))
								$fieldLabel = $flt['fieldLabel'];
							else
								$fieldLabel = gField2String($name);
							$type = $flt['type'];
							$value = $flt['value'];
							if ($type=='combo')
								$extra.='items: '.$flt['items'];
							$frm->add("{name: $name; fieldLabel: $fieldLabel; type: $type; value: $value; $extra}");
						}
					} else
					{

					}
					$frm->add("{name: gPage; type: hidden; value: 0}");
					$this->html.=$frm->render($o);
					break;

				case 3:
					break;

				default:
					/** Página inicial - Mostra os detalhes do registro atual */

					$this->showHeader();
					$this->showButtons();
					break;

			}
		}

		return($this->html);
	}
}
?>
