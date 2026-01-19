<?

include_once $gPathLib.$setup->get("lib.adodb");


/* TODO
 *
 * $ADODB_ASSOC_CASE para mudar case sensitive
 *
*/
define(gD_DEFAULT,'');
define(gD_NOTRANS,0);
define(gD_BEGINTRANS,1);
define(gD_INTRANS,2);
define(gD_ENDTRANS,3);

define(gQ_SELECT,1);
define(gQ_INSERT,2);
define(gQ_UPDATE,4);
define(gQ_DELETE,8);

define(gE_SYNTAX,'Erro de sintaxe');
define(gE_DATABASE,'Erro no banco de dados');
define(gE_DATABASEQUERY,'Erro na expressão de acesso ao banco de dados.');

$setup->set("database.lasttransaction",gD_NOTRANS);
$setup->set("database.lastquery","");


// Para manter compatibilidade retroativa (gFW 2.0)
function gFastQuery($sql,$bd=gD_DEFAULT,$fetch=0,$transaction=gD_NOTRANS)
{
	return(gDB::run($sql,$fetch,$transaction,-1,-1,false));
}
function sql2idd($sql)
{
	$id=intval($_SESSION['usrId']);
	$idd=intval($_SESSION['usrIdd']);
	if ((is_developer) && ($id==1))
	{
		$id=$_SESSION['usrAppId'];
		$idd=$_SESSION['usrAppId'];
	}

	if ($idd==0)
		$idd=1;
	if ($id>1)
	{
		if (gVar("database.idd")=="true")
		{
			$sec=parseQuerySections($sql);
			// SELECT - Adiciona filtro por idd
			if (isset($sec['select']))
			{
				$tab=parseQueryTables($sec["from"]);
				$flt="";
				$primeiro=true;
				foreach ($tab as $t)
				{
					if ($primeiro)
					{
						// Não incui o idd para tabelas em outros bancos de dados
						if (strpos($t['name'],".")===false)
						{
							$alias=$t['alias'];
							if ($alias<>"")
								$alias.=".";
							$flt[]="(".$alias."idd=$idd or ".$alias."idd=0 or ".$alias."idd is null)";
						}
					}
					$primeiro=false;
				}
				if (is_array($flt))
				{
					$flt=implode(" and ",$flt);
					if (isset($sec['where']))
					{
						$sec["where"]="($flt) and ".$sec["where"];
					} else
					{
						$sec["where"]="($flt)";
					}
					$sql="SELECT ".$sec['select']." FROM ".$sec['from']." WHERE ".$sec['where'];
					if (isset($sec['group by']))
						$sql.=" GROUP BY ".$sec["group by"];
					if (isset($sec['order by']))
						$sql.=" ORDER BY ".$sec["order by"];
					if (isset($sec['limit']))
						$sql.=" LIMIT ".$sec["limit"];
				}
			}
			// INSERT - Adiciona campo idd
			if (isset($sec['insert']))
			{
				$sql = preg_replace("/[ \t\n\r][Vv][Aa][Ll][Uu][Ee][Ss][ \t\n\r]/", " VALUES ", $sql);
				$div=explode(" VALUES",$sql);
				if (isset($div[0]))
				{
					if (strpos($div[0],"idd,")===false)
					{
						$fld=explode("(",$div[0]);
						$sql=$fld[0]."(idd,";
						for ($a=1; $a<count($fld); $a++)
							$sql.=$fld[$a];
						$sql.=" VALUES ($idd, ".substr(trim($div[1]),1);
					} else
					{
						$fld=explode("(",$div[0]);
						$sql=$fld[0]."(";
						for ($a=1; $a<count($fld); $a++)
							$sql.=$fld[$a];
						$sql.=" VALUES (".substr(trim($div[1]),1);
					}
				}
				//gLog("====> \n$sql");
			}
			// DELETE - Evita apagar idd=0 e idd<>do seu
			if (isset($sec['delete']))
			{
				$flt="(idd=$idd)";
				$sql="DELETE ".$sec['delete']." FROM ".$sec['from'];
				$sql.=" WHERE ".$flt;
				if (isset($sec['where']))
				{
					$sql.=" and (".$sec['where'].")";
				}
			}
			if (isset($sec['update']))
			{
				$flt="(idd=$idd)";
				$sql="UPDATE ".$sec['update']." WHERE ".$flt;
				if (isset($sec['where']))
				{
					$sql.=" and (".$sec['where'].")";
				}
			}
		}
	}
	return($sql);
}

function gQuery($sql,$bd=gD_DEFAULT,$fetch=0,$transaction=gD_NOTRANS)
{
	return(gDB::run($sql,$fetch,$transaction));
}
function gQueryLimit($sql,$ini=1, $qtd=9999999, $bd="_default",$fetch=0) {	return(gDB::runLimit($sql,$ini,$qtd,$fetch)); }
function gFieldById($table,$id,$field=2) { return(gDB::fieldById($table,$id,$field)); }

/** Retorna seções encontradas em uma Query
 * @author	Giuliano
 * @version	2009-10-13
 * @param string $sql Query completa
 * @return array $var Seções e conteúdos
 */
function parseQuerySections($sql)
{

	$posSec[] = array("SELECT ", "^[ \t\n\r]?[Ss][Ee][Ll][Ee][Cc][Tt][ \t\n\r]");
	$posSec[] = array("UPDATE ", "^[ \t\n\r]?[Uu][Pp][Dd][Aa][Tt][Ee][ \t\n\r]");
	$posSec[] = array("DELETE", "^[ \t\n\r]?[Dd][Ee][Ll][Ee][Tt][Ee]");
	$posSec[] = array("INSERT ", "^[ \t\n\r]?[Ii][Nn][Ss][Ee][Rr][Tt][ \t\n\r]");
	$posSec[] = array("REPLACE ", "^[ \t\n\r]?[Rr][Ee][Pp][Ll][Aa][Cc][Ee][ \t\n\r]");
	$posSec[] = array(" FROM ", "[ \t\n\r][Ff][Rr][Oo][Mm][ \t\n\r]");
	$posSec[] = array(" WHERE ", "[ \t\n\r][Ww][Hh][Ee][Rr][Ee][ \t\n\r]");
	$posSec[] = array(" GROUP BY ", "[ \t\n\r][Gg][Rr][Oo][Uu][Pp] [Bb][Yy][ \t\n\r]");
	$posSec[] = array(" ORDER BY ", "[ \t\n\r][Oo][Rr][Dd][Ee][Rr] [Bb][Yy][ \t\n\r]");
	$posSec[] = array(" HAVING ", "[ \t\n\r][Hh][Aa][Vv][Ii][Nn][Gg][ \t\n\r]");
	$posSec[] = array(" LIMIT ", "[ \t\n\r][Ll][Ii][Mm][Ii][Tt][ \t\n\r]");

	// Pra evitar a confusão entre termos que estão entre as aspas simples ou duplas
	$ativo="";
	for ($a=0; $a<strlen($sql); $a++)
	{
		if ($sql[$a]=="'")
		{
			if ($ativo=="")
				$ativo="'";
			else
				$ativo="";
		}
		if ($sql[$a]=='"')
		{
			if ($ativo=="")
				$ativo='"';
			else
				$ativo="";
		}
		if (($ativo<>"") && ($sql[$a]==" "))
			$sql[$a]="~";
	}
	foreach ($posSec as $ereg)
	{
		$sql = preg_replace('/'.$ereg[1].'/', $ereg[0], $sql);
	}
	foreach ($posSec as $section)
	{
		$sectionsPos[$section[0]]=strpos($sql,$section[0]);
	}
	foreach ($sectionsPos as $key=>$value)
	{
		if ($value!==false)  // tem seção, então, busca o final
		{
			$maxpos=strlen($sql);
			foreach ($sectionsPos as $nkey=>$nvalue)
			{
				if ($nvalue!==false)
				{
					if (($nvalue<$maxpos) && ($nvalue>$value))
					{
						$maxpos=$nvalue;
					}
				}
			}
			$sections[trim(strtolower($key))]=str_replace("~"," ",trim(substr($sql,$value+strlen($key),$maxpos-$value-strlen($key))));
		}
	}
	return ($sections);
}

