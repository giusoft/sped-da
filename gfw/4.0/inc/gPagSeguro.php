<?php

//error_reporting(E_ALL);

/* 
 * Classe para integração com pagseguro Assinatura..
 */
class gPagSeguro{
    
    /**
     * Moeda utilizada
     * 
     * @var string
     */
    private $currency = '';
    
    /**
     * E-mail conta pg
     * 
     * @var string
     */
    private $account_mail = '';
    
    /**
     * Token conta pg
     * 
     * @var string
     */
    private $account_token = '';
    
     /**
     * Tipo de assinatura (auto / manual)
     * 
     * @var string
     */
    private $request_charge = '';
    
    /**
     * Formato de requisi��o (HTTP / XML)
     * 
     * @var string
     */
    private $request_format = '';
    
    /**
     * Retorno das requisicoes
     * 
     * @var array
     */
    private $request_data = array();
    
    /**
     * Charset (ISO-8859-1 / UTF-8)
     * 
     * @var string
     */
    private $charset = '';
    
    
    /**
     * URL requisicao HTTP
     * 
     * @var string
     */
    private $url_request_http = '';
    
    /**
     * URL direciona comprador para fluxo de pagamento
     * 
     * @var string
     */
    private $url_payment_http = '';
    
    /**
     * URL notification HTTP
     * 
     * @var string
     */
    private $url_notification_http = '';    
    
    
    /**
     * URL cancel HTTP
     * 
     * @var string
     */
    private $url_cancel_http = '';    
    
    /**
     * Dados para consultar/cancelar/solicitar uma assinatura
     * 
     * @var array
     */
    private $data = array(); 
    
     /**
     * Ativa para exibir depuracao
     * 
     * @var array
     */
    private $mode_debug = false;
        
    /**
     * Variavel de depuração
     * 
     * @var array
     */
    private $debug = array();
    
    /**
     * Construtor
     */
    function __construct(){
        $this->currency = 'BRL';
        $this->account_mail = function_exists("gVar") ? gVar("pagseguro.email") : "";
        $this->account_token = function_exists("gVar") ? gVar("pagseguro.token"): "";
        $this->request_charge = 'auto';
        $this->request_format = 'http';
        $this->charset = 'UTF-8';
        $this->url_request_http = 'https://ws.pagseguro.uol.com.br/v2/pre-approvals/request';
        $this->url_payment_http = 'https://pagseguro.uol.com.br/v2/pre-approvals/request.html?code={preApprovalResquestCode} 
    ';
        $this->url_notification_http = 'https://ws.pagseguro.uol.com.br/v2/pre-approvals/notifications/{notification}?email={email}&token={token}';
        $this->url_cancel_http = 'https://ws.pagseguro.uol.com.br/v2/pre-approvals/cancel/{cancel}?email={email}&token={token}';
        //$this->url_notification_http = 'https://ws.pagseguro.uol.com.br/v2/pre-approvals/notifications/{notification}';
    }
    /*
     * Configura��o da classe
     * 
     * @var array parametros de configura��o
     * @return boolean true ou false
     */
    function config($data = array()){        
        if(is_array($data) && count($data)){            
            foreach($data as $k => $v){                
                if(property_exists($this,$k)){
                    // $this->getProperty($k)->setValue($v);                    
                    eval('$this->'.$k.' = "'.$v.'";');
                }
            }
        }
    }    
    
    function setData($key, $value){
        $this->data[$key]=$value;
    }
    
    function resetData(){
        $this->data = array();
    }
    
    function listData(){
        return $this->data;
    }
    
    function setRequestData($key = '', $value = ''){
        $this->request_data[$key] = $value;
    }
    function getRequestData($key = ''){
        return (!empty($key) && !empty($this->request_data[$key]))? $this->request_data[$key] : false;
    }
    function resetRequestData(){
        $this->request_data = array();
    }
    
    function request($url){        
        
        $data = $this->data;
        $data['email'] = $this->account_mail;
        $data['token'] = $this->account_token;
        $data = http_build_query($data);
        
        $this->resetData();
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, 'Content-Type: application/x-www-form-urlencoded; charset='.$this->charset);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $r = curl_exec($ch);
		
		$this->addDebug('request',$r);
		
        curl_close($ch);
        
        return $r;                    
    }
    
    
    function requestSender(){
        $r = $this->request($this->url_request_http);
        $this->processXml($r,'sender');        
        return $this->getRequestData('sender');
    }
    
    function requestApproval($code = ''){
        if(empty($code)){
            $rs = $this->requestSender();
            $code = $rs->code;
        }
        
        if($code){
            if($this->request_charge == 'auto'){
                header('Location: '.str_replace('{preApprovalResquestCode}',$code,$this->url_payment_http));
            }
        }
        return false;
    }
    
    function requestNotification($code = '', $notification_code=''){
        
        $a = array('{notification}','{email}','{token}');
        $b = array($code,$this->account_mail,$this->account_token);
        $url = str_replace($a,$b,$this->url_notification_http);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $r= curl_exec($ch);
        curl_close($ch);        
        
        $this->processXml($r,'notification');        
        return $this->getRequestData('notification');
    }
    
    function requestCancel($code = ''){
        
        $a = array('{cancel}','{email}','{token}');
        $b = array($code,$this->account_mail,$this->account_token);
        $url = str_replace($a,$b,$this->url_cancel_http);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $r= curl_exec($ch);
        curl_close($ch);        
        
        $this->processXml($r,'cancel');        
        return $this->getRequestData('cancel');
    }
    
    function processXml($xml = '', $identify = ''){        
        
        if($xml == 'Unauthorized'){
            $this->addDebug('ERROR', 'Requisição não autorizado.');
            return false;
        }
        
        if($xml == 'Not Found'){
            $this->addDebug('ERROR', 'Nenhuma informação disponível.');
            return false;
        }
        
        $xml = simplexml_load_string($xml);
        
        if(count($xml->error) > 0){           
            $this->addDebug('ERROR', print_r($xml,true));
            $this->setRequestData('error', $xml);
            return false;
        }else{           
            $this->setRequestData($identify, $xml);
            return true;
        }
        
    }
	
	function addDebug($tipo = '', $message = ''){
        $this->debug[$tipo] = $message;
    }
    
    function modeDebug($r = true){
        $this->mode_debug = $r;
        return $this->mode_debug;
    }
    
    function activedDebug(){
        return $this->mode_debug;
    }
    
    function showDebug(){
        if($this->debug)
            print_r($this->debug);
    }
    
    function __destruct() {
        if($this->activedDebug())
            $this->showDebug();        
    }
}
?>