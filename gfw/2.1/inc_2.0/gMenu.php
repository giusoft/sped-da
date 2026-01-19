<?
include $gPathDefault."gInput.php";
include $gPathDefault."gCSV.php";
include $gPathDefault."gPermissions.php";


$siglas="";
$siglasfull="";
$siglastipo="";
$siglasperm="";

$abas_siglas="";
$abas_nomes="";
$hints="";
$hintcnt=0;


function formatHint($titulo,$ajuda)
{
	$sai="<b>$titulo</b><br><br>".gWiki($ajuda);
	return($sai);
}


function arrayAbas($i,$t)
{
	global $siglas;
	global $siglasfull;
	global $siglastipo;
	global $siglasperm;
	global $abas_siglas;
	global $abas_nomes;
	global $hints;
	global $hintcnt;
	global $usr_id;
	$cli=gSessionLoad("usr_cliente");
	$for=gSessionLoad("usr_fornecedor");

	$tsiglas="";

	$gProcess="I";
	$filtro="";
	if ($cli) 
	{
		$gProcess="2";
		$filtro.=" sigla like 'C%' or ";
	}
	if ($for) 
	{
		$gProcess="3";
		$filtro.=" sigla like 'F%' or ";
	}
	if ($age) 
	{
		$gProcess="4";
		$filtro.=" sigla like 'A%' or ";
	}
	if ($filtro<>"")
		$filtro="(".substr($filtro,0,strlen($filtro)-3).") ";
	else
		$filtro.=" 1 ";

	if ($cli)
	{
		$sql="SELECT sigla,nome,descricao from geral_links where invisivel=0 and LENGTH(sigla)=3 and $filtro order by sigla";
		$siglasperm[]="C";
	} elseif ($for)
	{
		$sql="SELECT sigla,nome,descricao from geral_links where invisivel=0 and LENGTH(sigla)=3 and $filtro order by sigla";
		$siglasperm[]="F";
	} elseif ($usr_id<=3)
	{
		$sql="SELECT sigla,nome,descricao from geral_links where invisivel=0 and LENGTH(sigla)=3 order by sigla";
		$siglasperm[]="";
	} else
	{
		/*
		$tsiglas[]="I00";
		$abas_siglas[]="I00";
		$abas_nomes[]=array("Principal","openIndex(0)");
		*/
		$sql="SELECT distinct sigla,nome,descricao from geral_links where invisivel=0 and sigla=any (select distinct LEFT(sigla_geral_links,9) as sigla from geral_links_permissoes where (id_pes_funcoes=0 and id_pes_setores=0 ";
		$sql.="or id_pes_setores=any (Select s.id from pes_funcionalismo f left join pes_funcionalismo_setores fs on f.id=fs.id_pes_funcionalismo left join pes_setores s on fs.id_pes_setores=s.id where f.id_geral_pessoas=$usr_id)";
		$sql.="or id_pes_funcoes=any (Select s.id from pes_funcionalismo f left join pes_funcionalismo_funcoes fs on f.id=fs.id_pes_funcionalismo left join pes_funcoes s on fs.id_pes_funcoes=s.id where f.id_geral_pessoas=$usr_id))) order by sigla";
		$rst=gQuery($sql);
		while (!$rst->EOF)
		{
			$siglasperm[]=$rst->fields['sigla'];
			$rst->MoveNext();
		}
		$sql="SELECT distinct sigla,nome,descricao from geral_links where invisivel=0 and sigla=any (select distinct LEFT(sigla_geral_links,3) as sigla from geral_links_permissoes where (id_pes_funcoes=0 and id_pes_setores=0 ";
		$sql.="or id_pes_setores=any (Select s.id from pes_funcionalismo f left join pes_funcionalismo_setores fs on f.id=fs.id_pes_funcionalismo left join pes_setores s on fs.id_pes_setores=s.id where f.id_geral_pessoas=$usr_id)";
		$sql.="or id_pes_funcoes=any (Select s.id from pes_funcionalismo f left join pes_funcionalismo_funcoes fs on f.id=fs.id_pes_funcionalismo left join pes_funcoes s on fs.id_pes_funcoes=s.id where f.id_geral_pessoas=$usr_id))) order by sigla";
		
	}
//echo $sql;
	$rspro=gQuery($sql);
	if (!$rspro->EOF)
	{
		while (!$rspro->EOF)
		{
			$tem=false;
			$s=$rspro->fields['sigla'];
			
			// $siglas é usado fora desta função e contém todos os links de todos os usuários selecionados, acumulando resultados
			$siglas[]=$s;			
			// $tsiglas é usado somente dentro desta função para determinar quais links este usuário e tipo podem acessar
			$tsiglas[]=$s;
			$siglasfull[]=$s;
			$siglastipo[]=$t;
			$rspro->MoveNext();
		}
		if (count($tsiglas>0))
		{
			$s="'".implode("','",$tsiglas)."'";
			//$s=str_replace(",'I00'","",$s);
			//$s=str_replace("'I00',","",$s);
			if ($s<>"''")
			{
				$sql="select distinct id,sigla,nome,descricao,ajuda from geral_links where sigla in ($s) and invisivel=0 ";
				$rsabas=gQuery($sql);
				
				while (!$rsabas->EOF)
				{
					//if (strlen($rsabas->fields['sigla'])<=4)
					{
						$abas_siglas[]=$rsabas->fields['sigla'];
						$hints[]=formatHint($rsabas->fields['descricao'],$rsabas->fields['ajuda']);
						//$abas_nomes[]="<span onMouseOver=\"openHint($hintcnt)\" onMouseOut=\"closeHint()\">".$rsabas->fields['nome']."</span>";
						//$abas_nomes[]="<span style=\"display: block\" onClick=\"openIndex(".$rsabas->fields['id'].")\">".$rsabas->fields['nome']."</span>";
						$abas_nomes[]=array($rsabas->fields['nome'],"openIndex(".$rsabas->fields['id'].")");
						$hintcnt++;
					}
					$rsabas->MoveNext();
				}
			}
		}
	}	
}




