<?php

include_once 'Pagination.php';

class NotasFiscais extends ImportacaoNFE
{

	public $tipo;
	public $inner_item=false;
	protected $cfopsCombustivel;
	function __construct($tipo){
		$this->tipo = $tipo;
		$this->tabela = "notas";
		$this->ordenacao = "notas.id";
		$this->cfopsCombustivel = ['5663', '5664', '5655', '5665'];
		$this->erros=[];
	}

	/**
	* Obtem o cabecalho da nota fiscal com os principais atributos.
	*
	* @param  array  $row   Array com informações da nota fiscal.
	* @return string $html  string com código html do cabeçalho.
	*/
	public function defineTipo($tipo)
	{
		$this->tipo = $tipo;
	}


	public function verificarVencimentoCertificado()
	{
        $sql = "
        	SELECT data_vencimento_certificado data
        	FROM filial_notas
			WHERE id_filial = " . $_SESSION['filialAtualId'];
        $dataVencimento = dbQuery($sql)[0]['data'];
        if ($dataVencimento == '0000-00-00') {
        	return;
        }

        $dataAtual = date('Y-m-d');
        if ($dataAtual >= $dataVencimento) {
			$mensagem = array(
            	'descricao' => "A emissão de notas fiscais não será possível pois o certificado venceu em " . gDate($dataVencimento),
            	'tipo' => 'danger'
            );
            return $mensagem;
        }

        $diferencaDias = (int) date_diff(date_create($dataAtual), date_create($dataVencimento))->format("%a");
        if ($diferencaDias > 0 && $diferencaDias <= 60) {
        	 $mensagem = array(
            	'descricao' => "Restam apenas " . $diferencaDias . " dias para vencimento do certificado",
            	'tipo' => 'warning'
            );
        }

        return $mensagem;
	}


    public function validarFilialSessao($idNota)
    {
        $sql = "SELECT id_filial FROM notas WHERE id = " . (int)$idNota;
        $rs = dbFastQuery($sql);

        if (!$rs) {
            return ['sucesso' => false, 'msg' => 'Nota fiscal não encontrada.'];
        }

        $idFilialNota = (int)$rs[0]['id_filial'];
        $idFilialSessao = (int)$_SESSION['filialAtualId'];

        if ($idFilialNota != $idFilialSessao) {
            $nomeFilialNota = dbFastQuery("SELECT descricao FROM filial WHERE id = $idFilialNota")[0]['descricao'];

            return [
                'sucesso' => false,
                'msg' => "Esta nota pertence ao filial: <b>{$nomeFilialNota}</b><br>".
                         "Por favor, troque para o filial correto antes de realizar operações nesta nota"
            ];
        }

        return ['sucesso' => true];
    }


	function obtemDetalhesCabecalho($row)
	{
		global $o, $gPage, $gParam, $gId;

		$html = $o->tableBegin("big");

		$mtz = [];
		if ($row['cancelada'] == 1) {
			$mtz[] = '<-' . $o->small('Filial') . "<br><b>" . $row['filial'] . "</b>&nbsp;";
		} else {
			$mtz[] = '<-' . $o->small('Filial') . "<br><b>" . $row['filial'] . "</b>&nbsp;";
		}

		$mtz[] = '<-' . $o->small('Proprietário') . '<br><b>'
			. linkParaCadastroEmpresa($row['id_pessoas_proprietario'], $row['nome_proprietario'])
			. '</b>&nbsp;<br/>';
		$mtz[] = '<-' . $o->small('Transportadora') . '<br><b>' . $row['nome_transportadora'] . "</b>&nbsp;";
		$tipo = $row["tipo"] == "E" ? "Entrada" : "Saída";
		$mtz[] = '<-'. $o->small('Tipo').'<br><b>'."{$tipo}</b>&nbsp;";

		if (
			in_array($row["situacao"], array("Aprovada", "Reprovada", "Cancelada"))
		) {
			$mtz[] = '<-' . $o->small('CFOP').'<br><b>' . $row['codigo_cfops'];

		}

		$html .= $o->tableRow($mtz, 'header');

		$mtz = [];

		$mtz[] = '<-' . $o->small('Número').'<br><b>' . $row['numero'] . "</b>&nbsp<br/>".$o->small("&nbsp;");

		$mtz[]='<-' . $o->small('Emissão') . '<br><b>'
			. gDate($row['data_emissao'])."</b>&nbsp <br>"
			. $o->small($row['apelido_nfe']) . "<br>"
			. $o->small(
				gCheck($row['data_emissao'] != "0000-00-00", false, array("", "Não emitida"))
			);
		$mtz[]='<-'.$o->small('Movimento').'<br><b>'.gDate($row['data_movimento']) . "</b>&nbsp<br/>".$o->small("&nbsp;");
		$mtz[]='<-'.$o->small('Cadastro').'<br><b>'.gDateTime($row['data_criou'])."</b>&nbsp<br/>". $o->small($row['nome_criou']);

		if (in_array($row['situacao'], ['Cancelada', 'Reprovada'])) {
			$mtz[] = '<-'. $o->small('Situação').'<br><b><span class="label label-danger">'.$row["situacao"]."</span></b>" . "</b>&nbsp<br/>".$o->small("&nbsp;");
		} elseif ($row["situacao"] == "Aprovada") {
			$mtz[] = '<-'. $o->small('Situação').'<br><b><span class="label label-success">'.$row["situacao"]."</span></b>" . "</b>&nbsp<br/>".$o->small("&nbsp;");
		}

		$html .= $o->tableRow($mtz, 'header');

		if ($this->tipo == 'E') {
			$mtz = [];
			$mtz[] = '<-' . $o->small('CFOP')  . "<br><b>" . $row['codigo_cfops'] ."</b>&nbsp;";
			$mtz[] = '<-' . $o->small('Chave') . '<br><b>' . $row['chave'] . '</b>&nbsp;<br/>';
			$mtz[] = '<-' . $o->small('Série') . '<br><b>' . $row['serie'] . "</b>&nbsp;";
			$mtz[] = '<-' . $o->small('Protocolo') . '<br><b>'.$row["protocolo"] . "</b>&nbsp;";
			$html .= $o->tableRow($mtz, 'header');
		}

		if ($this->tipo == 'S') {
			$mensagemCertificado = $this->verificarVencimentoCertificado();
			if ($mensagemCertificado) {
				$colspan = count($mtz);
				$mtz = [];
				$mtz[] = '~' . $colspan . $mensagemCertificado['descricao'];
				$html .= $o->tableRow($mtz, $mensagemCertificado['tipo']);
			}
		}

		$html .= $o->tableEnd();
		return $html;
	}


	public function obtemCabecalho($row)
	{
		global $o,$gPage, $gParam, $gId;
		if ($row) {
			$html .= $this->obtemDetalhesCabecalho($row);
		} else {
			$html.=$o->msgError("Erro ao localizar o item. Pode ter sido excluído de forma inapropriada.");
		}

		switch ($_REQUEST['gPage']) {
			case DADOS:
				$btn0=true;
			break;
			case ITENS:
				$btn1=true;
			break;
			case NFE:
				$btn4=true;
			break;
		}
		$html .= $o->button("{title: Dados; active: ".($btn0 ? "true" : "false").";icon: clock; href: ".$o->page."&gPage=".DADOS."&gId=".$gId." }");
		$html .= $o->button("title: Itens; active: ".($btn1 ? "true" : "false").";icon: tasks; href: ".$o->page."&gPage=".ITENS."&gId=".$gId." }");

		if ($this->tipo == 'S') {
			$html .= $o->button("{title: NF-e; active: ".($btn4 ? "true" : "false")."; href: ".$o->page."&gPage=".NFE ."&gId=".$gId." }");
		}

		if ($_REQUEST['importado']) {
            $html .= $o->msgSuccess('Itens importados com sucesso');
        }

		return ($html);
	}
	/**
	* Obtem a tabela principal com os registros de notas.
	* @param  Array  $rs   Array com notas fiscais.
	* @return String $html  string com código html da tabela principal.
	*/
	function obtemTabelaPrincipal($rs)
	{
		global $o, $gParam, $usrId;

		$js="function cancelarNFe(id)
		{
			$('#modalCancelarNFE').modal('show');
			$('#id_nfe').val(id);
		}

		function btnConfirmarCancelarNFE()
		{
			showWait();
			let id_nfe=$('#id_nfe').val();
			let rota='".$o->page."&gPage=2&gId='+id_nfe;
			location.href=rota;
		}

		function confirmarAtualizacaoCfop(idNfe)
        {
            hideWait();
            bootbox.confirm({
                message: 'Confirma o desmembramento desta nota por CFOP de entrada ?',
                buttons: {
                    confirm: {
                        label: 'Sim',
                        className: 'btn-success'
                    },
                    cancel: {
                        label: 'Não',
                        className: 'btn-danger'
                    }
                },
                callback: function (resposta) {
                    if (resposta) {
                        atualizarCfopItem(idNfe);
                    }
                }
            });
        }

        function atualizarCfopItem(idNfe) {
            let rota = '".$o->page."&gPage=".ITENS_ATUALIZAR_CFOP_ORIGEM."&gId='+idNfe;
            location.href = rota;
        }

        function confirmarCancelamentoAgrupamento(idNota)
        {
            hideWait();
            bootbox.confirm({
                message: 'Confirma o cancelamento de agrupamento desta nota?',
                buttons: {
                    confirm: {
                        label: 'Sim',
                        className: 'btn-success'
                    },
                    cancel: {
                        label: 'Não',
                        className: 'btn-danger'
                    }
                },
                callback: function (resposta) {
                    if (resposta) {
                        removerAgrupamento(idNota);
                    }
                }
            });
        }

		";
		$o->addJavascript($js);

		if (!$rs) {
			$html .= $o->msgInfo("Nenhum registro encontrado");
			return $html;
		}

		$html .= $o->tableBegin("big",true);
		$mtz = [];
		$mtz[] = "<-Opções";
		$mtz[] = "<-Id";
		$mtz[] = "<-Número";
		$mtz[] = "<-Tipo";
		$mtz[] = "<-Proprietário";
		$mtz[] = "<-CFOP";
		$mtz[] = "<>Data de emissão";
		$mtz[] = "<>Data de movimento";
		$mtz[] = "<>Cadastro";
		$html .= $o->tableRow($mtz,"header");

		$tiposNota = array(
			'E'  => 'Entrada',
			'S'  => 'Saída'
		);

	 	//Verificar se existe uma nota aprovada.
		foreach ($rs as $row) {
			$mtz = [];
			$btns = $o->button("{icon: folder-open; caption: Abrir; hint: Abrir; style: default; size: small; href: ". $o->page ."&gPage=1&gId=". $row["id"] ."}");
			if (!$row['cancelada']) {
				if (
					$gParam["PERMITIR_CANCELAR_NOTA_SAIDA"]["ativo"] == 1
					|| (
						$gParam["PERMITIR_CANCELAR_NOTA_SAIDA"]["ativo"] == 0
						&& in_array($usrId, array(1, 2))
					)
				) {
					$btns .= $o->button("{icon: trash; caption:; hint: Cancelar espelho da nota; style: danger; size: small;}", "javascript:cancelarNFe(". $row["id"] .")");
				}
			}

			if ($row['id_nfe']) {
				if ($row['situacao'] != 'Reprovada') {
					$btns .= $o->button("{target:_blank; icon: print;  hint: Imprimir danfe; style: success; size: small; href: ". $o->page ."&gAjax=1&gId=" . $row['id_nfe'] . "&gerarDanfe=1}");
				}
			} else {
				if ($gParam['PERMITIR_DESMEMBRAMENTO_NOTA_SAIDA']['ativo']) {
					$btns .= $o->button("{icon: copy; size: small; hint: Desmembrar notas por CFOP de entrada; color: info; onClick:confirmarAtualizacaoCfop({$row['id']});}");
				}
			}

			$siglaTipo = $row['tipo'];

			$mtz[] = "<-" . $btns;
			$mtz[] = "<-" . $row['id'];
			$mtz[] = "<-" . $row['numero'];
			$mtz[] = "<-" . $tiposNota[$siglaTipo];
			$mtz[] = "<-" . $row['nome_cliente'];
			$mtz[] = "<-" . $row['codigo_cfops'];
			$mtz[] = "<>" . gDate($row["data_emissao"]);
			$mtz[] = "<>" . gDate($row["data_movimento"]);
			$mtz[] = "<>" . gDateTime($row['data_criou']) . "<br>" . $o->small($row["nome_criou"]);
			$corLinha = 'detail';

			if ($row['cancelada']) {
				$corLinha = 'text-danger';
			}

			$html .= $o->tableRow($mtz, $corLinha);
		}

		$html .= $o->tableEnd();

		return ($html);
	}


	/**
	* Obtem a tabela principal com os registros de notas.
	* @param  Array  $rs   Array com itens de uma nota fiscal.
	* @return String $html  string com código html da tabela de itens da nota fiscal.
	*/
	function obtemTabelaItem(
		$rs,
		$exibirOpcoes   = true,
		$exibirSubtotal = false,
		$exibirHeader   = true
	) {
		global $o, $gId, $gIdEnd, $gParam, $usrId;

		$sql = "SELECT N.id_pessoas_proprietario, N.id, N.id_cfops, C.codigo codigo_cfops, N.cancelada, N.refNfe ref
				FROM notas N
				LEFT JOIN cfops C ON C.id = N.id_cfops
				WHERE N.id = " . $rs[0]["id_notas"];
		$nota   = (dbQuery($sql)[0]);
		$idNota = $rs[0]["id_notas"];

		$html   = $o->msgFilter("Itens da nota");
		$html  .= $o->tableBegin("big",true, true);

		$mtz=[];
		$cnt = 0;
		if ($exibirOpcoes) {
			$mtz[] = "<-Opções";
		}
		$mtz[] = "->Nº";
		if ($this->tipo=="S") {
			$mtz[] = "<-NF Ent.";
			$mtz[] = "CFOP E.";
			$mtz[] = "CFOP S.";
		}
		$mtz[] = "<-Código";
		$mtz[] = "<-SKU";
		$mtz[] = "<>Apto";
		$mtz[] = "<>Ativo";
		$mtz[] = "<-NCM";
		$mtz[] = "<-Item";
		$mtz[] = "<-Unidade";
		$mtz[] = "<-Lote";
		$mtz[] = "->Quantidade";
		$mtz[] = "->Valor UN";
		$mtz[] = "->Valor TTL";
		$mtz[] = "->Peso Bruto";
		$mtz[] = "->Peso Líquido";

		$mostrarCfopCombustivel = in_array($nota["codigo_cfops"], $this->cfopsCombustivel);
		if ($mostrarCfopCombustivel) {
			$mtz[] = "<-Cód. ANP";
			$mtz[] = "<-Desc. ANP";
			$mtz[] = "<-CODIF";
		}

		if ($this->tipo == "S") {
			$mtz[] = "->ICMS";
			$mtz[] = "->IPI";
			$mtz[] = "->PIS";
			$mtz[] = "->COFINS";
			if ($rs[0]['id']) {
				$sql = "
					SELECT
						notas_itens.id AS id_notas_itens,
						notas_itens_icms.id AS id_icms,
						((notas_itens_icms.vBC) * (notas_itens_icms.pICMS / 100)) AS icms,
						notas_itens_ipi.id AS id_ipi,
						((notas_itens_ipi.vBC * notas_itens.quantidade) * (notas_itens_ipi.pIPI / 100)) AS ipi,
						notas_itens_pis.id AS id_pis,
						((notas_itens_pis.vBC * notas_itens.quantidade) * (notas_itens_pis.pPIS / 100)) AS pis,
						notas_itens_cofins.id AS id_cofins,
						((notas_itens_cofins.vBC * notas_itens.quantidade) * (notas_itens_cofins.pCOFINS / 100)) AS cofins
					FROM
						notas_itens
					LEFT JOIN notas_itens_icms ON
						notas_itens_icms.id_notas_itens = notas_itens.id
					LEFT JOIN notas_itens_ipi ON
						notas_itens_ipi.id_notas_itens = notas_itens.id
					LEFT JOIN notas_itens_pis ON
						notas_itens_pis.id_notas_itens = notas_itens.id
					LEFT JOIN notas_itens_cofins ON
						notas_itens_cofins.id_notas_itens = notas_itens.id
					WHERE
						notas_itens.id IN(" . implode(',', array_column($rs, 'id')) . ")
					ORDER BY
						notas_itens.id,
						notas_itens_icms.id DESC,
						notas_itens_ipi.id DESC,
						notas_itens_pis.id DESC,
						notas_itens_cofins.id DESC";
				$rsImposto = dbFastQuery($sql);
				$imposto = [];
				foreach ($rsImposto as $row) {
					if ($imposto[$row['id_notas_itens']]) {
						continue;
					}

					$imposto[$row['id_notas_itens']] = $row;
				}
			}
		}

		$a = '<div id="impostosmodal_content">Carregando dados...</div>';
		$html .= $o->modal("{title: Impostos ; cancelCaption: Fechar; confirm: false; name: modalImpostos; size:big; }",$a);
		$o->addJavascript($js);
		$this->carregarBotoesImposto();

		$tQuantidade = 0;
		$tPesoLiquido = 0;
		$tPesoBruto = 0;
		$tValorUnitario = 0;
		$tValorTotal = 0;

		$colspan = count($mtz);
		$html .= $o->tableRow($mtz,"header");
		$a = '<div id="modalMedicamento_content">Carregando dados...</div>';
		$html .= $o->modal("{title: Medicamentos ; cancelCaption: Fechar; confirm: false; name: modalMedicamento; size:big; }",$a);
		$o->addJavascript($js);

		$tQuantidade = 0;
		$tPesoLiquido = 0;
		$tPesoBruto = 0;
		$tValorUnitario = 0;
		$tValorTotal = 0;

		if ($gParam['INSERE_REFNFE_AUTO']['ativo']) {
			$refnfe = [];
		}

		foreach ($rs as $row) {
			$valorTL = ($row["valor"]*$row["quantidade"]);
			$pesoBrutoTL = ($row["peso_bruto"]*$row["quantidade"]);
			$pesoLiquidoTL = ($row["peso_liquido"]*$row["quantidade"]);
			$tQuantidade += $row["quantidade"];
			$tPesoLiquido += $pesoLiquidoTL;
			$tPesoBruto += $pesoBrutoTL;
			$tValorUnitario += $row["valor"];
			$tValorTL += $valorTL;
			$cnt++;
			$mtz = [];

			// $btnImpostos = $o->button("{icon: coins; caption: ; hint: Informações de tributação; style: success; size: small;openModal:modalImpostos}",'modalImpostos('.$row['id'].', '. $gId .')');
			$btnImpostos = $o->button("{icon: coins; caption: ; hint: Informações de tributação; style: success; size: small; openModal: modalImpostos}", "modalImpostos(".$row['id'].", ". $gId .", '".$row['situacao']."')");

			$btnMedicamento = '';
			if ($gParam['MEDICAMENTOS_EM_NOTA_FISCAL']['ativo']) {
				$btnMedicamento = $o->button("{icon: ambulance;caption:; hint:Informações do medicamento; style: danger; size: small;openModal:modalMedicamento}",'modalMedicamento(' . $row['id'] . ', ' . $gId . ')');
			}

			if ($row["tipo"] == "S") {
				if (in_array($row["situacao"], array('Aprovada', 'Cancelada'))) {
					$btns = $o->button("{icon: info; size: small; onClick: hideWait(); hint: Item de nota fiscal cancelada ou aprovada não pode ser editado;}") . $btnImpostos . $btnMedicamento;
				} else {
					$btnsEdicaoExclusao = $o->button("{icon: pencil; caption:; hint: Editar nota fiscal; style: default; size: small; href: ". $o->page ."&gPage=60&gId=". $gId ."&gIdEnd=". $row['id'] ."}");
					$btnsEdicaoExclusao .= $o->button("{name: btnExcluirNotaItem; icon: trash; caption:; hint: Excluir item da nota fiscal; style: danger; size: small;}", "javascript:btnExcluirItemNota(". $row["id"] .")");
					$btns = $btnsEdicaoExclusao . $btnImpostos . $btnMedicamento;
				}

				if ($exibirOpcoes) {
					$mtz[] = "<-" . $btns;
				}
			} else {
				$btns = "";
				if (in_array($row["situacao"], array('Aprovada', 'Cancelada'))) {
					$btns = $o->button("{icon: info; size: small; onClick: hideWait(); hint: Item de nota fiscal cancelada ou aprovada não pode ser editado;}") . $btnImpostos . $btnMedicamento;
				} else {
					$btnsEdicaoExclusao = $o->button("{icon: pencil; caption:; hint: Editar nota fiscal; style: default; size: small; href: ". $o->page ."&gPage=60&gId=". $gId ."&gIdEnd=". $row['id'] ."}");
					$btnsEdicaoExclusao .= $o->button("{name: btnExcluirNotaItem; icon: trash; caption:; hint: Excluir item da nota fiscal; style: danger; size: small;}", "javascript:btnExcluirItemNota(". $row["id"] .")");
					$btns = $btnsEdicaoExclusao . $btnImpostos . $btnMedicamento;
				}

				if ($exibirOpcoes) {
					$mtz[]="<-".$btns;
				}
			}

			if ($_SESSION['usrId'] == 1) {
				$mtz[]="->" . $cnt . '<br>' . $o->small('ID NI: ' . $row['id']);
			} else {
				$mtz[]="->" . $cnt;
			}

			if ($this->tipo == "S") {
				$mtz[]="<-".$row['nota_associada'];
				$mtz[]="".$row['cfop_entrada'];
				$mtz[]="".$row['cfop_saida'];
				if ($gParam['INSERE_REFNFE_AUTO']['ativo']) {
					$refnfe[] = $row['chave_nota_associada'];
				}
			}

			$idSkuMostrar = "";
			$idItemMostrar = "";
			if ($usrId == 1) {
				$idSkuMostrar = "<br>" . $o->small("ID SK: " . $row['id_itens_skus']);
				$idItemMostrar = "<br>" . $o->small("ID I: " . $row['id']);
			}

			$mtz[] = "<-" . '<a target="_blank" href="index.php?g=itens&gPage=20&gId=' . $row['id_itens'] . '&gIdd=' . $row['id_itens_skus'] . '">' . $row["codigo"] . '</a>' . $idItemMostrar;
			$mtz[] = "<-" . '<a target="_blank" href="index.php?g=itens&gPage=20&gId=' . $row['id_itens'] . '&gIdd=' . $row['id_itens_skus'] . '">' . $row["codigo_sku"] . '</a>' . $idSkuMostrar;
			$mtz[]="<>" . gCheck($row['apto']);
			$mtz[]="<>" . gCheck($row['ativo_sku']);
			$mtz[]="<-" . $row["ncm"];
			$mtz[]="<-" . $row["descricao"];
			$mtz[]="<-" . $row["sigla"];
			$mtz[]="<-" . $row["lote"];

			$mtz[]="->".number_format($row["quantidade"],4,",",".");
			$mtz[]="->".number_format($row["valor"],10,",",".");
			$mtz[]="->".number_format($valorTL,2,",",".");
			$mtz[]="->" . gFloat($row["peso_bruto"]);
			$mtz[]="->" . gFloat($row["peso_liquido"]);

			if ($mostrarCfopCombustivel) {
				$mtz[] = "<-" . $row["cProdANP"];
				$mtz[] = "<-" . $row["descANP"];
				$mtz[] = "<-" . $row["CODIF"];
			}

			if ($this->tipo == "S") {
				$mtz[] = "-> &nbsp;" . gFloat($imposto[$row['id']]['icms']);
				$mtz[] = "-> &nbsp;" . gFloat($imposto[$row['id']]['ipi']);
				$mtz[] = "-> &nbsp;" . gFloat($imposto[$row['id']]['pis']);
				$mtz[] = "-> &nbsp;" . gFloat($imposto[$row['id']]['cofins']);
			}

			if ($row["id"] == $gIdEnd) {
				$html .= $o->tableRow($mtz, "success");
			} else {
				$html .= $o->tableRow($mtz, "detail");
			}
		}

		$colspan = count($mtz);

		if ($exibirSubtotal) {
			$mtz = [];
			$mtz[]="~" . ($colspan - 9) . "-><b>Total:</b>";
			$mtz[]="->".gFloat($tQuantidade);
			$mtz[]="->".gFloat($tValorUnitario);
			$mtz[]="->".gFloat($tValorTL);
			$mtz[]="->".gFloat($tPesoBruto);
			$mtz[]="->".gFloat($tPesoLiquido);
			if (in_array($nota["codigo_cfops"], $this->cfopsCombustivel)) {
				$mtz[]="--";
				$mtz[]="--";
				$mtz[]="--";
			}

			if ($this->tipo=="S"){
				$mtz[]="-> &nbsp;";
				$mtz[]="->&nbsp;";
				$mtz[]="->&nbsp;";
				$mtz[]="->&nbsp;";
			}
				$html.=$o->tableRow($mtz, "footer");
		}
		$html.=$o->tableEnd();

		/* ESCONDENDO E BLOQUEANDO BOTÕES EM CASO DE NOTA FISCAL IMPORTADA */
		if (
			$gParam['INSERE_REFNFE_AUTO']['ativo']
			&& $this->tipo == "S"
		) {
			$refnfe = implode(',', array_unique($refnfe));
			$js = '
				if (document.querySelector("#refNfeOriginal").value == "") {
					document.querySelector("#refNfeOriginal").value = "' . $refnfe . '";
				}
			';

			if (!$nota['ref']) {
				$js .= '
					if (document.querySelector("#refNfe").value == "") {
						document.querySelector("#refNfe").value = "' . $refnfe . '";
					}
				';
			}
			$o->addJavascript($js);
		}
		return ($html);
	}


