<?php

/**
 * Gerar um arquivo .txt para imprimir em impressoras matriciais.
 * Adaptado de http://blog.unifick.com.br/tutoriais/imprimindo-com-php-na-impressora-nao-fiscal-bematech-mp-20-mi
 *
 * @author André Luiz
 * @version 1.0 02-03-2017 09:00
 *
 */
class gMatrixPrinter
{
	// padrão: 40 colunas por linha
	protected $txt_cabecalho = [];
	protected $txt_corpo = [];
	protected $txt_corpo_config = []; 		// Qtd de colunas para cada informação
	protected $txt_rodape = [];
	public $filename='imprimir.txt';
	public $useCrypto=false;
	function __construct($n_colunas = 40)
	{
		$this->n_colunas = $n_colunas;
	}

	/**
	 * Adiciona a quantidade necessaria de espaços no inicio
	 * da string informada para deixa-la centralizada na tela
	 *
	 * @global int $n_colunas Numero maximo de caracteres aceitos
	 * @param string $info String a ser centralizada
	 * @return string
	 */
 	function centraliza($info): string
	{
		// $aux = strlen($info);
		//
		// if ($aux < $this->n_colunas) {
		// 	// calcula quantos espaços devem ser adicionados
		// 	// antes da string para deixa-la centralizada
		// 	$espacos = floor(($this->n_colunas - $aux) / 2);
		//
		// 	$espaco = '';
		// 	for ($i = 0; $i < $espacos; $i++){
		// 		$espaco .= ' ';
		// 	}
		//
		// 	// retorna a string com os espaços necessários para centraliza-la
		// 	return $espaco.$info;
		//
		// } else {
		// 	// se for maior ou igual ao número de colunas
		// 	// retorna a string cortada com o número máximo de colunas.
		// 	return substr($info, 0, $this->n_colunas);
		// }
		//
		return(substr(str_pad($info, $this->n_colunas, ' ', STR_PAD_BOTH),0,$this->n_colunas));
	}

	/**
	 * Adiciona a quantidade de espaços informados na String
	 * passada na possição informada.
	 *
	 * Se a string informada for maior que a quantidade de posições
	 * informada, então corta a string para ela ter a quantidade
	 * de caracteres exata das posições.
	 *
	 * @param string $string String a ter os espaços adicionados.
	 * @param int $posicoes Qtde de posições da coluna
	 * @param string $onde Onde será adicionar os espaços. I (inicio) ou F (final).
	 * @return string
	 */
	function addEspacos(string $string, $posicoes, $onde): string
	{

		$aux = strlen($string);

		if ($aux >= $posicoes) {
			return substr ($string, 0, $posicoes);
		}

			$dif = $posicoes - $aux;

			$espacos = '';

			for ($i = 0; $i < $dif; $i++) {
				$espacos .= ' ';
			}

			if ($onde === 'I') {
				return $espacos.$string;
			} else {
				return $string.$espacos;
			}

	}

	/**
	 * Adiciona uma linha ao cabeçalho
	 *
	 * @param string $string
	 */
	function addCabecalho($string): void
	{
		$this->txt_cabecalho[] = $string;
	}

	/**
	 * Adiciona um texto grande de uma vez
	 *
	 * @param string $string
	 */
	function addTexto($s, $cabecalho = true): void
	{
		while ($s != "")
		{
			$s_tmp = substr((string) $s,0,$this->n_colunas);
			$p = strrpos($s_tmp,' ');
			if ($p === false || strlen((string) $s) < $this->n_colunas) {
				if ($cabecalho) {
					$this->addCabecalho($s);
				} else {
					$this->addRodape($s);
				}

				$s = '';
			} else {

				if ($cabecalho) {
					$this->addCabecalho(substr((string) $s,0,$p));
				} else {
					$this->addRodape(substr((string) $s,0,$p));
				}
				$s = substr((string) $s,$p+1);
			}
		}
	}


	/**
	 * Adiciona uma linha ao corpo
	 *
	 * @param array $array
	 */
	function addCorpo($array): void
	{
		$this->txt_corpo[] = $array;
	}

	/**
	 * Adiciona uma linha ao rodapé
	 *
	 * @param string $string
	 */
	function addRodape($string): void
	{
		$this->txt_rodape[] = $string;
	}

