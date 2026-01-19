<?
class gSimulador

{

	function calculo_soma($cir,$largura_de_banda,$e1_disponiveis,$total_e1,$ramais_disponiveis,$troncos_disponiveis,$tecnologia_link)
	{
		
		$solucao=new gSolucao();
		//$out=new gPage();
		if ($cir>$largura_de_banda)
		{
			$solucao->mensagem="É impossível existir uma banda mínima (CIR) maior que a largura de banda!";
			$solucao->erro=true;
			//$out->gMsg("É impossível existir uma banda mínima (CIR) maior que a largura de banda!",0,true);
		} elseif ($e1_disponiveis>$total_e1)
		{
			$solucao->mensagem="E1 disponíveis deve ser menor que o total de E1!";
			$solucao->erro=true;
			//$out->gMsg("E1 disponíveis deve ser menor que o total de E1!",0,true);
		} elseif ($e1_disponiveis+$ramais_disponiveis+$troncos_disponiveis+$largura_de_banda+$cir>0)
		{
			
			//$out->gOut("<hr>");
			//$out->gMsg("Parâmetros de entrada:");$out->gBr();
		//	$mtz[0][0]="<-Parâmetro";$solucao->valor=$Valor;
			$solucao->troncos_disponiveis=$troncos_disponiveis;
			$solucao->ramais_disponiveis=$ramais_disponiveis;
			$solucao->total_e1=$total_e1;
			$solucao->e1_disponiveis=$e1_disponiveis;
			$solucao->largura_de_banda=$largura_de_banda;
			$solucao->cir=$cir;
			$solucao->tecnologia_link=$tecnologia_link;
			//$mtz[7][0]="<-Link exclusivo para VoIP?";
			if ($link_exclusivo=="on")
				$solucao->link_exclusivo="Sim";
			else
				$solucao->link_exclusivo="Não";
			//$mtz[6][1]="<-".$link_exclusivo=="on"?"Sim":"Não";
			
			//$this->gTable($mtz,gT_TINY,false);
			//$this->gOut("<hr>");
			if($solucao->tecnologia_link=="ADSL")
				$solucao->banda_util=$solucao->cir;
			else
				$solucao->banda_util=intval(($largura_de_banda-$cir)/3+$cir);
			if ($link_exclusivo!="on")
			{
				$solucao->banda_util=intval($solucao->banda_util/2);
			}
			$max_ok=intval($solucao->banda_util/36);
			$solucao->max_ok = $max_ok;
			if ($ramais_disponiveis>$max_ok)
				$solucao->sol_fxo=$max_ok;
			else
				$solucao->sol_fxo=$max_ok-$ramais_disponiveis;
			if ($ramais_disponiveis>$solucao->sol_fxo)
				$solucao->sol_fxo=$ramais_disponiveis;
				
			if ($max_ok>$ramais_disponiveis)
				$solucao->sol_fxs=$ramais_disponiveis;
			else
				$solucao->sol_fxs=$max_ok;
			
			if ($solucao->sol_fxo>$troncos_disponiveis)
				$solucao->sol_fxo=$troncos_disponiveis;
			
			if ($solucao->sol_fxo+$solucao->sol_fxs>$max_ok)
				$solucao->sol_fxos=$max_ok;
			else
				$solucao->sol_fxos=$solucao->sol_fxo+$solucao->sol_fxs;
			
			$solucao->max_ok_iax=intval((($solucao->banda_util-36)/10)+1);
			$solucao->max_ok_e1=$solucao->max_ok_iax;
			$solucao->qtd_e1=ceil($solucao->max_ok_iax/30);
			if ($solucao->qtd_e1<=$solucao->e1_disponiveis)
			{
				$solucao->qtd_e1=$solucao->qtd_e1;
			} else
			{
				$solucao->max_ok_e1=$solucao->qtd_e1*30;
			}
			$solucao->erro=false;
			/*$mtz="";
			$mtz[0][0]="<-Parâmetro";$mtz[0][1]="<-Valor";
			$mtz[1][0]="<-Largura de banda útil para VoIP";$mtz[1][1]="<-".$banda_util;
			$mtz[2][0]="<-Máximo de números OKscm";$mtz[2][1]="<-".$max_ok;
			if ($total_e1>0)
			{
				$mtz[3][0]="<-Máximo de números OKscm c/ OKMaxband";$mtz[3][1]="<-".$max_ok_e1;
				$mtz[4][0]="<-Quantidade de E1 necessários";$mtz[4][1]="<-".$qtd_e1;
			}
			$out->gMsg("Parâmetros calculados:");$out->gBr();
			$out->gTable($mtz,gT_TINY,false);
			$out->gOut("<hr>");
			$out->gMsgSubTitle("Soluções possíveis");
			
			$mtz="";
			$mtz[0][0]="<-Solução";$mtz[0][1]="<-Máximo de números OKscm";$mtz[0][2]="<-Equipamentos";$mtz[0][3]="<-Inv. inicial";$mtz[0][4]="<-Inv. mensal";
			$inv_inicial=gFloat(0);
			$inv_mensal=gFloat(0);
			$mtz[1][0]="<-Baseada somente em FXO";$mtz[1][1]="<-$sol_fxo";$mtz[1][2]="<-$equip";$mtz[1][3]="<-$inv_inicial";$mtz[1][4]="<-$inv_mensal";
			$mtz[2][0]="<-Baseada somente em FXS";$mtz[2][1]="<-$sol_fxs";$mtz[2][2]="<-$equip";$mtz[2][3]="<-$inv_inicial";$mtz[2][4]="<-$inv_mensal";
			if (($sol_fxo==0) || ($sol_fxs==0))
			{
				$mtz[3][0]="~5<-Não é possível solução baseada em FXS e FXO";
			}
			else
			{
				$mtz[3][0]="<-Baseada em FXS e FXO";$mtz[3][1]="<-$sol_fxos";$mtz[3][2]="<-$equip";$mtz[3][3]="<-$inv_inicial";$mtz[3][4]="<-$inv_mensal";
			}
			if ($qtd_e1<=$e1_disponiveis)
			{
				$mtz[4][0]="<-Baseada em IPBX c/ E1 e OKMaxband";$mtz[4][1]="<-$max_ok_iax";$mtz[4][2]="<-$equip";$mtz[4][3]="<-$inv_inicial";$mtz[4][4]="<-$inv_mensal";
			}
			else
			{
				$mtz[4][0]="<-Baseada em IPBX sem E1 c/ OKMaxband";$mtz[4][1]="<-$max_ok_iax";$mtz[4][2]="<-$equip";$mtz[4][3]="<-$inv_inicial";$mtz[4][4]="<-$inv_mensal";
			}
			$out->gTable($mtz,gT_BIG,false);
			*/
		} 
		else
		{
			$solucao->mensagem="Erro desconhecido!";
			$solucao->erro=true;
			//$mtz[3][0]="~5<-ERRO!";
			//$out->gTable($mtz,gT_BIG,false);
		}
		return $solucao;
	}
	
}
class gSolucao
{
	var $Valor;
	var $troncos_disponiveis;
	var $ramais_disponiveis;
	var $total_e1;
	var $e1_disponiveis;
	var $largura_de_banda;
	var $cir;
	var $link_exclusivo;
	var $banda_util;
	var $sol_fxo;
	var $max_ok;
	var $sol_fxs;
	var $sol_fxos;
	var $max_ok_iax;
	var $max_ok_e1;
	var $qtd_e1;
	var $tecnologia_link;
	var $mensagem;
	var $erro;

}
?>