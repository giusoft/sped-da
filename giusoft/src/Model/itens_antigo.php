<?php
include_once "Pessoas.php";
class Itens extends Pessoas
{
	function __construct()
	{
		$this->tabela = "itens";
		$this->filtro = "";
		$this->ordenacao = "p.nome, i.nome";
	}

	public function validarSKU($atualizacao = 0)
	{
		global $gId, $gIdd, $gParam;
		$erros = array();
		if ($atualizacao == 1) {
			include_once "../../classes.php";
			$uma 	= new UMA();
			$where 	= " UI.id_itens_skus=".$gIdd." AND U.ativo=1 AND UI.cancelada=0";
			$rs 	= $uma->obtemUmasComSaldo($where);

			if ($rs) {
				$erros[] = "O Item já tem umas vinculadas e não pode mais ser alterado.";
			}
		} else {
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
		}
		return ($erros);
	}


	function obtemQueryConsulta($joinSku = "", $camposSku = "")
	{
		global $gParam;
		if ($gParam['INTEGRACAO_WINTHOR']['ativo'] || $joinSku) {
			$outrosAtributos = ", itens_skus.id_unidades , CONCAT(itens_skus.codigo_barras, '•', itens_skus.codigo_barras_alternativo) AS codigos_barras ";
			if ($camposSku) {
				$outrosAtributos = ", {$camposSku}";
			}
			$leftSkus = ' LEFT JOIN itens_skus ON itens_skus.id_itens = i.id';
		}

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
			JOIN pessoas_armazens a ON a.id_pessoas = p.id
			LEFT JOIN pessoas pc ON i.id_pessoas_criou = pc.id
			LEFT JOIN pessoas pf ON i.id_pessoas_fornecedor = pf.id
			LEFT JOIN pessoas pa ON i.id_pessoas_alterou = pa.id
			LEFT JOIN grupos g ON i.id_grupos = g.id
			LEFT JOIN tipos t ON i.id_tipos = t.id
			{$leftSkus}";

		return($sql);
	}


