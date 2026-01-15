<?php

/**
 * Classe responsável por gerar o arquivo do SPED
 * 
 * @author André Luiz
 * @version 1.0 28-11-2016 -> Guia Prático EFD-ICMS/IPI – Versão 2.0.19
 *
 */
class gSped {
	
	protected $registros;
	protected $xml;
	public $erros = array();
	private $regsQtdOcorrencia = array();
	private $blQtdLinhas = array();
	private $arquivoQtdLinhas = 0;
	private $arquivo;
	private $registroAtual = '0000'; 				// Instaciado com o primeiro registro
	private $params = array('dt_ini'=>'', 			// data de inicio
							'dt_fin'=>'', 			// data do final
							'ajuste'=>false, 		// Arquivo SPED de ajuste?
							'cod_part'=>0,			// Identificação do cliente no arquivo
							'unidades' => array()	// Descrição das unidades
							);
	private $arquivoAberto = false;
	private $blocosAbertos = array();
	private $blocosObrigatorios = array('C'=>true,'D'=>true,'E'=>true,'G'=>true,'H'=>true,'K'=>true); // os blocos 0, 1 e 9 não são incluidos porque são automaticamente gerados 
	private $arquivoUnidades = array();				// Unidades presentes no arquivo SPED
	private $arquivoProdutos = array();				// Produtos presentes no arquivo SPED
	private $arquivoClientes = array();				// Clientes presentes no arquivo SPED

	
	function __construct($xml = '', $params='')
	{
		$this->xml =  $xml;
		
		// >>>>
		// 0 - Primeiro bloco: Os regitros inciais (0000 e 0001) são gerados no método abreArquivo()
		// <<<<
		
		$this->registros[] = '0150';
		$this->registros[] = '0190';
		$this->registros[] = '0200';
		$this->registros[] = 'C100';
		// $this->registros[] = 'E100'; 				// PVA 2.3.0 Não permite a importação do bloco E
		
 		 				
		
		// >>>>
		// 9 - Último bloco: todos os registros são gerados no método fechaArquivo()
		// <<<<
			
		// Configura os parametros
		if(is_array($params))
		{
			foreach ($params as $k => $v)
				$this->params[$k] = $v;
		}
		
	}
	
	/**
	 * Gera os registros arquvio do SPED
	 *
	 */
	public function geraRegistros($xml='', $params='') 
	{
		$this->xml = $xml;
		if(is_array($params))
		{
			foreach ($params as $k => $param)
				$this->params[$k] = $param;
		}
		
		$registro = new Registros($xml,$this->params);
		if(!$this->arquivoAberto)
		{
			$this->abreArquivo($registro);
			$this->arquivoAberto = true;
		}
		
		foreach ($this->registros as $reg)
		{
			if($this->regFechaBloco($reg,true))
			{
				$this->regAbreBloco($reg,0);
			}
			if($reg <> '9000')
			{	
				$ret = $registro->obtemRegistros($reg,$this->params);
				if(is_array($ret)) 										// Alguns registros retornam mais de uma linha
				{
					foreach ($ret as $r)
					{
						$dado =  explode('|',$r);
					
						$bl = substr($dado[1], 0,1);
						$rgt = substr($dado[1], 1,4);
						if($this->confirmaLinha($r,$bl,$rgt))
						{	
							$this->arquivo[$bl][$rgt][] = $r;
							$this->contador($bl, $rgt);						// Incrementa os contadores
							
						}
					}
					
				}
				else
				{
					$bl = substr($reg, 0,1);
					$rgt = substr($reg, 1,4);
					
					if($this->confirmaLinha($ret,$bl,$rgt))
					{
						$this->arquivo[$bl][$rgt][] = $ret;
						$this->contador($bl, $rgt);						// Incrementa os contadores
					}
				}
				
			}
		}
		
		$this->regFechaBloco($this->registroAtual);						// Garante o fechamento/atualizacao do ultimo bloco
		
		foreach ($registro->obtemErros() as $erro)						// Captura os erros de campos obrigatórios
			$this->erros[] = $erro;
	}
	
	/**
	 * Gera as linhas iniciais do arquivo SPED
	 * Gera os registros de abertura do arquivo e de abertura do bloco zero.
	 * 
	 * @param object $registro referência de um objeto da classe Registros
	 */
	public function abreArquivo(&$registro)
	{
		
		// Abre o arquivo
		$this->arquivo['0']['000'][] = $registro->registro0000($this->params);
		$this->contador('0', '000');
		
		// Abre o bloco zero
		$this->arquivo['0']['001'][] = $registro->regAbreBloco('0000',0);
		$this->contador('0', '001');
		
		// >>>> Registros obrigatório que devem aparecer apenas uma vez no arquivo SPED
		$this->arquivo['0']['005'][] = $registro->registro0005();
		$this->contador('0', '005');
				
		$this->arquivo['0']['100'][] = $registro->registro0100($this->params);
		$this->contador('0', '100');
		// <<<<
	}
	
	/**
	 * Gera as linhas finais do arquivo SPED.
	 * Antes de gerar as linhas do bloco 9 o método chama a função que fará a crição dos demais blocos obrigatórios.
	 */
	public function fechaArquivo()
	{	
		$registro = new Registros();
		
		// Gera os demais blocos
		$this->geraBlocosVazios();
		
		// >>>> Gera os registros base do bloco 1 e garante a sua ordem no arquivo.
		$this->regAbreBloco('1001',0);
		$this->arquivo[1]['010'][] = $registro->registro1010($this->params);
		$this->contador(1, '010');
		$this->regFechaBloco('1990'); 
		// <<<< 
		
		// Gera os registros do bloco 9
		$bl9 = $registro->bloco9($this->regsQtdOcorrencia,$this->arquivoQtdLinhas);
		foreach ($bl9 as $bl)
		{
			$dado =  explode('|',$bl['dado']);
		
			$b = substr($dado[1], 0,1);
			$reg = substr($dado[1], 1,4);
			$this->arquivo[$b][$reg][] = $bl['dado'];
		}
		
	}
	
	/**
	 * Obtem o registro de fechametno de um bloco
	 * 
	 * @param string $reg_code código do registro
	 * @param boolean $verificaContexto deve ser utilizado pelos metodo da classe gSped para controle do fluxo
	 */
	public function regFechaBloco($reg_code, $verificaContexto = false) 
	{
		$sai = false;
		$reg_obj = new Registros($this->xml);
		
		if($verificaContexto)
		{
			if(substr($reg_code, 0,1) <> substr($this->registroAtual,0,1))
			{
				$qtd = $this->blQtdLinhas[substr($this->registroAtual, 0,1)] + 1; 	// + 1 Contabiliza o registro de fechamento no total de linha do bloco
				$fechamento = $reg_obj->regFechaBloco($this->registroAtual, $qtd);
			}

		}	
		else
		{
			$qtd = $this->blQtdLinhas[substr($reg_code, 0,1)] + 1; 					// + 1 Contabiliza o registro de fechamento no total de linha do bloco
			$fechamento = $reg_obj->regFechaBloco($reg_code, $qtd);
		}
		
		if($fechamento <> '')
		{
			$f = explode('|',$fechamento);
			$bl = substr($f[1], 0,1);
			$reg = substr($f[1], 1,4);
				
			$this->arquivo[$bl][$reg][0] = $fechamento; 							// Sobrescreve o reg de fechamento do bloco, atualizando a qtd de linhas do bloco
			$this->registroAtual = $reg_code;
				
			$sai = true;
		}
		
		return $sai;
	}
	
