<?


define('INICIO'								,0);
define('PESQUISAR'							,10);
define('PESQUISAR_RESULTADO'				,11);

if (($_REQUEST['g']<>"login" && $_REQUEST['g']<>"" && $_REQUEST['g']<>"logout") && (intval($_SESSION['usrId'])==0 || intval($usrId)==0))
{
	redirect("index.php");
}


/**
* Classe geral para trabalho com as tabelas do sistema
*/
class Pagination
{
	public $numero_pagina;
	public $numero_registro_por_pagina;
	public $total_paginas;
	public $total_rs;
	public $iniciar;
	public $proxima_pagina;
	public $anterior_pagina;


	public function controlarQuantidadePaginas($sql, $qtdMinimaPaginas)
	{
		$this->qtdMinimaPaginas = $qtdMinimaPaginas;
		$totalRegistros = 0;
		if ($_REQUEST["gPagination"] >= 10 || !$qtdMinimaPaginas) {
			$this->qtdMinimaPaginas = 0;
			$totalRegistros = dbFastQuery("SELECT COUNT(id) ttl FROM ({$sql}) H");
		}

		return $totalRegistros;
	}


	public function addPagination($rs, $por_pagina)
	{
		$this->total_rs = $rs[0]['ttl'];
		$this->numero_registro_por_pagina = $por_pagina;

		if (isset($_REQUEST["gPagination"]))
		{
			$this->numero_pagina=intval($_REQUEST["gPagination"]);
		} else
		{
			$this->numero_pagina=1;
		}
		if ($this->numero_pagina>1)
		{
			$this->anterior_pagina=$this->numero_pagina-1;
		}
		if ($this->numero_pagina<$this->total_rs)
		{
			$this->proxima_pagina=$this->numero_pagina+1;
		}

		$this->total_paginas = $this->qtdMinimaPaginas;
		if (!$this->qtdMinimaPaginas) {
			$this->total_paginas = ceil($this->total_rs / $this->numero_registro_por_pagina);
		}

		$this->iniciar = ($this->numero_pagina-1) * $this->numero_registro_por_pagina;

		if ($this->iniciar<0)
		{
			$this->iniciar=0;
		}
		return ($this);
	}

	public function render($json='{}')
	{
		global $o;
		if (
				$_REQUEST['gPDF']==0 &&
				$_REQUEST['gXLS']==0 &&
				$_REQUEST['gDOC']==0 &&
				$_REQUEST['gCSV']==0
			)
		{
			$jarr=cssDecode($json);
			$id=isset($jarr["id"])
					? $jarr["id"]
					: "g";

			$size=isset($jarr["size"])
				? $jarr["size"]
				: "sm";

			$style=isset($jarr["style"])
				? $jarr["style"]
				: "";
			if ($this->total_rs>0 || $this->qtdMinimaPaginas) {
				$html="<nav aria-label='Page navigation {$id}-nav' style={$style}>";
				$html.="<ul class='pagination pagination-{$size}'>";
				$frm.="<form action='' id='{$id}-formRequest' method='POST'>";
				foreach ($_REQUEST as $key => $value) {
					$frm .= "<input type='hidden' name='{$key}' value='{$value}'/>";
				}

				$frm.="<input type='hidden' name='gPagination' id='{$id}-gPagination' value=''/>";
				$frm.="</form>";
				$html.=$frm;
				if ($this->numero_pagina==1)
				{
					$html.="<li style='cursor:pointer;' class='page-item disabled'><a class='page-link' href='javascript:;'>&laquo;</a></li>";
				} else
				{
					$rota=$o->page."&gPagination=".$this->anterior_pagina;
					$html.="<li class='page-item'><a id='{$id}-btnAnterior' class='page-link' style='cursor:pointer;'>&laquo;</a></li>";
					$js="$('#{$id}-btnAnterior').on('click', function () {
						$('#{$id}-formRequest').attr('action', '".$rota."');
						$('#{$id}-gPagination').val('".$this->anterior_pagina."');
						$('#{$id}-formRequest').submit();
					})";
					$o->addJavascript($js);
				}

				if ($this->numero_pagina-5>0)
				{
					$iniciar=($this->numero_pagina-5);
				} else
				{
					$iniciar=1;
				}
				$finalizar=$this->numero_pagina+5;
				$cnt=0;
				for ($i=$iniciar; $i<=$this->total_paginas; $i++)
				{
					  $cnt++;
					  $rota=$o->page."&gPagination=".$i;
					  if ($i==$this->numero_pagina)
					  {
					  		$html.='<li class="page-item active"><a id="'.$id.'-btnPagination'.$i.'" class="page-link">'.$i.'</a></li>';
					  } else
					  {
					  		$html.='<li style="cursor:pointer;" class="page-item"><a id="'.$id.'-btnPagination'.$i.'" class="page-link">'.$i.'</a></li>';
					  }

					  $js="$('#{$id}-btnPagination".$i."').on('click', function () {
								$('#{$id}-formRequest').attr('action', '".$rota."');
								$('#{$id}-gPagination').val('".$i."');
								$('#{$id}-formRequest').submit();
							})";
					  $o->addJavascript($js);
					  if ($cnt==10)
					  {
					  		break;
					  }
				}

				if ($this->numero_pagina==$this->total_paginas)
				{
					$html.="<li class='page-item disabled'><a class='page-link' href='javascript:;'>&raquo;</a></li>";
				} else
				{
					$rota=$o->page."&gPagination=".$this->proxima_pagina;
					$html.="<li class='page-item'><a class='page-link' id='{$id}-btnProximo'>&raquo;</a></li>";
					$js="$('#{$id}-btnProximo').on('click', function () {
						$('#{$id}-formRequest').attr('action', '".$rota."');
						$('#{$id}-gPagination').val('".$this->proxima_pagina."');
						$('#{$id}-formRequest').submit();
					})";
					$o->addJavascript($js);
				}
				$html.="</ul>";
				$html.="</nav>";
				return ($html);
			}
		}
	}
}

class Persistencia
{
	public $tabela      = "";
	public $ordenacao   = "";
	public $filtro      = "";
	public $agrupamento = "";
	public $erros       = array();
	public $tipo_os_saida	 = array(2,3,4,5,7,22,25,26,24);
	public $tipo_os_entrada  = array(1,19,20,21,23,27,28);
	public $pagination;
	public $porPagina;
	public $depurar = false;

	public function __construct($json)
	{

	}

	public function defineErros($erro)
	{
		$this->erros[]=$erro;
	}

	public function obtemErros()
	{
		return ($this->erros);
	}

	function obtemQueryConsulta()
	{
		return("SELECT * FROM ".$this->tabela);
	}

	function obtemQueryExclusao($id)
	{
		return("DELETE FROM ".$this->tabela." WHERE id=".$id);
	}

	function insere($campos, &$gId)
	{
		return($gId = dbInsert($this->tabela, $campos, true));
	}

	function modifica($campos, $id)
	{
		return(dbUpdate($this->tabela, $campos, $id));
	}
	function remove($id)
	{
		$sql = $this->obtemQueryExclusao($id);
	}

	/**
	 * Obtém os registros para a operação atual
	 * (usado para os arquivos da pasta 'operacao')
	 *
	 * @param string $filtro
	 * @param string $ordenacao
	 * @param string $agrupamento
	 * @return array Registros
	 */
	function obtemRegistros($filtro = "", $ordenacao = "", $agrupamento = "", $naoLimitar = '')
	{
		global $gParam;
		$sql=$this->obtemQueryConsulta();
		$filtros="";
		$ordenacoes="";
		$agrupamentos="";
		if ($this->filtro<>"") { $filtros[]=$this->filtro; }
		if ($filtro<>"") { $filtros[]=$filtro; }

		if ($this->ordenacao<>"") { $ordenacoes[]=$this->ordenacao; }
		if ($ordenacao<>"") { $ordenacoes[]=$ordenacao; }

		if ($this->agrupamento<>"") { $agrupamentos[]=$this->agrupamento; }
		if ($agrupamento<>"") { $agrupamentos[]=$agrupamento; }

		if (is_array($filtros)) {
			$sql.=" WHERE ".implode(" AND ", $filtros);
		}

		if (is_array($agrupamentos)) {
			$sql.=" GROUP BY ".implode(", ", $agrupamentos);
		}
		if (is_array($ordenacoes)) {
			$sql.=" ORDER BY ".implode(", ", $ordenacoes);
		}
		if ($gParam["PAGINACAO"]["ativo"]==1) {
			$this->porPagina=$gParam["PAGINACAO"]["valor"];
		}
		if (!$naoLimitar) {
			if ($this->porPagina>0) {
				$this->pagination = new Pagination();
				$totalRegistros = $this->pagination->controlarQuantidadePaginas($sql, $qtdMinimaPaginas = 10);
				$pagination = $this->pagination->addPagination($totalRegistros, $this->porPagina);
				$sql .= " LIMIT {$pagination->iniciar}, $pagination->numero_registro_por_pagina";
			} else {
				if ($gParam['LIMITAR_VISUALIZACAO']['ativo']) {
					$sql .= " LIMIT " . $gParam['LIMITAR_VISUALIZACAO']['valor'];
				} else {
					$sql .= " LIMIT 500";
				}
			}
		}

		return dbFastQuery($sql);
	}
	function obtemCamposDoFormulario(&$frm, $registroAtual="", $proximaPagina="")
	{
		global $proximaPagina, $gId, $gPage, $o;
		if ($proximaPagina=="")
		{
			$proximaPagina=$gPage+1;
		}
	}

	public function gerarTabelaDivergencia($usarContagens = 0)
	{
		global $o, $gId, $gParam, $usrId, $html;

		if ($usarContagens) {
			$sql = "
				SELECT
					notas.numero,
					itens_skus.codigo AS codigo_sku,
					IFNULL(SUM(contagens_umas_itens.quantidade), 0) AS quantidade_conferida,
					programacao_itens.quantidade AS quantidade_programada,
					programacao_itens.quantidade_conferida AS quantidade_conferida_os,
					programacao_itens.id AS id_programacao_itens,
					programacao_itens.id_notas_itens,
					programacao.id_tipos_entrada
				FROM
					programacao
				JOIN programacao_itens ON
					programacao_itens.id_programacao = programacao.id
				LEFT JOIN contagens_umas_itens ON
					contagens_umas_itens.id_programacao = programacao.id
					AND contagens_umas_itens.id_programacao_itens = programacao_itens.id
				LEFT JOIN itens_skus ON
					itens_skus.id = programacao_itens.id_itens_skus
				LEFT JOIN notas_itens ON
					notas_itens.id = contagens_umas_itens.id_notas_itens
				LEFT JOIN notas ON
					notas.id = notas_itens.id_notas
				LEFT JOIN contagens_umas ON
					contagens_umas.ativo = 1
					AND contagens_umas.id = contagens_umas_itens.id_contagens_umas
				WHERE
					programacao.id = {$gId}
				GROUP BY
					programacao_itens.id
				HAVING
					IFNULL(programacao_itens.quantidade, 0) <> IFNULL(SUM(contagens_umas_itens.quantidade), 0)
				UNION ALL
				SELECT
					notas.numero,
					itens_skus.codigo AS codigo_sku,
					SUM(contagens_umas_itens.quantidade) AS quantidade_conferida,
					0 AS quantidade_programada,
					0 AS quantidade_conferida_os,
					0 AS id_programacao_itens,
					0 AS id_notas_itens,
					programacao.id_tipos_entrada
				FROM
					programacao
				LEFT JOIN contagens_umas_itens ON
					contagens_umas_itens.id_programacao = programacao.id
					AND contagens_umas_itens.id_programacao_itens = 0
				LEFT JOIN itens_skus ON
					itens_skus.id = contagens_umas_itens.id_itens_skus
				LEFT JOIN notas_itens ON
					notas_itens.id = contagens_umas_itens.id_notas_itens
				LEFT JOIN notas ON
					notas.id = notas_itens.id_notas
				LEFT JOIN contagens_umas ON
					contagens_umas.ativo = 1
					AND contagens_umas.id = contagens_umas_itens.id_contagens_umas
				WHERE
					programacao.id = {$gId}
					AND contagens_umas.ativo = 1
				GROUP BY
					contagens_umas_itens.id_itens_skus";
		} else {
			$sql = "
				SELECT
					notas.numero,
					itens_skus.codigo AS codigo_sku,
				 	IFNULL(SUM(umas_itens.quantidade), 0) AS quantidade_conferida,
					programacao_itens.quantidade AS quantidade_programada,
					programacao_itens.quantidade_conferida AS quantidade_conferida_os,
					programacao_itens.id AS id_programacao_itens,
					programacao_itens.id_notas_itens,
					programacao.id_tipos_entrada
				FROM programacao
				LEFT JOIN programacao_itens ON programacao.id = programacao_itens.id_programacao 
				LEFT JOIN umas_itens ON
					umas_itens.id_programacao = programacao.id
					AND umas_itens.id_programacao_itens = programacao_itens.id
					AND umas_itens.cancelada = 0
					AND	umas_itens.bloqueada = 0
					AND umas_itens.tipo = '+'
					AND umas_itens.id_umas_origem = 0
					AND umas_itens.id_tipos_operacao <> 7
				LEFT JOIN itens_skus ON
					itens_skus.id = programacao_itens.id_itens_skus
				LEFT JOIN notas_itens ON
					notas_itens.id = umas_itens.id_notas_itens
				LEFT JOIN notas ON
					notas.id = notas_itens.id_notas
				WHERE
					programacao.id = {$gId}
				GROUP BY
					programacao_itens.id
				HAVING
					IFNULL(programacao_itens.quantidade, 0) <> IFNULL(SUM(umas_itens.quantidade), 0)
				UNION ALL
				SELECT
					notas.numero,
					itens_skus.codigo AS codigo_sku,
				 	IFNULL(SUM(umas_itens.quantidade), 0) AS quantidade_conferida,
					0 AS quantidade_programada,
					0 AS quantidade_conferida_os,
					0 AS id_programacao_itens,
					0 AS id_notas_itens,
					programacao.id_tipos_entrada
				FROM programacao
				JOIN umas_itens ON
					umas_itens.id_programacao = programacao.id
					AND umas_itens.id_programacao_itens = 0
					AND umas_itens.cancelada = 0
					AND umas_itens.bloqueada = 1
					AND umas_itens.tipo = '+'
					AND umas_itens.id_tipos_operacao = 8
				LEFT JOIN itens_skus ON
					itens_skus.id = umas_itens.id_itens_skus
				LEFT JOIN notas_itens ON
					notas_itens.id = umas_itens.id_notas_itens
				LEFT JOIN notas ON
					notas.id = notas_itens.id_notas
				WHERE
					programacao.id = {$gId}
				GROUP BY
					umas_itens.id_itens_skus";
		}

		$rs = dbFastQuery($sql);

		if (!$rs) {
			return;
		}

		$msg = "Esta OS está com divergências.";

		if (
			(
				!in_array('Acesso total', $_SESSION['permissionsNames'])
				|| !in_array('Programação',  $_SESSION['permissionsNames'])
			)
			&& $usrId > 2
		) {
			if ($gParam['IMPEDIR_ENTRADA_COM_DIVERGENCIA']['ativo']) {
				$msg .= " Comunique à supervisão para tratar as divergências imediatamente para que a OS seja executada";
			}

			$html .= $o->msgInfo($msg);
			$html .= $o->button('{icon: arrow-left; caption: Voltar; href: ' . $o->page . '&gPage=' . EXECUTAR_7 . '&gId=' . $gId . '&uma=' . $_REQUEST['uma'] . "&manterUma=" . gDBCheck($_REQUEST["manterUma"]) . ";}");
			$html .= $o->button('{icon: list-ol; caption: Contagens; style: primary; href: ' . $o->page . '&gPage=' . EXECUTAR_7 . '&gId=' . $gId . '&uma=' . $_REQUEST['uma'] . "&manterUma=" . gDBCheck($_REQUEST["manterUma"]) . ";}");

			return true;
		}

		$tabela = $o->msgSubTitle("Divergências encontradas");

		$tabela .= $o->tableBegin("big", true);

		$mtz = array();
		$mtz[] = "<- Código";
		$mtz[] = "-> Nº nota";
		$mtz[] = "<- Status";
		// $mtz[] = "<- Lote";
		// $mtz[] = "<> Data validade";
		// $mtz[] = "<> Data fabricação";
		$mtz[] = "-> Quant. programada";
		$mtz[] = "-> Quant. conferida";
		$tabela .= $o->tableRow($mtz, "header");

		foreach ($rs as $key => $row) {

			if ($_SESSION['usrId'] == 1) {
				$detalhesRoot = '<br>' . $o->small(
					'ID PI: ' . $row['id_programacao_itens']
					. '<br>' . 'ID NI: ' . $row['id_notas_itens']
				);
			}

			$status = 'Sobra';

			if ($row['quantidade_conferida'] < $row['quantidade_programada']) {
				$status = 'Falta';
			}

			$mtz = array();
			$mtz[] = "<-" . $row['codigo_sku'] . $detalhesRoot;
			$mtz[] = "->" . $row['numero'];
			$mtz[] = "<-" . $status;
			// $mtz[] = "<-" . $row['lote'];
			// $mtz[] = "<>" . gDate($row['data_validade']);
			// $mtz[] = "<>" . gDate($row['data_fabricacao']);
			$mtz[] = "->" . gFloat($row['quantidade_programada']);

			if (!$usarContagens && $row['id_tipos_entrada'] == 3) { // 3 = Entrada com UMA Virgem
				$mtz[] = "->" . gFloat($row['quantidade_conferida_os']);
			} else {
				$mtz[] = "->" . gFloat($row['quantidade_conferida']);
			}

			$tabela .= $o->tableRow($mtz, "detail");
		}

		$tabela .= $o->tableEnd();

		return $tabela;
	}


	public function calcularPorcentagem($total, $parcial, $mostrarParcial = true)
	{
		if ($mostrarParcial) {
			return  ((int) $parcial) . ' (' . gFloat(($parcial * 100) / $total) . '%)';
		}
		return gFloat(($parcial * 100) / $total);
	}


	public function carregarPrioridadesReserva($idPessoasProprietario)
	{
		$idPessoasProprietario = (int) $idPessoasProprietario;
		$sql = "
			SELECT *
			FROM pessoas_prioridades_reservas
			WHERE id_pessoas = {$idPessoasProprietario}
				AND ativo = 1
			ORDER BY ordem";
		$rs = dbFastQuery($sql);
		if ($rs[0]['id']) {
			$gPrioridadesReservas = array_filter($rs, function($prioridade) {
				return $prioridade['ativo'] && $prioridade['id_prioridade'] == 1; //id_prioridade = 1 eh "Ordem de prioridades", ativa e desativa esta funcionalidade
			});
			if ($gPrioridadesReservas) {
				$_SESSION['gPrioridadesReservas'] = $rs;
			}
		}
	}
}


function mostraErros($titulo, $erros = array())
{
	global $o;
	$msg  = $titulo."<br><br>";
	if (count($erros)>0)
	{
		$msg.=$o->ul($erros);
	}
	return ($msg);
}

function obtemIdEmpresa($idProprietario=0)
{
	global $EMPRESA;

	if (
		$EMPRESA=="logic"
		&& $idProprietario==334
	) {
		return (2);
	}

	if (intval($_SESSION['armazemAtualId'])>0) {
		return ($_SESSION['armazemAtualId']);
	}

	return (dbQuery("SELECT id FROM armazens limit 1")[0]["id"]);
}


function obtemProprietario($id, $campo="*")
{
	$sql="SELECT
				{$campo}
		  FROM pessoas
		  WHERE id='{$id}' AND cliente='1';
		 ";

	return (dbQuery($sql)[0]);
}

function obtemSKU($id, $separador="•", $campo="")
{
	if (empty($campo))
	{
		$campo="";
	} else
	{
		$campo=$campo.",";
	}

	$sql="SELECT
			{$campo}
        	CONCAT(CONCAT_WS(' {$separador} ',ISK.codigo, I.nome,U.descricao), ' com ', CAST(ISK.quantidade as SIGNED)) descricao_sku
          FROM itens_skus ISK
          LEFT JOIN itens I on ISK.id_itens = I.id
       	  LEFT JOIN unidades U on U.id = ISK.id_unidades
          WHERE ISK.id='{$id}'
          GROUP BY ISK.id, I.nome";
    return (dbQuery($sql)[0]);
}



function userLog($details="")
{
	global $usrId, $usrEquip, $gMenuParameters;
	$usrId = (int) $usrId;
	$request=base64_encode(serialize($_REQUEST));
	$sql = "INSERT INTO gfw_log
	(id_gfw_users,id_equip, id_gfw_menus,date,full_link,request,details) VALUES
	($usrId, $usrEquip, ".intval($gMenuParameters['id_gfw_menus']).", NOW(),'".$gMenuParameters['full_link']."','$request','$details')";
	dbFastQuery($sql);
}

function formataDescricaoItemSKU($codigo, $nome, $unidade, $quantidade)
{
	global $o;
	$descricao="";
	$sep = "<br>";
	if ($_REQUEST['gPDF']==1)
	{
		$sep = " - ";
	}
	if (!empty($codigo))
	{
		if (
			$_REQUEST['gPDF']
			|| $_REQUEST['gXLS']
			|| $_REQUEST['gDOC']
		 	|| $_REQUEST['gCSV']
		 	|| $_REQUEST['g']=='programacao'
		) {
			$descricao.=$codigo.$sep;
		} else {
			$descricao.=$o->big($codigo).$sep;
		}

	}

	if (!empty($nome))
	{
		$descricao.=$nome.$sep;
	}

	if (!empty($unidade))
	{
		$descricao.=$unidade." com ";
	}

	if (!empty($quantidade))
	{
		$descricao.=intval($quantidade);
	}
    return ($descricao);
}

function formataDescricaoData($pessoa, $data)
{
	$descricao="";
	if (!empty($pessoa))
	{
		$descricao.=$pessoa;
	}

	if (!empty($data))
	{
		$descricao.=$data;
	}
}

function isBase64($texto)
{
	return ($texto === base64_encode(base64_decode($texto)));
}

function decodificarObservacao($texto) 
{
	return (isBase64($texto)) ? base64_decode($texto) : $texto;
}

function separarString($string, $limit)
{
	$str = wordwrap($string, $limit, "*");
    $str = explode("*", $str);
    
    return $str;
}

function verificarMovimentacoesUmas($idProgramacao)
{
	$sql = 
		"SELECT UI.id_umas AS id_umas, max(UI.id) AS id_umas_itens
		FROM programacao PR 
		LEFT JOIN umas_itens UI ON UI.id_programacao = PR.id 
		LEFT JOIN notas_itens NI ON NI.id = UI.id_notas_itens
		WHERE UI.id_programacao = {$idProgramacao}
		AND UI.cancelada = 0
		GROUP BY UI.id_umas
		ORDER BY UI.id DESC";
	$ids = dbQuery($sql);
	
	foreach ($ids as $idUmas) {
		$sql = 
		"SELECT U.codigo_barras AS codigo_barras, PR.os
		FROM umas_itens UI
		LEFT JOIN umas U ON U.id = UI.id_umas
		LEFT JOIN programacao PR ON PR.id = UI.id_programacao
		WHERE UI.cancelada = 0
			AND PR.id <> {$idProgramacao}
			AND PR.id_tipos_programacao <> 1
			AND PR.ativo = 1
			AND UI.id > {$idUmas['id_umas_itens']}
			AND U.id = {$idUmas['id_umas']}
		GROUP BY U.id, PR.id";
		$movUmas = dbQuery($sql);
		if ($movUmas) {
			foreach ($movUmas as $movUma) {
				$umasMovimentadas[]=$movUma;
			}
		}
	}
	return $umasMovimentadas;
}

function excluirIndicesNumericos($array) {
    foreach (array_keys($array) as $chave) {
        if (is_numeric($chave)) {
            unset($array[$chave]);
        }
    }
    return $array;
}

function obtemModalConfirmacao($idModal, $titulo, $btnCancelar, $btnConfirmar, $url)
{
  global $o, $gId;
  $content  = $titulo . "<br/><br/>";
  $content .= "<div class='modal-footer'>
                 <input type='hidden' name='idExcluir' id='idExcluir' value=''/>
                 ".$o->button("{id: ". $btnCancelar ."; title: Cancelar; size: medium; target: '#';}")."
                 ".$o->button("{id: ". $btnConfirmar ."; title: Confirmar; style:primary; size: medium; target: _new; href: $url; }")."
               </div>";
  $html.= $o->modal("{title: Confirmação; size: medium; confirm: false; cancel: false; content: $content; name: $idModal;}");
  $javascript = "
     function opemModal (id, idModal)
     {
     	var idModal=(idModal)?idModal:'".$idModal."';
     	$('#' + idModal).modal('show');
        $('#idExcluir').val(id);
     }

     $('#". $btnCancelar ."').on('click', function (e) {
        e.preventDefault();
        $('#".$idModal."').modal('hide');
     });
     $('#". $btnConfirmar ."').on('click', function (e) {
     	e.preventDefault();
        showWait();
     	$('#". $btnConfirmar ."').attr('disabled', 'disabled');
     	setTimeout(function () {
     		$('#". $btnConfirmar ."').removeAttr('disabled');
     	}, 1000);
        var idExcluir = $('#idExcluir').val();
        var gId=".$gId.";
        if (gId>0)
        {
        	var rota = '". $url ."&gIdEnd=' + idExcluir;
        } else
        {
        	var rota = '". $url ."';
        	var existegId=rota.indexOf('gId');
        	if (existegId=='-1')
        	{
        		rota+='&gId='+idExcluir+'&gIdEnd='+idExcluir;
        	} else
        	{
        		rota=rota.replace('gId=0', 'gId='+idExcluir);
        	}
        }
        location.href = rota;
     });
     ";
  $o->addJavascript($javascript);
  return ($html);
}

function jsButtonVoltar()
{
	global $o;
	$js = "
		    $('.fa-arrow-left')[0].parentNode.setAttribute('class', 'hidden-print btn btn-default pull-left');
		    $('#gSubmitButton').attr('style', 'margin-left: 0.2%;');
		  ";
	$o->addJavascript($js);
}

/* Calcula diferença entre duas datas e retorna uma string com a informação */
function calculaDiferencaDatas($entrou, $saiu)
{
	$entrou = new \DateTime($entrou);
    $saiu = new \DateTime($saiu);
    $intervalo=$entrou->diff($saiu);
    $diferenca="";
    if ($intervalo->days>0)
        $diferenca.="{$intervalo->days}d e ";

    if ($intervalo->h>0)
        $diferenca.="{$intervalo->h}h: ";

    if ($intervalo->i>0)
        $diferenca.="{$intervalo->i}m";
    return ($diferenca);
}

/*  -------------- Operações com UMA -------------- */
class UMA extends Persistencia
{
	public $id_umas          = 0;
	public $id_itens         = 0;
	public $uma              = "";
	public $uma_itens        = "";
	public $os               = "";
	public $item             = "";
	public $campos           = "";
	public $umas             = array();
	public $erros            = array();
	public $umas_necessarias = array();
	public $areasDesteItem   = array();
	public $idUmas           = array();
	public $jarr;
	public $temOSdeInventarioAtiva = true;



	function criaUMA($campos)
	{
		global $usrId, $armazemAtualId;
		$usrId = intval($usrId);
		$atualizaCodigo=false;
		$campos['id_armazens'] = $armazemAtualId;
		if ($campos['codigo_barras']=='') {
			$campos['codigo_barras']=uniqid();
			$atualizaCodigo=true;
		}

		if ($campos['id_posicoes'] && $campos['posicionada']) {
			$campos['data_posicionamento'] = date('Y-m-d H:i:s');
		}
		$id = dbFastInsert('umas', $campos, true);
		if($atualizaCodigo){
			$cBarras = formataUMA($id);
			$sql="UPDATE umas SET codigo_barras='".$cBarras."' WHERE id=$id";
			dbQuery($sql);
			$this->uma=$cBarras;
		}
		$campos['descricao'] = "Criada";
		 // C) Criação P) Posicionamento, T) Transferência S) Separação
		$campos['tipo'] = "C";
		$this->salvaUMAMovimentos($id, $campos, false);
		return($id);
	}

	function num2OS($numero, $id_armazens = 0, $ano = 0)
	{
		$id_armazens = intval($id_armazens);

		if ($id_armazens == 0) {
			$id_armazens = 1;
		}
		if ($ano == 0) {
			$ano = date("Y");
		}
		if ($ano < 100) {
			$ano = 2000 + $ano;
		}
		$sql = "SELECT prefixo, codigo_barras FROM armazens WHERE id=$id_armazens";
		$rs = dbQuery($sql);

		$os = strtoupper($rs[0]['prefixo']) . $rs[0]['codigo_barras'] . str_pad($numero, 10, "0", STR_PAD_LEFT) . "/" . $ano;

		return($os);
	}

	/**
	 * Gera um novo número de OS para uma programação.
	 * @return Void
	 */
	function novaOS()
	{
		global $id_armazens;
		// Calcula novo numero de OS
		$ano = date("Y");
		$sql = "SELECT numero FROM contadores WHERE id_armazens=$id_armazens and ano=$ano ORDER BY numero desc";
		$rs = dbQuery($sql);
		$numero = intval($rs[0]['numero']);
		$numero++;
		if ($numero == 1) {
			// Novo ano
			$sql = "INSERT into contadores (id_armazens,ano,numero) VALUES ($id_armazens,$ano,$numero)";
			dbQuery($sql);
		} else {
			// Ano já existe, incrementa numero
			$sql = "UPDATE contadores SET numero=$numero WHERE id_armazens=$id_armazens and ano=$ano";
			dbQuery($sql);
		}
		$os = $this->num2OS($numero, $id_armazens);
		return($os);
	}

	function salvaUMAMovimentos($id, $campos, $returnId = true)
	{
		global $usrId;
		$usrId = intval($usrId);
		$flds = array();
		$id = (int) $id;
		// Busca registro anterior pra copiar os dados...
		$sql = "SELECT id_posicoes, id_pessoas_proprietario, lote, data_fabricacao, data_validade FROM umas_movimentos WHERE id_umas={$id} ORDER BY id DESC LIMIT 1";
		$rst = dbFastQuery($sql)[0];
		if ($rst) {
			$flds['id_posicoes'] = $rst['id_posicoes'];
			$flds['id_pessoas_proprietario'] = $rst['id_pessoas_proprietario'];
			$flds['lote'] = $rst['lote'];
			$flds['data_fabricacao'] = $rst['data_fabricacao'];
			$flds['data_validade'] = $rst['data_validade'];
		}

		// Se necessário muda alguns campos...
		$flds['id_umas'] = $id;
		$flds['id_umas_para'] = (int) $campos['id_umas_para'];
		$flds['tipo'] = gCleanField($campos['tipo']);
		$flds['data'] = date('Y-m-d H:i:s');
		$flds['id_pessoas'] = $campos['id_pessoas'] ?: $usrId;

		if (isset($campos['id_posicoes']))
		{
			$flds['id_posicoes'] = $campos['id_posicoes'];
		}
		if (isset($campos['lote']))
		{
			$flds['lote'] = $campos['lote'];
		}
		if (isset($campos['id_pessoas_proprietario']))
		{
			$flds['id_pessoas_proprietario'] = $campos['id_pessoas_proprietario'];
		}
		if (isset($campos['data_fabricacao']))
		{
			$flds['data_fabricacao'] = $campos['data_fabricacao'];
		}
		if (isset($campos['data_validade']))
		{
			$flds['data_validade'] = $campos['data_validade'];
		}
		if (isset($campos['data_fabricacao_para']))
		{
			$flds['data_fabricacao_para'] = $campos['data_fabricacao_para'];
		}
		if (isset($campos['data_validade_para']))
		{
			$flds['data_validade_para'] = $campos['data_validade_para'];
		}
		$flds['descricao'] = $campos['descricao'];
		return(dbInsert('umas_movimentos', $flds,  $returnId));
	}

	function salvaUMAPosicionamento($idUma,$id_posicoes_atual){
		$mtz = array();
		$mtz['id_umas']  	= (int) $idUma;
		$mtz['id_pessoas']  = (int) $_SESSION['usrId'];
		$mtz['data']        = date("Y-m-d H:i:s");
		$mtz['id_posicoes'] = (int) $id_posicoes_atual;
		dbFastInsert("umas_posicionamentos",$mtz);
	}


	function linkParaOS($os="")
	{
		return(linkParaOS($os));
	}

	function linkParaUMA($uma="")
	{
		return(linkParaUMA($uma));
	}

	function linkParaPosicao($posicao="")
	{
		return(linkParaPosicao($posicao));
	}

	function ativa($id_umas)
	{

	}

	function desativa($id_umas)
	{

	}

	function obtemSaldoUMA($id, $reservada=false, $separada=false)
	{
		$where = "U.id=".$id;
		if ($reservada>0)
			$where.=" AND UI.reservada=1";
		if ($separada>0)
			$where.=" AND UI.separada=1";
		return ($this->obtemUMAsComSaldo($where,false, $prioridade = 0, $orderBy = "",
			$priorizarPaleteAberto = 0, $groupBy = "", $priorizarPaleteFechado = 0, $having = "",
			$limit = "", $addGroupBy = ", UI.valor"));
	}

	function obtemSaidaUMA($idUma, $idProgramacao = 0, $idItensSkus = 0, $limit = 1)
	{
		// Função para obter a saída de uma UMA especifica
		$where = array();
		$where[] = "U.id IN ({$idUma})";
		$where[] = "UI.bloqueada = 0";
		$where[] = "UI.cancelada = 0";
		$where[] = "UI.avariada = 0";
		$where[] = "UI.separada = 1";
		$where[] = "UI.tipo = '-'";
		if ($idItensSkus > 0) {
			$where[] = "UI.id_itens_skus IN ({$idItensSkus})";
		}
		if ($idProgramacao > 0) {
			// Se for passado o id da programação pegar apenas a saída daquela programação,
			// caso contrário pegarar todas as saídas da UMA
			$where[] = "(UI.id_programacao IN ({$idProgramacao}))";
		}
		// Pegar a quantidade exata que deu saída
		$where = implode(" AND ", $where);

		$sql = "
			SELECT U.ativo,
			 	SUM(UI.quantidade) quantidade,
			 	UI.data,
			 	UI.id_itens_skus,
			 	UI.id_umas
			FROM umas_itens UI
			JOIN umas U ON UI.id_umas=U.id
			JOIN itens_skus SK ON SK.id = UI.id_itens_skus
			WHERE {$where}
			GROUP BY U.id, SK.id";
		if ($limit) {
			$sql .= " LIMIT " . $limit;
			return dbFastQuery($sql);
		}

		$rs = dbFastQuery($sql);
		$saidas = array();
		foreach ($rs as $row) {
			$saidas[$row['id_itens_skus'] . '_' . $row['id_umas']] = $row;
		}

		return $saidas;
	}


	function existeSaida($id, $select="P.id_tipos_programacao")
	{
		$x = 0;
		if (is_numeric($id))
		{
			$x=$id;
		} else
		{
			$x=formataUMA($id);
		}
		$sql="SELECT
				{$select}
			FROM umas_itens UI
			LEFT JOIN programacao P ON P.id = UI.id_programacao
			LEFT JOIN umas U ON U.id = UI.id_umas
			where U.codigo_barras = '{$x}'";
		$rs=dbQuery($sql);
		$existeSaida=false;
		foreach ($rs as $row)
		{
			if ($row["id_tipos_programacao"]==2)
			{
				$existeSaida = true;
				break;
			}
		}
		return ($existeSaida);
	}

	/**
	 * Busca as quantidades e produtos que foram executadas para uma determinada programação
	 */
	function buscaQuantidadeExecutadaProgramacao($idProgramacao)
	{
		$sql="SELECT tipo FROM programacao WHERE id=$idProgramacao";
		$rs=dbQuery($sql)[0];

		$sql="SELECT
					SUM(UI.quantidade) quantidade,
					DATE(UI.data) data,
					SK.id id_sku,
					SK.codigo codigo_sku,
					I.codigo codigo_item
				FROM umas_itens UI
				INNER JOIN umas U ON U.id = UI.id_umas
				INNER JOIN itens_skus SK ON SK.id = UI.id_itens_skus
				INNER JOIN itens I ON I.id = SK.id_itens
				WHERE UI.cancelada=0 AND UI.id_programacao=$idProgramacao";
			if($rs['id_tipos_programacao']== 1 ) // Entrada
				$sql.="AND UI.separada =0 AND UI.tipo='+' ";
			else
				$sql.="AND UI.separada=1 AND UI.tipo='-' ";
		$sql.="GROUP BY UI.id_itens_skus";
		$rs=dbQuery($sql);

		return $rs;
	}

	function buscaDadosUMA($id, $ativo="", $limit = 0)
	{
		$sql="SELECT
				U.*, TIU.descricao as tipoUMA, PE.apelido, PE.nome, A.descricao armazem,
				AA.descricao area_posicao,
				AP.descricao area_posicao_posicionar,
				P.id_pessoas_proprietario, PA.codigo_barras posicao, PP.codigo_barras posicao_posicionar
				FROM umas U
				LEFT JOIN programacao P ON P.id = U.id_programacao
				LEFT JOIN posicoes PA ON U.id_posicoes=PA.id
				LEFT JOIN posicoes PP ON U.id_posicoes_posicionar=PP.id
				LEFT JOIN areas AA ON AA.id = PA.id_areas
				LEFT JOIN areas AP ON AP.id = PP.id_areas
				LEFT JOIN armazens A ON A.id = U.id_armazens
				LEFT JOIN tipos_umas TIU ON TIU.id = U.id_tipos_umas
				LEFT JOIN pessoas PE ON PE.id = P.id_pessoas_proprietario AND PE.cliente='1'
		";

		if (is_numeric($id))
		{
			$sql.=" WHERE U.id='{$id}'";
		} else
		{
			$sql.=" WHERE ('{$id}' <> '' AND (U.codigo_barras='".formataUMA($id)."' OR U.codigo_externo='{$id}'))";
		}
		if ($ativo<>"")
		{
			$sql.= " AND U.ativo='{$ativo}'";
		}
		$limit = (int) $limit;
		if ($limit) {
			$sql .= ' LIMIT '. $limit;
		}

		return (dbQuery($sql));
	}


	function buscaUMA($id_umas, $id_tipos_umas=0, $validarPosicaoFixa = 0)
	{
		global $gParam, $gId;
		if (
			$this->os['id_tipos_programacao'] == 8
			&& $gParam['USA_POSICAO_COMO_UMA']['ativo']
			&& $validarPosicaoFixa
		) {
			$sql = "
				SELECT
					umas.id AS id_umas,
					posicoes.id AS id_posicoes
				FROM
					umas
				JOIN posicoes ON
					posicoes.id = umas.id_posicoes
				WHERE
					posicoes.ativo = 1
					AND umas.codigo_barras = posicoes.codigo_barras
					AND umas.codigo_barras = '{$id_umas}'";
			$rs  = dbQuery($sql);
			if (!$rs) {
				$this->erros[] = "Posição de buffer informada não é uma posição fixa"; // ou seja, nao possui uma UMA associada aquela posicao
				return;
			}
		}

		$flt = "";
		$txt = "";
		if ($id_tipos_umas>0)
		{
			if ($id_tipos_umas==4 || $id_tipos_umas==3)
			{
				$flt = " AND (id_tipos_umas=3 OR id_tipos_umas=4)";
				$txt = "[Separação ou Expedição]";
			} else {
				$flt = " AND id_tipos_umas=".$id_tipos_umas;
				$txt = "[".gFieldById("tipos_umas", $id_tipos_umas,"descricao")."]";
			}
		}
		if (is_numeric($id_umas))
		{
			// Checa se existe...
			$sql = "SELECT id FROM umas WHERE id = $id_umas" . $flt;
		} else {
			$sql = "SELECT id FROM umas
				WHERE (codigo_barras='".formataUMA($id_umas)."' OR codigo_externo='".$id_umas."')".$flt . " LIMIT 1";
		}
		$rs = (int) dbFastQuery($sql)[0]['id'];
		if (!$rs) {
			$this->erros[] = "UMA {$txt} não encontrada: {$id_umas}";
		}
		return $rs;
	}


	function obtemUMA($umaInformada, $criaSeNaoAchar = false, $buscarNaTabelaPrincipal = true)
	{
		global $gId;
		$this->uma = '';
		$this->uma_itens = '';
		$uma = formataUMA($umaInformada);
		if ($buscarNaTabelaPrincipal)
		{
			$tabela       = "umas";
			$tabela_itens = "umas_itens";
		} else {
			$tabela       = "contagens_umas";
			$tabela_itens = "contagens_umas_itens";
		}

		$sql = "SELECT * FROM ".$tabela." WHERE (codigo_barras='" . $uma . "' OR codigo_externo='" . $umaInformada . "') AND ( '{$uma}' <> '' OR '{$umaInformada}' <> '')";
		$rs = dbQuery($sql);
		if (count($rs)==0 && $criaSeNaoAchar)
		{
			$flds = '';
			$flds['codigo_barras'] = $uma;
			$flds['ativo'] = 1;
			$flds['id_tipos_umas'] = 1;
			$flds['id_posicoes'] = 0 ;
			$flds['data'] = date('Y-m-d H:i:s');
			$flds['contagem'] = $this->contagem;
			$flds['id_programacao'] = $gId;
			$flds['id_armazens'] = $this->os['id_armazens'];
			$id = dbInsert('umas', $flds, true);
			$sql = "SELECT * FROM ".$tabela." WHERE id='".$id."'";
			$rs = dbQuery($sql);
		} else {
			$sql = "SELECT * FROM ".$tabela_itens." WHERE id_".$tabela."=".intval($rs[0]['id']);
			$this->umas_itens = dbQuery($sql);
		}
		$this->uma = $rs[0];
		return (intval($rs[0]['id']));
	}

	function obtemUMAS($idProgramacao)
	{
		$sql="
			 SELECT
			 	U.*, PP.id id_posicoes, PP.codigo_barras codigo_barras_posicao, TU.descricao, A.descricao as armazen, PU.id as idProgramacaoUMA
			 FROM programacao_umas PU
			 INNER JOIN umas U ON U.id = PU.id_umas
			 LEFT JOIN posicoes PP ON U.id_posicoes = PP.id
			 LEFT JOIN tipos_umas TU ON TU.id = U.id_tipos_umas
			 LEFT JOIN armazens A ON A.id = U.id_armazens
			 WHERE PU.id_programacao='$idProgramacao';
		";
		return (dbQuery($sql));
	}

	function zerarUMA($idUma,$id_sku=0)
	{
		$where=array();
		$where[]="(U.id='".$idUma."')";
		$where[]="(UI.cancelada='0')";
		if($id_sku)
			$where[]="(SK.id = $id_sku)";
		$where=implode(" AND ", $where);
		$saldos=$this->obtemUmasComSaldo($where);

		foreach ($saldos as $saldo)
		{
			$i=$this->preparaCamposDoItem($saldo["id"], $saldo);
			$i["quantidade"]=-($i["quantidade"]);
			$i["tipo"]=($i["tipo"]=="+") ? "-" : "+";
			$i["id_pessoas_proprietario"]=$saldo["id_pessoas_proprietario"];
			$i["id_armazens"]=intval($_SESSION["armazemAtualId"]);
			$i["peso_liquido"]=$sku["peso_liquido"];
			$i["peso_bruto"]=$sku["peso_bruto"];
			$i["m2"]=$sku["m2"];
			$i["m3"]=$sku["m3"];
			$i["valor"]=$saldo["valor_nota"];
			$i["id_programacao"]=0;
			$i["id_programacao_itens"]=0;
			$i["inventario"]=1;
			$i["faturar"]=0;
			dbInsert("umas_itens", $i);
		}
		atualizarAtivacaoUMA(array_column($saldos, 'id'));

	}


	public function verificarSeEstaInventariando($codigoPosicaoOuUma)
	{
		$codigo = gCleanField($codigoPosicaoOuUma);
		$sql = "
			SELECT programacao.os
			FROM umas
			JOIN posicoes ON posicoes.id = umas.id_posicoes
			JOIN programacao_inventario_posicoes ON programacao_inventario_posicoes.id_posicoes = posicoes.id
			JOIN programacao ON programacao_inventario_posicoes.id_programacao = programacao.id
			WHERE (posicoes.codigo_barras = '{$codigo}' OR umas.codigo_barras = '{$codigo}')
			    AND programacao.ativo = 1
			    AND programacao.executada = 0
			    AND programacao.cancelada = 0";
		return dbQuery($sql);

	}


    /**
     * Atualiza o saldo da tabela umas_saldos calculando as movimentações em umas_itens
     */
    function atualizaSaldoUMA($id_umas)
    {
    	atualizarAtivacaoUMA($id_umas);
        if ($id_umas > 0) {
            $sql = "SELECT * FROM umas_saldos WHERE id_umas=$id_umas";
            $umasSaldos = dbFastQuery($sql);
            $saldos = $this->obtemUMAsComSaldo("U.id=$id_umas AND UI.cancelada=0", false);
            foreach ($saldos as $saldo)
            {
                // Verifica se já existe e está correto
                $jaTemIgual = false;
                foreach($umasSaldos as $umas)
                {
                    if (
                        $umas['quantidade']==$saldo['quantidade'] &&
                        $umas['id_itens_skus']==$saldo['id_itens_skus'] &&
                        $umas['lote']==$saldo['lote'] &&
                        $umas['data_fabricacao']==$saldo['data_fabricacao'] &&
                        $umas['data_validade']==$saldo['data_validade'] &&
                        $umas['reservada']==$saldo['reservada'] &&
                        $umas['separada']==$saldo['separada'] &&
                        $umas['avariada']==$saldo['avariada'] &&
                        $umas['bloqueada']==$saldo['bloqueada']
                        )
                    {
                        $jaTemIgual = true;
                        break;
                    }
                }
                if (!$jaTemIgual)
                {
                    // Apaga primeiro (se houver)
                    $sql = "DELETE FROM umas_saldos WHERE id_umas=$id_umas AND id_itens_skus=".$saldo['id_itens_skus'].
                            " AND lote='{$saldo['lote']}' AND data_fabricacao='{$saldo['data_fabricacao']}'
                            AND data_validade='{$saldo['data_validade']}' AND reservada='{$saldo['reservada']}'
                            AND separada='{$saldo['separada']}' AND avariada='{$saldo['avariada']}'
                            AND bloqueada='{$saldo['bloqueada']}'";
                    dbFastQuery($sql);
                    if ($saldo['quantidade'] > 0)
                    {
                        // Insere com valor correto
                        $cmps = array();
                        $cmps['id_umas'] = $saldo['id'];
                        $cmps['id_itens_skus'] = $saldo['id_itens_skus'];
                        $cmps['lote'] = $saldo['lote'];
                        $cmps['data_validade'] = $saldo['data_validade'];
                        $cmps['data_fabricacao'] = $saldo['data_fabricacao'];
                        $cmps['quantidade'] = $saldo['quantidade'];
                        $cmps['reservada'] = $saldo['reservada'];
                        $cmps['separada'] = $saldo['separada'];
                        $cmps['avariada'] = $saldo['avariada'];
                        $cmps['bloqueada'] = $saldo['bloqueada'];
                        dbFastInsert('umas_saldos', $cmps);
                    }
                }
            }
        }
    }

    /**
     * Versão do método obtemUMAsComSaldo usando a tabela umas_saldos ao invés de umas_itens (para melhor performance)
     *
     * Atenção - Não retorna campos e valores:
     * - Notas fiscais
     * - Programação
     * - Pesos e Metragens de umas_itens
     */
    function obtemUMAsComSaldoSemCalcular($where="", $soAtivas=true, $prioridade=0, $orderBy="", $priorizarPaleteAberto=0, $groupBy="", $priorizarPaleteFechado=0, $having="", $limit="", $addGroupBy="", $select="")
	{
		global $gParam,$gPrioridadesReservas;

		if ($where<>"")
		{
			$where = "AND (".$where.")";
		}
		if ($soAtivas)
		{
			$ativas = "U.ativo=1 ";
			$ONativas = " AND U.ativo=1 ";
		}
		$agruparPorCliente="P.apelido,";
		$agrupaPorDataValidade = "UI.data_validade,";
		$agrupaPorDataFabricacao = "UI.data_fabricacao,";
		$agruparPorNota="";
		if ($gParam['PERFIL_FABRICANTE']['ativo'])
		{
			$agruparPorCliente="";
			$agrupaPorDataFabricacao = "";
			$agrupaPorDataValidade = "";
		}
		if ($gParam['USA_DATA_FABRICACAO']['ativo']==0)
		{
			$agrupaPorDataFabricacao = "";
		}
		if ($gParam['USA_DATA_VALIDADE']['ativo']==0)
		{
			$agrupaPorDataValidade = "";
		}
		$temDataPosicionamento = false;
		if (empty($select))
		{
			$select_default=" U.id,
					U.ativo,
					U.codigo_barras,
					U.codigo_externo,
					U.data_saida,
					U.conferida_saida,
					U.indivisivel,
					U.id_programacao,
					U.id_armazens,
					U.paletizada,
					U.filmada,
					U.fumigada,
					U.produzida,
					U.incompleta,
					TU.descricao tipo_uma,
					PP.id id_posicoes,
					PP.rua,
					PP.picking,
					PP.codigo_barras posicao, U.posicionada,
					PP.id_tipos_posicoes,
					U.data,
					UI.id_itens_skus,
					SK.codigo,
					SK.codigo_barras codigo_barras_sku,
					I.id id_itens,
					I.shelf_life,
					I.nome item,
					I.descricao item_descricao,
					A.descricao area,
					A.codigo local,
					PA.codigo_barras posicao_posicionar,
					PA.picking posicionar_picking,
					AA.descricao area_posicionar,
					AA.codigo area_posicionar_codigo,
					P.apelido proprietario,
					PJ.cnpj,
					P.id id_pessoas_proprietario,
					SK.quantidade quantidade_sku,
					D.descricao unidade,
					D.sigla unidade_sigla,
					UI.lote,
					UI.data_fabricacao,
					UI.data_validade,
					SUM(SK.peso_liquido*UI.quantidade) peso_liquido,
					SUM(SK.peso_bruto*UI.quantidade) peso_bruto,
					SK.comprimento/100*SK.largura/100*UI.quantidade m2,
					SK.altura/100*SK.largura/100*SK.comprimento/100*UI.quantidade m3,
					CEILING(SUM(UI.quantidade)/SK.palete_lastro)*SK.altura altura_palete,
					U.imobilizada,
					UI.reservada,
					UI.separada,
					UI.avariada,
					UI.bloqueada,
					SK.palete_lastro*SK.palete_altura capacidade_palete,
					SUM(UI.quantidade) quantidade,
					SUM(UI.quantidade)*SK.quantidade quantidade_un,
					PR.numero_cliente ,
					PR.os os,
					U.data_posicionamento
					";
					$temDataPosicionamento = true;
		} else
		{
			$select_default=" {$select} ";
		}

		$sql = "SELECT
					{$select_default}
				FROM umas U
				LEFT JOIN umas_saldos UI ON (U.id = UI.id_umas $ONativas)
				LEFT JOIN tipos_umas TU ON U.id_tipos_umas=TU.id
				LEFT JOIN itens_skus SK ON UI.id_itens_skus=SK.id
				LEFT JOIN itens I ON I.id=SK.id_itens
				LEFT JOIN pessoas P ON (P.id=I.id_pessoas_proprietario AND P.cliente=1)
				LEFT JOIN pessoas_juridicas PJ ON (P.id=PJ.id_pessoas)
				LEFT JOIN unidades D ON SK.id_unidades=D.id
				LEFT JOIN posicoes PP ON U.id_posicoes=PP.id
				LEFT JOIN areas A ON PP.id_areas=A.id
				LEFT JOIN posicoes PA ON U.id_posicoes_posicionar=PA.id
				LEFT JOIN areas AA ON PA.id_areas=AA.id
				LEFT JOIN armazens ON armazens.id = U.id_armazens
				WHERE $ativas
				{$where}
				GROUP BY";

		if ($groupBy<>"")
		{
			$sql.=" {$groupBy} ";
		} else
		{
			$sql.= " U.id, U.ativo, U.codigo_barras,U.codigo_externo,
					U.conferida_saida,
					U.id_programacao,
					U.id_armazens,
					PP.codigo_barras,U.posicionada,
					PA.codigo_barras,
					U.data,
					UI.id_itens_skus,
					SK.codigo,
					I.shelf_life,
					I.id,
					I.nome,
					A.descricao,
					$agruparPorCliente
					SK.quantidade,
					D.descricao,
					UI.lote,
					$agrupaPorDataFabricacao
					$agrupaPorDataValidade
					$agruparPorNota
					UI.reservada,
					UI.separada,
					UI.avariada,
					UI.bloqueada
					{$addGroupBy} ";
		}

		if ($having<>"")
		{
			$sql.=" HAVING {$having}";
		} else
		{
			if ($soAtivas)
			{
				$sql.=" HAVING SUM(UI.quantidade)>0 ";
			}
		}






		if (empty($orderBy))
		{
			// Ordem das UMAs para o caso de separação
			if (isset($_SESSION['gPrioridadesReservas']))
			{
				$gPrioridadesReservas = $_SESSION['gPrioridadesReservas'];
			}
			$usarMetodoAntigo = false;


			// Verifica se foi ativada a opção de prioridades de reserva no cadastro da empresa (proprietário)
			// Em caso positivo, usa a metodologia a seguir ($usarMetodoAntigo == false)
			if (count($gPrioridadesReservas)>1)
			{
				$ordens = array();
				foreach($gPrioridadesReservas as $prioridadeReserva)
				{
					if ($prioridadeReserva['ativo'] || $prioridadeReserva['id_prioridade']==1)
					{

						switch($prioridadeReserva['id_prioridade'])
						{
							case 1: // Ativar processamento destas ordens de prioridade
								if ($prioridadeReserva['ativo'])
								{
									$usarMetodoAntigo = false;
								} else {
									$usarMetodoAntigo = true;
								}
								break;

							case 2: // Área de picking
								$ordens[] = "PP.picking DESC";
								break;

							case 3: // Prioridade do cadastro do item
								$p='';
								switch ($prioridade)
								{
									case 1: //FIFO - First In, First Out
										$p="DATE(U.data)";
									break;
									case 2: //FEFO - First Expires, First Out (o primeiro q vence é o primeiro q sai)
										$p="UI.data_validade, DATE(U.data)";
									break;
									case 3: //LIFO - Last in, First Out
										$p="DATE(U.data) DESC";
									break;
									case 4: // IGNORAR
										$p="UI.data_fabricacao";
									break;
									default:
										$p="UI.data_fabricacao";
									break;
								}
								if ($p<>"")
								{
									$ordens[] = $p;
								}
								break;

							case 4: // Menor número de prédio
								$ordens[] = "PP.rua, PP.predio, PP.andar";
								break;

							case 5: // Maior número de prédio
								$ordens[] = "PP.rua, PP.predio DESC, PP.andar";
								break;

							case 6: // Palete aberto
								$ordens[] = "SUM(UI.quantidade)";
								break;

							case 7: // Palete fechado
								$ordens[] = "SUM(UI.quantidade) DESC";
								break;

							case 8: // Palete não posicionado
								$ordens[] = "(U.id_posicoes=0) DESC";
								break;
						}
					}
				}
				if (!$usarMetodoAntigo)
				{
					if (count($ordens))
					{
						$sql.=" ORDER BY ".implode(",",$ordens);
					} else {
						// Se ativou a prioridade no cadastro do cliente, mas não permitiu nada,
						// então usa o método antigo mesmo...
						$usarMetodoAntigo = true;
					}
					//gDR($ordens);gDR($sql);exit;
				}

			} else {
				$usarMetodoAntigo = true;
			}


			if ($usarMetodoAntigo)
			{
				$sql.=" ORDER BY (U.id_posicoes=0)";

				// Ordem de separação
				// Depois de escolher pela data, seleciona a UMA que tem menos itens
				// (ou que provavelmente já houve remoção parcial)
				if ($priorizarPaleteAberto)
				{
					$fltPaleteAberto = "";
				} else
				{
					$fltPaleteAberto = " DESC";
				}

				$fltPaleteFechado="";
				if ($priorizarPaleteFechado)
				{
					$fltPaleteFechado= " SUM(UI.quantidade) DESC, ";
				}

				// A última condição (U.id DESC) quer dizer pra pegar o último palete posicionado
				// Depois de consideradas todas as regras

				switch ($prioridade)
				{
					case 1: //FIFO - First In, First Out
						$sql.=",{$fltPaleteFechado} DATE(U.data),SUM(UI.quantidade) ".$fltPaleteAberto;
					break;
					case 2: //FEFO - First Expires, First Out (o primeiro q vence é o primeiro q sai)
						$sql.=",{$fltPaleteFechado} UI.data_validade, DATE(U.data), SUM(UI.quantidade) ".$fltPaleteAberto;
					break;
					case 3: //LIFO - Last in, First Out
						$sql.=",{$fltPaleteFechado} DATE(U.data) DESC, SUM(UI.quantidade) ".$fltPaleteAberto;
					break;
					case 4: // IGNORAR
						$sql.=",{$fltPaleteFechado} UI.data_fabricacao, SUM(UI.quantidade) {$fltPaleteAberto}";
					break;
					default:
						$sql.=",{$fltPaleteFechado} UI.data_fabricacao, SUM(UI.quantidade) ".$fltPaleteAberto;
					break;
				}
				$sql.=", PP.rua, PP.predio, PP.andar, ";
				if ($gParam['PERFIL_FABRICANTE']['ativo'])
				{
					if($temDataPosicionamento)
					$sql.=" data_posicionamento DESC, ";

				} else {
					if($temDataPosicionamento)
					$sql.=" data_posicionamento DESC, ";

				}
				$sql.="P.apelido, I.nome";
				$sql.=", A.prioridade, PP.codigo_barras, U.codigo_barras ";
			}
		} else
		{
			// Ordenação personalizada
			$sql.=" ORDER BY {$orderBy}";
		}

		if ($limit<>"")
		{
			$sql.=" LIMIT {$limit}";
		}


		if (
			$gParam['PAGINACAO_RELATORIO_SALDO_UMAS']['ativo']
			&& $_REQUEST['g'] == 'umas'
			&& $_REQUEST['gPage'] == '21'
		) {
			if($gParam["PAGINACAO"]["ativo"]==1){
				$porPagina = $gParam["PAGINACAO"]["valor"];
			}
			if($porPagina > 0 && !$limit){
				$sql_ttl="SELECT count(id_umas) ttl FROM (".$sql.") H";
				$this->pagination=$this->pagination->addPagination(dbQuery($sql_ttl), $porPagina);
				$sql.=" LIMIT {$this->pagination->iniciar}, {$this->pagination->numero_registro_por_pagina}";
			}
		}
		$rs = dbQuery($sql);

		return($rs);
	}


	function obtemHistoricoUMA($idUma=0,$mostraProgramacao=false)
	{
		global $usrId;

		$extra = 'UI.cancelada=0 AND ';
		if ($usrId<=2)
		{
			$extra = '';
		}

		if ($usrId <= 2) {
			$addSelect = " pessoas_cancelou.apelido AS apelido_pessoa_cancelou, tipos_operacao.descricao AS tipo_operacao, ";
		}

		$sql = "SELECT
					UI.*,
					O.os,
					TP.descricao tipo_programacao,
					SK.codigo,
					I.nome item,
					P.apelido criou,
					SK.quantidade quantidade_sku,
					D.sigla unidade_sigla,
					D.descricao unidade,
					PP.apelido,
					N.id AS id_notas,
					N.numero numero_nf,
					{$addSelect}
					A.descricao direcionar
				FROM umas_itens UI
				LEFT JOIN notas_itens NI ON NI.id = UI.id_notas_itens
				LEFT JOIN notas N ON N.id = NI.id_notas
				LEFT JOIN itens_skus SK ON UI.id_itens_skus=SK.id
				LEFT JOIN itens I ON I.id=SK.id_itens
				LEFT JOIN areas A ON A.id=UI.id_areas_direcionar
				LEFT JOIN pessoas P ON (P.id=UI.id_pessoas_criou AND P.cliente=0)
				LEFT JOIN unidades D ON SK.id_unidades=D.id ";

				if($mostraProgramacao && $usrId <= 2)
					$sql.=" LEFT JOIN programacao O ON ABS(UI.id_programacao)=O.id ";
				else
					$sql.=" LEFT JOIN programacao O ON UI.id_programacao=O.id ";

				if ($usrId <= 2) {
					$sql .= "
						LEFT JOIN pessoas pessoas_cancelou ON UI.id_pessoas_cancelou = pessoas_cancelou.id
						LEFT JOIN tipos_operacao ON tipos_operacao.id = UI.id_tipos_operacao ";
				}

				$sql.=" LEFT JOIN tipos_programacao TP ON TP.id = O.id_tipos_programacao
				LEFT JOIN pessoas PP ON (PP.id = UI.id_pessoas_proprietario AND PP.cliente=1)
				WHERE $extra UI.id_umas='{$idUma}'
				ORDER BY UI.data, UI.id";
		return(dbQuery($sql));
	}


	public function obtemUMAsComSaldo(
		$where = "", $soAtivas = true, $prioridade = 0, $orderBy = "",
		$priorizarPaleteAberto = 0, $groupBy = "", $priorizarPaleteFechado = 0, $having = "",
		$limit = "", $addGroupBy = "", $select = "", $outrosParametros = array()
	) {
		global $gParam, $gPrioridadesReservas;

		############################ SELECT ############################
		if ($select <> "") {
			$selectDefault = " {$select} ";
			$temDataPosicionamento = false;
		} else {
			$selectDefault = " U.id,
				U.ativo,
				U.codigo_barras,
				U.codigo_externo,
				U.data_saida,
				UI.id_umas_origem,
				U.conferida,
				U.conferida_saida,
				U.indivisivel,
				U.id_programacao,
				U.id_armazens,
				UI.id_armazens id_armazens_umas_itens,
				U.paletizada,
				U.filmada,
				U.fumigada,
				U.produzida,
				U.incompleta,
				U.liberada,
				U.qualidade,
				U.data_critica,
				TU.descricao tipo_uma,
				PP.id id_posicoes,
				PP.rua,
				UI.id_programacao id_programacao_umas_itens,
				PP.picking,
				PP.codigo_barras posicao, U.posicionada,
				PP.id_tipos_posicoes,
				U.data,
				UI.id_itens_skus,
				NF.id id_notas,
				NF.numero numero_nf,
				NF.data_emissao data_emissao_nf,
				SK.codigo,
				SK.codigo_barras codigo_barras_sku,
				I.id id_itens,
				I.critico item_critico,
				I.shelf_life,
				I.nome item,
				I.descricao item_descricao,
				I.picking_quantidade_minima,
				A.reservavel,
				A.descricao area,
				A.codigo local,
				PA.codigo_barras posicao_posicionar,
				PA.picking posicionar_picking,
				AA.descricao area_posicionar,
				AA.codigo area_posicionar_codigo,
				P.apelido proprietario,
				PJ.cnpj,
				P.id id_pessoas_proprietario,
				SK.quantidade quantidade_sku,
				D.descricao unidade,
				D.sigla unidade_sigla,
				UI.lote,
				UI.data_fabricacao,
				UI.data_validade,
				UI.peso_liquido un_peso_liquido,
				UI.peso_bruto un_peso_bruto,
				UI.m2 un_m2,
				UI.m3 un_m3,
				SUM(SK.peso_liquido*UI.quantidade) peso_liquido,
				SUM(SK.peso_bruto*UI.quantidade) peso_bruto,
				SK.comprimento/100*SK.largura/100*UI.quantidade m2,
				SK.altura/100*SK.largura/100*SK.comprimento/100*UI.quantidade m3,
				CEILING(SUM(UI.quantidade)/SK.palete_lastro)*SK.altura altura_palete,
				U.imobilizada,
				UI.id_programacao_itens,
				UI.reservada,
				UI.separada,
				UI.avariada,
				UI.bloqueada,
				UI.faturar,
				SK.palete_lastro*SK.palete_altura capacidade_palete,
				MIN(UI.data) data_primeiro_movimento,
				MAX(UI.data) data_ultimo_movimento,
				SUM(UI.quantidade) quantidade,
				SUM(UI.quantidade)*SK.quantidade quantidade_un,
				UI.valor valor_nota,
				UI.id_notas_itens,
				PR.numero_cliente ,
				GROUP_CONCAT(DISTINCT PR.os) os,
				SK.id_unidades,
				U.data_posicionamento";

			if ($outrosParametros['retornarPosicaoSaida']) {
				$selectDefault .= "
					,PD.id id_saida_posicionar,
					PD.codigo_barras saida_posicao";
				$addJoin = "LEFT JOIN posicoes PD ON PD.id = (SELECT id FROM posicoes WHERE id_areas=PR.id_areas_direcionar LIMIT 1)";
			}
			$temDataPosicionamento = true;
		}

		if ($outrosParametros['addSelect']) {
			$selectDefault .= $outrosParametros['addSelect'];
		}

		$addJoin .= ' ' . $outrosParametros['addJoin'];

 		############################ WHERE ############################
		if ($where <> "") {
			$where = "AND (" . $where . ")";
		}

		if ($outrosParametros['prazoMinimo']) {
			$where .= " AND ((TIMESTAMPDIFF(DAY, NOW(), UI.data_validade) + 1) >= " . (int) $outrosParametros['prazoMinimo'] .") ";
		}

		if ($soAtivas) {
			$ativas = "U.ativo = 1 AND ";
		}

		############################ CONSULTA ############################
		$sql = "SELECT
					{$selectDefault}
				FROM umas U
				LEFT JOIN umas_itens UI ON (U.id = UI.id_umas)
				LEFT JOIN itens_skus SK ON UI.id_itens_skus = SK.id
				LEFT JOIN itens I ON I.id = SK.id_itens
				LEFT JOIN unidades D ON SK.id_unidades = D.id
				JOIN pessoas P ON (P.id = UI.id_pessoas_proprietario)
				LEFT JOIN pessoas_juridicas PJ ON (P.id = PJ.id_pessoas)
				LEFT JOIN posicoes PP ON U.id_posicoes = PP.id
				LEFT JOIN areas A ON PP.id_areas = A.id
				LEFT JOIN posicoes PA ON U.id_posicoes_posicionar = PA.id
				LEFT JOIN areas AA ON PA.id_areas = AA.id
				LEFT JOIN tipos_umas TU ON U.id_tipos_umas = TU.id
				LEFT JOIN notas_itens NI ON UI.id_notas_itens = NI.id
				LEFT JOIN notas NF ON NF.id = NI.id_notas
				LEFT JOIN programacao PR ON PR.id = UI.id_programacao
				LEFT JOIN armazens ON armazens.id = UI.id_armazens
				{$addJoin}
				WHERE $ativas
					UI.cancelada  = 0
					AND P.cliente = 1
					{$where}
				GROUP BY";

		############################ GROUP BY ############################
		if ($groupBy <> "") {
			$sql .= " {$groupBy} ";
		} else {
			if ($gParam['USA_DATA_FABRICACAO']['ativo']) {
				$agrupaPorDataFabricacao = "UI.data_fabricacao,";
			}
			if ($gParam['USA_DATA_VALIDADE']['ativo']) {
				$agrupaPorDataValidade = "UI.data_validade,";
			}

			$agruparPorCliente = "P.apelido,";

			$agruparPorNota = "UI.id_notas_itens,";
			if ($gParam['EM_OBSERVACAO']['ativo']) {
				$agruparPorNota = "UI.id_notas_itens, UI.valor,";
			}
			if ($gParam['PERFIL_FABRICANTE']['ativo']) {
				$agruparPorNota = "";
				$agruparPorCliente = "";
				$agrupaPorDataValidade = "";
				$agrupaPorDataFabricacao = "";
			}

			if ($gParam['EM_OBSERVACAO']['ativo']) {
				$sql .= " U.id,
					PP.id,
					PA.id,
					SK.id,
					A.id,
					{$agruparPorCliente}
					D.descricao,
					UI.lote,
					{$agrupaPorDataFabricacao}
					{$agrupaPorDataValidade}
					{$agruparPorNota}
					UI.reservada,
					UI.separada,
					UI.avariada,
					UI.bloqueada
					{$addGroupBy} ";
			} else {
				$sql .= " U.id, U.ativo, U.codigo_barras, U.codigo_externo,
					U.conferida_saida,
					U.id_programacao,
					U.id_armazens,
					PP.codigo_barras,U.posicionada,
					PA.codigo_barras,
					U.data,
					UI.id_itens_skus,
					SK.codigo,
					I.shelf_life,
					I.id,
					I.nome,
					A.descricao,
					{$agruparPorCliente}
					SK.quantidade,
					D.descricao,
					UI.lote,
					{$agrupaPorDataFabricacao}
					{$agrupaPorDataValidade}
					{$agruparPorNota}
					UI.reservada,
					UI.separada,
					UI.avariada,
					UI.bloqueada
					{$addGroupBy} ";
			}

		}

		############################ HAVING ############################
		if ($having <> "") {
			$sql .= " HAVING {$having}";
		} else {
			if ($soAtivas) {
				$sql .= " HAVING SUM(UI.quantidade) > 0 ";
			}
		}

		############################ ORDER BY ############################
		if ($orderBy <> "") {
			$sql .= " ORDER BY {$orderBy}";
		} else {
			// Ordem das UMAs para o caso de separação
			if (isset($_SESSION['gPrioridadesReservas'])) {
				$gPrioridadesReservas = $_SESSION['gPrioridadesReservas'];
			}
			$usarMetodoAntigo = false;


			// Verifica se foi ativada a opção de prioridades de reserva no cadastro da empresa (proprietário)
			// Em caso positivo, usa a metodologia a seguir ($usarMetodoAntigo == false)
			if (!count($gPrioridadesReservas)) {
				$usarMetodoAntigo = true;
			} else {
				$ordens = array();

				foreach ($gPrioridadesReservas as $prioridadeReserva) {
					switch($prioridadeReserva['id_prioridade']) {
						case 1: // Ativar processamento destas ordens de prioridade
							$usarMetodoAntigo = true;
							if ($prioridadeReserva['ativo']) {
								$usarMetodoAntigo = false;
							}
							break;
						case 2: // Área de picking
							$ordens[] = "PP.picking DESC";
							break;
						case 3: // Prioridade do cadastro do item
							switch ($prioridade) {
								case 1: //FIFO - First In, First Out
									$p = "DATE(U.data)";
									break;
								case 2: //FEFO - First Expires, First Out (o primeiro q vence é o primeiro q sai)
									$p = "UI.data_validade, DATE(U.data)";
									break;
								case 3: //LIFO - Last in, First Out
									$p = "DATE(U.data) DESC";
									break;
								case 4: // IGNORAR
									$p = "DATE(UI.data_fabricacao)";
									break;
								default:
									$p = "DATE(UI.data_fabricacao)";
									break;
							}
							$ordens[] = $p;
							break;

						case 4: // Menor número de prédio
							$ordens[] = "PP.rua, PP.predio, PP.andar";
							break;

						case 5: // Maior número de prédio
							$ordens[] = "PP.rua, PP.predio DESC, PP.andar";
							break;

						case 6: // Palete aberto
							$ordens[] = "SUM(UI.quantidade) < (SK.palete_lastro * SK.palete_altura) DESC, SUM(UI.quantidade) ASC";
							break;

						case 7: // Palete fechado
							$ordens[] = "SUM(UI.quantidade) DESC";
							break;

						case 8: // Palete não posicionado
							$ordens[] = "(U.id_posicoes=0) DESC";
							break;

						case 9: // Reserva de paletes de pulmao primeiro desobedecendo FEFO, depois paletes no picking
							//nada a fazer
							break;
					}
				}

				if (!$usarMetodoAntigo) {
					if (count($ordens)) {
						$sql .= " ORDER BY " . $outrosParametros['addOrderByBefore']
							. implode(",",$ordens);
					} else {
						// Se ativou a prioridade no cadastro do cliente, mas não permitiu nada,
						// então usa o método antigo mesmo...
						$usarMetodoAntigo = true;
					}
				}

			}


			if ($usarMetodoAntigo) {
				$sql .= " ORDER BY " . $outrosParametros['addOrderByBefore'];

				// Ordem de separação
				// Depois de escolher pela data, seleciona a UMA que tem menos itens
				// (ou que provavelmente já houve remoção parcial)
				$fltPaleteAberto = "";
				if (!$priorizarPaleteAberto) {
					$fltPaleteAberto = " DESC";
				}

				$fltPaleteFechado = "";
				if ($priorizarPaleteFechado) {
					$fltPaleteFechado = " SUM(UI.quantidade) DESC, ";
				}

				// A última condição (U.id DESC) quer dizer pra pegar o último palete posicionado
				// Depois de consideradas todas as regras
				switch ($prioridade) {
					case 1: //FIFO - First In, First Out
						$sql .= " {$fltPaleteFechado} DATE(U.data),SUM(UI.quantidade) " . $fltPaleteAberto;
						break;
					case 2: //FEFO - First Expires, First Out (o primeiro q vence é o primeiro q sai)
						$sql .= " {$fltPaleteFechado} UI.data_validade, DATE(U.data), SUM(UI.quantidade) " . $fltPaleteAberto;
						break;
					case 3: //LIFO - Last in, First Out
						$sql .= " {$fltPaleteFechado} DATE(U.data) DESC, SUM(UI.quantidade) " . $fltPaleteAberto;
						break;
					case 4: // IGNORAR
						$sql .= " {$fltPaleteFechado} DATE(UI.data_fabricacao), SUM(UI.quantidade) " . $fltPaleteAberto;
						break;
					default:
						$sql .= "{$fltPaleteFechado} DATE(UI.data_fabricacao), SUM(UI.quantidade) " . $fltPaleteAberto;
						break;
				}
				$sql .= ", PP.rua, PP.predio, PP.andar, ";

				if ($temDataPosicionamento) {
					$sql .= " data_posicionamento DESC, ";
				}
				$sql .= "P.apelido, I.nome, A.prioridade, ";
				if ($gParam['FORMATA_POSICAO_RUA_ANDAR_PREDIO']['ativo']) {
					$sql .= "A.codigo, PP.rua, PP.predio, PP.andar, PP.apartamento, ";
				} else {
					$sql .= "PP.codigo_barras, ";
				}
				$sql .= "U.codigo_barras ";
			}
		}

		############################ LIMIT ############################
		if ($limit <> "") {
			$sql .= " LIMIT {$limit}";
		}

		if ($outrosParametros['paginarResultado']) {
			if ($gParam["PAGINACAO"]["ativo"]) {
				$porPagina = $gParam["PAGINACAO"]["valor"];
			}

			if ($porPagina > 0 && !$limit) {
				$sqlttl = "SELECT count(id_umas) ttl FROM (" . $sql . ") H";
				$this->pagination = $this->pagination->addPagination(dbQuery($sqlttl), $porPagina);
				$sql .= " LIMIT {$this->pagination->iniciar}, {$this->pagination->numero_registro_por_pagina}";
			}
		}
		############################ EXECUCAO ############################
		if ($outrosParametros['returnQuery']) {
			return $sql;
		}

		return dbFastQuery($sql);
	}


	public function obterQuantidade($parametro)
	{
		$where = array();
		$where[] = " UI.cancelada = 0 ";

		if ($parametro['idUmas']) {
			$where[] = "UI.id_umas = " . $parametro['idUmas'];
		}

		if ($parametro['idItensSkus']) {
			$where[] = "UI.id_itens_skus = " . $parametro['idItensSkus'];
		}

		if (!$parametro['where']) {
			$parametro['where'] = "
				UI.separada = 0
				AND UI.avariada = 0
				AND UI.reservada = 0
				AND UI.bloqueada = 0 ";
		}

		$where[] = $parametro['where'];
		$where = implode(" AND ", $where);

		$sql = "
			SELECT
				SUM(UI.quantidade) quantidade
			FROM umas_itens UI
			WHERE {$where}
			GROUP BY
				UI.id_umas,
				UI.id_itens_skus,
				UI.lote,
				UI.data_fabricacao,
				UI.data_validade,
				UI.id_notas_itens,
				UI.reservada,
				UI.separada,
				UI.avariada,
				UI.bloqueada
			HAVING
				SUM(UI.quantidade) > 0
			";
		$rs = dbFastQuery($sql);
		return (float) array_sum(array_column($rs, 'quantidade'));
	}


	public function formatarTabelaHistoricoUma($resultset)
	{
		global $o, $gParam, $usrId;
		if (!$resultset) {
			return;
		}

		$tabela = '';
		$tabela .= $o->msgSubTitle("Histórico da UMA");
		$tabela .= $o->tableBegin("big",true);

		$mtz   = [];
		$mtz[] = "->Id";
		if ($usrId <= 2) {
			$mtz[] = "<>Canc.";
			$mtz[] = "<>Cancelamento";
		}
		$mtz[] = '<>Data';
		if ($usrId <= 2) {
			$mtz[] = "<- Tipo";
		}
		$mtz[] = '<-Código';
		$mtz[] = '<-UMA origem';
		$mtz[] = '->Quant.';
		$mtz[] = "<-Lote";
		$mtz[] = '<-SKU';
		$mtz[] = '<>Res. ';
		$mtz[] = '<>Sep. ';
		$mtz[] = '<>Ava. ';
		$mtz[] = '<>Blq.';
		$mtz[] = '<>Inv. ';
		$mtz[] = '<-Propr.';
		$mtz[] = '<-OS        ';
		if ($gParam["PERFIL_PRODUCAO_COM_KITS"]["ativo"]) {
			$mtz[] = '<-Direc.';
			$mtz[] = '<-OF';
		}
		$mtz[] = '<-' . NOME_CODIGO_EXTERNO;
		if (!$gParam["PERFIL_FABRICANTE"]["ativo"]) {
			$mtz[] = '->Valor';
			$mtz[] = '<-NF';
		}
		$mtz[] = '->P.Líq.';
		$mtz[] = '->P.Bruto  ';
		if ($gParam['USA_M2']['ativo']) {
			$mtz[] = '->m2       ';
		}
		if ($gParam['USA_M3']['ativo']) {
			$mtz[] = '->m3       ';
		}
		if ($gParam['USA_DATA_FABRICACAO']['ativo']) {
			$mtz[] = '<>Fabric.';
		}
		if ($gParam['USA_DATA_VALIDADE']['ativo']) {
			$mtz[] = '<>Valid.';
		}
		if ($gParam['USA_CARGAS_REFRIGERADAS']['ativo']) {
			$mtz[] = '->Temp.    ';
		}
		$mtz[] = '<-Usuário  	';
		$mtz[] = '<-Obs.  ';
		$tabela .= $o->tableRow($mtz,"header");

		foreach ($resultset as $rowMovimentos) {
			$mtz   = array();
			$mtz[] = '->'. $rowMovimentos['id'];
			$cores = array(
				'success',
				'danger'
			);
			$tituloCancelado = array(
				'Não',
				'Sim'
			);

			if ($usrId == 1) {
				$mtz[] = $o->button("{title: " . $tituloCancelado[$rowMovimentos['cancelada']] . "; style: " . $cores[$rowMovimentos['cancelada']] . "; size: tiny; href: " . $o->page . "&gPage=" . ATUALIZAR_FLAGS . "&gId=" . $rowMovimentos['id'] . "&tabela=umas_itens&coluna=cancelada&buscar=" . $_REQUEST['buscar']."&forcar=" . $_REQUEST['forcar'] . '&idUmas=' . $rowMovimentos['id_umas']);

				$mtz[] = "<-" . gDateTime($rowMovimentos['data_cancelamento']) . '<br>' . ($rowMovimentos["apelido_pessoa_cancelou"]);
			}
			$mtz[] = '<>' . gDateTime($rowMovimentos['data']);

			if ($usrId <= 2) {
				$mtz[] = "<- " . $rowMovimentos['tipo_operacao'];
			}

			if ($usrId == 1) {
				$mtz[] = "<-" . linkParaCodigoItem($rowMovimentos["codigo"]) . '<br>' . $o->small('ID SK:' . $rowMovimentos["id_itens_skus"]);
			} else {
				$mtz[] = '<-' . $rowMovimentos['codigo'];
			}
			$mtz[] = '<-' . linkParaUMA(formataUMA($rowMovimentos['id_umas_origem']));

			$mtz[] = '->' . gFloat($rowMovimentos['quantidade']);
			$mtz[] = '<-' . $rowMovimentos['lote'];
			$mtz[] = '<-' . $rowMovimentos['unidade_sigla'].' com '.intval($rowMovimentos['quantidade_sku']);
			if ($usrId <= 2) {
				$mtz[] = $o->button("{title: R; style: " . $cores[$rowMovimentos['reservada']] . "; size: tiny; href: " . $o->page . "&gPage=" . ATUALIZAR_FLAGS . "&gId=" . $rowMovimentos['id'] . "&tabela=umas_itens&coluna=reservada&buscar=" . $_REQUEST['buscar'] . "&forcar=" . $_REQUEST['forcar']);
			} else {
				$mtz[]='<>' . gCheck($rowMovimentos['reservada'], true, array('R','R'));
			}

			if ($usrId <= 2) {
				$mtz[] = $o->button("{title: S; style: " . $cores[$rowMovimentos['separada']] . "; size: tiny; href: " . $o->page . "&gPage=" . ATUALIZAR_FLAGS . "&gId=" . $rowMovimentos['id'] . "&tabela=umas_itens&coluna=separada&buscar=" . $_REQUEST['buscar'] . "&forcar=" . $_REQUEST['forcar']);
			} else {
				$mtz[] = '<>' . gCheck($rowMovimentos['separada'], true, array('S','S'));
			}
			if ($usrId <= 2) {
				$mtz[] = $o->button("{title: A; style: " . $cores[$rowMovimentos['avariada']] . "; size: tiny; href: " . $o->page . "&gPage=" . ATUALIZAR_FLAGS . "&gId=" . $rowMovimentos['id'] . "&tabela=umas_itens&coluna=avariada&buscar=" . $_REQUEST['buscar'] . "&forcar=" . $_REQUEST['forcar']);
			} else {
				$mtz[] = '<>' . gCheck($rowMovimentos['avariada'], true, array('A','A'));
			}
			if ($usrId <= 2) {
				$mtz[] = $o->button("{title: B; style: " . $cores[$rowMovimentos['bloqueada']] . "; size: tiny; href: " . $o->page . "&gPage=" . ATUALIZAR_FLAGS . "&gId=" . $rowMovimentos['id'] . "&tabela=umas_itens&coluna=bloqueada&buscar=" . $_REQUEST['buscar'] . "&forcar=" . $_REQUEST['forcar']);
			} else {
				$mtz[] = '<>' . gCheck($rowMovimentos['bloqueada'], true, array('B','B'));
			}
			$mtz[] = '<>' . gCheck($rowMovimentos['inventario'], true, array('I','I'));
			if ($usrId == 1) {
				$mtz[] = '<-' . $rowMovimentos['apelido'] . '<br>' . $o->small('ID: ' . $rowMovimentos["id_pessoas_proprietario"]);
				$os = linkParaOS($rowMovimentos['os']) . '<br>' . $o->small('ID PI: ' . $rowMovimentos["id_programacao_itens"]);
			} else {
				$mtz[] = '<-' . $rowMovimentos['apelido'];
				$os = $rowMovimentos['os'];
			}
			$mtz[] = '<-' . $os . "<BR>" . $o->small($rowMovimentos['tipo_programacao']);
			if ($gParam["PERFIL_PRODUCAO_COM_KITS"]["ativo"])
			{
				$mtz[] = "<-" . $rowMovimentos["direcionar"];
				$mtz[] = "<-" . $rowMovimentos["numero_cliente"];
			}
			$mtz[] = '<-' . $rowMovimentos['codigo_externo'];
			if (!$gParam["PERFIL_FABRICANTE"]["ativo"]) {
				$formatoOriginal = gVar("global.numformat");
				gVar("global.numformat", '0.000,0000000000');
				$mtz[] = "->" . gFloat($rowMovimentos["valor"]);
				gVar("global.numformat", $formatoOriginal);
				if ($usrId == 1) {
					$mtz[] = "<-" . linkParaNota($rowMovimentos["id_notas"]) . '<br>' . $o->small('ID NI: ' . $rowMovimentos["id_notas_itens"]);
				} else {
					$mtz[] = "<-" . $rowMovimentos["numero_nf"];
				}
			}
			$mtz[] = '->' . gFloat($rowMovimentos['peso_liquido']);
			$mtz[] = '->' . gFloat($rowMovimentos['peso_bruto']);
			if ($gParam['USA_M2']['ativo']) {
				$mtz[] = '->' . gFloat($rowMovimentos['m2']);
			}
			if ($gParam['USA_M3']['ativo']) {
				$mtz[] = '->' . gFloat($rowMovimentos['m3']);
			}
			if ($gParam['USA_DATA_FABRICACAO']['ativo']) {
				$mtz[] = '<>' . gDateTime($rowMovimentos['data_fabricacao']);
			}
			if ($gParam['USA_DATA_VALIDADE']['ativo']) {
				$mtz[] = '<>' . gDateTime($rowMovimentos['data_validade']);
			}
			if ($gParam['USA_CARGAS_REFRIGERADAS']['ativo']) {
				$mtz[] = '->' . gFloat($rowMovimentos['temperatura']);
			}
			$mtz[] = '<-' . ucfirst($rowMovimentos['criou']);
			$mtz[] = '<-' . $rowMovimentos['observacoes'];

			$mtz2   = array();
			$mtz2[] = '~2';
			$mtz2[] = '~' . count($mtz) . '<-'. $o->small($rowMovimentos['item']);
			if ($rowMovimentos['cancelada']) {
				$tabela .= $o->tableRow($mtz,"text-danger");
				$tabela .= $o->tableRow($mtz2,"text-danger");
			} else {
				if ($rowMovimentos['quantidade']>=0) {
					$tabela .= $o->tableRow($mtz,"text-success");
					$tabela .= $o->tableRow($mtz2,"text-success");
				} else {
					$tabela .= $o->tableRow($mtz,"text-warning");
					$tabela .= $o->tableRow($mtz2,"text-warning");
				}
			}

		}
		$tabela .= $o->tableEnd();
		return $tabela;
	}

	function obtemProprietarioUMA($idUma)
	{
		$sql="SELECT
				UI.id_pessoas_proprietario
			  FROM umas_itens UI
			  LEFT JOIN umas U ON UI.id_umas = U.id
			  WHERE U.id = '{$idUma}'
			";
		return (dbQuery($sql)[0]["id_pessoas_proprietario"]);
	}

	function obtemMovimentosUMA($idUma=0)
	{
		$sql = "SELECT
					UI.*,
					A.descricao area, A.codigo local,
					PP.apelido proprietario,
					PS.codigo_barras posicao,
					UO.codigo_barras uma_origem,
					UD.codigo_barras uma_destino,
					P.apelido criou
				FROM umas_movimentos UI
				LEFT JOIN pessoas P ON (P.id=UI.id_pessoas AND P.cliente=0)
				LEFT JOIN pessoas PP ON (PP.id=UI.id_pessoas_proprietario AND PP.cliente=1)
				LEFT JOIN posicoes PS ON UI.id_posicoes=PS.id
				LEFT JOIN areas A ON PS.id_areas=A.id
				LEFT JOIN umas UO ON UI.id_umas=UO.id
				LEFT JOIN umas UD ON UI.id_umas_para=UD.id
				WHERE UI.id_umas=$idUma
				ORDER BY UI.data, UI.id";
		return(dbQuery($sql));
	}

	function obtemTransformacoesUMA($idUma=0)
	{
		$sql = "SELECT
					UT.*,
					TT.descricao tipo_descricao,
					U.codigo_barras  codigo_barras_de,
					UP.codigo_barras codigo_barras_para,
					UD.descricao unidade_de,
					ID.nome item_de,
					IKD.quantidade quantidade_de,
					IKD.codigo as codigo_de,
					IP.nome item_para,
					IKP.quantidade quantidade_para,
					IKP.codigo as codigo_para,
					UNP.descricao unidade_para,
					PI.nome as pessoa_iniciou,
					PF.nome as pessoa_finalizou
				FROM umas_transformacoes UT
				LEFT JOIN umas U ON UT.id_umas_de = U.id
				LEFT JOIN umas UP ON UT.id_umas_para = U.id
				LEFT JOIN itens_skus IKD ON IKD.id = UT.id_itens_skus_de
				LEFT JOIN itens ID ON ID.id = IKD.id_itens
				LEFT JOIN unidades UD ON UD.id = IKD.id_unidades
				LEFT JOIN itens_skus IKP ON IKP.id = UT.id_itens_skus_para
				LEFT JOIN itens IP ON IP.id = IKP.id_itens
				LEFT JOIN unidades UNP ON IKP.id_unidades = UP.id
				LEFT JOIN pessoas PI ON (PI.id = UT.id_pessoas_iniciou AND PI.cliente=0)
				LEFT JOIN pessoas PF ON (PF.id = UT.id_pessoas_finalizou AND PF.cliente=0)
				LEFT JOIN tipos_transformacoes TT ON TT.id = UT.id_tipos_transformacoes
				WHERE UT.id_umas_de='{$idUma}'
		";
		return (array());
	}


	public function obterObservacoesUMAs($idUma, $codigoUma)
	{
		$where = array();
		if ($idUma) {
			$where[] = "id_umas = '{$idUma}'";
		}

		if ($codigoUma) {
			$where[] = "(codigo_barras = '{$codigoUma}' OR codigo_externo = '{$codigoUma}')";
		}

		if (!$where) {
			return;
		}

		$where = implode(' AND ', $where);

		$sql = "
			SELECT  umas_verificar.`data`,
			        umas_verificar.observacoes,
			        pessoas.apelido AS colaborador
			FROM umas_verificar
			JOIN pessoas ON pessoas.id = umas_verificar.id_pessoas
			WHERE {$where}";
		return dbQuery($sql);
	}


	function conversaoUnidades($idItem, $idUnidade, $idUnidadeConverter)
	{
		$quantidadeSkuAtual=dbQuery("SELECT quantidade FROM itens_skus WHERE id_itens='{$idItem}' AND id_unidades='{$idUnidade}'")[0];
		$quantidadeSku=dbQuery("SELECT quantidade FROM itens_skus WHERE id_itens='{$idItem}' AND id_unidades='{$idUnidadeConverter}'")[0];
		return ($quantidadeSkuAtual["quantidade"]/$quantidadeSku["quantidade"]);
	}

	function conversaoUnidadesUma($itemProgramacao, $umaNecessaria, $quantidadeSeparar)
	{
		/* Ecnontrar o item da uma para transformar a unidade */
		$item["avariada"]=$umaNecessaria["avariada"];
		$item["separada"]=$umaNecessaria["separada"];
		$item["bloqueada"]=$umaNecessaria["bloqueada"];
		$item["reservada"]=$umaNecessaria["reservada"];
		$item["codigo"]=$umaNecessaria["codigo"];
		$item["id_itens_skus"]=$umaNecessaria["id_itens_skus"];
		$item["data_validade"]=$umaNecessaria["data_validade"];
		$item["data_fabricacao"]=$umaNecessaria["data_fabricacao"];
		$item["lote"]=$umaNecessaria["lote"];
		$item=$this->buscaItem($umaNecessaria["id"], $item)[0];
		$idUnidade=gFieldById("itens_skus", $umaNecessaria["id_itens_skus"], "id_unidades");
		$idUnidadeConverter=gFieldById("itens_skus", $itemProgramacao["id_itens_skus"], "id_unidades");
		$skuConvertido=$this->conversaoUnidades($itemProgramacao["id_itens"], $idUnidade, $idUnidadeConverter);
		if ($quantidadeSeparar >= ($umaNecessaria["quantidade"] * $skuConvertido) )
		{
			$item=$this->preparaCamposDoItem($umaNecessaria["id"], $item);
			$item["tipo"]="-";
			$item["quantidade"]=-($umaNecessaria["quantidade"]);
			$item["id_programacao"]=$itemProgramacao["id_programacao"];
			dbInsert("umas_itens", $item);

			$idUnidadeConverter=gFieldById("itens_skus", $itemProgramacao["id_itens_skus"], "id_unidades");
			$idUnidade=gFieldById("itens_skus", $umaNecessaria["id_itens_skus"], "id_unidades");
			$skuConvertido=$this->conversaoUnidades($itemProgramacao["id_itens"], $idUnidade, $idUnidadeConverter);
			$item["tipo"]="+";
			$item["quantidade"]=($skuConvertido * $umaNecessaria["quantidade"]);
			$item["id_itens_skus"]=$itemProgramacao["id_itens_skus"];
			$item["id_programacao"]=$itemProgramacao["id_programacao"];
			dbInsert("umas_itens", $item);
			atualizarAtivacaoUMA($item['id_umas']);

		} else
		{
			/* Negativar quantidade para dar baixa em saldo completo */
			$idUnidade=gFieldById("itens_skus", $itemProgramacao["id_itens_skus"], "id_unidades");
			$idUnidadeConverter=gFieldById("itens_skus", $umaNecessaria["id_itens_skus"], "id_unidades");
			$skuConvertido=$this->conversaoUnidades($itemProgramacao["id_itens"], $idUnidade, $idUnidadeConverter);
			if (is_float(  ($quantidadeSeparar * $skuConvertido)  ))
			{
				$quantidadeOriginal=ceil($quantidadeSeparar * $skuConvertido);
				/* Como aproximou a quantidade quebrada, aproximar tambem a quantidade na nova unidade */
				$idUnidadeConverter=gFieldById("itens_skus", $itemProgramacao["id_itens_skus"], "id_unidades");
				$idUnidade=gFieldById("itens_skus", $umaNecessaria["id_itens_skus"], "id_unidades");
				$skuConvertido=$this->conversaoUnidades($itemProgramacao["id_itens"], $idUnidade, $idUnidadeConverter);
				$quantidadeSeparar=($skuConvertido*$quantidadeOriginal);
			} else
			{
				$quantidadeOriginal=($quantidadeSeparar * $skuConvertido);
			}
			$item=$this->preparaCamposDoItem($umaNecessaria["id"], $item);
			$item["id_programacao"]=$itemProgramacao["id_programacao"];
			$item["tipo"]="-";
			$item["quantidade"]=-($quantidadeOriginal);
			dbInsert("umas_itens", $item);

			$item["tipo"]="+";
			$item["quantidade"]=$quantidadeSeparar;
			$item["id_itens_skus"]=$itemProgramacao["id_itens_skus"];
			dbInsert("umas_itens", $item);
			atualizarAtivacaoUMA($item['id_umas']);
		}

		/* Salvar log */
		// Inserindo registro histórico para rastreamento...
		/*
		$unidade=(dbQuery("SELECT U.id_unidades, U.descricao FROM itens_skus SK
					 LEFT JOIN unidades U ON SK.id_unidades = U.id
					 WHERE SK.id='".$umaNecessaria["id_itens_skus"]."'
					")[0]);

		$unidadeConverter=(dbQuery("SELECT U.id_unidades, U.descricao FROM itens_skus SK
					 LEFT JOIN unidades U ON SK.id_unidades = U.id
					 WHERE SK.id='".$itemProgramacao["id_itens_skus"]."'
					")[0]);

		$log = "Transformou ".$umaNecessaria["codigo_barras"]." unidade: ".$unidade["descricao"]." para ".$unidadeConverter["descricao"];
		$this->insereRastreamentoUMA($umaNecessaria["id"], 0, 'O', 0, $log);
		userLog($log);
		*/
		return ($quantidadeSeparar-$item["quantidade"]);
	}

	/**
	 * Busca um item
	 */
	function buscaItem($id_umas, $item)
	{
		global $gParam;
		$id 	= 0;
		$where 	= '';

		# Busca o id do item/SKU usando uma das formas abaixo
		if ($item['codigo']<>"")
		{
			$where.= " AND IK.codigo='".$item['codigo']."'";
		}

		if (isset($item["id_notas_itens"])) {
			$where .= " AND (UI.id_notas_itens = " . ((int) $item["id_notas_itens"]) . ")";
		}

		if ($item['codigo_barras']<>"")
		{
			$where.= " AND (IK.codigo_barras='".$item['codigo_barras']."' OR IK.codigo_barras_alternativo='".$item['codigo_barras_alternativo']."')";
		}
		if ($item['codigo_barras_posicao']<>"")
		{
			$where.= " AND P.codigo_barras='".$item['codigo_barras_posicao']."'";
		}
		if ($item['id_programacao']>0)
		{
			$where.= " AND UI.id_programacao=".intval($item["id_programacao"]);
		}
		if ($item['id_programacao_itens']>0)
		{
			$where.= " AND UI.id_programacao_itens=".intval($item['id_programacao_itens']);
		}
		if ($item['id_itens_skus']>0)
		{
			$where.= " AND UI.id_itens_skus=".intval($item['id_itens_skus']);
		}

		if (isset($item['lote']))
		{
			$where.= " AND UI.lote='".$item['lote']."'";
		}
		if ($gParam['PERFIL_FABRICANTE']['ativo']==0)
		{
			if (isset($item['data_validade']) && $gParam['USA_DATA_VALIDADE']['ativo']==1)
			{
				$where.= " AND UI.data_validade='".$item["data_validade"]."'";
			}
			if (isset($item['data_fabricacao']) && $gParam['USA_DATA_FABRICACAO']['ativo']==1)
			{
				$where.= " AND UI.data_fabricacao = '".$item["data_fabricacao"]."'";
			}
		}
		if (isset($item['posicionada']))
		{
			$where.= " AND U.posicionada=".intval($item['posicionada']);
		}
		if (isset($item['bloqueada']))
		{
			$where.= " AND UI.bloqueada=".intval($item['bloqueada']);
		}
		if (isset($item['separada']))
		{
			$where.= " AND UI.separada=".intval($item['separada']);
		}
		if (isset($item['reservada']))
		{
			$where.= " AND UI.reservada=".intval($item['reservada']);
		}
		if (isset($item['avariada']))
		{
			$where.= " AND UI.avariada=".intval($item['avariada']);
		}

		if (isset($item['id_pessoas_proprietario']))
		{
			$where.= " AND UI.id_pessoas_proprietario=".intval($item["id_pessoas_proprietario"]);
		}
		$where .= " AND U.imobilizada = " . (int) $item['imobilizada'];

		// Não precisa pegar todos os campos para obter os saldos
		// Só são considerados os campos principais
		$sql = "SELECT
					UI.id_programacao,
					UI.id_programacao_itens,
					UI.id_notas_itens,
					UI.id_itens_skus,
					UI.data_fabricacao,
					UI.data_validade,
					UI.lote,
					UI.reservada,
					UI.separada,
					UI.bloqueada,
					UI.avariada,
					SUM(UI.quantidade) quantidade,
					U.id_posicoes,
					U.qualidade
				FROM umas_itens UI
				JOIN umas U ON UI.id_umas = U.id
				LEFT JOIN posicoes P ON U.id_posicoes = P.id
				JOIN itens_skus IK ON UI.id_itens_skus=IK.id
				JOIN itens I ON IK.id_itens=I.id
				LEFT JOIN programacao_itens PI ON
						PI.id_programacao = UI.id_programacao
						AND UI.id_programacao_itens=PI.id
				LEFT JOIN programacao PR ON PI.id_programacao=PR.id
				WHERE UI.cancelada=0 AND UI.id_umas=$id_umas ".$where." ";
		$rs = dbFastQuery($sql);

		if (!$rs[0]) {
			return false;
		}

		// Só considera encontrado, se tiver saldo
		// Se encontrar, retorna os campos do item (umas_itens)
		// Pode ser que encontre vários registros (itens)
		foreach ($rs as $row) {
			if ($row['quantidade'] <= 0) {
				continue;
			}
			// Busca detalhes do item que ficam distorcidos no GROUP BY
			$sql = "SELECT * FROM umas_itens  WHERE
						cancelada=0 AND
						id_umas=".$id_umas." AND tipo='+' AND
						id_itens_skus=".$row['id_itens_skus']." AND
						data_validade='".$row['data_validade']."' AND
						data_fabricacao='".$row['data_fabricacao']."' AND
						lote='".$row['lote']."' AND
						bloqueada='".$row['bloqueada']."' AND
						avariada='".$row['avariada']."' AND
						separada='".$row['separada']."' AND
						reservada='".$row['reservada']."' AND
						id_notas_itens='".intval($row['id_notas_itens'])."'
						GROUP BY id_itens_skus, data_validade, lote
						ORDER BY id DESC"; // O último registro deve ter a informação mais atualizada
			$rst = dbFastQuery($sql);
			foreach ($rst as $res) {
				// Muda o valor dos campos obtidos pelos respectivos saldos
				$res['id_posicoes'] = $rs[0]['id_posicoes'];
				$res['quantidade'] = $row['quantidade'];
				$res['id_programacao_itens']=$row['id_programacao_itens'];
				$res['id_programacao']=$row['id_programacao'];
				$res['qualidade']=$row['qualidade'];
				$itensEncontrados[] = $res;
			}
		}

		return $itensEncontrados;
	}

	function preparaCamposDoItem($id_umas, $item)
	{
		global $usrId;
		$campos = array();
		$id_umas = $this->buscaUMA($id_umas);
		if (!$id_umas) {
			return $campos;
		}

		$campos['data']                    = date('Y-m-d H:i:s'); // troca pela data atual
		$campos['data_fabricacao']         = ($item['data_fabricacao'] ?: '0000-00-00');
		$campos['data_validade']           = ($item['data_validade'] ?: '0000-00-00');
		$campos['data_validade_antiga']    = ($item['data_validade_antiga'] ?: '0000-00-00');
		$campos['id_pessoas_criou']        = $usrId; // Troca pelo id do usuário atual
		$campos['id_pessoas_proprietario'] = intval($item['id_pessoas_proprietario']);
		$campos['id_umas']                 = intval($id_umas);
		$campos['id_umas_origem']          = intval($item['id_umas_origem']);
		$campos['id_itens_skus']           = intval($item['id_itens_skus']);
		$campos['id_programacao']          = $item['id_programacao'] ?: $this->os["id"];
		$campos['id_programacao_itens']    = intval($item['id_programacao_itens']);
		$campos['id_armazens']             = intval($item['id_armazens'] ?: $_SESSION['armazemAtualId']);
		$campos['id_veiculos_acessos']     = intval($item['id_veiculos_acessos']);
		$campos['id_notas_itens']          = intval($item['id_notas_itens']);
		$campos['id_areas_direcionar']     = intval($item['id_areas_direcionar']);
		$campos['reservada']               = intval($item['reservada']);
		$campos['separada']                = intval($item['separada']);
		$campos['avariada']                = intval($item['avariada']);
		$campos['bloqueada']               = intval($item['bloqueada']);
		$campos['inventario']              = intval($item['inventario']);
		$campos['faturar']                 = intval($item['faturar']);
		$campos['entrada']                 = intval($item['entrada']);
		$campos['saida']                   = intval($item['saida']);
		$campos['tipo']                    = ($item['tipo']=='-' ? '-':'+');
		$campos['lote']                    = $item['lote'];
		$campos['serial']                  = $item['serial'];
		$campos['codigo_externo']          = $item['codigo_externo'];
		$campos['observacoes']             = gCleanField($item['observacoes']);
		//$campos['id_notas_itens']		   = $item['id_notas_itens'];

		// Remove o sinal do valor e considera o sinal informado
		// Valor não pode ser negativado, pois o campo refere-se ao valor unitário do produto, sendo que pra pegar o saldo de valor usa-se, saldo da quantidade * valor
		$campos['valor']                   = ($item['valor'] ? $item['valor'] : $item['valor_nota']);
		$campos['quantidade']              = floatval($campos['tipo'].str_replace('-','',$item['quantidade']));
		// Deve ser considerado o peso unitário e não o peso total (ou seja, * quantidade)
		if ($item['un_peso_liquido']>0) {
			$campos['peso_liquido']            = floatval($campos['tipo'].str_replace('-','',$item['un_peso_liquido']));
			$campos['peso_bruto']              = floatval($campos['tipo'].str_replace('-','',$item['un_peso_bruto']));
			$campos['m2']                      = floatval($campos['tipo'].str_replace('-','',$item['un_m2']));
			$campos['m3']                      = floatval($campos['tipo'].str_replace('-','',$item['un_m3']));	
		} else {
			$campos['peso_liquido']            = floatval($campos['tipo'].str_replace('-','',$item['peso_liquido']));
			$campos['peso_bruto']              = floatval($campos['tipo'].str_replace('-','',$item['peso_bruto']));
			$campos['m2']                      = floatval($campos['tipo'].str_replace('-','',$item['m2']));
			$campos['m3']                      = floatval($campos['tipo'].str_replace('-','',$item['m3']));	
		}
		$campos['temperatura']             = $item['temperatura'];
		if (isset($item['id_tipos_operacao'])) {
			$campos['id_tipos_operacao']   = (int) $item['id_tipos_operacao'];
		}
		return $campos;
	}

	/**
	 * Insere um registro de item na conta da UMA com o sinal informado
	 */
	function salvaItemNaUMA($id_umas, $item, $sinal = '', $campoFlag='', $atualizarStatusUma = 0)
	{
		if ($sinal=="")
		{
			if (!isset($item['tipo']))
			{
				$item['tipo'] = '+';
			}
		} else {
			$item['tipo'] = $sinal;
		}
		if ($campoFlag<>'')
		{
			$item["bloqueada"]=0;
			$item["reservada"]=0;
			$item["separada"]=0;
			$item["avariada"]=0;
			$item["cancelada"]=0;
			$item["faturar"]=0;
			$item[$campoFlag]=1;
		}

		$item = $this->preparaCamposDoItem($id_umas, $item, $sinal);

		if (is_array($item)) {
			$item = array_map('gCleanField', $item);
			$values = "'" . implode("', '", $item)."'";
			$values = str_replace(' ,', " '',", $values);
			dbFastQuery("INSERT INTO umas_itens (".implode(', ', array_keys($item)).") VALUES ({$values})");
			if ($atualizarStatusUma) {
				atualizarAtivacaoUMA($id_umas);
			}
			return true;
		}

		if ($atualizarStatusUma) {
			atualizarAtivacaoUMA($id_umas);
		}

		return false;
	}

	/**
	 * Baixa o saldo de um item na UMA
	 */
	function baixaItemNaUMA($id_umas, $item)
	{
		return($this->salvaItemNaUMA($id_umas, $item, '-', '', 1));
	}

	/**
	 * Adiciona o saldo de um item na UMA
	 * (Verifica se a UMA existe - pelo ID ou Cód. Barras - se não existir não faz nada e gera erro)
	 */
	function adicionaItemNaUMA($id_umas, $item, $campoFlag='')
	{
		return($this->salvaItemNaUMA($id_umas, $item, '+', $campoFlag, 1));
	}

	/**
	 * Transforma um item que tenha saldo na UMA, exemplo: informar quantidade avariada
	 * (Verifica se a UMA existe - pelo ID ou Cód. Barras - se não existir não faz nada e gera erro)
	 */
	function transformaItemNaUMA($id_umas, $itemAProcurar, $transformarPara)
	{
		// Para transformar, é necessário antes buscar o item desejado
		// levando em consideração algum dos parâmetros:
		// Id, cód. do item, cód. de barras ou cód da posição
		// Os demais campos encontrados serão utilizados para
		// a criação do novo registro
		$itensEncontrados = $this->buscaItem($id_umas, $itemAProcurar);
		if (is_array($itensEncontrados) && isset($itensEncontrados[0]))
		{
			// Encontrou itens, então
			// 1. Zera a quantidade do item encontrado, criando um novo registro c/ sinal oposto
			// 2. Coloca a quantidade de novo com o sinal original, só que com alguns campos mudados
			foreach ($itensEncontrados as $item)
			{
				// Altera o valor somente para os campos informados:
				foreach ($itemAProcurar as $campo=>$valor)
				{
					$item[$campo] = $valor;
				}
				if (isset($transformarPara['id_programacao']))
				{
					$item['id_programacao']=$transformarPara['id_programacao'];
				} else {
					$item['id_programacao']= 0;
				}
				if (isset($transformarPara['id_programacao_itens']))
				{
					$item['id_programacao_itens']=$transformarPara['id_programacao_itens'];
				} else {
					$item['id_programacao_itens']= 0;
				}
				// Encontrou saldo, dá baixa:
				$this->salvaItemNaUMA($id_umas, $item, '-');
				// Altera o valor somente para os campos informados:
				foreach ($transformarPara as $campo=>$valor)
				{
					$item[$campo] = $valor;
				}
				// Adiciona no saldo, com alguns campos alterados:
				$this->salvaItemNaUMA($id_umas, $item, '+');
			}
		} else {
			$this->erros[] = "Não foi encontrado um valor positivo ao somar as movimentações e de acordo com as especificações de busca na " . formataUMA($id_umas);
		}
	}

	/**
	 * Baixa o saldo de um item de um tipo-uma na UMA
	 * (Verifica se a UMA existe - pelo ID ou Cód. Barras - se não existir não faz nada e gera erro)
	 * para outro tipo na mesma UMA ou em outra
	 */
	function transfereItemNaUMA($id_umas_origem, $id_umas_destino, $item, $tipoDestino=1, $campoFlag='')
	{
		if ($this->baixaItemNaUMA($id_umas_origem, $item)){
			$this->adicionaItemNaUMA($id_umas_destino, $item, $campoFlag);
		}
	}

	/**
	 * Faz o inverso da função transfereItemNaUMA
	 */
	function cancelarTransfereItemNaUMA($id_umas_origem, $id_umas_destino, $item, $tipoDestino=1, $campoFlag='')
	{
		$this->salvaItemNaUMA($id_umas_origem,  $item, '-', $campoFlag, 1);
		$this->salvaItemNaUMA($id_umas_destino, $item, '+', 'faturar', 1);
	}

	/**
	 * Registra uma atividade executada na OS
	 */
	function executaAtividade($descricao="", $quantidade=0, $id_tipos_atividades=0)
	{
		global $gId, $usrId;
		$usrId = (int) $usrId;
		$gId = (int) $gId;
		$sql = "INSERT INTO programacao_atividades
				(id_programacao,id_pessoas, id_tipos_atividades, id_itens_skus, data, quantidade, descricao) VALUES
				({$gId},{$usrId},".intval($id_tipos_atividades).",".intval($this->item['id_itens_skus']).",'".date('Y-m-d H:i:s')."',".floatval($quantidade).",'".gCleanField($descricao)."')";
		dbFastQuery($sql);
	}


	/**
	 * Verifica se a OS finalizou
	 * Faz a conferência da tabela programacao_umas, caso todos os itens esteja concluido retorna true
	 */
	function confereFinalizouProgramacaoUmas($idProgramacao)
	{
		$programacaoUmas=dbQuery("SELECT executada FROM programacao_umas WHERE id_programacao='{$idProgramacao}'");
		$liberar=true;
		foreach ($programacaoUmas as $programacao)
		{
			if ($programacao["executada"]==0)
			{
				$liberar=false;
			}
		}
		return ($liberar);
	}

	/* Verifica se uma UMA especifica da os finalizou */
	function confereFinalizouUmaProgramacao($idProgramacao, $idUma)
	{
		/* Verificar tipos de programação */
		$programacaoUma=dbQuery("SELECT executada FROM programacao_umas where id_programacao='{$idProgramacao}' AND id_umas='{$idUma}'");
		if ($programacaoUma["executada"]==1)
		{
			return (true);
		} else
		{
			return (false);
		}
	}

	function calculaUMAsNecessarias($id_programacao)
	{
		$quantidade = 0;
		// Calcula quantas UMAs serão necessárias
		$sql = "SELECT PIT.id, PIT.conferida,
						PIT.quantidade,
						PIT.quantidade_aceita,
						SK.altura,
						SK.largura,
						SK.comprimento,
						SK.palete_lastro,
						SK.palete_altura,
						SK.empilhamento_maximo
					FROM programacao_itens PIT
					LEFT JOIN itens_skus SK ON PIT.id_itens_skus=SK.id
					WHERE PIT.id_programacao=".intval($id_programacao);

		$rsp = dbQuery($sql);
		$this->umas_necessarias = array();
		foreach ($rsp as $row)
		{

			$max_por_uma = $row['palete_lastro']*$row['palete_altura'];
			$quantidadeCalcular = $row['quantidade'];
			if ($row['conferida']==1)
			{
				$quantidadeCalcular = $row['quantidade_aceita'];
				$quantidade=$quantidadeCalcular;
			}

			if ($quantidadeCalcular==0)
			{
				$quantidade = 0;
			} elseif ($quantidadeCalcular<$max_por_uma)
			{
				$quantidade = 1;
			} else {
				$quantidade = intval($quantidadeCalcular/$max_por_uma);
				if (($quantidadeCalcular/$max_por_uma) > $quantidade) // UMA com quantidade menor
				{
					$quantidade++;
				}
			}
			$total+=$quantidade;
			$sql="UPDATE programacao_itens SET umas_necessarias=".$quantidade." WHERE id=".$row['id'];
			$this->umas_necessarias[$row['id']]=$quantidade;
			dbQuery($sql);
		}

		return($total);
	}

	function adicionaContagemNaUMA($id, $itemContado, $itemProgramado, $bloqueada=0, $avariada=0)
	{
		global $usrId,$gParam;
		$usrId = intval($usrId);
		// Cria itens da UMA (normalmente só um)
		$camposItens = '';
		$camposItens['id_umas']                 = $id;
		$camposItens['id_programacao']          = $itemContado['id_programacao'];
		$camposItens['id_pessoas_proprietario'] = $itemContado['id_pessoas_proprietario'];
		$camposItens['id_pessoas_criou']        = $usrId;
		$camposItens['id_armazens']             = $itemContado['id_armazens'];
		$camposItens['id_veiculos_acessos']     = $itemContado['id_veiculos_acessos'];
		$camposItens['id_itens_skus']           = $itemContado['id_itens_skus'];
		$camposItens['data_fabricacao']         = $itemContado['data_fabricacao'];
		// Se a data de validade não for informada, busca do cadastro do item o prazo, pra calcular
		// de acordo com a data de fabricação
		if ($itemContado['data_validade']=="" || $itemContado['data_validade']=="0000-00-00") {
			// De 1 a 5, prazo em anos
			// De 6 a 12, prazo em meses
			// Maior de 12, prazo em dias
			$itemPrazo = dbQuery("SELECT I.prazo_validade
											FROM itens I
											JOIN itens_skus SK ON SK.id_itens=I.id
											WHERE I.exige_data_validade=1 AND SK.id=".$itemContado['id_itens_skus'])[0];
			if ($itemPrazo['prazo_validade']>0 || $gParam['PERFIL_FABRICANTE']['ativo']) {
				$prazoValidade = (int) $itemPrazo['prazo_validade'] ?: 2;

				if ($prazoValidade<6)
				{
					$prazoEm = "year";
				} elseif ($prazoValidade<13)
				{
					$prazoEm = "month";
				} else {
					$prazoEm = "day";
				}

				if (
					$itemContado['data_fabricacao'] != ""
					&& $itemContado['data_fabricacao'] != "0000-00-00"
				) {
					$camposItens['data_validade'] = date("Y-m-d",strtotime("+".$prazoValidade." ".$prazoEm,strtotime($itemContado['data_fabricacao'])));
				} else {
					$camposItens['data_validade'] = $itemContado['data_validade'];
				}
			}
		} else {
			$camposItens['data_validade']       = $itemContado['data_validade'];
		}
		$camposItens['id_programacao_itens']    = intval($itemProgramado['id']);
		$camposItens['codigo_externo']          = $itemContado['codigo_externo'];
		$camposItens['faturar']                 = '1';
		$camposItens['entrada']                 = '1';
		$camposItens['tipo']                    = '+';
		$camposItens['data']                    = date('Y-m-d H:i:s');

		if (intval($itemContado["id_notas_itens"])>0) {
			$camposItens['id_notas_itens']       = intval($itemContado['id_notas_itens']);
		} else {
			$camposItens['id_notas_itens']       = intval($itemProgramado['id_notas_itens']);
		}

		$bloqueada = (int) $itemContado['bloqueada'];
		$camposItens['quantidade']=$itemContado["quantidade"];
		$camposItens['bloqueada']= intval($bloqueada);
		$camposItens['avariada']= intval($avariada);

		// Caso existam dados tanto na OS quanto na contagem (pesos, lote, etc.), quem deve ser levado em conta?
		if ($gParam['USA_CONTAGEM_DADOS_OS']['ativo']==1)
		{
			$camposItens['lote']         = $itemContado['lote'] ?: $itemProgramado['lote'];
			$camposItens['peso_liquido'] = $itemContado['peso_liquido'] ?: floatval($itemProgramado['peso_liquido']);
			$camposItens['peso_bruto']   = $itemContado['peso_bruto'] ?: floatval($itemProgramado['peso_bruto']);
			$camposItens['m2']           = $itemContado['m2'] ?: floatval($itemProgramado['m2']);
			$camposItens['m3']           = $itemContado['m3'] ?: floatval($itemProgramado['m3']);
			$camposItens['valor']        = $itemContado['valor'] ?: floatval($itemProgramado['valor']);
			$camposItens['temperatura']  = $itemContado['temperatura'] ?: floatval($itemProgramado['temperatura']);
		} else {
			$camposItens['lote']         = $itemProgramado['lote'] ?: $itemContado['lote'];
			$camposItens['peso_liquido'] = $itemProgramado['peso_liquido'] ?: $itemContado['peso_liquido'];
			$camposItens['peso_bruto']   = $itemProgramado['peso_bruto'] ?: $itemContado['peso_bruto'];
			$camposItens['m2']           = $itemProgramado['m2'] ?: $itemContado['m2'];
			$camposItens['m3']           = $itemProgramado['m3'] ?: $itemContado['m3'];
			$camposItens['valor']        = $itemProgramado['valor'] ?: $itemContado['valor'];
			$camposItens['temperatura']  = $itemProgramado['temperatura'] ?: $itemContado['temperatura'];
		}

		// Se estiver cadastrado no item os campos de peso e metragem, devem ser priorizados
		$camposItens['peso_bruto'] = ($itemContado['ipeso_bruto'] ?: $camposItens['peso_bruto']);
		$camposItens['peso_liquido'] = ($itemContado['ipeso_liquido'] ?: $camposItens['peso_liquido']);
		$camposItens['m2'] = ($itemContado['im2'] ?: $camposItens['m2']);
		$camposItens['m3'] = ($itemContado['im3'] ?: $camposItens['m3']);
		dbInsert('umas_itens', $camposItens);
		atualizarAtivacaoUMA($camposItens['id_umas']);
	}


	function criaAlgumasUMAsNecessarias($quantidadeProgramada, $itemProgramado, $itemContado, $bloqueada=0, $avariada=0,$adicionandoItemNasMesmasUMAs=0, $umaUnica = 0)
	{
		global $usrId, $gParam, $id_armazens;
		$usrId = intval($usrId);
		$criou = true;
		$quantidadePorPalete = $itemContado['palete_lastro'] * $itemContado['palete_altura'];
		$quantidadeContada 	 = $quantidadeProgramada;
		if ($quantidadeContada>$quantidadePorPalete)
		{
			$quantidadeDePaletes = ceil($quantidadeContada / $quantidadePorPalete);
			$quantidadeUltimoPalete = $quantidadePorPalete;
			// Verifica se tem  quantidade diferente para o último palete
			if (($quantidadeContada / $quantidadePorPalete)<>$quantidadeDePaletes)
			{
				$quantidadeUltimoPalete = ($quantidadeContada-($quantidadeDePaletes-1)*$quantidadePorPalete);
			}
		} else {
			$quantidadeDePaletes=1;
			$quantidadeUltimoPalete = $quantidadeContada;
		}

		$umas_necessarias = $quantidadeDePaletes;
		if ($gParam['MODO_ENTRADA_UMA_UNICA']['ativo'] || $umaUnica)
		{
			$umas_necessarias = 1;
			$quantidadePorPalete = $quantidadeContada;
			$quantidadeUltimoPalete = $quantidadeContada;
		}

		$agora = date('Y-m-d H:i:s');
		$precisaCriarUMA = true;

		if ($itemContado['uma']<>'')
		{
			$precisaCriarUMA = false;
			$this->idUmas = array();
			$sql = "SELECT id
				FROM umas U
			    WHERE U.codigo_barras='".$itemContado['uma']."' OR U.id='".$itemContado['uma']."' LIMIT 1";
			$rst = dbQuery($sql);
			// Verifica se a UMA informada existe, se não
			if (count($rst)==0)
			{
				$criou = false;
				$this->erros[] = "UMA informada para adição deste item não existe!";
			} else {
				// Por via das dúvidas, ativa esta UMA
				$this->idUmas[]=$rst[0]['id'];
				dbQuery("UPDATE umas SET ativo=1, conferida = 1 WHERE id = ".$rst[0]['id']);
			}
		}

		// Adição do item atual na mesma UMA da mesma OS
		if ($adicionandoItemNasMesmasUMAs==1)
		{
			$precisaCriarUMA = false;
			$this->idUmas = array();

			// Busca item adicionado na primeira UMA
			$sql = "SELECT U.*, UI.id_itens_skus
					FROM umas U
					LEFT JOIN umas_itens UI ON U.id=UI.id_umas
					WHERE U.id_programacao=".$itemProgramado['id_programacao']." ORDER BY U.id DESC LIMIT 1";
			$rst = dbQuery($sql);
			$id_itens_skus = intval($rst[0]['id_itens_skus']);
			// Busca outros itens iguais em outras UMAs
			$sql = "SELECT U.*, UI.id_itens_skus
					FROM umas U
					LEFT JOIN umas_itens UI ON U.id=UI.id_umas
					WHERE U.id_programacao=".$itemProgramado['id_programacao']." AND UI.id_itens_skus=".$id_itens_skus." ORDER BY U.id";
			$rst = dbQuery($sql);
			foreach ($rst as $row)
			{
				// Registra cada UMA que compõe este item
				if ($id_itens_skus==$row['id_itens_skus'] && count($this->idUmas)<$quantidadeDePaletes)
				{
					$this->idUmas[]=$row['id'];
				} else {
					$id_itens_skus=-1; // pra não correr risco do mesmo item aparecer de novo em outras UMAs
				}
			}
			sort($this->idUmas);
			if ($quantidadeDePaletes>count($this->idUmas))
			{
				for ($i=0; $i<$quantidadeDePaletes-count($this->idUmas); $i++)
				{
					$this->idUmas[] = 0;
				}
			}
			$umas_necessarias = count($this->idUmas);
			if ($umas_necessarias==0)
			{
				$criou = false;
				$this->erros[] = "Não foi encontrada nenhuma UMA para adicionar este item!";
			}
		} else if ($adicionandoItemNasMesmasUMAs==2)
		{
			$precisaCriarUMA=false;
		}

		if ($criou)
		{
			for($c=0; $c<$umas_necessarias;$c++)
			{
				// INDICA POSIÇÃO PARA ESTA UMA...
				// Só ativa UMAs com registros válidos em umas_itens
				// Pra evitar de ativar UMAs que tem registros desfeitos
				$id_area_inicial=$this->os['id_areas'];
				$id_posicao_inicial=0;
				$posicionada = 0;
				// Obtém posição padrão definida na programação (pela área)
				if ($id_area_inicial>0)
				{
					$sql = "SELECT P.id, count(U.id) posicionado, P.quantidade
							FROM posicoes P
							LEFT JOIN umas U ON (U.ativo=1 AND (U.id_posicoes=P.id OR U.id_posicoes_posicionar=P.id))
							WHERE P.ativo=1 AND P.id_areas=".$id_area_inicial."
							GROUP BY P.id
							ORDER BY P.codigo_barras";
					$rsAreas = dbQuery($sql);
					foreach ($rsAreas as $rowAreas)
					{
						// Se não tiver nenhuma posição determinada com espaço
						// disponível, deixa em branco (não posicionado)
						if ($rowAreas['quantidade']>$rowAreas['posicionado'])
						{
							$id_posicao_inicial = $rowAreas['id'];
							$posicionada = 1;
							break;
						}
					}
				}
				$id_areas_direcionar=$this->os['id_areas_direcionar'];
				$id_posicao_destino=0;
				$sql="SELECT
						I.regra_posicionamento
					  FROM itens I
					  LEFT JOIN regra_posicionamento R ON R.codigo=I.regra_posicionamento
					  WHERE (I.regra_posicionamento <> '')
					  AND (I.regra_posicionamento IS NOT NULL)
					  AND (I.id=".intval($itemContado["id_itens"]).")
					  AND (R.cancelada=0)
					  AND (R.ativo=1)
					 ";
				$existe_regra=dbQuery($sql);
				if (count($existe_regra)>0 && intval($gParam["USAR_REGRA_POSICIONAMENTO"]["ativo"])==1)
				{
					$id_posicao_destino=$this->indicarProximaPosicaoRegraPosicionamento($itemContado['id_itens'], $itemContado["id_programacao"]);
				} else
				{
					// Obtém posição padrão definida na programação (pela área)
					if ($id_areas_direcionar>0)
					{
						$sql = "SELECT P.id, count(U.id) posicionado, P.quantidade
								FROM posicoes P
								LEFT JOIN umas U ON (U.ativo=1 AND (U.id_posicoes=P.id OR U.id_posicoes_posicionar=P.id))
								WHERE P.ativo=1 AND P.id_areas=".$id_areas_direcionar."
								GROUP BY P.id
								ORDER BY P.codigo_barras";
						$rsAreas = dbQuery($sql);
						foreach ($rsAreas as $rowAreas)
						{
							// Se não tiver nenhuma posição determinada com espaço
							// disponível, deixa em branco (não posicionado)
							if ($rowAreas['quantidade']>$rowAreas['posicionado'])
							{
								$id_posicao_destino = $rowAreas['id'];
								break;
							}
						}
					}
					// A posição indicada preferencial é a da programação
					// Se não tiver nenhuma apontada lá, usa o do cadastro do item (indicaPosicao)
					if ($id_posicao_destino == 0)
					{

						$id_posicao_destino = $this->indicaPosicao($itemContado['id_itens']);
					}
				}

				if ((intval($gParam["INDICAR_AREA_INICIAL"]["ativo"])==1) && ($id_posicao_inicial == 0))
				{
					// A posição inicial será baseado no valor do parametro, lembrando que o valor do parametro deve ser o id da área.
					$id_posicao_inicial=$this->indicaPosicaoLivreEmArea($gParam["INDICAR_AREA_INICIAL"]["valor"]);
				}
				if ($precisaCriarUMA)
				{
					$criou = true;
					$campos = array();
					$campos['ativo']                  = 1;
					$campos['conferida']              = 1;
					$campos['id_tipos_umas']          = 1;
					$campos['id_posicoes']            = $id_posicao_inicial;
					$campos['id_pessoas_posicionou']  = intval($_SESSION["usrId"]);
					$campos['id_posicoes_posicionar'] = $id_posicao_destino;
					$campos['id_programacao']         = $this->os['id'];
					$campos['id_programacao_itens']   = $itemProgramado['id'];
					$campos['id_armazens']            = $this->os['id_armazens'];
					$campos['posicionada']            = $posicionada;
					$campos['data']                   = $agora;
					$campos['data_conferencia']       = $agora;
					$campos['id_contagens']           = $itemContado['id_contagens'];
					if ($itemContado["situacao"]=="indivisivel")
					{
						$campos["indivisivel"]=1;
					}

					if (intval($itemContado["id_notas"])>0) {
						$campos['id_notas'] = (int) $itemContado["id_notas"];
					} else {
						$campos['id_notas'] = (int) $this->campos['id_notas'];
					}

					$id = $this->criaUMA($campos);
				} else {
					$id = $this->idUmas[$c];
					if ($id == 0)
					{
						$criou = true;
						$campos['ativo']                  = 1;
						$campos['conferida']              = 1;
						$campos['id_tipos_umas']          = 1;
						$campos['id_posicoes']            = $id_posicao_inicial;
						$campos['id_posicoes_posicionar'] = $id_posicao_destino;
						$campos['id_programacao']         = $this->os['id'];
						$campos['id_programacao_itens']   = $itemProgramado['id'];
						$campos['id_armazens']            = $this->os['id_armazens'];
						$campos['posicionada']            = $posicionada;
						$campos['data']                   = $agora;
						$campos['data_conferencia']       = $agora;
						$campos['id_contagens']           = $itemContado['id_contagens'];
						$campos['id_notas']               = (int) $this->campos['id_notas'];

						if ($itemContado["situacao"] == "indivisivel") {
							$campos["indivisivel"] = 1;
						}

						$id = $this->criaUMA($campos);
					}
				}

				// Cria itens da UMA (normalmente só um)
				$camposItens = array();
				$camposItens['id_umas']                 = $id;
				$camposItens['id_programacao']          = $itemContado['id_programacao'];
				$camposItens['id_pessoas_proprietario'] = $itemContado['id_pessoas_proprietario'];
				$camposItens['id_pessoas_criou']        = intval($usrId);
				$camposItens['id_armazens']             = $itemContado['id_armazens'];
				$camposItens['id_veiculos_acessos']     = $itemContado['id_veiculos_acessos'];
				$camposItens['id_itens_skus']           = $itemContado['id_itens_skus'];
				$camposItens['data_fabricacao']         = $itemContado['data_fabricacao'];
				// Se a data de validade não for informada, busca do cadastro do item o prazo, pra calcular
				// de acordo com a data de fabricação
				if ($itemContado['data_validade']=="" || $itemContado['data_validade']=="0000-00-00") {
					// De 1 a 5, prazo em anos
					// De 6 a 12, prazo em meses
					// Maior de 12, prazo em dias
					$itemPrazo = dbQuery("SELECT I.prazo_validade
											FROM itens I
											JOIN itens_skus SK ON SK.id_itens=I.id
											WHERE I.exige_data_validade=1 AND SK.id=".$itemContado['id_itens_skus'])[0];
					if ($itemPrazo['prazo_validade']>0 || $gParam['PERFIL_FABRICANTE']['ativo']) {
						$prazoValidade = (int) $itemPrazo['prazo_validade'] ?: 2;

						if ($prazoValidade<6)
						{
							$prazoEm = "year";
						} elseif ($prazoValidade<13)
						{
							$prazoEm = "month";
						} else {
							$prazoEm = "day";
						}

						if (
							$itemContado['data_fabricacao'] != ""
							&& $itemContado['data_fabricacao'] != "0000-00-00"
						) {
							$camposItens['data_validade'] = date("Y-m-d",strtotime("+".$prazoValidade." ".$prazoEm,strtotime($itemContado['data_fabricacao'])));
						} else {
							$camposItens['data_validade'] = $itemContado['data_validade'];
						}
					}
				} else {
					$camposItens['data_validade']       = $itemContado['data_validade'];
				}
				$camposItens['id_programacao_itens']    = intval($itemProgramado['id']);
				$camposItens['codigo_externo']          = $itemContado['codigo_externo'];
				$camposItens['faturar']                 = '1';
				$camposItens['entrada']                 = '1';
				$camposItens['tipo']                    = '+';
				$camposItens['data']                    = $agora;

				if (intval($itemContado["id_notas_itens"])>0) {
					$camposItens['id_notas_itens']      = intval($itemContado['id_notas_itens']);
				} else {
					$camposItens['id_notas_itens']      = intval($itemProgramado['id_notas_itens']);
				}

				$bloqueada = (int) ($itemContado['bloqueada']);

				if ($c < $umas_necessarias - 1) {
					$camposItens['quantidade'] = $quantidadePorPalete;
				} else {
					$camposItens['quantidade'] = $quantidadeUltimoPalete;
				}
				$camposItens['bloqueada'] = intval($bloqueada);
				$camposItens['avariada']  = intval($avariada);
				// Caso existam dados tanto na OS quanto na contagem (pesos, lote, etc.), quem deve ser levado em conta?
				if ($gParam['USA_CONTAGEM_DADOS_OS']['ativo'] == 1) {
					$camposItens['lote']         = $itemContado['lote'] ?: $itemProgramado['lote'];
					$camposItens['peso_liquido'] = $itemContado['peso_liquido'] > 0 ? $itemContado['peso_liquido'] : floatval($itemProgramado['peso_liquido']);
					$camposItens['peso_bruto']   = $itemContado['peso_bruto'] > 0 ? $itemContado['peso_bruto'] : floatval($itemProgramado['peso_bruto']);
					$camposItens['m2']           = $itemContado['m2'] > 0 ? $itemContado['m2'] : floatval($itemProgramado['m2']);
					$camposItens['m3']           = $itemContado['m3'] > 0 ? $itemContado['m3'] : floatval($itemProgramado['m3']);
					$camposItens['valor']        = $itemContado['valor'] > 0 ? $itemContado['valor'] : floatval($itemProgramado['valor']);
					$camposItens['temperatura']  = $itemContado['temperatura'] > 0 ? $itemContado['temperatura'] : floatval($itemProgramado['temperatura']);
				} else {
					$camposItens['lote']         = $itemProgramado['lote'] ?: $itemContado['lote'];
					$camposItens['peso_liquido'] = $itemProgramado['peso_liquido'] > 0 ? floatval($itemProgramado['peso_liquido']) : $itemContado['peso_liquido'];
					$camposItens['peso_bruto']   = $itemProgramado['peso_bruto'] > 0 ? floatval($itemProgramado['peso_bruto']) : $itemContado['peso_bruto'];
					$camposItens['m2']           = $itemProgramado['m2'] > 0 ? floatval($itemProgramado['m2']) : $itemContado['m2'];
					$camposItens['m3']           = $itemProgramado['m3'] > 0 ? floatval($itemProgramado['m3']) : $itemContado['m3'];
					$camposItens['valor']        = $itemProgramado['valor'] > 0 ? floatval($itemProgramado['valor']) : $itemContado['valor'];
					$camposItens['temperatura']  = $itemProgramado['temperatura'] > 0 ? floatval($itemProgramado['temperatura']) : $itemContado['temperatura'];
				}

				// Se estiver cadastrado no item os campos de peso e metragem, devem ser priorizados
				$camposItens['peso_bruto'] = ($itemContado['ipeso_bruto']>0 ? $itemContado['ipeso_bruto'] : $camposItens['peso_bruto']);
				$camposItens['peso_liquido'] = ($itemContado['ipeso_liquido']>0 ? $itemContado['ipeso_liquido'] : $camposItens['peso_liquido']);
				$camposItens['m2'] = ($itemContado['im2']>0 ? $itemContado['im2'] : $camposItens['m2']);
				$camposItens['m3'] = ($itemContado['im3']>0 ? $itemContado['im3'] : $camposItens['m3']);

				dbInsert('umas_itens', $camposItens);
				atualizarAtivacaoUMA($camposItens['id_umas']);

				if ($this->os['id_tipos_programacao'] == 9) // Cross docking
				{
					$camposItens['entrada'] = '0';
					$camposItens['saida']   = '1';
					$camposItens['tipo']    = '-';
					$camposItens['quantidade'] = -$camposItens['quantidade'];
					dbInsert('umas_itens', $camposItens);
					atualizarAtivacaoUMA($camposItens['id_umas']);
				}
				$this->umas[] = $this->uma;

				dbFastQuery("UPDATE programacao_itens SET quantidade_aceita = quantidade_aceita + '" . $camposItens['quantidade'] ."', conferida = '1' WHERE id = '" . $itemProgramado['id'] . "'");

				// Salva movimento de associação de carga à UMA
				$mtz=array();
				$mtz["id_umas_para"]=0;
				$mtz["id_pessoas_proprietario"]=$itemContado['id_pessoas_proprietario'];
				$mtz["tipo"]='E';
				$mtz["id_posicoes"]=$id_posicao_inicial;
				$mtz["lote"]=$camposItens['lote'];
				$mtz["data_fabricacao"]=$camposItens['data_fabricacao'];
				$mtz["data_validade"]=$camposItens['data_validade'];
				$mtz["descricao"]="Entrada";

				$idRastreio = $this->salvaUMAMovimentos($id, $mtz);

				// Específico para a BOMIX!
				// Substitui o NUMSEQ pela UMA nas OS de Paletização ao dar entrada
				if ($gParam['PERFIL_FABRICANTE']['ativo'] && $gParam['MODO_ENTRADA_UMA_UNICA']['ativo'])
				{
					$sql = "SELECT * FROM umas WHERE id=".$id;
					$uma = dbQuery($sql)[0]['codigo_barras'];
					if ($uma<>"")
					{
						$sql = "SELECT id FROM programacao WHERE id_tipos_programacao=15 AND numseq='".$itemContado['codigo_externo']."'";
						$rst = dbQuery($sql);
						foreach ($rst as $row)
						{
							$sql = "UPDATE programacao_itens SET uma='".$uma."' WHERE id_programacao=".$row['id'];
							dbQuery($sql);
						}
					}
				}
			}
		}
		return($criou);
	}

	function criaEntradaFracionada($itemContado, $idProgramacao, $idUma=0)
	{
		$sql = "SELECT PI.* FROM programacao_itens PI
					LEFT JOIN itens_skus SK ON PI.id_itens_skus=SK.id
					WHERE PI.id_programacao='{$idProgramacao}' AND PI.id_itens_skus=".$itemContado['id_programacao_itens'];
		$itemProgramado = dbQuery($sql)[0];
		$processarQuantidade  = $itemContado['quantidade'];
		$quantidadeProgramada = $itemProgramado['quantidade'];
		$criou=false;
		$bloqueada=0;
		$avariada=$itemContado['avariada'];
		if ($idUma>0)
		{
			$this->idUmas[]=$idUma;
			$criou=$this->criaAlgumasUMAsNecessarias($processarQuantidade, $itemProgramado, $itemContado, $bloqueada, $avariada, 2);
		} else
		{
			$criou=$this->criaAlgumasUMAsNecessarias($processarQuantidade, $itemProgramado, $itemContado, $bloqueada, $avariada);
		}
		if ($criou)
		{
			$sqlu="UPDATE programacao_itens set quantidade_conferida=".floatval($itemProgramado['quantidade'])." WHERE id=".$itemContado['id_programacao_itens'];
			dbQuery($sqlu);
		}
		return($criou);
	}


	/**
	* Cria UMAs necessárias para atender a uma OS na entrada
	*/
	function criaUMAsNecessarias($itemContado, $adicionandoItemNasMesmasUMAs, $umaUnica = 0)
	{
		if (intval($itemContado["id_programacao_itens"]))
		{
			$where="PI.id=".$itemContado["id_programacao_itens"];
		} else
		{
			$where="PI.id_programacao=".$this->os["id"]." AND PI.id_itens_skus=".$itemContado["id_itens_skus"];
		}
		$sql = "SELECT PI.* FROM programacao_itens PI
					LEFT JOIN itens_skus SK ON PI.id_itens_skus=SK.id
					WHERE {$where}";
		$itemProgramado = dbQuery($sql)[0];
		$processarQuantidade = $itemContado['quantidade'];
		$quantidadeProgramada = $itemProgramado['quantidade'];
		$criou = false;

		if ($quantidadeProgramada==0)
		{
			// Item não foi programado mas veio na contagem
			$bloqueada = 1;
			$avariada = $itemContado['avariada'];
			$criou = $this->criaAlgumasUMAsNecessarias($processarQuantidade, $itemProgramado, $itemContado, $bloqueada, $avariada, $adicionandoItemNasMesmasUMAs, $umaUnica);

		}
		elseif ($processarQuantidade<=$quantidadeProgramada)
		{
			// Quantidade menor do que o previsto, aceita tudo normal
			#entra aqui
			$bloqueada = 0;

			if($itemContado['bloqueada'] == 1){
				$bloqueada = 1;
			}

			$avariada  = $itemContado['avariada'];
			$criou = $this->criaAlgumasUMAsNecessarias($processarQuantidade, $itemProgramado, $itemContado, $bloqueada, $avariada, $adicionandoItemNasMesmasUMAs, $umaUnica);
		} else {
			// Quantidade maior do que o previsto, até o previsto é normal, demais, bloqueados
			$quantidadePorPalete  = $itemContado['palete_lastro'] * $itemContado['palete_altura'];
			$quantidadeDePaletes  = intval($quantidadeProgramada / $quantidadePorPalete);
			if ($quantidadeDePaletes < $quantidadeProgramada / $quantidadePorPalete)
			{
				$quantidadeDePaletes++;
			}
			$umas_necessarias = $quantidadeDePaletes;
			$bloqueada = 0;
			$avariada = $itemContado['avariada'];
			$criou = $this->criaAlgumasUMAsNecessarias($quantidadeProgramada, $itemProgramado, $itemContado, $bloqueada, $avariada, $adicionandoItemNasMesmasUMAs, $umaUnica);

			$quantidadeProgramada = ($processarQuantidade-$quantidadeProgramada);
			$quantidadePorPalete = $itemContado['palete_lastro'] * $itemContado['palete_altura'];
			$quantidadeDePaletes = intval($quantidadeProgramada / $quantidadePorPalete);
			if ($quantidadeDePaletes < $quantidadeProgramada / $quantidadePorPalete)
			{
				$quantidadeDePaletes++;
			}
			$umas_necessarias = $quantidadeDePaletes;
			$bloqueada = 1;
			$avariada = $itemContado['avariado'];
			/* ajustar quantidade programada */
			$criou = $this->criaAlgumasUMAsNecessarias($quantidadeProgramada, $itemProgramado, $itemContado, $bloqueada, $avariada, $adicionandoItemNasMesmasUMAs, $umaUnica);
		}
		return($criou);
	}

	/**
	 * ENTRADA: Aceita a contagem de um item na entrada
	 *
	 * A cada item, esta rotina atualiza a quantidade_aceita e confirma isto.
	 * Além disto, cria as UMAs necessárias (re-calculando antes)
	 * Caso já tenha feito em todos os itens, finaliza a OS e ativa as UMAs
	 */
	function aceitaContagemEntradaItem($id_programacao,$id_contagens_umas=0, $adicionandoItemNasMesmasUMAs=0,$vaiFinalizarOs=false, $umaUnica = 0)
	{
		global $usrId, $gId;
		$usrId = intval($usrId);
		// Busca dados da OS se não já tiver sido buscado
		if (count($this->os)<=1)
		{
			$this->filtro= "p.id=".$id_programacao;
			$this->os = $this->obtemRegistros()[0];
		}
		// $sql="SELECT * FROM programacao WHERE id=".$id_programacao;
		// $programacao=(dbQuery($sql)[0]);
		$sql = "SELECT U.*,
					C.situacao,
					C.id idc, C.id_contagens, CNT.contagem,
					IF(C.uma <> '', C.uma, C.codigo_barras) AS uma,
					I.nome nomeItem,
					SK.codigo,
					UN.descricao unidade,
					SK.quantidade quantidade_sku,
					SK.id_itens,
					I.id id_itens,
					U.avariada,
					U.bloqueada,
					U.data_fabricacao,
					U.data_validade,
					U.lote,
					SK.palete_lastro,
					SK.palete_altura,
					SK.altura,
					SK.peso_bruto ipeso_bruto,
					SK.peso_liquido ipeso_liquido,
					(SK.largura/100*SK.comprimento/100) im2,
					(SK.largura/100*SK.comprimento/100*SK.altura/100) im3,
					SK.palete_lastro, SK.altura
				FROM contagens_umas_itens U
				LEFT JOIN contagens_umas C ON C.id=U.id_contagens_umas
				LEFT JOIN contagens CNT ON CNT.id=C.id_contagens
				LEFT JOIN itens_skus SK ON U.id_itens_skus = SK.id
				LEFT JOIN itens I ON I.id=SK.id_itens
				LEFT JOIN unidades UN ON UN.id=SK.id_unidades
				WHERE C.ativo=1 AND U.id_programacao=".$id_programacao." ORDER BY U.id";
		$rsi = dbQuery($sql);
		$id_contagens_umas = $vaiFinalizarOs === true ? 0 : $id_contagens_umas;
		foreach ($rsi as $itemContado)
		{
			// Só cria UMAs do item selecionado ou cria todas se for informado 0
			if (($id_contagens_umas>0 && $itemContado['idc']==$id_contagens_umas) || ($id_contagens_umas==0))
			{

				if ($this->os['id_tipos_entrada'] == 3) { // 3 = Entrada com UMA Virgem
					$umaUnica = 1;
				}

				// Cria UMAs necessárias
				$criou = $this->criaUMAsNecessarias($itemContado, $adicionandoItemNasMesmasUMAs, $umaUnica);
				if ($criou)
				{
					// Marca contagem já aceita...
					$sql = "UPDATE contagens_umas SET ativo=0 WHERE id=".$itemContado['idc'];
					dbQuery($sql);

					if ($this->registrarAtividadeAceite) {
						$sql = "
							INSERT INTO programacao_atividades (
								id_programacao,
								id_pessoas,
								id_tipos_atividades,
								data,
								id_itens_skus,
								quantidade,
								descricao
							) VALUES (
								{$gId},
								{$usrId},
								13,
								NOW(),
								" . ((int) $itemContado['id_itens_skus']) . ",
								" . ((float) $itemContado['quantidade']) . ",
								'" . substr('Aceitou contagem ' . $itemContado['codigo'], 0, 60) . "'"
							. ")";
						dbFastQuery($sql);
					}
				}
			}
		}

		if ($criou)
		{
			$sql = "SELECT * FROM programacao_itens WHERE id_programacao=".$id_programacao;
			$rsp = dbQuery($sql);
			$temAlgumSemConferir = false;
			$teveDivergencia = 0;
			foreach($rsp as $row)
			{
				if ($row['quantidade']<>$row['quantidade_conferida'])
				{
					$teveDivergencia=true;
				}
				if ($row['conferida']==0)
				{
					$temAlgumSemConferir=true;
				}
			}

			if ($teveDivergencia)
			{
				// Salva informação de que houve divergencia nesta OS
				$sql = "UPDATE programacao SET divergencia=1 WHERE id=".$id_programacao;
				dbQuery($sql);
			} else {
				$sql = "UPDATE programacao SET divergencia=0 WHERE id=".$id_programacao;
				dbQuery($sql);

			}
		}


		// Não inserir entrada dinâmica pois exige uma conferência.
		if (intval($this->os["id_tipos_entrada"])==1)
		{
			$this->inserirEventoNaFila('{id_tipos_eventos:2; id_programacao:'.$id_programacao.';}');
		}
		return($criou);
	}


	public function validarObrigatoriedadeUMA()
	{
		/* Se validar UMA ou proprietario nao usa o recurso retorna verdadeiro*/
		$sql = "
			SELECT exige_uma_entrada_convencional
			FROM pessoas_juridicas
			WHERE id_pessoas = " . $this->os['id_pessoas_proprietario'];

		return (int) dbQuery($sql)[0]['exige_uma_entrada_convencional'];
	}


	public function validarUmaExiste()
	{
		$sql = "
			SELECT
				umas.id
			FROM
				umas
			WHERE
				umas.codigo_barras = '" . gCleanField($this->campos['adicionar_a_uma']) . "'";
		return dbQuery($sql)[0]['id'];
	}


	public function verificarSeUmaEhVirgem($codigoBarrasUma)
	{
		//UMAs com movimentacoes canceladas estao sendo consideradas por causa de erros de contagem do conferente na entrada
		//senao teria que emitir outras etiquetas
		$sql = "
			SELECT
				COUNT(umas_itens.id) quantidade_movimentos
			FROM
				umas
			LEFT JOIN umas_itens ON
				umas_itens.id_umas = umas.id
			WHERE
				umas.codigo_barras = '" . gCleanField($codigoBarrasUma) . "'
				AND umas_itens.cancelada = 0";

		return !((bool) dbQuery($sql)[0]['quantidade_movimentos']);
	}


	function aceitaEntradaAntiga($gId, $whereDefault, $totalProgramado)
	{
		global $gParam, $usrId, $o;
		$usrId = intval($usrId);
		$return=array();
		$divergencia=false;
		$htm="";

		$sql="SELECT * FROM programacao WHERE id=$gId";
		$programacao=dbQuery($sql);

		//global $gParam;
		$sql="SELECT
                    CUI.id_notas_itens, CUI.id_programacao_itens, CUI.lote, CUI.data_fabricacao, CUI.data_validade, SUM(CUI.quantidade) quantidade, CUI.id_itens_skus,
                    COUNT(DISTINCT(C.contagem)) ttlContagem,
                    P.id id_programacao,
                    CUI.id_pessoas_criou
              FROM contagens_umas_itens CUI
              LEFT JOIN contagens_umas CU ON CU.id = CUI.id_contagens_umas
              LEFT JOIN contagens C ON C.id = CU.id_contagens
              LEFT JOIN programacao_itens PI ON PI.id = CUI.id_programacao_itens
              LEFT JOIN programacao P ON P.id = PI.id_programacao
              WHERE {$whereDefault}
              GROUP BY
                    CUI.id_pessoas_criou, P.id, CUI.id_notas_itens, CUI.id_programacao_itens, CUI.lote, CUI.data_fabricacao, CUI.data_validade, CUI.quantidade, CUI.id_itens_skus
              ORDER BY CUI.id DESC";

        $contados=dbQuery($sql);
        $liberar=true;
        $totalContagens=1;
        $falhasDeContagem=array();
        foreach ($contados as $contado)
        {
        	if ($contado["ttlContagem"] < $totalContagens)
            {
                if ($gParam['PERMITIR_SALDO_OS_DIVERGENTE_UMA_VIRGEM']['ativo']==0)
                {

                   $quantidadeProgramado=gFieldById("programacao_itens", $contado["id_programacao_itens"], "quantidade");
                	if ($quantidadeProgramado <> $contado["quantidade"])
                	{
                		$liberar=false;
                		$falhasDeContagem[]="As contagens não conferem com o programado. O sistema está condigurado para não permitir contagem divergente da programação.";
                 	}
                   $liberar=false;
                }
            }
        }




        if (!$liberar)
        {
        	/* Contagem não confere com o programado */
            $htm=$o->msgWarning("A operação não pode continuar pois aconteceram os seguintes erros: ", $o->ul($falhasDeContagem));
        } else
        {
        	$programacao=(dbQuery("SELECT * FROM programacao WHERE id='".$contados[0]["id_programacao"]."'")[0]);
		    foreach ($contados as $contado)
        	{
        		$where=array();
		        $where[]="(CU.ativo='1')";
		        $where[]="(CUI.id_programacao=".$contado["id_programacao"].")";
		        $where[]="(CUI.id_pessoas_criou=".$contado["id_pessoas_criou"].")";
        		$where[]="(CUI.id_programacao_itens='".$contado["id_programacao_itens"]."')";
        		$where[]="(CUI.id_notas_itens='".$contado["id_notas_itens"]."')";
        		$where=implode(" AND ", $where)." AND ({$whereDefault})";

        		/* Recuperando as umas da contagem */
        		$sql="SELECT
                    CU.codigo_barras, CUI.lote, CUI.data_fabricacao, CUI.data_validade, CUI.quantidade, CUI.id_itens_skus,
                    COUNT(C.contagem) ttlContagem
	              FROM contagens_umas_itens CUI
	              LEFT JOIN contagens_umas CU ON CU.id = CUI.id_contagens_umas
	              LEFT JOIN contagens C ON C.id = CU.id_contagens
	              WHERE {$where}
	              GROUP BY
	                    CU.codigo_barras, CUI.lote, CUI.data_fabricacao, CUI.data_validade, CUI.quantidade, CUI.id_itens_skus
	              ORDER BY CUI.id DESC
	        	";
	        	$contagens=dbQuery($sql);


		       	/* Checar se todas as contagens estão aptas antes de aceitar*/
	        	foreach ($contagens as $contagem)
	            {
	            	/* Recuperando itens */
	                $where=array();
	                $where[]="(CU.codigo_barras='".$contagem["codigo_barras"]."')";
	                $where[]="(CUI.lote='".$contagem["lote"]."')";
	                $where[]="(CUI.data_fabricacao='".$contagem["data_fabricacao"]."')";
	                $where[]="(CUI.data_validade='".$contagem["data_validade"]."')";
	                $where[]="(CUI.id_itens_skus='".$contagem["id_itens_skus"]."')";
	                $where[]="(CUI.quantidade='".$contagem["quantidade"]."')";
	                $where[]="(CUI.id_programacao='".$contado["id_programacao"]."')";
	                $where[]="(CUI.id_programacao_itens='".$contado["id_programacao_itens"]."')";
	                $where[]="(CUI.id_pessoas_criou='".$contado["id_pessoas_criou"]."')";
	                $where[]="(CU.ativo='1')";
	                $where[]="(CUI.quantidade_temp=0 OR CUI.quantidade_temp IS NULL)";
	                $where=implode(" AND ", $where);

	                /* Pode ser que venha 2 registro mas considerar qualquer 1 neste caso dá no mesmo. */
	                $sql="SELECT
	                        CUI.*,
	                        CU.id_contagens,
	                        CU.id_notas,
	                        CU.codigo_barras,
	                        SK.peso_liquido i_peso_liquido,
	                        SK.peso_bruto i_peso_bruto,
	                        PI.id id_programacao_itens,
	                        PI.peso_bruto p_peso_bruto,
	                        PI.peso_liquido p_peso_liquido,
	                        PI.lote p_lote,
	                        PI.valor p_valor,
	                        PI.data_fabricacao p_data_fabricacao,
	                        PI.data_validade p_data_validade
	                      FROM contagens_umas_itens CUI
	                      LEFT JOIN contagens_umas CU ON CU.id = CUI.id_contagens_umas
	                      LEFT JOIN itens_skus SK ON SK.id = CUI.id_itens_skus
	                      LEFT JOIN programacao_itens PI ON PI.id = CUI.id_programacao_itens
	                      WHERE {$where} LIMIT 1
	                ";
	                $itemContado=(dbQuery($sql)[0]);
	                $idPosicaoInicial=$this->definePosicaoInicial($programacao["id_areas"]);
					$idPosicaoDestino=$this->definePosicaoDestino($programacao["id_areas_direcionar"]);


	                $uma=(dbQuery("SELECT id FROM umas WHERE codigo_barras='".$itemContado["codigo_barras"]."'")[0]);
	                if ($uma["id"]>0)
	                {
	                    $mtz=array();
	                    $mtz["id_posicoes"]=intval($idPosicaoInicial);
	                    $mtz["id_posicoes_posicionar"]=intval($idPosicaoDestino);
	                    $mtz["id_notas"]=intval($itemContado["id_notas"]);
	                    $mtz["id_programacao"]=intval($gId);
	                    $mtz["id_armazens"]=intval($programacao["id_armazens"]);
	                    $mtz["id_contagens"]=intval($contagem["id_contagens"]);
	                    $mtz["data"]=date('Y-m-d H:i:s');
	                    $mtz["data_posicionamento"] = date('Y-m-d H:i:s');
	                    $mtz["id_tipos_umas"]=1;
	                    $mtz["ativo"]=1;
	                    dbUpdate("umas", $mtz, $uma["id"]);

	                    $mtz=array();
	                    $mtz['id_umas']=intval($uma["id"]);
	                    $mtz['id_pessoas_proprietario']=intval($itemContado["id_pessoas_proprietario"]);
	                    $mtz['id_programacao']=intval($gId);
	                    $mtz['id_pessoas_criou']=intval($usrId);
	                    $mtz['id_armazens']=intval($programacao["id_armazens"]);
	                    $mtz['id_veiculos_acessos']=intval($itemContado["id_veiculos_acessos"]);
	                    $mtz['id_itens_skus']= intval($itemContado["id_itens_skus"]);
	                    $mtz['data_validade']= trim($itemContado['data_validade']);
	                    $mtz['data_fabricacao']=trim($itemContado["data_fabricacao"]);
	                    $mtz['serial']=trim($itemContado['serial']);
	                    $mtz['id_programacao_itens']=intval($itemContado["id_programacao_itens"]);
	                    $mtz['id_notas_itens']=intval($itemContado["id_notas_itens"]);
	                    $mtz['faturar']='1';
	                    $mtz['tipo']= '+';
	                    $mtz['reservada']=0;
	                    $mtz['separada']=0;
	                    $mtz['avariada']=0;
	                    $mtz['bloqueada']=0;
	                    $mtz['data']=date('Y-m-d H:i:s');

	                    // Item contado que não está na programação
	                    if (intval($itemContado["id_programacao_itens"])==0)
	                    {
	                    	/*
								Como o item não está na programação,
	                        	neste caso houve divergência
	                        */
	                        $divergencia="Divergência, Item não encontrado na programação.";
	                        $mtz["lote"]=trim($itemContado["lote"]);
	                        $mtz["peso_liquido"]=floatval($itemContado["peso_liquido"]);
	                        $mtz["peso_bruto"]=floatval($itemContado["peso_bruto"]);
	                        $mtz["data_validade"]=trim($itemContado["data_validade"]);
	                        $mtz["data_fabricacao"]=trim($itemContado["data_validade"]);
	                        $mtz["m2"]=floatval($itemContado["m2"]);
	                        $mtz["m3"]=floatval($itemContado["m3"]);
	                        $mtz["quantidade"]=floatval($itemContado["quantidade"]);
	                        $mtz["bloqueada"]=1;
	                    } else
	                    {
							// Cliente fiscal e OS sem nota bloquear
							$proprietarioFiscal = dbQuery("SELECT fiscal FROM pessoas_juridicas WHERE id_pessoas = " . $programacao["id_pessoas_proprietario"])[0]['fiscal'];
	                    	if ($proprietarioFiscal == 1)
	                    	{
	                    		if ($itemContado["id_notas_itens"]==0)
	                    		{
	                    			$mtz["bloqueada"]=1;
	                    		}
	                    	}

	                        /* Recuperar item programado */
	                        $sql="SELECT PI.* FROM programacao_itens PI
	                              LEFT JOIN itens_skus SK ON SK.id=PI.id_itens_skus
	                              WHERE PI.id='".$itemContado["id_programacao_itens"]."'";
	                        $itemProgramado=(dbQuery($sql)[0]);
	                        if ($gParam['USA_CONTAGEM_DADOS_OS']['ativo']==1)
	                        {
	                            /* Este parametro dá privilégio aos dados da OS mas caso não tenha pegar oque foi contado */
	                            $mtz['lote'] = empty($itemProgramado["lote"]) && is_null($itemProgramado["lote"])
	                                    ? trim($itemProgramado["lote"])
	                                    : trim($itemContado["lote"]);

	                            $mtz['peso_liquido'] = intval($itemProgramado["peso_liquido"]) > 0
	                                    ? floatval($itemProgramado["peso_liquido"])
	                                    : floatval($itemContado['peso_liquido']);

	                            $mtz['peso_bruto']=intval($itemProgramado["peso_bruto"]) > 0
	                                    ? floatval($itemProgramado['peso_bruto'])
	                                    : floatval($itemContado['peso_bruto']);

	                            $mtz['m2'] = intval($itemProgramado['m2']) > 0
	                                    ? floatval($itemProgramado['m2'])
	                                    : floatval($itemContado['m2']);

	                            $mtz['m3']= intval($itemProgramado['m3']) > 0
	                                    ? floatval($itemProgramado['m3'])
	                                    : floatval($itemContado['m3']);

	                            $mtz['valor']=round($itemProgramado['valor'], 10) > 0
	                                    ? floatval($itemProgramado['valor'])
	                                    : floatval($itemContado['valor']);
	                            //$mtz['temperatura']=intval($itemProgramado['temperatura'])>0
	                            //       ? floatval($itemProgramado['temperatura'])
	                            //        : floatval($itemContado['temperatura']);
	                        } else {
	                            $mtz['lote']= !empty($itemContagem['lote']) && !is_null($itemContagem["lote"])
	                                    ? trim($itemContado['lote'])
	                                    : trim($itemProgramado['lote']);

	                            $mtz['peso_liquido'] = intval($itemContagem['peso_liquido']) > 0
	                                    ? floatval($itemContado['peso_liquido'])
	                                    : floatval($itemProgramado['peso_liquido']);

	                            $mtz['peso_bruto']= intval($itemContagem['peso_bruto']) > 0
	                                    ? floatval($itemContagem['peso_bruto'])
	                                    : floatval($itemProgramado['peso_bruto']);

	                            $mtz['m2']= intval($itemContado['m2']) > 0
	                                    ? floatval($itemContado['m2'])
	                                    : floatval($itemProgramado['m2']);

	                            $mtz['m3']=intval($itemContado['m3']) > 0
	                                    ? floatval($itemContado['m3'])
	                                    : floatval($itemProgramado['m3']);

	                            $mtz['valor']=round($itemContado['valor'], 10) > 0
	                                    ? floatval($itemContado['valor'])
	                                    : floatval($itemProgramado['valor']);

	                            $mtz['temperatura']=intval($itemContado['temperatura']) > 0
	                                    ? floatval($itemContagem['temperatura'])
	                                    : floatval($itemProgramado['temperatura']);
	                        }
	                    }
	                    $quantidadeContada+=$itemContado["quantidade"];
                        if ($quantidadeContada>$totalProgramado)
                        {
                        	$divergencia=1;
                        	$mtz["bloqueada"]=1;
                        }
                        $mtz["quantidade"]=floatval($itemContado["quantidade"]);

	                    dbInsert('umas_itens', $mtz);
						atualizarAtivacaoUMA($mtz['id_umas']);

	                    /* Atualizar quantidade conferida do item programado. */
                        $sql="UPDATE programacao_itens SET quantidade_aceita=quantidade_aceita+".floatval($itemContado["quantidade"]).", conferida='1' WHERE id='".$itemContado["id_programacao_itens"]."'";
						dbQuery($sql);
	                  	dbQuery("UPDATE contagens_umas_itens SET quantidade_temp=1 WHERE id=".$itemContado["id"]);
	                    // Verificar se pode desabilitar a contagem
	                    $sql="SELECT * FROM contagens_umas_itens WHERE id_contagens_umas=".$itemContado["id_contagens_umas"]." AND (quantidade_temp=0 OR quantidade_temp IS NULL)";
	                    $verifica=dbQuery($sql);
	                    if (count($verifica)==0)
	                    {
	                    	 //Desativar contagem
		                    $mtz=array();
		                    $mtz["ativo"]=0;
	                    	dbUpdate("contagens_umas", $mtz, $itemContado["id_contagens_umas"]);
	                    }
	                }
	            }
        	}
        }
        $return["html"]=$htm;
        $return["divergencia"]=$divergencia;
        return ($return);
	}


	function aceitaEntradaFracionada($idProgramacao, $idContagemUma, $idUma=0)
	{
		global $usrId;
		$usrId = intval($usrId);
		// Busca dados da OS se não já tiver sido buscado
		$sql = "SELECT
					C.situacao,
					U.*, C.id idc, C.id_contagens, CNT.contagem, C.uma,
					I.nome nomeItem,
					SK.codigo,
					UN.descricao unidade,
					SK.quantidade quantidade_sku,
					SK.id_itens,
					I.id id_itens,
					U.avariada,
					U.bloqueada,
					U.data_fabricacao,
					U.data_validade,
					U.lote,
					SK.palete_lastro,
					SK.palete_altura,
					SK.altura,
					SK.peso_bruto ipeso_bruto,
					SK.peso_liquido ipeso_liquido,
					(SK.largura/100*SK.comprimento/100) im2,
					(SK.largura/100*SK.comprimento/100*SK.altura/100) im3,
					SK.palete_lastro, SK.altura
				FROM contagens_umas_itens U
				LEFT JOIN contagens_umas C ON C.id=U.id_contagens_umas
				LEFT JOIN contagens CNT ON CNT.id=C.id_contagens
				LEFT JOIN itens_skus SK ON U.id_itens_skus = SK.id
				LEFT JOIN itens I ON I.id=SK.id_itens
				LEFT JOIN unidades UN ON UN.id=SK.id_unidades
				WHERE C.ativo=1 AND U.id_programacao='{$idProgramacao}' ORDER BY U.id";
		$rsi = dbQuery($sql);
		foreach ($rsi as $itemContado)
		{
			// Só cria UMAs do item selecionado ou cria todas se for informado 0
			if (($idContagemUma>0 && $itemContado['idc']==$idContagemUma))
			{
				// Cria UMAs necessárias
				$criou = $this->criaEntradaFracionada($itemContado, $idProgramacao, $idUma);
				if ($criou)
				{
					// Marca contagem já aceita...
					$sql = "UPDATE contagens_umas SET ativo=0 WHERE id=".$itemContado['idc'];
					dbQuery($sql);
				}
			}
		}

		// Inserindo eventos na fila
		$this->inserirEventoNaFila('{id_tipos_eventos:2; id_programacao:'.$idProgramacao.';}');
		return($criou);
	}

	function indicaPosicaoLivreEmArea($idAreaDirecionar)
	{
		$idPosicaoDestino=0;
		
		/* ID da área direcionar definida no parâmetro */
		if (!$idAreaDirecionar) {
			return false;
		}

		$idAreaDirecionar = (int) $idAreaDirecionar;
		$sql = "SELECT P.id, P.codigo_barras, count(U.id) posicionado, P.quantidade
				FROM posicoes P
				LEFT JOIN umas U ON (P.id = U.id_posicoes AND U.ativo = 1)
				LEFT JOIN posicoes PP ON  PP.id = U.id_posicoes_posicionar
				WHERE P.ativo = 1
					AND P.id_areas IN (20, " . $idAreaDirecionar . ")
					GROUP BY P.id
				UNION ALL 
					SELECT PA.id, PA.codigo_barras, 0, PA.quantidade 
					FROM posicoes PA
					LEFT JOIN umas U ON U.id_posicoes = PA.id
					LEFT JOIN umas UP ON UP.id_posicoes_posicionar = PA.id
				WHERE PA.ativo = 1 
					AND PA.id_areas IN (20, " . $idAreaDirecionar . ")
					AND U.id IS NULL 
					AND UP.id IS null 
				GROUP BY PA.id
				ORDER BY codigo_barras";
		$rsAreas = dbQuery($sql);
		foreach ($rsAreas as $rowAreas) {
			if ($rowAreas['quantidade'] > $rowAreas['posicionado']) {
				$idPosicaoDestino = $rowAreas['id'];
				break;
			}
		}

		// Se não tiver nenhuma posição determinada com espaço
		// disponível, usa a última posição encontrada
		if (!$idPosicaoDestino) {
			$idPosicaoDestino = end($rsAreas)['id'];
		}

		return $idPosicaoDestino;
	}


	function validarPosicaoRegraPosicionamento($id_posicoes, $sigla, $id_umas)
	{
		$sai=false;
		$in_areas=array();
		$sql="SELECT unica_op_predio, id_areas FROM regra_posicionamento RP WHERE RP.codigo='".$sigla."' AND cancelada=0";
		$regra_posicionamento=(dbQuery($sql)[0]);
		$id_areas_validar[]=intval($regra_posicionamento["id_areas"]);

		// Somente validar em outras áreas, se a área principal estiver cheia.
		$sql="SELECT
			P.id
		FROM posicoes P
		WHERE
			(P.id_areas=".intval($regra_posicionamento["id_areas"]).")
			AND (P.ativo=1)
			AND (P.quantidade > quantidade_posicionada)
		GROUP BY P.id
		LIMIT 1";
		$existe_posicao_disponivel=dbQuery($sql);


		// Só buscar em areas alternativas se esgotar as posições da área principal.
		if (count($existe_posicao_disponivel)==0)
		{
			$sql="SELECT
				RP.id_areas,
				RPA.id_areas id_areas_alternativa
			  FROM regra_posicionamento_areas_alternativas RPA
 			  LEFT JOIN regra_posicionamento RP ON RP.id = RPA.id_regra_posicionamento
 			  WHERE (RP.codigo='".$sigla."')
 			  AND (RP.cancelada=0)
 			  AND (RPA.cancelada=0)
			";
			$rs=dbQuery($sql);
			foreach ($rs as $row)
			{
				$id_areas_validar[]=intval($row["id_areas_alternativa"]);
			}
		}


		$in="";
		if (count($id_areas_validar)>0)
		{
			$in=implode(", ", $id_areas_validar);
		}

		if (empty($in)) {
			return false;
		}

		// Verificando se a posição está em uma das áreas permitidas.
		$sql="SELECT id FROM posicoes WHERE id=".$id_posicoes." AND id_areas in ($in)";
		$rs=dbQuery($sql);
		if (!$rs['id'] || $regra_posicionamento["unica_op_predio"] <= 0) {
			return false;
		}

		// Se for uma única OP então só deixar posicionar se o predio não tiver OP ou tiver a mesma OP da UMA.
		$sql="SELECT
				P.numero_cliente
			  FROM umas_itens UI
			  LEFT JOIN umas U ON U.id= UI.id_umas
			  LEFT JOIN programacao P ON UI.id_programacao = P.id
			  WHERE U.id='".$id_umas."'
			  AND (UI.cancelada=0)
			  GROUP BY P.numero_cliente
			  ";
		$op_atual=(dbQuery($sql)[0]);

		$sql="SELECT
				U.id, PR.id id_programacao, PR.numero_cliente
			  FROM umas_itens UI
			  LEFT JOIN umas U ON U.id = UI.id_umas
			  LEFT JOIN programacao PR ON PR.id = UI.id_programacao
			  LEFT JOIN tipos_programacao TP ON TP.id = PR.id_tipos_programacao
			  LEFT JOIN posicoes P ON P.id = U.id_posicoes
			  WHERE U.ativo=1
			  AND UI.cancelada=0
			  AND P.ativo=1
			  AND U.id_posicoes=".$id_posicoes."
			  AND TP.descricao like 'Entrada%'
			  GROUP BY PR.numero_cliente
			  ";
		$op_posicao=dbQuery($sql);
		if (count($op_posicao)==0)
		{
			$sai=true;
		} else
		{
			$sai=false;
			$op_posicao=$op_posicao[0];
			if (gCleanField($op_posicao["numero_cliente"])==gCleanField($op_atual["numero_cliente"]))
			{
				$sai=true;
			}
		}


		return $sai;
	}

	function indicarProximaPosicaoRegraPosicionamento($id_itens, $id_programacao=0)
	{
		$id_posicoes_destino=0;
		$sql="SELECT regra_posicionamento FROM itens WHERE id={$id_itens}";
		$sigla=(dbQuery($sql)[0]);
		// Verificar posições disponíveis.
		$sql="SELECT
				R.id,
				R.id_areas,
				R.rua,
				R.predio,
				R.andar,
				R.unica_op_predio
			  FROM regra_posicionamento R
			  WHERE (R.codigo='".$sigla["regra_posicionamento"]."')
			  AND (R.cancelada=0)
			  AND (R.ativo=1)
			  ";
		$regra_posicionamento=(dbQuery($sql)[0]);
		if (intval($regra_posicionamento["unica_op_predio"])==1)
		{
			// MOLDLABEL
			$id_posicoes_destino=$this->buscarPosicaoRegraPosicionamento($regra_posicionamento["id_areas"], $regra_posicionamento, $id_programacao);

			if ($id_posicoes_destino==0)
			{
				$sql="
					SELECT
						RPA.ordem, RPA.id_areas
					FROM
					 	regra_posicionamento_areas_alternativas RPA
					WHERE (RPA.id_regra_posicionamento=".$regra_posicionamento["id"].")
					AND (RPA.cancelada=0)
					ORDER BY
						RPA.ordem ASC,
						RPA.data_cadastro ASC
				";
				$regras_alternativas=dbQuery($sql);
				if (count($regras_alternativas)>0)
				{
					// Nesse caso não precisa mais definir unica OP, Pois somente a área principal leva em consideração esse FLAG
					// Solicitação de rodrigo.
					$regra_posicionamento["unica_op_predio"]=0;
					foreach ($regras_alternativas as $regra_alternativa)
					{
						$id_posicoes_destino=$this->buscarPosicaoRegraPosicionamento($regra_alternativa["id_areas"], $regra_posicionamento, $id_programacao);
						if ($id_posicoes_destino>0)
						{
							break;
						}
					}
				}
			}
		} else
		{
			// Buscar primeira posição disponível baseado na regra de posicionamento.
			$id_posicoes_destino=$this->buscarPosicaoRegraPosicionamento($regra_posicionamento["id_areas"], $regra_posicionamento);
			if ($id_posicoes_destino==0)
			{
				// Não encontrou nenhuma posição ?
				$sql="
					 SELECT
						RPA.ordem, RPA.id_areas
					 FROM regra_posicionamento_areas_alternativas RPA
					 WHERE (RPA.id_regra_posicionamento=".intval($regra_posicionamento["id"]).")
					 AND (RPA.cancelada=0)
					 ORDER BY
					 	RPA.ordem ASC,
					 	RPA.data_cadastro ASC
				";
				$regras_alternativas=dbQuery($sql);
				if (count($regras_alternativas) > 0)
				{
					foreach ($regras_alternativas as $regra_alternativa)
					{
						$id_posicoes_destino=$this->buscarPosicaoRegraPosicionamento($regra_alternativa["id_areas"], $regra_posicionamento);
						if ($id_posicoes_destino>0)
						{
							break;
						}
					}
				}
			}
		}
		return ($id_posicoes_destino);
	}

	function buscarPosicaoRegraPosicionamento($id_areas, $regra_posicionamento, $id_programacao=0)
	{
		$id_posicoes_destino=0;
		if ($regra_posicionamento["unica_op_predio"]==1)
		{
			// Única OP na regra.
			// Buscar primeira posição disponível baseado na regra de posicionamento.
			$where=array();
			$where[]="(P.id_areas=".intval($id_areas).")";
			$where[]="(P.ativo=1)";
			$where[]="(UI.cancelada=0)";
			$where=implode(" AND ", $where);
			$sql="SELECT
					numero_cliente op
				  FROM programacao
				  WHERE (id=".$id_programacao.")
				  AND (cancelada=0)
				  ";
			$op_atual=(dbQuery($sql)[0]);
			// Pegando primeiro prédio disponível com essa OP ?
			$sql = "SELECT
						P.rua, P.predio
					FROM umas U
					LEFT JOIN programacao PR ON PR.id = U.id_programacao
					LEFT JOIN posicoes P ON P.id = U.id_posicoes
					WHERE (U.ativo=1)
					AND (PR.numero_cliente='".gCleanField($op_atual["op"])."')
					AND (U.id_posicoes<>0)
					AND (P.id_areas=".$regra_posicionamento["id_areas"].")
					GROUP BY P.id
					ORDER BY
						P.andar ".$regra_posicionamento["andar"].",
						P.rua ".$regra_posicionamento["rua"].",
						P.predio ".$regra_posicionamento["predio"]."
					";
			$predios_disponivel=dbQuery($sql);
			if (count($predios_disponivel)>0)
			{
				// Se existe eu jogo para a primeira posição disponível daquela rua e prédio, baseado na regra de posicionamento.
				$predio_disponivel=$predios_disponivel[0];
				$sql = "SELECT
						P.id,
						count(distinct(U.id)) posicionado,
						P.quantidade,
						P.codigo_barras
					FROM posicoes P
					LEFT JOIN umas U ON (U.ativo=1 AND U.id_posicoes=P.id)
					WHERE
						(P.id_areas=".$regra_posicionamento["id_areas"].")
						AND (P.ativo=1)
						AND (P.rua='".$predio_disponivel["rua"]."')
						AND (P.predio='".$predio_disponivel["predio"]."')
					GROUP BY P.id
					HAVING quantidade > posicionado
					ORDER BY
						P.quantidade_posicionada ASC,
						P.andar ".$regra_posicionamento["andar"].",
						P.rua ".$regra_posicionamento["rua"].",
						P.predio ".$regra_posicionamento["predio"]."
				";
				$posicao_disponivel=dbQuery($sql);
				if (count($posicao_disponivel)>0)
				{
					$id_posicoes_destino=$posicao_disponivel[0]["id"];
				}
			}

			if ($id_posicoes_destino==0)
			{
				// Se não existe nenhuma posição disponível para aquele prédio então buscar um prédio vazio para colocar a UMA.
				$sql = "
						SELECT
							p.rua,
							p.predio,
							count(distinct(p.id)) ttl_posicoes,
						    sum(p.quantidade) quantidade_predio,
						    count(distinct(u.id)) posicionado
						FROM posicoes p
						LEFT JOIN umas u on (u.ativo=1 and u.id_posicoes=p.id)
						WHERE p.id_areas=".$regra_posicionamento["id_areas"]."
						GROUP BY p.rua, p.predio
						HAVING posicionado=0
					";
				$predios_vazio=dbQuery($sql);
				if (count($predios_vazio)>0)
				{
					$predio_vazio=$predios_vazio[0];
					$sql = "SELECT
							P.id,
							count(distinct(U.id)) posicionado,
							P.quantidade,
							P.codigo_barras
						FROM posicoes P
						LEFT JOIN umas U ON (U.ativo=1 AND U.id_posicoes=P.id)
						WHERE
							(P.id_areas=".$regra_posicionamento["id_areas"].")
							AND (P.ativo=1)
							AND (P.rua='".$predio_vazio["rua"]."')
							AND (P.predio='".$predio_vazio["predio"]."')
						GROUP BY P.id
						HAVING quantidade > posicionado
						ORDER BY
							P.andar ".$regra_posicionamento["andar"].",
							P.rua ".$regra_posicionamento["rua"].",
							P.predio ".$regra_posicionamento["predio"]."
					";
					$posicao_disponivel=dbQuery($sql);
					if (count($posicao_disponivel)>0)
					{
						$id_posicoes_destino=$posicao_disponivel[0]["id"];
					}
				}
			}

		} else
		{
			// Buscar primeira posição disponível baseado na regra de posicionamento.
			$where=array();
			$where[]="(P.id_areas=".intval($id_areas).")";
			$where[]="(P.ativo=1)";
			$where=implode(" AND ", $where);
			$sql = "SELECT
						P.id_areas,
						P.id,
						P.rua,
						P.andar,
						P.id,
						count(distinct(U.id)) posicionado,
						P.quantidade
					FROM posicoes P
					LEFT JOIN umas U ON (U.ativo=1 AND U.id_posicoes=P.id)
					WHERE {$where}
					GROUP BY P.id
					HAVING quantidade > posicionado
					ORDER BY
						P.andar ".$regra_posicionamento["andar"].",
						P.rua ".$regra_posicionamento["rua"].",
						P.predio ".$regra_posicionamento["predio"]."
					LIMIT 1
			";

			// Achou uma posição apta.
			$posicao_disponivel=dbQuery($sql);
			if (count($posicao_disponivel)>0)
			{
				$id_posicoes_destino=$posicao_disponivel[0]["id"];
			}
		}
		return ($id_posicoes_destino);
	}

	public function obtemUmasNoInventario($id_itens_skus = 0, $id_pessoas_proprietario = 0, $codigoUma, $idUma)
	{
		// Rotina para evitar que sejam reservadas UMAs que estão sendo inventariadas
		global $gParam;

		$where = array();
		$where[] = "(PR.id_armazens = " . (int) $_SESSION["armazemAtualId"] . ")";
		$where[] = "(PR.cancelada = 0)";
		$where[] = "(PR.ativo = 1)";
		$where[] = "(PR.executada = 0)";
		$where[] = "(PR.id_tipos_programacao IN (6,30))";
		$where[] = "(U.ativo = 1)";

		$joinUmasItens = 0;

		if ((int) $id_itens_skus) {
			if (is_array($id_itens_skus)) {
				$id_itens_skus = implode("','", $id_itens_skus);
			}
			$where[] = "(UI.id_itens_skus IN ('" . $id_itens_skus . "'))";
			$joinUmasItens = 1;
		}

		$idPessoasProprietario = (int) $idPessoasProprietario;
		if ($idPessoasProprietario) {
			$where[] = "(UI.id_pessoas_proprietario = {$idPessoasProprietario})";
			$where[] = "(
				PR.id_pessoas_proprietario = 0
				OR PR.id_pessoas_proprietario = {$idPessoasProprietario})";
			$joinUmasItens = 1;
		}

		if ($codigoUma) {
			$where[] = "(U.codigo_barras = '{$codigoUma}')";
		}
		if ((int) $idUma) {
			$where[] = "(U.id = " . ((int) $idUma) . ")";
		}
		if ($joinUmasItens) {
			$where[] = "(UI.cancelada = 0)";
			$addJoin = " INNER JOIN umas_itens UI ON UI.id_umas = U.id ";
		}

		$where = implode(" AND ", $where);
		$sql = "
			SELECT U.id, PR.os, U.codigo_barras, P.codigo_barras AS posicao
			FROM programacao_inventario_posicoes PIP
			JOIN programacao PR ON PR.id = PIP.id_programacao
			JOIN posicoes P ON P.id = PIP.id_posicoes
			INNER JOIN umas U ON U.id_posicoes = P.id
			{$addJoin}
			WHERE {$where}
			GROUP BY U.id, PR.id";
		return dbQuery($sql);
	}

	function definePosicaoInicial($idAreaInicial)
	{
		if ($idAreaInicial>0)
		{
			// $sql = "SELECT P.id, count(U.id) posicionado, P.quantidade
			// 		FROM posicoes P
			// 		LEFT JOIN umas U ON (U.ativo=1 AND (U.id_posicoes=P.id OR U.id_posicoes_posicionar=P.id))
			// 		WHERE P.ativo=1 AND P.id_areas='{$idAreaInicial}'
			// 		GROUP BY P.id
			// 		ORDER BY P.codigo_barras";

			$sql = "SELECT P.id, P.codigo_barras, count(U.id) posicionado, P.quantidade
					FROM umas U
					LEFT JOIN posicoes P ON P.id = U.id_posicoes
					LEFT JOIN posicoes PP ON  PP.id = U.id_posicoes_posicionar
					WHERE U.ativo = 1
						AND P.ativo = 1
						AND P.id_areas = 20
						GROUP BY P.id
					UNION ALL 
						SELECT PA.id, PA.codigo_barras, 0, PA.quantidade 
						FROM posicoes PA
						LEFT JOIN umas U ON U.id_posicoes = PA.id
						LEFT JOIN umas UP ON UP.id_posicoes_posicionar = PA.id
					WHERE PA.ativo = 1 
						AND PA.id_areas = 20
						AND U.id IS NULL 
						AND UP.id IS null 
					GROUP BY PA.id
					ORDER BY codigo_barras";
			$rsAreas = dbQuery($sql);

			$idPosicaoInicial=0;
			foreach ($rsAreas as $rowAreas)
			{
				// Se não tiver nenhuma posição determinada com espaço
				// disponível, deixa em branco (não posicionado)
				if ($rowAreas['quantidade']>$rowAreas['posicionado'])
				{
					$idPosicaoIniciao=$rowAreas['id'];
				}
			}

			return ($idPosicaoIniciao);
		}
		return (0);
	}

	function definePosicaoDestino($id_areas_direcionar, $id_itens=0)
	{
		$id_posicao_destino=0;
		$id_posicao_destino=$this->indicaPosicaoLivreEmArea($id_areas_direcionar);
		if ($id_posicao_destino == 0 && $id_itens>0)
		{
			$id_posicao_destino = $this->indicaPosicao($id_itens);
		}
		return ($id_posicao_destino);
	}


	function indicaProximaPosicao($idAreaDirecionar, $idUma)
	{
		$idPosicaoDestino=$this->indicaPosicaoLivreEmArea($idAreaDirecionar);
		/* Se não achou nenhuma posição utilizar o método indicaPosicao que indica uma posição através do id do item */
		if ($idPosicaoDestino==0)
		{
			$umasComSaldo=$this->obtemUMAsComSaldo("(U.id = '{$idUma}')");
			$idPosicaoDestino = $this->indicaPosicao($umasComSaldo[0]["id_itens"]);
		}
		return ($idPosicaoDestino);
	}

	public function indicaPosicao($id_itens, $picking = false)
	{
		$sql = "
			SELECT
				pessoas_juridicas.indicar_posicao
			FROM
				pessoas_juridicas
			JOIN itens ON
				itens.id_pessoas_proprietario = pessoas_juridicas.id_pessoas
			WHERE
				itens.id = {$id_itens}
			LIMIT 1";
		$rs = dbQuery($sql)[0]['indicar_posicao'];
		if (!$rs) {
			return false;
		}
		// Primeiro verifica se o item tem áreas associadas
		if (count($this->areasDesteItem)==0 || $id_itens<>$this->id_itens)
		{
			$sql = "SELECT IA.*, A.de_cima_pra_baixo
					FROM itens_areas IA
					LEFT JOIN areas A ON IA.id_areas=A.id
					WHERE IA.id_itens=".$id_itens." ORDER BY IA.prioridade, IA.id";
			$this->areasDesteItem = dbQuery($sql);
		}
		$fltPicking = "AND picking = 0";
		if ($picking)
		{
			$fltPicking = "AND picking = 1";
		}
		$id_posicao_sugerida = 0;
		foreach ($this->areasDesteItem as $rowAreas)
		{
			if ($id_posicao_sugerida == 0)
			{
				// Flag que instrui posicionar as cargas de cima pra baixo e não de baixo pra cima
				// - Porta-palete é melhor de baixo pra cima
				// - Drive-in é melhor de cima pra baixo
				$deCimaPraBaixo="";
				if ($rowAreas['de_cima_pra_baixo'])
				{
					$deCimaPraBaixo="DESC ";
				}
				// Depois verifica as posições livres desta área
				// Não pode ser uma posição de picking!
				$sql = "SELECT * FROM posicoes
							WHERE ativo=1 ".$fltPicking."
								AND quantidade_posicionada < quantidade
								AND id_areas=".$rowAreas['id_areas']."
							ORDER BY armazem, rua, lado DESC, predio, andar ".$deCimaPraBaixo.", apartamento";
				$posicoesLivres = dbQuery($sql);
				if (count($posicoesLivres)>0)
				{
					foreach ($posicoesLivres as $posicaoLivre)
					{
						if ($id_posicao_sugerida == 0)
						{

							// Tem posição livre nesta área, verifica se já está associada a outra UMA
							// (posicionada ou não)...
							$sql = "SELECT COUNT(DISTINCT id) FROM umas WHERE ativo=1 AND id_posicoes_posicionar=".$posicaoLivre['id'];
							$totalDeUmasAqui = dbQuery($sql)[0]['quant'];
							// Verifica se tem menos UMAs aqui do que a posição permite
							if ($posicaoLivre['quantidade'] > $totalDeUmasAqui)
							{
								// Já que tem espaço, deixa usar esta posição
								$id_posicao_sugerida = $posicaoLivre['id'];
							}
						}
					}
				}
			}
		}
		return($id_posicao_sugerida);
	}

	function buscarPaleteVazio($id_posicoes_atual)
	{
		$sql="SELECT id, lado, rua FROM posicoes WHERE id=".intval($id_posicoes_atual);
		$posicao_atual=(dbQuery($sql)[0]);
		if (count($posicao_atual))
		{
			if ($posicao_atual["lado"]=="I")
			{
				$where_predio='999';
			} else
			{
				$where_predio='000';
			}

			$where=array();
			$where[]="(palete_vazio=1)";
			$where[]="(rua='".$posicao_atual["rua"]."')";
			$where[]="(lado='".$posicao_atual["lado"]."')";
			$where[]="(ativo=1)";
			$where[]="(predio='{$where_predio}')";
			$where=implode(" AND ", $where);
			$sql="SELECT
					id, palete_vazio, codigo_barras
			  FROM posicoes
			  WHERE {$where}
			  ORDER BY rua='".$posicao_atual["rua"]."' DESC, rua ASC LIMIT 1";
			$posicao=dbQuery($sql);
			if (count($posicao)>0)
			{
				return ($posicao[0]["id"]);
			}
			return (0);
		}
		return (0);
	}


	function buscarPosicaoDisponivelAreaEspera($id_posicoes_atual)
	{
		$sql="SELECT * FROM posicoes WHERE id=".intval($id_posicoes_atual);
		$posicao_atual=(dbQuery($sql)[0]);
		if (count($posicao_atual)>0)
		{

			if ($posicao_atual["lado"]=="I")
			{
				$where_predio='999';
			} else
			{
				$where_predio='000';
			}

			$where=array();
			$where[]="(p.rua='".$posicao_atual["rua"]."')";
			$where[]="(p.lado='".$posicao_atual["lado"]."')";
			$where[]="(p.predio='{$where_predio}')";
			$where[]="(p.ativo=1)";
			$where[]="(a.espera=1)";
			$where[]="(p.id<>".$posicao_atual["id"].")";
			$where[]="(p.quantidade_posicionada < p.quantidade)";
			$where[]="(p.palete_vazio=0)";
			$where=implode(" AND ", $where);

			// Encontrando a posição de espera mais próxima da atual.
			$sql="SELECT
				p.id
			  FROM posicoes p
			  LEFT JOIN areas a ON a.id = p.id_areas
			  WHERE {$where}
			  ORDER BY p.rua='".$posicao_atual["rua"]."' DESC, p.predio='{$where_predio}' DESC, p.andar ASC, p.apartamento ASC
			  LIMIT 1";

			$posicao=dbQuery($sql);
			if (count($posicao)>0)
			{
				return ($posicao[0]["id"]);
			}
			return (0);
		}
		return (0);
	}

	function calculaAlturaDoPalete($idUma, $idPosicao)
	{
		$posicao=$this->obtemRegistroPosicao($idPosicao);
		$uma=dbQuery("SELECT * FROM umas WHERE id = '$idUma'");
		if ($uma[0]["id_tipos_umas"]!=5)
		{
			// Não há uma preocupação se a carga está avariada, bloqueada ou normal, pois vai ocupar
			// espaço do mesmo jeito. Portanto, basta somar a quantidade que o total é o que é preciso
			// Se tiver mais de um item na UMA, usa o maior deles como referência...
			// Ou seja, se tiver mais de um item na UMA, deve ser colocado ao lado do produto maior e
			// não em cima, pois pode afetar o cálculo da altura do palete
			$sql="SELECT
					SKU.id, SKU.altura, SKU.largura, SKU.comprimento, SKU.palete_lastro, SKU.palete_altura,
					SUM(UI.quantidade) quantidade
			  FROM umas U
			  INNER JOIN umas_itens UI ON UI.id_umas = U.id
			  INNER JOIN itens_skus SKU ON SKU.id = UI.id_itens_skus
			  WHERE U.id='$idUma' AND UI.cancelada=0
			  GROUP BY 	SKU.id, SKU.altura, SKU.largura, SKU.comprimento, SKU.palete_lastro, SKU.palete_altura
			  ORDER BY SKU.altura DESC LIMIT 1
			";
			$sku=dbQuery($sql)[0];
			$quantidadeDeLinhas = ceil($sku['quantidade']/$sku['palete_lastro']);
			// if ($quantidadeDeLinhas < $sku['quantidade']/$sku['palete_lastro'])
			// {
			// 	// Se tiver uma fração, indica que tem algumas caixas a mais por cima da última altura
			// 	$quantidadeDeLinhas++;
			// }
			// A única dimensão realmente importante pra se preocupar é a altura...
			$alturaDoPalete = $quantidadeDeLinhas*$sku['altura'];
			return ($alturaDoPalete);
		} else
		{
			// Se for picking, ignora a altura do palete
			return (100);
		}
	}

	public function obtemPosicao($idPosicao)
	{
		$sql="SELECT
				 P.lado,
				 P.andar,
				 P.codigo_barras,
				 P.id,
				 P.id_areas,
				 (P.altura*100) as alturaCentimetro,
				 (P.largura*100) as larguraCentimentro,
				 (P.comprimento*100) as comprimentoCentimetro,
				 P.quantidade,
				 P.quantidade_posicionada,
				 P.peso_suportado,
				 A.espera,
				 A.armazenagem,
				 A.espera,A.expedicao,
				 A.recebimento,
				 A.divergencia,
				 A.avaria,
				 A.posicionavel,
				 A.lote_unico,
				 A.id_pessoas_proprietario,
				 PR.apelido,
				 P.ativo,
				 P.predio,
				 P.id_armazens,
				 P.rua,
				 A.descricao area,
				 P.picking
				FROM posicoes P
				LEFT JOIN areas A ON P.id_areas=A.id
				LEFT JOIN pessoas PR ON PR.id = A.id_pessoas_proprietario
				WHERE P.id = '{$idPosicao}'
					";
		return (dbQuery($sql)[0]);
	}

	function obtemRegistroPosicao($id)
	{
		$sql="SELECT
				P.*, A.descricao as armazem, AR.descricao as area
			  FROM posicoes P
			  LEFT JOIN armazens A ON P.id_armazens = A.id
			  LEFT JOIN areas AR ON P.id_areas = AR.id
			  WHERE P.id='$id'";
		return dbQuery($sql)[0];
	}

	function separaUMA($json='{}')
	{

	}


	public function verificarSaldoAntesSaidaAvulsa($where, $quantidadeMinima)
	{
		$saldos = $this->obtemUMAsComSaldo($where, true);
		return (array_sum(array_column($saldos, 'quantidade')) >= $quantidadeMinima);
	}


	public function saidaUma($parametro)
	{
		global $gParam, $usrId, $o;

		if (!$parametro['uma'] || !$parametro['idPosicaoDestino']) {
			$this->erros[] = 'UMA ou posição de destino não definidos';
			return;
		}

		$saldos = $this->obtemUMAsComSaldo($where = $parametro["where"], $soAtivas = true, $prioridade = 0, $orderBy = "UI.reservada DESC, UI.separada DESC");

		if ($gParam["PERFIL_FABRICANTE"]["ativo"]) {
			$mostrouUma = false;
			$this->informarMovimentacaoTotvs($saldos);
			if ($this->erros) {
				return;
			}
		}

		$idProgramacao = 0;
		$idUma = $saldos[0]["id"];
		$executouSaida = $this->prepararBaixaSaldo($saldos, $parametro['quantidade'], $parametro['idPosicaoDestino']);

		$return = array();
		$return["idUma"] = $idUma;
		$idProgramacoesFinalizadas = $this->executarProgramacoesUmasSeparadas($idUma);
		if ($idProgramacoesFinalizadas) {
			$return['idProgramacoesFinalizadas'] = implode(',', $idProgramacoesFinalizadas);
		}

		// Finalizar eventos de separação.
		if ($idUma) {
			$this->finalizarEventos('{id_umas:' . $idUma . ';}');
		}

		return $return;
	}


	public function	prepararBaixaSaldo($saldos, $quantidade, $idPosicaoDestino)
	{
		global $usrId;

		foreach ($saldos as $key => $saldo) {
			$idUma = (int) $saldo['id'];
			$buscarItem = array();
			$buscarItem["id_itens_skus"] = $saldo["id_itens_skus"];
			$buscarItem["reservada"] = $saldo["reservada"];
			$buscarItem["separada"]  = $saldo["separada"];
			$buscarItem["bloqueada"] = $saldo["bloqueada"];
			$buscarItem["avariada"]  = $saldo["avariada"];
			$buscarItem["lote"] = $saldo["lote"];
			$buscarItem["data_fabricacao"] = $saldo["data_fabricacao"];
			$buscarItem["data_validade"] = $saldo["data_validade"];
			$buscarItem["id_programacao_itens"] = $saldo["id_programacao_itens"];
			$buscarItem["id_programacao"] = $saldo['id_programacao_umas_itens'];
			$item = $this->buscaItem($idUma, $buscarItem)[0];
			if (!$item) {
				continue;
			}

			$idPosicoes = (int) $saldo["id_posicoes"];
			$idPosicaoDestino = $idPosicaoDestino;
			$saldo['quantidade'] = $quantidade ?: $saldo['quantidade'];

			$posicionou = $this->posicionarUmaSaidaAvulsa(array(
				'idUma' => $saldo['id'],
				'idProgramacao' => $saldo["id_programacao_itens"],
				'lote' => $saldo['lote'],
				'idPosicaoOrigem' => $idPosicao,
				'idPosicaoDestino' => $idPosicaoDestino,
				'saldo' => $saldo
			));
			if (!$posicionou) {
				$this->erros[] = 'Erro ao posicionar UMA';
				return false;
			}

			$saidaAvulsa = 0;
			if (!$saldo["id_programacao_itens"]) {
				$saidaAvulsa = 1;
			}

			$saldosRetirados += (int) $this->retirarSaldo($saldo, $item, $saidaAvulsa, $idPosicaoDestino);
			if ($this->erros) {
				return;
			}
		}

		return (count($saldos) == $saldosRetirados);
	}


	public function executarProgramacoesUmasSeparadas($idUma)
	{
		$sql = "
			SELECT
				PR.id,
				UI.id_programacao,
				PR.id_tipos_programacao,
				PR.executada
			FROM
				umas_itens UI
			LEFT JOIN programacao PR ON
				PR.id = UI.id_programacao
			LEFT JOIN tipos_programacao TP ON
				TP.id = PR.id_tipos_programacao
			WHERE
				UI.cancelada = 0
				AND UI.id_umas = '" . $idUma . "'
			GROUP BY
				UI.id_programacao";
		$programacoesUma = dbQuery($sql);

		/*** EXECUTA PROGRAMACOES ONDE ESTA UMA FOI SEPARADA***/
		foreach ($programacoesUma as $programacao)	{
			if (
				!$programacao["executada"]
				&& in_array($programacao["id_tipos_programacao"], array(2, 3, 4, 8, 22, 24, 25))
			) {
				$umasSeparadas = $this->umasSeparadasOS($programacao['id'], "SUM(UI.quantidade)>0");
				$sql = "
					SELECT
						DISTINCT(UI.id_umas)
					FROM
						umas_itens UI
					LEFT JOIN umas U ON
						UI.id_umas = U.id
					WHERE
							UI.cancelada = 0
						AND ativo = 1
						AND UI.reservada = 1
						AND UI.id_programacao = '" . $programacao["id"] . "'
					HAVING
						SUM(UI.quantidade)>0";
				$umasReservadas = dbQuery($sql);
				if (!$umasSeparadas && !$umasReservadas) {
					finalizaOS($programacao["id"]);
					$osFinalizada[] = $programacao["id"];
				}
			}
		}

		return $osFinalizada;
	}


	public function informarMovimentacaoTotvs($saldos)
	{
		global $usrId;
		foreach ($saldos as $confereSaldo) {
			$sql = "SELECT
					A.codigo
				FROM
					posicoes P
				LEFT JOIN areas A ON
					P.id_areas = A.id
				WHERE
					P.id = ".intval($confereSaldo[" id_posicoes"]);
			$conferePosicao = dbQuery($sql)[0];
			$sql = "
				SELECT
					quantidade
				FROM
					totvs_saldo_atual
				WHERE
					codigo_sku = '" . $confereSaldo[' codigo'] . "'
					AND lote = '" . $confereSaldo["lote"] . "'
					AND local = '" . $conferePosicao["codigo"] . "'
					AND quantidade >= '" . abs($confereSaldo["quantidade"]) . "'";
			$rs = dbQuery($sql);
			if ($rs) {
				continue;
			}
			if (!$mostrouUma) {
				$this->erros[] = $confereSaldo['codigo_barras'];
				$mostrouUma = true;
			}
			$descricao = "Sem saldo no " . $conferePosicao["codigo"] . ": "
				. $confereSaldo['codigo'] . " • "
				. $confereSaldo["lote"] ." = " . intval($confereSaldo["quantidade"]);
			$mtz = array();
			$mtz["id_pessoas"] = $usrId;
			$mtz["id_umas"] = $confereSaldo["id"];
			$mtz["id_umas_para"] = 0;
			$mtz["id_posicoes"] = $confereSaldo["id_posicoes"];
			$mtz["id_pessoas_proprietario"] = $confereSaldo["id_pessoas_proprietario"];
			$mtz["tipo"] = "L";
			$mtz["data"] = date('Y-m-d H:i:s');
			$mtz["descricao"] = $descricao;
			$mtz["lote"] = $confereSaldo["lote"];
			$mtz["data_validade"] = $confereSaldo["data_validade"];
			$mtz["data_fabricacao"] = $confereSaldo["data_fabricacao"];
			dbInsert("umas_movimentos", $mtz);
			$this->erros[] = $descricao;
		}
	}


	public function skusSeparadosReservadosNaUma($idUma, $idItensSkus)
	{
		$sql =
			"SELECT
				PI.id id_programacao_itens,
				PI.id_programacao,
				PI.id_itens_skus,
				PR.os,
				PR.id_tipos_programacao,
				SUM(UI.quantidade) ttl_quantidade
			FROM
				umas_itens UI
			LEFT JOIN programacao_itens PI ON
				PI.id = UI.id_programacao_itens
			LEFT JOIN programacao PR ON
				PR.id = PI.id_programacao
			WHERE
					UI.cancelada = 0
				AND UI.id_umas = " . ((int) $idUma) . "
				AND
					UI.id_itens_skus = " . ((int) $idItensSkus) . "
				AND
					PR.id_tipos_programacao IN (
						2, 3, 4, 8, 22, 24, 25
				)
				AND (
					UI.reservada = 1
						OR UI.separada = 1
				)
			GROUP BY
				PI.id
			HAVING
				ttl_quantidade > 0
			";

		return dbQuery($sql);
	}


	public function posicionarUmaSaidaAvulsa($parametro)
	{
		global $gParam;
		if ($gParam['PERFIL_FABRICANTE']['ativo']) {
			$where = "(U.id = " . $parametro['idUma']
				. " AND UI.id_programacao = " . $parametro['idProgramacao']
				. " AND UI.lote = '" . $parametro['lote'] . "')";
			$ok = TOTVS_transfereArmazem("{id_programacao: " . $parametro['idProgramacao'] . "; buscar_uma: " . $where . "; origem: " . $parametro['idPosicoes'] . "; destino: " . $parametro['idPosicaoDestino'] . "; tipo_operacao: saida;}");

			if (!$ok) {
				$this->erros[] = 'Erro ao transferir armazém';
				return false;
			}
		}

		if ($parametro['idPosicaoDestino'] <> $parametro['idPosicoes']) {
			$this->posicionar($parametro['idUma'], $parametro['idPosicaoDestino'], 0);
		}

		if ($gParam["USA_CONFERENCIA_SAIDA"]["ativo"]) {
			$parametro['saldo']['id_posicoes'] = $idPosicaoDestino;
			$this->conferirSaidaUMA($parametro['saldo']);
		}

		return true;
	}


	public function baixarSaldoPorItemProgramado($saldo, $skusSeparadosReservadosNaUma, $idPosicaoOrigem, $idPosicaoDestino, $buscarItem)
	{
		global $usrId;
		$idUma = (int) $saldo['id'];

		foreach ($skusSeparadosReservadosNaUma as $row) {
			$idProgramacao = (int) $row["id_programacao"];

			$buscarItem["id_programacao_itens"] = $row["id_programacao_itens"];
			$buscarItem["id_programacao"] = $idProgramacao;
			$item = $this->buscaItem($idUma, $buscarItem);
			if (!$item) {
				continue;
			}

			$posicionou = $this->posicionarUmaSaidaAvulsa(array(
				'idUma' => $idUma,
				'idProgramacao' => $idProgramacao,
				'lote' => $saldo['lote'],
				'idPosicaoOrigem' => $idPosicao,
				'idPosicaoDestino' => $idPosicaoDestino,
				'saldo' => $saldo
			));
			if (!$posicionou) {
				$this->erros[] = 'Erro ao posicionar UMA';
				return false;
			}

			$retirouSaldo = $this->retirarSaldo($saldo, $item[0], $idProgramacao, $saidaAvulsa = 0, $idPosicaoDestino);

			return $efetuouSaida;
		}
	}


	public function baixarSaldoNaoProgramadoUma($saldo, $buscarItem, $idPosicao, $idPosicaoDestino)
	{
		$idUma = (int) $saldo['id'];
		$item  = $this->buscaItem($idUma, $buscarItem);
		if (!$item) {
			$this->erros[] = 'Item não programado não encontrado para efetuar saída';
			return false;
		}

		$posicionou = $this->posicionarUmaSaidaAvulsa(array(
			'idUma' => $idUma,
			'idProgramacao' => 0,
			'lote' => $saldo['lote'],
			'idPosicaoOrigem' => $idPosicao,
			'idPosicaoDestino' => $idPosicaoDestino,
			'saldo' => $saldo
		));

		if (!$posicionou) {
			$this->erros[] = 'Erro ao posicionar UMA';
			return false;
		}

		$retirouSaldo = $this->retirarSaldo($saldo, $item[0], $saidaAvulsa = 1, $idProgramacao);

		return $efetuouSaida;
	}


	// public function retirarSaldo($saldo, $item, $saidaAvulsa = 1, $idProgramacao = 0, $idPosicaoDestino = 0)
	public function retirarSaldo($saldo, $item, $saidaAvulsa = 1, $idPosicaoDestino = 0)
	{
		global $usrId, $gParam;
		$idUma = $saldo['id'];

		$item['tipo']  = '-';
		$item['saida'] = '1';
		$item['quantidade'] = $saldo['quantidade'];
		$item['saida'] = 1;
		$item['id_notas_itens'] = $saldo['id_notas_itens'];

		$mtz = $this->preparaCamposDoItem($idUma, $item);
		$mtz['id_tipos_operacao'] = 4;
		$idMovimentacaoSaida = dbInsert('umas_itens', $mtz, true);
		atualizarAtivacaoUMA($mtz['id_umas']);

		if (!$idMovimentacaoSaida) {
			$this->erros[] = 'Movimentação de saída não ocorreu';
			return;
		}

		if (
			$gParam['USA_POSICAO_COMO_UMA']['ativo']
			&& $_SESSION['idLocalizarItens']
			&& $idMovimentacaoSaida
		) {
			$idsUmasItensLocalizados = gFieldById('localizar_itens', $_SESSION['idLocalizarItens'], 'id_umas_itens');
			if ($idsUmasItensLocalizados) {
				$idMovimentacaoSaida = $idsUmasItensLocalizados . ',' . $idMovimentacaoSaida;
			}
			$sql = "
				UPDATE localizar_itens
				SET id_umas_itens = '" . $idMovimentacaoSaida ."',
					quantidade = (quantidade + '" . ($item['quantidade'] * (-1)) . "')
				WHERE id = " . $_SESSION['idLocalizarItens'];
			dbQuery($sql);
		}

		if ($idProgramacao) {
			$os  = dbQuery("SELECT os FROM programacao WHERE id = " . $idProgramacao)[0]['os'];

			$mtz = array();
			$mtz['id_programacao'] = $idProgramacao;
			$mtz['id_itens_skus'] = $saldo['id_itens_skus'];
			$mtz['cancelada'] = 0;
			$mtz['data'] = date('Y-m-d H:i:s');
			$mtz['quantidade'] = $saldo['quantidade'];
			$mtz['descricao']  = 'Saída efetivada ' . $saldo['codigo_barras'];
			dbInsert('programacao_atividades', $mtz);
		}

		$mtz = array();
		$mtz['id_pessoas'] = $usrId;
		$mtz['id_umas'] = $idUma;
		$mtz['id_umas_para'] = 0;
		$mtz['id_posicoes'] = $idPosicaoDestino;
		$mtz['id_pessoas_proprietario'] = $saldo['id_pessoas_proprietario'];
		$mtz['tipo'] = 'S';
		$mtz['data'] = date('Y-m-d H:i:s');
		$mtz['descricao'] = 'Saída avulsa ' . $os;
		$mtz['lote'] = $saldo['lote'];
		$mtz['data_validade'] = $saldo['data_validade'];
		$mtz['data_fabricacao'] = $saldo['data_fabricacao'];
		dbInsert('umas_movimentos', $mtz);

		userLog('Saída avulsa ' . $saldo['codigo_barras'] . ', na OS: ' . $os);

		$mtz = array();
		$mtz['data_saida']   = date('Y-m-d H:i:s');
		$mtz['saida_avulsa'] = $saidaAvulsa;
		dbUpdate('umas', $mtz, $idUma);

		return (bool) $idMovimentacaoSaida;
	}


	function conferirSaidaUMA($uma, $id_programacao=0)
	{
		global $usrId, $gId;
		$usrId = intval($usrId);
		$gId = intval($gId);

		if ($id_programacao<>0)
		{
			$gId=$id_programacao;
		}

		$sql="SELECT
				id,
				conferida_saida
		  FROM umas
		  WHERE id=".$uma["id"].
		  " AND conferida_saida=1";
		$conferiu_uma=dbQuery($sql);
		if (count($conferiu_uma)==0)
		{
			$mtz=array();
			$mtz["conferida_saida"] = 1;
			$mtz["data_conferencia_saida"] = date('Y-m-d H:i:s');
			$mtz["id_pessoas_conferiu_saida"] = $usrId;
			dbUpdate("umas", $mtz, $uma["id"]);
			$sql="SELECT * FROM umas_conferencias
				  WHERE
				  id_itens_skus=".intval($uma["id_itens_skus"])."
				  AND data_validade='".gCleanField($uma["data_validade"])."'
				  AND data_fabricacao='".gCleanField($uma["data_fabricacao"])."'
				  AND lote='".gCleanField($uma["lote"])."'
				  AND conferida=1
				  AND saida=1
				  AND quantidade='".$uma["quantidade"]."'
				  AND id_umas=".intval($uma["id_umas"])."
				  AND id_pessoas_conferiu=".intval($usrId)."
				  AND id_programacao=".intval($gId)."
			";
			$existeConferencia=dbQuery($sql);
			if (count($existeConferencia)==0)
			{
				$mtz=array();
				$mtz["id_itens_skus"]=$uma['id_itens_skus'];
				$mtz["data_validade"]=$uma["data_validade"];
				$mtz["data_fabricacao"]=$uma["data_fabricacao"];
				$mtz["lote"]=$uma["lote"];
				$mtz["data_conferencia"]=date('Y-m-d H:i:s');
				$mtz["conferida"]=1;
				$mtz["saida"]=1;
				$mtz["quantidade"]=$uma["quantidade"];
				$mtz["id_umas"]=$uma["id"];
				$mtz["id_pessoas_conferiu"]=$usrId;
				$mtz["id_programacao"]=$gId;
				dbInsert("umas_conferencias", $mtz);
				if ($gId>0)
				{
					// Inserir atividades da programação
					$mtz=array();
					$mtz["id_programacao"]=$gId;
					$mtz["id_pessoas"]=$usrId;
					$mtz["id_tipos_atividades"]=12;
					$mtz["id_itens_skus"]=0;
					$mtz["cancelada"]=0;
					$mtz["data"]=date('Y-m-d H:i:s');
					$mtz["quantidade"]=$uma["quantidade"];
					$mtz["descricao"]="Conferiu ".$uma["codigo_barras"];
					dbInsert("programacao_atividades", $mtz);
				}

				// Inserir movimentos da UMA.
				$mtz=array();
				$mtz["id_pessoas"]=$usrId;
				$mtz["id_umas_para"]=0;
				$mtz["id_umas"]=$uma["id"];
				$mtz["id_posicoes"]=$uma["id_posicoes"];
				$mtz["id_pessoas_proprietario"]=$uma["id_pessoas_proprietario"];
				$mtz["tipo"]="C";
				$mtz["descricao"]="Saída conferida";
				$mtz["data"]=date('Y-m-d H:i:s');
				$mtz["lote"]=$uma["lote"];
				$mtz["data_validade"]=$uma["data_validade"];
				$mtz["data_fabricacao"]=$uma["data_fabricacao"];
				dbInsert("umas_movimentos", $mtz);
			}
		}
	}

	public function validarSKU($json='{}')
	{
		$jarr=cssDecode($json);
		if (!isset($jarr["id_tipos_eventos"]))
		{
			throw new Exception("ValidarSKU: não existe id_tipos_eventos", 1);
		}
		if (!isset($jarr["sku_validar"]))
		{
			throw new Exception("ValidarSKU: não existe o SKU a validar", 1);
		}
		if (!isset($jarr["codigo_uma"]))
		{
			throw new Exception("ValidarSKU: não existe o Código da UMA a validar", 1);
		}
		switch ($jarr["id_tipos_eventos"])
		{
			case 1:
				// Separação
			break;

			case 2:
				// Posionamento
				$sku_validar=$jarr["sku_validar"];
				$codigo_uma=$jarr["codigo_uma"];

				$sql="SELECT
						U.id, SK.id
					  FROM umas_itens UI
					  LEFT JOIN umas U ON UI.id_umas = U.id
					  LEFT JOIN itens_skus SK ON SK.id = UI.id_itens_skus
					  WHERE
					  	(SK.codigo='{$sku_validar}' OR SK.codigo_barras='{$sku_validar}' OR SK.codigo_barras_alternativo='{$sku_validar}')
					  	AND U.codigo_barras='{$codigo_uma}' OR U.codigo_externo='{$codigo_uma}'
					  GROUP BY U.id, SK.id
					  ";
				$rs=dbQuery($sql);
				if (count($rs)==0)
				{
					$this->erros[]="SKU informado não foi encontrado na UMA.";
				}
			break;
		}
	}

	public function validarPosicao($codigo_posicao, $idUma, $id_operacao=2, $id_programacao=0)
	{
		global $gId;

		$posicao=array();
		if ($id_operacao==4)
		{
			return (true);
		} else
		{
			if ($codigo_posicao=="")
			{
				$this->erros[]="Código da posição não é válido";
			} else
			{
				$sql="SELECT * FROM posicoes WHERE codigo_barras='{$codigo_posicao}'";
				$posicao=dbQuery($sql);
				if (count($posicao)==0)
				{
					$this->erros[]="Nenhuma posição encontrada.";
				} else
				{
					$posicao=$posicao[0];

					switch ($id_operacao)
					{
						case 1:
							// Evento de separação.
							$sql="SELECT E.*
								  FROM eventos E
								  WHERE E.id=".$gId;
							$evento=(dbQuery($sql)[0]);
							if ($evento['id_posicoes_posicionar']<>$posicao['id'])
							{
								$this->erros[]="A posição informada para a entrega está incorreta.";
							}
						break;

						case 2:
							// Evento de posicionamento.
							$erros=$this->validarPosicionamento($idUma, $posicao["id"]);
							if ($erros)
							{
								if (count($erros)>0)
								{
									foreach ($erros as $erro)
									{
										$this->erros[]=$erro;
									}
								}
							}
						break;

						case 3:
							// Evento de saída.
							$sql="SELECT id_areas, id_areas_direcionar FROM programacao WHERE executada=0 AND cancelada=0 AND id=".intval($id_programacao);
							$programacao=(dbQuery($sql)[0]);
							if (intval($programacao["id_areas"])==0)
							{
								return (true);
							} else
							{
								// Validar se a posição informada é a mesma que a da uma
								$sql="SELECT
										*
									  FROM posicoes P
									  WHERE
									  (P.codigo_barras='{$codigo_posicao}')
									  AND (id_areas=".intval($programacao["id_areas"])." OR id_areas=".intval($programacao["id_areas_direcionar"]).")";
								$posicao=(dbQuery($sql)[0]);
								if (count($posicao)==0)
								{
									$this->erros[]="Posição informada para a entrega está incorreta.";
								}
							}
						break;

						case 4:
							// Evento de conferência.
						break;

						case 5:
							$erros=$this->validarPosicionamento($idUma, $posicao["id"]);
							if ($erros)
							{
								if (count($erros)>0)
								{
									foreach ($erros as $erro)
									{
										$this->erros[]=$erro;
									}
								}
							}
						break;
					}
				}
			}
			return ($posicao);
		}
	}


	// Válida quantidade de acordo com o evento.
	function validarQuantidade($json='{}')
	{

		$jarr=cssDecode($json);

		// Método não funcionará sem essas informações, parar a execução e informar ao programador.
		if (!isset($jarr["id_tipos_eventos"]))
		{
			throw new Exception("Método sem tipo de evento.", 1);
		}

		if (!isset($jarr['quantidade']))
		{
			throw new Exception("Método sem a quantidade.", 1);
		}

		if (!isset($jarr['quantidade']))
		{
			throw new Exception("Método sem a quantidade a válidar.", 1);
		}

		switch ($jarr["id_tipos_eventos"])
		{
			case 1:
				// Separação.
				$quantidade_reserva=$jarr["quantidade"];
				$quantidade_informada=$jarr["quantidade_validar"];

				if (round($quantidade_reserva, 10)<>round($quantidade_informada, 10))
				{
					$this->erros[]="Quantidade informada diverge da reserva.";
				}
			break;

			case 2:
				// Posicionamento.
			break;

			case 3:
				// Saída
			break;

			case 4:
				// Conferencia.
			break;
		}
	}


	function validarPosicionamento($idUma, $idPosicao)
	{
		global $gParam, $o, $usrId;

		/*
			PASSSOS DA VALIDAÇÃO
			- VERIFICAR SE POSIÇÃO EXISTE
			- VERIFICAR SE A POSIÇÃO COMPORTA MAIS UMA
			- VERIFICAR SE UMA ESTÁ ATIVA
			- VERIFICAR SE UMA POSSUI ITENS
			- VERIFICAR SE A POSIÇÃO ESTÁ OCUPADA
			- VERIFICAR SE A ALTURA DA UMA CABE NA POSIÇÃO
			- VERIFICAR SE O COMPRIMENTO DA UMA CABE NA POSIÇÃO
			- SE A UMA ESTIVER COM id_posicao MAS posicionada=0, SÓ PODE POSICIONAR PRA POSIÇÃO QUE JÁ ESTÁ NA TABELA.
			- SE A POSIÇÃO DE DESTINO É POSCIONAVEL
		*/

		recalcularPosicoes([$idPosicao]);

		$erros = array();
		$posicao = $this->obtemPosicao($idPosicao);

		$uma = $this->buscaDadosUMA($idUma)[0];
		$umaItens = $this->obtemUMAsComSaldo("U.id=" . $idUma);

		foreach ($umaItens as $key => $row) {
			if ($posicao['id_areas']) {
				$posicaoDisponivel = dbQuery("SELECT id FROM itens_areas WHERE id_itens = " . $row['id_itens'] . " AND id_areas = " . $posicao['id_areas']);
			}

			if (!$posicaoDisponivel && (!$posicao["espera"] && !$posicao["avaria"] && !$posicao["divergencia"])) {
				$erros[] = "O item " . $row['codigo'] . " não pode ser posicionado na área " . ($posicao['area'] ?: 'não definida') . "";
			}
		}

		if ($_REQUEST['g'] == 'posicionamento' && !$umaItens) {//Se for operacao de posicionamento e nao tiver UMA virgem, ele retorna um erro
			$this->erros[] = "A [" . $uma['codigo_barras'] . "] não tem saldo portanto não será posicionada";
		}

		$pesoUma=0;
		$pesoUma = array_sum(array_column($umaItens, 'peso_bruto'));

		$alturaDaCargaNoPalete=$this->calculaAlturaDoPalete($idUma, $idPosicao);
		$alturaDoPalete=$alturaDaCargaNoPalete+floatval(gDBFloat($gParam['ALTURA_PALETE_VAZIO']['valor']));

		if (count($posicao)==0)
		{
			$erros[] = "Posição não existe";
			return $erros;
		}
		if ($posicao["quantidade_posicionada"] >= $posicao['quantidade']) {
			$msg = "Posição ({$posicao["codigo_barras"]}) não comporta mais nenhuma UMA."
				. "<br> Quantidade limite: " . gFloat($posicao['quantidade']);
			if ($usrId) {
				$msg .=  " / Quantidade de UMAs posicionadas: " . gFloat($posicao["quantidade_posicionada"]);
			}
			$erros[] = $msg;
			return $erros;
		}
		if ($posicao['ativo']==0){
			$erros[] = "Posição bloqueada";
		}
		if ($gParam['POSICIONAMENTO_LIVRE']['ativo']==0)
		{
			if ($uma["id_posicoes_posicionar"]>0)
			{
				// Tem uma posição de destino definida, então verifica se está na posição desejada ou se
				// é uma área de espera, avaria ou divergencia (que sempre é permitido posicionar)
				if (
						$uma["id_posicoes_posicionar"]<>$posicao["id"] &&
						$posicao["espera"]==0 &&
						$posicao["avaria"]==0 &&
						$posicao["divergencia"]==0
					)
				{
					$erros[] = "Posição incorreta (a posição correta é ".gFieldById("posicoes",$uma["id_posicoes_posicionar"],'codigo_barras').")";
					return $erros;
				}
			}

		}

		if (intval($gParam["PERMITIR_POSICIONAR_SEM_DIMENSAO"]["ativo"])==0)
		{
			if ($alturaDoPalete>($posicao["alturaCentimetro"]-floatval(gDBFloat($gParam['ESPACO_MANOBRA']['valor']))))
			{
				$dimensoes[]="Altura da carga no palete: ".gFloat($alturaDaCargaNoPalete).' cm';
				$dimensoes[]="Altura do palete vazio: ".$gParam['ALTURA_PALETE_VAZIO']['valor'].' cm';
				$dimensoes[]="Altura da posição: ".gFloat($posicao["alturaCentimetro"]).' cm';
				$dimensoes[]="Margem de manobra: ".gFloat($gParam['ESPACO_MANOBRA']['valor']).' cm';
				$dimensoes[]="<b>".gFloat($alturaDoPalete).' cm é maior do que '.gFloat($posicao["alturaCentimetro"]-floatval(gDBFloat($gParam['ESPACO_MANOBRA']['valor']))).' cm</b>';
				$erros[] = "A altura do palete não é comportada por esta posição (".$posicao['codigo_barras'].").<br>".$o->ul($dimensoes);
				return $erros;

			}
			// Só verifica o peso suportado se foi cadastrado na posição
			if ($posicao["peso_suportado"]>0 && $posicao["peso_suportado"]<$pesoUma)
			{
				$erros[] = "O peso do palete não é suportado por esta posição (suportado: ".gFloat($posicao["peso_suportado"])." - palete: ".gFloat($pesoUma).")";
				return $erros;
			}
		}


		//Verifica se a area é poscionavel
		$sql = "SELECT * FROM areas WHERE id = " . (int) $posicao['id_areas'];
		$rsp = dbQuery($sql);

		if((int)$rsp[0]['posicionavel'] == 0) {
			$erros[] = "Posição incorreta (a posição ".gFieldById("posicoes",$posicao["id"],'codigo_barras').") não é posicionável.";
			return $erros;
		}

		if (($uma["id_posicoes"] > 0 && $posicao["ativo"] == 1) || $uma["id_posicoes"] == 0) {
			// Se o parâmetro POSICIONAMENTO_LIVRE estiver desativado, permite posicionar em qualquer lugar
			if (!$gParam['POSICIONAMENTO_LIVRE']['ativo']) {

				if (
					$posicao["espera"] == 0
					&& $posicao["avaria"] == 0
					&& $posicao["divergencia"] == 0
				) {

					if ($_REQUEST['g'] == 'transferencia' && $this->itensTransferir) {
						$idsItensSkus = array_unique(
							array_merge(
								$this->itensTransferir,
								array_column($umaItens, 'id_itens_skus')
							)
						);
					} else {
						$idsItensSkus = array_unique(array_column($umaItens, 'id_itens_skus'));
					}

					$idsItensSkus = implode(",", $idsItensSkus);

					$sql = "SELECT id FROM areas WHERE (divergencia = 1 OR avaria = 1) AND id = " . $posicao["id_areas"];
					$areasDivergenciaAvaria = dbFastQuery($sql);

					if (!$areasDivergenciaAvaria && $idsItensSkus) {
						$sql = "SELECT
									COUNT(DISTINCT SKU.id) AS total
								FROM itens_skus SKU
								LEFT JOIN itens_areas IA ON SKU.id_itens = IA.id_itens
								WHERE SKU.id IN ({$idsItensSkus})
									AND (IA.id_areas = " . $posicao["id_areas"] . ")
								GROUP BY IA.id_areas";
						$itensPermitidos = dbFastQuery($sql)[0]['total'];

						if ($itensPermitidos != count(explode(",", $idsItensSkus))) {
							$erros[] = "A posição selecionada para o reposicionamento não está na lista de áreas permitidas.";
						}
					}

				}
			}
		}

		if (intval($gParam['POSICIONAMENTO_LIVRE']['ativo'])==0
			&& intval($gParam["POSICIONAMENTO_VALIDAR_PROPRIETARIO_AREA"]["ativo"])==1
			&& intval($posicao["id_pessoas_proprietario"])>0)
		{
			$mesmo_proprietario=true;
			$id_pessoas_proprietario_divergente=0;
			foreach ($umaItens as $umaItem)
			{
				if ($umaItem["id_pessoas_proprietario"]<>$posicao["id_pessoas_proprietario"])
				{
					$mesmo_proprietario=false;
					$id_pessoas_proprietario_divergente=$umaItem["id_pessoas_proprietario"];
				}
			}
			//gD($umaItens);
			if (!$mesmo_proprietario)
			{
				$sql="SELECT apelido FROM pessoas WHERE id=".$id_pessoas_proprietario_divergente;
				$apelido_proprietario=(dbQuery($sql)[0]["apelido"]);
				$erros[]="A posição informada pertence a <b>".$posicao["apelido"]."</b> e sua carga pertence a <b>".$apelido_proprietario."</b>";
				return $erros;
			}
		}

		if (
			!$gParam["POSICIONAMENTO_LIVRE"]["ativo"]
			&& $gParam["POSICIONAMENTO_VALIDAR_AREA_AVARIA"]["ativo"]
			&& !$posicao["avaria"]
		) {
			// Só permitir UMAs avariadas.
			if ($this->forcarAvaria) {
				$temSaldoAvariado = true;
			} else {
				$temSaldoAvariado = in_array(1, array_column($umaItens, 'avariada'));
			}

			if ($temSaldoAvariado) {
				$erros[] = "Só é permitido posicionar UMAs avariadas em uma área de avaria (".$posicao["area"].")";
				return $erros;

			}
		}

		// Verifica se exige conferência pra posicionar em áreas do tipo "Armazém" (estrutura)
		if ($gParam['EXIGE_CONFERENCIA_ARMAZENAR']['ativo']==1)
		{
			// Verifica se já foi conferida, ou está posicionando fora da estrutura
			// nestes casos, pode ser posicionada, caso contrário dá erro...
			if (!$uma['conferida_saida'] && $rsp[0]['armazenagem'])
			{
				$erros[] = "Esta UMA não foi conferida e não pode ser posicionada nesta área.<br>Realize a <b>Conferência</b> primeiro.";
				return $erros;
			}
		}

		// Verificar se o prédio so comporta um codigo e lote
		if ($posicao['lote_unico'])
		{
			// Pegar codigo e lote do predio
			$where=array();
			$where[]="(P.predio='".$posicao["predio"]."')";
			$where[]="(P.id_areas=".$posicao["id_areas"].")";
			$where[]="(P.id_armazens=".$posicao["id_armazens"].")";
			$where[]="(P.rua='".$posicao["rua"]."')";
			if ($gParam["USA_MODULO_EM_POSICOES"]["ativo"])
			{
				$where[]="(P.modulo='".$posicao["modulo"]."')";
			}
			$where[]="(U.ativo=1)";
			$where[]="(UI.cancelada=0)";
			$where=implode(" AND ", $where);
			$sql="SELECT
					UI.id_itens_skus, UI.lote, SK.codigo
				  FROM umas_itens UI
				  LEFT JOIN umas U ON UI.id_umas = U.id
				  LEFT JOIN posicoes P ON P.id = U.id_posicoes
				  LEFT JOIN itens_skus SK ON SK.id = UI.id_itens_skus
				  WHERE {$where}
				  GROUP BY UI.id_itens_skus, UI.lote
				";
			$verificar_primeira_uma=(dbQuery($sql)[0]);


			$sql="SELECT
					UI.id_itens_skus, UI.lote, SK.codigo
				  FROM umas_itens UI
				  LEFT JOIN umas U ON UI.id_umas = U.id
				  LEFT JOIN posicoes P ON P.id = U.id_posicoes
				  LEFT JOIN itens_skus SK ON SK.id = UI.id_itens_skus
				  WHERE
				  	UI.id_umas=".$uma["id"]."
				  GROUP BY UI.id_itens_skus, UI.lote";

			$verificar_uma=(dbQuery($sql));

			// Em caso da UMA nao ter itens nao validar
			if (count($verificar_uma)>0)
			{
				$verificar_uma=$verificar_uma[0];
				if ($verificar_primeira_uma["codigo"]<>$verificar_uma['codigo'] ||
				$verificar_primeira_uma["lote"]<>$verificar_uma['lote']
				)
				{
					$erros[]="O prédio só comporta o item: <b>".$verificar_primeira_uma["codigo"]."</b>, com o lote ".$verificar_primeira_uma["lote"];
				}

				if ($verificar_primeira_uma['id_itens_skus']<>$verificar_uma['id_itens_skus'])
				{
					$erros[]="Tentando posicionar o item".$verificar_uma["codigo"];
				}

				if ($verificar_primeira_uma["lote"]<>$verificar_uma['lote'])
				{
					$erros[]="Tentando posicionar o lote ".$verificar_uma["lote"];
				}
			}
		}

		if ($posicao['picking'] && $gParam['USA_POSICAO_FIXA_PICKING']['ativo']) {
			$sql = "SELECT
						itens_skus.codigo
					FROM
						umas
					JOIN umas_itens ON
						umas_itens.id_umas = umas.id
					JOIN itens_skus ON
						itens_skus.id = umas_itens.id_itens_skus
					JOIN itens ON
						itens.id = itens_skus.id_itens
					LEFT JOIN itens_areas ON
						itens_areas.id_itens = itens.id
						AND itens_areas.id_posicoes = '" . $posicao['id'] . "'
						AND itens_areas.ativo = 1
					LEFT JOIN posicoes ON
						posicoes.id = itens_areas.id_posicoes
						AND posicoes.picking = 1
					WHERE
						umas.id = {$idUma}
						AND itens_areas.id IS NULL
						AND itens.faz_picking = 1
					GROUP BY
						umas.id,
						itens.id,
						itens_skus.id,
						umas_itens.lote,
						umas_itens.data_validade,
						umas_itens.data_fabricacao,
						umas_itens.id_notas_itens,
						umas_itens.reservada,
						umas_itens.separada,
						umas_itens.avariada,
						umas_itens.bloqueada
					HAVING
						SUM(umas_itens.quantidade) > 0";
			$rs = dbQuery($sql);

			$itemNaoPodePosicionar = array_unique(array_column($rs, 'codigo'));

			if ($itemNaoPodePosicionar) {
				$erros[] = "Esta posição de picking não pode conter os SKUs: " . implode(', ', $itemNaoPodePosicionar) . ", pois não é uma posição fixa para estes SKUs";
			}

		}

		if ($gParam['RESTRINGE_POSICIONAMENTO_LATERAL']['ativo']) {
			$itemPossuiPosicionamentoLateral = $this->restringirPosicionamentoLateral($posicao['id'], $idUma);

			if ($itemPossuiPosicionamentoLateral) {
				$erros[] = "Não é possível posicionar este item, pois já existe um item pesado em uma das posições laterais.";
			}
		}

		if (count($erros)>0)
		{
			return(($erros));
		} else {
			return;
		}
	}




	function encerrarPosicionamento($idUma, $idPosicao)
	{
	//METODO DESCONTINUADO
		$saldos=$this->obtemUMAsComSaldo("U.id='{$idUma}'", true);
		if (count($saldos)==0)
		{
			dbQuery("UPDATE umas SET id_posicoes=0, posicionada='0' WHERE id='{$idUma}'");
		}

		recalcularPosicoes($idPosicao);
	}

	function identificaTrilateralAdequada($id_umas)
	{
		// verifica se foi posicionado na área de espera
		$sql = "SELECT
					U.*, MAX(UI.id_programacao) id_programacao,
					A1.espera espera, A1.expedicao, A1.recebimento, A1.avaria,
					A.codigo_barras armazem,
					A2.expedicao destino_expedicao,
					A2.espera destino_espera,
					A2.recebimento destino_recebimento,
					P1.codigo_barras codigo_posicionada,
					P2.codigo_barras codigo_posicionar,
					P2.modulo, P2.rua, P2.lado, P2.predio, P2.andar, P2.apartamento,P2.codigo_barras posicao,
					P1.modulo o_modulo, P1.rua o_rua, P1.lado o_lado, P1.predio o_predio, P1.andar o_andar, P1.apartamento o_apartamento
				FROM umas U
				LEFT JOIN umas_itens UI ON U.id=UI.id_umas
				LEFT JOIN posicoes P1 ON U.id_posicoes=P1.id
				LEFT JOIN posicoes P2 ON U.id_posicoes_posicionar=P2.id
				LEFT JOIN areas A1 ON P1.id_areas=A1.id
				LEFT JOIN areas A2 ON P2.id_areas=A2.id
				LEFT JOIN armazens A ON P2.id_armazens=A.id
				WHERE U.id=".$id_umas;
		$uma = (dbQuery($sql)[0]);


		// Verifica se tem alguma empilhadeira que é responsável pela posição informada
		// $sql = "SELECT E.*, P.rua
		// 		FROM equipamentos_posicoes EP
		// 		LEFT JOIN equipamentos E ON EP.id_equipamentos=E.id
		// 		LEFT JOIN posicoes P ON E.id_posicoes=P.id
		// 		WHERE
		// 			E.ativo=1 AND
		// 			E.id_tipos_equipamentos=6 AND
		// 			EP.rua_de<='".$uma['rua']."' AND
		// 			EP.rua_ate>='".$uma['rua']."' AND
		// 			EP.predio_de<='".$uma['predio']."' AND
		// 			EP.predio_ate>='".$uma['predio']."' AND
		// 			EP.andar_de<='".$uma['andar']."' AND
		// 			EP.andar_ate>='".$uma['andar']."'
		// 			";
		// Verifica se tem alguma empilhadeira que é responsável pela posição informada
		$sql = "SELECT E.*, P.rua
				FROM equipamentos_posicoes EP
				LEFT JOIN equipamentos E ON EP.id_equipamentos=E.id
				LEFT JOIN posicoes P ON E.id_posicoes=P.id
				WHERE
					E.ativo=1 AND
					E.id_tipos_equipamentos=6
				ORDER BY (EP.rua_de<='".$uma['o_rua']."' AND EP.rua_ate>='".$uma['o_rua']."') DESC
				";

		/*
		if ($gParam['USA_MODULO_EM_POSICOES']['ativo'])
		{
			$sql.=" AND EP.modulo='".$uma['modulo']."'";
		}
		*/
		$rse = dbQuery($sql);
		//gDR($sql);gDR($rse);exit;

		$equipamentosDaArea = array();
		$equipamentoSelecionado = 0;
		$ruaMaisProxima = 1000;
		foreach ($rse as $row)
		{
			$equipamentosDaArea[$row['id']] = $row;
			$equipamentoSelecionado = $row['id'];
			$distancia = abs($row['rua']-$uma['rua']);
		}
		if (count($equipamentosDaArea)>1)
		{
			// Tem mais de uma empilhadeira que atende na área, então
			// - Seleciona a mais próxima se estiver na mesma rua
			// - Seleciona a desocupada se estiver em outra rua
			foreach ($equipamentosDaArea as $id=>$equip)
			{
				if ($equip['rua']==$uma['rua'])
				{
					// Achou um equipamento que já está nesta rua!
					$equipamentoSelecionado = $id;
					break;
				}
				// Verifica qual equipamento está mais perto da rua a posicionar

				if (abs($equip['rua']-$uma['rua'])<$distancia)
				{
					$equipamentoSelecionado = $id;
					$distancia = abs($equip['rua']-$uma['rua']);
				}
			}
		}
		$uma['id_equipamentos'] = $equipamentoSelecionado;
		
		return($uma);
	}


	function posicionouEmEspera($id_umas)
	{
		global $gParam, $usrId;
		$usrId = intval($usrId);

		/*
			Passos necessários

			- Identificar qual trilateral está disponível
			- Identificar qual trilateral está mais próxima
			- Reordenar atividades para aproveitar o vai-e-vem
			- Verifica se foi posicionada em área de espera, para ativar o posicionamento definitivo

			$operacao = L (load) ou U (unload)
		*/

		if ($gParam['INTERFACE_JUNGHEINRICH']['ativo'])
		{

			$uma = $this->identificaTrilateralAdequada($id_umas);

			$sql="SELECT id, criar_eventos FROM programacao WHERE id = '".$uma["id_programacao"]."'";
			$programacao=(dbQuery($sql)[0]);
			//if (intval($programacao["criar_eventos"])==1)
			{
				$equipamentoSelecionado = $uma['id_equipamentos'];
				if ($uma['espera']==1 && $uma['destino_expedicao']==0 && $uma['destino_recebimento']==0 && $uma['id_posicoes']<>$uma['id_posicoes_posicionar'])
				{
					if ($equipamentoSelecionado>0)
					{
						// Cria evento...
						$json = "{id_tipos_eventos:7; id_umas: ".$id_umas."; id_equipamentos: ".$equipamentoSelecionado."}";
						$this->inserirEventoNaFila($json);
					}
				}
			}
		}
	}

	function enviaComandoParaJung($id_umas, $id_eventos=0)
	{
		global $gParam, $usrId;
		$usrId = intval($usrId);
		/*
			Passos necessários

			- Identificar qual trilateral está disponível
			- Identificar qual trilateral está mais próxima
			- Reordenar atividades para aproveitar o vai-e-vem
			- Verifica se foi posicionada em área de espera, para ativar o posicionamento definitivo

			$operacao = L (load) ou U (unload)
		*/
		if ($gParam['INTERFACE_JUNGHEINRICH']['ativo'])
		{
			$sql="SELECT expedicao, id_tipos_eventos, id_equipamentos FROM eventos WHERE id=".$id_eventos;
			$evento=(dbQuery($sql)[0]);

			$uma = $this->identificaTrilateralAdequada($id_umas);
			if ($evento["expedicao"]==1)
			{
				$equipamentoSelecionado = $evento["id_equipamentos"];
			} else
			{
				$equipamentoSelecionado = $uma['id_equipamentos'];
			}

			// Se não tiver equipamento disponível não inserir no webservice.
			if (intval($equipamentoSelecionado)==0)
			{
				return;
			}

			if ($evento["expedicao"]==1)
			{
				if ($evento['id_tipos_eventos']==8)
				{
					$id_posicoes_palete_vazio=$this->buscarPaleteVazio($uma['id_posicoes']);
					$sql="SELECT codigo_barras FROM posicoes WHERE id=".$id_posicoes_palete_vazio;
					$palete_vazio=(dbQuery($sql)[0]);
					$cmd="L,".$palete_vazio['codigo_barras'];
					$json="{id_umas: ".$uma["id"]."; id_eventos:".$id_eventos."; id_programacao: ".$uma['id_programacao']."; id_equipamentos: ".$equipamentoSelecionado." ; cmd: ".$cmd."; tipo:L;}";
					jungComando($json);
				} else
				{
					// Pegar a UMA.
					$cmd="L,".$uma['codigo_posicionada'];
					$json="{id_umas: ".$id_umas."; id_eventos:".$id_eventos."; id_programacao: ".$uma['id_programacao']."; id_equipamentos: ".$equipamentoSelecionado." ; cmd: ".$cmd."; tipo:L;}";
					jungComando($json);
				}
			} else if ($uma['espera']==1 && $uma['destino_expedicao']==0 && $uma['destino_recebimento']==0 && $uma['id_posicoes']<>$uma['id_posicoes_posicionar'])
			{
				// gDR("EQUIPAMENTOS:");
				// gDR($equipamentosDaArea);
				// gDR("EQUIPAMENTO SELECIONADO: ".$equipamentoSelecionado);
				if ($equipamentoSelecionado>0)
				{
					// Pegar o palete
					$cmd="L,".$uma['codigo_posicionada'];
					$json="{id_umas: ".$id_umas."; id_programacao: ".$uma['id_programacao']."; id_equipamentos: ".$equipamentoSelecionado." ; id_eventos: ".$id_eventos."; cmd: ".$cmd."; tipo:L;}";
					jungComando($json);

				}
			} elseif ($uma['espera']==0 && $uma['destino_espera']==1 && $uma['id_posicoes']<>$uma['id_posicoes_posicionar'])
			{
				// Verifica se tem alguma empilhadeira que é responsável pela posição atual da UMA
				$sql = "SELECT E.*, P.rua
						FROM equipamentos_posicoes EP
						LEFT JOIN equipamentos E ON EP.id_equipamentos=E.id
						LEFT JOIN posicoes P ON E.id_posicoes=P.id
						WHERE
							E.id_tipos_equipamentos=6 AND
							EP.rua_de<='".$uma['o_rua']."' AND
							EP.rua_ate>='".$uma['o_rua']."' AND
							EP.predio_de<='".$uma['o_predio']."' AND
							EP.predio_ate>='".$uma['o_predio']."' AND
							EP.andar_de<='".$uma['o_andar']."' AND
							EP.andar_ate>='".$uma['o_andar']."'
							";
				if ($gParam['USA_MODULO_EM_POSICOES']['ativo'])
				{
					$sql.=" AND EP.modulo='".$uma['modulo']."'";
				}
				$rse = dbQuery($sql);
//gDR($sql);gDR($rse);


				$equipamentosDaArea = array();
				$equipamentoSelecionado = 0;
				$ruaMaisProxima = 1000;
				foreach ($rse as $row)
				{
					$equipamentosDaArea[$row['id']] = $row;
					$equipamentoSelecionado = $row['id'];
					$distancia = abs($row['rua']-$uma['rua']);
				}
				if (count($equipamentosDaArea)>1)
				{
					// Tem mais de uma empilhadeira que atende na área, então
					// - Seleciona a mais próxima se estiver na mesma rua
					// - Seleciona a desocupada se estiver em outra rua
					foreach ($equipamentosDaArea as $id=>$equip)
					{
						if ($equip['rua']==$uma['rua'])
						{
							// Achou um equipamento que já está nesta rua!
							$equipamentoSelecionado = $id;
							break;
						}
						// Verifica qual equipamento está mais perto da rua a posicionar

						if (abs($equip['rua']-$uma['rua'])<$distancia)
						{
							$equipamentoSelecionado = $id;
							$distancia = abs($equip['rua']-$uma['rua']);
						}
					}
				}
// gDR("EQUIPAMENTOS:");
// gDR($equipamentosDaArea);
// gDR("EQUIPAMENTO SELECIONADO: ".$equipamentoSelecionado);


				if ($equipamentoSelecionado>0)
				{
					// Pegar o palete
					$cmd="L,".$uma['codigo_posicionada'];
					$json="{id_umas: ".$id_umas."; id_programacao: ".$uma['id_programacao']."; id_equipamentos: ".$equipamentoSelecionado." ;cmd: ".$cmd."}";


					//gDR($json);
					jungComando($json);
				}
			}
		}
	}


	public function posicionar($idUma, $idPosicaoAtual, $transferirTotvs = 1, $testarEspera = true, $ressuprimento = 0)
	{
		global $usrId, $gId, $gParam, $g;
		$idPosicoesAnterior = 0;
		// Verifica a posição anterior, e remove de lá
		$sql = "
			SELECT id_posicoes, posicionada, id_programacao, codigo_barras, id_posicoes_posicionar
			FROM umas
			WHERE id = " . $idUma;
		$rs = dbQuery($sql)[0];

		if ($rs['id_posicoes'] && $rs['posicionada'] == 1) {
			$idPosicoesAnterior = $rs['id_posicoes'];
		}
		$gId = $rs['id_programacao'];
		$uma = $rs['codigo_barras'];


		if ($transferirTotvs && $gParam['PERFIL_FABRICANTE']['ativo']) {
			$transferiu = TOTVS_transfereArmazem("{id_programacao: 0; buscar_uma: U.id=".$idUma."; origem: ".$idPosicoesAnterior.";destino: ".$idPosicaoAtual."}");
			if (!$transferiu) {
				return false;
			}
			// Só faz o posicionamento se for feito no Protheus primeiro ou se a empresa não tiver Protheus
		}


		if ($rs['id_posicoes'] > 0 && $rs['posicionada'] == 1) {
			$sql = "UPDATE posicoes SET quantidade_posicionada=IF(quantidade_posicionada>0, quantidade_posicionada-1,0) WHERE id=".$rs['id_posicoes'];
			dbFastQuery($sql);
		}
		// Posicionando...
		$mtz = array();
		if ($idPosicaoAtual == $rs['id_posicoes_posicionar']) {
			// Se está na posição prevista, zera posição futura
			$mtz["id_posicoes_posicionar"] = 0;
		}

		$mtz["id_pessoas_posicionou"] = $usrId;
		$mtz["id_posicoes"] = $idPosicaoAtual;
		$mtz["posicionada"] = 1;
		$mtz["data_posicionamento"] = date('Y-m-d H:i:s');
		dbUpdate("umas", $mtz, $idUma);

		// Inserindo registro histórico para rastreamento...
		$mtz = array();
		$mtz["id_umas_para"] = 0;
		$mtz["tipo"] = "P"; // P) Posicionamento
		$mtz["id_posicoes"] = $idPosicaoAtual;
		$mtz["descricao"] = "Posicionamento";
		if ($ressuprimento) {
			$mtz["descricao"] = "Ressuprimento (posicionou)";
		}
		$this->salvaUMAMovimentos($idUma, $mtz);

		$this->executaAtividade("Posicionou ".$uma, 0, 2);

		if ($idPosicoesAnterior) {
			recalcularPosicoes([$idPosicoesAnterior]);
		}

		if ($idPosicaoAtual) {
			recalcularPosicoes([$idPosicaoAtual]);
		}

		if (
			$g == 'posicionamento'
			&& $gParam['INTEGRACAO_GMI']['ativo']
			&& count(explode(',', $gParam['INTEGRACAO_GMI']['valor']))
		) {
			$saldoIntegrar = $this->obtemUMAsComSaldo(
				"U.id = {$idUma} AND UI.id_pessoas_proprietario IN(" . $gParam['INTEGRACAO_GMI']['valor'] . ')',
				true, $prioridade = 0, $orderBy = "",
				$priorizarPaleteAberto = 0, $groupBy = "", $priorizarPaleteFechado = 0, $having = "",
				$limit = "1", $addGroupBy = "", $select = "U.id")[0]['id'];
			if ($saldoIntegrar) {
				$this->apenasValidarComunicacaoSap = 0;
				$this->comunicarMovimentacaoSap(
					$idUma = $idUma,
					$idPosicaoAnterior = $idPosicoesAnterior,
					$idPosicaoAtual = $idPosicaoAtual,
					$idUmasItensAnterior = 0,
					$idUmasItensNova = 0,
					$obterQuantidadeTotal = 1
				);
			}

			$this->moverParaQualidadeSeValida($idUma);
		}

		// Verifica se precisa criar algum evento pra Trilateral
		if ($testarEspera) {
			$this->posicionouEmEspera($idUma);
		}

		if($_REQUEST['id_posicoes_atual']){
			$idPosicaoAtual = $_REQUEST['id_posicoes_atual'];
		}

		// recalcularPosicoes([$idPosicoesAnterior, $idPosicaoAtual]); TODO:: analisando necessidade em manter esta linha
		// Verifica se existe evento de posicionar, e dá baixa se existir...
		$sql = "UPDATE eventos SET finalizado=1, id_pessoas_finalizou=".$usrId.",data_finalizado='".date('Y-m-d H:i:s')."' WHERE id_tipos_eventos=2 AND cancelado=0 AND finalizado=0 AND id_umas=".$idUma." AND id_posicoes=".$idPosicaoAtual;
		dbFastQuery($sql);
		// Verifica se existe evento de separar, conferir ou saída, e atualiza posição se existir...
		$sql = "UPDATE eventos SET id_posicoes=".$idPosicaoAtual." WHERE id_tipos_eventos<>2 AND cancelado=0 AND finalizado=0 AND id_umas=".$idUma." AND id_posicoes<>".$idPosicaoAtual;
		dbFastQuery($sql);
		return true;
	}


	function converterQuantidadeSku($skuProgramado, $skuSelecionado)
	{
		return ($skuProgramado / $skuSelecionado);
	}


	function aptoAtualiza($gId, $apto)
	{
		$sql = "UPDATE itens SET apto=$apto WHERE id=".$gId;
		dbQuery($sql);
		if ($apto==0)
		{
			// Desativa OS que ainda não foram iniciadas e tem este item
			$sql = "SELECT DISTINCT P.id
					FROM programacao P
					LEFT JOIN programacao_itens PI ON P.id=PI.id_programacao
					LEFT JOIN itens_skus SK ON PI.id_itens_skus=SK.id
					LEFT JOIN itens I ON SK.id_itens=I.id
					WHERE P.iniciada=0 AND P.ativo=1 AND I.id=".$gId;
			$rs = dbQuery($sql);
			foreach($rs as $row)
			{
				dbQuery("UPDATE programacao SET ativo=0 WHERE id=".$row['id']);
			}
		}
	}

	function desfazerOSFracionada($gId)
	{
		$sql="SELECT id_primeira_programacao FROM programacao_entrada_fracionada WHERE id_programacao='{$gId}'";
		$idProgramacaoPai=dbQuery($sql)[0]["id_primeira_programacao"];
		$sql="SELECT PEF.id id_entrada_fracionada, PEF.id_programacao, PR.id_tipos_programacao FROM programacao_entrada_fracionada PEF
			  LEFT JOIN programacao PR ON PR.id = PEF.id_programacao
			  WHERE id_primeira_programacao='{$idProgramacaoPai}'";
		$programacoes=dbQuery($sql);
		$liberar=true;
		foreach ($programacoes as $programacao)
		{
			$mtz=array();
			$mtz["ativo"]=0;
			$mtz["situacao"]=0;
			dbUpdate("programacao_entrada_fracionada", $mtz, $programacao["id_entrada_fracionada"]);
			$liberar=$this->desfazerOS($programacao['id_programacao']);
		}
		return ($liberar);
	}

	function desfazerOS($id)
	{
		global $o, $html, $gParam, $usrId;
		$sql            = "SELECT * FROM programacao WHERE id=".$id;
		$programacao    = dbQuery($sql)[0];
		$dataExecucaoOS = $programacao["data_execucao_final"];
		$tipo           = $programacao['id_tipos_programacao'];
		$liberar=false;
		if (
			$gParam["LIMITE_DESFAZER_OS_ENTRADA"]["ativo"]
			&& $dataExecucaoOS <> '0000-00-00 00:00:00'
		) {
			$hoje           = date('Y-m-d H:i:s');
			$limite=$gParam["LIMITE_DESFAZER_OS_ENTRADA"]["valor"];
			$expirada       = date('Y-m-d H:i:s', strtotime("+{$limite} days", strtotime($dataExecucaoOS)));
			if (strtotime($hoje)<strtotime($expirada))
			{
				$liberar=true;
			}
		} else
		{
			// Parametro esta desativado, empresa deseja deixar sempre permitido.
			$liberar=true;
		}
		if (!$liberar) {
			return false;
		}

		// Se for uma entrada, transfere de volta para a produção ou personalização
		if ($tipo==1 || $tipo==14 || $tipo==19 || $tipo==23) {
			/* Excluindo contagens */
			dbQuery("DELETE FROM contagens WHERE id_programacao='{$id}'");
			dbQuery("DELETE FROM contagens_umas WHERE id_programacao='{$id}'");
			dbQuery("DELETE FROM contagens_umas_itens WHERE id_programacao='{$id}'");

			TOTVS_transfereArmazem("{id_programacao: ".$id."; buscar_uma: U.id_programacao=".$id."; origem: ".TOTVS_ARMAZEM_CONTROLE.";destino: ".TOTVS_ARMAZEM_PRODUCAO."}");

			$sql = "
				SELECT U.id, U.id_posicoes
				FROM umas_itens UI
				JOIN umas U ON U.id = UI.id_umas
				WHERE UI.id_programacao = '{$id}'
					AND UI.cancelada = 0
				GROUP BY U.id, U.id_posicoes";
			$rs = dbQuery($sql);
			if ($rs[0]['id']) {
				$sql = "
					UPDATE umas
					SET ativo = 0,
						posicionada = 0,
						id_posicoes = 0,
						conferida = 0,
						conferida_saida = 0,
						ordem = 0
					WHERE id IN(" . implode(',', array_column($rs, 'id')) . ")";
				dbFastQuery($sql);
				recalcularPosicoes(array_column($rs, 'id_posicoes'));
			}
		}

		/* Cancelando saldo da uma */
		$sql = "
			UPDATE
				umas_itens
			SET cancelada = 1,
				data_cancelamento = NOW(),
				id_pessoas_cancelou = '{$usrId}'
			WHERE cancelada = 0 AND id_programacao=".$id;
		dbQuery($sql);

		$sql = "SELECT id_umas FROM umas_itens WHERE id_programacao = {$id}";
		$rs  = dbFastQuery($sql);
		atualizarAtivacaoUMA(array_column($rs, 'id_umas'));

		dbQuery("UPDATE programacao_itens SET conferida = 0, quantidade_aceita = 0, quantidade_conferida = 0, gerou_entrada_dinamica = 0 WHERE id_programacao='{$id}'");
		dbQuery("UPDATE programacao_atividades SET cancelada='1' WHERE id_programacao='{$id}'");
		dbQuery("UPDATE programacao SET ativo = 0, data_execucao_inicio='0000-00-00 00:00:00',data_execucao_final='0000-00-00 00:00:00', iniciada=0, executada=0, reservada=0, separada=0, divergencia=0, conferida_saida=0, id_pessoas_executou=0 WHERE id='{$id}'");

		$dadosGatilho = [
			"idProgramacao" => $id,
			"idPessoasProprietario" => $programacao['id_pessoas_proprietario']
		];
		dispararGatilho("desfazerTudoEntrada", $dadosGatilho);

		$this->executaAtividade("Desfez a entrada da OS", 0, 11);
		userLog("Desfez entrada da OS: ".$this->linkParaOS($programacao["os"]));
		return (true);
	}

	function desfazerOSSaida($id)
	{
		global $o, $html, $usrId;
		/* Como medida de segurança verificar o tempo novamente ! */

		// Cancelamento eventos referentes as umas
		$programacao=(dbQuery("SELECT os, id, id_tipo_reserva FROM programacao WHERE id=".$id)[0]);
		$dataExecucaoOS=gFieldById("programacao", $id, "data_execucao_final");
		if (intval($dataExecucaoOS)==0)
		{
			$dataExecucaoOS=gFieldById("programacao", $id, "data_execucao_inicio");
		}
		$hoje=date('Y-m-d H:i:s');
		$expirada=date('Y-m-d H:i:s', strtotime("+120 days", strtotime($dataExecucaoOS)));

		if (strtotime($hoje)<strtotime($expirada))
		{
			// TODO ANALISAR... BUSCAR UMAS DA OS DE SAÍDA
			//TOTVS_transfereArmazem("{id_programacao: ".$id."; buscar_uma: U.id_programacao=".$id."; origem: ".TOTVS_ARMAZEM_CONTROLE.";destino: ".TOTVS_ARMAZEM_PRODUCAO."}");
			// TODO ANALISAR... BUSCAR UMAS DA OS DE SAÍDA
			/* Cancelando saldo da uma*/
			$sql="UPDATE
					umas_itens
				SET cancelada=1,
					data_cancelamento='".date('Y-m-d H:i:s')."',
					id_pessoas_cancelou = '{$usrId}'
				WHERE cancelada = 0 AND id_programacao={$id}";
			dbQuery($sql);
			$sql = "SELECT id_umas FROM umas_itens WHERE id_programacao = {$id}";
			$rs  = dbFastQuery($sql);
			atualizarAtivacaoUMA(array_column($rs, 'id_umas'));

			$this->cancelarEventos('{id_programacao:'.$id.';}');

			$sql="
				SELECT U.id, U.id_posicoes FROM umas_itens UI
				LEFT JOIN umas U ON U.id = UI.id_umas
				WHERE UI.id_programacao = '{$id}'
				GROUP BY U.id, U.id_posicoes;
			";
			$rs=dbQuery($sql);
			foreach($rs as $row)
			{
				$mtz=array();
				$mtz["ativo"]=0;
				$mtz["posicionada"]=0;
				$mtz["id_posicoes"]=0;
				$mtz["ordem"]=0;
				$mtz["conferida_saida"]=0;
				$mtz["data_saida"]='0000-00-00 00:00:00';
				$mtz["data_conferencia_saida"]='0000-00-00 00:00:00';
				$mtz["ativo"]=1;
				dbUpdate("umas", $mtz, $row["id"]);
				dbQuery("UPDATE posicoes SET quantidade_posicionada=quantidade_posicionada-1 WHERE id=".$row['id_posicoes']);
			}

			dbQuery("UPDATE programacao_atividades SET cancelada='1' WHERE id_programacao='{$id}'");
			$addTipoReserva = '';
			if ($programacao['id_tipo_reserva'] != 3) { //3 = reserva de UMA somente em data critica
				$addTipoReserva = " id_tipo_reserva = '0', ";
			}
			dbQuery("UPDATE programacao SET {$addTipoReserva} data_execucao_inicio='0000-00-00 00:00:00',data_execucao_final='0000-00-00 00:00:00', separada='0', iniciada=0, executada=0, reservada=0, separada=0, conferida_saida=0, id_pessoas_executou=0 WHERE id='{$id}'");

			/* Cancelar possiveis notas criadas pela programação */
			$sql="
				SELECT N.id, N.id_nfe
				FROM notas N
				LEFT JOIN nfe NFE ON NFE.id = N.id_nfe
				WHERE N.id_programacao='{$id}'";
			$notas=dbQuery($sql);
			if (count($notas)>0)
			{
				foreach ($notas as $nota)
				{
					$mtz=array();
					$mtz["data_movimento"]=date('Y-m-d H:i:s');
	                $mtz["cancelada"]=1;
	                dbUpdate("notas", $mtz, $nota["id"]);

	                $mtz=array();
	                $mtz["cancelada"]=1;
	                dbUpdate("nfe", $mtz, $nota["id_nfe"]);
				}
			}

			// Registrando Log
			$this->executaAtividade("Desfez saída da OS", 0, 12);
			userLog("Desfez saída da OS: ".$this->linkParaOS($programacao["os"]));

			return (true);
		} else
		{
			return (false);
		}
	}

	/**
	 * Desfaz tudo o que foi feito em uma OS que ainda não foi concluída
	 */
	function desfazerAtividadesDaOS($gId, $transfereDeVolta=true) // @note Desafazer geral
	{
		global $usrId, $o, $html, $gParam;
		$usrId = intval($usrId);
		// Só desfaz se ainda não foi concluída
		if (count($this->os)>0)
		{
			$this->filtro= "p.id=".$gId;
			$this->os = $this->obtemRegistros()[0];
		}
		/* Usar data execução início pois nem sempre a os está executada */
		$dataExecucaoOS= (intval($this->os['data_execucao_final'])>0)
			? $this->os['data_execucao_final']
			: $this->os['data_execucao_inicio'];
		$dataCadastroOS = $this->os['data_cadastro'];

		$dataExecucaoOS = ($dataExecucaoOS == '0000-00-00 00:00:00') ? $dataCadastroOS : $dataExecucaoOS;

		$expirada=date('Y-m-d H:i:s', strtotime("+".intval($gParam['LIMITE_DESFAZER_OS_GERAL']['valor'])." days", strtotime($dataExecucaoOS)));
		$hoje=date('Y-m-d H:i:s');
		if (strtotime($hoje)<strtotime($expirada) || $this->os["id_tipos_programacao"]==32)
		{
			// if ($this->os['executada']==0 || substr($this->os['data_execucao_final'],0,10)==date("Y-m-d"))
			// {

			// Entrada
			switch ($this->os["id_tipos_programacao"])
			{
				case 1:  // Entrada - PR
				case 9:  // Cross Docking
				case 14: // Entrada - PR - EXCEDENTE
				case 19: // Entrada - PR - PERDA
				case 20: // Entrada - PE
				case 21: // Entrada - PE - PERDA
				case 23: // Entrada - PR - FRACIONADA
				case 27: // Entrada - PE - FRACIONADA
				case 29: // Devolução
					// Libera posições ocupadas...
					if ($this->os["id_tipos_programacao"]==23)
					{
						$sql="SELECT
								id_programacao
							  FROM programacao_entrada_fracionada
							  WHERE id_primeira_programacao=".$gId."
							  GROUP BY id_programacao
							  ";
						$programacoes_fracionadas=dbQuery($sql);
						if (count($programacoes_fracionadas)>0)
						{
							foreach ($programacoes_fracionadas as $programacao_fracionada)
							{
								$id_programacao=intval($programacao_fracionada["id_programacao"]);
								$sql = "SELECT DISTINCT U.id, U.id_posicoes FROM umas_itens UI LEFT JOIN umas U ON UI.id_umas=U.id WHERE UI.id_programacao=".$id_programacao;
								$rst = dbQuery($sql);
								foreach ($rst as $row)
								{
									dbQuery("UPDATE posicoes SET quantidade_posicionada=quantidade_posicionada-1 WHERE id=".intval($row['id_posicoes']));
								}

								// Limpa contagens
								$sqld="DELETE FROM contagens WHERE id_programacao=".$id_programacao;
								dbQuery($sqld);
								$sqld="DELETE FROM contagens_umas WHERE id_programacao=".$id_programacao;
								dbQuery($sqld);
								$sqld="DELETE FROM contagens_umas_itens WHERE id_programacao=".$id_programacao;
								dbQuery($sqld);

								if ($transfereDeVolta)
								{
									TOTVS_transfereArmazem("{id_programacao: ".$id_programacao."; buscar_uma: U.id_programacao=".$id_programacao."; origem: ".TOTVS_ARMAZEM_CONTROLE.";destino: ".TOTVS_ARMAZEM_PRODUCAO."}");
								}
								$sqlu="UPDATE
										umas_itens
									  SET id_pessoas_cancelou=".$usrId.",
									  cancelada=1,
									  data_cancelamento='".date('Y-m-d H:i:s')."'
									  WHERE cancelada=0 AND id_programacao=".$id_programacao;
								dbQuery($sqlu);
								$sql = "SELECT id_umas FROM umas_itens WHERE id_programacao = {$id_programacao}";
								$rs  = dbFastQuery($sql);
								atualizarAtivacaoUMA(array_column($rs, 'id_umas'));

								// Cancelar eventos referentes a programação de entrada.
								$this->cancelarEventos("{id_tipos_eventos:2; id_programacao:".$id_programacao.";}");

								$sqlu="UPDATE programacao_itens SET conferida=0,quantidade_aceita=0,quantidade_conferida=0 WHERE id_programacao=".$id_programacao;
								dbQuery($sqlu);

								$sqlu="UPDATE programacao_atividades SET cancelada=1 WHERE id_programacao=".$id_programacao;
								dbQuery($sqlu);

								$sqlu="UPDATE programacao SET data_execucao_inicio='0000-00-00 00:00:00',data_execucao_final='0000-00-00 00:00:00', iniciada=0, executada=0, separada=0, conferida_saida=0, divergencia=0, id_pessoas_executou=0 WHERE id=".$id_programacao;
								dbQuery($sqlu);

								$sql = "DELETE FROM ocorrencias WHERE id_programacao = {$gId}";
								dbQuery($sql);

								$this->calculaUMAsNecessarias($id_programacao);
							}
						}
					} else {
						$movimentacoes=verificarMovimentacoesUmas($gId);
						if (count($movimentacoes)>0) {
							$tableRetorno .= $o->msgDanger("Não é possível desfazer a entrada pois as seguintes UMAs possuem movimentações nas suas respectivas programações:");
							$tableRetorno .= $o->tableBegin("big", true);	
							$mtz = array();
							$mtz[]="<-UMA";
							$mtz[]="<-OS";
							$tableRetorno .= $o->tableRow($mtz, "header");
							foreach ($movimentacoes as $movimentacao) {
								$mtz=array();
								$mtz[]="<-".$movimentacao['codigo_barras'];
								$mtz[]="<-".$movimentacao['os'];
								$tableRetorno .= $o->tableRow($mtz, "detail");
							}
							$tableRetorno.=$o->tableEnd();
							return $tableRetorno;
						}

						// Limpa contagens
						dbQuery("DELETE FROM contagens WHERE id_programacao=".$gId);
						dbQuery("DELETE FROM contagens_umas WHERE id_programacao=".$gId);
						dbQuery("DELETE FROM contagens_umas_itens WHERE id_programacao=".$gId);
						if ($transfereDeVolta) {
							TOTVS_transfereArmazem("{id_programacao: ".$gId."; buscar_uma: U.id_programacao=".$gId."; origem: ".TOTVS_ARMAZEM_CONTROLE.";destino: ".TOTVS_ARMAZEM_PRODUCAO."}");
						}
						$sql = "
							SELECT umas.id, umas.id_posicoes
							FROM umas_itens
							JOIN umas umas ON umas.id = umas_itens.id_umas
							WHERE umas_itens.id_programacao = '{$gId}'
								AND umas_itens.cancelada = 0
							GROUP BY umas.id, umas.id_posicoes";
						$rs = dbQuery($sql);
						if ($rs[0]['id']) {
							$sql = "
								UPDATE umas
								SET ativo = 0,
									posicionada = 0,
									id_posicoes = 0,
									conferida = 0,
									conferida_saida = 0,
									ordem = 0
								WHERE id IN(" . implode(',', array_column($rs, 'id')) . ")";
							dbFastQuery($sql);
							recalcularPosicoes(array_column($rs, 'id_posicoes'));
						}

						$sql = "
							UPDATE
								umas_itens
							SET cancelada = 1,
								data_cancelamento = NOW(),
								id_pessoas_cancelou = '{$usrId}'
							WHERE cancelada = 0 AND id_programacao=".$gId;
						dbQuery($sql);
						$sql = "SELECT id_umas FROM umas_itens WHERE id_programacao = {$gId}";
						$rs  = dbFastQuery($sql);
						atualizarAtivacaoUMA(array_column($rs, 'id_umas'));

						// Cancelar eventos referentes a programação de entrada.
						$this->cancelarEventos("{id_tipos_eventos:2; id_programacao:".$gId.";}");
					}
				break;

				case 2:  // Saída
					dbQuery("UPDATE notas  SET cancelada=1 WHERE id_programacao=".$gId);
				case 3:  // Separação PE
				case 4:  // Separação PE-Perda
				case 22: // Saída Avaria
				case 24: // Separação FA
				case 25: // Separação PA
				case 32: // Produção
					// @todo Desfazer produção ddeve ser analizada melhor!
					// CORRE O RISCO DE GERAR UMAS ATIVAS=0 ERRONEAMENTE!
					// Obs: Os registros de reserva não são cancelados na opção "Desfazer", pois a opção "Liberar reserva" faz isto
					if (($this->os["id_tipos_programacao"])==25)
					{
						dbQuery("UPDATE umas_itens SET id_pessoas_cancelou=".$usrId.",cancelada=1 WHERE (separada=1 OR (reservada=1 AND tipo='-') OR (separada=0 AND reservada=0 AND quantidade>0)) AND id_programacao=".$gId);
					} elseif  ($this->os["id_tipos_programacao"]==32)
					{
						$sql="UPDATE umas_itens 
								SET id_pessoas_cancelou=".$usrId.",cancelada=1 
								WHERE id_programacao=".$gId." AND cancelada=0 AND reservada=0 ";
						dbQuery($sql);
						$sql="UPDATE umas_itens 
								SET id_pessoas_cancelou=".$usrId.",cancelada=1 
								WHERE id_programacao=".$gId." AND cancelada=0 AND reservada=1 AND tipo='-' ";
						dbQuery($sql);
					} else {
						dbQuery("UPDATE umas_itens SET id_pessoas_cancelou=".$usrId.",cancelada=1 WHERE (separada=1 OR (reservada=1 AND tipo='-')) AND cancelada = 0 AND id_programacao=".$gId);
					}
					$sql = "SELECT id_umas FROM umas_itens WHERE id_programacao = {$gId}";
					$rs  = dbFastQuery($sql);
					atualizarAtivacaoUMA(array_column($rs, 'id_umas'));

					$sql = "SELECT DISTINCT U.id FROM umas_itens UI LEFT JOIN umas U ON UI.id_umas=U.id WHERE UI.id_programacao=".$gId;
					$rst = dbQuery($sql);
					foreach ($rst as $row)
					{
						$sqlu="UPDATE umas SET data_conferencia_saida='0000-00-00 00:00:00', data_saida='0000-00-00 00:00:00', conferida_saida=0 WHERE id=".$row['id'];
						dbQuery($sqlu);
					}

					// TODO: Deve retornar a posição anterior?????
				break;

				case 6:
				case 30:
				case 33:
					// Inventário
					$sql = "SELECT
								U.codigo_barras
							FROM
								programacao P
							INNER JOIN inventarios INV ON
								INV.id_programacao = P.id
							INNER JOIN umas U ON
								U.id = INV.id_umas
							INNER JOIN umas_itens UI ON
								UI.id_umas  = U.id
							WHERE P.id = {$gId}
								AND UI.data > P.data_execucao_final
								AND UI.cancelada = 0
								AND P.executada = 1
							GROUP BY U.id";
					$rs = dbQuery($sql);

					if ($rs) {
                        $umasMovimentadas = array_column($rs, 'codigo_barras');

                        if ($umasMovimentadas) {
                            return 'Esta programação não pode ser desfeita, pois as seguintes UMAs foram movimentadas após o inventário:<br>'.implode('<br>', array_map('linkParaUMA', $umasMovimentadas));
                        }
                    }

					if ($this->os['executada']) {
						dbQuery("UPDATE umas_itens SET id_pessoas_cancelou=".$usrId.",cancelada=1 WHERE id_programacao=".$gId);
						$sql = "SELECT id_umas FROM umas_itens WHERE id_programacao = {$gId}";
						$rs  = dbFastQuery($sql);
						atualizarAtivacaoUMA(array_column($rs, 'id_umas'));

						dbFastQuery("
							UPDATE programacao
							SET data_execucao_final = '0000-00-00 00:00:00',
								executada = 0, 
								id_pessoas_executou = 0
							WHERE id = {$gId}"
						);
		
						$mtz = array();
						$mtz["id_programacao"] = $gId;
						$mtz["id_tipos_atividades"] = 4;
						$mtz["id_pessoas"] = $usrId;
						$mtz["id_itens_skus"] = 0;
						$mtz["cancelada"] = 0;
						$mtz["data"] = date('Y-m-d H:i:s');
						$mtz["quantidade"] = 0;
						$mtz["descricao"] = "Reabriu contagens do inventário";
						dbInsert("programacao_atividades", $mtz);
						return;
					} 

					
					$idInv = implode(", ", array_column(dbQuery("SELECT id FROM inventarios WHERE id_programacao = {$gId}"), 'id'));
					dbQuery("DELETE FROM programacao_inventario_posicoes WHERE id_programacao=".$gId);
					dbQuery("
						DELETE inventarios_skus
						FROM inventarios_skus
						JOIN inventarios ON inventarios.id = inventarios_skus.id_inventarios
						WHERE inventarios.id_programacao = {$gId}"
					);
					dbQuery("DELETE FROM inventarios WHERE id_programacao=".$gId);
					dbQuery("TRUNCATE TABLE inventarios_integracao");
					//dbQuery("DELETE FROM totvs_inventario WHERE id_programacao=".$gId);
					dbQuery("UPDATE programacao_inventario_posicoes SET conferida=0, contagens=0 WHERE id_programacao=".$gId);
					dbQuery("UPDATE umas_itens SET id_pessoas_cancelou=".$usrId.",cancelada=1 WHERE id_programacao=".$gId);
					$sql = "SELECT id_umas FROM umas_itens WHERE id_programacao = {$gId}";
					$rs  = dbFastQuery($sql);
					atualizarAtivacaoUMA(array_column($rs, 'id_umas'));
					dbQuery("DELETE FROM inventarios_finalizados WHERE id_programacao=".$gId);


					$mtz=array();
					$mtz["id_programacao"]=$gId;
					$mtz["id_tipos_atividades"]=4;
					$mtz["id_pessoas"]=$usrId;
					$mtz["id_itens_skus"]=0;
					$mtz["cancelada"]=0;
					$mtz["data"]=date('Y-m-d H:i:s');
					$mtz["quantidade"]=0;
					$mtz["descricao"]="Desfez inventário";
					dbInsert("programacao_atividades", $mtz);
				break;



				case 7:
					// Apanha
					$apanhas=$this->obtemApanhas($gId);
					for ($i=0; $i<=count($apanhas); $i++)
					{
						$os=$apanhas[0];
						$sqlu="UPDATE umas_itens SET id_pessoas_cancelou=".$usrId.",cancelada=1 WHERE (separada=1 OR (reservada=1 AND tipo='-') OR (separada=0 AND reservada=0 AND quantidade>0)) AND id_programacao=".$gId;
						dbQuery($sqlu);
						$sql = "SELECT id_umas FROM umas_itens WHERE id_programacao = {$gId}";
						$rs  = dbQuery($sql);
						atualizarAtivacaoUMA(array_column($rs, 'id_umas'));

						// Para pegar o parcial.
						$sql="SELECT
							  	UI.*
							  FROM umas_itens UI
							  WHERE UI.cancelada=0 AND (UI.id_umas_origem <> 0 AND UI.id_umas_origem IS NOT NULL) AND UI.id_programacao=".$gId;
						$parciais=dbQuery($sql);

						$sql = "SELECT DISTINCT U.id FROM umas_itens UI LEFT JOIN umas U ON UI.id_umas=U.id WHERE UI.id_programacao=".$gId;

						$rst = dbQuery($sql);
						foreach ($rst as $row)
						{
							$sqlu="UPDATE umas SET data_conferencia_saida='0000-00-00 00:00:00',
								data_saida='0000-00-00 00:00:00',
								conferida_saida=0 WHERE id=".$row['id'];
							dbQuery($sqlu);
						}
					}
				break;

				case 26:
					// Verifica se as UMAs desta programação já tiveram movimentações, caso já tenham, o processo é finalizada;
					$podeDesfazer = true;
					$umasMovimentadas = [];
					$sql="SELECT id_umas,max(data) data FROM umas_itens WHERE cancelada=0 AND id_programacao=".$gId." GROUP BY id_umas";
					$rs=dbQuery($sql);
					foreach ($rs as $row) {
						$sql="SELECT U.codigo_barras
						FROM umas_itens UI
						INNER JOIN umas U ON U.id = UI.id_umas
						WHERE UI.id_umas=".$row['id_umas']." AND UI.cancelada=0 AND UI.id_programacao<>$gId AND UI.data >'".$row['data']."'";
						$rsu=dbQuery($sql)[0];
						if($rsu){
							$podeDesfazer=false;
							$umasMovimentadas[]=$rsu['codigo_barras'];
						}
					}

					if (!$podeDesfazer) {
						return 'Esta programação não pode ser desfeita pois os itens das seguintes UMAs foram movimentados após a transferência:<br>'.implode('<br>', array_map('linkParaUMA', $umasMovimentadas));
					} else{
						$sql="UPDATE umas_itens SET cancelada=1,data_cancelamento= NOW(), id_pessoas_cancelou = {$usrId} WHERE id_programacao={$gId} AND cancelada=0";
						dbQuery($sql);
						$sql = "SELECT id_umas FROM umas_itens WHERE id_programacao = {$gId}";
						$rs  = dbFastQuery($sql);
						atualizarAtivacaoUMA(array_column($rs, 'id_umas'));

						dbQuery("UPDATE programacao SET data_execucao_inicio='0000-00-00 00:00:00',data_execucao_final='0000-00-00 00:00:00', reservada=0,iniciada=0, executada=0, separada=0, conferida_saida=0, divergencia=0, id_pessoas_executou=0, id_tipo_reserva=0  WHERE id=".$gId);
					}
				break;
			}

			userLog('Atividades da OS desfeitas - id <a href="index.php?g=programacao&gPage='.DADOS.'&gId='.$gId.'">'.$gId.'</a>');
			$this->executaAtividade("Desfez atividades da OS", 0, 0);

			dbQuery("UPDATE programacao_itens SET conferida=0,gerou_entrada_dinamica=0,quantidade_aceita=0,quantidade_conferida=0 WHERE id_programacao=".$gId);
			dbQuery("UPDATE programacao_itens_produzir SET uma_destino='' WHERE id_programacao=".$gId);

			dbQuery("UPDATE programacao_atividades SET cancelada=1 WHERE id_programacao=".$gId);
			dbQuery("UPDATE programacao SET data_execucao_inicio='0000-00-00 00:00:00',data_execucao_final='0000-00-00 00:00:00', iniciada=0, executada=0, separada=0, conferida_saida=0, divergencia=0, id_pessoas_executou=0, divergencia=0 WHERE id=".$gId);
			$rs = dbQuery("SELECT divergencia FROM programacao WHERE id= ".$gId);
			$this->calculaUMAsNecessarias($gId);

			if ($this->os["id_tipos_programacao"] == 1) {
				$dadosGatilho = [
					"idProgramacao" => $gId,
					"idPessoasProprietario" => $this->os['id_pessoas_proprietario']
				];
				dispararGatilho("desfazerTudoEntrada", $dadosGatilho);
			}

			return $rs[0]['divergencia'];
		}
	}


	public function desfazerPorUMA($situacao, $uma)
	{
		global $usrId, $gParam;
		$usrId = intval($usrId);
		$whereDefault = array();
		if ($situacao != "saida") {
			$whereDefault[] = "(U.ativo = 1)";
			$whereDefault[] = "(UI.id_programacao_itens = " . intval($uma["id_programacao_itens"]) . ")";
			$whereDefault[] = "(UI.id_itens_skus = " . intval($uma["id_itens_skus"]) . ")";
			$whereDefault[] = "(UI.lote = '" . $uma["lote"] . "')";
			$whereDefault[] = "(UI.data_validade = '" . $uma["data_validade"] . "')";
			$whereDefault[] = "(UI.data_fabricacao = '" . $uma["data_fabricacao"] . "')";
		}

		$whereDefault[] = "(UI.cancelada = 0)";
		$whereDefault[] = "(UI.id_programacao = " . intval($uma["id_programacao"]) . ")";
		$whereDefault['id_umas'] = "(UI.id_umas = " . intval($uma["id"]) . ")"; //este indice eh utilizado para limpar UMA de origem de separacao parcial

		// Caso já tenha qualquer espelho não emitido, então cancelar com o desfazer.
		$this->cancelarEspelhoNFE($uma["id_programacao"]);
		switch ($situacao) {
			case "reservada":
				// UMAS do tipo separacao NAO entram aqui
				// Recuperar movimentos..
				$where = array();
				$where = $whereDefault;
				$where[] = "(UI.reservada=1 OR (reservada = 0 AND separada=0 AND tipo = '-'))";
				$where = implode(" AND ", $where);

				$sql = "
					SELECT
						UI.id_programacao_itens, UI.quantidade, PI.quantidade_conferida
					FROM umas_itens UI
					LEFT JOIN umas U ON U.id = UI.id_umas
					LEFT JOIN programacao_itens PI ON PI.id = UI.id_programacao_itens
					WHERE {$where}
					GROUP BY UI.id_programacao_itens";
				$programacao_itens=dbQuery($sql);

				if(intval($gParam['PERFIL_FABRICANTE']['ativo'])==1){
					/* Alteração realizada para Bomix.
					Motivo: Se a UMA for paletizada ou tiver saída parcial, o cancelamento do movimento altera o status, produto ou posição da UMA.
					Neste caso, a UMA deve continuar exatamente como está, apenas será desvinculada da programação e seu saldo retornará ao estoque.
					*/
					$where=$whereDefault;
					$where[] = "(UI.reservada=1)";
					$where=implode(" AND ", $where);
					$saldos = $this->obtemUmasComSaldo($where,true,0,"",0,"",0,"","","","UI.*");
					foreach ($saldos as $saldo) {
						$campos = $this->preparaCamposDoItem($saldo['id_umas'],$saldo);
						$campos['tipo'] = '-';
						$campos['quantidade'] = -($saldo["quantidade"]);
						$campos['reservada'] = 1;
						$campos['id_programacao'] = -($campos["id_programacao"]);
						$campos['id_programacao_itens']=-($campos["id_programacao_itens"]);
						dbInsert("umas_itens", $campos);

						$campos['tipo'] = '+';
						$campos['quantidade'] = $saldo["quantidade"];
						$campos['reservada'] = 0;
						dbInsert("umas_itens", $campos);
						atualizarAtivacaoUMA($campos['id_umas']);
					}
					foreach ($movimentos as $movimento) {
						$mtz=array();
						$mtz["id_programacao"]=-($movimento["id_programacao"]);
						$mtz["id_programacao_itens"]=-($movimento["id_programacao_itens"]);
						dbUpdate("umas_itens", $mtz, $movimento["id"]);
						atualizarAtivacaoUMA($movimento['id_umas']);

						if($movimento['id_umas_origem'] <> intval($uma["id"]) ){
							$sql="UPDATE umas_itens SET id_programacao=id_programacao*-1,id_programacao_itens=id_programacao_itens*-1
								WHERE id_umas=".$movimento['id_umas_origem']."
								AND cancelada=0
								AND reservada=1
								AND id_programacao= ".intval($uma["id_programacao"])."
								AND id_programacao_itens= ".intval($uma["id_programacao_itens"])."";
							dbQuery($sql);
							atualizarAtivacaoUMA($movimento['id_umas_origem']);
						}
					}
					/**
					 * Verificando a existência de uma UMA de origem, se houver significa que ela é uma UMA de expedição.
					 */
				} else {
					$sql="SELECT
							UI.*
						  FROM umas_itens UI
						  LEFT JOIN umas U ON U.id = UI.id_umas
						  WHERE {$where}
					";
					$movimentos = dbQuery($sql);
					foreach ($movimentos as $mov) {
						$mtz = array();
						$mtz["cancelada"]  = 1;
						$mtz["data_cancelamento"] = date('Y-m-d H:i:s');
						$mtz["id_pessoas_cancelou"] = $usrId;
						dbUpdate("umas_itens", $mtz, $mov["id"]);
					}
					atualizarAtivacaoUMA(array_column($movimentos, 'id_umas'));
				}
				$this->umaDesfazerMovimentos($uma, "Desfez reserva");
				$this->cancelarEventos("{id_programacao:".intval($uma["id_programacao"])."; id_itens_skus:".intval($uma["id_itens_skus"])."; id_umas:".intval($uma["id"])."; id_tipos_eventos:1,6;}");
				break;

			case "separada":
				// Recuperar movimentos..
				$where = array();
				$where = $whereDefault;
				if ($uma["separacao_parcial"]) {
					$where[] = "(UI.separada=1 OR UI.reservada=1)";
				} else {
					$where[] = "(UI.separada=1 OR (UI.tipo='-' AND UI.reservada=1))";
				}

				$where = implode(" AND ", $where);
				$sql = "SELECT UI.*
					  FROM umas_itens UI
					  LEFT JOIN umas U ON U.id = UI.id_umas
					  WHERE {$where}
				";
				$movimentos = dbQuery($sql);

				// Apaga as Ocorrências criadas na separação da UMA (Ao clicar em desfazer)
				dbQuery("DELETE FROM ocorrencias WHERE id_programacao = " . intval($uma["id_programacao"]) . " AND id_umas_destino = " . intval($uma["id"]));

				//Se a separação for parcial, deve remover a separação de todos os itens da UMA.

				/**
				 * Pegando a UMA que recebeu da uma de origem e defaz todos os movimentos para essa programação.
				 * OBS: O intuito é deixar todo o saldo que foi separada na uma de expedição, sem retornar o saldo para a UMA de origem,
				 * 		Por isso caso a UMA seja de expedição, o usuário não poderá desfazer item a item, e isso toda ela.
				 */
				$umasOrigens = array();

				foreach ($movimentos as $mov) {
					$mtz = array();
					$umasOrigens[] = $mov['id_umas_origem'];

					if (($mov['reservada'] == 1 && $mov['tipo'] == '-') || ($mov['separada'] == 1)) {
						$mtz["cancelada"] = 1;
						$mtz["data_cancelamento"] = date('Y-m-d H:i:s');
						$mtz["id_pessoas_cancelou"] = $usrId;
					} else {
						//entrada de saldo reservado na UMA de separacao
						if ($uma["separacao_parcial"]) {
							$sql = "SELECT id_tipos_programacao FROM programacao WHERE id = " . $mov['id_programacao'];
							if (dbQuery($sql)[0]['id_tipos_programacao'] == 22) { //22 = saída avaria
								$mtz["avariada"] = 1;
							}

							$mtz['id_tipos_operacao'] = 0;
							$mtz["reservada"] = 0;
							$mtz["separada"] = 0;
							$mtz["id_programacao"] = -abs($mov['id_programacao']);
							$mtz["id_programacao_itens"] = -abs($mov['id_programacao_itens']);
							$mtz["data"] = date("Y-m-d H:i:s");
						}
					}
					if ($mtz) dbUpdate("umas_itens", $mtz, $mov["id"]);

					if ($mov['separada'] == 1 && !$gParam['EXIGIR_CONFERENCIA_SAIDA']['ativo']) {
						$sql = "
							UPDATE programacao_itens
							SET quantidade_conferida = quantidade_conferida - " . $mov['quantidade']
							." WHERE id = " . (int) $uma['id_programacao_itens'];
						dbQuery($sql);
					}
				}
				atualizarAtivacaoUMA(array_column($movimentos, 'id_umas'));

				/**
				 * Cancelando movimentos de reserva na UMA de origem, o saldo deverá permanecer na UMA de destino
				 */
				$umasOrigens = array_unique($umasOrigens);
				foreach ($umasOrigens as $umaOrigem) {
					$where = array();
					$where = $whereDefault;
					$where['id_umas'] = " id_umas = '{$umaOrigem}'";
					$where[] = " reservada = 1";
					$where = implode(" AND ", $where);
					$sql = "SELECT UI.*
						  FROM umas_itens UI
						  LEFT JOIN umas U ON U.id = UI.id_umas
						  WHERE {$where}
					";
					$movimentos = dbQuery($sql);

					foreach ($movimentos as $mov) {
						if ($mov['reservada'] == 1) {
							$mtz = array();
							$mtz["cancelada"] = 1;
							$mtz["data_cancelamento"] = date('Y-m-d H:i:s');
							$mtz["id_pessoas_cancelou"] = $usrId;
					 		dbUpdate("umas_itens", $mtz, $mov["id"]);
						}
					}
					atualizarAtivacaoUMA(array_column($movimentos, 'id_umas'));
				}

				$this->umaDesfazerMovimentos($uma, "Desfez separação");

				// Cancelar eventos de separação, saída e conferência.
				$this->cancelarEventos("{id_tipos_eventos:3,4,1,6; id_programacao:".$uma["id_programacao"]."; id_umas:".$uma["id"]."; id_itens_skus:".$uma["id_itens_skus"].";}");

				// Inserir evento de separação.
				$this->inserirEventoNaFila("{id_tipos_eventos:1; id_programacao:".$uma["id_programacao"]."; id_umas:".$uma["id"].";}");
				break;

			case "conferida_saida":
				$where = array();
				$where[]="(id>0)";
				$where[]="(id_programacao=".intval($uma["id_programacao"]).")";
				$where[]="(data_validade='".$uma["data_validade"]."')";
				$where[]="(data_fabricacao='".$uma["data_fabricacao"]."')";
				$where[]="(saida=1)";
				$where[]="(id_umas=".intval($uma["id"]).")";
				$where[]="(id_itens_skus='".$uma["id_itens_skus"]."')";
				$where=implode(" AND ", $where);
				$sqlc = "SELECT SUM(quantidade) AS quantidade FROM umas_conferencias WHERE {$where}";
				$quantidadeConferida = dbQuery($sqlc)[0]['quantidade'];

				if ($quantidadeConferida) {
					$sql = "
						UPDATE programacao_itens
						SET quantidade_conferida = quantidade_conferida - " . $quantidadeConferida
						." WHERE id = " . (int) $uma["id_programacao_itens"];
					dbQuery($sql);

					$sqld="DELETE FROM umas_conferencias WHERE {$where}";
					dbQuery($sqld);
				}

				$sql = "SELECT id
					FROM umas_itens
					WHERE cancelada = 0
						AND separada = 1
						AND tipo = '-'
						AND id_programacao = ". (int) $uma["id_programacao"]
						." AND id_umas = ". (int) $uma["id"];
				$temMovimentoSaidaNestaUMA = dbQuery($sql);
				if (!$temMovimentoSaidaNestaUMA) {
					$mtz=array();
					$mtz["conferida_saida"]=0;
					$mtz["id_pessoas_conferiu_saida"]=0;
					$mtz["data_conferencia_saida"]='0000-00-00 00:00:00';
					dbUpdate("umas", $mtz, $uma["id"]);
				}
				// Desfazer o movimento de conferência de saída na UMA
				$this->umaDesfazerMovimentos($uma, "Desfez conferência");

				// Cancelar evento de saída.
				$this->cancelarEventos("{id_tipos_eventos:4,3; id_programacao:".$uma["id_programacao"]."; id_umas:".$uma["id"]."; id_itens_skus:".$uma["id_itens_skus"].";}");

				// Inserir evento de conferência.
				$this->inserirEventoNaFila("{id_tipos_eventos:4; id_programacao:".$uma["id_programacao"]."; id_umas:".$uma["id"].";}");
				break;

			case "saida":
				$where=$whereDefault;
				$where[]="(UI.separada=1 AND UI.tipo='-')";
				$where=implode(" AND ", $where);

				$sql="SELECT
						UI.*
					  FROM umas_itens UI
					  LEFT JOIN umas U ON U.id = UI.id_umas
					  WHERE {$where}
				";
				$movimentos=dbQuery($sql);
				if (count($movimentos)>0)
				{

					foreach ($movimentos as $mov)
					{
						$mtz=array();
						$mtz["cancelada"]=1;
						$mtz["data_cancelamento"]=date('Y-m-d H:i:s');
						$mtz["id_pessoas_cancelou"]=$usrId;
						dbUpdate("umas_itens", $mtz, $mov["id"]);
					}
					atualizarAtivacaoUMA(array_column($movimentos, 'id_umas'));

					$this->umaDesfazerMovimentos($uma, "Desfez saída");
					// Pegar em uma_movimentos a última posição.
					$sql="SELECT
							id_posicoes
						  FROM umas_movimentos
						  WHERE tipo='S'
						  AND descricao like '%Saída%'
						  AND id_umas=".$uma["id"]."
						";
					$ultimaPosicao=dbQuery($sql);
					if (count($ultimaPosicao)>0) {
						$ultimaPosicao=end($ultimaPosicao);
						$sqlu="UPDATE umas SET id_posicoes=".intval($ultimaPosicao["id_posicoes"])." WHERE id=".$uma["id"];
						dbQuery($sqlu);
					}

					$mtz=array();
					$mtz["data_saida"]='0000-00-00 00:00:00';
					$mtz["ativo"]=1;
					dbUpdate("umas", $mtz, $uma["id"]);

					// Cancelar evento de saída que provavelmente já está finalizado.
					$this->cancelarEventos("{id_tipos_eventos:3; id_programacao:".$uma["id_programacao"]."; id_umas:".$uma["id"]."; id_itens_skus:".$uma["id_itens_skus"].";}");

					// Inserir evento novo a finalizar
					$this->inserirEventoNaFila("{id_tipos_eventos:3; id_programacao:".$uma["id_programacao"]."; id_umas:".$uma["id"].";}");
				}
				break;
		}

		return $this->flegarProgramacaoPorUMA($uma);
	}


	public function flegarProgramacaoPorUMA($uma)
	{
		$where = array();
		$where[] = "(UI.id_programacao = ".(int) $uma['id_programacao'].")";
		$where[] = "(UI.id_umas = ".$uma['id'].")";
		$where = implode(" AND ", $where);
		$situacao = $this->obtemSituacaoUMA($where);

		if ($situacao == 'saida') {
			// como a movimentacao de saida foi desfeita na UMA, o status dela passa a ser separada ou conferida_saida
			return;
		} elseif (
			$situacao == 'separada'
			|| $situacao == 'conferida_saida'
		) {
			// Desfazer movimento de conferência/execucao na OS
			$sql = "SELECT id
				FROM umas_itens
				WHERE cancelada = 0
					AND separada = 1
					AND tipo = '-'
					AND id_programacao = ". (int) $uma["id_programacao"];
			$temMovimentoSaida = dbQuery($sql);
			$mtz = array();
			if (!$temMovimentoSaida) {
				$mtz["conferida_saida"]=0;
				$mtz["id_pessoas_conferiu_saida"]=0;
				$mtz["id_pessoas_executou"] = 0;
				$mtz["data_execucao_final"] = '0000-00-00 00:00:00';
				$mtz["data_conferencia_saida"]='0000-00-00 00:00:00';
			}
			$mtz["executada"] = 0;

			dbUpdate("programacao", $mtz, $uma["id_programacao"]);

			$dadosGatilho = [
				"idProgramacao" => $uma['id_programacao'],
				"idPessoasProprietario" => $uma['id_pessoas_proprietario']
			];
			dispararGatilho("desfazerEtapaOsSaida", $dadosGatilho);

			$this->flegarApanhaPorOs($uma['id_programacao'], $mtz);

		} elseif ($situacao == 'reservada') {
			$mtz = array();
			$mtz["separada"] = 0;
			dbUpdate("programacao", $mtz, $uma["id_programacao"]);

			$dadosGatilho = [
				"idProgramacao" => $uma['id_programacao'],
				"idPessoasProprietario" => $uma['id_pessoas_proprietario']
			];
			dispararGatilho("desfazerEtapaOsSaida", $dadosGatilho);

			$this->flegarApanhaPorOs($uma['id_programacao'], $mtz);

		} else {
			$sql = "
				SELECT id, MAX(reservada) reservada, MAX(separada) separada
				FROM umas_itens
				WHERE cancelada = 0
					AND tipo = '+'
					AND (reservada = 1 OR separada = 1)
					AND id_programacao = " . (int) $uma["id_programacao"];//se esta na reserva ou na separacao, esta tipo '+', se foi separacao parcial esta tipo '-' na UMA  de origem
			$movimentacaoAtiva = dbQuery($sql);
			//situacao apta
			$umasReservadas = $this->umasReservadasOS($uma["id_programacao"]);
			$sql = "SELECT id_tipo_reserva FROM programacao WHERE id = " . $uma["id_programacao"];
			$idTipoReserva = dbFastQuery($sql)[0]['id_tipo_reserva'];
			if (!$umasReservadas && !$movimentacaoAtiva) {
				$mtz = array();
				$mtz["reservando"] = 0;
				$mtz["reservada"]  = 0;
				if ($idTipoReserva != 3) { //3 = reserva de UMA somente em data critica
					$mtz["id_tipo_reserva"] = 0;
				}
				dbUpdate("programacao", $mtz, $uma["id_programacao"]);

				$dadosGatilho = [
					"idProgramacao" => $uma['id_programacao'],
					"idPessoasProprietario" => $uma['id_pessoas_proprietario']
				];
				dispararGatilho("desfazerEtapaOsSaida", $dadosGatilho);

				$this->flegarApanhaPorOs($uma['id_programacao'], $mtz);
				$sql = "UPDATE programacao_itens SET quantidade_conferida = 0 WHERE id_programacao = " . (int) $uma["id_programacao"];
				dbQuery($sql);
			} else {
				if ($idTipoReserva == 1) { // 1 = reserva manual
					$mtz = array();
					$mtz["reservando"] = 0;
					$mtz["reservada"]  = 0;
					dbUpdate("programacao", $mtz, $uma["id_programacao"]);

					$dadosGatilho = [
						"idProgramacao" => $uma['id_programacao'],
						"idPessoasProprietario" => $uma['id_pessoas_proprietario']
					];
					dispararGatilho("desfazerEtapaOsSaida", $dadosGatilho);

					$this->flegarApanhaPorOs($uma['id_programacao'], $mtz);

				}
			}

			$mtz = array();
			if (!$movimentacaoAtiva) {
				$mtz["data_execucao_inicio"] = "0000-00-00 00:00:00";
				$mtz["iniciada"]  = 0;
				$mtz["reservada"] = 0;//quando a OS tem apenas uma UMA de separacao parcial
				$mtz["separada"]  = 0;//quando a OS tem apenas uma UMA de separacao parcial
				$mtz["conferida_saida"] = 0;
				$mtz["executada"] = 0;

				$dadosGatilho = [
					"idProgramacao" => $uma['id_programacao'],
					"idPessoasProprietario" => $uma['id_pessoas_proprietario']
				];
				dispararGatilho("desfazerTudoOsSaida", $dadosGatilho);
			} else {
				if ($uma["separacao_parcial"]) {
					$mtz["separada"] = 0;
					$mtz["conferida_saida"] = 0;
				}
			}

			if ($mtz) {
				dbUpdate("programacao", $mtz, $uma["id_programacao"]);
				$this->flegarApanhaPorOs($uma['id_programacao'], $mtz);
			}
		}
	}


	public function flegarApanhaPorOs($idProgramacao, $flags)
	{
		if (!$flags) {
			return;
		}
		$sql = "SELECT id_programacao_apanha FROM programacao WHERE id = {$idProgramacao}";
		$idProgramacaoApanha = dbFastQuery($sql)[0]['id_programacao_apanha'];
		if (!$idProgramacaoApanha) {
			return;
		}
		$flagsBuscar = implode(',', array_keys($flags));
		$sql = "SELECT id, id_pessoas_proprietario, {$flagsBuscar} FROM programacao WHERE id_programacao_apanha = {$idProgramacaoApanha} AND ativo = 1";
		$rs  = dbFastQuery($sql);
		$quantidadeOs = count($rs);

		$mtz = array();
		$flags = explode(',', $flagsBuscar);
		foreach ($flags as $flag) {
			$somaFlags = array_sum(array_column($rs, $flag));
			$mtz[$flag] = 0;
			if ($somaFlags == $quantidadeOs) {
				$mtz[$flag] = 1;
			}
		}
		if ($mtz) {
			dbUpdate("programacao", $mtz, $idProgramacaoApanha);
			if (!$mtz['iniciada']) {
				$dadosGatilho = [
					"idProgramacao" => $uma['id_programacao'],
					"idPessoasProprietario" => $uma['id_pessoas_proprietario']
				];
				dispararGatilho("desfazerTudoOsApanha", $dadosGatilho);
			} else {
				$dadosGatilho = [
					"idProgramacao" => $uma['id_programacao'],
					"idPessoasProprietario" => $uma['id_pessoas_proprietario']
				];
				dispararGatilho("desfazerEtapaOsApanha", $dadosGatilho);
			}
		}
	}


	function umaDesfazerMovimentos($uma, $desc)
	{
		global $usrId;
		$usrId = intval($usrId);
		if(!is_array($uma))
			$uma['id'] = (int) $uma;
		if (intval($uma["id_programacao"])>0)
		{
			$mtz=array();
			$mtz["id_programacao"]=intval($uma["id_programacao"]);
			$mtz["id_pessoas"]=$usrId;
			$mtz["id_tipos_atividades"]=0;
			$mtz["id_itens_skus"]=intval($uma["id_itens_skus"]);
			$mtz["data"]=date('Y-m-d H:i:s');
			$mtz["quantidade"]=$uma["quantidade"];
			$mtz["descricao"]="{$desc} ".$uma["codigo_uma"];
			dbInsert("programacao_atividades", $mtz);
			$sql="SELECT * FROM programacao_itens WHERE id_programacao=".intval($uma["id_programacao"]);
			$itensProgramacao=dbQuery($sql);
		}


		// Salvar registro em umas_movimentos.
		$mtz=array();
		$mtz["id_pessoas"]=$usrId;
		$mtz["id_umas"]=$uma["id"];
		$mtz["id_umas_para"]=0;
		$mtz["id_pessoas_proprietario"]=$uma["id_pessoas_proprietario"];
		$mtz["tipo"]="C";
		$mtz["data"]=date('Y-m-d H:i:s');
		$mtz["descricao"] = $desc;
		if ($uma["id_programacao"]) {
			$mtz["descricao"] .= ('' . $uma["os"]);
		}
		dbInsert("umas_movimentos", $mtz);
		userLog($desc." ".$uma["codigo_uma"]);
	}

	function umasReservadasOS($id_programacao, $id_programacao_itens=0, $todas=false)
	{
		$where=array();
		if(!$todas)
			$where[]="(U.ativo=1)";
		$where[]="(UI.cancelada=0)";
		$where[]="(UI.tipo='+')";
		$where[]="(UI.reservada=1)";
		$where[]="(UI.id_tipos_operacao NOT IN(2, 5))";
		$where[]="(UI.id_programacao in ({$id_programacao}))";
		if ($id_programacao_itens) {
			$where[] = "(UI.id_programacao_itens IN ({$id_programacao_itens}))";
			$selectProgramacaoItens = $groupProgramacaoItens = " ,UI.id_programacao_itens ";
		}
		$where=implode(" AND ", $where);
		$sql = "SELECT
					U.id, U.codigo_barras, U.ordem, U.conferida_saida, ISK.codigo, UI.id_itens_skus, I.nome, UN.descricao unidade, ISK.quantidade 	quantidade_sku,
					R.descricao regiao, A.descricao area, P.codigo_barras posicao, SUM(UI.quantidade) quantidade, UI.reservada {$selectProgramacaoItens}
				FROM umas_itens UI
				LEFT JOIN umas U ON UI.id_umas=U.id
				LEFT JOIN posicoes P ON U.id_posicoes=P.id
				LEFT JOIN areas A ON P.id_areas=A.id
				LEFT JOIN regioes R ON A.id_regioes=R.id
				LEFT JOIN itens_skus ISK ON UI.id_itens_skus=ISK.id
				LEFT JOIN itens I ON ISK.id_itens=I.id
				LEFT JOIN unidades UN ON ISK.id_unidades=UN.id
				WHERE {$where}
				GROUP BY U.id,UI.id_itens_skus {$groupProgramacaoItens}
				ORDER BY U.ordem, P.rua;";
		return (dbQuery($sql));
	}

	public function umasSeparadasOS($id_programacao = 0, $having = '', $idProgramacaoItens = 0)
	{
		$where=array();
		if (!$having) {
			$having = " SUM(UI.quantidade)>0 ";
		}

		if ($having=="SUM(UI.quantidade)>0") {
			$where[]="(U.ativo=1)";
		}
		$where[]="(UI.bloqueada='0' AND UI.cancelada='0' AND UI.avariada='0' AND UI.separada='1')";
		// $where[]="(UI.id_tipos_operacao<>5)";
		$where[]="(UI.id_programacao={$id_programacao})";

		if ($idProgramacaoItens) {
			$where[] = "(UI.id_programacao_itens IN ({$idProgramacaoItens}))";
			$selectProgramacaoItens = $groupProgramacaoItens = " ,UI.id_programacao_itens ";
		}

		$where=implode(" AND ", $where);
		$sql="SELECT
				 U.codigo_barras, U.id, U.conferida_saida, SK.codigo, I.nome, UN.descricao, N.numero, P.codigo_barras codigo_posicoes, PC.nome nome_proprietario, PJ.fiscal,
				 SK.quantidade quantidade_sku, TU.descricao tipo_uma, SUM(UI.quantidade) quantidade, MAX(UI.id) maxid  {$selectProgramacaoItens}
				FROM umas_itens UI
				LEFT JOIN umas U ON UI.id_umas=U.id
				LEFT JOIN itens_skus SK ON SK.id = UI.id_itens_skus
				LEFT JOIN itens I ON I.id = SK.id_itens
				LEFT JOIN unidades UN ON UN.id = SK.id_unidades
				LEFT JOIN notas N ON N.id = U.id_notas
				LEFT JOIN posicoes P ON P.id = U.id_posicoes
				LEFT JOIN tipos_umas TU ON TU.id = U.id_tipos_umas
				LEFT JOIN pessoas PC ON PC.id = UI.id_pessoas_proprietario
				LEFT JOIN pessoas_juridicas PJ ON PJ.id_pessoas = UI.id_pessoas_proprietario
				WHERE {$where}
				GROUP BY U.id, SK.id {$groupProgramacaoItens}
				HAVING {$having}";
		return (dbQuery($sql));
	}


	function umasSaidaOS($id_programacao,$todas=false)
	{
		global $gParam;
		$where=array();
		$where[]="(UI.id_programacao={$id_programacao})";
		$where[]="(UI.cancelada=0)";
		$where[]="(UI.separada=1 AND UI.tipo='-')";
		$where[]="(U.ativo=0)";
		$where=implode(" AND ", $where);
		$sql="SELECT
					UI.id_umas
			  FROM umas_itens UI
			  LEFT JOIN umas U ON UI.id_umas=U.id
			  WHERE {$where}
			  GROUP BY U.id";
			if(!$todas)
			{
				$sql.="HAVING SUM(UI.quantidade)>0";
			}
		return (dbQuery($sql));
	}

	function ordemExecutada($idProgramacao)
	{
		$ordemExecutada=dbQuery("SELECT * FROM programacao WHERE executada = '1' AND (data_execucao_final <> '0000-00-00 00:00:00' AND data_execucao_final IS NOT NULL) AND id = '{$idProgramacao}'");

		if (count($ordemExecutada)>0)
		{
			return ($ordemExecutada);
		}

		return (false);
	}

	function ordemIniciada($idProgramacao)
	{
		$ordemIniciada=dbQuery("SELECT * FROM programacao WHERE iniciada = '1' AND (data_execucao_inicio <> '0000-00-00 00:00:00' AND data_execucao_inicio IS NOT NULL) AND id = '{$idProgramacao}'");
		if (count($ordemIniciada)>0)
		{
			return ($ordemIniciada);
		}
		return (false);
	}
	/** @note CriarNFe -> Cria nota após execução da OS */
	function criarNFE($gId, $somenteSaida=0,$idCfop=9, $InfCpl='')
	{
		// idCfop = 9 => CFOP = 5906

		global $usrId;
		global $gParam;
		$validacoes = array();
		$validacoes["erros"] = array();

		if ($idCfop != 9 || $_REQUEST['id_cfops']) {
			$andCfop = " AND id_cfops = {$idCfop} ";
		}
		// Verifica se a NFe desta programação já foi criada...
		$sql = "SELECT id, numero
				FROM notas
				WHERE tipo='S'
					AND cancelada=0
					AND id_programacao={$gId}
					{$andCfop}
				LIMIT 1";
		$notaExiste = dbQuery($sql)[0];
		if ($notaExiste['id']) {
			$validacoes["erros"][]='NFe já foi gerada anteriormente[' . linkParaNota($notaExiste['id'], $notaExiste['numero'], 'S') . ']. Caso queira gerar novamente, cancele a nota anterior antes.';
			return $validacoes;
		}
		$wherePadrao=array();
		/* Não pode filtrar apenas ativos no caso de já ter saido UMA fica inativa */
		$wherePadrao[]="(UI.cancelada='0')";
		$wherePadrao[]="(UI.id_programacao='{$gId}')";

		// Verifica primeiro se existe carga RESERVADA...
		$where=$wherePadrao;
		$where[]="(UI.tipo='+')";
		$where=implode(" AND ", $where);
		$sql = "SELECT 
					GROUP_CONCAT(DISTINCT nfe.chave) refNfe,
					U.id, U.ordem, U.codigo_barras,
					ISK.codigo, I.nome, UN.descricao unidade, ISK.quantidade quantidade_sku,
					R.descricao regiao, A.descricao area, P.codigo_barras posicao,
					SUM(UI.separada) separada,
					SUM(UI.reservada) reservada,
					SUM(UI.quantidade) quantidade
				FROM umas_itens UI
				LEFT JOIN umas U ON UI.id_umas=U.id
				LEFT JOIN itens_skus ISK ON UI.id_itens_skus=ISK.id
				LEFT JOIN itens I ON ISK.id_itens=I.id
				LEFT JOIN unidades UN ON ISK.id_unidades=UN.id
				LEFT JOIN posicoes P ON U.id_posicoes=P.id
				LEFT JOIN areas A ON P.id_areas=A.id
				LEFT JOIN regioes R ON A.id_regioes=R.id
				LEFT JOIN notas_itens NIE ON NIE.id = UI.id_notas_itens
				LEFT JOIN notas NE ON NE.id = NIE.id_notas
				LEFT JOIN nfe ON (nfe.id = NE.id_nfe AND nfe.sistema IN ('WMS', 'WMS2'))
				WHERE {$where}
				GROUP BY U.ordem, U.codigo_barras,
					ISK.codigo, I.nome, UN.descricao, ISK.quantidade,
					R.descricao, A.descricao, P.codigo_barras
				ORDER BY R.descricao, ordem";
		$rsUMAs = dbQuery($sql);
		$temSeparada = false;
		$temReservada = false;
		$refNfe = array();
		foreach ($rsUMAs as $row)
		{
			if($gParam['INSERE_REFNFE_AUTO']['ativo']){
				$aux = explode(",",$row['refNfe']);
				foreach ($aux as $k) {
					$refNfe[preg_replace("/[^0-9]/","",$k)]=1;
				}
			}

			if ($row['separada']>0)
			{
				$temSeparada = true;
			}
			if ($row['reservada']>0)
			{
				$temReservada = true;
			}
		}

		if (!$temSeparada && !$temReservada) {
			$validacoes["erros"][]="Nenhuma UMA separada ou reservada";
			return $validacoes;
		}

			// Busca dados do proprietário...
		$sql = "SELECT O.os,
					   O.id id_programacao,
					   O.id_tipos_programacao,
					   O.id_programacao_origem,
					   P.*,
					   PJ.*,
					   O.id_armazens,
					   O.id_pessoas_proprietario
				FROM programacao O
				LEFT JOIN pessoas P ON O.id_pessoas_proprietario = P.id
				LEFT JOIN pessoas_juridicas PJ ON P.id=PJ.id_pessoas
				WHERE O.id={$gId}";
		$programacao = dbQuery($sql)[0];
		$osDeProducao = 0; // Não é OS de produção
		if ($programacao['id_tipos_programacao']==32)
		{
			$osDeProducao = 1; // É OS de produção
			if ($programacao['id_programacao_origem']>0) {
				$osDeProducao = 2; // É OS de produção complementar
			}
		}

		$cfop = dbQuery("
			SELECT id, codigo, informacao_complementar
			FROM cfops
			WHERE id=" . $idCfop)[0];
		if ($cfop['codigo']=="5925") {// NF de Saída de item produzido
			$osDeProducao = 1;
			$ehOsComplementar = (bool) $programacao['id_programacao_origem'];
		}

		$mtz = array();
		$mtz['tipo']='S';
		$mtz['numero']=$programacao['os'];
		$mtz['data_criou']=date('Y-m-d H:i:s');
		$mtz['data_movimento']=date('Y-m-d');
		$mtz['id_pessoas_proprietario']=$programacao['id_pessoas_proprietario'];
		$mtz['id_pessoas_cliente']=$programacao['id_pessoas_proprietario'];
		$mtz['id_armazens']=$programacao['id_armazens'];
		$mtz['id_pessoas_criou']=$usrId;
		$mtz['id_programacao']=$programacao['id_programacao'];
		$mtz['id_cfops'] = $idCfop;
		$mtz['refNfe']=count($refNfe) ? implode(",",array_keys($refNfe)) : "";

		if (
			$osDeProducao == 2
			&& $cfop['codigo'] == "5125"
		) {// NF de Saída de item produzido
			$sql = "
				SELECT SK.codigo, I.nome, P.id_programacao_origem, N.numero
				FROM umas_itens UI
				INNER JOIN itens_skus SK ON UI.id_itens_skus=SK.id
				INNER JOIN itens I ON SK.id_itens=I.id
				INNER JOIN programacao P ON UI.id_programacao=P.id
				LEFT JOIN notas N ON N.id_programacao=P.id_programacao_origem
				WHERE UI.separada=1
					AND UI.tipo='-'
					AND UI.cancelada=0
					AND I.produto_acabado=0
					AND UI.id_programacao={$gId}
				GROUP BY P.id, SK.id";
			$itensInsumos = dbQuery($sql);
			// Busca informação complementar pelo CFOP
			$InfCpl = $cfop['informacao_complementar'];
			$mtz['InfCpl'] = $InfCpl." ";

			/*
			Verifica se este item é uma OS de produção complementar
			Em caso positivo, deve acrescentar o InfCpl utilizado e o número da nota original
			(pela qual foi enviado o produto incompleto)
			*/
			if ($itensInsumos[0]['id_programacao_origem']>0) {
				// É uma OS complementar (somente do item que faltou)
				$mtz['InfCpl'].="Conforme a NF ".$itensInsumos[0]['numero'].". Insumo ".$itensInsumos[0]['codigo']." ".$itensInsumos[0]['nome'];
			}
		}

		$id = dbInsert('notas', $mtz, true);
		$validacoes["id"]=$id;
		$where=$wherePadrao;
		$where[]=($temSeparada)
			? "(UI.separada='1')"
			: "(UI.reservada='1')";

		$where[]=($somenteSaida)
			? "(UI.tipo='-')"
			: "(UI.tipo='+')";

		

		// De onde buscar os itens que compõem a NF???
		switch($osDeProducao)
		{
			case 0: // Não é OS de produção, e sim saída normal
				$where=implode(" AND ", $where);
				$sql = "SELECT
						NI.id_notas,
						NI.id_grupos_combustivel,
						NI.CODIF,
						NI.UFCons,
						UI.id_itens_skus,
						I.nome,
						I.produto_acabado,
						ISK.codigo,
						ISK.id_unidades,
						UI.lote,
						UI.data_validade,
						UI.data_fabricacao,
						MAX(NI.m2) m2_nota,
						MAX(NI.m3) m3_nota,
						MAX(NI.valor) valor_nota,
						MAX(UI.valor) valor,
						MAX(UI.m2) m2,
						MAX(UI.m3) m3,
						MAX(UI.peso_bruto) peso_bruto,
						MAX(UI.peso_liquido) peso_liquido,
						SUM(UI.quantidade) quantidade,
						ISK.peso_bruto peso_bruto_sku,
						ISK.peso_liquido peso_liquido_sku,
						I.id_grupos_combustivel id_grupos_combustivel_item
					FROM umas_itens UI
					LEFT JOIN umas U ON UI.id_umas=U.id
					LEFT JOIN itens_skus ISK ON UI.id_itens_skus=ISK.id
					LEFT JOIN itens I ON ISK.id_itens=I.id
					LEFT JOIN unidades UN ON ISK.id_unidades=UN.id
					LEFT JOIN posicoes P ON U.id_posicoes=P.id
					LEFT JOIN areas A ON P.id_areas=A.id
					LEFT JOIN regioes R ON A.id_regioes=R.id
					LEFT JOIN notas_itens NI ON NI.id = UI.id_notas_itens
					WHERE {$where}
					GROUP BY
						NI.id_notas,
						UI.id_itens_skus,
						UI.valor
					ORDER BY I.nome";
			break;

			case 1: // Produto da UMA é um produto acabado, e veio de OS de produção
				// Busca UMA da OS
				$sql = "
					SELECT GROUP_CONCAT(DISTINCT UI.id_umas) id_umas
					FROM umas_itens UI
					WHERE UI.cancelada = 0
						AND UI.quantidade>0
						AND UI.id_programacao={$gId}";
				$id_umas = dbQuery($sql)[0]['id_umas'];
				$sql = "SELECT GROUP_CONCAT(DISTINCT UI.id_programacao) id_programacao
						FROM umas_itens UI
						WHERE UI.cancelada=0 AND UI.quantidade>0 AND UI.id_programacao<>$gId AND UI.id_umas IN($id_umas)";
				$gIdProducao = dbQuery($sql)[0]['id_programacao'];
				if ($ehOsComplementar) {
					$where[1] = "(UI.id_programacao IN ($gId))";
				} else {
					$where[1] = "(UI.id_programacao IN ($gIdProducao))";
				}
				$where[] = "(UI.id_umas IN($id_umas)) ";

				$where=implode(" AND ", $where);
				$sql = "SELECT
						NI.id_notas,
						NI.id_grupos_combustivel,
						NI.CODIF,
						NI.UFCons,
						UI.id_itens_skus,
						I.nome,
						I.produto_acabado,
						ISK.codigo,
						ISK.id_unidades,
						UI.lote,
						UI.data_validade,
						UI.data_fabricacao,
						MAX(NI.m2) m2_nota,
						MAX(NI.m3) m3_nota,
						MAX(NI.valor) valor_nota,
						MAX(UI.valor) valor,
						MAX(UI.m2) m2,
						MAX(UI.m3) m3,
						MAX(UI.peso_bruto) peso_bruto,
						MAX(UI.peso_liquido) peso_liquido,
						SUM(UI.quantidade) quantidade,
						ISK.peso_bruto peso_bruto_sku,
						ISK.peso_liquido peso_liquido_sku,
						I.id_grupos_combustivel id_grupos_combustivel_item
					FROM umas_itens UI
					LEFT JOIN umas U ON UI.id_umas=U.id
					LEFT JOIN itens_skus ISK ON UI.id_itens_skus=ISK.id
					LEFT JOIN itens I ON ISK.id_itens=I.id
					LEFT JOIN unidades UN ON ISK.id_unidades=UN.id
					LEFT JOIN posicoes P ON U.id_posicoes=P.id
					LEFT JOIN areas A ON P.id_areas=A.id
					LEFT JOIN regioes R ON A.id_regioes=R.id
					LEFT JOIN notas_itens NI ON NI.id = UI.id_notas_itens
					WHERE {$where}
					GROUP BY
						NI.id_notas,
						UI.id_itens_skus,
						UI.valor
					ORDER BY I.nome";
			break;

			case 2: // Produto da UMA é um insumo de OS de produção complementar
				// Busca todos os itens de insumo e produtos acabados da UMA Produzida
				$sql = "SELECT PI.id_programacao,PI.id_itens_skus, SK.codigo, I.nome, P.id_programacao_origem, SK.id_unidades, PI.lote
						FROM programacao P 
						INNER JOIN programacao_itens_produzir PI ON P.id=PI.id_programacao
						INNER JOIN itens_skus SK ON PI.id_itens_skus=SK.id 
						INNER JOIN itens I ON SK.id_itens=I.id
						WHERE I.produto_acabado=1 AND P.id=$gId";
				$itemAcabado = dbQuery($sql)[0];
				$where=implode(" AND ", $where);
				$sql = "SELECT
							NI.id_notas,
							NI.id_grupos_combustivel,
							NI.CODIF,
							NI.UFCons,
							1 produto_acabado,
							'".$itemAcabado['id_itens_skus']."' id_itens_skus,
							'".$itemAcabado['nome']."' nome,
							'".$itemAcabado['codigo']."' codigo,
							'".$itemAcabado['id_unidades']."' id_unidades,
							'".$itemAcabado['lote']."' lote, 
							'0000-00-00' data_validade,
							'0000-00-00' data_fabricacao,
							MAX(NI.m2) m2_nota,
							MAX(NI.m3) m3_nota,
							MAX(NI.valor) valor_nota,
							MAX(UI.valor) valor,
							MAX(UI.m2) m2,
							MAX(UI.m3) m3,
							MAX(UI.peso_bruto) peso_bruto,
							MAX(UI.peso_liquido) peso_liquido,
							1  quantidade,
							ISK.peso_bruto peso_bruto_sku,
							ISK.peso_liquido peso_liquido_sku,
							0 id_grupos_combustivel_item
						FROM umas_itens UI
						LEFT JOIN umas U ON UI.id_umas=U.id
						LEFT JOIN itens_skus ISK ON UI.id_itens_skus=ISK.id
						LEFT JOIN itens I ON ISK.id_itens=I.id
						LEFT JOIN unidades UN ON ISK.id_unidades=UN.id
						LEFT JOIN posicoes P ON U.id_posicoes=P.id
						LEFT JOIN areas A ON P.id_areas=A.id
						LEFT JOIN regioes R ON A.id_regioes=R.id
						LEFT JOIN notas_itens NI ON NI.id = UI.id_notas_itens
						WHERE {$where}
						GROUP BY
							NI.id_notas,
							UI.id_itens_skus,
							UI.valor
						ORDER BY I.nome";
			break;
		}
		$rsSKUs = dbQuery($sql);
		foreach ($rsSKUs as $row)
		{
			$quantidade=abs($row["quantidade"]);
			// Caso não tenha valor por algum bug buscar da nota, colocar round para evitar problemas com dizimas. Oque ocorre muito na intermaritima.
			$valor=(abs($row["valor"])>0)
				? round(abs($row["valor"]), 11)
				: round(abs($row["valor_nota"]), 11);

			// Não tendo as informações buscar do cadastro do item.
			$pesoBruto=(abs($row["peso_bruto"])>0)
				? round(abs($row["peso_bruto"]), 10)
				: round(abs($row["peso_bruto_sku"]), 10);

			$pesoLiquido=(abs($row["peso_liquido"])>0)
				? round(abs($row["peso_liquido"]), 10)
				: round(abs($row["peso_liquido_sku"]), 10);

			$m2=(abs($row["m2"])>0)
					? round(abs($row["m2"]), 10)
					: round(abs($row["m2_nota"]));

			$m3=(abs($row["m3"])>0)
				? round(abs($row["m3"]), 10)
				: round(abs($row["m3_nota"]), 10);

			// Valor base de calculo.
			$valorBaseCalculo=round(abs($row["valor_base_calculo"]), 10);

			$idGrupoCombustivel=intval($row["id_grupos_combustivel"]) >0
				? $row["id_grupos_combustivel"]
				: $row["id_grupos_combustivel_item"];

			$mtz = array();
			$mtz['id_notas'] = intval($id);
			$mtz['id_notas_associada'] = intval($row['id_notas']);
			$mtz['lote'] = gToUpper(gCleanField($row['lote']));
			$mtz['id_itens_skus'] = intval($row['id_itens_skus']);
			$mtz['id_unidades'] = intval($row['id_unidades']);
			$mtz['id_cfops']= intval($idCfop);
			$mtz['quantidade'] = floatval($quantidade);
			$mtz['peso_bruto'] = floatval($pesoBruto);
			$mtz['peso_liquido'] = floatval($pesoLiquido);
			$mtz['m2'] = floatval($m2);
			$mtz['m3'] = floatval($m3);
			$mtz['valor'] = floatval($valor);
			$mtz['valor_base_calculo'] = floatval($valorBaseCalculo);
			$mtz['data_fabricacao'] = trim($row['data_fabricacao']);
			$mtz['data_vencimento'] = trim($row['data_validade']);
			$mtz['id_grupos_combustivel']=intval($idGrupoCombustivel);
			$mtz['UFCons']=trim($row["UFCons"]);
			$mtz['CODIF']=trim($row["CODIF"]);
			$idNotaItem=dbInsert('notas_itens', $mtz, true);

			//Replicando informações referentes a tributação na nota de saída
			$quantidadeMult= $quantidade > 0 ? $quantidade : 1;
			$quantidadeMult=1;
			$sql="SELECT notas_itens_icms.*,notas_itens.quantidade quantidadeDiv
					FROM notas
					INNER JOIN notas_itens ON notas_itens.id_notas = notas.id
					INNER JOIN notas_itens_icms ON notas_itens_icms.id_notas_itens = notas_itens.id
					WHERE notas.id = ".intval($row['id_notas'])." AND notas_itens.id_itens_skus=".intval($row['id_itens_skus'])."
					ORDER BY notas_itens_icms.id DESC";

			$rsImp=dbQuery($sql)[0];
			$quantidadeDiv=(int) $rsImp['quantidadeDiv'];
			unset($rsImp['id']);
			unset($rsImp['quantidadeDiv']);
			foreach($rsImp as $key => $value){
				if(is_numeric($key))
					unset($rsImp[$key]);
			}
			if($rsImp){//06396085585
				$mtz= array();
				$mtz=$rsImp;
				$mtz['id_notas_itens']=$idNotaItem;
				$mtz['vBC']=($mtz['vBC'] / $quantidadeDiv) * $quantidadeMult ;
				$mtz['vBCSTRet']=($mtz['vBCSTRet'] / $quantidadeDiv) * $quantidadeMult;
				$mtz['vICMSSTRet']=($mtz['vICMSSTRet']/$quantidadeDiv) * $quantidadeMult;
				$mtz['vBCST'] = ($mtz['vBCST']/$quantidadeDiv) * $quantidadeMult;
				$mtz['vBCFCP'] = ($mtz['vBCFCP']/$quantidadeDiv) * $quantidadeMult;
				$mtz['vBCFCPST'] = ($mtz['vBCFCPST']/$quantidadeDiv) * $quantidadeMult;
				dbInsert('notas_itens_icms', $mtz);
			}
			$sql="SELECT notas_itens_ipi.*
					FROM notas
					INNER JOIN notas_itens ON notas_itens.id_notas = notas.id
					INNER JOIN notas_itens_ipi ON notas_itens_ipi.id_notas_itens = notas_itens.id
					WHERE notas.id = ".intval($row['id_notas'])." AND notas_itens.id_itens_skus=".intval($row['id_itens_skus'])."
					ORDER BY notas_itens_ipi.id DESC";
			$rsImp=dbQuery($sql);
			if($rsImp){
				$ipi = $rsImp[0];
				$mtz= array();
				$mtz['id_notas_itens']=$idNotaItem;
				$mtz['id_imp_ipi_cst']=$ipi['id_imp_ipi_cst'];
				$mtz['vBC']=($ipi['vBC'] / $quantidadeDiv) * $quantidadeMult;
				$mtz['pIPI']=$ipi['pIPI'];
				$mtz['vIPI'] = $mtz['vBC'] * ($mtz['pIPI']/100);
				dbInsert('notas_itens_ipi', $mtz);
			}

			$sql="SELECT notas_itens_pis.*
					FROM notas
					INNER JOIN notas_itens ON notas_itens.id_notas = notas.id
					INNER JOIN notas_itens_pis ON notas_itens_pis.id_notas_itens = notas_itens.id
					WHERE notas.id = ".intval($row['id_notas'])." AND notas_itens.id_itens_skus=".intval($row['id_itens_skus'])."
					ORDER BY notas_itens_pis.id DESC";
			$rsImp=dbQuery($sql);
			if($rsImp){
				$pis = $rsImp[0];
				$mtz= array();
				$mtz['id_notas_itens']=$idNotaItem;
				$mtz['id_imp_pis_cst']=$pis['id_imp_pis_cst'];
				$mtz['vBC']=($pis['vBC'] / $quantidadeDiv) * $quantidadeMult;
				$mtz['pPIS']=$pis['pIPI'];
				$mtz['vPIS'] = $mtz['vBC'] * ($mtz['pPIS']/100);
				dbInsert('notas_itens_pis', $mtz);
			} else {
				$sql = "SELECT id FROM notas_itens_pis WHERE id_notas_itens=" . ((int) $idNotaItem) . " LIMIT 1";
                $idNotasItensPis = dbQuery($sql)[0]['id'];
                if (!$idNotasItensPis) {
                    $mtz = array();
                    $mtz["id_notas_itens"] = $idNotaItem;
                    $mtz["id_imp_pis_cst"] = 1;
                    $mtz["pPIS"] = 0;
                    dbInsert("notas_itens_pis", $mtz);
                }
			}

			$sql="SELECT notas_itens_cofins.*
					FROM notas
					INNER JOIN notas_itens ON notas_itens.id_notas = notas.id
					INNER JOIN notas_itens_cofins ON notas_itens_cofins.id_notas_itens = notas_itens.id
					WHERE notas.id = ".intval($row['id_notas'])." AND notas_itens.id_itens_skus=".intval($row['id_itens_skus'])."
					ORDER BY notas_itens_cofins.id DESC";
			$rsImp=dbQuery($sql);
			if($rsImp){
				$cofins = $rsImp[0];
				$mtz= array();
				$mtz['id_notas_itens']=$idNotaItem;
				$mtz['id_imp_cofins_cst']=$cofins['id_imp_pis_cst'];
				$mtz['vBC']=($cofins['vBC'] / $quantidadeDiv) * $quantidadeMult;
				$mtz['pCOFINS']=$cofins['pCOFINS'];
				$mtz['vCOFINS'] = $mtz['vBC'] * ($mtz['pCOFINS']/100);
				dbInsert('notas_itens_cofins', $mtz);
			} else {
				$sql = "SELECT id FROM notas_itens_cofins WHERE id_notas_itens=" . ((int) $idNotaItem) . " LIMIT 1";
                $idNotasItensCofins = dbQuery($sql)[0]['id'];
                if (!$idNotasItensCofins) {
                    $mtz = array();
                    $mtz["id_notas_itens"] = $idNotaItem;
                    $mtz["id_imp_cofins_cst"] = 1;
                    $mtz["pCOFINS"] = 0;
                    dbInsert("notas_itens_cofins", $mtz);
                }
			}



			// Atualizar umas_itens.
			$sqlu="UPDATE umas_itens SET id_notas_itens_saida={$idNotaItem}
				   WHERE (cancelada=0)
				   AND (id_programacao={$gId})
				   AND (id_itens_skus=".intval($row["id_itens_skus"]).")
				   AND (valor='".floatval($valor)."')";
			dbQuery($sqlu);
			$sql = "SELECT id_umas FROM umas_itens WHERE id_programacao = {$gId}";
			$rs  = dbFastQuery($sql);
			atualizarAtivacaoUMA(array_column($rs, 'id_umas'));
		}

		// Registrar histórico na programação
		$mtz=array();
		$mtz["id_programacao"]=$gId;
		$mtz["id_pessoas"]=$usrId;
		$mtz["id_tipos_atividades"]=20;
		$mtz["id_itens_skus"]=0;
		//$mtz["cance"]
		$mtz["data"]=date('Y-m-d H:i:s');
		$mtz["quantidade"]=0;
		$mtz["descricao"]="NFe criada com sucesso.";
		//$mtz[""]
		dbInsert("programacao_atividades", $mtz);

		return ($validacoes);
	}

	function cancelarEspelhoNFE($id_programacao)
	{
		global $usrId;
		$usrId = intval($usrId);
		$id_programacao=intval($id_programacao);
		$sql="SELECT id_pessoas_proprietario FROM programacao WHERE id=".$id_programacao;
		$programacao=(dbQuery($sql)[0]);
		$sql="SELECT
				N.id
			  FROM notas N
			  LEFT JOIN nfe NE ON NE.id = N.id_nfe
			  WHERE (N.id_programacao=".$id_programacao.")
			  AND (N.cancelada=0)
			  AND (N.nota_importada_cliente = 0)
			  AND (N.tipo='S')
			  AND (NE.situacao IS NULL OR NE.situacao <> 'Aprovada')
			  AND (N.id_pessoas_cliente=".intval($programacao["id_pessoas_proprietario"]).")
		";
		$existe_nota=dbQuery($sql);
		if (count($existe_nota)>0)
		{
			$existe_nota=$existe_nota[0];
			$mtz=array();
			$mtz["cancelada"]=1;
			$mtz["data_cancelamento"]=date('Y-m-d H:i:s');
			$mtz["id_pessoas_cancelou"] = $usrId;
			dbUpdate("notas", $mtz, $existe_nota["id"]);
			// Salvar registro em atividades

			$mtz=array();
			$mtz["id_programacao"]=$id_programacao;
			$mtz["id_pessoas"]=$usrId;
			$mtz["id_tipos_atividades"]=20;
			$mtz["id_itens_skus"]=0;
			$mtz["cancelada"]=0;
			$mtz["data"]=date('Y-m-d H:i:s');
			$mtz["quantidade"]=0;
			$mtz["descricao"]="NFe cancelada";
			dbInsert("programacao_atividades", $mtz);
		}
	}

	function obtemApanhas()
	{
		global $gId;
		$apanhaProgramacoes=array();
		if ($gId>0)
		{
			$sql = "SELECT * FROM programacao WHERE id_programacao_apanha=".$gId;
			$rs = dbQuery($sql);
			foreach ($rs as $row)
			{
				$apanhaProgramacoes[]=$row['id'];
			}
		}
		return ($apanhaProgramacoes);
	}

	function criarNFEApanha($filtro="")
	{
		global $usrId, $gId;
		$usrId = intval($usrId);
		$gId = intval($gId);
		$ossApanha=$this->obtemApanhas();
		foreach ($ossApanha as $os)
		{
			// Ao finalizar as OSs de apanha sair gerar os espelhos da NFe
			$sql="SELECT
					PR.*, PJ.fiscal
				  FROM  programacao PR
				  LEFT JOIN pessoas_juridicas PJ ON P.id_pessoas = PR.id_pessoas_proprietario
				  WHERE PR.id=".$os;
			$programacao=(dbQuery($sql)[0]);
			if (intval($programacao["fiscal"])==1)
			{

				$sql = "
					SELECT
						id
					FROM notas
					WHERE (tipo='S')
					AND (cancelada=0)
					AND (id_programacao=".$programacao["id"].")
				";
				$existe_nfe=dbQuery($sql);
				if (count($existe_nfe)==0)
				{
					$this->criarNFE($os);
				}
			}
		}
	}

	function verificarContagens($inventarioRealizado)
	{
		$where=array();
		$where[]="(INV.id_posicoes=".(int) $inventarioRealizado["id_posicoes"].")";
		$where[]="(INV.id_programacao=".(int) $inventarioRealizado["id_programacao"].")";
		$where[]="(INV.id_umas=".(int) $inventarioRealizado["id_umas"].")";
		$where[]="(ISK.id_itens_skus=".(int) $inventarioRealizado["id_itens_skus"].")";
		$where[]="(ISK.lote='".$inventarioRealizado["lote"]."')";
		$where[]="(ISK.data_validade='".($inventarioRealizado["data_validade"] ?: '0000-00-00')."')";
		$where[]="(ISK.data_fabricacao='".($inventarioRealizado["data_fabricacao"] ?: '0000-00-00')."')";
		$where[]="(ISK.quantidade='".$inventarioRealizado["quantidade"]."')";
		$where=implode(" AND ", $where);
		return ($where);
	}

	/** Verifica se duas contagens de inventário (arrays) são iguais
	*/
	public function saoIguais($contagem1, $contagem2, $debug)
	{
		$saoIguais = true;
		$contagem1 = excluirIndicesNumericos($contagem1);
		unset($contagem1['id']);
		unset($contagem1['contagem']);
		unset($contagem1['data_cadastro']);
		unset($contagem1['id_inventarios']);
		unset($contagem1['avariada']);
		unset($contagem1['bloqueada']);
		foreach ($contagem1 as $campo=>$valor) {
			if ($contagem2[$campo] <> $valor) {
				$saoIguais = false;
				if ($debug) {
					gDR("==== DIFERENTES $campo : ".$contagem2[$campo]." <> ".$valor);
				}
			}
		}
		//gDR($contagem1);gDR($contagem2);
		return $saoIguais;
	}


	function confereInventario($idInventarioSku, $where="")
	{
		global $gParam;

		if ($where=="")
		{
			$where="ISK.id={$idInventarioSku}";
			$sql="SELECT
				ISK.*, INV.id_programacao, INV.id_umas, INV.id_posicoes
			  FROM inventarios_skus ISK
			  LEFT JOIN inventarios INV ON INV.id = ISK.id_inventarios
			  WHERE ISK.id={$idInventarioSku}
			";
			$inventarioRealizado=(dbQuery($sql)[0]);
			$where=array();
			$where[]="(INV.id_posicoes=".(int) $inventarioRealizado["id_posicoes"].")";
			$where[]="(INV.id_programacao=".(int) $inventarioRealizado["id_programacao"].")";
			$where[]="(INV.id_umas=".(int) $inventarioRealizado["id_umas"].")";
			$where[]="(ISK.id_itens_skus=".(int) $inventarioRealizado["id_itens_skus"].")";
			$where[]="(ISK.lote='".$inventarioRealizado["lote"]."')";
			if ($gParam["USA_DATA_VALIDADE"]["ativo"]==1)
			{
				$where[]="(ISK.data_validade='".($inventarioRealizado["data_validade"] ?: '0000-00-00')."')";
			}
			if ($gParam["USA_DATA_FABRICACAO"]["ativo"]==1)
			{
				$where[]="(ISK.data_fabricacao='".($inventarioRealizado["data_fabricacao"] ?: '0000-00-00')."')";
			}
			$where[]="(ISK.quantidade='".$inventarioRealizado["quantidade"]."')";
			$where=implode(" AND ", $where);
		}

		$sql = "SELECT
				ISK.*, INV.id_programacao, id_umas
			  FROM inventarios_skus ISK
			  LEFT JOIN inventarios INV ON INV.id = ISK.id_inventarios
			  WHERE {$where}";

		$rs=dbQuery($sql);
		if (count($rs)>1)
		{
			foreach ($rs as $row)
			{
				$mtz=array();
				$mtz["conferida"]=1;
				dbUpdate("inventarios", $mtz, $row["id_inventarios"]);
			}
		} else
		{
			foreach ($rs as $row)
			{
				$mtz=array();
				$mtz["conferida"]=0;
				dbUpdate("inventarios", $mtz, $row["id_inventarios"]);
			}
		}
	}

	function finalizaOSeRestauraUMAs($gId)
	{
		global $usrId,$gParam;
		$usrId = intval($usrId);
		$sql="SELECT * FROM programacao WHERE id=".$gId;
		$programacao=(dbQuery($sql)[0]);
		$id_tipos_programacao = $programacao['id_tipos_programacao'];
		// Verifica se é uma OS de Saída, e toma algumas ações antes de finalizar...
		if ($id_tipos_programacao==2 || $id_tipos_programacao==3 || $id_tipos_programacao==4 || $id_tipos_programacao==22 || $id_tipos_programacao==24 || $id_tipos_programacao==25)
		{
			// Procura UMAs reservadas e separadas e as libera (volta ao status normal)
			// Se tiver alguma UMA ainda reservada ou separada, transforma ela em normal de novo
			$where=array();
			$where[]="(UI.id_programacao=".$gId.")";
			$where[]="(UI.cancelada=0)";
			$where[]="(U.ativo=1)";
			$where=implode(" AND ", $where);
			$sql="SELECT
					UI.id_umas, U.codigo_barras, U.id_posicoes
				  FROM umas_itens UI
				  LEFT JOIN umas U ON U.id = UI.id_umas
				  WHERE {$where}
				  GROUP BY UI.id_umas
				";
			$umas_os=dbQuery($sql);

			foreach ($umas_os as $uma) {
				// Se entrar aqui, a UMA devem ter apenas seu Status alterado
				$w = array();
				$w[] = "(UI.id_programacao=".$gId.")";
				$w[] = "(U.id=".$uma['id_umas'].")";
				$w[] = "(UI.reservada=1 OR UI.separada=1)";
				$saldos = $this->obtemUmasComSaldo(implode(' AND ',$w));
				if (count($saldos)>0) {
					foreach ($saldos as $saldo) {
						$w[] = "(UI.cancelada=0)";
						$sql = "
							SELECT UI.*
							  FROM umas_itens UI
							  LEFT JOIN umas U ON U.id = UI.id_umas
							  WHERE ".implode(' AND ', $w);
						$movimentos = dbQuery($sql);

						foreach ($movimentos as $mov) {
							$mtz = array();
							if (($mov['reservada'] == 1 && $mov['tipo'] == '-') || ($mov['separada'] == 1)) {
								//saida da reserva em diante
								$mtz["cancelada"] = 1;
								$mtz["data_cancelamento"] = date('Y-m-d H:i:s');
								$mtz["id_pessoas_cancelou"] = $usrId;
							} else {
								//entrada para reserva
								$mtz["reservada"] = 0;
								$mtz["separada"]  = 0;
								$mtz["id_programacao"] = -abs($mov['id_programacao']);
								$mtz["id_programacao_itens"] = -abs($mov['id_programacao_itens']);
							}
							dbUpdate("umas_itens", $mtz, $mov["id"]);

						}
						$idItensSkus = $saldo['id_itens_skus'];
					}
					$sql = "SELECT itens.id
							FROM itens
							INNER JOIN itens_skus ON itens_skus.id_itens = itens.id
							WHERE itens_skus.id = ".$idItensSkus;
					$r = dbQuery($sql)[0];

					if (intval($gParam["USAR_REGRA_POSICIONAMENTO"]["ativo"])==1) {
						$id_posicoes_posicionar = $this->indicarProximaPosicaoRegraPosicionamento($r['id']);
						if($id_posicoes_posicionar <> $uma['id_posicoes'] && (int) $id_posicoes_posicionar > 0){
							$sql="UPDATE umas SET id_posicoes_posicionar=$id_posicoes_posicionar WHERE id=".(int) $uma['id_umas'];
							dbQuery($sql);
						}
					}

					$sql = "
						SELECT id
						FROM umas_itens
						WHERE cancelada = 0
							AND separada = 1
							AND tipo = '-'
							AND id_programacao = ". (int) $gId
							." AND id_umas = ". (int) $uma["id_umas"];
					$temMovimentoSaidaNestaUMA = dbQuery($sql);
					if (!$temMovimentoSaidaNestaUMA) {
						$mtz = array();
						$mtz["conferida_saida"] = 0;
						$mtz["id_pessoas_conferiu_saida"] = 0;
						$mtz["data_conferencia_saida"] = "0000-00-00 00:00:00";
						dbUpdate("umas", $mtz, $uma["id_umas"]);
					}
				}

				$mtz=array();
				$mtz["id_programacao"]=$gId;
				$mtz["id_pessoas"]=$usrId;
				$mtz["id_tipos_atividades"]=0;
				$mtz["data"]=date('Y-m-d H:i:s');
				$mtz["descricao"]=" OS finalizada e ".$uma["codigo_barras"]." estornada.";
				dbInsert("programacao_atividades", $mtz);

				// Salvar registro em umas_movimentos.
				$mtz=array();
				$mtz["id_pessoas"]=$usrId;
				$mtz["id_umas"]=$uma["id_umas"];
				$mtz["id_umas_para"]=0;
				$mtz["id_pessoas_proprietario"]=$uma["id_pessoas_proprietario"];
				$mtz["tipo"]="C";
				$mtz["data"]=date('Y-m-d H:i:s');
				$mtz["descricao"]=$programacao["os"]." finalizada e ".$uma["codigo_barras"]." estornada.";
				dbInsert("umas_movimentos", $mtz);
			}
		}

		### TODO:: a UMA ainda pode estar ativa com outros itens, logo o procedimento abaixo se torna defazado
		if (in_array($id_tipos_programacao, array(2, 22)))
		{
			$sql="SELECT
					UI.id_umas, U.codigo_barras
			  FROM umas_itens UI
			  LEFT JOIN umas U ON U.id = UI.id_umas
			  WHERE
				(UI.id_programacao=".$gId.")
				AND (UI.cancelada=0)
				AND (U.ativo=0)
			  GROUP BY UI.id_umas
			";
			$umas_finalizadas=dbQuery($sql);
			if (count($umas_finalizadas)>0)
			{
				foreach ($umas_finalizadas as $uma_finalizada)
				{
					$mtz=array();
					$mtz["id_programacao"]=$gId;
					$mtz["id_pessoas"]=$usrId;
					$mtz["id_tipos_atividades"]=0;
					$mtz["data"]=date('Y-m-d H:i:s');
					$mtz["descricao"]=" OS finalizada e ".$uma_finalizada["codigo_barras"]." expedida.";
					dbInsert("programacao_atividades", $mtz);
				}
			}
		}

		finalizaOS($gId);

		if ($programacao['id_tipos_programacao'] == 1) {
			$dadosGatilho = [
				"idProgramacao" => $gId,
				"idPessoasProprietario" => $programacao['id_pessoas_proprietario']
			];
			dispararGatilho("finalizarEntrada", $dadosGatilho);
		}

		if ($programacao['id_tipos_programacao'] == 2) {
			$dadosGatilho = [
				"idProgramacao" => $gId,
				"idPessoasProprietario" => $programacao['id_pessoas_proprietario']
			];
			dispararGatilho("finalizarSaidaOsSaida", $dadosGatilho);
		}

		if ($programacao['id_tipos_programacao'] == 7) {
			$dadosGatilho = [
				"idProgramacao" => $gId,
				"idPessoasProprietario" => $programacao['id_pessoas_proprietario']
			];
			dispararGatilho("finalizarSaidaOsApanha", $dadosGatilho);
		}

		$mtz=array();
		$mtz["id_programacao"]=$gId;
		$mtz["id_pessoas"]=$usrId;
		$mtz["id_tipos_atividades"]=16;
		$mtz["data"]=date('Y-m-d H:i:s');
		$mtz["descricao"]=" OS finalizada";
		dbInsert("programacao_atividades", $mtz);
	}

	public function obtemPosicoesEquipamento($idEquipamento)
	{
		global $gParam;

		$sql="SELECT
					rua_de, rua_ate, predio_de, predio_ate, andar_de, andar_ate
			  FROM equipamentos_posicoes EP
			  LEFT JOIN equipamentos EM ON EM.id = EP.id_equipamentos
			  WHERE EP.id_equipamentos={$idEquipamento}
			";
		$rs=dbQuery($sql);

	}

	public function obtemPosicoesOperador($idOperador,$id_equipamento=0, $apenas_where=false)
	{

		global $gParam;
		$sql="SELECT
				rua_de, rua_ate, predio_de, predio_ate, andar_de, andar_ate
			  FROM equipamentos_pessoas EP
			  LEFT JOIN equipamentos E ON E.id = EP.id_equipamentos
			  LEFT JOIN equipamentos_posicoes EPO ON EPO.id_equipamentos = E.id
			  WHERE EP.id_pessoas={$idOperador}
			";
		if ($id_equipamento>0)
		{
			$sql.=" AND E.id={$id_equipamento} ";
		}
		$posicoes=dbQuery($sql);
		
		if (!$posicoes[0]) {
			return;
		}

		// Montar in
		$in=array();
		$in_where=array();
		foreach ($posicoes as $posicao)
		{

			$andar_de=str_pad(intval($posicao["andar_de"]), strlen($gParam['POSICOES_FORMATO_ANDAR']['valor']), '0', STR_PAD_LEFT);
			$andar_ate=str_pad(intval($posicao["andar_ate"]), strlen($gParam['POSICOES_FORMATO_ANDAR']['valor']), '0', STR_PAD_LEFT);

			$predio_de=str_pad(intval($posicao["predio_de"]), strlen($gParam['POSICOES_FORMATO_PREDIO']['valor']), '0', STR_PAD_LEFT);
			$predio_ate=str_pad(intval($posicao["predio_ate"]), strlen($gParam['POSICOES_FORMATO_PREDIO']['valor']), '0', STR_PAD_LEFT);

			$rua_de=str_pad(intval($posicao["rua_de"]), strlen($gParam['POSICOES_FORMATO_RUA']['valor']), '0', STR_PAD_LEFT);
			$rua_ate=str_pad(intval($posicao["rua_ate"]), strlen($gParam['POSICOES_FORMATO_RUA']['valor']), '0', STR_PAD_LEFT);
			$where=array();


			if (!empty($rua_de) && !empty($rua_ate))
			{
				$where[]="(P.rua>='{$rua_de}' AND P.rua<='{$rua_ate}')";
			}
			if (!empty($predio_de)>0 && !empty($predio_ate)>0)
			{
				$where[]="(P.predio>='{$predio_de}' AND P.predio<='{$predio_ate}')";
			}
			if (!empty($andar_de) && !empty($andar_ate))
			{
				$where[]="(P.andar>='{$andar_de}' AND P.andar<='{$andar_ate}')";
			}
			$where=implode(" AND ", $where);
			if ($apenas_where)
			{
				$in_where[]="({$where})";
			}
			$sql="SELECT id FROM posicoes P WHERE {$where} AND P.ativo=1";
			$pos=dbQuery($sql);

			foreach ($pos as $p)
			{
				if (!in_array($p["id"], $in))
				{
					$in[]=$p["id"];
				}
			}
		}

		if (count($in_where)>0)
		{
			$where=implode(" OR ", $in_where);
			return ($where);
		}
		if (count($in)>0)
		{
			$implode=implode(", ", $in);
			$implode="in ({$implode})";
			return ($implode);
		}
		return;

	}

	/**
	* Método para verificar se o evento já foi inserido
	*/
	public function existeEvento($jarr, $row)
	{
		$sql="SELECT *
			  FROM eventos
			  WHERE
			  id_programacao=".intval($jarr["id_programacao"])."
			  AND id_tipos_eventos=".intval($jarr["id_tipos_eventos"])."
			  AND id_itens_skus=".intval($row["id_itens_skus"])."
			  AND id_umas=".intval($row["id_umas"])."
			  AND cancelado=0
			";
		$existe=dbQuery($sql);
		if (count($existe)>0)
		{
			return (true);
		}
		return (false);
	}

	/**
	*	Método para filtrar eventos para atualização, cancelamento, ativação, etc.
	*/
	public function filtrarEventos($jarr)
	{
		$where=array();
		$where[]="(cancelado=0)";
		if ($jarr["id_tipos_eventos"])
		{
			$where[]="(E.id_tipos_eventos in (".$jarr["id_tipos_eventos"]."))";
		}

		if ($jarr["id_programacao"])
		{
			$where[]="(E.id_programacao=".intval($jarr["id_programacao"]).")";
		}

		if ($jarr["id_posicoes"])
		{
			$where[]="(E.id_posicoes=".intval($jarr["id_posicoes"]).")";
		}

		if ($jarr["id_umas"])
		{
			$where[]="(E.id_umas=".intval($jarr["id_umas"]).")";
		}

		if ($jarr["id_itens_skus"])
		{
			$where[]="(E.id_itens_skus=".intval($jarr["id_itens_skus"]).")";
		}

		if ($jarr["quantidade"])
		{
			$where[]="(E.quantidade=".floatval($jarr["quantidade"]).")";
		}

		// Filtrar diretamente pelo ID
		if ($jarr["id_evento"])
		{
			$where[]="(E.id=".intval($jarr["id_evento"]).")";
		}

		if (isset($jarr["ativo"]))
		{
			$where[]="(E.ativo=".$jarr["ativo"].")";
		}

		if (isset($jarr["finalizado"]))
		{
			$where[]="(E.finalizado=".intval($jarr["finalizado"]).")";
		}

		if (isset($jarr["cancelado"]))
		{
			$where[]="(E.cancelado=".intval($jarr["cancelado"]).")";
		}
		if(!count($where) > 1)
			$where=false;
		return ($where);
	}

	/**
	 * Método para atualizar eventos.
	 */
	public function atualizarEventos($json='{}')
	{
		global $usrId, $gParam;
		$usrId = intval($usrId);
		if (intval($gParam["USA_EVENTOS"]["ativo"])==1)
		{
			$jarr=cssDecode($json);
			$where=$this->filtrarEventos($jarr);
			if (count($where)>0)
			{
				$update=array();
				if (isset($jarr["u_ativo"]))
				{
					$update[]="E.ativo=".intval($jarr["u_ativo"]);
				}
				if (isset($jarr["u_id_pessoas_operador"]))
				{
					$update[]="E.id_pessoas_operador=".intval($jarr["u_id_pessoas_operador"]);
				}
				if (isset($jarr["u_id_equipamentos"]))
				{
					$update[]="E.id_equipamentos=".intval($jarr["u_id_equipamentos"]);
				}
				if (isset($jarr["u_id_posicoes_posicionar"]))
				{
					$update[]="E.id_posicoes_posicionar=".intval($jarr["u_id_posicoes_posicionar"]);
				}
				if (count($update)>0)
				{
					$update=implode(", ", $update);
					$where=implode(" AND ", $where);
					$sqlu="UPDATE
						eventos E
						SET {$update}
						WHERE {$where}
					   ";
					dbQuery($sqlu);
				}
			}
		}
	}

	/**
	* Cancelar evento.
	*/
	public function cancelarEventos($json='{}')
	{
		global $usrId, $gParam;
		$usrId = intval($usrId);
		if (intval($gParam["USA_EVENTOS"]["ativo"])==1)
		{
			// Montando where de cancelmento.
			$jarr=cssDecode($json);
			$where=$this->filtrarEventos($jarr);
			if (count($where)>0)
			{
				$where=implode(" AND ", $where);
				$sql="SELECT
					E.*, UI.id_pessoas_proprietario
				  FROM eventos E
				  INNER JOIN umas U ON U.id = E.id_umas
				  INNER JOIN umas_itens UI ON UI.id_umas = U.id
				  WHERE {$where}
				  GROUP BY E.id, U.id, UI.id_pessoas_proprietario, UI.lote, UI.data_validade, UI.data_fabricacao
				  ";
				$rs=(dbQuery($sql));
				// Registrar em umas_movimentos.
				$sqlu="UPDATE
						eventos E
					  SET
					  	E.data_cancelamento='".date('Y-m-d H:i:s')."',
					  	E.id_pessoas_cancelou={$usrId},
					  	E.cancelado=1,
					  	E.ativo=0
					  WHERE {$where}
					  ";
				dbQuery($sqlu);
				foreach ($rs as $evento)
				{
					// Inserir movimentos na UMA
					$mtz=array();
					$mtz["id_pessoas"]=$usrId;
					$mtz["id_umas"]=$evento["id_umas"];
					$mtz["id_posicoes"]=$evento["id_posicoes"];
					$mtz["id_pessoas_proprietario"]=$evento["id_pessoas_proprietario"];
					$mtz["tipo"]="E";
					$mtz["data"]=date('Y-m-d H:i:s');
					$mtz["descricao"]="Removido da lista de eventos";
					$mtz["lote"]=$evento["lote"];
					$mtz["data_fabricacao"]=$evento["data_fabricacao"];
					$mtz["data_validade"]=$evento["data_validade"];
					dbInsert("umas_movimentos", $mtz);
					return (true);
				}
			}
		}
	}

	public function finalizarEventos($json='{}')
	{
		global $usrId, $gParam;
		$usrId = intval($usrId);
		if (intval($gParam["USA_EVENTOS"]["ativo"])==1)
		{
			$jarr=cssDecode($json);
			$where=$this->filtrarEventos($jarr);
			if (count($where)>0)
			{
				$where=implode(" AND ", $where);
				$now=date('Y-m-d H:i:s');
				$sqlu="UPDATE eventos E
						SET
						ativo=0,
						finalizado=1,
						id_pessoas_finalizou={$usrId},
						data_finalizado='{$now}'
						WHERE {$where}";
				dbQuery($sqlu);
			}
		}
	}




	/**
	* Query padrão para os eventos de separação, saída e conferência.
	*/
	public function obtemQueryEvento($where)
	{
		$sql="SELECT
				U.conferida_saida,
				U.id id_umas,
				SUM(UI.quantidade) quantidade,
				U.id_posicoes,
				U.id_posicoes_posicionar,
				UI.id_itens_skus
			  FROM umas_itens UI
			  LEFT JOIN umas U ON U.id=UI.id_umas
			  LEFT JOIN posicoes P ON U.id_posicoes=P.id
			  LEFT JOIN posicoes PP ON PP.id = U.id_posicoes_posicionar
			  LEFT JOIN programacao O ON UI.id_programacao=O.id
			  LEFT JOIN tipos_programacao TP ON TP.id = O.id_tipos_programacao
			  LEFT JOIN itens_skus SK ON UI.id_itens_skus=SK.id
			  LEFT JOIN itens I ON I.id=SK.id_itens
			  LEFT JOIN unidades UN ON UN.id=SK.id_unidades
			  LEFT JOIN areas A ON P.id_areas=A.id
			  WHERE {$where}
			  GROUP BY
			  		P.rua,
			  		P.predio,
			  		P.andar,
					U.id,
					U.codigo_barras,
					P.codigo_barras,
					A.descricao
			 HAVING (quantidade > 0)";
		return ($sql);
	}

	public function inserirEventoManual($json='{}', $return_id=false)
	{
		global $usrId, $gParam;
		$usrId = intval($usrId);
		if (intval($gParam["USA_EVENTOS"]["ativo"])==1)
		{
			$jarr=cssDecode($json);
			$sql="SELECT *
				  FROM eventos
				  WHERE
				  id_programacao=".intval($jarr["id_programacao"])."
				  AND id_tipos_eventos=".intval($jarr["id_tipos_eventos"])."
				  AND quantidade=".$jarr["quantidade"]."
				  AND id_itens_skus='".intval($jarr["id_itens_skus"])."'
				  AND id_umas=".intval($jarr["id_umas"])."
				  AND cancelado=0
			";
			$existe=dbQuery($sql);
			if (count($existe)==0)
			{
				$mtz=array();
				$mtz["id_programacao"]=$jarr["id_programacao"];
				$mtz["id_umas"]=$jarr["id_umas"];
				$mtz["quantidade"]=$jarr["quantidade"];
				$mtz["id_itens_skus"]=$jarr["id_itens_skus"];
				$mtz["id_tipos_eventos"]=intval($jarr["id_tipos_eventos"]);
				$mtz["cancelado"]=0;
				$mtz["ordem"]=0;
				$mtz["id_pessoas_cadastrou"]=$usrId;
				$mtz["data_cadastro"]=date('Y-m-d H:i:s');
				if (isset($jarr["id_posicoes"]))
				{
					$mtz["id_posicoes"]=$jarr["id_posicoes"];
				}
				if (isset($jarr["id_eventos_prioridade"]))
				{
					$mtz["id_eventos_prioridade"]=intval($jarr["id_eventos_prioridade"]);
				}
				if (isset($jarr["ativo"]))
				{
					$mtz["ativo"]=intval($jarr["ativo"]);
				} else
				{
					$mtz["ativo"]=1;
				}
				$id=dbInsert("eventos", $mtz, true);
			} else
			{
				$id=$existe[0]["id"];
			}

			if ($return_id)
			{
				return ($id);
			}
		}
	}



	/**
	* Inserir evento de posicionamento.
	*/
	public function inserirEventoPosicionamento($jarr)
	{
		global $usrId;
		$usrId = intval($usrId);
		// UMAs sem posicionar.
		$where=array();
		$where[]="(U.ativo=1)";
		$where[]="(UI.cancelada=0)";
		if (isset($jarr["id_posicoes"]))
		{
			if ($jarr["id_posicoes"]<>"false")
			{
				$where[]="(U.id_posicoes=".intval($jarr["id_posicoes"]).")";
			}
		} else
		{
			$where[]="(U.id_posicoes=0)";
		}
		if (intval($jarr["id_programacao"])>0)
		{
			$where[]="(UI.id_programacao=".intval($jarr["id_programacao"]).")";
		}
		if (intval($jarr["id_umas"])>0)
		{
			$where[]="(UI.id_umas=".intval($jarr["id_umas"]).")";
		}
		if (intval($jarr["id_posicoes"])>0)
		{
			$where[]="(U.id_posicoes=".intval($jarr["id_posicoes"]).")";
		}
		if (intval($jarr["id_itens_skus"])>0)
		{
			$where[]="(UI.id_itens_skus=".intval($jarr["id_itens_skus"]).")";
		}
		if (intval($jarr["id_pessoas_proprietario"])>0)
		{
			$where[]="(UI.id_pessoas_proprietario=".intval($jarr["id_pessoas_proprietario"]).")";
		}
		if (intval($jarr["id_armazens"]))
		{
			$where[]="(UI.id_armazens=".intval($jarr["id_armazens"]).")";
		}
		$where=implode(" AND ", $where);
		$sql="SELECT IK.codigo,
				U.*,
				U.id id_umas,
				P.apelido,
				PO.codigo_barras posicao_posicionar,
				A.descricao area,
				N.numero numero_nf,
				SUM(UI.quantidade) quantidade
			  FROM umas U
			  LEFT JOIN umas_itens UI ON UI.id_umas = U.id
			  LEFT JOIN notas_itens NI ON NI.id = UI.id_notas_itens
			  LEFT JOIN notas N ON N.id = NI.id_notas
			  LEFT JOIN pessoas P ON P.id = UI.id_pessoas_proprietario
			  LEFT JOIN posicoes PO ON PO.id = U.id_posicoes_posicionar
			  LEFT JOIN posicoes PP ON PP.id = U.id_posicoes
			  LEFT JOIN areas A ON A.id = PP.id_areas
			  LEFT JOIN itens_skus IK ON IK.id = UI.id_itens_skus
			  LEFT JOIN itens I ON I.id = IK.id_itens
			  WHERE {$where}
			  GROUP BY U.id, N.numero
			  ORDER BY N.numero ASC
		";
		$rs=dbQuery($sql);
		if (count($rs)>0)
		{
			foreach ($rs as $row)
			{
				// Verificar se evento não já foi inserido
				$sql="SELECT * FROM eventos
					  WHERE
					  id_tipos_eventos=2
					  AND id_programacao=".$jarr["id_programacao"]."
					  AND id_posicoes=".$row["id_posicoes_posicionar"]."
					  AND id_umas=".$row["id_umas"]."
					  AND cancelado=0
					 ";

				$existe=dbQuery($sql);
				if (count($existe)==0)
				{
					// Inserir evento
					$mtz=array();
					$mtz["id_tipos_eventos"]=2;
					$mtz["id_pessoas_cadastrou"]=$usrId;
					$mtz["data_cadastro"]=date('Y-m-d H:i:s');
					$mtz["id_programacao"]=$jarr["id_programacao"];
					// Neste tipo de evento será o posicionar em
					$mtz["id_posicoes"]=$row["id_posicoes_posicionar"];
					$mtz["id_umas"]=$row["id_umas"];
					if (isset($jarr["ativo"]))
					{
						$mtz["ativo"]=intval($jarr["ativo"]);
					} else
					{
						$mtz["ativo"]=1;
					}
					$mtz["cancelado"]=0;
					$mtz["ordem"]=0;
					$mtz["quantidade"]=$row["quantidade"];
					$mtz["id_itens_skus"]=$row["id_itens_skus"];
					dbInsert("eventos", $mtz);
				}
			}
			return (true);
		}
		return (false);
	}

	/* Verificar se a reserva é uma separação parcial. */
	public function verificarParcial($id_umas=0, $id_programacao=0)
	{
		global $gId;

		$where=array();
		$where[]="(UI.id_umas=".intval($id_umas).")";
		$where[]="( (UI.reservada=0) OR (UI.reservada=1 AND UI.id_programacao<>".intval($id_programacao).") )";
		$where[]="(UI.avariada=0)";
		$where[]="(UI.bloqueada=0)";
		$where[]="(UI.cancelada=0)";
		$where=implode(" AND ", $where);
		$saldos=$this->obtemUmasComSaldo($where);
		if (count($saldos)>0)
		{
			$parcial=true;
		} else
		{
			$parcial=false;
		}
		return ($parcial);
	}

	/**
	* Inserir evento de posicionamento.
	*/
	public function inserirEventoPosicionamentoJung($jarr)
	{
		global $usrId;
		$usrId = intval($usrId);
		// UMAs sem posicionar.
		$jarr['id_tipos_eventos']=7;
		$where=array();
		$where[]="(U.ativo=1)";
		$where[]="(UI.cancelada=0)";
		if (isset($jarr["reserva"]) && intval($jarr["reserva"])==1)
		{
			$where[]="(UI.separada=0 AND UI.reservada=1)";
			$id_posicoes_espera=$this->buscarPosicaoDisponivelAreaEspera($jarr["id_posicoes"]);

			// Posição disponível para posicionamento.
			$sqlu="UPDATE umas SET id_posicoes_posicionar={$id_posicoes_espera} WHERE id=".intval($jarr["id_umas"]);
			dbQuery($sqlu);


			// Identificar trilateral adequada.
			$uma=$this->identificaTrilateralAdequada($jarr["id_umas"]);
			$jarr["id_equipamentos"]=$uma["id_equipamentos"];

			// Se for parcial então defini tipo de evento.
			$parcial=$this->verificarParcial($jarr["id_umas"], $uma['id_programacao']);
			if ($parcial)
			{
				$jarr["id_tipos_eventos"]=8;
			}
		}

		if (isset($jarr["id_posicoes"]))
		{
			if ($jarr["id_posicoes"]<>"false")
			{
				$where[]="(U.id_posicoes=".intval($jarr["id_posicoes"]).")";
			}
		// } else
		// {
		// 	$where[]="(U.id_posicoes=0)";
		}
		if (intval($jarr["id_programacao"])>0)
		{
			$where[]="(UI.id_programacao=".intval($jarr["id_programacao"]).")";
		}
		if (intval($jarr["id_umas"])>0)
		{
			$where[]="(UI.id_umas=".intval($jarr["id_umas"]).")";
		}
		// if (intval($jarr["id_posicoes"])>0)
		// {
		// 	$where[]="(U.id_posicoes=".intval($jarr["id_posicoes"]).")";
		// }
		if (intval($jarr["id_itens_skus"])>0)
		{
			$where[]="(UI.id_itens_skus=".intval($jarr["id_itens_skus"]).")";
		}
		if (intval($jarr["id_pessoas_proprietario"])>0)
		{
			$where[]="(UI.id_pessoas_proprietario=".intval($jarr["id_pessoas_proprietario"]).")";
		}
		if (intval($jarr["id_armazens"]))
		{
			$where[]="(UI.id_armazens=".intval($jarr["id_armazens"]).")";
		}
		$where=implode(" AND ", $where);
		$sql="SELECT IK.codigo,
				U.*,
				U.id id_umas,
				P.apelido,
				PO.codigo_barras posicao_posicionar,
				A.descricao area,
				N.numero numero_nf,
				UI.id_programacao,
				SUM(UI.quantidade) quantidade
			  FROM umas U
			  LEFT JOIN umas_itens UI ON UI.id_umas = U.id
			  LEFT JOIN notas_itens NI ON NI.id = UI.id_notas_itens
			  LEFT JOIN notas N ON N.id = NI.id_notas
			  LEFT JOIN pessoas P ON P.id = UI.id_pessoas_proprietario
			  LEFT JOIN posicoes PO ON PO.id = U.id_posicoes_posicionar
			  LEFT JOIN posicoes PP ON PP.id = U.id_posicoes
			  LEFT JOIN areas A ON A.id = PP.id_areas
			  LEFT JOIN itens_skus IK ON IK.id = UI.id_itens_skus
			  LEFT JOIN itens I ON I.id = IK.id_itens
			  WHERE {$where}
			  GROUP BY U.id, N.numero
			  ORDER BY N.numero ASC
		";
		$rs=dbQuery($sql);
		if (count($rs)>0)
		{
			$row = $rs[0];
			//foreach ($rs as $row)
			{
				// Verificar se evento não já foi inserido
				$sql="SELECT * FROM eventos
					  WHERE
					  (id_tipos_eventos in (7,8))
					  AND (id_programacao=".$row["id_programacao"].")
					  AND (id_posicoes=".$row["id_posicoes"].")
					  AND (id_posicoes_posicionar=".$row["id_posicoes_posicionar"].")
					  AND (id_umas=".$row["id_umas"].")
					  AND (id_equipamentos=".intval($jarr["id_equipamentos"]).")
					  AND (cancelado=0)
					 ";
				$existe=dbQuery($sql);
				if (count($existe)==0)
				{

					// Inserir evento
					$mtz=array();
					$mtz["id_tipos_eventos"]=$jarr['id_tipos_eventos'];
					$mtz["id_pessoas_cadastrou"]=$usrId;
					$mtz["data_cadastro"]=date('Y-m-d H:i:s');
					$mtz["id_programacao"]=$row["id_programacao"];

					// Neste tipo de evento será o posicionar em
					$mtz["id_posicoes"]=$row["id_posicoes"];
					$mtz["id_equipamentos"]=$jarr["id_equipamentos"];
					$mtz["id_posicoes_posicionar"]=$row["id_posicoes_posicionar"];
					$mtz["id_umas"]=$row["id_umas"];
					if (isset($jarr["ativo"]))
					{
						$mtz["ativo"]=intval($jarr["ativo"]);
					} else
					{
						$mtz["ativo"]=1;
					}
					$mtz["cancelado"]=0;
					$mtz["ordem"]=0;
					$mtz["quantidade"]=$row["quantidade"];
					$mtz["id_itens_skus"]=$row["id_itens_skus"];
					if (isset($jarr["reserva"]) && intval($jarr["reserva"])==1)
					{
						$mtz["expedicao"]=1;
					}
					dbInsert("eventos", $mtz);
				}
			}
			return (true);
		}
		return (false);
	}


	public function tabelaOrdemSeparacaoUmas($idProgramacao, $exibirOpcoes = true)
    {
        global $o;

        $sql = "
        	SELECT programacao.id, pessoas_juridicas.priorizar_palete_fechado, programacao.separada
        	FROM programacao
			LEFT JOIN pessoas_juridicas ON programacao.id_pessoas_proprietario = pessoas_juridicas.id_pessoas
        	WHERE programacao.id = '{$idProgramacao}' LIMIT 1";
        $programacao = dbFastQuery($sql)[0];
        if (!$programacao['id']) {
        	return false;
        }
        // Busca UMAs reservadas, pra depois verificar se já foram separadas
		$orderByPaleteFechado = "";
		if ($programacao["priorizar_palete_fechado"]) {
			$orderByPaleteFechado = " SUM(UI.quantidade) DESC, ";
		}
		$sql = "
			SELECT
				U.saida_avulsa, U.id, U.ativo, U.codigo_barras, U.ordem, U.conferida_saida, ISK.codigo, UI.id_itens_skus, I.nome, UN.descricao unidade, ISK.quantidade 	quantidade_sku,
				R.descricao regiao, A.descricao area, P.codigo_barras posicao, SUM(UI.quantidade) quantidade, UI.reservada, UI.lote, N.numero numero_nota, MAX(UI.id_umas_origem) AS id_umas_origem, IF(UI.id_tipos_operacao = 5, 1, 0) AS separacao_parcial
			FROM umas_itens UI
			LEFT JOIN umas U ON UI.id_umas=U.id
			LEFT JOIN posicoes P ON U.id_posicoes=P.id
			LEFT JOIN areas A ON P.id_areas=A.id
			LEFT JOIN regioes R ON A.id_regioes=R.id
			LEFT JOIN itens_skus ISK ON UI.id_itens_skus=ISK.id
			LEFT JOIN itens I ON ISK.id_itens=I.id
			LEFT JOIN unidades UN ON ISK.id_unidades=UN.id
			LEFT JOIN notas_itens NI ON NI.id = UI.id_notas_itens
			LEFT JOIN notas N ON N.id = NI.id_notas
			WHERE UI.cancelada=0 AND UI.tipo='+' AND UI.id_programacao = '" . $programacao['id'] . "' AND UI.reservada = '1'
			GROUP BY U.id,UI.id_itens_skus
			ORDER BY U.ordem, ISK.codigo, {$orderByPaleteFechado} P.rua, P.predio, P.andar, UI.data_fabricacao";
		$umasReservadas = dbFastQuery($sql);
		if (!$umasReservadas[0]['id']) {
			return false;
		}

        $table  = '';
        $table .= $o->msgSubTitle("Ordem de separação das UMAs");
        $table .= $o->tableBegin("big", true, true,	 true);

        $mtz = array();
        $mtz[] = "<-Opções";
        $mtz[] = "->Nº";
        $mtz[] = "<-UMA";
        $mtz[] = "<-NF";
        $mtz[] = "<-Região";
        $mtz[] = "<-Área";
        $mtz[] = "<-Posição";
        $mtz[] = "<-Lote";
        $mtz[] = "<-Código";
        $mtz[] = "<-Item";
        $mtz[] = "<-SKU";
        $mtz[] = "->Qtd RES";
        $mtz[] = "<>Conf.";
        $table .= $o->tableRow($mtz, "header");

        $sql = "
        	SELECT
				DISTINCT id_umas
			FROM
				umas_itens
			WHERE
				id_programacao = '" . $programacao['id'] . "'
				AND id_umas IN (" . implode(",", array_column($umasReservadas, 'id')) . ")
				AND cancelada = 0
				AND separada = 1
				AND tipo = '+'";
        $rs = array_flip(array_column(dbQuery($sql), 'id_umas'));
        $teveAlgumaSeparada = (bool) ($rs ?: $programacao["separada"]);
        $separada = false;
        $umaAtual = "";

        foreach ($umasReservadas as $chave => $uma) {
            $separada = false;
            $baixada  = false;
            $naPosicao = false;

            $btns = '';
            if ($uma['ativo']) {
                if (!is_numeric($rs[$uma['id']])) {
                	if ($exibirOpcoes && !$uma['separacao_parcial']) {
                		$link = $o->page
	                		. "&gId=" . $programacao['id']
	                		. "&gIdEnd=" . $uma['id']
	                		. "&ordem="  . $uma['ordem']
	                		. "&gIdAnt=" . ($umasReservadas[($chave-1)]['id'])
	                		. "&gIdPos=" . ($umasReservadas[($chave+1)]['id']);
	                    if ($uma['ordem'] > 1) {//1 = ordem minima para aparecer o botao

	                        $btns .= $o->button("{icon: arrow-up; size: small; style: primary; hint: Separar antes; href: " . $link . "&gPage=" . ITENS_RESERVAR_SUBIR . ";}");
	                    }
	                    if ($uma['ordem'] < count($umasReservadas)) {
	                        $btns .= $o->button("{icon: arrow-down; size: small; style: primary; hint: Separar depois; href: " . $link . "&gPage=" . ITENS_RESERVAR_DESCER . ";}");
	                    }
                	}
                } else {
                    $separada = true;
                    $btns = $o->label("Separada", "success");
                }
            } else {
                $baixada = true;
            }

            $mtz = array();
            if ($umaAtual <> $uma['codigo_barras']) {

                if (($teveAlgumaSeparada && !$separada)) {
                    if ($baixada) {
                        $mtz[] = "<-" . $o->label("Baixada", "danger");
                    } else {
                        $mtz[] = "<-" . $o->label("Reservada", "info");
                    }
                } else {
                    if (
                    	$uma['id_umas_origem']
                    	&& $uma['separacao_parcial']
                   	) {
                        $btns = $o->label("Separação parcial", "info");
                    }
                    $mtz[] = "<-" . $btns;
                }
                $mtz[] = "->" . $uma['ordem'];
                $mtz[] = "<-" . $this->linkParaUMA($uma['codigo_barras']);
                $mtz[] = "<-" . $uma['numero_nota'];
                $mtz[] = "<-" . $uma['regiao'];
                $mtz[] = "<-" . $uma['area'];
                $mtz[] = "<-" . $uma['posicao'];
            } else {
                $mtz[] = "~6->";
            }
            $mtz[] = "<-" . $uma["lote"];
            $mtz[] = "<-" . $uma['codigo'];
            $mtz[] = "<-" . $uma['nome'];
            $mtz[] = "<-" . $uma['unidade'] . ' com ' . (int) $uma['quantidade_sku'];
            $mtz[] = "->" . gFloat($uma['quantidade']);
            if ($uma['id_umas_origem']) {
                $mtz[] = "<>--";
            } else {
            	$mtz[] = "<>" . gCheck($uma['conferida_saida']);
            }
            if ($baixada) {
                $table .= $o->tableRow($mtz, "text-success");
            } else {
                $table .= $o->tableRow($mtz, "detail");
            }
            $umaAtual = $uma['codigo_barras'];
        }

        $table .= $o->tableEnd();

        return array(
        	'registros' => $umasReservadas,
        	'html' => $table
        );
    }


	public function inserirEventoSaida($jarr)
	{
		global $usrId, $gParam;
		$usrId = intval($usrId);
		$where=array();
		$where[]="(U.ativo=1)";
		$where[]="(UI.cancelada=0)";
		$where[]="(UI.separada=1 AND UI.reservada=0)";
		if (intval($jarr["id_programacao"])>0)
		{
			$where[]="(UI.id_programacao=".intval($jarr["id_programacao"]).")";
		}
		if (intval($jarr["id_umas"])>0)
		{
			$where[]="(UI.id_umas=".intval($jarr["id_umas"]).")";
		}
		if (intval($jarr["id_posicoes"])>0)
		{
			$where[]="(U.id_posicoes=".intval($jarr["id_posicoes"]).")";
		}
		if (intval($jarr["id_itens_skus"])>0)
		{
			$where[]="(UI.id_itens_skus=".intval($jarr["id_itens_skus"]).")";
		}
		if (intval($jarr["id_pessoas_proprietario"])>0)
		{
			$where[]="(UI.id_pessoas_proprietario=".intval($jarr["id_pessoas_proprietario"]).")";
		}
		if (intval($jarr["id_armazens"]))
		{
			$where[]="(UI.id_armazens=".intval($jarr["id_armazens"]).")";
		}
		$where=implode(" AND ", $where);
		$sql=$this->obtemQueryEvento($where);
		$rs=dbQuery($sql);


		$liberar=true;
		if (intval($gParam["EXIGIR_CONFERENCIA_SAIDA"]["ativo"])==1)
		{
			foreach ($rs as $row)
			{
				if (intval($row["conferida_saida"])==0)
				{
					$liberar=false;
				}
			}
		}

		if (!$liberar)
		{
			return (false);
		} else
		{
			if (count($rs)>0)
			{
				foreach ($rs as $row)
				{
					if (!$this->existeEvento($jarr, $row))
					{
						// Inserir evento
						$mtz=array();
						$mtz["id_tipos_eventos"]=3;
						$mtz["id_pessoas_cadastrou"]=$usrId;
						$mtz["data_cadastro"]=date('Y-m-d H:i:s');
						$mtz["id_programacao"]=$jarr["id_programacao"];
						// Neste tipo de evento será o posicionar em
						$mtz["id_posicoes"]=$row["id_posicoes"];
						$mtz["id_umas"]=$row["id_umas"];
						if (isset($jarr["ativo"]))
						{
							$mtz["ativo"]=intval($jarr["ativo"]);
						} else
						{
							$mtz["ativo"]=1;
						}
						$mtz["cancelado"]=0;
						$mtz["ordem"]=0;
						$mtz["quantidade"]=$row["quantidade"];
						$mtz["id_itens_skus"]=$row["id_itens_skus"];
						dbInsert("eventos", $mtz);
					}
				}
				return (true);
			}
			return (false);
		}
	}

	public function inserirEventoSeparacao($jarr)
	{
		global $usrId, $gParam;
		$usrId = intval($usrId);
		$where=array();
		$where[]="(U.ativo=1)";
		$where[]="(UI.cancelada=0)";
		$where[]="(UI.separada=0 AND UI.reservada=1)";
		if (intval($jarr["id_programacao"])>0)
		{
			$where[]="(UI.id_programacao=".intval($jarr["id_programacao"]).")";
		}
		if (intval($jarr["id_umas"])>0)
		{
			$where[]="(UI.id_umas=".intval($jarr["id_umas"]).")";
		}
		if (intval($jarr["id_posicoes"])>0)
		{
			$where[]="(U.id_posicoes=".intval($jarr["id_posicoes"]).")";
		}
		if (intval($jarr["id_itens_skus"])>0)
		{
			$where[]="(UI.id_itens_skus=".intval($jarr["id_itens_skus"]).")";
		}
		if (intval($jarr["id_pessoas_proprietario"])>0)
		{
			$where[]="(UI.id_pessoas_proprietario=".intval($jarr["id_pessoas_proprietario"]).")";
		}
		if (intval($jarr["id_armazens"]))
		{
			$where[]="(UI.id_armazens=".intval($jarr["id_armazens"]).")";
		}
		$where=implode(" AND ", $where);
		$sql=$this->obtemQueryEvento($where);
		$rs=dbQuery($sql);
		if (count($rs)>0)
		{
			foreach ($rs as $row)
			{
				// Inserir evento
				if (!$this->existeEvento($jarr, $row))
				{
					$mtz=array();
					$mtz["id_tipos_eventos"]=1;
					$mtz["id_pessoas_cadastrou"]=$usrId;
					$mtz["data_cadastro"]=date('Y-m-d H:i:s');
					$mtz["id_programacao"]=$jarr["id_programacao"];
					// Neste tipo de evento será o posicionar em
					$mtz["id_posicoes"]=$row["id_posicoes"];
					$mtz["id_posicoes_posicionar"]=$row["id_posicoes_posicionar"];
					$mtz["id_umas"]=$row["id_umas"];
					if (isset($jarr["ativo"]))
					{
						$mtz["ativo"]=$jarr["ativo"];
					} else
					{
						$mtz["ativo"]=1;
					}
					$mtz["cancelado"]=0;
					$mtz["ordem"]=0;
					$mtz["quantidade"]=$row["quantidade"];
					$mtz["id_itens_skus"]=$row["id_itens_skus"];
					dbInsert("eventos", $mtz);
				}
			}
			return (true);
		}
		return (false);
	}

	public function inserirEventoConferencia($jarr)
	{
		global $usrId;
		$usrId = intval($usrId);
		$where=array();
		$where[]="(U.ativo=1)";
		$where[]="(UI.cancelada=0)";
		$where[]="(UI.separada=1 AND UI.reservada=0)";
		if (intval($jarr["id_programacao"])>0)
		{
			$where[]="(UI.id_programacao=".intval($jarr["id_programacao"]).")";
		}
		if (intval($jarr["id_umas"])>0)
		{
			$where[]="(UI.id_umas=".intval($jarr["id_umas"]).")";
		}
		if (intval($jarr["id_posicoes"])>0)
		{
			$where[]="(U.id_posicoes=".intval($jarr["id_posicoes"]).")";
		}
		if (intval($jarr["id_itens_skus"])>0)
		{
			$where[]="(UI.id_itens_skus=".intval($jarr["id_itens_skus"]).")";
		}
		if (intval($jarr["id_pessoas_proprietario"])>0)
		{
			$where[]="(UI.id_pessoas_proprietario=".intval($jarr["id_pessoas_proprietario"]).")";
		}
		if (intval($jarr["id_armazens"]))
		{
			$where[]="(UI.id_armazens=".intval($jarr["id_armazens"]).")";
		}
		$where=implode(" AND ", $where);
		$sql=$this->obtemQueryEvento($where);

		$rs=dbQuery($sql);
		if (count($rs)>0)
		{
			foreach ($rs as $row)
			{
				// Inserir evento

				if (!$this->existeEvento($jarr, $row))
				{
					$mtz=array();
					$mtz["id_tipos_eventos"]=4;
					$mtz["id_pessoas_cadastrou"]=$usrId;
					$mtz["data_cadastro"]=date('Y-m-d H:i:s');
					$mtz["id_programacao"]=$jarr["id_programacao"];
					// Neste tipo de evento será o posicionar em
					$mtz["id_posicoes"]=$row["id_posicoes"];
					$mtz["id_umas"]=$row["id_umas"];
					if (isset($jarr["ativo"]))
					{
						$mtz["ativo"]=intval($jarr["ativo"]);
					} else
					{
						$mtz["ativo"]=1;
					}
					$mtz["cancelado"]=0;
					$mtz["ordem"]=0;
					$mtz["quantidade"]=$row["quantidade"];
					$mtz["id_itens_skus"]=$row["id_itens_skus"];
					dbInsert("eventos", $mtz);
				}
			}
			return (true);
		}
		return (false);
	}

	public function inserirEventoNaFila($json='{}')
	{
		global $gParam;
		if (intval($gParam["USA_EVENTOS"]["ativo"])==1)
		{
			$jarr=cssDecode($json);
			$eventoAtivo = dbQuery("SELECT * FROM tipos_eventos WHERE id=".intval($jarr["id_tipos_eventos"]))[0]['ativo'];
			if ($eventoAtivo)
			{
				switch ($jarr["id_tipos_eventos"])
				{
					case 1:
						$this->inserirEventoSeparacao($jarr);
					break;
					case 2:
						$this->inserirEventoPosicionamento($jarr);
					break;
					case 3:
						$this->inserirEventoSaida($jarr);
					break;
					case 4:
						$this->inserirEventoConferencia($jarr);
					break;
					case 7:
						// Caso o id_equipamentos não seja informado então usar método para buscar.
						$this->inserirEventoPosicionamentoJung($jarr);
					break;
				}
			}
		}
	}

	public function baixarProgramacao($id_programacao)
	{
		global $usrId;
		$usrId = intval($usrId);
		$jarr=cssDecode($json);
		$where=array();
		$where[]="(UI.cancelada=0)";
		$where[]="(UI.id_programacao={$id_programacao})";

		if (count($where)>0)
		{
			$where=implode(" AND ", $where);
			// Recuperar em quais itens da programação a UMA se encontra.
			$sql="SELECT
				PI.id id_programacao_itens,
				PR.id_tipos_programacao,
				PI.id_programacao,
				PR.id_armazens
			FROM umas_itens UI
			LEFT JOIN programacao_itens PI ON PI.id = UI.id_programacao_itens
			LEFT JOIN programacao PR ON PR.id = PI.id_programacao
			WHERE {$where}
			GROUP BY PI.id";
			$rs=dbQuery($sql);
			foreach ($rs as $row)
			{
				$where=array();
				$where[]="(UI.id_programacao={$id_programacao})";
				$where[]="(UI.id_programacao_itens='".$row["id_programacao_itens"]."')";
				$where[]="(UI.avariada=0 AND UI.bloqueada=0)";
				$where[]="(UI.reservada=1 OR UI.separada=1)";
				$where=implode(" AND ", $where);
				$umas=$this->obtemUmasComSaldo($where);
				if (count($umas)>0)
				{
					foreach ($umas as $uma)
					{
						$where.=" AND ( UI.id_umas=".intval($uma["id"]).")";
						$id_posicoes=0;
						$sql="SELECT id_posicoes FROM umas WHERE id=".$uma["id"];
						$rsp=dbQuery($sql);
						if (count($rsp)>0)
						{
							$id_posicoes=$rsp[0]["id_posicoes"];
						}

						if ($row["id_tipos_programacao"]==2)
						{
							$enviarPara = TOTVS_ARMAZEM_SAIDA;
							if(intval($row["id_programacao"])>0)
							{
								$sql="SELECT areas.codigo_faturamento
										FROM programacao
										INNER JOIN areas ON areas.id = programacao.id_areas
										WHERE programacao.id = ".$row["id_programacao"];
								$rs = dbQuery($sql);
								if(count($rs)>0)
								{
									$enviarPara = $rs[0]['codigo_faturamento'];
								}
							}
							$ok = TOTVS_transfereArmazem("{id_programacao: ".$row["id_programacao"]."; buscar_uma: ".$where."; origem: ".$id_posicoes."; destino: ".$enviarPara."}");
						} else
						{
							$ok = TOTVS_transfereArmazem("{id_programacao: ".$row["id_programacao"]."; buscar_uma: ".$where."; origem: ".$id_posicoes.";destino: ".TOTVS_ARMAZEM_PERSONALIZACAO."}");
						}
						$quantidade=$uma['quantidade'];
						$item['separada']= $uma["separada"];
						$item['reservada']=$uma["reservada"];
						$item['id_programacao']=$row["id_programacao"];
						$item['id_armazens']=$row["id_armazens"];
						$item['id_programacao_itens'] = $row["id_programacao_itens"];
						$item['id_itens_skus']=$uma['id_itens_skus'];
						$item['lote']=$uma['lote'];
						$item['data_fabricacao']=$uma['data_fabricacao'];
						$item['data_validade']=$uma['data_validade'];
						$item=$this->buscaItem($uma["id"], $item);
						if (count($item)>0)
						{
							$item=$item[0];
							$item["quantidade"]=-($uma["quantidade"]);
							$item["tipo"]="-";
							$item=$this->preparaCamposDoItem($uma["id"], $item);
							dbInsert("umas_itens", $item);
							atualizarAtivacaoUMA($item['id_umas']);

							// Salvando umas_movimentos.
							$mtz=array();
							$mtz["id_pessoas"]=$usrId;
							$mtz["id_umas"]=$uma["id"];
							$mtz["id_umas_para"]=0;
							$mtz["id_posicoes"]=$uma["id_posicoes"];
							$mtz["id_pessoas_proprietario"]=$uma["id_pessoas_proprietario"];
							$mtz["tipo"]="B";
							$mtz["data"]=date('Y-m-d H:i:s');
							$mtz["descricao"]="Baixa da programação id: ".$id_programacao;
							$mtz["lote"]=$item["lote"];
							$mtz["data_validade"]=$item["data_validade"];
							$mtz["data_fabricacao"]=$item["data_fabricacao"];
							dbInsert("umas_movimentos", $mtz);

							// Registrando atividades da OS
							$mtz["id_programacao"]=$id_programacao;
							$mtz["id_pessoas"]=$usrId;
							$mtz["id_itens_skus"]=$item["id_itens_skus"];
							$mtz["data"]=date('Y-m-d H:i:s');
							$mtz["quantidade"]=$quantidade;
							$mtz["descricao"]="Baixa da programação";
							$mtz["id_tipos_atividades"]=21;
						}
					}
				}
			}
		}

		// Registrar como separada e executada.
		$mtz=array();
		$mtz["id_pessoas_separou"]=1;
		$mtz["separada"]=1;
		$mtz["executada"]=1;
		$mtz["data_execucao_final"]=date('Y-m-d H:i:s');
		$mtz["id_pessoas_executou"]=$usrId;
		dbUpdate("programacao", $mtz, $id_programacao);

		// Finalizar possíveis eventos vinculados a programação.
		if((int) $jarr["id_programacao"] > 0 )
			$this->finalizarEventos('{id_programacao:'.$jarr["id_programacao"].';}');
	}


	public function obtemSituacaoUMA($where)
	{
		$sql = "
			SELECT
		        UI.id_programacao_itens,
		        UI.id_programacao,
		        UI.tipo,
		        UI.reservada,
		        UI.separada,
		        UI.avariada,
		        UI.bloqueada,
		        U.conferida_saida,
		        U.ativo,
		        U.id
		    FROM umas_itens UI
		    LEFT JOIN umas U ON U.id = UI.id_umas
		    LEFT JOIN programacao PR ON PR.id = UI.id_programacao
		    LEFT JOIN itens_skus SK ON SK.id = UI.id_itens_skus
		    WHERE {$where}
		    	AND UI.cancelada = 0
		    ORDER BY UI.id";
		$movimentacoes = dbQuery($sql);
		$situacao = "apta";
		foreach ($movimentacoes as $movimentacao) {
			if (
				!$movimentacao['reservada']
				&& !$movimentacao['bloqueada']
				&& !$movimentacao['avariada']
				&& !$movimentacao['separada']
				&& $movimentacao['tipo'] == '-'
			) {
				// movimentacao de saída do normal
				$situacao = '';
				continue;
			}
			if ($movimentacao['reservada'] && $movimentacao['tipo'] == '+') {
				$situacao = 'reservada';
			}

			if ($movimentacao['reservada'] && $movimentacao['tipo'] == '-') {
				//enviou reservada
				$situacao = '';
				continue;
			}

			if ($movimentacao['bloqueada'] && $movimentacao['tipo'] == '+') {
				$situacao = 'bloqueada';
			}

			if ($movimentacao['bloqueada'] && $movimentacao['tipo'] == '-') {
				//saiu do bloqueio
				$situacao = '';
			}

			if ($movimentacao['avariada'] &&  $movimentacao['tipo'] == '+') {
				$situacao = 'avariada';
			}

			if ($movimentacao['avariada'] && $movimentacao['tipo'] == '-') {
				//saiu da avaria
				$situacao = 'avariada';
			}

			if ($movimentacao['separada'] && $movimentacao['tipo'] == '+') {
				$situacao = 'separada';
			}

			if ($movimentacao['conferida_saida']) {
				$situacao = 'conferida_saida';
			}

			if ($movimentacao['separada'] && $movimentacao['tipo'] == '-') {
				$situacao = 'saida';
			}

			if (
				($situacao == 'saida' || $situacao == '')
				&& $movimentacao['ativo']
			) {
				$filtraSaldoDevolvido = array();
				$filtraSaldoDevolvido[] = "(ABS(id_programacao) = ".((int) $movimentacao['id_programacao']).")";
				$filtraSaldoDevolvido[] = "(ABS(id_programacao_itens) = ".((int) $movimentacao['id_programacao_itens']).")";
				$filtraSaldoDevolvido[] = "(id_umas = ".$movimentacao['id'].")";
				$filtraSaldoDevolvido[] = "(tipo = '+')";
				$filtraSaldoDevolvido[] = "(reservada = 0)";
				$filtraSaldoDevolvido[] = "(separada = 0)";
				$filtraSaldoDevolvido[] = "(bloqueada = 0)";
				$filtraSaldoDevolvido[] = "(avariada = 0)";
				$filtraSaldoDevolvido = implode(" AND ", $filtraSaldoDevolvido);
				$sql = "SELECT id FROM umas_itens WHERE {$filtraSaldoDevolvido} LIMIT 1";
				$saldoFoiDevolvido = dbQuery($sql)[0]['id'];

				if ($saldoFoiDevolvido) {
					$situacao = 'apta';
				}
			}
		}

		return $situacao;
	}


	public function verificarMovimentacaoFaturada($where)
	{

		if (is_array($where)) {
			$where = implode(' AND ', $where);
		}
		$whereFat[] = $where;
		$whereFat[] = "(UI.faturar = 1 AND UI.reservada = 0 AND UI.separada = 0)";
		$whereFat[] = "(UI.tipo = '+' AND UI.id_tipos_operacao = 6)";
		$whereFat=implode(" AND ", $whereFat);
		$sql = "SELECT 
					UI.* 
				FROM umas_itens UI
				LEFT JOIN umas U ON U.id = UI.id_umas
				WHERE {$whereFat}";
		$movsFat = dbQuery($sql);
		return $movsFat;
	}


	public function obtemSituacaoOS($programacao)
	{
		global $o;
		$situacao=$o->label("Aguardando", "info");
		if ($programacao["reservada"]==1)
		{
			$situacao=$o->label("Reservada", "info");
		}
		if ($programacao["separada"]==1)
		{
			$situacao=$o->label("Separada", "info");
		}
		if ($programacao["conferida_saida"])
		{
			$situacao=$o->label("Conferida", "info");
		}
		if ($programacao["executada"]==1)
		{
			$situacao=$o->label("Finalizada", "success");
		}
		return ($situacao);
	}
}


function padronizarCampoPosicao($valor, $formato)
{
	if ($formato == '') {
		return $valor;
	}

	if (is_numeric($formato)) {
		$caractereCompletivo = 0;
		return str_pad((int) $valor, strlen($formato), $caractereCompletivo, STR_PAD_LEFT);
	}

	return substr(str_pad(strtoupper(trim($valor)), strlen($formato), '_', STR_PAD_RIGHT), 0, strlen($formato));
}


function formataCamposPosicao($campos)
{
	global $gParam;

	if ($gParam['USA_POSICAO_COMO_UMA']['ativo']) {
		$caractereCompletivo = ' ';
		$campos['modulo']     = (int) $campos['modulo'];
		$campos['rua_de']     = (int) $campos['rua_de'];
		$campos['rua_ate']    = (int) $campos['rua_ate'];
		$campos['predio_de']  = (int) $campos['predio_de'];
		$campos['predio_ate'] = (int) $campos['predio_ate'];
		$campos['andar_de']   = (int) $campos['andar_de'];
		$campos['andar_ate']  = (int) $campos['andar_ate'];
		$campos['apartamento_de']  = (int) $campos['apartamento_de'];
		$campos['apartamento_ate'] = (int) $campos['apartamento_ate'];
		return($campos);
	}


	if ($campos['modulo'] <> "") {
		$campos['modulo'] = padronizarCampoPosicao($campos['modulo'], $gParam['POSICOES_FORMATO_MODULO']['valor']);
	}

	if ($campos['andar'] <> "") {
		$campos['andar'] = padronizarCampoPosicao($campos['andar'], $gParam['POSICOES_FORMATO_ANDAR']['valor']);
	}

	if ($campos['apartamento'] <> "") {
		$campos['apartamento'] = padronizarCampoPosicao($campos['apartamento'], $gParam['POSICOES_FORMATO_APTO']['valor']);
	}

	if ($campos['rua'] <> "") {
		$campos['rua'] = padronizarCampoPosicao($campos['rua'], $gParam['POSICOES_FORMATO_RUA']['valor']);
	}

	if ($campos['predio'] <> "") {
		$campos['predio'] = padronizarCampoPosicao($campos['predio'], $gParam['POSICOES_FORMATO_PREDIO']['valor']);
	}

	if ($campos['andar_de'] <> "") {
		$campos['andar_de'] = padronizarCampoPosicao($campos['andar_de'], $gParam['POSICOES_FORMATO_ANDAR']['valor']);
	}

	if ($campos['andar_ate'] <> "") {
		$campos['andar_ate'] = padronizarCampoPosicao($campos['andar_ate'], $gParam['POSICOES_FORMATO_ANDAR']['valor']);
	}

	if ($campos['apartamento_de'] <> "") {
		$campos['apartamento_de'] = padronizarCampoPosicao($campos['apartamento_de'], $gParam['POSICOES_FORMATO_APTO']['valor']);
	}

	if ($campos['apartamento_ate'] <> "") {
		$campos['apartamento_ate'] = padronizarCampoPosicao($campos['apartamento_ate'], $gParam['POSICOES_FORMATO_APTO']['valor']);
	}

	if ($campos['rua_de'] <> "") {
		$campos['rua_de'] = padronizarCampoPosicao($campos['rua_de'], $gParam['POSICOES_FORMATO_RUA']['valor']);
	}

	if ($campos['rua_ate'] <> "") {
		$campos['rua_ate'] = padronizarCampoPosicao($campos['rua_ate'], $gParam['POSICOES_FORMATO_RUA']['valor']);
	}

	if ($campos['predio_de'] <> "") {
		$campos['predio_de'] = padronizarCampoPosicao($campos['predio_de'], $gParam['POSICOES_FORMATO_PREDIO']['valor']);
	}

	if ($campos['predio_ate'] <> "") {
		$campos['predio_ate'] = padronizarCampoPosicao($campos['predio_ate'], $gParam['POSICOES_FORMATO_PREDIO']['valor']);
	}

	if ($campos['modulo_de'] <> "") {
		$campos['modulo_de'] = padronizarCampoPosicao($campos['modulo_de'], $gParam['POSICOES_FORMATO_MODULO']['valor']);
	}

	if ($campos['modulo_ate'] <> "") {
		$campos['modulo_ate'] = padronizarCampoPosicao($campos['modulo_ate'], $gParam['POSICOES_FORMATO_MODULO']['valor']);
	}

	return $campos;
}

function linkParaGoogle($query, $modoIa = false)
{
	if ($modoIa) {
		$modoIa = '&udm=50';
	} else {
		$modoIa = '';
	}
	return '<a href="https://www.google.com/search?q=' . urlencode(trim($query)) . $modoIa .  '" target="_blank">' . htmlspecialchars(trim($query)) . '</a>';
}


function linkParaOS($os="")
{
	global $o, $usrCliente;
	if ($_REQUEST['gPDF']=="1")
	{
		return($os);
	} else
	{
		$descOs=($usrCliente==1)
			? $os
			: '<a target="_new" href="index.php?g=programacao&pesquisa='.$os.'">'.$os.'</a>';
		return($descOs);
	}
}


function linkParaEir($eir="")
{
	global $usrCliente;
	if (
		$_REQUEST['gPDF']
		|| $_REQUEST['gXLS']
		|| $_REQUEST['gCSV']
		|| $_REQUEST['gDOC']
		|| $usrCliente
	) {
		return $eir;
	}

	return '<a target="_new" href="index.php?g=eir&gPage=40&gId='.$eir.'">'.$eir.'</a>';
}


function linkParaAtividadesOS($idProgramacao, $os)
{
	global $o, $usrCliente;
	if ($_REQUEST['gPDF'] || $_REQUEST['XLS'] || $_REQUEST['CSV'] || $_REQUEST['gDOC']) return $os;
	$os = $os ?: gFieldById('programacao', $idProgramacao, 'os');
	return ($usrCliente)
		? $idProgramacao
		: '<a target="_new" href="index.php?g=programacao&gPage=40&gId=' . $idProgramacao . '">' . $os . '</a>';
}

function converterPlacaMercosulParaAntiga($placa)
{
	$ehPlacaMercosulDoBrasil = (!is_numeric($placa[4]) && !is_numeric($placa[2]));
 	if ($ehPlacaMercosulDoBrasil) {
 		$placa[4] = array_flip(range('A', 'J'))[$placa[4]];
 	}
 	return $placa;
}


function tabelaSkuUmas($rs)
{
	global $o;
	$html = "";
	if (!$rs) {
		return $html;
	}

	$html.=$o->msgFilter("UMAs com saldo apto");
	$html.=$o->tableBegin("big", true, true);
	$mtz=array();
	$mtz[]="<-Pos.";
	$mtz[]="<-UMA";
	$mtz[]="<-Proprietário";
	$mtz[]="<-Código";
	$mtz[]="<-SKU";
	$mtz[]="->Quantidade";
	$mtz[]="<-Lote";
	$mtz[]="<>Fabricação";
	$mtz[]="<>Validade";
	$html.=$o->tableRow($mtz, "header");
	$tQuantidade=0;
	foreach ($rs as $uma) {
		if ($uma["quantidade"] <= 0) {
			continue;
		}

		if (!in_array($uma["codigo_barras"], $tUMA))		{
			$tUMA[$uma["codigo_barras"]] = 1;
		}
		$tQuantidade+=$uma["quantidade"];
		$descricaoProprietario=(!empty($uma["proprietario"])) ?  $uma["proprietario"] : "--";
		$mtz=array();
		$mtz[]="<-".$uma['local'].' '.linkParaPosicao($uma["posicao"]);
		$mtz[]="<-".linkParaUMA($uma["codigo_barras"]);
		$mtz[]="<-".$descricaoProprietario;
		$mtz[]="<-".linkParaCodigoItem($uma["codigo"]);
		$mtz[]="<-".$uma["item_descricao"];
		$mtz[]="->".gFloat($uma["quantidade"]);
		$mtz[]="<-".$uma["lote"];
		$mtz[]="<>".gDate($uma["data_fabricacao"]);
		$mtz[]="<>".gDate($uma["data_validade"]);
		$html.=$o->tableRow($mtz, "detail");
	}
	$mtz=array();
	$mtz[]="<---";
	$mtz[]="<-TOTAL DE UMAs: ".count($tUMA);
	$mtz[]="<---";
	$mtz[]="--";
	$mtz[]="<---";
	$mtz[]="->".gFloat($tQuantidade);
	$mtz[]="--";
	$mtz[]="--";
	$mtz[]="--";
	$html .= $o->tableRow($mtz, "footer");

	return $html;
}


function linkParaUMA($uma="", $separador)
{
	global $o, $usrCliente;

	if (
		$_REQUEST['gPDF']
		|| $_REQUEST['gXLS']
		|| $_REQUEST['gCSV']
		|| $_REQUEST['gDOC']
		|| $usrCliente
	) {
		if ($separador) {
			$uma = str_replace($separador, ',', $uma);
		}
		return $uma;
	}

	if ($separador) {
		$umas = explode($separador, $uma);
		return implode($separador,
			array_map(function($uma) {
				return '<a target="_new" href="index.php?g=informacoes&gPage='.PESQUISAR.'&forcar=uma&buscar=' . $uma . '">' . $uma . '</a>';
			}, $umas)
		);
	}

	return '<a target="_new" href="index.php?g=informacoes&gPage='.PESQUISAR.'&forcar=uma&buscar=' . $uma .'">' . $uma . '</a>';
}


function linkParaCodigoItem($codigo="")
{
	global $o, $usrCliente;
	if (
		$_REQUEST['gPDF']
		|| $_REQUEST['gXLS']
		|| $_REQUEST['gCSV']
		|| $_REQUEST['gDOC']
		|| $usrCliente
	) {
		return $codigo;
	}

	return '<a target="_new" href="index.php?g=informacoes&gPage=' . PESQUISAR . '&forcar=item&buscar=' . $codigo . '&exibir_umas=1">' . $codigo . '</a>';
}


function linkParaNFESaida($nfSaida, $page = 80)
{
	global $o, $usrCliente;
	if (
		$_REQUEST['gPDF']
		|| $_REQUEST['gXLS']
		|| $_REQUEST['gCSV']
		|| $_REQUEST['gDOC']
		|| $usrCliente
		|| !$nfSaida
	) {
		return $nfSaida;
	}

	$sql  = "SELECT id FROM notas WHERE numero = '{$nfSaida}' AND tipo='S' LIMIT 1";
	$idNota = dbFastQuery($sql)[0]['id'];
	if (!$idNota) {
		return $nfSaida;
	}

	$rota = 'index.php?g=nf_saida&gPage=' . $page . '&gId=' . $idNota;
	$link = '<a target="_new" href="' . $rota . '">' . $nfSaida . '</a>';
	return ($link);
}


function linkParaNFEntrada($nfEntrada, $page=80)
{
	global $o, $usrCliente;

	if (
		$_REQUEST['gPDF']
		|| $_REQUEST['gXLS']
		|| $_REQUEST['gCSV']
		|| $_REQUEST['gDOC']
		|| $usrCliente
		|| !$nfEntrada
	) {
		return $nfEntrada;
	}

	$sql  = "SELECT id FROM notas WHERE numero = '{$nfEntrada}' AND tipo = 'E' LIMIT 1";
	$idNota = dbQuery($sql)[0]['id'];

	if (!$idNota) {
		return $nfEntrada;
	}

	$rota = 'index.php?g=nf_entrada&gPage=' . $page . '&gId=' . $idNota;
	return '<a target="_new" href="' . $rota . '">' . $nfEntrada . '</a>';
}


function linkParaNota($id, $numero, $tipo) {
	$link = '';
	if (!$id) {
		return $link;
	}

	if (is_array($id)) {
		$id = implode(',', $id);
	}

	if ($id && $numero && $tipo) {
		$notas = array();
		$notas[0]['id'] = $id;
		$notas[0]['tipo'] = $tipo;
		$notas[0]['numero'] = $numero;
	} else {
		$sql  = "SELECT DISTINCT id, tipo, numero FROM notas WHERE id IN ({$id})";
		$notas = dbFastQuery($sql);
		if (!$notas) {
			return $link;
		}
	}

	foreach ($notas as $nota) {
		$conteudoExibir = ($nota['numero'] ?: 'id_' . $nota['id']);
		if (
			$_REQUEST['gPDF']
			|| $_REQUEST['gXLS']
			|| $_REQUEST['gCSV']
			|| $_REQUEST['gDOC']
			|| $usrCliente
		) {
			$link .= $conteudoExibir . ', ';
			continue;
		}

		if ($nota['tipo'] == 'E') {
			$rota = 'index.php?g=nf_entrada&gPage=1&gId=' . $nota['id']; // aba Dados
		} elseif ($nota['tipo'] == 'M') {
			$rota = 'index.php?g=nf_maquina&gPage=131&gId=' . $nota['id']; // aba DADOS_MAQUINA
		} else {
			$rota = 'index.php?g=nf_saida&gPage=1&gId=' . $nota['id']; // aba Dados
		}

		$link .= '<a target="_new" href="' . $rota . '">' . $conteudoExibir . '</a>' . ', ';
	}

	return substr($link, 0, -2);
}


function linkParaPosicao($posicao="")
{
	global $o, $usrCliente;

	if (
		$_REQUEST['gPDF']
		|| $_REQUEST['gXLS']
		|| $_REQUEST['gCSV']
		|| $_REQUEST['gDOC']
		|| $usrCliente
	) {
		return $posicao;
	}

	return '<a target="_new" href="index.php?g=informacoes&gPage='.PESQUISAR.'&forcar=posicao&buscar='.$posicao.'">'.$posicao.'</a>';
}


function linkParaDocumento($idDocumento)
{
	if (
		$_REQUEST['gPDF']
		|| $_REQUEST['gXLS']
		|| $_REQUEST['gCSV']
		|| $_REQUEST['gDOC']
		|| $usrCliente
		|| !$idDocumento
	) {
		return $idDocumento;
	}

	return '<a target="_new" href="index.php?g=documentos&gPage=4&gId=' . $idDocumento . '">' . $idDocumento . '</a>';
}


function linkParaCadastroEmpresa($id, $textoLink)
{
	global $usrCliente;
	if (
		$_REQUEST['gPDF']
		|| $_REQUEST['gXLS']
		|| $_REQUEST['gCSV']
		|| $_REQUEST['gDOC']
		|| $usrCliente
	) {
		return $textoLink;
	}

	return '<a target="_new" href="index.php?g=empresas&gPage=20&gId= ' . $id . '">' . $textoLink . "</a>";
}


function linkParaCadastroItem($id, $textoLink)
{
	global $usrCliente;
	if (
		$_REQUEST['gPDF']
		|| $_REQUEST['gXLS']
		|| $_REQUEST['gCSV']
		|| $_REQUEST['gDOC']
		|| $usrCliente
	) {
		return $textoLink;
	}

	return '<a target="_new" href="index.php?g=itens&gPage=10&gId=' . $id . '">' . $textoLink . "</a>";
}


function linkParaCadastroSku($idItensSkus, $textoLink, $idItens = 0)
{
	global $usrCliente;
	if (
		$_REQUEST['gPDF']
		|| $_REQUEST['gXLS']
		|| $_REQUEST['gCSV']
		|| $_REQUEST['gDOC']
		|| $usrCliente
	) {
		return $textoLink;
	}

	if (!$idItens) {
		$idItens = gFieldById('itens_skus', $idItensSkus, 'id_itens');
	}
	return '<a target="_new" href="index.php?g=itens&gPage=20&gId=' . $idItens . '&gIdd=' . $idItensSkus . '">' . $textoLink . "</a>";
}


function finalizaEventosOS($gId)
{
	global $usrId;
	$usrId = intval($usrId);
	$sql = "UPDATE eventos SET finalizado=1, id_pessoas_finalizou=".$usrId.", data_finalizado='".date("Y-m-d H:i:s")."' WHERE id_programacao=".$gId." AND finalizado=0 AND cancelado=0";
	dbQuery($sql);
}

function finalizaOS($gId)
{
	global $usrId;
	$usrId = intval($usrId);
	$idTiposProgramacao = gFieldById('programacao', $gId, "id_tipos_programacao");
	if (in_array($idTiposProgramacao, array(2, 3, 4, 22, 24, 25))) {
		$flagsOsSaida = ", conferida_saida = 1, reservada=1, separada=1 ";
	}

	$sql = "
		UPDATE programacao
		SET data_execucao_final='".date("Y-m-d H:i:s")."',
			executada=1,
			id_pessoas_executou=".$usrId."
			{$flagsOsSaida}
		WHERE id=" . $gId;
	dbQuery($sql);

	$sql = "
		INSERT INTO programacao_atividades
		(id_programacao, id_pessoas, id_tipos_atividades, data, descricao) VALUES
		('{$gId}', '{$usrId}', 16, NOW(), 'Finalizou programação')";
	dbQuery($sql);
	finalizaEventosOS($gId);
}


function imprimeEtiquetasUMA($impressora, $id_programacao, $id_umas=0, $qtd=0, $modelo="UMA")
{
	global $EMPRESA, $gParam, $o, $html, $backButton, $usrId;
	$usrId = intval($usrId);
	if ($modelo=="")
	{
		$modelo="UMA";
	}
	if ($_SERVER['HTTP_HOST']!="localhost" && $_SERVER['HTTP_HOST']!="127.0.0.1")
	{
		$persistencia = new UMA();
		$sql = "SELECT * FROM etiquetas WHERE nome='".$modelo."'";
		$rs = dbQuery($sql);
		$tamanho = $rs[0]['tamanho'];
		$itensPorEtiqueta = $rs[0]['itens_por_etiqueta'];

		$fonte = str_replace("@empresa@", strtoupper($EMPRESA),$rs[0]['codigo']);

		$sql = "SELECT * FROM programacao WHERE id=".$id_programacao;
		$rsp = dbQuery($sql)[0];
		$tipo = $rsp['id_tipos_programacao'];
		$os = $rsp['os'];
		$ativo='';
		switch ($tipo)
		{
			case '1': $id_tipos_umas=1;$ativo=' AND U.ativo=1';break;
			case '2': $id_tipos_umas=4;break;
			case '3': $id_tipos_umas=3;break;
			default: $id_tipos_umas=1;break;
		}

		$flt = "U.ativo=1";
		if ($id_umas<>"")
		{
			$flt.= " AND U.id='{$id_umas}' AND (UI.cancelada='0' OR UI.cancelada IS NULL)";
		}
		if ($id_programacao>0)
		{
			$flt.=$ativo." AND U.id_programacao='{$id_programacao}' AND (UI.cancelada='0' OR UI.cancelada IS NULL)";
		}
		if ($$_REQUEST["posicao_futura"])
		{
			$sql = "SELECT U.id, U.codigo_barras uma, U.codigo_externo,
								T.descricao tipo,
								TP.descricao tipo_programacao,
								P.codigo_barras posicao,
								U.complemento,
								MIN(UI.data) inicio, MAX(UI.data) final
						FROM umas U
						LEFT JOIN umas_itens UI ON U.id=UI.id_umas
						LEFT JOIN programacao O ON UI.id_programacao=O.id
						LEFT JOIN tipos_programacao TP ON O.id_tipos_programacao=TP.id
						LEFT JOIN posicoes P ON U.id_posicoes_posicionar=P.id
						LEFT JOIN tipos_umas T ON U.id_tipos_umas=T.id
						WHERE {$flt}
						GROUP BY U.id, U.codigo_barras, U.codigo_externo,
								T.descricao,
								P.codigo_barras,
								U.complemento
						ORDER BY U.codigo_barras";

		} else {
			$sql = "SELECT U.id, U.codigo_barras uma, U.codigo_externo,
								T.descricao tipo,
								TP.descricao tipo_programacao,
								P.codigo_barras posicao,
								U.complemento,
								MIN(UI.data) inicio, MAX(UI.data) final
						FROM umas U
						LEFT JOIN umas_itens UI ON U.id=UI.id_umas
						LEFT JOIN programacao O ON UI.id_programacao=O.id
						LEFT JOIN tipos_programacao TP ON O.id_tipos_programacao=TP.id
						LEFT JOIN posicoes P ON U.id_posicoes=P.id
						LEFT JOIN tipos_umas T ON U.id_tipos_umas=T.id
						WHERE {$flt}
						GROUP BY U.id, U.codigo_barras, U.codigo_externo,
								T.descricao,
								P.codigo_barras,
								U.complemento
						ORDER BY U.codigo_barras";
		}
		$rs = dbQuery($sql);
		$zpl="";
		$cnt=0;
		$return=gerarZpl($rs, $id_programacao, $fonte, $qtd, $itensPorEtiqueta);
		$gerou=$return["gerou"];
		$zpl=$return["zpl"];
		$cnt=$return["cnt"];
		if ($gerou)
		{
			$impresso=imprimirZpl($zpl, $impressora);
			if ($impresso) {
				return (true);
			} else {
				return (false);
			}
		} else {
			return (false);
		}
	}
}

function processarImpressaoEtiqueta($zpl, $tamanho, $pdf, $gPage, $nome, $chave) {

	global $sp, $html, $o, $backButton, $gParam;

	if (!empty($_REQUEST["pre_visualizacao"])) {
		if ($nome == "POSICAO") {
			userLog("Gerou " . $chave . " etiquetas das posições {$de} até {$ate}");
		}

		$zpl  = str_replace("\n","", $zpl);
		$pdf .= zpl2pdf($zpl,$tamanho);
		if ($pdf<>"") {
			download("etiquetas.pdf", $pdf);
			return true;
		} else {
			download("etiquetas.prn", $zpl);
			return true;
		}
		return false;
	}

	if (intval($gParam["USA_IMPRESSORA"]["ativo"]) == 0 ||
	   (intval($gParam["USA_IMPRESSORA"]["ativo"]) && !$_REQUEST["id_impressora"]))
	{
		download("etiquetas.prn", $zpl);
		return true;
	}

	if ($nome == "SKUs") {
		userLog("Gerou ".$_REQUEST['quantidade']." etiquetas do SKU ".$_REQUEST['codigo']);
	}

	$impressorasDisponiveis = dbFastQuery($sp["combo_impressora"]);

	if (!$impressorasDisponiveis || empty($_REQUEST["id_impressora"])) {
		download("etiquetas.prn", $zpl);
	}

	$impressora = gFieldById("impressoras", $_REQUEST["id_impressora"] , "comando");
	$impresso = imprimirZpl($zpl, $impressora);

	if ($impresso) {
		redirect($o->page . "&gPage=" . $gPage . "&gMsg=1");
	}

	$html .= $o->msgTitle("Gerar etiquetas - $nome");
	$html .= $o->msgDanger("Não foi possível imprimir a etiqueta pois as configurações da impressora não estão corretas");
	$html .= $o->br();
	$html .= $backButton;
}

function montaUmaEtiqueta($id_programacao, $id_tipos_umas, $fonte, $saldo, $row, $itens, $descricaoItem, $lotesSeparadosPorVirgula, $lotesSeparadosPorEnter, $minData, $nr)
{
	global $gParam, $usrId, $usrName, $EMPRESA;
	$usrId = intval($usrId);

	/*
	@1@  - UMA
	@2@  - Código externo (código alternativo da UMA quando importada de outros sistemas)
	@3@  - Tipo de etiqueta
	@4@  - Posição atual
	@5@  - Nome do proprietário
	@6@  - OS
	@7@  - Item 1
	@8@  - Item 2
	@9@  - Item 3
	@10@ - NUMSEQ
	@11@ - Lotes separados por vírgula
	@12@ - Lotes separados por enter
	@13@ - Descrição do item (linha 1)
	@14@ - Descrição do item (linha 2)
	@15@ - Data da criação
	@16@ - Código de um item
	@17@ - Lotes deste código separados por enter
	@18@ - Quantidades deste código separados por enter
	@19@ - Unidades deste código separados por enter
	@20@ - Data e hora da impressão
	@21@ - Nome do usuário
	@22@ - Nome completo de um item
	@23@ - Dados do QR-Code
	*/

	$etiqueta = str_replace("@1@", $row['uma'], $fonte);
	$etiqueta = str_replace("@2@", $row['codigo_externo'].' ', $etiqueta);
	$etiqueta = str_replace("@3@", normalize($row['tipo']).' ', $etiqueta);
	$sql = "
		SELECT P.*,TP.descricao tipo
		FROM programacao P
		JOIN tipos_programacao TP ON TP.id=P.id_tipos_programacao
		WHERE P.id='" . ($id_programacao ?: $saldo[0]['id_programacao']) . "'";
	$prog = dbQuery($sql)[0];
	$os = $prog["os"];
	$tipoOS = $prog["tipo"]; 

	
	if (!$gParam['PERFIL_FABRICANTE']['ativo'])
	{
		// Logic, demais empresas
		$posicao=($row["posicao"]) ? $row["posicao"] : $saldo[0]["posicao"];
		$etiqueta = str_replace("@4@", $posicao.' ', $etiqueta);
		if ($gParam['USA_POSICAO_COMO_UMA']['ativo']) {
			$sql = "
				SELECT pessoas.nome
				FROM notas
				LEFT JOIN pessoas ON pessoas.id = notas.id_pessoas_fornecedor 
				WHERE id_programacao = {$id_programacao}";
				$fornecedor = dbquery($sql)[0]['nome'];
				$fornecedor = explode(' ', $fornecedor);
				$fornecedor = $fornecedor[0].' '.$fornecedor[1];
				$fornecedor = substr($fornecedor, 0, 25);
			$etiqueta = str_replace("@5@", normalize($fornecedor).' ', $etiqueta);
		} else {
			$etiqueta = str_replace("@5@", normalize($saldo[0]['proprietario']).' ', $etiqueta);
		}
		
		$etiqueta = str_replace("@6@", $os, $etiqueta);
	} else {
		$etiqueta = str_replace("@6@", normalize($row['tipo_programacao']), $etiqueta);
	}
	$etiqueta = str_replace("@10@", $row['numseq'], $etiqueta);
	$etiqueta = str_replace("@11@", $lotesSeparadosPorVirgula, $etiqueta);
	$etiqueta = str_replace("@12@", $lotesSeparadosPorEnter, $etiqueta);
	$etiqueta = str_replace("@13@", substr($descricaoItem,0,80), $etiqueta);
	$etiqueta = str_replace("@14@", substr($descricaoItem,80,80), $etiqueta);
	$etiqueta = str_replace("@15@", gDateTime($minData), $etiqueta);

	


	$etiqueta = str_replace("@20@", str_replace('/20','/',date("d/m/Y H:m:s")), $etiqueta);
	$etiqueta = str_replace("@21@", $_SESSION['usrNickname'], $etiqueta);

	$etiqueta = str_replace("@22@", $saldo[$nr]['item'], $etiqueta);
	$qrcode = $EMPRESA.';'.$row['uma'];

	if ($id_tipos_umas==4)
	{
		$etiqueta = str_replace("@7@", "", $etiqueta);
		$etiqueta = str_replace("@8@", "", $etiqueta);
		$etiqueta = str_replace("@9@", "", $etiqueta);
	} else {
		if($gParam["MOSTRAR_OBSERVACOES_ETIQUETAS_UMAS"]["ativo"]==1){

			if ($trocar=="")
				$trocar = " ";
			$trocar = explode(' - ',$itens[0]);
			$etiqueta = str_replace("@7@", $trocar[0], $etiqueta);
			$etiqueta = str_replace("@8@", $trocar[1], $etiqueta);
			$etiqueta = str_replace("@9@", $row['observacoes'], $etiqueta);

		}else{
			
			for($i=0; $i<3; $i++)
			{
				$trocar = $itens[$i];
				if ($trocar=="")
					$trocar = " ";
				$etiqueta = str_replace("@".($i+7)."@", $trocar.' ', $etiqueta);
			}
		}

	}
	// Um item por etiqueta ?
	if ($nr>=0)
	{

		$lotes       = array();
		$quantidades = array();
		$unidades    = array();
		$ttl = 0;
		$max = 4;
		$cnt = 0;
		for($a=$nr; $a<count($saldo); $a++)
		{
			if ($saldo[$nr]['codigo']==$saldo[$a]['codigo'])
			{
				$cnt++;
				if ($cnt<=$max)
				{
					$lotes[]       = $saldo[$a]['lote'];
					$quantidades[] = intval($saldo[$a]['quantidade']);
					if ($cnt==$max)
					{
						$unidades[] = $saldo[$a]['unidade_sigla']."...";
					} else {
						$unidades[] = $saldo[$a]['unidade_sigla'];
					}

					$qrcode.=';'.$saldo[$a]['lote'].",".$saldo[$a]['quantidade'].",".$saldo[$a]['unidade_sigla'];
				}
				$ttl+=$saldo[$a]['quantidade'];
			}
		}
		$lotes[]       = "Total";
		$quantidades[] = intval($ttl);
		$unidades[]    = "";
		$etiqueta = str_replace("@16@", $saldo[$nr]['codigo'], $etiqueta);
		$etiqueta = str_replace("@17@", implode('\&',$lotes), $etiqueta);
		$etiqueta = str_replace("@18@", implode('\&',$quantidades), $etiqueta);
		$etiqueta = str_replace("@19@", implode('\&',$unidades), $etiqueta);
	}
	$etiqueta = str_replace("@23@", $qrcode, $etiqueta);

	return($etiqueta);
}


function gerarZpl($rs, $id_programacao, $fonte, $qtd=0, $itensPorEtiqueta=0, $idItensSkusAssociar=0)
{
	global $gParam;

	$group_by_default="
		U.id,
        U.ativo,
        U.codigo_barras,
        U.codigo_externo,
        U.conferida_saida,
        U.id_armazens,
        PP.codigo_barras,
        U.posicionada,
        PA.codigo_barras,
        U.data,
        UI.id_itens_skus,
        UI.lote,
        SK.codigo,
        I.shelf_life,
        I.id,
        I.nome,
        A.descricao,
        SK.quantidade,
        D.descricao,
        UI.reservada,
        UI.separada,
        UI.avariada,
        UI.bloqueada";
	$classUma= new UMA();
	$zpl="";
	$cnt=0;
	if (count($rs)>0)
	{
		$minData = "2999-01-01";
		$maxData = "1900-01-01";
		foreach ($rs as $row)
		{
			$cnt++;
			if ($idItensSkusAssociar) {
				$saldo = dbQuery("
					SELECT  umas.codigo_barras,
							umas.observacoes,
						    umas_itens.lote,
						    CONCAT(
						    	itens.nome,
						    	' PB ',
						    	itens_skus.peso_bruto
						    ) AS item,
						    itens_skus.codigo,
							umas_itens.reservada,
							umas_itens.separada,
							umas_itens.avariada,
							umas_itens.bloqueada,
							unidades.sigla AS unidades_sigla,
							pessoas.apelido proprietario,
							pessoas.id id_pessoas_proprietario
					FROM umas
					LEFT JOIN itens_skus ON itens_skus.id = umas.id_itens_skus_associar
					LEFT JOIN itens ON itens.id = itens_skus.id_itens
					LEFT JOIN umas_itens ON umas_itens.id_umas = umas.id
					LEFT JOIN unidades ON unidades.id = itens_skus.id_unidades
					LEFT JOIN pessoas ON pessoas.id = itens.id_pessoas_proprietario
					WHERE itens_skus.ativo = 1
						 AND umas.ativo = 1
						 AND itens.ativo = 1
						 AND itens_skus.id = '".$idItensSkusAssociar."'
						 AND pessoas.id = '".$_REQUEST['id_proprietario']."';
				");
				
			} else {
				$saldo=$classUma->obtemUmasComSaldo("(UI.id_umas='".$row["id"]."')", true, 0, "SK.codigo,UI.lote", 0, $group_by_default, 0, "", "", "", "", ['addSelect' => ",SK.palete_lastro, SK.palete_altura"]);

				if (count($saldo)==0)
				{
					$sql = "SELECT U.id, U.codigo_barras, T.descricao tipo, U.data inicio, U.data final, 0 codigo, U.id_programacao
							FROM umas U
							LEFT JOIN tipos_umas T ON U.id_tipos_umas=T.id
							WHERE U.id=".$row["id"];
					$saldo = dbQuery($sql);
				}
			}

			$montarLote=array();
			$itens =  array();
			$descricaoItem="";

			foreach ($saldo as $s)
			{
				if ($row['inicio']<$minData)
				{
					$minData = $row['inicio'];
				}
				if ($row['final']>$maxData)
				{
					$maxData = $row['final'];
				}
				if (!empty($s["lote"]) && !is_null($s["lote"]))
				{
					if (!in_array($s["lote"], $montarLote))
					{
						$montarLote[]=$s["lote"];
					}
				}
				if (empty($descricaoItem))
				{
					if (!empty($s["item"]))
					{
						$descricaoItem=$s['codigo'].'-'.$s["item"];
					}
				}
			}

			
			$minData = date("Y-m-d H:i:s");
			$lotesSeparadosPorVirgula=implode(", ", $montarLote);
			$lotesSeparadosPorEnter=implode('\&', $montarLote);

			$exibir_item=array();
			foreach ($saldo as $skus)
			{
				if (!in_array($skus["codigo"], $exibir_item))
				{
					$exibir_item[]=$skus["codigo"];
					$status='';
					if ($skus['avariada']==1)
						$status.='[Avar]';
					if ($skus['bloqueada']==1)
						$status.='[Bloq]';

					if (!$gParam['USA_POSICAO_COMO_UMA']['ativo']) {
						$formatoSetup = gVar("global.numformat");
						gVar("global.numformat", '0.000,00');
						$paleteLastro = gFloat($skus['palete_lastro']);
						if (substr($paleteLastro, -3) == ',00') {
							$paleteLastro = substr($paleteLastro, 0, -3);
						}

						$paleteAltura = gFloat($skus['palete_altura']);
						if (substr($paleteAltura, -3) == ',00') {
							$paleteAltura = substr($paleteAltura, 0, -3);
						}
						$paletizacao = ' [' . $paleteLastro . 'X' . $paleteAltura . ']';
						gVar("global.numformat", $formatoSetup);

						$itens[] = $skus['codigo'] . $paletizacao . ' - '.normalize($skus['item']).' '.$row['unidade_sigla'].' '.$status;

					}
				}
			}
			if ($itensPorEtiqueta==0)
			{
				$zpl.=montaUmaEtiqueta($id_programacao, $id_tipos_umas, $fonte, $saldo, $row, $itens, $descricaoItem, $lotesSeparadosPorVirgula, $lotesSeparadosPorEnter, $minData, -1);
			} else {
				// Um etiqueta pra cada item contido na UMA
				// Primeiro
				$codAtual = "";

				foreach ($saldo as $nr=>$umItem)
				{
					if ($codAtual<>$umItem['codigo'])
					{
						$zpl.=montaUmaEtiqueta($id_programacao, $id_tipos_umas, $fonte, $saldo, $row, $itens, $descricaoItem, $lotesSeparadosPorVirgula, $lotesSeparadosPorEnter, $minData, $nr);
						$zpl.="\n\n";
					}
					$codAtual = $umItem['codigo'];
				}
			}
		}
		if ($id_programacao==999999999999 || $id_programacao==0)
		{
			dbQuery("UPDATE umas SET id_programacao=0 WHERE id_programacao=999999999999");
			$classUma->executaAtividade("Gerou etiquetas de UMA ", $cnt, 14);
			userLog("Gerou etiquetas");
		} else {

			$classUma->executaAtividade("Gerou etiquetas de UMA da OS ".$os, $cnt, 14);
			userLog("Gerou etiquetas da OS {$os}");
		}
		$gerou=true;
	} else
	{
		$gerou=false;
	}

	$zplFinal = '';
	if ($qtd == 0)
	{
		$qtd=1;
	}
	for ($i=0; $i<$qtd; $i++)
	{
		$zplFinal.=$zpl;
	}
	$return=array();
	$return["gerou"]=$gerou;
	$return["zpl"]=$zplFinal;
	$return["cnt"]=$cnt;
	return ($return);
}

function imprimirZpl($zpl, $comandoImpressora)
{
	global $usrId, $gPath;
	$usrId = intval($usrId);
	if (!empty($comandoImpressora) && !is_null($comandoImpressora))
	{
		$arq = $gPath."etiquetas/etiquetas_".$usrId."_".rand(1,9999).".prn";
		$comandoImpressora = str_replace('“','"', $comandoImpressora);
		$comandoImpressora = str_replace('etiquetas.prn', $arq, $comandoImpressora);
		$comandoImpressora = str_replace('@file@', $arq, $comandoImpressora);
		$zpl = str_replace("\n","", $zpl);
		file_put_contents($arq, $zpl);
		gLog("Imprimindo etiquetas: ".$comandoImpressora);
		shell_exec($comandoImpressora);
		//unlink("/tmp/".$arq);
		return (true);
	} else {
		return (false);
	}
}

/* Gerar combo baseado na quantidade de itens */
function renderComboItem($comboSql, $value="")
{
	/*Conferir quantidade de itens*/
	$totalItem=(dbQuery("SELECT count(id) tt FROM itens_skus")[0]['tt']);
	if ($totalItem>3000)
	{
		$combo="{name: codigo_itens_skus; fieldLabel: Código Item; type:text; value:".$value.";}";
	} else
	{
		$combo="{name: id_itens_skus; fieldLabel: Item; type: combo; items:".$comboSql."; value:".$value.";}";
	}
	return ($combo);
}

function filtroComboItem($requisicao, $aliasSKU)
{
	$return=array();
	if (isset($requisicao["id_itens_skus"]) && $requisicao["id_itens_skus"])
	{
		$idItem=intval($requisicao["id_itens_skus"]);
		$return["where"]= " AND ({$aliasSKU}.id='{$idItem}')";
		$sql="SELECT
        		ISK.id,
        		CONCAT(CONCAT_WS(' • ',ISK.codigo, I.descricao,U.descricao), ' com ', CAST(ISK.quantidade as SIGNED)) descricao
        	FROM itens I
        	LEFT JOIN itens_skus ISK on ISK.id_itens = I.id
        	LEFT JOIN unidades U on U.id = ISK.id_unidades
        	WHERE ISK.id='$idItem'
        	GROUP BY ISK.id, I.descricao";
		$return["cabecalho"] = " Item: ".dbQuery($sql)[0]["descricao"];
	} else if (isset($requisicao["codigo_itens_skus"]) && $requisicao["codigo_itens_skus"])
	{
		$codigoItem=gCleanField($requisicao["codigo_itens_skus"]);
		$return["where"]= " AND ({$aliasSKU}.codigo like '%{$codigoItem}%' OR {$aliasSKU}.codigo_barras like '%{$codigoItem}%')";
		$return["cabecalho"]=" Código do item: ".$codigoItem;
	}
	return ($return);
}

function obtemNotasId($idProgramacao)
{
	$sql="SELECT
			NI.id_notas id
		  FROM programacao_itens PI
		  LEFT JOIN notas_itens NI ON NI.id = PI.id_notas_itens
		  WHERE PI.id_programacao = {$idProgramacao}
		  GROUP BY NI.id_notas
		 ";
	$rs=dbQuery($sql);
	if (count($rs)>0)
	{
		$inNotas=array();
		foreach ($rs as $nota)
		{
			if (!in_array($nota["id"], $inNotas))
			{
				$inNotas[]=$nota["id"];
			}
		}
		return ($inNotas);
	}
	return array();
}


function gerarEtiquetaPedidos(
	$idProgramacao,
	$volumeTotal,
	$id_impressora,
	$download = false
) {
	global $gParam,$gPathFiles;
	$dadosEtiqueta = consultarDadosEtiquetaPedido($idProgramacao);
	$detalhesNfe   = $dadosEtiqueta['detalhes_nfe'];

	$volumeAtual = 1;
	$templateEtiqueta = consultarTemplateEtiquetaPedido();

	while ($volumeAtual <= $volumeTotal)
	{
		$novaEtiqueta[$volumeAtual] = $templateEtiqueta;

		$substituicoesEtiqueta = array(
			'@volume@'     => $volumeAtual.'/'.$volumeTotal,
			'@rota@'       => $detalhesNfe['rota'],
			'@bairro@'     => $detalhesNfe['bairro'],
			'@destino@'    => $detalhesNfe['cidade'],
			'@cliente@'    => limpaString($detalhesNfe['nome']),
			'@conferente@' => $dadosEtiqueta['conferente'],
			'@pedido@'     => $dadosEtiqueta['numero_cliente'],
			'@data@'       => date('d/m/Y H:i:s')
		);

		foreach ($substituicoesEtiqueta as $macro => $valor) {
			$novaEtiqueta[$volumeAtual] = str_replace($macro, $valor, $novaEtiqueta[$volumeAtual]);
		}
		++$volumeAtual;
	}

	if ($download) {
		$nomeArquivoEtiqueta = 'etiquetas_pedido_'.$dadosEtiqueta['numero_cliente'].'.prn';
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-disposition: attachment; filename='.$nomeArquivoEtiqueta);
		echo implode('', $novaEtiqueta);
		exit;
	} else {
		$sql="SELECT comando FROM impressoras WHERE id=".(int) $id_impressora;
		$rs = dbQuery($sql)[0];
		if($rs){
			$nomeArquivoEtiqueta = $gPathFiles.'/etiquetas_pedido_'.uniqid().'.prn';
			$etiquetaPedido      = fopen($nomeArquivoEtiqueta, 'w');
			fwrite($etiquetaPedido, implode('', $novaEtiqueta));
			fclose($etiquetaPedido);
			$cmd = str_replace('@file@',$nomeArquivoEtiqueta,$rs['comando']);
			gLog($cmd);
			shell_exec($cmd);
		}

	}

	return $nomeArquivoEtiqueta;
}

function gerarEtiquetaPedidosAlternativo($idProgramacao, $volumeTotal) {
	global $gParam, $gPathFiles;
	$dadosEtiqueta = consultarDadosEtiquetaPedidoAlternativo($idProgramacao);
	if ($dadosEtiqueta['erro']) {
		return $dadosEtiqueta['erro'];
	}
	$agora = date('Y-m-d H:i:s');
	$volumeAtual = 1;
	$templateEtiqueta = consultarTemplateEtiquetaPedidoAlternativo();
	$endereco = $dadosEtiqueta['endereco'].", ".$dadosEtiqueta['numero_endereco'].", ".$dadosEtiqueta['bairro'];
	if (strlen($endereco) > 35) { //quantidade maxima de caracteres em uma linha da etiqueta
		$endereco = separarString($endereco, 35);
	}

	while ($volumeAtual <= $volumeTotal) {
		$novaEtiqueta[$volumeAtual] = $templateEtiqueta;
		$substituicoesEtiqueta = array(
			'@data@'       => gDateTime($agora),
			'@nf@'     	   => $dadosEtiqueta['numero_nota'],
			'@empresa@'    => substr(limpaString($dadosEtiqueta['nome']), 0, 43),
			'@endereco1@'  => $endereco[0],
			'@endereco2@'  => $endereco[1],
			'@cep@' 	   => $dadosEtiqueta['cep'],
			'@cidade@'     => substr($dadosEtiqueta['cidade'], 0, 20),
			'@estado@'	   => ($dadosEtiqueta['estado']),
			'@emitente@'   => $dadosEtiqueta['emitente'],
			'@os@'   	   => $dadosEtiqueta['os'],
			'@qtd@'		   => str_pad($volumeAtual, 2, 0, STR_PAD_LEFT),
			'@max@' 	   => str_pad($volumeTotal, 2, 0, STR_PAD_LEFT),
		);

		foreach ($substituicoesEtiqueta as $macro => $valor) {
			$novaEtiqueta[$volumeAtual] = str_replace($macro, $valor, $novaEtiqueta[$volumeAtual]);
		}
		++$volumeAtual;
	}

	$nomeArquivoEtiqueta = 'etiquetas_pedido_'.$dadosEtiqueta['numero_cliente'].'.prn';
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-disposition: attachment; filename='.$nomeArquivoEtiqueta);
	echo implode('', $novaEtiqueta);
	exit;
}


function consultarDadosEtiquetaPedido($idProgramacao)
{
	global $gParam;
	//consulta dados a serem preenchidos na etiqueta
	$sql = "SELECT programacao.id,
			detalhes_nfe,
			numseq,
			numero_cliente,
			pessoas.nome conferente,
			pessoas_enderecos.bairro,
			destinatario.nome AS destinatario_nome,
			enderecos_cidades.descricao AS cidade
		FROM programacao
		LEFT JOIN pessoas ON pessoas.id = programacao.id_pessoas_executou
		LEFT JOIN pessoas destinatario ON destinatario.id = programacao.id_pessoas_destinatario
		LEFT JOIN pessoas_enderecos ON pessoas_enderecos.id_pessoas = destinatario.id
		LEFT JOIN enderecos_cidades ON enderecos_cidades.id = pessoas_enderecos.id_enderecos_cidades
		WHERE programacao.id = ".$idProgramacao . ' LIMIT 1';
	$dadosEtiqueta = dbQuery($sql)[0];
	$dadosEtiqueta['numero_cliente'] = $dadosEtiqueta['numseq'] ?: $dadosEtiqueta['numero_cliente'];

	$detalhesNfe   = $dadosEtiqueta['detalhes_nfe'];
	$detalhesNfe   = json_decode($detalhesNfe, true);
	if (
		$gParam['INTEGRACAO_WINTHOR']['ativo']
		&& $dadosEtiqueta['id_pessoas_proprietario'] != ID_GA
		&& $dadosEtiqueta['id_pessoas_proprietario'] != ID_VETBR
		&& (
			!$detalhesNfe
			|| ($detalhesNfe['rota'] && !$detalhesNfe['bairro'] && !$detalhesNfe['cidade'])
		)
	) {
		$detalhesNfe = array(
			'bairro' => $dadosEtiqueta['bairro'],
			'cidade' => $dadosEtiqueta['cidade'],
			'nome' => $dadosEtiqueta['destinatario_nome'],
			'rota' => $detalhesNfe['rota'] ?: 'INDEFINIDO (A)'
		);
	}

	if (!$detalhesNfe) {
		$detalhesNfe = array(
			'nome'   => 'INDEFINIDO (A)',
			'rota'   => 'INDEFINIDO (A)',
			'bairro' => 'INDEFINIDO (A)',
			'cidade' => 'INDEFINIDO (A)',
			'obs'    => 'INDEFINIDO (A)',
			'obs1'   => 'INDEFINIDO (A)',
			'obs2'   => 'INDEFINIDO (A)'
		);
	}
	$dadosEtiqueta['detalhes_nfe'] = $detalhesNfe;

	return $dadosEtiqueta;
}


function consultarDadosEtiquetaPedidoAlternativo($idProgramacao)
{
	$sql =
		"SELECT PR.os,
				N.numero AS numero_nota,
				N.id AS id_notas,
				PE.endereco AS endereco,
				PE.numero AS numero_endereco,
				PE.bairro AS bairro,
				PE.cep AS cep,
				EC.descricao AS cidade,
				EE.sigla AS estado
		FROM programacao PR
		LEFT JOIN notas N ON N.id_programacao = PR.id
		LEFT JOIN pessoas P ON P.id = N.id_pessoas_proprietario
		LEFT JOIN pessoas_enderecos PE ON PE.id_pessoas = P.id
		LEFT JOIN enderecos_cidades EC ON PE.id_enderecos_cidades = EC.id
		LEFT JOIN enderecos_estados EE ON PE.id_enderecos_estados = EE.id
		WHERE PR.id = {$idProgramacao}";
	$dadosEtiqueta = dbQuery($sql)[0];

	if (!$dadosEtiqueta['id_notas']) {
		$dadosEtiqueta['erro'] = 'Não foi possível gerar etiqueta pois a OS não possui nota fiscal associada';
		return false;
	}

	$sql =
		"SELECT xml
		FROM notas N
		LEFT JOIN nfe ON nfe.id = N.id_nfe
		WHERE N.id = ".$dadosEtiqueta['id_notas'];
	$xml = dbQuery($sql)[0][0];

	if ($xml) {
		$xml = simplexml_load_string($xml);
		$emitente = gCleanField($xml->NFe->infNFe->emit->xNome);
		$destinatario = gCleanField($xml->NFe->infNFe->dest->xNome);
	}

	$dadosEtiqueta = array(
		'numero_nota'     => $dadosEtiqueta['numero_nota'] ?: 'INDEFINIDO (A)',
		'nome'  		  => $destinatario ?: 'INDEFINIDO (A)',
		'endereco'		  => $dadosEtiqueta['endereco'] ?: 'INDEFINIDO (A)',
		'numero_endereco' => $dadosEtiqueta['numero_endereco'] ?: 'INDEFINIDO (A)',
		'bairro'    	  => $dadosEtiqueta['bairro'] ?: 'INDEFINIDO (A)',
		'cep'  			  => $dadosEtiqueta['cep'] ?: 'INDEFINIDO (A)',
		'cidade' 		  => $dadosEtiqueta['cidade'] ?: 'INDEFINIDO (A)',
		'estado'  		  => $dadosEtiqueta['estado'] ?: 'INDEFINIDO (A)',
		'emitente' 		  => $emitente ?: 'INDEFINIDO (A)',
		'os' 		  	  => $dadosEtiqueta['os'] ?: 'INDEFINIDO (A)'
	);

	return $dadosEtiqueta;
}

function consultarTemplateEtiquetaPedido()
{
	//consulta template da etiqueta de pedido
	$sql = "
		SELECT codigo
		FROM etiquetas
		WHERE id = 10";
	$templateEtiqueta = dbQuery($sql)[0];

	return $templateEtiqueta['codigo'];
}

function consultarTemplateEtiquetaPedidoAlternativo()
{
	//consulta template da etiqueta de pedido
	$sql = "
		SELECT codigo
		FROM etiquetas
		WHERE nome = 'Pedido'";
	$templateEtiqueta = dbQuery($sql)[0];

	return $templateEtiqueta['codigo'];
}



function imprimirEtiqueta($idImpressora, $nomeArquivo) {
	//consulta impressora
	$sql = "SELECT id, comando FROM impressoras WHERE id = ".$idImpressora;
	$comandoImpressora = dbQuery($sql)[0]['comando'];

	if (!empty($idImpressora)) {
		$comandoImpressora = str_replace('/tmp/etiquetas.prn“', $nomeArquivo, $comandoImpressora);
		shell_exec($comandoImpressora);

		return true;
	}

	return false;
}



// -------------------------- ROTINAS DE INTEGRAÇÃO TOTVS ---------------------- //

/* Conexão WebService com o TOTVS */
class TOTVS_WebService
{
	public $jarr;
	// Teste
	// public $ip = "192.168.254.7";
	// public $porta = "8079";

	// Produção Bomix
	public $ip = "192.168.254.94";
	public $porta = "5996";


	function __construct($json="")
	{
		$this->jarr=cssDecode($json);
		if (gVar("protheus.ip")<>"")
		{
			$this->ip = gVar("protheus.ip");
			gLog("==== Protheus IP: ".$this->ip);
		}
		if (gVar("protheus.porta")<>"")
		{
			$this->porta = gVar("protheus.porta");
			gLog("==== Protheus Porta: ".$this->porta);
		}
	}
	/**
	 * Processa dados recebidos e formata-os para o layout do corpo do webservice
	 * @param  [string] $raiz           Tag raiz
	 * @param  [array] $mtz             Array associativo contendo campos e valores
	 * @return [string] this->xmlEnvio  Dados formatados
	 */
	function processaEnvio($body="")
	{
		$sai= '<?xml version="1.0" encoding="utf-8"?>';
		$sai.= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
		$sai.= 		'<soap:Body>';
		$sai.= 			$body;
		$sai.= 		'</soap:Body>';
		$sai.= '</soap:Envelope>';
		return($sai);
	}

	/**
	 * @return array ou string $sai array em caso de erro, string (com a reposta) em caso positivo
	 */
	function envia($idTotvs)
	{
		global $usrId, $gParam,$EMPRESA,$dbConn;
		$usrId = intval($usrId);
		$liberado = false;
		$sai = false;

		for ($a=0; $a<60; $a++)
		{
			if (!file_exists("/tmp/".$EMPRESA."_enviando_protheus.lck"))
			{
				break;
			} else {
				sleep(1);
			}
		}
		$liberado = true;
		if ($this->ip=="0")
		{
			$liberado = false;
			gLog("==== Protheus DESATIVADO!");
		}

		if ($liberado)
		{
			file_put_contents("/tmp/".$EMPRESA."_enviando_protheus.lck",date("Y-m-d H:i:s"));
			$sql = "SELECT TW.*, U.codigo_barras
						FROM totvs_webservice TW
						LEFT JOIN umas U ON TW.id_umas=U.id
						WHERE TW.enviado=0 AND TW.id=$idTotvs ORDER BY TW.id";
			$rs = dbQuery($sql);
			foreach($rs as $row)
			{
				$nome   = $row['nome'];
				$metodo = $row['metodo'];
				$body   = $row['conteudo'];
				$soap_request = trim(str_replace("\n","",$this->processaEnvio($body)));


				$url = $gParam['INTERFACE_PROTHEUS']['valor']."/";
				$url = $url.$nome.".apw";



				$header = array(
								"Content-type: text/xml;charset=\"utf-8\"",
								"Accept: text/xml",
								"Cache-Control: no-cache",
								"Pragma: no-cache",
								"SOAPAction: \"http://".$this->ip.":".$this->porta."/".$metodo."\"",
								"Content-length: ".strlen($soap_request),
								);

				$url = "http://".$this->ip.":".$this->porta."/".$nome.".apw";

				// Conexão com o Webservice do Protheus (desenvolvido por Christian Tanaka)
				$soap_do = curl_init($url);
				curl_setopt($soap_do, CURLOPT_URL, $url);
				curl_setopt($soap_do, CURLOPT_FOLLOWLOCATION, true); // se tiver um redirecionamento, vai atrás dele
				curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 360);
				curl_setopt($soap_do, CURLOPT_TIMEOUT,        720);
				curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
				curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($soap_do, CURLOPT_POST,           true );
				curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $soap_request);
				curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $header);

				$response = curl_exec($soap_do);
				$erroDoCurl = curl_errno($soap_do);
				$erroDoCurlMsg = curl_error($soap_do);

				curl_close($soap_do);


				$spOk      = true;
				$analise   = "";
				$documento = "";
				$op        = "";
				$json      = "";

				if ($erroDoCurl==0)
				{
					// Verifica se a operação foi bem-sucedida usando o Stored Procedure criado por Brito em 04/03/2020
					$sql = "Exec GiuSoft.dbo.sp_GetDadosMovimentacaoProtheus '".$row['id']."', '".$row['codigo_barras']."'";

					$PDO = new PDO("odbc:sqlsrv", "GiuSoft", "BomYixx@19");
					$PDO->exec( "USE GiuSoft");
					$SQLServerRS = $PDO->query( $sql );
					$SQLServerRows = $SQLServerRS->fetchAll( PDO::FETCH_ASSOC );
					gLog("--- CONSULTANDO PROTHEUS");
					gLog($sql);

					// A consulta deve retornar 2 registros (como normalmente acontece no Protheus)
					if (count($SQLServerRows)==2)
					{
						gLog("0 produto id: ".trim($SQLServerRows[0]['Produto_ID'])." - ".$row['codigo_sku_saida']);
						gLog("1 produto id: ".trim($SQLServerRows[1]['Produto_ID'])." - ".$row['codigo_sku_entrada']);
						if ($row['tipo']=="Transferencia")
						{
							if (
									trim($SQLServerRows[0]['Produto_ID']) != $row['codigo_sku_saida'] ||
									trim($SQLServerRows[1]['Produto_ID']) != $row['codigo_sku_entrada']
								)
							{
								$spOk = false;
								$analise.="Produto no Protheus diferiu do informado pelo WMS\n";
							}
							if (
									trim($SQLServerRows[0]['Lote']) != $row['lote'] ||
									trim($SQLServerRows[1]['Lote']) != $row['lote']
								)
							{
								$spOk = false;
								$analise.="Lote no Protheus diferiu do informado pelo WMS\n";
							}
							if (
									trim($SQLServerRows[0]['Quantidade']) != $row['quantidade'] ||
									trim($SQLServerRows[1]['Quantidade']) != $row['quantidade']
								)
							{
								$spOk = false;
								$analise.="Quantidade no Protheus diferiu da informada no WMS\n";
							}
						} else {
							if (
									trim($SQLServerRows[0]['Lote']) != $row['lote'] ||
									trim($SQLServerRows[1]['Lote']) != $row['lote']
								)
							{
								$spOk = false;
								$analise.="Lote no Protheus diferiu do informado pelo WMS\n";
							}
							if (
									trim($SQLServerRows[0]['Quantidade']) != $row['quantidade'] ||
									trim($SQLServerRows[1]['Quantidade']) != $row['quantidade']
								)
							{
								$spOk = false;
								$analise.="Quantidade no Protheus diferiu da informada no WMS\n";
							}
						}
					} elseif (count($SQLServerRows)==1)
					{
						$spOk = false;
						$analise="Operação foi realizada parcialmente no Protheus";
					} else {
						$spOk = false;
						$analise="Operação não foi realizada no Protheus";
					}
					gLog($analise);
					$json = json_encode($SQLServerRows);
				} else {
					$analise = "Protheus sobrecarregado!";
				}

				$dbConn = array();

				$connName = gVar("database.name").gVar("database.url");
				if (is_null($dbConn[$connName]))
				{
					$dbConn[$connName] = new gDatabase("{mode: pdo}");
					$dbConn[$connName]->connect();
				}


				$PDOMySQL = new PDO(gVar("database.engine") . ":host=" . gVar("database.url") . "$charset;dbname=" . gVar("database.name"), gVar("database.user"), gVar("database.password"), array(PDO::ATTR_PERSISTENT => true));
				$PDOMySQL->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				if ($spOk)
				{
					if ($erroDoCurl==28 || ($spOk && stripos($response,'faultcode')===false && stripos($response,'-- XMLWSVCCONNECT INFORMATION --')===false && (stripos($response,'TRANSMOD1') || stripos($response,'PCPMOD2') )))
					{
						$sql = "UPDATE totvs_webservice SET enviado=1, erro=0, erro_curl='".$erroDoCurl."', resposta_curl='".$erroDoCurlMsg."', erro_analise=".($spOk ? 0:1).", realizado_protheus='".$json."', analise='".$analise."', documento='".$SQLServerRows[1]['Documento']."', op='".$SQLServerRows[1]['OP']."', resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id'];
						gLog($sql);
						$stmt = $PDOMySQL->prepare( $sql );
						$stmt->execute();
						$sai = true;
					} elseif(stripos($response,'faultcode')){
						$sql = "UPDATE totvs_webservice SET enviado=1, erro=1, erro_curl='".$erroDoCurl."', resposta_curl='".$erroDoCurlMsg."', erro_analise=".($spOk ? 0:1).", realizado_protheus='".$json."', analise='".$analise."', documento='".$SQLServerRows[1]['Documento']."', op='".$SQLServerRows[1]['OP']."', resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id'];
						gLog($sql);
						$stmt = $PDOMySQL->prepare( $sql );
						$stmt->execute();
					} elseif(stripos($response,'-- XMLWSVCCONNECT INFORMATION --')) {
						$sql = "UPDATE totvs_webservice SET enviado=0, erro=1, erro_curl='".$erroDoCurl."', resposta_curl='".$erroDoCurlMsg."', erro_analise=".($spOk ? 0:1).", realizado_protheus='".$json."', analise='".$analise."', documento='".$SQLServerRows[1]['Documento']."', op='".$SQLServerRows[1]['OP']."', resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id'];
						gLog($sql);
						$stmt = $PDOMySQL->prepare( $sql );
						$stmt->execute();
					} else{
						$sql = "UPDATE totvs_webservice SET enviado=1, erro=1, erro_curl='".$erroDoCurl."', resposta_curl='".$erroDoCurlMsg."', erro_analise=".($spOk ? 0:1).", realizado_protheus='".$json."', analise='".$analise."', documento='".$SQLServerRows[1]['Documento']."', op='".$SQLServerRows[1]['OP']."', resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id'];
						gLog($sql);
						$stmt = $PDOMySQL->prepare( $sql );
						$stmt->execute();
					}
				} else {
					$sql = "UPDATE totvs_webservice SET enviado=1, erro=1, erro_curl='".$erroDoCurl."', resposta_curl='".$erroDoCurlMsg."', erro_analise=".($spOk ? 0:1).", realizado_protheus='".$json."', analise='".$analise."', documento='".$SQLServerRows[1]['Documento']."', op='".$SQLServerRows[1]['OP']."', resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id'];
					gLog($sql);
					$stmt = $PDOMySQL->prepare( $sql );
					$stmt->execute();
				}
				// file_put_contents("/tmp/soap_request.txt", $soap_request);
				// file_put_contents("/tmp/soap_response.txt", $response);
				gLog("===> Webservice: ".$metodo." -> $analise");
			}
			unlink("/tmp/".$EMPRESA."_enviando_protheus.lck");
		}
		return $sai;
	}

}


function TOTVS_insereNaFila($campos)
{
	$id = dbInsert("totvs_webservice", $campos, true);
	if (strpos($campos['conteudo'],'WMS_ID')!==false)
	{
		$conteudo = str_replace('WMS_ID',$id,$campos['conteudo']);
		dbQuery("UPDATE totvs_webservice SET conteudo='$conteudo' WHERE id=".$id);
	}
	return($id);
}

function TOTVS_transfereArmazem($json)
{
	global $usrId, $gParam, $persistencia;
	$usrId = intval($usrId);
	if (!$gParam['PERFIL_FABRICANTE']['ativo']) {
		return true;
	}

	gLog("---- TRANSFERÊNCIA PROTHEUS ----- ".$json);
	$jarr = cssDecode($json);
	$tipo_operacao=isset($jarr["tipo_operacao"])
		? $jarr["tipo_operacao"]
		: "posicionamento";

	$id_posicoes=$jarr["origem"];
	$o = $jarr['origem'];
	$d = $jarr['destino'];

	if ($jarr['origem']=='' || $jarr["origem"]=='0')
	{
		$jarr['origem'] = TOTVS_ARMAZEM_PADRAO;
	}


	if (is_numeric($jarr['origem']))
	{
		$sql = "SELECT A.codigo FROM posicoes P LEFT JOIN areas A ON P.id_areas=A.id WHERE P.id=".intval($jarr['origem']);
		$rst = dbQuery($sql);
		if (count($rst)>0)
		{
			$jarr['origem'] = $rst[0]['codigo'];
		} else {
			$jarr['origem'] = TOTVS_ARMAZEM_PADRAO;
		}
	}
	if (is_numeric($jarr['destino']))
	{
		$sql = "SELECT A.codigo,A.codigo_faturamento FROM posicoes P LEFT JOIN areas A ON P.id_areas=A.id WHERE P.id=".intval($jarr['destino']);
		$rst = dbQuery($sql);
		if ($tipo_operacao=="saida" && $rst[0]['codigo_faturamento']<>"")
		{
			$jarr["destino"]=$rst[0]['codigo_faturamento'];
		} else
		{
			$jarr["destino"]=$rst[0]['codigo'];
		}
	}

	if ($jarr['origem'] == "")
	{
		$jarr['origem'] = TOTVS_ARMAZEM_PADRAO;
	}

	if ($jarr['destino'] == "")
	{
		$jarr['destino'] = TOTVS_ARMAZEM_PADRAO;
	}
	// Só faz a transferência se origem e destino forem diferentes
	if ( ($jarr['origem']<>$jarr['destino'])
		|| (isset($jarr["estorno"]) && $jarr["destino"]=="LO") )
	{
		$totvs = new TOTVS_WebService();
		if (isset($jarr["estorno"]))
		{
			$uma = $persistencia->obtemUMAsComSaldo($jarr['buscar_uma'], false);
		} else
		{
			$uma = $persistencia->obtemUMAsComSaldo($jarr['buscar_uma']);
		}

		//function obtemUMAsComSaldo($where="", $soAtivas=true, $prioridade=0, $orderBy="", $priorizarPaleteAberto=0, $groupBy="")
		if (count($uma)==0)
		{
			return (true);
		}
		$ok=true;
		$mostrouUma = false;
		if (isset($jarr["estorno"]))
		{				$sql="SELECT
					posicoes.id, posicoes.codigo_barras, areas.codigo, areas.codigo_faturamento
				FROM posicoes
				LEFT JOIN areas ON areas.id = posicoes.id_areas
				WHERE posicoes.id=".intval($id_posicoes)."
				GROUP BY posicoes.codigo_barras, areas.codigo, areas.codigo_faturamento
				";
			$posicao=dbQuery($sql);
			if ($posicao[0]["codigo_faturamento"]<>"")
			{
				$jarr["origem"] = $posicao[0]["codigo_faturamento"];
			} else
			{
				$jarr["origem"] = $posicao[0]["codigo"];
			}
			$jarr["destino"]= $posicao[0]["codigo"];
		}
		else{
			foreach ($uma as $item)
			{
				$sql="SELECT
						quantidade
					FROM totvs_saldo_atual
					WHERE codigo_sku='".$item['codigo']."'
					AND lote='".$item["lote"]."'
					AND local='".$jarr["origem"]."'
					AND quantidade>='".abs($item["quantidade"])."'
					";
				if (count(dbQuery($sql))==0)
				{
					if (!$mostrouUma)
					{
						$persistencia->erros[]=$item['codigo_barras'];
						$mostrouUma = true;
					}
					$descricao="Sem saldo no ".$jarr['origem'].": ".$item['codigo']." • ".$item["lote"]." = ".intval($item["quantidade"]);
					if ($tipo_operacao<>"saida")
					{
						$mtz=array();
						$mtz["id_pessoas"]=$usrId;
						$mtz["id_umas"]=$item["id"];
						$mtz["id_umas_para"]=0;
						$mtz["id_posicoes"]=$item["id_posicoes"];
						$mtz["id_pessoas_proprietario"]=$item["id_pessoas_proprietario"];
						$mtz["tipo"]="L";
						$mtz["data"]=date("Y-m-d H:s:s");
						$mtz["descricao"]=$descricao;
						$mtz["lote"]=$item["lote"];
						$mtz["lote_para"]="";
						$mtz["data_validade"]=$item["data_validade"];
						$mtz["data_fabricacao"]=$item["data_fabricacao"];
						dbInsert("umas_movimentos", $mtz);
					}
					$persistencia->erros[]=$descricao;
					$ok=false;
				}
			}
		}

		if ($ok)
		{
			foreach ($uma as $item)
			{
				$body = "";
				$body.=tagMe("CEMPRESA", TOTVS_EMPRESA); // produção
				$body.=tagMe("CFIL", TOTVS_FILIAL);
				$body.=tagMe("CUSERID", TOTVS_USUARIO); // Código do usuário WMS no TOTVS
				$transMod=tagMe("CARMDEST",  $jarr['destino']);
				$transMod.=tagMe("CARMORIG",  $jarr['origem']);
				$transMod.=tagMe("CDTVALID",  str_replace("-","",$item['data_validade']));
				$transMod.=tagMe("CLOTECTL",  $item['lote']);
				$transMod.=tagMe("CPRODDEST", $item['codigo']);
				$transMod.=tagMe("CPRODORIG", $item['codigo']);
				$transMod.=tagMe("COBSERVA", $item['codigo_barras'].';WMS_ID');
				$transMod.=tagMe("NQUANT",    intval(abs($item['quantidade'])));
				$body.=tagMe("O_TRANSMOD1", $transMod);
				$body =tagMe("TRANSMOD1",$body);

				$cmps = array();
				$cmps['data'] = date('Y-m-d H:i:s');
				$cmps['enviado'] = 0;
				$cmps['nome'] = 'WSTRANSFERENCIA';
				$cmps['metodo'] = 'TRANSMOD1';
				$cmps['tipo'] = 'Transferencia';
				$cmps['conteudo'] = $body;
				$cmps['id_umas'] = intval($item['id']);
				$cmps['id_programacao'] = intval($jarr['id_programacao']);
				$cmps['id_pessoas'] = intval($usrId);
				$cmps['id_itens_skus'] = $item['id_itens_skus'];
				$cmps['codigo_sku_saida'] = $item['codigo'];
				$cmps['codigo_sku_entrada'] = $item['codigo'];
				$cmps['lote'] = $item['lote'];
				$cmps['saida'] = $jarr['origem'];
				$cmps['entrada'] = $jarr['destino'];
				$cmps['quantidade'] = intval(abs($item['quantidade']));

				//file_put_contents('/tmp/webservice',$body);
				$idTotvs=TOTVS_insereNaFila($cmps);

				$ok=$totvs->envia($idTotvs);
			}
			return ($ok);
		} else
		{
			return (false);
		}
	} else {
		return(true); // Origem e destino iguais, não faz nada e retorna ok
	}

}

function TOTVS_apontamentoPaletizacao($json, $item)
{
	global $usrId, $gParam, $persistencia,$AMBIENTE_TESTE;
	$usrId = intval($usrId);


	$novaPaletizacao = true;

	if ($gParam['PERFIL_FABRICANTE']['ativo']==1)
	{
		$jarr = cssDecode($json);

		$body = "";

		$body.=tagMe("CEMPRESA", TOTVS_EMPRESA); // produção
		$body.=tagMe("CFIL",     TOTVS_FILIAL);
		$body.=tagMe("CUSERID",  TOTVS_USUARIO); // Código do usuário WMS no TOTVS


		$dataInicio   = str_replace("-","",substr($jarr['dataInicio'],0,10));
		$dataFinal    = str_replace("-","",substr($jarr['dataFinal'],0,10));
		$horaInicio   = date("H:i",strtotime("-2 minute",strtotime($jarr['dataInicio'])));
		$horaFinal    = substr($jarr['dataFinal'],11,5);
		$dataValidade = str_replace("-","",$item['data_validade']);
		$op           = $jarr['op'];
		$cpt          = $jarr['cpt'];
		$recurso      = $item['recurso'];
		$lote         = $item['lote'];
		$produto      = $item['codigo_paletizado'];
		$produto_int  = $item['codigo'];
		$uma          = $item['codigo_barras'];
		$quantidade   = intval($item['quantidade']);
		$turno        = 'TURNO 03';
		if (str_replace(':','',$horaFinal)>=520)
		{
			$turno = 'TURNO 01';
		}
		if (str_replace(':','',$horaFinal)>=1350 && str_replace(':','',$horaFinal)<2210)
		{
			$turno = 'TURNO 02';
		}


		$totvs = new TOTVS_WebService();

		$transMod=tagMe("CARMAZEM",  'CT');
		$transMod.=tagMe("CDATAFIN",  $dataFinal);
		$transMod.=tagMe("CDATAINI",  $dataInicio);
		$transMod.=tagMe("CDTVALID",  $dataValidade);
		$transMod.=tagMe("CFERRAM",   "");
		$transMod.=tagMe("CHORAFIN",  $horaFinal);
		$transMod.=tagMe("CHORAINI",  $horaInicio);
		$transMod.=tagMe("CLOTE",     $lote);
		if ($novaPaletizacao)
		{
			$transMod.=tagMe("COP",       "W---");
		} else {
			$transMod.=tagMe("COP",       $op);
		}
		$transMod.=tagMe("COPERACAO", '01');
		$transMod.=tagMe("CPRODUTO",  $produto);
		if ($novaPaletizacao)
		{
			$transMod.=tagMe("CPRODINT",  $produto_int);
		}
		$transMod.=tagMe("CPT",       $cpt);
		$transMod.=tagMe("CRECURSO",  $recurso);
		$transMod.=tagMe("CTURNO",    $turno);
		$transMod.=tagMe("NQUANT",    $quantidade);
		$transMod.=tagMe("COBSERVA",  $uma.';WMS_ID');


		$body.=tagMe("O_PCPMOD2", $transMod);
		$body =tagMe("PCPMOD2",$body);

		$cmps = array();
		$cmps['data'] = date('Y-m-d H:i:s');
		$cmps['enviado'] = 0;
		$cmps['erro'] = 0;
		$cmps['nome'] = 'WSPCPMOD2';
		$cmps['metodo'] = 'PCPMOD2';
		$cmps['tipo'] = 'Paletizacao';
		$cmps['conteudo'] = $body;
		$cmps['id_umas'] = intval($item['id']);
		$cmps['id_programacao'] = intval($jarr['id_programacao']);
		$cmps['id_pessoas'] = intval($usrId);
		$cmps['id_itens_skus'] = $item['id_itens_skus'];
		$cmps['codigo_sku_saida'] = $item['codigo'];
		$cmps['codigo_sku_entrada'] = $item['codigo_paletizado'];
		$cmps['lote'] = $lote;
		$cmps['saida'] = 'CT';
		$cmps['entrada'] = 'CT';
		$cmps['quantidade'] = intval(abs($item['quantidade']));

		$idTotvs=TOTVS_insereNaFila($cmps);
		if ($_SERVER['HTTP_HOST']!='localhost' && $_SERVER['HTTP_HOST']!='127.0.0.1')
		{
			if ($novaPaletizacao)
			{
				// Formato da OP: 5.2.3
				// Primeiras letras já usadas: 0,1,2,3,4,A,K,O,P
				// A primeira a ser usada pelo WMS é a letra W

				$let = chr(intval(87+intval($idTotvs/1048575)));
				$num = $idTotvs - (intval($idTotvs/1048575)*1048575);
				$novaOp = strtoupper($let.str_pad(dechex($num), 5, STR_PAD_LEFT)."01001");
				$body = str_replace("W---",$novaOp,$body);
				$body = str_replace('WMS_ID',$idTotvs,$body);
				gLog("----- CRIADA A OP: ".$novaOp. " (id ".$idTotvs.") --------");
				dbQuery("UPDATE totvs_webservice SET conteudo='$body' WHERE id=".$idTotvs);
			}
			$sai = $totvs->envia($idTotvs);
			return($sai);
		} else {
			return(true);
		}
	} else {
		return(true);
	}
}



// -------------------- ROTINAS DE INTEGRAÇÃO JUNGHEINRICH ---------------------- //

class Jungheinrich
{
	public $jarr;

	function __construct($json="")
	{
		$this->jarr=cssDecode($json);
	}
	/**
	 * Processa dados recebidos e formata-os para o layout do corpo do webservice
	 * @param  [string] $raiz           Tag raiz
	 * @param  [array] $mtz             Array associativo contendo campos e valores
	 * @return [string] this->xmlEnvio  Dados formatados
	 */
	function processaEnvio($metodo, $body="")
	{
		/* $sai= '<?xml version="1.0" encoding="utf-8"?>'; */
		$sai.= '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wms="http://schemas.microsoft.com/clr/nsassem/WmsConnectionWebService.LIExternalLink/WmsConnectionWebService">';
		$sai.= 		'<soap:Header/>';
		$sai.= 		'<soap:Body>';
		$sai.= 		'<wms:'.$metodo.'>';
		$sai.= 		'<data>';
		$sai.= 			$body;
		$sai.= 		'</data>';
		$sai.= 		'</wms:'.$metodo.'>';
		$sai.= 		'</soap:Body>';
		$sai.= '</soap:Envelope>';
		return($sai);
	}

	/**
	 * @return array ou string $sai array em caso de erro, string (com a reposta) em caso positivo
	 */
	function envia($id=0)
	{
		global $usrId, $gParam;
		$usrId = intval($usrId);

		// Verifica se tem algo pendente na fila, aguardando resposta da empilhadeira
		$aguardandoConclusaoTarefaPendente = false;

		$redePermitida = true;
		if ($redePermitida && !$aguardandoConclusaoTarefaPendente)
		{
			$liberado = false;
			for ($a=0; $a<60; $a++)
			{
				if (!file_exists("/tmp/enviando_jungheinrich.lck"))
				{
					break;
				} else {
					sleep(1);
				}
			}
			$liberado = true;
			if ($liberado)
			{
				file_put_contents("/tmp/enviando_jungheinrich.lck",date("Y-m-d H:i:s"));

				if ($id>0)
				{
					$sql = "SELECT J.*, E.ip_porta
							FROM jung_webservice J
							LEFT JOIN equipamentos E ON J.id_equipamentos=E.id
							WHERE J.id=".$id;
				} else {
					$sql = "SELECT J.*, E.ip_porta
							FROM jung_webservice J
							LEFT JOIN equipamentos E ON J.id_equipamentos=E.id
							WHERE enviado=0 ORDER BY J.id_equipamentos, J.id LIMIT 1";
				}
				$rs = dbQuery($sql);
				//gDR($rs);
				$idEquipamento=0;
				foreach ($rs as $row)
				{
					if ($idEquipamento<>$row['id_equipamentos'] && $row['ip_porta']<>'')
					{
						//$url = "http://192.168.1.205:8080/soap/LIExternalLink";
						$nome   = $row['nome'];
						$metodo = $row['metodo'];
						$body   = $row['conteudo'];
						$url    = 'http://'.$row['ip_porta']."/soap/LIExternalLink";
						$metodo = "SendRequestWithReply";

						$soap_request = trim(str_replace("\n","",$this->processaEnvio($metodo, $body)));

						$header = array(
										"Content-type: text/xml;charset=\"utf-8\"",
										"Accept: */*",
										"Cache-Control: no-cache",
										"Pragma: no-cache",
										//"SOAPAction: \"http://schemas.microsoft.com/clr/nsassem/WmsConnectionWebService.LIExternalLink/WmsConnectionWebService#SendRequestWithReplyInput\"",
										"SOAPAction: \"http://schemas.microsoft.com/clr/nsassem/WmsConnectionWebService.LIExternalLink/WmsConnectionWebService#".$metodo."\"",
										"Content-length: ".strlen($soap_request),
										);

						// gDR(date("H:i:s"));
						// gDR("HEADER");
						// gDR($header);echo "\n\n";
						// gDR("BODY $url");
						// gDR($body);echo "\n\n";
						// gDR(htmlentities($soap_request));
						// gDR("REQUEST");
						// gDR($soap_request);echo "\n\n";
						file_put_contents("/var/log/gs_jung.log", date("Y-m-d H:i:s") . " > $url \n", FILE_APPEND);
						$soap_do = curl_init($url);
						curl_setopt($soap_do, CURLOPT_URL, $url);
						curl_setopt($soap_do, CURLOPT_FOLLOWLOCATION, true); // se tiver um redirecionamento, vai atrás dele
						curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 60);
						curl_setopt($soap_do, CURLOPT_TIMEOUT,        60);
						curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
						curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
						curl_setopt($soap_do, CURLOPT_POST,           true );
						curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $soap_request);
						curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $header);
						$response = curl_exec($soap_do);
 						if (stripos($response,'<return>OK</return>')!==false)
						{
							dbQuery("UPDATE jung_webservice SET enviado=1, erro=0, resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id']);
						} elseif(stripos($response,'faultcode')){
							dbQuery("UPDATE jung_webservice SET enviado=1, erro=1, resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id']);
						} elseif(stripos($response,'-- XMLWSVCCONNECT INFORMATION --')) {
							dbQuery("UPDATE jung_webservice SET enviado=0, erro=1, resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id']);
						} else{
							dbQuery("UPDATE jung_webservice SET resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id']);
						}
						curl_close($soap_do);
						file_put_contents("/var/log/gs_jung.log", date("Y-m-d H:i:s") . " > $response \n", FILE_APPEND);
						// file_put_contents("/tmp/soap_request.txt", $soap_request);
						// file_put_contents("/tmp/soap_response.txt", $response);
						//gLog("===> Webservice: ".$metodo);
						//gDR(date("H:i:s"));
					}
					$idEquipamento = $row['id_equipamentos'];
				}
				unlink("/tmp/enviando_jungheinrich.lck");
			}
		} else
		{
			$liberado = false;
			for ($a=0; $a<60; $a++)
			{
				if (!file_exists("/tmp/enviando_jungheinrich.lck"))
				{
					break;
				} else {
					sleep(1);
				}
			}
			$liberado = true;
			if ($liberado)
			{
				file_put_contents("/tmp/enviando_jungheinrich.lck",date("Y-m-d H:i:s"));

				if ($id>0)
				{
					$sql = "SELECT J.*, E.ip_porta
							FROM jung_webservice J
							LEFT JOIN equipamentos E ON J.id_equipamentos=E.id
							WHERE J.id=".$id;
				} else {
					$sql = "SELECT J.*, E.ip_porta
							FROM jung_webservice J
							LEFT JOIN equipamentos E ON J.id_equipamentos=E.id
							WHERE enviado=0 ORDER BY J.id_equipamentos, J.id LIMIT 1";
				}
				$rs = dbQuery($sql);
				//gDR($rs);
				$idEquipamento=0;
				foreach ($rs as $row)
				{
					if ($idEquipamento<>$row['id_equipamentos'] && $row['ip_porta']<>'')
					{
						//$url = "http://192.168.1.205:8080/soap/LIExternalLink";
						$nome   = $row['nome'];
						$metodo = $row['metodo'];
						$body   = $row['conteudo'];
						$row['ip_porta']='localhost/wms/logic/api.php?teste=ok';
						$url    = 'http://'.$row['ip_porta'];
						$metodo = "SendRequestWithReply";
						$soap_request = trim(str_replace("\n","",$this->processaEnvio($metodo, $body)));
						$header = array(
										"Content-type: text/xml;charset=\"utf-8\"",
										"Accept: */*",
										"Cache-Control: no-cache",
										"Pragma: no-cache",
										//"SOAPAction: \"http://schemas.microsoft.com/clr/nsassem/WmsConnectionWebService.LIExternalLink/WmsConnectionWebService#SendRequestWithReplyInput\"",
										"SOAPAction: \"http://schemas.microsoft.com/clr/nsassem/WmsConnectionWebService.LIExternalLink/WmsConnectionWebService#".$metodo."\"",
										"Content-length: ".strlen($soap_request),
										);

						// gDR(date("H:i:s"));
						// gDR("HEADER");
						// gDR($header);echo "\n\n";
						// gDR("BODY $url");
						// gDR($body);echo "\n\n";
						// gDR(htmlentities($soap_request));
						// gDR("REQUEST");
						// gDR($soap_request);echo "\n\n";

						$soap_do = curl_init($url);
						curl_setopt($soap_do, CURLOPT_URL, $url);
						curl_setopt($soap_do, CURLOPT_FOLLOWLOCATION, true); // se tiver um redirecionamento, vai atrás dele
						curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 60);
						curl_setopt($soap_do, CURLOPT_TIMEOUT,        60);
						curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
						curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
						curl_setopt($soap_do, CURLOPT_POST,           true );
						curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $soap_request);
						curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $header);
						$response = curl_exec($soap_do);

	// 						gDR("RESPONSE");
	// 						gDR($response);echo "\n\n";
	// exit;

						if (stripos($response,'<return>OK</return>')!==false)
						{
							dbQuery("UPDATE jung_webservice SET enviado=1, erro=0, resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id']);
						} elseif(stripos($response,'faultcode')){
							dbQuery("UPDATE jung_webservice SET enviado=1, erro=1, resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id']);
						} elseif(stripos($response,'-- XMLWSVCCONNECT INFORMATION --')) {
							dbQuery("UPDATE jung_webservice SET enviado=0, erro=1, resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id']);
						} else{
							dbQuery("UPDATE jung_webservice SET resposta='".$response."', data_envio='".date("Y-m-d H:i:s")."' WHERE id=".$row['id']);
						}
						curl_close($soap_do);
						file_put_contents("/tmp/soap_request.txt", $soap_request);
						file_put_contents("/tmp/soap_response.txt", $response);
						gLog("===> Webservice: ".$metodo);
						//gDR(date("H:i:s"));
					}
					$idEquipamento = $row['id_equipamentos'];
				}
				unlink("/tmp/enviando_jungheinrich.lck");
			}
		}
		return $sai;
	}

	function insereNaFila($campos)
	{
		return(dbInsert("jung_webservice", $campos, true));
	}

	function enviaComando($json)
	{
		global $usrId, $gParam;
		$usrId = intval($usrId);
		$jarr = cssDecode($json);
		$cmps = array();
		$cmps['data'] = date('Y-m-d H:i:s');
		$cmps['enviado'] = 0;
		$cmps['concluido'] = 0;
		$cmps['erro'] = 0;
		$cmps['nome'] = '';
		$cmps['metodo'] = 'SendRequestWithReply';
		$cmps['conteudo'] = str_replace(",",";",$jarr['cmd']);
		$cmps['id_umas'] = intval($jarr['id_umas']);
		$cmps['id_programacao'] = intval($jarr['id_programacao']);
		$cmps['id_pessoas'] = intval($usrId);
		$cmps['id_equipamentos'] = intval($jarr['id_equipamentos']);
		$cmps['id_eventos'] = intval($jarr['id_eventos']);
		if (isset($jarr["tipo"]))
		{
			$cmps['tipo']=$jarr["tipo"];
		}
		$this->envia($this->insereNaFila($cmps));
		return(true);
	}
}




function jungComando($json)
{
	// Exemplo: jungComando("L;02.001.01.01.01")
	$jung = new Jungheinrich();
	//gLog("---------- JUNG COMANDO: ".$json);
	file_put_contents("/var/log/gs_jung.log", date("Y-m-d H:i:s") . " > $json \n", FILE_APPEND);
	$jung->enviaComando($json);
}

function jungFinalizado($json='{}')
{
	$jarr=cssDecode($json);
	$where=array();
	if (isset($jarr["id_programacao"]))
	{
		$where[]="(id_programacao=".intval($jarr["id_programacao"]).")";
	}
	if (isset($jarr["id_equipamentos"]))
	{
		$where[]="(id_equipamentos=".intval($jarr["id_equipamentos"]).")";
	}
	if (isset($jarr["id_evento"]))
	{
		$where[]="(id_eventos=".intval($jarr["id_evento"]).")";
	}
	if (isset($jarr["id_umas"]))
	{
		$where[]="(id_umas=".intval($jarr["id_umas"]).")";
	}
	if (isset($jarr["tipo"]))
	{
		$where[]="(tipo='".$jarr["tipo"]."')";
	}

	$where[]="(enviado=1)";
	$where[]="(concluido=1)";
	$where[]="(erro=0)";
	$where=implode(" AND ", $where);
	$sql="SELECT
			id
		  FROM jung_webservice
		  WHERE {$where}";
	$existe_jung=dbQuery($sql);
	if (count($existe_jung)>0)
	{
		return (true);
	}
	return (false);
}

// Tratamento especial para a BOMIX:
// Obtém a data de fabricação e validade a partir do lote
// Exemplo de Lote: 14B18G2019
//                  14 = semana do ano, 2019 = ano
function calcularDataFabricacaoPeloLoteBomix($lote)
{
	$ano = substr($lote,-4);
	$sem = substr($lote,0,2);
	$week_start = new DateTime();
	$week_start->setISODate($ano,$sem);
	$data = $week_start->format('Y-m-d');
	return($data);
}

function calcularDataValidade2AnosPelaDataFabricacao($dataFabricacao)
{
	return(date("Y-m-d", strtotime("+730 day", strtotime($dataFabricacao))));
}


function dataEhValida($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}


function limpaProgramacoesComProblema()
{
	$sql = "SELECT distinct P.id, PI.id idi, P.os, P.numseq, P.data_previsao
			FROM programacao P
			LEFT JOIN programacao_itens PI ON (P.id <> PI.id_programacao AND P.numseq=PI.uma)
			WHERE P.executada=0 AND P.id_tipos_programacao IN (1,14,19,20,21,23,27) AND PI.id_tipos_programacao IN (1,14,19,20,21,23,27)";
	$rs = dbQuery($sql);
	foreach($rs as $row)
	{
		dbQuery("DELETE FROM programacao WHERE id=".$row['id']);
		if (intval($row['idi'])>0)
		{
			dbQuery("DELETE FROM programacao_itens WHERE id=".$row['idi']);
		}
	}
}


function recalcularPosicoes($idPosicoes = array())
{
	if ($idPosicoes == '*') {
		$sql = "
			UPDATE posicoes P
			LEFT JOIN (
			    SELECT
			        umas.id_posicoes,
			        COUNT(umas.id) AS qtd_umas_na_posicao
			    FROM umas
			    WHERE umas.ativo = 1
			      AND umas.posicionada = 1
			    GROUP BY umas.id_posicoes
			) AS qtd_umas_por_posicao ON qtd_umas_por_posicao.id_posicoes = P.id
			SET P.quantidade_posicionada = IFNULL(qtd_umas_por_posicao.qtd_umas_na_posicao, 0)";
		dbFastQuery($sql);
		return;
	}

	$idPosicoes = array_unique(array_filter($idPosicoes));
	if ($idPosicoes) {
		$idPosicoes = implode(', ', $idPosicoes);

		$where = array();
		$where[0] = " AND U.id_posicoes IN ({$idPosicoes})";
		$where[1] = " P.id IN ({$idPosicoes})";

		$sql = "
			UPDATE posicoes P
			LEFT JOIN (
				SELECT
					U.id_posicoes,
					COUNT(U.id) qtd_umas
				FROM
					umas U
				WHERE
					U.ativo = 1
					AND U.posicionada = 1
					" . $where[0] . "
				GROUP BY U.id_posicoes
			) T ON T.id_posicoes = P.id
			SET P.quantidade_posicionada = IFNULL(T.qtd_umas, 0)
			WHERE " . ($where[1] ?: 'true');
		dbFastQuery($sql);
	}
}


function registrarOcorrenciaDivergencia($divergencia, $idTiposOcorrencia = 5, $outrosDados = array())
{
	global $gId, $usrId;
	$sql = "SELECT id, teve_atuacao FROM ocorrencias WHERE id_programacao = {$gId} AND id_tipos_ocorrencias = {$idTiposOcorrencia}";
	$rs = dbQuery($sql);
	$teveAtuacao = array_column($rs, 'teve_atuacao');
	$temOcorrenciaEmAberto = array_search('0', $teveAtuacao);
	$ocorrencia = array();
	$ocorrencia['id_programacao'] = $gId;
	if (
		($divergencia && !$rs)
		|| ($divergencia && !is_numeric($temOcorrenciaEmAberto))
	) {
		$ocorrencia['id_pessoas_criou'] = $usrId;
		$ocorrencia['id_tipos_ocorrencias'] = $idTiposOcorrencia;
		$ocorrencia['data_ocorrencia'] = date("Y-m-d H:i:s");
		$ocorrencia['teve_atuacao'] = 0;
		$ocorrencia['id_umas'] = $outrosDados['id_umas'];
		$ocorrencia['id_umas_itens'] = $outrosDados['id_umas_itens'];
		$ocorrencia['id_pessoas_proprietario'] = $outrosDados['id_pessoas_proprietario'];
		$ocorrencia['complemento'] = $outrosDados['descricao'];
		$ocorrencia['id_itens_skus'] = $outrosDados['id_itens_skus'];
		$ocorrencia['quantidade'] = $outrosDados['quantidade'];
		dbInsert("ocorrencias", $ocorrencia);
	} elseif (
		(!$divergencia && is_numeric($temOcorrenciaEmAberto))
	) {
		$ocorrencia['id_pessoas_criou'] = $usrId;
		$ocorrencia['id_pessoas_atuou'] = $usrId;
		$ocorrencia['data_atuacao'] = date("Y-m-d H:i:s");
		$ocorrencia['teve_atuacao'] = 1;
		$ocorrencia['complemento'] = "Divergência corrigida após conclusão da contagem";
		dbUpdate("ocorrencias", $ocorrencia, $rs[$temOcorrenciaEmAberto]['id']);
	}
}


function registrarOcorrenciaEntradaFaltante($ocorrencia)
{
	global $usrId;
	$ocorrencia['idTiposOcorrencias'] = $ocorrencia['idTiposOcorrencias'] ?: 13;//13 = tipos_ocorrencias.id - Chegada item faltante
	$sql = "
		SELECT id
		FROM ocorrencias
		WHERE id_itens_skus = " . (int) $ocorrencia['idItensSkus']
		. " AND (
				(id_tipos_ocorrencias = '" . $ocorrencia['idTiposOcorrencias'] . "' AND teve_atuacao = 0)
				OR (
					id_tipos_ocorrencias = '" . $ocorrencia['idTiposOcorrencias'] . "'
					AND teve_atuacao = 1
					AND data_ocorrencia = '" . $ocorrencia['data'] . "'
				)
			)";
	$rs = dbQuery($sql);
	$ocorrenciaInserir = array();
	if (!$rs) {
		$ocorrenciaInserir['id_pessoas_criou'] = $usrId;
		$ocorrenciaInserir['id_tipos_ocorrencias'] = $ocorrencia['idTiposOcorrencias'];
		$ocorrenciaInserir['id_itens_skus'] = (int) $ocorrencia['idItensSkus'];
		$ocorrenciaInserir['data_ocorrencia'] = $ocorrencia['idItensSkus'];
		$ocorrenciaInserir['teve_atuacao'] = 0;
		dbInsert("ocorrencias", $ocorrenciaInserir);
	}
}


function orderBy()
{
	//exemplo de uso: orderBy($array, 'col1', SORT_DESC, 'col2', SORT_DESC...)
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (!is_string($field)) continue;
        $tmp = array();
        foreach ($data as $key => $row)
            $tmp[$key] = $row[$field];
        $args[$n] = $tmp;
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);

    return array_pop($args);
}


function arrayUniqueMultidimensional($array, $key) {
    $tempArray = array();
    $i = 0;
    $keyArray = array();
    foreach($array as $val) {
        if (!in_array($val[$key], $keyArray)) {
            $keyArray[$i]  = $val[$key];
            $tempArray[$i] = $val;
        }
        $i++;
    }
    return $tempArray;
}


function paramLabel($chave, $padrao="")
{
	global $gParam;
	if ($gParam[$chave]['ativo'])
		return $gParam[$chave]['valor'];
	return $padrao;
}


function ehProgramacaoDeEntrada($idTiposProgramacao)
{
	return in_array($idTiposProgramacao, array(1,14,19,20,21,23,27,28,29));
}


function ehProgramacaoDeSaida($idTiposProgramacao)
{
	return in_array($idTiposProgramacao, array(2,3,4,5,7,22,25,26,24));
}


function somarHoras($horarios = array('00:00:00'))
{
	//exemplo de uso: somarHoras(array('10:00:00', '11:54:48'))
	$soma = 0;
    foreach ($horarios as $horario) {
		list($horas, $minutos, $segundos) = explode( ':', $horario);
		$soma += (($horas * 3600) + ($minutos * 60) + $segundos);
    }

	$segundos = $soma % 60;
	$minutos  = floor(($soma % 3600) / 60);
	$horas    = floor($soma / 3600);

	return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
}


function retirarCaracteresReservadosXml($stringXmlCompleto)
{
	//troca os caracteres '&', ';' por espaco vazio
	return str_replace(array('&', ';'), ' ', $stringXmlCompleto);
}




function downloadModeloImportacao($modelo = '', $gId = '')
{
	header('Content-Type: text/csv; charset=' . gVar('database.charset'));
	header('Content-Disposition: attachment; filename=modelo.csv');

	$modeloGerado = gerarModeloImportacao($modelo, $gId);

	exit;
}


function gerarModeloImportacao($modelo = '', $gId = '')
{
	if ($gId) {
		//Cabeçalho do modelo
		$modeloCabecalho = dbQuery('SELECT nome FROM importacoes_cabecalho WHERE id_importacoes = ' . $gId);
		$modelo = dbQuery('SELECT nome FROM importacoes_registros WHERE id_importacoes = ' . $gId);
		if ($modeloCabecalho) {
			// Cabeçalho do Arquivo
			foreach ($modeloCabecalho as $key => $item) {
				echo '#' . $item['nome'] . ';';
			}
			// Espaço entre cabeçalho e corpo do arquivo
			echo PHP_EOL . PHP_EOL;
			// Corpo do arquivo
			foreach ($modelo as $item) {
				echo '#' . $item['nome'] . ';';
			}
			echo PHP_EOL;
		} else {
			//Corpo do arquivo
			foreach ($modelo as $key => $item) {
				echo $item['nome'] . ';';
			}
		}
	} else {
		$modelo = explode(',', $modelo);

		foreach ($modelo as $key => $item) {
			echo $item . ';' ;
		}
	}
	return;
}


function calcularPrazoMinimo($prazoDestinatario, $prazoItem, $leadTimeDestinatario = 0)
{
	//prazoMinimo = (prazo[%] * campo prazo_validade ou shelf_life (depende de parametros) do item [dias] / 100) + leadTime [dias]
	return (int) ((($prazoDestinatario * $prazoItem) / 100) + $leadTimeDestinatario);
}


function calcularQtdDiasParaValidade($dataValidade)
{
	$dataHoje = new DateTime('tomorrow');
    $dataValidade = new DateTime($dataValidade);
    $quantidadeDiasAteValidade = $dataHoje->diff($dataValidade);
	$quantidadeDiasAteValidade = $quantidadeDiasAteValidade->format('%R%a') + 1; //1 = acrescimo de um dia pois considera-se a data de hoje na quantidade de dias para vencer.
	return $quantidadeDiasAteValidade;
}

function adicionarAspasSeNecessario($var)
{
	$var = gCleanField($var);
	return is_numeric($var) ? ((int) $var) : "'{$var}'";
}


if ($gParam['TIRAR_FOTO_NA_OPERACAO']['ativo']) {
	function descricaoFotos($idTiposProgramacao = 1, $idDescricaoFoto = 0) {
		if ($idDescricaoFoto) {
			$andIdProgramacaoImagensDescricao = " AND id IN ($idDescricaoFoto) ";
		}

		$sql =
			"SELECT id, nome
			FROM programacao_imagens_descricao
			WHERE ativo = 1
				AND id_tipos_programacao IN($idTiposProgramacao)
				{$andIdProgramacaoImagensDescricao}";
		return $sql;
	}

}


function validarLoteFabricado($lote, $idItensSkus)
{
	global $gParam;

	$lote = trim($lote);

	if (
		!$gParam['VALIDAR_LOTE_FABRICADO']['ativo']
		|| !$idItensSkus
		|| !$lote
	) {
		return true;
	}

	$sql = "
		SELECT id, lote
		FROM lote_fabricado
		WHERE id_itens_skus = '{$idItensSkus}'
			AND ativo = 1
		ORDER BY lote = '{$lote}' DESC
		LIMIT 1";
	$rs = dbFastQuery($sql)[0];
	if ($rs['id'] && $rs['lote'] != $lote) {
		return false;
	}

	return true;
}


function atualizarUmasQualidade($idPessoasProprietario)
{
	global $gParam, $usrId;

	$sql =
	     	"SELECT id, lote, id_itens_skus
	     	FROM gestao_qualidade
	     	WHERE id_pessoas_proprietario = {$idPessoasProprietario}
	     		AND ativo = 1";
   $rs = dbQuery($sql);
	if (!$rs[0]['id']) {
		return;
	}

	$select = "U.id,
     	U.codigo_barras,
     	UI.reservada,
     	UI.separada,
     	P.id AS id_pessoas_proprietario,
     	UI.data_validade,
     	UI.data_fabricacao,
     	U.id_posicoes,
     	UI.lote,
     	SK.id AS id_itens_skus,
     	(
     		SELECT id
     		FROM umas_itens
     		WHERE umas_itens.id_umas = U.id
     			AND umas_itens.cancelada = 0
     			AND umas_itens.tipo = '+'
			ORDER BY umas_itens.id DESC LIMIT 1
		) AS ultimo_id_umas_itens";
	$where = "
		U.qualidade = 0
		AND (A.id IS NULL
     		OR (
     			A.divergencia = 0
        		AND A.falta = 0
        		AND A.sobra = 0
        		AND A.codigo <> 9999
        	)
	   )";
	$whereQualidade = array();
	foreach ($rs as $row) {
		$whereQualidade[] = "(UI.lote = '" . $row['lote'] . "' AND SK.id = '" . $row['id_itens_skus'] . "')";
	}
	$where = $where . " AND (" . implode(' OR ', $whereQualidade) . ")";
	$persistencia = new Operacao("{os: false;}");
   $saldos = $persistencia->obtemUmasComSaldo($where, $soAtivas = true, $prioridade = 0, $orderBy = "",
		$priorizarPaleteAberto = 0, $groupBy = "", $priorizarPaleteFechado = 0, $having = "",
		$limit = "", $addGroupBy = "", $select);
	if (!$saldos[0]['id']) {
		return;
	}

   $values = ' VALUES ';
   $moverParaQualidade = array();
   $comunicouSap = array();
	foreach ($saldos as $row) {
		$values .= "({$usrId},
			'" . $row['id'] . "',
			'" . $row['id_posicoes'] . "',
			'" . $row['id_pessoas_proprietario'] . "',
			'A',
			NOW(),
			'Movida automaticamente para qualidade ao reservar',
			'" . $row['lote'] . "',
			'" . $row['data_validade'] . "',
			'" . $row['data_fabricacao'] . "'),";

		$moverParaQualidade[$row['id']] = 1;

		if (
			$gParam['INTEGRACAO_GMI']['ativo']
			&& in_array($row['id_pessoas_proprietario'], explode(',', $gParam['INTEGRACAO_GMI']['valor']))
			&& !$comunicouSap[$row['id']]
		) {
			$persistencia->apenasValidarComunicacaoSap = 0;
			$persistencia->comunicarMovimentacaoSap(
				$row['id'],
				$idPosicaoAnterior = $row['id_posicoes'],
				$idPosicaoAtual = $row['id_posicoes'],
				$row['ultimo_id_umas_itens'],
				$row['ultimo_id_umas_itens'],
				$obterQuantidadeTotal = 1,
				$novoStatusQualidade = 1
			);
			$comunicouSap[$row['id']] = 1;
		}
	}

	if (!$moverParaQualidade) {
		return;
	}

	$sql = "UPDATE umas SET qualidade = 1 WHERE id IN(" . implode(',', array_keys($moverParaQualidade)) . ")";
	dbFastQuery($sql);

   $sql =
   	"INSERT INTO umas_movimentos (
      	id_pessoas,
			id_umas,
			id_posicoes,
			id_pessoas_proprietario,
			tipo,
			`data`,
			descricao,
			lote,
			data_validade,
			data_fabricacao
	) {$values}";

	$sql = substr($sql, 0, -1);
	dbQuery($sql);
}