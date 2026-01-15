<?
/**
 * [$gPathLib Variável global utilizada pelo PhPMailer, para localizar o diretório]
 * @var string
 */
$gPathLib = "/var/www/html/gfw/4.0/inc/lib/";

function de($var,$var2=""){
	echo "<pre>";
	if($var2<>"")
		var_dump($var,$var2);
	else
		var_dump($var);
	exit;
}
//email-ssl.com.br:993
//usuario: jorge@giusoft.com.br
//senha: minha senha
//seguraca tls/ssl ou somente ssl
/**
 * Classe para registro de boletos online
 * Envia boletos indenpendente da aplicação, necessário criação da tabela fin_boletos
 *
 * CREATE TABLE fin_boletos(
	id BIGINT(20) PRIMARY KEY AUTO_INCREMENT,
	data DATETIME,
	id_parcela BIGINT(20) DEFAULT 0,
	id_pessoas BIGINT(20) DEFAULT 0,
	id_pessoas_cliente BIGINT(20) DEFAULT 0,
	numero VARCHAR(20),
	codigo_barras VARCHAR(100),
	linha_digitavel VARCHAR(100),
	valor DECIMAL (20,2) DEFAULT 0.00,
	data_vencimento DATE DEFAULT '0000-00-00',
	data_emissao DATE DEFAULT '0000-00-00',
	desconto DECIMAL (20,2) DEFAULT 0.00,
	json TEXT
	);
 */

class BradescoOnline{

	/**
	 * Dados de conexão com o banco
	 */
	public $BD = array("host" => "127.0.0.1","usuario" => "web","senha" => "web","bd" => "gadmin");

	/**
	 * Conexão com o banco de dados
	 */
	public $conn;

	/**
	 * Erros encontrados durante o processamento
	 */
	public $erros="";

	/**
	 * Configuração do ambiente
	 */
	public $ambiente = "homologacao";

	/**
	 * URL para envio da requisição
	 */
	private $url = array("homologacao" => "https://cobranca.bradesconetempresa.b.br/ibpjregistrotitulows/registrotitulohomologacao","producao" => "https://cobranca.bradesconetempresa.b.br/ibpjregistrotitulows/registrotitulo");

	/**
	 * Informações do certificado digital A1
	 */
	private $dadosCertificado = array("pathPfx" => "","password"=>"","priKey" => "","pubKey"=>"","certKey"=>"");

	/**
	 * Array com os dados do beneficiário da conta
	 */
	private $dadosBeneficiario = array();

	/**
	 * Json a ser enviado para o WebService
	 */
	public $json = "";

	/**
	 * Array contendo retorno do registro
	 */
	public $boleto = array();


	/**
	 * [$layoutboleto Contem os dados para gerar o layout do boleto]
	 * @var array
	 */
	public $layoutboleto = array();
	
	
	public function __construct($dadosBeneficiario,$dadosCertificado,$db=""){
		$this->conn = new PDO('mysql:host='.$this->BD['host'].';dbname='.$this->BD['bd'].';', $this->BD['usuario'], $this->BD['senha']);
		$ok=true;
		$this->dadosBeneficiario = $dadosBeneficiario;
		if(!is_array($dadosBeneficiario))
			$this->erros[]="Dados do beneficário inválidos.";
		if(!is_array($dadosCertificado))
			$this->erros[]="Dados do certificado inválidos.";
		if(!file_exists($dadosCertificado['pathPfx']))
			$this->erros[]="Arquivo de certificado não encontrado.";
		if(!is_array($this->erros)){
			//Valida certificado
			$this->validaCertificado($dadosCertificado);
			if(is_array($this->erros))
				$ok=false;
		}
		else
			$ok = false;

		if(is_array($bd)){
			$this->BD['host'] = $db['host'];
			$this->BD['usuario'] = $db['usuario'];
			$this->BD['senha'] = $db['senha'];
			$this->BD['bd'] = $db['bd'];
		}elseif(function_exists("gVar")){
			$this->BD['host'] = gVar("database.url");
			$this->BD['usuario'] = gVar("database.user");
			$this->BD['senha'] = gVar("database.password");
			$this->BD['bd'] = gVar("database.name");
		}
		
		return $ok;
	}

	/**
	 * validaCertificado 
	 * Valida o certificado digitial informado
	 * @param  Array $dadosCertificado
	 */
	public function validaCertificado($dadosCertificado)
	{
		//carrega o certificado em um string
		$pfxContent = file_get_contents($dadosCertificado['pathPfx']);
		//carrega os certificados e chaves para um array denominado $x509certdata
		if (!openssl_pkcs12_read($pfxContent, $x509certdata, $dadosCertificado['password'])) {
			$this->erros[]= "O certificado não pode ser lido!! Provavelmente corrompido ou com formato inválido!!";
		}
		$pathToFile = dirname($dadosCertificado['pathPfx']);
		$nameFile = basename($dadosCertificado['pathPfx']);
		$flagNovo = false;
		$pubKey = $pathToFile."/".str_replace(".pfx", "_pubKey.pem", $nameFile);
		$priKey = $pathToFile."/".str_replace(".pfx", "_priKey.pem", $nameFile);
		$certKey = $pathToFile."/".str_replace(".pfx", "_certKey.pem", $nameFile);
		$this->dadosCertificado['priKey']= $priKey;
		$this->dadosCertificado['pubKey']= $pubKey;
		$this->dadosCertificado['certKey']= $certKey;
		$this->dadosCertificado['pathPfx']= $dadosCertificado['pathPfx'];
		$this->dadosCertificado['password']= $dadosCertificado['password'];
		if (file_exists($pubKey)) {
            $cert = file_get_contents($pubKey);
            if (!$data = openssl_x509_read($cert)) {
                //arquivo não pode ser lido como um certificado,então deletar
                $flagNovo = true;
            }
        } else {
            //arquivo não localizado
            $flagNovo = true;
        }//fim if file pubkey
        
        //criar novos arquivos PEM
        if ($flagNovo) {
            if (file_exists($pubKey)) {
                unlink($pubKey);
            }
            if (file_exists($priKey)) {
                unlink($priKey);
            }
            if (file_exists($certKey)) {
                unlink($certKey);
            }
            //recriar os arquivos pem com o arquivo pfx
            if (! file_put_contents($priKey, $x509certdata['pkey'])) {
                $this->erros = "Impossivel gravar no diretório!!! Permissão negada!!";
            }

            //acrescenta a cadeia completa dos certificados se estiverem inclusas no arquivo pfx.
            $aCer = $x509certdata['extracerts'];
            $chain = '';
            foreach ($aCer as $cert) {
                $chain .= "$cert";
            }
            file_put_contents($pubKey, $x509certdata['cert']);
            file_put_contents($certKey, $x509certdata['cert'] . $chain);
        }
	}