	/**
	 * Obtem o registro de abertura de um bloco
	 *
	 * @param string $reg_code código do registro
	 * @param integer $ind_mov indicacao de registros filhos, por padrão assume que não tem.
	 */
	public function regAbreBloco($reg_code, $ind_mov=1)
	{
		$sai = false;
		$reg_obj = new Registros($this->xml);
		if(substr($reg_code, 0,1) <> '0' && substr($reg_code, 0,1) <> '9') // aberturas geradas à parte
		{
			$abertura = $reg_obj->regAbreBloco($reg_code, $ind_mov);
			if($abertura <> '')
			{
				$f = explode('|',$abertura);
				$bl = substr($f[1], 0,1);
				$reg = substr($f[1], 1,4);
				
				if(!isset($this->blocosAbertos[$bl.$reg]))
					$this->blocosAbertos[$bl.$reg] = false;
				
				if(!$this->blocosAbertos[$bl.$reg]) // Evita a duplicidade de abertura
				{
					$this->arquivo[$bl][$reg][] = $abertura;
					
					$this->contador($bl, $reg);
		
					$this->blocosAbertos[$bl.$reg] = true;
					$this->blocosObrigatorios[$bl] = false;
					$sai = true;
				}
			}
		}
		return $sai;
	}
	
	/**
	 * Gera os blocos obrigatórios que não foram gerados através do método geraRegistros.
	 * Esse método garante que todos os blocos obrigatórios exitam no arquivo SPED.
	 */
	public function geraBlocosVazios()
	{
		foreach ($this->blocosObrigatorios as $bloco=>$naoGerado)
		{
			if($naoGerado)
			{
				$this->regAbreBloco($bloco);
				$this->regFechaBloco($bloco);
			}
		}	
	}
	
	/**
	 * Monta e devolve o arquivo SPED
	 * 
	 * @return array $arquivo contendo em cada posição uma linha do arquivo SPED
	 */
	public function obtemArquivo()
	{
		$arquivo = '';
		$this->fechaArquivo();
		
		foreach($this->arquivo as $bl=>$regs)
		{
			// >>>> Realiza a ordenação dos registros dentro do bloco
			$regLinhas = array_keys($regs);
			foreach($regLinhas as $k => $v)
			{
				$regLinhas[$k] = intval($v);			// força o valor inteiro para fazer a ordenação
			}
			sort($regLinhas); 							// ordena 
			// <<<<
			
			foreach($regLinhas as $linhas)
				foreach($this->arquivo[$bl][str_pad($linhas, 3,'0',STR_PAD_LEFT)] as $linha)
					$arquivo[]= $linha;
		}
		
		return $arquivo;
	}
	
	/**
	 * Realiza uma última verificação antes de inserir a linha. 
	 * Esse método garante que a inclusão da linha não fere o padrão de determinados blocos
	 *  
	 * @param string $r linha a ser inserida
	 * @param string $bl referencia do bloco
	 * @param string $rgt referencia do registro
	 * @return boolean
	 */
	public function confirmaLinha(&$r,$bl,$rgt)
	{
		$sai = true;
		$campos = explode('|',$r);
		
		// Evita a existencias de linhas vazias
		if($r=='')
			return false;
		
		if($bl.$rgt == '0150')
		{
			// Verifica unicidade do cliente
			if(!in_array($campos[5], $this->arquivoClientes))
			{
				$this->arquivoClientes[]=$campos[5];
			}
			else
				return false;
		}
		
		if($bl.$rgt == '0100')
		{
			// Verifica unicidade de ocorrência
			if(isset($this->regsQtdOcorrencia['0100']))
				return false;
		}
		
		if($bl.$rgt == '0190')
		{
			// Verifica unicidade de unidade
			if(in_array($campos[2], $this->arquivoUnidades))
				return false;
			else 
				$this->arquivoUnidades[] = $campos[2];
		}

		if($bl.$rgt == '0200')
		{
			// Verifica unicidade do produto
			if(in_array($campos[2], $this->arquivoProdutos))
				return false;
			else 
				$this->arquivoProdutos[] = $campos[2];
		}

		
		if($bl == 'C')
		{
			/*  
			  bloco C exige que os os registros filhos estejam posicionados abaixo do registro pai. 
			  Por ele é tratado de forma diferente dos demais
			*/
			$all = '989'; // Esse registro não existe, apenas criei um posição no array para garantir a ordenação dos dados
			$this->arquivo[$bl][$all][] = $r;
			
			$this->contador($bl, $rgt);
						
			return false;
		}
		
		if($bl.$rgt == 'E100')
		{
			/* ASSUMINDO QUE O PERIODO DE APURAÇÃO DO ICMS É IGUAL AO INTERVALO DO ARQUIVO SPED (BLOCO 0000) */
			// Verifica unicidade de ocorrência
			if(isset($this->regsQtdOcorrencia['E100']))
				return false;
		}
		
		if($bl.$rgt == '1010')
		{
			// Verifica unicidade de ocorrência
			if(isset($this->regsQtdOcorrencia['1010']))
				return false;
		}
		
		return $sai;
	}
	
	/**
	 * Atualiza os contadores de ocorrência de registros, linhas por bloco, e linhas do arquivo.
	 * 
	 * @param string $bl referência do bloco
	 * @param string $rgt refência do registro
	 */
	private function contador($bl, $rgt)
	{
		if(!isset($this->regsQtdOcorrencia[$bl.$rgt]))
			$this->regsQtdOcorrencia[$bl.$rgt]=0; 			// Adiciona o registro no contador
				
		if(!isset($this->blQtdLinhas[$bl]))					// Adiciona o bloco no contador
			$this->blQtdLinhas[$bl]=0;
			
		if($rgt == '990')
			$this->regsQtdOcorrencia[$bl.$rgt]=1; 			// Registros de fechamento de bloco, só devem aparecer uma vez
		else
			$this->regsQtdOcorrencia[$bl.$rgt]++; 			// Contabiliza a quantidade de ocorrencias do registro
			
		$this->blQtdLinhas[$bl]++;						// Contabiliza o total de linha do bloco
		$this->arquivoQtdLinhas++; 						// Contabiliza o total de linhas do arquivo
	}
	
}


/**
 * Classe para a geração dos registros dos blocos do arquivo SPED
*
* @author André Luiz
* @version 1.0 28-11-2016  -> Guia Prático EFD-ICMS/IPI – Versão 2.0.19
*
*/
class Registros
{
	protected  $xml = '';
	private $erros = array();
	private $cod_ver = '';

