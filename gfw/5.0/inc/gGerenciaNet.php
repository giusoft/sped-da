<?php
$gPathLib = __DIR__ . '/lib/';

if (file_exists($gPathLib . 'gerencianet/vendor/autoload.php')) {
    require_once $gPathLib . 'gerencianet/vendor/autoload.php';
} else {
    require_once '/var/www/html/5.0/inc/lib/gerencianet/vendor/autoload.php';
}

use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

class gGerenciaNet extends Gerencianet {

    public $options; 

	private array $items 				= [];
	private array $customer 			= [];
	private array $discount 			= [];
	private array $configurations 		= [];
	private array $conditional_discount = [];
	private array $bankingBillet 		= [];
	private $metadata					= []; 

	public $request					= "";
	public $response				= "";
	public $msgErro					= [];
	public $charge_id				= 0;

	public function __construct($clientId, $clientSecret, $sandbox = true){
        $this->options = [
            'client_id' => $clientId, 
            'client_secret' => $clientSecret, 
            'sandbox' => $sandbox
        ];
		parent::__construct($this->options);
	}

	public function dadosBoleto($cliente, $servico, $desconto, $metadata = ""): void {
		if (isset($cliente['cpf'])) {
			$this->customer['name'] = $this->limpaString($cliente['nome']);
			$this->customer['cpf'] = $this->soNumeros($cliente['cpf']);
			$this->customer['phone_number'] = $this->soNumeros($cliente['telefone']);
			$this->bankingBillet['customer'] = $this->customer;
		} else {
			$this->customer['corporate_name'] = $this->limpaString($cliente['razao_social']);
			$this->customer['cnpj'] = $this->soNumeros($cliente['cnpj']);
			$this->bankingBillet['customer']['phone_number'] = $this->soNumeros($cliente['telefone']);
			$this->bankingBillet['customer']['juridical_person'] = $this->customer;
		}

		$this->items['name'] = $this->limpaString($servico['nome']);
		$this->items['amount'] = (int) $servico['quantidade'] == 0 ? 1 : (int) $servico['quantidade'];
		$this->items['value'] = (int) number_format((float)$servico['valor'], 2, '', '');

		if (isset($servico['repasses'])) {
			$this->items['marketplace'] = ['repasses' => $servico['repasses']];
		}

		//Configuração de descontos
		$this->discount['type'] = 'percentage';
		$this->discount['value'] = (int) number_format((float)$desconto['desconto'], 2, '', '');

		//Juros e multa (Em %)
		$this->configurations['fine'] = (int) number_format((float)$servico['multa'], 2, '', '');
		$this->configurations['interest'] = (int) number_format((float)$servico['juros'], 2, '', '');

		// Desconto condicional
		$this->conditional_discount['type'] 		= 'percentage';
		$this->conditional_discount['value'] 		= (int) number_format((float)$desconto['desconto_condicional'], 2, '', '');
		$this->conditional_discount['until_date'] 	= $desconto['data_desconto_condicional'];
		
		//Dados gerais do boleto
		$this->bankingBillet['expire_at'] = $servico['data_vencimento'];
		$this->bankingBillet['message'] = $servico['messagem'];
        $this->bankingBillet['configurations'] = $this->configurations;

		if ($this->discount['value'] > 0) {
			$this->bankingBillet['discount'] = $this->discount;
		}

		if (!empty($metadata)) {
			$this->metadata['notification_url'] = $metadata['notification_url'];
			$this->metadata['custom_id'] = $metadata['custom_id'];
		}
	}

    /**
     * ATUALIZADO: Substitui oneStep por createCharge + payCharge
     */
	public function enviaBoletoOneStep()
	{
        // 1. Prepara o corpo da Transação (Item + Metadata apenas)
		$bodyCharge = [
			'items' => [$this->items],
			'metadata' => $this->metadata
		];

        // 2. Prepara o corpo do Pagamento (Boleto)
        $bodyPayment = [
			'payment' => ['banking_billet' => $this->bankingBillet]
        ];

		$this->request = json_encode(['charge' => $bodyCharge, 'payment' => $bodyPayment]);
        
		try {
            // PASSO 1: Criar a transação
			$charge = $this->createCharge([], $bodyCharge);
            
            if ($charge['code'] == 200) {
                $this->charge_id = (int) $charge['data']['charge_id'];

                // PASSO 2: Pagar a transação (Gerar Boleto)
                $params = ['id' => $this->charge_id];
                $pay_charge = $this->payCharge($params, $bodyPayment);
                
                $this->response = json_encode($pay_charge);

                if ($pay_charge['code'] == 200) {
                    return true;
                }

                $erroDesc = $pay_charge['error_description'] ?? 'Erro desconhecido no pagamento';
                if(is_array($erroDesc)) {
					$erroDesc = implode(" / ", $erroDesc);
				}
				$this->msgErro[] = $pay_charge['code']." : ".$erroDesc;
                return false;
            }

            $erroDesc = $charge['error_description'] ?? 'Erro desconhecido na criação';
            if(is_array($erroDesc)) $erroDesc = implode(" / ", $erroDesc);
			$this->msgErro[] = $charge['code']." : ".$erroDesc;

		} catch (GerencianetException $e) {
			$erroDesc = is_array($e->errorDescription) ? implode(" / ", $e->errorDescription) : $e->errorDescription;
			$this->msgErro[] = $e->code." - ".$e->error." : ".$erroDesc;
		} catch (Exception $e) {
			 $this->msgErro[] = $e->getMessage();
		}
		return false;
	}

