<?


//include $gPathDefault."gPage.php";
include $gPathDefault."gDB.php";
include $gPathDefault.'gPDF.php';

define(gB_ITAU, "341");
define(gB_BB, "0");

class gBoleto extends gPdf {
	// GERA BOLETO EM PDF
	/*
	
	//Exemplo de utilização:
	
	include "inc_2.0/gConf.php";
	include "inc_2.0/gBoletos.php";
	
	$out=new gBoleto();
	$out->gPDFBegin();
	$out->gPreparaBoleto(gB_ITAU,1,0,250,465);
	
	$out->gBoletoVazio(); // boleto 1 - recibo do sacado
	$out->gBoletoDados();
	
	$out->gBoletoVazio(); // boleto 2 - ficha de caixa
	$out->gBoletoDados();
	
	$out->gBoletoVazio(); // boleto 3 - código de barras
	$out->gBoletoDados();
	
	$out->gPDFEnd();
	
	*/
	var $dados;
	var $banco;
	var $margem_esquerda;
	var $margem_superior;
	var $opdf;
	var $boleto_atual;

	function gPDFBegin($logomarca = false) {
		$orientation = "P";
		$this->boleto_atual = 0;
		$this->margem_esquerda = 10;

		$this->gPdf($orientation);
		if ($logomarca) {
			$this->logomark = true;
			$this->report = false;
			$this->showfooter = false;
			$this->margem_superior = 10;
		} else {
			$this->logomark = false;
			$this->report = false;
			$this->showfooter = false;
			$this->margem_superior = 10;
		}
		$this->SetFont('helvetica', '', 7);
		$this->detailfontsize = 7;
		$this->Open();
		$this->AddPage();
	}

	function gPDFEnd($arquivo = "boleto.pdf", $download = true) {
		$this->Output($arquivo, $download);
	}

	function gPreparaBoleto($banco = gB_ITAU, $id_empresa = 0, $id_pessoa = 0, $valor = 0, $nosso_numero = 0, $numero_documento = '', $tipo_boleto = '', $mes = '', $ano = '', $id_banco = 1, $data_automatica="")
		//o campo tipo_boleto = 0 e gerado pelo empresa
		//o campo tipo_boleto = 1 e gerado pelo site(cliente)
		//	function gPreparaBoleto($banco=gB_ITAU,$id_empresa=0,$id_pessoa=0,$valor=0,$nosso_numero=0)

	{

			// Zera variáveis
		$this->dados = "";
		$this->banco = $banco;
		$fmt_data = gVar("global.dateformat");
		gVar("global.dateformat", "dd/mm/yyyy");

		// Acesso ao banco
		$_rs = gQuery("select * from fin_bancos where id=$id_banco");
		
		/*$sql = "select geral_empresas.*,geral_empresas_enderecos.*,geral_cidades.descricao as cidade,geral_estados.descricao as estado from geral_empresas left join geral_empresas_enderecos on geral_empresas_enderecos.id_geral_empresas=geral_empresas.id left join geral_cidades on geral_empresas_enderecos.id_geral_cidades=geral_cidades.id left join geral_estados on geral_empresas_enderecos.id_geral_estados=geral_estados.id where geral_empresas.id=1";*/
		$sql = "select p.*,e.*,c.descricao as cidade,s.descricao as estado from geral_pessoas p left join geral_pessoas_enderecos e on e.id_geral_pessoas=p.id left join geral_cidades c on e.id_geral_cidades=c.id left join geral_estados s on e.id_geral_estados=s.id where p.id=1";
		$rs = gQuery($sql);
		if ($id_empresa > 0) {
			// Boleto para pessoa jurídica
			$sql = "select geral_empresas.id as id_cli,geral_empresas.*,geral_empresas_enderecos.*,geral_cidades.descricao as cidade,geral_estados.descricao as estado from geral_empresas left join geral_empresas_enderecos on geral_empresas_enderecos.id_geral_empresas=geral_empresas.id left join geral_cidades on geral_empresas_enderecos.id_geral_cidades=geral_cidades.id left join geral_estados on geral_empresas_enderecos.id_geral_estados=geral_estados.id where geral_empresas.id=".$id_empresa;

			$rsc = gQuery($sql);

			$this->dados['id_cliente'] = $id_empresa;
			$this->dados['tp_cliente'] = "id_geral_empresas";
			//REPRESENTA O BOLETO VINDO DO SITE OKSCM id_banco=13
			$this->dados['sacado1'] = $rsc->fields['razao_social'];
			if ($id_banco <> 13) {
				$this->dados['sacador_cnpj'] = "CNPJ: ".$rsc->fields['cnpj'];
				$this->dados['sacado_cnpj'] = "CNPJ: ".$rsc->fields['cnpj'];

				$this->dados['sacado2'] = $rsc->fields['endereco']." ".$rsc->fields['complemento'];
				$this->dados['sacado3'] = $rsc->fields['bairro']." - ".$rsc->fields['cidade']."/".$rsc->fields['estado']."  ".$rsc->fields['cep'];
				$this->dados['sacador'] = $rsc->fields['razao_social'];
			}

		}

		if ($id_pessoa > 0) {
			// Boleto para pessoa física
			$sql = "select geral_pessoas.nome as razao_social,geral_pessoas.cpf,geral_pessoas.id as id_cli,geral_cidades.descricao as cidade,geral_estados.descricao as estado,geral_pessoas_enderecos.* from geral_pessoas left join geral_pessoas_enderecos on geral_pessoas_enderecos.id_geral_pessoas=geral_pessoas.id left join geral_estados on geral_pessoas_enderecos.id_geral_estados=geral_estados.id left join geral_cidades on geral_pessoas_enderecos.id_geral_cidades=geral_cidades.id where geral_pessoas.id=".$id_pessoa;
			$rsc = gQuery($sql);

			$this->dados['id_cliente'] = $id_pessoa;
			$this->dados['tp_cliente'] = "id_geral_pessoas";
			//REPRESENTA O BOLETO VINDO DO SITE OKSCM id_banco=13
			$this->dados['sacado1'] = $rsc->fields['razao_social'];
			if ($id_banco <> 13) {
				$this->dados['sacador_cnpj'] = "CPF: ".$rsc->fields['cpf'];

				$this->dados['sacado2'] = $rsc->fields['endereco']." ".$rsc->fields['complemento'];
				$this->dados['sacado3'] = $rsc->fields['bairro']." - ".$rsc->fields['cidade']."/".$rsc->fields['estado']."  ".$rsc->fields['cep'];
				$this->dados['sacador'] = $rsc->fields['razao_social'];
			}

		}

		// Associando valores
		//criar campo $Lanc_extra
		$this->dados['mes'] = $mes;
		$this->dados['ano'] = $ano;
		$this->dados['tipo_boleto'] = $tipo_boleto;
		$this->dados['codigo_banco'] = $banco;
		$this->dados['codigo_banco_digito'] = $this->_geraCodigoBanco($banco);
		$this->dados['emissao'] = gDate(date("Y-m-d"));
		$this->dados['numero_documento'] = $numero_documento;
		$this->dados['vencimento'] = gDateAdd($this->dados['emissao'], $_rs->fields['vencimento_em_dias_apos_emissao']);

		$this->dados['valor'] = $valor;

		$this->dados['carteira'] = $_rs->fields['carteira'];
		$this->dados['cedente'] = $rs->fields['razao_social'];
		$this->dados['agencia'] = $_rs->fields['boleto_agencia'];
		$this->dados['conta'] = $_rs->fields['boleto_conta'];
		$this->dados['codigo_cliente'] = $_rs->fields['codigo_cliente'];
		
		$this->dados['data_automatica'] = $data_automatica;

		if ($id_banco == 13) {
			$this->dados['instrucoes1'] = "NÃO RECEBER APÓS VENCIMENTO";
			$this->dados['instrucoes2'] = "";
		} else {
			$this->dados['instrucoes1'] = "APÓS VENCIMENTO COBRAR R$ ".gFloat($valor * $_rs->fields['percentual_permanencia'] / 100)." POR DIA DE ATRASO";
			$this->dados['instrucoes2'] = "APÓS VENCIMENTO COBRAR MULTA DE R$ ".gFloat($valor * $_rs->fields['percentual_multa'] / 100);
		}

		$this->dados['instrucoes3'] = "";

		/*if ($this->banco==gB_ITAU)
		{
			$this->dados['nosso_numero']=substr("00000000".$nosso_numero,-8);
			$this->dados['nosso_numero_digito']=$this->dados['nosso_numero'].$this->_modulo10($this->dados['carteira'].$this->dados['nosso_numero']);
			
			$this->dados['nosso_numero_texto']=$this->dados['carteira']."/".substr("00000000".$nosso_numero,-8)."-".$this->_modulo10($this->dados['carteira'].$this->dados['nosso_numero']);
		}*/

		if ($this->banco == gB_ITAU) {
			$this->dados['nosso_numero'] = substr("00000000".$nosso_numero, -8);
			$this->dados['nosso_numero_digito'] = $this->dados['nosso_numero'].$this->_modulo10($this->dados['carteira'].$this->dados['nosso_numero']);
			$this->dados['nosso_numero_texto'] = $this->dados['carteira']."/".substr("00000000".$nosso_numero, -8)."-".$this->_modulo10($this->dados['carteira'].$this->dados['nosso_numero']);
		}
		//echo $this->dados['nosso_numero'];
		gVar("global.dateformat", $fmt_data);
	}

