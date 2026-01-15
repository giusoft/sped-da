<?php
/** Classes para tratamento de Notas Fiscais Eletronicas (SEFAZ)
 * @author	Equipe GiuSoft: giuliano, milton, bruno, emerson e mateus
 * @version	1.0 07-10-2009 11:03
 */

define ("NL","\r\n");

/** Classe responsavel por gerar arquivo XML
 * @package	gNF
 * @author	Equipe GiuSoft: giuliano, milton, bruno, emerson e mateus
 * @version	1.0 07-10-2009 09:34
 */
class gNFe {
	private $log="";											// armazena mensagens de log para serem armazenadas em arquivo posteriormente
	private $xml="";											// armazena todo codigo XML gerado
	private $tags="";											// armazena tags XML geradas
	private $version="1.0";									// versao da nota que pode ser mudadada dinamicamente apos instanciacao da classe extedida
	private $caminhoArquivoXml="/tmp/nf.xml";			// caminho aonde sera gerado o XML
	private $caminhoArquivoLog="/tmp/nfe.log";		// caminho aonde sera gerado o log das acoes executadas
	private $tipoNf="";										// tipo da nota fiscal. Indica como sera o processamento de acordo com o tipo
	private $qtdeItem=0;										// quantidade de itens em cada nota.
	private $qtdeNf=0;										// quantidade total de notas do lote
	private $valorTotalItem=0;								// TODO: necessario?? para calculo de imposto em <total>
	private $valorTotalItemIss=0;							//
	private $valorTotalItemIpi=0;							//
	private $valorTotalItemPis=0;							//
	private $itemCofins=0;									//
	private $valorTotalItemIcms=0;						//
	private $valorNfTotalIpi=0;							// acumulador deste imposto em todos itens
	private $valorNfTotalIss=0;							// acumulador deste imposto em todos itens
	private $valorNfTotalPis=0;							// acumulador deste imposto em todos itens
	private $valorNfTotalCofins=0;						// acumulador deste imposto em todos itens
	private $valorNfTotalIcms=0;							// acumulador deste imposto em todos itens
	private $valorNfTotal=0;								// TODO: necessario??....ja que o valor pode ser calculado com base em outros totais dinamicamente
	private $valorNfTotalFrete=0;							// TODO: necessario??
	private $item="";											// matriz associativa com todos dados de um item
	private $nota="";											// matriz associativa com todos dados de cabecalho de uma nota

	//public $chaveModelo="M"; // TODO: Identificar valor padrao e colocar aqui
	//public $chaveSerie="UNI"; // TODO: Identificar valor padrao e colocar aqui

	/** Inicia automaticamente ao instanciar o objeto
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 */
	function __construct($tipo) {
		$this->xml="";
		$this->tags="";
		
		$lote_de_notasTESTE=$this->geraNotaFiscal($tipo); // TODO: teste
		echo $lote_de_notasTESTE; // teste
	}

	/** Monta a TAG de abertura ou seja, <tag>
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 * @param string $tag TAG propriamente dita
	 */
	private function abreTag($tag,$atributo="") {
		$tag=trim($tag);
		if($atributo<>'') {
			$atributo=" ".$atributo;
		}
		$this->xml.="<$tag$atributo>";
		if (count($this->tags)<3)
			$this->xml.=NL;
		$this->tags[]=$tag;
	}

	/** Monta a TAG de fechamento ou seja, </tag>
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 */
	private function fechaTag() {
		$tag=array_pop($this->tags);
		$this->xml.="</$tag>".NL;
	}

	/** Adiciona conteudo gerado entre TAG informada
	 * @author	giuliano
	 * @version	3.0 07-10-2009 09:47
	 * @param string $tag TAG propriamente dita
	 * @param string $conteudo Conteudo a ser abracado pela TAG
	 * @param int $tam Controla o tamanho da string
	 */
	private function tag($tag,$conteudo,$tamMax=0) {

		$tag=trim($tag);
		if ($conteudo<>"") { // Cria a tag apenas se houver algum conteudo a ser acrescentado
			$this->abreTag($tag);

			$tamCampo=strlen($conteudo);

			if(($tamMax>0 || $tamMax<>"") && ($tamCampo>$tamMax)) { // Verifica se o tamanho da string possue restriÃ§Ã£o de tamanho e faz ajuste
				$this->xml.=substr($conteudo,0,$tamMax-1);
				$this->adicionaLog('aviso',"o conteudo da tag <$tag> foi reduzida de $tamCampo para $tam");
			}
			else {
				$this->xml.=$conteudo;
			}
			if (count($this->tags)<3)
				$this->xml.=NL;
			$this->fechaTag();
		}
	}

	/** Gera lote
	 * @author	giuliano
	 * @version	1.0 28-10-2009 09:47
	 * @return
	 */
	private function geraLote() {
		return $this->obtemLote();
	}

	private function adicionaRetirada() {

		$this->adicionaEntidade("dest",$this->obtemDestinatario());
	}

	/** Adiciona mensagem no log
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @param string $tipo tipo da mensagem de log
	 *	@param string $msg mensagem do log (descriï¿½ao)
	 */
	public function adicionaLog($tipo,$msg) {
		$this->log=$tipo.':'.$msg.NL;
	}

	/** Obtem as mensagens do log
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @param string $tipo tipo da mensagem de log
	 *	@param string $msg mensagem do log (descriï¿½ao)
	 */
	private function obtemLog() {
		return $this->log;
	}

