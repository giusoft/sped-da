<?

include_once $gPathDefault."gInput.php";

/** Classe que gera formulários para entrada de dados
* @package gForm
* @author Giuliano Nascimento
* @version 2.0
*/
class gForm extends gInput
{

/**Gera uma saída na página(somente se não estiver exportando dados).
* @author Giuliano Nascimento
* @version 2.0
* @param String $name "Label" e nome do formulário.
* @param String $rs Recordset => se for vazio, formulário em branco, senão, pega os valores do campo atual
* @param array $fields "Array" com os campos do formulário (exemplo: elemento 1 = array(gI_TEXT,array("Campo texto","texto1"),"Teste de texto"))
* 1º elemento = tipo (ver gInput)
* 2º elemento = nome ou array(caption,nome)
* 3º elemento = valor padrão
* @param String $link Página de destino
* @param array $connectors array de dois elementos, onde o 1º=nome do campo, 2º="onClick='javascript...'"
*        se não for um array, será um evento javascript no Submit
* @param String $frmtype Tipo de formulário, podendo ser: "upload", "vertical" ou ""
* @return void
*/
	var $breakForm;
	var $breakFormRows;
	
	function gInput()
	{
		$this->breakForm=false;
		$default=3;
		if (intval(gVar("table.filterrows"))>0)
			$default=intval(gVar("table.filterrows"));;
		$this->breakFormRows=$default;
	}
  function gShowForm($name,$rs,$fields,$link,$connectors="",$frmtype="")
  {

	global $gDebug;
	global $cr;
	
	$idname=gVar("database.id");
	$sql="";
   $label=$name;
	$dbidd=-1;
	if ($frmtype<>"")
		$cs="2";
	else
		$cs="4";
   if (count($name)>1)
	{
      $tmp=$name;
      $label=$tmp[0];
      $name=$tmp[1];
   }
   if (is_array($link))
   {
   	$link_button=$link[0];
   	$link=$link[1];
   } else
   {
   	$link_button=gLng("confirm.short");
   }
   if ($gDebug>0) $this->gOut($cr);
	$dataf=date("His");
	/*
	$js="<script language='javascript'>\nfunction gHideForm(){\ndocument.getElementById('gForm$dataf').style.visibility='hidden';\ndocument.getElementById('gPage').style.top='50';\n}\n</script>";
	$this->gOut($js);
	$this->gOut("<div id='gForm$dataf'>");
	*/
	$lab="";
	$sublab="";
	if ($label<>"")
	{
		if (is_array($label))
		{
			$lab=$label[0];			
			$sublab=$label[1];
		} else
		{
			$lab=$label;			
			$sublab=gLng("gform.short");
		}
		$this->gMsgTitle($lab);
	}
	if ($frmtype=="upload")
	{
   	$this->gOut("<form enctype='multipart/form-data' action='$link' name='$name' method='post'>");
	} else
	{
   	$this->gOut("<form action='$link' name='$name' method='post'>");
	}
   //echo gVar("table.size");
   $this->gTableBegin(gVar("table.size"),true,gVar("table.controls")!="false");
   
   
   $cntrow=0;
   if ($sublab<>"")
   {
		$this->gTableRow(array("~$cs". $sublab),gT_HEADER);
  	}
  	
	$this->gOut("<tr><td style='height: 6px' colspan='$cs'></td></tr>");
	if ((is_object($rs)) || ($rs<>""))
	{
		if (is_object($rs))
		{
			$rstmp=$rs;
			$table="error!";
		} else
		{
			$rstmp=gQuery($rs,gD_DEFAULT,1);
			$rs=strtolower($rs);
			$table=substr($rs,strpos($rs," from ")+6);
			if (strpos($table," ")>0)
			{
				$table=substr($table,0,strpos($table," "));
			}
		}
		$ttlfld=$rstmp->FieldCount();
		$flds="";
		$types["C"]=gI_TEXT;
		$types["D"]=gI_DATENULL;
		$types["T"]=gI_DATETIMENULL;
		$types["N"]=gI_NUM;
		$types["I"]=gI_NUM;
		$types["R"]=gI_READONLY;
		$types["B"]=gI_SUPERMEMO;
		$types["X"]=gI_MEMO;
		$types["L"]=gI_CHECK;
		$types["@"]=gI_EMAIL;
		$types["0"]=gI_EXCLUDE;
		$orgfields=$fields;
		$novo=false;
		if (gVar("database.idd")=="true")
		{
			$dbidd=$rstmp->fields['idd'];
			if ($dbidd=="") $dbidd=-1;
		}
		for ($g_t=0; $g_t<$ttlfld; $g_t++)
		{
			$fld=$rstmp->FetchField($g_t);
			$fldtype=$rstmp->MetaType($fld);
			//$fldname=strtolower($fld->name);
			$fldname=$fld->name;
			$fldvalue=$rstmp->fields[$g_t];
			$fldmaxlen=$fld->max_length;
			// ajustes para SQLServer
			if ((substr(gVar("database.type"),0,5)<>"mysql") && ($fld->type=='char') && ($fld->max_length==10)) $fldtype="D";
			if ((substr(gVar("database.type"),0,5)<>"mysql") && ($fld->type=='datetime')) $fldtype="T";
			if ((substr(gVar("database.type"),0,5)<>"mysql") && ($fld->type=='bigint')) $fldtype="I";
			if ((substr(gVar("database.type"),0,5)<>"mysql") && ($fld->type=='bigint identity')) $fldtype="R";
			if ((substr(gVar("database.type"),0,5)<>"mysql") && ($fldtype=='C') && ($fld->max_length==1)) $fldtype="L";
			if ($fld->type=="VARCHAR2") $fldmaxlen=$fldmaxlen/2;
			$fldhint="";
			$fldjs="";
			//echo "==> $fldname : $fldtype : ".$fld->type." (".$fld->max_length.") <== <br>";
			if ($fldname==$idname)
			{
				$fldtype="R";
			}
			if ($fldtype=="C")
			{
				$fldhint=gLng("hinttext").' '.$fldmaxlen.' '.gLng("hintchars");
			}
			if ($fldtype=="D")
			{
				$fldvalue=gDate($fldvalue);
				$fldmaxlen=strlen($fldvalue);
				$fldhint=gLng("hintdate").' '.gVar("global.dateformat");
			}
			if ($fldtype=="T")
			{
				$fldvalue=gDateTime($fldvalue);
				$fldmaxlen=strlen($fldvalue);
				$fldhint=gLng("hintdatetime").' '.gVar("global.dateformat").' '.gVar("global.timeformat");
			}
			if ($fldtype=="N")
			{
				$fldvalue=gFloat($fldvalue);
				$fldhint=gLng("hintnum");
			}
			if (($fldtype=="B") || ($fldtype=="X"))
			{
				//$fldvalue=nl2br($fldvalue);
			}
			if (($fldtype=="L") || (($fldmaxlen<=4) && ($fldtype=="I")))
			{
				$fldtype="L";
				$fldhint=gLng("hintcheck");
			}
			if (($fldname=="email") || ($fldname=="e-mail"))
			{
				$fldtype="@";
			}
			// Verifica se deve personalizar algum campo
			$fez=false;
			$newtype=$types[$fldtype];
			for ($f_t=0; $f_t<count($fields); $f_t++)
			{
				$field=$fields[$f_t];
				if ($field[0]==gI_NEW)
				{
					$novo=true;
					$dbidd=-1;
				}
				$header=$field[1];
				if (count($header)>1)
				{
					$label=$header[0];
					$name=$header[1];
				} else
				{
					$label=$header;
					$name=$header;
				}
				if ($name==$fldname)
				{
					
					$fldname=array(gField($label),$name);
					$fldjs=$field[3];
					// Hidden
					if ($field[0]==100)
					{
						$fldvalue=$field[2];
					}
					// Select
					if (($field[0]>=20) && ($field[0]<30))
					{
						$fldmaxlen=$fldvalue;
						$fldvalue=$field[2];
						$fldjs=$field[4];
						if ($field[0]==24) // SelectCode
						{
							$fldhint=gLng("hintselectcode");
						}
					}
					if ($novo)
					{
						// Tipo texto e variantes
						if (($field[0]<20) || ($field[0]==40)|| ($field[0]==41))
						{
							$fldvalue="";
							if (count($field)>2)
								$fldvalue=$field[2];
						}
						// Select
						if (($field[0]>=20) && ($field[0]<30))
						{
							$fldvalue=$field[2];
							$fldmaxlen='';
							if (count($field)>3)
								$fldmaxlen=$field[3];
						}
						// Check
						if ($field[0]==gI_CHECK)
						{
							$fldvalue='';
							if (count($field)>2)
								$fldvalue=$field[2];
						}
					}
					$newtype=$field[0];
					$fez=true;
					$f_t=count($fields);
				}
				//gLog("--------> gI_NEW? ".$fields[0][0]);
			}
			if (!$fez)
			{
				$fldname=array(gField($fldname),$fldname);
			}
			if (($novo) && (!$fez))
			{
				$fldvalue="";
				if ($fldname[1]==$idname)
				{
					$newtype=gI_EXCLUDE;
				}
			}
			$flds[]=array($newtype,$fldname,$fldvalue,$fldmaxlen,$fldhint,$fldjs);
		}
		$fields=$flds;
		//gD($fields);

		// Verifica se tem algum campo a ser adicionado aos existentes no banco de dados
		$addfields="";
		for ($f_t=0; $f_t<count($orgfields); $f_t++)
		{
			$orgfield=$orgfields[$f_t];
			if ($orgfield[0]!=gI_DICT)
			{
				$header=$orgfield[1];
				if (count($header)>1)
				{
					$label=$header[0];
					$name=$header[1];
				} else
				{
					$label=$header;
					$name=$header;
				}
				$fez=false;
				for ($g_t=0;$g_t<count($fields);$g_t++)
				{
					$field=$fields[$g_t];
					$header=$field[1];
					if (count($header)>1)
					{
						$flabel=$header[0];
						$fname=$header[1];
					} else
					{
						$flabel=$header;
						$fname=$header;
					}
					if ($name==$fname)
					{
						$fez=true;
						$g_t=count($fields);
					}
				}
				if ((!$fez) && ($orgfield[0]>=100))
				{
					$fields[]=$orgfield;
				}
			}
		}
	} else
	{
		for ($f=0; $f<count($fields); $f++)
		{
			$field=$fields[$f][1];
			if (count($field)==1)
				$fields[$f][1]=array(gField($field),$field);
		}
	}

	$gFields="";
	$hiddens="";
	$doBreakForm=false;
//gD($fields);
	if ($dbidd==0)
	{
		$this->gTableRow(array(gLng("denied.long")));
	} else
	{
		$impar=true;
		for ($f=0; $f<count($fields); $f++)
		{
			$field=$fields[$f];
			$type=$field[0];
			$header=$field[1];
			$hint=$field[4];
			$js=$field[5];
			if (count($header)>1)
			{
				$label=$header[0];
				$name=$header[1];
			} else
			{
				$label=$header;
				$name=$header;
			}
			for ($t2=0;$t2<count($connectors);$t2++)
			{
				$connector=$connectors[$t2];
				if ($name==$connector[0]) $js=$connector[1];
			}
			$gFields.=$type."|".$name."|".str_replace("'","\"",$field[2]).$cr;
			//$gFields.=$type."|".$name."|";
			$value="";
			$default="";
			$label=ucfirst(strtolower($label));
			if (count($field)>2) $value=$field[2];
			if (count($field)>3) $default=$field[3];
			//if (count($field)>3) $hint=$field[4];
			if (($type<>gI_EXCLUDE) && ($type<>gI_HIDDEN))
			{
				if (($impar) || ($frmtype<>""))
				{
					$this->gTableRowBegin();
					$linhafechada=false;
				}
				$this->gTableColBegin();
				$this->gOut($label);
				$this->gTableColEnd();
				$this->gTableColBegin();
				if ($type==gI_PASSWORD)
				{
					$hint=gLng("hintpassword");
					$this->gOut("<acronym title='$hint'>");
					if ($value<>"")
						$value="_senhainalterada_";
					$this->gText($name,$value,$type,$default,$js);
					$this->gOut("</acronym>");
				} elseif ($type<20)
				{
					$this->gOut("<acronym title='$hint'>");
					$this->gText($name,$value,$type,$default,$js);
					$this->gOut("</acronym>");
				}
				if ($type==200) // DateBetween
				{
					$value1=$value;
					$value2=$value;
					if (is_array($value))
					{
	
						$value1=$value[0];
						$value2=$value[1];
					}
					$this->gOut("<acronym title='$hint'>");
					$this->gText($name."1",$value1,gI_DATENULL,$default,$js);
					$this->gOut(gLng("gform_ate"));
					$this->gText($name."2",$value2,gI_DATENULL,$default,$js);
					$this->gOut("</acronym>");
				}
				if ($type==201) // DateTimeBetween
				{
					$value1=$value;
					$value2=$value;
					if (is_array($value))
					{
	
						$value1=$value[0];
						$value2=$value[1];
					}
					$this->gOut("<acronym title='$hint'>");
					$this->gText($name."1",$value1,gI_DATETIMENULL,$default,$js);
					$this->gOut(gLng("gform_ate"));
					$this->gText($name."2",$value2,gI_DATETIMENULL,$default,$js);
					$this->gOut("</acronym>");
				}
				if ($type==202) // NumBetween
				{
					$value1=$value;
					$value2=$value;
					if (is_array($value))
					{
	
						$value1=$value[0];
						$value2=$value[1];
					}
					$this->gOut("<acronym title='$hint'>");
					$this->gText($name."1",$value1,gI_NUM,$default,$js);
					$this->gOut(gLng("gform_ate"));
					$this->gText($name."2",$value2,gI_NUM,$default,$js);
					$this->gOut("</acronym>");
				}
				if ($type==203) // TextBetween
				{
					$value1=$value;
					$value2=$value;
					if (is_array($value))
					{
	
						$value1=$value[0];
						$value2=$value[1];
					}
					$this->gOut("<acronym title='$hint'>");
					$this->gText($name."1",$value1,gI_TEXT,$default,$js);
					$this->gOut(gLng("gform_ate"));
					$this->gText($name."2",$value2,gI_TEXT,$default,$js);
					$this->gOut("</acronym>");
				}
				
				if (($type>=20) && ($type<30))
				{
					if ($type==24) $this->gOut("<acronym title='$hint'>");
					$this->gSelect($name,$value,$default,$type,$js);
					if ($type==24) $this->gOut("</acronym>");
				}
				if ($type==gI_READONLY)
				{
					$this->gOut($value);
				}
				if ($type==gI_RADIO) $this->gRadio($name,$value,$default,$js);
				if ($type==gI_MEMO) $this->gMemo($name,$value,100,4,$js);
				if ($type==gI_SUPERMEMO)
				{
					$value=str_replace("&quot;","\"",$value);
					$this->gSuperMemo($name,$value,80,8,$js);
				}
				if ($type==gI_CHECK)
				{
					$this->gOut("<acronym title='$hint'>");
					$this->gCheck($name,$value,$js);
					$this->gOut("</acronym>");
				}
				if ($type==gI_SHOW) $this->gOut($value);
	
				$this->gTableColEnd();
				if ((!$impar) || ($frmtype<>""))
				{
					$linhafechada=true;
					$this->gTableRowEnd();
					$cntrow++;
				}
				if (($this->breakForm) && ($cntrow==$this->breakFormRows))
				{
					if (!$linhafechada)
						$this->gTableRowEnd();
					$impar=false;
					$tabid=$this->table_count;
					$this->gTableRow(array("~$cs-><a href='#' onClick='gShowHide(\"gTable".($tabid+1)."\")'>".gLng("advanced.long")."</a>&nbsp;"));

					$this->gTableRowBegin();
					$this->gTableColBegin("colspan='$cs'");
					$this->tableDefaultDisplay='none';
					$this->gTableBegin(gT_BIG,false);
					$this->gTableRow(array("~$cs"));
					$cntrow++;
					$doBreakForm=true;
				}
				$impar=!$impar; // inverte valor de impar
			}
			if (($type==gI_READONLY) || ($type==gI_HIDDEN))
			{
				$hidden.="<input type='hidden' name='$name' value='$value'>";
			}
			if ($gDebug>0) $this->gOut($cr);
		}
		$this->tableDefaultDisplay='block';
		if ($doBreakForm)
		{
			$this->gTableEnd();
			$this->gTableColEnd();
			$this->gTableRowEnd();
		}
		// Converte fields em algo processável pelo HTML
		
		$s.="~".$cs."<>".$hidden;
		if ($table<>"")
			$s.="<input type='hidden' name='gTable' value='$table'>";
		$s.="<input type='hidden' name='gFields' value='$gFields'>";
		$submit=" onClick=\"this.disabled=true,this.form.submit();\"";
		if (!is_array($connectors))
			$submit=" onClick=\"$connectors\"";
		$s.="<input type='submit' class='tool' value='". $link_button."'$submit>&nbsp;&nbsp;&nbsp;<input type='reset' class='tool' value='". gLng("reset.short")."'>";
   	$this->gOut("<tr><td style='height: 6px' colspan='$cs'></td></tr>");
		$this->gTableRow(array($s),gT_FOOTER);
	}
   $this->gTableEnd();
   $this->gOut("</form>");
	//$this->gOut("</div>");
   //if ($gDebug>0) $this->gOut($cr);
  }
}

?>
