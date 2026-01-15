<?
header( 'Content-Type: text/html; charset=UTF-8' );
include "gNF.php";
/**
 * Classe responsavel por gerar arquivo XML para NFSe (nota fiscal de servico eletronica)
 * @package gNF
 * @author mateus
 * @version 19/11/2009 15:24:47
 * @author mateus
 */
class gNFSe extends gNF {
	var $notaXml="";		// array contendo ID e XMl de cada nota
	var $temp="";			// TODO: NECESSARIO?
	var $xml="";
	var $rps="";

	/*
	 function __construct() {
		$this->geraNf();
		}
		*/

	function __construct() {
		$this->geraLoteRps();
	}

	/** Adiciona cabecalho
	 * @author	mateus
	 * @version	1.0 16-11-2009 09:47

	 */
	protected function adicionaCabecalho($mtz = "") {
		$this->xml.= '<?xml version="1.0" encoding="UTF-8"?>'.NL;
		$this->xml .='<EnviarLoteRpsEnvio xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http:/www.abrasf.org.br/nfse.xsd">'.NL;
	}

	/** Adiciona rodape
	 * @author	mateus
	 * @version 19/11/2009 15:24:47
	 * @param
	 */
	protected function adicionaRodape() {
		$this->xml.= "</EnviarLoteRpsEnvio>".NL;
	}

	protected function adicionaRpsItem($mtz) {
		$this->temp['rps'].=$this->abreTag('Servico','',false);
		$this->temp['rps'].=$this->abreTag('Valores','',false);
		$this->temp['rps'].=$this->tag('ValorServicos',$this->temp['item']['ValorServicos'],'',false);
		//$this->tag('ValorDeducoes',$mtz['ValorDeducoes']); // nao obrigatorio
		$this->temp['rps'].=$this->tag('ValorPis',$this->temp['item']['ValorPis'],'',false); // TODO: aliquota ou valor??
		$this->temp['rps'].=$this->tag('IssRetido',$this->temp['item']['IssRetido'],'',false); // Sim/N?o TODO: Deve ser informado na hora da emissao da Nota, segundo Nali. Criar este campo na emissao de notas
		$this->temp['rps'].=$this->tag('BaseCalculo',$this->temp['item']['BaseCalculo'],'',false);
		$this->temp['rps'].=$this->fechaTag(false);
		$this->temp['rps'].=$this->tag('ItemListaServico',$this->temp['item']['ItemListaServico'],'',false); // codigo do servico prestado
		//$this->tag('CodigoTributacaoMunicipio',$mtz['CodigoTributacaoMunicipio']); // nao obrigatorio
		//$this->temp['rps'].=$this->tag('Discriminacao',utf8_decode($mtz['discriminacao']) ,'',false); // discriminacao dos servicos TODO: errro de condificacao mesmo tentanto encode e decode para UTF-8...
		//echo  utf8_encode($this->tag('Discriminacao',$mtz['discriminacao'] ,'',false));
		$this->temp['rps'].=$this->tag('Discriminacao',$this->temp['item']['Discriminacao'],'',false ); // descriminacao dos servicos // TODO: reativar
		//		$this->temp['rps'].=$this->tag('CodigoMunicipio',$mtz['CodigoMunicipio'],'',false); // codigo do IBGE
		$this->temp['rps'].=$this->tag('CodigoMunicipio',$this->temp['item']['CodigoMunicipio'],'',false); // codigo do IBGE // TODO: codigo teste
		$this->temp['rps'].=$this->fechaTag(false);		
	} 
	
