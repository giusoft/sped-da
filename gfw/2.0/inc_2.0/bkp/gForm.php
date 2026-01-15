<?

include $gPathDefault."gInput.php";

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
* @param String $frmtype Tipo de formulário, podendo ser: "upload" ou ""
* @return void
*/
  function gShowForm($name,$rs,$fields,$link,$connectors="",$frmtype="")
  {

	global $gDebug;
	global $cr;
	$sql="";
   $label=$name;
	$dbidd=-1;
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
	$this->gMsgTitle($label);
	if ($frmtype=="upload")
	{
   	$this->gOut("<form enctype='multipart/form-data' action='$link' name='$name' method='post'>");
	} else
	{
   	$this->gOut("<form action='$link' name='$name' method='post'>");
	}
   //echo gVar("table.size");
   $this->gTableBegin(gVar("table.size"),true,true);
   $this->gTableRow(array("~2". gLng("gform.short")),gT_HEADER);

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
			$fldname=$fld->name;
			$fldvalue=$rstmp->fields[$g_t];
			$fldmaxlen=$fld->max_length;
			$fldhint="";
			$fldjs="";
			if ($fldtype=="C")
			{
				$fldhint=gLng("hint.text").' '.$fldmaxlen.' '.gLng("hint.chars");
			}
			if ($fldtype=="D")
			{
				$fldvalue=gDate($fldvalue);
				$fldmaxlen=strlen($fldvalue);
				$fldhint=gLng("hint.date").' '.gVar("global.dateformat");
			}
			if ($fldtype=="T")
			{
				$fldvalue=gDateTime($fldvalue);
				$fldmaxlen=strlen($fldvalue);
				$fldhint=gLng("hint.datetime").' '.gVar("global.dateformat").' '.gVar("global.timeformat");
			}
			if ($fldtype=="N")
			{
				$fldvalue=gFloat($fldvalue);
				$fldhint=gLng("hint.num");
			}
			if (($fldtype=="B") || ($fldtype=="X"))
			{
				//$fldvalue=nl2br($fldvalue);
			}
			if (($fldtype=="L") || (($fldmaxlen<=4) && ($fldtype=="I")))
			{
				$fldtype="L";
				$fldhint=gLng("hint.check");
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
							$fldhint=gLng("hint.selectcode");
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
				if ($fldname[1]=="id")
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
//gD($fields);
	if ($dbidd==0)
	{
		$this->gTableRow(array(gLng("message.denied.long")));
	} else
	{
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
			if (count($field)>2) $value=$field[2];
			if (count($field)>3) $default=$field[3];
			//if (count($field)>3) $hint=$field[4];
			if (($type<>gI_EXCLUDE) && ($type<>gI_HIDDEN))
			{
				$this->gTableRowBegin();
				$this->gTableColBegin();
				$this->gOut($label);
				$this->gTableColEnd();
				$this->gTableColBegin();
				if ($type==gI_PASSWORD)
				{
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
				if ($type==gI_MEMO) $this->gMemo($name,$value,55,8,$js);
				if ($type==gI_SUPERMEMO) $this->gSuperMemo($name,$value,80,8,$js);
				if ($type==gI_CHECK)
				{
					$this->gOut("<acronym title='$hint'>");
					$this->gCheck($name,$value,$js);
					$this->gOut("</acronym>");
				}
				if ($type==gI_SHOW) $this->gOut($value);
	
				$this->gTableColEnd();
				$this->gTableRowEnd();
			}
			if (($type==gI_READONLY) || ($type==gI_HIDDEN))
			{
				$hidden.="<input type='hidden' name='$name' value='$value'>";
			}
			if ($gDebug>0) $this->gOut($cr);
		}
		// Converte fields em algo processável pelo HTML
		$s="~2<>";
		if ($gDebug>0) $s.=$cr;
		$s.=$hidden;
		if ($gDebug>0) $s.=$cr;
		if ($table<>"")
			$s.="<input type='hidden' name='gTable' value='$table'>";
		$s.="<input type='hidden' name='gFields' value='$gFields'>";
		$submit="";
		if (!is_array($connectors))
			$submit=" onClick=\"$connectors\"";
		$s.="<input type='submit' class='tool' value='". $link_button."'$submit>&nbsp;&nbsp;&nbsp;<input type='reset' class='tool' value='". gLng("reset.short")."'>";
		$this->gTableRow(array($s),gT_FOOTER);
	}
   $this->gTableEnd();
   $this->gOut("</form>");
	//$this->gOut("</div>");
   if ($gDebug>0) $this->gOut($cr);
	}

}



?>
