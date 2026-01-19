<?php

// Roteamento para seções dentro desta página
define("INICIO", 0);
define("INICIO_PESQUISAR", 1);
define("INICIO_PESQUISAR_RESULTADO", 2);

define("CAPA", 10);
define("DADOS", 20);
define("DADOS_SALVAR", 21);

define("ENDERECOS", 30);
define("ENDERECOS_SALVAR", 31);
define("ENDERECOS_NOVO", 32);
define("ENDERECOS_EXCLUIR", 33);

// define("ARMAZEM",                    40);
// define("ARMAZEM_SALVAR",             41);
// define("ARMAZEM_CANCELAR",           42);

define("OCORRENCIAS", 50);
define("OCORRENCIAS_SALVAR", 51);
define("OCORRENCIAS_NOVA", 52);
define("OCORRENCIAS_CANCELAR", 53);

define("ANEXOS", 60);
define("ANEXOS_ADICIONAR", 61);
define("ANEXOS_REMOVER", 62);

define("PERMISSOES", 70);
define("PERMISSOES_ADICIONAR", 71);
define("PERMISSOES_EXCLUIR", 72);
define("PERMISSOES_EXCLUIR_TODAS", 73);
define("PERMISSOES_COPIAR", 74);
define("PERMISSOES_COPIAR_SALVAR", 75);

define("GERENCIADOR_ACCESS_POINT", 80);
define("SALVAR_DADOS_INTEGRACAO", 81);
define("TESTE_CONEXAO", 82);
define("MOMENTO_INTEGRACAO", 83);
define("SALVAR_MOMENTO_INTEGRACAO", 84);
define("EDITAR_MOMENTO_INTEGRACAO", 85);
define("ATUALIZA_MOMENTO_INTEGRACAO", 86);
define("ATIVAR_DESATIVAR_MOMENTO", 87);
define("MOMENTO_COPIAR", 88);
define("MOMENTO_COPIAR_SALVAR", 89);


define("REGISTRO_AVANCAR", 100);
define("REGISTRO_VOLTAR", 101);

define("WEBCAM", 102);
define("WEBCAM_SALVAR", 103);

define("ARMAZENS", 110);
define("ARMAZENS_SALVAR", 111);
define("ARMAZENS_CANCELAR", 112);

define("CANCELAR", 120);

define("PRIORIDADES", 130);
define("PRIORIDADES_ATIVAR", 131);
define("PRIORIDADES_ORDENAR", 132);

define("CFOPS", 200);
define("CFOPS_SALVAR", 201);
define("CFOPS_EXCLUIR", 202);

define("OPERACAO", 300);
define("SALVAR_ABA_OPERACAO", 301);

define("IMPORTACAO", 400);
define("IMPORTACAO_SALVAR", 401);
define("IMPRIMIR_MODELO", 402);

if (in_array($gPage, $paginasPodeExportar)) {
    $o->PDFEnabled = true;
    $o->DOCEnabled = true;
    $o->XLSEnabled = true;
    $o->CSVEnabled = true;
}

$html .= $o->msgTitle("Cadastro de empresas");

$gPage = intval($gPage);
$gId = intval($gId);
$gPathUsrFiles = $gPath . "files/pessoas/";
$http_usr_files = $http_base . "files/pessoas/";
$agora = date('Y-m-d H:i:s');

$persistencia = new PessoasJuridicas();

