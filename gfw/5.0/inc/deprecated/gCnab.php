<?php
require_once __DIR__ . '/lib/CnabPHP/Remessa.php';
require_once __DIR__ . '/lib/CnabPHP/Retorno.php';

class gCnab
{

	private $remessa;
	private $retorno;

	/**
	 * Cria uma nova remessa.
	 * @param integer $codigo_banco ( BANCO DO BRASIL = 1; SANTANDER = 33; CEF = 104; BRADESCO = 237; ITAU = 341; )
	 *
	 */
	public function novaRemessa($codigo_banco): void
	{
		$this->remessa = new Remessa($codigo_banco);
	}


	/**
	 * Adiciona a linha de cabeçalho da remessa
	 * @param array $cabecalho
	 */
	public function remessaCabecalho($cabecalho): void
	{
		$this->remessa->insereCabecalho($cabecalho);
	}


	/**
	 * Adiciona uma linha de detalhe (boleto) à remessa
	 * @param array $boleto
	 */
	public function remessaDetalhe($boleto): void
	{
		$this->remessa->insereDetalhe($boleto);
	}


	/**
	 * Gera o conteúdo de remessa e salva em arquivo. Se o caminho não for especificado, salva na pasta temporaria "/tmp"
	 * @param string $caminho
	 */
	public function remessaSalvarEmArquivo($caminho=''): void
	{
		$this->remessa->salvarArquivo($caminho);
	}


	public function remessaDownloadFile($nome): void
	{
		$this->remessa->downloadFile($nome);
	}


	/**
	 * Gera o conteúdo de remessa e o retona numa string
	 * @return string remessa
	 */
	public function remessaSalvarEmString()
	{
		return $this->remessa->obtemArquivo();
	}


	/**
	 * Abre, para processamento, um arquivo de retorno
	 * @param string $caminho do arquivo de retorno
	 */
	public function novoRetorno($caminho): void
	{
		$this->retorno = new Retorno();
		$this->retorno->abreArquivo($caminho);
	}


	/**
	 * Processa os dados do arquivo de retorno e os devolve num array
	 * @return array
	 */
	public function retornoObtemDados()
	{
		return $this->retorno->mostrarDetalhes();
	}


}