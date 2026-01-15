<?php
define("NL", "\n");
define('NFEPHP', "nfephp-3.10/");
define('M', '-          ');

/*
* Pra converter .pfx em .pem:
* openssl pkcs12 -clcerts -in <certificado>.pfx -out <certificado>.pem -nodes
*/

//echo $gPathDefault . NFEPHP . "/libs/NFe/ToolsNFePHP.class.php";exit;
if ($gPathLib != "") {
	include $gPathLib . NFEPHP . "/libs/NFe/ToolsNFePHP.class.php";
	include $gPathLib . NFEPHP . "/libs/NFe/DacceNFePHP.class.php";
	include $gPathLib . NFEPHP . "/libs/NFe/DanfeNFePHP.class.php";
	include $gPathLib . NFEPHP . "/libs/NFe/ConvertNFePHP.class.php";
} else {
	include $gPathDefault . NFEPHP . "/libs/NFe/ToolsNFePHP.class.php";
	include $gPathDefault . NFEPHP . "/libs/NFe/DacceNFePHP.class.php";
	include $gPathDefault . NFEPHP . "/libs/NFe/DanfeNFePHP.class.php";
	include $gPathDefault . NFEPHP . "/libs/NFe/ConvertNFePHP.class.php";
}

if ($ambienteDesenvolvimento = false) {
	function mssql_connect(): bool
	{
		return true;
	}


	function mssql_select_db(): bool
	{
		return true;
	}


	function mssql_query(): bool
	{
		return true;
	}


	function mssql_fetch_assoc(): bool
	{
		return true;
	}
}

class gNFeTools extends ToolsNFePHP
{

	function __construct($defaultCertName = "")
	{
		$defaultCertName["cMunicipio"]=297;
		//$defaultCertName["enableSVC"]=1;
		parent::__construct($defaultCertName);
		$sql = gSQLLimit("SELECT * FROM nfe_operacao ORDER BY id DESC", 1);
		$rs = dbQuery($sql)[0];
		//$rs->fields=$rs;
		if ($rs['modo_operacao'] == "6" || $rs['modo_operacao'] == "7") {
			$this->ativaContingencia();
		}
	}
}

class gDanfe extends DanfeNFePHP {}

class gNFe extends ConvertNFePHP
{
	public $c, $i, $s;
	public $nfetxt, $nfexml, $nfeid, $recibo, $dataRecibo, $protocolo, $numeroNota, $numeroLote,$gTimeZone;
	public $nfsexml;
	public $erros;
	public $tmp, $arqtxt, $arqxml, $arqpd;
	public $id_empresa = 0;
	public $id_operacao = 0;
	public $enableSVC = false;
	public $versao, $schemes, $nfeTools, $protfile;

	private array $tzUFlist = [
		'AC'=>'America/Rio_Branco',
		'AL'=>'America/Sao_Paulo',
		'AM'=>'America/Manaus',
		'AP'=>'America/Sao_Paulo',
		'BA'=>'America/Bahia',
		'CE'=>'America/Fortaleza',
		'DF'=>'America/Sao_Paulo',
		'ES'=>'America/Sao_Paulo',
		'GO'=>'America/Sao_Paulo',
		'MA'=>'America/Sao_Paulo',
		'MG'=>'America/Sao_Paulo',
		'MS'=>'America/Campo_Grande',
		'MT'=>'America/Cuiaba',
		'PA'=>'America/Belem',
		'PB'=>'America/Sao_Paulo',
		'PE'=>'America/Recife',
		'PI'=>'America/Sao_Paulo',
		'PR'=>'America/Sao_Paulo',
		'RJ'=>'America/Sao_Paulo',
		'RN'=>'America/Sao_Paulo',
		'RO'=>'America/Porto_Velho',
		'RR'=>'America/Boa_Vista',
		'RS'=>'America/Sao_Paulo',
		'SC'=>'America/Sao_Paulo',
		'SE'=>'America/Sao_Paulo',
		'SP'=>'America/Sao_Paulo',
		'TO'=>'America/Sao_Paulo'
	];

	function __construct($tipo = "nfe", $verificaStatus = true,$versao='4.00')
	{
		date_default_timezone_set("America/Bahia");
		$this->tmp = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? "\\inetpub\\wwwroot\\" : "/tmp/";
		$this->arqtxt = $this->tmp . "nfe.txt";
		$this->arqxml = $this->tmp;
		$this->arqpdf = $this->tmp . "nfe.pdf";

		if ($tipo == "nfe") {
			$this->schemes = "PL_008e";
		} else {
			$this->schemes = "NFSE_BA";
			$this->c['naturezaOperacao'] = "5";
		}

		$this->versao = "3.10";
		if ($versao == '4.00') {
			//$this->schemes = "PL_009_V4";
			$this->schemes = "PL_009c_V4";
			$this->versao='4.00';
		}

		// Valores default dos campos da NFe
		$this->c['modelo'] = '55';
		$this->c['senhaCertificado'] = gVar("nfse.senha") != "" ? gVar("nfse.senha") : gVar("nfe.senha");
		$this->c['versao'] = $this->versao;

		if ($tipo == "nfe") {
			$this->c['ambiente'] = gVar('nfe.ambiente') == "1" ? "1" : "2";
		} elseif (gVar('nfse.ambiente') == "1") {
			$this->c['ambiente'] = "1";
		} else {
			$this->c['ambiente'] = "2";
		}

		$this->gTimeZone = date("P");
		$this->c['municipio'] = gVar('nfe.municipio');
		$this->c['regime'] = gVar("nfe.regime") == "" ? "3" : gVar("nfe.regime"); // 1) Simples 2) Simples excesso limite 3) Normal
		$this->c['propriaEnderecoIbgeEstado'] = gVar('nfe.cUF');
		$this->c['propriaEnderecoIbgeMunicipio'] = gVar('nfe.cUF');
		$this->c['operacao'] = gVar('nfe.operacao') != "" ? gVar('nfe.operacao') : "Retorno de Mercadoria"; // venda de mercadoria, saída, etc.
		$this->c['formaPagamento'] = 0; // 0 = à vista, 1 = à prazo
		$this->c['tipoDocumento'] = 1; // 0 = entrada, 1 = saida
		$this->c['dataSaida'] = date("Y-m-d H:i:s");
		$this->c['propriaRazaoSocial'] = gVar('nfe.razao_social');
		$this->c['propriaNome'] = gVar('nfe.empresa');
		$this->c['propriaInscricaoEstadual'] = gVar('nfe.insc_estadual');
		$this->c['propriaInscricaoMunicipal'] = gVar('nfe.insc_municipal');
		$this->c['propriaCnpj'] = $this->soNumeros(gVar('nfe.cnpj'));
		$this->c['propriaCnae'] = gVar('nfe.cnae');
		$this->c['propriaUf'] = gVar('nfe.UF');
		$this->c['propriaEndereco'] = gVar('nfe.endereco');
		$this->c['propriaEnderecoNumero'] = gVar('nfe.endereco_numero');
		$this->c['propriaEnderecoComplemento'] = gVar('nfe.endereco_complemento');
		$this->c['propriaEnderecoBairro'] = gVar('nfe.endereco_bairro');
		$this->c['propriaEnderecoIbgeMunicipio'] = gVar('nfe.endereco_ibge_municipio');
		$this->c['propriaEnderecoMunicipio'] = gVar('nfe.endereco_municipio');
		$this->c['propriaEnderecoUf'] = trim(gVar('nfe.endereco_uf'));
		$this->c['propriaEnderecoCep'] = gVar('nfe.endereco_cep');
		$this->c['propriaTelefone'] = gVar('nfe.telefone');
		$verificaStatus = true;

		if (
			$verificaStatus
			&& $tipo == "nfe"
			&& intval($_SESSION["armazemAtualId"]) != 8
		) {
			//Verifica qual o modo de operação
			$sql = gSQLLimit("select * from nfe_operacao order by id desc", 1);
			$rs  = dbQuery($sql)[0];
			//$rs->fields=$rs;
			//$this->c['serie'] = $rs->fields['serie'];
			$this->c['serie']=$rs['serie'];
			if (($rs['modo_operacao'] == "6") || ($rs['modo_operacao'] == "7")) {
				$this->c['tipoEmissao'] = $rs['modo_operacao']; // 3 = SCAN , 6=SVC-AN, 7=SVC-RS
				$this->c['dhCont'] = str_replace(" ", "T", $rs['data']).$this->gTimeZone;
				$this->enableSVC = true;
			} else {
				$this->c['dhCont'] = "";
				$this->c['tipoEmissao'] = "1";
				$this->c['serie'] = "1";
			}
			$this->id_operacao = $rs->fields['id'];

		} else {
			$this->c['dhCont'] = "";
			$this->c['tipoEmissao'] = "1";
			$this->c['serie'] = "1";
		}
	}


	// Processa a NFe (gera XML)
	function processa(): void
	{
		$this->processaNfe();
	}


	// Processa a NFe (gera XML)
	function processaNfe(): void
	{
		$this->cria();
		$this->geraTxt();
		$this->geraXml();
		$this->assina();
		$this->valida();

		if (!is_array($this->erros)) {
			// Só salva se não houverem erros e se já não está na base
			$this->salva();
		}
	}


	/**
	* Processa a NFSe (gera XML)
	*/
	function processaNfse(): void
	{
		$this->cria("nfse");
		$this->geraNfse();
		$this->valida("nfse");

		if (!is_array($this->erros)) {
			// Só salva se não houverem erros
			$this->enviaNfse();
		}
	}


	// Define valor para cada um dos campos da NFe
	function defineCabecalho($campos, $valor = ""): void
	{
		if (!is_array($campos)) {
			$this->c[$campos] = $valor;
			return;
		}

		foreach ($campos as $campo => $valor) {
			if ($campo == "propriaCnpj") {
				$valor = $this->soNumeros($valor);
			}
			$this->c[$campo] = $valor;
		}

		return;

		/* Campos necessários:

		Datas em formato de BD

		id						: Gerado automaticamente (id do BD)
		numero					: Gerado automaticamente (id com zeros a esquerda)
		dataEmissao			: Gerado automaticamente (hoje)
		codigoAcesso			: Gerado automaticamente (numero)
		digitoVerificador	: Gerado automaticamente (modulo 11 do codigoAcesso)

		dataSaida				: Vazio = hoje

		*/
	}


	function config()
	{
		$cfg['ambiente'] = $this->c['ambiente'];
		$cfg['empresa'] = $this->c['propriaRazaoSocial'];
		$cfg['UF'] = trim((string) $this->c['propriaEnderecoUf']);
		$cfg['cMunicipio'] = trim((string) $this->c['propriaEnderecoIbgeMunicipio']);
		$cfg['cnpj'] = $this->soNumeros($this->c['propriaCnpj']);
		$cfg['certName'] = $this->soNumeros($this->c['propriaCnpj']) . ".pfx";
		$cfg['keyPass'] = $this->c['senhaCertificado'];
		$cfg['baseurl'] = $http_lib . "nfephp";
		$cfg['schemes'] = $this->schemes;
		$cfg['arquivoURLxml'] = $this->versao == '4.00' ? 'wsnfe_4.00_mod55.xml' : 'nfe_ws3_mod55.xml';
		$cfg['arquivosDir'] = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? '/' : '/tmp';
		$this->c['raizNfe'] = $cfg['arquivosDir'] . '/' . ($cfg['ambiente'] == 2 ? 'homologacao' : 'producao') . "/";
		$cfg['enableSVC'] = $this->enableSVC;

		//definir o timezone default para o estado do emitente
		$timezone = $this->tzUFlist[$this->c['propriaEnderecoUf']];
		date_default_timezone_set($timezone);

		return($cfg);
	}


	// Define valor para cada um dos itens da NFe
	function defineItens($campos): void
	{
		$item = "";
		foreach ($campos as $campo => $valor) {
			$item[$campo] = $valor;
		}

		$this->i[] = $item;
		/* Campos necessários:

		Datas em formato de BD

		id						: Gerado automaticamente (id do BD)
		numero					: Gerado automaticamente (id com zeros a esquerda)
		dataEmissao			: Gerado automaticamente (hoje)
		codigoAcesso			: Gerado automaticamente (numero)
		digitoVerificador	: Gerado automaticamente (modulo 11 do codigoAcesso)

		dataSaida				: Vazio = hoje

		*/
	}


	function geraIdNFE()
	{
		$ano = substr((string) $this->c['dataEmissao'], 2, 2);
		$mes = substr((string) $this->c['dataEmissao'], 5, 2);
		$AAMM = $ano . $mes;
		return (trim((string) $this->c['propriaEnderecoIbgeEstado']) . $AAMM . $this->c['propriaCnpj'] . $this->c['modelo'] . str_pad((string) $this->c['serie'], 3, "0", STR_PAD_LEFT) . str_pad((string) $this->c['numero'], 9, "0", STR_PAD_LEFT) . $this->c['tipoEmissao'] . $this->c['codigoAcesso']);
	}