switch ($gPage) {

    case INICIO:
        $html .= '<div class="hidden-print"><form class="form-inline" method="POST" action="index.php?g=empresas">';
        $html .= $o->button("{icon: plus; caption: Novo; hint: Cadastrar um novo item; style: info; size: normal; href: index.php?g=empresas&gPage=" . DADOS . "}");
        $html .= '<input id="nome" name="nome" type="text" class="form-control input-md" placeholder="Nome/Apelido">&nbsp;<input type="hidden" name="g" value="empresas"><input type="hidden" name="gPage" value="' . INICIO_PESQUISAR . '"> ';
        $html .= '<button type="submit" class="btn btn-default" style="margin-bottom: 4px"><span class="fal fa-search"></span> Pesquisar</button> ';
        $html .= $o->button("{icon: upload; caption: Importar; hint: Importar empresas via CSV; size: normal; href: index.php?g=empresas&gPage=" . IMPORTACAO . "}");
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
        if (count($rs) > 0) {
            // Javascript
            $js = "function btnAbrirModal(id)
				{
					hideWait();
					$('#id_pessoas').val(id);
					$('#modalCancelarPessoa').modal('show');
				}

				function btnConfirmarCancelar()
				{
					var id=$('#id_pessoas').val();
					var rota='" . $o->page . "&gPage=" . CANCELAR . "&gId='+id;
					location.href=rota;
				}
				";
            $o->addJavascript($js);

            // Modal de cancelamento.
            $conteudo_modal = "Deseja cancelar a empresa ?";
            $conteudo_modal .= "<input type='hidden' name='id_pessoas' id='id_pessoas' />";
            $html .= $o->modal("{title: Confirmação; cancelCaption: Fechar; url:btnConfirmarCancelar(); confirm: true; name: modalCancelarPessoa; size:large; }", $conteudo_modal);

            // Registros.
            if ($gParam["PAGINACAO"]["ativo"] == 1) {
                $html .= $persistencia->pagination->render();
            }
            $html .= $o->tableBegin('big', true, true);
            $mtz = [];
            $mtz[] = "<-Opções";
            $mtz[] = "<>Id";
            $mtz[] = "<>Situação";
            $mtz[] = "<-Apelido";
            $mtz[] = "<-Nome";
            $mtz[] = "<-Telefone";
            $mtz[] = "<-Celular";
            $mtz[] = "<-E-mail";
            $mtz[] = "<>Cliente";
            $mtz[] = "<>Fornecedor";
            $mtz[] = "<>Transport.";
            $html .= $o->tableRow($mtz, 'header');
            foreach ($rs as $pessoa) {
                $btns = [];
                $btns .= $o->button("{icon: folder-open; hint: Abrir a ficha da pessoa; size:tiny; href: " . $o->page . "&gPage=" . CAPA . "&gId=" . $pessoa['id'] . "}");

                $btns .= $o->button("{style:danger; icon:trash; hint:Cancelar pessoa; size:tiny; onClick:btnAbrirModal(" . $pessoa["id"] . ");}");

                $mtz = [];
                $mtz[] = '<-' . $btns;
                $mtz[] = "<>" . $pessoa["id"];
                $mtz[] = '<>' . $pessoa['situacao'];
                $mtz[] = '<-' . $pessoa['apelido'];
                $mtz[] = '<-' . $pessoa['nome'];
                $mtz[] = '<-' . $pessoa['telefone'];
                $mtz[] = '<-' . $pessoa['celular'];
                $mtz[] = '<-' . $pessoa['email'];
                $mtz[] = '<>' . gCheck($pessoa['cliente']);
                $mtz[] = '<>' . gCheck($pessoa['fornecedor']);
                $mtz[] = '<>' . gCheck($pessoa['transportadora']);
                $html .= $o->tableRow($mtz, 'detail');
            }
            $html .= $o->tableEnd();
            if ($gParam["PAGINACAO"]["ativo"] == 1) {
                $html .= $persistencia->pagination->render('{id:o; style:margin-top:-1.4%;;}');
            }
        } else {
            $html .= $o->msginfo("Nenhuma pessoa cadastrada ainda.");
        }
        break;

    case INICIO_PESQUISAR:
        if ($_REQUEST['nome']) {
            redirect($o->page . '&gPage=' . INICIO_PESQUISAR_RESULTADO . '&pesquisaRapida=' . $_REQUEST['nome']);
        }

        $frm = new gForm();
        $frm->addFormMessage("Informe uma ou mais opções abaixo para a busca");
        $frm->add("{name: nome}");
        $frm->add("{name: apelido; fieldLabel: Apelido}");
        $frm->add("{name: telefone}");
        $frm->add("{name: celular}");
        $frm->add("{name: email}");
        $frm->add("{name: gPage; type: hidden; value: " . INICIO_PESQUISAR_RESULTADO . "}");
        $html .= $frm->render($o);
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

        $html .= '<div class="hidden-print"><form class="form-inline" method="POST" action="index.php?g=empresas">';
        $html .= $o->button("{icon: plus; caption: Novo; hint: Cadastrar um novo item; style: info; size: normal; href: index.php?g=empresas&gPage=" . DADOS . "}");
        $html .= '<input id="nome" name="nome" type="text" class="form-control input-md" placeholder="Nome/Apelido">&nbsp;<input type="hidden" name="g" value="empresas"><input type="hidden" name="gPage" value="' . INICIO_PESQUISAR . '"> ';
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
            $where[] = "(nome LIKE '%{$pesquisaRapida}%' OR apelido LIKE '%{$pesquisaRapida}%')";
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

            if ($celular <> '') {
                $where[] = "celular LIKE '%{$celular}%'";
                $filtros[] = "celular: {$celular}";
            }

            if ($email <> '') {
                $where[] = "email LIKE '%{$email}%'";
                $filtros[] = "email: {$email}";
            }

            $html .= $o->msgFilter('Filtros selecionados: ' . implode(" • ", $filtros));
        }

        if (!is_array($where)) {
            $html .= $o->msgDanger("Você deve especificar ao menos uma opção de filtro");
            $html .= $backButton;
            break;
        }

        $where = implode(' AND ', $where);

        $sql = "
			SELECT p.id, p.apelido,nome,razao_social, cnpj, email,telefone, codigo_sistema_externo
			FROM pessoas p
			LEFT JOIN pessoas_juridicas f ON p.id = f.id_pessoas
			WHERE p.id > 2
				AND tipo = 'J'
				AND {$where}
			ORDER BY p.nome";
        $rs = dbQuery($sql);

        if (!$rs[0]['id']) {
            $html .= $o->msgWarning("Nenhuma empresa encontrada");
            break;
        }

        $row = $rs[0];
        $html .= $o->tableBegin('big', true);
        $mtz = [];
        $mtz[] = '<-Opções';
        $mtz[] = '<-Apelido';
        $mtz[] = '<-Nome';
        $mtz[] = '<-Razão social';
        $mtz[] = '<-CNPJ';
        $mtz[] = '<-E-mail';
        $mtz[] = '<-Telefone';
        if ($gParam['INTEGRACAO_GMI']['ativo']) {
            $mtz[] = '<-Código externo';
        }

        $html .= $o->tableRow($mtz, 'header');

        foreach ($rs as $row) {
            $mtz = [];
            $mtz[] = '<-' . $o->button("{icon: search; caption: Abrir; size: small; href: " . $o->page . "&gPage=" . CAPA . "&gId=" . $row['id'] . "}");
            $mtz[] = '<-' . $row['apelido'];
            $mtz[] = '<-' . $row['nome'];
            $mtz[] = '<-' . $row['razao_social'];
            $mtz[] = '<-' . $row['cnpj'];
            $mtz[] = '<-' . $row['email'];
            $mtz[] = '<-' . $row['telefone'];
            if ($gParam['INTEGRACAO_GMI']['ativo']) {
                $mtz[] = '<-' . $row['codigo_sistema_externo'];
            }
            $html .= $o->tableRow($mtz, 'detail');
        }

        $html .= $o->tableEnd();
        break;


    case CAPA:
        $cabecalho = mostraCabecalho($gId);
        if ($cabecalho <> '') {
            $html .= $cabecalho;
            $html .= '<div class="row">';
            $html .= '<div class="col-lg-3 col-md-3 col-sm-4 col-xl-6">';
            if (file_exists($gPathUsrFiles . '/' . $gId . '.jpg')) {
                $html .= '<img class="img img-responsive img-thumbnail" src="files/pessoas/' . $gId . '.jpg?' . random_int(1, 9999) . '">';
            } else {
                $html .= '<img class="img img-responsive img-thumbnail" src="files/pessoas/0.jpg">';
            }
            $html .= $o->button("{icon: camera; block: true; caption: Obter foto pela webcam; href: " . $o->page . "&gPage=" . WEBCAM . "&gId=" . $gId . "&gIdDetalhe=" . $_REQUEST['gIdDetalhe'] . "&tipo=0}");

            $html .= '</div>';

            $html .= '<div class="col-lg-9 col-md-9 col-sm-8 col-xl-6">';
            $html .= $o->msgSubTitle("Pendências");
            $erros = "";

            $sql = "SELECT
                        count(p.id) p,
                        count(f.id) f,
                        count(e.id) e
					FROM pessoas p
					LEFT JOIN pessoas_fisicas f ON p.id=f.id_pessoas
					LEFT JOIN pessoas_enderecos e ON p.id=e.id_pessoas
					WHERE p.id = " . $gId;
            $rs = dbQuery($sql);

            if ($rs[0]['f'] == 0) {
                $erros[] = "Nenhum documento foi cadastrado";
            }

            if ($rs[0]['e'] == 0) {
                $erros[] = "Nenhum endereço foi cadastrado";
            }

            if (is_array($erros)) {
                $msgErro = '<ul>';
                foreach ($erros as $erro) {
                    $msgErro .= "<li>$erro</li>";
                }
                $msgErro .= '</ul>';
                $html .= $o->msgDanger($msgErro);
            } else {
                $html .= $o->msgInfo("O cadastro está completo. Não há nenhuma pendência.");
            }

            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= $o->msgDanger("A pessoa selecionada não foi encontrada");
            $html .= $backButton;
        }
        break;


    case DADOS:
        if ($gId > 0) {
            $html .= mostraCabecalho($gId);
        }

        $rs = $persistencia->obtemRegistros("p.id=" . $gId)[0];
        $frm = new gForm();
        $html .= $persistencia->geraCamposDoFormulario($frm, $rs, DADOS_SALVAR);
        break;

    case DADOS_SALVAR:

        $msgErro = verificarNomeOuApelidoReservado();
        if ($msgErro) {
            $html .= $o->msgDanger("<b>Erros encontrados: </b><br>" . implode("<br>",$msgErro));
            $html .= $o->backButton;
            break;
        }

        $nomeAntigo = $persistencia->obtemRegistros("p.id=" . $gId)[0]['nome'];
        if ($gId == 0) {
            $ok = $persistencia->insere($_REQUEST);
            $gId = $ok;
            userLog('Empresa adicionada - id <a href="pessoas.php?g=itens&gPage=' . CAPA . '&gId=' . $gId . '">' . $gId . '</a>');
        } else {
            $ok = $persistencia->modifica($_REQUEST, $gId);
        }

        if ($ok) {
            if (($nomeAntigo != $_REQUEST['nome']) && $_REQUEST['nome'] != '') {
                $dadosGatilho = [
                    'empresa' => [
                        'nome' => $_REQUEST['nome'],
                        'apelido' => $_REQUEST['apelido']
                    ],
                    'idPessoasProprietario' => 1
                ];

                dispararGatilho('sincronizarProprietario', $dadosGatilho);
            }
            $persistencia->atualizarPessoasSituacao($gId);

            redirect($o->page . '&gPage=' . DADOS . '&gId=' . $gId);
        } else {
            $html .= $o->msgDanger(implode("<br>", $persistencia->erros));
            $html .= $o->backButton;
        }
        break;


    case ENDERECOS:
        $gIdEnd = intval($_REQUEST['gIdEnd']);
        $html .= mostraCabecalho($gId);
        $rs = $persistencia->obtemRegistrosEnderecos($gIdEnd);
        $gIdEnd = $rs[0]['id'];
        $frm = new gForm();
        if ($rs) {
            $frm->addButton("{title: Adicionar outro endereço; style: default; href: " . $o->page . "&gPage=" . ENDERECOS_NOVO . "&gId=" . $gId . "}");
        }

        $html .= $persistencia->geraCamposDoFormularioEnderecos($frm, $rs[0], ENDERECOS_SALVAR, $gIdEnd);

        // Mostra os endereços que já existem
        $rs = $persistencia->obtemRegistrosEnderecos();
        if ($rs) {
            if ($gIdEnd == 0) {
                $gIdEnd = $rs[0]['id'];
            }

            $temMaisDeUm = (count($rs) > 1);
            if ($temMaisDeUm) {
                $o->out($o->modal("{title: Confirme; size: small; content: Excluir este endereço?; okCaption: Excluir agora; name: confirmaExclusaoEnd; url: excluiEnd()}"), gLOC_INLINE, 999);
            }
            $html .= $o->tableBegin('big', true);
            $mtz = [];
            $mtz[] = '<-Opções';
            $mtz[] = '<-Endereço';
            $mtz[] = '<-Bairro';
            $mtz[] = '<-Cidade/Estado';
            $mtz[] = '<-CEP';
            $html .= $o->tableRow($mtz, 'header');
            foreach ($rs as $row) {
                $mtz = [];
                $btns = $o->button("{icon: pencil; hint: Alterar endereço; caption: Editar; size: small; href: " . $o->page . "&gPage=" . ENDERECOS . "&gId=" . $gId . "&gIdEnd=" . $row['id'] . "}");
                if ($temMaisDeUm) {
                    $btns .= $o->button("{icon: trash; caption: Excluir; style: danger; size: small; openModal: confirmaExclusaoEnd; }", "javascript:gIda='" . $row['id'] . "'");
                }

                $mtz[] = '<-' . $btns;

                $mtz[] = '<-' . $row['endereco'] . " " . $row['numero'] . ($row['complemento'] == '' ? '' : '<br>' . $o->small($row['complemento']));
                $mtz[] = '<-' . $row['bairro'];
                $mtz[] = '<-' . $row['cidade'] . '/' . $row['estado'];
                $mtz[] = '<-' . $row['cep'];
                if ($row['id'] == $gIdEnd) {
                    $html .= $o->tableRow($mtz, 'success');
                } else {
                    $html .= $o->tableRow($mtz, 'detail');
                }

                $primeiro = false;
            }

            $html .= $o->tableEnd();
            if ($temMaisDeUm) {
                $o->addJavascript('gIda=0;function excluiEnd(){document.location.href="' . $o->page . "&gPage=" . ENDERECOS_EXCLUIR . "&gId=$gId&gIdEnd=" . '"+gIda;}');
            }
        }
        break;


    case ENDERECOS_SALVAR:
        $gIdEnd = intval($_REQUEST['gIdEnd']);
        if ($gIdEnd == 0) {
            $ok = $persistencia->insereEndereco($_REQUEST, $gId);
            $gIdEnd = $ok;
        } else {
            $ok = $persistencia->modificaEndereco($_REQUEST, $gId, $gIdEnd);
        }

        if ($ok) {
            $persistencia->atualizarPessoasSituacao($gId);

            redirect($o->page . "&gPage=" . ENDERECOS . "&gId=" . $gId . "&gIdEnd=" . $gIdEnd);
        } else {
            $html .= $o->msgDanger(implode("<br>", $persistencia->erros));
            $html .= $o->backButton;
        }

        break;


    case ENDERECOS_NOVO:
        $gIdEnd = dbInsert('pessoas_enderecos', ['id_pessoas'    => $gId], true);
        redirect($o->page . "&gPage=" . ENDERECOS . "&gId=" . $gId . "&gIdEnd=" . $gIdEnd);
        break;


    case ENDERECOS_EXCLUIR:
        $sql = "DELETE FROM pessoas_enderecos WHERE id=" . intval($_REQUEST['gIdEnd']);
        dbQuery($sql);
        redirect($o->page . "&gPage=" . ENDERECOS . "&gId=" . $gId . "&gIdEnd=0");
        break;


    case OCORRENCIAS:
        $gIdEnd = intval($_REQUEST['gIdEnd']);
        $html .= mostraCabecalho($gId);
        $rs = $persistencia->obtemRegistrosOcorrencias($gIdEnd);
        $frm = new gForm();
        if ($rs) {
            $frm->addButton("{title: Adicionar outra ocorrência; style: default; href: " . $o->page . "&gPage=" . OCORRENCIAS_NOVA . "&gId=" . $gId . "}");
        }
        $html .= $persistencia->geraCamposDoFormularioOcorrencias($frm, $rs[0], OCORRENCIAS_SALVAR, $gIdEnd);

        // Mostra todas as ocorrências que já existem
        $rs = $persistencia->obtemRegistrosOcorrencias();
        if ($rs) {
            if ($gIdEnd == 0) {
                $gIdEnd = $rs[0]['id'];
            }
            $o->out($o->modal("{title: Confirme; size: small; content: Excluir esta ocorrência?; okCaption: Excluir agora; name: confirmaExclusaoOco; url: excluiOco()}"), gLOC_INLINE, 999);
            $html .= $o->tableBegin('big', true);
            $mtz = [];
            $mtz[] = '<-Opções';
            $mtz[] = '<-Data digitação';
            $mtz[] = '<-Data ocorrência';
            $mtz[] = '<-Descrição';
            $mtz[] = '<-Tipo';
            $mtz[] = '<-Colaborador';
            $mtz[] = '<-Pública?';
            $html .= $o->tableRow($mtz, 'header');
            foreach ($rs as $row) {
                $mtz = [];
                $btns = $o->button("{icon: pencil; hint: Alterar ocorrência; caption: Editar; size: small; href: " . $o->page . "&gPage=" . OCORRENCIAS . "&gId=" . $gId . "&gIdEnd=" . $row['id'] . "}");
                $btns .= $o->button("{icon: trash; caption: Excluir; style: danger; size: small; openModal: confirmaExclusaoOco; }", "javascript:gIda='" . $row['id'] . "'");
                $mtz[] = '<-' . $btns;

                $mtz[] = '<-' . gDate($row['data_digitacao']);
                $mtz[] = '<-' . gDate($row['data_ocorrencia']);
                $mtz[] = '<-' . $o->small(nl2br((string) $row['descricao']));
                $mtz[] = '<-' . $row['tipo_ocorrencia'];
                $mtz[] = '<-' . $row['funcionario'];
                $mtz[] = '<-' . gCheck($row['publica']);
                if ($row['id'] == $gIdEnd) {
                    $html .= $o->tableRow($mtz, 'success');
                } else {
                    $html .= $o->tableRow($mtz, 'detail');
                }

                $primeiro = false;
            }

            $html .= $o->tableEnd();
            $o->addJavascript('gIda=0;function excluiOco(){document.location.href="' . $o->page . "&gPage=" . OCORRENCIAS_CANCELAR . "&gId=$gId&gIdEnd=" . '"+gIda;}');
        }

        break;


    case OCORRENCIAS_SALVAR:
        $gIdEnd = intval($_REQUEST['gIdEnd']);
        $sql = "SELECT p.apelido, e.* FROM pessoas p LEFT JOIN pessoas_ocorrencias e on p.id=e.id_pessoas WHERE p.id=" . $gId;
        if ($gIdEnd > 0) {
            $sql .= " AND e.id=" . $gIdEnd;
        }

        $rs = dbQuery($sql);
        $row = $rs[0];
        $apelido = $row['apelido'];

        $flds = [
            'id_pessoas'             => $gId,
            'id_pessoas_funcionario' => intval($_REQUEST['id_pessoas_funcionario']),
            'id_tipos_ocorrencias'   => intval($_REQUEST['id_tipos_ocorrencias']),
            'descricao'              => gCleanField($_REQUEST['descricao']),
            'data_digitacao'         => date("Y-m-d H:i:s"),
            'data_ocorrencia'        => gDBDate($_REQUEST['data_ocorrencia']),
            'publica'                => gDBCheck($_REQUEST['publica'])
        ];

        if ($row['id'] > 0) {
            dbUpdate('pessoas_ocorrencias', $flds, $row['id']);
        } else {
            dbInsert('pessoas_ocorrencias', $flds);
        }

        $persistencia->atualizarPessoasSituacao($gId);

        redirect($o->page . "&gPage=" . OCORRENCIAS . "&gId=" . $gId . "&gIdEnd=" . $gIdEnd);
        break;


    case OCORRENCIAS_NOVA:
        $flds = ['id_pessoas' => $gId];
        $gIdEnd = dbInsert('pessoas_ocorrencias', $flds, true);
        redirect($o->page . "&gPage=" . OCORRENCIAS . "&gId=" . $gId . "&gIdEnd=" . $gIdEnd);
        break;


    case OCORRENCIAS_CANCELAR:
        dbQuery("DELETE FROM pessoas_ocorrencias WHERE id_pessoas=$gId AND id=" . $gIdEnd);
        redirect($o->page . "&gPage=" . OCORRENCIAS . "&gId=" . $gId . "&gIdEnd=" . $gIdEnd);
        break;


    case ANEXOS:
        $html .= mostraCabecalho($gId);

        $rs = $persistencia->obtemRegistrosAnexos($gId);

        $frm = new gForm();
        $html .= $persistencia->geraCamposDoFormularioAnexos($frm, ANEXOS_ADICIONAR);
        if ($rs) {
            $http_usr_files .= 'anexos/';
            $o->out($o->modal("{title: Confirme; size: small; content: Remover este arquivo?; okCaption: Remover agora; name: confirmaExclusao; url: excluiItem()}"), gLOC_INLINE, 999);
            $html .= $o->tableBegin('big', true);
            $mtz = [];
            $mtz[] = '<-Opções';
            $mtz[] = '<-Data';
            $mtz[] = '<-Descrição';
            $mtz[] = '<-Inserido por...';
            $mtz[] = '<-Tipo de arquivo';
            $html .= $o->tableRow($mtz, 'header');
            foreach ($rs as $row) {
                $imgName = $row['id'] . '.' . substr((string) $row['arquivo'], strpos((string) $row['arquivo'], '/') + 1);
                $arquivo = $http_usr_files . $imgName;
                $mtz = [];
                $btns = $o->button("{icon: folder-open; caption: Abrir; style: default; size: small; hint: Abrir este arquivo; target: _newAttach; href: " . $arquivo . "}");
                $btns .= $o->button("{icon: trash; caption: Remover; style: danger; size: small; openModal: confirmaExclusao; }", "javascript:gIda='" . $row['id'] . "'");
                $mtz[] = '<-' . $btns;
                $mtz[] = '<-' . gDateTime($row['data']);
                $mtz[] = '<-' . $row['descricao'];
                $mtz[] = '<-' . $row['criou'];
                $mtz[] = '<-' . $row['arquivo'];
                $html .= $o->tableRow($mtz, 'detail');
            }
            $html .= $o->tableEnd();
            $o->addJavascript('gIda=0;function excluiItem(){document.location.href="' . $o->page . "&gPage=" . ANEXOS_REMOVER . "&gId=$gId&gIda=" . '"+gIda;}');
        }

        break;


    case ANEXOS_ADICIONAR:
        if ($persistencia->anexoSalvar()) {

            $persistencia->atualizarPessoasSituacao($gId);

            redirect($o->page . "&gPage=" . ANEXOS . "&gId=" . $gId);
        } else {
            $html .= $o->msgDanger(implode("<br>", $persistencia->erros));
            $html .= $o->backButton;
        }

        break;


    case ANEXOS_REMOVER:
        if ($persistencia->anexoRemover($_REQUEST['gIda'])) {
            redirect($o->page . "&gPage=" . ANEXOS . "&gId=" . $gId);
        } else {
            $html .= $o->msgDanger(implode("<br>", $persistencia->erros));
            $html .= $o->backButton;
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

        $sql = "SELECT
                    p.*,
                    u.id idu
                FROM gfw_permissions_users u
                LEFT JOIN gfw_permissions p ON u.id_gfw_permissions = p.id
                WHERE u.id_gfw_users = " . $gId . " ORDER BY name";
        $rs = dbQuery($sql);
        if (count($rs) > 0) {
            $html .= $o->tableBegin("medium", true);
            $mtz = [];
            $mtz[] = "<-Opções";
            $mtz[] = "<-Nome";
            $html .= $o->tableRow($mtz, "header");
            foreach ($rs as $row) {
                $mtz = [];
                $mtz[] = "<-" . $o->button("{icon: trash; title: Excluir; hint: Excluir permissão; style: danger; size: small; href: " . $o->page . "&gPage=" . PERMISSOES_EXCLUIR . "&gId=" . $gId . "&gIdu=" . $row['idu'] . "}");
                $mtz[] = "<-" . $row["name"];
                $html .= $o->tableRow($mtz, "detail");
            }
            $html .= $o->tableEnd();
        } else {
            $html .= $o->msgWarning("Nenhuma permissão definida para este usuário");
        }

        break;

    case PERMISSOES_ADICIONAR:
        $id_gfw_permissions = intval($_REQUEST['id_gfw_permissions']);
        $sql = "SELECT * FROM gfw_permissions_users WHERE id_gfw_users=" . $gId . " AND id_gfw_permissions=" . $id_gfw_permissions;
        $rs = dbQuery($sql);
        if (!$rs) {
            $sql = "INSERT INTO gfw_permissions_users (id_gfw_users, id_gfw_permissions) VALUES (" . $gId . "," . $id_gfw_permissions . ")";
            dbQuery($sql);
        }
        redirect($o->page . "&gPage=" . PERMISSOES . "&gId=" . $gId);
        break;


    case PERMISSOES_EXCLUIR:
        $sql = "DELETE FROM gfw_permissions_users WHERE id=" . intval($_REQUEST['gIdu']);
        dbQuery($sql);
        redirect($o->page . "&gPage=" . PERMISSOES . "&gId=" . $gId);
        break;


    case PERMISSOES_EXCLUIR_TODAS:
        $sql = "DELETE FROM gfw_permissions_users WHERE id_gfw_users=" . $gId;
        dbQuery($sql);
        redirect($o->page . "&gPage=" . PERMISSOES . "&gId=" . $gId);
        break;


    case PERMISSOES_COPIAR:
        $html .= mostraCabecalho($gId);
        $frm = new gForm("{columns: 2}");
        $frm->add("{name: id_pessoas; fieldLabel: Copiar permissões da empresa; type: combo; items: " . $sp['combo_empresas'] . "}");
        $frm->add("{name: gPage; type: hidden; value: " . PERMISSOES_COPIAR_SALVAR . "}");
        $frm->add("{name: gId; type: hidden; value: " . $gId . "}");
        $html .= $frm->render($o);
        break;


    case PERMISSOES_COPIAR_SALVAR:
        // Primeiro remove as permissões atuais
        dbQuery("DELETE FROM gfw_permissions_users WHERE id_gfw_users=" . $gId);
        // Adiciona as permissões com base no outro usuário
        $rs = dbQuery("SELECT * FROM gfw_permissions_users WHERE id_gfw_users=" . intval($_REQUEST['id_pessoas']));
        foreach ($rs as $row) {
            dbQuery("INSERT INTO gfw_permissions_users (id_gfw_users,id_gfw_permissions) VALUES ($gId, " . $row['id_gfw_permissions'] . ")");
        }

        redirect($o->page . "&gPage=" . PERMISSOES . "&gId=" . $gId);
        break;


	case GERENCIADOR_ACCESS_POINT:

        $classeIntegracao = $_REQUEST["classe_integracao"] ?: 0;

		$idPessoasProprietario = $gId;

        $sql = "SELECT DISTINCT classe_integracao FROM gatilhos_configuracoes ORDER BY classe_integracao ASC";
		$listaClasses = dbQuery($sql);

        if ($listaClasses) {
			$listaClasses = array_column($listaClasses, 'classe_integracao');
			$classesAssociativas = array_combine($listaClasses, $listaClasses);
		}

        if (
            str_starts_with((string) $EMPRESA, 'logic')
            && $classeIntegracao == 'omie'
        ) {
            $idPessoasProprietario = 1;
        }

        if ($classeIntegracao) {
            $sql = "SELECT
                        porta_rede,
                        usuario,
                        url_base,
                        id_pessoas_proprietario,
                        quantidade_tentativas,
                        tempo_limite
                    FROM gatilhos_configuracoes
                    WHERE id_pessoas_proprietario = '{$idPessoasProprietario}'
                        AND classe_integracao = '{$classeIntegracao}'
                    LIMIT 1";
            $rs = dbQuery($sql)[0];
        }

		$html .= mostraCabecalho($gId);

		$frm = new gForm();
		$frm->add("{name: url_base; fieldLabel: Endereço URL; type: text; allowBlank: false; value: ".$rs['url_base']."}");
		$frm->add("{name: porta_rede; fieldLabel: Porta de rede; type: text; allowBlank: false; value: ".$rs['porta_rede']."}");
		$frm->add("{name: classe_integracao; fieldLabel: Classe para integração; allowBlank: true; type: combo; value: ".$classeIntegracao."}", $classesAssociativas);
		$frm->add("{name: usuario; fieldLabel: Usuário; type: text; allowBlank: false; value: ".$rs['usuario']."}");
		$frm->add("{name: senha; fieldLabel: Senha; type: password; allowBlank: false; value:" . SENHA_NAO_MODIFICADA . ";}");
		$frm->add("{name: confirmar_senha; fieldLabel: Confirmar senha; type: password; allowBlank: false; value:" . SENHA_NAO_MODIFICADA . ";}");
		$frm->add("{name: quantidade_tentativas; fieldLabel: Quant. máxima de tentativas de reenvio; type: text; allowBlank: false; value: ".$rs['quantidade_tentativas']."}");
        $frm->add("{name: tempo_limite; fieldLabel: Tempo limite de requisição (em segundos); type: number; allowBlank: false; value:" . $rs['tempo_limite'] . "}");

		$frm->add("{name: idPessoasProprietario; type: hidden; value: ".$idPessoasProprietario."}");
        $frm->addButton("{title: Testar conexão; style: info;}", "javascript:testarConexao()");
		$frm->add("{name: gPage; type: hidden; value: ".SALVAR_DADOS_INTEGRACAO."}");


		if ((boolean)$_SESSION["testeConexao"]) {
			$html .= $o->msgSuccess("Teste de conexão bem sucedido");
			unset($_SESSION["testeConexao"]);
		}

		if ((boolean)$_SESSION["dadosCadastrados"]) {
			$html .= $o->msgSuccess("Dados cadastrados com sucesso");
			unset($_SESSION["dadosCadastrados"]);
		}

		if ((boolean)$_SESSION["dadosAtualizados"]) {
			$html .= $o->msgSuccess("Dados atualizados com sucesso");
			unset($_SESSION["dadosAtualizados"]);
		}

		$sql = "SELECT
					G.id,
		 			G.ativo,
		 			G.http_verbo,
		 			G.descricao,
		 			G.metodo,
		 			G.url_rota,
                    G.passiva
		 		FROM gatilhos G
		 		LEFT JOIN gatilhos_configuracoes GC
					ON G.id_gatilhos_configuracoes = GC.id
		 		WHERE GC.id_pessoas_proprietario = '{$idPessoasProprietario}'
					AND GC.classe_integracao = '{$classeIntegracao}'
                ORDER BY G.ativo DESC";
		$rs = dbQuery($sql);

        $html .= $frm->render($o);
        $html .= $o->tableBegin('big', true);

		if (!$rs) {
			$html .= $o->msgWarning("Nenhum momento cadastrado");
		} else {
			$mtz = [];
			$mtz[] = "<>Opções";
			$mtz[] = "<-HTTP";
			$mtz[] = "<-Descrição";
			$mtz[] = "<-Método";
			$mtz[] = "<-URL da rota";
			$mtz[] = "<>Passiva";
			$html .= $o->tableRow($mtz, 'header');

			foreach ($rs as $value) {
				$btn = $o->button("{icon: pencil; caption:; hint: Editar momento; style: default; size: small;"
				. "href: ". $o->page."&gPage=".MOMENTO_INTEGRACAO."&gId=".$gId."&idPessoasProprietario=".$value['id_pessoas_proprietario']."&idMomento=".$value['id']."&classe_integracao=".$classeIntegracao."}");

                if ($value['ativo']) {
                    $btn .= $o->button("{icon: thumbs-up; caption:; hint: Desativar; style: success; size: small;"
				. "}", "javascript:ativarDesativarMomento(" . $value['id'] . ")");
                } else {
                    $btn .= $o->button("{icon: thumbs-down; caption:; hint: Ativar; style: danger; size: small;"
				. "}", "javascript:ativarDesativarMomento(" . $value['id'] . ")");
                }

				$mtz = [];
				$mtz[] = '<>'.$btn;
				$mtz[] = "<-".$value["http_verbo"];
				$mtz[] = "<-".$value['descricao'];
				$mtz[] = "<-".$value['metodo'];
				$mtz[] = "<-".$value['url_rota'];
				$mtz[] = "<>".gCheck($value['passiva']);
				$html .= $o->tableRow($mtz, 'detail');
			}
			$html .= $o->tableEnd();
		}

        $js = "
            function rechargPage(select) {
                let selected = select.value;
                let urlRecharg = '{$o->page}&gPage=".GERENCIADOR_ACCESS_POINT."&gId={$gId}&classe_integracao='+selected;
                window.location.href = urlRecharg;
            }

            window.onload = function() {
                var select = document.querySelector('select[" . 'name="classe_integracao"' . "]');
                select.onchange = function() {
                    rechargPage(this);
                };
            }

            function ativarDesativarMomento(id) {
                let url = '{$o->page}&gId={$gId}&classe_integracao={$classeIntegracao}&gPage=" . ATIVAR_DESATIVAR_MOMENTO . "&idMomento=' + encodeURIComponent(id);
                window.location.href = url;
            }

            function testarConexao() {
                let urlBase = document.querySelector('[name=\"url_base\"]').value;
                let classeIntegracao = document.querySelector('[name=\"classe_integracao\"]').value;
                let idPessoasProprietario = document.querySelector('[name=\"idPessoasProprietario\"]').value;

                let url = '{$o->page}&gId={$gId}&gPage=" . TESTE_CONEXAO .
                    "&url_base=' + encodeURIComponent(urlBase) +
                    '&classe_integracao=' + encodeURIComponent(classeIntegracao) +
                    '&idPessoasProprietario=' + encodeURIComponent(idPessoasProprietario);

                window.location.href = url;
            }
        ";
        $o->addJavascript($js);

		$frm = new gForm("{buttonNextCaption: Adicionar momento;}");
        $frm->addButton("{title: Copiar de outro usuário; style: default; href: " . $o->page . "&gId=" . $gId . "&gPage=" . MOMENTO_COPIAR . "&classe_integracao=" . $classeIntegracao . "}");
		$frm->add("name: classe_integracao; fieldLabel: Classe de integração; type: hidden; value: {$classeIntegracao}");
		$frm->add("{name: gPage; type: hidden; value: ".MOMENTO_INTEGRACAO."}");
		$html .= $frm->render($o);
		break;


	case SALVAR_DADOS_INTEGRACAO:
		if ($_REQUEST['senha'] != $_REQUEST['confirmar_senha']) {
			$html .= $o->msgDanger("Senhas não conferem");
			$html .= $backButton;
			break;
		}

        if (!$_POST['classe_integracao']) {
            $html .= $o->msgDanger("Selecione uma classe de integração");
            $html .= $backButton;
            break;
        }

		$sql = "
            SELECT id
            FROM gatilhos_configuracoes
            WHERE id_pessoas_proprietario = '{$_REQUEST['idPessoasProprietario']}'
                AND classe_integracao = '{$_REQUEST['classe_integracao']}'";
		$idGatilhosConfiguracoes = dbFastQuery($sql)[0]["id"];

		// verificar quais campos vai precisar adicionar gcleanfield
		if (!$idGatilhosConfiguracoes) {
            if ($_REQUEST['senha'] <> SENHA_NAO_MODIFICADA) {
                $campoSenha = " HEX(AES_ENCRYPT('" . gCleanField($_REQUEST['senha']) . "', '{$AESKEY}')), ";
            }

			$sql = "INSERT INTO gatilhos_configuracoes (
						usuario,
						id_pessoas_proprietario,
						url_base,
						classe_integracao,
						porta_rede,
						senha,
                        quantidade_tentativas,
                        tempo_limite
                    ) VALUES (
						'" . gCleanField($_REQUEST['usuario']) . "',
						'" . gCleanField($_REQUEST['idPessoasProprietario']) . "',
						'" . gCleanField($_REQUEST['url_base']) . "',
						'" . gCleanField($_REQUEST['classe_integracao']) . "',
						'" . gCleanField($_REQUEST['porta_rede']) . "',
						{$campoSenha}
                        '" . gCleanField($_REQUEST['quantidade_tentativas']) . "',
                        '" . gCleanField($_REQUEST['tempo_limite']) . "')";
			dbQuery($sql);
            $_SESSION["dadosCadastrados"] = 1;
		} else {
            if ($_REQUEST['senha'] <> SENHA_NAO_MODIFICADA) {
                $campoSenha = " senha = HEX(AES_ENCRYPT('" . gCleanField($_REQUEST['senha']) . "', '{$AESKEY}')), ";
            }

			$sql = "UPDATE gatilhos_configuracoes
					SET usuario = '" . gCleanField($_REQUEST['usuario']) . "',
						id_pessoas_proprietario = '" . gCleanField($_REQUEST['idPessoasProprietario']) . "',
						url_base = '" . gCleanField($_REQUEST['url_base']) . "',
						classe_integracao = '" . gCleanField($_REQUEST['classe_integracao']) . "',
						porta_rede = '" . gCleanField($_REQUEST['porta_rede']) . "',
						{$campoSenha}
                        quantidade_tentativas = '" . gCleanField($_REQUEST['quantidade_tentativas']) . "',
                        tempo_limite = '" . gCleanField($_REQUEST['tempo_limite']) . "'
					WHERE id = {$idGatilhosConfiguracoes}";
			dbQuery($sql);
            $_SESSION["dadosAtualizados"] = 1;
		}

		$urlRedirect = $o->page."&gPage=" . GERENCIADOR_ACCESS_POINT. "&gId=".$gId . "&classe_integracao=" . $_REQUEST['classe_integracao'];
		header('Location: '.$urlRedirect);
		break;


    case TESTE_CONEXAO:
        $urlBase = $_REQUEST['url_base'];
        $classeIntegracao = $_REQUEST['classe_integracao'];

        if (!$urlBase) {
            $html .= $o->msgDanger('URL não informada!');
            $html .= $backButton;
            break;
        }

        if (!$classeIntegracao) {
            $html .= $o->msgDanger('Classe de integração não informada!');
            $html .= $backButton;
            break;
        }

        $ch = curl_init($urlBase);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erros    = curl_errno($ch);
        curl_close($ch);

        if ($erros) {
            $html .= $o->msgDanger("Não foi possível conectar em {$urlBase}");
            $html .= $backButton;
            break;
        }

        // Se conseguiu realizar o ping acima... tenta executar o teste de autenticacao
		if (in_array('teste', explode("/", (string) $_SERVER['REQUEST_URI']))) {
			$ambiente = '/teste';
		}

        require_once $_SERVER["DOCUMENT_ROOT"].$ambiente."/wms/giusoft/res/api/accesspoint.php";
        $pontoAcesso = new PontoAcesso(['empresa' => $EMPRESA]);

        $autenticar = $pontoAcesso->testarConexao($classeIntegracao, [
            'idPessoasProprietario' => $_REQUEST['idPessoasProprietario'],
        ]);

        if (!$autenticar) {
            $html .= $o->msgDanger("Falha ao autenticar com {$classeIntegracao}");
            $html .= $backButton;
            break;
        }

		$_SESSION["testeConexao"] = 1;
		header('Location: '.$o->page."&gPage=".GERENCIADOR_ACCESS_POINT."&gId=".$gId."&classe_integracao=".$classeIntegracao);
		break;


	case MOMENTO_INTEGRACAO:
		$idPessoasProprietario = ($_REQUEST['classe_integracao'] == 'omie') ? 1 : $gId;
		$classeIntegracao = $_POST["classe_integracao"] ?: $_REQUEST["classe_integracao"];
		$idMomento = $_GET["idMomento"] ?: null;



        if ($idMomento) {
            $sql = "SELECT
                        G.id,
                        G.metodo,
                        G.http_verbo,
                        G.url_rota,
                        G.descricao,
                        G.ativo,
                        G.enviar_tempo_real,
                        G.passiva
                    FROM gatilhos G
                    LEFT JOIN gatilhos_configuracoes GC
                        ON GC.id = G.id_gatilhos_configuracoes
                    WHERE
                        G.id = '{$idMomento}'
                        AND GC.id_pessoas_proprietario = '{$idPessoasProprietario}'
                        AND GC.classe_integracao = '{$classeIntegracao}'";
            $rs = dbQuery($sql)[0];
        }

        if ($idPessoasProprietario == 1) {
            $html .= mostraCabecalho($_GET["gId"]);
        } else {
            $html .= mostraCabecalho($idPessoasProprietario);
        }

		$frm = new gForm();
        $frm->row(
            $frm->add("{allowBlank:false; name: metodo; fieldLabel: Método / Momento; type: text; value:" . $rs['metodo'] . "}"),
            $frm->add("{name: descricao; fieldLabel: Descrição; type: text; allowBlank: false; value:" . $rs['descricao'] . "}")
        );

        $frm->row(
            $frm->add("{allowBlank:false; name: http_verbo; fieldLabel: Verbo http; type: combo; items:'POST, PUT, PATCH, DELETE, GET'; value:" . $rs['http_verbo'] . "}"),
            $frm->add("{name: url_rota; fieldLabel: Url da rota; type: text; allowBlank: false; value:" . $rs['url_rota'] . "}")
        );

        $frm->row(
            $frm->add("{name: enviar_tempo_real; fieldLabel: Enviar em tempo real; type: checkbox; value:" . ($idMomento ? $rs['enviar_tempo_real'] : 1) . "}"),
            $frm->add("{name: ativo; fieldLabel: Ativo; type: checkbox; value:" . ($idMomento ? $rs['ativo'] : 1) . "}")
        );

        $frm->row(
            $frm->add("{name: passiva; fieldLabel: Passiva; type: checkbox; value:" . ($idMomento ? $rs['passiva'] : 1) . "}")
        );

		if ($idMomento) {
            $frm->add("{name: idMomento; type: hidden; value: {$idMomento}}");
		}

        $frm->add("{name: classe_integracao; type: hidden; value: {$classeIntegracao}}");
        $frm->add("{name: id_pessoas_proprietario; type: hidden; value: {$idPessoasProprietario}}");
		$frm->add("{name: gPage; type: hidden; value: ".SALVAR_MOMENTO_INTEGRACAO."}");
		$frm->addButton("{title: Voltar sem salvar; style: default; href: ".$o->page."&gPage=".GERENCIADOR_ACCESS_POINT."&gId=".$gId."&classe_integracao=".$classeIntegracao."}");
		$html .= $frm->render($o);
		break;


	case SALVAR_MOMENTO_INTEGRACAO:
		$idPessoasProprietario = $_POST['id_pessoas_proprietario'] ?: $_GET["gId"];

		if ($_GET["idMomento"] && $_GET['classe_integracao'] == "omie") {
			$idPessoasProprietario = 1;
		}

		$sql = "SELECT id FROM gatilhos_configuracoes WHERE id_pessoas_proprietario = '{$idPessoasProprietario}' AND classe_integracao = '{$_REQUEST['classe_integracao']}';";
		$idGatilhosConfiguracoes = dbFastQuery($sql)[0]["id"];

		$mtz = [];
		$mtz['metodo'] = $_REQUEST['metodo'];
		$mtz['http_verbo'] = $_REQUEST['http_verbo'];
		$mtz['url_rota'] = $_REQUEST['url_rota'];
		$mtz['descricao'] = gCleanField($_REQUEST['descricao']);
		$mtz['ativo'] = gDBCheck($_REQUEST['ativo']);
		$mtz['enviar_tempo_real'] = gDBCheck($_REQUEST['enviar_tempo_real']);
		$mtz['passiva'] = gDBCheck($_REQUEST['passiva']);
		$mtz['id_gatilhos_configuracoes'] = (int) $idGatilhosConfiguracoes;

		if ($_REQUEST["idMomento"]) {
			dbUpdate("gatilhos", $mtz, $_REQUEST['idMomento']);
			$_SESSION["dadosAtualizados"] = 1;
			$_SESSION["nomeMomento"] = $_REQUEST["metodo"];
		} else {
			dbInsert("gatilhos", $mtz);
			$_SESSION["dadosCadastrados"] = 1;
		}

		header('Location: '.$o->page."&gPage=".GERENCIADOR_ACCESS_POINT."&gId=".$gId."&classe_integracao=".$_GET['classe_integracao']);
		break;


    case ATIVAR_DESATIVAR_MOMENTO:
        $idMomento = $_GET['idMomento'];

        $sql = "UPDATE gatilhos SET ativo = !ativo WHERE id = '{$idMomento}';";
        dbQuery($sql);

        header('Location: '.$o->page."&gPage=".GERENCIADOR_ACCESS_POINT."&gId=".$gId."&classe_integracao=".$_GET['classe_integracao']);
        break;


    case MOMENTO_COPIAR:
        $html .= mostraCabecalho($gId);
        $frm = new gForm("{columns: 2}");
        $frm->add("{name: id_pessoas; fieldLabel: Copiar gatilhos da empresa; type: combo; items: " . $sp['combo_empresas'] . "}");
        $frm->add("{name: gPage; type: hidden; value: " . MOMENTO_COPIAR_SALVAR . "}");
        $frm->add("{name: gId; type: hidden; value: " . $gId . "}");
        $frm->addButton("{title: Voltar; style: default; href: " . $o->page . "&gPage=" . GERENCIADOR_ACCESS_POINT . "&gId=" . $gId . "&classe_integracao=" . $_GET['classe_integracao'] . "}");
        $html .= $frm->render($o);
        break;


    case MOMENTO_COPIAR_SALVAR:

        $idGatilhosConfiguracoes = dbFastQuery("SELECT id FROM gatilhos_configuracoes WHERE id_pessoas_proprietario=" . $gId . " AND classe_integracao=" . "'" . $_GET['classe_integracao'] . "'")[0]['id'];
        if (!$idGatilhosConfiguracoes) {
            $html .= $o->msgDanger("Não foi possível copiar os momentos, você precisa cadastar a classe selecionada");
            $html .= $backButton;
            break;
        }

        // Primeiro remove os momentos da empresa
        $sql = "DELETE
                    gatilhos
                FROM gatilhos
                JOIN gatilhos_configuracoes
                    ON gatilhos.id_gatilhos_configuracoes = gatilhos_configuracoes.id
                WHERE gatilhos_configuracoes.id_pessoas_proprietario = " . $gId . "
                AND gatilhos_configuracoes.classe_integracao = " . "'" . $_GET['classe_integracao'] . "'";
        dbQuery($sql);

        // Adiciona os momentos da empresa selecionada
        $sql = "SELECT
                    gatilhos.*
                FROM
                    gatilhos
                JOIN gatilhos_configuracoes
                    ON gatilhos.id_gatilhos_configuracoes = gatilhos_configuracoes.id
                WHERE gatilhos_configuracoes.id_pessoas_proprietario=" . ( (int) $_REQUEST['id_pessoas']) . "
                AND gatilhos_configuracoes.classe_integracao=" . "'" . $_GET['classe_integracao'] . "'";
        $rs = dbQuery($sql);

        foreach ($rs as $row) {
            $mtz = [];
            $mtz['url_rota'] = $row['url_rota'];
            $mtz['http_verbo'] = $row['http_verbo'];
            $mtz['descricao'] = $row['descricao'];
            $mtz['metodo'] = $row['metodo'];
            $mtz['ativo'] = $row['ativo'];
            $mtz['id_gatilhos_configuracoes'] = $idGatilhosConfiguracoes;
            dbInsert("gatilhos", $mtz);
        }

        redirect($o->page . "&gPage=" . GERENCIADOR_ACCESS_POINT . "&gId=" . $gId . "&classe_integracao=" . $_GET['classe_integracao']);
        break;


	case REGISTRO_AVANCAR:
		$sql="SELECT id FROM pessoas WHERE ".$persistencia->filtro." ORDER BY situacao DESC, nome";
		$rs=dbQuery($sql);
		$max=count($rs);
		$cnt=0;$achou=false;$idAnt=$id=$gId;
		while (!$achou)
		{
			$row=$rs[$cnt];
			if ($rs[$cnt]['id']==$gId)
			{
				$achou=true;
				$id=$idAnt;
			}
			$idAnt=$rs[$cnt]['id'];
			++$cnt;
			if ($cnt>$max)
				$achou=true;
		}
		redirect($o->page."&gPage=".$gIdRel."&gId=".$id);
    	break;


    case REGISTRO_VOLTAR:
        $sql = "SELECT id FROM pessoas p WHERE " . $persistencia->filtro . " ORDER BY situacao DESC, nome";
        $rs = dbQuery($sql);
        $max = count($rs);
        $cnt = 0;
        $achou = false;
        $id = $gId;
        while (!$achou) {
            $row = $rs[$cnt];
            if ($rs[$cnt]['id'] == $gId) {
                $achou = true;
                ++$cnt;
                $id = $rs[$cnt]['id'];
                if ($id == 0) {
                    $id = $gId;
                }
            }
            ++$cnt;
            if ($cnt > $max) {
                $achou = true;
            }
        }
        redirect($o->page . "&gPage=" . $gIdRel . "&gId=" . $id);
        break;


    case WEBCAM:
        $html .= mostraCabecalho($gId);
        $html .= $o->webcam(WEBCAM_SALVAR);
        break;


    case WEBCAM_SALVAR:
        $o->webcamSave($gPathUsrFiles . '/' . $gId . '.jpg');
        break;


    case ARMAZENS:
        $sql = "SELECT id,apelido FROM pessoas WHERE situacao='Ativo' AND cliente=1 ORDER BY nome";
        $rs = dbQuery($sql);
        foreach ($rs as $row) {
            $sql = "SELECT * FROM pessoas_armazens WHERE id_pessoas=" . intval($row["id"]) . " AND id_armazens=" . $_SESSION["armazemAtualId"];
            $existe = dbQuery($sql);
        }
        $html .= mostraCabecalho($gId);
        $frm = new gForm("{columns: 2}");
        $frm->add("{name: id_armazens; fieldLabel: Armazém; type: combo; items: " . $sp['combo_armazens'] . ";allowBlank:false}");
        $frm->add("{name: gPage; type: hidden; value: " . ARMAZENS_SALVAR . "}");
        $frm->add("{name: gId; type: hidden; value: " . $gId . "}");
        $html .= $frm->render($o);
        $sql = "SELECT
                    armazens.descricao armazemNome,
					pessoas_armazens.*
				FROM pessoas_armazens
				INNER JOIN armazens ON armazens.id = pessoas_armazens.id_armazens
				WHERE pessoas_armazens.id_pessoas=$gId AND cancelado = 0
				ORDER BY pessoas_armazens.cancelado ASC, armazens.descricao";
        $rs = dbQuery($sql);
        if ($rs) {
            $mtz = [];
            $mtz[] = "<-Cancelar";
            $mtz[] = "<-Armazém";
            $mtz[] = "<-Informações";
            $html .= $o->tableBegin("big", true);
            $html .= $o->tableRow($mtz, "header");
            foreach ($rs as $row) {
                $dados = "Criado por " . gFieldById("pessoas", $row['id_pessoas_criou'], "nome") . " - " . gDateTime($row['data_criou']);
                $style = "detail";

                $mtz = [];
                if ($row['cancelado']) {
                    $style = "bg-danger";
                    $dados .= "<BR>Cancelado por " . gFieldById("pessoas", $row['id_pessoas_cancelou'], "nome") . " - " . gDateTime($row['data_cancelou']);
                    $mtz[] = "";
                } else {
                    $mtz[] = "<-" . $o->button("{active: " . $active1 . "; icon: trash; style: danger; size: small; caption: Cancelar; hint: Cancelar registro; href: " . $o->page . "&gPage=" . ARMAZENS_CANCELAR . "&gId=" . $gId . "&gIdDel=" . $row['id'] . "}");
                    ;
                }
                $mtz[] = "<-" . $row['armazemNome'];
                $mtz[] = "<-" . $dados;
                $html .= $o->tableRow($mtz, $style);
            }
            $html .= $o->tableEnd();
        }
        break;


    case ARMAZENS_SALVAR:
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sql = "SELECT id FROM pessoas_armazens WHERE id_pessoas=$gId AND id_armazens=" . intval($_REQUEST["id_armazens"]) . " AND cancelado=0 ";
            $rs = dbQuery($sql);
            if (count($rs) > 0) {
                $html .= $o->msgDanger("Falhas de validação: " . $o->ul(["Não é possível adicionar o mesmo armazem"]));
                $html .= $o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: info; size: normal; href: " . $o->page . "&gPage=" . ARMAZENS . "&gId=" . $gId);
                return;
            } else {
                $mtz = [];
                $mtz['id_armazens']      = $_REQUEST['id_armazens'];
                $mtz['id_pessoas_criou'] = $usrId;
                $mtz['id_pessoas']       = $gId;
                $mtz['data_criou']       = date("Y-m-d H:i:s");
                dbInsert('pessoas_armazens', $mtz);
                userLog('Pessoa - armazém adicionado - id <a href="pessoas.php?g=itens&gPage=' . CAPA . '&gId=' . $gId . '">' . $gId . '</a>');
            }
        }

        $persistencia->atualizarPessoasSituacao($gId);

        redirect($o->page . "&gPage=" . ARMAZENS . "&gId=" . $gId);
        break;


    case ARMAZENS_CANCELAR:
        if ((int) $gIdDel > 0) {
            $sql = "UPDATE pessoas_armazens
				  SET data_cancelou='" . date("Y-m-d H:i:s") . "',id_pessoas_cancelou=$usrId,cancelado=1 WHERE id=$gIdDel";
            dbQuery($sql);
            userLog('Pessoa - armazém cancelado - id <a href="pessoas.php?g=itens&gPage=' . CAPA . '&gId=' . $gId . '">' . $gId . '</a>');
        }

        $persistencia->atualizarPessoasSituacao($gId);

        redirect($o->page . "&gPage=" . ARMAZENS . "&gId=" . $gId);
        break;


    case CANCELAR:
        $mtz = [];
        $mtz["id_pessoas_cancelou"] = $usrId;
        $mtz["data_cancelou"] = date('Y-m-d H:i:s');
        $mtz["situacao"] = "Cancelado";
        dbUpdate("pessoas", $mtz, $gId);
        $rota = $o->page . "&gPage=" . INICIO;
        redirect($rota);
        break;

    case CFOPS:
        $html .= mostraCabecalho($gId);
        $frm = new gForm();
        $html .= $persistencia->geraCamposDoFormularioCfop($frm);

        $sql = "SELECT pessoas_cfops.id,cfops.descricao,cfops.codigo
				FROM pessoas_cfops
				INNER JOIN cfops ON cfops.id = pessoas_cfops.id_cfops
				WHERE id_pessoas=$gId";
        $rs = dbQuery($sql);
        if ($rs) {
            $mtz = ['&nbsp;','Código','<-Descrição'];
            $html .= $o->tableBegin('big', true);
            $html .= $o->tableRow($mtz, 'header');
            foreach ($rs as $row) {
                $mtz = [
                    '' . $o->button("{icon: trash; title: Excluir; hint: Excluir CFOP; style: danger; size: small; href: " . $o->page . "&gPage=" . CFOPS_EXCLUIR . "&gId=" . $gId . "&gIdu=" . $row['id'] . "}"),
                    '' . $row['codigo'],
                    '<-' . $row['descricao']
                ];
                $html .= $o->tableRow($mtz, 'detail');
            }
            $html .= $o->tableEnd();
        }
        break;


    case CFOPS_SALVAR:
        $mtz = [
            'id_pessoas'    => (int) $_REQUEST['gId'],
            'id_cfops'      => (int) $_REQUEST['id_cfops']
        ];
        dbInsert('pessoas_cfops', $mtz);
        redirect($o->page . "&gPage=" . CFOPS . "&gId=" . $gId);
        break;


    case CFOPS_EXCLUIR:
        $sql = "DELETE FROM pessoas_cfops WHERE id=" . (int) $_REQUEST['gIdu'];
        dbQuery($sql);
        redirect($o->page . "&gPage=" . CFOPS . "&gId=" . $gId);
        break;


    case PRIORIDADES:
        $html .= mostraCabecalho($gId);
        $sql = "SELECT * FROM pessoas_prioridades_reservas WHERE id_pessoas=$gId ORDER BY ordem";
        $rs = dbQuery($sql);
        if (count($rs) == 0) {
            $sql = "SELECT * FROM pessoas_prioridades_reservas WHERE id_pessoas=0 ORDER BY ordem";
            $rs = dbQuery($sql);
            foreach ($rs as $row) {
                $mtz = [];
                $mtz['id_pessoas'] = $gId;
                $mtz['id_prioridade'] = $row['id'];
                $mtz['ativo'] = $row['ativo'];
                $mtz['ordem'] = $row['ordem'];
                $mtz['descricao'] = $row['descricao'];
                dbInsert('pessoas_prioridades_reservas', $mtz);
            }
            $sql = "SELECT * FROM pessoas_prioridades_reservas WHERE id_pessoas=$gId ORDER BY ordem";
            $rs = dbQuery($sql);
        }

        if ($rs[0]['ativo']) {
            $btns = $o->button("{icon: thumbs-up; style: success; size: small; href:" . $o->page . "&gId=$gId&gIdd=" . $rs[0]['id'] . "&gPage=" . PRIORIDADES_ATIVAR . "}");
        } else {
            $btns = $o->button("{icon: thumbs-down; style: danger; size: small; href:" . $o->page . "&gId=$gId&gIdd=" . $rs[0]['id'] . "&gPage=" . PRIORIDADES_ATIVAR . "}");
        }

        $html .= $btns . "Respeitar a ordem de prioridades abaixo para reservas deste cliente";
        $html .= $o->hr();

        $html .= $o->tableBegin("medium", true);
        $mtz = [];
        $mtz[] = "<-Opções";
        $mtz[] = "<-Prioridade";
        $html .= $o->tableRow($mtz, "header");
        $id = 0;
        foreach ($rs as $row) {
            if ($id > 0) {
                $mtz = [];
                if ($row['ativo']) {
                    $btns = $o->button("{icon: thumbs-up; style: success; size: small; href:" . $o->page . "&gId=$gId&gIdd=" . $row['id'] . "&gPage=" . PRIORIDADES_ATIVAR . "}");
                } else {
                    $btns = $o->button("{icon: thumbs-down; style: danger; size: small; href:" . $o->page . "&gId=$gId&gIdd=" . $row['id'] . "&gPage=" . PRIORIDADES_ATIVAR . "}");
                }

                if ($id > 1) {
                    $btns .= $o->button("{icon: arrow-up; style: info; size: small; href:" . $o->page . "&gId=$gId&gIdd=" . $row['id'] . "&gPage=" . PRIORIDADES_ORDENAR . "&sinal=0}");
                } else {
                    $btns .= $o->button("{icon: arrow-up; disabled: true; style: info; size: small; href:" . $o->page . "&gId=$gId&gIdd=" . $row['id'] . "&gPage=" . PRIORIDADES_ORDENAR . "&sinal=0}");
                }

                if ($id < count($rs) - 1) {
                    $btns .= $o->button("{icon: arrow-down; style: info; size: small; href:" . $o->page . "&gId=$gId&gIdd=" . $row['id'] . "&gPage=" . PRIORIDADES_ORDENAR . "&sinal=2}");
                } else {
                    $btns .= $o->button("{icon: arrow-down; style: info; disabled: true; size: small; href:" . $o->page . "&gId=$gId&gIdd=" . $row['id'] . "&gPage=" . PRIORIDADES_ORDENAR . "&sinal=2}");
                }

                $mtz[] = "<-" . $btns;
                $mtz[] = "<-" . $row['descricao'];
                $html .= $o->tableRow($mtz, "detail");
            }
            $id++;
        }
        $html .= $o->tableEnd();
        break;


    case PRIORIDADES_ATIVAR:
        $sql = "SELECT
                    pessoas_juridicas.priorizar_palete_aberto,
                    pessoas_juridicas.priorizar_palete_fechado
                FROM pessoas_juridicas WHERE id_pessoas = " . $gId;
        $buscaFlags = dbFastQuery($sql)[0];

        if ($buscaFlags['priorizar_palete_aberto'] == 1 || $buscaFlags['priorizar_palete_fechado'] == 1) {
            $html .= $o->msgDanger("Para ativar a prioridade da reserva, é necessário desativar previamente a prioridade de palete fechado/aberto na aba Operação do cadastro desta empresa");
            $html .= $backButton;
            break;
        }

        dbQuery("UPDATE pessoas_prioridades_reservas SET ativo=1-ativo WHERE id = " . intval($_REQUEST['gIdd']));
        redirect($o->page . "&gPage=" . PRIORIDADES . "&gId=" . $gId);
        break;


    case PRIORIDADES_ORDENAR:
        $gIdd = intval($_REQUEST['gIdd']);
        $sinal = intval($_REQUEST['sinal']) - 1;
        $sql = "SELECT * FROM pessoas_prioridades_reservas WHERE id_pessoas=$gId ORDER BY ordem";
        $rs = dbQuery($sql);
        foreach ($rs as $key => $row) {
            if ($gIdd == $row['id']) {
                $idAntes = $key - 1;
                $idAtual = $key;
                $idDepois = $key + 1;
            }
        }

        $novaOrdem = 0;
        if ($sinal == -1) {
            $novaOrdem = $rs[$idAntes]['ordem'];
            $sql = "UPDATE pessoas_prioridades_reservas SET ordem=ordem+1 WHERE id=" . $rs[$idAntes]['id'];
            gDR($sql);
            dbQuery($sql);
        }

        if ($sinal == 1) {
            $novaOrdem = $rs[$idDepois]['ordem'];
            $sql = "UPDATE pessoas_prioridades_reservas SET ordem=ordem-1 WHERE id=" . $rs[$idDepois]['id'];
            gDR($sql);
            dbQuery($sql);
        }

        if ($novaOrdem > 0) {
            $sql = "UPDATE pessoas_prioridades_reservas SET ordem=$novaOrdem WHERE id=$gIdd";
            gDR($sql);
            dbQuery($sql);
        }

        redirect($o->page . "&gPage=" . PRIORIDADES . "&gId=" . $gId);
        break;


    case OPERACAO:
        $html .= mostraCabecalho($gId);

        $rs = $persistencia->obtemRegistros("p.id=" . $gId)[0];
        $frm = new gForm();
        $html .= $persistencia->gerarCamposAbaOperacao($frm, $rs, SALVAR_ABA_OPERACAO);
        break;


    case SALVAR_ABA_OPERACAO:

        $sql = "SELECT ativo
                FROM pessoas_prioridades_reservas
                WHERE id_prioridade = 1 AND id_pessoas = " . $gId . " LIMIT 1";
        $buscaPrioridadeReserva = dbFastQuery($sql)[0]['ativo'];

        if ($buscaPrioridadeReserva && (gDBCheck($_REQUEST['priorizar_palete_aberto']) || gDBCheck($_REQUEST['priorizar_palete_fechado']))) {
            $html .= $o->msgDanger("Para ativar a prioridade de palete fechado/aberto, é necessário desativar previamente a prioridade da reserva na aba Prioridades do cadastro desta empresa");
            $html .= $backButton;
            break;
        }

        $idPessoaJuridica = dbQuery("SELECT id FROM pessoas_juridicas WHERE id_pessoas = {$gId}")[0]['id'];
        if (!$idPessoaJuridica) {
            $html .= $o->msgDanger('Clique no botão Confirmar na aba de Dados pessoais');
            $html .= $backButton;
            break;
        }

        $flds = [];
        $flds['faz_segunda_separacao'] = gDBCheck($_REQUEST['segunda_separacao']);
        $flds['exigir_sku_separacao'] = gDBCheck($_REQUEST['exigir_sku_separacao']);
        $flds['lote_xprod'] = gDBCheck($_REQUEST['lote_xprod']);
        $flds['fiscal'] = gDBCheck($_REQUEST['fiscal']);
        $flds['indicar_posicao'] = gDBCheck($_REQUEST['indicar_posicao']);
        $flds['priorizar_palete_aberto'] = gDBCheck($_REQUEST['priorizar_palete_aberto']);
        $flds['priorizar_palete_fechado'] = gDBCheck($_REQUEST['priorizar_palete_fechado']);
        $flds['exige_uma_entrada_convencional'] = gDBCheck($_REQUEST['exige_uma_entrada_convencional']);
        $flds['codigo_sistema_externo'] = gCleanField($_REQUEST['codigo_sistema_externo']);
        $flds['foto_obrigatoria'] = gDBCheck($_REQUEST['foto_obrigatoria']);
        $flds['tipo_separacao'] = intval($_REQUEST['tipo_separacao']);
        $flds['prazo'] = gCleanField($_REQUEST['prazo']);
        $flds['lead_time'] = gCleanField($_REQUEST['leadTime']);
        $flds['permitir_portaria_sem_os'] = gDBCheck($_REQUEST['permitirPortariaSemOs']);
        $flds['quantidade_posicoes'] = intval($_REQUEST['quantidade_posicoes']);
        $flds['variacao_divergencia'] = gDBFloat(gCleanField($_REQUEST['variacao_divergencia']));
        dbUpdate('pessoas_juridicas', $flds, $idPessoaJuridica);

        $persistencia->atualizarPessoasSituacao($gId);

        $persistencia->persistirTiposEntrada($_REQUEST['id_tipos_entrada']);
        redirect($o->page . "&gPage=" . OPERACAO . "&gId=" . $gId);
        break;

    case IMPORTACAO:
        $frm = new gForm("columns: 2");
        $frm->addFormMessage("<b>Importação de empresas</b>");

        $comboTipoEmpresa = [];
        $comboTipoEmpresa['cliente'] = "Cliente";
        $comboTipoEmpresa['cliente_final'] = "Cliente Final";
        $comboTipoEmpresa['fornecedor'] = "Fornecedor";
        $comboTipoEmpresa['transportadora'] = "Transportadora";

        $frm->add("{type: comboMultiSelection; name: tipo_empresa; fieldLabel: Tipo de Empresa; items:'" . json_encode($comboTipoEmpresa) . "'; allowBlank: false;}");
        $frm->add("{name: arquivo; fieldLabel: Arquivo .CSV; type: file; accept: .csv; allowBlank: false; }");

        $modelo = "Nome,Razão Social,Tipo,CNPJ,Inscrição Estadual,Inscrição Municipal,Código do sistema externo,Bairro,Endereço,Número,CEP";
        $frm->addButton("{icon: download; title: Baixar modelo CSV; hint: Baixar modelo CSV; style: info; size: normal; href: " . $o->page . "&gPage=" . IMPRIMIR_MODELO . "&modelo=" . $modelo);

        $frm->add("{name: gPage; type: hidden; value: " . IMPORTACAO_SALVAR . "}");
        $html .= $frm->render($o);

        $html .= $o->ul(
            [
                "Submeta o arquivo sem o título nas colunas",
                "Use o delimitador de campo ponto e vírgula <b>( ; )</b> e o delimitador de texto sendo aspas duplas <b>( \" )</b>",
                "O <b>Tipo</b> de empresa deve ser <b>'F'</b> para pessoa física e <b>'J'</b> para pessoa jurídica",
                "Informe <b>apenas números</b> para os dados: CNPJ, CEP, Inscrição Estadual, Inscrição Municipal e Código do sistema externo"
            ]
        );
        break;


    case IMPORTACAO_SALVAR:
        $extensao = strtolower(pathinfo((string) $_FILES['arquivo']['name'], PATHINFO_EXTENSION));
        if ($extensao !== 'csv') {
            $html .= $o->msgDanger("O arquivo deve ser do tipo CSV");
            $html .= $backButton;
            break;
        }

        $arquivo = $_FILES['arquivo']['tmp_name'];
        $handle = fopen($arquivo, "r");

        if ($handle === false) {
            $html .= $o->msgDanger("Não foi possível abrir o arquivo");
            $html .= $backButton;
            break;
        }

        $sucesso = 0;
        $erros = [];

        $arquivoCsv = file_get_contents($arquivo);
        $linhas = explode("\n", $arquivoCsv);

        if (count($linhas) == 0) {
            $html .= $o->msgDanger("Nenhum dado encontrado no arquivo");
            $html .= $backButton;
            break;
        }

        $tiposEmpresa = $_REQUEST['tipo_empresa'];
        $tiposEmpresaFormatados = $persistencia->formatarTiposEmpresa($tiposEmpresa);

        foreach ($linhas as $linha => $dados) {
            if (trim($dados) === '') {
                continue;
            }

            $dados = explode(';', $dados);
            if (count($dados) != 11) {
                $erros[] = "Linha " . ($linha + 1) . ": Número incorreto de colunas";
                continue;
            }

            $mensagensErro = $persistencia->validarDadosEmpresa($dados, $linha);

            if ($mensagensErro) {
                $erros[] = "Linha " . ($linha + 1) . ": " . implode(", ", $mensagensErro);
                continue;
            }

            $sql = "SELECT id, razao_social FROM pessoas_juridicas WHERE cnpj = '" . preg_replace("/[^0-9]/", "", gCleanField($dados[3])) . "' AND codigo_sistema_externo = '" . preg_replace("/[^0-9]/", "", gCleanField($dados[6])) . "' LIMIT 1";
            $rs = dbQuery($sql)[0];
            if ($rs['id']) {
                $erros[] = "Linha " . ($linha + 1) . ": Já existe uma empresa cadastrada com este cnpj e código de sistema externo. Empresa: " . $rs['razao_social'];
                continue;
            }

            $pessoa = [
                'nome' => preg_replace("/[^a-zA-Z0-9\s]/", "", gCleanField($dados[0])),
                'apelido' => substr(preg_replace("/[^a-zA-Z0-9\s]/", "", gCleanField($dados[0])), 0, 50),
                'tipo' => preg_replace("/[^a-zA-Z]/", "", strtoupper($dados[2])),
                'situacao' => 'Ativo',
                'data_cadastro' => date('Y-m-d H:i:s'),
                'id_pessoas_criou' => $usrId,
                'cliente' => $tiposEmpresaFormatados['cliente'],
                'cliente_final' => $tiposEmpresaFormatados['cliente_final'],
                'fornecedor' => $tiposEmpresaFormatados['fornecedor'],
                'transportadora' => $tiposEmpresaFormatados['transportadora']
            ];

            $idPessoa = dbInsert('pessoas', $pessoa, true);
            $arrPessoas[$linha]['idPessoas'] = $idPessoa;

            if ($idPessoa) {
                $pessoaJuridica = [
                    'id_pessoas' => $idPessoa,
                    'razao_social' => preg_replace("/[^a-zA-Z0-9\s]/", "", gCleanField($dados[1])),
                    'cnpj' => preg_replace("/[^0-9]/", "", gCleanField($dados[3])),
                    'insc_estadual' => preg_replace("/[^0-9]/", "", gCleanField($dados[4])),
                    'insc_municipal' => preg_replace("/[^0-9]/", "", gCleanField($dados[5])),
                    'codigo_sistema_externo' => preg_replace("/[^0-9]/", "", gCleanField($dados[6]))
                ];
                $pessoasJuridicas[$linha] = $pessoaJuridica;

                $pessoaEndereco = [
                    'id_pessoas' => $idPessoa,
                    'bairro' => preg_replace("/[^a-zA-Z0-9\s]/", "", gCleanField($dados[7])),
                    'endereco' => preg_replace("/[^a-zA-Z0-9\s]/", "", gCleanField($dados[8])),
                    'numero' => preg_replace("/[^a-zA-Z0-9\s]/", "", gCleanField($dados[9])),
                    'cep' => preg_replace("/[^0-9]/", "", gCleanField($dados[10]))
                ];
                $pessoasEnderecos[$linha] = $pessoaEndereco;

                $sucesso++;
            } else {
                $erros[] = "Linha " . ($linha + 1) . ": Erro ao inserir no banco de dados";
            }
        }

        if ($pessoasJuridicas) {
            $values = [];
            foreach ($pessoasJuridicas as $linha => $dados) {
                $values[] = "('" . implode("','", array_values($dados)) . "')";
            }

            $sql = "INSERT INTO pessoas_juridicas (" . implode(',', array_keys(reset($pessoasJuridicas))) . ") VALUES " . implode(',', $values);
            dbQuery($sql);
        }

        if ($pessoasEnderecos) {
            $values = [];
            foreach ($pessoasEnderecos as $linha => $dados) {
                $values[] = "('" . implode("','", array_values($dados)) . "')";
            }

            $sql = "INSERT INTO pessoas_enderecos (" . implode(',', array_keys(reset($pessoasEnderecos))) . ") VALUES " . implode(',', $values);
            dbQuery($sql);
        }

        if ($sucesso > 0) {
            $html .= $o->msgSuccess("$sucesso empresas importadas com sucesso!");
        }

        if ($erros) {
            $msg = "Erros encontrados durante a importação:";
            $html .= $o->msgDanger($msg . $o->ul($erros));
        }

        $html .= $o->backButton;
        break;

    case IMPRIMIR_MODELO:
        downloadModeloImportacao($modelo, $_REQUEST['gId']);
        break;
}

