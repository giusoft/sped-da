<?php

define('INICIO'                         ,0);
define('DADOS'                          ,1);
define('CANCELAR_NOTA'                  ,2);
define('PESQUISAR'                      ,10);
define('PESQUISAR_RESULTADO'            ,11);
define('CRIAR_ATUALIZAR'                ,20);
define('SALVAR'                         ,21);
define('EXCLUIR'                        ,30);
define('IMPORTAR'                       ,40);
define('IMPORTAR_SALVAR'                ,41);
define('ITENS'                          ,60);
define('ITENS_SALVAR'                   ,61);
define('ITENS_EXCLUIR'                  ,62);
define('ITENS_ATUALIZAR_CFOP_ORIGEM'    ,63);
define('FORMULARIO_IMPORTAR_ITENS_NOTA' ,64);
define('MEDICAMENTO'                    ,90);
define('MEDICAMENTO_SALVAR'             ,91);
define('IMPOSTOS'                       ,100);
define('IMPOSTOS_SALVAR'                ,101);
define('NFE'                            ,110);
define('NFE_SALVAR'                     ,111);
define('NFE_OBTER_XML'                  ,115);
define('NFE_OPCOES'                     ,116);
define('NFE_REENVIAR_EMAIL'             ,122);
define('LISTAR_CLIENTES_AGRUPAMENTO'    , 210);
define('LISTAR_NOTAS_AGRUPAMENTO'       , 211);
define('EXIBIR_NOTA_AGRUPADA'           , 212);
define('AGRUPAR_NOTAS_SELECIONADAS'     , 213);
define('REMOVER_AGRUPAMENTO'            , 214);
define('NFE_OBTER_XML_CANCELAMENTO'     , 215);
define('NFE_OBTER_XML_CARTA_CORRECAO'   , 216);
define('ATIVAR_DESATIVAR_CONTINGENCIA'  , 217);

include_once $gPath."/gfw/inc/gPage.php";

// Removendo paginação e limit quando for exportação
if (
    $_REQUEST["gPDF"]
    || $_REQUEST["gXLS"]
    || $_REQUEST["gDOC"]
    || $_REQUEST["gCSV"]
) {
    $gParam["PAGINACAO"]["ativo"] = 0;
    $gParam["LIMITAR_VISUALIZACAO"]["ativo"] = 0;
}

$nf = new NotasFiscais('S');

