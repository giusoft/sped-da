<?
include_once $gPathDefault."adodb/adodb.inc.php";
define('gD_DEFAULT','_default');
define('gD_NOTRANS',0);
define('gD_BEGINTRANS',1);
define('gD_INTRANS',2);
define('gD_ENDTRANS',3);

$gLastTransaction=gD_NOTRANS;

/** Gera um "recordset" com a configuração padrão do banco de dados
* @author Giuliano
* @param String $arg Query
* @param String $bd Banco de dados (opcional se for utilizado o padrão definido do arquivo de configuração)
* @param int $fetch Modo do "fetch" (verifique a documentação do ADOdb)
* @return Recordset
*/
function gQuery($arg,$bd=gD_DEFAULT,$fetch=0,$transaction=gD_NOTRANS)
{
  global $gDebug;
  global $gError;
  global $db;
  global $gLastTransaction;

	$gLastTransaction=$transaction;
	if ($bd=="_default")
		$bd=gVar("database.name");
	$sql=($arg);
	if ($fetch==0)
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	else
		$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
	if ($transaction<=1)
	{
		$db = ADONewConnection(gVar("database.type"));
		//$db->debug = $gDebug;
		if (gVar("database.type")=="oci8")
		{
			$db->NLS_LANG="BRAZIL_BRAZILIAN PORTUGUESE.WE8ISO8859P1";
			//$db->NLS_LANG="AMERICAN_AMERICA.WE8ISO8859P1";
			//$db->NLS_LANG="BRAZIL_BRAZILIAN PORTUGUESE.AL24UTFFSS";

			$db->NLS_DATE_FORMAT =  'YYYY-MM-DD';
			$db->NLS_TIMESTAMP_FORMAT =  'YYYY-MM-DD HH24:MI:SS';
			$db->PConnect(gVar("database.url"), gVar("database.user"), gVar("database.password"), $bd);
			$db->SetFetchMode($ADODB_FETCH_MODE);
		}
		else
		{
		//	if (substr(gVar("database.type"),0,5)<>"mysql")
		//		$db->SetFetchMode($ADODB_FETCH_MODE);
			$db->PConnect(gVar("database.url"), gVar("database.user"), gVar("database.password"), $bd);
		}

	}
	if ($gDebug>0)
	{
		gLog("gDB	gQuery	\033[33;1mSQL($transaction): ".$sql);
	}
	$gError="";
	if ($db->ErrorMsg()<>"")
	{
		if ($gDebug>0)	{ gLog("gDB	gQuery	\033[31;1mSQL Error: ".$db->ErrorMsg());}
		$gError=gE_DATABASE." (".$db->ErrorMsg().")";
	} else
	{
		if ($transaction==gD_BEGINTRANS)
		{
			$db->StartTrans();
		}
		//$rs = $db->Execute($sql) or $gError=gE_DATABASEQUERY;
		// Padronizando formato de datas para o framework quando for Oracle
		if (gVar("database.type")=="oci8")
		{
			//$db->Execute("ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS';");
			//$db->Execute("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD';");
		}
		$rs = $db->Execute($sql) or $gError=$db->ErrorMsg();
		if ((gVar("database.type")=="oci8") && ($transaction==gD_NOTRANS))
		{
				$db->Execute("COMMIT;");
		}
		if ($transaction==gD_ENDTRANS)
		{
			$db->CompleteTrans();
		}
		if ($transaction==gD_ROLLBACK)
		{
			$db->FailTrans();
		}
		if ($gError!="")
		{
			if ($gDebug>0)
			{
				gLog("gDB	gQuery	\033[31;1mSQL Error: ".$gError);
				echo "<acronym title='$gError'><font style='font-size: 8pt; color: #ff0000'>Houve um erro ao acessar o banco de dados.<br>Verifique se os dados digitados estão corretos e tente novamente mais tarde.<br><br></font></acronym>";
				echo "Query: $arg<br>";
			}
		}
	}
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
function gQueryLimit($arg,$ini=1, $qtd=9999999, $bd="_default",$fetch=0)
{

  global $gDebug;
  global $gError;

	if ($bd=="_default")
		$bd=gVar("database.name");
	$sql=($arg);
	if ($fetch==0)
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	else
		$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
	$db = ADONewConnection(gVar("database.type"));
	//$db->debug = $gDebug;
	$db->PConnect(gVar("database.url"), gVar("database.user"), gVar("database.password"), $bd);
	if ($gDebug>0)	{ gLog("gDB	gQueryLimit	\033[33;1mSQL: ".$sql);}
	$gError="";
	if ($db->ErrorMsg()<>"")
	{
		if ($gDebug>0)	{ gLog("gDB	gQueryLimit	\033[31;1mSQL Error: ".$db->ErrorMsg());}
		$gError=gE_DATABASE." (".$db->ErrorMsg().")";
	} else
	{
		$rs = $db->SelectLimit($sql,$qtd,$ini) or $gError=gE_DATABASEQUERY;
		/*
		if ((gVar("database.type")=="mysql") || (gVar("database.type")=="pgsql"))
		{
			$rs = $db->Execute($sql." LIMIT $ini,$qtd") or $gError=gE_DATABASEQUERY;
		}
		if (gVar("database.type")=="mssql")
		{
			$sql=trim($sql);
			$sql="Select TOP ".$qtd." ".substr($sql,7);
			$rs = $db->Execute($sql) or $gError=gE_DATABASEQUERY;
		}
		*/
	}
   return $rs;
}

/** Retorna com o valor de um campo da tabela em referência, tendo como índice o campo id
* @author Giuliano
* @param String $table Nome da tabela
* @param int $id Valor do campo id
* @param Variable $field Pode ser o número do campo da tabela ou seu nome
* @return String valor do campo
*/function gFieldById($table,$id,$field=2)
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
				$r=gLng("includes.gselect.long");
			else
			$r=$rs->fields[$field];
		}
	}
	return($r);
}

function gExpire()
{
		global $http;
		global $http_img;
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
?>
