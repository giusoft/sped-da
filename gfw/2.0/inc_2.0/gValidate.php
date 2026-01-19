<?
/*
//VERIFICA SE O FORMULÁRIO FOI ENVIADO
if($_POST["verOK"]) {
 //RECEBE OS DADOS DO FORMULÁRIO
 $cpf = $_POST["cpf"];

 //VERIFICA SE O QUE FOI INFORMADO É NÚMERO
 */
 
 function gValidaCampos($exclude){
	$msg= "<font color='red'>";
	$keys=array_keys($_REQUEST);
   foreach ($keys as $camposform)
	{
		if ($_REQUEST[$camposform]=="" || $_REQUEST[$camposform]==null) {
		$faz=false;
	//	echo $camposform."<br>";
		if($camposform!="insc_municipal")
				$msg=$msg." campo <b>".$lb[$camposform]."</b> não foi preenchido<br/>";
		}
	}
	 return false;
 }
 
 function gValidaCPF($cpf){
	 if(!is_numeric($cpf)) 
	 {
	  $status = false;
	 }
	 else 
	{
	  //VERIFICA
	  if( ($cpf == '11111111111') || ($cpf == '22222222222') ||
		($cpf == '33333333333') || ($cpf == '44444444444') ||
		($cpf == '55555555555') || ($cpf == '66666666666') ||
		($cpf == '77777777777') || ($cpf == '88888888888') ||
		($cpf == '99999999999') || ($cpf == '00000000000') ) {
		$status = false;
	  }
	  else 
	  {
			//PEGA O DIGITO VERIFIACADOR
			$dv_informado = substr($cpf, 9,2);

			for($i=0; $i<=8; $i++) {
			 $digito[$i] = substr($cpf, $i,1);
			}

		//CALCULA O VALOR DO 10º DIGITO DE VERIFICAÇÂO
			$posicao = 10;
			$soma = 0;

			for($i=0; $i<=8; $i++) {
			 $soma = $soma + $digito[$i] * $posicao;
			 $posicao = $posicao - 1;
			}

			$digito[9] = $soma % 11;

			if($digito[9] < 2) {
			 $digito[9] = 0;
			}
			else {
			 $digito[9] = 11 - $digito[9];
			}

		//CALCULA O VALOR DO 11º DIGITO DE VERIFICAÇÃO
			$posicao = 11;
			$soma = 0;

			for ($i=0; $i<=9; $i++) {
			 $soma = $soma + $digito[$i] * $posicao;
			 $posicao = $posicao - 1;
			}

			$digito[10] = $soma % 11;

			if ($digito[10] < 2) {
			 $digito[10] = 0; 
			}
			else {
			 $digito[10] = 11 - $digito[10];
			}

	  //VERIFICA SE O DV CALCULADO É IGUAL AO INFORMADO
		  $dv = $digito[9] * 10 + $digito[10];
		  if ($dv != $dv_informado) {
			$status = false;
		  }else $status = true;
		}//FECHA ELSE
	}//FECHA ELSE(is_numeric)
	return $status;
}//FECHA function

function gValidaCNPJ($RecebeCNPJ)
{
$valida=true;
//$RecebeCNPJ=${"CampoNumero"};

$s="";
for ($x=1; $x<=strlen($RecebeCNPJ); $x=$x+1)
{
$ch=substr($RecebeCNPJ,$x-1,1);
if (ord($ch)>=48 && ord($ch)<=57)
{
$s=$s.$ch;
}
}

$RecebeCNPJ=$s;

if (strlen($RecebeCNPJ)!=14)
{
  $valida=false;
//echo "<h1>É obrigatório o CNPJ com 14 dígitos</h1>";
}
else
if ($RecebeCNPJ=="00000000000000")
{
$then;
$valida=false;
//echo "<h1>CNPJ Inválido</h1>";
}
else
{
$Numero[1]=intval(substr($RecebeCNPJ,1-1,1));
$Numero[2]=intval(substr($RecebeCNPJ,2-1,1));
$Numero[3]=intval(substr($RecebeCNPJ,3-1,1));
$Numero[4]=intval(substr($RecebeCNPJ,4-1,1));
$Numero[5]=intval(substr($RecebeCNPJ,5-1,1));
$Numero[6]=intval(substr($RecebeCNPJ,6-1,1));
$Numero[7]=intval(substr($RecebeCNPJ,7-1,1));
$Numero[8]=intval(substr($RecebeCNPJ,8-1,1));
$Numero[9]=intval(substr($RecebeCNPJ,9-1,1));
$Numero[10]=intval(substr($RecebeCNPJ,10-1,1));
$Numero[11]=intval(substr($RecebeCNPJ,11-1,1));
$Numero[12]=intval(substr($RecebeCNPJ,12-1,1));
$Numero[13]=intval(substr($RecebeCNPJ,13-1,1));
$Numero[14]=intval(substr($RecebeCNPJ,14-1,1));

$soma=$Numero[1]*5+$Numero[2]*4+$Numero[3]*3+$Numero[4]*2+$Numero[5]*9+$Numero[6]*8+$Numero[7]*7+$Numero[8]*6+$Numero[9]*5+$Numero[10]*4+$Numero[11]*3+$Numero[12]*2;

$soma=$soma-(11*(intval($soma/11)));

if ($soma==0 || $soma==1)
{
$resultado1=0;
}
else
{
$resultado1=11-$soma;
}

if ($resultado1==$Numero[13])
{
$soma=$Numero[1]*6+$Numero[2]*5+$Numero[3]*4+$Numero[4]*3+$Numero[5]*2+$Numero[6]*9+$Numero[7]*8+$Numero[8]*7+$Numero[9]*6+$Numero[10]*5+$Numero[11]*4+$Numero[12]*3+$Numero[13]*2;
$soma=$soma-(11*(intval($soma/11)));
if ($soma==0 || $soma==1)
{
$resultado2=0;
}
else
{
$resultado2=11-$soma;
}

if ($resultado2==$Numero[14])
{
$valida=true;
//echo "<h1>CNPJ válido</h1>";
}
else
{
$valida=false;
//echo "<h1>CNPJ inválido</h1>";
}
}
else
{
$valida=false;
//echo "<h1>CNPJ inválido</h1>";
}
}
return $valida;
}

?>