
<?
class Relatorios{}

class Saldos extends Relatorios
{
	private $select;
	private $groupBy;
	public $orderBy;

	public function __construct()
	{
		global $o;

		$this->orderBy = "";
		$this->subtotal = array();
		$this->total = array();

		$js = "
			function bloquearComboProprietario()
			{
				select = $('#id_pessoas_proprietario').selectize();
				select[0].selectize.lock();
			}";
		$o->addJavascript($js);
	}


	public function obtemBusca($request)
	{
		global $sp;
		$where = "(UI.id > 0)";
		$cabecalho = array();

		if ($request["id_pessoas_proprietario"]) {
			$where .= " AND (UI.id_pessoas_proprietario = '" . $request["id_pessoas_proprietario"] . "')";
			$cabecalho[] = "Proprietário: " . gFieldById("pessoas", $request["id_pessoas_proprietario"], "nome");
		}

		if ($request["data_execucao_inicio"]) {
			$where .= " AND (PR.data_execucao >= '" . gDBDate($request["data_execucao_inicio"]) . "')";
			$cabecalho[] = "Data de execução de: " . $request["data_execucao_inicio"];
		}

		if ($request["item"]) {
			$cabecalho[] = "Item: " . $request["item"];
			$idItensSku = dbQuery('SELECT id FROM itens_skus WHERE codigo ="' . $request["item"] . '"')[0]['id'];
			$where .= " AND (UI.id_itens_skus = '" . (int) $idItensSku . "')";
		}

		if ($request["data_execucao_final"]) {
			$where .= " AND (PR.data_execucao <= '" . gDBDate($request["data_execucao_final"]) . "')";
			$cabecalho[] = "Data de execução até: " . $request["data_execucao_final"];
		}

		$filtroItem = filtroComboItem($request, "SK");
		if (count($filtroItem) > 0) {
			$where .= $filtroItem["where"];
			$cabecalho[] = $filtroItem["cabecalho"];
		}

		if ($request["apto"]) {
			$where .= " AND (UI.reservada = 0 AND UI.separada = 0 AND UI.bloqueada = 0 AND UI.avariada = 0)";
			$cabecalho[] = "Somente aptos";
		} else {
			if (!$_REQUEST['ocultarCabecalhoApto']) {
				$cabecalho[] = "Itens aptos e inaptos";
			}
		}

		if ($request['gPage'] == RELATORIO_POR_NOTA_FISCAL) {
			if ($_REQUEST['itens_com_saldo']) {
				$cabecalho[] = "Itens da nota com saldo";
			} else {
				$cabecalho[] = "Itens da nota com saldo e sem saldo";
			}
		}

		if ($request["id_grupos"]) {
			$where .= " AND (I.id_grupos='" . $request["id_grupos"] . "')";
			$cabecalho[] = "Grupo: " . gFieldById("grupos", $request["id_grupos"], 'descricao');
		}

		if ($request["id_tipos"]) {
			$where .= " AND (I.id_tipos='" . $request["id_tipos"] . "')";
			$cabecalho[] = "Tipo: " . gFieldById("tipos", $request["id_tipos"], 'descricao');
		}

		if ($_REQUEST["data_entrada_de"]) {
			$where .= " AND (UI.data >= '" . gDBDate($_REQUEST["data_entrada_de"]) . "')";
			$cabecalho[] = "Data de entrada de: " . $_REQUEST["data_entrada_de"];
		}

		if ($_REQUEST["data_entrada_ate"]) {
			$where .= " AND (UI.data<='" . date("Y-m-d", strtotime(gDBDate($_REQUEST["data_entrada_ate"]))) . "')";
			$cabecalho[] = " Data de entrada até: " . $_REQUEST["data_entrada_ate"];
		}

		if ($_REQUEST["data_validade_de"]) {
			$where .= " AND UI.data_validade >= '" . gDBDate($_REQUEST["data_validade_de"]) . "'";
			$cabecalho[] = "Data de validade de: " . $_REQUEST["data_validade_de"];
		}

		if ($_REQUEST["data_validade_ate"]) {
			$where .= " AND UI.data_validade <= '" . date("Y-m-d", strtotime(gDBDate($_REQUEST["data_validade_ate"]))) . "'";
			$cabecalho[] = "Data de validade até: " . $_REQUEST["data_validade_ate"];
		}

		if ($_REQUEST["data_saida_de"]) {
			$where .= " AND UI.data >= '" . gDBDate($_REQUEST["data_saida_de"]) . "' AND UI.separada = 1 AND UI.tipo = '-'";
			$cabecalho[] = "Data de saída de: " . $_REQUEST["data_saida_de"];
		}

		if ($_REQUEST["data_saida_ate"]) {
			$where .= " AND UI.data <= '" . date("Y-m-d", strtotime(gDBDate($_REQUEST["data_saida_ate"]))) . "' AND UI.separada = 1 AND UI.tipo = '-'";
			$cabecalho[] = "Data de saída até: " . $_REQUEST["data_saida_ate"];
		}

		if ($request['data_referencia']) {
			$where .= " AND (UI.data <= '" . date('Y-m-d 23:59:59', strtotime(gDBDate($request["data_referencia"]))) . "')";
			$cabecalho[] = " Data de referência: " . $_REQUEST["data_referencia"];
		}

		if ($request["numero_nota"]) {
			/* Montar inNotas */
			$numeroNota = gCleanField($request["numero_nota"]);
			$cabecalho[] = "Número da nota: {$numeroNota}";
			$where .= " AND (N.numero = '{$numeroNota}')";
		}

		if ($_REQUEST["data_emissao_de"]) {
			$where .= " AND N.data_emissao >= '" . gDBDate($_REQUEST["data_emissao_de"]) . "'";
			$cabecalho[] = "Data de emissão de: " . $_REQUEST["data_emissao_de"];
		}

		if ($_REQUEST["data_emissao_ate"]) {
			$where .= " AND N.data_emissao <= '" . gDBDate($_REQUEST["data_emissao_ate"]) . "'";
			$cabecalho[] = "Data de emissão até: " . $_REQUEST["data_emissao_ate"];
		}

		if ($request["os"]) {
			/* Montar inOS */
			$os = gCleanField($request["os"]);
			$cabecalho[] = "OS: {$os}";
			$where .= " AND (PR.os = '{$os}') ";
		}

		$qtdSituacoes = count($request["situacoes"]);
		if ($qtdSituacoes) {
			for ($i = 0; $i <= $qtdSituacoes; $i++) {
				$situacao = $request["situacoes"][$i];
				switch ($situacao) {
					case 1:
						$cabecalho[] = "Situação: Somente aptas";
						$orWhere[] = "(UI.bloqueada=0 AND UI.avariada=0 AND UI.separada=0 AND UI.reservada=0)";
						break;
					case 2:
						$cabecalho[] = "Situação: Bloqueada";
						$orWhere[] = "(UI.bloqueada=1)";
						break;
					case 3:
						$cabecalho[] = "Situação: Avariada";
						$orWhere[] = "(UI.avariada=1)";
						break;
					case 4:
						$cabecalho[] = "Situação: Reservada";
						$orWhere[] = "(UI.reservada=1)";
						break;
					case 5:
						$cabecalho[] = "Situação: Separada";
						$orWhere[] = "(UI.separada=1)";
						break;
				}
			}

			$busca['whereSituacoes'] = ' AND (' . implode(' OR ', $orWhere) .  ')';
			$where .= $busca['whereSituacoes'];
		} else {
			if ($request["apto"]) {
				$where .= " AND (UI.reservada = 0 AND UI.separada = 0 AND UI.avariada = 0 AND UI.bloqueada = 0)";
			}
		}

		if ($request["groupBy"]) {
			$groupCabecalho = array();
			$groupCabecalho["t.lote"] = "Lote";
			$groupCabecalho["t.data_fabricacao"] = "Fabricação";
			$groupCabecalho["t.data_validade"] = "Validade";
			$groupCabecalho["t.id_posicoes"] = "Posições";
			$groupCabecalho["t.id_umas"] = "UMA(s)";
			$groupCabecalho["t.id_grupos"] = "Grupo";
			$groupCabecalho["t.id_tipos"] = "Tipos";
			$groupCabecalho["t.valor_un"] = "Valor unitário";

			$groupBy = $request["groupBy"];

			foreach ($groupBy as $agrupamento) {
				$cabecalho[] = 'Agrupar por: ' . $groupCabecalho[$agrupamento];
			}

			$groupBy = ", " . implode(", ", $request["groupBy"]);
		}

		$cabecalho[] = "Exibir totais: " . gCheck($request["exibirTotal"]);

		$busca = array(
			'groupBy' => $groupBy,
			'where'   => $where,
			'cabecalho' => $cabecalho,
			'whereSituacoes' => $busca['whereSituacoes']
		);
		return $busca;
	}

	/******************************* SINTETICO *************************************************/
	public function obtemSaldoSintetico($where, $addGroupBy = "")
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		if ($this->orderBy) {
			$orderBy = $this->orderBy;
		} else {
			$orderBy = "apelido, item, separada DESC, reservada DESC, avariada DESC, bloqueada DESC, quantidade";
		}

		$sql = "
			SELECT t.*,
				CEIL(
					SUM(quantidade)/(
						palete_lastro * palete_altura
					)
				) paletes,
				SUM(peso_liquido) peso_liquido,
				SUM(peso_bruto) peso_bruto,
				SUM(m2) m2,
				SUM(m3) m3,
				SUM(quantidade_un) quantidade_un,
				SUM(quantidade) quantidade_sku,
				SUM(valor_total) valor_total,
				COUNT(DISTINCT id_umas) AS qtd_umas
			FROM (
				SELECT
					P.id AS idProprietario,
					P.apelido,
					U.id AS id_umas,
					U.codigo_barras AS codigo_barras_uma,
					PP.id AS id_posicoes,
					PP.codigo_barras AS codigo_barras_posicao,
					PR.id AS id_programacao,
					PR.os,
					GI.id AS id_grupos,
					GI.descricao AS descricao_grupo,
					TI.id AS id_tipos,
					TI.descricao AS descricao_tipo,
					SK.codigo AS codigo,
					SK.id AS id_itens_skus,
					UI.lote,
					UI.data_validade,
					UI.data_fabricacao,
					SK.palete_lastro,
					SK.palete_altura,
					I.nome AS item,
					CONCAT(I.nome, ' - ', CAST(SK.quantidade AS SIGNED), ' x ', D.descricao) AS nomeItem,
					CONCAT(D.sigla, ' com ', CAST(SK.quantidade AS SIGNED)) AS sku,
					SUM(UI.quantidade) quantidade,
					SUM(UI.quantidade) * SK.quantidade quantidade_un,
					(
						SK.peso_liquido * SUM(UI.quantidade)
					) AS peso_liquido,
					(
						SK.peso_bruto * SUM(UI.quantidade)
					) AS peso_bruto,
					(
						(SK.comprimento/100) * (SK.largura/100) * UI.quantidade
					) AS m2,
					(
						(SK.altura/100) * (SK.largura/100) * (SK.comprimento/100) * UI.quantidade
					) AS m3,
					SUM(UI.quantidade * UI.valor) AS valor_total,
					UI.valor AS valor_un,
					UI.bloqueada,
					UI.avariada,
					UI.reservada,
					UI.separada
				FROM
					umas U
				LEFT JOIN umas_itens UI ON
					(
						U.id = UI.id_umas
					)
				LEFT JOIN itens_skus SK ON
					UI.id_itens_skus = SK.id
				LEFT JOIN itens I ON
					I.id = SK.id_itens
				LEFT JOIN grupos GI ON
					GI.id = I.id_grupos
				LEFT JOIN tipos TI ON
					TI.id = I.id_tipos
				LEFT JOIN unidades D ON
					SK.id_unidades = D.id
				JOIN pessoas P ON
					(
						P.id = UI.id_pessoas_proprietario
					)
				LEFT JOIN posicoes PP ON
					U.id_posicoes = PP.id
				LEFT JOIN programacao PR ON
					PR.id = UI.id_programacao
				WHERE
					U.ativo = 1
					AND UI.cancelada = 0
					AND P.cliente = 1
					AND UI.id_filial = '" . $_SESSION['filialAtualId'] . "'
					{$where}
				GROUP BY
					U.id,
					U.ativo,
					U.codigo_barras,
					U.codigo_externo,
					U.conferida_saida,
					U.id_programacao,
					U.id_filial,
					PP.codigo_barras,
					U.posicionada,
					U.data,
					UI.id_itens_skus,
					SK.codigo,
					I.shelf_life,
					I.id,
					I.nome,
					P.apelido,
					SK.quantidade,
					D.descricao,
					UI.lote,
					UI.data_fabricacao,
					UI.data_validade,
					UI.id_notas_itens,
					UI.reservada,
					UI.separada,
					UI.avariada,
					UI.bloqueada
				HAVING
					SUM(UI.quantidade) > 0
			) t
			GROUP BY id_itens_skus
				{$addGroupBy}
			ORDER BY {$orderBy}
		";
		$saldos = dbFastQuery($sql);

