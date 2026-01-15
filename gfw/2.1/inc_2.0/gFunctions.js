<!--
function gShowHide(whichLayer)
{
	if (document.getElementById)
	{
	// this is the way the standards work
	var style2 = document.getElementById(whichLayer).style;
	if (style2.display=="none")
		style2.display = "block";
	else
		style2.display = "none";
	}
	else if (document.all)
	{
	// this is the way old msie versions work
	var style2 = document.all[whichLayer].style;
	if (style2.display=="none")
		style2.display = "block";
	else
		style2.display = "none";
	}
	else if (document.layers)
	{
	// this is the way nn4 works
	var style2 = document.layers[whichLayer].style;
	if (style2.display=="none")
		style2.display = "block";
	else
		style2.display = "none";
	}
}
//********* VALIDA��O DE DATAS
// Funcao para testar se o caractere � um n�mero ou tbarra
function gIsValidKey( caractere,strValidos)
{
	if ( strValidos.indexOf( caractere ) == -1 )
		return false;
	return true;
}
// Funcao para s� deixar o usu�rio digitar n�meros
function gDateKeyCheck(campo, event, fmt)
{
	var BACKSPACE=8;
	var TAB=0;
	var key;
	var tecla;
	var strValidos = "0123456789" ;
	CheckTAB=false;
	if(navigator.appName.indexOf("Netscape")!= -1)
	  tecla= event.which;
	else
	  tecla= event.keyCode;
	key = String.fromCharCode( tecla);
	if ( tecla == 13 )
		return false;
	if ( tecla == BACKSPACE )
		return true;
	if ( tecla == TAB)
		return true;
	if ( tecla == 118) // Ctrl + V
		return true;
	if ( tecla == 99) // Ctrl + C
		return true;
	fmt=new String(fmt);
	tbarra='/';
	if (fmt.indexOf('-')>0) tbarra='-';
	strValidos=strValidos+tbarra;
	//alert( 'key: ' + tecla + '  -> tecla: ' + tecla);
	return ( gIsValidKey(key,strValidos));
}
function gDateVerify(o,fmt,nulo,msgerro)
{
	var tdia;
	var tmes;
	var tano;
	tano=0;
	tdata=o.value;
	tbarra='';
	if (tdata.indexOf('/')>0) tbarra='/';
	if (tdata.indexOf('-')>0) tbarra='-';
	if ((nulo) && (tdata==''))
	{
		tdata='';
	} else if (!(nulo) && (tdata==''))
	{
		var agora = new Date();
		var mNome = agora.getMonth() + 1;
		var dNome = agora.getDay() + 1;
		var NrDia = agora.getDate();
		var NrAno=agora.getYear();
		if(NrAno > 2000) 
			tano = 2000- NrAno;
		else 
			tano = NrAno;
		if (tano>100) tano=tano-100;
	 	if (mNome<10) mNome="0"+mNome;
	 	if (NrDia<10) NrDia="0"+NrDia;
	 	if (tano<10) tano="0"+tano;
		o.value=NrDia + "-" + mNome + "-" + tano;
	
		alert(msgerro);
		o.focus();
	} else
	{
		if (tbarra!='')
		{
			fmt=new String(fmt);
			fmts = fmt.split(tbarra);
			tflds = tdata.split(tbarra);
			tdia=-1;
			tmes=-1;
			tano=-1;
			thoje=new Date();
			fmtano='yy';
			for (a=0; a<fmts.length; a++)
			{
				lfmt=new String(fmts[a]);
				if (lfmt.indexOf('y')>-1) fmtano=lfmt;
			}
			for (a=0; a<tflds.length; a++)
			{
				lfmt=new String(fmts[a]);
				if (lfmt.indexOf('d')>-1) tdia=tflds[a];
				if (lfmt.indexOf('m')>-1) tmes=tflds[a];
				if (lfmt.indexOf('y')>-1)
				{
					if (tflds[a]=='')
					{
						tano=-1;
						tbarra='';
					}
					else
						tano=parseInt(tflds[a]);
				}
			}
			if (tano==-1)
			{
				tano=thoje.getYear();
				if (tano<999)
					tano=1900+tano;
				if (fmtano=='yy')
				{
					tano=new String(tano-2000);
					if (tano-2000<10)
						tdata=tdata.concat(tbarra,'0',tano);
					else
						tdata=tdata.concat(tbarra,tano);
				} else
					tdata=tdata.concat(tbarra,tano);
			}
			if (tano<999)
			{
				tano=(2000+tano);
			}
			tsituacao = "true";
			// verifica o tdia valido para cada tmes
			if ((tdia < 1)||(tdia < 1 || tdia > 30) && (  tmes == 4 || tmes == 6 || tmes == 9 || tmes == 11 ) || tdia > 31) {
				tsituacao = "falsa";
			}
			// verifica se o tmes e valido
			if (tmes < 01 || tmes > 12 ) {
				tsituacao = "falsa";
			}
			// verifica se e tano bissexto
			if (tmes == 2 && ( tdia < 1 || tdia > 29 || ( tdia > 28 && (parseInt(tano / 4) != tano / 4)))) {
				tsituacao = "falsa";
			}
			if (tdata== "") {
				tsituacao = "falsa";
			}
			if (tsituacao == "falsa") {
				alert(msgerro+' '+o.value+' ('+fmt+')');
				o.focus();
			} else
			{
				o.value=tdata;
			}
		} else
		{
				if (fmt.indexOf('/')>0) tbarra='/';
			if (fmt.indexOf('-')>0) tbarra='-';
			fmt=new String(fmt);
			fmts = fmt.split(tbarra);
			i=parseFloat(tdata);
			if(i>=10100)
			{
				s=i.toString();
				if (i>99999)
				{
					d1=s.substr(0,2);
					d2=s.substr(2,2);
					d3=s.substr(4,2);
				} else
				{
					d1=s.substr(0,1);
					d2=s.substr(1,2);
					d3=s.substr(3,2);
				}
				if (fmts[0].indexOf('d')>-1) tdia=parseFloat(d1);
				if (fmts[0].indexOf('m')>-1) tmes=parseFloat(d1);
				if (fmts[1].indexOf('d')>-1) tdia=parseFloat(d2);
				if (fmts[1].indexOf('m')>-1) tmes=parseFloat(d2);
				tano=100+parseFloat(d3);
			}
			else if(i>=101)
			{
				tdata=new Date();
				tano=tdata.getYear();
				s=i.toString();
				if (i>999)
				{
					d1=s.substr(0,2);
					d2=s.substr(2,2);
				} else
				{
					d1=s.substr(0,1);
					d2=s.substr(1,2);
				}
				if (fmts[0].indexOf('d')>-1) tdia=parseFloat(d1);
				if (fmts[0].indexOf('m')>-1) tmes=parseFloat(d1);
				if (fmts[1].indexOf('d')>-1) tdia=parseFloat(d2);
				if (fmts[1].indexOf('m')>-1) tmes=parseFloat(d2);
			}
			else if(i>0)
			{
				tdata=new Date();
				tdia=i;
				tano=tdata.getYear();
				tmes=tdata.getMonth()+1;
				//t=tdia+tbarra+tmes+tbarra+ano;
			}
			if (tano<1900)
				tano=tano-100;
			else
				tano=tano-2000;
			if (tano<10)
				tano='0'+tano;
			if (tmes<10)
				tmes='0'+tmes;
			if (tdia<10)
				tdia='0'+tdia;
			t='';
			//alert('tdia: '+ tdia+' tmes: '+tmes+' Ano: '+ano);
			for (a=0; a<fmts.length; a++)
			{
				lfmt=new String(fmts[a]);
				if (lfmt.indexOf('d')>-1) t=t+tdia+tbarra;
				if (lfmt.indexOf('m')>-1) t=t+tmes+tbarra;
				if (lfmt.indexOf('y')>-1)
				{
					if (lfmt=='yy')
						t=t+tano+tbarra;
					else
						if (tano>60)
							t=t+'19'+tano+tbarra;
						else
							t=t+'20'+tano+tbarra;
				}
			}
			t=t.substr(0,t.length-1);
			t1=t.split('-');
			tdia = t1[0];
			tmes = t1[1];
			tano = t1[2];
			tsituacao = "true";
			// verifica o tdia valido para cada tmes
			if ((tdia < 1)||(tdia < 1 || tdia > 30) && (  tmes == 4 || tmes == 6 || tmes == 9 || tmes == 11 ) || tdia > 31) {
				tsituacao = "falsa";
			}
			// verifica se o tmes e valido
			if (tmes < 01 || tmes > 12 ) {
				tsituacao = "falsa";
			}
			// verifica se e tano bissexto
			if (tmes == 2 && ( tdia < 1 || tdia > 29 || ( tdia > 28 && (parseInt(tano / 4) != tano / 4)))) {
				tsituacao = "falsa";
			}
			if (tdata== "") {
				tsituacao = "falsa";
			}
			if (tsituacao == "falsa") {
				alert(msgerro+' '+t+' ('+fmt+')');
				o.focus();
			} else
			{
				o.value=t;
			}

		}
	}
}
// Funcao para s� deixar o usu�rio digitar n�meros
function gDateTimeKeyCheck(campo, event, fmt)
{
	var BACKSPACE=8;
	var TAB=0;
	var key;
	var tecla;
	var strValidos = "0123456789: " ;
	CheckTAB=false;
	if(navigator.appName.indexOf("Netscape")!= -1)
	  tecla= event.which;
	else
	  tecla= event.keyCode;
	key = String.fromCharCode( tecla);
	if ( tecla == 13 )
		return false;
	if ( tecla == BACKSPACE )
		return true;
	if ( tecla == TAB)
		return true;
	if ( tecla == 118) // Ctrl + V
		return true;
	if ( tecla == 99) // Ctrl + C
		return true;
	//alert( 'key: ' + tecla + '  -> tecla: ' + tecla);
	fmt=new String(fmt);
	tbarra='/';
	if (fmt.indexOf('-')>0) tbarra='-';
	strValidos=strValidos+tbarra;
	return ( gIsValidKey(key,strValidos));
}
// Funcao para testar se o campo do formul�rio � uma Data e aceita nulo
function gDateTimeVerify(ob,fmt,nulo,msgerro)
{
	t=ob.value;
	xcnt=0;
	hora='';
	//ob.value='Teste';
	for (i=0; i<t.length; i++)
	{
		c = t.substring(i,i+1);
		if (c == ' ')
		{
			xcnt=i;
			break;
		}
	}
	if (xcnt>0)
	{
		hora=t.substring(cnt,t.length);
	}
	gDateVerify(ob,fmt,nulo,msgerro);
	t=ob.value;
	if (xcnt>0)
		ob.value=ob.value+hora;
	else
	{
		tdata=new Date();
		hor=tdata.getHours();
		min=tdata.getMinutes();
		seg=tdata.getSeconds();
		if (hor<10) hor='0'+hor;
		if (min<10) min='0'+min;
		if (seg<10) seg='0'+seg;
		tagora=hor+':'+min+':'+seg;
		ob.value=ob.value+' '+tagora;
	}
}
// Funcao para s� deixar o usu�rio digitar n�meros
function gNumKeyCheck(campo, event, fmt)
{
	var num;
	var key;
	var letter;
	var strValidos = "0123456789-," ;
	if(navigator.appName.indexOf("Netscape")!= -1)
	{
		num = String.fromCharCode(event.charCode);
		letter = event.charCode
	}
	else
	{
		num = String.fromCharCode(event.keyCode);
		letter = event.keyCode
	}
		
	key = event.keyCode;

	if("8,9,13,17,27,35,36,37,38,39,46,0".indexOf(key+",")>-1){
		return true
	}

	if (event.ctrlKey && letter == 118){ // Ctrl + V
		return true;
	}
	if (event.ctrlKey && letter == 99){ // Ctrl + C
		return true;
	}

	return ( gIsValidKey(num,strValidos));
}
// Funcao para s� deixar o usu�rio digitar n�meros e s�mbolos matem�ticos
function gCalcKeyCheck(campo, event, fmt)
{
	var BACKSPACE=8;
	var TAB=0;
	var key;
	var tecla;
	var strValidos = '0123456789-,*/+^' ;
	CheckTAB=false;
	if(navigator.appName.indexOf("Netscape")!= -1)
	  tecla= event.which;
	else
	  tecla= event.keyCode;
	key = String.fromCharCode( tecla);
	if ( tecla == 13 )
	{
		document.getElementById("gCalcDoBtn").focus();
		return false;
	}
	if ( tecla == BACKSPACE )
		return true;
	if ( tecla == TAB)
		return true;
	if ( tecla == 118) // Ctrl + V
		return true;
	if ( tecla == 99) // Ctrl + C
		return true;
	//alert( 'key: ' + tecla + '  -> tecla: ' + tecla);
	return ( gIsValidKey(key,strValidos));
}
// Funcao para testar se o campo do formul�rio � um n�mero inteiro
function gNumVerify(o,fmt,nulo,msgerro)
{
   erro = "0";
	f=o.value;
	if (f!='')
	{
		i=parseInt(f);
		o.value=i;
		i=o.value;
		if (i.substring(0,1)=='N' || i.substring(0,1)=='I'){
			o.focus();
			if (erro=="0"){
				alert(msgerro);
			}
			erro = "1";
		}
		o.value=f;
	}
	else
	{
		if (!nulo)
		{
			o.value='0';
		}
	}
}
function time_mask(hora){
	var myhora = '';
	myhora = myhora + hora;
	if (myhora.length == 2){
		myhora = myhora + ':';
		document.forms[0].hora.value = myhora;
	}
	if (myhora.length == 5){
		time_verify();
	}
}
function time_verify()
{
  hrs = (document.forms[0].hora.value.substring(0,2));
  min = (document.forms[0].hora.value.substring(3,5));
  alert('hrs '+ hrs);
  alert('min '+ min);
  tsituacao = "";
  // verifica data e hora
  if ((hrs < 00 ) || (hrs > 23) || ( min < 00) ||( min > 59)){
		tsituacao = "falsa";
  }
  if (document.forms[0].hora.value == "") {
		tsituacao = "falsa";
  }
  if (tsituacao == "falsa") {
		alert("Hora inválida!");
		document.forms[0].hora.focus();
  }
}
// Funcao para testar se o caractere � um n�mero
function isNum( caractere )
{
	var strValidos = "0123456789" ;
	if ( strValidos.indexOf( caractere ) == -1 )
		return false;
	return true;
}
// Funcao para testar se o caractere � um n�mero e alguns d�gitos
function isNum2( caractere )
{
	var strValidos = "0123456789.,/-:()" ;
	if ( strValidos.indexOf( caractere ) == -1 )
		return false;
	return true;
}
// Funcao para testar se o caractere � um n�mero
function isNum3( caractere )
{
	var strValidos = "0123456789.,-" ;
	if ( strValidos.indexOf( caractere ) == -1 )
	  return false;
	return true;
}
// Funcao para s� deixar o usu�rio digitar caracteres v�lidos .,/-:()
function validaTecla2(campo, event)
{
	var BACKSPACE=8;
	var key;
	var tecla;
	CheckTAB=true;
	if(navigator.appName.indexOf("Netscape")!= -1)
	  tecla= event.which;
	else
	  tecla= event.keyCode;
	key = String.fromCharCode( tecla);
	//alert( 'key: ' + tecla + '  -> campo: ' + campo.value);
	if ( tecla == 13 )
	  return false;
	if ( tecla == BACKSPACE )
	  return true;
	return ( isNum2(key));
}
// Funcao para s� deixar o usu�rio digitar caracteres v�lidos .,/-:()
function validaTecla3(campo, event)
{
	var BACKSPACE=8;
	var key;
	var tecla;
	CheckTAB=true;
	if(navigator.appName.indexOf("Netscape")!= -1)
	  tecla= event.which;
	else
	  tecla= event.keyCode;
	key = String.fromCharCode( tecla);
	//alert( 'key: ' + tecla + '  -> campo: ' + campo.value);
	if ( tecla == 13 )
	  return false;
	if ( tecla == BACKSPACE )
	  return true;
	return ( isNum3(key));
}
// Funcao para formatar o texto da caixa de texto para CNPJ
// Exemplo de uso:
// <input type="text" name="cnpj" size="18" maxlength="18" OnBlur="vCnpj(this)" onkeypress="return validaTecla(this, event)">
function vCNPJ( el )
{
	if (el.value=="")
		el.value="00.000.000/0000-00";
	vr = el.value;
	vr=vr.replace(".","");
	vr=vr.replace(".","");
	vr=vr.replace(".","");
	vr=vr.replace(".","");
	vr=vr.replace("/","");
	vr=vr.replace("-","");

	tam = vr.length;
	if ( vr.indexOf(".") == -1 )
	{
		if ( tam <= 2 )
				  el.value = vr;
		if ( (tam > 2) && (tam <= 6) )
				  el.value = vr.substr( 0, 2 ) + '.' + vr.substr( 2, tam );
		if ( (tam >= 7) && (tam <= 10) )
				  el.value = vr.substr( 0, 2 ) + '.' + vr.substr( 2, 3 ) + '.'+ vr.substr( 5, 3 ) + '/';
		if ( (tam >= 11) && (tam <= 18) )
				 el.value = vr.substr( 0, 2 ) + '.' + vr.substr( 2, 3 ) + '.' + vr.substr( 5, 3 ) + '/' + vr.substr( 8, 4 ) + '-' + vr.substr( 12, 2 ) ;
	}
	vr=el.value;
	if ((vr!="00.000.000/0000-00") && (vr!=""))
	{
		CPFCGC = vr.substr(0,16);
		tmp="";
		for (var i=0;i<CPFCGC.length-1; i++) {
		 s=CPFCGC.substring(i,i+1);
		 if ((s!="/")&&(s!="."))
			tmp = tmp + s;
		}
		CPFCGC=tmp;
		temp = 0
		potencia = 1
	  for (var i=CPFCGC.length-1;i>=0; i--)
	  {
			if ( parseInt(CPFCGC.substring(i,i+1),10) == CPFCGC.substring(i,i+1) )
			{
				temp = temp + parseInt(CPFCGC.substring(i,i+1),10)* potencia
				potencia = potencia *10
			}
		}
		CPFCGC = temp + "";
		tam=CPFCGC.length;
		temp="000000000000" + CPFCGC;
		CPFCGC=temp.substring(tam,tam+12);
		// rotina para gerar CGC
		somacgc = 0;
		for (var i = 0; i<4; i++)
		{
			somacgc = somacgc + CPFCGC.substring(i,i+1)*(5-i)
		}
		for (var i = 4; i<12; i++)
		{
			somacgc = somacgc + CPFCGC.substring(i,i+1)*(13-i)
		}
		cgcdv = 11 - (somacgc % 11)
		if ( cgcdv == 10 )
		{
			cgcdv = 0
		}
		somacgc = 0;
		for (var i = 0; i<5; i++)
		{
			somacgc = somacgc + CPFCGC.substring(i,i+1)*(6-i)
		}
		for (var i = 5; i<12; i++)
		{
			somacgc = somacgc + CPFCGC.substring(i,i+1)*(14-i)
		}
		somacgc = somacgc + cgcdv * 2;
		cgcdv2 = 11 - (somacgc%11)
		if ( cgcdv2 == 10 )
		{
			cgcdv2 = 0
		}
		cgcdv = ( cgcdv * 10 ) + cgcdv2
		dig=parseFloat(vr.substr(16,2),10);
		if (dig!=cgcdv)
		{
			//alert("CNPJ Inválido ! " );
			//el.select();
		}
	}
	return true;
}
function isNumber(text)
{
	for (var i = 0; i <= text.length-1; i++)
	{
		if (isNaN(parseInt(text.substring(i,i+1))))
		{
			  return (false);
		}
	}
	return (true);
}
// Funcao para formatar o texto da caixa de texto para CPF
// Exemplo de uso:
// <input type="text" name="cpf" size="18" maxlength="18" OnBlur="vCpf(this)" onkeypress="return validaTecla(this, event)">
function vCPF( el ){
	var numeros, digitos, soma, i, resultado, digitos_iguais, cpf=el.value, valido=true;
	digitos_iguais = 1;
	if (cpf.length < 11)
	{
		valido = false;
	}
	else
	{
		for (i = 0; i < cpf.length - 1; i++)
		{
			if (cpf.charAt(i) != cpf.charAt(i + 1))
			{
				digitos_iguais = 0;
				break;
			}
		}
		if (!digitos_iguais)
		{
			numeros = cpf.substring(0,9);
			digitos = cpf.substring(9);
			soma = 0;
			for (i = 10; i > 1; i--)
			{
				soma += numeros.charAt(10 - i) * i;
			}
			resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
			if (resultado != digitos.charAt(0))
			{
				valido = false;
			}
			numeros = cpf.substring(0,10);
			soma = 0;
			for (i = 11; i > 1; i--)
			{
				soma += numeros.charAt(11 - i) * i;
			}
			resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
			if (resultado != digitos.charAt(1))
			{
				valido = false;
			}
		}
		else
		{
			valido = false;
		}
	}
	if(!valido)
	{
		alert("CPF Inválido !" );
		el.select();
		return false;
	}
	return true;
}

