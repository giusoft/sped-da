<?php

/**
 * Este arquivo contém métodos para criação de portais, como login, logout, register, etc.
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */

include_once $gPathDefault . "gMultiPage.php";

if ($includegApi <> 'false') {

	include_once $gPathDefault . "gApi.php";
}

/**
 * Classe responsável pela entrada de dados
 * @package	gInput
 * @author	Giuliano Nascimento <giusoft@hotmail.com>
 * @version	4.0 01-12-2013 10:50
 */
class gPortal extends gMultiPage
{
	public $linkEsqueceuSenha = '';
	function __construct($json)
	{
		parent::__construct($json);
		$this->linkEsqueceuSenha = 'index.php?g=forgot';
		if ($this->jarr['linkForgotPassword'] <> '') {
			$this->linkEsqueceuSenha = $this->jarr['linkForgotPassword'];
		}
	}

	/**
	 * Registro de um novo usuário
	 * @author	giuliano
	 * @global type $http_css
	 * @param type $json Parâmetros em formato JSON: style: horizontal ou vertical
	 * @param type $moreFields
	 * @return type
	 * @version	4.0 10-12-2013 10:50
	 */
	function register($json, $moreFields = "")
	{
		global $http_css;
		$jarr = cssDecode($json);
		$sai = '';
		$url = $this->page;
		$title = 'Acesso';
		$style = '1column';
		if ($jarr['title'] <> '') {
			$title = $jarr['title'];
		}
		if ($jarr['url'] <> '') {
			$url = $jarr['url'];
		}
		$debug = "false";
		if ($jarr['debug'] <> '') {
			$debug = $jarr['debug'];
		}
		if ($jarr['style'] <> '') {
			$style = $jarr['style'];
		}
		$size = '';
		if ($jarr['size'] <> '') {
			$size = $jarr['size'];
		}
		$nickname = true;
		if ($jarr['nickname'] == 'false')
			$nickname = false;
		$frm = new gForm("{title: $title; style: $style; size: $size; url: $url; columns: 1; debug: " . $debug . "}");
		if ($jarr['fullName'] == 'true') {
			$frm->add("{name: first_name; fieldLabel: first_name;type: text; allowBlank: false; value: " . $_REQUEST['first_name'] . "}");
			$frm->add("{name: middle_name; fieldLabel: middle_name;type: text; allowBlank: true; ; value: " . $_REQUEST['middle_name'] . "}");
			$frm->add("{name: last_name; fieldLabel: last_name;type: text; allowBlank: false; ; value: " . $_REQUEST['last_name'] . "}");
		} elseif ($jarr['alternativeName'] == 'true') {
			$frm->add("{name: name; type: text; allowBlank: false; fieldLabel: " . gT('Nome alternativo') . "; value: " . $_REQUEST['name'] . "}");
		} else {
			$frm->add("{name: name; type: text; allowBlank: false; fieldLabel: " . gT('Nome') . "; value: " . $_REQUEST['name'] . "}");
		}
		if ($nickname)
			$frm->add("{name: nickname; type: text; allowBlank: false; fieldLabel: " . gT('Apelido') . "; value: " . $_REQUEST['nickname'] . "}");
		$frm->add("{name: email; type: text; allowBlank: false; fieldLabel: " . gT('Login') . "; value: " . $_REQUEST['email'] . "}");
		$frm->add("{name: password; type: password; allowBlank: false; fieldLabel: " . gT('Senha') . "}");
		$frm->add("{name: confirm_password; type: password; allowBlank: false; fieldLabel: " . gT('Confirmação de senha') . "}");
		if (is_array($moreFields)) {
			foreach ($moreFields as $fld) {
				$frm->add($fld);
			}
		}
		if ($jarr['captcha'] == 'true')
			$frm->add("{name: captcha;type:recaptcha;}");
		if ($jarr['terms'] <> '') {
			$sai .= '<div id="regTerms" class="col-xs-12 col-sm-12 col-md-12 col-lg-12">';
			$cont = '<div style="height: 300px; overflow:auto" >' . file_get_contents($jarr['terms']) . '</div>';
			$sai .= $this->panel($cont, "Termos e condições gerais de uso");
			$sai .= $this->button("{title: Aceitar; tag: button; url: formOk();}");
			$sai .= '</div>';
			$sai .= '<div id="regForm" style="display: none;" class="col-xs-12 col-sm-6 col-md-6 col-lg-6">';
			$formOk = "
				function formOk()
				{
					document.getElementById('regTerms').style.display='none';document.getElementById('regForm').style.display='inline'
				}
				";
			$this->addJavascript($formOk);
			//            $frm->add("{name: terms; type: html; fieldName: Termos e condições de uso; value: <iframe src='".$jarr['terms']." class=\"img-responsive\" style=\"width: 100%; height: 300px\"'></iframe>}");
		}
		$out = $frm->render();
		$this->out($out[0], gLOC_PRE);
		$sai .= $out[1];
		$this->out($out[2], gLOC_POS);
		if ($jarr['terms'] <> '') {
			$sai .= '</div>';
		}
		return ($sai);
	}

