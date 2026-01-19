<?php

/**
 * Classe de pagamento
 */

class gPayPal{	
	
	/**
	 * config
	 * 
	 * @var array
	 */
    private $config = array();
	
	/**
	 * Requisoes para o sandbox
	 * 
	 * @var boolean
	 */
	private $sandbox = false;
	
	/**
	 * Dados para processamento
	 * 
	 * @var array
	 */
    private $data = array();
	
	
	/**
	 * Grava o retorno das requisicoes
	 * 
	 * @var array
	 */
    private $request_data = array();
	
	
	/**
	 * Grava o retorno das requisicoes
	 * 
	 * @var array
	 */
    private $process_data = array();
	
	/**
	 * Id pedido
	 * @var array
	 */
	private $payment_id = 0;
	
	/**
	 * Lista de itens de um pedido
	 * @var array
	 */
	private $items = array();
    
    
	/**
	 * Construtor da Classe
	 * 
	 * @param string Usuario
	 * @param string Senha
	 * @param string Assinatura
	 * @param array Configuracoes extra
	 */
	function __construct($usr = '', $pwd = '', $signature = '', $secret = '', $client_id = '', $app_id = '', $extra = array()) {
		$usr = $usr? $usr : gVar('paypal.email');
		$pwd = $pwd? $pwd : gVar('paypal.senha');
		$signature = $signature? $signature : gVar('paypal.assinatura');
		$secret = $secret? $secret : gVar('paypal.secret');
		$client_id = $client_id? $client_id : gVar('paypal.clientid');
		$app_id = $app_id? $app_id : gVar('paypal.appid');
		$this->config($usr, $pwd, $signature, $secret, $client_id, $app_id, $extra);
	}
	
	/**
	 * Configuracoes da classe
	 * 
	 * @param string Usuario
	 * @param string Senha
	 * @param string Assinatura
	 * @param array Configuracoes extra
	 */
	function config($usr = '', $pwd = '', $signature = '', $secret = '', $client_id = '', $app_id = '', $extra = array()){
		
		$this->config['auth'] = array(
							'USER' => $usr,
							'PWD' => $pwd,
							'SIGNATURE' => $signature,
							'SECRET' => $secret,
							'CLIENT_ID' => $client_id,
							'APP_ID' => $app_id,
							'VERSION' => '108',
							'LOCALECODE' => 'pt_BR'
							);
		
		$this->config['region'] = array(
							'CURRENCYCODE' => 'BRL',
							'COUNTRYCODE' => 'BR'
							);
		
		$addr = 'http'.(!empty($_SERVER['HTTPS'])? 's' : '').'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
		$cancel_url = $_GET;
		$cancel_url['ExpressCheckoutCancel'] = 1;
		$cancel_url = http_build_query($cancel_url);		
		$cancel_url = $addr.'?'.$cancel_url;
		
		$return_url = $_GET;
		$return_url['ExpressCheckoutReturn'] = 1;
		$return_url = http_build_query($return_url);		
		$return_url = $addr.'?'.$return_url;
		
		$this->config['geral'] = array(
			'CANCELURL' => $cancel_url,
			'RETURNURL' => $return_url
		);
		
		if(!empty($extra['region'])){
			$this->config['region'] = array_merge($this->config['region'],$extra['region']);
		}
		
		if(!empty($extra['geral'])){
			$this->config['geral'] = array_merge($this->config['geral'],$extra['geral']);
		}
			
	}
	
	/**
	 * Retorna um valor configurado
	 * 
	 * @param string Secao
	 * @param string Valor
	 * @return type Retorno
	 */
	function getConfig($category = '', $option = ''){
		$r = (array_key_exists($category, $this->config))? $this->config[$category]: array();
		if(!empty($option) && array_key_exists($option, $r)){
			$r = $r[$option];
		}
		return $r;
	}
	
	/**
	 * Adiciona/Substitui um valor para determinada variavel
	 * 
	 * @param array Variavel
	 * @param type Indice
	 * @param type Valor
	 */
	function set(&$var, $key, $value){
		return $var[$key] = $value;		
	}
	
	/**
	 * Converge valores entre dois arrays
	 * 
	 * @param array Array 1
	 * @param array Array 2
	 */
	function add(&$var, $data){
		return $var = array_merge($var, $data);
	}
	
	/**
	 * Retorna um valor existente em uma variavel
	 * 
	 * @param type Variavel
	 * @param type Indice
	 * @return type Valor
	 */
	function get(&$var, $key){
		if(!empty($key)){
			return (array_key_exists($key, $var))? $var[$key] : '';
		}else{
			return $var;
		}
	}
	
