<?php

include_once $gPathDefault . "gUI.php";

define('QUESTIONARIO_DETALHADO' ,100);
define('QUESTIONARIO_RESUMIDO'  ,200);

if ($usrId > 1 && $gPage == 3 && $gId > 0) {
    /*
    Impede que usuários alterem o valor de parâmetros não editáveis.
    Isso DEVE ser feito antes da instanciação da classe gUI

    @author André Luiz em 18-05-2017 17:00
    */
    $sql = "SELECT editavel,valor, ativo FROM parametros WHERE id = " . $gId;
    $rs = dbQuery($sql);
    if (intval($rs[0]['editavel']) == 0) {
        $_GET['valor'] = $rs[0]['valor'];
        $_POST['valor'] = $rs[0]['valor'];
        $_REQUEST['valor'] = $rs[0]['valor'];

        $_GET['editavel'] = $rs[0]['editavel'];
        $_POST['editavel'] = $rs[0]['editavel'];
        $_REQUEST['editavel'] = $rs[0]['editavel'];
    }
}


if ($usrId > 2) {
    $html.=$o->msgDanger("Acesso negado a esta funcionalidade<br>Somente o administrador do sistema tem direito a acessar esta funcionalidade");
} else {
    if ($usrId == 1) {
        $ui = new gUI("{title: Parâmetros; columns: 2; table: parametros; permissions: SIU; ajax: false}");
        $ui->addDictionary("{name: editavel; fieldLabel: Editável}");
    } else {
        $ui = new gUI("{title: Parâmetros; columns: 2; table: parametros; permissions: SU; ajax: false}");
        $ui->addDictionary("{name: descricao; type: show}");
        $ui->addDictionary("{name: ajuda; type: show}");
        $ui->addDictionary("{name: editavel; fieldLabel: Editável; type: hidden}");
    }

    $ui->addButton("{icon: question; title: Gerar questionário completo; href: ".$o->page."&gPDFOrientation=L&gPage=".QUESTIONARIO_DETALHADO."}");
    $ui->addButton("{icon: question; title: Gerar questionário resumido; href: ".$o->page."&gPage=".QUESTIONARIO_RESUMIDO."}");
    $ui->run($o, $html);

    $sql = "SELECT * FROM parametros";
    $rsp = dbQuery($sql);
    $gParam = '';
    foreach ($rsp as $key => $value) {
        unset($value[0]);
        unset($value[1]);
        unset($value[2]);
        unset($value[3]);
        unset($value[4]);
        unset($value[5]);
        $gParam[$value['chave']]=$value;
    }

    $_SESSION['gParam']=$gParam;

}

if ($gPage == QUESTIONARIO_DETALHADO) {
    $o->PDFEnabled = true;
    $o->CSVEnabled = true;
    $o->XLSEnabled = true;
    $html .= $o->msgTitle("Parâmetros");
    $html .= $o->msgSubTitle("Questionário para parametrização do sistema");
    $sql = "SELECT * FROM parametros order by grupo,chave";
    $rs = dbQuery($sql);
    $html .= $o->tableBegin("big", true);
    $mtz = [];
    $mtz[] = "->Id";
    $mtz[] = "<-Grupo";
    $mtz[] = "<-Chave";
    $mtz[] = "<-Item";
    $mtz[] = "<>Ativar";
    $mtz[] = "<-Valor padrão";
    $mtz[] = "<-Resposta";
    $html .= $o->tableRow($mtz, "header");
    foreach ($rs as $row){
        $mtz = [];
        $mtz[]="->".$row["id"];
        $mtz[]="<-".$row["grupo"];
        $mtz[]="<-".$row["chave"];
        if ($_REQUEST['gPDF'] == 1) {
            $mtz[] = "<-".$row['descricao'];
            $mtz[] = "<>".($row["ativo"]==1?'[  ]<b>SIM</b>   [  ]não':'[  ]Sim   [  ]<b>NÃO</b>');
            $mtz[] = "<-".$row["valor"]." ";
        } else {
            $mtz[] = "<-".$row['descricao'];
            $mtz[] = "<><div style='display: block'>".($row["ativo"]==1?'[&nbsp;&nbsp;]<b>SIM</b>&nbsp;&nbsp;[&nbsp;&nbsp;]Não':'[&nbsp;&nbsp;]Sim&nbsp;&nbsp;[&nbsp;&nbsp;]<b>NÃO</b>').'</div>';
            $mtz[] = "<-".$o->small($row["valor"]).$o->br(2);
        }

        $mtz[] = "<-";

        $html .= $o->tableRow($mtz, "detail");
    }

    $html.=$o->tableEnd();

}