	/** Adiciona um Rps (adiciona nota ()
	 * @author	mateus
	 * @version 02-02-2010
	 */
	protected function adicionaRps() {
			$rs=$this->obtemRpss();
			while(!$rs->EOF) {
				$mtz=$rs->fields;
				//print_r($mtz); exit;
				$this->temp['rps']=''; // limpa variavel para processar outra nota
				$this->temp['rps'].=$this->abreTag("Rps",'',false);
				$this->temp['rps'].=$this->abreTag("InfRps",'id="infrps'.$mtz['idNf'].$mtz['itemCod'].'"',false); // TODO: retirar
				$this->temp['rps'].=$this->abreTag("IdentificacaoRps",'',false);
				$this->temp['rps'].=$this->tag("Numero",date('20y').str_pad($mtz['numero'], 11, "0", STR_PAD_LEFT),0,false); // ano+11digitos no formato: AAAANNNNNNNNNNN TODO: pode ser o numero da nota??
				$this->temp['rps'].=$this->tag("Serie",trim($mtz['serie']),0,false); // nao obrigatorio
				$this->temp['rps'].=$this->tag("Tipo",1,0,false); // 1 ? Recibo Provis?rio de Servi?os; 2 ? RPS Conjugada (Mista); 3 ? Cupom. TODO: por enquanto sera apenas codigo 1, segundo Nali
				$this->temp['rps'].=$this->fechaTag(false);
				$mtz['dataEmissao']=str_replace(" ","T",$mtz['data_emissao']); //2007-09-26 00:00:00.000
				$mtz['dataEmissao']=str_replace(".000","",$mtz['dataEmissao']); //2007-09-26 00:00:00.000
				$this->temp['rps'].=$this->tag("DataEmissao",$mtz['dataEmissao'],0,false); // formato: AAAA-MM-DDTHH:mm:ss
				$this->temp['rps'].=$this->tag("NaturezaOperacao",$this->obtemNaturezaOperacao($mtz['cfop']),0,false); // 1=no municipio 2= fora do mun. 3=insencao 4=imune 5=exigibilidade suspenca por decisao judicial 6=exigibilidade suspenca por procedimento administrativo
				$this->temp['rps'].=$this->tag("OptanteSimplesNacional",2/*$mtz['OptanteSimplesNacional']*/,0,false); // 1=sim 2=nao
				$this->temp['rps'].=$this->tag("IncentivadorCultural",2/*$mtz['IncentivadorCultural']*/,0,false); // 1=sim 2=nao
				$this->temp['rps'].=$this->tag("Status",1/*$mtz['Status']*/,0,false); // 1=normal 2=cancelado
	
				//SERVICO/////////////////////////////////////////////////////////////////////////////////////////////////////// 
				$this->temp['rps'].=$this->abreTag('Servico','',false);
				$this->temp['rps'].=$this->abreTag('Valores','',false);
				$this->temp['rps'].=$this->tag('ValorServicos',$mtz['valor_unitario'],'',false);
				//$this->tag('ValorDeducoes',$mtz['ValorDeducoes']); // nao obrigatorio
				$this->temp['rps'].=$this->tag('ValorPis',$mtz['pis'],'',false); // TODO: aliquota ou valor??
				$this->temp['rps'].=$this->tag('IssRetido',$mtz['issRetido'],'',false); // Sim/N?o TODO: Deve ser informado na hora da emissao da Nota, segundo Nali. Criar este campo na emissao de notas
				$this->temp['rps'].=$this->tag('BaseCalculo',$mtz['valor_unitario'],'',false);
				$this->temp['rps'].=$this->fechaTag(false);
				$this->temp['rps'].=$this->tag('ItemListaServico',$mtz['itemListaServico'],'',false); // codigo do servico prestado
				//$this->temp['rps'].=$this->tag('Discriminacao',$mtz['nome'],'',false ); // descriminacao dos servicos // TODO: reativar
				$this->temp['rps'].=$this->tag('Discriminacao','teste servico','',false ); // descriminacao dos servicos // TODO: reativar				
				$this->temp['rps'].=$this->tag('CodigoMunicipio',$mtz['itemCod'],'',false); // codigo do IBGE
				//$this->temp['rps'].=$this->tag('CodigoMunicipio',$mtz['municipioIbge'],'',false); // codigo do IBGE // TODO: codigo teste
				$this->temp['rps'].=$this->fechaTag(false);						
				/////////////////////////////////////////////////////////////////////////////////////////////////////////				
				
				$this->temp['rps'].=$this->adicionaItemPrestador($mtz['id_geral_pessoas_filial']); // filial TODO: filial da emissao ou filial a qual a nota refere-se???
				$this->temp['rps'].=$this->adicionaItemTomador($mtz['id_geral_pessoas_cliente']);
				$this->temp['rps'].=$this->fechaTag(false);
				$this->temp['rps'].=$this->fechaTag(false);

				// Assina a tag InfRps
				$ass=$this->adicionaAssinatura($this->temp['rps'],'InfRps','infrps'.$mtz['idNf'].$mtz['itemCod']);
				$aux=str_replace('<?xml version="1.0"?>','',$ass); // retira o cabecalho retornado
				//$this->temp['notas'].=$aux;
	
				//TESTE
				$this->temp['rpss'].=$aux;
				
				$rs->movenext();
			}
	}

