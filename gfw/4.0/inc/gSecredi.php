<?php

/**
 * Classe de integração com Secredi
 */

class gSecredi {

    public $data = array();
    public $request_token = '';
    public $access_token = '';
    public $refresh_token = '';
    public $username = '';
    public $password = '';
    public $expires_in = '';
    public $refresh_expires_in = '';
    public $scope  = '';
    public $url_authentication_homologation= '';
    public $url_authentication_production= '';
    public $url_billetRegistration_homologation ='';
    public $url_billetRegistration_production ='';
    public $url_ticketPrinting_homologation ='';
    public $url_ticketPrinting_production ='';
    public $url_consultPrinting_homologation ='';
    public $url_consultPrinting_production ='';
    public $url_lowTicket_homologation ='';
    public $url_lowTicket_production ='';
    public $url_receipt_data_web ='';
    public $url_receipt_data_production ='';
    public $url_receipt_day_homologation = '';
    public $url_receipt_day_production = '';
    public $context = '';
    public $cooperativa = '';
    public $posto = '';
    public $codigoBeneficiario = '';
    public $nome='';
    public $email ='';
    public $cpfCnpjBeneficiarioFinal ='';
    

    
    function __construct($parameter) {
        $this->cpfCnpjBeneficiarioFinal=$parameter['cnpj'];//10659816000185 - 12345678912 $parameter['cnpj'];
        $this->cooperativa = '0911'; //para teste $parameter['cooperativa']
        $this->posto = '21'; //para teste $parameter['posto']
        $this->access_token = ''; //para teste
        $this->codigoBeneficiario = $parameter['codigoBeneficiario']; //para teste 12345
        $this->username = $this->codigoBeneficiario.$this->cooperativa; // para teste $this->codigoBeneficiario . $this->cooperativa
        $this->password = '399640C8ED79BC37497608A3B19CCF521D33D6D8ED0C2B823F5D07B2D98BC046';  // $parameter['senha']; teste123 para teste
        $this->email =$parameter['email'];
        $this->nome = $parameter['nome'];
        $this->url_authentication_homologation= 'https://api-parceiro.sicredi.com.br/sb/auth/openapi/token';
        $this->url_authentication_production= 'https://api-parceiro.sicredi.com.br/auth/openapi/token';
        $this->url_billetRegistration_homologation ='https://api-parceiro.sicredi.com.br/sb/cobranca/boleto/v1/boletos';
        $this->url_billetRegistration_production ='https://api-parceiro.sicredi.com.br/cobranca/boleto/v1/boletos';
        $this->url_ticketPrinting_homologation ='https://api-parceiro.sicredi.com.br/sb/cobranca/boleto/v1/boletos/pdf';
        $this->url_ticketPrinting_production ='https://api-parceiro.sicredi.com.br/cobranca/boleto/v1/boletos/pdf';
        $this->url_consultPrinting_homologation ='https://api-parceiro.sicredi.com.br/sb/cobranca/boleto/v1/boletos';
        $this->url_consultPrinting_production ='https://api-parceiro.sicredi.com.br/cobranca/boleto/v1/boletos';
        $this->url_lowTicket_homologation ='https://api-parceiro.sicredi.com.br/sb/cobranca/boleto/v1/boletos/';
        $this->url_lowTicket_production ='https://api-parceiro.sicredi.com.br/cobranca/boleto/v1/boletos/';
        $this->url_receipt_data_production = 'https://api-parceiro.sicredi.com.br/cobranca/boleto/v1/webhook/contrato/';
        $this->url_receipt_data_web = 'https://webcfc.com.br/secredi.php'; // Inserir caminho da rotina para testes
        $this->url_receipt_day_homologation = 'https://api-parceiro.sicredi.com.br/sb/cobranca/boleto/v1/boletos/liquidados/dia';
        $this->url_receipt_day_production = 'https://api-parceiro.sicredi.com.br/cobranca/boleto/v1/boletos/liquidados/dia';
        $this->refresh_token = '';
        $this->request_token ='5fdacf39-92f6-489d-8ec2-3575e071d8ce';
        // $this->request_token ='399640C8ED79BC37497608A3B19CCD5521D33D6D8ED0C2B823F5D07B2D98BC046';
        $this->url_receipt_data_homologationWeb ='';
        $this->autenticacao();

    }
    
  
    function autenticacao($refresh_token='')
    {
      
        $context = 'COBRANCA';
        $scope = 'cobranca';
        $header = array (
			"Content-type: application/x-www-form-urlencoded",
			"x-api-key:" .$this->request_token ,
			"context: ".$context
        );

        if (!$refresh_token) {
            $json  = http_build_query(['username' => $this->username, 'password' => $this->password,'scope'=> $scope, 'grant_type' => 'password' ]);

        } else {
            $json  = http_build_query(['username' => $this->username, 'password' => $this->password,'scope'=> $scope, 'grant_type' => 'refresh_token', 'refresh_token' => $this->request_token ]);

        }
        $result = $this->enviarRequisicao($header,$this->url_authentication_production,$json,'POST');
        $result = json_decode($result,true);
        $this->access_token = 'bearer ' .$result['access_token'];
        // echo $this->access_token ;exit;
        $this->refresh_token = $result['refresh_token'];
        $this->refresh_expires_in = $result['refresh_expires_in'];
        return $result;
    }

