<?php
include "../inc_2.0/gNFCertificado.php";
include "../inc_2.0/gNFAssinatura.php";

/** Classes para tratamento de Notas Fiscais Eletronicas (SEFAZ)
 * @author	Equipe GiuSoft: giuliano, milton, bruno, emerson e mateus
 * @version	1.0 07-10-2009 11:03
 */

//define ("NL","\r\n");
define ("NL","");

/** Classe responsavel por gerar arquivo XML
 * @package	gNF
 * @author	Equipe GiuSoft: giuliano, milton, bruno, emerson e mateus
 * @version	1.0 07-10-2009 09:34
 */
class gNF {
	protected $log="";											// armazena mensagens de log para serem armazenadas em arquivo posteriormente
	protected $xml="";											// armazena todo codigo XML gerado
	protected $xmlBlocos="";									// armazena todo codigo XML gerado em blocos para posterior processamento
	protected $tags="";											// armazena tags XML geradas
	protected $version="1.0";									// versao da nota que pode ser mudadada dinamicamente apos instanciacao da classe extedida
	protected $caminhoArquivoXml="";							// caminho aonde sera gerado o XML
	protected $nomeArquivoXml='nf.xml';
	protected $caminhoArquivoLog="/tmp/nfe.log";				// caminho aonde sera gerado o log das acoes executadas
	//protected $tipoNf="";										// tipo da nota fiscal. Indica como sera o processamento de acordo com o tipo
	protected $qtdeItem="";										// quantidade de itens em cada nota.
	protected $qtdeNf=0;											// quantidade total de notas do lote
	protected $valorTotalItem=0;								// TODO: necessario?? para calculo de imposto em <total>
	protected $valorTotalItemIss=0;								//
	protected $valorTotalItemIpi=0;								//
	protected $valorTotalItemPis=0;								//
	protected $itemCofins=0;									//
	protected $valorTotalItemIcms=0;							//
	protected $valorNfTotalIpi=0;								// acumulador deste imposto em todos itens
	protected $valorNfTotalIss=0;								// acumulador deste imposto em todos itens
	protected $valorNfTotalPis=0;								// acumulador deste imposto em todos itens
	protected $valorNfTotalCofins=0;							// acumulador deste imposto em todos itens
	protected $valorNfTotalIcms=0;								// acumulador deste imposto em todos itens
	protected $valorNfTotal=0;									// TODO: necessario??....ja que o valor pode ser calculado com base em outros totais dinamicamente
	protected $valorNfTotalFrete=0;								// TODO: necessario??
	protected $item="";											// matriz associativa com todos dados de um item
	protected $nota="";											// matriz associativa com todos dados de cabecalho de uma nota
	protected $assinatura="";

	//public $chaveModelo="M"; // TODO: Identificar valor padrao e colocar aqui
	//public $chaveSerie="UNI"; // TODO: Identificar valor padrao e colocar aqui

	/** Inicia automaticamente ao instanciar o objeto
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 */
	private function __construct() {
		$this->xml="";
		$this->tags="";
		//		$lote_de_notasTESTE=$this->geraNotaFiscal($tipo); // TODO: teste
		//		echo $lote_de_notasTESTE; // teste
	}

	/** Monta a TAG de abertura ou seja, <tag>
	 * @author	giuliano e mateus
	 * @version	1.0 07-10-2009 09:47
	 * @param string $tag TAG propriamente dita
	 * @param string $atributo acrescenta atributo na tag
	 * @param boolean $grava informa se a string resultante deve ser retornada
	 */
	protected function abreTag($tag,$atributo="",$grava=true) {
		$tag=trim($tag);
		if($atributo<>'') {
			$atributo=" ".$atributo;
		}
		if($grava)
		$this->xml.="<$tag$atributo>";
		if (count($this->tags)<3) {
			if($grava)
			$this->xml.=NL;
		}
		$this->tags[]=$tag;

		return "<$tag$atributo>"; // TODO: verificar funcionamento
	}

