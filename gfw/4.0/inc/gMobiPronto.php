<?php

/**
 * Classe responsável por fazer a integração entre os sistemas da GiuSoft e o MobiPronto, plataforma dedicada à comunicação móvel.
*
* @author André Luiz
* @version 1.0 21-07-2017 09:16
*/
class gMobiPronto
{

	private $h_wsdl = '';
	private $p_wsdl = 'http://www.mpgateway.com/v_3_00/sms/service.asmx?wsdl';
	private $errors = '';
	private $params = array();
	private $operation_mode = 'H';


	private $template_MPG_Send_LMS = array(
			'CREDENCIAL' => array('required' => true, 'min' => 1, 'max' => 40),
			'TOKEN' => array('required' => true, 'min' => 1, 'max' => 6),
			'PRINCIPAL_USER' => array('required' => false, 'min' => 1, 'max' => 50), // Segundo a documentacao é o campo (tag) é obrigatório, mas pode uma string vazia... Logo (o conteúdo) não é obrigatório.
			'AUX_USER' => array('required' => true, 'min' => 1, 'max' => 20),
			'MOBILE' => array('required' => true, 'min' => 1, 'max' => 16),
			'MESSAGE' => array('required' => true, 'min' => 1, 'max' => 160)
	);

	private $template_MPG_Credits = array(
			'CREDENCIAL' => array('required' => true, 'min' => 1, 'max' => 40),
			'TOKEN' => array('required' => true, 'min' => 1, 'max' => 6),
			'v_st_Status' => array('required' => false, 'min' => 1, 'max' => 3)
	);

	private $template_MPG_Calculate_Message_Length_UTF8_or_UTF16 = array(
			'CREDENCIAL' => array('required' => true, 'min' => 1, 'max' => 40),
			'TOKEN' => array('required' => true, 'min' => 1, 'max' => 6),
			'MESSAGE' => array('required' => true, 'min' => 1, 'max' => 999999999)
	);

	private $template_MPG_Query01 = array(
			'CREDENCIAL' => array('required' => true, 'min' => 1, 'max' => 40),
			'TOKEN' => array('required' => true, 'min' => 1, 'max' => 6),
			'START_DATE' => array('required' => true, 'min' => 10, 'max' => 10),
			'END_DATE' => array('required' => true, 'min' => 10, 'max' => 10),
			'AUX_USER' => array('required' => false, 'min' => 1, 'max' => 20),
			'MOBILE' => array('required' => false, 'min' => 15, 'max' => 15),
			'STATUS_CODE' => array('required' => true, 'min' => 1, 'max' => 1)
	);

	function __construct($params = array())
	{
		foreach ($params as $k => $v)
		{
			if($k == 'operation_mode')
				$this->operation_mode = $v;
				else
					$this->params[$k] = $v;
		}

		var_dump($params,$this->params);
	}

	/**
	 * Apelido para a funcão MPG_Send_LMS
	 * visando facilitar o entendimento e utilização da classe.
	 *
	 * MPG_Send_LMS é o nome orignal do serviço.
	 *
	 * @param array $data
	 * @return string result
	 */
	public function sendSMS($data)
	{
		return $this->MPG_Send_LMS($data);
	}

