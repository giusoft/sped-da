<?php
include_once "Pessoas.php";
class Itens extends Pessoas
{
	public function __construct()
	{
		$this->tabela = "itens";
		$this->filtro = "";
		$this->ordenacao = "p.nome, i.nome";
	}


	public function validarSKU($atualizacao = 0)
	{
		global $gId, $gIdd, $gParam;
		$erros = array();
		if ($gIdd > 0 && !isset($_REQUEST["ativo"])) {
			return (array());
		}
		// Se for mesmo proprietário e o código de barras for igual não permitir.
		// Se for proprietário diferentes mas todas as caracteristicas forem iguais, não permitid.

		$idProprietario = gFieldById("itens", $gId, "id_pessoas_proprietario");
		$where = array();
		$where[] = "(SK.ativo=1)";
		$where[] = "(I.id_pessoas_proprietario=".$idProprietario.")";
		$where[] = "(SK.id <> ".$gIdd.")";
		$orWhere = array();

		if (!empty($_REQUEST["codigo"])) {
			$orWhere[] = "(SK.codigo='" . gCleanField($_REQUEST["codigo"]) . "' AND SK.id_unidades=" . intval($_REQUEST["id_unidades"]) . ")";
		}

		if (!empty($_REQUEST["codigo_barras"])) {
			$orWhere[] = "(SK.codigo_barras='" . gCleanField($_REQUEST["codigo_barras"]) . "')";
		}

		if ($orWhere) {
			$where[] = "(" . implode(" OR ", $orWhere) . ")";
		}

		$where = implode(" AND ", $where);
		$sql = "SELECT SK.id FROM itens_skus SK
				LEFT JOIN itens I ON I.id = SK.id_itens
				WHERE {$where}
				LIMIT 1";
		$rs = dbFastQuery($sql);

		if ($rs[0]['id']) {
			// Será um item repetido não permitir.
			if (!empty($_REQUEST["codigo"])) {
				$erros[] = " Não é permitido dois itens com mesmo código e unidade para o mesmo proprietário. " . linkParaCadastroSku($rs[0]['id'], 'Abrir cadastro do SKU');
			}

			if (!empty($_REQUEST["codigo_barras"])) {
				$erros[] = " Não é permitido dois itens com o mesmo código de barras para o mesmo proprietário. "  . linkParaCadastroSku($rs[0]['id'], 'Abrir cadastro do SKU');
			}
		} else {
			// Verificar se tem todas as caracteristicas iguais, ignorando o proprietário.

			$sql  = "SELECT id, id_pessoas_proprietario FROM itens WHERE id = " . $gId;
			$item = (dbQuery($sql)[0]);

			$where=array();
			if ($_REQUEST['codigo2']) {
				$where[] = "(I.id <> " . $item["id"] . " AND codigo2)";
			}
			if ($_REQUEST['gIdd']) {
				$where[] = "(SK.id <> " . $_REQUEST['gIdd'] . ")";
			}
			$where[] = "(SK.ativo=1)";
			$where[] = "(SK.codigo='".gCleanField($_REQUEST["codigo"])."')";
			$where[] = "(SK.codigo_barras='".gCleanField($_REQUEST["codigo"])."')";
			$where[] = "(SK.nome='".gCleanField($_REQUEST["nome"])."')";
			$where[] = "(SK.id_unidades=".intval($_REQUEST["id_unidades"]).")";
			$where[] = "(SK.peso_liquido='".gDBFloat($_REQUEST["peso_liquido"])."')";
			$where[] = "(SK.peso_bruto='".gDBFloat($_REQUEST["peso_bruto"])."')";
			$where[] = "(SK.quantidade='".gDBFloat($_REQUEST["quantidade"])."')";
			$where[] = "(SK.largura='".gDBFloat($_REQUEST["largura"])."')";
			$where[] = "(SK.comprimento='".gDBFloat($_REQUEST["comprimento"])."')";
			$where[] = "(SK.altura='".gDBFloat($_REQUEST["altura"])."')";
			$where[] = "(SK.palete_altura='".gDBFloat($_REQUEST["palete_altura"])."')";
			$where[] = "(SK.palete_lastro='".gDBFloat($_REQUEST["palete_lastro"])."')";
			$where[] = "(SK.empilhamento_maximo='".intval($_REQUEST["empilhamento_maximo"])."')";
			$where[] = "(SK.id_unidades=".intval($_REQUEST["id_unidades"]).")";
			$where[] = "(I.id_pessoas_proprietario=".$item["id_pessoas_proprietario"].")";

			$where = implode(" AND ", $where);

			$sql = "SELECT SK.id
					FROM itens_skus SK
					LEFT JOIN itens I ON I.id = SK.id_itens
					WHERE {$where}";
			$rs = dbFastQuery($sql);

			if ($rs) {
				$erros[]=" O mesmo item com as mesmas caracteristicas já existe no sistema.";
			}
		}

		return $erros;
	}


