<?
include_once "res/classes.php";

/*  -------------- PESSOAS -------------- */

class Pessoas extends Persistencia
{
	function __construct()
	{
		$this->tabela = "pessoas";
		$this->filtro = "p.id>2";
		$this->ordenacao = "situacao,nome";
	}

	function obtemQueryConsulta()
	{
		return("SELECT p.* FROM pessoas p LEFT JOIN pessoas_fisicas f ON f.id_pessoas=p.id");
	}

	// ENDEREÇOS -----------------------------------------------

	function obtemRegistrosEnderecos($id="")
	{
		global $gId;

		$sql="SELECT p.id, e.descricao estado, c.descricao cidade,
		endereco,complemento,bairro,
		p.id_enderecos_estados, p.id_enderecos_cidades,p.id_enderecos_paises, p.numero, p.cep
		FROM pessoas_enderecos p
		LEFT JOIN enderecos_cidades c ON p.id_enderecos_cidades=c.id
		LEFT JOIN enderecos_estados e ON p.id_enderecos_estados=e.id
		WHERE p.id_pessoas=" . $gId;
		if ($id>0)
		{
			$sql.=" AND p.id=".$id;
		}
		return(dbQuery($sql));
	}

	/**
	* Gera os campos necessários para um formulário de entrada de dados
	*/
	function geraCamposDoFormularioEnderecos(&$frm, $registroAtual, $proximaPagina="", $gIdEnd)
	{
		global $proximaPagina, $gId, $gPage, $o, $sp;
		if ($proximaPagina=="")
		{
			$proximaPagina=$gPage+1;
		}
		$frm->row(
			$frm->add("{name: endereco; type: upperFirstWordText; allowBlank: false; fieldLabel: Endereço; maxLength: 120; value: ".$registroAtual['endereco']."}"),
			$frm->add("{name: numero; type: upperFirstWordText; fieldLabel: Número; maxLength: 20; value: ".$registroAtual['numero']."}")
		);
		$frm->row(
			$frm->add("{name: cep; fieldLabel: CEP; type: cep; value: ".$registroAtual['cep']."}"),
			$frm->add("{name: complemento; type: upperFirstWordText; fieldLabel: Complemento; maxLength: 120; value: ".$registroAtual['complemento']."}"),
			$frm->add("{name: bairro; type: upperFirstWordText; fieldLabel: Bairro; maxLength: 120; value: ".$registroAtual['bairro']."}")
		);
		$frm->row(
			$frm->add("{name: id_enderecos_paises; allowBlank: false; fieldLabel: País; type: combo; items: ".$sp['combo_paises']."; value: ".$registroAtual['id_enderecos_paises']."}"),
			$frm->add("{name: id_enderecos_estados; fieldLabel: Estado; type: combo; items: ".$sp['combo_estados']."; value: ".$registroAtual['id_enderecos_estados']."}"),
			$frm->add("{name: id_enderecos_cidades; fieldLabel: Cidade; type: combo; items: ".$sp['combo_cidades']."; value: ".$registroAtual['id_enderecos_cidades']."}")
			
		);

		$frm->add("{name: gId; type: hidden; value: $gId}");
		$frm->add("{name: gIdEnd; type: hidden; value: $gIdEnd}");
		$frm->add("{name: gPage; type: hidden; value: $proximaPagina}");
		return($frm->render($o));
	}

	function geraCamposDoFormularioCfop(&$frm){
		global $o,$sp,$gId;
		$frm->row(
			$frm->add("{name: id_cfops; type: combo; allowBlank: false; fieldLabel: CFOP; items: ".$sp["combo_cfop_saida"]."}")
		);
		$frm->add("{name: gId; type: hidden; value: $gId}");
		$frm->add("{name: gPage; type: hidden; value: ".CFOPS_SALVAR."}");
		return($frm->render($o));
	}

	/**
	* Formata campos de endereços enviados pelas funções de persistência no banco de dados
	*/
	function preparaCamposEndereco($todosOsCampos)
	{
		$campos = array();
		$campos['id_pessoas']=intval($todosOsCampos['id_pessoas']);
		$campos['endereco']=gUcwords($todosOsCampos['endereco']);
		$campos['complemento']=gUcwords($todosOsCampos['complemento']);
		$campos['bairro']=gUcwords($todosOsCampos['bairro']);
		$campos['numero']=gCleanField($todosOsCampos['numero']);
		$campos['cep']=gJustNumbers($todosOsCampos['cep']);
		$campos['id_enderecos_estados']=intval($todosOsCampos['id_enderecos_estados']);
		$campos['id_enderecos_cidades']=intval($todosOsCampos['id_enderecos_cidades']);
		$campos['id_enderecos_paises']=intval($todosOsCampos['id_enderecos_paises']);
		return($campos);
	}


	/**
	* Cria um novo registro no banco de dados e salva valores passados (tratando dados antes)
	*/
	function insereEndereco($todosOsCampos, $gId)
	{
		$todosOsCampos['id_pessoas']=$gId;
		return(dbInsert("pessoas_enderecos", $this->preparaCamposEndereco($todosOsCampos), true));
	}

	/**
	* Modifica um registro no banco de dados e salva com valores passados (tratando dados antes)
	*/
	function modificaEndereco($todosOsCampos, $gId, $gIdEnd)
	{
		$todosOsCampos['id_pessoas']=$gId;
		dbUpdate('pessoas_enderecos', $this->preparaCamposEndereco($todosOsCampos), $gIdEnd);
		return(true);
	}

	// OCORRÊNCIAS -----------------------------------------------

	function obtemRegistrosOcorrencias($id="")
	{
		global $gId;

		$sql="SELECT p.* , f.nome funcionario, t.descricao tipo_ocorrencia
		FROM pessoas_ocorrencias p
		LEFT JOIN pessoas f ON p.id_pessoas_funcionario=f.id
		LEFT JOIN tipos_ocorrencias t ON p.id_tipos_ocorrencias=t.id
		WHERE p.id_pessoas=" . $gId;
		if ($id>0)
		$sql.=" AND p.id=".$id;
		$sql.=" ORDER BY p.id DESC";
		return(dbQuery($sql));
	}