	/** Gera o arquivo log
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	private function geraLog() {
		$this->salva($this->obtemCaminhoArquivoLog(),$this->obtemLog());
	}

	/** Obtem caminho padrao para salvar o arquivo contendo o Xml gerado
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	private function obtemCaminhoArquivoXml() {
		return $this->caminhoArquivoXml;
	}

	/** Obtem caminho padrao para salvar o arquivo contendo o log gerado
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	private function obtemCaminhoArquivoLog() {
		return $this->caminhoArquivoLog;
	}

	/** Obtem versao do xml
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	private function obtemVersion() {
		return $this->version;
	}

	/** Adiciona versao do xml, cosu seja necessario munda-la na classe extendida,sem necessidade de modificar a classe pai
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	public function adicionaVersion($version) {
		$this->version=$version;
	}

	/** Obtem conteudo do xml
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	private function obtemXml() {
		return $this->xml;
	}

	/** Obtem quantidade de itens de cada nota
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	private function obtemItemQtde() {
		return $this->qtdeItem;
	}

	/** Obtem quantidade de notas inclusas no arquivo xml gerado
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	private function obtemNfQtde() {
		return $this->qtdeNf;
	}

	/** Obtem valor total dos itens
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	private function obtemItemTotal() {
		return $this->valorTotalItem;
	}

	/** Adiciona cabecalho do XML
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 * @param string $idLote Numero do lote de Notas Fiscais
	 */
	private function adicionaXMLCabecalho($idLote) {
		$this->xml.='<?xml version="'.$this->obtemVersion().'" encoding="UTF-8"?>'.NL;
		$this->xml.='<enviNFe xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.01">'.NL;
		$this->tag("idLote",$idLote);
	}

	/** Adiciona identificacao da nota
	 * @author	Emerson Santana
	 * @version	1.0 29-10-2009 09:47
	 */

	private function adicionaIdentificacao() {
	//$this->obtemIdentificacao();
		$mtz=&$this->nota;
		$this->abreTag("ide");
		$this->tag("cUF",$mtz['cUF']);
		$this->tag("cNF",$mtz['chave']);                // Chave de acesso a NF-e
		$this->tag("natOp",$mtz['operacao']);    	// Descricao da natureza daoperacao (CFOP)
		$this->tag("indPag","2"); 			// Indicador de pagamento 2 => outros
		$this->tag("mod","55");     			// 55 => codigo que indica emissï¿½o de NF-e
		$this->tag("serie","0"); 			// 0 => para serie unica
		$this->tag("nNF",$mtz['nf']);                   // ID da NF com 09 caracteres
		$this->tag("dEmi",date("Y-m-d"));		// Data da Saida
		$this->tag("dSaiEnt",date("Y-m-d"));     	// Data da entrada
		$this->tag("tpNF",$mtz['ent_sai']); 		// 0 => Entrada ; 1 => Saida
		$this->tag("cMunFG",$mtz['codigo_municipio']); 	// Codigo do municipio
		$this->tag("tpImp",$mtz['impressao']);    	// Tipo de impressao 1=>retrato ; 2=> paisagem
		$this->tag("tpEmis","1");			// Tipo de emissao 1=> normal ; 2=>contigencia
		$this->tag("cDV",$this->geraDigitoVerificador($this->geraChave()));   	// digito verificador de acesso a NF-e


		// TODO: Eh preciso trocar o codigo to tipo de ambiente

		$this->tag("tpAmb","2");  			// 1 => Producao ; 2 => homologacao
		$this->tag("finNFe","1"); 			// 1 => Normal ; 2 => complementar ; 3 =>Ajuste de NF-e
		$this->tag("procEmi","3");  			// Padrao 3 => Eplicativo proprio paraemissao de NF-e
		$this->tag("verProc","1.1");  			// Versao  do aplicativo  emissor de NF-e
		$this->fechaTag();
	}

	/** Gera chave. Metodo que auxilia o metodo gera o digito verificador
	 * @author	mateus
	 * @version	1.0 29-10-2009 09:47
	 * @return string $chaveComDigitoVerificador
	 */
	private function geraChave() {
		$mtz=$this->obtemChave(); // verificar como obter os dados inves de utilizar este metodo. Transformar a $mtz em atributos?
		$idNf=$mtz['idNf'];
		$modelo=55; // cod. que indica que eh uma NFe
		$serie="000"; // TODO: buscar do BD...??
		$cnpjEmitente=str_pad(str_replace("/","",str_replace("-","",str_replace(".","",$mtz['cnpj']))),14,0,STR_PAD_LEFT);
		$codigoIbgeUf="29"/*$rs->fields['cUF']*/; // TODO: buscar do BD
		$numeroNf=str_pad($idNf,9,0,STR_PAD_LEFT); // usa o ID da nota
		$anoMesEmissao=date("Ym");

		$chaveSemDigitoVerificador=$codigoIbgeUf.$anoMesEmissao.$cnpjEmitente.$modelo.$serie.$numeroNf.$idNf;
		$chaveComDigitoVerificador=$chaveSemDigitoVerificador.$this->geraDigitoVerificador($chaveSemDigitoVerificador);

		$this->chave=$chaveComDigitoVerificador;
		return $chaveComDigitoVerificador;
	}

	/** Adiciona rodape do XML
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 */
	private function adicionaXMLRodape() {
		$this->xml.='</enviNFe>'.NL;
	}