	/**
	 * Faz o envio de mensagens via SMS através do nosso Gateway (MobiPronto).
	 * Mensagens com até 160 caracteres podem ser enviadas para
	 * qualquer país que o MobiPronto possui cobertura.
	 *
	 * @param array $data
	 */
	private function MPG_Send_LMS($data)
	{
		$sai = false;

		$data['credencial'] = $this->params['credencial'];
		$data['token'] = $this->params['token'];

		if($this->validateData($data, 'template_MPG_Send_LMS'))
		{
			// Monta a mensagem SOAP
			$content = '<?xml version="1.0" encoding="utf-8"?>
						<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
	  						<soap:Body>
		    					<MPG_Send_LMS xmlns="MobileProntoMPGateway">
			      					<Credencial>'.$data['credencial'].'</Credencial>
			      					<Token>'.$data['token'].'</Token>
			      					<Principal_User>'.$data['principal_user'].'</Principal_User>
			      					<Aux_User>'.$data['aux_user'].'</Aux_User>
			      					<Mobile>'.$data['mobile'].'</Mobile>
			      					<Message>'.$data['message'].'</Message>
		    					</MPG_Send_LMS>
	  						</soap:Body>
						</soap:Envelope>';
				
			$ret = $this->sendSOAP($content, 'MPG_Send_LMS');
				
			// trata o resultado
			if($ret !== false)
			{
				$code = $ret->MPG_Send_LMSResponse->MPG_Send_LMSResult;
				$code = substr($code,0,3);
				switch ($code)
				{
					case '000':
						$sai = true;
						break;
					case '001':
						$this->errors[] = 'Erro ' . $code . ': Credencial inválida';
						break;
					case '005':
						$this->errors[] = 'Erro ' . $code . ': MOBILE com formato inválido';
						break;
					case '008':
						$this->errors[] = 'Erro ' . $code . ': MESSAGE ou MESSAGE + NOME_PROJETO com mais de 160 posições ou SMS concatenado com mais de 1000 posições';
						break;
					case '009':
						$this->errors[] = 'Erro ' . $code . ': Créditos insuficientes em conta';
						break;
					case '010':
						$this->errors[] = 'Erro ' . $code . ': Gateway SMS da conta bloqueado';
						break;
					case '012':
						$this->errors[] = 'Erro ' . $code . ': MOBILE correto, porém com crítica';
						break;
					case '013':
						$this->errors[] = 'Erro ' . $code . ': Conteúdo da mensagem inválido ou vazio';
						break;
					case '015':
						$this->errors[] = 'Erro ' . $code . ': País sem cobertura ou não aceita mensagens concatenadas (SMS Longo)';
						break;
					case '016':
						$this->errors[] = 'Erro ' . $code . ': MOBILE com código de área inválido';
						break;
					case '017':
						$this->errors[] = 'Erro ' . $code . ': Operadora não autorizada para esta credencial';
						break;
					case '018':
						$this->errors[] = 'Erro ' . $code . ': Token inválido';
						break;
					case '022':
						$this->errors[] = 'Erro ' . $code . ': Conta atingiu o limite de envio do dia';
						break;
					default:
						if(intval($code) >= 800 && intval($code) <= 899)
							$this->errors[] = 'Erro ' . $code . ': Falha no Gateway';
							if(intval($code) == 900 )
								$this->errors[] = 'Erro ' . $code . ': Erro de autenticação ou limite de segurança excedido';
								if(intval($code) >= 901 && intval($code) <= 999)
									$this->errors[] = 'Erro ' . $code . ': Erro no acesso as operadoras';
									break;
				}

			}
			else
			{
				// Erro no cURL: a mensagem de erro já foi registrada no metodo sendSOAP()
				$sai = false;
			}
		}

		return $sai;
	}

