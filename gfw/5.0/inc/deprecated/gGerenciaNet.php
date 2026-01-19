<?php
$gPathLib = "/var/www/html/gfw/4.0/inc/lib/";
require $gPathLib.'gerencianet/vendor/autoload.php';

use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

class gGerenciaNet extends Gerencianet{

	private array $items 				= [];
	private array $customer 			= [];
	private array $discount 			= [];
	private array $configurations 		= [];
	private array $conditional_discount = [];
	private array $bankingBillet 		= [];
	private $metadata				= "";

	public $request					= "";
	public $response				= "";

	public $msgErro					= [];

	public $charge_id				= 0;

	public function __construct($clientId,$clientSecret,$sandbox=true){
		parent::__construct(['client_id' => $clientId, 'client_secret' => $clientSecret, 'sandbox' =>$sandbox]);
	}

	public function dadosBoleto($cliente,$servico,$desconto,$metadata=""): void{

		//Dados do cliente
		if (isset($cliente['cpf'])) {
			$this->customer['name']=$this->limpaString($cliente['nome']);
			$this->customer['cpf']=$this->soNumeros($cliente['cpf']);
			$this->customer['phone_number']=$this->soNumeros($cliente['telefone']);
			$this->bankingBillet['customer'] = $this->customer;
		} else {
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

		if (isset($servico['repasses'])) {
			$this->items['marketplace'] = ['repasses'=>$servico['repasses']];
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
		if ($this->discount['value'] > 0) {
			$this->bankingBillet['discount'] = $this->discount;
		}

		//$this->bankingBillet['conditional_discount'] = $this->conditional_discount;

		//URL de notificação
		if ($metadata) {
			$this->metadata['notification_url'] = $metadata['notification_url'];
			$this->metadata['custom_id'] = $metadata['custom_id'];
		}

	}

	public function enviaBoletoOneStep()
	{
		$body = [
			'items' => [$this->items],
			'metadata' => $this->metadata,
			'payment' => ['banking_billet' => $this->bankingBillet]
		];

		$this->request=json_encode($body);
		try {
			$pay_charge = $this->oneStep([],$body);
			$this->response=json_encode($pay_charge);
			if ($pay_charge['code'] == 200) {
				$this->charge_id =(int) $pay_charge['data']['charge_id'];
				return true;
			}
			if (is_array($pay_charge['error_description'])) {
				$erroDesc = implode(" / ",$pay_charge['error_description']);
			} else {
				$erroDesc = $pay_charge['error_description'];
			}
			$this->msgErro[] = $pay_charge['code']." : ".$erroDesc;
		}
		catch (GerencianetException $e){
			$erroDesc = is_array($e->errorDescription) ? implode(" / ",$e->errorDescription) : $e->errorDescription;

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
			return end($chargeNotification['data']);
		}
		catch (GerencianetException $e) {}
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
			return $this->detailCharge($params, []);
		} catch (GerencianetException $e) {
			print_r($e->code);
			print_r($e->error);
			print_r($e->errorDescription);
		} catch (Exception $e) {
			print_r($e->getMessage());
		}

		return false;
	}

	public function createPaymentLink($itens,$metadata="",$vencimento="",$mensagem="",$descontoBoleto=0,$descontoCartao=0)
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
					//'billet_discount' => 0, // desconto, em reais, caso o pagador escolha boleto (5000 equivale a R$ 50,00)
					//'card_discount' => 0, // desconto, em reais, caso o pagador escolha cartão (3000 equivale a R$ 30,00)
					'message' => $mensagem, // mensagem para o pagador com até 80 caracteres
					'expire_at' => $vencimento, // data de vencimento da tela de pagamento e do próprio boleto
					'request_delivery_address' => false, // solicitar endereço de entrega do comprador?
					'payment_method' => 'all' // formas de pagamento disponíveis: all, banking_billet (boleto), credit_card (cartão)
				];

				if ((int) $descontoBoleto > 0) {
					$body['billet_discount'] = (int) $descontoBoleto ;
				}

				if ((int) $descontoCartao > 0) {
					$body['card_discount'] = (int) $descontoCartao ;
				}

				$response = $this->linkCharge(['id' => $charge['data']['charge_id']],$body);
				$this->response = json_encode($response);

				if ($response['code'] == 200) {
					return $response['data'];
				}

				return false;
			}
			return false;
		} catch (GerencianetException $e) {
			print_r($e->code);
			print_r($e->error);
			print_r($e->errorDescription);
		} catch (Exception $e) {
			print_r($e->getMessage());
		}

  		return null;
	}
	/**
	 * Remove acentos de uma string
	 * @param  string $string
	 * @return string
	 */
	public function limpaString($string)
	{
		$t_st = str_replace(["/", "-", ".", "º", ",", "ª", "´", "'", "´"], "", $string);
        $string = $t_st;
        //$string = str_replace(array("Ã§"),"c",$string);
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