/** Retorna tabelas encontradas em uma Query
 * @author	Giuliano
 * @version	2009-10-13
 * @param string $sql Query (somente conteúdo entre o FROM e outra seção
 * @return array $var Tabelas e parâmetros
 */
function parseQueryTables($sql)
{
	$sql = preg_replace("/[ \t\n\r][Ll][Ee][Ff][Tt] [Jj][Oo][Ii][Nn][ \t\n\r]/", " LEFT JOIN ", $sql);
	$sql = preg_replace("/[ \t\n\r][Rr][Ii][Gg][Hh][Tt] [Jj][Oo][Ii][Nn][ \t\n\r]/", " RIGHT JOIN ", $sql);
	$sql = preg_replace("/[ \t\n\r][Ii][Nn][Nn][Ee][Rr] [Jj][Oo][Ii][Nn][ \t\n\r]/", " INNER JOIN ", $sql);
	$sql = preg_replace("/[ \t\n\r][Jj][Oo][Ii][Nn][ \t\n\r]/", " JOIN ", $sql);
	$sql = preg_replace("/[ \t\n\r][Oo][Nn][ \t\n\r]/", " ON ", $sql);

	$t=explode(" JOIN ",$sql);
	$nextJoin="";
	foreach ($t as $tdata)
	{
		$tdata=str_replace("  "," ",$tdata);
		$table="";
		//$table['data']=$tdata;
		if ($nextJoin<>"")
		{
			$table['join']='left';
			$nextJoin="";
		}
		if (strpos($tdata," LEFT")!==false)
		{
			$nextJoin="left";
			$tdata=str_replace(" LEFT","",$tdata);
		}
		if (strpos($tdata," RIGHT")!==false)
		{
			$nextJoin="right";
			$table['join']='right';
			$tdata=str_replace(" RIGHT","",$tdata);
		}
		if (strpos($tdata," INNER")!==false)
		{
			$nextJoin="inner";
			$tdata=str_replace(" INNER","",$tdata);
		}
		if (strpos($tdata," ON ")!==false)
		{
			$table['on']=substr($tdata,strpos($tdata," ON ")+4);
			$tdata=substr($tdata,0,strpos($tdata," ON "));
		}

		//$tdata=str_replace(" l"," ",$tdata);
		$prox=0;
		$tdata=str_replace("\n"," ",$tdata);
		$tmp=explode(" ",$tdata);
		$table['name']=str_replace("\n","",trim($tmp[$prox]));
		$prox++;
		if (strtolower($tmp[$prox])=="as") {
			$prox++;
			$table['alias']=str_replace("\n","",trim($tmp[$prox]));
			$table['fieldLabel']=$tmp[$prox];
		} else
		{
			if ($tmp[$prox]<>"") {
				$table['alias']=str_replace("\n","",trim($tmp[$prox]));
				$table['fieldLabel']=$tmp[$prox];
			} else
			{
				$table['alias']=str_replace("\n","",trim($table['name']));
				$table['fieldLabel']=$table['name'];
			}
		}
		$tables[]=$table;
	}
	return($tables);
}

/** Formata Query para saída na tela
 * @author	Giuliano
 * @version	2009-10-13
 * @param string $sql Query
 */
function formatQuery($sql)
{
	$sections=parseQuerySections($sql);
	foreach ($sections as $key=>$value)
	{
		$sai.=strtoupper($key)."\n\t$value\n";
	}
	return($sai);
}


class gDataDictionary
{
	public $dictionary,$arrays;

	function __construct()
   {
		$this->dictionary="";
	}

	/** Adiciona entrada de dicionário
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $json
	 */
	function add($json,$array)
	{
		if ($json<>"")
		{
			$mtz=jsonDecode($json,";",false);
			$name=stripQuote($mtz['name']);
			$mtz=jsonRemove($mtz,"name");
			$this->dictionary[$name]=$mtz;
			$this->arrays[$name]=$array;
		}
	}

	function set($mtz)
	{
		$this->dictionary=$mtz;
	}

	function get()
	{
		return($this->dictionary);
	}

}

class gDataFilter
{
	public $filter,$arrays;

	function __construct()
   {
		$this->filter="";
		$this->arrays="";
	}

	/** Adiciona entrada
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query
	 * @return array $var Campos e parâmetros
	 */
	function add($json,$array="")
	{
		if ($json<>"")
		{
			$mtz=jsonDecode($json,";",false);
			$name=stripQuote($mtz['name']);
			//$mtz=jsonRemove($mtz,"name");
			$this->filter[]=$mtz;
			$this->arrays[]=$array;
		}
	}

	function set($mtz)
	{
		$this->filter=$mtz;
	}

	function get()
	{
		return($this->filter);
	}

	function getArrays()
	{
		return($this->arrays);
	}

}

class gDataParameters
{
	public $parm;

	function __construct()
   {
		$this->parm="";
	}

	/** Adiciona entrada
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query
	 * @return array $var Campos e parâmetros
	 */
	function add($json)
	{
		/*
		if ($json<>"")
		{
			$mtz=jsonDecode($json,";",false);
			$name=stripQuote($mtz['name']);
			//$mtz=jsonRemove($mtz,"name");
			$this->parm[]=$mtz;
		}
		 */
		$this->parm[]=$json;
	}

	function set($mtz)
	{
		$this->parm=$mtz;
	}

	function get()
	{
		return($this->parm);
	}
}


