<?php

/*
ESTE ARQUIVO SEMPRE DEVE FUNCIONAR INDEPENDENTE DE LOGIN
O PROPOSITO DELE EH SUPORTAR TODA E QUALQUER TAREFA AGENDADA NO CRON
ESTE ARQUIVO DEVE INDEPENDER DO FRAMEWORK PARA FAZER QUALQUER COISA
ESTE ARQUIVO PODE IMPORTAR FUNCIONALIDADES E OUTROS ARQUIVOS MAS DEVE SER EXECUTAVEL VIA TERMINAL SEMPRE
*/

define('SERVIDOR_ATUAL_EH_GA', (int) (stripos($_SERVER['REQUEST_URI'], '/ga/') !== false));

if (!$_REQUEST['task']) {
	error_log('TAREFA NAO INFORMADA NO REQUEST', 0);
	echo 'Task nao informada';
	exit;
}


if ($_REQUEST['task'] == 'dataCritica') {
	// 0 4 * * * root /bin/wget "http://127.0.0.1/wms/giusoft/res/system/task.php?task=dataCritica" >/dev/null 2>&1
	if ($_SERVER["HTTP_HOST"] != 'localhost' && $_SERVER["HTTP_HOST"] != '127.0.0.1') {
		$ambiente = '';
	}
	if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
		$ambiente = 'teste';
	}

	if ($ambiente == 'teste') {
		include_once $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . '/wms/giusoft/res/_classes/padrao/integracao.php';
	} else {
		include_once $_SERVER['DOCUMENT_ROOT'] . '/wms/giusoft/res/_classes/padrao/integracao.php';
	}
	if (SERVIDOR_ATUAL_EH_GA) {
		$empresas = array('ga');
	} else {
		$empresas = array('logiclog', 'uniklog');
	}

	foreach ($empresas as $empresa) {
		if ($ambiente == 'teste') {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . "/wms/{$empresa}/setup.php";
			require_once $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . "/wms/{$empresa}/res/_classes/integracao/gmi/gmi.php";
		} else {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/setup.php";
			require_once $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/res/_classes/integracao/gmi/gmi.php";
		}

		$parametro = array(
		    'caminhoSetup'   => $caminhoSetup,
		    'idPessoasCriou' => 1
		);

		$integracao = new Integracao($parametro);
		$integracao->atualizarParaSaldoCritico();
	}
}


if ($_REQUEST['task'] == 'removerDataCritica') {
	// 0 * * * * root /bin/wget "http://127.0.0.1/wms/giusoft/res/system/task.php?task=removerDataCritica" >/dev/null 2>&1
	if ($_SERVER["HTTP_HOST"] != 'localhost' && $_SERVER["HTTP_HOST"] != '127.0.0.1') {
		$ambiente = '';
	}
	if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
		$ambiente = 'teste';
	}

	if ($ambiente == 'teste') {
		include_once $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . '/wms/giusoft/res/_classes/padrao/integracao.php';
	} else {
		include_once $_SERVER['DOCUMENT_ROOT'] . '/wms/giusoft/res/_classes/padrao/integracao.php';
	}

	if (SERVIDOR_ATUAL_EH_GA) {
		$empresas = array('ga');
	} else {
		$empresas = array('logiclog', 'uniklog');
	}

	foreach ($empresas as $empresa) {
		if ($ambiente == 'teste') {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . "/wms/{$empresa}/setup.php";
			require_once $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . "/wms/{$empresa}/res/_classes/integracao/gmi/gmi.php";
		} else {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/setup.php";
			require_once $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/res/_classes/integracao/gmi/gmi.php";
		}

		$parametro = array(
		    'caminhoSetup'   => $caminhoSetup,
		    'idPessoasCriou' => 1
		);

		$integracao = new Integracao($parametro);
		$integracao->removerStatusDataCritica();
	}
}


