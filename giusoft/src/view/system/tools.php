<?php

include_once __DIR__ . "/res/_classes/classes.php";

define('INICIO'             			     , 0);
define('IMPORTAR_DOCUMENTACAO'				 , 1);
define('PROCESSAR_DOCUMENTACAO'				 , 2);
define('CONFIRMACAO_IMPORTACAO_DOCUMENTACAO' , 3);
define('IMPORTACAO_GWMS'    			  	 , 10);
define('IMPORTACAO_GWMS_0'  			  	 , 20);
define('EXPURGO'           					 , 700);

if (
	in_array($gPage,
		[
			IMPORTAR_DOCUMENTACAO,
			PROCESSAR_DOCUMENTACAO,
			CONFIRMACAO_IMPORTACAO_DOCUMENTACAO
		]
	)
) {
	$html .= $o->msgTitle("Importação de documentação do WMS");
}

$mesesDeExpurgo = (intval($_REQUEST['mesesDeExpurgo']) ? intval($_REQUEST['mesesDeExpurgo']) : "12");

if ($_REQUEST['gAjax']) {
	$limite = "100";
	$tabelas['log'] = "FROM gfw_log WHERE date < DATE_SUB(NOW(), INTERVAL $mesesDeExpurgo MONTH)";

	switch ($cmd) {

		case 'log':
			$sql = "DELETE ".$tabelas['log'];
			dbQuery($sql);

			$sql = "SELECT count(id) ttl ".$tabelas['log'];
			echo dbQuery($sql)[0]['ttl'];
		break;
	}

	exit;
}

