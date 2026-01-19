<?
$ip = $_SERVER["REMOTE_ADDR"];
$SITE="emitenota";

$LOCALHOST = false;

$url = "localhost";
if ($_SERVER['SERVER_PORT']=='8080') {
        $url = "10.0.0.12";
        $LOCALHOST = true;
}

$EMPRESA = 'giusoft';
$AMBIENTE_TESTE = false;
$gBASE = "emitenota/$EMPRESA";
$gPathClasses = "/var/www/html/emitenota/" . $EMPRESA . "/src/Lib/";

define("NOME_CODIGO_EXTERNO","Cód.externo");


// if (substr($ip,0,7)=="192.168" || substr($ip,0,10)=="189.89.157")
// {
//         $gBASE = "wms/$EMPRESA";
// }
$gSETUP = "
global
{
	site: $SITE;
	theme: cosmo;
	bodyfont: Play;
	headersfont: Play;
	ssl: false;
	url: ;
	dateformat: dd-mm-yy;
	datenull: 0000-00-00;
	timeformat: hh:nn:ss;
	numformat: 0.000,00;
	keywords: giusoft;
	language: pt_BR;
	languages: pt_BR;
	charset: UTF-8;
	debug:false;
	icon: favicon.png;
	phone: 55 71 3402 0123;
	logo: logo.png;
	timeout: 300;
	logfile: wms_$EMPRESA.log;
	logcolor: true;
	align: center;
	safemode: on;
	translate: true;
	auto_translate: false;
	maxrows: 100;
	imagesbydb: false;
	tmp: pub/code;
	idd: false;
	chat: false;
}

database
{
	name: wms_$EMPRESA;
	transaction: true;
	charset: utf8mb4;
	user: web;
	password:web;
	engine: mysql;
	url: $url;
	port: 3306;
	maxcols: 8;
	maxrows: 100;
	id: id;
	idd: false;
	i18n: gfw_i18n;
	system: gfw_;
	stoponerror: true;
}


google {
	key: AIzaSyAFvZGR9LxG6MLGGxgYc47Jui-bclt6SWY;
	serverkey: AIzaSyCBMbRbBt-m531f2Ns1tnTeQjkw48HKLy0;
}

recaptcha {
    publicKey:6Lfx9fkSAAAAANc2i2wcMStEPqEraYPatosTZqM_;
    privateKey:6Lfx9fkSAAAAAFcvlKGY9EF2BBmtnXUY3beFV7MG;
}

smtp
{
	server: mail.giusoft.com.br;
	from: auto@giusoft.com.br;
	fromname: SAC;
	user: ;
	password: ;
}

pdf
{
	headersize: 48;
	color: blue;
	font: helvetica;
	logojpgfile: logo_pdf.jpg;
	logowidth: 20;
	logoheight: 10;
	logoalign: left;
	orientation: autodetect;
	footer1: '".strtoupper($EMPRESA)."';
	footer2: '';
	footer3: '';
}

lib
{
	bootstrap: bootstrap-3.3.4/;
	bootstrap_themes: bootstrap-themes/;
	bootstrap_addons: bootstrap-addons/;
	bootbox: bootbox-4.4.0/;
	moment: moment/moment-with-langs.min.js;
	sortable: sortable-master/;
	jasny: bootstrap-addons/jasny/;
	parsley: bootstrap-addons/Parsley.js-1.2.2/;
	select: bootstrap-addons/bootstrap-select/;
	selectize: selectize.js-master/;
	facebook: facebook-php-sdk-master/src/facebook.php;
	adodb_lite: adodb_lite/adodb.inc.php;
	adodb: adodb5/adodb.inc.php;
	jquery: jquery-2.1.0/jquery-2.1.0.min.js;
	xfont_awesome5: fonts/fontawesome-5.0.2/;
	font_awesome5: fonts/fontawesome-pro-5.3.1-web/;
	fpdf: fpdf181/;
	webcamjs: webcamjs-master/;
	jquery_mask: jquery-mask/dist/;
}

nfe
{
	senha: backuplogic;
	ambiente:2;
	regime:3;
	id_contador: 13937073000156;
        quagga: quagga/quagga.min.js;

}
";