	public function obtemQueryConsulta($joinSku = "", $camposSku = "")
	{
		global $gParam;

		if (gDBCheck($_REQUEST['mostrarDetalhesSku'])) {
			$leftSkus = "
				LEFT JOIN itens_skus ON itens_skus.id_itens = i.id
				LEFT JOIN unidades ON unidades.id = itens_skus.id_unidades
			";

			$outrosAtributos = "
				, unidades.sigla AS unidade,
				itens_skus.palete_lastro,
				itens_skus.palete_altura,
				(itens_skus.palete_lastro * itens_skus.palete_altura) AS regra_paletizacao,
				itens_skus.peso_liquido,
				itens_skus.peso_bruto,
				itens_skus.codigo_barras AS codigo_barras_1,
				itens_skus.largura,
				itens_skus.altura,
				itens_skus.comprimento";
		}

		$sql = "
			SELECT
				i.*, p.nome proprietario, pf.nome fornecedor, pc.nome criou,
				pa.nome alterou, g.descricao grupo, t.descricao tipo {$outrosAtributos}
			FROM itens i
			JOIN pessoas p ON i.id_pessoas_proprietario = p.id
			JOIN pessoas_filial a ON a.id_pessoas = p.id
			LEFT JOIN pessoas pc ON i.id_pessoas_criou = pc.id
			LEFT JOIN pessoas pf ON i.id_pessoas_fornecedor = pf.id
			LEFT JOIN pessoas pa ON i.id_pessoas_alterou = pa.id
			LEFT JOIN grupos g ON i.id_grupos = g.id
			LEFT JOIN tipos t ON i.id_tipos = t.id
			{$leftSkus}";

		return($sql);
	}



	function preparaCampos($todosOsCampos, $gId = 0) {

		global $usrId, $gParam;
		$hoje = date('Y-m-d H:i:s');
		$campos = array();
		$campos['codigo']=str_replace("'", '', gCleanField($todosOsCampos['codigo']));
		$campos['codigo_barras']=str_replace("'", '', gCleanField($todosOsCampos['codigo_barras']));
		$campos['codigo_barras_alternativo']=str_replace("'", '', gCleanField($todosOsCampos['codigo_barras_alternativo']));
		$campos['codigo_anterior']=str_replace("'", '', gCleanField($todosOsCampos['codigo_anterior']));
		$campos['nome']=gCleanField($todosOsCampos['nome']);
		$campos['descricao']=gCleanField($todosOsCampos['descricao']);
		$campos['id_pessoas_proprietario']=intval($todosOsCampos['id_pessoas_proprietario']);
		$campos['id_pessoas_fornecedor']=intval($todosOsCampos['id_pessoas_fornecedor']);
		$campos['id_grupos']=intval($todosOsCampos['id_grupos']);
		$campos['id_tipos']=intval($todosOsCampos['id_tipos']);
		$campos['ativo']=gDBCheck($todosOsCampos['ativo']);
		$campos['ncm']=gJustNumbers($todosOsCampos['ncm']);
		$campos['id_itens_skus_operacao']=intval($todosOsCampos['id_itens_skus_operacao']);
		$campos['id_itens_skus_pedido']=intval($todosOsCampos['id_itens_skus_pedido']);
		$campos['id_grupos_combustivel'] = intval($todosOsCampos['id_grupos_combustivel']);

		if (strlen($todosOsCampos['observacoes'])<20) {
			$campos['observacoes'] = ($todosOsCampos['observacoes']);
		} else {
			$campos['observacoes'] = base64_encode($todosOsCampos['observacoes']);
		}

		# Campos necessários para que o item esteja apto para uso
		$campos['apto'] = 0;
		if (	$campos['nome']<>"" &&
				$campos['codigo_barras']<>"" &&
				$campos['codigo']<>"" &&
				$campos['ncm']<>"" &&
				$campos['id_pessoas_proprietario']>0
			)
		{
			$campos['apto'] = 1;
		} else {
			$this->erros[] = 'Preencha todos os campos obrigatórios: Nome, código, código de barras, NCM e proprietário';
		}

		if ($gId==0) {
			$campos['data_cadastro']      = $hoje;
			$campos['id_pessoas_criou']   = $usrId;
		}else {
			$campos['data_alteracao']     = $hoje;
			$campos['id_pessoas_alterou'] = $usrId;
		}
		return $campos;
	}