switch($gPage) {
	case INICIO:
		$html .= $o->msgTitle("Ferramentas");
		$html .= $o->button("{title: Importar Documentação; icon: download; style: primary; size: big; href: " . $o->page . "&gPage=" . IMPORTAR_DOCUMENTACAO . "}");
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
				$html.=$o->button("{title: Próximo; icon: arrow-right; href: ".$o->page."&bd=$bd&gPage=".IMPORTACAO_GWMS_1."&gIdVelho=".$camposVelho['id']."&gId=".$gId."&cnpj=$cnpj&itens=$itens&saldos=$saldos&nfs=$nfs}");
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

	case EXPURGO:
		$html.=$o->msgTitle("Expurgo");
		$html.=$o->msgSubTitle("Transferir dados não utilizados há $mesesDeExpurgo meses para arquivo morto");
		$html.=$o->msg("Selecione quais tipos de dados deverão ser expurgados:");
		$frm = new gForm("{columns: 6}");
		$frm->add("{name: gPage; type: hidden; value: ".(EXPURGO+1)."}");
		//$frm->add("{name: notas_fiscais_antigas; type: checkbox; value: 0}");
		$frm->add("{name: log; type: checkbox; value: 0}");
		$frm->add("{name: mesesDeExpurgo; fieldLabel: Meses a ignorar; type: text; value: 12}");
		$html.=$frm->render($o);
	break;

	case (EXPURGO+1):
		$notas_fiscais_antigas = gDBCheck($_REQUEST['notas_fiscais_antigas']);
		$tabelas_temporarias = gDBCheck($_REQUEST['tabelas_temporarias']);
		$mesesDeExpurgo = intval($_REQUEST['mesesDeExpurgo']);
		$log = gDBCheck($_REQUEST['log']);

		$html .= $o->msgTitle("Expurgo");
		$html .= $o->msgSubTitle("Transferir dados não utilizados há " . $mesesDeExpurgo . " meses para arquivo morto");
		$html .= $o->hr();

		// Primeiro verifica se tabelas existem e se são iguais:
		$tabelas = [""]; // Aqui coloca as tabelas que desejar
		$todosIguais = true;
		$erros = array();
		$PDO  = new PDO( 'mysql:host=localhost;dbname=wms_'.$EMPRESA, 'web', 'web' ); // Configura o banco
		$sql  = "SHOW TABLES";
		$rs   = $PDO->query( $sql );
		$tabelasNoBanco= $rs->fetchAll( PDO::FETCH_ASSOC );
		foreach ($tabelas as $tabela) {
			$tabelaExiste = false;
			foreach($tabelasNoBanco as $row) {
				if ($row["Tables_in_wms_".$EMPRESA]==$tabela) {
					$tabelaExiste = true;
				}
			}

			if ($tabelaExiste) {
				$sql = "describe ".$tabela;
				$rs = $PDO->query( $sql );
				$rsO = $rs->fetchAll( PDO::FETCH_ASSOC );
				$sql = "describe expurgo_".$tabela;
				$rs = $PDO->query( $sql );
				$rsD = $rs->fetchAll( PDO::FETCH_ASSOC );

				foreach ($rsO as $key=>$value) {
					if ($rsO[$key]['Field']!=$rsD[$key]['Field']) {
						$todosIguais = false;
						$erros[] = "Estruturas diferentes: ".$tabela." e expurgo_".$tabela;
					}
				}

			} else {
				$erros[] = "Tabela não existe: expurgo_$tabela";
			}

		}

		if (count($erros))
		{
			$html.=$o->msgDanger("Infelizmente não será possível expurgar pois ocorreram alguns erros: <br><br> ".$o->ul($erros));
			$html.=$o->msg("Edite a estrutura das tabelas para que fiquem iguais, <b>com excessão do campo ID que não pode ser autonumerado</b>.");

		} else {
			$html.="<div class='row'>";

			// $html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			// $html.="		NFs antigas";
			// $html.="	</div>";
			// $html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			// $html.='		<div id="notas_fiscais_antigas_num">---</div>';
			// $html.="	</div>";
			// $html.="	<div class='col-xs-8 col-sm-8 col-md-8 col-lg-8'>";
			// $html.='		<div class="progress"><div id="notas_fiscais_antigas" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>';
			// $html.="	</div>";

			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.="		Tabelas temporárias";
			$html.="	</div>";
			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.='		<div id="tabelas_temporarias_num">---</div>';
			$html.="	</div>";
			$html.="	<div class='col-xs-8 col-sm-8 col-md-8 col-lg-8'>";
			$html.='		<div class="progress"><div id="tabelas_temporarias" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>';
			$html.="	</div>";

			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.="		Logs";
			$html.="	</div>";
			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.='		<div id="logs_num">---</div>';
			$html.="	</div>";
			$html.="	<div class='col-xs-8 col-sm-8 col-md-8 col-lg-8'>";
			$html.='		<div class="progress"><div id="logs" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>';
			$html.="	</div>";


			$html.="</div>";

			if ($tabelas_temporarias) {
				$js = "
					tabelas_temporarias_ttl = 0;

					function tabelas_temporarias()
					{
						$.ajax({
							url: '".$o->page."&gAjax=1&mesesDeExpurgo=$mesesDeExpurgo&cmd=tabelas_temporarias',
							context: document.body
						})
						.done(function(data,textStatus){
							valor = parseInt(data);
							if (tabelas_temporarias_ttl==0)
							{
								tabelas_temporarias_ttl = valor;
							} else {
								x = Math.round((100*valor)/tabelas_temporarias_ttl);
								$('#tabelas_temporarias').css('width', x +'%').attr('aria-valuenow', x);
								$('#tabelas_temporarias_num').html(data+' ('+x+'%)');
							}
							if (valor>0)
							{
								tabelas_temporarias();
							}
						});
					}
					tabelas_temporarias();
				";
				$o->addJavascript($js);
			}

			if ($log) {
				$js = "
					log_ttl = 0;

					function log()
					{
						$.ajax({
							url: '".$o->page."&gAjax=1&mesesDeExpurgo=$mesesDeExpurgo&cmd=log',
							context: document.body
						})
						.done(function(data,textStatus){
							valor = parseInt(data);
							if (log_ttl==0)
							{
								log_ttl = valor;
							} else {
								x = Math.round((100*valor)/log_ttl);
								$('#log').css('width', x +'%').attr('aria-valuenow', x);
								$('#log_num').html(data+' ('+x+'%)');
							}
							if (valor>0)
							{
								log();
							}
						});
					}
					log();
				";
				$o->addJavascript($js);
			}


		}

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