	/**
	 * dadosBoletos
	 * Recebe todos os dados necessários para montar o JSON, validando as informações necessárias
	 * @param  Array $dados Array associativo contendo todos os parametros, seguindo o padrão da documentação
	 */
	public function dadosBoleto($dados)
	{	
		$dadosBoleto                                                           = array();
		$dadosBoleto['nuCPFCNPJ']                                              = substr($this->dadosBeneficiario['cnpj'],0,8);
		$dadosBoleto['filialCPFCNPJ']                                          = substr($this->dadosBeneficiario['cnpj'],8,4);
		// Filial CNPJ (4) *
		$dadosBoleto['ctrlCPFCNPJ']                                            = substr($this->dadosBeneficiario['cnpj'],12,2); // Dígito de controle CNPJ (2) *
		// $dadosBoleto['nuCPFCNPJ']                                              = "123456789"; // Raiz do CNPJ (9) *
		// $dadosBoleto['filialCPFCNPJ']                                          = "0001"; // Filial CNPJ (4) *
		// $dadosBoleto['ctrlCPFCNPJ']                                            = "39"; // Dígito de controle CNPJ (2) *
		
		$dadosBoleto['cdTipoAcesso']                                           = "2"; //Tipo de acesso (2) *
		$dadosBoleto['clubBanco']                                              = "2269561"; //Club Banco - 237 (10)
		$dadosBoleto['cdTipoContrato']                                         = "48"; // Tipo de contrato (3)
		$dadosBoleto['nuSequenciaContrato']                                    = (string) intval($this->dadosBeneficiario['contrato']); // Número de Sequência do Contrato (10)

		$dadosBoleto['idProduto']                                              = $this->limpaString($this->dadosBeneficiario['nCarteira']); // Número da carteira (2) *
		/**
		 * Alterar o cálculo da agência em $dadosBoleto['nuNegociacao'], porque as agencias do Bradesco não contém sempre dos primeiros digitos como 00
		 */
		$dadosBoleto['nuNegociacao']                                           = str_pad($this->dadosBeneficiario['agencia'],2,"0",STR_PAD_LEFT).'0000000'.str_pad($this->dadosBeneficiario['conta'],7,"0",STR_PAD_LEFT);// Número da Negociação Formato: Agencia: 4 posições(Sem digito) Zeros: 7 posições Conta: 7 posições (Sem digito) (18) *
		//$dadosBoleto['nuNegociacao']                                           = "123400000001234567";
		$dadosBoleto['cdBanco']                                                = "237"; // Código do Banco – Fixo “237” (3) *
		$dadosBoleto['eNuSequenciaContrato']                                   = (string) intval($this->dadosBeneficiario['contrato']); //Número de Sequência do Contrato (10)
		$dadosBoleto['tpRegistro']                                             = "1"; //Tipo de Registro – Fixo “1” (à vencer/vencido) (3) *
		$dadosBoleto['cdProduto']                                              = "0"; //Código do Produto (8) *
		$dadosBoleto['nuTitulo']                                               = (string) $dados['nuTitulo']; //Número do Título (Nosso Número sem o dígito) (11)
		$dadosBoleto['nuCliente']                                              = (string) $dados['nuTitulo']; //Número do Título (Nosso Número sem o dígito) (10) *

		$dados['dtEmissaoTitulo']                                              = $dados['dtEmissaoTitulo']<>"" ? $dados['dtEmissaoTitulo'] : date("Y-m-d");
		$dhEmi = DateTime::createFromFormat('Y-m-d', $dados['dtEmissaoTitulo']);
		$dadosBoleto['dtEmissaoTitulo']                                        = $dhEmi->format('d.m.Y'); //Data de Emissão do Título (Formato: DD.MM.AAAA) (10) *

		$dhVen = DateTime::createFromFormat('Y-m-d', $dados['dtVencimentoTitulo']);
		$dadosBoleto['dtVencimentoTitulo']                                     = $dhVen->format('d.m.Y'); //Data de Vencimento do Título (Formato: DD.MM.AAAA) Obs: Data de Vencimento do título deve ser maior ou igual a data de emissão do título (10) *

		$dadosBoleto['tpVencimento']                                           = "0"; //Tipo de Vencimento – Fixo “0” (1) *
		$dadosBoleto['vlNominalTitulo']                                        = number_format($dados['vlNominalTitulo'],2,'',''); // Valor Nominal do Título (17) *
		$dadosBoleto['cdEspecieTitulo']                                        = "18"; //Código da Espécie do Título Códigos possíveis de acordo com item 9.1 (2) *
		$dadosBoleto['tpProtestoAutomaticoNegativacao']                        = "0"; //Tipo de Protesto Automático ou Negativação = 01 – DIAS CORRIDOS PARA PROTESTO,  02- DIAS ÚTEIS PARA PROTESTO, 03 – DIAS CORRIDOS PARA NEGATIVAÇÃO (2)
		$dadosBoleto['prazoProtestoAutomaticoNegativacao']                     = "0"; //Prazo para Protesto Automático ou Negativação (2) *
		$dadosBoleto['controleParticipante']                                   = $this->limpaString(substr($dados['controleParticipante'],0,25));
		$dadosBoleto['cdPagamentoParcial']                                     = ""; //Indicador de Pagamento parcial, S ou N (1)
		$dadosBoleto['qtdePagamentoParcial']                                   = "0"; //Quantidade de Pagamentos Parciais (3)
		$dadosBoleto['percentualJuros']                                        = $this->formataPercentual($dados['percentualJuros']); //Quantidade de Pagamentos Parciais (3)
		$dadosBoleto['vlJuros']                                                = number_format($dados['vlJuros'],2,'',''); //Valor de Juros Se o campo percentualjuros for preenchido, não deve ser preenchido esse campo (17) *
		$dadosBoleto['qtdeDiasJuros']                                          = (string) intval($dados['qtdeDiasJuros']); //Quantidade de dias para cálculo Juros (2)
		$dadosBoleto['percentualMulta']                                        = $this->formataPercentual($dados['percentualMulta']);  //Percentual de Multa (8)
		$dadosBoleto['vlMulta']                                                = number_format($dados['vlMulta'],2,'',''); // Valor da Multa (17)
		$dadosBoleto['qtdeDiasMulta']                                          = (string) intval($dados['qtdeDiasMulta']); // Quantidade de dias para cálculo Multa
		
		$dadosBoleto['percentualDesconto1']                                    = $this->formataPercentual($dados['percentualDesconto1']); // Percentual do Desconto 1 (8)
		$dadosBoleto['vlDesconto1']                                            = number_format($dados['vlDesconto1'],2,'',''); // Percentual do Desconto 1 (8)
		$dhDesc1 = DateTime::createFromFormat('Y-m-d', $dados['dataLimiteDesconto1']);
		$dDesc1 = $dhDesc1 instanceof \DateTime ? $dhDesc1->format('d.m.Y') : "";
		$dadosBoleto['dataLimiteDesconto1']                                    = $dDesc1; //Data Limite para Desconto 1 (10)

		$dadosBoleto['percentualDesconto2']                                    = $this->formataPercentual($dados['percentualDesconto2']); // Percentual do Desconto 2 (8)
		$dadosBoleto['vlDesconto2']                                            = number_format($dados['vlDesconto2'],2,'',''); // Percentual do Desconto 2 (8)
		$dhDesc2 = DateTime::createFromFormat('Y-m-d', $dados['dataLimiteDesconto2']);
		$dDesc2 = $dhDesc2 instanceof \DateTime ? $dhDesc2->format('d.m.Y') : "";
		$dadosBoleto['dataLimiteDesconto2']                                    = $dDesc2; //Data Limite para Desconto 2 (10)

		$dadosBoleto['percentualDesconto3']                                    = $this->formataPercentual($dados['percentualDesconto3']); // Percentual do Desconto 3 (8)
		$dadosBoleto['vlDesconto3']                                            = number_format($dados['vlDesconto3'],2,'',''); // Percentual do Desconto 3 (8)
		$dhDesc3 = DateTime::createFromFormat('Y-m-d', $dados['dataLimiteDesconto3']);
		$dDesc3 = $dhDesc3 instanceof \DateTime ? $dhDesc3->format('d.m.Y') : "";
		$dadosBoleto['dataLimiteDesconto3']                                    = $dDesc3; //Data Limite para Desconto 3 (10)

		$dadosBoleto['prazoBonificacao']                                       = "0"; //Prazo para Bonificação: 1 – dias corridos / 2 - dias úteis
		$dadosBoleto['percentualBonificacao']                                  = "0"; //Percentual de Bonificação
		$dadosBoleto['vlBonificacao']                                          = "0"; //Valor de Bonificação
		$dadosBoleto['dtLimiteBonificacao']                                    = ""; //Data Limite para Bonificação

		$dadosBoleto['vlAbatimento']                                           = "0"; //Valor do Abatimento
		$dadosBoleto['vlIOF']                                                  = "0"; //Valor do IOF

		/**
		 * Dados do pagador do boleto
		 */
		$dadosBoleto['nomePagador']                                            = $this->limpaString(substr($dados['nomePagador'],0,70));
		$dadosBoleto['logradouroPagador']                                      = $this->limpaString(substr($dados['logradouroPagador'],0,40));
		$dadosBoleto['nuLogradouroPagador']                                    = $this->limpaString(substr($dados['nuLogradouroPagador'],0,10));
		/**
		 * Expresão regular para remover caracters invalidos
		 */
		$dadosBoleto['complementoLogradouroPagador']                           = preg_match("/^[a-zA-Z\d]+$/", $this->limpaString(substr($dados['complementoLogradouroPagador'],0,15)));

		$cep                                                                   = $this->soNumeros($dados['cepPagador']);
		$dadosBoleto['cepPagador']                                             = $this->soNumeros(substr($cep,0,5));
		$dadosBoleto['complementoCepPagador']                                  = $this->soNumeros(substr($cep,5,3));

		$dadosBoleto['bairroPagador']                                          = $this->limpaString(substr($dados['bairroPagador'],0,40));
		$dadosBoleto['municipioPagador']                                       = $this->limpaString(substr($dados['municipioPagador'],0,30));
		$dadosBoleto['ufPagador']                                              = $this->limpaString(substr($dados['ufPagador'],0,2));
		/**
		* cdIndCpfcnpjPagador
		* 1 = CPF
		* 2 = CNPJ
		*/
		if( !strlen($this->soNumeros($dados['nuCpfcnpjPagador'])) == 14 )
		{
			$dadosBoleto['cdIndCpfcnpjPagador']                                = "1";
			$dadosBoleto['nuCpfcnpjPagador']                                   = str_pad($this->soNumeros($dados['nuCpfcnpjPagador']),14,"0",STR_PAD_LEFT);
		}
		else{
			$dadosBoleto['cdIndCpfcnpjPagador']                                = "2";
			$dadosBoleto['nuCpfcnpjPagador']                                   = str_pad($this->soNumeros($dados['nuCpfcnpjPagador']), 14,"0",STR_PAD_LEFT);
		}
		$dadosBoleto['endEletronicoPagador']                                   = $this->limpaString(substr($dados['endEletronicoPagador'],0,70));
		$dadosBoleto['nomeSacadorAvalista']                                    = $this->limpaString(substr($dados['nomeSacadorAvalista'],0,40));
		$dadosBoleto['logradouroSacadorAvalista']                              = $this->limpaString(substr($dados['logradouroSacadorAvalista'],0,40));
		$dadosBoleto['nuLogradouroSacadorAvalista']                            = $this->limpaString(substr($dados['nuLogradouroSacadorAvalista'],0,10));
		$dadosBoleto['complementoLogradouroSacadorAvalista']                   = $this->limpaString(substr($dados['complementoLogradouroSacadorAvalista'],0,15));

		$cep = $this->soNumeros($dados['cepSacadorAvalista']);
		$dadosBoleto['cepSacadorAvalista']                                     = $this->soNumeros(substr($cep,0,5)) == "" ? "0" : $this->soNumeros(substr($cep,0,5));
		$dadosBoleto['complementoCepSacadorAvalista']                          = $this->soNumeros(substr($cep,5,3))  == "" ? "0" : $this->soNumeros(substr($cep,5,3));

		$dadosBoleto['bairroSacadorAvalista']                                  = $this->limpaString(substr($dados['bairroSacadorAvalista'],0,40));	
		$dadosBoleto['municipioSacadorAvalista']                               = $this->limpaString(substr($dados['municipioSacadorAvalista'],0,40));	
		$dadosBoleto['ufSacadorAvalista']                                      = $this->limpaString(substr($dados['ufSacadorAvalista'],0,2));
		/**
		* nuCpfcnpjSacadorAvalista
		* 1 = CPF
		* 2 = CNPJ
		*/
		if(strlen($this->soNumeros($dadosBoleto['nuCpfcnpjSacadorAvalista']))  == 14)
		{
			$dadosBoleto['cdIndCpfcnpjSacadorAvalista']                        = "2";
			$dadosBoleto['nuCpfcnpjSacadorAvalista']                           = $this->soNumeros($dados['nuCpfcnpjSacadorAvalista']);
		}
		elseif(strlen($this->soNumeros($dadosBoleto['nuCpfcnpjSacadorAvalista'])) > 0 ){
			$dadosBoleto['cdIndCpfcnpjSacadorAvalista']                        = "1";
			$dadosBoleto['nuCpfcnpjSacadorAvalista']                           = str_pad($this->soNumeros($dados['nuCpfcnpjSacadorAvalista']), 14,0,STR_PAD_LEFT);
		}
		else{
			$dadosBoleto['cdIndCpfcnpjSacadorAvalista']                        = "0";
			$dadosBoleto['nuCpfcnpjSacadorAvalista']                       	   = "0";
		}
		$dadosBoleto['endEletronicoSacadorAvalista']                           = $this->limpaString(substr($dados['endEletronicoSacadorAvalista'],0,70));

		$this->json = json_encode($dadosBoleto,JSON_UNESCAPED_UNICODE);
	}

