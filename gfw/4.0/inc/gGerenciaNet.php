<?php
$gPathLib = "/var/www/html/gfw/4.0/inc/lib/";
require $gPathLib.'gerencianet/vendor/autoload.php';

use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

class gGerenciaNet extends Gerencianet{

	private $token					= "";
	private $sandBox				= true;
	private $items 					= array();
	private $customer 				= array();
	private $discount 				= array();
	private $configurations 		= array();
	private $conditional_discount 	= array();
	private $bankingBillet 			= array();
	private $metadata				= "";

	public $request					= "";
	public $response				= "";

	public $msgErro					= array();

	public $charge_id				= 0;



	public function __construct($clientId,$clientSecret,$sandbox=true)
	{
		parent::__construct(array('client_id' => $clientId, 'client_secret' => $clientSecret, 'sandbox' =>$sandbox));
		$this->sandBox=$sandbox;
		// $this->sandBox = true;
		$this->token($clientId,$clientSecret);
	}

	private function envia($endPoint,$headers,$body,$metodo){
		$urlBase = "https://cobrancas-h.api.efipay.com.br/".$endPoint;
		if(!$this->sandBox)
			$urlBase="https://cobrancas.api.efipay.com.br/".$endPoint;

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $urlBase,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $metodo,
			CURLOPT_POSTFIELDS => $body,
			CURLOPT_HTTPHEADER => $headers
		));
	
		$response = curl_exec($curl);
		curl_close($curl);

		return $response;

	}

	private function token($clientId,$clientSecret){
		$endPoint="v1/authorize";
		$headers=["Authorization: Basic ".base64_encode($clientId . ":" . $clientSecret),"Content-Type: application/json"];
		$body='{"grant_type": "client_credentials"}';
		$metodo="POST";
		$token = $this->envia($endPoint,$headers,$body,$metodo);
		$token = json_decode($token,true);
		if($token['access_token'] <> ""){
			$this->token = $token['token_type'].' '.$token['access_token'];
		}
		else{
			$this->msgErro[] = "Client ID e/ou Client Secret incorretos.";
		}
	}

	public function dadosBoleto($cliente,$servico,$desconto,$metadata=""){

		//Dados do cliente
		if(isset($cliente['cpf'])){
			$this->customer['name']=$this->limpaString($cliente['nome']);
			$this->customer['cpf']=$this->soNumeros($cliente['cpf']);
			$this->customer['phone_number']=$this->soNumeros($cliente['telefone']);	
			$this->bankingBillet['customer'] = $this->customer;
		}
		else{
			//$this->customer['phone_number']=$this->soNumeros($cliente['telefone']);	
			$this->customer['corporate_name']=$this->limpaString($cliente['razao_social']);
			$this->customer['cnpj']=$this->soNumeros($cliente['cnpj']);
			$this->bankingBillet['customer']['phone_number'] = $this->soNumeros($cliente['telefone']);
			$this->bankingBillet['customer']['juridical_person'] = $this->customer;
		}
		
		//Items do boleto
		$this->items['name'] = $this->limpaString($servico['nome']);
		$this->items['amount'] = (int) $servico['quantidade'] == 0 ? 1 : (int) $servico['quantidade'];
		$this->items['value'] = (int) number_format($servico['valor'], 2, '', '');
		if(isset($servico['repasses'])){
			$this->items['marketplace']=array('repasses'=>$servico['repasses']);
		}

		//Configuração de descontos
		$this->discount['type'] = 'percentage';
		$this->discount['value'] =(int) number_format($desconto['desconto'], 2, '', '');

		//Juros e multa (Em %)
		$this->configurations['fine'] = number_format($servico['multa'], 2, '', '');
		$this->configurations['interest'] = number_format($servico['juros'], 2, '', '');

		// Desconto condicional
		$this->conditional_discount['type'] 		= 'percentage';
		$this->conditional_discount['value'] 		= (int) number_format($desconto['desconto_condicional'], 2, '', '');
		$this->conditional_discount['until_date'] 	= $desconto['data_desconto_condicional'];

		//Dados gerais do boleto
		$this->bankingBillet['expire_at'] = $servico['data_vencimento'];
		$this->bankingBillet['message'] = $servico['messagem'];
		if($this->discount['value'] > 0)
			$this->bankingBillet['discount'] = $this->discount;
		
		//$this->bankingBillet['conditional_discount'] = $this->conditional_discount;

		//URL de notificação
		if($metadata){
			$this->metadata['notification_url'] = $metadata['notification_url'];
			$this->metadata['custom_id'] = $metadata['custom_id'];
		}
		

	}

	public function enviaBoletoOneStep(){
		$body = array(
			'items' => array($this->items),
			'metadata' => $this->metadata,
			'payment' => array('banking_billet' => $this->bankingBillet)
		);
		$this->request=json_encode($body);
		try {
			
			$headers=["Authorization: ".$this->token,"Content-Type: application/json"];
			$pay_charge = $this->envia("v1/charge/one-step",$headers,json_encode($body),'POST');
			$this->response=$pay_charge;
			$pay_charge=json_decode($pay_charge,true);
			if($pay_charge['code']==200){
				$this->charge_id =(int) $pay_charge['data']['charge_id'];
				return true;			
			}
			else{
				if(is_array($pay_charge['error_description']))
					$erroDesc = implode(" / ",$pay_charge['error_description']);
				else
					$erroDesc = $pay_charge['error_description'];

				$this->msgErro[] = $pay_charge['code']." : ".$erroDesc;
			}
		}
		catch (Exception $e) {
			 $this->msgErro[] = $e->getMessage();
		}
		return false;
	}

	public function cancelaBoleto($idBoleto){
		
		try {
		    $charge = $this->cancelCharge(array('id' => (int) $idBoleto), []);
		    if($charge['code']==200)
		    	return true;
		} catch (GerencianetException $e) {
			$this->msgErro[] = $e->code." - ".$e->error." : ".$e->errorDescription;
		} catch (Exception $e) {
		    $this->msgErro[] = $e->getMessage();
		}
		return false;
	}

	public function verificaStatus($token){
		try{
			//$chargeNotification = $this->getNotification(array('token' => $token), []);
			$headers=["Authorization: ".$this->token,"Content-Type: application/json"];
			$chargeNotification = $this->envia("v1/notification/".$token,$headers,"",'GET');
			//file_put_contents("/mnt/dados/gn.log",$chargeNotification."\n",FILE_APPEND);
			$chargeNotification = json_decode($chargeNotification,true);
			$lastKey = end($chargeNotification['data']);
			return $lastKey;
		}
		catch (Exception $e) {}
	}

	public function alterarNotificacao($chargeId,$notificaionURL, $customId){
		$params = [
			'id' => $chargeId
		];
		$body = [
			'custom_id' => (string) $customId, // associar transação Gerencianet com seu identificador próprio
			'notification_url' => $notificaionURL // url de notificação
		];
		
		try {
			$charge = $this->updateChargeMetadata($params, $body);
			return true;
		} catch (GerencianetException $e) {
			print_r($e->code);
			print_r($e->error);
			print_r($e->errorDescription);
		} catch (Exception $e) {
			print_r($e->getMessage());
		}
		return false;

	}

	public function verificarDadosTransacao($chargeId){
		$params = [
			'id' => (string) $chargeId // $charge_id refere-se ao ID da transação ("charge_id")
		];
		try {
			// $charge = $this->detailCharge($params, []);
			$headers=["Authorization: ".$this->token,"Content-Type: application/json"];
			$charge = $this->envia("v1/charge/".$chargeId,$headers,"",'GET');
			return json_decode($charge,true);
		}catch (Exception $e) {
			print_r($e->getMessage());
		}
		return false;
	}

	public function createPaymentLink($itens,$metadata="",$vencimento="",$mensagem="",$descontoBoleto=0,$descontoCartao=0){
		
		$items = array($itens);
		$body = [
			'items' => $items
		];
		if($metadata){
			$body['metadata'] = $metadata;
		}
		try{
			$charge = $this->createCharge([], $body);
			// echo "<pre>";
			// var_dump($charge);exit;
			if($charge['code']== 200){
				$body = [
					//'billet_discount' => 0, // desconto, em reais, caso o pagador escolha boleto (5000 equivale a R$ 50,00)
					//'card_discount' => 0, // desconto, em reais, caso o pagador escolha cartão (3000 equivale a R$ 30,00)
					'message' => $mensagem, // mensagem para o pagador com até 80 caracteres
					'expire_at' => $vencimento, // data de vencimento da tela de pagamento e do próprio boleto
					'request_delivery_address' => false, // solicitar endereço de entrega do comprador?
					'payment_method' => 'all' // formas de pagamento disponíveis: all, banking_billet (boleto), credit_card (cartão)
				];
				if((int) $descontoBoleto > 0)
					$body['billet_discount']=(int) $descontoBoleto ;
				if((int) $descontoCartao > 0)
					$body['card_discount']=(int) $descontoCartao ;
				$response = $this->linkCharge(['id' => $charge['data']['charge_id']],$body);
				$this->response=json_encode($response);
				// echo "<pre>";
				// var_dump($this->response);
				// exit;
				if($response['code']== 200)
				{
					return $response['data'];
				}
				else{
					return false;
				}
			}
			else
				return false;
		}
		catch (GerencianetException $e) {
			print_r($e->code);
			print_r($e->error);
			print_r($e->errorDescription);
		}
		catch (Exception $e) {
			print_r($e->getMessage());
		}
	}
	/**
	 * Remove acentos de uma string
	 * @param  string $string
	 * @return string
	 */
	public function limpaString($string)
	{
		$t_st = str_replace(array ("/", "-", ".", "º", ",","ª","´","'","´"), "", $string);
        $string = $t_st;
        //$string = str_replace(array("Ã§"),"c",$string);
	    $string = str_replace(array('á','à','ã'),"a",$string);
	    $string = str_replace(array("À","Á","Ã"),"A",$string);
	    $string = str_replace(array("é","è","ê"),"e",$string);
	    $string = str_replace(array("É","È","Ê"),"E",$string);
	    $string = str_replace(array("í","ì"),"i",$string);
	    $string = str_replace(array("Í","Ì"),"I",$string);
	    $string = str_replace(array("ó","ò","õ","ô"),"o",$string);
	    $string = str_replace(array("Ò","Ó","Õ","Ô"),"O",$string);
	    $string = str_replace(array("ú","ù"),"u",$string);
	    $string = str_replace(array("Ú","Ù"),"U",$string);
	    $string = str_replace(array("Ç"),"C",$string);
	    $string = str_replace(array("ç"),"c",$string);
	    return $string;
	}

	/**
	 * Deixa apenas números da String
	 */
	public function soNumeros($str) {
    	return preg_replace("/[^0-9]/", "", $str);
	}
	
}
?>