    public function cadastrarBoleto($dadosBoleto)
    {
        $header = array (
            "cooperativa:" .$this->cooperativa,
            "posto:" .$this->posto,
            "Authorization:". $this->access_token ,
			"x-api-key:" .$this->request_token ,
			"Content-type: application/json",
        );

        $info = array(
            "Não receber após o vencimento",
        );

        $mensagens = array(
            "Não receber após o vencimento",
        );

        $beneficiarioFinal = array("beneficiarioFinal" =>array(
            "cep" => $dadosBoleto['cepBeneficiario'],
            "cidade" => $dadosBoleto['cidadeBeneficiario'],
            "documento" => $dadosBoleto['documentoBeneficiario'],
            "logradouro" => $dadosBoleto['logradouroBeneficiario'],
            "nome" => $dadosBoleto['nomeBeneficiario'],
            "numeroEndereco" => $dadosBoleto['numeroEnderecoBeneficiario'],
            "tipoPessoa" => $dadosBoleto['tipoPessoaBeneficiario'],
            "uf" => $dadosBoleto['ufBeneficiario'])
        );
       
        $data = array(
           "codigoBeneficiario" => $dadosBoleto['codigoBeneficiario'],
           "dataVencimento" => $dadosBoleto['dataVencimento'],
           "especieDocumento" => $dadosBoleto['especieDocumento'],
           "pagador" =>array(
                "cep" => $dadosBoleto['cepPagador'],
                "cidade" => $dadosBoleto['cidadePagador'],
                "documento" => $dadosBoleto['documentoPagador'],
                "nome" => $dadosBoleto['nomePagador'],
                "tipoPessoa" => $dadosBoleto['tipoPessoaPagador'],
                "endereco" => $dadosBoleto['enderecoPagador'],
                "uf" => $dadosBoleto['ufPagador'],
            ),
            "tipoCobranca"=> $dadosBoleto['tipoCobranca'],
            "seuNumero"=> $dadosBoleto['seuNumero'],
            "valor"=> $dadosBoleto['valor'],
            "informativo"=> $info,
            "mensagens"=> $mensagens
        );
        $json  = json_encode($data);   
        $result = $this->enviarRequisicao($header,$this->url_billetRegistration_production,$json,'POST');
        return $result;
    }

    public function impressaoBoleto($linhaDigitavel,$nome)
    {
        $parameter = '?linhaDigitavel='.$linhaDigitavel;
       
        $header = array (
            "Authorization:". $this->access_token ,
			"Content-type: application/json",
			"x-api-key:" .$this->request_token ,
        );
        $result = $this->enviarRequisicao($header,$this->url_ticketPrinting_production.$parameter,'',"GET");
        $json = json_decode($response,true);
        if ($json['error']) {
            return $json;
        }
        return download('boleto_aluno_'. $nome.'.pdf',$result, 'pdf');
    }

    public function consultarBoleto($nossoNumero)
    {
        $parameter = '?codigoBeneficiario='.$this->codigoBeneficiario.'&nossoNumero='.$nossoNumero;
        $header = array (
            "Authorization:". $this->access_token ,
			"Content-type: application/json",
            "x-api-key:" .$this->request_token ,
            "cooperativa:" .$this->cooperativa,
            "posto:" .$this->posto,
            
        );
        $result = $this->enviarRequisicao($header,$this->url_consultPrinting_production.$parameter,'','GET');
        return $result;
    }

    public function baixaBoleto($nossoNumero)
    {
        $parameter = $nossoNumero.'/baixa';
       
        $dados = '{

        }';
        
