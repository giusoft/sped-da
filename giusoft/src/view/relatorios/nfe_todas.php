<?php

define('INICIO', 0);
define('PESQUISAR', 1);
define('DOWNLOAD_XML', 2);

$paginasPodeExportar = [PESQUISAR];

if (in_array($gPage, $paginasPodeExportar)) {
	$o->PDFEnabled = true;
	$o->DOCEnabled = true;
	$o->XLSEnabled = true;
	$o->CSVEnabled = true;
}

$html .= $o->msgTitle("NFe status");

$situacoesNfe = [];
$situacoesNfe[] = 'Aprovada';
$situacoesNfe[] = 'Assinada';
$situacoesNfe[] = 'Submetida';
$situacoesNfe[] = 'Cancelada';
$situacoesNfe[] = 'Reprovada';
$situacoesNfe[] = 'Importada';

switch ($gPage) {
	case INICIO:
		$frm = new gForm("{columns: 2}");
		$frm->add('{type: combo; name: id_proprietario; fieldLabel: Proprietário; items: ' . $sp['combo_clientes'] . ';}');
		$frm->add('{type: text; name: numero_de; fieldLabel: Número de;}');
		$frm->add('{type: text; name: numero_ate; fieldLabel: Número até;}');
		$frm->add('{type: date; name: data_cadastro_de; fieldLabel: Data de cadastro de;}');
		$frm->add('{type: date; name: data_cadastro_ate; fieldLabel: Data de cadastro até;}');
		$frm->add('{name: id_filial; type: combo; fieldLabel: Filial;  value: 1; allowBlank: true; items:' . $sp['combo_filial'] . ';}');
		$frm->add('{name: situacao; type: comboMultiSelection; fieldLabel: Situação; allowBlank: true; value: 0, 1, 2;}', $situacoesNfe);
		$frm->add('{type: hidden; name: gPage; value: ' . PESQUISAR . ';}');
		$html .= $frm->render($o);
		break;


	case PESQUISAR:
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

            $where[] = sprintf(
                '((nfe.situacao IN (%s) AND nfe.sistema IN ("WMS", "WMS2")) %s)',
                $situacoes,
                $whereSituacaoImportada
            );
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
					SUBSTR(nfe.xml, 1, 1) AS tem_xml,
					nfe.chave AS chave,
					nfe.situacao,
					nfe.id_notas,
					nfe.protocolo,
					SUBSTR(nfe.xml_cancelamento, 1, 1) AS tem_xml_cancelamento,
					nfe.data_cancelamento,
					pessoa_cancelou.apelido AS colaborador_cancelou
				FROM nfe
				LEFT JOIN pessoas pessoa_emitiu ON pessoa_emitiu.id = nfe.id_pessoa
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
			if ($row['tem_xml']) {
				$botoes .= $o->button("{icon: download; caption: XML enviado; style: default ; size: small; href: " . $o->page . "&gPage=" . DOWNLOAD_XML . "&gId=" . $row['id'] . "&atributo=xml; hint: Baixar xml enviado para SEFAZ; target: _blank;}");
			}

			if ($row['tem_cancelamento']) {
				$botoes .= $o->button("{icon: download; caption: XML cancelamento; style: danger; size: small; href: " . $o->page . "&gPage=" . DOWNLOAD_XML . "&gId=" . $row['id'] . "&atributo=xml_cancelamento; hint: Baixar xml enviado para cancelamento; target: _blank;}");
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
		$rs = dbFastQuery("SELECT id, chave, situacao, numero, " . $_REQUEST['atributo'] . sprintf(" FROM nfe WHERE id = '%s'", $gId))[0];
        if (!$rs['id']) {
            $html .= $o->msgDanger("Não foi possível fazer o download do xml pois aconteceram os seguintes erros: <br> NF-e não encontrada");
           	$html .= $backButton;
            break;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $rs['numero'] . '_' . $rs['situacao'] . "_" . $rs['chave'] . "_" . $_REQUEST['atributo'] . ".xml");
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen((string) $rs[$_REQUEST['atributo']]));
        ob_clean();
        flush();
        echo($rs[$_REQUEST['atributo']]);
        exit;
		break;
}