	/**
	 * Apelido para a funcao MPG_Credits.
	 *
	 * Obtem o saldo de crédidos disponíveis.
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function getCredits()
	{
		return $this->MPG_Credits();
	}

	/**
	 *  Consultar o número de créditos disponíveis em sua conta.
	 * @return boolean
	 */
	private function MPG_Credits()
	{
		$sai = false;
		echo  'MPG Credits ...  <br/>';
		$data['credencial'] = $this->params['credencial'];
		$data['token'] = $this->params['token'];

		if($this->validateData($data, 'template_MPG_Credits'))
		{
			$content = '<?xml version="1.0" encoding="utf-8"?>
						<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
						  <soap:Body>
						    <MPG_Credits xmlns="MobileProntoMPGateway">
						      <Credencial>'.$data['credencial'].'</Credencial>
			      			  <Token>'.$data['token'].'</Token>
						      <v_st_Status>000</v_st_Status>
						    </MPG_Credits>
						  </soap:Body>
						</soap:Envelope>';
			$ret = $this->sendSOAP($content, 'MPG_Credits');

			if($ret !== false)
			{
				if($ret->MPG_CreditsResponse->MPG_CreditsResult == '-1')
				{
					switch ($ret->MPG_CreditsResponse->v_st_Status)
					{
						case '001':
							$this->errors[] = 'Erro ' . $code . ': Credencial inválida';
							break;
						case '019':
							$this->errors[] = 'Erro ' . $code . ': Token inválido';
							break;
						case '022':
							$this->errors[] = 'Erro ' . $code . ': Conta atingiu o limite de envio do dia';
							break;
						default:
							if(intval($code) >= 800 && intval($code) <= 899)
								$this->errors[] = 'Erro ' . $code . ': Falha no Gateway';
								if(intval($code) == 900 )
									$this->errors[] = 'Erro ' . $code . ': Erro de autenticação ou limite de segurança excedido';
									break;
					}
				}
				else
				{
					$sai = $ret->MPG_CreditsResponse->MPG_CreditsResult;
				}
			}
			else
			{
				// Erro no cURL: a mensagem de erro já foi registrada no metodo sendSOAP()
				$sai = false;
				// 				echo $this->getErrors();
			}

				
		}
		else
		{
			// 			echo $this->getErrors();
		}

		return $sai;
	}

	/**
	 * Apelido para MPG_Calculate_Message_Length_UTF8_or_UTF16
	 *
	 * Returna a quantidade de caracteres da mensagem
	 *
	 * @param string $msg
	 * @return unknown
	 */
	function messageLength($msg)
	{
		return $this->MPG_Calculate_Message_Length_UTF8_or_UTF16($msg);
	}

	/**
	 * O método é utilizado para calcular o número de caracteres (UTF-8 ou UTF-16)
	 * presentes no conteúdo da mensagem.
	 *
	 * O tamanho da mensagem não considera os caracteres do “Remetente do SMS“
	 * que pode ter até 9 caracteres, incluindo os “:” (dois pontos).
	 *
	 * @param string $msg
	 * @return unknown
	 */
	private function MPG_Calculate_Message_Length_UTF8_or_UTF16($msg)
	{
		$data['credencial'] = $this->params['credencial'];
		$data['token'] = $this->params['token'];
		$data['message'] = $msg;

		$sai = false;
		if($this->validateData($data, 'template_MPG_Calculate_Message_Length_UTF8_or_UTF16'))
		{
			$content = '<?xml version="1.0" encoding="utf-8"?>
						<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
						  <soap:Body>
						    <MPG_Calculate_Message_Length_UTF8_or_UTF16 xmlns="MobileProntoMPGateway">
						      <Credencial>'.$data['credencial'].'</Credencial>
						      <Token>'.$data['token'].'</Token>
						      <Message>'.$data['message'].'</Message>
						    </MPG_Calculate_Message_Length_UTF8_or_UTF16>
						  </soap:Body>
						</soap:Envelope>';
			$ret = $this->sendSOAP($content, 'MPG_Calculate_Message_Length_UTF8_or_UTF16');
				
			if($ret !== false)
			{
				$qtd = (int) $ret->MPG_Calculate_Message_Length_UTF8_or_UTF16Response->MPG_Calculate_Message_Length_UTF8_or_UTF16Result;

				if($qtd < 0)
				{
					switch ($qtd)
					{
						case -1:
							$this->errors[] = 'Erro ' . $qtd . ': Credencial inválida';
							break;
						case -2:
							$this->errors[] = 'Erro ' . $qtd . ': Token inválido';
							break;
						case -9:
							$this->errors[] = 'Erro ' . $qtd . ': Erro interno, favor entrar em contato com o suporte';
							break;
						default:
							$this->errors[] = 'Erro ' . $qtd . ': código não identificado';
							break;
					}
				}
				else
				{
					$sai = $qtd;
				}
			}
		}


		return $sai;
	}

