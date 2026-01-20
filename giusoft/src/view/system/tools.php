<?php

include_once __DIR__ . "/res/_classes/classes.php";

define('INICIO'             ,0);

define('IMPORTAR_DOCUMENTACAO', 1);
define('PROCESSAR_DOCUMENTACAO', 2);
define('CONFIRMACAO_IMPORTACAO_DOCUMENTACAO', 3);

define('IMPORTACAO_GWMS'    ,10);
define('IMPORTACAO_GWMS_0'  ,20);
define('IMPORTACAO_GWMS_1'  ,21);
define('IMPORTACAO_GWMS_2'  ,22);
define('IMPORTACAO_GWMS_3'  ,23);
define('IMPORTACAO_GWMS_4'  ,24);
define('IMPORTACAO_GWMS_5'  ,25);
define('IMPORTACAO_GWMS_6'  ,26);
define('IMPORTACAO_GWMS_7'  ,27);
define('IMPORTACAO_GWMS_8'  ,28);
define('IMPORTACAO_GWMS_9'  ,29);

define('CRIAR_UMAS'         ,30);
define('CRIAR_UMAS_TNL'     ,31);

define('IMPORTAR_SALDO'     ,40);
define('IMPORTAR_SALDO_TNL' ,41);

define('IMPORTAR_SENIOR'    ,50);
define('IMPORTAR_SENIOR_TNL',51);

define('RECALCULAR_SALDO', 60);
define('RECALCULAR_SALDO_TNL', 61);

define('TESTAR_JUNG'        ,70);
define('TESTAR_JUNG_TNL'    ,71);

define('BOMIX_LOTE_FABRICACAO',80);

define('BOMIX_POSICOES'		,90);
define('BOMIX_POSICOES_TNL'	,91);

define('CORRIGIR'           ,200);
define('LIMPA_PROGRAMACAO'  ,210);

define('IMPORTAR_CONSOLIDADO',     300);
define('IMPORTAR_CONSOLIDADO_TNL', 301);

define('LIMPAR_SALDO_WMS_NAO_TEM_PROTHEUS',     400);
define('LIMPAR_SALDO_WMS_NAO_TEM_PROTHEUS_TNL', 401);

define('IMPORTAR_ITENS_GWMS_ANTIGO', 500);
define('IMPORTAR_NOTAS_YPE', 600);

define('EXPURGO'           ,700);
define('CAPTURAR_UMAS_PRESAS_RESERVA', 800);

define('CORRIGE_SALDO_ANALITICO_NEGATIVO', 801);
define('DESATIVAR_UMAS_SEM_SALDO', 802);
define('AJUSTE_POSICAO_SIEMENS', 803);

define("FILTRO_ALIMENTACAO_UMA", 850);
define("ALIMENTACAO_UMA", 851);

if (in_array($gPage, [
	IMPORTAR_DOCUMENTACAO,
	PROCESSAR_DOCUMENTACAO,
	CONFIRMACAO_IMPORTACAO_DOCUMENTACAO]
	)
) {
	$html .= $o->msgTitle("Importação de documentação do WMS");
}

$mesesDeExpurgo=(intval($_REQUEST['mesesDeExpurgo']) ? intval($_REQUEST['mesesDeExpurgo']) : "12");