	/**
	 * Limpa os dados de uma variavel
	 */
	function reset(&$var){
       $var = array();
    }
	
	/**
	 * Adiciona/Substitui um valor var $data
	 * 
	 * @param type Indice
	 * @param type Valor
	 */
	function setData($key, $value){
		$this->set($this->data,$key,$value);
    }
	
	/**
	 * Converge um array com var $data
	 * 
	 * @param array Dados
	 */
	function addData($data = array()){
		$this->add($this->data,$data);
	}
	
	/**
	 * Retorna um valor existente na var $data
	 * 
	 * @param type Indice
	 * @return type Valor
	 */
	function getData($key = ''){
		return $this->get($this->data,$key);
    }
    
	/**
	 * Limpa os dados da var $data
	 */
    function resetData(){
		$this->reset($this->data);        
    }
	
	/**
	 * Adiciona/Substitui um valor var $process_data
	 * 
	 * @param type Indice
	 * @param type Valor
	 */
	function setProcessData($key, $value){
		$this->set($this->process_data,$key,$value);
    }
	
	/**
	 * Converge um array com var $process_data
	 * 
	 * @param array Dados
	 */
	function addProcessData($data = array()){
		$this->add($this->process_data,$data);
	}	
	
	/**
	 * Retorna um valor existente na var $process_data
	 * 
	 * @param type Indice
	 * @return type Valor
	 */
	function getProcessData($key = ''){
		return $this->get($this->process_data,$key);
    }
	
	/**
	 * Limpa os dados da var $process_data
	 */
	function resetProcessData(){
		$this->reset($this->process_data);
    }	
	
	/**
	 * Adiciona/Substitui um valor var $request_data
	 * 
	 * @param type Indice
	 * @param type Valor
	 */
	function setRequestData($key, $value = array()){        
		$this->set($this->request_data,$key,$value);
    }
	
	/**
	 * Retorna um valor existente na var $request_data
	 * 
	 * @param type Indice
	 * @return type Valor
	 */
	function getRequestData($key){
		return $this->get($this->request_data,$key);
    }
	
	/**
	 * Limpa os dados da var $request_data
	 */
	function resetRequestData(){
		$this->reset($this->request_data);
    }
	
	/**
	 * Copia dados de var $data para var $process_data
	 */
	function copyDataToProcessData(){
		$args = func_get_args();
		if(count($args)){
			foreach($args as $value){
				$data = $this->getData($value);
				if(!empty($data))
					$this->setProcessData($value, $data);
			}
		}
	}	
	
	/**
	 * Cria um pedido
	 * @param string Identificado do pedido (opcional)
	 * @param string Acao (Default: Sale)
	 */
	function paymentRequest($invnum = '', $act = 'Sale'){
		$id = $this->payment_id;		
		
		if(count($this->items)){
			
			$iteamt = 0;
			$items = array();
			
			foreach($this->items as $id_item => $item_data){
				foreach($item_data as $name => $value){
					$items['L_PAYMENTREQUEST_'.$id.'_'.$name.$id_item] = $value;
				}
				$iteamt += $item_data['AMT'];
			}
			
			$this->setData('PAYMENTREQUEST_'.$id.'_PAYMENTACTION', $act);
			$this->setData('PAYMENTREQUEST_'.$id.'_CURRENCYCODE', $this->getConfig('region', 'CURRENCYCODE'));
			if($invnum)
				$this->setData('PAYMENTREQUEST_'.$id.'_INVNUM', $invnum);

			$this->setData('PAYMENTREQUEST_'.$id.'_AMT', $iteamt);
			$this->setData('PAYMENTREQUEST_'.$id.'_ITEAMT', $iteamt);
			$this->addData($items);
			
			$this->payment_id++;
			$this->items = array();
		}		
		
	
	}
	
	/**
	 * Adiciona um item ao pedido
	 * 
	 * @param string Nome
	 * @param string Descricao
	 * @param float Valor
	 * @param int Quantidade
	 * @param string Categoria (Physical / Digital)
	 */
	function addItem($name = '', $desc = '', $amt = 0, $qty = 1, $category = 'Physical'){
		$this->items[] = array(
								'NAME' => $name,
								'DESC' => $desc,
								'AMT' => $amt,
								'QTY' => $qty,
								'ITEMCATEGORY' => $category
							);
		
	}
	