		return $saldos;
	}


	public function consultarQuantidadeUmas($row, $whereSituacoes)
	{
		// Para obter a quantidade de UMAs deste item, deve-se levar em conta
		// que uma UMA pode ter mais de 1 item, e não deve ser contada 2 ou mais vezes
		// Para evitar isto, a query abaixo pode retornar que este item tem 0,5 UMA,
		// por exemplo, indicando que outro item também está dentro desta mesma UMA
		$sql = "
			SELECT
			-- Divide 1 pela quantidade de itens dentro da UMA
				SUM(1/
					(
						SELECT COUNT(DISTINCT id_itens_skus) skus
						FROM umas TU
						JOIN umas_itens TUI ON TU.id = TUI.id_umas
						WHERE S.id = TU.id AND TUI.cancelada = 0
					)
				) total
			FROM
			(
				-- Busca as UMAs de acordo com o SKU, lote e datas
				SELECT U.id,
					SUM(quantidade) qtd
				FROM umas U
				INNER JOIN umas_itens UI ON U.id = UI.id_umas
				WHERE
					U.ativo = 1
					AND UI.cancelada = 0
					AND UI.id_itens_skus = '" . $row['id_itens_skus'] . "'
					AND	UI.lote = '" . $row['lote'] . "'
					AND	UI.data_validade = '" . $row['data_validade'] . "'
					AND	UI.data_fabricacao = '" . $row['data_fabricacao'] . "'
					{$whereSituacoes}
				GROUP BY U.id
			) S
			WHERE qtd > 0
		";
		$umas = floatval(dbFastQuery($sql)[0]['total']);
		return str_replace(".", ",", round($umas, 1));
	}


	public function incrementarSubTotal($row)
	{
		$atributosSomar = array(
			"quantidade_sku",
			"quantidade_un",
			"peso_liquido",
			"peso_bruto",
			"m2",
			"m3",
			"valor_total",
			"valor_un",
			'paletes',
			'qtd_umas'
		);
		foreach ($atributosSomar as $atributo) {
			$valor = (float) $row[$atributo];
			$valor = number_format($valor, 10, '.', '');
			$this->subtotal[$atributo] += $valor;
			$this->total[$atributo] += $valor;
		}
	}


	public function exibirSubTotal($colspan, $agruparPor)
	{
		global $o;

		$mtz = array();
		$mtz[] = "~" . $colspan . "->Subtotal";
		$mtz[] = "->" . ((int) $this->subtotal['paletes']);

		if (gDBCheck($_REQUEST['exibirQtdUma'])) {
			$mtz[] = "->" . str_replace(".", ",", round($this->subtotal['qtd_umas'], 1));
		}

		$mtz[] = "->" . gFloat($this->subtotal['quantidade_sku']);
		$mtz[] = "->" . gFloat($this->subtotal['quantidade_un']);
		$mtz[] = "->" . gFloat($this->subtotal['peso_liquido']);
		$mtz[] = "->" . gFloat($this->subtotal['peso_bruto']);
		$mtz[] = "->" . gFloat($this->subtotal['m2']);
		$mtz[] = "->" . gFloat($this->subtotal['m3']);

		if (strstr($agruparPor, "t.valor")) {
			$mtz[] = "->" . gFloat($this->subtotal['valor_un']);
		}

		$mtz[] = "->" . gFloat($this->subtotal['valor_total']);

		$this->subtotal = array();

		return $o->tableRow($mtz, "grey");
	}


	public function exibirTotal($colspan, $agruparPor)
	{
		global $o;

		$mtz = array();
		$mtz[] = "~" . $colspan . "->Total";
		$mtz[] = "->" . ((int) $this->total['paletes']);

		if (gDBCheck($_REQUEST['exibirQtdUma'])) {
			$mtz[] = "->" . str_replace(".", ",", round($this->total['qtd_umas'], 1));
		}

		$mtz[] = "->" . gFloat($this->total['quantidade_sku']);
		$mtz[] = "->" . gFloat($this->total['quantidade_un']);
		$mtz[] = "->" . gFloat($this->total['peso_liquido']);
		$mtz[] = "->" . gFloat($this->total['peso_bruto']);
		$mtz[] = "->" . gFloat($this->total['m2']);
		$mtz[] = "->" . gFloat($this->total['m3']);

		if (strstr($agruparPor, "t.valor")) {
			$mtz[] = "->" . gFloat($this->total['valor_un']);
		}

		$mtz[] = "->" . gFloat($this->total['valor_total']);

		return $o->tableRow($mtz, "grey");
	}


	function obtemUmasItens($where, $groupBy = "", $tipo = 1)
	{
		global $usrCliente;

		if (!$usrCliente) {
			$whereDefault = " U.ativo=1 AND ";
		}
		$where = $whereDefault . $where;
		$sql = "
			SELECT
			SK.codigo, UI.lote, UI.data_fabricacao, UI.data_validade,
			CONCAT(I.nome, ' - ', CAST(SK.quantidade as SIGNED), ' x ', D.descricao) as nomeItem,
			I.nome as item,
			CONCAT(D.sigla, ' com ', CAST(SK.quantidade as SIGNED)) as sku,
			P.apelido, P.id as idProprietario, P.nome,
			UI.reservada, UI.separada, UI.bloqueada, UI.avariada,
			COUNT(U.id) umas,
			sum(UI.quantidade) quantidade,
			sum(UI.quantidade)*SK.quantidade quantidade_un,
			CEIL(SUM(UI.quantidade)/(SK.palete_lastro * SK.palete_altura)) paletes,
			(SK.peso_liquido * sum(UI.quantidade)) peso_liquido,
			(SK.peso_bruto * sum(UI.quantidade)) peso_bruto,
			(sum(UI.quantidade*SK.comprimento/100*SK.largura/100)) m2,
			(sum(UI.quantidade*SK.altura/100*SK.largura/100*SK.comprimento/100)) m3,
			(sum(UI.quantidade * UI.valor)) valor,
			UI.id_itens_skus
			{$groupBy}
		FROM umas U
		LEFT JOIN umas_itens UI ON U.id=UI.id_umas
		LEFT JOIN itens_skus SK ON UI.id_itens_skus=SK.id
		LEFT JOIN itens I ON I.id=SK.id_itens
		LEFT JOIN pessoas P ON (P.id=UI.id_pessoas_proprietario AND P.cliente=1)
		LEFT JOIN unidades D ON SK.id_unidades=D.id
		LEFT JOIN notas_itens NI ON UI.id_notas_itens=NI.id
		LEFT JOIN notas NF ON NF.id=NI.id_notas
		LEFT JOIN programacao PR ON UI.id_programacao = PR.id
		LEFT JOIN tipos_programacao TP ON TP.id = PR.id_tipos_programacao
		WHERE UI.cancelada = 0 AND {$where}
		GROUP BY
			SK.id, I.shelf_life, I.id, P.id, SK.quantidade, D.descricao,
			UI.reservada, UI.separada, UI.avariada, UI.bloqueada
			{$groupBy}
		HAVING SUM(UI.quantidade)>0";
		if ($this->orderBy <> "") {
			$sql .= " ORDER BY {$this->orderBy}";
		} else {
			$sql .= " ORDER BY P.apelido ASC, UI.separada DESC, UI.reservada DESC, UI.avariada DESC, UI.bloqueada DESC, I.nome ASC, SUM(UI.quantidade) ASC";
		}
		return (dbQuery($sql));
	}
	/******************************* ANALITICO *************************************************/

	function obtemRelatorioAnalitico($where, $tipoProgramacao = '1', $idNota = null, $idProprietario = null)
	{
		$where .= "  AND (UI.bloqueada=0 AND UI.avariada=0)";
		$where .= " AND (P.id_tipos_programacao in ({$tipoProgramacao}) AND P.ativo=1)";
		if (!is_null($idNota)) {
			$where .= ($idNota == 0)
				? " AND (NI.id_notas_associada = '{$idNota}')"
				: " AND NI.id_notas_associada = '{$idNota}'";
		}
		if (!is_null($idProprietario)) {
			$where .= " AND UI.id_pessoas_proprietario='{$idProprietario}'";
		}
		$sql = "SELECT
	    		CONCAT(CONCAT_WS(' • ',SK.codigo, I.descricao,UN.descricao), ' com ', CAST(SK.quantidade as SIGNED)) nomeItem,
	    		TP.descricao as tipo_programacao,
				NF.id id_nota,
				P.id id_programacao,
				P.os,
				P.id_tipos_programacao,
				NF.data_emissao n_data_emissao,
				NF.data_movimento n_data_movimento,
				NF.numero numero_nota,
				PE.nome cliente,
				SK.id id_sku,
				P.data_execucao_final,
				UI.lote,
				UI.data_fabricacao,
				UI.data_validade,
				UI.valor,
				UI.data,
				UN.descricao unidade,
				(max(UI.peso_liquido) * SUM(UI.quantidade)) peso_liquido,
				(max(UI.peso_bruto) * SUM(UI.quantidade)) peso_bruto,
				(max(UI.m2) * SUM(UI.quantidade)) m2,
				(max(UI.m3) * SUM(UI.quantidade)) m3,
				(max(UI.valor) * SUM(UI.quantidade)) valor,
				(NI.peso_liquido * SUM(NI.quantidade)) n_peso_liquido,
				(NI.peso_bruto * SUM(NI.quantidade)) n_peso_bruto,
				(NI.m2 * SUM(NI.quantidade)) n_m2,
				(NI.m3 * SUM(NI.quantidade)) n_m3,
				(NI.valor * SUM(NI.quantidade)) n_valor,
				SUM(UI.quantidade) quantidade,
				SUM(NI.quantidade) n_quantidade,
				COUNT(DISTINCT(U.id)) as qtdUMA,
				UI.id_pessoas_proprietario,
				PE.nome nome_proprietario,
				PE.apelido,
				SK.codigo
		FROM umas_itens UI
		LEFT JOIN umas U ON U.id = UI.id_umas
		LEFT JOIN programacao P ON P.id = UI.id_programacao
		LEFT JOIN tipos_programacao TP ON TP.id = P.id_tipos_programacao
		LEFT JOIN itens_skus SK on SK.id = UI.id_itens_skus
		LEFT JOIN itens I on I.id=SK.id_itens
		LEFT JOIN unidades UN on SK.id_unidades=UN.id
		LEFT JOIN pessoas PE on UI.id_pessoas_proprietario=PE.id
		LEFT JOIN notas_itens NI ON NI.id = UI.id_notas_itens
		LEFT JOIN notas NF on NF.id=NI.id_notas
		WHERE UI.cancelada='0' AND {$where}
		GROUP BY
			P.id, UI.id_pessoas_proprietario, SK.id, NF.id,
			TP.id, UI.lote, UI.data_fabricacao, UI.data_validade
		HAVING quantidade <> 0
		ORDER BY P.data_cadastro DESC, PE.apelido ASC, SK.id ASC, UI.data, P.id_tipos_programacao ASC, sum(UI.quantidade) ASC";

		return (dbQuery($sql));
	}


	public function camposPadrao($frm)
	{
		global $sp, $o, $usrCliente;

		$js = "
			function changeProprietario(idProprietario)
			{
				showWait();
				var rota='" . $o->page . "&gAjs=1&cmd=mudouProprietario&gIdProprietario='+idProprietario;
				$.ajax({
					method: 'GET',
					url:rota,
					success: function (resp)
					{
						hideWait();
						var itens=JSON.parse(resp);
					},
					error: function (resp)
					{
						hideWait();
					}
				});
			}
		";
		$o->addJavascript($js);

		if ($usrCliente) {
			$o->addJavascript("bloquearComboProprietario();");
			$idPessoasProprietario = $_SESSION['usrId'];
		}

		$frm->row(
			$frm->add("{ name: id_pessoas_proprietario; fieldLabel: Proprietário; type: combo; items:" . $sp["combo_proprietarios"] . "; value: " . $idPessoasProprietario . "; onChange: changeProprietario;}"),
			$frm->add(renderComboItem($sp["combo_itens"]))
		);

		$frm->row(
			$frm->add("{name: id_grupos; fieldLabel: Grupo; type:combo; items:" . $sp["combo_grupos"] . ";}"),
			$frm->add("{name: id_tipos; fieldLabel: Tipo; type: combo; items: " . $sp["combo_tipos"] . ";}")
		);
	}
}

