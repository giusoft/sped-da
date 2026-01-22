<?php
define('IMPORTAR_CERTIFICADO', 10);
define('IMPORTAR_NOVO_CERTIFICADO', 11);
define('PROCESSAR_NOVO_CERTIFICADO', 12);
define('CONFIRMACAO_IMPORTACAO_CERTIFICADO', 13);
define('CADASTRAR_DADOS_FILIAL_NOTAS', 14);
define('SALVAR_DADOS_FILIAL_NOTAS', 15);

include_once $gPathDefault . "gUI.php";

$permissao = "SIUD";
if ($usrId != 1) {
    $permissao = "SIU";
}

$ui = new gUI("{title: Filiais; table: filial; permissions: $permissao}");

$ui->addDictionary("{name: id_enderecos_estados; fieldLabel: Estado; type: combo; items: ".$sp['combo_estados']."}");
$ui->addDictionary("{name: id_enderecos_cidades; fieldLabel: Cidade; type: combo; items: ".$sp['combo_cidades']."}");

if (in_array($gPage, array(
        IMPORTAR_CERTIFICADO,
        IMPORTAR_NOVO_CERTIFICADO,
        PROCESSAR_NOVO_CERTIFICADO,
        CONFIRMACAO_IMPORTACAO_CERTIFICADO,
        CADASTRAR_DADOS_FILIAL_NOTAS)
    )
) {
    $html .= $o->msgTitle("Filial - Importar certificado");
}

