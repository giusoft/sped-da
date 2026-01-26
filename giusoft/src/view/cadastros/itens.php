<?php

define("INICIO",                     0);
define("INICIO_PESQUISAR", 		     1);
define("INICIO_PESQUISAR_RESULTADO", 2);

define("CAPA",                      10);
define("DADOS",                     10);
define("DADOS_SALVAR",              11);

define("SKUS",                      20);
define("SKUS_NOVO",                 21);
define("SKUS_SALVAR",               22);
define("SKUS_EXCLUIR",              23);

define("IMAGENS",                   40);
define("IMAGENS_SALVAR",            41);
define("IMAGENS_EXCLUIR",           42);
define("IMAGENS_UPLOAD",            43);

define("IMAGENS_RAPIDO",            45);
define("IMAGENS_RAPIDO_SALVAR",     46);

define("ATIVAR_DESATIVAR",          50);
define("COPIAR",                    51);
define("COPIAR_SALVAR",             52);

define("IMPORTAR",          		60);
define('IMPORTACOES_DIVERSAS',      61);

define("ATUALIZAR_EM_LOTE", 80);
define("ATUALIZAR_EM_LOTE_PESQUISAR", 81);
define("CONFIRMAR_ATUALIZAR_EM_LOTE_PESQUISAR", 82);
define("FINALIZOU_ATUALIZAR_EM_LOTE_PESQUISAR", 83);

define("FORNECEDORES", 100);
define("CADASTRAR_FORNECEDOR", 101);
define("EXCLUIR_FORNECEDOR", 102);

define("FORMULARIO_IMPORTACAO_DE_FORNECEDORES", 400);
define("IMPORTACAO_DE_FORNECEDORES", 401);
define("CONFIRMAÇÃO_IMPORTACAO_DE_FORNECEDORES", 402);
define("IMPRIMIR_MODELO", 410);


// Removendo paginação e limit quando for exportação
if (
	$_REQUEST["gPDF"]
	|| $_REQUEST["gXLS"]
	|| $_REQUEST["gDOC"]
	|| $_REQUEST["gCSV"]
	|| $gPage == INICIO_PESQUISAR_RESULTADO
) {
	$gParam["PAGINACAO"]["ativo"] = 0;
	$gParam["LIMITAR_VISUALIZACAO"]["ativo"] = 0;
}

if ($gPage < 10) {
	$o->PDFEnabled = true;
	$o->DOCEnabled = true;
	$o->XLSEnabled = true;
	$o->CSVEnabled = true;
}

$gIdd = (int) $_REQUEST['gIdd'];
$gPathUsrFiles = $gPath . "files/itens/";
$httpUsrFiles = $http_base . "files/itens/";

gVar("global.numformat", "0.000,0000");

include_once __DIR__ . "/../../Model/itens_antigo.php";
$persistencia = new Itens();

$html .= $o->msgTitle("Cadastro de itens");

if ($gId) {
	$aptoNoBanco = gFieldById("itens", $gId, "apto");
	$aptoSKUs = $persistencia->aptoSKUs($gId);
	if ($aptoNoBanco <> $aptoSKUs) {
		if ($aptoSKUs) {
			$apto = 1;
		} else {
			$apto = 0;
		}
		$persistencia->aptoAtualiza($gId, $apto);
	}
}