	/** Adiciona o lote dos Rpss (adiciona notas)
	 * @author	mateus
	 * @version 02-02-2010
	 */
	protected function adicionaLoteRps() {
		$this->obtemRpss();
		$mtz=$this->obtemEntidade(2593); // inter 1, por enquanto apenas Inter 1 ira emitir Rps, segundo Nali TODO: ja funciona mas, checar com calma isso dps, com Nali
		$this->temp['lote']=''; // limpa variavel para processar outra
		$this->temp['lote'].="<teste>";
		$this->temp['lote'].=$this->abreTag("LoteRps",'id="'.$this->obtemLote().'"','',false); // obtem numero do lote na classe estendida // TODO: retirar
		//$this->temp['lote'].=$this->abreTag("LoteRps",'id="1"','',false); // obtem numero do lote na classe estendida // TODO: retirar
		$this->temp['lote'].=$this->tag("NumeroLote",$this->obtemLote(),'',false);
		$this->temp['lote'].=$this->tag("Cnpj",$this->formataCnpj($mtz['cnpj']),'',false); // inscricaoMunicipal do local da emissao do RPS, segundo Nali
		$this->temp['lote'].=$this->tag("InscricaoMunicipal",$mtz['insc_municipal'],'',false); // inscricaoMunicipal do local da emissao do RPS, segundo Nali
		//$this->temp['lote'].=$this->tag("QuantidadeRps",$this->obtemNfQtde(),'',false); // quantidade de notas fiscais TODO:retirar codigo de teste
		$this->temp['lote'].=$this->tag("QuantidadeRps",2,'',false); // quantidade de notas fiscais TODO:retirar codigo de teste
		$this->temp['lote'].=$this->abreTag("ListaRps",'',false);
		$this->adicionaRps(); // tag <Rps></Rps>
		$this->temp['lote'].=$this->temp['rpss'];
		$this->temp['lote'].=$this->fechaTag(false);
		$this->temp['lote'].=$this->fechaTag(false);
		$this->temp['lote'].="</teste>";
		//echo $this->temp['lote'];exit;

		//$ass=$this->adicionaAssinatura($xml,'LoteRps','2');
		$ass=$this->adicionaAssinatura($this->temp['lote'],'LoteRps','2');

		$aux=str_replace('<?xml version="1.0"?>','',$ass); // retira o cabecalho retornado

		$aux=str_replace('<teste>','',$aux);
		$aux=str_replace('</teste>','',$aux);
		$this->xml.=$aux;
	}

