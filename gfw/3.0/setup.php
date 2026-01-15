<?
//date_default_timezone_set("Etc/GMT+3"); // Para Bahia e outros sem Horário de Verão //Transferido para o setup.php de cada cliente

define(gBASE,"gfwapp");

$gSetup="

global
{
	site: gFW Sample Application;
	url: www.giusoft.com.br;
	dateformat: dd-mm-yy;
	datenull: 0000-00-00;
	timeformat: hh:nn:ss
	numformat: 0.000,00;
	keywords: tecnologia,sistemas,software,hardware,consultoria,assessoria,voip,asterisk,ura,erp;
	language: pt_BR;
	charset: UTF-8;
	icon: gs.ico;
	phone: 55 71 2107 0330;
	logo: logo_app.jpg;
	timeout: 300;
	debug: 3;
	logfile: gfwapp.log;
	showcalendar: true;
	align: center;
	safemode: on;
}

database
{
	engine: mysqlt;
	url: localhost;
	port: 3306;
	name: gfwapp;
	user: root;
	password: ;
	maxcols: 8;
	maxrows: 20;
	id: id;
	idd: false;
}

smtp
{
	server: 200.254.1.134;
	from: suporte@giusoft.com.br;
	fromname: SAC GiuSoft;
	user: ;
	password: ;
}

page
{
	index: index.php;
	login: login.php;
	logout: login.php?t=out;
	error: login.php?t=err;
	expires: login.php?t=exp;
}

export
{
	doc: on;
	plan: on;
	pdf: on;
	csv: on;
	txt: on;
	html: on;
	rtf: on;
}

rtf
{
	font: arial;
}

csv
{
	delimiter:  ';';
}

pdf
{
	headersize: 48;
	color: blue;
	font: helvetica;
	logojpgfile: logo_pdf.jpg;
	logowidth: 37;
	logoheight: 10;
	logoalign: left;
	orientation: autodetect;
	footer1: 'Rua André Luiz R. da Fonte, 25. Salas 516/517. Pitangueiras. Lauro de Freitas/Bahia';
	footer2: 'TeleFax: 71 2107 0330 / E-mail: central@giusoft.com.br';
	footer3: www.giusoft.com.br;
}

txt
{
	heght: 66;
	marginbottom: 2;
	marginleft: 2;
	marginright: 2;
	margintop: 2;
	showfooter: on;
	showheader: on;
	width: 132;
}

pessoal
{
	ponto: on;
}

alitem
{
	db: alitem;
	dbhost: 200.254.1.200;
	dbuser: web;
	dbpassword: web;
}

openser
{
	host:sip.alitem.com.br;
	db:openser;
	user:root;
	password:SHarpGif85;
}

asterisk
{
	host: 200.254.1.135;
	port: 5038;
	db: asterisk;
	login: web;
	user: web;
}

lib
{
	adodb: adodb5/adodb.inc.php;
	extjs: 'ext-3.3.0/';
	jquery: jquery-1.3.1/jquery-1.3.1.js;
	jquery_plugins: jquery-1.3.1/;
	pg: pg/class.graphic.php;
	fpdf: fpdf16/fpdf.php;
	xmpphp: xmpphp-0.1rc2-r77/XMPPHP/XMPP.php;
	lib.gantt: ;
}
";

// ================== DONT CHANGE ABOVE THIS LINE ! ===========================
include_once $_SERVER['DOCUMENT_ROOT']."/".gBASE."/gfw/inc/gStart.php";

?>