switch ($gPage) {
	case INICIO:
		$html .= '<div class="hidden-print"><form class="form-inline" method="POST" action="index.php?g=itens">';
		$html .= $o->button("{icon: plus; caption: Novo; hint: Cadastrar um novo item; style: info; size: normal; href: index.php?g=itens&gPage=" . DADOS . "}");
		$html .= '<input id="pesquisaRapida" name="pesquisaRapida" type="text" class="form-control input-md" placeholder="Cód/Nome/Cliente">&nbsp;<input type="hidden" name="g" value="itens"><input type="hidden" name="gPage" value="' . INICIO_PESQUISAR_RESULTADO . '"> ';
		$html .= '<button type="submit" class="btn btn-default" style="margin-bottom: 4px"><span class="fal fa-search"></span> Pesquisar</button> ';
		$html .= $o->button("{icon: exchange; caption: Atualização em lote; href: " . $o->page . "&gPage=" . ATUALIZAR_EM_LOTE . "}");
		$html .= $o->button("{icon: download; caption: Importar; href: " . $o->page . "&gPage=" . IMPORTACOES_DIVERSAS . "}");
		$html .= '</form>';
		$html .= '<br></div>';

		$js = "
			$('#pesquisaRapida').keydown(function(event) {
				if (event.keyCode == 13) {
					this.form.submit();
					return false;
				}
			});
		";
		$o->addJavascript($js);

		$persistencia->agrupamento = "p.id, pf.id, pc.id, pa.id, g.id, t.id, i.id {$agrupaSku}";

		$persistencia->filtro = "((a.id_filial=" . intval($_SESSION["filialAtualId"]) . ") OR (a.id_filial IS NULL)) AND a.cancelado = 0";

		$rs = $persistencia->obtemRegistros();

		if (!$rs) {
			$html .= $o->msgWarning("Nenhum item cadastrado ainda");
			break;
		}

		if ($gParam["PAGINACAO"]["ativo"]) {
			$html .= $persistencia->pagination->render('{style:margin-top:-1.6%;}');
		}

		$html .= $o->tableBegin('big', true, true);

		$mtz = [];
		$mtz[] = "<-Opções";
		$mtz[] = "<>Ativo";
		$mtz[] = "<>Apto";
		$mtz[] = "<-Nome      	               ";
		$mtz[] = "<-Código";
		$mtz[] = "<-Proprietário     ";
		$mtz[] = "<-Fornecedor       ";
		$mtz[] = "<-Grupo         ";
		$mtz[] = "<-Tipo          ";
		if (!$gPDF) {
			$mtz[] = "<>Cadastro      ";
			$mtz[] = "<>Alteração     ";
		}

		$html .= $o->tableRow($mtz, 'header');

		foreach ($rs as $id => $row) {
			$mtz = [];
			$mtz[] = '<-' . $o->button("{icon: folder-open; caption: Abrir; hint: Abrir a ficha do item; size: small; href: " . $o->page . "&gPage=" . CAPA . "&gId=" . $row['id'] . "}");
			$mtz[] = '<>' . gCheck($row['ativo']);
			$mtz[] = '<>' . gCheck($row['apto']);
			$mtz[] = '<-' . $row['nome'] . '<br>' . $o->small($row['descricao']);
			$mtz[] = '<-' . $row['codigo'];
			$mtz[] = '<-' . $row['proprietario'];
			$mtz[] = '<-' . $row['fornecedor'];
			$mtz[] = '<-' . $row['grupo'];
			$mtz[] = '<-' . $row['tipo'];
			if (!$gPDF) {
				$mtz[] = '<>' . gDateTime($row['data_cadastro']) . '<br><small>' . $row['criou'] . '</small>';
				$mtz[] = '<>' . gDateTime($row['data_alteracao']) . '<br><small>' . $row['alterou'] . '</small>';
			}

			$html .= $o->tableRow($mtz,'detail');
		}

		$html .= $o->tableEnd();

		if ($gParam["PAGINACAO"]["ativo"]) {
			$html .= $persistencia->pagination->render('{id:o; style:margin-top:-1.4%;;}');
		}

		break;


	case INICIO_PESQUISAR:
		$frm   = new gForm("columns: 3;");
		$html .= $o->msgSubTitle("Pesquisar itens");
		$frm->addFormMessage("Informe uma ou mais opções abaixo para a busca");
		$frm->add("{name: nome}");
		$frm->add("{name: codigo_barras; type: text;}");
		$frm->add("{name: codigo; fieldLabel: Código; type: text;}");
		$comboOpcoes = [
			'*Indiferente',
			'Sim',
			'Não'
		];
		$frm->add("{name: situacao; fieldLabel: Ativo; allowBlank: true; type: combo; items: " . json_encode($comboOpcoes) . ";}");
		$frm->add("{name: id_pessoas_proprietario; fieldLabel: Cliente; type: combo; items: " . $sp['combo_clientes'] . "}");
		$frm->add("{name: id_pessoas_fornecedor; fieldLabel: Fornecedor; type: combo; items: " . $sp['combo_fornecedores'] . "}");
		$frm->add("{name: id_grupos; fieldLabel: Grupo; type: combo; items: " . $sp['combo_grupos'] . "}");
		$frm->add("{name: id_tipos; fieldLabel: Tipos; type: combo; items: " . $sp['combo_tipos'] . "}");
		$frm->add("{name: mostrarDetalhesSku; fieldLabel: Mostrar detalhes SKU; type: checkbox;}");
		$frm->add("{name: gPDFOrientation; type: hidden; value: L;}");
		$frm->add("{name: gPage; type: hidden; value: " . INICIO_PESQUISAR_RESULTADO . "}");
		$frm->add("{name: formulario; type: hidden; value: 1}");
		$html .= $frm->render($o);
		break;


	case INICIO_PESQUISAR_RESULTADO:
		$pesquisaRapida  = gCleanField($_REQUEST['pesquisaRapida']);
		$nome = gCleanField($_REQUEST['nome']);
		$codigo = gCleanField($_REQUEST['codigo']);
		$codigoBarras = gCleanField($_REQUEST['codigo_barras']);
		$idPessoasProprietario = (int) $_REQUEST['id_pessoas_proprietario'];
		$idPessoasFornecedor = (int) $_REQUEST['id_pessoas_fornecedor'];
		$situacao = (int) $_REQUEST['situacao'];
		$idGrupos = (int) $_REQUEST['id_grupos'];
		$idTipos = (int) $_REQUEST['id_tipos'];

		$html .= '<div class="hidden-print"><form class="form-inline" method="POST" action="index.php?g=itens">';
		$html .= $o->button("{icon: plus; caption: Novo; hint: Cadastrar um novo item; style: info; size: normal; href: index.php?g=itens&gPage=" . DADOS . "}");
		$html .= '<input id="pesquisaRapida" name="pesquisaRapida" type="text" class="form-control input-md" placeholder="Cód/Nome/Cliente">&nbsp;<input type="hidden" name="g" value="itens"><input type="hidden" name="gPage" value="' . INICIO_PESQUISAR_RESULTADO . '"> ';
		$html .= '<button type="submit" class="btn btn-default" style="margin-bottom: 4px"><span class="fal fa-search"></span> Pesquisar</button> ';
		$html .= $o->button("{icon: exchange; caption: Atualização em lote; href: " . $o->page . "&gPage=" . ATUALIZAR_EM_LOTE . "}");
		$html .= $o->button("{icon: download; caption: Importar; href: " . $o->page . "&gPage=" . IMPORTACOES_DIVERSAS . "}");
		$html .= '</form>';
		$html .= '<br></div>';
		$js = "
			$('#pesquisaRapida').keydown(function(event) {
				if (event.keyCode == 13) {
					this.form.submit();
					return false;
				}
			});
		";

		$o->addJavascript($js);

		$where = [];
		$filtro = [];

		if ($pesquisaRapida) {
			$where[] = "(i.nome LIKE '%$pesquisaRapida%' OR p.nome LIKE '%$pesquisaRapida%' OR pf.nome LIKE '%$pesquisaRapida%' OR i.codigo LIKE '%$pesquisaRapida%' OR i.codigo_barras LIKE '%$pesquisaRapida%')";
			$html .= $o->msgFilter('Pesquisar por: ' . $pesquisaRapida);
		} else {
			if (
				trim($nome.$codigo.$codigoBarras) == ''
				&& (
					(int) $idPessoasProprietario.$idPessoasFornecedor.$situacao
					.$idGrupos.$idTipos
				) == 0
			) {
				redirect($o->page . '&gPage=' . INICIO_PESQUISAR);
			}

			if ($nome<>'') {
				$where[] = "(i.nome LIKE '%{$nome}%')";
				$filtro[] = "Nome: {$nome}";
			}

			if ($codigo <> "") {
				$where[] = "i.codigo = '{$codigo}'";
				$filtro[] = "Código: {$codigo}";
			}

			if ($codigoBarras <> "") {
				$where[] = "i.codigo_barras = '{$codigoBarras}'";
				$filtro[] = "Código de barras: {$codigoBarras}";
			}

			if ($idPessoasProprietario>0) {
				$where[] = "i.id_pessoas_proprietario = {$idPessoasProprietario}";
				$filtro[] = "Proprietário: " . gFieldById("pessoas", $idPessoasProprietario, "apelido");
			}

			if ($idPessoasFornecedor>0) {
				$where[] = "i.id_pessoas_fornecedor = {$idPessoasFornecedor}";
				$filtro[] = "Fornecedor: " . gFieldById("pessoas", $idPessoasFornecedor, "apelido");
			}

			if ($idGrupos) {
				$where[] = "i.id_grupos = " . $idGrupos;
				$filtro[] = "Grupo: " . gFieldById("grupos", $idGrupos, "descricao");
			}

			if ($idTipos) {
				$where[] = "i.id_tipos = " . $idTipos;
				$filtro[] = "Tipo: " . gFieldById("tipos", $idTipos, "descricao");
			}

			$comboCondicional = [
				0 => false, // *Indiferente
				1 => 1, // Opcao "Sim"
				2 => 0 // Opcao "Nao"
			];

			if ($situacao) {
				$where[]  = "i.ativo = " . $comboCondicional[$situacao];

				if ($situacao == 1) {
					$filtro[] = 'Somente ativos';
				} else {
					$filtro[] = 'Somente inativos';
				}
			}

			$filtro[] = "Mostrar detalhes dos SKUs: " . gCheck($_REQUEST['mostrarDetalhesSku']);
		}

		$where[] = "((a.id_filial=" . intval($_SESSION["filialAtualId"]) . ") OR (a.id_filial IS NULL)) AND a.cancelado = 0";

		if ($filtro) {
			$html .= $o->msgFilter('Filtros selecionados: ' . implode(" • ", $filtro));
		}

		$persistencia->agrupamento = "p.id, pf.id, pc.id, pa.id, g.id, t.id, i.id";
		$rs = $persistencia->obtemRegistros(implode(" AND ",$where), "", "", 1);
		if (!$rs[0]) {
			$html .= $o->msgWarning("Nada encontrado a partir dos filtros especificados");
			break;
		}

		$html .= $o->button("{icon: copy; caption: Copiar todos; style: info; href: " . $o->page . "&gPage=" . COPIAR .
			"&gId=0
			&nome=$nome
			&codigo=$codigo
			&codigo_barras=$codigoBarras
			&id_pessoas_proprietario=$idPessoasProprietario
			&id_pessoas_fornecedor=$idPessoasFornecedor
			&id_grupos=" . $_REQUEST['id_grupos'] . "
			&id_tipos=" . $_REQUEST['id_tipos'] . "
			&situacao=$situacao
			}"
		);

		$html .= $o->tableBegin('big', true);
		$mtz   = [];

		if (!$gXLS && !$gCSV && !$gXML && !$gPDF && !$gDOC) {
			$mtz[] = '<-Opções';
		}

		if (gDBCheck($_REQUEST['mostrarDetalhesSku'])) {
			$mtz[] = '<-SKU ativo';
		}
		$mtz[] = '<-Código                      ';
		if (gDBCheck($_REQUEST['mostrarDetalhesSku'])) {
			$mtz[] = '<-Un.   ';
		}
		$mtz[] = '<-Nome                                   ';
		$mtz[] = '<-Descrição                              ';
		$mtz[] = '<-Código de barras     ';
		$mtz[] = '<-Proprietário                    ';
		$mtz[] = '<-Fornecedor                      ';
		if (gDBCheck($_REQUEST['mostrarDetalhesSku'])) {
			$mtz[] = '->Peso líquido';
			$mtz[] = '->Peso bruto';
			$mtz[] = '<-Cód. barras - SKU';
		}
		$html .= $o->tableRow($mtz, 'header');

		foreach ($rs as $row) {
			$salt  = gSalt($row['apelido']);
			$mtz   = [];

			if (!$gXLS && !$gCSV && !$gXML && !$gPDF && !$gDOC) {
				$btns  = '<-' . $o->button("{icon: search; caption: Abrir; size: small; href: " . $o->page . "&gPage=" . DADOS . "&gId=" . $row['id'] . "}");
				$btns .= $o->button("{icon: copy; caption: Copiar; style: info; size: small; href: " . $o->page . "&gPage=" . COPIAR . "&gId=" . $row['id'] . "&nome=$nome&codigo=$codigo&codigo_barras=$codigoBarras&id_pessoas_proprietario=$idPessoasProprietario&id_pessoas_fornecedor=$idPessoasFornecedor}");
				$btns .= $o->button("{icon: image; caption: Imagem; size: small; style: info; hint: Cadastro rápido de imagens; href: " . $o->page . "&gPage=" . IMAGENS_RAPIDO . "&gId=" . $row['id'] . "}");
				if ($row['ativo']==1) {
					$btns .= $o->button("{icon: thumbs-up; caption: Ativado; style: success; size: small; href: " . $o->page . "&gPage=" . ATIVAR_DESATIVAR . "&gId=" . $row['id'] . "&nome=$nome&codigo=$codigo&codigo_barras=$codigoBarras&id_pessoas_proprietario=$idPessoasProprietario&id_pessoas_fornecedor=$idPessoasFornecedor&ativo=" . $row['ativo'] . "&pesquisaRapida=$pesquisaRapida" . "}");
				} else {
					$btns .= $o->button("{icon: thumbs-down; caption: Desativado; style: danger; size: small; href: " . $o->page . "&gPage=" . ATIVAR_DESATIVAR . "&gId=" . $row['id'] . "&nome=$nome&codigo=$codigo&codigo_barras=$codigoBarras&id_pessoas_proprietario=$idPessoasProprietario&id_pessoas_fornecedor=$idPessoasFornecedor&ativo=" . $row['ativo'] . "&pesquisaRapida=$pesquisaRapida" . "}");
				}
				$mtz[] = $btns;
			}

			if (gDBCheck($_REQUEST['mostrarDetalhesSku'])) {
				$mtz[] = '<-' . gCheck($row['ativo']);
			}

			$mtz[] = '<-' . $row['codigo'];
			if (gDBCheck($_REQUEST['mostrarDetalhesSku'])) {
				$mtz[] = '<-' . $row['unidade'];
			}

			$mtz[] = '<-' . $row['nome'];
			$mtz[] = '<-' . $o->small($row['descricao']);
			$mtz[] = '<-' . $row['codigo_barras'];
			$mtz[] = '<-' . $row['proprietario'];
			$mtz[] = '<-' . $row['fornecedor'];
			if (gDBCheck($_REQUEST['mostrarDetalhesSku'])) {
				$mtz[] = '->' . gFloat($row['peso_liquido']);
				$mtz[] = '->' . gFloat($row['peso_bruto']);
				$mtz[] = '<-' . $row['codigo_barras_1'];
			}
			$html .= $o->tableRow($mtz, 'detail');
		}
		$html .= $o->tableEnd();
		$html .= $o->msg("Total de itens: " . count($rs));
		break;


	case DADOS:
		if ($gId) {
			$html .= mostraCabecalho();
			$registroAtual = $persistencia->obtemRegistros("i.id = " . $gId)[0];
		}

		$frm = new gForm();

		$campoCodigo = '';
		$campoCodigoBarras = '';
		if (!$gId) {
			$campoCodigo = $frm->add("{name: codigo; fieldLabel: Código *; type: upperText; value: ".$registroAtual['codigo']."; allowBlank: false;}");
			$campoCodigoBarras = $frm->add("{name: codigo_barras; fieldLabel: Código de barras; type: upperText; value: ".$registroAtual['codigo_barras']."}");
		}

		$frm->row(
			$frm->add("{name: nome; fieldLabel: Nome *; type: upperText; value: " . $registroAtual['nome'] . "; allowBlank: false;}"),
			$frm->add("{name: descricao; fieldLabel: Descrição; type: text; value: " . $registroAtual['descricao'] . "}"),
			$campoCodigo,
			$campoCodigoBarras
		);

		$frm->row(
			$frm->add("{name: id_pessoas_proprietario; fieldLabel: Cliente *; allowBlank: false; type: combo; value: " . $registroAtual['id_pessoas_proprietario'].  "; items: " . $sp['combo_clientes'] . "; allowBlank: false;}"),
			$frm->add("{name: id_pessoas_fornecedor; fieldLabel: Fornecedor; allowBlank: true; type: combo; value: " . $registroAtual['id_pessoas_fornecedor'] . "; items: " . $sp['combo_fornecedores'] . "}"),
			$frm->add("{name: id_grupos; fieldLabel: Grupo; type: combo; value: " . $registroAtual['id_grupos'] . "; items: " . $sp['combo_grupos'] . "}"),
			$frm->add("{name: id_tipos; fieldLabel: Tipo; allowBlank: false; type: combo; value: " . $registroAtual['id_tipos'] . "; items: " . $sp['combo_tipos'] . "}")
		);

		$comboGrupo = "SELECT id, CONCAT(codigo, ' • ', descricao) descricao FROM grupos_combustivel";
		$frm->row(
			$frm->add("{name: id_grupos_combustivel; fieldLabel: Grupo do combustível; type: combo; items:" . $comboGrupo . "; value: " . $registroAtual['id_grupos_combustivel'] . "}"),
			$frm->add("{name: ncm; fieldLabel: NCM*; type: text; maxLength: 8; value: " . $registroAtual['ncm'] . "; allowBlank: false;}")
		);

		$frm->row(
			$frm->add("{name: ativo; fieldLabel: Ativo; type: checkbox; value: " . ($gId==0 ? 1 : $registroAtual['ativo']) . "}")
		);

		$observacoes = decodificarObservacao($registroAtual['observacoes']);

		$frm->row(
			$frm->add("{name: observacoes; type: textarea; value: " . $observacoes . "}")
		);

		$frm->add("{name: gId;type: hidden; value: ".$gId."}");
		$frm->add("{name: gPage; type: hidden; value: " . DADOS_SALVAR . "}");

		$html .= $frm->render($o);

		$html .= $o->msg("* Campos obrigatórios para tornar o item apto para utilização.");

		break;


	/* ----------------------------- TUNNEL > SALVAR ------------------ */
	case DADOS_SALVAR:

		if ($gId == 0) {
			$ok  = $persistencia->insere($_REQUEST);
			$gId = $ok;
			userLog('Item adicionado id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		} else {
			$ok = $persistencia->modifica($_REQUEST, $gId);
			userLog('Item modificado id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		}

		if ($ok) {
			redirect($o->page.'&gPage='.DADOS.'&gId='.$gId);
		} else {
			$html .= $o->msgDanger(implode("<br>",$persistencia->erros));
			$html .= $o->backButton;
		}

		break;


	/* ----------------------------- SKU ------------------------ */
	case SKUS:
		$html .= mostraCabecalho();
		if ($gIdd == 0) {
			$sql = "SELECT ik.*, u.descricao unidade
					FROM itens_skus ik
					LEFT JOIN unidades u ON ik.id_unidades=u.id
					WHERE ik.id_itens = $gId";
		} else {
			$sql = "SELECT ik.*, u.descricao unidade
					FROM itens_skus ik
					LEFT JOIN unidades u ON ik.id_unidades=u.id
					WHERE ik.id = $gIdd";
		}
		$rs = dbQuery($sql);
		$row2 = $rs[0];
		$gIdd = $row2['id'];

		$frm = new gForm();

		$frm->row(
			$frm->add("{name: nome; fieldLabel: Nome; type: upperText; value:".$row2['nome']."}"),
			$frm->add("{name: codigo; fieldLabel: Código *; allowBlank: false; type: upperText; value: ".$row2['codigo']."}"),
			$campoDatasul,
			$frm->add("{name: codigo_barras; fieldLabel: Cód. de barras 1 *; allowBlank: false; type: upperText; value: ".$row2['codigo_barras']."}"),
			$frm->add("{name: codigo_barras_alternativo; fieldLabel: Cód. de barras 2; type: upperText; value: ".$row2['codigo_barras_alternativo']."}")
		);

		$frm->row(
			$frm->add("{name: id_unidades; fieldLabel: Unidade; allowBlank: false; type: combo; value: ".$row2['id_unidades']."; items: ".$sp['combo_unidades']."}"),
			$frm->add("{name: quantidade; fieldLabel: Quantidade; type: number; value: ".gFloat($row2['quantidade'])."}"),
			$frm->add("{name: peso_liquido; fieldLabel: Peso líquido; type: number; value: ".gFloat($row2['peso_liquido'])."}"),
			$frm->add("{name: peso_bruto; fieldLabel: Peso bruto; type: number; value: ".gFloat($row2['peso_bruto'])."}")
		);

		$frm->row(
			$frm->add("{name: largura; fieldLabel: Largura (cm); type: number; value: ".gFloat($row2['largura'])."}"),
			$frm->add("{name: comprimento; fieldLabel: Comprimento (cm); type: number; value: ".gFloat($row2['comprimento'])."}"),
			$frm->add("{name: altura; fieldLabel: Altura (cm); type: number; value: ".gFloat($row2['altura'])."}"),
			$frm->add("{name: valor; fieldLabel: Valor; type: number; value: ".gFloat($row2['valor'])."}"),
			$frm->add("{name: ativo; fieldLabel: Ativo; type: checkbox; value: ".$row2['ativo']."}")
		);

		$frm->add("{name: gPage;type: hidden; value: ".SKUS_SALVAR."}");
		$frm->add("{name: gId;type: hidden; value: ".$gId."}");
		$frm->add("{name: gIdd;type: hidden; value: ".($gIdd ?: $row2['id'])."}");
		$frm->addButton("{icon: box; style: default; title: Novo SKU; href: ".$o->page."&gPage=".SKUS."&gId=".$gId."&gIdd=-1}");
		$html.=$frm->render($o);

		$sql = "
			SELECT ik.*, u.descricao unidade
			FROM itens_skus  ik
			LEFT JOIN unidades u ON ik.id_unidades=u.id
			WHERE ik.id_itens=$gId ORDER BY ik.id";
		$rs = dbQuery($sql);
		$o->out($o->modal("{title: Confirme; size: small; content: Excluir este registro?; okCaption: Excluir agora; name: confirmaExclusao; url: excluirRegistro()}"), gLOC_INLINE, 999);

		$html .= $o->msgSubTitle('SKUs deste item');

		$html .= $o->tableBegin("big", true);

		$mtz = [];
		$mtz[] = "<-Opções";
		$mtz[] = "->Id";
		$mtz[] = "<>Ativo";
		$mtz[] = "<-Código";
		$mtz[] = "<-Cód.Barras";
		$mtz[] = "<-Cód.Barras 2";
		$mtz[] = "<-Nome";
		$mtz[] = "->Qtd";
		$mtz[] = "<-Unidade";
		$mtz[] = "->P.Líquido";
		$mtz[] = "->P.Bruto";
		$mtz[] = "->Alt.SKU";
		$mtz[] = "->Valor";
		$html .= $o->tableRow($mtz, "header");
		foreach ($rs as $id=>$row) {
			$mtz = [];
			$btns = $o->button("{icon: pencil; caption: Editar;size: tiny; style: default; href: " . $o->page . "&gPage=" . SKUS . "&gId=" . $gId . "&gIdd=" . $row['id'] . "}");
			if ($id > 0) {
				// Verifica se o SKU já foi utilizado. Só permite excluir se nunca foi utilizado
				$sql = "
					SELECT notas_itens.id
					FROM notas_itens
					LEFT JOIN notas ON notas.id = notas_itens.id_notas
					WHERE notas.cancelada = 0
						AND notas_itens.id_itens_skus = ".$row['id']." LIMIT 1;";
				$existeNotaComSku = dbQuery($sql);

				if (!$existeNotaComSku) {
					$btns .= $o->button("{icon: trash; caption: Excluir; style: danger; size: tiny; openModal: confirmaExclusao; }", "javascript:gIdd='" . $row['id'] . "'");
				}
			}

			$mtz[] = "<-" . $btns;
			$mtz[] = "->" . $row["id"];
			$mtz[] = "<>" . gCheck($row["ativo"]);
			$mtz[] = "<-" . $row["codigo"];
			$mtz[] = "<-" . $row["codigo_barras"];
			$mtz[] = "<-" . $row["codigo_barras_alternativo"];
			$mtz[] = "<-" . $row["nome"];
			$mtz[] = "->" . gFloat($row["quantidade"]);
			$mtz[] = "<-" . $row["unidade"];
			$mtz[] = "->" . gFloat($row["peso_liquido"]);
			$mtz[] = "->" . gFloat($row["peso_bruto"]);
			$mtz[] = "->" . str_replace(",0000","",gFloat($row["altura"]))."cm";
			$mtz[] = "->" . gFloat($row["valor"]);
			if ($gIdd == $row['id']) {
				$html .= $o->tableRow($mtz, "detail", "style='border: 4px solid #fe6600'");
			} else {
				$html .= $o->tableRow($mtz, "detail");
			}
		}

		$html .= $o->tableEnd();

		$o->addJavascript('
			gIdd=0;
			function excluirRegistro(){
				document.location.href="'.$o->page."&gPage=".SKUS_EXCLUIR."&gId=$gId&gIdd=".'"+gIdd;
			}'
		);

		break;


	case SKUS_SALVAR:
		$flds = [];
		$flds['id_itens'] = $gId;
		$flds['ativo'] = gDBCheck($_REQUEST['ativo']);
		$flds['codigo'] = gCleanField($_REQUEST['codigo']);
		$flds['codigo_barras'] = gCleanField($_REQUEST['codigo_barras']);
		$flds['codigo_barras_alternativo'] = gCleanField($_REQUEST['codigo_barras_alternativo']);
		$flds['nome'] = gCleanField($_REQUEST['nome']);
		$flds['id_unidades'] = intval($_REQUEST['id_unidades']);
		$flds['quantidade'] = gDBFloat($_REQUEST['quantidade']);
		$flds['peso_liquido'] = gDBFloat($_REQUEST['peso_liquido']);
		$flds['peso_bruto'] = gDBFloat($_REQUEST['peso_bruto']);
		$flds['largura'] = gDBFloat($_REQUEST['largura']);
		$flds['altura'] = gDBFloat($_REQUEST['altura']);
		$flds['comprimento'] = gDBFloat($_REQUEST['comprimento']);
		$flds['valor'] = gDBFloat($_REQUEST['valor']);

		$validar = $persistencia->validarSKU();
		if ($validar) {
			$html.=$o->msgDanger("Erros de validação: ".$o->ul($validar));
			$html.=$o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: info; size: normal; href: ".$o->page . "&gPage=" . SKUS . "&gId=" . $gId);
			return;
		}
		if ($gIdd) {
			$flds['data_alteracao']     = agora();
			$flds['id_pessoas_alterou'] = $usrId;

			dbUpdate("itens_skus", $flds, $gIdd);
			userLog('SKU alterado no item id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		} else {
			$flds['data_cadastro']    = agora();
			$flds['id_pessoas_criou'] = $usrId;
			$gIdd = dbInsert("itens_skus", $flds, true);
			userLog('SKU adicionado ao item id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		}

		redirect($o->page.'&gPage='.SKUS.'&gId='.$gId."&gIdd=".$gIdd);
		break;


	case SKUS_EXCLUIR:
		$sql = "DELETE FROM itens_skus WHERE id_itens=$gId and id=$gIdd";
		dbQuery($sql);
		userLog('SKU removido do item id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		redirect($o->page.'&gPage='.SKUS.'&gId='.$gId."&gIdd=".$gIdd);
		break;


	case IMAGENS_RAPIDO:
		$sql = "SELECT
					a.*,
					p.nome criou,
					I.nome,
					SK.codigo
				FROM itens_anexos a
				LEFT JOIN pessoas p on (a.id_pessoas_criou=p.id AND p.cliente=0)
				LEFT JOIN itens I ON a.id_itens=I.id
				LEFT JOIN itens_skus SK ON I.id=SK.id_itens
				WHERE a.id_itens=" . $gId ." ORDER BY a.descricao";
		$rs = dbQuery($sql);

		$html .= $o->msg($o->big($rs[0]['codigo'])."<br>".$rs[0]['nome']);

		$frm = new gForm("{columns: 2}");
		$frm->add("{name: descricao; fieldLabel: Descrição da imagem; type: upperFirstLetterText; }");
		$frm->add("{name: arquivo; type: file; }");
		$frm->add("{name: gPage; type: hidden; value: ".IMAGENS_SALVAR."}");
		$frm->add("{name: gId; type: hidden; value: $gId}");
		$frm->addButton("{icon: camera; title: Usar webcam; hint: Utilizar a webcam; style: primary; size: small; href:javascript:;;}", "javascript:solicitarCam();");
		$frm->buttonNextCaption = gT('Incluir');
		$html .= $frm->render($o);

		$html .= $o->msgWarning("O tamanho máximo permitido para a inclusão de arquivos é de 4Mb");

		if ($rs) {
			$httpUsrFiles.='anexos/';
			$gPathUsrFiles.='anexos/';
			$o->out($o->modal("{title: Confirme; size: small; content: Excluir este arquivo?; okCaption: Excluir agora; name: confirmaExclusao; url: excluiItem()}"), gLOC_INLINE, 999);
			$html.='<div class="row">';
			$id = 0;
			foreach ($rs as $row) {
				if ($id<>$row['id']) {
					$ext = substr((string) $row['arquivo'],strpos((string) $row['arquivo'],'/')+1);
					if ($ext == "") {
						$ext = "jpg";
					}
					$imgName = $row['id'].'.'.$ext;
					$arquivo = $httpUsrFiles . $imgName;
					$html.='<div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center">';
					$html.='<a href="'.$arquivo.'"><img class="img img-thumbnail img-responsive" src="'.$arquivo.'"></img></a><br>';
					$html.=$row['descricao'].'<br>';
					$html.=$o->small(gDateTime($row['data'])).'<br>';
					$html.=$o->small($row['criou'])."<br>";
					$html.=$o->button("{icon: trash; caption: Excluir; style: danger; size: small; openModal: confirmaExclusao; }", "javascript:gIda='" . $row['id'] . "'");
					$html.='<br>&nbsp;</div>';
				}
				$id = $row['id'];
			}
			$html.='</div>';
			$o->addJavascript('gIda=0;function excluiItem(){document.location.href="'.$o->page."&gPage=42&gId=$gId&gIda=".'"+gIda;}');
		}

		$o->out('<script src="' . $http_lib . gVar("lib.webcamjs"). 'webcam.min.js"></script>', gLOC_POS,2);
		$html.='
			<table>
				<tr>
					<td>
						<div id="my_camera" style="width:320px; height:240px;"></div>
					</td>
					<td>&nbsp;</td>
					<td>
						<div id="my_result" style="display: inline" class="img img-thumbnail"></div>
					</td>
				</tr>
			</table>';
		$html .= $o->button("{id:capturarImagem; icon: camera; caption: Capturar imagem; showWait:false;}","javascript:take_snapshot()");
		$js = "
			$('#capturarImagem').attr('style', 'display:none');
			function solicitarCam()
			{
				$('#capturarImagem').attr('style', '');
				abrirCam();
			}

			function abrirCam()
			{
				Webcam.attach( '#my_camera' );
			}

			function take_snapshot() {
				Webcam.snap( function(data_uri) {
					document.getElementById('my_result').innerHTML = '<img src=\"'+data_uri+'\"/>';
					Webcam.upload( data_uri, 'index.php?g=itens&gPage=".IMAGENS_UPLOAD."&gId=".$gId."', function(code, text) {
						bootbox.alert('Foto salva no cadastro do item');
					} );
				} );
			}
		";
		$o->addJavascript($js);
		break;


	/* ----------------------------- IMAGENS ------------------------ */
	case IMAGENS:
		$html .= mostraCabecalho();

		$sql = "
			SELECT a.*, p.nome criou
			FROM itens_anexos a
			LEFT JOIN pessoas p on (a.id_pessoas_criou=p.id AND p.cliente=0)
			WHERE a.id_itens = {$gId}
			ORDER BY a.descricao";
		$rs = dbQuery($sql);

		$html .= $o->msgWarning("O tamanho máximo permitido para a inclusão de arquivos é de 4Mb");

		$frm = new gForm();
		$frm->add("{name: gPage; type: hidden; value: 41}");
		$frm->add("{name: gId; type: hidden; value: $gId}");
		$frm->add("{name: descricao; type: upperFirstLetterText; }");
		$frm->add("{name: arquivo; type: file; }");

		$frm->addButton("{icon: camera; title: Usar webcam; hint: Utilizar a webcam; style: primary; size: small; href:javascript:;;}", "javascript:solicitarCam();");
		$frm->buttonNextCaption=gT('Incluir');
		$html .= $frm->render($o);

		if ($rs) {
			$httpUsrFiles.='anexos/';
			$gPathUsrFiles.='anexos/';
			$o->out($o->modal("{title: Confirme; size: small; content: Excluir este arquivo?; okCaption: Excluir agora; name: confirmaExclusao; url: excluiItem()}"), gLOC_INLINE, 999);
			$html.='<div class="row">';
			foreach ($rs as $row) {
				$ext = substr((string) $row['arquivo'],strpos((string) $row['arquivo'],'/')+1);
				if ($ext == "") {
					$ext = "jpg";
				}

				$imgName = $row['id'].'.'.$ext;
				$arquivo = $httpUsrFiles . $imgName;
				$html.='<div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center">';
				$html.='<a href="'.$arquivo.'"><img class="img img-thumbnail img-responsive" src="'.$arquivo.'"></img></a><br>';
				$html.=$row['descricao'].'<br>';
				$html.=$o->small(gDateTime($row['data'])).'<br>';
				$html.=$o->small($row['criou'])."<br>";
				$html.=$o->button("{icon: trash; caption: Excluir; style: danger; size: small; openModal: confirmaExclusao; }", "javascript:gIda='" . $row['id'] . "'");
				$html.='<br>&nbsp;</div>';
			}
			$html.='</div>';
			$o->addJavascript('gIda=0;function excluiItem(){document.location.href="'.$o->page."&gPage=42&gId=$gId&gIda=".'"+gIda;}');
		}


		$o->out('<script src="' . $http_lib . gVar("lib.webcamjs"). 'webcam.min.js"></script>', gLOC_POS,2);
		$html.='
		<table>
		<tr>
		<td>
		<div id="my_camera" style="width:320px; height:240px;"></div>
		</td>
		<td>&nbsp;</td>
		<td>
		<div id="my_result" style="display: inline" class="img img-thumbnail"></div>
		</td>
		</tr>
		</table>';
		$html.=$o->button("{id:capturarImagem; icon: camera; caption: Capturar imagem; showWait:false;}","javascript:take_snapshot()");
		$js = "
		$('#capturarImagem').attr('style', 'display:none');
		function solicitarCam()
		{
			$('#capturarImagem').attr('style', '');
			abrirCam();
		}

		function abrirCam()
		{
			Webcam.attach( '#my_camera' );
		}

		function take_snapshot() {
			Webcam.snap( function(data_uri) {
				document.getElementById('my_result').innerHTML = '<img src=\"'+data_uri+'\"/>';
				Webcam.upload( data_uri, 'index.php?g=itens&gPage=".IMAGENS_UPLOAD."&gId=".$gId."', function(code, text) {
					bootbox.alert('Foto salva no cadastro do item');
				} );
			} );
		}
		";
		$o->addJavascript($js);
		break;


	case IMAGENS_SALVAR:
		$erros='';
		$tamanhoMaximo=4000000;
		$arquivo = $_FILES['arquivo'] ?? FALSE;
		if ($gId == 0) {
			$sql = "SELECT I.id
					FROM itens I
					LEFT JOIN itens_skus SK ON I.id=SK.id_itens
					WHERE codigo='".strtoupper((string) $_REQUEST['codigo'])."'";
			$rst = dbQuery($sql);
			$gId = intval($rst[0]['id_itens']);
		}

		if (!$arquivo || $arquivo['name'] == '') {
			redirect($o->page."&gPage=".IMAGENS."&gId=".$gId);
		}

		if ($arquivo['error'] == 1) {
			$html.=$o->msgDanger("Houve um erro ao salvar o arquivo");
			$html.=$o->msg("Verifique se o tamanho do arquivo é inferior ao limite, se existe permissão na pasta para salvá-lo e se o tipo de arquivo é compatível.");
			$html .= $backButton;
			break;
		}
		// Verifica tamanho do arquivo
		if ($arquivo['size'] > $tamanhoMaximo) {
			$html .= $o->msgDanger("Houve um erro ao salvar o arquivo");
			$html .= $o->msg("Arquivo em tamanho muito grande! A imagem deve ser de no máximo ' . $tamanhoMaximo . ' bytes. Envie outro arquivo...");
			$html .= $backButton;
			break;
		}

		$flds = [
			'data'             => agora(),
			'id_itens'         => $gId,
			'id_pessoas_criou' => $usrId,
			'descricao'        => gCleanField($_REQUEST['descricao']),
			'arquivo'          => $arquivo['type']
		];
		$gPathUsrFiles.='anexos/';
		$ext = substr((string) $arquivo['type'],strpos((string) $arquivo['type'],'/')+1);
		if ($ext == "") {
			$ext = "jpg";
		}
		if (!is_dir($gPathUsrFiles)) {
			mkdir($gPathUsrFiles,0755);
		}

		$id = dbInsert('itens_anexos', $flds, true);
		$imgName = $id.'.'.$ext;
		$ok = move_uploaded_file($arquivo['tmp_name'], $gPathUsrFiles . $imgName);
		gLog("===> Arquivo salvo: ".$gPathUsrFiles . $imgName . " (".$arquivo['tmp_name'].")");
		chmod($gPathUsrFiles . $imgName, 0644); // evita ação de hackers
		userLog('Imagem adicionada ao item id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		redirect($o->page."&gPage=".IMAGENS."&gId=".$gId);

		break;


	case IMAGENS_EXCLUIR:
		$rs = dbQuery("SELECT * FROM itens_anexos WHERE id=".intval($_REQUEST['gIda']));
		if ($rs) {
			$gPathUsrFiles.='anexos/';
			$imgName = $_REQUEST['gIda'].'.'.substr((string) $rs[0]['arquivo'],strpos((string) $rs[0]['arquivo'],'/')+1);
			unlink($gPathUsrFiles.$imgName);
			dbQuery("DELETE FROM itens_anexos WHERE id=".intval($_REQUEST['gIda']));
			userLog('Imagem excluída do item id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		}

		redirect($o->page."&gPage=".IMAGENS."&gId=".$gId);
		break;


	case IMAGENS_UPLOAD:
		if (isset($_FILES['webcam']['tmp_name'])) {
			if ($gId == 0) {
				$sql = "SELECT I.id
						FROM itens I
						LEFT JOIN itens_skus SK ON I.id=SK.id_itens
						WHERE codigo='".strtoupper((string) $_REQUEST['codigo'])."'";
				$rst = dbQuery($sql);
				$gId = intval($rst[0]['id_itens']);
			}

			/* Reculperando arquivo */
			$arquivo=$_FILES['webcam'];
			$flds = [
				'data'             => agora(),
				'id_itens'         => $gId,
				'id_pessoas_criou' => $usrId,
				'descricao'        => 'Imagem do item',
				'arquivo'          => $arquivo['type']
			];
			$gPathUsrFiles.='anexos/';
			$ext = substr((string) $arquivo['type'],strpos((string) $arquivo['type'],'/')+1);
			if ($ext == "") {
				$ext = "jpg";
			}

			if (!is_dir($gPathUsrFiles)) {
				mkdir($gPathUsrFiles,0755);
			}
			$id = dbInsert('itens_anexos', $flds, true);
			$imgName = $id.'.'.$ext;
			move_uploaded_file($arquivo['tmp_name'], $gPathUsrFiles . $imgName);
			chmod($gPathUsrFiles . $imgName, 0644); // evita ação de hackers
			userLog('Imagem adicionada pela webcam ao item id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		}
		exit;
		break;


	case ATIVAR_DESATIVAR:
		$sql = "UPDATE itens SET ativo=1-ativo,data_alteracao = NOW(),id_pessoas_alterou=".$usrId." WHERE id=".$gId;
		dbQuery($sql);
		if ($_REQUEST['ativo'] == 1) {
			userLog('Desativou item id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		} else {
			userLog('Ativou item id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		}

		redirect($o->page."&gPage=".INICIO_PESQUISAR_RESULTADO."&nome=".$_REQUEST['nome']."&codigo=".$_REQUEST['codigo']."&codigo_barras=".$_REQUEST['codigo_barras']."&id_pessoas_proprietario=".$_REQUEST['id_pessoas_proprietario']."&id_pessoas_fornecedor=".$_REQUEST['id_pessoas_fornecedor']."&pesquisaRapida=".$_REQUEST['pesquisaRapida']);
		break;


	case COPIAR:

		$nome = gCleanField($_REQUEST['nome']);
		$codigo = gCleanField($_REQUEST['codigo']);
		$codigoBarras = gCleanField($_REQUEST['codigo_barras']);
		$idPessoasProprietario = intval($_REQUEST['id_pessoas_proprietario']);
		$idPessoasFornecedor = intval($_REQUEST['id_pessoas_fornecedor']);
		$situacao = intval($_REQUEST['situacao']);
		$idGrupos = intval($_REQUEST['id_grupos']);
		$idTipos = intval($_REQUEST['id_tipos']);

		$html .= $o->msgSubTitle("Cópia de itens");
		$frm   = new gForm("{columns: 2}");
		$frm->add("{name: novo_id_pessoas_proprietario; allowBlank:false; fieldLabel: Novo proprietário;type: combo; items: " . $sp['combo_clientes'] . "}");
		$frm->add("{name: novo_id_pessoas_fornecedor; fieldLabel: Novo fornecedor;type: combo; items: " . $sp['combo_fornecedores'] . "}");

		$frm->add("{name: formulario; type: hidden; value: $formulario}");
		$frm->add("{name: nome; type: hidden; value: $nome}");
		$frm->add("{name: codigo; type: hidden; value: $codigo}");
		$frm->add("{name: codigo_barras; type: hidden; value: $codigoBarras}");
		$frm->add("{name: id_pessoas_proprietario; type: hidden; value: $idPessoasProprietario}");
		$frm->add("{name: id_pessoas_fornecedor; type: hidden; value: $idPessoasFornecedor}");
		$frm->add("{name: situacao; type: hidden; value: $situacao}");
		$frm->add("{name: id_grupos; type: hidden; value: $idGrupos}");
		$frm->add("{name: id_tipos; type: hidden; value: $idTipos}");
		$frm->add("{name: gPage; type: hidden; value: " . COPIAR_SALVAR . "}");
		$frm->add("{name: gId; type: hidden; value: " . $gId . "}");
		$html .= $frm->render($o);

		break;


	case COPIAR_SALVAR:

		$html .= $o->msgSubTitle("Cópia de itens");

		//Dados da pesquisa
		$nome = gCleanField($_REQUEST['nome']);
		$codigo = gCleanField($_REQUEST['codigo']);
		$codigoBarras = gCleanField($_REQUEST['codigo_barras']);
		$idPessoasProprietario = intval($_REQUEST['id_pessoas_proprietario']);
		$idPessoasFornecedor = intval($_REQUEST['id_pessoas_fornecedor']);
		$idGrupos = intval($_REQUEST['id_grupos']);
		$idTipos = intval($_REQUEST['id_tipos']);
		$situacao = intval($_REQUEST['situacao']);
		//Dados a serem alterados no insert
		$novoIdPessoasProprietario = (int) $_REQUEST['novo_id_pessoas_proprietario'];
		$novoIdPessoasFornecedor = intval($_REQUEST['novo_id_pessoas_fornecedor']);

		if (!$novoIPpessoaPproprietario) {
			$html .= $o->msgWarning("Insira o novo proprietário para o item");
			$html .= $backButton;
			break;
		}

		$where = [];

		if ($nome <> '') {
			if (intval($_REQUEST['formulario'])==1) {
				// Se veio a partir do formulário completo, usa o campo nome só pro item
				$where[] = "(i.nome = '{$nome}')";
			} else {
				// Se veio a partir da pesquisa rápida, filtra também pelo nome do item ou da empresa
				$where[] = "(i.nome = '{$nome}' OR p.nome = '{$nome}' OR pf.nome = '{$nome}')";
			}
		}

		if ($codigo <> '') {
			$where[] = "i.codigo = '{$codigo}'";
		}

		if ($codigoBarras <> '') {
			$where[] = "i.codigo_barras = '{$codigoBarras}'";
		}

		if ($idPessoasProprietario) {
			$where['id_pessoas_proprietario'] = "i.id_pessoas_proprietario = {$idPessoasProprietario}";
		}

		if ($idPessoasFornecedor) {
			$where['id_pessoas_fornecedor'] = "i.id_pessoas_fornecedor = {$idPessoasFornecedor}";
		}

		if ($idGrupos) {
			$where[] = "i.id_grupos = {$idGrupos}";
		}

		if ($idTipos) {
			$where[] = "i.id_tipos = {$idTipos}";
		}

		$comboCondicional = [
			0 => false, // *Indiferente
			1 => 1, // Opcao "Sim"
			2 => 0 // Opcao "Nao"
		];

		if ($situacao) {
			$where[] = "i.ativo = " . $comboCondicional[$situacao];
		}

		if ($gId>0) {
			$where = '';
			$where[] = "i.id = {$gId}";
		}

		if (is_array($where)) {
			$novoDadoItem = [
				'id_pessoas_proprietario' => $novoIdPessoasProprietario,
				'id_pessoas_fornecedor'   => $novoIdPessoasFornecedor,
				'apto' => 0
			];
			$persistencia->copiarItem($where, $novoDadoItem);
			$html .= $o->msgWarning("Avisos: " . $o->ul($persistencia->avisos));

			if ($novoDadoItem['id_pessoas_proprietario']) {
				$where['id_pessoas_proprietario'] = "i.id_pessoas_proprietario = " . (int) $novoDadoItem['id_pessoas_proprietario'];
			}

			if ($novoDadoItem['id_pessoas_fornecedor']) {
				$where['id_pessoas_fornecedor']   = "i.id_pessoas_fornecedor = "   . (int) $novoDadoItem['id_pessoas_fornecedor'];
			}

			$sql = "
				SELECT
					i.id AS id_itens,
					sk.id AS id_itens_skus,
					sk.ativo AS sku_ativo,
					CONCAT(i.codigo, ' - ', i.nome, ' (', sk.quantidade, 'x<b>', u.sigla, '</b>)') AS descricao_item,
					proprietario.nome AS proprietario,
					i.data_cadastro
				FROM itens i
				LEFT JOIN itens_skus sk ON sk.id_itens = i.id
				LEFT JOIN unidades u ON u.id = sk.id_unidades
				LEFT JOIN pessoas proprietario ON proprietario.id = i.id_pessoas_proprietario
				WHERE  " . implode(' AND ', $where)
				. " ORDER BY descricao_item, sk.ativo DESC, i.data_cadastro";
			$rs  = dbQuery($sql);

			$html .= $o->msgSubTitle('Itens a partir da cópia');
			$html .= $o->tableBegin('big', true);

			$mtz = [];
			$mtz[] = '<>' . 'Opções';
			$mtz[] = '<-' . 'Item';
			$mtz[] = '<>' . 'Ativo';
			$mtz[] = '<-' . 'Proprietário';
			$mtz[] = '<>' . 'Data cadastro';
			$html .= $o->tableRow($mtz, 'header');

			foreach ($rs as $row) {
				$mtz = [];
				$botoes = $o->button("{icon: folder; caption: Abrir; size: small; href: " . $o->page . "&gPage=" . SKUS . "&gId=" . $row['id_itens'] . "&gIdd=" . $row['id_itens_skus'] . "; target: _blank;}");
				$mtz[] = '<>' . $botoes;
				$mtz[] = '<-' . $row['descricao_item'];
				$mtz[] = '<>' . gCheck($row['sku_ativo']);
				$mtz[] = '<-' . $row['proprietario'];
				$mtz[] = '<>' . gDateTime($row['data_cadastro']);

				$html .= $o->tableRow($mtz, 'detail');
			}

			$html .= $o->tableEnd();
		}
		break;


	case IMPORTAR:
		include 'res/_classes/padrao/importacoes.php';
		$imp = new Importacoes();
		$html .= $imp->processar("itens");
		break;


	case IMPORTACOES_DIVERSAS:

		$html .= $o->button("{icon: download; caption: Itens;  style: primary; size: big; href: " . $o->page . "&gPage=" . IMPORTAR . "}");

		if ($gParam['CONVERTER_SKU_AO_IMPORTAR_NF']['ativo']) {
			$html .= $o->button("{icon: download; caption: Importar fornecedores; style: primary; size: big; href: " . $o->page . "&gPage=" . FORMULARIO_IMPORTACAO_DE_FORNECEDORES . "}");
			$html .= $o->button("{icon: download; caption: Itens kits em lote; style: primary; size: big; href: " . $o->page . "&gPage=" . FILTRO_IMPORTAR_ITENS_KIT . "}");
		}
		break;


	case ATUALIZAR_EM_LOTE:
		$html .= $o->msgSubTitle("Atualização em lote");
		$form  = new gForm('{columns: 3}');
		$form->add("{allowBlank:true; type: combo; fieldLabel: Unidade; name:id_unidades; items:" . $sp["combo_unidades"] . ";}");
		$form->add("{type: text; fieldLabel: Nome do item; name:nome;}");
		$form->add("{type: text; fieldLabel: Código; hint: Separar com vígula; name: codigo;}");
		$form->add("{type: combo; fieldLabel: Proprietário; name:id_pessoas_proprietario; items:" . $sp["combo_clientes"] . "}");
		$form->add("{type: combo; fieldLabel: Grupo; name:id_grupos; items:" . $sp["combo_grupos"] . " }");
		$form->add("{type: combo; fieldLabel: Tipo; name:id_tipos; items:" . $sp["combo_tipos"] . ";}");
		$form->add("{type: combo; fieldLabel: Fornecedor; name:id_pessoas_fornecedor; items:" . $sp["combo_fornecedores"] . ";}");
		$form->add("{type: combo; fieldLabel: Ativo; name: ativo; allowbank: true; items:{'Sim','Não'}}");
		$form->add("{type: hidden; fieldLabel:; name:gPage; value:" . ATUALIZAR_EM_LOTE_PESQUISAR . ";}");
		$html .= $form->render($o);
		break;


	case ATUALIZAR_EM_LOTE_PESQUISAR:
		$html .= $o->msgSubTitle('Atualização em lote');

		$where   = [];
		$filtros = [];

		if ($_REQUEST["id_unidades"]) {
			$filtros[] = "Unidade: " . gFieldById('unidades', $_REQUEST["id_unidades"], 'sigla');
			$where[] = " IK.id_unidades='" . $_REQUEST["id_unidades"] . "' ";
		}

		if ($_REQUEST["nome"]) {
			$filtros[] = "Nome do item: " . $_REQUEST["nome"];
			$where[] = " I.nome like '%" . $_REQUEST["nome"] . "%' ";
		}

		if ($_REQUEST["codigo"]) {
			$filtros[] = "Código do item: " . $_REQUEST["codigo"];
			$codigo = str_replace(' ', '', implode("','", explode(',', (string) $codigo)));
			$where[] = " (IK.codigo IN ('" . $codigo . "')) ";
		}

		if ($_REQUEST["id_pessoas_proprietario"]) {
			$filtros[] = "Proprietário: " . gFieldById('gfw_users', $_REQUEST["id_pessoas_proprietario"], 'name');
			$where[] = " I.id_pessoas_proprietario = '" . $_REQUEST["id_pessoas_proprietario"] . "'";
		}

		if ($_REQUEST['id_grupos']) {
			$filtros[] = "Grupo: " . gFieldById('grupos', $_REQUEST["id_grupos"], 'descricao');
			$where[] = " I.id_grupos = '" . $_REQUEST["id_grupos"] . "'";
		}

		if ($_REQUEST["id_tipos"]) {
			$filtros[] = "Tipo: " . gFieldById('tipos', $_REQUEST["id_tipos"], 'descricao');
			$where[] = " I.id_tipos = '" . $_REQUEST["id_tipos"] . "'";
		}

		if ($_REQUEST["id_pessoas_fornecedor"]) {
			$filtros[] = "Fornecedor: " . gFieldById('gfw_users', $_REQUEST["id_pessoas_fornecedor"], 'name');
			$where[] = " I.id_pessoas_fornecedor = '" . $_REQUEST["id_pessoas_fornecedor"] . "'";
		}

		$opcoesCombo = ['Sim' => 1, 'Não' => 0];

		if ($_REQUEST['ativo']) {
			$filtros[] = "Ativo: " . $_REQUEST['ativo'];
			$where[]   = " I.ativo = " . $opcoesCombo[$_REQUEST['ativo']];
		}

		$where = implode(" AND ", $where);
		$where = $where ? "WHERE" . $where : "";

		$sql = "SELECT
					I.ativo,
					IK.id, IK.largura, IK.comprimento, P.nome fornecedor,
					I.nome, IK.codigo, IK.quantidade, IK.peso_liquido, IK.peso_bruto, U.descricao un_descricao, T.descricao ti_descricao, G.descricao gr_descricao, IK.altura,
					I.id AS id_item
				FROM itens_skus IK
				JOIN itens I ON IK.id_itens = I.id
				LEFT JOIN unidades U ON IK.id_unidades = U.id
				LEFT JOIN grupos G ON G.id = I.id_grupos
				LEFT JOIN pessoas P ON P.id = I.id_pessoas_fornecedor
				LEFT JOIN tipos T ON I.id_tipos = T.id
				{$where}
				ORDER BY I.nome ASC";
		$itens = dbQuery($sql);

		if ($filtros) {
			$html .= $o->msgFilter("Filtros selecionados: " . implode(' • ', $filtros));
		}

		if (!$itens) {
			$html .= $o->msgWarning("Nenhum registro encontrado a partir dos filtros selecionados");
			$html .= $backButton;
			break;
		}

		$frm = new gForm('{columns: 3}');
		$frm->row(
			$frm->add('{type: number; name: quantidade; fieldLabel: Quantidade; value:;}'),
			$frm->add('{type: number; name: peso_bruto; fieldLabel: Peso bruto; value:;}'),
			$frm->add('{type: number; name: peso_liquido; fieldLabel:Peso líquido; value:;}')
		);
		$frm->row(
			$frm->add('{type: number; name:largura; fieldLabel:Largura; value:;}'),
			$frm->add('{type: number; name:comprimento; fieldLabel:Comprimento; value:;}'),
			$frm->add('{type: number; name:altura; fieldLabel:Altura (cm); value:;}')
		);

		$padraoCombo = [];
		$padraoCombo["0"] = "* Indiferente";
		$padraoCombo["1"] = "SIM";
		$padraoCombo["2"] = "NÃO";

		$frm->row(
			$frm->add("{name: id_pessoas_fornecedor; fieldLabel: Fornecedor; type: combo; items: " . $sp['combo_fornecedores' ] . "}")
		);

		$comboAtivarItem=[];
		$comboAtivarItem["0"] = "* Indiferente";
		$comboAtivarItem["1"] = "SIM";
		$comboAtivarItem["2"] = "NÃO";

		$frm->row(
			$frm->add("{name:item_ativar; fieldLabel:Ativo; type:combo; items:'" . json_encode($comboAtivarItem) . "';}")
		);

		$frm->row(
			$frm->add("{type: combo; fieldLabel: Grupo; name:id_grupo; items:" . $sp["grupos"] . ";}"),
			$frm->add("{type: combo; fieldLabel: Tipo; name:id_tipo; items:" . $sp["combo_tipos"] . ";}"),
			$frm->add("{type: combo; fieldLabel: Unidade; name:id_unidades; items:" . $sp["combo_unidades"] . ";}"),
		);

		$frm->add('{type: hidden; name: gPage; value:' . CONFIRMAR_ATUALIZAR_EM_LOTE_PESQUISAR . ';}');
		$stringItens = implode('|', array_column($itens, 'id'));
		$frm->add('{type: hidden; name:itens_json; value:' . $stringItens . ';}');
		$frm->addButton("{icon: arrow-left; title: Voltar; hint: Voltar a página anterior; style: default; href: " . $o->page . "&gPage=" . ATUALIZAR_EM_LOTE . ";}");
		$html .= $frm->render($o);

		$html .= $o->msg("Itens que serão alterados: ");

		$html .= $o->tableBegin("big", true);

		$mtz   = [];
		$mtz[] = "<>Ativo";
		$mtz[] = "<-Nome";
		$mtz[] = "<-Fornecedor";
		$mtz[] = "<-Unidade";
		$mtz[] = "<-Tipo";
		$mtz[] = "<-Grupo";
		$mtz[] = "<-Código";
		$mtz[] = "->Quantidade SKU";
		$mtz[] = "->Peso bruto";
		$mtz[] = "->Peso líquido";
		$mtz[] = "->Largura";
		$mtz[] = "->Comprimento";
		$mtz[] = "->Altura (cm)";
		$html .=  $o->tableRow($mtz, "header");

		foreach ($itens as $item) {
			$mtz = [];
			$mtz[] = "<>" . gCheck($item["ativo"]);
			$mtz[] = "<-" . $item["nome"];
			$mtz[] = "<-" . $item["fornecedor"];
			$mtz[] = "<-" . $item["un_descricao"];
			$mtz[] = "<-" . $item["ti_descricao"];
			$mtz[] = "<-" . $item["gr_descricao"];
			$mtz[] = "<-" .  "<a href='" . $o->page."&gPage=" . DADOS . "&gPage=10&gId=" . $item["id_item"] . "'>" . $item["codigo"] . "</a>";
			$mtz[] = "->" . gFloat($item["quantidade"]);
			$mtz[] = "->" . gFloat($item["peso_bruto"]);
			$mtz[] = "->" . gFloat($item["peso_liquido"]);
			$mtz[] = "->" . gFloat($item["largura"]);
			$mtz[] = "->" . gFloat($item["comprimento"]);
			$mtz[] = "->" . gFloat($item["altura"]);
			$html .= $o->tableRow($mtz, "detail");
		}
		$html .= $o->tableEnd();
		break;


	case CONFIRMAR_ATUALIZAR_EM_LOTE_PESQUISAR:
		$mtz = [];
		if ($_REQUEST["quantidade"]) {
			$mtz["quantidade"] = gDBFloat($_REQUEST["quantidade"]);
		}

		if ($_REQUEST["peso_liquido"]) {
			$mtz["peso_liquido"] = gDBFloat($_REQUEST["peso_liquido"]);
		}

		if ($_REQUEST["peso_bruto"]) {
			$mtz["peso_bruto"] = gDBFloat($_REQUEST["peso_bruto"]);
		}

		if ($_REQUEST["largura"]) {
			$mtz["largura"] = gDBFloat($_REQUEST["largura"]);
		}

		if ($_REQUEST["comprimento"]) {
			$mtz["comprimento"] = gDBFloat($_REQUEST["comprimento"]);
		}

		if ($_REQUEST["altura"]) {
			$mtz["altura"] = gDBFloat($_REQUEST["altura"]);
		}

		if ($_REQUEST["id_unidades"]) {
			$mtz["id_unidades"] = $_REQUEST["id_unidades"];
		}

		$mtzItem = [];
		if ($_REQUEST["id_grupo"]) {
			$mtzItem["id_grupos"] = $_REQUEST["id_grupo"];
		}

		if ($_REQUEST["id_tipo"]) {
			$mtzItem["id_tipos"] = $_REQUEST["id_tipo"];
		}

		if ($_REQUEST["item_ativar"]) {
			$ativo = ($_REQUEST["item_ativar"] == 2)
				? 0
				: 1;
			$mtzItem["ativo"] = $ativo;
			$mtzItem["apto"] = $ativo;
			$mtz["ativo"] = $ativo;
		}

		if ($_REQUEST['id_pessoas_fornecedor']) {
			$mtzItem['id_pessoas_fornecedor'] = (int) $_REQUEST['id_pessoas_fornecedor'];
		}

		$itens = explode("|", (string) $_REQUEST["itens_json"]);
		foreach ($itens as $item) {
			if (!empty($item) && !is_null($item)) {

				$idItens=gFieldById("itens_skus", $item, "id_itens");

				if (count($mtzItem)>0) {
					dbUpdate("itens", $mtzItem, $idItens);
				}

				if (count($mtz)>0) {
					dbUpdate("itens_skus", $mtz, $item);
				}
			}
		}
		redirect($o->page."&gPage=".FINALIZOU_ATUALIZAR_EM_LOTE_PESQUISAR);
		break;


	case FINALIZOU_ATUALIZAR_EM_LOTE_PESQUISAR:
		$html .= $o->msgSuccess("Itens atualizados com sucesso");
		$html .= $o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: info; size: normal; href: " . $o->page . "&gPage=" . ATUALIZAR_EM_LOTE);
		break;


	case FORNECEDORES:
		$html .= mostraCabecalho();
		$html .= $o->msg("Tabela de conversão de códigos para importação de XML de fornecedor para o cliente");

		$frm = new gForm();

		$sql = "SELECT ik.id,
					CONCAT(
						ik.codigo, ' - ', i.nome,
						' (', ik.quantidade, 'x', u.sigla, ')',
						' - ', proprietario.apelido
					) nome
				FROM itens_skus ik
				LEFT JOIN unidades u ON ik.id_unidades=u.id
				LEFT JOIN itens i ON ik.id_itens=i.id
				JOIN pessoas proprietario ON proprietario.id = i.id_pessoas_proprietario
				WHERE i.ativo=1
					AND i.id={$gId}
					AND ik.ativo=1
				ORDER BY i.nome, ik.quantidade";

		$frm->row(
			$frm->add("{name: cnpj; fieldLabel: CNPJ; type: text; value: ".($row2['cnpj'])."; maxLength: 14;"),
			$frm->add("{name: codigo; fieldLabel: Código; type: text; value: ".($row2['codigo'])."}"),
			$frm->add("{name: id_itens_skus; fieldLabel: SKU; type: combo; value:".$row2['id_itens_skus']."; items: ".$sql."}")
		);
		$frm->add("{name: gPage;type: hidden; value: ".($gPage+1)."}");
		$frm->add("{name: gId;type: hidden; value: ".$gId."}");
		$frm->add("{name: gIdd;type: hidden; value: ".$gIdd."}");
		$html.=$frm->render($o);

		$sql = "SELECT f.id,
					f.cnpj,
					f.codigo,
					CONCAT(ik.codigo,' - ',i.nome, ' (',ik.quantidade,'x', u.sigla,')') nome
				FROM itens_fornecedores f
				JOIN itens_skus ik ON f.id_itens_skus = ik.id
				JOIN itens i ON ik.id_itens=i.id
				JOIN unidades u ON ik.id_unidades=u.id
				WHERE f.id_itens = {$gId}";
		$rs = dbQuery($sql);
		if (!$rs[0]['id']) {
			$html .= $o->msgInfo("Este item não tem códigos para conversão cadastrados");
			break;
		}

		$o->addJavascript('
			gIda = 0;
			function excluirRegistro()
			{
				document.location.href="' . $o->page . "&gPage=" . (FORNECEDORES+2)
					. "&gId=$gId&gIdd=$gIdd&gIda=".'"+gIda;
			}
		');

		$o->out($o->modal("{title: Confirme; size: small; content: Excluir este registro?; okCaption: Excluir agora; name: confirmaExclusao; url: excluirRegistro()}"), gLOC_INLINE, 999);

		$html .= $o->tableBegin("big", true);

		$mtz = [];
		$mtz[]="<-Opções";
		$mtz[]="->Nº";
		$mtz[]="<-CNPJ";
		$mtz[]="<-Código";
		$mtz[]="<-SKU";
		$html.=$o->tableRow($mtz, "header");

		foreach ($rs as $chave=>$row) {
			$mtz = [];
			$mtz[] = "<-" . $o->button("{icon: trash; caption: Excluir; style: danger; size: tiny; openModal: confirmaExclusao; }", "javascript:gIda='" . $row['id'] . "'");
			$mtz[]="->".($chave+1);
			$mtz[]="<-".$row["cnpj"];
			$mtz[]="<-".$row["codigo"];
			$mtz[]="<-".$row["nome"];
			$html.=$o->tableRow($mtz, "detail");
		}
		$html.=$o->tableEnd();

		break;

	case CADASTRAR_FORNECEDOR:
		if ($_REQUEST['cnpj']<>'' && $_REQUEST['codigo']<>'' && $_REQUEST['id_itens_skus']>0) {
			$flds = [];
			$flds['id_itens']=$gId;
			$flds['id_itens_skus']=intval($_REQUEST['id_itens_skus']);
			$flds['codigo']=gCleanField($_REQUEST['codigo']);
			$flds['cnpj']=preg_replace('/[^0-9]+/i ', '', (string) $_REQUEST['cnpj']);
			dbInsert("itens_fornecedores", $flds);
			userLog('Código de fornecedor '.$flds['codigo'].' à estrutura do item id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		}
		redirect($o->page.'&gPage='.FORNECEDORES.'&gId='.$gId."&gIdd=".$gIdd);
		break;


	case EXCLUIR_FORNECEDOR:
		$sql = "DELETE FROM itens_fornecedores WHERE id_itens=$gId AND id=".intval($_REQUEST['gIda']);
		dbQuery($sql);
		userLog('Código de fornecedor removido da estrutura do item id <a href="index.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		redirect($o->page.'&gPage='.FORNECEDORES.'&gId='.$gId);
		break;


	case FORMULARIO_IMPORTACAO_DE_FORNECEDORES:
		$html .= $o->msgSubTitle("Importação de fornecedores");

		$frm   = new gForm("{columns: 2}");
		$frm->add("{name: fornecedores; fieldLabel: Arquivo CSV;type: file;}");
		$frm->add("{name: gPage; type: hidden; value: " . IMPORTACAO_DE_FORNECEDORES . "}");

		$modelo = 'CNPJ,Código do Fornecedor,Item SKU,Unidade do item';
		$frm->addButton("{icon: download; title: Baixar modelo CSV; hint: Baixar modelo CSV; style: info; size: normal; href: " . $o->page . "&gPage=" . IMPRIMIR_MODELO . "&modelo=" . $modelo);
		$html .= $frm->render($o);

		$html .= $o->msgInfo("Observação, antes de importar o arquivo CSV verifique se: <br>
			<br> - Importe CSV sem título
			<br> - O arquivo tem alguma linha vazia ou espaços no nome dos itens
			<br> - Verificar se todas as colunas foram preenchidas corretamente
			<br> - Verificar se o item ja foi cadastrado");
		break;


	case IMPORTACAO_DE_FORNECEDORES:
		$html .= $o->msgSubTitle("Importação de fornecedores");
		if ($_FILES['fornecedores']['type'] != 'text/csv') {
			$html .= $o->msgDanger('Importar somente arquivos CSV');
			$html .= $backButton;
			break;
		}

		$conteudoCsv = file_get_contents($_FILES['fornecedores']['tmp_name']);

		$conteudoCsv = explode("\n", $conteudoCsv);

		$novosSkus = [];
		$values = [];
		foreach ($conteudoCsv as $chave => $linha) {

			$numeroLinha = $chave + 1;

			if (empty($linha) || trim(str_replace(';', '', $linha)) === '') {
				continue;
			}

			$linha = explode(';', $linha);

			$codigo  = str_replace(' ', '', gCleanField($linha[2]));
			if (!$codigo) {
				$erros[] = 'Linha ' . $numeroLinha . ' está com código vazio';
			}

			$unidade = str_replace(' ', '', gCleanField($linha[3]));
			if (!$unidade) {
				$erros[] = 'Linha ' . $numeroLinha . ' está com unidade vazia';
			}

			$cnpj = str_replace(' ', '', gCleanField($linha[0]));
			$sql = "SELECT id FROM pessoas_juridicas WHERE cnpj = '" . $cnpj . "'";
			$fornecedor  = dbQuery($sql)[0];
			if (!$fornecedor) {
				$erros[] = 'Linha ' . $numeroLinha . ' apresenta CNPJ não cadastrado no sistema: ' . $cnpj;
			}

			$codigoFornecedor = str_replace(' ', '', gCleanField($linha[1]));
			if (!$codigoFornecedor) {
				$erros[] = 'Código do fonecedor está vazio na linha ' . $numeroLinha;
			}

			$sql = "SELECT itens_skus.id, itens_skus.id_itens
					FROM itens_skus
					JOIN unidades ON unidades.id = itens_skus.id_unidades
					WHERE (codigo = '" . $codigo . "'
						OR codigo_barras = '" . $codigo . "')
						AND unidades.sigla = '" . $unidade . "'
					ORDER by ativo DESC";
			$sku = dbQuery($sql)[0];

			if (!$sku) {
				$sql = "SELECT id_itens
						FROM itens_skus
						JOIN unidades ON unidades.id = itens_skus.id_unidades
						WHERE (codigo = '" . $codigo . "'
							OR codigo_barras = '" . $codigo . "')
						ORDER by ativo DESC";
				$idItem = dbQuery($sql)[0]['id_itens'];

				if (!$idItem) {
					$html .= $o->msgDanger('O item ' . $codigo . ' da linha ' . $numeroLinha . ' não foi cadastrado');
					$html .= $backButton;
					break 2;
				}

				if (!$erros) {
					$idUnidade = dbQuery("SELECT id FROM unidades WHERE sigla = '{$unidade}'")[0]['id'];

					$dados['ativo'] = 0;
					$dados['id_itens'] = $idItem;
					$dados['id_unidades'] = $idUnidade;
					$dados['id_pessoas_criou'] = $_SESSION['usrId'];
					$dados['data_cadastro'] = date('Y-m-d');
					$dados['codigo'] = $codigo;
					$dados['codigo_barras'] = $codigo;
					$dados['quantidade'] = 1;
					$dados['largura'] = 1;
					$dados['altura'] = 1;
					$dados['comprimento'] = 1;
					$dados['comprimento'] = 1;

					$novosSkus[$codigo]['dados'] = $dados;// dados a inserir na tabela itens_skus
					$novosSkus[$codigo]['cnpjFornecedor'] = $cnpj;
					$novosSkus[$codigo]['codigoFornecedor'] = $codigoFornecedor;
					continue;
				}
			}

			$sql = "SELECT id
					FROM itens_fornecedores
					WHERE id_itens = '" . $sku['id_itens'] . "'
						AND id_itens_skus = '" . $sku['id'] . "'
						AND cnpj = '" . $cnpj . "'
						AND codigo = '" . $codigoFornecedor . "'";
			$itemFornecedorCadastrado = dbQuery($sql);

			if (!$itemFornecedorCadastrado && !$erros) {
				$values[] = "(" . ((int) $sku['id_itens']) . ", " . ((int) $sku['id']) . ", '" . $cnpj . "', '" . $codigoFornecedor . "')";
			}

			$html .= $backButton;
			break;
		}

		if ($erros) {
			$html .= $o->msgWarning("Não podemos prosseguir com o cadastro por causa dos seguintes erros:<br>" . implode("<br>", $erros));
			$html .= $backButton;
			break;
		}

		foreach ($novosSkus as $chave => $sku) {
			$idNovoSku = dbInsert('itens_skus', $sku['dados'], true);
			$idItens   = (int) gFieldById('itens_skus', $idNovoSku , 'id_itens');
			$values[]  = "(" . $idItens . ", " . $idNovoSku . ", '" . $sku['cnpjFornecedor']. "', '" . $sku['codigoFornecedor'] . "')";
		}

		if ($values) {
			$sql = "INSERT INTO itens_fornecedores (id_itens, id_itens_skus, cnpj, codigo) VALUES " . implode(',', $values);
			dbQuery($sql);
		}

		$novosSkus = implode(',', array_keys($novosSkus));
		redirect($o->page . "&gPage=" . CONFIRMAÇÃO_IMPORTACAO_DE_FORNECEDORES . "&novosSkus=" . $novosSkus);
		break;


	case CONFIRMAÇÃO_IMPORTACAO_DE_FORNECEDORES:
		$html .= $o->msgSubTitle("Importação de fornecedores");
		$html .= $o->msgSuccess("Importação realizada com sucesso");

		$novosSkus = explode(',', (string) $_REQUEST['novosSkus']);
		if ($novosSkus) {
			$html .= $o->msgInfo("Foram criados novos SKUS com as unidades que constam no arquivo importado para os seguintes itens:<br>");
			$skusExibir = '';

			$html .= $o->tableBegin('tiny', true);
			$mtz   = [];
			$mtz[] = '<-' . 'Código';
			$html .= $o->tableRow($mtz, 'header');

			foreach ($novosSkus as $sku) {
				$mtz   = [];
				$mtz[] = '<-' . linkParaCodigoItem($sku);
				$html .= $o->tableRow($mtz, 'detail');
			}
			$html .= $o->tableEnd();
		}

		$html .= $o->button("{icon:arrow-left; caption: Voltar; style: info; size: small; href: " . $o->page . "&gPage=" . FORMULARIO_IMPORTACAO_DE_FORNECEDORES . " ;}");;
		break;


	case IMPRIMIR_MODELO:
		downloadModeloImportacao($modelo, $_REQUEST['gId']);
		break;

}


function mostraCabecalho()
{

	global $o, $html, $gPage, $gId, $rs, $row, $gParam, $persistencia;

	if (!$gId) {
		return;
	}

	$sql = "SELECT
				i.*,
				p.nome cliente,
				pf.nome fornecedor,
				pc.nome criou,
				pa.nome alterou,
				g.descricao grupo,
				t.descricao tipo
			FROM itens i
			LEFT JOIN pessoas p ON (i.id_pessoas_proprietario = p.id and p.cliente = 1)
			LEFT JOIN pessoas pf ON (i.id_pessoas_fornecedor = pf.id AND pf.cliente = 0)
			LEFT JOIN pessoas pc ON (i.id_pessoas_criou = pc.id AND pc.cliente = 0)
			LEFT JOIN pessoas pa ON (i.id_pessoas_alterou = pa.id AND pa.cliente = 0)
			LEFT JOIN grupos g ON i.id_grupos = g.id
			LEFT JOIN tipos t ON i.id_tipos = t.id
			WHERE i.id = " . $gId;
	$rs = dbQuery($sql);
	if (!$rs) {
		$html .= $o->msgError("Erro ao localizar o item. Pode ter sido excluído de forma inapropriada.");
		return;
	}

	$row = $rs[0];
	$cor = 'header';
	if ($row['ativo'] == 0) {
		$cor = "danger";
	}

	$html .= $o->tableBegin("big");
	$mtz = [];
	$mtz[] = '<-' . $o->small('Código') . '<br><b>' . $row['codigo'] . '</b><br>&nbsp;';
	$mtz[] = '<-' . $o->small('Empresa') . '<br><b>' . $row['cliente'] . '<br>' . $row['fornecedor'] . "</b>&nbsp;";
	$mtz[] = '<-' . $o->small('Nome') . '<br><b>' . $row['nome'] . '<br><small>' . $row['descricao'] . '</small></b>&nbsp;';
	$mtz[] = '<-' . $o->small('Cadastro') . '<br><b>' . gDateTime($row['data_cadastro']) . "<br><small>" . $row['criou'] . "</small></b>&nbsp;";
	$mtz[] = '<-' . $o->small('Alteração') . '<br><b>' . gDateTime($row['data_alteracao']) . "<br><small>" . $row['alterou'] . "</small></b>&nbsp;";
	$html .= $o->tableRow($mtz, $cor);

	$mtz = [];
	if ($row['apto'] == 1) {
		$mtz[] = '~6<>Cadastro do item suficientemente completo - pode ser utilizado';
		$html .= $o->tableRow($mtz, 'success');
	} else {
		$mtz[] = '~6<>Cadastro do item sem dados suficientes - não poderá ser utilizado';
		$html .= $o->tableRow($mtz, 'danger');
	}

	$html .= $o->tableEnd();

	$active1 = 'false';
	$active2 = 'false';
	$active3 = 'false';
	$active4 = 'false';
	$active5 = 'false';
	$active6 = 'false';
	$active7 = 'false';
	switch ($gPage) {
		case DADOS:
			$active1 = 'true';
			break;
		case SKUS:
			$active2 = 'true';
			break;
		case IMAGENS:
			$active5 = 'true';
			break;
		case FORNECEDORES:
			$active7 = 'true';
			break;
		case POSICAO_FIXA:
			$active8 = 'true';
			break;
	}

	$btns = [];

	$btns[] = $o->button("{active: ".$active1."; caption: Dados do item; icon: barcode-read; hint: Dados do item; responsive: true; href: ".$o->page."&gPage=".DADOS."&gId=".$gId."}");
	if (!$persistencia->aptoSKUs($gId)) {
		$caption = "SKUs&nbsp&nbsp" . $o->badge("1");
		$btns[] = $o->button("{active: " . $active2 . "; style:danger; caption: " . $caption . ";icon: box; hint: SKU; responsive: true; href: ".$o->page."&gPage=".SKUS."&gId=".$gId."}");
	} else {
		$btns[] = $o->button("{active: " . $active2 . "; caption: SKUs;icon: box; hint: SKU; responsive: true; href: ".$o->page."&gPage=".SKUS."&gId=".$gId."}");
	}

	$btns[] = $o->button("{active: ".$active5."; caption: Imagens; icon: image; hint: Imagens; responsive: true; href: ".$o->page."&gPage=".IMAGENS."&gId=".$gId."}");

	if ($gParam['CONVERTER_SKU_AO_IMPORTAR_NF']['ativo']) {
		$btns[] = $o->button("{active: ".$active7."; caption: Conversão SKUs; icon: random; hint: Relação de conversões de SKUs; responsive: true; href: ".$o->page."&gPage=".FORNECEDORES."&gId=".$gId."}");
	}

	$html .= implode(" ", $btns);
}
