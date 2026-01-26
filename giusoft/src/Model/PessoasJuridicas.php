<?php

include_once "Pessoas.php";

class PessoasJuridicas extends Pessoas
{

	public function __construct()
	{
		parent::__construct();
		$this->filtro = "p.id>2 AND p.tipo='J' AND p.situacao in ('Ativo', 'Inativo')";
	}

	public function obtemQueryConsulta()
	{
		$sql = "SELECT
					p.*,
					pj.razao_social,
					pj.cnpj,
					pj.insc_estadual,
					pj.insc_municipal,
					pj.site site_empresa,
					pj.observacoes,
					pj.matriz,
					pj.unidade,
					pj.fiscal
				FROM pessoas p
				LEFT JOIN pessoas_juridicas pj ON pj.id_pessoas = p.id";
		return ($sql);
	}


	public function geraCamposDoFormulario(&$frm, $registroAtual, $proximaPagina="")
	{
		// O formulário de edição de dados usa este método
		global $proximaPagina, $gId, $gPage, $o, $gParam;

		if ($proximaPagina == "") {
			$proximaPagina=$gPage+1;
		}

		$pwd = "";
		if ($gId > 0) {
			$pwd = SENHA_NAO_MODIFICADA;
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

		$numero_documento=(!empty($registroAtual["cnpj"])) ? $registroAtual["cnpj"] : $registroAtual["cpf"];

		$frm->row(
			$frm->add("{name: unidade; fieldLabel: Unidade; type: upperText; maxLength: 30; value: ".$registroAtual['unidade']."}"),
			$frm->add("{name: cnpj; fieldLabel: CNPJ; type: cnpj; allowBlank: false;  ;maxLength: 30; value: ".$numero_documento."}"),
			$frm->add("{name: insc_municipal; fieldLabel: Insc. municipal; type: text; maxLength: 20; value: ".$registroAtual['insc_municipal']."}"),
			$frm->add("{name: insc_estadual; fieldLabel: Insc. estadual; type: text; maxLength: 20; value: ".$registroAtual['insc_estadual']."}")
		);

		$frm->add("{name: observacoes; fieldLabel: Observações; type: textarea; value: ".base64_decode($registroAtual['observacoes'])."}");
		$frm->add("{name: gId; type: hidden; value: ".$gId."}");
		$frm->add("{name: gPage; type: hidden; value: ".$proximaPagina."}");

		return $frm->render($o);
	}


	public function preparaCampos($todosOsCampos, $gId = 0)
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

		if ($gId == 0) {
			$campos['data_cadastro'] = gDBDateTime(date('Y-m-d H:i:s'));
			$campos['id_pessoas_criou'] = intval($usrId);
		} else {
			$campos['data_alteracao'] = gDBDateTime(date('Y-m-d H:i:s'));
			$campos['id_pessoas_alterou'] = intval($usrId);
		}

		if ($todosOsCampos['senha'] <> SENHA_NAO_MODIFICADA) {
			$campos['senha'] = md5(gCleanField($todosOsCampos['senha']));
		}

		return $campos;
	}


	public function preparaCamposAdicionais($todosOsCampos, $gId = 0)
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
		$campos['unidade']=gCleanField($todosOsCampos['unidade']);
		$campos['observacoes']=base64_encode($todosOsCampos['observacoes']);
		return $campos;
	}


	public function insere($campos = [], &$gId = '')
	{
		global $gParam;

		if (($campos['senha'] != $campos['confirmacao']) ) {
			$this->erros[] = "A senha e a confirmação devem ser iguais e diferentes de vazio!";
			return false;
		}

		// Primeiro obtém o próximo id
		$gId = dbInsert('pessoas',$this->preparaCampos($campos), true);
		$campos['id_pessoas']=$gId;
		dbInsert('pessoas_juridicas', $this->preparaCamposAdicionais($campos));

		// Inserindo acesso ao filial atual.
		$sql = "SELECT id
				FROM pessoas_filial
				WHERE id_pessoas = $gId
					AND id_filial = " . $_SESSION["filialAtualId"] . "
					AND cancelado = 0";
		$existe = dbQuery($sql);
		if (!$existe) {
			$mtz=array();
			$mtz["id_pessoas"]=$gId;
			$mtz["id_filial"]=$_SESSION["filialAtualId"];
			$mtz["id_pessoas_criou"]=$_SESSION["usrId"];
			$mtz["data_criou"]=date('Y-m-d H:i:s');
			dbInsert("pessoas_filial", $mtz);
		}


		// Transportadora então inserir pessoas_fisicas
		if (
			isset($campos["transportadora"])
			&& ($campos["transportadora"] == "on"
			|| intval($campos["transportadora"]) == 1)
		) {
			if (strlen($campos["cnpj"]) < 14) {
				$mtz=array();
				$mtz["cpf"]=$campos["cnpj"];
				$mtz["id_pessoas"]=$gId;
				dbInsert("pessoas_fisicas", $mtz);
				$sqlu = "UPDATE pessoas_juridicas SET cnpj='' WHERE id_pessoas=".$gId;
				dbQuery($sqlu);
			}
		}

		return $gId;
	}


	public function modifica($campos, $gId)
	{
		global $gParam;

		if (($campos['senha'] != $campos['confirmacao']) ) {
			$this->erros[] = "A senha e a confirmação devem ser iguais e diferentes de vazio!";
			return false;
		}

		$sql = "SELECT id FROM pessoas WHERE id > 2 AND id = " . $gId;
		$rs = dbQuery($sql);
		if (!$rs) {
			$this->erros[] = "A pessoa selecionada não foi encontrada no banco de dados";
			return false;
		}

		dbUpdate('pessoas', $this->preparaCampos($campos, $gId), $gId);
		$campos['id_pessoas'] = $gId;

		$rs = dbQuery("SELECT id FROM pessoas_juridicas WHERE id_pessoas = {$gId} LIMIT 1");
		if ($rs) {
			dbUpdate('pessoas_juridicas', $this->preparaCamposAdicionais($campos), $rs[0]['id']);
		} else {
			dbInsert("pessoas_juridicas", $this->preparaCamposAdicionais($campos));
		}

		if (
			$campos["transportadora"]
			&& strlen($campos["cnpj"]) < 14
		) {
			$sql = "SELECT id FROM pessoas_fisicas WHERE id_pessoas = {$gId} LIMIT 1";
			$idPessoaFisica = dbQuery($sql)[0]['id'];
			if ($idPessoaFisica) {
				$mtz = array();
				$mtz["cpf"] = $campos["cnpj"];
				$mtz["id_pessoas"] = $gId;
				dbUpdate("pessoas_fisicas", $mtz, $idPessoaFisica);
			} else {
				$mtz = array();
				$mtz["cpf"] = $campos["cnpj"];
				$mtz["id_pessoas"] = $gId;
				dbInsert("pessoas_fisicas", $mtz);
			}
			$sqlu = "UPDATE pessoas_juridicas SET cnpj='' WHERE id_pessoas=".$gId;
			dbFastQuery($sqlu);
		}

		return true;
	}


	public function verificarItensAtivosExigeLote() {
		global $gId;

		$sql = "SELECT id FROM itens
				WHERE ativo = 1 AND exige_lote = 1 AND id_pessoas_proprietario = {$gId}";
		$rs = dbQuery($sql);
		return (bool) $rs;
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
