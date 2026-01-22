<?php

include_once "Pessoas.php";
class PessoasJuridicas extends Pessoas
{


	function __construct()
	{
		parent::__construct();
		$this->filtro = "p.id>2 AND p.tipo='J' AND p.situacao in ('Ativo', 'Inativo')";
	}

	function obtemQueryConsulta()
	{
		$sql = "
			SELECT
				p.*,
				pj.razao_social,
				pj.cnpj,
				pj.insc_estadual,
				pj.insc_municipal,
				pj.site site_empresa,
				pj.observacoes,
				pj.matriz,
				pj.unidade,
				pj.codigo_sistema_externo,
				pj.faz_segunda_separacao,
				pj.lote_xprod,
				pj.prazo,
				pj.lead_time,
				pj.exige_uma_entrada_convencional,
				pj.exigir_sku_separacao,
				pj.indicar_posicao,
				pj.fiscal,
				pj.tipo_separacao,
				pj.priorizar_palete_aberto,
				pj.priorizar_palete_fechado,
				pj.foto_obrigatoria,
				pj.permitir_portaria_sem_os,
				pj.quantidade_posicoes,
				pj.variacao_divergencia
			FROM
				pessoas p
			LEFT JOIN pessoas_juridicas pj ON
				pj.id_pessoas = p.id";
		return ($sql);
	}


	function geraCamposDoFormulario(&$frm, $registroAtual, $proximaPagina="")
	{
		// O formulário de edição de dados usa este método
		global $proximaPagina, $gId, $gPage, $o, $gParam;
		if ($proximaPagina=="")
		{
			$proximaPagina=$gPage+1;
		}
		$pwd="";
		if ($gId>0)
		{
			$pwd=SENHA_NAO_MODIFICADA;
		}

		$frm->row(
			$frm->add("{name: matriz; type: checkbox; value: ".($registroAtual['matriz'])."}"),
			$frm->add("{name: cliente; type: checkbox; value: ".$registroAtual['cliente']."}"),
			$frm->add("{name: cliente_final; type: checkbox; value: ".$registroAtual['cliente_final']."}"),
			$frm->add("{name: fornecedor; fieldLabel:Fornecedor; type: checkbox; value: ".$registroAtual['fornecedor']."}"),
			$frm->add("{name: transportadora; type: checkbox; value: ".$registroAtual['transportadora']."}"),
			$fotoObrigatoria
		);

		$frm->row(
			$frm->add("{name: nome; type: upperFirstWordText; value: ".$registroAtual['nome']."}"),
			$frm->add("{name: apelido; type: text; maxLength: 22; value: ".$registroAtual['apelido']."}"),
			$frm->add("{name: senha; type: password; value: ".$pwd."}"),
			$frm->add("{name: confirmacao; type: password; value: ".$pwd."}")
		);
		$frm->row(
			$frm->add("{name: razao_social; fieldLabel: Razão social; type: upperFirstWordText; maxLength: 100; value: ".$registroAtual['razao_social']."}"),
			$frm->add("{name: email; fieldLabel: Email; type: text; maxLength: 100; value: ".$registroAtual['email']."}"),
			$frm->add("{name: site; fieldLabel: Site; type: text; maxLength: 100; value: ".$registroAtual['site_empresa']."}"),
			$frm->add("{name: situacao; type: combo; allowBlank: false; value: ".$registroAtual['situacao']."; items: {'Ativo','Inativo'}}")
		);

		$numero_documento=(!empty($registroAtual["cnpj"]))
		? $registroAtual["cnpj"]
		: $registroAtual["cpf"];

		$frm->row(
			$frm->add("{name: unidade; fieldLabel: Unidade; type: upperText; maxLength: 30; value: ".$registroAtual['unidade']."}"),
			$frm->add("{name: cnpj; fieldLabel: CNPJ; type: cnpj; allowBlank: false;  ;maxLength: 30; value: ".$numero_documento."}"),
			$frm->add("{name: insc_municipal; fieldLabel: Insc. municipal; type: text; maxLength: 20; value: ".$registroAtual['insc_municipal']."}"),
			$frm->add("{name: insc_estadual; fieldLabel: Insc. estadual; type: text; maxLength: 20; value: ".$registroAtual['insc_estadual']."}")
		);

		$frm->add("{name: observacoes; fieldLabel: Observações; type: textarea; value: ".base64_decode($registroAtual['observacoes'])."}");
		$frm->add("{name: gId; type: hidden; value: ".$gId."}");
		$frm->add("{name: gPage; type: hidden; value: ".$proximaPagina."}");
		
		return($frm->render($o));
	}


