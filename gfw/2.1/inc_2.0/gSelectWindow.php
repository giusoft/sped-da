<?
$gIncludes='gInput.php';
include_once 'gConf.php';
$out=new gInput();
$out->gBegin();
$out->gMsgSubTitle(gLng("includes.register_select.long"));
define(PATH_IMG, $_SERVER['DOCUMENT_ROOT'].gBAR.gBASE.gBAR."online/");
define(PATH_IMGWEB, "../online");
define(MAX_WIDTH, 200);
define(MAX_HEIGHT, 134);

$keys=array_keys($_REQUEST);
if ($maxreg=="")
	$maxreg=gVar("database.maxrows")*2.5;$maxreg=500;
$reply="";
$where="";
$sqlf=$gSql;
$sqlf=substr($sqlf,7); // tira o "select"
$sqlf=substr($sqlf,0,strpos(strtolower($sqlf)," from ")); // deixa somente os campos
$sf=split(',',$sqlf); // array de campos
$sc="";
for ($a=0; $a<count($sf); $a++)
{
	$s=trim($sf[$a]);
	$t=$s;
	if (strpos($s,'.')>0)
	{
		$s=substr($s,strpos($s,'.')+1);
	}
	if (strpos(strtolower($s),' as ')>0)
	{
		$s=substr($t,strpos(strtolower($t),' as ')+4);
		$t=substr($t,0,strpos(strtolower($t),' as '));
	}
	$sc[]=array($s,$t);
	//echo $s."=".$t."<br>";
}
foreach ($keys as $key)
{
	$reply[]=array($key,$_REQUEST[$key]);
	if (substr($key,0,5)=="_flt_")
	{
		//if ($key!="_flt_id")
		//{
			for ($a=0; $a<count($sc); $a++)
			{
				if (trim(substr($key,5))==$sc[$a][0])
				{
					if ($where<>"") $where.=" and ";
						$where.=$sc[$a][1]." like '%".$_REQUEST[$key]."%'";
				}
			}

		//}
	}
}
$out->gOut("<form action='".$_SERVER["PHP_SELF"]."' method='post'>");
$out->gOut("<input type='hidden' name='gSql' value='". $gSql."'>");
$out->gOut("<input type='hidden' name='gType' value='". $gType."'>");
$out->gOut("<input type='hidden' name='gFiltro' value='sim'>");
$out->gOut("<input type='hidden' name='gFormField' value='$gFormField'>");
$gSql=str_replace("$","'",$gSql);
$gSql=str_replace("_MAIS_","+",$gSql);

