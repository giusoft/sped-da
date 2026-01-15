<?php

//error_reporting(E_ALL^E_NOTICE^E_WARNING^E_DEPRECATED);
//error_reporting(E_ALL);
include_once $gPathDefault."gMultiPage.php";
include_once $gPathDefault."gEmail.php";
include_once $gPathDefault."gNet.php";
include_once $gPath."res/config.php";
define ("XML_TAG",'<?xml version="1.0" encoding="UTF-8"?>');
define ("NL","\n");

function ownerData()
{
	$sql="select p.*, f.*, j.*, e.*,
				cid.descricao cidade,
				est.descricao estado,
				pai.nome pais
			from alitem.pessoas p
			left join alitem.pessoas_fisicas f on p.id=f.id_pessoas
			left join alitem.pessoas_juridicas j on p.id=j.id_pessoas
			left join alitem.pessoas_enderecos e on p.id=e.id_pessoas
			left join alitem.cidades cid on cid.id=e.id_cidades
			left join alitem.estados est on est.id=e.id_estados
			left join alitem.paises pai on pai.id=e.id_paises
			where p.id=".$_SESSION['usrIdd'];
	$rs=gFastQuery($sql);
	$sai=$rs->fields;
	unset($sai['id']);
	unset($sai['id_pessoas']);
	unset($sai['id_cidades']);
	unset($sai['id_estados']);
	unset($sai['id_paises']);
	foreach ($sai as $key=>$value)
		if (is_numeric($key))
			unset($sai[$key]);

	return($sai);
}

function contactData($id)
{
	$sql="select * from pessoas where id=".intval($id);
	$rs=gQuery($sql);
	$sai=$rs->fields;
	unset($sai['id']);
	foreach ($sai as $key=>$value)
		if (is_numeric($key))
			unset($sai[$key]);

	$sql="select * from alitem.pessoas where id=".intval($id);
	$rs=gQuery($sql);
	$sai['data_cadastro']=$rs->fields['data_cadastro'];
	$sai['data_ativacao']=$rs->fields['data_ativacao'];
	$sai['id_pessoas_criou']=$rs->fields['id_pessoas_criou'];

	$sql="select * from pessoas_complemento where id_pessoas=".intval($id);
	$rsc=gQuery($sql);
	if (!$rsc->EOF)
	{
		$mtz=cssDecode($rsc->fields['dados_adicionais']);
		$form=explode("},{",$rsc->fields['formulario']);
		//$dados.="<hr>".$rs->fields['formulario']."<hr>";
		$frm='';
		foreach ($form as $f)
		{
			$f='{'.$f.'}';
			$f=str_replace("{{","{",$f);
			$f=str_replace("}}","}",$f);
			$fmtz=cssDecode($f);
			if ($fmtz['fieldLabel']<>'')
				$frm[$fmtz['name']]=$fmtz['fieldLabel'];
			else
				$frm[$fmtz['name']]=$fmtz['name'];
			$mtz[$fmtz['name']]=$mtz[$fmtz['name']];
			//$dados.="==> $f ==> ".$fmtz['name']." = ".$fmtz['fieldLabel']."<br>";
		}
		if (is_array($mtz))
		{
			foreach ($mtz as $key=>$value)
			{
				$sai[$key]=$value;
			}
		}
	}
	return($sai);
}

function gPeople($flt="",$ord="")
{

	$usrIdd=intval($_SESSION['usrIdd']);
	$gApp=$_SESSION['gApp'];
	if ($gApp==-1)
	{
		$gApp=$_SESSION['appDevel'];
	}

	$sql="select p.id idp, p.*,c.*,
			prm.colaborador,prm.consumidor as cliente,prm.consumidor,prm.fornecedor,
			e.bairro,cid.descricao cidade, est.descricao estado
			from alitem.pessoas p
			left join pessoas_complemento c on p.id=c.id_pessoas
			left join alitem.pessoas_enderecos e on p.id=e.id_pessoas
			left join alitem.cidades cid on e.id_cidades=cid.id
			left join alitem.estados est on e.id_estados=est.id
			left join alitem.pessoas_permissoes_aplicativos prm on prm.id_pessoas_relacionado=p.id
			left join alitem.aplicativos a on prm.id_aplicativos=a.id
			where prm.id_aplicativos=$gApp and prm.id_pessoas=$usrIdd";
	if (is_array($flt))
	{
		$sql.=" and ".implode($flt," and ");
	} elseif ($flt<>"")
	{
		$sql.=" and ".$flt;
	}
	if (is_array($ord))
	{
		$sql=" order by ".implode($ord,", ");
	} elseif($ord<>"")
	{
		$sql.=" order by $ord";
	}
	$rs=gFastQuery($sql);
	while (!$rs->EOF)
	{
		// Varre todo o recordset pra corrigir e implementar campos

		// Tira campos com nome numérico
		$rec=$rs->fields;
		foreach ($rec as $key=>$value)
		{
			if ((is_numeric($key)) || (substr($key,0,6)=="imagem")  || ($key=="idd") || ($key=="formulario") || ($key=="dados_adicionais"))
				unset($rec[$key]);
		}
		$rec['id']=$rec['idp'];
		unset($rec['idp']);
		// Adiciona campos complementares
		$mtz=cssDecode($rs->fields['dados_adicionais']);
		$form=explode("},{",$rs->fields['formulario']);
		$frm='';
		$cntVal=0;
		foreach ($form as $f)
		{
			$f='{'.$f.'}';
			$f=str_replace("{{","{",$f);
			$f=str_replace("}}","}",$f);
			$fmtz=cssDecode($f);
			$rec[$fmtz['name']]=$mtz[$fmtz['name']];
			$cntVal++;
		}
		$sai[]=$rec;
		$rs->MoveNext();
	}
	return($sai);
}

function gPeopleData($id)
{
	include "../../res/config.php";
	$gApp=$_SESSION['gApp'];
	if ($gApp==-1)
	{
		$gApp=$_SESSION['appDevel'];
	}
	$data=contactData($id);
	$core=new gCore();
	$data2=$core->getAppPermissions($id,$gApp);
	$data2=$data2[$gApp];
	$data[gT('colaborador')]=$data2['colaborador'];
	$data[gT('cliente')]=$data2['consumidor'];
	$data[gT('fornecedor')]=$data2['fornecedor'];
	return ($data);
}

/** Class de ferramentas para o Alitem
 * @package	gAlitem
 * @author	giuliano
 * @version	1.0 30-10-2011 10:57
 */
class gPeopleConnections extends gMultiPage
{

	public $contato='';
	public $complement='';

	function is($tipo)
	{
		$ali=new gCore();

		$sai=false;
		if (strtolower($tipo)==gT("dono"))
		{
			$sai=$_SESSION['usrId']==$_SESSION['usrIdd'];
		}
		if (strtolower($tipo)==gT("colaborador"))
		{
			$p=$_SESSION['usrPerm'];
			$sai=(substr($p,1,1)=='1');
		}
		if ((strtolower($tipo)==gT("consumidor")) || (strtolower($tipo)==gT("cliente")))
		{
			$p=$_SESSION['usrPerm'];
			$sai=(substr($p,2,1)=='1');
		}
		if (strtolower($tipo)==gT("fornecedor"))
		{
			$p=$_SESSION['usrPerm'];
			$sai=(substr($p,3,1)=='1');
		}
		return ($sai);
	}

	/** Convida um usuário que já existe no Alitem
	 * @author	giuliano
	 * @version	1.0 30-10-2011 10:57
	 * @param id integer Id do usuário a ser convidado
	 */
	function invite($id,$msg='',$login='', $accepted=false)
	{
		global $http_base;
		$usrIdd=$_SESSION['usrIdd'];
		$hoje=date('Y-m-d H:i:s');
		$sql="select * from alitem.pessoas_relacionamentos where id_pessoas=$usrIdd and id_pessoas_relacionado=$id";
		$rs=gFastQuery($sql);
		$nivel="0";
		if ($rs->EOF)
		{
			$tipo="0";
			if ($accepted)
			{
				$nivel="2";
				$sql="insert into alitem.pessoas_relacionamentos
						(idd,id_pessoas,id_pessoas_relacionado,data,data_aceite,nivel,tipo) values
						($id,$id,".$usrIdd.",'$hoje','$hoje',$nivel,1)";
				gFastQuery($sql);
				$tipo="1";
			}
			$sql="insert into alitem.pessoas_relacionamentos (idd,data,id_pessoas,id_pessoas_relacionado,nivel,tipo) values ($usrIdd, '$hoje',$usrIdd,$id,$nivel,$tipo)";
			gFastQuery($sql);

			$sql="select email from alitem.pessoas where id=$id";
			$rsp=gFastQuery($sql);
			$remetente="auto@alitem.com.br";
			$para=$rsp->fields['email'];
			$cc="";
			if ($login=='')
			{
				// Convite a um usuário já cadastrado
				$assunto=$_SESSION['usrIddName']." te convidou";
				$conteudo=$_SESSION['usrIddName']." te convida a se relacionar no portal de aplicativos .oO Alitem.<br><br>Assim que for possível, confirme ou cancele o pedido.<br>".$msg."<br><br>Saudações,<br><br>Equipe Alitem<br>http://www.alitem.com.br";
			} else
			{
				// Convite com cadastro efetuado no hora
				$assunto=$_SESSION['usrIddName']." te convidou";
				$conteudo=$_SESSION['usrIddName']." te convida a se relacionar no portal de aplicativos .oO Alitem.<br><br>Algumas informações suas foram pré-cadastradas e seu login criado<br><br>$login<br><br>Assim que for possível, acesse o nosso portal, complete seus dados e aceite o convite de ".$_SESSION['usrIddName'].".<br>".$msg."<br><br>Saudações,<br><br>Equipe Alitem<br>http://www.alitem.com.br";

			}
			if (($para<>"") && (stripos($http_base,"127.0.0.1")===false))
				gSendEmail($remetente,$para,$cc,$assunto,$conteudo,"","");
		}
	}

