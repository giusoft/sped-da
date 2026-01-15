<?php
/**
 * Este arquivo contém métodos para acesso a bancos de dados
 *
 * @author	giuliano
 * @version	1.0 17-12-2012 14:08
 */
include_once $gPathLib . $setup->get("lib.adodb");

$dbConn = [];

/* TODO
 *
 * $ADODB_ASSOC_CASE para mudar case sensitive
 *
 */
define("gD_DEFAULT", 	'');
define("gD_NOTRANS", 	0);
define("gD_BEGINTRANS", 1);
define("gD_INTRANS", 	2);
define("gD_ENDTRANS", 	3);

define("gQ_SELECT", 	1);
define("gQ_INSERT", 	2);
define("gQ_UPDATE", 	4);
define("gQ_DELETE", 	8);

define("gE_SYNTAX", 		'Erro de sintaxe');
define("gE_DATABASE", 		'Erro no banco de dados');
define("gE_DATABASEQUERY",  'Erro na expressão de acesso ao banco de dados.');

$setup->set("database.lasttransaction", gD_NOTRANS);
$setup->set("database.lastquery", "");

/**
 * Classe para acesso a banco de dados
 * @package	gDatabase
 * @author	Giuliano Nascimento <giusoft@hotmail.com>
 * @version	4.0 17-12-2013 10:50
 */
class gDatabase
{

	public $con;
	public $mode              = "";
	public $persistent        = false;
	public $connected         = false;
	public $iddProcess        = true;
	public $iddIgnore         = false;
	public $isTrueTransaction = false;
	public $useTransaction    = false;

	public function __construct($json="")
	{
		if ($json != "") {
			$jarr = cssDecode($json);
			$this->mode = $jarr['mode'] == "pdo" ? "pdo" : "adodb";
			$this->persistent = ($jarr['persistent'] == "true") || (($jarr['persistent'] == "on"));

			if ($jarr['idd'] == "false") {
				$this->iddProcess = false;
			}

			if ($jarr['iddIgnore'] == "true") {
				$this->iddIgnore = true;
			}

			if (gVar("database.transaction") == "true") {
				$this->useTransaction = true;
				$this->beginTransaction();
			} else {
				$this->useTransaction = false;
			}

		}
	}


	public function __destruct()
	{
		if ($this->useTransaction && $this->isTrueTransaction) {
			$this->commit();
		}
	}


	/**
	 * Conecta ao banco de dados com os parâmetros fornecidos em setup.php
	 * @author	giuliano
	 * @version	4.0 17-12-2013 10:50
	 */
	public function connect(): void
	{
		try {
			$charset = gVar("database.charset");
			if ($charset) {
				$charset = ";charset=$charset";
			}

			if ($this->persistent) {
				$this->con = new PDO(gVar("database.engine") . ":host=" . gVar("database.url") . "$charset;dbname=" . gVar("database.name"), gVar("database.user"), gVar("database.password"), [PDO::ATTR_PERSISTENT => true]);
			} else {
				$this->con = new PDO(gVar("database.engine") . ":host=" . gVar("database.url") . "$charset;dbname=" . gVar("database.name"), gVar("database.user"), gVar("database.password"));
			}

			if (gVar("database.engine") == "mysql") {
				$this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				if (!$charset) {
					$this->con->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8");
				}
			}

			$this->connected = true;
		} catch (PDOException $e) {
			print "DB Connection Error: " . $e->getMessage() . "<br/>";
			die();
		}
	}


	public function beginTransaction(): void
	{
		$this->connect();
		if ($this->useTransaction) {
			$this->isTrueTransaction = true;
			$this->con->beginTransaction();
		}
	}


	public function commit(): void
	{
		if ($this->useTransaction && is_object($this->con)) {
			try {
				$this->con->commit();
			} catch (Exception $e) {
				echo "";
			}
		}
	}


	public function rollBack(): void
	{
		if ($this->useTransaction) {
			$this->isTrueTransaction=false;
			$this->con->rollBack();
		}
	}


	//function endTransaction()
	//{
	//	$this->commit();
	//	$this->endTransaction;
	//}
	/**
	 * Executa query. Se passados mais de um parâmetro, os seguintes são valores a substituir na query (onde constam '?')
	 * @param   string $sql Query a executar
	 * @param   string $par1 Primeiro valor a sebstituir
	 * @param   string $par2 Segundo valor a sebstituir
	 * @param   string $parN N... valor a sebstituir
	 * @author	giuliano
	 * @version	4.0 17-12-2013 10:50
	 */
	public function query()
	{
		global $usrId;

		$aCnt 	= func_num_args();
		$aList  = func_get_args();
		$select = false;
		$sai = '';
		try {
			if (!$this->connected) {
				$this->connect();
			}

			$sql = $aList[0];
			// Query com parâmetros
			if (!$this->iddIgnore) {
				$sql=sql2idd($sql);
			}

			//$sql=str_replace(array("\n","  ")," ", $sql);
			$sql = gQueryReplaceMacros($sql);
			$stmt = $this->con->prepare($sql);
			//gLog($stmt."<==");
			if ($aCnt > 1) {
				for ($a = 1; $a < $aCnt; $a++) {
					$stmt->bindParam($a, $aList[$a]);
				}
			}

			$sql = str_replace(["\n", "  "]," ", $sql);
			gLog("SQL(" . gVar("database.name") . "):\t" . $sql);

			$stmt->execute();

			if (
				stripos($sql, "SELECT ") !== false
				&& strtoupper(substr(trim($sql),0,6)) !== "UPDATE"
				&& strtoupper(substr(trim($sql),0,6)) !== "DELETE"
			) {
				if ($this->mode == "adodb") {
					// Emula o PHP ADODB
					$sai = $stmt->fetchAll();
					$obj = new gAdodb($sai);
					$sai = $obj;
				} else {
					return($stmt->fetchAll());
				}
			}

		} catch (PDOException $e) {
			$this->rollBack();
			if ((!str_contains($e->getMessage(),"General error")) || true) {
				print "<hr><b>Houve um erro ao executar um comando no banco de dados</b><br>";

				if ($usrId <= 1) {
					echo  "<I>".$e->getMessage() . "</I><br/><br/>";
					echo "<pre>";
					print_r($aList[0]);
					echo "</pre>";
				}

				gLog("SQL(Error)(" . gVar("database.name") . "):\t" . $sql, LOG_ERROR);
				gLog("SQL(Error):\t" . $e->getMessage(), LOG_ERROR);
				die();
			}
		}

		return($sai);
	}


	public function gfwType($name, $type): string
	{
		$sai = "text";
		switch ($type) {
			case 'TINY':
   			case 'BOOL':
				$sai = 'checkbox';
				break;

			case 'STRING':
			case 'VAR_STRING':
				$sai = 'text';
				break;

			case 'BLOB':
				$sai = 'textarea';
				break;

			case 'LONG':
			case 'LONGLONG':
				$sai = 'integer';
				break;

			case 'DATE':
				$sai = 'date';
				break;

			case 'DATETIME':
				$sai = 'dateTime';
				break;

			case 'NEWDECIMAL':
				$sai = 'number';
				break;
		}

		return (match ($name) {
			'cpf' 	   		=> 'cpf',
			'cnpj'     		=> 'cnpj',
			'senha'    		=> 'password',
			'password' 		=> 'password',
			'email'    		=> 'email',
			'url' 	   		=> 'url',
			'site' 	   		=> 'url',
			'ip' 	   		=> 'ip',
			'ncm' 	   		=> 'ncm',
			'image'    		=> 'file',
			'active'   		=> 'checkbox',
			'ativo'    		=> 'checkbox',
			'file' 	   		=> 'file',
			'arquivo_anexo' => 'file',
			'attachment'    => 'file',
			default => $sai,
  		});
	}


 	public function describe($sql): array
	{
		$aCnt   = func_num_args();
		$aList  = func_get_args();
		$select = false;
		$sai = [];

		try {
			if (!$this->connected) {
				$this->connect();
			}

			// Query com parâmetros
			$sql = str_replace(["\n", "  "]," ", $sql);
			$sql = gQueryReplaceMacros($sql);

			$rs = $this->con->query($sql);
			for ($i = 0; $i < $rs->columnCount(); $i++) {
				$col  = $rs->getColumnMeta($i);
				$name = $col['name'];

				if (($col['native_type'] == '') && ($col['pdo_type'] == 2)) {
					$col['native_type'] = 'BOOL';
				}

				$col['type'] = $this->gfwType($name, $col['native_type']);
				$col['fieldLabel'] = gField2String($name);
				$col['maxLength']  = $col['len'];

				if (
					($col['flags'][0] == 'not_null')
					|| ($col['flags'][1] == 'not_null')
					|| ($col['flags'][2] == 'not_null')
				) {
					$col['allowBlank']='false';
				} else {
					$col['allowBlank']='true';
				}

				$col['align'] = gCheckAlignByType($col['type']);
				unset($col['table']);
				$name = str_replace("'",'"',$name);
				$sai[$name] = $col;
			}
			//gLog("DESCRIBE(" . gVar("database.name") . "):\t" . $sql);
		} catch (PDOException $e) {
			print "DB Query Error: " . $e->getMessage() . "<br/>";
			gLog("DESCRIBE(" . gVar("database.name") . "):\t" . $sql, LOG_ERROR);
			gLog("DESCRIBE(Error):\t" . $e->getMessage(), LOG_ERROR);
			die();
		}
		return($sai);
	}
}


class gDataDictionary
{
	public $dictionary = [], $arrays;

	/**
	 * Adiciona entrada de dicionário
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $json
	 */
	public function add($json, $array): void
	{
		if ($json != "") {
			$mtz  = jsonDecode($json, ";", false);
			$name = stripQuote($mtz['name']);
			$mtz  = jsonRemove($mtz, "name");
			$this->dictionary[$name] = $mtz;
			$this->arrays[$name] = $array;
		}
	}


	public function set($mtz): void
	{
		$this->dictionary = $mtz;
	}


	public function get()
	{
		return($this->dictionary);
	}
}


