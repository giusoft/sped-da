<?php

$debug = false;
if ($gPage == 10) {
	$o->PDFEnabled = true;
	$o->DOCEnabled = true;
	$o->XLSEnabled = true;
	$o->CSVEnabled = true;
}

$html .= $o->msgTitle("NFe emitidas");
switch($gPage) {
	case INICIO:
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
		$frm->add('{type: text; name: os; fieldLabel: OS;}');
		$frm->add('{type: text; name: numero; fieldLabel: Número;}');
		$frm->add('{type: date; name: data_emissao_de; fieldLabel: Data de emissão de;}');
		$frm->add('{type: date; name: data_emissao_ate; fieldLabel: Data de emissão até;}');
		$frm->add('{type: combo; name: id_filial; fieldLabel: Filial;  value: 1; allowBlank: false; items:' . $sp['combo_filial'] . ';}');

		$frm->row(
			$frm->add("{name: exibirTotais; fieldLabel: Exibir totais; type: checkbox; value:1;}"),
			$frm->add('{name: exibir_canceladas; fieldLabel:Exibir canceladas?; type: checkbox;}'),
			$frm->add('{name: exibir_itens; fieldLabel:Exibir Itens?; type: checkbox;}'),
			$frm->add("{name: nota_avulsa; fieldLabel: Apenas avulsas:; type: checkbox;")
		);

		$frm->add('{type:hidden; name:gPage; value:'.PESQUISAR.';}');

		$html .= $frm->render($o);
		break;


	case PESQUISAR:
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

		if ($_REQUEST["os"]) {
			$flt[] = "OS = ".$_REQUEST["os"];
			$where[] = "(PR.os like '%".gCleanField($_REQUEST["os"])."%')";
		}

		if (isset($_REQUEST["nota_avulsa"])) {
			$where[] = "(N.id_programacao=0)";
		}

		if (count($where)<=1) {
			$html.=$o->msgDanger("Informe ao menos um filtro antes de tentar gerar um relatório");
		} else {
			$where[]="(N.tipo='S')";
			$where[] = "(N.id_filial=" . $_REQUEST['id_filial'] . ")";
			$where = implode(" AND ", $where);
			$sql = "SELECT
						N.*,
						C.codigo codigo_cfop,
						C.descricao_resumida cfop,
						PP.apelido proprietario,
						PP.nome nome_completo_proprietario,
						PR.os,
						T.descricao tipo,
						NFE.situacao,
						NFE.chave
					FROM notas N
					LEFT JOIN nfe NFE ON NFE.id = N.id_nfe
					LEFT JOIN pessoas PP ON PP.id = N.id_pessoas_proprietario
					LEFT JOIN cfops C ON C.id = N.id_cfops
					LEFT JOIN programacao PR ON PR.id = N.id_programacao
					LEFT JOIN tipos_programacao T ON PR.id_tipos_programacao = T.id
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
		        $mtz[] = "<-OS";
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
						$mtz[] = "~8->Sub-total";
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
					$mtz[] = "<-".$row["os"];
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
					$mtz[] = "~8->Sub-total";
					$mtz[] = "->".gFloat($sTotalQuantidade);
					$mtz[] = "->".gFloat($sTotalPesoB);
					$mtz[] = "->".gFloat($sTotalPesoL);
					$mtz[] = "->".gFloat($sTotalValor);
					$html .= $o->tableRow($mtz, "footer");

					/* Total */
					$mtz = [];
					$mtz[] = "~8->Total";
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