	public function obterTabelaInformacoesOperacao($idNota)
	{
		global $o;

        $sql = "SELECT
					nfe_eventos.id,
					nfe_eventos.id_pessoas,
					nfe_eventos.id_nfe,
					nfe_eventos.id_nfe_tipos_eventos,
					nfe_eventos.protocolo,
					nfe_eventos.data,
					nfe_eventos.motivo,
					nfe_eventos.retorno_mensagem,
					nfe_eventos.sucesso,
					nfe_eventos.xml,
					nfe_eventos.sequencial,
					nfe_tipos_eventos.descricao,
					nfe.chave,
					nfe.modo_operacao,
					pessoas.apelido
				FROM nfe_eventos
				LEFT JOIN nfe_tipos_eventos ON nfe_eventos.id_nfe_tipos_eventos = nfe_tipos_eventos.id
				LEFT JOIN nfe ON nfe.id = nfe_eventos.id_nfe
				LEFT JOIN pessoas ON nfe_eventos.id_pessoas = pessoas.id
				WHERE nfe_eventos.id_notas_saida = {$idNota}
				ORDER BY nfe_eventos.id DESC";
        $rs = dbFastQuery($sql);

		if (!$rs) {
            return false;
        }

		$html = $o->msgFilter("Eventos e serviços");
		$html .= $o->tableBegin("big", true);

		$mtz = [];
		$mtz[] = "<-Opções";
		$mtz[] = "->Id";
		$mtz[] = "<-Chave";
		$mtz[] = "<>Data";
		$mtz[] = "<-Operação";
		$mtz[] = "<-Colaborador";
		$mtz[] = "<-Protocolo";
		$mtz[] = "<-Observações";
		$mtz[] = "<>Motivo";
		$mtz[] = "<-Sucesso";
		$html .= $o->tableRow($mtz, "header");

		$sql = "SELECT
					estorno.id AS nota_estorno,
					origem.id_notas_origem_estorno AS nota_origem_estorno
				FROM notas origem
				LEFT JOIN notas estorno ON estorno.id_notas_origem_estorno = origem.id AND estorno.cancelada = 0
				WHERE origem.id = {$idNota} AND origem.cancelada = 0";
		$resultado = dbFastQuery($sql)[0];

		$html .= obtemModalConfirmacao("confirmarEnviarEmail", "Você deseja realmente reenviar um email para o cliente?", "btnCancelarEnviar", "btnConfirmarEnviar", $o->page."&gPage=" . NFE_REENVIAR_EMAIL);
		foreach ($rs as $row) {
			$botoes = '';

			// Emissão
			if ($row['id_nfe_tipos_eventos'] == 1 && $row['sucesso']) {
                $botoes .= $o->button("{icon:envelope; size:small; hint: Reenviar email; color:info;}", "javascript:opemModal(" . $row['id_nfe'] . ", 'confirmarEnviarEmail');");
				$botoes .= $o->button("{icon: file-code; hint: Baixar xml da NF-e; style: info; size: small;}", "javascript: btnObterXml(".$row['id_nfe'].")");
				$botoes .= $o->button("{icon: file-pdf; hint: Gerar danfe da NF-e; style: success; size: small;}", "javascript: btnImprimirDanfe(".$row['id_nfe'].")");
			} elseif ($row['id_nfe_tipos_eventos'] == 2 && $row['sucesso']) { // Cancelamento
				$botoes .= $o->button("{icon: file-code; hint: Baixar XML; target: _blank; size: small; style: info; href: " . $o->page . "&gPage=" . NFE_OBTER_XML_CANCELAMENTO . "&gId=" . $row['id_nfe'] . ";}");
				$botoes .= $o->button("{icon: file-pdf; target: _blank; hint: Gerar danfe da NF-e; style: success; size: small; href: " . $o->page . "&gAjax=1&gId=" . $row['id_nfe'] . "&gerarDanfe=1&cancelamento=1;})");
			} elseif ($row['id_nfe_tipos_eventos'] == 3 && $row['sucesso']) { // Carta de Correção
				$botoes .= $o->button("{icon: file-code; hint: Baixar XML; target: _blank; size: small; style: info; href:" . $o->page . "&gPage=" . NFE_OBTER_XML_CARTA_CORRECAO . "&gId=" . $row['id'] . "}");
				$botoes .= $o->button("{icon: file-pdf; hint: Abrir PDF; size: small;" . "style: success; onClick: javascript:btnImprimirCCe(" . $row['id_nfe'] . ", " . $row['id'] . ")}");
			} else if ($row['id_nfe_tipos_eventos'] == 5 && $row['sucesso']) { // Estorno
				$botoes .= $o->button("{icon: file-code; hint: Baixar xml da NF-e; style: info; size: small;}", "javascript: btnObterXml(".$row['id_nfe'].")");
				$botoes .= $o->button("{icon: file-pdf; hint: Gerar danfe da NF-e; style: success; size: small;}", "javascript: btnImprimirDanfe(".$row['id_nfe'].")");
			}

			$mtz = [];
			$mtz[] = "<- " . $botoes;
			$mtz[] = "-> " . $row['id'];
			$mtz[] = "<-" . $row['chave'];
			$mtz[] = "<>" . gDateTime($row["data"]);
			$mtz[] = "<-" . $row['descricao'];
			$mtz[] = "<- " . $row['apelido'];
			$mtz[] = "<- " . $row['protocolo'];

			$mensagem = "<- " . $row['retorno_mensagem'];
			if ($row['id_nfe_tipos_eventos'] == 5) {
				$nota = $resultado['nota_estorno'] ?: $resultado['nota_origem_estorno'];
				$tipo = $resultado['nota_estorno'] ? "estorno" : "origem do estorno";
				$mensagem .= "<br> Nota de {$tipo}: " . linkParaNota($nota);
			}

			if ($row['modo_operacao'] == 7) {
				$mensagem .= "<br> Emitido em contingencia";
			}

			$mtz[] = $mensagem;

			$mtz[] = "<- " . $row['motivo'];
			$mtz[] = "<> " . gCheck($row['sucesso']);
			$html .= $o->tableRow($mtz);
		}
		$html .= $o->tableEnd();
		$html .= "<hr/>";
		return ($html);
	}


