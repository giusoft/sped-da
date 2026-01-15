<?
define("NL","\n");
if ($gPathLib<>"")
{
	include $gPathLib."nfephp/libs/ToolsNFePHP.class.php";
	include $gPathLib."nfephp/libs/DanfeNFePHP.class.php";
} else
{
	include $gPathDefault."nfephp/libs/ToolsNFePHP.class.php";
	include $gPathDefault."nfephp/libs/DanfeNFePHP.class.php";
}


class gNFeTools extends ToolsNFePHP
{
}

class gDanfe extends DanfeNFePHP
{
}


class gNFe extends ConvertNFePHP
{
	public $c,$i,$s;
	public $nfetxt,$nfexml, $nfeid;
	public $erros;
	private $tmp,$arqtxt,$arqxml,$arqpdf;
	public $id_empresa=0;
	
	function __construct()
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
			$this->tmp="\\inetpub\\wwwroot\\";
		else
			$this->tmp="/tmp/";
		$this->arqtxt=$this->tmp."nfe.txt";
		$this->arqxml=$this->tmp;
		$this->arqpdf=$this->tmp."nfe.pdf";

		// Valores default dos campos da NFe
		$this->c['modelo']='55';
		$this->c['serie']='001';
		if (gVar('nfe.ambiente')=="1")
			$this->c['versao']="1.4.2"; // Para produção, alterar para 1.4.2
		else
			$this->c['versao']="TESTE 1.4.2"; // Para produção, alterar para 1.4.2
		$this->c['municipio']=gVar('nfe.municipio');
		
		$this->c['propriaEnderecoIbgeEstado']=gVar('nfe.cUF');
		$this->c['propriaEnderecoIbgeMunicipio']=gVar('nfe.cUF');
		$this->c['ambiente']=gVar('nfe.ambiente')<>""?gVar('nfe.ambiente'):"2";// 1 producao, 2 homologacao
		$this->c['operacao']=gVar('nfe.operacao')<>""?gVar('nfe.operacao'):"Remessa";// venda de mercadoria, saída, etc.
		$this->c['formaPagamento']=0; // 0 = à vista, 1 = à prazo
		$this->c['tipoDocumento']=1; // 0 = entrada, 1 = saida
		$this->c['dataSaida']=date("Y-m-d");
		