	function gBoletoVazio() {
		// Gera saída da grade do boleto (formulário vazio)
		global $gPathImg;
		$mesq = $this->margem_esquerda;
		$ba = $this->boleto_atual;
		if ($ba == 0)
			$msupb = $this->margem_superior;
		elseif ($ba == 1) $msupb = $this->margem_superior + 90;
		else
			//$msupb = $this->margem_superior + 191;
			$msupb = $this->margem_superior + 184;
		$this->boleto_atual = $this->boleto_atual + 1;
		// *** BOLETO
		// Linhas
		$this->SetDrawColor(240, 240, 240);
		$this->Line($mesq, $msupb, $mesq +193, $msupb); // Linha 1 (picote)
		$this->SetDrawColor(0, 0, 0);
		$this->Line($mesq, $msupb +8.5, $mesq +193, $msupb +8.5); // Linha 2
		$this->Line($mesq, $msupb +17.5, $mesq +193, $msupb +17.5); // Linha 3
		$this->Line($mesq, $msupb +23.6, $mesq +193, $msupb +23.6); // Linha 4
		$this->Line($mesq, $msupb +30.2, $mesq +193, $msupb +30.2); // Linha 5
		$this->Line($mesq, $msupb +36.3, $mesq +193, $msupb +36.3); // Linha 6 (instrucoes)
		$this->Line($mesq, $msupb +66.5, $mesq +193, $msupb +66.5); // Linha 7 (sacado)
		$this->Line($mesq, $msupb +81, $mesq +193, $msupb +81); // Última linha (8)
		$this->Line($mesq +136, $msupb +41.8, $mesq +193, $msupb +41.8);
		$this->Line($mesq +136, $msupb +47.8, $mesq +193, $msupb +47.8);
		$this->Line($mesq +136, $msupb +53.8, $mesq +193, $msupb +53.8);
		$this->Line($mesq +136, $msupb +59.8, $mesq +193, $msupb +59.8);
		$this->Line($mesq +136, $msupb +8.5, $mesq +136, $msupb +66.5);
		$this->Line($mesq +37.5, $msupb +2, $mesq +37.5, $msupb +8.5);
		$this->Line($mesq +55.8, $msupb +2, $mesq +55.8, $msupb +8.5);
		$this->Line($mesq +33.7, $msupb +23.6, $mesq +33.7, $msupb +36.3);
		$this->Line($mesq +62.7, $msupb +23.6, $mesq +62.7, $msupb +36.3);
		$this->Line($mesq +102.7, $msupb +23.6, $mesq +102.7, $msupb +36.3);
		$this->Line($mesq +48.7, $msupb +30.2, $mesq +48.7, $msupb +36.3);
		$this->Line($mesq +83, $msupb +23.6, $mesq +83, $msupb +30.2);
		$this->SetDrawColor(255, 255, 255);
		$this->Line($mesq +102, $msupb +33, $mesq +103, $msupb +33);
		$this->Line($mesq +102, $msupb +33.2, $mesq +103, $msupb +33.2);
		$this->SetDrawColor(0, 0, 0);

		// Texto fixo
		if ($this->banco == gB_ITAU) {
			$this->Image($gPathImg."itau.jpg", $mesq, $msupb +2.2, 5.5, 5.5, "jpg");
			$this->SetFont('helvetica', 'B', 8);
			$this->Text($mesq +10, $msupb +7, "Banco Itaú S.A.");
			$this->SetFont('helvetica', 'B', 17);
			$this->Text($mesq +39, $msupb +7, "341-7");
		}
		$this->SetFont('helvetica', '', 6);
		$this->Text($mesq, $msupb +10.7, "Local de Pagamento");
		$this->Text($mesq +137, $msupb +10.7, "Vencimento");
		$this->Text($mesq, $msupb +19.8, "Cedente");
		$this->Text($mesq +137, $msupb +19.8, "Agência/Código Cedente");
		$this->Text($mesq, $msupb +26, "Data do Documento");
		$this->Text($mesq +34.5, $msupb +26, "Num. do Documento");
		$this->Text($mesq +63.5, $msupb +26, "Espécie Doc.");
		$this->Text($mesq +83.8, $msupb +26, "Aceite");
		$this->Text($mesq +103.5, $msupb +26, "Data do Processamento");
		$this->Text($mesq +137, $msupb +26, "Nosso Número");
		$this->Text($mesq, $msupb +32.7, "Uso do Banco");
		$this->Text($mesq +34.5, $msupb +32.7, "Carteira");
		$this->Text($mesq +49.5, $msupb +32.7, "Espécie");
		$this->Text($mesq +63.5, $msupb +32.7, "Quantidade");
		$this->Text($mesq +102.2, $msupb +33.9, "x");
		$this->Text($mesq +103.5, $msupb +32.7, "Valor");
		$this->Text($mesq +137, $msupb +32.7, "(=) Valor do Documento");
		$this->Text($mesq, $msupb +38.9, "Instruções (Todas informações deste bloqueto são de exclusiva responsabilidade do cedente.)");
		$this->Text($mesq +137, $msupb +38.9, "(-) Desconto/Abatimento");
		$this->Text($mesq +137, $msupb +50.1, "(+) Mora/Multa");
		$this->Text($mesq +137, $msupb +62.1, "(=) Valor Cobrado");
		$this->Text($mesq, $msupb +68.8, "Sacado");
		$this->Text($mesq, $msupb +80.2, "Sacador/Avalista");
		$this->Text($mesq +149.5, $msupb +80.2, "Código de Baixa");

		if ($this->boleto_atual == 1) {
			$this->Text($mesq +137, $msupb +83.3, "Autenticação mecânica");
			$this->Text($mesq, $msupb +83.3, "Recebimento através do cheque num.                             do banco");
			$this->Text($mesq, $msupb +85.3, "Esta quitação só terá validade após o pagamento do cheque pelo");
			$this->Text($mesq, $msupb +87.3, "banco sacado.");
			$this->SetFont("Courier", 'B', 10);
			$this->Text($mesq +150, $msupb +6.5, "Recibo do Sacado");
		}
		elseif ($this->boleto_atual == 2) {
			$this->Text($mesq +137, $msupb +83.3, "Autenticação mecânica");
			$this->SetFont("Courier", 'B', 10);
			$this->Text($mesq +151, $msupb +6.5, "Ficha de Caixa");
		} else {
			//$this->Text($mesq +128, $msupb +83.3, "Autenticação mecânica - Ficha de Compensação");
			$this->Text($mesq +128, $msupb +73.3, "Autenticação mecânica - Ficha de Compensação");
		}
	}