	public function insere($campos)
	{
		global $o, $gParam;

		if (!is_numeric($campos['ncm']) || strlen($campos['ncm']) != 8) {
			$this->erros[] = "O NCM informado não é válido. Verifique se o código foi digitado corretamente";
			return false;
		}

		$campos = $this->preparaCampos($campos);
		// Verifica se já existe algum produto com este código, se ele não estiver em branco
		if ($campos['codigo'] <> "") {
			$sql = "SELECT 1 FROM itens WHERE id_pessoas_proprietario = " . $campos['id_pessoas_proprietario'] . " AND codigo =  '".$campos['codigo']."' LIMIT 1";
			$rst = dbFastQuery($sql);

			if ($rst) {
				$this->erros[] = "Produto com o código [".$campos['codigo']."] já está cadastrado para esta empresa";
				return false;
			}
		}


		$flt = "";
		// Permite o cadastramento do mesmo item em clientes diferentes (definido por parametro de configuração)
		if ($gParam['PERMITIR_ITENS_IGUAIS']['ativo']) {
			$flt = "id_pessoas_proprietario=" . $campos['id_pessoas_proprietario'] . " AND ";
		}

		// Verifica se já existe algum produto com este código de barras
		$sql   = "SELECT id FROM itens WHERE $flt codigo_barras = '" . $campos['codigo_barras'] . "'";
		$itens = dbFastQuery($sql)[0]['id'];

		if ($itens) {
			$this->erros[] = "Código de barras já está em uso pelo item: <a href=" . $o->page . "&gPage=10&gId=" . $itens . ">[" . $campos['nome'] . "]</a>";
			return false;
		}

		$msgErro = $this->verificarDuplicataSku($campos['id_pessoas_proprietario'], '', $campos['codigo_barras']);
		if ($msgErro) {
			$this->erros[] = "<b>Erros encontrados: </b><br>" . implode("<br>", $msgErro);
			return false;
		}

		$gId = dbInsert('itens', $campos, true);

		$campos = array();
		$campos['id_itens']      = $gId;
		$campos['data_cadastro'] = date('Y-m-d H:i:s');
		$campos['codigo']        = gCleanField($_REQUEST['codigo']);
		$campos['codigo_barras'] = gCleanField($_REQUEST['codigo_barras']);
		$campos['nome']          = 'Item individual';
		$campos['id_unidades']   = 1;
		$campos['quantidade']    = 1;
		dbInsert('itens_skus', $campos);

		return $gId;
	}


