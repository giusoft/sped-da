<?php

define('INICIO'                                 			,0);
define('DADOS'                                  			,1);
define('CANCELAR_NOTA'                          			,2);
define('PESQUISAR'                              			,10);
define('PESQUISAR_RESULTADO'    							,11);
define('CRIAR_ATUALIZAR'                        			,20);
define('SALVAR'                    							,21);
define('EXCLUIR'                                			,30);
define('IMPORTAR'											,40);
define('VALIDAR_IMPORTACAO'									,41);
define('IMPORTAR_MULTIPLO_CONCLUIR'							,43);
define('ITENS'												,60);
define('ITENS_SALVAR'										,61);
define('ITENS_EXCLUIR'										,62);
define('FORMULARIO_IMPORTAR_ITENS_NOTA'						,64);
define('SUBSTITUIR_NFE'										,90);
define('IMPOSTOS'											,100);
define('IMPOSTOS_SALVAR'									,101);
define('TRANSFERENCIA_PROPRIETARIO_FRAGMENTADA'				, 110);
define('CONFIRMACAO_TRANSFERENCIA_PROPRIETARIO_FRAGMENTADA'	, 120);
define('ITENS_ATUALIZAR_CFOP_ORIGEM'						, 130);

// Removendo paginação e limit quando for exportação
if (
	isset($_REQUEST["gPDF"])
	|| isset($_REQUEST["gXLS"])
	|| isset($_REQUEST["gDOC"])
	|| isset($_REQUEST["gCSV"])
) {
	$gParam["PAGINACAO"]["ativo"]=0;
	$gParam["LIMITAR_VISUALIZACAO"]["ativo"]=0;
}

if (!$gAjs) {
    $html  .= $o->msgTitle("Notas fiscais de clientes");
}

$nf = new NotasFiscais('E');
$ni = new ImportacaoNFE();
if ($gId > 0) {
	$conferirImportacao=dbQuery(sprintf("SELECT nfe.id FROM nfe INNER JOIN notas on notas.id_nfe=nfe.id WHERE notas.id='%s'", $gId));
}

if ($gPage < 10) {
   $o->PDFEnabled=true;
   $o->DOCEnabled=true;
   $o->XLSEnabled=true;
   $o->CSVEnabled=true;
}

