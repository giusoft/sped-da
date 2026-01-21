<?php

include $gPath."gfw/inc/gChart.php";
include $gPath."res/_classes/classes.php";

if ($gParam['ACESSO']['ativo']==0 && $usrId>0)
{
	$html.=$o->msgTitle("Acesso desabilitado");
	$html.=$o->msgError("O administrador do sistema desabilitou o acesso ao mesmo pelos usuários, provavelmente para realizar alguma manutenção.<br>Aguarde alguns minutos e tente novamente.");
} else {
	$html.=$o->msgTitle("Bem-vindo!");
}

$hoje=date("Y-m-d");

// Mensagens
$sql="SELECT m.*,p.nome de
		FROM mensagens m
		LEFT JOIN pessoas p ON m.id_pessoas_de=p.id
		WHERE id_pessoas_para=".$usrId." AND m.lida=0";
$rs=dbQuery($sql);
if (count($rs)>0)
{
	$html.='<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Você possui mensagens não lidas</div>';
}

// ----------------- DASHBOARD -----------------

include_once $gPathDefault . "gDashboard.php";

$dash = new gDashboard($o);
$col = "<div style='padding: 2px; background-color: red'>Item 1</div>";
$graf = array(10,25,40,5,11);
$hint = "";
if ($_SESSION['usrId']<=2)
{
    if ($_SESSION['usrId']==1)
    {
        $hint.=$o->label("Root");
    } else {
        $hint.=$o->label("Administrador do sistema");
    }

} else {
    if ($_SESSION['usrClient']) {
        $hint .= $o->label("Cliente");
    } else {
        $hint .= $o->label("Colaborador");
    }
}

$filtroProprietario = "";
if ($_SESSION['usrClient']) // Se for cliente, filtra pelo seu código pra só mostrar suas OS
{
   	$filtroProprietario = "AND id_pessoas_proprietario=".$_SESSION['usrId'];
}

