<?php

namespace App\Model;

use \PDO;
use \PDOException;
use \DateTime;
use \Exception;

date_default_timezone_set('America/Bahia');

class DB
{
	public $parametro;
	public $setup;
	public $exibirDebug = 0;
	public $debug = [];
	public $erros = [];
	public $gParam;
	protected $conexaoBanco;
	public $nomeArquivoLog;


	public function __construct($args)
	{
		$this->parametro = $args;
		$this->carregarSetup();

		$this->nomeArquivoLog = "emitenota_" . ($this->setup["global.logfile"] ?? 'default.log');

		$conexao = $this->parametro['conexaoBanco'] ?? $this->setup ?? [];
		$this->conectarBanco($conexao);
		// $this->gParam = $this->carregarParametros();
	}


	public function carregarSetup()
	{
		require_once __DIR__ . '/../../setup.php';

		$stp = trim(str_replace("\n", "", $gSETUP));
		$mtz = explode("}", $stp);
		foreach ($mtz as $el) {
			if (strpos($el, "{") !== false) {
				$class = trim(substr($el, 0, strpos($el, "{")));
				$parm = substr($el, strpos($el, "{")+1);
				$parm = trim(substr($parm, 0, strlen($parm)-1));
				$parms = $this->cssDecode($parm);
				foreach ($parms as $key => $value) {
					$classes[$class . "." . $key] = $value;
				}
			}
		}

		$this->setup = $classes;
	}


