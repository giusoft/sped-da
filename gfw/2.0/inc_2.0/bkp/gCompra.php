<?
//FUNÇÃO QUE FORNECE CRÉDITOS AO CLIENTE VINDO DE BOLETOS IMPRESSOS PELO SITE - PRÉ-PAGO
function gBaixaBoletoPrePago($nosso_numero,$data_ocorrencia,$data_efetivacao)
{
	$sql="select * from fin_numero_boletos where nosso_numero = '".$nosso_numero."' ";
	$rs=gQuery($sql);
	//echo $sql." - 1<br>";
		
	if(!$rs->EOF)
	{
		//CASO O NÚMERO DO DOCUMENTO JÁ TENHA SIDO ALTERADO
		$sql="select * from fin_parcelas where num_documento = '".$nosso_numero."' ";
		$rsV=gQuery($sql);
		
		if($rsV->EOF)
		{
			if($rs->fields['id_geral_pessoas']>0)
			{
				$id_cliente=$rs->fields['id_geral_pessoas'];;
				$tipo_cliente="id_geral_pessoas";
			}
			else
			{
				$id_cliente=$rs->fields['id_geral_empresas'];;
				$tipo_cliente="id_geral_empresas";
			}
			//ADICIONA CRÉDITOS
			if($rs->fields['pedido']=="Créditos")
			{
				addCreditos($rs->fields['id']);
				$observacao = $rs->fields['pedido']." de ".$rs->fields['valor']." no pré-pago";
			}
			//ADICIONA OKIN
			else
			{
				addOkin($rs->fields['id']);
				$observacao = "Compra OK IN no valor de ".$rs->fields['valor']." + R$15,00 de créditos grátis";
			}
			
			$sql="insert into fin_lancamentos(id_geral_pessoas,id_ok_voip_numeros_ok,id_geral_empresas,debcre,data_lancamento,id_fin_tipos,valor,id_cliente,tipo_cliente,id_fin_numero_boletos,id_formas_pagamento) values (".$rs->fields['id_geral_pessoas'].",".$rs->fields["id_pedido"].",".$rs->fields['id_geral_empresas'].",'CR','".gDBDate(date('d-m-y'))."',110,".$rs->fields['valor'].",$id_cliente,'$tipo_cliente',".$rs->fields['id'].",1)";
			gQuery($sql);
			//echo $sql."<br>";	
			$sql="select max(id) maxlanc from fin_lancamentos where id_fin_numero_boletos=".$rs->fields['id']." ";
			$rsMLanc=gQuery($sql);
			
			$sql="insert into fin_parcelas (id_fin_lancamentos,num_documento,data_lancamento,data_vencimento,data_pagamento,data_efetivacao,valor,id_cliente,tipo_cliente,observacao) values (".$rsMLanc->fields['maxlanc'].",'".$rs->fields['nosso_numero']."','".gDBDate(date('d-m-y'))."','".$rs->fields['data_vencimento']."','".gDBDate($data_ocorrencia)."','".gDBDate($data_efetivacao)."',".$rs->fields['valor'].",$id_cliente,'$tipo_cliente','$observacao')";
			gQuery($sql);
			//echo $sql."<br>";	
			
			return true;
		}
		else
		{
			return false;
		}
	}
	else
		return false;
}
//FUNÇÃO QUE DA BAIXA NOS BOLETOS VINDOS ATRAVÉS DE EMISSÃO DE CONTAS
function gBaixaBoletoConta($nosso_numero,$data_ocorrencia,$data_efetivacao)
{
	if(($nosso_numero<>"") || ($nosso_numero<>"00000000"))
	{
		$sql="select * from fin_parcelas where num_documento = '$nosso_numero' ";
		$rs=gQuery($sql);
		//echo $sql." - 2<br>";
		if(!$rs->EOF)
		{
			$sql="update fin_parcelas set data_pagamento='".gDBDate($data_ocorrencia)."',data_efetivacao='".gDBDate($data_efetivacao)."' where id=".$rs->fields['id']." ";
			gQuery($sql);
			//echo $sql."<br>";
			return true;
		}
		else
		{
			return false;
		}
	}
	else
		return false;
}
function tiracentos($t_st)
{
    $t_t=strtr($t_st," áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇ","_aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcC");
    return $t_t;
}