class gDataFilter
{
	public $filter = [], $arrays = "";

	/**
	 * Adiciona entrada
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query
	 * @return array $var Campos e parâmetros
	 */
	public function add($json, $array = ""): void
	{
		if ($json != "") {
			$mtz = jsonDecode($json, ";", false);
			$name = stripQuote($mtz['name']);
			//$mtz=jsonRemove($mtz,"name");
			$this->filter[] = $mtz;
			$this->arrays[] = $array;
		}
	}


	public function set($mtz): void
	{
		$this->filter = $mtz;
	}


	public function get()
	{
		return($this->filter);
	}


	public function getArrays()
	{
		return($this->arrays);
	}
}


class gDataParameters
{
	public $parm = [];

	/**
	 * Adiciona entrada
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query
	 * @return array $var Campos e parâmetros
	 */
	public function add($json): void
	{
		/*
		  if ($json<>"")
		  {
		  $mtz=jsonDecode($json,";",false);
		  $name=stripQuote($mtz['name']);
		  //$mtz=jsonRemove($mtz,"name");
		  $this->parm[]=$mtz;
		  }
		 */
		$this->parm[] = $json;
	}


	public function set($mtz): void
	{
		$this->parm = $mtz;
	}


	public function get()
	{
		return($this->parm);
	}
}


/**
 * Classe para compatibilidade retroativa com o PHP Adodb
 * @package	gAdodb
 * @author	Giuliano Nascimento <giusoft@hotmail.com>
 * @version	4.0 17-12-2013 10:50
 */
class gAdodb
{
	public $rs;
	public $fields;
	public $pointer = 0;
	public $ttlFields = 0;
	public $EOF = false;
	public $BOF = true;

	public function __construct($rs)
	{
		$this->ttlFields = count($rs);
		if ($this->ttlFields == 0) {
			$this->EOF = true;
		}

		$this->rs = $rs;
		$this->fields = $this->rs[$this->pointer];
	}


	public function refreshFields(): void
	{
		$this->fields = ($this->pointer >= $this->ttlFields) || ($this->pointer < 0) ? null : $this->rs[$this->pointer];
	}


	public function fieldCount()
	{
		return($this->ttlFields);
	}


	public function MoveNext(): void
	{
		$this->pointer++;
		if ($this->pointer >= $this->ttlFields) {
			$this->EOF = true;
			$this->pointer = $this->ttlFields;
		} else {
			$this->BOF = false;
		}

		$this->refreshFields();
	}


	public function MovePrevious(): void
	{
		$this->pointer--;
		if ($this->pointer == 0) {
			$this->BOF = true;
		} else {
			$this->EOF = false;
		}
		$this->refreshFields();
	}


	public function MoveFirst(): void
	{
		$this->pointer = 0;
		$this->BOF = true;
		if ($this->ttlFields == 0) {
			$this->EOF = true;
		}

		$this->refreshFields();
	}


	public function MoveLast(): void
	{
		$this->pointer = $this->ttlFields;
		if ($this->ttlFields == 0) {
			$this->BOF = true;
			$this->EOF = true;
		}

		$this->refreshFields();
	}
}

// Funções =========================================================================================

/**
 * Executa uma query usando o PDO
 * @author Giuliano
 * @param String $sql Query
 */
function dbQuery()
{
	global $dbConn;

	$connName = gVar("database.name").gVar("database.url");
	if (is_null($dbConn[$connName])) {
		$dbConn[$connName] = new gDatabase("{mode: pdo}");
	}

	$aCnt  = func_num_args();
	$aList = func_get_args();

	foreach($aList as $key => $value) {
		//$aList[$key]=str_replace(array("\r","\n","\r\n","  ")," ",$value);
		$aList[$key] = $value;
	}

	return (match ($aCnt) {
		1 => $dbConn[$connName]->query($aList[0]),
		2 => $dbConn[$connName]->query($aList[0], $aList[1]),
		3 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2]),
		4 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3]),
		5 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4]),
		6 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4], $aList[5]),
		7 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4], $aList[5], $aList[6]),
		8 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4], $aList[5], $aList[6], $aList[7]),
		9 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4], $aList[5], $aList[6], $aList[7], $aList[8]),
		10 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4], $aList[5], $aList[6], $aList[7], $aList[8], $aList[9]),
		default => $sai,
	});
}


function dbCommit(): void
{
	$connName = gVar("database.name").gVar("database.url");
	if (is_null($dbConn[$connName])) {
		$dbConn[$connName] = new gDatabase("{mode: pdo}");
	}

	$dbConn[$connName]->commit();
}


function dbRollback(): void
{
	$connName = gVar("database.name").gVar("database.url");
	if (is_null($dbConn[$connName])) {
		$dbConn[$connName] = new gDatabase("{mode: pdo}");
	}

	$dbConn[$connName]->rollBack();
}


/**
 * Executa uma query usando o PDO sem processar IDD
 * @author Giuliano
 * @param String $sql Query
 */
function dbFastQuery()
{
	global $dbConn;

	$connName = gVar("database.name").gVar("database.url");
	if (is_null($dbConn[$connName])) {
		$dbConn[$connName] = new gDatabase("{mode: pdo; iddIgnore: true}");
	}

	// Mais rápido
	$aCnt  = func_num_args();
	$aList = func_get_args();

	return(match ($aCnt) {
		1 => $dbConn[$connName]->query($aList[0]),
		2 => $dbConn[$connName]->query($aList[0], $aList[1]),
		3 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2]),
		4 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3]),
		5 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4]),
		6 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4], $aList[5]),
		7 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4], $aList[5], $aList[6]),
		8 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4], $aList[5], $aList[6], $aList[7]),
		9 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4], $aList[5], $aList[6], $aList[7], $aList[8]),
		10 => $dbConn[$connName]->query($aList[0], $aList[1], $aList[2], $aList[3], $aList[4], $aList[5], $aList[6], $aList[7], $aList[8], $aList[9]),
		default => $sai,
	});
}


/**
 * Executa uma query usando o PDO e retorna somente o primeiro campo do primeiro registro
 * @author Giuliano
 * @param String $sql Query
 */
function dbQueryValue(): mixed
{
    global $dbConn;
    $connName = gVar("database.name") . gVar("database.url");

    $dbConn ??= [];
    $dbConn[$connName] ??= new gDatabase("{mode: pdo}");

    $args = func_get_args();

    $rs = $dbConn[$connName]->query(...$args);

    return $rs[0][0] ?? null;
}


/**
 * Apaga registro(s) do banco
 * @param string $table Nome da tabela
 * @param type $id
 */
function dbDelete($table, $id): void
{
	dbQuery("DELETE FROM $table WHERE id=$id");
}


/**
 * Cria um novo registro no banco de dados
 *
 * @param string $table Nome da tabela
 * @param array $fields Array com os campos e seus respectivos valores
 * @param boolean $returnId Booleano indicador se deve ou não retornar o último id criado
 *
 * @return int Retorna o id do novo registro criado
 */
function dbInsert($table, $fields, $returnId = false): int
{
	global $AESKEY;

	$usrIdd = intval($_SESSION['usrIdd']);
	if ($fields['idd'] == '') {
		if (gVar("database.idd") == 'true') {
			$fields['idd'] = $usrIdd;
		} elseif (gVar("global.idd") == 'true') {
			$fields['idd'] = 1;
		}
	}

	$sai = 0;
	$nfields = [];
	foreach ($fields as $key => $value) {
		// Verifica se deve criptografar o campo
		if (str_starts_with($key, '*')) {
			$nfields[substr($key,1)] = "HEX(AES_ENCRYPT('" . $value . "','" . $AESKEY . "'))";
		} else {
			$nfields[$key] = "'" . $value . "'";
		}
	}

	$sql = "INSERT INTO $table (" . implode(",", array_keys($nfields)) . ") VALUES (" . implode(",", array_values($nfields)) . ")";
	dbQuery($sql);
	if ($returnId) {
		//$sql = "SELECT LAST_INSERT_ID() id";
		$sql = "SELECT id FROM $table WHERE ";
		$flt = [];
		foreach ($nfields as $key => $value) {
			if ($value !== "") {
				$flt[] = $key . "=$value";
			}
		}

		$sql .= implode(" and ", $flt) . " order by id desc";
		$r = dbQuery($sql);
		$sai = intval($r[0]['id']);
	}

	return($sai);
}


/**
 * Cria um novo registro no banco de dados ignorando idd
 *
 * @param string $table Nome da tabela
 * @param array $fields Array com os campos e seus respectivos valores
 * @param boolean $returnId Booleano indicador se deve ou não retornar o último id criado
 *
 * @return int Retorna o id do novo registro criado
 */
function dbFastInsert($table, $fields, $returnId = false): int
{
	global $AESKEY;

	$sai = 0;
	$nfields = [];
	foreach ($fields as $key => $value) {
		// Verifica se deve criptografar o campo
		if (str_starts_with($key, '*')) {
			$nfields[substr($key,1)] = "HEX(AES_ENCRYPT('" . $value . "','".$AESKEY."'))";
		} else {
			$nfields[$key] = "'".$value."'";
		}
	}

	$sql = "INSERT INTO $table (" . implode(",", array_keys($nfields)) . ") VALUES (" . implode(",", array_values($nfields)) . ")";
	dbQuery($sql);

	if ($returnId) {
		$sql = "SELECT id FROM $table WHERE ";
		$flt = [];
		foreach ($nfields as $key => $value) {
			if ($value !== "") {
				$flt[] = $key . "=$value";
			}
		}

		$sql .= implode(" and ", $flt) . " order by id desc";
		$r = dbFastQuery($sql);
		$sai = intval($r[0]['id']);
	}

	return($sai);
}


/**
 *
 * @param string $table Nome da tabela
 * @param array $fields Array com os campos e seus respectivos valores
 * @param string $where
 * @param type $idField
 */