if ($_REQUEST['task'] == 'relatorioInventario') {
	// 50 5 * * * root /bin/wget "http://127.0.0.1/wms/giusoft/res/system/task.php?task=relatorioInventario" >/dev/null 2>&1
	if ($_SERVER["HTTP_HOST"] != 'localhost' && $_SERVER["HTTP_HOST"] != '127.0.0.1') {
		$ambiente = '';
	}
	if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
		$ambiente = 'teste';
	}

	if ($ambiente == 'teste') {
		include_once $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . '/wms/giusoft/res/_classes/padrao/integracao.php';
	} else {
		include_once $_SERVER['DOCUMENT_ROOT'] . '/wms/giusoft/res/_classes/padrao/integracao.php';
	}

	$empresas = array('logiclog');

	foreach ($empresas as $empresa) {
		if ($ambiente == 'teste') {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . "/wms/{$empresa}/setup.php";
			require_once $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . "/wms/{$empresa}/res/_classes/integracao/gmi/gmi.php";
		} else {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/setup.php";
			require_once $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/res/_classes/integracao/gmi/gmi.php";
		}

		$parametro = array(
		    'caminhoSetup'   => $caminhoSetup,
		    'idPessoasCriou' => 1
		);

		$integracao = new Integracao($parametro);

		$sql  = "SELECT ativo, valor FROM parametros WHERE chave = 'INTEGRACAO_GMI'";
		$parametroIntegracaoSap = $integracao->executarQuery($sql)[0];
		if ($parametroIntegracaoSap['ativo']) {
			$idsProprietario = explode(',', $parametroIntegracaoSap['valor']);
			// require_once  $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/res/_classes/integracao/gmi/gmi.php";
			foreach ($idsProprietario as $idPessoasProprietario) {
				$_REQUEST['idPessoasProprietario'] = $idPessoasProprietario;
				$gmi = new Gmi();
				$gmi->gerarInvrpt();
			}
		}
	}

}