	/**
	* Gera TXT no formato NFePHP
	* @return type
	*/
	function geraTxt()
	{
		// Monta arquivo texto no layout do NFePHP
		$ano = substr((string) $this->c['dataEmissao'], 2, 2);
		$mes = substr((string) $this->c['dataEmissao'], 5, 2);

		// Tratamento de alguns campos antes de processar
		$this->c['propriaCnpj'] = str_pad((string) $this->soNumeros($this->c['propriaCnpj']), 14, "0", STR_PAD_LEFT);
		$this->c['propriaInscricaoEstadual'] = $this->soNumerosIsento($this->c['propriaInscricaoEstadual']);
		$this->c['propriaInscricaoMunicipal'] = $this->soNumerosIsento($this->tiraPontos($this->c['propriaInscricaoMunicipal']));
		$this->c['cnpj'] = str_pad((string) $this->tiraPontos($this->c['cnpj']), 14, "0", STR_PAD_LEFT);
		$this->c['cpf'] = str_pad((string) $this->tiraPontos($this->c['cpf']), 14, "0", STR_PAD_LEFT);
		//$this->c['inscricaoEstadual'] = $this->soNumerosIsento($this->c['inscricaoEstadual']);
		$this->c['transportadoraCnpj'] = str_pad((string) $this->soNumeros($this->c['transportadoraCnpj']), 14, "0", STR_PAD_LEFT);
		$this->c['transportadoraCpf'] = $this->soNumeros($this->c['transportadoraCpf']);
		$this->c['transportadoraInscricaoEstadual'] = $this->soNumerosIsento($this->c['transportadoraInscricaoEstadual']);
		$this->c['propriaCnae'] = $this->soNumeros($this->c['propriaCnae']);
		$this->c['propriaEnderecoUf'] = trim((string) $this->c['propriaEnderecoUf']);
		// Gerando código de acesso
		$idNfe = $this->geraIdNFE();
		$this->c['digitoVerificador'] = $this->modulo_11($idNfe);
		$idNfe .= $this->c['digitoVerificador'];
		$this->nfeid = $idNfe;
		// Atributos da NF-e
		$s = "A|" . gCleanField($this->versao) . "|NFe".gCleanField($idNfe)."" . NL;
		//B|cUF|cNF|NatOp|indPag|mod|serie|nNF|dEmi|dSaiEnt|hSaiEnt|tpNF|cMunFG|TpImp|TpEmis|cDV|tpAmb|finNFe|procEmi|VerProc|dhCont|xJust

		$m = [];
		$m[] = "B";
		$m[] = gCleanField($this->c['propriaEnderecoIbgeEstado']);
		$m[] = gCleanField($this->c['codigoAcesso']);
		$m[] = gCleanField($this->c['operacao']);
		$m[] = gCleanField($this->c['formaPagamento']);
		$m[] = "55";
		$m[] = gCleanField($this->c['serie']);
		$m[] = gCleanField($this->c['numero']);
		$m[] = gCleanField(substr((string) $this->c['dataEmissao'], 0, 10)."T".date("H:i:s").$this->gTimeZone);
		$m[] = gCleanField(substr((string) $this->c['dataSaida'], 0, 10)."T".date("H:i:s").$this->gTimeZone);
		$m[] = gCleanField($this->c['tipoDocumento']);
		$m[] = $this->c['idDest'] != "" ? intval($this->c['idDest']) : "1" ; // Indicador do local de destino da operação.
		$m[] = gCleanField($this->c['propriaEnderecoIbgeMunicipio']);
		$m[] = "1";
		$m[] = gCleanField($this->c['tipoEmissao']);
		$m[] = gCleanField($this->c['digitoVerificador']);
		$m[] = gCleanField($this->c['ambiente']);
		$m[] = intval($this->c['idFinalidade']) > 0 ? intval($this->c['idFinalidade']) : "1";
		// Finalidade da emissao: 1- NF-e normal/ 2-NF-e complementar / 3 – NF-e de ajuste / 4 - Devolução, Retorno
		$m[] = "1"; //Indica operação com Consumidor final
		$m[] = "9"; //Indicador de presença do comprador no estabelecimento comercial no momento da operação
		$m[] = "0"; // Processo de emissao: 0- Aplicativo do contribuinte
		$m[] = gCleanField($this->c['versao']);

		if ($this->c['dhCont'] != "") {
			$m[] = $this->c['dhCont'];
			$m[] = "Queda do ambiente de producao";
		}

		$s .= implode("|", $m) . NL;
		if ($this->c['refNFe'] != "") {
 			$s .= "B13|" . $this->c['refNFe'] . "|" . NL;
		}

		if ($this->c['chaveEstorno'] != "") {
			$s .= "B13|".$this->c['chaveEstorno']."|".NL;
		}

		if ($this->c['indIntermed'] != "") {
			$indIntermed = (int) $this->c['indIntermed'];
			$s .= "B25c|".$indIntermed."|".NL;
		}

		// Emitente
		$m = [];
		$m[] = "C";
		$m[] = gCleanField($this->tiraEstranhos($this->c['propriaRazaoSocial']));
		$m[] = gCleanField($this->tiraEstranhos($this->c['propriaNome']));
		$m[] = gCleanField($this->c['propriaInscricaoEstadual']);
		$m[] = "";
		$m[] = gCleanField($this->c['propriaInscricaoMunicipal']);
		$m[] = gCleanField($this->c['propriaCnae']);
		$m[] = gCleanField($this->c['regime']);

		$s .= implode("|", $m) . NL;
		$s .= "C02|" . $this->c['propriaCnpj'] . NL;

		$m = [];
		$m[] = "C05";
		$m[] = gCleanField($this->tiraEstranhos($this->c['propriaEndereco']));
		$m[] = gCleanField($this->c['propriaEnderecoNumero']);
		$m[] = gCleanField($this->tiraEstranhos($this->c['propriaEnderecoComplemento']));
		$m[] = gCleanField($this->tiraEstranhos($this->c['propriaEnderecoBairro']));
		$m[] = gCleanField($this->c['propriaEnderecoIbgeMunicipio']);
		$m[] = gCleanField($this->tiraEstranhos($this->c['propriaEnderecoMunicipio']));
		$m[] = gCleanField($this->c['propriaEnderecoUf']);
		$m[] = gCleanField($this->soNumeros($this->c['propriaEnderecoCep']));

		if ($this->c['propriaEnderecoPais'] == "") {
			$m[] = '1058';
			$m[] = 'BRASIL';
		} else {
			$m[] = $this->c['propriaEnderecoIbgePais'];
			$m[] = $this->c['propriaEnderecoPais'];
		}

		$m[] = str_replace(")", "", str_replace("(", "", str_replace(" ", "", str_replace(".", "", str_replace("-", "", $this->c['propriaTelefone'])))));
		$s .= implode("|", $m) . NL;
		//Destinatario
		$xNome = $this->c['razaoSocial'] != "" && $this->c['razaoSocial'] != " " ? $this->c['razaoSocial'] : $this->c['empresa'];
		$m = [];
		$m[] = "E";
		$m[] = $xNome;
		//if($this->soNumeros($this->c['inscricaoEstadual']) <> "")

		if (gCleanField($this->c['inscricaoEstadual']) != "") {
			$m[] = "1"; // Indicador da IE do Destinatário
			//$m[] = $this->soNumeros($this->c['inscricaoEstadual']); // IE do destinatário
			$m[] = gCleanField($this->c['inscricaoEstadual']); // IE do destinatário
		} else {
			$indIEDest = intval($this->c['indIEDest']) > 0 ? intval($this->c['indIEDest']) : 2;
			$m[]=$indIEDest;
		}

		$s .= implode("|", $m) . NL;

		if ($this->c['idDest'] != 3) { // Não é uma operação com exterior

			if ($this->c['cnpj'] != "00000000000000" || $this->c['cpf'] != "") {
				if (trim((string) $this->c['cnpj']) !== "" && trim($this->c['cnpj'] != "00000000000000")) {
					$s.="E02|" . $this->c['cnpj'] . NL;
				} else {
					$s.="E03|" . substr((string) $this->c['cpf'], -11) . NL;
				}
			}

		} else {
			$s .= "E03a|".NL;
		}
		$m = [];
		$m[] = "E05";
		$m[] = gCleanField($this->tiraEstranhos($this->c['endereco']));
		$m[] = gCleanField($this->c['enderecoNumero']);
		$m[] = gCleanField($this->tiraEstranhos($this->c['enderecoComplemento']));
		$m[] = gCleanField($this->tiraEstranhos($this->c['enderecoBairro']));
		$m[] = $this->c['idDest'] == 3 ? '9999999' : gCleanField($this->c['enderecoIbgeMunicipio']);
		$m[] = $this->c['idDest'] == 3 ? 'EXTERIOR' : gCleanField($this->tiraEstranhos($this->c['enderecoMunicipio']));
		$m[] = $this->c['idDest'] == 3 ? 'EX' : gCleanField($this->c['enderecoUf']);
		$m[] = gCleanField($this->soNumeros($this->c['enderecoCep']));
		$m[] = $this->c['enderecoCodigoPais'] != "" ? gCleanField($this->c['enderecoCodigoPais']) : '1058';
		$m[] = $this->c['enderecoPais'] ? gCleanField(strtoupper((string) $this->tiraEstranhos($this->c['enderecoPais']))) : 'BRASIL';
		$m[] = str_replace(")", "", str_replace("(", "", str_replace(" ", "", str_replace(".", "", str_replace("-", "", $this->c['telefone'])))));
		// if(intval($this->c['indIEDest']) > 0)
		// {
		//   $m[]= intval($this->c['indIEDest']);
		//   if((int) $this->c['indIEDest'] == 1)
		//   	$m[]= $this->soNumeros($this->c['inscricaoEstadual']);
		// }
		$s .= implode("|", $m) . NL;
		// Itens da nota
		$qtd = $t_cofins = $t_pis = $v_total = $total_itens = $t_icms = $t_ipi = $total_pb = $total_pl = $t_desconto = $total_base_icms = $valor_frete = $valor_seguro = 0;
		$temIcms = false;
		$temIcmsST = false;

		//Tratamentos para Peso Bruto e Peso Liquido
		if ($this->c['totalPesoBruto'] > 0) {
			$total_pb = $this->c['totalPesoBruto'];
		}

		if ($this->c['totalPesoLiquido'] > 0) {
			$total_pl = $this->c['totalPesoLiquido'];
		}

		if (gVar("nfe.id_contador") != "") {
			$m = [];
			$m[] = "GA";
			$m[] = strlen(gVar("nfe.id_contador")) < 14 ? gVar("nfe.id_contador") : "";
			$m[] = strlen(gVar("nfe.id_contador")) == 14 ? gVar("nfe.id_contador") : "";
			$s .= implode("|", $m) . NL;
		}

		foreach ($this->i as $item)  {
			//Informações adicionais do produto
			$infAdProd = "";
			$xPed = "";
			if ((int) $item['NumeroEntrada'] > 0) {
				$infAdProd = "NF de cobertura: ". (int) $item['NumeroEntrada']."/".(int) $item['SerieEntrada'];
			}

			if (isset($item["NumeroCliente"]) && !empty($item["NumeroCliente"]) && !is_null($item["NumeroCliente"])) {
				$xPed=$item["NumeroCliente"];
			}

			$qtd++;
			$s.="H|$qtd|$infAdProd|" . NL;
			$m = [];
			$m[] = "I";
			$m[] = $item['codigo'];
			$m[] = "SEM GTIN";
			$m[] = $this->tiraEstranhos($item['descricao']);
			$m[] = $this->soNumeros($item['ncm']);
			$m[] = ""; //Codificação NVE - Nomenclatura de valor Aduaneiro e Estatística.
			$m[] = ""; // EXT TIPI
			//$cfop = $this->soNumeros($this->c['cfop'] <> "" ? $this->c['cfop'] : $item['cfop']);

			if ((int) $this->soNumeros($item['cfop_saida']) > 0) {
				$cfop = $this->soNumeros($item['cfop_saida']);
			} else {
				$cfop = $this->soNumeros($this->c['cfop'] != "" ? $this->c['cfop'] : $item['cfop']);
			}

			if ($this->c['chaveEstorno'] != "") {
				$p = substr((string) $cfop,0,1);
				switch ((int) $p) {
					case 5:
						//$cfop = "1".substr($cfop,-3);
						$cfop="1905";
						break;
					case 6:
						//$cfop = "2".substr($cfop,-3);
						$cfop="2905";
						break;
				}
			}

			//$m[] = $this->soNumeros($this->c['cfop'] <> "" ? $this->c['cfop'] : $item['cfop']);
			$m[] = $cfop;
			$m[] = $item['unidade'];

			$qtdI = number_format($item['quantidade'], 4, ".", "");
			$vtI = number_format($item['valor'], 10, ".", "");
			$m[] = $qtdI;
			$m[] = $vtI;
			$isoma = $item['quantidade'] > 0 ? number_format($qtdI * $vtI, 2, ".", "") : number_format(1 * $vtI, 2, ".", "");
			$m[] = $isoma;
			$m[] = "SEM GTIN";
			$m[] = $item['unidade'];

			$m[] = '0'; //q trib
			$m[] = '0'; //v un trib

			if ($item['frete'] > 0) {
				$m[] = number_format($item['frete'], 2, ".", "");
				$valor_frete+=$item['frete'];
			} else {
				$m[] = "0"; //frete
			}

			if ($item['seguro'] > 0) {
				$m[] = number_format($item['seguro'], 2, ".", "");
				$valor_seguro+=$item['seguro'];
			} else {
				$m[] = "0"; //Seguro
			}

			if ($item['desconto'] > 0) {
				$m[] = number_format($item['desconto'], 2, ".", ""); //desconto
			} else {
				$m[] = "0";
			}

			if ($item['outrasDespesas'] > 0) {
				$m[] = number_format($item['outrasDespesas'], 2, ".", ""); //outros
				$outrasDespesas+=number_format($item['outrasDespesas'], 2, ".", "");
			} else {
				$m[] = "";
				$outrasDespesas+=0;
			}

			$m[] = "1"; // 0) o item nao compoe o total, 1) o item compoe o total
			$m[] = $xPed;
			$m[] = "";
			$m[] = "";
			$m[] = "";
			$s .= implode("|", $m) . NL;
			/*
				Grupo LA de combustível.
				L101|cProdANP|descANP|CODIF|qTemp|UFCons|
			*/

			if ($item["cProdANP"] != "" || $item["cProdANP_item"]) {
				$uf = $item["UFCons"] != "" ? $item["UFCons"] : $this->c['enderecoUf'];
				$cProdANP = $item["cProdANP"] != "" ? $item["cProdANP"] : $item["cProdANP_item"];
				$descANP = $item["descANP"] != "" ? $item["descANP"] : $item["descANP_item"];
				$s .= "L101|".$cProdANP."|".$descANP."|".$item["CODIF"]."|".$uf."|";
			}

			$s .= NL;
			// Valor total do item
			$v_total = $isoma;
			$total_itens += $v_total;
			$total_volumes += $item['quantidade'];
			$t_desconto += $item['desconto'];

			if (!$this->c['totalPesoLiquido'] > 0) {
				$total_pl += floatval($item['pesoLiquido']);
			}

			if (!$this->c['totalPesoBruto'] > 0) {
				$total_pb += floatval($item['pesoBruto']);
			}

			// Impostos
			$s .= "M" . NL;

			// Operacoes de armazenagem geral, ICMS, PIS e COFINS são ISENTOS!
			// ICMS
			$valorCalculadoIcms="";
			$origem = intval(substr(trim((string) $this->c['origem']), 0, 1));
			if ($item['tipo'] == "importacao") {
				$s.="N" . NL;
				$s.="N05|1|30|0|00|00|00|00|00" . NL;
			} else {
				$s .= "N" . NL;
				if ($item['icms_cst'] != "") {
					if ($item['baseICMS'] == "") {
						$item["baseICMS"] = $item["valor"];
					}

					$detIcms = $item['icms_modalidadebc'] != "" ? $item['icms_modalidadebc'] : intval(substr((string) $this->c['detIcms'], 0, 1));
					$detIcmsSt = $item['icmsst_modalidadebc'] != "" ? $item['icmsst_modalidadebc'] : intval(substr((string) $this->c['detIcmsSt'], 0, 1));
					$orig = $item['origem'] != "" ? $item['origem'] : $origem;

					switch ($item['icms_cst']) {
						case '00': // Tributada integralmente
							$vBC=number_format(($item['baseICMS'] * $item['quantidade']),2, ".", "");
							$vICMS = number_format(($vBC * ($item['aliquotaICMS'] / 100)), 2, ".", "");
							$s.="N02|".$orig."|".$item['icms_cst']."|".$detIcms."|".$vBC."|".number_format($item['aliquotaICMS'], 2, ".", "")."|".$vICMS.NL;
							$total_base_icms+=$vBC;
							$temIcms = true;
							$valorCalculadoIcms = $vICMS;
							break;

						case '10': // Tributada e com cobrança do ICMS por substituição truibtária
							$temIcmsST = true;
							$temIcms = true;
							if ($this->c['sistema'] != "WMS2"){
								$vBC = number_format(($item['baseICMS'] * $item['quantidade']),2, ".", "");
								$valorCalculadoIcms = $vICMS = number_format(($vBC * ($item['aliquotaICMS'] / 100)), 2, ".", "");
								$pICMS = number_format($item['aliquotaICMS'], 2, ".", "");

								$pMVAST   = number_format(($item['percentAdicICMSST']), 2, ".", "");
								$pRedBCST = number_format(($item['percentReducICMST']), 2, ".", "");
								$vBCST 	  = number_format(($item['quantidade'] * $item['baseICMSST']), 2, ".", "");
								$pICMSST  = number_format($item['aliquotaICMSST'], 2, ".", "");
								$vICMSST  = number_format((($vBCST * ($item['aliquotaICMSST'] / 100)- $vICMS)), 2, ".", "");

								$s .= "N03|$orig|".$item['icms_cst']."|$detIcms|" . $vBC . "|" . $pICMS . "|";
								$s .= $vICMS . "|$detIcmsSt|" . $pMVAST . "|" . $pRedBCST . "|";
								$s .= $vBCST . "|" . $pICMSST . "|" . $vICMSST . NL;
								$total_base_icms 	+= $item['quantidade'] * $item['baseICMS'];
								$total_base_icms_st	+= $item['quantidade'] * $item['baseICMSST'];
								$total_icms_st		+= $vICMSST;
							} else {

								$vBC 	  = number_format(($item['vBC']),2, ".", "");
								$pICMS 	  = number_format($item['pICMS'], 2, ".", "");
								$vICMS 	  = $vBC * ($pICMS/100);
								$pMVAST   = number_format($item['pMVAST'], 4, ".", "");
								$pRedBCST = number_format($item['pRedBCST'], 4, ".", "");
								$vBCST 	  = number_format($item['vBCST'], 2, ".", "");
								$pICMSST  = number_format($item['pICMSST'], 2, ".", "");
								$vICMSST  = number_format(($vBCST * ($pICMSST/100)) - $vICMS,2, ".","" );

								$icms10 = [];
								$icms10[] = "N03";
								$icms10[] = $orig; // Origem da mercadoria
								$icms10[] = "10"; // Tributação do ICMS = 10
								$icms10[] = $detIcms; // Modalidade de determinação da BC do ICMS
								$icms10[] = $vBC; // Valor da BC do ICMS
								$icms10[] = $pICMS; //Alíquota do imposto

								$icms10[] = number_format($vICMS, 2, ".", ""); // Valor do ICMS
								$icms10[] = $item['modBCST'];//Modalidade de determinação da BC do ICMS ST
								$icms10[] = $pMVAST; //Percentual da margem de valor Adicionado do ICMS ST
								$icms10[] = $pRedBCST; //Percentual da Redução de BC do ICMS ST
								$icms10[] = $vBCST; //Valor da BC do ICMS ST
								$icms10[] = $pICMSST; // Alíquota do imposto do ICMS ST
								$icms10[] = $vICMSST; // Valor do ICMS ST

								$vBCFCP = number_format($item['vBCFCP'], 2, ".", "");
								$pFCP = number_format($item['pFCP'], 4, ".", "");
								$vFCP = number_format($vBCFCP * ($pFCP/100),2,".","");

								$icms10[] = $vBCFCP ; // vBCFCP - Valor da Base de Cálculo do FCP
								$icms10[] = $pFCP ; // pFCP - Percentual do Fundo de Combate à Pobreza (FCP)
								$icms10[] = $vFCP; //vFCP - Valor do Fundo de Combate à Pobreza (FCP)

								$vBCFCPST = number_format($item['vBCFCPST'], 2, ".", "");
								$pFCPST = number_format($item['pFCPST'], 4, ".", "");
								$vFCPST = number_format(($vBCFCPST * ($pFCPST/100) - $vFCP),2,".","");

								$icms10[] = $vBCFCPST;
								$icms10[] = $pFCPST;
								$icms10[] = $vFCPST;

								$s .= implode("|",$icms10).NL;

							}
							break;

						case '20'://Tributação com redução de base de cálculo
							$vBC = number_format(($item['baseICMS'] * $item['quantidade']),2, ".", "");
							$vICMS = number_format(($vBC * ($item['aliquotaICMS'] / 100)), 2, ".", "");
							$pRedBC = number_format($item['icms_percentual_reducao'], 2, ".", "");

							$s .= "N04|".$orig."|".$item['icms_cst']."|".$detIcms."|".$pRedBC ."|".$vBC."|".number_format($item['aliquotaICMS'], 2, ".", "")."|".$vICMS.NL;

							$total_base_icms+=$vBC;
							$temIcms = true;
							$valorCalculadoIcms = $vICMS;
							break;

						case '30'://Tributação Isenta ou não tributada e com cobrança do ICMS por substituição tributária
							$pMVAST   = number_format(($item['percentAdicICMSST']), 2, ".", "");
							$pRedBCST = number_format(($item['percentReducICMST']), 2, ".", "");
							$vBCST    = number_format(($item['quantidade'] * $item['baseICMSST']), 2, ".", "");
							$pICMSST  = number_format($item['aliquotaICMSST'], 2, ".", "");
							$vICMSST  = number_format(($vBCST * ($item['aliquotaICMSST'] / 100)), 2, ".", "");

							$s .= "N05|$orig|".$item['icms_cst']."|$detIcmsSt|" . $pMVAST."|". $pRedBCST."|".$vBCST."|".$pICMSST."|".$vICMSST .NL;
							$total_base_icms_st += $vBCST;
							$total_icms_st += $vICMSST;
							$temIcmsST = true;
							break;

						case '40':
						case '41':
						case '50':
							$s .= "N06|$orig|".$item['icms_cst']. NL;
							break;

						case '51':
							$pRedBC = number_format($item['icms_percentual_reducao'], 2, ".", "");
							$vBC = number_format(($item['baseICMS'] * $item['quantidade']),2, ".", "");
							$pICMS = number_format($item['aliquotaICMS'], 2, ".", "");
							$vICMSOp = number_format(($vBC * (($pICMS) / 100)), 2, ".", "");
							$pDif = number_format($item['percentual_diferimento_icms'], 2, ".", "");
							$vICMSDif = number_format(($vICMSOp * ($pDif / 100)), 2, ".", "");
							$vICMS = number_format(($vICMSOp - $vICMSDif), 2, ".", "");
							$s .= $a="N07|$orig|".$item['icms_cst']."|$detIcms||$vBC|$pICMS|$vICMSOp|$pDif|$vICMSDif|$vICMS".NL;
							$total_base_icms += $vBC;
							$temIcms = true;
							$valorCalculadoIcms = $vICMS;
							break;

						case '60':
							$vBCSTRet = number_format(($item['quantidade'] * $item['baseICMS']), 2, ".", "");
							$pST = number_format($item['pICMSST'], 2, ".", "");
							$vICMSSTRet = number_format($item['vICMSSTRet'], 2, ".", "");
							$s .= "N08|$orig|".$item["icms_cst"]."|{$vBCSTRet}|{$pST}|{$vICMSSTRet}".NL;
							break;

						case '90': // TODO: É preciso conferir se os campos e cálculos estão corretos

							$vBC = number_format(($item['vBC']),2, ".", "");
							//$vBC = number_format(($item['baseICMS'] * $item['quantidade']),2, ".", "");
							$pICMS = number_format($item['pICMS'], 2, ".", "");
							$vICMS = $vBC * ($pICMS/100);
							$pMVAST = number_format($item['pMVAST'], 4, ".", "");
							$modBCST = $item['modBCST'];
							$pRedBCST = number_format($item['pRedBCST'], 4, ".", "");
							$vBCST = number_format($item['vBCST'], 2, ".", "");
							$pICMSST = number_format($item['pICMSST'], 2, ".", "");
							$vICMSST =  number_format(($vBCST * ($pICMSST/100)) - $vICMS,2, ".","" );
							$modBC = $item['modBC'];
							$s.="N10|$orig|".$item["icms_cst"]."|".($item['id_imp_icms_mod']-1)."|".$vBC."|".$pRedBCST."|".$pICMS."|".$vICMS.'|'.$modBCST.'|'.$pMVAST.'|'.$pRedBCST.'|'.$vBCST.'|'.$pICMSST.'|'.$vICMSST .NL;
							$total_base_icms+=$vBC;
							break;
					}

					$item["baseICMS"]="";
				} elseif ($item['aliquotaICMS'] > 0) {
					if (!$item['icms'] > 0) {
						$item['icms'] = number_format((($item['quantidade'] * $item['baseICMS']) * ($item['aliquotaICMS'] / 100)), 2, ".", "");
					}

					$detIcms = intval(substr((string) $this->c['detIcms'], 0, 1));
					$detIcmsSt = intval(substr((string) $this->c['detIcmsSt'], 0, 1));
					// 10 - Tributada e com cobrança do ICMS por substituição tributária
					if (intval(substr((string) $this->c['cst'], 0, 2)) == 10) {
						$s.="N03|$origem|10|$detIcms|" . number_format(($item['quantidade'] * $item['baseICMS']), 2, ".", "") . "|" . number_format($item['aliquotaICMS'], 2, ".", "") . "|";
						$s.=number_format(($item['quantidade'] * $item['icms']), 2, ".", "") . "|$detIcmsSt|" . number_format(($item['percentAdicICMSST']), 2, ".", "") . "|" . number_format(($item['percentReducICMSST']), 2, ".", "") . "|";
						$s.=number_format(($item['quantidade'] * $item['baseICMSST']), 2, ".", "") . "|" . number_format($item['aliquotaICMSST'], 2, ".", "") . "|" . number_format(($item['quantidade'] * $item['icmsST']), 2, ".", "") . NL;
						$total_base_icms_st+=$item['quantidade'] * $item['baseICMSST'];
						$total_icms_st+=$item['quantidade'] * $item['icmsST'];
						$temIcmsST = true;
					} elseif(intval(substr((string) $this->c['cst'], 0, 2)) == 20) { // 20 - Com redução de base de cálculo
						$valorCalculadoIcms=( ($item['aliquotaICMS']/100) * ($item['baseICMS'] * $item['quantidade'] ) ) ;

						//echo "Aliquota: ".$item['aliquotaICMS']."<BR>Base:".$item['baseICMS']."<BR>Quantidade:".$item['quantidade']."<BR>";
						$Valores=explode(".",$valorCalculadoIcms);
						$decimais=$Valores[1];
						$decimais=substr($decimais,0,2);
						$valorCalculadoIcms=$Valores[0].".".$decimais;
						//echo $valorCalculadoIcms."<BR>".number_format($valorCalculadoIcms, 2, ".", "");exit;
						//N04 | orig | CST | modBC |  pRedBC  | vBC | pICMS  | vICMS
						$s .= "N04|$origem|20|$detIcms|" . number_format(($item['percentReducICMST']), 2, ".", "") .
							"|" . number_format(($item['quantidade'] * $item['baseICMS']), 2, ".", "") .
							"|" . number_format($item['aliquotaICMS'], 2, ".", "") .
							"|" . number_format($valorCalculadoIcms, 2, ".", "") . NL /*vICMS*/;
					} else {
						$valorCalculadoIcms = (($item['aliquotaICMS'] / 100) * ($item['baseICMS'] * $item['quantidade'])) ;
						$Valores  = explode(".",$valorCalculadoIcms);
						$decimais = $Valores[1];
						$decimais = substr($decimais,0,2);
						$valorCalculadoIcms = $Valores[0].".".$decimais;
						$s .= "N02|$origem|00|0|" . number_format(($item['quantidade'] * $item['baseICMS']), 2, ".", "") . "|" .
						number_format($item['aliquotaICMS'], 2, ".", "") . "|" .
						number_format($valorCalculadoIcms, 2, ".", "") . NL;
					}

					$total_base_icms+=($item['quantidade'] * $item['baseICMS']);
					$temIcms = true;
				} else {
					$cst = 50;
					if (intval(substr((string) $this->c['cst'], 0, 2)) > 0) {
						$cst = intval(substr((string) $this->c['cst'], 0, 2));
					}

					$s .= "N06|$origem|$cst" . NL; // 40 isento, 50 suspensão , 41 ñ tributada
				}
			}

			switch ($item['ipi_cst']) {
				case '01':
				case '02':
				case '03':
				case '04':
				case '05':
				case '51':
				case '52':
				case '53':
				case '54':
				case '55':
					$cEnq =$item['ipi_cEnq'];
					if ($cEnq == "") {
						$cEnq="109";
						if ($cfop == "5902") {
							$cEnq="108";
						}
					}

					$s .= "O|0||||".$cEnq."|" . NL;
					$s .= "O08|".$item['ipi_cst'].NL;
					break;

				case '50':
					$s .= "O|0||||999|" . NL;
					$s .= "O07|50|" . number_format( ( ($item['valor'] * $item['quantidade']) * $item['pIPI']/100 ), 2, ".", "") . NL;
					$s .= "O10|" . number_format($item['quantidade'] * $item['valor'], 2, ".", "") . "|" . number_format($item['pIPI'], 2, ".", "") . NL;
					$t_ipi += number_format( ($item['valor'] * $item['quantidade']) * $item['pIPI']/100, 2, ".", "");
					break;

				default:
					if ($item['cst_ipi'] > 0) {
						//IPI
						$s .= "O|0||||999|" . NL;
						$s .= "O07|50|" . number_format( ( ($item['valor'] * $item['quantidade']) * $item['aliquotaIPI']/100 ), 2, ".", "") . NL;
						$s .= "O10|" . number_format($item['quantidade'] * $item['valor'], 2, ".", "") . "|" . number_format($item['aliquotaIPI'], 2, ".", "") . NL;
						// if (intval($item['cst_ipi'])==50)
						// {
						$t_ipi += number_format( ($item['valor'] * $item['quantidade']) * $item['aliquotaIPI']/100 , 2, ".", "");
						// } else {
						// 	$t_ipi+=$item['ipi'] * $item['quantidade'];
						// }
					}

					break;
			}

			// PIS
			switch ($item['pis_cst']) {
				case '01':
					$pPIS = number_format($item['pPIS'], 2, ".", "");
					$vPIS = number_format( ($pPIS/100) * ($item['quantidade'] * $item['valor'] ), 2, ".", "" ) ;
					$s .= "Q" . NL;
					$s .= "Q02|01|".number_format($item['quantidade'] * $item['valor'], 2, ".", "")."|".$pPIS."|".$vPIS.NL;
					$t_pis += $vPIS;
					break;

				case '04':
				case '06':
				case '07':
				case '08':
				case '09':
					$s .= "Q" . NL;
					$s .= "Q04|" .$item['pis_cst']. NL;
					break;

				case '49':
					$vBC  = number_format($item['quantidade'] * $item['valor'], 2, ".", "" );
					$pPIS = number_format($item['pPIS'], 2, ".", "");
					$vPIS = number_format( ($pPIS/100) * ($item['quantidade'] * $item['valor'] ), 2, ".", "" ) ;
					$s .= "Q" . NL;
					$s .= "Q05|".$item['pis_cst']."|".$vPIS.NL;
					$s .= "Q07|".$vBC."|".$pPIS.NL;
					$t_pis += $vPIS;
					break;

				default:
					$s .= "Q" . NL;
					$s .= "Q04|08" . NL; // 07 Isenta, 08 sem incidência
					break;
			}

			// COFINS
			switch ($item['cofins_cst']) {
				case '01':
					$pCOFINS = number_format($item['pCOFINS'], 2, ".", "");
					$vCOFINS = number_format( ($pCOFINS/100) * ($item['quantidade'] * $item['valor'] ), 2, ".", "" ) ;
					$t_cofins += $vCOFINS;
					$s .= "S" . NL;
					$s .= "S02|01|".number_format($item['quantidade'] * $item['valor'], 2, ".", "")."|".$pCOFINS."|".$vCOFINS.NL;
					break;

				case '04':
				case '06':
				case '07':
				case '08':
				case '09':
					$s .= "S" . NL;
					$s .= "S04|".$item['cofins_cst'].NL;
					break;

				case '49':
					$vBC 	 = number_format($item['quantidade'] * $item['valor'], 2, ".", "" );
					$pCOFINS = number_format($item['pCOFINS'], 2, ".", "");
					$vCOFINS = number_format( ($pCOFINS/100) * ($item['quantidade'] * $item['valor'] ), 2, ".", "" ) ;
					$s .= "S" . NL;
					$s .= "S05|".$item['cofins_cst']."|".$vCOFINS.NL;
					$s .= "S07|".$vBC."|".$pCOFINS.NL;
					$t_cofins+=$vCOFINS;
					break;

				default:
					$s .= "S" . NL;
					$s .= "S04|08|" . NL; // 07 Isenta, 08 sem incidência
					break;
			}

			if ($valorCalculadoIcms !== "") {
				$t_icms += $valorCalculadoIcms;
			} else {
				$t_icms += $item['icms'] * $item['quantidade'];
			}

			if ($item['cProdANVISA'] != "" && $item['xMotivoIsencao'] != "" && $item['VPMC'] != "") {
				// I80|nLote|qLote|dFab|dVal|cAgreg
				$s .= "I80|".$item['nLote']."|".number_format($item['qLote'], 3, ".", "")."|".$item['dFab']."|".$item['dVal']."|".$item['cAgreg'].NL;
				//K|cProdANVISA|xMotivoIsencao|vPMC
				$s .= "K|".$item['cProdANVISA']."|".$item['xMotivoIsencao']."|".number_format($item['VPMC'], 2, ".", "").NL;
			}

		}

		// Totais
		$base_calculo = $temIcms ? $total_base_icms : floatval($this->c['baseCalculo']);

		if ($t_desconto > 0) {
			$tt_itens   = ($total_itens + $t_ipi + $outrasDespesas + $valor_frete + $valor_seguro + $total_icms_st) - $t_desconto;//+$t_cofins+$t_pis
			$t_desconto = number_format($t_desconto, 2, ".", "");
		} else {
			$tt_itens   = ($total_itens + $t_ipi + $outrasDespesas + $valor_frete + $valor_seguro+$total_icms_st); // +$t_cofins+$t_pis
			$t_desconto = "0.00";
		}

		//gLog("T_DESCONTO => ".$t_desconto." \n". $tt_itens);
		$s .= "W" . NL;
		$s .= "W02|" .number_format($base_calculo, 2, ".", "") . "|" .number_format($t_icms, 2, ".", "") ."|".number_format(0, 2, ".", ""). "|" .number_format($total_base_icms_st, 2, ".", "") . "|" .number_format($total_icms_st, 2, ".", "") . "|" .number_format($total_itens, 2, ".", "") . "|" .number_format($valor_frete, 2, ".", "") . "|" .number_format($valor_seguro, 2, ".", "") . "|$t_desconto| 0.00|" . number_format($t_ipi, 2, ".", "") . "|" .number_format($t_pis, 2, ".", "") . "|" . number_format($t_cofins, 2, ".", "") . "|" . number_format($outrasDespesas , 2, ".", "") . "|" . number_format($tt_itens, 2, ".", "") . NL;
		// Transportadora
		$s .= "X|" . $this->c['modalidadeFrete'] . NL;

		if (
			(
				($this->c['transportadoraCnpj'] != "")
				|| ($this->c['transportadoraCpf'] != "")
			) && $this->c['modalidadeFrete'] != '9'
		) {

			$razao = $this->tiraEstranhos($this->c['transportadoraRazaoSocial'] != "" && $this->c['transportadoraRazaoSocial'] != " " ? $this->c['transportadoraRazaoSocial'] : $this->c['transportadoraNome']);
			$s .= "X03|" . $razao . "|" . $this->soNumeros($this->c['transportadoraInscricaoEstadual']) . "|" . $this->c['transportadoraEndereco'] . "|" . $this->c['transportadoraEnderecoMunicipio'] . "|" . $this->c['transportadoraEnderecoUf'] . NL;
			if ($this->c['transportadoraCnpj'] != "" && $this->c['transportadoraCnpj'] != "00000000000000") {
				$s .= "X04|" . str_replace("-", "", str_replace(".", "", str_replace("/", "", $this->c['transportadoraCnpj']))) . NL;
			} else {
				$s .= "X05|" . $this->c['transportadoraCpf'] . NL;
			}

		}

		if ($this->c['veiculoPlaca'] != "") {
			$placaUf = $this->c['veiculoPlacaUf'] != "" ? strtoupper((string) $this->c['veiculoPlacaUf']) : "BA";
			$s .= "X18|" . strtoupper((string) $this->c['veiculoPlaca']) . "|" . $placaUf . NL;
		}

		if ($this->c['reboquePlaca'] != "") {
			$placaUf = $this->c['reboquePlacaUf'] != "" ? strtoupper((string) $this->c['reboquePlacaUf']) : "BA";
			$s .= "X22|" . strtoupper((string) $this->c['reboquePlaca']) . "|" . $placaUf . NL;
		}

		if (intval($this->c['quantidadeEspecie']) > 0) {
			$s .= "X26|" . intval($this->c['quantidadeEspecie']) . "|" . $this->c['especieTransportada'] . "|".$this->c['especieMarca']."|".$this->c['especieNumero']."|" . number_format($total_pl, 3, ".", "") . "|" . number_format($total_pb, 3, ".", "") . NL;
		} else {
			$s .= "X26|" . intval($total_volumes) . "||||" . number_format($total_pl, 3, ".", "") . "|" . number_format($total_pb, 3, ".", "") . NL;
		}

		if ($this->versao=='4.00') {
			$s .= "YA|90|0.00".NL;
		}

		if ($indIntermed == 1) {
			$s .= "YB|".$this->c['propriaCnpj']."|".gCleanField($this->tiraEstranhos($this->c['propriaRazaoSocial']));
		}

		if ($this->c['lacres'] != "") {
			$s .= "X33|" . $this->c['lacres'] . NL;
		}

		if (($this->c['informacoesFisco'] != "") || ($this->c['informacoesContribuinte'] != "")) {
			$s .= "Z|" . $this->c['informacoesFisco'] . "|" . $this->c['informacoesContribuinte'] . NL;
		}

		if ($this->c['idDest'] == 3) {
			//Indica o local de saída da carga
			$m = [];
			$m[] = "ZA";
			$m[] = $this->c['propriaEnderecoUf'];
			$m[] = $this->tiraEstranhos($this->c['propriaEnderecoMunicipio']);
			$m[] = $this->tiraEstranhos($this->c['propriaEnderecoMunicipio']);
			$s .= implode("|", $m) . NL;
		}

		if ($xPed != "") {
			$m = [];
			$m[] = "ZB";
			$m[] = "";
			$m[] = $xPed;
			$m[] = "";
			$s .= implode("|", $m) . NL;
		}

		if ((int) gVar("nfe.incluirResponsavelTecnico") == 1 ) {
			$m = [];
			$m[] = "RPTEC";
			$m[] = "01108339000179";
			$m[] = "Setor desenvolvimento Giusoft";
			$m[] = "sistemas@giusoft.com.br";
			$m[] = "7134020123";
			$s .= implode("|", $m) . NL;
		}

		$s = $this->tiraAcentos($s);
		$this->nfetxt = $s;

		// Salva arquivo criado em disco
		$s = str_replace("\t", "", $s);
		$arq = fopen($this->arqtxt, "w");
		fwrite($arq, $s);
		fclose($arq);
		return($s);
	}