	public function gerarCamposAbaOperacao(&$frm, $registroAtual, $proximaPagina="")
	{
		global $gId, $gPage, $o, $gParam;

		if ($gParam['PERMITIR_TRANSFERIR_CONFERENCIA_UMA']['ativo']) {
			$segunda_separacao = $frm->add("{name: segunda_separacao; fieldLabel: Segunda Separação; type: checkbox; value: ".$registroAtual['faz_segunda_separacao']."}");
		}

		$exigirSkuSeparacao = $frm->add("{name: exigir_sku_separacao; fieldLabel: Exigir SKU na separação; type: checkbox; value: ".$registroAtual['exigir_sku_separacao']."}");

		if ($gParam['EXTRAIR_LOTE_TAG_XPROD']['ativo']==1) {
			$usaLoteProduto	= $frm->add("{name: lote_xprod; fieldLabel: Lote junto ao nome do produto;type: checkbox; value:".$registroAtual['lote_xprod']." }");
		}

		$frm->row(
			$frm->add("{name: fiscal; fieldLabel: Tratamento fiscal; type: checkbox; value: ".$registroAtual['fiscal']."}"),
			$frm->add("{name: indicar_posicao; fieldLabel: Indicar pos. na entrada; type: checkbox; value: ".$registroAtual['indicar_posicao']."}"),
			$frm->add("{name: priorizar_palete_aberto; fieldLabel: Priorizar palete aberto; type: checkbox; value: " . $registroAtual['priorizar_palete_aberto'] . "}"),
			$frm->add("{name: priorizar_palete_fechado; fieldLabel: Priorizar palete fechado; type: checkbox; value: " . $registroAtual['priorizar_palete_fechado'] . "}")
		);

		if ($gParam['TIRAR_FOTO_NA_OPERACAO']['ativo']) {
			$fotoObrigatoria = $frm->add("{name: foto_obrigatoria; fieldLabel: Obrigar fotos;type: checkbox; value:".$registroAtual['foto_obrigatoria']." }");
		}

		$frm->row(
			$frm->add("{name: exige_uma_entrada_convencional; fieldLabel: Exige UMA ent. convencional; type: checkbox; value: " . $registroAtual['exige_uma_entrada_convencional'] . "}"),
			$segunda_separacao,
			$usaLoteProduto,
			$exigirSkuSeparacao,
			$fotoObrigatoria
		);
		if ($gParam['PERSISTIR_NOTA_PORTARIA']['ativo']) {
			$frm->row(
				$frm->add("{name: permitirPortariaSemOs; fieldLabel: Permitir portaria sem OS; type: checkbox; value:" . $registroAtual['permitir_portaria_sem_os'] . ";}")
			);
		}

		if (
			$gParam['INTEGRACAO_GMI']['ativo']
			&& in_array($registroAtual['id'], explode(',', $gParam['INTEGRACAO_GMI']['valor']))
		) {
			$campoCodigoExterno = $frm->add("{name: codigo_sistema_externo_show; fieldLabel: Código de sistema externo; type: show; value: " . $registroAtual['codigo_sistema_externo'] .";}");
			$frm->add("{name: codigo_sistema_externo; type: hidden; value: " . $registroAtual['codigo_sistema_externo'] .";}");
		} else {
			$campoCodigoExterno = $frm->add("{name: codigo_sistema_externo; fieldLabel: Código de sistema externo; type: text; maxLength: 9; value: " . $registroAtual['codigo_sistema_externo'] . "}");
		}

		if ($gParam['INTEGRACAO_GPAT']['ativo']) {
			$variacaoDivergencia = $frm->add("{name: variacao_divergencia; fieldLabel: Variação de divergência; type:number; value: " . $registroAtual['variacao_divergencia'] .";}");
		}

		$comboTipoSeparacao = array();
		$comboTipoSeparacao["1"] = "Separar por rua";
		$comboTipoSeparacao["2"] = "Separar por item";

		$sql = "SELECT GROUP_CONCAT(id_tipos_entrada) ids_tipos_entrada FROM tipos_entrada_proprietario WHERE id_pessoas_proprietario = {$gId}";
		$rs  = dbFastQuery($sql)[0]['ids_tipos_entrada'];
		$sql = "SELECT id, descricao FROM tipos_entrada WHERE id IN (" . $gParam['ENTRADAS_HABILITADAS']['valor'] . ")";

		$frm->row(
			$campoCodigoExterno,
			$variacaoDivergencia,
			$frm->add("{allowBlank:false; name: tipo_separacao; fieldLabel: Tipo de separação; type: combo; items:'" . json_encode($comboTipoSeparacao) . "'; value:".$registroAtual['tipo_separacao'] . ";}"),
			$frm->add("{name: id_tipos_entrada; type: comboMultiSelection; fieldLabel: Tipos de entrada; items:'" . $sql . "'; allowBlank: false; value: " . $rs . ";}"),
			$frm->add("{name: quantidade_posicoes; fieldLabel: Quantidade de posições contratadas; type: number; value: " . $registroAtual['quantidade_posicoes'] . "}")
		);

		if ($gParam['RESERVAR_VALIDANDO_PRAZO_MINIMO']['ativo']) {
			$frm->row(
				$frm->add("{name: prazo; fieldLabel: Prazo (%); type: number; value:" . $registroAtual['prazo'] . ";}"),
				$frm->add("{name: leadTime; fieldLabel: Lead time (d); type: integer; value: " . $registroAtual['lead_time'] . "}")
			);
		}


		$frm->add("{name: gId; type: hidden; value: ".$gId."}");
		$frm->add("{name: gPage; type: hidden; value: ".$proximaPagina."}");
		return($frm->render($o));
	}