if ($_REQUEST['task'] == 'bloquearSaldoVencer') {
	// 0 4 * * * root /bin/wget "http://127.0.0.1/wms/giusoft/res/system/task.php?task=bloquearSaldoVencer" >/dev/null 2>&1
	if ($_SERVER["HTTP_HOST"] != 'localhost' && $_SERVER["HTTP_HOST"] != '127.0.0.1') {
		$ambiente = '';
	}
	if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
		$ambiente = 'teste';
	}

	if ($ambiente == 'teste') {
		include_once $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . '/wms/giusoft/res/_classes/padrao/integracao.php';
	} else {
		include_once $_SERVER['DOCUMENT_ROOT'] . '/wms/giusoft/res/_classes/padrao/integracao.php';
	}
	if (SERVIDOR_ATUAL_EH_GA) {
		$empresas = array('ga');
	} else {
		$empresas = array('logiclog', 'uniklog');
	}

	foreach ($empresas as $empresa) {
		if ($ambiente == 'teste') {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . "/wms/{$empresa}/setup.php";
			require_once $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . "/wms/{$empresa}/res/_classes/integracao/gmi/gmi.php";
		} else {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/setup.php";
			require_once $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/res/_classes/integracao/gmi/gmi.php";
		}

		$parametro = array(
		    'caminhoSetup'   => $caminhoSetup,
		    'idPessoasCriou' => 1
		);

		$integracao = new Integracao($parametro);
		$sql = "
			SELECT DISTINCT
				U.id AS id_umas,
				SK.id AS id_itens_skus,
				SUM(UI.quantidade) quantidade,
				UI.lote,
				UI.data_fabricacao,
				UI.data_validade,
				UI.id_notas_itens,
				MAX(UI.valor) valor,
				MAX(UI.peso_liquido) peso_liquido,
				MAX(UI.peso_bruto) peso_bruto,
				UI.codigo_externo,
				1 AS entrada,
				1 AS faturar,
				UI.id_armazens,
				UI.id_areas_direcionar,
				1 AS id_pessoas_criou,
				UI.id_pessoas_proprietario,
				UI.id_umas_origem,
				UI.id_veiculos_acessos,
				UI.m2,
				UI.m3,
				U.qualidade,
				CASE
					WHEN UI.data_validade <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'BLQ. AUTO POR VENCIMENTO'
					WHEN DATEDIFF(UI.data_validade, DATE_ADD(CURDATE(), INTERVAL 1 DAY)) <= I.dias_bloqueio THEN 'BLQ.POR LIMITE DE DIAS CADASTRADO NO ITEM'
				END
				AS observacoes,
				UI.id_programacao,
				U.id_posicoes,
				U.data_critica
			FROM
				umas U
			LEFT JOIN umas_itens UI ON
				(
					U.id = UI.id_umas
						AND UI.cancelada = 0
						AND U.ativo = 1
				)
			LEFT JOIN itens_skus SK ON
				UI.id_itens_skus = SK.id
			LEFT JOIN itens I ON
				I.id = SK.id_itens
			LEFT JOIN programacao PR ON
				PR.id = UI.id_programacao
			LEFT JOIN posicoes P ON P.id = U.id_posicoes
			LEFT JOIN areas A ON A.id = P.id_areas
			WHERE
				U.ativo = 1
				AND U.qualidade = 0
				AND UI.cancelada = 0
				AND I.exige_data_validade = 1
				AND UI.id_itens_skus IS NOT NULL
				AND (
					(
						UI.data_validade > '0000-00-00'
							AND UI.data_validade <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
					)
					OR (
						I.dias_bloqueio > 0
						AND DATEDIFF(UI.data_validade, DATE_ADD(CURDATE(), INTERVAL 1 DAY)) <= I.dias_bloqueio
					)
				)
				AND UI.separada = 0
				AND UI.reservada = 0
				AND UI.bloqueada = 0
				AND UI.avariada = 0
				AND UI.data <= CURRENT_TIMESTAMP()
				AND (A.id IS NULL OR (A.avaria = 0 AND A.divergencia = 0 AND A.codigo <> 'RETRABALHO'))
			GROUP BY
				U.id,
				U.id_armazens,
				SK.id,
				UI.lote,
				UI.data_fabricacao,
				UI.data_validade,
				UI.id_notas_itens,
				UI.reservada,
				UI.separada,
				UI.avariada,
				UI.bloqueada,
				UI.valor
			HAVING
				SUM(UI.quantidade) > 0
			ORDER BY
				UI.id_armazens
		";

		$rs = $integracao->executarQuery($sql);

		$sql  = "SELECT ativo, valor FROM parametros WHERE chave = 'INTEGRACAO_GMI'";
		$parametroIntegracaoSap = $integracao->executarQuery($sql)[0];

		foreach ($rs as $row) {
			//BLOQUEAR SALDO
			$row['id_tipos_operacao'] = 10; // 10 = Bloqueio automatico
			$row['tipo'] = '-';
			$campos = prepararCamposDoItem($row);

			$idRetiraNormal = 0;
			$idInsereBloqueado = 0;

			while (!$idRetiraNormal) { //tenta inserir de novo se der deadlock
				$campos['bloqueada'] = 0;
				$idRetiraNormal = $integracao->insertTable('umas_itens', $campos);
			}

			while (!$idInsereBloqueado) { //tenta inserir de novo se der deadlock
				$campos['tipo'] = '+';
				$campos['bloqueada']  = 1;
				$campos['quantidade'] = $row['quantidade'];
				$campos = prepararCamposDoItem($campos);
				$idInsereBloqueado = $integracao->insertTable('umas_itens', $campos);
			}

			$mtz = array();
			$mtz['data'] = $campos['data'];
			$mtz['id_pessoas'] = $campos['id_pessoas_criou'];
			$mtz['id_pessoas_proprietario'] = $campos['id_pessoas_proprietario'];
			$mtz['tipo'] = 'B'; // B = bloqueio
			$mtz['id_posicoes'] = $row['id_posicoes'];
			$mtz['id_umas'] = $campos['id_umas'];
			$mtz['descricao'] = ucfirst(strtolower($campos['observacoes']));
			$mtz['lote'] = $campos['lote'];
			$mtz['data_fabricacao'] = $campos['data_fabricacao'];
			$mtz['data_validade'] = $campos['data_validade'];
			$integracao->insertTable('umas_movimentos', $mtz);

			if ($parametroIntegracaoSap['ativo'] && in_array($campos['id_pessoas_proprietario'], explode(',', $parametroIntegracaoSap['valor']))) {
				// Chama o método do HANMOV
				$itensLinhas = array();

				$itensLinhas['numero_planta_armazenamento'] 		= 0; // 0 = SLFG 
				if ($row['data_critica'] == 1) {
					$itensLinhas['numero_planta_armazenamento'] 	= 2; // 2 = SLFS
				}
				$itensLinhas['numero_planta_armazenamento_2'] 		= 2; // 2 = SLFS 
				// require_once  $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/res/_classes/integracao/gmi/gmi.php";
				$gmi = new Gmi($parametro);
				$gmi->gerarHanmov(4, $itensLinhas['numero_planta_armazenamento'], $itensLinhas['numero_planta_armazenamento_2'], $idInsereBloqueado);
			}
		}
	}
}