	/** Adiciona cabecalho da Nota Fiscal Eletronica ao XML
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 * @param string $infNFE Id da NFe
	 */
	private function adicionaNfCabecalho() {
		$this->xml.='<NFe xmlsns="http://www.portalfiscal.inf.br/nfe">'.NL;
		$this->xml.='<infNFe Id="'.$this->adicionaAssinaturaContribuinte().'" versao="1.01">'.NL;
	}

	/** Adiciona rodape da Nota Fiscal Eletronica ao XML
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 * @param string $json JSON com parametros
	 * @return mixed $sai
	 */
	private function adicionaNfRodape() {
		$this->xml.='</infNFe>'.NL;
		$this->adicionaAssinatura();
		$this->xml.='</NFe>'.NL;
	}
	
	private function adicionaAssinaturaContribuinte() {
		$assinaturaContr="NFe".$this->nota['id'];
		return $assinaturaContr;
	}

	/** Adiciona uma nota fiscal
	 * @author	mateus
	 * @version	1.0 29-10-2009 09:47
	 * @return string
	 */
	private function adicionaNotas() {
	//$nItem=$this->obtemNotaQtde(); // obtem qtde atual da contagem de itens, que eigual a ordem do item atual
		$this->obtemNotas();
		while($this->adicionaNota()) {} // Adiciona item a item
	}

	/** Adiciona uma nota fiscal
	 * @author	mateus
	 * @version	1.0 29-10-2009 09:47
	 * @return string
	 */
	private function adicionaNota() {
		$this->nota=$this->obtemNota();
		$mtz=&$this->nota;
		if (is_array($mtz)) {
			$this->qtdeNf++;
			$this->adicionaNfCabecalho(); //</NFe>
			$this->adicionaEmitente();
			$this->adicionaDestinatario();
			//$this->adicionaNotaFiscalInformacao($mtz['id']); // TODO: reativar mais tarde?
			$this->adicionaIdentificacao();
			//echo "nota".$mtz['id']."<br>";
			$this->adicionaItens();
			$this->adicionaNfRodape(); //</NFe>
			$sai=true;
		} else {
			$sai=false;
		}
		return($sai);
	}

	/** Adiciona endereco ao XML
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 * @param string $json JSON com parametros
	 * @return mixed $sai
	 */
	private function adicionaEndereco() {
	// Os nomes dos campos seguem o padrao da GiuSoft
		$this->tag("xLgr",$mtz['endereco']);
		$this->tag("nro",$mtz['numero']);
		$this->tag("xCpl",$mtz['complemento']);
		$this->tag("xBairro",$mtz['bairro']);
		$this->tag("cMun",$mtz['cod_municipio']);
		$this->tag("xMun",$mtz['municipio']);
		$this->tag("UF",$mtz['uf']);
	}

	/** Adiciona entidade ao XML, podendo ser "emit", "dest"
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 * @param string $tipo Tipo da Tag XML
	 * @param string $mtz Array contendo campos
	 * @return mixed $sai
	 */
	private function adicionaEntidade($tipo) {
		// busca o tipo de entidade 
		switch($tipo) {
			case 'emit': // emitente
				$mtz=$this->obtemEmitente();
				break;
			case 'dest': // detinatario
				$mtz=$this->obtemDestinatario();
				break;
		}

	// Os nomes dos campos seguem o padrao da GiuSoft
		$this->abreTag($tipo);
		$this->tag("CNPJ",$mtz['cnpj']);
		$this->tag("xNome",$mtz['razao_social']);
		$this->tag("xFant",$mtz['nome']);
		// TODO: Identificar o porque da TAG abaixo
		$this->tag("xEnder".ucfirst($tipo),$mtz['endereco']);
		$this->adicionaEndereco(&$mtz);
		// TODO: Verificar se o CEP estah ou nao incluido no endereco
                /*
                 * O CEP estah na tabela GERAL_PESSOAS_ENDERECOS
                 * serah preciso fazer um JOIN na tabela GERAL_PESSOAS_ENEDERECOS
                 */
		$this->tag("CEP",$mtz['cep']);
		$this->tag("cPais","1058");                 // valor padrao para o codigo do pais
		$this->tag("xPais","Brasil");               // Nome do Pais padrao
		$this->tag("fone",$mtz['telefone']);
		$this->tag("IE",$mtz['insc_estadual']);
		$this->fechaTag();
	}

	/** Est e mï¿½todo serah obrigatoriamente estendida no uso em producao
	 * Pois buscarah no banco de dados os campos desejados. A matriz contida aqui ï¿½ apenas um TESTE
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 * @return string $mtz Array contendo campos
	 */
	 
	 /*
	private function obtemEntidade() {
		$mtz="";
		$mtz["cnpj"]="01234567";
		$mtz["razao_social"]="Empresa Teste Ltda.";
		return($mtz);
	}
	*/

	private function adicionaEmitente() {
		$this->adicionaEntidade('emit');
	}

	/** Adiciona entidade do tipo destinatario
	 * @author	mateus
	 * @version	1.0 10-11-2009
	 */
	private function adicionaDestinatario() {
		$this->adicionaEntidade("dest");
	}

	private function adicionaDestinatarioEntrega($json) {
		$mtz=cssDecode($json);
		$this->abreTag("entrega");
		$this->adicionaEndereco($mtz);
		$this->fechaTag();
	}