function dbUpdate($table, $fields, $where, $idField = 'id'): void
{
	global $AESKEY;

	$nfields = [];
	foreach ($fields as $key => $value) {
		// Verifica se deve criptografar o campo
		if (str_starts_with($key, '*')) {
			$nfields[substr($key,1)] = "HEX(AES_ENCRYPT('" . $value . "','" . $AESKEY . "'))";
		} else {
			$nfields[$key] = "'" . $value . "'";
		}
	}
	$sql = "UPDATE $table set ";
	$flds = [];
	foreach ($nfields as $key => $value) {
		if (str_starts_with($value, "'")) {
			$flds[] = "$key='" . str_replace("'","\\'",substr($value,1,strlen($value)-2)) . "'";
		} else {
			$flds[] = "$key=$value";
		}
	}

	if (intval($where) > 0) {
		$where = $idField . "=" . $where;
	}

	$sql .= implode(",", $flds) . " where " . $where;
	dbQuery($sql);
}


/**
 *
 * @param string $table Nome da tabela
 * @param array $fields Array com os campos e seus respectivos valores
 * @param string $where
 * @param type $idField
 */
function dbFastUpdate($table, $fields, $where, $idField = 'id'): void
{
	global $AESKEY;

	$nfields = [];
	foreach ($fields as $key => $value) {
		// Verifica se deve criptografar o campo
		if (str_starts_with($key, '*')) {
			$nfields[substr($key,1)] = "HEX(AES_ENCRYPT('" . $value . "','" . $AESKEY . "'))";
		} else {
			$nfields[$key] = "'" . $value . "'";
		}
	}

	$sql = "UPDATE $table set ";
	$flds = [];
	foreach ($nfields as $key => $value) {
		if (str_starts_with($value, "'")) {
			$flds[] = "$key='".str_replace("'","\\'",substr($value,1,strlen($value)-2))."'";
		} else {
			$flds[] = "$key=$value";
		}
	}

	if (intval($where) > 0) {
		$where = $idField . "=" . $where;
	}

	$sql.=implode(",", $flds) . " where " . $where;
	dbFastQuery($sql);
}


/**
 * Retorna com o valor de um campo da tabela em referência, tendo como índice o campo id
 * @author Giuliano
 * @param String $table Nome da tabela
 * @param int $id Valor do campo id
 * @param Variable $field Pode ser o número do campo da tabela ou seu nome
 * @return String valor do campo
 */
function dbFieldById($table, $id, $field = 2)
{
	global $gLastTransaction;

	if (!is_null($id)) {
		$sql = "SELECT * FROM $table WHERE id=$id";
		$rs  = dbQuery($sql);
		if ($rs) {
			return $rs[0][$field];
		}

		return gLng("null_value");
	}

	return;
}


/**
 *
 * @param type $table
 * @param type $id
 * @param type $field
 * @return type
 */
function gFieldById($table, $id, $field = 2)
{
	return(dbFieldById($table, $id, $field));
}


/**
 * Executa uma query sem verificações desnecessárias e sem utilizar o campo IDD
 * @author Giuliano
 * @param String $sql Query
 */
function gFastQuery($sql)
{
	// Mais rápido
	$db = new gDatabase("{mode: adodb; idd: false}");
	return($db->query($sql));
}


/**
 * Executa uma query usando o AdoDB
 * @author Giuliano
 * @param String $sql Query
 */
function gQuery($sql, $bd = gD_DEFAULT, $fetch = 0, $transaction = gD_NOTRANS)
{
	return(gDB::run($sql, $fetch, $transaction));
}


function gQueryLimit($sql, $ini = 1, $qtd = 9999999, $bd = "_default", $fetch = 0)
{
	return(gDB::runLimit($sql, $ini, $qtd, $fetch));
}


/**
 * Retorna seções encontradas em uma Query
 * @author	Giuliano
 * @version	2009-10-13
 * @param string $sql Query completa
 * @return array $var Seções e conteúdos
 */
function parseQuerySections($sql)
{
	$posSec[] = ["SELECT ", "^[ \t\n\r]?[Ss][Ee][Ll][Ee][Cc][Tt][ \t\n\r]"];
	$posSec[] = ["UPDATE ", "^[ \t\n\r]?[Uu][Pp][Dd][Aa][Tt][Ee][ \t\n\r]"];
	$posSec[] = ["DELETE", "^[ \t\n\r]?[Dd][Ee][Ll][Ee][Tt][Ee]"];
	$posSec[] = ["INSERT ", "^[ \t\n\r]?[Ii][Nn][Ss][Ee][Rr][Tt][ \t\n\r]"];
	$posSec[] = ["REPLACE ", "^[ \t\n\r]?[Rr][Ee][Pp][Ll][Aa][Cc][Ee][ \t\n\r]"];
	$posSec[] = [" FROM ", "[ \t\n\r][Ff][Rr][Oo][Mm][ \t\n\r]"];
	$posSec[] = [" WHERE ", "[ \t\n\r][Ww][Hh][Ee][Rr][Ee][ \t\n\r]"];
	$posSec[] = [" GROUP BY ", "[ \t\n\r][Gg][Rr][Oo][Uu][Pp] [Bb][Yy][ \t\n\r]"];
	$posSec[] = [" ORDER BY ", "[ \t\n\r][Oo][Rr][Dd][Ee][Rr] [Bb][Yy][ \t\n\r]"];
	$posSec[] = [" HAVING ", "[ \t\n\r][Hh][Aa][Vv][Ii][Nn][Gg][ \t\n\r]"];
	$posSec[] = [" LIMIT ", "[ \t\n\r][Ll][Ii][Mm][Ii][Tt][ \t\n\r]"];

	// Pra evitar a confusão entre termos que estão entre as aspas simples ou duplas
	$ativo = "";
	for ($a = 0; $a < strlen($sql); $a++) {
		if ($sql[$a] === "'") {
			$ativo = $ativo === "" ? "'" : "";
		}

		if ($sql[$a] === '"') {
			$ativo = $ativo === "" ? '"' : "";
		}

		if (($ativo !== "") && ($sql[$a] === " ")) {
			$sql[$a] = "~";
		}
	}

	foreach ($posSec as $ereg) {
		$sql = preg_replace('/'.$ereg[1].'/', $ereg[0], (string) $sql);
	}

	foreach ($posSec as $section) {
		$sectionsPos[$section[0]] = strpos((string) $sql, $section[0]);
	}

	foreach ($sectionsPos as $key => $value) {
		if ($value !== false) {  // tem seção, então, busca o final
			$maxpos = strlen((string) $sql);
			foreach ($sectionsPos as $nvalue) {
				if ($nvalue !== false) {
					if (($nvalue < $maxpos) && ($nvalue > $value)) {
						$maxpos = $nvalue;
					}
				}
			}
			$sections[trim(strtolower($key))] = str_replace("~", " ", trim(substr((string) $sql, $value + strlen($key), $maxpos - $value - strlen($key))));
		}
	}

	return ($sections);
}


/**
 * Retorna tabelas encontradas em uma Query
 * @author	Giuliano
 * @version	2009-10-13
 * @param string $sql Query (somente conteúdo entre o FROM e outra seção
 * @return array $var Tabelas e parâmetros
 */
function parseQueryTables($sql)
{
	$sql = preg_replace("/[ \t\n\r][Ll][Ee][Ff][Tt] [Jj][Oo][Ii][Nn][ \t\n\r]/", " LEFT JOIN ", $sql);
	$sql = preg_replace("/[ \t\n\r][Rr][Ii][Gg][Hh][Tt] [Jj][Oo][Ii][Nn][ \t\n\r]/", " RIGHT JOIN ", (string) $sql);
	$sql = preg_replace("/[ \t\n\r][Ii][Nn][Nn][Ee][Rr] [Jj][Oo][Ii][Nn][ \t\n\r]/", " INNER JOIN ", (string) $sql);
	$sql = preg_replace("/[ \t\n\r][Jj][Oo][Ii][Nn][ \t\n\r]/", " JOIN ", (string) $sql);
	$sql = preg_replace("/[ \t\n\r][Oo][Nn][ \t\n\r]/", " ON ", (string) $sql);
	$t   = explode(" JOIN ", (string) $sql);
	$nextJoin = "";

	foreach ($t as $tdata) {
		$tdata = str_replace("\n", " ", $tdata);
		$tdata = str_replace("\t", "", $tdata);
		$tdata = str_replace("  ", " ", $tdata);
		$tdata = str_replace("  ", " ", $tdata);
		$tdata = str_replace("  ", " ", $tdata);
		$table = [];
		//$table['data']=$tdata;
		if ($nextJoin !== "") {
			$table['join'] = 'left';
			$nextJoin = "";
		}

		if (str_contains($tdata, " LEFT")) {
			$nextJoin = "left";
			$tdata = str_replace(" LEFT", "", $tdata);
		}

		if (str_contains($tdata, " RIGHT")) {
			$nextJoin = "right";
			$table['join'] = 'right';
			$tdata = str_replace(" RIGHT", "", $tdata);
		}

		if (str_contains($tdata, " INNER")) {
			$nextJoin = "inner";
			$tdata = str_replace(" INNER", "", $tdata);
		}

		if (str_contains($tdata, " ON ")) {
			$table['on'] = substr($tdata, strpos($tdata, " ON ") + 4);
			if (stripos($table['on'], " WHERE ") !== false) {
				$table['on'] = substr($table['on'],0,stripos($table['on'], " WHERE "));
			}

			if (stripos($table['on'], " ORDER ") !== false) {
				$table['on'] = substr($table['on'],0,stripos($table['on'], " ORDER "));
			}

			if (stripos($table['on'], " GROUP ") !== false) {
				$table['on'] = substr($table['on'],0,stripos($table['on'], " GROUP "));
			}

			$tdata = substr($tdata, 0, strpos($tdata, " ON "));
		}

		//$tdata=str_replace(" l"," ",$tdata);
		$prox = 0;
		$tmp  = explode(" ", $tdata);
		if (strtoupper($tmp[0]) === "SELECT") {
			$table['name'] = '';
			$table['join'] = '';
			$table['on'] = '';
   			$counter = count($tmp);
			for ($a = 0; $a < $counter; $a++) {
				$col=$tmp[$a];
				if (strtoupper($col) === "FROM") {
					$table['name']=$tmp[$a+1];
					if (strtolower($tmp[$a+2]) === "as") {
						$table['alias'] = trim($tmp[$a+3]);
						$table['fieldLabel'] = trim($tmp[$a+3]);
					} elseif ($tmp[$a+2] !== '' && strtoupper($tmp[$a+2]) !== 'LIMIT' && strtoupper($tmp[$a+2]) !== 'ORDER' && strtoupper($tmp[$a+2]) !== 'WHERE' && strtoupper($tmp[$a+2]) !== 'GROUP') {
						$table['alias'] = trim($tmp[$a+2]);
						$table['fieldLabel'] = trim($tmp[$a+2]);
					} else {
						$table['alias'] = $table['name'];
						$table['fieldLabel'] = $table['name'];
					}
				}
			}

		} else {
			$table['name'] = str_replace("\n", "", trim($tmp[$prox]));
			$prox++;
			if (strtolower($tmp[$prox]) === "as") {
				$prox++;
				$table['alias'] = str_replace("\n", "", trim($tmp[$prox]));
				$table['fieldLabel'] = $tmp[$prox];
			} elseif ($tmp[$prox] !== "" && strtoupper($tmp[$prox]) !== 'LIMIT') {
				$table['alias'] = str_replace("\n", "", trim($tmp[$prox]));
				$table['fieldLabel'] = $tmp[$prox];
			} else {
				$table['alias'] = str_replace("\n", "", trim($table['name']));
				$table['fieldLabel'] = $table['name'];
			}
		}

		$tables[] = $table;
	}

	return($tables);
}