	public function assinaBoleto()
	{
		$result = "";
		$file = "/tmp/".uniqid();
		$file2 = "/tmp/".uniqid();
		file_put_contents($file, $this->json);
		//echo $this->json."<BR>".$file;exit;
		// echo "<pre>";
		// var_dump(json_decode($this->json,JSON_UNESCAPED_UNICODE));
		// exit;
		$pfxContent = file_get_contents($this->dadosCertificado['pathPfx']);
		openssl_pkcs12_read($pfxContent, $result, $this->dadosCertificado['password']);
		$certificado_key = openssl_x509_read($result['cert']);
		$private_key = openssl_pkey_get_private($result['pkey'], $this->dadosCertificado['pKey']);
		openssl_pkcs7_sign($file, $file2, $certificado_key, $private_key, [], PKCS7_BINARY | PKCS7_TEXT);
		//echo file_get_contents($file);exit;
		$parts = preg_split("#\n\s*\n#Uis", file_get_contents($file2));
		unlink($file);
		unlink($file2);
		$this->json = $parts[1];
	}

	public function registra(){
		$this->assinaBoleto();
		//echo $this->json;exit;
		
		if($this->enviaBoleto()){
			switch ((string) $this->boleto->cdErro) {
				case '0': //Solicitação atendida
				
					$mtz="";
					$mtz['id_parcela'] = (int) $this->dadosBeneficiario['idParcela'];
					$mtz['id_pessoas'] = (int) $_SESSION['usrId'];
					$mtz['id_pessoas_cliente'] = (int) $this->dadosBeneficiario['idCliente'];
					$mtz['data'] = date("Y-m-d H:i:s");
					$mtz['numero'] = $this->boleto->nuTituloGerado;
					$mtz['codigo_barras'] = $this->boleto->cdBarras;
					/**
					 * Observaçao:
					 * O código de barras enviado pelo Bradesco aparece na forma de uma string de WWWw
					 * Selecionar se o desenho da barras será feito pelo gFW ou decodificado do json enviado pelo BradescoOnline
					 */
					$mtz['linha_digitavel'] = $this->boleto->linhaDigitavel;
					$mtz['valor'] = substr($this->boleto->vlTitulo,0,strlen($this->boleto->vlTitulo)-2).'.'.substr($this->boleto->vlTitulo,-2);
					$mtz['data_vencimento'] = DateTime::createFromFormat('d.m.Y', (string) $this->boleto->dtVencimento)->format("Y-m-d");
					$mtz['data_emissao'] = DateTime::createFromFormat('dmY', (string) $this->boleto->dtEmissao)->format("Y-m-d");;
					$mtz['desconto'] = 0.00;
					$mtz['json'] =json_encode($this->boleto);
					
					$sql="INSERT INTO fin_boletos (".implode(",",array_keys($mtz)).") VALUE('".implode("','",array_values($mtz))."')";
					$this->conn->query($sql);
				break;
				
				default:
					$this->erros[] = $this->listaErro((string) $this->boleto->cdErro);
				break;
			}
		}
	}