		function gBoletoDados($incluir = 1) //Caso o valor de $incluir seja 1 insere nas tabelas lancamentos e parcelas
	{
			// Gera saída dos dados do boleto nos seus respectivos lugares
	global $gPathImg;
		$dados = & $this->dados;
		$mesq = $this->margem_esquerda;
		$ba = $this->boleto_atual;
		$ba = $ba -1;
		if ($ba == 0)
			$msupb = $this->margem_superior;
		elseif ($ba == 1) $msupb = $this->margem_superior + 90;
		else
			$msupb = $this->margem_superior + 184;
		$nfmt = gVar("global.numformat");
		gVar("global.numformat", "0.000,00");

		// Dados
		$this->SetFont('helvetica', '', 8);
		$this->Text($mesq +20, $msupb +12.7, "ATÉ O VENCIMENTO, PREFERENCIALMENTE NO ITAÚ OU BANERJ");
		$this->Text($mesq +20, $msupb +16.7, "APÓS O VENCIMENTO, SOMENTE NO ITAÚ OU BANERJ");
		$this->SetFont('helvetica', 'B', 8);
		$this->Text($mesq +168, $msupb +16.7, $dados['vencimento']);
		$this->SetFont('helvetica', '', 8);
		$this->Text($mesq +165, $msupb +22.8, strtoupper($dados['agencia']."/".$dados['conta']));
		$this->Text($mesq, $msupb +22.8, strtoupper($dados['cedente']));
		//$this->Text($mesq,$msupb+26,"Data do Documento");$this->Text($mesq+34.5,$msupb+26,"Num. do Documento");$this->Text($mesq+63.5,$msupb+26,"Espécie Doc.");$this->Text($mesq+83.8,$msupb+26,"Aceite");$this->Text($mesq+103.5,$msupb+26,"Data do Processamento");$this->Text($mesq+137,$msupb+26,"Nosso Número");

		//inicio do lancamento financeiro
		///////////////////////////
		//* Filtra o numero ok do cliente

		$ssql = "SELECT id  FROM `ok_voip_numeros_ok`";
		if ($this->dados['tp_cliente'] == "id_geral_pessoas") {
			$filtro = " where id_geral_pessoas =".$this->dados['id_cliente']." order by id LIMIT 1";
			$id_lanc_pessoa = $this->dados['id_cliente'];
			$id_lanc_empresa = 0;

		} else {
			$filtro = " where id_geral_empresas =".$this->dados['id_cliente']." order by id LIMIT 1";
			$id_lanc_pessoa = 0;
			$id_lanc_empresa = $this->dados['id_cliente'];
		}
		$ssql = $ssql.$filtro;
		//	echo $ssql;
		$rs_numero_ok = gQuery($ssql);

		$id_numero_ok = $rs_numero_ok->fields['id'];

		// Insere na tabela de lancamentos

		if ($this->dados['tipo_boleto'] == 0) //e uma conta 
			{
			$id_fin_tipos = 109;
		} else {
			$id_fin_tipos = 110;
		}

		// 1- inicio colocar condicao de ser lancamento extra se for lancamento extra nao entra

		$ssql = " select max(id) as id from fin_lancamentos where id_cliente =".$this->dados['id_cliente']." and tipo_cliente ='".$this->dados['tp_cliente']."' and id_fin_tipos =".$id_fin_tipos." and month(fin_lancamentos.data_lancamento) ='".$this->dados['mes']."' and year(fin_lancamentos.data_lancamento) ='".$this->dados['ano']."'";
		//$this->DrawTextLine(array($ssql,2));
		//echo $ssql;
		$rs_lanc_existe = gQuery($ssql);

		if (is_null($rs_lanc_existe->fields['id'])) {
			$existe = 0;
		} else {
			$existe = 1;
			$id_lanc = $rs_lanc_existe->fields['id'];
		}
		//se for via site forca a criacao do financeiro
		if ($this->dados['tipo_boleto'] == 1) //e uma via site
		{
			$existe = 0;
		}
        
		//1- fim

		//$numero_documento_local = $dados['valor']; comentado por carlos
        
		if ($incluir == 1) {
			//echo "teste2";
			$ValorDoc_banco = $dados['valor'];
            //echo "<hr>valor doc=$ValorDoc_banco incluir=$incluir existe=$existe<hr>";
			if ($ValorDoc_banco <> 0) {
				if ($existe == 0) {
					//2-inicio
					// se ($this->dados['tipo_boleto'] == 1)  colocar o dia para ser o dia  corrente ao inves de 01   
					// se lancamento extra colocar o dia para ser o dia  corrente ao inves de 01   
					if ($this->dados['tipo_boleto'] == 0) //eh uma conta
						{
						$data_lanc = $this->dados['ano'].'-'.$this->dados['mes'].'-'.'01';
					} else {
						$data_lanc = date("Y-m-d");
					}

					// 2-fim

					//echo$data_lanc;
					$ssql = "insert into fin_lancamentos  (id_geral_empresas_dono,id_formas_pagamento,id_fin_tipos,data_lancamento,id_ok_voip_numeros_ok,valor,debcre,id_cliente,tipo_cliente,id_geral_pessoas,id_geral_empresas) values (1,1,$id_fin_tipos,'".$data_lanc."',$id_numero_ok,".$ValorDoc_banco.",'CR',".$this->dados['id_cliente'].",'".$this->dados['tp_cliente']."',$id_lanc_pessoa,$id_lanc_empresa)";
					//echo $ssql;

					$rs_lanc_insert = gQuery($ssql);

					//seleciona o registro insererido

					$ssql = " select max(id) as id from fin_lancamentos where id_cliente =".$this->dados['id_cliente']." and tipo_cliente ='".$this->dados['tp_cliente']."' and id_fin_tipos =".$id_fin_tipos;
					$rs_lanc = gQuery($ssql);
					$id_lanc = $rs_lanc->fields['id'];

					// Insere na tabela de parcelas
					//date("Y-m-d")
					$ssql = "insert into fin_parcelas (id_fin_lancamentos,data_lancamento,data_vencimento, id_cliente,num_documento,valor,tipo_cliente,multa,juros,id_fin_contas,id_geral_pessoas) values
					($id_lanc,'".$data_lanc."','".gDBdate($this->dados['vencimento'])."',".$this->dados['id_cliente'].",'".$dados['numero_documento']."',$ValorDoc_banco,'".$this->dados['tp_cliente']."',0,0,1,1)";
					//echo $ssql;
					$rs_parcelas_insert = gQuery($ssql);
					//echo "nao existe";
				} else {
                   // echo "Existe";
					if($this->dados['data_automatica']<>"") 
						$sql_datam=",data_vencimento='".gDBDate($this->dados['data_automatica'])."' ";
					else 
						$sql_datam=",data_vencimento='".gDBDate($this->dados['vencimento'])."' ";
				
					$sql_update = "update fin_lancamentos set valor ='".$ValorDoc_banco."' where id =$id_lanc";
					//echo $sql_update;
					$rs_lanc_update = gQuery($sql_update);

					$sql_update = "update fin_parcelas set valor ='".$ValorDoc_banco."' $sql_datam where id_fin_lancamentos =$id_lanc";
					//echo $sql_update;
					//$this->DrawTextLine(array($sql_update." - ".$data_automatica,2));
					$rs_parcelas_update = gQuery($sql_update);
				}
				//$id_lanc =10;

				// Se for do tipo conta deve criar o numero do documento caso contrario ja esta criado pois pelo site
				//3- colocar a condicao or pra a variavel lancamento extra 

				if ($this->dados['tipo_boleto'] == 0) //eh uma conta
					{
					//seleciona o ultimo registro inserido

					$ssql_parcelas = "Select max(id) as id from fin_parcelas where id_cliente =".$this->dados['id_cliente']." and tipo_cliente ='".$this->dados['tp_cliente']."' and id_fin_lancamentos =".$id_lanc;
					//echo $ssql_parcelas;
					$rs_parcelas = gQuery($ssql_parcelas);
					$id_parcelas = $rs_parcelas->fields['id'];

					//atualiza o campo numero do documento
					// $id_parcelas =1;
					$carteira_local = $dados['carteira'];

					$numero_documento_local = $this->_gera_nosso_numero($carteira_local, $id_parcelas);
					$sql_update = "update fin_parcelas set num_documento ='".$numero_documento_local."' where id =$id_parcelas";
					//echo $sql_update;
					$rs_parcelas_update = gQuery($sql_update);
				}

				///////////////////////////
				//Fim do lancamento financeiro
			}
		}

		if ($this->dados['tipo_boleto'] == 0) //e uma conta
			{
			$this->Text($mesq +10, $msupb +29.5, $dados['emissao']);
			$this->Text($mesq +34.5, $msupb +29.5, $numero_documento_local);
			$this->Text($mesq +70.2, $msupb +29.5, "DM");
			$this->Text($mesq +92, $msupb +29.5, "N");
			$this->Text($mesq +165, $msupb +29.5, $NossoNum);
		} else {
			$numero_documento_local = $dados['numero_documento'];
			$this->Text($mesq +10, $msupb +29.5, $dados['emissao']);
			$this->Text($mesq +34.5, $msupb +29.5, $numero_documento_local);
			$this->Text($mesq +70.2, $msupb +29.5, "DM");
			$this->Text($mesq +92, $msupb +29.5, "N");
			$this->Text($mesq +165, $msupb +29.5, $NossoNum);
		}

		//nosso numero e igual ao numero do boleto
		//echo $numero_documento_local;
		$numero_documento_sem_traco = str_replace('-', '', $numero_documento_local);
		$numero_documento_sem_traco = substr("00000000".$numero_documento_sem_traco, -8);
		

		//echo $numero_documento_sem_traco;

		$nosso_numero_texto = $dados['carteira']."/".substr("00000000".$numero_documento_sem_traco, -8)."-".$this->_modulo10($this->dados['carteira'].$numero_documento_sem_traco);
		
	//	$nosso_numero_texto = getCodigoDigitavel();
		//$this->dados['nosso_numero_texto']=$this->dados['carteira']."/".substr("00000000".$numero_documento_sem_traco,-8)."-".$this->_modulo10($this->dados['carteira'].$this->dados['nosso_numero']);

		$this->Text($mesq +39, $msupb +35.5, strtoupper($dados['carteira']));
		$this->Text($mesq +54, $msupb +35.5, "R$");
		$this->SetFont('helvetica', 'B', 8);
		$this->Text($mesq +165, $msupb +35.5, gFloat($dados['valor']));
		$this->SetFont('helvetica', '', 8);
		$this->Text($mesq +5, $msupb +45, strtoupper($dados['instrucoes1']));
		$this->Text($mesq +5, $msupb +49, strtoupper($dados['instrucoes2']));
		$this->Text($mesq +5, $msupb +53, strtoupper($dados['instrucoes3']));
		$this->Text($mesq +17, $msupb +70, strtoupper($dados['sacado1'])."  ".$dados['sacado_cnpj']);
		$this->Text($mesq +17, $msupb +73, strtoupper($dados['sacado2']));
		$this->Text($mesq +17, $msupb +76, strtoupper($dados['sacado3']));
		$this->Text($mesq +17, $msupb +80, strtoupper($dados['sacador'])."  ".$dados['sacador_cnpj']);
		//antes da  mudanca do nosso numero
		/*$this->Text($mesq+165,$msupb+29.5,strtoupper($dados['nosso_numero_texto']));
		$this->Text($mesq+167,$msupb+80,strtoupper($dados['nosso_numero_texto']));*/
		//Depois da mudanca do nosso numero
		$this->Text($mesq +165, $msupb +29.5, strtoupper($nosso_numero_texto));
		$this->Text($mesq +167, $msupb +80, strtoupper($nosso_numero_texto));

		$valor = str_replace('.', '', $dados['valor']);
		//Campo 1
		$sA = "341";
		$sB = "9";

		$sC = $dados['carteira'];

		//Antes da mudanca do nosso numero
		//$sD = substr($dados['nosso_numero'],0,2);	

		//depois da mudanca do nosso numero
		$sD = substr($numero_documento_sem_traco, 0, 2);

		//echo $sA.$sB.$sC.$sD."<br>";
		$sX = $this->_modulo10($sA.$sB.$sC.$sD);

		$numbarra_ini = $sA.$sB;
		$numdigitavel_cal = $sA.$sB.$sC.$sD.$sX;

		$campo1 = $sA.$sB.$dados['carteira'][0].".".substr($dados['carteira'], 1).$sD.$sX." ";

		//echo $campo1;

		$FatorVenc = $this->_fatorVencimento($this->dados['vencimento']);

		//Achando o valor do documento e configurando com zero 

		$ValorDoc = gFloat($dados['valor']);
		$ValorDoc = str_replace('.', '', $ValorDoc);
		$ValorDoc = str_replace(',', '', $ValorDoc);

		$ValorDoc = str_pad($ValorDoc, 10, "0", STR_PAD_LEFT);
		//campo 5

		$campo5 = $FatorVenc.$ValorDoc;

		//echo $campo5;

		//$campo1="3419".substr($dados['carteira'],0,2).;
		//$dig1=$this->_modulo10($campo1);

		//campo 2
		// echo $dados['nosso_numero']."//".substr($dados['nosso_numero'],2);

		// Antes da mudanca do nosso numero
		//$sDD = substr($dados['nosso_numero'],2);	//restante do nosso numero

		// Depois da mudanca do nosso numero
		$sDD = substr($numero_documento_sem_traco, 2); //restante do nosso numero

		//campo E		
		$pos = strrpos($dados['conta'], "-");
		$cc_sem_traco = substr($dados['conta'], 0, $pos);
		//echo $dados['conta']."---------". $cc_sem_traco;

		//antes da mundaca nosso numero
		//$nn_sem_traco = $dados['nosso_numero'];

		//depois da mundaca do nosso numero
		$nn_sem_traco = $numero_documento_sem_traco;

		//echo $dados['nosso_numero']."---------". $nn_sem_traco;

		$ag_cc_carteira_nosso_num = $dados['agencia'].$cc_sem_traco.$dados['carteira'].$nn_sem_traco;

		$sE = $this->_modulo10($ag_cc_carteira_nosso_num); //DAC do Campo E

		$sF = substr($dados['agencia'], 0, 3);

		//echo $sDD.$sE.$sF;

		$sY = $this->_modulo10($sDD.$sE.$sF);

		$campo2 = substr($sDD, 0, 5).".".substr($sDD, 5).$sE.$sF.$sY;

		//echo $campo2."<br>";

		$sag = $dados['agencia'];
		$scc = substr($dados['conta'], 0, 5);
		//antes da mundanca do nosso numero
		//$numbarra_fim = $FatorVenc.$ValorDoc.$dados['carteira'].$dados['nosso_numero'].$sE.$sag.$scc;
		//depois da mundanca do nosso numero
		$numbarra_fim = $FatorVenc.$ValorDoc.$dados['carteira'].$numero_documento_sem_traco.$sE.$sag.$scc;

		//echo $sag.$scc;
		$dac_ag_cc = $this->_modulo10($sag.$scc);

		$numbarra_fim = $numbarra_fim.$dac_ag_cc.'000';

		//echo$numbarra_ini.$numbarra_fim."<br>";

		$dac_barra = $this->_modulo11($numbarra_ini.$numbarra_fim);

		//echo $dac_barra."<br>";

		$numbarra = $numbarra_ini.$dac_barra.$numbarra_fim;
		//$numbarra = ;
		//campo 3

		$sF3 = substr($dados['agencia'], 3);

		$sG3 = str_replace("-", "", $dados['conta']);

		//echo 	$sG3;

		$sH3 = '000';
		//echo "Agencia".$dados['conta']."f3".$sF3."G3".$sG3."h3".$sH3;

		//echo 		$sF3.$sG3.$sH3;

		//$sZ = $this->_modulo10($sF3.$sG3.$sH3);//campo Z

		//echo $sZ."<br>";

		$sZ = $this->_modulo10($sF3.$sG3.$sH3); //campo Z

		//echo $sZ;
		$campo3 = $sF3.substr($sG3, 0, 4).".".substr($sG3, 4).$sH3.$sZ;

		//echo $campo3;

		$campo4 = $dac_barra; //campo k	

		$numdigitavel = $campo1." ".$campo2." ".$campo3." ".$campo4." ".$campo5;
		
		
		//echo $numbarra;

		/*$campo2 = substr($dados['nosso_numero'],2,7).$campo2_E.substr($dados['agencia'],0,2);
		$dig2 = $this->_modulo10($campo2);
		
		$campo2 = $campo2.$dig2;//Campo  2
		
		//$campo3=substr($dados['cod_cliente'],3,5).substr($dados['cod_cliente'],8,2)."000";
		
		$sF3 =substr($dados['agencia'],3);
		$sG =str_replace($dados['conta'],"-",""); 
		$campo3=$sF.$sG."000";
		$dig3=$this->_modulo10($campo3);//campo Z
		
		$campo3=$campo3.$dig3;	
		
		$numdigitavel="3419".$dados['carteira'][0].".".substr($dados['carteira'],1).substr($dados['nosso_numero'],0,2).$dig1." ";
		$numdigitavel.=substr($campo2,0,5).".".substr($campo2,5,5).$dig2." ";
		
		//achando o fator de vencimento		 
		
		$nn_completo =str_replace($dados['nosso_numero'],"-","");
		$cc_completo =str_replace($dados['conta'],"-","");
		
		$numbarra_ini="3419".
		$numbarra_fim=$FatorVenc.$ValorDoc.$dados['carteira'].$nn_completo.$dados['agencia'].$cc_completo."000";
		
		$dig4 =  $this->_modulo11($numbarra_ini.$numbarra_fim); //campo DAC do codigo de barra		
		
		$numbarra =$numbarra_ini.$dig4.$numbarra_fim;
		
		//$Campo4 =  $this->_modulo11($numbarra); //campo 4 (K)
			 
		$numdigitavel.=substr($campo3,0,5).".".substr($campo3,5,5).$dig3." ".$Campo4." ".$Campo5;
		
		//$numb="3419".$this->_fatorVencimento($ano, $mes, $dia);
		
		*/
		//echo $ValorDoc_banco;

		//echo $numbarra;

		$this->SetFont('helvetica', '', 12);
		//$this->Text($mesq+66,$msupb,"Cod cliente: ".$dados['cod_cliente']);
		if ($this->boleto_atual == 3) {
			//$this->Text($mesq+66,$msupb,$numbarra);
			//echo strlen($numbarra);
			
			
			$valor = str_replace(",",".",$this->dados['valor']);
			
			//Converte para o fromato 9999.99
			$valor = number_format($valor, 2, '.', '');
			
			$doc = str_replace("-","",$numero_documento_local);
			$doc = $this->dados['numero_documento'];
			$doc = str_replace("-","",$numero_documento_local);
			//$doc = str_replace("-","",$doc);
			$num_doc = substr($doc,-8);
			
			
			$conta = substr($this->dados['conta'],0,5);
			
			$codigobarras_sem_dac = getNumberBarraSemDAC($this->dados['codigo_banco'], "9", $this->dados['vencimento'], $valor, $this->dados['carteira'], $num_doc, $this->dados['agencia'], $conta);
			
			$numdigitavel = getCodigoDigitavel($this->dados['codigo_banco'], "9", $this->dados['vencimento'], $valor, $this->dados['carteira'], $num_doc, $this->dados['agencia'], $conta, $codigobarras_sem_dac);
			
			$this->Text($mesq +66, $msupb +8, $numdigitavel);
			
			$number = getNumberBarra($this->dados['codigo_banco'], "9", $this->dados['vencimento'], $valor, $this->dados['carteira'], $num_doc, $this->dados['agencia'], $conta);
			//echo $this->dados['agencia']."<br>";
			//echo $conta."<br>";
			//echo $this->dados['vencimento']."<br>";
			//echo $num_doc."<br>";
			//echo $number."<br>";
			//echo $valor;
		//	$this->gCodigoBarra($this, $numbarra, $mesq, $msupb +82.5);
			//$number = "";
			$this->gCodigoBarra($this, $number, $mesq, $msupb +82.5);
			//$img = getCodigoBarras($valor);
			//$this->Image($img, $mesq, $msupb +2.2, 5.5, 5.5, "png");
			/*
			$img = $this->getCodigoBarras($numbarra);
			$this->Text($img);
			*/
		}

		gVar("global.numformat", $nfmt);
	}