/**
 * Formata Query para saída na tela
 * @author	Giuliano
 * @version	2009-10-13
 * @param string $sql Query
 */
function formatQuery($sql): string
{
	$sections = parseQuerySections($sql);
	foreach ($sections as $key => $value) {
		$sai .= strtoupper($key) . "\n\t$value\n";
	}

	return($sai);
}


function sql2idd($sql)
{
	if (stripos((string) $sql,'idd') === false) {
		$id  = intval($_SESSION['usrId']);
		$idd = intval($_SESSION['usrIdd']);

		if ($idd == 0 || $idd == 1) {
			$idd = 0;
		}

		if ($id > 1) {
			$sec = parseQuerySections($sql);
			if (gVar("database.idd") == "true") {
				// SELECT - Adiciona filtro por idd
				if (isset($sec['select'])) {
					$tab  = parseQueryTables($sec["from"]);
					$aflt = [];
					foreach ($tab as $t) {
						$alias = $t['alias'];
						if ($alias != "") {
							$alias.=".";
						}

						$aflt[] = "(" . $alias . "idd=$idd or " . $alias . "idd=0 or " . $alias . "idd is null)";
					}

					$flt = implode(" and ", $aflt);

					if (isset($sec['where'])) {
						$sec["where"] = "($flt) and " . $sec["where"];
					} else {
						$sec["where"] = "($flt)";
					}

					$sql = "SELECT " . $sec['select'] . " FROM " . $sec['from'] . " WHERE " . $sec['where'];
					if (isset($sec['group by'])) {
						$sql .= " GROUP BY " . $sec["group by"];
					}

					if (isset($sec['order by'])) {
						$sql .= " ORDER BY " . $sec["order by"];
					}

					if (isset($sec['limit'])) {
						$sql .= " LIMIT " . $sec["limit"];
					}
				}

				// DELETE - Evita apagar idd=0 e idd<>do seu
				if (isset($sec['delete'])) {
					$flt = "(idd=$idd)";
					$sql = "DELETE " . $sec['delete'] . " FROM " . $sec['from'];
					$sql .= " WHERE " . $flt;

					if (isset($sec['where'])) {
						$sql .= " and (" . $sec['where'] . ")";
					}

				}

				if (isset($sec['update'])) {
					$flt = "(idd=$idd)";
					$sql = "UPDATE " . $sec['update'] . " WHERE " . $flt;
					if (isset($sec['where'])) {
						$sql .= " and (" . $sec['where'] . ")";
					}
				}

				// INSERT - Adiciona campo idd
				if (isset($sec['insert'])) {
					$sql = preg_replace("/[ \t\n\r][Vv][Aa][Ll][Uu][Ee][Ss][ \t\n\r]/", " VALUES ", (string) $sql);
					$div = explode(" VALUES", $sql);
					if (isset($div[0])) {
						if (!str_contains($div[0], "idd,")) {
							$fld = explode("(", $div[0]);
							$sql = $fld[0] . "(idd,";
       						$counter = count($fld);
							for ($a = 1; $a < $counter; $a++) {
								$sql .= $fld[$a];
							}

							$sql .= " VALUES ($idd, " . substr(trim($div[1]), 1);
						} else {
							$fld = explode("(", $div[0]);
							$sql = $fld[0] . "(";
       						$counter = count($fld);
							for ($a = 1; $a < $counter; $a++) {
								$sql .= $fld[$a];
							}
							$sql .= " VALUES (" . substr(trim($div[1]), 1);
						}
					}
					//gLog("====> \n$sql");
				}

			}
		}
	}

	return($sql);
}

// Obsoleto ================================================================================================

class gDB
{

	public $dictionary = "";
	public $tableDict;
	public $sections = "";
	public $tables = [];
	public $fields = [];
	public $associativeFields;
	public $querys = [];
	public $query = "";
	public $types;
	private $md5;

	public function __construct()
	{
		$this->types["C"] = "text";
		$this->types["D"] = "date";
		$this->types["T"] = "datetime";
		$this->types["N"] = "number";
		$this->types["I"] = "integer";
		$this->types["R"] = "integer";
		$this->types["X"] = "textarea";
		$this->types["B"] = "memo";
		$this->types["L"] = "checkbox";
		$this->types["W"] = "password";
		$this->types["U"] = "url";
		$this->types["E"] = "email";
		$this->types["A"] = "plate";
		$this->types["F"] = "cpf";
		$this->types["J"] = "cnpj";
		$this->types["P"] = "ip";
		$this->types["H"] = "container";
		$this->types["M"] = "time";
		$this->types["O"] = "ncm";
	}


	/**
	 * Gera um "recordset" com a configuração padrão do banco de dados
	 * @author Giuliano
	 * @param String $sql Query
	 * @param String $bd Banco de dados (opcional se for utilizado o padrão definido do arquivo de configuração)
	 * @param int $fetch Modo do "fetch" (verifique a documentação do ADOdb)
	 * @return Recordset
	 */
	public static function run($sql, $fetch = 0, $transaction = gD_NOTRANS, $ini = -1, $qtd = -1, $iddControl = true)
	{
		global $setup;
		global $http_base;
		global $http_inc;
		global $http_css, $http_system_css;
		global $http_img;
		global $http_lib;

		$sqlOriginal = $sql;
		//if ($iddControl)
		$sql = sql2idd($sql);

		//$bd=$setup->get("database.db");
		$gDebug = $setup->get("global.debug");
		$gDebug = 10;

		$setup->set("database.lastquery", $sql);
		$setup->set("database.lasttransaction", $transaction);

		//if ($bd=="")
		$bd = $setup->get("database.name");
		$bd = str_replace(".dbo", "", $bd);
		if ($fetch == 0) {
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		} else {
			$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		}

		if ($transaction <= 1) {
			$db = ADONewConnection($setup->get("database.engine"));

			if (gVar("database.engine") == "oci8") {
				$db->NLS_LANG = "BRAZIL_BRAZILIAN PORTUGUESE.WE8ISO8859P1";
				//$db->NLS_LANG="AMERICAN_AMERICA.WE8ISO8859P1";
				//$db->NLS_LANG="BRAZIL_BRAZILIAN PORTUGUESE.AL24UTFFSS";
				$db->NLS_DATE_FORMAT = 'YYYY-MM-DD';
				$db->NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS';
				$db->PConnect($setup->get("database.url"), $setup->get("database.user"), $setup->get("database.password"), $bd);
				$db->SetFetchMode($ADODB_FETCH_MODE);
			} elseif ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') && (str_contains((string) gVar("database.engine"), "mssql"))) {
				$dsn = "Driver={SQL Server};Server=" . $setup->get("database.url") . ";Database=" . $setup->get("database.name");
				$db = &ADONewConnection($setup->get("database.engine"));
				$db->Connect($dsn, $setup->get("database.user"), $setup->get("database.password"));
				$db->SetFetchMode($ADODB_FETCH_MODE);
			} else {
				if (!str_starts_with((string) gVar("database.engine"), "mysql")) {
					$db->SetFetchMode($ADODB_FETCH_MODE);
					$transaction = gD_NOTRANS;
				}
				$db->Connect($setup->get("database.url"), $setup->get("database.user"), $setup->get("database.password"), $bd);
			}

			/* TODO
			 * Ajusta resultado das querys para o charset atual
			 * (só resolvido para o MySQL. Checar demais bancos!
			 */
			if (
				($setup->get("database.charset") == "UTF-8")
				&& (str_starts_with((string) $setup->get("database.engine"), "mysql"))
			) {
				$db->Execute("SET NAMES 'utf8'");
				$db->Execute('SET character_set_connection=utf8');
				$db->Execute('SET character_set_client=utf8');
				$db->Execute('SET character_set_results=utf8');
			}
			//$db->SetFetchMode($ADODB_FETCH_MODE);
		}