	function geraCamposDoFormulario(&$frm, $registroAtual, $proximaPagina="") {
		// O formulário de edição de dados usa este método
		global $proximaPagina, $gId, $gPage, $o, $sp, $gParam;

		if ($proximaPagina=="") {
			$proximaPagina=$gPage+1;
		}

		$spSKUs = "SELECT id,nome FROM itens_skus WHERE id_itens=".$gId." ORDER BY id";

		if ($gParam['PERFIL_FABRICANTE']['ativo']==1) {
			$frm->row(
				$frm->add("{name: nome; fieldLabel: Nome *; type: upperText; value: ".$registroAtual['nome']."}; allowBlank: false;"),
				$frm->add("{name: descricao; fieldLabel: Descrição; type: text; value: ".$registroAtual['descricao']."}"),
				$frm->add("{name: codigo; fieldLabel: Código *; type: upperText; value: ".$registroAtual['codigo']."; allowBlank: false;}"),
				$frm->add("{name: codigo_barras; fieldLabel: Código de barras; type: upperText; value: ".$registroAtual['codigo_barras']."}")
			);
			$frm->row(
				$frm->add("{name: id_pessoas_proprietario; fieldLabel: Cliente; allowBlank: false; type: combo; value: ".$registroAtual['id_pessoas_proprietario']."; items: ".$sp['combo_clientes']."}"),
				$frm->add("{name: id_pessoas_fornecedor; fieldLabel: Fornecedor; allowBlank: true; type: combo; value: ".$registroAtual['id_pessoas_fornecedor']."; items: ".$sp['combo_fornecedores']."}"),
				$frm->add("{name: id_grupos; fieldLabel: Grupo; type:combo; value: ".$registroAtual['id_grupos']."; items: ".$sp['combo_grupos']."}"),
				$frm->add("{name: id_tipos; fieldLabel: Tipo; allowBlank: false; type: combo; value: ".$registroAtual['id_tipos']."; items: ".$sp['combo_tipos']."}")
			);
		} else {
			$frm->row(
				$frm->add("{name: nome; fieldLabel: Nome *; type: upperText; value: ".$registroAtual['nome']."; allowBlank: false;}"),
				$frm->add("{name: descricao; fieldLabel: Descrição; type: text; value: ".$registroAtual['descricao']."}"),
				$frm->add("{name: codigo; fieldLabel: Código *; type: upperText; value: ".$registroAtual['codigo']."; allowBlank: false;}"),
				$frm->add("{name: codigo_barras; fieldLabel: Código de barras *; type: upperText; value: ".$registroAtual['codigo_barras']."; allowBlank: false;}")
			);
			$frm->row(
				$frm->add("{name: id_pessoas_proprietario; fieldLabel: Cliente *; allowBlank: false; type: combo; value: ".$registroAtual['id_pessoas_proprietario']."; items: ".$sp['combo_clientes']."; allowBlank: false;}"),
				$frm->add("{name: id_pessoas_fornecedor; fieldLabel: Fornecedor; allowBlank: true; type: combo; value: ".$registroAtual['id_pessoas_fornecedor']."; items: ".$sp['combo_fornecedores']."}"),
				$frm->add("{name: id_grupos; fieldLabel: Grupo; type: combo; value: ".$registroAtual['id_grupos']."; items: ".$sp['combo_grupos']."}"),
				$frm->add("{name: id_tipos; fieldLabel: Tipo; allowBlank: false; type: combo; value: ".$registroAtual['id_tipos']."; items: ".$sp['combo_tipos']."}")
			);

		}

		$campoPrazoRecebimento = '';
		if ($gParam['POSSUI_PRAZO_RECEBIMENTO']['ativo']) {
			$campoPrazoRecebimento = $frm->add("{name: prazo_recebimento; fieldLabel: Prazo de recebimento (dias); maxLength: 3; type: number; value: ".$registroAtual['prazo_recebimento']."}");
		}
		$dataCritica = '';
		$dataCritica = 	$frm->add("{name: data_critica; fieldLabel: Data Crítica (dias); type: number; value: ".$registroAtual['data_critica']."}");

		$frm->row(
			$frm->add("{name: prazo_validade; fieldLabel: Prazo validade (dias); type: number; value: ".$registroAtual['prazo_validade']."}"),
			$frm->add("{name: shelf_life; fieldLabel: Shelf life (dias); type: number; value: ".$registroAtual['shelf_life']."}"),
			$campoPrazoRecebimento,
			$frm->add("{name: temperatura_ideal; fieldLabel: Temperatura ideal (°C); type: number; value: ".$registroAtual['temperatura_ideal']."}"),
			$frm->add("{name: temperatura_limite; fieldLabel: Temperatura limite (°C); type: number; value: ".$registroAtual['temperatura_limite']."}")
		);

		$frm->row(
			$frm->add("{name: id_itens_skus_reposicao_picking; fieldLabel: SKU reposição picking; allowBlank: true; type: combo; value: ".$registroAtual['id_itens_skus_reposicao_picking']."; items: $spSKUs}" ),
			$frm->add("{name: id_itens_skus_operacao; fieldLabel: SKU operação; allowBlank: true; type: combo; value: ".$registroAtual['id_itens_skus_operacao']."; items: $spSKUs}" ),
			$frm->add("{name: id_itens_skus_pedido; fieldLabel: SKU pedido pelo cliente; allowBlank: true; type: combo; value: ".$registroAtual['id_itens_skus_pedido']."; items: $spSKUs}"),
			$frm->add("{name: id_prioridades_saida; fieldLabel: Prioridade de saída; allowBlank: false; type: combo; value: ".($gId==0 ? "FIFO" : $registroAtual['id_prioridades_saida'])."; items: ".$sp['combo_prioridades_saida']."}")
		);

		$comboGrupo="SELECT id, CONCAT(codigo, ' • ', descricao) descricao FROM grupos_combustivel";
		$frm->row(
			$dataCritica,
			$frm->add("{name: dias_bloqueio; fieldLabel: Dias Bloqueio; type: number; maxLength: 4; value: ".$registroAtual['dias_bloqueio']."} "),
			$frm->add("{name: id_grupos_combustivel; fieldLabel: Grupo do combustível; type: combo; items:".$comboGrupo."; value: ".$registroAtual['id_grupos_combustivel']."}"),
			$frm->add("{name: ncm; fieldLabel: NCM *; type: text; maxLength: 8; value: ".$registroAtual['ncm']."; allowBlank: false;}")
		);

		if ($gParam['PERFIL_FABRICANTE']['ativo']==1) {
			$frm->row(
				$frm->add("{name: picking_quantidade_minima; fieldLabel: Picking - Qtd. mínima; type: number; value: ".$registroAtual['picking_quantidade_minima']."}"),
				$frm->add("{name: picking_quantidade_maxima; fieldLabel: Picking - Qtd. máxima; type: number; value: ".$registroAtual['picking_quantidade_maxima']."}"),
				$frm->add("{name: faz_picking; fieldLabel: Faz picking; type: checkbox; value: ".($gId==0?0:$registroAtual['faz_picking'])."}")
			);

		} else {
			$curvas = array(
				'1' => 'A',
				'2' => 'B',
				'3' => 'C'
			);

			$frm->row(
				$frm->add("{name: picking_quantidade_minima; fieldLabel: Picking - Qtd. mínima; type: number; value: ".$registroAtual['picking_quantidade_minima']."}"),
				$frm->add("{name: picking_quantidade_maxima; fieldLabel: Picking - Qtd. máxima; type: number; value: ".$registroAtual['picking_quantidade_maxima']."}"),
				$frm->add("{name: curva; fieldLabel: Curva; type: combo; value: " . array_flip($curvas)[$registroAtual['curva']]. "; }", $curvas),
				$frm->add("{name: faz_picking; fieldLabel: Faz picking; type: checkbox; value: ".($gId==0?0:$registroAtual['faz_picking'])."}"),
				$frm->add("{name: critico; fieldLabel: Item Crítico; type: checkbox; value: ". (int) $registroAtual['critico']." }"),
				$frm->add("{name: restringir_posicionamento_lateral; fieldLabel: Restringir posicionamento lateral; type: checkbox; value: ". (int) $registroAtual['restringir_posicionamento_lateral']." }")
			);

		}

		$frm->row(
			$frm->add("{name: exige_lote; fieldLabel: Exigir Lote na entrada; type: checkbox; value: ".($gId==0?0:$registroAtual['exige_lote'])."}"),
			$frm->add("{name: exige_serial; fieldLabel: Exigir Serial na entrada; type: checkbox; value: ".($gId==0?0:$registroAtual['exige_serial'])."}"),
			$frm->add("{name: exige_data_fabricacao; fieldLabel: Exigir data fabricação na entrada; type: checkbox; value: ".($gId==0?0:$registroAtual['exige_data_fabricacao'])."}"),
			$frm->add("{name: exige_data_validade; fieldLabel: Exigir data validade na entrada; type: checkbox; value: ".($gId==0?0:$registroAtual['exige_data_validade'])."}"),
			$frm->add("{name: ativo; fieldLabel: Ativo; type: checkbox; value: ".($gId==0?1:$registroAtual['ativo'])."}")
		);

		$observacoes = decodificarObservacao($registroAtual['observacoes']);

		$frm->row(
			$frm->add("{name: observacoes;type: textarea; value: ".$observacoes."}")
		);
		$frm->add("{name: gId;type: hidden; value: ".$gId."}");
		$frm->add("{name: gPage; type: hidden; value: ".$proximaPagina."}");
		$html =$frm->render($o);
		$html.=$o->msg("* Campos obrigatórios para tornar o item apto para utilização.");
		return($html);
	}