	function _gera_nosso_numero($carteira, $nosso_numero) {
		$aux = substr("000000".$nosso_numero, -8)."-".$this->_modulo10($carteira.$nosso_numero);
		return $aux;
	}

	function gCodigoBarra(&$this, $valor, $mesq, $msupb) {

		// Desenha linhas do código de barras
		/*
        $fino = 0.25;
		$largo = 0.75;
		$altura = 50;
*/
		$fino = 0.25;
		$largo = 0.75;
		$altura = 12.5;

		$barcodes[0] = "00110";
		$barcodes[1] = "10001";
		$barcodes[2] = "01001";
		$barcodes[3] = "11000";
		$barcodes[4] = "00101";
		$barcodes[5] = "10100";
		$barcodes[6] = "01100";
		$barcodes[7] = "00011";
		$barcodes[8] = "10010";
		$barcodes[9] = "01010";

		for ($f1 = 9; $f1 >= 0; $f1 --) {
			for ($f2 = 9; $f2 >= 0; $f2 --) {
				//echo "F2".$f2."-".$f1."<br>";
				$f = ($f1 * 10) + $f2;
				$texto = "";
				for ($i = 1; $i < 6; $i ++) {
					//echo "texto>>>>".substr($barcodes[$f1],($i-1),1)."<---->".substr($barcodes[$f2],($i-1),1)."<BR>" ;
					$texto .= substr($barcodes[$f1], ($i -1), 1).substr($barcodes[$f2], ($i -1), 1);
					//echo "txt conc".$texto."<br>";
				}
				//echo "valor  de f".$f."<br>";
				$barcodes[$f] = $texto;

			}
		}

		//echo "texto".$texto."----";

	//	$alt = 10;
		$alt = 10;
		$tl = $this->LineWidth;
		$tip = "F";
		//$this->LineWidth=round(.567/$this->k/2,3);
		
		
		$this->SetFillColor(0, 0, 0);
		$this->SetDrawColor(255, 255, 255);
		
		
		$esq = 0;
		
		# Abertura do código de barras.
        $this->Rect($mesq + $esq, $msupb, $fino, $alt, $tip);
        $esq += $fino;
        $esq += $fino;
        $this->Rect($mesq + $esq, $msupb, $fino, $alt, $tip);
        $esq += $fino;
        $esq += $fino;
    
		$texto = $valor;

		//echo (strlen($texto) % 2);

		if ((strlen($texto) % 2) <> 0) {
			$texto = "0".$texto;
		}

		// Draw dos dados
		//echo strlen($texto);
		while (strlen($texto) > 0) {
        
			$i = round(esquerda($texto,2));
  			$texto = direita($texto,strlen($texto)-2);
			
			$f = $barcodes[$i];
			for ($i = 1; $i < 11; $i += 2) {
				if (substr($f, ($i -1), 1) == "0") {
					$f1 = $fino;
				} else {
					$f1 = $largo;
				}

				# Imprime preto
                $this->Rect($mesq + $esq, $msupb, $f1, $alt, $tip);
				$esq += $f1;

				if (substr($f, $i, 1) == "0") {
					$f2 = $fino;
				} else {
					$f2 = $largo;
				}
                
                #imprime branco
				$esq += $f2;
				
			}
		}
		
		# Fechamento do código de barras
        $this->Rect($mesq + $esq, $msupb, $largo, $alt, $tip);
        $esq += $largo;
        $esq += $fino;
        $this->Rect($mesq + $esq, $msupb, $fino, $alt, $tip);
        $esq += $fino;
		
		
		# gera seta antes do pontilhado
		$alt += 1.5;
		$this->SetDrawColor(150, 150, 150);
		$this->Line($mesq + $esq +2, $msupb + $alt, $mesq + $esq +3, $msupb + $alt +0.5);
		$this->Line($mesq + $esq +2, $msupb + $alt, $mesq + $esq +3, $msupb + $alt -0.5);
		$this->Line($mesq + $esq +3, $msupb + $alt +0.5, $mesq + $esq +3, $msupb + $alt -0.5);
		
		# gera linha pontilhada
		for ($a = 1; $a < 45; $a ++) {
			$this->Line($mesq + $esq +4 + $a * 2, $msupb + $alt, $mesq + $esq +5 + $a * 2, $msupb + $alt);
		}

		$this->SetFont('helvetica', 'I', 6);
		$this->SetTextColor(100, 100, 100);
		$this->Text($mesq + $esq +5, $msupb + $alt +2, "corte aqui");
		$this->SetDrawColor(0, 0, 0);
		$this->SetTextColor(0, 0, 0);
	}
	