class gMenu extends gInput
{
	
	function gMenu()
	{
	}
	
	
	function gShowIcon($iconfile,$style=0)
	{
		global $http_img;
		if ($style==0) $tam="16x16";
		if ($style==0) $tam="22x22";
		if ($style==0) $tam="32x32";
		$file="$http_img/icons/$tam/$iconfile";
		$sai="<img src='$file' border='0' style=\"filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=scale src='$file');\" >";
		return($sai);
	}
	
	function gShowMenu($style=gM_DEFAULT)
	{
		global $gPath;
		global $gPathDefault;
		global $idd;
		global $usr_id;
		global $usr_idd;
		
		global $usr_funcionario;
		global $usr_cliente;
		global $usr_fornecedor;
		global $usr_terceirizado;
		global $usr_concorrente;

		global $empr_agente;
		global $empr_cliente;
		global $empr_fornecedor;
		global $empr_terceirizado;
		global $empr_concorrente;
		
		global $http;
		global $http_img;
		global $http_inc;
		global $http_base;
		
		global $gId;

		global $siglas;
		global $siglasfull;
		global $siglastipo;
		global $siglasperm;
		
		global $abas_siglas;
		global $abas_nomes;
		global $hints;
		global $hintcnt;
		
		
		$thispage=$_SERVER["PHP_SELF"];
		$gId=$_REQUEST["gId"];
		$gParam=$_REQUEST["gParam"];
		$gProcess=$_REQUEST["gProcess"];
		$gSubProcess=$_REQUEST["gSubProcess"];
		
		/*
		$cli=$empr_cliente | $usr_cliente;
		$for=$empr_fornecedor | $usr_fornecedor;
		$ter=$empr_terceirizado | $usr_terceirizado;
		$con=$empr_concorrente | $usr_concorrente;
		$fun=$usr_funcionario | $usr_id==1;
		*/
		
		$browser=$_SERVER["HTTP_USER_AGENT"];
			
		$border=0;
		if (strpos($browser,"MSIE")>0)
		{
			$altura=150;
			$div="hintie";
		} else
		{
			$div="hintff";
			$altura=146;
		}
		if ($usr_id>0)
		{
		
		
		




			if ( (!(strpos($browser,"iPhone")===false)) || ($usr_id==51111111))
			{

				arrayAbas(0,0);
				$this->gOut("<html><head><title>".gVar("global.site")."</title></head>\n");
				$this->gOut("<link href='$http_img/schema/".gVar("global.schema")."/menu_mini.css' rel='stylesheet' type='text/css'>\n");
				$this->gOut("<meta http-equiv='content-type' content='text/html; charset=ISO-8859-1'>\n");
				$this->gOut("<meta name='viewport' content='width=480, user-scalable=0'>\n");
				$this->gOut("<link rel='shortcut icon' href='img_2.0/gs.ico'>\n");
				$this->gOut("<body >\n");
				$this->gOut("<div align='center'><b>".gVar("global.site")."</b></div><br>");

				if ($gParam=="")
				{
					$this->gOut("<table width='100%' border='0'>\n");
					$this->gOut("<tr>\n");
					if (substr(gVar("database.type"),0,5)=="mysql")
						$sql="select * from geral_links where invisivel=0 and estilo=10 order by sigla";
					else
						$sql="select * from geral_links where invisivel=0 and estilo=10 order by sigla";
					$rs=gQuery($sql);
					$cnt=0;
					while (!$rs->EOF)
					{
						$cnt++;
						$lnk="<img border='0' src='$http_img/icons/32x32/".$rs->fields['imagem']."'>";
						//$this->gOut("<td><a href='$thispage?gParam=".$rs->fields['sigla']."'>$lnk<br>".$rs->fields['nome']."</a></td>\n");
						$this->gOut("<td><a href='".$rs->fields['link']."'>$lnk<br>".$rs->fields['nome']."</a></td>\n");
						$rs->MoveNext();
						if ($cnt==5)
						{
							$cnt=0;
							$this->gOut("</tr><tr>");
						}
					}
					$this->gOut("</tr>\n");
					$this->gOut("</table>\n");


				} else
				{


					// Cria abas com os nomes coletados anteriormente
					$this->gTabTableBegin($abas_nomes,gT_BIG);
					for ($a=0; $a<count($abas_nomes); $a++)
					{
						$sigla=$abas_siglas[$a];
						$this->gTabBegin();
						$mtz="";
						// Cada aba contém uma tabela completa
						$this->gOut("<table cellpadding='0' cellspacing='0'>");
						$this->gOut("<tr valign='top'>");
						for ($sf=0; $sf<count($siglasfull); $sf++)
						{
							$s=$siglasfull[$sf];
							$t=$siglastipo[$sf];

							// Lê siglas em busca das que são deste módulo
							if ($sigla==substr($s,0,strlen($sigla)))
							{
								$sql="select * from geral_links where sigla like '$s%' and  ({ fn LENGTH(sigla) } > 3) and invisivel=0 order by sigla";
								$rsfr=gQuery($sql);
								if (!$rsfr->EOF)
								{

									while (!$rsfr->EOF)
									{
										$fez=false;
										$rtipo=$rsfr->fields['id_geral_pessoas_tipos'];
										$achou=false;
										foreach ($siglasperm as $s)
										{
											if (substr($s,0,6)==substr($rsfr->fields['sigla'],0,strlen(substr($s,0,6)))) $achou=true;
										}
										if (($achou) && (strlen($rsfr->fields['sigla'])==6))
										{

										}
									}
								}
							}
						}
					}








					$this->gOut("</body>");
					$this->gOut("</html>");

				}





			} else
			{








		
		
				if ($gParam=="")
				{
					?>
					<script>
					if (self.parent.frames.length >= 2)
						self.parent.location = document.location;
					</script>
					<?
					$this->gOut("<html><head><title>".gVar("global.site")."</title><link rel='shortcut icon' href='$http_img/gs.ico'>\n</head>");
					$this->gOut("<link href='$http_img/schema/".gVar("global.schema")."/menu.css' rel='stylesheet' type='text/css'>");
					
					$this->gOut("<frameset bgcolor='white' border=$border rows=$altura,*>");
					//$this->gOut("  <frame name='hidden'>");
					$this->gOut("  <frame name='topbar' src='$thispage?gParam=topbar'>");
					$this->gOut("  <frame name='screen' src='$http_base/online/index.php'>");
					$this->gOut("</frameset>");
					$this->gOut("</html>");
			// ************************
				} elseif ($gParam=="topbar")
				{
				
					$this->gOut(gTag("menu.topbar_start"));
					
					$admin="";
					//if (gSessionLoad("usr_id")==1) $admin="bgcolor='#800000'";
					$this->gOut("<div id='gHint' class='$div'></div>");
					$s="<table class='topbar' width='100%' border='0' $admin><tr><td class='topbar' width='35%' align='left'>".gSessionLoad("usr_nome")."</td><td class='topbar' width='30%' align='center'><b>".gVar("global.site")."</b></td><td width='35%' align='right'>&nbsp;</td></tr></table>";
					$this->gOut($s);
					$this->gOut("<link href='$http_img/schema/".gVar("global.schema")."/menu.css' rel='stylesheet' type='text/css'>");
					$this->BaseSel=false;
					$this->gOut("<script language='javascript'>".$this->gJSMousePos());
			?>
			
				var hints=new Array();
				var onHint=false;
				var hintTimer;
				function hint(num)
				{
					clearTimeout(hintTimer);
					x=posx;
					if (x>600) x=x-240;
					document.getElementById('gHint').style.left=(x+12)+'px';
					//document.getElementById('gHint').style.top=(posy-5)+'px';
					document.getElementById('gHint').style.top='5px';
					document.getElementById('gHint').style.display='block';
					document.getElementById('gHint').innerHTML=hints[num];
					onHint=true;
				}
				function openHint(num)
				{
					if (!onHint)
					{
						s='hint('+num+')';
						hintTimer=setTimeout(s,1500);
						//setTimeout('closeHint()',60000);
					}
				}
				function closeHint()
				{
					document.getElementById('gHint').style.top='-500px';
					document.getElementById('gHint').style.display='none';
					document.getElementById('gHint').innerHTML='';
					onHint=false;
					clearTimeout(hintTimer);
				}
				function openIndex(id)
				{
					window.open('<?echo $thispage."?gParam=index&gId=";?>'+id,'screen');
				}
				
				
				</script>
			
			<?
				
						// ----------------- CRIA VETORES NECESSÁRIOS PARA DESENHAR O MENU ----------------- 
				
						// Query para pegar produtos contratados pelo login atual
						
						arrayAbas(0,0);
				
						
											
						// ----------------- DESENHA MENU ----------------- 
	?>
	<!--[if gte IE 5.5000]>
	<style type="text/css">
		 * html img/**/ {
	 filter:expression(
		this.alphaxLoaded ? "" :
		(
			 this.src.substr(this.src.length-4)==".png"
			 ?
			 (
		  (!this.complete)
		  ? "" :
				this.runtimeStyle.filter=
				("progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+this.src+"')")+
				(this.onbeforeprint="this.runtimeStyle.filter='';this.src='"+this.src+"'").substr(0,0)+
				String(this.alphaxLoaded=true).substr(0,0)+
				(this.src="http://www.giusoft.com.br/gs/v6/img_2.0/blank.png").substr(0,0)
			 )
			 :
			 this.runtimeStyle.filter=""
		)
		  );
		 }

	</style>
	<![endif]-->
	<?					
						$this->gOut("<table width='100%' class='frame' cellpadding='0' cellspacing='0'><tr valign='top'>");
						$this->gOut("<td width='140' align='center'>");
						$this->gOut("<p style='height: 4px'></p><a href='index.php' target='screen'><img border='0' src='$http_img/schema/".gVar("global.schema")."/mini_logo.png'></a><br>");
						$icons="<table class='nullborder' width='100%'><tr>";
						$icons.="<td align='center'><a class='menu2' title='Página inicial' href='index.php' target='screen'>".$this->gIcon("casa.png",gI_BIG)."<br>Início</a></td>";
						//$icons.="<a title='Agenda' href='utilitarios/agenda_mes.php' target='screen'>".$this->gIcon("calendario.png",gI_BIG)."</a> ";
						//$icons.="<td><a title='Relacionados' href='relacionamentos/pessoas_listar.php?gAction=edit' target='screen'>".$this->gIcon("pessoas.png",gI_BIG)."<br>Relacionados</a></td>";
						$icons.="<td align='center'><a class='menu2' title='Ajuda' href='../site/suporte.php?int=1' target='screen'>".$this->gIcon("m_interrogacao.png",gI_BIG)."<br>Ajuda</a></td>";
						$icons.="<td align='center'><a class='menu2' title='Sair do sistema' href='../logout.php' target='_top'>".$this->gIcon("sair.png",gI_BIG)."<br>Sair</a></td>";
						
						$icons.="</tr></table>";
						$this->gOut($icons);

		
						if (gVar("global.userphoto")=="true")
						{
							// Exibe foto do funcionário
							$sql="select imagem,imagem_altura,imagem_largura from geral_pessoas where id=$usr_id";
							$rsimg=gQuery($sql);
							if ($rsimg->fields["imagem_tipo"]!="")
							{
								$this->gOut("</td><td width='1%'><table class='nullborder' cellpadding='2' cellspacing='2' border='0'><tr><td>");
								$w=$rsimg->fields['imagem_largura'];
								$h=$rsimg->fields['imagem_altura'];
								$nw=$w;
								$nh=$h;
								/*
								$wm=$w>100?100:$w;
								if ($w>$wm)
								{
									$nw=$wm;
									$nh=($wm*$h)/$w;
								}
								*/
								$hm=$h>100?100:$h;
								if ($h>$hm)
								{
									$nh=$hm;
									$nw=($hm*$w)/$h;
								}
								
								$this->gOut("<img src='$http_base/online/i03_adm/cad_pessoas_img.php?gId=$usr_id' width='$nw' height='$nh' border='0'><br>");
								$this->gOut("</td></tr></table></td><td>");						
							}	else
							{
								$this->gOut("</td><td>");							
							}
						} else
						{
							$this->gOut("</td><td>");							

						}

						
						// Cria abas com os nomes coletados anteriormente
						$this->gTabTableBegin($abas_nomes,gT_BIG);
						for ($a=0; $a<count($abas_nomes); $a++)
						{
							$sigla=$abas_siglas[$a];
							$this->gTabBegin();
							$mtz="";
							// Cada aba contém uma tabela completa
							$this->gOut("<table cellpadding='0' cellspacing='0'>");
							$this->gOut("<tr valign='top'>");
							for ($sf=0; $sf<count($siglasfull); $sf++)
							{
								$s=$siglasfull[$sf];
								$t=$siglastipo[$sf];
								
								// Lê siglas em busca das que são deste módulo
								if ($sigla==substr($s,0,strlen($sigla)))
								{
									$sql="select * from geral_links where sigla like '$s%' and length(sigla)>3 and invisivel=0 order by sigla";
									$rsfr=gQuery($sql);
									if (!$rsfr->EOF)
									{
									
										while (!$rsfr->EOF)
										{
											$fez=false;
											$rtipo=$rsfr->fields['id_geral_pessoas_tipos'];
											$achou=false;
											foreach ($siglasperm as $s)
											{
												if (substr($s,0,6)==substr($rsfr->fields['sigla'],0,strlen(substr($s,0,6)))) $achou=true;
											}
											if (($achou) && (strlen($rsfr->fields['sigla'])==6))
											{
												
												$panel=$rsfr->fields['nome'];
												$rsfr->MoveNext();
												if ((!$rsfr->EOF) && (strlen($rsfr->fields['sigla'])>=9)) // Seção
												{
													$this->gOut("<td class='tab'>");
				
													$this->gTableBegin(gI_TINY,3); // Painel interno (inverte o transparente das imagens das bordas)
													$this->gTableRowBegin();
													$this->gOut("<td class='tab'>");
													
													$this->gOut("<div style='height: 60px; min-width: 120px; overflow: hidden'>");
													$this->gOut("<table class='menu' height='100%' width='100%'><tr valign='top'>");
													$cnt=0;
													$maxcnt=4;
													
													while ((!$rsfr->EOF) && (strlen($rsfr->fields['sigla'])>=9)) // Seção
													{
														
														$achou=false;
														foreach ($siglasperm as $s)
														{
															if (substr($s,0,9)==substr($rsfr->fields['sigla'],0,strlen(substr($s,0,9)))) $achou=true;
														}
														//if (in_array(substr($rsfr->fields['sigla'],0,9),$siglasperm))
														if ($achou)
														{
															$fez=true;
															if (strlen($rsfr->fields['sigla'])==9) // Sub-seção (início)
															{
																$estilo=$rsfr->fields['estilo'];
																if ($estilo==0)
																{
																	$style="width: 100px";
																	$maxcnt=4;
																}
																if ($estilo==1)
																{
																	$style="width: 100px";
																	$maxcnt=3;
																}
																if ($estilo==2)
																{
																	$style="width: 54px";
																	$maxcnt=2;
																}
																if ($cnt>0) 
																{
																	$this->gOut("</td>");
																	$this->gOut("<td style='width:0.5px; max-width:0.5px; overload:hidden; background: #d3dbf4'></td>");
																} 
																$this->gOut("<td class='panel_int' style='$style'>");
																$this->gOut("<font style='font-size: 9px; color: #719Bf0;'>".$rsfr->fields['nome']."<br></font>");
																$cnt=0;
															} elseif (strlen($rsfr->fields['sigla'])==12) // Link
															{
																$siglasperm[]=$rsfr->fields['link'];
																if ($estilo==0)
																{
																	$hints[]=formatHint($rsfr->fields['descricao'],$rsfr->fields['ajuda']);
																	if ($cnt==0)
																	{
																		$this->gOut("<font style='font-size: 9px; color: black;'> <br></font>");
																		$cnt++;
																	}
																	$this->gOut("<a class='menu' href='".$rsfr->fields['link']."' onMouseOver='openHint($hintcnt)' onMouseOut='closeHint()' target='screen'>".$rsfr->fields['nome']."</a>");
																	$hintcnt++;
																}
																if ($estilo==1)
																{
																	$hints[]=formatHint($rsfr->fields['descricao'],$rsfr->fields['ajuda']);
																	$lnk="<a class='menu' href='".$rsfr->fields['link']."' onMouseOver='openHint($hintcnt)' onMouseOut='closeHint()' target='screen'>";
																	$hintcnt++;
																	if ($rsfr->fields['imagem']<>"")
																		$lnk.="<img src='$http_img/icons/16x16/".$rsfr->fields['imagem']."' border='0'> ".$rsfr->fields['nome']."</a>";
																	else
																		$lnk.="<img src='$http_img/icons/16x16/categories/applications-other.png' border='0'>".$rsfr->fields['nome']."</a>";
																		$lnk.="";
																	$this->gOut($lnk);
																}
																if ($estilo==2)
																{
																	$hints[]=formatHint($rsfr->fields['descricao'],$rsfr->fields['ajuda']);
																	if ($cnt==0)
																		$this->gOut("<font style='font-size: 9px; color: black;'> <br></font>");
																	$lnk="<div align='center'><a class='menu' href='".$rsfr->fields['link']."' onMouseOver='openHint($hintcnt)' onMouseOut='closeHint()' target='screen'>";
																	$hintcnt++;
																	if ($rsfr->fields['imagem']<>"")
																		$lnk.="<img src='$http_img/icons/32x32/".$rsfr->fields['imagem']."' border='0'>";
																	else
																		$lnk.="<img src='$http_img/icons/32x32/categories/applications-other.png' border='0'>";
																	$lnk.="<br>".$rsfr->fields['nome']."</a></div>";
																	$this->gOut($lnk);
																	$cnt=1;
																}
															}


															$rsfr->MoveNext();
															$cnt++;
															if (($cnt==$maxcnt) && (strlen($rsfr->fields['sigla'])>9))
															{
																if ($cnt>0) 
																{
																	$this->gOut("</td>");
																}
																if (!$rsfr->EOF)
																{
																	$this->gOut("<td class='panel_int' style='$style'>");
																}
																$cnt=0;
															}


															
														} else
														{
															$rsfr->MoveNext();
														}
													}
													$this->gOut("</td></tr></table>");
													$this->gOut("</div>");
													$this->gTableColEnd();
													$this->gTableRowEnd();
													$this->gTableRowBegin();
													if ($rtipo>0)
														$this->gOut("<td class='panel_rel'>");
													else
														$this->gOut("<td class='panel'>");
													$this->gOut($panel);
													$this->gTableColEnd();
													$this->gTableRowEnd();
													$this->gTableEnd();
													$this->gTableColEnd();
												
												}
											}
											if (!$fez) $rsfr->MoveNext();
										}
									}
								}
							}
							
							$this->gTableRowEnd();
							$this->gOut("</table>");
							$this->gTabEnd();
						}
						$this->gTabTableEnd();
						$this->gOut("</td>");
						$this->gOut("</tr></table>");
						gSessionSave("gGrant",$siglasperm);
		?>
		<script language='javascript'>
			<?
			for ($a=0; $a<count($hints); $a++)
			{
				$this->gOut("hints[$a]='".str_replace("\n","",str_replace("\r","<br>",$hints[$a]))."';\n");
			}
			?>	
		</script>
	<!--[if gte IE 5.5000]>
	<style type="text/css">
		 * html img/**/ {
	 filter:expression(
		this.alphaxLoaded ? "" :
		(
			 this.src.substr(this.src.length-4)==".png"
			 ?
			 (
		  (!this.complete)
		  ? "" :
				this.runtimeStyle.filter=
				("progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+this.src+"')")+
				(this.onbeforeprint="this.runtimeStyle.filter='';this.src='"+this.src+"'").substr(0,0)+
				String(this.alphaxLoaded=true).substr(0,0)+
				(this.src="http://www.giusoft.com.br/gs/v6/img_2.0/blank.png").substr(0,0)
			 )
			 :
			 this.runtimeStyle.filter=""
		)
		  );
		 }

	</style>
	<![endif]-->


		<?		
				
				
				} elseif ($gParam=="index")
				{
					$sql="select * from geral_links where id=".intval($gId);
					$rs=gQuery($sql);
					if (!$rs->EOF)
					{
						$sigla=$rs->fields['sigla'];
						$arq=$rs->fields['link'];
						// Descrição do módulo
						$this->gBegin();
	?>
	<!--[if gte IE 5.5000]>
	<style type="text/css">
		 * html img/**/ {
	 filter:expression(
		this.alphaxLoaded ? "" :
		(
			 this.src.substr(this.src.length-4)==".png"
			 ?
			 (
		  (!this.complete)
		  ? "" :
				this.runtimeStyle.filter=
				("progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+this.src+"')")+
				(this.onbeforeprint="this.runtimeStyle.filter='';this.src='"+this.src+"'").substr(0,0)+
				String(this.alphaxLoaded=true).substr(0,0)+
				(this.src="http://www.giusoft.com.br/gs/v6/img_2.0/blank.png").substr(0,0)
			 )
			 :
			 this.runtimeStyle.filter=""
		)
		  );
		 }

	</style>
	<![endif]-->

	<?					
						$this->gOut("<div align='center'>");
							$this->gTableBegin(gT_TINY,true);
							$this->gTableRowBegin();
								$this->gTableColBegin("align='center'");
									$this->gOut("<div align='center'>");
									
										if ($rs->fields['imagem']<>"")
										{
											$img="<br><img src='$http_img/icons/64x64/".$rs->fields['imagem']."' border='0'>";
											$this->gOut($img."<br>");
										}
										$this->gMsgTitle($rs->fields['descricao']);
									$this->gOut("</div>");
									$this->gOut("<div align='center' style='padding: 4pt;'>");
										$this->gMsg(gWiki($rs->fields['ajuda']));
									$this->gOut("</div>");
								
								$this->gTableColEnd();
							$this->gTableRowEnd();
							
							$this->gTableEnd();
						$this->gOut("</div>");
		
						$sql="select * from geral_noticias where aprovada=1 and data_validade>='".date("Y-m-d h:n:s")."' and (tipo='".$rs->fields['nome']."' or tipo='Início') order by data_cadastro desc limit 8";
						$rs=gQuery($sql);

						if (!$rs->EOF)
						{
							$this->gTableBegin(gT_BIG,true);
							echo "<tr><td class='single' align='center' colspan='2'><b>Notícias</b></td></tr>";
							echo "<tr valign='top'><td class='single' width='80' align='center'>".$this->gImage("foto.png","","",false)."</td><td class='single'>";
							$this->gTableBegin(gT_BIG,true);
							$primeira=true;
							while (!$rs->EOF)
							{
								$ineg="";
								$fneg="";
								if (gDate($rs->fields['data_cadastro'])==gDate(date("Y-m-d")))
								{
									$ineg="<b>";
									$fneg="</b>";
								}
								$mais="";
								if (!$primeira)
									$mais="<a href='i00_uti/noticias.php?gAction=list&gId=".$rs->fields['id']."'>(mais...)</a>";
								$mtz="";
								$mtz[]="<-<acronym title='Por ".gFieldById("geral_pessoas",$rs->fields['id_geral_pessoas'],"nome")."'>$ineg".gDate($rs->fields['data_cadastro'])."$fneg</acronym>";
								$mtz[]="<>$ineg".$rs->fields['descricao']." $mais $fneg";
								$this->gTableRow($mtz,gT_GRIDDETAIL);
								if ($primeira)
								{
									$primeira=false;
									$mtz="";
									$mtz[]="";
									$mtz[]="<>".nl2br($rs->fields['detalhe']);
									$this->gTableRow($mtz,gT_GRIDDETAIL);
								}
								$rs->MoveNext();
							}
							if ($primeira)
							{
								$mtz="";
								$mtz[]="";
								$mtz[]="";
								$this->gTableRow($mtz,gT_GRIDDETAIL);
							}
							$this->gTableEnd();
							
							echo "</td></tr>";
							$this->gTableEnd();
							$this->gBr();
						}	
						if (($arq<>"") && (file_exists($gPath.gBAR."online".gBAR.$arq)) )
						{
							include $gPath."online".gBAR.$arq;
						}
						
						$this->gEnd();
					
					} else
					{
						header("location: $http_base"."/online/index.php");
					}
					
				}
				//$this->gOut("<iframe name='screen' src='index.php' width='100%' height='100%' frameborder='0' scrolling='auto'></iframe>");
			}
		} else
		{
			gExpire();
		}
		
		$this->gOut(gTag("menu.menu_end"));
// ************************
	}
}
?>
