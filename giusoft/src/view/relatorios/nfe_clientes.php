<?php

define('INICIO', 0);
define('PESQUISAR', 1);

gVar("global.numformat","0.000,0000");

$debug = false;

if ($gPage == 10) {
	$o->PDFEnabled=true;
	$o->DOCEnabled=true;
	$o->XLSEnabled=true;
	$o->CSVEnabled=true;
}

$html .= $o->msgTitle("NFe de clientes");
switch ($gPage) {

	case INICIO:
		$frm = new gForm("{columns: 2}");
		$frm->add('{type:combo; name:id_proprietario; fieldLabel:Proprietário; items:'.$sp["combo_proprietarios"].';}');
		$frm->add('{type:text; name:numero; fieldLabel:Número;}');
		$frm->add('{type:date; name:data_importacao_de; fieldLabel:Data de importação de;}');
		$frm->add('{type:date; name:data_importacao_ate; fieldLabel:Data de importação até;}');
		$frm->add('{type:combo; name:id_cfop; fieldLabel:CFOP; items:'.$sp["combo_cfop"].';}');

		$frm->row(
			$frm->add("{name: exibirTotais; fieldLabel: Exibir totais; type: checkbox; value:1;}"),
			$frm->add('{type:checkbox; name:exibir_canceladas; fieldLabel:Exibir canceladas ?;}')
		);

		$frm->add('{type:hidden; name:gPage; value:'.PESQUISAR.';}');

		$html .= $frm->render($o);
		break;


	case PESQUISAR:
		$where = [];
		$where[] = "(N.id > 0)";
		if (!isset($_REQUEST["exibir_canceladas"])) {
			$where[]="(N.cancelada=0)";
		}

		if ($_REQUEST["id_proprietario"]) {
			$flt[]="Proprietário: ".gFieldById("pessoas", $_REQUEST["id_proprietario"], "nome");
			$where[]="(N.id_pessoas_proprietario='".intval($_REQUEST["id_proprietario"])."')";
		}

		if ($_REQUEST["id_cfop"]) {
			$flt[]="CFOP = ".gFieldById("cfops", $_REQUEST["id_cfop"], 'codigodescricao');
			$where[]="(N.id_cfops='".intval($_REQUEST["id_cfop"])."')";
		}

		if (!$_REQUEST["data_importacao_de"] && $_REQUEST["data_importacao_ate"]) {
			$flt[]="Data de importação até = ".$_REQUEST["data_importacao_ate"];
			$data_de=date('Y-m-d 23:59:59', strtotime(gDBDate($_REQUEST["data_importacao_de"])));
			$where[]=sprintf("(N.data_criou <= '%s')", $data_de);
		}

		if ($_REQUEST["data_importacao_de"] && !$_REQUEST["data_importacao_ate"]) {
			$flt[]="Data de importação de = ".$_REQUEST["data_importacao_de"];
			$data_ate=date('Y-m-d 00:00:00', strtotime(gDBDate($_REQUEST["data_emissao_de"])));
			$where[]=sprintf("(N.data_criou >= '%s')", $data_ate);
		}

		if ($_REQUEST["data_importacao_de"] && $_REQUEST["data_importacao_ate"]) {
			$flt[]="Data de importação de = ".$_REQUEST["data_importacao_de"];
			$flt[]="Data de importação até = ".$_REQUEST["data_importacao_ate"];
			$data_de=date('Y-m-d 00:00:01', strtotime(gDBDate($_REQUEST["data_importacao_de"])));
			$data_ate=date('Y-m-d 23:59:59', strtotime(gDBDate($_REQUEST["data_importacao_ate"])));
			$where[]=sprintf("(N.data_criou >= '%s' AND N.data_criou <= '%s')", $data_de, $data_ate);
		}

		if ($_REQUEST["numero"]) {
			$flt[]="NF = ".$_REQUEST["numero"];
			$where[]="N.numero='".gCleanField($_REQUEST["numero"])."'";
		}


		if (count($where) <= 1) {
			$html.=$o->msgDanger("Informe ao menos um filtro antes de tentar gerar um relatório");
		} else {
			$flt[] = "Motrar subtotais: " . gCheck($_REQUEST['exibirTotais']);
			$flt[] = "Motrar canceladas: " . gCheck($_REQUEST['exibir_canceladas']);
			$html .= $o->msgFilter("Filtros selecionados: " . implode(" • ", $flt));
			$where[] = "(N.tipo='E')";
			$where[] = "(N.id_filial='".intval($_SESSION["filialAtualId"])."')";
			$where = implode(" AND ", $where);
			$sql = "SELECT
						N.*,
						C.codigo codigo_cfop,
						C.descricao_resumida cfop,
						PP.apelido proprietario,
						PP.nome nome_completo_proprietario,
						NFE.situacao,
						I.codigo codigo_item,
						U.descricao unidade,
						SK.quantidade quantidade_sku,
						NI.id_itens_skus,
						SUM(NI.quantidade) quantidade,
						NI.valor,
						SK.peso_bruto,
						SK.peso_liquido
					FROM notas_itens NI
					LEFT JOIN itens_skus SK ON SK.id = NI.id_itens_skus
					LEFT JOIN itens I ON I.id = SK.id_itens
					LEFT JOIN unidades U ON U.id = SK.id_unidades
					LEFT JOIN notas N ON N.id = NI.id_notas
					LEFT JOIN nfe NFE ON NFE.id = N.id_nfe
					LEFT JOIN pessoas PP ON PP.id = N.id_pessoas_proprietario
					LEFT JOIN cfops C ON C.id = N.id_cfops
					WHERE {$where}
					GROUP BY N.id, NI.id_itens_skus
					ORDER BY PP.apelido ASC, N.numero ASC, N.data_emissao DESC";
			$rs = dbQuery($sql);

			if (!$rs) {
				$html .= $o->msgWarning("Nenhum registro encontrado com os filtros selecionados");
			} else {
				$html .= $o->tableBegin("big", true);
				$mtz = [];
		        $mtz[] = "<>Data Importação";
		        $mtz[] = "<-Item         ";
		        $mtz[] = "->Quantidade";
		        $mtz[] = "->Peso bruto";
		        $mtz[] = "->Peso líquido";
		        $mtz[] = "->Valor total";
		        $colspan = count($mtz);
		        $html .= $o->tableRow($mtz,"header-fixed");
		        $idProprietario = 0;
		        $idItem = 0;

		        /* Campos subtotal */
		        $sTotalPesoB = 0;
				$sTotalPesoL = 0;
				$sTotalValor = 0;
				$sTotalQuantidade = 0;
				$sTotalItens = 0;

				/* Campos subtotal */
		        $sTotalPesoBNota = 0;
				$sTotalPesoLNota = 0;
				$sTotalValorNota = 0;
				$sTotalQuantidadeNota = 0;
				$sTotalItensNota = 0;

				/* Campos total */
				$totalPesoB = 0;
				$totalPesoL = 0;
				$totalValor = 0;
				$totalQuantidade = 0;
				$totalItens = 0;

				$nf_numero = "";
				foreach ($rs as $row) {

					$valor=$row["valor"]*$row["quantidade"];
					$peso_bruto=$row["peso_bruto"]*$row["quantidade"];
					$peso_liquido=$row["peso_liquido"]*$row["quantidade"];

					if ($idProprietario != $row["id_pessoas_proprietario"]) {

						if ($sTotalQuantidadeNota > 0 && $_REQUEST['exibirTotais']) {
							/* Último subtotal */
							$mtz = [];
							$mtz[] = "~2->Sub-total: NF ".$nf_numero;
							$mtz[] = "->".gFloat($sTotalQuantidadeNota);
							$mtz[] = "->".gFloat($sTotalPesoBNota);
							$mtz[] = "->".gFloat($sTotalPesoLNota);
							$mtz[] = "->".gFloat($sTotalValorNota);
							$html .= $o->tableRow($mtz, "footer");

							/* Zerando o subtotal */
							$sTotalPesoBNota = 0;
							$sTotalPesoLNota = 0;
							$sTotalValorNota = 0;
							$sTotalQuantidadeNota = 0;
							$sTotalItensNota = 0;
						}

						if ((intval($sTotalQuantidade) > 0) && $_REQUEST['exibirTotais']) {
							$mtz = [];
							$mtz[] = "~2->Sub-total: ".$proprietario;
							$mtz[] = "->".gFloat($sTotalQuantidade);
							$mtz[] = "->".gFloat($sTotalPesoB);
							$mtz[] = "->".gFloat($sTotalPesoL);
							$mtz[] = "->".gFloat($sTotalValor);
							$html .= $o->tableRow($mtz, "footer");

							/* Zerando o subtotal */
							$sTotalPesoB = 0;
							$sTotalPesoL = 0;
							$sTotalValor = 0;
							$sTotalQuantidade = 0;
							$sTotalItens = 0;
						}
					}

					if ($idProprietario != $row["id_pessoas_proprietario"]) {
						$idProprietario = $row["id_pessoas_proprietario"];
						$proprietario = $row["proprietario"];
						$mtz = [];
						$mtz[] = sprintf('~%d<-', $colspan).strtoupper((string) $row["nome_completo_proprietario"]) . " (". $row["proprietario"] .") ";
						$html .= $o->tableRow($mtz, "detail");
					}

					if (
						$nf_numero != $row["numero"]
						&& ((intval($sTotalValorNota) > 0) && $_REQUEST['exibirTotais'])
					) {
						$mtz = [];
						$mtz[] = "~2->Sub-total NF: ".$nf_numero;
						$mtz[] = "->".gFloat($sTotalQuantidadeNota);
						$mtz[] = "->".gFloat($sTotalPesoBNota);
						$mtz[] = "->".gFloat($sTotalPesoLNota);
						$mtz[] = "->".gFloat($sTotalValorNota);
						$html .= $o->tableRow($mtz, "footer");
						/* Zerando o subtotal */
						$sTotalPesoBNota = 0;
						$sTotalPesoLNota = 0;
						$sTotalValorNota = 0;
						$sTotalQuantidadeNota = 0;
						$sTotalItensNota = 0;
					}

					if ($nf_numero != $row["numero"]) {
						$descNota=linkParaNFEntrada($row["numero"], 60);

						$mtz = [];
						$mtz[] = sprintf('~%d<-NF: %s', $colspan, $descNota);
						$html .= $o->tableRow($mtz, "detail");
						$nf_numero = $row["numero"];
					}

					$descricaoCfop = $row["codigo_cfop"];
					if (!empty($row["cfop"])) {
						$descricaoCfop .= " - ".$row["cfop"];
					}

					$sku = $row["codigo_item"]." • ".$row["unidade"]." com ".gFloat($row["quantidade_sku"])."           ";


					$mtz = [];
					$mtz[] = "<>".date("d-m-Y H:i", strtotime((string) $row["data_criou"]));
			        $mtz[] = "<-".$sku;
			        $mtz[] = "->".gFloat($row["quantidade"]);
			        $mtz[] = "->".gFloat($peso_bruto);
			        $mtz[] = "->".gFloat($peso_liquido);
			       	$mtz[] = "->".gFloat($valor);
			        $html .= $o->tableRow($mtz,"detail");

					if (gDBCheck($_REQUEST['exibirTotais'])) {
						/* Totais */
						$sTotalPesoB += $peso_bruto;
						$sTotalPesoL += $peso_liquido;
						$sTotalValor += $valor;
						$sTotalQuantidade += $row["quantidade"];
						$sTotalItens = 0;

						/* Totais */
						$sTotalPesoBNota += $peso_bruto;
						$sTotalPesoLNota += $peso_liquido;
						$sTotalValorNota += $valor;
						$sTotalQuantidadeNota += $row["quantidade"];
						$sTotalItensNota = 0;

						$totalPesoB += $peso_bruto;
						$totalPesoL += $peso_liquido;
						$totalValor += $valor;
						$totalQuantidade += $row["quantidade"];
						$totalItens = 0;
					}
				}

				if (gDBCheck($_REQUEST['exibirTotais'])) {
					// Último sub-total nota
					$mtz = [];
					$mtz[] = "~2->Sub-total: NF ".$nf_numero;
					$mtz[] = "->".gFloat($sTotalQuantidadeNota);
					$mtz[] = "->".gFloat($sTotalPesoBNota);
					$mtz[] = "->".gFloat($sTotalPesoLNota);
					$mtz[] = "->".gFloat($sTotalValorNota);
					$html .= $o->tableRow($mtz, "footer");

					// Último subtotal proprietário
					$mtz=[];
					$mtz[] = "~2->Sub-total: ".$row["proprietario"];
					$mtz[] = "->".gFloat($sTotalQuantidade);
					$mtz[] = "->".gFloat($sTotalPesoB);
					$mtz[] = "->".gFloat($sTotalPesoL);
					$mtz[] = "->".gFloat($sTotalValor);
					$html .= $o->tableRow($mtz, "footer");

					// Total
					$mtz = [];
					$mtz[] = "~2->Total";
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
