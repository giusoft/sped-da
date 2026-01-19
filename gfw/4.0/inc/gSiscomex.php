<?php

class Siscomex
{
	private $url;
	private $erros;
	private $dadosObrigatorios;
	private $parametroReenvioAutomatico;
	private $urlHomologacao;
	private $urlProducao;
	private $conexaoGs;
	private $bancoGs;
	private $ipBancoGs;
	private $usuarioGs;
	private $senhaGs;
	private $portaGs;
	private $ambienteProducao;
	private $setup;
	private $horaInicialFuncionamento;
	private $horaFinalFuncionamento;


	public function __construct()
	{
		//LEITURA DO SETUP SISCOMEX
		$caminhoSetup = $_SERVER['DOCUMENT_ROOT'].'/gs/giusoft/';
		$nomeArquivoSetup = 'setup_siscomex.ini';
		$this->setup = parse_ini_file($caminhoSetup.$nomeArquivoSetup, true);

		//DADOS DE CONEXAO DE BASE DE DADOS GS
		$this->bancoGs   = $this->setup['gs']['banco'];
		$this->ipBancoGs = $this->setup['gs']['ip'];
		$this->usuarioGs = $this->setup['gs']['usuario'];
		$this->senhaGs   = $this->setup['gs']['senha'];
		$this->portaGs   = $this->setup['gs']['porta'];
		$this->conectarGs();

		//DADOS SISCOMEX
		$this->parametroReenvioAutomatico = (bool) $this->setup['siscomex']['parametroReenvioAutomatico'];
		$this->ambienteProducao = (bool) $this->setup['siscomex']['ambienteProducao'];
		$this->urlHomologacao   = $this->setup['siscomex']['urlHomologacao'];
		$this->urlProducao      = $this->setup['siscomex']['urlProducao'];
		$this->url = ($this->ambienteProducao) ? $this->urlProducao : $this->urlHomologacao;
		$this->horaInicialFuncionamento = $this->setup['siscomex']['horaInicialFuncionamento'];
		$this->horaFinalFuncionamento = $this->setup['siscomex']['horaFinalFuncionamento'];
	}


	private function conectarGs()
	{
		$usuario = $this->usuarioGs;
		$senha   = $this->senhaGs;
		$stringConexao = 'mysql:host='.$this->ipBancoGs.';dbname='.$this->bancoGs;

		try {
			$this->conexaoGs = new \PDO($stringConexao, $usuario, $senha);
			$this->conexaoGs->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			echo 'Não foi possível conectar com banco de dados, verifique as credenciais!';
			//echo 'Erro da conexao: ' . $e->getMessage() . '\n';
			exit;
		}
	}


	private function consultarConfiguracoes()
	{
		$consultaConfiguracoes = "
			SELECT parametro, valor
			FROM siscomex_configuracoes";
		$query = $this->conexaoGs->prepare($consultaConfiguracoes);
		$siscomexConfiguracoes = $query->execute();
		$siscomexConfiguracoes = $query->fetchAll(PDO::FETCH_KEY_PAIR);

		return $siscomexConfiguracoes;
	}