	/**
	 * Apelido para MPG_Query01
	 *
	 * Retorna um relatorio de SMS enviadas em determinado periodo
	 *
	 * @param array $data
	 * @return unknown
	 */
	function getReport($data)
	{
		return $this->MPG_Query01($data);
	}

	/**
	 * O método é utilizado para consultar as mensagens de texto SMS
	 *  enviadas através da sua conta em um determinado intervalo de tempo.
	 *
	 *  O retorno é um XML de até 1000 linhas, no qual os campos são:
	 *  código auxiliar, data de envio, mobile, flag de recebimento, código de retorno e message.
	 *
	 * @param array $data
	 */
	private function MPG_Query01($data)
	{
		$sai = false;
		$data['credencial'] = $this->params['credencial'];
		$data['token'] = $this->params['token'];

		if($this->validateData($data, 'template_MPG_Query01'))
		{
			$content = '<?xml version="1.0" encoding="utf-8"?>
						<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
						  <soap:Body>
						    <MPG_Query01 xmlns="MobileProntoMPGateway">
						      <Credencial>'.$data['credencial'].'</Credencial>
						      <Token>'.$data['token'].'</Token>
						      <Start_Date>'.$data['start_date'].'</Start_Date>
						      <End_Date>'.$data['end_date'].'</End_Date>
						      <Aux_User>'.$data['aux_user'].'</Aux_User>
						      <Mobile>'.$data['mobile'].'</Mobile>
						      <Status_Code>'.$data['status_code'].'</Status_Code>
						      <Status>'.$data['status'].'</Status>
						    </MPG_Query01>
						  </soap:Body>
						</soap:Envelope>';
				
			$ret = $this->sendSOAP($content, 'MPG_Query01');
			if($ret !== false)
			{
				$report = (String) $ret->MPG_Query01Response->MPG_Query01Result;
				$report = str_replace('%3c%3fxml+version%3d%22%221.0%22%22%3f%3e', '', $report);
				$report = urldecode($report);

				$r = simplexml_load_string($report);
				$sai = (array) $r->children();
			}
		}

		return $sai;

	}

	/**
	 * Verifica se os dados estão no formato esperado.
	 *
	 * @param array $data
	 * @param string $template
	 * @return boolean
	 */
	private function validateData(&$data,$template)
	{
		$sai = true;
		$this->errors = '';
		foreach ($this->$template as $key => $value)
		{
			// Verifica se é obrigatorio
			if($value['required'] === true)
			{
				if($data[strtolower($key)] == '')
				{
					$sai = false;
					$this->errors[] = 'O campo ' . $key . ' é obrigatorio.';
				}
			}

			// Se o campo foi informado
			if(isset($data[strtolower($key)]))
			{
				// Verifica se o tamanho está correto
				$length = strlen($data[strtolower($key)]);

				if($length < 1 || $length > $value['max'])
				{
					$sai = false;
					if($value['min'] == $value['max'])
					{
						$c = $value['min'] > 1 ? 'caracteres' : 'caractere';
						$this->errors[] = 'O campo ' . $key . ' deve ter exatamente ' . $value['min'] . " $c";
					}
					else
					{
						$this->errors[] = 'O campo ' . $key . ' deve ter entre ' . $value['min'] . ' e '. $value['max'] . ' caracteres';
					}
				}
			}
			else
			{
				$data[strtolower($key)] = ''; // Adiciona a chave pois precisa existir, ao menos, a tag vazia.
			}
		}

		return $sai;

	}