if ($_REQUEST['task'] == 'viewUnidadesPorCliente') {
	//0 4 * * * sistemas /bin/wget -q "http://localhost/wms/mmedeiros/res/system/task.php?task=viewUnidadesPorCliente"  >/dev/null 2>/dev/null
	include_once $_SERVER['DOCUMENT_ROOT'] . '/wms/giusoft/res/_classes/padrao/integracao.php';
	$parametro = array(
	    'caminhoSetup'   => $_SERVER['DOCUMENT_ROOT'] . '/wms/mmedeiros/setup.php',
	    'idArmazens'     => 1,
	    'idPessoasCriou' => 1
	);

	$integracao = new Integracao($parametro);

	$sql = "
		INSERT
			INTO
			valor_por_cliente (
				id_pessoas_proprietario,
				valor,
				`data`
			)
			SELECT
			id_pessoas_proprietario,
			SUM(valor) AS valor,
			`data`
		FROM
			(
				SELECT
					P.id AS id_pessoas_proprietario,
					(
						SUM(UI.quantidade) * UI.valor
					) AS valor,
					DATE_SUB(CURDATE(), INTERVAL 1 DAY)	AS `data`
				FROM umas U
				LEFT JOIN umas_itens UI ON
					(
						U.id = UI.id_umas
						AND UI.cancelada = 0
						AND U.ativo = 1
					)
				LEFT JOIN itens_skus SK ON
					UI.id_itens_skus = SK.id
				LEFT JOIN itens I ON
					I.id = SK.id_itens
				JOIN pessoas P ON
					(
						P.id = UI.id_pessoas_proprietario
						AND P.cliente = 1
					)
				WHERE
					U.ativo = 1
					AND UI.cancelada = 0
					AND UI.id_itens_skus > 0
					AND UI.data <= DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY)
				GROUP BY
					U.id,
					U.id_programacao,
					UI.id_itens_skus,
					SK.codigo,
					I.id,
					P.id,
					SK.quantidade,
					UI.lote,
					UI.data_fabricacao,
					UI.data_validade,
					UI.id_notas_itens,
					UI.reservada,
					UI.separada,
					UI.avariada,
					UI.bloqueada,
					UI.valor
				HAVING
					SUM(UI.quantidade) > 0
			) t
		GROUP BY
			id_pessoas_proprietario;
	";
	$integracao->executarQuery($sql);
}