	function aptoAreas($gId) {
		global $gParam;
		$apto = true;
		if ($gParam['POSICIONAMENTO_LIVRE']['ativo']==0) {
			$sql = "SELECT IK.*, I.apto FROM itens_areas IK LEFT JOIN itens I ON IK.id_itens=I.id WHERE IK.id_itens=".$gId;
			$rst = dbQuery($sql);
			// Nenhum SKU, então não está apto
			if (count($rst)==0) {
				$apto = false;
			}
		}
		return($apto);
	}


	function aptoPosicaoFixa($gId, $fazPicking) {
		global $gParam;
		$apto = true;
		if ($fazPicking && $gParam['USA_POSICAO_FIXA_PICKING']['ativo']) {
			$posicaoFixa = dbQuery("SELECT id FROM itens_areas WHERE id_itens = $gId AND id_posicoes > 0 LIMIT 1")[0]['id'];
			if (!$posicaoFixa) {
				$apto = false;
			}
		}
		return($apto);
	}


	function aptoSKUs($gId) {
		global $gParam;
		$apto = true;
		$sql = "SELECT IK.*, I.apto FROM itens_skus IK LEFT JOIN itens I ON IK.id_itens=I.id WHERE IK.id_itens=".$gId;
		$rst = dbQuery($sql);

		if (
			$gParam['PERFIL_FABRICANTE']['ativo']==0
			&& $gParam["USA_REGRA_PALETIZACAO"]["ativo"]==1
		) {
			// Nenhum SKU, então não está apto
			if (count($rst)==1 && ($rst[0]["id_unidades"]==1 && $rst[0]["codigo_barras"]=="" && $rst[0]["codigo"]=="") ) {
				$apto = false;
			}

			// Tem que ter pelo menos um SKU com condições pra calcular a altura
			// do palete e a quantidade de itens por palete
			foreach ($rst as $row) {

				if ($row["quantidade"]==0) {
					$apto = false;
					break;
				} else if (
					($row['quantidade']>1
					|| ($row["quantidade"]==1 && $row["id_unidades"]>1) )
					&& ($row['palete_altura']==0 || $row['palete_lastro']==0 || $row['altura']==0)
				) {
					$apto = false;
					break;
				}
			}
		}
		return($apto);
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
		$campos['critico']=gDBCheck($todosOsCampos['critico']);
		$campos['descricao']=gCleanField($todosOsCampos['descricao']);
		$campos['id_pessoas_proprietario']=intval($todosOsCampos['id_pessoas_proprietario']);
		$campos['id_pessoas_fornecedor']=intval($todosOsCampos['id_pessoas_fornecedor']);
		$campos['id_grupos']=intval($todosOsCampos['id_grupos']);
		$campos['id_tipos']=intval($todosOsCampos['id_tipos']);
		$campos['id_prioridades_saida']=intval($todosOsCampos['id_prioridades_saida']);
		$campos['prazo_validade']=intval($todosOsCampos['prazo_validade']);
		$campos['shelf_life']=intval($todosOsCampos['shelf_life']);
		$campos['ativo']=gDBCheck($todosOsCampos['ativo']);
		$campos['temperatura_ideal']=gDBFloat($todosOsCampos['temperatura_ideal']);
		$campos['temperatura_limite']=gDBFloat($todosOsCampos['temperatura_limite']);
		$campos['ncm']=gJustNumbers($todosOsCampos['ncm']);
		$campos['id_itens_skus_operacao']=intval($todosOsCampos['id_itens_skus_operacao']);
		$campos['id_itens_skus_pedido']=intval($todosOsCampos['id_itens_skus_pedido']);
		$campos['id_itens_skus_reposicao_picking']=intval($todosOsCampos['id_itens_skus_reposicao_picking']);
		$campos['picking_quantidade_minima']=intval($todosOsCampos['picking_quantidade_minima']);
		$campos['picking_quantidade_maxima']=intval($todosOsCampos['picking_quantidade_maxima']);
		$campos['restringir_posicionamento_lateral']=gDBCheck($todosOsCampos['restringir_posicionamento_lateral']);
		$curvas = array(
			'1' => 'A',
			'2' => 'B',
			'3' => 'C'
		);
		$campos['curva'] = $curvas[$todosOsCampos['curva']];

		if ($gParam['POSSUI_PRAZO_RECEBIMENTO']['ativo']) {
			$campos['prazo_recebimento'] = (int) $todosOsCampos['prazo_recebimento'];
		}

		$campos['faz_picking'] = gDBCheck($todosOsCampos['faz_picking']);
		$campos['exige_lote'] = gDBCheck($todosOsCampos['exige_lote']);
		$campos['exige_serial'] = gDBCheck($todosOsCampos['exige_serial']);
		$campos['exige_data_fabricacao'] = gDBCheck($todosOsCampos['exige_data_fabricacao']);
		$campos['exige_data_validade'] = gDBCheck($todosOsCampos['exige_data_validade']);
		$campos['id_grupos_combustivel'] = intval($todosOsCampos['id_grupos_combustivel']);
		$campos['data_critica'] = (int) $todosOsCampos['data_critica'];
		$campos['dias_bloqueio'] = (int) $todosOsCampos['dias_bloqueio'];

		if (strlen($todosOsCampos['observacoes'])<20) {
			$campos['observacoes'] = ($todosOsCampos['observacoes']);
		} else {
			$campos['observacoes'] = base64_encode($todosOsCampos['observacoes']);
		}

		# Campos necessários para que o item esteja apto para uso
		$campos['apto'] = 0;
		if ($gParam['PERFIL_FABRICANTE']['ativo']) {
			if (	$campos['nome']<>"" &&
					$campos['codigo']<>""
				)
			{
				$campos['apto'] = 1;
				if ($gId>0) {

					if (!$this->aptoSKUs($gId)) {
						$campos['apto'] = 0;
					}

					if (!$this->aptoAreas($gId)) {
						$campos['apto'] = 0;
					}

					if (!$this->aptoPosicaoFixa($gId, $campos['faz_picking'])) {
						$campos['apto'] = 0;
					}
				}
			}
		} else {
			if (	$campos['nome']<>"" &&
					$campos['codigo_barras']<>"" &&
					$campos['codigo']<>"" &&
					$campos['ncm']<>"" &&
					$campos['id_pessoas_proprietario']>0
				)
			{
				$campos['apto'] = 1;
				if ($gId>0) {

					if (!$this->aptoSKUs($gId)) {
						$campos['apto'] = 0;
					}

					if (!$this->aptoAreas($gId)) {
						$campos['apto'] = 0;
					}

					if (!$this->aptoPosicaoFixa($gId, $campos['faz_picking'])) {
						$campos['apto'] = 0;
					}
				}
			} else {
				$this->erros[] = 'Preencha todos os campos obrigatórios: Nome, código, código de barras, NCM e proprietário';
			}
		}

		if ($gId==0) {
			$campos['data_cadastro']      = $hoje;
			$campos['id_pessoas_criou']   = $usrId;
		}else {
			$campos['data_alteracao']     = $hoje;
			$campos['id_pessoas_alterou'] = $usrId;
		}
		return($campos);
	}


