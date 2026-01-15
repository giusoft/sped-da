<?php
include "inc_2.0/gConf.php";
include "inc_2.0/gInput.php";
$out=new gInput();

$i=gCleanField($_REQUEST['i']);
$t=gCleanField($_REQUEST['t']);
if ($t=="cfc")
{
	$s=file_get_contents("/tmp/boleto_cfc_$i");
	if ($s==date("YmdHi"))
	{
		$CFC=$i;
		$erro=false;

		// temporário!!!!!!!!!!!!!!!!!!!!!!!
		gVar("database.url","www.giusoft.com.br");

		$sql="select p.id pid, p.*,j.*,e.*,c.descricao cidade,est.descricao estado
				from geral_pessoas p
				left join geral_pessoas_juridicas j on p.id=j.id_geral_pessoas
				left join geral_pessoas_enderecos e on p.id=e.id_geral_pessoas
				left join geral_cidades c on e.id_geral_cidades=c.id
				left join geral_estados est on e.id_geral_estados=est.id
				where p.apelido='$i'";
		$rs=gQuery($sql);
		if (!$rs->EOF)
		{
			// Cliente existe. Verifica se existe lançamento financeiro pendente para ele
			$sql="select l.id idl,p.* from fin_lancamentos l
					left join fin_parcelas p on l.id=p.id_fin_lancamentos
					where l.id_geral_pessoas=".$rs->fields['pid']." and (p.data_efetivacao='' or p.data_efetivacao='0000-00-00')
					order by data_vencimento";
			$rsl=gQuery($sql);
			if (!$rsl->EOF)
			{
				$dias_de_prazo_para_pagamento = 3;
				$multa=2; //2%
				$juros=0.03; // 0,03% por dia
				$taxa_boleto = 2.70;

				$data_vencimento=$rsl->fields['data_vencimento'];
				$valor_cobrado = $rsl->fields['valor']; // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
				$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006";
				
				if ($data_vencimento<date("Y-m-d"))
				{
					// Venceu, aplica juros e multa
					$dias=gDateDiff(gDate($data_vencimento),gDate(date("Y-m-d")));
					/*
					$intervalo=date_diff(date_create($data_vencimento),date_create(date("Y-m-d")));
					$dias=$intervalo->format('%a'); // dias de atraso
					 * 
					 */
					$valor_final=$valor_cobrado+($valor_cobrado*$multa/100)+($valor_cobrado*$juros/100*$dias);
					$valor_cobrado=$valor_final;
					//echo "venceu: $data_vencimento - dias: $dias - valor: $valor_cobrado <br>";
				} else
					$data_venc=date("d/m/Y", strtotime($data_vencimento));
				$valor_cobrado = str_replace(",", ".",$valor_cobrado);
				$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');
				$dadosboleto["servico"] = "Sistema ERPCFC";
				$dadosboleto["numero_documento"] = $rsl->fields['idl'];	// Num do pedido ou do documento
				$dadosboleto["nosso_numero"] = $rsl->fields['idl'];

				
				// DADOS DO SEU CLIENTE
				$dadosboleto["sacado"] = ($rs->fields['razao_social']);
				$dadosboleto["endereco1"] = ($rs->fields['endereco']);
				$dadosboleto["endereco2"] = ($rs->fields['cidade']." - ".$rs->fields['estado']." - CEP ".$rs->fields['cep']);

				// INFORMACOES PARA O CLIENTE
				$dadosboleto["demonstrativo1"] = "PAGAMENTO REFERENTE AO USO DO SISTEMA ERPCFC";
				$dadosboleto["demonstrativo2"] = "TAXA BANCARIA - R$ ".number_format($taxa_boleto, 2, ',', '');
				$dadosboleto["demonstrativo3"] = "";

				// INSTRUCOES PARA O CAIXA
				$dadosboleto["instrucoes1"] = "- NAO RECEBER APOS VENCIMENTO";
				$dadosboleto["instrucoes2"] = "";
				$dadosboleto["instrucoes3"] = "";
				$dadosboleto["instrucoes4"] = "";

				// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
				$dadosboleto["quantidade"] = "1";
				$dadosboleto["valor_unitario"] = $valor_boleto;
				$dadosboleto["aceite"] = "N";
				$dadosboleto["especie"] = "R$";
				$dadosboleto["especie_doc"] = "DM";
			} else
			{
				$out->gBegin();
				$out->gMsgTitle("Geração de Boletos");
				$out->gMsgAlert("Não existe nenhum boleto pendente para pagamento.");
				$out->gEnd();
				exit;
			}
		} else
			$erro=true;
		/*
		gVar("database.url","www.sindautobahia.com.br");
		gVar("database.name","erpcfc_gestao");
		$sql="select * from logins where login='$CFC'";
		$rs=gQuery($sql);
		if (!$rs->EOF)
		{
		} else
			$erro=true;
		 * 
		 */
		if ($erro)
		{
			$out->gBegin();
			$out->gMsgTitle("Geração de Boletos");
			$out->gMsgAlert("Erro ao gerar o boleto. Tente novamente mais tarde.");
			$out->gEnd();
		} else
		{
			include "boleto_bb.php";
		}
	} else
	{
		$out->gBegin();
		$out->gMsgTitle("Geração de Boletos");
		$out->gMsgAlert("Erro ao gerar o boleto. Tente novamente mais tarde.");
		$out->gEnd();
	}

} else
{
	$out->gBegin();
	$out->gMsgTitle("Geração de Boletos");
	$out->gEnd();
}
?>