	/**
	 * Janela de login
	 * @author	giuliano
	 * @global type $http_css
	 * @param type $json Parâmetros em formato JSON: style: dropdown, inline, default
	 * @return type
	 * @version	4.0 10-12-2013 10:50
	 */
	function login($json, $extraFields = "")
	{
		global $http_css, $gDevice;
		$jarr = cssDecode($json);
		$sai = '';
		$url = $this->page;
		$style = '1column';
		$fb = false;
		$twitter = false;
		$title = 'Acesso';
		if ($jarr['title'] <> '') {
			$title = $jarr['title'];
		}
		if ($jarr['url'] <> '') {
			$url = $jarr['url'];
		}
		if ($jarr['style'] <> '') {
			$style = $jarr['style'];
		}
		$debug = "false";
		if ($jarr['debug'] <> '') {
			$debug = $jarr['debug'];
		}
		$size = '';
		if ($jarr['size'] <> '') {
			$size = $jarr['size'];
		}
		if ($jarr['facebook'] == 'true') {
			$fb = true;
		}
		if ($jarr['twitter'] == 'true') {
			$twitter = true;
		}
		$emailType = "email";
		if ($jarr['emailCheck'] == 'false') {
			$emailType = "text";
		}
		$emailPlaceholder = gT("email");
		if ($jarr['emailPlaceholder'] != '') {
			$emailPlaceholder = $jarr['emailPlaceholder'];
		}
		$passwordPlaceholder = gT("password");
		if ($jarr['passwordPlaceholder'] != '') {
			$passwordPlaceholder = $jarr['passwordPlaceholder'];
		}
		switch ($jarr['layout']) {
			case 'dropdown':
				$sai .= '<a class="dropdown-toggle" data-toggle="dropdown" href="#loginMenu">' . $title . '<b class="caret"></b></a>' . $this->n;
				$sai .= '<div class="dropdown-menu">' . $this->n;
				$sai .= '<form class="form-signin" method="post" accept-charset="UTF-8" action="' . $url . '" method="post"><div style="margin:0;padding:0;display:inline"><input name="utf8" type="hidden" value="&#x2713;" /></div>' . $this->n;
				$sai .= '<div class="form-group">';
				$sai .= '<label for="email">' . gT("email.long") . '</label>';
				$sai .= '<input type="' . $emailType . '" id="email" name="email" required autofocus placeholder="' . $emailPlaceholder . '" />' . $this->n;
				$sai .= '</div>';
				$sai .= '<div class="form-group">';
				$sai .= '<label for="password">' . gT('password.long') . '</label>';
				$sai .= '<input type="password" id="password" name="password" required placeholder="' . $passwordPlaceholder . '" />' . $this->n;
				$sai .= '</div>';
				$sai .= '<button class="btn btn btn-primary btn-block" type="submit">' . gT("Acesso") . '</button>';
				$sai .= '<a href="' . $this->linkEsqueceuSenha . '">' . gT('forgot_password') . '</a>';
				$sai .= '</form>';
				$sai .= '</div>' . $this->n;
				$this->addJavascript("$('.dropdown-toggle').dropdown();");
				$this->addJavascript("$('.dropdown-menu').find('form').click(function (e) { e.stopPropagation(); });");
				break;

			case 'inline':
				$sai .= '<form class="navbar-form navbar-right" method="post" role="form" action="' . $url . '">' . $this->n;
				$sai .= '<div class="form-group">' . $this->n;
				$sai .= '<input type="' . $emailType . '" class="form-control" id="email" name="email" placeholder="' . $emailPlaceholder . '">' . $this->n;
				$sai .= '</div>' . $this->n;
				$sai .= '<div class="form-group">' . $this->n;
				$sai .= '<input type="password" class="form-control" id="password" name="password" placeholder="' . $passwordPlaceholder . '">' . $this->n;
				$sai .= '</div>' . $this->n;
				$sai .= '<button type="submit" class="btn btn-default">' . gT("Acesso") . '</button>' . $this->n;
				$sai .= '</form>' . $this->n;
				break;

			default:
				if ($_SESSION['gDevice'] == "mobile") {
					//echo '<i class="fa fa-camera-retro fa-5x"></i>';
					$frm = new gMinimal\gForm("{ name:login; action: $url;}");
					$frm->add("{fieldLabel: Apelido ; type: text; name:email}");
					$frm->add("{fieldLabel: Senha ; type: text; inputType:password; name:password}");
					$sai .= $frm->render();
				} else {
					// $sai .= '<form class="form-signin" role="form" action="' . $url . '">' . $this->n;
					// 	$sai .= '<h2 class="form-signin-heading">' . $title . '</h2>' . $this->n;
					// 	$sai .= '<div class="form-group">' . $this->n;
					// 		$sai .= '<label for="email">' . gT("E-mail") . '</label>';
					// 		$sai .= '<input type="text" id="email" class="form-control" placeholder="' . gT("E-mail") . '" autofocus>' . $this->n;
					// 	$sai .= '</div>' . $this->n;


					// 	$sai .= '<div class="form-group">' . $this->n;
					// 		$sai .= '<label for="password">' . gT('Senha') . '</label>';
					// 		$sai .= '<input type="password" id="password" class="form-control" placeholder="' . gT("password") . '">' . $this->n;
					// 	$sai .= '</div>' . $this->n;
					// 	$sai .= '<button class="btn btn-lg btn-primary btn-block" type="submit">' . gT("Acesso") . '</button>' . $this->n;
					// 	if ($jarr['forgot'] <> 'false')
					// 		$sai .= '<a href="' . $this->linkEsqueceuSenha . '">' . gT('Esqueceu a senha?') . '</a>';
					// $sai .= '</form>' . $this->n;


					$frm = new gForm("{url: $url; style: $style; forceSubmit: true; size: $size; debug: " . $debug . "}");
					if ($jarr['loginMethod'] == "nickname")
						$frm->add("{name: email; type: text; allowBlank: true; hint: " . gT('Apelido') . "; fieldLabel: " . gT('Apelido') . "}");
					else
						$frm->add("{name: email; type: " . $emailType . "; allowBlank: true; hint: " . $emailPlaceholder . "; fieldLabel: " . $emailPlaceholder . "}");

					$frm->add("{name: password; type: password; allowBlank: true; hint: " . $passwordPlaceholder . "; fieldLabel: " . $passwordPlaceholder . "}");
					if ($jarr['captcha'] == 'true')
						$frm->add("{name: captcha;type:recaptcha;}");
					if ($jarr['captchav2'] == 'true')
						$frm->add("{name: captcha;type:recaptchav2;}");
					if ($jarr['esqueceuSenha'] <> "false" && $jarr['forgot'] <> 'false')
						$frm->add("{name: label;type: label; value: <a href=\"" . $this->linkEsqueceuSenha . "\">" . gT('Esqueceu a senha?') . "</a>'}");
					if ($fb) {
						$fb = new gFacebook();
						$frm->addButton("{icon: facebook; title: 'Facebook'; type: a; style: info; href: " . $fb->getLoginUrl() . "}");
					}
					$frm->addButton("{icon: eye; title: 'Mostrar Senha'; style: default; showWait: false;>}", "togglePassword()");
					$this->addJavaScript('function togglePassword(){if($("#password").attr("type") == "text"){$("#password").attr("type","password")}else{$("#password").attr("type","text")}}');
					if ($twitter) {
						$js = "var keyword='';\nfunction twitter_connect() {    document.location.href='" . $this->page . "&action=twitter' }";
						$this->addJavaScript($js);
						//$frm->addButton("{title: 'Twitter'; style: info; url: twitter_connect()}");
						$frm->addButton("{icon: 'twitter'; title: 'Tw&iacute;tter'; type: a; style: info; href: index.php?g=signin&action=twitter}");
					}
					if ($extraFields <> "") {
						$frm->add($extraFields);
					}

					$out = $frm->render();
					$this->out($out[0], gLOC_PRE);
					$sai = $out[1]; //." <a class='btn btn-info' href='http://worldcupride.com/index.php?g=signin&action=twitter'>•</a>";
					$this->out($out[2], gLOC_POS);
				}
				break;
		}
		return ($sai);
	}

	/**
	 * Função logout
	 * @author	giuliano
	 * @param $json Parâmetros em formato JSON:
	 * 						style: horizontal ou vertical
	 * @version	4.0 10-12-2013 10:50
	 */
	function logout($json)
	{
	}

	/**
	 * Função pra lembrar a senha
	 * @author	giuliano
	 * @param $json Parâmetros em formato JSON:
	 * 						style: horizontal ou vertical
	 * @version	4.0 10-12-2013 10:50
	 */
	function remember($json)
	{
	}

	function submenu($json,$links = ""){
		if(method_exists(get_parent_class($this), 'submenu'))
			return parent :: submenu($json,$links);
	}

}