	/** Define ou remove um contato como favorito
	 * @author	giuliano
	 * @version	1.0 22-02-2012 13:56
	 * @param id integer Id do usuário a ser definido
	 */
	function favorite($id)
	{
		$usrIdd=$_SESSION['usrIdd'];
		$sql="update alitem.pessoas_relacionamentos set favorito=1-favorito where id_pessoas=$usrIdd and id_pessoas_relacionado=$id";
		gFastQuery($sql);
	}

	/** Convida um usuário que não existe no Alitem
	 * @author	giuliano
	 * @version	1.0 30-10-2011 10:57
	 * @param data array Dados do contato
	 */
	function inviteNew()
	{

	}

	/** Cancela o convite para ambos os lados
	 * @author	giuliano
	 * @version	1.0 30-10-2011 10:57
	 * @param id integer Id do usuário (não o atual)
	 */
	function inviteCancelBoth($id,$msg='')
	{
		global $http_base;
		$usrId=$_SESSION['usrId'];
		$hoje=date('Y-m-d H:i:s');

		$sql="delete from alitem.pessoas_relacionamentos where (id_pessoas_relacionado=$usrId and id_pessoas=".$id.") or (id_pessoas=$usrId and id_pessoas_relacionado=".$id.")";
		$rst=gFastQuery($sql);

		$sql="select nome,email from alitem.pessoas where id=".$id;
		$rsp=gFastQuery($sql);
		$remetente="auto@alitem.com.br";
		$para=$rsp->fields['email'];
		$cc="";
		$assunto=$_SESSION['usrName']." cancelou o relacionamento contigo";
		$conteudo=$_SESSION['usrName']." cancelou o relacionamento que havia contigo no portal de aplicativos .oO Alitem.<br>$msg<br><br>Saudações,<br><br>Equipe Alitem<br>http://www.alitem.com.br";
		if (($para<>"") && (stripos($http_base,"127.0.0.1")===false))
			gSendEmail($remetente,$para,$cc,$assunto,$conteudo,"","");

	}

	/** Cancela o convite para o outro usuário
	 * @author	giuliano
	 * @version	1.0 30-10-2011 10:57
	 * @param idInvite integer Id do convite
	 * @param id integer Id do convidado
	 * @param idd integer Id de quem convidou
	 */
	function inviteCancel($idInvite,$id,$idd,$msg='')
	{
		global $http_base;
		$usrId=$_SESSION['usrId'];
		$hoje=date('Y-m-d H:i:s');

		$sql="delete from alitem.pessoas_relacionamentos where (id_pessoas_relacionado=$usrId or id_pessoas=$usrId) and id=$idInvite";
		gFastQuery($sql);

		if ($usrId<>$idd)
		{
			$sql="select nome,email from alitem.pessoas where id=".$id;
			$rsp=gFastQuery($sql);
			$remetente="auto@alitem.com.br";
			$para=$rsp->fields['email'];
			$cc="";
			$assunto=$_SESSION['usrName']." rejeitou seu convite";
			$conteudo=$_SESSION['usrName']." rejeitou seu convite de relacionamento no portal de aplicativos .oO Alitem.<br>$msg<br><br>Saudações,<br><br>Equipe Alitem<br>http://www.alitem.com.br";
			if (($para<>"") && (stripos($http_base,"127.0.0.1")===false))
				gSendEmail($remetente,$para,$cc,$assunto,$conteudo,"","");
		}
	}

	/** Aceita o convite de outro usuário
	 * @author	giuliano
	 * @version	1.0 30-10-2011 10:57
	 * @param idInvite integer Id do convite
	 * @param id integer Id do convidado
	 * @param idd integer Id de quem convidou
	 */
	function inviteAccept($idInvite,$usrRel,$msg='')
	{
		global $http_base;
		$usrId=$_SESSION['usrId'];
		$hoje=date('Y-m-d H:i:s');
		$sql="insert into alitem.pessoas_relacionamentos
				(idd,id_pessoas,id_pessoas_relacionado,data,data_aceite,tipo,favorito) values
				($usrId,$usrId,".$usrRel.",'$hoje','$hoje',1,1)";
		gFastQuery($sql);

		$sql="update alitem.pessoas_relacionamentos set tipo=1,data_aceite='$hoje',favorito=1 where (id_pessoas_relacionado=$usrId) and id=$idInvite";
		gFastQuery($sql);

		$sql="select nome,email from alitem.pessoas where id=".$usrRel;
		$rsp=gFastQuery($sql);
		$remetente="auto@alitem.com.br";
		$para=$rsp->fields['email'];
		$cc="";
		$assunto=$_SESSION['usrName']." aceitou seu convite";
		$conteudo=$_SESSION['usrName']." aceitou seu convite de relacionamento no portal de aplicativos .oO Alitem.<br>$msg<br><br>Saudações,<br><br>Equipe Alitem<br>http://www.alitem.com.br";
		if (($para<>"") && (stripos($http_base,"127.0.0.1")===false))
			gSendEmail($remetente,$para,$cc,$assunto,$conteudo,"","");

	}

	/** Retorna se o contato tem relacionamento com o usuário atual ou não
	 * @author	giuliano
	 * @version	1.0 30-10-2011 10:57
	 * @param id integer Id do convidado
	 */
	function isRelated($id)
	{
		$usrId=$_SESSION['usrId'];
		$sql="select id from alitem.pessoas_relacionamentos where id_pessoas=$usrId and id_pessoas_relacionado=$id and tipo=1";
		$rs=gFastQuery($sql);
		return (!$rs->EOF);
	}








