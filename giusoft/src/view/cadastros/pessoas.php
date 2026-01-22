<?
// Roteamento para seções dentro desta página
define("INICIO", 							0);
define("INICIO_PESQUISAR", 					1);
define("INICIO_PESQUISAR_RESULTADO",		2);
define("CAPA", 								10);
define("DADOS", 							20);
define("DADOS_SALVAR", 						21);
define("ENDERECOS", 						30);
define("ENDERECOS_SALVAR", 					31);
define("ENDERECOS_NOVO", 					32);
define("ENDERECOS_EXCLUIR", 				33);
define("FILIAL", 							40);
define("FILIAL_SALVAR", 					41);
define("FILIAL_CANCELAR", 					42);
define("OCORRENCIAS", 						50);
define("OCORRENCIAS_SALVAR", 				51);
define("OCORRENCIAS_NOVA", 					52);
define("OCORRENCIAS_CANCELAR",				53);
define("ANEXOS", 							60);
define("ANEXOS_ADICIONAR",					61);
define("ANEXOS_REMOVER", 					62);
define("PERMISSOES", 						70);
define("PERMISSOES_ADICIONAR",				71);
define("PERMISSOES_EXCLUIR", 				72);
define("PERMISSOES_EXCLUIR_TODAS",			73);
define("PERMISSOES_COPIAR",					74);
define("PERMISSOES_COPIAR_SALVAR",			75);
define("REGISTRO_AVANCAR",					100);
define("REGISTRO_VOLTAR",					101);
define("WEBCAM", 							102);
define("WEBCAM_SALVAR", 					103);


$paginasPodeExportar = [
	INICIO,
	INICIO_PESQUISAR_RESULTADO
];

if (in_array($gPage, $paginasPodeExportar)) {
	$o->PDFEnabled = true;
	$o->DOCEnabled = true;
	$o->XLSEnabled = true;
	$o->CSVEnabled = true;
}

$html.=$o->msgTitle("Cadastro de pessoas");

$gPage=intval($gPage);
$gId=intval($gId);
$gPathUsrFiles=$gPath."files/pessoas/";
$http_usr_files=$http_base."files/pessoas/";
$agora=date('Y-m-d H:i:s');

include_once __DIR__ . "/../../Model/PessoasFisicas.php";
$persistencia = new PessoasFisicas();