	/**
	* Gera os campos necessários para um formulário de entrada de dados
	*/
	function geraCamposDoFormularioOcorrencias(&$frm, $registroAtual, $proximaPagina="", $gIdEnd)
	{
		global $proximaPagina, $gId, $gPage, $o, $sp;
		if ($proximaPagina=="")
		{
			$proximaPagina=$gPage+1;
		}

		$frm->row(
			$frm->add("{name: descricao; type: textarea; fieldLabel: Descrição; value: ".$registroAtual['descricao']."}")
		);
		$frm->row(
			$frm->add("{name: data_ocorrencia; fieldLabel: Data da ocorrência; type: date; value: ".gDate($registroAtual['data_ocorrencia']=='0000-00-00 00:00:00' || $registroAtual['data_ocorrencia']=='' ? date('Y-m-d') : $registroAtual['data_ocorrencia'])."}"),
			$frm->add("{name: id_pessoas_funcionario; fieldLabel: Colaborador; type: combo; items: ".$sp['combo_funcionarios']."; value: ".$registroAtual['id_pessoas_funcionario']."}"),
			$frm->add("{name: id_tipos_ocorrencias; fieldLabel: Tipo de ocorrência; allowBlank: false; type: combo; items: ".$sp['combo_tipos_ocorrencias']."; value: ".$registroAtual['tipos_ocorrencias']."}"),
			$frm->add("{name: publica; type: checkbox; fieldLabel: Informação pública; value: ".$registroAtual['publica']."}")
		);

		$frm->add("{name: gId; type: hidden; value: $gId}");
		$frm->add("{name: gIdEnd; type: hidden; value: $gIdEnd}");
		$frm->add("{name: gPage; type: hidden; value: ".$proximaPagina."}");
		return($frm->render($o));
	}

	/**
	* Formata campos de endereços enviados pelas funções de persistência no banco de dados
	*/
	function preparaCamposOcorrencia($todosOsCampos)
	{
		$campos = array();
		$campos['id_pessoas']=intval($todosOsCampos['id_pessoas']);
		$campos['id_pessoas_funcionario']=intval($todosOsCampos['id_pessoas_funcionario']);
		$campos['id_tipos_ocorrencias']=intval($todosOsCampos['id_tipos_ocorrencias']);
		$campos['descricao']=gCleanField($todosOsCampos['descricao']);
		$campos['data_ocorrencia']=gDBDate($todosOsCampos['data_ocorrencia']);
		$campos['data_digitacao']=date("Y-m-d H:i:s");
		$campos['publica']=gDBCheck($todosOsCampos['publica']);
		return($campos);
	}

	/**
	* Cria um novo registro no banco de dados e salva valores passados (tratando dados antes)
	*/
	function insereOcorrencia($todosOsCampos, $gId)
	{
		$todosOsCampos['id_pessoas']=$gId;
		return(dbInsert("pessoas_ocorrencias", $this->preparaCamposOcorrencia($todosOsCampos), true));
	}

	/**
	* Modifica um registro no banco de dados e salva com valores passados (tratando dados antes)
	*/
	function modificaOcorrencia($todosOsCampos, $gId, $gIdEnd)
	{
		$todosOsCampos['id_pessoas']=$gId;
		dbUpdate('pessoas_ocorrencias', $this->preparaCamposOcorrencia($todosOsCampos), $gIdEnd);
		return(true);
	}


	// Atualiza a situação da pessoa se ela não tiver cadastrado o armazem
	public function atualizarPessoasSituacao($idPessoa) {
		$sql = "SELECT id FROM pessoas_armazens WHERE cancelado = 0 AND id_pessoas = " . $idPessoa . " LIMIT 1";
		$verificarPessoasArmazem = dbFastQuery($sql)[0]['id'];

		if (!$verificarPessoasArmazem) {
			dbFastQuery("UPDATE pessoas SET situacao = 'Inativo' WHERE id = " . $idPessoa);
		}
	}


	// ANEXOS -----------------------------------------------

	function obtemRegistrosAnexos($id="")
	{
		$sql="SELECT a.*, p.nome criou
		FROM pessoas_anexos a
		LEFT JOIN pessoas p on a.id_pessoas_criou=p.id
		WHERE a.id_pessoas=" . $id ." ORDER BY a.descricao";
		return(dbQuery($sql));
	}

	function geraCamposDoFormularioAnexos(&$frm, $irParaPagina)
	{
		global $gId, $html, $o;
		$frm->addFormMessage("O tamanho máximo permitido para a inclusão de arquivos é de 8Mb");
		$frm->add("{name: gPage; type: hidden; value: ".$irParaPagina."}");
		$frm->add("{name: gId; type: hidden; value: $gId}");
		$frm->add("{name: descricao; type: upperFirstLetterText; }");
		$frm->add("{name: arquivo; type: file; }");
		$frm->buttonNextCaption='Adicionar';
		$html.=$frm->render($o);
	}

	function anexoSalvar()
	{
		global $html, $o, $gPathUsrFiles, $gId, $usrId, $agora;
		$sucesso = true;

		$tamanhoMaximo=4000000;
		$arquivo = isset($_FILES['arquivo']) ? $_FILES['arquivo'] : FALSE;
		if ($arquivo && $arquivo['name']<>'') {
			if ($arquivo['error']==1)
			{
				$this->erros[]="Verifique se o tamanho do arquivo é inferior ao limite, se existe permissão na pasta para salvá-lo e se o tipo de arquivo é compatível.";
				$sucesso = false;
			} else {
				// Verifica tamanho do arquivo
				if ($arquivo['size'] > $tamanhoMaximo)
				{
					$this->erros[] = 'Arquivo em tamanho muito grande! O arquivo deve ser de no máximo ' . $tamanhoMaximo . ' bytes. Envie outro arquivo...';
					$sucesso = false;
				} else
				{
					$flds = array(
						'data'						 => $agora,
						'id_pessoas'			    => $gId,
						'id_pessoas_criou'	    => $usrId,
						'descricao'					 => gCleanField($_REQUEST['descricao']),
						'arquivo'					 => $arquivo['type']
					);
					$gPathUsrFiles.='anexos/';
					$ext = substr($arquivo['type'],strpos($arquivo['type'],'/')+1);
					if ($ext == "")
					{
						$ext = "pdf";
					}
					if(!is_dir($gPathUsrFiles))
					mkdir($gPathUsrFiles,0755);
					$id=dbInsert('pessoas_anexos', $flds, true);
					$imgName = $id.'.'.$ext;
					$ok = move_uploaded_file($arquivo['tmp_name'], $gPathUsrFiles . $imgName);
					gLog("===> Arquivo salvo: ".$gPathUsrFiles . $imgName . " (".$arquivo['tmp_name'].")");
					chmod($gPathUsrFiles . $imgName, 0644); // evita ação de hackers
				}
			}
		} else
		{
			$this->erros[]="Nenhum arquivo enviado";
			$sucesso=false;
		}
		return($sucesso);
	}

	function anexoRemover($id)
	{
		$id = intval($id);
		$rs=dbQuery("SELECT * FROM pessoas_anexos WHERE id=".$id);
		if (count($rs)>0)
		{
			$gPathUsrFiles.='anexos/';
			$imgName = $id.'.'.substr($rs[0]['arquivo'],strpos($rs[0]['arquivo'],'/')+1);
			unlink($gPathUsrFiles.$imgName);
			dbQuery("DELETE FROM pessoas_anexos WHERE id=".$id);
		}
		return(true);
	}
}