if ($gFiltro<>"")
{
	$gSql=str_replace("$","'",$gSql);
	if (strpos(strtolower($gSql)," where ")>0)
	{
		$pnt=strpos(strtolower($gSql)," where ")+7;
		$where="$where and ";
	} else
	{
		$pnt=strpos(strtolower($gSql)," order ");
		if ($pnt==0) $pnt=strlen($gSql);
		$where=" where $where";
	}
	$gSql=substr($gSql,0,$pnt).$where.substr($gSql,$pnt);
//	$gSql.=" LIMIT $maxreg";
} else
{
//	$gSql.=" LIMIT $maxreg";
}
$rs=gQueryLimit($gSql,0,$maxreg);
$ttlfld=$rs->FieldCount();
$mtz="";
$cab="";
$out->gTableBegin(gT_BIG,false);
for ($g_t=0; $g_t<=$ttlfld; $g_t++)
{
	$fld=$rs->FetchField($g_t);
	$fldtype=$rs->MetaType($fld);
	$fldname=$fld->name;
	$fldvalue=$rs->fields[$g_t];
	// ajustes para SQLServer
	if ((substr(gVar("database.type"),0,5)<>"mysql") && ($fld->type=='char') && ($fld->max_length==10)) $fldtype="D";
	if ((substr(gVar("database.type"),0,5)<>"mysql") && ($fld->type=='datetime')) $fldtype="T";
	if ((substr(gVar("database.type"),0,5)<>"mysql") && ($fld->type=='bigint')) $fldtype="I";
	if ((substr(gVar("database.type"),0,5)<>"mysql") && ($fld->type=='bigint identity')) $fldtype="R";
	
	$out->gOut("<tr><td align='left'>".gField($fldname)."</td><td>");
	/*
	if ($fldtype=="D")
		$out->gText("_flt_".$fldname,${"_flt_$fldname"},gI_DATENULL);
	elseif ($fldtype=="T")
		$out->gText("_flt_".$fldname,${"_flt_$fldname"},gI_DATETIMENULL);
	elseif ($fldtype=="N")
		$out->gText("_flt_".$fldname,${"_flt_$fldname"},gI_NUM);
	else
	*/
		$out->gText("_flt_".$fldname,${"_flt_$fldname"},gI_TEXT);
	
	$out->gOut("</td></tr>");
	$mtz[]=$fldname;
	$cab[]="<-".gField($fldname);
}
$out->gTableEnd();
$out->gOut("<input type='submit' value='". gLng("register_refresh")."'>");
$out->gMsg(" Mostrar no máximo ".$out->gText("maxreg",$maxreg,gI_NUM,8,"",false)." registros");
$out->gOut("</form>");
if ($gType=="selectcode")
{
	//if ($where<>"")
	{
		$out->gTableBegin(gT_BIG,true);
		$out->gTableRow($cab,gI_HEADER);
		$c=0;
		while (!$rs->EOF)
		{
			$c++;
			$mtz2="";
			for ($a=0;$a<count($mtz);$a++)
			{
				if ($a<=1)
					//$mtz2[]="<-<a class='menu' href=\"java01-01-00 13:37:35script: window.opener.document.forms[0].$gFormField.value=".$rs->fields[$mtz[$a]].";window.opener.document.forms[0].b$gFormField.value='".$rs->fields[$mtz[$a+1]]."';self.close();\">".$rs->fields[$mtz[$a]]."</a>";
				
				/*
				
					$mtz2[]="<-<a class='menu' href=\"javascript: window.opener.document.forms[0].$gFormField.value=".$rs->fields[$mtz[$a-$a]].";var element = window.opener.getElementByID('$gFormField'); element.innerHTML =' <i>".$rs->fields[$mtz[$a]]."</i>';self.close();\">".$rs->fields[$mtz[$a]]."</a>";
					
				*/	
					$mtz2[]="<-<a class='menu' href=\"javascript: window.opener.document.forms[0].$gFormField.value=".$rs->fields[$mtz[$a-$a]]."; window.opener.document.all['".$gFormField."_txt'].innerHTML=' <i>".$rs->fields[$mtz[$a]]."</i>';self.close();\">".$rs->fields[$mtz[$a]]."</a>";
				else
					$mtz2[]="<-".$rs->fields[$mtz[$a]];
			}
			$out->gTableRow($mtz2);
			$rs->MoveNext();
		}
		$out->gTableEnd();
		$out->gMsg("Registros exibidos: $c");
	}
} else
{
	//if ($where<>"")
	{
		$image_path=PATH_IMG;
		$out->gTableBegin(gT_BIG,true);
		$col=0;
		while (!$rs->EOF)
		{
			$arq=$image_path.$rs->fields[1].$rs->fields[2];
			//echo $image_path."/".$rs->fields[1].$rs->fields[2];
			if (file_exists($arq))
			{
				$img = @imagecreatefromjpeg($arq);
				if (!$img) { /* See if it failed */
					$img  = imagecreate(150, 30); /* Create a blank image */
					$bgc = imagecolorallocate($img, 255, 255, 255);
					$tc  = imagecolorallocate($img, 0, 0, 0);
					imagefilledrectangle($img, 0, 0, 150, 30, $bgc);
					/* Output an errmsg */
					imagestring($img, 1, 5, 5, "Erro na imagem: ".$rs->fields[0], $tc);
				} 
				$width = imagesx($img);
				$height = imagesy($img);
				$scale = min(MAX_WIDTH/$width, MAX_HEIGHT/$height);
				
				// encolhe a imagem se ela for maior que o permitido
				$new_width = floor($scale * $width);
				$new_height = floor($scale * $height);
				
				//cria o handle temporário com as novas dimensões
				$image_tmp = imagecreatetruecolor($new_width, $new_height);
				
				// copia e redimensiona a antiga imagem na nova
				imagecopyresampled($image_tmp, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				imagedestroy($img);
				imagejpeg($image_tmp,$image_path."/".$rs->fields[1]."_".$rs->fields[2],60);
				if ($col==0)
					$out->gTableRowBegin();
				$out->gTableColBegin("align='center'");
				$out->gOut("<acronym title='[".$rs->fields[0]."] ".$rs->fields[3]."'><a href='".PATH_IMGWEB."/".$rs->fields[1].$rs->fields[2]."' target='_new'><img src='".PATH_IMGWEB."/".$rs->fields[1]."_".$rs->fields[2]."' border='0'></a><br><a href=\"javascript: window.opener.document.forms[0].$gFormField.value=".$rs->fields[0].";self.close();\" style='font-size: 8pt'>".$rs->fields[3]."</a></acronym>");
				$out->gTableColEnd();
				$col++;
				if ($col==3)
				{
					$out->gTableRowEnd();
					$col=0;
				}
			}
			$rs->MoveNext();
		}
		$out->gTableEnd();
	}
}
$out->gEnd();
?>