	//function _fatorVencimento($ano, $mes, $dia)
	function _fatorVencimento($data) {
		$DataVenc = explode("/", $data);
		$DiaVenc = $DataVenc[0];
		$MesVenc = $DataVenc[1];
		$AnoVenc = $DataVenc[2];

		$DataInic = explode("/", "07/10/1997");
		$DiaInic = $DataInic[0];
		$MesInic = $DataInic[1];
		$AnoInic = $DataInic[2];

		$DataInic = mktime(0, 0, 0, $MesInic, $DiaInic, $AnoInic);
		$DataVenc = mktime(0, 0, 0, $MesVenc, $DiaVenc, $AnoVenc);

		$FatorVenc = $DataVenc - $DataInic;

		$Segundos = 24 * 60 * 60; // 24 horas * 60 minutos * 60 segundos

		$FatorVenc = ceil($FatorVenc / $Segundos);

		return $FatorVenc -1;

		//return(abs(($this->_dateToDays($ano, $mes, $dia))-($this->_dateToDays("1997","10","07")) ));
		//return(abs(($this->_dateToDays("1997","10","07")) - ($this->_dateToDays($ano, $mes, $dia))));
	}

	function _dateToDays($year, $month, $day) {
		$century = substr($year, 0, 2);
		$year = substr($year, 2, 2);
		if ($month > 2) {
			$month -= 3;
		} else {
			$month += 9;
			if ($year) {
				$year --;
			} else {
				$year = 99;
				$century --;
			}
		}

		return (floor((146097 * $century) / 4) + floor((1461 * $year) / 4) + floor((153 * $month +2) / 5) + $day +1721119);
	}