if ($_REQUEST['gAjax']) {
	$limite = "100";
	$tabelas['umas'] = sprintf("FROM umas WHERE data < DATE_SUB(NOW(), INTERVAL %s MONTH) AND data_desativacao < DATE_SUB(NOW(), INTERVAL %s MONTH) AND data_desativacao<>'0000-00-00 00:00:00' AND ativo=0", $mesesDeExpurgo, $mesesDeExpurgo);
	$tabelas['programacao'] = sprintf('FROM programacao WHERE data_cadastro < DATE_SUB(NOW(), INTERVAL %s MONTH)', $mesesDeExpurgo);
	$tabelas['veiculos'] = sprintf('FROM veiculos_acessos WHERE data_chegada < DATE_SUB(NOW(), INTERVAL %s MONTH)', $mesesDeExpurgo);
	$tabelas['inventarios'] = sprintf('FROM inventarios WHERE data < DATE_SUB(NOW(), INTERVAL %s MONTH)', $mesesDeExpurgo);
	$tabelas['log'] = sprintf('FROM gfw_log WHERE date < DATE_SUB(NOW(), INTERVAL %s MONTH)', $mesesDeExpurgo);
	$tabelas['contagens'] = sprintf('FROM contagens_umas WHERE data < DATE_SUB(NOW(), INTERVAL %s MONTH)', $mesesDeExpurgo);

	switch ($cmd) {
		case 'umas_inativas':
			$sql = "SELECT * ".$tabelas['umas'].(' LIMIT ' . $limite);
			$rs = dbQuery($sql);
			$ids = [];
			foreach($rs as $row) {
				$flds = [];
				foreach ($row as $key => $value) {
					if (!is_numeric($key)) {
						$flds[$key] = str_replace("'"," ",str_replace("'"," ",$value));
					}
				}

				dbInsert('expurgo_umas', $flds);
				$ids[] = $row['id'];
			}

			if ($ids) {
				$sql = "INSERT INTO expurgo_umas_itens (SELECT * FROM umas_itens WHERE id_umas IN (".implode(",",$ids)."))";
				dbQuery($sql);
				$sql = "DELETE FROM umas_itens WHERE id_umas IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "INSERT INTO expurgo_umas_movimentos (SELECT * FROM umas_movimentos WHERE id_umas IN (".implode(",",$ids)."))";
				dbQuery($sql);
				$sql = "DELETE FROM umas_movimentos WHERE id_umas IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "DELETE FROM umas WHERE id IN (".implode(",",$ids).")";
				dbQuery($sql);
			}

			$sql = "SELECT count(id) ttl ".$tabelas['umas'];
			echo dbQuery($sql)[0]['ttl'];
			break;

		case 'programacoes_antigas':
			$sql = "SELECT * " . $tabelas['programacao'] . (" LIMIT " . $limite);
			$rs = dbQuery($sql);
			$ids = [];
			foreach($rs as $row) {
				$flds = [];
				foreach ($row as $key => $value) {
					if (!is_numeric($key)) {
						$flds[$key] = $value;
					}
				}

				dbInsert('expurgo_programacao', $flds);
				$ids[] = $row['id'];
			}

			if ($ids) {
				$sql = "INSERT INTO expurgo_programacao_itens (SELECT * FROM programacao_itens WHERE id_programacao IN (".implode(",",$ids)."))";
				dbQuery($sql);
				$sql = "DELETE FROM programacao_itens WHERE id_programacao IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "INSERT INTO expurgo_programacao_atividades (SELECT * FROM programacao_atividades WHERE id_programacao IN (".implode(",",$ids)."))";
				dbQuery($sql);
				$sql = "DELETE FROM programacao_atividades WHERE id_programacao IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "DELETE FROM programacao WHERE id IN (".implode(",",$ids).")";
				dbQuery($sql);
			}

			$sql = "SELECT count(id) ttl ".$tabelas['programacao'];
			echo dbQuery($sql)[0]['ttl'];
		break;


		case 'veiculos_antigos':
			$sql = "SELECT * " . $tabelas['veiculos'] . (" LIMIT " . $limite);
			$rs = dbQuery($sql);
			$ids = [];
			foreach($rs as $row) {
				$flds = [];
				foreach ($row as $key => $value) {
					if (!is_numeric($key)) {
						$flds[$key] = $value;
					}
				}

				dbInsert('expurgo_veiculos_acessos', $flds);
				$ids[] = $row['id'];
			}

			if ($ids) {
				$sql = "INSERT INTO expurgo_veiculos_acessos_programacoes (SELECT * FROM veiculos_acessos_programacoes WHERE id_veiculos_acessos IN (".implode(",",$ids)."))";
				dbQuery($sql);
				$sql = "DELETE FROM veiculos_acessos WHERE id IN (".implode(",",$ids).")";
				dbQuery($sql);
			}

			$sql = "SELECT count(id) ttl ".$tabelas['veiculos'];
			echo dbQuery($sql)[0]['ttl'];
		break;

		case 'inventarios_antigos':
			$sql = "SELECT * " . $tabelas['inventarios'] . (" LIMIT " . $limite);
			$rs = dbQuery($sql);
			$ids = [];
			foreach($rs as $row) {
				$flds = [];
				foreach ($row as $key => $value) {
					if (!is_numeric($key)) {
						$flds[$key] = $value;
					}
				}

				$ids[] = $row['id'];
			}

			if ($ids) {
				$sql = "DELETE FROM inventarios_skus WHERE id_inventarios IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "DELETE FROM inventarios WHERE id IN (".implode(",",$ids).")";
				dbQuery($sql);
			}

			$sql = "SELECT count(id) ttl ".$tabelas['inventarios'];
			echo dbQuery($sql)[0]['ttl'];
		break;

		case 'log':
			$sql = "DELETE ".$tabelas['log'];
			dbQuery($sql);

			$sql = "SELECT count(id) ttl ".$tabelas['log'];
			echo dbQuery($sql)[0]['ttl'];
		break;

		case 'tabelas_temporarias':
			$sql = "SELECT * " . $tabelas['contagens'] . (" LIMIT " . $limite);
			$rs = dbQuery($sql);
			$ids = [];
			foreach($rs as $row) {
				$flds = [];
				foreach ($row as $key=>$value) {
					if (!is_numeric($key)) {
						$flds[$key] = $value;
					}
				}

				$ids[] = $row['id'];
			}

			if ($ids) {
				$sql = "DELETE FROM contagens_umas_itens WHERE id_contagens_umas IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "DELETE FROM contagens_umas WHERE id IN (".implode(",",$ids).")";
				dbQuery($sql);
			}

			$sql = "SELECT count(id) ttl ".$tabelas['contagens'];
			echo dbQuery($sql)[0]['ttl'];
		break;


	}

	exit;
}