class Operacoes extends Relatorios
{
	public function __construct() {}

	public function obtemBusca($request)
	{
		global $sp;

		$where = "UI.id > 0";
		$cabecalho = array();
		if ($request["id_pessoas_proprietario"]) {
			$idProprietario = $request["id_pessoas_proprietario"];
			$where .= " AND UI.id_pessoas_proprietario='{$idProprietario}'";
			$cabecalho[] = " Proprietário: " . dbQuery("SELECT nome FROM pessoas where id='$idProprietario'")[0]["nome"];
		}


		if ($request["id_itens_skus"]) {
			$idItem = $request["id_itens_skus"];
			$where .= " AND ISK.id = '$idItem'";
			$sql = "SELECT
            		ISK.id,
            		CONCAT(CONCAT_WS(' • ',ISK.codigo, I.descricao,U.descricao), ' com ', CAST(ISK.quantidade as SIGNED)) descricao
            	FROM itens I
            	LEFT JOIN itens_skus ISK on ISK.id_itens = I.id
            	LEFT JOIN unidades U on U.id = ISK.id_unidades
            	WHERE ISK.id='$idItem'
            	GROUP BY ISK.id, I.descricao";
			$cabecalho[] = " Item: " . dbQuery($sql)[0]["descricao"];
		}

		$cabecalho[] = " Mostrar total: " . gCheck($request['exibirTotal']);

		if ($request['sku']) {
			$idItemSkus =  dbQuery('SELECT id FROM itens_skus WHERE codigo = "' . $request["sku"] . '"')[0]['id'];
			$where .= ' AND ISK.id = "' . (int) $idItemSkus . '"';
			$sql = "SELECT
            		ISK.id,
            		CONCAT(CONCAT_WS(' • ',ISK.codigo, I.descricao,U.descricao), ' com ', CAST(ISK.quantidade as SIGNED)) descricao
            	FROM itens I
            	LEFT JOIN itens_skus ISK on ISK.id_itens = I.id
            	LEFT JOIN unidades U on U.id = ISK.id_unidades
            	WHERE ISK.id='{$idItemSkus}'
            	GROUP BY ISK.id, I.descricao";
			$cabecalho[] = " Item: " . dbQuery($sql)[0]["descricao"];
		}


		if ($request["apto"]) {
			$where .= " AND UI.reservada='0' AND UI.separada='0' AND UI.bloqueada='0' AND UI.avariada='0'";
			$cabecalho[] = "Itens aptos";
		}
		if ($request["id_grupos"]) {
			$idGrupo = $request["id_grupos"];
			$where .= " AND I.id_grupos='$idGrupo'";
			$cabecalho[] = "Grupo: " . dbQuery("SELECT descricao FROM grupos where id = '$idGrupo'")[0]["descricao"];
		}
		if ($request["id_tipos"]) {
			$idTipo = $request["id_tipos"];
			$where .= " AND I.id_tipos='$idTipo'";
			$cabecalho[] = "Tipo: " . dbQuery("SELECT descricao FROM tipos WHERE id='$idTipo'")[0]["descricao"];
		}
		if ($request["data_de"]) {
			$dataDe = $request["data_de"];
			$where .= " AND DATE(P.data_execucao_inicio)>='" . gDBDate($dataDe) . "'";
			$cabecalho[] = "De: " . $dataDe;
		}
		if ($request["data_ate"]) {
			$dataAte = $request["data_ate"];
			$where .= " AND DATE(P.data_execucao_inicio)<='" . gDBDate($dataAte) . "'";
			$cabecalho[] = "Até: " . $dataAte;
		}
		$busca = array();
		$busca["where"] = $where;
		$busca["cabecalho"] = $cabecalho;
		return ($busca);
	}

	public function obtemUmasItens($where)
	{

		$sql = "
			SELECT
				N.numero numeroNota,
				PE.apelido, PE.id as idProprietario, PE.nome,
				P.os,
				TP.descricao tipo_os,
				DATE(P.data_execucao_inicio) data,
				ISK.codigo,
				I.nome item,
				UN.descricao unidade,
				ISK.quantidade quantidade_sku,
				UI.reservada, UI.separada, UI.bloqueada, UI.avariada,
				SUM(UI.quantidade) quantidade,
				(max(UI.peso_bruto) * sum(UI.quantidade)) peso_bruto,
				(max(UI.peso_liquido) * sum(UI.quantidade)) peso_liquido,
				(max(UI.m2) * sum(UI.quantidade)) m2,
				(max(UI.m3) * sum(UI.quantidade)) m3
				FROM umas_itens UI
				LEFT JOIN umas U ON UI.id_umas = U.id
				LEFT JOIN programacao P ON P.id = U.id_programacao
				LEFT JOIN notas N ON U.id_notas = N.id
				LEFT JOIN pessoas PE ON PE.id = UI.id_pessoas_proprietario
				LEFT JOIN itens_skus ISK ON ISK.id = UI.id_itens_skus
				LEFT JOIN itens I ON I.id = ISK.id_itens
				LEFT JOIN tipos_programacao TP ON P.id_tipos_programacao=TP.id
				LEFT JOIN unidades UN ON UN.id = ISK.id_unidades
				WHERE {$where} AND UI.cancelada = '0'
				GROUP BY
					N.numero, UI.peso_bruto, UI.peso_liquido, UI.m2, UI.m3,
					UI.id_pessoas_proprietario,ISK.id, P.os,
					UI.reservada, UI.separada, UI.bloqueada, UI.avariada
				HAVING SUM(UI.quantidade) > 0
				ORDER BY PE.nome, P.data_execucao_inicio, P.os, I.nome
				";
		return (dbQuery($sql));
	}
}

class Transito extends Relatorios
{
	function __construct() {}

	function obtemVeiculos($where = "", $orderBy = "")
	{
		global $usrCliente;

		$sql = "SELECT
                VA.*,
                PP.nome as nome_completo_proprietario,
                PP.apelido as proprietario,
                PT.apelido as motorista,
                V.placa,
                TVM.descricao as tipo_veiculo_marca,
                TV.descricao as tipo_veiculo,
                PC.apelido as criado_por,
                PE.apelido as criou_entrada,
                PS.apelido as criou_saida,
                PAE.apelido as autorizou_entrada,
                PAS.apelido as autorizou_saida,
                PCA.apelido as cancelou,
                M.nome AS nome_motorista,
                TRANSPORTADORA.nome AS transportadora
            FROM veiculos_acessos VA
            LEFT JOIN veiculos V ON VA.id_veiculos = V.id
            LEFT JOIN tipos_veiculos TV ON TV.id = V.id_tipos_veiculos
            LEFT JOIN tipos_veiculos_marcas TVM ON TVM.id = V.id_tipos_veiculos_marcas
            LEFT JOIN enderecos_cidades EC ON EC.id = V.id_enderecos_cidades
            LEFT JOIN pessoas PT ON PT.id = VA.id_pessoas_transportadora
            LEFT JOIN pessoas PP ON PP.id = VA.id_pessoas_cliente
            LEFT JOIN pessoas PC ON PC.id = VA.id_pessoas_criou
            LEFT JOIN pessoas PE ON PE.id = VA.id_pessoas_entrada
            LEFT JOIN pessoas PS ON PS.id = VA.id_pessoas_saida
            LEFT JOIN pessoas PAE ON PAE.id = VA.id_pessoas_autorizou_entrada
            LEFT JOIN pessoas PAS ON PAS.id = VA.id_pessoas_autorizou_saida
            LEFT JOIN pessoas PCA ON PCA.id = VA.id_pessoas_cancelou
            LEFT JOIN pessoas M ON M.id = VA.id_pessoas_motorista
            LEFT JOIN pessoas TRANSPORTADORA ON TRANSPORTADORA.id = VA.id_pessoas_transportadora
        ";

		if ($where <> "") {
			$sql .= " " . $where;
		}

		if ($orderBy <> "") {
			$sql .= " " . $orderBy;
		}

		return (dbQuery($sql));
	}


