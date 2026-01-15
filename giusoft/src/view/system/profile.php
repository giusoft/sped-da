<?
$html = $o->msgTitle("Perfil");

$pwd = 'xx_nao_alterada_xx';

switch($gPage) {
	//-----------------------------------------------------------------------------------------------
	case 0:
		$sql = "SELECT
					id,
					nickname,
					name,
					email,
					phone
				FROM gfw_users WHERE id=$usrId";
		$rs = dbFastQuery($sql);

		$frm = new gForm("{columns: 2}");

		$frm->add("{name: gPage; type: hidden; value: 1}");

		if ($usrClient == 1) {
			$frm->add("{fieldLabel: Senha; name: password; type: password; value: '$pwd'}");
			$frm->add("{fieldLabel: Confirmação da senha; name: confirm_password; type: password; value: '$pwd'}");
			$frm->add("{fieldLabel: E-mail; name: email; type: text; value: ".$rs[0]['email']."; hint: E-mail para contato}");
		} else {
			$frm->add("{fieldLabel: Apelido; name: apelido; type: text; value: ".$rs[0]['nickname']."}");
			$frm->add("{fieldLabel: Nome; name: nome; type: text; value: ".$rs[0]['name']."}");
			$frm->add("{fieldLabel: Senha; name: password; type: password; value: '$pwd'}");
			$frm->add("{fieldLabel: Confirmação da senha; name: confirm_password; type: password; value: '$pwd'}");
			$frm->add("{fieldLabel: E-mail; name: email; type: text; value: ".$rs[0]['email']."; hint: E-mail para contato}");
			$frm->add("{fieldLabel: Telefone(s); name: phone; type: text; value: ".$rs[0]['phone']."; hint: Telefone(s) principais}");
		}

		$html .= $frm->render($o);

		$html .= $o->msgSubTitle("Últimos acessos");
		$sql  = "SELECT date FROM gfw_access WHERE idd = $usrId AND login = 1 AND try = 0 ORDER BY id desc LIMIT 5";
		$rs   = dbFastQuery($sql);
		$perm = '';

		foreach ($rs as $row) {
			$perm .= tagMe('li', gDateTime($row['date']));
		}

		$html .= tagMe('ul', $perm);

		if ($usrId > 1) {
			$html.=$o->msgSubTitle("Permissões");
			$sql="SELECT DISTINCT p.name
					FROM gfw_permissions p
					LEFT JOIN gfw_permissions_users u ON u.id_gfw_permissions=p.id
					LEFT JOIN gfw_permissions_links l ON l.id_gfw_permissions=p.id
					WHERE u.id_gfw_users = $usrId";
			$rs   = dbFastQuery($sql);
			$perm = '';
			foreach ($rs as $row) {
				$perm .= tagMe('li', $row['name']);
			}
			$html .= tagMe('ul', $perm);

		}
		break;

	case 1:

		$erros = array();
		$flds  = array();

		$apelido	= gCleanField($_REQUEST['apelido']);
		$nome		= gCleanField($_REQUEST['nome']);
		$email		= gCleanField($_REQUEST['email']);
		$phone		= gCleanField($_REQUEST['phone']);

		$password			= gCleanField($_REQUEST['password']);
		$confirm_password	= gCleanField($_REQUEST['confirm_password']);

		$msgErro = verificarNomeOuApelidoReservado();
		if ($msgErro) {
			$html .= $o->msgDanger("<b>Erros encontrados: </b><br>" . implode("<br>",$msgErro));
			$html .= $o->backButton;
			break;
		}

		$t = $_FILES['logo'];

		if ($t['type']<>'image/jpeg' && $t['type']<>'image/png' && $t['type']<>'') {
			$erros[]="Arquivo de logomarca com formato inválido";
		}

		// Verifica se a senha foi alterada
		if ($password <> $pwd) {
			if ($password <> $confirm_password)
				$erros[] = gT("Password not match");
			else
				$pass = "password='".md5($password)."',";
		} else {
			$pass = '';
		}

		// Verifica se existe outro usuário com este e-mail
		$sql = "SELECT email FROM gfw_users WHERE email = '" . $email . "' AND id <> " . $usrId . " LIMIT 1";
		$rs  = dbFastQuery($sql)[0]['email'];
		if ($rs) {
			$erros[] = gT("E-mail already exists");
		}

		// Verifica se existe outro usuário com este apelido
		$sql = "SELECT nickname FROM gfw_users WHERE nickname = '" . $apelido . "' AND id <> " . $usrId . " LIMIT 1";
		$rs  = dbFastQuery($sql)[0]['nickname'];
		if ($rs) {
			$erros[] = gT("Nickname already exists");
		}

		if ($erros) {
			$msg = '';
			foreach ($erros as $erro) {
				$msg .= tagMe('li', $erro);
			}
			$html .= $o->msgSubTitle('Errors found!');
			$html .= $o->msgError(tagMe('ul',$msg));
		} else {
			//dbUpdate('gfw_users', $flds, $usrId);
			if ($usrClient == 1) {
				$sql = "UPDATE gfw_users SET $pass email = '" . strtolower($email) . "' WHERE id = " . $usrId;

			} else {
				$sql = "UPDATE gfw_users SET
							name = '" . gUcwords($nome) . "',
							$pass
							nickname = '" . $apelido . "',
							email    = '" . strtolower($email) . "',
							phone    = '" . ($phone) . "'
							WHERE id = " . $usrId;
			}

			dbFastQuery($sql);

			if ($t['tmp_name'] <> '') {
				// Antes de salvar apaga os registros antigos
				$logo = $gPathImg . 'logo_' . gVar("database.name");
				$ext  = array('png','jpg');
				foreach ($ext as $e) {
					if(file_exists($logo.'.'.$e)) {
						unlink($logo.'.'.$e);
					}
				}

				// Salva imagem
				$fileDownloaded = fileUpload('logo', 'logo_'.gVar("database.name"), $gPathImg);
			}
			redirect($o->page);
		}
		break;
}
?>
