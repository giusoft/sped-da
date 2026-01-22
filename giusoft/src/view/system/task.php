<?php

/*
ESTE ARQUIVO SEMPRE DEVE FUNCIONAR INDEPENDENTE DE LOGIN
O PROPOSITO DELE EH SUPORTAR TODA E QUALQUER TAREFA AGENDADA NO CRON
ESTE ARQUIVO DEVE INDEPENDER DO FRAMEWORK PARA FAZER QUALQUER COISA
ESTE ARQUIVO PODE IMPORTAR FUNCIONALIDADES E OUTROS ARQUIVOS MAS DEVE SER EXECUTAVEL VIA TERMINAL SEMPRE
*/

if (!$_REQUEST['task']) {
	error_log('TAREFA NAO INFORMADA NO REQUEST', 0);
	echo 'Task nao informada';
	exit;
}


if ($_REQUEST['task'] == 'enviarRequisicoesPendentes') {
	// 0 4 * * * root /bin/wget -q "http://localhost/wms/giusoft/res/system/task.php?task=enviarRequisicoesPendentes"  >/dev/null 2>&1

	if (in_array('teste', explode("/", (string) $_SERVER['REQUEST_URI']))) {
		$ambiente = 'teste';
	}

	require_once $_SERVER["DOCUMENT_ROOT"] . '/' . $ambiente . "/wms/giusoft/res/api/accesspoint.php";

	$empresas = ['logic', 'logiclog', 'logicpe', 'logicce'];

	foreach ($empresas as $empresa) {

		$persistencia = new PontoAcesso(['empresa' => $empresa]);

		$sql = "SELECT
					gatilhos_configuracoes.id_pessoas_proprietario,
					gatilhos.metodo,
					gatilhos_requisicoes.enviado,
                    gatilhos_requisicoes_detalhes.id AS id_gatilhos_requisicoes_detalhes,
					MAX(gatilhos_requisicoes_detalhes.numero_tentativa) AS total_tentativas,
					gatilhos_requisicoes.id AS id_gatilhos_requisicoes,
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
			$parametro = [];
			$parametro['task'] = 1;
			$parametro['idPessoasProprietario'] = $requisicao['id_pessoas_proprietario'];

			$persistencia->acionarEventoMomento($requisicao['metodo'], $parametro);

			$dadosRequisicao = [];

			if ($requisicao['total_tentativas'] == 0) {
				$dadosRequisicao['idGatilhoRequisicaoDetalhes']  = $requisicao['id_gatilhos_requisicoes_detalhes'];
			}

			$dadosRequisicao['numeroTentativa'] 	= $requisicao['total_tentativas'];
			$dadosRequisicao['idGatilhoRequisicao'] = $requisicao['id_gatilhos_requisicoes'];
			$dadosRequisicao['idGatilhos'] 			= $requisicao['id_gatilhos'];

			$body = base64_decode((string) $requisicao['enviado']);
			$persistencia->objetoGenerico->acessarRota($requisicao['metodo'], $body, $dadosRequisicao);
		}

	}
}


if ($_REQUEST['task'] == 'enviarNfesOmie') {
	// 0 4 * * * sistemas /bin/wget -q "http://localhost/wms/giusoft/res/system/task.php?task=enviarNfesOmie"  >/dev/null 2>&1

	if (in_array('teste', explode("/", (string) $_SERVER['REQUEST_URI']))) {
		$ambiente = 'teste';
	}

	require_once $_SERVER["DOCUMENT_ROOT"] . '/' . $ambiente . "/wms/giusoft/res/api/accesspoint.php";

	$empresas = ['logic', 'logicce', 'logiclog', 'logicpe'];  ### Antes de colocar em producao descomentar esta linha

	$where = [];
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

    if (in_array('teste', explode("/", (string) $_SERVER['REQUEST_URI']))) {
        $ambiente = 'teste';
    }

    require_once $_SERVER["DOCUMENT_ROOT"] . '/' . $ambiente . "/wms/giusoft/res/api/accesspoint.php";

    $empresas = ['logic', 'logiclog', 'logicpe', 'logicce'];

    foreach ($empresas as $empresa) {

        $persistencia = new PontoAcesso(['empresa' => $empresa]);

		$sql = 'DELETE
					gatilhos_requisicoes,
					gatilhos_requisicoes_detalhes
				FROM gatilhos_requisicoes
				JOIN gatilhos_requisicoes_detalhes ON
					gatilhos_requisicoes.id = gatilhos_requisicoes_detalhes.id_gatilhos_requisicoes
				JOIN gatilhos ON gatilhos.id = gatilhos_requisicoes.id_gatilhos
				LEFT JOIN gatilhos_requisicoes_detalhes AS detalhes_recentes ON
					gatilhos_requisicoes.id = detalhes_recentes.id_gatilhos_requisicoes
					AND detalhes_recentes.data_hora >= NOW() - INTERVAL 7 DAY
				WHERE detalhes_recentes.id IS NULL ' . $addWhere;
        $persistencia->integracao->executarQuery($sql);
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
	$campos['id_itens_skus']           = (int) $item['id_itens_skus'];
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
	$campos['observacoes']    = substr((string) $item['observacoes'], 0, 60);
	$campos['valor']          = $item['valor'];
	$campos['quantidade']     = $campos['tipo'] . (float) str_replace('-', '', $item['quantidade']);
	$campos['peso_liquido']   = $campos['tipo'] . (float) str_replace('-', '', $item['peso_liquido']);
	$campos['peso_bruto']     = $campos['tipo'] . (float) str_replace('-', '', $item['peso_bruto']);
	$campos['m2']             = $campos['tipo'] . (float) str_replace('-', '', $item['m2']);
	$campos['m3']             = $campos['tipo'] . (float) str_replace('-', '', $item['m3']);
	$campos['temperatura']    = $item['temperatura'];
	$campos['id_filial']    = (int) $item['id_filial'];
	$campos['id_notas_itens'] = (int) $item['id_notas_itens'];

	return $campos;
}