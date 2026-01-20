<?php

$html = $o->msgTitle("Users");
$exit = false;
$notChangedYet = 'not-changed-yet';

// Ações
switch ($gPage) {

	case 0: // Listagem de páginas e opção pra criar uma nova
		$html .= $o->button("{style: default; icon: plus; title: add; url: " . $o->page . "&gPage=1}");
		$html .= "<br>&nbsp;";
		if ($usrId == 1) {
      		$sql = "SELECT u.*
	  				FROM gfw_users u
					LEFT JOIN pessoas ON pessoas.id = u.id
					WHERE pessoas.motorista = 0
					ORDER BY u.name";
		} else {
			$sql = "SELECT u.*
					FROM gfw_users u
					LEFT JOIN pessoas ON pessoas.id = u.id
					WHERE u.id > 1 AND pessoas.motorista = 0
					ORDER BY u.name";
		}

		$rs = dbQuery($sql);

		if ($rs) {
			// Obtém os idiomas ativos
			$tab = '';
			$tab.=$o->tableBegin('big', true, false, true); // tamanho, borda, zebra, ordenável
			$mtz = [];
			$mtz[] = 'Opções';
			$mtz[] = 'Id';
			$mtz[] = 'Ativo';
			$mtz[] = '<-Nome';
			$mtz[] = '<-Apelido';
			$mtz[] = '<-Senha';
			$mtz[] = '<-E-mail';
			$mtz[] = 'Empresa';
			$mtz[] = 'Funcionário';
			$mtz[] = 'Cliente';
			$mtz[] = 'Fornecedor';
			$tab.=$o->tableRow($mtz, 'header');
			foreach ($rs as $field) {
				//echo '<pre>';var_dump($field);exit;
				$mtz = [];
				$btns = '';
				if ($field['id'] > 1) {
					if ($field['active'] == 0) {
						$btns.= $o->button("{style: danger; size: tiny; icon: thumbs-down; hint: Ativar; url: " . $o->page . "&gPage=3&gId=" . $field['id'] . "}");
					} else {
						$btns.= $o->button("{style: success; size: tiny; icon: thumbs-up; hint: Desativar; url: " . $o->page . "&gPage=3&gId=" . $field['id'] . "}");
					}
				}

				$btns.= $o->button("{style: info; size: tiny; icon: pencil; hint: Editar; url: " . $o->page . "&gPage=1&gId=" . $field['id'] . "}");
				$mtz[] = $btns;
				$mtz[] = $field['id'];
				$mtz[] = gCheck($field['active']);
				$mtz[] = '<-' . $field['name'] ;
				$mtz[] = '<-' . $field['nickname'];
				$mtz[] = '<-' . $field['password'];
				$mtz[] = '<-' . $field['email'];
				$mtz[] = gCheck($field['employer']);
				$mtz[] = gCheck($field['employee']);
				$mtz[] = gCheck($field['client']);
				$mtz[] = gCheck($field['partner']);
				$tab.=$o->tableRow($mtz);
			}

			$tab.=$o->tableEnd();
		} else {
			$tab = $o->msgAlert(gT('error_no_fields'));
		}

		$html.=$tab;
		break;


	case 1: // Novo registro
		$rs='';
		$$name = $nickname = $password = $confirm_password = $email = '';
		$admin = 0;
		$active = 1;
		$id_countries = 32;
		$signup_method = 'local';

		$pageTitle = 'New';
		if ($gId > 0) {
			$pageTitle='Edit';
			$sql = "SELECT p.* FROM gfw_users p WHERE p.id = " . $gId;
			$rs = dbQuery($sql);
			$name = $rs[0]['name'];
			$nickname = $rs[0]['nickname'];
			$email = $rs[0]['email'];
			$id_countries = $rs[0]['id_countries'];

			$admin = $rs[0]['admin'];
			$active = $rs[0]['active'];
			$employer = $rs[0]['employer'];
			$employee = $rs[0]['employee'];
			$client = $rs[0]['client'];
			$partner = $rs[0]['partner'];
			$password = $notChangedYet;
			$confirm_password = $notChangedYet;
		}

		$frm = new gForm("{title: ".$pageTitle."; style: 2column; }");
		$frm->setButtonBackCaption("back");

		$frm->add("{name: gPage; type: hidden; value: 2}");
		$frm->add("{name: gId; type: hidden; value: " . $gId . "}");
		if ($gId != 1) {
			$frm->add("{name: active; type: checkbox; value: " . $active . "}");
			$frm->add("{name: admin; type: checkbox; value: " . $admin . "}");
		}

		$frm->add("{name: employer; type: checkbox; value: " . $employer . "}");
		$frm->add("{name: employee; type: checkbox; value: " . $employee . "}");
		$frm->add("{name: client; type: checkbox; value: " . $client . "}");
		$frm->add("{name: partner; type: checkbox; value: " . $partner . "}");
		$frm->add("{name: name; fieldLabel: full_name; type: text; value: " . $name . "}");
		$frm->add("{name: nickname; type: text; value: " . $nickname . "}");
		$frm->add("{name: email; type: text; value: " . $email . "}");
		$frm->add("{name: id_countries; fieldLabel: country; type: combo; value: " . $id_countries . "; items: " . $sp['gfw_countries'] . "}");
		$frm->add("{name: password; type: password; value: " . $password . "}");
		$frm->add("{name: confirm_password; fieldLabel: confirm_password; type: password; value: " . $confirm_password . "}");

		$html .= $frm->render($o);
		break;

	case 2: // Tunnel (salvar/editar)

		$flds='';
		$flds['name']=gCleanField($_REQUEST['name']);
		$flds['nickname']=gCleanField($_REQUEST['nickname']);
		$flds['email']=gCleanField($_REQUEST['email']);
		$flds['password']=gCleanField($_REQUEST['password']);
		$flds['active']=gDBCheck($_REQUEST['active']);
		$flds['admin']=gDBCheck($_REQUEST['admin']);
		$flds['employer']=gDBCheck($_REQUEST['employer']);
		$flds['employee']=gDBCheck($_REQUEST['employee']);
		$flds['client']=gDBCheck($_REQUEST['client']);
		$flds['partner']=gDBCheck($_REQUEST['partner']);
		$flds['id_countries']=intval($_REQUEST['id_countries']);
		$flds['id_sales']=intval($_REQUEST['id_sales']);

		$errors=[];
		if (($_REQUEST['name']=='') || ($_REQUEST['nickname']=='') || ($_REQUEST['password']=='') ) {
			$errors[]=gT('error_all_fields');
		}

		if ($_REQUEST['password'] != $flds['password']) {
			$errors[]=gT('error_password_invalid');
		}

		if ($_REQUEST['password'] != $_REQUEST['confirm_password']) {
			$errors[]=gT('error_confirm_password');
		}

		if (!is_array($errors)) {

			if ($gId == 0) {
				$flds['idd']=intval($usrIdd);
				dbInsert('gfw_users',$flds);
			} else {
				if ($flds['password']==$notChangedYet) {
					unset($flds['password']);
				} else {
					$flds['password'] = md5((string) $flds['password']);
				}

				if (($gId>1) || ($usrId==1)) {
					// Só o root altera seus dados
					dbUpdate('gfw_users',$flds,$gId);
				}
			}

			redirect($o->page);
		} else {
			$html .= $o->h3('error');
			$html .= $o->msgError($o->ul($errors));
		}

		break;

	case 3: // Apagar (desativar)
		$sql="UPDATE pessoas set ativo=1-ativo WHERE id > 1 AND id = " . $gId;
		dbFastQuery($sql);
		redirect($o->page);
		break;

	case 4: // Admin ou não?
		$sql="UPDATE pessoas SET admin=1-admin WHERE id > 1 AND id = " . $gId;
		dbQuery($sql);
		redirect($o->page);
		break;
}