/**
 * mostraCabecalho
 * @param integer $gId Id do aluno
 * @return string HTML
 */
function mostraCabecalho($gId)
{
    global $o, $gPage, $gParam;
    $sql = "SELECT p.id idp, p.*,f.*
			FROM pessoas p
			LEFT JOIN pessoas_fisicas f on p.id=f.id_pessoas
			WHERE p.id>2 AND p.id=" . $gId;
    $rs = dbQuery($sql);
    if (count($rs) > 0) {
        $row = $rs[0];
        $html .= $o->tableBegin('big', true);
        $mtz = [];
        $mtz[] = '<-' . $o->small('Nome') . '<br>' . $o->label($row['idp']) . '<b>' . $row['nome'] . '</b>&nbsp;';
        $mtz[] = '<-' . $o->small('Apelido') . '<br>' . $row['apelido'] . '&nbsp;';
        $mtz[] = '<-' . $o->small('Situação') . '<br>' . $row['situacao'] . '&nbsp;';
        $html .= $o->tableRow($mtz, 'header');

        $mtz = [];
        $mtz[] = '<-' . $o->small('Celular') . '<br>' . $row['celular'] . '&nbsp;';
        $mtz[] = '<-' . $o->small('Telefone') . '<br>' . $row['telefone'] . '&nbsp;';
        $mtz[] = '<-' . $o->small('E-mail') . '<br>' . $row['email'] . '&nbsp;';
        $html .= $o->tableRow($mtz, 'header');

        $mtz = [];
        $mtz[] = '~2<-' . $o->small('Observações') . '<br>' . nl2br(base64_decode((string) $row['observacoes'])) . '&nbsp;';
        $mtz[] = '<-' . $o->small('Data do cadastro') . '<br>' . gDateTime($row['data_cadastro']) . "&nbsp;";
        $html .= $o->tableRow($mtz, 'header');

        $sql = "SELECT id FROM pessoas_armazens WHERE cancelado = 0 AND id_pessoas = " . $gId . " LIMIT 1";
        $verificarPessoasArmazem = dbFastQuery($sql)[0]['id'];

        $mtz = [];
        if ($verificarPessoasArmazem) {
            $mtz[] = '~6<>Cadastro do pessoas suficientemente completo - pode ser utilizado';
            $html .= $o->tableRow($mtz, 'success');
        } else {
            $mtz[] = '~6<>Cadastro do pessoas sem dados suficientes - não poderá ser utilizado';
            $html .= $o->tableRow($mtz, 'danger');
        }

        $html .= $o->tableEnd();

        $active1 = $active2 = $active3 = $active4 = $active5 = $active6 = $active7 = $active8 = $active9 = 'false';
        switch ($gPage) {
            case CAPA:
                $active0 = 'true';
                break;
            case DADOS:
                $active1 = 'true';
                break;
            case ENDERECOS:
                $active2 = 'true';
                break;
            case ARMAZEM:
                $active3 = 'true';
                break;
            case OCORRENCIAS:
                $active4 = 'true';
                break;
            case ANEXOS:
                $active5 = 'true';
                break;
            case PERMISSOES:
                $active6 = 'true';
                break;
            case CFOPS:
                $active7 = 'true';
                break;
            case PRIORIDADES:
                $active8 = 'true';
                break;
            case OPERACAO:
                $active9 = 'true';
                break;
        }

        $btns = [];
        $btns[] = $o->button("{active: " . $active0 . "; icon: home; style: info; hint: Capa da ficha de matrícula; href: " . $o->page . "&gPage=" . CAPA . "&gId=" . $gId . "}");
        $btns[] =
            '<div class="btn-group" role="group" aria-label="...">' .
                $o->button("{icon: arrow-left; style: info; hint: Registro anterior; href: " . $o->page . "&gPage=" . REGISTRO_VOLTAR . "&gId=" . $gId . "&gIdRel=" . $gPage . "}") .
                $o->button("{icon: arrow-right; style: info; hint: Próximo registro; href: " . $o->page . "&gPage=" . REGISTRO_AVANCAR . "&gId=" . $gId . "&gIdRel=" . $gPage . "}") .
            '</div>';
        $btns[] = $o->button("{active: " . $active1 . "; icon: file-alt; caption: Dados pessoais; hint: Alterar os dados pessoais; href: " . $o->page . "&gPage=" . DADOS . "&gId=" . $gId . "}");
        $btns[] = $o->button("{active: " . $active9 . "; icon: person-carry; caption: Operação; hint: Controle de operações; href: " . $o->page . "&gPage=" . OPERACAO . "&gId=" . $gId . "}");
        $btns[] = $o->button("{active: " . $active2 . "; icon: map-marker; caption: Endereços; hint: Incluir ou alterar endereços; href: " . $o->page . "&gPage=" . ENDERECOS . "&gId=" . $gId . "}");
        $btns[] = $o->button("{active: " . $active3 . "; icon: warehouse; caption: Armazém; hint: Relacionar pessoa ao armazém; href: " . $o->page . "&gPage=" . ARMAZENS . "&gId=" . $gId . "}");
        $btns[] = $o->button("{active: " . $active4 . "; icon: exclamation-triangle; caption: Ocorrências; hint: Incluir ocorrências; href: " . $o->page . "&gPage=" . OCORRENCIAS . "&gId=" . $gId . "}");
        $btns[] = $o->button("{active: " . $active5 . "; icon: paperclip; caption: Anexos; hint: Anexar documentos digitalizados; href: " . $o->page . "&gPage=" . ANEXOS . "&gId=" . $gId . "}");
        if ($gParam['PERFIL_PRODUCAO']['ativo'] == 1) {
            $btns[] = $o->button("{active: " . $active7 . "; icon: file-alt; caption: CFOPs; hint: CFOPs utilizados na emissão de NFe; href: " . $o->page . "&gPage=" . CFOPS . "&gId=" . $gId . "}");
        }
        $btns[] = $o->button("{active: " . $active8 . "; icon: tasks; caption: Prioridades; hint: Ordem de prioridades para reserva e separação; href: " . $o->page . "&gPage=" . PRIORIDADES . "&gId=" . $gId . "}");
        $btns[] = $o->button("{active: " . $active6 . "; icon: lock; caption: Permissões; hint: Permissõs de acesso; href: " . $o->page . "&gPage=" . PERMISSOES . "&gId=" . $gId . "}");
        if ($_SESSION['usrId'] == 1) {
            $btns[] = $o->button("{active: " . $active5 . "; icon: sign-out; caption: Gatilhos; hint: Configuração de gatilhos de integração; href: " . $o->page . "&gPage=" . GERENCIADOR_ACCESS_POINT . "&gId=" . $gId . "}");
        }

        //$html.='<div class="btn-group" role="group" aria-label="...">'.implode(" ",$btns).'</div>';
        $html .= '<div>' . implode(" ", $btns) . '</div>';

        $html .= $o->hr('soft');
    } else {
        $html = '';
    }
    return($html);
}