	/**
	* Gera XML no formato SEFAZ
	* @return type
	*/
	function geraXml()
	{
		$sai = true;
		$arq = $this->nfetxt2xml($this->arqtxt);
		// gLog($arq[0]);

		$this->arqxml = $this->tmp . $this->chave . '-nfe.xml';
		file_put_contents($this->arqxml, $arq);
		if (!file_put_contents($this->arqxml, $arq)) {
			$sai = false;
			$this->erros[] = M . "Erro ao salvar arquivo XML";
		}

		$this->nfexml = $arq;
		return($sai);
	}


	function geraNfse(): void
	{
		// Preparando...
		$idRps = "rps1";
		$idLote = "lote1";

		//cria o objeto DOM para o xml
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$dom->preserveWhiteSpace = false;

		$Rps = $dom->createElement("Rps");

		$infRps = $dom->createElement("InfRps");
		$infRps->setAttribute("id", $idRps);

		// Identificação
		$IdentificacaoRps = $dom->createElement("IdentificacaoRps");
		$Numero = $dom->createElement("Numero", $this->c['numeroNota']);
		$Serie = $dom->createElement("Serie", $this->c['serie']);
		$Tipo = $dom->createElement("Tipo", $this->c['tipo']);
		$IdentificacaoRps->appendChild($Numero);
		$IdentificacaoRps->appendChild($Serie);
		$IdentificacaoRps->appendChild($Tipo);

		$infRps->appendChild($IdentificacaoRps);
		$infRps->appendChild($dom->createElement("DataEmissao", date("Y-m-d") . "T" . date("H:i:s")));
		$infRps->appendChild($dom->createElement("NaturezaOperacao", $this->c['naturezaOperacao']));
		$infRps->appendChild($dom->createElement("OptanteSimplesNacional", $this->c['optanteSimplesNacional']));
		$infRps->appendChild($dom->createElement("IncentivadorCultural", $this->c['incentivadorCultural']));
		$infRps->appendChild($dom->createElement("Status", $this->c['status']));

		$qtd = $v_total = $total_itens = $t_icms = $t_ipi = $total_pb = $total_pl = 0;
		foreach ($this->i as $item) {
			$qtd++;
			$Servico = $dom->createElement("Servico");

			// Valores
			$Valores = $dom->createElement("Valores");
			$ValorServicos = $dom->createElement("ValorServicos", number_format($item['valor'], 2, '.', ''));
			$ValorDeducoes = $dom->createElement("ValorDeducoes", number_format($item['valorDeducoes'], 2, '.', ''));
			$ValorPis = $dom->createElement("ValorPis", number_format($item['valorPis'], 2, '.', ''));
			$ValorCofins = $dom->createElement("ValorCofins", number_format($item['valorCofins'], 2, '.', ''));
			$ValorIr = $dom->createElement("ValorIr", number_format($item['valorIr'], 2, '.', ''));
			$ValorCsll = $dom->createElement("ValorCsll", number_format($item['valorCsll'], 2, '.', ''));
			$IssRetido = $dom->createElement("IssRetido", $item['issRetido']);
			$ValorIss = $dom->createElement("ValorIss", number_format($item['valorIss'], 2, '.', ''));
			$ValorIssRetido = $dom->createElement("ValorIssRetido", number_format($item['valorIssRetido'], 2, '.', ''));
			$OutrasRetencoes = $dom->createElement("OutrasRetencoes", number_format($item['outrasRetencoes'], 2, '.', ''));
			$BaseCalculo = $dom->createElement("BaseCalculo", number_format($item['baseCalculo'], 2, '.', ''));
			$Aliquota = $dom->createElement("Aliquota", number_format($item['aliquota'], 2, '.', ''));
			$ValorLiquidoNfse = $dom->createElement("ValorLiquidoNfse", number_format($item['valorLiquidoNfse'], 2, '.', ''));
			$DescontoIncondicionado = $dom->createElement("DescontoIncondicionado", number_format($item['descontoIncondicionado'], 2, '.', ''));
			$DescontoCondicionado = $dom->createElement("DescontoCondicionado", number_format($item['descontoCondicionado'], 2, '.', ''));

			$Valores->appendChild($ValorServicos);
			$Valores->appendChild($ValorDeducoes);
			$Valores->appendChild($IssRetido);
			$Valores->appendChild($ValorIss);
			$Valores->appendChild($ValorIssRetido);
			$Valores->appendChild($BaseCalculo);
			$Valores->appendChild($Aliquota);

			// Detalhes do serviço
			$ItemListaServico = $dom->createElement("ItemListaServico", trim((string) $item['itemListaServico']));

			$CodigoTributacaoMunicipio = $dom->createElement("CodigoCnae", trim((string) $item['codigoCnae']));
			$Discriminacao = $dom->createElement("Discriminacao", $this->tiraAcentos($item['discriminacao']));
			$CodigoMunicipio = $dom->createElement("CodigoMunicipio", '2927408');

			$Servico->appendChild($Valores);
			$Servico->appendChild($ItemListaServico);
			$Servico->appendChild($CodigoTributacaoMunicipio);
			$Servico->appendChild($Discriminacao);
			$Servico->appendChild($CodigoMunicipio);
			$infRps->appendChild($Servico);
		}

		// Prestador
		$Prestador = $dom->createElement("Prestador");
		$Cnpj = $dom->createElement("Cnpj", $this->c['propriaCnpj']);
		$InscricaoMunicipal = $dom->createElement("InscricaoMunicipal", $this->soNumerosIsento($this->tiraPontos($this->c['propriaInscricaoMunicipal'])));

		$Prestador->appendChild($Cnpj);
		$Prestador->appendChild($InscricaoMunicipal);

		// TSomador
		$Tomador = $dom->createElement("Tomador");
		$IdentificacaoTomador = $dom->createElement("IdentificacaoTomador");
		$CpfCnpj = $dom->createElement("CpfCnpj");

		$TomadorCpf = $dom->createElement("Cpf", $this->soNumeros($this->c['cpf']));
		$TomadorCnpj = $dom->createElement("Cnpj", $this->soNumeros($this->c['cnpj']));

		if ($this->c['cpf'] != "") {
			$CpfCnpj->appendChild($TomadorCpf);
		} else {
			$CpfCnpj->appendChild($TomadorCnpj);
		}

		$IdentificacaoTomador->appendChild($CpfCnpj);

		$RazaoSocial = $dom->createElement("RazaoSocial", $this->tiraAcentos($this->c['razaoSocial']));
		$EEndereco = $dom->createElement("Endereco");
		$Endereco = $dom->createElement("Endereco", $this->tiraAcentos(trim((string) $this->c['endereco'])));
		$Numero = $dom->createElement("Numero", $this->tiraAcentos(trim((string) $this->c['enderecoNumero'])));
		$Bairro = $dom->createElement("Bairro", $this->tiraAcentos(trim((string) $this->c['enderecoBairro'])));
		$CodigoMunicipio = $dom->createElement("CodigoMunicipio", $this->soNumeros($this->c['enderecoIbgeMunicipio']));
		$Uf = $dom->createElement("Uf", trim((string) $this->c['enderecoUf']));
		$Cep = $dom->createElement("Cep", $this->soNumeros($this->c['enderecoCep']));

		$EEndereco->appendChild($Endereco);
		$EEndereco->appendChild($Numero);
		$EEndereco->appendChild($Bairro);
		$EEndereco->appendChild($CodigoMunicipio);
		$EEndereco->appendChild($Uf);
		$EEndereco->appendChild($Cep);

		$Tomador->appendChild($IdentificacaoTomador);
		$Tomador->appendChild($RazaoSocial);
		$Tomador->appendChild($EEndereco);

		if ($this->c['email'] != "") {
			$Contato = $dom->createElement("Contato");
			$Email = $dom->createElement("Email", $this->tiraAcentos($this->c['email']));
			$Contato->appendChild($Email);
			$Tomador->appendChild($Contato);
		}

		$infRps->appendChild($Prestador);
		$infRps->appendChild($Tomador);

		// Serviços
		$Rps->appendChild($infRps);

		$ListaRps = $dom->createElement("ListaRps");
		$ListaRps->appendChild($Rps);

		$LoteRps = $dom->createElement("LoteRps");
		$LoteRps->setAttribute("id", $idLote);

		$NumeroLote = $dom->createElement("NumeroLote", $this->c['numeroLote']);
		$QuantidadeRps = $dom->createElement("QuantidadeRps", 1);
		$Cnpj = $dom->createElement("Cnpj", $this->c['propriaCnpj']);
		$InscricaoMunicipal = $dom->createElement("InscricaoMunicipal", $this->soNumerosIsento($this->tiraPontos($this->c['propriaInscricaoMunicipal'])));

		$EnviarLoteRpsEnvio = $dom->createElement("EnviarLoteRpsEnvio");
		$EnviarLoteRpsEnvio->setAttribute("xmlns", "http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd");

		$LoteRps->appendChild($NumeroLote);
		$LoteRps->appendChild($Cnpj);
		$LoteRps->appendChild($InscricaoMunicipal);
		$LoteRps->appendChild($QuantidadeRps);
		$LoteRps->appendChild($ListaRps);

		$EnviarLoteRpsEnvio->appendChild($LoteRps);

		$dom->appendChild($EnviarLoteRpsEnvio);

		$xml = $dom->saveXML();

		$xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '<?xml version="1.0" encoding="UTF-8" standalone="no"?>', $xml);
		$xml = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>', '', $xml);
		$xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml);
		$xml = str_replace("\n", "", $xml);
		$xml = str_replace("  ", " ", $xml);
		$xml = str_replace("  ", " ", $xml);
		$xml = str_replace("  ", " ", $xml);
		$xml = str_replace("  ", " ", $xml);
		$xml = str_replace("  ", " ", $xml);
		$xml = str_replace("> <", "><", $xml);

		$this->nfexml = $xml;
		$this->assina("InfRps");
		$this->assina("LoteRps");
		$this->nfexml = str_replace('<?xml version="1.0"?>', '', $this->nfexml);
	}


	/**
	* Gera arquivo TXT para importação da SEFAZ de Simões Filho
	*/
	function geraTxtNFSE(): void
	{
		$hoje = date("Ymd");
		$dataHora = date("YmdHis");

		// REGISTRO 1 - Header do arquivo
		$s = "1"; // Tipo de Registro
		$s .= "103"; // Vesão do Layout
		$s .= padrl($this->c['propriaInscricaoMunicipal'], 26, "r"); // Insc. Municipal do prestador
		$s .= "2"; // Indicador de CPF/CNPJ -> 1 = CPF , 2 = CNPJ
		$s .= padrl($this->c['propriaCnpj'], 14, "l"); // CPF / CNPJ do prestador
		$s .= $this->c['propriaOptante']; // Optante pelo simples
		$s .= $hoje; // Data de início do período
		$s .= $hoje; // Data de fim do período
		$s .= padrl("1", 5, "l", "0"); // Qtd de NFS-e informadas
		$s .= padrl(" ", 324, "l"); // Preencher com 324 espações em branco
		$s .= padrl($this->c['numeroLote'], 8, "l", "0"); // Sequencial do registro
		$s .= NL;

		foreach ($this->i as $item) {
			// REGISTRO 2 - Cabeçalho da NFS-e
			$s .= "2"; // Tipo de registro
			$s .= padrl($this->nfeid, 20, "l", "0"); // Sequencial da NFS-e
			$s .= $dataHora; // Data e Hora da NFS-e
			$s .= $item['tipoRecolhimento']; // Tipo de recolhimento (N - Normal ou R - Retido na fonte)
			$s .= "T"; // Situação da nota fiscal (T - I - F - C - E - J)
			$s .= padrl(" ", 8, "r", " "); // Data de cancelamento
			$s .= $this->c['propriaEnderecoIbgeMunicipio']; // Codigo IBGE do municipio de prestação do serviço
			$s .= padrl(number_format($item['valor'], 2, "", ""), 15, "l", "0"); // Valor do serviço
			$s .= padrl(number_format($item['valorDeducoes'], 2, "", ""), 15, "l", "0"); // valor das deduções
			$s .= padrl(number_format($item['valorIss'], 2, "", ""), 15, "l", "0"); // Valor da retenção do PIS
			$s .= padrl(number_format($item['valorCofins'], 2, "", ""), 15, "l", "0"); // Valor da retenção do COFINS
			$s .= padrl(number_format($item['valorInss'], 2, "", ""), 15, "l", "0"); // Valor da retenção do INSS
			$s .= padrl(number_format($item['valorIr'], 2, "", ""), 15, "l", "0"); // Valor da retenção do IR
			$s .= padrl(number_format($item['valorRetCsll'], 2, "", ""), 15, "l", "0"); // Valor da retenção do CSLL
			$s .= padrl(number_format($item['valorIssqn'], 2, "", ""), 15, "l", "0"); // Valor do ISSQN
			$s .= padrl(" ", 219, "l"); // Preencher com 219 espações em branco
			$s .= padrl($this->c['numeroLote'], 8, "l", "0"); // Sequencial do registro
			$s .= NL;

			// REGISTRO 3 - Identificação do tomador da NFS-e
			$s .= "3"; // Tipo de registro
			$s .= padrl($this->nfeid, 20, "r", "0"); // Sequencial da NFS-e
			$s .= "2"; // Indicador de CPF/CNPJ do Tomador
			$s .= padrl($this->soNumeros($this->c['cnpj']), 14, "l"); // CPF/CNPJ do tomador
			$s .= padrl($this->tiraAcentos($this->c['razaoSocial']), 50, "r"); // Nome do tomador (Nome ou razão social)
			$s .= padrl($this->tiraAcentos($this->c['empresa']), 50, "r"); // Nome fantasia
			$s .= padrl(" ", 3, "l"); // Tipo de endereço do tomador
			$s .= padrl($this->tiraAcentos($this->c['endereco']), 50, "r"); // Endereço do tomador
			$s .= padrl($this->c['enderecoNumero'], 10, "l", "0"); // Número do endereço do tomador
			$s .= padrl($this->tiraAcentos($this->c['enderecoComplemento']), 20, "r"); // Complemento do endereço do tomador
			$s .= padrl($this->tiraAcentos($this->c['enderecoBairro']), 30, "r"); // Bairro do tomador
			$s .= padrl($this->tiraAcentos($this->c['enderecoCidade']), 50, "r"); // Cidade do tomador
			$s .= padrl($this->tiraAcentos($this->c['enderecoUf']), 2, "r"); // UF do tomador
			$s .= padrl($this->soNumeros($this->c['enderecoCep']), 8, "r"); // CEP do tomador
			$s .= padrl($this->tiraAcentos($this->c['email']), 60, "r"); // Email do tomador
			$s .= padrl(" ", 22, "l"); // Preencher com 22 espações em branco
			$s .= padrl($this->c['numeroLote'], 8, "l", "0"); // Sequencial do registro
			$s .= NL;

			// REGISTRO 4 - Descrição da NFS-e
			$s .= "4"; // Tipo de registro
			$s .= padrl($this->nfeid, 20, "l", "0"); // Sequencial da NFS-e
			$s .= padrl(" ", 255, "l"); // Descrição da nota
			$s .= padrl(" ", 115, "l"); // Preencher com 22 espações em branco
			$s .= padrl($this->c['numeroLote'], 8, "l", "0"); // Sequencial do registro
			$s .= NL;

			// REGISTRO 5 - Descrição do serviço realizado
			$s .= "5"; // Tipo de registro
			$s .= padrl($this->nfeid, 20, "l", "0"); // Sequencial da NFS-e
			$s .= padrl($this->soNumeros($item['codigo']), 4, "l"); // Codigo do serviço pretasdo
			$s .= padrl($this->soNumeros($item['codigoMunicipio']), 20, "l"); // Código tributação município
			$s .= padrl(number_format($item['valor'], 2, "", ""), 15, "l", "0"); // Valor do serviço
			$s .= padrl(number_format($item['valorDeducoes'], 2, "", ""), 15, "l", "0"); // Valor dedução
			$s .= padrl(number_format($item['aliquota'], 2, "", ""), 4, "l", "0"); // Alíquota
			$s .= padrl($item['unidade'], 20, "l"); // Unidade
			$s .= padrl(number_format($item['quantidade'], 2, "", ""), 8, "l", "0"); // Quantidade
			$s .= padrl($this->tiraAcentos($item['discriminacao']), 255, "r", " "); // Descrição do serviço
			$s .= padrl(" ", 20, "l"); // Alvará
			$s .= padrl(" ", 9, "l"); // Preencher com 9 espações em branco
			$s .= padrl($this->c['numeroLote'], 8, "l", "0"); // Sequencial do registro
			$s .= NL;
		}

		// REGISTRO 6 - Indicador de final de arquivo
		$s .= "6"; // Tipo de registro
		$s .= padrl(" ", 390, "l"); // Preencher com 390 espações em branco
		$s .= padrl($this->c['numeroLote'], 8, "l", "0"); // Sequencial do registro
		$s .= NL;

		$sql = "UPDATE nfse SET txt = '$s' WHERE id = " . $this->nfeid;
		gQuery($sql);

		$this->arqtxt = $s;
	}


	function envelopa(): void
	{
		// Envelopa
		$idLote = "1";

		// cria DOM pro nfexml
		$dom = new DOMDocument();
		$dom->formatOutput = true;
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($this->nfexml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);

		//cria o objeto DOM para o novo xml
		$dom2 = new DOMDocument('1.0', 'UTF-8');
		$dom2->formatOutput = true;
		$dom2->preserveWhiteSpace = false;

		$node = $dom->getElementsByTagName("Rps")->item(0);
		$Rps  = $node->C14N(FALSE, FALSE, NULL, NULL);

		$l .= '<LoteRps id="' . $idLote . '">';
		$l .= '<NumeroLote>' . $this->c['numero'] . '</NumeroLote>';
		$l .= '<Cnpj>' . $this->c['propriaCnpj'] . '</Cnpj>';
		$l .= '<InscricaoMunicipal>' . $this->soNumerosIsento($this->tiraPontos($this->c['propriaInscricaoMunicipal'])) . '</InscricaoMunicipal>';
		$l .= '<QuantidadeRps>1</QuantidadeRps>';
		$l .= '<ListaRps>' . $Rps . '</ListaRps>';
		$l .= '</LoteRps>';
		$this->nfexml = $l;
		$this->assina("LoteRps");
		$this->nfexml = str_replace('<?xml version="1.0"?>', '', $this->nfexml);
		$this->nfexml = '<?xml version="1.0" encoding="UTF-8"?><EnviarLoteRpsEnvio xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd">' . $this->nfexml . '</EnviarLoteRpsEnvio>';
	}


	function limpaNumero($num)
	{
		return(str_replace(".00", "", floatval($num)));
	}


	/**
	* Assina XML
	* @global type $http_lib
	* @param type $tag
	* @return type
	*/
	function assina($tag = 'infNFe')
	{
		global $http_lib;
		$sai = false;
		$nfefile = $this->nfexml;
		$this->nfeTools = new gNFeTools($this->config());
		if ($tag == 'infNFe') {
			if ($this->nfeTools->errMsg == "") {
				if ($signn = $this->nfeTools->signXML($nfefile[0], $tag)) {
					unlink($this->arqxml);
					$tmpChave = $this->c['raizNfe']."assinadas/".$this->chave."-nfe.xml";
					if (!file_put_contents($tmpChave, $signn)) {
						$this->erros[] = M . "Houve uma falha ao salvar a NFe assinada.";
					} else {
						$this->nfexml = $signn;
						$sai = true;
					}
				} else {
					$this->erros[] = M . "Houve uma falha ao assinar a NFe.";
				}
			} else {
				$this->erros[] = M . $this->nfeTools->errMsg . " (" . $this->nfeTools->cert . ")";
			}
		} else {
			$this->nfexml = $this->nfeTools->__signXMLNFSe($nfefile, $tag);
		}
		return($sai);
	}


	/**
	* Valida XML assinado
	* @global type $gPathLib
	* @global type $gPathDefault
	* @param type $tipo
	* @return type
	*/
	function valida($tipo = "nfe")
	{
		global $gPathLib, $gPathDefault;
		$arq = $gPathLib != "" ? $gPathLib . NFEPHP . "/schemes/" : $gPathDefault . NFEPHP . "/schemes/";

		if ($tipo == "nfe") {
			$xsd = $this->versao=='4.00' ? $arq . $this->schemes . '/nfe_v4.00.xsd' : $arq . $this->schemes . '/nfe_v3.10.xsd';
		} else {
			$xsd = $arq . '/NFSE_SSA/nfse.xsd';
		}

		$sai = $this->nfeTools->validXML($this->nfexml, $xsd);
		if (!$sai) {
			$e=explode("\n",$this->nfeTools->errMsg);
			foreach ($e as $el) {
				if (trim($el) !== "") {
					$this->erros[] = trim($el);
				}
			}
		}
		return($sai);
	}


	/**
	* Enviar para Sefaz
	* @param type $xml
	* @param type $numero
	* @return type
	*/
	function envia($xml = "", $numero = "")
	{
		$sai = true;
		if (!is_object($this->nfeTools)) {
			$this->nfeTools = new gNFeTools($this->config());
		}

		$sql = "SELECT * FROM nfe_numeros WHERE id_armazens='{$this->id_empresa}'"; // numero do lote
		$rs = dbQuery($sql)[0];
		if (!$rs) {
			$numeroLote = 1;
			$sql = "INSERT INTO nfe_numeros (id_armazens,numero) VALUES ('{$numeroLote}', '{$this->id_empresa}')";
			dbQuery($sql);
		} else {
			$numeroLote=$rs["numero"];
		}

		/*
		else {
			$numeroLote = intval($rs['numero']) + 1;
		}
		$sql = "UPDATE nfe_numeros SET numero='{$numeroLote}' WHERE id_armazens='{$this->id_empresa}'";
		dbQuery($sql);
		*/
		if ($xml == "") {
			$xml = $this->nfexml;
		}

		$ret = $this->nfeTools->autoriza($xml, $numeroLote);
		if (is_array($ret)) {
			if ($ret['bStat']) { // Se houve comunicação
				$this->erros[]=$ret['cStat']." - ".$ret['xMotivo'];
				$this->recibo=$ret['nRec'];
				$sai=true;
			} else {
				$this->erros[] = M . $this->nfeTools->errMsg;
				$sai = true;
			}
		} else {
			$this->erros[] = M . $this->nfeTools->errMsg;
			$sai = false;
		}

		return($sai);
	}


	/**
	* Enviar para Sefaz
	* @param type $xml
	* @param type $numero
	* @return type
	*/
	function enviaNfse($xml = "", $numero = "")
	{
		$sai = true;
		if (!is_object($this->nfeTools)) {
			$this->nfeTools = new gNFeTools($this->config());
		}

		if ($xml == "") {
			$xml = $this->nfexml;
		}

		$xml = str_replace("\n", '', $xml);
		if ($ret = $this->nfeTools->sendRps($xml, $this->c['numeroLote'])) {

			if ($ret['Protocolo'] != '') {
				// Nova aceita... Salva no banco
				$sql = "UPDATE nfse_numeros SET numero_nota=" . $this->c['numeroNota'] . " WHERE ambiente=" . $this->c['ambiente'] . " and id_empresa=" . $this->id_empresa;
				gQuery($sql);
				$sql = "UPDATE nfse SET situacao = 'Aceita', xml = '$xml', data_recibo = '" . $ret['DataRecebimento'] . "',protocolo = '" . $ret['Protocolo'] . "' WHERE id = " . $this->nfeid;
				gQuery($sql);
				$this->protocolo = $ret['Protocolo'];
				$this->numeroNota = $numeroNota;
				$this->numeroLote = $numeroLote;
			} else {
				$sql = "UPDATE nfse SET situacao='Rejeitada',xml='$xml',mensagens='Codigo " . $ret['Codigo'] . "<br>" . $ret['Mensagem'] . "<br>" . $ret['Correcao'] . "' WHERE id = " . $this->nfeid;
				gQuery($sql);
				$this->erros[] = str_pad((string) $ret['Codigo'], 10, ' ') . " " . $ret['Mensagem'] . "<br>" . $ret['Correcao'];
				$sai = false;
			}

		} else {
			$sql = "UPDATE nfse SET situacao = 'Nao enviada',xml='$xml',mensagens='Codigo " . $ret['Codigo'] . "<br>" . $ret['Mensagem'] . "<br>" . $ret['Correcao'] . "' WHERE id = " . $this->nfeid;
			gQuery($sql);
			$this->erros[] = str_pad('E000', 10, ' ') . " Nao foi possivel enviar para a SEFAZ, provavelmente por problema no certificado.";
			$sai = false;
		}

		return($sai);
	}


	function consultaRps($protocolo)
	{
		if (!is_object($this->nfeTools)) {
			$this->nfeTools = new gNFeTools($this->config());
		}

		return($this->nfeTools->consultRps($this->c['propriaCnpj'], $this->soNumerosIsento($this->tiraPontos($this->c['propriaInscricaoMunicipal'])), trim((string) $protocolo)));
	}


	function consultaSituacaoRps($protocolo)
	{
		$sai = true;
		if (!is_object($this->nfeTools)) {
			$this->nfeTools = new gNFeTools($this->config());
		}

		$sai = $this->nfeTools->consultSitRps($this->c['propriaCnpj'], $this->soNumerosIsento($this->tiraPontos($this->c['propriaInscricaoMunicipal'])), trim((string) $protocolo));
		/*
		Código de situação de lote de RPS
		1 – Não Recebido
		2 – Não Processado
		3 – Processado com Erro
		4 – Processado com Sucesso
		*/
		return($sai['Situacao']);
	}


	/**
	* Enviar para Sefaz
	* @param type $recibo
	* @param type $chave
	* @return type
	*/
	function protocolo($recibo = "", $chave = "")
	{
		$sai = true;
		if (!is_object($this->nfeTools)) {
			$this->nfeTools = new gNFeTools($this->config());
		}

		$rc = $ch = "";
		if ($recibo == "") {
			$ch = $chave;
		} else {
			$rc = $recibo;
		}

		$aRet = $this->nfeTools->getProtocol($rc, $ch);
		if ($aRet) {
			// Retornando, pega o protocolo e inclui no XML
			if (
				($aRet['bStat'])
				&& (intval($aRet['cStat']) < 201)
				&& (intval($aRet['cStat']) != 105)
				&& (intval($aRet['cStat']) != 106)
			) {
				//montar a NFe com o protocolo
				$cfg = $this->config();
				$tmp = $this->c['raizNfe'] . "temporarias/" . $chave;
				$protFile = $tmp . '-prot.xml';
				$nfeFile  = $this->c['raizNfe']."assinadas/".$chave."-nfe.xml";

				if (!file_exists($protFile)) {
					$protFile = $this->c['raizNfe'] . "temporarias/$recibo-recprot.xml";
				}

				$this->protfile = $protFile;
				//protocolo do lote enviado
				$prot = new DOMDocument(); //cria objeto DOM
				$prot->formatOutput = false;
				$prot->preserveWhiteSpace = false;
				//carrega o protocolo e seus dados
				$xmlprot = file_get_contents($protFile);
				$prot->loadXML($xmlprot, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
				$protNFe = $prot->getElementsByTagName("protNFe")->item(0);

				if (is_object($protNFe)) {
					$protver  = trim($protNFe->getAttribute("versao"));
					$tpAmb    = $protNFe->getElementsByTagName("tpAmb")->item(0)->nodeValue;
					$verAplic = $protNFe->getElementsByTagName("verAplic")->item(0)->nodeValue;
					$chNFe    = $protNFe->getElementsByTagName("chNFe")->item(0)->nodeValue;
					$dhRecbto = $protNFe->getElementsByTagName("dhRecbto")->item(0)->nodeValue;
					$nProt    = $protNFe->getElementsByTagName("nProt")->item(0)->nodeValue;
					$digVal   = $protNFe->getElementsByTagName("digVal")->item(0)->nodeValue;
					$cStat 	  = $protNFe->getElementsByTagName("cStat")->item(0)->nodeValue;
					$xMotivo  = $protNFe->getElementsByTagName("xMotivo")->item(0)->nodeValue;
				} else {
					$protver  = $prot->getElementsByTagName("versaoDados")->item(0)->nodeValue;
					$tpAmb    = $prot->getElementsByTagName("tpAmb")->item(0)->nodeValue;
					$verAplic = $prot->getElementsByTagName("verAplic")->item(0)->nodeValue;
					$chNFe    = $prot->getElementsByTagName("chNFe")->item(0)->nodeValue;
					$dhRecbto = $prot->getElementsByTagName("dhRecbto")->item(0)->nodeValue;
					$nProt    = $prot->getElementsByTagName("nProt")->item(0)->nodeValue;
					$digVal   = $prot->getElementsByTagName("digVal")->item(0)->nodeValue;
					$cStat    = $prot->getElementsByTagName("cStat")->item(0)->nodeValue;
					$xMotivo  = $prot->getElementsByTagName("xMotivo")->item(0)->nodeValue;
				}

				$this->erros[] = str_pad($cStat, 10, ' ') . " " . $xMotivo;
				if (is_file($protFile) && is_file($nfeFile)) {

					$procnfe = $this->nfeTools->addProt($nfeFile, $protFile);
					if ($cStat == 100) {
						//NFe aprovada
						$pasta = $this->nfeTools->aprDir;
					} // endif

					if ($cStat == 110) {
						//NFe denegada
						$pasta = $this->nfeTools->denDir;
					} // endif

					if ($cStat > 200) {
						//NFe reprovada
						$pasta = $this->nfeTools->repDir;
					} // endif

					//arquivo da NFe com o protocolo
					$prot = new DOMDocument(); //cria objeto DOM
					$prot->formatOutput = false;
					$prot->preserveWhiteSpace = false;
					$prot->loadXML($procnfe);
					$this->nfexml = $prot->saveXML();

					// Salvando o numero do protocolo no banco
					$sql = "UPDATE nfe SET protocolo = '$nProt', recibo = '$recibo', data_recibo = '" . date("Y-m-d H:i:s") . "' WHERE chave = '$chave'";
					dbQuery($sql);
					//salvar a NFe com o protocolo na pasta
					if ($prot->save($pasta . $chave . '-nfe.xml')) {
						//se o arquivo foi gravado na pasta destino com sucesso
						//remover os arquivos das outras pastas
						unlink($nfeFile);
						unlink($protFile);
					} //endif

				} //endif
			} else {
				$this->erros[] = str_pad((string) $aRet['cStat'], 10, ' ') . " " . $aRet['xMotivo'];
			}

		} else {
			echo $this->nfeTools->errMsg;
		}

		return($sai);
	}


	function inutiliza($ano, $numero, $justificativa = "Falha no sistema")
	{
		$numIni = $numFim = intval($numero);
		$sai = true;
		if (!is_object($this->nfeTools)) {
			$this->nfeTools = new gNFeTools($this->config());
		}

		$aRet = $this->nfeTools->inutNF($ano, intval($this->c['serie']), $numIni, $numFim, strtoupper((string) $this->tiraAcentos($justificativa)));
		if ($aRet['bStat']) {
			if (trim((string) $aRet['cStat']) !== '102') {
				$sai = false;

			}
			$this->erros[] = $aRet['cStat']." - ".$aRet['xMotivo'];
		} else {
			$sai = false;
			$this->erros[] = $this->nfeTools->errMsg;
		}

		return($sai);
	}


	/**
	* Carta de correção
	* @global type $usr_id
	* @param type $id_nfe
	* @param type $correcao
	* @return boolean
	*/
	function cartaCorrecao($id_nfe, $correcao)
	{
		global $usr_id;

		// Verificando o número sequencial
		$sql = "SELECT
					max(c.sequencial) seq,
					n.id,
					n.chave
				FROM nfe n
				LEFT JOIN nfe_carta_correcao c ON c.id_nfe=n.id
				WHERE n.id=$id_nfe
				GROUP BY n.id,n.chave";
		$rs = gQuery($sql);
		$chave = $rs->fields['chave'];
		$errStatus = false;
		$errMsg = [];

		if ($chave == '' || $correcao == '') {
			$errStatus = true;
			$errMsg[]  = "Dados para a carta de correção não podem ser vazios";
		}

		if (strlen((string) $chave) != 44) {
			$errStatus = true;
			$errMsg[]  = "Uma chave de NFe válida não foi passada como parâmetro.";
		}

		if (strlen($correcao) < 15 || strlen($correcao) > 1000) {
			$errStatus = true;
			$errMsg[]  = "O texto da correção deve ter entre 15 e 1000 caracteres!";
		}

		$xCorrecao = $correcao;

		//Caso "seq" venha null ou seja a primeira CCe
		if (intval($rs->fields['seq']) == 0) {
			$sql = "SELECT max(sequencial) seq FROM nfe_carta_correcao WHERE id_nfe=$id_nfe";
			$rs  = gQuery($sql);
		}
		$nSeqEvento = intval($rs->fields['seq']) + 1; //Sequencial do evento
		//se o numero sequencial do evento não foi informado ou se for maior que 1 digito
		if (strlen($nSeqEvento) > 2) {
			$errStatus = true;
			$errMsg[]  = "Número sequencial da correção não encontrado ou é maior que 99 [$nSeqEvento]";
		}

		if ($errStatus) {
			return $errMsg;
		}

		if (!is_object($this->nfeTools)) {
			$this->nfeTools = new gNFeTools($this->config());
		}

		$aRet = $this->nfeTools->envCCe($chave, $xCorrecao, $nSeqEvento);
		if ($aRet) {
			$datahora = date("Y-m-d H:i:s");
			$sql = "INSERT INTO nfe_carta_correcao (id_pessoas, id_nfe, datahora, texto, xml, confirmado, sequencial)
					VALUES (" . intval($usr_id) . ",$id_nfe,'$datahora','$correcao','" . $aRet. "',1,$nSeqEvento)";
			gQuery($sql);
			$retorno=[];
			$retorno['true'] = true;
			$retorno['motivo'] = "Evento registrado e vinculado a NF-e";
			return $retorno;
		}

		$retorno['motivo'] = str_replace("\n","<br>",$this->nfeTools->errMsg);
		return $retorno;
	}


	/**
	* Imprime CCe
	* @global type $gPath
	* @global type $gPathImg;
	* @param type $gId
	* @param type $emp
	* @param type $dest
	*/
	function cce($gId, $emp, $dest = "",$logo=""): void
	{
		global $gPath, $gPathImg;

		$sql = gSQLLimit("SELECT * FROM nfe_carta_correcao WHERE id_nfe = $gId AND confirmado = 1 ORDER BY id DESC", 1);
		$rs  = gQuery($sql);

		$sql = "SELECT chave FROM nfe WHERE id = " . $gId;
		$rsNfe = gQuery($sql);

		if (!$rs->EOF) {
			$docxml = $rs->fields['xml'];
			$img = $gPath . 'files/nfe/logo_nfe.jpg';
			if (!file_exists($img)) {
				$img = $gPathImg . "logo_app.jpg";
			}

			if($logo != "" && file_exists($logo)){
				$img = $logo;
			}

			if ($dest == "") {
				$sairPara = "I";
				$nomeArq  = $rsNfe->fields['chave'] . "-" . $rs->fields['sequencial'] . "-CCe.pdf";
			} else {
				$sairPara = "F";
				$nomeArq  = "/tmp/" . $rsNfe->fields['chave'] . "-" . $rs->fields['sequencial'] . "-CCe.pdf";
			}

			$aEnd = [
				'razao' => $emp->fields['propriaRazaoSocial'],
				'logradouro' => $emp->fields['propriaEndereco'],
				'numero' => $emp->fields['propriaEnderecoNumero'],
				'complemento' => $emp->fields['propriaEnderecoComplemento'],
				'bairro' => $emp->fields['propriaEnderecoBairro'],
				'CEP' => $emp->fields['propriaEnderecoCep'],
				'municipio' => $emp->fields['propriaEnderecoMunicipio'],
				'UF' => $emp->fields['propriaEnderecoUf'],
				'telefone' => $emp->fields['propriaTelefone'],
				'email' => ''
			];

			$cce = new DacceNFePHP($docxml, 'P', 'A4', $img, $sairPara, $aEnd, '', 'Times', 1);
			$cce->printDACCE($nomeArq, $sairPara);
		}
	}


	/**
	* Cancela NFe
	* @param type $chave
	* @param type $protocolo
	* @param type $justificativa
	* @return type
	*/
	function cancela($chave, $protocolo, $justificativa = "Dados incorretos")
	{
		$sai = true;
		if (!is_object($this->nfeTools)) {
			$this->nfeTools = new gNFeTools($this->config());
		}

		$aRet = $this->nfeTools->cancelEvent($chave, $protocolo, strtoupper((string) $this->tiraAcentos($justificativa)));
		if ($aRet) {
			$sql = "UPDATE nfe SET xml_cancelamento='" .addslashes($aRet). "' WHERE chave='{$chave}'";
			dbQuery($sql);
			$sai = true;
			$this->erros[]="NF-e Cancelada";
		} else {
			$sai = false;
			$e = explode("\n",$this->nfeTools->errMsg);
			foreach ($e as $el) {
				if (trim($el) !== "") {
					$this->erros[] = $el;
				}
			}

		}
		return($sai);
	}


	/**
	* Faz download do XML gerado
	* @param type $tipo
	* @param type $name
	* @param type $type
	* @param type $down
	*/
	function baixa($tipo = "xml", $name = false, $type = false, $down = true): void
	{
		$file = $tipo == "xml" ? $this->arqxml : $this->arqtxt;
		if (!file_exists($file)) {
			exit;
		}

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . basename((string) $file));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		ob_clean();
		flush();
		readfile($file);
	}


	/**
	* Cria no BD
	* @param type $tipo
	* @return type
	*/
	function cria($tipo = "nfe")
	{
		global $EMPRESA;
		if ($tipo == "nfe") {
			$hoje = gDBDateTime(date("d-m-y H:i:s"));
			$usrId = $_SESSION['usrId'] > 0 ? $_SESSION['usrId'] : $_SESSION['usr_id'];
			//gLog("-============>".$this->id_empresa);
			if ($EMPRES == "inter" && $_SERVER['REMOTE_ADDR'] != "127.0.0.1") {
				$id_empresa = match ($this->id_empresa) {
					1 => 2,
					2 => 4,
					3 => 8,
					4 => 14,
					5 => 6,
					6 => 5,
					14 => 4,
					default => $this->id_empresa,
				};

				if (
					$id_empresa != 14
					&& $this->id_empresa != 8
					&& $this->id_empresa != 7
					&& $this->id_empresa != 12
					&& $this->id_empresa != 16
				) {
					if ($this->id_empresa == 3) {
						$id_empresa = 8;
					} // TMI

					$con 	= mssql_connect("192.168.10.5","sa", "cablev35");
					$db  	= mssql_select_db("gsapp_wms", $con);
					$result = mssql_query("select * from nfe_numeros where id_armazens=".$id_empresa." and serie='" . $this->c['serie'] . "' ORDER BY id DESC ",$con);
					$row 	= mssql_fetch_assoc($result);
					$numero = $row['numero'] + 1;

				} elseif ($id_empresa == 14) {
					$sql = "SELECT * FROM nfe_numeros
							WHERE id_armazens = $id_empresa AND serie = ".$this->c['serie']."";
					$rs  = dbQuery($sql);

					if (!$rs) {
						$numero = 1;
						$sql = "INSERT INTO nfe_numeros (id_armazens,numero,serie)
								VALUES ($id_empresa,0,".$this->c['serie'].")";
						dbQuery($sql);
					} else {
						$numero = intval($rs[0]['numero']) + 1;
					}

				} else {
					$sql = "SELECT * FROM nfe_numeros
							WHERE id_armazens = " . $id_empresa . " AND serie = '" . $this->c['serie'] . "' ";
					$rs  = dbQuery($sql);

					if (!$rs) {
						$numero = 1;
						$sql = "INSERT INTO nfe_numeros (id_armazens,numero,serie)
								VALUES (" . $id_empresa . ",0,'" . $this->c['serie'] . "')";
						dbQuery($sql);
					} else {
						$numero = intval($rs[0]['numero']) + 1;
					}

				}

			} else {
				$sql = "SELECT * FROM nfe_numeros
						WHERE id_armazens = " . $this->id_empresa . " AND serie='" . $this->c['serie'] . "' ";
				$rs  = dbQuery($sql);
				if (!$rs) {
					$numero = 1;
					$sql = "INSERT INTO nfe_numeros (id_armazens,numero,serie)
							VALUES (" . $this->id_empresa . ",0,'" . $this->c['serie'] . "')";
					dbQuery($sql);
				} else {
					$numero = intval($rs[0]['numero']) + 1;
				}
			}

			/*
			$hoje = gDBDateTime(date("d-m-y H:i:s"));
			$usrId = $_SESSION['usrId'] > 0 ? $_SESSION['usrId'] : $_SESSION['usr_id'];
			$sql = "select * from nfe_numeros where id_armazens=2 and serie='" . $this->c['serie'] . "' ";
			$rs = dbQuery($sql);
			if (count($rs)==0) {
				$numero = 1;
				$sql = "INSERT INTO nfe_numeros (id_armazens,numero,serie)
						VALUES (" . $this->id_empresa . ",0,'" . $this->c['serie'] . "')";
				dbQuery($sql);
			} else {
				$numero = intval($rs[0]['numero']) + 1;
			}
			*/
			//gLog("==----------==============".$numero);

			$this->c['numero'] = $numero;
			$this->c['dataEmissao']  = $hoje;
			$this->c['codigoAcesso'] = substr($this->id_empresa . $usrId . date("HiYsdm"), 0, 7);
			$this->c['digitoVerificador'] = $this->modulo_11($this->c['codigoAcesso']);
			$this->c['codigoAcesso'] .= $this->c['digitoVerificador'];
			$this->c['numeroCompleto'] = $this->c['codigoAcesso'] . $this->c['digitoVerificador'];

		} else  {
			$hoje  = gDBDateTime(date("d-m-y H:i:s"));
			$usrId = $_SESSION['usrId'] > 0 ? $_SESSION['usrId'] : $_SESSION['usr_id'];
			$sql = "SELECT * FROM nfse_numeros
					WHERE ambiente=" . $this->c['ambiente'] . " AND id_empresa=" . $this->id_empresa;
			$rs  = gQuery($sql);

			if ($rs->EOF) {
				$sql = "INSERT INTO nfse_numeros (id_empresa,ambiente,numero_lote,numero_nota)
						VALUES (" . $this->id_empresa . "," . $this->c['ambiente'] . ",0,0)";
				gQuery($sql);
				$numeroLote = 1;
				$numeroNota = 1;
			} else {
				$numeroLote = intval($rs->fields['numero_lote']) + 1;
				$numeroNota = intval($rs->fields['numero_nota']) + 1;
				// Independente do resultado, sempre muda o número do lote
				$sql = "UPDATE nfse_numeros SET numero_lote = " . $numeroLote . " WHERE ambiente = " . $this->c['ambiente'] . " AND id_empresa = " . $this->id_empresa;
				gQuery($sql);
			}

			$dataHora = date("Y-m-d H:i:s");
			$sql = "INSERT INTO nfse (sistema,situacao,data,id_os,id_nota,numero,lote,id_empresa,id_cliente,id_pessoa)
					VALUES ('" . $this->c['sistema'] . "','Criada','" . $dataHora . "'," . intval($this->c['idOs']) . "," . intval($this->c['idNota']) . ",'$numeroNota','$numeroLote'," . $this->id_empresa . "," . $this->c['idCliente'] . "," . $this->c['idPessoa'] . ")";
			gQuery($sql);
			$sql = "SELECT id FROM nfse WHERE id_os = " . intval($this->c['idOs']) . " AND numero = '$numeroNota' AND lote = '$numeroLote' AND data = '$dataHora'";
			$rs  = gQuery($sql);
			$this->nfeid = $rs->fields['id'];
			$this->c['numeroLote'] = $numeroLote;
			$this->c['numeroNota'] = $numeroNota;
		}
		return($this->c['id']);
	}


	/**
	* Salva no BD
	* @param type $situacao
	* @param type $tipo
	*/
	function salva($situacao = 'Assinada', $tipo = "nfe"): void
	{
		global $EMPRESA;
		$hoje = $this->c['dataEmissao'];
		$usrId = $_SESSION['usrId'] > 0 ? $_SESSION['usrId'] : $_SESSION['usr_id'];
		// Atualiza contador de numeracao das NF-e
		if ($EMPRESA == "inter" && $_SERVER['REMOTE_ADDR'] != "127.0.0.1") {
			$id_empresa = match ($this->id_empresa) {
				1 => 2,
				2 => 4,
				3 => 8,
				4 => 14,
				5 => 6,
				6 => 5,
				default => $this->id_empresa,
			};

			if (
				$id_empresa != 14
				&& $this->id_empresa != 8
				&& $this->id_empresa != 7
				&& $this->id_empresa != 12
				&& $this->id_empresa != 16
			) {
				if ($this->id_empresa == 3) {
					$id_empresa = 8;
				}

				$con = mssql_connect("192.168.10.5","sa", "cablev35");
				$db  =  mssql_select_db("gsapp_wms", $con);
				$numero = intval($this->c['numero']);

				$sql = "SELECT * FROM {$tipo}_numeros
						WHERE id_armazens = " . $id_empresa . " AND serie = '" . $this->c['serie'] . "' ";
				gLog("MSSQL :: ".$sql);
				$result = mssql_query($sql, $con);
				$rs 	= mssql_fetch_assoc($result);

				$sql = "UPDATE {$tipo}_numeros SET numero = numero+1 WHERE id = " . $rs['id'];
				gLog("MSSQL :: ".$sql);
				mssql_query($sql,$con);

				$sql = "UPDATE {$tipo}_numeros SET numero = numero+1 WHERE id = " . $rs['id'];
				dbQuery($sql);
			} elseif($id_empresa == 14) {
				$sql = "SELECT * FROM nfe_numeros WHERE id_armazens = {$id_empresa} AND serie = ".$this->c['serie']."";
				$rs = dbQuery($sql);

				if (!$rs) {
					$numero = 1;
					$sql = "INSERT INTO nfe_numeros (id_armazens, numero, serie)
							VALUES ($id_empresa,0,".$this->c['serie'].")";
					dbQuery($sql);
				} else {
					$numero = intval($rs[0]['numero']) + 1;
					$sql = "UPDATE nfe_numeros SET numero = numero+1 WHERE id = " . $rs[0]['id'];
					dbQuery($sql);
				}

			} else {
				$sql = "SELECT * FROM nfe_numeros WHERE id_armazens = " . $id_empresa . " AND serie = '" . $this->c['serie'] . "' ";
				$rs = dbQuery($sql);

				if (!$rs) {
					$numero = 1;
					$sql = "INSERT INTO nfe_numeros (id_armazens,numero,serie)
							VALUES (" . $id_empresa . ",0,'" . $this->c['serie'] . "')";
					dbQuery($sql);
				} else {
					$numero = intval($rs[0]['numero']) + 1;
					$sql = "UPDATE nfe_numeros SET numero = numero+1 WHERE id = " . $rs[0]['id'];
					dbQuery($sql);
				}

			}

		} else {
			$sql = "SELECT * FROM {$tipo}_numeros
					WHERE id_armazens=" . $this->id_empresa . " AND serie = '" . $this->c['serie'] . "' ";
			$rs  = dbQuery($sql)[0];
			$numero = intval($this->c['numero']);
			$sql = "UPDATE {$tipo}_numeros SET numero = numero+1 WHERE id = " . $rs['id'];
			dbQuery($sql);
		}

		// Cria registro no banco da NF-e criada
		if ($tipo == "nfe") {
			$mtz = [];
			$mtz['sistema'] = $this->c['sistema'];
			$mtz['situacao'] = 'Assinada';
			$mtz['id_empresa'] = $this->id_empresa;
			$mtz['id_pessoa'] = $_SESSION['usrId'];
			$mtz['id_cliente'] = intval($this->c['idCliente']);
			$mtz['id_os'] = intval($this->c['idProgramacao']);
			$mtz['id_notas'] = intval($this->c['idNotas']);
			$mtz['data'] = $hoje;
			$mtz['cancelada'] = 0;
			$mtz['enviada'] = 0;
			$mtz['numero'] = str_pad((string) $this->c['numero'], 9, "0", STR_PAD_LEFT);
			$mtz['chave'] = $this->nfeid;
			$mtz['txt']= $this->nfetxt;
			$mtz['xml']= $this->nfexml;
			$mtz['id_operacao'] =  $this->id_operacao;
			$mtz['serie'] = $this->c['serie'];
			$sql="INSERT INTO nfe (".implode(",",array_keys($mtz)).") VALUES ('".implode("','",array_values($mtz))."')";
			// $sql = "insert into nfe (sistema,situacao,id_empresa,id_pessoa,id_cliente,data,cancelada,enviada,numero,chave,txt,xml,id_operacao,serie)
			// values ('" . $this->c['sistema'] . "','Assinada'," . $this->id_empresa . ",$usrId," . intval($this->c['idCliente']) . ",'$hoje',0,0,'" . str_pad($this->c['numero'], 9, "0", STR_PAD_LEFT) . "','" . $this->nfeid . "','" . $this->nfetxt . "','" . $this->nfexml . "'," . $this->id_operacao . ",'" . $this->c['serie'] . "')";
		} else {
			$sql = "INSERT INTO nfe (sistema,situacao,id_empresa,id_pessoa,id_cliente,data,numero,chave,xml)
					VALUES ('" . $this->c['sistema'] . "','Assinada'," . $this->id_empresa . ",$usrId," . intval($this->c['idCliente']) . ",'$hoje','" . str_pad((string) $this->c['numero'], 9, "0", STR_PAD_LEFT) . "','" . $this->nfeid . "','" . $this->nfexml . "')";
		}

		dbQuery($sql);
		$ultimoId = dbQuery("SELECT id FROM nfe ORDER BY id DESC");
		//$sql="update notas set id_nfe='{$ultimoId}' where id_notas=''"
	}


	/**
	* Carregar do BD
	* @param type $numero
	* @return type
	*/
	function carrega($numero)
	{
		$sql = "SELECT * FROM nfe WHERE numero = '$numero'";
		return(dbQu($sql));
	}


	/**
	* Emite DANFE
	* @global type $gPath
	* @global type $gPathImg
	* @param type $gId
	* @param type $dest
	*/
	function danfe($gId, $dest = "",$logo=""): void
	{
		global $gPath, $gPathImg;
		$sql = "SELECT * FROM nfe WHERE id=$gId";
		$rs = dbQuery($sql)[0];
		if ($rs) {
			$docxml = $rs['xml'];
			$img = $gPath . 'files/nfe/logo_nfe.jpg';
			if (!file_exists($img)) {
				$img = $gPathImg . "logo_app.jpg";
			}

			if($logo != "" && file_exists($logo)){
				$img=$logo;
			}

			if ($dest == "") {
				$sairPara = "I";
				$nomeArq = $rs['chave'] . '-nfe.pdf';
			} else {
				$sairPara = "F";
				$nomeArq = is_dir($dest) ? $dest."/". $rs['chave'] . '-nfe.pdf' : $this->tmp . $rs['chave'] . '-nfe.pdf';
			}

			$danfe = new DanfeNFePHP($docxml, 'P', 'A4', $img, $sairPara, '', 'Arial');
			$id = $danfe->montaDANFE();
			$teste = $danfe->printDANFE($nomeArq, $sairPara);
		}
	}


	// Verifica status do servidor da SEFAZ
	function verificaStatus()
	{
		if (!is_object($this->nfeTools)) {
			$this->nfeTools = new gNFeTools($this->config());
		}

		header('Content-type: text/html; charset=UTF-8');

		$UF    = trim(strtoupper((string) $this->c['propriaEnderecoUf']));
		$tpAmb = $this->c['ambiente'];
		$resp  = $this->nfeTools->statusServico($UF, $tpAmb);
		if ($resp) {
			$this->erros[] = $resp;
			$sAmb = $tpAmb == 1 ? 'Produ&ccedil;&atilde;o' : 'Homologa&ccedil;&atilde;o';
			$resp['ambiente'] = $UF . " - " . $sAmb;
		} else {
			$res['Erro']= $this->nfeTools->errMsg;
		}

		return($resp);
	}


	function leCertificado()
	{
		global $gPathLib, $gPathDefault;
		$arq = $gPathLib != "" ? $gPathLib . NFEPHP . "/certs/" : $gPathDefault . NFEPHP . "/certs/";

		//carrega as propriedades da classe com as configurações
		$cert  = $this->c['propriaCnpj'] . ".pfx";
		$pCert = $arq . $cert;

		if (file_exists($pCert)) {

			if (!is_object($this->nfeTools)) {
				$this->nfeTools = new gNFeTools($this->config());
			}

			// Certificado
			$key = file_get_contents($pCert);
			openssl_pkcs12_read($key, $x509certdata, $this->c['senhaCertificado']);
			$data = openssl_x509_read($x509certdata['cert']);
			$cert_data = openssl_x509_parse($data);
			$resp['Arquivo'] = $cert;
			$resp['Empresa'] = $cert_data['subject']['CN'];
			$resp['E-mail']  = $cert_data['subject']['emailAddress'];
			$resp['País'] = $cert_data['subject']['C'];
			$resp['Certificadora'] = $cert_data['subject']['O'];
			$resp['Tipo de certificado'] = $cert_data['subject']['OU'][2];
			$resp['Fornecedora'] = $cert_data['issuer']['OU'] . " / " . $cert_data['issuer']['CN'];
			$ano = substr((string) $cert_data['validTo'], 0, 2);
			$mes = substr((string) $cert_data['validTo'], 2, 2);
			$dia = substr((string) $cert_data['validTo'], 4, 2);

			//obtem o timeestamp da data de validade do certificado
			$dValid = date("d-m-Y", gmmktime(0, 0, 0, $mes, $dia, $ano));
			$resp['Validade'] = $dValid;
		} else {
			$resp['xMotivo'] = "Certificado não encontrado ($cert)";
		}

		return($resp);
	}

	function tiraAcentos($t)
	{
		global $gPathLib;
		// $antes = html_entity_decode($t);
		if ($gPathLib != "") {
			$pa = ["a", "e", "i", "o", "u", "o", "o", "a", "e"];
			$de = ["á", "é", "í", "ó", "ú", "°", "º", "ª", "&"];
			$t  = str_replace($de, $pa, $t);
			$de = ["à", "è", "ì", "ò", "ù"];
			$t  = str_replace($de, $pa, $t);
			$de = ["â", "ê", "î", "ô", "û"];
			$t  = str_replace($de, $pa, $t);
			$t  = str_replace("ã", "a", $t);
			$t  = str_replace("õ", "o", $t);
			$t  = str_replace("ç", "c", $t);
			$t  = str_replace("Ç", "C", $t);
			$t  = autoencode($t);
			$pa = ["A", "E", "I", "O", "U"];
			$de = ["Á", "É", "Í", "Ó", "Ú"];
			$t  = str_replace($de, $pa, $t);
			$de = ["À", "È", "Ì", "Ò", "Ù"];
			$t  = str_replace($de, $pa, $t);
			$de = ["Â", "Ê", "Î", "Ô", "Û"];
			$t  = str_replace($de, $pa, $t);
			$de = ["Ã", "Õ", "Ç"];
			$pa = ["A", "O", "C"];
			$t  = str_replace($de, $pa, $t);
			$de = [""];
			$pa = ["E"];
			$t  = str_replace($de, $pa, $t);
		} else {
			$t = strtr($t, mb_convert_encoding("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&", 'ISO-8859-1'), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
			$t = strtr($t, mb_convert_encoding("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&", 'UTF-8', 'ISO-8859-1'), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
			$t = strtr($t, "áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&", "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
		}

		return iconv('ISO-8859-1', 'ASCII//TRANSLIT//IGNORE', $t);
	}


	function tiraEstranhos($var)
	{
		$var = $this->tiraAcentos($var);
		return(preg_replace('/[^a-z0-9\+\-\=\.\,\!\?\:\;\@\%\&\(\)\{\}\<\>\[\]\s\'\$\/]+/i ', '', (string) $var));
	}


	function soNumeros($var)
	{
		return(preg_replace('/[^0-9]+/i ', '', (string) $var));
	}


	function soNumerosIsento($var)
	{
		$var = $this->soNumeros($var);
		if ($var == "") {
			return "ISENTO";
		}

		return($var);
	}


	function tiraPontos($t_st)
	{
		return(str_replace('/', '', str_replace(")", "", str_replace("(", "", str_replace(" ", "", str_replace(".", "", str_replace("-", "", $t_st)))))));
	}


	function modulo_11($num, $base = 9, $r = 0)
	{
		/**
		* Autor:
		* Pablo Costa <pablo@users.sourceforge.net>
		*
		* Função:
		* Calculo do Modulo 11 para geracao do digito verificador
		* de boletos bancarios conforme documentos obtidos
		* da Febraban - www.febraban.org.br
		*
		* Entrada:
		* $num: string numérica para a qual se deseja calcularo digito verificador;
		* $base: valor maximo de multiplicacao [2-$base]
		* $r: quando especificado um devolve somente o resto
		*
		* Saída:
		* Retorna o Digito verificador.
		*
		* Observações:
		* - Script desenvolvido sem nenhum reaproveitamento de código pré existente.
		* - Assume-se que a verificação do formato das variáveis de entrada é feita antes da execução deste script.
		*/
		$soma  = 0;
		$fator = 2;

		/* Separacao dos numeros */
		for ($i = strlen((string) $num); $i > 0; $i--) {
			// pega cada numero isoladamente
			$numeros[$i] = substr((string) $num, $i - 1, 1);
			// Efetua multiplicacao do numero pelo falor
			$parcial[$i] = $numeros[$i] * $fator;
			// Soma dos digitos
			$soma += $parcial[$i];

			if ($fator == $base) {
				// restaura fator de multiplicacao para 2
				$fator = 1;
			}

			$fator++;
		}
		/* Calculo do modulo 11 */
		if ($r == 0) {
			$soma *= 10;
			$digito = $soma % 11;
			if ($digito == 10) {
				return 0;
			}
			return $digito;
		}

		/* Calculo do modulo 11 */
		if ($r == 1) {
			return $soma % 11;
		}

		return;
	}
}

/* $xml - Arquivo xml
* $lc  - Indica se tira ou não os zeros do codigo do item
*/

function importaNfe($xml, $lc = true)
{
	$doc = new DOMDocument();
	$doc->preservWhiteSpace = FALSE; //elimina espaços em branco
	$doc->formatOutput = FALSE;
	$doc->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
	$node = $doc->getElementsByTagName('infNFe')->item(0);
	//obtem a versão do layout da NFe
	$dados['versao'] = trim($node->getAttribute("versao"));
	$dados['chave'] = substr(trim($node->getAttribute("Id")), 3);

	// Reconhecimento dos campos do XML
	if ($dados['versao'] === "3.10") {
		$dados['dataRecibo'] = substr((string) tagValue($doc, "dhRecbto"), 0, 19);
		$dados['dataEmissao'] = substr((string) tagValue($doc, "dhEmi"), 0, 19);
		$dados['dataMovimento'] = substr((string) tagValue($doc, "dhSaiEnt"), 0, 19);

		if (trim($dados['dataMovimento']) === "") {
			$dados['dataMovimento']= $dados['dataEmissao'];
		}

	} else {
		$dados['dataRecibo'] 	= tagValue($doc, "dhRecbto");
		$dados['dataEmissao'] 	= tagValue($doc, "dEmi");
		$dados['dataMovimento'] = tagValue($doc, "dSaiEnt") . " " . tagValue($doc, "hSaiEnt");
	}

	$dados['numero'] 					= tagValue($doc, "nNF");
	$dados['modelo'] 					= tagValue($doc, "mod");
	$dados['serie']  					= tagValue($doc, "serie");
	$dados['naturezaOperacao']  		= tagValue($doc, "natOp");
	$dados['modalidadeFrete']   		= tagValue($doc, "modFrete");
	$dados['totalDesconto']     		= tagValue($doc, "vDesc");
	$dados['totalFrete']  				= tagValue($doc, "vFret");
	$dados['totalSeguro'] 				= tagValue($doc, "vSeg");
	$dados['totalProdutos'] 			= tagValue($doc, "vProd");
	$dados['totalOutros']   			= tagValue($doc, "vOutro");
	$dados['totalNota'] 				= tagValue($doc, "vNF");
	$dados['informacoesFisco']  		= tagValue($doc, "infAdFisco");
	$dados['informacoesComplementares'] = tagValue($doc, "infCpl");

	$emi = $doc->getElementsByTagName('emit')->item(0);
	$c1  = tagValue($emi, "CNPJ");
	$c2  = substr((string) $c1, 0, 2) . "." . substr((string) $c1, 2, 3) . "." . substr((string) $c1, 5, 3) . "/" . substr((string) $c1, 8, 4) . "-" . substr((string) $c1, 12, 2);
	$dados['emitenteCnpj'] 			     = $c1;
	$dados['emitenteCnpjFormatado']      = $c2;
	$dados['emitenteRazaoSocial'] 	     = tagValue($emi, "xNome");
	$dados['emitenteNome'] 			     = tagValue($emi, "xFant");
	$dados['emitenteInscricaoEstadual']  = tagValue($emi, "IE");
	$dados['emitenteInscricaoMunicipal'] = tagValue($emi, "IM");
	$dados['emitenteCnae'] 				 = tagValue($emi, "CNAE");
	$dados['emitenteEndereco'] 			 = tagValue($emi, "xLgr");
	$dados['emitenteNumero'] 			 = tagValue($emi, "nro");
	$dados['emitenteBairro'] 			 = tagValue($emi, "xBairro");
	$dados['emitenteMunicipio'] 		 = tagValue($emi, "xMun");
	$dados['emitenteMunicipioIbge'] 	 = tagValue($emi, "cMun");
	$dados['emitenteCep'] 				 = tagValue($emi, "CEP");
	$dados['emitenteUF'] 				 = tagValue($emi, "UF");
	$dados['emitentePaisIbge'] 			 = tagValue($emi, "cPais");
	$dados['emitentePais'] 				 = tagValue($emi, "xPais");
	$dados['emitenteTelefone'] 			 = tagValue($emi, "fone");

	$dst = $doc->getElementsByTagName('dest')->item(0);
	$c1  = tagValue($dst, "CNPJ");
	$c2  = substr((string) $c1, 0, 2) . "." . substr((string) $c1, 2, 3) . "." . substr((string) $c1, 5, 3) . "/" . substr((string) $c1, 8, 4) . "-" . substr((string) $c1, 12, 2);
	$dados['destinatarioCnpj'] 				 = $c1;
	$dados['destinatarioCnpjFormatado'] 	 = $c2;
	$dados['destinatarioRazaoSocial'] 		 = tagValue($dst, "xNome");
	$dados['destinatarioNome'] 				 = tagValue($dst, "xFant");
	$dados['destinatarioInscricaoEstadual']  = tagValue($dst, "IE");
	$dados['destinatarioInscricaoMunicipal'] = tagValue($dst, "IM");
	$dados['destinatarioEndereco'] 			 = tagValue($dst, "xLgr");
	$dados['destinatarioNumero'] 			 = tagValue($dst, "nro");
	$dados['destinatarioBairro'] 			 = tagValue($dst, "xBairro");
	$dados['destinatarioMunicipio'] 		 = tagValue($dst, "xMun");
	$dados['destinatarioMunicipioIbge'] 	 = tagValue($dst, "cMun");
	$dados['destinatarioCep'] 				 = tagValue($dst, "CEP");
	$dados['destinatarioUF'] 				 = tagValue($dst, "UF");
	$dados['destinatarioPaisIbge'] 			 = tagValue($dst, "cPais");
	$dados['destinatarioPais'] 				 = tagValue($dst, "xPais");
	$dados['destinatarioTelefone'] 			 = tagValue($dst, "fone");

	$tra = $doc->getElementsByTagName('transp')->item(0);
	$c1  = tagValue($tra, "CNPJ");
	$c2  = substr((string) $c1, 0, 2) . "." . substr((string) $c1, 2, 3) . "." . substr((string) $c1, 5, 3) . "/" . substr((string) $c1, 8, 4) . "-" . substr((string) $c1, 12, 2);
	$dados['transportadoraCnpj'] 		  = $c1;
	$dados['transportadoraCnpjFormatado'] = $c2;
	$dados['transportadoraNome'] 		  = tagValue($tra, "xNome");
	$dados['transportadoraEndereco'] 	  = tagValue($tra, "xEnder");
	$dados['transportadoraUF'] 			  = tagValue($tra, "UF");

	$vei = $doc->getElementsByTagName('veicTransp')->item(0);
	$dados['veiculoPlaca'] = tagValue($vei, "placa");
	$dados['veiculoUF']    = tagValue($vei, "UF");

	$dados['pesoLiquido'] = floatval(tagValue($doc, "pesoL"));
	$dados['pesoBruto']   = floatval(tagValue($doc, "pesoB"));

	$dados['protocolo'] = tagValue($doc, "nProt");
	$det = $doc->getElementsByTagName('det');
	$aux = $det->item(0);
	$dados['CFOP'] = tagValue($aux, "CFOP");

	$itens = [];
	for ($i = 0; $i < $det->length; $i++) {
		$item = $det->item($i);
		$s = "";
		$codigo = tagValue($item, "cProd");

		if ($lc) {
			for ($a = 0; $a < strlen((string) $codigo); $a++) {
				if ($codigo[$a] == "0") {
					$codigo[$a] = " ";
				} else {
					$a = strlen((string) $codigo);
				}
			}
		}

		$codigo = trim((string) $codigo);
		$s['codigo'] = $codigo;
		$s['ean'] = tagValue($item, "cEAN");
		$s['nome'] = str_replace(["'", "\""], " ", tagValue($item, "xProd"));
		$s['ncm'] = gNcm(tagValue($item, "NCM"));
		$s['cfop'] = tagValue($item, "CFOP");
		$s['unidade'] = tagValue($item, "uCom");
		$s['quantidade'] = tagValue($item, "qCom");
		$s['valor'] = tagValue($item, "vUnCom");
		$s['valorTotal'] = tagValue($item, "vProd");

		$impostos=$item->getElementsByTagName('imposto')->item(0);

		$newdoc = new DOMDocument;
		$newdoc->formatOutput = true;
		$newdoc->loadXML("<XML></XML>");
		$impostos = $newdoc->importNode($impostos, true);
		$newdoc->documentElement->appendChild($impostos);

		$cc = XML2Array::createArray($newdoc);

		$k 		= key($cc['XML']['imposto']['ICMS']);
		$icms 	= $cc['XML']['imposto']['ICMS'][$k];
		$ipi 	= $cc['XML']['imposto']['IPI'];
		$pis 	= $cc['XML']['imposto']['ICMS'];
		$cofins = $cc['XML']['imposto']['COFINS'];

		$s['icms'] 	 = $icms;
		$s['ipi']    = $ipi;
		$s['pis']    = $pis;
		$s['cofins'] = $cofins;

		$itens[] = $s;
	}

	$dados['itens'] = $itens;
	return($dados);
}

function importaNfse($xml)
{
	$doc = new DOMDocument();
	$doc->preservWhiteSpace = FALSE; //elimina espaços em branco
	$doc->formatOutput = FALSE;
	$doc->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
	// $node = $doc->getElementsByTagName('infrps')->item(0);

	// Reconhecimento dos campos do XML
	$dados['numero'] 				 = tagValue($doc, "Numero");
	$dados['serie']  				 = tagValue($doc, "Serie");
	$dados['tipo']   				 = tagValue($doc, "Tipo");
	$dados['dataEmissao'] 			 = tagValue($doc, "DataEmissao");
	$dados['naturezaOperacao'] 		 = tagValue($doc, "NaturezaOperacao");
	$dados['optanteSimplesNacional'] = tagValue($doc, "OptanteSimplesNacional");
	$dados['incentivadorCultural'] 	 = tagValue($doc, "IncentivadorCultural");
	$dados['status'] 				 = tagValue($doc, "status");

	$prest = $doc->getElementsByTagName('Prestador')->item(0);
	$c1 = tagValue($prest, "Cnpj");
	$c2 = substr((string) $c1, 0, 2) . "." . substr((string) $c1, 2, 3) . "." . substr((string) $c1, 5, 3) . "/" . substr((string) $c1, 8, 4) . "-" . substr((string) $c1, 12, 2);
	$dados['prestadorCnpj'] = $c1;
	$dados['prestadorCnpjFormatado'] = $c2;
	$dados['inscricaoMunicipal'] = tagValue($prest, "InscricaoMunicipal");

	$tomad = $doc->getElementsByTagName('Tomador')->item(0);
	$c1 = tagValue($tomad, "CpfCnpj");
	if (strlen((string) $c1) <= 11) {
		// cpf
		$c2 = substr((string) $c1, 0, 3) . "." . substr((string) $c1, 3, 3) . "." . substr((string) $c1, 6, 3) . "-" . substr((string) $c1, 9, 2);
	} else {
		// cnpj
		$c2 = substr((string) $c1, 0, 2) . "." . substr((string) $c1, 2, 3) . "." . substr((string) $c1, 5, 3) . "/" . substr((string) $c1, 8, 4) . "-" . substr((string) $c1, 12, 2);
	}
	$dados['tomadorCpfCnpj'] = $c1;
	$dados['tomadorCpfCnpjFormatado'] = $c2;
	$dados['razaoSocial'] = tagValue($tomad, "RazaoSocial");

	$end = $doc->getElementsByTagName('Endereco')->item(0);
	$dados['endereco'] 			= tagValue($end, "Endereco");
	$dados['enderecoNumero'] 	= tagValue($end, "Numero");
	$dados['enderecoBairro'] 	= tagValue($end, "Bairro");
	$dados['codigoMunicipio']   = tagValue($end, "CodigoMunicipio");
	$dados['enderecoUf'] 		= tagValue($end, "Uf");
	$dados['enderecoCep'] 		= tagValue($end, "Cep");
	$dados['email'] 			= tagValue($doc, "Email");

	$det = $doc->getElementsByTagName('Servico');
	$itens = [];
	for ($i = 0; $i < $det->length; $i++) {
		$item = $det->item($i);
		$s = "";
		$s['itemListaServico'] = tagValue($item, "ItemListaServico");
		$s['codigoCnae'] 	   = tagValue($item, "CodigoCnae");
		$s['discriminacao']    = tagValue($item, "Discriminacao");
		$s['codigoMunicipio']  = tagValue($item, "CodigoMunicipio");
		$s['valorServicos']    = tagValue($item, "ValorServicos");
		$s['valorDeducoes']    = tagValue($item, "ValorDeducoes");
		$s['issRetido'] 	   = tagValue($item, "IssRetido");
		$s['valorIssRetido']   = tagValue($item, "ValorIssRetido");
		$s['valorIss'] 		   = tagValue($item, "ValorIss");
		$s['baseCalculo'] 	   = tagValue($item, "BaseCalculo");
		$s['aliquota'] 		   = tagValue($item, "Aliquota");
		$itens[] = $s;
	}
	$dados['itens'] = $itens;
	return($dados);
}


// Pra garantir a formatação do NCM
function gNcm($ncm): string
{
	$ncm = str_replace(".", "", $ncm);
	return(substr($ncm, 0, 4) . "." . substr($ncm, 4, 2) . "." . substr($ncm, 6, 2));
}


function tagValue(&$dom, $tag)
{
	if (is_object($dom)) {
		return $dom->getElementsByTagName($tag)->item(0)->nodeValue;
	}

	return("");
}

// Alinha de acordo com os parametros passados
function padrl($txt, $qtd, $alinha, $complemento = " "): string
{
	if ($alinha == "r") {
		return (str_pad(substr((string) $txt, 0, $qtd), $qtd, $complemento, STR_PAD_RIGHT));
	}

 	return (str_pad(substr((string) $txt, 0, $qtd), $qtd, $complemento, STR_PAD_LEFT));
}


class XML2Array
{

	private static $xml = null;
	private static string $encoding = 'UTF-8';

	/**
	* Initialize the root XML node [optional]
	* @param $version
	* @param $encoding
	* @param $format_output
	*/
	public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true): void {
		self::$xml = new DOMDocument($version, $encoding);
		self::$xml->formatOutput = $format_output;
		self::$encoding = $encoding;
	}


	/**
	* Convert an XML to Array
	* @param string $node_name - name of the root node to be converted
	* @param array $arr - aray to be converterd
	* @return DOMDocument
	*/
	public static function &createArray($input_xml) {
		$xml = self::getXMLRoot();

		if (is_string($input_xml)) {
			$parsed = $xml->loadXML($input_xml);
			if (!$parsed) {
				throw new Exception('[XML2Array] Error parsing the XML string.');
			}

		} else {

			if ($input_xml::class !== 'DOMDocument') {
				throw new Exception('[XML2Array] The input XML object should be of type: DOMDocument.');
			}

			$xml = self::$xml = $input_xml;
		}
		$array[$xml->documentElement->tagName] = self::convert($xml->documentElement);
		self::$xml = null;    // clear the xml node in the class for 2nd time use.
		return $array;
	}


	/**
	* Convert an Array to XML
	* @param mixed $node - XML as a string or as an object of DOMDocument
	* @return mixed
	*/
	private static function &convert($node) {
		$output = [];

		switch ($node->nodeType) {
			case XML_CDATA_SECTION_NODE:
			$output['@cdata'] = trim($node->textContent);
			break;

			case XML_TEXT_NODE:
			$output = trim($node->textContent);
			break;

			case XML_ELEMENT_NODE:

			// for each child node, call the covert function recursively
			for ($i = 0, $m=$node->childNodes->length; $i < $m; $i++) {
				$child = $node->childNodes->item($i);
				$v = self::convert($child);
				if (isset($child->tagName)) {
					$t = $child->tagName;
					// assume more nodes of same kind are coming
					if (!isset($output[$t])) {
						$output[$t] = [];
					}

					$output[$t][] = $v;
				} elseif ($v !== '') {
					//check if it is not an empty text node
					$output = $v;
				}
			}

			if (is_array($output)) {
				// if only one node of its kind, assign it directly instead if array($value);
				foreach ($output as $t => $v) {
					if (is_array($v) && count($v) == 1) {
						$output[$t] = $v[0];
					}
				}

				if ($output === []) {
					//for empty nodes
					$output = '';
				}
			}

			// loop through the attributes and collect them
			if ($node->attributes->length) {
				$a = [];
				foreach($node->attributes as $attrName => $attrNode) {
					$a[$attrName] = (string) $attrNode->value;
				}

				// if its an leaf node, store the value in @value instead of directly storing it.
				if (!is_array($output)) {
					$output = ['@value' => $output];
				}

				$output['@attributes'] = $a;
			}
			break;
		}
		return $output;
	}

	/*
	* Get the root XML node, if there isn't one, create it.
	*/
	private static function getXMLRoot()
	{
		if (empty(self::$xml)) {
			self::init();
		}

		return self::$xml;
	}

}