if ($_REQUEST['task'] == 'desativaContainer') {
	// 0 0 1 * * sistemas /bin/wget -q "http://localhost/wms/mmedeiros/res/system/task.php?task=desativaContainer"  >/dev/null 2>/dev/null
	include_once $_SERVER['DOCUMENT_ROOT'] . '/wms/giusoft/res/_classes/padrao/integracao.php';
	$empresas = array('mmedeiros');
	foreach ($empresas as $empresa) {
		if ($ambiente == 'teste') {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . "/wms/{$empresa}/setup.php";
		} else {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/setup.php";
		}
		$parametro = array(
		    'caminhoSetup'   => $caminhoSetup,
		    'idArmazens'     => 1,
		    'idPessoasCriou' => 1
		);

		$integracao = new Integracao($parametro);

		$sql = "UPDATE eir_conteiner
				SET ativo = 0
				WHERE data_validade < CURDATE()";
		$integracao->executarQuery($sql);
	}
}


if ($_REQUEST['task'] == 'posicoesContratadas') {
	// 0 4 * * * sistemas /bin/wget -q "http://localhost/wms/giusoft/res/system/task.php?task=posicoesContratadas"  >/dev/null 2>&1
	include_once $_SERVER['DOCUMENT_ROOT'] . '/wms/giusoft/res/_classes/padrao/integracao.php';
	$empresas = array('logic', 'logicce', 'logiclog', 'logicpe');

	foreach ($empresas as $empresa) {
		if ($ambiente == 'teste') {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . "/wms/{$empresa}/setup.php";
		} else {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/setup.php";
		}
		$parametro = array(
		    'caminhoSetup'   => $caminhoSetup,
		    'idArmazens'     => 1,
		    'idPessoasCriou' => 1
		);

		$integracao = new Integracao($parametro);

		$sql = "INSERT INTO posicoes_contratadas (
				id_posicoes,
				quantidade_umas,
				id_pessoas_proprietario,
				quantidade_contratada,
				data
			)
			SELECT
				P.id,
				COUNT(DISTINCT U.id),
				UI.id_pessoas_proprietario,
				pessoas_juridicas.quantidade_posicoes,
				CURDATE()
			FROM
				umas U
			JOIN umas_itens UI ON UI.id_umas = U.id
			JOIN posicoes P ON P.id = U.id_posicoes
			JOIN pessoas_juridicas ON pessoas_juridicas.id_pessoas = UI.id_pessoas_proprietario
			WHERE
				U.ativo = 1
				AND UI.cancelada = 0
				AND U.id_posicoes > 0
				AND U.posicionada = 1
			GROUP BY
				P.id,
				UI.id_pessoas_proprietario
			;";
		$integracao->executarQuery($sql);
	}
}


if ($_REQUEST['task'] == 'enviarRelatorioSaldoAtual') {
	// 0 4 * * * sistemas /bin/wget -q "http://localhost/wms/giusoft/res/system/task.php?task=enviarRelatorioSaldoAtual"  >/dev/null 2>&1
	if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
		$ambiente = 'teste';
	}

	require_once $_SERVER["DOCUMENT_ROOT"] . $ambiente . "/wms/giusoft/res/api/accesspoint.php";

	$empresas = array('logic', 'logiclog', 'logicpe', 'logicce');

	foreach ($empresas as $empresa) {
		$persistencia = new PontoAcesso(['empresa' => $empresa]);

		$metodo = 'enviarRelatorioSaldoAtual';
		$sql = "SELECT
					gatilhos_configuracoes.id_pessoas_proprietario
				FROM
					gatilhos_configuracoes
				JOIN gatilhos ON
					gatilhos.id_gatilhos_configuracoes = gatilhos_configuracoes.id
				WHERE gatilhos.metodo = '{$metodo}'
				AND gatilhos.ativo = 1";
		$proprietarios = $persistencia->integracao->executarQuery($sql);

		foreach ($proprietarios as $proprietario) {
			$persistencia->acionarEventoMomento('enviarRelatorioSaldoAtual', ['idPessoasProprietario' => $proprietario['id_pessoas_proprietario']]);
		}
	}
}


