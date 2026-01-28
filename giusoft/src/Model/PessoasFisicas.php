<?php

include_once "Pessoas.php";

class PessoasFisicas extends Pessoas
{
	public function __construct()
	{
		parent::__construct();
		$this->filtro = "p.id>2 AND motorista='0'";
	}


	public function obtemQueryConsulta()
	{
		// Query utilizada para a listagem de registros, busca de um registro, etc.
		return (
			"SELECT
				p.*,
				f.rg,
				f.cpf,
				f.data_nascimento,
				f.telefone_comercial,
				f.telefone_celular,
				f.telefone_residencial,
				f.cnh,
				f.cnh_categoria,
				f.cnh_data_emissao,
				f.cnh_data_validade,
				f.observacoes
			FROM pessoas p
			JOIN pessoas_fisicas f ON f.id_pessoas = p.id"
		);
	}


	public function geraCamposDoFormulario(&$frm, $registroAtual, $proximaPagina = "")
	{
		// O formulário de edição de dados usa este método
		global $proximaPagina, $gId, $gPage, $o, $usrId;

		if ($proximaPagina == "") {
			$proximaPagina = $gPage+1;
		}

		$pwd = "";
		if ($gId > 0) {
			$pwd = SENHA_NAO_MODIFICADA;
		}

		$acessoRemoto = null;
		if ($gParam['RESTRINGIR_ACESSO_POR_IP']['ativo'] && $usrId <= 2) {
			$acessoRemoto = $frm->add("{name: acesso_remoto; type: checkbox; value: ".$registroAtual['acesso_remoto']."}");
		}

		$keyUser = null;
		if ($usrId == 1) {
			$keyUser = $frm->add("{name: key_user; fieldLabel: Key User; type: checkbox; value: ".$registroAtual['key_user']."}");
		}

		$frm->row(
			// $frm->add("{name: cliente; type: checkbox; value: ".$registroAtual['cliente']."}"),
			$frm->add("{name: fornecedor; type: checkbox; value: ".$registroAtual['fornecedor']."}"),
			$frm->add("{name: funcionario; fieldLabel: Colaborador; type: checkbox; value: ".$registroAtual['funcionario']."}"),
			$frm->add("{name: motorista; fieldLabel: Motorisa; type: checkbox; value: ".$registroAtual['motorista']."}"),
			$acessoRemoto,
			$keyUser
		);

		$frm->row(
			$frm->add("{name: nome; type: upperFirstWordText; value: ".$registroAtual['nome']."}"),
			$frm->add("{name: apelido; type: text; value: ".$registroAtual['apelido']."}"),
			$frm->add("{name: senha; type: password; value: ".$pwd."}"),
			$frm->add("{name: confirmacao; type: password; value: ".$pwd."}")
		);

		$frm->row(
			$frm->add("{name: situacao; type: combo; allowBlank: false; value: '".$registroAtual['situacao']."'; items: {'Ativo','Inativo'}}"),
			$frm->add("{name: telefone; type: text; value: ".$registroAtual['telefone']."}"),
			$frm->add("{name: celular; type: text; value: ".$registroAtual['celular']."}"),
			$frm->add("{name: email; type: email; value: ".$registroAtual['email']."}")
		);

		$frm->row(
			$frm->add("{name: cpf; fieldLabel: CPF; type: text; maxLength: 30; value: ".$registroAtual['cpf']."}"),
			$frm->add("{name: rg; fieldLabel: RG; type: text; maxLength: 20; value: ".$registroAtual['rg']."}"),
			$frm->add("{name: codigo_sistema_externo; fieldLabel: Código de sistema externo; type: text; maxLength: 9; value: " . $registroAtual['codigo_sistema_externo'] . "}"),
			$frm->add("{name: data_nascimento; fieldLabel: Data de nascimento; type: date; value: ".gDate($registroAtual['data_nascimento'])."}")
		);

		$frm->row(
			$frm->add("{name: cnh; fieldLabel: CNH; type: text; value: ".$registroAtual['cnh']."}"),
			$frm->add("{name: cnh_categoria; fieldLabel: CNH categoria; type: upperText; value: ".$registroAtual['cnh_categoria']."}"),
			$frm->add("{name: cnh_data_emissao; fieldLabel: CNH Data emissão; type: date; value: ".gDate($registroAtual['cnh_data_emissao'])."}"),
			$frm->add("{name: cnh_data_validade; fieldLabel: CNH Vencimento; type: date; value: ".gDate($registroAtual['cnh_data_validade'])."}")
		);

		$frm->add("{name: observacoes; fieldLabel: Observações; type: textarea; value: ".base64_decode($registroAtual['observacoes'])."}");
		$frm->add("{name: gId; type: hidden; value: ".$gId."}");
		$frm->add("{name: gPage; type: hidden; value: ".$proximaPagina."}");

		return $frm->render($o);
	}