function mkDirE($dir,$dirmode=0744)
{
	if (!empty($dir))
	{
		if (!file_exists($dir))
		{
			preg_match_all('/([^\/]*)\/?/i', $dir,$atmp);
			$base="";
			foreach ($atmp[0] as $key=>$val)
			{
				$base=$base.$val;
				if(!file_exists($base))
					if (!mkdir($base,$dirmode))
					{
						echo "Error: Cannot create ".$base;
						return -1;
					}
			}
		}
		else
			if (!is_dir($dir))
			{
				echo "Error: ".$dir." exists and is not a directory";
				return -2;
			}
	}
		return 0;
}
//ADICIONA CRÉDITOS
function addCreditos($id_boleto){
	$sql = "select id_pedido, valor, nosso_numero, data_criacao, data_vencimento, id_geral_pessoas, id_geral_empresas from fin_numero_boletos where id=$id_boleto";
	$rs = gQuery($sql);
	$valor = $rs->fields["valor"];
	$id_numero_ok = $rs->fields["id_pedido"];
	$data_criacao = $rs->fields["data_criacao"];
	$data_vencimento = $rs->fields["data_vencimento"];
	$nosso_numero = $rs->fields["nosso_numero"];
	$id_geral_pessoas = $rs->fields["id_geral_pessoas"];
	$id_geral_empresas = $rs->fields["id_geral_empresas"];
	$dbcred = "CR";
	$data_lanc = date("Y-m-d");
	if($id_geral_pessoas==0){
		$tipo_cliente = "id_geral_empresas";
		$id_cliente = $id_geral_empresas;
	}else{
		$tipo_cliente = "id_geral_pessoas";
		$id_cliente = $id_geral_pessoas;
	}
	$sql = "update ok_voip_numeros_ok set ligar_para_pstn=1, prepago = 1, creditos= creditos+$valor where id=$id_numero_ok";
	//echo $sql."<br/>";
	gQuery($sql);
	
	$sql = "update fin_numero_boletos set pago = 1 where id=$id_boleto";
	gQuery($sql);
	/*
	//******** Insere em fin_lancamentos ***************
	$sql = "insert into fin_lancamentos (id_ok_voip_numeros_ok, id_geral_pessoas, id_geral_empresas, tipo_cliente,id_cliente,valor,id_formas_pagamento, debcre, data_lancamento,observacao)";
	$sql .= " values ($id_numero_ok,$id_geral_pessoas,$id_geral_empresas,'$tipo_cliente',$id_cliente,$valor,1,'CR','$data_lanc','Créditos de $valor no pre-pago')";
	//echo $sql."<br/>";
	gQuery($sql);
	
	//*********Insere em fin_parcelas *********
	$sql = "select max(id) as maxid from fin_lancamentos where id_geral_pessoas=$id_geral_pessoas and id_geral_empresas = $id_geral_empresas and id_ok_voip_numeros_ok = $id_numero_ok ";
	$rs = gQuery($sql);
	//echo $sql."<br/>";
	$id_fin = $rs->fields["maxid"];
	$sql = "insert into fin_parcelas (id_fin_lancamentos, id_cliente, tipo_cliente, valor, num_documento, id_fin_contas,data_lancamento,data_vencimento,data_pagamento,data_efetivacao,observacao)";
	$sql .= " values ($id_fin, $id_cliente, '$tipo_cliente', $valor, '$nosso_numero', 1,'$data_lanc','$data_lanc', '$data_lanc','$data_lanc','Créditos de $valor no pre-pago')";
	//echo $sql."<br/>";
	gQuery($sql);
	*/
}
function addOkin($id_boleto){
	$sql = "select id_pedido, pedido, valor, nosso_numero, data_criacao, data_vencimento, id_geral_pessoas, id_geral_empresas,ddd,id_pstn from fin_numero_boletos where id=$id_boleto";
	$rs = gQuery($sql);
	$ddd = $rs->fields["ddd"];
	$id_pstn = $rs->fields["id_pedido"];
	$pedido = $rs->fields["pedido"];
	$valor = $rs->fields["valor"];
	$id_numero_ok = $rs->fields["id_pedido"];
	$data_criacao = $rs->fields["data_criacao"];
	$data_vencimento = $rs->fields["data_vencimento"];
	$nosso_numero = $rs->fields["nosso_numero"];
	$id_geral_pessoas = $rs->fields["id_geral_pessoas"];
	$id_geral_empresas = $rs->fields["id_geral_empresas"];
	$qtde = $valor / 39.99;
	$qtde_dias = $qtde * 30;
	$valor_creditos = $qtde * 15; 
	$dbcred = "CR";
	$data_lanc = date("Y-m-d");
	if($id_geral_pessoas==0){
		$tipo_cliente = "id_geral_empresas";
		$id_cliente = $id_geral_empresas;
	}else{
		$tipo_cliente = "id_geral_pessoas";
		$id_cliente = $id_geral_pessoas;
	}
	
	if($pedido == "Compra OKIN"){
		compraOkin($id_numero_ok,$ddd,$qtde_dias,$valor_creditos);
	}else{
		renovaOkin($id_pstn, $qtde_dias);
	}
	
	
	$sql = "update fin_numero_boletos set pago = 1 where id=$id_boleto";
	gQuery($sql);
	/*
	//******** Insere em fin_lancamentos ***************
	$sql = "insert into fin_lancamentos (debcre,id_fin_tipos,id_ok_voip_numeros_ok, id_geral_pessoas, id_geral_empresas, tipo_cliente,id_cliente,valor,id_formas_pagamento, data_lancamento,observacao)";
	$sql .= " values ('CR',110,$id_numero_ok,$id_geral_pessoas,$id_geral_empresas,'$tipo_cliente',$id_cliente,$valor,1,'$data_lanc','Compra OK IN no valor de $valor + R$15,00 de créditos grátis')";
	//echo $sql."<br/>";
	gQuery($sql);
	
	//*********Insere em fin_parcelas *********
	$sql = "select max(id) as maxid from fin_lancamentos where id_geral_pessoas=$id_geral_pessoas and id_geral_empresas = $id_geral_empresas and id_ok_voip_numeros_ok = $id_numero_ok ";
	$rs = gQuery($sql);
	//echo $sql."<br/>";
	$id_fin = $rs->fields["maxid"];
	$sql = "insert into fin_parcelas (id_fin_lancamentos, id_cliente, tipo_cliente, valor, num_documento, id_fin_contas,data_lancamento,data_vencimento,data_pagamento,data_efetivacao,observacao)";
	$sql .= " values ($id_fin, $id_cliente, '$tipo_cliente', $valor, '$nosso_numero', 1,'$data_lanc','$data_lanc', '$data_lanc','$data_lanc','Compra OK IN no valor de $valor + R$15,00 de créditos grátis')";
	//echo $sql."<br/>";
	gQuery($sql);
	*/
}