	public function insere($campos, &$gId)
	{
		global $o, $gParam;
		$gId = false;
		// Validações:
		if ($gParam['EXTRAIR_LOTE_TAG_XPROD']['ativo']) {
			$sql = "SELECT lote_xprod FROM pessoas_juridicas WHERE id_pessoas = ".$campos['id_pessoas_proprietario'];
			$rs  = dbFastQuery($sql);
			if ($rs[0]['lote_xprod'] && gDBCheck($campos['exige_lote'])) {
				$this->erros[] = 'Este item não pode ter exigência de lote pois o lote é informado na descrição do item na nota do proprietário deste';
				return false;
			}
		}

		if ($gParam['PERMITIR_SERIAL_COMO_LOTE']['ativo']) {
			if (gDBCheck($campos['exige_lote']) && gDBCheck($campos['exige_serial'])) {
				/*os lote e serial nao podem ficar ativos para o mesmo item
				 se o parametro PERMITIR_SERIAL_COMO_LOTE estiver ativo
				 Ou exige lote ou exige serial, usuario eh obrigado a ajustar
				*/
				$this->erros[] = 'O item só pode exigir ou lote ou serial, não podendo exigir os dois ao mesmo tempo';
				return false;
			}
		}

		if (!is_numeric($campos['ncm']) || strlen($campos['ncm']) != 8) {
			$this->erros[] = "O NCM informado não é válido. Verifique se o código foi digitado corretamente";
			return false;
		}

		$campos = $this->preparaCampos($campos, $gId);
		// Verifica se já existe algum produto com este código, se ele não estiver em branco
		$codigoOk = true;
		if ($campos['codigo'] <> "") {
			$sql = "SELECT * FROM itens WHERE id_pessoas_proprietario = " . $campos['id_pessoas_proprietario'] . " AND codigo='".$campos['codigo']."'";
			$rst = dbFastQuery($sql);

			if ($rst) {
				$codigoOk = false;
			}
		}

		if (!$codigoOk) {
			$this->erros[] = "Produto com o código [".$campos['codigo']."] já está cadastrado para esta empresa";
			return false;
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

		if ($gParam['EXTRAIR_LOTE_TAG_XPROD']['ativo']) {
			//TODO:: avaliar eficacia deste codigo
			//$this->notaItem['lote'] tem algum efeito sobre o sistema?
			$sql = "SELECT lote_xprod FROM pessoas_juridicas WHERE id_pessoas =".$campos['id_pessoas_proprietario'];
			$rs  = dbQuery($sql)[0]['lote_xprod'];

			if ($rs) {
				$loteExtraido = $this->extrairLoteTagXprod($registro->prod->xProd);
				$this->notaItem["lote"] = gCleanField($loteExtraido);
			}
		}

		$gId = dbInsert('itens',$campos, true);
		$campos = array();
		$campos['id_itens']      = $gId;
		$campos['data_cadastro'] = date('Y-m-d H:i:s');
		$campos['codigo']        = gCleanField($_REQUEST['codigo']);
		$campos['codigo_barras'] = gCleanField($_REQUEST['codigo_barras']);
		$campos['nome']          = 'Item individual';
		$campos['id_unidades']   = 1;
		$campos['quantidade']    = 1;
		dbInsert('itens_skus', $campos);

		return($gId);
	}

	function modifica($campos, $gId)
	{
		global $o, $gParam;
		$sucesso = true;
		if ($gParam['EXTRAIR_LOTE_TAG_XPROD']['ativo']) {
			$sql = "SELECT lote_xprod FROM pessoas_juridicas WHERE id_pessoas = ".$campos['id_pessoas_proprietario'];
			$rs = dbQuery($sql);
			if ($rs[0]['lote_xprod'] && gDBCheck($campos['exige_lote'])) {
				$this->erros[] = 'Este item não pode ter exigência de lote pois o lote é informado na descrição do item na nota do proprietário deste';
				return false;
			}
		}

		if ($gParam['PERMITIR_SERIAL_COMO_LOTE']['ativo']) {
			if (gDBCheck($campos['exige_lote']) && gDBCheck($campos['exige_serial'])) {
				/*os lote e serial nao podem ficar ativos para o mesmo item
				 se o parametro PERMITIR_SERIAL_COMO_LOTE estiver ativo
				 Ou exige lote ou exige serial, usuario eh obrigado a ajustar
				*/
				$this->erros[] = 'O item só pode exigir ou lote ou serial, não podendo exigir os dois ao mesmo tempo';
				return false;
			}
		}

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
				$codigoOk = false;
			}
		} else {
			$codigoOk = true;
		}

