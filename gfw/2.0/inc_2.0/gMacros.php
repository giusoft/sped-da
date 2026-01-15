<?

$gMacros="";
$gMacros[]=array("usuario_id",$usr_id);
$gMacros[]=array("usuario_nome",$usr_nome);
$gMacros[]=array("usuario_apelido",$usr_apelido);
$gMacros[]=array("usuario_email",$usr_email);
$gMacros[]=array("empresa_id",$empr_id);
$gMacros[]=array("empresa_nome",$empr_nome);
$gMacros[]=array("empresa_email",$empr_email);
$sqltemp="select j.*,e.* from geral_pessoas p left join geral_pessoas_juridicas j on p.id=j.id_geral_pessoas left join geral_pessoas_enderecos e on p.id=e.id_geral_pessoas where p.id=$empr_id";
$rstmp=gQuery($sqltemp);
if (!$rstmp->EOF)
{
	$gMacros[]=array("empresa_razao_social",$rstmp->fields['razao_social']);
	$gMacros[]=array("empresa_cnpj",$rstmp->fields['cnpj']);
	$gMacros[]=array("empresa_inscricao_estadual",$rstmp->fields['insc_estadual']);
	$gMacros[]=array("empresa_inscricao_municipal",$rstmp->fields['insc_municipal']);
	$gMacros[]=array("empresa_endereco",$rstmp->fields['endereco']);
	$gMacros[]=array("empresa_endereco_numero",$rstmp->fields['numero']);
	$gMacros[]=array("empresa_bairro",$rstmp->fields['bairro']);
	$gMacros[]=array("empresa_cep",$rstmp->fields['cep']);
	$gMacros[]=array("empresa_complemento",$rstmp->fields['complemento']);
	$gMacros[]=array("empresa_cidade",gFieldById("geral_cidades",$rstmp->fields['id_geral_cidades'],"descricao"));
	$gMacros[]=array("empresa_estado",gFieldById("geral_estados",$rstmp->fields['id_geral_estados'],"descricao"));
}
$mes['01']="Janeiro";
$mes['02']="Fevereiro";
$mes['03']="Março";
$mes['04']="Abril";
$mes['05']="Maio";
$mes['06']="Junho";
$mes['07']="Julho";
$mes['08']="Agosto";
$mes['09']="Setembro";
$mes['10']="Outubro";
$mes['11']="Novembro";
$mes['12']="Dezembro";

$gMacros[]=array("dia",date("d"));
$gMacros[]=array("mes",date("m"));
$gMacros[]=array("ano",date("Y"));

$gMacros[]=array("data_extenso",date("d")." de ".$mes[date("m")]." de ".date("Y"));
$gMacros[]=array("datahora",gDateTime(date("Y-m-d h:i:s")));
$gMacros[]=array("data",gDate(date("Y-m-d")));

$gMacros[]=array("hora",date("H:i:s"));
$gMacros[]=array("ip",$REMOTE_ADDR);
$gMacros[]=array("id",$gId);

/** gMacro
* Realiza macro substituição de texto
*/
function gMacro($texto,$macros="",$bd="")
{
	global $gId;

	$texto.=" ";
	$texto=str_replace("@gId",$gId,$texto);
	$texto=str_replace("@aluno_valor_total_extenso",Extenso($bd->fields['aluno_valor_total']),$texto);
	foreach ($macros as $macro)
	{
		$texto=str_replace('@'.$macro[0]." ",$macro[1]." ",$texto);
		$texto=str_replace('@'.$macro[0].".",$macro[1].".",$texto);
		$texto=str_replace('@'.$macro[0].",",$macro[1].",",$texto);
		$texto=str_replace('@'.$macro[0].";",$macro[1].";",$texto);
		$texto=str_replace('@'.$macro[0]."/",$macro[1]."/",$texto);
		$texto=str_replace('@'.$macro[0]."-",$macro[1]."-",$texto);
		$texto=str_replace('@'.$macro[0]."'",$macro[1]."'",$texto);
		$texto=str_replace('@'.$macro[0]."%",$macro[1]."%",$texto);
		$texto=str_replace('@'.$macro[0]."<",$macro[1]."<",$texto);
		$texto=str_replace('@'.$macro[0]."\n",$macro[1]."\n",$texto);
		$texto=str_replace('@'.$macro[0].")",$macro[1].")",$texto);
		$texto=str_replace('@'.$macro[0]."]",$macro[1]."]",$texto);
	}
	if ($bd<>"")
	{
		$ttlfld=$bd->FieldCount();

		$sum=0;
		$min=0;
		$max=0;

		$campos="";

		if ($rs->fields['numerar_detalhe']==1)
			$tabela.="<th>Nº</th>";
		for ($g_t=0; $g_t<$ttlfld; $g_t++)
		{

			$fld=$bd->FetchField($g_t);
			$fldtype=$bd->MetaType($fld);

			$campos[]=$fld->name;
			$fty[$fld->name]=$fldtype;

			$max[$fld->name]=$fld->max_length;
		}

		if(!$bd->EOF)
		{
			foreach ($campos as $campo)
			{
				$value=$bd->fields[$campo];

				if ($fty[$campo]=="D")
				{

					$value=gDate($value);
				}
				if ($fty[$campo]=="T")
				{

					$value=gDateTime($value);
				}
				if (($fty[$campo]=="R") || ($campo=="I"))
				{
					$value=$value;
				}
				if ($fty[$campo]=="N")
				{
					$value=gFloat($value);
				}
				if ($fty[$campo]=="C")
				{
				}
				if (($fty[$campo]=="B") || ($fty[$campo]=="X"))
				{
					$value=nl2br($value);
				}
				if (($fty[$campo]=="N") || ($fty[$campo]=="R") || ($fty[$campo]=="I"))
				{
					// ... para o sumário e os totais
					$numvalue=$value;
					$sum[$g_t]+=$numvalue;
					if ($numvalue<$min[$g_t]) $min[$g_t]=$numvalue;
					if ($numvalue>$max[$g_t]) $max[$g_t]=$numvalue;
				}
				if (($fty[$campo]=="L"))
				{
					if ($value==0)
					{
						$value=gLng("message.false.short");
					} else
					{
						$value=gLng("message.true.short");
					}
				}

				$texto=str_replace('@'.$campo." ",$value." ",$texto);
				$texto=str_replace('@'.$campo.".",$value.".",$texto);
				$texto=str_replace('@'.$campo.",",$value.",",$texto);
				$texto=str_replace('@'.$campo.";",$value.";",$texto);
				$texto=str_replace('@'.$campo."/",$value."/",$texto);
				$texto=str_replace('@'.$campo."-",$value."-",$texto);
				$texto=str_replace('@'.$campo."%",$value."%",$texto);
				$texto=str_replace('@'.$campo.":",$value.":",$texto);
				$texto=str_replace('@'.$campo."'",$value."'",$texto);
				$texto=str_replace('@'.$campo."<",$value."<",$texto);
				$texto=str_replace('@'.$campo."\n",$value."\n",$texto);
				$texto=str_replace('@'.$campo.")",$value.")",$texto);
				$texto=str_replace('@'.$campo."]",$value."]",$texto);
			}
			
		}
	}
	return($texto);
}

?>