	function obtemBuscaVeiculo($req)
	{
		global $usrCliente, $usrId, $usrName, $usrNickname;
		$busca = array();
		$where = "WHERE VA.id > 0";
		if ($usrCliente == 1) {
			$where .= " AND VA.cancelado=0 ";
		}
		$cabecalho = array();
		if ($req["somente_patio"]) {
			$where .= " AND VA.data_saida='0000-00-00 00:00:00'";
		}

		if ($req["placa"]) {
			$placa = gCleanField($req["placa"]);
			$where .= " AND V.placa like '%{$placa}%'";
			$cabecalho[] = "Placa: " . $placa;
		}

		if ($req["id_tipos_veiculos"]) {
			$where .= " AND V.id_tipos_veiculos='" . intval($req["id_tipos_veiculos"]) . "'";
			$cabecalho[] = "Tipo de veículo: " . gFieldById("tipos_veiculos", intval($req["id_tipos_veiculos"]), "descricao");
		}

		if ($req["id_enderecos_cidades"]) {
			$where .= " AND V.id_enderecos_cidades='" . intval($req["id_enderecos_cidades"]) . "'";
			$cabecalho[] = "Cidade: " . gFieldById("enderecos_cidades", intval($req["id_enderecos_cidades"]), "descricao");
		}

		if ($req["id_pessoas_motorista"]) {
			$where .= " AND VA.id_pessoas_motorista='" . intval($req["id_pessoas_motorista"]) . "'";
			$cabecalho[] = "Motorista: " . gFieldById("pessoas", intval($req["id_pessoas_motorista"]), "apelido");
		}

		if ($req["id_tipos_veiculos_marcas"]) {
			$where .= " AND VA.id_tipos_veiculos_marcas='" . intval($req["id_tipos_veiculos_marcas"]) . "'";
			$cabecalho[] = "Marca: " . gFieldById("tipos_veiculos_marcas", intval($req["id_tipos_veiculos_marcas"]), "descricao");
		}

		if ($usrCliente == 1) {
			$where .= " AND VA.cancelado=0 AND VA.id_pessoas_cliente=" . intval($usrId);
			$descProprietario = "{$usrName} ({$usrNickname})";
			$cabecalho[] = "Proprietário: " . $descProprietario;
		} else {
			if ($req["id_pessoas_cliente"]) {
				$where .= " AND VA.id_pessoas_cliente='" . intval($req["id_pessoas_cliente"]) . "'";
				$cabecalho[] = "Proprietário: " . gFieldById("pessoas", intval($req["id_pessoas_cliente"]), "apelido");
			}
		}

		if ($req["id_pessoas_transportadora"]) {
			$where .= " AND VA.id_pessoas_transportadora='" . intval($req["id_pessoas_transportadora"]) . "'";
			$cabecalho[] = "Transportadora: " . gFieldById("pessoas", intval($req["id_pessoas_transportadora"]), "apelido");
		}

		if ($req["data_chegada_de"] && !$req["data_chegada_ate"]) {
			$dataChegadaDe = gDBDate($req["data_chegada_de"]);
			$where .= " AND data_chegada>='$dataChegadaDe'";
			$cabecalho[] = "Data de chegada de: " . $req["data_chegada_de"];
		}
		if (!$req["data_chegada_de"] && $req["data_chegada_ate"]) {
			$dataChegadaAte = date("Y-m-d 23:59:59", strtotime(gDBDate($req["data_chegada_ate"])));
			$where .= " AND data_chegada <= '$dataChegadaAte'";
			$cabecalho[] = "Data de chegada até: " . $req["data_chegada_ate"];
		}
		if ($req["data_chegada_de"] && $req["data_chegada_ate"]) {
			$dataChegadaDe = gDBDate($req["data_chegada_de"]);
			$dataChegadaAte = date("Y-m-d 23:59:59", strtotime(gDBDate($req["data_chegada_ate"])));
			$where .= " AND (data_chegada>='{$dataChegadaDe}' AND data_chegada <= '{$dataChegadaAte}')";
			$cabecalho[] = "Data de chegada de: " . $req["data_chegada_de"] . " até " . $req["data_chegada_ate"];
		}
		if ($req["data_entrada_de"] && !$req["data_entrada_ate"]) {
			$dataEntradaDe = gDBDate($req["data_entrada_de"]);
			$where .= " AND data_entrada>='{$dataEntradaDe}'";
			$cabecalho[] = "Data de entrada de: " . $req["data_entrada_de"];
		}
		if (!$req["data_entrada_de"] && $req["data_entrada_ate"]) {
			$dataEntradaAte = date("Y-m-d 23:59:59", strtotime(gDBDate($req["data_entrada_ate"])));
			$where .= " AND data_entrada<='{$dataEntradaAte}'";
			$cabecalho[] = "Data de entrada até: " . $req["data_entrada_ate"];
		}
		if ($req["data_entrada_de"] && $req["data_entrada_ate"]) {
			$dataEntradaDe  = gDBDate($req["data_entrada_de"]);
			$dataEntradaAte = date("Y-m-d 23:59:59", strtotime(gDBDate($req["data_entrada_ate"])));
			$where .= " AND (data_entrada>='{$dataEntradaDe}' AND data_entrada <= '{$dataEntradaAte}')";
			$cabecalho[] = "Data de entrada de: " . $req["data_entrada_de"] . " até: " . $req["data_entrada_ate"];
		}
		if ($req["data_saida_de"] && !$req["data_saida_ate"]) {
			$dataSaidaDe = gDBDate($req["data_saida_de"]);
			$where .= " AND data_saida>='{$dataSaidaDe}'";
			$cabecalho[] = "Data de saída de: " . $req["data_saida_de"];
		}
		if (!$req["data_saida_de"] && $req["data_saida_ate"]) {
			$dataSaidaAte = date("Y-m-d 23:59:59", strtotime(gDBDate($req["data_saida_ate"])));
			$where .= " AND data_saida<='$dataSaidaAte'";
			$cabecalho[] = "Data de saída até: " . $req["data_saida_ate"];
		}
		if ($req["data_saida_de"] && $req["data_saida_ate"]) {
			$dataSaidaDe = gDBDate($req["data_saida_de"]);
			$dataSaidaAte = date("Y-m-d 23:59:59", strtotime(gDBDate($req["data_saida_ate"])));
			$where .= " AND (data_saida>='$dataSaidaDe' AND data_saida<='$dataSaidaAte')";
			$cabecalho[] = "Data de saída de: " . $req["data_saida_de"] . " até: " . $req["data_saida_ate"];
		}
		if (!$req["apto"]) {
			$where .= " AND cancelado = '0'";
			$cabecalho[] = " Exibir cancelados";
		}
		$busca["where"] = $where;
		$busca["cabecalho"] = $cabecalho;
		return ($busca);
	}

	function obtemPessoas($where = "", $orderBy = "PA.id_setores")
	{
		$sql = "SELECT
				P.apelido as visitante,
				PE.apelido as pessoa_entrada,
				PC.apelido as pessoa_criou,
				PS.apelido as pessoa_saida,
				PCA.apelido as pessoa_cancelou,
				PAE.apelido as pessoa_autorizou_entrada,
				PAS.apelido as pessoa_Autorizou_saida,
				PA.data_chegada,
				PA.data_cadastro,
				PA.data_entrada,
				PA.data_saida,
				PA.data_autorizou_entrada,
				PA.data_autorizou_saida,
				PA.data_cancelamento,
				S.descricao as setor
			  FROM pessoas_acessos PA
			  LEFT JOIN pessoas P ON PA.id_pessoas = P.id
			  LEFT JOIN pessoas PE ON PE.id = PA.id_pessoas_entrada
			  LEFT JOIN pessoas PC ON PC.id = PA.id_pessoas_criou
			  LEFT JOIN pessoas PS ON PS.id = PA.id_pessoas_saida
			  LEFT JOIN pessoas PCA ON PCA.id = PA.id_pessoas_cancelou
			  LEFT JOIN pessoas PAE ON PAE.id = PA.id_pessoas_autorizou_entrada
			  LEFT JOIN pessoas PAS ON PAS.id = PA.id_pessoas_autorizou_saida
			  LEFT JOIN setores S ON S.id = PA.id_setores
		";
		if ($where <> "") {
			$sql .= " WHERE $where";
		}
		if ($orderBy <> "") {
			$sql .= " ORDER BY $orderBy";
		}
		return (dbQuery($sql));
	}

	function obtemBuscaPessoa($req)
	{
		$filtro = array();
		$where = "PA.id > 0";
		$cabecalho = array();
		if ($req["nome"]) {
			$nome = gCleanField($req["nome"]);
			$where .= " AND P.nome like '%{$nome}%'";
			$cabecalho[] = " Pessoa: " . $nome;
		}

		if ($req["apelido"]) {
			$apelido = gCleanField($req["apelido"]);
			$where .= " AND P.apelido like '%{$apelido}%'";
			$cabecalho[] = "Apelido: " . $apelido;
		}

		if ($req["id_setores"]) {
			$where .= " AND PA.id_setores = '" . intval($req["id_setores"]) . "'";
			$cabecalho[] = "Setor: " . gFieldById("setores", intval($req["id_setores"]), "descricao");
		}

		if ($req["data_chegada_de"] && !$req["data_chegada_ate"]) {
			$dataChegadaDe = gDBDate($req["data_chegada_de"]);
			$where .= " AND PA.data_chegada >= '{$dataChegadaDe}'";
			$cabecalho[] = "Data de chegada de: " . $req["data_chegada_de"];
		}

		if (!$req["data_chegada_de"] && $req["data_chegada_ate"]) {
			$dataChegadaAte = date("Y-m-d 23:59", strtotime(gDBDate($req["data_chegada_ate"])));
			$where .= " AND PA.data_chegada <= '{$dataChegadaAte}'";
			$cabecalho[] = "Data de chegada até: " . $req["data_chegada_ate"];
		}

		if ($req["data_chegada_de"] && $req["data_chegada_ate"]) {
			$dataChegadaDe = gDBDate($req["data_chegada_de"]);
			$dataChegadaAte = date("Y-m-d 23:59", strtotime(gDBDate($req["data_chegada_ate"])));
			$where .= " AND (PA.data_chegada >= '{$dataChegadaDe}' AND PA.data_chegada<='{$dataChegadaAte}')";
			$cabecalho[] = "Data de chegada de: " . $req["data_chegada_de"] . " até: " . $req["data_chegada_ate"];
		}

		if ($req["data_entrada_de"] && !$req["data_entrada_ate"]) {
			$dataEntradaDe = gDBDate($req["data_entrada_de"]);
			$where .= " AND PA.data_entrada >= '{$dataEntradaDe}'";
			$cabecalho[] = "Data de entrada de: " . $req["data_entrada_de"];
		}

		if (!$req["data_entrada_de"] && $req["data_entrada_ate"]) {
			$dataEntradaAte = date("Y-m-d 23:59:59", strtotime(gDBDate($req["data_entrada_ate"])));
			$where .= " AND PA.data_entrada <= '{$dataEntradaAte}'";
			$cabecalho[] = "Data de entrada até: " . $req["data_entrada_ate"];
		}

		if ($req["data_entrada_de"] && $req["data_entrada_ate"]) {
			$dataEntradaDe = gDBDate($req["data_entrada_de"]);
			$dataEntradaAte = date("Y-m-d 23:59:59", strtotime(gDBDate($req["data_entrada_ate"])));
			$where .= " AND (PA.data_entrada >= '{$dataEntradaDe}' AND PA.data_entrada <= '{$dataEntradaAte}')";
			$cabecalho[] = "Data de entrada de: " . $req["data_entrada_de"] . " até " . $req["data_entrada_ate"];
		}

		if ($req["data_saida_de"] && !$req["data_saida_ate"]) {
			$dataSaidaDe = gDBDate($req["data_saida_de"]);
			$where .= " AND PA.data_saida >= '{$dataSaidaDe}'";
			$cabecalho[] = "Data de saída de: " . $req["data_saida_de"];
		}

		if (!$req["data_saida_de"] && $req["data_saida_ate"]) {
			$dataSaidaAte = date("Y-m-d 23:59:59", strtotime(gDBDate($req["data_saida_ate"])));
			$where .= " AND PA.data_saida <= '{$dataSaidaAte}'";
			$cabecalho[] = "Data de saída até: " . $req["data_saida_ate"];
		}

		if ($req["data_saida_ate"] && $req["data_saida_ate"]) {
			$dataSaidaDe = gDBDate($req["data_saida_de"]);
			$dataSaidaAte = date("Y-m-d 23:59:59", strtotime(gDBDate($req["data_saida_ate"])));
			$where .= " AND (PA.data_saida >= '{$dataSaidaDe}' AND PA.data_saida <= '{$dataSaidaAte}')";
			$cabecalho[] = "Data de saída de: " . $req["data_saida_de"] . " até " . $req["data_saida_ate"];
		}
		/* Sem proprietário na tabela */
		if ($req["id_pessoas_proprietario"]) {
			$idProprietario = intval($req["id_pessoas_proprietario"]);
			//$where.= " AND (PA.id_pessoas_proprietario)"
		}
		$filtro["where"] = $where;
		$filtro["cabecalho"] = $cabecalho;
		return ($filtro);
	}
}

