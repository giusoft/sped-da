<?

define('INICIO', 0);
define('PESQUISAR', 10);
define('INUTILIZAR', 20);

$paginasPodeExportar = [PESQUISAR];

if (in_array($gPage, $paginasPodeExportar)) {
	$o->PDFEnabled = true;
	$o->DOCEnabled = true;
	$o->XLSEnabled = true;
	$o->CSVEnabled = true;
}

$html .= $o->msgTitle("Inutilizar Numeração de NFe");

$situacoesNfe = [
	'Aprovada',
	'Assinada',
	'Submetida',
	'Cancelada',
	'Reprovada'
];

switch ($gPage) {
	case INICIO:
		$frm = new gForm("{columns: 2}");
		$frm->add('{type: text; name: numero_de; fieldLabel: Número de;}');
		$frm->add('{type: text; name: numero_ate; fieldLabel: Número até;}');
		$frm->add('{type: date; name: data_cadastro_de; fieldLabel: Data de cadastro de;}');
		$frm->add('{type: date; name: data_cadastro_ate; fieldLabel: Data de cadastro até;}');
		$frm->add('{name: situacao; type: comboMultiSelection; fieldLabel: Situação; allowBlank: true; value: 0, 1, 2;}', $situacoesNfe);
		$frm->add('{type: checkbox; name: numeros_nao_utilizados; fieldLabel: Mostrar apenas números a inutilizar;}');
		$frm->add('{type: hidden; name: gPage; value: ' . PESQUISAR . ';}');
		$html .= $frm->render($o);
		break;


	case PESQUISAR:
		$where = [];
		$filtros = [];

		$temFiltroData   = (!empty($_REQUEST["data_cadastro_de"]) && !empty($_REQUEST["data_cadastro_ate"]));
		$temFiltroNumero = (!empty($_REQUEST["numero_de"]) && !empty($_REQUEST["numero_ate"]));

		// REMOVER DEPOIS, AQUI ESTÁ SÓ PARA AMBIENTE DE TESTE
		if (in_array('teste', explode("/", (string) $_SERVER['REQUEST_URI']))) {
			$where[] = "nfe.data >= '2025-12-03 00:00:00'";
		}

		if (!$temFiltroData && !$temFiltroNumero) {
			$html .= $o->msgDanger('É necessário preencher as datas de início e fim, ou os números de início e fim');
			$html .= $backButton;
			break;
		}

		if ($_REQUEST["data_cadastro_de"]) {
			$filtros[] = "Data de cadastro de: " . $_REQUEST["data_cadastro_de"];
			$where[] = "nfe.data >= '" . gDBDate($_REQUEST["data_cadastro_de"]) . " 00:00:00'";
		}

		if ($_REQUEST["data_cadastro_ate"]) {
			$filtros[] = "Data de cadastro até: " . $_REQUEST["data_cadastro_ate"];
			$where[] = "nfe.data <= '" . gDBDate($_REQUEST["data_cadastro_ate"]) . " 23:59:59'";
		}

		if ($_REQUEST["numero_de"]) {
			$numeroDe = (int) gCleanField($_REQUEST["numero_de"]);
			$filtros[] = "Número de: " . $numeroDe;
			$where[] = "nfe.numero >= " . $numeroDe;
		}

		if ($_REQUEST["numero_ate"]) {
			$numeroAte = (int) gCleanField($_REQUEST["numero_ate"]);
			$filtros[] = "Número até: " . $numeroAte;
			$where[] = "nfe.numero <= " . $numeroAte;
		}

		if ($_REQUEST['numeros_nao_utilizados']) {
			$filtros[] = "Mostrar apenas números não utilizados";
		}

		if ($_REQUEST['situacao']) {
			$situacoes = '';
			foreach ($_REQUEST['situacao'] as $i) {
			 	$situacoes .= $situacoesNfe[$i] . ", ";
			 	$filtrarSituacao[$situacoesNfe[$i]] = 1;
			}

			$filtrarSituacao['Não emitida - A inutilizar'] = 1;
			$filtrarSituacao['Inutilizada'] = 1;

			$filtros[] = "Situações: " . substr($situacoes, 0, -2);
			$where[] = "nfe.situacao IN ('" . implode("', '", $situacoesNfe) . "')";
		}

		$html .= $o->msgFilter("Filtros selecionados: " . implode(" • ", $filtros));

		if (!$where) {
			$html .= $o->msgDanger('Informe pelo menos um filtro');
			$html .= $backButton;
			break;
		}

		$where = implode(" AND ", $where);

		$sql = "SELECT
					nfe.id,
					nfe.data AS data_cadastro,
					nfe.numero,
					nfe.situacao,
					nfe.chave,
					nfe.cancelada,
					nfe.id_notas
				FROM nfe
				LEFT JOIN notas ON notas.id = nfe.id_notas
				WHERE {$where}
					AND (notas.tipo = 'S' OR notas.tipo IS NULL)
					AND nfe.id_notas_saida = 0
				ORDER BY nfe.numero";

		$rs = dbFastQuery($sql);

		if (!$rs) {
			$html .= $o->msgDanger('Nenhum registro encontrado a partir dos filtros selecionados');
			$html .= $backButton;
			break;
		}

		$numerosEmitidos = array_column($rs, 'numero');
		$numeroInicial = min($numerosEmitidos);
		$numeroFinal = max($numerosEmitidos);
		$numerosFaltantes = [];

		// Buscar números já inutilizados na SEFAZ
		$sqlInutilizados = "
			SELECT numero_inutilizado
			FROM nfe_eventos
			WHERE id_nfe_tipos_eventos = 4
				AND sucesso = 1
				AND numero_inutilizado >= {$numeroInicial}
				AND numero_inutilizado <= {$numeroFinal}
			ORDER BY numero_inutilizado";
		$numerosInutilizados = dbFastQuery($sqlInutilizados);
		$numerosInutilizados = array_column($numerosInutilizados, 'numero_inutilizado');

		$podeInutilizar = false;
		for ($i = $numeroInicial; $i <= $numeroFinal; $i++) {
			$indiceEmitido = array_search($i, $numerosEmitidos, true);
			if ($indiceEmitido !== false && in_array($i, $numerosInutilizados)) {
				$rs[$indiceEmitido]['situacao'] = 'Inutilizada';
			} elseif (in_array($i, $numerosInutilizados)) {
				$rs[] = [
					'id' => 0,
					'numero' => $i,
					'situacao' => 'Inutilizada'
				];
			} else {
				$podeInutilizar = true;
				$rs[] = [
					'id' => 0,
					'numero' => $i,
					'situacao' => 'Não emitida - A inutilizar'
				];
			}
		}

		if ($podeInutilizar || in_array('Reprovada', array_column($rs, 'situacao'))) {
			$html .= $o->button("{id: btn-selecionar-todos; icon: tasks; caption: Selecionar todos; style: primary; size: normal; onClick: selecionarTodos();}");
			$html .= $o->button("{id: btn-confirmar-inutilizacao; icon: file-times; caption: Confirmar Inutilização; style: success; size: normal; onClick: confirmarInutilizacao();}");

			$js = "
				let numerosParaInutilizar = [];

				$('.checkbox-inutilizar').on('change', function() {
					let numeros = $(this).data('numeros').toString().split(',');

					if ($(this).is(':checked')) {
						numeros.forEach(function(num) {
							if (!numerosParaInutilizar.includes(num)) {
								numerosParaInutilizar.push(num);
							}
						});
					} else {
						numeros.forEach(function(num) {
							let index = numerosParaInutilizar.indexOf(num);
							if (index > -1) {
								numerosParaInutilizar.splice(index, 1);
							}
						});
					}

					atualizarBotaoSelecao();
				});

				function atualizarBotaoSelecao() {
					let checkboxes = $('.checkbox-inutilizar');
					let todosChecados = checkboxes.filter(':checked').length === checkboxes.length;
					let btn = $('#btn-selecionar-todos');

					if (todosChecados) {
						btn.html('<i class=\"fa fa-times\"></i> Remover seleção')
						.removeClass('btn-primary')
						.addClass('btn-danger');
					} else {
						btn.html('<i class=\"fa fa-tasks\"></i> Selecionar todos')
						.removeClass('btn-danger')
						.addClass('btn-primary');
					}
				}

				function selecionarTodos() {
					hideWait();
					let checkboxes = $('.checkbox-inutilizar');
					let todosChecados = checkboxes.filter(':checked').length === checkboxes.length;

					checkboxes.prop('checked', !todosChecados).trigger('change');
				}

				function confirmarInutilizacao() {
					hideWait();

					if (numerosParaInutilizar.length === 0) {
						bootbox.alert('Selecione ao menos uma numeração para inutilizar');
						return;
					}

					let numerosOrdenados = numerosParaInutilizar.sort(function(a, b){return a - b}).join(', ');

					let msgHtml = '<b>Você está prestes a inutilizar as numerações:</b><br>' + numerosOrdenados + '<br><br>' +
								'<label>Motivo da inutilização (Mínimo 15 caracteres):</label>' +
								'<textarea id=\"motivo_inutilizacao\" class=\"form-control\" rows=\"3\" placeholder=\"Digite a justificativa...\"></textarea>';

					bootbox.confirm({
						title: 'Confirmar inutilização',
						message: msgHtml,
						buttons: {
							confirm: {
								label: 'Confirmar e inutilizar',
								className: 'btn-success'
							},
							cancel: {
								label: 'Cancelar',
								className: 'btn-default'
							}
						},
						callback: function (result) {
							if (result) {
								let motivo = $('#motivo_inutilizacao').val().trim();

								if (motivo.length < 15) {
									bootbox.alert('O motivo deve ter no mínimo 15 caracteres');
									return;
								}

								let url = '" . $o->page . "&gPage=" . INUTILIZAR . "';
								url += '&numeros=' + numerosParaInutilizar.join(',');
								url += '&motivo=' + encodeURIComponent(motivo);

								window.location.href = url;
							}
						}
					});
				}
			";
			$o->addJavascript($js);
		}

		$html .= $o->tableBegin("big", true, true);
		$mtz = [];
		$mtz[] = '<>' . 'Opções';

		if ($usrId <= 2) {
			$mtz[] = '->Id NFe';
		}

		$mtz[] = '<>Data cadastro';
		$mtz[] = '<-Número';
		$mtz[] = '<-Situação';
		$mtz[] = '<-Chave';
		$mtz[] = '<>Cancelada';
		$html .= $o->tableRow($mtz, "header-fixed");

		foreach ($rs as $row) {
			if (
				$_REQUEST['numeros_nao_utilizados'] // somente os que podem inutilizar
				&& (
					$row['situacao'] == 'Aprovada'
					|| $row['situacao'] == 'Assinada'
					|| $row['situacao'] == 'Submetida'
					|| $row['situacao'] == 'Cancelada'
					|| $row['situacao'] == 'Inutilizada'
				)
			) {
				continue;
			}

			$checkbox = "";
			if (
				$row['situacao'] == 'Reprovada'
				|| $row['situacao'] == 'Não emitida - A inutilizar'
			) {
				$checkbox = '<input type="checkbox" class="checkbox-inutilizar" data-numeros="' . $row['numero'] . '" />';
			}

			if (!$filtrarSituacao[$row['situacao']]) {
				continue;
			}

			$mtz = [];
			$mtz[] = '<>' . $checkbox;
			if ($usrId <= 2) {
				$mtz[] = '->' . $row['id'];
			}

			$mtz[] = '<>' . gDateTime($row['data_cadastro']);

			if (!empty($row['id_notas'])) {
				$mtz[] = '<-' . linkParaNota($row['id_notas'], $row['numero'], 'S');
			} else {
				$mtz[] = '<-' . $row['numero'];
			}

			$mtz[] = '<-' . $row['situacao'];
			$mtz[] = '<-' . $row['chave'];
			$mtz[] = '<>' . gCheck($row['cancelada'], true);

			$html .= $o->tableRow($mtz, "detail");
		}

		$html .= $o->tableEnd();
		break;


	case INUTILIZAR:
		if (empty($_REQUEST['numeros'])) {
			$html .= $o->msgError("Nenhuma numeração informada.");
			$html .= $backButton;
			break;
		}

		$motivo = trim((string) $_REQUEST['motivo']);
		$tamanhoMotivo = strlen($motivo);

		if ($tamanhoMotivo < 15 || $tamanhoMotivo > 1000) {
			$html .= $o->msgError("Informe uma justificativa válida (mínimo de 15 e máximo de 1000 caracteres)");
			$html .= $backButton;
			break;
		}

		$numerosSelecionados = explode(',', (string) $_REQUEST['numeros']);
		$numeros = array_filter(array_map('intval', $numerosSelecionados));
		sort($numeros);

		// Agrupa números consecutivos em intervalos
		$intervalos = [];
		$intervaloAtual = null;
		foreach ($numeros as $numero) {
			if ($intervaloAtual === null) {
				$intervaloAtual = ['inicio' => $numero, 'fim' => $numero];
			} elseif ($numero == $intervaloAtual['fim'] + 1) {
				$intervaloAtual['fim'] = $numero;
			} else {
				$intervalos[] = $intervaloAtual;
				$intervaloAtual = ['inicio' => $numero, 'fim' => $numero];
			}
		}

		if ($intervaloAtual !== null) {
			$intervalos[] = $intervaloAtual;
		}

		$nf = new NotasFiscais('S');
		$dadosEmpresa = $nf->obtemDadosEmpresa(obtemIdEmpresa());
		$dadosConfig = $nf->buscarConfiguracoes($dadosEmpresa['cnpjFilial']);

		$sucessos = [];
		$erros = [];

		foreach ($intervalos as $intervalo) {
			$numeroInicial = $intervalo['inicio'];
			$numeroFinal   = $intervalo['fim'];

			$dadosInutilizar = [];
			$dadosInutilizar['temRetorno']     = 1;
			$dadosInutilizar['serie']          = $dadosConfig['serie'];
			$dadosInutilizar['numero_inicial'] = $numeroInicial;
			$dadosInutilizar['numero_final']   = $numeroFinal;
			$dadosInutilizar['justificativa']  = $motivo;
			$dadosInutilizar['empresa']        = $dadosEmpresa;
			$dadosInutilizar['config']         = $dadosConfig;

			$retorno = dispararGatilho('inutilizarNfe', $dadosInutilizar);

			if ($retorno['erroCurl'] && !$retorno['resposta'] && !empty($retorno['erroCurl'])) {
				$erros[] = gCleanField($retorno['erroCurl']);
			}

			$retorno = json_decode((string) $retorno['resposta'], true);

			$textoNumeros = ($numeroInicial == $numeroFinal)
					? "Número <b>{$numeroInicial}</b>"
					: "Números <b>{$numeroInicial}</b> a <b>{$numeroFinal}</b>";

			// Inserir um registro para CADA número no intervalo
			for ($num = $numeroInicial; $num <= $numeroFinal; $num++) {
				$registro = [];
				$registro['data']                 = date('Y-m-d H:i:s');
				$registro['serie']                = $dadosConfig['serie'];
				$registro['motivo']               = gCleanField($motivo);
				$registro['id_pessoas']           = $usrId;
				$registro['numero_inutilizado']   = $num;
				$registro['id_nfe_tipos_eventos'] = 4; // 4 = Inutilização
				$registro['sucesso']          	  = (int) $retorno['sucesso'];
				$registro['retorno_mensagem'] 	  = gCleanField(removerAcentos($retorno['mensagem']));

				if ($retorno['sucesso']) {
					$registro['xml']       = base64_decode((string) $retorno['detalhes']['xml']);
					$registro['protocolo'] = $retorno['detalhes']['protocolo'];
				}

				dbInsert("nfe_eventos", $registro);
			}

			// Mensagens de sucesso/erro (uma por intervalo)
			if ($retorno['sucesso']) {
				$sucessos[] = sprintf('%s - Protocolo: %s', $textoNumeros, $retorno['detalhes']['protocolo']);
			} elseif (!$retorno['detalhes']['codigo']) {
				$erros[] = $registro['retorno_mensagem'];
			} else {
				$erros[] = "{$textoNumeros} - " . $registro['retorno_mensagem'] . " (Código: {$retorno['detalhes']['codigo']})";
			}
		}

		// Exibe mensagens de resultado
		if ($sucessos && !$erros) {
			$html .= $o->msgSuccess("Inutilização realizada com sucesso: " . $o->ul($sucessos));
		} elseif (!$sucessos && $erros) {
			$html .= $o->msgError("Falha na inutilização: " . $o->ul($erros));
		} else {
			$html .= $o->msgWarning("Inutilização concluída com ressalvas");

			if ($sucessos) {
				$html .= $o->msgSuccess("Inutilizados com sucesso: " . $o->ul($sucessos));
			} else {
				$html .= $o->msgDanger("Falhas na inutilização: " . $o->ul($erros));
			}
		}

		$html .= $o->button("{icon: arrow-left; caption: Voltar; style: default; size: small; href: " . $o->page . "&gPage=" . INICIO . "}");
		break;

}