		$gError = "";
		if (($db->ErrorMsg() != "") && (!str_starts_with(trim((string) $db->ErrorMsg()), "Changed database context to"))) {
			//$sql="\n\n".formatQuery($sqlOriginal)."\n";
			gLog("SQL (Error): $bd - " . $db->ErrorMsg() . $sql, LOG_ERROR);
			$gError = gE_DATABASE . " (" . $db->ErrorMsg() . ")";
		} else {
			if ($transaction == gD_BEGINTRANS) {
				$db->StartTrans();
			}

			//$rs = $db->Execute($sql) or $gError=gE_DATABASEQUERY;
			if ($ini > -1) {
				$rs = $db->SelectLimit($sql, $qtd, $ini);
			} else {
				$rs = $db->Execute($sql);
			}

			if ((gVar("database.type") == "oci8") && ($transaction == gD_NOTRANS)) {
				$db->Execute("COMMIT;");
			}

			if ($transaction == gD_ENDTRANS) {
				$db->CompleteTrans();
			}

			if (!$rs) {
				$gError = $db->ErrorMsg();
				gLog("SQL (Error): $bd - " . $gError . "\t[ $sql ]", LOG_ERROR);
				$sql = formatQuery($sqlOriginal);
				$sqlf = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", nl2br((string) $sql));
				$sqlf = str_replace("SELECT", "<b>SELECT</b>", $sqlf);
				$sqlf = str_replace("DELETE", "<b>DELETE</b>", $sqlf);
				$sqlf = str_replace("UPDATE", "<b>UPDATE</b>", $sqlf);
				$sqlf = str_replace("INSERT", "<b>INSERT</b>", $sqlf);
				$sqlf = str_replace("FROM", "<b>FROM</b>", $sqlf);
				$sqlf = str_replace("WHERE", "<b>WHERE</b>", $sqlf);
				$sqlf = str_replace("GROUP", "<b>GROUP</b>", $sqlf);
				$sqlf = str_replace("ORDER", "<b>ORDER</b>", $sqlf);
				if ($_SESSION['usrId'] != '') {
					echo "
						<!DOCTYPE html>
							<html>
							<head>
							<title>" . gVar("global.site") . "</title>
							<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
							<link href='https://fonts.googleapis.com/css?family=Cabin' rel='stylesheet' type='text/css'>
							<link href='https://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'>
							<link href='https://fonts.googleapis.com/css?family=Josefin+Sans' rel='stylesheet' type='text/css'>
							<link href='https://fonts.googleapis.com/css?family=Dancing+Script' rel='stylesheet' type='text/css'>
							<link href='" . $http_css . "/styleWeb.css' rel='stylesheet' type='text/css'>
							<link href='" . $http_system_css . "/icons.css' rel='stylesheet' type='text/css'></link>
							</head>
							<body>
							<span class='g-msg-title' style='padding: 4px; '>" . gT("&nbsp;Erro de banco de dados") . "</span>
							<span class='g-msg-subtitle'style='padding: 4px; '>" . gT("&nbsp;Verifique se as informações digitadas estão corretas.") . "</span>
					";

					if ($_SESSION['appDevel'] > 0) {
						echo "&nbsp;<span class='g-msg-error' style='padding: 4px; '>" . $gError . "</span>";
						echo "<div style='padding: 14px; text-align: left; z-index:100; width:80%;  color: black; '><br><br><p style='text-align: left'>" . $sqlf . "</p></div></acronym>";
					}
					echo "</body></html>";
				} else {
					echo "Tempo sem atividade alcançado.<br>Sua sessão expirou. Faça o login novamente.";
				}

				exit;

			} elseif ($gDebug > 0) {
				gLog("SQL($bd-$transaction):\t" . $sql);
			}
		}
		//$setup->set("database.db",$bd);
		return $rs;
	}


	/**
	 * Gera um "recordset" com a configuração padrão do banco de dados, com um retorno limitado de registros
	 * @author Giuliano
	 * @param String $arg Query
	 * @param String $bd Banco de dados (opcional se for utilizado o padrão definido no arquivo de  configuração)
	 * @param int $fetch Modo do "fetch" (verifique a documentação do ADODb)
	 * @param int $ini	Registro inicial
	 * @param int $qtd	Quantidade de registros
	 * @return recordset
	 */
	public static function runLimit($sql, $ini = 1, $qtd = 9999999, $fetch = 0)
	{
		global $gError, $setup;

		$gDebug = $setup->get("global.debug");

		$sqlOriginal = $sql;
		$sql = sql2idd($sql);

		$bd = gVar("database.name");
		//$sql=func_get_arg($arg);
		if ($fetch == 0) {
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		} else {
			$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		}

		$db = ADONewConnection($setup->get("database.engine"));

		if (gVar("database.engine") == "oci8") {
			$db->NLS_LANG = "BRAZIL_BRAZILIAN PORTUGUESE.WE8ISO8859P1";
			//$db->NLS_LANG="AMERICAN_AMERICA.WE8ISO8859P1";
			//$db->NLS_LANG="BRAZIL_BRAZILIAN PORTUGUESE.AL24UTFFSS";
			$db->NLS_DATE_FORMAT = 'YYYY-MM-DD';
			$db->NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS';
			$db->PConnect($setup->get("database.url"), $setup->get("database.user"), $setup->get("database.password"), $bd);
			$db->SetFetchMode($ADODB_FETCH_MODE);
		} elseif ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') && (str_contains((string) $setup->get("database.engine"), "mssql"))) {
			$dsn = "Driver={SQL Server};Server=" . $setup->get("database.url") . ";Database=" . $setup->get("database.name");
			$db = &ADONewConnection($setup->get("database.engine"));
			$db->Connect($dsn, $setup->get("database.user"), $setup->get("database.password"));
			$db->SetFetchMode($ADODB_FETCH_MODE);
		} else {
			if (!str_starts_with((string) gVar("database.engine"), "mysql")) {
				$db->SetFetchMode($ADODB_FETCH_MODE);
				$transaction = gD_NOTRANS;
			}
			$db->PConnect($setup->get("database.url"), $setup->get("database.user"), $setup->get("database.password"), $bd);
		}

		/* TODO
		 * Ajusta resultado das querys para o charset atual
		 * (só resolvido para o MySQL. Checar demais bancos!
		 */
		// if (
		// 	(gVar("global.charset") == "UTF-8")
		// 	&& (str_starts_with((string) $setup->get("database.engine"), "mysql"))
		// ) {
			/*
			  $db->Execute("SET NAMES 'utf8'");
			  $db->Execute('SET character_set_connection=utf8');
			  $db->Execute('SET character_set_client=utf8');
			  $db->Execute('SET character_set_results=utf8');
			 */
		// }
		$gError = "";
		if (
			($db->ErrorMsg() != "")
			&& (!str_starts_with((string) $db->ErrorMsg(), "Changed database context to "))
		) {
			if ($gDebug > 0) {
				gLog("SQL (Error): " . $db->ErrorMsg() . "\n" . $sql, LOG_ERROR);
			}
			$gError = gE_DATABASE . " (" . $db->ErrorMsg() . ")";
		} else {
			if ($gDebug > 0) {
				gLog("SQLim($bd,$ini,$qtd):\t" . $sqlOriginal);
			}
			$rs = $db->SelectLimit($sql, $qtd, $ini) || ($gError = gE_DATABASEQUERY);
		}

		return $rs;
	}


	/**
	 * Retorna com o valor de um campo da tabela em referência, tendo como índice o campo id
	 * @author Giuliano
	 * @param String $table Nome da tabela
	 * @param int $id Valor do campo id
	 * @param Variable $field Pode ser o número do campo da tabela ou seu nome
	 * @return String valor do campo
	 */
	public function fieldById($table, $id, $field = 2)
	{
		global $gLastTransaction;
		$r = "";
		if (!is_null($id)) {
			if (intval($field) > 0) {
				$sql = "Select * from $table where id=$id";
				$rs = gQuery($sql, gD_DEFAULT, 0, $gLastTransaction);
				$r = $rs->EOF ? gLng("includes.gselect.long") : $rs->fields[$field];
			} else {
				$sql = "Select $field from $table where id=$id";
				$rs = gQuery($sql, gD_DEFAULT, 0, $gLastTransaction);
				$r = $rs->EOF ? gT("* Indiferente") : $rs->fields[$field];
			}
		}

		return($r);
	}


	/**
	 * Retorna campos encontrados em uma Query
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query
	 * @return array $var Campos e parâmetros
	 */
	public function parseQueryFields($sql)
	{
		$sql = str_ireplace("distinct ", "", $sql);
		$sql = preg_replace("/[ \t\n\r][Aa][Ss][ \t\n\r]/", " AS ", $sql);
		$par = 0;
		for ($a = 0; $a < strlen($sql); $a++) {
			if ($sql[$a] === "(") {
				$par++;
			}

			if ($sql[$a] === ")") {
				$par--;
			}

			if (($par > 0) && ($sql[$a] === ",")) {
				$sql[$a] = "^";
			}
		}

		$fld = explode(",", $sql);
		$fld = array_map("trim", $fld);
		// campo
		// campo outro
		// campo as outro
		// formula outro
		// formula as campo

		$defaultTable = $this->tables[0]["alias"];
		foreach ($fld as $tdata) {
			$tdata = str_replace("^", ",", $tdata);
			$prox = 0;
			if ($tdata != "*") {
				$tmp = explode(" ", $tdata);
				$ult = $tmp[count($tmp) - 1];
				if (!str_contains($ult, ".")) {
					$campo['table'] = $defaultTable;
					$campo['name'] = $ult;
					$campo['alias'] = $ult;
					$campo['fieldLabel'] = gField2String($ult);
				} else {
					$name = explode(".", $ult);
					$campo['table'] = $name[0];
					$campo['name'] = $name[1];
					$campo['alias'] = $name[1];
					$campo['fieldLabel'] = gField2String($name[1]);
				}
				$this->fields[] = $campo;
			}
		}

		return ($this->fields);
	}


	/**
	 * Retorna tabelas encontradas em uma Query
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query (somente conteúdo entre o FROM e outra seção
	 * @return array $var Tabelas e parâmetros
	 */
	public function parseQueryTables($sql)
	{
		$sql = preg_replace("/[ \t\n\r][Ll][Ee][Ff][Tt] [Jj][Oo][Ii][Nn][ \t\n\r]/", " LEFT JOIN ", $sql);
		$sql = preg_replace("/[ \t\n\r][Rr][Ii][Gg][Hh][Tt] [Jj][Oo][Ii][Nn][ \t\n\r]/", " RIGHT JOIN ", (string) $sql);
		$sql = preg_replace("/[ \t\n\r][Ii][Nn][Nn][Ee][Rr] [Jj][Oo][Ii][Nn][ \t\n\r]/", " INNER JOIN ", (string) $sql);
		$sql = preg_replace("/[ \t\n\r][Jj][Oo][Ii][Nn][ \t\n\r]/", " JOIN ", (string) $sql);
		$sql = preg_replace("/[ \t\n\r][Oo][Nn][ \t\n\r]/", " ON ", (string) $sql);
		$t = explode(" JOIN ", (string) $sql);
		$nextJoin = "";
		foreach ($t as $tdata) {
			$tdata = str_replace("  ", " ", $tdata);
			$table = "";
			//$table['data']=$tdata;
			if ($nextJoin !== "") {
				$table['join'] = 'left';
				$nextJoin = "";
			}

			if (str_contains($tdata, " LEFT")) {
				$nextJoin = "left";
				$tdata = str_replace(" LEFT", "", $tdata);
			}

			if (str_contains($tdata, " RIGHT")) {
				$nextJoin = "right";
				$table['join'] = 'right';
				$tdata = str_replace(" RIGHT", "", $tdata);
			}

			if (str_contains($tdata, " INNER")) {
				$nextJoin = "inner";
				$tdata = str_replace(" INNER", "", $tdata);
			}

			if (str_contains($tdata, " ON ")) {
				$table['on'] = substr($tdata, strpos($tdata, " ON ") + 4);
				$tdata = substr($tdata, 0, strpos($tdata, " ON "));
			}

			//$tdata=str_replace(" l"," ",$tdata);
			$prox = 0;
			$tdata = str_replace("\n", " ", $tdata);
			$tmp = explode(" ", $tdata);
			$table['name'] = str_replace("\n", "", trim($tmp[$prox]));
			$prox++;
			if (strtolower($tmp[$prox]) === "as") {
				$prox++;
				$table['alias'] = str_replace("\n", "", trim($tmp[$prox]));
				$table['fieldLabel'] = $tmp[$prox];
			} elseif ($tmp[$prox] !== "") {
				$table['alias'] = str_replace("\n", "", trim($tmp[$prox]));
				$table['fieldLabel'] = $tmp[$prox];
			} else {
				$table['alias'] = str_replace("\n", "", trim($table['name']));
				$table['fieldLabel'] = $table['name'];
			}

			$this->tables[] = $table;
		}

		return($this->tables);
	}


	/**
	 * Retorna seções encontradas em uma Query
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query completa
	 * @return array $var Seções e conteúdos
	 */
	public function parseQuerySections($sql)
	{
		$posSec[] = ["SELECT ", "^[ \t\n\r]?[Ss][Ee][Ll][Ee][Cc][Tt][ \t\n\r]"];
		$posSec[] = ["UPDATE ", "^[ \t\n\r]?[Uu][Pp][Dd][Aa][Tt][Ee][ \t\n\r]"];
		$posSec[] = ["DELETE ", "^[ \t\n\r]?[Dd][Ee][Ll][Ee][Tt][Ee][ \t\n\r]"];
		$posSec[] = ["INSERT ", "^[ \t\n\r]?[Ii][Nn][Ss][Ee][Rr][Tt][ \t\n\r]"];
		$posSec[] = ["REPLACE ", "^[ \t\n\r]?[Rr][Ee][Pp][Ll][Aa][Cc][Ee][ \t\n\r]"];
		$posSec[] = [" FROM ", "[ \t\n\r][Ff][Rr][Oo][Mm][ \t\n\r]"];
		$posSec[] = [" WHERE ", "[ \t\n\r][Ww][Hh][Ee][Rr][Ee][ \t\n\r]"];
		$posSec[] = [" GROUP BY ", "[ \t\n\r][Gg][Rr][Oo][Uu][Pp] [Bb][Yy][ \t\n\r]"];
		$posSec[] = [" ORDER BY ", "[ \t\n\r][Oo][Rr][Dd][Ee][Rr] [Bb][Yy][ \t\n\r]"];
		$posSec[] = [" HAVING ", "[ \t\n\r][Hh][Aa][Vv][Ii][Nn][Gg][ \t\n\r]"];
		$posSec[] = [" LIMIT ", "[ \t\n\r][Ll][Ii][Mm][Ii][Tt][ \t\n\r]"];

		foreach ($posSec as $ereg) {
			$sql = preg_replace('/'.$ereg[1].'/', $ereg[0], (string) $sql);
		}

		$this->query = $sql;
		$this->sections = parseQuerySections($sql);
		return ($this->sections);
	}


	public function parseQuery($sql): void
	{
		$this->md5 = md5((string) $sql);
		$this->parseQuerySections($sql);

		if (isset($this->sections['select'])) {
			$this->parseQueryTables($this->sections['from']);
			$this->parseQueryFields($this->sections['select']);
		}

	}


	public function cachePush(): void
	{
		$cache = "";
		$cache["sections"] = $this->sections;
		$cache["tables"] = $this->tables;
		$cache["fields"] = $this->fields;
		$cache["associativeFields"] = $this->associativeFields;
		$cache["dictionary"] = $this->dictionary;
		$cache["querys"] = $this->querys;
		$cache["query"] = $this->query;
		$cache["md5"] = $this->md5;
		$_SESSION['_cacheDB'][] = $cache;
	}


	public function cachePop($sql)
	{
		$sai = false;
		$md5 = md5((string) $sql);
		foreach ($_SESSION['_cacheDB'] as $cache) {
			if ($cache['md5'] == $md5) {
				$this->sections = $cache["sections"];
				$this->tables = $cache["tables"];
				$this->fields = $cache["fields"];
				$this->associativeFields = $cache["associativeFields"];
				$this->dictionary = $cache["dictionary"];
				$this->query = $cache["query"];
				$this->md5 = $cache["md5"];
				$sai = true;
				break;
			}
		}

		return($sai);
	}


	/**
	 * Verifica campos da Query e retorna mais informações sobre eles
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query
	 */
	public function parseQueryOnServer($sql = "", $needData = true): void
	{
		// Cache
		$leuDoCache = $this->cachePop($sql);
		$sql = gQueryReplaceMacros($sql);
		if ($leuDoCache) {
			$sql = "SELECT " . $this->sections['select'] . " FROM " . $this->sections['from'];
			if ($this->sections['where'] != "") {
				$sql .= " WHERE " . $this->sections['where'];
			}

			if ($this->sections['group by'] != "") {
				$sql .= " GROUP BY " . $this->sections['group by'];
			}

			if ($needData) {
				$sql = $this->addLimitToQuery($sql, 1);
				$rs  = $this->run($sql, 1);

				foreach ($this->fields as $ind => $value) {
					$this->fields[$ind]['value'] = $rs->fields[$ind];
				}
			}

		} else {
			if ($sql != "") {
				$this->parseQuery($sql);
			}

			if (isset($this->sections['select'])) {
				$sql = "SELECT " . $this->sections['select'] . " FROM " . $this->sections['from'];
				if ($this->sections['where'] != "") {
					$sql .= " WHERE " . $this->sections['where'];
				}

				if ($this->sections['group by'] != "") {
					$sql .= " GROUP BY " . $this->sections['group by'];
				}

				$sql = $this->addLimitToQuery($sql, 1);
				$rs  = $this->run($sql, 1);
				$ttlFields = $rs->FieldCount();
				$newFields = "";
				$tables = $this->tables;
				$fldTable = $tables[0]["name"];
				for ($g = 0; $g < $ttlFields; $g++) {
					$fld = $rs->FetchField($g);
					$fldName  = $fld->name;
					$fldValue = $rs->fields[$g];
					//gLog("==== $fldName = ".$rs->MetaType($fld));
					$fldType = $this->fieldType($fld, $rs->MetaType($fld));
					$fldLength = $fld->max_length;
					//if (($fldType=="I") && ($fldLength==4)) $fldType="L";
					if (!str_starts_with((string) gVar("database.engine"), "mysql")) {
						if (($fld->type == 'char') && ($fld->max_length == 10)) {
							$fldType = "D";
						}

						if ($fld->type == 'datetime') {
							$fldType = "T";
						}

						if ($fld->type == 'bigint') {
							$fldType = "I";
						}

						if ($fld->type == 'bigint identity') {
							$fldType = "R";
						}

						if (
							($fldType == 'C')
							&& ($fld->max_length == 1)
							&& (($rstmp->fields[$fld->name] == " ")
							|| ($rstmp->fields[$fld->name] == "1")
							|| ($rstmp->fields[$fld->name] == "0"))
						) {
							$fldType = "L";
						}
					}

					//if ($fldType)
					$fez = false;
     				$counter = count($this->fields);
					for ($a = 0; $a < $counter; $a++) {
						$f = $this->fields[$a];
						if ($f['alias'] == $fldName) {
							$f['value'] = $fldValue;
							$f['length'] = $fldLength;
							$f['type'] = $this->types[$fldType];
							$f['align'] = gCheckAlignByType($this->types[$fldType]);
							$this->fields[$a] = $f;
							$this->associativeFields[$fldName] = $f;
							$a = count($this->fields);
							$fez = true;
						}
					}

					if (!$fez) {
						$fld = "";
						$fld['table'] = $fldTable;
						$fld['name'] = $fldName;
						$fld['alias'] = $fldName;
						$fld['fieldLabel'] = gField2String($fldName);
						$fld['length'] = $fldLength;
						$fld['value'] = $fldValue;
						$fld['type'] = $this->types[$fldType];
						$fld['align'] = gCheckAlignByType($this->types[$fldType]);

						$this->fields[] = $fld;
						$this->associativeFields[$fldName] = $fld;
					}
				}

			}

			$this->cachePush($sql);
		}
	}


	/**
	 * Retorna tipo de campo reajustado de acordo com o SGBD atual
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $fld Tipo de campo no formato do ADOdb
	 * @return string $fldType Tipo de campo
	 */
	public function fieldType($fld, $default): string
	{
		$fldType = $default;
		if (!str_starts_with((string) gVar("database.engine"), "mysql")) {
			if (($fld->type == 'char') && ($fld->max_length == 10)) {
				$fldType = "D";
			}

			if ($fld->type == 'datetime') {
				$fldType = "T";
			}

			if ($fld->type == 'bigint') {
				$fldType = "I";
			}

			if ($fld->type == 'bigint identity') {
				$fldType = "R";
			}
		}

		//if (($fldType=='C') && ($fld->max_length==1)) $fldType="L";
		if (($fldType == 'C') && ($fld->max_length == 5)) {
			$fldType = "M";
		}

		if (($fldType == 'I') && ($fld->max_length <= 3)) {
			$fldType = "L";
		}

		$tmp['senha'] 		= "W";
		$tmp['password'] 	= "W";
		$tmp['plate'] 		= "A";
		$tmp['placa'] 		= "A";
		$tmp['email'] 		= "E";
		$tmp['url']   		= "U";
		$tmp['website'] 	= "U";
		$tmp['site'] 		= "U";
		$tmp['cpf']  		= "F";
		$tmp['cnpj'] 		= "J";
		$tmp['ip']   		= "P";
		$tmp['container'] 	= "H";
		$tmp['ncm'] 		= "O";

		if ($tmp[$fld->name] !== "") {
			$fldType = $tmp[$fld->name];
		}

		if (str_starts_with($fld->name, "container")) {
			$fldType = $tmp['container'];
		}

		if (str_starts_with($fld->name, "placa")) {
			return $tmp['placa'];
		}

		return $fldType;
	}


	/**
	 * Formata Query para saída na tela
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query
	 */
	public function formatQuery($sql = "", $html = true): string
	{
		$sai = "";
		if ($sql != "") {
			$this->parseQuerySections($sql);
		}

		if ($html) {
			$sai = "<div style='text-align: left; padding: 6px'>";
			foreach ($this->sections as $key => $value) {
				$sai .= "<b>" . strtoupper((string) $key) . "</b><br>$value<br>";
			}

			$sai .= "</div><br>";
		} else {
			foreach ($this->sections as $key => $value) {
				$value = str_ireplace("LEFT JOIN", "\n\tLEFT JOIN", $value);
				$value = str_ireplace("RIGHT JOIN", "\n\tRIGHT JOIN", $value);
				$value = str_ireplace("INNER JOIN", "\n\tINNER JOIN", $value);
				$sai .= strtoupper((string) $key) . " \n\t$value \n";
			}
		}

		return($sai);
	}


	/**
	 * Mostra Query na tela
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $sql Query
	 */
	public function echoQuery($sql = ""): void
	{
		echo $this->formatQuery($sql);
	}


	public function addLimitToQuery($sql, $start, $max = 0)
	{
		global $setup;

		if (str_contains((string) $setup->get("database.engine"), "mssql")) {
			if ($max == 0) {
				$max = $start;
			}

			if (!str_contains(strtoupper((string) $sql), " TOP ")) {
				$sql = "SELECT TOP $max " . substr((string) $sql, 7);
			}
		} else {
			if ($max == 0) {
				$sql .= " LIMIT $start";
			} else {
				$sql .= " LIMIT $start,$max";
			}
		}
		return ($sql);
	}


	/**
	 * Adiciona tabela ao dicionário de dados
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $json Conteúdo:
	 *    name:  nome da tabela
	 *    label: descrição da tabela
	 * 		query: query select
	 */
	public function addQuery($sql): void
	{
		$this->querys[] = $sql;
	}


	/**
	 * Adiciona tabela ao dicionário de dados
	 * @author	Giuliano
	 * @version	2009-10-13
	 * @param string $json Conteúdo:
	 *    name:  nome da tabela
	 *    label: descrição da tabela
	 * 		query: query select
	 */
	public function addTable($json): void
	{
		$mtz = cssDecode($json);
		$this->tableDict[$mtz['name']] = $mtz;
	}


	/**
	 *  Retorna dados referente a tabela mencionada
	 * @author Giuliano
	 * @param String $campo Nome da tabela
	 * @return array $sai Dados
	 */
	public function getTable($name, $attrib = "")
	{
		$table = $this->tableDict[$name];
		if ($attrib != "") {
			return $table[$attrib];
		}

		return ($table);
	}


	/**
	 * Retorna dados referente ao campo mencionado
	 * @author Giuliano
	 * @param String $campo Nome do campo
	 * @return array $sai Dados
	 */
	public function getField($campo)
	{
		$sai = "";
		$flds = $this->fields;
		foreach ($flds as $fld) {
			if ($fld['alias'] == $campo) {
				$sai = $fld;
				break;
			}
		}
		return($sai);
	}
}