	function preparaCampos($todosOsCampos, $gId = 0)
	{
		global $usrId;
		$campos = array();
		$campos['tipo']='J';
		$campos['cliente']=gDBCheck($todosOsCampos['cliente']);
		$campos['cliente_final']=gDBCheck($todosOsCampos['cliente_final']);
		$campos['transportadora']=gDBCheck($todosOsCampos['transportadora']);
		$campos['fornecedor']=gDBCheck($todosOsCampos['fornecedor']);
		$campos['apelido']=gCleanField($todosOsCampos['apelido']);
		$campos['nome']=gUcwords($todosOsCampos['nome']);
		$campos['email']=gCleanField($todosOsCampos['email']);
		$campos['telefone']=gCleanField($todosOsCampos['telefone']);
		$campos['celular']=gCleanField($todosOsCampos['celular']);
		$campos['situacao']=gCleanField($todosOsCampos['situacao']);
		$campos['email']=gCleanField($todosOsCampos['email']);

		if ($gId==0)
		{
			$campos['data_cadastro']=gDBDateTime(date('Y-m-d H:i:s'));
			$campos['id_pessoas_criou']=intval($usrId);
		} else {
			$campos['data_alteracao']=gDBDateTime(date('Y-m-d H:i:s'));
			$campos['id_pessoas_alterou']=intval($usrId);
		}
		if ($todosOsCampos['senha']<>SENHA_NAO_MODIFICADA)
		{
			$campos['senha']=md5(gCleanField($todosOsCampos['senha']));
		}
		return($campos);
	}

	function preparaCamposAdicionais($todosOsCampos, $gId = 0)
	{
		global $gParam;

		$campos = array();
		$campos['id_pessoas']=intval($todosOsCampos['id_pessoas']);
		$campos['razao_social']=gCleanField($todosOsCampos['razao_social']);
		$campos['cnpj']=gJustNumbers($todosOsCampos['cnpj']);
		$campos['insc_municipal']=gJustNumbers($todosOsCampos['insc_municipal']);
		$campos['insc_estadual']=gJustNumbers($todosOsCampos['insc_estadual']);
		$campos['matriz']=gDBCheck($todosOsCampos['matriz']);
		$campos['site']=gCleanField($todosOsCampos['site']);
		$campos['unidade']=gDBCheck($todosOsCampos['unidade']);
		$campos['observacoes']=base64_encode($todosOsCampos['observacoes']);
		return($campos);
	}