        $header = array (
            "Authorization:". $this->access_token ,
			"Content-type: application/json",
            "cooperativa:" .$this->cooperativa,
            "posto:" .$this->posto,
            "x-api-key:" .$this->request_token ,
            "codigoBeneficiario:".$this->codigoBeneficiario
        );
       
        $result = $this->enviarRequisicao($header,$this->url_lowTicket_production.$parameter, $dados,'PATCH');
        return $result;
    }

    public function receberDadosBaixa()
    {
   
        $header = array (
            "Authorization:". $this->access_token ,
			"Content-type: application/json",
            "x-api-key:" .$this->request_token ,
        );

        $data = array(
            "cooperativa:". $this->username,
            "posto:". $this->password,
            "codBeneficiario:".  $this->codigoBeneficiario,
            "eventos:"=>array(
                "LIQUIDACAO_PIX",
                "LIQUIDACAO_REDE",
                "LIQUIDACAO_COMPE_H5",
            ),
            "url: ".$this->url_receipt_data_web, //alterarURL
            "urlStatus: ATIVO",
            "contratoStatus: ATIVO",
            "nomeResponsavel:" .$this->nome,
            "email:".$this->email,
            "telefone :51 999999999"
        );
        $json = json_encode($data);
        $result = $this->enviarRequisicao($header,$this->url_receipt_data_production,$json,'');
        echo $result;exit;
    }

    public function baixaDia()
    {
        $data = date('d/m/Y');
        $header = array (
            "Authorization:". $this->access_token ,
			"Content-type:application/x-www-form-urlencoded",
            "x-api-key:" .$this->request_token ,
            "cooperativa:".  $this->cooperativa,
            "posto:" .$this->posto,
            "codigoBeneficiario:".$this->codigoBeneficiario,
        );
        $parameter = '?codigoBeneficiario='.$this->codigoBeneficiario.'&dia='.$data.'&cpfCnpjBeneficiarioFinal='.$this->cpfCnpjBeneficiarioFinal;
        $result = $this->enviarRequisicao($header,$this->url_receipt_day_production.$parameter,'','GET');
        return $result;
    }

   
    public function enviarRequisicao($header,$url, $content,$type='') 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, 443);
        if ($type == 'POST' ) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        } elseif($type == 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true );
        }else{
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      
        $result = curl_exec($ch);
        $err = curl_error($ch);
        return $result;
    }
}

// $dados = array(
//     "cepBeneficiario"=>"91250000",
//     "cidadeBeneficiario" =>"PORTO ALEGRE",
//     "documentoBeneficiario" =>"25140124069",
//     "logradouroBeneficiario" =>"RUA DOUTOR VARGAS NETO 980",
//     "nomeBeneficiario" =>"FELIPE OLIVEIRA",
//     "numeroEnderecoBeneficiario" =>'119',
//     "tipoPessoaBeneficiario" =>"PESSOA_FISICA",
//     "ufBeneficiario" =>"RS",
//     "codigoBeneficiario" =>"12345",
//     "dataVencimento" =>"2022-07-30",
//     "especieDocumento" =>"DUPLICATA_MERCANTIL_INDICACAO",
//     "cepPagador" =>"91250000",
//     "cidadePagador" =>"PORTO ALEGRE",
//     "documentoPagador" =>"02738306006",
//     "nomePagador" =>"RODRIGO OLIVEIRA",
//     "tipoPessoaPagador" =>"PESSOA_FISICA",
//     "enderecoPagador" =>"RUA DOUTOR VARGAS NETO 150",
//     "ufPagador" =>"RS",
//     "tipoCobranca" =>"HIBRIDO",
//     "nossoNumero" => '600046210',
//     "seuNumero" =>"TESTE",
//     "valor" =>'50.00',
//     "tipoDesconto" => "VALOR",
//     "valorDesconto1" =>'10.00',
//     "dataDesconto1" =>"2022-07-15",
//     "valorDesconto2" =>'7.00',
//     "dataDesconto2" =>"2022-07-20",
//     "valorDesconto3" =>'3.00',
//     "dataDesconto3" =>"2022-07-30",
//     "tipoJuros" => "VALOR",
//     "juros" => '5.00',
//     "multa" => '3.00'
// );
// $teste = new gSecredi();
// $teste->autenticacao();
// $teste->receberDadosBaixa();
// $teste->baixaDia();
// $teste->cadastrarBoleto($dados);
// $teste->baixaBoleto(211003973);
// $teste->impressaoBoleto('74891160090000020911421033051091493910000016667');
// $teste->consultarBoleto('600046210');