if ($_REQUEST['task'] == 'enviarRequisicoesPendentes') {
	// 0 4 * * * root /bin/wget -q "http://localhost/wms/giusoft/res/system/task.php?task=enviarRequisicoesPendentes"  >/dev/null 2>&1

	if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
		$ambiente = 'teste';
	}

	require_once $_SERVER["DOCUMENT_ROOT"] . '/' . $ambiente . "/wms/giusoft/res/api/accesspoint.php";

	$empresas = array('logic', 'logiclog', 'logicpe', 'logicce');

	foreach ($empresas as $empresa) {

		$persistencia = new PontoAcesso(['empresa' => $empresa]);

		$sql = "SELECT
					gatilhos_configuracoes.id_pessoas_proprietario,
					gatilhos.metodo,
					gatilhos_requisicoes.enviado,
                    gatilhos_requisicoes_detalhes.id AS id_gatilhos_requisicoes_detalhes,
					MAX(gatilhos_requisicoes_detalhes.numero_tentativa) AS total_tentativas,
					gatilhos_requisicoes.id AS id_gatilhos_requisicoes,
					gatilhos_requisicoes.id_programacao,
					gatilhos.id AS id_gatilhos
				FROM gatilhos_requisicoes
				JOIN gatilhos_requisicoes_detalhes ON gatilhos_requisicoes_detalhes.id_gatilhos_requisicoes = gatilhos_requisicoes.id
				JOIN gatilhos ON gatilhos.id = gatilhos_requisicoes.id_gatilhos
				JOIN gatilhos_configuracoes ON gatilhos_configuracoes.id = gatilhos.id_gatilhos_configuracoes
				WHERE gatilhos_requisicoes.pendente = 1
				GROUP BY gatilhos.id, gatilhos_requisicoes.id
				HAVING MAX(gatilhos_requisicoes_detalhes.numero_tentativa) < MAX(gatilhos_configuracoes.quantidade_tentativas)";
		$requisicoes = $persistencia->integracao->executarQuery($sql);

		foreach ($requisicoes as $requisicao) {
			$parametro = array();
			$parametro['task'] = 1;
			$parametro['idPessoasProprietario'] = $requisicao['id_pessoas_proprietario'];

			$persistencia->acionarEventoMomento($requisicao['metodo'], $parametro);

			$dadosRequisicao = array();

			if ($requisicao['total_tentativas'] == 0) {
				$dadosRequisicao['idGatilhoRequisicaoDetalhes']  = $requisicao['id_gatilhos_requisicoes_detalhes'];
			}

			$dadosRequisicao['numeroTentativa'] 	= $requisicao['total_tentativas'];
			$dadosRequisicao['idGatilhoRequisicao'] = $requisicao['id_gatilhos_requisicoes'];
			$dadosRequisicao['idProgramacao'] 		= $requisicao['id_programacao'];
			$dadosRequisicao['idGatilhos'] 			= $requisicao['id_gatilhos'];

			$body = base64_decode($requisicao['enviado']);
			$persistencia->objetoGenerico->acessarRota($requisicao['metodo'], $body, $dadosRequisicao);
		}

	}
}