	public function __construct($xml = '', $params)
	{
		if($xml <> '')
		{
			/* 
			 	André Luiz em 01-02-17 
			 	Detectamos um problema com as notas importadas: algumas não vem com as tags <nfeproc> </nfeproc> gerando erro ao criar o arquivo do SPED
			 	Por isso, estamos forçando a inclusão das tags, supondo que as notas enviadas pelo cliente são aprovadas.
			 */
			if(strpos($xml,'nfeProc') == false)
			{
		
				preg_match("/NFe[0-9]+/", $xml, $nfeid);
				$chnfe = str_replace("NFe", '', $nfeid[0]);
				
				if(strpos($xml,'<NFe'))
					$xml = str_replace('<NFe', '<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe" versao="3.10"> <NFe', $xml);
				else
					$xml = str_replace('<nfe', '<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe" versao="3.10"> <NFe', $xml);
				
				$xml = $xml . '<protNFe><infProt><chNFe>'.$chnfe.'</chNFe><cStat>100</cStat></infProt></protNFe></nfeProc>';
				
			}
			$this->xml = simplexml_load_string($xml);
			
		}

		$this->calculaCodVer($params['dt_ini']);
	}

	/**
	 * REGISTRO 0000 - ABERTURA DO ARQUIVO DIGITAL E IDENTIFICAÇÃO DA ENTIDADE
	 *
	 * Retorna dados do registro 0000
	 *
	 * @return string fortada com os dados do registro
	 */
	public function registro0000($params = '')
	{
		// Nível hierárquico - 0
		// Ocorrencia - um por arquivo

		// >>> CAMPOS DO BLOCO
		$campos[1] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'REG');
		$campos[2] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'COD_VER');	
		$campos[3] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'COD_FIN');	
		$campos[4] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'DT_INI');	
		$campos[5] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'DT_FIN');	
		$campos[6] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'NOME');	
		$campos[7] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'CNPJ');	
		$campos[8] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'CPF');	
		$campos[9] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'UF');	
		$campos[10] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'IE');	
		$campos[11] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'COD_MUN');	
		$campos[12] = array('dado'=>'', 'obrig'=>false, 'nome'=>'IM');		
		$campos[13] = array('dado'=>'', 'obrig'=>false, 'nome'=>'SUFRAMA');	
		$campos[14] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'IND_PERFIL');
		$campos[15] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'IND_ATIV');	


		// >>> OBTENCAO DOS DADOS

		// REG
		$campos[1]['dado'] = '0000';

		// COD_VER
		$campos[2]['dado'] = $this->calculaCodVer($params['dt_ini']);

		// COD_FIN
		$campos[3]['dado'] = $params['ajuste'] ? 1 : 0;

		// DT_INI
		$dt_ini = explode('-',$params['dt_ini']);
		$dt_ini = $dt_ini[2].'-'.$dt_ini[1].'-'.$dt_ini[0];
		$dt = new DateTime($dt_ini,new DateTimeZone('America/Bahia'));
		$campos[4]['dado'] = $dt->format('dmY');

		// DT_FIN
		$dt_fin = explode('-',$params['dt_fin']);
		$dt_fin = $dt_fin[2].'-'.$dt_fin[1].'-'.$dt_fin[0];
		$dt = new DateTime($dt_fin,new DateTimeZone('America/Bahia'));
		$campos[5]['dado'] = $dt->format('dmY');
		
		if($params['nfeSituacao']=='Aprovada' || $params['nfeSituacao'] == 'Cancelada')		
		{
			$campos[6]['dado'] = strtoupper($this->xml->NFe->infNFe->emit->xNome);
			$campos[7]['dado'] = (string) $this->xml->NFe->infNFe->emit->CNPJ;
			if($campos[7]['dado']=='')
			{
				// Só pode exisitir se não houver CNPJ
				$campos[8]['dado'] = (string) $this->xml->NFe->infNFe->emit->CPF;
			}
			$campos[9]['dado'] = (string) $this->xml->NFe->infNFe->emit->enderEmit->UF;
			$campos[10]['dado'] = (string) $this->xml->NFe->infNFe->emit->IE;
			$campos[11]['dado'] = (string) $this->xml->NFe->infNFe->emit->enderEmit->cMun;
	
		}
		elseif($params['nfeSituacao']=='Importada') // Se for uma nota de entrada
		{
			$campos[6]['dado'] = strtoupper($this->xml->NFe->infNFe->dest->xNome);
			$campos[7]['dado'] = (string) $this->xml->NFe->infNFe->dest->CNPJ;
			if($campos[7]['dado']=='')
			{
				// Só pode exisitir se não houver CNPJ
				$campos[8]['dado'] = (string) $this->xml->NFe->infNFe->dest->CPF;
			}
			$campos[9]['dado'] = (string) $this->xml->NFe->infNFe->dest->enderDest->UF;
			$campos[10]['dado'] = (string) $this->xml->NFe->infNFe->dest->IE;
			$campos[11]['dado'] = (string) $this->xml->NFe->infNFe->dest->enderDest->cMun;
		}
		
		$campos[14]['dado'] = (string) $params['ind_perfil'];
		$campos[15]['dado'] = (string) $params['ind_ativ'];
		
		return $this->montaRegistro($campos);
	}
	
	/**
	 * REGISTRO 0005 - DADOS COMPLEMENTARES DA ENTIDADE
	 * 
	 * @param $params
	 * @return string $sai formatada com os dados do registro
	 */
	function registro0005($params='')
	{
		// Nível hierárquico - 2
		// Ocorrencia - vários por arquivo
		$sai = '';
		
		$campos[1] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'REG');
		$campos[2] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'FANTASIA');
		$campos[3] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'CEP');
		$campos[4] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'END');
		$campos[5] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'NUM');
		$campos[6] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'COMPL');
		$campos[7] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'BAIRRO');
		$campos[8] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'FONE');
		$campos[9] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'FAX');
		$campos[10] = array('dado'=>'', 'obrig'=>false,	'nome'=>'EMAIL');
		
		$campos[1]['dado'] = '0005';
		$campos[2]['dado'] = strtoupper($this->xml->NFe->infNFe->emit->xFant);
		$campos[3]['dado'] = strtoupper($this->xml->NFe->infNFe->emit->enderEmit->CEP);
		$campos[4]['dado'] = strtoupper($this->xml->NFe->infNFe->emit->enderEmit->xLgr);
		$campos[5]['dado'] = strtoupper($this->xml->NFe->infNFe->emit->enderEmit->nro);
		$campos[6]['dado'] = strtoupper($this->xml->NFe->infNFe->emit->enderEmit->xCpl);
		$campos[7]['dado'] = strtoupper($this->xml->NFe->infNFe->emit->enderEmit->xBairro);
		
		$sai = $this->montaRegistro($campos);
		return $sai;
		
	}
	
	/**
	 * REGISTRO 0100 - DADOS DO CONTABILISTA
	 * 
	 * @param string $params
	 * @return string
	 */
	function registro0100($params='')
	{
		// Nível hierárquico - 2
		// Ocorrência – um por arquivo
		$sai = '';
		
		$campos[1] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'REG');
		$campos[2] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'NOME');
		$campos[3] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'CPF');
		$campos[4] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'CRC');
		$campos[5] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'CNPJ');
		$campos[6] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'CEP');
		$campos[7] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'END');
		$campos[8] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'NUM');
		$campos[9] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'COMPL');
		$campos[10] = array('dado'=>'', 'obrig'=>false, 'nome'=>'BAIRRO');
		$campos[11] = array('dado'=>'', 'obrig'=>false, 'nome'=>'FONE');
		$campos[12] = array('dado'=>'', 'obrig'=>false, 'nome'=>'FAX');
		$campos[13] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'EMAIL');
		$campos[14] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'COD_MUN');
		
		$campos[1]['dado'] = '0100';
		$campos[2]['dado'] = $params['contador']['nome'];
		$campos[3]['dado'] = $params['contador']['cpf'];
		$campos[4]['dado'] = $params['contador']['crc'];
		$campos[13]['dado'] = $params['contador']['email'];
		$campos[14]['dado'] = $params['contador']['cmun'];
		
		$sai = $this->montaRegistro($campos);
		
		return $sai;
	}

	/**
	 * REGISTRO 0150 - TABELA DE CADASTRO DO PARTICIPANTE
	 *
	 * @return string formatada com os dados do registro
	 */
	function registro0150($params='')
	{
		// Nível hierárquico - 2
		// Ocorrencia - vários por arquivo

		$campos[1] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'REG');		
		$campos[2] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'COD_PART');
		$campos[3] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'NOME');	
		$campos[4] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'COD_PAIS');
		$campos[5] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'CNPJ');	
		$campos[6] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'CPF');	
		$campos[7] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'IE');		
		$campos[8] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'COD_MUN');	
		$campos[9] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'SUFRAMA');	
		$campos[10] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'END');	
		$campos[11] = array('dado'=>'', 'obrig'=>false, 'nome'=>'NUM');	
		$campos[12] = array('dado'=>'', 'obrig'=>false, 'nome'=>'COMPL');
		$campos[13] = array('dado'=>'', 'obrig'=>false, 'nome'=>'BAIRRO');

		$campos[1]['dado'] = '0150';
		if($params['nfeSituacao'] == 'Importada')
		{
			// NF-e de entrada
			$campos[2]['dado'] = $params['cod_part'];
			$campos[3]['dado'] = (string) $this->xml->NFe->infNFe->emit->xNome;
			$campos[4]['dado'] = (string) $this->xml->NFe->infNFe->emit->enderEmit->cPais;
			$campos[5]['dado'] = (string) $this->xml->NFe->infNFe->emit->CNPJ;
			if($campos[5]['dado']=='')
				$campos[6]['dado'] = (string) $this->xml->NFe->infNFe->emit->CPF;
					
			$campos[7]['dado'] = (string) $this->xml->NFe->infNFe->emit->IE;
			$campos[8]['dado'] = (string) $this->xml->NFe->infNFe->emit->enderEmit->cMun;
			$campos[9]['dado'] = '';
			$campos[10]['dado'] = (string) $this->xml->NFe->infNFe->emit->enderEmit->xLgr;
			$campos[11]['dado'] = (string) $this->xml->NFe->infNFe->emit->enderEmit->nro;
			$campos[12]['dado'] = '';
			$campos[13]['dado'] = (string) $this->xml->NFe->infNFe->emit->enderEmit->xBairro;
		}
		else
		{
			// NF-e de saída
			$campos[2]['dado'] = $params['cod_part'];
			$campos[3]['dado'] = (string) $this->xml->NFe->infNFe->dest->xNome;
			$campos[4]['dado'] = (string) $this->xml->NFe->infNFe->dest->enderDest->cPais;
			$campos[5]['dado'] = (string) $this->xml->NFe->infNFe->dest->CNPJ;
			if($campos[5]['dado']=='')
				$campos[6]['dado'] = (string) $this->xml->NFe->infNFe->dest->CPF;
			
			$campos[7]['dado'] = (string) $this->xml->NFe->infNFe->dest->IE;
			$campos[8]['dado'] = (string) $this->xml->NFe->infNFe->dest->enderDest->cMun;
			$campos[9]['dado'] = '';
			$campos[10]['dado'] = (string) $this->xml->NFe->infNFe->dest->enderDest->xLgr;
			$campos[11]['dado'] = (string) $this->xml->NFe->infNFe->dest->enderDest->nro;
			$campos[12]['dado'] = '';
			$campos[13]['dado'] = (string) $this->xml->NFe->infNFe->dest->enderDest->xBairro;
		}

			return $this->montaRegistro($campos);
	}

	/**
	 * REGISTRO 0190 - IDENTIFICAÇÃO DAS UNIDADES DE MEDIDA
	 *
	 * Esse método devolve uma array com todos os registros de unidades de medidas encontrados no XML
	 *
	 * @return array contendo em cada linha uma string formadata com os dados de um registro 0190.
	 */
	public function registro0190($params='')
	{
		// Nível hierárquivo - 2
		// Ocorrencia - vários por arquivo
		$sai = '';

		$campos[1] = array('dado'=>'', 'obrig'=>true, 'nome'=>'REG');		
		$campos[2] = array('dado'=>'', 'obrig'=>true, 'nome'=>'UNID');		
		$campos[3] = array('dado'=>'', 'obrig'=>true, 'nome'=>'DESCR');		

		$campos[1]['dado'] = '0190';
		if($params['nfeSituacao'] == 'Importada')
		{
			foreach ($this->xml->NFe->infNFe->det as $item)
			{
					
				$campos[2]['dado'] = (string) strtoupper($item->prod->uTrib);
				$campos[3]['dado'] = (string) strtoupper($params['unidades'][$campos[2]['dado']]); 
				$sai[] = $this->montaRegistro($campos);
	
			}
		}

		return $sai;
	}

	/**
	 * REGISTRO 0200: TABELA DE IDENTIFICAÇÃO DO ITEM (PRODUTO E SERVIÇOS)
	 *
	 * @return array $sai contendo em cada linha uma string formatada com dados de um registro 0200.
	 */
	public function registro0200($params='')
	{
		// Nível hierárquico - 2
		// Ocorrência - vários por arquivo
		$sai = '';

		$campos[1] = array('dado'=>'', 	'obrig'=>true,	'nome'=>'REG');		
		$campos[2] = array('dado'=>'', 	'obrig'=>true,	'nome'=>'COD_ITEM');	
		$campos[3] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'DESC_ITEM');	
		$campos[4] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'COD_BARRA');	
		$campos[5] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'COD_ANT_ITEM');	
		$campos[6] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'UNID_INV');		
		$campos[7] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'TIPO_ITEM');		
		$campos[8] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'COD_NCM');		
		$campos[9] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'EX_IPI');		
		$campos[10] = array('dado'=>'', 'obrig'=>false, 'nome'=>'COD_GEN');		
		$campos[11] = array('dado'=>'', 'obrig'=>false,	'nome'=>'COD_LST');		
		$campos[12] = array('dado'=>'', 'obrig'=>false,	'nome'=>'ALIQ_ICMS');	
		if(intval($this->cod_ver) > 10)
		{	
			$campos[13] = array('dado'=>'', 'obrig'=>false, 'nome'=>'CEST');
		}		

		if($params['nfeSituacao'] == 'Importada') // Apenas notas de entrada (emissao de terceiros) tem os itens listados no bloco C 
		{
			$campos[1]['dado'] = '0200';
			foreach ($this->xml->NFe->infNFe->det as $item)
			{
				$campos[2]['dado'] = (string) $item->prod->cProd;
				$campos[3]['dado'] = (string) strtoupper($item->prod->xProd);
				$campos[4]['dado'] = '';
				$campos[5]['dado'] = '';
				$campos[6]['dado'] = (string) $item->prod->uTrib;
				$campos[7]['dado'] = '99';											// Considerando tipo do item como 99 (Outras)		
				$campos[8]['dado'] = (string) $item->prod->NCM;
				
				$sai[] = $this->montaRegistro($campos);
			}
		}

		return $sai;
	}


	/**
	 * REGISTRO C100 - NOTA FISCAL (CÓDIGO 01), NOTA FISCAL AVULSA (CÓDIGO 1B), NOTA FISCAL DE PRODUTOR (CÓDIGO 04), NF-e (CÓDIGO 55) e NFC-e (CÓDIGO 65)
	 * Este registro deve ser gerado para cada documento fiscal código 01, 1B, 04, 55 e 65 (saída).
	 * Para cada registro C100, obrigatoriamente deve ser apresentado, pelo menos, um registro C170 e um registro C190, observadas as exceções relacionadas.
	 *
	 * @return array $sai contendo um registro C100 e registros filhos C170 e C190, observadas as exceções
	 */
	public function registroC100($params='')
	{
		// Nível hierárquico - 2
		// Ocorrência - várioas por arquivo
		$sai = '';
		$excecao = false;

		$campos[1] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'REG');	
		$campos[2] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'IND_OPER');
		$campos[3] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'IND_EMIT');
		$campos[4] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'COD_PART');
		$campos[5] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'COD_MOD');
		$campos[6] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'COD_SIT');
		$campos[7] = array('dado'=>'', 	'obrig'=>false,	'nome'=>'SER');
		$campos[8] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'NUM_DOC');
		$campos[9] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'CHV_NFE');
		$campos[10] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'DT_DOC');
		$campos[11] = array('dado'=>'', 'obrig'=>false, 'nome'=>'DT_E_S');
		$campos[12] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'VL_DOC');
		$campos[13] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'IND_PGTO');
		$campos[14] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_DESC');	
		$campos[15] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_ABAT_NT');
		$campos[16] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_MERC');
		$campos[17] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'IND_FRT');
		$campos[18] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_FRT');
		$campos[19] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_SEG');
		$campos[20] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_OUT_DA');
		$campos[21] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_BC_ICMS');
		$campos[22] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_ICMS');
		$campos[23] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_BC_ICMS_ST');
		$campos[24] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_ICMS_ST');
		$campos[25] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_IPI');
		$campos[26] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_PIS');
		$campos[27] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_COFINS');
		$campos[28] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_PIS_ST');
		$campos[29] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_COFINS_ST');


		$campos[1]['dado'] = 'C100';
		$campos[4]['dado'] = $params['cod_part']; 								// idem ao campo[2] do registro 150
		$campos[5]['dado'] = (string) $this->xml->NFe->infNFe->ide->mod;
		$campos[6]['dado'] = '00'; 												// 00: nota aprovada
		$campos[7]['dado'] = (string) $this->xml->NFe->infNFe->ide->serie;
		$campos[8]['dado'] = (string) $this->xml->NFe->infNFe->ide->nNF;
		$campos[9]['dado'] = (string) $this->xml->protNFe->infProt->chNFe;

		$nfeStatus = (int) $this->xml->protNFe->infProt->cStat;

		// Exceção 1 - NFe cancelada, NFe cancelada exteporaneo, NFe denegada
		$denegada = array(110,301,302,303);
		$situacoes = array('Cancelada','Inutilizada');
		if(in_array($nfeStatus, $denegada) || in_array($params['nfeSituacao'], $situacoes) )
		{
			// Preencher somente: REG, IND_OPER, IND_EMIT, COD_MOD, COD_SIT, SER, NUM_DOC e CHV_NF-e
			// Não tem registros filhos 
			$campos[2]['dado'] = '1';												// Movimento de saída
			$campos[3]['dado'] = '0';												// Emissão propria
			
			$campos[4]['dado'] = '';
			$campos[4]['obrig'] = false;
			$campos[7]['obrig'] = true;
			$campos[10]['obrig'] = false;
			$campos[12]['obrig'] = false;
			$campos[13]['obrig'] = false;
			$campos[17]['obrig'] = false;
							
			
			if($params['nfeSituacao'] == 'Cancelada')
				$campos[6]['dado'] = '02';
			elseif(in_array($nfeStatus, $denegada))
				$campos[6]['dado'] = '04';
			else 
				$campos[6]['dado'] = '05';
			
			if($params['nfeSituacao'] == 'Inutilizada')
			{
				$campos[9]['dado']='';
				$campos[9]['obrig'] = false; 
			}
						
			$sai[] = $this->montaRegistro($campos);
			
			return $sai;
						
		}


		// Exceção 2 - NFe de emissão propria
		if($params['nfeSituacao'] == 'Aprovada')
		{
			$campos[2]['dado'] = '1';												// Movimento de saída
			$campos[3]['dado'] = '0';												// Emissão propria
			
			$dh = (string) $this->xml->NFe->infNFe->ide->dhEmi;
			$dh = explode('-',substr($dh, 0,10));
			$campos[10]['dado'] = $dh[2].$dh[1].$dh[0]; 							// ddmmaaaa
			
			$dh = (string) $this->xml->NFe->infNFe->ide->dhSaiEnt;
			$dh = explode('-',substr($dh, 0,10));
			$campos[11]['dado'] = $dh[2].$dh[1].$dh[0]; 							// ddmmaaaa
			$campos[12]['dado'] = $this->formata_numeros($this->xml->NFe->infNFe->total->ICMSTot->vNF);
			$campos[13]['dado'] = (string) $this->xml->NFe->infNFe->ide->indPag;
			$campos[16]['dado'] = $this->formata_numeros($this->xml->NFe->infNFe->total->ICMSTot->vNF);
			$campos[17]['dado'] = (string) $this->xml->NFe->infNFe->transp->modFrete;
				
			$sai[] = $this->montaRegistro($campos);
			// Deve apenas um registro 190
							
			$c190 = $this->registroC190(1);
			foreach ($c190 as $c)
				$sai[] = $c;
			
		}

		// Exceção 3 - Notas Fiscais Complementares e Notas Fiscais Complementares escrituradas extemporaneamente
		if(intval($this->xml->NFe->infNFe->ide->finNFe) == 2)
		{
			// Preenchimento obrigatório: REG, IND_EMIT, COD_PART, COD_MOD, COD_SIT, NUM_DOC, CHV_NFE e DT_DOC
				
			// É obrigatório ter um registro C190
				
			// Demais registros, apenas se houverem informações
		}
			
		// Exceção 4/5 - Notas Fiscais emitidas por regime especial ou norma específica
		{
			// Preenchimento obrigatório: REG, IND_OPER, IND_EMIT, COD_PART, COD_MOD, COD_SIT, NUM_DOC e DT_DOC
				
			// É obrigatório ter um registro C190
		}

		// Exceção 6 - Venda de produtos que geram direito a ressarcimento com utilização de NF-e
		{
			// Indicar no registro C176 os dados para ressarcimento fututo
				
			// O registro C170 deter ter apenas os itens que permitam ressarcimento
		}

		// Exceção 7 - Escrituração de documentos emitidos por terceiros
		{
				
		}

		// Exceção 8 - NF-e com o campo UF de consumo preenchido
		{
			// Se UF de consumo for diferente UF do destinatário,  obrigatório registro C105.
		}
		// Exceção 9 - Para notas fiscais eletrônicas ao consumidor final (NFC-e), modelo 65
		if(intval($this->xml->NFe->infNfe->ide->indFinal) == 1)
		{
			// Não preecher: COD_PART, VL_BC_ICMS_ST, VL_ICMS_ST, VL_IPI, VL_PIS, VL_COFINS, VL_PIS_ST e VL_COFINS_ST
		}

		// Nota de entrada
		if($params['nfeSituacao']== 'Importada')
		{
			$campos[2]['dado'] = '0';												// Movimento de entrada
			$campos[3]['dado'] = '1';												// Emissão de terceiros
			
			$dh = (string) $this->xml->NFe->infNFe->ide->dhEmi;
			$dh = explode('-',substr($dh, 0,10));
			$campos[10]['dado'] = $dh[2].$dh[1].$dh[0]; 							// ddmmaaaa
				
			$dh = (string) $this->xml->NFe->infNFe->ide->dhSaiEnt;
			$dh = explode('-',substr($dh, 0,10));
			$campos[11]['dado'] = $dh[2].$dh[1].$dh[0]; 							// ddmmaaaa
			$campos[12]['dado'] = $this->formata_numeros($this->xml->NFe->infNFe->total->ICMSTot->vNF);
			$campos[13]['dado'] = (string) $this->xml->NFe->infNFe->ide->indPag;
			$campos[16]['dado'] = $this->formata_numeros($this->xml->NFe->infNFe->total->ICMSTot->vNF);
			$campos[17]['dado'] = (string) $this->xml->NFe->infNFe->transp->modFrete;
			
			$sai[] = $this->montaRegistro($campos);
			// Deve ter pelo menos um registro C170 e um registro 190
			$c170 = $this->registroC170(0);
			foreach ($c170 as $c)
				$sai[] = $c;
					
			$c190 = $this->registroC190(0);
			foreach ($c190 as $c)
				$sai[] = $c;
		}

		return $sai;

	}

	/**
	 * REGISTRO C170 - ITENS DO DOCUMENTO
	 *
	 * Retorna um array contendo um registro C170 para cada item da NF
	 * @return $sai array 
	 */
	public function registroC170($entradaOuSaida=0)
	{
		$sai = '';

		$campos[1] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'REG');		
		$campos[2] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'NUM_ITEM');
		$campos[3] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'COD_ITEM');
		$campos[4] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'DESCR_COMPL');
		$campos[5] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'QTD');
		$campos[6] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'UNID');
		$campos[7] = array('dado'=>'', 	'obrig'=>false, 'nome'=>'VL_ITEM');
		$campos[8] = array('dado'=>'', 	'obrig'=>false,	'nome'=>'VL_DESC');
		$campos[9] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'IND_MOV');
		$campos[10] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'CST_ICMS');
		$campos[11] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'CFOP');	
		$campos[12] = array('dado'=>'', 'obrig'=>false,	'nome'=>'COD_NAT');	
		$campos[13] = array('dado'=>'', 'obrig'=>false,	'nome'=>'VL_BC_ICMS');
		$campos[14] = array('dado'=>'', 'obrig'=>false, 'nome'=>'ALIQ_ICMS');
		$campos[15] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_ICMS');	
		$campos[16] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_BC_ICMS_ST');
		$campos[17] = array('dado'=>'', 'obrig'=>false, 'nome'=>'ALIQ_ST');
		$campos[18] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_ICMS_ST');
		$campos[19] = array('dado'=>'', 'obrig'=>false, 'nome'=>'IND_APUR');
		$campos[20] = array('dado'=>'', 'obrig'=>false, 'nome'=>'CST_IPI');
		$campos[21] = array('dado'=>'', 'obrig'=>false, 'nome'=>'COD_ENQ');
		$campos[22] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_BC_IPI');
		$campos[23] = array('dado'=>'', 'obrig'=>false, 'nome'=>'ALIQ_IPI');
		$campos[24] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_IPI');
		$campos[25] = array('dado'=>'', 'obrig'=>false, 'nome'=>'CST_PIS');
		$campos[26] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_BC_PIS');
		$campos[27] = array('dado'=>'', 'obrig'=>false, 'nome'=>'ALIQ_PIS');
		$campos[28] = array('dado'=>'', 'obrig'=>false, 'nome'=>'QUANT_BC_PIS');
		$campos[29] = array('dado'=>'', 'obrig'=>false, 'nome'=>'AQLI_PIS');
		$campos[30] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_PIS');	
		$campos[31] = array('dado'=>'', 'obrig'=>false, 'nome'=>'CST_COFINS');
		$campos[32] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_BC_COFINS');
		$campos[33] = array('dado'=>'', 'obrig'=>false, 'nome'=>'ALIQ_COFINS');
		$campos[34] = array('dado'=>'', 'obrig'=>false, 'nome'=>'QUANT_BC_COGINS');
		$campos[35] = array('dado'=>'', 'obrig'=>false, 'nome'=>'ALIQ_COFINS');
		$campos[36] = array('dado'=>'', 'obrig'=>false, 'nome'=>'VL_COFINS');
		$campos[37] = array('dado'=>'', 'obrig'=>false, 'nome'=>'COD_CTA');

		$campos[1]['dado'] = 'C170';
		foreach ($this->xml->NFe->infNFe->det as $item)
		{
			$campos[2]['dado'] = (string) $item[0]['nItem'];
			$campos[3]['dado'] = (string) $item->prod->cProd;							// idem ao campo 02 do registro 0200
			$campos[5]['dado'] = $this->formata_numeros($item->prod->qTrib);
			$campos[6]['dado'] = (string) $item->prod->uTrib;  							// idem ao campo 02 do registro 0190
			$campos[7]['dado'] = $this->formata_numeros($item->prod->vUnTrib);
			$campos[9]['dado'] = '0';													// 0 - conderando (para armazéns) que sempre existe movimentação fisica
			
			foreach ($item->imposto->ICMS->children() as $child)
			{
				// usando um loop pois não encontrei outra forma genérica de pegar a informação 
				$campos[10]['dado'] = (string) str_pad($child->CST,3,"0",STR_PAD_LEFT);
			}
			
			if($entradaOuSaida == 0) // tratando uma nota de entrada
				$campos[11]['dado'] =  $this->converteCfopSaidaParaEntrada( (string) $item->prod->CFOP);
			else
				$campos[11]['dado'] = (string) $item->prod->CFOP;

			$sai[] = $this->montaRegistro($campos);
		}

		return $sai;
	}

	/**
	 * REGISTRO C190: REGISTRO ANALÍTICO DO DOCUMENTO (CÓDIGO 01, 1B, 04, 55 e 65).
	 * Este registro tem por objetivo representar a escrituração dos documentos fiscais totalizados por CST, CFOP e Alíquota de ICMS
	 * 
	 * Este método deve gerar uma consolidação dos registros agrupando os dados por CST_ICMS, CFOP e ALIQ_ICMS
	 *
	 * @return array $sai com um ou mais registros C190
	 */
	public function registroC190($entradaOuSaida)
	{
		$sai = '';

		$campos[1] = array('dado'=>'',	'obrig'=>true,	'nome'=>'REG');		
		$campos[2] = array('dado'=>'',	'obrig'=>true, 	'nome'=>'CST_ICMS');		
		$campos[3] = array('dado'=>'',	'obrig'=>true, 	'nome'=>'CFOP');		
		$campos[4] = array('dado'=>'',	'obrig'=>false, 'nome'=>'ALIQ_ICMS');		
		$campos[5] = array('dado'=>'',	'obrig'=>true, 	'nome'=>'VL_OPR');		
		$campos[6] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'VL_BC_ICMS');	
		$campos[7] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'VL_ICMS');		
		$campos[8] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'VL_BC_ICMS_ST');	
		$campos[9] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'VL_ICMS_ST');	
		$campos[10] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'VL_RED_BC');	
		$campos[11] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'VL_IPI');		
		$campos[12] = array('dado'=>'', 'obrig'=>false, 'nome'=>'COD_OBS');	

		$nfe = $this->xml->NFe->infNFe;

		$dados = array();
		foreach ($this->xml->NFe->infNFe->det as $item)
		{
			foreach ($item->imposto->ICMS->children() as $child)
			{
				// usando um loop pois não encontrei uma forma genérica de pegar a informação
				$cst = (string) $child->CST;
				$alq = (string) $child->pICMS;
			}
			
			if($entradaOuSaida == 0) // tratando uma nota de entrada
				$cfop =  $this->converteCfopSaidaParaEntrada( (string) $item->prod->CFOP);
			else
				$cfop = (string) $item->prod->CFOP;
			$icms = 'ICMS' . $cst;
			
			if(!isset($dados[$cst.$cfop.$alq]))
			{
				$dados[$cst.$cfop.$alq] = array(
												'cst'=>str_pad($cst,3,"0",STR_PAD_LEFT),
												'cfop'=>$cfop,
												'vl_opr'=>0,
												'vl_bc_icms'=>0,
												'vl_icms'=>0,
												'vl_bc_icms_st'=>0,
												'vl_icms_st'=>0,
												'vl_red_bc'=>0,
												'vl_ipi'=>0
												);
			}
			$vl = floatval($item->prod->vProd);
			$dados[$cst.$cfop.$alq]['vl_opr'] += $vl;
				
			$vl = floatval($item->imposto->ICMS->$icms->vBC);
			$dados[$cst.$cfop.$alq]['vl_bc_icms'] += $vl;
				
			$vl = floatval($item->imposto->ICMS->$icms->vICMS);
			$dados[$cst.$cfop.$alq]['vl_icms'] += $vl;
				
			$vl = floatval($item->imposto->ICMS->$icms->vBCST);
			$dados[$cst.$cfop.$alq]['vl_bc_icms_st'] += $vl;
				
			$vl = floatval($item->imposto->ICMS->$icms->vICMSST);
			$dados[$cst.$cfop.$alq]['vl_icms_st'] += $vl;
				
			$vl = floatval($item->imposto->ICMS->$icms->vBC);		// usando vBC temporariamente
			$dados[$cst.$cfop.$alq]['vl_red_bc'] += $vl;
				
			$vl = 0;												// condiderando 0 para armazéns
			$dados[$cst.$cfop.$alq]['vl_ipi'] += $vl;
				
		}

		$campos[1]['dado'] = 'C190';
		foreach ($dados as $reg)
		{

			$campos[2]['dado'] = $reg['cst'];
			$campos[3]['dado'] = $reg['cfop'];

			$campos[5]['dado'] = $this->formata_numeros($reg['vl_opr']);
			$campos[6]['dado'] = $this->formata_numeros($reg['vl_bc_icms']);
			$campos[7]['dado'] = $this->formata_numeros($reg['vl_icms']);
			$campos[8]['dado'] = $this->formata_numeros($reg['vl_bc_icms_st']);
			$campos[9]['dado'] = $this->formata_numeros($reg['vl_icms_st']);
			$campos[10]['dado'] = $this->formata_numeros($reg['vl_red_bc']);
			$campos[11]['dado'] = $this->formata_numeros($reg['vl_ipi']);
				
			$sai[]= $this->montaRegistro($campos);
		}

		return $sai;
	}
	
	/**
	 * REGISTRO E100 - PERÍODO DA APURAÇÃO DO ICMS.
	 * Considera o mesmo periodo informado no registro 0000.
	 * 
	 * @param string $params
	 * @return string
	 */
	function registroE100($params='')
	{
		//	Nível hierárquico – 2
		//	Ocorrência – 1:N
		
		$sai = '';
		$campos[1] = array('dado'=>'',	'obrig'=>true,	'nome'=>'REG');
		$campos[2] = array('dado'=>'',	'obrig'=>true, 	'nome'=>'DT_INI');
		$campos[3] = array('dado'=>'',	'obrig'=>true, 	'nome'=>'DT_FIN');
		
		$campos[1]['dado'] = 'E100';

		$dt_ini = explode('-',$params['dt_ini']);
		$dt_ini = $dt_ini[2].'-'.$dt_ini[1].'-'.$dt_ini[0];
		$dt = new DateTime($dt_ini,new DateTimeZone('America/Bahia'));
		$campos[2]['dado'] = $dt->format('dmY');
		
		$dt_fin = explode('-',$params['dt_fin']);
		$dt_fin = $dt_fin[2].'-'.$dt_fin[1].'-'.$dt_fin[0];
		$dt = new DateTime($dt_fin,new DateTimeZone('America/Bahia'));
		$campos[3]['dado'] = $dt->format('dmY');
		
		$sai = $this->montaRegistro($campos);
		
		return $sai;
	}
	
	/**
	 * REGISTRO 1010 - OBRIGATORIEDADE DE REGISTROS DO BLOCO 1 
	 * 
	 * @param string $params
	 * @return string formatada com os dados do registro
	 */
	function registro1010($params='')
	{
		// Nível hierárquico - 2
		// Ocorrência – 1:1
		
		$sai = '';
		
		$campos[1] = array('dado'=>'',	'obrig'=>true,	'nome'=>'REG');
		$campos[2] = array('dado'=>'',	'obrig'=>true, 	'nome'=>'IND_EXP');
		$campos[3] = array('dado'=>'',	'obrig'=>true, 	'nome'=>'IND_CCRF');
		$campos[4] = array('dado'=>'',	'obrig'=>true, 	'nome'=>'IND_COMB');
		$campos[5] = array('dado'=>'',	'obrig'=>true, 	'nome'=>'IND_USINA');
		$campos[6] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'IND_VA');
		$campos[7] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'IND_EE');
		$campos[8] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'IND_CART');
		$campos[9] = array('dado'=>'', 	'obrig'=>true, 	'nome'=>'IND_FORM');
		$campos[10] = array('dado'=>'', 'obrig'=>true, 	'nome'=>'IND_AER');
		
		$campos[1]['dado'] = '1010';
		$campos[2]['dado'] = 'N';
		$campos[3]['dado'] = '';
		$campos[4]['dado'] = 'N';
		$campos[5]['dado'] = 'N';
		$campos[6]['dado'] = '';
		$campos[7]['dado'] = 'N';
		$campos[8]['dado'] = 'N';
		$campos[9]['dado'] = 'N';
		$campos[10]['dado'] = 'N';
		
		$sai = $this->montaRegistro($campos);
		
		return $sai;
	}
	

	/**
	 * BLOCO 9 - CONTROLE E ENCERRAMENTO DO ARQUIVO DIGITAL
	 *
	 * Esse método monta e devolve um array com todos os dados dos blocos 9
	 *
	 * @param array $blocosQtd associativo contendo quantidade de ocorrencias dos blocos
	 * @param int $qtdArq quantidade de linhas do arquivo
	 * @return array $campos com todas as linhas do bloco 9
	 */
	public function bloco9($blocosQtd,$qtdArq)
	{
		$campos = array();
		$ttlBlc = 0; // Total de linha do bloco
		$ttlArq = intval($qtdArq); // Total de linhas do arquivo
		$ttl9900 = 0; // Total de registros de fechamento

		// >>>> Abertura do bloco
		$abertura = '|9001|0|';
		$ttlBlc++;
		$campos[] = array('dado'=>$abertura, 'obrig'=>true);

		// >>>> Registros do bloco
		$blocosQtd['9001']=1;
		$blocosQtd['9990']=1;
		$blocosQtd['9999']=1;

		foreach ($blocosQtd as $bl => $qtd)
		{
			if($bl <> '9000')
			{
				$reg = "|9900|$bl|$qtd|";
				$campos[] = array('dado'=>$reg, 'obrig'=>true);
				$ttlBlc++;
				$ttl9900++;

			}
		}

		// >>>> Registros de encerramento: contabiliza os registros 9900
		$encerramento = "|9990|$ttl9900|";
		$ttlBlc++;
		$campos[] = array('dado'=>$encerramento, 'obrig'=>true);


		// >>>> Encerramento do bloco
		$ttlBlc++; //  contabiliza o registro de fechamento do bloco (9990)
		$ttlBlc++; //  contabiliza o registro de fechamento do arquivo (9999)
		$encerramento = "|9990|$ttlBlc|";
		$campos[] = array('dado'=>$encerramento, 'obrig'=>true);

		// >>>> Encerramento do aquivo digital (SPED)
		$ttl = $ttlArq + $ttlBlc;
		$encerramento = "|9999|$ttl|";
		$campos[] = array('dado'=>$encerramento, 'obrig'=>true);

		return $campos;
	}

	/**
	 * Monta e retorna os dados os dados de um registro
	 *
	 * @param $campos array com os dados do registro
	 * @return string $sai dados formatados
	 */
	private function montaRegistro($campos)
	{
		$sai = '' ;

		$reg = $campos[1]['dado'];
		foreach ($campos as $k => $campo)
		{
			$sai[] = $campo['dado'];
			if($campo['dado']==='' && $campo['obrig']==true)
			{
				$this->erros[] = "Registro {$reg}. Campo $k ({$campo['nome']}): obrigatório." ;
			}
		}

		$sai = '|'.  implode('|', $sai) . '|';

		return $sai;
	}

	/**
	 * Obtem um ou mais registros de uma determinada categoria. A quantidade de registro depende
	 * do método chamado.
	 *
	 * @param string $reg código do do registro
	 * @param string $params parametros adicionais
	 * @return mixed $sai string (apenas um registro), array caso contrário
	 */
	public function obtemRegistros($reg, $params='')
	{
		$sai = '';

		$bl = 'registro'.$reg;

		$sai = $this->$bl($params);

		return $sai;
	}

	/**
	 * Cálcula o código da versão do arquivo do SPED a partir do
	 * ATO COTEPE/ICMS 7, DE 13 DE MAIO DE 2016
	 * 
	 * @param $dt_ini data da escrituração. Se não for informada, o cálculo será baesado no ano corrente.
	 * @return string com o código da versão do layout  
	 */
	 private function calculaCodVer($dt_ini = '')
	 {
	 	
	 	if($dt_ini <> '')
	 	{
		 	$dt_ini = explode('-',$dt_ini);
		 	$dt_ini = $dt_ini[2].'-'.$dt_ini[1].'-'.$dt_ini[0];
		 	$dt = new DateTime($dt_ini,new DateTimeZone('America/Bahia'));
		 	$y = $dt->format('Y');
	 	}
	 	else
	 	{
	 		$y = date('Y');
	 	}
	 	
			 	
	 	if($y==2016)
	 		$this->cod_ver = '010';
	 	
	 	if($y >= 2017)
	 		$this->cod_ver = '011';
	 	
	 	return $this->cod_ver;
	 }

	 /**
	  * Gera o registro de abertura de um bloco.
	  * Por padrão, não considera a existencia de registros filhos (Bloco sem dados informados)
	  *
	  * @param string $bloco código do bloco
	  * @param int $ind_mov indicador de existencia de dados: 0 (sim) | 1 (não) 
	  * @return string formatada com os dados de abertura do bloco
	  */
	 public function regAbreBloco($bloco, $ind_mov=1)
	 {
	 	$sai = '';
	 	 
	 	$bl = substr($bloco, 0,1);
	 	$bl = strtoupper($bl);
	 	 
	 	$sai = '|' . $bl . '001|' . intval($ind_mov) . '|';
	 	return $sai;
	 }

	 /**
	  * Gera o registro de fechamento de um bloco
	  *
	  * @param string $bloco código do bloco
	  * @param int $qtd quantidade de linhas do bloco
	  * @return string formatada com os dados de fechamento do bloco
	  */
	 public function regFechaBloco($bloco, $qtd)
	 {
	 	$sai = '';

	 	$bl = substr($bloco, 0,1);
	 	$bl = strtoupper($bl);

	 	$sai = '|' . $bl . '990|' . $qtd . '|';
	 	return $sai;
	 	 
	 }

	 /**
	  * Retorna um array com os erros ocorruidos na geração do arquivo SPED
	  * 
	  * @return array
	  */
	 public function obtemErros()
	 {
	 	return $this->erros;
	 }
	 
	/**
	 * Formata numeros para o formato do arquivo SPED
	 * @param string $numero
	 */
	private function formata_numeros($numero)
	{
		return number_format(floatval($numero),2,',','');
	}
	
	/**
	 * Converte um CFOPs de saída no seu equivalente de entrada
	 * 
	 * @param string $cfop
	 * @return string
	 */
	private function converteCfopSaidaParaEntrada($cfop)
	{
		$sai = '';
		
		$base = substr($cfop, 0,1);
		$opr = substr($cfop, 1,4);
		
		switch ($base) 
		{
			case '5':
				$sai = '1'.$opr;
				break;
			case '6':
				$sai = '2'.$opr;
				break;
			case '7':
				$sai = '3'.$opr;
				break;
			default:
				$sai = $cfop;
			break;
		}
		
		return $sai;
	}

}