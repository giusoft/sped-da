<?php
//CNPJ de TESTE: 11097137000122
//Senha: 123
Class gNFSe_SF
{
	//Identificação do usuário
	private $usuario;
	//Senha do usuário
	private $senha;
	//Parametros adicionais para emissão da Nota
	private $params = "";
	//Protocolo de recebimento da nota
	private $protocolo;
	//Numero do lote de envio
	private $numeroLote;
	//Numero da nota fiscal em nosso sistema
	private $numeroNota;
	//Hash gerado em MD5 com base no número do lote
	private $hashIdentificador;
	// XML da nota fiscal
	private $xml;
	//ID gerado na tabela nfse
	private $idNfse;
	//URL de envio da nota
	private $url = "http://201.49.29.18:8081/el-nfse/RpsServiceService?WSDL";
	// Caminho para o XDS de validação
	private $xsd_path = "lib/nfse-sf/el-nfse.xsd";
	// Dados do emissor
	public  $dadosEmissor = "";
	//Dados do tomador do serviço
	public  $dadosTomador ="";
	//Mensagens de erros
	public  $msgErros = "";
	//Se for true, indica que houve um erro algum erro no processamento da nota
	public  $erroStatus= false;
	//Itens da nota fiscal
	public $itens = "";

	function __construct($p="")
	{
		$this->usuario=trim($p['usuario'])<>"" ? trim($p['usuario']) : gVar("nfsesf.usuario");
		$this->senha=trim($p['senha'])<>"" ? trim($p['senha']) : gVar("nfsesf.senha");
	}

	function criaEnviaNFSeSF()
	{
		//$this->consultarLoteRpsEnvio();
		//$this->enviarLoteRpsEnvio();
		//$this->consultarLoteRpsEnvio("000000019273");
		//$this->consultarLoteRpsEnvio("000000019272");
		//$this->consultarSituacaoLoteRpsEnvio("000000019272");
		//exit;
		//Gera o XML
		$this->geraXml();
		echo "Gerou XML <BR>";
		//Valida o XML
		$this->validaXml();
		//Se validar, envia
		if(!$this->erroStatus)
		{
			//Autentica contribuindo e obtem o Hash
			$this->autenticarContribuinte();
			//Se autenticou, envia a NFSe
			if(!$this->erroStatus)
			{
				$this->salva();
				echo "Salvou...<BR>";
				echo "Autenticou. Protocolo: ".$this->hashIdentificador." <BR>";
				$this->enviarLoteRpsEnvio();
				echo "Enviou...";
				$this->consultarLoteRpsEnvio($this->protocolo);
			}
		}

	}

	function defineDadosEmissor($campos, $valor = "")
	{
		if (is_array($campos))
		{
			foreach ($campos as $campo => $valor) {
				if ($campo == "cnpj")
					$valor = $this->soNumeros($valor);
				$this->dadosEmissor[$campo] = $valor;
			}
		}
		else
		{
			$this->dadosEmissor[$campos] = $valor;
		}
	}

	function defineDadosTomador($campos, $valor = "")
	{
		if (is_array($campos))
		{
			foreach ($campos as $campo => $valor) {
				if ($campo == "cnpj")
					$valor = $this->soNumeros($valor);
				$this->dadosTomador[$campo] = $valor;
			}
		}
		else
		{
			$this->dadosTomador[$campos] = $valor;
		}
	}

	function defineParams($campos,$valor="")
	{
		if (is_array($campos))
		{
			foreach ($campos as $campo => $valor) {
				$this->params[$campo] = $valor;
			}
		}
		else
		{
			$this->params[$campos] = $valor;
		}
	}

	function defineItens($campos)
	{
//		echo "<pre>";
//		print_r($campos);exit;
		$item = "";
		foreach ($campos as $campo => $valor) {
			$item[$campo] = $valor;
		}
		$this->itens[] = $item;
	}

	function geraXml()
	{
		//Buscando dados da ultima nota emitida
		$usrId = $_SESSION['usrId'] > 0 ? $_SESSION['usrId'] : $_SESSION['usr_id'];
		$sql = "select * from nfse_numeros where ambiente=1 and id_empresa=" . $this->dadosEmissor["idEmpresa"]." and ano='".date("Y")."'";
		$rs=gQuery($sql);
		if ($rs->EOF)
		{
			$sql = "insert into nfse_numeros (id_empresa,ambiente,numero_lote,numero_nota,ano) values (" . $this->dadosEmissor["idEmpresa"] . ",1,1,1,'".date("Y")."')";
			gQuery($sql);

			$numeroLote = 1;
			$numeroNota = 1;
		}
		else
		{
			$numeroLote = intval($rs->fields['numero_lote']) + 1;
			$numeroNota = intval($rs->fields['numero_nota']) + 1;
			// Independente do resultado, sempre muda o número do lote
			$sql = "update nfse_numeros set numero_lote=" . $numeroLote . ", numero_nota=".$numeroNota." where ambiente=1 and id_empresa=" . $this->dadosEmissor['idEmpresa'];
			gQuery($sql);
		}
		$this->numeroNota=date("Y").str_pad($numeroNota, 7, "0", STR_PAD_LEFT);
		$this->numeroLote=date("Y").str_pad($numeroLote, 7, "0", STR_PAD_LEFT);

		$xmldoc = new DOMDocument();
		$xmldoc->preservWhiteSpace = false;
		$xmldoc->formatOutput = false;
		$root    = $xmldoc->createElement("LoteRps");
		$root->setAttribute("xmlns","http://www.el.com.br/nfse/xsd/el-nfse.xsd");
		$root->setAttribute("xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance");
		$root->setAttribute("xmlns:xsd","http://www.w3.org/2001/XMLSchema");
		$root->setAttribute("xsi:schemaLocation","http://www.el.com.br/nfse/xsd/el-nfse.xsd el-nfse.xsd");

		$hoje=date("Y-m-d");
		$agora=date("H:i:s");
		$hash=md5($this->numeroNota);
		//Id
		$id=$xmldoc->createElement("Id",$hash);//Hash do tipo FNV com comprimento de chave de 32 bits, que identifica unicamente um registo
		$root->appendChild($id);

		//Número do Lote
		$nrLote=$xmldoc->createElement("NumeroLote",$this->numeroLote);//Número sequencial, formado pelo ano com 04 (quatro) d?gitos e um n?mero sequencial com 11 posi??es ? Formato AAAANNNNNNNNNNN
		$root->appendChild($nrLote);

		//Quantidade de RPS
		$qtdRps=$xmldoc->createElement("QuantidadeRps",1);//Número inteiro no formato NNNNN
		$root->appendChild($qtdRps);

		//Identificação do prestador do serviço
		$IdentificacaoPrestador=$xmldoc->createElement("IdentificacaoPrestador");
		$cpfCnpj=$xmldoc->createElement("CpfCnpj",$this->dadosEmissor['cnpj']<>"" ? $this->soNumeros($this->dadosEmissor['cnpj']) : $this->soNumeros($this->dadosEmissor['cpf']));
		$IdcpfCnpj=$xmldoc->createElement("IndicacaoCpfCnpj",$this->dadosEmissor['cnpj']<>"" ? 2 : 1);
		$inscMunicipal=$xmldoc->createElement("InscricaoMunicipal",$this->dadosEmissor['inscricaoMunicipal']);
		$IdentificacaoPrestador->appendChild($cpfCnpj);
		$IdentificacaoPrestador->appendChild($IdcpfCnpj);
		$IdentificacaoPrestador->appendChild($inscMunicipal);
		$root->appendChild($IdentificacaoPrestador);

		//Lista de RPS - As tag Rps está dentro desta
		$listaRps=$xmldoc->createElement("ListaRps");

		// Rps - As tagas abaixo estão dentro desta
		$Rps=$xmldoc->createElement("Rps");
		$id=$xmldoc->createElement("Id",$hash); //Hash do tipo FNV com comprimento de chave de 32 bits, que identifica unicamente um registo (Foi utilizado MD5)
		$locPrestacao=$xmldoc->createElement("LocalPrestacao",1); // 1 = Fora do municipio; 2 = Dentro do municipio
		$issRetido=$xmldoc->createElement("IssRetido",1); //1-Normal; 2-Recolhido na Fonte
		$dataEmissao=$xmldoc->createElement("DataEmissao",$hoje."T".$agora);
		$Rps->appendChild($id);
		$Rps->appendChild($locPrestacao);
		$Rps->appendChild($issRetido);
		$Rps->appendChild($dataEmissao);

		//Identificacao do RPS
		$identificacaoRps=$xmldoc->createElement("IdentificacaoRps");
		$numero=$xmldoc->createElement("Numero",$this->numeroLote); //Número sequencial, formado pelo ano com 04 (quatro) dígitos e um número sequencial com 11 posições é Formato AAAANNNNNNNNNNN
		$serie=$xmldoc->createElement("Serie",1);// Serie do RPS
		$tipo=$xmldoc->createElement("Tipo",1); //Código de tipo de RPS - 1-RPS; 2-Nota Fiscal Conjugada (Mista); 3-Cupom
		$identificacaoRps->appendChild($numero);
		$identificacaoRps->appendChild($serie);
		$identificacaoRps->appendChild($tipo);
		$Rps->appendChild($identificacaoRps);

		//Dados do prestador
		$dadosPrestador=$xmldoc->createElement("DadosPrestador");
		$identificacaoPrestador=$xmldoc->createElement("IdentificacaoPrestador");
		$cpfCnpj=$xmldoc->createElement("CpfCnpj",$this->dadosEmissor['cnpj']<>""? $this->soNumeros($this->dadosEmissor['cnpj']) :$this->soNumeros($this->dadosEmissor['cpf']));
		$IdcpfCnpj=$xmldoc->createElement("IndicacaoCpfCnpj",$this->dadosEmissor['cnpj']<>"" ? 2 : 1);
		$inscMunicipal=$xmldoc->createElement("InscricaoMunicipal",$this->dadosEmissor['inscricaoMunicipal']);
		$identificacaoPrestador->appendChild($cpfCnpj);
		$identificacaoPrestador->appendChild($IdcpfCnpj);
		$identificacaoPrestador->appendChild($inscMunicipal);
		$dadosPrestador->appendChild($identificacaoPrestador);

		$razaoSocial=$xmldoc->createElement("RazaoSocial",$this->tiraAcentos($this->dadosEmissor['razaoSocial']));
		$dadosPrestador->appendChild($razaoSocial);
		$nomeFantasia=$xmldoc->createElement("NomeFantasia",$this->tiraAcentos($this->dadosEmissor['nome']));
		$dadosPrestador->appendChild($nomeFantasia);
		$incentivadorCultural=$xmldoc->createElement("IncentivadorCultural",$this->params['incentivadorCultural']);// 1-Sim;2-Nao
		$dadosPrestador->appendChild($incentivadorCultural);
		$optanteSimplesNacional=$xmldoc->createElement("OptanteSimplesNacional",$this->params['optanteSimplesNacional']);// 1-Sim;2-Nao
		$dadosPrestador->appendChild($optanteSimplesNacional);
		$naturezaOperacao=$xmldoc->createElement("NaturezaOperacao",1); //Código de natureza da operação. Consultar a Municipalidade
		$dadosPrestador->appendChild($naturezaOperacao);
		$regimeEspecialTributacao=$xmldoc->createElement("RegimeEspecialTributacao",0);//C?digo da identificação do regime especial de tributação. Consultar a Municipalidade
		$dadosPrestador->appendChild($regimeEspecialTributacao);

		$end=explode(" ",$this->dadosEmissor['endereco']);
		$endereco=$xmldoc->createElement("Endereco");
		$logradouroTipo=$xmldoc->createElement("LogradouroTipo",$this->tiraAcentos($end[0]));
		$endereco->appendChild($logradouroTipo);
		$logradouro=$xmldoc->createElement("Logradouro",$this->tiraAcentos($this->dadosEmissor['endereco']));
		$endereco->appendChild($logradouro);
		$enderecoNumero=$xmldoc->createElement("LogradouroNumero",$this->tiraAcentos($this->dadosEmissor['enderecoNumero']));
		$endereco->appendChild($enderecoNumero);
		$enderecoComplemento=$xmldoc->createElement("LogradouroComplemento",$this->tiraAcentos($this->dadosEmissor['enderecoComplemento']));
		$endereco->appendChild($enderecoComplemento);
		$bairro=$xmldoc->createElement("Bairro",$this->tiraAcentos($this->dadosEmissor['enderecoBairro']));
		$endereco->appendChild($bairro);
		$codigoMunIbge=$xmldoc->createElement("CodigoMunicipio",$this->soNumeros($this->dadosEmissor['enderecoIbgeMunicipio']));
		$endereco->appendChild($codigoMunIbge);
		$municipio=$xmldoc->createElement("Municipio",$this->tiraAcentos($this->dadosEmissor['enderecoMunicipio']));
		$endereco->appendChild($municipio);
		$uf=$xmldoc->createElement("Uf",$this->tiraAcentos($this->dadosEmissor['enderecoUf']));
		$endereco->appendChild($uf);
		$cep=$xmldoc->createElement("Cep",$this->soNumeros($this->dadosEmissor['enderecoCep']));
		$endereco->appendChild($cep);
		$dadosPrestador->appendChild($endereco);

		$contato=$xmldoc->createElement("Contato");
		$telefone=$xmldoc->createElement("Telefone",$this->soNumeros($this->dadosEmissor['telefone']));
		$contato->appendChild($telefone);
		$email=$xmldoc->createElement("Email",$this->dadosEmissor['email']);
		$contato->appendChild($email);
		$dadosPrestador->appendChild($contato);
		$Rps->appendChild($dadosPrestador);

		//Dados do tomador
		$dadosTomador=$xmldoc->createElement("DadosTomador");
		$identificacaoTomador=$xmldoc->createElement("IdentificacaoTomador");
		$cpfCnpj=$xmldoc->createElement("CpfCnpj",$this->dadosTomador['cnpj']<>""?$this->dadosTomador['cnpj']:$this->dadosTomador['cpf']);
		$identificacaoTomador->appendChild($cpfCnpj);
		$IdcpfCnpj=$xmldoc->createElement("IndicacaoCpfCnpj",$this->dadosTomador['cnpj']<>"" ? 2 : 1);
		$identificacaoTomador->appendChild($IdcpfCnpj);
		$inscMunicipal=$xmldoc->createElement("InscricaoMunicipal",$this->dadosTomador['inscricaoMunicipal']);
		$identificacaoTomador->appendChild($inscMunicipal);
		$dadosTomador->appendChild($identificacaoTomador);

		$razaoSocial=$xmldoc->createElement("RazaoSocial",$this->tiraAcentos($this->dadosTomador['razaoSocial']));
		$dadosTomador->appendChild($razaoSocial);
		$nomeFantasia=$xmldoc->createElement("NomeFantasia",$this->tiraAcentos($this->dadosTomador['nome']));
		$dadosTomador->appendChild($nomeFantasia);

		$end=explode(" ",$this->dadosEmissor['endereco']);
		$endereco=$xmldoc->createElement("Endereco");
		$logradouroTipo=$xmldoc->createElement("LogradouroTipo",$this->tiraAcentos($end[0]));
		$endereco->appendChild($logradouroTipo);
		$logradouro=$xmldoc->createElement("Logradouro",$this->tiraAcentos($this->dadosTomador['endereco']));
		$endereco->appendChild($logradouro);
		$enderecoNumero=$xmldoc->createElement("LogradouroNumero",$this->tiraAcentos($this->dadosTomador['enderecoNumero']));
		$endereco->appendChild($enderecoNumero);
		$enderecoComplemento=$xmldoc->createElement("LogradouroComplemento",$this->tiraAcentos($this->dadosTomador['enderecoComplemento']));
		$endereco->appendChild($enderecoComplemento);
		$bairro=$xmldoc->createElement("Bairro",$this->tiraAcentos($this->dadosTomador['enderecoBairro']));
		$endereco->appendChild($bairro);
		$codigoMunIbge=$xmldoc->createElement("CodigoMunicipio",$this->soNumeros($this->dadosTomador['enderecoIbgeMunicipio']));
		$endereco->appendChild($codigoMunIbge);
		$municipio=$xmldoc->createElement("Municipio",$this->tiraAcentos($this->dadosTomador['enderecoMunicipio']));
		$endereco->appendChild($municipio);
		$uf=$xmldoc->createElement("Uf",$this->tiraAcentos($this->dadosTomador['enderecoUf']));
		$endereco->appendChild($uf);
		$cep=$xmldoc->createElement("Cep",$this->soNumeros($this->dadosTomador['enderecoCep']));
		$endereco->appendChild($cep);
		$dadosTomador->appendChild($endereco);

		$contato=$xmldoc->createElement("Contato");
		$telefone=$xmldoc->createElement("Telefone",$this->soNumeros($this->dadosTomador['telefone']));
		$contato->appendChild($telefone);
		$email=$xmldoc->createElement("Email",$this->dadosTomador['email']);
		$contato->appendChild($email);
		$dadosTomador->appendChild($contato);
		$Rps->appendChild($dadosTomador);

		$totalValor=$totalIss=$totalDesconto=0;

		//Serviços
		$servicos=$xmldoc->createElement("Servicos");
		foreach($this->itens as $item)
		{

			$servico=$xmldoc->createElement("Servico");
			$codigoCnae=$xmldoc->createElement("CodigoCnae",$this->soNumeros($item['codigoCnae']));
			$servico->appendChild($codigoCnae);
			$codigoServico116=$xmldoc->createElement("CodigoServico116",$this->soNumeros($item['codigo']));
			$servico->appendChild($codigoServico116);
			$codigoServicoMunicipal=$xmldoc->createElement("CodigoServicoMunicipal",$this->soNumeros($item['codigo']));
			$servico->appendChild($codigoServicoMunicipal);
			$quantidade=$xmldoc->createElement("Quantidade",intval($item['quantidade']));
			$servico->appendChild($quantidade);
			$unidade=$xmldoc->createElement("Unidade",$item['unidade']<>"" ? strtoupper(substr($item['unidade'],0,2)) : "UN");
			$servico->appendChild($unidade);
			$descricao=$xmldoc->createElement("Descricao",$this->tiraAcentos($item['descricao']));
			$servico->appendChild($descricao);
			$aliquota=$xmldoc->createElement("Aliquota",number_format( ($item['aliquotaIss'] / 100) , 2, ".", ""));
			$servico->appendChild($aliquota);
			$valorServico=$xmldoc->createElement("ValorServico",number_format( ($item['valor']) , 2, ".", ""));
			$servico->appendChild($valorServico);
			$valorIssqn=$xmldoc->createElement("ValorIssqn",number_format( ($item['issQn']) , 2, ".", ""));
			$servico->appendChild($valorIssqn);
			$valorDesconto=$xmldoc->createElement("ValorDesconto",number_format( ($item['desconto']) , 2, ".", ""));
			$servico->appendChild($valorDesconto);
			$numeroAlvara=$xmldoc->createElement("NumeroAlvara",trim($this->tiraAcentos($item['alvara'])));
			$servico->appendChild($numeroAlvara);
			$servicos->appendChild($servico);

			$totalDesconto+=number_format( ($item['desconto']) , 2, ".", "");
			$totalIss+=number_format( ($item['issQn']) , 2, ".", "");
			$totalValor+=number_format( ($item['valor']) , 2, ".", "");

		}
		$Rps->appendChild($servicos);

		//Valores
		$valores=$xmldoc->createElement("Valores");
		$valorServicos=$xmldoc->createElement("ValorServicos",number_format($totalValor , 2, ".", ""));
		$valores->appendChild($valorServicos);
		$valorDeducoes=$xmldoc->createElement("ValorDeducoes",number_format($totalDesconto , 2, ".", ""));
		$valores->appendChild($valorDeducoes);
		$valorPis=$xmldoc->createElement("ValorPis",0.00);
		$valores->appendChild($valorPis);
		$valorCofins=$xmldoc->createElement("ValorCofins",0.00);
		$valores->appendChild($valorCofins);
		$valorInss=$xmldoc->createElement("ValorInss",0.00);
		$valores->appendChild($valorInss);
		$valorIr=$xmldoc->createElement("ValorIr",0.00);
		$valores->appendChild($valorIr);
		$valorCsll=$xmldoc->createElement("ValorCsll",0.00);
		$valores->appendChild($valorCsll);
		$valorIss=$xmldoc->createElement("ValorIss",number_format($totalIss , 2, ".", ""));
		$valores->appendChild($valorIss);
		$valorOutrasRetencoes=$xmldoc->createElement("ValorOutrasRetencoes",0.00);
		$valores->appendChild($valorOutrasRetencoes);
		$valorLiquidoNfse=$xmldoc->createElement("ValorLiquidoNfse",0.00);
		$valores->appendChild($valorLiquidoNfse);
		$valorIssRetido=$xmldoc->createElement("ValorIssRetido",0.00);
		$valores->appendChild($valorIssRetido);
		$Rps->appendChild($valores);

		//Observações
		$obs=$xmldoc->createElement("Observacao",trim($this->tiraAcentos($this->params['observacao'])));
		$Rps->appendChild($obs);

		//Status
		$status=$xmldoc->createElement("Status",1);
		$Rps->appendChild($status);

		$listaRps->appendChild($Rps);

		$root->appendChild($listaRps);
		$xmldoc->appendChild($root);

		//Removendo tags vazias
		$xpath = new DOMXPath($xmldoc);
		foreach( $xpath->query('//*[not(node())]') as $node )
			$node->parentNode->removeChild($node);

		$docxml=$xmldoc->saveXML();

		$this->xml=trim($docxml);
	}

	private function validaXml()
	{
		libxml_use_internal_errors(true);
		$objDom = new DomDocument();
		$objDom->loadXML($this->xml);

		try
		{
			if (!$objDom->schemaValidate($this->xsd_path))
			{
				// Se n?o foi poss?vel validar, você pode capturar  todos os erros em um array
				$erros = libxml_get_errors();

				$erroMsg = "XML inválido";
				// Cada elemento do array $arrayAllErrors ser? um objeto do tipo LibXmlError
				foreach ($erros as $intError)
				{
					switch ($intError->level)
					{
						case LIBXML_ERR_WARNING:
							$this->msgErros[]= "Atenção $intError->code: ";
							break;
						case LIBXML_ERR_ERROR:
							$this->msgErros[]= "Erro (".$intError->code.")"." : ".$intError->message;
							break;
						case LIBXML_ERR_FATAL:
							$this->msgErros[]= "Erro Fatal ($intError->code) : ".$intError->message;
							break;
					}
				}
				$this->erroStatus=true;
			}
			else // XML valido!
				return true;
		}
		catch (Exception $e)
		{
			$this->erroStatus=true;
			$this->msgErros[]=$e->getMessage();
			return false;
		}
	}

	function salva()
	{
		$dataHora=date("Y-m-d H:i:s");

		$dados=array();
		$dados['sistema']=$this->params['sistema'];
		$dados['situacao']="Validada";
		$dados['data']=$dataHora;
		$dados['id_os']=intval($this->params['id_os']);
		$dados['id_nota']=intval($this->params['id_nota']);
		$dados['numero']=$this->numeroNota;
		$dados['lote']=$this->numeroLote;
		$dados['id_cliente']=$this->dadosEmissor['idEmpresa'];
		$dados['id_pessoa']=$this->dadosTomador['idCliente'];
		$dados['xml']=addslashes($this->xml);
		gQuery("INSERT INTO nfse (".implode(",",array_keys($dados)).") VALUES ('".implode("','",array_values($dados))."')");

		$sql = "select id from nfse where numero='".$this->numeroNota."' and lote='".$this->numeroLote."' and data='$dataHora'";
		$rs = gQuery($sql);

		$this->idNfse=$rs->fields['id'];
	}

	function autenticarContribuinte()
	{
		/*
		<autenticarContribuinte xmlns="http://des36.el.com.br:8080/el-issonline/">
			<identificacaoPrestador xmlns="">[string?]</identificacaoPrestador>
			<senha xmlns="">[string?]</senha>
		</autenticarContribuinte>
		 */
		$xmldoc = new DOMDocument();
		$xmldoc->preservWhiteSpace = false;
		$xmldoc->formatOutput = false;

		$root    = $xmldoc->createElement("autenticarContribuinte");
		$root->setAttribute("xmlns","http://des36.el.com.br:8080/el-issonline/");

		$usuario = $xmldoc->createElement("identificacaoPrestador",$this->usuario);
		$usuario->setAttribute("xmlns","");
		$senha   = $xmldoc->createElement("senha",$this->senha);
		$senha->setAttribute("xmlns","");
		$root->appendChild($usuario);
		$root->appendChild($senha);
		$xmldoc->appendChild($root);
		$docxml = $xmldoc->saveXML();

		try
		{
			$rtn = $this->sendSoap($docxml);
			if($rtn <> "")
			{
				$dom = new DOMDocument('1.0', 'utf-8');
				$dom->preserveWhiteSpace = false;
				$dom->formatOutput = false;
				$dom->loadXML($rtn, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
				$hash=$dom->getElementsByTagName('return')->item(0)->nodeValue;
				if($hash<>"")
					$this->hashIdentificador=$hash;
				else
				{
					$this->erroStatus=true;
					$this->msgErros[] = "Falha na autenticação. O SEFAZ não retornou o protocolo.";
				}
			}
			else
			{
				$this->erroStatus=true;
				$this->msgErros[] = "Não houve retorno da mensagem enviada";
			}

		}
		catch(Exception $e)
		{
			$this->erroStatus=true;
			$this->msgErros[]=$e->getMessage();
		}
	}

	function finalizarSessao()
	{
		/*
			<finalizarSessao xmlns="http://des36.el.com.br:8080/el-issonline/">
				<hashIdentificador xmlns="">[string?]</hashIdentificador>
			</finalizarSessao>
		 */

		$xmldoc = new DOMDocument();
		$xmldoc->preservWhiteSpace = false;
		$xmldoc->formatOutput = false;
		$root    = $xmldoc->createElement("finalizarSessao");
		$root->setAttribute("xmlns","http://des36.el.com.br:8080/el-issonline/");
		$hash = $xmldoc->createElement("hashIdentificador",$this->hashIdentificador);
		$hash->setAttribute("xmlns","");
		$root->appendChild($hash);
		$xmldoc->appendChild($root);
		$docxml = $xmldoc->saveXML();

		try
		{
			$this->sendSoap($docxml);
		}
		catch(Exception $e)
		{
			$this->erroStatus=true;
			$this->msgErros[]=$e->getMessage();
		}

	}

	function enviarLoteRpsEnvio()
	{
		/*
		 <EnviarLoteRpsEnvio xmlns="http://des36.el.com.br:8080/el-issonline/">
		    <identificacaoPrestador xmlns="">[string?]</identificacaoPrestador>
		    <hashIdentificador xmlns="">[string?]</hashIdentificador>
		    <arquivo xmlns="">[string?]</arquivo>
		 </EnviarLoteRpsEnvio>
		 */

		//echo " ==>> <TEXTAREA>".$this->xml."</TEXTAREA>";exit;
		$xmldoc = new DOMDocument();
		$xmldoc->preservWhiteSpace = false;
		$xmldoc->formatOutput = false;
		$root= $xmldoc->createElement("EnviarLoteRpsEnvio");
		$root->setAttribute("xmlns","http://des36.el.com.br:8080/el-issonline/");
		$identificacaoPrestador = $xmldoc->createElement("identificacaoPrestador",$this->usuario);
		$identificacaoPrestador->setAttribute("xmlns","");
		$hashIdentificador = $xmldoc->createElement("hashIdentificador",$this->hashIdentificador);
		$hashIdentificador->setAttribute("xmlns","");
		$arquivo = $xmldoc->createElement("arquivo", $this->xml);
		$arquivo->setAttribute("xmlns","");

		$root->appendChild($identificacaoPrestador);
		$root->appendChild($hashIdentificador);
		$root->appendChild($arquivo);
		$xmldoc->appendChild($root);
		$docxml = $xmldoc->saveXML();

		try
		{
			$xml=$this->sendSoap($docxml,true);
			if($xml<>"")
			{
				$arr=XML2Array::createArray($xml);
				$retorno=$arr["S:Envelope"]["S:Body"]["ns2:EnviarLoteRpsEnvioResponse"]["return"];
				$sql="UPDATE nfse SET situacao='Submetida',data_recibo='".substr($retorno["dataRecebimento"],0,19)."', protocolo='".$retorno['numeroProtocolo']."' WHERE id=".$this->idNfse;
				gQuery($sql);
				$this->protocolo=$retorno['numeroProtocolo'];
			}
			else
			{
				$this->erroStatus=true;
				$this->msgErros[]="Não retornou protocolo";
			}
		}
		catch(Exception $e)
		{
			$this->erroStatus=true;
			$this->msgErros[]=$e->getMessage();
		}

	}

	function consultarSituacaoLoteRpsEnvio($protocolo="")
	{
		/*
			 <ConsultarSituacaoLoteRpsEnvio xmlns="http://des36.el.com.br:8080/el-issonline/">
	            <identificacaoPrestador xmlns="">[string?]</identificacaoPrestador>
	            <numeroProtocolo xmlns="">[string?]</numeroProtocolo>
	        </ConsultarSituacaoLoteRpsEnvio>
		 */
		$protocolo=$protocolo<>"" ? $protocolo : $this->protocolo ;
		$xmldoc = new DOMDocument();
		$xmldoc->preserveWhiteSpace=false;
		$xmldoc->formatOutput=false;

		$root = $xmldoc->createElement("ConsultarSituacaoLoteRpsEnvio");
		$root->setAttribute("xmlns","http://des36.el.com.br:8080/el-issonline/");
		$identificacaoPrestador=$xmldoc->createElement("identificacaoPrestador",$this->usuario);
		$identificacaoPrestador->setAttribute("xmlns","");
		$root->appendChild($identificacaoPrestador);
		$numeroProtocolo=$xmldoc->createElement("numeroProtocolo",$protocolo);
		$numeroProtocolo->setAttribute("xmlns","");
		$root->appendChild($numeroProtocolo);
		$xmldoc->appendChild($root);
		$docxml = $xmldoc->saveXML();

		try
		{
			$xml=$this->sendSoap($docxml);
			$arr=XML2Array::createArray($xml);
			$retorno=$arr["S:Envelope"]["S:Body"]["ns2:ConsultarSituacaoLoteRpsEnvioResponse"]["return"];
//			echo "<pre>";
//			var_dump($retorno);
////			exit;
			return $retorno;
		}
		catch(Exception $e)
		{
			$this->erroStatus=true;
			$this->msgErros[]=$e->getMessage();
		}

	}

	function consultarLoteRpsEnvio($protocolo="")
	{
		/*
		<ConsultarLoteRpsEnvio xmlns="http://des36.el.com.br:8080/el-issonline/">
			<identificacaoPrestador xmlns="">[string?]</identificacaoPrestador>
		    <numeroProtocolo xmlns="">[string?]</numeroProtocolo>
		</ConsultarLoteRpsEnvio>
		 */
		$protocolo= $protocolo<>"" ? $protocolo : $this->protocolo ;
		$this->protocolo=$protocolo;
		$xmldoc = new DOMDocument();
		$xmldoc->preserveWhiteSpace=false;
		$xmldoc->formatOutput=false;

		$root = $xmldoc->createElement("ConsultarLoteRpsEnvio");
		$root->setAttribute("xmlns","http://des36.el.com.br:8080/el-issonline/");
		$identificacaoPrestador=$xmldoc->createElement("identificacaoPrestador",$this->usuario);
		$identificacaoPrestador->setAttribute("xmlns","");
		$root->appendChild($identificacaoPrestador);
		$numeroProtocolo=$xmldoc->createElement("numeroProtocolo",$protocolo);
		$numeroProtocolo->setAttribute("xmlns","");
		$root->appendChild($numeroProtocolo);
		$xmldoc->appendChild($root);
		$docxml = $xmldoc->saveXML();

		try
		{
			$xml=$this->sendSoap($docxml);
			$arr=XML2Array::createArray($xml);
			$retorno=$arr["S:Envelope"]["S:Body"]["ns2:ConsultarLoteRpsEnvioResponse"]["return"];
//
			if(isset($retorno['notasFiscais']))//Nota fiscal aprovada
			{
				$sql="UPDATE nfse SET numero_nfse='".$retorno['notasFiscais']['numero']."',situacao='Aprovada' WHERE lote='".$retorno['notasFiscais']['rpsNumero']."'";
				gQuery($sql);
				return true;
			}
			elseif(substr($retorno['mensagens'],0,4) == "EL68")
			{
				$this->consultarLoteRpsEnvio();
			}
			else
			{
				//echo "TESTE2";exit;
				$this->erroStatus=true;
				foreach($retorno['mensagens'] as $key => $value)
				{
					if(trim($value)<>"")
					$this->msgErros[]=$this->tiraAcentos(mb_convert_encoding(trim($value), 'ISO-8859-1', 'UTF-8'));
				}
				if($this->numeroLote=="")
				{
					$buscaLote=$this->consultarSituacaoLoteRpsEnvio($protocolo);
					$this->numeroLote=$buscaLote['numeroLote'];
				}
				if($this->numeroLote<>"")
				{
					$sql="UPDATE nfse SET mensagens='".implode("<BR>",$this->msgErros)."', situacao='Reprovada' WHERE lote='".$this->numeroLote."'";
					gQuery($sql);
				}
			}

		}
		catch(Exception $e)
		{
			$this->erroStatus=true;
			$this->msgErros[]=$e->getMessage();
		}

	}

	function listaServicosMunicipaisPrestador()
	{
		/*
		 <ListarServicosMunicipaisPrestador xmlns="http://des36.el.com.br:8080/el-issonline/">
		    <identificacaoPrestador xmlns="">[string?]</identificacaoPrestador>
		 </ListarServicosMunicipaisPrestador>
		 */
		$xmldoc = new DOMDocument();
		$xmldoc->preservWhiteSpace = false;
		$xmldoc->formatOutput = false;
		$root= $xmldoc->createElement("ListarServicosMunicipaisPrestador");
		$root->setAttribute("xmlns","http://des36.el.com.br:8080/el-issonline/");
		$identificacaoPrestador=$xmldoc->createElement("identificacaoPrestador",$this->usuario);
		$identificacaoPrestador->setAttribute("xmlns","");
		$root->appendChild($identificacaoPrestador);
		$xmldoc->appendChild($root);
		$docxml = $xmldoc->saveXML();

		try
		{
			$xml=$this->sendSoap($docxml);
			$arr=XML2Array::createArray($xml);
			$items =$arr["S:Envelope"]["S:Body"]["ns2:ListarServicosMunicipaisPrestadorResponse"]["return"];


			function limpaString(&$arr)
			{
				$arr["denominacao"]=gNFSe_SF::tiraAcentos(mb_convert_encoding($arr["denominacao"], 'ISO-8859-1', 'UTF-8'));
			}
			array_walk($items,'limpaString');

			return $items;

		}
		catch(Exception $e)
		{
			$this->erroStatus=true;
			$this->msgErros[]=$e->getMessage();
		}


	}

	function cancelarNfseEnvio()
	{
		/*
		 <CancelarNfseEnvio xmlns="http://des36.el.com.br:8080/el-issonline/">
			<identificacaoPrestador xmlns="">[string?]</identificacaoPrestador>
		    <numeroNfse xmlns="">[string?]</numeroNfse>
		 </CancelarNfseEnvio>
		 */


	}

	function sendSoap($dados,$exit=false)
	{
		$dados=str_replace(array('<?xml version="1.0" encoding="UTF-8"?>','<?xml version="1.0"?>','\n','\r','<?xml version="4"?>'),'',$dados);
		$data = '';
		$data .= '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">';
		$data .= '<Body>';
		$data .= trim($dados);
		$data .= '</Body>';
		$data .= '</Envelope>';
		$data=preg_replace('/\n/',' ',$data);
		$data=trim(preg_replace('/\t+/', '', $data));
		//$tamanho = strlen($data);
		//echo "Enviado...<BR><textarea>$data</textarea>";//exit;
		//$tamanho = strlen($data);
		//$namespace="http://201.49.29.18:8081/el-nfse/RpsService";
		//$data='<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/"><Body><autenticarContribuinte xmlns="http://des36.el.com.br:8080/el-issonline/"><identificacaoPrestador xmlns="">11097137000122</identificacaoPrestador><senha xmlns="">123</senha></autenticarContribuinte></Body></Envelope>';
		//if($exit){echo "<BR><BR><textarea>$data</textarea>";}
		$tamanho = strlen($data);
		$parametros = array(
			'Content-Type: text/xml',
			"Cache-Control: no-cache",
			"Pragma: no-cache",
			'SOAPAction: run',
			"Content-length: $tamanho");
		$oCurl = curl_init();
		curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($oCurl, CURLOPT_URL, $this->url.'');
		//curl_setopt($oCurl, CURLOPT_PORT, 443);
		curl_setopt($oCurl, CURLOPT_VERBOSE, 1);
		curl_setopt($oCurl, CURLOPT_HEADER, 1);
		curl_setopt($oCurl, CURLOPT_POST, 1);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_HTTPHEADER, $parametros);
		$xml = curl_exec($oCurl);
		curl_close($oCurl);
		//echo "<BR>Retorno...<BR><textarea>$xml</textarea><br>";
		$lenN = strlen($xml);
		$posX = stripos($xml, "<");
		if ($posX !== false) {
			$xml = substr($xml, $posX, $lenN-$posX);
		} else {
			$xml = '';
		}
		return $xml;
	}

	function soNumeros($var)
	{
		return(preg_replace('/[^0-9]+/i ', '', $var));
	}

	function tiraAcentos($t)
	{
		global $gPathLib;
		$antes = html_entity_decode($t);
		if ($gPathLib <> "") {
			$pa = array("a", "e", "i", "o", "u", "o", "o", "a", "e");
			$de = array("á", "é", "í", "ó", "ú", "°", "º", "ª", "&");
			$t = str_replace($de, $pa, $t);
			$de = array("à", "è", "ì", "ò", "ù");
			$t = str_replace($de, $pa, $t);
			$de = array("â", "ê", "î", "ô", "û");
			$t = str_replace($de, $pa, $t);
			$t = str_replace("ã", "a", $t);
			$t = str_replace("õ", "o", $t);
			$t = str_replace("ç", "c", $t);
			$t = str_replace("Ç", "C", $t);
			$t = autoencode($t);
			$pa = array("A", "E", "I", "O", "U");
			$de = array("Á", "É", "Í", "Ó", "Ú");
			$t = str_replace($de, $pa, $t);
			$de = array("À", "È", "Ì", "Ò", "Ù");
			$t = str_replace($de, $pa, $t);
			$de = array("Â", "Ê", "Î", "Ô", "Û");
			$t = str_replace($de, $pa, $t);
			$de = array("Ã", "Õ", "Ç");
			$pa = array("A", "O", "C");
			$t = str_replace($de, $pa, $t);
			$de = array("");
			$pa = array("E");
			$t = str_replace($de, $pa, $t);
		} else {
			$t = strtr($t, utf8_decode("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&"), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
			$t = strtr($t, utf8_encode("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&"), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
			$t = strtr($t, "áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&", "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
		}
		$t = iconv('ISO-8859-1', 'ASCII//TRANSLIT//IGNORE', $t);
		return $t;
	}
}


/**
 * XML2Array: A class to convert XML to array in PHP
 * It returns the array which can be converted back to XML using the Array2XML script
 * It takes an XML string or a DOMDocument object as an input.
 *
 * See Array2XML: http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes
 *
 * Author : Lalit Patel
 * Website: http://www.lalit.org/lab/convert-xml-to-array-in-php-xml2array
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 * Version: 0.1 (07 Dec 2011)
 * Version: 0.2 (04 Mar 2012)
 * 			Fixed typo 'DomDocument' to 'DOMDocument'
 *
 * Usage:
 *       $array = XML2Array::createArray($xml);
 */

class XML2Array {

	private static $xml = null;
	private static $encoding = 'UTF-8';

	/**
	 * Initialize the root XML node [optional]
	 * @param $version
	 * @param $encoding
	 * @param $format_output
	 */
	public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true) {
		self::$xml = new DOMDocument($version, $encoding);
		self::$xml->formatOutput = $format_output;
		self::$encoding = $encoding;
	}

	/**
	 * Convert an XML to Array
	 * @param string $node_name - name of the root node to be converted
	 * @param array $arr - aray to be converterd
	 * @return DOMDocument
	 */
	public static function &createArray($input_xml) {
		$xml = self::getXMLRoot();
		if(is_string($input_xml)) {
			$parsed = $xml->loadXML($input_xml);
			if(!$parsed) {
				throw new Exception('[XML2Array] Error parsing the XML string.');
			}
		} else {
			if(get_class($input_xml) != 'DOMDocument') {
				throw new Exception('[XML2Array] The input XML object should be of type: DOMDocument.');
			}
			$xml = self::$xml = $input_xml;
		}
		$array[$xml->documentElement->tagName] = self::convert($xml->documentElement);
		self::$xml = null;    // clear the xml node in the class for 2nd time use.
		return $array;
	}

	/**
	 * Convert an Array to XML
	 * @param mixed $node - XML as a string or as an object of DOMDocument
	 * @return mixed
	 */
	private static function &convert($node) {
		$output = array();

		switch ($node->nodeType) {
			case XML_CDATA_SECTION_NODE:
				$output['@cdata'] = trim($node->textContent);
				break;

			case XML_TEXT_NODE:
				$output = trim($node->textContent);
				break;

			case XML_ELEMENT_NODE:

				// for each child node, call the covert function recursively
				for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
					$child = $node->childNodes->item($i);
					$v = self::convert($child);
					if(isset($child->tagName)) {
						$t = $child->tagName;

						// assume more nodes of same kind are coming
						if(!isset($output[$t])) {
							$output[$t] = array();
						}
						$output[$t][] = $v;
					} else {
						//check if it is not an empty text node
						if($v !== '') {
							$output = $v;
						}
					}
				}

				if(is_array($output)) {
					// if only one node of its kind, assign it directly instead if array($value);
					foreach ($output as $t => $v) {
						if(is_array($v) && count($v)==1) {
							$output[$t] = $v[0];
						}
					}
					if(empty($output)) {
						//for empty nodes
						$output = '';
					}
				}

				// loop through the attributes and collect them
				if($node->attributes->length) {
					$a = array();
					foreach($node->attributes as $attrName => $attrNode) {
						$a[$attrName] = (string) $attrNode->value;
					}
					// if its an leaf node, store the value in @value instead of directly storing it.
					if(!is_array($output)) {
						$output = array('@value' => $output);
					}
					$output['@attributes'] = $a;
				}
				break;
		}
		return $output;
	}

	/*
	 * Get the root XML node, if there isn't one, create it.
	 */
	private static function getXMLRoot(){
		if(empty(self::$xml)) {
			self::init();
		}
		return self::$xml;
	}

	function encodeToUtf8( $s ) //encode if necessary
	{
		//if (!mb_check_encoding($s,'UTF-8'))
		if ( !check_utf8( $s ) )
			$s = utf8_encode( $s );
		return $s;
	}
}
?>