$osEntradaHoje = dbQuery("SELECT
		COUNT(id) total
	FROM
		programacao
	WHERE
		data_previsao >= CONCAT(CURRENT_DATE() + ' 00:00:00')
		AND data_previsao <= CONCAT(CURRENT_DATE() + ' 23:59:59') AND id_tipos_programacao IN (1,14,20,21,23,27,28) AND cancelada = 0 {$filtroProprietario}")[0]['total'];

$osEntradaHojeFaltando = dbQuery("SELECT
		COUNT(id) total
	FROM
		programacao
	WHERE
		data_previsao >= CONCAT(CURRENT_DATE() + ' 00:00:00')
		AND data_previsao <= CONCAT(CURRENT_DATE() + ' 23:59:59') AND executada=0 AND cancelada = 0 AND id_tipos_programacao IN (1,14,20,21,23,27,28) {$filtroProprietario}")[0]['total'];

$osSaidaHoje = dbQuery("SELECT
		COUNT(id) total
	FROM
		programacao
	WHERE
		data_previsao >= CONCAT(CURRENT_DATE() + ' 00:00:00')
		AND data_previsao <= CONCAT(CURRENT_DATE() + ' 23:59:59') AND id_tipos_programacao IN (2,3,22) AND cancelada = 0 {$filtroProprietario}")[0]['total'];
$osSaidaHojeFaltando = dbQuery("SELECT
		COUNT(id) total
	FROM
		programacao
	WHERE
		data_previsao >= CONCAT(CURRENT_DATE() + ' 00:00:00')
		AND data_previsao <= CONCAT(CURRENT_DATE() + ' 23:59:59') AND executada=0 AND id_tipos_programacao IN (2,3,22) AND cancelada = 0 {$filtroProprietario}")[0]['total'];

$osHoje = $osEntradaHoje + $osSaidaHoje;
$osHojeFaltando = $osEntradaHojeFaltando + $osSaidaHojeFaltando;


// Token
function gerarTokenAlexa($id)
{
    $numero = (int)$id;
    $mod = 1000000;

    // Chaves que mudam diariamente, baseadas no dia do ano e no ano.
    $chaveSoma = ((int)date('z') + 1) * 101;
    $chaveXOR = ((int)date('Y')) * 31;

    // --- Processo de Embaralhamento (4 Passos) ---
    // Cada passo é uma camada de "mistura" que é totalmente reversível.
    $numero = ($numero + $chaveSoma) % $mod;

    $numero = $numero ^ $chaveXOR;

    $numero = ($numero + $chaveSoma) % $mod;

    $numero = $numero ^ $chaveXOR;

    return str_pad($numero, 6, '0', STR_PAD_LEFT);
}

$token = gerarTokenAlexa($usrId);

// Notícias
$noticias = $o->tableBegin();
$sql = "SELECT V.placa, C.apelido, DATE(A.data_chegada) data_chegada
        FROM (SELECT data_chegada, id_veiculos, id_pessoas_cliente FROM veiculos_acessos WHERE data_chegada > DATE_SUB(NOW(), INTERVAL 15 DAY) AND cancelado=0 AND data_saida='0000-00-00 00:00:00') A
        INNER JOIN veiculos V ON A.id_veiculos=V.id
        INNER JOIN pessoas C ON A.id_pessoas_cliente=C.id
        WHERE V.placa<>'' AND V.placa<>'XXXXXXX'
        ORDER BY A.data_chegada ASC, V.placa";
$rsVeiculosEsperando = dbQuery($sql);
foreach ($rsVeiculosEsperando as $row)
{
    $mtz = array();
    $mtz[] = '<-' . substr(gDate($row['data_chegada']), 0, 5);
    $mtz[] = '<-' . $row['placa']
    	. ' (' . gDateTimeDiff($row['data_chegada'], date('Y-m-d H:i:s'), array('format' => 'hours'))['formatted'] . ')';
    $mtz[] = '<-<small>' . substr($row['apelido'], 0, 30) . "</small>";
    $noticias .= $o->tableRow($mtz, 'detail');
}

$noticias .= $o->tableEnd();

// Ocorrências recentes
if ($gParam['INTEGRACAO_WINTHOR']['ativo']) {
	$_SESSION['controlaLoteGA'] = 1;
	$sql = "SELECT O.*, T.descricao tipo, programacao.numero_cliente
			FROM ocorrencias O
			LEFT JOIN tipos_ocorrencias T ON O.id_tipos_ocorrencias=T.id
			LEFT JOIN programacao ON programacao.id = O.id_programacao
	        WHERE programacao.cancelada = 0
	        	AND (
	        			T.necessita_atuar = 0
	        			OR (T.necessita_atuar = 1 AND O.teve_atuacao = 0)
	        		)
			ORDER BY programacao.id";
	$rsOcorrencias = dbQuery($sql);
	$ocorrencias = '';
	if ($rsOcorrencias) {
	    $ocorrencias = $o->tableBegin("big", true);
	    $mtz = array();
	    $mtz[] = '~2<>Pedidos Cancelados Winthor';
	    $ocorrencias .= $o->tableRow($mtz, 'header');
	    $mtz = array();
	    $mtz[] = '<-Data';
	    $mtz[] = '<-Nº cliente';
	    $ocorrencias .= $o->tableRow($mtz, 'header');
	    foreach ($rsOcorrencias as $row) {
	        $mtz = array();
	        $mtz[] = '<-' . gDate($row['data_ocorrencia']);
	        $mtz[] = '<-' . linkParaOS($row['numero_cliente']);
	        $ocorrencias .= $o->tableRow($mtz, 'detail');
	    }
	    $ocorrencias .= $o->tableEnd().$o->br();
	}
} else {
	$intervalo = $gParam['USA_POSICAO_COMO_UMA']['ativo'] ? 120 : 5;
	$limite = $gParam['USA_POSICAO_COMO_UMA']['ativo'] ? 200 : 50;
	$sql = "SELECT O.*, T.descricao tipo
			FROM ocorrencias O
			LEFT JOIN tipos_ocorrencias T ON O.id_tipos_ocorrencias=T.id
	        WHERE DATE_SUB(now(), INTERVAL {$intervalo} DAY) <= O.data_ocorrencia AND (T.necessita_atuar = 0 OR (T.necessita_atuar = 1 AND O.teve_atuacao=0))
			ORDER BY O.data_ocorrencia DESC
	        LIMIT {$limite} ";
	$rsOcorrencias = dbQuery($sql);
	$ocorrencias = '';
	if (count($rsOcorrencias)) {
	    $ocorrencias = $o->tableBegin("big", true, true);
	    $mtz = array();
		$colspan = $gParam['USA_POSICAO_COMO_UMA']['ativo'] ? 3 : 2;
	    $mtz[] = '~'.$colspan.'<>Ocorrências';
	    $ocorrencias .= $o->tableRow($mtz, 'header');
	    $mtz = array();
	    $mtz[] = '<-Data';
	    $mtz[] = '<-Tipo';
	    if ($gParam['USA_POSICAO_COMO_UMA']['ativo']) {
	    	$mtz[] = '<-' . 'Item';
	    }
	    $ocorrencias .= $o->tableRow($mtz, 'header');
	    foreach ($rsOcorrencias as $row) {
	        $mtz = array();
	        $mtz[] = '<-' . substr(gDateTime($row['data_ocorrencia']),0,14);
	        $mtz[] = '<-' . $row['tipo'];
	        if ($gParam['USA_POSICAO_COMO_UMA']['ativo']) {
		    	$mtz[] = '<-' . gFieldById('itens_skus', $row['id_itens_skus'], 'codigo');
		    }
	        if ($row['id_tipos_ocorrencias']==11 || $row['id_tipos_ocorrencias']==12)
	        {
	            $ocorrencias .= $o->tableRow($mtz, 'danger');
	        } else {
	            $ocorrencias .= $o->tableRow($mtz, 'detail');
	        }

	    }
	    $ocorrencias .= $o->tableEnd().$o->br();
	}
}


/*
*************** CODIGO DESATIVADO POIS A TABELA UMAS SALDOS ESTA COM DADOS DESATUALIZADOS ***************
// Vencimentos

$sql = "SELECT * FROM (
        SELECT I.codigo, I.nome, S.data_validade, DATE_SUB(S.data_validade, INTERVAL I.shelf_life DAY) shelf_life, S.quantidade
        FROM (SELECT * FROM umas_saldos WHERE quantidade > 0 AND data_validade>'2018-01-01') S
        INNER JOIN itens_skus SK ON S.id_itens_skus
        INNER JOIN itens I ON SK.id_itens=I.id
        WHERE I.ativo=1 AND I.codigo<>'' AND I.nome<>'' AND I.shelf_life>0 AND DATE_SUB(S.data_validade, INTERVAL I.shelf_life DAY) <= DATE(now())
        LIMIT 50) F ORDER BY F.shelf_life;";
$rsSaldosVencendo = dbQuery($sql);

$vencimentos = '';
if (count($rsSaldosVencendo)) {
    $vencimentos = $o->tableBegin("big", true);
    $mtz = array();
    $mtz[] = '~2<>Vencimentos';
    $vencimentos .= $o->tableRow($mtz, 'header');
    $mtz = array();
    $mtz[] = '<-Data';
    $mtz[] = '<-Item';
    $vencimentos .= $o->tableRow($mtz, 'header');

    foreach ($rsSaldosVencendo as $row) {
        $mtz = array();
        $mtz[] = '<-' . gDate($row['shelf_life']);

        $msg = "<a href='index.php?g=umas&gPage=21&mostrar_cliente=1&com_subtotal=1&data_referencia=" . date("d-m-y") . "&codigo_sku=" . $row['codigo'] . "&agrupar=Item&id_proprietario=0&id_grupos=0&id_tipos=0&rua=0&lado=0&id_areas=0'>" . $row['codigo'] . "</a>";
        $mtz[] = '<-' . $msg;
        $vencimentos .= $o->tableRow($mtz, 'detail');
    }

    $vencimentos .= $o->tableEnd();
}
*/

/*
*************** CODIGO DESATIVADO POIS A INTEMARITIMA SEMPRE MUDA DE SERVIDORES GERANDO ERROS DE ACESSO NA TELA INICIAL ***************
$conexaoBdCliente = array(
	'name' => gVar('database.name'),
	'url'  => gVar('database.url'),
	'user' => gVar('database.user'),
	'password' => gVar('database.password')
);
if ($_SERVER['SERVER_ADDR']!='::1' && $_SERVER['SERVER_ADDR']!='127.0.0.1' && substr($_SERVER['REMOTE_ADDR'],0,3)!='192')
{
	gVar('database.name',"gadmin");
	gVar('database.url',"bd.giusoft.com.br");
	gVar('database.user',"web");
	gVar('database.password',"web");

	$agora = date("Y-m-d H:i:s");
	$sql = "SELECT c.*, t.descricao tipo, p.nome_interno produto
			FROM gadmin.comunicacao c
			LEFT JOIN gadmin.comunicacao_tipos t ON c.id_comunicacao_tipos=t.id
			LEFT JOIN gadmin.produtos p ON c.id_produtos=p.id
			WHERE c.ativa=1 AND c.aprovada=1 and c.data_inicio<'$agora' AND c.data_final>'$agora'
				AND c.id_comunicacao_tipos>1 AND c.id_comunicacao_tipos<5
				AND c.id_produtos IN (4,16)
			ORDER BY c.data_inicio desc limit 5";
	$rs = dbQuery($sql);
	if (count($rs)==0)
	{
		$sql = "SELECT c.*, t.descricao tipo, p.nome_interno produto
				FROM gadmin.comunicacao c
				LEFT JOIN gadmin.comunicacao_tipos t ON c.id_comunicacao_tipos=t.id
				LEFT JOIN gadmin.produtos p ON c.id_produtos=p.id
				WHERE c.ativa=1 AND c.aprovada=1
					AND c.id_comunicacao_tipos>1
					AND c.id_produtos IN (4,16)
				ORDER BY RAND() limit 1";
		$rs = dbQuery($sql);
	}
	foreach($rs as $row)
	{
		$cor = "info";
		switch($row['id_comunicacao_tipos'])
		{
			case 3:
				$cor = 'danger';
				break;
			case 4:
				$cor = 'warning';
				break;
		}
		$tags=$o->label($row['tipo'],$cor)." ".$o->label($row['produto']);
		$giusoft.=$dash->format("{size: default; style: $cor; title: ".autoencode($row['titulo'])."; subTitle: ".autoencode($row['subtitulo'])."; date: ".$row['data_cadastro']."; tags: $tags; }", autoencode($row['conteudo']));
		$giusoft.=$o->hr();
	}

	gVar('database.name', $conexaoBdCliente['name']);
	gVar('database.url', $conexaoBdCliente['url']);
	gVar('database.user', $conexaoBdCliente['user']);
	gVar('database.password', $conexaoBdCliente['password']);
}
*/

// ------------- Estrutura do dashboard -------------
$col1 = $dash->avatar("{id: $usrId; name: ".gShortName($usrName)." ".$o->label($gId,"success")."; token_alexa: $token; email: $usrEmail; phone: $usrPhone; hint: $hint; href: index.php?g=profile;}", $graf);
$col2 = $dash->text("{title: OS de entrada; hint: Faltando executar: ".$o->badge($osEntradaHojeFaltando).";value: $osEntradaHoje; }", $btnsPraticas);
$col3 = $dash->text("{title: OS de saída; hint: Faltando executar: ".$o->badge($osSaidaHojeFaltando).";value: $osSaidaHoje; }", $btnsTeoricas);
$col4 = $dash->text("{title: OS de hoje; hint: Faltando executar: ".$o->badge($osHojeFaltando).";value: $osHoje; }", $btnsTeoricas);

$dash->setColumnsWidth(1,1,1,1);
$dash->addRow($col1,$col2, $col3, $col4);

if (!$_SESSION['usrClient']) {
    $dash->setColumnsWidth(1,1,2);
    $col1 = $dash->card("{title: Alertas; titleStyle: danger}", $ocorrencias.$vencimentos);
    // $col3 = $dash->card("{}", $giusoft);
    $col2 = '';
    if ($rsVeiculosEsperando) {
		$col2 = $dash->card("{title: Veículos aguardando; titleStyle: warning}", $noticias);
    }
    $col3 = '';

	$dash->addRow($col1, $col2, $col3);

}

$html.=$dash->render();