	private function adicionaDestinatarioRetirada($json) {
		$mtz=cssDecode($json);
		$this->abreTag("retirada");
		$this->adicionaEndereco($mtz);
		$this->fechaTag();
	}

	/** Adiciona um item ao grupo de itens
	 * @author	mateus
	 * @version	1.0 13-10-2009 09:47
	 * @return string
	 */
	private function adicionaItem() {
	//$mtz=$this->obtemItem();
		$this->item=$this->obtemItem();
		$mtz=&$this->item;
		//print_r($mtz)."<br>";
		
		if (is_array($mtz)) {
			$this->qtdeItem++;
			$nItem=$this->obtemItemQtde(); // obtem qtde atual da contagem de itens, que eigual a ordem do item atual
			$this->abreTag("det","nItem='".$mtz['itemOrdem']."'");
			$this->abreTag("prod");										 // produto e servico
			$this->tag("cProd",$mtz['cProd']);                  // Codigo do produto ou servico
			$this->tag("cEAN",$mtz['cEAN']);                    // GTIN (Global Trade Item Number) do produto, antigo cï¿½digo EAN ou cï¿½digo de barras
			$this->tag("xProd",$mtz['xProd']);						 // Descricao do produto ou servico
			$this->tag("NCM",$mtz['NCM']);                      // Codigo NCM
			$this->tag("EXTIPI",$mtz['EXTIPI']);                // EXTIPI
			$this->tag("genero",$mtz['genero']);                // Genero do Produto ou Servico
			$this->tag("CFOP",$mtz['CFOP']);                    // Codigo Fiscal de Operacoes e Prestaï¿½ï¿½es
			$this->tag("uCom",$mtz['uCom']);                    // Unidade comercial
			$this->tag("qCom",$mtz['qCom']);                    // Quantidade comercial
			$this->tag("vUnCom",$mtz['vUnCom']);                // Valor unitario de comercializaï¿½ao
			$this->tag("vProd",$mtz['vProd']);                  // Valor Total Bruto dos Produtos ou Serviï¿½os
			$this->tag("cEANTrib",$mtz['cEANTrib']);            // GTIN (Global Trade Item Number) da unidade tributï¿½vel, antigo cï¿½digo EAN ou cï¿½digo de barras
			$this->tag("uTrib",$mtz['uTrib']);                  // Unidade Tributï¿½vel
			$this->tag("qTrib",$mtz['qTrib']);                  // Unidade Tributavel
			$this->tag("vUnTrib",$mtz['vUnTrib']);              // Valor Unitario de tributacao
			$this->tag("vFrete",$mtz['vFrete']);                // Valor total do frete
			$this->tag("vSeg",$mtz['vSeg']);                    // Valor total do seguro
			$this->tag("vDesc",$mtz['vDesc']);                  // Valor do desconto
			$this->valorNfTotal += ($mtz['qCom'] * $mtz['vUnCom'] );

			// TODO calculo de impostos e totais para serem usados no mï¿½todo totais
			$this->adicionaItemImposto();
			$this->fechaTag();
			$this->fechaTag();
			$sai=true;
		} else {
			$sai=false;
		}
		return($sai);


	}

	/** Adiciona os itens a nf
	 * @author	mateus
	 * @version	1.0 13-10-2009 09:47
	 * @return string
	 */
	private function adicionaItens() {
		$mtz=&$this->nota;
		$idNf=$mtz['id'];
		$this->obtemItens($idNf);
		$this->qtdeItem=0; // zera para cada nota
		while($this->adicionaItem()) {/*echo "ITEM da nota $idNf<br>";*/} // Adiciona item a item
	}

	/** Adiciona DI (declaracao de importacao)
	 * @author	mateus
	 * @version	1.0 03-11-2009
	 * @return string
	 */
	private function adicionaItemDi($mtz) {
		$this->abreTag("DI");
		$this->tag("nDI",$mtz["nDI"]);
		$this->tag("xLocDesemb",$mtz["xLocDesemb"]);
		$this->tag("UFDesemb",$mtz["UFDesemb"]);
		$this->tag("cDesemb",$mtz["cDesemb"]);
		$this->tag("cExportador",$mtz["cExportador"]);
		$this->fechaTag();
	}

	/** Adiciona ADI (adiï¿½oes)
	 * @author	mateus
	 * @version	1.0 03-11-2009
	 * @return string
	 */
	private function adicionaItemAdi($mtz) {
		$this->abreTag("adi");
		$this->tag("nAdicao",$mtz["nAdicao"]);
		$this->tag("nSeqAdic",$mtz["nSeqAdic"]);
		$this->tag("cFabricante",$mtz["cFabricante"]);
		$this->tag("vDescDI",$mtz["vDescDI"]);
		$this->fechaTag();
	}

	/** Adiciona total
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return string
	 */
	private function adicionaNfTotal() {
		$this->abreTag("total"); // W01 Ele. G
		$this->adicionaNfTotalIcms(); // ICMSTot
		$this->adicionaNfTotalIssQn(); // ISSQNTot opcional
		$this->adicionaNfTotalRetencaoTrib(); // retTrib opcional
		$this->fechaTag();
	}