switch ($gPage)
{


	case INICIO:
		$html .= '<div class="hidden-print"><form class="form-inline" method="POST" action="index.php?g=pessoas">';
		$html .= $o->button("{icon: plus; caption: Novo; hint: Cadastrar um novo item; style: info; size: normal; href: index.php?g=pessoas&gPage=" . DADOS . "}");
		$html .= '<input id="nome" name="nome" type="text" class="form-control input-md" placeholder="Nome/Apelido">&nbsp;<input type="hidden" name="g" value="pessoas"><input type="hidden" name="gPage" value="' . INICIO_PESQUISAR . '"> ';
		$html .= '<button type="submit" class="btn btn-default" style="margin-bottom: 4px"><span class="fal fa-search"></span> Pesquisar</button> ';
		$html .= '</form>';
		$html .= '<br></div>';
		$js = "
		$('#nome').keydown(function(event) {
			if (event.keyCode == 13) {
				this.form.submit();
				return false;
			}
		});
		";
		$o->addJavascript($js);

		$rs = $persistencia->obtemRegistros();
		if (count($rs)>0)
		{
			if ($gParam["PAGINACAO"]["ativo"]==1)
			{
				$html.=$persistencia->pagination->render();
			}
			$html.=$o->tableBegin('big', true, true);
			$mtz = [];
			$mtz[]="<-Opções";
			$mtz[]="<>Situação";
			$mtz[]="<-Apelido";
			$mtz[]="<-Nome";
			$mtz[]="<-Telefone";
			$mtz[]="<-Celular";
			$mtz[]="<-E-mail";
			$html.=$o->tableRow($mtz,'header');
			foreach ($rs as $pessoa) {
				$mtz=[];
				$mtz[]='<-'.$o->button("{icon: folder-open; caption: Abrir; hint: Abrir a ficha da pessoa; size: small; href: ".$o->page."&gPage=".CAPA."&gId=".$pessoa['id']."}");
				$mtz[]='<>'.$pessoa['situacao'];
				$mtz[]='<-'.$pessoa['apelido'];
				$mtz[]='<-'.$pessoa['nome'];
				$mtz[]='<-'.$pessoa['telefone'];
				$mtz[]='<-'.$pessoa['celular'];
				$mtz[]='<-'.$pessoa['email'];
				$html.=$o->tableRow($mtz,'detail');
			}

			$html.=$o->tableEnd();
			if ($gParam["PAGINACAO"]["ativo"]==1) {
				$html.=$persistencia->pagination->render('{id:o; style:margin-top:-1.4%;}');
			}
		} else {
			$html.=$o->msginfo("Nenhuma pessoa cadastrada ainda.");
		}
	break;

	case INICIO_PESQUISAR:
		if ($_REQUEST['nome']) {
			redirect($o->page . '&gPage=' . INICIO_PESQUISAR_RESULTADO . '&pesquisaRapida=' . $_REQUEST['nome']);
		}

		$frm=new gForm('{columns: 2}');
		$frm->addFormMessage("Informe uma ou mais opções abaixo para a busca");
		$frm->add("{name: nome}");
		$frm->add("{name: apelido; fieldLabel: Apelido}");
		$frm->add("{name: telefone}");
		$frm->add("{name: celular}");
		$frm->add("{name: email}");
		$frm->row(
			$frm->add("{name: cliente; type: checkbox;}"),
			$frm->add("{name: fornecedor; type: checkbox;}"),
			$frm->add("{name: funcionario; fieldLabel: Colaborador; type: checkbox;}"),
			$frm->add("{name: motorista; type: checkbox;}")
		);
		$frm->add("{name: gPage; type: hidden; value: ".INICIO_PESQUISAR_RESULTADO."}");
		$html.=$frm->render($o);
	break;

	case INICIO_PESQUISAR_RESULTADO:
		$filtros = [];
		$where = [];
		$pesquisaRapida = gCleanField($_REQUEST['pesquisaRapida']);
		$nome = gCleanField($_REQUEST['nome']);
		$apelido = gCleanField($_REQUEST['apelido']);
		$telefone = gCleanField($_REQUEST['telefone']);
		$celular = gCleanField($_REQUEST['celular']);
		$email = gCleanField($_REQUEST['email']);
		$motorista=isset($_REQUEST["motorista"]);
		$cliente=isset($_REQUEST["cliente"]);
		$funcionario=isset($_REQUEST["funcionario"]);
		$fornecedor=isset($_REQUEST["fornecedor"]);

		$html .= '<div class="hidden-print"><form class="form-inline" method="POST" action="index.php?g=pessoas">';
		$html .= $o->button("{icon: plus; caption: Novo; hint: Cadastrar um novo item; style: info; size: normal; href: index.php?g=pessoas&gPage=" . DADOS . "}");
		$html .= '<input id="nome" name="nome" type="text" class="form-control input-md" placeholder="Nome/Apelido">&nbsp;<input type="hidden" name="g" value="pessoas"><input type="hidden" name="gPage" value="' . INICIO_PESQUISAR . '"> ';
		$html .= '<button type="submit" class="btn btn-default" style="margin-bottom: 4px"><span class="fal fa-search"></span> Pesquisar</button> ';
		$html .= '</form>';
		$html .= '<br></div>';
		$js = "
		$('#nome').keydown(function(event) {
			if (event.keyCode == 13) {
				this.form.submit();
				return false;
			}
		});
		";
		$o->addJavascript($js);

		if ($pesquisaRapida) {
			$where[] = "(nome LIKE '%$pesquisaRapida%' OR apelido LIKE '%$pesquisaRapida%')";
			$filtros[] = "Pesquisar por: {$pesquisaRapida}";

			$html .= $o->msgFilter(implode(" • ", $filtros));
		} else {
			if ($nome) {
				$where[] = "nome LIKE '%{$nome}%'";
				$filtros[] = "nome: {$nome}";
			}

			if ($apelido) {
				$where[] = "apelido LIKE '%{$apelido}%'";
				$filtros[] = "apelido: {$apelido}";
			}

			if ($telefone) {
				$where[] = "telefone LIKE '%{$telefone}%'";
				$filtros[] = "telefone: {$telefone}";
			}

			if ($celular<>'') {
				$where[] = "celular LIKE '%{$celular}%'";
				$filtros[] = "celular: {$celular}";
			}

			if ($email<>'') {
				$where[] = "email LIKE '%{$email}%'";
				$filtros[] = "email: {$email}";
			}

			if ($motorista)
				$where[] = "motorista=1";
				$filtros[] = "motorista: " . gCheck($motorista);
			if ($cliente)
				$where[] = "cliente=1";
				$filtros[] = "cliente: " . gCheck($cliente);
			if ($funcionario)
				$where[] = "funcionario=1";
				$filtros[] = "funcionario: " . gCheck($funcionario);
			if ($fornecedor)
				$where[] = "fornecedor=1";
				$filtros[] = "fornecedor: " . gCheck($fornecedor);

			$html .= $o->msgFilter('Filtros selecionados: ' . implode(" • ", $filtros));
		}

		if (is_array($where)) {
			$where = implode(' AND ', $where);
			$sql = "SELECT p.id, p.apelido,nome,email,telefone,cpf,rg
					FROM pessoas p
					LEFT JOIN pessoas_fisicas f ON p.id=f.id_pessoas
					WHERE p.id > 2 AND tipo = 'F' AND {$where}
					ORDER BY p.nome";
			$rs = dbQuery($sql);
			if ($rs) {
				$row=$rs[0];
				$html.=$o->tableBegin('big', true);
				$mtz = [];
				$mtz[]='<-Opções';
				$mtz[]='<-Apelido';
				$mtz[]='<-Nome';
				$mtz[]='<-Telefone';
				$mtz[]='<-Celular';
				$mtz[]='<-E-mail';
				$html.=$o->tableRow($mtz, 'header');
				foreach ($rs as $row) {
					$salt=gSalt($row['apelido']);
					$mtz = [];
					$mtz[]='<-'.$o->button("{icon: search; caption: Abrir; size: small; href: ".$o->page."&gPage=".CAPA."&gId=".$row['id']."}");
					$mtz[]='<-'.$row['apelido'];
					$mtz[]='<-'.$row['nome'];
					$mtz[]='<-'.$row['telefone'];
					$mtz[]='<-'.$row['celular'];
					$mtz[]='<-'.$row['email'];
					$html.=$o->tableRow($mtz, 'detail');
				}

				$html.=$o->tableEnd();
			} else {
				$html .= $o->msgWarning("Nenhuma pessoa encontrada");
			}
		} else {
			$html.=$o->msgDanger("Você deve especificar ao menos uma opção de filtro");
			$html.=$backButton;
		}
	break;


	case CAPA:
		$cabecalho = mostraCabecalho($gId);
		if ($cabecalho <> '') {
			$html.=$cabecalho;
			$html.='<div class="row">';
			$html.='<div class="col-lg-3 col-md-3 col-sm-4 col-xl-6">';
			if (file_exists($gPathUsrFiles . '/' . $gId . '.jpg')) {
				$html.='<img class="img img-responsive img-thumbnail" src="files/pessoas/' . $gId . '.jpg?'.random_int(1,9999).'">';
			} else {
				$html.='<img class="img img-responsive img-thumbnail" src="files/pessoas/0.jpg">';
			}
			$html .= $o->button("{icon: camera; block: true; caption: Obter foto pela webcam; href: ".$o->page."&gPage=".WEBCAM."&gId=".$gId."&gIdDetalhe=".$_REQUEST['gIdDetalhe']."&tipo=0}");

			$html .= '</div>';

			$html .= '<div class="col-lg-9 col-md-9 col-sm-8 col-xl-6">';
			$html .= $o->msgSubTitle("Pendências");
			$erros = [];

			$sql = "SELECT count(p.id) p, count(f.id) f, count(e.id) e
					FROM pessoas p
					LEFT JOIN pessoas_fisicas f ON p.id=f.id_pessoas
					LEFT JOIN pessoas_enderecos e ON p.id=e.id_pessoas
					WHERE p.id=".$gId;
			$rs = dbQuery($sql);
			if ($rs[0]['f'] == 0) {
				$erros[]="Nenhum documento foi cadastrado";
			}

			if ($rs[0]['e'] == 0) {
				$erros[]="Nenhum endereço foi cadastrado";
			}

			if (is_array($erros)) {
				$msgErro='<ul>';
				foreach ($erros as $erro) {
					$msgErro .= "<li>$erro</li>";
				}
				$msgErro .= '</ul>';
				$html.=$o->msgDanger($msgErro);
			} else {
				$html .= $o->msgInfo("O cadastro está completo. Não há nenhuma pendência.");
			}
			$html.='</div>';

			$html.='</div>';
		} else {
			$html.=$o->msgDanger("A pessoa selecionada não foi encontrada");
			$html.=$backButton;
		}
	break;


	case DADOS:
		if ($gId > 0) {
			$html .= mostraCabecalho($gId);
		}

		$frm = new gForm();
		$persistencia->filtro = '';
		$rs = $persistencia->obtemRegistros("p.id=".$gId);
		$html .= $persistencia->geraCamposDoFormulario($frm, $rs[0], DADOS_SALVAR);
	break;


	case DADOS_SALVAR:

		$msgErro = verificarNomeOuApelidoReservado();
		if ($msgErro) {
			$html .= $o->msgDanger("<b>Erros encontrados: </b><br>" . implode("<br>",$msgErro));
			$html .= $o->backButton;
			break;
		}

		if ($gId == 0) {
			$ok = $persistencia->insere($_REQUEST);
			$gId = $ok;
			userLog('Pessoa adicionada id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		} else {
			$ok = $persistencia->modifica($_REQUEST, $gId);
		}

		if ($ok) {
			redirect($o->page.'&gPage='.DADOS.'&gId='.$gId);
			userLog('Pessoa modificada id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		} else {
			$html.=$o->msgDanger(implode("<br>",$persistencia->erros));
			$html.=$o->backButton;
		}
	break;


	case ENDERECOS:
		$gIdEnd=intval($_REQUEST['gIdEnd']);
		$html.=mostraCabecalho($gId);
		$rs = $persistencia->obtemRegistrosEnderecos($gIdEnd);
		$gIdEnd = $rs[0]['id'];
		$frm = new gForm();
		if ($rs) {
			$frm->addButton("{title: Adicionar outro endereço; style: default; href: ".$o->page."&gPage=".ENDERECOS_NOVO."&gId=".$gId."}");
		}
		$html.=$persistencia->geraCamposDoFormularioEnderecos($frm, $rs[0], ENDERECOS_SALVAR, $gIdEnd);

		// Mostra os endereços que já existem
		$rs = $persistencia->obtemRegistrosEnderecos();
		if ($rs) {
			if ($gIdEnd==0)
			{
				$gIdEnd=$rs[0]['id'];
			}
			$temMaisDeUm=(count($rs)>1);
			if ($temMaisDeUm)
				$o->out($o->modal("{title: Confirme; size: small; content: Excluir este endereço?; okCaption: Excluir agora; name: confirmaExclusaoEnd; url: excluiEnd()}"), gLOC_INLINE, 999);
			$html.=$o->tableBegin('big', true);
			$mtz = [];
			$mtz[]='<-Opções';
			$mtz[]='<-Endereço';
			$mtz[]='<-Bairro';
			$mtz[]='<-Cidade/Estado';
			$mtz[]='<-CEP';
			$html.=$o->tableRow($mtz, 'header');
			foreach ($rs as $row) {
				$mtz = [];
				$btns=$o->button("{icon: pencil; hint: Alterar endereço; caption: Editar; size: small; href: ".$o->page."&gPage=".ENDERECOS."&gId=".$gId."&gIdEnd=".$row['id']."}");
				if ($temMaisDeUm) {
					$btns.=$o->button("{icon: trash; caption: Excluir; style: danger; size: small; openModal: confirmaExclusaoEnd; }", "javascript:gIda='" . $row['id'] . "'");
				}
				$mtz[]='<-'.$btns;

				$mtz[]='<-'.$row['endereco']." ".$row['numero'].($row['complemento']==''?'':'<br>'.$o->small($row['complemento']));
				$mtz[]='<-'.$row['bairro'];
				$mtz[]='<-'.$row['cidade'].'/'.$row['estado'];
				$mtz[]='<-'.$row['cep'];
				if ($row['id'] == $gIdEnd) {
					$html.=$o->tableRow($mtz, 'success');
				} else {
					$html.=$o->tableRow($mtz, 'detail');
				}
				$primeiro=false;
			}
			$html.=$o->tableEnd();
			if ($temMaisDeUm) {
				$o->addJavascript('gIda=0;function excluiEnd(){document.location.href="'.$o->page."&gPage=".ENDERECOS_EXCLUIR."&gId=$gId&gIdEnd=".'"+gIda;}');
			}
		}
		break;

	case ENDERECOS_SALVAR:
		$gIdEnd=intval($_REQUEST['gIdEnd']);
		if ($gIdEnd==0) {
			$ok = $persistencia->insereEndereco($_REQUEST, $gId);
			$gIdEnd = $ok;
			userLog('Pessoa - endereço adicionado - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		} else {
			$ok = $persistencia->modificaEndereco($_REQUEST, $gId, $gIdEnd);
			userLog('Pessoa - endereço modificado - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		}

		if ($ok) {
			redirect($o->page."&gPage=".ENDERECOS."&gId=".$gId."&gIdEnd=".$gIdEnd);
		} else {
			$html.=$o->msgDanger(implode("<br>",$persistencia->erros));
			$html.=$o->backButton;
		}
	break;

	case ENDERECOS_NOVO:
		$gIdEnd=dbInsert('pessoas_enderecos', ['id_pessoas'	=> $gId], true);
		redirect($o->page."&gPage=".ENDERECOS."&gId=".$gId."&gIdEnd=".$gIdEnd);
	break;

	case ENDERECOS_EXCLUIR:
		$sql="DELETE FROM pessoas_enderecos WHERE id=".intval($_REQUEST['gIdEnd']);
		dbQuery($sql);
		userLog('Pessoa - endereço excluído - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		redirect($o->page."&gPage=".ENDERECOS."&gId=".$gId."&gIdEnd=0");
	break;


	case FILIAL:
		$html.=mostraCabecalho($gId);
		$frm = new gForm("{columns: 2}");
		$frm->add("{name: id_filial; fieldLabel: Filial; type: combo; items: ".$sp['combo_filial'].";allowBlank:false}");
		$frm->add("{name: gPage; type: hidden; value: ".FILIAL_SALVAR."}");
		$frm->add("{name: gId; type: hidden; value: ".$gId."}");
		$html.=$frm->render($o);
		$sql = "SELECT
					filial.descricao filialNome,
					pessoas_filial.*
				FROM pessoas_filial
				INNER JOIN filial ON filial.id = pessoas_filial.id_filial
				WHERE pessoas_filial.id_pessoas=$gId
				ORDER BY pessoas_filial.cancelado ASC,filial.descricao";
		$rs = dbQuery($sql);

		if ($rs) {
			$mtz = [];
			$mtz[]="<-Cancelar";
			$mtz[]="<-Filial";
			$mtz[]="<-Informações";
			$html.=$o->tableBegin("big",true);
			$html.=$o->tableRow($mtz,"header");
			foreach ($rs as $row) {
				$dados="Criado por ".gFieldById("pessoas",$row['id_pessoas_criou'],"nome")." - ".gDateTime($row['data_criou']);
				$style = "detail";

				$mtz = [];
				if ($row['cancelado']) {
					$style = "bg-danger";
					$dados.="<BR>Cancelado por ".gFieldById("pessoas",$row['id_pessoas_cancelou'],"nome")." - ".gDateTime($row['data_cancelou']);
					$mtz[]="";
				} else {
					$mtz[]="<-".$o->button("{active: ".$active1."; icon: trash; style: danger; size: small; caption: Cancelar; hint: Cancelar registro; href: ".$o->page."&gPage=".FILIAL_CANCELAR."&gId=".$gId."&gIdDel=".$row['id']."}");;
				}

				$mtz[]="<-".$row['filialNome'];
				$mtz[]="<-".$dados;
				$html.=$o->tableRow($mtz,$style);
			}

			$html.=$o->tableEnd();
		}
		break;

	case FILIAL_SALVAR:
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$sql = "SELECT id FROM pessoas_filial WHERE id_pessoas=$gId AND id_filial=".intval($_REQUEST["id_filial"])." AND cancelado=0 ";
			$rs = dbQuery($sql);
			if ($rs) {
				$html.=$o->msgDanger("Falhas de validação: ".$o->ul(["Não é possível adicionar o mesmo filial"]));
				$html.=$o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: info; size: normal; href: ".$o->page . "&gPage=" . FILIAL . "&gId=" . $gId);
				return;
			} else {
				$mtz=[];
				$mtz['id_filial']      = $_REQUEST['id_filial'];
				$mtz['id_pessoas_criou'] = $usrId;
				$mtz['id_pessoas']       = $gId;
				$mtz['data_criou']       = date("Y-m-d H:i:s");
				dbInsert('pessoas_filial',$mtz);
				userLog('Pessoa - filial adicionado - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
			}
		}
		redirect($o->page."&gPage=".FILIAL."&gId=".$gId);
		break;

	case FILIAL_CANCELAR:
		if((int) $gIdDel > 0){
			$sql="UPDATE pessoas_filial SET data_cancelou='".date("Y-m-d H:i:s")."',id_pessoas_cancelou=$usrId,cancelado=1 WHERE id=$gIdDel";
			dbQuery($sql);
			userLog('Pessoa - filial cancelado - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		}
		redirect($o->page."&gPage=".FILIAL."&gId=".$gId);
	break;


	case OCORRENCIAS:

		$gIdEnd=intval($_REQUEST['gIdEnd']);
		$html.=mostraCabecalho($gId);
		$rs = $persistencia->obtemRegistrosOcorrencias($gIdEnd);
		$frm = new gForm();
		if ($rs) {
			$frm->addButton("{title: Adicionar outra ocorrência; style: default; href: ".$o->page."&gPage=".OCORRENCIAS_NOVA."&gId=".$gId."}");
		}
		$html.=$persistencia->geraCamposDoFormularioOcorrencias($frm, $rs[0], OCORRENCIAS_SALVAR, $gIdEnd);

		// Mostra todas as ocorrências que já existem
		$rs = $persistencia->obtemRegistrosOcorrencias();
		if ($rs) {
			if ($gIdEnd == 0) {
				$gIdEnd=$rs[0]['id'];
			}
			$o->out($o->modal("{title: Confirme; size: small; content: Excluir esta ocorrência?; okCaption: Excluir agora; name: confirmaExclusaoOco; url: excluiOco()}"), gLOC_INLINE, 999);
			$html.=$o->tableBegin('big', true);
			$mtz = [];
			$mtz[]='<-Opções';
			$mtz[]='<-Data digitação';
			$mtz[]='<-Data ocorrência';
			$mtz[]='<-Descrição';
			$mtz[]='<-Tipo';
			$mtz[]='<-Colaborador';
			$mtz[]='<-Pública?';
			$html.=$o->tableRow($mtz, 'header');
			foreach ($rs as $row) {
				$mtz = [];
				$btns=$o->button("{icon: pencil; hint: Alterar ocorrência; caption: Editar; size: small; href: ".$o->page."&gPage=".OCORRENCIAS."&gId=".$gId."&gIdEnd=".$row['id']."}");
				$btns.=$o->button("{icon: trash; caption: Excluir; style: danger; size: small; openModal: confirmaExclusaoOco; }", "javascript:gIda='" . $row['id'] . "'");
				$mtz[]='<-'.$btns;

				$mtz[]='<-'.gDate($row['data_digitacao']);
				$mtz[]='<-'.gDate($row['data_ocorrencia']);
				$mtz[]='<-'.$o->small(nl2br((string) $row['descricao']));
				$mtz[]='<-'.$row['tipo_ocorrencia'];
				$mtz[]='<-'.$row['funcionario'];
				$mtz[]='<-'.gCheck($row['publica']);
				if ($row['id']==$gIdEnd) {
					$html.=$o->tableRow($mtz, 'success');
				} else {
					$html.=$o->tableRow($mtz, 'detail');
				}
				$primeiro=false;
			}
			$html.=$o->tableEnd();
			$o->addJavascript('gIda=0;function excluiOco(){document.location.href="'.$o->page."&gPage=".OCORRENCIAS_CANCELAR."&gId=$gId&gIdEnd=".'"+gIda;}');
		}
		break;

	case OCORRENCIAS_SALVAR:
		$gIdEnd=intval($_REQUEST['gIdEnd']);
		if ($gIdEnd == 0) {
			$ok = $persistencia->insereOcorrencia($_REQUEST, $gId);
			$gIdEnd = $ok;
			userLog('Pessoa - ocorrência adicionada - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		} else {
			$ok = $persistencia->modificaOcorrencia($_REQUEST, $gId, $gIdEnd);
			userLog('Pessoa - ocorrência modificada - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		}

		if ($ok) {
			redirect($o->page."&gPage=".OCORRENCIAS."&gId=".$gId."&gIdEnd=".$gIdEnd);
		} else {
			$html.=$o->msgDanger(implode("<br>",$persistencia->erros));
			$html.=$o->backButton;
		}
		break;

	case OCORRENCIAS_NOVA:
		$flds=['id_pessoas'	=> $gId];
		$gIdEnd=dbInsert('pessoas_ocorrencias', $flds, true);
		redirect($o->page."&gPage=".OCORRENCIAS."&gId=".$gId."&gIdEnd=".$gIdEnd);
		break;

	case OCORRENCIAS_CANCELAR:
		dbQuery("DELETE FROM pessoas_ocorrencias WHERE id_pessoas=$gId AND id=".$gIdEnd);
		userLog('Pessoa - ocorrência removida - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		redirect($o->page."&gPage=".OCORRENCIAS."&gId=".$gId."&gIdEnd=".$gIdEnd);
		break;


	case ANEXOS:
		$html.=mostraCabecalho($gId);

		$rs=$persistencia->obtemRegistrosAnexos($gId);

		$frm = new gForm();
		$html.=$persistencia->geraCamposDoFormularioAnexos($frm,ANEXOS_ADICIONAR);
		if ($rs) {
			$http_usr_files.='anexos/';
			$o->out($o->modal("{title: Confirme; size: small; content: Remover este arquivo?; okCaption: Remover agora; name: confirmaExclusao; url: excluiItem()}"), gLOC_INLINE, 999);
			$html.=$o->tableBegin('big', true);
			$mtz = [];
			$mtz[]='<-Opções';
			$mtz[]='<-Data';
			$mtz[]='<-Descrição';
			$mtz[]='<-Inserido por...';
			$mtz[]='<-Tipo de arquivo';
			$html.=$o->tableRow($mtz, 'header');
			foreach ($rs as $row) {
				$imgName = $row['id'].'.'.substr((string) $row['arquivo'],strpos((string) $row['arquivo'],'/')+1);
				$arquivo = $http_usr_files . $imgName;
				$mtz = [];
				$btns=$o->button("{icon: folder-open; caption: Abrir; style: default; size: small; hint: Abrir este arquivo; target: _newAttach; href: ".$arquivo."}");
				$btns.=$o->button("{icon: trash; caption: Remover; style: danger; size: small; openModal: confirmaExclusao; }", "javascript:gIda='" . $row['id'] . "'");
				$mtz[]='<-'.$btns;
				$mtz[]='<-'.gDateTime($row['data']);
				$mtz[]='<-'.$row['descricao'];
				$mtz[]='<-'.$row['criou'];
				$mtz[]='<-'.$row['arquivo'];
				$html.=$o->tableRow($mtz, 'detail');
			}
			$html.=$o->tableEnd();
			$o->addJavascript('gIda=0;function excluiItem(){document.location.href="'.$o->page."&gPage=".ANEXOS_REMOVER."&gId=$gId&gIda=".'"+gIda;}');
		}
		break;

	case ANEXOS_ADICIONAR:

		if ($persistencia->anexoSalvar()) {
			userLog('Pessoa - anexo adicionado - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
			redirect($o->page."&gPage=".ANEXOS."&gId=".$gId);
		} else {
			$html.=$o->msgDanger(implode("<br>",$persistencia->erros));
			$html.=$o->backButton;
		}

		break;

	case ANEXOS_REMOVER:

		if ($persistencia->anexoRemover($_REQUEST['gIda'])) {
			userLog('Pessoa - anexo removido - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
			redirect($o->page."&gPage=".ANEXOS."&gId=".$gId);
		} else {
			$html.=$o->msgDanger(implode("<br>",$persistencia->erros));
			$html.=$o->backButton;
		}
		break;


	case PERMISSOES:
		$html .= mostraCabecalho($gId);

		$frm = new gForm("{columns: 2; buttonNextCaption: Adicionar}");
		$frm->add("{name: id_gfw_permissions; fieldLabel: Adicionar permissão; type: combo; items: " . $sp['combo_gfw_permissions'] . "}");
		$frm->add("{name: gPage; type: hidden; value: " . PERMISSOES_ADICIONAR . "}");
		$frm->add("{name: gId; type: hidden; value: " . $gId . "}");
		$frm->addButton("{title: Copiar de outro usuário; style: default; href: " . $o->page . "&gId=" . $gId . "&gPage=" . PERMISSOES_COPIAR . "}");
		$frm->addButton("{title: Excluir todas as permissões; style: danger; href: " . $o->page . "&gId=" . $gId . "&gPage=" . PERMISSOES_EXCLUIR_TODAS . "}");
		$html .= $frm->render($o);

		$sql = "SELECT u.id idu, p.name, u.id_gfw_permissions, GROUP_CONCAT(M.title) AS permissoes
				FROM gfw_permissions_users u
				LEFT JOIN gfw_permissions p ON u.id_gfw_permissions = p.id
				JOIN gfw_permissions_links L ON L.id_gfw_permissions  = p.id
				JOIN gfw_menus M ON M.id = L.id_gfw_menus
				WHERE u.id_gfw_users = " . $gId . " ORDER BY name";
		$rs = dbQuery($sql);

		if ($rs) {
			$html .= $o->tableBegin("medium", true);
			$mtz   = "";
			$mtz[] = "<-Opções";
			$mtz[] = "<-Nome";
			$mtz[] = "<-Acessos";
			$html .= $o->tableRow($mtz, "header");
			foreach ($rs as $row) {
				$mtz   = "";
				$mtz[] = "<-" . $o->button("{icon: trash; title: Excluir; hint: Excluir permissão; style: danger; size: small; href: " . $o->page . "&gPage=" . PERMISSOES_EXCLUIR . "&gId=" . $gId . "&gIdu=" . $row['idu'] . "}");;
				$mtz[] = "<-" . $row["name"];
				$mtz[] = "<-" . $row["permissoes"];
				$html .= $o->tableRow($mtz, "detail");
			}
			$html .= $o->tableEnd();
		} else {
			$html .= $o->msgWarning("Nenhuma permissão definida para este usuário");
		}
	break;

	case PERMISSOES_ADICIONAR:
		$id_gfw_permissions=intval($_REQUEST['id_gfw_permissions']);
		$sql="SELECT * FROM gfw_permissions_users WHERE id_gfw_users=".$gId." AND id_gfw_permissions=".$id_gfw_permissions;
		$rs=dbQuery($sql);
		if (!$rs) {
			$sql="INSERT INTO gfw_permissions_users (id_gfw_users, id_gfw_permissions) VALUES (".$gId.",".$id_gfw_permissions.")";
			dbQuery($sql);
			userLog('Pessoa - permissão adicionada - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		}
		redirect($o->page."&gPage=".PERMISSOES."&gId=".$gId);
		break;

	case PERMISSOES_EXCLUIR:
		$sql="DELETE FROM gfw_permissions_users WHERE id=".intval($_REQUEST['gIdu']);
		dbQuery($sql);
		userLog('Pessoa - permissão removida - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		redirect($o->page."&gPage=".PERMISSOES."&gId=".$gId);
		break;

	case PERMISSOES_EXCLUIR_TODAS:
		$sql="DELETE FROM gfw_permissions_users WHERE id_gfw_users=".$gId;
		dbQuery($sql);
		userLog('Pessoa - todas as permissões removidas - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		redirect($o->page."&gPage=".PERMISSOES."&gId=".$gId);
		break;

	case PERMISSOES_COPIAR:
		$html.=mostraCabecalho($gId);
		$frm = new gForm("{columns: 2}");
		$frm->add("{name: id_pessoas; fieldLabel: Copiar permissões do usuário...; type: combo; items: ".$sp['combo_funcionarios']."}");
		$frm->add("{name: gPage; type: hidden; value: ".PERMISSOES_COPIAR_SALVAR."}");
		$frm->add("{name: gId; type: hidden; value: ".$gId."}");
		$html.=$frm->render($o);
		break;

	case PERMISSOES_COPIAR_SALVAR:
		// Primeiro remove as permissões atuais
		dbQuery("DELETE FROM gfw_permissions_users WHERE id_gfw_users=".$gId);
		// Adiciona as permissões com base no outro usuário
		$rs = dbQuery("SELECT * FROM gfw_permissions_users WHERE id_gfw_users=".intval($_REQUEST['id_pessoas']));
		foreach ($rs as $row) {
			dbQuery("INSERT INTO gfw_permissions_users (id_gfw_users,id_gfw_permissions) VALUES ($gId, ".$row['id_gfw_permissions'].")");
		}
		userLog('Pessoa - permissões copiadas - id <a href="pessoas.php?g=itens&gPage='.CAPA.'&gId='.$gId.'">'.$gId.'</a>');
		redirect($o->page."&gPage=".PERMISSOES."&gId=".$gId);
		break;


	case REGISTRO_AVANCAR:
		$sql="SELECT id FROM pessoas p WHERE ".$persistencia->filtro." ORDER BY situacao DESC, nome";
		$rs=dbQuery($sql);
		$max=count($rs);
		$cnt = 0;
		$achou = false;
		$idAnt = $id = $gId;
		while (!$achou) {
			$row = $rs[$cnt];
			if ($rs[$cnt]['id']==$gId) {
				$achou=true;
				$id=$idAnt;
			}
			$idAnt=$rs[$cnt]['id'];
			++$cnt;
			if ($cnt > $max) {
				$achou=true;
			}
		}
		redirect($o->page."&gPage=".$gIdRel."&gId=".$id);
		break;

	case REGISTRO_VOLTAR:
		$sql = "SELECT id FROM pessoas p WHERE ".$persistencia->filtro." ORDER BY situacao DESC, nome";
		$rs = dbQuery($sql);
		$max = count($rs);
		$cnt = 0;
		$achou = false;
		$id = $gId;
		while (!$achou) {
			$row=$rs[$cnt];
			if ($rs[$cnt]['id'] == $gId) {
				$achou=true;
				++$cnt;
				$id=$rs[$cnt]['id'];
				if ($id==0)
					$id=$gId;
			}
			++$cnt;
			if ($cnt>$max) {
				$achou=true;
			}
		}
		redirect($o->page."&gPage=".$gIdRel."&gId=".$id);
		break;


	case WEBCAM:
		$html.=mostraCabecalho($gId);
		$html.=$o->webcam(WEBCAM_SALVAR);

		break;

	case WEBCAM_SALVAR:
		$o->webcamSave($gPathUsrFiles . '/' . $gId . '.jpg');
	break;

}

/**
 * mostraCabecalho
 * @param integer $gId Id do aluno
 * @return string HTML
 */
function mostraCabecalho($gId)
{
	global $o, $salt, $gPage, $AESKEY;

	$sql = "SELECT
				p.*,
				f.*
			FROM pessoas p
			LEFT JOIN pessoas_fisicas f on p.id=f.id_pessoas
			WHERE p.id > 2 AND p.id = " . $gId;
	$rs = dbQuery($sql);
	if ($rs) {
		$row = $rs[0];
		$html.=$o->tableBegin('big', true);
		$mtz = [];
		$mtz[]='<-'. $o->small('Nome').'<br><b>'.$row['nome'].'</b>&nbsp;';
		$mtz[]='<-'. $o->small('Apelido').'<br>'.$row['apelido'].'&nbsp;';
		$mtz[]='<-'. $o->small('Situação').'<br>'.$row['situacao'].'&nbsp;';
		$html.=$o->tableRow($mtz, 'header');

		$mtz = [];
		$mtz[]='<-'. $o->small('Celular').'<br>'.$row['celular'].'&nbsp;';
		$mtz[]='<-'. $o->small('Telefone').'<br>'.$row['telefone'].'&nbsp;';
		$mtz[]='<-'. $o->small('E-mail').'<br>'.$row['email'].'&nbsp;';
		$html.=$o->tableRow($mtz, 'header');

		$mtz = [];
		$mtz[]='~2<-'. $o->small('Observações').'<br>'.nl2br(base64_decode((string) $row['observacoes'])).'&nbsp;';
		$mtz[]='<-'. $o->small('Data do cadastro').'<br>'.gDateTime($row['data_cadastro'])."&nbsp;";
		$html.=$o->tableRow($mtz, 'header');

		$html.=$o->tableEnd();

		$active1=$active2=$active3=$active4=$active5=$active6=$active7=$active8=$active9='false';
		switch ($gPage) {
			case CAPA:
				$active0='true';
				break;
			case DADOS:
				$active1='true';
				break;
			case ENDERECOS:
				$active2='true';
				break;
			case FILIAL:
				$active3='true';
				break;
			case OCORRENCIAS:
				$active4='true';
				break;
			case ANEXOS:
				$active5='true';
				break;
			case PERMISSOES:
				$active6='true';
				break;
		}

		$btns=[];
		$btns[]=$o->button("{active: ".$active0."; icon: home; style: info; hint: Capa da ficha de matrícula; href: ".$o->page."&gPage=".CAPA."&gId=".$gId."}");
		$btns[]=
			'<div class="btn-group" role="group" aria-label="...">'.
				$o->button("{icon: arrow-left; style: info; hint: Registro anterior; href: ".$o->page."&gPage=".REGISTRO_VOLTAR."&gId=".$gId."&gIdRel=".$gPage."}").
				$o->button("{icon: arrow-right; style: info; hint: Próximo registro; href: ".$o->page."&gPage=".REGISTRO_AVANCAR."&gId=".$gId."&gIdRel=".$gPage."}").
			'</div>';
		$btns[]=$o->button("{active: ".$active1."; icon: file-alt; caption: Dados pessoais; hint: Alterar os dados pessoais; href: ".$o->page."&gPage=".DADOS."&gId=".$gId."}");
		$btns[]=$o->button("{active: ".$active2."; icon: map-marker; caption: Endereços; hint: Incluir ou alterar endereços; href: ".$o->page."&gPage=".ENDERECOS."&gId=".$gId."}");
		$btns[]=$o->button("{active: ".$active3."; icon: warehouse; caption: Filial; hint: Relacionar pessoa ao filial; href: ".$o->page."&gPage=".FILIAL."&gId=".$gId."}");
		$btns[]=$o->button("{active: ".$active4."; icon: exclamation-triangle; caption: Ocorrências; hint: Incluir ocorrências; href: ".$o->page."&gPage=".OCORRENCIAS."&gId=".$gId."}");
		$btns[]=$o->button("{active: ".$active5."; icon: paperclip; caption: Anexos; hint: Anexar documentos digitalizados; href: ".$o->page."&gPage=".ANEXOS."&gId=".$gId."}");
		$btns[]=$o->button("{active: ".$active6."; icon: lock; caption: Permissões; hint: Permissõs de acesso; href: ".$o->page."&gPage=".PERMISSOES."&gId=".$gId."}");
		//$html.='<div class="btn-group" role="group" aria-label="...">'.implode(" ",$btns).'</div>';
		$html.='<div>'.implode(" ",$btns).'</div>';

		$html.=$o->hr('soft');
	} else {
		$html='';
	}

	return($html);
}