$o->addJavascript(
"
	function btnICMS(self) {
		showWait();
		var liberar = document.getElementById('icms_liberar').value;
		if (liberar==0)
		{
			$.ajax({
				method: 'POST',
				url: '".$o->page."&gAjs=1&gPage=".IMPOSTOS_SALVAR."&cmd=salvar',
				data: {
					icms_cst: document.getElementById('icms_cst').value,
					icms_orig: document.getElementById('icms_orig').value,
					icms_mod: document.getElementById('icms_mod').value,
					icms_aliquota: document.getElementById('icms_aliquota').value,
					id_notas_itens_icms: document.getElementById('id_notas_itens_icms').value,
					reducao_icms_aliquota: document.getElementById('reducao_icms_aliquota').value,
					gIdItem:document.getElementById('gIdItem').value
				},
				success: function (resp)
				{
					hideWait();
					$('.respostas').html('');
					if (resp)
					{
						$('#formICMS').prepend('<div class=\"respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-success\">Dados ICMS salvos com sucesso !</div></div>');

					} else
					{
						$('#formICMS').prepend('<div class=\"respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Houve um erro ao sakvar os dados do ICMS!</div></div>');
					}

				},
				error: function (resp)
				{
					$('.respostas').html('');
					$('#formICMS').prepend('<div class=\"respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Houve um erro ao sakvar os dados do ICMS!</div></div>');
				}
			});
		} else
		{
			hideWait();
			var situacao = document.getElementById('icms_situacao').value;
			$('.respostas').html('');
			$('#formICMS').prepend('<div class=\"respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Não foi possível atualizar o ICMS a nota foi <b>'+situacao.toLowerCase()+'</b> e portando não pode ser alterada.</div></div>');
		}
	}

	function btnIPI(self)
	{
		showWait();

		var liberar = document.getElementById('ipi_liberar').value;
		if (liberar==0)
		{
			$.ajax({
				method: 'POST',
				url: '".$o->page."&gAjs=1&gPage=".IMPOSTOS_SALVAR."&cmd=salvarIPI',
				data: {
					id_imp_ipi_cst: document.getElementById('id_imp_ipi_cst').value,
					pIPI: document.getElementById('ipi_pIPI').value,
					gIdItem: document.getElementById('ipi_gIdItem').value,
					id_notas_itens_ipi: document.getElementById('id_notas_itens_ipi').value
				},
				success: function (resp) {
					hideWait();
					$('.ipi_respostas').html('');
					if (resp)
					{
						$('#formIPI').prepend('<div class=\"ipi_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-success\">Dados do IPI salvos com sucesso!</div></div>');
					} else
					{
						$('#formIPI').prepend('<div class=\"ipi_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Houve um erro ao salvar os dados do IPI!</div></div>');
					}
				},
				error: function (resp)
				{
					$('.ipi_respostas').html('');
					$('#formIPI').prepend('<div class=\"ipi_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Houve um erro ao salvar os dados do IPI!</div></div>');
				}
			});
		} else
		{
			hideWait();
			var situacao = document.getElementById('ipi_situacao').value;
			$('.ipi_respostas').html('');
			$('#formIPI').prepend('<div class=\"ipi_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Não foi possível atualizar o IPI a nota foi <b>'+situacao.toLowerCase()+'</b> e portando não pode ser alterada.</div></div>');
		}
	}

	function btnPIS(self)
	{
	   showWait();
	   var liberar = document.getElementById('pis_liberar').value;
	   if (liberar==0)
	   {
			$.ajax({
				method: 'POST',
				url: '".$o->page."&gAjs=1&gPage=".IMPOSTOS_SALVAR."&cmd=salvarPIS',
				data:{
					id_imp_pis_cst: document.getElementById('id_imp_pis_cst').value,
					gIdItem: document.getElementById('gIdItem').value,
					pPIS: document.getElementById('pis_pPIS').value,
					id_pis: document.getElementById('id_pis').value
				},
				success: function (resp) {
					hideWait();
					$('.pis_respostas').html('');
					if (resp)
					{
						$('#formPIS').prepend('<div class=\"pis_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-success\">Dados do PIS salvos com sucesso!</div></div>');
					} else
					{
						$('#formPIS').prepend('<div class=\"pis_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Erro ao salvar dados do PIS.</div></div>');
					}
				},

				error: function (resp)
				{
					$('#formPIS').prepend('<div class=\"pis_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Erro ao salvar dados do PIS.</div></div>');
				}
		   });
	   } else
	   {
			hideWait();
			var situacao = document.getElementById('pis_situacao').value;
			$('.pis_respostas').html('');
			$('#formPIS').prepend('<div class=\"pis_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Não foi possível atualizar o PIS a nota foi <b>'+situacao.toLowerCase()+'</b> e portando não pode ser alterada.</div></div>');
	   }
	}

	function btnCOFINS(self)
	{
		showWait();
		var liberar = document.getElementById('cofins_liberar').value;
		if (liberar==0)
		{
			$.ajax({
				method: 'POST',
				url: '".$o->page."&gAjs=1&gPage=".IMPOSTOS_SALVAR."&cmd=salvarCOFINS',
				data:{
					id_imp_cofins_cst: document.getElementById('id_imp_cofins_cst').value,
					pCOFINS: document.getElementById('cofins_pCOFINS').value,
					gIdItem: document.getElementById('cofins_gIdItem').value,
					id_cofins: document.getElementById('id_cofins').value
				},
				success: function (resp) {
					hideWait();
					$('.cofins_respostas').html('');
					if (resp)
					{
						$('#formCOFINS').prepend('<div class=\"cofins_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-success\">Dados dos COFINS atualizado com sucesso !</div></div>');
					} else
					{
						$('#formCOFINS').prepend('<div class=\"cofins_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Erro ao salvar dados do COFINS.</div></div>');
					}
				},
				error: function (resp)
				{
					hideWait();
					var situacao = document.getElementById('cofins_situacao').value;
					$('#formCOFINS').prepend('<div class=\"cofins_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Erro ao salvar dados do COFINS.</div></div>');
				}
			});
		} else
		{
			hideWait();
			var situacao = document.getElementById('cofins_situacao').value;
			$('.cofins_respostas').html('');
			$('#formCOFINS').prepend('<div class=\"cofins_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Não foi possível atualizar o COFINS a nota foi <b>'+situacao.toLowerCase()+'</b> e portando não pode ser alterada.</div></div>');
		}
	}

	function modalImpostos(id, idNota){
		showWait();
		$.ajax({
			url: '".$o->page."&gAjs=1&gPage=".IMPOSTOS."&gId='+id+'&gIdNota='+idNota,
			type: 'GET',
			success: function(data){
				hideWait();
				document.querySelector('#impostosmodal_content').innerHTML = data;
			},
			error: function(){
				hideWait();
				document.querySelector('#impostosmodal_content').innerHTML = 'Erro ao carregar dados. Tente novamente mais tarde';
			}
		});
	}

");

switch($gPage) {
	case INICIO:
		$html .= '<div class="hidden-print"><form id="formPesquisa" class="form-inline" method="POST" action="index.php?g=nf_entrada">';
		$html .= $o->button("{style: info; icon: plus; caption: Novo; hint: Nova nota; size: normal; href: index.php?g=nf_entrada&gPage=".CRIAR_ATUALIZAR."}");
		$html .= '<input id="pesquisa" name="pesquisa" type="text" class="form-control input-md" placeholder="Pesquisa rápida...">&nbsp;<input type="hidden" name="g" value="nf_entrada"><input type="hidden" name="gPage" value="0">';
		$html .= '<input id="action" name="action" type="hidden" class="form-control input-md" value="filtroRapido">';
		$html .= $o->button("{icon: search; caption: Pesquisar; hint: Pesquisa Avançada; size: normal;", "javascript:btnPesquisar();");
		$javascript = "function btnPesquisar() {
			let pesquisaRapida = $('[name=\'pesquisa\']').val();
			if (pesquisaRapida)
			{
				showWait();
				$('#formPesquisa').submit();
			} else
			{
				showWait();
				location.href = 'index.php?g=nf_entrada&gPage=' + ". PESQUISAR .";
			}
		}";
		$o->addJavascript($javascript);
		$html.=$o->button("{icon: download; caption: Importar nota fiscal; href: ".$o->page."&gPage=".IMPORTAR."}");
		$html.='</form></div>';
		$html .= $o->br();
		$frm = new gForm("columns: 3");
		$conteudoModal = "Cancelar NF-e ?";
		$conteudoModal .= "<input type='hidden' name='id_nfe' id='id_nfe'/>";
		$html .= $o->modal("{title: Confirmação; cancelCaption: Fechar; url:btnConfirmarCancelarNFE(); confirm: true; name: modalCancelarNFE; size:large; }", $conteudoModal);

		if ($_POST) {
			if ($_REQUEST["action"] == "filtroRapido") {
				$where =  $nf->obtemBusca($_REQUEST, 1);
				$rs    = $nf->obtemRegistros("N.id desc", $where);
				$html .= $o->msgFilter("Pesquisar por: " . $_REQUEST['pesquisa']);
			} else {
				$nf->inner_item=true;
				$filtro = $nf->obtemBusca($_REQUEST, 2);
				$where  = $filtro["where"];
				$cabecalho = $filtro["cabecalho"];
				if ($cabecalho) {
					$html .= $o->msgFilter("Filtros selecionados: " . implode(" • ", $cabecalho));
				}

				$rs = $nf->obtemRegistros("N.id desc", $where);
			}
		} else {
			$where = "(N.tipo='E') AND (N.cancelada='0') AND (N.id_filial=".intval($_SESSION["filialAtualId"]).")";
			$rs = $nf->obtemRegistros("N.id desc", $where);
		}

		if ($where != "N.tipo='E'" && count($rs) == 1 && isset($_REQUEST["pesquisa"])) {
			$nota=$rs[0];
			redirect($o->page."&gPage=".DADOS."&gId=".$nota["id"]);
		} else {
			if ($gParam["PAGINACAO"]["ativo"] == 1) {
				$html.=$nf->pagination->render('{style:margin-top:-1.6%;}');
			}

			$html.=$nf->obtemTabelaPrincipal($rs);
			if ($gParam["PAGINACAO"]["ativo"] == 1) {
				$html.=$nf->pagination->render('{id:o; style:margin-top:-1.4%;;}');
			}
		}

		break;

	case DADOS:
		$nota  = $nf->obtemRegistro($gId);
		$frm   = $nf->geraCamposDoFormularioNota($nota, SALVAR);
		$html .= $nf->obtemCabecalho($nota);
		$html .= $frm->render($o);
		break;

	case CANCELAR_NOTA:
		if ($nf->tipo == 'E') {
			/*
				Conferir associação, caso não esteja associada a uma nota não permitir.
			*/
			$confereNota = dbQuery("SELECT id_programacao FROM notas WHERE id='{$gId}'");

			if (
				$confereNota
				&& (
					$confereNota[0]["id_programacao"] == 0
					|| is_null($confereNota[0]["id_programacao"])
				)
			) {
				$sql = "SELECT U.codigo_barras
						FROM umas_itens UI
						LEFT JOIN umas U ON U.id=UI.id_umas
						LEFT JOIN notas_itens NI ON NI.id = UI.id_notas_itens
						WHERE UI.cancelada=0 AND U.ativo=1
						AND NI.id_notas=".$gId."
						GROUP BY U.id";
				$umas = dbQuery($sql);

				if (!$umas) {
					$mtz=[];
					$mtz["data_movimento"]=date('Y-m-d H:i:s');
					$mtz["cancelada"]=1;
					$mtz["id_programacao"]=0;
					$mtz["id_pessoas_cancelou"] = $_SESSION['usrId'];
					dbUpdate("notas", $mtz, $gId);

					$sql = "UPDATE nfe
							INNER JOIN notas ON nfe.numero = notas.numero
							SET
								nfe.cancelada = 1,
								nfe.id_pessoas_cancelou = " . $_SESSION['usrId'] . ",
								nfe.data_cancelamento = now(),
								nfe.situacao = 'Cancelada'
							WHERE notas.id = '" . $gId . "'
								AND nfe.id_cliente = notas.id_pessoas_proprietario";
					dbQuery($sql);
					$html .= $o->msgSuccess("Nota cancelada com sucesso");
					$html .= $o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: info; size: normal; href: ".$o->page . "&gPage=" . INICIO);
				} else {
					$umasAssociadas = [];
					foreach ($umas as $uma) {
						$umasAssociadas[]=$uma['codigo_barras'];
					}

					$html.=$o->msgWarning("A nota não pode ser cancelada pois ela está associada a UMAs, remova o vinculo e faça o cancelamento novamente");
					$html.=$o->msgWarning(implode(" ",$umasAssociadas));
					$html.=$o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: info; size: normal; href: ".$o->page . "&gPage=" . INICIO);
				}
			} else {
				$html.= $o->msgWarning("A nota não pode ser cancelada pois ela está associada a uma programação, remova o vinculo e faça o cancelamento novamente");
				$html.=$o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: info; size: normal; href: ".$o->page . "&gPage=" . INICIO);
			}
		}

		break;

	case ITENS:
		$conteudo_modal="Deseja realmente excluir o item ?";
		$conteudo_modal.="<input type='hidden' name='gIdEnd' id='id_notas_itens' value='' />";
		$html.=$o->modal("{title: Confirmação de exclusão; cancelCaption: Fechar; url:btnConfirmarExcluirItem(); confirm: true; name: modalExcluirItem; size:large; }", $conteudo_modal);
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

		$nota  = $nf->obtemRegistro($gId);
		$item  = $nf->obtemNotaItem($gIdEnd);
		$itens = $nf->obtemRegistrosNotasItens($gId);
		$frm   = $nf->geraFormularioNotaItem($nota, $item);
		$html .= $nf->obtemCabecalho($nota);

		if (!$nf->estaAssociada($gId)) {
			$html .= $frm->render($o);
		} else {
			$html .= "<br/>";
		}

		if ($itens) {
			$html.=$nf->obtemTabelaItem($itens);
		} else {
			$html.=$o->msgInfo("Adicione itens a nota fiscal.");
		}

		return ($html);
		break;

	case ITENS_SALVAR:
		if ($gIdEnd) {
			$novoItem = $nf->modificaNotaItem($_POST);
			$item=$nf->obtemNotaItem($gIdEnd);
			userLog('Item: <a href="index.php?g=nf_entrada&gPage='.ITENS.'&gId='.$gId.'&gIdEnd='.$gIdEnd.'">'.$item["descricao"].'</a> modificado na nota fiscal id: <a href="index.php?g=nf_entrada&gPage='.DADOS.'&gId='.$gId.'">'.$gId.'</a>');
			$redirect = $o->page . "&gPage=" . ITENS . "&gId=" . $gId . "&gIdEnd=".$gIdEnd;
		} else {
			$novoItem = $nf->insereNotaItem($_POST);
			$item=$nf->obtemNotaItem($novoItem["idItem"]);
			userLog('Item: <a href="index.php?g=nf_entrada&gPage='.ITENS.'&gId='.$gId.'&gIdEnd='.$item["id"].'">'.$item["descricao"].'</a>  adicionado a nota fiscal id: <a href="index.php?g=nf_entrada&gPage='.DADOS.'&gId='.$gId.'">'.$gId.'</a>');
			$redirect = $o->page . "&gPage=" . ITENS . "&gId=" . $gId;
		}

		redirect($redirect);
		break;

	case ITENS_EXCLUIR:
		$item=$nf->obtemNotaItem($gIdEnd);
		$nf->excluirItemNota($gIdEnd);
		userLog('Exclusão do item '.$item['descricao'].' na nota fiscal id: <a href="index.php?g=nf_entrada?&gPage='.ITENS.'gId='.$gId.'">'.$gId.'</a>');
		redirect($o->page . "&gPage=" . ITENS . "&gId=" . $gId);
		break;

	case PESQUISAR:
		$html .= $nf->geraFormularioFiltro()->render($o);
		break;

	case PESQUISAR_RESULTADO:
		$html.=$nf->obtemTabelaPrincipal($rs);
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
				userLog('Nota fiscal de entrada modificada id: <a href="index.php?g=nf_entrada&gPage='.DADOS.'&gId='.$gId.'">'.$gId.'</a>');
				redirect($o->page . "&gPage=" . DADOS . "&gId=" . $gId);
			}
		} else {
			$gId = $nf->insereNota($_POST);
			userLog('Nota fiscal de entrada adicionada id: <a href="index.php?g=nf_entrada&gPage='.DADOS.'&gId='.$gId.'">'.$gId.'</a>');
			redirect($o->page . "&gPage=" . ITENS . "&gId=" . $gId);
		}

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

	case IMPORTAR:
		if (isset($_REQUEST["cmd"])) {
			$where=[];
			$where[]="(DATE(p.data_previsao)>='".date("Y-m-d H:i:s",strtotime("-".$gParam['VER_OS_DE']['valor']." day"))."' AND DATE(p.data_previsao)<='".date("Y-m-d H:i:s",strtotime("+".$gParam['VER_OS_ATE']['valor']." day"))."')";
			$where[]="(id_tipos_programacao=".intval($_REQUEST["gProgramacao"]).")";
			$where[]="(data_execucao_inicio='0000-00-00 00:00:00')";
			$where[]="(ativo=0)";
			$where[]="(id_pessoas_proprietario=".intval($gId).")";
			$sql="SELECT id, os FROM programacao p WHERE ".implode(" AND ", $where);
			// Buscar proprietário
			echo json_encode(dbQuery($sql));
			exit;
		}

		$comboProprietarios="SELECT id,apelido FROM pessoas WHERE situacao='Ativo' AND cliente=1  ORDER BY apelido";
		$frm=new gForm();
		if (isset($_REQUEST["gProgramacao"]) && intval($_REQUEST["gProgramacao"])) {
			$where=[];
			$where[]="(DATE(p.data_previsao)>='".date("Y-m-d H:i:s",strtotime("-".$gParam['VER_OS_DE']['valor']." day"))."' AND DATE(p.data_previsao)<='".date("Y-m-d H:i:s",strtotime("+".$gParam['VER_OS_ATE']['valor']." day"))."')";
			$where[]="(id_tipos_programacao=".intval($_REQUEST["gProgramacao"]).")";
			$where[]="(data_execucao_inicio='0000-00-00 00:00:00')";
			$where[]="(ativo=0)";
			$where[]="(id_pessoas_proprietario=".intval($gId).")";
			$comboOS="SELECT id, os FROM programacao p WHERE ".implode(" AND ", $where);

			$frm->addFormMessage("Importar nota fiscal e <b>criar programação de saída:</b>");
			$frm->row(
				$frm->add("{name: proprietario; fieldLabel: Vincular a proprietário; type: combo; value: 0; items:".$comboProprietarios."; onChange:mudouProprietario;}"),
				  //$frm->add("{name: id_os; fieldLabel: Vincular a OS; type: combo; value: 0; items:".$comboOS."}"),
				 $frm->add("{name: arquivo; type: file; }")
			);
			$rotaAjax=$o->page."&gPage=".IMPORTAR."&gAjs=1&cmd=mudouProprietario&gProgramacao=".$_REQUEST["gProgramacao"]."&gId=";
			$js="function mudouProprietario (id) {
					$.ajax({
						method: 'GET',
						url: '".$rotaAjax."'+id,
						success: function (resp) {
							hideWait();
							var os = JSON.parse(resp);
							if (os.length>0)
							{
								var selectProgramacao = \$select_id_os[0].selectize;
								selectProgramacao.clearOptions();
								for (let i=0; i<os.length; i++)
								{
									selectProgramacao.addOption({value: os[i].id, text: os[i].os});
								}
								selectProgramacao.setValue(os[0].id);
							}
						}
					});
			}";
			$o->addJavascript($js);
		} else {
			$comboTipoImportacao = [
				"0"=> "Normal",
				"1"=> "Preencher combustível"
			];
			$frm->addFormMessage("<b>Importar nota fiscal</b>");

			if ($gParam['IMPORTAR_NOTA_AGRUPANDO_ITENS']['ativo']) {
				$campoAgruparItem = $frm->add("{name: agrupar_itens_nota; fieldLabel: Agrupar itens; type: checkbox; value: 0; disabled: disabled;}");
			}

			$frm->row(
				$frm->add("{name: cadastroCliente; fieldLabel: Cadastrar cliente automaticamente?; type: checkbox; value: 0;}"),
				$frm->add("{name: importarComoSaida; fieldLabel: Importar como saída; type: checkbox; value: 0;}"),
				$frm->add("{name: crossdocking; fieldLabel: Operação de cross docking?; type: checkbox; value: 0;}"),
				$frm->add("{name: venda; fieldLabel: Nota de venda; type: checkbox; value: 0;}"),
				$frm->add("{name: priorizarSkuInativo; fieldLabel: Priorizar sku inativo; type: checkbox; value: 0;}"),
				$campoAgruparItem
			);

			$frm->row(
				$frm->add("{name: proprietario; fieldLabel: Vincular a proprietário; type: combo; value: 0; items:".$comboProprietarios."}"),
				$frm->add("{name: id_pessoas_fornecedor; fieldLabel: Fornecedor; type: combo; items: ".$sp['combo_fornecedores']."}")
			);

			$frm->add("{name: arquivo; type: file; multiple:true;}");
		}

		$frm->add("{name: tipo; type: hidden; value: 1;}");
		$frm->add("{name: gPage; type: hidden; value: ". VALIDAR_IMPORTACAO ." ;}");
		$html.=$frm->render($o);
		break;

	case VALIDAR_IMPORTACAO:

		if (gDBCheck($_REQUEST['venda']) && (!$_REQUEST['proprietario'] || !$_REQUEST['id_pessoas_fornecedor'])) {
			$html .= $o->msgDanger("Preencha os campos de proprietário e fornecedor para prosseguir com a nota a nota de venda, pois o valor considerado será indicado para o fornecedor");
			$html .= $backButton;
			break;
		}

		$erros = [];

		if (isset($_REQUEST["gProgramacao"])) {
			$rotaBack = $o->page . "&gPage=" . IMPORTAR . "&gProgramacao=" . $_REQUEST["gProgramacao"] . "&gBack=" . $_REQUEST["gBack"];
		} else {
			$rotaBack = $o->page . "&gPage=" . IMPORTAR;
		}

		if ($_REQUEST["cadastroCliente"] != "on" && $_REQUEST["proprietario"] == 0) {
			$erros[] = "Selecione um proprietário caso não queira criar o cliente automaticamente";
		}

		if (!$_FILES['arquivo']['name'][0]) {
			$erros[] = "Você não selecionou arquivo";
		}

		if ($erros) {
			$msg = mostraErros("Verifique o arquivo a ser processado antes de continuar.", $erros);
			$html.=$o->msgDanger($msg);
			// $html.=$o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: default; size: normal; href:".$rotaBack.";}");
			$html .= $backButton;
			return;
		}

		$arquivos    	  = [];
		$erros		  	  = [];
		$_SESSION["xmls"] = [];

		$chavesNfe = [];
		$quantidadeArquivos =  count($_FILES['arquivo']['tmp_name']);
		for ($i = 0; $i < $quantidadeArquivos; $i++) {
			if (!$_FILES['arquivo']['name'][$i]) {
				$erros[] = "Existe um arquivo incorreto na lista. Por favor verifique os arquivos antes de enviar.";
				continue;
			}

			$arquivo = trim(@file_get_contents($_FILES['arquivo']['tmp_name'][$i]));
			$xml     = simplexml_load_string($arquivo);
			$sai     = is_string($arquivo) && (is_object($xml) || is_array($xml));
			$in 	 = ($_REQUEST["proprietario"] == 0) ? new ImportacaoNFE($arquivo) : new ImportacaoNFE($arquivo, $_REQUEST["proprietario"]);
			$chaveNfeImportar = (string) $xml->NFe->infNFe['Id'];
			if (in_array($chaveNfeImportar, $chavesNfe)) {
				$erros[] = "A nota fiscal ". $_FILES['arquivo']['name'][$i] ." foi carregada mais de uma vez. ";
			}

			$chavesNfe[] = $chaveNfeImportar;

			if (!$sai) {
				$erros[] = "Existe um arquivo incorreto na lista. Por favor verifique os arquivos antes de enviar.";
				break;
			}

			if (!$xml->NFe) {
				$erros[] = "Existe um arquivo incorreto na lista. Por favor verifique os arquivos antes de enviar.";
				break;
			}

			$in->exibir($_REQUEST["tipo"], $_REQUEST["cadastroCliente"]);

			if (!empty($in->obtemErros())) {
				$erros[] = $in->obtemErros();
			}

			$_SESSION["xmls"][] = $arquivo;
			$arquivos[] = $xml;
		}

		if (!$erros) {
			$html.=$o->tableBegin("big", true, true);
			$mtz   = [];
			$mtz[] = "<-NF";
			$mtz[] = "<-Proprietário original";
			$mtz[] = "<-Proprietário vincular";
			$mtz[] = "<-Emissão";
			$html .= $o->tableRow($mtz, "header");

			foreach ($arquivos as $arquivo) {
				if ($arquivo->NFe->infNFe) {
					if (isset($_REQUEST["proprietario"]) && intval($_REQUEST["proprietario"])>0) {
						$sql="SELECT nome FROM pessoas WHERE id=".intval($_REQUEST["proprietario"]);
						$desc_proprietario=dbQuery($sql)[0]["nome"];
					} else {
						$desc_proprietario=trim($arquivo->NFe->infNFe->dest->xNome);
					}

					$mtz=[];
					$mtz[]="<-".trim($arquivo->NFe->infNFe->ide->nNF);
					$mtz[]="<-".trim($arquivo->NFe->infNFe->dest->xNome);
					$mtz[]="<-".$desc_proprietario;
					$mtz[]="<-".date("d/m/Y", strtotime(trim($arquivo->NFe->infNFe->ide->dhEmi)));
					$html.=$o->tableRow($mtz, "detail");
				}
			}

			$html.=$o->tableEnd();
		}

		for ($i = 0; $i < $quantidadeArquivos; $i++) {

			$arquivo = trim(@file_get_contents($_FILES['arquivo']['tmp_name'][$i]));
			$xml     = simplexml_load_string($arquivo);
			$in 	 = ($_REQUEST["proprietario"] == 0) ? new ImportacaoNFE($arquivo) : new ImportacaoNFE($arquivo, $_REQUEST["proprietario"]);

			$in->exibir($_REQUEST["tipo"], $_REQUEST["cadastroCliente"]);

			if (!$erros) {
				$cliente   = $in->obtemCliente();
				$nota      = $in->obtemNota();
				$nfe       = $in->obtemNFE();
				$endereco  = $in->obtemEndereco();
				$notaItens = $in->obtemItensExibir();
				$volume    = $nota['volume'];
				$infCliente .= $o->tableBegin("big",true);
				$mtz = [];

				if ($cliente["automatico"]) {
					$mtz[]="<-Detalhes";
				}

				$html .= $o->msgSubtitle("Nota Fiscal");
				$html .= $in->montarCabecalhoConfirmacao($nota, $nfe, $cliente, $xml, $_REQUEST["proprietario"]);
				$infItens .= $o->tableBegin("big", true);
				$mtz   = [];
				$mtz[] = "<>Situação";
				$mtz[] = "<-Código";
				$mtz[] = "<- SKU";
				$mtz[] = "<-Descrição";
				$mtz[] = "<-Fornecedor";
				$mtz[] = "<-Unidade";
				$mtz[] = "->Quantidade";
				$mtz[] = "->Valor";
				$mtz[] = "<>CFOP";
				$infItens .= $o->tableRow($mtz,"header");
				$colspan = count($mtz) - 3;
				$ttlVolumes = 0;

				$html .= $o->msgSubtitle("Itens da nota");

				include_once $gPath."res/_classes/padrao/cadastros.php";
				$persistencia = new Itens();
				foreach ($notaItens as $item) {
					$mtz = [];
					$itemExiste = $in->checarItem($item['codigo']);
					$skuExiste  = $in->checarSKU($itemExiste['id'], $in->checarUnidade($item['unidade'])['id']);
					if ($itemExiste) {
						$aptoNoBanco = gFieldById("itens", $itemExiste['id'], "apto");
						$aptoSKUs    = $persistencia->aptoSKUs($itemExiste['id']);
						$aptoAreas   = $persistencia->aptoAreas($itemExiste['id']);
						if ($aptoNoBanco != $aptoSKUs || $aptoNoBanco != $aptoAreas) {
							$apto = (int) ($aptoSKUs && $aptoAreas);
							$persistencia->aptoAtualiza($itemExiste['id'], $apto);
						}
					}

					if (!$skuExiste) {
						$mtz[] = $o->label("Não cadastrado", "danger");
						if ($gParam['USA_POSICAO_COMO_UMA']['ativo']) {
							$o->addJavascript("$('#gSubmitButton').attr('style', 'display:none');");
						}
					} elseif (!gFieldById("itens", $itemExiste['id'], "apto")) {
						$mtz[] = $o->label("Inapto", "danger");
						if ($gParam['USA_POSICAO_COMO_UMA']['ativo']) {
							$o->addJavascript("$('#gSubmitButton').attr('style', 'display:none');");
						}
					} else {
						$mtz[] = $o->label("Apto", "success");
					}

					if ($_SESSION['usrId'] == 1) {
						$mtz[] = "<-" . $item["codigo"] . '<br>' . $o->small("ID I: " . $itemExiste['id']);
					} else {
						$mtz[] = "<-" . $item["codigo"];
					}

					if ($_SESSION['usrId'] == 1) {
						$mtz[] = "<-" . $skuExiste["codigo"] . '<br>' . $o->small("ID SK: " . $skuExiste['id']);
					} else {
						$mtz[] = "<-" . $skuExiste["codigo"];
					}

					$mtz[] = "<-" . $item["item"];
					$sql = 'SELECT apelido FROM pessoas WHERE id =' . (int) ($_POST['id_pessoas_fornecedor']) . " LIMIT 1";
					$fornecedor = dbQuery($sql);

					$mtz[] = "<-" . $fornecedor[0]['apelido'];
					$mtz[] = "<-" . $item["unidade"];
					$mtz[] = "->" . gFloat($item["quantidade_comercial"]);
					$mtz[] = "->" . gFloat($item["valor_produto"]);
					$mtz[] = "<>" . $item["cfop"];
					$ttlVolumes += floatval($item["quantidade_comercial"]);
					$ttlValor+=floatval($item["valor_produto"]);

					$corLinha = (!$itemExiste || !$skuExiste) ? 'text-danger' : 'detail';
					$infItens .= $o->tableRow($mtz, $corLinha);
				}

				$mtz = [];
				$mtz[] = "~"  . $colspan . "->Total";
				$mtz[] = "->" . gFloat($ttlVolumes);
				$mtz[] = "->" . gFloat($ttlValor);
				$mtz[] = "";
				$infItens .= $o->tableLine();
				$infItens .= $o->tableRow($mtz,"detail");

				$infItens   .= $o->tableEnd();
				$painel     .= $infItens;
				$html       .= $painel;
				$infCliente .= $o->tableBegin("big",true);

				$painel = "";
				$infItens = "";
			}
		}

		if ($erros) {
			$html .= $o->msgDanger("Falhas de validação: ".$o->ul($erros));
			$html .= $backButton;
			break;
		}

		// $frm->add("{name: json_object; type:hidden; value:;}"); //
		$frm = new gForm();
		$frm->add("{name: gPage; type: hidden; value:".IMPORTAR_MULTIPLO_CONCLUIR.";}");
		$frm->add("{name: crossdocking; type: hidden; value: ".$_POST['crossdocking'].";}");
		$frm->add("{name: id_pessoas_fornecedor; type: hidden; value: ".$_POST['id_pessoas_fornecedor'].";}");
		$frm->add("{name: proprietario; type: hidden; value:".$_REQUEST["proprietario"].";}");
		$frm->add("{name: cadastroCliente; type: hidden; value: ".$_POST['cadastroCliente'].";}");
		$frm->add("{name: tipo; type: hidden; value: ".$_POST['tipo'].";}");
		$frm->add("{name: importarComoSaida; type: hidden; value: " . gDBCheck($_REQUEST['importarComoSaida']) . ";}");
		$frm->add("{name: venda; type: hidden; value: " . gDBCheck($_REQUEST['venda']) . ";}");
		$frm->add("{name: priorizarSkuInativo; type: hidden; value: " . gDBCheck($_REQUEST['priorizarSkuInativo']) . ";}");
		if ($gParam['IMPORTAR_NOTA_AGRUPANDO_ITENS']['ativo']) {
			$frm->add("{name: agrupar_itens_nota; type: hidden; value: " . gDBCheck($_REQUEST['agrupar_itens_nota']) . ";}");
		}

		$frm->addButton("{icon: arrow-left; title: Voltar; hint: Cancelar e voltar a página anterior; style: default; size: small; href: ".$rotaBack."}");
		jsButtonVoltar();
		$html .= $frm->render($o);
		$html .= $o->msgFilter("Confirme as notas a serem importadas");

		if (gDBCheck($_REQUEST['agrupar_itens_nota'])) {
			$html .= $o->msgAlert('Agrupamento de itens por código e valor foi ativado');
		}

		break;

	case IMPORTAR_MULTIPLO_CONCLUIR:
		$arquivos = $_SESSION["xmls"];
		if (!$arquivos) {
			$html .= $o->msgDanger('Tente importar novamente');
			$html .= $backButton;
			break;
		}

		foreach ($arquivos as $arquivo) {
			$xml 	= simplexml_load_string((string) $arquivo);
			$in 	= ($_REQUEST["proprietario"] > 0) ? new ImportacaoNFE($arquivo, $_REQUEST["proprietario"]) : new ImportacaoNFE($arquivo);
			$idNota = $in->processar($_REQUEST["tipo"], $_REQUEST["cadastroCliente"], gDBCheck($_POST['crossdocking']));
			$chave 	= $in->obtemChave();

			if (isset($_REQUEST["gBack"])) {
				$sql  = "SELECT id_programacao FROM notas WHERE id_nfe=".intval($idNota);
				$nota = dbQuery($sql);
				$rota = "index.php?g=programacao&gPage=10&gId=".$nota[0]["id_programacao"];
				redirect($rota);
			} else {
				$rota = $o->page."&gPage=".INICIO;
			}

			$html .= $o->msg("Nota fiscal nº: " . $in->nota['numero'] . " (emissão em ".gDate($in->nota['data_emissao']).")");
			userLog('Nota fiscal eletrônica de chave: <a href="index.php?g=nf_entrada&gPage=1&gId='.$idNota.'">'.$chave.'</a> importada.');
			if($in->itensDuplicados) {
				$html .= $o->msgAlert("ATENÇÃO: Esta NF possui itens com cadastros de SKU duplicados.<BR>Favor verificar se as informações inseridas estão corretas.");
				$html .= $o->button("{icon: file-download; caption: ".$in->nota['numero']."; hint: ".$in->nota['numero']."; style: default; size: normal; href:index.php?g=nf_entrada&gPage=".ITENS."&gId=".$in->nota['id']." }");
			}
		}

		if (count($in->obtemErros()) == 0) {
			$html .= $o->msgSuccess("Arquivos processados corretamente");
			unset($_SESSION["xmls"]);
			$html .= $o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: default; size: normal; href: ".$o->page."&gPage=" . INICIO);
		} else {
			$msg   = mostraErros("Não foi possível interpretar o(s) arquivo(s), pois foram encontrados o(s) erro(s):", $in->obtemErros());
			$html .= $o->msgDanger($msg);
			$html .= $o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: info; size: normal; href: ".$o->page."&gPage=" . IMPORTAR);
		}

		break;

	case IMPOSTOS:

		/**
		 * ICMS
		 */
		$confereNota=dbQuery("SELECT nfe.id FROM nfe INNER JOIN notas on notas.id_nfe=nfe.id WHERE notas.id='".$_REQUEST["gIdNota"]."'");
		$icms=$nf->obtemDadosIcms($gId);
		$valueIcmsCst=(count($icms)>0) ? $icms["id_imp_icms_cst"] : 7;
		$valueIcmsOri=(count($icms)>0) ? $icms["id_imp_icms_origem"] : 1;
		$frm = new gForm("{id:formICMS; onClickSubmit: btnICMS; columns:1;}");
		$frm->add("{allowBlank: false;name: icms_cst; fieldLabel: CST; type: combo; items: ".$sp["combo_icms_cst"]."; value:".$valueIcmsCst.";}");
		$frm->add("{allowBlank: false; name: icms_orig; fieldLabel: Origem; type: combo; items: ".$sp["combo_imp_icms_origem"]."; value:".$valueIcmsOri.";}");
		$frm->add("{allowBlank: true; name: icms_mod; fieldLabel: Modalidade; type: combo; items: ".$sp["combo_imp_icms_mod"]."; value:".$icms["id_imp_icms_mod"].";}");
		$frm->add("{allowBlank: true; name: icms_aliquota; fieldLabel: Alíquota; type: number; value:".gFloat($icms["pICMS"]).";}");
		$frm->add("{allowBlank: true; name: reducao_icms_aliquota; fieldLabel: Percentual Redução Alíquota ; type: number; value:".gFloat($icms["reducao_icms_aliquota"]).";}");
		$frm->add("{type:hidden; name:gPage; value: ".IMPOSTOS_SALVAR."}");
		$frm->add("{type:hidden; name:tipo; value: icms}");
		$frm->add("{type:hidden; name:gIdItem; value: " . $gId . "}");
		$frm->add("{type:hidden; name:id_notas_itens_icms; value: ".$icms["id"].";}");
		if ($confereNota) {
			$frm->add("type: hidden; name: icms_liberar; value: 1;");
			$frm->add("type: hidden; name: icms_situacao; value: Importada;");
		} else {
			$frm->add("type: hidden; name: icms_liberar; value:0;");
			$frm->add("type: hidden; name: icms_situacao; value:;");
		}

		$abaIcms = $frm->render($o);
		/**
		 * IPI
		 */
		$ipi = $nf->obtemDadosIpi($gId);
		$valueIpiCst = ($ipi) ? $ipi["id_imp_ipi_cst"] : 1;
		$frm=new gForm("{id:formIPI; onClickSubmit: btnIPI}");
		$frm->row(
			$frm->add("type:combo; name: id_imp_ipi_cst; fieldLabel: CST; items:".$sp["combo_imp_ipi_cst"]."; value:".$valueIpiCst.";")
		);
		$frm->row(
			$frm->add("type:number; name: ipi_pIPI; fieldLabel: Aliquota; value:".gFloat($ipi["pIPI"]).";")
		);
		$frm->add("{type:hidden; name:tipo; value: ipi}");
		$frm->add(sprintf('{type:hidden; name:ipi_gIdItem; value: %s}', $gId));
		$frm->add("{type:hidden; name:id_notas_itens_ipi; value:".$ipi["id"].";}");
		if ($confereNota) {
			$frm->add("type: hidden; name: ipi_liberar; value:1;");
			$frm->add("type: hidden; name: ipi_situacao; value: Importada;");
		} else {
			$frm->add("type: hidden; name: ipi_liberar; value:0;");
			$frm->add("type: hidden; name: ipi_situacao; value:;");
		}

		$abaIpi = $frm->render($o);

		/**
		 * PIS
		 */
		$pis=$nf->obtemDadosPis($gId);
		$valuePisCst = ($pis) ? $pis["id_imp_pis_cst"] : 1;
		$frm = new gForm("{id:formPIS; onClickSubmit: btnPIS}");
		$frm->row(
			$frm->add("{type:combo; name: id_imp_pis_cst; fieldLabel: CST; value:".$valuePisCst."; items:".$sp["combo_imp_pis_cst"].";}")
		);
		$frm->row(
			$frm->add("{type:number; name: pis_pPIS; fieldLabel: Aliquota; value:".gFloat($pis["pPIS"]).";}")
		);
		$frm->add("{type:hidden; name:tipo; value: pis}");
		$frm->add("{type:hidden; name:pis_gIdItem; value: " . $gId . "}");
		$frm->add("{type:hidden; name:id_pis; value:".$pis["id"].";}");
		if ($confereNota) {
			$frm->add("type: hidden; name: pis_liberar; value:1;");
			$frm->add("type: hidden; name: pis_situacao; value: Importada;");
		} else {
			$frm->add("type: hidden; name: pis_liberar; value:0;");
			$frm->add("type: hidden; name: pis_situacao; value:;");
		}

		$abaPis = $frm->render($o);
		/**
		 * COFINS
		 */
		$cofins=$nf->obtemDadosCofins($gId);
		$valueCofinsCst = ($cofins) ? $cofins["id_imp_cofins_cst"] : 1;
		$frm = new gForm("{id:formCOFINS; onClickSubmit: btnCOFINS}");
		$frm->row(
			$frm->add("type: combo; name:id_imp_cofins_cst; fieldLabel:CST; value:".$valueCofinsCst."; items:".$sp["combo_imp_cofins_cst"].";")
		);
		$frm->row(
			$frm->add("type: number; name:cofins_pCOFINS; fieldLabel:Alíquota; value:".gFloat($cofins["pCOFINS"]).";")
		);
		$frm->add("{type:hidden; name:tipo; value: cofins}");
		$frm->add("{type:hidden; name:cofins_gIdItem; value: " . $gId . "}");
		$frm->add("{type:hidden; name:id_cofins; value:".$cofins["id"].";}");
		if ($confereNota) {
			$frm->add("type: hidden; name: cofins_liberar; value:1;");
			$frm->add("type: hidden; name: cofins_situacao; value: Importada;");
		} else {
			$frm->add("type: hidden; name: cofins_liberar; value:0;");
			$frm->add("type: hidden; name: cofins_situacao; value:;");
		}

		$abaCofins = $frm->render($o);
		$tabs = [];
		$tabs[] = $o->addTabItem("ICMS",$abaIcms);
		$tabs[] = $o->addTabItem("IPI",$abaIpi);
		$tabs[] = $o->addTabItem("PIS",$abaPis);
		$tabs[] = $o->addTabItem("COFINS",$abaCofins);
		$html.= $o->tabRender();
		break;

	case IMPOSTOS_SALVAR:
		switch ($_REQUEST["cmd"]) {
			case "salvar":
				$mtz=[];
				$mtz["id_notas_itens"]=intval($_REQUEST["gIdItem"]);
				$mtz["id_imp_icms_cst"]=intval($_REQUEST["icms_cst"]);
				$mtz["id_imp_icms_origem"]=intval($_REQUEST["icms_orig"]);
				$mtz["id_imp_icms_mod"]=intval($_REQUEST["icms_mod"]);
				$mtz["reducao_icms_aliquota"]=gDBFloat(str_replace(".", ",", $_REQUEST["reducao_icms_aliquota"]));
				$mtz["pICMS"]=gDBFloat(str_replace(".", ",", $_REQUEST["icms_aliquota"]));
				if ($_REQUEST["id_notas_itens_icms"] > 0) {
					dbUpdate("notas_itens_icms", $mtz, $_REQUEST["id_notas_itens_icms"]);
					$id=$_REQUEST["id_notas_itens_icms"];
				} else {
					$id=dbInsert("notas_itens_icms", $mtz, true);
				}

				echo json_encode($id);
				exit;
			case "salvarIPI":
				$mtz=[];
				$mtz["id_notas_itens"]=intval($_REQUEST["gIdItem"]);
				$mtz["id_imp_ipi_cst"]=intval($_REQUEST["id_imp_ipi_cst"]);
				$mtz["pIPI"]=gDBFloat(str_replace(".", ",", $_REQUEST["pIPI"]));
				if ($_REQUEST["id_notas_itens_ipi"]>0)
				{
					dbUpdate("notas_itens_ipi", $mtz, intval($_REQUEST["id_notas_itens_ipi"]));
					$id=$_REQUEST["id_notas_itens_ipi"];
				} else
				{
					$id=dbInsert("notas_itens_ipi", $mtz, true);
				}

				echo json_encode($id);
				exit;
			case "salvarPIS":
				$mtz=[];
				$mtz["id_notas_itens"]=intval($_REQUEST["gIdItem"]);
				$mtz["id_imp_pis_cst"]=intval($_REQUEST["id_imp_pis_cst"]);
				$mtz["pPIS"]=gDBFloat(str_replace(".", ",", $_REQUEST["pPIS"]));
				if ($_REQUEST["id_pis"] > 0) {
					dbUpdate("notas_itens_pis", $mtz, intval($_REQUEST["id_pis"]));
					$id=intval($_REQUEST["id_pis"]);
				} else {
					$id=dbInsert("notas_itens_pis", $mtz, true);
				}

				echo json_encode($id);
				exit;
			case "salvarCOFINS":
				$mtz=[];
				$mtz["id_notas_itens"]=intval($_REQUEST["gIdItem"]);
				$mtz["id_imp_cofins_cst"]=intval($_REQUEST["id_imp_cofins_cst"]);
				$mtz["pCOFINS"]=gDBFloat(str_replace(".", ",", $_REQUEST["pCOFINS"]));
				if ($_REQUEST["id_cofins"]) {
					dbUpdate("notas_itens_cofins", $mtz, intval($_REQUEST["id_cofins"]));
					$id=intval($_REQUEST["id_cofins"]);
				} else {
					$id=dbInsert("notas_itens_cofins", $mtz, true);
				}

				echo json_encode($id);
				exit;
		}

		break;

	case SUBSTITUIR_NFE:
		/* Substituir NF-e */
		$nota = $nf->obtemRegistros("", "(N.id='{$gId}')")[0];
		$itens = $nf->obtemRegistrosNotasItens($gId);
		$inItem = [];
		$isn = [];
		foreach($itens as $item) {
			$where=[];
			$where[]="(notas.id <> '".$nota["id"]."')";
			$where[]="(notas_itens.id_itens_skus = '".$item["id_itens_skus"]."')";
			$where[]="(notas_itens.quantidade = '".$item["quantidade"]."')";
			if ($item["peso_bruto"]) {
				$where[]="(notas_itens.peso_bruto = '".$item["peso_bruto"]."')";
			}

			if ($item["peso_liquido"]) {
				$where[]="(notas_itens.peso_liquido='".$item["peso_liquido"]."')";
			}

			if ($item["valor"]) {
				$where[]="(notas_itens.valor='".$item["valor"]."')";
			}

			if ($item["data_fabricacao"]) {
				$where[]="(notas_itens.data_fabricacao='".$item["data_fabricacao"]."')";
			}

			if ($item["lote"]) {
				$where[]="(notas_itens.lote='".$item["lote"]."')";
			}

			$where=implode(" AND ", $where);
			$confere=$nf->obtemRegistrosNotasItens($gId, 0, $where);
			if ($confere && !in_array($confere[0]["id_notas"], $isn)) {
				$isn[] = $confere[0]["id_notas"];
			}
		}

		if (!$isn) {
			$html.=$o->msgInfo("Nenhuma nota encontrada para substituição.");
		} else {
			$inNota=implode(", ", $isn);
			$sql = "SELECT
						N.id, N.numero
					FROM notas N
					LEFT JOIN pessoas P ON N.id_pessoas_proprietario = P.id
					WHERE N.id in ({$inNota})";

			$frm = new gForm('{columns: 3}');
			$frm->add("{allowBlank:false; name:id_nota; fieldLabel:Nota para substituir: ; type:combo; items:".$sql.";}");
			$html.=$frm->render($o);
		}

		break;

	case TRANSFERENCIA_PROPRIETARIO_FRAGMENTADA:
		$sql = "SELECT
					DISTINCT GROUP_CONCAT(programacao.id) AS id
				FROM notas_itens
				JOIN  programacao_itens ON programacao_itens.id_notas_itens  = notas_itens.id
				JOIN programacao ON programacao.id = programacao_itens.id_programacao
				WHERE notas_itens.id_notas = {$gId} AND programacao.cancelada = 0";
		$verificaSeTemOs = dbQuery($sql)[0]['id'];

		if ($verificaSeTemOs) {
			redirect($o->page . "&gPage=" . CONFIRMACAO_TRANSFERENCIA_PROPRIETARIO_FRAGMENTADA . "&gId=" . $gId . "&osGeradas=" . $verificaSeTemOs);
		}

		$uma = new UMA();

		//Busca os itens associados a Nota
		$itens = dbQuery('SELECT * FROM notas_itens WHERE id_notas = ' . $gId);
		$nota = dbQuery('SELECT id_pessoas_proprietario, id_pessoas_fornecedor, numero FROM notas WHERE id = ' . $gId)[0];

		//Cria uma lista com os itens a serem inseridos na OS, separando 10 itens por OS
		$listaItens = array_chunk($itens, 5);
		$quantidadeItensNota = count($listaItens);

		foreach ($listaItens as $key => $itens) {
			//Preenche os campos referente aos dados da OS

			$campos = [];
			$campos['ativo'] = 0;
			$campos['id_filial'] = $_SESSION['filialAtualId'];
			$campos['id_itens_skus_kit'] = 0;
			$campos['id_areas'] = 0;
			$campos['id_areas_direcionar'] = 0;
			$campos['id_areas_direcionar_falta'] = 0;
			$campos['id_areas_direcionar_sobra'] = 0;
			$campos['id_tipos_programacao'] = 26;
			$campos['id_pessoas_proprietario'] = $nota['id_pessoas_fornecedor'];
			$campos['id_pessoas_destinatario'] = $nota['id_pessoas_proprietario'];
			$campos['id_pessoas_colaborador'] = 0;
			$campos['data_previsao'] = date('Y-m-d H:i:s', strtotime('+15 minute', strtotime(date('Y-m-d H:i:s'))));
			$campos['numero_cliente'] = 0;
			$campos['observacoes'] = 'Nota de venda fragmentada [' . $nota['numero'] . '] - ' . ($key + 1) . '/' . $quantidadeItensNota;
			$campos['os'] = $uma->novaOS();
			$campos['data_cadastro'] = date('Y-m-d H:i:s');
			$campos['data_previsao_inicial'] = date('Y-m-d H:i:s', strtotime('+30 minute', strtotime(date('Y-m-d H:i:s'))));
			$campos['id_pessoas_criou'] = $_SESSION['usrId'];
			$campos['conferir_por_apanha'] = 0;
			$campos['crossdocking'] = 0;
			$campos['usar_saldos'] = 1;
			$idNovaOs[$key] = dbInsert("programacao", $campos, true);

			//Insere os itens na OS
			foreach ($itens as $item) {
				$itensParaInserir = [];
				$itensParaInserir['id_programacao'] = $idNovaOs[$key];
				$itensParaInserir['id_itens_skus'] = $item['id_itens_skus'];
				$itensParaInserir['quantidade'] = $item['quantidade'];
				$itensParaInserir['id_notas_itens'] = $item['id'];
				$itensParaInserir['peso_liquido'] = $item['peso_liquido'];
				$itensParaInserir['peso_bruto'] = $item['peso_bruto'];
				$itensParaInserir['m2'] = $item['m2'];
				$itensParaInserir['m3'] = $item['m3'];
				$itensParaInserir['valor'] = $item['valor'];
				$itensParaInserir['lote'] = $item['lote'];
				$itensParaInserir['data_fabricacao'] = $item['data_fabricacao'];
				$itensParaInserir['data_validade'] = $item['data_vencimento'];
				dbInsert("programacao_itens", $itensParaInserir);
			}
		}

		$url = implode(',', $idNovaOs);
		redirect($o->page . "&gPage=" . CONFIRMACAO_TRANSFERENCIA_PROPRIETARIO_FRAGMENTADA . "&gId=" . $gId . "&osGeradas=" . $url);
		break;

	case CONFIRMACAO_TRANSFERENCIA_PROPRIETARIO_FRAGMENTADA:
		$nota  = $nf->obtemRegistro($gId);
		$html .= $nf->obtemCabecalho($nota);

		$idOs  = dbQuery("SELECT os FROM programacao WHERE id IN (" . $_REQUEST['osGeradas'] . ") AND CANCELADA = 0");

		$html .= $o->msgSubTitle("OSs Fragmentadas");
		$html .= $o->tableBegin("small", true);
		$mtz   = [];
		$mtz[] = "<- OSs Fragmentadas";
		$html .= $o->tableRow($mtz, "header");

		foreach($idOs as $os) {
			$mtz   = [];
			$mtz[] = "<-" . linkParaOS($os['os']);
			$html .= $o->tableRow($mtz, "detail");
		}

		$html .= $o->tableEnd();
		$html .= $backButton;
		break;
}