	function onBeforeShowPage($fields,$page)
	{
		global $gPage;
		$usrIdd=$_SESSION['usrIdd'];
		$flt='1=0';
		if ($fields['apelido']<>'')
			$flt="apelido like '".$fields['apelido']."'";
		if ($fields['email']<>'')
			$flt="email like '".$fields['email']."'";
		/*
		if ($fields['cpf']<>'')
			$flt="cpf ='".$fields['cpf']."'";
		if ($fields['cnpj']<>'')
			$flt="cnpj='".$fields['cnpj']."'";
		*/
		if ($fields['nome']<>'')
			$flt="nome like '".$fields['nome']."'";
		$sql="select p.*,est.descricao estado, cid.descricao cidade, pc.dados_adicionais, pc.formulario
				from alitem.pessoas p
				left join alitem.pessoas_enderecos e on p.id=e.id_pessoas
				left join alitem.estados est on e.id_estados=est.id
				left join alitem.cidades cid on e.id_cidades=cid.id
				left join pessoas_complemento pc on p.id=pc.id_pessoas
				where $flt";
		$rs=gFastQuery($sql);
		switch ($pagex)
		{
			case 2:
				// Novo - busca primeiro
				if (!$rs->EOF)
				{
					// Contato existe...
					$id=$rs->fields['id'];
					$this->contato=$rs->fields;

					$dados="<br>Nome: ".$this->contato['nome'];
					$dados.="<br>Apelido: ".$this->contato['apelido'];
					//$filtro.="<br>".$this->contato['email'];
					$dados.="<br>Localização: ".$this->contato['cidade']."/".$this->contato['estado'];
					$dados=$this->gMsgFilter($dados);

					if ($this->isRelated($id))
					{
						// Contato já tem relacionamento com o usuário atual
						$fields['gPage']=2;
					} else
					{
						// Contato não tem relacionamento
						$fields['gPage']=3;
					}
					$this->pages[$fields['gPage']]=$this->pages[$fields['gPage']].$dados;
				} else
				{
					// Contato não encontrado
					$fields['gPage']=6;
				}
				break;
			case 4:
				if ($this->complement=='')
					$fields['gPage']=5;
				break;
			case 5:
				if ($this->complement!='')
				{
					// Salva dados complementares (só se for novo)
					$sql="select * from pessoas_complemento where id_pessoas=".$rs->fields['id'];
					$rsc=gQuery($sql);
					if ($rsc->EOF)
					{
						$dados='{';
						foreach ($fields as $key=>$value)
						{
							if (($key<>'g') &&($key<>'gPage')&&($key<>'apelido')&&($key<>'nome')&&($key<>'email')&&($key<>'cpf')&&($key<>'cnpj')&&($key<>'PHPSESSID'))
							$dados.="$key: $value;";
						}
						$dados.='}';
						$form=str_replace("'",'',implode(",",$this->complement->getOriginalFields()));
						$sql="insert into pessoas_complemento (id_pessoas,dados_adicionais,formulario) values (".$rs->fields['id'].",'$dados','$form')";
						gQuery($sql);
					}
				}
				$this->invite($rs->fields['id']);
				break;
			case 8:
				// Cadastro de novo contato

				if ((strtolower($fields['n_nome'])=="root") || (strtolower($fields['n_nome'])=="giusoft"))
					$erro[]="Nome inválido";
				if (($fields['n_nome']=="") || ($fields['n_apelido']=="") || ($fields['n_email']==""))
					$erro[]="Todos os campos devem ser preenchidos";
				// Já existe?
				$sql="select * from alitem.pessoas where apelido='".$fields['n_apelido']."' or email='".$fields['n_email']."'";
				$rs=gFastQuery($sql);
				if (!$rs->EOF)
					$erro[]="Apelido ou e-mail já estão em uso";
				if ($fields['n_cpf']<>'')
				{
					/*
					$sql="select * from alitem.pessoas_fisicas where cpf='".$fields['n_cpf']."'";
					$rs=gFastQuery($sql);
					if (!$rs->EOF)
						$erro[]="CPF já está em uso. Clique em <b>Novo</b> na tela inicial e informe este CPF: ".$fields['n_cpf'];
					*/
				}
				if ($fields['n_cnpj']<>'')
				{
					/*
					$sql="select * from alitem.pessoas_juridicas where cnpj='".$fields['n_cnpj']."'";
					$rs=gFastQuery($sql);
					if (!$rs->EOF)
						$erro[]="CNPJ já está em uso. Clique em <b>Novo</b> na tela inicial e informe este CNPJ: ".$fields['n_cnpj'];
					*/
				}

				if (!is_array($erro))
				{
					if ($fields['tipo']=="Pessoa jurídica")
						$fj="J";
					else
						$fj="F";
					$data_cadastro=date("Y-m-d H:i:s");
					$senha=gPasswordSugest();
					$id_idiomas=$_SESSION["usrLang"];
					$sql="insert into alitem.pessoas
							(tipo,apelido,email,nome,senha, data_cadastro,id_idiomas) values
							('$fj','".$fields['n_apelido']."','".$fields['n_email']."','".gUcwords($fields['n_nome'])."','".md5($senha)."', '$data_cadastro',$id_idiomas)";
					gFastQuery($sql);
					$sql="select id from alitem.pessoas where apelido='".$fields['n_apelido']."' and senha='".md5($senha)."'";
					$rs=gFastQuery($sql);
					$id=intval($rs->fields['id']);
					gFastQuery("update alitem.pessoas set idd=$id where id=$id");

					if ($fj=="F")
					{
						$sql="insert into alitem.pessoas_fisicas
							(idd,id_pessoas,cpf) values
							($id,$id,'".$fields['n_cpf']."')";
					} else
					{
						$sql="insert into alitem.pessoas_juridicas
								(idd,id_pessoas,razao_social,cnpj,insc_municipal,insc_estadual) values
								($id,$id,'".$fields['n_razao']."','".$fields['n_cnpj']."','".$fields['n_insc_municipal']."','".$fields['n_insc_estadual']."')";
					}
					gFastQuery($sql);

						$sql="insert into alitem.pessoas_enderecos
							(idd,id_pessoas,endereco,numero,complemento,bairro,cep,id_paises,id_estados,id_cidades) values
							($id,$id,'".$fields['n_endereco']."','".$fields['n_numero']."','".$fields['n_complemento']."','".$fields['n_bairro']."','".$fields['n_cep']."','".$fields['n_id_paises']."','".$fields['n_id_estados']."','".$fields['n_id_cidades']."')";
					gFastQuery($sql);
					gLog("======> usuário criado: ".$fields['n_apelido']." - $senha");

					$msg="";

					$login="Apelido: ".$fields['n_apelido']."\n";
					$login.="E-mail: ".$fields['n_email']."\n";
					$login.="Senha: ".$senha."\n";

					$this->invite($id, $msg, $login,true);

					$dados=$this->msgMiniTitle("Cadastro efetuado com sucesso.");
				} else
				{
					$dados=$this->msgAlert("Não foi possível cadastrar, pois foram encontrados os seguintes problemas:<br><br>");
					foreach ($erro as $e)
						$dados.=$this->msg($e)."<br>";
				}
				$this->pages[$fields['gPage']]=$this->pages[$fields['gPage']].$dados;
				break;
			case 10:
				if ($rs->EOF)
				{
					$dados=$this->msgError("Contato não encontrado!");
				} else
				{
					$dados=$this->gTableBegin("medium",true);
					$dados.=$this->gTableRow(array("~2Dados do seu relacionamento"),"summary");
					//$dados.=$this->gTableRow(array("<-Nome","<-".$rs->fields['nome']));
					//$dados.=$this->gTableRow(array("<-Apelido","<-".$rs->fields['apelido']));
					$rel=new gCore();
					$r=$rel->getProfile($rs->fields['id']);
					$niveis="";
					$niveis[]="Somente nome, apelido, e-mail e localidade";
					$niveis[]="Nome, apelido, e-mail, telefone e endereço completo";
					$niveis[]="Todas as informações, inclusive CPF e CNPJ";

					foreach ($r as $key=>$value)
					{
						if (($key<>"idr") && ($key<>"id"))
						{
							$mtz="";
							$mtz[]="<-".gField2String($key);
							if ($key=="nivel")
								$mtz[]="<-".$niveis[$value];
							else
								$mtz[]="<-".$value;
							$dados.=$this->gTableRow($mtz);
						}
					}


					$mtz=cssDecode($rs->fields['dados_adicionais']);
					if (is_array($mtz))
					{
						$form=explode("},{",$rs->fields['formulario']);
						//$dados.="<hr>".$rs->fields['formulario']."<hr>";
						$frm='';
						foreach ($form as $f)
						{
							$f='{'.$f.'}';
							$f=str_replace("{{","{",$f);
							$f=str_replace("}}","}",$f);
							$fmtz=cssDecode($f);
							if ($fmtz['fieldLabel']<>'')
								$frm[$fmtz['name']]=$fmtz['fieldLabel'];
							else
								$frm[$fmtz['name']]=$fmtz['name'];
							//$dados.="==> $f ==> ".$fmtz['name']." = ".$fmtz['fieldLabel']."<br>";
						}
						$dados.=$this->gTableRow(array("~2Dados complementares"),"summary");
						foreach ($mtz as $key=>$value)
						{
							$dados.=$this->gTableRow(array("<-".$frm[$key],"<-$value"));

						}
					}
					$dados.=$this->gTableEnd();

				}
				$this->pages[$fields['gPage']]=$this->pages[$fields['gPage']].$dados;
				break;
		}
		$gPage=$fields['gPage'];
		return($fields);
	}
	function addComplement($frm)
	{
		$this->complement=$frm;
	}