function compraOkin($id_numero_ok,$ddd, $qtde_dias, $valor_creditos){
	$sql = "select id, numero_pstn from ok_voip_numeros_pstn where ativo=0 and ddd=$ddd and id_ok_voip_numeros_ok=0 limit 0,1";
	$rs_pstn = gQuery($sql);
	$id_pstn = $rs_pstn->fields["id"];
	$numero_pstn = $rs_pstn->fields["numero_pstn"];
	$validade_okin = gDBDate(gDateAdd(date("d-m-y"),$qtde_dias));
	AST_AtivaPSTN2($id_numero_ok,$numero_pstn);
	$sql = "update ok_voip_numeros_pstn set ativo=1, id_ok_voip_numeros_ok = $id_numero_ok, data_cadastro='$data_lanc', data_validade_okin='$validade_okin' where id=$id_pstn";
	gQuery($sql);
	
	//$valor = 15;
	
	$sql = "update ok_voip_numeros_ok set numero_pstn='$numero_pstn',ligar_para_pstn = 1, prepago = 1, creditos= creditos+$valor_creditos where id=$id_numero_ok";
	//echo $sql."<br/>";
	gQuery($sql);
	
	//$sql = "update ok_voip_numeros_ok set ligar";
	//echo $sql."<br/>";
}

function renovaOkin($id_numero_pstn, $qtde_dias){
	$sql = "select id, data_validade_okin, id_ok_voip_numeros_ok from ok_voip_numeros_pstn where id=$id_numero_pstn";
	$rs_pstn = gQuery($sql);
	$data_validade_okin = $rs_pstn->fields["data_validade_okin"];
	if($data_validade_okin > date){
		$validade_okin = gDBDate(gDateAdd(date("d-m-y"),$qtde_dias));
	}else{
		$validade_okin = gDBDate(gDateAdd(gDate($data_validade_okin),$qtde_dias));
	}
	$sql = "update ok_voip_numeros_pstn set ativo=1, data_validade_okin='$validade_okin' where id=$id_numero_pstn";
	gQuery($sql);
	
	//echo $sql."<br/>";
}

?>