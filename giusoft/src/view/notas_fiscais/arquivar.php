<?php

include_once $gPath . "res/_classes/padrao/notas_fiscais.php";

define("INICIO", 			0);
define("PREPARAR", 			1);
define("PROCESSAR_LOTE", 	2);
define("FINALIZAR", 		3);
define("BAIXAR_ARQUIVO", 	4); 

$html .= $o->msgTitle("Arquivar");

if ($_REQUEST['gAjax']) {

    if ($_REQUEST['processarLote']) {
        header('Content-Type: application/json');

        if (!isset($_SESSION['processo_arquivar'])) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada']);
            exit;
        }

        $processo = $_SESSION['processo_arquivar'];
        $loteIndex = intval($_POST['lote']);

        $chunks = array_chunk($processo['documentos'], 50);

        if (!isset($chunks[$loteIndex])) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Lote inválido']);
            exit;
        }

        $loteAtual = $chunks[$loteIndex];

        $dadosGatilho = [
            'temRetorno' => 1,
            'documentos' => $loteAtual,
            'empresa' => $processo['empresa'],
            'config' => $processo['config'],
            'id_lote' => $processo['id_lote'],
            'acao' => 'processar',
            'com_pdf' => gDBCheck($processo['com_pdf']),
            'com_xml' => gDBCheck($processo['com_xml'])
        ];

        $retornoJson = dispararGatilho('gerarDanfeEmLote', $dadosGatilho);

        $mensagemErro = '';
        if ($retornoJson['erroCurl'] && !$retornoJson['resposta']) {

            $mensagemErro = "Falha ao conectar com o servidor";
            if (!empty($retornoJson['erroCurl'])) {
                $mensagemErro = gCleanField($retornoJson['erroCurl']);
            }
        }

        $retorno = json_decode((string) $retornoJson['resposta'], true);

        if (isset($retorno['sucesso']) && $retorno['sucesso']) {
            $_SESSION['processo_arquivar']['processados'] += count($loteAtual);
            echo json_encode([
                'sucesso' => true,
                'processados' => count($loteAtual)
            ]);
        } else {

            if (!$mensagemErro) {
                $mensagemErro = $retorno['mensagem'] ?: 'Erro desconhecido';
            }

            echo json_encode([
                'sucesso' => false,
                'mensagem' => $mensagemErro
            ]);
        }
    } elseif ($_REQUEST['finalizar']) {
        header('Content-Type: application/json');

        if (!isset($_SESSION['processo_arquivar'])) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada']);
            exit;
        }

        $processo = $_SESSION['processo_arquivar'];

        $dadosFinalizar = [
            'temRetorno' => 1,
            'empresa' => $processo['empresa'],
            'config' => $processo['config'],
            'documentos' => [],
            'id_lote' => $processo['id_lote'],
            'acao' => 'finalizar'
        ];

        $retornoJson = dispararGatilho('gerarDanfeEmLote', $dadosFinalizar);

        $mensagemErro = '';
        if ($retornoJson['erroCurl'] && !$retornoJson['resposta']) {

            $mensagemErro = "Falha ao conectar com o servidor";
            if (!empty($retornoJson['erroCurl'])) {
                $mensagemErro = gCleanField($retornoJson['erroCurl']);
            }
        }

        $retorno = json_decode((string) $retornoJson['resposta'], true);
        if (isset($retorno['sucesso']) && $retorno['sucesso']) {
            $dadosRetorno = $retorno['detalhes'] ?: $retorno['dados'] ?: [];
            $nomeArquivo = $dadosRetorno['arquivo'] ?: '';
            $cnpjEmitente = $processo['empresa']['cnpjFilial'];

            $sql = "SELECT
                        gatilhos.url_rota,
                        gatilhos_configuracoes.url_base
                    FROM gatilhos
                    LEFT JOIN gatilhos_configuracoes ON gatilhos_configuracoes.id = gatilhos.id_gatilhos_configuracoes
                    WHERE metodo = 'baixarDanfeEmLote' AND gatilhos.ativo = 1";
            $dadosRota = dbFastQuery($sql)[0];

            $urlBase = rtrim(gCleanField($dadosRota['url_base']), '/');
            $urlRota = trim(gCleanField($dadosRota['url_rota']), '/');

            if (!str_starts_with($urlBase, 'http://') && !str_starts_with($urlBase, 'https://')) {
                $urlBase = 'http://' . $urlBase;
            }

            $downloadUrl = "{$urlBase}/{$urlRota}/{$cnpjEmitente}/{$nomeArquivo}";
            $downloadUrl = preg_replace('#/+#', '/', str_replace('://', ':::', $downloadUrl));
            $downloadUrl = str_replace(':::', '://', $downloadUrl);

            unset($_SESSION['processo_arquivar']);

            echo json_encode([
                'sucesso' => true,
                'downloadUrl' => $downloadUrl,
                'nome' => $nomeArquivo
            ]);
        } else {

            if (!$mensagemErro) {
                $mensagemErro = $retorno['mensagem'] ?: 'Erro ao finalizar';
            }

            echo json_encode([
                'sucesso' => false,
                'mensagem' => $mensagemErro
            ]);
        }
    }

    exit;
}

