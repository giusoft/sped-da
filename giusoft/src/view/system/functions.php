<?php

function mostraErros($titulo, $erros = [])
{
	global $o;
	$msg  = $titulo . "<br><br>";
	if ($erros) {
		$msg .= $o->ul($erros);
	}

	return ($msg);
}

function obtemIdEmpresa($idProprietario=0)
{
	global $EMPRESA;

	if (
		$EMPRESA == "logic"
		&& $idProprietario == 334
	) {
		return (2);
	}

	if (intval($_SESSION['filialAtualId']) > 0) {
		return ($_SESSION['filialAtualId']);
	}

	return (dbQuery("SELECT id FROM filial limit 1")[0]["id"]);
}


function obtemProprietario($id, $campo="*")
{
	$sql = "SELECT
				{$campo}
			FROM pessoas
			WHERE id = '{$id}' AND cliente = '1'";
	return (dbQuery($sql)[0]);
}


function obtemSKU($id, $separador="•", $campo="")
{
	$campo = empty($campo) ? "" : $campo . ",";

	$sql = "SELECT
				{$campo}
				CONCAT(CONCAT_WS(' {$separador} ',ISK.codigo, I.nome,U.descricao), ' com ', CAST(ISK.quantidade as SIGNED)) descricao_sku
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
	$request = base64_encode(serialize($_REQUEST));
	$sql = "INSERT INTO gfw_log
	(id_gfw_users,id_equip, id_gfw_menus,date,full_link,request,details) VALUES
	($usrId, $usrEquip, ".intval($gMenuParameters['id_gfw_menus']).", NOW(),'".$gMenuParameters['full_link']."','$request','$details')";
	dbFastQuery($sql);
}


function formataDescricaoItemSKU($codigo, $nome, $unidade, $quantidade)
{
	global $o;
	$descricao = "";
	$sep = "<br>";
	if ($_REQUEST['gPDF'] == 1) {
		$sep = " - ";
	}

	if (!empty($codigo)) {
		if (
			$_REQUEST['gPDF']
			|| $_REQUEST['gXLS']
			|| $_REQUEST['gDOC']
		 	|| $_REQUEST['gCSV']
		 	|| $_REQUEST['g']=='programacao'
		) {
			$descricao .= $codigo . $sep;
		} else {
			$descricao .= $o->big($codigo) . $sep;
		}

	}

	if (!empty($nome)) {
		$descricao .= $nome . $sep;
	}

	if (!empty($unidade)) {
		$descricao .= $unidade . " com ";
	}

	if (!empty($quantidade)) {
		$descricao .= intval($quantidade);
	}

    return ($descricao);
}


function formataDescricaoData($pessoa, $data)
{
	$descricao = "";
	if (!empty($pessoa)) {
		$descricao .= $pessoa;
	}

	if (!empty($data)) {
		$descricao .= $data;
	}
}


function isBase64($texto)
{
	return ($texto === base64_encode(base64_decode((string) $texto)));
}


function decodificarObservacao($texto)
{
	return (isBase64($texto)) ? base64_decode((string) $texto) : $texto;
}