	/** Adiciona imposto ao item
	 * @author	mateus
	 * @version	1.0 21-10-2009
	 * @return string
	 */
	private function adicionaItemImposto() {
		$this->abreTag("imposto");
		$this->adicionaItemIcms(/*$mtz*/);
		$this->adicionaItemPis(/*$mtz*/);
		$this->adicionaItemCofins();
		if(($this->tipoNf=="servico") || ($this->tipoNf=="manufatura")) {
			$this->adicionaItemIpi(/*$mtz*/);
		}
		$this->adicionaItemIpiTrib();
		if($this->tipoNf=="servico") {
			$this->adicionaItemIss(/*$mtz*/);
		}
		$this->fechaTag();
	}

	/** Adiciona ICMS do item
	 * Esse metodo eh obrigatorio independenete do cliente utilizar
	 * o segmento de venda ou serviço
	 * @author	Emerson Santana
	 * @version	1.0 28-10-2009 17:06
	 * @return string
	 */

	private function adicionaItemIcms() {
		$mtz=&$this->item;
		$this->abreTag("ICMS");
		$this->abreTag("ICMS40");
		$this->tag("orig",$mtz["origem"]);
		$this->tag("CST",$mtz["cst"]);
		$this->tag("vBCST",$mtz["valor_base"]);
		$this->tag("vICMSST",$mtz["valor_icms_st"]);
		$this->fechaTag();
		$this->fechaTag();
	}
	/** Adiciona Ipi do item  (CFOP 5933 e 6933 incide PIS)
	 * @author	Emerson Santana
	 * @version	1.0 28-10-2009 17:06
	 * @return string
	 */

	private function adicionaItemPis() {

		if($this->valorTotalItemPis > 0) {
			$this->abreTag("PIS");
			$this->abreTag("PISAliq");
			$this->tag("CST",$mtz["cst"]);
			$this->tag("vBC",$mtz["valor_base"]);
			$this->tag("pPIS",$mtz["percentual"]);
			$this->tag("vPIS",$mtz["valor"]);
			$this->fechaTag();
			$this->fechaTag();
		}

	}

	/** Adiciona Ipi do item
	 * @author	mateus
	 * @version	1.0 28-10-2009 09:47
	 * @return string
	 */
	private function adicionaItemIpi() {
	//$this->abreTag("IPI");
	//$this->tag("","");
	//$this->fechaTag();/*
	}

	/** Adiciona Ipi trib do item
	 * @author	mateus
	 * @version	1.0 28-10-2009 09:47
	 * @return string
	 */
	private function adicionaItemIpiTrib() {
	//$this->abreTag("IPI");
	//$this->tag("","");
	//$this->fechaTag();/*
	}

	/** Adiciona Iss do item
	 * @author	Emerson Santana
	 * @version	1.0 28-10-2009 09:47
	 * @return string
	 */
	private function adicionaItemIss() {
		$mtz=&$this->item; // item
		$mtz_nota=&$this->nota; // nota
		if ($mtz['iss'] > 0) {
			$this->abreTag("ISSQN");
			$this->tag("vbc",$mtz['valor_unitario']); // vbc=base de calculo, valor do item
			$this->tag("valiq",$mtz['iss']);
			$this->tag("cMunFG",$mtz_nota["cMunFG"]); // conforme tabela do IBGE
			$this->tag("clistServ",$mtz["codigo_servico"]); // conforme tabela de codigo de servicos
			$this->fechaTag();

			$this->valorNfTotalIss+=($mtz['valor_unitario']*$mtz['quantidade']/$mtz['iss']);
		}
	}

	/** Adiciona Cofins do item (CFOP 5933 e 6933 incide COFINS)
	 * @author	Emerson Santana
	 * @version	1.0 28-10-2009 17:15
	 * @return string
	 */
	private function adicionaItemCofins() {
		$mtz=&$this->item;
		if($this->itemConfins > 0) {
			$this->abreTag("COFINS");
			$this->abreTag("COFINSAliq");
			$this->tag("CST",$mtz["cst"]); // TODO: pesquisar como obter esta informacao
			$this->tag("vBC",$this->valorNfTotalCofins);
			$this->tag("pCOFINS",$this->itemCofins);
			//$valor = ($this->valorNfTotalCofins * $this->itemCofins) / 100;
			$valor = ($this->valorNfTotalCofins * $mtz['cofins']) / 100;
			$this->tag("vCOFINS",$valor);
			$this->fechaTag();
			$this->fechaTag();

		}
	}

	/** Adiciona Icms total
	 * @author	mateus
	 * @version	1.0 21-10-2009
	 * @return string
	 */
	private function adicionaNfTotalIcms($mtz) {
		$this->abreTag("ICMSTot");
		// TODO: ver qual e o tipo de Nfe (Serviï¿½o ou venda) em caso de venda vBc = 0.0
		$this->tag("vBC",$this->valorNfTotalIcms); // Base de calculo do Icms
		$this->tag("vICMS",$this->valorNfTotalIcms); // Valor total do Icms
		//TODO: colocar o valor total da NFe
		$this->tag("vBCST",$mtz['vBCST']); // Base de cï¿½lculo do Icms ST
		$this->tag("vST",$mtz['vST']); // Valor total do ICMS ST
		//TODO: colocar o valor total dos itens da NF-e
		$this->tag("vProd",$mtz['vProd']); // Valor total dos itens
		$this->tag("vFrete",$mtz['vFrete']); // Valor total do frete
		$this->tag("vSeg",$mtz['vSeg']); // Valor total do seguro
		// TDDO: colocar o valor total dos descontos
		$this->tag("vDesc",$mtz['vDesc']); // Valor total do desconto
		//TODO: colocad o valor total do Impostos Importacao
		$this->tag("vII",$mtz['vII']); // Valor total do II
		$this->tag("vIPI",$this->valorNfTotalIpi); // Valor total do Ipi
		$this->tag("vPIS",$this->valorNfTotalPis); // Valor total do Pis
		$this->tag("vCOFINS",$this->valorNfTotalCofins); // Valor total do Cofins
		//TODO: colocar valor referente a outros impostos, padrao R$ 0.0
		$this->tag("vOutro","0.00"); // Valor total de Outras despesas acessorias
		$this->tag("vNF",$this->obtemNfTotal()); // Valor total da nota fiscal
		$this->fechaTag();
	}