	/**
	 * Cria um pedido do tipo assinatura
	 * 
	 * @param float Valor
	 * @param int Quantidade de parcelas
	 * @param string Descrição da Assinatura
	 * @param String Período (Day, Week, SemiMonth, Month e Year)
	 * @param int Frequencia de acordo com o Periodo (Ex.: A cada duas semanas, utiliza Período 'Week' e Frequencia '2')
	 */
	function signature($amt, $cycle, $desc = 'Assinatura', $period = 'Month', $frequency = 1){
		$data = array(
					'L_BILLINGTYPE0' => 'RecurringPayments',
					'L_BILLINGAGREEMENTDESCRIPTION0' => $desc,			

					'DESC' => $desc,
					'BILLINGPERIOD' => $period,
					'BILLINGFREQUENCY' => $frequency,
					'AMT' => $amt,
					'TOTALBILLINGCYCLES' => $cycle
					);
		$this->addData($data);
	}
	
	/**
	 * Envia requisicoes para o PayPal
	 * 
	 * @param array Dados para ser enviado
	 * @return string Retorno em formato de Query String
	 */
	function request($data){
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_URL, 'https://api-3t.'.($this->sandbox? 'sandbox.' : '').'paypal.com/nvp');
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
 
		$r = curl_exec($ch);
 
		curl_close($ch);
		
		$this->resetProcessData();
		