class InventarioRelatorio extends Relatorios
{
	function __construct() {}

	public function somarHoras($horas)
	{
		$soma = [0, 0, 0];
		foreach ($horas as $key => $val) {
			$horas[$key] = explode(':', $val);
			for ($i = 0; $i < count($horas[$key]); $i++) {
				$soma[$i] += $horas[$key][$i];
			}
		}

		$totalSegundos = ($soma[0] * 3600) + ($soma[1] * 60) + $soma[2];

		$horaSomada = sprintf(
			'%02d:%02d:%02d',
			floor($totalSegundos / 3600),
			floor(($totalSegundos % 3600) / 60),
			$totalSegundos % 60
		);
		return $horaSomada;
	}

	function obtemRegistros($where = "IFI.id > 0")
	{
		$sql = "SELECT
					IFI.*,
					P.codigo_barras codigo_barras_posicao,
					U.codigo_barras codigo_barras_umas,
					PP.apelido apelido_proprietario,
					PA.apelido apelido_aceitou,
					IK.codigo  codigo_sku,
					N.numero numero_nf,
					I.nome  nome_item,
					UN.descricao  unidade,
					IK.quantidade quantidade_item,
					UI.quantidade,
					UI.valor,
					A.descricao area_descricao,
					PR.os,
					PP.nome nome_proprietario,
					IFI.peso_liquido,
					IFI.peso_bruto,
					PR.data_execucao_inicio,
					PR.data_execucao_final
			  FROM inventarios_finalizados IFI
			  LEFT JOIN programacao PR ON PR.id = IFI.id_programacao
			  LEFT JOIN posicoes P ON IFI.id_posicoes = P.id
			  LEFT JOIN areas A ON A.id = P.id_areas
			  LEFT JOIN umas U ON U.id = IFI.id_umas
			  LEFT JOIN umas_itens UI ON UI.id_umas = U.id
			  LEFT JOIN itens_skus IK ON IK.id = UI.id_itens_skus
			  LEFT JOIN itens I ON I.id = IK.id_itens
			  LEFT JOIN unidades UN ON UN.id = IK.id_unidades
			  LEFT JOIN notas N ON IFI.id_notas = N.id
			  LEFT JOIN pessoas PP ON IFI.id_pessoas_proprietario = PP.id
			  LEFT JOIN pessoas PA ON IFI.id_pessoas_aceitou = PA.id
			  WHERE {$where}
			  GROUP BY 	U.id, P.id, PP.id, PA.id, UI.id_itens_skus, UI.data_validade, PR.id
			  ORDER BY PR.id desc, PP.apelido asc;
			";
		return (dbQuery($sql));
	}

	function totalRegistrosEmAndamento($where)
	{
		$where[] = "(INV.id)";
		$where = implode(" AND ", $where);
		$sql = "SELECT
					PO.codigo_barras posicao
				FROM inventarios INV
				LEFT JOIN posicoes PO ON PO.id = INV.id_posicoes
				LEFT JOIN pessoas P ON (INV.id_pessoas=P.id and P.cliente='0')
				LEFT JOIN itens_skus SK ON INV.id_itens_skus=SK.id
				LEFT JOIN itens I ON SK.id_itens=I.id
				LEFT JOIN unidades UN ON SK.id_unidades=UN.id
				LEFT JOIN umas U ON INV.id_umas=U.id
				WHERE {$where}
				GROUP BY PO.codigo_barras
				ORDER BY PO.codigo_barras ASC";
		$rs = dbQuery($sql);
		return ($rs);
	}

	function obtemRegistrosEmAndamentoNovo($where)
	{
		global $gParam;
		$where[] = "(INVP.id > 0) AND (PO.ativo = 1) ";
		$where = implode(" AND ", $where);

		$qtdContagensMin = 2;
		if ($gParam['CONTAGENS_INVENTARIO']['ativo']) {
			$qtdContagensMin = (int) $gParam['CONTAGENS_INVENTARIO']['valor'];
		}

		$sql = "SELECT
				INVP.id,
				INVP.contagens,
				INV.id_umas,
				INV.id_posicoes,
				INV.data,
				INV.id_programacao,
				INV.id_pessoas,
				ISK.data_fabricacao,
				ISK.data_validade,
				ISK.lote,
				ISK.quantidade,
				ISK.id_itens_skus,
				U.codigo_barras uma,
				P.apelido usuario,
				I.nome,
				SK.codigo,
				PR.os,
				UN.descricao unidade,
				SK.quantidade quantidade_sku,
				PO.codigo_barras posicao,
				IF (COUNT(*) >= {$qtdContagensMin}, 1, 0) AS conferida
			FROM programacao_inventario_posicoes INVP
			LEFT JOIN programacao PR ON PR.id = INVP.id_programacao
			LEFT JOIN inventarios INV ON INV.id_programacao_inventario_posicoes = INVP.id
			LEFT JOIN inventarios_skus ISK ON ISK.id_inventarios = INV.id
			LEFT JOIN posicoes PO ON PO.id = INVP.id_posicoes
			LEFT JOIN pessoas P ON (INV.id_pessoas = P.id AND P.cliente = '0')
			LEFT JOIN itens_skus SK ON ISK.id_itens_skus = SK.id
			LEFT JOIN itens I ON SK.id_itens = I.id
			LEFT JOIN unidades UN ON SK.id_unidades = UN.id
			LEFT JOIN umas U ON INV.id_umas = U.id
			WHERE {$where} AND PR.executada = 0
			GROUP BY PR.id,
				PO.id,
				U.id,
				SK.id,
				ISK.data_fabricacao,
				ISK.data_validade,
				ISK.lote,
				ISK.quantidade
			ORDER BY PO.codigo_barras ASC, U.id ASC, INV.id ASC";
		$rs = dbQuery($sql);

		$return = array();
		$return["total"] = $rs;
		$return["umasAprovadas"] = array_filter($rs, function ($uma) {
			return $uma['conferida'] == 1;
		});

		return ($return);
	}

	function obtemRegistrosEmAndamento($where, $situacao)
	{
		$pr = new Programacao();
		$where[] = "(INV.id > 0) AND PO.ativo=1 ";
		$where = implode(" AND ", $where);
		$sql = "SELECT INV.*, U.codigo_barras uma, P.apelido usuario, I.nome, SK.codigo, UN.descricao unidade, INV.quantidade, SK.quantidade quantidade_sku, PO.codigo_barras posicao
				FROM inventarios INV
				LEFT JOIN posicoes PO ON PO.id = INV.id_posicoes
				LEFT JOIN pessoas P ON (INV.id_pessoas=P.id and P.cliente='0')
				LEFT JOIN itens_skus SK ON INV.id_itens_skus=SK.id
				LEFT JOIN itens I ON SK.id_itens=I.id
				LEFT JOIN unidades UN ON SK.id_unidades=UN.id
				LEFT JOIN umas U ON INV.id_umas=U.id
				WHERE {$where}
				ORDER BY PO.codigo_barras ASC, U.id ASC, INV.id";
		$rs = dbQuery($sql);
		/* Como não temos controles no banco de sobre a divergência do inventário, verificar no php e manter o código centralizado em um método */
		if ($situacao == 0) {
			return ($rs);
		} else if ($situacao == 1) {
			$return = array();
			foreach ($rs as $r) {
				if (!$pr->verificaContagem($r)) {
					$return[] = $r;
				}
			}
			return ($return);
		} else if ($situacao == 2) {
			$return = array();
			foreach ($rs as $r) {
				if ($pr->verificaContagem($r)) {
					$return[] = $r;
				}
			}
			return ($return);
		}
	}

	function verificarSituacaoPosicao($where)
	{
		$pr = new Programacao();
		$posicoes = $conferenciasPosicao = $this->obtemRegistrosEmAndamentoNovo($where)["total"];
		$posicoesLiberadas = array();
		$umasLiberadas = array();
		foreach ($posicoes as $conferencia) {
			$umasLiberadas = array();
			$situacao = false;
			if ($conferencia["liberar"] == "liberado") {
				$situacao = true;
				$umasLiberadas[] = $conferencia["id_umas"];
			} else {
				if (in_array($conferencia["id_umas"], $umasLiberadas)) {
					$situacao = true;
				}
			}
			if ($situacao) {
				if (!in_array($conferencia["id_posicoes"], $posicoesLiberadas)) {
					$posicoesLiberadas[] = $conferencia["id_posicoes"];
				}
			}
		}
		return ($posicoesLiberadas);
	}

	function verificarSituacaoUMA($inv)
	{
		$pr = new Programacao();
		$where = array();
		$where[] = "(INV.id_umas='" . $inv["id_umas"] . "')";
		$where[] = "(INV.id_posicoes='" . $inv["id_posicoes"] . "')";
		$rs = $this->obtemRegistrosEmAndamento($where);
		$situacao = false;
		foreach ($rs as $row) {
			if ($pr->verificaContagem($row)) {
				$situacao = true;
			}
		}
		return ($situacao);
	}

