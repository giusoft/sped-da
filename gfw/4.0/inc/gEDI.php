<?

define(gEDI_FIXED,0);
define(gEDI_CSV,1);
define(gEDI_LEFT,STR_PAD_RIGHT);
define(gEDI_RIGHT,STR_PAD_LEFT);
define(gEDI_CENTER,STR_PAD_BOTH);

/** Classe que interpreta e trata arquivos EDI
 * @package	gEDI
 * @uses gStart.php
 * @author	giuliano
 * @version	1.0 17-04-2009 17:40
 */
 
 /*
	Forma de uso:
 
   RECONHECENDO ARQUIVO EDI 
	
	// Seta alguns parâmetros para processamento
	$prefs["type"]=gEDI_FIXED;
	$prefs["useRowIdentifier"]=true;

	$arq=new gEDI($prefs);

	// Define tipos de dados em cada linha
	$tipo_de_linha=array(						
			array("Nome",gI_TEXT,60),
			array("Valor",gI_NUM,12,2), // 12 de tamanho e 2 de casas decimais
			array("Data",gI_DATE,8)
			);

	// Informa layout à classe
	$layout["1"]=$tipo_de_linha;
	$arq->setLayout($layout);	
	
	// Converte arquivo em array
	$mtz=$arq->toArray($arquivo_codificado,$prefs,3);		

   CRIANDO ARQUIVO EDI 
	
	// Seta alguns parâmetros para processamento
	$prefs["type"]=gEDI_FIXED;
	$prefs["useRowIdentifier"]=false;

	$arq=new gEDI($prefs);
	
	// Define tipos de dados em cada linha
	$layout=array(						
			array("Nome",gI_TEXT,60),
			array("Valor",gI_NUM,12,2), // 12 de tamanho e 2 de casas decimais
			array("Data",gI_DATE,8)
			);
	
	// Informa layout à classe
	$arq->setLayout($layout);		
	$campos[]=array("Giuliano",10.54,"2008-01-01"); // aceita formato DB
	$campos[]=array("José",12.54,"01-01-08"); // aceita formato brasileiro
	$campos[]=array("Maria",120.54,"2008-01-01");
	
	// Transforma array em arquivo EDI
	$edi=$arq->toEDI($campos,$prefs);		
 
 */
 class gEDI{
	var $in;		
	var $out="";	
	var $rows;	
	var $rowsByLayout;
	var $totalProcessed=0;	
	var $layout;	
	var $layoutFound=0;
	var $preferences;
	
	function __construct($prefs="")	
	{
		$this->in=$in;		
		$this->preferences["type"]=gEDI_FIXED;
		$this->preferences["separator"]=";";
		$this->preferences["eol"]="\r\n";
		$this->preferences["useRowIdentifier"]=true;
		$this->preferences["dateFormat"]="yyyymmdd";
		$this->preferences["floatFormat"]="0"; // divisão com decimais: 0.00
		$this->preferences["databaseFormat"]=false;
		$this->preferences["align"]=STR_PAD_RIGHT;
		if ($prefs<>"") $this->setPreferences($prefs);
	}	
	
	function detectDateSeparator($date="")
	{
		$sep="";
		if ($date=="")
			$date=$this->preferences["dateFormat"];
		if (strpos($date,"-")>0) $sep="-";
		if (strpos($date,"/")>0) $sep="/";
		if (strpos($date,".")>0) $sep=".";
		return($sep);
	}
	
	function detectFloatSeparator($num="")
	{
		$sep="";
		if ($num=="")
			$num=$this->preferences["floatFormat"];
		if (strpos($num,".")>0) $sep=".";
		if (strpos($num,",")>0) $sep=",";
		return($sep);
	}
	
	function setPreferences($prefs)
	{
		foreach ($prefs as $pref=>$valor)
			$this->preferences[$pref]=$valor;
		$this->preferences["dateSeparator"]=$this->detectDateSeparator();
		$this->preferences["floatSeparator"]=$this->detectFloatSeparator();
	}
	
	function date2EDI($date)
	{
		// Verifica se a data está no formato certo, senão, corrige
		if (substr($date,4,1)<>$this->detectDateSeparator(gVar("global.dateformat")))
			$date=gDBDate($date);
		$sep=$this->detectDateSeparator($date);
		$dateParts=explode($sep,$date);
		$dateFormat=$this->preferences["dateFormat"];
		$dateNew=str_replace("yyyy",$dateParts[0],$dateFormat);
		$dateNew=str_replace("yy",substr($dateParts[0],-2),$dateNew);
		$dateNew=str_replace("mm",str_pad($dateParts[1], 2, "0"),$dateNew);
		$dateNew=str_replace("m",intval($dateParts[1]),$dateNew);
		$dateNew=str_replace("dd",str_pad($dateParts[2], 2, "0"),$dateNew);
		$dateNew=str_replace("d",intval($dateParts[2]),$dateNew);
		return($dateNew);
	}
	
	function EDI2date($date)
	{
		$dateFormat=$this->preferences["dateFormat"];
		$sep=$this->preferences["dateSeparator"];
		$yearPos=strpos($dateFormat,"yyyy");
		$yearSize=4;
		if (($yearPos===false))
		{
			$yearPos=strpos($dateFormat,"yy");
			$yearSize=2;
		}
		$monthPos=strpos($dateFormat,"mm");
		$monthSize=2;
		if ($monthPos===false)
		{
			$monthPos=strpos($dateFormat,"m");
			$monthSize=2;
		}
		$dayPos=strpos($dateFormat,"dd");
		$daySize=2;
		if ($dayPos===false)
		{
			$dayPos=strpos($dateFormat,"d");
			$daySize=2;
		}
		$dateParts[]=substr($date,$yearPos,$yearSize);
		$dateParts[]=substr($date,$monthPos,$monthSize);
		$dateParts[]=substr($date,$dayPos,$daySize);
		$dateNew=implode($this->detectDateSeparator(gVar("global.dateformat")),$dateParts);
		if (!$this->preferences["databaseFormat"])
			$dateNew=gDate($dateNew);
		return($dateNew);		
	}

	function EDI2Float($num)
	{
		$num=trim($num);
		$fmt=$this->preferences["floatFormat"];
		$sep=$this->preferences["floatSeparator"];
		if ($sep=="")
		{
			$numNew=floatval($num);
		} else
		{
			if (strpos($num,$sep)===false)
			{
				$fmtParts=explode($sep,$fmt);
				$numNew=floatval(substr($num,0,strlen($fmtParts[0])).".".substr($num,strlen($fmtParts[0])));
			} else
			{
				$numParts=explode($sep,$num);
				$numNew=floatval(implode(".",$numParts));
			}
		}
		if (!$this->preferences["databaseFormat"])
			$numNew=gFloat($numNew);
		return($numNew);
	}
	
	function float2EDI($num)
	{
		// Identifica se está no formato brasileiro ou do banco
		$encontrouVirgula=strpos($num,",");
		$encontrouPonto=strpos($num,".");
		// Formato brasileiro
		if ( (($encontrouVirgula>0) && ($encontrouPonto>0) && ($encontrouVirgula<$encontrouPonto)) 
				|| (($encontrouVirgula>0) && ($encontrouPonto===false)) )
		{
			$num=gDBFloat($num);
		}
		if (strpos($num,".")===false)
		{
			$floatParts[0]=$num;
			$floatParts[1]="00";
		}
		else
			$floatParts=explode(".",$num);
		$floatRight=substr($this->preferences["floatFormat"],-1);
		$floatLeft=substr($this->preferences["floatFormat"],0,1);
		if ($this->preferences["floatSeparator"]=="")
		{
			$floatSizes[0]=strlen($this->preferences["floatSeparator"]);
			$floatSizes[1]=$floatParts[1];
		} else
			$floatSizes=explode($this->preferences["floatSeparator"],$this->preferences["floatFormat"]);

//echo "<Pre>Align: ".$this->preferences["align"]."\nPartes:\n";print_r($floatParts);print_r($floatSizes);echo " floatRight: $floatRight</pre><br>";
		// Verifica se precisa arredondar
		if (strlen($floatParts[1])>strlen($floatSizes[1]))
			$floatParts[1]=substr(round(floatval("0.".$floatParts[1]),strlen($floatSizes[1])),2);
		$floatParts[1]=str_pad(substr($floatParts[1],0,strlen($floatSizes[1])),strlen($floatSizes[1]),$floatRight,STR_PAD_RIGHT);
		
		
		$numNew=implode($this->preferences["floatSeparator"],$floatParts);
		if ($this->preferences["align"]==gEDI_LEFT)
			$numNew=str_pad($numNew,strlen($this->preferences["floatFormat"]),$floatRight,STR_PAD_RIGHT);
		else
			$numNew=str_pad($numNew,strlen($this->preferences["floatFormat"]),$floatLeft,STR_PAD_LEFT);
		return($numNew);
	}
	
	
	function getTotalProcessed()	
	{		
		return ($this->totalProcessed);	
	}

	/** Registra o layout para reconhecimento do formato do arquivo
	 * @author	giuliano
	 * @version	1.0 29-04-2009 13:32
	 * @param string $layout Array com campos e formatos (nome, tipo, tamanho, valor)
	 */
	function setLayout($layout)		
	{
		$this->layout="";
		if ($this->preferences["useRowIdentifier"])
			$this->layout=$layout;
		else
		{
			$this->layout["none"]=$layout;
		}
	}

	/** Processa arquivo EDI e retorna Array com linhas encontradas
	 * @author	giuliano
	 * @version	1.0 29-04-2009 13:32
	 * @param string $in Arquivo texto formatado
	 * @param string $prefs Tipo de processamento do arquivo
	 * @return array $sai Array
	 */
	function toRows($in="",$prefs="")
	{
		if ($in<>"") $this->in=$in;
		if ($prefs<>"") $this->setPreferences($prefs);
		$this->rows=explode($this->preferences["eol"],$this->in);		
		$this->totalProcessed=count($this->rows);				
		$sai=$this->rows;
		$this->out=$sai;
		v($in);
		return($sai);	
	}	
	
	/** Processa arquivo EDI e retorna Array com dados encontrados
	 * @author	giuliano
	 * @version	1.0 29-04-2009 13:32
	 * @param string $in Arquivo texto formatado
	 * @param string $prefs Tipo de processamento do arquivo
	 * @param string $selectedLayout Só retornar o layout especificado
	 * @return array $sai Array
	 */
	function toArray($in="",$prefs="",$selectedLayout="")	
	{		
		$this->toRows($in,$prefs);
		$matriz="";			
		$tipoAnterior="none";
		
		for ($f=0; $f<$this->totalProcessed; $f++)		
		{			
			
			$linha=$this->rows[$f];			
			if ($this->preferences["useRowIdentifier"])
			{
				$tamTipo=1;
				$tipoLinha=substr($linha,0,$tamTipo);					
				$linha=substr($linha,$tamTipo);
			} else
				$tipoLinha="none";
			
			if ($linha<>"")
			{
				// Identifica qual o layout será usado de acordo com o tipo de linha						
				// (tipo="" significa, sem tipo, ou seja, o primeiro elemento do layout)						
				foreach ($this->layout as $layout=>$tiposColunas)
				{		
					if (($tipoLinha==$layout) || ($layout=="0")  || ($layout=="")  || ($layout=="none"))
					{						
						if ($tipoAnterior<>$tipoLinha)
						{
							if ($this->layoutFound>0)
								$this->rowsByLayout[$tipoAnterior]=$matriz;
							$this->layoutFound++;
							$tipoAnterior=$tipoLinha;
							$matriz="";
						}
						// processa linha de acordo com o layout										
					
						$colunas="";				
						$cnt=0;
						if ($this->preferences["type"]==gEDI_CSV)
						{
							$cols=explode($this->preferences["separator"],$linha);
							if ($this->preferences["useRowIdentifier"])
								$cols=array_slice($cols,1); // remove o primeiro elemento
						}
						foreach ($tiposColunas as $tipoColuna)										
						{												

							if ($this->preferences["type"]==gEDI_CSV)
							{
								$valor=trim($cols[$cnt]);
								$valor=str_replace("\r","",$valor);
								
								if ((substr($valor,0,1)=='"') && (substr($valor,-1)=='"') && (strlen($valor)>1))
								{
									$valor=substr($valor,1,strlen($valor)-2);
								}
							} elseif ($this->preferences["type"]==gEDI_FIXED)
							{
								if (is_array($tipoColuna))														
									$tam=$tipoColuna[2];							
								else														
									$tam=$tipoColuna;
								$valor=substr($linha,0,$tam);												
							}
							$valor=trim($valor);
							// Processa formatacao de campo				
							if (is_array($tipoColuna))
							{	
								if ($tipoColuna[1]==gI_DATE)
								{									
									
									$valor=$this->EDI2Date($valor);
								}
								if ($tipoColuna[1]==gI_NUM)
								{
									$fmt=substr("0000000000000000000000000000000000000",0,$tipoColuna[2]-$tipoColuna[3]).".".
											substr("0000000000000000000000000000000000000",0,$tipoColuna[3]);
									$prefs['floatFormat']=$fmt;
									$this->setPreferences($prefs);
									$valor=$this->EDI2Float($valor);
								}
							}						
							$colunas[]=$valor;										
							$linha=substr($linha,$tam);					
							$cnt++;
						}									
						if (($linha<>"") && ($this->preferences["type"]==gEDI_FIXED))
							$colunas[]=$linha;	
						$matriz[]=$colunas;							
					}			
				}
			}
		}	
		$this->rowsByLayout[$tipoAnterior]=$matriz;
		if (!$this->preferences["useRowIdentifier"])
			$sai=$this->rowsByLayout['none'];
		elseif ($selectedLayout=="")
			$sai=$this->rowsByLayout;
		else
			$sai=$this->rowsByLayout[$selectedLayout];
		$this->out=$sai;
		return ($sai);		
	}		
	
	/** Processa arquivo EDI e retorna uma tabela com os campos encontrados
	 * @author	giuliano
	 * @version	1.0 29-04-2009 13:32
	 * @param string $in Arquivo texto formatado
	 * @param string $prefs Tipo de processamento do arquivo
	 * @param string $layout (Opcional) qual o tipo de linha desejado
	 * @return array $sai Array
	 */
	function toTable($in="",$prefs="",$layout="")	
	{	
		// a fazer...		
		$this->toArray($in,$prefs);		
		$screen=new gOutput();		
		$tipo="";		
		for ($f=0; $f<$this->layoutFound; $f++)		
		{			
			$mtz="";			
			
			$layout_atual=$this->layout[$f][0];
			if (($layout==$layout_atual) || ($layout==""))
			{
				
				$l=$this->layout[$f][1];
				$cols="";
				for ($g=0; $g<count($l); $g++)
				{
					$cols[]=$l[$g][0];
				}
				$mtz[]=$cols;
				$linhas=$this->rowsByLayout[$layout_atual];
				for ($g=0; $g<count($linhas); $g++)
				{
					$mtz[]=$linhas[$g];
				}
				$screen->gTable($mtz);
			}
		}	
	}		

	/** Processa Array e retorna formato texto de acordo com o layout informado
	 * @author	giuliano
	 * @version	1.0 29-04-2009 13:32
	 * @param array $in Linhas e valores
	 * @param string $prefs Tipo de processamento do arquivo
	 * @return array $sai Array
	 */
	function toEDI($in="",$prefs="")	
	{		
		if ($in<>"") $this->in=$in;
		if ($prefs<>"") $this->setPreferences($prefs);
		for ($l=0; $l<count($in); $l++)
		{
			//$layout=$this->layout[
			$linha=$in[$l];
			$novalinha="";
			for ($c=0; $c<count($linha); $c++)
			{
				$tipoRow="none";
				if ($this->preferences["useRowIdentifier"])
					$tipoRow=$linha[0];
				$tipoColuna=$this->layout[$tipoRow][$c];
				$valor=$linha[$c];
				// Convertendo campos
				if ($tipoColuna[1]==gI_DATE)
				{
					$valor=$this->date2EDI($valor);
				}
				if ($tipoColuna[1]==gI_NUM)
					$valor=$this->float2EDI($valor);
				if ($tipoColuna[1]==gI_TEXT)
				{
					// Alinha o texto
					if ($tipoColuna[3]=="")
						$valor=str_pad($valor,$tipoColuna[2]," ",$this->preferences['align']);
					else
						$valor=str_pad($valor,$tipoColuna[2]," ",$tipoColuna[3]);
				}
				if ($this->preferences["type"]==gEDI_CSV)
					$valor=trim($valor);
				$novalinha[]=$valor;
			}
			if ($this->preferences["type"]==gEDI_CSV)
			{
				$novalinha=implode($this->preferences['separator'],$novalinha);
			} elseif ($this->preferences["type"]==gEDI_FIXED)
			{
				$novalinha=implode("",$novalinha);
			}
			$sai.=$novalinha.$this->preferences['eol'];
		}
		return($sai);
	}
	
	function __destruct()		
	{	
	}
}


?>