if ($_REQUEST['gAjax']) {
    if ($_REQUEST['emitirNfe']) {
        $validaFilial = $nf->validarFilialSessao($gId);
        if (!$validaFilial['sucesso']) {
            $resultado['msgErro'] = $validaFilial['msg'];
            echo json_encode($resultado);
            exit;
        }

        $sql = "SELECT nfe.situacao FROM notas
                INNER JOIN nfe ON nfe.id_notas = notas.id
                WHERE notas.id = '{$gId}'";
        $confereNFE = dbFastQuery($sql)[0];

        if ($confereNFE && in_array($confereNFE["situacao"], ["Assinada", "Submetida", "Aprovada", "Cancelada"])) {
            $return["msgErro"] = "A NFe já foi emitida anteriormente. Por favor aperte F5 ou recarregue a página e vá em \"opções da NFe\"";
            echo json_encode($return);
            exit;
        }

        // Pega os dados da nota
        $nota = $nf->obtemDadosNFE($gId);
        $dados['nota'] = $nota;

        // Pega os dados do filial
        $dados['empresa'] = $nf->obtemDadosEmpresa(obtemIdEmpresa($nota["id_pessoas_proprietario"]));
        $dados['config'] = $nf->buscarConfiguracoes($dados['empresa']['cnpjFilial']);

        $dados['cliente'] = $nf->obtemDadosProprietario($nota["id_pessoas_proprietario"], $nota["tipo"]);

        $idTransportadora = $nota["id_pessoas_proprietario"];
        if (intval($nota["id_pessoas_transportadora"]) > 0) {
            $idTransportadora = $nota["id_pessoas_transportadora"];
        }

        // Busca os dados do responsável pelo envio do produto
        $dadosTransportadora = $nf->obtemDadosTransportadora($idTransportadora);

        if ($nota["tipo"] == "E") {
            $dados['tipoOperacao'] = 0; // entrada (Usamos estorno)
        }

        if ($nota['finNFe'] == 3) {
            $dados['operacao'] = '999 - ESTORNO DE NFE NAO CANCELADA NO PRAZO LEGAL';
        } else {
            $dados['operacao'] = gFieldById("cfops", $nota["id_cfops"], "descricao_resumida"); // Aqui é a Natureza da Operação
        }

        if ($idTransportadora > 0) {
            $dados['transportadora'] = $dadosTransportadora;
        }

        if ($nota["refNfe"]) {
            $dados['refNfe'] = $nota["refNfe"];
        }

        $dados['cfop'] = gFieldById("cfops", $nota["id_cfops"], "codigo");
        $dados['tPag'] = $nota['tPag'];
        $dados['vPag'] = $nota['vPag'];
        $dados['idDest'] = $nota["idDestino"];
        $dados['idNotas'] = $gId;
        $dados['idEmpresa'] = $_SESSION['filialAtualId'];
        $dados['indIEDest'] = $nota["IE"];
        $dados['codigoAntt'] = $nota["RNTC"];
        $dados['veiculoPlaca'] = $nota["placa"];
        $dados['especieMarca'] = $nota["marca"];
        $dados['outrasDespesas'] = $nota["vOutro"]; // Atualmente não usamos na API
        $dados['veiculoPlacaUf'] = $nota["placaUF"];
        $dados['modalidadeFrete'] =$nota["modFrete"]; // Atualmente não usamos na API
        $dados['quantidadeVolume'] = $nota["qVol"];
        $dados['finalidadeOperacao'] = $nota["finNFe"];
        $dados['especieTransportada'] = $nota["esp"];

        if (intval($nota["pesoB"]) > 0) {
            $dados['totalPesoBruto'] = $nota["pesoB"];
        }

        if (intval($nota["pesoL"]) > 0) {
            $dados['totalPesoLiquido'] = $nota["pesoL"];
        }

        $informacao = "";
        if ($nota["informacaoFisco"] != "" && !is_null($nota["informacaoFisco"])) {
            $informacao = $nota["informacaoFisco"];
        }

        $informacao .= " " . $nota["infAdFisco"];
        $dados['infAdFisco'] = $informacao;

        $informacaoContribuente .= $nota["InfCpl"];
        $dados['informacoesContribuinte'] = $informacaoContribuente;
        $dados['indIntermed'] = $nota['indIntermed']; // Atualmente não usamos na API

        // DADOS DOS ITENS
        $itens = $nf->obtemDadosItensNFE($gId);

        foreach ($itens as $item) {
            $item["numeroCliente"] = $nota["numseq"];
            $dados['item'][] = $item;
        }

        $dados['NfeNumeroEOperacao'] = $nf->obterDadosNfeNumeroEOperacao($dados['config']);

        $idNfe = dbInsert('nfe', $nf->prepararCamposNfe($dados), 1);

        $sql = "UPDATE notas
                SET numero = " . $dados['NfeNumeroEOperacao']['numeroNota'] . "
                WHERE id = " . $nota['id'];
        dbFastQuery($sql);

        $emitirNfe = [
            "temRetorno" => 1,
            "dadosNfe" => $dados,
            "idPessoasProprietario" => 1
        ];

        $retornoEmiteNota = dispararGatilho('enviarNfe', $emitirNfe);

        $mensagemErro = '';
        if ($retornoEmiteNota['erroCurl'] && !$retornoEmiteNota['resposta']) {

            $mensagemErro = "Falha ao conectar com o servidor";
            if (!empty($retornoEmiteNota['erroCurl'])) {
                $mensagemErro = gCleanField($retornoEmiteNota['erroCurl']);
            }
        }

        $retornoEmiteNota = json_decode((string) $retornoEmiteNota['resposta'], true);
        if ($retornoEmiteNota && $retornoEmiteNota['detalhes']['andamento']) {

            $andamento = explode("|", gCleanField($retornoEmiteNota['detalhes']['andamento']));

            $resultado['statusSubmetida'] = $andamento[0];
            $resultado['modoOperacao'] = $andamento[1];
            $resultado['statusMontagem'] = $andamento[2];
            $resultado['statusAssinado'] = $andamento[3];

            $resultado['statusValidado'] = $andamento[4];
            if (!$andamento[4]) {
                $resultado['statusValidado'] = linkParaGoogle(gCleanField($retornoEmiteNota['mensagem']));
            }

            $resultado['statusXmlEmEnvio'] = $andamento[5];
            $resultado['statusXmlEnviado'] = $andamento[6];
            if ($andamento[6] || $andamento[5]) {
                $resultado['statusSefaz'] = linkParaGoogle(gCleanField($retornoEmiteNota['mensagem']));
            }
        } else {
            $mensagemErro .= $retornoEmiteNota['mensagem'];
        }

        $sql = "
            UPDATE nfe
            SET situacao = '" . gCleanField($retornoEmiteNota['detalhes']['situacao']) . "',
                chave = '" . gCleanField($retornoEmiteNota['detalhes']['chave']) . "',
                protocolo = '" . gCleanField($retornoEmiteNota['detalhes']['protocolo'] ) . "',
                data_recibo = '" . gCleanField($retornoEmiteNota['detalhes']['dataHoraRecebimento']) . "'
            WHERE id = {$idNfe}";
        dbFastQuery($sql);

        if ($retornoEmiteNota['sucesso']) {
            $resultado['sucesso'] = 1;
            $botoesHtml .= $o->button("{icon: file-code; caption: Baixar XML; hint: Baixar xml da NF-e; style: primary; size: normal;}", "javascript: btnObterXml(".$idNfe.")");
            $botoesHtml .= $o->button("{icon: file-pdf; caption: Imprimir danfe; hint: Gerar danfe da NF-e; style: info; size: normal;}", "javascript: btnImprimirDanfe(".$idNfe.")");

            $resultado['botoesHtml'] = $botoesHtml;

            if ($itens[0]['emails_nota'] || $itens[0]['email_proprietario']) {
                $statusEnvioEmail = $nf->enviarEmail($idNfe, base64_decode((string) $retornoEmiteNota['detalhes']['xml']));
            }

            if (is_array($statusEnvioEmail)) {
                $resultado['statusEmail'] = "Não enviado ao cliente. Motivo: " . implode(';', $statusEnvioEmail);
            } else {
                $resultado['statusEmail'] = "Enviado ao cliente";
            }

            if ($dados['config']['tpAmb'] != 2) { //2=homologacao
                ### Esse metodo é para chamar o OMIE. Ativar só depois para não mandar nada para eles agora ###
                $dadosNf = [
                    "chave" => gCleanField($retornoEmiteNota['detalhes']['chave']),
                    "xml" => base64_decode((string) $retornoEmiteNota['detalhes']['xml']),
                    "idPessoasProprietario" => 1
                ];
                dispararGatilho("emitirNf", $dadosNf); // Esse metodo é do OMIE
            }
        }

        if ($mensagemErro) {
            $resultado["msgErro"] = $mensagemErro;
        }

        echo json_encode($resultado);

        $dadosEventos = [];
        $dadosEventos['id_nfe']               = $idNfe;
        $dadosEventos['id_notas_saida']       = $nota['id'];
        $dadosEventos['id_pessoas']           = $usrId;
        $dadosEventos['xml']                  = base64_decode((string) $retornoEmiteNota['detalhes']['xml']);
        $dadosEventos['data']                 = date('Y-m-d H:i:s');
        $dadosEventos['serie']                = (int) $dados['NfeNumeroEOperacao']['serie'];
        $dadosEventos['sucesso']              = (int) $retornoEmiteNota['sucesso'];
        $dadosEventos['protocolo']            = gCleanField($retornoEmiteNota['detalhes']['protocolo']);
        $dadosEventos['retorno_mensagem']     = gCleanField(removerAcentos($retornoEmiteNota['mensagem'] ?: $mensagemErro));
        $dadosEventos['id_nfe_tipos_eventos'] = 1; // 1 = Emissão
        dbInsert('nfe_eventos', $dadosEventos);
    }

    if ($_REQUEST['cancelarNfe']) {
        $frm= new gForm("{columns: 12; id:formCOFINS; onClickSubmit: btnConfirmaCancelarNFE;}");
        $frm->row(
            $frm->add("{type: textarea; name: motivoCancelamento; fieldLabel: Motivo cancelamento; hint: Deve conter no mínimo 15 caracteres;}")
        );
        $frm->add("{type: hidden; name: cancela_gId; value:".$gId.";}");
        $frm->add("{type: hidden; name: cancela_gIdNota; value:".$_REQUEST["gIdNota"].";}");
        $frm->addButton("{icon: arrow-left; title: Voltar; style: success; size: normal;", "javascript: btnOpcoesNFE(".$gId.", ".$_REQUEST["gIdNota"].")");
        echo $frm->render($o);
        exit;
    }

    if ($_REQUEST['cancelarNfeConfirmar']) {
        $validaFilial = $nf->validarFilialSessao($_REQUEST['gIdNota']);
        if (!$validaFilial['sucesso']) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => strip_tags($validaFilial['msg'])
            ]);
            exit;
        }

        $sql = "SELECT
                    nfe.id,
                    nfe.protocolo,
                    nfe.chave,
                    nfe.situacao,
                    notas.id as id_nota,
                    notas.id_pessoas_proprietario,
                    notas.numero
                FROM nfe
                LEFT JOIN notas ON notas.id = nfe.id_notas
                WHERE nfe.id = '{$gId}'";
        $nfeBD = dbQuery($sql)[0];

        ## TODO:: Verificar se são somente essas validações
        if (!$nfeBD) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'NFe não encontrada'
            ]);
            exit;
        }

        if ($nfeBD['situacao'] !== 'Aprovada') {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Apenas NFe aprovadas podem ser canceladas'
            ]);
            exit;
        }

        $motivo = gCleanField($_REQUEST["motivo"]);
        if (strlen($motivo) < 15) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'O motivo do cancelamento deve ter no mínimo 15 caracteres'
            ]);
            exit;
        }

        $dadosCancelamento = [];
        $dadosCancelamento['temRetorno'] = 1;
        $dadosCancelamento['chave'] = $nfeBD['chave'];
        $dadosCancelamento['protocolo'] = $nfeBD['protocolo'];
        $dadosCancelamento['justificativa'] = $motivo;
        $dadosCancelamento['idPessoasProprietario'] = 1;
        $dadosCancelamento['empresa'] = $nf->obtemDadosEmpresa(obtemIdEmpresa($nfeBD["id_pessoas_proprietario"]));
        $dadosCancelamento['config'] = $nf->buscarConfiguracoes($dadosCancelamento['empresa']['cnpjFilial']);

        // Disparar gatilho para API
        $retornoCancelamento = dispararGatilho('cancelarNfe', $dadosCancelamento);

        $mensagemErro = '';
        if ($retornoCancelamento['erroCurl'] && !$retornoCancelamento['resposta']) {
            $mensagemErro = "Falha ao conectar com o servidor";
            if (!empty($retornoCancelamento['erroCurl'])) {
                $mensagemErro = gCleanField($retornoCancelamento['erroCurl']);
            }
        }

        $retornoCancelamento = json_decode((string) $retornoCancelamento['resposta'], true);

        $resultado = [];

        if ($retornoCancelamento['sucesso']) {
            $mtz = [];
            $mtz["cancelada"] = 1;
            $mtz["data_movimento"] = date('Y-m-d H:i:s');
            dbUpdate("notas", $mtz, $nfeBD['id_nota']);

            $mtz = [];
            $mtz["cancelada"] = 1;
            $mtz["situacao"] = "Cancelada";
            $mtz["data_cancelamento"] = date("Y-m-d H:i:s");
            $mtz["id_pessoas_cancelou"] = $usrId;
            dbUpdate("nfe", $mtz, $nfeBD['id']);

            if ($dados['config']['tpAmb'] != 2) { //2=homologacao
                $dadosImportacao = [
                    "chave" => $nfeBD['chave'],
                    "xml" => $mtz['xml_cancelamento'] = base64_decode((string) $retornoCancelamento['mensagem']['xml_cancelamento']),
                    "idPessoasProprietario" => 1
                ];
                dispararGatilho("importarCancNFe", $dadosImportacao); // Esse aqui é do OMIE
            }

            $resultado['sucesso'] = true;
            $resultado['mensagem'] = 'NFe cancelada com sucesso!';
        } else {
            // Verificar se é duplicidade de evento (já cancelada no SEFAZ)
            if (
                strpos(gCleanField($retornoCancelamento['mensagem']), '573') !== false
                || strpos(gCleanField($retornoCancelamento['mensagem']), 'Duplicidade de Evento') !== false
            ) {

                // Atualizar como cancelada
                $mtz = [];
                $mtz["cancelada"] = 1;
                $mtz["data_movimento"] = date('Y-m-d H:i:s');
                dbUpdate("notas", $mtz, $nfeBD['id_nota']);

                $mtz = [];
                $mtz["cancelada"] = 1;
                $mtz["situacao"] = "Cancelada";
                dbUpdate("nfe", $mtz, $nfeBD['id']);

                $resultado['sucesso'] = false;
                $resultado['mensagem'] = 'A NFe já se encontra cancelada no SEFAZ';
            } else {
                $resultado['sucesso'] = false;
                $resultado['mensagem'] = gCleanField($retornoCancelamento['mensagem']);
                $resultado['detalhes'] = gCleanField($retornoCancelamento['detalhes']);
            }
        }

        if ($retornoCancelamento['mensagem']) {
            $dadosEventos = [];
            $dadosEventos['id_nfe']               = $nfeBD['id'];
            $dadosEventos['id_notas_saida']       = $nfeBD['id_nota'];
            $dadosEventos['id_pessoas']           = $usrId;
            $dadosEventos['id_nfe_tipos_eventos'] = 2; // 2 = Cancelamento
            $dadosEventos['xml']                  = base64_decode((string) $retornoCancelamento['mensagem']['xml_cancelamento']);
            $dadosEventos['data']                 = date('Y-m-d H:i:s');
            $dadosEventos['serie']                = (int) $nfeBD['serie'];
            $dadosEventos['motivo']               = $motivo;
            $dadosEventos['sucesso']              = (int) $retornoCancelamento['sucesso'];
            if (!empty($retornoCancelamento['mensagem']['protocolo']) ) {
                $dadosEventos['protocolo']        = ($retornoCancelamento['mensagem']['protocolo']);
            }
            $dadosEventos['retorno_mensagem'] = is_array($retornoCancelamento['mensagem'])
                ? gCleanField(removerAcentos($retornoCancelamento['mensagem']['mensagem']))
                : gCleanField(removerAcentos($retornoCancelamento['mensagem']));

            dbInsert('nfe_eventos', $dadosEventos);
        }

        if ($mensagemErro) {
            $resultado['mensagem'] = $mensagemErro;
        }

        echo json_encode($resultado);
        exit;
    }

    if ($_REQUEST['gerarDanfe']) {
        $existeNfe = dbFastQuery("SELECT id FROM nfe WHERE id = '{$gId}'");

        if ($existeNfe) {

            $join = '';
            $select = '';
            if ($_REQUEST['cancelamento']) {
                $select = "nfe_eventos_cancelamento.xml AS xml_cancelamento,";
                $join = "LEFT JOIN nfe_eventos AS nfe_eventos_cancelamento
                            ON nfe_eventos_cancelamento.id_nfe = nfe.id
                            AND nfe_eventos_cancelamento.id_nfe_tipos_eventos = 2"; // id_nfe_tipos_eventos = 2 (Evento de cancelamento)
            }

            $sql = "SELECT
                        nfe.chave,
                        notas.id AS idNota,
                        notas.id_pessoas_proprietario,
                        {$select}
                        nfe_eventos_normal.xml AS xml
                    FROM nfe
                    LEFT JOIN notas ON notas.id = nfe.id_notas
                    LEFT JOIN nfe_eventos AS nfe_eventos_normal
                        ON nfe_eventos_normal.id_nfe = nfe.id
                        AND nfe_eventos_normal.id_nfe_tipos_eventos IN (1, 5)
                    {$join}
                    WHERE nfe.id = '{$gId}' {$where}
                    GROUP BY nfe.id";
            $xml = dbFastQuery($sql)[0];

            $dadosDanfe = [];
            $dadosDanfe['temRetorno'] = 1;
            $dadosDanfe['xml'] = $xml['xml'];
            $dadosDanfe['chave'] = $xml['chave'];
            $dadosDanfe['idPessoasProprietario'] = 1;
            $dadosDanfe['empresa'] = $nf->obtemDadosEmpresa(obtemIdEmpresa($xml['id_pessoas_proprietario']));
            $dadosDanfe['config'] = $nf->buscarConfiguracoes($dadosDanfe['empresa']['cnpjFilial']);

            if ($_REQUEST['cancelamento']) {
                $dadosDanfe['xml_cancelamento'] = $xml['xml_cancelamento'];
                $retornoGerarDanfe = dispararGatilho('gerarDanfeCancelamento', $dadosDanfe);
            } else {
                $retornoGerarDanfe = dispararGatilho('gerarDanfe', $dadosDanfe);
            }

            $mensagemErro = '';
            if ($retornoGerarDanfe['erroCurl'] && !$retornoGerarDanfe['resposta']) {
                $mensagemErro = "Falha ao conectar com o servidor";
                if (!empty($retornoGerarDanfe['erroCurl'])) {
                    $mensagemErro = gCleanField($retornoGerarDanfe['erroCurl']);
                }
            }

            $retornoGerarDanfe = json_decode((string) $retornoGerarDanfe['resposta'], true);
            if ($retornoGerarDanfe['sucesso']) {
                $pdf = base64_decode((string) $retornoGerarDanfe['detalhes']['pdf_base64']);

                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="danfe_' . $xml['chave'] . '.pdf"');
                header('Content-Length: ' . strlen($pdf));
                ob_clean();
                flush();
                echo $pdf;
            } else {
                $html .= $o->msgTitle("Notas fiscais internas");
                if (!$mensagemErro) {
                    $mensagemErro = (gCleanField($retornoGerarDanfe['mensagem']) ?: 'Arquivo não gerado');
                }

                $mensagemErro = $o->ul([$mensagemErro]);
                $html .= $o->msgDanger("Erro ao gerar o danfe: " . $mensagemErro);
                $html .= $o->button("{name: back; icon: arrow-left; title: Voltar; style: default; href: " . $o->page . "&gPage=" . NFE . "&gId=" . $xml['idNota'] . "}");
                return;
            }
        }
    }

    if ($_REQUEST['estornarNfe']) {
        $idNotaOrigem = (int) $idNota;

        // Verifica se a nota não foi cancelada e não está vinculada a nenhum outro estorno
        $sql = "SELECT id FROM notas WHERE id_notas_origem_estorno = {$idNotaOrigem} AND cancelada = 0";
        $rs  = dbFastQuery($sql)[0]['id'];
        if ($rs) {
            $return["msgErro"] = "Esta nota já possui vínculo de estorno com a nota: " . linkParaNota($rs);
            echo json_encode($return);
            exit;
        }

        // Busca os dados da nota origem para criar a nota de estorno com base nos dados dessa nota
        $sql = 'SELECT * FROM notas WHERE id = ' . $idNotaOrigem;
        $notaOrigem = dbFastQuery($sql)[0];

        if ($notaOrigem['id_filial'] != $_SESSION['filialAtualId']) {
            $return["msgErro"] = "Divergência de filial: Esta nota pertence a um filial diferente do que você está logado atualmente. Por favor, troque de filial para realizar esta operação.";
            echo json_encode($return);
            exit;
        }

        $notaOrigem = excluirIndicesNumericos($notaOrigem);
        unset($notaOrigem['id']);
        unset($notaOrigem['numero']);
        unset($notaOrigem['confirmada']);
        unset($notaOrigem['data_movimento']);
        $notaOrigem['data_criou'] = date('Y-m-d H:i:s');
        $notaOrigem['id_filial'] = $_SESSION['filialAtualId'];
        $notaOrigem['id_pessoas_criou'] = $_SESSION['usrId'];
        $notaOrigem['id_notas_origem_estorno'] = $idNotaOrigem;
        $notaOrigem['tPag'] = 90;
        $notaOrigem['vPag'] = 0;
        $notaOrigem['finNFe'] = 3; // finNFe = 3 (Finalidade da Emissão para nota de estorno)

        $cfop = $nf->converterCfopSaidaParaEntrada(gFieldById("cfops", $notaOrigem["id_cfops"], "codigo"));

        $idCfop = dbFastQuery("SELECT id FROM cfops WHERE codigo = '{$cfop}' LIMIT 1")[0]['id'];

        $notaOrigem['id_cfops'] = $idCfop;
        $idNovaNota = dbInsert('notas', $notaOrigem, true);

        $nf->salvarNotasItensEImpostos($idNotaOrigem, $idNovaNota);

        $nota = $nf->obtemDadosNFE($idNovaNota);
        $dados['nota'] = $nota;

        $dados['empresa'] = $nf->obtemDadosEmpresa(obtemIdEmpresa($nota["id_pessoas_proprietario"]));

        $dados['config'] = $nf->buscarConfiguracoes($dados['empresa']['cnpjFilial']);

        $dados['NfeNumeroEOperacao'] = $nf->obterDadosNfeNumeroEOperacao($dados['config']);

        $dados['cliente'] = $nf->obtemDadosProprietario($nota["id_pessoas_proprietario"], 'S');

        $idTransportadora = $nota["id_pessoas_transportadora"] ?: $nota["id_pessoas_proprietario"];
        $dadosTransportadora = $nf->obtemDadosTransportadora($idTransportadora);

        $dados['tipoOperacao'] = 0; // Entrada

        if ($idTransportadora) {
            $dados['transportadora'] = ($dadosTransportadora);
        }

        $dados['cfop'] = $cfop;
        $dados['tPag'] = $nota['tPag'];
        $dados['vPag'] = $nota['vPag'];
        $dados['idDest'] =  $nota["idDestino"];
        $dados['idNotas'] = $idNovaNota;
        $dados['operacao'] = '999 - ESTORNO DE NFE NAO CANCELADA NO PRAZO LEGAL';
        $dados['indIEDest'] = $nota["IE"]; // Atualmente não usamos na API
        $dados['codigoAntt'] = $nota["RNTC"];
        $chaveNfe = dbFastQuery('SELECT chave FROM nfe WHERE id = ' . $idNFe)[0]['chave'];
        $dados['refNfe'] = $chaveNfe;
        $dados['finalidadeOperacao'] = 3;

        $dados['especieMarca'] = $nota["marca"];
        $dados['veiculoPlaca'] = $nota["placa"];
        $dados['outrasDespesas'] = $nota["vOutro"]; // Atualmente não usamos na API
        $dados['veiculoPlacaUf'] = $nota["placaUF"];
        $dados['modalidadeFrete'] = $nota["modFrete"];
        $dados['quantidadeVolume'] = $nota["qVol"];
        $dados['especieTransportada'] =  $nota["esp"];

        if (intval($nota["pesoB"]) > 0) {
            $dados['totalPesoBruto'] = $nota["pesoB"];
        }

        if (intval($nota["pesoL"]) > 0) {
            $dados['totalPesoLiquido'] = $nota["pesoL"];
        }

        $informacao = "";
        if ($nota["informacaoFisco"]) {
            $informacao = $nota["informacaoFisco"];
        }

        $informacao .= " " . $nota["infAdFisco"];
        $dados['infAdFisco'] = $informacao;

        $informacaoContribuente .= $nota["InfCpl"];
        $dados['informacoesContribuinte'] = $informacaoContribuente;

        $itens = $nf->obtemDadosItensNFE($idNovaNota);
        foreach ($itens as $item) {
            $item["NumeroCliente"] = $nota["numseq"]; // Atualmente não usamos na API
            $dados['item'][] = $item;
        }

        $idNfeEstorno = dbInsert('nfe', $nf->prepararCamposNfe($dados), 1);

        $estornarNfe = [
            "temRetorno" => 1,
            "dadosNfe" => $dados,
            "idPessoasProprietario" => 1
        ];

        $retornoEmiteNota = dispararGatilho('estornarNfe', $estornarNfe);

        $mensagemErro = '';
        if ($retornoEmiteNota['erroCurl'] && !$retornoEmiteNota['resposta']) {

            $mensagemErro = "Falha ao conectar com o servidor";
            if (!empty($retornoEmiteNota['erroCurl'])) {
                $mensagemErro = gCleanField($retornoEmiteNota['erroCurl']);
            }
        }

        $retornoEmiteNota = json_decode((string) $retornoEmiteNota['resposta'], true);

        if ($retornoEmiteNota && $retornoEmiteNota['detalhes']['andamento']) {

            $andamento = explode("|", (string) $retornoEmiteNota['detalhes']['andamento']);

            $resultado['statusSubmetida'] = $andamento[0];
            $resultado['modoOperacao'] = $andamento[1];
            $resultado['statusMontagem'] = $andamento[2];
            $resultado['statusAssinado'] = $andamento[3];

            $resultado['statusValidado'] = $andamento[4];
            if (!$andamento[4]) {
                $resultado['statusValidado'] = linkParaGoogle($retornoEmiteNota['mensagem']);
            }

            $resultado['statusXmlEmEnvio'] = $andamento[5];
            $resultado['statusXmlEnviado'] = $andamento[6];
            if ($andamento[6] || $andamento[5]) {
                $resultado['statusSefaz'] = linkParaGoogle($retornoEmiteNota['mensagem']);
            }

        } else {
            $mensagemErro .= $retornoEmiteNota['mensagem'];
        }

        if ($retornoEmiteNota['sucesso']) {
            $resultado['sucesso'] = 1;
            $botoesHtml .= $o->button("{icon: file-code; caption: Baixar XML; hint: Baixar xml da NF-e; style: primary; size: normal;}", "javascript: btnObterXml(".$idNfeEstorno.")");
            $botoesHtml .= $o->button("{icon: file-pdf; caption: Imprimir danfe; hint: Gerar danfe da NF-e; style: info; size: normal;}", "javascript: btnImprimirDanfe(".$idNfeEstorno.")");

            $resultado['botoesHtml'] = $botoesHtml;
        }

        if ($mensagemErro) {
            $resultado["msgErro"] = $mensagemErro;
        }

        echo json_encode($resultado);

        $sql = "UPDATE nfe
                SET situacao = '" . gCleanField($retornoEmiteNota['detalhes']['situacao']) . "',
                    chave = '" . gCleanField($retornoEmiteNota['detalhes']['chave']) . "',
                    protocolo = '" . gCleanField($retornoEmiteNota['detalhes']['protocolo']) . "',
                    data_recibo = '" . gCleanField($retornoEmiteNota['detalhes']['dataHoraRecebimento']) . "'
                WHERE id = " . $idNfeEstorno;
        dbFastQuery($sql);

        $sql = "UPDATE notas
                SET numero = " . $dados['NfeNumeroEOperacao']['numeroNota'] . "
                WHERE id = " . $idNovaNota;
        dbFastQuery($sql);

        $dadosEventos = [];
        $dadosEventos['id_nfe']               = $idNfeEstorno;
        $dadosEventos['id_notas_saida']       = $idNotaOrigem;
        $dadosEventos['id_pessoas']           = $usrId;
        $dadosEventos['id_nfe_tipos_eventos'] = 5; // 5 = Estorno
        $dadosEventos['xml']                  = base64_decode((string) $retornoEmiteNota['detalhes']['xml']);
        $dadosEventos['data']                 = date('Y-m-d H:i:s');
        $dadosEventos['serie']                = (int) $dados['NfeNumeroEOperacao']['serie'];
        $dadosEventos['sucesso']              = (int) $retornoEmiteNota['sucesso'];
        $dadosEventos['protocolo']            = gCleanField($retornoEmiteNota['detalhes']['protocolo']);
        $dadosEventos['retorno_mensagem']     = gCleanField(removerAcentos($retornoEmiteNota['mensagem'] ?: $mensagemErro));
        dbInsert('nfe_eventos', $dadosEventos);

        $dadosEventos = [];
        $dadosEventos['id_nfe']               = $idNfeEstorno;
        $dadosEventos['id_notas_saida']       = $idNovaNota;
        $dadosEventos['id_pessoas']           = $usrId;
        $dadosEventos['id_nfe_tipos_eventos'] = 5; // 5 = Estorno
        $dadosEventos['xml']                  = base64_decode((string) $retornoEmiteNota['detalhes']['xml']);
        $dadosEventos['data']                 = date('Y-m-d H:i:s');
        $dadosEventos['serie']                = (int) $dados['NfeNumeroEOperacao']['serie'];
        $dadosEventos['sucesso']              = (int) $retornoEmiteNota['sucesso'];
        $dadosEventos['protocolo']            = gCleanField($retornoEmiteNota['detalhes']['protocolo']);
        $dadosEventos['retorno_mensagem']     = gCleanField(removerAcentos($retornoEmiteNota['mensagem'] ?: $mensagemErro));
        dbInsert('nfe_eventos', $dadosEventos);
    }

    if ($_REQUEST['cartaCorrecao']) {
        $sql = "SELECT id, data, motivo FROM nfe_eventos WHERE id_nfe = " . (int) $gId . " AND sucesso = 1 ORDER BY id DESC";
        $dadosEvento = dbFastQuery($sql);

        if ($dadosEvento) {
            $html .= $o->label("Cartas de correção emitidas");
            $html .= $o->tableBegin('big', true);
            $html .= $o->tableRow(["Imprimir", "Correção", "Data"], 'header');

            foreach ($dadosEvento as $dadoEvento) {
                $mtz = [];
                $mtz[] = "" . $o->button("{icon: print; hint: Imprimir CCe; style: success; size: small;}", "javascript: btnImprimirCCe(" . $gId . ", " . $dadoEvento['id'] . ")");
                $mtz[] = "<-" . $dadoEvento['motivo'];
                $mtz[] = "" . gDateTime($dadoEvento['datahora']);
                $html .= $o->tableRow($mtz,'detail');
            }

            $html .= $o->tableEnd();
            $html .= $o->br();
        }

        $frm= new gForm("{columns: 12; id:formCCe; onClickSubmit: btnConfirmaCCe;}");
        $frm->row(
            $frm->add("{type: textarea; name: cce_correcao; fieldLabel: Carta de correção; hint: Deve conter no mínimo 15 caracteres;}")
        );

        $frm->add("{type: hidden; name: cancela_gId; value:".$gId.";}");
        $frm->add("{type: hidden; name: cancela_gIdNota; value:".$_REQUEST["gIdNota"].";}");
        $frm->addButton("{icon: arrow-left; title: Voltar; style: success; size: normal;", "javascript: btnOpcoesNFE(".$gId.", ".$_REQUEST['gIdNota'].")");
        echo $frm->render($o);
        exit;
    }

    if ($_REQUEST['cartaCorrecaoConfirmar']) {
        $validaFilial = $nf->validarFilialSessao($_REQUEST["gIdNota"]);
        if (!$validaFilial['sucesso']) {
            $htm = $o->msgDanger($validaFilial['msg']);
            $htm .= $o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: info; size: normal;}", "javascript: btnCCe(" . $gId . ", " . $_REQUEST["gIdNota"] . ")");
            echo json_encode($htm);
            exit;
        }

        $sql = "SELECT
                    nfe.protocolo,
                    nfe.chave,
                    notas.id_pessoas_proprietario,
                    notas.id AS id_notas
                FROM nfe
                LEFT JOIN notas ON notas.id = nfe.id_notas
                WHERE nfe.id = '{$gId}'";
        $nfeBD = dbFastQuery($sql)[0];

        $numeroSequencial = (int) $nf->obtemNumeroSequencial($gId);

        // TODO:: Verificar se vai ter mais validações
        $statusErro = false;
		if (strlen((string) $_REQUEST['cce_correcao']) < 15 || strlen((string) $_REQUEST['cce_correcao']) > 1000) {
			$statusErro = true;
			$msgErro[] = "O texto da correção deve ter entre 15 e 1000 caracteres!";
		}

        if ($numeroSequencial < 1 || $numeroSequencial > 99) {
            $statusErro = true;
            $msgErro[] = "O número sequencial da correção deve ter entre 1 e 99 caracteres!";
        }

        if ($statusErro) {
            $htm="A CCe não pode ser enviada pois aconteceram os seguintes erros:";
            $htm.=$o->ul($msgErro);
            $htm=$o->msgDanger($htm);
            $htm.=$o->button("{icon: arrow-left; caption: Tentar novamente; hint: Tentar novamente; style: info; size: normal;", "javascript: btnCCe(".$gId.", ".$_REQUEST["gIdNota"].")");
            echo json_encode($htm);
            exit;
        }

        $dadosCartaCorrecao = [];
        $dadosCartaCorrecao['temRetorno']   = 1;
        $dadosCartaCorrecao['chave']        = $nfeBD['chave'];
        $dadosCartaCorrecao['sequencial']   = $numeroSequencial;
        $dadosCartaCorrecao['correcao']     = gCleanField($_REQUEST["cce_correcao"]);
        $dadosCartaCorrecao['empresa']      = $nf->obtemDadosEmpresa(obtemIdEmpresa($nfeBD["id_pessoas_proprietario"]));
        $dadosCartaCorrecao['config']       = $nf->buscarConfiguracoes($dadosCartaCorrecao['empresa']['cnpjFilial']);
        $dadosCartaCorrecao['idPessoasProprietario'] = 1;

        $retornoCartaCorrecao = dispararGatilho('cartaCorrecao', $dadosCartaCorrecao);

        $mensagemErro = '';
        if ($retornoCartaCorrecao['erroCurl'] && !$retornoCartaCorrecao['resposta']) {

            $mensagemErro = "Falha ao conectar com o servidor";
            if (!empty($retornoCartaCorrecao['erroCurl'])) {
                $mensagemErro = gCleanField($retornoCartaCorrecao['erroCurl']);
            }
        }

        $retornoCartaCorrecao = json_decode((string) $retornoCartaCorrecao['resposta'], true);

        $mtz = [];
        $mtz['id_nfe']               = $gId;
        $mtz['id_notas_saida']       = $nfeBD['id_notas'];
        $mtz['id_pessoas']           = $usrId;
        $mtz['id_nfe_tipos_eventos'] = 3; // 3 = carta de correção
        $mtz['data']                 = date('Y-m-d H:i:s');
        $mtz['serie']                = (int) $dadosCartaCorrecao['config']['serie'];
        $mtz['motivo']               = gCleanField($_REQUEST['cce_correcao']);
        $mtz['sequencial']           = $numeroSequencial;
        $mtz['retorno_mensagem']     = gCleanField(removerAcentos($retornoCartaCorrecao['mensagem'] ?: $mensagemErro));
        $mtz['sucesso']              = (int) gCleanField($retornoCartaCorrecao['sucesso']);
        if (gCleanField($retornoCartaCorrecao['sucesso'])) {
            $mtz['xml']       = base64_decode((string) $retornoCartaCorrecao['detalhes']['xml']);
            $mtz['protocolo'] = $retornoCartaCorrecao['detalhes']['protocolo'];
        }

        $idCce = dbInsert("nfe_eventos", $mtz, true);

        if (gCleanField($retornoCartaCorrecao['sucesso']) && $idCce) {
            $modal = "Evento registrado e vinculado a NF-e";
            $modal = $o->msgSuccess($modal);
            $modal .= $o->button("{icon: print; caption: Imprimir CCe ; hint: Imprimir CCe; style: success; size: small;", "javascript: btnImprimirCCe(".$gId.", ".$idCce.")");
        } else {
            $modal  = "A CCe não pode ser enviada pois aconteceram os seguintes erros:";

            $msgErroApi = isset($retornoCartaCorrecao['detalhes']['codigo'])
                ? "Código: " . gCleanField($retornoCartaCorrecao['detalhes']['codigo']) . " - " . gCleanField($retornoCartaCorrecao['mensagem'])
                : gCleanField($retornoCartaCorrecao['mensagem']);

            $msgErroApi .= $mensagemErro;

            $modal .= $o->ul([$msgErroApi]);
            $modal  = $o->msgDanger($modal);
            $modal .= $o->button(
                "{icon: arrow-left; caption: Tentar novamente; hint: Tentar novamente; style: info; size: normal;}",
                "javascript: btnCCe(" . $gId . ", " . gCleanField($_REQUEST["gIdNota"]) . ")"
            );
        }

        echo json_encode($modal);
        exit;
    }

    if ($_REQUEST['imprimirCartaCorrecao']) {
        $idCce = (int) $_REQUEST['idCce'];

        if ($idCce == 0) {
            $html .= $o->msgTitle("Notas fiscais internas");
            $html .= $o->msgDanger("Erro: O id da carta de correção não foi encontrado!");
            $html .= $o->button("{name: back; icon: arrow-left; title: Voltar; style: default; href: " . $o->page . "&gPage=" . NFE . "&gId=" . $_REQUEST['idNota'] . "}");
            return;
        }

        $sql = "SELECT
                    nfe_eventos.*,
                    nfe.chave,
                    notas.id_pessoas_proprietario
                FROM nfe_eventos
                LEFT JOIN nfe ON nfe.id = nfe_eventos.id_nfe
                LEFT JOIN notas ON notas.id = nfe.id_notas
                WHERE nfe_eventos.id_nfe = {$gId}
                    AND nfe_eventos.sucesso = 1
                    AND nfe_eventos.id = {$idCce}";
        $rs = dbFastQuery($sql)[0];

        if (!$rs) {
            $html .= $o->msgTitle("Notas fiscais internas");
            $html .= $o->msgDanger("Erro: Carta de correção (ID: " . $idCce . ") não encontrada ou não pertence a esta NFe (ID: " . $gId . ").");
            $html .= $o->button("{name: back; icon: arrow-left; title: Voltar; style: default; href: " . $o->page . "&gPage=" . NFE . "&gId=" . $rs['id_notas'] . "}");
            return;
        }

        $dadosDanfeCartaCorrecao = [];
        $dadosDanfeCartaCorrecao['xml']        = $rs['xml'];
        $dadosDanfeCartaCorrecao['chave']      = $rs['chave'];
        $dadosDanfeCartaCorrecao['config']     = $nf->buscarConfiguracoes($dadosDanfeCartaCorrecao['empresa']['cnpjFilial']);
        $dadosDanfeCartaCorrecao['empresa']    = $nf->obtemDadosEmpresa(obtemIdEmpresa($rs['id_pessoas_proprietario']));
        $dadosDanfeCartaCorrecao['temRetorno'] = 1;
        $dadosDanfeCartaCorrecao['idPessoasProprietario'] = 1;

        $retornoGerarDanfeCartaCorrecao = dispararGatilho('gerarDanfeCce', $dadosDanfeCartaCorrecao);

        $mensagemErro = '';
        if ($retornoGerarDanfeCartaCorrecao['erroCurl'] && !$retornoGerarDanfeCartaCorrecao['resposta']) {
            $mensagemErro = "Falha ao conectar com o servidor";
            if (!empty($retornoGerarDanfeCartaCorrecao['erroCurl'])) {
                $mensagemErro = gCleanField($retornoGerarDanfeCartaCorrecao['erroCurl']);
            }
        }

        $retornoGerarDanfeCartaCorrecao = json_decode((string) $retornoGerarDanfeCartaCorrecao['resposta'], true);

        if ($retornoGerarDanfeCartaCorrecao['sucesso']) {
            $pdf = base64_decode((string) $retornoGerarDanfeCartaCorrecao['detalhes']['pdf_base64']);

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="danfe_' . $rs['chave'] . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            ob_clean();
            flush();
            echo $pdf;
        } else {
            $html .= $o->msgTitle("Notas fiscais internas");
            if (!$mensagemErro) {
                $mensagemErro = (gCleanField($retornoGerarDanfeCartaCorrecao['mensagem']) ?: 'Arquivo não gerado');
            }

            $mensagemErro = $o->ul([$mensagemErro]);
            $html .= $o->msgDanger("Erro ao gerar o danfe da carta de correção: " . $mensagemErro);
            $html .= $o->button("{name: back; icon: arrow-left; title: Voltar; style: default; href: " . $o->page . "&gPage=" . NFE . "&gId=" . $rs['id_notas'] . "}");
            return;
        }
    }

    exit; // NÃO APAGUE ESTE EXIT

}


