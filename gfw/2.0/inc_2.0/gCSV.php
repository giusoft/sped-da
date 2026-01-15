<?
include_once $gPathDefault."gForm.php";

define("gD_FIELD_SEPARATOR",";");
define("gD_FIELD_DELIMITER","");
define("gD_ROW_SEPARATOR",chr(13));

/** Função responsável pela conversão de uma string CSV para Array (corrigindo imperfeições)
@author Giuliano Nascimento
@version 2.0
@param String $texto	= Texto em formato CSV
@return Array $ArrCSV
*/
function gCSV2Array($texto,$field_delimiter=gD_FIELD_DELIMITER,$field_separator=gD_FIELD_SEPARATOR,$row_separator=gD_ROW_SEPARATOR)
{
	$linhas="";
	if (trim($texto)<>"")
	{
		$linhas=explode($row_separator,trim($texto)); // quebra as linhas
		for ($a=0; $a<count($linhas); $a++)
		{
			$mtz=explode($field_separator,str_replace("#","",str_replace(chr(13),"",str_replace(chr(10),"",$linhas[$a]))));
			for ($b=0; $b<count($mtz); $b++)
			{
				if ($field_delimiter<>"")
				{
					$mtz[$b]=substr($mtz[$b],0,strlen($mtz[$b])-1);
					$mtz[$b]=substr($mtz[$b],1,strlen($mtz[$b])-1);
				}
				//$mtz[$b]=gCleanField($mtz[$b]);
			}
			$linhas[$a]=$mtz;
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
function gArray2CSV($arr,$field_delimiter=gD_FIELD_DELIMITER,$field_separator=gD_FIELD_SEPARATOR,$row_separator=gD_ROW_SEPARATOR)
{
	$sai="";
	foreach ($arr as $linha)
	{
		$s="";
		foreach($linha as $campos)
		{
			$s.=$field_delimiter.$campos.$field_delimiter.$field_separator;
		}
		$sai.=$s.$row_separator;
	}
	return ($sai);
}

/** Gera uma tabela a partir de uma string CSV
@author Giuliano Nascimento
@version 2.0
@param String $texto	= Texto em formato CSV
@return void
*/
function gCSVTable($obj,$texto,$prefixo="")
{
	if (trim($texto)<>"")
	{
		if ($prefixo<>"")
			$prefixo.="_";
		
		$csv=gCSV2Array($texto);
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
				if ($linha[3]<20)	
				{
					$mtz[]="<-".$obj->gText($prefixo.$linha[0],$linha[2],$linha[3],$linha[4],$linha[5],false);
				}
				// Select - Formato: nome_campo,lista,valor_padrao,tipo,param
				if (($linha[3]>=20) && ($linha[3]<=29))	
				{
					if (strpos($linha[4],"|")===false)
						$mtz[]="<-".$obj->gSelect($prefixo.$linha[0],$linha[4],$linha[5],$linha[3],$linha[6],false);
					else
						$mtz[]="<-".$obj->gSelect($prefixo.$linha[0],explode("|",$linha[4]),$linha[5],$linha[3],$linha[6],false);
				}
				// Radios - Formato: nome_campo,valor_padrao,tipo,param
				if (($linha[3]>=30) && ($linha[3]<=39))	
				{
					$mtz[]="<-".$obj->gRadio($prefixo.$linha[0],explode("|",$linha[4]),$linha[2],$linha[5],false);
				}
				// Memo - Formato: nome_campo,valor_padrao,colunas,linhas,param
				if ($linha[3]==40) 
				{
					$mtz[]="<-".$obj->gMemo($prefixo.$linha[0],$linha[2],"35","8",$linha[4],false);
				}
				// SuperMemo - Formato: nome_campo,valor_padrao,colunas,linhas,param
				if ($linha[3]==41) 
				{
					$mtz[]="<-".$obj->gSuperMemo($prefixo.$linha[0],$linha[2],"35","8",$linha[4],false);
				}
				// Checkbox - Formato: nome_campo,valor_padrao,param
				if (($linha[3]>=50) && ($linha[3]<=59))	
				{
					$mtz[]="<-".$obj->gCheck($prefixo.$linha[0],$linha[2],$linha[6],false);
				}
				// Show - Formato: nome_campo,valor_padrao
				if ($linha[3]==102)
				{
					$mtz[]="<-".$linha[2];
				}
				$obj->gTableRow($mtz);
			}
		}
	}
}

function gPost2DBTXT()
{
	$sai="";
	return ($sai);
}



/** Classe responsável pela geração de código HTML para tratamento de CSV
* @package	gCSV
* @author	Giuliano Nascimento
* @version	2.0
*/
class gCSV extends gForm
{
	function __construct()
	{
		$this->gBegin();
	}
	function __destruct()
	{
		$this->gEnd();
	}
	function gCSVUpload($sql)
	{
		$thispage=$_SERVER["PHP_SELF"];
		$action=$_REQUEST["gAction"];
		if ($action=="")
		{
			$formtype="upload";
			$name="upload";
			$label=gT("Tratamento de arquivo CSV");
			$tmp="";
			$tmp[]=array(gI_HIDDEN,"gAction","uploadcsv");
			$tmp[]=array(gI_FILE,array(gT("Arquivo"),"arquivo"),"");
			$tmp[]=array(gI_SELECT,array(gT("Delimitador de campos"),"del_campos"),array(gT("Detectar automaticamente"),' ','"',chr(39)));
			$tmp[]=array(gI_SELECT,array(gT("Separador de campos"),"sep_campos"),array(gT("Detectar automaticamente"),';',','));
			$tmp[]=array(gI_SELECT,array(gT("Separador de linhas"),"sep_linhas"),array(gT("Detectar automaticamente"),'{CR}','{CR}{LF}','{LF}'));
			$this->gShowForm(array($label,$name),$rs,$tmp,$thispage,"",$formtype);
		} else
		{
			// Prepara a variável do arquivo
			$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
			
			// Tamanho máximo do arquivo (em bytes)
			$config["tamanho"] = 1000000;
			$erro="";
			
			// Formulário postado... executa as ações
			if ($arquivo) {  
				// Verifica se o mime-type do arquivo é de imagem
				if ((!eregi("^text\/(csv|txt|comma-separated-values)$", $arquivo["type"])) && (!eregi("^application\/(octet-stream)$", $arquivo["type"])) ) {
					$erro[] = "Arquivo em formato inválido! Deve ser obrigatoriamente CSV ou TXT. Envie outro arquivo...";
				} else {
				  // Verifica tamanho do arquivo
				  if ($arquivo["size"] > $config["tamanho"]) {
						$erro[] = "Arquivo em tamanho muito grande! Deve ter no máximo " . $config["tamanho"] . " bytes. Envie outro arquivo...";
				  }		  
				} 
				$del_campos=$_POST['del_campos'];
				$sep_campos=$_POST['sep_campos'];
				$sep_linhas=$_POST['sep_linhas'];
				if (!is_array($erro))
				{
					$pArq = $_FILES["arquivo"]["tmp_name"];
					$pTipo = $_FILES["arquivo"]["type"];
					if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
						$pDir  = "c:\\";
					else
						$pDir  = '/tmp/';
					//MOVE
					move_uploaded_file($pArq, $pDir."csv$idlan.csv");
					//ABRE ARQUIVO
					$pont = fopen($pDir."csv$idlan.csv", "rb");					
					//PERCORRE O ARQUIVO
					$csv = fread($pont, filesize($pDir."csv$idlan.csv"));
					if ($del_campos==gT("Detectar automaticamente"))
					{
						$lin=$csv[0];
						if (substr($lin,0,1)=="'")
							$del_campos="'";
						elseif (substr($lin,0,1)=="\"")
							$del_campos="\"";
						else
							$del_campos="";
					}
					if ($sep_campos==gT("Detectar automaticamente"))
					{
						$lin=$csv;
						$cntv=substr_count($lin,",");
						$cntp=substr_count($lin,";");
						if ($cntv>$cntp)
							$sep_campos=",";
						else
							$sep_campos=";";
					}
					if ($sep_linhas==gT("Detectar automaticamente"))
					{
						$lin=$csv;
						$cnt1=substr_count($lin,chr(13));
						$cnt2=substr_count($lin,chr(13).chr(10));
						$cnt3=substr_count($lin,chr(10));
						$sep_linhas=chr(13);
						if (($cnt2>$cnt1) && ($cnt2>$cnt3))
							$sep_linhas=chr(13).chr(10);
						elseif (($cnt3>$cnt1) && ($cnt3>$cnt2))
							$sep_linhas=chr(10);
					}
					$mtz=gCSV2Array($csv,$del_campos,$sep_campos,$sep_linhas);
					if ($sql<>"")
					{
						$rstmp=gQueryLimit($sql,1,1);
						$rs=strtolower($sql);
						$table=substr($sql,strpos($rs," from ")+6);
						if (strpos($table," ")>0)
						{
							$table=substr($table,0,strpos($table," "));
						}
						$ttlfld=$rstmp->FieldCount();
						$flds="";
						for ($g_t=0; $g_t<$ttlfld; $g_t++)
						{
							$fld=$rstmp->FetchField($g_t);
							$fldtype=$rstmp->MetaType($fld);
							$fldname=$fld->name;
							$fldvalue=$rstmp->fields[$g_t];
							$fldmaxlen=$fld->max_length;
							$flds[]=$fld->name;
						}
						//$insert="insert into $table (".implode(",",$flds).") values (";
						$insert="insert into $table (";
						foreach ($mtz as $lin)
						{
							$camp="";
							for ($c=0; $c<count($lin); $c++)
							{
								$camp[]=$flds[$c];
							}
							$query=$insert.implode(",",$camp).") values ('".implode("','",$lin)."')";
							gQuery($query);
						}
						//$query.=$val.")";
						
					}

					/*
					echo "<h1> = ".ord($sep_linhas)." = $cnt1 $cnt2 $cnt3</h1>";
					echo "<div align='left'><pre>";
					print_r($mtz);
					echo "</pre></div>";
					*/
					return($mtz);
				} else
				{
					$this->gMsgTitle("Processamento de arquivo CSV");
					foreach ($erro as $msg)
						$this->gMsgError($msg);
					return("");
				}
			}
			
		}
	}
	function gCSVDownload($sql)
	{
	}
}

?>
