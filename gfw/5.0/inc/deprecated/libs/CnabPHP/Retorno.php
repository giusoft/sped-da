<?php
use Cnab\Remessa\Cnab400\Detalhe;
use Cnab;

include_once 'vendor/autoload.php';

/**
 * 
 * A finalidade dessa classe é fazer a interface entre a biblioteca CnabPHP e o gFW
 * permitindo que o controle de dependências e instanciação das classes da
 * biblioteca fiquem independetes do gFW.
 *
 * Essa classe processa um arquivo de retorno enviado pelo banco
 *
 * @author André Luiz
 * @version 1.0 09-01-2017 10:03
 *         
 */
class Retorno
{

	private $cnabFactory;

	private $arquivo;

	public function __construct()
	{
		$this->cnabFactory = new Cnab\Factory();
	}

	public function abreArquivo($caminho)
	{
		$this->arquivo = $this->cnabFactory->createRetorno($caminho);
	}
	
	/**
	 * Devolve um array com principais informações a cerca do retorno do banco para os daods enviados no arquivos de remessa.
	 * 
	 * @return $sai array
	 */
	public function listaDetalhes()
	{
		$sai = array();
		$codigo_banco=$this->arquivo->getCodigoBanco();
		$detalhes = $this->arquivo->listDetalhes();
		$rateios = $this->arquivo->rateios;
		foreach ($detalhes as $detalhe) {
			if ($detalhe->getValorTitulo() > 0) {
				
				$nndv = $detalhe->getNossoNumero();
				$d="";
				$d['codigo_banco'] = $codigo_banco;
				switch ($codigo_banco) {
					case '748': // Sicredi
						$d['seu_numero'] = $detalhe->seu_numero;
						if((int) substr($detalhe->getNossoNumero(), 2,1) > 1 ) // Significa que foi emitido pelo sistema
							$d['nosso_numero'] = substr( $detalhe->getNossoNumero(),3,5);
						else
							$d['nosso_numero'] = 0;
						if($detalhe->data_ocorrecia <> ""){
							$data = $detalhe->data_ocorrecia ? \DateTime::createFromFormat('dmy', sprintf('%06d', $detalhe->data_ocorrecia)) : false;
					        $d['data_ocorrencia'] = $data->format('Y-m-d');
						}
						$d['motivo_rejeicao'] = (string) $this->getMotivoDetalhe('748',substr($detalhe->motivo_ocorrencia,0,2));
						$d['valor_pago'] = ((float) $detalhe->valor_pago / 100);
					break;
					
					case '237': // Bradesco
						$dataCredito = $detalhe->data_credito ? \DateTime::createFromFormat('dmy', sprintf('%06d', $detalhe->data_credito)) : false;
						$d['data_ocorrencia'] = $dataCredito instanceof \DateTime ? $dataCredito->format('Y-m-d') : "";
						$d['nosso_numero_dv'] = substr($nndv, strlen($nndv) - 1, strlen($nndv));	
						$d['carteira'] = $detalhe->getCarteira();
						$d['motivo_rejeicao'] = $detalhe->motivo_rejeicao;								// string de 10 caracteres numericos contendo os 5 pares de código que definem os motivos
						$d['indicador_rateio'] = $detalhe->indicador_rateio;
						$d['nosso_numero'] = (int) substr($nndv, 0, strlen($nndv) - 1);
					break;
				}
				
				$valor = $detalhe->getValorTitulo();
				$d['valor_recebido'] = $valor;
				$d['data_vencimento'] = $detalhe->getDataVencimento() instanceof \DateTime ?$detalhe->getDataVencimento()->format('Y-m-d') : "";
				$d['codigo_ocorrencia'] = $detalhe->getCodigo();
				$d['ocorrencia'] = $detalhe->getCodigoNome();
				
				$aux = array();
				for ($i = 0; $i <= 8; $i += 2) {
					$code = substr($d['motivo_rejeicao'], $i, 2);
					if (! in_array($code, $aux))
						$aux[] = $code;
				}
				
				$detalhe_ocorrencia = array();

				foreach ($aux as $cod) {
					$tmp = $this->getMotivoDetalhe($detalhe->_codigo_banco, $d['codigo_ocorrencia'], $cod);
					if ($tmp != '') {
						$detalhe_ocorrencia[$d['codigo_ocorrencia'].$cod] = $tmp;
					}
				}
				
				$d['ocorrencia_detalhe'] = join(' | ', $detalhe_ocorrencia);
				$d['rateios'] = array();
				if (isset($rateios[$nndv.$d['codigo_ocorrencia']])) {
					foreach ($rateios[$nndv.$d['codigo_ocorrencia']] as $rateio) {
						$r['nome_beneficiario_1'] 			= $rateio->nome_beneficiario_1;
						$r['numero_conta_beneficiario_1'] 	= $rateio->numero_conta_beneficiario_1;
						$r['digito_conta_beneficiario_1'] 	= $rateio->digito_conta_beneficiario_1;
						$r['digito_conta_beneficiario_1'] 	= $rateio->digito_conta_beneficiario_1;
						$r['valor_beneficiario_1'] 			= $rateio->valor_beneficiario_1;
						$r['tipo_valor_informado_1'] 		= $rateio->tipo_valor_informado;
						$r['motivo_ocorrencia_1'] 			= $rateio->motivo_ocorrencia_1;
						
						$r['nome_beneficiario_2'] 			= $rateio->nome_beneficiario_2;
						$r['numero_conta_beneficiario_2'] 	= $rateio->numero_conta_beneficiario_2;
						$r['digito_conta_beneficiario_2'] 	= $rateio->digito_conta_beneficiario_2;
						$r['digito_conta_beneficiario_2'] 	= $rateio->digito_conta_beneficiario_2;
						$r['valor_beneficiario_2'] 			= $rateio->valor_beneficiario_2;
						$r['tipo_valor_informado_2'] 		= $rateio->tipo_valor_informado;
						$r['motivo_ocorrencia_2'] 			= $rateio->motivo_ocorrencia_2;
						
						$r['nome_beneficiario_3'] 			= $rateio->nome_beneficiario_3;
						$r['numero_conta_beneficiario_3'] 	= $rateio->numero_conta_beneficiario_3;
						$r['digito_conta_beneficiario_3'] 	= $rateio->digito_conta_beneficiario_3;
						$r['digito_conta_beneficiario_3'] 	= $rateio->digito_conta_beneficiario_3;
						$r['valor_beneficiario_3'] 			= $rateio->valor_beneficiario_3;
						$r['tipo_valor_informado_3'] 		= $rateio->tipo_valor_informado;
						$r['motivo_ocorrencia_3'] 			= $rateio->motivo_ocorrencia_3;
						
						$d['rateios'][] = $r;
					}
				}
				
				$sai[] = $d;
			}
		}
		return $sai;
	}