		return $r;
	}
	
	/**
	 * Decodifica um retorno Query String para Array
	 * 
	 * @param string Dados codificados
	 * @return array Dados decodificados
	 */
	function decodeData($data){
		$nvp = array();
		
		if (preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $data, $matches)) {
			foreach ($matches['name'] as $offset => $name) {
				$nvp[$name] = urldecode($matches['value'][$offset]);
			}
		}
		return $nvp;
	}
	
	/**
	 * Inicia uma transacao de pagamento no PayPal
	 * 
	 * @return boolean
	 */
	function setExpressCheckout(){
		$this->setData('METHOD', 'SetExpressCheckout');
		
		$this->addProcessData($this->getConfig('auth'));		
		$this->addProcessData($this->getData());
		$this->setProcessData('CANCELURL', $this->getConfig('geral', 'CANCELURL'));
		$this->setProcessData('RETURNURL', $this->getConfig('geral', 'RETURNURL'));
		
		$data = $this->getProcessData();
		
		$response = $this->decodeData($this->request($data));		
		
		if (isset($response['ACK']) && $response['ACK'] == 'Success') {
			header('Location: https://www.'.($this->sandbox? 'sandbox.' : '').'paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$response['TOKEN']);
		}
		$this->setRequestData('SetExpressCheckout', $response);
		return $response;
	}
	
	/**
	 * Criando um ID de ftauramento
	 * 
	 * @param string Token
	 */
	function createBillingAgreement(){
		$details = $this->getRequestData('GetExpressCheckoutDetails');
		
		$this->addProcessData($this->getConfig('auth'));
		$this->setData('METHOD', 'CreateBillingAgreement');
		$this->setProcessData('TOKEN', $details["TOKEN"]);
		
		$response = $this->decodeData($this->request($data));
		
		$this->setRequestData('CreateBillingAgreement', $response);
		return $response;
	}
	
	/**
	 * Retorna o detalhes de uma transacao
	 * 
	 * @return boolean
	 */
	function getExpressCheckoutDetails(){
		
		$this->addProcessData($this->getConfig('auth'));		
		$this->setProcessData('TOKEN', $_GET['token']);
		$this->setProcessData('METHOD', 'GetExpressCheckoutDetails');
		$data = $this->getProcessData();
		
		$response = $this->decodeData($this->request($data));
		
		$this->setRequestData('GetExpressCheckoutDetails', $response);
		
		return (isset($response['ACK']) && $response['ACK'] == 'Success')? true : false;
	}	
	
	/**
	 * Cria um perfil recorrente no Paypal
	 * 
	 * @return boolean
	 */
	function createRecurringPaymentsProfile(){
		
		$details = $this->getRequestData('GetExpressCheckoutDetails');
		
		$this->addProcessData($this->getConfig('auth'));
		$this->addProcessData($this->getConfig('region'));
		
		$this->setProcessData('TOKEN', $details["TOKEN"]);
		$this->setProcessData('PayerID', $details["PAYERID"]);
		$this->setProcessData('PROFILESTARTDATE', $details["TIMESTAMP"]);
		$this->setProcessData('METHOD', 'CreateRecurringPaymentsProfile');
		$this->copyDataToProcessData('DESC','BILLINGPERIOD','BILLINGFREQUENCY','AMT');
		$data = $this->getProcessData();
				
		$response = $this->decodeData($this->request($data));
		
		$this->setRequestData('CreateRecurringPaymentsProfile', $response);
		
		return (isset($response['ACK']) && $response['ACK'] == 'Success')? true : false;
	}
	
	/**
	 * Informacoes de um determinado perfil
	 * 
	 * @param string Id do perfil
	 * @return array Dados do perfil
	 */
	function getRecurringPaymentsProfileDetails($profile_id){
		$this->addProcessData($this->getConfig('auth'));
		$this->setProcessData('METHOD', 'GetRecurringPaymentsProfileDetails');
		$this->setProcessData('PROFILEID', $profile_id);
		$data = $this->getProcessData();
				
		$response = $this->decodeData($this->request($data));
		
		$this->setRequestData('GetRecurringPaymentsProfileDetails', $response);		
		
		return $response;
	}
	
	/**
	 * Metodo para gerenciar situacao da assinatura
	 * 
	 * @param string $profile_id
	 * @param string $act Cancel, Suspend e Reactivate
	 * @param string $note (Opcional) Um motivo para esta executando tal acao
	 * @return boolean
	 */
	function manageProfileStatus($profile_id, $act, $note = ''){
		
		if(empty($profile_id) && empty($act)) return false;
		
		$this->addProcessData($this->getConfig('auth'));		
		$this->setProcessData('METHOD', 'ManageRecurringPaymentsProfileStatus');
		$this->setProcessData('PROFILEID', $profile_id);
		$this->setProcessData('ACTION', $act);
		$this->setProcessData('NOTE', $note);
		$data = $this->getProcessData();
				
		$response = $this->decodeData($this->request($data));
		
		$this->setRequestData('ManageRecurringPaymentsProfileStatus', $response);		
		
		return (isset($response['ACK']) && $response['ACK'] == 'Success')? true : false;
		
	}
	
	/**
	 * Pesquisar por transacoes
	 * 
	 * @param string TRANSACTIONID
	 * @return array Dados do retorno
	 */
	function transactionSearch($transaction_id = ''){
		$this->addProcessData($this->getConfig('auth'));
		$this->setProcessData('METHOD', 'TransactionSearch');
		$this->copyDataToProcessData('STARTDATE','ENDDATE','EMAIL','RECEIVER','RECEIPTID','TRANSACTIONID','INVNUM','PROFILEID');
		
		if($transaction_id)
			$this->setProcessData('TRANSACTIONID', $transaction_id);
		
		$data = $this->getProcessData();
				
		$response = $this->decodeData($this->request($data));
		
		$this->setRequestData('TransactionSearch', $response);		
		
		return $response;
	}
	
	
	/**
	 * Detalhes de uma transacao
	 * 
	 * @param string TRANSACTIONID
	 * @return array Dados do retorno
	 */
	function getTransactionDetails($transaction_id = ''){
		$this->addProcessData($this->getConfig('auth'));
		$this->setProcessData('METHOD', 'GetTransactionDetails');
		
		if($transaction_id)
			$this->setProcessData('TRANSACTIONID', $transaction_id);
		
		$data = $this->getProcessData();
				
		$response = $this->decodeData($this->request($data));
		
		$this->setRequestData('GetTransactionDetails', $response);		
		
		return $response;
	}
	
	/**
	 * Detalhes de uma pagamento
	 *
	 * @return array Dados do retorno
	 */
	function paymentDetails(){
		
	}
	
	/**
	 * Recebe um retorno IPN do PayPal
	 * 
	 * @return array Dados recebidos via POST
	 */
	function getIPN(){
		if(!empty($_POST)){
			$this->setRequestData('GetIPN', $_POST);
			return $_POST;
		}
		return false;
	}
		
	/**
	 * Processa pagamento e cria conta no Paypal
	 * 
	 * @return boolean
	 */
	function process(){	
		if(!empty($_GET['ExpressCheckoutCancel'])) return false;
			
		$this->resetRequestData();
		
		$r = false;

		if(empty($_GET['ExpressCheckoutReturn'])){
			$this->setExpressCheckout();
		}else{

			$r = $this->getExpressCheckoutDetails();
			
			if($r)
				$r = $this->createRecurringPaymentsProfile();
			
			if($r){
				$profile = $this->getRequestData('CreateRecurringPaymentsProfile');
				$this->getRecurringPaymentsProfileDetails($profile['PROFILEID']);
			}			
		}
		
		return $r;
	}	
	
	
}