	/** Adiciona ISSQN total. Opcional
	 * @author	mateus
	 * @version	1.0 21-10-2009
	 * @return string
	 */
	private function adicionaNfTotalIssQn($mtz) {

		if ($this->valorTotalItemIss > 0) {
			$this->abreTag("ISSQNtot");
			$this->tag("vServ",$this->valorNfTotalIss); // Valor total dos servicos sob nï¿½o-incidï¿½ncia ou nï¿½o tributados pelo Icms
			$this->tag("vBC",$this->valorNfTotalIss); // Base de calculo do Iss
			$this->tag("vISS",$this->valorNfTotalIss); // Valor total do Iss
			$this->tag("vPis",$this->valorNfTotalPis); // Valor do Pis sobre os serviï¿½os
			$this->tag("vCOFINS",$this->valorNfTotalCofins); // Valor do Cofins sobre serviï¿½os
			$this->fechaTag();
		}

	}

	/** Adiciona grupo de retenï¿½ï¿½o de tributos. Opcional
	 * @author	mateus
	 * @version	1.0 21-10-2009
	 * @return string
	 */
	private function adicionaNfTotalRetencaoTrib($mtz) {

		$this->abreTag("retTrib");
		$this->tag("vRetCofins",$mtz['vRetCofins']); //
		$this->tag("vRetPIS",$mtz['vRetPIS']); //
		$this->tag("vRetCSLL",$mtz['vRtCSLL']); //
		$this->tag("vBCIRRF",$mtz['vBCIRRF']); //
		$this->tag("vIRRF",$mtz['vIRRF']); //
		$this->tag("vBCRetPrev",$mtz['vBCRetprev']); //
		$this->fechaTag();
	}

	/** Obtem total
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	private function obtemNfTotal() {
		return $this->valorNfTotal;
	}

	/** Obtem total Ipi
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	private function obtemNfTotalIpi() {
		return $this->valorNfTotalIpi;
	}

	/** Obtem total Ipi
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	private function obtemNfTotalFrete() {
		return $this->valorNfTotalFrete;
	}

	/** Obtem total Icms
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	private function obtemNfTotalIcms() {
		return $this->valorNfTotalIcms;
	}

	/** Obtem total Pis
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	private function obtemNfTotalPis() {
		return $this->valorNfTotalPis;
	}

	/** Obtem total Iss
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	private function obtemNfTotalIss() {
		return $this->valorNfTotalIss;
	}

	/** Obtem total Cofins da nf
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	private function obtemNfTotalCofins() {
		return $this->valorNfTotalCofins;
	}

	/** Obtem Transporte
	 * @author	mateus
	 * @version	1.0 22-10-2009 09:47
	 * @return float
	 */
	private function adicionaTransporte($json) {
		$mtz=cssDecode($json);
		$this->abreTag("transp");
		$this->tags("modFrete",$mtz['modFrete']);
		$this->adicionaEndereco($mtz);
		$this->fechaTag();
	}

	/** Obtem Transporte - Retenï¿½ï¿½o de ICMS
	 * @author	mateus
	 * @version	1.0 22-10-2009 09:47
	 * @return float
	 */
	private function adicionaTransporteRetICMS($json) {
		$mtz=cssDecode($json);
		$this->abreTag("retTransp");
		$this->tags("vServ",$mtz['vServ']);
		$this->tags("vBCRet",$mtz['vBCRet']);
		$this->tags("pICMSRet",$mtz['pICMSRet']);
		$this->tags("vICMSRet",$mtz['vICMSRet']);
		$this->tags("CFOP",$mtz['CFOP']);
		$this->tags("gMunFG",$mtz['gMunFG']);
		$this->fechaTag();
	}

	/** Obtem Transportador. Opcional
	 * @author	mateus
	 * @version	1.0 22-10-2009 09:47
	 * @return float
	 */
	private function adicionaTransportador($json) { // opcional
		$mtz=cssDecode($json);
		$this->abreTag("transporta");
		$this->tags("CNPJ","");
		// TODO implementar restante dos campos. Opcionais
		$this->adicionaEndereco($mtz);
		$this->fechaTag();
	}
	/** Adiciona dados da cobranca e fatura
	 * @author	Emerson Santana
	 * @version	1.0 28-10-2009 09:47
	 * @return string[] ou recordset com alias dos campos
	 */

	private function adicionaCobranca($mtz) {

		$this->abreTag("cobr");
		$this->abreTag("fat");
		$this->tags("nFat",$mtz['numero_fatura']);
		$this->tags("vOrig",$mtz['valor']);
		$this->tags("vDesc",$mtz['desconto']);
		$this->tags("vLiq",$mtz['valor_liquido']);
		$this->fechaTag();
		$this->abreTag("dup");
		$this->tags("nDup",$mtz['numero_duplicata']);
		$this->tags("dVenc",$mtz['vencimento']);
		$this->tags("vDup",$mtz['valor_duplicata']);
		$this->fechaTag();
		$this->fechaTag();

	}

