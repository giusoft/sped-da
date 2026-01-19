<?
/* Definição de constantes */

define("gI_TEXT",0);		// Texto
define("gI_UTEXT",1);		// Texto em maiúsculas
define("gI_LTEXT",2);		// Texto em minúsculas
define("gI_EMAIL",3);		// Email
define("gI_NUM",4);		// Número
define("gI_DATE",5);		// Data
define("gI_DATENULL",6);	// Data permitindo em branco
define("gI_TIME",7);		// Hora
define("gI_TIMENULL",8);	// Hora permitindo em branco
define("gI_DATETIME",9);	// Data e hora no mesmo campo
define("gI_DATETIMENULL",10);	// Data e hora permitindo em branco
define("gI_CPF",11);		// CPF
define("gI_CNPJ",12);		// CNPJ
define("gI_PLATE",13);		// Placa de veículo
define("gI_PASSWORD",14);	// Senha
define("gI_UFTEXT",15);		// Texto com 1a letra em maiúscula
define("gI_UFWTEXT",16);	// Texto com 1a letra de cada palavra em maiúscula
define("gI_FILE",19);		// Utilizado para upload de arquivos
define("gI_SELECT",20);		// Caixa de seleção (select)
define("gI_SELECTNULL",21);	// Select permitindo << Indiferente >>
define("gI_MULTISELECT",22);	// Select de várias opções simultâneas
define("gI_MULTISELECTNULL",23);// Select múltiplo permitindo << Indiferente >>
define("gI_SELECTCODE",24);	// Select para digitação de código
define("gI_SELECTIMAGE",25);	// Select para imagens da tabela geral_arquivos
define("gI_RADIO",30);		// Radio button
define("gI_MEMO",40);		// Textarea
define("gI_SUPERMEMO",41);	// Textarea com ferramentas avançadas de edição de texto
define("gI_EDITOR",42);	// Editor de texto completo
define("gI_CHECK",50);		// Checkbox
define("gI_HIDDEN",100);	// Oculto
define("gI_EXCLUDE",101);	// Excluir
define("gI_SHOW",102);		// Mostrar o valor, sem permitir alterar
define("gI_READONLY",103);	// Mostrar o valor, sem permitir alterar e passa-o como parâmetro 
define("gI_NEW",104);		// Significa que os próximos campos terão o valor vazio (somente gPage.php)
define("gI_EDIT",105);		// Significa que os próximos campos terão o valor do banco de dados (somente gPage.php)
define("gI_DICT",106);		// Significa que este campo serve para tradução de nomes (somente gPage.php)
define("gI_DATEBETWEEN",200);	// Utilizado para selecionar duas datas no gForm
define("gI_DATETIMEBETWEEN",201);// Utilizado para selecionar duas datas (datetime)no gForm

define("gI_SAVE_NEW", 107);				// Significa que os dados novos deverão ser salvos
define("gI_SAVE_EDIT", 108);			// Significa que os dados atualizados deverão ser salvos
define("gI_FINALIZA", 109);				// Significa que chegou ao final do cadadstro

include $gPathDefault."gOutput.php";
include $gPathDefault."gDB.php";
include $gPathDefault."fckeditor/fckeditor.php";