	public function preparaCampos($todosOsCampos, $gId = 0)
	{
		global $usrId;

		$campos = [];
		$campos['tipo']='F';
		$campos['cliente']=gDBCheck($todosOsCampos['cliente']);
		$campos['cliente_final']=gDBCheck($todosOsCampos['cliente_final']);
		$campos['fornecedor']=gDBCheck($todosOsCampos['fornecedor']);
		$campos['funcionario']=gDBCheck($todosOsCampos['funcionario']);
		$campos['motorista']=gDBCheck($todosOsCampos['motorista']);
		$campos['apelido']=gCleanField($todosOsCampos['apelido']);
		$campos['nome']=gUcwords($todosOsCampos['nome']);
		$campos['email']=gCleanField($todosOsCampos['email']);
		$campos['telefone']=gCleanField($todosOsCampos['telefone']);
		$campos['celular']=gCleanField($todosOsCampos['celular']);
		$campos['situacao']=gCleanField($todosOsCampos['situacao']);
		$campos['key_user'] = gDBCheck($todosOsCampos['key_user']);
		$campos['codigo_sistema_externo'] = gCleanField($todosOsCampos['codigo_sistema_externo']);

		if ($gId == 0) {
			$campos['data_cadastro']=gDBDateTime($todosOsCampos['data_cadastro']);
			$campos['id_pessoas_criou']=intval($usrId);
		} else {
			$campos['data_alteracao']=gDBDateTime($todosOsCampos['data_cadastro']);
			$campos['id_pessoas_alterou']=intval($usrId);
		}

		if ($todosOsCampos['senha'] <> SENHA_NAO_MODIFICADA) {
			$campos['senha']=md5(gCleanField($todosOsCampos['senha']));
		}

		if ($usrId <= 2) {
			$campos['acesso_remoto'] = gDBCheck($todosOsCampos['acesso_remoto']);
		}

		return $campos;
	}


	public function preparaCamposAdicionais($todosOsCampos, $gId = 0)
	{
		$campos = [];
		$campos['id_pessoas']=intval($todosOsCampos['id_pessoas']);
		$campos['rg']=gJustNumbers($todosOsCampos['rg']);
		$campos['cpf']=gJustNumbers($todosOsCampos['cpf']);
		$campos['cnh']=gJustNumbers($todosOsCampos['cnh']);
		$campos['cnh_categoria']=strtoupper($todosOsCampos['cnh_categoria']);
		$campos['cnh_data_emissao']=gDBDate($todosOsCampos['cnh_data_emissao']);
		$campos['cnh_data_validade']=gDBDate($todosOsCampos['cnh_data_validade']);
		$campos['data_nascimento']=gDBDate($todosOsCampos['data_nascimento']);
		$campos['observacoes']=base64_encode($todosOsCampos['observacoes']);
		return $campos;
	}


	public function insere($campos, &$gId = '')
	{
		if (($campos['senha'] != $campos['confirmacao']) ) {
			$this->erros[]="A senha e a confirmação devem ser iguais e diferentes de vazio!";
			return false;
		}

		// Primeiro obtém o próximo id
		$gId = dbInsert('pessoas', $this->preparaCampos($campos), true);
		$campos['id_pessoas'] = $gId;
		dbInsert('pessoas_fisicas', $this->preparaCamposAdicionais($campos));

		return $gId;
	}


	public function modifica($campos, $gId)
	{
		if (($campos['senha']!=$campos['confirmacao']) ) {
			$this->erros[]="A senha e a confirmação devem ser iguais e diferentes de vazio!";
			return false;
		}

		dbUpdate('pessoas', $this->preparaCampos($campos), $gId);
		$campos['id_pessoas'] = $gId;

		$rs = dbQuery("SELECT id FROM pessoas_fisicas WHERE id_pessoas = " . $gId);
		if (!$rs) {
			dbInsert("pessoas_fisicas", $this->preparaCamposAdicionais($campos));
		} else {
			dbUpdate('pessoas_fisicas', $this->preparaCamposAdicionais($campos), $rs[0]['id']);
		}

		return true;
	}
}