	function showInviteMenu($json='')
	{
		global $http_base,$gDevice, $usrId, $usrIdd;
		$gPage=$_REQUEST['gPage'];
		//=$_REQUEST['gPage'];
		$usrIdd=$_SESSION['usrIdd'];
		$mtz=cssDecode($json);
		$title=gT("Cadastro de Contatos");
		if (!isset($mtz['new']))
			$new=true;
		else
			$new=($mtz['new']=='true');

		if (!isset($mtz['find']))
			$find=true;
		else
			$find=($mtz['find']=='true');

		if (!isset($mtz['update']))
			$update=true;
		else
			$update=($mtz['update']=='true');

		if (!isset($mtz['cancel']))
			$cancel=true;
		else
			$cancel=($mtz['cancel']=='true');

		$this->senchaScroll="vertical";
//=== 0 Seleção da funcionalidade

		$html='';
		if ($gPage==0)
		{
			$html.="<a name='topo'>".$this->tableBegin("big",false);
			$mtz='';
			$mtz[]=array("<-".$this->image("{url: 32/a0009; hint: Adicionar contato; href: ".$this->page."&gPage=2}")," style='weight: 36px'");
			//$sql="select * from pessoas order by nome";
			$sql="select * from alitem.pessoas where id in
					(select IF(id_pessoas=$usrIdd,id_pessoas_relacionado,id_pessoas) id from alitem.pessoas_relacionamentos where id_pessoas=$usrIdd) order by nome";
			$rs=gQuery($sql);
			$letrasExistentes='';
			$ttlLetras=0;
			$pessoas='';
			while (!$rs->EOF)
			{
				$pessoas[]=$rs->fields;
				$letra=strtoupper(substr(html_entity_decode($rs->fields['nome']),0,1));
				$letrasExistentes[$letra]=$letra;
				$ttlLetras++;
				$rs->MoveNext();
			}

			$letras="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$w=intval(100/count($letrasExistents));
			for($a=0; $a<strlen($letras); $a++)
			{
				if ($letrasExistentes[$letras[$a]]<>"")
					$cols[]=array("<a href='#".$letras[$a]."'>".$this->msgSubTitle($letras[$a])."</a>","style='width: $w%'");
			}
			$col='';
			$col.=$this->tableBegin("big",true);
			$col.=$this->tableRow($cols,"light");
			$col.=$this->tableEnd();
			$mtz[]=$col;
			$html.=$this->tableRow($mtz,"");
			$html.=$this->tableEnd();
			$html.=$this->tableBegin("big",true);
			$letraAtual='';
			foreach ($pessoas as $ps)
			{
				$pre='';
				$letra=strtoupper(substr($ps['nome'],0,1));
				if ($letraAtual<>$letra)
				{
					$mtz='';
					$mtz[]="~4<><a name='$letra'><a href=#topo>".$this->msgSubTitle($letra)."</a>";
					$html.=$this->tableRow($mtz,"group");
				}
				$letraAtual=$letra;
				$mtz='';
				$mtz[]=array("<-".$this->image("{url: 32/a0011; hint: Mais informações; href: ".$this->page."&gId=".$ps['id']."&gPage=1}")," style='weight: 36px'");
				$mtz[]="<-".$ps['nome'];
				$mtz[]="<-".$ps['apelido'];
				if ($gDevice<>"mobile")
					$mtz[]="<-".$ps['email'];
				$html.=$this->tableRow($mtz,"light");
			}
			$html.=$this->tableEnd();

		}
		$this->add("{name: 'gc0'; title: '$title'; subTitle: '$subTitle'}",$html);

//=== 1

		$html='';
		if ($gPage==1)
		{
			$simnao[0]=gT("Não");
			$simnao[1]=gT("Sim");
			$sql="";

			$flt="p.id=".$_REQUEST['gId'];
			if (!is_developer())
			{
				$flt.=" and a.id_aplicativos=".$_SESSION['gApp'];
			}
			$rs=rsContacts("rs",$flt);
			$html.=$this->msgSubTitle($rs->fields['nome']);
			$html.=$this->msgMiniTitle($rs->fields['apelido']);
			$html.=$this->msgFilter($rs->fields['email']);
			$html.="<img id='gFoto' src='$http_base/res/alitem/inc/class.images.php?i=".$_REQUEST['gId']."&t=".date("His")."' align='left' style='float: none; width: 75; height: 75; border: #d0d0d0 1px solid;'><br><br>";



/*
 *
 *  PERMISSÃO DE EDIÇÃO DE CADASTRO DE USUÁRIO QUE JÁ ENTROU NO ALITEM POR QUEM O CADASTROU
 *
 */

			//if ($rs->fields['id_pessoas_criou']==$usrIdd)
				$html.=$this->image("{url: 32/a0036; hint: Alterar informações; href: ".$this->page."&gId=".$_REQUEST['gId']."&gPage=6}");
			//else
			//	$html.=$this->msgError("Este é um usuário ativo no Alitem. Somente o próprio pode alterar os seus dados principais.");
			$html.=$this->tableBegin("big",true);
			$mtz='';
			$mtz[]="<-".$this->msgFilter("Favorito").$this->msg($simnao[$rs->fields['favorito']]);
			$mtz[]="<-".$this->msgFilter("Colaborador").$this->msg($simnao[$rs->fields['colaborador']]);
			$mtz[]="<-".$this->msgFilter("Cliente").$this->msg($simnao[$rs->fields['consumidor']]);
			$mtz[]="<-".$this->msgFilter("Fornecedor").$this->msg($simnao[$rs->fields['fornecedor']]);
			$html.=$this->tableRow($mtz,"header");

			if ($rs->fields['tipo']=="J")
			{
				$mtz='';
				$mtz[]="~2<-".$this->msgFilter("Razão social").$this->msg($rs->fields['razao_social'])."&nbsp;";
				$mtz[]="~2<-".$this->msgFilter("Site").$this->msg($rs->fields['site'])."&nbsp;";
				$html.=$this->tableRow($mtz,"light");
				$mtz='';
				$mtz[]="~2<-".$this->msgFilter("CNPJ").$this->msg($rs->fields['cnpj'])."&nbsp;";
				$mtz[]="<-".$this->msgFilter("Insc. Estadual").$this->msg($rs->fields['insc_estadual'])."&nbsp;";
				$mtz[]="<-".$this->msgFilter("Insc. Municipal").$this->msg($rs->fields['insc_municipal'])."&nbsp;";
				$html.=$this->tableRow($mtz,"light");
				$mtz='';
				$mtz[]="<-".$this->msgFilter("Celular").$this->msg($rs->fields['celular'])."&nbsp;";
				$mtz[]="<-".$this->msgFilter("Telefone").$this->msg($rs->fields['telefone'])."&nbsp;";
				$mtz[]="<-".$this->msgFilter("Ramal").$this->msg($rs->fields['ramal'])."&nbsp;";
				$mtz[]="<-".$this->msgFilter("Fax").$this->msg($rs->fields['fax'])."&nbsp;";
				$html.=$this->tableRow($mtz,"light");
			} else
			{
				$mtz='';
				$mtz[]="~4<-".$this->msgFilter("Site").$this->msg($rs->fields['site'])."&nbsp;";
				$html.=$this->tableRow($mtz,"light");
				$mtz='';
				$mtz[]="<-".$this->msgFilter("RG").$this->msg($rs->fields['rg'])."&nbsp;";
				$mtz[]="<-".$this->msgFilter("CPF").$this->msg($rs->fields['cpf'])."&nbsp;";
				$mtz[]="<-".$this->msgFilter("Celular").$this->msg($rs->fields['celular'])."&nbsp;";
				$mtz[]="<-".$this->msgFilter("Telefone").$this->msg($rs->fields['telefone'])."&nbsp;";
				$html.=$this->tableRow($mtz,"light");
			}
			$html.=$this->tableEnd();
			$html.=$this->tableBegin("big",true);
			$mtz='';
			$mtz[]="~4<-".$this->msgFilter("Endereço").$this->msg($rs->fields['endereco'])."&nbsp;";
			$html.=$this->tableRow($mtz,"light");
			$mtz='';
			$mtz[]="<-".$this->msgFilter("Número").$this->msg($rs->fields['numero'])."&nbsp;";
			$mtz[]="~2<-".$this->msgFilter("Complemento").$this->msg($rs->fields['complemento'])."&nbsp;";
			$mtz[]="<-".$this->msgFilter("Bairro").$this->msg($rs->fields['bairro'])."&nbsp;";
			$html.=$this->tableRow($mtz,"light");
			$mtz='';
			$mtz[]="<-".$this->msgFilter("Cidade").$this->msg($rs->fields['cidade'])."&nbsp;";
			$mtz[]="<-".$this->msgFilter("Estado").$this->msg($rs->fields['estado'])."&nbsp;";
			$mtz[]="<-".$this->msgFilter("País").$this->msg($rs->fields['pais'])."&nbsp;";
			$mtz[]="<-".$this->msgFilter("CEP").$this->msg($rs->fields['cep'])."&nbsp;";
			$html.=$this->tableRow($mtz,"light");
			$html.=$this->tableEnd();

			if ($this->complement!='')
				$html.=$this->image("{url: 32/a0036; hint: Alterar informações complementares; href: ".$this->page."&gId=".$_REQUEST['gId']."&gPage=8}");
			$sql="select * from pessoas_complemento where id_pessoas=".$_REQUEST['gId'];
			$rsc=gQuery($sql);
			if (!$rsc->EOF)
			{
				$mtz=cssDecode($rsc->fields['dados_adicionais']);
				$form=explode("},{",$rsc->fields['formulario']);
				//$dados.="<hr>".$rs->fields['formulario']."<hr>";
				$frm='';
				foreach ($form as $f)
				{
					$f='{'.$f.'}';
					$f=str_replace("{{","{",$f);
					$f=str_replace("}}","}",$f);
					$fmtz=cssDecode($f);
					if ($fmtz['fieldLabel']<>'')
						$frm[$fmtz['name']]=$fmtz['fieldLabel'];
					else
						$frm[$fmtz['name']]=$fmtz['name'];
					$typ[$fmtz['name']]=$fmtz['type'];
					//$dados.="==> $f ==> ".$fmtz['name']." = ".$fmtz['fieldLabel']."<br>";
				}
				if (is_array($mtz))
				{
					$html.=$this->tableBegin("big",true);
					$cnt=0;
					$cols='';
					foreach ($mtz as $key=>$value)
					{
						$cnt++;
						if ($typ[$key]<>"checkbox")
							$cols[]="<-".$this->msgFilter($frm[$key]).nl2br($this->msg($value));
						else
							$cols[]="<-".$this->msgFilter($frm[$key]).nl2br($this->msg(gCheck($value)));
						if ($cnt==3)
						{
							$cnt=0;
							$html.=$this->tableRow($cols,"light");
							$cols='';
						}
					}
					if ($cnt<>3)
					{
						for ($a=count($cols); $a<3; $a++)
							$cols[]="&nbsp;";
						$html.=$this->tableRow($cols,"light");
					}
					$html.=$this->tableEnd();
				}
			}
		}
		$this->add("{name: 'gc1'; title: '$title'; subTitle: '$subTitle'; buttonNextCaption: no}",$html);

//=== 2

		$frm='';
		if ($gPage==2)
		{
			$frm=new gForm("{title:$title}");
			$frm->add("{type: show; fieldLabel: ''; value:'<span class=g-msg-filter>".gT("Informe os dados abaixo para realizar um novo cadastro")."</span>'}");
			$frm->add("{type: text; fieldLabel: 'Nome completo'; allowBlank: false; name: n_nome; maxLength: 60; }");
			$frm->add("{type: email; fieldLabel: 'E-mail'; allowBlank: true; name: n_email; maxLength: 60; }");
			$frm->add("{type: combo; fieldLabel: 'Tipo'; allowBlank: false; name: n_tipo; value: 'Pessoa física'; items: {'Pessoa física','Pessoa jurídica'}}");
			$frm->add("{type: number; fieldLabel: 'CPF/CNPJ'; name: n_cpfcnpj; maxLength: 14}");
		}
		$this->add("{name: 'gc2'; title: '$title'; subTitle: '$subTitle'}",$frm);

//=== 3

		$html='';
		if ($gPage==3)
		{
			// Verifica se já existe...
			$usrIdd=$_SESSION['usrIdd'];
			$f=0;
			$n_email=trim($_REQUEST['n_email']);
			$n_cpfcnpj=trim($_REQUEST['n_cpfcnpj']);
			if ($n_email<>'')
			{
				$f++;
				$flt[]="email like '$n_email'";
			}
			if (($n_cpfcnpj<>'') && ($_REQUEST['n_tipo']=="Pessoa física"))
			{
				$f++;
				$flt[]="cpf ='$n_cpfcnpj'";
			}
			if (($n_cpfcnpj<>'') && ($_REQUEST['n_tipo']=="Pessoa jurídica"))
			{
				$f++;
				$flt[]="cnpj='$n_cpfcnpj'";
			}
			if ($f>0)
			{
				$sql="select p.*,est.descricao estado, cid.descricao cidade, pc.dados_adicionais, pc.formulario, r.descricao ramo
						from alitem.pessoas p
						left join alitem.pessoas_juridicas j on p.id=j.id_pessoas
						left join alitem.pessoas_fisicas f on p.id=f.id_pessoas
						left join alitem.pessoas_enderecos e on p.id=e.id_pessoas
						left join alitem.estados est on e.id_estados=est.id
						left join alitem.cidades cid on e.id_cidades=cid.id
						left join pessoas_complemento pc on p.id=pc.id_pessoas
						left join alitem.pessoas_ramos r on j.id_pessoas_ramos=r.id
						where ".implode(" or ",$flt);
				$rs=gFastQuery($sql);
				if ($rs->EOF)
				{
					$frm=new gForm("title: $title; columns: 2");
					$n_tipo=$_REQUEST['n_tipo'];
					$frm->add("{type: show; name: 's3'; fieldLabel: 'Nome completo'; value: '".gUcwords($_REQUEST['n_nome'])."'}");
					$frm->add("{type: show; name: 's4'; fieldLabel: 'E-mail'; value: '".$_REQUEST['n_email']."' }");
					$frm->add("{type: show; name: 's5'; fieldLabel: 'Tipo'; value: '".$_REQUEST['n_tipo']."'}");
					if (trim($n_tipo)=="Pessoa jurídica")
					{
						$frm->add("{type: show; name: 's6'; fieldLabel: 'CNPJ'; value: '".$_REQUEST['n_cpfcnpj']."'}");
						$frm->add("{type: text; fieldLabel: 'Razão social'; name: n_razao; maxLength: 60; }");
						$frm->add("{type: integer; fieldLabel: 'Insc. municipal'; name: n_insc_municipal; maxLength: 20}");
						$frm->add("{type: integer; fieldLabel: 'Insc. estadual'; name: n_insc_estadual; maxLength: 20}");
					} else
					{
						$frm->add("{type: show; name: 's6'; fieldLabel: 'CPF'; value: '".$_REQUEST['n_cpfcnpj']."'}");
						$frm->add("{type: text; fieldLabel: 'RG'; name: n_rg; maxLength: 25}");
					}

					$sql="select e.descricao estado, c.descricao cidade
							from alitem.pessoas_enderecos p
							left join alitem.estados e on p.id_estados=e.id
							left join alitem.cidades c on p.id_cidades=c.id
							where id_pessoas=$usrIdd";
					$rse=gFastQuery($sql);
					$estado=$rse->fields['estado'];
					$cidade=$rse->fields['cidade'];
					$frm->add("{type: text; fieldLabel: 'Telefone'; allowBlank: true; name: n_telefone; maxLength: 30; }");
					$frm->add("{type: text; fieldLabel: 'Ramal'; allowBlank: true; name: n_ramal; maxLength: 30; }");
					$frm->add("{type: text; fieldLabel: 'Celular'; allowBlank: true; name: n_celular; maxLength: 30; }");
					$frm->add("{type: text; fieldLabel: 'Fax'; allowBlank: true; name: n_fax; maxLength: 30; }");
					$frm->add("{type: combo; fieldLabel: 'País'; allowBlank: true; name: n_id_paises; value: 'Brasil'; items: paises; }");
					$frm->add("{type: combo; fieldLabel: 'Estado'; allowBlank: true; name: n_id_estados; value: '$estado'; items: estados; }");
					$frm->add("{type: combo; fieldLabel: 'Cidade'; allowBlank: true; name: n_id_cidades; value: '$cidade'; items: cidades; }");
					$frm->add("{type: text; fieldLabel: 'Endereço'; allowBlank: true; name: n_endereco; maxLength: 80; }");
					$frm->add("{type: text; fieldLabel: 'Número'; allowBlank: true; name: n_numero; maxLength: 10; }");
					$frm->add("{type: text; fieldLabel: 'Complemento'; allowBlank: true; name: n_complemento; maxLength: 80; }");
					$frm->add("{type: text; fieldLabel: 'Bairro'; allowBlank: true; name: n_bairro; maxLength: 60; }");
					$frm->add("{type: number; fieldLabel: 'CEP (só números)'; allowBlank: true; name: n_cep; maxLength: 8; }");
					$frm->add("{type: text; fieldLabel: 'Site'; allowBlank: true; name: n_site; maxLength: 100; }");
					$frm->add("{type: checkbox; fieldLabel: 'Colaborador'; name: n_colaborador; }");
					$frm->add("{type: checkbox; fieldLabel: 'Cliente'; name: n_consumidor; }");
					$frm->add("{type: checkbox; fieldLabel: 'Fornecedor'; name: n_fornecedor; }");
					$frm->add("{type: file; fieldLabel: 'Foto/logomarca'; name: n_arquivo; }");
					$frm->add("{type: hidden; name: nw; value: 1}");
					$button="";
				} else
				{
					$gId=$rs->fields['id'];
					$frm.=$this->msgError(gT("Contato já existe!<br>Para disponibilizá-lo na sua relação, clique no ícone abaixo."));
					$frm.="<img src='$http_base/res/alitem/inc/class.images.php?i=".$gId."' align='left' style='float: none; width: 75; height: 75; border: #d0d0d0 1px solid;'><br><br>";
					$frm.="<br />".$this->tableBegin("big",true);

					$mtz='';
					$mtz[]="~2<-".$this->msgFilter("Nome").$this->msg($rs->fields['nome'])."&nbsp;";
					$mtz[]="~2<-".$this->msgFilter("E-mail").$this->msg($rs->fields['email'])."&nbsp;";
					$frm.=$this->tableRow($mtz,"light");
					if ($rs->fields['tipo']=="J")
					{
						$mtz='';
						$mtz[]="~4<-".$this->msgFilter("Razão social").$this->msg($rs->fields['razao_social'])."&nbsp;";
						$frm.=$this->tableRow($mtz,"light");
						$mtz='';
						$mtz[]="~2<-".$this->msgFilter("Site").$this->msg($rs->fields['site'])."&nbsp;";
						$mtz[]="~2<-".$this->msgFilter("Ramo").$this->msg($rs->fields['ramo'])."&nbsp;";
						$frm.=$this->tableRow($mtz,"light");
					} else
					{
					}
					$mtz='';
					$mtz[]="<-".$this->msgFilter("Bairro").$this->msg($rs->fields['bairro'])."&nbsp;";
					$mtz[]="<-".$this->msgFilter("Cidade").$this->msg($rs->fields['cidade'])."&nbsp;";
					$mtz[]="<-".$this->msgFilter("Estado").$this->msg($rs->fields['estado'])."&nbsp;";
					$mtz[]="<-".$this->msgFilter("País").$this->msg($rs->fields['pais'])."&nbsp;";
					$frm.=$this->tableRow($mtz,"light");
					$frm.=$this->tableEnd();
					$frm.=$this->image("{url: 32/a0009; hint: Adicionar à sua relação de contatos (convidar); href: ".$this->page."&gPage=5}");
					$button="; buttonNextCaption: no";
				}
			} else
			{
				$button="; buttonNextCaption: no";
				$frm=$this->msgError("Deve ser informado o e-mail ou CPF/CNPJ.");
			}
		}
		$this->add("{name: 'gc3'; title: '$title'; subTitle: '$subTitle' $button}",$frm);

//=== 4

		$html='';
		if ($gPage==4)
		{
			if ($_REQUEST['nw']==1)
			{
				// Cadastro de novo contato

				if ((strtolower($_REQUEST['n_nome'])=="root") || (strtolower($_REQUEST['n_nome'])=="giusoft"))
					$erro[]="Nome inválido";
				if (($_REQUEST['n_nome']=="") || (($_REQUEST['n_email']=="") && ($_REQUEST['n_cpfcnpj']=="") ))
					$erro[]="Todos os campos devem ser preenchidos";

				$nomes=explode(" ",$_REQUEST['n_nome']);
				$n1=$nomes[0];
				$n2=$nomes[(count($nomes)-1)];
				if ($n1==$n2)
					$n2='';
				$apelido=ucfirst($n1).ucfirst($n2);
				$sql="select max(id) idm, count(id) ttl from alitem.pessoas where apelido like '$apelido%'";
				$rsm=gFastQuery($sql);
				$idm=$rsm->fields['idm'];
				if ($idm==0)
					$idm='';
				else
					$idm++;
				$apelido.=$rsm->fields['ttl']+1;
				// Já existe?
				if ($_REQUEST['n_email']<>'')
				{
					$sql="select * from alitem.pessoas where apelido='$apelido' or email='".$_REQUEST['n_email']."'";
					$rs=gFastQuery($sql);
					if (!$rs->EOF)
						$erro[]="Apelido ou e-mail já está em uso";
				} else
				{
					$sql="select * from alitem.pessoas where apelido='$apelido'";
					$rs=gFastQuery($sql);
					if (!$rs->EOF)
					{
						$sql="select max(id) m from alitem.pessoas";
						$rsm=gFastQuery($sql);
						$apelido.=$rsm->fields['m'];
					}
				}

				if ($_REQUEST['n_cpf']<>'')
				{
					/*
					$sql="select * from alitem.pessoas_fisicas where cpf='".$_REQUEST['n_cpf']."'";
					$rs=gFastQuery($sql);
					if (!$rs->EOF)
						$erro[]="CPF já está em uso";
					*/

				}
				if ($_REQUEST['n_cnpj']<>'')
				{
					/*
					$sql="select * from alitem.pessoas_juridicas where cnpj='".$_REQUEST['n_cnpj']."'";
					$rs=gFastQuery($sql);
					if (!$rs->EOF)
						$erro[]="CNPJ já está em uso";
					*/
				}

				// Prepara a variável do arquivo
				$arquivo = isset($_FILES["n_arquivo"]) ? $_FILES["n_arquivo"] : FALSE;
				// Tamanho máximo do arquivo (em bytes)
				$config["tamanho"] = 106883000000;
				// Formulário postado... executa as ações
				if (($arquivo['tmp_name']<>""))
				{
					// Verifica se o mime-type do arquivo é de imagem
					if (!eregi("^image\/(pjpeg|jpeg|png)$", $arquivo["type"]))
					{

						$erro[] = "Arquivo em formato inválido! A imagem deve ser jpg, jpeg, ou png.";
					} else
					{
					  // Verifica tamanho do arquivo
					  if ($arquivo["size"] > $config["tamanho"])
					  {
							$erro[] = "Arquivo em tamanho muito grande! A imagem deve ser de no máximo " . $config["tamanho"] . " bytes. Envie outro arquivo";
					  }
					}
				}

				if (!is_array($erro))
				{
					if ($_REQUEST['n_tipo']=="Pessoa jurídica")
						$fj="J";
					else
						$fj="F";
					$data_cadastro=date("Y-m-d H:i:s");
					$senha=gPasswordSugest();

					$imgCampos=$imgValores="";

					if (is_array($arquivo))
					{
						$img=gImageResize($arquivo);
						$imgCampos=", imagem, imagem_tipo, imagem_altura, imagem_largura ";
						$imgValores=", '".$img['file']."', '".$img['type']."', '".$img['height']."', '".$img['width']."' ";
					}

					// Pessoa
					$id_idiomas=$_SESSION["usrLang"];
					$sql="insert into alitem.pessoas
							(tipo,apelido,email,nome,senha, data_cadastro, id_pessoas_criou,telefone,celular,ramal,fax,site,id_idiomas $imgCampos) values
							('$fj','$apelido','".$_REQUEST['n_email']."','".gUcwords($_REQUEST['n_nome'])."','".md5($senha)."', '$data_cadastro',".$_SESSION['usrIdd'].",'".$_REQUEST['n_telefone']."','".$_REQUEST['n_celular']."','".$_REQUEST['n_ramal']."','".$_REQUEST['n_fax']."','".$_REQUEST['n_site']."',$id_idiomas $imgValores)";
					gFastQuery($sql);
					$sql="select id from alitem.pessoas where apelido='".$apelido."' and senha='".md5($senha)."'";
					$rs=gFastQuery($sql);
					$id=intval($rs->fields['id']);
					gFastQuery("update alitem.pessoas set idd=$id where id=$id");

					// PF ou PJ
					if ($fj=="F")
					{
						$sql="insert into alitem.pessoas_fisicas
							(idd,id_pessoas,cpf,rg) values
							($id,$id,'".$_REQUEST['n_cpfcnpj']."','".$_REQUEST['n_rg']."')";
					} else
					{
						$sql="insert into alitem.pessoas_juridicas
								(idd,id_pessoas,razao_social,cnpj,insc_municipal,insc_estadual) values
								($id,$id,'".gUcwords($_REQUEST['n_razao'])."','".$_REQUEST['n_cpfcnpj']."','".$_REQUEST['n_insc_municipal']."','".$_REQUEST['n_insc_estadual']."')";
					}
					gFastQuery($sql);

					// Endereços
					$sql="insert into alitem.pessoas_enderecos
							(idd,id_pessoas,endereco,numero,complemento,bairro,cep,id_paises,id_estados,id_cidades) values
							($id,$id,'".$_REQUEST['n_endereco']."','".$_REQUEST['n_numero']."','".$_REQUEST['n_complemento']."','".$_REQUEST['n_bairro']."','".$_REQUEST['n_cep']."','".$_REQUEST['n_id_paises']."','".$_REQUEST['n_id_estados']."','".$_REQUEST['n_id_cidades']."')";
					gFastQuery($sql);

					// Permissões
					$sql="insert into alitem.pessoas_permissoes_aplicativos
							(idd,id_pessoas,id_pessoas_relacionado,id_aplicativos,colaborador,consumidor,fornecedor) values
							($usrIdd,$usrIdd,$id,".$_SESSION['gApp'].",".gDBCheck($_REQUEST['n_colaborador']).",".gDBCheck($_REQUEST['n_consumidor']).",".gDBCheck($_REQUEST['n_fornecedor']).")";
					gFastQuery($sql);

					gLog("======> Usuário criado: ".$apelido."($id) - $senha");

					$msg="";

					$login="Nome: ".$_REQUEST['n_nome']."\n";
					$login="Apelido: ".$apelido."\n";
					$login.="E-mail: ".$_REQUEST['n_email']."\n";
					$login.="Senha: ".$senha."\n";

					$this->invite($id, $msg, $login, true);

					// Dados complementares
					if ($this->complement!='')
					{
						$html=$this->complement;
						$html->add("{name: n_id; type: hidden; value: $id}");
					}
					else
					{
						$html=$this->msgMiniTitle("Cadastro efetuado com sucesso.");
						$buttonNext="buttonNextCaption: no";
					}
				} else
				{
					$html.=$this->msgError("Não foi possível salvar. Erros encontrados:<br><br><ul>".implode("<li>",$erro)."</ul>");
				}
			} else
			{
				// Convidar
				$html.=$this->msgError("Convidar...");
			}

		}
		$this->add("{name: 'gc4'; title: '$title'; subTitle: '$subTitle'; buttonBack: -3; $buttonNext}",$html);

//=== 5

		$html='';
		if ($gPage==5)
		{
			// Dados complementares
			if ($this->complement!='')
			{
				// Salva dados complementares (só se for novo)
				$sql="select * from pessoas_complemento where id_pessoas=".$_REQUEST['n_id'];
				$rsc=gQuery($sql);
				if ($rsc->EOF)
				{
					$dados='{';
					foreach ($_REQUEST as $key=>$value)
					{
						if ((substr($key,0,2)<>"n_") && ($key<>'g') && ($key<>'nw') &&($key<>'gPage')&&($key<>'apelido')&&($key<>'nome')&&($key<>'email')&&($key<>'cpf')&&($key<>'cnpj')&&($key<>'PHPSESSID'))
						$dados.="$key: $value;";
					}
					$dados.='}';
					$form=str_replace("'",'',implode(",",$this->complement->getOriginalFields()));
					$sql="insert into pessoas_complemento (id_pessoas,dados_adicionais,formulario) values (".$_REQUEST['n_id'].",'$dados','$form')";
					gQuery($sql);
					$html=$this->msgMiniTitle("Cadastro efetuado com sucesso.");
				}
			} else
			{
				$html=$this->msgMiniTitle("Cadastro efetuado com sucesso.");
			}
		}
		$this->add("{name: 'gc5'; title: '$title'; subTitle: '$subTitle'; buttonNextCaption: no}",$html);

//=== 6

		$html='';
		if ($gPage==6)
		{
			$flt="p.id=".$_REQUEST['gId'];
			if (!is_developer())
			{
				$flt.=" and a.id_aplicativos=".$_SESSION['gApp'];
			}
			$rsp=rsContacts("rs",$flt);
			$sql="select p.*,est.descricao estado, cid.descricao cidade, pc.dados_adicionais, pc.formulario, r.descricao ramo,
						j.razao_social,j.cnpj,j.insc_estadual, j.insc_municipal,
						f.cpf, f.rg,
						e.id_paises,e.id_estados,e.id_cidades,e.endereco, e.numero, e.complemento, e.bairro, e.cep
					from alitem.pessoas p
					left join alitem.pessoas_juridicas j on p.id=j.id_pessoas
					left join alitem.pessoas_fisicas f on p.id=f.id_pessoas
					left join alitem.pessoas_enderecos e on p.id=e.id_pessoas
					left join alitem.estados est on e.id_estados=est.id
					left join alitem.cidades cid on e.id_cidades=cid.id
					left join pessoas_complemento pc on p.id=pc.id_pessoas
					left join alitem.pessoas_ramos r on j.id_pessoas_ramos=r.id
					where p.id=".$_REQUEST['gId'];
			$rs=gFastQuery($sql);
			if (!$rs->EOF)
			{
				$frm=new gForm("title: $title; columns: 2");
				$frm->add("{type: text; name: 'a_nome'; fieldLabel: 'Nome completo'; value: '".gUcwords($rs->fields['nome'])."'}");
				$frm->add("{type: text; name: 'a_email'; fieldLabel: 'E-mail'; value: '".$rs->fields['email']."' }");
				if (trim($rs->fields['tipo'])=="J")
				{
					$frm->add("{type: integer; name: 'a_cnpj'; maxLength: 14; fieldLabel: 'CNPJ'; value: '".$rs->fields['cnpj']."'}");
					$frm->add("{type: text; fieldLabel: 'Razão social'; name: a_razao; maxLength: 60; value: '".$rs->fields['razao_social']."'}");
					$frm->add("{type: integer; fieldLabel: 'Insc. municipal'; name: a_insc_municipal; maxLength: 20;value: '".$rs->fields['insc_municipal']."'}");
					$frm->add("{type: integer; fieldLabel: 'Insc. estadual'; name: a_insc_estadual; maxLength: 20;value: '".$rs->fields['insc_estadual']."'}");
				} else
				{
					$frm->add("{type: integer; name: 'a_cpf'; fieldLabel: 'CPF'; maxLength: 11; value: '".$rs->fields['cpf']."'}");
					$frm->add("{type: text; fieldLabel: 'RG'; name: a_rg; maxLength: 25;value: '".$rs->fields['rg']."'}");
				}

				$pais=$rs->fields['id_paises'];
				$estado=$rs->fields['id_estados'];
				$cidade=$rs->fields['id_cidades'];
				$frm->add("{type: text; fieldLabel: 'Telefone'; allowBlank: true; name: a_telefone; maxLength: 30; value: '".$rs->fields['telefone']."'}");
				$frm->add("{type: text; fieldLabel: 'Ramal'; allowBlank: true; name: a_ramal; maxLength: 30; value: '".$rs->fields['ramal']."'}");
				$frm->add("{type: text; fieldLabel: 'Celular'; allowBlank: true; name: a_celular; maxLength: 30; value: '".$rs->fields['celular']."'}");
				$frm->add("{type: text; fieldLabel: 'Fax'; allowBlank: true; name: a_fax; maxLength: 30; value: '".$rs->fields['fax']."'}");
				$frm->add("{type: combo; fieldLabel: 'País'; allowBlank: true; name: a_id_paises; value: $pais; items: paises; }");
				$frm->add("{type: combo; fieldLabel: 'Estado'; allowBlank: true; name: a_id_estados; value: '$estado'; items: estados; }");
				$frm->add("{type: combo; fieldLabel: 'Cidade'; allowBlank: true; name: a_id_cidades; value: '$cidade'; items: cidades; }");
				$frm->add("{type: text; fieldLabel: 'Endereço'; allowBlank: true; name: a_endereco; maxLength: 80; value: '".$rs->fields['endereco']."'}");
				$frm->add("{type: text; fieldLabel: 'Número'; allowBlank: true; name: a_numero; maxLength: 10; value: '".$rs->fields['numero']."'}");
				$frm->add("{type: text; fieldLabel: 'Complemento'; allowBlank: true; name: a_complemento; maxLength: 80; value: '".$rs->fields['complemento']."'}");
				$frm->add("{type: text; fieldLabel: 'Bairro'; allowBlank: true; name: a_bairro; maxLength: 60; value: '".$rs->fields['bairro']."'}");
				$frm->add("{type: number; fieldLabel: 'CEP (só números)'; allowBlank: true; name: a_cep; maxLength: 8; value: '".$rs->fields['cep']."'}");
				$frm->add("{type: text; fieldLabel: 'Site'; allowBlank: true; name: a_site; maxLength: 100; value: '".$rs->fields['site']."'}");
				$frm->add("{type: checkbox; fieldLabel: 'Colaborador'; name: a_colaborador; value: '".$rsp->fields['colaborador']."'}");
				$frm->add("{type: checkbox; fieldLabel: 'Cliente'; name: a_consumidor; value: '".$rsp->fields['consumidor']."'}");
				$frm->add("{type: checkbox; fieldLabel: 'Fornecedor'; name: a_fornecedor; value: '".$rsp->fields['fornecedor']."'}");
				$frm->add("{type: file; fieldLabel: 'Foto/logomarca'; name: a_arquivo; }");
				$frm->add("{type: hidden; name: nw; value: 0}");
				$frm->add("{type: hidden; name: tipo; value: ".$rs->fields['tipo']."}");
				$frm->add("{type: hidden; name: gId; value: ".$_REQUEST['gId']."}");
				$button="";
			}
		}
		$this->add("{name: 'gc6'; title: '$title'; subTitle: '$subTitle'}",$frm);

//=== 7

		$html='';
		if ($gPage==7)
		{
			$gId=$_REQUEST['gId'];
			if ((strtolower($_REQUEST['a_nome'])=="root") || (strtolower($_REQUEST['a_nome'])=="giusoft"))
				$erro[]="Nome inválido";
			if (($_REQUEST['a_nome']=="") || (($_REQUEST['a_email']=="") && ($_REQUEST['a_cpf']=="") && ($_REQUEST['a_cnpj']=="")))
				$erro[]="Os campos <b>Nome</b> e (<b>E-mail</b> ou <b>CPF/CNPJ</b>) são obrigatórios";

			/*
			$nomes=explode(" ",$_REQUEST['a_nome']);
			$n1=$nomes[0];
			$n2=$nomes[(count($nomes)-1)];
			if ($n1==$n2)
				$n2='';
			$apelido=ucfirst($n1).ucfirst($n2);

			$sql="select max(id) idm from alitem.pessoas where apelido like '$apelido%'";
			$rsm=gQuery($sql);
			$idm=$rsm->fields['idm'];
			if ($idm==0)
				$idm='';
			else
				$idm++;
			$apelido.=$idm;
			 *
			 */
			// Já existe?
			if ($_REQUEST['a_email']<>'')
			{
				$sql="select * from alitem.pessoas where id<>$gId and email='".$_REQUEST['a_email']."'";
				$rs=gFastQuery($sql);
				if (!$rs->EOF)
					$erro[]="E-mail já está em uso";
			}
			if ($_REQUEST['a_cpf']<>'')
			{
				/*
				$sql="select * from alitem.pessoas_fisicas where id_pessoas<>$gId and cpf='".$_REQUEST['a_cpf']."'";
				$rs=gFastQuery($sql);
				if (!$rs->EOF)
					$erro[]="CPF já está em uso";
				*/
			}
			if ($_REQUEST['a_cnpj']<>'')
			{
				/*
				$sql="select * from alitem.pessoas_juridicas where id_pessoas<>$gId and cnpj='".$_REQUEST['a_cnpj']."'";
				$rs=gFastQuery($sql);
				if (!$rs->EOF)
					$erro[]="CNPJ já está em uso";
				*/
			}

			$sql="select * from alitem.pessoas where id=$gId";
			$rs=gFastQuery($sql);
			if ($rs->fields['id_pessoas_criou']<>$usrIdd) // Medida de segurança
			{
				//$erro[]="Permissão negada para esta operação";
			}

			// Prepara a variável do arquivo
			$arquivo = isset($_FILES["a_arquivo"]) ? $_FILES["a_arquivo"] : FALSE;
			// Tamanho máximo do arquivo (em bytes)
			$config["tamanho"] = 106883000000;
			// Formulário postado... executa as ações
			if (($arquivo['tmp_name']<>""))
			{
				// Verifica se o mime-type do arquivo é de imagem
				if (!eregi("^image\/(pjpeg|jpeg|png)$", $arquivo["type"]))
				{
					$erro[] = "Arquivo em formato inválido! A imagem deve ser jpg, jpeg, ou png. Envie outro arquivo";
				} else
				{
				  // Verifica tamanho do arquivo
				  if ($arquivo["size"] > $config["tamanho"])
				  {
						$erro[] = "Arquivo em tamanho muito grande! A imagem deve ser de no máximo " . $config["tamanho"] . " bytes. Envie outro arquivo";
				  }
				}
			}

			if (!is_array($erro))
			{
				// Pessoas

				$campos['nome']=gUcwords($_REQUEST['a_nome']);
				$campos['email']=$_REQUEST['a_email'];
				$campos['telefone']=$_REQUEST['a_telefone'];
				$campos['celular']=$_REQUEST['a_celular'];
				$campos['ramal']=$_REQUEST['a_ramal'];
				$campos['fax']=$_REQUEST['a_fax'];
				$campos['site']=$_REQUEST['a_site'];
				if (file_exists($arquivo['tmp_name']))
				{
					$img=gImageResize($arquivo);
					$campos['imagem']=$img['file'];
					$campos['imagem_tipo']=$img['type'];
					$campos['imagem_largura']=$img['width'];
					$campos['imagem_altura']=$img['height'];
				}
				$up='';
				foreach ($campos as $key=>$value)
				{
					$up[]=$key."='$value'";
				}
				$update=implode(",",$up);

				$sql="update alitem.pessoas set $update where id=$gId";
				gFastQuery($sql);

				// J ou F
				$campos='';
				if ($_REQUEST['tipo']=="J")
				{
					$tipo="juridicas";
					$campos['razao_social']=gUcwords($_REQUEST['a_razao']);
					$campos['cnpj']=$_REQUEST['a_cnpj'];
					$campos['insc_municipal']=$_REQUEST['a_insc_municipal'];
					$campos['insc_estadual']=$_REQUEST['a_insc_estadual'];
				} else
				{
					$tipo="fisicas";
					$campos['cpf']=$_REQUEST['a_cpf'];
					$campos['rg']=$_REQUEST['a_rg'];
				}
				$up='';
				foreach ($campos as $key=>$value)
				{
					$up[]=$key."='$value'";
				}
				$update=implode(",",$up);
				$sql="update alitem.pessoas_$tipo set $update where id_pessoas=$gId";
				gFastQuery($sql);

				// Endereços
				$campos='';
				$campos['endereco']=$_REQUEST['a_endereco'];
				$campos['numero']=$_REQUEST['a_numero'];
				$campos['complemento']=$_REQUEST['a_complemento'];
				$campos['bairro']=$_REQUEST['a_bairro'];
				$campos['id_paises']=$_REQUEST['a_id_paises'];
				$campos['id_estados']=$_REQUEST['a_id_estados'];
				$campos['id_cidades']=$_REQUEST['a_id_cidades'];
				$campos['cep']=$_REQUEST['a_cep'];
				$up='';
				foreach ($campos as $key=>$value)
				{
					$up[]=$key."='$value'";
				}
				$update=implode(",",$up);
				$sql="update alitem.pessoas_enderecos set $update where id_pessoas=$gId";
				gFastQuery($sql);

				// Permissões
				$campos='';
				$campos['colaborador']=gDBCheck($_REQUEST['a_colaborador']);
				$campos['consumidor']=gDBCheck($_REQUEST['a_consumidor']);
				$campos['fornecedor']=gDBCheck($_REQUEST['a_fornecedor']);
				$up='';
				foreach ($campos as $key=>$value)
				{
					$up[]=$key."='$value'";
				}
				$update=implode(",",$up);

				$sql="update alitem.pessoas_permissoes_aplicativos
							set $update
							where id_aplicativos=".$_SESSION['gApp']." and id_pessoas=$usrIdd and id_pessoas_relacionado=$gId";
				gFastQuery($sql);
				$html=$this->msgMiniTitle("Cadastro alterado!");
			} else
			{
					$html=$this->msgError("Não foi possível alterar, pois foram encontrados os seguintes problemas:");
					foreach ($erro as $e)
						$html.=$this->msgMiniTitle($e)."<br>";
			}
		}
		$this->add("{name: 'gc7'; title: '$title'; subTitle: '$subTitle'; buttonBack: -2; buttonNextCaption: no}",$html);

//=== 8 Editar informações complementares

		$html='';
		if ($gPage==8)
		{
			$gId=intval($_REQUEST['gId']);
			$sql="select * from pessoas_complemento where id_pessoas=$gId";
			$rsc=gQuery($sql);
			if (!$rsc->EOF)
			{
				$mtz=cssDecode($rsc->fields['dados_adicionais']);
			}
				$fields=$this->complement->fields;
				foreach ($fields as $kfield=>$field)
				{
					//$fld=jsDecode($field);
					foreach ($mtz as $key=>$value)
					{
						$value=str_replace(chr(10),'\n',$value);
						$value=str_replace(chr(13),'',$value);
						if (
								(strpos($field,"name: '$key'")!==false) ||
								(strpos($field,"hiddenName: '$key'")!==false) ||
								(strpos($field,"name: \"$key\"")!==false)
							)
						{
							if (strpos($field,"checkbox")!==false)
							{
								if (gDBCheck($value)==1)
									$fields[$kfield]="{checked: true, ".substr($field,1);
							}
							else
								$fields[$kfield]="{value: '$value', ".substr($field,1);
						}
					}
				}
//echo "<pre>";print_r($rsc->fields['dados_adicionais']);echo "\n\n";print_r($mtz);print_r($fields);exit;
			$this->complement->fields=$fields;
			$html=$this->complement;
			$html->add("{name: n_id; type: hidden; value: $gId}");
		}
		$this->add("{name: 'gc8'; title: '$title'; subTitle: '$subTitle'; }",$html);

//=== 9

		$html='';
		if ($gPage==9)
		{
			$dados='{';
			foreach ($_REQUEST as $key=>$value)
			{
				if ((substr($key,0,2)<>"n_") && ($key<>'g') && ($key<>'gId') && ($key<>'nw') &&($key<>'gPage')&&($key<>'apelido')&&($key<>'nome')&&($key<>'email')&&($key<>'cpf')&&($key<>'cnpj')&&($key<>'PHPSESSID'))
				{
					$dados.="$key: $value;";
				}
			}
			$dados.='}';
			$form=str_replace("'",'',implode(",",$this->complement->getOriginalFields()));
			// Dados complementares
			if ($this->complement!='')
			{
				// Salva dados complementares (só se for novo)
				$sql="select * from pessoas_complemento where id_pessoas=".$_REQUEST['n_id'];
				$rsc=gQuery($sql);
				if ($rsc->EOF)
				{
					$sql="insert into pessoas_complemento (id_pessoas,dados_adicionais,formulario) values (".$_REQUEST['n_id'].",'$dados','$form')";
					gQuery($sql);
					$html=$this->msgMiniTitle("Cadastro efetuado com sucesso.");
				} else
				{
					$sql="update pessoas_complemento set dados_adicionais='$dados',formulario='$form' where id_pessoas=".$_REQUEST['n_id'];
					gQuery($sql);
					$html=$this->msgMiniTitle("Cadastro alterado com sucesso.");
				}
			}
		}
		$this->add("{name: 'gc9'; title: '$title'; subTitle: '$subTitle'; buttonBack: -2; buttonNextCaption: no}",$html);




		$this->showMultiPage();
	}








}


/*
class gAppResource
{

}
 *
 */
?>