function requireAccessPoint()
{
    $uriParameters = explode("/", (string) $_SERVER['REQUEST_URI']);
    $empresa = $uriParameters[2];

    if (in_array('teste', $uriParameters)) {
        $ambiente = '/teste';
        $empresa = $uriParameters[3];
    }
    require_once "{$_SERVER["DOCUMENT_ROOT"]}{$ambiente}/wms/{$empresa}/res/api/accesspoint.php";
}

function testarConexaoIntegracao($classIntegracao, $args)
{
    return PontoAcesso::testarConexao($classIntegracao, $args);
}

// function getClassesIntegracao() {
// 	if (!in_array($_SERVER["HTTP_HOST"], ['localhost', '127.0.0.1'])) {
// 		$ambiente = '';
// 	} elseif (strpos($empresa, "/teste") !== false) {
// 		$ambiente = '/teste';
// 	}
// 	require_once $_SERVER['DOCUMENT_ROOT'] . $ambiente . '/wms/giusoft/res/api/accesspoint.php';

// 	$pathClassesIntegracao = str_replace("cadastros", "_classes/integracao/*", __DIR__);
// 	$naoIntegrar = ["gmi", "kimberly_suzano"];
// 	$parametros = montarParametrosIntegracao();
// 	$empresa = $parametros["empresa"];

// 	if (strpos($empresa, "logic") === false) {
// 		$naoIntegrar[] = "omie";
// 	}

// 	foreach (glob($pathClassesIntegracao) as $arquivo) {
// 		$nomeClasse = end(explode("/", $arquivo));
// 		if (!in_array($nomeClasse, $naoIntegrar)) {
// 			$classesIntegracao[$nomeClasse] = $nomeClasse;
// 		}
// 	}
// 	return $classesIntegracao;
// }