	function obtemDetalhes($where)
	{
		$sql = "SELECT PP.id id_posicoes, INV.*, U.id id_umas, U.codigo_barras uma, P.apelido usuario, I.nome, SK.codigo, UN.descricao unidade, INV.quantidade, SK.quantidade quantidade_sku, PP.codigo_barras codigo_posicao
				FROM inventarios INV
				LEFT JOIN pessoas P ON (INV.id_pessoas=P.id and P.cliente='0')
				LEFT JOIN itens_skus SK ON INV.id_itens_skus=SK.id
				LEFT JOIN itens I ON SK.id_itens=I.id
				LEFT JOIN unidades UN ON SK.id_unidades=UN.id
				LEFT JOIN umas U ON INV.id_umas=U.id
				LEFT JOIN posicoes PP ON PP.id = INV.id_posicoes
				WHERE {$where}";
		return (dbQuery($sql));
	}

	public function desenhaBarra($nome, $valor, $valorMaximo)
	{
		$sai .= '<div class="row">';
		$sai .= '<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">' . $o->n;
		$sai .= $nome;
		$sai .= "</div>" . $o->n;
		$sai .= '<div class="col-lg-8 col-md-10 col-sm-10 col-xs-10">' . $o->n;
		$cor = 'success';
		$valorPercentual = ($valor * 100) / $valorMaximo;
		if ($valorPercentual > 75)
			$cor = 'warning';
		if ($valorPercentual > 90)
			$cor = 'danger';
		$sai .= '
		<div style="height: 20px" class="progress">
		<div id="barra" class="progress-bar progress-bar-' . $cor . '" role="progressbar" aria-valuenow="' . $valor . '" aria-valuemin="0" aria-valuemax="' . $valorMaximo . '" style="width: ' . $valorPercentual . '%">
		' . $valor . '
		</div>
		</div>
		';
		$sai .= "</div>" . $o->n;
		$sai .= "<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'><span id='ttPosicoes'></span></div>";
		$sai .= '</div>';
		return ($sai);
	}

	public function renderizarLabel($conteudo, $tipo, $atributos)
	{
		global $o;
		if (
			$_REQUEST['gPDF']
			|| $_REQUEST['gCSV']
			|| $_REQUEST['gXLS']
			|| $_REQUEST['gDOC']
		) {
			return $conteudo;
		}

		return $o->label($conteudo, $tipo, $atributos);
	}

	public function linhaAnaliseAcuracia($parametro)
	{
		global $o;
		$codigosItens = array_unique(array_merge(array_keys($parametro['saldoAntigo']), array_keys($parametro['saldoNovo'])));

		foreach ($codigosItens as $codigo) {
			$diferenca = $parametro['saldoAntigo'][$codigo] + $parametro['saldoNovo'][$codigo];
			if ($diferenca > 0) { //sobra
				$indices['sobra']++;
				$indicesQuantitativos['sobra'] += $diferenca;
			} elseif ($diferenca < 0) { //falta
				$indices['falta']++;
				$indicesQuantitativos['falta'] += $diferenca;
			} else { //sem divergencia
				$indices['acuracia']++;
			}
		}

		$quantidadeCodigos = count($codigosItens);
		$tituloLabels = array(
			'total' => "title='Quantidade de SKUs contados'",
			'sobra' => "title='Quantidade de SKUs cuja contagem do colaborador é superou o sistema'",
			'falta' => "title='Quantidade de SKUs cuja contagem do colaborador é inferior o sistema'",
			'divergencia' => "title='Quantidade de SKUs que tiveram divergência na contagem'",
			'diferenca' => "title='Somatório da coluna Diferença'",
			'ajuste' => "title='Somatório da coluna Ajuste já dada em módulo'",
			'acuracia' => "title='Quantidade de SKUs cuja contagem do colaborador é igual a do sistema'"
		);
		$mtz = array();
		if (gDBCheck($_REQUEST['apenas_divergencia']) && !gDBCheck($_REQUEST['detalhar_acuracidade'])) {
			//Se for apenas divergencia o calculo sera alterado pois nao ha acuracia, apenas divergencia
			$mtz[] = '~3<>' . $this->renderizarLabel("Total de SKUs: " . $quantidadeCodigos . ' (100%)', 'default', $tituloLabels['total']);
			$mtz[] = '~4<>' . $this->renderizarLabel("Sobra: " . $this->calcularPorcentagem($quantidadeCodigos, $indices['sobra']), 'warning', $tituloLabels['sobra']);
			$mtz[] = '~3<>' . $this->renderizarLabel("Falta: " . $this->calcularPorcentagem($quantidadeCodigos, $indices['falta']), 'warning', $tituloLabels['falta']);
		} else if (gDBCheck($_REQUEST['detalhar_acuracidade'])) {
			//Se for detalhar acuracidade, exibimos o cálculo da diferença, acuracia e ajustes
			$mtz[] = '~3<>' . $this->renderizarLabel("Total de SKUs: " . $quantidadeCodigos . ' (100%)', 'default', $tituloLabels['total']);
			$mtz[] = '<>' 	. $this->renderizarLabel("Acurácia: " . $this->calcularPorcentagem($quantidadeCodigos, $indices['acuracia']), 'success', $tituloLabels['acuracia']);
			$mtz[] = '~4<>' . $this->renderizarLabel("Divergência total: " . $this->calcularPorcentagem($quantidadeCodigos, $indices['falta'] + $indices['sobra']), 'danger', $tituloLabels['divergencia']);
			$mtz[] = '<>' 	. $this->renderizarLabel("Diferenças: "  . ($indicesQuantitativos['sobra'] + $indicesQuantitativos['falta']), 'warning', $tituloLabels['diferenca']);
			$mtz[] = '<>' 	. $this->renderizarLabel("Ajustes: " . (abs($indicesQuantitativos['sobra']) + abs($indicesQuantitativos['falta'])), 'danger', $tituloLabels['ajuste']);
			$mtz[] = '~2<>' . $this->renderizarLabel("Sobra: " . $this->calcularPorcentagem($quantidadeCodigos, $indices['sobra']), 'warning', $tituloLabels['sobra']);
			$mtz[] = '~2<>' . $this->renderizarLabel("Falta: " . $this->calcularPorcentagem($quantidadeCodigos, $indices['falta']), 'warning', $tituloLabels['falta']);
		} else {
			$mtz[] = '~2<>' . $this->renderizarLabel("Total de SKUs: " . $quantidadeCodigos . ' (100%)', 'default', $tituloLabels['total']);
			$mtz[] = '~2<>' . $this->renderizarLabel("Acurácia: " . $this->calcularPorcentagem($quantidadeCodigos, $indices['acuracia']), 'success', $tituloLabels['acuracia']);
			$mtz[] = '~2<>' . $this->renderizarLabel("Divergência total: " . $this->calcularPorcentagem($quantidadeCodigos, $indices['falta'] + $indices['sobra']), 'danger', $tituloLabels['total']);
			$mtz[] = '~2<>' . $this->renderizarLabel("Sobra: " . $this->calcularPorcentagem($quantidadeCodigos, $indices['sobra']), 'warning', $tituloLabels['sobra']);
			$mtz[] = '~2<>' . $this->renderizarLabel("Falta: " . $this->calcularPorcentagem($quantidadeCodigos, $indices['falta']), 'warning', $tituloLabels['falta']);
		}

		return $o->tableRow($mtz, 'detail');
	}

	public function somarContagensPorTipo($contagens) {
	    $somas = [0, 0];
	    foreach ($contagens as $quantidade) {
	        if (is_numeric($quantidade)) $somas[$quantidade >= 0] += $quantidade;
	    }

	    return [
	    	'negativo' => $somas[0],
	    	'positivo' => $somas[1]
	    ];
	}

}

class AtividadeRelatorio extends Relatorios
{
	function __construct() {}


	function obtemBusca($req)
	{
		$filtros = array();
		$where = array();
		$cabecalho = array();

		if ($req['detalhes']) {
			$detalhes = gCleanField($req["detalhes"]);
			$where[] = "PA.descricao like '%{$detalhes}%'";
			$cabecalho[] = "Detalhes: " . $os;
		}

		if ($req['os']) {
			$os = gCleanField($req["os"]);
			$where[] = "P.os like '%{$os}%'";
			$cabecalho[] = "OS: " . $os;
		}

		if ($req['id_tipos_atividades']) {
			$tipoAtividade = intval($req['id_tipos_atividades']);
			$where[] = "PA.id_tipos_atividades = '{$tipoAtividade}'";
			$cabecalho[] = "Tipo de Atividade: " . gFieldById("tipos_atividades", $tipoAtividade, "descricao");
		}

		if ($req['id_pessoas']) {
			$idPessoa = intval($req['id_pessoas']);
			$where[] = "PA.id_pessoas = '{$idPessoa}'";
			$cabecalho[] = "Colaborador: " . gFieldById("pessoas", $idPessoa, "nome");
		}

		if ($req['id_tipos_programacao']) {
			$tipoProgramacao = intval($req['id_tipos_programacao']);
			$where[] = "P.id_tipos_programacao = '{$tipoProgramacao}'";
			$cabecalho[] = "Tipo de programação: " . gFieldById("tipos_programacao", $tipoProgramacao, "descricao");
		}

		if ($req['id_pessoas_proprietario']) {
			$idProprietario = intval($req['id_pessoas_proprietario']);
			$where[] = "P.id_pessoas_proprietario = '{$idProprietario}'";

			/* obtemProprietario */

			$cabecalho[] = "Proprietário: " . obtemProprietario($idProprietario, "apelido")["apelido"];
		}
		if ($req['data_inicio']) {
			$dataInicio = gDBDate($req['data_inicio']);
			$where[] = "PA.data>='{$dataInicio}'";
			$cabecalho[] = "Data da atividade de: " . $req["data_inicio"];
		}
		if ($req['data_fim']) {
			$dataFinal = date('Y-m-d 23:59:59', strtotime(gDBDate($req['data_fim'])));
			$where[] = "PA.data<='{$dataFinal}'";
			$cabecalho[] = "Data da atividade até " . $req["data_fim"];
		}

		$cabecalho[] = "Mostrar subtotais: " . gCheck($req['exibirTotal']);

		$filtros["where"] = implode(" AND ", $where);
		$filtros["cabecalho"] = implode(" • ", $cabecalho);
		return ($filtros);
	}