		if (!$codigoOk) {
			$sucesso = false;
			$this->erros[] = "Produto com o código [" . $campos['codigo'] . "] já está cadastrado para esta empresa (Item <a href=".$o->page."&gPage=10&gId=".$rst[0]['id'].">[".$campos['nome']."]</a>)";
			return false;
		}

		// Permite o cadastramento do mesmo item em clientes diferentes (definido por parametro de configuração)
		if ($gParam['PERMITIR_ITENS_IGUAIS']['ativo'] == 1) {
			$flt = "id_pessoas_proprietario = " . $campos['id_pessoas_proprietario'] . " AND ";
		}
		// Verifica se já existe algum produto com este código de barras
		$sql = "SELECT * FROM itens WHERE id <> $gId AND $flt codigo_barras = '" . $campos['codigo_barras'] . "' AND ativo = 1";
		$rst = dbFastQuery($sql);

		if ($rst) {
			$this->erros[] = "Código de barras já está em uso pelo item: <a href=".$o->page."&gPage=10&gId=".$rst[0]['id'].">[".$campos['nome']."]</a>";
			return false;
		}

		dbUpdate('itens', $campos, $gId);


		return($sucesso);
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

	function obtemRegistrosOcorrencias($id="")
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
	function geraCamposDoFormularioOcorrencias(&$frm, $registroAtual, $proximaPagina="", $gIdEnd)
	{
		global $proximaPagina, $gId, $gPage, $o, $sp;
		if ($proximaPagina=="")
		{
			$proximaPagina=$gPage+1;
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
		return($campos);
	}

	/**
	* Cria um novo registro no banco de dados e salva valores passados (tratando dados antes)
	*/
	function insereOcorrencia($todosOsCampos, $gId)
	{
		$todosOsCampos['id_itens']=$gId;
		return(dbInsert("itens_ocorrencias", $this->preparaCamposOcorrencia($todosOsCampos), true));
	}

	/**
	* Modifica um registro no banco de dados e salva com valores passados (tratando dados antes)
	*/
	function modificaOcorrencia($todosOsCampos, $gId, $gIdEnd)
	{
		$todosOsCampos['id_itens']=$gId;
		dbUpdate('itens_ocorrencias', $this->preparaCamposOcorrencia($todosOsCampos), $gIdEnd);
		return(true);
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