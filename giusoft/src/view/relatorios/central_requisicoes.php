<?php

define("INICIO",                       0);
define("FILTROS_PESQUISAR_REQUISICAO", 1);
define("DESCRICAO_REQUISICAO",         2);
define("BAIXAR_REQUISICAO",            3);
define("REENVIAR_REQUISICAO",          4);


$html .= $o->msgTitle("Central de integrações");

switch ($gPage) {
	case INICIO:

        $html .= '<div class="hidden-print"><form class="form-inline" method="POST" action="index.php?g=central_requisicoes">';
        $html .= '<input id="pesquisa" name="pesquisa" type="text" class="form-control input-md" placeholder="Pesquisa rápida...">&nbsp;<input type="hidden" name="g" value="central_requisicoes"><input type="hidden" name="gPage" value="0"> ';
        $html .= '<input id="action" name="action" type="hidden" class="form-control input-md" value="filtroRapido">';
        $html .= '<button type="button" onclick="btnPesquisar();" class="btn btn-default" style="margin-bottom: 4px"><span class="fal fa-search"></span> Pesquisar</button>';
        $html .= '</form></div>';
        $html .= $o->br();
        $o->addJavascript(
            "function btnPesquisar() {
                let pesquisaRapida = $('[name=\'pesquisa\']').val();
                if (pesquisaRapida){
                    showWait();
                    $('form').submit();
                } else {
                    showWait();
                    location.href = 'index.php?g=central_requisicoes&gPage=' + " . FILTROS_PESQUISAR_REQUISICAO . ";
                }
            }"
        );

        $where = array();

        if (!empty($_POST['pesquisa'])) {
            $pesquisa = gCleanField($_POST['pesquisa']);
            $where[] = "(
                gatilhos_requisicoes.id LIKE '%{$pesquisa}%' OR
                gatilhos_configuracoes.classe_integracao LIKE '%{$pesquisa}%' OR
                gatilhos.metodo LIKE '%{$pesquisa}%' OR
                pessoas.apelido LIKE '%{$pesquisa}%'
            )";
        }

        if ($_REQUEST['classe']) {
            $where[] = "gatilhos_configuracoes.classe_integracao = '" . gCleanField($_REQUEST['classe']) . "' ";
        }

        if ($_REQUEST['metodo']) {
            $where[] = "gatilhos.metodo = '" . gCleanField($_REQUEST['metodo']) . "' ";
        }

        if ($_REQUEST['dataDe']) {
            $where[] = "gatilhos_requisicoes_detalhes.data_hora >= '" . gDBDateTime($_REQUEST['dataDe']) . "' ";
        }

        if ($_REQUEST['dataAte']) {
            $where[] = "gatilhos_requisicoes_detalhes.data_hora <= '" . gDBDateTime($_REQUEST['dataAte']) . "' ";
        }

        if ($_REQUEST['id_pessoas']) {
            $where[] = "pessoas.id = '" . $_REQUEST['id_pessoas'] . "' ";
        }

        if (gDBCheck($_REQUEST['pendente'])) {
            $where[] = "gatilhos_requisicoes.pendente = " . gDBCheck($_REQUEST['pendente']);
        }

        if (!isset($where[0])) {
            $where[] = "gatilhos_configuracoes.id > 0";
        }

        $where = implode(" AND ", $where);

        $sql = "SELECT
                    gatilhos_requisicoes.id,
                    gatilhos_requisicoes.id AS id_requisicao,
                    gatilhos_requisicoes_detalhes.id AS id_detalhes_requisicao,
                    gatilhos_requisicoes_detalhes.data_hora,
                    gatilhos_configuracoes.classe_integracao,
                    gatilhos.metodo,
                    pessoas.apelido AS proprietario,
                    gatilhos_requisicoes.pendente,
                    MAX(gatilhos_requisicoes_detalhes.numero_tentativa) AS quantidade_tentativas,
                    programacao.os
                FROM gatilhos_configuracoes
                JOIN gatilhos ON gatilhos.id_gatilhos_configuracoes = gatilhos_configuracoes.id
                JOIN gatilhos_requisicoes ON gatilhos_requisicoes.id_gatilhos = gatilhos.id
                JOIN gatilhos_requisicoes_detalhes ON gatilhos_requisicoes_detalhes.id_gatilhos_requisicoes = gatilhos_requisicoes.id
                JOIN pessoas ON pessoas.id = gatilhos_configuracoes.id_pessoas_proprietario
                LEFT JOIN programacao ON programacao.id = gatilhos_requisicoes.id_programacao
                WHERE {$where}
                GROUP BY gatilhos_requisicoes.id
                ORDER BY gatilhos_requisicoes_detalhes.id DESC";

        $persistencia->pagination = new Pagination();

        if ($gParam["PAGINACAO"]["ativo"]) {
            $persistencia->porPagina = $gParam["PAGINACAO"]["valor"];
        }

        if ($persistencia->porPagina > 0) {
            $totalRegistros = $persistencia->pagination->controlarQuantidadePaginas($sql, $qtdMinimaPaginas = 10);
            $pagination = $persistencia->pagination->addPagination($totalRegistros, $persistencia->porPagina);
            $sql .= " LIMIT {$pagination->iniciar}, {$pagination->numero_registro_por_pagina}";
        } else {
            if ($gParam['LIMITAR_VISUALIZACAO']['ativo']) {
                $sql .= " LIMIT " . $gParam['LIMITAR_VISUALIZACAO']['valor'];
            }
        }

        $rs = dbFastQuery($sql);

        if (!$rs) {
            $html .= $persistencia->pagination->render('{style:margin-top:-1%;}');
            $html .= $o->msgDanger("Nenhum registro encontrado");
            $html .= $persistencia->pagination->render('{id:o;}');
            break;
        }

        $html .= $persistencia->pagination->render('{style:margin-top:-1%;}');

        $html .= $o->tableBegin("big", true);
        $mtz   = array();
        $mtz[] = '<>' . 'Opções';
        $mtz[] = '->' . 'Id';
        $mtz[] = '<>' . 'Data';
        $mtz[] = '<-' . 'Classe';
        $mtz[] = '<-' . 'Método';
        $mtz[] = '<-' . 'Proprietário';
        $mtz[] = '<-' . 'OS';
        // $mtz[] = '<-' . 'Ativa / Passiva';
        $mtz[] = '<>' . 'Pendente';
        $mtz[] = '->' . 'Quantidade tentativas';
        $html .= $o->tableRow($mtz, 'header-fixed');

        foreach ($rs as $row) {

            $botaoAbrir = '';
            if ($usrId == 1 || $row['metodo'] != 'autenticar') {
                $botaoAbrir = $o->button("{icon: plus; caption: Abrir; style: success; size: small; href: "
                . $o->page . "&gPage=" . DESCRICAO_REQUISICAO . "&idRequisicao=" . $row['id_requisicao']
                . "; hint: Abrir requisição; target: _blank}");
            }

            $mtz   = array();
            $mtz[] = '<>' . $botaoAbrir;
            $mtz[] = '->' . $row['id_requisicao'];
            $mtz[] = '<>' . gDateTime($row['data_hora']);
            $mtz[] = '<-' . ucfirst($row['classe_integracao']);
            $mtz[] = '<-' . $row['metodo'];
            $mtz[] = '<-' . $row['proprietario'];
            $mtz[] = '<-' . linkParaOS($row['os']);
            // $mtz[] = '<-' . $row['ativa_passiva'];
            $mtz[] = '<>' . gCheck($row['pendente'], true);
            $mtz[] = '->' . $row['quantidade_tentativas'];
            $html .= $o->tableRow($mtz, 'detail');
        }

        $html .= $o->tableEnd();
        $html .= $persistencia->pagination->render('{id:o;}');
        break;


    case FILTROS_PESQUISAR_REQUISICAO:
        $frm = new gForm();
        $frm->addFormMessage("Informe uma ou mais opções abaixo para busca...");
        $frm->row(
            $frm->add("{name: id_pessoas; fieldLabel: Empresas; type: combo; items: " . $sp['combo_empresas'] . "}"),
            $frm->add("{name: classe; fieldLabel: Classe; type: text;}"),
            $frm->add("{name: metodo; fieldLabel: Método; type: text;}")
        );

        $frm->row(
            $frm->add("{name: dataDe; fieldLabel: Data de; type: dateTime;}"),
            $frm->add("{name: dataAte; fieldLabel: Data até; type: dateTime;}")
        );

        $frm->add("{name: pendente; fieldLabel: Pendente; type: checkbox;}");

        $frm->add("{name: gPage; value: INICIO; type: hidden;}");
        $frm->add("{name: action; value: filtro; type:hidden;}");
        $html .= $frm->render($o);
        break;


    case DESCRICAO_REQUISICAO:
        $html .= $o->msgSubTitle("Detalhes da requisição");
        $sql = "SELECT
                    gatilhos_requisicoes.id AS id_requisicao,
                    gatilhos_requisicoes_detalhes.id AS id_detalhes_requisicao,
                    gatilhos_requisicoes_detalhes.data_hora,
                    gatilhos_configuracoes.classe_integracao,
                    gatilhos.metodo,
                    pessoas.id AS id_pessoas_proprietario,
                    pessoas.apelido AS proprietario,
                    gatilhos_requisicoes.pendente,
                    gatilhos_requisicoes_detalhes.numero_tentativa AS numero_tentativa,
                    gatilhos_requisicoes_detalhes.recebido,
                    gatilhos_requisicoes_detalhes.tempo_execucao,
                    gatilhos_requisicoes.enviado,
                    gatilhos.url_rota,
                    gatilhos_configuracoes.url_base,
                    programacao.os
                FROM
                    gatilhos_configuracoes
                JOIN gatilhos ON
                    gatilhos.id_gatilhos_configuracoes = gatilhos_configuracoes.id
                JOIN gatilhos_requisicoes ON
                    gatilhos_requisicoes.id_gatilhos = gatilhos.id
                JOIN gatilhos_requisicoes_detalhes ON
                    gatilhos_requisicoes_detalhes.id_gatilhos_requisicoes = gatilhos_requisicoes.id
                JOIN pessoas ON
                    pessoas.id = gatilhos_configuracoes.id_pessoas_proprietario
                LEFT JOIN programacao ON
                    programacao.id = gatilhos_requisicoes.id_programacao
                WHERE
                    gatilhos_requisicoes.id = {$_REQUEST['idRequisicao']}
                GROUP BY
                    gatilhos_requisicoes_detalhes.id
                ORDER BY
                    gatilhos_requisicoes_detalhes.id";
        $rs = dbFastQuery($sql);

        $html.=$o->tableBegin("big", true);

        $dadosCabecalho = $rs[0];

		$mtz = array();
		$mtz[] = '<-' . formatarParaCabecalho('Id', $dadosCabecalho['id_requisicao']);
		$mtz[] = '<-' . formatarParaCabecalho('Data', gDateTime($dadosCabecalho['data_hora']));
        $mtz[] = '<-' . formatarParaCabecalho('Proprietário', $dadosCabecalho['proprietario']);
		$html .= $o->tableRow($mtz, 'header');

		$mtz = array();
        $mtz[] = '<-' . formatarParaCabecalho('Classe', ucfirst($dadosCabecalho['classe_integracao']));
		$mtz[] = '<-' . formatarParaCabecalho('Método', $dadosCabecalho['metodo']);
		$mtz[] = '<-' . formatarParaCabecalho('Ativa / Passiva', $dadosCabecalho['ativa_passiva']);
		$html .= $o->tableRow($mtz, 'header');

        $mtz = array();
        $mtz[] = '<-' . formatarParaCabecalho('Pendente', gCheck($dadosCabecalho['pendente'], true));
		$mtz[] = '<-' . formatarParaCabecalho('OS', linkParaOS($dadosCabecalho['os']));
        $mtz[] = '<-' . formatarParaCabecalho('Quantidade tentativas', count($rs));
		$html .= $o->tableRow($mtz, 'header');

        $mtz = array();
		$mtz[] = '~3<-' . formatarParaCabecalho('Url completa', $dadosCabecalho['url_base'] . $dadosCabecalho['url_rota']);
		$html .= $o->tableRow($mtz, 'header');

        $html .= $o->tableBegin('big', true);
        $mtz = array();
        $btnDownloadEnviado = $o->button("{icon: download; style: success; size: tiny; href: "
        . $o->page . "&gPage=" . BAIXAR_REQUISICAO . "&idRequisicao=" . $dadosCabecalho['id_requisicao'] . "&tipo=enviado"
        . "; hint: Download; target: _blank}");

        $js = "
            function btnAbrirModal(id)
            {
                hideWait();
                $('#idRequisicao').val(id);
                $('#modalReenviarRequisicao').modal('show');
            }

            function btnConfirmarCancelar() {
                let id = $('#idRequisicao').val();
                let rota = '" . $o->page . "&gPage=" . REENVIAR_REQUISICAO . "&gId='+id;
                location.href=rota;
            }
        ";
        $o->addJavascript($js);

        $conteudoModal = "Deseja reenviar a requisição?";
        $conteudoModal .= "<input type='hidden' name='idRequisicao' id='idRequisicao' />";
        $html .= $o->modal("{title: Confirmação; cancelCaption: Fechar; url:btnConfirmarCancelar(); confirm: true; name: modalReenviarRequisicao; size:large; }", $conteudoModal);

        $btnReenviar = '';
        if ($usrId == 1 && $dadosCabecalho['metodo'] != 'autenticar') {
            $btnReenviar = $o->button("{style: primary; icon: upload; hint: Reenviar requisição; size: tiny; onClick: btnAbrirModal(" . $dadosCabecalho['id_requisicao'] . ");}");
        }

        $enviado = base64_decode($dadosCabecalho['enviado']);
        $mtz[] = '<-' . formatarParaCabecalho(
            $o->big('Enviado ') . $btnDownloadEnviado . ' ' . $btnReenviar .
            "<button class='btnArrumarJson hidden-print btn btn-primary btn-xs' style='margin-bottom: 4px; margin-left: 2px;' data-target='jsonEnviado'>
                <i class='fas fa-code'></i>
            </button> <br>" .
            "<pre id='jsonEnviado'>" . stripcslashes(htmlspecialchars($enviado)) . "</pre>",
            ' '
        );

        $html .= $o->tableRow($mtz, 'header');
        $html .= $o->tableEnd();

        $contador = 0;
        foreach ($rs as $row) {
            if ($row['numero_tentativa'] == 0) {
                $html .= $o->msgWarning('Comunicação ainda está na fila para acontecer');
                break;
            }

            $html .= $o->tableBegin('big', true);

            $mtz = array();

            $btnDownloadRecebido = $o->button("{icon: download; style: success; size: tiny; href: "
                . $o->page . "&gPage=" . BAIXAR_REQUISICAO . "&idDetalhesRequisicao=" . $row['id_detalhes_requisicao'] . "&tipo=recebido"
                . "; hint: Download; target: _blank}");

            $idPreRecebido = "jsonRecebido_" . $row['numero_tentativa'];

            $mtz[] = '<-' . formatarParaCabecalho(
                $o->big('Recebido • Tentativa ' . $row['numero_tentativa'] . ' • ' . gDateTime($row['data_hora']) . ' • Duração: ' . $row['tempo_execucao']) . ' ' . $btnDownloadRecebido .
                "<button class='btnArrumarJson hidden-print btn btn-primary btn-xs' style='margin-bottom: 4px; margin-left: 2px;' data-target='$idPreRecebido'>
                    <i class='fas fa-code'></i>
                </button> <br>" .
                "<pre id='$idPreRecebido'>" . stripcslashes(htmlspecialchars(base64_decode($row['recebido']))) . "</pre>",
                ' '
            );

            $html .= $o->tableRow($mtz, 'header');
            $html .= $o->tableEnd();
        }

        $js = "
            function mostrarComoJson(elementId, dado) {
                try {
                    let obj = typeof dado === 'string' ? JSON.parse(dado) : dado;
                    let jsonFormatado = JSON.stringify(obj, null, 4);
                    document.getElementById(elementId).textContent = jsonFormatado;
                } catch (e) {
                    document.getElementById(elementId).textContent = dado;
                    console.error('Erro ao formatar JSON:', e);
                }
            }

            document.querySelectorAll('.btnArrumarJson').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    let targetId = btn.getAttribute('data-target');
                    let conteudo = document.getElementById(targetId).textContent;
                    mostrarComoJson(targetId, conteudo);
                });
            });
            ";
        $o->addJavaScript($js);
        break;


    case BAIXAR_REQUISICAO:
        if ($_REQUEST['tipo'] == 'enviado') {
            $id = $_REQUEST['idRequisicao'];

            $sql = "SELECT enviado FROM gatilhos_requisicoes WHERE id = {$id}";
            $rs = dbFastQuery($sql)[0]['enviado'];
        }

        if ($_REQUEST['tipo'] == 'recebido') {
            $id = $_REQUEST['idDetalhesRequisicao'];

            $sql = "SELECT recebido FROM gatilhos_requisicoes_detalhes WHERE id = {$id}";
            $rs = dbFastQuery($sql)[0]['recebido'];
        }

        $conteudo = base64_decode($rs);

        $nomeArquivo = "requisicao_{$id}.txt";
        header('Content-Type: text/plain; charset=utf-8');

        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . strlen($conteudo));

        echo $conteudo;
        exit;
        break;

    case REENVIAR_REQUISICAO:

        require_once $_SERVER["DOCUMENT_ROOT"] . '/' . $ambiente . "/wms/giusoft/res/api/accesspoint.php";

        $sql = "SELECT
                    gatilhos.metodo,
                    gatilhos.id AS id_gatilhos,
                    gatilhos_configuracoes.id_pessoas_proprietario,
                    gatilhos_requisicoes_detalhes.id AS id_gatilhos_requisicoes_detalhes,
                    MAX(gatilhos_requisicoes_detalhes.numero_tentativa) AS total_tentativas,
                    gatilhos_requisicoes.enviado,
                    gatilhos_requisicoes.id AS id_gatilhos_requisicoes,
                    gatilhos_requisicoes.id_programacao
                FROM gatilhos_requisicoes_detalhes
                JOIN gatilhos_requisicoes ON gatilhos_requisicoes.id = gatilhos_requisicoes_detalhes.id_gatilhos_requisicoes
                JOIN gatilhos ON gatilhos.id = gatilhos_requisicoes.id_gatilhos
                JOIN gatilhos_configuracoes ON gatilhos_configuracoes.id = gatilhos.id_gatilhos_configuracoes
                WHERE gatilhos_requisicoes.id = " . $gId;
        $dadosRequisicao = dbFastQuery($sql)[0];

        $persistencia = new PontoAcesso(['empresa' => $GLOBALS['EMPRESA']]);
        $parametro['task'] = 1;
        $parametro['idPessoasProprietario'] = $dadosRequisicao['id_pessoas_proprietario'];

        $persistencia->acionarEventoMomento($dadosRequisicao['metodo'], $parametro);

        $body = base64_decode($dadosRequisicao['enviado']);

        $dadosReenvioRequisicao = array();
        if ($dadosRequisicao['total_tentativas'] == 0) {
            $dadosReenvioRequisicao['idGatilhoRequisicaoDetalhes']  = $dadosRequisicao['id_gatilhos_requisicoes_detalhes'];
        }
        $dadosReenvioRequisicao['idProgramacao'] 		= $dadosRequisicao['id_programacao'];
        $dadosReenvioRequisicao['numeroTentativa'] 	    = $dadosRequisicao['total_tentativas'];
        $dadosReenvioRequisicao['idGatilhos'] 	        = $dadosRequisicao['id_gatilhos'];
        $dadosReenvioRequisicao['idGatilhoRequisicao']  = $dadosRequisicao['id_gatilhos_requisicoes'];

        $persistencia->objetoGenerico->acessarRota($dadosRequisicao['metodo'], $body, $dadosReenvioRequisicao);

        redirect($o->page . "&gPage=" . DESCRICAO_REQUISICAO . "&idRequisicao=" . $gId);
        break;
}