function vCPFold( el )
{
	vr = el.value;
	tam = vr.length;
	if ( vr.indexOf(".") == -1 )
	{
		if ( tam < 11 )
		{
				  vr = '00000000000' + vr;
				  vr = vr.substr(tam,11);
				  tam = vr.length
		}
		if ( (tam >= 11) )
    {
			el.value = vr.substr( 0, 3 ) + '.' + vr.substr( 3, 3 ) + '.' +
			vr.substr( 6, 3 ) + '-' + vr.substr( 9, 2 );
		}
	}
	vr=el.value;
	if (vr!="000.000.000-00")
	{
	  CPFCGC = vr.substr(0,12);
		//784.998.955-49
	  temp = 0
	  potencia = 1
	  for (var i=CPFCGC.length-1;i>=0; i--)
	  {
			if ( parseInt(CPFCGC.substring(i,i+1),10) == CPFCGC.substring(i,i+1) )
			{
				temp = temp + parseInt(CPFCGC.substring(i,i+1),10)* potencia
				potencia = potencia *10
			}
		}
		CPFCGC = temp + "";
		// rotina para testar CPF
		tam=CPFCGC.length;
		temp="000000000" + CPFCGC;
		CPFCGC=temp.substring(tam,tam+9);
		somacpf = 0;
		for (var i = 0; i<9; i++)
		{
			somacpf = somacpf + CPFCGC.substring(i,i+1)*(10-i)
		}
		cpfdv = 11 - (somacpf % 11)
		if ( cpfdv == 10 )
		{
			cpfdv = 0
		}
		somacpf = 0;
		for (var i = 0; i<9; i++)
		{
			somacpf = somacpf + CPFCGC.substring(i,i+1)*(11-i)
		}
		somacpf = somacpf + cpfdv * 2;
		cpfdv2 = 11 - (somacpf%11)
		if ( cpfdv2 == 10 )
		{
			cpfdv2 = 0
		}
		cpfdv = ( cpfdv * 10 ) + cpfdv2
		dig=parseFloat(vr.substr(12,2),10);
		if (dig!=cpfdv)
		{
			 alert("CPF Inválido !" );
			 el.select();
		}
	}
  return true;
}
//
//
// Funcao para mostrar a data atual na janela
//
//
function ShowDate()
{
		var agora = new Date();
		var mNome = agora.getMonth() + 1;
		var dNome = agora.getDay() + 1;
		var NrDia = agora.getDate();
		var NrAno=agora.getYear();
		if(dNome==1) tdia = "Domingo";
		if(dNome==2) tdia = "Segunda";
		if(dNome==3) tdia = "Ter&ccedil;a";
		if(dNome==4) tdia = "Quarta";
		if(dNome==5) tdia = "Quinta";
		if(dNome==6) tdia = "Sexta";
		if(dNome==7) tdia = "S&aacute;bado";
		if(NrAno < 2000) tano = 1900 + NrAno;
		else tano = NrAno;
	 	var thoje =(" " + tdia + ", " + NrDia + "/" + mNome + "/" + tano);
		document.write("&nbsp;" + thoje + " - " + agora.getHours() + ":" + agora.getMinutes());
}
// Funcao para verificar e mostrar a vers�o do navegador
function version()
{
	s = navigator.appName + " v. " + navigator.appVersion;
	if(parseInt(navigator.appVersion)<4)
	{
 		alert("Você está usando um navegador muito antigo, você não terá acesso a alguns recursos do site !");
	}
	document.write(s);
	return;
}
// Funcao para testar se o campo do formul�rio � uma Hora
function vTime(campo)
{
	valor = campo.value;
	if (valor.length >= 5)
	{
		for (i = 1 ; i <= valor.length ; i++)
		{
			c = valor.substring(i-1,i);
			if (((c >= 0) == false) && (c != ":"))
			{
				alert("Hora Inválida");
				campo.select();
				break;
			}
		}
	}
	else if (valor.length==1)
	{
		campo.value="0"+valor+":00";
	} else if (valor.length==2)
	{
		campo.value=valor+":00";
	} else if (valor.length==3)
	{
		campo.value="0"+valor.substring(0,1)+":"+valor.substring(1,3);
	} else if (valor.length==4)
	{
		campo.value=valor.substring(0,2)+":"+valor.substring(2,4);
	} else if (valor.length>0)
	{
		
	 	alert("Hora Inválida");
	 	campo.select();
	 	return;
 	}
}
// Funcao para testar se o campo do formul�rio � um e-mail
function vEmail(campo)
{
	valor = campo.value;
	arr = false;
	ponto=0;
	if (valor.length >= 7)
	{
		for (i = 1 ; i <= valor.length ; i++)
		{
			c = valor.substring(i-1,i);
			if (c == "@")
			{
					arr=true;
			}
			if (c == ".") 	{
				ponto=ponto+1;
			}
		}
	}	else {
	 	alert("E-mail inválido");
	 	campo.select();
	 	return;
 	}
 	if ((arr == false) || (ponto==0))
 	{
 	  alert("E-mail inválido");
	 	campo.select();
	 	return;
 	}
}
// Funcao para testar se o campo do formul�rio � um valor financeiro
function vMoney(campo)
{
//critica numero tipo float
	valor = campo.value;
	for (i=1; i<=valor.length; i++)
	{c = valor.substring(i-1,i);
		if (((c >= 0) == false) && (c != ".") && (c != ","))
		{
			alert("Valor Inválido");
			campo.select();
			break;
		}
	}
}
// Funcao para testar se o campo do formul�rio � um n�mero inteiro
function vNumInt(o){
   erro = "0";
	f=o.value;
	if (f!=''){						//Se nao estiver vazio
		i=parseInt(f);
		o.value=i;
		i=o.value;
		if (i.substring(0,1)=='N' || i.substring(0,1)=='I'){
			o.focus();
			if (erro=="0"){
				alert('Número inválido: '+f);
			}
			erro = "1";
		}
		o.value=f;
	}
	else{
		o.value='0';
		erro = "0";
	}
}
// Funcao para testar se o campo do formul�rio � um n�mero real
function vNumReal(o){
    erro = "0";
	f=o.value;
	if (f!=''){						//Se nao estiver vazio
		i=parseFloat(f);
		o.value=i;
		i=o.value;
		if (i.substring(0,1)=='N' || i.substring(0,1)=='I'){
			o.focus();
			if (erro=="0"){
				alert('Número inválido: '+f);
			}
			erro = "1";
		}
		o.value=f;
	}
	else{
		// o.value='0'; � melhor n�o completar com zero pq em JavaScript s� validamos o vazio
		erro = "0";
	}
}
// Funcao para formatar uma string em mai�sculas
function vUText(o)
{
	t=o.value;
	o.value=t.toUpperCase();
}
// Funcao para formatar uma string em min�sculas
function vLText(o)
{
	t=o.value;
	o.value=t.toLowerCase();
}
// Funcao para formatar uma string com a primeira letra em mai�scula
function vUFText(o)
{
	t=o.value;
	o.value=t.substr(0,1).toUpperCase()+t.substr(1,200);
}
// Funcao para formatar uma string com a primeira letra de cada palavra em mai�scula
function vUFWText(o)
{
	var sai='';
	t=o.value;
	mai=1;
	for (a=0; a<t.length; a++)
	{
		if (mai==1)
		{
			if ((a<t.length-3) && (a>0))
			{
				if ((t.substr(a-1,4).toLowerCase()==' de ') || (t.substr(a-1,4).toLowerCase()==' da ') || (t.substr(a-1,4).toLowerCase()==' do ')|| (t.substr(a-1,5).toLowerCase()==' das ')|| (t.substr(a-1,5).toLowerCase()==' dos ') || (t.substr(a-1,3).toLowerCase()==' a ')  || (t.substr(a-1,3).toLowerCase()==' e ')  || (t.substr(a-1,3).toLowerCase()==' o ') )
					sai=sai+t.substr(a,1).toLowerCase();
				else
					sai=sai+t.substr(a,1).toUpperCase();
			} 
			else
				sai=sai+t.substr(a,1).toUpperCase();
		}
		else
			sai=sai+t.substr(a,1).toLowerCase();
		mai=0;
		if ((t.substr(a,1)==' ') || (t.substr(a,1)=='-') || (t.substr(a,1)==';') || (t.substr(a,1)==',') || (t.substr(a,1)=='.') || (t.substr(a,1)=='_') || (t.substr(a,1)=='/')) 
			mai=1;
	}
	o.value=sai;
}
// Funcao para formatar uma string em min�sculas
function vPlate(o)
{
	t=o.value;
	o.value=t.toUpperCase();
}
// Resize o Iframe
function ResizeFrame(Objeto){
	// Tamanho da p�gina
  	var Altura = document.getElementById(Objeto).contentWindow.document.body.scrollHeight;
  	// Resize o iframe
  	document.getElementById(Objeto).height = Altura;
}
/*
//valida formul�rios
//Obs.: Um campo do tipo hidden no formulario com os nomes dos campos obrigat�rios
//separados por pelo caracter |
//exemplo:
//texto;nome_campo_1|texto;nome_campo_2
//ou nome_campo_1|nome_campo_2
function ValidFormulario(Form)
{
	var Field,Fields,nF,i,Item;
	Fields = Form.fields.value;
	Field = Fields.split('|');
	nF = Field.length;
	for(i=0;i<nF;i++){
		Item = Field[i].split(';');
		if(Item.length>1){
			if(document.getElementById(Item[1]).value==""){
				alert('O campo '+Item[0]+' � de preenchimento obrigat�rio!');
				document.getElementById(Item[1]).focus();
				document.getElementById(Item[1]).select();
				return false;
			}
		}else{
			str = Item[0].substring(0,1);
			Campo = Item[0].replace(str, str.toUpperCase());
			if(document.getElementById(Item[0]).value==""){
				alert('O campo '+Campo+' � de preenchimento obrigat�rio!');
				document.getElementById(Item[0]).focus();
				document.getElementById(Item[0]).select();
				return false;
			}
		}
	}
	return false;
}
*/
//-->