	/**
	 * Envia conteudo para o web service da MobiPronto
	 *
	 * @param unknown $content
	 * @param unknown $soapAction
	 * @return string|mixed
	 */
	private function sendSOAP($content, $soapAction)
	{

		$content = preg_replace("/\t\R/", "", $content); // remove tabs e quebras de linhas

		$header = array(
				"Content-type: text/xml;charset=\"utf-8\"",
				"Accept: text/xml",
				"Cache-Control: no-cache",
				"Pragma: no-cache",
				"SOAPAction: MobileProntoMPGateway/".$soapAction,
				"Content-length: ".strlen($content),
		);


		if($this->operation_mode == 'P')
			$url = $this->p_wsdl;
			else
				$url = $this->h_wsdl;

				$soap_do = curl_init();
				curl_setopt($soap_do, CURLOPT_URL, $url );
				curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($soap_do, CURLOPT_TIMEOUT,        10);
				curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
				curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($soap_do, CURLOPT_POST,           true );
				curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $content);
				curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $header);

				$response = curl_exec($soap_do);

				if( $response === false)
				{
					$this->errors[] = 'cURL errors: ' . curl_error($soap_do);
					$sai = false;
				}
				else
				{
					$xml = simplexml_load_string($response, null, null, 'http://schemas.xmlsoap.org/soap/envelope/');

					if ($xml === false)
					{
						$sai = false;
						$msg =  "Falha ao carregar XML: ";
						foreach(libxml_get_errors() as $error)
						{
							$msg .= "<br>" . $error->message;
						}
						$this->erros[] = $msg;
					}
					else
					{
						$sai = $xml->Body->children();
					}

				}
				curl_close($soap_do);

				return $sai;
	}


	/**
	 * Retorna todos os erros
	 *
	 * @return string
	 */
	public function getErrors()
	{
		return join('<br/>', $this->errors);
	}
}

/*

// EXEMPLOS

$params = '';
$params['credencial'] = '0ECFB06F3EF4CA0C8B2AED831831192CAEF9B3EA';
$params['token'] = '320Af7'; // Deve ser usado o campo Token Gateways disponibilizado em http://www.mobipronto.net/iqc_MPC_Config_Conta.aspx
$params['operation_mode'] = 'P';

$mobi = new gMobiPronto($params);

$saldo = $mobi->getCredits();
if($saldo === false)
{
	echo '<br/> Falha ao consultar saldo. <br/>' . $mobi->getErrors();
}
else
{
	echo '<br/> Saldo antes do envio: ' . $saldo;
}

$data = '';
$data['principal_user'] = 1;
$data['aux_user'] = 1;
$data['mobile'] = '+55(75)991876478';
$data['message'] = 'Teste de envio de SMS usando API MobiPronto!';

$length = $mobi->messageLength($data['message']);
if($length === false)
{
	echo '<br/> Falha ao calcular tamanho da mensagem. <br/>' . $mobi->getErrors();
}
else
{
	echo '<br/> Tamanho da mensagem (em caracteres): ' . $length;
}

// $send = $mobi->sendSMS($data);

if($send === false)
{
	echo '<br/> Falha ao enviar SMS. ' . $mobi->getErrors();
}
else
{
	echo '<br/> SMS enviado com sucesso!';
}

$saldo = $mobi->getCredits();
if($saldo === false)
{
	echo '<br/> Falha ao consultar saldo. <br/>' . $mobi->getErrors();
}
else
{
	echo '<br/> Saldo após o envio: ' . $saldo;
}

$data = '';
$data['start_date'] = '29/08/2017';
$data['end_date'] = '29/08/2017';
// $data['aux_user'] = '';
// $data['mobile'] = '';
$data['status_code'] = '0';

$report =  $mobi->getReport($data);

if($report === false)
{
	echo '<br/> Falha ao obter relatório. <br/>' . $mobi->getErrors();
}
else
{
	echo '<br/> Relatório: <pre>' . print_r($report,true) . '<pre>';
}
*/