	/** Adiciona todas notas
	 * @author	mateus
	 * @version	1.0
	 * @return string
	 */
	protected function adicionaNotas() {

		//$xml='<EnviarLoteRpsEnvio xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http:/www.abrasf.org.br/nfse.xsd"><LoteRps id="2"><NumeroLote>2</NumeroLote><Cnpj>96825575000112</Cnpj><InscricaoMunicipal>11111111111</InscricaoMunicipal><ListaRps><Rps><InfRps id="infrps201"><IdentificacaoRps><Numero>201000000048609</Numero><Serie>U</Serie><Tipo>1</Tipo></IdentificacaoRps><DataEmissao>2007-09-26T00:00:00</DataEmissao><NaturezaOperacao>1</NaturezaOperacao><OptanteSimplesNacional>2</OptanteSimplesNacional><IncentivadorCultural>2</IncentivadorCultural><Status>1</Status><Servico><Valores><ValorServicos>150.0</ValorServicos><BaseCalculo>150.0</BaseCalculo></Valores><ItemListaServico>69</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>120.0</ValorServicos><BaseCalculo>120.0</BaseCalculo></Valores><ItemListaServico>69</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>55.0</ValorServicos><BaseCalculo>55.0</BaseCalculo></Valores><ItemListaServico>40</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>55.0</ValorServicos><BaseCalculo>55.0</BaseCalculo></Valores><ItemListaServico>41</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>131.0</ValorServicos><BaseCalculo>131.0</BaseCalculo></Valores><ItemListaServico>43</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>90.0</ValorServicos><BaseCalculo>90.0</BaseCalculo></Valores><ItemListaServico>39</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Prestador><Cnpj>96825575000112</Cnpj><InscricaoMunicipal>11111111111</InscricaoMunicipal></Prestador><Tomador><IdentificacaoTomador><Cnpj>16460081000142</Cnpj></IdentificacaoTomador></Tomador></InfRps></Rps><Rps><InfRps id="infrps202"><IdentificacaoRps><Numero>201000000002766</Numero><Serie>U</Serie><Tipo>1</Tipo></IdentificacaoRps><DataEmissao>2007-09-26T00:00:00</DataEmissao><NaturezaOperacao>1</NaturezaOperacao><OptanteSimplesNacional>2</OptanteSimplesNacional><IncentivadorCultural>2</IncentivadorCultural><Status>1</Status><Servico><Valores><ValorServicos>185.0</ValorServicos><BaseCalculo>185.0</BaseCalculo></Valores><ItemListaServico>142</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Prestador><Cnpj>96825575000112</Cnpj><InscricaoMunicipal>11111111111</InscricaoMunicipal></Prestador><Tomador><IdentificacaoTomador><Cnpj>04705090000681</Cnpj></IdentificacaoTomador></Tomador></InfRps></Rps></ListaRps></LoteRps></EnviarLoteRpsEnvio>';
		//$xml='<EnviarLoteRpsEnvio><LoteRps id="2"><NumeroLote>2</NumeroLote><Cnpj>96825575000112</Cnpj><InscricaoMunicipal>11111111111</InscricaoMunicipal><ListaRps><Rps><InfRps id="infrps201"><IdentificacaoRps><Numero>201000000048609</Numero><Serie>U</Serie><Tipo>1</Tipo></IdentificacaoRps><DataEmissao>2007-09-26T00:00:00</DataEmissao><NaturezaOperacao>1</NaturezaOperacao><OptanteSimplesNacional>2</OptanteSimplesNacional><IncentivadorCultural>2</IncentivadorCultural><Status>1</Status><Servico><Valores><ValorServicos>150.0</ValorServicos><BaseCalculo>150.0</BaseCalculo></Valores><ItemListaServico>69</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>120.0</ValorServicos><BaseCalculo>120.0</BaseCalculo></Valores><ItemListaServico>69</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>55.0</ValorServicos><BaseCalculo>55.0</BaseCalculo></Valores><ItemListaServico>40</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>55.0</ValorServicos><BaseCalculo>55.0</BaseCalculo></Valores><ItemListaServico>41</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>131.0</ValorServicos><BaseCalculo>131.0</BaseCalculo></Valores><ItemListaServico>43</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>90.0</ValorServicos><BaseCalculo>90.0</BaseCalculo></Valores><ItemListaServico>39</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Prestador><Cnpj>96825575000112</Cnpj><InscricaoMunicipal>11111111111</InscricaoMunicipal></Prestador><Tomador><IdentificacaoTomador><Cnpj>16460081000142</Cnpj></IdentificacaoTomador></Tomador></InfRps></Rps><Rps><InfRps id="infrps202"><IdentificacaoRps><Numero>201000000002766</Numero><Serie>U</Serie><Tipo>1</Tipo></IdentificacaoRps><DataEmissao>2007-09-26T00:00:00</DataEmissao><NaturezaOperacao>1</NaturezaOperacao><OptanteSimplesNacional>2</OptanteSimplesNacional><IncentivadorCultural>2</IncentivadorCultural><Status>1</Status><Servico><Valores><ValorServicos>185.0</ValorServicos><BaseCalculo>185.0</BaseCalculo></Valores><ItemListaServico>142</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Prestador><Cnpj>96825575000112</Cnpj><InscricaoMunicipal>11111111111</InscricaoMunicipal></Prestador><Tomador><IdentificacaoTomador><Cnpj>04705090000681</Cnpj></IdentificacaoTomador></Tomador></InfRps></Rps></ListaRps></LoteRps></EnviarLoteRpsEnvio>';
		$xml='<LoteRps id="2"><NumeroLote>2</NumeroLote><Cnpj>96825575000112</Cnpj><InscricaoMunicipal>11111111111</InscricaoMunicipal><ListaRps><Rps><InfRps id="infrps201"><IdentificacaoRps><Numero>201000000048609</Numero><Serie>U</Serie><Tipo>1</Tipo></IdentificacaoRps><DataEmissao>2007-09-26T00:00:00</DataEmissao><NaturezaOperacao>1</NaturezaOperacao><OptanteSimplesNacional>2</OptanteSimplesNacional><IncentivadorCultural>2</IncentivadorCultural><Status>1</Status><Servico><Valores><ValorServicos>150.0</ValorServicos><BaseCalculo>150.0</BaseCalculo></Valores><ItemListaServico>69</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>120.0</ValorServicos><BaseCalculo>120.0</BaseCalculo></Valores><ItemListaServico>69</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>55.0</ValorServicos><BaseCalculo>55.0</BaseCalculo></Valores><ItemListaServico>40</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>55.0</ValorServicos><BaseCalculo>55.0</BaseCalculo></Valores><ItemListaServico>41</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>131.0</ValorServicos><BaseCalculo>131.0</BaseCalculo></Valores><ItemListaServico>43</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Servico><Valores><ValorServicos>90.0</ValorServicos><BaseCalculo>90.0</BaseCalculo></Valores><ItemListaServico>39</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Prestador><Cnpj>96825575000112</Cnpj><InscricaoMunicipal>11111111111</InscricaoMunicipal></Prestador><Tomador><IdentificacaoTomador><Cnpj>16460081000142</Cnpj></IdentificacaoTomador></Tomador></InfRps></Rps><Rps><InfRps id="infrps202"><IdentificacaoRps><Numero>201000000002766</Numero><Serie>U</Serie><Tipo>1</Tipo></IdentificacaoRps><DataEmissao>2007-09-26T00:00:00</DataEmissao><NaturezaOperacao>1</NaturezaOperacao><OptanteSimplesNacional>2</OptanteSimplesNacional><IncentivadorCultural>2</IncentivadorCultural><Status>1</Status><Servico><Valores><ValorServicos>185.0</ValorServicos><BaseCalculo>185.0</BaseCalculo></Valores><ItemListaServico>142</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Prestador><Cnpj>96825575000112</Cnpj><InscricaoMunicipal>11111111111</InscricaoMunicipal></Prestador><Tomador><IdentificacaoTomador><Cnpj>04705090000681</Cnpj></IdentificacaoTomador></Tomador></InfRps></Rps></ListaRps></LoteRps>';

		//$ass=$this->adicionaAssinatura($xml,'InfRps','infrps201');
		//exit;

		$mtz=$this->obtemEntidade(2593); // inter 1, por enquanto apenas Inter 1 ira emitir Rps, segundo Nali TODO: ja funciona mas, checar com calma isso dps, com Nali
		$this->temp['lote']=''; // limpa variavel para processar outra
		$this->temp['lote'].="<teste>";
		$this->temp['lote'].=$this->abreTag("LoteRps",'id="'.$this->obtemLote().'"','',false); // obtem numero do lote na classe estendida // TODO: retirar
		//$this->temp['lote'].=$this->abreTag("LoteRps",'id="1"','',false); // obtem numero do lote na classe estendida // TODO: retirar
		$this->temp['lote'].=$this->tag("NumeroLote",$this->obtemLote(),'',false);
		$this->temp['lote'].=$this->tag("Cnpj",$this->formataCnpj($mtz['cnpj']),'',false); // inscricaoMunicipal do local da emissao do RPS, segundo Nali
		$this->temp['lote'].=$this->tag("InscricaoMunicipal",$mtz['insc_municipal'],'',false); // inscricaoMunicipal do local da emissao do RPS, segundo Nali
		//$this->temp['lote'].=$this->tag("QuantidadeRps",$this->obtemNfQtde(),'',false); // quantidade de notas fiscais TODO:retirar codigo de teste
		$this->temp['lote'].=$this->tag("QuantidadeRps",2,'',false); // quantidade de notas fiscais TODO:retirar codigo de teste
		$this->temp['lote'].=$this->abreTag("ListaRps",'',false);
		parent::adicionaNotas(); // tag <Rps></Rps>
		$this->temp['lote'].=$this->temp['notas'];
		$this->temp['lote'].=$this->fechaTag(false);
		$this->temp['lote'].=$this->fechaTag(false);
		$this->temp['lote'].="</teste>";
		//echo $this->temp['lote'];exit;


		//$ass=$this->adicionaAssinatura($xml,'LoteRps','2');
		$ass=$this->adicionaAssinatura($this->temp['lote'],'LoteRps','2');

		$aux=str_replace('<?xml version="1.0"?>','',$ass); // retira o cabecalho retornado

		$aux=str_replace('<teste>','',$aux);
		$aux=str_replace('</teste>','',$aux);
		$this->xml.=$aux;

		//echo $aux;exit;
	}

