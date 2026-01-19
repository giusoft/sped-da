<?
include "gNF.php";
/**
 * Classe responsavel por gerar arquivo XML para NFSe (nota fiscal se servico eletronica)
 * @package gNF
 * @author mateus
 * @version 19/11/2009 15:24:47
 * @author mateus
 */
class gNFSe extends gNF {


	function __construct() {
		//echo "teste";exit;
		$this->ajustaArquivoXmlNome(servico_enviar_lote_rps_envio);
		
		$this->adicionaCabecalho($this->geraLote()); // <?xml version="1.0" encoding="UTF-8" ...
		$this->adicionaNotas();
		$this->adicionaAssinatura();
		$this->adicionaRodape();

		//return $this->xml; // TODO: remover esta linha de debug

		// XML pronto, salva e faz o download
		//$this->salva($this->obtemCaminhoArquivoXml(),$this->obtemXml());
		//$this->baixa($this->obtemCaminhoArquivoXml());
	}
	
	/** Adiciona cabecalho
	 * @author	mateus
	 * @version	1.0 16-11-2009 09:47

	 */
	protected function adicionaCabecalho($mtz = "") {
		$this->xml.= "<?xml version='1.0' encoding='UTF-8'?>".NL;
		$this->xml .="<EnviarLoteRpsEnvio xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xmlns:xsd='http://www.w3.org/2001/XMLSchema' xmlns='http:/www.abrasf.org.br/nfse.xsd'>".NL;
	}