function separarString($string, $limit)
{
	$str = wordwrap((string) $string, $limit, "*");
    $str = explode("*", $str);

    return $str;
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
					".$o->button("{id: ". $btnConfirmar .sprintf('; title: Confirmar; style:primary; size: medium; target: _new; href: %s; }', $url))."
				</div>";
	$html .= $o->modal(sprintf('{title: Confirmação; size: medium; confirm: false; cancel: false; content: %s; name: %s;}', $content, $idModal));
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
    $intervalo = $entrou->diff($saiu);

    $diferenca = "";
    if ($intervalo->days > 0) {
        $diferenca .= $intervalo->days . 'd e ';
    }

    if ($intervalo->h > 0) {
        $diferenca .= $intervalo->h . 'h: ';
    }

    if ($intervalo->i > 0) {
        $diferenca .= $intervalo->i . 'm';
    }

    return ($diferenca);
}


function linkParaGoogle($query, $modoIa = false)
{
	$modoIa = $modoIa ? '&udm=50' : '';

 	return '<a href="https://www.google.com/search?q=' . urlencode(trim((string) $query)) . $modoIa .  '" target="_blank">' . htmlspecialchars(trim((string) $query)) . '</a>';
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

	$sql  = sprintf("SELECT id FROM notas WHERE numero = '%s' AND tipo='S' LIMIT 1", $nfSaida);
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

	$sql  = sprintf("SELECT id FROM notas WHERE numero = '%s' AND tipo = 'E' LIMIT 1", $nfEntrada);
	$idNota = dbQuery($sql)[0]['id'];

	if (!$idNota) {
		return $nfEntrada;
	}

	$rota = 'index.php?g=nf_entrada&gPage=' . $page . '&gId=' . $idNota;
	return '<a target="_new" href="' . $rota . '">' . $nfEntrada . '</a>';
}


function linkParaNota($id = '', $numero = '', $tipo = '')
{
	$link = '';
	if (!$id) {
		return $link;
	}

	if (is_array($id)) {
		$id = implode(',', $id);
	}

	if ($id && $numero && $tipo) {
		$notas = [];
		$notas[0]['id'] = $id;
		$notas[0]['tipo'] = $tipo;
		$notas[0]['numero'] = $numero;
	} else {
		$sql  = sprintf('SELECT DISTINCT id, tipo, numero FROM notas WHERE id IN (%s)', $id);
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
		} else {
			$rota = 'index.php?g=nf_saida&gPage=1&gId=' . $nota['id']; // aba Dados
		}

		$link .= '<a target="_new" href="' . $rota . '">' . $conteudoExibir . '</a>' . ', ';
	}

	return substr($link, 0, -2);
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


/* Gerar combo baseado na quantidade de itens */
function renderComboItem($comboSql, $value="")
{
	/*Conferir quantidade de itens*/
	$totalItem = (dbQuery("SELECT count(id) tt FROM itens_skus")[0]['tt']);
	if ($totalItem > 3000) {
		$combo = "{name: codigo_itens_skus; fieldLabel: Código Item; type:text; value:" . $value . ";}";
	} else {
		$combo = "{name: id_itens_skus; fieldLabel: Item; type: combo; items:" . $comboSql . "; value:" . $value . ";}";
	}

	return ($combo);
}


function filtroComboItem($requisicao, $aliasSKU)
{
	$return = [];
	if (isset($requisicao["id_itens_skus"]) && $requisicao["id_itens_skus"]) {
		$idItem=intval($requisicao["id_itens_skus"]);
		$return["where"]= " AND ({$aliasSKU}.id = '{$idItem}')";
		$sql = "SELECT
					ISK.id,
					CONCAT(CONCAT_WS(' • ',ISK.codigo, I.descricao,U.descricao), ' com ', CAST(ISK.quantidade as SIGNED)) descricao
				FROM itens I
				LEFT JOIN itens_skus ISK on ISK.id_itens = I.id
				LEFT JOIN unidades U on U.id = ISK.id_unidades
				WHERE ISK.id = '{$idItem}'
				GROUP BY ISK.id, I.descricao";
		$return["cabecalho"] = " Item: " . dbQuery($sql)[0]["descricao"];
	} elseif (isset($requisicao["codigo_itens_skus"]) && $requisicao["codigo_itens_skus"]) {
		$codigoItem=gCleanField($requisicao["codigo_itens_skus"]);
		$return["where"]= " AND ({$aliasSKU}.codigo LIKE '%{$codigoItem}%' OR {$aliasSKU}.codigo_barras LIKE '%{$codigoItem}%')";
		$return["cabecalho"]=" Código do item: ".$codigoItem;
	}

	return ($return);
}


function orderBy()
{
	$args = func_get_args();
	$data = array_shift($args);
    foreach ($args as $n => $field) {
        if (!is_string($field)) {
            continue;
        }

        $tmp = [];
        foreach ($data as $key => $row) {
            $tmp[$key] = $row[$field];
		}

        $args[$n] = $tmp;
    }

    $args[] = &$data;
    array_multisort($args);

    return array_pop($args);
}


function arrayUniqueMultidimensional($array, $key) {
    $tempArray = [];
    $i = 0;
    $keyArray = [];
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

	if ($gParam[$chave]['ativo']) {
		return $gParam[$chave]['valor'];
	}

	return $padrao;
}


function somarHoras($horarios = ['00:00:00'])
{
	//exemplo de uso: somarHoras(array('10:00:00', '11:54:48'))
	$soma = 0;
    foreach ($horarios as $horario) {
		[$horas, $minutos, $segundos] = explode( ':', (string) $horario);
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
	return str_replace(['&', ';'], ' ', $stringXmlCompleto);
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
		$modelo = explode(',', (string) $modelo);

		foreach ($modelo as $key => $item) {
			echo $item . ';' ;
		}
	}

	return;
}



function agora()
{
	return date('Y-m-d H:i:s');
}