	/** Adiciona uma nota
	 * @author	mateus
	 * @version 19/11/2009 15:24:47
	 * @param
	 */
	protected function adicionaNota() {
		$this->nota=$this->obtemNota();
		$mtz=&$this->nota;
		if (is_array($mtz)) {
			$this->temp['rps']=''; // limpa variavel para processar outra nota
			$this->temp['rps'].=$this->abreTag("Rps",'',false);
			$this->temp['rps'].=$this->abreTag("InfRps",'id="infrps'.$mtz['id'].'"',false); // TODO: retirar
			$this->temp['rps'].=$this->abreTag("IdentificacaoRps",'',false);
			$this->temp['rps'].=$this->tag("Numero",date('20y').str_pad($mtz['numero'], 11, "0", STR_PAD_LEFT),0,false); // ano+11digitos no formato: AAAANNNNNNNNNNN TODO: pode ser o numero da nota??
			$this->temp['rps'].=$this->tag("Serie",trim($mtz['serie']),0,false); // nao obrigatorio
			$this->temp['rps'].=$this->tag("Tipo",1,0,false); // 1 ? Recibo Provis?rio de Servi?os; 2 ? RPS Conjugada (Mista); 3 ? Cupom. TODO: por enquanto sera apenas codigo 1, segundo Nali
			$this->temp['rps'].=$this->fechaTag(false);
			$mtz['dataEmissao']=str_replace(" ","T",$mtz['dataEmissao']); //2007-09-26 00:00:00.000
			$mtz['dataEmissao']=str_replace(".000","",$mtz['dataEmissao']); //2007-09-26 00:00:00.000
			$this->temp['rps'].=$this->tag("DataEmissao",$mtz['dataEmissao'],0,false); // formato: AAAA-MM-DDTHH:mm:ss
			$this->temp['rps'].=$this->tag("NaturezaOperacao",$this->obtemNaturezaOperacao($mtz['cfop']),0,false); // 1=no municipio 2= fora do mun. 3=insencao 4=imune 5=exigibilidade suspenca por decisao judicial 6=exigibilidade suspenca por procedimento administrativo
			$this->temp['rps'].=$this->tag("OptanteSimplesNacional",$mtz['OptanteSimplesNacional'],0,false); // 1=sim 2=nao
			$this->temp['rps'].=$this->tag("IncentivadorCultural",$mtz['IncentivadorCultural'],0,false); // 1=sim 2=nao
			$this->temp['rps'].=$this->tag("Status",$mtz['Status'],0,false); // 1=normal 2=cancelado
			$this->temp['rps'].=$this->adicionaItens(); // servicos
			$this->temp['rps'].=$this->adicionaItemPrestador($mtz['id_geral_pessoas_filial']); // filial TODO: filial da emissao ou filial a qual a nota refere-se???
			$this->temp['rps'].=$this->adicionaItemTomador($mtz['id_geral_pessoas_cliente']);
			$this->temp['rps'].=$this->fechaTag(false);
			$this->temp['rps'].=$this->fechaTag(false);

			//$this->temp['rps']='<Rps><InfRps id="infrps202"><IdentificacaoRps><Numero>201000000002766</Numero><Serie>U</Serie><Tipo>1</Tipo></IdentificacaoRps><DataEmissao>2007-09-26T00:00:00</DataEmissao><NaturezaOperacao>1</NaturezaOperacao><OptanteSimplesNacional>2</OptanteSimplesNacional><IncentivadorCultural>2</IncentivadorCultural><Status>1</Status><Servico><Valores><ValorServicos>185.0</ValorServicos><BaseCalculo>185.0</BaseCalculo></Valores><ItemListaServico>142</ItemListaServico><Discriminacao>teste-servico</Discriminacao></Servico><Prestador><Cnpj>96825575000112</Cnpj><InscricaoMunicipal>11111111111</InscricaoMunicipal></Prestador><Tomador><IdentificacaoTomador><Cnpj>04705090000681</Cnpj></IdentificacaoTomador></Tomador></InfRps></Rps>';

			// Assina a tag InfRps
			$ass=$this->adicionaAssinatura($this->temp['rps'],'InfRps','infrps'.$mtz['id']);
			$aux=str_replace('<?xml version="1.0"?>','',$ass); // retira o cabecalho retornado
			$this->temp['notas'].=$aux;

			$sai=true;
		} else {
			$sai=false;
		}
		return($sai);
	}