	public function cancelaBoleto($idBoleto){
		try {
		    $charge = $this->cancelCharge(['id' => (int) $idBoleto], []);
		    if ($charge['code'] == 200) {
				return true;
			}
		} catch (GerencianetException $e) {
			$this->msgErro[] = $e->code." - ".$e->error." : ".$e->errorDescription;
		} catch (Exception $e) {
		    $this->msgErro[] = $e->getMessage();
		}
		return false;
	}

	public function verificaStatus($token){
		try {
			$chargeNotification = $this->getNotification(['token' => $token], []);
            if (empty($chargeNotification['data'])) {
				return false;
			}
			return end($chargeNotification['data']);
		}
		catch (GerencianetException $e) {}
		catch (Exception $e) {}
        return false;
	}

	public function alterarNotificacao($chargeId, $notificaionURL, $customId){
		$params = [
			'id' => $chargeId
		];
		$body = [
			'custom_id' => (string) $customId,
			'notification_url' => $notificaionURL
		];
		try {
			$charge = $this->updateChargeMetadata($params, $body);
			return true;
		} catch (GerencianetException $e) {
            $this->msgErro[] = $e->errorDescription;
		} catch (Exception $e) {
			$this->msgErro[] = $e->getMessage();
		}
		return false;
	}

	public function verificarDadosTransacao($chargeId){
		$params = [
			'id' => (string) $chargeId // $charge_id refere-se ao ID da transação ("charge_id")
		];

		try {
			return $this->detailCharge($params, []);
		} catch (GerencianetException $e) {
			$this->msgErro[] = $e->errorDescription;
		} catch (Exception $e) {
            $this->msgErro[] = $e->getMessage();
		}
		return false;
	}

	public function createPaymentLink($itens, $metadata="", $vencimento="", $mensagem="", $descontoBoleto=0, $descontoCartao=0)
	{
		$items = [$itens];
		$body = [
			'items' => $items
		];
		if ($metadata) {
			$body['metadata'] = $metadata;
		}
		try {
			$charge = $this->createCharge([], $body);
			if ($charge['code'] == 200) {
				$body = [
					'message' => $mensagem,
					'expire_at' => $vencimento,
					'request_delivery_address' => false,
					'payment_method' => 'all'
				];
				if ((int) $descontoBoleto > 0) {
					$body['billet_discount'] = (int) $descontoBoleto ;
				}
				if ((int) $descontoCartao > 0) {
					$body['card_discount'] = (int) $descontoCartao ;
				}
				$response = $this->linkCharge(['id' => $charge['data']['charge_id']], $body);
				$this->response = json_encode($response);
				if ($response['code'] == 200) {
					return $response['data'];
				}
				return false;
			}
			return false;
		} catch (GerencianetException $e) {
			$this->msgErro[] = $e->errorDescription;
		} catch (Exception $e) {
            $this->msgErro[] = $e->getMessage();
		}
  		return null;
	}

	public function limpaString($string)
	{
        $string = (string) $string;
		$t_st = str_replace(["/", "-", ".", "º", ",", "ª", "´", "'", "´"], "", $string);
        $string = $t_st;
	    $string = str_replace(['á', 'à', 'ã'],"a",$string);
	    $string = str_replace(["À", "Á", "Ã"],"A",$string);
	    $string = str_replace(["é", "è", "ê"],"e",$string);
	    $string = str_replace(["É", "È", "Ê"],"E",$string);
	    $string = str_replace(["í", "ì"],"i",$string);
	    $string = str_replace(["Í", "Ì"],"I",$string);
	    $string = str_replace(["ó", "ò", "õ", "ô"],"o",$string);
	    $string = str_replace(["Ò", "Ó", "Õ", "Ô"],"O",$string);
	    $string = str_replace(["ú", "ù"],"u",$string);
	    $string = str_replace(["Ú", "Ù"],"U",$string);
	    $string = str_replace(["Ç"],"C",$string);
	    return str_replace(["ç"],"c",$string);
	}

	/**
	 * Deixa apenas números da String
	 */
	public function soNumeros($str) {
    	return preg_replace("/[^0-9]/", "", (string) $str);
	}

}
?>