	function obtemRegistros($filtro)
	{
		if (empty($filtro)) {
			$filtro = "PA.id > 0";
		}
		if (gDBCheck($_REQUEST['consolidar'])) {
			$sql = "SELECT
					PE.apelido,
					TP.descricao tipo_programacao,
					TA.descricao tipo_atividade,
					PA.cancelada,
					COUNT(PA.id) total,
					sum(PA.quantidade) quantidade
				  FROM programacao_atividades PA
				  LEFT JOIN programacao P ON P.id = PA.id_programacao
				  LEFT JOIN pessoas PP ON PP.id = P.id_pessoas_proprietario AND PP.cliente='1'
				  LEFT JOIN pessoas PE ON PE.id = PA.id_pessoas AND PE.cliente='0'
				  LEFT JOIN tipos_atividades TA ON TA.id = PA.id_tipos_atividades
				  LEFT JOIN tipos_programacao TP ON TP.id = P.id_tipos_programacao
				  WHERE {$filtro}
				  GROUP BY
					PE.apelido,
					TP.descricao,
					TA.descricao,
					PA.cancelada
				  ORDER BY PE.apelido";
		} else {
			$sql = "SELECT
					P.id id_programacao, PA.data, PA.quantidade, PA.descricao  at_descricao, P.os, A.descricao  ar_descricao, PP.apelido pp_apelido, PE.apelido pe_apelido, U.descricao un_descricao, I.nome i_nome, SK.quantidade sku_quantidade, SK.codigo,
						TA.descricao ta_descricao, TP.descricao tp_descricao, PA.cancelada
				  FROM programacao_atividades PA
				  LEFT JOIN programacao P ON P.id = PA.id_programacao
				  LEFT JOIN areas A ON A.id = P.id_areas
				  LEFT JOIN pessoas PP ON PP.id = P.id_pessoas_proprietario AND PP.cliente='1'
				  LEFT JOIN pessoas PE ON PE.id = PA.id_pessoas AND PE.cliente='0'
				  LEFT JOIN itens_skus SK ON SK.id = PA.id_itens_skus
				  LEFT JOIN itens I ON I.id = SK.id_itens
				  LEFT JOIN unidades U ON U.id = SK.id_unidades
				  LEFT JOIN tipos_atividades TA ON TA.id = PA.id_tipos_atividades
				  LEFT JOIN tipos_programacao TP ON TP.id = P.id_tipos_programacao
				  WHERE {$filtro}
				  ORDER BY PA.id ASC
				 ";
		}
		return (dbQuery($sql));
	}
}

class RelatorioEntradas extends Relatorios
{
	public $filtroTipoProgramacao = "AND P.id_tipos_programacao IN (1 , 14, 19, 20, 21, 23, 26) ";

	protected $tipo;
	function __construct()
	{
		$this->tipo = 'E';
	}

	function obtemBusca($req)
	{
		global $usrCliente;
		$filtros = array();
		$where = array();
		$cabecalho = array();

		$comboCondicional = array(
			0 => false, //*Indiferente
			1 => 1, //Opcao "Sim"
			2 => 0 //Opcao "Nao"
		);

		if ($req['detalhes']) {
			$detalhes = gCleanField($req["detalhes"]);
			$where[] = "PA.descricao like '%{$detalhes}%'";
			$cabecalho[] = "Detalhes: " . $os;
		}
		if ($req['os']) {
			$os = gCleanField($req["os"]);
			if ($usrCliente == 1) {
				$where[] = " (P.os like '{$os}' AND P.id_pessoas_proprietario='" . $req["id_pessoas_proprietario"] . "')";
			} else {
				$where[] = " (P.os like '%{$os}%') ";
			}
			$cabecalho[] = "OS: " . $os;
		}
		if ($req['codigo_item'] <> '') {
			$where[] .= "SK.codigo='" . $req['codigo_item'] . "'";
		}
		if ($req['codigo_sku'] <> '') {
			$where[] .= "ISK.codigo='" . $req['codigo_sku'] . "'";
		}
		if ($req["id_itens_skus"]) {
			$idItem = $req["id_itens_skus"];
			$where[] .= "PI.id_itens_skus = '{$idItem}'";
			$sql = "SELECT
            		ISK.id,
            		CONCAT(CONCAT_WS(' • ',ISK.codigo, I.descricao,U.descricao), ' com ', CAST(ISK.quantidade as SIGNED)) descricao
            	FROM itens I
            	LEFT JOIN itens_skus ISK on ISK.id_itens = I.id
            	LEFT JOIN unidades U on U.id = ISK.id_unidades
            	WHERE ISK.id='$idItem'
            	GROUP BY ISK.id, I.descricao";
			$cabecalho[] = " Item: " . dbQuery($sql)[0]["descricao"];
		}
		if ($req["codigo_sku"] <> "") {
			$codigo_sku = $req['codigo_sku'];
			$where[] .= "(ISK.codigo = '{$codigo_sku}'"
				. " OR I.descricao LIKE '%{$codigo_sku}%')";
			$sql = "SELECT
            		ISK.id,
            		CONCAT(CONCAT_WS(' • ',ISK.codigo, I.descricao,U.descricao), ' com ', CAST(ISK.quantidade as SIGNED)) descricao
            	FROM itens I
            	LEFT JOIN itens_skus ISK on ISK.id_itens = I.id
            	LEFT JOIN unidades U on U.id = ISK.id_unidades
            	WHERE ISK.codigo='$codigo_sku'
        			OR I.descricao LIKE '%{$codigo_sku}%'
            	GROUP BY ISK.id, I.descricao";
			$cabecalho[] = " Item: " . dbQuery($sql)[0]["descricao"];
		}
		if ($req['id_tipos_atividades']) {
			$tipoAtividade = intval($req['id_tipos_atividades']);
			$where[] = "PA.id_tipos_atividades = '{$tipoAtividade}'";
			$cabecalho[] = "Tipo de Atividade: " . gFieldById("tipos_atividades", $tipoAtividade, "descricao");
		}
		if ($req['id_tipos_programacao']) {
			$tipoProgramacao = intval($req['id_tipos_programacao']);
			$where[] = "P.id_tipos_programacao = '{$tipoProgramacao}'";
			$cabecalho[] = "Tipo de programação: " . gFieldById("tipos_programacao", $tipoProgramacao, "descricao");
		}
		if ($req['id_pessoas_proprietario']) {
			$idProprietario = intval($req['id_pessoas_proprietario']);
			if ($this->tipo == 'E' && $req["id_tipos_programacao"] == 26) {
				$where[] = "P.id_pessoas_destinatario = '{$idProprietario}'";
			} else {
				$where[] = "P.id_pessoas_proprietario = '{$idProprietario}'";
			}
			$cabecalho[] = "Proprietário: " . obtemProprietario($idProprietario, "apelido")["apelido"];
		}
		if ($req['data_inicio']) {
			$dataInicial = date('Y-m-d 00:00:00', strtotime(gDBDate($req['data_inicio'])));
			$where[] = "(P.data_execucao_final>='{$dataInicial}')";
			$cabecalho[] = "Data de: " . $req["data_inicio"];
		}
		if ($req['data_fim']) {
			$dataFinal = date('Y-m-d 23:59:59', strtotime(gDBDate($req['data_fim'])));
			$where[] = "(P.data_execucao_final<='{$dataFinal}')";
			$cabecalho[] = "Data até: " . $req["data_fim"];
		}
		if ($req['avariada']) {
			$cabecalho[] = "Avariada: " . gCheck($comboCondicional[$req['avariada']], true);
		}

		$cabecalho[] = "Mostrar subtotais: " . gCheck($req['exibirTotal']);
		$cabecalho[] = "Mostrar observações da programação: " . gCheck($req['mostrarObservacoesProgramacao']);
		
		$filtros["where"] = implode(" AND ", $where);
		$filtros["cabecalho"] = implode(" • ", $cabecalho);

		return ($filtros);
	} 

	function obtemRegistros($filtro, $limit)
	{
		global $gParam;
		$sql = "SELECT P.id_pessoas_proprietario,
					P.id, PI.id idi,
					P.os, C.apelido cliente,
					P.data_execucao_inicio,
					P.data_execucao_final,
					TP.descricao tipo_programacao,
					P.id_tipos_programacao,
					NS.numero nota_saida,
					P.numero_cliente,
					PESSOA_CRIOU.nome AS pessoa_criou
				FROM programacao P
				LEFT JOIN notas NE ON NE.id_programacao = P.id
				LEFT JOIN pessoas C ON P.id_pessoas_proprietario=C.id
				LEFT JOIN pessoas AS PESSOA_CRIOU ON PESSOA_CRIOU.id = P.id_pessoas_criou
				LEFT JOIN programacao_itens PI ON P.id=PI.id_programacao
				LEFT JOIN itens_skus ISK ON ISK.id = PI.id_itens_skus
				LEFT JOIN itens I ON I.id = ISK.id_itens
				LEFT JOIN tipos_programacao TP ON TP.id = P.id_tipos_programacao
				LEFT JOIN notas NS ON (NS.id_programacao = P.id AND NS.tipo='S' AND NS.cancelada=0)
				LEFT JOIN nfe NFE ON (NFE.id= NS.id_nfe AND NFE.situacao='Aprovada' AND NFE.cancelada=0)
				WHERE P.id_filial=" . $_SESSION['filialAtualId'] . " AND P.cancelada=0 AND P.executada=1 " . $this->filtroTipoProgramacao . " AND {$filtro}
				GROUP BY P.id, PI.id
				ORDER BY C.apelido, P.data_execucao_final, P.os, PI.id";

		if (!$limit) {
			if ($gParam["PAGINACAO"]["ativo"] == 1) {
				$this->porPagina = $gParam["PAGINACAO"]["valor"];
			}
			if ($this->porPagina > 0) {
				$this->pagination = new Pagination();
				$totalRegistros = $this->pagination->controlarQuantidadePaginas($sql, $qtdMinimaPaginas = 10);
            	$pagination = $this->pagination->addPagination($totalRegistros, $this->porPagina);
				$sql .= " LIMIT {$pagination->iniciar}, {$pagination->numero_registro_por_pagina}";
			} else {
				if ($gParam['LIMITAR_VISUALIZACAO']['ativo']) {
					$sql .= " LIMIT " . $gParam['LIMITAR_VISUALIZACAO']['valor'];
				}
			}
		} else {
			$sql .= " LIMIT " . $limit;
		}

		$rs = dbQuery($sql);

		return ($rs);
	}


	public function obtemFotos($idOs, $os, $colspan)
	{
		global $o, $gPDF;
		$sql = "
			SELECT
				CONCAT('files/programacao_imagens/', programacao_imagens.local_imagem) AS local_imagem,
				programacao_imagens_descricao.nome
			FROM
				programacao_imagens
			LEFT JOIN programacao_imagens_descricao ON programacao_imagens_descricao.id = programacao_imagens.id_programacao_imagens_descricao
			WHERE programacao_imagens.id_programacao = {$idOs}
		";
		$fotosOperacao = dbQuery($sql);

		if (!$fotosOperacao) {
			return array();
		}

		$exibirFotos = array();
		$mtz = array();
		$mtz[] = "~{$colspan}<-"  . " Fotos da operação - {$os}";
		$exibirFotos['cabecalhoFotos'] = $o->tableRow($mtz, "detail");

		$divFoto = '';
		$mtz = array();
		if ($gPDF) {
			$tam = '~15~15~15'; //15 largura max/ 15 altura max/ 15 espacamento max
			foreach ($fotosOperacao as $key => $fotos) {
				$mtz[] = $gPath . $fotos['local_imagem'] . '~' . $tam;
			}
			$exibirFotos['corpoFotos'] = $o->tableRow($mtz, "image");
		} else {
			foreach ($fotosOperacao as $key => $fotos) {
				$divFoto .=
					"<div class='text-center' style='display: inline-block; width: 130px; height: 180px; margin: 13px;'>
						<div>"
					. "<img onClick='mostraFotoOperacao(`" . $fotos['local_imagem'] . "`)' src='" . $fotos['local_imagem'] . "' class='popup img-responsive' style='width: 200px; height: 100px; border: 1px solid grey; padding: 2px;'/>"
					. "</div><br>
						<div style='display: table;'>" . $fotos['nome'] . "</div>
					</div>";
			}
			$mtz[] = "~{$colspan}<-" . " <div style='display: inline-block;'>{$divFoto}</div>";
			$exibirFotos['corpoFotos'] = $o->tableRow($mtz, "footer");
		}

		return $exibirFotos;
	}
}

