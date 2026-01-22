<?php

define("INICIO", 0);
define("FILTROS_CONSUMO_API", 1);
define("RELATORIO_CONSUMO_API", 2);

$paginasPodeExportar = [RELATORIO_CONSUMO_API];

if (in_array($gPage, $paginasPodeExportar)) {
    $o->PDFEnabled = true;
    $o->DOCEnabled = true;
    $o->XLSEnabled = true;
    $o->CSVEnabled = true;
}

switch ($gPage) {
	case INICIO:
        $html .= $o->msgTitle("API");

		$html .= $o->button("{title: Consumo de API; icon: exchange; style: primary; size: big; href: " . $o->page . "&gPage=" . FILTROS_CONSUMO_API . ";}");
	    break;


    case FILTROS_CONSUMO_API:
        $html .= $o->msgTitle("Consumo de API");

        $frm = new gForm("columns: 2");
        $frm->add("{type: combo; name: id_pessoas_proprietario; fieldLabel: Proprietário; items: ".$sp['combo_clientes']."; value: " . $idPessoasProprietario . ";}");
        $frm->add("{type: month; name: mes; allowBlank: false; fieldLabel: Mês;}");

        $frm->add('{type: hidden; name:gPage; value:' . RELATORIO_CONSUMO_API . ';}');
        $html .= $frm->render($o);
        break;


    case RELATORIO_CONSUMO_API:
        $html .= $o->msgTitle("Consumo de API");

        $where  = [];
        $filtros = [];

        $where[] = "PCA.quantidade_sucessos > 0";

        if ($_REQUEST['id_pessoas_proprietario']) {
            $where[] = "P.id = '" . $_REQUEST['id_pessoas_proprietario'] . "'" ;
            $filtros[] = "Proprietário: " . gFieldById("pessoas", $_REQUEST['id_pessoas_proprietario'], "apelido");
        }

        if ($_REQUEST['mes']) {
            $where[] = "data_inicio >= '" . date("Y-m-01", strtotime((string) $_REQUEST['mes'])) . "'";
            $where[] = "data_fim <= '" . date("Y-m-t", strtotime((string) $_REQUEST['mes'])) . "'";
            $filtros[] = "Mês: " . $_REQUEST['mes'];
        }

        $where = implode(" AND ", $where);
        $html .= $o->msgFilter("Filtros selecionados: " . implode(" • ", $filtros));

        $sql = "SELECT
                    P.apelido,
                    SUM(PCA.quantidade_sucessos) as quantidade_sucessos,
                    SUM(PCA.quantidade_erros) as quantidade_erros,
                    PCA.recurso,
                    PCA.data_inicio,
                    PCA.data_fim,
                    PCA.rota,
                    PCA.quantidade_contratada
                FROM pessoas_consumo_api PCA
                LEFT JOIN pessoas P ON
                    P.id = PCA.id_pessoas_proprietario
                WHERE {$where}
                GROUP BY
                    P.id,
                    PCA.quantidade_sucessos,
                    PCA.quantidade_erros,
                    PCA.recurso,
                    PCA.data_inicio,
                    PCA.data_fim,
                    PCA.rota,
                    PCA.quantidade_contratada
                ORDER BY
                    P.apelido,
                    PCA.data_inicio";
        $rs = dbQuery($sql);

        if (!$rs) {
            $html .= $o->msgInfo("Nenhum registro encontrado a partir dos filtros selecionados");
            $html .= $backButton;
            break;
        }

        $consumoPorProprietario = [];
        foreach ($rs as $row) {
            $apelido = $row['apelido'];
            if (!isset($consumoPorProprietario[$apelido])) {
                $consumoPorProprietario[$apelido] = [
                    'total_consumido' => 0,
                    'rows' => []
                ];
            }

            $consumoPorProprietario[$apelido]['total_consumido'] += $row['quantidade_sucessos'];
            $consumoPorProprietario[$apelido]['rows'][] = $row;
        }

        foreach ($consumoPorProprietario as $apelido => $consumo) {
            $html .= $o->tableBegin("big", true);

            $mtz = [];
            $mtz[] = "~6<b>" . $apelido . "</b>";
            $html .= $o->tableRow($mtz, "header");
            $mtz = [];
            $mtz[] = "<-Rota / Recurso";
            $mtz[] = "->Quantidade consumida";
            $mtz[] = "->Quantidade de erros";
            $mtz[] = "->Quantidade contratada";
            $mtz[] = "<>Data de inicio";
            $mtz[] = "<>Data de fim";
            $html .= $o->tableRow($mtz, "header");

            foreach ($consumo['rows'] as $row) {
                $mtz = [];
                $mtz[] = "<-" . $row['rota'] . " - " . $row['recurso'] . "";
                $mtz[] = "->" . $row['quantidade_sucessos'];
                $mtz[] = "->" . $row['quantidade_erros'];
                $mtz[] = "->" . $row['quantidade_contratada'];
                $mtz[] = "<>" . gDate($row['data_inicio']);
                $mtz[] = "<>" . gDate($row['data_fim']);
                $html .= $o->tableRow($mtz, "detail");
            }

            $mtz = [];
            $mtz[] = "~2->Total consumido: " . $consumo['total_consumido'];
            $html .= $o->tableRow($mtz, "footer");

            $html .= $o->tableEnd() . "<br>";
        }

        break;
}