<?php
namespace Cnab\Retorno\Cnab400;

class Rateio extends \Cnab\Format\Linha
{
	public function __construct(\Cnab\Retorno\IArquivo $arquivo)
	{
		$codigo_banco = $arquivo->codigo_banco;
		$yamlLoad = new \Cnab\Format\YamlLoad($codigo_banco);
		$yamlLoad->load($this, 'cnab400', 'retorno/rateio');
	}
}