if (!$gAjs) {
    $html  .= $o->msgTitle("Notas fiscais internas");
}

if ($gPage < 10) {
    $o->PDFEnabled=true;
    $o->DOCEnabled=true;
    $o->XLSEnabled=true;
    $o->CSVEnabled=true;
}

$idNotaFiscal = (int) ($_REQUEST['gIdNota'] ?: $_REQUEST['gId']);

if ($gId > 0) {
    $sql = "SELECT
                notas.refNfe,
                notas.id AS idNota,
                nfe.id AS idNfe,
                nfe.situacao
            FROM notas
            INNER JOIN nfe ON nfe.id_notas = notas.id
            WHERE notas.id = " . intval($idNotaFiscal);
    $confereNFE = dbFastQuery($sql);
    if ($confereNFE
        && in_array($confereNFE[0]["situacao"], ["Submetida", "Aprovada", "Cancelada"])
        && $confereNFE[0]["situacao"] != "Submetida"
    ) {
        $nf->bloquearNotaFiscal();
    }
}


if ($gPage == NFE) {
    $o->addJavascript("

        function btnImprimirDanfe(idNFE)
        {
            let pdfDanfe = '".$o->page."&gAjax=1&gId='+idNFE+'&gerarDanfe=1';
            window.open(pdfDanfe, '_blank');
            hideWait();
        }


        function btnImprimirCCe(idNFE, idCce)
        {
            let pdfDanfeCce = '".$o->page."&gAjax=1&gId='+idNFE+'&idCce='+idCce+'&imprimirCartaCorrecao=1&idNota=" . $gId . "';
            window.open(pdfDanfeCce, '_blank');
        }


        function btnObterXml(idNFE)
        {
            let rota = '".$o->page."&gPage=".NFE_OBTER_XML."&gId='+idNFE;
            window.open(rota, '_blank');
            hideWait();
        }


        function btnTentarNFeNovamente(idNFe, idNota)
        {
            reloadPage(idNFe, idNota, true);
        }


        function btnCancelarNFE(idNFE, idNota)
        {
            $.ajax({
                method: 'GET',
                url: '".$o->page."&gAjax=1&gId='+idNFE+'&gIdNota='+idNota+'&cancelarNfe=1',
                success: function (resp) {
                    document.getElementById('contentModalOpcoesNFe').innerHTML=resp;
                },
                error: function (resp) {
                }
            });
        }


        function btnConfirmaCancelarNFE() {
            showWait();
            let gId = document.getElementById('cancela_gId').value;
            let gIdNota = document.getElementById('cancela_gIdNota').value;
            let motivo = document.getElementById('motivoCancelamento').value;

            $.ajax({
                url: '".$o->page."&gAjax=1&gId='+gId+'&cancelarNfeConfirmar=1',
                method: 'POST',
                data: {
                    motivo: motivo,
                    gIdNota: gIdNota
                },
                success: function (resp) {
                    hideWait();
                    let data = JSON.parse(resp);

                    if (data.sucesso) {
                        let html = '".$o->msgSuccess("' + data.mensagem + '")."';
                        document.getElementById('contentModalOpcoesNFe').innerHTML = html;
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        let html = '".$o->msgDanger("' + data.mensagem + '")."';
                        html += '".$o->button("{icon: arrow-left; caption: Tentar novamente; hint: Tentar novamente; style: info; size: normal;}", "javascript: btnCancelarNFE(' + gId + ', ' + gIdNota + ')")."';
                        document.getElementById('contentModalOpcoesNFe').innerHTML = html;
                    }
                },
                error: function (resp) {
                    hideWait();
                    console.error('Erro:', resp);
                    let html = '".$o->msgDanger("Erro ao processar cancelamento")."';
                    document.getElementById('contentModalOpcoesNFe').innerHTML = html;
                }
            });
        }


        function btnCCe(idNFE, idNota)
        {
            $.ajax({
                method: 'GET',
                url: '".$o->page."&gAjax=1&gId='+idNFE+'&gIdNota='+idNota+'&cartaCorrecao=1',
                success: function (resp) {
                    document.getElementById('contentModalOpcoesNFe').innerHTML=resp;
                },
                error: function (resp) {
                }
            });
        }


        function modalEnvioEstorno(url)
        {
            const modal = document.createElement('div');
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0, 0, 0, 0.6)';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            modal.style.zIndex = '9999';
            modal.style.fontFamily = 'Arial, sans-serif';
            modal.style.padding = '5%';

            const box = document.createElement('div');
            box.style.backgroundColor = '#fff';
            box.style.color = '#333';
            box.style.padding = '3%';
            box.style.borderRadius = '0.19em';
            box.style.width = '90%';
            box.style.maxWidth = '560px';
            box.style.boxShadow = '0 0 12px rgba(0,0,0,0.4)';
            box.style.fontSize = '1em';

            const titulo = document.createElement('h3');
            titulo.innerText = 'Consultando SEFAZ...';
            titulo.style.margin = '0 0 0.75em 0';
            titulo.style.fontSize = '1.15em';
            titulo.style.fontWeight = '600';
            box.appendChild(titulo);

            const modalText = document.createElement('p');
            modalText.innerHTML = '<b>Status:</b> Iniciando...';
            modalText.style.lineHeight = '1.5';
            modalText.style.margin = '0';
            box.appendChild(modalText);

            const linha = document.createElement('div');
            linha.style.height = '1px';
            linha.style.background = '#e0e0e0';
            linha.style.margin = '1.3em 0';
            box.appendChild(linha);

            const botoesAcao = document.createElement('div');
            botoesAcao.style.display = 'none';
            botoesAcao.style.marginBottom = '1em';
            box.appendChild(botoesAcao);

            const btnFechar = document.createElement('button');
            btnFechar.innerText = 'Fechar';
            btnFechar.style.padding = '0.6em 1.3em';
            btnFechar.style.background = '#333';
            btnFechar.style.border = 'none';
            btnFechar.style.color = '#fff';
            btnFechar.style.borderRadius = '4px';
            btnFechar.style.cursor = 'pointer';
            btnFechar.style.fontSize = '1em';
            btnFechar.style.transition = 'background 0.2s';
            btnFechar.onmouseover = () => btnFechar.style.background = '#555';
            btnFechar.onmouseout = () => btnFechar.style.background = '#333';
            btnFechar.onclick = () => {
                modal.remove();
                location.reload();
            };

            const botoes = document.createElement('div');
            botoes.style.display = 'flex';
            botoes.style.justifyContent = 'flex-end';
            botoes.appendChild(btnFechar);

            box.appendChild(botoes);
            modal.appendChild(box);
            document.body.appendChild(modal);

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json'
            })
            .done(function(data) {
                const campos = [
                    { chave: 'statusSubmetida', label: 'Submissão da NF-e' },
                    { chave: 'modoOperacao', label: 'Modo de operação' },
                    { chave: 'statusMontagem', label: 'Montagem do XML' },
                    { chave: 'statusAssinado', label: 'Assinatura digital' },
                    { chave: 'statusValidado', label: 'Validação do XML' },
                    { chave: 'statusXmlEmEnvio', label: 'Envio do XML' },
                    { chave: 'statusXmlEnviado', label: 'XML enviado' },
                    { chave: 'statusSefaz', label: 'Retorno da SEFAZ' },
                    { chave: 'statusEmail', label: 'E-mail de NF' },
                    { chave: 'msgErro', label: 'Erro' }
                ];

                modalText.innerHTML = campos
                    .filter(function(campo) { return data[campo.chave]; })
                    .map(function(campo) { return '<b>' + campo.label + ':</b> ' + data[campo.chave]; })
                    .join('<br>');

                if (data.sucesso) {
                    titulo.innerText = '✅ NF-e autorizada com sucesso';
                    titulo.style.color = '#28a745';

                    if (data.botoesHtml) {
                        botoesAcao.innerHTML = data.botoesHtml;
                        botoesAcao.style.display = 'block';
                    }
                } else {
                    titulo.innerText = '❌ Falha na emissão da NF-e';
                    titulo.style.color = '#dc3545';
                }

                btnFechar.disabled = false;
            })
            .fail(function(xhr, status, error) {
                titulo.innerText = 'Erro ao processar solicitação';
                titulo.style.color = '#dc3545';

                let mensagemErro = '';
                if (xhr.responseJSON && xhr.responseJSON.mensagem) {
                    mensagemErro = xhr.responseJSON.mensagem;
                } else if (xhr.responseJSON && xhr.responseJSON.msgErro) {
                    mensagemErro = xhr.responseJSON.msgErro;
                } else {
                    mensagemErro = 'Ocorreu um erro ao processar a solicitação. ' + xhr.responseText;
                }

                modalText.innerHTML = mensagemErro;
                btnFechar.disabled = false;
            });

            return { modal, titulo, modalText, btnFechar, botoesAcao };
        }


        function bntEstornar(idNFE, idNota)
        {
            dialog = bootbox.dialog({
                title: 'Estornar NFe',
                message: '" . $o->msgAlert("Tem certeza que deseja estornar a NFe") . "',
                closeButton: true,
                buttons: {
                    confirmar: {
                        label: 'Confirmar',
                        className: 'btn-success',
                        callback: function() {
                            dialog.modal('hide');

                            const url = '" . $o->page . "&idNFe=' + idNFE + '&idNota=' + idNota + '&gAjax=1&estornarNfe=1';
                            modalEnvioEstorno(url);
                        }
                    },
                    fechar:{
                        label: 'Fechar',
                        className: 'btn-danger',
                        callback: function() {
                            dialog.modal('hide');
                        }
                    }
                }
            });
        }


        function btnConfirmaCCe()
        {
            showWait();
            let gId=document.getElementById('cancela_gId').value;
            let gIdNota=document.getElementById('cancela_gIdNota').value;
            let cce_correcao =document.getElementById('cce_correcao').value;
            $.ajax({
                url: '".$o->page."&gAjax=1&gId='+gId+'&gIdNota='+gIdNota+'&cartaCorrecaoConfirmar=1',
                method: 'POST',
                data: {
                    gId: gId,
                    gIdNota: gIdNota,
                    cce_correcao: cce_correcao
                },
                success: function (resp) {
                    hideWait();
                    let html=JSON.parse(resp);
                    document.getElementById('contentModalOpcoesNFe').innerHTML=html;
                },
                error: function (resp) {
                    hideWait();
                    console.log(resp);
                }
            });
        }


        function modalNFE(status, msgErro, idNfe, idNota)
        {
            hideWait();
            $('#emitirNFe').modal('show');
            if (status==1) {
                document.getElementById('modalEmitirNfeContent').innerHTML='Enviando NFE...';
                reloadPage(idNfe, idNota, false);
                return;
            }

            document.getElementById('modalEmitirNfeContent').innerHTML=msgErro;
        }


        function modalOpcoesNFe(idNfe, idNota)
        {
            hideWait();
            let rota='".$o->page."&gAjs=1&gPage=".NFE_OPCOES."&gId='+idNfe+'&gIdNota='+idNota;
            $('#opcoesNFE').modal('show');
            $.ajax({
                url: rota,
                method: 'GET',
                success: function (resp) {
                    document.getElementById('contentModalOpcoesNFe').innerHTML=resp;
                },
                error: function (resp) {
                }
            });
        }
    ");
}