/** Classe responsável pela geração de código HTML para objetos de entrada de dados
* @package	gInput
* @author	Giuliano Nascimento
* @version	2.0
*/
class gInput extends gOutput
{
	var $ajax=false;
	var $BaseSel=false;
	var $DateSel=false;
	var $CalcSel=false;
	function gBeginAjax($m)
	{
		global $gPathDefault;
		$this->ajax=true;
		if ($this->filter=="")
		{
			include $gPathDefault."sajax/Sajax.php";
			sajax_init();
			foreach ($m as $func)
			{
				sajax_export($func); // list of functions to export
			}
			sajax_handle_client_request(); // serve client instances
		}
		
	}
		
/** Gera saída HTML do calendário exibido nos formulários
* @author		Giuliano Nascimento
* @version	2.0
* @return		string Código HTML
*/
	function gDIVCalendar()
	{
		$s.="<div id='gDCalendar' style='position:absolute; left:-300px; top:-300px; width: 130px; height: 136px; padding: 2px;  cursor:help; z-index: 100'>";
		$s.="<span id='gMonth'></span>";
		$ante=date("d-m-y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		$data=date("d-m-y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		$prox=date("d-m-y", mktime(0, 0, 0, date("m")+1, date("d"), date("Y")));
		$s.="<span id='gMonth1' style='display: none'>".$this->gCalendar("elDate",0,substr($ante,3,2),substr($ante,6,2),"","gMonth2")."</span>";
		$s.="<span id='gMonth2' style='display: none'>".$this->gCalendar("elDate",substr($data,0,2),substr($data,3,2),substr($data,6,2),"gMonth1","gMonth3")."</span>";
		$s.="<span id='gMonth3' style='display: none'>".$this->gCalendar("elDate",0,substr($prox,3,2),substr($prox,6,2),"gMonth2","")."</span>";
		$s.="</div>";
		return ($s);
	}

/** Gera saída HTML da calculadora exibida nos formulários
* @author Giuliano Nascimento
* @version 2.0
* @return string Código HTML
*/
	function gDIVCalculator()
	{
		$s.="<div id='gDCalc' style='position:absolute; left:-300px; top:0px; width: 114px; height: 200px; padding: 2px;  cursor:help; z-index: 100 '>";
		
		$s.="<table style='background: #707070'><tr><td class='single' align='center' colspan='4' style='color: white'>Calculadora</td></tr>";
		$s.="<tr><td class='single' colspan='4'><input type='text' id='gCalcDisplay' class='calcDisplay' maxlength='120' onKeyPress='return gCalcKeyCheck(this,event,\"\")'></td></tr>";
		
		$s.="<tr>";
		$s.="<td class='single' colspan='3'><input type='button' id='gCalcDoBtn' value='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;=&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' class='calcDo' onClick='gCalcDo()'></td>";
		$s.="<td class='single'><input type='button' value='C' class='calcBtn' onClick='gCalcClear()'></td>";
		$s.="</tr><tr>";
		$s.="<td class='single'><input type='button' value='7' class='calcBtn' onClick='gCalcPut(7)'></td>";
		$s.="<td class='single'><input type='button' value='8' class='calcBtn' onClick='gCalcPut(8)'></td>";
		$s.="<td class='single'><input type='button' value='9' class='calcBtn' onClick='gCalcPut(9)'></td>";
		$s.="<td class='single'><input type='button' value='+' class='calcBtn' onClick='gCalcPut(\"+\")'></td>";
		$s.="</tr><tr>";
		$s.="<td class='single'><input type='button' value='4' class='calcBtn' onClick='gCalcPut(4)'></td>";
		$s.="<td class='single'><input type='button' value='5' class='calcBtn' onClick='gCalcPut(5)'></td>";
		$s.="<td class='single'><input type='button' value='6' class='calcBtn' onClick='gCalcPut(6)'></td>";
		$s.="<td class='single'><input type='button' value='-' class='calcBtn' onClick='gCalcPut(\"-\")'></td>";
		$s.="</tr><tr>";
		$s.="<td class='single'><input type='button' value='1' class='calcBtn' onClick='gCalcPut(1)'></td>";
		$s.="<td class='single'><input type='button' value='2' class='calcBtn' onClick='gCalcPut(2)'></td>";
		$s.="<td class='single'><input type='button' value='3' class='calcBtn' onClick='gCalcPut(3)'></td>";
		$s.="<td class='single'><input type='button' value='*' class='calcBtn' onClick='gCalcPut(\"*\")'></td>";
		$s.="</tr><tr>";
		$s.="<td class='single'><input type='button' value='0' class='calcBtn' onClick='gCalcPut(0)'></td>";
		$s.="<td class='single'><input type='button' value=',' class='calcBtn' onClick='gCalcPut(\",\")'></td>";
		$s.="<td class='single'><input type='button' value='^' class='calcBtn' onClick='gCalcPut(\"^\")'></td>";
		$s.="<td class='single'><input type='button' value='/' class='calcBtn' onClick='gCalcPut(\"+\")'></td>";
		$s.="</tr>";
		$s.="</table>";
		
		$s.="</div>";
		return ($s);
	}

/** Gera saída HTML de código javascript necessário para várias funções
* @author Giuliano Nascimento
* @version 2.0
* @return string Código HTML
*/
	function gBeginJS()
	{
		global $gPathDefault;
		global $http_inc;
		$this->gOut("<script language='javascript' src='$http_inc/gFunctions.js'></script>");
		$out="<script language='javascript'>\n";
		$out.="function selectpop(parametros,campo,tipo)\n{";
		$janela="/".gBASE."/inc_2.0/gSelectWindow.php";
		$janela=str_replace("//","/",$janela);
		$out.="if (tipo=='selectcode') {w=520;h=420;} else {w=780;h=580;}";
		$out.="janela=window.open(\"$janela?gFormField=\"+campo+\"&gType=\"+tipo+\"&gSql=\"+parametros,\"\",\"left=200,top=200,width=\"+w+\",height=\"+h+\",scrollbars=yes,status=yes\");\n";
		$out.="text = \"Desabilite seu bloqueador de pop-ups!\";\n";
		$out.="if(janela == null) \n{\n alert(text); \n return;\n }\n";
		$out.="}";		
		$this->gOut($out);
		
		if (($this->ajax) && ($this->_getFilter()==""))
			sajax_show_javascript();
		$out="</script>";
		
		// Calendário
		$out.=$this->gDIVCalendar();
		
		// Calculadora
		$out.=$this->gDIVCalculator();
		$this->gOut($out.$s);
	}

/** Gera código Ajax para atualização dinâmica dos campos
* @author Giuliano Nascimento
* @version 2.5
* @param string $param ajax|evento|função|destino do resultado[|outro campo do formulário/parâmetro para a função]... => Exemplo: ajax|onChange|gAjaxCalc|valor|id_geral_indices_tipos
*/
	function gBuildAjax($param)
	{
		if (substr($param,0,4)=="ajax")
		{
			$p=explode("|",$param);
			if ($p[2]=="gAjaxCalc")
			{
				$param=$p[1]."=\"".$p[2]."_".$p[3]."(this";
				$param.=")\"";
				$out="<script>";
				$out.="function do_gAjaxCalc_".$p[3]."(result) {window.document.forms[0].".$p[3].".value = result;}";
				$out.="function gAjaxCalc_".$p[3]."(org) {";
				$out.="x_gAjaxCalc_".$p[3]."(org.value,";
				if (count($p)>4)
				{
					for ($a=4; $a<count($p); $a++)
						$out.="window.document.forms[0].".$p[$a].".value,";
				}
				$out.="do_gAjaxCalc_".$p[3].");}";
				$out.="</script>";
			} else
			{
				$param=$p[1]."=\"".$p[2]."(this";
				$param.=")\"";
			}
			$this->gOut($out);
		}
		$param=" ".$param;
		return $param;
	}

/** Desenha um calendário HTML
* @author Giuliano Nascimento
* @version 2.0
* @param string $pag Página ou elemento que será colocado o valor
* @param string $di Dia
* @param string $m  Mês
* @param string $an Ano
* @param string $link_esq Id da Tag span com o conteúdo ao clicar na seta esquerda
* @param string $link_dir Id da Tag span com o conteúdo ao clicar na seta direita
* @return string Código HTML
*/
	function gCalendar($pag="",$di=0, $m=0, $an=0, $lnk_esq, $lnk_dir) 
	{
	if ($di==0) $di=date("j");
	if ($m==0) $m=date("n");
	if ($an==0) $an=date("Y");
	$cr = "\n";

	$meses= array (1 => array ("Janeiro","Fevereiro","Março","Abril","Maio","Junho",
									"Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"),
									2 => array (31,28,31,30,31,30,31,31,30,31,30,31));
	$mes = $m;
	$dia = $di;
	$s="";
	$s.="<table class='calendar' align='center'>";
	$s.="<tr>";
	if ($lnk_esq!="")
		$s.="<td width='15%' class=\"cal\" align='center'><a href='#' class='setas' onClick='gCalMonth(\"$lnk_esq\")'><</a></td>";
	else
		$s.="<td width='15%' class=\"cal\" >&nbsp;&nbsp;</td>";
	$s.="<td width='70%' align='center' class=\"cal\" colspan=5>" ;
	$s.=$meses[1][$mes-1];
	$s.="</td>" ;
	if ($lnk_dir!="")
		$s.="<td width='15%' class=\"cal\" align='center'><a href='#' class='setas' onClick='gCalMonth(\"$lnk_dir\")'>></a></td>";
	else
		$s.="<td width='15%' class=\"cal\" >&nbsp;&nbsp;</td>";
	$s.="</tr>" ;
	$s.="<th class=\"dias\">D</th><th class=\"dias\">S</th><th class=\"dias\">T</th><th class=\"dias\">Q</th><th class=\"dias\">Q</th><th class=\"dias\">S</th><th class=\"dias\">S</th></tr>" ;
	$data=mktime(0,0,0,$mes,1,$an);
	For ($i=1; $i<=6 ; $i++)
	{
	  $s = $s . "<tr>";
		For ( $c=0; $c<=6; $c++)
		{
			$class="num";
			if ( ($di == date("j",$data))  && ( date("w",$data) == $c) && ( date("n",$data)== $mes) )
			{
				$class="num_sel";
				$s = $s . "<td class=\"$class\" align='center'>" ;
			} else
			{
				if ( $c == 0 )
			  	{
			 		if ( $Tipo == 1 )
						$s = $s . "<td class=\"num\" align='center' bgcolor='#aefaab'>" ;
			   	else
			         $s = $s . "<td class=\"num\" align='center' bgcolor='#aecbfa'>" ;
				} else
					$s = $s . "<td class=\"num\" align='center'>" ;
	 	   }
			if (date("j",$data)  < 7 )
			{
				if ( ( date("w",$data)  == $c ) && ( date("n",$data)  == $mes ) )
				{
					if ($pag<>"")
					{
						 if (strpos($pag,".")>0)
						 {
					    	$s = $s . "<a target='screen' class='$class' href=". $pag . "?data=" . date("d",$data)  . "-" . date("m",$data)  . "-" . date("y",$data)  . ">" . date("j",$data)  . "</a>";
					    } else
					    {
					    	$s = $s . "<a target='screen' class='$class' href='#' onClick='gChangeDate($pag,\"" . date("d",$data)  . "-" . date("m",$data)  . "-" . date("y",$data)  . "\")'>" . date("j",$data)  . "</a>";
					    }
				   } else
				   {
					    $s = $s . date("j",$data) ;
					}
				    $data=mktime(0,0,0,date("n",$data) ,date("j",$data) +1,date("y",$data) );
				}
				else {
					$s = $s . "&nbsp;";
				}
			} else
			{
				If ( date("n",$data) == $mes )
				{
					if ($pag <> "")
				 	{
						 if (strpos($pag,".")>0)
						 {
					 		$s = $s . "<a target='screen' class='$class' href=". $pag . "?data=" . date("d",$data)  . "-" . date("m",$data)  . "-" . date("y",$data)  . ">" . date("j",$data)  . "</a>";
					    } else
					    {
					    	$s = $s . "<a target='screen' class='$class' href='#' onClick='gChangeDate($pag,\"" . date("d",$data)  . "-" . date("m",$data)  . "-" . date("y",$data)  . "\")'>" . date("j",$data)  . "</a>";
					    }
					} else
					{
						$s = $s . date("j",$data) ;
					}
	 		      $data=mktime(0,0,0,date("n",$data) ,date("j",$data) +1,date("y",$data) );
				}
	      }
			$s = $s  . "</td>";
		}//end do for
		$s = $s . "</tr>";
	}//end for 2
	$s.="</table>";
	return($s);
	}

/** Gera Javascript para detecção da posição do mouse
* @author Giuliano Nascimento
* @version 2.0
* @return string Código Javascript
*/
	function gJSMousePos()
	{
		$sai="";
		if (!$this->BaseSel)
		{
			$sai.="var posx=0; var posy=0;";
			$sai.="ns = document.layers;ie = document.all;ns6 = (document.getElementById && !document.all);\n";
  			$sai.="function moveMouse(e){\n";
			$sai.="if (document.documentElement && document.documentElement.scrollTop)\n";
			$sai.="	theTop = document.documentElement.scrollTop;\n";
			$sai.="else if (document.body)\n";
			$sai.="	theTop = document.body.scrollTop;\n";
			$sai.="else \n";
			$sai.="	theTop = document.documentElement.scrollTop;\n";
	         $sai.="if(ie){posx = event.clientX;posy = event.clientY;\n";
         $sai.="} else if (ns){posx = e.x;posy = e.y;\n";
         $sai.="} else if (ns6){posx = e.clientX;posy = e.clientY;}posy=posy+theTop;}\n";
			$sai.="document.onmousemove = moveMouse;\n";
		}
		$this->BaseSel=true;
		return ($sai);
	}

/** Gera caixa de seleção de data
* @author Giuliano Nascimento
* @version 2.0
* @param string $el Nome do elemento HTML onde a data será colocada
* @return string Código HTML
*/
	function gDateSelect($el,$data="")
	{
		$s="";
		if (!$this->DateSel)
		{
			$this->DateSel=true;
			$s="<script language='javascript'>";
			$s.="var elDate='$el';\n";
			
			$s.=$this->gJSMousePos();			
			
			$s.="function gCalMonth(vl)\n{\n";
			$s.="document.getElementById('gMonth').innerHTML=document.getElementById(vl).innerHTML;";
			$s.="}\n";			
			$s.="function gChangeDate(el,vl)\n{\n";
			$s.="document.getElementById(el).value = vl;\n";
			$s.="document.getElementById('gDCalendar').style.top='-500px';\n";
			$s.="document.getElementById('gDCalendar').style.display='none';\n";
			$s.="document.getElementById(el).focus();";
			$s.="\n}\n";
			$s.="function gOpenCalendar(el,date)\n{\n";
			$s.="if (document.getElementById('gDCalendar').style.display!='block'){\n";
			$s.="document.getElementById('gDCalendar').style.left=(posx+12)+'px';\n";
			$s.="document.getElementById('gDCalendar').style.top=(posy-5)+'px';\n";
			$s.="document.getElementById('gDCalendar').style.display='block';\n";
			$s.="elDate=el;\n";
			$s.="document.getElementById('gMonth').innerHTML=document.getElementById('gMonth2').innerHTML;";
			$s.="//document.getElementById(divel).innerHTML=document.getElementById('gDCalendar').innerHTML;\n";
			$s.="//document.getElementById(divel).style.display='block';\n";
			$s.="} else {\n";
			$s.="document.getElementById('gDCalendar').style.top='-500px';\n";
			$s.="document.getElementById('gDCalendar').style.display='none';\n";
			$s.="document.getElementById(el).focus();";
			$s.="}\n}\n";
			$s.="</script>";
		}
		$s.="<a href='#' class='imagem' onClick='gOpenCalendar(\"$el\",\"$data\")'>".$this->gImage("calen.gif","border='0'","",false)."</a>";
		return($s);
	}

/** Gera calculadora para números
* @author Giuliano Nascimento
* @version 2.0
* @param string $el Nome do elemento HTML onde a data será colocada
* @return string Código HTML
*/
	function gCalcSelect($el,$data="")
	{
		//return;
		$s="";
		if (!$this->CalcSel)
		{
			$this->CalcSel=true;
			$s="<script>";
			$s.="var elCalc='$el';\n";
			
			$s.=$this->gJSMousePos();			
			
			$s.="function gCalcPut(vl)\n{\n";
			$s.="document.getElementById('gCalcDisplay').value=document.getElementById('gCalcDisplay').value+vl;\n";
			$s.="\n}\n";
			$s.="function gCalcClear(vl)\n{\n";
			$s.="document.getElementById('gCalcDisplay').value='';\n";
			$s.="\n}\n";
			$s.="function gCalcDo(vl)\n{\n";
			$s.="var gVDisplay=document.getElementById('gCalcDisplay').value;\n"; 
			$s.="gVDisplay=gVDisplay.replace(\",\",\".\");\n";
			$s.="if (gVDisplay=='') gVDisplay='0';\n";
			$s.="ttl=eval(gVDisplay);ttl=Math.round( ttl* Math.pow( 10 , 2) ) / Math.pow( 10 , 2) ;\n";
			$s.="ttl=ttl.toString();\n";
			$s.="ttl=ttl.replace(\".\",\",\");\n";
			$s.="if (ttl.indexOf(\",\")>-1) {	if (ttl.length-ttl.indexOf(\",\")==2)	{ ttl=ttl+\"0\";	} } else	{ ttl=ttl+\",00\"; }\n";
			$s.="ttl=ttl.replace(\",00\",\"\");\n";
			$s.="document.getElementById(elCalc).value=ttl;\n";
			$s.="document.getElementById('gDCalc').style.top='-500px';\n";
			$s.="document.getElementById('gDCalc').style.display='none';\n";
			$s.="document.getElementById(elCalc).focus();";
			$s.="\n}\n";
			$s.="function gOpenCalc(el,date)\n{\n";
			$s.="if (document.getElementById('gDCalc').style.display!='block'){\n";
			$s.="document.getElementById('gDCalc').style.left=(posx+8)+'px';\n";
			$s.="document.getElementById('gDCalc').style.top=(posy-5)+'px';\n";
			$s.="document.getElementById('gDCalc').style.display='block';\n";
			$s.="document.getElementById('gCalcDisplay').value='';\n";
			$s.="document.getElementById('gCalcDisplay').focus();\n";
			$s.="elCalc=el;\n";
			$s.="} else {\n";
			$s.="document.getElementById('gDCalc').style.top='-500px';\n";
			$s.="document.getElementById('gDCalc').style.display='none';\n";
			$s.="document.getElementById(el).focus();";
			$s.="}\n}\n";
			$s.="</script>";
		}
		$s.="<a href='#' class='imagem' onClick='gOpenCalc(\"$el\",\"$data\")'>".$this->gImage("calc.gif","border='0'","",false)."</a>";
		return($s);
	}
	
/** Monta um link para outra página em formato de botão
* @author 	Giuliano Nascimento
* @version 	2.0
* @param 	string	$text					Texto referente a uma tag no gConf.xml.
* @param 	string	$link					Caminho da página referente ao link.
* @param 	string	$hint					Dica
* @param 	bool		$exit_onpage	Saída na tela (true) ou retorna na função como string (false)
* @return	string	Código HTML
*/
	function gButton($text,$link,$hint="",$exit_on_page=true)
	{
		$_pre="";$_pos="";$_target="";
		if ($hint<>"")
		{
			$_pre="<acronym title='".gLng($hint)."'>";
			$_pos="</acronym>";
		}
		$s="$_pre<input type=\"button\" onClick=\"javascript:window.location.href='$link';\" value='".gLng($text)."'>$_pos";
		if ($exit_on_page)
			$this->gOut($s);
		else
			return($s);
	}

/** Gera uma mensagem formatada e alguns botões.
* @author Giuliano Nascimento
* @param string $caption Título da caixa de mensagem
* @param string $text Texto detalhando a caixa de mensagem
* @param array $buttons Array de botões a serem exibidos. Cada botão: "Nome|n" onde n=páginas a voltar (-1)
* @param bool $alert Se verdadeiro, exibe $text em vermelho
*/
function gMsgBox($caption, $text,$buttons, $alert=false)
	{
  
		global $gDebug;
		global $cr;
		if ($gDebug>0) $this->gOut($cr);
		$this->gMsgTitle($caption);
		$this->gMsg($text,0,$alert);$this->gBr();
		if ($gDebug>0) $this->gOut($cr);
		if (!is_array($buttons))
			$buttons=array($buttons);
		if (count($buttons)>0)
		{
			for ($a=0; $a<count($buttons); $a++)
			{
				$sButton=$buttons[$a];
				// Se não for um array, considera que o botão é "Confirmar" e o button = a página para ser redirecionado
				$sAct=substr($sButton,strpos($sButton,"|")+1);
				$sTxt=(substr($sButton,0,strpos($sButton,"|")));
				if (gLng(strtolower($sTxt). ".short")<>strtolower($sTxt). ".short")
					$sTxt=gLng(strtolower($sTxt). ".short");
					$sTxt=gLng($sTxt);
				if (substr($sAct,0,1)=="-")
					$this->gOut("<input type=button value='$sTxt' onclick='javascript:history.go($sAct)'>");
				else
					$this->gOut("<input type=button value='$sTxt' onclick=\"window.location.href='$sAct'\">");
				$this->gSpc(1);
			}
		}
		if ($gDebug>0) $this->gOut($cr);
	}

/** Gera um <INPUT TYPE> lendo informações do BD e incluindo validações Javascript automaticamente
* @author Giuliano Nascimento
* @param string $name Nome do objeto ou um array de 2 elementos contendo o "label" e o nome;
* @param string 	$value 			Valor padrão ou um "recordset" onde o nome do campo do banco é igual a $name
* @param string		$style				Estilo do objeto gerado, podendo ser qualquer dos citados na declaração de constantes do início desta página
* @param Integer	$maxlength		Tamanho máximo da caixa de texto
* @param string		$param				Algum parâmetro adicional (onClick, onMouseOver, ...)
* @param bool	$exit_onpage	Saída na tela (true) ou retorna na função como string (false)
* @return string	$out
*/
function gText($name,$value="",$style=gI_TEXT,$maxlength=60,$param="",$exit_on_page=true)
{
	global $gDebug;
	global $cr;

	$param=$this->gBuildAjax($param);
	$label="";
	$poscode="";
	if (count($name)>1)
	{
		$tmp=$name;
		$label=$tmp[0];
		$name=$tmp[1];
	}
	$type="text";
		$size=$maxlength;
	$out="";
	if ($label<>"") $out=$label."&nbsp;";

	if (is_object($value))
	{
		$field=$name;
		$rs=$value;
		$g_fld=$rs->FetchField($field);
		$fldtype="C";
		$g_maxlength=-1;
		for ($f=0;$f<$rs->FieldCount();$f++)
		{
		$fld=$rs->FetchField($f);
		if ($fld->name==$field)
		{
			$fldtype=$rs->MetaType($rs->FetchField($f));
			$g_maxlength=$fld->max_length;
		}
		}
		$value=$rs->fields["$field"] ;
		$g_type=gI_TEXT;
		if (($fldtype=="I") || ($fldtype=="N") || ($fldtype=="R"))
		{
			$g_type=gI_NUM;
			$g_maxlength=20;
		}
		if ($fldtype=="D")
		{
			$g_type=gI_DATE;
			$g_maxlength=10;
			$value=gDate($value);
		}
		if ($fldtype=="T")
		{
			$g_type=gI_DATETIME;
			$g_maxlength=19;
			$value=gDateTime($value);
		}
		if ($fldtype=="X")
		{
			$g_type=gI_MEMO;
			$g_maxlength=2000;
		}
		if ($fldtype=="B")
		{
			$g_type=gI_SUPERMEMO;
			$g_maxlength=4000;
		}
		if ($fldtype=="T")
		{
			$g_type=gI_TIME;
			$g_maxlength=5;
		}
		if ((strtoupper($field)==strtoupper(gLng("password.short"))) || (strtoupper($field)=="PASSWORD"))
		$g_type=gI_PASSWORD;
		if (strtoupper($field)==strtoupper(gLng("email.short")))
		$g_type=gI_EMAIL;
		if (strtoupper($field)==strtoupper(gLng("plate.short")))
		$g_type=gI_PLATE;
		// Only for Brazil...
		if ((strtoupper($field)=="CNPJ") || (strtoupper($field)=="CGC"))
		$g_type=gI_CNPJ;
		if (strtoupper($field)=="CPF")
		$g_type=gI_CPF;
		// ... End
		if ($style==gI_TEXT) $style=$g_type;
		if ($g_maxlength>0) $maxlength=$g_maxlength;
	}
		if ($maxlength>60) { $size="60"; }
		if ($style==gI_LTEXT)
		{
			if (strpos(strtolower($param),"onblur")===false)
				$param=" onBlur='vLText(this);' ".$param;
		} elseif ($style==gI_PASSWORD)
		{
			$type="password";
			$size="18" ;
		} elseif ($style==gI_UTEXT)
		{
			if (strpos(strtolower($param),"onblur")===false)
				$param=" onBlur='vUText(this);' ".$param;
		} elseif ($style==gI_UFTEXT)
		{
			if (strpos(strtolower($param),"onblur")===false)
				$param=" onBlur='vUFText(this);' ".$param;
		} elseif ($style==gI_UFWTEXT)
		{
			if (strpos(strtolower($param),"onblur")===false)
				$param=" onBlur='vUFWText(this);' ".$param;
		} elseif ($style==gI_NUM)
		{
			$poscode=$this->gCalcSelect($name);
			if ($maxlength>15) { $size="15"; }
			if (strpos(strtolower($param),"onkeypress")===false)
				$param=" onkeypress=\"return gNumKeyCheck(this,event,'".gVar("global.numformat")."')\" ".$param;
		} elseif ($style==gI_EMAIL)
		{
			$maxlength="50";
			$size="35";
			$param="".$param;
			$param=" onBlur='vEmail(this);' ".$param;  
		} elseif ($style==gI_DATE)
		{
			$poscode=$this->gDateSelect($name);
			$maxlength=strlen(gVar("global.dateformat"));
			$size="12";
			if (strpos(strtolower($param),"onkeypress")===false)
				$param.=" onkeypress=\"return gDateKeyCheck(this,event,'".gVar("global.dateformat")."')\"";
			if (strpos(strtolower($param),"onblur")===false)
				$param.="onBlur=\"gDateVerify(this,'".gVar("global.dateformat")."',false,'".gLng("invalid_date")."');\" ";
		} elseif ($style==gI_DATENULL)
		{
			$poscode=$this->gDateSelect($name);
			$maxlength=strlen(gVar("global.dateformat"));
			$size="12";
			if (strpos(strtolower($param),"onkeypress")===false)
				$param.=" onkeypress=\"return gDateKeyCheck(this,event,'".gVar("global.dateformat")."')\" ";
			if (strpos(strtolower($param),"onblur")===false)
				$param.=" onBlur=\"gDateVerify(this,'".gVar("global.dateformat")."',true,'".gLng("invalid_date")."');\" ";
		} elseif ($style==gI_DATETIME)
		{
			$poscode=$this->gDateSelect($name);
			$maxlength=strlen(gVar("global.dateformat"))+9;
			$size="20";
			if (strpos(strtolower($param),"onkeypress")===false)
				$param.=" onkeypress=\"return gDateTimeKeyCheck(this,event,'".gVar("global.dateformat")."')\" ";
			if (strpos(strtolower($param),"onblur")===false)
				$param.=" onBlur=\"gDateTimeVerify(this,'".gVar("global.dateformat")."',false,'".gLng("invalid_date.long")."');\" ";
		} elseif ($style==gI_DATETIMENULL)
		{
			$poscode=$this->gDateSelect($name);
			$maxlength=strlen(gVar("global.dateformat"))+9;
			$size="20";
			if (strpos(strtolower($param),"onkeypress")===false)
				$param.=" onkeypress=\"return gDateTimeKeyCheck(this,event,'".gVar("global.dateformat")."')\" ";
			if (strpos(strtolower($param),"onblur")===false)
				$param.=" onBlur=\"gDateTimeVerify(this,'".gVar("global.dateformat")."',true,'".gLng("invalid_date")."');\" ";
		} elseif ($style==gI_TIME)
		{
			$maxlength="5";
			$size="7";
			if (strpos(strtolower($param),"onkeypress")===false)
				$param=" onkeypress=\"return gTimeKeyCheck(this,event)\" onBlur='vTime(this);' ".$param;
		} elseif ($style==gI_TIMENULL)
		{
			$maxlength="5";
			$size="7";
			if (strpos(strtolower($param),"onkeypress")===false)
				$param=" onkeypress=\"return gTimeKeyCheck(this,event)\" onBlur='vTimeNull(this);' ".$param;
		} elseif ($style==gI_CPF)
		{
			$param=" onBlur='vCPF(this);' ".$param;
		} elseif ($style==gI_CNPJ)
		{
			$param=" onBlur='vCNPJ(this);' ".$param;
		} elseif ($style==gI_PLATE)
		{
			$maxlength="7";
			$size="9";
			$param=" onBlur='vPlate(this);' ".$param;
		}
		if ($style==gI_FILE)
		{
			$out.="<input type='hidden' name='MAX_FILE_SIZE' value='3000000'>";
			$out.="<input type='file' id='$name' name='$name' value='$value' size='$size' onfocus='this.select()' maxlength='$maxlength'$param>";
		} else
		{
			$out.="<input type='$type' id='$name' name='$name' value='$value' size='$size' onfocus='this.select()' maxlength='$maxlength'$param> $poscode";
			if ($type=="password")
			{
				$out.="&nbsp;&nbsp;Confirmação <input type='$type' name='_confirmacao_".$name."' value='$value' size='$size' onfocus='this.select()' maxlength='$maxlength'$param>";
			}
		}
		if ($exit_on_page)
		{
			if ($gDebug>0) $this->gOut($cr);
			$this->gOut($out);
		} 
		return ($out);
	}

/**Gera um <SELECT>
@author Giuliano Nascimento, Cassiano Guimarães
@param string $name       = Nome do objeto ou um array de 2 elementos contendo o "label" e o nome;
@param array $lista      = Array contendo a lista de elementos. Se for bi-dimensional, o ID será a 1ª e o valor a 2ª. Pode ser um "recordset"
@param string $selected   = Valor padrão (pode ser a posição na lista,o ID ou o valor) ou um "recordset" onde o nome do campo do banco é igual a $name
@param string $style      = Estilo do objeto gerado, podendo ser qualquer dos citados na declaração de constantes do início desta página
@param string $param      = Algum parâmetro adicional (onClick, onMouseOver, ...)
@return string $out
*/
function gSelect($name,$list,$selected="",$style=gI_SELECT,$param="",$exit_on_page=true)
{
	
	global $gDebug;
	global $cr;
	global $gPathImg;
	$typeSelect="";
	$cr_s="";
	$label="";
	$maxrows=gVar("database.maxrows")*5;
	$param=$this->gBuildAjax($param);
	if (count($name)>1)
	{
		$tmp=$name;
		$label=$tmp[0];
		$name=$tmp[1];
	}
	if (is_object($selected))
	{
		$selected=$selected->fields["$name"];
	}

	if ($gDebug>0) $cr_s=$cr;
	if (count($list)==1)
	{
		if (substr(strtoupper($list),0,7)=="SELECT ")
		{
			$qtmp=$list;
			$rstmp=gQuery($qtmp,gD_DEFAULT,1);

			$slist="";
			$cnt=0;
			while (!($rstmp->EOF))
			{
				if ($rstmp->FieldCount()>1)
				{
					$slist[]=array($rstmp->fields[0],$rstmp->fields[1]);
				} else
				{
					$slist[]=$rstmp->fields[0];
				}
				$rstmp->MoveNext();
				$cnt++;
			}
			if (($cnt<$maxrows) && ($style==gI_SELECTCODE))
			{
				$style=gI_SELECTNULL;
			} 

		}
	}
	if (($style==gI_SELECTCODE) || ($style==gI_SELECTIMAGE))
	{
		$size=10;
		$xlist=str_replace("'","$",$list);
		$nome_botao="...";
		if ($selected<>"")
		{
			$sql=$list;
			$table=substr($sql,strpos(strtolower($sql)," from ")+6);
			$relac=false;
			if (strpos($table," ")>0)
			{
				$table=substr($table,0,strpos($table," "));
			} elseif (strpos($table,",")>0)
			{
				$table=substr($table,0,strpos($table,","));
				$relac=true;
			}
			$table.=".";
			if (strpos(strtolower($sql)," where ")>0)
			{
				$pnt=strpos(strtolower($sql)," where ")+7;
				if ((strpos(strtolower($sql)," join ")>0) || $relac)
				{
					$where=$table."id=$selected and ";
				} else
				{
					$sql=substr($sql,0,$pnt);
					$where=$table."id=$selected";
				}
			} else
			{
				$pnt=strpos(strtolower($sql)," order ");
				if ($pnt==0) $pnt=strlen($sql);
				$where=" where ".$table."id=$selected";
			}
			$sql=substr($sql,0,$pnt).$where.substr($sql,$pnt);
			$rstmp=gQuery($sql,gD_DEFAULT,1);
			$nome_botao=$rstmp->fields[1];
		}
		if ($nome_botao=='...') $nome_botao='';
		if (strpos(strtolower($param),"onkeypress")===false)
			$param.=" onkeypress=\"return gNumKeyCheck(this,event,'".gVar("global.numformat")."')\" ";
		if (strpos(strtolower($param),"onblur")===false)
			$param.=" onBlur=\"gNumVerify(this,'".gVar("global.numformat")."',false,'".gLng("errors.num.long")."');\" ";
		if ($style==gI_SELECTCODE)
			$stipo="selectcode";
		else
			$stipo="selectimage";
		if (file_exists($gPathImg."/lupa.gif"))
			$out.="<input type='text' id='$name' name='$name' value='$selected' size='$size' onfocus='this.select()' maxlength='$maxlength'$param>&nbsp;<a href='#' class='imagem' name=\"b$name\" onClick=\"selectpop('$xlist','$name','$stipo');\">".$this->gImage("lupa.gif","border='0'","",false)."</a>&nbsp;<span id=\"".$name."_txt\"><i>".$nome_botao."</i></span>";
		else
			$out.="<input type='text' id='$name' name='$name' value='$selected' size='$size' onfocus='this.select()' maxlength='$maxlength'$param>&nbsp;<input type=\"button\" name=\"b$name\" value=\"...\" onClick=\"selectpop('$xlist','$name','$stipo');\">&nbsp;<span id=\"".$name."_txt\"><i>".$nome_botao."</i></span>";
	} else
	{
		if ($style>21)
		{
			$typeSelect = " multiple";
		}
		if ($gDebug>0) $this->gOut($cr);
		$out="";
		if ($label<>"") $out=$label."&nbsp;";
		if (($style==gI_MULTISELECTNULL) ||($style==gI_MULTISELECT))
			$out.="<select size='6' id='".$name."[]' name='".$name."[]'".$typeSelect."$param>".$cr_s;
		else
			$out.="<select id='$name' name='$name'".$typeSelect."$param>".$cr_s;
		if (($style==gI_SELECTNULL) || ($style==gI_MULTISELECTNULL))
		{
			$out.="<option value='".gLng("gselect.short")."'>".gLng("gselect.long")."</option>".$cr_s;
		}
		
		if (count($list)==1) $list=$slist;
		for ($f=0; $f<count($list); $f++)
		{
			$sel="";
			$id=$list[$f];
			$value=$list[$f];
			if (count($id)>1)
			{
				$id=$value[0];
				$value=$value[1];
			}
			if (count($selected)>1)
			{
				for ($g=0; $g<count($selected);$g++)
				{
					if ((is_numeric($selected[$g])) && (!(is_numeric($id))) && ($selected[$g]==$f)) {$sel=" selected";}
					if (($selected[$g]==$id) || ($selected[$g]==$value)) {$sel=" selected";}
				}
			} else
			{
				if ((is_numeric($selected)) && (!(is_numeric($id))) && ($selected==$f)) {$sel=" selected";}
				if (($selected==$id) || ($selected==$value)) {$sel=" selected";}
			}
			if ($value<>"")
			$out.="<option value='".$id."'$sel>".$value."</option>".$cr_s;
		}
		$out.="</select>".$cr_s;
	}
	if ($exit_on_page)
	{
		$this->gOut($out);
	}
	return ($out);
	}

/**Gera um objeto HTML <INPUT TYPE='radio'>
@author Cassiano Guimarães, Giuliano Nascimento
@param string $name       = Nome do objeto ou um array de 2 elementos contendo o "label" e o nome;
@param array $lista      = Array contendo a lista de elementos. Se for bi-dimensional, o ID será a 1ª e o valor a 2ª. Pode ser um "recordset"
@param string $selected   = Valor padrão (pode ser a posição na lista, o ID ou o valor) ou um "recordset" onde o nome do campo do banco é igual a $name
@param string $param      = Algum parâmetro adicional (onClick, onMouseOver, ...)
@return string $out
*/
function gRadio($name,$list,$selected="null",$param="",$exit_on_page=true)
{
	
	//função que fornece a criação do botão radio sendo passado os valores que eles irão assumir. Os radios existentes possuem o mesmo nome.
		global $gDebug;
		global $cr;

		$label="";
		if (count($name)>1)
		{
			$tmp=$name;
			$label=$tmp[0];
			$name=$tmp[1];
		}
		$out="";
		if ($label<>"") $out=$label."&nbsp;";
		for ($f=0; $f<count($list) ; $f++)
		{
			$id=$list[$f];
			$value=$list[$f];
			if (count($id)>1)
			{
			$id=$value[0];
			$value=$value[1];
			}
			$sel="";
			if (is_object($selected))
			{
			$selected=$selected->fields["$name"];
			}
			if ((is_numeric($selected)) && (!(is_numeric($id))) && ($f==$selected)) {$sel=" checked";}
			if (($selected==$id) || ($selected==$value)) {$sel=" checked";}
			$out.="<input type='radio' name='$name' value='".$id."'$sel>".$value." &nbsp;";
		}
			if ($gDebug>0) $this->gOut($cr);
		if ($exit_on_page)
		{
			$this->gOut($out);
		}
		return ($out);
	}

/**Gera um objeto HTML <INPUT TYPE='checkbox'>
@author Cassiano Guimarães, Giuliano Nascimento
@param string $name       = Nome do objeto ou um array de 2 elementos contendo o "label" e o nome;
@param string $value      = Valor padrão (pode ser "true" ou "false") ou um "recordset" onde o nome do campo do banco é igual a $name
@param string $param      = Algum parâmetro adicional (onClick, onMouseOver, ...)
@return string $out
*/
function gCheck($name,$value=false,$param="",$exit_on_page=true)
{
	
		global $gDebug;
		global $cr;

			if ($gDebug>0) $this->gOut($cr);
		$label="";
		if (count($name)>1)
		{
			$tmp=$name;
			$label=$tmp[0];
			$name=$tmp[1];
		}
		$out="";
		if (is_object($value))
		{
			$value=$value->fields["$name"];
		}
		if ($value==true)
			$value=" checked";
		else
			$value="";
		$out.=("<input type='checkbox' name=".$name."$value $param>");
		if ($label<>"") $out.=$label;
		if ($exit_on_page)
		{
			$this->gOut($out);
		}
		return ($out);
	}

/**Gera um objeto HTML TEXTAREA
@author Cassiano Guimarães, Giuliano Nascimento
@param string $name       = Nome do objeto ou um array de 2 elementos contendo o "label" e o nome;
@param string $value      = Valor padrão ou um "recordset" onde o nome do campo do banco é igual a $name
@param Integer $cols      = Quantidade de colunas
@param Integer $rows      = Quantidade de linhas
@param string $param      = Algum parâmetro adicional (onClick, onMouseOver, ...)
@return string $out
*/
	function gMemo($name,$value="",$cols=70,$rows=5,$param="",$exit_on_page=true)
	{
		global $gDebug;
		global $cr;
			if ($gDebug>0) $this->gOut($cr);
		$label="";
		if (count($name)>1)
		{
			$tmp=$name;
			$label=$tmp[0];
			$name=$tmp[1];
		}
		if (is_object($value))
		{
			$value=$value->fields["$name"];
		}
		$out="";
		if ($label<>"") $out=$label."&nbsp;";
		if ($cols>=60)
			$out.="<textarea name='$name' rows='$rows' style='width: 100%'>".$value."</textarea>";
		else
			$out.="<textarea name='$name' rows='$rows' cols='$cols'>".$value."</textarea>";
		if ($exit_on_page)
		{
			$this->gOut($out);
		}
		return ($out);
	}

/**Gera um objeto HTML para edição de texto Wysiwyg
@author Giuliano Nascimento
@param string $name       = Nome do objeto ou um array de 2 elementos contendo o "label" e o nome;
@param string $value      = Valor padrão ou um "recordset" onde o nome do campo do banco é igual a $name
@param Integer $cols      = Quantidade de colunas
@param Integer $rows      = Quantidade de linhas
@param string $param      = Algum parâmetro adicional (onClick, onMouseOver, ...)
@return string $out
*/
	function gSuperMemo($name,$value="",$cols=80,$rows=5,$param="",$exit_on_page=true)
	{
		global $gDebug;
		global $cr;
		global $gPathDefault;
		global $http_inc;
			if ($gDebug>0) $this->gOut($cr);
		$label="";
		if (count($name)>1)
		{
			$tmp=$name;
			$label=$tmp[0];
			$name=$tmp[1];
		}
		if (is_object($value))
		{
			$value=$value->fields["$name"];
		}
		if ($label<>"") $out=$label."&nbsp;";
		$sBasePath = $_SERVER['PHP_SELF'] ;
		$sBasePath = substr( $sBasePath, strlen($gPathDefault)) ;
		//$sFCKBasePath = gBAR.gBASE.gBAR."inc_".gVER.gBAR."fckeditor".gBAR;
		
		$sFCKBasePath = $http_inc."/fckeditor/";
		$oFCKeditor = new FCKeditor($name);
		$oFCKeditor->BasePath = $sFCKBasePath ;
		$oFCKeditor->ToolbarSet = "Basic";
		$oFCKeditor->Config['AutoDetectLanguage']	= false ;
		$oFCKeditor->Config['DefaultLanguage']		= 'pt-br' ;
		$oFCKeditor->Value = trim($value);
		$oFCKeditor->Create() ;
	}

/**Gera um objeto HTML para edição de texto Wysiwyg
@author Giuliano Nascimento
@param string $name       = Nome do objeto ou um array de 2 elementos contendo o "label" e o nome;
@param string $value      = Valor padrão ou um "recordset" onde o nome do campo do banco é igual a $name
@param Integer $cols      = Quantidade de colunas
@param Integer $rows      = Quantidade de linhas
@param string $param      = Algum parâmetro adicional (onClick, onMouseOver, ...)
@return string $out
*/
	function gEditor($name,$value="",$cols=80,$rows=5,$param="",$exit_on_page=true)
	{
		global $gDebug;
		global $cr;
		global $gPathDefault;
		global $http_inc;
			if ($gDebug>0) $this->gOut($cr);
		$label="";
		if (count($name)>1)
		{
			$tmp=$name;
			$label=$tmp[0];
			$name=$tmp[1];
		}
		if (is_object($value))
		{
			$value=$value->fields["$name"];
		}
		if ($label<>"") $out=$label."&nbsp;";
		$sBasePath = $_SERVER['PHP_SELF'] ;
		$sBasePath = substr( $sBasePath, strlen($gPathDefault)) ;
		//$sFCKBasePath = gBAR.gBASE.gBAR."inc_".gVER.gBAR."fckeditor".gBAR;
		
		$sFCKBasePath = $http_inc."/fckeditor/";
		$oFCKeditor = new FCKeditor($name);
		$oFCKeditor->BasePath = $sFCKBasePath ;
		$oFCKeditor->ToolbarSet = "Default";
		$oFCKeditor->Config['AutoDetectLanguage']	= false ;
		$oFCKeditor->Config['DefaultLanguage']		= 'pt-br' ;
		$oFCKeditor->Value = trim($value);
		$oFCKeditor->Create() ;
	}
/**
@author Cassiano Guimarães, Giuliano Nascimento
@param string $name       = Nome do objeto ou um array de 2 elementos contendo o "label" e o nome;
@param string $value      = Valor padrão ou um "recordset" onde o nome do campo do banco é igual a $name
@param Integer $cols      = Quantidade de colunas
@param Integer $rows      = Quantidade de linhas
@param string $param      = Algum parâmetro adicional (onClick, onMouseOver, ...)
@return string $out
*/
	function gGrid($name,$value="",$fields="",$style="100%",$border=true,$rows=25,$param="")
	{
	/*
	Funcionalidade: Exibe uma grade (tabela) com valores
	Parâmetros:
		$name       = Nome do objeto ou um array de 2 elementos contendo o "label" e o nome;
		$value      = Valor padrão (matriz de 2 dimensões) ou um "recordset"
		$fields			= Dicionário de conversão de nomes de campos (array de 2 dimensões);
							ou Dicionário de tipos e campos (como em gForm->gShowForm);
		$style		= Largura (igual ao style da gTableBegin
		$rows       = Quantidade de linhas
		$param      = Algum parâmetro adicional (onClick, onMouseOver, ...)
	Criado por: Giuliano
	*/
		global $gDebug;
		global $cr;
		$reply="";
		$selectcap=gLng("register_select.short");
		$keys=array_keys($_POST);
		foreach ($keys as $key)
		{
			//if (($key<>"gAction") && ($key<>"gParam"))
			if (substr($key,0,1)<>"g")
			{
				if (strpos($reply,"&".$key."=")==0)
					$reply.="&".$key."=".$_REQUEST[$key];
			}
		}
		if ($gDebug>0)
		{
			$this->gOut($cr);
		}
		$label="";
		$gTAGs=false;
		if (count($name)>1)
		{
			$tmp=$name;
			$label=$tmp[0];
			$name=$tmp[1];
		}
		if (is_array($fields))
		{
			$field=$fields[0];
			if (is_numeric($field[0]))
			{
				// Formato de campos tipo FIELDS (então cria tipo DICT)
				$dict="";
				foreach ($fields as $field)
				{
					$dict[]=$field[1];
					if (($field[1]=="gEdit") || ($field[1]=="gCopy") || ($field[1]=="gDelete")) $gTAGs=true;
				}
			} else
			{
				// Formato de campos tipo DICT
				$dict=$fields;
			}
		} else
		{
			$dict=$fields;
		}
		$out="";
		if ($label<>"") $out=$label."&nbsp;";
		$this->gTableBegin($style,$border);

		if (!is_object($value) && (is_array($value)))
		{
			for ($f_t=0;$f_t<count($value);$f_t++)
			{
				$mtz="";
				$row=$value[$f_t];
				if (count($row)>1)
				{
					for ($g_t=0;$g_t<count($row);$g_t++)
					{
						$mtz[]=$row[$g_t];
					}
				} else
				{
					$mtz[]=$row;
				}
				if ($name=="gToolBar")
				{
					$this->gTableRow($mtz,gT_TOOL);
				} else
				{
					if ($f_t==0)
						$this->gTableRow($mtz,gT_HEADER);
					else
						$this->gTableRow($mtz,gT_DETAIL);
				}
			}
		} else
		{
			if ((is_object($value)) || (substr(strtoupper($value),0,7)=="SELECT "))
			{
				if (is_object($value))
				{
					$rstmp=$value;

				} else
				{
					$qtmp=$value;
					$rstmp=gQuery($qtmp,gD_DEFAULT,1);;
				}
				$f_t=0;
				$ttlfld=$rstmp->FieldCount();
				$fldmenos=0;
				$mtz="";
				if (gVar("database.showcount")=="true")
					$mtz[]="->N<sup>o</sup>";
				$summary=false;
				$sum="";
				$min="";
				$max="";
				$fty="";
				$nme="";
				$adict="";
				$gsum="";
				$gmin="";
				$gmax="";

				$tmp=$dict[0];
				if (($gTAGs==true) && ($ttlfld>gVar("database.maxcols")))
				{
					$ttlfld=gVar("database.maxcols");
				}
				// Zera os totais para os grupos
				for ($h_t=0; $h_t<count($dict); $h_t++)
				{
					$tmp=$dict[$h_t];
					if ((count($tmp)>2) && (strtoupper($tmp[2])=="GROUP"))
					{
						for ($g_t=0; $g_t<$ttlfld; $g_t++)
						{
							// nome do grupo - nome do campo
							$gsum[$tmp[1]][$g_t]=0;
							$gmin[$tmp[1]][$g_t]=0;
							$gmax[$tmp[1]][$g_t]=0;
							$gult[$tmp[1]]=":x:";
							$gcnt[$tmp[1]][$g_t]=0;
						}
					}
				}
				// *********** Monta cabeçalho da tabela
				for ($g_t=0; $g_t<$ttlfld; $g_t++)
				{
					$fld=$rstmp->FetchField($g_t);
					$fldtype=$rstmp->MetaType($fld);
					$sum[]=0;
					$min[]=0;
					$max[]=0;
					$nme[]=$fld->name;
					$fty[]=$fldtype;
					if (($fldtype!="R") || (gVar("database.showid")=="true"))
					{
						$pre="";
						if (($fldtype=="R") || ($fldtype=="I"))
						{
							$pre="->";
						}
						if ($fldtype=="N")
						{
							$pre="->";
						}
						if ($fldtype=="C")
						{
							$pre="<-";
						}
						if (($fldtype=="L") || (($fld->max_length<=4) && ($fldtype=="I")))
						{
							$pre="<>";
						}
						$dbgs=$dbge="";
						if ($gDebug>0)
						{
							$types["C"]="Varchar";
							$types["D"]="Date";
							$types["T"]="DateTime";
							$types["N"]="Float";
							$types["I"]="Integer";
							$types["R"]="Autoincrement";
							$types["B"]="Blob";
							$types["X"]="Memo";
							//$dbgs="<acronym title='".$fld->name. " - ". $types[$fldtype]. " (". $fld->max_length.")'>";
							//$dbge="</acronym>";
						}
						// **** Desabilitando o Order By
						//$dbgs.="<a class='orderby' href='".$_SERVER["PHP_SELF"]."?gAction=".$_REQUEST["gAction"]."&gOrderBy=".$fld->name."$reply'>";
						//$dbge="</a>".$dbge;
						$dbgs.="";
						$dbge="".$dbge;
						$caption=$fld->name;
						$inclui=true;
						if (is_array($dict))
						{
							for ($h_t=0; $h_t<count($dict); $h_t++)
							{
								$tmp=$dict[$h_t];
								if (count($tmp)>2)
								{
									$summary=true;
									if (($tmp[1]==$caption) && (strtoupper($tmp[2])=="GROUP"))
										$inclui=false;
								}
								if ($caption==$tmp[0])
								{
									$adict[]=true;
									$caption=$tmp[1];
								} else
								{
									$adict[]=false;
								}
							}
						}
						for ($h_t=0; $h_t<count($fields); $h_t++)
						{
							$field=$fields[$h_t];
							$header=$field[1];
							if (count($header)>1)
							{
								$label=$header[0];
								$name=$header[1];
							} else
							{
								$label=$header;
								$name=$header;
							}
							if (($name==$fld->name) && ($field[0]>=20) && ($field[0]<=30))
							{
								$pre="<-";
							}
							if (($name==$fld->name) && (($field[0]==gI_HIDDEN) || ($field[0]==gI_EXCLUDE))) // Campo tipo HIDDEN e EXCLUDE
							{
								$fldmenos++;
								$inclui=false;

							}
							if ($name==$fld->name)
								$caption=$label;

						}
						if ($inclui)
							$mtz[]=$pre.$dbgs.gField($caption).$dbge;
					}
				}
				// *********** Verifica se existem campos adicionais e mostra-os
				$cs=0;
				if (is_array($dict))
				{
					for ($h_t=0; $h_t<count($dict); $h_t++)
					{
						$tmp=$dict[$h_t];
						if ((substr($tmp,0,5)=="gEdit") || ($tmp=="gCopy") || ($tmp=="gDelete") || (substr($tmp,0,5)=="gLink"))
						{
							$cs++;
						}
					}
				}
				if ($cs>0)
				{
					$mtztmp=$mtz;
					$mtz="";
					$mtz[]="~".$cs.gLng("options.short");
					//foreach ($mtztmp as $tmp)
					foreach ($mtztmp as $tmp)
						$mtz[]=$tmp;
				}
				$this->gTableRow($mtz,gT_HEADER);
				$cnt=0;
				// *********** Exibindo registros, um a um...
				while (!($rstmp->EOF))
				{
					$mtz="";
					$cnt++;

					for ($g_t=0; $g_t<$ttlfld; $g_t++)
					{
						$fld=$rstmp->FetchField($g_t);
						if (($fty[$g_t]=="R") || ($fld->name=="id"))
						{
							$id=$rstmp->fields[$g_t];
							$idname=$fld->name;
						}
					}
					if (gVar("database.idd")=="true")
					{
						$dbidd=$rstmp->fields['idd'];
						if ($dbidd=="") $dbidd=-1;
					}
					else
						$dbidd=-1;
					
					// ********* Mostra EDIT, DELETE, LINK e COPY
					$cnt_lnk=0;
					if ($cs>0)
					{
						for ($h_t=0; $h_t<count($dict); $h_t++)
						{
							$tmp=$dict[$h_t];
							if ((substr($tmp,0,5)=="gEdit") || ($tmp=="gCopy"))
							{
								$parm="";
								if (substr($tmp,0,5)=="gEdit")
								{
									$parm=substr($tmp,6);
									$tmp=substr($tmp,0,5);
								}
								if ($dbidd==0)
									$mtz[]="";
								else
									$mtz[]="<acronym title='".gLng("register_".substr($tmp,1).".long")."'><a class='menu' href='".$_SERVER["PHP_SELF"]."?gAction=".strtolower(substr($tmp,1))."_frm&gIdName=$idname&gId=$id&id_processo=".$_REQUEST['id_processo']."&id_roteiro=".$_REQUEST['id_roteiro'].$parm."'> ".gLng("register_".substr($tmp,1).".short")." </a></acronym>";
								$cnt_lnk++;
							}
							if (substr($tmp,0,5)=="gLink")
							{
								$tmplink=substr($tmp,6);
								if (strpos($tmp,"|")>0)
								{
									list($selectcap,$tmplink)=explode("|",$tmplink);
								}
								$div="?";
								if (strpos($tmplink,"?")>0) $div="&";
								//if ($dbidd==0)
								//	$mtz[]="";
								//else
									$mtz[]="<acronym title='".gLng("register_select.long")."'><a class='menu' href='".$tmplink.$div."gIdName=$idname&gId=$id'> ".$selectcap." </a></acronym>";
								$cnt_lnk++;
							}
							if ($tmp=="gDelete")
							{
								if ($dbidd==0)
									$mtz[]="";
								else
									$mtz[]="<acronym title='".gLng("register_".substr($tmp,1).".long")."'><a class='menu' href=\"javascript:gConfirm('$idname',$id);\"> ".gLng("register_".substr($tmp,1).".short")." </a></acronym>";
								$cnt_lnk++;
							}
						}
					}
					if (gVar("database.showcount")=="true")
						$mtz[]="->".$cnt;
					for ($g_t=0; $g_t<$ttlfld; $g_t++)
					{
						$fld=$rstmp->FetchField($g_t);
						$value=str_replace("&#9679;","*",html2str($rstmp->fields[$g_t]));
						// ****** Formata os campos de acordo com seu tipo no Banco de Dados
						if (($fty[$g_t]!="R") || (gVar("database.showid")=="true"))
						{
							$pre="";
							if ($fty[$g_t]=="D")
							{
								$value=gDate($value);
							}
							if ($fty[$g_t]=="T")
							{
								$value=gDateTime($value);
							}
							if ($fty[$g_t]=="R")
							{
								$id=$value;
								$idname=$fld->name;
							}
							if (($fty[$g_t]=="R") || ($fldtype=="I"))
							{
								$pre="->";
								$value=$value;
							}
							if ($fty[$g_t]=="N")
							{
								$pre="->";
								$value=gFloat($value);
							}
							if ($fty[$g_t]=="C")
							{
								$pre="<-";
							}
							if (($fty[$g_t]=="B") || ($fty[$g_t]=="X"))
							{
								$pre="<-";
								$value=nl2br($value);
							}
							if (($fty[$g_t]=="N") || ($fty[$g_t]=="R") || ($fty[$g_t]=="I"))
							{
								// ... para o sumário e os totais
								$pre="->";
								$numvalue=$rstmp->fields[$g_t];
								$sum[$g_t]+=$numvalue;
								if ($numvalue<$min[$g_t]) $min[$g_t]=$numvalue;
								if ($numvalue>$max[$g_t]) $max[$g_t]=$numvalue;
								for ($h_t=0; $h_t<count($dict); $h_t++)
								{
									$tmp=$dict[$h_t];
									if ((count($tmp)>2) && (strtoupper($tmp[2])=="GROUP"))
									{
										$gsum[$tmp[1]][$g_t]+=$numvalue;
										if ($numvalue<$gmin[$tmp[1]][$g_t]) $gmin[$tmp[1]][$g_t]=$numvalue;
										if ($numvalue>$gmax[$tmp[1]][$g_t]) $gmax[$tmp[1]][$g_t]=$numvalue;
										$usum[$tmp[1]][$g_t]=$numvalue;
										$umin[$tmp[1]][$g_t]=$numvalue;
										$umax[$tmp[1]][$g_t]=$numvalue;
									}
								}
							}
							if (($fty[$g_t]=="L") || (($fld->max_length<=4) && ($fty[$g_t]=="I")))
							{
								$pre="<>";
								if ($value==0)
								{
									$value=gLng("false.short");
								} else
								{
									$value=gLng("true.short");
								}
							}
							$inclui=true;
							if (is_array($dict))
							{
								for ($h_t=0; $h_t<count($dict); $h_t++)
								{
									$tmp=$dict[$h_t];
									if (($tmp[1]==$fld->name) && (count($tmp)>2))
									{
										if (strtoupper($tmp[2])=="GROUP")
											$inclui=false;
									}
								}
							}
							// Inverte a ordem dos campos personalizados para
							// permitir que o último parâmetro seja processado primeiro

							$tfields=$fields;
							$fields="";
							for ($h_t=count($tfields); $h_t>=0; $h_t--)
							{
								$fields[]=$tfields[$h_t];
							}

							for ($h_t=0; $h_t<count($fields); $h_t++)
							{
								$field=$fields[$h_t];
								$header=$field[1];
								if (count($header)>1)
								{
									$label=$header[0];
									$name=$header[1];
								} else
								{
									$label=$header;
									$name=$header;
								}
								if (($name==$fld->name) && ($field[0]>=20) && ($field[0]<=30)) // Select
								{
									if (substr(strtolower($field[2]),0,7)=="select ")
									{
										$sql=$field[2];
										$table=substr($sql,strpos(strtolower($sql)," from ")+6);
										$relac=false;
										if (strpos($table," ")>0)
										{
											$table=substr($table,0,strpos($table," "));
										} elseif (strpos($table,",")>0)
										{
											$table=substr($table,0,strpos($table,","));
											$relac=true;
										}
										$table.=".";
										if (strpos(strtolower($sql)," where ")>0)
										{
											$pnt=strpos(strtolower($sql)," where ")+7;
											if ((strpos(strtolower($sql)," join ")>0) || $relac)
											{
												$where=$table."id=$value and ";
											} else
											{
												$sql=substr($sql,0,$pnt);
												$where=$table."id=$value ";
											}
										} else
										{
											$pnt=strpos(strtolower($sql)," order ");
											if ($pnt==0) $pnt=strlen($sql);
											$where=" where ".$table."id=$value";
										}
										$sql=substr($sql,0,$pnt).$where.substr($sql,$pnt);
										$tDebug=$gDebug;
										$gDebug=0;
										$rs=gQuery($sql,gD_DEFAULT,1);
										$gDebug=$tDebug;
										//$value=$sql;
										$pre="<-";
										if (!$rs->EOF)
										{
											$value=$rs->fields[1];
										}
									}
									$h_t=count($fields);
								}
								if (($name==$fld->name) && (($field[0]==gI_HIDDEN) || ($field[0]==gI_EXCLUDE))) // Campo tipo HIDDEN e EXCLUDE
								{
									$inclui=false;
								}
							}
							if ($inclui)
								$mtz[]=$pre.$value."&nbsp;";
						}
					}
					// 3ÁRIO Se existir algum GROUP então mostra...
					$mostra_grupo=false;
					for ($h_t=0; $h_t<count($dict); $h_t++)
					{
						$tmp=$dict[$h_t];
						if ((count($tmp)>2) && (strtoupper($tmp[2])=="GROUP"))
						{
							if ($gult[$tmp[1]]<>$rstmp->fields[$tmp[1]])
							{
								$mostra_grupo=true;
							}
						}
					}
					for ($h_t=0; $h_t<count($dict); $h_t++)
					{
						$tmp=$dict[$h_t];
						if ((count($tmp)>2) && (strtoupper($tmp[2])=="GROUP"))
						{
							if (($gult[$tmp[1]]<>":x:") && ($gult[$tmp[1]]<>$rstmp->fields[$tmp[1]]))
							{
								$summ="";
								if (gVar("database.showcount")=="true")
									$summ[]="&nbsp;";
								for ($j_t=0; $j_t<$cnt_lnk; $j_t++)
									$summ[]="&nbsp;";
								$mostra_sumarios=false;
								for ($j_t=0; $j_t<$ttlfld-$fldmenos; $j_t++)
								{
									$caption="&nbsp;";
									if ((gVar("database.showid")=="true") || ($nme[$j_t]<>$idname))
									{
										$inclui_campo=true;
										for ($i_t=0; $i_t<count($dict); $i_t++)
										{
											$ttmp=$dict[$i_t];
											if ((count($ttmp)>2) && ($ttmp[1]==$nme[$j_t]))
											{
												if (strtoupper($ttmp[2])=="GROUP")
												{
													$inclui_campo=false;
												} else
												{
													if (strtoupper($ttmp[2])=="SUM")
													{
														$caption=$gsum[$tmp[1]][$j_t]-$usum[$tmp[1]][$j_t];
														$mostra_sumarios=true;
													}
													if (strtoupper($ttmp[2])=="COUNT")
													{
														$caption=$gcnt[$tmp[1]][$j_t];
														$mostra_sumarios=true;
													}
													if (strtoupper($ttmp[2])=="AVG")
													{
														$caption=($gsum[$tmp[1]][$j_t]-$usum[$tmp[1]][$j_t])/$gcnt[$tmp[1]][$j_t];
														$mostra_sumarios=true;
													}
													if (strtoupper($ttmp[2])=="MIN")
													{
														if ($gmin[$tmp[1]][$j_t]<$umin[$tmp[1]][$j_t])
															$caption=$gmin[$tmp[1]][$j_t];
														else
															$caption=$umin[$tmp[1]][$j_t];
														$mostra_sumarios=true;
													}
													if (strtoupper($ttmp[2])=="MAX")
													{
														if ($gmax[$tmp[1]][$j_t]>$umax[$tmp[1]][$j_t])
															$caption=$gmax[$tmp[1]][$j_t];
														else
															$caption=$umax[$tmp[1]][$j_t];
														$mostra_sumarios=true;
													}
													if ($fty[$j_t]=="N") $caption=gFloat($caption);
													if (gVar("database.showsummary")<>"false")
													$caption="<acronym title='".gLng(strtolower($ttmp[2]).".long")."'>".$caption."&nbsp;</acronym>";
													$gsum[$tmp[1]][$j_t]=$usum[$tmp[1]][$j_t];
													$gmin[$tmp[1]][$j_t]=$umin[$tmp[1]][$j_t];
													$gmax[$tmp[1]][$j_t]=$umax[$tmp[1]][$j_t];
													$gcnt[$tmp[1]][$j_t]=1;
												}
											}
										}
										if ($inclui_campo)
											$summ[]="->".$caption;
									}
								}
								if ($mostra_sumarios)
									$this->gTableRow($summ,gT_SUMMARY);
							} else
							{
								for ($j_t=0; $j_t<$ttlfld; $j_t++)
								{
									$gcnt[$tmp[1]][$j_t]++;
								}

							}
							$gult[$tmp[1]]=$rstmp->fields[$tmp[1]];
						}
					}
					for ($h_t=0; $h_t<count($dict); $h_t++)
					{
						$tmp=$dict[$h_t];
						if ((count($tmp)>2) && (strtoupper($tmp[2])=="GROUP"))
						{
							if ($mostra_grupo)
							{
								$mais=0;
								if (gVar("database.showcount")=="true")
									$mais++;
								for ($j_t=0; $j_t<$cnt_lnk; $j_t++)
									$mais++;
								$group="";
								$tgroup=trim(str_replace("&#9679;","*",html2str($rstmp->fields[$tmp[1]])));
								if (strpos($tgroup,"-")>0)
								{
									$m=split("-",$tgroup);
									if (count($m)==3)
										$tgroup=gDate($tgroup);
								}
								if (strpos($tgroup,"/")>0)
								{
									$m=split("/",$tgroup);
									if (count($m)==3)
										$tgroup=gDate($tgroup);
								}
								$group[]="~".trim($ttlfld-$fldmenos+$mais-1)." ".$tgroup;
								$this->gTableRow($group,gT_SUMMARY);
							}
						}
					}
					$this->gTableRow($mtz,gT_GRIDDETAIL);
					$f_t++;
					$rstmp->MoveNext();
				}

				if ($mostra_sumarios)
				{
					for ($h_t=0; $h_t<count($dict); $h_t++)
					{
						$tmp=$dict[$h_t];

						if ((count($tmp)>2) && (strtoupper($tmp[2])=="GROUP"))
						{
							$h_t=count($dict);
							$summ="";
							if (gVar("database.showcount")=="true")
								$summ[]="&nbsp;";
							for ($j_t=0; $j_t<$cnt_lnk; $j_t++)
								$summ[]="&nbsp;";
							for ($j_t=0; $j_t<$ttlfld-$fldmenos; $j_t++)
							{
								if ((gVar("database.showid")=="true") || ($nme[$j_t]<>$idname))
								{
									$caption="&nbsp;";
									$inclui_campo=true;
									for ($i_t=0; $i_t<count($dict); $i_t++)
									{
										$ttmp=$dict[$i_t];
										if ((count($ttmp)>2) && ($ttmp[1]==$nme[$j_t]))
										{
											if (strtoupper($ttmp[2])=="GROUP")
											{
												$inclui_campo=false;
											} else
											{
												if (strtoupper($ttmp[2])=="SUM") $caption=$gsum[$tmp[1]][$j_t];
												if (strtoupper($ttmp[2])=="MIN") $caption=$gmin[$tmp[1]][$j_t];
												if (strtoupper($ttmp[2])=="MAX") $caption=$gmax[$tmp[1]][$j_t];
												if (strtoupper($ttmp[2])=="COUNT") $caption=$gcnt[$tmp[1]][$j_t];
												if (strtoupper($ttmp[2])=="AVG") $caption=($gsum[$tmp[1]][$j_t]-$usum[$tmp[1]][$j_t])/$gcnt[$tmp[1]][$j_t];
												if ($fty[$j_t]=="N") $caption=gFloat($caption);
												if (gVar("database.showsummary")<>"false")
													$caption="<acronym title='".gLng(strtolower($ttmp[2]).".long")."'>".$caption."&nbsp;</acronym>";
											}
										}
									}
									if ($inclui_campo)
										$summ[]="->".$caption;
								}
							}
							$this->gTableRow($summ,gT_SUMMARY);
						}
					}
				}
				// ******* TOTAL GERAL

				if ($summary)
				{
					$mtz="";
					if ($cs>0)
					{
						$mtz[]="~$cs&nbsp;";
					}
					if (gVar("database.showcount")=="true")
						$mtz[]="&nbsp;";

					for ($g_t=0; $g_t<$ttlfld-$fldmenos; $g_t++)
					{
						if ((gVar("database.showid")=="true") || ($nme[$g_t]<>$idname))
						{
							$caption="&nbsp;";
							$inclui_campo=true;
							for ($h_t=0; $h_t<count($dict); $h_t++)
							{
								$tmp=$dict[$h_t];
								if ((count($tmp)>2) && ($tmp[1]==$nme[$g_t]))
								{
									if (strtoupper($tmp[2])=="GROUP")
									{
										$inclui_campo=false;
									} else
									{
										if (strtoupper($tmp[2])=="SUM") $caption=$sum[$g_t];
										if (strtoupper($tmp[2])=="MIN") $caption=$min[$g_t];
										if (strtoupper($tmp[2])=="MAX") $caption=$max[$g_t];
										if (strtoupper($tmp[2])=="AVG") $caption=$sum[$g_t]/$f_t;
										if (strtoupper($tmp[2])=="COUNT") $caption=$f_t;
										if ($fty[$g_t]=="N") $caption=gFloat($caption);
										if (gVar("database.showsummary")<>"false")
										$caption="<acronym title='".gLng(strtolower($tmp[2]).".long")."'>".$caption."</acronym>";
									}
								}
							}
							if ($inclui_campo)
								$mtz[]="->".$caption;
						}
					}
					$this->gTableRow($mtz,gT_SUMMARY);
				}
			}
		}
		$this->gTableEnd();
	}
}

function gForm2Sql($cmd,$idname="id",$id=0)
{
	global $table;
	$sql="";
	$names="";
	$values="";
	$types="";
	$flds=split("\n",$_POST["gFields"]);
	if ($table=="")
		$table=$_REQUEST["gTable"];
	$cmps="";
	$rst=gQuery("Select * from $table",gD_DEFAULT,1);
	$cntcmps=$rst->FieldCount();
	for ($t=0; $t<$cntcmps; $t++)
	{
		$cmp=$rst->FetchField($t);
		$cmps[]=$cmp->name;
	}
	foreach ($flds as $field)
	{
		$fld=split("\|",$field);
		$name=$fld[1];
		$value=$_POST[$name];
		// Prevenção contra o SQLInjection:
		$value=str_replace("\""," ",$value);
		$value=str_replace("'"," ",$value);
		$type=$fld[0];
		for ($t=0; $t<$cntcmps; $t++)
		{
			if ($name==$cmps[$t]) // Só aceita campos que existem na tabela mencionada !
			{
				$names[]=$name;
				$values[]=$value;
				$types[]=$type;
			}
		}
	}
	if (($cmd==gQ_INSERT) || ($cmd==gQ_SELECT))
	{
		$sql1="";
		$sql2="";
		for ($t=0; $t<count($names); $t++)
		{
			$name=$names[$t];
			$value=$values[$t];
			$type=$types[$t];
			$sep1="'";
			$sep2="'";
			if ($type<>gI_EXCLUDE)
			{
				$sql1.=$name.", ";
				if ($type==gI_NUM)
				{
					$sep1="";
					$sep2="";
					if ($value=='')
						$value='0';
					else
						$value=gDBFloat($value);
				}
				if (($type==gI_PASSWORD) && (substr(gVar("database.type"),0,5)=="mysql"))
				{
					$sep1="PASSWORD('";
					$sep2="')";
				}
				if (($type==gI_DATE) || ($type==gI_DATENULL))
				{
					$value=gDBDate($value);
					if ($value=='') $value='0000-00-00';
				}
				if (($type==gI_DATETIME) || ($type==gI_DATETIMENULL))
				{
					$value=gDBDateTime($value);
					if ($value=='') $value='0000-00-00 00:00:00';
				}
				if ($type==gI_CHECK)
				{
					$sep1="";
					$sep2="";

					if ($value=="on")
						$value="1";
					else
						$value="0";
				}
				if ($type==gI_SUPERMEMO)
					$sql2.=$sep1.$value.$sep2.", ";
				else
					$sql2.=$sep1.html2str($value).$sep2.", ";
			}
		}
		if ($cmd==gQ_INSERT)
		{
			$sql="Insert into $table (";
			$sql1=substr($sql1,0,strlen($sql1)-2);
			$sql2=substr($sql2,0,strlen($sql2)-2);
			$sql.=$sql1.") values (".$sql2.")";
		}
		if ($cmd==gQ_SELECT)
		{
			$sql1=substr($sql1,0,strlen($sql1)-2);
			$sql2=substr($sql2,0,strlen($sql2)-2);
			$sql="Select $sql1 from $table";
			if ($_POST["id"]<>"")
			{
				$sql.=" where id=".$_POST["id"];
			}

		}
	}
	if ($cmd==gQ_UPDATE)
	{
		$sql1="";
		$sql2="";
		for ($t=0; $t<count($names); $t++)
		{
			$name=$names[$t];
			$value=$values[$t];
			$type=$types[$t];
			$sep1="'";
			$sep2="'";
			$faz=true;
			if (($type<>gI_EXCLUDE) && (strtolower($name)!=strtolower($idname)))
			{
				if ($type==gI_NUM)
				{
					$sep1="";
					$sep2="";
					if ($value=='')
						$value='0';
					else
						$value=gDBFloat($value);
				}
				if (($type==gI_PASSWORD) && (substr(gVar("database.type"),0,5)=="mysql"))
				{
					if ($value=="_senhainalterada_")
					{
						$faz=false;
					} else
					{
						$sep1="PASSWORD('";
						$sep2="')";
					}
				}
				if (($type==gI_DATE) || ($type==gI_DATENULL))
				{
					$value=gDBDate($value);
					if ($value=='') $value='0000-00-00';
				}
				if (($type==gI_DATETIME) || ($type==gI_DATETIMENULL))
				{
					$value=gDBDateTime($value);
					if ($value=='') $value='0000-00-00 00:00:00';
				}
				if ($type==gI_CHECK)
				{
					$sep1="";
					$sep2="";

					if ($value=="on")
						$value="1";
					else
						$value="0";
				}
				if ($faz)
				{
					if ($type==gI_SUPERMEMO)
						$sql1.=$name."=".$sep1.$value.$sep2.", ";
					else
						$sql1.=$name."=".$sep1.html2str($value).$sep2.", ";
				}
			}
		}
		$sql1=substr($sql1,0,strlen($sql1)-2);
		$sql="Update $table set ";
		$sql.=$sql1." where $idname=$id";
	}
	if ($cmd==gQ_DELETE)
	{
		$sql="Delete from $table where $idname=$id";
	}
	return($sql);
}

/** 
Gera uma imagem buscando-a do banco de dados (tabela geral_imagens)
@param $classe string categoria de imagem (texto para agrupar)
@param $id integer id 
@param $tamanho integer gI_BIG, gI_MEDIUM, gI_TINY
@param $indice integer quando encontrar as imagens com o Id de referência, mostra a de índice $indice, se zero, mostra todas
*/
function gDBImage($classe,$id,$tamanho,$indice)
{
}
?>