	function insere($campos, &$gId)
	{
		global $gParam;
		$gId = false;
		
		if (($campos['senha']!=$campos['confirmacao']) ) //|| ($senha=='')
		{
			$this->erros[]="A senha e a confirmação devem ser iguais e diferentes de vazio!";
			$gId = false;
		} else
		{
			if ($gParam['EXTRAIR_LOTE_TAG_XPROD']['ativo']) {
				if ($this->verificarItensAtivosExigeLote() && gDBCheck($campos['lote_xprod'])) {
					$this->erros[] = 'Este cliente possui itens ativos que exigem lote na entrada. Desative a exigência de lote para todos os itens deste cliente para ativar esta funcionalidade';
					return false;
				}
			}
			// Primeiro obtém o próximo id
			$gId=dbInsert('pessoas',$this->preparaCampos($campos), true);
			$campos['id_pessoas']=$gId;
			dbInsert('pessoas_juridicas', $this->preparaCamposAdicionais($campos));

			// Inserindo acesso ao filial atual.
			$sql="SELECT
					id
				  FROM pessoas_filial
				  WHERE id_pessoas=$gId
				  AND id_filial=".$_SESSION["filialAtualId"]."
				  AND cancelado=0
				  ";
			$existe=dbQuery($sql);
			if (count($existe)==0)
			{
				$mtz=array();
				$mtz["id_pessoas"]=$gId;
				$mtz["id_filial"]=$_SESSION["filialAtualId"];
				$mtz["id_pessoas_criou"]=$_SESSION["usrId"];
				$mtz["data_criou"]=date('Y-m-d H:i:s');
				dbInsert("pessoas_filial", $mtz);
			}


			// Transportadora então inserir pessoas_fisicas

			if (isset($campos["transportadora"]) && ($campos["transportadora"]=="on" || intval($campos["transportadora"])==1))
			{
				if (strlen($campos["cnpj"])<14)
				{
					$mtz=array();
					$mtz["cpf"]=$campos["cnpj"];
					$mtz["id_pessoas"]=$gId;
					dbInsert("pessoas_fisicas", $mtz);
					$sqlu="UPDATE pessoas_juridicas SET cnpj='' WHERE id_pessoas=".$gId;
					dbQuery($sqlu);
				}
			}

		}
		return($gId);
	}

	function modifica($campos, $gId)
	{
		global $gParam;
		$sucesso = true;

		if (($campos['senha']!=$campos['confirmacao']) ) //|| ($senha=='')
		{
			$this->erros[]="A senha e a confirmação devem ser iguais e diferentes de vazio!";
			$sucesso = false;
		} else
		{
			$sql="SELECT * FROM pessoas WHERE id>2 AND id=".$gId;
			$rs=dbQuery($sql);

			if ($gParam['EXTRAIR_LOTE_TAG_XPROD']['ativo']) {
				if ($this->verificarItensAtivosExigeLote() && gDBCheck($campos['lote_xprod'])) {
					$this->erros[] = 'Este cliente possui itens ativos que exigem lote na entrada. Desative a exigência de lote para todos os itens deste cliente para ativar esta funcionalidade';
					return false;
				}
			}
			if (count($rs)>0)
			{
				dbUpdate('pessoas', $this->preparaCampos($campos, $gId), $gId);
				$campos['id_pessoas'] = $gId;

				$rs=dbQuery("SELECT id FROM pessoas_juridicas WHERE id_pessoas=".$gId);
				if (count($rs)==0)
				{
					dbInsert("pessoas_juridicas", $this->preparaCamposAdicionais($campos));
				} else {
					dbUpdate('pessoas_juridicas', $this->preparaCamposAdicionais($campos), $rs[0]['id']);
				}

				if (isset($campos["transportadora"]) && intval($campos["transportadora"])==1)
				{
					if (strlen($campos["cnpj"])<14)
					{
						$sql="SELECT id FROM pessoas_fisicas WHERE id_pessoas=".$gId;
						$existe_rs=dbQuery($sql);
						if (count($existe_rs)==0)
						{
							$mtz=array();
							$mtz["cpf"]=$campos["cnpj"];
							$mtz["id_pessoas"]=$gId;
							dbInsert("pessoas_fisicas", $mtz);
						} else
						{
							$id_pessoas_fisica=$existe_rs[0]["id"];
							$mtz=array();
							$mtz["cpf"]=$campos["cnpj"];
							$mtz["id_pessoas"]=$gId;
							dbUpdate("pessoas_fisicas", $mtz, $id_pessoas_fisica);
						}
						$sqlu="UPDATE pessoas_juridicas SET cnpj='' WHERE id_pessoas=".$gId;
						dbQuery($sqlu);
					}
				}
			} else
			{
				$this->erros[]="A pessoa selecionada não foi encontrada no banco de dados.";
				$sucesso = false;
			}
		}
		return($sucesso);
	}


