<?php
if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
    $ambiente = '/teste';
}
require_once $_SERVER["DOCUMENT_ROOT"] . $ambiente . "/emitenota/giusoft/src/Lib/utils.php";

date_default_timezone_set('America/Bahia');

class Integracao
{
	public $parametro;
	public $setup;
	public $exibirDebug = 0;
	public $debug = array();
	public $erros = array();
	public $gParam;
	protected $conexaoBanco;
	public $nomeArquivoLog;
	public $cadastrarSkuAoCriarOs = 1;


	public function __construct($parametro)
	{
		$this->parametro = $parametro;
		if ($this->parametro['caminhoSetup']) {
			$this->carregarSetup($this->parametro['caminhoSetup']);
		}
		$this->nomeArquivoLog = "integracao_".$this->setup["global.logfile"];

		$this->conectarBanco($this->parametro['conexaoBanco'] ?: $this->setup);
		$this->gParam = $this->gParam();
		$sql = "SELECT filial.id
					FROM pessoas_filial
					JOIN filial ON filial.id = pessoas_filial.id_filial
					WHERE pessoas_filial.cancelado = 0
					LIMIT 1";
		$this->parametro['idFilial'] = $this->parametro['idFilial'] ?: $this->executarQuery($sql)[0]['id'];
	}

	public function carregarSetup()
	{
		include $this->parametro['caminhoSetup'];

		$stp = trim(str_replace("\n","", $gSETUP));
		$mtz = explode("}",$stp);
		$new = "";
		foreach ($mtz as $el) {
			if (strpos($el,"{") !== false) {
				$class = trim(substr($el,0,strpos($el,"{")));
				$parm = substr($el,strpos($el,"{")+1);
				$parm = trim(substr($parm,0,strlen($parm)-1));
				$parms = $this->cssDecode($parm);
				foreach ($parms as $key=>$value)
					$classes[$class . "." . $key] = $value;
			}
		}
		$this->setup = $classes;
	}

	public function conectarBanco($credenciais)
	{
		$conexao = 'mysql:host=' . $credenciais['database.url']
			. '; port=' . $credenciais['database.port']
			. '; charset=' . ($credenciais['database.charset'] ?: 'latin1')
			. ';  dbname='  . $credenciais['database.name'];
		$this->conexaoBanco = new PDO($conexao, $credenciais['database.user'], $credenciais['database.password']);
		$this->conexaoBanco->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if ($this->parametro['transacao']) {
			$this->conexaoBanco->beginTransaction();
		}
	}