	/**Adiciona um item (servico)
	 * @author	mateus
	 * @version 19/11/2009 15:24:47
	 */
	protected function adicionaItem($mtz="") {
		$mtz=$this->obtemItem();
		if($mtz) {
			if (is_array($mtz)) {
				$this->temp['item']['ValorServicos']+=$mtz['valor_unitario'];
				$this->temp['item']['ValorPis']+=$mtz['valor_unitario']*($mtz['pis']/100);
				$this->temp['item']['IssRetido']=$mtz['issRetido']<>""?$mtz['issRetido']:2;
				$this->temp['item']['BaseCalculo']=+$mtz['valor_unitario'];
				$this->temp['item']['ItemListaServico']=$mtz['itemListaServico'];
				//$this->temp['item']['Discriminacao'].=$mtz['discriminacao']." \n "; // TODO: reativar
				$this->temp['item']['Discriminacao']="--SERVICO--".",";//$mtz['discriminacao']." \n ";
				//$this->temp['item']['CodigoMunicipio']=$mtz['CodigoMunicipio']; // TODO: reativar
				$this->temp['item']['CodigoMunicipio']="1234";
			}

			return true;
		}
		else {
			return false;
		}
	}

	protected function adicionaItens() {
		parent::adicionaItens();
		echo 'teste: nota '.$this->nota['id'].' tem '.$this->qtdeItem[$this->nota['id']].' itens<br/>';
		$this->temp['rps'].=$this->abreTag('Servico','',false);
		$this->temp['rps'].=$this->abreTag('Valores','',false);
		$this->temp['rps'].=$this->tag('ValorServicos',$this->temp['item']['ValorServicos'],'',false);
		//$this->tag('ValorDeducoes',$mtz['ValorDeducoes']); // nao obrigatorio
		$this->temp['rps'].=$this->tag('ValorPis',$this->temp['item']['ValorPis'],'',false); // TODO: aliquota ou valor??
		$this->temp['rps'].=$this->tag('IssRetido',$this->temp['item']['IssRetido'],'',false); // Sim/N?o TODO: Deve ser informado na hora da emissao da Nota, segundo Nali. Criar este campo na emissao de notas
		$this->temp['rps'].=$this->tag('BaseCalculo',$this->temp['item']['BaseCalculo'],'',false);
		$this->temp['rps'].=$this->fechaTag(false);
		$this->temp['rps'].=$this->tag('ItemListaServico',$this->temp['item']['ItemListaServico'],'',false); // codigo do servico prestado
		//$this->tag('CodigoTributacaoMunicipio',$mtz['CodigoTributacaoMunicipio']); // nao obrigatorio
		//$this->temp['rps'].=$this->tag('Discriminacao',utf8_decode($mtz['discriminacao']) ,'',false); // discriminacao dos servicos TODO: errro de condificacao mesmo tentanto encode e decode para UTF-8...
		//echo  utf8_encode($this->tag('Discriminacao',$mtz['discriminacao'] ,'',false));
		$this->temp['rps'].=$this->tag('Discriminacao',$this->temp['item']['Discriminacao'],'',false ); // descriminacao dos servicos // TODO: reativar
		//		$this->temp['rps'].=$this->tag('CodigoMunicipio',$mtz['CodigoMunicipio'],'',false); // codigo do IBGE
		$this->temp['rps'].=$this->tag('CodigoMunicipio',$this->temp['item']['CodigoMunicipio'],'',false); // codigo do IBGE // TODO: codigo teste
		$this->temp['rps'].=$this->fechaTag(false);
	}

