<?php
class Asaas{

    private $url = [
        'homologacao'   => 'https://sandbox.asaas.com/api/v3/',
        'producao'      => 'https://www.asaas.com/api/v3/'
    ];

    private $access_token = '';
    private $modo = "";

    public function __construct($access_token,$modo='producao')
    {
        /** Podemos colocar uma validação do toke, contudo é mais uma requisição a ser feita. ANALISAR */
        $this->access_token = $access_token;
        $this->modo = $modo;
    }

    public function envia($dados,$url,$metodo = 'POST'){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        
        if($metodo=='POST'){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$dados);
        }
        else
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "access_token: ".$this->access_token
        ));
        
        $response = curl_exec($ch);
        return $response;
    }

    public function clientes($nome,$cpfCnpj,$params=array()){
        $url = $this->url[$this->modo].'customers';

        $response = $this->envia('',$url.'?cpfCnpj='.$this->soNumeros($cpfCnpj),'GET');
        $response = json_decode($response,true);
        
        if($response['totalCount'] == 0){
            $dados = [
                'name'      => $this->limpaString($nome),
                'cpfCnpj'   => $this->soNumeros($cpfCnpj)
            ];
    
            if(preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i",$params['email'])){
                $dados['email']= $this->limpaString($params);
            }
            if($params['phone'] <> "")
                $dados['phone'] = $params['phone'];
            
            if($params['mobilePhone'] <> "")
                $dados['mobilePhone'] = $params['mobilePhone'];
    
            
            $response = $this->envia(json_encode($dados),$url);
    
            $response = json_decode($response,true);
    
            $clienteId =  $response['id'];
        }
        else{
            $clienteId = $response['data'][0]['id'];
        }
        return $clienteId;
    }

    public function boletos($params){
        $url = $this->url[$this->modo].'payments';
        // echo $url;exit;
        $dados = [];

        $clienteId = $this->clientes($params['nome'],$params['cpfCnpj']);

        $dados['customer']              = $clienteId;
        $dados['billingType']           = 'BOLETO';
        $dados['value']                 = $params['valor'];
        $dados['dueDate']               = $params['dataVencimento'];
        $dados['description']           = $this->limpaString($params['descricao']);
        $dados['externalReference']     = $params['id'];
        $dados['installmentCount']      = (int) $params['numeroParcelas'] > 0 ?:1 ;
        $dados['installmentValue']      = $params['valorParcelas'];

        if( (int) $params['desconto']['valor'] > 0){
            $desconto=[
                'value'                     => $params['desconto']['valor'],
                'dueDateLimitDays'          => $params['desconto']['dataLimite'],
                'type'                      => 'FIXED'
            ];
            $dados['discount']              = $desconto;
            $dados['interest']              = $params['valorJuroMes'];
            $dados['fine']                  = $params['multaAposVencimento'];
        }
        
        // if($params['split']){
        //     $split = [
        //         'walletId'                  => $params['split']['walletId'],
        //         $params['split']['tipo']    =>  $params['split']['valor']
        //     ];
        //     $dados['split']                 = $split;
        // }
        
        
        $response = $this->envia(json_encode($dados),$url,'POST');
        echo "<pre>";
        var_dump($response);
        exit;

    }

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
    public function soNumeros($str) {
    	return preg_replace("/[^0-9]/", "", $str);
	}
}


$token = '$aact_YTU5YTE0M2M2N2I4MTliNzk0YTI5N2U5MzdjNWZmNDQ6OjAwMDAwMDAwMDAwMDAzMTIxNjA6OiRhYWNoX2RkYTFjMDUzLTM2ODAtNDgyNC04M2JlLTljYjQ3NGUwNDcwYw==';
$modo = 'producao';
$a = new Asaas($token,$modo);
//$a->clientes("Bruno Coelho Ferreira",'02799023525');

$dados=[];
$dados['cpfCnpj']           ='02799023525';
$dados['valor']             = '10223';
$dados['dataVencimento']    = '2023-07-01';
$dados['descricao']         = '45 aulas teóricas + 200 aula práticas + Todo o resto';
$dados['id']                = 1234;
$dados['numeroParcelas']    = 1;
$dados['valorParcelas']     = 0;

$dados['desconto']['valor'] = 0;
$dados['desconto']['dataLimite'] = '2023-06-27';

$dados['valorJuroMes'] = 0;
$dados['multaAposVencimento']=0;

$dados['split'][1]['walletId'] = 'asdasdasd';
$dados['split'][1]['valor'] = 2000;
$a->boletos($dados);
?>