function gExpire(): never
{
	global $http, $http_img, $gPathDefault;

	require_once $gPathDefault . "gOutput.php";

	gSessionSave("usr_id", 0);
	gSessionSave("idd", 0);
	$out = new gOutput();
	$out->gBegin();
	?>
	<script language='javascript'>
		if (self.parent.frames.length >= 2) {
			self.parent.location = document.location;
		}
	</script>
	<?php
	$out->gTableBegin(gT_MEDIUM, true);
	$out->gTableRowBegin();
	$out->gTableColBegin("align='center'");
	$out->gOut("<br><img border='0' src='$http_img/logo_app.jpg'><br><br>");

	$out->gMsgTitle(gT("session_expired.short"));
	$out->gMsg(gT("session_expired.long"));
	$out->gBr();
	$out->gMsg("<a href='http://" . gVar("global.url") . "'>" . gVar("global.url") . "</a>");
	$out->gTableColEnd();
	$out->gTableRowEnd();
	$out->gTableEnd();
	$out->gEnd();
	session_write_close();

	exit();
}


/**
 * Cria um array de duas dimensões contendo campos e valores de uma tabela do banco
 * @author Giuliano Nascimento
 * @version 2.0
 * @param string $query query de banco de dados
 * @param string $prefixo prefixo para os nomes dos campos
 * @param string $metodo sendo 0=nomes dos campos e seus valores no primeiro registro e 1=registros (1 elemento é o nome e 2 o valor)
 * @return array
 */