	/** Monta a TAG de fechamento ou seja, </tag>
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 * @param boolean $grava informa se a string resultante deve ser retornada
	 */
	protected function fechaTag($grava=true) {
		$tag=array_pop($this->tags);
		if($grava)
		$this->xml.="</$tag>".NL;

		return "</$tag>"; // TODO: verificar funcionamento
	}

	/** Adiciona conteudo gerado entre TAG informada
	 * @author	giuliano
	 * @version	3.0 07-10-2009 09:47
	 * @param string $tag TAG propriamente dita
	 * @param string $conteudo Conteudo a ser abracado pela TAG
	 * @param int $tam Controla o tamanho da string
	 */
	protected function tag($tag,$conteudo,$tamMax=0,$grava=true) {

		$tag=trim($tag);
		if ($conteudo<>"") { // Cria a tag apenas se houver algum conteudo a ser acrescentado
			$tag_.=$this->abreTag($tag,'',$grava); // TODO: verificar funcionamento

			$tamCampo=strlen($conteudo);

			if(($tamMax>0 || $tamMax<>"") && ($tamCampo>$tamMax)) { // Verifica se o tamanho da string possue restriÃ§Ã£o de tamanho e faz ajuste
				if($grava)
				$this->xml.=substr($conteudo,0,$tamMax-1);
				$tag_.=substr($conteudo,0,$tamMax-1);
				$this->adicionaLog('aviso',"o conteudo da tag <$tag> foi reduzida de $tamCampo para $tam");
			}
			else {
				if($grava)
				$this->xml.=$conteudo;
				$tag_.=$conteudo;
			}
			if (count($this->tags)<3)
			if($grava)
			$this->xml.=NL;
			$tag_.=$this->fechaTag($grava); // TODO: verificar funcionamento
		}

		return $tag_;
	}