	/**
	 * enviaBoleto
	 * Faz o envio do boleto armazenado no Json
	 */
	public function enviaBoleto(){
		
		if($this->json<>""){
			$ch = curl_init($this->url[$this->ambiente]);
			curl_setopt($ch, CURLOPT_POST, true);
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->json);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		    curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-pkcs7-mime;","Content-Transfer-Encoding: base64")); 

		    $xml = curl_exec($ch);
		   	$xml = str_replace(array('<?xml version="1.0" encoding="UTF-8"?>'),"",$xml);
			if($xml){
		    	$ret = preg_replace("/\r?\n/","", $xml);
		    	$lenN = strlen($ret);
		    	$posX = stripos($ret, "<return>");
		    	$posY = stripos($ret, "</return>");
		    	$return = json_decode(substr($ret,$posX+8,$posY-$lenN));
		    	if(is_object($return)){
		    		$this->boleto = $return;
		    		return true;
		    	}
		    	else
		    	{
					$this->erros[]="Falha ao processar retorno!!";
					return false;
		    	}
		    }
		}
		else{
			$this->erros[]="Nenhum dado a ser enviado";
			return false;
		}
	}

	/**
	 * formataPercentual
	 * Formata um número passado para o valor aceito na documentação
	 * @param  float $val
	 * @return string
	 */
	public function formataPercentual($val)
	{
		if(!$val > 0)
			$tmp=0;

		$tmp = explode(".",$val);
		$interos = str_pad($tmp[0],3,"0",STR_PAD_LEFT);
		$decimais = str_pad($tmp[2],5,"0",STR_PAD_RIGHT);
		return $interos.$decimais;
	}

	/**
	 * Deixa apenas números da String
	 */
	public function soNumeros($str) {
    	return preg_replace("/[^0-9]/", "", $str);
	}
	/**
     * limpaString
     * Remove todos dos caracteres espceiais do texto e os acentos
     *
     * @name cleanString
     * @return  string Texto sem caractere especiais
     */
	private function limpaString($texto)
    {
        $aFind = array('&','á','à','ã','â','é','ê','í','ó','ô','õ','ú','ü',
            'ç','Á','À','Ã','Â','É','Ê','Í','Ó','Ô','Õ','Ú','Ü','Ç');
        $aSubs = array('e','a','a','a','a','e','e','i','o','o','o','u','u',
            'c','A','A','A','A','E','E','I','O','O','O','U','U','C');
        $novoTexto = str_replace($aFind, $aSubs, $texto);
        $novoTexto = preg_replace("/[^a-zA-Z0-9 @,-.;:\/]/", "", $novoTexto);
        return strtoupper($novoTexto);
    }

    private function addBarDate($str){
    	return substr($str,0,2)."/".substr($str, 2,2)."/".substr($str, 4,4);
    }

    /**
     * listaErro
     * Retorna a descrição do erro retornado ao enviar a requisição
     */
    public function listaErro($codigo)
    {

    	$erros = array();
    	$erros['-99'] 	= "Serviço indisponível no momento. Tente novamente mais tarde.";
    	$erros['-4'] 	= "Tamanho do campo inválido";
    	$erros['-3'] 	= "Tipo do campo inválido";
    	$erros['-2'] 	= "Contrato não encontrado";
    	$erros['-1'] 	= "Contrato não aprovado";
    	$erros['00'] 	= "Solicitação atendida";
    	$erros['01'] 	= "Solicitação não encontrada";
    	$erros['05'] 	= "Inclusão efetuada";
    	$erros['06']	= "Dados inconsistentes";
		$erros['10']	= "Erro Acesso Subrotina";
		$erros['12']	= "Cliente/Negociação Bloqueado";
		$erros['13']	= "Usuário não Autorizado";
		$erros['14']	= "Espécie Título Inválida";
		$erros['15']	= "Tipo/Número Inscrição Inválido";
		$erros['16']	= "Informe todos os campos para decurso de Prazo";
		$erros['17']	= "Nome do Pagador Especial Não Informado";
		$erros['18']	= "Endereço Inválido";
		$erros['19']	= "CEP Inválido";
		$erros['20']	= "Agência Depositária Inválida";
		$erros['21']	= "Informe todos os campos para Instrução de Protesto";
		$erros['22'] 	= "Banco Inválido";
		$erros['23']	= "Seu Número Inválido";
		$erros['24']	= "Informe todos os campos para Abatimento";
		$erros['25']	= "Valor dos Juros maior que o Valor do Título";
		$erros['26']	= "Data de Emissão maior que a Data de Vencimento";
		$erros['27']	= "Documento do Sacador Avalista Inválido";
		$erros['28']	= "Informe todos os campos para Desconto";
		$erros['29']	= "Informe todos os campos para Sacador Avalista";
		$erros['30']	= "Data Vencimento Menor ou igual Data Emissão";
		$erros['31']	= "Data Desconto menor ou igual Data Emissão";
		$erros['32']	= "Data Desconto maior que Data Vencimento";
		$erros['33']	= "Valor Desconto/Bonificação maior ou igual Valor Título";
		$erros['34']	= "Tipo informado deve ser 1, 2 ou 3";
		$erros['35']	= "Valor Abatimento maior que o Valor do Título";
		$erros['36']	= "CEP Inválido";
		$erros['37']	= "Data Emissão Inválida";
		$erros['38']	= "Data Vencimento Inválida";
		$erros['39']	= "Percentual informado maior ou igual 100,00";
		$erros['40']	= "Número CGC/CPF inválido";
		$erros['41']	= "Protesto Automático x Decurso de Prazo Incompatível";
		$erros['42']	= "Banco/Agência Depositária Inválido";
		$erros['43']	= "Espécie de Documento inválido";
		$erros['44']	= "Informe 1-contra apresentação ou 2-a vista Código da instrução de protesto inválido";
		$erros['46']	= "Dias para instrução de protesto inválido Código para desconto inválido";
		$erros['48']	= "Código para multa inválido";
		$erros['49']	= "Código para comissão permanência dia inválido";
		$erros['50']	= "Espécie Documento exige CGC para Sacador Avalista";
		$erros['51']	= "CEP e/ou Banco/Agência Depositária Inválido";
		$erros['52']	= "Data Emissão maior ou igual Data Vencimento";
		$erros['53']	= "Data Desconto Inválida";
		$erros['54']	= "Data emissão maior Data Registro";
		$erros['55']	= "Percentual multa informado maior que o permitido";
		$erros['56']	= "Percentual comissão permanência informado maior que o Permitido";
		$erros['57']	= "Percentual Bonificação informado maior que o permitido";
		$erros['58']	= "Prazo para Protesto inválido";
		$erros['59']	= "Informe a data ou tipo do vencimento";
		$erros['60']	= "Valor do IOF não permitido para produtos 05,15,43 ou 44";
		$erros['61']	= "Abatimento já cadastrado para o título";
		$erros['62']	= "Abatimento não cadastrado para o título";
		$erros['63']	= "Não é permitida mais de uma bonificação para o título";
		$erros['64']	= "Não é permitido datas de desconto/bonificação iguais";
		$erros['65']	= "Negociação inexistente";
		$erros['66']	= "Cliente inexistente";
		$erros['67']	= "CNPJ/CPF inválido";
		$erros['68']	= "N.Número não pode ser informado quando status 4";
		$erros['69']	= "Título já cadastrado";
		$erros['70']	= "Data e tipo de vencimento incompatíveis";
		$erros['71']	= "Data de vencimento não pode ser posterior a 10 anos";
		$erros['72']	= "Dias para instrução inferior ao padrão";
		$erros['73']	= "Dias para instrução antecipa data de protesto";
		$erros['74']	= "Valor IOF obrigatório";
		$erros['75']	= "Valor IOF incompatível com id produto";
		$erros['76']	= "Tipo de abatimento inválido";
		$erros['77']	= "Status Inválido";
		$erros['78']	= "Registro on line não permite banco diferente de 237";
		$erros['79']	= "Carta para protesto não recebida";
		$erros['80']	= "Tipo de vencimento inválido";
		$erros['81']	= "Valor acumulado desconto/bonificação maior ou igual valor título";
		$erros['82']	= "Datas desconto/bonificação fora de sequência";
		$erros['83']	= "Informe todos os campos para multa";
		$erros['84']	= "Código comissão permanência inválido";
 		$erros['85']	= "Informe todos os campos para comissão permanência";
		$erros['86']	= "Registro duplicado na tabela de ocorrências";
 		$erros['87']	= "Solicitação de protesto já existente";
		$erros['88']	= "Registro duplicado na base de atualização sequencial";
 		$erros['89']	= "Sacador avalista já cadastrado";
		$erros['90']	= "Indicador CIP inexistente";
		$erros['91']	= "Moeda negociada inexistente";
		$erros['92']	= "Banco/agência operadora inexistente";
		$erros['93']	= "Acessório escritural negociado inexistente";
		$erros['94']	= "Pólo de serviço inexistente para banco/agência";
		$erros['95']	= "Banco/agência centralizadora não cadastrada para banco/agência depositária";
		$erros['96']	= "Título não encontrado pelo módulo CBON8230";
		$erros['97']	= "Valor IOF maior ou igual valor título";
		$erros['98']	= "Data Inválida";
		$erros['99']	= "Id Prod/Cta não cadastrados";

		return $erros[$codigo];
	}

	private function motivo_ocorrencia($id){
		$ocorrencias = array();
		$ocorrencias['02'] = 'Entrada Confirmada';
		$ocorrencias['06'] = 'Liquidação normal';
		$ocorrencias['10'] = 'Baixado conforme instruções da Agência';
		$ocorrencias['12'] = 'Abatimento Concedido';
		$ocorrencias['13'] = 'Abatimento Cancelado ';
		$ocorrencias['14'] = 'Vencimento Alterado';
		$ocorrencias['17'] = 'Liquidação após baixa ou Título não registrado';
		$ocorrencias['18'] = 'Acerto de Depositária';
		$ocorrencias['20'] = 'Confirmação Recebimento Instrução Sustação de Protesto';
		$ocorrencias['21'] = 'Acerto do Controle do Participante';
		$ocorrencias['23'] = 'Entrada do Título em Cartório ';
		$ocorrencias['33'] = 'Confirmação Pedido Alteração Outros Dados';
		$ocorrencias['34'] = 'Retirado de Cartório e Manutenção Carteira';
		$ocorrencias['69'] = 'Cancelamento de Rateio';
		return $ocorrencias[$id];
	}
	/**
	 * sendMail Envio do boleto gerado por email
	 * @author Jorge <jorge@giusoft.com.br>
	 * @param  array $email Informações como remetente, destinatario e conteudo do email a ser enviado
	 * @return boolean       Confirma se foi possivel enviar o email
	 */
	public function sendMail($email){
		include_once('/var/www/html/gfw/4.0/inc/lib/phpmailer/class.phpmailer.php');

		if(!is_array($email) || !filter_var($email['remetente'], FILTER_VALIDATE_EMAIL) || !filter_var($email['destinatario'], FILTER_VALIDATE_EMAIL))
			return false;
		if(!is_string($email['assunto']) || !file_exists($email['anexo']))
			return false;
		$mail = new PHPMailer();
		
		try{
			/**
			 * PHPMailer utiliza o sendmail do servidor
			 * Login de teste: contatestemail@gmail.com
			 * Senha de teste: 123Mudar#
			 */
			$mail->isSendmail();
			/**
			 * Em produção, SMTPDebug deve ter valor = SMTP::DEBUG_OFF
			 */
			$mail->FromName='GiuSoft';
			$mail->AddAddress($email['destinatario']);
			$mail->Subject = $email['assunto'];
			$mail->Body = $email['conteudo'];
			$mail->AltBody = 'O HTML deve ser ativado';


		/*
						PHPMAILER 6.1
			$mail->setFrom($email['remetente']);
			$mail->addAddress($email['destinatario']);
			$mail->Subject = $email['assunto'];
			$mail->Body = $email['conteudo'];
			$mail->AltBody = 'O HTML deve ser ativado';

		*/
			if (file_exists($email['anexo']))
				$mail->addAttachment($email['anexo']);

			if(!$mail->send())
			{
				return false;
			}
			return true;
		}
		catch(Exception $e)
		{
			return false;
		}

		// require_once("/var/www/html/gfw/4.0/inc/lib/phpmailer/class.phpmailer.php");
		// require_once("/var/www/html/gfw/4.0/inc/lib/phpmailer/class.smtp.php");
		

		// $mail = new PHPMailer();

		// /**
		//  * [$mail->PluginDir Diretorio utilizado para localizar a class SMTP, dentro da classe PHPMailer]
		//  * @var string
		//  */
		// $mail->PluginDir = '/var/www/html/gfw/4.0/inc/lib/phpmailer/';

		// /**
		//  * [$mail->SMTPDegub Adicionar DEGUB nas classes PHPMailer e SMTP]
		//  * @var integer
		//  */
		// $mail->SMTPDegub = 2;

		// var_dump($mail->smtp);

		// $mail->SetLanguage("br");
		// $mail->isSMTP();
		// $mail->Host = 'smtp.gmail.com';
		// $mail->FromName = 'Conta teste';
		// $mail->SMTPAuth = true;
		// $mail->Username = 'contatestmail@gmail.com';
		// $mail->Password = '123Mudar!';
		// $mail->Port = 587;
		// $mail->WordWrap = 50;
		// //Adicionar aqui $mail->AddAttachment($anexo)
		// $mail->From = 'contatestmail@gmail.com';
		// $mail->IsHTML(true);
		// $mail->Subject = 'Email de Teste';
		// $mail->Body = nl2br("Mensagem de Teste \n Com tratamento para \\n");
		// $mail->AltBody = 'Formato valido: HTML';
		// $mail->AddAddress('jorge@giusoft.com.br');
		// if(!$mail->send()){
		// 	echo "Error ao enviar o email: ".$mail->ErrorInfo;
		// }
		// else {
		// 	echo "Email enviado com sucesso";
		// }

	}

	/**
	 * gerarBoleto Geração do boleto bancário através da tabela gadmin.fin_boletos
	 * @author Jorge <jorge@giusoft.com.br>
	 * @param  integer $id   ID da tabela gadmin.fin_boletos, que contém os dados do boleto a ser gerado
	 * @return string       Buffer contendo o layout do boleto
	 */
	public function gerarBoleto($id)
	{	
		$result = $this->conn->query("SELECT * FROM gadmin.fin_boletos WHERE id = ".$id)->fetchAll()[0];
		if (count($result) >= 1)
		{
			/**
			 * Os dados obtidos via Json do bradesco são passados para o array layoutboleto
			 * 
			 * Os dados utilizados para gerar o boleto Bradesco sáo defindos no array $dadosboleto. Este array deve estar no mesmo escopo do "include"
			 * para os arquivos "funcoes_bradesco.php" e "layout_bradesco.php"
			 * 
			 * Para gerar a linha digitável do boleto, algumas valores são passadas para o array $dadosboleto"
			 * 1 - Codigo do banco (Já definido)
			 * 2 - Numero da moeda (Já definido)
			 * 3 - Fator Vencimento: Gerado pela data do vencimento
			 * 4 - Valor do boleto
			 * 5 - Agencia
			 * 6 - Nosso Numero: 6.1 - Numero da carteira + 6.2 - Nosso Numero
			 * 7 - Conta Cedente
			 */
		
			$data = json_decode($result['json'],true);
			$this->layoutboleto['servico'] = 'Associado';
			$this->layoutboleto['numero_documento'] = $data['numeroTitulo'];
			
			$this->layoutboleto['sacado'] = $data['nomePagador'];
			$this->layoutboleto['endereco1'] = $data['enderecoPagador'];
			$this->layoutboleto['endereco2'] = $data['municipioPagador']." - ".$data['bairroPagador']." CEP: ".$data['cepPagador'];
			$this->layoutboleto['aceite'] = "N";
			$this->layoutboleto['especie'] = "R$";
			$this->layoutboleto['especie_doc'] = $data['especieDocumentoTitulo'];
			/**
			 * As datas gerados obtidas pelo JSON Bradesco não contem /, sendo um string Alfanumerica
			 *
			 * 3 - Fator Vencimento / $layoutboleto['data_vencimento']
			 */
			$this->layoutboleto['data_vencimento'] = str_replace(".", "/", $data['dtVencimento']);
			/**
			 * O campo $this->layoutboleto['valor_boleto'] deve conter somente números
			 *
			 * 4 - Valor do boleto
			 */
			$this->layoutboleto['valor_boleto'] = str_replace(".", ",",$result['valor']);
			$this->layoutboleto['data_documento'] = DateTime::createFromFormat('dmY', (string) $data['dtEmissao'])->format("d/m/Y");
			$this->layoutboleto['data_processamento'] = $this->layoutboleto['data_documento'];
			/**
			 * 5 - Agencia
			 */
			$this->layoutboleto['agencia'] = substr($data['negociacao'],0,4);
			// $this->layoutboleto['agencia_dv'] = substr($data['agenciaCreditoBeneficiario'],-1);
			/**
			 * O digito da agência foi adicionado manualmente
			 * O Dígito da agência do beneficiário não é adicionado no arquivo-retorno
			 */
			$this->layoutboleto['agencia_dv'] = "7"; 
			$this->layoutboleto['conta'] = substr($data['negociacao'],-7);
			$this->layoutboleto['conta_dv'] = $data['digCreditoBeneficiario'];
			/**
			 * 6.1 - Carteira 
			 */
			$this->layoutboleto['carteira'] = $data['idProduto'];
			/**
			 * 6.2 - Nosso Numero
			 */
			$this->layoutboleto['nosso_numero'] = $data['numeroTitulo'];
			/**
			 * conta_cedente == conta
			 *
			 * 7 - Conta Cedente
			 */
			$this->layoutboleto['conta_cedente'] = $this->layoutboleto['conta'];
			$this->layoutboleto['identificacao'] = $data['razaoContaBeneficiario'];
			$this->layoutboleto['cpf_cnpj'] = $data['cpfcnpjBeneficiario'];
			$this->layoutboleto['endereco'] = $data['logradouroBeneficiario']." ".$data['nuLogradouroBeneficiario'].", ".$data['complementoLogradouroBeneficiario'];
			$this->layoutboleto['cedente'] = $this->dadosBeneficiario['razaoSocial'];
			//$this->layoutboleto['demonstrativo1'] = "Não receber após ";
			
			if ($result['desconto'] <> '0.00')
			{
				$this->layoutboleto['demonstrativo2'] = "DESCONTO DE ".$result['desconto']." ATÉ O VENCIMENTO";
			}

			$this->layoutboleto['instrucoes1'] = $this->layoutboleto['demonstrativo2'];
			/**
			 * Exibir demostrativo sobre o cálculo de juros
			 * A mensagem varia caso os juros seja por mês ou ao dia
			 * Juros por dia possui $data['cdJuros'] == '1'
			 * Juros por mês possui $data['cdJuros'] == '2'
			 * Se o valor retornado for 0, não é exibido a mensagem sobre os juros
			 * O BradescoOnline fornece valores de juros ao dia ou ao mês
			 * 
			 * Foi considerado que valores literais (cdValorJuros == 1) refere-se a juros diários e valores percentuais (cdValorJuros == 2) a juros mensais
			 *
			 * Apesar disso, juros nominais podem se referir ao mês (R$15,00 ao mês) ou juros percentuais se referir ao dia (0,1% ao dia), mesmo não sendo muito adotado na prática
			 */
			
			/**
			 * Variáveis definidas para testar os juros
			 * 
			 * $data['vlJuros'] = '0100';
			 * $data['cdValorJuros'] = '2';
			 * $juros = "Após o vencimento cobrar juros de mora de ";
			 */
			if ($data['vlJuros'] <> '0' || $data['vlJuros'] <> '')
			{
				/**
				 * Juros nominais ao dia
				 */
				if ($data['cdValorJuros'] == '1')
				{
					/**
					 * O Bradesco informa que os valores de juros podem conter 15 dígitos. Os dois últimos se referem aos centavos
					 */
					/**
					 * $valor - Valor dos juros a serem inseridos no sistema
					 * O tratamento da string garante que haverá pelo menos três caracters, removendo os algorismos não-signficativos (zeros à esquerda)
					 * @var string
					 */
					$valor = str_pad(ltrim($data['vlJuros'],'0'),3,'0', STR_PAD_LEFT);
					
					$valorJuros.="R$ ".substr($data['vlJuros'],0,-2).",".substr($data['vlJuros'],-2)." ao dia.";
				}
				/**
				 * Juros percentuais ao mês
				 */
				elseif($data['cdValorJuros'] == "2")
				{
					/**
					 * Caso o cdJuros == '2' a percentagem iria se referir ao dia (" % ao dia")
					 * Para campos percentuais, o Bradesco informa que os campos possuem 8 dígitos, sendo os 3 primeiros para números inteiros e os 5 restantes para valores decimais.
					 * NNNDDDDD
					 * N - Inteiros
					 * D - Decimais
					 */
					$valorPrevio = $data['vlJuros'];
					/**
					 * Verifica se existe os três primeiros algoritmos dos juros, que irão representar os inteiros
					 */
					if(strlen(substr($valorPrevio, 0, 3)) == 3)
					{
						$valorInteiro = ltrim(substr($valorPrevio, 0,3),'0');
						/**
						 * Verifica se existe os cinco posteriores algoritmos dos juros, que serão os decimais
						 */
						if(strlen(substr($valorPrevio, 3,5)) == 5)
						{
							$valorDecimal = substr($valorPrevio, 3, 5);
						}
						/**
						 * Caso não haja números suficientes, os decimais que serão complementados com zero, e não os inteiros
						 */
						else
						{
							$valorDecimal = str_pad(substr($valorPrevio, 3,5), 5, '0', STR_PAD_RIGHT);
						}
						/**
						 * $valorJuros Valor dos juros a serem exibitos no boleto
						 * Apesar do Bradesco aceitar cinco dígitos decimais, apenas serão apresentados os dois primeiros
						 * São removidos os algoritmos não significativos dos valores inteiros
						 * @var string
						 */
						$valorJuros = str_replace("000", "0", $valorInteiro).",".substr($valorDecimal,0,2)." % ao mês.";
					}
					/**
					 * Caso não exista pelo menos três números inteiros, considerar que os X primeiros algoritmos serão inteiros, complementando com zero
					 * OBS: Fazer tratamento para considerar 1 digito como unidade (1%), 2 digitos como dezena(22%) e 3 dígitos como centena(333%)
					 */
					else
					{
						/**
						 * [$valorInteiro Valor inteiro do juros calculado pela API]
						 * Verifica se removendo todos os zeros dos juros, a string ficará vazia, adicionando zero como valor padrão
						 * C
						 * @var [string]
						 */
						$valorInteiro = ltrim($valorPrevio,'0') == '' ? '0' : ltrim($valorPrevio,'0');
						$valorJuros = $valorInteiro.",00% ao mês";
					}
					
				}
				if($valorJuros)
				{
					$this->layoutboleto['demonstrativo1'].=$juros.$valorJuros;
					$this->layoutboleto['instrucoes2'] = $this->layoutboleto['demonstrativo1'];

				}
			}

			/**
			 * $dadosboleto Nome da variável utilizada nos arquivos "funcoes_bradesco.php" e "layout_bradesco.php", para inserir os dados no layout do boleto
			 * Caso seja utilizado outro nome para a variável, também deve ser alterado o código fonte dos arquivos incluídos abaixos
			 * @var Array
			 */
			$dadosboleto = $this->layoutboleto;
			/**
			 * Para evitar alterações nas bibliotecas layout_bradesco e funcoes_bradesco, foram armazenados em buffer a saída, em HTML, do boleto,
			 *  para posterior alteração de Layout e Estilos
			 */
			ob_start();
			include_once ("/var/www/html/webcfc/rj/giusoft/res/sindicato/boletos/include/funcoes_bradesco.php");
			/**
			 * [$novaLinhaDigitavel A Linha digitável fornecida pelo BradescoOnline, A formatacão da linha através da funcoes_bradesco.php se tornou inválida]
			 * O valor do array $dadosboleto['linha_digitavel'] será substituído pela linha_digitavel obtida na base de dados gadmin.fin_boletos
			 * @var string
			 * 
			 */
			//$novaLinhaDigitavel = $data['linhaDigitavel'];
			//$dadosboleto['linha_digitavel'] = $novaLinhaDigitavel;
			include_once ("/var/www/html/webcfc/rj/giusoft/res/sindicato/boletos/include/layout_bradesco.php");
			$out = ob_get_clean();
			/**
			 * [$out Substituição do PATH para as imagens do boleto]
			 * @var string
			 */
			$out = str_replace('res/sindicato/boletos/imagens/',"/var/www/html/webcfc/rj/giusoft/res/sindicato/boletos/imagens/", $out);
			/**
			 * Salva o arquivo PDF, armazenado em Buffer, no servidor.
			 * O arquivo salvo será enviado por email
			 * Por padrão, o arquivo será salva no diretório /tmp
			 */
			return $out;
		}
		return '';
	}

	/**
	 * [gerarPDF Gerar o arquivo PDF através de um buffer]
	 * @param  string $out  Buffer armazenado pelo método gerarBoleto
	 * @param  string $path Caminho onde o boleto será salvo no servidor
	 * @param array $email Contem dados sobre o envio do boleto via email
	 * @return boolean       Confirma a geração ou não do boleto, com base no buffer
	 */
	public function gerarPDF($out, $path, $visualizar=false)
	{
		/**
		 * $style Define a formatação do boleto. O Layout foi obtido diretamente do arquivo layout_boleto.php
		 * O conteúdo da tag <style> é adicionado no buffer $out
		 * @var string
		 */
		$style = '
	    <style>
	        .cp { font-weight: bold;font-size: 10px;font-family: Arial; color: #000}
	        .ti { font-size: 9px; font-family: Arial, Helvetica, sans-serif}
	        .ld { font-weight: bold;font-size: 15px;font-family: Arial; color: #000000}
	        .ct { font-size: 9px; font-family: Arial ; COLOR: #000033}
	        .cn { font-size: 9px;font-family: Arial; COLOR: black }
	        .bc { font-weight: bold; font-size: 20px; font-family: Arial; color: #000000 }
	        .ld2 { font-weight: bold; font-size: 12px; font-family: Arial; color: #000000 }
	        table, tr, td{border:none;padding:0;margin:0}
	    </style>';

		$out = str_replace('</head>', $style.'</head>', $out);
		if($out)
		{
		    require_once('/var/www/html/webcfc/rj/giusoft/res/sindicato/boletos/html2pdf_v4/html2pdf.class.php');
		    try
		    {
		    	$html2pdf = new HTML2PDF('P', 'A4', 'pt');
				$html2pdf->writeHTML($out);
		    	if($visualizar)
		    	{
			        $html2pdf->Output('boleto_pdf.pdf', "D");
			        return true;
		    	}
		    	if($path)
		    	{
		    		$html2pdf->Output($path, "F");
		    	}
		    }
		    catch(HTML2PDF_exception $e)
		    {
		        echo $e;
		        return false;
		    }
		}
		return false;
	}

	/**
	 * processarRetorno Obtem o arquivo-retorno, para posterior tratamento
	 * @author Jorge <jorge@giusoft.com.br>
	 * @param  string $path Caminho do arquivo para processar o arquivo-retorno
	 * @return array       Transações tratadas pelo método obterDadosTransacoes
	 */
	public function processarRetorno($path='retorno.txt')
	{
		$arquivo_retorno = file($path);
		$transacoes = array();
		foreach ($arquivo_retorno as $key => $value) 
		{
			# code...
			if ($value[0] == '1')
				$transacoes[] = $value;
		}
		return $this->obterDadosTransacoes($transacoes);
	}

	/**
	 * obterDadosTransacoes Trata os dados obtidos pelo arquivo retorno, adicionado os valores em um array
	 * @author Jorge <jorge@giusoft.com.br>
	 * @param  array  $transacoes Transações obtidas pelo arquivo-retorno Bradesco
	 * @return array             Transações armazenadas em array
	 */
	public function obterDadosTransacoes($transacoes = array()){
		$arrays_transacoes = array();
		foreach ($transacoes as $key => $value) {
			# code...
			# 1 - Tratamento do codigo de ocorrencia
			# 2 - Tratar datas como datatime
			# 3 - tratar valores como casas decimais com ponto
			# 4 - tratar ocorrencias [adicionar o codigo e descricao]
			$data = array();
			$data['id_registro'] = substr($value, 0, 1);
			$data['inscricao_empresa'] = substr($value, 1, 2); 
			$data['id_empresa'] = substr($value,3, 14);
			$data['id_beneficiario_banco'] = substr($value, 20,17);
			$data['controle_participante'] = substr($value, 37, 25);
			$data['id_titulo_banco'] = substr($value, 70, 12);
			$data['parcial'] = substr($value, 105,2);
			$data['carteira'] = substr($value, 107,1);
			$data['id_ocorrencia'] = substr($value, 108,2); //Fazer o tratamento deste valor
			$data['motivo_ocorrencia'] = $this->motivo_ocorrencia($data['id_ocorrencia']);
			$data['data_ocorencia'] =  DateTime::createFromFormat('dmy', substr($value, 110, 6));
			$data['numero_documento'] = substr($value, 126, 20);
			$data['data_titulo'] = DateTime::createFromFormat('dmy', substr($value, 146, 6));
			$data['valor_titulo'] = number_format(substr($value, 152, 13),2,',','');
			$data['despesas_ocorrencia'] = number_format(substr($value, 175, 13), 2,',','');
			$data['desconto_concedido'] = number_format(substr($value, 240, 13),2,',','');
			$data['valor_pago'] = number_format(substr($value, 253, 13),2,',','.');
			$data['juros_mora'] = number_format(substr($value, 266, 13),2,',','');
			$data['motivo_codigo_ocorrencia'] = substr($value, 294, 1);
			$data['data_credito'] = DateTime::createFromFormat('dmy', substr($value, 295, 6));
			$data['motivo_rejeicao'] = substr($value, 318, 10);
			$data['numero_cartorio'] = substr($value, 368, 2);
			$data['numero_registro'] = substr($value, 394, 6);
			$arrays_transacoes[] = $data;
		}
		return $arrays_transacoes;
	}
}
$dadosCertificado=array("pathPfx" => "/var/www/html/certificados/giusoft.pfx","password" => "giusoft");
$dadosBeneficiario = array();
$dadosBeneficiario['cnpj']="01108339000179";
$dadosBeneficiario['razaoSocial']="GiuSoft Tecnologia Ltda-EPP";
$dadosBeneficiario['email']="adm@giusoft.com.br";
$dadosBeneficiario['nCarteira']="09";
$dadosBeneficiario['agencia']="6643";//6643-5//6643
$dadosBeneficiario['conta']="0044109"; // 44109-0 // 0044109
//$dadosBeneficiario['contrato']="2920977";

$bradesco = new BradescoOnline($dadosBeneficiario,$dadosCertificado);
$dados="";
$dados['vlNominalTitulo']="409.40"; //128.877
$dados['dtVencimentoTitulo']="2019-12-01";
$dados['nomePagador']="Bruno Coelho ferreira";
$dados['logradouroPagador']="Rua itagi";
$dados['nuLogradouroPagador']="20";
$dados['complementoLogradouroPagador']="";
$dados['cepPagador']="42700-000";
$dados['municipioPagador']="Lauro de Freitas";
$dados['nuCpfcnpjPagador']="027.990.235-25";
$dados['nuTitulo']="10";
$dados['bairroPagador']="CENTRO";
$dados['ufPagador']="BA";

// $email['remetente'] = 'contatestmail@gmail.com';
// $email['destinatario'] = 'jorge@giusoft.com.br';
// $email['assunto'] = 'Envio do arquivo boleto_pdf_N.pdf';
// $email['conteudo'] = '<b>Este envio de boleto est&aacute; utilizando TAG HTML</b>';



//$b->dadosBoleto($dados);
//$b->registra();
//var_dump($b->processarRetorno());
//var_dump($b->boleto);exit;
//$bradesco->gerarBoleto(1,$email);
//$b->sendMail();


?>