function gQuery2Array($prefixo = '', $query, $metodo = 0)
{
	global $gLastTransaction;

	$rstmp = gQuery($query, gD_DEFAULT, 0, $gLastTransaction);
	$sai = "";
	if ($prefixo != "") {
		$prefixo .= ".";
	}

	if (!$rstmp->EOF) {
		if ($metodo == 0) {
			$ttlf = $rstmp->FieldCount();
			for ($g_t = 0; $g_t < $ttlf; $g_t++) {
				$fld = $rstmp->FetchField($g_t);
				$sai[$prefixo . strtolower($fld->name)] = $rstmp->fields[$g_t];
			}
		} else {
			while(!$rstmp->EOF) {
				$sai[$prefixo . strtolower((string) $rstmp->fields[0])] = $rstmp->fields[1];
				$rstmp->MoveNext();
			}
		}
	}

	return $sai;
}
/*
  $usr_id=$_SESSION['usr_id'];

  $gPermissions="x"; // Select, Insert, Update, Delete
  $thispage=$_SERVER["PHP_SELF"];

  if (($gPageSecurity) && (strpos($thispage,"index.php")===false) && (strpos($thispage,"menu.php")===false) && (strpos($thispage,gVar("page.index"))===false) && (strpos($thispage,gVar("page.login"))===false) && (strpos($thispage,gVar("page.logout"))===false) && (strpos($thispage, "online")>0) )
  {
  $sql="delete from geral_online where data_atualizacao < DATE_SUB(now(), INTERVAL ".gVar("global.timeout")." MINUTE)";
  gQuery($sql);
  $sql="select id from geral_online where id_geral_pessoas=$usr_id order by id desc";
  $rs=gQuery($sql);

  if (!$rs->EOF)
  {
  // Atualiza informação de acessos simultâneos (17-01-06)
  $sql="update geral_online set data_atualizacao=now() where id_geral_pessoas=$usr_id";
  gQuery($sql);

  if (($usr_id<=3) || ((isset($usr_idd)) && ($usr_idd==0))) // Se usuário for "root" ou cliente Alitem
  {
  $faz=true;
  if ($permissoes=="")
  $gPermissions="SIUD";
  else
  $gPermissions=$permissoes;
  } else
  {

  // Testa para ver se o usuário pode acessar esta página
  // e qual o tipo de acesso permitido (leitura, escrita, apagar, editar)
  $usr_id=gSessionLoad("usr_id");
  if (strpos($thispage,".php")>0)
  $thispage=substr($thispage,0,strpos($thispage,".php"));
  if (strpos($thispage,"_tnl")>0)
  $thispage=substr($thispage,0,strpos($thispage,"_tnl"));
  if (strpos($thispage,"_frm")>0)
  $thispage=substr($thispage,0,strpos($thispage,"_frm"));
  if (strpos($thispage,"_lst")>0)
  $thispage=substr($thispage,0,strpos($thispage,"_lst"));
  if (strpos($thispage,"_flt")>0)
  $thispage=substr($thispage,0,strpos($thispage,"_flt"));
  $dirs=explode("/",$thispage);
  $cnt=count($dirs);
  if ($cnt>1)
  {
  $thispage=$dirs[$cnt-2]."/".$dirs[$cnt-1];
  }
  if (($usr_id<>1) && ($cnt>4))
  {
  $faz=true;
  $sql="Select * from geral_links where link like '%$thispage%'";
  $S="";$I="";$U="";$D="";

  $rsts=gQuery($sql);
  while (!$rsts->EOF)
  {
  $dbsigla=$rsts->fields['sigla'];
  $dbsigla=substr($dbsigla,0,9);
  $sql="Select geral_links_permissoes.* from geral_links,geral_links_permissoes where ";
  $sql.=" geral_links.sigla=geral_links_permissoes.sigla_geral_links and sigla like '$dbsigla%'";

  $rst=gQuery($sql);
  if (!$rst->EOF)
  {

  if ($usr_id>0)
  {
  $usr_setores=gSessionLoad("usr_setores");
  $usr_funcoes=gSessionLoad("usr_funcoes");
  while (!$rst->EOF)
  {
  if (($rst->fields['id_pes_setores']==0) && ($rst->fields['id_pes_funcoes']==0))
  {
  $faz=true;
  if ($rst->fields['ler']==1) $S="S";
  if ($rst->fields['inserir']==1) $I="I";
  if ($rst->fields['editar']==1) $U="U";
  if ($rst->fields['remover']==1) $D="D";
  } elseif (($rst->fields['id_pes_setores']<>0) && ($rst->fields['id_pes_funcoes']<>0))
  {
  foreach ($usr_setores as $usr_setor)
  {
  if ($rst->fields['id_pes_setores']==$usr_setor[0])
  {
  $faz=true;
  if ($rst->fields['ler']==1) $S="S";
  if ($rst->fields['inserir']==1) $I="I";
  if ($rst->fields['editar']==1) $U="U";
  if ($rst->fields['remover']==1) $D="D";
  }
  }
  if ($faz)
  {
  $faz=false;
  $gPermissions="";
  foreach ($usr_funcoes as $usr_funcao)
  {
  if ($rst->fields['id_pes_funcoes']==$usr_funcao[0])
  {
  $faz=true;
  if ($rst->fields['ler']==1) $S="S";
  if ($rst->fields['inserir']==1) $I="I";
  if ($rst->fields['editar']==1) $U="U";
  if ($rst->fields['remover']==1) $D="D";
  }
  }
  }
  } else
  {
  foreach ($usr_setores as $usr_setor)
  {

  if (($rst->fields['id_pes_setores']==$usr_setor[0]) && ($rst->fields['id_pes_funcoes']==0))
  {
  $faz=true;
  if ($rst->fields['ler']==1) $S="S";
  if ($rst->fields['inserir']==1) $I="I";
  if ($rst->fields['editar']==1) $U="U";
  if ($rst->fields['remover']==1) $D="D";
  }
  //gLog("--- $sigla $usr_nome $usr_setor[0] - $usr_setor[1] = ".$rst->fields['id_pes_setores']." [$S $I $U $D]");
  }
  foreach ($usr_funcoes as $usr_funcao)
  {
  if (($rst->fields['id_pes_funcoes']==$usr_funcao[0]) && ($rst->fields['id_pes_setores']==0))
  {
  $faz=true;
  if ($rst->fields['ler']==1) $S="S";
  if ($rst->fields['inserir']==1) $I="I";
  if ($rst->fields['editar']==1) $U="U";
  if ($rst->fields['remover']==1) $D="D";
  }
  }
  }
  $rst->MoveNext();
  }
  }
  }
  $rsts->MoveNext();
  }
  if ($permissoes<>"")
  {
  if (strpos($permissoes,$S)===false) { $S="";}
  else
  {
  if (strpos($permissoes,"L")>0) $S.="L";
  if (strpos($permissoes,"M")>0) $S.="M";
  }
  if (strpos($permissoes,$I)===false) $I="";
  if (strpos($permissoes,$U)===false) $U="";
  if (strpos($permissoes,$D)===false) $D="";
  }
  $gPermissions=$S.$I.$U.$D;
  } else
  {
  $faz=true;
  }
  }
  } else
  {
  $faz=false;
  }
  if (!$faz)
  {
  gExpire();
  }

  }

 */