	/** Obtem informacoes sobre a cobranca e fatura da venda ou serviï¿½o
	 * @author	Emerson Santana
	 * @version	1.0 27-10-2009 15:33
	 */

	private function obtemCobranca() {
		$this->adicionaEntidade("cobr",$this->obtemCobranca());

	}


	/** Informacoes adicionais. Opcional
	 * @author	Emerson Santana
	 * @version	1.0 22-10-2009 09:47
	 */
	private function adicionaInformacoesAdicionais($json) { // opcional!
	// Web Services - Informacoes Adicionais (Z)
		$mtz=cssDecode($json);
		$this->abreTag("infAdic");
		$this->tag("infAdFisco",$mtz['infAdFisco']);
		$this->tag("infCpl",$mtz['infCpl']);
		$this->fechaTag();

		$this->abreTag("obsCont");
		$this->tag("xCampo",$mtz['xCampo']);
		$this->tag("xTexto",$mtz['xTexto']);
		$this->fechaTag();

		$this->abreTag("obsFisco");
		$this->tag("xCampo",$mtz['xCampo']);
		$this->tag("xTexto",$mtz['xTexto']);
		$this->fechaTag();

		$this->abreTag("procRef");
		$this->tag("nProc",$mtz['nProc']);
		$this->tag("indProc",$mtz['indProc']);
		$this->fechaTag();

		// Web Services - Informaï¿½ï¿½es de Comercio Exterior (ZA)
		$this->abreTag("exporta");
		$this->tag("UFEmbarq",$mtz['UFEmbarq']);
		$this->tag("xLocEmbarq",$mtz['xLocEmbarq']);
		$this->fechaTag();
		// Web Services - Informaï¿½ï¿½es de Compras (ZB)
		$this->abreTag("compra");
		$this->tag("xNEmp",$mtz['xNEmp']);
		$this->tag("xPed",$mtz['xPed']);
		$this->tag("xCont",$mtz['xCont']);
		$this->fechaTag();
	}

	private function obtemImposto($imposto) {
		return(1);
	}

	/** Gera digito verificador da chave de conectividades do numero da nota fiscal
	 * @author    Emerson Santana
	 * @version    1.0 27-10-2009 09:47
	 * @param string $chave com 44 sendo eles:
	 * chave: chave gerada previamente
	 * TODO: ver com  Giuliano se eh melhor recordset ou Json
	 */
	private function geraDigitoVerificador($chave) {
		$peso[] = 2;
		$peso[] = 3;
		$peso[] = 4;
		$peso[] = 5;
		$peso[] = 6;
		$peso[] = 7;
		$peso[] = 8;
		$peso[] = 9;

		$incrementa_chave = 0;
		$total = 0;
		$validador = -1;
		for($i = strlen($chave) - 1 ; $i > -1  ; $i--) {

			if($incrementa_chave == 8) {

				$incrementa_chave = 0;
			}
			$valor = substr($chave,$i,1);
			$total += ($peso[$incrementa_chave] * $valor);
			$incrementa_chave++;
		}
		$div = intval($total / 11);
		$validador = 11 - ($total - ($div * 11));

		if($validador == 0 || $validador == 1) {

			$validador = 0;
		}
		return $validador;
	}

	/** Obtem dados do local da retirada da mercadoria
	 * @author    Emerson Santana
	 * @version    1.0 14-10-2009 09:47
	 */

	private function obtemRetirada() {

		$this->adicionaEntidade("retirada",$this->obtemRetirada());


	}
	/** Adiciona carretas e reboques que poderao ser atribuidos ao cavalo
	 * @author    Emerson Santana
	 * @version    1.0 14-10-2009 09:47
	 * @param string[] $mtz ou record set dos dados do reboque ou carretas
	 */

	public function adicionaReboque($mtz) {
		$this->abreTag("reboque");
		$this->tag("placa",$mtz['placa']);
		$this->tag("uf",$mtz['uf']);
		//$this->tag("rntrc",$mtz['rntrc']);  // esse campo nao e obrigatorio
		$this->fechaTag();
	}
	/** Obtem dados do reboque ou carreta
	 * @author    Emerson Santana
	 * @version    1.0 14-10-2009 09:47
	 */

	private function obtemReboque() {
		$this->adicionaEntidade("reboque",$this->obtemReboque());
	}
	/** Adiciona a quantidade de volums peso liquido e bruto da NF-e
	 * @author    Emerson Santana
	 * @version    1.0 14-10-2009 09:47
	 * @param string[] $mtz ou recordset com dados dos volumes transportados
	 */

	private function adicionaVolume($mtz) {
		$this->abreTag("vol");
		$this->tag("qVol",$mtz['quantidade']);
		$this->tag("esp",$mtz['mercadoria']);
		$this->tag("marca",$mtz['marca']);  // esse campo nao e obrigatorio
		$this->tag("nVol",$mtz['volume']);  // esse campo nao e obrigatorio
		$this->tag("pesoL",$mtz['peso_liquido']);  // esse campo nao e obrigatorio
		$this->tag("pesoB",$mtz['peso_bruto']);  // esse campo nao e obrigatorio
		$this->fechaTag();

	}

