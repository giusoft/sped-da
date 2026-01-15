<?
if ($gAction=="new")
{
	// Ativa o cache
   header("Expires: ".gmdate("D, d M Y")." ".substr("00".$hmais,-2).":".date("i:s")." GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:") . "00 GMT");
	header("Cache-Control: store, cache");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: cache");
}
include $gPathDefault."gForm.php";
$param=split('&',$_POST["gParam"]);
$newparm="";
foreach ($param as $par)
{
	$par=split("=",$par);
	$newparm[$par[0]]=$par[1];
}
$param=$newparm;
if (strtoupper($param["special"])=='PDF')
{
	include_once $gPathDefault."gPDF.php";
}
// Estilos de menu
define(gM_DEFAULT,0);
/** Classe que gera página de inclusão, edição ou remoção de dados com todas as funcionalidades
* @package gPage
* @author Giuliano Nascimento
* @version 2.5
*/
class gPage extends gForm
{
/** Gera HTML com código javascript de acordo com algumas condições
* @author Giuliano Nascimento
* @version 2.5
* @param string $pagetype Tipo de página
*/
	var $campo;
	var $campotxt;
	var $campovalor;
	var $campovalortxt;
	function g_jsGo($pagetype,$link)
	{
		global $table;
		$tmplink=$link;
		if (is_array($link))
			$tmplink=$link[1];
		
		if ($this->_getFilter()=='')
		{
	?>
<script language='javascript'>

function printNow()
{
	var t=1;
	for (a=1; a<9; a++)
	{
		if (document.getElementById('gTable'+a)!=undefined)
			t=a;
	}
	for (a=1; a<t; a++)
	{
		if (document.getElementById('gTable'+a)!=undefined) 
			document.getElementById('gTable'+a).innerHTML='';
	}
	t=1;
	for (a=1; a<9; a++)
	{
		if (document.getElementById('gControls'+a)!=undefined)
			t=a;
	}

	for (a=1; a<=t; a++)
	{
		if (document.getElementById('gControls'+a)!=undefined)
			document.getElementById('gControls'+a).innerHTML='';
	}
	window.print();
}

function gGo(argc,argd)
{
// Editar, copiar e apagar
	window.setTimeout('gWaitRemove()',5000)

	document.getElementById('gWait').style.display='inline';
	act=window.document.glist.action;
   if (argc!='new')
	{
		if (act.indexOf('?')>0)
			window.document.glist.action=act + "&" + argd;
		else
			window.document.glist.action=act + "?" + argd;
	}
	if (argc=='refresh')
	{
		if (window.document.glist.gParam.value!='')
		{
			window.document.glist.gParam.value=window.document.glist.gParam.value + '&';
		}
		window.document.glist.gParam.value=window.document.glist.gParam.value + 'start=' + window.document.glist.start.value;
		window.document.glist.submit();
	} else if (argc=='new')
	{
		if (window.document.glist.gParam.value!='')
		{
			window.document.glist.gParam.value=window.document.glist.gParam.value + '&';
		}
		window.document.glist.gParam.value=window.document.glist.gParam.value + 'start=' + window.document.glist.start.value;
		window.document.glist.gAction.value='new';
		window.document.glist.submit();
	} else
	{
		if (window.document.glist.gParam.value!='')
		{
			window.document.glist.gParam.value=window.document.glist.gParam.value + '&';
		}
		window.document.glist.gParam.value=window.document.glist.gParam.value + 'start=' + argc;
		window.document.glist.submit();
	}
}
function gGo2(argc,argd)
{
// Exportação de dados
	window.setTimeout('gWaitRemove()',5000)
	document.getElementById('gWait').style.display='inline';
	act=window.document.glist.action;
	if (act.indexOf('?')>0)
		window.document.glist.action=act + "&" + argd;
	else
		window.document.glist.action=act + "?" + argd;
	if (window.document.glist.gParam.value!='')
	{
		window.document.glist.gParam.value=window.document.glist.gParam.value + '&';
	}
	window.document.glist.gParam.value=window.document.glist.gParam.value + 'special=' + argc;
	window.document.glist.submit();
}

function gWaitRemove()
{
	document.getElementById('gWait').style.display='none';
}

function gMultiFunc(idname,p)
{
	if (p=='0')
	{
		var listaMarcados = document.getElementsByTagName("INPUT");
		for (loop = 0; loop < listaMarcados.length; loop++) 
		{
			var item = listaMarcados[loop];
			if (item.type == "checkbox" && item.checked) 
				item.checked=false;
			else
				item.checked=true;
		}
	}
	if (p=='1')
	{
		var id='';
		var listaMarcados = document.getElementsByTagName("INPUT");
		for (loop = 0; loop < listaMarcados.length; loop++) 
		{
			var item = listaMarcados[loop];
			if (item.type == "checkbox" && item.checked) 
			{
				if (id!='')
					id=id + ';' + item.name.substr(3,10);
				else
					id=item.name.substr(3,10);
			}
		}
		if (id!='')
		{
			if (confirm('<? echo gLng("confirm_delete.long");?>'))
			{
				url='<?echo $_SERVER["PHP_SELF"]?>?gAction=delete_tnl&gTable=<?echo $table;?>&gIdName='+idname+'&gId=' + id;
				window.open(url,'_new');
				//alert(url);
			}
		} else
		{
			alert('<?echo gLng("select_item_first.long")?>');
		}
	}
	if (p=='2')
	{
		var id='';
		var listaMarcados = document.getElementsByTagName("INPUT");
		for (loop = 0; loop < listaMarcados.length; loop++) 
		{
			var item = listaMarcados[loop];
			if (item.type == "checkbox" && item.checked) 
			{
				if (id!='')
					id=id + ';' + item.name.substr(3,10);
				else
					id=item.name.substr(3,10);
			}
		}
		if (id!='')
		{
			url='<?echo $tmplink;?>?gId=' + id;
			window.open(url,'_self');
			//alert(url);
		} else
		{
			alert('<?echo gLng("select_item_first.long")?>');
		}
	}
}

</script>
<form action='<?echo $_SERVER["PHP_SELF"];?>' name='glist' method='post'>
<input type='hidden' name='gAction' value='<?echo $pagetype;?>'>
<input type='hidden' name='gParam' value=''>
<?
		}
	}
/** Gera HTML com código javascript de confirmação exclusão
* @author Giuliano Nascimento
* @version 2.5
* @param string $rs recordset
*/
	function g_jsDelete($rs)
	{
		global $table;
		if ($this->_getFilter()=='')
		{
			$table=substr($rs,strpos($rs," from ")+6);
			if (strpos($table," ")>0)
			{
				$tablek=explode(" ",$table);
				if (strtolower($tablek[1])=="as")
				{
					$table=$tablek[2];
				} elseif ((strtolower($tablek[2])=="left") || (strtolower($tablek[2])=="inner") || (strtolower($tablek[2])=="right"))
				{
					$table=$tablek[1];
				} else
				{
					$table=substr($table,0,strpos($table," "));
				}
			}
	?>
		<script language='JavaScript'>
		function gConfirm(idname,id)
		{
			if (confirm('<? echo gLng("confirm_delete.long");?>'+' (' + idname+'='+id+')'))
			{
				url='<?echo $_SERVER["PHP_SELF"]?>?gAction=delete_tnl&gTable=<?echo $table;?>&gIdName='+idname+'&gId=' + id;
				window.open(url,'_self');
			}
		}
		</script>
	<?
		}
	}
/** Transforma array em string url http
 * @author	giuliano
 * @version	1.0 11-08-2008 18:45
 * param mixed $reply Array contendo parâmetros
 * return mixed $sai URL
 */
function gGo($reply)
{
	$go="";
	if (is_array($reply))
	{
		foreach ($reply as $rpl)
			$go.="&" . $rpl[0]."=".$rpl[1];
		$go=substr($go,1);
	}
	return $go;
}
/** Gera HTML com código javascript da barra de ferramentas
* @author Giuliano Nascimento
* @version 2.5
* @param string $pagetype Tipo de página
*/
	function g_ToolBar($start,$step,$ttl,$move_previous,$move_next,$move_last,$reply,$perm,$link)
	{
		global $http_img;
		if (is_array($link))
			$linktxt=$link[0];
		else
			$linktxt=gLng("register_select.short");
		$bar="";
		$step=($start+$step)-1;
		if ($step>$ttl) $step=$ttl;
		$go=$this->gGo($reply);
		$s="";
			//$s.="<input type='hidden' name='$rpl[0]' value='$rpl[1]'>";
		$s.="<-<input class='navig' type='button' value='&laquo;' onClick=\"gGo('1','$go')\"><input class='navig' type='button' value='&lsaquo;' onClick=\"gGo('".$move_previous."','$go')\">";
		$s.="&nbsp;<input class='navig' type='button' value='&rsaquo;' onClick=\"gGo('".$move_next."','$go')\"><input class='navig' type='button' value='&raquo;' onClick=\"gGo('".$move_last."','$go')\">";
		$barra[]=$s;

		$s="<-<input class='tool' type='text' name='start' value='".$start."' maxlength='8' size='6'> a $step de $ttl ";
		if (strpos($perm,"I")>0)
			$s.="<input class='tool' type='button' value='".gLng("register_new.long")."' onClick=\"gGo('new','$go')\">&nbsp;";
		$s.="<input class='tool' type='button' value='".gLng("register_refresh.short")."' onClick=\"gGo('refresh','$go')\">&nbsp;";
		$s.="<input class='tool' type='button' value='".gLng("register_showall.long")."' onClick=\"gGo2('showall','$go')\">&nbsp;";
		
		if ((strpos($perm,"M")>0))
		{
			$s.=" ".gLng("selected.short")." ";
			$s.="<input class='tool' type='button' value='".gLng("invert.short")."' onClick=\"gMultiFunc('id',0)\">&nbsp;";
			if (strpos($perm,"D")>0)
				$s.="<input class='tool' type='button' value='".gLng("register_delete.short")."' onClick=\"gMultiFunc('id',1)\">&nbsp;";
			if (strpos($perm,"L")>0)
				$s.="<input class='tool' type='button' value='".$linktxt."' onClick=\"gMultiFunc('id',2)\">&nbsp;";
		}
		
		$barra[]=$s;
		$barra[]="<-<img src='".$http_img."/impressora.png' border='0' onClick='printNow();'>";		
		//$barra[]="<-<input type='button' value='Imprimir' onClick='printNow();'>";		

				
		$s="";
		if (gVar("export.txt")=="true") $s.="<input class='export' type='button' value='TXT' onClick=\"gGo2('txt','$go')\">";
		if (gVar("export.prn")=="true") $s.="<input class='export' type='button' value='PRN' onClick=\"gGo2('prn','$go')\">";
		if (gVar("export.csv")=="true") $s.="<input class='export' type='button' value='CSV' onClick=\"gGo2('csv','$go')\">";
		if (gVar("export.htm")=="true") $s.="<input class='export' type='button' value='HTM' onClick=\"gGo2('htm','$go')\">";
		if (gVar("export.pdf")=="true") $s.="<input class='export' type='button' value='PDF' onClick=\"gGo2('pdf','$go')\">";
		if (gVar("export.doc")=="true") $s.="<input class='export' type='button' value='DOC' onClick=\"gGo2('doc','$go')\">";
		if (gVar("export.xls")=="true") $s.="<input class='export' type='button' value='XLS' onClick=\"gGo2('xls','$go')\">";
		if (gVar("export.rtf")=="true") $s.="<input class='export' type='button' value='RTF' onClick=\"gGo2('rtf','$go')\">";
$s.="&nbsp;";
		if ($s<>"") $s="->".gLng("register_export.long")." ".$s;
		$barra[]=$s;
		$bar[]=$barra;
		$this->gGrid("gToolBar",$bar);
	}
/** Função auxiliar que indica se o filtro está ativo para este campo ou não
* @author Giuliano Nascimento
* @version 2.5
* @param string $field Tipo de campo
* @param string $value Valor deste campo
*/
	function activeFilter($field,$value)
	{
		$sai=true;
		if (!is_array($value))
			$value=trim($value);
		if (($field==gI_NUM) && ($value=="")) $sai=false;
		if (($field==gI_NUMBETWEEN) && (($value=="") || (($value[0]=="") && ($value[1]=="")) )) $sai=false;
		if (($field==gI_TEXTBETWEEN) && (($value=="") || (($value[0]=="") && ($value[1]=="")) )) $sai=false;
		if (($field==gI_SELECTCODE) && (($value=="0") || ($value==""))) $sai=false;
		if (($field==gI_SELECTNULL) && (($value=="0") || ($value==""))) $sai=false;
		if (($field==gI_DATENULL) && ($value=="")) $sai=false;
		if (($field==gI_DATETIMENULL) && ($value=="")) $sai=false;
		if (($field==gI_DATEBETWEEN) && (($value=="") || (($value[0]=="") && ($value[1]=="")) )) $sai=false;
		if (($field==gI_DATETIMEBETWEEN) && (($value=="") || (($value[0]=="") && ($value[1]=="")) )) $sai=false;
		if (($field<4) && ($value=="")) $sai=false; // gI_TEXT, gI_UTEXT, gI_LTEXT e gI_EMAIL
		return ($sai);
	}
/** Função auxiliar que quebra o nome do campo em $this->campo (Nome) e $this->campotxt (Caption)
* @author Giuliano Nascimento
* @version 2.5
*/
	function chkField()
	{
		$this->campotxt=$this->campo;
		if (count($this->campo)>1)
		{
			$this->campotxt=$this->campo[0];
			$this->campo=$this->campo[1];
		}
	}
/** Gera uma página completa de inclusão, edição ou exclusão
* @author Giuliano Nascimento
* @version 2.5
* @param string $rs recordset ou query
* @param string $name "Label" e nome do formulário.
* @param string $perm Permissões (SIUDL) Select, Insert, Update, Delete ou Link para outra página
* @param string $fields Matriz de campos (para personalizar sua exibição)
* @param string $link Link a ser acionado na opção L de "perm"
* @param string $filters Matriz de campos (para filtrar os resultados)
* @param string $autoend indica se gera HTML de fechamento da página
*/
	function gShowPage($rs,$name,$perm,$fields="",$link="",$filters="",$autoend=true)
	{
		global $gDebug;
		global $cr;
		global $gError;

		if (gVar("table.corners")<>"")
			$this->table_corners=gVar("table.corners");
		else
			$this->table_corners="round";

		$perm=" ".strtoupper($perm);
		$action=$_REQUEST["gAction"];
		$param=split('&',$_POST["gParam"]);
		$newparm="";
		foreach ($param as $par)
		{
			$par=split("=",$par);
			$newparm[$par[0]]=$par[1];
		}
		$param=$newparm;
		$thispage=$_SERVER["PHP_SELF"];
		$label=$name;
		$table=substr($rs,strpos(strtolower($rs)," from ")+6);
		
		if (strpos($table," ")>0)
		{
			$tablek=explode(" ",$table);
			if (strtolower($tablek[1])=="as")
			{
				$table=$tablek[2];
			} elseif ((strtolower($tablek[2])=="left") || (strtolower($tablek[2])=="inner") || (strtolower($tablek[2])=="right"))
			{
				$table=$tablek[1];
			} else
			{
				$table=substr($table,0,strpos($table," "));
			}
		} elseif (strpos($table,",")>0)
		{
			$table=substr($table,0,strpos($table,","));
			$relac=true;
		}

		// Copia os parâmetros passados, para repeti-los nesta atualização
		$keys=array_keys($_REQUEST);
		$reply="";
		foreach ($keys as $key)
		{
			//if (($key<>"gAction") && ($key<>"gParam"))
			if (substr($key,0,1)<>"g")
			{
				$reply[]=array($key,$_REQUEST[$key]);
			}
		}
		if (count($name)>1)
		{
			$tmp=$name;
			$label=$tmp[0];
			$name=$tmp[1];
		}
		// *********** Novo Registro
		if (($action=="new") && (strpos($perm,"I")>0))
		{
			$h=date("H");
			$hmais=$h+1;if ($hmais>23) $hmais=0;
			$this->gBegin($param["special"]);
			if ($gDebug>0) $this->gOut($cr);
			$tmp[]=array(gI_NEW);
			$tmp[]=array(gI_HIDDEN,"gAction","new_tnl");
			if (is_array($reply))
			{
				foreach ($reply as $rpl)
				{
					$tmp[]=array(gI_HIDDEN,$rpl[0],$rpl[1]);
				}
			}
			$formtype="vertical";
			if (is_array($fields))
			{
				foreach ($fields as $field)
				{
					if ($field[0]==gI_FILE) $formtype="upload";
					$tmp[]=$field;
				}
			}
			$this->gShowForm(array($label." - ".gLng("register_new.long"),$name),$rs,$tmp,$thispage,"",$formtype);
			if ($autoend) $this->gEnd();
		}
		// *********** Novo Registro - Tunnel
		else if (($action=="new_tnl") && (strpos($perm,"I")>0))
		{
			$this->gBegin();
			if ($gDebug>0) $this->gOut($cr);
			if ($_REQUEST["senha"]!=$_REQUEST["_confirmacao_senha"])
			{
				$gError="Senha e confirmação são diferentes!";
			} else
			{
				$sql=gForm2Sql(gQ_INSERT);
				$rs=gQuery($sql,gD_DEFAULT,1);
				foreach ($reply as $rpl)
					$prm.="&".$rpl[0]."=".$rpl[1];
			}
			if ($gError=="")
			{
				$this->gMsgBox($label." - ".gLng("register_new.long"),"Registro inserido com sucesso.",array("Cadastrar outro|".$thispage."?gAction=new","Cadastrar outro aproveitando os dados|-1"));
			} else
			{
				$this->gMsgBox($label." - ".gLng("register_new.long"),"<font style='color:red'>$gError</font>",array("Limpar|".$thispage."?gAction=new$prm","Voltar|-1"));
			}
			if ($autoend) $this->gEnd();
		}
		// ********** Listagem de tabela
		else if (($action=="list") && (strpos($perm,"S")>0))
		{
			$faz=true;
			$this->breakForm=true;
			$this->breakFormRows=3;
			$export=$param["special"];
			if (($export=="showall") || ($export=="refresh")) $export="";
			$this->gBegin($export);
			if ($gDebug>0) $this->gOut($cr);
			if ($filters<>"")
			{
				if (($_REQUEST["gFilterCtrl"]=="onfilter") || (strlen($export)==3))
				{
					$where="";
					$flt="";
					if (strpos(strtolower($rs)," where ")>0)
					{
						$pnt=strpos(strtolower($rs)," where ")+7;
						$reply[]=array("gFilterCtrl",$_REQUEST["gFilterCtrl"]);
						for ($t_f=0; $t_f<count($filters); $t_f++)
						{
							$this->campo=$filters[$t_f][1];
							$this->chkField();
							if ($this->activeFilter($filters[$t_f][0],$_REQUEST[$this->campo].$_REQUEST[$this->campo."1"].$_REQUEST[$this->campo."2"]))
							{
								$reply[]=array($this->campo,$_REQUEST[$this->campo].$_REQUEST[$this->campo."1"].$_REQUEST[$this->campo."2"]);
								if ($filters[$t_f][0]<=4)
								{
									$flt[]=gField($this->campotxt)." '".$_REQUEST[$this->campo]."'";
									if (strpos($this->campo,"__")>0)
										$where.=str_replace("__",".",$this->campo)." like '%".$_REQUEST[$this->campo]."%' and ";
									else
										$where.="$table.$this->campo like '%".$_REQUEST[$this->campo]."%' and ";
								} elseif (($filters[$t_f][0]==gI_DATE) || ($filters[$t_f][0]==gI_DATENULL))
								{
									$flt[]=gField($this->campotxt)." ".$_REQUEST[$this->campo];
									if (strpos($this->campo,"__")>0)
										$where.=str_replace("__",".",$this->campo)."='".gDBDate($_REQUEST[$this->campo])."' and ";
									else
										$where.="$table.$this->campo='".gDBDate($_REQUEST[$this->campo])."' and ";
								} elseif (($filters[$t_f][0]==gI_TEXTBETWEEN))
								{
									$flt[]=gField($this->campotxt)." entre ".$_REQUEST[$this->campo."1"]." e ".$_REQUEST[$this->campo."2"];
									if (strpos($this->campo,"__")>0)
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.=str_replace("__",".",$this->campo).">='".$_REQUEST[$this->campo."1"]."' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.=str_replace("__",".",$this->campo)."<='".$_REQUEST[$this->campo."2"]."' and ";
									}
									else
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.="$table.$this->campo>='".$_REQUEST[$this->campo."1"]."' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.="$table.$this->campo<='".$_REQUEST[$this->campo."2"]."' and ";
									}
								
								} elseif (($filters[$t_f][0]==gI_NUMBETWEEN))
								{
									$flt[]=gField($this->campotxt)." entre ".$_REQUEST[$this->campo."1"]." e ".$_REQUEST[$this->campo."2"];
									if (strpos($this->campo,"__")>0)
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.=str_replace("__",".",$this->campo).">=".gDBFloat($_REQUEST[$this->campo."1"])." and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.=str_replace("__",".",$this->campo)."<=".gDBFloat($_REQUEST[$this->campo."2"])." and ";
									}
									else
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.="$table.$this->campo>=".gDBFloat($_REQUEST[$this->campo."1"])." and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.="$table.$this->campo<=".gDBFloat($_REQUEST[$this->campo."2"])." and ";
									}
								} elseif (($filters[$t_f][0]==gI_DATETIMEBETWEEN))
								{
									$flt[]=gField($this->campotxt)." entre ".$_REQUEST[$this->campo."1"]." e ".$_REQUEST[$this->campo."2"];
									if (strpos($this->campo,"__")>0)
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.=str_replace("__",".",$this->campo).">='".gDBDate($_REQUEST[$this->campo."1"])." 00:00:00' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.=str_replace("__",".",$this->campo)."<='".gDBDate($_REQUEST[$this->campo."2"])." 23:59:59' and ";
									}
									else
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.="$table.$this->campo>='".gDBDate($_REQUEST[$this->campo."1"])." 00:00:00' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.="$table.$this->campo<='".gDBDate($_REQUEST[$this->campo."2"])." 23:59:59' and ";
									}
								} elseif ($filters[$t_f][0]==gI_DATEBETWEEN)
								{
									$flt[]=gField($this->campotxt)." entre ".$_REQUEST[$this->campo."1"]." e ".$_REQUEST[$this->campo."2"];
									if (strpos($this->campo,"__")>0)
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.=str_replace("__",".",$this->campo).">='".gDBDateTime($_REQUEST[$this->campo."1"])."' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.=str_replace("__",".",$this->campo)."<='".gDBDateTime($_REQUEST[$this->campo."2"])."' and ";
									}
									else
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.="$table.$this->campo>='".gDBDateTime($_REQUEST[$this->campo."1"])."' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.="$table.$this->campo<='".gDBDateTime($_REQUEST[$this->campo."2"])."' and ";
									}
								} else
								{
									$this->campovalor=$_REQUEST[$this->campo];
									if (!is_array($filters[$t_f][2]))
									{
										$rstt=gQuery($filters[$t_f][2],gD_DEFAULT,1);
										while (!$rstt->EOF)
										{
											if ($_REQUEST[$this->campo]==$rstt->fields['id'])
											{
												$this->campovalor=$rstt->fields[1];
												$rstt->MoveLast();
											}
											$rstt->MoveNext();
										}
									}
									if (strpos($this->campo,"__")>0)
										$where.=str_replace("__",".",$this->campo)."='".$_REQUEST[$this->campo]."' and ";
									else
										$where.="$table.$this->campo='".$_REQUEST[$this->campo]."' and ";
									$flt[]=gField($this->campotxt)." '".$this->campovalor."'";
								}
							}
						}
					} else
					{
						$pnt=strpos(strtolower($rs)," group ");
						if ($pnt==0) 
						{
							$pnt=strpos(strtolower($rs)," order ");
							if ($pnt==0) $pnt=strlen($rs);
						}
						$where.=" where ";
						$reply[]=array("gFilterCtrl",$_REQUEST["gFilterCtrl"]);
						for ($t_f=0; $t_f<count($filters); $t_f++)
						{
							$this->campo=$filters[$t_f][1];
							$this->chkField();
							if ($this->activeFilter($filters[$t_f][0],$_REQUEST[$this->campo].$_REQUEST[$this->campo."1"].$_REQUEST[$this->campo."2"]))
							{
								$reply[]=array($this->campo,$_REQUEST[$this->campo].$_REQUEST[$this->campo."1"].$_REQUEST[$this->campo."2"]);
								if ($filters[$t_f][0]<4) // tipos texto
								{
									$flt[]=gField($this->campotxt)." '".$_REQUEST[$this->campo]."'";
									if (strpos($this->campo,"__")>0)
										$where.=str_replace("__",".",$this->campo)." like '%".$_REQUEST[$this->campo]."%' and ";
									else
										$where.="$table.$this->campo like '%".$_REQUEST[$this->campo]."%' and ";
								} elseif ($filters[$t_f][0]==4) // tipos numericos
								{
									$flt[]=gField($this->campotxt)." '".$_REQUEST[$this->campo]."'";
									if (strpos($this->campo,"__")>0)
										$where.=str_replace("__",".",$this->campo)."=".gDBFloat($_REQUEST[$this->campo])." and ";
									else
										$where.="$table.$this->campo = ".gDBFloat($_REQUEST[$this->campo])." and ";
								} elseif (($filters[$t_f][0]==gI_DATE) || ($filters[$t_f][0]==gI_DATENULL))
								{
									$flt[]=gField($this->campotxt)." ".$_REQUEST[$this->campo];
									if (strpos($this->campo,"__")>0)
										$where.=str_replace("__",".",$this->campo)."='".gDBDate($_REQUEST[$this->campo])."' and ";
									else
										$where.="$table.$this->campo='".gDBDate($_REQUEST[$this->campo])."' and ";
								} elseif ($filters[$t_f][0]==gI_TEXTBETWEEN)
								{
									$flt[]=gField($this->campotxt)." entre ".$_REQUEST[$this->campo."1"]." e ".$_REQUEST[$this->campo."2"];
									if (strpos($this->campo,"__")>0)
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.=str_replace("__",".",$this->campo).">='".$_REQUEST[$this->campo."1"]."' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.=str_replace("__",".",$this->campo)."<='".$_REQUEST[$this->campo."2"]."' and ";
									}
									else
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.="$table.$this->campo>='".$_REQUEST[$this->campo."1"]."' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.="$table.$this->campo<='".$_REQUEST[$this->campo."2"]."' and ";
									}
								} elseif ($filters[$t_f][0]==gI_NUMBETWEEN)
								{
									$flt[]=gField($this->campotxt)." entre ".$_REQUEST[$this->campo."1"]." e ".$_REQUEST[$this->campo."2"];
									if (strpos($this->campo,"__")>0)
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.=str_replace("__",".",$this->campo).">=".gDBFloat($_REQUEST[$this->campo."1"])." and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.=str_replace("__",".",$this->campo)."<=".gDBFloat($_REQUEST[$this->campo."2"])." and ";
									}
									else
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.="$table.$this->campo>=".gDBFloat($_REQUEST[$this->campo."1"])." and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.="$table.$this->campo<=".gDBFloat($_REQUEST[$this->campo."2"])." and ";
									}
								
								} elseif ($filters[$t_f][0]==gI_DATEBETWEEN)
								{
									$flt[]=gField($this->campotxt)." entre ".$_REQUEST[$this->campo."1"]." e ".$_REQUEST[$this->campo."2"];
									if (strpos($this->campo,"__")>0)
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.=str_replace("__",".",$this->campo).">='".gDBDate($_REQUEST[$this->campo."1"])."' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.=str_replace("__",".",$this->campo)."<='".gDBDate($_REQUEST[$this->campo."2"])."' and ";
									}
									else
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.="$table.$this->campo>='".gDBDate($_REQUEST[$this->campo."1"])."' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.="$table.$this->campo<='".gDBDate($_REQUEST[$this->campo."2"])."' and ";
									}
								} elseif ($filters[$t_f][0]==gI_DATETIMEBETWEEN)
								{
									$flt[]=gField($this->campotxt)." entre ".$_REQUEST[$this->campo."1"]." e ".$_REQUEST[$this->campo."2"];
									if (strpos($this->campo,"__")>0)
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.=str_replace("__",".",$this->campo).">='".gDBDateTime($_REQUEST[$this->campo."1"])."' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.=str_replace("__",".",$this->campo)."<='".gDBDateTime($_REQUEST[$this->campo."2"])."' and ";
									}
									else
									{
										if ($_REQUEST[$this->campo."1"]<>"")
											$where.="$table.$this->campo>='".gDBDateTime($_REQUEST[$this->campo."1"])."' and ";
										if ($_REQUEST[$this->campo."2"]<>"")
											$where.="$table.$this->campo<='".gDBDateTime($_REQUEST[$this->campo."2"])."' and ";
									}
								} else
								{
									$this->campovalor=$_REQUEST[$this->campo];
									if (!is_array($filters[$t_f][2]))
									{
										$rstt=gQuery($filters[$t_f][2],gD_DEFAULT,1);
										while (!$rstt->EOF)
										{
											if ($_REQUEST[$this->campo]==$rstt->fields['id'])
											{
												$this->campovalor=$rstt->fields[1];
												$rstt->MoveLast();
											}
											$rstt->MoveNext();
										}
									}
									if (strpos($this->campo,"__")>0)
										$where.=str_replace("__",".",$this->campo)."='".$_REQUEST[$this->campo]."' and ";
									else
										$where.="$table.$this->campo='".$_REQUEST[$this->campo]."' and ";
									$flt[]=gField($this->campotxt)." '".$this->campovalor."'";
								}
							}
						}
						if ($where<>" where ")
							$where=substr($where,0,strlen($where)-5);
						else
							$where="";
					}
					$rs=substr($rs,0,$pnt).$where.substr($rs,$pnt);
					if (is_array($flt))
						$this->report_filter=implode(" / ",$flt);
					else
						$this->report_filter=$flt;
				} else
				//if (($export<>"pdf") && ($export<>"txt"))
				{
					// Mostra filtros
					$filters[]=array(gI_HIDDEN,"gFilterCtrl","onfilter");
					$filters[]=array(gI_HIDDEN,"gAction","list");
					if (count($_GET)>1)
					{
						$gts=array_keys($_GET);
						for ($tm=1; $tm<count($_GET); $tm++)
						{
							$filters[]=array(gI_HIDDEN,$gts[$tm],$_GET[$gts[$tm]]);
						}
					}
					$this->gShowForm(array($label." - ".gLng("register_list.long"),$name),"",$filters,$thispage,"","");
					$faz=false;
				}
			}
			if ($faz)
			{
				$this->g_jsGo("list",$link);
				$this->gMsgTitle($label);
				$this->gMsgSubTitle(gLng("register_list.long"));
				if (($_REQUEST["gFilter"]!="") || ($this->report_filter))
				{
					if ($this->report_filter)
						$this->gMsgFilter($this->report_filter);
					else
						$this->gMsgFilter($_REQUEST["gFilter"]);
				}
				$start=$param["start"];
				if ($start<=0) $start=1;
				if ($_REQUEST["gOrderBy"]!="")
				{
					// Muda a ordenação
					$rst=strtolower($rs);
					$p=strpos($rst,"order by ");
					if ($p>0)
					{
					} else
					{
						$rs.=" order by ".$_REQUEST["gOrderBy"];
					}
				}
				$rstmp=gQuery($rs,gD_DEFAULT,1);
				//echo $rs;
				$ttl=$rstmp->RecordCount();
				if ($start>$ttl) $start=$ttl;
				$step=gVar("database.maxrows");
				if (($param["special"]=="showall")|| ($export<>"")) $step=$ttl;
				$move_last=$ttl-$step+1; if($move_last<1) $move_last=1;
				$move_next=$start+$step; if($move_next>$ttl) $move_next=$ttl;
				$move_previous=$start-$step; if($move_previous<1) $move_previous=1;
				$rstmp=gQueryLimit($rs,$start-1,$step,gD_DEFAULT,1);
				$this->table_border="square";
				$this->g_ToolBar($start,$step,$ttl,$move_previous,$move_next,$move_last,$reply,$perm,$link);
				$this->gGrid($name,$rstmp,$fields);
				$this->gOut("</form>");
				if ($autoend) $this->gEnd();
			}
		}
		// ********** Edição (Listagem)
		else if (($action=="edit") && (strpos($perm,"S")>0))
		{
			$faz=gVar("table.showrowsonedit")<>"false";
			$this->breakForm=true;
			$this->breakFormRows=3;
			$export=$param["special"];
			if ($export=="showall") $export="";
			$this->gBegin($export);
			if ($gDebug>0) $this->gOut($cr);
			$replay="";
			foreach ($_GET as $chave => $valor)
			{
				if ($chave<>"gAction")
					$replay[]=array($chave,$valor);
			}
			if ($filters<>"")
			{
				if (true)
				//if (!(($_REQUEST["gFilterCtrl"]=="onfilter") || (strlen($export)==3)))
				{

					$flttmp=$filters;
					// Mostra filtros
					/*
					$filters[]=array(gI_HIDDEN,"gFilterCtrl","onfilter");
					$filters[]=array(gI_HIDDEN,"id_processo",$_REQUEST['id_processo']);
					$filters[]=array(gI_HIDDEN,"id_roteiro",$_REQUEST['id_roteiro']);
					$filters[]=array(gI_HIDDEN,"gAction","edit");
					for ($tm=0; $tm<count($reply); $tm++)
					{
						$f=true;
						for ($tn=0;$tn<count($filters); $tn++)
						{
							if ($filters[$tn][1]==$reply[$tm][0]) $f=false;
						}
						if ($f) $filters[]=array(gI_HIDDEN,$reply[$tm][0],$reply[$tm][1]);
					}
					*/
					$filters[]=array(gI_HIDDEN,"gFilterCtrl","onfilter");
					$filters[]=array(gI_HIDDEN,"gAction","edit");
					
					// chegando valores pelo POST, altera nos campos passados pelo código
					foreach($_REQUEST as $chave => $valor)
					{
						if (($valor<>"") && ($chave!="PHPSESSID") && ($chave!="gAction"))
						{
							
							for ($t_f=0; $t_f<count($filters); $t_f++)
							{
								$this->campo=$filters[$t_f][1];
								$this->chkField();
								if (($this->campo==$chave) || 
								((($filters[$t_f][0]>=200) || ($filters[$t_f][0]<=205)) && ($this->campo==substr($chave,0,strlen($chave)-1))))
								{
									if (($filters[$t_f][0]>=gI_DATEBETWEEN) && ($filters[$t_f][0]<=gI_DATETIMEBETWEEN) )
									{
										$c1=$this->campo."1";
										$c2=$this->campo."2";
										$filters[$t_f][2]=array($_REQUEST[$c1],$_REQUEST[$c2]);
									} 
									elseif ($filters[$t_f][0]==gI_NUMBETWEEN) 
									{
										$c1=$this->campo."1";
										$c2=$this->campo."2";
										$filters[$t_f][2]=array($_REQUEST[$c1],$_REQUEST[$c2]);
									} 
									elseif ($filters[$t_f][0]==gI_TEXTBETWEEN) 
									{
										$c1=$this->campo."1";
										$c2=$this->campo."2";
										$filters[$t_f][2]=array($_REQUEST[$c1],$_REQUEST[$c2]);
									} 
									elseif (($filters[$t_f][0]<20) || ($filters[$t_f][0]>=30) )
									{
										$filters[$t_f][2]=$valor;
									} 
									elseif (($filters[$t_f][0]>=20) && ($filters[$t_f][0]<30))
									{
										$filters[$t_f][3]=$valor;
									} 
								}
							}
						}
					}
					$g=$this->gGo($replay);
					if (strpos($thispage,"?")>0)
						$thispage_parm=$thispage."&".$g;
					else
						$thispage_parm=$thispage."?".$g;
					
					if (strlen($export)!=3)
						$this->gShowForm(array(array($label,gLng("gfilter.long")),$name),"",$filters,$thispage_parm);
				} 
				
				//if (($_REQUEST["gFilterCtrl"]=="onfilter") || (strlen($export)==3))
				if (true)
				{
					
					$where="";
						
					$pnt=strpos(strtolower($rs)," order ");
					if ($pnt==0) $pnt=strlen($rs);
					
					$reply[]=array("gFilterCtrl",$_REQUEST["gFilterCtrl"]);
					for ($t_f=0; $t_f<count($filters); $t_f++)
					{
						$this->campo=$filters[$t_f][1];
						$this->chkField();
						if (($filters[$t_f][0]>=20) && ($filters[$t_f][0]<=29))
							$this->campovalor=$filters[$t_f][3];
						else
							$this->campovalor=$filters[$t_f][2];
						
						if ($this->activeFilter($filters[$t_f][0],$this->campovalor))
						{
							$reply[]=array($this->campo,$this->campovalor);
							if ($filters[$t_f][0]<4) // tipos texto
							{
								$flt[]=gField($this->campotxt)." '".$this->campovalor."'";
								if (strpos($this->campo,"__")>0)
									$where.=str_replace("__",".",$this->campo)." like '%".$this->campovalor."%' and ";
								else
									$where.="$table.$this->campo like '%".$this->campovalor."%' and ";
							} elseif ($filters[$t_f][0]==4) // tipos numericos
							{
								$flt[]=gField($this->campotxt)." '".$_REQUEST[$this->campo]."'";
								if (strpos($this->campo,"__")>0)
									$where.=str_replace("__",".",$this->campo)."=".gDBFloat($_REQUEST[$this->campo])." and ";
								else
									$where.="$table.$this->campo = ".gDBFloat($_REQUEST[$this->campo])." and ";
							} elseif (($filters[$t_f][0]==gI_DATE) || ($filters[$t_f][0]==gI_DATENULL))
							{
								$flt[]=gField($this->campotxt)." ".$this->campovalor;
								if (strpos($this->campo,"__")>0)
									$where.=str_replace("__",".",$this->campo)."='".gDBDate($this->campovalor)."' and ";
								else
									$where.="$table.$this->campo='".gDBDate($this->campovalor)."' and ";
							} elseif ($filters[$t_f][0]==gI_NUMBETWEEN)
							{
								$flt[]=gField($this->campotxt)." entre ".$this->campovalor[0]." e ".$this->campovalor[1];
								if (strpos($this->campo,"__")>0)
								{
									if ($this->campovalor[0]<>"")
										$where.=str_replace("__",".",$this->campo).">=".gDBFloat($this->campovalor[0])." and ";
									if ($this->campovalor[1]<>"")
										$where.=str_replace("__",".",$this->campo)."<=".gDBFloat($this->campovalor[1])." and ";
								}
								else
								{
									if ($this->campovalor[0]<>"")
										$where.="$table.$this->campo>=".gDBFloat($this->campovalor[0])." and ";
									if ($this->campovalor[1]<>"")
										$where.="$table.$this->campo<=".gDBFloat($this->campovalor[1])." and ";
								}
							} elseif ($filters[$t_f][0]==gI_TEXTBETWEEN)
							{
								$flt[]=gField($this->campotxt)." entre ".$this->campovalor[0]." e ".$this->campovalor[1];
								if (strpos($this->campo,"__")>0)
								{
									if ($this->campovalor[0]<>"")
										$where.=str_replace("__",".",$this->campo).">='".$this->campovalor[0]."' and ";
									if ($this->campovalor[1]<>"")
										$where.=str_replace("__",".",$this->campo)."<='".$this->campovalor[1]."' and ";
								}
								else
								{
									if ($this->campovalor[0]<>"")
										$where.="$table.$this->campo>='".$this->campovalor[0]."' and ";
									if ($this->campovalor[1]<>"")
										$where.="$table.$this->campo<='".$this->campovalor[1]."' and ";
								}
							
							} elseif ($filters[$t_f][0]==gI_DATEBETWEEN)
							{
								$flt[]=gField($this->campotxt)." entre ".$this->campovalor[0]." e ".$this->campovalor[1];
								if (strpos($this->campo,"__")>0)
								{
									if ($this->campovalor[0]<>"")
										$where.=str_replace("__",".",$this->campo).">='".gDBDate($this->campovalor[0])."' and ";
									if ($this->campovalor[1]<>"")
										$where.=str_replace("__",".",$this->campo)."<='".gDBDate($this->campovalor[1])."' and ";
								}
								else
								{
									if ($this->campovalor[0]<>"")
										$where.="$table.$this->campo>='".gDBDate($this->campovalor[0])."' and ";
									if ($this->campovalor[1]<>"")
										$where.="$table.$this->campo<='".gDBDate($this->campovalor[1])."' and ";
								}
							} elseif ($filters[$t_f][0]==gI_DATETIMEBETWEEN)
							{
								$flt[]=gField($this->campotxt)." entre ".$this->campovalor[0]." e ".$this->campovalor[1];
								if (strpos($this->campo,"__")>0)
								{
									if ($this->campovalor[0]<>"")
										$where.=str_replace("__",".",$this->campo).">='".gDBDateTime($this->campovalor[0])."' and ";
									if ($this->campovalor[1]<>"")
										$where.=str_replace("__",".",$this->campo)."<='".gDBDateTime($this->campovalor[1])."' and ";
								}
								else
								{
									if ($this->campovalor[0]<>"")
										$where.="$table.$this->campo>='".gDBDateTime($this->campovalor[0])."' and ";
									if ($this->campovalor[1]<>"")
										$where.="$table.$this->campo<='".gDBDateTime($this->campovalor[1])."' and ";
								}
							} elseif (($filters[$t_f][0]>=20) && ($filters[$t_f][0]<30))
							{
								$this->campovalortxt=$this->campovalor;
								if (!is_array($filters[$t_f][2]))
								{
									//echo "<b>".$filters[$t_f][0]."=".$filters[$t_f][2]."</b><br>";
									$rstt=gQuery($filters[$t_f][2],gD_DEFAULT,1);
									while (!$rstt->EOF)
									{
										if ($this->campovalor==$rstt->fields['id'])
										{
											$this->campovalortxt=$rstt->fields[1];
											$rstt->MoveLast();
										}
										$rstt->MoveNext();
									}
								}
								if (strpos($this->campo,"__")>0)
									$where.=str_replace("__",".",$this->campo)."='".$this->campovalor."' and ";
								else
									$where.="$table.$this->campo='".$this->campovalor."' and ";
								$flt[]=gField($this->campotxt)." '".$this->campovalortxt."'";
							}
						}
					}
					if (strpos(strtolower($rs)," where ")>0)
					{
						$pnt=strpos(strtolower($rs)," where ")+7;
					} else {
						if ($where<>"")
						$where=" where ".substr($where,0,strlen($where)-4);
					}
					
					$rs=substr($rs,0,$pnt).$where.substr($rs,$pnt);
					if (is_array($flt))
						$this->report_filter=implode(" / ",$flt);
					else
						$this->report_filter=$flt;
				} 
			}
		//echo "<h1>========".$_REQUEST["gFilterCtrl"]." $export</h1>";	
			if (($faz) || (($_REQUEST["gFilterCtrl"]=="onfilter") && (strlen($export)<>3)))
			{
				if (strpos($perm,"D")>0)
				{
					$this->g_jsDelete($rs);
				}
				$this->g_jsGo("edit",$link);
				if ($filters=="")
					$this->gMsgTitle($label);
				$this->gMsgSubTitle(gLng("register_list.long"));
				if ($filters=="")
				{
				}
				if (($_REQUEST["gFilter"]!="") || ($this->report_filter))
				{
					if ($this->report_filter)
						$this->gMsgFilter($this->report_filter);
					else
						$this->gMsgFilter($_REQUEST["gFilter"]);
				}
				$start=$param["start"];
				if ($start<=0) $start=1;
				if ($_REQUEST["gOrderBy"]!="")
				{
					// Muda a ordenação
					$rst=strtolower($rs);
					$p=strpos($rst," order by ");
					if ($p>0)
					{
					} else
					{
						$rs.=" order by ".$_REQUEST["gOrderBy"];
					}
				}
				$rstmp=gQuery($rs,gD_DEFAULT,1);
				$ttl=$rstmp->RecordCount();
				if ($start>$ttl) $start=$ttl;
				$step=gVar("database.maxrows");
				if (($param["special"]=="showall")|| ($export<>""))
				{
					$start=0;
					$step=$ttl;
				}
				$move_last=$ttl-$step+1; if($move_last<1) $move_last=1;
				$move_next=$start+$step; if($move_next>$ttl) $move_next=$ttl;
				$move_previous=$start-$step; if($move_previous<1) $move_previous=1;
				$rstmp=gQueryLimit($rs,$start-1,$step,gD_DEFAULT,1);
				foreach ($filters as $pfil)
				{
					$campo=$pfil[1];
					if (is_array($campo))
						$campo=$campo[1];
					$this->chkField();
					if ($campo<>"gAction")
					{
						if (($pfil[0]>=20) && ($pfil[0]<30)) // tipo select
							$replay[]=array($campo,$pfil[3]);
						elseif (($pfil[0]>=200) && ($pfil[0]<=201)) // tipo datebetween
						{
							$replay[]=array($campo."1",$pfil[2][0]);
							$replay[]=array($campo."2",$pfil[2][1]);
						}
						else
							$replay[]=array($campo,$pfil[2]);
					}
				}
					//echo "\n\n\n\n\n<!--\n";
					//print_r($filters);
					//echo " -->\n\n\n\n\n";
				$this->table_border="square";					
				$this->g_ToolBar($start,$step,$ttl,$move_previous,$move_next,$move_last,$replay,$perm,$link);
				/*
				$dict="";
				if (is_array($fields))
				{
					foreach($fields as $field)
					{
						if (count($field[1])>2)
						{
							$dict[]=$field[1];
						}
					}
				}
				$dict[]="gEdit";
				$dict[]="gCopy";
				$dict[]="gDelete";
				*/
				if (strpos($perm,"U")>0)
				{
					$parm="";
					if (is_array($reply))
					{
						foreach ($reply as $rpl)
						{
							if ($rpl[0]<>"gAction")
								$parm.="&".$rpl[0]."=".$rpl[1];
						}
					}
					$fields[]=array(gI_DICT,"gEdit:$parm");
				}
				if ((strpos($perm,"I")>0) && (gVar("database.showcopy")=="true"))
				{
					$fields[]=array(gI_DICT,"gCopy");
				}
				if (strpos($perm,"D")>0)
				{
					$fields[]=array(gI_DICT,"gDelete");
				}
				if (strpos($perm,"M")>0)
				{
					$fields[]=array(gI_DICT,"gMulti:$link");
				}
				if (strpos($perm,"L")>0)
				{
					if (is_array($link))
					{
						$fields[]=array(gI_DICT,"gLink:$link[0]|$link[1]");
					} else
						$fields[]=array(gI_DICT,"gLink:$link");
				}
				$this->gGrid($name,$rstmp,$fields);
				//$this->gOut("</form>");
				if ($autoend) $this->gEnd();
			}
		}
		// *********** Editar Registro
		else if (($action=="edit_frm") && (strpos($perm,"U")>0))
		{
			$this->gBegin();
			if ($gDebug>0) $this->gOut($cr);
			$id=$_REQUEST["gId"];
			$idname=$_REQUEST["gIdName"];
			if (is_array($fields))
			{
				foreach ($fields as $field)
				{
					if ($field[0]==gI_FILE) $formtype="upload";
					$tmp[]=$field;
				}
			}
			if ($id=="")
			{
				$this->gMsg("errors.id_not_found",2);
			} else
			{
				$fields[]=array(gI_HIDDEN,"gAction","edit_tnl");
				$fields[]=array(gI_HIDDEN,"gIdName",$idname);
				$fields[]=array(gI_HIDDEN,"gId",$id);
				if (strpos(strtolower($rs)," where ")>0)
				{
					$pnt=strpos(strtolower($rs)," where ")+7;
					$where="$table.$idname=$id and ";
				} else
				{
					$pnt=strpos(strtolower($rs)," order ");
					if ($pnt==0) $pnt=strlen($rs);
					$where=" where $table.$idname=$id";
				}
				$rs=substr($rs,0,$pnt).$where.substr($rs,$pnt);
				$formtype="vertical";
				if (is_array($fields))
				{
					foreach ($fields as $field)
					{
						if ($field[0]==gI_FILE) $formtype="upload";
					}
				}
				$this->gShowForm(array($label." - ".gLng("register_edit.long"),$name),$rs,$fields,$thispage,"",$formtype);
			}
			if ($autoend) $this->gEnd();
		}
		// *********** Editar Registro - Tunnel
		else if (($action=="edit_tnl") && (strpos($perm,"U")>0))
		{
			$this->gBegin();
			if ($gDebug>0) $this->gOut($cr);
			$id=$_REQUEST["gId"];
			$idname=$_REQUEST["gIdName"];
			if ($_REQUEST["senha"]!=$_REQUEST["_confirmacao_senha"])
			{
				$gError="Senha e confirmação são diferentes!";
			} else
			{
				$sql=gForm2Sql(gQ_UPDATE,$idname,$id);
				//echo "\n\n\n$sql\n\n\n";exit;
				$rs=gQuery($sql,gD_DEFAULT,1);
			}
			if ($gError=="")
			{
				$this->gMsgBox($label." - ".gLng("register_new.long"),"Registro alterado com sucesso.",array("Editar outro|".$thispage."?gAction=edit","Voltar a seleção sem atualizar|-2","Voltar|-1"));
			} else
			{
				$this->gMsgBox($label." - ".gLng("register_new.long"),"<font style='color:red'>$gError</font>",array("Voltar|-1"));
			}
			if ($autoend) $this->gEnd();
		}
		// *********** Copiar para novo registro
		else if (($action=="copy_frm") && (strpos($perm,"I")>0))
		{
			$this->gBegin();
			if ($gDebug>0) $this->gOut($cr);
			$fields[]=array(gI_HIDDEN,"gAction","copy_tnl");
			$id=$_REQUEST["gId"];
			$idname=$_REQUEST["gIdName"];
			if (strpos(strtolower($rs)," where ")>0)
			{
				$pnt=strpos(strtolower($rs)," where ")+7;
				$where="$idname=$id and ";
			} else
			{
				$pnt=strpos(strtolower($rs)," order ");
				if ($pnt==0) $pnt=strlen($rs);
				$where=" where $idname=$id";
			}
			$fields[]=array(gI_EXCLUDE,$idname);
			$rs=substr($rs,0,$pnt).$where.substr($rs,$pnt);
			$this->gShowForm(array($label." - ".gLng("register_copy.long"),$name),$rs,$fields,$thispage);
			if ($autoend) $this->gEnd();
		}
		// *********** Novo Registro - Tunnel
		else if (($action=="copy_tnl") && (strpos($perm,"I")>0))
		{
			$this->gBegin();
			if ($gDebug>0) $this->gOut($cr);
			$sql=gForm2Sql(gQ_INSERT);
			$rs=gQuery($sql,gD_DEFAULT,1);
			if ($gError=="")
			{
				$this->gMsgBox($label." - ".gLng("register_copy.long"),"Registro inserido com sucesso.",array("Cadastrar outro|".$thispage."?gAction=new","Cadastrar outro aproveitando os dados|-1","Voltar a seleção sem atualizar|-2"));
			} else
			{
				$this->gMsgBox($label." - ".gLng("register_copy.long"),"<font style='color:red'>$gError</font>",array("Limpar|".$thispage."?gAction=new","Voltar|-1","Voltar a seleção sem atualizar|-2"));
			}
			if ($autoend) $this->gEnd();
		}
		// *********** Deletar Registro - Tunnel
		else if (($action=="delete_tnl") && (strpos($perm,"I")>0))
		{
			$this->gBegin();
			if ($gDebug>0) $this->gOut($cr);
			$id=$_REQUEST["gId"];
			$idname=$_REQUEST["gIdName"];
			$sql=gForm2Sql(gQ_DELETE,$idname,$id);
			$rs=gQuery($sql,gD_DEFAULT,1);
			if ($gError=="")
			{
				$this->gMsgBox($label." - ".gLng("register_delete.long"),"Registro removido com sucesso.",array("Voltar a seleção sem atualizar|-1"));
			} else
			{
				$this->gMsgBox($label." - ".gLng("register_delete.long"),"<font style='color:red'>$gError</font>",array("Voltar|-1"));
			}
			if ($autoend) $this->gEnd();
		}
	}
}//fim da classe
?>