/*  -------------- PESSOAS FÍSICAS -------------- */

class PessoasFisicas extends Pessoas
{
	function __construct()
	{
		parent::__construct();
		$this->filtro = "p.id>2 AND motorista='0'";
	}

	function obtemQueryConsulta()
	{
		// Query utilizada para a listagem de registros, busca de um registro, etc.
		return(
			"SELECT p.*, f.rg, f.cpf, f.data_nascimento,
			f.telefone_comercial, f.telefone_celular, f.telefone_residencial,
			f.cnh, f.cnh_categoria, f.cnh_data_emissao, f.cnh_data_validade,
			f.observacoes
			FROM pessoas p
			JOIN pessoas_fisicas f ON f.id_pessoas=p.id
			"
		);
	}


	function geraCamposDoFormulario(&$frm, $registroAtual, $proximaPagina="")
	{
		// O formulário de edição de dados usa este método
		global $proximaPagina, $gId, $gPage, $o, $usrId;

		if ($proximaPagina=="")
		{
			$proximaPagina=$gPage+1;
		}
		$pwd="";
		if ($gId>0)
		{
			$pwd=SENHA_NAO_MODIFICADA;
		}

		if ($gParam['RESTRINGIR_ACESSO_POR_IP']['ativo'] && $usrId <= 2) {
			$acessoRemoto = $frm->add("{name: acesso_remoto; type: checkbox; value: ".$registroAtual['acesso_remoto']."}");
		}

		if ($usrId == 1) {
			$keyUser = $frm->add("{name: key_user; type: checkbox; value: ".$registroAtual['key_user']."}");
		}

		$frm->row(
			//$frm->add("{name: cliente; type: checkbox; value: ".$registroAtual['cliente']."}"),
			$frm->add("{name: fornecedor; type: checkbox; value: ".$registroAtual['fornecedor']."}"),
			$frm->add("{name: funcionario; fieldLabel: Colaborador; type: checkbox; value: ".$registroAtual['funcionario']."}"),
			$frm->add("{name: motorista; type: checkbox; value: ".$registroAtual['motorista']."}"),
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
			$frm->add("{name: situacao; type: combo; allowBlank: false; value: ".$registroAtual['situacao']."; items: {'Ativo','Inativo'}}"),
			$frm->add("{name: telefone; type: text; value: ".$registroAtual['telefone']."}"),
			$frm->add("{name: celular; type: text; value: ".$registroAtual['celular']."}"),
			$frm->add("{name: email; type: email; value: ".$registroAtual['email']."}")
		);
		$frm->row(
			$frm->add("{name: cpf; fieldLabel: CPF; type: text; maxLength: 30; value: ".$registroAtual['cpf']."}"),
			$frm->add("{name: rg; fieldLabel: RG; type: text; maxLength: 20; value: ".$registroAtual['rg']."}"),
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
		return($frm->render($o));
	}



	function preparaCampos($todosOsCampos, $gId = 0)
	{
		global $usrId;
		$campos = array();
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
		$campos['key_user']=gDBCheck($todosOsCampos['key_user']);
		if ($gId==0)
		{
			$campos['data_cadastro']=gDBDateTime($todosOsCampos['data_cadastro']);
			$campos['id_pessoas_criou']=intval($usrId);
		} else {
			$campos['data_alteracao']=gDBDateTime($todosOsCampos['data_cadastro']);
			$campos['id_pessoas_alterou']=intval($usrId);
		}
		if ($todosOsCampos['senha']<>SENHA_NAO_MODIFICADA)
		{
			$campos['senha']=md5(gCleanField($todosOsCampos['senha']));
		}
		if ($usrId <= 2) {
			$campos['acesso_remoto'] = gDBCheck($todosOsCampos['acesso_remoto']);
		}
		return($campos);
	}

	function preparaCamposAdicionais($todosOsCampos, $gId = 0)
	{
		$campos = array();
		$campos['id_pessoas']=intval($todosOsCampos['id_pessoas']);
		$campos['rg']=gJustNumbers($todosOsCampos['rg']);
		$campos['cpf']=gJustNumbers($todosOsCampos['cpf']);
		$campos['cnh']=gJustNumbers($todosOsCampos['cnh']);
		$campos['cnh_categoria']=strtoupper($todosOsCampos['cnh_categoria']);
		$campos['cnh_data_emissao']=gDBDate($todosOsCampos['cnh_data_emissao']);
		$campos['cnh_data_validade']=gDBDate($todosOsCampos['cnh_data_validade']);
		$campos['data_nascimento']=gDBDate($todosOsCampos['data_nascimento']);
		$campos['observacoes']=base64_encode($todosOsCampos['observacoes']);
		return($campos);
	}


	function insere($campos)
	{
		$gId = false;
		if (($campos['senha']!=$campos['confirmacao']) ) //|| ($senha=='')
		{
			$this->erros[]="A senha e a confirmação devem ser iguais e diferentes de vazio!";
			$gId = false;
		} else
		{
			// Primeiro obtém o próximo id
			$gId=dbInsert('pessoas',$this->preparaCampos($campos), true);
			$campos['id_pessoas']=$gId;
			dbInsert('pessoas_fisicas', $this->preparaCamposAdicionais($campos));
		}
		return($gId);
	}


	function modifica($campos, $gId)
	{
		$sucesso = true;
		if (($campos['senha']!=$campos['confirmacao']) ) //|| ($senha=='')
		{
			$this->erros[]="A senha e a confirmação devem ser iguais e diferentes de vazio!";
			$sucesso = false;
		} else
		{
			dbUpdate('pessoas', $this->preparaCampos($campos), $gId);
			$campos['id_pessoas'] = $gId;
			$rs=dbQuery("SELECT id FROM pessoas_fisicas WHERE id_pessoas=".$gId);
			if (count($rs)==0)
			{
				dbInsert("pessoas_fisicas", $this->preparaCamposAdicionais($campos));
			} else {
				dbUpdate('pessoas_fisicas', $this->preparaCamposAdicionais($campos), $rs[0]['id']);
			}
		}
		return($sucesso);
	}
}


/*  -------------- PESSOAS JURÍDICAS -------------- */

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

	function insere($campos)
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