	public function executarQuery($query, $retornarId = 0)
	{
		$this->debug($query);

		$this->gLog($query, 0, $this->nomeArquivoLog);
		$stmt = $this->conexaoBanco->prepare($query);

		try {
			$stmt->execute();
		} catch (PDOException $e) {
			$this->gLog("SQL(Error)(" . $this->setup["database.name"] . "):\t" . $query, 1, $this->nomeArquivoLog);
			$this->gLog("SQL(Error)(" . $this->setup["database.name"] . "):\t" . $e->getMessage(), 1, $this->nomeArquivoLog);
		}

		if (
			$stmt->rowCount()
			&& stripos($query, "SELECT") !== false
			&& strtoupper(substr(trim($query), 0, 6)) != "INSERT"
			&& strtoupper(substr(trim($query), 0, 6)) != "UPDATE"
			&& strtoupper(substr(trim($query), 0, 6)) != "DELETE"
			&& (
				stripos($query, "INSERT") === false
				|| stripos($query, "UPDATE") === false
				|| stripos($query, "DELETE") === false
			)
		) {
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		if ($retornarId) {
			return $this->conexaoBanco->lastInsertId();
		}
	}

	public function consultarId($tabela, $where, $orderBy)
	{
		if (is_array($where)) {
			$where = implode(' AND ', $where);
		}

		if ($orderBy) {
			$orderBy = " ORDER BY " . $orderBy;
		}

		$sql = "SELECT id FROM {$tabela} WHERE {$where} {$orderBy} LIMIT 1";
	 	return (int) $this->executarQuery($sql)[0]['id'];
	}


	public function cadastrarItem($dados, $retornarDetalhes = 0)
	{
		$mtz['apto']   = $dados['apto'] ?: 0;
		$mtz['ativo']  = $dados['ativo'] ?: 0;
		$mtz['codigo'] = $dados['codigo'];
		$mtz['nome'] = $dados['nome'];
		$mtz['ncm'] = $dados['ncm'];
		$mtz['codigo_barras'] = $dados['codigo_barras'];
		$mtz['data_cadastro'] = date('Y-m-d H:i:s');
		$mtz['descricao'] = $dados['descricao'] ?: $dados['codigo'];
		$mtz['id_pessoas_criou'] = $this->parametro['idPessoasCriou'];
		$mtz['id_pessoas_proprietario'] = $dados['id_pessoas_proprietario'] ?: $this->parametro['idPessoasProprietario'];
		$mtz['observacoes'] = 'Criado através de integração';

		$idItens = $this->insertTable('itens', $mtz);
		if (!$idItens) {
			$this->erros[] = 'Erro de banco de dados:  Não foi possível cadastrar os itens';
			$this->debug(implode('; ','[ERROR]' . $this->erros), '', 1);
			return ;
		}
		$this->debug('#insere na tabela itens: ' . $idItens);

		$mtz['id_itens'] = $idItens;
		$mtz['unidade']  = $dados['unidade'];
		$mtz['descricao_unidade'] = $dados['descricaoUnidade'];
		$mtz['palete_lastro'] = $dados['palete_lastro'];
		$mtz['palete_altura'] = $dados['palete_altura'];
		$mtz['quantidade'] = $dados['quantidade'];
		$mtz['largura'] = $dados['largura'];
		$mtz['altura'] = $dados['altura'];
		$mtz['comprimento'] = $dados['comprimento'];
		$idItensSkus = $this->cadastrarSku($mtz);

		if ($retornarDetalhes) {
			return array(
				'id' => $idItensSkus,
                'codigoBarras' => $dados['codigo_barras'],
                'siglaUnidade' => $dados['unidade']
            );
		}
		return $idItensSkus;
	}


	public function cadastrarSku($dados)
	{
		$idUnidades = 1;
		if (trim($dados['unidade'])) {
			$sql = "SELECT id FROM unidades WHERE sigla = '" . gCleanField($dados['unidade']) . "' LIMIT 1";
			$idUnidades = ($this->executarQuery($sql)[0]['id']);
			if (!$idUnidades) {
				$mtz = array();
				$mtz['sigla'] = $dados['unidade'];
				$mtz['descricao'] = $dados['descricao_unidade'];
				$idUnidades = $this->insertTable('unidades', $mtz);
			}
		}

		$mtz = array();
		$mtz['ativo'] = $dados['ativo'];
		$mtz['id_itens'] = $dados['id_itens'];
		$mtz['id_unidades'] = $idUnidades;
		$mtz['id_pessoas_criou'] = $this->parametro['idPessoasCriou'];
		$mtz['data_cadastro'] = date('Y-m-d H:i:s');
		$mtz['codigo'] = $dados['codigo'];
		$mtz['codigo_barras'] = $dados['codigo_barras'];
		$mtz['palete_lastro'] = $dados['palete_lastro'];
		$mtz['palete_altura'] = $dados['palete_altura'];
		$mtz['quantidade'] = $dados['quantidade'];
		$mtz['largura'] = $dados['largura'];
		$mtz['altura'] = $dados['altura'];
		$mtz['comprimento'] = $dados['comprimento'];
		$mtz['nome'] = $dados['nome'];

		$idItensSkus = $this->insertTable('itens_skus', $mtz);
		if (!$idItensSkus) {
			$this->erros[] = 'Erro de banco de dados: Não foi possível cadastrar os SKUs';
			$this->debug(implode('; ','[ERROR]' . $this->erros), '', 1);
			return ;
		}

		$this->debug('#Insere na tabela itens_skus: ' . $idItensSkus);
		return $idItensSkus;
	}


	public function cadastrarEmpresa($pessoa, $juridico, $endereco, $retornarDetalhes = 0)
	{
		$pessoa['data_cadastro'] = $pessoa['data_cadastro'] ?: date('Y-m-d H:i:s');
		$pessoa['id_pessoas_criou'] = $pessoa['id_pessoas_criou'] ?: 1;
		$pessoa['cliente'] = $pessoa['cliente'] ?: 0;
		$pessoa['fornecedor'] = $pessoa['fornecedor'] ?: 0;
		$idPessoas = $this->insertTable('pessoas', $pessoa);

		if ($juridico) {
			$juridico['tipo_separacao'] = $pessoa['tipo_separacao'] ?: 2;
			$juridico['id_pessoas'] = $juridico['id_pessoas'] ?: $idPessoas;
			$this->insertTable('pessoas_juridicas', $juridico);
		}

		if ($endereco) {
			$endereco['id_pessoas'] = $endereco['id_pessoas'] ?: $idPessoas;
			$this->insertTable('pessoas_enderecos', $endereco);
		}

		$pessoasFilial = array();
		$pessoasFilial['id_filial'] = $this->parametro['idFilial'];
		$pessoasFilial['id_pessoas'] = $idPessoas;
		$pessoasFilial['id_pessoas_criou'] = 1;
		$pessoasFilial['data_criou'] = date('Y-m-d H:i:s');
		$this->insertTable('pessoas_filial', $pessoasFilial);

		if ($retornarDetalhes) {
			return array(
				'id' => $idPessoas,
				'codigoSistemaExterno' => $juridico['codigo_sistema_externo']
			);
		}
		return $idPessoas;
	}


	public function verificarSeSkuExiste($codigo, $codigoBarras)
	{
		$where = array();
		$where[] = "itens_skus.codigo = '{$codigo}'";
		if ($codigoBarras) {
			$where[] = "itens_skus.codigo_barras = '{$codigoBarras}'";
		}
		$where = implode(' AND ', $where);
		return $this->consultarId('itens_skus', $where, "ativo DESC");
	}
	/* METODOS DE UTILIDADE GERAL*/

	public function cssDecode($css)
	{
		$sai = array();
		$css = html_entity_decode($css, ENT_NOQUOTES, 'UTF-8');
		if (strpos($css, "[") !== false) {
			$b = strpos($css, "[") + 1;
			for ($a = $b; $a < strlen($css); $a++) {
				if ($css[$a] == "{") {
					$css[$a] = "^";
				}
				if ($css[$a] == "}") {
					$css[$a] = "~";
				}
				if ($css[$a] == "]") {
					break;
				}
			}
		}

		$items = "";
		if (strpos($css, "items:") !== false) {
			$i = explode("items:", $css);
			if (substr(trim($i[1]), 0, 1) == "'") {
				$items = substr(trim($i[1]), 1);
				$ini = strpos($items, "'");
				$fim = strrpos($items, "'");
				$css = $i[0] . substr($items, $ini + 2) . "}";
				$items = substr($items, 0, $ini);
				$items = str_replace("\"", "'", $items);
			} elseif (strtolower(substr(trim($i[1]), 0, 6)) == "select") {
				$items = str_replace("}", "", trim($i[1]));
				if (strpos($items, ";") !== false) {
					$items = substr($items, 0, strpos($items, ";"));
				}
			}
		}
		$css = str_replace("{", "", $css);
		$css = str_replace("}", "", $css);
		$array = explode(";", $css);

		foreach ($array as $value) {
			$value = trim($value);
			$key = substr($value, 0, strpos($value, ":"));
			$value = trim(str_replace("'", "", substr($value, strpos($value, ":") + 1)));
			$new = array($key, $value);
			if (trim($new[0]) <> "") {
				$val = '';
				$val = trim($new[1]);
				$val = str_replace("`", "'", $val);
				$val = str_replace("^", "{", $val);
				$val = str_replace("~", "}", $val);
				if (substr($val, 0, 1) == "[") {
					if (strpos($val, "|") !== false) {
						$val = explode("|", substr($val, 1, strlen($val - 3)));
					}
				}
				$sai[trim($new[0])] = $val;
			}
		}
		if ($items <> "") {
			$sai['items'] = trim($items);
		}
		foreach ($sai as $key => $item) {
			if (substr($item, 0, 10) == "--(encode)") {
				$sai[$key] = base64_decode(substr($item, 10));
			}
		}

		return $sai;
	}


	public function insertTable($nomeTabela, $matrizDados, $retornarId = 1)
    {
        $sql =
            "INSERT INTO {$nomeTabela}
            (" . implode(",", array_keys($matrizDados)) . ")
            VALUES ('" . implode("','", array_map('gCleanField', array_values($matrizDados))) . "')";

		return $this->executarQuery($sql, $retornarId);
    }


    public function excluirIndicesNumericos($array) {
        foreach (array_keys($array) as $chave) {
            if (is_numeric($chave)) {
                unset($array[$chave]);
            }
        }
        
        return $array;
    }


    public function numeroOS()
	{
        $ano = date('Y');
        $idFilial = $this->parametro['idFilial'];
		$sql = "SELECT numero FROM contadores WHERE id_filial = '{$idFilial}' AND ano = '{$ano}' ORDER BY numero DESC LIMIT 1";
		$rs = $this->executarQuery($sql);
		$numero = (int) $rs[0]['numero'];
		$numero++;
		if ($numero == 1) {
			$sql = "INSERT into contadores (id_filial,ano,numero) VALUES ({$idFilial}, {$ano}, {$numero})";
			$this->executarQuery($sql);
		} else {
			// Ano já existe, incrementa numero
			$sql = "UPDATE contadores SET numero = {$numero} WHERE id_filial = {$idFilial} and ano = {$ano}";
			$this->executarQuery($sql);
        }

		if ($ano < 100) {
			$ano = 2000 + $ano;
		}
		$sql = "SELECT * FROM filial WHERE id = {$idFilial}";
        $rs = $this->executarQuery($sql);

        $os = strtoupper($rs[0]['prefixo']) . $rs[0]['codigo_barras'] . str_pad($numero, 10, "0", STR_PAD_LEFT) . "/" . $ano;
        return $os;
    }


    public function formatarDataHora($dataHora, $formatoOrigem, $formatoDestino = 'Y-m-d H:i:s')
    {
    	if (!$dataHora || !$formatoOrigem) {
    		return $dataHora;
    	}

    	$data = DateTime::createFromFormat($formatoOrigem, $dataHora);
		return $data->format($formatoDestino);
    }

	public function decodificarBase64($texto)
	{
		return ($this->isBase64($texto)) ? base64_decode($texto) : $texto;
	}

	public function isBase64($texto)
	{
		return ($texto === base64_encode(base64_decode($texto)));
	}

	public function gParam()
	{
		$sql = 'SELECT * FROM parametros';
		$rs = $this->executarQuery($sql);

		$gParam = array();
		foreach ($rs as $parametro) {
			$gParam[$parametro['chave']] = $parametro;
		}

		return $gParam;
	}


	public function debug($mensagem, $dados = "", $erro = 0)
	{
		if (!$this->exibirDebug && $this->parametro['idPessoasCriou'] <> 1) {
			return '';
		}
		$debug .= '[' . date('d-m-Y H:i:s') . ']  FILE:'. debug_backtrace()[1]['file'] . '  LINE:' . debug_backtrace()[1]['line'];
		if ($mensagem) {
			if ($erro) {
				$mensagem = "[ERROR] " . $mensagem;
			}
			$debug .= '  MSG: ' . $mensagem;
		}

		if ($dados) {
			$debug .= '  DADOS: ';
			if (is_array($dados)) {
				$debug .= json_encode($dados);
			} else {
				$debug .= $dados;
			}
		}
		$this->debug[] = $debug;
	}

	function gLog($txt, $erro = 0, $arq = "")
	{
		global $debug,$DB, $_SESSION;

		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
			define('gAPP_FILE', "/gApp_");
			setlocale(LC_ALL, 'POSIX');
			define('gLogPath', "/");
		} else {
			define('gAPP_FILE', "/tmp/gApp_");
			setlocale(LC_ALL, 'english');
			if (file_exists("/var/www/log"))
			{
				define('gLogPath', "/var/www/log/");
			} else {
				define('gLogPath', "/var/log/");
			}
		
		}

		if(strpos($_SERVER["HTTP_HOST"],"localhost")!==false || strpos($_SERVER["HTTP_HOST"],"127.0.0.1")!==false || file_exists("/tmp/gLogEnabled") || true)
		{
			$logfile = gLogPath . $this->gVar("global.logfile");
			$sqllogfile = gLogPath . $this->gVar("global.sqllogfile");
			$loglevel = $this->gVar("global.debug");

			if ($arq <> "") {
				$logfile = gLogPath . $arq;
			}

			if ($logfile <> "") {
				$txt = str_replace(["\n", "\r"], '', $txt);

				$colors = [
					'default' => "\033[0;00;33m",
					'info'    => "\033[0;40;37m",
					'error'   => "\033[1;00;31m",
					'line'    => "\033[0;00;36m",
					'reset'   => "\033[0;00;37m"
				];

				$cpre = $erro ? $colors['error'] : $colors['info'];
				$lpre = $colors['line'];
				$cpos = $colors['reset'];

				$d = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				unset($d[0]);

				unset($d['function']);

				array_splice($d, -2);
				
				$deb = array_map(function($trace) {
					return basename($trace['file']) . ":" . $trace['function'] . ":" . $trace['line'];
				}, array_reverse($d));

				$deb = implode(" => ", $deb);

				$faz = (stripos($txt, "select") !== false) || 
					(stripos($txt, "update") !== false) || 
					(stripos($txt, "insert") !== false) || 
					(stripos($txt, "delete") !== false);

					$ipAddress = (($_SERVER['REMOTE_ADDR'] == '::1') || ($_SERVER['REMOTE_ADDR'] == '127.0.0.1')) ? gethostbyname(gethostname()) : $_SERVER['REMOTE_ADDR'];

					$logHeader = $colors['default'] . date("y-m-d H:i:s") . " " . $ipAddress;

					if ($faz) {
						$logMessage = "$logHeader\t" . $lpre . $deb . $colors['reset'] . "\t" . $cpre . $txt . $cpos;
					} else {
						$logMessage = $logHeader . "\tLOG:\t" . $lpre . $deb . $cpos . "\t" . $cpre . $txt . $cpos;
					}
					$path = fopen($logfile, 'a');
					if ($path) {
						$logMessage = preg_replace('/\s+/', ' ', $logMessage);
						fputs($path, $logMessage . PHP_EOL);
						fclose($logfile);
					}
			}
		}
	}