	/** Adiciona prestador do item da nota
	 * @author	mateus
	 * @version 19/11/2009 15:24:47
	 * @param
	 */
	protected function adicionaItemPrestador($id) {
		//$mtz=$this->obtemEntidade($this->nota['id_geral_pessoas_filial']);
		$mtz=$this->obtemEntidade($id);		
		$this->temp['rps'].=$this->abreTag('Prestador','',false);
		$this->temp['rps'].=$this->tag('Cnpj',$this->formataCnpj($mtz['cnpj']),'',false); // lay-out nao informa a formata?ao, tamanho 14
		$this->temp['rps'].=$this->tag('InscricaoMunicipal',$mtz['insc_municipal'],'',false); // obrigatorio,particularidade SEFAZ-SSA, segundo a Analista do SEFAZ TODO: implementar na classe estendida.
		$this->temp['rps'].=$this->fechaTag(false);
	}

	/** Adiciona tomador do item da nota
	 * @author	mateus
	 * @version 19/11/2009 15:24:47
	 * @param
	 */
	protected function adicionaItemTomador($id) {
		//$mtz=$this->obtemEntidade($this->nota['idCliente']);
		$mtz=$this->obtemEntidade($id);

		$this->temp['rps'].=$this->abreTag('Tomador','',false);
		$this->temp['rps'].=$this->tag('RazaoSocial',"RAZAOSOCIALTESTE",'',false);
		$this->temp['rps'].=$this->fechaTag(false);
		/* // Obrigatorio o Cnpj
		 $this->temp['rps'].=$this->abreTag('Tomador','',false);
		 $this->temp['rps'].=$this->abreTag('IdentificacaoTomador','',false);
		 $this->temp['rps'].=$this->tag('Cnpj',"96825575000112",'',false);
		 $this->temp['rps'].=$this->fechaTag(false);
		 $this->temp['rps'].=$this->fechaTag(false);
		 */

	}