	public function modifica($campos, $gId)
	{
		global $o, $gParam;
		$sucesso = true;

		if (!is_numeric($campos['ncm']) || strlen($campos['ncm']) != 8) {
			$this->erros[] = "O NCM informado não é válido. Verifique se o código foi digitado corretamente";
			return false;
		}

		$campos = $this->preparaCampos($campos, $gId);

		$msgErro = $this->verificarDuplicataSku($campos['id_pessoas_proprietario'], $gId);
		if ($msgErro) {
			$this->erros[] = "<b>Erros encontrados: </b><br>" . implode("<br>",$msgErro);
			return false;
		}

		// Verifica se já existe algum produto com este código, se ele não estiver em branco
		if ($campos['codigo'] <> "") {
			$codigoOk = true;
			$sql = "SELECT * FROM itens WHERE id<>$gId AND id_pessoas_proprietario=".$campos['id_pessoas_proprietario']." AND codigo='".$campos['codigo']."' AND ativo=1";
			$rst = dbQuery($sql);
			if ($rst) {
				$sucesso = false;
				$this->erros[] = "Produto com o código [" . $campos['codigo'] . "] já está cadastrado para esta empresa (Item <a href=".$o->page."&gPage=10&gId=".$rst[0]['id'].">[".$campos['nome']."]</a>)";
				return false;
			}
		}
		// Permite o cadastramento do mesmo item em clientes diferentes (definido por parametro de configuração)
		if ($gParam['PERMITIR_ITENS_IGUAIS']['ativo'] == 1) {
			$flt = "id_pessoas_proprietario = " . $campos['id_pessoas_proprietario'] . " AND ";
		}
		// Verifica se já existe algum produto com este código de barras
		$sql = "SELECT id FROM itens WHERE id <> $gId AND $flt codigo_barras = '" . $campos['codigo_barras'] . "' AND ativo = 1 LIMIT 1";
		$rst = dbFastQuery($sql);

		if ($rst) {
			$this->erros[] = "Código de barras já está em uso pelo item: <a href=".$o->page."&gPage=10&gId=".$rst[0]['id'].">[".$campos['nome']."]</a>";
			return false;
		}

		dbUpdate('itens', $campos, $gId);


		return $sucesso;
	}


	public function prepararCamposSkus($sku)
	{
		global $gId, $gIdd, $usrId;

		$mtz = array();
		$mtz['id_itens'] = $sku['id_itens'] ?: $gId;
		$mtz['ativo']   = $sku['itens_skus_ativo'] ?: gDBCheck($sku['ativo']);
		$mtz['codigo']  = $sku['itens_skus_codigo'] ?: gCleanField($sku['codigo']);
		$mtz['codigo2'] = gCleanField($sku['codigo2']);
		$mtz['codigo_barras'] = $sku['itens_skus_codigo_barras'] ?: gCleanField($sku['codigo_barras']);
		$mtz['codigo_barras_alternativo'] = $sku['itens_skus_codigo_barras_alternativo'] ?: gCleanField($sku['codigo_barras_alternativo']);
		$mtz['codigo_anterior'] = $sku['itens_skus_codigo_anterior'] ?: gCleanField($sku['codigo_anterior']);
		$mtz['nome'] = $sku['itens_skus_nome'] ?: gCleanField($sku['nome']);
		$mtz['id_unidades'] = (int) $sku['id_unidades'];
		$mtz['quantidade'] = gDBFloat($sku['quantidade']);
		$mtz['peso_liquido'] = gDBFloat($sku['peso_liquido']);
		$mtz['peso_bruto'] = gDBFloat($sku['peso_bruto']);
		$mtz['largura'] = gDBFloat($sku['largura']);
		$mtz['altura'] = gDBFloat($sku['altura']);
		$mtz['comprimento'] = gDBFloat($sku['comprimento']);
		$mtz['palete_lastro'] = gDBFloat($sku['palete_lastro']);
		$mtz['palete_altura'] = gDBFloat($sku['palete_altura']);
		$mtz['empilhamento_maximo'] = (int) $sku['empilhamento_maximo'];
		$mtz['valor'] = gDBFloat($sku['valor']);
		if ($gIdd) {
			$mtz['data_alteracao']     = date('Y-m-d H:i:s');
			$mtz['id_pessoas_alterou'] = $usrId;
		} else {
			$mtz['data_cadastro']    = date('Y-m-d H:i:s');
			$mtz['id_pessoas_criou'] = $usrId;
		}

		return $mtz;
	}


