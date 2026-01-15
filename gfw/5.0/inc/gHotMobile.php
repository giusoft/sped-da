<?
/**
 * Classe responsável pela integração via WS com a HotMobile
 */
class HotMobile{

	private string $login = "sindicato@sindautobahia.com.br";
	private string $senha = "sindauto.123";
	private $numeros = [];
	private string $url = "http://painel.hotmobile.com.br/ws/ws.asmx?WSDL";

	function __construct($empresa) {
		if (strtoupper((string) $empresa) === "SINDAUTO") {
			$this->login = "sindicato@sindautobahia.com.br";
			$this->senha = "sindauto.123";
		}
	}

	public function EnviarSMS($msg,$remetente="",$numeros="") {
		$send="<?xml version=\"1.0\" encoding=\"utf-8\"?>
		<soap12:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap12=\"http://www.w3.org/2003/05/soap-envelope\">
		  <soap12:Body>
			<EnviarSMS xmlns=\"http://tempuri2.org/\">
		      <mensagemRQ>
		      	<Login>".$this->login."</Login>
		      	<Senha>".$this->senha."</Senha>
		        <Mensagem>$msg</Mensagem>
		        <Remetente>$remetente</Remetente>
		        <CentroDeCusto></CentroDeCusto>
		        <Flash>false</Flash>
		        <AlphaSize>0</AlphaSize>
		        <ListNumeros>";
		        if (is_array($numeros)) {
		        	foreach ($numeros as $n) {
		        		$send.="<NumeroRQ>
		            				<Numero>$n</Numero>
		          				</NumeroRQ>";
		        	}
		        } else {
		        	$send .= "<NumeroRQ>
		            			<Numero>$numeros</Numero>
		          			</NumeroRQ>";
		        }

        $send.="</ListNumeros>
		      </mensagemRQ>
		    </EnviarSMS>
		 </soap12:Body>
		</soap12:Envelope>
		    ";
		preg_replace('/[ \t]+/', ' ', (string) preg_replace('/[\r\n]+/', "\n", $send));
		return $this->sendSOAP($send,"EnviarSMS");

	}

	private function sendSOAP(string $content, string $soapAction)
	{

		$content = preg_replace("/\t\R/", "", $content); // remove tabs e quebras de linhas

		$header = [
			"Content-type: application/soap+xml;charset=\"utf-8\"",
			"Accept: text/xml",
			"Cache-Control: no-cache",
			"Pragma: no-cache",
			"SOAPAction: http://tempuri2.org/".$soapAction,
			"Content-length: ".strlen($content),
		];

		$soap_do = curl_init();
		curl_setopt($soap_do, CURLOPT_URL, $this->url );
		curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($soap_do, CURLOPT_TIMEOUT,        10);
		curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($soap_do, CURLOPT_POST,           true );
		curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $content);
		curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $header);

		$response = curl_exec($soap_do);

		$response='<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><soap:Body><EnviarSMSResponse xmlns="http://tempuri2.org/"><EnviarSMSResult><Erro>false</Erro><MensagemRetorno>Sua Mensagem está sendo enviada, use o Id para buscar o resultado do envio</MensagemRetorno><MensagemId>269392085</MensagemId></EnviarSMSResult></EnviarSMSResponse></soap:Body></soap:Envelope>';
		$remover = [];
		$remover[] = "</EnviarSMSResponse></soap:Body></soap:Envelope>";
		$remover[] = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><soap:Body><EnviarSMSResponse xmlns="http://tempuri2.org/">';
		$response  =  str_replace($remover,"",$response);
		if ($response === false) {
			$this->errors[] = 'cURL errors: ' . curl_error($soap_do);
			$sai = false;
		} else {
			$xml = simplexml_load_string($response);

			if ($xml === false) {
				$sai = false;
				$msg =  "Falha ao carregar XML: ";
				foreach (libxml_get_errors() as $error) {
					$msg .= "<br>" . $error->message;
				}
				$this->erros[] = $msg;
			} else {
				$sai = $xml->Erro;
			}

		}
		curl_close($soap_do);

		return $sai;
	}
}

//$o = new HotMobile("sindauto");
//$o->EnviarSMS("Bruno Coelho Ferreira","Sindauto",array("5571992339353"));
?>