	function _modulo11($num, $base = 9, $r = 0) {
		/*
			Autor:
						Pablo Costa <pablo@users.sourceforge.net>
		
			Entrada:
				$num: string numérica para a qual se deseja calcular o digito verificador;
				$base: valor maximo de multiplicacao [2-$base]
				$r: quando especificado um devolve somente o resto
		
			Saída:
				Retorna o Digito verificador.
		*/

		$soma = 0;
		$fator = 2;

		/* Separacao dos numeros */
		for ($i = strlen($num); $i > 0; $i --) {
			// pega cada numero isoladamente
			$numeros[$i] = substr($num, $i -1, 1);
			// Efetua multiplicacao do numero pelo falor
			$parcial[$i] = $numeros[$i] * $fator;
			// Soma dos digitos
			$soma += $parcial[$i];
			if ($fator == $base) {
				// restaura fator de multiplicacao para 2
				$fator = 1;
			}
			$fator ++;
		}

		/* Calculo do modulo 11 */
		if ($r == 0) {
			$soma *= 10;
			$digito = $soma % 11;
			if ($digito == 10) {
				$digito = 0;
			}
			return $digito;
		}
		elseif ($r == 1) {
			$resto = $soma % 11;
			return $resto;
		}
	}

	function _modulo10($num) {
		/*
			Autor:
						Pablo Costa <pablo@users.sourceforge.net>
			Entrada:
						$num: string numérica para a qual se deseja calcular o digito verificador;
			Saída:
						Retorna o Digito verificador.
		*/

		$numtotal10 = 0;
		$fator = 2;

		// Separacao dos numeros
		for ($i = strlen($num); $i > 0; $i --) {
			// pega cada numero isoladamente
			$numeros[$i] = substr($num, $i -1, 1);
			// Efetua multiplicacao do numero pelo (falor 10)
			$parcial10[$i] = $numeros[$i] * $fator;
			// monta sequencia para soma dos digitos no (modulo 10)
			$numtotal10 .= $parcial10[$i];
			if ($fator == 2) {
				$fator = 1;
			} else {
				$fator = 2; // intercala fator de multiplicacao (modulo 10)
			}
		}

		$soma = 0;
		// Calculo do modulo 10
		for ($i = strlen($numtotal10); $i > 0; $i --) {
			$numeros[$i] = substr($numtotal10, $i -1, 1);
			$soma += $numeros[$i];
		}

		$resto = $soma % 10;
		$digito = 10 - $resto;
		if ($resto == 0) {
			$digito = 0;
		}

		return $digito;
	}