switch ($_REQUEST['gPage']) {
	case 0:
		$ui->addRowButton("{hint: Cadastrar informações adicionais do filial; icon: plus; style: primary; gPage: " . CADASTRAR_DADOS_FILIAL_NOTAS . "; url: " . $o->Page . "}");
		$ui->addRowButton("{hint: Gerenciar certificado; icon: certificate; style: primary; gPage: " . IMPORTAR_CERTIFICADO . "; url: " . $o->Page . "}");
		break;

    case IMPORTAR_CERTIFICADO:

        if ($filialAtualId != $gId) {
            $html .= $o->msgDanger('Só é possível importar o certificado do filial que você estiver logado');
            $html .= $backButton;
            break;
        }

        $dadosFilialNota = dbFastQuery("SELECT senha_certificado, id FROM filial_notas WHERE id_filial = {$gId} LIMIT 1")[0];

        $html .= $o->msgSubTitle("Informações do certificado");
        if (!$dadosFilialNota) {
            $html .= $o->msgDanger("Cadastre as informações adicionais do filial para poder consultar o certificado");
            break;
        }

        if ($usrId == 1) {
            $html .= $o->button('{hint: Importar novo certificado; title: Novo; sytle: info; icon: plus; url: ' . $o->page . '&gPage=' . IMPORTAR_NOVO_CERTIFICADO . '&gId=' . $gId . '}');
        }

        $dadosCertificado['temRetorno'] = 1;
        $dadosCertificado['cnpj_filial'] = gFieldById("filial", $gId, 'cnpj');
        $dadosCertificado['senhaCertificado'] = $dadosFilialNota['senha_certificado'];
        $dadosCertificado['idPessoasProprietario'] = 1;
        $certificado = dispararGatilho('buscarDadosCertificado', $dadosCertificado);

        $mensagemErro = '';
        if ($certificado['erroCurl'] && !$certificado['resposta']) {

            $mensagemErro = "Falha ao conectar com o servidor";
            if (!empty($certificado['erroCurl'])) {
                $mensagemErro = gCleanField($certificado['erroCurl']);
            }
        }

        if ($mensagemErro) {
            $html .= $o->msgDanger($mensagemErro);
            break;
        }

        $certificado = json_decode($certificado['resposta'], true);

        if (!$certificado['sucesso'] || !$dadosFilialNota) {
            $html .= $o->msgDanger($certificado['mensagem']);
            break;
        }

        $certificado = $certificado['detalhes'];

        $dataAtual = date('Y-m-d');
        $dataVencimento = date('Y-m-d', strtotime($certificado['Validade']));
        if ($dataAtual >= $dataVencimento) {
            $html .= $o->msgDanger("Este certificado venceu em " . gDate($dataVencimento));
        }

        $diferencaDias = (int) date_diff(date_create($dataAtual), date_create($dataVencimento))->format("%a");
        if ($diferencaDias > 0 && $diferencaDias <= 60) {
            $html .= $o->msgWarning("Restam apenas " . $diferencaDias . " dias para vencimento deste certificado");
        }

		$html .= $o->tableBegin("big", true);

		$dadosCertificado   = array();
		$dadosCertificado[] = '<-' . '<b>Arquivo</b><br>' . $certificado['Arquivo'];
		$dadosCertificado[] = '<-' . '<b>Empresa</b><br>' . $certificado['Empresa'];
		$html .= $o->tableRow($dadosCertificado);

        $dadosCertificado   = array();
		$dadosCertificado[] = '<-' . '<b>E-mail</b><br>' . $certificado['E-mail'];
		$dadosCertificado[] = '<-' . '<b>País</b><br>' . $certificado['País'];
		$html .= $o->tableRow($dadosCertificado);

        $dadosCertificado   = array();
		$dadosCertificado[] = '<-' . '<b>Certificadora</b><br>' . $certificado['Certificadora'];
		$dadosCertificado[] = '<-' . '<b>Tipo de certificado</b><br>' . $certificado['Tipo de certificado'];
		$html .= $o->tableRow($dadosCertificado);

        $dadosCertificado   = array();
		$dadosCertificado[] = '<-' . '<b>Fornecedora</b><br>' . $certificado['Fornecedora'];
		$dadosCertificado[] = '<-' . '<b>Validade</b><br>' . $certificado['Validade'] . "&nbsp&nbsp" . $o->label("Vence em " . max($diferencaDias, 0) . " dias");
		$html .= $o->tableRow($dadosCertificado);

        $html .= $o->tableEnd();
        break;


    case IMPORTAR_NOVO_CERTIFICADO:
        if ($usrId != 1) {
            $html .= $o->msgDanger("Usuário sem permissão para realizar este procedimento");
            $html .= $backButton;
            break;
        }

        $frm = new gForm("{columns: 3}");
        $frm->add("{name: arquivoCertificado; type: file; allowBlank: true; fieldLabel: Certificado;}");
        $frm->add("{name: senhaCertificado; type: password; allowBlank: false; fieldLabel: Senha do certificado;}");
        $frm->add("{name: gPage; type: hidden; value: " . PROCESSAR_NOVO_CERTIFICADO . "}");
        $frm->add("{name: gId; type: hidden; value: " . $_REQUEST['gId'] . "}");
        $html .= $frm->render($o);

        $html .= $o->msgFilter("O tamanho máximo permitido para a inclusão de arquivos é de 5Mb");
        break;


    case CADASTRAR_DADOS_FILIAL_NOTAS:

        $sql = "SELECT
                    filial_notas.*,
                    pessoas.nome,
                    nfe_numeros.numero
                FROM filial_notas
                LEFT JOIN pessoas ON pessoas.id = filial_notas.id_pessoas_alterou
                LEFT JOIN nfe_numeros ON nfe_numeros.id_filial = filial_notas.id_filial AND nfe_numeros.serie = filial_notas.serie
                WHERE filial_notas.id_filial = {$gId} LIMIT 1";
        $dadosFilialNota = dbFastQuery($sql)[0];

        $html .= $o->msgSubTitle("Informações operacionais do filial");

		$html .= $o->tableBegin("big", true);

        $mtz = array();
        $mtz[] = '<-' . formatarParaCabecalho('Usuário responsável', $dadosFilialNota['nome']);
		$mtz[] = '<-' . formatarParaCabecalho('Última modificação da operação', gDateTime($dadosFilialNota['data_alteracao_operacao']));
        $mtz[] = '<-' . formatarParaCabecalho('Última atualização do registro', gDateTime($dadosFilialNota['data_alteracao']));
		$html .= $o->tableRow($mtz, 'header');

        $mtz = array();
        $mtz[] = '<-' . formatarParaCabecalho('Série', $dadosFilialNota['serie']);
        $mtz[] = '<-' . formatarParaCabecalho(' Último número da NFE', $dadosFilialNota['numero']);
		$mtz[] = '<-' . formatarParaCabecalho('Data de vencimento do certificado', gDateTime($dadosFilialNota['data_vencimento_certificado']));
		$html .= $o->tableRow($mtz, 'header');

        $html .= $o->tableEnd();

        $frm = new gForm('column: 2');
        if ($usrId == 1) {
            $frm->row(
                $frm->add("{name: schemes; fieldLabel: Schemes; type: upperText; value: " . $dadosFilialNota['schemes'] . "}; allowBlank: false;"),
                $frm->add("{name: versao_xml; fieldLabel: Versão do XML; type: upperText; value: " . $dadosFilialNota['versao_xml'] . "}; allowBlank: false;")
            );

            $frm->row(
                $frm->add("{name: regime; fieldLabel: Regime; type: upperText; value: " . $dadosFilialNota['regime'] . "}"),
                $frm->add("{name: tpAmb; fieldLabel: Tipo de Ambiente; type: text; value: " . $dadosFilialNota['tpAmb'] . "}")
            );

            $frm->row(
                $frm->add("{name: serie; fieldLabel: Série; type: upperText; value: " . $dadosFilialNota['serie'] . "}"),
                $frm->add("{name: usa_contingencia_ibs_cbs; fieldLabel: Usar contingência ibs/cbs; type: checkbox; value: " . $dadosFilialNota['usarContingenciaIbsCbs'] . "}"),
                $frm->add("{name: desativar_impostos_antigos; fieldLabel: Desativar impostos antigos; type: checkbox; value: " . $dadosFilialNota['desativarImpostosAntigos'] . "}")
            );
        }

        $frm->add('{name: ativar_modo_contingencia; fieldLabel: Ativar/Desativar contigência; type: checkbox; value: ' . $dadosFilialNota['ativar_modo_contingencia'] . ';}');

		$frm->add("{name: id_filial_notas; type: hidden; value: " . $dadosFilialNota['id'] . "}");
		$frm->add("{name: gPage; type: hidden; value: " . SALVAR_DADOS_FILIAL_NOTAS . "}");
        $html .= $frm->render($o);
        break;


    case SALVAR_DADOS_FILIAL_NOTAS:
        $mtz = array();
        $mtz['schemes'] = $_REQUEST['schemes'];
        $mtz['tpAmb']   = $_REQUEST['tpAmb'];
        $mtz['regime']  = $_REQUEST['regime'];
        $mtz['serie']  = $_REQUEST['serie'];
        $mtz['versao_xml'] = $_REQUEST['versao_xml'];
        $mtz['usa_contingencia_ibs_cbs']   = $_REQUEST['usa_contingencia_ibs_cbs'];
        $mtz['desativar_impostos_antigos'] = $_REQUEST['desativar_impostos_antigos'];
        $mtz['id_pessoas_alterou'] = $usrId;

        // Contingencia ativa modo de operação = 7 | Contingencia inativa modo operação igual a 1
        $mtz['ativar_modo_contingencia'] = gDBCheck($_REQUEST['ativar_modo_contingencia']);
        $mtz['data_alteracao'] = date('Y-m-d H:i:s');

        if (gDBCheck($_REQUEST['ativar_modo_contingencia'])) {
            $mtz['data_alteracao_operacao'] = date('Y-m-d H:i:s');
        }

        if ($_REQUEST['id_filial_notas']) {
            dbUpdate('filial_notas', $mtz, $_REQUEST['id_filial_notas']);
        } else {
            $mtz['cnpj'] = gFieldById("filial", $gId, 'cnpj');
            $mtz['id_filial'] = $gId;
            dbInsert('filial_notas', $mtz);
        }

        redirect($o->page . '&gPage=' . CADASTRAR_DADOS_FILIAL_NOTAS . '&gId=' . $gId);
        break;


    case PROCESSAR_NOVO_CERTIFICADO:

        if ($_FILES['arquivoCertificado']['error'] == UPLOAD_ERR_NO_FILE) {
            $html .= $o->msgDanger("Nenhum arquivo foi selecionado");
            $html .= $backButton;
            break;
        }

        $tipoArquivo = strtolower(pathinfo($_FILES['arquivoCertificado']['name'], PATHINFO_EXTENSION));

        if ($tipoArquivo != 'pfx') {
            $html .= $o->msgDanger("O arquivo selecionado não é um arquivo .pfx");
            $html .= $backButton;
            break;
        }

        if ($_FILES['arquivoCertificado']['size'] > 5000000) { // 5000000 eh igual a 5MB
            $html .= $o->msgDanger("O arquivo excede o tamanho limite de 5MB");
            $html .= $backButton;
            break;
        }

        $senhaEncriptada = openssl_encrypt(
            $_REQUEST['senhaCertificado'],
            'AES-128-CBC',
            'emiteNota',
            OPENSSL_RAW_DATA,
            str_repeat("\0", 16)
        );

        $dadosCertificado['temRetorno'] = 1;
        $dadosCertificado['certificado'] = base64_encode(file_get_contents($_FILES['arquivoCertificado']['tmp_name']));
        $dadosCertificado['senhaCertificado'] = bin2hex($senhaEncriptada);
        $dadosCertificado['idPessoasProprietario'] = 1;
        $certificado = dispararGatilho('buscarDadosCertificado', $dadosCertificado);

        $certificado = json_decode($certificado['resposta'], true);

        if (!$certificado['sucesso']) {
            $html .= $o->msgDanger($certificado['mensagem']);
            $html .= $backButton;
            break;
        }


        $dataVencimento = date('Y-m-d', strtotime($certificado['detalhes']['Validade'])) ?: '0000-00-00';
        dbFastQuery("
            UPDATE filial_notas
            SET senha_certificado = HEX(AES_ENCRYPT('" . gCleanField($_REQUEST['senhaCertificado']) . "', 'emiteNota')),
                data_vencimento_certificado = '{$dataVencimento}',
                data_alteracao = NOW()
            WHERE id_filial = {$gId}");

        $dadosCertificado['temRetorno'] = 1;
        $dadosCertificado['certificado'] = base64_encode(file_get_contents($_FILES['arquivoCertificado']['tmp_name']));
        $dadosCertificado['cnpj_filial'] = gFieldById("filial", $gId, 'cnpj');
        $dadosCertificado['idPessoasProprietario'] = 1;
        $mensagem = dispararGatilho('importarPfx', $dadosCertificado);

        $mensagem = json_decode($mensagem['resposta'], true);
        if (!$mensagem['sucesso']) {
            $html .= $o->msgDanger('Erro ao mover o arquivo para o diretório de destino');
            $html .= $backButton;
            break;
        }
        redirect($o->page . '&gPage=' . CONFIRMACAO_IMPORTACAO_CERTIFICADO . '&gId=' . $gId);
        break;


    case CONFIRMACAO_IMPORTACAO_CERTIFICADO:
        $html .= $o->msgSuccess("Importação realizada com sucesso");
        $html .= $o->button('{hint: Voltar; title: Voltar; sytle: info; icon: arrow-left; url: ' . $o->page . '&gPage=' . IMPORTAR_CERTIFICADO . '&gId=' . $gId . '}');
        break;

}

$ui->run($o, $html);
