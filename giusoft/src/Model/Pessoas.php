<?php
class Pessoas
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


	function obtemRegistros($filtro = "", $ordenacao = "", $agrupamento = "", $naoLimitar = '')
	{
		global $gParam;
		$sql=$this->obtemQueryConsulta();
		$filtros=[];
		$ordenacoes=[];
		$agrupamentos=[];
		if ($this->filtro<>"") { $filtros[]=$this->filtro; }
		if ($filtro<>"") { $filtros[]=$filtro; }

		if ($this->ordenacao<>"") { $ordenacoes[]=$this->ordenacao; }
		if ($ordenacao<>"") { $ordenacoes[]=$ordenacao; }

		if ($this->agrupamento<>"") { $agrupamentos[]=$this->agrupamento; }
		if ($agrupamento<>"") { $agrupamentos[]=$agrupamento; }

		if (count($filtros) > 0) {
			$sql.=" WHERE ".implode(" AND ", $filtros);
		}

		if (count($agrupamentos) > 0) {
			$sql.=" GROUP BY ".implode(", ", $agrupamentos);
		}

		if (count($ordenacoes) > 0) {
			$sql.=" ORDER BY ".implode(", ", $ordenacoes);
		}
		if ($gParam["PAGINACAO"]["ativo"]==1) {
			$this->porPagina=$gParam["PAGINACAO"]["valor"];
		}
		if (!$naoLimitar) {
			if ($this->porPagina>0) {
				include_once 'Pagination.php';
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


	// Atualiza a situação da pessoa se ela não tiver cadastrado o filial
	public function atualizarPessoasSituacao($idPessoa) {
		$sql = "SELECT id FROM pessoas_filial WHERE cancelado = 0 AND id_pessoas = " . $idPessoa . " LIMIT 1";
		$verificarPessoasFilial = dbFastQuery($sql)[0]['id'];

		if (!$verificarPessoasFilial) {
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