<?php

define('INICIO'              , 0);
define('DADOS'               , 1);
define('PESQUISAR'           , 10);
define('PESQUISAR_RESULTADO' , 11);
define('NOVO'                , 20);
define('NOVO_SALVAR'         , 21);
define('EXCLUIR'             , 30);

$html.=$o->msgTitle("Situação");

$tabela = "areas";

switch($gPage) {
        case INICIO:
        	$html .= '<div class="hidden-print"><form class="form-inline" method="POST" action="index.php?g=painel">';
            $html .= '<input id="pesquisa" name="pesquisa" type="text" class="form-control input-md" placeholder="Pesquisa rápida...">&nbsp;<input type="hidden" name="g" value="painel"><input type="hidden" name="gPage" value="0"> ';
            $html .= '<input id="action" name="action" type="hidden" class="form-control input-md" value="filtro">';
            $html .= $o->button("{icon: search; caption: Pesquisar; hint: Pesquisa Avançada; size: normal; href: index.php?g=painel&gPage=".PESQUISAR."}");
            $html .= '</form></div>';
            $html .= $o->br();

            // Botões com ações possíveis...
        	$frm = new gForm("columns: 3");
            if ($_POST["action"] == "filtro") {
                /* TRATAR FILTRO */
                $sql = "SELECT * FROM " . $tabela;
                $rs = dbQuery($sql);
            } else {
                $sql = "SELECT * FROM " . $tabela;
                $rs = dbQuery($sql);
            }

            $html .= mostraTabelaPrincipal($rs);
            break;

        case PESQUISAR:
                $frm = new gForm();
                $frm->addFormMessage("Informe uma ou mais opções abaixo para busca...");
                $frm->add("{name: campo}");
                $html .= $frm->render($o);
            break;

        case PESQUISAR_RESULTADO:
            $html.=mostraTabelaPrincipal($rs);
            break;

        case NOVO:
            $frm=new gForm();
            $frm->addFormMessage("Informe uma ou mais opções abaixo para busca...");
            $frm->add("{name: campo}");
            $html.=$frm->render($o);
            break;

        case NOVO_SALVAR:
            // Trata os campos obtidos do formulário
            $campos = [];
            $campos['campo'] = gCleanField($_REQUEST['campo']);
            $campos['campo'] = intval($_REQUEST['campo']);
            $campos['campo'] = gDBDate($_REQUEST['campo']);
            $campos['campo'] = gDBDateTime($_REQUEST['campo']);
            $campos['campo'] = gDBFloat($_REQUEST['campo']);
            $campos['campo'] = gDBCheck($_REQUEST['campo']);
            redirect($o->page);
            break;
        case EXCLUIR:
            $sql = "DELETE FROM tabela WHERE id=".$gId;
            dbQuery($sql);
            redirect($o->page);
            break;
}

function mostraTabelaPrincipal($rs)
{
	global $o;

    if ($rs) {
        $html.=$o->tableBegin("big",true);
        $mtz = [];
        $mtz[] = "<-Opções";
        $mtz[] = "<-Descrição";
        $html .= $o->tableRow($mtz,"header");
        foreach ($rs as $row) {
            $mtz = [];
            $mtz[]="<-" . $o->button("{icon: folder-open; caption: Executar; hint: Editar informações; style: default; size: tiny; href: ". $o->page ."&gPage=1&gId=". $row["id"] ."}");
            $mtz[]="<-".$row['descricao'];
            $html.=$o->tableRow($mtz,"detail");
        }

        $html .= $o->tableEnd();
    } else {
        $html .= $o->msgInfo("Nenhum registro encontrado");
    }

    return ($html);
}
