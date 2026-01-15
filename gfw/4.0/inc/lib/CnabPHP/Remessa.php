<?php
include_once 'vendor/autoload.php';

/***
 * A finalidade dessa classe é fazer a interface entre a biblioteca CnabPHP e o gFW
 * permitindo que o controle de dependências e instanciação das classes da
 * biblioteca fiquem independetes do gFW.
 *
 * Essa classe gera um arquivo de remessa para envio ao banco
 *
 * @author André Luiz
 * @version 1.0 05-01-2017 15:00
 *
 */

const  BANCO_DO_BRASIL = 1;
const  SANTANDER = 33;
const  CEF = 104;
const  BRADESCO = 237;
const  ITAU = 341;

class Remessa
{
	private  $codigo_banco;
	private $arquivo;

	public function __construct($codigo_banco) {
		$this->codigo_banco = $codigo_banco;
		$this->arquivo = new Cnab\Remessa\Cnab400\Arquivo($codigo_banco);
	}

	/**
	 * Cria a primeira linha do arquivo
	 * @param array $cabecalho
	 */
	public function insereCabecalho(array $cabecalho) {

		/*
		 	Embora tenda a um padrão, cada banco pode especificar peculiaridades.

		 	Relação de campos por bancos

		 	BRADESCO =	[
		 					razao_social,
		 					banco,
		 					identificacao_sistema,
		 					sequencial_remessa,
		 					codigo_cobranca
		 				]
		 	ITAU	=	[
		 					nome_fantasia,
							razao_social,
							cnpj,
							banco,
							logradouro,
							numero,
							bairro,
							cidade,
							uf,
							cep,
							agencia,
							conta,
							conta_dac,
		 				]

		 */

		$cabecalho['banco'] = $this->codigo_banco;
		$this->arquivo->configure($cabecalho);
	}

	/**
	 * Adiciona uma linha de detalhe ao arquivo de remessa
	 * @param array $boleto
	 */
	public function insereDetalhe(array $boleto) {

		/*
		 	Relação de campos por bancos

		  	BRADESCO = 	[
						   	nosso_numero
						   	digito_nosso_numero
						   	numero_documento
						   	carteira
						   	especie
						   	valor
						   	instrucao1
						   	instrucao2
						   	sacado_nome
						   	sacado_tipo
						   	sacado_cpf
						   	sacado_logradouro
						   	sacado_bairro
						   	sacado_cep
						    sacado_cidade
						    sacado_uf
						    data_vencimento
						    data_cadastro
						    juros_de_um_dia
						    data_desconto
						    valor_desconto
						    mensagem
						    data_multa
						    valor_multa
							agencia_beneficiaria
							conta_corrente_beneficiaria
							conta_corrente_beneficiaria_dv
							numero_participante
		  				]
		  	ITAU = 		[
	  					   	codigo_ocorrencia
						   	nosso_numero
						   	digito_nosso_numero
						   	numero_documento
						   	carteira
						   	especie
						   	valor
						   	instrucao1
						   	instrucao2
						   	sacado_nome
						   	sacado_tipo
						   	sacado_cpf
						   	sacado_logradouro
						   	sacado_bairro
						   	sacado_cep
						   	sacado_cidade
						   	sacado_uf
						   	data_vencimento
						   	data_cadastro
						   	juros_de_um_dia
						   	data_desconto
						   	valor_desconto
						   	prazo
						   	taxa_de_permanencia
						   	mensagem
						   	data_multa
						   	valor_multa
		  				]
		 */
// 		echo '<pre>'; var_dump($boleto); exit;
		$this->arquivo->insertDetalhe($boleto);
	}

	/**
	 * Salva o arquivo de remessa
	 * @param string $caminho diretorio onde o arquivo deve ser salvo
	 */
	public function salvarArquivo($caminho='') {

		if($caminho == '')
			$caminho = sys_get_temp_dir();

		$nome = 'remessa' . date('YmdHis');
		$ext = '.txt';
		if($this->codigo_banco == \Cnab\Banco::BRADESCO)
		{
			$nome = 'CB' . date('dm') . '00';
// 			$ext = '.rem'; // Produção
			$ext = '.tst'; // Teste

		}

		$this->arquivo->save($caminho.$nome.$ext);
		
	}
	
	public function downloadFile($nome) {
		
		$caminho = $caminho = sys_get_temp_dir();
		$arquivoNome = "$caminho/$nome";
		
		$this->arquivo->save($arquivoNome);
		
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename="'.$nome.'"');
		header('Content-Type: application/octet-stream');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . filesize($arquivoNome));
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Expires: 0');
		readfile($arquivoNome);
		
		unlink($arquivoNome);
		exit;
	}

	/**
	 * Obtem o conteudo o arquivo de remessa
	 * @return string
	 */
	public function obtemArquivo() {
		return $this->arquivo->getText();
	}

}

?>