	function _montaLinha($codigo) {
		// 2002-07-06 19:41:28 alterado para Itaú
		// Posição  Conteúdo
		// Campo 1
		// 1 a 3  Número do Banco
		// 4    Código da Moeda - 9 para Real
		// 5 a 7  Código da carteira (175)
		// 8 a 9  Dois primeiros dígitos do nosso número
		// 10   DAC campo 1 (Mod10)
		//
		// Campo 2
		// 1 a 6  Restante do Nosso Número
		// 7    DAC de Agência/Conta/Carteira/NossoNúmero
		// 8 a 10 Três primeiros dígitos da Agência
		// 11     DAC campo 2 (Mod10)
		//
		// Campo 3
		// 1    Restante do número da Ag
		// 2 a 7  Conta corrente + DAC
		// 8 a 10 Zeros (Não utilizado)
		// 11   DAC campo 3 (Mod10)
		//
		// Campo 4
		// 1    DAC do Código de Barras
		//
		// Campo 5
		// 1 a 4  Fator de Vencimento
		// 5 a 14 Valor do Título

		$banco = substr($codigo, 0, 3);
		$moeda = substr($codigo, 3, 1);
		$k = substr($codigo, 4, 1);
		$fator = substr($codigo, 5, 4);
		$valor = substr($codigo, 9, 10);
		$carteira = substr($codigo, 19, 3);
		$nn = substr($codigo, 22, 9);
		$agencia = substr($codigo, 31, 4);
		$conta = substr($codigo, 35, 6);
		$zeros = substr($codigo, 41, 3);

		// 1. Campo - composto pelo código do banco, código da moeda, carteira, dois primeiros dígitos
		// do noosso número e DAC (modulo10) deste campo
		$p1 = "$banco$moeda".$carteira[0].substr($carteira, -2).substr($nn, 0, 2);
		$dv_1 = $this->_modulo10($p1);
		$campo1 = substr($p1, 0, 5).'.'.substr($p1, -4).$dv_1;

		// 2. Campo - restante do Nosso Numero, DAC NN, três primeiros digitos da agenca + DAC
		$p1 = substr($nn, -7).substr($agencia, 0, 3);
		$dv_2 = $this->_modulo10($p1);
		$campo2 = substr($p1, 0, 5).'.'.substr($p1, -5).$dv_2;

		// 3. Campo composto por: último digito da agencia, conta, zeros + DAC
		$p1 = substr($agencia, -1).$conta.$zeros;
		$dv_3 = $this->_modulo10($p1);
		$campo3 = substr($p1, 0, 5).'.'.substr($p1, -5).$dv_3;

		// 4. Campo - digito verificador do codigo de barras
		$campo4 = $k;

		// 5. Campo composto pelo valor nominal pelo valor nominal do documento, sem
		// indicacao de zeros a esquerda e sem edicao (sem ponto e virgula). Quando se
		// tratar de valor zerado, a representacao deve ser 000 (tres zeros).
		$campo5 = $fator.$valor;

		return "$campo1 $campo2 $campo3 $campo4 $campo5";
	}