if ($_REQUEST['task'] == 'enviarNfesOmie') {
	// 0 4 * * * sistemas /bin/wget -q "http://localhost/wms/giusoft/res/system/task.php?task=enviarNfesOmie"  >/dev/null 2>&1

	if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
		$ambiente = 'teste';
	}

	require_once $_SERVER["DOCUMENT_ROOT"] . '/' . $ambiente . "/wms/giusoft/res/api/accesspoint.php";

	$empresas = array('logic', 'logicce', 'logiclog', 'logicpe');  ### Antes de colocar em producao descomentar esta linha

	$where = array();
	$where[] = " AND YEAR(nfe.data) = 20" . $_REQUEST['ano'];
	$where[] = " MONTH(nfe.data) = "  . $_REQUEST['mes'];

	if ($_REQUEST['dia']) {
		$where[] = " DAY(nfe.data) = " . $_REQUEST['dia'];
	}

	$where = implode(' AND ', $where);

	foreach ($empresas as $empresa) {

		$persistencia = new PontoAcesso(['empresa' => $empresa]);

		$sql = "
			SELECT  nfe.chave, nfe.xml, nfe.id_os,
					nfe.xml_cancelamento, nfe.cancelada
			FROM nfe
			JOIN notas ON notas.id_nfe = nfe.id AND notas.tipo = 'S'
			WHERE
				notas.nota_importada_cliente = 0
				AND (
					(
						nfe.situacao = 'Aprovada'
						AND nfe.cancelada = 0
					)
					OR (
						nfe.situacao = 'Cancelada'
						AND nfe.cancelada = 1
					)
				)
				{$where}";
		$nfes = $persistencia->integracao->executarQuery($sql);

		foreach ($nfes as $nfe) {
			$dadosNf = [
				"xml" 	=> $nfe['xml'],
				"chave" => $nfe['chave'],
				"idProgramacao" => $nfe['id_os'],
				"idPessoasProprietario" => 1
			];
			$persistencia->acionarEventoMomento("emitirNf", $dadosNf);

			if ($nfe['cancelada']) {
				$dadosNf = [
					"chave" => $nfe['chave'],
					"xml" => $nfe['xml_cancelamento'],
					"idPessoasProprietario" => 1
				];
				$persistencia->acionarEventoMomento("importarCancNFe", $dadosNf);
			}
		}
	}
}


if ($_REQUEST['task'] == 'limparRequisicoesAntigas') {
	// 0 3 * * * sistemas /bin/wget -q "http://localhost/wms/giusoft/res/system/task.php?task=limparRequisicoesAntigas"  >/dev/null 2>&1

    if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
        $ambiente = 'teste';
    }

    require_once $_SERVER["DOCUMENT_ROOT"] . '/' . $ambiente . "/wms/giusoft/res/api/accesspoint.php";

    $empresas = array('logic', 'logiclog', 'logicpe', 'logicce');
    if (SERVIDOR_ATUAL_EH_GA) {
    	$empresas = array('ga');
    	$addWhere = " AND gatilhos.http_verbo <> 'PUT'";
    }

    foreach ($empresas as $empresa) {

        $persistencia = new PontoAcesso(['empresa' => $empresa]);

		$sql = "DELETE
					gatilhos_requisicoes,
					gatilhos_requisicoes_detalhes
				FROM gatilhos_requisicoes
				JOIN gatilhos_requisicoes_detalhes ON
					gatilhos_requisicoes.id = gatilhos_requisicoes_detalhes.id_gatilhos_requisicoes
				JOIN gatilhos ON gatilhos.id = gatilhos_requisicoes.id_gatilhos
				LEFT JOIN gatilhos_requisicoes_detalhes AS detalhes_recentes ON
					gatilhos_requisicoes.id = detalhes_recentes.id_gatilhos_requisicoes
					AND detalhes_recentes.data_hora >= NOW() - INTERVAL 7 DAY
				WHERE detalhes_recentes.id IS NULL {$addWhere}";
        $persistencia->integracao->executarQuery($sql);
    }
}