	/** Define/Obtem a natureza de operacao
	 * @author	mateus
	 * @version 01/12/2009
	 */
	private function obtemNaturezaOperacao($cfop) {
		// TODO: revisar este metodo
		// 1=no municipio
		// 2= fora do mun.
		// 3=insencao
		// NAO SE APLICA A INTERMARITIMA 4=imune 5=exigibilidade suspenca por decisao judicial 6=exigibilidade suspenca por procedimento administrativo
		if(substr($cfop, 0,1)=="5") { // dentro do estado
			$cfop=1;
		}
		if(substr($cfop, 0,1)=="6") { // fora do estado
			$cfop=2;
		}

		return $cfop;
	}

	private function geraNf() {
		$this->ajustaArquivoXmlNome('servico_enviar_lote_rps_envio');
		$this->xml="";
		$this->assinatura=new gNfAssinatura();
		$this->adicionaCabecalho($this->geraLote()); // <?xml version="1.0" encoding="UTF-8" ...
		$this->adicionaNotas();
		$this->adicionaRodape();
		//exit;
		echo $this->xml; exit;
		//echo 'caminho='.$this->obtemCaminhoArquivoXml().'<br>';
		//echo 'xml'.$this->obtemXml().'<br>';exit;
		// XML pronto, salva e faz o download
		$this->salva($this->obtemCaminhoArquivoXml().$this->obtemCaminhoArquivoXml,$this->obtemXml());
		$this->baixa($this->obtemCaminhoArquivoXml());
	}

	private function geraLoteRps() {
		$this->ajustaArquivoXmlNome('servico_enviar_lote_rps_envio');
		$this->xml="";
		$this->assinatura=new gNfAssinatura();
		$this->adicionaCabecalho($this->geraLote()); // <?xml version="1.0" encoding="UTF-8" ...
		$this->adicionaLoteRps();
		$this->adicionaRodape();
		//exit;
		//echo $this->xml; exit;
		// XML pronto, salva e faz o download
		$this->salva($this->obtemCaminhoArquivoXml().$this->obtemCaminhoArquivoXml,$this->obtemXml());
		$this->baixa($this->obtemCaminhoArquivoXml());
	}
}
?>