			// Inserindo acesso ao armazem atual.
			$sql="SELECT
					id
				  FROM pessoas_armazens
				  WHERE id_pessoas=$gId
				  AND id_armazens=".$_SESSION["armazemAtualId"]."
				  AND cancelado=0
				  ";
			$existe=dbQuery($sql);
			if (count($existe)==0)
			{
				$mtz=array();
				$mtz["id_pessoas"]=$gId;
				$mtz["id_armazens"]=$_SESSION["armazemAtualId"];
				$mtz["id_pessoas_criou"]=$_SESSION["usrId"];
				$mtz["data_criou"]=date('Y-m-d H:i:s');
				dbInsert("pessoas_armazens", $mtz);
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
/*  -------------- ITENS -------------- */

class PessoasArmazens extends Pessoas
{
	function __construct()
	{
		parent::__construct();
		$this->filtro = "(p.id>2) AND (p.tipo='J') AND (p.situacao in ('Ativo', 'Inativo')) AND (p.armazem=1)";
	}

	function obtemQueryConsulta()
	{
		// Query utilizada para a listagem de registros, busca de um registro, etc.
		$sql="SELECT p.*, f.razao_social, f.cnpj, f.insc_estadual, f.insc_municipal,
			f.site site_empresa, f.observacoes, f.matriz, f.unidade, pf.cpf
			FROM pessoas p
			LEFT JOIN pessoas_juridicas f ON f.id_pessoas=p.id
			LEFT JOIN pessoas_fisicas pf ON pf.id_pessoas=p.id
			";
		return ($sql);
	}


	function geraCamposDoFormulario(&$frm, $registroAtual, $proximaPagina="")
	{
		// O formulário de edição de dados usa este método
		global $proximaPagina, $gId, $gPage, $o;
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
			$frm->add("{name: nome; type: upperFirstWordText; value: ".$registroAtual['nome']."}"),
			$frm->add("{name: apelido; type: text; value: ".$registroAtual['apelido']."}")
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
			$frm->add("{name: cnpj; fieldLabel: CNPJ; type: text; maxLength: 30; value: ".$numero_documento."}"),
			$frm->add("{name: insc_municipal; fieldLabel: Insc. municipal; type: text; maxLength: 20; value: ".$registroAtual['insc_municipal']."}"),
			$frm->add("{name: insc_estadual; fieldLabel: Insc. estadual; type: text; maxLength: 20; value: ".$registroAtual['insc_estadual']."}")
		);
		// Estratégia para alinhar o formulário.
		$o->addJavascript("$('#field-alinhar').attr('style', 'display:none;')");

		$frm->add("{name: observacoes; fieldLabel: Observações; type: textarea; value: ".base64_decode($registroAtual['observacoes'])."}");
		$frm->add("{name: gId; type: hidden; value: ".$gId."}");
		$frm->add("{name: gPage; type: hidden; value: ".$proximaPagina."}");
		return($frm->render($o));
	}


	function preparaCampos($todosOsCampos, $gId = 0)
	{
		global $usrId;
		$campos = array();
		$campos['tipo']='J';
		$campos['armazem']=1;
		$campos['cliente']=0;
		$campos['cliente_final']=0;
		$campos['apelido']=gCleanField($todosOsCampos['apelido']);
		$campos['nome']=gUcwords($todosOsCampos['nome']);
		$campos['email']=gCleanField($todosOsCampos['email']);
		$campos['telefone']=gCleanField($todosOsCampos['telefone']);
		$campos['celular']=gCleanField($todosOsCampos['celular']);
		$campos['situacao']=gCleanField($todosOsCampos['situacao']);
		$campos['email']=gCleanField($todosOsCampos['email']);
		if ($gId==0)
		{
			$campos['data_cadastro']=gDBDateTime($todosOsCampos['data_cadastro']);
			$campos['id_pessoas_criou']=intval($usrId);
		} else {
			$campos['data_alteracao']=gDBDateTime($todosOsCampos['data_cadastro']);
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

	function insere($campos)
	{
		$gId = false;
		if (($campos['senha']!=$campos['confirmacao']) ) //|| ($senha=='')
		{
			$this->erros[]="A senha e a confirmação devem ser iguais e diferentes de vazio!";
			$gId = false;
		} else
		{
			// Primeiro obtém o próximo id
			$gId=dbInsert('pessoas',$this->preparaCampos($campos), true);
			$campos['id_pessoas']=$gId;
			dbInsert('pessoas_juridicas', $this->preparaCamposAdicionais($campos));
			// Inserindo acesso ao armazem atual.
			$sql="SELECT
					*
				  FROM pessoas_armazens
				  WHERE id_pessoas=$gId
				  AND id_armazens=".$_SESSION["armazemAtualId"]."
				  AND cancelado=0
				  ";
			$existe=dbQuery($sql);
			if (count($existe)==0)
			{
				$mtz=array();
				$mtz["id_pessoas"]=$gId;
				$mtz["id_armazens"]=$_SESSION["armazemAtualId"];
				$mtz["id_pessoas_criou"]=$_SESSION["usrId"];
				$mtz["data_criou"]=date('Y-m-d H:i:s');
				dbInsert("pessoas_armazens", $mtz);
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
		$sucesso = true;
		if (($campos['senha']!=$campos['confirmacao']) ) //|| ($senha=='')
		{
			$this->erros[]="A senha e a confirmação devem ser iguais e diferentes de vazio!";
			$sucesso = false;
		} else
		{
			$sql="SELECT * FROM pessoas WHERE id>2 AND id=".$gId;
			$rs=dbQuery($sql);
			if (count($rs)>0)
			{
				dbUpdate('pessoas', $this->preparaCampos($campos), $gId);
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
}


/* -------------- ITENS -------------- */
include_once 'itens.php';



/* -------------- POSIÇÕES -------------- */


class Posicoes  extends Persistencia
{
	public $ultimosCamposUsados;
	public $ultimoRegistro;
	public $queryMapa="";

	function __construct()
	{
		$this->tabela = "posicoes";
		$this->filtro = "";
		$this->ordenacao = "a.descricao, p.codigo_barras";
	}

	function obtemQueryConsulta()
	{
		$sql="SELECT p.*, TP.sigla tipo, a.descricao area, pc.nome criou, pa.nome alterou, z.descricao armazem_descricao
				FROM posicoes p
				LEFT JOIN tipos_posicoes TP ON p.id_tipos_posicoes=TP.id
				LEFT JOIN armazens z ON p.id_armazens = z.id
				LEFT JOIN pessoas pc ON p.id_pessoas_criou=pc.id
				LEFT JOIN pessoas pa ON p.id_pessoas_alterou=pa.id
				LEFT JOIN areas a ON p.id_areas = a.id ";
		return($sql);
	}

	function geraCamposDoFormulario(&$frm, $registroAtual, $proximaPagina="")
	{
		// O formulário de edição de dados usa este método
		global $proximaPagina, $gId, $gPage, $o, $sp, $gParam;
		if ($proximaPagina=="")
		{
			$proximaPagina=$gPage+1;
		}
		$spSKUs = "SELECT * FROM posicoes WHERE id=".$gId." ORDER BY id";
		$tipoRua = "number";
		$tipoPredio = "number";
		$tipoAndar = "number";
		$tipoApartamento = "number";

		if (!is_numeric($gParam['POSICOES_FORMATO_RUA']['valor']))
		{
			$tipoRua = "upperText";
		}
		if (!is_numeric($gParam['POSICOES_FORMATO_PREDIO']['valor'])) {
			$tipoPredio = "upperText";
		}

		if (!is_numeric($gParam['POSICOES_FORMATO_ANDAR']['valor'])) {
			$tipoAndar = "upperText";
		}
		if (!is_numeric($gParam['POSICOES_FORMATO_APTO']['valor'])) {
			$tipoApartamento = "upperText";
		}
		$frm->row(
			$frm->add("{name: id_armazens; fieldLabel: Armazém; allowBlank: false; type: combo; value: ".$registroAtual['id_armazens']."; items: ".$sp['combo_armazens']."}"),
			$frm->add("{name: id_tipos_posicoes; fieldLabel: Tipo; allowBlank: false; type: combo; value: ".$registroAtual['id_tipos_posicoes']."; items: ".$sp['combo_tipos_posicoes']."}"),
			$frm->add("{name: id_areas; fieldLabel: Área; allowBlank: false; type: combo; value: ".$registroAtual['id_areas']."; items: ".$sp['combo_areas']."}"),
			$frm->add("{name: observacoes; fieldLabel: Observações; type: text; value: ".$registroAtual['observacoes']."}")
		);
		$largura=(isset($registroAtual['largura'])) ? $registroAtual['largura'] : "1.00";
		$altura=(isset($registroAtual['altura'])) ? $registroAtual['altura'] : "1.68";
		$comprimento=(isset($registroAtual['comprimento'])) ? $registroAtual["comprimento"]:"1.20";
		$peso_suportado=(isset($registroAtual['peso_suportado'])) ? $registroAtual["peso_suportado"]:"0";
		$frm->row(
			$frm->add("{name: largura; fieldLabel: Largura (em metros); type: number; value: ".gFloat($largura)."}"),
			$frm->add("{name: altura; fieldLabel: Altura (em metros); type: number; value: ".gFloat($altura)."}"),
			$frm->add("{name: comprimento; fieldLabel: Comprimento (em metros); type: number; value: ".gFloat($comprimento)."}"),
			$frm->add("{name: peso_suportado; fieldLabel: Peso suportado; type: number; value: ".gFloat($peso_suportado)."}")
		);

		/*Labels do campo modificam para quem tem o parametro USA_POSICAO_COMO_UMA ativo*/
		$labelsPosicao = array(
			'predio' => 'Prédio',
			'andar'  => 'Andar',
			'modulo' => 'Módulo',
			'apartamento' => 'Apartamento'
		);

		if ($gParam["USA_POSICAO_COMO_UMA"]["ativo"]) {
			$labelsPosicao = array(
				'predio' => 'Módulo',
				'andar'  => 'Nível',
				'modulo' => 'Área',
				'apartamento' => 'Posição'
			);
			$frm->add('{name: posicaoFixa; type: hidden; value: 1;}');
		}

		if ($gParam["USA_MODULO_EM_POSICOES"]["ativo"])
		{
			$tipoModulo = "number";
			if (!is_numeric($gParam['POSICOES_FORMATO_MODULO']['valor']))
			{
				$tipoModulo= "upperText";
			}
			$frm->row(
				$frm->add("{name: quantidade; fieldLabel: Qtd. máxima de UMAs; type: number; value: ".($registroAtual['quantidade']==0?1:$registroAtual['quantidade'])."}"),
				$frm->add("{name: modulo; fieldLabel: " . $labelsPosicao['modulo'] . "; type:$tipoModulo; value:".$registroAtual["modulo"]."; maxLength:".strlen($gParam["POSICOES_FORMATO_MODULO"]["valor"]).";}"),
				$frm->add("{name: rua; fieldLabel: Rua; type: $tipoRua; maxLength: ".strlen($gParam['POSICOES_FORMATO_RUA']['valor'])."; value: ".$registroAtual['rua']."}"),
				$frm->add("{name: predio; fieldLabel: " . $labelsPosicao['predio'] . " ou blocado; type: $tipoPredio; maxLength: ".strlen($gParam['POSICOES_FORMATO_PREDIO']['valor'])."; value: ".$registroAtual['predio']."}"),
				$frm->add("{name: andar; fieldLabel: " . $labelsPosicao['andar'] . "; type: " . $tipoAndar . "; maxLength: ".strlen($gParam['POSICOES_FORMATO_ANDAR']['valor'])."; value: ".$registroAtual['andar']."}"),
				$frm->add("{name: apartamento; fieldLabel: " . $labelsPosicao['apartamento'] . "; type: " . $tipoApartamento .  "; maxLength: ".strlen($gParam['POSICOES_FORMATO_APTO']['valor'])."; value: ".$registroAtual['apartamento']."}")
			);
			$frm->row(
				$frm->add("{name: picking; fieldLabel: Picking; type: checkbox; value: ".($gId==0?1:$registroAtual['picking'])."}")
			);
		} else
		{
			$frm->row(
				$frm->add("{name: quantidade; fieldLabel: Qtd. máxima de UMAs; type: number; value: ".($registroAtual['quantidade']==0?1:$registroAtual['quantidade'])."}"),
				$frm->add("{name: rua; fieldLabel: Rua; type: $tipoRua; maxLength: ".strlen($gParam['POSICOES_FORMATO_RUA']['valor'])."; value: ".$registroAtual['rua']."}"),
				$frm->add("{name: predio; fieldLabel: " . $labelsPosicao['predio'] . " ou blocado; type: $tipoPredio; maxLength: ".strlen($gParam['POSICOES_FORMATO_PREDIO']['valor'])."; value: ".$registroAtual['predio']."}"),
				$frm->add("{name: andar; fieldLabel: " . $labelsPosicao['andar'] . "; type: " . $tipoAndar . "; maxLength: ".strlen($gParam['POSICOES_FORMATO_ANDAR']['valor'])."; value: ".$registroAtual['andar']."}"),
				$frm->add("{name: apartamento; fieldLabel:  " . $labelsPosicao['apartamento'] . "; type: " . $tipoApartamento . "; maxLength: ".strlen($gParam['POSICOES_FORMATO_APTO']['valor'])."; value: ".$registroAtual['apartamento']."}"),
				$frm->add("{name: picking; fieldLabel: Picking; type: checkbox; value: ".($gId==0?1:$registroAtual['picking'])."}")
			);
		}

		$frm->row(
			$frm->add("{name: ativo; fieldLabel: Ativo; type: checkbox; value: ".($gId==0?1:$registroAtual['ativo'])."}"),
			$frm->add("{name: palete_vazio; fieldLabel: Apenas paletes vazios ?; type: checkbox; value:".($gId==0?0:$registroAtual['palete_vazio']).";}")
		);

		$frm->add("{name: gId;type: hidden; value: ".$gId."}");
		$frm->add("{name: gPage; type: hidden; value: ".$proximaPagina."}");
		return($frm->render($o));
	}

	function formataCodigoBarras(&$campos, $retornarCodigoBarras = 0)
	{
		global $gParam, $gId;

		$sql = "SELECT codigo_barras FROM armazens WHERE id = " . (int) $campos['id_armazens'];
		$rst = dbQuery($sql)[0];
		$sep = $gParam['POSICOES_FORMATO_SEPARADOR']['valor'];
		if (is_numeric($gParam['POSICOES_FORMATO_ARMAZEM']['valor']))
		{
			$armazem=str_pad(intval($rst['codigo_barras']), strlen($gParam['POSICOES_FORMATO_ARMAZEM']['valor']), '0', STR_PAD_LEFT) . $sep;
		} else
		{
			$armazem = $rst['codigo_barras'] . $sep;
		}

		$campos['codigo_barras']="0";

		if ($gParam["POSICOES_FORMATO_ARMAZEM"]["ativo"]==0)
		{
			$armazem = '';
		}
		if ($campos['apartamento']) {
			$apartamento = $sep .  padronizarCampoPosicao($campos['apartamento'], $gParam['POSICOES_FORMATO_APTO']['valor']);
		} else {
			$apartamento=$sep.$campos['apartamento'];
		}

		if ($gParam["POSICOES_FORMATO_APTO"]["ativo"]==0)
		{
			$apartamento='';
		}
		// BOMIX UTILIZARÁ BLOCADO SEM A INFORMAÇÃO DO ARMAZÉM.
		if ($gParam['USA_ARMAZEM_EM_POSICOES_BLOCADO']['ativo']==0
			&& $campos['apartamento']==0
			&& $campos['andar']==0
			&& !$gParam['USA_AREA_CODIGO_BARRAS_POSICAO']['ativo']
		) {
			$armazem='';
		}

		if($gParam['PERFIL_FABRICANTE']['ativo'] == 1){
			if($campos['quantidade']>2 && $campos['quantidade']<6)
			{
				$armazem='';
				$apartamento='';
			}
		}

		if (intval($campos['predio'])/2 == intval(intval($campos['predio'])/2)) {
			$campos['lado'] = "P";// par
		} else {
			$campos['lado'] = "I"; // impar
		}

		if ($gParam['USA_AREA_CODIGO_BARRAS_POSICAO']['ativo']) {
			$codigoArea = gFieldById('areas', $campos['id_areas'], 'codigo');
			if ($campos["modulo"] <> '' && $gParam["USA_MODULO_EM_POSICOES"]["ativo"]) {
				$modulo = $campos["modulo"] . $sep;
			}

			if ($gParam['FORMATA_POSICAO_RUA_ANDAR_PREDIO']['ativo']) {
				$campos['codigo_barras'] = $armazem
					. $modulo
					. $codigoArea
					. $sep . $campos['rua']
					. $sep . $campos['andar']
					. $sep . $campos['predio']
					. $sep . $campos['apartamento'];
			} else {
				$campos['codigo_barras'] = $armazem
					. $modulo
					. $codigoArea
					. $sep . $campos['rua']
					. $sep . $campos['predio']
					. $sep . $campos['andar']
					. $sep . $campos['apartamento'];
			}

			if (
				$gParam['USA_ARMAZEM_EM_POSICOES_BLOCADO']['ativo']
				&& $campos['apartamento'] == 0
				&& $campos['andar'] == 0
			) {
				$campos['codigo_barras'] = $armazem
				. $modulo
				. $codigoArea
				. $sep . $campos['rua']
				. $sep . $campos['predio'];
			}

			if ($retornarCodigoBarras) {
				return $campos['codigo_barras'];
			}

			return $campos;
		}

		if ($campos['andar']==0 && !$gParam['USA_ANDAR_ZERO']['ativo'])
		{
			// Não tem andar, então é um blocado
			if ($gParam["USA_MODULO_EM_POSICOES"]["ativo"])
			{
				$campos['codigo_barras']=$armazem.$campos["modulo"].$sep.$campos['rua'].$sep.$campos['predio'];
			} else
			{
				$campos['codigo_barras']=$armazem.$campos['rua'].$sep.$campos['predio'];
			}
		} elseif ($campos['apartamento']==0) {
			if ($gParam["USA_MODULO_EM_POSICOES"]["ativo"]) {
				// Não tem apartamento então é um porta-palete
				$campos['codigo_barras']=$armazem.$campos["modulo"].$sep.$campos['rua'].$sep.$campos['predio'].$sep.$campos['andar'].$apartamento;
			}

			if ($gParam["USA_MODULO_EM_POSICOES"]["ativo"] && $gParam["ESTRUTURA_DIFERENTE_DRIVEIN"]["ativo"]) {
				// Não tem apartamento então é um porta-palete
				if (
					$gParam['USA_ANDAR_ZERO']['ativo']
					&& $campos['andar'] == 0
				) {
					$campos['codigo_barras']=$armazem.$campos["modulo"].$sep.$campos['rua'].$sep.$campos['predio'];
				} else {
					$campos['codigo_barras']=$armazem.$campos["modulo"].$sep.$campos['rua'].$sep.$campos['predio'].$sep.$campos['andar'].$apartamento;
				}
			} else {
				if (
					$gParam['USA_ANDAR_ZERO']['ativo']
					&& $campos['andar'] == 0
				) {
					$campos['codigo_barras']=$armazem.$campos['rua'].$sep.$campos['predio'];
				} else {
					$campos['codigo_barras']=$armazem.$campos['rua'].$sep.$campos['predio'].$sep.$campos['andar'].$apartamento;
				}
			}
		} else {

			// Drive-in
			if ($gParam["USA_MODULO_EM_POSICOES"]["ativo"])
			{
				$campos['codigo_barras']=$armazem.$campos["modulo"].$sep.$campos['rua'].$sep.$campos['predio'].$sep.$campos['andar'].$apartamento;
			} else
			{
				$campos['codigo_barras']=$armazem.$campos['rua'].$sep.$campos['predio'].$sep.$campos['andar'].$apartamento;
			}
		}

		if ($retornarCodigoBarras) {
			return $campos['codigo_barras'];
		}

		return(true);
	}

	function preparaCampos($todosOsCampos, $gId = 0)
	{
		global $usrId, $gParam;
		$hoje = date('Y-m-d H:i:s');
		$campos=array();
		$campos['ativo']=gDBCheck($todosOsCampos['ativo']);
		$campos['palete_vazio']=gDBCheck($todosOsCampos['palete_vazio']);
		$campos['picking']=gDBCheck($todosOsCampos['picking']);
		$campos['id_armazens']=intval($todosOsCampos['id_armazens']);
		$campos['id_areas']=intval($todosOsCampos['id_areas']);
		$campos['id_tipos_posicoes']=intval($todosOsCampos['id_tipos_posicoes']);
		$campos['quantidade'] = (int) $todosOsCampos['quantidade'] ?: 1;// minimo eh sempre 1 para este campo
		$campos['largura']=gDBFloat($todosOsCampos['largura']);
		$campos['altura']=gDBFloat($todosOsCampos['altura']);
		$campos['peso_suportado']=gDBFloat($todosOsCampos["peso_suportado"]);
		$campos['comprimento']=gDBFloat($todosOsCampos['comprimento']);

		if (gDBCheck($_REQUEST['posicaoFixa'])) {
			//valores devem assegurar que a posicao contera qualquer item
			$campos['quantidade'] = 1;
			$campos['largura'] = 100000;
			$campos['altura']  = 100000;
			$campos['comprimento'] = 100000;
			$campos['peso_suportado'] = 100000;
		}

		$campos['observacoes']=($todosOsCampos['observacoes']);
		if ($gParam['USA_POSICAO_COMO_UMA']['ativo']) {
			$caractereCompletivo = ' ';
			$campos['andar'] = (int) $todosOsCampos['andar'];
			$campos['apartamento'] = (int) $todosOsCampos['apartamento'];
			$campos["modulo"] = (int) $todosOsCampos['modulo'];
			$campos['rua'] = (int) $todosOsCampos['rua'];
			$campos['predio'] = (int) $todosOsCampos['predio'];

		} else {
			$caractereCompletivo = '0';

			$campos['andar'] = padronizarCampoPosicao($todosOsCampos['andar'], $gParam['POSICOES_FORMATO_ANDAR']['valor']);
			$campos['apartamento'] = padronizarCampoPosicao($todosOsCampos['apartamento'], $gParam['POSICOES_FORMATO_APTO']['valor']);

			if ($gParam["USA_MODULO_EM_POSICOES"]["ativo"]) {
				$campos['modulo'] = padronizarCampoPosicao($todosOsCampos['modulo'], $gParam['POSICOES_FORMATO_MODULO']['valor']);
			}
			$campos['rua'] = padronizarCampoPosicao($todosOsCampos['rua'], $gParam['POSICOES_FORMATO_RUA']['valor']);
			$campos['predio'] = padronizarCampoPosicao($todosOsCampos['predio'], $gParam['POSICOES_FORMATO_PREDIO']['valor']);
		}

		if ($campos['andar']>0 && $campos['apartamento']==0)
		{
			$campos['apartamento']=1;
		}
		if ($campos['quantidade']==0)
		{
			$campos['quantidade']=1;
		}

		$this->formataCodigoBarras($campos);

		if ($gId==0)
		{
			$campos['data_cadastro']      =$hoje;
			$campos['id_pessoas_criou']   =$usrId;
		}else {
			$campos['data_alteracao']     =$hoje;
			$campos['id_pessoas_alterou'] =$usrId;
		}
		$this->ultimosCamposUsados=$campos;
		return($campos);
	}

	function insere($campos)
	{
		global  $gParam;
		$gId = false;
		// Validações:
		$campos = $this->preparaCampos($campos, $gId);
		$sql = "SELECT * FROM posicoes WHERE codigo_barras='".$campos['codigo_barras']."' OR '".$campos['codigo_barras']."'='0'";
		$rst=dbQuery($sql);

		if (count($rst)>0)
		{
			$this->ultimoRegistro = $rst[0];
			$gId = false;
			$this->erros[]="Posição já existe [".$campos['codigo_barras']."]";
		} else {
			$gId=dbInsert('posicoes', $campos, true);
			if (
				$gId
				&& $gParam['USA_POSICAO_COMO_UMA']['ativo']
				&& gDBCheck($_REQUEST['posicaoFixa'])
			) {
				$this->inserirUMAcomCodigoPosicao($gId);
			}
		}
		return($gId);
	}


	function modifica($campos, $gId)
	{
		global $o, $gParam;
		$sucesso = true;
		$campos = $this->preparaCampos($campos, $gId);
		$sql = "SELECT * FROM posicoes WHERE id<>$gId AND codigo_barras='".$campos['codigo_barras']."' OR '".$campos['codigo_barras']."'='0'";
		$rst=dbQuery($sql);
		$this->ultimoRegistro = $rst[0];
		if (count($rst)>0)
		{
			$sucesso = false;
			$this->erros[]="Posição já existe [".$campos['codigo_barras']."] ";
		} else {
			dbUpdate('posicoes', $campos, $gId);
			if (
				$gId
				&& $gParam['USA_POSICAO_COMO_UMA']['ativo']
				&& gDBCheck($_REQUEST['posicaoFixa'])
			) {
				$this->inserirUMAcomCodigoPosicao($gId);
			}
		}

		return($sucesso);
	}


	public function inserirUMAcomCodigoPosicao($gId)
	{
		$sql = "SELECT * FROM posicoes WHERE id = " . $gId;
		$posicao = dbQuery($sql)[0];

		$sql = "
			SELECT umas.id, umas.id_posicoes
			FROM umas
			JOIN posicoes ON posicoes.codigo_barras = umas.codigo_barras
			WHERE posicoes.id = " . $gId;
		$uma = dbQuery($sql);

		//Se existir esta UMA em outra posição
		if ($uma['id_posicoes'] <> $gId) {
			$this->erros[] = 'UMA não foi criada para esta posição pois já existe com este código de barras. Posicione a UMA ' . linkParaUMA(formataUMA($posicao['codigo_barras'])) . ' na posição correspondente';
		}

		//Se existe posicao E UMA - NÃO insere
		if ($uma) return;


		$sql = "
			INSERT INTO umas (
				id_armazens,
				id_posicoes,
				id_posicoes_posicionar,
				id_programacao,
				id_programacao_itens,
				id_notas,
				id_itens_skus_associar,
				id_tipos_umas,
				id_contagens,
				id_pessoas_conferiu,
				id_pessoas_conferiu_saida,
				`data`,
				data_ativacao,
				data_desativacao,
				data_conferencia,
				data_conferencia_saida,
				data_paletizacao,
				data_filmagem,
				data_fumigacao,
				data_saida,
				ativo,
				posicionada,
				conferida,
				conferida_saida,
				liberada,
				paletizada,
				filmada,
				fumigada,
				indivisivel,
				imobilizada,
				produzida,
				incompleta,
				codigo_barras,
				codigo_externo,
				situacao,
				complemento,
				ordem,
				ordem_movimentacao,
				saida_avulsa,
				id_pessoas_posicionou,
				observacoes,
				data_posicionamento
			) VALUES (
				1,
				'" . $posicao['id'] . "',
				0,
				0,
				0,
				0,
				0,
				1,
				0,
				0,
				0,
				NOW(),
				NOW(),
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				1,
				1,
				0,
				0,
				1,
				0,
				0,
				0,
				0,
				0,
				0,
				0,
				'" . $posicao['codigo_barras'] . "',
				'',
				'',
				'',
				0,
				0,
				0,
				'" . (int) $_SESSION['usrId'] . "',
				'',
				NOW()
			)";
		dbFastQuery($sql);
	}


	function mapaMontaLado($lado, $predios, $armazem, $rua, $andares, $apartamentos, $selecionados)
	{
		global $o;
		$primeiroLado = 'I';
		$html.='<div class="b">';
		//$html.='rua<br>';
		foreach ($predios[$armazem][$rua] as $predio=>$pValue)
		{
			$faz = true;
			foreach ($andares[$armazem][$rua][$predio] as $andar=>$andValue)
			{
				// Usa o primeiro andar como referencia
				if ($faz)
				{
					if ($lado<>$primeiroLado)
					{
						$html.='<div class="b" style="vertical-align: top">';
						$html.=$predio.'<br>';
					} else {
						$html.='<div class="b" style="vertical-align: bottom">';

					}
					$aptos = '';
					foreach ($apartamentos[$armazem][$rua][$predio][$andar] as $apartamento=>$aptoValue)
					{
						$colunas = '';
						$sel = '';
						foreach ($andares[$armazem][$rua][$predio] as $andar=>$andarValue)
						{
							$codBarras = $apartamentos[$armazem][$rua][$predio][$andar][$apartamento]['codigo_barras'];
							$area = $apartamentos[$armazem][$rua][$predio][$andar][$apartamento]['area'];

							if (isset($selecionados[$codBarras]) )
							{
								$sel = 's';
								$codBarras = '<a href=index.php?g=informacoes&gPage='.PESQUISAR.'&forcar=posicao&buscar='.$apartamentos[$armazem][$rua][$predio][$andar][$apartamento]['codigo_barras'].'>'.$codBarras.'</a>';
								$colunas[] = '<b>'.$andar.'</b> - '.$codBarras;
							} else {
								$codBarras = '<a href=index.php?g=posicoes&gPage=10&gId='.$apartamentos[$armazem][$rua][$predio][$andar][$apartamento]['id'].'>'.$codBarras.'</a>';
								$colunas[] = '<b>'.$andar.'</b> - '.$codBarras;
							}
						}

						$css=(intval($apartamento)==0)
							? "style='background: #ccc;'"
							: "";

						$colunas = array_reverse($colunas);
						$colunas = implode('<br>',$colunas);
						$apartamento = '<a data-toggle="popover" title="Andares" data-html="true" data-content="'.$colunas.'" style="cursor: pointer">'.$apartamento.'</a>';
						$aptos[]='	<div '.$css.' class="p '.$sel.'">'.$apartamento.'</div>';
					}
					if ($lado==$primeiroLado)
					{
						$aptos = array_reverse($aptos);
					}
					$html.=implode("\n",$aptos);
					if ($lado==$primeiroLado)
					{
						$html.=$predio.'<br>';
					}
					$html.='</div>';
				}
				$faz = false;
			}
		}
		$html.='</div>';
		//$html.='<br><br><br><br>';
		return($html);
	}

	function mapa($selecionados="")
	{
		global $o,$gParam;
		$html.='
			<style>
				.b {padding: 0px; margin: 0px; spacing: 0px; border: 0px none; text-align: center; display: table-cell}
				.p {width: 32px; height: 32px; border: 1px solid #404040; display: block; text-align: center;}
				.s {background-color:#ffff80}
			</style>';
		$sql="SELECT p.*, a.descricao area, pc.nome criou, pa.nome alterou, z.descricao armazem, z.codigo_barras codigo_barras_armazem
				FROM posicoes p
				LEFT JOIN armazens z ON p.id_armazens = z.id
				LEFT JOIN pessoas pc ON p.id_pessoas_criou=pc.id
				LEFT JOIN pessoas pa ON p.id_pessoas_alterou=pa.id
				LEFT JOIN areas a ON p.id_areas = a.id
				WHERE p.ativo=1
				ORDER BY id_armazens, rua, predio, lado, andar, apartamento";
		$rs = dbQuery($sql);

		$id_armazens = "";
		$rua         = "";
		$predio      = "";
		$andar       = "";
		$apartamento = "";
		$predios     = "";
		$sep = $gParam['POSICOES_FORMATO_SEPARADOR']['valor'];
		$html.=$o->msg("Visualização do armazém pelo alto");
		foreach ($rs as $row)
		{
			$armazens[$row['codigo_barras_armazem']] = $row['armazem'];
			$ruas[$row['codigo_barras_armazem']][$row['rua']]=1;
			$predios[$row['codigo_barras_armazem']][$row['rua']][$row['predio']]=1;
			if ($row['predio']/2==intval($row['predio']/2))
			{
				$prediosPar[$row['codigo_barras_armazem']][$row['rua']][$row['predio']]=1;
			} else {
				$prediosImpar[$row['codigo_barras_armazem']][$row['rua']][$row['predio']]=1;
			}
			$andares[$row['codigo_barras_armazem']][$row['rua']][$row['predio']][$row['andar']]=1;
			$apartamentos[$row['codigo_barras_armazem']][$row['rua']][$row['predio']][$row['andar']][$row['apartamento']]=$row;
			//$rows[$row['codigo_barras_armazem']][$row['rua']][$row['predio']][$row['andar']][$row['apartamento']]=$row;

		}
		foreach ($armazens as $armazem => $nomeArmazem)
		{
			$html.=$o->msgInfo("Armazém • ".$nomeArmazem);
			foreach ($ruas[$armazem] as $rua => $ruasDoArmazem)
			{
				$html.=$this->mapaMontaLado("I", $prediosImpar, $armazem, $rua, $andares, $apartamentos, $selecionados);
				$html.=$o->label("Rua ".$rua);
				$html.=$this->mapaMontaLado("P", $prediosPar, $armazem, $rua, $andares, $apartamentos, $selecionados);
				$html.='<hr>';
			}
		}

		// gD($armazens);
		// gD($ruas);
		// gD($predios);
		// gD($andares);
		// gD($apartamentos);
		return($html);
	}
}