	public function conectarBanco($credenciais)
	{
		$conexao = 'mysql:host=' . ($credenciais['database.url'] ?? 'localhost')
			. '; port=' . ($credenciais['database.port'] ?? '3306')
			. '; charset=' . ($credenciais['database.charset'] ?? 'latin1')
			. '; dbname='  . ($credenciais['database.name'] ?? '');

		$this->conexaoBanco = new PDO(
			$conexao,
			$credenciais['database.user'] ?? '',
			$credenciais['database.password'] ?? ''
		);
		$this->conexaoBanco->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		if (isset($this->parametro['transacao']) && $this->parametro['transacao'] === true) {
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
			&& is_object($stmt)
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


	public function cssDecode($css)
	{
		$sai = [];  // CORRIGIDO: era "" agora é []
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
				$val = trim($new[1]);
				$val = str_replace("`", "'", $val);
				$val = str_replace("^", "{", $val);
				$val = str_replace("~", "}", $val);
				if (substr($val, 0, 1) == "[") {
					if (strpos($val, "|") !== false) {
						$val = explode("|", substr($val, 1, strlen($val) - 3));  // CORRIGIDO: strlen($val) - 3
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

		return ($sai);
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


	// public function carregarParametros()
	// {
	// 	$sql = 'SELECT * FROM parametros';
	// 	$rs = $this->executarQuery($sql);

	// 	$gParam = array();
	// 	foreach ($rs as $parametro) {
	// 		$gParam[$parametro['chave']] = $parametro;
	// 	}

	// 	return $gParam;
	// }


	public function debug($mensagem, $dados = null, $erro = 0)
	{
		if (!$this->exibirDebug) {
			return '';
		}
		$debug = '[' . date('d-m-Y H:i:s') . ']  FILE:'. debug_backtrace()[1]['file'] . '  LINE:' . debug_backtrace()[1]['line'];
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


	public function gLog($txt, $erro = 0, $arq = "")
	{
		if (!defined('gAPP_FILE') || !defined('gLogPath')) {
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
				if (!defined('gAPP_FILE')) {
					define('gAPP_FILE', "/gApp_");
				}
				setlocale(LC_ALL, 'POSIX');
				if (!defined('gLogPath')) {
					define('gLogPath', "/");
				}
			} else {
				if (!defined('gAPP_FILE')) {
					define('gAPP_FILE', "/tmp/gApp_");
				}
				setlocale(LC_ALL, 'english');
				if (!defined('gLogPath')) {
					if (file_exists("/var/www/log")) {
						define('gLogPath', "/var/www/log/");
					} else {
						define('gLogPath', "/var/log/");
					}
				}
			}
		}


		$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		$httpHost = $_SERVER['HTTP_HOST'] ?? '';

		if (
			strpos($httpHost, "localhost") !== false
			|| strpos($httpHost, "127.0.0.1") !== false
			|| file_exists("/tmp/gLogEnabled")
			|| true
		) {
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
					return basename($trace['file'] ?? 'unknown') . ":" . ($trace['function'] ?? 'unknown') . ":" . ($trace['line'] ?? 0);
				}, array_reverse($d));

				$deb = implode(" => ", $deb);

				$faz = (
					stripos($txt, "select") !== false
					|| stripos($txt, "update") !== false
					|| stripos($txt, "insert") !== false
					|| stripos($txt, "delete") !== false
				);

				$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
				$ipAddress = (($remoteAddr == '::1') || ($remoteAddr == '127.0.0.1')) ? gethostbyname(gethostname()) : $remoteAddr;

				$logHeader = $colors['default'] . date("y-m-d H:i:s") . " " . $ipAddress;

				if ($faz) {
					$logMessage = "$logHeader\t" . $lpre . $deb . $colors['reset'] . "\t" . $cpre . $txt . $cpos;
				} else {
					$logMessage = $logHeader . "\tLOG:\t" . $lpre . $deb . $cpos . "\t" . $cpre . $txt . $cpos;
				}

				$path = @fopen($logfile, 'a');
				if ($path !== false) {
					$logMessage = preg_replace('/\s+/', ' ', $logMessage);
					fputs($path, $logMessage . PHP_EOL);
					fclose($path);
				} else {
					$fallbackLog = '/tmp/' . basename($logfile);
					$path = @fopen($fallbackLog, 'a');
					if ($path !== false) {
						$logMessage = preg_replace('/\s+/', ' ', $logMessage);
						fputs($path, $logMessage . PHP_EOL);
						fclose($path);
					}
				}
			}
		}
	}


	public function gVar($par, $new = null)
	{
		global $_gVar;

		if (!isset($_gVar) || !is_array($_gVar)) {
			$_gVar = [];
		}

		if ($new !== null) {
			$_gVar[$par] = $new;
		}

		return $_gVar[$par] ?? null;
	}



	public function insertTable($nomeTabela, $matrizDados, $retornarId = 1)
    {
        $sql =
            "INSERT INTO {$nomeTabela}
            (" . implode(",", array_keys($matrizDados)) . ")
            VALUES ('" . implode("','", array_map('gCleanField', array_values($matrizDados))) . "')";

		return $this->executarQuery($sql, $retornarId);
    }


	public function salvarRequisicao($dadosConsulta, $dadosRecebidos)
	{
		if (!isset($dadosRecebidos['idGatilhoRequisicao'])) {
			$mtz = [];
			$mtz['enviado'] = base64_encode(json_encode($dadosConsulta));
			$mtz['pendente'] = 0;
			$mtz['id_pessoas_criou'] = $dadosRecebidos['idPessoasCriou'];
			$mtz['id_gatilhos'] = $dadosRecebidos['idGatilhos'] ?? 0;
			$idGatilhoRequisicao = $this->insertTable('gatilhos_requisicoes', $mtz, 1);
		}

		if (!isset($dadosRecebidos['idGatilhoRequisicaoDetalhes'])) {
			$mtz = [];
			$mtz['id_gatilhos_requisicoes'] = $idGatilhoRequisicao;
			$mtz['data_hora'] = date("Y-m-d H:i:s");
			$mtz['recebido'] = '';
			$mtz['sucesso'] = 0;
			$mtz['numero_tentativa'] = 1;
			$mtz['tempo_execucao'] = 0;
			$idGatilhoRequisicaoDetalhes = $this->insertTable('gatilhos_requisicoes_detalhes', $mtz, 1);

			$dadosRecebidos['idGatilhoRequisicao'] = $idGatilhoRequisicao ?? $dadosRecebidos['idGatilhoRequisicao'];
			$dadosRecebidos['idGatilhoRequisicaoDetalhes'] = $idGatilhoRequisicaoDetalhes;
			return $dadosRecebidos;
		}

		$sql = "UPDATE gatilhos_requisicoes_detalhes
				SET recebido = '" . base64_encode($dadosRecebidos['recebido']) . "',
					sucesso = " . $dadosRecebidos['sucesso'] . "
				WHERE id = " . $dadosRecebidos['idGatilhoRequisicaoDetalhes'];
		$this->executarQuery($sql);

		$sql = "UPDATE gatilhos_requisicoes
				SET pendente = " . $dadosRecebidos['pendente'] . ",
					id_gatilhos = " . $dadosRecebidos['idGatilhos'] . "
				WHERE id = " . $dadosRecebidos['idGatilhoRequisicao'];
		$this->executarQuery($sql);

	}


    public function excluirIndicesNumericos($array) {
        foreach (array_keys($array) as $chave) {
            if (is_numeric($chave)) {
                unset($array[$chave]);
            }
        }

        return $array;
    }


	public function updateTable($nomeTabela, $matrizDados, $id)
    {
        foreach ($matrizDados as $atributo => $valor) {
            $atributos[] = " {$atributo} = '" . $valor . "' ";
        }

        $sql = "UPDATE " . $nomeTabela . " SET"
            . implode(', ', $atributos)
            . " WHERE id = " . $id;
        $this->executarQuery($sql);
    }


	public function __destruct()
	{
		// CORREÇÃO: Verificar se existe antes de acessar
		if (!isset($this->parametro['transacao']) || !$this->parametro['transacao']) {
			return;
		}

		if ($this->conexaoBanco) {
			try {
				$this->conexaoBanco->commit();
			} catch (Exception $e) {
				$this->debug("Erro ao fazer commit: " . $e->getMessage(), '', 1);
				if ($this->conexaoBanco->inTransaction()) {
					$this->conexaoBanco->rollBack();
				}
			}
		}
	}
}

/************* EXEMPLO DE USO
$parametros = array(
	'caminhoSetup' => '/var/www/html/wms/logiclog/setup.php'
);
$db = new DB($parametros);
$r = $db->executarQuery("Select * from itens limit 10");
echo '<pre>';var_dump($r);exit;
*/