class RelatorioSaidas extends RelatorioEntradas
{

	function __construct()
	{
		$this->filtroTipoProgramacao = "AND (P.id_tipos_programacao in (2, 22,26))";
		$this->tipo = 'S';
	}


	public function obtemRegistros($filtro, $limit = 50)
	{
		global $gParam;

		$sql = "SELECT
					P.id,
					C.id AS id_pessoas_proprietario
				FROM programacao P
				LEFT JOIN notas NE ON NE.id_programacao = P.id
				LEFT JOIN pessoas C ON P.id_pessoas_proprietario=C.id
				LEFT JOIN programacao_itens PI ON P.id=PI.id_programacao
				LEFT JOIN itens_skus ISK ON ISK.id = PI.id_itens_skus
				LEFT JOIN itens I ON I.id = ISK.id_itens
				LEFT JOIN tipos_programacao TP ON TP.id = P.id_tipos_programacao
				LEFT JOIN notas NS ON (NS.id_programacao = P.id AND NS.tipo='S' AND NS.cancelada=0)
				LEFT JOIN nfe NFE ON (NFE.id= NS.id_nfe AND NFE.situacao='Aprovada' AND NFE.cancelada=0)
				WHERE P.id_filial=" . $_SESSION['filialAtualId'] . " AND P.cancelada=0 AND P.executada=1 " . $this->filtroTipoProgramacao . " AND {$filtro}
				GROUP BY P.id
				ORDER BY C.apelido, P.data_execucao_final";

		if (!$limit) {
			$sql .= " LIMIT " . $limit;
		} else {
			if ($gParam["PAGINACAO"]["ativo"]) {
				$this->porPagina = $gParam["PAGINACAO"]["valor"];
			}

			if ($this->porPagina > 0) {
				$this->pagination = new Pagination();
				$totalRegistros = $this->pagination->controlarQuantidadePaginas($sql, $qtdMinimaPaginas = 10);
				$pagination = $this->pagination->addPagination($totalRegistros, $this->porPagina);
				$sql .= " LIMIT {$pagination->iniciar}, {$pagination->numero_registro_por_pagina}";
			} else {
				if ($gParam['LIMITAR_VISUALIZACAO']['ativo']) {
					$sql .= " LIMIT " . $gParam['LIMITAR_VISUALIZACAO']['valor'];
				}
			}
		}
		return dbFastQuery($sql);
	}
}

class RelatorioSeparacoes extends RelatorioEntradas
{

	function __construct()
	{
		$this->filtroTipoProgramacao = "AND (P.id_tipos_programacao=2 OR P.id_tipos_programacao=3 OR P.id_tipos_programacao=32) AND P.separada=1";
	}


	public function consultarOsVinculadaApanha($whereOs)
	{
		$sql = "
			SELECT GROUP_CONCAT(DISTINCT id) id,
				id_tipos_programacao
			FROM programacao
			WHERE {$whereOs}
			GROUP BY id_tipos_programacao
			ORDER BY id_tipos_programacao";
		$rs = dbFastQuery($sql);
		if (!$rs[0]) {
			$this->erros[] = 'Nenhum registro encontrado a partir das OSs informadas';
			return;
		}

		$idOsApanha = array_search(7, array_column($rs, 'id_tipos_programacao'));
		if ($idOsApanha !== false) { //tem OS de apanha
			$idOsApanha = $rs[$idOsApanha]['id'];
			$sql = "
				SELECT GROUP_CONCAT(id) AS id
				FROM programacao
				WHERE id_programacao_apanha IN(" . $idOsApanha . ")";
			$rsApanha = dbFastQuery($sql)[0]['id'];
			if ($rsApanha) {
				$rs[0]['id'] = $rs[0]['id'] . ',' .  $rsApanha;
			}
		}

		return $rs[0]['id'];
	}
}

class RelatoriosProducoes extends Relatorios
{
	private $select;
	private $groupBy;
	public $orderBy;

	public function __construct()
	{
		$this->orderBy = "";
	}

	public function obtemBusca($request)
	{
		global $sp;
		$where = "";
		$groupBy = "";
		$cabecalho = array();
		if ($request["id_pessoas_proprietario"]) {
			$idProprietario = $request["id_pessoas_proprietario"];
			$where .= " AND (P.id_pessoas_proprietario='{$idProprietario}')";
			$cabecalho[] = " Proprietário: " . gFieldById("pessoas", $idProprietario, "nome");
		}
		if ($request["data_execucao_inicio"]) {
			$where .= " AND (P.data_execucao_final>='" . gDBDate($request["data_execucao_inicio"]) . "')";
			$cabecalho[] = " Data de execução de: " . $request["data_execucao_inicio"];
		}
		if ($request["data_execucao_final"]) {
			$where .= " AND (P.data_execucao_final<='" . gDBDate($request["data_execucao_final"]) . "')";
			$cabecalho[] = " Data de execução até: " . $request["data_execucao_final"];
		}


		if ($request["id_itens_skus"]) {
			$idItem = gCleanField($request["id_itens_skus"]);
			$where .= " AND (PI.id_itens_skus ='{$idItem}')";
			$sql = "SELECT
					ISK.id,
					CONCAT(CONCAT_WS(' • ',ISK.codigo, I.descricao,U.descricao), ' com ', CAST(ISK.quantidade as SIGNED)) descricao
				FROM itens I
				LEFT JOIN itens_skus ISK on ISK.id_itens = I.id
				LEFT JOIN unidades U on U.id = ISK.id_unidades
				WHERE ISK.id='$idItem'
				GROUP BY ISK.id, I.descricao";
			$cabecalho[] = " Item: " . dbQuery($sql)[0]["descricao"];
		}
		if ($request["os"]) {
			/* Montar inOS */
			$os = gCleanField($request["os"]);
			$cabecalho[] = " Número da OS: {$os}";
			$sql = "SELECT id FROM programacao WHERE os like '%{$os}%' LIMIT 1";
			$oss = dbQuery($sql);
			$inOs = array();
			foreach ($oss as $os) {
				$inOs[] = $os["id"];
			}

			if (count($inOs) > 0) {
				$inOs = implode(", ", $inOs);
				$where .= " AND (P.id in ({$inOs})) ";
			}
		}
		$busca = array();
		$busca["groupBy"] = $groupBy;
		$busca["where"] = $where;
		$busca["cabecalho"] = $cabecalho;
		return ($busca);
	}

	function obtemRegistros($filtro)
	{
		$sql = "SELECT P.id_pessoas_proprietario,
						P.id, PI.id idi,
						PI.id_itens_skus,
						PI.quantidade quantidade_pedida,
						P.os, C.apelido cliente,
						P.data_execucao_inicio,
						P.data_execucao_final,
						TP.descricao tipo_programacao,
						P.id_tipos_programacao,
						NS.numero nota_saida,
						P.data_reservada
				FROM programacao P
				LEFT JOIN notas NE ON NE.id_programacao = P.id
				LEFT JOIN pessoas C ON P.id_pessoas_proprietario=C.id
				LEFT JOIN programacao_itens PI ON P.id=PI.id_programacao
				LEFT JOIN itens_skus ISK ON ISK.id = PI.id_itens_skus
				LEFT JOIN tipos_programacao TP ON TP.id = P.id_tipos_programacao
				LEFT JOIN notas NS ON (NS.id_programacao = P.id AND NS.tipo='S' AND NS.cancelada=0)
				LEFT JOIN nfe NFE ON (NFE.id= NS.id_nfe AND NFE.situacao='Aprovada' AND NFE.cancelada=0)
				WHERE P.id_filial=" . $_SESSION['filialAtualId'] . " AND P.cancelada=0 AND P.executada=1 AND P.id_tipos_programacao=32 AND P.divergencia=1 {$filtro}
				GROUP BY P.id, P.os, PI.id
				ORDER BY P.data_execucao_inicio, P.os, PI.id";
		$rs = dbQuery($sql);
		return ($rs);
	}

	function obtemUmasItens($where, $groupBy = "", $tipo = 1)
	{
		global $usrCliente;

		if ($usrCliente) {
			$whereDefault = "UI.cancelada=0 AND ";
		} else {
			$whereDefault = "UI.cancelada=0 AND U.ativo=1 AND ";
		}
		$where = $whereDefault . $where;
		$sql = "
			SELECT
			SK.codigo, UI.lote, UI.data_fabricacao, UI.data_validade,
			CONCAT(I.nome, ' - ', CAST(SK.quantidade as SIGNED), ' x ', D.descricao) as nomeItem,
			I.nome as item,
			CONCAT(D.sigla, ' com ', CAST(SK.quantidade as SIGNED)) as sku,
			P.apelido, P.id as idProprietario, P.nome,
			UI.reservada, UI.separada, UI.bloqueada, UI.avariada,
			COUNT(U.id) umas,
			sum(UI.quantidade) quantidade,
			sum(UI.quantidade)*SK.quantidade quantidade_un,
			CEIL(SUM(UI.quantidade)/(SK.palete_lastro * SK.palete_altura)) paletes,
			(SK.peso_liquido * sum(UI.quantidade)) peso_liquido,
			(SK.peso_bruto * sum(UI.quantidade)) peso_bruto,
			(sum(UI.quantidade*SK.comprimento/100*SK.largura/100)) m2,
			(sum(UI.quantidade*SK.altura/100*SK.largura/100*SK.comprimento/100)) m3,
			(sum(UI.quantidade * UI.valor)) valor,
			UI.id_itens_skus
			{$groupBy}
		FROM umas U
		LEFT JOIN umas_itens UI ON U.id=UI.id_umas
		LEFT JOIN itens_skus SK ON UI.id_itens_skus=SK.id
		LEFT JOIN itens I ON I.id=SK.id_itens
		LEFT JOIN pessoas P ON (P.id=UI.id_pessoas_proprietario AND P.cliente=1)
		LEFT JOIN unidades D ON SK.id_unidades=D.id
		LEFT JOIN notas_itens NI ON UI.id_notas_itens=NI.id
		LEFT JOIN notas NF ON NF.id=NI.id_notas
		LEFT JOIN programacao PR ON UI.id_programacao = PR.id
		LEFT JOIN tipos_programacao TP ON TP.id = PR.id_tipos_programacao
		WHERE {$where}
		GROUP BY
			UI.id_itens_skus, I.shelf_life, I.id, P.id, SK.quantidade, D.descricao,
			UI.reservada, UI.separada, UI.avariada, UI.bloqueada
			{$groupBy}
		HAVING SUM(UI.quantidade)>0";
		if ($this->orderBy <> "") {
			$sql .= " ORDER BY {$this->orderBy}";
		} else {
			$sql .= " ORDER BY P.apelido ASC, UI.separada DESC, UI.reservada DESC, UI.avariada DESC, UI.bloqueada DESC, I.nome ASC, SUM(UI.quantidade) ASC";
		}
		return (dbQuery($sql));
	}
}