	public function carregarBotoesImposto()
	{
		global $o;

		$js = '
			function atualizarCclasstrib(cstCodigo)
			{
				const regrasCclasstribPorCST = {
					"000": ["000001", "000002", "000003", "000004"],
					"010": ["010001", "010002"],
					"011": ["011001", "011002", "011003", "011004", "011005"],
					"200": [
						"200001","200002","200003","200004","200005","200006","200007","200008","200009","200010",
						"200011","200012","200013","200014","200015","200016","200017","200018","200019","200020",
						"200021","200022","200023","200024","200025","200026","200027","200028","200029","200030",
						"200031","200032","200033","200034","200035","200036","200037","200038","200039","200040",
						"200041","200042","200043","200044","200045","200046","200047","200048","200049","200050",
						"200051","200052"
					],
					"220": ["220001", "220002", "220003"],
					"221": ["221001"],
					"222": ["222001"],
					"400": ["400001"],
					"410": [
						"410001","410002","410003","410004","410005","410006","410007","410008","410009","410010",
						"410011","410012","410013","410014","410015","410016","410017","410018","410019","410020",
						"410021","410022","410023","410024","410025","410026","410027","410028","410029","410030",
						"410031","410999"
					],
					"510": ["510001"],
					"515": ["515001"],
					"550": [
						"550001","550002","550003","550004","550005","550006","550007","550008","550009","550010",
						"550011","550012","550013","550014","550015","550016","550017","550018","550019","550020",
						"550021"
					],
					"620": ["620001","620002","620003","620004","620005","620006"],
					"800": ["800001","800002"],
					"810": ["810001"],
					"811": ["811001","811002","811003"],
					"820": [
						"820001","820002","820003","820004","820005","820006","820007","820008"
					],
					"830": ["830001"]
				};


				const select = document.getElementById("id_cclasstrib_ibs_cbs");
				if (!select) {
					return;
				}

				if (!window.opcoesOriginaisCclasstrib) {
					window.opcoesOriginaisCclasstrib = Array.from(select.options).map(opt => ({
						value: opt.value,
						text: opt.text
					}));
				}

				const permitidos = regrasCclasstribPorCST[cstCodigo] || [];

				select.innerHTML = "";

				window.opcoesOriginaisCclasstrib.forEach(opt => {
					const codigo = opt.text.split(" ")[0].trim();
					if (permitidos.includes(codigo)) {
						const option = document.createElement("option");
						option.value = opt.value;
						option.textContent = opt.text;
						select.appendChild(option);
					}
				});

				const valorAtual = select.getAttribute("data-current");
				if (valorAtual) {
					select.value = valorAtual;
				}
			}
		';
		$o->addJavascript($js);

		$js = "

	    	function btn_aguardar(self)
			{
                self.setAttribute('disabled', 'disabled');
                setTimeout(function () {
                    self.removeAttribute('disabled');
                }, 5000);
            }


        	function modalImpostos(id, idNota, situacao)
        	{
                showWait();
                $.ajax({
                    url: '".$o->page."&gAjs=1&gPage=" . IMPOSTOS . "&gId='+ id,
                    type: 'GET',
                    success: function(data){
                        hideWait();
                        $('#impostosmodal_content').html(data);

                        if (situacao == 'Aprovada') {
                            setTimeout(function() {
                                var formsImpostos = [\"#formICMS\", \"#formIPI\", \"#formPIS\", \"#formCOFINS\", \"#formIbsCbs\"];

                                $.each(formsImpostos, function(index, idForm) {
                                    $(idForm).find(\".btn, button, input[type='submit'], .form-actions, .g-form-footer\").remove();
                                });

                                $(\"input[name='replicar']\").closest(\"div\").prev(\"label\").remove();
                                $(\"input[name='replicar']\").closest(\"div\").remove();

								$(\"input[name='indDoacao']\").closest(\"div\").prev(\"label\").remove();
                                $(\"input[name='indDoacao']\").closest(\"div\").remove();

                            });
                        }

                        $('#icms_cst').off('change').on('change', function() {
                            atualizarCamposICMS();
                        });
                        atualizarCamposICMS();

                        $('#id_imp_ipi_cst').off('change').on('change', function() {
                            atualizarCamposIPI();
                        });
                        atualizarCamposIPI();

                        $('#id_imp_pis_cst').off('change').on('change', function() {
                            atualizarCamposPIS();
                        });
                        atualizarCamposPIS();

                        $('#id_imp_cofins_cst').off('change').on('change', function() {
                            atualizarCamposCOFINS();
                        });
                        atualizarCamposCOFINS();

                        $(document).ready(function() {
                            $('#id_imp_ibs_cbs_cst').on('change', function() {
                                atualizarCamposIBSCBS();
                            });
                            atualizarCamposIBSCBS();
                        });
                    },
                    error: function(){
                        hideWait();
                        $('#impostosmodal_content').html('Erro ao carregar dados. Tente novamente mais tarde');
                    }
                });
            }


			function modalMedicamento(id_notas_itens,id_notas)
			{
                showWait();
                $.ajax({
                    url: '".$o->page."&gAjs=1&gPage=" . MEDICAMENTO . "&id_notas='+ id_notas+'&id_notas_itens='+id_notas_itens,
                    type: 'GET',
                    success: function(data){
                        hideWait();
                        document.querySelector('#modalMedicamento_content').innerHTML = data;
                    },
                    error: function(){
                        hideWait();
                        document.querySelector('#modalMedicamento_content').innerHTML = 'Erro ao carregar dados. Tente novamente mais tarde';
                    }
                });
            }


           	function atualizarCamposICMS()
			{
				if ($('#icms_cst').length === 0) return;

				let textoOpcao = $('#icms_cst option:selected').text();
				let codigoCompleto = textoOpcao.split(' ')[0].trim();
				let finalCst = codigoCompleto.slice(-2);

				let camposBase     = ['vBC', 'icms_aliquota'];
				let camposReducao  = ['reducao_icms_aliquota'];
				let camposST       = ['modBCST', 'pMVAST', 'pRedBCST', 'vBCST', 'pICMSST', 'vICMSST', 'vBCFCP', 'pFCP', 'vBCFCPST', 'pFCPST'];
				let camposRetencao = ['vBCSTRet', 'vICMSSTRet'];

				let todos = [].concat(camposBase, camposReducao, camposST, camposRetencao);

				let camposParaExibir = [];

				// GRUPO NORMAL (00)
				if (finalCst === '00') {
					camposParaExibir = camposBase;
				}
				// GRUPO MISTO (10, 70, 90) -> Base + Redução + ST
				else if (['10', '70', '90'].includes(finalCst)) {
					camposParaExibir = [].concat(camposBase, camposReducao, camposST);
					$('label[for=\"pICMSST\"]').text('Alíquota do imposto do ICMS ST');
				}
				// GRUPO REDUÇÃO / DIFERIMENTO (20, 51) -> Base + Redução
				else if (['20', '51'].includes(finalCst)) {
					camposParaExibir = [].concat(camposBase, camposReducao);
				}
				// GRUPO ST PURA (30) -> Apenas ST
				else if (finalCst === '30') {
					camposParaExibir = camposST;
					$('label[for=\"pICMSST\"]').text('Alíquota do imposto do ICMS ST');
				}
				// GRUPO RETENÇÃO (60)
				else if (finalCst === '60') {
					camposParaExibir = camposRetencao;
					$('label[for=\"pICMSST\"]').text('Alíquota suportada pelo Consumidor Final');
				}

				todos.forEach(function(id) {
					let campo = $('[name=' + id + ']');
					let grupo = campo.closest('.form-group');

					if (camposParaExibir.indexOf(id) !== -1) {
						grupo.show();
					} else {
						campo.val('0');
						grupo.hide();
					}
				});
			}


            function mostrarGrupo(ids)
			{
                ids.forEach(function(id) {
                    $('[name=' + id + ']').closest('.form-group').show();
                });
            }


			function btnICMS(self)
			{
                showWait();
                btn_aguardar(self);
                let liberar = document.getElementById('icms_liberar').value;
                let replicar = Number($('#replicar').is(':checked'));

                if (liberar == 0) {
                    let obj = {};
                    obj.icms_cst = document.getElementById('icms_cst').value;
                    obj.icms_orig = document.getElementById('icms_orig').value;
                    obj.icms_mod = document.getElementById('icms_mod').value;
                    obj.replicar = replicar;

                    obj.vBC = getFieldValue('vBC', 0);
                    obj.icms_aliquota = getFieldValue('icms_aliquota', 0);
                    obj.reducao_icms_aliquota = getFieldValue('reducao_icms_aliquota', 0);

                    obj.modBCST = getFieldValue('modBCST', 0);
                    obj.pMVAST = getFieldValue('pMVAST', 0);
                    obj.pRedBCST = getFieldValue('pRedBCST', 0);
                    obj.vBCST = getFieldValue('vBCST', 0);
                    obj.pICMSST = getFieldValue('pICMSST', 0);
                    obj.vICMSST = getFieldValue('vICMSST', 0);

                    obj.vBCFCP = getFieldValue('vBCFCP', 0);
                    obj.pFCP = getFieldValue('pFCP', 0);
                    obj.vBCFCPST = getFieldValue('vBCFCPST', 0);
                    obj.pFCPST = getFieldValue('pFCPST', 0);

                    obj.vBCSTRet = getFieldValue('vBCSTRet', 0);
                    obj.vICMSSTRet = getFieldValue('vICMSSTRet', 0);

                    if (document.getElementById('id_notas_itens_icms')) obj.id_notas_itens_icms=document.getElementById('id_notas_itens_icms').value;
                    obj.gIdItem=document.getElementById('gIdItem').value;

                    $.ajax({
                        method: 'POST',
                        url: '".$o->page."&gAjs=1&gPage=".IMPOSTOS_SALVAR."&cmd=salvar',
                        data:obj,
                        success: function (resp) {
                            hideWait();
                            $('.respostas').html('');

                            let respString = String(resp).trim();
                            let idLimpo = respString.replace(/\"/g, '');

                            if (resp && !isNaN(idLimpo)) {
                                $('#formICMS').prepend('<div class=\"respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-success\">Dados ICMS salvos com sucesso !</div></div>');

								if(document.getElementById('id_notas_itens_icms') && document.getElementById('id_notas_itens_icms').value == 0) {
                                     document.getElementById('id_notas_itens_icms').value = idLimpo;
                                }
                            } else {
                                $('#formICMS').prepend('<div class=\"respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Houve um erro ao salvar os dados do ICMS!</div></div>');
                            }
                        },
                        error: function (resp) {
                            $('.respostas').html('');
                            $('#formICMS').prepend('<div class=\"respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Houve um erro ao salvar os dados do ICMS!</div></div>');
                        }
                    });
                } else {
                    hideWait();
                    let situacao = document.getElementById('icms_situacao').value;
                    $('#formICMS').prepend('<div class=\"respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Não foi possível atualizar o ICMS a nota esta <b>'+situacao.toLowerCase()+'</b> e portando não pode ser alterada.</div></div>');
                }
            }


			function atualizarCamposIPI() {
				if ($('#id_imp_ipi_cst').length === 0) return;

				let textoOpcao = $('#id_imp_ipi_cst option:selected').text();
				let codigoCst = textoOpcao.split(' ')[0].trim();

				let cstComAliquota = ['00', '49', '50', '99'];

				let campoEnq = $('[name=\"ipi_cEnq\"]');
				let groupEnq = campoEnq.closest('.form-group');

				let campoAliq = $('[name=\"ipi_pIPI\"]');
				let groupAliq = campoAliq.closest('.form-group');

				if (codigoCst === '*') {
					campoEnq.val('0');
					campoAliq.val('0');

					campoEnq.prop('required', false);
					campoAliq.prop('required', false);

					groupEnq.hide();
					groupAliq.hide();

					groupEnq.find('.required').remove();
					groupAliq.find('.required').remove();
				} else {
					groupEnq.show();
					campoEnq.prop('required', true);

					if (groupEnq.find('label .required').length === 0) {
						groupEnq.find('label').append(' <span class=\"required\">*</span>');
					}

					if (cstComAliquota.includes(codigoCst)) {
						groupAliq.show();
					} else {
						campoAliq.val('0');
						campoAliq.prop('required', false);
						groupAliq.hide();
					}
				}
			}


           	function btnIPI(self)
			{
                showWait();
                btn_aguardar(self);
                let liberar = document.getElementById('ipi_liberar').value;
                let replicar = Number($('#replicar').is(':checked'));

                if (liberar == 0) {
					let pIPI = getFieldValue('ipi_pIPI', 0);
					let cEnq = getFieldValue('ipi_cEnq', 0);
					let cstSelecionado = $('#id_imp_ipi_cst option:selected').text();
					let codigoCst = cstSelecionado.split(' ')[0].trim();
					let cstTributados = ['00', '49', '50', '99'];

					if (codigoCst !== '*' && (!cEnq || cEnq == '0')) {
						hideWait();
						$('.ipi_respostas').html('');
						$('#formIPI').prepend('<div class=\"ipi_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-warning\">O campo <b>Enquadramento fiscal</b> é obrigatório para o CST selecionado!</div></div>');
						$('[name=ipi_cEnq]').focus();
						return false;
					}

                    $.ajax({
                        method: 'POST',
                        url: '".$o->page."&gAjs=1&gPage=" . IMPOSTOS_SALVAR . "&cmd=salvarIPI',
                        data: {
                            id_imp_ipi_cst: document.getElementById('id_imp_ipi_cst').value,
                            pIPI: pIPI,
                            ipi_cEnq: cEnq,
                            gIdItem: document.getElementById('ipi_gIdItem').value,
                            id_notas_itens_ipi: document.getElementById('id_notas_itens_ipi').value,
                            replicar: replicar
                        },
                        success: function (resp) {
                            hideWait();
                            $('.ipi_respostas').html('');

                            let respString = String(resp).trim();
                            let idLimpo = respString.replace(/\"/g, '');

                            if (resp && !isNaN(idLimpo)) {
                                $('#formIPI').prepend('<div class=\"ipi_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-success\">Dados do IPI salvos com sucesso!</div></div>');
                                if(document.getElementById('id_notas_itens_ipi') && document.getElementById('id_notas_itens_ipi').value == 0) {
                                    document.getElementById('id_notas_itens_ipi').value = idLimpo;
                                }
                            } else {
                                $('#formIPI').prepend('<div class=\"ipi_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Houve um erro ao salvar os dados do IPI!</div></div>');
                            }
                        },
                        error: function (resp) {
                            $('.ipi_respostas').html('');
                            $('#formIPI').prepend('<div class=\"ipi_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Erro de conexão!</div></div>');
                        }
                    });
                } else {
                    hideWait();
                    let situacao = document.getElementById('ipi_situacao').value;
                    $('#formIPI').prepend('<div class=\"ipi_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Não foi possível atualizar o IPI a nota esta <b>'+situacao.toLowerCase()+'</b> portanto não pode ser alterada.</div></div>');
                }
            }


			function atualizarCamposPIS()
			{
				if ($('#id_imp_pis_cst').length === 0) return;

				let texto = $('#id_imp_pis_cst option:selected').text();
				let cst = texto.split(' ')[0].trim();

				let gruposSemAliquota = ['04', '05', '06', '07', '08', '09', '*'];

				if (gruposSemAliquota.includes(cst)) {
					let campo = $('[name=\"pis_pPIS\"]');
					campo.val('0');
					campo.closest('.form-group').hide();
				} else {
					$('[name=\"pis_pPIS\"]').closest('.form-group').show();
				}
			}


            function btnPIS(self)
			{
                showWait();
                btn_aguardar(self);
                let liberar = document.getElementById('pis_liberar').value;
                let replicar = Number($('#replicar').is(':checked'));

                if (liberar==0) {
                    // Verifica se está visível, senão zera
					let pPIS = getFieldValue('pis_pPIS', 0);

                    $.ajax({
                        method: 'POST',
                        url: '".$o->page."&gAjs=1&gPage=".IMPOSTOS_SALVAR."&cmd=salvarPIS',
                        data:{
                            id_imp_pis_cst: document.getElementById('id_imp_pis_cst').value,
                            gIdItem: document.getElementById('pis_gIdItem').value,
                            pPIS: pPIS,
                            id_pis: document.getElementById('id_pis').value,
                            replicar: replicar
                        },
                        success: function (resp) {
                            hideWait();
                            $('.pis_respostas').html('');
                            if (resp) {
                                $('#formPIS').prepend('<div class=\"pis_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-success\">Dados do PIS salvos com sucesso!</div></div>');
                            } else {
                                $('#formPIS').prepend('<div class=\"pis_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Erro ao salvar dados do PIS.</div></div>');
                            }
                        },
                        error: function (resp) {
                            $('.pis_respostas').html('');
                            $('#formPIS').prepend('<div class=\"pis_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Erro de conexão!</div></div>');
                        }
                    });
                } else {
                    hideWait();
                    let situacao = document.getElementById('pis_situacao').value;
                    $('#formPIS').prepend('<div class=\"pis_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Nota bloqueada: <b>'+situacao.toLowerCase()+'</b>.</div></div>');
                }
            }


			function atualizarCamposCOFINS()
			{
				if ($('#id_imp_cofins_cst').length === 0) return;

				let texto = $('#id_imp_cofins_cst option:selected').text();
				let cst = texto.split(' ')[0].trim();

				let gruposSemAliquota = ['04', '05', '06', '07', '08', '09', '*'];

				if (gruposSemAliquota.includes(cst)) {
					let campo = $('[name=\"cofins_pCOFINS\"]');
					campo.val('0');
					campo.closest('.form-group').hide();
				} else {
					$('[name=\"cofins_pCOFINS\"]').closest('.form-group').show();
				}
			}


			function btnCOFINS(self)
			{
                showWait();
                btn_aguardar(self);
                let liberar = getFieldValue('cofins_liberar', 0);
                let replicar = Number($('#replicar').is(':checked'));

                if (liberar == 0) {
                    let pCOFINS = getFieldValue('cofins_pCOFINS', 0);

                    $.ajax({
                        method: 'POST',
                        url: '" .$o->page . "&gAjs=1&gPage=" . IMPOSTOS_SALVAR . "&cmd=salvarCOFINS',
                        data:{
                            id_imp_cofins_cst: document.getElementById('id_imp_cofins_cst').value,
                            pCOFINS: pCOFINS,
                            gIdItem: document.getElementById('cofins_gIdItem').value,
                            id_cofins: document.getElementById('id_cofins').value,
                            replicar: replicar
                        },
                        success: function (resp) {
                            hideWait();
                            $('.cofins_respostas').html('');
                            // Tratamento de resposta seguro
                            let respString = String(resp).trim();
                            let idLimpo = respString.replace(/\"/g, '');

                            if (resp && !isNaN(idLimpo)) {
                                $('#formCOFINS').prepend('<div class=\"cofins_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-success\">Dados dos COFINS atualizado com sucesso !</div></div>');
                                if(document.getElementById('id_cofins').value == 0 || document.getElementById('id_cofins').value == '') {
                                     document.getElementById('id_cofins').value = idLimpo;
                                }
                            } else {
                                $('#formCOFINS').prepend('<div class=\"cofins_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Erro ao salvar dados do COFINS.</div></div>');
                            }
                        },
                        error: function (resp) {
                            hideWait();
                            $('#formCOFINS').prepend('<div class=\"cofins_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Erro de conexão!</div></div>');
                        }
                    });
                } else {
                    hideWait();
                    let situacao = document.getElementById('cofins_situacao').value;
                    $('#formCOFINS').prepend('<div class=\"cofins_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Nota bloqueada: <b>'+situacao.toLowerCase()+'</b>.</div></div>');
                }
            }


			function atualizarCamposIBSCBS()
			{
				if ($('#id_imp_ibs_cbs_cst').length === 0) {
					return;
				}

				let textoOpcao = $('#id_imp_ibs_cbs_cst option:selected').text();
				let cstCodigo = textoOpcao.split(' ')[0].trim();

				atualizarCclasstrib(cstCodigo);

				// Definição dos grupos de campos
				let camposBase = ['vBC_IBS_CBS'];
				let camposAliquotas = ['gIBSUF_pIBSUF', 'gIBSMun_pIBSMun', 'gCBS_pCBS'];
				let camposReducao = ['gIBSUF_pRedAliq', 'gIBSMun_pRedAliq', 'gCBS_pRedAliq'];
				let camposRedutorBC = ['pRedutorBC'];
				let camposEfetivos = ['gIBSUF_pAliqEfet', 'gIBSMun_pAliqEfet', 'gCBS_pAliqEfet'];
				let camposValores = ['gIBSUF_vIBSUF', 'gIBSMun_vIBSMun', 'gCBS_vCBS'];
				let camposDiferimento = ['gIBSUF_pDif', 'gIBSUF_vDif', 'gIBSMun_pDif', 'gIBSMun_vDif', 'gCBS_pDif', 'gCBS_vDif'];
				let camposDevolucao = ['gIBSUF_vDevTrib', 'gIBSMun_vDevTrib', 'gCBS_vDevTrib'];
				let camposMonofasico = ['qBCMono', 'adRemIBS', 'vIBSMono', 'adRemCBS', 'vCBSMono'];
				let camposTransfCred = ['vIBSTransf', 'vCBSTransf'];
				let camposAjuste = ['competApur', 'vIBSAjuste', 'vCBSAjuste'];
				let camposZFM = ['competApur', 'tpCredPresIBSZFM', 'vCredPresIBSZFM'];

				let todosCampos = [].concat(
					camposBase, camposAliquotas, camposReducao, camposRedutorBC,
					camposEfetivos, camposValores, camposDiferimento, camposDevolucao,
					camposMonofasico, camposTransfCred, camposAjuste, camposZFM
				);

				// Salvar valores originais
				if (typeof window.valoresOriginaisIBSCBS === 'undefined') {
					window.valoresOriginaisIBSCBS = {};
					todosCampos.forEach(function(id) {
						let campo = $('[name=\"' + id + '\"]');
						if (campo.length > 0) {
							window.valoresOriginaisIBSCBS[id] = campo.val();
						}
					});
				}

				let camposParaMostrar = {
					'000': [].concat(camposBase, camposAliquotas, camposValores),
					'010': [].concat(camposBase, camposAliquotas, camposValores),
					'220': [].concat(camposBase, camposAliquotas, camposValores),
					'221': [].concat(camposBase, camposAliquotas, camposValores),
					'510': [].concat(camposBase, camposAliquotas, camposValores),
					'550': [].concat(camposBase, camposAliquotas, camposValores),
					'830': [].concat(camposBase, camposAliquotas, camposValores),
					'011': [].concat(camposBase, camposAliquotas, camposReducao, camposEfetivos, camposValores),
					'200': [].concat(camposBase, camposAliquotas, camposReducao, camposEfetivos, camposValores),
					'222': [].concat(camposBase, camposAliquotas, camposRedutorBC, camposValores),
					'400': [], // Isenção/Imunidade - nenhum campo
					'410': [], // Isenção/Imunidade - nenhum campo
					'515': [].concat(camposBase, camposAliquotas, camposReducao, camposEfetivos, camposDiferimento, camposValores),
					'620': [].concat(camposMonofasico),
					'800': [].concat(camposTransfCred),
					'810': [].concat(camposZFM),
					'811': [].concat(camposAjuste),
					'820': [] // Tributação em declaração de regime específico
				}[cstCodigo];

				todosCampos.forEach(function(id) {
					let campo = $('[name=' + id + ']');

					if (camposParaMostrar.indexOf(id) === -1) {
						campo.closest('.form-group').hide();
						campo.val('');
					} else {
						campo.closest('.form-group').show();
						if (campo.val() === '' && window.valoresOriginaisIBSCBS[id]) {
							campo.val(window.valoresOriginaisIBSCBS[id]);
						}
					}
				});

				$('#gIBSUF_pIBSUF, #gIBSUF_pAliqEfet, #gIBSUF_vIBSUF, #gCBS_pCBS, #gCBS_pAliqEfet, #gCBS_vCBS').attr('readonly', true);
			}


			function getFieldValue(id, defaultValue)
			{
				let elem = document.getElementById(id);
				return elem ? elem.value : defaultValue;
			}


			function btnIbsCbs(self)
			{
				showWait();
				btn_aguardar(self);

				let liberar = getFieldValue('ibscbs_liberar', 0);
				let replicar = Number($('#replicar').is(':checked'));

				if (liberar == 0) {
					let obj = {};

					obj.id_imp_ibs_cbs_cst = getFieldValue('id_imp_ibs_cbs_cst', '');
					obj.id_cclasstrib_ibs_cbs = getFieldValue('id_cclasstrib_ibs_cbs', '');
					obj.gIdItem = getFieldValue('ibscbs_gIdItem', '');
					obj.id_ibs_cbs = getFieldValue('id_ibs_cbs', '');
					obj.replicar = replicar;

					obj.indDoacao = Number($('#formIbsCbs input[name=\"indDoacao\"]').is(':checked'));

					obj.vBC_IBS_CBS = getFieldValue('vBC_IBS_CBS', '0');

					// IBS UF (Estadual)
					obj.gIBSUF_pIBSUF = getFieldValue('gIBSUF_pIBSUF', '0');
					obj.gIBSUF_pRedAliq = getFieldValue('gIBSUF_pRedAliq', '0');
					obj.gIBSUF_pAliqEfet = getFieldValue('gIBSUF_pAliqEfet', '0');
					obj.gIBSUF_vIBSUF = getFieldValue('gIBSUF_vIBSUF', '0');
					obj.gIBSUF_pDif = getFieldValue('gIBSUF_pDif', '0');
					obj.gIBSUF_vDif = getFieldValue('gIBSUF_vDif', '0');
					obj.gIBSUF_vDevTrib = getFieldValue('gIBSUF_vDevTrib', '0');

					// IBS Municipal
					obj.gIBSMun_pIBSMun = getFieldValue('gIBSMun_pIBSMun', '0');
					obj.gIBSMun_pRedAliq = getFieldValue('gIBSMun_pRedAliq', '0');
					obj.gIBSMun_pAliqEfet = getFieldValue('gIBSMun_pAliqEfet', '0');
					obj.gIBSMun_vIBSMun = getFieldValue('gIBSMun_vIBSMun', '0');
					obj.gIBSMun_pDif = getFieldValue('gIBSMun_pDif', '0');
					obj.gIBSMun_vDif = getFieldValue('gIBSMun_vDif', '0');
					obj.gIBSMun_vDevTrib = getFieldValue('gIBSMun_vDevTrib', '0');

					// CBS (Federal)
					obj.gCBS_pCBS = getFieldValue('gCBS_pCBS', '0');
					obj.gCBS_pRedAliq = getFieldValue('gCBS_pRedAliq', '0');
					obj.gCBS_pAliqEfet = getFieldValue('gCBS_pAliqEfet', '0');
					obj.gCBS_vCBS = getFieldValue('gCBS_vCBS', '0');
					obj.gCBS_pDif = getFieldValue('gCBS_pDif', '0');
					obj.gCBS_vDif = getFieldValue('gCBS_vDif', '0');
					obj.gCBS_vDevTrib = getFieldValue('gCBS_vDevTrib', '0');

					// CST 222 - Redução de Base de Cálculo
					obj.pRedutorBC = getFieldValue('pRedutorBC', '0');

					// CST 620 - Tributação Monofásica
					obj.qBCMono = getFieldValue('qBCMono', '0');
					obj.adRemIBS = getFieldValue('adRemIBS', '0');
					obj.vIBSMono = getFieldValue('vIBSMono', '0');
					obj.adRemCBS = getFieldValue('adRemCBS', '0');
					obj.vCBSMono = getFieldValue('vCBSMono', '0');

					// CST 800 - Transferência de Crédito
					obj.vIBSTransf = getFieldValue('vIBSTransf', '0');
					obj.vCBSTransf = getFieldValue('vCBSTransf', '0');

					// CST 810 - Ajuste de IBS na ZFM
					obj.tpCredPresIBSZFM = getFieldValue('tpCredPresIBSZFM', '0');
					obj.vCredPresIBSZFM = getFieldValue('vCredPresIBSZFM', '0');

					// CST 811 - Ajustes de Competência
					obj.competApur = getFieldValue('competApur', '');
					obj.vIBSAjuste = getFieldValue('vIBSAjuste', '0');
					obj.vCBSAjuste = getFieldValue('vCBSAjuste', '0');

					$.ajax({
						method: 'POST',
						url: '".$o->page."&gAjs=1&gPage=" . IMPOSTOS_SALVAR . "&cmd=salvarIbsCbs',
						data: obj,
						success: function (resp) {
							hideWait();
							$('.ibscbs_respostas').html('');

							let respString = String(resp).trim();
							let idLimpo = respString.replace(/\"/g, '');

							if (resp && !isNaN(idLimpo)) {
								$('#formIbsCbs').prepend('<div class=\"ibscbs_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-success\">Dados IBS/CBS salvos com sucesso!</div></div>');

								if(getFieldValue('id_ibs_cbs', 0) == 0) {
									let elem = document.getElementById('id_ibs_cbs');
									if (elem) elem.value = idLimpo;
								}
							} else {
								$('#formIbsCbs').prepend('<div class=\"ibscbs_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Erro ao salvar dados IBS/CBS!</div></div>');
							}
						},
						error: function (resp) {
							hideWait();
							$('.ibscbs_respostas').html('');
							$('#formIbsCbs').prepend('<div class=\"ibscbs_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Erro ao salvar dados IBS/CBS!</div></div>');
						}
					});
				} else {
					hideWait();
					let situacao = getFieldValue('ibscbs_situacao', '');
					$('#formIbsCbs').prepend('<div class=\"ibscbs_respostas\"><div style=\"margin-top:3%;\" class=\"alert alert-danger\">Não foi possível atualizar o IBS/CBS. A nota está <b>'+situacao.toLowerCase()+'</b> portanto não pode ser alterada.</div></div>');
				}
			}

        ";
        $o->addJavascript($js);
	}


	function bloquearNotaFiscal()
	{
		/* BLOQUEANDO MOVIMENTAÇÃO COM JAVASCRIPT */
		global $o;
		$js="

		if ($('#field-sku'))
		$('#field-sku').attr('style', 'display:none;');

		if ($('#field-quantidade'))
		$('#field-quantidade').attr('style', 'display:none');

		if ($('#field-pesoB'))
		$('#field-pesoB').attr('style', 'display:none');

		if ($('#field-pesoL'))
		$('#field-pesoL').attr('style', 'display:none');

		if ($('#field-valor'))
		$('#field-valor').attr('style', 'display:none');

		if ($('#field-lote'))
		$('#field-lote').attr('style', 'display:none');

		if ($('#field-serial'))
		$('#field-serial').attr('style', 'display:none');

		if ($('#gSubmitButton'))
		{
			$('#gSubmitButton').attr('style', 'display:none');
		}

		$('a').each(function (key, value) {
			var btn = this.innerText.trim();
			if (btn=='Emitir Nf-e')
			{
				$(this).attr('disabled', 'disabled');
			}

			if (btn=='Excluir')
			{
				$(this).attr('disabled', 'disabled');
			}

			if (btn=='Impostos')
			{
				$(this).attr('disabled', 'disabled');
			}
		});
		";
		$o->addJavascript($js);
	}

	/**
	 * Trata o filtro rápido.
	 * @param  Obj    $req   Requisição post com filtro rápido
	 * @return string  $where where montada a partir da requisição
	 */
	function obtemFiltroRapido($req)
	{
		$where = ($this->tipo=='S') ? "N.tipo='S'" : "N.tipo='E'";
		if ($req["pesquisa"])
		{
			$pesquisa = $req["pesquisa"];
			$where .= " AND (C.codigo LIKE '%$pesquisa%' || N.numero LIKE '%$pesquisa%' || PC.nome LIKE '%$pesquisa%')";
		}
		return $where;
	}

	function comboItens($idProprietario)
	{
		global $gParam;

		$sql = "SELECT
					ISK.id,
					CONCAT(CONCAT_WS(' • ',ISK.codigo, I.descricao,U.descricao), ' com ', CAST(ISK.quantidade as SIGNED)) descricao
				FROM itens I
				LEFT JOIN itens_skus ISK on ISK.id_itens = I.id
				LEFT JOIN unidades U on U.id = ISK.id_unidades
				WHERE I.id_pessoas_proprietario = '{$idProprietario}'
				GROUP BY descricao, I.id";
		  return $sql;
	}
	/**
	 * Trata o filtro da requisição
	 * @param  Obj    $req    Requisição post passada
	 * @return Obj    $return Array contendo uma where e seu cabeçalho demonstrativo.
	 */
	function obtemBusca($req, $tipo)
	{
		global $gParam;
		$where = ($this->tipo=='S') ? "N.tipo='S'" : "N.tipo='E'";

		if(!isset($req['cancelada']))
			$where.= " AND (N.cancelada=0)";

		if ($tipo==1) {
			if ($req["pesquisa"]) {
				$pesquisa = $req["pesquisa"];
				$where .= " AND (N.id='{$pesquisa}' OR C.codigo LIKE '%{$pesquisa}%' OR N.numero LIKE '%{$pesquisa}%' OR PC.nome LIKE '%{$pesquisa}%')";
				$where.=" AND (N.id_filial=".intval($_SESSION["filialAtualId"]).")";
			}

			if ($req['pesquisaGeral']) {
				if (isset($req['confirmada'])) {
					$where .= ' AND N.confirmada = '.$req['confirmada'];
				}
				if ($req['id_proprietarios']) {
					$where .= ' AND N.id_pessoas_proprietario = '.$req['id_proprietarios'];
				}
			}

			if (is_numeric($req['id_notas_agrupar'])) {
				$where .= " AND N.id_notas_agrupar = '".$req['id_notas_agrupar']."'";
			}

			if ($req["dtCadastroDe"]) {
				$dataCadastroDe = date("Y-m-d", strtotime(gDBDate($req["dtCadastroDe"])));
				$where .= " AND N.data_criou >= '$dataCadastroDe'";
				$cabecalho[] = "Data cadastro de: ".$req["dtCadastroDe"];
			}
			if ($req["dtCadastroAte"]) {
				$dataCadastroAte = date("Y-m-d", strtotime(gDBDate($req["dtCadastroAte"])));
				$where .= " AND N.data_criou <= '$dataCadastroAte 23:59:59'";
				$cabecalho[] = "Data cadastro até: ".$req["dtCadastroDe"];
			}

			if ($req["dtCancelamentoDe"]) {
				$dataCancelamentoDe = date("Y-m-d", strtotime(gDBDate($req["dtCancelamentoDe"])));
				$where .= " AND N.cancelada = 1 AND N.data_cancelamento >= '$dataCancelamentoDe'";
				$cabecalho[] = "Data cancelamento de: ".$req["dtCancelamentoDe"];
			}

			if ($req["dtCancelamentoAte"]) {
				$dataCancelamentoAte = date("Y-m-d", strtotime(gDBDate($req["dtCancelamentoAte"])));
				$where .= " AND N.cancelada = 1 AND N.data_cancelamento <= '$dataCancelamentoAte 23:59:59'";
				$cabecalho[] = "Data cancelamento até: ".$req["dtCancelamentoAte"];
			}

			if ($req['id_pessoas_proprietario']) {
				$where .= " AND N.id_pessoas_proprietario = ".$req['id_pessoas_proprietario'];
				$cabecalho[] = "Proprietário: ".obtemProprietario($req["id_pessoas_proprietario"], 'apelido')[0];
			}

			if (gDBCheck($req['cancelada'])) {
				$where .= ' AND NE.cancelada = 1';
				$cabecalho[] = "NFs canceladas: Sim";
			}

			return ($where);

		} else {
			$cabecalho = [];
			$return = [];
			if (!empty($req["codigo"])) {
				$codigo=gCleanField($req["codigo"]);
				$where.=" AND itens_skus.codigo LIKE '%{$codigo}%'";
				$cabecalho[]=" Item: {$codigo}";
			}

			if (gDBCheck($req['estorno'])) {
				$where .= ' AND N.id_notas_origem_estorno > 0';
				$cabecalho[] = "Nota de estorno: Sim";
			}

			if (gDBCheck($req['cancelada'])) {
				$where .= ' AND NE.cancelada > 0';
				$cabecalho[] = "Nota de cancelamento: Sim";
			}

			if ($req["numero"]) {
				$numero = gCleanField($req["numero"]);
				$where .= " AND N.numero = '{$numero}'";
				$cabecalho[] = " Número: {$numero}";
			}

			if ($req["proprietario"] > 0) {
				$proprietario = gCleanField($req["proprietario"]);
				$nomeProprietario = dbQuery("SELECT nome FROM pessoas WHERE id = $proprietario")[0]["nome"];
				$where .= " AND N.id_pessoas_proprietario={$proprietario}";
				$cabecalho[] = "Proprietário: {$nomeProprietario}";
			}

			if ($req["filial"]) {
				$filial = gCleanField($req["filial"]);
				$nomeFilial = dbQuery("SELECT descricao FROM filial WHERE id = $filial")[0]["descricao"];
				$where .= " AND N.id_filial = {$filial}";
				$cabecalho[] = "Filial: {$nomeFilial}";
			}

			if ($req["chave"]) {
				$chave=gCleanField($req["chave"]);
				$where .= " AND NE.chave LIKE '%{$chave}%'";
				$cabecalho[] = "Chave: {$chave}";
			}

			if ($req["cfop"]) {
				$cfop = gCleanField($req["cfop"]);
				$nomeCfop = dbQuery("SELECT CONCAT(codigo, ' - ', descricao) as nomeCfop FROM cfops WHERE id = $cfop")[0]["nomeCfop"];
				$where .= " AND N.id_cfops={$cfop}";
				$cabecalho[] = "CFOP: {$nomeCfop}";
			}

			if ($req["transportadora"]) {
				$transportadora = gCleanField($req["transportadora"]);
				$nomeTransportadora = dbQuery("SELECT nome FROM pessoas WHERE id=$transportadora")[0]["nome"];
				$where .= " AND N.id_pessoas_transportadora='{$transportadora}'";
				$cabecalho[] = "Transportadora: {$nomeTransportadora}";
			}

			if ($req["dtEmissaoDe"]) {
				$dataEmissaoDe  = gDBDate($req["dtEmissaoDe"]);
				$where .= " AND N.data_emissao >= '$dataEmissaoDe'";
				$cabecalho[] = "Data de emissão de: " . $req["dtEmissaoDe"];
			}

			if ($req["dtEmissaoAte"]) {
				$dataEmissaoAte = date("Y-m-d", strtotime("+1 day", strtotime(gDBDate($req["dtEmissaoAte"]))));
				$where .= " AND N.data_emissao <= '$dataEmissaoAte'";
				$cabecalho[] = "Data de emissão até: " . $req["dtEmissaoAte"];
			}

			if ($req["dtMovimentoDe"]) {
				$dataMovimentoDe  = gDBDate($req["dtMovimentoDe"]);
				$where .= " AND N.data_movimento >= '$dataMovimentoDe'";
				$cabecalho[] = "Data de movimento de: " . $req["dtMovimentoDe"];
			}

			if ($req["dtMovimentoAte"]) {
				$dataMovimentoAte  = gDBDate($req["dtMovimentoAte"]);
				$where .= " AND N.data_movimento < '$dataMovimentoAte'";
				$cabecalho[] = "Data de movimento até: " . $req["dtMovimentoAte"];
			}

			if ($req["dtCadastroDe"]) {
				$dataCadastroDe  = gDBDate($req["dtCadastroDe"]);
				$where .= " AND N.data_criou >= '$dataCadastroDe'";
				$cabecalho[] = "Data de cadastro de: " . $req["dtCadastroDe"];
			}

			if ($req["dtCadastroAte"]) {
				$dataCadastroAte  = date("Y-m-d", strtotime("+1 day", strtotime(gDBDate($req["dtCadastroAte"]))));
				$where .= " AND N.data_criou < '$dataCadastroAte'";
				$cabecalho[] = "Data de cadastro até: " . $req["dtCadastroAte"];
			}

			if ($req["dtCancelamentoDe"]) {
				$dataCancelamentoDe = date("Y-m-d", strtotime(gDBDate($req["dtCancelamentoDe"])));
				$where .= " AND NE.cancelada = 1 AND NE.data_cancelamento >= '$dataCancelamentoDe'";
				$cabecalho[] = "Data cancelamento de: ".$req["dtCancelamentoDe"];
			}

			if ($req["dtCancelamentoAte"]) {
				$dataCancelamentoAte = date("Y-m-d", strtotime(gDBDate($req["dtCancelamentoAte"])));
				$where .= " AND NE.cancelada = 1 AND NE.data_cancelamento <= '$dataCancelamentoAte 23:59:59'";
				$cabecalho[] = "Data cancelamento até: ".$req["dtCancelamentoAte"];
			}

			$where.=" AND ( N.id_filial=".intval($_SESSION["filialAtualId"]).")";

			$return["where"] = $where;
			$return["cabecalho"] = $cabecalho;
			return ($return);
		}
	}
	/**
	 * Gera o formulário para preenchimento do cabeçalho da nota fiscal
	 * @param  Integet 	$registroAtual  - ID do registro atual, usado em caso de edição ou visualização
	 * @param  string 	$proximaPagina  - Indica a próxima ação que será processada na submissão do formulário
	 * @return HTML 	$frm            - Código HTML para renderização do formulário
	 */
	function geraCamposDoFormularioNota($registroAtual, $proximaPagina){
		global $gId, $gPage, $o, $sp;
		$descTipo = $this->tipo == 'S' ? 'Saída' : 'Entrada';

		$frm = new gForm("{columns:2;}");
		if ($registroAtual) {
			$frm->add("{name: tpFormulario; type:hidden; value: 2}");
		} else {
			$frm->add("{name: tpFormulario; type:hidden; value: 1}");
		}

		if ($this->tipo == 'S') {
			$comboTipo = [];
			$comboTipo["S"]="Saída";
			$comboTipo["E"]="Entrada";
			$campoTipoNota = $frm->add("{name: tipoNota; type: combo; items:'".json_encode($comboTipo)."'; allowBlank: false; fieldLabel: Tipo; value: ".$registroAtual["tipo"]."}");
			$frm->row($campoTipoNota);
		} else {
			$frm->row(
				//$frm->add("{name: show_tipoNota; type: show; allowBlank: false; fieldLabel: Tipo; value: ".$descTipo."}"),
				$frm->add("{name: numero; type:number; fieldLabel: Número; maxLength:9; value: ".$registroAtual['numero'].";}"),
				$frm->add("{name: serie; type:number; fieldLabel: Série; maxLength:9; value: ".$registroAtual['serie'].";}")
			);
			$frm->add("{name:tipoNota; type:hidden; value:E;}");
		}

		$idFilial = $registroAtual['id_filial'] ?: $_SESSION['filialAtualId'];

		$frm->row(
			$frm->add("{name: id_filial; fieldLabel: Filial; allowBlank: false; type: combo; value: " . $idFilial . "; items: ".$sp['combo_filial']."}"),
			$frm->add("{name: id_cfops; fieldLabel: CFOP; allowBlank: false; type: combo; items: " . $sp["combo_cfop"] . "; value:".$registroAtual["id_cfops"].";}")
		);

		$frm->row(
			$frm->add("{name: id_pessoas_proprietario; fieldLabel: Cliente; allowBlank: false; type: combo; value: ".$registroAtual['id_pessoas_proprietario']."; items: ".$sp['combo_clientes']."}"),
			$frm->add("{name: id_pessoas_transportadora; fieldLabel: Transportadora; type: combo; value: ".$registroAtual['id_pessoas_transportadora']."; items: ".$sp['combo_transportadora']."}")
		);

		$frm->row(
			$frm->add("{name: id_pessoas_fornecedor; fieldLabel: Fornecedor; type: combo; value: ".$registroAtual['id_pessoas_fornecedor']."; items: ".$sp['combo_fornecedores']."}"),
			$frm->add("{name: volume; type: number; allowBlank: true; fieldLabel: Volume; value: ".$registroAtual['volume']."}")
		);

		$dataEmissao= ($registroAtual) ? $registroAtual["data_emissao"] : date('Y-m-d');
		$frm->row(
			$frm->add("{name: data_emissao; type: date; allowBlank: false; fieldLabel: Emissão; value: ".gDate($dataEmissao)."}"),
			$frm->add("{name: data_movimento; type:date; fieldLabel: Movimento; value: ".gDate($registroAtual['data_movimento']).";}")
		);

		$frm->add("{name: gId; type: hidden; value: $gId}");
		$frm->add("{name: gPage; type: hidden; value: $proximaPagina}");
		return($frm);
	}

	/**
	 * Renderiza o formulário de pesquisa.
	 * @return Void
	 */
	function geraFormularioFiltro()
	{
		global $sp, $gParam;
		$frm=new gForm();
		$frm->addFormMessage("Informe uma ou mais opções abaixo para buscar...");

		$frm->row(
			$frm->add("{name: numero; fieldLabel: Número; type: text;"),
			$frm->add("{name: proprietario; fieldLabel: Proprietário; items: ". $sp["combo_clientes"] .";type: combo;"),
			$frm->add("{name: codigo; fieldLabel:Código item; value:; type:text;}")
		);

		$frm->row(
			$frm->add("{name: chave; fieldLabel: Chave; type: text;"),
			$frm->add("{name: cfop; fieldLabel: CFOP; items: ". $sp["combo_cfop"] ."; type: combo;"),
			$frm->add("{name: transportadora; fieldLabel: Transportadora; items: ". $sp["combo_transportadora"] ."; type: combo;")
		);

		$frm->row(
			$frm->add("{name: dtEmissaoDe; fieldLabel: Data de emissão de: ; type: date;"),
			$frm->add("{name: dtEmissaoAte; fieldLabel: Data de emissão até: ; type: date;")
		);

		$frm->row(
			$frm->add("{name: dtMovimentoDe; fieldLabel: Data de movimento de:; type: date;"),
			$frm->add("{name: dtMovimentoAte; fieldLabel: Data de movimento até; type: date;")
		);

		$frm->row(
			$frm->add("{name: dtCadastroDe; fieldLabel: Data de cadastro de:; type: date;"),
			$frm->add("{name: dtCadastroAte; fieldLabel: Data de cadastro até; type: date;")
		);

		$frm->row(
			$frm->add("{name: dtCancelamentoDe; fieldLabel: Data de cancelamento de:; type: date;"),
			$frm->add("{name: dtCancelamentoAte; fieldLabel: Data de cancelamento até; type: date;")
		);

		$combo = [];
		$combo[1] = 'Sim';
		$combo[2] = 'Não';
		$frm->row(
			$frm->add("{name: estorno; fieldLabel: Nota de estorno; type: checkbox; value: 0;}"),
			$frm->add("{name: cancelada; fieldLabel: Nota cancelada; type: checkbox; value: 0;}")
		);

		$frm->add("{name: tipoNota; value: E; type: hidden}");
		$frm->add("{name: gPage; value: 0; type: hidden}");
		$frm->add("{name: action; value: filtro; type: hidden}");
		$frm->add("{name: filtro; value:1; type:hidden;}");
		return ($frm);
	}

	/**
	 * Renderiza o formulário de pesquisa.
	 * @param  Array $nota Dados da nota fiscal.
	 * @param  Array $item Dados do item atual.
	 * @return Void
	 */
	function geraFormularioNotaItem($nota, $item)
	{
		global $o, $sp, $gParam;
		$frm = new gForm();

		$usuarioPodeEditar = (
			in_array('Acesso total', $_SESSION['permissionsNames'])
			|| in_array('Editar Itens Nota', $_SESSION['permissionsNames'])
			|| $_SESSION['usrId'] <= 2
		);
		$itens = $this->comboItens($nota["id_pessoas_proprietario"]);
		$campoQuantidade = $frm->add("{name: quantidade; fieldLabel: Quantidade; type: number; value: ". gFloat($item["quantidade"]) .";");
		if (!$usuarioPodeEditar) {
			$js =
				"$(window).on('load', function() {
					if ($('#quantidade')) {
						$('#quantidade').attr('readonly', 'readonly');
					}
					document.getElementsByName('btnNovoItem')[0].setAttribute('disabled', true);
				});";
			$o->addJavascript($js);
			$frm->add("{name: sku; type: hidden; value: ". $item["id_itens_skus"] .";}");
			$frm->row(
				$frm->add("{name: item; type: show; value: ". $item["descricao"] .";}"),
				$campoQuantidade
			);
		}

		if ($usuarioPodeEditar) {
			$frm->row(
				$frm->add("{allowBlank: false; name: sku; fieldLabel: Item; type: combo; items: ". $itens ." ; value:".$item["id_itens_skus"]."; }"),
				$campoQuantidade
			);
		}

		$frm->row(
			$frm->add("{name: pesoB; fieldLabel: Peso bruto; type: number; value: ". gFloat($item["peso_bruto"]) ."; "),
			$frm->add("{name: pesoL; fieldLabel: Peso líquido; type: number; value: ". gFloat($item["peso_liquido"]) .";"),
			$frm->add("{name: valor; fieldLabel: Valor; type: number; value:". number_format($item["valor"], 5, ',', '.') ."; ")
		);

		if ($nota['tipo'] == 'E') {
			$comboCfop = $sp["combo_cfop_entrada"];
			$nomeCombo = 'CFOP Entrada';
		} else {
			$comboCfop = $sp['combo_cfop_saida'];
			$nomeCombo = 'CFOP Saída';
		}

		$frm->row(
			$frm->add("{name: lote; fieldLabel: Lote; type: text; value: ".$item["lote"] ."; "),
			$frm->add("{name: serial; fieldLabel: Serial; type: text; value:". $item["serial"] .";"),
			$frm->add("{name: id_cfops_saida; fieldLabel: " . $nomeCombo . "; type:combo; items:".$comboCfop."; value:".$item["id_cfops_saida"]." }")
		);

		if (in_array($nota["codigo_cfops"], $this->cfopsCombustivel)) {

			if (intval($item["id_grupos_combustivel"])>0) {
				$defaultValue=$item["id_grupos_combustivel"];
			} else {
				$defaultValue=$item["id_grupos_combustivel_item"];
			}

			$comboGrupo = "SELECT id, CONCAT(codigo, ' • ', descricao) descricao FROM grupos_combustivel";
			$html.=$o->msgFilter("Grupo combustível");
			$frm->row(
				$frm->add("{allowBlank:true; type:combo; name:id_grupos_combustivel; fieldLabel:Grupo combustível; items:".$comboGrupo."; value:".$defaultValue.";}"),
				$frm->add("{type:text; name:CODIF; value:".$item["CODIF"].";}")
			);
		}

		$frm->add("{name: gPage; type: hidden; value: 61}");
		$frm->add("{name: gId; type:hidden; value: ". $nota["id"] .";}");
		$frm->add("{name: gIdEnd; type:hidden; value: ". $item["id"] .";}");
		$frm->addButton("{name: btnNovoItem; icon: box; title: Novo item; style: default; href: ".$o->page."&gPage=60&gId=".$nota["id"]."}");
		$frm->addButton("{icon: download; title: Cadastro de itens em lote; style: default; href: " . $o->page . "&gPage=" . FORMULARIO_IMPORTAR_ITENS_NOTA . "&gId=".$nota["id"]."&idPessoasProprietario=" . $nota['id_pessoas_proprietario'] . "}");
		return ($frm);
	}

	function preparaCamposNotaNFE($req, $gId) {
		$totais=$this->totaisNota($gId);
		$mtz=[];
		$mtz["idDestino"]=intval($req["idDestino"]);
		$mtz["IE"]=intval($req["IE"]);
		$mtz["finNFe"]=intval($req["finNFe"]);
		$mtz["modFrete"]=intval($req["modFrete"]);
		$mtz["RNTC"] = retirarCaracteresReservadosXml(gCleanField($req["RNTC"]));
		$mtz["placa"] = retirarCaracteresReservadosXml(gCleanField($req["placa"]));
		$mtz["placaUF"] = retirarCaracteresReservadosXml(gCleanField($req["placaUF"]));
		$mtz["id_nfe_informacoes"]=intval($req["id_nfe_informacoes"]);
		$mtz["infAdFisco"] = retirarCaracteresReservadosXml(gCleanField($req["infAdFisco"]));
		$mtz["InfCpl"] = retirarCaracteresReservadosXml(gCleanField($req["InfCpl"]));
		$mtz["esp"] = retirarCaracteresReservadosXml(gCleanField($req["esp"]));
		$mtz["qVol"] = retirarCaracteresReservadosXml($req['qVol']);
		$mtz["marca"] = retirarCaracteresReservadosXml(gCleanField($req["marca"]));
		$mtz["vOutro"]=gDBFloat($req["vOutro"]);
		$mtz["pesoB"]=$totais["pesoB"];
		$mtz["pesoL"]=$totais["pesoL"];
		$mtz["tPag"]=intval($req["tPag"]);
		$mtz["vPag"]=gDBFloat($req["vPag"]);
		$mtz["vSeg"]=gDBFloat($req["vSeg"]);
		$mtz["vFrete"]=gDBFloat($req["vFrete"]);
		$mtz["vProd"]=$totais["vProd"];
		$mtz["vDesc"]=gDBFloat($req["vDesc"]);
		$mtz["refNfe"]= retirarCaracteresReservadosXml(gCleanField($req["refNfe"]));
		$mtz["indIntermed"]=intval($req["indIntermed"]);
		$mtz["emails_enviar"] = retirarCaracteresReservadosXml(str_replace(", ", ",", gCleanField($req["emails_enviar"])));
		return ($mtz);
	}

	function preparaCamposNota($todosOsCampos, $gId = 0)
	{
		global $usrId;

		$campos = [];
		$campos['id_pessoas_proprietario'] = intval($todosOsCampos['id_pessoas_proprietario']);
		$campos['id_pessoas_transportadora'] = intval($todosOsCampos['id_pessoas_transportadora']);
		$campos['id_pessoas_fornecedor'] =  (int) $todosOsCampos['id_pessoas_fornecedor'];
		$campos['id_pessoas_cliente']  =  intval($todosOsCampos['id_pessoas_proprietario']);
		$campos['id_filial'] = intval($todosOsCampos['id_filial']);
		$campos['id_cfops'] = intval($todosOsCampos['id_cfops']);
		$campos['numero'] = $todosOsCampos['numero'];
		$campos['data_emissao'] = gDBDate($todosOsCampos['data_emissao']);
		$campos['data_movimento'] = gDBDate($todosOsCampos['data_movimento']);
		$campos['tipo'] = $todosOsCampos["tipoNota"];
		$campos['volume'] = intval($todosOsCampos["volume"]);
		if (!$gId) {
			$campos['data_criou'] = date("Y-m-d H:i:s");
			$campos['id_pessoas_criou'] = intval($usrId);
		}
		return($campos);
	}

	function insereNota($campos)
	{
		$gId=dbInsert('notas', $this->preparaCamposNota($campos, 0), true);
		return($gId);
	}

	function atualizarNotaNFE($req, $gId) {
		dbUpdate("notas", $this->preparaCamposNotaNFE($req, $gId), $gId);
		return true;
	}

	/**
	* Faz alteração no cabeçalho da nota fiscal
	* @param  Array 	$campos 	- Array associativo com os dados da nota
	* @param  Integer 	$gId    	- ID do registro a ser alterado
	* @return Boolean
	*/
	function modificaNota($campos)
	{
		global $o;

		$gId = $campos["gId"];
		$sucesso = true;
		$sql = "SELECT
					notas_itens.id,
					notas.id_pessoas_proprietario
				FROM notas
				LEFT JOIN notas_itens ON notas_itens.id_notas = notas.id
				WHERE notas.id=$gId";
		$rs = dbQuery($sql);

		$row = $rs[0];
		if (
			(int) $row['id'] == 0
			|| $campos['id_pessoas_proprietario'] == $row['id_pessoas_proprietario']
		) {
			$campos = $this->preparaCamposNota($campos, $gId);
			dbUpdate('notas', $campos, $gId);
			if ((int) $campos['id_cfops'] > 0) {
				dbUpdate('notas_itens', ['id_cfops' => $campos['id_cfops']], $gId, "id_notas");
			}
		} else {
			$this->defineErros("Está nota já possui itens cadastrados, não é possível alterar o proprietário.");
			$sucesso = true;
		}

		return ($sucesso);
	}


	public function obtemQueryItem()
	{
		global $gParam;
		if ($gParam['INSERE_REFNFE_AUTO']['ativo']) {
			$selectChaveNfeAssociada  = ' ,nfe_associada.chave AS chave_nota_associada ';
			$joinNfeAssociada  = ' LEFT JOIN nfe nfe_associada ON (
				nfe_associada.id_notas = notas_associadas.id
				AND nfe_associada.cancelada = 0
		) ';
		}

		$sql = "SELECT
					notas_itens.id,
					notas_itens.quantidade,
					notas_itens.valor,
					notas_itens.id_itens_skus,
					notas_itens.lote,
					notas_itens.serial,
					notas_itens.peso_bruto,
					notas_itens.peso_liquido,
					notas_itens.data_fabricacao,
					notas_itens.valor_frete,
					notas_itens.valor_base_calculo,
					itens_skus.nome,
					itens_skus.codigo_barras,
					itens_skus.codigo AS codigo_sku,
					itens.codigo,
					itens.descricao,
					unidades.sigla,
					notas_itens.id_notas,
					notas.tipo,
					itens.ncm,
					itens.apto,
					itens.id AS id_itens,
					itens_skus.quantidade quantidade_sku,
					notas_itens.id_notas_associada,
					itens_skus.ativo ativo_sku,
					itens.ativo ativo_item,
					notas_associadas.numero nota_associada,
					grupos_combustivel.descricao descANP,
					grupos_combustivel.codigo cProdANP,
					notas_itens.id_grupos_combustivel,
					itens.id_grupos_combustivel id_grupos_combustivel_item,
					notas_itens.CODIF,
					notas_itens.UFCons,
					NFE.situacao,
					cfops.codigo cfop,
					cfops.codigo cfop_entrada,
					CFOPSAIDA.codigo cfop_saida,
					CFOPSAIDA.id id_cfops_saida
					{$selectChaveNfeAssociada}
				FROM notas
				LEFT JOIN nfe NFE ON NFE.id_notas = notas.id
				INNER JOIN notas_itens ON notas_itens.id_notas = notas.id
				LEFT JOIN grupos_combustivel ON notas_itens.id_grupos_combustivel=grupos_combustivel.id
				INNER JOIN itens_skus ON notas_itens.id_itens_skus = itens_skus.id
				INNER JOIN itens ON itens.id = itens_skus.id_itens
				LEFT JOIN notas notas_associadas ON notas_associadas.id = notas_itens.id_notas_associada
				{$joinNfeAssociada}
				LEFT JOIN cfops ON cfops.id = notas_associadas.id_cfops
				LEFT JOIN cfops CFOPSAIDA ON CFOPSAIDA.id = notas_itens.id_cfops
				LEFT JOIN unidades ON itens_skus.id_unidades = unidades.id";
		return $sql;
	}

	/**
	* Obtem registros da tabela notas_itens
	* @param  integer $idNotas    		 - Id da tabela notas
	* @param  integer $idNotasItens     - Id da tabela notas_itens
	* @return Array          	- Resultset com os registro obtidos
	*/
	function obtemRegistrosNotasItens($idNotas,$idNotasItens=0, $where="")
	{
		global $gId;

		$idNotas = is_array($idNotas) ? implode(', ', $idNotas) : $idNotas;
		$sql = "SELECT * FROM notas WHERE id IN (".$idNotas.")";
		$nota = dbQuery($sql)[0];
		$filtro = "notas.id>0";
		$sql = $this->obtemQueryItem();

		if ($idNotas) {
			$filtro .= " AND notas.id IN ({$idNotas})";
		}

		if (!empty($where) && !is_null($where)) {
			$filtro.= " AND {$where}";
		}

		$sql .= " WHERE {$filtro}";
		if ($idNotasItens > 0) {
			$sql.=" AND notas_itens.id = {$idNotasItens}";
		}
		return(dbQuery($sql));
	}


	public function obtemNotaItem($idItem = 0)
	{
		$sql = $this->obtemQueryItem();
		$sql .= " WHERE notas_itens.id = '{$idItem}'";
		return(dbQuery($sql)[0]);
	}


	public function obtemQuery()
	{
		$sql = "SELECT
					N.id,
					N.numero,
					NE.id AS id_nfe,
					NE.chave,
					NE.data data_nfe,
					PE.nome nome_nfe,
					PE.apelido apelido_nfe,
					N.data_emissao,
					N.data_criou,
					P.nome as nome_criou,
					PC.nome as nome_cliente,
					C.codigo as codigo_cfops,
					C.descricao as descricao_cfops,
					CONCAT(C.codigo, ' - ', C.descricao) as demonstrativo_cfops,
					NE.protocolo,
					N.data_movimento,
					C.codigo,
					N.tipo,
					N.id_pessoas_proprietario,
					N.id_pessoas_transportadora,
					N.id_pessoas_fornecedor,
					N.confirmada,
					N.cancelada,
					NE.situacao,
					N.id_cfops,
					PC.nome nome_proprietario,
					PC.apelido apelido_proprietario,
					A.descricao filial,
					PT.nome nome_transportadora,
					N.id_filial,
					N.volume,
					NOTA_AGRUPADA.id AS id_nota_agrupada,
					'0' AS agrupada
				FROM notas N
				LEFT JOIN notas NOTA_AGRUPADA ON NOTA_AGRUPADA.id = N.id_notas_agrupar
				LEFT JOIN nfe NE ON (NE.id_notas = N.id)
				LEFT JOIN pessoas PE ON PE.id = NE.id_pessoa
				LEFT JOIN pessoas P ON P.id = N.id_pessoas_criou
				LEFT JOIN cfops C ON C.id = N.id_cfops
				LEFT JOIN pessoas PC ON PC.id = N.id_pessoas_proprietario
				LEFT JOIN filial A ON A.id = N.id_filial
				LEFT JOIN pessoas PT ON PT.id = N.id_pessoas_transportadora";

		if ($this->inner_item) {
			$sql.=" INNER JOIN notas_itens ON notas_itens.id_notas = N.id ";
			$sql.=" INNER JOIN itens_skus ON itens_skus.id = notas_itens.id_itens_skus ";
		}

		return $sql;
	}

	public function obtemRegistros($orderBy = null, $where = null, $limit = "")
	{
		global $gParam;
		$sql = $this->obtemQuery();
		if (!is_null($where) && !empty($where)) {
			$sql .= " WHERE {$where}";
		}
		$sql .= " GROUP BY N.id";

		if (!is_null($orderBy) && !empty($orderBy)) {
			$sql .= " ORDER BY {$orderBy}";
		}

		if ($limit) {
			$sql .= " LIMIT " . $limit;
		} else {
			if ($gParam["PAGINACAO"]["ativo"]) {
				$this->porPagina = $gParam["PAGINACAO"]["valor"];
			}

			if ($this->porPagina > 0) {
				$this->pagination = new Pagination();
				$totalRegistros = $this->pagination->controlarQuantidadePaginas($sql, $qtdMinimaPaginas = 10);
				$pagination = $this->pagination->addPagination($totalRegistros, $this->porPagina);
				$sql .= " LIMIT {$pagination->iniciar}, {$pagination->numero_registro_por_pagina}";
			} else {
				if ($gParam['LIMITAR_VISUALIZACAO']['ativo']) {
					$sql .= " LIMIT " . $gParam['LIMITAR_VISUALIZACAO']['valor'];
				}
			}
		}

		return dbQuery($sql);
	}


	public function obtemRegistro($id)
	{
		$sql = $this->obtemQuery();
		$sql .= " WHERE N.id = '{$id}'";
		return dbFastQuery($sql)[0];
	}


	/**
	* [geraCamposDoFormularioNotaItem description]
	* @param  Obj 		&$frm          			Instancia do objeto Frm (formulário)
	* @param  integer 	$registroAtual 			ID do registro atual
	* @param  integer 	$proximaPagina 			Constante usado no switch da página
	* @return string                			String contendo o html para montagem do form
	*/
	public function geraCamposDoFormularioNotasItens(&$frm, $registroAtual, $proximaPagina="")
	{
		global $gId, $gPage, $o, $sp, $usrId;
		$sql="SELECT id_pessoas_proprietario FROM notas WHERE id=$gId";
		$rs=dbQuery($sql);
		if ($rs) {
			$sqlItem = "SELECT
							itens_skus.id,
							CONCAT(COALESCE(itens.codigo,' '),' - ',COALESCE(itens.descricao,' '),' x ',COALESCE(unidades.sigla,' ')) descricao
						FROM itens_skus
						INNER JOIN itens ON itens.id = itens_skus.id_itens
						LEFT JOIN unidades ON unidades.id = itens_skus.id_unidades
						WHERE itens.ativo=1 AND itens.id_pessoas_proprietario = ".$rs[0]['id_pessoas_proprietario']."
						ORDER BY itens.codigo";
		} else {
			$sqlItem = "SELECT 0, 'Nenhum item cadastrado para o cliente'";
		}
		$frm->add("{name: id_itens_skus; fieldLabel: Item; allowBlank: false; type: combo; value: ".$registroAtual['id_itens_skus']."; items: ".$sqlItem."}");
		$frm->add("{name: quantidade; type: number; allowBlank: false; fieldLabel:Quantidade; value: ".gFloat($registroAtual['quantidade'])."}");
		$frm->add("{name: valor; type: number; allowBlank: false; fieldLabel:Valor unitário; value: ".gFloat($registroAtual['valor'])."}");
		$frm->add("{name: lote; type:text; fieldLabel: Lote; value: ".$registroAtual['lote'].";}");
		$frm->add("{name: gId; type: hidden; value: $gId}");
		$frm->add("{name: gIdItem; type: hidden; value: $gIdItem}");
		$frm->add("{name: gPage; type: hidden; value: $proximaPagina}");
		return $frm->render($o);
	}


	/**
	* Insere o registro de itens da nota fiscal
	* @param  Array 	$campos     - Array associativo com os dados do item da nota
	* @return Array         		- [idItem] [gId]
	*/
	public function insereNotaItem($campos)
	{
		// Primeiro obtém o próximo id
		$gId = dbInsert('notas_itens',$this->preparaCamposNotaItem($campos), true);

		$sai = [];
		$sai['idItem'] = $gId;
		$sai['gId'] = $campos['gId'];
		return($sai);
	}


	public function modificaNotaItem($req)
	{
		dbUpdate("notas_itens", $this->preparaCamposNotaItem($req), $req["gIdEnd"]);

		$item = [];
		$item["idItem"]=$req["gIdEnd"];
		$item["gId"]=$req["gId"];

		return $item;
	}


	public function excluirItemNota($idItem)
	{
		$sql = "DELETE FROM notas_itens where id = '$idItem'";
		dbQuery($sql);
	}


	public function preparaCamposNotaItem($todosOsCampos)
	{
		global $usrId;

		$campos = [];
		$campos['id_cfops']=intval($todosOsCampos['id_cfops_saida']);
		$campos['id_itens_skus']=intval($todosOsCampos['sku']);
		$campos['id_notas']=intval($todosOsCampos['gId']);
		$campos['quantidade']=gDBFloat($todosOsCampos['quantidade']);
		$campos['valor']=gDBFloat($todosOsCampos['valor']);
		$campos['lote']=gCleanField($todosOsCampos['lote']);
		$campos['serial']=gCleanField($todosOsCampos['serial']);
		$campos['peso_bruto']=gDBFloat($todosOsCampos['pesoB']);
		$campos['peso_liquido']=gDBFloat($todosOsCampos['pesoL']);
		$campos['id_grupos_combustivel']=intval($todosOsCampos["id_grupos_combustivel"]);
		$campos['CODIF']=gCleanField($todosOsCampos['CODIF']);
		$campos['UFCons']=gCleanField($todosOsCampos['UFCons']);
		return($campos);
	}


	public function obtemDadosIcms($idNotaItem, $campos="*")
	{
		return (dbFastQuery("SELECT {$campos} FROM notas_itens_icms WHERE id_notas_itens = '{$idNotaItem}'")[0]);
	}


	public function obtemDadosPis($idNotaItem, $campos="*")
	{
		return (dbFastQuery("SELECT {$campos} FROM notas_itens_pis WHERE id_notas_itens = '{$idNotaItem}'")[0]);
	}


	public function obtemDadosCofins($idNotaItem, $campos="*")
	{
		return (dbFastQuery("SELECT {$campos} FROM notas_itens_cofins WHERE id_notas_itens = '{$idNotaItem}'")[0]);
	}


	public function obtemDadosIpi($idNotaItem, $campos="*")
	{
		return dbFastQuery("SELECT {$campos} FROM notas_itens_ipi WHERE id_notas_itens = '{$idNotaItem}'")[0];
	}


	public function obterDadosIs($idNotaItem, $campos = "*")
	{
		return dbFastQuery("SELECT {$campos} FROM notas_itens_is WHERE id_notas_itens = '{$idNotaItem}'")[0];
	}


	public function obterDadosIbsCbs($idNotaItem, $campos = "*")
	{
		return dbFastQuery("SELECT {$campos} FROM notas_itens_ibs_cbs WHERE id_notas_itens = '{$idNotaItem}'")[0];
	}


	public function obtemDadosNFE($id, $campos="N.*, NFI.descricao informacaoFisco")
	{
		$sql = "SELECT
					{$campos}
				FROM notas N
				LEFT JOIN nfe_informacoes NFI ON N.id_nfe_informacoes = NFI.id
				WHERE N.id = {$id}";
		return dbFastQuery($sql)[0];
	}


	public function comboIdentificadorDestino()
	{
		return (json_encode(array(1=>"1 - Operação interna",2=>"2 - Operação interestadual","3 - Operação com Exterior")));
	}


	public function comboIndentificadorIntermediario()
	{
		return (json_encode(array(0=>"0 - Operação sem intermediador",1=>"1 - Operação em site ou plataforma de terceiros")));
	}


	public function comboIE()
	{
		return (json_encode(array(1=>"1 - Contribuente do ICMS", 9=>"9 - Não contribuente")));
	}


	public function comboFinalidadeEmissao()
	{
		return (json_encode(array(1=>"1 - NF-e normal", 2=>"2 - NFe complementar",3=>"3 - NFe de ajuste", 4=>"4 - Devolução/Retorno")));
	}


	public function modalidadeFrete()
	{
		return (json_encode(array(0=>"0 - Por conta do emitente", 1=>"1 - Por conta do destinatário/remetente", 2=>"2 - Por conta de terceiros", 9=>"9 - Sem frete")));
	}


	public function tipoPagamento()
	{
		return json_encode(array(
			01  => "01 - Dinheiro",
			02  => "02 - Cheque",
			03  => "03 - Cartão de Crédito",
			04  => "04 - Cartão de Débito",
			05  => "05 - Cartão da Loja / Crediário",
			10 => "10 - Vale Alimentação",
			11 => "11 - Vale Refeição",
			12 => "12 - Vale Presente",
			13 => "13 - Vale Combustível",
			14 => "14 - Duplicata Mercantil",
			15 => "15 - Boleto Bancário",
			16 => "16 - Depósito Bancário",
			17 => "17 - PIX Dinâmico",
			18 => "18 - Transferência Bancária / Carteira Digital",
			19 => "19 - Programa de Fidelidade / Cashback / Crédito Virtual",
			20 => "20 - PIX Estático",
			21 => "21 - Crédito em Loja",
			22 => "22 - Pagamento eletrônico não informado",
			90 => "90 - Sem Pagamento",
			91 => "91 - Pagamento Posterior",
			99 => "99 - Outros"
		));
	}


	public function obtemDadosItensNFE($idNota)
	{
		$dadosNotaNFE = $this->obtemDadosNFE($idNota);
		$totais = $this->totaisNota($idNota);
		$ttlItens = count(dbQuery("SELECT * FROM notas_itens where id_notas='{$idNota}'"));
		$vSegItem = $dadosNotaNFE["vSeg"] / $ttlItens;
		$vOutroItem = $dadosNotaNFE["vOutro"] / $ttlItens;
		$vDescItem  = $dadosNotaNFE["vDesc"] / $ttlItens;
		$vFreteItem = $dadosNotaNFE["vFrete"] / $ttlItens;

		$sql = "SELECT
					n.id_pessoas_cliente,
					n.id_pessoas_proprietario,
					i.codigo,
					i.codigo_barras ean,
					i.ncm,
					cf.codigo cfop,
					CFOP_ITEM.codigo cfop_saida,
					i.nome descricao,
					GC.codigo cProdANP,
					GC.descricao descANP,
					ni.CODIF,
					ni.UFCons,
					GPI.codigo cProdANP_item,
					GPI.descricao descANP_item,
					'" . $totais["pesoL"] . "' pesoLiquido,
					'" . $totais["pesoB"] . "' pesoBruto,
					ni.quantidade,
					u.sigla unidade,
					ni.valor,
					iic.codigo as icms_cst,
					ico.codigo as origem,
					icm.codigo as icms_modalidadebc,
					niic.id_imp_icms_mod,
					niic.reducao_icms_aliquota,
					niic.pICMS aliquotaICMS,
					niic.vICMSSTRet,
					niic.pICMSST,
					niic.vBC baseICMS,
					niic.vBC,
					niic.modBCST,
					niic.pMVAST,
					niic.pRedBCST,
					niic.vBCST,
					niic.vBCFCP,
					niic.pFCP,
					niic.vBCFCPST,
					niic.pFCPST,
					niic.pICMS,
					nii.pIPI pIPI,
					imp_ipi_cst.codigo ipi_cst,
					nii.cEnq ipi_cEnq,
					icc.codigo as cofins_cst,
					nic.pCOFINS as pCOFINS,
					icp.codigo as pis_cst,
					nip.pPIS as pPIS,
					NIM.cProdANVISA,
					NIM.xMotivoIsencao,
					NIM.VPMC,
					NIM.nLote,
					NIM.qLote,
					NIM.dFab,
					NIM.dVal,
					NIM.cAgreg,
					'{$vDescItem}' desconto,
					'{$vFreteItem}' frete,
					'{$vSegItem}' seguro,
					'{$vOutroItem}' outrasDespesas,
					n.pesoB totalPesoLiquido,
					n.pesoL totalPesoBruto,
					n.emails_enviar AS emails_nota,
					p.email AS email_proprietario,
					ne.numero NumeroEntrada,
					notas_itens_ibs_cbs.vBC AS vbc_ibs_cbs,
					notas_itens_ibs_cbs.gIBSUF_pIBSUF,
					notas_itens_ibs_cbs.gIBSUF_pRedAliq,
					notas_itens_ibs_cbs.gIBSUF_pAliqEfet,
					notas_itens_ibs_cbs.gIBSUF_vIBSUF,
					notas_itens_ibs_cbs.gIBSUF_pDif,
					notas_itens_ibs_cbs.gIBSUF_vDif,
					notas_itens_ibs_cbs.gIBSUF_vDevTrib,
					notas_itens_ibs_cbs.gIBSMun_pIBSMun,
					notas_itens_ibs_cbs.gIBSMun_pRedAliq,
					notas_itens_ibs_cbs.gIBSMun_pAliqEfet,
					notas_itens_ibs_cbs.gIBSMun_vIBSMun,
					notas_itens_ibs_cbs.gIBSMun_pDif,
					notas_itens_ibs_cbs.gIBSMun_vDif,
					notas_itens_ibs_cbs.gIBSMun_vDevTrib,
					notas_itens_ibs_cbs.gCBS_pCBS,
					notas_itens_ibs_cbs.gCBS_pRedAliq,
					notas_itens_ibs_cbs.gCBS_pAliqEfet,
					notas_itens_ibs_cbs.gCBS_vCBS,
					notas_itens_ibs_cbs.gCBS_pDif,
					notas_itens_ibs_cbs.gCBS_vDif,
					notas_itens_ibs_cbs.gCBS_vDevTrib,
					notas_itens_ibs_cbs.pRedutorBC,
					notas_itens_ibs_cbs.qBCMono,
					notas_itens_ibs_cbs.adRemIBS,
					notas_itens_ibs_cbs.vIBSMono,
					notas_itens_ibs_cbs.adRemCBS,
					notas_itens_ibs_cbs.vCBSMono,
					notas_itens_ibs_cbs.vIBSTransf,
					notas_itens_ibs_cbs.vCBSTransf,
					notas_itens_ibs_cbs.tpCredPresIBSZFM,
					notas_itens_ibs_cbs.vCredPresIBSZFM,
					notas_itens_ibs_cbs.competApur,
					notas_itens_ibs_cbs.vIBSAjuste,
					notas_itens_ibs_cbs.vCBSAjuste,
					notas_itens_ibs_cbs.indDoacao,
					imp_ibs_cbs_cst.codigo AS cst_ibs_cbs,
					cclasstrib_ibs_cbs.codigo AS cclasstrib_ibs_cbs
				FROM notas n
				LEFT JOIN notas_itens ni ON n.id = ni.id_notas
				LEFT JOIN pessoas p ON p.id = n.id_pessoas_proprietario
				LEFT JOIN notas_itens_ipi nii ON nii.id_notas_itens = ni.id
				LEFT JOIN imp_ipi_cst ON imp_ipi_cst.id = nii.id_imp_ipi_cst
				LEFT JOIN notas ne ON ni.id_notas_associada = ne.id
				LEFT JOIN grupos_combustivel GC ON ni.id_grupos_combustivel = GC.id
				LEFT JOIN notas_itens_icms niic ON niic.id_notas_itens = ni.id
				LEFT JOIN imp_icms_cst iic ON iic.id = niic.id_imp_icms_cst
				LEFT JOIN imp_icms_origem ico ON ico.id = niic.id_imp_icms_origem
				LEFT JOIN imp_icms_mod icm ON icm.id = niic.id_imp_icms_mod
				LEFT JOIN notas_itens_pis nip ON nip.id_notas_itens = ni.id
				LEFT JOIN imp_pis_cst icp ON icp.id = nip.id_imp_pis_cst
				LEFT JOIN notas_itens_cofins nic ON nic.id_notas_itens = ni.id
				LEFT JOIN imp_cofins_cst icc ON icc.id = nic.id_imp_cofins_cst
				LEFT JOIN cfops cf ON n.id_cfops = cf.id
				LEFT JOIN notas_itens_medicamentos NIM ON NIM.id_notas_itens = ni.id
				LEFT JOIN cfops CFOP_ITEM ON CFOP_ITEM.id = ni.id_cfops
				LEFT JOIN itens_skus sk ON ni.id_itens_skus = sk.id
				LEFT JOIN itens i ON i.id = sk.id_itens
				LEFT JOIN grupos_combustivel GPI ON GPI.id = i.id_grupos_combustivel
				LEFT JOIN unidades u ON sk.id_unidades = u.id
				LEFT JOIN notas_itens_ibs_cbs ON notas_itens_ibs_cbs.id_notas_itens = ni.id
				LEFT JOIN imp_ibs_cbs_cst ON notas_itens_ibs_cbs.id_imp_ibs_cbs_cst = imp_ibs_cbs_cst.id
				LEFT JOIN cclasstrib_ibs_cbs ON notas_itens_ibs_cbs.id_cclasstrib_ibs_cbs = cclasstrib_ibs_cbs.id
				WHERE ni.id_notas='{$idNota}'
				GROUP BY ni.id
				ORDER BY ni.id";
		return dbFastQuery($sql);

	}

	public function totaisNota($id)
	{
		$sql="SELECT peso_bruto, peso_liquido, quantidade, valor FROM notas_itens WHERE id_notas = '{$id}'";
		$itens=dbQuery($sql);
		$totais=[];
		foreach ($itens as $item) {
			$totais["quantidade"]+=$item["quantidade"];
			$totais["pesoL"]+=($item["peso_liquido"]*$item["quantidade"]);
			$totais["pesoB"]+=($item["peso_bruto"]*$item["quantidade"]);
			$totais["vProd"]+=($item["valor"]*$item["quantidade"]);
		}
		return ($totais);
	}


	public function obtemDadosEmpresa($idFilial)
	{
		$sql = "SELECT
					filial.razao_social AS razaoSocial,
					filial.razao_social AS nomeFilial,
					filial.cnpj AS cnpjFilial,
					filial.insc_estadual AS inscricaoEstadualFilial,
					filial.insc_municipal AS inscricaoMunicipalFilial,
					filial.cnae AS cnaeFilial,
					filial.endereco AS enderecoFilial,
					filial.numero AS numeroFilial,
					filial.complemento AS enderecoComplementoFilial,
					filial.bairro AS enderecoBairroFilial,
					filial.cep AS cepFilial,
					filial.telefone AS telefoneFilial,
					est.codigo_ibge AS codigoIbgeEstado,
					est.sigla AS siglaUf,
					mun.codigo_ibge AS codigoIbgeMunicipio,
					mun.descricao AS municipioDescricao,
					pais.nome AS xPais,
					pais.codigo AS cPais
				FROM filial
				LEFT JOIN enderecos_estados est ON filial.id_enderecos_estados = est.id
				LEFT JOIN enderecos_cidades mun ON filial.id_enderecos_cidades = mun.id
				LEFT JOIN enderecos_paises pais ON pais.id = est.id_enderecos_paises
				WHERE filial.id = '{$idFilial}';";
		return dbQuery($sql)[0];
	}


	public function buscarConfiguracoes($cnpj)
	{
		$sql = "SELECT
                    schemes,
                    tpAmb,
                    regime,
                    versao_xml,
					senha_certificado,
					usa_contingencia_ibs_cbs,
					desativar_impostos_antigos,
					ativar_modo_contingencia,
					data_alteracao_operacao,
					serie
                FROM filial_notas WHERE cnpj = '{$cnpj}'";
		return dbFastQuery($sql)[0];
	}


	public function obtemNumeroSequencial($idNfe)
	{
		$sql = "SELECT
					COALESCE(MAX(sequencial), 0) AS max_sequencial
				FROM nfe_eventos
				WHERE id_nfe = {$idNfe}
					AND sucesso = 1
				LIMIT 1";
		$rs = dbQuery($sql)[0];

		$numeroSequencial = (int) ($rs['max_sequencial']) + 1;
		return $numeroSequencial;
	}


	public function salvarNotasItensEImpostos($idNotaOrigem, $idNovaNota)
	{
		$sql = "SELECT * FROM notas_itens WHERE id_notas IN (" . $idNotaOrigem . ") ORDER BY id";
        $notasItensSelecionados = dbFastQuery($sql);

		$idsNotasItensOrigem = implode(",", array_column($notasItensSelecionados, 'id'));

        foreach ($notasItensSelecionados as $item) {
            $item = excluirIndicesNumericos($item);
            unset($item['id']);
            $item['id_notas'] = $idNovaNota;
            $item['aliquota_ipi']    = $item['aliquota_ipi']    ?: '0';
            $item['total_icms_calc'] = $item['total_icms_calc'] ?: '0';

            $sqlItens .= "('" . implode("','", array_values($item)) . "'),";
        }

        $sql = "INSERT INTO notas_itens (".implode(',', array_keys($item)).") VALUES ";
        $sql = $sql . substr($sqlItens, 0, -1);
        dbFastQuery($sql);

        $idsNotasItensNovo = dbFastQuery("SELECT id FROM notas_itens WHERE id_notas = {$idNovaNota} ORDER BY id");
        $idsNotasItensNovo = array_column($idsNotasItensNovo, 'id');

		// ICMS
        $sql = "SELECT * FROM notas_itens_icms WHERE id_notas_itens IN (" . $idsNotasItensOrigem . ") ORDER BY id";
        $notasItensIcmsSelecionados = dbFastQuery($sql);

        $sqlItens = '';
        foreach ($notasItensIcmsSelecionados as $key => $itemIcms) {
            $itemIcms = excluirIndicesNumericos($itemIcms);
            unset($itemIcms['id']);
            $itemIcms['id_notas_itens'] = $idsNotasItensNovo[$key];
            $sqlItens .= "('" . implode("','", array_values($itemIcms)) . "'),";
        }

        $sql = "INSERT INTO notas_itens_icms (".implode(',', array_keys($itemIcms)).") VALUES ";
        $sql = $sql . substr($sqlItens, 0, -1);
        dbFastQuery($sql);

		// PIS
        $sql = "SELECT * FROM notas_itens_pis WHERE id_notas_itens IN (" . $idsNotasItensOrigem . ") ORDER BY id";
        $notasItensPisSelecionados = dbFastQuery($sql);

        $sqlItens = '';
        foreach ($notasItensPisSelecionados as $key => $itemPis) {
            $itemPis = excluirIndicesNumericos($itemPis);
            unset($itemPis['id']);
            $itemPis['id_notas_itens'] = $idsNotasItensNovo[$key];
            $sqlItens .= "('" . implode("','", array_values($itemPis)) . "'),";
        }

        $sql = "INSERT INTO notas_itens_pis (".implode(',', array_keys($itemPis)).") VALUES ";
        $sql = $sql . substr($sqlItens, 0, -1);
        dbFastQuery($sql);

		// COFINS
        $sql = "SELECT * FROM notas_itens_cofins WHERE id_notas_itens IN (" . $idsNotasItensOrigem . ") ORDER BY id";
        $notasItensCofinsSelecionados = dbFastQuery($sql);

        $sqlItens = '';
        foreach ($notasItensCofinsSelecionados as $key => $itemCofins) {
            $itemCofins = excluirIndicesNumericos($itemCofins);
            unset($itemCofins['id']);
            $itemCofins['id_notas_itens'] = $idsNotasItensNovo[$key];
            $sqlItens .= "('" . implode("','", array_values($itemCofins)) . "'),";
        }

        $sql = "INSERT INTO notas_itens_cofins (".implode(',', array_keys($itemCofins)).") VALUES ";
        $sql = $sql . substr($sqlItens, 0, -1);
        dbFastQuery($sql);

		// IBS/CBS
        $sql = "SELECT * FROM notas_itens_ibs_cbs WHERE id_notas_itens IN (" . $idsNotasItensOrigem . ") ORDER BY id";
        $notasItensIbsCbsSelecionados = dbFastQuery($sql);

        if ($notasItensIbsCbsSelecionados) {
	        $sqlItens = '';
	        foreach ($notasItensIbsCbsSelecionados as $key => $itemIbsCbs) {
	            $itemIbsCbs = excluirIndicesNumericos($itemIbsCbs);
	            unset($itemIbsCbs['id']);
	            $itemIbsCbs['id_notas_itens'] = $idsNotasItensNovo[$key];
	            $sqlItens .= "('" . implode("','", array_values($itemIbsCbs)) . "'),";
	        }

	        $sql = "INSERT INTO notas_itens_ibs_cbs (".implode(',', array_keys($itemIbsCbs)).") VALUES ";
	        $sql = $sql . substr($sqlItens, 0, -1);
	        dbFastQuery($sql);
        }
	}


	public function obterDadosNfeNumeroEOperacao($dadosFilialNotas)
	{
        if ($dadosFilialNotas['ativar_modo_contingencia']) {
			// (Depois precisaremos colocar uma tratativa para quando tivermos estados que não usam o 7, atualmente era fixo esse 7)
            $dados['modoOperacao'] = "7"; // 3 = SCAN, 6 = SVCAN, 7 = SVCRS
            $dados['dataHoraContingencia'] = str_replace(" ", "T", $dadosFilialNotas['data_alteracao_operacao']).date("P");
        } else {
            $dados['dataHoraContingencia'] = "";
            $dados['modoOperacao'] = "1"; // 1 = Normal
            $dados['serie'] = "1";
        }

        $sql = "SELECT id, numero, serie
				FROM nfe_numeros
				WHERE id_filial = " . $_SESSION['filialAtualId'] . " AND serie = '" . $dadosFilialNotas['serie'] . "'";
        $dadosNfeNumeros = dbFastQuery($sql)[0];

        $numero = (int) $dadosNfeNumeros['numero'] + 1;
		if (!$dadosNfeNumeros) {
			$numero = 1;
			$mtz = [];
			$mtz['id_filial'] = $_SESSION['filialAtualId'];
			$mtz['numero'] = $numero;
			$mtz['serie'] = $dadosFilialNotas['serie'];
			dbInsert('nfe_numeros', $mtz);
		} else {
			$sql = "UPDATE nfe_numeros SET numero = {$numero} WHERE id = " . $dadosNfeNumeros['id'];
			dbFastQuery($sql);
		}

        $dados['idNfeNumeros'] = $dadosNfeNumeros['id'];
        $dados['numeroNota'] = $numero;
        $dados['serie'] = $dadosNfeNumeros['serie'] ?: $dadosFilialNotas['serie'];

		return $dados;
	}


	public function prepararCamposNfe($dados)
	{
		$dadosNfe = [];
        $dadosNfe['data']        = date("Y-m-d H:i:s");
        $dadosNfe['numero']      = $dados['NfeNumeroEOperacao']["numeroNota"];
        $dadosNfe['chave']       = '';
        $dadosNfe['situacao']    = 'Submetida';
        $dadosNfe['data_recibo'] = '0000-00-00 00:00:00';
        $dadosNfe['id_empresa']  = $dados['idEmpresa'];
        $dadosNfe['id_cliente']  = $dados["cliente"]['idCliente'];
        $dadosNfe['id_pessoa']   = $_SESSION['usrId'];
        $dadosNfe['modo_operacao'] = $dados['NfeNumeroEOperacao']["modoOperacao"];
        $dadosNfe['cancelada']   = 0;
        $dadosNfe['id_notas'] = $dados['nota']["id"];
		return $dadosNfe;
	}


	public function converterCfopSaidaParaEntrada($cfop)
	{
		$primeiro = substr($cfop, 0, 1);
		if (strlen($cfop) !== 4 || ($primeiro <> 5 && $primeiro <> 6)) {
			return $cfop;
		}

		return [
            5 => '1905', // Estorno tratasse de uma devolução para meu estoque contabil (Entrada), todos CFOPs de entrada finalizam-se em 05. 1905 estadual
			6 => '2905'  // Estorno tratasse de uma devolução para meu estoque contabil (Entrada), todos CFOPs de entrada finalizam-se em 05. 2905 estadual
		][$primeiro];
	}


	function tratarCNPJCPF($cnpj_cpf)
	{
		$documento = str_replace("-", "", $cnpj_cpf);
		$documento = str_replace(".", "", $documento);
		$documento = str_replace("/", "", $documento);
		return ($documento);
	}

	function obtemDadosProprietario($idProprietario, $tipo='S')
	{
		if ($tipo == 'S' || $tipo == 'E') {
			$sql = "SELECT
						p.id idCliente,
						j.razao_social razaoSocial,
						pf.cpf,
						p.nome empresa,
						j.cnpj cnpj,
						j.insc_estadual inscricaoEstadual,
						j.insc_municipal inscricaoMunicipal,
						e.endereco endereco,
						e.numero enderecoNumero,
						e.complemento enderecoComplemento,
						e.bairro enderecoBairro,
						mun.codigo_ibge enderecoIbgeMunicipio,
						mun.descricao enderecoMunicipio,
						est.sigla enderecoUf,
						e.cep enderecoCep,
						p.telefone telefone,
						pai.nome enderecoPais,
						pai.codigo enderecoCodigoPais
					FROM pessoas p
					LEFT JOIN pessoas_enderecos e ON e.id_pessoas=p.id
					LEFT JOIN pessoas_juridicas j ON p.id=j.id_pessoas
					LEFT JOIN pessoas_fisicas pf ON pf.id_pessoas=p.id
					LEFT JOIN enderecos_estados est ON e.id_enderecos_estados=est.id
					LEFT JOIN enderecos_cidades mun ON e.id_enderecos_cidades=mun.id
					LEFT JOIN enderecos_paises pai ON pai.id = e.id_enderecos_paises
					WHERE p.id='{$idProprietario}'
					ORDER BY aplicacao DESC";
		} else {
			$sql = "SELECT
						A.id idCliente,
						A.razao_social razaoSocial,
						A.cnpj,
						A.insc_estadual inscricaoEstadual,
						A.insc_municipal inscricaoMunicipal,
						A.endereco endereco,
						A.numero enderecoNumero,
						A.complemento enderecoComplemento,
						A.bairro enderecoBairro,
						A.telefone telefone,
						A.cep enderecoCep,
						mun.codigo_ibge enderecoIbgeMunicipio,
						mun.descricao enderecoMunicipio,
						est.sigla enderecoUf
					FROM filial A
					LEFT JOIN enderecos_estados est ON A.id_enderecos_estados = est.id
					LEFT JOIN enderecos_cidades mun ON A.id_enderecos_cidades = mun.id
					WHERE A.id=".$idProprietario;
		}

		$dados_proprietario=(dbQuery($sql)[0]);
		$cnpj_cpf=$this->tratarCNPJCPF($dados_proprietario["cnpj"]);
		if (strlen($cnpj_cpf) == 11) {
			$dados_proprietario["cpf"]=$cnpj_cpf;
			$dados_proprietario["cnpj"]='';
		}

		return ($dados_proprietario);
	}


	public function obtemDadosTransportadora($idTransportadora)
	{
		$sql = "SELECT
					j.razao_social transportadoraRazaoSocial,
					p.nome transportadoraNome,
					j.cnpj transportadoraCnpj,
					f.cpf transportadoraCpf,
					j.insc_estadual transportadoraInscricaoEstadual,
					j.insc_municipal transportadoraInscricaoMunicipal,
					e.endereco transportadoraEndereco,
					e.numero transportadoraEnderecoNumero,
					e.complemento transportadoraEnderecoComplemento,
					e.bairro transportadoraEnderecoBairro,
					mun.codigo_ibge transportadoraEnderecoIbgeMunicipio,
					mun.descricao transportadoraEnderecoMunicipio,
					est.sigla transportadoraEnderecoUf,
					e.cep transportadoraEnderecoCep,
					p.telefone transportadoraTelefone,
					'$modFrete' modalidadeFrete
				FROM pessoas p
				LEFT JOIN pessoas_enderecos e ON e.id_pessoas = p.id
				LEFT JOIN pessoas_juridicas j ON p.id = j.id_pessoas
				LEFT JOIN pessoas_fisicas f ON p.id = f.id_pessoas
				LEFT JOIN enderecos_estados est ON e.id_enderecos_estados = est.id
				LEFT JOIN enderecos_cidades mun ON e.id_enderecos_cidades = mun.id
				WHERE p.id = '{$idTransportadora}'
				ORDER BY aplicacao DESC";
		return (dbFastQuery($sql)[0]);
	}


	public function enviarEmail($idNfe, $xml = '')
	{

		$sql = "SELECT
					N.id,
					N.tipo,
					N.emails_enviar AS emails_nota,
					P.email AS email_proprietario,
					NF.chave,
					N.id_pessoas_proprietario,
					NF.data_recibo,
					A.descricao filial,
					nfe_eventos.xml
				FROM nfe NF
				JOIN notas N ON N.id = NF.id_notas
				LEFT JOIN nfe_eventos ON nfe_eventos.id_nfe = NF.id
				LEFT JOIN pessoas P ON P.id = N.id_pessoas_proprietario
				LEFT JOIN filial A ON A.id = N.id_filial
				WHERE NF.id = " . $idNfe;
		$nota = dbFastQuery($sql)[0];

		$erros = [];
		if (!$nota['id']) {
			$erros[] = "Nota não encontrada";
			return $erros;
		}

		$remetente = gVar("smtp.from");
		if ($nota['emails_nota']) {
			$para = $nota["emails_nota"];
		} else {
			$para = $nota['email_proprietario'];
			dbQuery("UPDATE notas SET emails_enviar = '{$para}' WHERE id = " . (int) $nota['id']);
		}

		$para = str_replace(" ", "", $para);
		if (is_null($para) || empty($para)) {
			$erros[] = 'Endereço de destino do e-mail não foi informado';
			return $erros;
		}

		$dadosDanfe = [];
        $dadosDanfe['temRetorno'] = 1;
        $dadosDanfe['xml'] = $xml ?: $nota['xml'];
        $dadosDanfe['chave'] = $nota['chave'];
        $dadosDanfe['idPessoasProprietario'] = 1;
        $dadosDanfe['empresa'] = $this->obtemDadosEmpresa(obtemIdEmpresa($nota['id_pessoas_proprietario']));
        $dadosDanfe['config'] = $this->buscarConfiguracoes($dadosDanfe['empresa']['cnpjFilial']);
        $retornoGerarDanfe = dispararGatilho('gerarDanfe', $dadosDanfe);

        if ($retornoGerarDanfe['erroCurl'] && !$retornoGerarDanfe['resposta']) {
            if (!empty($retornoGerarDanfe['erroCurl'])) {
                $erros[] = gCleanField($retornoGerarDanfe['erroCurl']);
				return $erros;
            }
        }

        $retornoGerarDanfe = json_decode($retornoGerarDanfe['resposta'], true);
        if ($retornoGerarDanfe['sucesso']) {
			$anexo = [];
        	$arquivo =  '/tmp/' . $nota['chave'] . "-nfe.xml";
			file_put_contents($arquivo, $nota["xml"]);
			$anexo[] = $arquivo;

            $pdf = base64_decode($retornoGerarDanfe['detalhes']['pdf_base64']);
            $arquivo =  '/tmp/' . $nota['chave'] . "-nfe.pdf";
			file_put_contents($arquivo, $pdf);
			$anexo[] = $arquivo;
        } else {
            $erros[] = "E-mail não enviado - " . ($retornoGerarDanfe['mensagem'] ?: 'Arquivos XML e PDF não gerados');
            return $erros;
        }

        $conteudo  =
			" Prezado cliente, \n\nSegue em anexo o danfe e o arquivo xml referente a Nota Fiscal Eletrônica abaixo:\n\n"
			. "Data: " . date('d/m/Y H:i', strtotime($nota["data_recibo"]))
			. "\nChave: " . $nota["chave"]
			. "\n" . $dadosDanfe['empresa']['razaoSocial']
			. "\n<font size=1>E-mail enviado automaticamente. Gentileza não responder.</font></i>";
		$assunto = gVar("global.site") . " " . $nota["filial"] . " NFe";
		$emailFoiEnviado = gSendEmail($remetente, $para, $cc, $assunto, $conteudo, $anexo);
		if (!$emailFoiEnviado) {
			$erros[] = 'Erro ao enviar email';
			return $erros;
		}

		return true;
	}


	public function buscarRefNfe($idNotaFiscal)
	{
		$sql = "SELECT refNfe FROM notas WHERE id = " . (int) $idNotaFiscal;
		return dbQuery($sql)[0]["refNfe"] ?: '';
	}

	public function gerarFormularioImportacaoItens($o, $backButton)
	{
		$frm = new gForm();
		$frm->row(
			$frm->add("{name: arquivo; fieldLabel: Arquivo CSV; allowBlank: false; type: file;}"),
			$frm->add('{name: removerItens; fieldLabel: Remover itens desta nota; type: checkbox; value: 1;}')
		);
		$frm->add("{name: idPessoasProprietario; type: hidden; value: " . $_REQUEST['idPessoasProprietario'] ." ;}");
		$frm->add("{name: id_notas; type: hidden; value: " . $_REQUEST['gId'] ." ;}");
		$frm->add("{name: importar; type: hidden; value: " . 1 ." ;}");
		$frm->addButton("{icon: download; title: Baixar modelo CSV; hint: Baixar modelo CSV; style: info; size: normal; href: " . $o->page . "&gPage=" . FORMULARIO_IMPORTAR_ITENS_NOTA . "&modelo=SKU,quantidade,unidade,precoUnitario");

		return $frm->render($o);
	}


	public function importaItensFormulario($o, $backButton)
	{
		if ($_FILES['arquivo']['type'] != "text/csv") {
			$html = $o->msgDanger('O arquivo importado deve ser um arquivo CSV');
			$html .= $backButton;
			return $html;
		}

		$file = file_get_contents ($_FILES['arquivo']['tmp_name']);

		$erros = [];
		$arrayInsert = [];
		$infoNota = explode("\n", $file);
		$unidadeVerificada = [];
		$unidadeInexistente = [];
		$skuVerificado = [];
		$skuInexistente = [];

		foreach ($infoNota as $key => $itemNota) {
			$linha = explode(";", $itemNota);

			if ($_REQUEST['idPessoasProprietario'] == 116) {

				if (!(is_numeric($linha[0]))) {
					continue;
				}

				if (count($linha) < 13) { //13 = quantidade de colunas de arquivo da Marilan
					$erros[] = "Modelo do arquivo é incompatível";
					break;
				}

				$sku = $linha[4];
				$quantidade = gDBFloat($linha[7]);
				$unidade = $linha[8];
				$precoUnitario = (gDBFloat($linha[9])/1000);

			} else {
				$sku = $linha[0];
				$quantidade = gDBFloat($linha[1]);
				$unidade = $linha[2];
				$precoUnitario = gDBFloat($linha[3]);
			}

			if ($unidadeInexistente[$unidade]) {
				continue;
			}

			if ($skuInexistente[$sku]) {
				continue;
			}

			if (!$unidadeVerificada[$unidade]) {
				$unidadeVerificada[$unidade] = dbFastQuery("SELECT id FROM unidades WHERE sigla = '{$unidade}'")[0]['id'] ?: 9999; //99999 = idProvisorio
				if ($unidadeVerificada[$unidade] == 9999) {
					$erros[] = "A unidade '{$unidade}' não foi encontrada nos cadastros de unidades. SKUs que constam neste arquivo nesta unidade devem ser alterados também. Ocorre na linha " . ($key+1);
					$unidadeInexistente[$unidade] = 1;
					continue;
				}
			}

			if (!$skuVerificado[$sku]) {
				$skuVerificado[$sku] = dbFastQuery("
					SELECT itens_skus.id
					FROM itens_skus
					JOIN itens ON
						itens.id = itens_skus.id_itens
					WHERE (itens_skus.codigo = '{$sku}' OR itens_skus.codigo_barras = '{$sku}')
						AND itens.ativo = 1
						AND itens.id_pessoas_proprietario = " . $_REQUEST['idPessoasProprietario'] . "
						AND itens_skus.id_unidades = " . $unidadeVerificada[$unidade]
					. " ORDER BY itens_skus.ativo DESC
					LIMIT 1")[0]['id'] ?: 9999;
				if ($skuVerificado[$sku] == 9999) {
					$erros[] = "O SKU {$sku} não foi encontrado ativo e com a unidade mencionada no arquivo, verifique o cadastro deste SKU. Ocorre na linha " . ($key+1);
					$skuInexistente[$sku] = 1;
					continue;
				}
			}


			if (!$precoUnitario) {
				$erros[] = "Campo de preço está vazio na linha " . ($key+1);
			}

			$arrayInsert[] = "('". $_REQUEST['gId'] ."', '" . $skuVerificado[$sku] . "', '" . $unidadeVerificada[$unidade] . "', '{$quantidade}', '{$precoUnitario}')";
		}

		if ($erros) {
			$html .= $o->msgDanger('A importação não pode continuar pelos seguintes erros:');
			$html .= $o->tableBegin("big", true);
			$mtz = [];
			$mtz[] = "<-Erros";
			$html .= $o->tableRow($mtz, "header");

			foreach($erros as $erro) {
				$mtz = [];
				$mtz[] = "<-" . $erro;
				$html .= $o->tableRow($mtz, "detail");
			}

			$html .= $o->tableEnd();
			$html .= $backButton;
			return $html;
		}

		if (gDBCheck($_REQUEST['removerItens'])) {
			dbQuery('DELETE FROM notas_itens WHERE id_notas = ' . $_REQUEST['gId']);
		}

		$dadosInserts = implode(",", $arrayInsert);
		$inserindoItens = dbQuery("INSERT INTO notas_itens (id_notas, id_itens_skus, id_unidades, quantidade, valor) VALUES {$dadosInserts}");
		redirect($o->page . "&gPage=" . ITENS . "&gId=" . $_REQUEST['gId'] . "&importado=1");

	}

}

class ImportacaoNFE
{
	/*campos xml*/
	private $chamada;
	private $registrosXML;
	private $informacoesXML;
	private $clienteXML;
	private $xml;
	private $arquivo;

	/* cliente BD */
	private $clienteBD;

	/* tratamento */
	private $registros;
	private $cliente;
	private $endereco;
	private $clienteJuridico;
	private $nfe;
	public $nota;
	private $notaItem;
	private $item;
	private $sku;
	private $unidade;
	private $crossdocking;

	/* INFORMATIVO */
	private $erros;
	private $itens;
	private $itensExibir;
	private $clientes;
	private $notas;
	private $notasItens;
	private $skus;
	public $itensDuplicados=[];

	/* AUXILIADORES PARA ICMS IPI E PIS */
	private $icms;
	private $ipi;
	private $pis;
	private $cofins;
	private $ibsCbs;

	private $idProprietario;
	private $idPessoasFornecedor;

	public function __construct($xml="", $idProprietario=0, $xmlObject="")
	{
		$this->arquivo = $xml;
		$this->idPessoasFornecedor = (int) $_REQUEST['id_pessoas_fornecedor'];
		if (!empty($xmlObject)) {
			$xml = $xmlObject;
		} else {
			$xml = simplexml_load_string($xml);
		}

		$this->registrosXML= $xml->NFe->infNFe->det;
		$this->informacoesXML = $xml->NFe->infNFe->ide;
		$this->clienteXML= $xml->NFe->infNFe->emit;
		$this->transporteXML = $xml->NFe->infNFe->transp;
		$this->xml = $xml;

		/* IMPOSTOS */
		$this->icms   = [];
		$this->ipi 	  = [];
		$this->pis 	  = [];
		$this->cofins = [];
		$this->ibsCbs = [];

		$this->registros	   = [];
		$this->cliente 		   = [];
		$this->endereco 	   = [];
		$this->clienteJuridico = [];
		$this->item 		   = [];
		$this->sku 			   = [];
		$this->unidade 		   = [];
		$this->nfe 			   = [];
		$this->nota 		   = [];
		$this->notaItem 	   = [];

		/* INFORMATIVOS */
		$this->erros = [];
		$this->itens = [];

		/* id do proprietário */
		$this->idProprietario = $idProprietario;
	}

	public function conferirNota($chave)
	{
		$sql = "SELECT count(chave) as qtd, notas.id
				LEFT JOIN notas ON notas.id = nfe.id_notas
					AND notas.cancelada = 0
				WHERE nfe.chave = '{$chave}'
					AND nfe.cancelada = 0";
		$conferir = dbQuery($sql);
		return !($conferir[0]['qtd'] > 0 && $conferir[0]['id'] > 1);
	}


	/* MÉTODO PARA PRÉ EXIBIÇÃO */
	public function exibir($tipo, $comCliente)
	{
		$cliente = $this->checarEmitente(gCleanField($this->clienteXML->CNPJ), $this->idProprietario);

		$this->chamada="exibir";
		if (
			($comCliente && $cliente)
			|| ($comCliente && !$cliente)
			|| (!$comCliente && $cliente)
		) {
			$this->preparaCliente();
			$this->preparaClienteJuridico();
			$this->preparaNFE();
			$this->preparaNotaFiscal();
			$this->preparaEndereco();

			$this->agruparItensNota();

			foreach ($this->registrosXML as $row) {
				if (!$row) {
					continue; //row pode ser nulo ao agrupar itens da nota
				}

				$item = [];
				$item['nItem'] = (int) $row->attributes()->nItem;
				$item["codigo"] = gCleanField($row->prod->cProd);
				$item["item"] = gToUpper(gCleanField($row->prod->xProd));
				$item["cfop"] = gCleanField($row->prod->CFOP);
				$item["unidade"] = gCleanField($row->prod->uCom);
				$item["quantidade_comercial"] = gCleanField($row->prod->qCom);
				$item["valor_unidade_comercial"] = gCleanField($row->prod->vUnCom);
				$item["valor_produto"] = gCleanField($row->prod->vProd);
				$this->itensExibir[]=$item;
			}

			$chave = $this->validarChave($this->xml->NFe->infNFe["Id"]);
			$numeroNF = trim($this->xml->NFe->infNFe->ide->nNF);

			$this->checarDuplicidade($chave, $numeroNF);
		} else {
			$this->erros[] = "Não encontramos o cliente no sistema.";
		}
	}

	public function checarDuplicidade($chave, $numeroNF)
	{
		if (isset($this->xml->NFe->infNFe->ide->cNF)) {
			$where = [];
			$where[] = "(notas.numero='{$numeroNF}' AND notas.cancelada=0)";
			$where[] = "(notas.id_pessoas_proprietario='" . $this->idProprietario . "')";
			$where[] = "(notas.id_pessoas_fornecedor = '" . $this->idPessoasFornecedor . "')";
			$where[] = "(notas.tipo = '" . $this->nota['tipo'] . "')";
			$where = implode(" AND ", $where);
			$where = " OR (
					{$where}
				) ";
		}

		$sql = "SELECT notas.id AS id_notas, nfe.id AS id_nfe
				FROM nfe
				LEFT JOIN notas ON notas.id = nfe.id_notas
				WHERE (
						nfe.chave = '{$chave}'
						AND nfe.cancelada = 0
						AND notas.cancelada = 0
					)
				{$where}
				LIMIT 1";
		$nota = dbFastQuery($sql)[0];

		if ($nota['id_notas']) {
			$this->erros[] = "A nota fiscal já foi carregada anteriormente. Número da nota: " . linkParaNota($nota['id_notas'], $numeroNF, 'E');
			return;
		}

		if ($nota['id_nfe']) {
			$this->erros[] = "A nota fiscal já foi carregada anteriormente. Número da nota: " . linkParaNota($nota['id_nfe'], $numeroNF, 'E');
			return;
		}

	}


	public function atualizarGrupoCombustivel($idProprietario = 0)
	{
		// Recuperar notas_itens já cadastradas.
		$sql = "SELECT * FROM notas
				WHERE numero='".trim($this->xml->NFe->infNFe->ide->nNF)."'
					AND id_pessoas_proprietario=".intval($idProprietario)."
					AND cancelada=0";
		$existeNota=dbQuery($sql);
		$erros=[];
		if (!$existeNota) {
			$erros[]="Nenhuma NF encontrada.";
		} else {
			foreach ($this->registrosXML as $registro) {
				if (!$registro) {
					continue;//row pode ser nulo ao agrupar itens da nota
				}

				if (isset($registro->prod->comb)) {
					$where=[];
					$where[]="(N.id_pessoas_proprietario={$idProprietario})";
					$where[]="(SK.codigo='".trim($registro->prod->cProd)."')";
					$where[]="(U.sigla='".trim($registro->prod->uTrib)."')";
					$where[]="(N.numero='".trim($this->xml->NFe->infNFe->ide->nNF)."')";
					$where=implode(" AND ", $where);
					$sql = "SELECT
								NI.*,
								U.sigla, SK.codigo
							FROM notas_itens NI
							LEFT JOIN itens_skus SK ON SK.id = NI.id_itens_skus
							LEFT JOIN notas N ON N.id = NI.id_notas
							LEFT JOIN unidades U ON SK.id_unidades = U.id
							WHERE {$where}";
					$rs = dbQuery($sql);
					if ($rs) {
						foreach ($rs as $row) {
							$mtz=[];
							if (isset($registro->prod->comb->cProdANP)) {
								$mtz["cProdANP"]=trim($registro->prod->comb->cProdANP);
							}

							if (isset($registro->prod->comb->descANP)) {
								$mtz["descANP"]=trim($registro->prod->comb->descANP);
							}

							if (isset($registro->prod->comb->UFCons)) {
								$mtz["UFCons"]=trim($registro->prod->comb->UFCons);
							}

							if (isset($registro->prod->comb->CODIF)) {
								$mtz["CODIF"]=trim($registro->prod->comb->CODIF);
							}
							dbUpdate("notas_itens", $mtz, $row["id"]);
						}
					}
				}
			}
		}

		return ($erros);
	}

	/**
	 * Processa efetivamente a importação da NFe
	 * salvando no banco de dados a NFe e criando a nota nas tabelas
	 * 'notas' e 'notas_itens'
	 *
	 * @param string $tipo ?
	 * @param bool $comCliente Cadastrar o cliente automaticamente?
	 * @param integer $crossdocking É uma NFe de cross docking? Se for, tem agrupa e totaliza itens
	 * @return void
	 */
	public function processar($tipo, $comCliente = null, $crossdocking = 0)
	{
		$ok = true;
		$this->chamada="processar";
		$this->crossdocking = $crossdocking;

		$chave = $this->validarChave($this->xml->NFe->infNFe["Id"]);
		if ($this->conferirNota($chave)) {
			$this->clienteBD = $this->checarEmitente(gCleanField($this->clienteXML->CNPJ), $this->idProprietario);
			if (!$this->clienteBD) {
				if ($comCliente) {
					$this->inserirCliente();
				} else {
					$ok = false;
				}
			} else {
				$this->cliente =  $this->clienteBD;
				$this->cliente["automatico"] = false;
			}

			if ($ok) {
				$this->inserirNFE();
				$this->inserirNota();
				$this->inserirRegistros($tipo);

				return ($this->nfe["id"]);
			} else {
				$this->erros[] = "Não encontramos o cliente no sistema, se necessário ative a opção criar cliente automaticamente e tente novamente";
			}
		} else {
			$this->defineErros("Nota fiscal de chave {$chave} já foi carregada anteriormente.");
		}
	}


	public function montarCabecalhoConfirmacao($nota, $nfe, $cliente, $xml, $idProprietario=0)
	{
		global $o,$gPage, $gParam, $gId;
		$cfops = isset($xml->NFe->infNFe->det[0]->prod->CFOP) ? gCleanField($xml->NFe->infNFe->det[0]->prod->CFOP) : null;
		// $ni = new ImportacaoNFE();
		$cliente = $this->checarEmitente(gCleanField($xml->NFe->infNFe->emit->CNPJ), $idProprietario);
		$volume = gCleanField($xml->NFe->infNFe->transp->vol->qVol);
		$existeCliente = ($cliente) ? "SIM" : "NÃO";
		$destinatario = $this->checarDestinatario(gCleanField($xml->NFe->infNFe->dest->CNPJ));
		if(!$destinatario){
			$alertas[] = "A NF-e não tem como destinatário o filial atual. <br/>CNPJ Filial : ".gFieldById("filial",$_SESSION['filialAtualId'],'cnpj')."<br/>CNPJ NF-e&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ".gCleanField($xml->NFe->infNFe->dest->CNPJ);
		}

		if (gDBCheck($_REQUEST['agrupar_itens_nota'])) {
			$alertas[] = 'Agrupamento de itens por código e valor foi ativado';
		}

		if (gDBCheck($_REQUEST['importarComoSaida'])) {
			$alertas[] = 'Esta nota <b>SERÁ IMPORTADA COMO SAÍDA</b>, trata-se da ativação da flag Importar como saída que irá importar esta nota fiscal como sendo nota do tipo saída';
		}

		if ($alertas) {
			$html .= $o->msgAlert($o->ul($alertas));
		}

		$html.=$o->tableBegin("big");

		$mtz = [];
		$mtz[]='<-'. $o->small('Chave').'<br><b>'. $nfe['chave'].'</b>&nbsp;';
		$mtz[]='<-'. $o->small('Número').'<br><b><small>'. $nfe['numero'].'</small></b>&nbsp;';
		$mtz[]=$o->small('Série').'<br><b>'.$nfe['serie']."</b>&nbsp;";
		$mtz[]='<-'. $o->small('Protocolo').'<br><b>'.$nfe["protocolo"]."</b>&nbsp;";
		$mtz[]='<-'. $o->small('CFOPS').'<br><b>'.$cfops."</b>&nbsp;";
		$mtz[]='<-'. $o->small('Volume').'<br><b>'.$volume."<b>&nbsp;";
		$html.=$o->tableRow($mtz, 'header');

		$mtz = [];
		$mtz[]='~2<-'. $o->small('Data de emissão').'<br><b>'. gDate($nota['data_emissao']).'</b>&nbsp;';
		$mtz[]='<-'. $o->small('Data de movimento').'<br><b>'.gDate($nota['data_movimento'])."</b>&nbsp;";
		$mtz[]='<-'. $o->small('Data do recibo').'<br><b>'.gDateTime($nfe['data_recibo'])."</b>&nbsp;";
		$mtz[]='<-'. $o->small('Outras despesas').'<br><b>'.gFloat($nota['outras_despesas'])."</b>&nbsp;";
		$mtz[]='<-'. $o->small('Informações Adicionais').'<br><b>'.$nota['informacoes']."</b>&nbsp;";
		$html.=$o->tableRow($mtz, 'header');

		$mtz = [];
		$mtz[]='~2<-'. $o->small('Cliente existe no sistema ?').'<br><b>'. $existeCliente ."</b>&nbsp;";
		$mtz[]='<-'. $o->small('Cliente').'<br><b>'.$cliente['nome']."</b>&nbsp;";
		$mtz[]='<-'. $o->small('Telefone').'<br><b>'.$cliente['telefone']."</b>&nbsp;";
		$mtz[]='<-'. $o->small('Endereco').'<br><b>'.$xml->NFe->infNFe->emit->enderEmit->xLgr .", ". $xml->NFe->infNFe->emit->enderEmit->nro ."</b>&nbsp";
		$mtz[]='<-'. $o->small('Cidade/Estado').'<br><b>'.$xml->NFe->infNFe->emit->enderEmit->xMun ."/". $xml->NFe->infNFe->emit->enderEmit->UF  ."</b>&nbsp";
		$html.=$o->tableRow($mtz, 'header');
		$html.=$o->tableEnd();
		return ($html);
	}


	public function defineErros($erro)
	{
		$this->erros[] = $erro;
	}


	public function obtemErros()
	{
		return ($this->erros);
	}


	public function obtemCidade($codIbge)
	{
		$sql = "SELECT EC.id, EE.id as idEstado
		FROM enderecos_cidades EC
		INNER JOIN enderecos_estados EE on EE.id = EC.id_enderecos_estados
		WHERE EC.codigo_ibge = '$codIbge'";
		return (dbQuery($sql)[0]);
	}


	public function obtemEstado ($uf)
	{
		$sql = "SELECT id from enderecos_estados where sigla = '{$uf}'";
		return (dbQuery($sql)[0]);
	}


	public function obtemCliente()
	{
		return ($this->cliente);
	}


	public function obtemNota()
	{
		return ($this->nota);
	}


	public function obtemNFE()
	{
		return ($this->nfe);
	}


	public function obtemItens()
	{
		return ($this->itens);
	}


	public function obtemChamada()
	{
		return ($this->chamada);
	}


	public function obtemItensExibir()
	{
		return ($this->itensExibir);
	}


	public function obtemEndereco()
	{
		return ($this->endereco);
	}


	public function novaCidade($idEstado, $dados)
	{
		$mtz = [];
		$mtz["descricao"]=gCleanField($dados->xMun);
		$mtz["codigo_ibge"]=gCleanField($dados->cMun);
		$mtz["id_enderecos_estados"]=$idEstado;
	}


	public function obtemNotaDB($id)
	{
		$sql = "SELECT
					N.numero,
					N.data_emissao,
					N.data_movimento,
					N.data_criou,
					C.descricao as desc_cfops,
					NE.chave
				FROM notas N
				LEFT JOIN nfe NE  ON NE.id_notas = N.id
				LEFT JOIN cfops C ON C.id  = N.id_cfops
				WHERE N.id = '{$id}'";
		return (dbQuery($sql)[0]);
	}


	public function obtemNotaItemBD($id)
	{
		$sql = "SELECT
					U.descricao,
					U.sigla,
					I.nome,
					I.codigo,
					NI.quantidade,
					NI.id,
					NI.valor
				FROM notas_itens NI
				INNER JOIN itens_skus ISK ON ISK.id = NI.id_itens_skus
				INNER JOIN itens I ON I.id = ISK.id_itens
				INNER JOIN unidades U ON U.id = ISK.id_unidades
				WHERE NI.id = '{$id}'";
		return (dbQuery($sql)[0]);
	}


	public function checarItem($codigo)
	{
		/* Verificar o código com o mesmo proprietário */
		/* Se não achou o item com o código informado checar os códigos do sku */

		// Verifica se é de um fornecedor cadastrado
		$this->idItensSkusConverter = 0;
		$cnpj = gCleanField($this->xml->NFe->infNFe->dest->CNPJ);
		if ($_REQUEST['id_pessoas_fornecedor'] || gDBCheck($_REQUEST['importarComoSaida'])) {
			$cnpj = gCleanField($this->clienteXML->CNPJ);
		}
		$sql = "
			SELECT itens_fornecedores.id_itens_skus, itens.*
			FROM itens_fornecedores
			LEFT JOIN itens ON itens.id = itens_fornecedores.id_itens
			LEFT JOIN itens_skus ON itens_skus.id = itens_fornecedores.id_itens_skus
			WHERE itens_fornecedores.cnpj = '{$cnpj}'
				AND itens_fornecedores.codigo = '{$codigo}'
			ORDER BY itens.ativo DESC, itens_skus.ativo DESC
			LIMIT 1";
		if ($_REQUEST['id_pessoas_fornecedor']) {
			$sql = "
				SELECT itens_skus.id AS id_itens_skus, itens.*
				FROM itens
				JOIN itens_skus ON itens_skus.id_itens = itens.id
				WHERE itens.id_pessoas_proprietario = '" . $_REQUEST['id_pessoas_fornecedor'] . "'
					AND itens_skus.codigo = '{$codigo}'
					AND itens.ativo = 1
					AND itens_skus.ativo = 1
				LIMIT 1";
		}
		$rsf = dbFastQuery($sql)[0];
		if ($rsf['id_itens_skus']) {
			$this->idItensSkusConverter = $rsf['id_itens_skus'];
			return $rsf;
		}

		// Item do proprietario
		$sql = "
			SELECT I.*
			FROM itens I
			LEFT JOIN itens_skus SK ON I.id = SK.id_itens
			WHERE (
					I.codigo = '{$codigo}'
					OR I.codigo_barras = '{$codigo}'
					OR SK.codigo ='{$codigo}'
					OR SK.codigo_barras_alternativo = '{$codigo}'
				)
				AND I.id_pessoas_proprietario = '".($this->cliente["id"] ?: $_REQUEST['proprietario'])."'";
		if (gDBCheck($_REQUEST['priorizarSkuInativo'])) {
			$sql .= " ORDER BY I.ativo DESC, SK.ativo ASC";
		} else {
			$sql .= " ORDER BY I.ativo ASC, SK.ativo DESC";
		}
		$sql .= " LIMIT 1";
		$confereSku = dbQuery($sql);
		$this->checaDuplicidadeItems($codigo);
		if ($confereSku) {
			return ($confereSku[0]);
		}

		return 0;
	}


	public function checarTransportadora ($cnpj)
	{
		$sql = "SELECT * FROM pessoas P
				LEFT JOIN pessoas_juridicas PJ on P.id = PJ.id_pessoas
				WHERE transportadora = '1' and cnpj = '$cnpj'";
		$transportadora = dbQuery($sql);

		if (!$transportadora) {
			return false;
		}

		return $transportadora[0];
	}


	public function checarCfops($codigo)
	{
		$cfops=dbQuery("SELECT * FROM cfops WHERE codigo='{$codigo}'");
		if (!$cfops) {
			$mtz = [];
			$mtz["codigo"] = $codigo;
			$mtz["descricao"] = $codigo;
			$mtz["descricao_resumida"] = $codigo;
			$id = dbInsert("cfops", $mtz, true);
			$cfops = dbQuery("SELECT * FROM cfops WHERE id='{$id}'");
			return $cfops[0];
		}

		return $cfops[0];
	}


	public function checarUnidade ($unidade)
	{
		$unidade = $this->tiraEstranhos(gCleanField($unidade));
		$unidade = substr($unidade, 0, 3);

		$sql = "SELECT * FROM unidades where sigla = '$unidade'";
		$unidade = dbQuery($sql);
		if (!$unidade) {
			return false;
		}

		return ($unidade[0]);

	}


	public function checarSKU($idItem, $unidade)
	{

		$where = "
			id_itens = '{$idItem}'
			AND id_unidades = '{$unidade}'";

		if ($this->idItensSkusConverter) {
			$where = " id = " . $this->idItensSkusConverter;
			$this->idItensSkusConverter = 0;
		}

		$sql = "
			SELECT *
			FROM itens_skus
			WHERE {$where}";
		if (gDBCheck($_REQUEST['priorizarSkuInativo'])) {
			$sql .= " ORDER BY ativo ASC, id ASC";
		} else {
			$sql .= " ORDER BY ativo DESC, id ASC";
		}

		$sql .= " LIMIT 1 ";

		$sku = dbFastQuery($sql);

		return $sku[0];

	}


	public function checarEmitente ($cnpj, $idProprietario=0)
	{
		if (!empty($idProprietario)) {
			$where=" P.id = '{$idProprietario}' AND P.cliente='1'";
		} else {
			$where= " PJ.cnpj='{$cnpj}'";
		}

		$sql = "SELECT
					P.*,
					P.id  as idCliente,
					PJ.id as idClienteJuridico,
					PE.id as idEndereco
				FROM pessoas P
				LEFT JOIN pessoas_juridicas PJ on P.id = PJ.id_pessoas
				LEFT JOIN pessoas_enderecos PE ON P.id = PE.id_pessoas
				WHERE {$where}";
		$cliente = dbQuery($sql);
		if (!$cliente)
		{
			return false;
		}

		return ($cliente[0]);
	}


	public function checarDestinatario($cnpj, $idFilial=0)
	{
		if (!empty($idFilial)) {
			$where=" A.id = '{$idFilial}' ";
		} else {
			$where= " A.cnpj='{$cnpj}'";
		}

		$sql = "SELECT id FROM filial WHERE ((cnpj='{$cnpj}' AND id=".$_SESSION['filialAtualId'].") OR (cnpj='{$cnpj}' AND id=".(int) $idFilial.")) ";
		$rs = dbQuery($sql);
		if ($rs) {
			return true;
		}

		return false;
	}


	public function obtemChave()
	{
		return ($this->nfe["chave"]);
	}


	private function validarChave($chave)
	{
		return str_replace("NFe", "", gCleanField($chave));
	}


	public function validarGlobalICMS($icms)
	{
		$this->icms = [];

		$grupos = array(
			'ICMS00', 'ICMS10', 'ICMS20', 'ICMS30', 'ICMS40',
			'ICMS51', 'ICMS60', 'ICMS70', 'ICMS90', 'ICMSPart',
			'ICMSSN101', 'ICMSSN102', 'ICMSSN201', 'ICMSSN202',
			'ICMSSN500', 'ICMSSN900', 'ICMSST'
		);

		foreach ($grupos as $grupo) {
			if (isset($icms->$grupo)) {
				$this->validarICMS($icms->$grupo);
				return;
			}
		}
	}


	public function validarICMS($dadosICMS)
	{
		if (isset($dadosICMS->CST)) {
			$codigoCst = gCleanField($dadosICMS->CST);
			$rs = dbQuery("SELECT id FROM imp_icms_cst WHERE codigo = '{$codigoCst}'");
			if (count($rs) > 0) $this->icms["id_imp_icms_cst"] = $rs[0]["id"];
		}

		if (isset($dadosICMS->orig)) {
			$codigoOrigem = gCleanField($dadosICMS->orig);
			$rs = dbQuery("SELECT id FROM imp_icms_origem WHERE codigo = '{$codigoOrigem}'");
			if (count($rs) > 0) $this->icms["id_imp_icms_origem"] = $rs[0]["id"];
		}

		if (isset($dadosICMS->modBC)) {
			$codigoMod = gCleanField($dadosICMS->modBC);
			$rs = dbQuery("SELECT id FROM imp_icms_mod WHERE codigo = '{$codigoMod}'");
			if (count($rs) > 0) $this->icms["id_imp_icms_mod"] = $rs[0]["id"];
		}

		$mapaCampos = array(
			'vBC' => 'vBC',
			'pICMS' => 'pICMS',
			'pRedBC' => 'pRedBC',
			'pICMSST' => 'pICMSST',
			'vICMSST' => 'vICMSST',
			'vBCSTRet' => 'vBCSTRet',
			'vICMSSTRet' => 'vICMSSTRet',
			'modBCST' => 'modBCST',
			'pMVAST' => 'pMVAST',
			'pRedBCST' => 'pRedBCST',
			'vBCST' => 'vBCST',
			'vBCFCP' => 'vBCFCP',
			'pFCP' => 'pFCP',
			'vBCFCPST' => 'vBCFCPST',
			'pFCPST' => 'pFCPST'
		);

		foreach ($mapaCampos as $tagXML => $colunaBanco) {
			if (isset($dadosICMS->$tagXML)) {
				if ($tagXML == 'modBCST') {
					$this->icms[$colunaBanco] = gCleanField($dadosICMS->$tagXML);
				} else {
					$this->icms[$colunaBanco] = (float)($dadosICMS->$tagXML);
				}
			}
		}

		if (isset($dadosICMS->pRedBC)) {
			$this->icms['reducao_icms_aliquota'] = (float)($dadosICMS->pRedBC);
		}
	}


	public function validarIPI($ipi)
	{
		$this->ipi = [];

		if (isset($ipi->cEnq)) {
			$this->ipi["cEnq"] = gCleanField($ipi->cEnq);
		}

		$dadosIPI = null;
		if (isset($ipi->IPITrib)) {
			$dadosIPI = $ipi->IPITrib;
		} elseif (isset($ipi->IPINT)) {
			$dadosIPI = $ipi->IPINT;
		}

		if ($dadosIPI) {
			if (isset($dadosIPI->CST)) {
				$codigoCst = gCleanField($dadosIPI->CST);
				$rs = dbQuery("SELECT id FROM imp_ipi_cst WHERE codigo = '{$codigoCst}'");
				if (count($rs) > 0) $this->ipi["id_imp_ipi_cst"] = $rs[0]["id"];
			}

			if (isset($dadosIPI->vBC))  $this->ipi["vBC"]  = (float)($dadosIPI->vBC);
			if (isset($dadosIPI->pIPI)) $this->ipi["pIPI"] = (float)($dadosIPI->pIPI);
			if (isset($dadosIPI->vIPI)) $this->ipi["vIPI"] = (float)($dadosIPI->vIPI);
		}
	}


	public function validarPIS($pis)
	{
		$this->pis = [];

		$grupos = array('PISAliq', 'PISQtde', 'PISNT', 'PISOutr', 'PIS99');
		$dadosPIS = null;

		foreach ($grupos as $grupo) {
			if (isset($pis->$grupo)) {
				$dadosPIS = $pis->$grupo;
				break;
			}
		}

		if ($dadosPIS) {
			if (isset($dadosPIS->CST)) {
				$codigoCst = gCleanField($dadosPIS->CST);
				$rs = dbQuery("SELECT id FROM imp_pis_cst WHERE codigo = '{$codigoCst}'");
				if (count($rs) > 0) $this->pis["id_imp_pis_cst"] = $rs[0]["id"];
			}

			if (isset($dadosPIS->vBC))  $this->pis["vBC"]  = (float)($dadosPIS->vBC);
			if (isset($dadosPIS->pPIS)) $this->pis["pPIS"] = (float)($dadosPIS->pPIS);
			if (isset($dadosPIS->vPIS)) $this->pis["vPIS"] = (float)($dadosPIS->vPIS);
		}
	}


	public function validarCOFINS($cofins)
	{
		$this->cofins = [];

		$grupos = array('COFINSAliq', 'COFINSQtde', 'COFINSNT', 'COFINSOutr', 'COFINS99');
		$dadosCOFINS = null;

		foreach ($grupos as $grupo) {
			if (isset($cofins->$grupo)) {
				$dadosCOFINS = $cofins->$grupo;
				break;
			}
		}

		if ($dadosCOFINS) {
			if (isset($dadosCOFINS->CST)) {
				$codigoCst = gCleanField($dadosCOFINS->CST);
				$rs = dbQuery("SELECT id FROM imp_cofins_cst WHERE codigo = '{$codigoCst}'");
				if (count($rs) > 0) $this->cofins["id_imp_cofins_cst"] = $rs[0]["id"];
			}

			if (isset($dadosCOFINS->vBC))     $this->cofins["vBC"]     = (float)($dadosCOFINS->vBC);
			if (isset($dadosCOFINS->pCOFINS)) $this->cofins["pCOFINS"] = (float)($dadosCOFINS->pCOFINS);
			if (isset($dadosCOFINS->vCOFINS)) $this->cofins["vCOFINS"] = (float)($dadosCOFINS->vCOFINS);
		}
	}


	public function validarGlobalIBSCBS($nodeIBSCBS)
    {
        $this->ibsCbs = [];

        $cst = null;
        if (isset($nodeIBSCBS->CST)) {
            $cst = gCleanField($nodeIBSCBS->CST);
            $rs = dbQuery("SELECT id FROM imp_ibs_cbs_cst WHERE codigo = '{$cst}' LIMIT 1");
            if (count($rs) > 0) {
                $this->ibsCbs["id_imp_ibs_cbs_cst"] = $rs[0]["id"];
            }
        }

        if (isset($nodeIBSCBS->cClassTrib)) {
            $codigoClass = gCleanField($nodeIBSCBS->cClassTrib);
            $rs = dbQuery("SELECT id FROM cclasstrib_ibs_cbs WHERE codigo = '{$codigoClass}' LIMIT 1");
            if (count($rs) > 0) {
                $this->ibsCbs["id_cclasstrib_ibs_cbs"] = $rs[0]["id"];
            }
        }

        if (isset($nodeIBSCBS->indDoacao)) {
            $this->ibsCbs["indDoacao"] = (int) $nodeIBSCBS->indDoacao;
        }

        if (isset($nodeIBSCBS->gIBSCBS)) {
            $grupo = $nodeIBSCBS->gIBSCBS;

            if (isset($grupo->vBC)) $this->ibsCbs["vBC"] = (float)($grupo->vBC);

            if (isset($grupo->gIBSUF)) {
                $uf = $grupo->gIBSUF;
                if (isset($uf->pIBSUF))     $this->ibsCbs["gIBSUF_pIBSUF"]     = (float)($uf->pIBSUF);
                if (isset($uf->vIBSUF))     $this->ibsCbs["gIBSUF_vIBSUF"]     = (float)($uf->vIBSUF);
                if (isset($uf->pDif))       $this->ibsCbs["gIBSUF_pDif"]       = (float)($uf->pDif);
                if (isset($uf->vDif))       $this->ibsCbs["gIBSUF_vDif"]       = (float)($uf->vDif);
                if (isset($uf->vDevTrib))   $this->ibsCbs["gIBSUF_vDevTrib"]   = (float)($uf->vDevTrib);

                if (isset($uf->gRed)) {
                    if (isset($uf->gRed->pRedAliq))  $this->ibsCbs["gIBSUF_pRedAliq"]  = (float)($uf->gRed->pRedAliq);
                    if (isset($uf->gRed->pAliqEfet)) $this->ibsCbs["gIBSUF_pAliqEfet"] = (float)($uf->gRed->pAliqEfet);
                }
            }

            if (isset($grupo->gIBSMun)) {
                $mun = $grupo->gIBSMun;
                if (isset($mun->pIBSMun))     $this->ibsCbs["gIBSMun_pIBSMun"]     = (float)($mun->pIBSMun);
                if (isset($mun->vIBSMun))     $this->ibsCbs["gIBSMun_vIBSMun"]     = (float)($mun->vIBSMun);
                if (isset($mun->pDif))        $this->ibsCbs["gIBSMun_pDif"]        = (float)($mun->pDif);
                if (isset($mun->vDif))        $this->ibsCbs["gIBSMun_vDif"]        = (float)($mun->vDif);
                if (isset($mun->vDevTrib))    $this->ibsCbs["gIBSMun_vDevTrib"]    = (float)($mun->vDevTrib);

                if (isset($mun->gRed)) {
                    if (isset($mun->gRed->pRedAliq))  $this->ibsCbs["gIBSMun_pRedAliq"]  = (float)($mun->gRed->pRedAliq);
                    if (isset($mun->gRed->pAliqEfet)) $this->ibsCbs["gIBSMun_pAliqEfet"] = (float)($mun->gRed->pAliqEfet);
                }
            }

            if (isset($grupo->gCBS)) {
                $cbs = $grupo->gCBS;
                if (isset($cbs->pCBS))      $this->ibsCbs["gCBS_pCBS"]      = (float)($cbs->pCBS);
                if (isset($cbs->vCBS))      $this->ibsCbs["gCBS_vCBS"]      = (float)($cbs->vCBS);
                if (isset($cbs->pDif))      $this->ibsCbs["gCBS_pDif"]      = (float)($cbs->pDif);
                if (isset($cbs->vDif))      $this->ibsCbs["gCBS_vDif"]      = (float)($cbs->vDif);
                if (isset($cbs->vDevTrib))  $this->ibsCbs["gCBS_vDevTrib"]  = (float)($cbs->vDevTrib);

                if (isset($cbs->gRed)) {
                    if (isset($cbs->gRed->pRedAliq))  $this->ibsCbs["gCBS_pRedAliq"]  = (float)($cbs->gRed->pRedAliq);
                    if (isset($cbs->gRed->pAliqEfet)) $this->ibsCbs["gCBS_pAliqEfet"] = (float)($cbs->gRed->pAliqEfet);
                }
            }
        }

        if (isset($nodeIBSCBS->qBCMono))      $this->ibsCbs["qBCMono"]      = (float)($nodeIBSCBS->qBCMono);
        if (isset($nodeIBSCBS->adRemIBS))     $this->ibsCbs["adRemIBS"]     = (float)($nodeIBSCBS->adRemIBS);
        if (isset($nodeIBSCBS->vIBSMono))     $this->ibsCbs["vIBSMono"]     = (float)($nodeIBSCBS->vIBSMono);
        if (isset($nodeIBSCBS->adRemCBS))     $this->ibsCbs["adRemCBS"]     = (float)($nodeIBSCBS->adRemCBS);
        if (isset($nodeIBSCBS->vCBSMono))     $this->ibsCbs["vCBSMono"]     = (float)($nodeIBSCBS->vCBSMono);

        if (isset($nodeIBSCBS->gTransfCred)) {
            $transf = $nodeIBSCBS->gTransfCred;
            if (isset($transf->vIBS)) $this->ibsCbs["vIBSTransf"] = (float)($transf->vIBS);
            if (isset($transf->vCBS)) $this->ibsCbs["vCBSTransf"] = (float)($transf->vCBS);
        }

        if (isset($nodeIBSCBS->gCredPresIBSZFM)) {
            $zfm = $nodeIBSCBS->gCredPresIBSZFM;
            if (isset($zfm->competApur))       $this->ibsCbs["competApur"]       = gCleanField($zfm->competApur);
            if (isset($zfm->vCredPresIBSZFM))  $this->ibsCbs["vCredPresIBSZFM"]  = (float)($zfm->vCredPresIBSZFM);
            if (isset($zfm->tpCredPresIBSZFM)) $this->ibsCbs["tpCredPresIBSZFM"] = gCleanField($zfm->tpCredPresIBSZFM);
        }

        if (isset($nodeIBSCBS->gAjusteCompet)) {
            $ajuste = $nodeIBSCBS->gAjusteCompet;
            if (isset($ajuste->competApur)) $this->ibsCbs["competApur"] = gCleanField($ajuste->competApur);

            if (isset($ajuste->vIBS)) $this->ibsCbs["vIBSAjuste"] = (float)($ajuste->vIBS);
            if (isset($ajuste->vCBS)) $this->ibsCbs["vCBSAjuste"] = (float)($ajuste->vCBS);
        }
    }


	private function preparaCliente ()
	{
		global $usrId;
		$this->cliente=[];
		$this->cliente["nome"]        = gUcwords(gCleanField($this->clienteXML->xNome));
		$this->cliente["apelido"]     = gUcwords(gCleanField($this->clienteXML->xFant));
		$this->cliente["situacao"]    = "Ativo";
		$this->cliente["data_cadastro"] = date('Y-m-d H:i:s');
		$this->cliente["id_pessoas_criou"] = $usrId;
		$this->cliente["telefone"]    = gCleanField($this->clienteXML->enderEmit->fone);
		$this->cliente["cliente"]     = 1;
		$this->cliente["tipo"]        = 'J'; // F-FÍSICA, J-JURÍDICA.;
	}


	private function inserirCliente ()
	{
		$this->preparaCliente();
		$this->cliente["id"] = dbInsert("pessoas", $this->cliente, true);
		$this->preparaEndereco();
		$this->endereco["id"] = dbInsert("pessoas_enderecos", $this->endereco, true);
		$this->preparaClienteJuridico();
		$this->clienteJuridico["id"] = dbInsert("pessoas_juridicas", $this->clienteJuridico, true);
		$this->clienteBD = $this->checarEmitente(intval($this->clienteJuridico["cnpj"]), $this->idProprietario);
		$this->cliente["automatico"] = true;
	}


	private function preparaClienteJuridico()
	{
		$cliente = $this->clienteXML;
		$this->clienteJuridico = [];
		$this->clienteJuridico["id_pessoas"] = $this->cliente["id"];
		$this->clienteJuridico["cnpj"] = gCleanField($this->clienteXML->CNPJ);
		$this->clienteJuridico["razao_social"] = gCleanField($this->clienteXML->xNome);
	}


	private function preparaEndereco ()
	{
		$cliente = $this->clienteXML;
		$this->endereco=[];
		$this->endereco["id_pessoas"] = $this->cliente["id"];
		$this->endereco["endereco"]   = gCleanField($cliente->enderEmit->xLgr);
		$this->endereco["numero"]     = intval($cliente->enderEmit->nro);
		$this->endereco["bairro"]     = gCleanField($cliente->enderEmit->xBairro);
		$this->endereco["cep"]        = gCleanField($cliente->enderEmit->CEP);
		$cidade = $this->obtemCidade(gCleanField($cliente->enderEmit->cMun));
		if (!is_null($cidade)) {
			$this->endereco["id_enderecos_cidades"] = $cidade["id"];
			$this->endereco["id_enderecos_estados"] = $cidade["idEstado"];
		} else {
			$estado   = $this->obtemEstado(gCleanField($cliente->enderEmit->UF));
			$idCidade = $this->novaCidade($estado["id"], $cliente->enderEmit);
			$this->endereco["id_enderecos_cidades"] = $idCidade;
			$this->endereco["id_enderecos_estados"] = $estado["id"];
		}
	}


	function tiraEstranhos($var)
	{
		$var = $this->tiraAcentos($var);
		return(preg_replace('/[^a-z0-9\+\-\=\.\,\!\?\:\;\@\%\&\(\)\{\}\<\>\[\]\s\'\$\/]+/i ', '', $var));
	}


	function tiraAcentos($t)
	{
		global $gPathLib;
		$antes = html_entity_decode($t);
		if ($gPathLib <> "") {
			$pa = array("a", "e", "i", "o", "u", "o", "o", "a", "e");
			$de = array("á", "é", "í", "ó", "ú", "°", "º", "ª", "&");
			$t = str_replace($de, $pa, $t);
			$de = array("à", "è", "ì", "ò", "ù");
			$t = str_replace($de, $pa, $t);
			$de = array("â", "ê", "î", "ô", "û");
			$t = str_replace($de, $pa, $t);
			$t = str_replace("ã", "a", $t);
			$t = str_replace("õ", "o", $t);
			$t = str_replace("ç", "c", $t);
			$t = str_replace("Ç", "C", $t);
			$t = autoencode($t);
			$pa = array("A", "E", "I", "O", "U");
			$de = array("Á", "É", "Í", "Ó", "Ú");
			$t = str_replace($de, $pa, $t);
			$de = array("À", "È", "Ì", "Ò", "Ù");
			$t = str_replace($de, $pa, $t);
			$de = array("Â", "Ê", "Î", "Ô", "Û");
			$t = str_replace($de, $pa, $t);
			$de = array("Ã", "Õ", "Ç");
			$pa = array("A", "O", "C");
			$t = str_replace($de, $pa, $t);
			$de = array("");
			$pa = array("E");
			$t = str_replace($de, $pa, $t);
		} else {
			$t = strtr($t, utf8_decode("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&"), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
			$t = strtr($t, utf8_encode("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&"), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
			$t = strtr($t, "áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&", "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
		}

		$t = iconv('ISO-8859-1', 'ASCII//TRANSLIT//IGNORE', $t);
		return $t;
	}


	private function preparaUnidade($item)
	{
		$sigla=$this->tiraEstranhos(gCleanField($item->prod->uCom));
		$sigla=substr($sigla, 0, 3);
		$this->unidade=[];
		$this->unidade["sigla"]=$sigla;
		$this->unidade["descricao"]=$sigla;
	}

	public function inserirUnidade($item)
	{
		$this->preparaUnidade($item);
		$this->unidade["id"] = dbInsert("unidades", $this->unidade, true);
		$this->unidade["novo"] = true;
		$this->unidades[] = true;
	}


	private function preparaSKUMinimo()
	{
		global $usrId;

		$sku = [];
		$sku["ativo"] = 0;
		$sku["nome"] = $this->item["nome"];
		$sku["id_itens"] = $this->item["id"];
		$sku["id_unidades"] = $this->unidade['id'];
		$sku["id_pessoas_criou"] = $usrId;
		$sku["data_cadastro"] = date('Y-m-d H:i:s');
		$sku["codigo"] = $this->item['codigo'];
		$sku["codigo_barras"] = $this->item['codigo_barras'];
		$sku["quantidade"] = 1;
		return ($sku);
	}


	private function preparaSKU($item)
	{
		global $usrId;
		$this->sku = [];
		$this->sku["ativo"] 		  = 0;
		$this->sku["id_itens"] 	  	  = $this->item["id"];
		$this->sku["id_unidades"] 	  = $this->unidade["id"];
		$this->sku["id_pessoas_criou"]= $usrId;
		$this->sku["data_cadastro"]	  = date('Y-m-d H:i:s');
		$this->sku["codigo"] 		  = gCleanField($item->prod->cProd);
		if (isset($item->prod->cEAN) && (!empty($item->prod->cEAN) && !is_null($item->prod->cEAN) && trim($item->prod->cEAN)<> "SEM GTIN")) {
			$this->sku["codigo_barras"]= gCleanField($item->prod->cEAN);
		} else {
			$this->sku["codigo_barras"]=gCleanField($item->prod->cProd);
		}

		if (
			empty($this->sku["codigo"])
			|| is_null($this->sku["codigo"])
		) {
			$this->sku["codigo"]=$item["codigo"];
		}

		if (
			empty($this->sku["codigo_barras"])
			|| is_null($this->sku["codigo_barras"])
		) {

			if (
				isset($item["codigo_barras"])
				&& (!empty($item["codigo_barras"])
				&& !is_null($item["codigo_barras"])
				&& trim($item["codigo_barras"]) <> "SEM GTIN")
			) {
				$this->sku["codigo_barras"]=$item["codigo_barras"];
			} else {
				$this->sku["codigo_barras"]=$item["codigo"];
			}
		}
		$this->sku["quantidade"]=1;
	}


	private function inserirSKUMinimo($item)
	{
		$skuMinimo = $this->preparaSKUMinimo();
		dbInsert("itens_skus", $skuMinimo, true);
	}


	private function inserirSKU ($item)
	{

		$this->preparaSKU($item);
		/* Checar SKU */
		$liberar=true;
		if (isset($this->sku["codigo"])) {
			$idProprietario = $this->cliente['id'];

			$sql = "SELECT SK.id
					FROM itens_skus SK
					LEFT JOIN itens I ON I.id = SK.id_itens
					WHERE (SK.codigo='".$this->sku["codigo"]."'
						AND SK.id_unidades=".intval($this->unidade["id"]).")
						AND I.id_pessoas_proprietario='" . $idProprietario . "'";
			$confereSku=dbQuery($sql);
			if ($confereSku) {
				$liberar=false;
				$this->sku["id"] = $confereSku[0]["id"];
			}
		}

		/* Caso falhe na verificação da existência do SKU inserir SKU */
		if ($liberar) {
			$this->sku["id"] = dbInsert("itens_skus", $this->sku, true);
		}
	}


	private function preparaNFE ()
	{
		$this->nfe=[];
		$this->nfe["data"]   = date('Y-m-d H:i:s');
		$this->nfe["chave"]  = $this->validarChave($this->xml->NFe->infNFe["Id"]);
		$this->nfe["numero"] = gCleanField($this->informacoesXML->nNF);
		$this->nfe["situacao"]   = "Importada";
		$this->nfe["id_empresa"] = $this->cliente["id"];
		$this->nfe["id_cliente"] = $this->cliente["id"];
		$this->nfe["xml"]=(addslashes(trim($this->arquivo)));
		/* CAMPOS QUE PODEM NÃO EXISTIR */
		if (isset($this->xml->protNFe->infProt->dhRecbto)) {
			$this->nfe["data_recibo"] = date("Y-m-d H:i:s", strtotime(gCleanField($this->xml->protNFe->infProt->dhRecbto)));
		}

		if (isset($this->xml->protNFe->infProt->nProt)) {
			$this->nfe["protocolo"] = gCleanField($this->xml->protNFe->infProt->nProt);
		}

	}


	private function inserirNFE()
	{
		$this->preparaNFE();
		/* framework não aceita a string completa do xml, por enquanto usar sql comum */
		$this->nfe["id"] = dbInsert("nfe", $this->nfe, true);
	}


	private function preparaNotaFiscal()
	{
		global $usrId;
		$cfops = $this->checarCfops($this->registrosXML[0]->prod->CFOP);
		$this->nota=[];
		$this->nota["tipo"]       = 'E';
		$this->nota["confirmada"] = 0;
		$this->nota["cancelada"]  = 0;

		/* Tratando data de emissão */
		if (isset($this->informacoesXML->dhEmi)) {
			$dataEmissao = gDBDate($this->informacoesXML->dhEmi);
		} elseif (isset($this->informacoesXML->dEmi)) {
			$dataEmissao = gDBDate($this->informacoesXML->dEmi);
		} else {
			$dataEmissao = "0000-00-00";
		}

		/* tratando data de movimento */
		if (isset($this->informacoesXML->dhSaiEnt)) {
			$dataMovimento=gDBDate($this->informacoesXML->dhSaiEnt);
		} else {
			$dataMovimento="0000-00-00";
		}

		$this->nota["id_pessoas_proprietario"]  = $this->cliente["id"];
		$this->nota["numero"]         			=  gCleanField($this->informacoesXML->nNF);
		$this->nota["serie"]          			=  gCleanField($this->informacoesXML->serie);
		$this->nota["data_emissao"]   			=  $dataEmissao;
		$this->nota["data_movimento"] 			=  $dataMovimento;
		$this->nota["data_criou"]     			=  date('Y-m-d H:i:s');
		$this->nota["id_filial"]   	  			=  obtemIdEmpresa();
		$this->nota["id_cfops"]       			=  (count($cfops) > 0) ? $cfops["id"] : 0;
		$this->nota["id_pessoas_cliente"]       = $this->cliente["id"];
		$this->nota["id_nfe"] 					= $this->nfe["id"];
		$this->nota["id_pessoas_criou"] 		= $usrId;
		$this->nota['id_pessoas_fornecedor'] 	= $this->idPessoasFornecedor;

		/* CAMPOS QUE PODEM NÃO EXISTIR */
		if (isset($this->transporteXML->vol->qVol)) {
			$this->nota["volume"] = gCleanField($this->transporteXML->vol->qVol);
		}

		if (isset($this->xml->NFe->infNFe->transp->vol->pesoL)) {
			$this->nota["pesoL"] = (float) $this->xml->NFe->infNFe->transp->vol->pesoL;
		}

		if (isset($this->xml->NFe->infNFe->transp->vol->pesoB)) {
			$this->nota["pesoB"] = (float) $this->xml->NFe->infNFe->transp->vol->pesoB;
		}

		if (isset($this->xml->NFe->infNFe->total->ICMSTot->vOutro)) {
			$this->nota["vOutro"] = gDBFloat($this->xml->NFe->infNFe->total->ICMSTot->vOutro);
		}

		if (isset($this->xml->NFe->infNFe->total->ICMSTot->vFrete)) {
			$this->nota["vFrete"] = gDBFloat($this->xml->NFe->infNFe->total->ICMSTot->vFrete);
		}

		if (isset($this->xml->NFe->infNFe->total->ICMSTot->vSeg)) {
			$this->nota["vSeg"] = gDBFloat($this->xml->NFe->infNFe->total->ICMSTot->vSeg);
		}

		if (isset($this->xml->NFe->infNFe->total->ICMSTot->vDesc)) {
			$this->nota["vDesc"] = gDBFloat($this->xml->NFe->infNFe->total->ICMSTot->vDesc);
		}

		if (isset($this->xml->NFe->infNFe->total->ICMSTot->vProd)) {
			$this->nota["vProd"] = gDBFloat($this->xml->NFe->infNFe->total->ICMSTot->vProd);
		}

		if (isset($this->xml->NFe->infNFe->infAdic->infCpl)) {
			$this->nota["infCpl"] = gCleanField($this->xml->NFe->infNFe->infAdic->infCpl);
			$this->nota["infCpl"] = str_replace(array("{}","{","}"),"",$this->nota["infCpl"]);
		}

		if (isset($this->xml->NFe->infNFe->infAdic->infAdFisco)) {
			$this->nota["infCpl"] = gCleanField($this->xml->NFe->infNFe->infAdic->infAdFisco);
		}

		$this->nota['id_notas_agrupar'] = 0;

		if (gDBCheck($_REQUEST['importarComoSaida']) == 1) {
			$this->nota['tipo'] = 'S';
		}

	}


	private function inserirNota()
	{
		$this->preparaNotaFiscal();
		$this->nota["id"] = dbInsert("notas", $this->nota, true);
	}


	private function preparaNotaItem($registro)
	{
		global $gParam;
		$this->notaItem = [];
		$this->notaItem["id_notas"]      = $this->nota["id"];
		$this->notaItem["id_itens_skus"] = $this->sku["id"];
		$this->notaItem["id_unidades"]	 = $this->unidade["id"];
		$this->notaItem["quantidade"]    = gCleanField($registro->prod->qCom);
		$this->notaItem["valor"]         = gCleanField($registro->prod->vUnCom);

		// Lote
		$this->notaItem["lote"] = isset($registro->prod->Rastro->RastroItem->nLote) ? gCleanField($registro->prod->Rastro->RastroItem->nLote) : '';

		if (isset($registro->prod->Rastro->RastroItem->dFab))
			$this->notaItem["data_fabricacao"] = gCleanField($registro->prod->Rastro->RastroItem->dFab);

		// --- IMPOSTOS ---

		/* ICMS */
		if (isset($registro->imposto->ICMS)) {
			$this->validarGlobalICMS($registro->imposto->ICMS);
		}

		/* IPI */
		if (isset($registro->imposto->IPI)) {
			$this->validarIPI($registro->imposto->IPI);
		}

		/* PIS */
		if (isset($registro->imposto->PIS)) {
			$this->validarPIS($registro->imposto->PIS);
		}

		/* COFINS */
		if (isset($registro->imposto->COFINS)) {
			$this->validarCOFINS($registro->imposto->COFINS);
		}

		/* IBS_CBS */
		if (isset($registro->imposto->IBSCBS)) {
    		$this->validarGlobalIBSCBS($registro->imposto->IBSCBS);
		}

		// Combustível
		if (isset($registro->prod->comb)) {
			if (isset($registro->prod->comb->cProdANP)) $this->notaItem["cProdANP"] = gCleanField($registro->prod->comb->cProdANP);
			if (isset($registro->prod->comb->descANP))  $this->notaItem["descANP"]  = gCleanField($registro->prod->comb->descANP);
			if (isset($registro->prod->comb->CODIF))    $this->notaItem["CODIF"]    = gCleanField($registro->prod->comb->CODIF);
			if (isset($registro->prod->comb->UFCons))   $this->notaItem["UFCons"]   = gCleanField($registro->prod->comb->UFCons);
		}
	}


	private function inserirNotaItem($registro)
	{
		$this->preparaNotaItem($registro);
		$this->notaItem["id"] = dbInsert("notas_itens", $this->notaItem, true);

		if ($this->notaItem["id"] > 0) {
			// --- GRAVAÇÃO ICMS ---
			if (!empty($this->icms)) {
				// Remove campos auxiliares que não existem no banco
				if (isset($this->icms["descricao"])) unset($this->icms["descricao"]);

				if (count($this->icms) > 0) {
					$this->icms["id_notas_itens"] = $this->notaItem["id"];
					dbInsert("notas_itens_icms", $this->icms);
				}
			}

			// --- GRAVAÇÃO IPI ---
			if (!empty($this->ipi)) {
				$this->ipi["id_notas_itens"] = $this->notaItem["id"];
				dbInsert("notas_itens_ipi", $this->ipi);
			}

			// --- GRAVAÇÃO PIS ---
			if (!empty($this->pis)) {
				$this->pis["id_notas_itens"] = $this->notaItem["id"];
				dbInsert("notas_itens_pis", $this->pis);
			}

			// --- GRAVAÇÃO COFINS ---
			if (!empty($this->cofins)) {
				$this->cofins["id_notas_itens"] = $this->notaItem["id"];
				dbInsert("notas_itens_cofins", $this->cofins);
			}

			// --- GRAVAÇÃO IBS/CBS ---
			if (!empty($this->ibsCbs)) {
				$this->ibsCbs["id_notas_itens"] = $this->notaItem["id"];
				dbInsert("notas_itens_ibs_cbs", $this->ibsCbs);
			}

			// --- GRAVAÇÃO COMBUSTIVEL ---
			if (isset($registro->prod->comb)) {
				$grupoCombustivel = [];
				$item = [];

				$codigoAnp = isset($registro->prod->comb->cProdANP) ? gCleanField($registro->prod->comb->cProdANP) : '';

				if (!empty($codigoAnp)) {
					$grupoCombustivel["codigo"] = $codigoAnp;
				}

				if (isset($registro->prod->comb->descANP)) {
					$grupoCombustivel["descricao"] = gCleanField($registro->prod->comb->descANP);
				}

				$rs = dbQuery("SELECT * FROM grupos_combustivel WHERE codigo='".$codigoAnp."'");
				if (!$rs) {
					$id = dbInsert("grupos_combustivel", $grupoCombustivel, true);
				} else {
					$id = $rs[0]["id"];
				}

				$item["id_grupos_combustivel"] = $id;
				dbUpdate("itens", $item, $this->item["id"]);

				$mtz = [];
				$mtz["id_grupos_combustivel"] = $id;
				dbUpdate("notas_itens", $mtz, $this->notaItem["id"]);
			}
		}
		$this->itens[] = $this->notaItem;
	}


	private function preparaItem($registro)
	{
		global $usrId, $gParam;

		$this->item=[];
		$this->item["ativo"] = 0;
		$this->item["apto"]  = 0;
		$this->item["data_cadastro"] = date('Y-m-d H:i:s');
		$this->item["id_pessoas_proprietario"] = $this->cliente["id"];
		$this->item["id_pessoas_criou"] = $usrId;
		$this->item["codigo"]=gCleanField($registro->prod->cProd);
		$this->item["codigo_barras"]=gCleanField($registro->prod->cProd);
		$this->item["ncm"] = trim($registro->prod->NCM);
		$this->item["nome"]= gToUpper(gCleanField($registro->prod->xProd));
		$this->item["descricao"] = gToUpper(gCleanField($registro->prod->xProd));
	}


	private function inserirItem($item)
	{
		$this->preparaItem($item);
		$unidadeBD = $this->checarUnidade(gCleanField($item->prod->uCom));
		if (!$unidadeBD) {
			$this->inserirUnidade($item);
		} else {
			$this->unidade = $unidadeBD;
		}
		$this->item["id"] = dbInsert("itens", $this->item, true);
		$this->inserirSKU($item);
		$skuMinimo = $this->checarSKU($this->item['id'], ($this->unidade["id"] ?: 1));
		$this->item["id_unidades"] = $unidadeBD['id'];
		if (!$skuMinimo) {
			$this->inserirSKUMinimo();
		}
	}


	/**
	 * Verifica se itens da nota fiscal eletrônica já estão cadastrados
	 * Se não estiverem, cria item (com registros nas tabelas 'itens' e 'itens_skus'
	 * Para depois disto, salvar itens da nota fiscal eletrônica na tabela 'notas_itens'
	 *
	 * OBS: Neste momento o cabeçalho da nota já foi criado!
	 *
	 * @param string $tipo (Não está sendo usado)
	 * @return void
	 */
	private function inserirRegistros($tipo)
	{
		global $usrId;
		if ($this->crossdocking)
		{
			// Verifica se este cliente já tem um item cadastrado
			$itemBD = $this->checarItem("000");
			if (!$itemBD)
			{
				// Não, existe, cadastra agora...
				$flds = [];
				$flds['ativo'] = '1';
				$flds['apto'] = '1';
				$flds['data_cadastro'] = date("Y-m-d H:i:s");
				$flds['id_pessoas_proprietario'] = $this->cliente['id'];
				$flds['id_pessoas_criou'] = $usrId;
				$flds['id_grupos'] = '0';
				$flds['id_tipos'] = '0';
				$flds['nome'] = 'VCD';
				$flds['descricao'] = 'Volume de Cross Docking';
				$idItem = dbInsert("itens", $flds, true);
				if ($idItem > 0) {
					$flds = [];
					$flds['id_itens'] = $idItem;
					$flds['id_unidades'] = '1';
					$flds['id_pessoas_criou'] = $usrId;
					$flds['codigo'] = '000';
					$flds['codigo_barras'] = '000';
					$flds['data_cadastro'] = date("Y-m-d H:i:s");
					$flds['ativo'] = '1';
					$flds['quantidade'] = '1';
					$flds['peso_liquido'] = '0';
					$flds['peso_bruto'] = '0';
					$flds['largura'] = '1';
					$flds['altura'] = '1';
					$flds['comprimento'] = '1';
					$flds['nome'] = 'VCD';
					$idItemSku = dbInsert("itens_skus", $flds, true);
				} else {
					$this->erros[] = "Erro ao criar item de cadastro";
				}
			} else {
				$idItem = $itemBD['id'];
				$sql = "SELECT * FROM itens_skus WHERE id_itens=".$idItem;
				$rss = dbQuery($sql);
				if (count($rss))
				{
					$idItemSku = $rss[0]['id'];
				} else {
					$this->erros[] = "Erro ao criar SKU do item de cadastro";
				}
			}

			// Totaliza os volumes e insere na programação com 1 item apenas
			$ttlVolumes = 0;
			$ttlValor = 0;
			foreach ($this->registrosXML as $row) {
				if (!$row) {
					continue;//row pode ser nulo ao agrupar itens da nota
				}

				$ttlVolumes += floatval($row->prod->qCom);
				if (isset($row->prod->indTot) && $row->prod->indTot == 1) {
					$ttlValor   += floatval($row->prod->vProd);
				}

			}
			if (isset($this->xml->NFe->infNFe->transp->vol->qVol)) {
				$ttlVolumes = floatval($this->xml->NFe->infNFe->transp->vol->qVol);
			}

			$this->notaItem=[];

			$pesoL = 0;
			$pesoB = 0;

			if (isset($this->xml->NFe->infNFe->transp->vol->pesoL)) {
				// O peso liquido da NFe é em toneladas?
				if ($this->xml->NFe->infNFe->transp->vol->pesoL<50) {
					$pesoL = 1000 * floatval($this->xml->NFe->infNFe->transp->vol->pesoL);
				} else {
					$pesoL = floatval($this->xml->NFe->infNFe->transp->vol->pesoL);
				}

				$this->notaItem["peso_liquido"] = $pesoL / $ttlVolumes;
			}

			if (isset($this->xml->NFe->infNFe->transp->vol->pesoB)) {
				// O peso bruto da NFe é em toneladas?
				if ($this->xml->NFe->infNFe->transp->vol->pesoB < 50) {
					$pesoB = 1000 * floatval($this->xml->NFe->infNFe->transp->vol->pesoB);
				} else {
					$pesoB = floatval($this->xml->NFe->infNFe->transp->vol->pesoB);
				}

				$this->notaItem["peso_bruto"] = $pesoB / $ttlVolumes;
			}
			$this->notaItem["valor"] = $ttlValor / $ttlVolumes;
			$this->notaItem["id_notas"]      = $this->nota["id"];
			$this->notaItem["id_itens_skus"] = $idItemSku;
			$this->notaItem["id_unidades"]	 = 1;
			$this->notaItem["quantidade"]    = $ttlVolumes;
			$this->notaItem["id"] = dbInsert("notas_itens", $this->notaItem, true);

		} else {

			$this->agruparItensNota();

			foreach ($this->registrosXML as $row) {
				if (!$row) {
					continue;//row pode ser nulo ao agrupar itens da nota
				}

				$codigo  = gCleanField($row->prod->cProd);
				$itemBD = $this->checarItem($codigo);
				if ($itemBD == 0) {
					$this->inserirItem($row);
				} else {
					$unidadeBD=$this->checarUnidade($row->prod->uCom);
					if (!$unidadeBD) {
						$this->inserirUnidade($row);
						$this->unidade = $this->checarUnidade($row->prod->uCom);
					} else {
						$this->unidade = $unidadeBD;
					}
					$this->item = $itemBD;
					$this->sku  = $this->checarSKU($this->item["id"], $this->unidade["id"]);
					$itemBD["codigo_barras"]=trim($row->prod->cEAN);
					$itemBD["codigo"]=trim($row->prod->cProd);
					if (!$this->sku) {
						$this->inserirSKUMinimo();
					}
				}
				$this->inserirNotaItem($row);
			}

		}

	}


	public function checaDuplicidadeItems($item,$idProprietario=0)
	{
		$idProprietario = (int) $idProprietario == 0 ? (int) $_REQUEST['proprietario'] : (int) $idProprietario;
		$sql = "SELECT
					SK.codigo,
					I.nome,
					SK.id id_sku
				FROM itens_skus SK
				INNER JOIN itens I ON I.id = SK.id_itens
				WHERE SK.ativo = 1 AND I.ativo = 1
					AND SK.codigo='".$item."'
					AND I.id_pessoas_proprietario = " . $idProprietario;

		if (gDBCheck($_REQUEST['priorizarSkuInativo'])) {
			$sql .= ' ORDER BY SK.ativo ASC';
		}

		$rs=dbQuery($sql);
		$sai=[];
		$cnt=0;
		foreach ($rs as $row) {
			$cnt++;
			$sai[$cnt]['id'] = $row['id'];
			$sai[$cnt]['codigo'] = $row['codigo'];
			$sai[$cnt]['descricao'] = $row['nome'];
		}

		if ($cnt > 1){
			$this->itensDuplicados[]=$sai;
			return $sai;
		} else {
			return false;
		}
	}


	public function agruparItensNota()
	{
		/*
			Agrupa itens da nota por codigo e valor
		*/
		global $gParam;

		if (
			!$gParam['IMPORTAR_NOTA_AGRUPANDO_ITENS']['ativo']
			|| !$_REQUEST['agrupar_itens_nota']
		) {
			return;
		}

		for ($i = 0; $i <= count($this->registrosXML); $i++) {
			$chave = $this->registrosXML[$i]->prod->cProd
				. '_' . $this->registrosXML[$i]->prod->vUnCom;
			if (isset($itensIterados[$chave])) {//corre o risco de retornar zero pois $i comeca em zero
				$this->registrosXML[$itensIterados[$chave]]->prod->qCom  += $this->registrosXML[$i]->prod->qCom;
				$this->registrosXML[$itensIterados[$chave]]->prod->vProd += $this->registrosXML[$i]->prod->vProd;

        		unset($this->registrosXML[$i]->attributes()->nItem);
        		$this->registrosXML[$i] =  null;

				continue;
			}

			$itensIterados[$chave] = $i;
		}
	}

}