	/** Adiciona rodape
	 * @author	mateus
	 * @version 19/11/2009 15:24:47
	 * @param
	 */
	protected function adicionaRodape() {		
		$this->xml.= "</EnviarLoteRpsEnvio>".NL;
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
			$this->abreTag("Rps");
				$this->abreTag("InfRps","Id='".$mtz['id']."' "); // TODO: colocar ID da nota??
					$this->abreTag("IdentificacaoRps");
						$this->tag("Numero",date('20y').str_pad($mtz['numero'], 11, "0", STR_PAD_LEFT)); // ano+11digitos no formato: AAAANNNNNNNNNNN TODO: pode ser o numero da nota??
						$this->tag("Serie",trim($mtz['serie'])); // nao obrigatorio
						$this->tag("Tipo",1); // 1 ? Recibo Provisório de Serviços; 2 ? RPS Conjugada (Mista); 3 ? Cupom. TODO: por enquanto sera apenas codigo 1, segundo Nali
					$this->fechaTag();
					$mtz['dataEmissao']=str_replace(" ","T",$mtz['dataEmissao']); //2007-09-26 00:00:00.000
					$mtz['dataEmissao']=str_replace(".000","",$mtz['dataEmissao']); //2007-09-26 00:00:00.000
					$this->tag("DataEmissao",$mtz['dataEmissao']); // formato: AAAA-MM-DDTHH:mm:ss
					$this->tag("NaturezaOperacao",$this->obtemNaturezaOperacao($mtz['cfop']) ); // 1=no municipio 2= fora do mun. 3=insencao 4=imune 5=exigibilidade suspenca por decisao judicial 6=exigibilidade suspenca por procedimento administrativo
					$this->tag("OptanteSimplesNacional",$mtz['OptanteSimplesNacional']); // 1=sim 2=nao
					$this->tag("IncentivadorCultural",$mtz['IncentivadorCultural']); // 1=sim 2=nao
					$this->tag("Status",$mtz['Status']); // 1=normal 2=cancelado
					$this->adicionaItens(); // servicos
					$this->adicionaItemPrestador($mtz['id_geral_pessoas_filial']); // filial TODO: filial da emissao ou filial a qual a nota refere-se???
					$this->adicionaItemTomador($mtz['id_geral_pessoas_cliente']);
				$this->fechaTag();
				$this->adicionaItemAssinatura();
			$this->fechaTag();
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
	protected function adicionaItem() {
		$this->item=$this->obtemItem();
		$mtz=&$this->item;
		if (is_array($mtz)) {
			$this->abreTag('Servico');
				$this->abreTag('Valores');
					$this->tag('ValorServicos',$mtz['valor_unitario']);
					//$this->tag('ValorDeducoes',$mtz['ValorDeducoes']); // nao obrigatorio
					$this->tag('ValorPis',$mtz['pis']/100);
					$this->tag('IssRetido',$mtz['issRetido']); // Sim/Não TODO: Deve ser informado na hora da emissao da Nota, segundo Nali. Criar este campo na emissao de notas
					$this->tag('BaseCalculo',$mtz['base_calculo']);
				$this->fechaTag();
				$this->tag('ItemListaServico',$mtz['itemListaServico']); // codigo do servico prestado
				//$this->tag('CodigoTributacaoMunicipio',$mtz['CodigoTributacaoMunicipio']); // nao obrigatorio
				$this->tag('Discriminacao',$mtz['discriminacao']); // discriminacao dos servicos
				$this->tag('CodigoMunicipio',$mtz['CodigoMunicipio']); // codigo do IBGE
			$this->fechaTag();
		}
	}

	/** Adiciona todas notas
	 * @author	mateus
	 * @version	1.0
	 * @return string
	 */
	protected function adicionaNotas() {
		$mtz=$this->obtemEntidade(2593); // inter 1, por enquanto apenas Inter 1 ira emitir Rps, segundo Nali TODO: ja funciona mas, checar com calma isso dps, com Nali
		$this->abreTag("LoteRps",'Id="'.$this->obtemLote().'" '); // obtem numero do lote na classe estendida
			$this->tag("NumeroLote",$this->obtemLote());
			$this->tag("Cnpj",$this->formataCnpj($mtz['cnpj'])); // inscricaoMunicipal do local da emissao do RPS, segundo Nali
			$this->tag("InscricaoMunicipal",$mtz['insc_municipal']); // inscricaoMunicipal do local da emissao do RPS, segundo Nali
			$this->tag("QuantidadeRps",'2'.$this->obtemNfQtde()); // quantidade de notas fiscais TODO:retirar codigo de teste
			$this->abreTag("ListaRps");
			parent::adicionaNotas(); // tag <Rps></Rps>
			$this->fechaTag();
		$this->fechaTag();
	}

	/** Adiciona prestador do item da nota
	 * @author	mateus
	 * @version 19/11/2009 15:24:47
	 * @param
	 */
	protected function adicionaItemPrestador($id) {
		$mtz=$this->obtemEntidade($this->nota['id_geral_pessoas_filial']);
		$this->abreTag('Prestador');
			$this->tag('Cnpj',$this->formataCnpj($mtz['cnpj'])); // lay-out nao informa a formataçao, tamanho 14
			$this->tag('InscricaoMunicipal',$mtz['insc_municipal']); // obrigatorio,particularidade SEFAZ-SSA, segundo a Analista do SEFAZ TODO: implementar na classe estendida.
		$this->fechaTag();
	}
	/** Adiciona tomador do item da nota
	 * @author	mateus
	 * @version 19/11/2009 15:24:47
	 * @param
	 */
	protected function adicionaItemTomador($id) {
		$mtz=$this->obtemEntidade($this->nota['idCliente']);
		$this->abreTag('Tomador');
			$this->abreTag('IdentificacaoTomador');
				$this->tag('Cnpj',$this->formataCnpj($mtz['cnpj']));
				//$this->tag('Endereco',$mtz['logradouro']);
			$this->fechaTag();
		$this->fechaTag();
	}
	/** Adiciona assinatura do item
	 * @author	mateus
	 * @version 19/11/2009 15:24:47
	 * @param
	 */
	protected function adicionaItemAssinatura() {
		$this->xml.='<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
							<SignedInfo>
								<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" />
								<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1" />
								<Reference URI="#PAoA3s_slGd8lx_py1gtoJ3GAT3iEb1YSVKPcSWkLo_-xxqMHhYaUfWPOC9xS8XIYh">
									<Transforms>
										<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />
									</Transforms>
									<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />
									<DigestValue>4SKr1HNSeHM41RvoMm0J03FAGVw=</DigestValue>
								</Reference>
							</SignedInfo>
							<SignatureValue>QAni59AuF+/WUGv9FAtuGZYq/o8u6k1kQQMsTDCRg5hp2aK8pS0pkrcpMTKyFBDUzd/NTxWpnqb/0c3MY4TvPsIUpAVZfyepX9q+f8q/xyJN5z5esE0qeYZLY/BdpqNSaH6OqrFHKQrv2odto2dfeNCW9xfl3V3CLMljDucmCcQ=</SignatureValue>
							<KeyInfo>
								<X509Data>
									<X509Certificate>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</X509Certificate>
								</X509Data>
								<KeyValue>
									<RSAKeyValue>
										<Modulus>sOmqCciqMErHi2wyjbiqnbIqhunNdNrZBX563PRQbbDbYYACejgmjjLxGANjEElA9WVeYcVfAFIsdaXlRn/IbasNfqXST0cJ0qK4pXXK3ntbB/EprqynO7/i7SJdl1eP/f4MPOF3KJj2gdiVjDMugHubDY19fDhEyiN3APUf09k=</Modulus>
										<Exponent>AQAB</Exponent>
									</RSAKeyValue>
								</KeyValue>
							</KeyInfo>
						 </Signature>';
	}

	/** Adiciona assinatura da nota
	 * @author	mateus
	 * @version 19/11/2009 15:24:47
	 * @param
	 */
	protected function adicionaAssinatura() {
		$this->xml.='<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
							<SignedInfo>
								<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" />
								<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1" />
								<Reference URI="#PAoA3s_slGd8lx_py1gtoJ3GAT3iEb1YSVKPcSWkLo_-xxqMHhYaUfWPOC9xS8XIYh">
									<Transforms>
										<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />
									</Transforms>
									<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />
									<DigestValue>4SKr1HNSeHM41RvoMm0J03FAGVw=</DigestValue>
								</Reference>
							</SignedInfo>
							<SignatureValue>QAni59AuF+/WUGv9FAtuGZYq/o8u6k1kQQMsTDCRg5hp2aK8pS0pkrcpMTKyFBDUzd/NTxWpnqb/0c3MY4TvPsIUpAVZfyepX9q+f8q/xyJN5z5esE0qeYZLY/BdpqNSaH6OqrFHKQrv2odto2dfeNCW9xfl3V3CLMljDucmCcQ=</SignatureValue>
							<KeyInfo>
								<X509Data>
									<X509Certificate>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</X509Certificate>
								</X509Data>
								<KeyValue>
									<RSAKeyValue>
										<Modulus>sOmqCciqMErHi2wyjbiqnbIqhunNdNrZBX563PRQbbDbYYACejgmjjLxGANjEElA9WVeYcVfAFIsdaXlRn/IbasNfqXST0cJ0qK4pXXK3ntbB/EprqynO7/i7SJdl1eP/f4MPOF3KJj2gdiVjDMugHubDY19fDhEyiN3APUf09k=</Modulus>
										<Exponent>AQAB</Exponent>
									</RSAKeyValue>
								</KeyValue>
							</KeyInfo>
						 </Signature>';
	}

	/** Define a natureza de operacao
	 * @author	mateus
	 * @version 01/12/2009
	 */
	private function obtemNaturezaOperacao($cfop) {
		// TODO: revisar este metodo
		// 1=no municipio
		//2= fora do mun.
		//3=insencao
		// NAO SE APLICA A INTERMARITIMA 4=imune 5=exigibilidade suspenca por decisao judicial 6=exigibilidade suspenca por procedimento administrativo
		if(substr($cfop, 0,1)=="5") { // dentro do estado
			$cfop=1;
		}
		if(substr($cfop, 0,1)=="6") { // fora do estado
			$cfop=2;
		}

		return $cfop;

	}
}
?>