	function gVar($par, $new="")
	{
		global $gLang, $_gVar;
		$sai = "";
		if ($new<>"")
			$_gVar[$par]=$new;
		$sai=$_gVar[$par];
		return($sai);
	}

	public function retornouDados($rs)
	{
		if (!is_object($rs)) {
			 return false;
		}

		if ($rs->rowCount() > 0) {
			 return true;
		}

		return false;	
}


	public function updateTable($nomeTabela, $matrizDados, $id)
    {
        foreach ($matrizDados as $atributo => $valor) {
            $atributos[] = " {$atributo} = '" . $valor . "' ";
        }

        $sql = "UPDATE " . TABLE_API . " SET"
            . implode(', ', $atributos)
            . " WHERE id = " . $id;
        $this->executarQuery($sql);
    }


	public function salvarRequisicao($enviado, $dadosEnviados)
	{

		if (!$dadosEnviados['idGatilhoRequisicao']) {
			$mtz = array();
			$mtz['enviado'] = base64_encode($enviado);
			$mtz['pendente'] = $dadosEnviados['pendente'];
			$mtz['id_pessoas_criou'] = $dadosEnviados['idPessoasCriou'];
			$mtz['id_gatilhos'] = $dadosEnviados['idGatilhos'];
			$idGatilhoRequisicao = $this->insertTable('gatilhos_requisicoes', $mtz, 1);
		}

		if (!$dadosEnviados['idGatilhoRequisicaoDetalhes']) {
			$mtz = array();
			$mtz['id_gatilhos_requisicoes'] = $idGatilhoRequisicao ?: $dadosEnviados['idGatilhoRequisicao'];
			$mtz['data_hora'] = date("Y-m-d H:i:s");
			$mtz['recebido'] = '';
			$mtz['sucesso'] = 0;
			$mtz['numero_tentativa'] = $dadosEnviados['numeroTentativa'];
			$idGatilhoRequisicaoDetalhes = $this->insertTable('gatilhos_requisicoes_detalhes', $mtz, 1);

			$dadosEnviados['idGatilhoRequisicao'] = $idGatilhoRequisicao ?: $dadosEnviados['idGatilhoRequisicao'];
			$dadosEnviados['idGatilhoRequisicaoDetalhes'] = $idGatilhoRequisicaoDetalhes;
			return $dadosEnviados;
		}

		if ($dadosEnviados['requisitou']) {
			$setNumeroTentativa = " numero_tentativa = (numero_tentativa+1), ";
		}

		$sql = "UPDATE gatilhos_requisicoes_detalhes
				SET {$setNumeroTentativa} recebido = '" . base64_encode($dadosEnviados['recebido']) . "', sucesso = " . $dadosEnviados['sucesso'] . ",
				tempo_execucao = " . ((int) $dadosEnviados['tempo_execucao']) . "
				WHERE id = " . $dadosEnviados['idGatilhoRequisicaoDetalhes'];
		$this->executarQuery($sql);

		$this->executarQuery("UPDATE gatilhos_requisicoes SET pendente = " . $dadosEnviados['pendente'] . " WHERE id = " . $dadosEnviados['idGatilhoRequisicao']);

		return $dadosEnviados;
	}


	public function __destruct()
    {
    	if (!$this->parametro['transacao']) {
    		return;
    	}

        if ($this->conexaoBanco) {
            try {
                $this->conexaoBanco->commit();
            } catch (Exception $e) {
                $this->debug("Erro ao fazer commit: " . $e->getMessage(), '', 1);
                $this->conexaoBanco->rollBack();
            }
        }
    }
}