	/** Gera lote
	 * @author	giuliano
	 * @version	1.0 28-10-2009 09:47
	 * @return
	 */
	protected function geraLote() {
		return $this->obtemLote();
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
	protected  function obtemLog() {
		return $this->log;
	}

	/** Gera o arquivo log
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	protected function geraLog() {
		$this->salva($this->obtemCaminhoArquivoLog(),$this->obtemLog());
	}

	protected function ajustaArquivoXmlNome($nome) {
		$this->nomeArquivoXml=$nome;
	}

	protected function obtemArquivoXmlNome() {
		return $this->nomeArquivoXml;
	}

	/** Obtem caminho padrao para salvar o arquivo contendo o Xml gerado
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	protected function obtemCaminhoArquivoXml() {
		return $this->caminhoArquivoXml;
	}

	/** Obtem caminho padrao para salvar o arquivo contendo o log gerado
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	protected function obtemCaminhoArquivoLog() {
		return $this->caminhoArquivoLog;
	}

	/** Obtem versao do xml
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	protected function obtemVersion() {
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
	public function obtemXml() {
		return $this->xml;
	}

	/** Obtem quantidade de itens de cada nota
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	protected function obtemItemQtde() {
		return $this->qtdeItem;
	}

	/** Obtem quantidade de notas inclusas no arquivo xml gerado
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	protected function obtemNfQtde() {
		return $this->qtdeNf;
	}

	/** Obtem valor total dos itens
	 * @author	mateus
	 * @version	1.0 15-10-2009 09:47
	 */
	protected function obtemItemTotal() {
		return $this->valorTotalItem;
	}

	/** Gera chave. Metodo que auxilia o metodo gera o digito verificador
	 * @author	mateus
	 * @version	1.0 29-10-2009 09:47
	 * @return string $chaveComDigitoVerificador
	 */
	protected function geraChave() {
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

	/** Adiciona todas notas
	 * @author	mateus
	 * @version	1.0 29-10-2009 09:47
	 * @return string
	 */
	protected function adicionaNotas() {
		//$nItem=$this->obtemNotaQtde(); // obtem qtde atual da contagem de itens, que eigual a ordem do item atual
		$this->obtemNotas();
		while($this->adicionaNota()) {} // Adiciona item a item
	}

	/** Adiciona entidade ao XML, podendo ser "emit", "dest"
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 * @param string $tipo Tipo da Tag XML
	 * @param string $mtz Array contendo campos
	 * @return mixed $sai
	 */
	protected function adicionaEntidade($tipo) {

	}

	/** Est e metodo serah obrigatoriamente estendida no uso em producao
	 * Pois buscarah no banco de dados os campos desejados. A matriz contida aqui ï¿½ apenas um TESTE
	 * @author	giuliano
	 * @version	1.0 07-10-2009 09:47
	 * @return string $mtz Array contendo campos
	 */

	protected function obtemEntidade() {
		$mtz="";
		$mtz["cnpj"]="01234567";
		$mtz["razao_social"]="Empresa Teste Ltda.";
		return($mtz);
	}

	protected function adicionaEmitente() {
		$this->adicionaEntidade('emit');
	}

	/** Adiciona os itens a nf
	 * @author	mateus
	 * @version	1.0 13-10-2009 09:47
	 * @return string
	 */
	protected function adicionaItens() {
		$mtz=&$this->nota;
		$idNf=$mtz['id'];
		$this->obtemItens($idNf);
		$this->qtdeItem[$idNf]=0; // zera para cada nota
		while($this->adicionaItem()){
			$this->qtdeItem[$idNf]++;
		} // Adiciona item a item
	}

	/** Obtem total
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	protected function obtemNfTotal() {
		return $this->valorNfTotal;
	}

	/** Obtem total Ipi
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	protected function obtemNfTotalIpi() {
		return $this->valorNfTotalIpi;
	}

	/** Obtem total Icms
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	protected function obtemNfTotalIcms() {
		return $this->valorNfTotalIcms;
	}

	/** Obtem total Pis
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	protected function obtemNfTotalPis() {
		return $this->valorNfTotalPis;
	}

	/** Obtem total Iss
	 * @author	mateus
	 * @version	1.0 14-10-2009 09:47
	 * @return float
	 */
	protected function obtemNfTotalIss() {
		return $this->valorNfTotalIss;
	}

	/** Formata Cnpj retirando barras, pontos e traï¿½os
	 * @author	mateus
	 * @version	1.0
	 * @return string
	 */
	protected function formataCnpj($cnpj) {
		$cnpj=str_replace("-","",$cnpj);
		$cnpj=str_replace("/","",$cnpj);
		$cnpj=str_replace(".","",$cnpj);
		return $cnpj;
	}


	/** Adiciona assinatura da nota
	 * @author	mateus
	 * @version 19/11/2009 15:24:47
	 * @param   $tag
	 *
	 */
	protected function adicionaAssinatura($xml='',$tagName='',$tagId='') {
		return $this->assinatura->assinaXML($xml,$tagName,$tagId);
	}

	/** Gera digito verificador da chave de conectividades do numero da nota fiscal
	 * @author    Emerson Santana
	 * @version    1.0 27-10-2009 09:47
	 * @param string $chave com 44 sendo eles:
	 * chave: chave gerada previamente
	 * TODO: ver com  Giuliano se eh melhor recordset ou Json
	 */
	protected function geraDigitoVerificador($chave) {
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



	/** Salva conteudo XML em arquivo no disco
	 * @author	giuliano
	 * @version	1.0 07-10-2009 11:01
	 * @param string $file Nome do arquivo
	 */
	protected function salva($caminhoArquivo,$conteudo) {
		$arq=fopen($this->obtemCaminhoArquivoXml().$this->obtemArquivoXmlNome().'.xml',"w");
		//echo $this->obtemCaminhoArquivoXml().$this->obtemArquivoXmlNome().'.xml';exit;
		fwrite($arq,$conteudo);
		fclose($arq);
	}

	/** Aciona download no navegador do cliente
	 * @author	giuliano
	 * @version	1.0 07-10-2009 11:01
	 * @param string $file Nome do arquivo
	 */
	protected function baixa($file="/tmp/nf.xml", $name=false, $type='application/xml; charset=UTF-8', $down=true) {
		$file=$this->obtemCaminhoArquivoXml().$this->obtemArquivoXmlNome().'.xml';

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
}

?>