	private function prepararJsonRequisicao($dadosEnvio)
	{
		return json_encode(array_merge($this->dadosObrigatorios, $dadosEnvio), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}


	private function consultarToken()
	{
		$ch = curl_init('http://192.168.10.50:93/teste/cert/integra1.php');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$resultado = curl_exec($ch);
		curl_close($ch);
		$tokens = json_decode($resultado, true);

		return $tokens;
	}


	private function capturarDadosObrigatorios($dadosEnvio)
	{
		$dataAgora = \DateTime::createFromFormat('U.u', microtime(true))->setTimezone(new \DateTimeZone('America/Bahia'));
		$dataAgora = $dataAgora->format('Y-m-d\TH:i:s.u');
		$dataAgora = substr($dataAgora, 0, -3).'-0300';//"FORMATO PADRAO SISCOMEX 2020-04-01T10:50:30.150-0300",
		/*
			//Quando ainda nao houver instancia da classe gSiscomex no local em que se queira formatar a data para o padrao(2020-04-01T10:50:30.150-0300), pode-se utilizar a seguinte closure.
			$dataFormatada = function ($data) {
				return substr(date_format(date_create($data), 'Y-m-d\TH:i:s.u'), 0 , -3)."-0300";
			};
		*/

		$consultaProximoId = "
			SELECT auto_increment  AS proximo_id
			FROM INFORMATION_SCHEMA.TABLES
			WHERE table_name = :nomeTabela";

		$query = $this->conexaoGs->prepare($consultaProximoId);
		$query->bindValue(':nomeTabela', 'siscomex_requisicoes', PDO::PARAM_STR);
		$query->execute();

		$idEvento = $query->fetch(PDO::FETCH_ASSOC);
		$idEvento = $idEvento['proximo_id'];
		$idEvento = empty($idEvento) ? '1' : (int) $idEvento;

		$dataHoraRegistro   = $dataAgora;
		if (is_array($dadosEnvio)
			&& empty($dadosEnvio['dataHoraOcorrencia'])
		) {
			$dataHoraOcorrencia = $dataAgora;
		} elseif (
			is_array($dadosEnvio)
			&& !empty($dadosEnvio['dataHoraOcorrencia'])
		) {
			$dataHoraOcorrencia = $dadosEnvio['dataHoraOcorrencia'];
			unset($dadosEnvio['dataHoraOcorrencia']);
		}

		$contingencia       = (bool) false;
		$codigoRecinto      = 5921304;//codigo da intermaritima no siscomex
		$dadosObrigatorios  = [
			'idEvento'           => $idEvento,
			'dataHoraOcorrencia' => $dataHoraOcorrencia,
			'dataHoraRegistro'   => $dataHoraRegistro,
			'contingencia'       => $contingencia,
			'codigoRecinto'      => $codigoRecinto];

		return $dadosObrigatorios;
	}


	private function prepararEnvioGeracaoLote($idOs)
	{
		$consultaRequisicaoAnterior = "
			SELECT  id,
					dados_enviados,
					protocolo
			FROM  gs.siscomex_requisicoes
			WHERE siscomex_requisicoes.id_geral_os = :idOs
				AND enviada = :enviada
				AND sucesso = :sucesso
				AND excluida = :excluida
				AND dados_enviados LIKE '%".'"I"'."%'";
		$idOs = (int)$idOs;
		$query = $this->conexaoGs->prepare($consultaRequisicaoAnterior);
		$query->bindParam(':idOs',     $idOs, PDO::PARAM_INT);
		$query->bindValue(':enviada',  1,     PDO::PARAM_INT);
		$query->bindValue(':sucesso',  1,     PDO::PARAM_INT);
		$query->bindValue(':excluida', 0,     PDO::PARAM_INT);
		$query->execute();
		$dadosRequisicaoAnterior = $query->fetch(PDO::FETCH_ASSOC);
		if (!empty($dadosRequisicaoAnterior)) {
			$dadosEnviados        = $dadosRequisicaoAnterior['dados_enviados'];
			$protocoloAnterior    = $dadosRequisicaoAnterior['protocolo'];
			$idRequisicaoAnterior = $dadosRequisicaoAnterior['id'];
			$dadosEnviados = str_replace('"tipoOperacao": "I"', '"tipoOperacao": "E", "protocoloEventoRetificadoOuExcluido":"'.$protocoloAnterior.'"', $dadosEnviados);
			$dadosEnviados = str_replace('"tipoOperacao": "R"', '"tipoOperacao": "E", "protocoloEventoRetificadoOuExcluido":"'.$protocoloAnterior.'"', $dadosEnviados);

			$atualizaDadosEnviados = "
				UPDATE siscomex_requisicoes
				SET id_geral_pessoa_enviou = :idGeralPessoa,
					dados_enviados = :dadosEnviados,
					enviada  = :enviada,
					sucesso  = :sucesso,
					excluida = :excluida
				WHERE id = :idRequisicao;";

			$query = $this->conexaoGs->prepare($atualizaDadosEnviados);
			$query->bindValue(':idGeralPessoa', 0, PDO::PARAM_INT);
			$query->bindParam(':dadosEnviados', $dadosEnviados,        PDO::PARAM_INT);
			$query->bindParam(':idRequisicao',  $idRequisicaoAnterior, PDO::PARAM_INT);
			$query->bindValue(':enviada',  1, PDO::PARAM_INT);
			$query->bindValue(':sucesso',  0, PDO::PARAM_INT);
			$query->bindValue(':excluida', 1, PDO::PARAM_INT);
			$query->execute();

			$retornoReenvio = $this->reenviarRequisicoes(false, $idRequisicaoAnterior);
		}
	}


	private function cadastrarRequisicaoSiscomex
	(
		$dadosEnviados,
		$operacao,
		$idOs,
		$idOpeImpCntrs,
		$idTblTransaction,
		$ultimaDataCredenciamento,
		$idSiscomexGeorreferencia
	) {
		$insereRequisicao = "
			INSERT INTO gs.siscomex_requisicoes (
				id_geral_pessoa_enviou,
				data_cadastro,
				data_envio,
				dados_enviados,
				operacao,
				id_geral_os,
				id_ope_imp_cntrs,
				id_tbl_transaction,
				ultima_data_credenciamento,
				id_siscomex_georreferencia
			) VALUES (
				:idGeralPessoaEnviou,
				:dataCadastro,
				:dataEnvio,
				:dadosEnviados,
				:operacao,
				:idGeralOs,
				:idOpeImpCntrs,
				:idTblTransaction,
				:ultimaDataCredenciamento,
				:idSiscomexGeorreferencia
			)";

		$usrId            = (int) $_SESSION['usrId'];
		$idOs             = (int) $idOs;
		$idOpeImpCntrs    = (int) $idOpeImpCntrs;
		$idTblTransaction = (int) $idTblTransaction;
		$ultimaDataCredenciamento = $ultimaDataCredenciamento;
		$idSiscomexGeorreferencia = (int) $idSiscomexGeorreferencia;

		$query = $this->conexaoGs->prepare($insereRequisicao);
		$query->bindParam(':idGeralPessoaEnviou',  $usrId,              PDO::PARAM_INT);
		$query->bindParam(':dataCadastro',         date('Y-m-d H:i:s'), PDO::PARAM_STR);
		$query->bindParam(':dataEnvio',            date('Y-m-d H:i:s'), PDO::PARAM_STR);
		$query->bindParam(':dadosEnviados',        $dadosEnviados,      PDO::PARAM_STR);
		$query->bindParam(':operacao',             $operacao,           PDO::PARAM_STR);
		$query->bindParam(':idGeralOs',            $idOs,               PDO::PARAM_INT);
		$query->bindParam(':idOpeImpCntrs',        $idOpeImpCntrs,      PDO::PARAM_INT);
		$query->bindParam(':idTblTransaction',     $idTblTransaction,   PDO::PARAM_INT);
		$query->bindParam(':ultimaDataCredenciamento', $ultimaDataCredenciamento, PDO::PARAM_STR);
		$query->bindParam(':idSiscomexGeorreferencia', $idSiscomexGeorreferencia, PDO::PARAM_INT);
		$query->execute();

		$consultaUltimoId = "SELECT MAX(id) AS ultimo_id FROM gs.siscomex_requisicoes;";

		$query = $this->conexaoGs->prepare($consultaUltimoId);
		$query->execute();

		$idRequisicao     = $query->fetch(PDO::FETCH_LAZY);
		$idRequisicao     = (int) $idRequisicao['ultimo_id'];

		return $idRequisicao;
	}


	private function atualizarSituacaoRequisicao($dadosRecebidos, $idRequisicao)
	{
		$enviada      = 1;
		$sucesso      = 0;
		$protocolo    = '';
		$idRequisicao = (int) $idRequisicao;

		if(is_object($dadosRecebidos)) {
			$sucesso        = 1;
			$protocolo      = $dadosRecebidos->protocolo;
			$dadosRecebidos = json_encode($dadosRecebidos);
		}

		$atualizaSituacao = "
			UPDATE  gs.siscomex_requisicoes
			SET     dados_recebidos = :dadosRecebidos,
					enviada         = :enviada,
					protocolo       = :protocolo,
					sucesso         = :sucesso
			WHERE  id = :idRequisicao;";
		$query = $this->conexaoGs->prepare($atualizaSituacao);
		$query->bindParam(':dadosRecebidos', $dadosRecebidos, PDO::PARAM_STR);
		$query->bindParam(':enviada',        $enviada,        PDO::PARAM_INT);
		$query->bindParam(':protocolo',      $protocolo,      PDO::PARAM_STR);
		$query->bindParam(':sucesso',        $sucesso,        PDO::PARAM_INT);
		$query->bindParam(':idRequisicao',   $idRequisicao,   PDO::PARAM_INT);
		$query->execute();
	}


	private function verificarErros($resultado)
	{
		if(isset($resultado->errosValidacao)) {
			$this->erros = "";
			foreach($resultado->errosValidacao as $chave => $erro) {
				$codigo   = $erro->codigo;
				$atributo = $erro->atributo;
				$detalhes = $erro->detalhes;
				$this->erros .= 'ERROR RESPONSE: '
					.'<br>Código de erro: '   .$codigo
					.'<br>Campo: '            .$atributo
					.'<br>Descrição do erro: '.$detalhes.'<br>';
			}

			return utf8_decode($this->erros);

		} elseif(isset($resultado->message)) {
			$message   = $resultado->message;
			$code      = $resultado->code;
			$field     = $resultado->field;
			$path      = $resultado->path;
			$tag       = $resultado->tag;
			$date      = $resultado->date;
			$detail    = $resultado->detail;
			$severity  = $resultado->severity;
			$info      = $resultado->info;
			$mnemonico = $info->mnemonico;
			$sistema   = $info->sistema;
			$ambiente  = $info->ambiente;
			$visao     = $info->visao;
			$usuario   = $info->usuario;
			$url       = $info->url;
			$fluxo     = $info->fluxo;
			$trackerId = $info->trackerId;
			$this->erros = 'ERROR RESPONSE: '
				.'<br>Message:   '.$message
				.'<br>Code:      '.$code
				.'<br>Field:     '.$field
				.'<br>Path:      '.$path
				.'<br>Tag:       '.$tag
				.'<br>Date:      '.$date
				.'<br>Detail:    '.$detail
				.'<br>Severity:  '.$severity
				.'<br>Mnemonico: '.$mnemonico
				.'<br>Sistema:   '.$sistema
				.'<br>Ambiente:  '.$ambiente
				.'<br>Visao:     '.$visao
				.'<br>Usuario:   '.$usuario
				.'<br>Url:       '.$url
				.'<br>Fluxo:     '.$fluxo;

			return utf8_decode($this->erros);
		}

		return $resultado;
	}


	private function verificarExisteRequisicaoOs($idOs, $operacao)
	{
		$consultaRequisicaoOs = "
			SELECT id
			FROM   gs.siscomex_requisicoes
			WHERE  id_geral_os = :idOs
				AND operacao   = :operacao LIMIT 1";

		$query = $this->conexaoGs->prepare($consultaRequisicaoOs);
		$query->bindParam(':idOs',     $idOs,     PDO::PARAM_INT);
		$query->bindParam(':operacao', $operacao, PDO::PARAM_STR);
		$query->execute();

		$existeRequisicaoOs = $query->fetch(PDO::FETCH_ASSOC);

		return $existeRequisicaoOs;
	}


	private function enviarRequisicao
	(
		$servico,
		$dadosEnvio,
		$operacao,
		$idOs,
		$idOpeImpCntrs,
		$idTblTransaction,
		$ultimaDataCredenciamento,
		$idSiscomexGeorreferencia
	) {

		if (
			$operacao    == 'avariaExtravioLote'
			|| $operacao == 'geracaoLotes'
		) {
			if($this->verificarExisteRequisicaoOs($idOs, $operacao)){
				if($operacao == 'geracaoLotes'){
					$this->prepararEnvioGeracaoLote($idOs);
				} else {
					return true;
				}
			}
		}

		$token    = $this->consultarToken();
		$this->dadosObrigatorios = $this->capturarDadosObrigatorios($dadosEnvio);
		$curlBody = (is_array($dadosEnvio)) ? $this->prepararJsonRequisicao($dadosEnvio) : $dadosEnvio;
		$idRequisicao = $this->cadastrarRequisicaoSiscomex(
			$curlBody,
			$operacao,
			$idOs,
			$idOpeImpCntrs,
			$idTblTransaction,
			$ultimaDataCredenciamento,
			$idSiscomexGeorreferencia
		);

		$curlHeader   = [
						'Content-Type: application/json',
						'Role-Type:DEPOSIT',
						"Authorization: Bearer " . $token["access_token"],
						"Authorization-Pucomex:" . $token["jwt_pucomex"]
						];

		
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL            => $this->url . $servico,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_POST           => true,
			CURLOPT_HTTPHEADER     => $curlHeader,
			CURLOPT_POSTFIELDS     => $curlBody
		));