if ($gPage == QUESTIONARIO_RESUMIDO) {
    $o->PDFEnabled = true;
    $o->CSVEnabled = true;
    $o->XLSEnabled = true;
    $html .= $o->msgTitle("Parâmetros");
    $html .= $o->msgSubTitle("Questionário resumido para parametrização do sistema");

    $html.=$o->tableBegin("big", true);
    $mtz = [];
    $mtz[] = "<>Nº";
    $mtz[] = "<-Item";
    $mtz[] = "<>Ativar";
    $mtz[] = "<-Resposta";
    $html .= $o->tableRow($mtz, "header");
    $cnt = 0;
    $perguntas = [];
    $perguntas['Sistema'][] = "Precisa controlar o saldo por notas fiscais?";
    $perguntas['Sistema'][] = "Imprime usando impressora específica para etiquetas? Qual?";
    $perguntas['Sistema'][] = "Permite ao conferente executar qualquer Pedido (OS) ou só os associados a ele?";
    $perguntas['Sistema'][] = "Prefere executar as atividades por Pedido (OS) ou por Evento?";
    $perguntas['Sistema'][] = "Pretende utilizar o módulo de controle de Portaria?";
    $perguntas['Sistema'][] = "Pretende utilizar o módulo de controle de Containers?";

    $perguntas['Itens'][] = "Precisa controlar o nº serial dos itens?";
    $perguntas['Itens'][] = "Precisa controlar temperatura dos itens?";
    $perguntas['Itens'][] = "Precisa controlar metragem dos itens?";
    $perguntas['Itens'][] = "Usa regra de paletização de itens para a entrada?";
    $perguntas['Itens'][] = "Qual o formato de identificação de posições na estrutura?";
    $perguntas['Itens'][] = "Precisa montar kits a partir dos itens cadastrados?";

    $perguntas['Entrada'][] = "Cargas são contadas e etiquetadas imediatamente após o descarregamento?";
    $perguntas['Entrada'][] = "É mais importante descarregar rapidamente e liberar rapidamente o veículo?";
    $perguntas['Entrada'][] = "Assim que colar uma etiqueta, já pode ser posicionada?";
    $perguntas['Entrada'][] = "Sistema deve indicar o posicionamento na entrada?";
    $perguntas['Entrada'][] = "Qual o mínimo de contagens para aceitar a entrada (padrão é 1)?";
    $perguntas['Entrada'][] = "Aceita entrada com divergência da Nota Fiscal?";
    $perguntas['Entrada'][] = "Deve exigir a conferência de entrada antes de posicionar no armazém?";

    $perguntas['Saída'][] = "É necessário conferir antes de autorizar a saída?";
    $perguntas['Saída'][] = "É necessário conferir a saída minuciosamente?";
    $perguntas['Saída'][] = "Ao conferir já é realizada a saída automaticamente?";
    $perguntas['Saída'][] = "Ao separar já é realizada a saída automaticamente?";

    $perguntas['Inventário'][] = "Usa o saldo atual como primeira contagem?";
    $perguntas['Inventário'][] = "Qual o mínimo de contagens para validar cada posição (padrão é 2)?";
    $perguntas['Inventário'][] = "Usa o inventário completo (UMAs, posição, detalhes do item e quantidade)?";
    $perguntas['Inventário'][] = "Usa o inventário simples (UMAs e posições)?";

    $grupoAnterior = "";
    foreach ($perguntas as $grupo => $perguntasDoGrupo) {
        if ($grupo !== $grupoAnterior) {
            $grupoAnterior = $grupo;
            $mtz = [];
            $mtz[]="~4<>".$grupo;
            $html.=$o->tableRow($mtz, "detail");
        }

        foreach($perguntasDoGrupo as $pergunta) {
            $cnt++;
            $mtz = [];
            $mtz[] = "<>".$cnt;
            $mtz[] = "<-".$pergunta;
            if ($_REQUEST['gPDF'] == 1) {
                $mtz[] = "<>[  ]Sim   [  ]Não";
            } else {
                $mtz[] = "<><div style='display: block'>[&nbsp;&nbsp;]Sim&nbsp;&nbsp;[&nbsp;&nbsp;]Não</div>";
            }

            $mtz[] = "<-";

            $html .= $o->tableRow($mtz, "detail");

        }
    }

    $html.=$o->tableEnd();

}
