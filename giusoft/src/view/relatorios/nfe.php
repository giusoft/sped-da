<?php

define('SELECIONAR_RELATORIO',      0);
define('INICIO_NFE_STATUS',         10);
define('PESQUISAR_NFE_STATUS',      20);
define('DOWNLOAD_XML',              30);
define('DOWNLOAD_XML_CANCELAMENTO', 40);

define('INICIO_NFE_EMITIDAS',       50);
define('PESQUISAR_NFE_EMITIDAS',    60);



$paginasPodeExportar = [PESQUISAR_NFE_STATUS, PESQUISAR_NFE_EMITIDAS];

if (in_array($gPage, $paginasPodeExportar)) {
	$o->PDFEnabled = true;
	$o->DOCEnabled = true;
	$o->XLSEnabled = true;
	$o->CSVEnabled = true;
}

$situacoesNfe = [];
$situacoesNfe[] = 'Aprovada';
$situacoesNfe[] = 'Assinada';
$situacoesNfe[] = 'Submetida';
$situacoesNfe[] = 'Cancelada';
$situacoesNfe[] = 'Reprovada';
$situacoesNfe[] = 'Importada';

$html .= $o->msgTitle("Relatório de notas fiscais");
switch($gPage) {
	case SELECIONAR_RELATORIO:

		$html .= $o->button("{title: NFe status; icon: file; style: primary; size: big; href: ".$o->page."&gPage=".INICIO_NFE_STATUS.";}");
		$html .= $o->button("{title: NFe Emitidas; icon: archive; style: primary; size: big; href: ".$o->page."&gPage=".INICIO_NFE_EMITIDAS.";}");


		break;

    case INICIO_NFE_STATUS:
        $html = $o->msgTitle("NFe status");

		$frm = new gForm("{columns: 2}");
		$frm->add('{type: combo; name: id_proprietario; fieldLabel: Proprietário; items: ' . $sp['combo_clientes'] . ';}');
		$frm->add('{type: text; name: numero_de; fieldLabel: Número de;}');
		$frm->add('{type: text; name: numero_ate; fieldLabel: Número até;}');
		$frm->add('{type: date; name: data_cadastro_de; fieldLabel: Data de cadastro de;}');
		$frm->add('{type: date; name: data_cadastro_ate; fieldLabel: Data de cadastro até;}');
		$frm->add('{name: id_filial; type: combo; fieldLabel: Filial;  value: 1; allowBlank: true; items:' . $sp['combo_filial'] . ';}');
		$frm->add('{name: situacao; type: comboMultiSelection; fieldLabel: Situação; allowBlank: true; value: 0, 1, 2;}', $situacoesNfe);
		$frm->add('{type: hidden; name: gPage; value: ' . PESQUISAR_NFE_STATUS . ';}');
		$html .= $frm->render($o);
		break;

        case PESQUISAR_NFE_STATUS:
		$where = [];
		$filtros = [];
		if ($_REQUEST["id_proprietario"]) {
			$filtros[] = "Proprietário: " . gFieldById("pessoas", $_REQUEST["id_proprietario"], "nome");
			$where[] = "nfe.id_cliente = '" . $_REQUEST["id_proprietario"] . "'";
		}

		if ($_REQUEST["data_cadastro_ate"]) {
			$filtros[] = "Data de cadastro até: " . $_REQUEST["data_cadastro_ate"];
			$where[] = "nfe.data <= '" . gDBDate($_REQUEST["data_cadastro_ate"]) . " 23:59:59'";
		}

		if ($_REQUEST["data_cadastro_de"]) {
			$filtros[] = "Data de cadastro de: " . $_REQUEST["data_cadastro_de"];
			$where[] = "nfe.data >= '" . gDBDate($_REQUEST["data_cadastro_de"]) . " 00:00:00'";
		}

		if ($_REQUEST["numero_de"]) {
			$filtros[] = "Número de: " . $_REQUEST["numero_de"];
			$where[] = "nfe.numero >= '" . gCleanField($_REQUEST["numero_de"]) . "'";
		}

		if ($_REQUEST["numero_ate"]) {
			$filtros[] = "Número até: " . $_REQUEST["numero_ate"];
			$where[] = "nfe.numero <= '" . gCleanField($_REQUEST["numero_ate"]) . "'";
		}

		if ($_REQUEST['situacao']) {
            $situacoes = '';
            $whereSituacaoImportada = '';
            foreach ($_REQUEST['situacao'] as $i) {
                if ($i == 5) { // 5 = importada (ou situação que identifica notas de terceiros)
                    $whereSituacaoImportada = " OR (nfe.situacao = 'Importada')";
                }

                $situacoes .= "'" . $situacoesNfe[$i] . "',";
            }

            $situacoes = substr($situacoes, 0, -1);
            $filtros[] = "Situações: " . str_replace("'", "", $situacoes);

			$where[] = "(nfe.situacao IN (" . $situacoes .") {$whereSituacaoImportada})";
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
					pessoa_emitiu.apelido AS colaborador_emitiu,
					nfe.cancelada,
					nfe.numero,
					SUBSTR(nfe_eventos.xml, 1, 1) AS tem_xml,
					nfe_eventos.sucesso,
					nfe_eventos.id_nfe_tipos_eventos,
					nfe.chave AS chave,
					nfe.situacao,
					nfe.id_notas,
					nfe.protocolo,
					nfe.data_cancelamento,
					pessoa_cancelou.apelido AS colaborador_cancelou
				FROM nfe
				LEFT JOIN nfe_eventos ON nfe_eventos.id_nfe = nfe.id AND nfe_eventos.id_nfe_tipos_eventos IN (1, 2, 5)
				LEFT JOIN pessoas pessoa_emitiu ON 	pessoa_emitiu.id = nfe.id_pessoa
				LEFT JOIN pessoas pessoa_cancelou ON pessoa_cancelou.id = nfe.id_pessoas_cancelou
				LEFT JOIN notas ON notas.id = nfe.id_notas
				WHERE {$where}
				ORDER BY nfe.numero";
		$rs = dbFastQuery($sql);

		if (!$rs) {
			$html .= $o->msgDanger('Nenhum registro encontrado a partir dos filtros selecionados');
			$html .= $backButton;
			break;
		}

		$html .= $o->tableBegin("big", true, true);
		$mtz = [];
		$mtz[] = '->Opções';
		$mtz[] = '->Id';
		$mtz[] = '<>Data cadastro';
		$mtz[] = '->Número';
		$mtz[] = '<-Situação';
		$mtz[] = '<-Chave';
		$mtz[] = '<-Resposta SEFAZ';
		$mtz[] = '->Protocolo';
		$mtz[] = '<-Nota';
		$mtz[] = '<-Colaborador emissor';
		$mtz[] = '<>Cancelada';
		$mtz[] = '<-Colaborador cancelou';
		$mtz[] = '<>Data cancelamento';
        $html .= $o->tableRow($mtz, "header-fixed");
		foreach ($rs as $key => $row) {
			$mtz = [];
			$botoes = '';
			if (in_array($row['id_nfe_tipos_eventos'], [1,5]) && $row['sucesso'] == 1) {
				$botoes .= $o->button("{icon: download; caption: XML enviado; style: default ; size: small; href: " . $o->page . "&gPage=" . DOWNLOAD_XML . "&gId=" . $row['id'] . "&atributo=xml; hint: Baixar xml enviado para SEFAZ; target: _blank;}");
			}

			if ($row['id_nfe_tipos_eventos'] == 2 && $row['sucesso'] == 1) {

				$botoes .= $o->button("{icon: download; caption: XML cancelamento; style: danger; size: small; href: " . $o->page . "&gPage=" . DOWNLOAD_XML_CANCELAMENTO . "&gId=" . $row['id'] . "&atributo=xml_cancelamento; hint: Baixar xml enviado para cancelamento; target: _blank;}");
			}

			$mtz[] = '->' . $botoes;
			$mtz[] = '->' . $row['id'];
			$mtz[] = '<>' . gDateTime($row['data_cadastro']);
			$mtz[] = '->' . $row['numero'];
			$mtz[] = '<-' . $row['situacao'];
			$mtz[] = '<-' . $row['chave'];
			$mtz[] = '<-' . $row['resposta'];
			$mtz[] = '->' . $row['protocolo'];
			$mtz[] = '<-' . linkParaNota($row['id_notas'], $row['numero'], ($row['situacao'] == 'Importada' ? 'E' : 'S'));
			$mtz[] = '<-' . $row['colaborador_emitiu'];
			$mtz[] = '<>' . gCheck($row['cancelada'], true);
			$mtz[] = '<-' . $row['colaborador_cancelou'];
			$mtz[] = '<>' . gDate($row['data_cancelamento']);

			$html .= $o->tableRow($mtz, "footer");
		}

		$html .= $o->tableEnd();
		break;


	case DOWNLOAD_XML:
        $sql = "SELECT nfe_eventos.xml, nfe.chave
                FROM nfe_eventos
                LEFT JOIN nfe ON nfe.id = nfe_eventos.id_nfe
                WHERE id_nfe = '{$gId}'";
        $rs = dbFastQuery($sql)[0];

        if (!$rs) {
            $html .= $o->msgDanger("Não foi possível fazer o download do xml pois aconteceram os seguintes erros: <br> NF-e não encontrada");
           	$html .= $backButton;
			break;
        }

        $formato = "xml";
        $file = $rs[$formato];
        $nome = $rs['chave'];
        $nome = $nome . "-nfe." . strtolower($formato);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $nome);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen((string) $file));
        ob_clean();
        flush();
        echo($file);
        exit;
        break;

    case DOWNLOAD_XML_CANCELAMENTO:
        $sql = "SELECT nfe_eventos.id, nfe_eventos.xml, nfe.chave
                FROM nfe_eventos
                LEFT JOIN nfe ON nfe.id = nfe_eventos.id_nfe
                WHERE id_nfe_tipos_eventos = 2 AND id_nfe = '{$gId}'"; // id_nfe_tipos_eventos = 2 (Evento de cancelamento)
        $rs = dbFastQuery($sql)[0];

        if (!$rs['id']) {
            $html .= $o->msgDanger("Não foi possível fazer o download do xml pois aconteceram os seguintes erros: <br> NF-e não encontrada");
           	$html .= $backButton;
            break;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $rs['chave'] . "-xml_cancelamento.xml");
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen((string) $rs['xml']));
        ob_clean();
        flush();
        echo($rs['xml']);
        exit;
        break;


    case INICIO_NFE_EMITIDAS:
        $html = $o->msgTitle("NFe emitidas");

		$frm = new gForm("{columns: 2}");
		$sql = "SELECT P.id, P.apelido
				FROM pessoas P
				WHERE P.situacao = 'Ativo'
					AND P.cliente = 1
					AND P.apelido <> ''
					GROUP BY P.id, P.apelido
				ORDER BY P.apelido";
		$frm->add('{type: combo; name: id_proprietario; fieldLabel: Proprietário; items:' . $sql . ';}');
		$frm->add('{type: combo; name: id_cfop; fieldLabel: CFOP; items:'.$sp["combo_cfop"].';}');
		$frm->add('{type: text; name: numero; fieldLabel: Número;}');
		$frm->add('{type: date; name: data_emissao_de; fieldLabel: Data de emissão de;}');
		$frm->add('{type: date; name: data_emissao_ate; fieldLabel: Data de emissão até;}');
		$frm->add('{type: combo; name: id_filial; fieldLabel: Filial;  value: 1; allowBlank: false; items:' . $sp['combo_filial'] . ';}');

		$frm->row(
			$frm->add("{name: exibirTotais; fieldLabel: Exibir totais; type: checkbox; value:1;}"),
			$frm->add('{name: exibir_canceladas; fieldLabel:Exibir canceladas?; type: checkbox;}'),
			$frm->add('{name: exibir_itens; fieldLabel:Exibir Itens?; type: checkbox;}')
		);

		$frm->add('{type:hidden; name:gPage; value:'.PESQUISAR_NFE_EMITIDAS.';}');

		$html .= $frm->render($o);
		break;


    case PESQUISAR_NFE_EMITIDAS:
		$where = [];
		$where[] = "(N.id > 0)";
		if (isset($_REQUEST["exibir_canceladas"])) {
			$where[] = "(NFE.situacao in ('Cancelada', 'Aprovada'))";
		} else {
			$where[] = "(NFE.situacao='Aprovada')";
		}

		if ($_REQUEST["id_proprietario"]) {
			$flt[] = "Proprietário: ".gFieldById("pessoas", $_REQUEST["id_proprietario"], "nome");
			$where[] = "(N.id_pessoas_proprietario='".intval($_REQUEST["id_proprietario"])."')";
		}

		if ($_REQUEST["id_cfop"]) {
			$flt[] = "CFOP = ".gFieldById("cfops", $_REQUEST["id_cfop"], 'codigodescricao');
			$where[] = "(N.id_cfops='".intval($_REQUEST["id_cfop"])."')";
		}

		if (!$_REQUEST["data_emissao_de"] && $_REQUEST["data_emissao_ate"]) {
			$flt[] = "Data de emissão até = ".$_REQUEST["data_emissao_ate"];
			$data_ate = date('Y-m-d', strtotime("+1 day", strtotime(gDBDate($_REQUEST["data_emissao_ate"]))));
			$where[] = sprintf("(N.data_emissao < '%s')", $data_ate);
		}

		if ($_REQUEST["data_emissao_de"] && !$_REQUEST["data_emissao_ate"]) {
			$flt[] = "Data de emissão de = ".$_REQUEST["data_emissao_de"];
			$data_de = date('Y-m-d', strtotime(gDBDate($_REQUEST["data_emissao_de"])));
			$where[] = sprintf("(N.data_emissao >= '%s')", $data_de);
		}

		if ($_REQUEST["data_emissao_de"] && $_REQUEST["data_emissao_ate"]) {
			$flt[] = "Data de emissão de = ".$_REQUEST["data_emissao_de"];
			$flt[] = "Data de emissão até = ".$_REQUEST["data_emissao_ate"];
			$data_de = date('Y-m-d', strtotime(gDBDate($_REQUEST["data_emissao_de"])));
			$data_ate = date('Y-m-d', strtotime("+1 day", strtotime(gDBDate($_REQUEST["data_emissao_ate"]))));
			$where[] = sprintf("(N.data_emissao>='%s' AND N.data_emissao <'%s')", $data_de, $data_ate);
		}

		if ($_REQUEST["numero"]) {
			$flt[] = "NF = ".$_REQUEST["numero"];
			$where[] = "N.numero='".gCleanField($_REQUEST["numero"])."'";
		}

		if (count($where)<=1) {
			$html.=$o->msgDanger("Informe ao menos um filtro antes de tentar gerar um relatório");
		} else {
			$where[] = "(N.id_filial=" . $_REQUEST['id_filial'] . ")";
			$where = implode(" AND ", $where);
			$sql = "SELECT
						N.*,
						C.codigo codigo_cfop,
						C.descricao_resumida cfop,
						PP.apelido proprietario,
						PP.nome nome_completo_proprietario,
						NFE.situacao,
						NFE.chave
					FROM notas N
					LEFT JOIN nfe NFE ON NFE.id_notas = N.id
					LEFT JOIN pessoas PP ON PP.id = N.id_pessoas_proprietario
					LEFT JOIN cfops C ON C.id = N.id_cfops
					WHERE {$where}
					ORDER BY N.data_emissao DESC";
			$rs = dbQuery($sql);
			$flt[] = "Motrar subtotais: " . gCheck($_REQUEST['exibirTotais']);
			$flt[] = "Motrar canceladas: " . gCheck($_REQUEST['exibir_canceladas']);
			$flt[] = "Motrar itens: " . gCheck($_REQUEST['exibir_itens']);
			$flt[] = "Apenas avulsas: " . gCheck($_REQUEST['nota_avulsa']);

			$html .= $o->msgFilter("Filtros selecionados: " . implode(" • ", $flt));
			if (!$rs) {
				$html .= $o->msgWarning("Nenhum registro encontrado com os filtros selecionados");
			} else {
				$html .= $o->tableBegin("big", true, true);
				$mtz = [];
		        $mtz[] = "<-Id";
		        $mtz[] = "<-Tipo";
		        $mtz[] = "<-Situação";
		        $mtz[] = "<-Chave                                             ";
		        $mtz[] = "<-Número        ";
		        $mtz[] = "<>Data de emissão";
		        $mtz[] = "<>Data de movimento";
		        $mtz[] = "->Quantidade";
		        $mtz[] = "->Peso bruto";
		        $mtz[] = "->Peso líquido";
		        $mtz[] = "->Valor total";
		        $colspan = count($mtz);
		        $html .= $o->tableRow($mtz,"header-fixed");
		        $idProprietario = 0;

		        /* Campos subtotal */
		        $sTotalPesoB = 0;
				$sTotalPesoL = 0;
				$sTotalValor = 0;
				$sTotalQuantidade = 0;
				$sTotalItens = 0;

				/* Campos total */
				$totalPesoB = 0;
				$totalPesoL = 0;
				$totalValor = 0;
				$totalQuantidade = 0;
				$totalItens = 0;

				foreach ($rs as $row) {
					if (
						$idProprietario != $row["id_pessoas_proprietario"]
						&& ((intval($sTotalValor) > 0) && gDBCheck($_REQUEST['exibirTotais']))
					) {
						$mtz = [];
						$mtz[] = "~7->Sub-total";
						$mtz[] = "->".gFloat($sTotalQuantidade);
						$mtz[] = "->".gFloat($sTotalPesoB);
						$mtz[] = "->".gFloat($sTotalPesoL);
						$mtz[] = "->".gFloat($sTotalValor);
						$html .= $o->tableRow($mtz, "footer");
						// Zerando o subtotal
						$sTotalPesoB = 0;
						$sTotalPesoL = 0;
						$sTotalValor = 0;
						$sTotalQuantidade = 0;
						$sTotalItens = 0;
					}

					if ($idProprietario != $row["id_pessoas_proprietario"]) {
						$idProprietario=$row["id_pessoas_proprietario"];
						$mtz = [];
						$mtz[] = '~' . $colspan.strtoupper((string) $row["nome_completo_proprietario"]) . " (". $row["proprietario"] .") ";
						$html.=$o->tableRow($mtz, "detail");
					}

					$descricaoCfop=$row["codigo_cfop"];
					if (!empty($row["cfop"])) {
						$descricaoCfop.=" - ".$row["cfop"];
					}

					$mtz = [];
					$mtz[] = "<-".$row["id"];
					$mtz[] = "<-".$row["tipo"];
			        $mtz[] = "<-".$row["situacao"];
			        $mtz[] = "<-".$row["chave"];
			        $mtz[] = "<-".linkParaNFESaida($row["numero"], 60);
			        $mtz[] = "<>".gDate($row["data_emissao"]);
			        $mtz[] = "<>".gDate($row["data_movimento"]);
			        $mtz[] = "->".gFloat($row["qVol"]);
			        $mtz[] = "->".gFloat($row["pesoB"]);
			        $mtz[] = "->".gFloat($row["pesoL"]);
			       	$mtz[] = "->".gFloat($row["vProd"]);

			        if (isset($_REQUEST['exibir_itens'])) {
			        	$html.=$o->tableRow($mtz,"header");
			        	$sql = "SELECT
									notas_itens.id,
									notas_itens.quantidade,
									notas_itens.valor,
									notas_itens.peso_bruto,
									notas_itens.peso_liquido,
									itens.descricao,
									itens_skus.codigo
								FROM notas_itens
								INNER JOIN itens_skus ON itens_skus.id = notas_itens.id_itens_skus
								INNER JOIN itens ON itens.id = itens_skus.id_itens
								WHERE notas_itens.id_notas=".(int) $row['id'];
						$rsi = dbQuery($sql);

						foreach ($rsi as $r) {
							$mtz = [];
							$mtz[] = "".$r['id'];
							$mtz[] = "~7<-".$r['codigo']." - ".$r['descricao'];
							$mtz[] = "->".gFloat($r['quantidade']);
							$mtz[] = "->".gFloat($r['peso_bruto']);
							$mtz[] = "->".gFloat($r['peso_liquido']);
							$mtz[] = "->".gFloat($r['valor']);
							$html .= $o->tableRow($mtz,'detail');
						}

						$html .= $o->tableLine();
			        } else {
						$html .= $o->tableRow($mtz,"detail");
					}

					if (gDBCheck($_REQUEST['exibirTotais'])) {
						/* Totais */
						$sTotalPesoB += $row["pesoB"];
						$sTotalPesoL += $row["pesoL"];
						$sTotalValor += $row["vProd"];
						$sTotalQuantidade += $row["qVol"];
						$sTotalItens = 0;

						$totalPesoB += $row["pesoB"];
						$totalPesoL += $row["pesoL"];
						$totalValor += $row["vProd"];
						$totalQuantidade += $row["qVol"];
						$totalItens = 0;
					}
				}

				if (gDBCheck($_REQUEST['exibirTotais'])) {
					/* Último subtotal */
					$mtz = [];
					$mtz[] = "~7->Sub-total";
					$mtz[] = "->".gFloat($sTotalQuantidade);
					$mtz[] = "->".gFloat($sTotalPesoB);
					$mtz[] = "->".gFloat($sTotalPesoL);
					$mtz[] = "->".gFloat($sTotalValor);
					$html .= $o->tableRow($mtz, "footer");

					/* Total */
					$mtz = [];
					$mtz[] = "~7->Total";
					$mtz[] = "->".gFloat($totalQuantidade);
					$mtz[] = "->".gFloat($totalPesoB);
					$mtz[] = "->".gFloat($totalPesoL);
					$mtz[] = "->".gFloat($totalValor);
					$html .= $o->tableRow($mtz, "footer");
				}

				$html .= $o->tableEnd();
			}
		}

		break;


}