class gDB
{
   public $sections;
	public $tables;
	public $fields;
	public $querys;
	public $query;
	public $types;
	private $md5;

   function __construct()
   {
      $this->sections="";
      $this->tables="";
      $this->fields="";
		$this->dictionary="";
		$this->querys="";
		$this->query="";
		$this->types["C"]="text";
		$this->types["D"]="date";
		$this->types["T"]="datetime";
		$this->types["N"]="number";
		$this->types["I"]="integer";
		$this->types["R"]="integer";
		$this->types["X"]="textarea";
		$this->types["B"]="memo";
		$this->types["L"]="checkbox";
		$this->types["W"]="password";
		$this->types["U"]="url";
		$this->types["E"]="email";
		$this->types["A"]="plate";
		$this->types["F"]="cpf";
		$this->types["J"]="cnpj";
		$this->types["P"]="ip";
		$this->types["H"]="container";
		$this->types["M"]="time";
		$this->types["O"]="ncm";
   }

	/** Gera um "recordset" com a configuração padrão do banco de dados
	* @author Giuliano
	* @param String $sql Query
	* @param String $bd Banco de dados (opcional se for utilizado o padrão definido do arquivo de configuração)
	* @param int $fetch Modo do "fetch" (verifique a documentação do ADOdb)
	* @return Recordset
	*/
	static function run($sql,$fetch=0,$transaction=gD_NOTRANS,$ini=-1,$qtd=-1,$iddControl=true)
	{
		global $setup;
		global $http_base;
		global $http_inc;
		global $http_css,$http_system_css;
		global $http_img;
		global $http_lib;

		$sqlOriginal=$sql;
		if ($iddControl)
			$sql=sql2idd($sql);

		//$bd=$setup->get("database.db");
		$gDebug=$setup->get("global.debug");
		$gDebug=10;

		$setup->set("database.lastquery",$sql);
		$setup->set("database.lasttransaction",$transaction);

		//if ($bd=="")
			$bd=$setup->get("database.name");
		$bd=str_replace(".dbo","",$bd);
		if ($fetch==0)
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		else
			$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		if ($transaction<=1)
		{
			$db = ADONewConnection($setup->get("database.engine"));

			if (gVar("database.engine")=="oci8")
			{
				$db->NLS_LANG="BRAZIL_BRAZILIAN PORTUGUESE.WE8ISO8859P1";
				//$db->NLS_LANG="AMERICAN_AMERICA.WE8ISO8859P1";
				//$db->NLS_LANG="BRAZIL_BRAZILIAN PORTUGUESE.AL24UTFFSS";

				$db->NLS_DATE_FORMAT =  'YYYY-MM-DD';
				$db->NLS_TIMESTAMP_FORMAT =  'YYYY-MM-DD HH24:MI:SS';
				$db->PConnect($setup->get("database.url"), $setup->get("database.user"), $setup->get("database.password"), $bd);
				$db->SetFetchMode($ADODB_FETCH_MODE);
			}
			else
			{

				if ((strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') && (strpos(gVar("database.engine"),"mssql")!==false))
				{
					$dsn="Driver={SQL Server};Server=".$setup->get("database.url").";Database=".$setup->get("database.name");
					$db = &ADONewConnection($setup->get("database.engine"));
					$db->Connect($dsn, $setup->get("database.user"), $setup->get("database.password"));
					$db->SetFetchMode($ADODB_FETCH_MODE);
				} else
				{
					if (substr(gVar("database.engine"),0,5)<>"mysql")
					{
						$db->SetFetchMode($ADODB_FETCH_MODE);
						$transaction=gD_NOTRANS;
					}
					$db->Connect($setup->get("database.url"), $setup->get("database.user"), $setup->get("database.password"), $bd);
				}
			}

			/* TODO
			 * Ajusta resultado das querys para o charset atual
			 * (só resolvido para o MySQL. Checar demais bancos!
			 */
			if (($setup->get("database.charset")=="UTF-8") && (substr($setup->get("database.engine"),0,5)=="mysql"))
			{
				$db->Execute("SET NAMES 'utf8'");
				$db->Execute('SET character_set_connection=utf8');
				$db->Execute('SET character_set_client=utf8');
				$db->Execute('SET character_set_results=utf8');
			}
			//$db->SetFetchMode($ADODB_FETCH_MODE);
		}
		$gError="";
		if (($db->ErrorMsg()<>"") && (substr(trim($db->ErrorMsg()),0,27)<>"Changed database context to"))
		{
			//$sql="\n\n".formatQuery($sqlOriginal)."\n";
			if ($gDebug>0)	{ gLog("SQL (Error): $bd - ".$db->ErrorMsg().$sql,LOG_ERROR);}
			$gError=gE_DATABASE." (".$db->ErrorMsg().")";
		} else
		{
			if ($transaction==gD_BEGINTRANS)
			{
				$db->StartTrans();
			}
			//$rs = $db->Execute($sql) or $gError=gE_DATABASEQUERY;
			if ($ini>-1)
			{
				$rs = $db->SelectLimit($sql,$qtd,$ini);
			} else
			{
				$rs = $db->Execute($sql);
			}
			if ((gVar("database.type")=="oci8") && ($transaction==gD_NOTRANS))
			{
					$db->Execute("COMMIT;");
			}

			if ($transaction==gD_ENDTRANS)
			{
				$db->CompleteTrans();
			}

			if ((!$rs) )
			{
				$gError=$db->ErrorMsg();
				if ($gDebug>0)
				{
					gLog("SQL (Error): $bd - ".$gError."\t[ $sql ]",LOG_ERROR);
					$sql=formatQuery($sqlOriginal);
					$sqlf=str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",nl2br($sql));
					$sqlf=str_replace("SELECT","<b>SELECT</b>",$sqlf);
					$sqlf=str_replace("DELETE","<b>DELETE</b>",$sqlf);
					$sqlf=str_replace("UPDATE","<b>UPDATE</b>",$sqlf);
					$sqlf=str_replace("INSERT","<b>INSERT</b>",$sqlf);
					$sqlf=str_replace("FROM","<b>FROM</b>",$sqlf);
					$sqlf=str_replace("WHERE","<b>WHERE</b>",$sqlf);
					$sqlf=str_replace("GROUP","<b>GROUP</b>",$sqlf);
					$sqlf=str_replace("ORDER","<b>ORDER</b>",$sqlf);
					if ($_SESSION['usrId']<>'')
					{
						echo "
	<!DOCTYPE html>
		<html>
		<head>
		<title>".gVar("global.site")."</title>
		<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
		<link href='https://fonts.googleapis.com/css?family=Cabin' rel='stylesheet' type='text/css'>
		<link href='https://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'>
		<link href='https://fonts.googleapis.com/css?family=Josefin+Sans' rel='stylesheet' type='text/css'>
		<link href='https://fonts.googleapis.com/css?family=Dancing+Script' rel='stylesheet' type='text/css'>
		<link href='".$http_css."/styleWeb.css' rel='stylesheet' type='text/css'>
		<link href='".$http_system_css."/icons.css' rel='stylesheet' type='text/css'></link>
		</head>
		<body>
		<span class='g-msg-title' style='padding: 4px; '>".gT("&nbsp;Erro de banco de dados")."</span>
		<span class='g-msg-subtitle'style='padding: 4px; '>".gT("&nbsp;Verifique se as informações digitadas estão corretas.")."</span>
		";
						if ($_SESSION['appDevel']>0)
						{
							echo "&nbsp;<span class='g-msg-error' style='padding: 4px; '>".$gError."</span>";
							echo "<div style='padding: 14px; text-align: left; z-index:100; width:80%;  color: black; '><br><br><p style='text-align: left'>".$sqlf."</p></div></acronym>";
						}
						echo "</body></html>";
					} else
					{
						echo "Tempo sem atividade alcançado.<br>Sua sessão expirou. Faça o login novamente.";
					}
					//exit;
					echo "Query: $sql<br>";
exit;
				}
			} else
			{
				if ($gDebug>0)
				{
					gLog("SQL($bd-$transaction):\t".$sql);
				}
			}
		}
		//$setup->set("database.db",$bd);
		return $rs;
	}


	/** Gera um "recordset" com a configuração padrão do banco de dados, com um retorno limitado de registros
	* @author Giuliano
	* @param String $arg Query
	* @param String $bd Banco de dados (opcional se for utilizado o padrão definido no arquivo de  configuração)
	* @param int $fetch Modo do "fetch" (verifique a documentação do ADODb)
	* @param int $ini	Registro inicial
	* @param int $qtd	Quantidade de registros
	* @return recordset
	*/
	static function runLimit($sql,$ini=1, $qtd=9999999, $fetch=0)
	{
		global $gError;
		global $setup;
		$gDebug=$setup->get("global.debug");

		$sqlOriginal=$sql;
		$sql=sql2idd($sql);

		$bd=gVar("database.name");
		//$sql=func_get_arg($arg);
		if ($fetch==0)
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		else
			$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		$db = ADONewConnection($setup->get("database.engine"));

		if (gVar("database.engine")=="oci8")
		{
			$db->NLS_LANG="BRAZIL_BRAZILIAN PORTUGUESE.WE8ISO8859P1";
			//$db->NLS_LANG="AMERICAN_AMERICA.WE8ISO8859P1";
			//$db->NLS_LANG="BRAZIL_BRAZILIAN PORTUGUESE.AL24UTFFSS";

			$db->NLS_DATE_FORMAT =  'YYYY-MM-DD';
			$db->NLS_TIMESTAMP_FORMAT =  'YYYY-MM-DD HH24:MI:SS';
			$db->PConnect($setup->get("database.url"), $setup->get("database.user"), $setup->get("database.password"), $bd);
			$db->SetFetchMode($ADODB_FETCH_MODE);
		}
		else
		{
			if ((strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') && (strpos($setup->get("database.engine"),"mssql")!==false))
			{
				$dsn="Driver={SQL Server};Server=".$setup->get("database.url").";Database=".$setup->get("database.name");
				$db = &ADONewConnection($setup->get("database.engine"));
				$db->Connect($dsn, $setup->get("database.user"), $setup->get("database.password"));
				$db->SetFetchMode($ADODB_FETCH_MODE);
			} else
			{
				if (substr(gVar("database.engine"),0,5)<>"mysql")
				{
					$db->SetFetchMode($ADODB_FETCH_MODE);
					$transaction=gD_NOTRANS;

				}
				$db->PConnect($setup->get("database.url"), $setup->get("database.user"), $setup->get("database.password"), $bd);
			}
		}
		/* TODO
		 * Ajusta resultado das querys para o charset atual
		 * (só resolvido para o MySQL. Checar demais bancos!
		 */
		if ((gVar("global.charset")=="UTF-8") && (substr($setup->get("database.engine"),0,5)=="mysql"))
		{
	/*
			$db->Execute("SET NAMES 'utf8'");
			$db->Execute('SET character_set_connection=utf8');
			$db->Execute('SET character_set_client=utf8');
			$db->Execute('SET character_set_results=utf8');
	*/
		}
		$gError="";
		if (($db->ErrorMsg()<>"") && (substr($db->ErrorMsg(),0,28)<>"Changed database context to "))
		{
			if ($gDebug>0)	{ gLog("SQL (Error): ".$db->ErrorMsg()."\n".$sql,LOG_ERROR);}
			$gError=gE_DATABASE." (".$db->ErrorMsg().")";
		} else
		{
			if ($gDebug>0)
				gLog("SQLim($bd,$ini,$qtd):\t".$sqlOriginal);
			$rs = $db->SelectLimit($sql,$qtd,$ini) or $gError=gE_DATABASEQUERY;
		}
		return $rs;
	}

	/** Retorna com o valor de um campo da tabela em referência, tendo como índice o campo id
	* @author Giuliano
	* @param String $table Nome da tabela
	* @param int $id Valor do campo id
	* @param Variable $field Pode ser o número do campo da tabela ou seu nome
	* @return String valor do campo
	*/
	function fieldById($table,$id,$field=2)
	{
		global $gLastTransaction;
		$r="";
		if (!is_null($id))
		{
			if (intval($field)>0)
			{
				$sql="Select * from $table where id=$id";
				$rs=gQuery($sql,gD_DEFAULT,0,$gLastTransaction);
				if ($rs->EOF)
					$r=gLng("includes.gselect.long");
				else
					$r=$rs->fields[$field];
			} else
			{
				$sql="Select $field from $table where id=$id";
				$rs=gQuery($sql,gD_DEFAULT,0,$gLastTransaction);
				if ($rs->EOF)
					$r=gT("* Indiferente");
				else
				$r=$rs->fields[$field];
			}
		}
		return($r);
	}

	/** Retorna campos encontrados em uma Query
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query
	 * @return array $var Campos e parâmetros
	 */
	function parseQueryFields($sql)
	{
		$sql=str_ireplace("distinct ", "", $sql);
		$sql = preg_replace("/[ \t\n\r][Aa][Ss][ \t\n\r]/", " AS ", $sql);
		$par=0;
		for ($a=0; $a<strlen($sql); $a++)
		{
			if ($sql[$a]=="(")
				$par++;
			if ($sql[$a]==")")
				$par--;
			if (($par>0) && ($sql[$a]==","))
				$sql[$a]="^";
		}
		$fld=explode(",",$sql);
		$fld=array_map("trim",$fld);
		// campo
		// campo outro
		// campo as outro
		// formula outro
		// formula as campo

		$defaultTable=$this->tables[0]["alias"];
		foreach ($fld as $tdata)
		{
			$tdata=str_replace("^",",",$tdata);
			$prox=0;
			if ($tdata<>"*")
			{
				$tmp=explode(" ",$tdata);
				$ult=$tmp[count($tmp)-1];
				if (strpos($ult,".")===false)
				{
					$campo['table']=$defaultTable;
					$campo['name']=$ult;
					$campo['alias']=$ult;
					$campo['fieldLabel']=gField2String($ult);
				} else
				{
					$name=explode(".",$ult);
					$campo['table']=$name[0];
					$campo['name']=$name[1];
					$campo['alias']=$name[1];
					$campo['fieldLabel']=gField2String($name[1]);
				}
				$this->fields[]=$campo;
			}
		}
		return ($this->fields);
	}

	/** Retorna tabelas encontradas em uma Query
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query (somente conteúdo entre o FROM e outra seção
	 * @return array $var Tabelas e parâmetros
	 */
	function parseQueryTables($sql)
	{
		$sql = preg_replace("/[ \t\n\r][Ll][Ee][Ff][Tt] [Jj][Oo][Ii][Nn][ \t\n\r]/", " LEFT JOIN ", $sql);
		$sql = preg_replace("/[ \t\n\r][Rr][Ii][Gg][Hh][Tt] [Jj][Oo][Ii][Nn][ \t\n\r]/", " RIGHT JOIN ", $sql);
		$sql = preg_replace("/[ \t\n\r][Ii][Nn][Nn][Ee][Rr] [Jj][Oo][Ii][Nn][ \t\n\r]/", " INNER JOIN ", $sql);
		$sql = preg_replace("/[ \t\n\r][Jj][Oo][Ii][Nn][ \t\n\r]/", " JOIN ", $sql);
		$sql = preg_replace("/[ \t\n\r][Oo][Nn][ \t\n\r]/", " ON ", $sql);
		$t=explode(" JOIN ",$sql);
		$nextJoin="";
		foreach ($t as $tdata)
		{
			$tdata=str_replace("  "," ",$tdata);
			$table="";
			//$table['data']=$tdata;
			if ($nextJoin<>"")
			{
				$table['join']='left';
				$nextJoin="";
			}
			if (strpos($tdata," LEFT")!==false)
			{
				$nextJoin="left";
				$tdata=str_replace(" LEFT","",$tdata);
			}
			if (strpos($tdata," RIGHT")!==false)
			{
				$nextJoin="right";
				$table['join']='right';
				$tdata=str_replace(" RIGHT","",$tdata);
			}
			if (strpos($tdata," INNER")!==false)
			{
				$nextJoin="inner";
				$tdata=str_replace(" INNER","",$tdata);
			}
			if (strpos($tdata," ON ")!==false)
			{
				$table['on']=substr($tdata,strpos($tdata," ON ")+4);
				$tdata=substr($tdata,0,strpos($tdata," ON "));
			}

			//$tdata=str_replace(" l"," ",$tdata);
			$prox=0;
			$tdata=str_replace("\n"," ",$tdata);
			$tmp=explode(" ",$tdata);
			$table['name']=str_replace("\n","",trim($tmp[$prox]));
			$prox++;
			if (strtolower($tmp[$prox])=="as") {
				$prox++;
				$table['alias']=str_replace("\n","",trim($tmp[$prox]));
				$table['fieldLabel']=$tmp[$prox];
			} else
			{
				if ($tmp[$prox]<>"") {
					$table['alias']=str_replace("\n","",trim($tmp[$prox]));
					$table['fieldLabel']=$tmp[$prox];
				} else
				{
					$table['alias']=str_replace("\n","",trim($table['name']));
					$table['fieldLabel']=$table['name'];
				}
			}
			$this->tables[]=$table;
		}
		return($this->tables);
	}

	/** Retorna seções encontradas em uma Query
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query completa
	 * @return array $var Seções e conteúdos
	 */
	function parseQuerySections($sql)
	{

		$posSec[] = array("SELECT ", "^[ \t\n\r]?[Ss][Ee][Ll][Ee][Cc][Tt][ \t\n\r]");
		$posSec[] = array("UPDATE ", "^[ \t\n\r]?[Uu][Pp][Dd][Aa][Tt][Ee][ \t\n\r]");
		$posSec[] = array("DELETE ", "^[ \t\n\r]?[Dd][Ee][Ll][Ee][Tt][Ee][ \t\n\r]");
		$posSec[] = array("INSERT ", "^[ \t\n\r]?[Ii][Nn][Ss][Ee][Rr][Tt][ \t\n\r]");
		$posSec[] = array("REPLACE ", "^[ \t\n\r]?[Rr][Ee][Pp][Ll][Aa][Cc][Ee][ \t\n\r]");
		$posSec[] = array(" FROM ", "[ \t\n\r][Ff][Rr][Oo][Mm][ \t\n\r]");
		$posSec[] = array(" WHERE ", "[ \t\n\r][Ww][Hh][Ee][Rr][Ee][ \t\n\r]");
		$posSec[] = array(" GROUP BY ", "[ \t\n\r][Gg][Rr][Oo][Uu][Pp] [Bb][Yy][ \t\n\r]");
		$posSec[] = array(" ORDER BY ", "[ \t\n\r][Oo][Rr][Dd][Ee][Rr] [Bb][Yy][ \t\n\r]");
		$posSec[] = array(" HAVING ", "[ \t\n\r][Hh][Aa][Vv][Ii][Nn][Gg][ \t\n\r]");
		$posSec[] = array(" LIMIT ", "[ \t\n\r][Ll][Ii][Mm][Ii][Tt][ \t\n\r]");

		foreach ($posSec as $ereg) {
			$sql = preg_replace('/'.$ereg[1].'/', $ereg[0], $sql);
		}
		$this->query = $sql;
		$this->sections = parseQuerySections($sql);
		return ($this->sections);
	}

	function parseQuery($sql)
	{
		$this->md5=md5($sql);
		$this->parseQuerySections($sql);
		if (isset($this->sections['select']))
		{
			$this->parseQueryTables($this->sections['from']);
			$this->parseQueryFields($this->sections['select']);
		}
	}

	function cachePush()
	{
		$cache="";
		$cache["sections"]=$this->sections;
		$cache["tables"]=$this->tables;
		$cache["fields"]=$this->fields;
		$cache["dictionary"]=$this->dictionary;
		$cache["querys"]=$this->querys;
		$cache["query"]=$this->query;
		$cache["md5"]=$this->md5;
		$_SESSION['_cacheDB'][]=$cache;
	}

	function cachePop($sql)
	{
		$sai=false;
		$md5=md5($sql);
		foreach ($_SESSION['_cacheDB'] as $cache)
		{
			if ($cache['md5']==$md5)
			{
				$this->sections=$cache["sections"];
				$this->tables=$cache["tables"];
				$this->fields=$cache["fields"];
				$this->dictionary=$cache["dictionary"];
				$this->querys==$cache["querys"];
				$this->query=$cache["query"];
				$this->md5=$cache["md5"];
				$sai=true;
				break;
			}
		}
		return($sai);
	}

	/** Verifica campos da Query e retorna mais informações sobre eles
    * @author	Giuliano
    * @version	2009-10-13
    * @param string $sql Query
    */
	function parseQueryOnServer($sql="",$needData=true)
	{
		// Cache
		$leuDoCache=$this->cachePop($sql);
		//$leuDoCache=false;
		if ($leuDoCache)
		{

			$sql="SELECT ".$this->sections['select']." FROM ".$this->sections['from'];
			if ($this->sections['where']<>"") {
				$sql.=" WHERE ".$this->sections['where'];
			}
			if ($this->sections['group by']<>"") {
				$sql.=" GROUP BY ".$this->sections['group by'];
			}
			if ($needData)
			{
				$sql=$this->addLimitToQuery($sql,1);
				$rs=$this->run($sql,1);
				//echo "<pre>";print_r($rs->fields);echo "</pre>";exit;
				foreach ($this->fields as $ind=>$value)
				{
					$this->fields[$ind]['value']=$rs->fields[$ind];
				}
			}
		} else
		{
			if ($sql<>"")
			{
				$this->parseQuery($sql);
			}
			if (isset($this->sections['select']))
			{
				$sql="SELECT ".$this->sections['select']." FROM ".$this->sections['from'];
				if ($this->sections['where']<>"") {
					$sql.=" WHERE ".$this->sections['where'];
				}
				if ($this->sections['group by']<>"") {
					$sql.=" GROUP BY ".$this->sections['group by'];
				}

				$sql=$this->addLimitToQuery($sql,1);
				$rs=$this->run($sql,1);

				$ttlFields=$rs->FieldCount();
				$newFields="";
				$tables=$this->tables;
				$fldTable=$tables[0]["name"];
				for ($g=0; $g<$ttlFields; $g++)
				{
					$fld=$rs->FetchField($g);
					$fldName=$fld->name;
					$fldValue=$rs->fields[$g];
					//error_reporting(E_ALL);
// **** ATENÇÃO PARA A LINHA ABAIXO.... NO AdoDB5 o MetaType recebe o tipo. Ex.: $rs->MetaType($fld->type)
//                                      No AdbDB4 o MetaType recebe o objeto. Ex.: $rs->MetaType($fld)
					$fldType=$this->fieldType($fld,$rs->MetaType($fld->type));

					$fldLength=(int) $fld->max_length;
					/*
						O metodo fetch() do ADO estava trazendo o valor baixo para o maxLength
						e, por isso, causando problemas na validacao dos formularios.
						O IF abaixo minimiza o problema, uma vez verificado que o valor
						retornado para length é maior do que o maxLength, para campos
						do tipo inteiro.
						13-07-16 15:37 André Luiz
					*/
					if($fldLength < (int) $fld->length)
						$fldLength=(int) $fld->length;

					//if (($fldType=="I") && ($fldLength==4)) $fldType="L";
					if( ($fldType=="C") && ((int) $fldLength==10) )$fldType="D";
					if (substr(gVar("database.engine"),0,5)<>"mysql")
					{
						if (($fld->type=='char') && ($fld->max_length==10)) $fldType="D";
						if (($fld->type=='datetime')) $fldType="T";
						if (($fld->type=='bigint')) $fldType="I";
						if (($fld->type=='bigint identity')) $fldType="R";
						if (($fldType=='C') && ($fld->max_length==1) &&
							(($rstmp->fields[$fld->name]==" ") || ($rstmp->fields[$fld->name]=="1") || ($rstmp->fields[$fld->name]=="0"))
							) $fldType="L";
					}

					//if ($fldType)
					$fez=false;
					for ($a=0; $a<count($this->fields);$a++)
					{
						$f=$this->fields[$a];
						if ($f['alias']==$fldName)
						{
							$f['value']=$fldValue;
							$f['length']=$fldLength;
							$f['type']=$this->types[$fldType];
							$this->fields[$a]=$f;
							$a=count($this->fields);
							$fez=true;
						}
					}
					if (!$fez)
					{
						$fld="";
						$fld['table']=$fldTable;
						$fld['name']=$fldName;
						$fld['alias']=$fldName;
						$fld['fieldLabel']=gField2String($fldName);
						$fld['length']=$fldLength;
						$fld['value']=$fldValue;
						$fld['type']=$this->types[$fldType];
						$this->fields[]=$fld;
					}
				}
				//echo "\n\n<b>".$sql."</b><pre style='text-align: left'>";print_r($this->fields);"</pre>\n\n";exit;
			}
			//$this->cachePush($sql);
		}
	}

   /** Retorna tipo de campo reajustado de acordo com o SGBD atual
    * @author	Giuliano
    * @version	2009-10-13
    * @param string $fld Tipo de campo no formato do ADOdb
	 * @return string $fldType Tipo de campo
    */
	function fieldType($fld,$default)
	{
		$fldType=$default;
		//gLog($fld->name." => type: ".$fld->type." ($default) - max_len: ".$fld->max_length." - len: ".$fld->length);


		// Versões anteriores 5.4
		if (substr(gVar("database.engine"),0,5)<>"mysql")
		{
			if (($fld->type=='char') && ($fld->max_length==10)) $fldType="D";
			if ($fld->type=='datetime') $fldType="T";
			if ($fld->type=='bigint') $fldType="I";
			if ($fld->type=='bigint identity') $fldType="R";
		}
		//if (($fldType=='C') && ($fld->max_length==1)) $fldType="L";
		if (($fldType=='C') && ($fld->length==5)) $fldType="M";
		if (($fldType=='I') && ($fld->max_length<=3) && ($fld->length<=3)) $fldType="L";

		$tmp['senha']="W";
		$tmp['password']="W";
		//$tmp['plate']="A";
		//$tmp['placa']="A";
		$tmp['email']="E";
		$tmp['url']="U";
		$tmp['website']="U";
		$tmp['site']="U";
		$tmp['cpf']="F";
		$tmp['cnpj']="J";
		$tmp['ip']="P";
		$tmp['container']="H";
		$tmp['ncm']="O";
		if ($tmp[$fld->name]<>"") $fldType=$tmp[$fld->name];
		if (substr($fld->name,0,9)=="container") $fldType=$tmp['container'];
		//if (substr($fld->name,0,5)=="placa") $fldType=$tmp['placa'];
		if(substr($fld->name,0,5=="data_")) $fldType="date";
		return $fldType;


	}

   /** Formata Query para saída na tela
    * @author	Giuliano
    * @version	2009-10-13
    * @param string $sql Query
    */
	function formatQuery($sql="",$html=true)
	{
		$sai="";
		if ($sql<>"")
		{
			$this->parseQuerySections($sql);
		}
		if ($html)
		{
			$sai= "<div style='text-align: left; padding: 6px'>";
			foreach ($this->sections as $key=>$value)
			{
				$sai.="<b>".strtoupper($key)."</b><br>$value<br>";
			}
			$sai.="</div><br>";
		} else
		{
			foreach ($this->sections as $key=>$value)
			{
				$value=str_ireplace("LEFT JOIN","\n\tLEFT JOIN",$value);
				$value=str_ireplace("RIGHT JOIN","\n\tRIGHT JOIN",$value);
				$value=str_ireplace("INNER JOIN","\n\tINNER JOIN",$value);
				$sai.=strtoupper($key)." \n\t$value \n";
			}
		}
		return($sai);
	}

   /** Mostra Query na tela
    * @author	Giuliano
    * @version	2009-10-13
    * @param string $sql Query
    */
	function echoQuery($sql="")
	{
		echo $this->formatQuery($sql);
	}

	function addLimitToQuery($sql,$start,$max=0)
	{
		global $setup;
		if (strpos($setup->get("database.engine"),"mssql")!==false)
		{
			if ($max==0)
				$max=$start;
			if (strpos(strtoupper($sql)," TOP ")===false)
				$sql="SELECT TOP $max ".substr($sql,7);
		} else
		{
			if ($max==0)
			{
				$sql.=" LIMIT $start";
			} else
			{
				$sql.=" LIMIT $start,$max";
			}
		}
		return ($sql);
	}


   /** Adiciona tabela ao dicionário de dados
    * @author	Giuliano
    * @version	2009-10-13
    * @param string $json Conteúdo:
    *    name:  nome da tabela
    *    label: descrição da tabela
	 *		query: query select
    */
   function addQuery($sql)
   {
		$this->querys[]=$sql;
   }

   /** Adiciona tabela ao dicionário de dados
    * @author	Giuliano
    * @version	2009-10-13
    * @param string $json Conteúdo:
    *    name:  nome da tabela
    *    label: descrição da tabela
	 *		query: query select
    */
   function addTable($json)
   {
      $mtz=cssDecode($json);
      $this->tableDict[$mtz['name']]=$mtz;
   }

	/**
	 *  Retorna dados referente a tabela mencionada
	 * @author Giuliano
	 * @param String $campo Nome da tabela
	 * @return array $sai Dados
	 */
   function getTable($name,$attrib="")
   {
		$table=$this->tableDict[$name];
		if ($attrib<>"")
		{
			$table=$table[$attrib];
		}
		return ($table);
   }

	/**
	 *  Retorna dados referente ao campo mencionado
	 * @author Giuliano
	 * @param String $campo Nome do campo
	 * @return array $sai Dados
	 */
	function getField($campo)
	{
		$sai="";
		$flds=$this->fields;
		foreach ($flds as $fld)
		{
			if ($fld['alias']==$campo)
			{
				$sai=$fld;
				break;
			}
		}
		return($sai);
	}

}








function gExpire()
{
		global $http;
		global $http_img;
		global $gPathDefault;

		require_once $gPathDefault."gOutput.php";

		gSessionSave("usr_id",0);
		gSessionSave("idd",0);
		$out=new gOutput();
		$out->gBegin();
?>
<script language='javascript'>
if (self.parent.frames.length >= 2)
	self.parent.location = document.location;
</script>
<?
		$out->gTableBegin(gT_MEDIUM,true);
		$out->gTableRowBegin();
		$out->gTableColBegin("align='center'");
		$out->gOut("<br><img border='0' src='$http_img/logo_app.jpg'><br><br>");

		$out->gMsgTitle(gT("session_expired.short"));
		$out->gMsg(gT("session_expired.long"));
		$out->gBr();
		$out->gMsg("<a href='http://".gVar("global.url")."'>".gVar("global.url")."</a>");
		$out->gTableColEnd();
		$out->gTableRowEnd();
		$out->gTableEnd();
		$out->gEnd();
		session_write_close();

	exit();
}

/** Cria um array de duas dimensões contendo campos e valores de uma tabela do banco
*@author Giuliano Nascimento
*@version 2.0
*@param string $query query de banco de dados
*@param string $prefixo prefixo para os nomes dos campos
*@param string $metodo sendo 0=nomes dos campos e seus valores no primeiro registro e 1=registros (1 elemento é o nome e 2 o valor)
*@return array
*/
function gQuery2Array($prefixo='',$query,$metodo=0)
{
	global $gLastTransaction;
	$rstmp=gQuery($query,gD_DEFAULT,0,$gLastTransaction);
	$sai="";
	if ($prefixo<>"") $prefixo=$prefixo.".";
	if (!$rstmp->EOF)
	{
		if ($metodo==0)
		{
			$ttlf=$rstmp->FieldCount();
			for ($g_t=0; $g_t<$ttlf; $g_t++)
			{
				$fld=$rstmp->FetchField($g_t);
				$sai[$prefixo.strtolower($fld->name)]=$rstmp->fields[$g_t];
			}
		} else
		{
			while (!$rstmp->EOF)
			{
				$sai[$prefixo.strtolower($rstmp->fields[0])]=$rstmp->fields[1];
				$rstmp->MoveNext();
			}
		}
	}
	return $sai;
}


/**
 * Apaga registro(s) do banco
 * @param string $table Nome da tabela
 * @param type $id
 */
function dbDelete($table, $id)
{
	dbQuery("DELETE FROM $table WHERE id=$id");
}

/**
 * Cria um novo registro no banco de dados
 *
 * @param string $table Nome da tabela
 * @param array $fields Array com os campos e seus respectivos valores
 * @param boolean $returnId Booleano indicador se deve ou não retornar o último id criado
 *
 * @return int Retorna o id do novo registro criado
 */
function dbInsert($table, $fields, $returnId = false)
{
	$sai = true;
	$sql = "INSERT INTO $table (" . implode(",", array_keys($fields)) . ") VALUES ('" . implode("','", array_values($fields)) . "')";
	gQuery($sql);
	if ($returnId) {
		$sql = "SELECT id FROM $table WHERE ";
		$flt = '';
		foreach ($fields as $key => $value) {
			$flt[] = $key . "='$value'";
		}
		$sql.=implode(" and ", $flt) . " order by id desc";
		$r = gQuery($sql);
		$sai = intval($r->fields['id']);
	}
	return($sai);
}

/**
 *
 * @param string $table Nome da tabela
 * @param array $fields Array com os campos e seus respectivos valores
 * @param string $where
 * @param type $idField
 */
function dbUpdate($table, $fields, $where, $idField = 'id')
{
	$sql = "UPDATE $table set ";
	$flds = '';
	foreach ($fields as $key => $value) {
		$flds[] = "$key='$value'";
	}
	if (intval($where) > 0) {
		$where = $idField . "=" . $where;
	}
	$sql.=implode(",", $flds) . " where " . $where;
	gQuery($sql);
}


/*
$usr_id=$_SESSION['usr_id'];

$gPermissions="x"; // Select, Insert, Update, Delete
$thispage=$_SERVER["PHP_SELF"];

if (($gPageSecurity) && (strpos($thispage,"index.php")===false) && (strpos($thispage,"menu.php")===false) && (strpos($thispage,gVar("page.index"))===false) && (strpos($thispage,gVar("page.login"))===false) && (strpos($thispage,gVar("page.logout"))===false) && (strpos($thispage, "online")>0) )
{
	$sql="delete from geral_online where data_atualizacao < DATE_SUB(now(), INTERVAL ".gVar("global.timeout")." MINUTE)";
	gQuery($sql);
	$sql="select id from geral_online where id_geral_pessoas=$usr_id order by id desc";
	$rs=gQuery($sql);

	if (!$rs->EOF)
	{
		// Atualiza informação de acessos simultâneos (17-01-06)
		$sql="update geral_online set data_atualizacao=now() where id_geral_pessoas=$usr_id";
		gQuery($sql);

		if (($usr_id<=3) || ((isset($usr_idd)) && ($usr_idd==0))) // Se usuário for "root" ou cliente Alitem
		{
			$faz=true;
			if ($permissoes=="")
				$gPermissions="SIUD";
			else
				$gPermissions=$permissoes;
		} else
		{

			// Testa para ver se o usuário pode acessar esta página
			// e qual o tipo de acesso permitido (leitura, escrita, apagar, editar)
			$usr_id=gSessionLoad("usr_id");
			if (strpos($thispage,".php")>0)
				$thispage=substr($thispage,0,strpos($thispage,".php"));
			if (strpos($thispage,"_tnl")>0)
				$thispage=substr($thispage,0,strpos($thispage,"_tnl"));
			if (strpos($thispage,"_frm")>0)
				$thispage=substr($thispage,0,strpos($thispage,"_frm"));
			if (strpos($thispage,"_lst")>0)
				$thispage=substr($thispage,0,strpos($thispage,"_lst"));
			if (strpos($thispage,"_flt")>0)
				$thispage=substr($thispage,0,strpos($thispage,"_flt"));
			$dirs=explode("/",$thispage);
			$cnt=count($dirs);
			if ($cnt>1)
			{
				$thispage=$dirs[$cnt-2]."/".$dirs[$cnt-1];
			}
			if (($usr_id<>1) && ($cnt>4))
			{
				$faz=true;
				$sql="Select * from geral_links where link like '%$thispage%'";
				$S="";$I="";$U="";$D="";

				$rsts=gQuery($sql);
				while (!$rsts->EOF)
				{
					$dbsigla=$rsts->fields['sigla'];
					$dbsigla=substr($dbsigla,0,9);
					$sql="Select geral_links_permissoes.* from geral_links,geral_links_permissoes where ";
					$sql.=" geral_links.sigla=geral_links_permissoes.sigla_geral_links and sigla like '$dbsigla%'";

					$rst=gQuery($sql);
					if (!$rst->EOF)
					{

						if ($usr_id>0)
						{
							$usr_setores=gSessionLoad("usr_setores");
							$usr_funcoes=gSessionLoad("usr_funcoes");
							while (!$rst->EOF)
							{
								if (($rst->fields['id_pes_setores']==0) && ($rst->fields['id_pes_funcoes']==0))
								{
									$faz=true;
									if ($rst->fields['ler']==1) $S="S";
									if ($rst->fields['inserir']==1) $I="I";
									if ($rst->fields['editar']==1) $U="U";
									if ($rst->fields['remover']==1) $D="D";
								} elseif (($rst->fields['id_pes_setores']<>0) && ($rst->fields['id_pes_funcoes']<>0))
								{
									foreach ($usr_setores as $usr_setor)
									{
										if ($rst->fields['id_pes_setores']==$usr_setor[0])
										{
											$faz=true;
											if ($rst->fields['ler']==1) $S="S";
											if ($rst->fields['inserir']==1) $I="I";
											if ($rst->fields['editar']==1) $U="U";
											if ($rst->fields['remover']==1) $D="D";
										}
									}
									if ($faz)
									{
										$faz=false;
										$gPermissions="";
										foreach ($usr_funcoes as $usr_funcao)
										{
											if ($rst->fields['id_pes_funcoes']==$usr_funcao[0])
											{
												$faz=true;
												if ($rst->fields['ler']==1) $S="S";
												if ($rst->fields['inserir']==1) $I="I";
												if ($rst->fields['editar']==1) $U="U";
												if ($rst->fields['remover']==1) $D="D";
											}
										}
									}
								} else
								{
									foreach ($usr_setores as $usr_setor)
									{

										if (($rst->fields['id_pes_setores']==$usr_setor[0]) && ($rst->fields['id_pes_funcoes']==0))
										{
											$faz=true;
											if ($rst->fields['ler']==1) $S="S";
											if ($rst->fields['inserir']==1) $I="I";
											if ($rst->fields['editar']==1) $U="U";
											if ($rst->fields['remover']==1) $D="D";
										}
										//gLog("--- $sigla $usr_nome $usr_setor[0] - $usr_setor[1] = ".$rst->fields['id_pes_setores']." [$S $I $U $D]");
									}
									foreach ($usr_funcoes as $usr_funcao)
									{
										if (($rst->fields['id_pes_funcoes']==$usr_funcao[0]) && ($rst->fields['id_pes_setores']==0))
										{
											$faz=true;
											if ($rst->fields['ler']==1) $S="S";
											if ($rst->fields['inserir']==1) $I="I";
											if ($rst->fields['editar']==1) $U="U";
											if ($rst->fields['remover']==1) $D="D";
										}
									}
								}
								$rst->MoveNext();
							}
						}
					}
					$rsts->MoveNext();
				}
				if ($permissoes<>"")
				{
					if (strpos($permissoes,$S)===false) { $S="";}
						else
						{
							if (strpos($permissoes,"L")>0) $S.="L";
							if (strpos($permissoes,"M")>0) $S.="M";
						}
					if (strpos($permissoes,$I)===false) $I="";
					if (strpos($permissoes,$U)===false) $U="";
					if (strpos($permissoes,$D)===false) $D="";
				}
				$gPermissions=$S.$I.$U.$D;
			} else
			{
				$faz=true;
			}
		}
	} else
	{
		$faz=false;
	}
	if (!$faz)
	{
		gExpire();
	}

}

*/
?>