/**
 * Classe para criação do cache do BD
 * @package	gDB
 * @author	Giuliano Nascimento <giusoft@hotmail.com>
 * @version	4.0 01-12-2013 10:50
 */
class gDBCache
{
	public $de;
 	public $gDB = '';
	public $fileName = '';
	private $enabled = true;
	public $db;

	public function __construct($enabled = true)
	{
		$this->enabled = $enabled;
		if ($this->enabled) {
			global $gPath;

			if (gVar("global.tmp") != "") {
				$pre = $gPath.'/'.gVar("global.tmp") . '/' . str_replace(" ", "_", gVar("global.site")) . '-';
			} else {
				$pre = sys_get_temp_dir() . '/' . str_replace(" ", "_", gVar("global.site")) . '-';
			}

			$pre = str_replace('//', '/', $pre);
			$this->fileName = $pre."db.php";
		}
	}


	public function parseTables(): void
	{
		global $sp;

		$this->gDB = [];
		$this->db = new gDatabase("{mode: pdo}");
		foreach ($sp as $tableName => $sql) {
			$this->gDB[$tableName]['sql']=$sql;

			if (stripos((string) $sql, 'LIMIT ') === false) {
				$sql.=' LIMIT 0';
			}

			$sql=gQueryReplaceMacros($sql);
			$this->gDB[$tableName]['fields']=$this->db->describe( $sql );
			$this->gDB[$tableName]['sections']=parseQuerySections( $sql );
			$this->gDB[$tableName]['tables']=parseQueryTables( $sql );

		}

	}

	public function create(): void
	{
		global $gDB;

		$this->parseTables();
		if ($this->enabled) {
			$content = "<?\n";
			$content .= "\$gDB='';\n";
			foreach ($this->gDB as $key => $value) {
				$content.="\$gDB['" . $key . "']['sql']    = \"".$value['sql']."\";\n";

				// Campos
				$fields  = $value['fields'];
				$afields = "Array ( \n";
				$af = [];
				foreach ($fields as $fkey => $fvalue) {
					$av = [];
					foreach ($fvalue as $vkey => $vvalue) {
						$av[] = " '$vkey' => \"$vvalue\" ";
					}

					$af[] = "\t\t\t'$fkey' => \n\t\t\t\tArray(\n\t\t\t\t" . implode(", \n\t\t\t\t", $av) . "\n\t\t\t\t) ";
				}

				$afields .= implode(",\n",$af);
				$afields .= ");";
				$content .= "\$gDB['" . $key . "']['fields'] = ".$afields."\n\n";

				// Seções
				$sections = $value['sections'];
				$asections = "Array ( \n";
				$af = '';
				foreach ($sections as $fkey => $fvalue) {
					$av = [];
					foreach ($fvalue as $vkey => $vvalue) {
						$av[] = " '$vkey' => \"$vvalue\" ";
					}
					$af[] = "\t\t\t'$fkey' => \"$fvalue\" ";
				}
				$asections .= implode(",\n",$af);
				$asections .= ");";
				$content .= "\$gDB['" . $key . "']['sections'] = ".$asections."\n\n";

				// Tables
				$tables = $value['tables'];
				$atables = "Array ( \n";
				$af = '';
				foreach ($tables as $fkey => $fvalue) {
					$av = [];
					foreach ($fvalue as $vkey => $vvalue) {
						$av[] = " '$vkey' => \"$vvalue\" ";
					}

					$af[] = "\t\t\t'$fkey' => \n\t\t\t\tArray(\n\t\t\t\t" . implode(", \n\t\t\t\t", $av) . "\n\t\t\t\t) ";
				}

				$atables .= implode(",\n",$af);
				$atables .= ");";
				$content .= "\$gDB['" . $key . "']['tables'] = ".$atables."\n\n";
			}

			$content .= "\n?>";
			unlink($this->de);
			gLog("===> Criando arquivo de cache de BD em ".$this->fileName);
			file_put_contents($this->fileName, $content);
		} else {
			gLog("===> Cache desabilitado");
		}

		$gDB = $this->gDB;
	}


	public function load($force = false): void
	{
		global $gDB, $sp;

		if ($this->enabled) {
			$precisaRecarregar=false;

			if (file_exists($this->fileName) && !$gDB && !$force) {
				include_once $this->fileName;
			}

			if ($force) {
				$precisaRecarregar = true;
			} elseif (count($sp) !== count($gDB)) {
				// Verifica se houve alteração na variável $sp
				$precisaRecarregar = true;
				$nao = [];
				foreach ($sp as $key => $value) {
					if (!isset($gDB[$key])) {
						$nao[] = $key;
					}
				}

				gLog("===> Número de tabelas em SP difere do cache. Não encontrado em gDB: ".implode(', ',$nao));
			} else {
				foreach ($sp as $tableName=>$sql) {
					if (($sql != $gDB[$tableName]['sql']) && (!str_contains((string) $sql,'gfw_'))) {
						gLog("===> Query em SP difere do cache: $tableName\t$sql\t".$gDB[$tableName]['sql']);
						$precisaRecarregar = true;
					}
				}
			}

			if ($precisaRecarregar) {
				$this->create();
			}

		} else {
			$this->create();
		}
	}
}


function gCheckAlignByType($type): string
{
	$align  = "center";
	$aligns = [];
	$aligns['text'] 	 			= 'left';
	$aligns['textarea']  			= 'left';
	$aligns['upperText'] 			= 'left';
	$aligns['lowerText'] 			= 'left';
	$aligns['upperFirstWordText']   = 'left';
	$aligns['upperFirstLetterText'] = 'left';
	$aligns['memo'] 				= 'left';
	$aligns['email'] 				= 'left';
	$aligns['url'] 					= 'left';
	$aligns['ip'] 					= 'left';
	$aligns['combo'] 				= 'left';
	$aligns['number'] 				= 'right';
	$aligns['integer'] 				= 'right';
	$aligns['show'] 				= 'left';
	$aligns['html'] 				= 'left';

	if (isset($aligns[$type]) && ($aligns[$type] !== '' && $aligns[$type] !== '0')) {
		return $aligns[$type];
	}

	return($align);
}


function gLoadDBCache($force = false, $enabled = false): void
{
	$cache = new gDBCache($enabled);

	if (file_exists($cache->fileName)) {
		// Se existir, carrega o cache (verifica se está atualizado antes)
		$cache->load( $force );
	} else {
		// Cria o cache se não existir
		$cache->create();
	}
}


function gQueryReplaceMacros($sql)
{
	if (str_contains((string) $sql,'{')) {
		if ($_SESSION) {
			foreach ($_SESSION as $key => $value) {
				if (str_contains((string) $sql,'{'.$key.'}')) {
					if ($value == '') {
						$sql = str_replace('{'.$key.'}',"''", $sql);
					} else {
						$sql = str_replace('{'.$key.'}',$value, $sql);
					}
				}
			}
			if (str_contains((string) $sql,'{')) {
				$sql = "SELECT 1";
			}
		} else {
			$sql = "SELECT 1";
		}
	}

	return($sql);
}