	public function copiarItem($where, $novoItem) {
		global $usrId;

		$camposSku = "
			itens_skus.ativo AS itens_skus_ativo,
			itens_skus.codigo AS itens_skus_codigo,
			itens_skus.codigo_barras AS itens_skus_codigo_barras,
			itens_skus.codigo_anterior AS itens_skus_codigo_anterior,
			itens_skus.codigo_barras_alternativo AS itens_skus_codigo_barras_alternativo,
			itens_skus.nome AS itens_skus_nome,
			id_itens,
			id_unidades,
			id_itens_skus_intermediario,
			quantidade,
			peso_liquido,
			peso_bruto,
			largura,
			altura,
			comprimento,
			palete_lastro,
			palete_altura,
			empilhamento_maximo,
			valor,
			invisivel,
			sigla,
			codigo2";
		$sql = $this->obtemQueryConsulta($joinSku = 1, $camposSku) . " WHERE " . implode(" AND ", $where) . " ORDER BY i.ativo DESC";
		$rs  = dbQuery($sql);

		foreach ($rs as $chave => $row) {

			$row['id_pessoas_proprietario'] = $novoItem['id_pessoas_proprietario'] ?: $row['id_pessoas_proprietario'];
			$row['id_pessoas_fornecedor']   = $novoItem['id_pessoas_fornecedor']   ?: $row['id_pessoas_fornecedor'];

			$cadastrarItem = true;
			$cadastrarSku  = true;
			// consulta se item existe
			$sql = "
				SELECT
					ativo, codigo, id
				FROM
					itens
				WHERE
					(
						codigo = '" . $row['codigo'] . "'
							OR codigo_barras = '" . $row['codigo_barras'] . "'
					)
					AND id_pessoas_proprietario = '" . $row['id_pessoas_proprietario'] . "'
				ORDER BY ativo DESC LIMIT 1";
			$item = dbQuery($sql)[0];
			// se item existe
			if ($item) {
				$cadastrarItem = false;
				$idItens  = $item['id'];
				$this->avisos[] = 'Item já criado [' . $item['codigo'] . ']';
			}

			// 	verifica se existe sku
			$sql = "
				SELECT
					*
				FROM
					itens_skus
				WHERE
					(
						codigo = '" . $row['codigo'] . "'
							OR codigo_barras = '" . $row['codigo_barras'] . "'
					)
					AND id_unidades = '" . $row['id_unidades'] . "'
					AND id_itens = '" . $item['id'] . "'
				ORDER BY ativo DESC LIMIT 1";
			$sku  = dbQuery($sql)[0];
			if ($sku) {
				$cadastrarSku = false;
				// 	se existe sku, com unidade e codigo, nao cadastra
				$this->avisos[] = 'SKU já criado [' . $sku['codigo'] . ']';
				continue;
			}

			$campos  = $this->preparaCampos($row);
			if ($cadastrarItem) {
				$idItens = dbInsert('itens', $campos, true);
				if (!$idItens) {
					$this->erros[] = "Falha o cadastrar item [" . $campos['codigo'] . "]";
				}
			}

			if ($cadastrarSku) {
				$row['id_itens'] = $idItens;
				$campos = $this->prepararCamposSkus($row);
				$idItensSkus = dbInsert('itens_skus', $campos, true);
				if (!$idItensSkus) {
					$this->erros[] = "Falha o cadastrar SKU [" . $campos['codigo'] . "]";
				}
			}
		}
		$this->avisos = array_unique($this->avisos);
	}


	// OCORRÊNCIAS -----------------------------------------------

	public function obtemRegistrosOcorrencias($id="")
	{
		global $gId;

		$sql="SELECT p.* , f.nome funcionario, t.descricao tipo_ocorrencia
		FROM itens_ocorrencias p
		LEFT JOIN pessoas f ON p.id_pessoas_funcionario=f.id
		LEFT JOIN tipos_ocorrencias t ON p.id_tipos_ocorrencias=t.id
		WHERE p.id_itens=" . $gId;
		if ($id>0)
		$sql.=" AND p.id=".$id;
		$sql.=" ORDER BY p.id DESC";
		return(dbQuery($sql));
	}