	/**
	 * Gera uma linha inteira com o caracter passado como paramentro
	 * @param string $caracter
	 * @return string
	 */
 	function linhaSeparadora(string $caracter = '='): string
	{
		$sai = '';
		for($i = 0; $i < $this->n_colunas; $i++) {
			$sai .= $caracter;
		}

		return $sai;
	}

	/**
	 * Configura a quantidade de linhas de cada coluna do corpo
	 *
	 * @param array $config
	 */
	function configColunasCorpo($config): void
	{
		$this->txt_corpo_config = $config;
	}

	/**
	 * Gera o arquivo TXT com as informações a serem impressas e força o download do arquivo
	 */
	function gerarTxt(): void
	{
		/* para cada linha de item (array) existente no array $txt_corpo,
		 * adiciona cada posição da linha em um novo array $itens
		 * fazendo a formatação dos espaçamentos entre cada coluna
		 * da linha através da função "addEspacos"
		 */

		$aux_qtd = count($this->txt_corpo_config);
		foreach ($this->txt_corpo as $item) {

			$linha = '';
			for ($i = 0; $i < $aux_qtd; $i++) {
				$linha .= $this->addEspacos($item[$i], $this->txt_corpo_config[$i], 'F');
			}

			$itens[] = $linha;
		}

		/* concatena o cabelhaço, o corpo, e o rodapé
		 * adicionando uma quebra de linha "\r\n" ao final de cada
		 * item dos arrays $txt_cabecalho, $itens, $txt_rodape
		 */
		$txt = implode("\r\n", $this->txt_cabecalho)
				. "\r\n"
				. implode("\r\n", $itens)
				. "\r\n"
				. implode("\r\n", $this->txt_rodape);

		$txt = normalize($txt); // remove acentos

		// // caminho e nome onde o TXT será criado no servidor
		// $file = '/tmp/arquivo-''.txt';
		//
		// // cria o arquivo
		// $_file  = fopen($file,"w");
		//
		// fwrite($_file,$txt);
		// fclose($_file);

		if ($this->useCrypto) {
			$this->filename='imprimir.prn';
		}

		header("Pragma: public");
		// Força o header para salvar o arquivo
		header("Content-type: application/save");
		header("X-Download-Options: noopen "); // For IE8
		header("X-Content-Type-Options: nosniff"); // For IE8
		// Pré define o nome do arquivo
		header("Content-Disposition: attachment; filename=".$this->filename);
		header("Expires: 0");
		header("Pragma: no-cache");

		// Lê o arquivo para download
		//readfile($file);

		if ($this->useCrypto) {
			echo(base64_encode($txt));
		} else {
			echo($txt);
		}
		exit;
	}

}

/*
 Exemplo:

$bm = new gMatrixPrinter();
$bm->addCabecalho( $bm->centraliza('Auto Escola GiuSoft Ltda') );
$bm->addCabecalho(' ');
$bm->addCabecalho('Tels: 1234-4567 / 7899-1234');
$bm->addCabecalho('CNPJ: 132132131313');
$bm->addCabecalho(' ');
$bm->addCabecalho(date('d-m-Y H:i:s'));
$bm->addCabecalho($bm->linhaSeparadora());
$bm->addCabecalho("===========[ Aulas Práticas ]===========");
$bm->addCabecalho($bm->linhaSeparadora());
$bm->addCabecalho(' ');
$bm->addCabecalho("123/17 NOME DO ALUNO DA SILVA SAURO");
$bm->addCabecalho(' ');
$bm->addCabecalho($bm->linhaSeparadora());
$bm->configColunasCorpo(array(9,8,9,4,6));
$bm->addCorpo(array('Marca', 'Placa', 'Data', 'Dia', 'Hora'));
$bm->addCorpo(array('=========', '========', '=========', '====', '======'));
$bm->addCorpo(array("Simulado","PHP1234","02-03-17","Qui","11:00"));
$bm->addRodape($bm->linhaSeparadora());
$bm->addRodape("Total de Treinos: 1");
$bm->addRodape($bm->linhaSeparadora());
$bm->addRodape(' ');
$bm->addRodape(' ');
$bm->addRodape(' ');
$bm->addRodape(' ');
$bm->addRodape("Desmarcação em até 24Hs de antecedência");
$bm->addRodape("Usar calcado que se prenda aos pés");
$bm->gerarTxt();

*/