		$resultado = curl_exec($curl);
		if($resultado === false) {
			$erro       = curl_error($curl);
			$codigoErro = curl_errno($curl);
			$resultado  = 'ERROR RESPONSE: '
				.'<br>CÓDIGO DO ERRO CURL: '.$codigoErro
				.'<br>ERRO DE CURL: '       .$erro;
		} else {
			$resultado = (json_decode($resultado)) ?: $resultado;
			$resultado = $this->verificarErros($resultado);
		}

		curl_close($curl);
		$this->atualizarSituacaoRequisicao($resultado, $idRequisicao);

		return $resultado;
	}


	public function reenviarRequisicoes($reenvioAutomatico = true, $idRequisicao)
	{
		if(
			$reenvioAutomatico !== true
			|| $this->parametroReenvioAutomatico === true
		) {
			$condicionalIdRequisicao = '';
			if(!empty($idRequisicao)) {
				$condicionalIdRequisicao = " AND siscomex_requisicoes.id = :idRequisicao";
			}

			$consultaRequisicoesPendentes = "
				SELECT  id,
						dados_enviados,
						operacao,
						id_geral_os,
						id_ope_imp_cntrs,
						id_tbl_transaction,
						ultima_data_credenciamento,
						id_siscomex_georreferencia
				FROM   gs.siscomex_requisicoes
				WHERE  sucesso  = :sucesso
					AND enviada = :enviada"
				.$condicionalIdRequisicao
				." ORDER BY id;";

			$query = $this->conexaoGs->prepare($consultaRequisicoesPendentes);
			$query->bindValue(':sucesso', 0, PDO::PARAM_INT);
			$query->bindValue(':enviada', 1, PDO::PARAM_INT);
			if(!empty($condicionalIdRequisicao)){
				$query->bindParam(':idRequisicao', $idRequisicao, PDO::PARAM_INT);
			}
			$query->execute();

			$requisicoesPendentes = $query->fetchAll(PDO::FETCH_ASSOC);

			foreach($requisicoesPendentes as $requisicao) {

				$atualizaEnviadoPendente = "
					UPDATE gs.siscomex_requisicoes
					SET    enviada = :enviada
					WHERE  id = :idRequisicao;";

				$query = $this->conexaoGs->prepare($atualizaEnviadoPendente);
				$query->bindParam(':idRequisicao', $requisicao['id'], PDO::PARAM_INT);
				$query->bindValue(':enviada',      2,                 PDO::PARAM_INT);
				$query->execute();

				$dadosRecebidos = $this->{$requisicao['operacao']} (
					$requisicao['dados_enviados'],
					$requisicao['id_geral_os'],
					$requisicao['id_ope_imp_cntrs'],
					$requisicao['id_tbl_transaction'],
					$requisicao['ultima_data_credenciamento'],
					$requisicao['id_siscomex_georreferencia']
				);//chama a funcao de acordo com a operacao. Por exemplo: operacao de 'armazenamentoLote', chama funcao de mesmo nome, ou seja, armazenamentoLote($parametros)
				if(is_object($dadosRecebidos)) {
					$dadosRecebidos = json_encode($dadosRecebidos);
				}

				$atualizaRequisicaoReenviada = "
					UPDATE gs.siscomex_requisicoes
					SET dados_enviados  = :dados_enviados,
						dados_recebidos = :dadosRecebidos,
						data_envio      = :dataAtual,
						enviada         = :enviada
						WHERE id = :idRequisicao;";

				$query = $this->conexaoGs->prepare($atualizaRequisicaoReenviada);
				$query->bindParam(':dados_enviados', $requisicao['dados_enviados'], PDO::PARAM_STR);
				$query->bindParam(':dadosRecebidos', $dadosRecebidos,     PDO::PARAM_STR);
				$query->bindValue(':dataAtual',      date("Y-m-d H:i:s"), PDO::PARAM_STR);
				$query->bindValue(':enviada',        2,                   PDO::PARAM_INT);
				$query->bindParam(':idRequisicao',   $requisicao['id'],   PDO::PARAM_INT);
				$query->execute();
			}
		}
	}