	function _geraCodigoBanco($numero) {
		$parte1 = substr($numero, 0, 3);
		$parte2 = $this->_modulo11($parte1);
		return $parte1."-".$parte2;
	}


	//montacodigodebarras('23794225800000041933394060092001280100001020');

	//$valorpassado = $_SERVER['QUERY_STRING'] ;
	//montacodigodebarras('23791227400000041933394060092001280200001020');

	//montacodigodebarras($_SERVER['QUERY_STRING']) ;

	/** -------------- Fim ----------------------*/
	
	
}


/** trecho inserido em 08/11/2005 */
function esquerda($entra, $comp) {
	return substr($entra, 0, $comp);
}

function direita($entra, $comp) {
	return substr($entra, strlen($entra) - $comp, $comp);
}

 function Modulo11($valor) {
            $multiplicador = '4329876543298765432987654329876543298765432';
            for ($i = 0; $i<=42; $i++ ) {
                 $parcial = $valor[$i] * $multiplicador[$i];
                         $total += $parcial;
            }
            $resultado = 11-($total%11);
            if (($resultado >= 10)||($resultado == 0)) {
                 $resultado = 1;
            }

            return $resultado;
    }


    function calculaDAC ($CalculaDAC) {
            $tamanho = strlen($CalculaDAC);
            for ($i = $tamanho-1; $i>=0; $i--) {
                if ($multiplicador !== 2) {
                    $multiplicador = 2;
                }
                else {
                    $multiplicador = 1;
                }
                $parcial = strval($CalculaDAC[$i] * $multiplicador);

                if ($parcial >= 10) {
                    $parcial = $parcial[0] + $parcial[1];
                }
                $total += $parcial;
            }
            $total = 10-($total%10);
            if ($total >= 10) {
                    $total = 0;
            }
            return $total;
    }

    function calculaValor ($valor) {
            $valor = str_replace('.','',$valor);
            return str_repeat('0',(10-strlen($valor))).$valor;
    }

    function calculaNossoNumero ($valor) {
            return str_repeat('0',(8-strlen($valor))).$valor;
    }

    function calculaFatorVencimento ($dia,$mes,$ano) {
             $vencimento = mktime(0,0,0,$mes,$dia,$ano)-mktime(0,0,0,7,3,2000);
             return ceil(($vencimento/86400)+1000);
    }
	
	function getNumberBarra($codigobanco, $moeda, $vencimento, $valor, $carteira, $nossonumero, $agencia, $conta){
		// CALCULO DO CODIGO DE BARRAS (SEM O DAC VERIFICADOR)
    	$codigo_barras = $codigobanco.$moeda.calculaFatorVencimento(substr($vencimento,0,2),substr($vencimento,3,2),substr($vencimento,6,4));
    	$codigo_barras .= calculaValor($valor).$carteira.calculaNossoNumero($nossonumero).calculaDAC($agencia.$conta.$carteira.calculaNossoNumero($nossonumero)).$agencia.$conta.calculaDAC($agencia.$conta).'000';
		$codigo_barras = substr($codigo_barras,0,4).Modulo11($codigo_barras).substr($codigo_barras,4,43);
		return $codigo_barras;
	}
	
	function getNumberBarraSemDAC($codigobanco, $moeda, $vencimento, $valor, $carteira, $nossonumero, $agencia, $conta){
		// CALCULO DO CODIGO DE BARRAS (SEM O DAC VERIFICADOR)
    	$codigo_barras = $codigobanco.$moeda.calculaFatorVencimento(substr($vencimento,0,2),substr($vencimento,3,2),substr($vencimento,6,4));
    	$codigo_barras .= calculaValor($valor).$carteira.calculaNossoNumero($nossonumero).calculaDAC($agencia.$conta.$carteira.calculaNossoNumero($nossonumero)).$agencia.$conta.calculaDAC($agencia.$conta).'000';
		//$codigo_barras = substr($codigo_barras,0,4).Modulo11($codigo_barras).substr($codigo_barras,4,43);
		return $codigo_barras;
	}
	
	function getCodigoDigitavel($codigobanco,$moeda, $vencimento, $valor, $carteira, $nossonumero, $agencia, $conta, $codigobarras){
		$parte1 = $codigobanco.$moeda.substr($carteira,0,1).substr($carteira,1,2).substr(calculaNossoNumero($nossonumero),0,2);
    	$parte1 = substr($parte1,0,5).'.'.substr($parte1,5,4).calculaDAC($parte1);

    	$parte2 = substr(calculaNossoNumero($nossonumero),2,5).substr(calculaNossoNumero($nossonumero),7,1).calculaDAC($agencia.$conta.$carteira.calculaNossoNumero($nossonumero)).substr($agencia,0,3);
    	$parte2 = substr($parte2,0,5).'.'.substr($parte2,5,5).calculaDAC($parte2);

   		$parte3 = substr($agencia,3,1).$conta.calculaDAC($agencia.$conta).'000';
    	$parte3 = substr($parte3,0,5).'.'.substr($parte3,5,8).calculaDAC($parte3);

    	$parte5 = calculaFatorVencimento(substr($vencimento,0,2),substr($vencimento,3,2),substr($vencimento,6,4)).calculaValor($valor);

    	$numero_boleto = $parte1.' '.$parte2.' '.$parte3.' '.Modulo11($codigobarras).' '.$parte5;
    	return $numero_boleto;			
	}
		/** ------------ Nova função ----------------*/

?>