	/**
	 * Retorna a descrição de um dado motivo de uma determinada ocorrência
	 *
	 * @param int $ocorrencia
	 *        	código da ocorrência
	 * @param int $detalhe
	 *        	código do motivo
	 * @return string descrição do motivo
	 */
	private function getMotivoDetalhe($banco, $ocorrencia, $detalhe)
	{
		$sai = '';
		
		$ocorrencia = str_pad(intval($ocorrencia), 2, '0', STR_PAD_LEFT);
		$detalhe = str_pad(intval($detalhe), 2, '0', STR_PAD_LEFT);
		if ($banco == 237) {
			
			$tabelaDetalhes = array(
				// 02 - Entrada Confirmada
				'02_00' => 'Ocorrência aceita',
				'02_01' => 'Código do Banco inválido',
				'02_04' => 'Código do movimento não permitido para a carteira',
				'02_15' => 'Características da cobrança incompatíveis',
				'02_17' => 'Data de vencimento anterior a data de emissão',
				'02_21' => 'Espécie do Título inválido',
				'02_24' => 'Data da emissão inválida',
				'02_27' => 'Valor/taxa de juros mora inválido',
				'02_38' => 'Prazo para protesto inválido',
				'02_39' => 'Pedido para protesto não permitido para título',
				'02_43' => 'Prazo para baixa e devolução inválido',
				'02_45' => 'Nome do Pagador inválido',
				'02_46' => 'Tipo/num. de inscrição do Pagador inválidos',
				'02_47' => 'Endereço do Pagador não informado',
				'02_48' => 'CEP Inválido',
				'02_50' => 'CEP referente a Banco correspondente',
				'02_53' => 'No de inscrição do Pagador/avalista inválidos (CPF/CNPJ)',
				'02_54' => 'Pagadorr/avalista não informado',
				'02_67' => 'Débito automático agendado',
				'02_68' => 'Débito não agendado - erro nos dados de remessa',
				'02_69' => 'Débito não agendado - Pagador não consta no cadastro de autorizante',
				'02_70' => 'Débito não agendado - Beneficiário não autorizado pelo Pagador',
				'02_71' => 'Débito não agendado - Beneficiário não participa da modalidade de déb.automático',
				'02_72' => 'Débito não agendado - Código de moeda diferente de R$',
				'02_73' => 'Débito não agendado - Data de vencimento inválida/vencida',
				'02_75' => 'Débito não agendado - Tipo do número de inscrição do pagador debitado inválido',
				'02_76' => 'Pagador Eletrônico DDA - Esse motivo somente será disponibilizado no arquivo retorno para as empresas cadastradas nessa condição',
				'02_86' => 'Seu número do documento inválido',
				'02_89' => 'Email Pagador não enviado – título com débito automático',
				'02_90' => 'Email pagador não enviado – título de cobrança sem registro',
				
				// 03 - Entrada Rejeitada
				'03_02' => 'Código do registro detalhe inválido',
				'03_03' => 'Código da ocorrência inválida',
				'03_04' => 'Código de ocorrência não permitida para a carteira',
				'03_05' => 'Código de ocorrência não numérico',
				'03_07' => 'Agência/conta/Digito - Inválido',
				'03_08' => 'Nosso número inválido',
				'03_09' => 'Nosso número duplicado',
				'03_10' => 'Carteira inválida',
				'03_13' => 'Identificação da emissão do bloqueto inválida',
				'03_16' => 'Data de vencimento inválida',
				'03_18' => 'Vencimento fora do prazo de operação',
				'03_20' => 'Valor do Título inválido',
				'03_21' => 'Espécie do Título inválida',
				'03_22' => 'Espécie não permitida para a carteira',
				'03_24' => 'Data de emissão inválida',
				'03_28' => 'Código do desconto inválido',
				'03_38' => 'Prazo para protesto inválido',
				'03_44' => 'Agência Beneficiário não prevista',
				'03_45' => 'Nome do pagador não informado',
				'03_46' => 'Tipo/número de inscrição do pagador inválidos',
				'03_47' => 'Endereço do pagador não informado',
				'03_48' => 'CEP Inválido',
				'03_50' => 'CEP irregular - Banco Correspondente',
				'03_63' => 'Entrada para Título já cadastrado',
				'03_65' => 'Limite excedido',
				'03_66' => 'Número autorização inexistente',
				'03_68' => 'Débito não agendado - erro nos dados de remessa',
				'03_69' => 'Débito não agendado - Pagador não consta no cadastro de autorizante',
				'03_70' => 'Débito não agendado - Beneficiário não autorizado pelo Pagador',
				'03_71' => 'Débito não agendado - Beneficiário não participa do débito Automático',
				'03_72' => 'Débito não agendado - Código de moeda diferente de R$',
				'03_73' => 'Débito não agendado - Data de vencimento inválida',
				'03_74' => 'Débito não agendado - Conforme seu pedido, Título não registrado',
				'03_75' => 'Débito não agendado – Tipo de número de inscrição do debitado inválido'
			);
			
			$key = $ocorrencia . "_" . $detalhe;
			
			$sai = $tabelaDetalhes[$key];
		}
		
		if ($banco == \Cnab\Banco::SICREDI) {
			$key=$ocorrencia;
			$tabelaDetalhes = array (
				'01' => 'Código do banco inválido',
				'02' => 'Código do registro detalhe inválido',
				'03' => 'Código da ocorrência inválido',
				'04' => 'Código de ocorrência não permitida para a carteira',
				'05' => 'Código de ocorrência não numérico',
				'07' => 'Cooperativa/agência/conta/dígito inválidos',
				'08' => 'Nosso número inválido',
				'09' => 'Nosso número duplicado',
				'10' => 'Carteira inválida',
				'14' => 'Título protestado',
				'15' => 'Cooperativa/carteira/agência/conta/nosso número inválidos',
				'16' => 'Data de vencimento inválida',
				'17' => 'Data de vencimento anterior à data de emissão',
				'18' => 'Vencimento fora do prazo de operação',
				'20' => 'Valor do título inválido',
				'21' => 'Espécie do título inválida',
				'22' => 'Espécie não permitida para a carteira',
				'24' => 'Data de emissão inválida',
				'29' => 'Valor do desconto maior/igual ao valor do título 31 Concessão de desconto - existe desconto anterior',
				'33' => 'Valor do abatimento inválido',
				'34' => 'Valor do abatimento maior/igual ao valor do título',
				'36' => 'Concessão de abatimento - existe abatimento anterior',
				'38' => 'Prazo para protesto inválido',
				'39' => 'Pedido para protesto não permitido para o título',
				'40' => 'Título com ordem de protesto emitida',
				'41' => 'Pedido cancelamento/sustação sem instrução de protesto',
				'44' => 'Cooperativa de crédito/agência beneficiária não prevista',
				'45' => 'Nome do pagador inválido',
				'46' => 'Tipo/número de inscrição do pagador inválidos',
				'47' => 'Endereço do pagador não informado', 
				'48' => 'CEP irregular', 
				'49' => 'Número de Inscrição do pagador/avalista inválido',
				'50' => 'Pagador/avalista não informado',
				'60' => 'Movimento para título não cadastrado 63 Entrada para título já cadastrado',
				' A' => 'Aceito',
				' D' => 'Desprezado',
				'A ' => 'Aceito',
				'D ' => 'Desprezado',
				'A1' => 'Praça do pagador não cadastrada.',
				'A2' => 'Tipo de cobrança do título divergente com a praça do pagador.',
				'A3' => 'Cooperativa/agência depositária divergente: atualiza o cadastro de praças da Coop./agência beneficiária',
				'A4' => 'Beneficiário não cadastrado ou possui CGC/CIC inválido',
				'A5' => 'Pagador não cadastrado',
				'A6' => 'Data da instrução/ocorrência inválida',
				'A7' => 'Ocorrência não pode ser comandada',
				'A8' => 'Recebimento da liquidação fora da rede Sicredi - via compensação eletrônica',
				'B4' => 'Tipo de moeda inválido',
				'B5' => 'Tipo de desconto/juros inválido',
				'B6' => 'Mensagem padrão não cadastrada',
				'B7' => 'Seu número inválido',
				'B8' => 'Percentual de multa inválido',
				'B9' => 'Valor ou percentual de juros inválido',
				'C1' => 'Data limite para concessão de desconto inválida',
				'C2' => 'Aceite do título inválido',
				'C3' => 'Campo alterado na instrução “31 – alteração de outros dados” inválido',
				'C4' => 'Título ainda não foi confirmado pela centralizadora',
				'C5' => 'Título rejeitado pela centralizadora',
				'C6' => 'Título já liquidado',
				'C7' => 'Título já baixado',
				'C8' => 'Existe mesma instrução pendente de confirmação para este título',
				'C9' => 'Instrução prévia de concessão de abatimento não existe ou não confirmada',
				'D1' => 'Título dentro do prazo de vencimento (em dia)',
				'D2' => 'Espécie de documento não permite protesto de título',
				'D3' => 'Título possui instrução de baixa pendente de confirmação',
				'D4' => 'Quantidade de mensagens padrão excede o limite permitido',
				'D5' => 'Quantidade inválida no pedido de boletos pré-impressos da cobrança sem registro',
				'D6' => 'Tipo de impressão inválida para cobrança sem registro',
				'D7' => 'Cidade ou Estado do pagador não informado',
				'D8' => 'Seqüência para composição do nosso número do ano atual esgotada',
				'D9' => 'Registro mensagem para título não cadastrado',
				'E2' => 'Registro complementar ao cadastro do título da cobrança com e sem registro não cadastrado',
				'E3' => 'Tipo de postagem inválido, diferente de S, N e branco',
				'E4' => 'Pedido de boletos pré-impressos',
				'E5' => 'Confirmação/rejeição para pedidos de boletos não cadastrado',
				'E6' => 'Pagador/avalista não cadastrado',
				'E7' => 'Informação para atualização do valor do título para protesto inválido',
				'E8' => 'Tipo de impressão inválido, diferente de A, B e branco',
				'E9' => 'Código do pagador do título divergente com o código da cooperativa de crédito',
				'F1' => 'Liquidado no sistema do cliente',
				'F2' => 'Baixado no sistema do cliente',
				'F3' => 'Instrução inválida, este título está caucionado/descontado',
				'F4' => 'Instrução fixa com caracteres inválidos',
				'F6' => 'Nosso número / número da parcela fora de seqüência – total de parcelas inválido',
				'F7' => 'Falta de comprovante de prestação de serviço',
				'F8' => 'Nome do beneficiário incompleto / incorreto.',
				'F9' => 'CNPJ / CPF incompatível com o nome do pagador / Sacador Avalista',
				'G1' => 'CNPJ / CPF do pagador Incompatível com a espécie',
				'G2' => 'Título aceito: sem a assinatura do pagador',
				'G3' => 'Título aceito: rasurado ou rasgado',
				'G4' => 'Título aceito: falta título (cooperativa/ag. beneficiária deverá enviá-lo)',
				'G5' => 'Praça de pagamento incompatível com o endereço',
				'G6' => 'Título aceito: sem endosso ou beneficiário irregular',
				'G7' => 'Título aceito: valor por extenso diferente do valor numérico',
				'G8' => 'Saldo maior que o valor do título',
				'G9' => 'Tipo de endosso inválido',
				'H1' => 'Nome do pagador incompleto / Incorreto',
				'H2' => 'Sustação judicial',
				'H3' => 'Pagador não encontrado',
				'H4' => 'Alteração de carteira',
				'H5' => 'Recebimento de liquidação fora da rede Sicredi – VLB Inferior – Via Compensação',
				'H6' => 'Recebimento de liquidação fora da rede Sicredi – VLB Superior – Via Compensação',
				'H7' => 'Espécie de documento necessita beneficiário ou avalista PJ',
				'H8' => 'Recebimento de liquidação fora da rede Sicredi – Contingência Via Compe',
				'H9' => 'Dados do título não conferem com disquete',
				'I1' => 'Pagador e Sacador Avalista são a mesma pessoa',
				'I2' => 'Aguardar um dia útil após o vencimento para protestar',
				'I3' => 'Data do vencimento rasurada',
				'I4' => 'Vencimento – extenso não confere com número',
				'I5' => 'Falta data de vencimento no título',
				'I6' => 'DM/DMI sem comprovante autenticado ou declaração',
				'I7' => 'Comprovante ilegível para conferência e microfilmagem',
				'I8' => 'Nome solicitado não confere com emitente ou pagador',
				'I9' => 'Confirmar se são 2 emitentes. Se sim, indicar os dados dos 2',
				'J1' => 'Endereço do pagador igual ao do pagador ou do portador',
				'J2' => 'Endereço do apresentante incompleto ou não informado',
				'J3' => 'Rua/número inexistente no endereço',
				'J4' => 'Falta endosso do favorecido para o apresentante',
				'J5' => 'Data da emissão rasurada',
				'J6' => 'Falta assinatura do pagador no título',
				'J7' => 'Nome do apresentante não informado/incompleto/incorreto',
				'J8' => 'Erro de preenchimento do titulo',
				'J9' => 'Titulo com direito de regresso vencido',
				'K1' => 'Titulo apresentado em duplicidade',
				'K2' => 'Titulo já protestado',
				'K3' => 'Letra de cambio vencida – falta aceite do pagador',
				'K4' => 'Falta declaração de saldo assinada no título',
				'K5' => 'Contrato de cambio – Falta conta gráfica',
				'K6' => 'Ausência do documento físico',
				'K7' => 'Pagador falecido',
				'K8' => 'Pagador apresentou quitação do título',
				'K9' => 'Título de outra jurisdição territorial',
				'L1' => 'Título com emissão anterior a concordata do pagador',
				'L2' => 'Pagador consta na lista de falência',
				'L3' => 'Apresentante não aceita publicação de edital',
				'L4' => 'Dados do Pagador em Branco ou inválido',
				'L5' => 'Código do Pagador na agência beneficiária está duplicado',
				'M1' => 'Reconhecimento da dívida pelo pagador',
				'M2' => 'Não reconhecimento da dívida pelo pagador',
				'X1' => 'Regularização centralizadora – Rede Sicredi',
				'X2' => 'Regularização centralizadora – Compensação',
				'X3' => 'Regularização centralizadora – Banco correspondente',
				'X4' => 'Regularização centralizadora - VLB Inferior - via compensação',
				'X5' => 'Regularização centralizadora - VLB Superior - via compensação',
				'X0' => 'Pago com cheque',
				'X6' => 'Pago com cheque – bloqueado 24 horas',
				'X7' => 'Pago com cheque – bloqueado 48 horas',
				'X8' => 'Pago com cheque – bloqueado 72 horas',
				'X9' => 'Pago com cheque – bloqueado 96 horas',
				'XA' => 'Pago com cheque – bloqueado 120 horas',
				'XB' => 'Pago com cheque – bloqueado 144 horas'
			);
			//$key = $detalhe;
			$sai = $tabelaDetalhes[$key];
		}
		
		return $sai;
	}
}