switch ($gPage) {
    case INICIO:
        $frm = new gForm('{columns:2;}');
        $combo_tipo_nota = [];
        $combo_tipo_nota[0] = "* Indiferente";
        $combo_tipo_nota[1] = "NF-e Saída";
        $combo_tipo_nota[3] = "NF-e Entrada";

        $mtz = [];
        $mtz[0] = 'Somente aprovadas';
        $mtz[1] = 'Somente canceladas';
        $mtz[2] = 'Canceladas e aprovadas';

        $frm->row(
            $frm->add('{type: combo; allowBlank: true; name: id_pessoas_proprietario; fieldLabel: Proprietário; items: ' . $sp["combo_clientes"] . ';}'),
            $frm->add("{type: combo; allowBlank: true; name: tipo_nota; fieldLabel: Tipo; items: '" . json_encode($combo_tipo_nota) . "'; value: 1;}"),
            $frm->add("{type: combo; allowBlank: false; name: situacao; fieldLabel: Situação; items: '" . json_encode($mtz) . "'; value: 0;}")
        );

        $frm->row(
            $frm->add('{type: date; allowBlank: false; name: data_inicio; fieldLabel: Data início;}'),
            $frm->add('{type: date; allowBlank: false; name: data_final; fieldLabel: Data final;}')
        );

        $mtz = [];
        $mtz[0] = '*Indiferente';
        $mtz[1] = 'Sim';
        $mtz[2] = 'Não';

        $frm->row(
            $frm->add("{name: tratamento_fiscal; fieldLabel: Tratamento fiscal; type: combo; value: 0;}", $mtz),
            $frm->add('{type: checkbox; name: com_xml; fieldLabel: XML; value: 1;}'),
            $frm->add('{type: checkbox; name: com_pdf; fieldLabel: PDF; value: 0;}')
        );

        $frm->add('{type: hidden; name: gPage; value: ' . PREPARAR . ';}');
        $frm->add('{type: hidden; name: gFilter; value: 1;}');
        $html .= $frm->render($o);
        break;


    case PREPARAR:
        // Validações
        $validacoes = [];
        $filtros = [];

        if (!isset($_REQUEST["com_pdf"]) && !isset($_REQUEST["com_xml"])) {
            $validacoes[] = "Selecione se deseja arquivar DANFE ou PDF";
        }

        if (!$_REQUEST["tipo_nota"]) {
            $validacoes[] = "Informe o tipo de nota";
        }

        if (!isset($validacoes)) {
            $html .= $o->msgDanger("Falhas de validação: " . $o->ul($validacoes));
            $html .= $o->button('{title:Voltar; hint:Voltar a página anterior; icon:arrow-left; href:'.$o->page.'&gPage='.INICIO.';}');
            break;
        }

        $dataInicio = date('Y-m-d 00:00:00', strtotime(gDBDate($_REQUEST["data_inicio"])));
        $dataFinal = date('Y-m-d 23:59:59', strtotime(gDBDate($_REQUEST["data_final"])));
        $situacao = $_REQUEST['situacao'];

        $where = [];
        $where[] = sprintf("(DATE(NE.data)>='%s' AND DATE(NE.data)<='%s')", $dataInicio, $dataFinal);

        if ($_REQUEST["id_pessoas_proprietario"]) {
            $where[] = "(N.id_pessoas_proprietario=" . intval($_REQUEST["id_pessoas_proprietario"]) . ")";
        }

        if ($situacao != 0) {
            $orNfeCancelada = " OR NE.cancelada = 1 ";
        }

        if ($_REQUEST["tipo_nota"]) {
            switch ($_REQUEST["tipo_nota"]) {
                case 1:
                    $where[] = "(N.tipo = 'S')";
                    $where[] = "(NE.situacao = 'Aprovada' {$orNfeCancelada})";
                    break;
                case 2:
                    $where[] = "(N.tipo = 'M')";
                    $where[] = "(NE.situacao = 'Aprovada' {$orNfeCancelada})";
                    break;
                case 3:
                    $where[] = "(N.tipo = 'E')";
                    $where[] = "(NE.situacao = 'Aprovada' OR NE.situacao = 'Importada' {$orNfeCancelada})";
                    break;
            }
        }

        if ($_REQUEST['tratamento_fiscal'] > 0) {
            $joinEmpresa = " LEFT JOIN pessoas_juridicas ON pessoas_juridicas.id_pessoas = N.id_pessoas_proprietario ";
            $where[] = "(pessoas_juridicas.fiscal = " . ((int) ($_REQUEST['tratamento_fiscal'] == 1)) . " )";
        }

        $where[] = "NE.sistema IN ('WMS', 'WMS2')";
        $where = implode(" AND ", $where);

        $sql = "SELECT NE.id, NE.xml, NE.chave, NE.data, NE.xml_cancelamento, NE.cancelada
                FROM notas N
                LEFT JOIN nfe NE ON NE.id_notas = N.id
                {$joinEmpresa}
                WHERE {$where}";
        $rs = dbFastQuery($sql);

        // Processar documentos
        $documentosParaApi = [];
        foreach ($rs as $row) {
            if ($situacao == 1 && !$row['cancelada']) {
                continue;
            }

            if ($situacao == 0 && $row['cancelada']) {
                continue;
            }

            if ($row['cancelada'] && !empty($row['xml_cancelamento'])) {
                $documentosParaApi[] = [
                    'chave' => $row['chave'],
                    'xml' => $row['xml_cancelamento'],
                    'tipo' => 'xml_cancelamento'
                ];
            }

            if ($row['xml']) {
                $documentosParaApi[] = [
                    'chave' => $row['chave'],
                    'xml' => $row['xml'],
                    'tipo' => 'nfe'
                ];
            }
        }

        if (!$documentosParaApi) {
            $html .= $o->msgWarning("Nenhum XML válido encontrado para os filtros selecionados.");
            $html .= $o->button('{icon: arrow-left; title: Voltar; href: ' . $o->page . '&gPage=' . INICIO . ';}');
            break;
        }

        // Salvar dados em sessão para processamento posterior
        $idLote = uniqid();
        $_SESSION['processo_arquivar'] = [
            'documentos' => $documentosParaApi,
            'id_lote' => $idLote,
            'com_pdf' => gDBCheck($_REQUEST['com_pdf']),
            'com_xml' => gDBCheck($_REQUEST['com_xml']),
            'total' => count($documentosParaApi),
            'processados' => 0
        ];

        $nf = new NotasFiscais('S');
        $dadosEmpresa = $nf->obtemDadosEmpresa(obtemIdEmpresa($_REQUEST["id_pessoas_proprietario"]));
        $dadosConfig = $nf->buscarConfiguracoes($dadosEmpresa['cnpjFilial']);

        $_SESSION['processo_arquivar']['empresa'] = $dadosEmpresa;
        $_SESSION['processo_arquivar']['config'] = $dadosConfig;

        // Interface de processamento
        $html .= "<div id='progress-container'>";
        $html .= "<h3>Processando " . count($documentosParaApi) . " documentos...</h3>";
        $html .= "<h4>Não atualize nem feche esta página enquanto processamos sua solicitação.</h4>";
        $html .= "<div style='background: #f0f0f0; padding: 20px; border-radius: 5px;'>";
        $html .= "<div id='progress-bar' style='background: #4CAF50; height: 30px; width: 0%; border-radius: 5px; transition: width 0.3s;'></div>";
        $html .= "<div id='progress-text' style='margin-top: 10px; font-weight: bold;'>0%</div>";
        $html .= "<div id='progress-status' style='margin-top: 10px;'>Iniciando...</div>";
        $html .= "</div>";
        $html .= "</div>";

        $js = "
            let totalLotes = Math.ceil(" . count($documentosParaApi) . " / 50);
            let loteAtual = 0;
            let processados = 0;
            let total = " . count($documentosParaApi) . ";

            function processarProximoLote() {
                if (loteAtual >= totalLotes) {
                    finalizarProcessamento();
                    return;
                }

                document.getElementById('progress-status').innerHTML =
                    'Processando lote ' + (loteAtual + 1) + ' de ' + totalLotes + '...';

                fetch('" . $o->page . "&gAjax=1&processarLote=1', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'lote=' + loteAtual
                })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        processados += data.processados;
                        loteAtual++;

                        let percentual = Math.round((processados / total) * 100);
                        document.getElementById('progress-bar').style.width = percentual + '%';
                        document.getElementById('progress-text').innerHTML = percentual + '% (' + processados + '/' + total + ')';

                        setTimeout(processarProximoLote, 500);
                    } else {
                        document.getElementById('progress-status').innerHTML =
                            '<span style=\"color: red;\">Erro: ' + data.mensagem + '</span>';
                    }
                })
                .catch(error => {
                    document.getElementById('progress-status').innerHTML =
                        '<span style=\"color: red;\">Erro de conexão: ' + error + '</span>';
                });
            }

            function finalizarProcessamento() {
                document.getElementById('progress-status').innerHTML = 'Finalizando e gerando arquivo ZIP...';

                fetch('" . $o->page . "&gAjax=1&finalizar=1', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        document.getElementById('progress-container').innerHTML =
                            '<div class=\"alert alert-success\">' +
                            '<h4>Processamento concluído com sucesso!</h4>' +
                            '<p>' + total + ' documento(s) processado(s)</p>' +
                            '<a href=\"' + data.downloadUrl + '\" class=\"btn btn-success\" target=\"_blank\">' +
                            '<i class=\"fa fa-download\"></i> Baixar Arquivo ZIP' +
                            '</a>' +
                            '</div>';
                    } else {
                        document.getElementById('progress-status').innerHTML =
                            '<span style=\"color: red;\">Erro ao finalizar: ' + data.mensagem + '</span>';
                    }
                });
            }

            processarProximoLote();
        ";
        $o->addJavascript($js);

        break;

}