	/**
	* Gera os campos necessários para um formulário de entrada de dados
	*/
	public function geraCamposDoFormularioOcorrencias(&$frm, $registroAtual, $proximaPagina="", $gIdEnd)
	{
		global $proximaPagina, $gId, $gPage, $o, $sp;
		if ($proximaPagina == "") {
			$proximaPagina = OCORRENCIAS_SALVAR;
		}

		$frm->row(
			$frm->add("{name: descricao; type: textarea; fieldLabel: Descrição; value: ".$registroAtual['descricao']."}")
		);
		$frm->row(
			$frm->add("{name: data_ocorrencia; fieldLabel: Data da ocorrência; type: date; value: ".gDate($registroAtual['data_ocorrencia']=='0000-00-00 00:00:00' || $registroAtual['data_ocorrencia']=='' ? date('Y-m-d') : $registroAtual['data_ocorrencia'])."}"),
			$frm->add("{name: id_pessoas_funcionario; fieldLabel: Colaborador; type: combo; items: ".$sp['combo_funcionarios']."; value: ".$registroAtual['id_pessoas_funcionario']."}"),
			$frm->add("{name: id_tipos_ocorrencias; fieldLabel: Tipo de ocorrência; allowBlank: false; type: combo; items: ".$sp['combo_tipos_ocorrencias']."; value: ".$registroAtual['tipos_ocorrencias']."}"),
			$frm->add("{name: publica; type: checkbox; fieldLabel: Informação pública; value: ".$registroAtual['publica']."}")
		);

		$frm->add("{name: gId; type: hidden; value: $gId}");
		$frm->add("{name: gIdEnd; type: hidden; value: $gIdEnd}");
		$frm->add("{name: gPage; type: hidden; value: ".$proximaPagina."}");
		return($frm->render($o));
	}

	/**
	* Formata campos de endereços enviados pelas funções de persistência no banco de dados
	*/
	function preparaCamposOcorrencia($todosOsCampos)
	{
		$campos = array();
		$campos['id_itens']=intval($todosOsCampos['id_itens']);
		$campos['id_pessoas_funcionario']=intval($todosOsCampos['id_pessoas_funcionario']);
		$campos['id_tipos_ocorrencias']=intval($todosOsCampos['id_tipos_ocorrencias']);
		$campos['descricao']=gCleanField($todosOsCampos['descricao']);
		$campos['data_ocorrencia']=gDBDate($todosOsCampos['data_ocorrencia']);
		$campos['data_digitacao']=date("Y-m-d H:i:s");
		$campos['publica']=gDBCheck($todosOsCampos['publica']);
		return $campos;
	}

	/**
	* Cria um novo registro no banco de dados e salva valores passados (tratando dados antes)
	*/
	function insereOcorrencia($todosOsCampos, $gId)
	{
		$todosOsCampos['id_itens'] = $gId;
		return dbInsert("itens_ocorrencias", $this->preparaCamposOcorrencia($todosOsCampos), true);
	}

	/**
	* Modifica um registro no banco de dados e salva com valores passados (tratando dados antes)
	*/
	function modificaOcorrencia($todosOsCampos, $gId, $gIdEnd)
	{
		$todosOsCampos['id_itens']=$gId;
		dbUpdate('itens_ocorrencias', $this->preparaCamposOcorrencia($todosOsCampos), $gIdEnd);
		return true;
	}


	public function verificarDuplicataSku($idPessoasProprietario, $idItens, $codigoBarras)
	{
		$where = array();
		if ($idItens) {
			$where[] = "skuReferencia.id_itens = {$idItens}";
			$where[] = "skuComparado.id_itens <> {$idItens}";
		}

		if ($codigoBarras) {
			$where[] = "skuReferencia.codigo_barras = '" . $codigoBarras . "'";
		}

		$where = implode(" AND ", $where);

		$sql = "SELECT
					skuComparado.id_itens,
					skuComparado.id AS id_itens_skus,
					CONCAT(itens.codigo, '-', itens.nome) AS itemDetalhes
				FROM itens_skus skuReferencia
				LEFT JOIN itens_skus skuComparado ON skuComparado.codigo_barras = skuReferencia.codigo_barras
				JOIN itens ON itens.id = skuComparado.id_itens
				WHERE itens.id_pessoas_proprietario = {$idPessoasProprietario} AND {$where}
				GROUP BY skuComparado.id_itens";
		$skus = dbFastQuery($sql);
		if (!$skus) {
			return false;
		}

		$msg = array();
		foreach ($skus as $sku) {
			$msg[] = "Esse SKU já está sendo utilizado no cadastro do item: " . linkParaCadastroSku($sku['id_itens_skus'], $sku['itemDetalhes']);
		}
		return $msg;

	}
}