if ($_REQUEST['task'] == 'atualizarAtivacaoUMA') {
	// 0 4 * * * sistemas /bin/wget -q "http://localhost/wms/giusoft/res/system/task.php?task=atualizarAtivacaoUMA"  >/dev/null 2>&1
	include_once $_SERVER['DOCUMENT_ROOT'] . '/wms/giusoft/res/_classes/padrao/integracao.php';
	$empresas = array('logiclog', 'logic', 'logicce', 'logicpe', 'uniklog', 'toplog');

	foreach ($empresas as $empresa) {

		if ($ambiente == 'teste') {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . '/' . $ambiente . "/wms/{$empresa}/setup.php";
		} else {
			$caminhoSetup = $_SERVER['DOCUMENT_ROOT'] . "/wms/{$empresa}/setup.php";
		}

		$parametro = array(
		    'caminhoSetup'   => $caminhoSetup,
		    'idArmazens'     => 1,
		    'idPessoasCriou' => 1
		);

		$integracao = new Integracao($parametro);

		if ($empresa == "logiclog") {
			$where = "AND umas_itens.id_pessoas_proprietario <> 3613";
		}

		$sql = "SELECT umas.id
				FROM umas
				LEFT JOIN umas_itens ON (umas_itens.id_umas = umas.id AND umas_itens.cancelada = 0)
				WHERE umas.ativo = 0
					{$where}
					AND EXISTS (SELECT 1 FROM umas_itens WHERE id_umas = umas.id LIMIT 1)
				GROUP BY umas.id
				HAVING IFNULL(SUM(umas_itens.quantidade), 0) > 0";
		$idUmas = $integracao->executarQuery($sql);

		if ($idUmas) {
			$idsUmas = implode(", ", array_column($idUmas, 'id'));

			$sql = "UPDATE umas SET data_desativacao = '0000-00-00 00:00:00', ativo = 1, posicionada = (id_posicoes > 0) WHERE ativo = 0 AND id IN ({$idsUmas})";
			$integracao->executarQuery($sql);
		}

	}
}


/*
	FUNCOES UTEIS
*/
function prepararCamposDoItem($item)
{
	$campos['data']                    = date('Y-m-d H:i:s'); // troca pela data atual
	$campos['data_fabricacao']         = $item['data_fabricacao'] ?: '0000-00-00';
	$campos['data_validade']           = $item['data_validade'] ?: '0000-00-00';
	$campos['data_validade_antiga']    = $item['data_validade_antiga'] ?: '0000-00-00';
	$campos['id_pessoas_criou']        = 1; // Troca pelo id do usuário atual
	$campos['id_pessoas_proprietario'] = (int) $item['id_pessoas_proprietario'];
	$campos['id_umas_origem']          = (int) $item['id_umas_origem'];
	$campos['id_itens_skus']           = (int) $item['id_itens_skus'];
	$campos['id_programacao']          = (int) $item['id_programacao'];
	$campos['id_programacao_itens']    = (int) $item['id_programacao_itens'];
	$campos['id_veiculos_acessos']     = (int) $item['id_veiculos_acessos'];
	$campos['id_areas_direcionar']     = (int) $item['id_areas_direcionar'];
	$campos['codigo_externo'] = $item['codigo_externo'];
	$campos['id_tipos_operacao'] = (int) $item['id_tipos_operacao'];
	$campos['reservada']      = (int) $item['reservada'];
	$campos['separada']       = (int) $item['separada'];
	$campos['avariada']       = (int) $item['avariada'];
	$campos['bloqueada']      = (int) $item['bloqueada'];
	$campos['inventario']     = (int) $item['inventario'];
	$campos['faturar']        = (int) $item['faturar'];
	$campos['entrada']        = (int) $item['entrada'];
	$campos['saida']          = (int) $item['saida'];
	$campos['tipo']           = ($item['tipo'] == '-' ? '-' : '+');
	$campos['lote']           = $item['lote'];
	$campos['serial']         = $item['serial'];
	$campos['observacoes']    = substr($item['observacoes'], 0, 60);
	$campos['valor']          = $item['valor'];
	$campos['quantidade']     = $campos['tipo'] . (float) str_replace('-', '', $item['quantidade']);
	$campos['peso_liquido']   = $campos['tipo'] . (float) str_replace('-', '', $item['peso_liquido']);
	$campos['peso_bruto']     = $campos['tipo'] . (float) str_replace('-', '', $item['peso_bruto']);
	$campos['m2']             = $campos['tipo'] . (float) str_replace('-', '', $item['m2']);
	$campos['m3']             = $campos['tipo'] . (float) str_replace('-', '', $item['m3']);
	$campos['temperatura']    = $item['temperatura'];
	$campos['id_umas']        = (int) $item['id_umas'];
	$campos['id_armazens']    = (int) $item['id_armazens'];
	$campos['id_notas_itens'] = (int) $item['id_notas_itens'];

	return $campos;
}