	public function verificarItensAtivosExigeLote() {
		global $gId;
		$sql = "
			SELECT id
			FROM itens
			WHERE ativo = 1
				AND exige_lote = 1
				AND id_pessoas_proprietario = {$gId}";
		$rs = dbQuery($sql);
		return (bool) $rs;
	}


	public function persistirTiposEntrada($idTiposEntrada)
	{
		global $gId;

		$sql = "SELECT id, id_tipos_entrada FROM tipos_entrada_proprietario WHERE id_pessoas_proprietario = {$gId}";
		$rs  = dbFastQuery($sql);
		$idExcluir = "";
		foreach ($rs as $tipoEntradaAtual) {
			if (!in_array($tipoEntradaAtual['id_tipos_entrada'], $idTiposEntrada)) {
				//tem no banco e nao tem na aplicacao
				$idExcluir .= $tipoEntradaAtual['id'] . ",";
			}
		}
		if ($idExcluir) {
			$idExcluir = substr($idExcluir, 0, -1);
			dbFastQuery("DELETE FROM tipos_entrada_proprietario WHERE id IN ({$idExcluir})");
		}

		$rs = array_column($rs, 'id_tipos_entrada');
		foreach ($idTiposEntrada as $id) {
			if (!in_array($id, $rs)) {
				// tem na aplicacao e nao tem no banco
				$sql = "INSERT INTO tipos_entrada_proprietario (id_pessoas_proprietario, id_tipos_entrada)
					VALUES ('{$gId}', '{$id}')";
				dbFastQuery($sql);
			}
		}
	}




	public function validarDadosEmpresa($dados, $linha)
	{
		$errorMessage = [];
	    $validacoes = [
	        0 => ['maxLength' => 100, 		'campo' => 'Nome'],
	        1 => ['maxLength' => 100, 		'campo' => 'Razão Social'],
	        2 => ['inArray' => ['F', 'J'], 	'campo' => 'Tipo'],
	        3 => ['maxLength' => 14, 		'campo' => 'CNPJ'],
	        4 => ['maxLength' => 12, 		'campo' => 'Inscrição Estadual'],
	        5 => ['maxLength' => 12, 		'campo' => 'Inscrição Municipal'],
	        6 => ['maxLength' => 30, 		'campo' => 'Código do sistema externo'],
	        7 => ['maxLength' => 100, 		'campo' => 'Bairro'],
	        8 => ['maxLength' => 100, 		'campo' => 'Endereço'],
	        9 => ['maxLength' => 10, 		'campo' => 'Número'],
	        10 => ['maxLength' => 9, 		'campo' => 'CEP']
	    ];

		foreach ($validacoes as $index => $regra) {
			if ($regra['maxLength'] && strlen($dados[$index]) > $regra['maxLength']) {
				$errorMessage[] = "Linha {$linha}: O campo {$regra['campo']} excede o limite máximo de {$regra['maxLength']} caracteres";
			}
			if ($regra['inArray'] && !in_array(strtoupper($dados[$index]), $regra['inArray'])) {
				$errorMessage[] = "Linha {$linha}: O campo {$regra['campo']} deve ser F para pessoa física ou J para pessoa jurídica";
			}
		}

	    return $errorMessage;
	}


	public function formatarTiposEmpresa($tiposEmpresa)
	{
	    $resultado = array(
	        'cliente' => 0,
	        'cliente_final' => 0,
	        'fornecedor' => 0,
	        'transportadora' => 0,
	    );

	    foreach ($tiposEmpresa as $tipo) {
	        $resultado[$tipo] = 1;
	    }

	    return $resultado;
	}

}