/*
*   METODOS DE COMUNICACAO COM AS API'S
*   Estes metodos recebem o nome de suas respectivas API's
*/
	public function armazenamentoLote($dadosEnvio)
	{
		//Chamada quando uma carga solta for posicionada ou reposicionada no armazém de carga solta.

		return $this->enviarRequisicao(
			'armazenamento-lote',
			$dadosEnvio,
			'armazenamentoLote'
		);
	}


	public function avariaExtravioLote($dadosEnvio, $idOs)
	{
		//Chamada logo após a geração do termo de avaria (TFA), na desova.

		return $this->enviarRequisicao(
			'avaria-extravio-lote',
			$dadosEnvio,
			'avariaExtravioLote',
			$idOs
		);
	}


	public function acessoPessoas
	(
		$dadosEnvio,
		$idOs = 0,
		$idOpeImpCntrs = 0,
		$idTblTransaction
	) {
		//Chamada quando uma pessoa credenciada entra ou sai do recinto, através do sistema de portaria. Não envolve diretamente o GS. É feito no sistema de catracas, mas puxamos dados de lá para os relatórios de acesso, então podemos usar isso a nosso favor, desenvolvendo uma rotina que buscará, com determinada frequência, os dados que precisamos para montarmos o JSON.

		return $this->enviarRequisicao(
			'acesso-pessoas',
			$dadosEnvio,
			'acessoPessoas',
			$idOs,
			$idOpeImpCntrs,
			$idTblTransaction
		);
	}


	public function acessoVeiculos($dadosEnvio)
	{
		//Chamada quando um veículo credenciado (programado) entrar ou sair do recinto. gGMS (entrada/saída)

		return $this->enviarRequisicao(
			'acesso-veiculos',
			$dadosEnvio,
			'acessoVeiculos'
		);
	}


	public function credenciamentoPessoas(
		$dadosEnvio,
		$idOs = 0,
		$idOpeImpCntrs = 0,
		$idTblTransaction = 0,
		$ultimaDataCredenciamento
	) {
		// Chamada quando uma pessoa é cadastrada no sistema de portaria. Não envolve diretamente o GS. É feito no sistema de catracas, mas puxamos dados de lá para os relatórios de acesso, então podemos usar isso a nosso favor, desenvolvendo uma rotina que buscará, com determinada frequência, os dados que precisamos para montarmos o JSON.

		return $this->enviarRequisicao(
			'credenciamento-pessoas',
			$dadosEnvio,
			'credenciamentoPessoas',
			$idOs,
			$idOpeImpCntrs,
			$idTblTransaction,
			$ultimaDataCredenciamento
		);
	}


	public function credenciamentoVeiculos($dadosEnvio)
	{
		// Chamada quando um veículo é programado. gGMS

		return $this->enviarRequisicao(
			'credenciamento-veiculos',
			$dadosEnvio,
			'credenciamentoVeiculos'
		);
	}


	public function geracaoLotes($dadosEnvio, $idOs)
	{
		//Chamada quando uma OS for aberta. O número de lote já é gerado na OS. gIMP (abertura de OS)
		return $this->enviarRequisicao(
			'geracao-lotes',
			$dadosEnvio,
			'geracaoLotes',
			$idOs
		);
	}


	public function pesagemVeiculosCargas($dadosEnvio)
	{
		//Chamada quando uma pesagem de um veículo for realizada e o ticket extraído. gPAT (pesagem)

		return $this->enviarRequisicao(
			'pesagem-veiculos-cargas',
			$dadosEnvio,
			'pesagemVeiculosCargas'
		);
	}


	public function posicaoConteiner($dadosEnvio)
	{
		//Chamada quando um container for posicionado. gIMP (coletor)

		return $this->enviarRequisicao(
			'posicao-conteiner',
			$dadosEnvio,
			'posicaoConteiner'
		);
	}


	public function agendaNaviosAeronaves($dadosEnvio)
	{
	/*
		/ext​/agenda-navios-aeronaves
		Agenda/Operação de Navios/Aeronaves
		Link no GS: Operacional->Lineup
	*/
		return $this->enviarRequisicao(
			'agenda-navios-aeronaves',
			$dadosEnvio,
			'agendaNaviosAeronaves'
		);
	}


	public function atribuicaoTrocaNavio($dadosEnvio)
	{
		return $this->enviarRequisicao(
			'atribuicao-troca-navio',
			$dadosEnvio,
			'atribuicaoTrocaNavio'
		);
	}


	public function carregamentoLotes($dadosEnvio)
	{
		//Após execução das programações de saída ("desova carro", "saída c. solta", "saída de cntr cheio") - gIMP. Coletor.
		return $this->enviarRequisicao(
			'carregamento-lotes',
			$dadosEnvio,
			'carregamentoLotes'
		);
	}


	public function chegadaPontoZero($dadosEnvio)
	{
		return $this->enviarRequisicao(
			'chegada-ponto-zero',
			$dadosEnvio,
			'chegadaPontoZero'
		);
	}


	public function conferenciaFisica($dadosEnvio)
	{
		return $this->enviarRequisicao(
			'conferencia-fisica',
			$dadosEnvio,
			'conferenciaFisica'
		);
	}


	public function eventoGeorreferenciamento(
		$dadosEnvio,
		$idOs = 0,
		$idOpeImpCntrs    = 0,
		$idTblTransaction = 0,
		$ultimaDataCredenciamento   = 0,
		$idSiscomexGeorreferencia
	) {
		return $this->enviarRequisicao(
			'evento-georreferenciamento',
			$dadosEnvio,
			'eventoGeorreferenciamento',
			$idOs = 0,
			$idOpeImpCntrs    = 0,
			$idTblTransaction = 0,
			$ultimaDataCredenciamento   = 0,
			$idSiscomexGeorreferencia
		);
	}


	public function bloqueioDesbloqueioVeiculoCarga($dadosEnvio)
	{
		return $this->enviarRequisicao(
			'bloqueio-desbloqueio-veiculo-carga',
			 $dadosEnvio,
			 'bloqueioDesbloqueioVeiculoCarga'
		 );
	}


	public function transitoSimplificadoConteiner
	(
		$dadosEnvio,
		$idOs,
		$idOpeImpCntrs
	) {
		return $this->enviarRequisicao(
			'transito-simplificado-conteiner',
			$dadosEnvio,
			'transitoSimplificadoConteiner',
			$idOs,
			$idOpeImpCntrs
		);
	}


	public function inspecaoNaoInvasiva($dadosEnvio)
	{
		return $this->enviarRequisicao(
			'inspecao-nao-invasiva',
			$dadosEnvio,
			'inspecaoNaoInvasiva'
		);
	}


	public function indisponibilidadeEquipamentos(
		$dadosEnvio,
		$idOs = 0,
		$idOpeImpCntrs    = 0,
		$idTblTransaction = 0,
		$ultimaDataCredenciamento   = 0,
		$idSiscomexGeorreferencia
	) {
		return $this->enviarRequisicao(
			'indisponibilidade-equipamentos',
			$dadosEnvio,
			'indisponibilidadeEquipamentos'
		);
	}


	public function posicaoVeiculoPatio($dadosEnvio)
	{
		return $this->enviarRequisicao(
			'posicao-veiculo-patio',
			$dadosEnvio,
			'posicaoVeiculoPatio'
		);
	}


	public function representantes($dadosEnvio)
	{
		return $this->enviarRequisicao(
			'representantes',
			$dadosEnvio,
			'representantes'
		);
	}
}