		$this->c['propriaRazaoSocial']=gVar('nfe.razao_social');
		$this->c['propriaNome']=gVar('nfe.empresa');
		$this->c['propriaInscricaoEstadual']=gVar('nfe.insc_estadual');
		$this->c['propriaInscricaoMunicipal']=gVar('nfe.insc_municipal');
		$this->c['propriaCnpj']=gVar('nfe.cnpj');
		$this->c['propriaCnae']=gVar('nfe.cnae');
		$this->c['propriaUf']=gVar('nfe.UF');
		$this->c['propriaEndereco']=gVar('nfe.endereco');
		$this->c['propriaEnderecoNumero']=gVar('nfe.endereco_numero');
		$this->c['propriaEnderecoComplemento']=gVar('nfe.endereco_complemento');
		$this->c['propriaEnderecoBairro']=gVar('nfe.endereco_bairro');
		$this->c['propriaEnderecoIbgeMunicipio']=gVar('nfe.endereco_ibge_municipio');
		$this->c['propriaEnderecoMunicipio']=gVar('nfe.endereco_municipio');
		$this->c['propriaEnderecoUf']=gVar('nfe.endereco_uf');
		$this->c['propriaEnderecoCep']=gVar('nfe.endereco_cep');
		$this->c['propriaTelefone']=gVar('nfe.telefone');


		
		parent::__construct();
	}


	// Processa a NFe (gera XML)
	function processa()
	{
		$this->cria();
		$this->geraTxt();
		//echo $this->nfetxt;exit;
		$this->geraXml();
		$this->assina();
		$this->salva();
	}




	// Define valor para cada um dos campos da NFe
	function defineCabecalho($campos,$valor="")
	{
		if (is_array($campos))
		{
			foreach ($campos as $campo=>$valor)
				$this->c[$campo]=$valor;
		} else
		{
			$this->c[$campos]=$valor;
		}
			
		/* Campos necessários:
		 
		 Datas em formato de BD
		 
		 id						: Gerado automaticamente (id do BD)
		 numero					: Gerado automaticamente (id com zeros a esquerda)
		 dataEmissao			: Gerado automaticamente (hoje)
		 codigoAcesso			: Gerado automaticamente (numero)
		 digitoVerificador	: Gerado automaticamente (modulo 11 do codigoAcesso)
		 
		 dataSaida				: Vazio = hoje
		 		 
		 */
	}
	
	// Define valor para cada um dos itens da NFe
	function defineItens($campos)
	{
		$item="";
		foreach ($campos as $campo=>$valor)
			$item[$campo]=$valor;
		$this->i[]=$item;	
		/* Campos necessários:
		 
		 Datas em formato de BD
		 
		 id						: Gerado automaticamente (id do BD)
		 numero					: Gerado automaticamente (id com zeros a esquerda)
		 dataEmissao			: Gerado automaticamente (hoje)
		 codigoAcesso			: Gerado automaticamente (numero)
		 digitoVerificador	: Gerado automaticamente (modulo 11 do codigoAcesso)
		 
		 dataSaida				: Vazio = hoje
		 		 
		 */
	}
	
	
	// Gera TXT no formato NFePHP
	function geraTxt()
	{
		$sai=true;
		// Monta arquivo texto no layout do NFePHP

		$ano=substr($this->c['dataEmissao'],2,2);
		$mes=substr($this->c['dataEmissao'],5,2);
		$AAMM=$ano.$mes;

		// Tratamento de alguns campos antes de processar
		$this->c['propriaCnpj']=str_pad($this->tirapontos($this->c['propriaCnpj']),14, "0", STR_PAD_LEFT);
		$this->c['propriaInscricaoEstadual']=$this->tirapontos($this->c['propriaInscricaoEstadual']);
		$this->c['propriaInscricaoMunicipal']=strtoupper($this->tirapontos($this->c['propriaInscricaoMunicipal']));
		$this->c['propriaInscricaoMunicipal']=substr($this->c['propriaInscricaoMunicipal'],0,5)=="ISENT"?"":$this->c['propriaInscricaoMunicipal'];
		$this->c['cnpj']=str_pad($this->tirapontos($this->c['cnpj']),14, "0", STR_PAD_LEFT);
		$this->c['inscricaoEstadual']=$this->tirapontos($this->c['inscricaoEstadual']);
		$this->c['transportadoraCnpj']=str_pad($this->tirapontos($this->c['transportadoraCnpj']),14, "0", STR_PAD_LEFT);
		$this->c['transportadoraCpf']=$this->tirapontos($this->c['transportadoraCpf']);
		$this->c['transportadoraInscricaoEstadual']=$this->tirapontos($this->c['transportadoraInscricaoEstadual']);
		$this->c['propriaCnae']=$this->tirapontos($this->c['propriaCnae']);

		// Gerando código de acesso
		$idNfe=$this->c['propriaEnderecoIbgeEstado'].$AAMM.$this->c['propriaCnpj'].$this->c['modelo'].$this->c['serie'].str_pad($this->c['numero'],9, "0", STR_PAD_LEFT).$this->c['codigoAcesso'];
		$this->c['digitoVerificador']=$this->modulo_11($idNfe);
		$idNfe="NFe".$idNfe.$this->c['digitoVerificador'];
		$this->nfeid=$idNfe;
		// Atributos da NF-e
		$s ="A|1.10|$idNfe".NL;

		$m="";
		$m[]="B";
		$m[]=$this->c['propriaEnderecoIbgeEstado'];
		$m[]=$this->c['codigoAcesso'];
		$m[]=$this->c['operacao'];
		$m[]=$this->c['formaPagamento'];
		$m[]="55";
		$m[]="1";
		$m[]=$this->c['numero'];
		$m[]=substr($this->c['dataEmissao'],0,10);
		$m[]=substr($this->c['dataSaida'],0,10);
		$m[]=$this->c['tipoDocumento'];
		$m[]=$this->c['propriaEnderecoIbgeMunicipio'];
		$m[]="1";
		$m[]="1";
		$m[]=$this->c['digitoVerificador'];
		$m[]=$this->c['ambiente'];
		$m[]="1";
		$m[]="0";
		$m[]=$this->c['versao'];
		$s.=implode("|",$m).NL;
		
		// Emitente
		$m="";
		$m[]="C";
		$m[]=$this->tiraestranhos($this->c['propriaRazaoSocial']);
		$m[]=$this->tiraestranhos($this->c['propriaNome']);
		$m[]=$this->c['propriaInscricaoEstadual'];
		$m[]="";
		$m[]=$this->c['propriaInscricaoMunicipal'];
		$m[]=$this->c['propriaCnae'];
		$s.=implode("|",$m).NL;		
		
		$s.="C02|".$this->c['propriaCnpj'].NL;
		
		$m="";
		$m[]="C05";
		$m[]=$this->tiraestranhos($this->c['propriaEndereco']);
		$m[]=$this->c['propriaEnderecoNumero'];
		$m[]=$this->c['propriaEnderecoComplemento'];
		$m[]=$this->c['propriaEnderecoBairro'];
		$m[]=$this->c['propriaEnderecoIbgeMunicipio'];
		$m[]=$this->c['propriaEnderecoMunicipio'];
		$m[]=$this->c['propriaEnderecoUf'];
		$m[]=$this->c['propriaEnderecoCep'];
		$m[]='1058';
		$m[]='BRASIL';
		$m[]=str_replace(")","",str_replace("(","",str_replace(" ","",str_replace(".","",str_replace("-","",$this->c['propriaTelefone'])))));
		$s.=implode("|",$m).NL;		

		//Destinatario
		$s.="E|".$this->c['razaoSocial']."|".$this->c['inscricaoEstadual']."||".NL;
		$s.="E02|".$this->c['cnpj'].NL;
		$s.="E03||".NL;
		
		$m="";
		$m[]="E05";
		$m[]=$this->tiraestranhos($this->c['endereco']);
		$m[]=$this->c['enderecoNumero'];
		$m[]=$this->c['enderecoComplemento'];
		$m[]=$this->c['enderecoBairro'];
		$m[]=$this->c['enderecoIbgeMunicipio'];
		$m[]=$this->c['enderecoMunicipio'];
		$m[]=$this->c['enderecoUf'];
		$m[]=str_replace(".","",str_replace("-","",$this->c['enderecoCep']));
		$m[]='1058';
		$m[]='BRASIL';
		$m[]=str_replace(")","",str_replace("(","",str_replace(" ","",str_replace(".","",str_replace("-","",$this->c['telefone'])))));
		$s.=implode("|",$m).NL;
		
		// Itens da nota
		$qtd=$v_total=$total_itens=$t_icms=$t_ipi=$total_pb=$total_pl=0;
		foreach ($this->i as $item)
		{
			$qtd++;
			$s.="H|$qtd||".NL;
			$m="";
			$m[]="I";
			$m[]=$item['codigo'];
			$m[]="";
			$m[]=$this->tiraestranhos($item['descricao']);
			$m[]=gNcm($item['ncm']);
			$m[]="";
			$m[]="";
			$m[]=str_replace(".","",$this->c['cfop']);
			$m[]=$item['unidade'];
			$m[]=number_format($item['quantidade'],4,".","");
			$m[]=number_format($item['valor'],4,".",""); // valor unitario
			$m[]=number_format($item['quantidade']*$item['valor'],2,".",""); // total bruto
			$m[]="";
			$m[]=$item['unidade'];
			$m[]=number_format($item['valor'],4,".","");
			$s.=implode("|",$m).NL;			
			// Valor total do item
			$v_total=$item['quantidade']*$item['valor'];
			$total_itens+=$v_total;
			$total_volumes+=$item['quantidade'];
			$total_pl+=floatval($item['pesoLiquido']);
			$total_pb+=floatval($item['pesoBruto']);
			//echo $item['codigo']." pb:".$item['pesoBruto']." pl: ".$item['pesoLiquido']."<br>";
			// Impostos
			$s.="M".NL;
			// ICMS
			if ($item['tipo']=="importacao")
			{
				$s.="N".NL;
				$s.="N05|1|30|0|00|00|00|00|00".NL;

			} else
			{
				$s.="N".NL;
				$s.="N05|0|30|0|00|00|00|00|00".NL;
			}
			$icms=$v_total*($item['icms']/100);
			$t_icms+=$icms;
		}
		//echo "pb: $total_pb pl: $total_pl <br>";exit;
		// Totais

		$tt_itens=$total_itens+$t_ipi+$valor_frete;
		$s.="W".NL;
		$s.="W02|".number_format($total_itens,2,".","")."|".number_format($t_icms,2,".","")."|0.00|0.00|".number_format($total_itens,2,".","")."|".number_format($valor_frete,2,".","")."|0.00|0.00|0.00|".number_format($t_ipi,2,".","")."|".number_format($t_pis,2,".","")."|".number_format($t_cofins,2,".","")."|0.00|".number_format($tt_itens,2,".","").NL;

		// Transportadora
		if (($this->c['transportadoraCnpj']<>"") || ($this->c['transportadoraCpf']<>""))
		{
			$s.="X|".$this->c['modalidadeFrete'].NL;
			$razao=$this->tiraestranhos($this->c['transportadoraRazaoSocial']<>""?$this->c['transportadoraRazaoSocial']:$this->c['transportadoraNome']);
			$s.="X03|".$razao."|".$this->c['transportadoraInscricaoEstadual']."|".$this->c['transportadoraEndereco']."|".$this->c['transportadoraEnderecoUf']."|".$this->c['transportadoraEnderecoMunicipio'].NL;
			if($this->c['transportadoraCnpj']<>"")
				$s.="X04|".str_replace("-","",str_replace(".","",str_replace("/","",$this->c['transportadoraCnpj']))).NL;
			else
			$s.="X05|".$this->c['transportadoraCpf'].NL;
			if ($this->c['veiculoPlaca']<>"")
			{
				$placaUf=$this->c['veiculoPlacaUf']<>""?strtoupper($this->c['veiculoPlacaUf']):"BA";
				$s.="X18|".strtoupper($this->c['veiculoPlaca'])."|".$placaUf.NL;
			}
			if ($this->c['reboquePlaca']<>"")
			{
				$placaUf=$this->c['reboquePlacaUf']<>""?strtoupper($this->c['reboquePlacaUf']):"BA";
				$s.="X22|".strtoupper($this->c['reboquePlaca'])."|".$placaUf.NL;
			}
			$s.="X26|".intval($total_volumes)."||||".number_format($total_pl,3,".","")."|".number_format($total_pb,3,".","").NL;

		}
		if ($this->c['lacres']<>"")
		{
			$s.="X33|".$this->c['lacres'].NL;
		}
		if (($this->c['informacoesFisco']<>"") || ($this->c['informacoesContribuinte']<>""))
		{
			$s.="Z|".$this->c['informacoesFisco']."|".$this->c['informacoesContribuinte'].NL;
		}
		
		
		$s=$this->tiracentos($s);
		$this->nfetxt=$s;
		
		//echo "<pre>$s</pre>";exit;
		
		// Salva arquivo criado em disco
		$s=str_replace("\t","", $s);
		$arq=fopen($this->arqtxt,"w");
		fwrite($arq,$s);
		fclose($arq);
		return($s);
	}

	// Gera XML no formato SEFAZ
	function geraXml()
	{
		$sai=true;
		$arq = $this->nfetxt2xml($this->arqtxt);
		$this->arqxml = $this->tmp.$this->chave.'-nfe.xml';
		//echo "txt: ".$this->arqtxt."<br>";echo "xml: ".$this->arqxml."<br>";echo "arq: <br>$arq";
		if ( !file_put_contents($this->arqxml, $arq) )
		{
			$sai=false;
			$this->erros[]="Erro ao salvar arquivo XML";
		}
		$this->nfexml=$arq;
		//echo "<br><br>Ok ? $sai<br>";print_r($this->erros);
		return($sai);
	}
	
	// Assina XML
	function assina()
	{
		$sai=false;
		$nfe = new gNFeTools;
		//$nfefile = file_get_contents($this->arqxml);
		$nfefile=$this->nfexml;
		if ( $signn = $nfe->signXML($nfefile, 'infNFe') ) 
		{
			unlink($this->arqxml);
			if ( !file_put_contents($this->arqxml , $signn) ) 
			{
				$this->erros[]="Houve uma falha ao salvar a NFe assinada.";
			} else
			{
				$this->nfexml=$signn;
				$sai=true;
			}			
		} else 
		{
			$this->erros[]="Houve uma falha ao assinar a NFe.";
		}    
		return($sai);		
	}
	
	// Faz download do XML gerado
	function baixa($tipo="xml",$name = false, $type = false, $down = true)
	{
		if ($tipo=="xml")
			$file=$this->arqxml;
		else 
			$file=$this->arqtxt;
		if(!file_exists($file)) exit;
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($file));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		ob_clean();
		flush();
		readfile($file);
	}
	
	// Cria no BD
	function cria()
	{
		$hoje=gDBDateTime(date("d-m-y H:i:s"));
		$usrId=$_SESSION['usrId']>0?$_SESSION['usrId']:$_SESSION['usr_id'];
		$sql="select * from nfe_numeros where id_armazens=".$this->id_empresa;
		$rs=gQuery($sql);
		if ($rs->EOF)
		{
			$sql="insert into nfe_numeros (id_armazens,numero) values (".$this->id_empresa.",1)";
			gQuery($sql);
			$numero=1;
		} else
		{
			$numero=intval($rs->fields['numero'])+1;
			$sql="update nfe_numeros set numero=$numero where id=".$rs->fields['id'];
			gQuery($sql);
		}
		$sql="insert into nfe (id_empresa,id_pessoas,data,cancelada,enviada) values
				(".$this->id_empresa.",$usrId,'$hoje',0,0)";
		gQuery($sql);
		$sql="select id from nfe where id_pessoas=$usrId and data='$hoje' order by id desc";
		$rsc=gQuery($sql);
		$this->c['id']=intval($rsc->fields['id']);
		$this->c['numero']=$numero;
		$this->c['dataEmissao']=$hoje;
		$this->c['codigoAcesso']=substr($this->id_empresa.$usrId.date("HiYsdm"),8);
		$this->c['digitoVerificador']=$this->modulo_11($this->c['codigoAcesso']);
		$this->c['codigoAcesso']=$this->c['codigoAcesso'].$this->c['digitoVerificador'];
		$this->c['numeroCompleto']=$this->c['codigoAcesso'].$this->c['digitoVerificador'];
		return($this->c['id']);
	}
	
	// Salva no BD
	function salva()
	{
		$sql="update nfe set numero='".$this->c['numero']."',nfeid='".$this->chave."', txt='".$this->nfetxt."',xml='".$this->nfexml."' where id=".$this->c['id'];
		gQuery($sql);
	}
	
	// Carregar do BD
	function carrega($numero)
	{
		$sql="select * from nfe where numero='$numero'";
		$rs=gQuery($sql);
		return($rs);		
	}
	
	// Enviar para Sefaz
	function envia($numero)
	{
		
	}
	
	// Cancela NFe
	function cancela($numero)
	{
		
	}
	
	// Emite DANFE
	function danfe()
	{
		
	}
	
	function tiracentos($t)
	{
		global $gPathLib;
		if ($gPathLib<>"")
		{
			$pa=array("a","e","i","o","u");
			$de=array("á","é","í","ó","ú");
			$t=str_replace($de,$pa,$t);
			$de=array("à","è","ì","ò","ù");
			$t=str_replace($de,$pa,$t);
			$de=array("â","ê","î","ô","û");
			$t=str_replace($de,$pa,$t);
			$t=str_replace("ã","a",$t);
			$t=str_replace("õ","o",$t);
			$t=str_replace("ç","c",$t);
			$t=str_replace("Ç","C",$t);
			$t=autoencode($t);
			$pa=array("A","E","I","O","U");
			$de=array("Á","É","Í","Ó","Ú");
			$t=str_replace($de,$pa,$t);
			$de=array("À","È","Ì","Ò","Ù");
			$t=str_replace($de,$pa,$t);
			$de=array("Â","Ê","Î","Ô","Û");
			$t=str_replace($de,$pa,$t);
			$de=array("Ã","Õ","Ç");
			$pa=array("A","O","C");
			$t=str_replace($de,$pa,$t);
			$de=array("");
			$pa=array("E");
			$t=str_replace($de,$pa,$t);
		} else
		{
			$t=strtr($t,utf8_decode("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇ"),"aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcC");
			$t=strtr($t,"çêîòËéíñôåæëïóÌÍ","aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcC");
		}
/*
		$t=str_replace("É","E",$t);
		$t=str_replace("Õ","O",$t);
		//$t=utf8_decode($t);
	   //$t=strtr($t,utf8_decode("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇ"),"aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcC");
	    //$t=strtr($t,"çêîòËéíñôåæëïóÌÍ","aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcC");
		 //$t=strtr($t, "ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ", "SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
		 //$t=ereg_replace('[[:alpha]]'," ",$t);
*/
		 return $t;
	}

	function tiraestranhos($var)
	{
		return(preg_replace('/[^a-z0-9\+\-\=\.\,\!\?\:\;\@\%\&\(\)\{\}\<\>\[\]\s\'\$\/]+/i ','', $var));
	}

	function tirapontos($t_st)
	{
		return(str_replace('/','',str_replace(")","",str_replace("(","",str_replace(" ","",str_replace(".","",str_replace("-","",$t_st)))))));
	}
	
	function modulo_11($num, $base=9, $r=0) 
	{
		/**
		* Autor:
		* Pablo Costa <pablo@users.sourceforge.net>
		*
		* Função:
		* Calculo do Modulo 11 para geracao do digito verificador
		* de boletos bancarios conforme documentos obtidos
		* da Febraban - www.febraban.org.br
		*
		* Entrada:
		* $num: string numérica para a qual se deseja calcularo digito verificador;
		* $base: valor maximo de multiplicacao [2-$base]
		* $r: quando especificado um devolve somente o resto
		*
		* Saída:
		* Retorna o Digito verificador.
		*
		* Observações:
		* - Script desenvolvido sem nenhum reaproveitamento de código pré existente.
		* - Assume-se que a verificação do formato das variáveis de entrada é feita antes da execução deste script.
		*/
		
		$soma = 0;
		$fator = 2;
		
		/* Separacao dos numeros */
		for ($i = strlen($num); $i > 0; $i--) {
			// pega cada numero isoladamente
			$numeros[$i] = substr($num,$i-1,1);
			// Efetua multiplicacao do numero pelo falor
			$parcial[$i] = $numeros[$i] * $fator;
			// Soma dos digitos
			$soma += $parcial[$i];
			if ($fator == $base) {
			// restaura fator de multiplicacao para 2
				$fator = 1;
			}
			$fator++;
		}
		
		/* Calculo do modulo 11 */
		if ($r == 0) {
			$soma *= 10;
			$digito = $soma % 11;
			if ($digito == 10) {
				$digito = 0;
			}
			return $digito;
		} elseif ($r == 1){
			$resto = $soma % 11;
			return $resto;
		}
	}
	
}


// Pra garantir a formatação do NCM
function gNcm($ncm)
{
	$ncm=str_replace(".","",$ncm);
	$ncm=substr($ncm,0,4).".".substr($ncm,4,2).".".substr($ncm,6,2);
	return($ncm);
}
?>
