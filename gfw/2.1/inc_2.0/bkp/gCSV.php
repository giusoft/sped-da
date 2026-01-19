<?
define("gD_FIELD_SEPARATOR",";");
define("gD_ROW_SEPARATOR",chr(13));

/** Função responsável pela conversão de uma string CSV para Array (corrigindo imperfeições)
@author Giuliano Nascimento
@version 2.0
@param String $texto	= Texto em formato CSV
@return Array $ArrCSV
*/
function gCSV2Array($texto)
{
	$linhas="";
	if (trim($texto)<>"")
	{
		$linhas=explode(gD_ROW_SEPARATOR,trim($texto)); // quebra as linhas
		for ($a=0; $a<count($linhas); $a++)
		{
			$linhas[$a]=explode(gD_FIELD_SEPARATOR,str_replace("#","",str_replace(chr(13),"",str_replace(chr(10),"",$linhas[$a]))));
		}
	}
	return ($linhas);
}

/** Função responsável pela conversão de uma string CSV para Array associativo (corrigindo imperfeições)
@author Giuliano Nascimento
@version 2.0
@param String $texto	= Texto em formato CSV
@return Array $ArrCSV
*/
function gCSV2AArray($texto)
{
	$linhas="";
	if (trim($texto)<>"")
	{
		$linhas=explode(gD_ROW_SEPARATOR,trim($texto)); // quebra as linhas
		for ($a=0; $a<count($linhas); $a++)
		{
			$s=explode(gD_FIELD_SEPARATOR,str_replace("#","",str_replace(chr(13),"",str_replace(chr(10),"",$linhas[$a]))));
			$linhas[$s[0]]=$s[1];
		}
	}
	return ($linhas);
}

/** Função responsável pela conversão de um Array para uma string CSV
@author Giuliano Nascimento
@version 2.0
@param Array $arr	= Array para conversão
@return String $CSV
*/
function gArray2CSV($arr)
{
	$sai="";
	foreach ($arr as $linha)
	{
		$s="";
		foreach($linha as $campos)
		{
			$s.=$campos.gD_FIELD_SEPARATOR;
		}
		$sai.=$s.gD_ROW_SEPARATOR;
	}
	return ($sai);
}

/** Gera uma tabela a partir de uma string CSV
@author Giuliano Nascimento
@version 2.0
@param String $texto	= Texto em formato CSV
@return void
*/
function gCSVTable($obj,$texto,$prefixo="",$valores="")
{
	if (trim($texto)<>"")
	{
		if ($prefixo<>"")
			$prefixo.="_";
		
		$csv=gCSV2Array($texto);
		if ($valores<>"")
		{
			$valores=gCSV2Array($valores);
		}
		$cnt=0;
		foreach ($csv as $linha)
		{
			// Hidden - Formato: nome_campo,valor_padrao,tipo,tamanho_maximo,param
			if ($linha[3]==100)	
			{
				echo "<input type='hidden' name='$prefixo"."$linha[0]' value='".$linha[1].$linha[2]."'>";
			} else
			{
				$mtz="";
				$mtz[]="<-".$linha[1];
				// Textos - Formato: nome_campo,valor_padrao,tipo,tamanho_maximo,param
				$val=".null.";
				if (is_array($valores))
				{
					$val=$valores[$cnt][1];
				}
				if ($linha[3]<20)	
				{
					if ($val==".null.") $val=$linha[2];
					$mtz[]="<-".$obj->gText($prefixo.$linha[0],$val,$linha[3],$linha[4],$linha[5],false);
				}
				// Select - Formato: nome_campo,lista,valor_padrao,tipo,param
				if (($linha[3]>=20) && ($linha[3]<=29))	
				{
					if ($val==".null.") $val=$linha[5];
					if (strpos($linha[4],"|")===false)
						$mtz[]="<-".$obj->gSelect($prefixo.$linha[0],$linha[4],$val,$linha[3],$linha[6],false);
					else
						$mtz[]="<-".$obj->gSelect($prefixo.$linha[0],explode("|",$linha[4]),$val,$linha[3],$linha[6],false);
				}
				// Radios - Formato: nome_campo,valor_padrao,tipo,param
				if (($linha[3]>=30) && ($linha[3]<=39))	
				{
					if ($val==".null.") $val=$linha[2];
					$mtz[]="<-".$obj->gRadio($prefixo.$linha[0],explode("|",$linha[4]),$linha[2],$linha[5],false);
				}
				// Memo - Formato: nome_campo,valor_padrao,colunas,linhas,param
				if ($linha[3]==40) 
				{
					if ($val==".null.") $val=$linha[2];
					$mtz[]="<-".$obj->gMemo($prefixo.$linha[0],$val,"35","8",$linha[4],false);
				}
				// SuperMemo - Formato: nome_campo,valor_padrao,colunas,linhas,param
				if ($linha[3]==41) 
				{
					if ($val==".null.") $val=$linha[2];
					$mtz[]="<-".$obj->gSuperMemo($prefixo.$linha[0],$val,"35","8",$linha[4],false);
				}
				// Checkbox - Formato: nome_campo,valor_padrao,param
				if (($linha[3]>=50) && ($linha[3]<=59))	
				{
					if ($val==".null.") $val=$linha[2];
					$mtz[]="<-".$obj->gCheck($prefixo.$linha[0],$val,$linha[6],false);
				}
				// Show - Formato: nome_campo,valor_padrao
				if ($linha[3]==102)
				{
					if ($val==".null.") $val=$linha[2];
					$mtz[]="<-".$linha[2];
				}
				$obj->gTableRow($mtz);
			}
			$cnt++;
		}
	}
}


function gPost2DBTXT()
{
	$sai="";
	return ($sai);
}

?>