	/** Obtem dados dos volumes dos itens a serem transportados
	 * @author    Emerson Santana
	 * @version    1.0 14-10-2009 09:47
	 */

	private function obtemVolume() {
		$this->adicionaEntidade("vol",$this->obtemVolume());

	}
	/** Adiciona os lacres da carga a ser transportada
	 * @author    Emerson Santana
	 * @version    1.0 14-10-2009 09:47
	 * @param string[] $mtz ou recordset referente as lacres da carga
	 */

	private function adicionaLacre($mtz) {
		$this->abreTag("lacres");
		$this->tag("nLacre",$mtz['lacre']);
		$this->fechaTag();

	}
	/** Obtem dados do(s) lacres utilizados para lacrar a carga dos veiculos
	 * @author    Emerson Santana
	 * @version    1.0 14-10-2009 09:47
	 */

	private function obtemLacre() {
		$this->adicionaEntidade("lacres",$this->obtemLacre());

	}
	/** Adiciona dados do veiculo que ira transportar a mercadoria
	 * @author    Emerson Santana
	 * @version    1.0 14-10-2009 09:47
	 * @param string[] $mtz ou recordset dos dados referente ao veiculo que ira transportar a carga
	 */

	private function adicionaVeiculo($mtz) {

		$this->abreTag("veicTransp");
		$this->tag("placa",$mtz['placa']);
		$this->tag("uf",$mtz['uf']);
		//$this->tag("rntrc",$mtz['rntrc']);  // esse campo nao e obrigatorio
		$this->fechaTag();

	}
	/** Obtem dados do veiculo que irah transportar a mercadoria
	 * @author    Emerson Santana
	 * @version    1.0 14-10-2009 09:47
	 */

	private function obtemVeiculo() {

		$this->adicionaEntidade("veicTransp",$this->obtemVeiculo());

	}

	/** Salva conteudo XML em arquivo no disco
	 * @author	giuliano
	 * @version	1.0 07-10-2009 11:01
	 * @param string $file Nome do arquivo
	 */
	private function salva($caminhoArquivo,$conteudo) {
		$arq=fopen($caminhoArquivo,"w");
		fwrite($arq,$conteudo);
		fclose($arq);
	}

	/** Aciona download no navegador do cliente
	 * @author	giuliano
	 * @version	1.0 07-10-2009 11:01
	 * @param string $file Nome do arquivo
	 */
	private function baixa($file="/tmp/nf.xml", $name=false, $type=false, $down=true) {

		if(!file_exists($file)) exit;
		if(!$name) $name = basename($file);
		if($down) $type = "application/force-download";
		else if(!$type) $type = "application/download";
		$disp = $down ? "attachment" : "inline";
		header("Content-disposition: ".$disp."; filename=$name");
		header("Content-length: ".filesize($file));
		header("Content-type: ".$type);
		header("Connection: close");
		header("Expires: 0");
		set_time_limit(0);
		readfile($file);
		exit;
	}

	/** Adiciona assinatura (pagina 16 do manual)
	 * @author	mateus
	 * @version	1.0 29-10-2009 11:26
	 * @return string
	 */
	private function adicionaAssinatura() {
		// Web Services - Informacoes da Assinatura Digital (ZC)
		$this->abreTag('Signature','xmlns="http://www.w3.org/2000/09/xmldsig#"');
			$this->abreTag('SignedInfo');
				$this->xml.='<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'.NL;
				$this->xml.='<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1" />'.NL;
				$this->abreTag('Reference','URI="#'.$this->adicionaAssinaturaContribuinte().'"');
					$this->abreTag('Transforms');
						$this->xml.='<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>';
						$this->xml.='<Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>';
					$this->fechaTag(); // Transforms
					$this->xml.='<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>';
					$this->tag('DigestValue', 'vFL68WETQ+mvj1aJAMDx+oVi928='); // TODO: verificar se tem que gerar isso
				$this->fechaTag(); // reference
			$this->fechaTag(); // SignedInfo
			$this->tag('SignatureValue','IhXNhbdL1F9UGb2ydVc5v/gTB/y6r0KIFaf5evUi1i ...'); // TODO: checar como gerar este conteudo
			$this->abreTag('KeyInfo');
				$this->abreTag('X509Data');
					$this->tag('X509Certificate', 'MIIFazCCBFOgAwIBAgIQaHEfNaxSeOEvZGlVDANB ... '); // TODO: checar como gerar este conteudo
				$this->fechaTag(); // /X509Data>
			$this->fechaTag(); // /KeyInfo

		$this->fechaTag(); // Signature
	}

	/** Gera um lote de nota fiscal
	 * @author	mateus
	 * @version	1.0 30-10-2009 11:26
	 */
	public function geraNotaFiscal($tipo,$json="") {
	// armazenagem, transporte, locacao, servico,
		$this->tipoNf=$tipo; // tipo de nota: auxilia o processamento dos dados de acordo com o tipo da nota

		$this->adicionaXMLCabecalho($this->geraLote()); // <?xml version="1.0" encoding="UTF-8" ...
		$this->adicionaNotas();
		$this->adicionaXMLRodape();

		echo $this->xml; // TODO: remover esta linha de debug

	// XML pronto, salva e faz o download
	//$this->salva($this->obtemCaminhoArquivoXml(),$this->obtemXml());
	//$this->baixa($this->obtemCaminhoArquivoXml());
	}

}

?>