switch($gPage)
{
	case INICIO:
		$html .= $o->msgTitle("Ferramentas");
		$html .= $o->button("{title: Recalcular umas_saldos; icon: calculator; style: primary; size: big; href: ".$o->page."&gPage=".RECALCULAR_SALDO."}");
		$html .= $o->button("{title: Criar UMAs em lote; icon: barcode; style: primary; size: big; href: ".$o->page."&gPage=".CRIAR_UMAS."}");
		$html .= $o->button("{title: Importar UMAs gWMS antigo; icon: random; style: primary; size: big; href: ".$o->page."&gPage=".IMPORTAR_SALDO."}");
		$html .= $o->button("{title: Importar Documentação; icon: download; style: primary; size: big; href: " . $o->page . "&gPage=" . IMPORTAR_DOCUMENTACAO . "}");
		$html .= $o->button("{title: Importar Senior/SILT; icon: file-import; style: primary; size: big; href: ".$o->page."&gPage=".IMPORTAR_SENIOR."}");
		$html .= $o->button("{title: Testar Jungheinrich; icon: forklift; style: primary; size: big; href: ".$o->page."&gPage=".TESTAR_JUNG."}");
		$html .= $o->button("{title: Data Fab.Bomix; hint: Recalcular data_fabricacao de acordo com o lote (Bomix); icon: check; style: primary; size: big; href: ".$o->page."&gPage=".BOMIX_LOTE_FABRICACAO."}");
		$html .= $o->button("{title: Corrigir; icon: check; style: primary; size: big; href: ".$o->page."&gPage=".CORRIGIR."}");
		$html .= $o->button("{title: Posicoes Bomix; icon: map; style: primary; size: big; href: ".$o->page."&gPage=".BOMIX_POSICOES."}");
		$html .= $o->button("{title: Consolidado Bomix; icon: download; style: primary; size: big; href: ".$o->page."&gPage=".IMPORTAR_CONSOLIDADO."}");
		$html .= $o->button("{title: Limpa programação; hint: Limpa registros não relacionados entre programacao e programacao_itens; icon: trash; style: primary; size: big; href: ".$o->page."&gPage=".LIMPA_PROGRAMACAO."}");
		$html .= $o->button("{title: Limpa saldo wms; hint: Limpa saldo que tem no wms, mas não existe no protheus; icon: ban; style: primary; size: big; href: ".$o->page."&gPage=".LIMPAR_SALDO_WMS_NAO_TEM_PROTHEUS."}");
		$html .= $o->button("{title: Alimentação UMA; hint: Alimentação de UMA; icon: file-import; style: primary; size: big; href: ".$o->page."&gPage=" . FILTRO_ALIMENTACAO_UMA . "}");
		$html .= $o->button("{title: Expurgo; hint: Transferir dados antigos para arquivo morto; icon: trash; style: danger; size: big; href: ".$o->page."&gPage=".EXPURGO."}");
		break;

	case IMPORTACAO_GWMS:
		$html.=$o->msgTitle("Importação gWMS Antigo");
		$frm=new gForm("{columns: 3}");
		$frm->row(
			$frm->add("{name: cnpj; fieldLabel: CNPJ; type: text; hint: CNPJ do cliente}"),
			$frm->add("{name: bd; fieldLabel: Banco de dados; type: text; hint: Nome do BD}")
		);
		$frm->add("{name: dados; fieldLabel: Dados do cliente; type: checkbox; value: 0}");
		$frm->add("{name: itens; fieldLabel: Itens; type: checkbox;}");
		$frm->add("{name: saldos; fieldLabel: Saldo inventário; type: checkbox;}");
		$frm->add("{name: nfs; fieldLabel: Notas fiscais; type: checkbox;}");
		$frm->add("{name: umas; fieldLabel: UMAs; type: checkbox;}");
		$frm->add("{name: programacoes; fieldLabel: Programações ativas; type: checkbox;}");
		$frm->add("{name: gPage; type: hidden; value: ".IMPORTACAO_GWMS_0.";}");

		$html.=$frm->render($o);
		break;

	case IMPORTACAO_GWMS_0:
		$html.=$o->msgTitle("Importação gWMS Antigo");

		$cnpj = gJustNumbers($_REQUEST['cnpj']);
		$bd = gCleanField($_REQUEST['bd']);

		$sql = "SELECT P.*, PJ.id idPj, PJ.razao_social, PJ.cnpj, PJ.insc_estadual, PJ.insc_municipal, PJ.site
				FROM ".$bd."_wms.pessoas P
				LEFT JOIN ".$bd."_wms.pessoas_juridicas PJ ON P.id=PJ.id_pessoas
				WHERE PJ.cnpj='".$cnpj."'";
		$camposVelho = dbFastQuery($sql)[0];
		if ($camposVelho['id'] > 0) {
			if ($camposVelho['ativo'] == 0) {
				dbFastQuery("TRUNCATE TABLE cidades");
				dbFastQuery("INSERT INTO cidades (SELECT id, id_estados, sigla, descricao, distancia_da_sede FROM ".$bd."_wms.cidades)");

				// Verifica se já existe no sistema atual
				$sql = "SELECT P.*, PJ.id idPj, PJ.razao_social, PJ.cnpj, PJ.insc_estadual, PJ.insc_municipal, PJ.site
						FROM pessoas P
						LEFT JOIN pessoas_juridicas PJ ON P.id=PJ.id_pessoas
						WHERE PJ.cnpj = '" . $cnpj . "'";
				$camposNovo = dbFastQuery($sql)[0];
				$cmps = [];
				$cmps['tipo'] = 'J';
				$cmps['apelido'] = $camposVelho['apelido'];
				$cmps['nome'] = $camposVelho['nome'];
				$cmps['senha'] = $camposVelho['senha'];
				$cmps['email'] = $camposVelho['email'];
				$cmps['situacao'] = $camposVelho['situacao'];
				$cmps['cliente'] = $camposVelho['cliente'];
				$cmps['fornecedor'] = $camposVelho['fornecedor'];
				$cmps['terceirizado'] = $camposVelho['terceirizado'];
				$cmps['telefone'] = $camposVelho['telefone'];
				$cmps['celular'] = $camposVelho['celular'];
				$cmps['ramal'] = $camposVelho['ramal'];
				$cmps['site'] = $camposVelho['site'];
				$cmps['emitir_nfe'] = $camposVelho['emitir_nfe'];
				$cmps['data_cadastro'] = $camposVelho['data_cadastro'];
				if ($camposNovo['cnpj'] != '') {
					$msg = "Cliente modificado!";
					$gId = $camposNovo['id'];
					// Já existe... atualiza alguns dados
					dbUpdate('pessoas', $cmps, $gId);
				} else {
					// Não existe... insere
					$msg = "Cliente adicionado!";
					$gId = dbInsert('pessoas', $cmps, true);
				}

				$cmps = [];
				$cmps['id_pessoas'] = $gId;
				$cmps['fiscal'] = '1';
				$cmps['cnpj'] = $camposVelho['cnpj'];
				$cmps['insc_estadual'] = $camposVelho['insc_estadual'];
				$cmps['insc_municipal'] = $camposVelho['insc_municipal'];
				$cmps['razao_social'] = $camposVelho['razao_social'];
				$cmps['site'] = $camposVelho['site'];
				$cmps['matriz'] = $camposVelho['matriz'];
				$cmps['observacoes'] = $camposVelho['informacoes_publicas'];
				if ($camposNovo['cnpj'] != '') {
					$idPj = $camposNovo['idPj'];
					// Já existe... atualiza alguns dados
					dbUpdate('pessoas_juridicas', $cmps, $idPj);
				} else {
					// Não existe... insere
					dbInsert('pessoas_juridicas', $cmps);
				}

				$sql = "SELECT E.*
						FROM ".$bd."_wms.pessoas_enderecos E
						WHERE E.id_pessoas='".$camposVelho['id']."'";
				$endVelho = dbFastQuery($sql)[0];

				$end = [];
				$end['id_pessoas'] = $gId;
				$end['endereco'] = $endVelho['endereco'];
				$end['complemento'] = $endVelho['complemento'];
				$end['bairro'] = $endVelho['bairro'];
				$end['numero'] = $endVelho['numero'];
				$end['aplicacao'] = $endVelho['aplicacao'];
				$end['cep'] = $endVelho['cep'];
				$end['id_enderecos_cidades'] = $endVelho['id_cidades'];
				$end['id_enderecos_estados'] = $endVelho['id_estados'];
				$end['id_enderecos_paises'] = $endVelho['id_paises'];

				if ($endNovo['id'] > 0) {
					$id = $endNovo['id'];
					// Já existe... atualiza alguns dados
					dbUpdate('pessoas_enderecos', $end, $id);
				} else {
					// Não existe... insere
					dbInsert('pessoas_enderecos', $end);
				}

				$html.=$o->msgInfo($msg);
				$html.=$o->button("{title: Próximo; icon: arrow-right; href: ".$o->page.sprintf('&bd=%s&gPage=', $bd).IMPORTACAO_GWMS_1."&gIdVelho=".$camposVelho['id']."&gId=".$gId.sprintf('&cnpj=%s&itens=%s&umas=%s&saldos=%s&nfs=%s}', $cnpj, $itens, $umas, $saldos, $nfs));
			} else {
				$html.=$o->msgDanger("Cliente inativo");
			}
		} else {
			$html.=$o->msgDanger("Cliente não encontrado");
		}

		break;



	case IMPORTAR_DOCUMENTACAO:

        if ($usrId != 1) {
            $html .= $o->msgDanger("Usuário sem permissão para realizar este procedimento");
            $html .= $backButton;
            break;
        }

        $frm = new gForm("{columns: 3}");
        $frm->add("{name: arquivoDocumentacao; type: file; allowBlank: true; fieldLabel: Documentação WMS;}");
        $frm->add("{name: gPage; type: hidden; value: " . PROCESSAR_DOCUMENTACAO . "}");
        $html .= $frm->render($o);

        $html .= $o->msgFilter("O tamanho máximo permitido para a inclusão de arquivos é de 5Mb");
        break;

    case PROCESSAR_DOCUMENTACAO:

        if ($_FILES['arquivoDocumentacao']['error'] == UPLOAD_ERR_NO_FILE) {
            $html .= $o->msgDanger("Nenhum arquivo foi selecionado");
            $html .= $backButton;
            break;
        }

        $tipoArquivo = strtolower(pathinfo((string) $_FILES['arquivoDocumentacao']['name'], PATHINFO_EXTENSION));

        if ($tipoArquivo !== 'md') {
            $html .= $o->msgDanger("O arquivo selecionado não é um arquivo .md");
            $html .= $backButton;
            break;
        }

        if ($_FILES['arquivoDocumentacao']['size'] > 5000000) { // 5000000 eh igual a 5MB
            $html .= $o->msgDanger("O arquivo excede o tamanho limite de 5MB");
            $html .= $backButton;
            break;
        }

        $diretorioDocumentacaoWms = $gPath . "res/system/chatbot/documentacao_wms";
        $diretorioDocumentacaoWmsAntigos = $gPath . "res/system/chatbot/documentacao_wms_antigos";

        if (!is_dir($diretorioDocumentacaoWmsAntigos)) {
            mkdir($diretorioDocumentacaoWmsAntigos, 0777, true);
        }

        $uniqId = uniqid();
        $documentacoesWmsMovidos = [];
        $documentacoesWmsExistentes = glob($diretorioDocumentacaoWms ."/manual_wms*");

        foreach ($documentacoesWmsExistentes as $documentacaoWmExistente) {
            if (!is_file($documentacaoWmExistente)) {
                continue;
            }

            $nomeDocumentacao = $diretorioDocumentacaoWmsAntigos
                . "/" . pathinfo($documentacaoWmExistente)['filename']
                . "_" . $uniqId
                . "." . pathinfo($documentacaoWmExistente)['extension'];
            $documentosWmsMovidos[] = $nomeDocumentacao;
            rename($documentacaoWmExistente, $nomeDocumentacao);
        }

        $destino = $diretorioDocumentacaoWms . "/manual_wms.md";

        if (!move_uploaded_file($_FILES['arquivoDocumentacao']['tmp_name'], $destino)) {
            $html .= $o->msgDanger('Erro ao mover o arquivo para o diretório de destino');

            foreach ($documentacoesWmsMovidos as $documentacaoWmsMovido) {
                if (!is_file($documentacaoWmsMovido)) {
                    continue;
                }

                $documentacaoWmsRestaurado = substr(pathinfo($documentacaoWmsMovido)['basename'], 0, strrpos(pathinfo($documentacaoWmsMovido)['basename'], '_'));
                $nomeOriginal = $diretorioDocumentacaoWms . "/" . $documentacaoWmsRestaurado . ".md";
                rename($documentacaoWmsMovido, $nomeOriginal);
            }

            $html .= $backButton;
            break;
        }

        chmod($destino, 0664);

        redirect($o->page . '&gPage=' . CONFIRMACAO_IMPORTACAO_DOCUMENTACAO . '&gId=' . $gId);
        break;


    case CONFIRMACAO_IMPORTACAO_DOCUMENTACAO:
        $html .= $o->msgSuccess("Importação realizada com sucesso");
        $html .= $o->button('{hint: Voltar; title: Voltar; sytle: info; icon: arrow-left; url: ' . $o->page . '&gPage=' . IMPORTAR_DOCUMENTACAO . '&gId=' . $gId . '}');
        break;

}


function obtemColuna($C)
{
	if ($C<=25) {
		$chr = 65+$C;
		$col = chr($chr);
	} elseif($C<51) {
		$chr = 65+($C-25);
		$col = "A".chr($chr);
	} else {
		$chr = 65+($C-50);
		$col = "B".chr($chr);
	}

	return($col);
}