switch ($gPage) {
    case INICIO:
        $html .= '<div class="hidden-print"><form id="filtroRapido" class="form-inline" method="POST" action="index.php?g=nf_saida">';
        $html .= $o->button("{style: info; icon: plus; caption: Novo; hint: Nova nota; size: normal; href: index.php?g=nf_saida&gPage=".CRIAR_ATUALIZAR."}");
        $html .= '<input id="pesquisa" name="pesquisa" type="text" class="form-control input-md" placeholder="Pesquisa rápida...">&nbsp;<input type="hidden" name="g" value="nf_saida"><input type="hidden" name="gPage" value="0">';
        $html .= '<input id="action" name="action" type="hidden" class="form-control input-md" value="filtroRapido">';
        $html .= "<input type='hidden' name='filtro' value='1' />";
        $html .= $o->button("{icon: search; caption: Pesquisar; hint: Pesquisa avançada; size: normal;", "javascript:btnPesquisar();");

        $sql = "SELECT
                    ativar_modo_contingencia,
                    data_alteracao_operacao,
                    pessoas.nome
                FROM filial_notas
                LEFT JOIN pessoas ON pessoas.id = filial_notas.id_pessoas_alterou
                WHERE id_filial = " . $_SESSION['filialAtualId'] . " LIMIT 1";
        $modoContingencia = dbFastQuery($sql)[0];

        if ($modoContingencia['ativar_modo_contingencia']) {
            $mensagem = $o->msgWarning("Você deseja realmente <b>desativar</b> o modo de contingência?");
            $html .= $o->button("{caption: Desativar contingência; hint: Desativar contingência; icon: bell-slash; style: success;}", "javascript:opemModal(" . $modoContingencia['ativar_modo_contingencia'] . ", 'confirmarAtivarDesativarContingencia');");
        } else {
            $mensagem = $o->msgWarning("Você deseja realmente <b>ativar</b> o modo de contingência?");
            $html .= $o->button("{caption: Ativar contingência; hint: Ativar contingência; icon: bell;}", "javascript:opemModal(" . $modoContingencia['ativar_modo_contingencia'] . ", 'confirmarAtivarDesativarContingencia');");
        }

        $mensagem .= $o->msgInfo("<b>Usuário responsável: </b>" . $modoContingencia['nome'] . "<br><b>Data última alteração: </b>" . gDateTime($modoContingencia['data_alteracao_operacao']));
        $html .= obtemModalConfirmacao("confirmarAtivarDesativarContingencia", $mensagem, "btnCancelarEnviar", "btnConfirmarEnviar", $o->page . "&gPage=" . ATIVAR_DESATIVAR_CONTINGENCIA . "&modoContingencia=" . $modoContingencia['ativar_modo_contingencia']);

        if ($gParam['PERMITIR_AGRUPAMENTO_NOTA_SAIDA']['ativo']) {
            $html .= $o->button('{caption: Agrupar; icon: object-group; href:'.$o->page.'&gPage=' . LISTAR_CLIENTES_AGRUPAMENTO . '; hint: Agrupar notas;}');
        }

        $javascript = "
            function btnPesquisar() {
                let pesquisaRapida = $('[name=\'pesquisa\']').val();
                showWait();
                if (pesquisaRapida) {
                    $('#filtroRapido').submit();
                } else {
                    location.href = 'index.php?g=nf_saida&gPage=' + " . PESQUISAR . ";
                }
            }";

        $o->addJavascript($javascript);
        $html .= '</form></div>';
        $html .= $o->br();
        $frm   = new gForm("columns: 3");
        $urlCancelar    = $o->page."&gPage=" . CANCELAR_NOTA . "&gId=".$gId;
        $conteudoModal  = "Cancelar espelho da NF-e?<br> Este procedimento só cancela no WMS, não cancela na SEFAZ. Para cancelar na SEFAZ abra a nota e cancele-a";
        $conteudoModal .= "<input type='hidden' name='id_nfe' id='id_nfe'/>";
        $html .= $o->modal("{title: Confirmação; cancelCaption: Fechar; url:btnConfirmarCancelarNFE(); confirm: true; name: modalCancelarNFE; size:large; }", $conteudoModal);

        if ($_REQUEST["filtro"] || $_REQUEST["pesquisa"]) {
            $_POST['id_notas_agrupar'] = 0;
            if ($_REQUEST["action"] == "filtroRapido") {
                $_POST['cancelada'] = 0;
                $where= $nf->obtemBusca($_POST, 1);
                $rs   = $nf->obtemRegistros("N.id DESC", $where);
                $html .= $o->msgFilter("Pesquisar por: " . $_REQUEST['pesquisa']);
            } else {
                $nf->inner_item = true;
                if (isset($req['cancelada'])) {
                    $_POST['cancelada'] = 0;
                }

                $filtro = $nf->obtemBusca($_POST, 2);
                $where  = $filtro["where"];
                $cabecalho = $filtro["cabecalho"];
                if ($cabecalho) {
                    $html .= $o->msgFilter("Filtros selecionados: " . implode(' • ', $cabecalho));
                }

                $rs = $nf->obtemRegistros("N.id DESC", $where);
            }
        } else {
            $where = " (N.tipo='S')
                        AND (N.id_filial=".intval($_SESSION["filialAtualId"]).")
                        AND N.id_notas_agrupar=0";
            $rs = $nf->obtemRegistros("N.id DESC", $where);
        }

        if ($gParam["PAGINACAO"]["ativo"] == 1) {
            $html .= $nf->pagination->render('{style:margin-top:-1.6%;;}');
        }

        $html .= $nf->obtemTabelaPrincipal($rs);
        if ($gParam["PAGINACAO"]["ativo"] == 1) {
            $html .= $nf->pagination->render('{id:o;style:margin-top:-1.4%;;}');
        }

        break;


    case DADOS:
        $nota  = $nf->obtemRegistro($gId);
        $frm   = $nf->geraCamposDoFormularioNota($nota, SALVAR);
        $html .= $nf->obtemCabecalho($nota);
        $html .= $frm->render($o);
        break;


    case CANCELAR_NOTA:
        $sql="SELECT
                    notas.*,
                    nfe.situacao
              FROM notas
              LEFT JOIN nfe ON nfe.id = notas.id_nfe
              WHERE notas.id=".$gId;
        $nota=(dbQuery($sql)[0]);
        if ($nota["situacao"] == "Aprovada") {
            $html.=$o->msgDanger("Não é possível cancelar a nota pois uma nota fiscal eletrônica já foi aprovada, por favor cancele a NFe, e tente novamente");
            return;
        }

        // Cancelando nota
        $mtz = [];
        $mtz["cancelada"]=1;
        dbUpdate("notas", $mtz, $nota["id"]);
        $sql =
            "UPDATE notas
            SET   id_notas_agrupar = 0,
                  confirmada = 1
            WHERE id_notas_agrupar = ".$nota["id"];
        dbQuery($sql);

        $mtz = [];
        $mtz["cancelada"]=1;
        dbUpdate("nfe", $mtz, $nota["id_nfe"]);
        redirect($o->page . "&gPage=" . INICIO);

        break;


    case ITENS:
        $conteudoModal = "Deseja realmente excluir o item?";
        $conteudoModal .= "<input type='hidden' name='gIdEnd' id='id_notas_itens' value='' />";
        $html .= $o->modal("{title: Confirmação de exclusão; cancelCaption: Fechar; url:btnConfirmarExcluirItem(); confirm: true; name: modalExcluirItem; size:large; }", $conteudoModal);
        $nota  = $nf->obtemRegistro($gId);
        $item  = $nf->obtemNotaItem($gIdEnd);
        $itens = $nf->obtemRegistrosNotasItens($gId); //<- Local onde importa as informações sobre a NFE do item 
        $frm   = $nf->geraFormularioNotaItem($nota, $item);
        $html .= $nf->obtemCabecalho($nota);

        $usuarioPodeEditar = (
            in_array('Acesso total', $_SESSION['permissionsNames'])
            || in_array('Editar Itens Nota', $_SESSION['permissionsNames'])
            || $_SESSION['usrId'] <= 2
        );

        if (!$usuarioPodeEditar) {
            $html.=$o->msgDanger("Não é possível adicionar, editar ou excluir os itens sem a permissão necessária, ou se a nota já estiver vinculada à alguma programação");
            $o->addJavascript("document.getElementsByName('btnExcluirNotaItem')[0].setAttribute('disabled', true);");

            if (!$gIdEnd) {
                $o->addJavascript("
                    document.getElementsByName('submit_default')[0].setAttribute('disabled', true);
                ");
            }
        } else {
            $o->addJavascript("
                function btnConfirmarExcluirItem () {
                    var id_notas_itens=$('#id_notas_itens').val();
                    var rota='".$o->page."&gPage=".ITENS_EXCLUIR."&gId=".$gId."&gIdEnd='+id_notas_itens;
                    location.href=rota;
                }

                function btnExcluirItemNota(idNotaItem)
                {
                    $('#modalExcluirItem').modal('show');
                    $('#id_notas_itens').val(idNotaItem)
                }
            ");
        }

        if ($nota["id_pessoas_proprietario"]) {
            if ($nota["tipo"]=='S' && in_array($nota["situacao"], ['Aprovada', 'Cancelada'])) {
                $html .= "";
            } else {
                $html .= $frm->render($o);
            }

            if ($itens) {
                $html .= $nf->obtemTabelaItem($itens);
            } else {
                $html .= $o->msgInfo("Adicione itens a nota fiscal.");
            }
        } else {
            $html .= $o->msgInfo("Não é possível gerar uma nota sem designar o proprietário");
        }

        return $html;
        break;


    case ITENS_SALVAR:
        if ($gIdEnd) {
            $novoItem = $nf->modificaNotaItem($_POST);
            $item=$nf->obtemNotaItem($gIdEnd);
            $redirect = $o->page . "&gPage=" . ITENS . "&gId=" . $gId . "&gIdEnd=".$gIdEnd;
            userLog('Item: <a href="index.php?g=nf_saida&gPage='.ITENS.'&gId='.$gId.'&gIdEnd='.$gIdEnd.'">'.$item["descricao"].'</a> modificado na nota fiscal id: <a href="index.php?g=nf_saida&gPage='.DADOS.'&gId='.$gId.'">'.$gId.'</a>');
        } else {
            $novoItem = $nf->insereNotaItem($_POST);
            $item=$nf->obtemNotaItem($novoItem["idItem"]);
            $redirect = $o->page . "&gPage=" . ITENS . "&gId=" . $gId;
            userLog('Item: <a href="index.php?g=nf_saida&gPage='.ITENS.'&gId='.$gId.'&gIdEnd='.$item["id"].'">'.$item["descricao"].'</a>  adicionado a nota fiscal id: <a href="index.php?g=nf_saida&gPage='.DADOS.'&gId='.$gId.'">'.$gId.'</a>');
        }

        redirect($redirect);
        break;


    case ITENS_EXCLUIR:
        $item=$nf->obtemNotaItem($gIdEnd);
        $nf->excluirItemNota($gIdEnd);
        userLog('Exclusão do item '.$item['descricao'].' na nota fiscal interna id: <a href="index.php?g=nf_saida?&gPage='.ITENS.'gId='.$gId.'">'.$gId.'</a>');
        redirect($o->page . "&gPage=" . ITENS . "&gId=" . $gId);
        break;


    case FORMULARIO_IMPORTAR_ITENS_NOTA:
		if ($_REQUEST['importar'] == 1) {
			$resultadoImportacao = $nf->importaItensFormulario($o, $backButton);
			if ($resultadoImportacao) {
				$html .= $resultadoImportacao;
				break;
			}
		}

		if ($_REQUEST['modelo']) {
			downloadModeloImportacao($modelo);
		}

		$html .= $nf->gerarFormularioImportacaoItens($o, $backButton);
        $html .= $o->msgInfo('Importe CSV sem título');
		break;


    case PESQUISAR:
        $html .= $nf->geraFormularioFiltro()->render($o);
        break;


    case PESQUISAR_RESULTADO:
        $html .= $nf->obtemTabelaPrincipal($rs);
        break;


    case ITENS_ATUALIZAR_CFOP_ORIGEM:
        //consulta cfops de entrada de todos os itens
        $sql =
            "SELECT notas_associadas.id_cfops,GROUP_CONCAT(notas_itens.id) ids
            FROM notas_itens
            INNER JOIN notas notas_associadas ON notas_associadas.id = notas_itens.id_notas_associada
            WHERE notas_itens.id_notas = {$_REQUEST['gId']}
            GROUP BY notas_associadas.id_cfops";
        $cfops = dbQuery($sql);

        //consulta a nota atual gerando cfops para cada cfop diferente encontrado nos itens
        $nota = dbQuery("SELECT * FROM notas WHERE id = {$_REQUEST['gId']} AND cancelada=0");
        #consulto a nota de entrada ou de saída do item? Ela quem sera inserida
        if (!$nota) {
            $html .= $o->msgAlert('Este procedimento não pode ser efetivado pois esta nota está cancelada');
            $html .= $backButton;
            break;
        }
        $nota = excluirIndicesNumericos($nota[0]);
        $idNotaInserida = [];

        if(count($cfops) > 1){
            unset($nota['id']);
            $counter = count($cfops);
            for ($i=1; $i < $counter; $i++) {
                $nota['id_pessoas_criou']=$_SESSION['usrId'];
                $nota['data_criou'] = date("Y-m-d H:i:s");
                $idNotas = dbInsert('notas', $nota, true);
                $sql="UPDATE notas_itens SET id_notas=".$idNotas." WHERE id IN(".$cfops[$i]['ids'].")";
                dbQuery($sql);
            }
        }

        header('Location: '.$o->page);
        break;


    case CRIAR_ATUALIZAR:
        $rs =  $nf->obtemRegistro($gIdEnd);
        $frm = $nf->geraCamposDoFormularioNota($rs, SALVAR);
        $html .= $frm->render($o);
        break;


    case SALVAR:
        if ($_POST['tpFormulario'] == 2) {
            $nf->modificaNota($_POST);
            if ($nf->obtemErros()) {
                $msg = mostraErros("Não foi possível atualizar a nota fiscal, pois foram encontrados os erros:", $nf->obtemErros());
                $html.= $o->msgDanger($msg);
                $html.=$o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: info; size: normal; href: ".$o->page . "&gPage=" . DADOS . "&gId=" . $gId);
            } else {
                userLog('Nota fiscal interna modificada id: <a href="index.php?g=nf_saida&gPage='.DADOS.'&gId='.$gId.'">'.$gId.'</a>');
                redirect($o->page . "&gPage=" . DADOS . "&gId=" . $gId);
            }
        } else {
            $gId = $nf->insereNota($_POST);
            userLog('Nota fiscal interna adicionada id: <a href="index.php?g=nf_saida&gPage='.DADOS.'&gId='.$gId.'">'.$gId.'</a>');
            redirect($o->page . "&gPage=" . ITENS . "&gId=" . $gId);
        }

        break;


    case IMPORTAR:
        $frm = new gForm();
        $frm->addFormMessage("Importar nota fiscal: ");
        $frm->row(
            $frm->add("{name: cadastroCliente; fieldLabel: Cadastrar cliente automaticamente?; type: checkbox;}")
        );

        $itens = "{Importar apenas nota fiscal, Importar nota fiscal e criar programação}";
        $frm->row(
            $frm->add("{allowBlank: false;name: tipo; fieldLabel: Tipo de importação; type: combo; items: ". $itens ."}"),
            $frm->add("{name: arquivo; type: file;}")
        );
        $frm->add("{name: gPage; type: hidden; value: ". IMPORTAR_SALVAR ." ;}");
        $_SESSION["tipoImportacao"] = 1;
        $html .= $frm->render($o);
        break;


    case MEDICAMENTO:
        $sql = "SELECT * FROM notas_itens_medicamentos WHERE id_notas_itens=".(int) $id_notas_itens;
        $rs = dbQuery($sql)[0];

        $frm = new gForm("{columns:1;}");
        $frm->row($frm->add("{allowBlank: false; name: cProdANVISA; fieldLabel: Código de produtio da ANVISA; maxLength: 15; type: text; value:".$rs['cProdANVISA']."}"));
        $frm->row($frm->add("{allowBlank: false; name: xMotivoIsencao; fieldLabel: Motivo de isenção da ANVISA; type: text;value:".$rs['xMotivoIsencao']."}"));
        $frm->add("{allowBlank: false; name: vPMC; fieldLabel: Preço máximo para o consumidor; type: number;value:".gFloat($rs['vPMC'])."}");
        $frm->add("{allowBlank: false; name: nLote; fieldLabel: Lote; type: text;value:".$rs['nLote']."}");
        $frm->add("{allowBlank: false; name: qLote; fieldLabel: Quantidade do lote; type: number;value:".gFloat($rs['qLote'])."}");
        $frm->add("{allowBlank: false; name: dFab; fieldLabel: Data de fabricação; type: date; maxLength: 8; value:".gDate($rs['dFab'])."}");
        $frm->add("{allowBlank: false; name: dVal; fieldLabel: Data de validade; type: date; maxLength: 8; value:".gDate($rs['dVal'])."}");
        $frm->add("{allowBlank: true; name: cAgreg; fieldLabel: Código de agregação; type: text;value:".$rs['cAgreg']."}");
        $frm->add("{type:hidden; name:id_notas; value: " . $id_notas . "}");
        $frm->add("{type:hidden; name:id_notas_itens; value: " . $id_notas_itens . "}");
        $frm->add("{type:hidden; name:gPage; value: ".MEDICAMENTO_SALVAR.";}");

        $html .= $frm->render($o);
        break;


    case IMPOSTOS:

        $html .= $o->br();
        /* ICMS */
        $sql = "SELECT
                    notas.id AS idNota,
                    nfe.id AS idNfe,
                    nfe.situacao
                FROM notas
                INNER JOIN notas_itens ON notas.id = notas_itens.id_notas
                INNER JOIN nfe ON nfe.id_notas = notas.id
                WHERE notas_itens.id = '{$gId}'";
        $confereNota = dbFastQuery($sql)[0];

        $icms = $nf->obtemDadosIcms($gId);
        $valueIcmsCst = ($icms) ? $icms["id_imp_icms_cst"] : 7;
        $valueIcmsOri = ($icms) ? $icms["id_imp_icms_origem"] : 1;
        $liberar = ($confereNota["situacao"] == 'Reprovada') ? 0 : 1;

        $frm = new gForm("{onClickSubmit: btnICMS; id: formICMS; columns: 1;}");

        // -- Campos Fixos --
        // Adicionado ID explícito 'icms_cst' para o JS encontrar
        $frm->add("{allowBlank: false; name: icms_cst; id: icms_cst; fieldLabel: CST; type: select; items: " . $sp["combo_icms_cst"] . "; value:" . $valueIcmsCst . ";}");
        $frm->add("{allowBlank: false; name: icms_orig; id: icms_orig; fieldLabel: Origem; type: select; items: " . $sp["combo_imp_icms_origem"] . "; value:" . $valueIcmsOri . ";}");
        $frm->add("{allowBlank: false; name: icms_mod; id: icms_mod; fieldLabel: Modalidade; type: select; items: " . $sp["combo_imp_icms_mod"] . "; value:" . $icms["id_imp_icms_mod"] . ";}");

        // -- Grupo Normal (Tributação) --
        $frm->add("{allowBlank: true; name: vBC; fieldLabel: Base de cálculo; type: number; value:".str_replace(".",",",$icms["vBC"]).";}");
        $frm->add("{allowBlank: true; name: icms_aliquota; fieldLabel: Alíquota; type: number; value:".gFloat($icms["pICMS"]).";}");
        $frm->add("{allowBlank: true; name: reducao_icms_aliquota; fieldLabel: Percentual Redução Alíquota; type: number; value:".gFloat($icms["reducao_icms_aliquota"]).";}");

        // -- Grupo ST (Substituição Tributária) --
        $modBMIcmsST = [];
        $modBMIcmsST[0] = "0 - Preço tabelado ou máximo sugerido";
        $modBMIcmsST[1] = "1 - Lista negativa";
        $modBMIcmsST[2] = "2 - Lista positiva";
        $modBMIcmsST[3] = "3 - Lista neutra";
        $modBMIcmsST[4] = "4 - Margem valor agregado";
        $modBMIcmsST[5] = "5 - Pauta";

        $frm->add("{allowBlank: true; name: modBCST; fieldLabel: Modalidade BC ST; type: select; items:'".json_encode($modBMIcmsST)."'; value:".$icms["modBCST"].";}");
        $frm->add("{allowBlank: true; name: pMVAST; fieldLabel: % da margem ICMS ST; type: number; value:".gFloat($icms["pMVAST"]).";}");
        $frm->add("{allowBlank: true; name: pRedBCST; fieldLabel: % da Redução de BC do ICMS ST; type: number; value:".gFloat($icms["pRedBCST"]).";}");
        $frm->add("{allowBlank: true; name: vBCST; fieldLabel: Valor da BC do ICMS ST; type: number; value:".gFloat($icms["vBCST"]).";}");
        $frm->add("{allowBlank: true; name: pICMSST; id: pICMSST; fieldLabel: Alíquota do imposto do ICMS ST; type: number; value:".gFloat($icms["pICMSST"]).";}"); // O Label deste muda via JS
        $frm->add("{allowBlank: true; name: vICMSST; fieldLabel: Valor ICMS ST; type: number; value:".gFloat($icms["vICMSST"]).";}");

        // -- Grupo FCP (Fundo Combate Pobreza) --
        $frm->add("{allowBlank: true; name: vBCFCP; fieldLabel: Valor da BC do FCP; type: number; value:".gFloat($icms["vBCFCP"]).";}");
        $frm->add("{allowBlank: true; name: pFCP; fieldLabel: Percentual do FCP; type: number; value:".gFloat($icms["pFCP"]).";}");
        $frm->add("{allowBlank: true; name: vBCFCPST; fieldLabel: Valor da BC do FCP retido por ST; type: number; value:".gFloat($icms["vBCFCPST"]).";}");
        $frm->add("{allowBlank: true; name: pFCPST; fieldLabel: % do FCP retido por ST; type: number; value:".gFloat($icms["pFCPST"]).";}");

        // -- Grupo Retido (Anteriormente) --
        $frm->add("{allowBlank: true; name: vBCSTRet; fieldLabel: Valor da BC do ICMS ST retido; type: number; value:".gFloat($icms["vBCSTRet"]).";}");
        $frm->add("{allowBlank: true; name: vICMSSTRet; fieldLabel: Valor do ICMS ST retido anteriormente; type: number; value:".gFloat($icms["vICMSSTRet"]).";}");

        // -- Campos Ocultos de Controle --
        $frm->add("{type: hidden; name: gPage; value: " . IMPOSTOS_SALVAR . "}");
        $frm->add("{type: hidden; name: tipo; value: icms}");
        $frm->add("{type: hidden; name: gIdItem; value: " . $gId . "}");
        $frm->add("{type: hidden; name: id_notas_itens_icms; value: ".$icms["id"].";}");

      if ($confereNota) {
            $frm->add("type: hidden; name: icms_liberar; value: " . $liberar . ";");
            $frm->add("type: hidden; name: icms_situacao; value:" . $confereNota["situacao"] . ";");
        } else {
            $frm->add("type: hidden; name: icms_liberar; value: 0;");
            $frm->add("type: hidden; name: icms_situacao; value:;");
        }

        $abaIcms = $frm->render($o);

        /* IPI */
        $ipi = $nf->obtemDadosIpi($gId);
        $valueIpiCst = ($ipi) ? $ipi["id_imp_ipi_cst"] : 1;
        $frm = new gForm("{id:formIPI; onClickSubmit: btnIPI; columns: 1}");
        $frm->add("{type: select; name: id_imp_ipi_cst; fieldLabel: CST; items:".$sp["combo_imp_ipi_cst"]."; value:".$valueIpiCst.";}");
        $frm->add("{type: number; name: ipi_pIPI; fieldLabel: Alíquota; value:".gFloat($ipi["pIPI"]).";}");
        $frm->add("{type: number; name: ipi_cEnq; fieldLabel: Enquadramento fiscal; value:".$ipi["cEnq"].";}");
        $frm->add("{type: hidden; name: tipo; value: ipi}");
        $frm->add("{type: hidden; name: ipi_gIdItem; value: " . $gId . "}");
        $frm->add("{type: hidden; name: id_notas_itens_ipi; value:".$ipi["id"].";}");

        if ($confereNota) {
            $frm->add("type: hidden; name: ipi_liberar; value: " . $liberar . ";");
            $frm->add("type: hidden; name: ipi_situacao; value:" . $confereNota["situacao"] . ";");
        } else {
            $frm->add("type: hidden; name: ipi_liberar; value: 0;");
            $frm->add("type: hidden; name: ipi_situacao; value:;");
        }

        $abaIpi = $frm->render($o);

        /* PIS */
        $pis = $nf->obtemDadosPis($gId);
        $valuePisCst = ($pis) ? $pis["id_imp_pis_cst"] : 1;
        $frm = new gForm("{id:formPIS; onClickSubmit: btnPIS; columns: 1}");
        $frm->add("{type: select; name: id_imp_pis_cst; fieldLabel: CST; value:".$valuePisCst."; items:".$sp["combo_imp_pis_cst"].";}");
        $frm->add("{type: number; name: pis_pPIS; fieldLabel: Alíquota; value:".gFloat($pis["pPIS"]).";}");
        $frm->add("{type: hidden; name: tipo; value: pis}");
        $frm->add("{type: hidden; name: pis_gIdItem; value: " . $gId . "}");
        $frm->add("{type: hidden; name: id_pis; value:".$pis["id"].";}");

        if ($confereNota) {
            $frm->add("type: hidden; name: pis_liberar; value: " . $liberar . ";");
            $frm->add("type: hidden; name: pis_situacao; value:" . $confereNota["situacao"] . ";");
        } else {
            $frm->add("type: hidden; name: pis_liberar; value: 0;");
            $frm->add("type: hidden; name: pis_situacao; value:;");
        }

        $abaPis = $frm->render($o);

        /* COFINS */
        $cofins = $nf->obtemDadosCofins($gId);
        $valueCofinsCst = ($cofins) ? $cofins["id_imp_cofins_cst"] : 1;
        $frm = new gForm("{id: formCOFINS; onClickSubmit: btnCOFINS; columns: 1}");
        $frm->add("{type: select; name: id_imp_cofins_cst; fieldLabel: CST; value: ".$valueCofinsCst."; items:" . $sp["combo_imp_cofins_cst"] . ";}");
        $frm->add("{type: number; name: cofins_pCOFINS; fieldLabel: Alíquota; value: " . gFloat($cofins["pCOFINS"]) . ";}");
        $frm->add("{type: hidden; name: tipo; value: cofins}");
        $frm->add("{type: hidden; name: cofins_gIdItem; value: " . $gId . "}");
        $frm->add("{type: hidden; name: id_cofins; value:" . $cofins["id"] . ";}");

        if ($confereNota) {
            $frm->add("type: hidden; name: cofins_liberar; value: " . $liberar . ";");
            $frm->add("type: hidden; name: cofins_situacao; value:" . $confereNota["situacao"] . ";");
        } else {
            $frm->add("type: hidden; name: cofins_liberar; value: 0;");
            $frm->add("type: hidden; name: cofins_situacao; value:;");
        }

        $abaCofins = $frm->render($o);

        /* IS  - NAO APAGUE ESTE CODIGO - PRECISAREMOS DELE EM 2026*/
        // $is = $nf->obterDadosIs($gId);
        // $valueIsCst = ($is) ? $is["id_imp_cofins_cst"] : 1;
        // $frm = new gForm("{id: formIS; onClickSubmit: btnIS; columns: 1}");
        // $frm->add("{type: select; name: id_imp_is_cst; fieldLabel: CST IS; value: " . $valueIsCst . "; items:" . $sp["combo_imp_cofins_cst"] . ";}"); // Mudar
        // $frm->add("{type: select; name: id_cclass_trib_is; fieldLabel: Classe Tributária IS (id_cclass_trib_is); value: " . $valueIsCst . "; items:" . $sp["combo_imp_cofins_cst"] . ";}"); // Mudar
        // $frm->add("{type: number; name: pIS; fieldLabel: Alíquota IS (pIS); value: " . gFloat($is["pIS"]) . ";}");
        // $frm->add("{type: number; name: vBCIS; fieldLabel: Base de Cálculo IS (vBCIS); value: " . gFloat($is["vBCIS"]) . ";}");
        // $frm->add("{type: number; name: vIS; fieldLabel: Valor IS (vIS); value: " . gFloat($is["vIS"]) . ";}");
        // $frm->add("{type: number; name: uTrib; fieldLabel: Unidade Tributável (uTrib); value: " . gFloat($is["uTrib"]) . ";}");
        // $frm->add("{type: number; name: qTrib; fieldLabel: Quantidade Tributável (qTrib); value: " . gFloat($is["qTrib"]) . ";}");
        // $frm->add("{type: hidden; name: tipo; value: is}");
        // $frm->add("{type: hidden; name: is_gIdItem; value: $gId}");
        // $frm->add("{type: hidden; name: id_is; value:" . $is["id"] . ";}");

        // if ($confereNota) {
        //     $frm->add("type: hidden; name: is_liberar; value: " . $liberar . ";");
        //     $frm->add("type: hidden; name: is_situacao; value:" . $confereNota["situacao"] . ";");
        // } else {
        //     $frm->add("type: hidden; name: is_liberar; value: 0;");
        //     $frm->add("type: hidden; name: is_situacao; value:;");
        // }

        // $abaIs = $frm->render($o);

        // IBS/CBS
        $ibsCbs = $nf->obterDadosIbsCbs($gId);
        $valueIbsCst = ($ibsCbs) ? $ibsCbs["id_imp_ibs_cbs_cst"] : 1;
        $valueClassTrib = ($ibsCbs) ? $ibsCbs["id_cclasstrib_ibs_cbs"] : 1;

        $frm = new gForm("{id: formIbsCbs; onClickSubmit: btnIbsCbs; columns: 1}");

        $comboCclassTrib = "SELECT id, CONCAT(codigo,' - ',descricao) FROM cclasstrib_ibs_cbs WHERE ativo = 1 ORDER BY codigo";

        $frm->add("{type: select; name: id_imp_ibs_cbs_cst; id: id_imp_ibs_cbs_cst; fieldLabel: CST IBS/CBS; value: " . $valueIbsCst . "; items: " . $sp["combo_imp_ibs_cbs_cst"] . "; allowBlank: false;}");
        $frm->add("{type: select; name: id_cclasstrib_ibs_cbs; id: id_cclasstrib_ibs_cbs; fieldLabel: Classe Tributária; value: " . $valueClassTrib . "; items: " . $comboCclassTrib . "; allowBlank: false;}");

        // BASE DE CÁLCULO (Compartilhada)
        $frm->add("{type: number; name: vBC_IBS_CBS; id: vBC_IBS_CBS; fieldLabel: Base de Cálculo (vBC); value: " . gFloat($ibsCbs["vBC"]) . "; allowBlank: true;}");

        // IBS UF (ESTADUAL)
        $frm->add("{type: number; name: gIBSUF_pIBSUF; id: gIBSUF_pIBSUF; fieldLabel: Alíquota IBS UF (%); value: 0,1;}");
        $frm->add("{type: number; name: gIBSUF_pRedAliq; id: gIBSUF_pRedAliq; fieldLabel: Redução Alíquota IBS UF (%); value: " . gFloat($ibsCbs["gIBSUF_pRedAliq"]) . ";}");
        $frm->add("{type: number; name: gIBSUF_pAliqEfet; id: gIBSUF_pAliqEfet; fieldLabel: Alíquota Efetiva IBS UF (%); value: 0,1;}");
        $frm->add("{type: number; name: gIBSUF_vIBSUF; id: gIBSUF_vIBSUF; fieldLabel: Valor IBS UF (R$); value: 0,1;}");

        // Campos de Diferimento IBS UF
        $frm->add("{type: number; name: gIBSUF_pDif; id: gIBSUF_pDif; fieldLabel: Diferimento IBS UF (%); value: " . gFloat($ibsCbs["gIBSUF_pDif"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: gIBSUF_vDif; id: gIBSUF_vDif; fieldLabel: Valor Diferimento IBS UF (R$); value: " . gFloat($ibsCbs["gIBSUF_vDif"]) . "; allowBlank: true;}");

        // Campo de Devolução IBS UF
        $frm->add("{type: number; name: gIBSUF_vDevTrib; id: gIBSUF_vDevTrib; fieldLabel: Tributo Devolvido IBS UF (R$); value: " . gFloat($ibsCbs["gIBSUF_vDevTrib"]) . "; allowBlank: true;}");

        // IBS MUNICIPAL
        $frm->add("{type: number; name: gIBSMun_pIBSMun; id: gIBSMun_pIBSMun; fieldLabel: Alíquota IBS Municipal (%); value: " . gFloat($ibsCbs["gIBSMun_pIBSMun"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: gIBSMun_pRedAliq; id: gIBSMun_pRedAliq; fieldLabel: Redução Alíquota IBS Municipal (%); value: " . gFloat($ibsCbs["gIBSMun_pRedAliq"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: gIBSMun_pAliqEfet; id: gIBSMun_pAliqEfet; fieldLabel: Alíquota Efetiva IBS Municipal (%); value: " . gFloat($ibsCbs["gIBSMun_pAliqEfet"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: gIBSMun_vIBSMun; id: gIBSMun_vIBSMun; fieldLabel: Valor IBS Municipal (R$); value: " . gFloat($ibsCbs["gIBSMun_vIBSMun"]) . "; allowBlank: true;}");

        // Campos de Diferimento IBS Municipal
        $frm->add("{type: number; name: gIBSMun_pDif; id: gIBSMun_pDif; fieldLabel: Diferimento IBS Municipal (%); value: " . gFloat($ibsCbs["gIBSMun_pDif"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: gIBSMun_vDif; id: gIBSMun_vDif; fieldLabel: Valor Diferimento IBS Municipal (R$); value: " . gFloat($ibsCbs["gIBSMun_vDif"]) . "; allowBlank: true;}");

        // Campo de Devolução IBS Municipal
        $frm->add("{type: number; name: gIBSMun_vDevTrib; id: gIBSMun_vDevTrib; fieldLabel: Tributo Devolvido IBS Municipal (R$); value: " . gFloat($ibsCbs["gIBSMun_vDevTrib"]) . "; allowBlank: true;}");

        // CBS (FEDERAL)
        $frm->add("{type: number; name: gCBS_pCBS; id: gCBS_pCBS; fieldLabel: Alíquota CBS (%); value: " . '0,9' . "; allowBlank: true;}");
        $frm->add("{type: number; name: gCBS_pRedAliq; id: gCBS_pRedAliq; fieldLabel: Redução Alíquota CBS (%); value: " . gFloat($ibsCbs["gCBS_pRedAliq"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: gCBS_pAliqEfet; id: gCBS_pAliqEfet; fieldLabel: Alíquota Efetiva CBS (%); value: " . '0,9' . "; allowBlank: true;}");
        $frm->add("{type: number; name: gCBS_vCBS; id: gCBS_vCBS; fieldLabel: Valor CBS (R$); value: " . '0,9' . "; allowBlank: true;}");

        // Campos de Diferimento CBS
        $frm->add("{type: number; name: gCBS_pDif; id: gCBS_pDif; fieldLabel: Diferimento CBS (%); value: " . gFloat($ibsCbs["gCBS_pDif"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: gCBS_vDif; id: gCBS_vDif; fieldLabel: Valor Diferimento CBS (R$); value: " . gFloat($ibsCbs["gCBS_vDif"]) . "; allowBlank: true;}");

        // Campo de Devolução CBS
        $frm->add("{type: number; name: gCBS_vDevTrib; id: gCBS_vDevTrib; fieldLabel: Tributo Devolvido CBS (R$); value: " . gFloat($ibsCbs["gCBS_vDevTrib"]) . "; allowBlank: true;}");

        // CST 222 - Redução de Base de Cálculo
        $frm->add("{type: number; name: pRedutorBC; id: pRedutorBC; fieldLabel: Redutor de Base de Cálculo (%); value: " . gFloat($ibsCbs["pRedutorBC"]) . "; allowBlank: true;}");

        // CST 620 - Tributação Monofásica
        $frm->add("{type: number; name: qBCMono; id: qBCMono; fieldLabel: Quantidade BC Monofásico; value: " . gFloat($ibsCbs["qBCMono"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: adRemIBS; id: adRemIBS; fieldLabel: Alíquota ad rem IBS (R$); value: " . gFloat($ibsCbs["adRemIBS"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: vIBSMono; id: vIBSMono; fieldLabel: Valor IBS Monofásico (R$); value: " . gFloat($ibsCbs["vIBSMono"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: adRemCBS; id: adRemCBS; fieldLabel: Alíquota ad rem CBS (R$); value: " . gFloat($ibsCbs["adRemCBS"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: vCBSMono; id: vCBSMono; fieldLabel: Valor CBS Monofásico (R$); value: " . gFloat($ibsCbs["vCBSMono"]) . "; allowBlank: true;}");

        // CST 800 - Transferência de Crédito
        $frm->add("{type: number; name: vIBSTransf; id: vIBSTransf; fieldLabel: Valor IBS Transferido (R$); value: " . gFloat($ibsCbs["vIBSTransf"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: vCBSTransf; id: vCBSTransf; fieldLabel: Valor CBS Transferido (R$); value: " . gFloat($ibsCbs["vCBSTransf"]) . "; allowBlank: true;}");

        $tpCredPresIBSZFM = [];
        $tpCredPresIBSZFM[0] = "0 - Sem Crédito Presumido";
        $tpCredPresIBSZFM[1] = "1 - Bens de consumo final (55%)";
        $tpCredPresIBSZFM[2] = "2 - Bens de capital (75%)";
        $tpCredPresIBSZFM[3] = "3 - Bens intermediários (90,25%)";
        $tpCredPresIBSZFM[4] = "4 - Bens de informática (100%)";

        // CST 810 - Ajuste de IBS na ZFM
        $frm->add("{type: select; name: tpCredPresIBSZFM; id: tpCredPresIBSZFM; fieldLabel: Tipo Crédito Presumido ZFM; items: " . json_encode($tpCredPresIBSZFM) . "; value: " . ($ibsCbs["tpCredPresIBSZFM"] ?: '0') . "; allowBlank: true;}");
        $frm->add("{type: number; name: vCredPresIBSZFM; id: vCredPresIBSZFM; fieldLabel: Valor Crédito Presumido ZFM (R$); value: " . gFloat($ibsCbs["vCredPresIBSZFM"]) . "; allowBlank: true;}");

        // CST 811 - Ajustes de Competência
        $frm->add("{type: date; name: competApur; id: competApur; fieldLabel: Competência Apuração (AAAA-MM); value: " . $ibsCbs["competApur"] . "; allowBlank: true;}");
        $frm->add("{type: number; name: vIBSAjuste; id: vIBSAjuste; fieldLabel: Valor Ajuste IBS (R$); value: " . gFloat($ibsCbs["vIBSAjuste"]) . "; allowBlank: true;}");
        $frm->add("{type: number; name: vCBSAjuste; id: vCBSAjuste; fieldLabel: Valor Ajuste CBS (R$); value: " . gFloat($ibsCbs["vCBSAjuste"]) . "; allowBlank: true;}");

        $checked = $ibsCbs["indDoacao"] == 1 ? 'checked' : '';
        if ($confereNota["situacao"] != 'Aprovada') {
            $frm->addHTML('
                <label for="indDoacao" class="control-label text-left">Indicador de Doação</label>
                <div>
                    <label class="switch">
                        <input type="checkbox" name="indDoacao" id="indDoacao" ' . $checked . '>
                        <span class="slider round"></span>
                    </label>
                </div>');
        }

        $frm->add("{type: hidden; name: ibscbs_gIdItem; id: ibscbs_gIdItem; value: " . $gId . "}");
        $frm->add("{type: hidden; name: id_ibs_cbs; id: id_ibs_cbs; value:" . $ibsCbs["id"] . ";}");

        if ($confereNota) {
            $frm->add("type: hidden; name: ibscbs_liberar; id: ibscbs_liberar; value: " . $liberar . ";");
            $frm->add("type: hidden; name: ibscbs_situacao; id: ibscbs_situacao; value:" . $confereNota["situacao"] . ";");
        } else {
            $frm->add("type: hidden; name: ibscbs_liberar; id: ibscbs_liberar; value: 0;");
            $frm->add("type: hidden; name: ibscbs_situacao; id: ibscbs_situacao; value:;");
        }

        $abaIbsCbs = $frm->render($o);

        // Renderização das Abas
        $tabs = [];
        $tabs[] = $o->addTabItem("ICMS", $abaIcms);
        $tabs[] = $o->addTabItem("IPI", $abaIpi);
        $tabs[] = $o->addTabItem("PIS", $abaPis);
        $tabs[] = $o->addTabItem("COFINS", $abaCofins);
        // $tabs[] = $o->addTabItem("IS", $abaIs);
        $tabs[] = $o->addTabItem("IBS/CBS", $abaIbsCbs);
        $html .= $o->tabRender();

        if ($confereNota["situacao"] != 'Aprovada') {
            $html .= '
                <style>
                  .switch { position: relative; display: inline-block; width: 55px; height: 34px; }
                  .switch input { opacity: 0; width: 0; height: 0; }
                  .slider { text-align:right; font-size:12px; color:#fff; padding: 9px; content:"Não"; position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #d60030; -webkit-transition: .4s; transition: .4s; }
                  .slider:before { position: absolute; content: ""; height: 30px; width: 12px; left: 2px; bottom: 2px; background-color: black; -webkit-transition: .4s; transition: .4s; }
                  input:checked + .slider { text-align:left; background-color: #3fb618; }
                  .slider:after { content:"Não"; }
                  input:checked + .slider:after{ content: "Sim"; padding-left:2px; }
                  input:focus + .slider { box-shadow: 0 0 1px #2196F3; }
                  input:checked + .slider:before { -webkit-transform: translateX(39px); -ms-transform: translateX(39px); transform: translateX(39px); }
                  .slider.round { border-radius: 4px; }
                  .slider.round:before { border-radius: 8%; }
                </style>

                <label for="replicar" class="control-label text-left">Replicar dados</label>
                <div>
                <label class="switch">
                    <input type="checkbox" name="replicar" id="replicar">
                    <span class="slider round"></span>
                </label>
                </div>
            ';

        }

        break;


    case IMPOSTOS_SALVAR:
        switch ($_REQUEST["cmd"]) {
            case "salvar":
                $apagaRegistros=false;
                $sql="SELECT COUNT(id) q FROM notas_itens_icms WHERE id_notas_itens=".intval($_REQUEST["gIdItem"]);
                $r=dbQuery($sql)[0];
                if ($r['q']>1 || (int) $_REQUEST["id_notas_itens_icms"] == 0 ) {
                    $apagaRegistros = true;
                    dbQuery("DELETE FROM notas_itens_icms WHERE id_notas_itens=".intval($_REQUEST["gIdItem"]));
                }

                $mtz=[];
                $mtz["id_notas_itens"]=intval($_REQUEST["gIdItem"]);
                $mtz["id_imp_icms_cst"]=intval($_REQUEST["icms_cst"]);
                $mtz["id_imp_icms_origem"]=intval($_REQUEST["icms_orig"]);
                $mtz["id_imp_icms_mod"]=intval($_REQUEST["icms_mod"]);
                $mtz["reducao_icms_aliquota"]=gDBFloat(str_replace(".", ",", $_REQUEST["reducao_icms_aliquota"]));
                $mtz["pICMS"]=gDBFloat(str_replace(".", ",", $_REQUEST["icms_aliquota"]));
                $mtz["vBC"]=gDBFloat(str_replace(".", ",", $_REQUEST["vBC"]));
                $mtz["vICMSST"]=gDBFloat(str_replace(".", ",", $_REQUEST["vICMSST"]));
                $mtz["vICMSSTRet"]=gDBFloat(str_replace(".", ",", $_REQUEST["vICMSSTRet"]));
                $mtz['pICMSST']=gDBFloat(str_replace(".", ",", $_REQUEST["pICMSST"]));

                $mtz['modBCST'] = (int) $_REQUEST['modBCST'] ;
                $mtz['pMVAST'] = gDBFloat($_REQUEST['pMVAST']);
                $mtz['pRedBCST'] = gDBFloat($_REQUEST['pRedBCST']);
                $mtz['vBCST'] = gDBFloat($_REQUEST['vBCST']);
                $mtz['vBCFCP'] = gDBFloat($_REQUEST['vBCFCP']);
                $mtz['pFCP'] = gDBFloat($_REQUEST['pFCP']);
                $mtz['vBCFCPST'] = gDBFloat($_REQUEST['vBCFCPST']);
                $mtz['pFCPST'] = gDBFloat($_REQUEST['pFCPST']);
                gLog(json_encode($_REQUEST));
                if ($_REQUEST["id_notas_itens_icms"] > 0 && $apagaRegistros == false) {
                    dbUpdate("notas_itens_icms", $mtz, $_REQUEST["id_notas_itens_icms"]);
                    $id=$_REQUEST["id_notas_itens_icms"];
                } else {
                    $id=dbInsert("notas_itens_icms", $mtz, true);
                }

                if (!empty($_REQUEST['replicar'])) {
                    $id_notas = dbQuery("SELECT id_notas id FROM notas_itens WHERE id = ".intval($_REQUEST["gIdItem"]))[0];

                    $sql = "SELECT NI.id id_notas_itens, NI.id_notas, CMS.id id_cms FROM notas_itens NI 
                    LEFT JOIN notas_itens_icms CMS ON CMS.id_notas_itens = NI.id
                    WHERE NI.id_notas = ".$id_notas['id'];
                    $res = dbQuery($sql);

                    foreach ($res as $rs) {
                        $mtz=[];
                        $mtz["id_notas_itens"]=$rs['id_notas_itens'];
                        $mtz["id_imp_icms_cst"]=intval($_REQUEST["icms_cst"]);
                        $mtz["id_imp_icms_origem"]=intval($_REQUEST["icms_orig"]);
                        $mtz["id_imp_icms_mod"]=intval($_REQUEST["icms_mod"]);
                        $mtz["reducao_icms_aliquota"]=gDBFloat(str_replace(".", ",", $_REQUEST["reducao_icms_aliquota"]));
                        $mtz["pICMS"]=gDBFloat(str_replace(".", ",", $_REQUEST["icms_aliquota"]));
                        $mtz["vBC"]=gDBFloat(str_replace(".", ",", $_REQUEST["vBC"]));
                        $mtz["vICMSSTRet"]=gDBFloat(str_replace(".", ",", $_REQUEST["vICMSSTRet"]));
                        $mtz['pICMSST']=gDBFloat(str_replace(".", ",", $_REQUEST["pICMSST"]));

                        $mtz['modBCST'] = (int) $_REQUEST['modBCST'] ;
                        $mtz['pMVAST'] = gDBFloat($_REQUEST['pMVAST']);
                        $mtz['pRedBCST'] = gDBFloat($_REQUEST['pRedBCST']);
                        $mtz['vBCST'] = gDBFloat($_REQUEST['vBCST']);
                        $mtz['vBCFCP'] = gDBFloat($_REQUEST['vBCFCP']);
                        $mtz['pFCP'] = gDBFloat($_REQUEST['pFCP']);
                        $mtz['vBCFCPST'] = gDBFloat($_REQUEST['vBCFCPST']);
                        $mtz['pFCPST'] = gDBFloat($_REQUEST['pFCPST']);

                        if (!empty($rs['id_cms'])) {
                            dbUpdate("notas_itens_icms", $mtz, $rs['id_cms']);
                        } else {
                            dbInsert("notas_itens_icms", $mtz, false);
                        }
                    }

                    $sql = "SELECT id FROM notas_itens_pis WHERE id_notas_itens=" . ((int) $rs['id_notas_itens']) . " LIMIT 1";
                    $idNotasItensPis = dbQuery($sql)[0]['id'];
                    if (!$idNotasItensPis) {
                        $mtzPis = [];
                        $mtzPis["id_notas_itens"] = $rs['id_notas_itens'];
                        $mtzPis["id_imp_pis_cst"] = 1;
                        $mtzPis["pPIS"] = 0;
                        dbInsert("notas_itens_pis", $mtzPis, false);
                    }

                    $sql = "SELECT id FROM notas_itens_cofins WHERE id_notas_itens=" . ((int) $rs['id_notas_itens']) . " LIMIT 1";
                    $idNotasItensCofins = dbQuery($sql)[0]['id'];
                    if (!$idNotasItensCofins) {
                        $mtzCofins = [];
                        $mtzCofins["id_notas_itens"] = $rs['id_notas_itens'];
                        $mtzCofins["id_imp_cofins_cst"] = 1;
                        $mtzCofins["pCOFINS"] = 0;
                        dbInsert("notas_itens_cofins", $mtzCofins, false);
                    }
                }

                echo json_encode($id);

                $sql = "SELECT id FROM notas_itens_pis WHERE id_notas_itens=" . ((int) $_REQUEST["gIdItem"]) . " LIMIT 1";
                $idNotasItensPis = dbQuery($sql)[0]['id'];
                if (!$idNotasItensPis) {
                    $mtz = [];
                    $mtz["id_notas_itens"] = $_REQUEST["gIdItem"];
                    $mtz["id_imp_pis_cst"] = 1;
                    $mtz["pPIS"] = 0;
                    dbInsert("notas_itens_pis", $mtz);
                }

                $sql = "SELECT id FROM notas_itens_cofins WHERE id_notas_itens=" . ((int) $_REQUEST["gIdItem"]) . " LIMIT 1";
                $idNotasItensCofins = dbQuery($sql)[0]['id'];
                if (!$idNotasItensCofins) {
                    $mtz = [];
                    $mtz["id_notas_itens"] = $_REQUEST["gIdItem"];
                    $mtz["id_imp_cofins_cst"] = 1;
                    $mtz["pCOFINS"] = 0;
                    dbInsert("notas_itens_cofins", $mtz);
                }


                exit;

            case "salvarIPI":
                $apagaRegistros=false;
                $sql="SELECT COUNT(id) q FROM notas_itens_ipi WHERE id_notas_itens=".intval($_REQUEST["gIdItem"]);
                $r=dbQuery($sql)[0];
                if($r['q']>1){
                    $apagaRegistros = true;
                    dbQuery("DELETE FROM notas_itens_ipi WHERE id_notas_itens=".intval($_REQUEST["gIdItem"]));
                }
                $mtz=[];
                $mtz["id_notas_itens"]=intval($_REQUEST["gIdItem"]);
                $mtz['cEnq'] = $_REQUEST["ipi_cEnq"];
                $mtz["id_imp_ipi_cst"]=intval($_REQUEST["id_imp_ipi_cst"]);
                $mtz["pIPI"]=gDBFloat(str_replace(".", ",", $_REQUEST["pIPI"]));
                if ($_REQUEST["id_notas_itens_ipi"]>0 && $apagaRegistros==false) {
                    dbUpdate("notas_itens_ipi", $mtz, intval($_REQUEST["id_notas_itens_ipi"]));
                    $id=$_REQUEST["id_notas_itens_ipi"];
                } else {
                    $id=dbInsert("notas_itens_ipi", $mtz, true);
                }

                if (!empty($_REQUEST['replicar'])) {
                    $id_notas = dbQuery("SELECT id_notas id FROM notas_itens WHERE id = ".intval($_REQUEST["gIdItem"]))[0];

                    $sql = "SELECT NI.id id_notas_itens, NI.id_notas, NII.id id_ipi FROM notas_itens NI 
                    LEFT JOIN notas_itens_ipi NII ON NII.id_notas_itens = NI.id
                    WHERE NI.id_notas = ".$id_notas['id'];
                    $res = dbQuery($sql);

                    foreach ($res as $rs) {
                        $mtz=[];
                        $mtz["id_notas_itens"]=$rs['id_notas_itens'];
                        $mtz['cEnq'] = $_REQUEST["ipi_cEnq"];
                        $mtz["id_imp_ipi_cst"]=intval($_REQUEST["id_imp_ipi_cst"]);
                        $mtz["pIPI"]=gDBFloat(str_replace(".", ",", $_REQUEST["pIPI"]));

                        if (!empty($rs['id_ipi'])) {
                            dbUpdate("notas_itens_ipi", $mtz, $rs['id_ipi']);
                        } else {
                            dbInsert("notas_itens_ipi", $mtz, false);
                        }
                    }
                }

                echo json_encode($id);
                exit;

            case "salvarPIS":
                $apagaRegistros=false;
                $sql="SELECT COUNT(id) q FROM notas_itens_pis WHERE id_notas_itens=".intval($_REQUEST["gIdItem"]);
                $r=dbQuery($sql)[0];
                if ($r['q']>1) {
                    $apagaRegistros = true;
                    dbQuery("DELETE FROM notas_itens_pis WHERE id_notas_itens=".intval($_REQUEST["gIdItem"]));
                }

                $mtz=[];
                $mtz["id_notas_itens"]=intval($_REQUEST["gIdItem"]);
                $mtz["id_imp_pis_cst"]=intval($_REQUEST["id_imp_pis_cst"]);
                $mtz["pPIS"]=gDBFloat(str_replace(".", ",", $_REQUEST["pPIS"]));
                if ($_REQUEST["id_pis"] > 0 && $apagaRegistros == false) {
                    dbUpdate("notas_itens_pis", $mtz, intval($_REQUEST["id_pis"]));
                    $id=intval($_REQUEST["id_pis"]);
                } else {
                    $id = dbInsert("notas_itens_pis", $mtz, true);
                }

                if (!empty($_REQUEST['replicar'])) {
                    $id_notas = dbQuery("SELECT id_notas id FROM notas_itens WHERE id = ".intval($_REQUEST["gIdItem"]))[0];

                    $sql = "SELECT NI.id id_notas_itens, NI.id_notas, NIP.id id_pis FROM notas_itens NI 
                            LEFT JOIN notas_itens_pis NIP ON NIP.id_notas_itens = NI.id
                            WHERE NI.id_notas = " . $id_notas['id'];
                    $res = dbQuery($sql);

                    foreach ($res as $rs) {
                        $mtz = [];
                        $mtz["id_notas_itens"]=$rs['id_notas_itens'];
                        $mtz["id_imp_pis_cst"]=intval($_REQUEST["id_imp_pis_cst"]);
                        $mtz["pPIS"]=gDBFloat(str_replace(".", ",", $_REQUEST["pPIS"]));

                        if (!empty($rs['id_pis'])) {
                            dbUpdate("notas_itens_pis", $mtz, $rs['id_pis']);
                        } else {
                            dbInsert("notas_itens_pis", $mtz, false);
                        }
                    }
                }

                echo json_encode($id);
            exit;

            case "salvarCOFINS":
                $apagaRegistros = false;
                $sql = "SELECT COUNT(id) q FROM notas_itens_cofins WHERE id_notas_itens=".intval($_REQUEST["gIdItem"]);
                $r = dbQuery($sql)[0];
                if ($r['q'] > 1) {
                    $apagaRegistros = true;
                    dbQuery("DELETE FROM notas_itens_cofins WHERE id_notas_itens=".intval($_REQUEST["gIdItem"]));
                }
                $mtz=[];
                $mtz["id_notas_itens"]=intval($_REQUEST["gIdItem"]);
                $mtz["id_imp_cofins_cst"]=intval($_REQUEST["id_imp_cofins_cst"]);
                $mtz["pCOFINS"]=gDBFloat(str_replace(".", ",", $_REQUEST["pCOFINS"]));
                if ($_REQUEST["id_cofins"] && $apagaRegistros == false) {
                    dbUpdate("notas_itens_cofins", $mtz, intval($_REQUEST["id_cofins"]));
                    $id=intval($_REQUEST["id_cofins"]);
                } else {
                    $id=dbInsert("notas_itens_cofins", $mtz, true);
                }

                if (!empty($_REQUEST['replicar'])) {
                    $id_notas = dbQuery("SELECT id_notas id FROM notas_itens WHERE id = ".intval($_REQUEST["gIdItem"]))[0];

                    $sql = "SELECT NI.id id_notas_itens, NI.id_notas, NIC.id id_cofins FROM notas_itens NI 
                    LEFT JOIN notas_itens_cofins NIC ON NIC.id_notas_itens = NI.id
                    WHERE NI.id_notas = ".$id_notas['id'];
                    $res = dbQuery($sql);

                    foreach ($res as $rs) {
                        $mtz=[];
                        $mtz["id_notas_itens"]=$rs['id_notas_itens'];
                        $mtz["id_imp_cofins_cst"]=intval($_REQUEST["id_imp_cofins_cst"]);
                        $mtz["pCOFINS"]=gDBFloat(str_replace(".", ",", $_REQUEST["pCOFINS"]));

                        if (!empty($rs['id_cofins'])) {
                            dbUpdate("notas_itens_cofins", $mtz, $rs['id_cofins']);
                        } else {
                            dbInsert("notas_itens_cofins", $mtz, false);
                        }
                    }
                }

                echo json_encode($id);
                exit;

            case "salvarIbsCbs":
                $sql = "SELECT id FROM notas_itens_ibs_cbs WHERE id_notas_itens = " . intval($_REQUEST["gIdItem"]);
                $rs = dbFastQuery($sql)[0];

                $mtz = [];
                $mtz["vBC"]                   = gDBFloat(str_replace(".", ",", $_REQUEST["vBC_IBS_CBS"]));
                $mtz["indDoacao"]             = $_REQUEST["indDoacao"];
                $mtz["id_notas_itens"]        = $_REQUEST["gIdItem"];
                $mtz["id_imp_ibs_cbs_cst"]    = $_REQUEST["id_imp_ibs_cbs_cst"];
                $mtz["id_cclasstrib_ibs_cbs"] = $_REQUEST["id_cclasstrib_ibs_cbs"];

                // Campos IBS UF (Estadual)
                $mtz["gIBSUF_vIBSUF"]    = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSUF_vIBSUF"]));
                $mtz["gIBSUF_pIBSUF"]    = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSUF_pIBSUF"]));
                $mtz["gIBSUF_pRedAliq"]  = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSUF_pRedAliq"]));
                $mtz["gIBSUF_pAliqEfet"] = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSUF_pAliqEfet"]));
                $mtz["gIBSUF_pDif"]      = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSUF_pDif"]));
                $mtz["gIBSUF_vDif"]      = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSUF_vDif"]));
                $mtz["gIBSUF_vDevTrib"]  = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSUF_vDevTrib"]));

                // Campos IBS Municipal
                $mtz["gIBSMun_pIBSMun"]   = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSMun_pIBSMun"]));
                $mtz["gIBSMun_vIBSMun"]   = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSMun_vIBSMun"]));
                $mtz["gIBSMun_pRedAliq"]  = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSMun_pRedAliq"]));
                $mtz["gIBSMun_pAliqEfet"] = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSMun_pAliqEfet"]));
                $mtz["gIBSMun_pDif"]      = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSMun_pDif"]));
                $mtz["gIBSMun_vDif"]      = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSMun_vDif"]));
                $mtz["gIBSMun_vDevTrib"]  = gDBFloat(str_replace(".", ",", $_REQUEST["gIBSMun_vDevTrib"]));

                // Campos CBS
                $mtz["gCBS_pCBS"]      = gDBFloat(str_replace(".", ",", $_REQUEST["gCBS_pCBS"]));
                $mtz["gCBS_vCBS"]      = gDBFloat(str_replace(".", ",", $_REQUEST["gCBS_vCBS"]));
                $mtz["gCBS_pRedAliq"]  = gDBFloat(str_replace(".", ",", $_REQUEST["gCBS_pRedAliq"]));
                $mtz["gCBS_pAliqEfet"] = gDBFloat(str_replace(".", ",", $_REQUEST["gCBS_pAliqEfet"]));
                $mtz["gCBS_pDif"]      = gDBFloat(str_replace(".", ",", $_REQUEST["gCBS_pDif"]));
                $mtz["gCBS_vDif"]      = gDBFloat(str_replace(".", ",", $_REQUEST["gCBS_vDif"]));
                $mtz["gCBS_vDevTrib"]  = gDBFloat(str_replace(".", ",", $_REQUEST["gCBS_vDevTrib"]));

                // CST 222 - Redução de Base de Cálculo
                $mtz["pRedutorBC"]     = gDBFloat(str_replace(".", ",", $_REQUEST["pRedutorBC"]));

                // CST 620 - Tributação Monofásica
                $mtz["qBCMono"]  = gDBFloat(str_replace(".", ",", $_REQUEST["qBCMono"]));
                $mtz["adRemIBS"] = gDBFloat(str_replace(".", ",", $_REQUEST["adRemIBS"]));
                $mtz["vIBSMono"] = gDBFloat(str_replace(".", ",", $_REQUEST["vIBSMono"]));
                $mtz["adRemCBS"] = gDBFloat(str_replace(".", ",", $_REQUEST["adRemCBS"]));
                $mtz["vCBSMono"] = gDBFloat(str_replace(".", ",", $_REQUEST["vCBSMono"]));

                // CST 800 - Transferência de Crédito
                $mtz["vIBSTransf"] = gDBFloat(str_replace(".", ",", $_REQUEST["vIBSTransf"]));
                $mtz["vCBSTransf"] = gDBFloat(str_replace(".", ",", $_REQUEST["vCBSTransf"]));

                // CST 810 - Ajuste de IBS na ZFM
                $mtz["tpCredPresIBSZFM"] = $_REQUEST["tpCredPresIBSZFM"];
                $mtz["vCredPresIBSZFM"]  = gDBFloat(str_replace(".", ",", $_REQUEST["vCredPresIBSZFM"]));

                // CST 811 - Ajustes de Competência
                $mtz["competApur"] = $_REQUEST["competApur"];
                $mtz["vIBSAjuste"] = gDBFloat(str_replace(".", ",", $_REQUEST["vIBSAjuste"]));
                $mtz["vCBSAjuste"] = gDBFloat(str_replace(".", ",", $_REQUEST["vCBSAjuste"]));

                // Update ou Insert
                if ($rs) {
                    $id = (int) $_REQUEST["id_ibs_cbs"];
                    dbUpdate("notas_itens_ibs_cbs", $mtz, $id);
                } else {
                    $id = dbInsert("notas_itens_ibs_cbs", $mtz, true);
                }

                // Replicar para todos os itens da nota
                if (!empty($_REQUEST['replicar'])) {
                    $idNotas = dbFastQuery("SELECT id_notas id FROM notas_itens WHERE id = ".intval($_REQUEST["gIdItem"]))[0];

                    $sql = "SELECT
                                NI.id id_notas_itens,
                                NIC.id id_ibs_cbs
                            FROM notas_itens NI
                            LEFT JOIN notas_itens_ibs_cbs NIC ON NIC.id_notas_itens = NI.id
                            WHERE NI.id_notas = " . $idNotas['id'];
                    $res = dbFastQuery($sql);

                    foreach ($res as $rs) {
                        $mtz["id_notas_itens"] = $rs["id_notas_itens"];

                        if (!empty($rs['id_ibs_cbs'])) {
                            dbUpdate("notas_itens_ibs_cbs", $mtz, $rs['id_ibs_cbs']);
                        } else {
                            dbInsert("notas_itens_ibs_cbs", $mtz, false);
                        }
                    }
                }

                echo json_encode($id);
            break;
        }

        break;


    case MEDICAMENTO_SALVAR:
        $mtz = [];
        $mtz['data'] = date('Y-m-d H:i');
        $mtz['id_pessoas'] = $_SESSION['usrId'];
        $mtz['id_notas_itens'] = (int) $id_notas_itens;
        $mtz['cProdANVISA'] = gCleanField($_REQUEST["cProdANVISA"]);
        $mtz['xMotivoIsencao'] = gCleanField($_REQUEST["xMotivoIsencao"]);
        $mtz["VPMC"] = gDBFloat($_REQUEST["vPMC"]);
        $mtz['nLote'] = gCleanField($_REQUEST["nLote"]);
        $mtz['qLote'] = gDBFloat($_REQUEST["qLote"]);
        $mtz['dFab'] = gDBDate($_REQUEST["dFab"]);
        $mtz['dVal'] = gDBDate($_REQUEST["dVal"]);
        $mtz['cAgreg'] = gCleanField($_REQUEST["cAgreg"]);

        $sql = 'SELECT id FROM notas_itens_medicamentos WHERE id_notas_itens=' . $id_notas_itens;
        $rs = dbQuery($sql);
        if (!$rs) {
            dbInsert("notas_itens_medicamentos", $mtz);
        } else {
            dbUpdate("notas_itens_medicamentos", $mtz, $rs[0]['id']);
        }
        redirect($o->page . "&gPage=" . nf_saida . "&gPage=" . ITENS . "&gId=".$id_notas);
        break;


    case NFE_REENVIAR_EMAIL:
        $statusEnvioEmail = $nf->enviarEmail($gIdEnd);
        if (is_array($statusEnvioEmail)) {
            $html .= $o->msgDanger("E-mail de NF não enviado ao cliente."
                . $o->ul($statusEnvioEmail));
        } else {
            $html .= $o->msgSuccess("E-mail de NF enviado ao cliente");
        }
        $html .= $backButton;

        break;


    case NFE:
        // Sempre conferir antes de testar.
        //(gVar("nfe.ambiente"));
        //exit;

        $validaFilial = $nf->validarFilialSessao($gId);
        if (!$validaFilial['sucesso']) {
            $html .= $o->msgDanger($validaFilial['msg']);
            $html .= $o->button("{name: back; icon: arrow-left; caption: Voltar; style: default; href: " . $o->page . "&gPage=" . INICIO . "}");
            break;
        }

        $rs = $nf->obtemRegistro($gId);
        $html .= $nf->obtemCabecalho($rs);

        $nota  = $nf->obtemDadosNFE($gId);
        $totaisNota = $nf->totaisNota($gId);
        $itens = $nf->obtemRegistrosNotasItens($gId);

        if (!$itens) {
            $msg = "Você não pode concluir esta ação pois aconteceram os seguintes erros: "
                . $o->ul([
                    "Cadastre itens a nota fiscal antes de tentar preencher os dados da NF-e"
                ]);
            $html .= $o->msgDanger($msg);
            break;
        }

        $defaultInfContribuente="";
        $inNotas = [];
        foreach ($itens as $item) {
            if (!in_array($item["nota_associada"], $inNotas) && !empty($item["nota_associada"])) {
                $inNotas[] = $item["nota_associada"];
            }
        }

        // Caso tenha notas de referência, colocar nas informações do contribuente.
        if ($inNotas) {
            $defaultInfContribuente.="Notas entrada: ".implode(", ", $inNotas);
        }

        $sql = "SELECT
                    nfe.*,
                    notas.numero os,
                    C.descricao_resumida cfop,
                    notas.id idNota,
                    notas.id_notas_origem_estorno
                FROM notas
                INNER JOIN nfe ON nfe.id_notas = notas.id
                LEFT  JOIN cfops C ON C.id = notas.id_cfops
                WHERE notas.id = '{$gId}'";
        $confereNFE = dbFastQuery($sql);

        $html .= $o->hr();
        if ($gParam['PERMITIR_AGRUPAMENTO_NOTA_SAIDA']['ativo']) {
            $sql = "SELECT
                        notas.id,
                        notas.numero
                    FROM notas
                    WHERE notas.cancelada = 0 AND notas.id_notas_agrupar = " . $gId;
            $rs = dbFastQuery($sql);
            if ($rs) {
                $html .= $o->msg('Notas agrupadas: ' . linkParaNota(array_column($rs, 'id')));
            }
        }

        $js = "
            function btnItensNota(gId)
            {
                $('#itensNota').modal('show');
            }


            function changeFinalidadeEmissao()
            {
                var finalidade=document.getElementById('finNFe').value;
                document.getElementById('field-refNfe').removeAttribute('style');
            }
        ";
        $html .= $o->addJavascript($js);

        $frm = new gForm();
        $frm->row(
            $frm->add("{type: combo; name: idDestino; fieldLabel: Identificador de destino; value:".$nota["idDestino"]."; items:'".$nf->comboIdentificadorDestino()."';}"),
            $frm->add("{type: combo; name: IE; fieldLabel: Identificação IE Destinatário;  value:".$nota["IE"]."; items:'".$nf->comboIE()."';}"),
            $frm->add("{type: combo; name: finNFe; fieldLabel: Finalidade da Emissão;  value:".$nota["finNFe"]."; items: '".$nf->comboFinalidadeEmissao()."'; onChange: changeFinalidadeEmissao();}"),
            $frm->add("{type: combo; name: modFrete; fieldLabel: Modalidade Frete;  value:".$nota["modFrete"]."; items:'".$nf->modalidadeFrete()."';}")
        );

        $infoRefnfe = '';
        $frm->add("{type: hidden; name:refNfeOriginal; value: '';}");
        if ($gParam['INSERE_REFNFE_AUTO']['ativo']) {
            $infoRefnfe = ' (para preencher automaticamente apague o campo completamente e confirme)';
        }

        $frm->add("{type: textarea; name: refNfe; fieldLabel: Chave de referência " . $infoRefnfe . "; value:" . $nota["refNfe"] . ";}");

        $frm->row(
            $frm->add("{type: text; name:RNTC; fieldLabel: Código ANTT;  value:".$nota["RNTC"].";}"),
            $frm->add("{type: upperText; maxLength: 7;  name: placa; fieldLabel: Placa;  value:".$nota["placa"].";}"),
            $frm->add("{type: upperText; maxLength: 4; name: placaUF; fieldLabel: UF da placa;  value:".$nota["placaUF"].";}")
        );

        $frm->row(
            $frm->add("{allowBlank: false; type: combo; name: indIntermed; fieldLabel: Identificador de intermediador; value:".$nota["indIntermed"]."; items:'".$nf->comboIndentificadorIntermediario()."';}")
        );

        $frm->row(
            $frm->add("{type: combo; name: id_nfe_informacoes; fieldLabel: Informações; value:".$nota["id_nfe_informacoes"]."; items:".$sp["combo_informacoes_nfe"].";}")
        );

        $frm->row(
            $frm->add("{type: text;  name: infAdFisco; fieldLabel: Informações fisco; value:".$nota["infAdFisco"].";}")
        );

        if ($nota["InfCpl"] != "") {
            $defaultInfContribuente = $nota["InfCpl"];
        }

        $frm->row(
            $frm->add("{type: text;  name: InfCpl; fieldLabel: Informações contribuinte; value:".$defaultInfContribuente.";}")
        );

        $frm->row(
            $frm->add("{type: text;  name: esp; fieldLabel: Espécie transportadora;  value:".$nota["esp"].";}"),
            $frm->add("{type: number;  name: qVol; fieldLabel: Quantidade espécie;  value:".gFloat($nota["qVol"]).";}"),
            $frm->add("{type: text;  name: marca; fieldLabel: Marca;  value:".$nota["marca"].";}"),
            $frm->add("{type: number;  name: vOutro; fieldLabel: Outras despesas;  value:".gFloat($nota["vOutro"]).";}")
        );

        $frm->row(
            $frm->add("{type: number;  name: vDesc; fieldLabel: Valor do desconto; value:".gFloat($nota["vDesc"]).";}"),
            $frm->add("{type: number;  name: vSeg; fieldLabel: Valor do seguro; value:".gFloat($nota["vSeg"]).";}"),
            $frm->add("{type: number;  name: vFrete; fieldLabel: Valor do frete;  value:".gFloat($nota["vFrete"]).";}"),
            $frm->add("{type: number;  name: vProd; fieldLabel: Valor total;  value:".gFloat($totaisNota["vProd"]).";}")
        );

        $listaPagamento = $nf->tipoPagamento();

        if (($confereNFE[0]['id_notas_origem_estorno'] != 0)) {
            $listaPagamento = json_encode([90 => "90 - Sem Pagamento"]);
        }

        $frm->row(
            $frm->add("{type: number;  name: pesoB; fieldLabel: Peso bruto; value:".gFloat($totaisNota["pesoB"]).";}"),
            $frm->add("{type: number;  name: pesoL; fieldLabel: Peso líquido; value:".gFloat($totaisNota["pesoL"]).";}"),
            $frm->add("{type: combo; name: tPag; fieldLabel: Tipo de pagamento; allowBlank: false; value:". ($nota['tPag'] ?: 90)."; items:'".$listaPagamento."';}"),
            $frm->add("{type: number;  name: vPag; fieldLabel: Valor do pagamento; value:".gFloat($nota["vPag"]).";}")
        );

        $emailsEnviar = dbQuery("SELECT P.email AS email_proprietario, N.emails_enviar AS emails_nota FROM pessoas P LEFT JOIN notas N ON N.id_pessoas_proprietario = P.id WHERE N.id = " . $gId)[0];
        $emailsEnviar = $emailsEnviar['emails_nota'] ?: $emailsEnviar['email_proprietario'];

        $frm->row(
            $frm->add("{type: email; name: emails_enviar; fieldLabel: E-mails; value:'".$emailsEnviar."';}")
        );

        $frm->add("{type: hidden; name: gPage; value: " . NFE_SALVAR . ";}");
        $frm->add("{type: hidden; name: gTipoOperacao; value: 1;}");

        $frm->addButton("{name:btnEmitirNfe; icon: print; title: Emitir Nf-e; hint: Emitir NF-e; style: info; size: small;}", "emitNFE('".$gId."', this)");

        $sql = "SELECT nfe_eventos.id_nfe_tipos_eventos
                FROM nfe_eventos
                WHERE nfe_eventos.id_notas_saida = {$gId}";
        $eventosNfe = dbFastQuery($sql);
        if (
            $confereNFE
            && $confereNFE[0]["situacao"] == "Aprovada"
            && !in_array("5", array_column($eventosNfe, 'id_nfe_tipos_eventos'))
        ) {
            $content='<div id="contentModalOpcoesNFe"></div>';
            $html .= $o->modal("{title: Opções da NF-e; id: opcoesNFE; cancelCaption: Fechar; confirm: false; name: opcoesNFE; size:large; }", $content);
            $frm->addButton("{icon: cog; title: Opções da NF-e; hint: Opções; style: info; size: small;}", "btnOpcoesNFE('".$confereNFE[0]["id"]."', '".$confereNFE[0]["idNota"]."')");
        }

        if ($confereNFE && $confereNFE[0]["situacao"] == "Cancelada" && $confereNFE[0]['xml_cancelamento'] != '') {
            $frm->addButton("{icon: download; title: Baixar XML de cancelamento; hint: Baixar XML de cancelamento; style: info; size: small; href: " . $o->page . "&gPage=" . NFE_OBTER_XML_CANCELAMENTO . "&gId=" . $confereNFE[0]['id'] . ";}");
        }

        $html .= $frm->render($o);
        $g = '<div id="modalEmitirNfeContent">Carregando dados...</div>';
        $html .= $o->modal("{title: Emitir NF-e; id: emitirNFE; cancelCaption: Fechar; confirm: false; name: emitirNFe; size:large; }",$g);
        if ($nota['cancelada'] == 1) {
            $o->addJavascript("document.getElementsByName('btnEmitirNfe')[0].setAttribute('disabled', 'disabled');");
        }

        $o->addJavascript("
            $('#pesoB').attr('readonly', 'readonly');
            $('#pesoL').attr('readonly', 'readonly');
            $('#vProd').attr('readonly', 'readonly');

            function emitNFE(gId, self)
            {
                if (document.getElementById('gSubmitButton')) {
                    document.getElementById('gSubmitButton').setAttribute('disabled', 'disabled');
                }
                self.setAttribute('disabled', 'disabled');

                const url = '" . $o->page . "&gId=' + gId + '&gAjax=1&emitirNfe=1';
                modalEnvioEstorno(url);
            }


            function btnOpcoesNFE(idNfe, idNota)
            {
                showWait();
                modalOpcoesNFe(idNfe, idNota);
            }


            function btnObterXml(idNFE) {
                var rota = '".$o->page."&gPage=".NFE_OBTER_XML."&gId='+idNFE;
                window.open(rota, '_blank');
                hideWait();
            }

            function verificarPlacaUF() {
                $('#placaUF').prop('required', $('#placa').val().trim() !== '');
            }

            $('#placa').on('input blur', function() {
                verificarPlacaUF();
            });
        ");

        if ($gParam['EMITIR_NF_APROVADA']['ativo']) {
            $o->addJavascript("
                document.getElementsByName('btnEmitirNfe')[0].removeAttribute('disabled');
            ");
        }

        $html .= "<hr/>";
        $itens = $nf->obtemRegistrosNotasItens($gId);
        if ($confereNFE[0]['id']) {
            $html.=$nf->obterTabelaInformacoesOperacao($gId);
        }

        $html.=$nf->obtemTabelaItem($itens, false, true);
        break;


    case NFE_SALVAR:
        // @note  NFE>Valida RefNfe
        if ($gParam['INSERE_REFNFE_AUTO']['ativo']) {
            $_REQUEST['refNfe'] = $_REQUEST['refNfe'] ?: $_REQUEST['refNfeOriginal'];
            $aux = array_unique(explode(",",(string) $_REQUEST['refNfe']));
            foreach ($aux as $k) {
                if (strlen($k) != 44 || preg_match("/[^0-9]/", $k)) {
                    $content = $o->msgAlert("Erro na validação do campo chave de referência.");
                    $mtz = [
                        "Chave " . $k . " incorreta",
                        "As chaves inseridas devem estar separadas por vírgula ',' ",
                        "As chaves devem possuir exatamente 44 caracteres numéricos",
                        "Não pode haver espaços ou outros caracteres que não sejam números"
                    ];

                    $content .= $o->msgDanger($o->ul($mtz));

                    if ($_REQUEST['gAjs']) {
                        header('HTTP/1.1 500 Validation error');
                        header('Content-Type: application/json; charset=UTF-8');
                        die($content);
                    } else {
                        $html .= $content;
                        $html .= $o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: default; size: normal; href: " . $o->page . "&gPage=" . NFE . "&gId=" . $gId);
                        break 2;
                    }
                }
            }
        }

        $nf->atualizarNotaNFE($_REQUEST, $gId);
        if ($_REQUEST["gTipoOperacao"] == 1) {
            redirect($o->page."&gPage=".NFE."&gId=".$gId);
        } else {
            echo json_encode($gId);
            exit;

        }
    break;


    case NFE_OBTER_XML:
        $sql = "SELECT nfe_eventos.xml, nfe.chave
                FROM nfe_eventos
                LEFT JOIN nfe ON nfe.id = nfe_eventos.id_nfe
                WHERE id_nfe = '{$gId}'";
        $rs = dbFastQuery($sql)[0];

        if (!$rs) {
            $msg = "Não foi possível fazer o download do xml pois aconteceram os seguintes erros: <br>";
            $msg .= $o->ul(["NF-e não encontrada"]);
            $html .= $o->msgDanger($msg);
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


    case NFE_OBTER_XML_CANCELAMENTO:
        $sql = "SELECT nfe_eventos.id, nfe_eventos.xml, nfe.chave
                FROM nfe_eventos
                LEFT JOIN nfe ON nfe.id = nfe_eventos.id_nfe
                WHERE id_nfe_tipos_eventos = 2 AND id_nfe = '{$gId}'"; // id_nfe_tipos_eventos = 2 (Evento de cancelamento)
        $rs = dbFastQuery($sql)[0];

        if (!$rs['id']) {
            $msg   = "Não foi possível fazer o download do xml pois aconteceram os seguintes erros: <br>";
            $msg  .= $o->ul(["NF-e não encontrada"]);
            $html .= $o->msgDanger($msg);
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


    case NFE_OBTER_XML_CARTA_CORRECAO:
        $rs = dbFastQuery("SELECT id, motivo, xml FROM nfe_eventos where id = '{$gId}'")[0];

        if (!$rs['id']) {
            $msg   = "Não foi possível fazer o download do xml pois aconteceram os seguintes erros: <br>";
            $msg  .= $o->ul(["Carta de correção não encontrada"]);
            $html .= $o->msgDanger($msg);
            break;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=xml_carta_correcao.xml'); // Não pode ter caracteres especiais isso aqui quebra
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


    case NFE_OPCOES:
        $sql = "SELECT
                    nfe.id,
                    nfe.chave,
                    nfe.data,
                    nfe.situacao,
                    notas.id_notas_origem_estorno
                FROM nfe
                LEFT JOIN notas ON notas.id = nfe.id_notas
                WHERE nfe.id = '{$gId}'";
        $nfe = dbFastQuery($sql)[0];

        if (!$nfe['id_notas_origem_estorno']) {
            $sql = "SELECT id FROM notas WHERE cancelada = 0 AND id_notas_origem_estorno = {$gIdNota}";
            $temEstorno = dbFastQuery($sql)[0]['id'];
        }

        $mtz = [];
        $mtz[] = '<-' . $o->small('Chave') . '<br><b>' . $nfe['chave'] . '</b>&nbsp;';
        $mtz[] = '<-' . $o->small('Situação') . '<br><b><small>' . $nfe['situacao'] . '</small></b>&nbsp;';
        $html .= $o->tableRow($mtz, 'header');
        $html .= $o->tableEnd();
        $html .= $o->br();

        if (!$temEstorno && !$nfe['id_notas_origem_estorno']) {

            $dataNfe = strtotime((string) $nfe['data']);
            $agora   = time();

            if (($agora - $dataNfe) > 86400) {//86400 equivale a 24 horas
                $html .= $o->button("{icon: ban; caption: Estornar; hint: Estornar NFe; style: info; size: normal;", "javascript: bntEstornar(".$gId.",".$_REQUEST["gIdNota"].")");
            } else {
                $html .= $o->button("{icon: trash; caption: Cancelar NFe; hint: Cancelar NF-e; style: danger; size: normal;", "javascript: btnCancelarNFE(".$gId.", ".$_REQUEST["gIdNota"].")");
            }
        }

        $html .= $o->button("{icon: envelope-open; caption: CC-e; hint: Emitir carta de correção; style: success; size: normal;", "javascript: btnCCe(".$gId.", ".$_REQUEST["gIdNota"].")");

        break;

    case LISTAR_CLIENTES_AGRUPAMENTO:
        $form = new gForm();
        $form->row(
            $form->add('{type: combo; id: id_proprietarios; name:id_pessoas_proprietario; allowBlank: true; fieldLabel:Proprietário; items:'.$sp["combo_proprietarios"].';}')
        );

        $form->row(
            $form->add('{name: dataCadastroDe; fieldLabel: Data cadastro de; type: date;}'),
            $form->add('{name: dataCadastroAte; fieldLabel: Data cadastro até; type: date;}')
        );

        $form->add('{name: gPage; id: gPage; type: hidden; value: '.LISTAR_NOTAS_AGRUPAMENTO.';}');
        $html .= $form->render($o);
        break;


    case LISTAR_NOTAS_AGRUPAMENTO:
        if (!$_REQUEST['id_pessoas_proprietario']) {
            $html .= $o->msgDanger("Selecione um proprietário");
            $html .= $backButton;
            break;
        }

        $js = "
            var notasAgrupar = [];
            function marcarNota(idNota) {
                posicao = notasAgrupar.indexOf(idNota);
                if (posicao == -1) {
                    notasAgrupar.push(idNota);
                } else {
                    notasAgrupar.splice(posicao, 1);
                }
                document.getElementById('id_notas').value  = notasAgrupar.join(',');
            }
        ";
        $o->addJavascript($js);

        $_POST['confirmada'] = 0;
        $_POST['pesquisaGeral'] = 1;
        $_POST['id_notas_agrupar'] = 0;
        $_POST['agrupada'] = 0;
        $where = $nf->obtemBusca($_POST, 1);

        if ($_REQUEST["dataCadastroAte"]) {
            //nao eh necessario add where pois o metodo obtemBusca ja o faz
            $cabecalho[] = "Proprietário: " . gFieldById('pessoas', $_REQUEST['id_pessoas_proprietario'], 'apelido');
        }

        if ($_REQUEST["dataCadastroDe"]) {
            $where .= " AND DATE(N.data_criou) >= '".gDBDate($_REQUEST["dataCadastroDe"])."'";
            $cabecalho[] = "Data criação De: ".$_REQUEST["dataCadastroDe"];
        }

        if ($_REQUEST["dataCadastroAte"]) {
            $where .= " AND DATE(N.data_criou) <= '".gDBDate($_REQUEST["dataCadastroAte"])."'";
            $cabecalho[] = "Data criação Até: ".$_REQUEST["dataCadastroAte"];
        }

        if (!$_REQUEST["dataCadastroDe"] && !$_REQUEST["dataCadastroAte"]) {
            $qtdLimiteNotas = 1000;
            $cabecalho[] = "Limite padrão de registros: " . $qtdLimiteNotas;
        }

        $html .= $o->msgFilter('Filtros selecionados: ' . implode(' • ', $cabecalho));

        $notas = $nf->obtemRegistros('N.id desc', $where, $qtdLimiteNotas);
        if (!$notas) {
            $html .= $o->msgInfo("Nenhum registro encontrado");
            $html .= $backButton;
            break;
        }

        $form = new gForm();
        $form->add('{name: id_notas; id: id_notas; type: hidden; allowBlank: false;}');
        $form->add('{name: gPage; id: gPage; type: hidden; value: '.EXIBIR_NOTA_AGRUPADA.';}');
        $html .= $form->render($o);

        $html .= $o->tableBegin('big', true);
        $mtz   = [];
        $mtz[] = 'Selecionar';
        $mtz[] = '<-Id';
        $mtz[] = '<-OS';
        $mtz[] = '<-Número';
        $mtz[] = '<-Proprietário';
        $mtz[] = '<-CFOP';
        $mtz[] = '<>Data de movimento';
        $mtz[] = '<>Cadastro';
        $html .= $o->tableRow($mtz, 'header');

        foreach ($notas as $nota) {
            $sql = "SELECT id FROM notas WHERE id_notas_agrupar = ".$nota['id'];
            $agrupada = dbQuery($sql)[0]['id'];
            if ($agrupada) {
                continue;
            }

            $mtz   = [];
            $mtz[] = '<input type="checkbox" class="notas" onChange="marcarNota('.$nota['id'].')">';
            if ($usrId == 1) {//1=root
                $mtz[] = '<-' . linkParaNota($nota['id']);
            } else {
                $mtz[] = '<-' . $nota['id'];
            }

            $mtz[] = '<-' . linkParaOS($nota['os']);
            $mtz[] = '<-' . $nota['numero'];
            $mtz[] = '<-' . $nota['nome_cliente'];
            $mtz[] = '<-' . $nota['codigo_cfops'];
            $mtz[] = '<>' . gDate($nota['data_movimento']);
            $mtz[] = '<>' . gDateTime($nota['data_criou']) . '<br>' . $o->small($nota['nome_criou']);
            $html .= $o->tableRow($mtz, 'detail');
        }

        $html .= $o->tableEnd();
        break;

    case EXIBIR_NOTA_AGRUPADA:
        $idNotas = explode(',', (string) $_REQUEST['id_notas']);

        if (count($idNotas) < 2) {//2=minimo de duas notas para agrupar
            $html .= $o->msgDanger('Você deve selecionar pelo menos duas notas para continuar este procedimento');
            $html .= $backButton;
            break;
        }

        $itens = $nf->obtemRegistrosNotasItens($idNotas);
        $itensNovaNota = [];
        foreach ($itens as $item) {
            $item = excluirIndicesNumericos($item);
            $indice = $item['id_itens_skus'] . '_' . $item['valor'];
            $skuExistente = $itensNovaNota[$indice];
            if ($skuExistente) {
                $skuExistente['quantidade'] += $item['quantidade'];
                $item = $skuExistente;
            }

            $itensNovaNota[$indice] = $item;
        }

        $itensNovaNota = array_values($itensNovaNota);

        $form = new gForm();
        $form->add("{name: id_notas; id: id_notas; type: hidden; value: '".$_REQUEST['id_notas']."';}");
        $form->add("{name: gPage; id: gPage; type: hidden; value: ".AGRUPAR_NOTAS_SELECIONADAS."';}");
        $html .= $form->render($o);

        $html .= $o->tableBegin('big', true);
        $sql =
            "SELECT PROPRIETARIO.apelido AS proprietario
            FROM notas
            JOIN pessoas PROPRIETARIO ON PROPRIETARIO.id = id_pessoas_proprietario
            WHERE notas.id = ".$idNotas[0];
        $nota = dbQuery($sql)[0];
        $html .= $o->msg("Dados da nota");
        $mtz = [];
        $mtz[] = '<-Filial';
        $mtz[] = '<-Proprietário';
        $mtz[] = '<-Tipo';
        $mtz[] = 'Cadastro';
        $html .= $o->tableRow($mtz, 'header');

        $mtz = [];
        $mtz[] = '<-<b>'.$_SESSION['filialAtualDescricao'].'</b>';
        $mtz[] = '<-<b>'.$nota['proprietario'].'</b>';
        $mtz[] = '<-<b>'.'Saída'.'</b>';
        $mtz[] = '<b>'.gDate(date('Y-m-d')).'<br>'.$_SESSION['usrName'].'</b>';
        $html .= $o->tableRow($mtz, 'header');

        $html .= $o->tableEnd();

        $html .= $nf->obtemTabelaItem($itensNovaNota, 0, 0, 0);
        break;


    case AGRUPAR_NOTAS_SELECIONADAS:
        $sql = "SELECT * FROM notas WHERE id IN (".$_REQUEST['id_notas'].")";
        $notasSelecionadas = dbQuery($sql);

        $novaNota = $notasSelecionadas[0];
        $novaNota = excluirIndicesNumericos($novaNota);
        unset($novaNota['id']);
        unset($novaNota['numero']);
        unset($novaNota['data_movimento']);
        $novaNota['data_criou'] = date('Y-m-d H:i:s');
        $novaNota['id_filial'] = $_SESSION['filialAtualId'];
        $novaNota['id_pessoas_criou'] = $_SESSION['usrId'];
        $novaNota['id_notas_agrupar'] = 0;
        $idNovaNota = dbInsert('notas', $novaNota, true);

        $sql = "SELECT * FROM notas_itens
                WHERE id_notas IN (".$_REQUEST['id_notas'].")
                ORDER BY id_notas_associada, id";
        $notasItensSelecionados = dbQuery($sql);
        $itensNovaNota = [];
        foreach ($notasItensSelecionados as $item) {
            $item = excluirIndicesNumericos($item);
            unset($item['id']);
            $indice = $item['id_itens_skus'] . '_' . $item['valor'];
            $skuExistente = $itensNovaNota[$indice];
            if ($skuExistente) {
                $skuExistente['quantidade'] += $item['quantidade'];
                $item = $skuExistente;
            }

            $itensNovaNota[$indice] = $item;
        }

        $itensNovaNota = array_values($itensNovaNota);

        $sql =
            "INSERT INTO notas_itens (".implode(',', array_keys(reset($itensNovaNota))).") VALUES ";
        foreach ($itensNovaNota as $item) {
            $item['id_notas'] = $idNovaNota;
            $item['aliquota_ipi']    = $item['aliquota_ipi']    ?: '0';
            $item['total_icms_calc'] = $item['total_icms_calc'] ?: '0';
            $sql .= "('".implode("','", array_values($item))."'),";
        }

        $sql = substr($sql, 0, -1);
        dbQuery($sql);

        $sql = "UPDATE notas
                SET id_notas_agrupar = {$idNovaNota}
                WHERE id IN (".$_REQUEST['id_notas'].")";
        dbQuery($sql);


        header("Location: ".$o->page."&gPage=".DADOS."&gId=".$idNovaNota);
        break;


    case ATIVAR_DESATIVAR_CONTINGENCIA:
        $sql = "UPDATE filial_notas
                SET data_alteracao_operacao = NOW(), ativar_modo_contingencia = " . ($_REQUEST['modoContingencia'] ? 0 : 1)
                . " WHERE id_filial = " . $_SESSION['filialAtualId'];
        dbFastQuery($sql);

        redirect($o->page . '&gPage=' . INICIO);
        break;
}
