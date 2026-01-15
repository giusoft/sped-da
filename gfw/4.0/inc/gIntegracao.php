<?php

include_once $gPathDefault . "gBrowser.php";

class rj_examesTeoricos {
	public $usuario= '';
	public $senha = '';
	public $errorCode= '';
	public $errorMessage= '';
	public $urls;
	public $dados='';


	function __construct($json)
	{
		$this->jarr    = cssDecode($json);
		$this->usuario = $this->jarr['usuario'];
		$this->senha   = $this->jarr['senha'];
		$this->browser = new gBrowser("{debug: true; timeout: 90}");
		$this->browser->defaultContentType = "text/html;charset=utf-8";
		$this->browser->usrAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:48.0) Gecko/20100101 Firefox/48.0';

		// $this->urls['index']       = "http://cfc.detran.rj.gov.br/index.asp";
		// $this->urls['agendar_1']   = "http://cfc.detran.rj.gov.br/tela1.asp";
		// $this->urls['agendar_2']   = "http://cfc.detran.rj.gov.br/tela12.asp";
		// $this->urls['agendar_3']   = "http://cfc.detran.rj.gov.br/tela13.asp";
		// $this->urls['agendar_4']   = "http://cfc.detran.rj.gov.br/tela14.asp";
		// $this->urls['agendar_5']   = "http://cfc.detran.rj.gov.br/tela15.asp";
		// $this->urls['cancelar_1']  = "http://cfc.detran.rj.gov.br/tela3.asp";
		// $this->urls['cancelar_2']  = "http://cfc.detran.rj.gov.br/tela32.asp";
		// $this->urls['cancelar_3']  = "http://cfc.detran.rj.gov.br/tela33.asp";
		// $this->urls['consultar_1'] = "http://cfc.detran.rj.gov.br/tela2.asp";
		// $this->urls['consultar_2'] = "http://cfc.detran.rj.gov.br/tela22.asp";

		$this->urls['index']       = "https://novocfcweb.detran.rj.gov.br/index.asp";
		$this->urls['agendar_1']   = "https://novocfcweb.detran.rj.gov.br/tela1.asp";
		$this->urls['agendar_2']   = "https://novocfcweb.detran.rj.gov.br/tela12.asp";
		$this->urls['agendar_3']   = "https://novocfcweb.detran.rj.gov.br/tela13.asp";
		$this->urls['agendar_4']   = "https://novocfcweb.detran.rj.gov.br/tela14.asp";
		$this->urls['agendar_5']   = "https://novocfcweb.detran.rj.gov.br/tela15.asp";
		$this->urls['cancelar_1']  = "https://novocfcweb.detran.rj.gov.br/tela3.asp";
		$this->urls['cancelar_2']  = "https://novocfcweb.detran.rj.gov.br/tela32.asp";
		$this->urls['cancelar_3']  = "https://novocfcweb.detran.rj.gov.br/tela33.asp";
		$this->urls['consultar_1'] = "https://novocfcweb.detran.rj.gov.br/tela2.asp";
		$this->urls['consultar_2'] = "https://novocfcweb.detran.rj.gov.br/tela22.asp";
	}

	/**
	 * Verifica se há conectividade com a Interprint
	 * @return boolean True/False (Tem ou não conectividade)
	 */
	function verificaConectividade()
	{
		// Testa conectividade
		$this->url = $this->urls['index'];
		$this->browser->get($this->url, 'Verificando conectividade');
		if ($this->browser->errorCode<>'')
		{
			$sai = false;
			$this->errorCode = $this->browser->errorCode;
			$this->errorMessage = $this->browser->errorMessage;
		} else {
			if (stripos($this->browser->html, 'Agendar exame')!==false)
			{
				$sai = true;
				$this->errorCode = 0;
				$this->errorMessage = '';
			} else {
				$sai = false;
				$this->errorCode = '600';
				$this->errorMessage = 'Site fora do ar...';
			}

		}

		$sql = "SELECT * FROM webcfc_admin.integracao WHERE provedor='rj_exames_teoricos' AND estado='rj'";
		$rs=dbQuery($sql);

		$flds = '';
		$flds['provedor']			= 'rj_exames_teoricos';
		$flds['estado']				= 'rj';
		$flds['url']				= $this->url;
		$flds['erro_codigo']		= $this->errorCode;
		$flds['erro_mensagem']		= $this->errorMessage;
		$flds['online']				= ($sai ? '1' : '0');
		$flds['data_atualizacao']	= date('Y-m-d H:i:s');

		if (count($rs)>0)
		{
			dbUpdate('webcfc_admin.integracao', $flds, $rs[0]['id']);
		} else {
			dbInsert('webcfc_admin.integracao', $flds);
		}
		return($sai);
	}

	function consultar($json)
	{
		$sai = true;
		$jarr = cssDecode($json);
		$cpf = $jarr['cpf'];
		$renach = $jarr['renach'];

		// Abre página inicial e pega cookie, verificando se o site está ok
		$this->url = $this->urls['index'];
		$this->browser->get($this->url,'Buscando cookie');
		if ($this->browser->errorCode<>'')
		{
			$sai = false;
			$this->errorCode = $this->browser->errorCode;
			$this->errorMessage = $this->browser->errorMessage;
		} else {
			if (stripos($this->browser->html, 'Agendar exame')!==false)
			{
				$this->browser->get($this->urls['consultar_1'],"Abrindo consulta");
				if ($this->browser->errorCode=='')
				{
					$this->browser->post['cpf']=$cpf;
					$this->browser->post['imageField.x']="34";
					$this->browser->post['imageField.y']="9";
					$this->browser->post['login']=$this->usuario;
					$this->browser->post['renach']=$renach;
					$this->browser->post['senha']=$this->senha;
					$this->browser->post['tipo']="leg";
					$this->browser->post($this->urls['consultar_2'],"Enviando dados");
					if (stripos($this->browser->html, 'Documento:')!==false)
					{
						$html=strip_tags($this->browser->html);
						$html=str_replace('&nbsp;&nbsp;',' ',$html);
						$html=str_replace('&nbsp;',' ',$html);
						$linhas = explode("\n", $html);
						$this->dados = '';
						for($f=0; $f<count($linhas); $f++)
						{
							$linha = autoencode(trim($linhas[$f]));
							if ($linha<>'')
							{
								$linhasTratadas[] = $linha;
							}
						}
						for($f=0; $f<count($linhasTratadas); $f++)
						{
							$linha = $linhasTratadas[$f];
							$proxLinha = $linhasTratadas[$f+1];
							if ($linha<>'')
							{
								switch($linha)
								{
									case 'Data / Hora:':
										$this->dados['data_bruta'] = $proxLinha;
										$this->dados['data'] = $this->browser->formatDate($proxLinha).':00';
										break;
									case 'Posto:':
										$this->dados['posto'] = $proxLinha;
										break;
									case 'Endereço:':
										$this->dados['endereco'] = $proxLinha;
										break;
									case 'Bairro:':
										$this->dados['bairro'] = $proxLinha;
										break;
									case 'Renach:':
										$this->dados['renach'] = $proxLinha;
										break;
									case 'Nome:':
										$this->dados['nome'] = $proxLinha;
										break;
									case 'CPF:':
										$this->dados['cpf'] = $proxLinha;
										break;
									case 'Data Nasc:':
										$this->dados['data_nascimento'] = $this->browser->formatDate($proxLinha);
										break;
								}
							}
						}
						$sai = true;
					} else {
						$sai = false;
						$this->errorCode = '602';
						$this->errorMessage = 'Este aluno não possui agendamento para o exame teórico no DETRAN.';
					}
				} else {
					$sai = false;
					$this->errorCode = '601';
					$this->errorMessage = 'Sistema do Detran com erro na página da consulta';
				}
			} else {
				$sai = false;
				$this->errorCode = '600';
				$this->errorMessage = 'Site fora do ar...';
			}
		}
		return($sai);
	}

	function cancelar($json)
	{
		$sai = true;
		$jarr = cssDecode($json);
		$cpf = $jarr['cpf'];
		$renach = $jarr['renach'];

		// Abre página inicial e pega cookie, verificando se o site está ok
		$this->url = $this->urls['index'];
		$this->browser->get($this->url,'Buscando cookie');
		if ($this->browser->errorCode<>'')
		{
			$sai = false;
			$this->errorCode = $this->browser->errorCode;
			$this->errorMessage = $this->browser->errorMessage;
		} else {
			if (stripos($this->browser->html, 'Agendar exame')!==false)
			{
				$this->browser->get($this->urls['cancelar_1'],"Abrindo consulta");
				if ($this->browser->errorCode=='')
				{
					$this->browser->post['cpf']=$cpf;
					$this->browser->post['imageField.x']="34";
					$this->browser->post['imageField.y']="9";
					$this->browser->post['login']=$this->usuario;
					$this->browser->post['renach']=$renach;
					$this->browser->post['senha']=$this->senha;
					$this->browser->post['tipo']="leg";
					$this->browser->post($this->urls['cancelar_2'],"Enviando dados");
					if (stripos($this->browser->html, 'Confirmar o cancelamento?')!==false)
					{
						$this->browser->getHtmlObject();
						$this->browser->post='';
						$this->browser->post['NomePosto'] = $this->browser->htmlObject->getElementsByTagName('input')->item(0)->getAttribute('value');
						$this->browser->post['enviar'] = $this->browser->htmlObject->getElementsByTagName('input')->item(1)->getAttribute('value');
//gD($this->browser->post);exit;
						$this->browser->post($this->urls['cancelar_3'],"Cancelando");
						$sai = true;
					} else {
						$html = substr($this->browser->html, strpos($this->browser->html,'<td class="corpo">'));
						$html = strip_tags(substr($html, 0, strpos($html, '</td>')+5));
						$sai = false;
						$this->errorCode = '603';
						$this->errorMessage = 'Erro na tentativa de cancelamento de exame.<br>'.$html;
					}
				} else {
					$sai = false;
					$this->errorCode = '601';
					$this->errorMessage = 'Sistema do Detran com erro na página da consulta';
				}
			} else {
				$sai = false;
				$this->errorCode = '600';
				$this->errorMessage = 'Site fora do ar...';
			}
		}
		return($sai);
	}

	function buscarLocais($json)
	{
		$sai = true;
		$jarr = cssDecode($json);
		$cpf = $jarr['cpf'];
		$renach = $jarr['renach'];
		$this->dados = '';

		// Abre página inicial e pega cookie, verificando se o site está ok
		$this->url = $this->urls['index'];
		$this->browser->get($this->url,'Buscando cookie');
		if ($this->browser->errorCode<>'')
		{
			$sai = false;
			$this->errorCode = $this->browser->errorCode;
			$this->errorMessage = $this->browser->errorMessage;
		} else {
			if (stripos($this->browser->html, 'Agendar exame')!==false)
			{
				$this->browser->get($this->urls['agendar_1'],"Abrindo consulta");
				if ($this->browser->errorCode=='')
				{
					$this->browser->post['cpf']=$cpf;
					$this->browser->post['imageField.x']="34";
					$this->browser->post['imageField.y']="9";
					$this->browser->post['login']=$this->usuario;
					$this->browser->post['renach']=$renach;
					$this->browser->post['senha']=$this->senha;
					$this->browser->post['tipo']="leg";
					$this->browser->post['libras']="N";
					$this->browser->post['opcao']="2";

					$this->browser->post($this->urls['agendar_2'],"Enviando dados");
					if (stripos($this->browser->html, 'Selecione o local desejado')!==false)
					{
						$this->browser->getHtmlObject();
						$tables = $this->browser->htmlObject->getElementsByTagName('table');
						foreach ($tables as $table)
						{
							if ($table->getAttribute('rules')=='none')
							{
								$trs = $table->getElementsByTagName('tr');
								foreach ($trs as $tr)
								{
									if ($tr->getAttribute('valign')=='top')
									{
										$id = $tr->getElementsByTagName('input')->item(0)->getAttribute('value');
										$tablePostos = $tr->getElementsByTagName('table');
										foreach ($tablePostos as $tablePosto)
										{
											$tds = $tablePosto->getElementsByTagName('td');
											$posto['codigo'] = $id;
											$posto['posto'] = superTrim($tds->item(1)->nodeValue);
											$posto['endereco'] = gUcwords(superTrim($tds->item(3)->nodeValue));
											$posto['bairro'] = gUcwords(superTrim($tds->item(5)->nodeValue));
											$this->dados[$id] = $posto;
										}
									}
								}
								break;
							}
						}
						$sai = true;
					} else {
						$html = substr($this->browser->html, strpos($this->browser->html,'<td class="corpo">'));
						$html = strip_tags(substr($html, 0, strpos($html, '</td>')+5));
						$sai = false;
						$this->errorCode = '603';
						$this->errorMessage = 'Não foi possível buscar locais. Aluno já agendado.<br>'.$html;
					}
				} else {
					$sai = false;
					$this->errorCode = '601';
					$this->errorMessage = 'Sistema do Detran com erro na página da consulta';
				}
			} else {
				$sai = false;
				$this->errorCode = '600';
				$this->errorMessage = 'Site fora do ar...';
			}
		}
		return($sai);
	}

	function buscarDatas($json)
	{
		$sai         = true;
		$jarr        = cssDecode($json);
		$cpf         = $jarr['cpf'];
		$renach      = $jarr['renach'];
		$posto       = $jarr['posto'];

		if ($this->buscarLocais($json))
		{
			$inputs = $this->browser->htmlObject->getElementsByTagName('input');
			$this->browser->post='';
			foreach ($inputs as $input)
			{
				if ($input->getAttribute('name')=='DescRegiao')
					$this->browser->post['DescRegiao']=$input->getAttribute('value');
				if ($input->getAttribute('name')=='Continuar')
					$this->browser->post['Continuar']=$input->getAttribute('value');
				if ($input->getAttribute('name')=='enviar')
					$this->browser->post['enviar']=$input->getAttribute('value');
			}
			$this->browser->post['imageField.x']="34";
			$this->browser->post['imageField.y']="9";
			$this->browser->post['libras']="N";
			$this->browser->post['posto']=$posto;

			$this->browser->post($this->urls['agendar_3'],"Obtendo datas");

			if (stripos($this->browser->html, 'Selecione a Data e o Turno')!==false)
			{
				$this->browser->getHtmlObject();
				$inputs = $this->browser->htmlObject->getElementsByTagName('input');
				$this->dados = '';
				foreach ($inputs as $input)
				{
					if ($input->getAttribute('type')=='radio')
					{
						$this->dados[] = $input->getAttribute('value');
					}
				}
				$sai = true;
			} else {
				$sai = false;
				$this->errorCode = '604';
				$this->errorMessage = 'Não foi possível obter datas neste local de exame.';
			}
		}

		return($sai);
	}

	function buscarHoras($json)
	{
		$sai         = true;
		$jarr        = cssDecode($json);
		$cpf         = $jarr['cpf'];
		$renach      = $jarr['renach'];
		$posto       = $jarr['posto'];
		$descPosto   = $jarr['descPosto'];
		$data        = $jarr['data'];

		if ($this->buscarDatas($json))
		{
			$inputs = $this->browser->htmlObject->getElementsByTagName('input');
			$this->browser->post='';
			foreach ($inputs as $input)
			{
				if ($input->getAttribute('name')=='enviar')
					$this->browser->post['enviar']=$input->getAttribute('value');
			}
			$this->browser->post['x']="34";
			$this->browser->post['y']="9";
			$this->browser->post['libras']="N";
			$this->browser->post['dataeturno']=$data;
			$this->browser->post['descposto']=$descPosto;

			$this->browser->post($this->urls['agendar_4'],"Obtendo horas");

			if (stripos($this->browser->html, 'Selecione o Hor')!==false)
			{
				$this->browser->getHtmlObject();
				$options = $this->browser->htmlObject->getElementsByTagName('option');
				$this->dados = '';
				foreach ($options as $option)
				{
					$this->dados[] = $option->getAttribute('value');
				}
				$sai = true;
			} else {
				$sai = false;
				$this->errorCode = '604';
				$this->errorMessage = 'Não foi possível agendar este exame.';
			}
		}

		return($sai);
	}

	function agendarExame($json)
	{
		$sai         = true;
		$jarr        = cssDecode($json);
		$cpf         = $jarr['cpf'];
		$renach      = $jarr['renach'];
		$posto       = $jarr['posto'];
		$descPosto   = $jarr['descPosto'];
		$data        = $jarr['data'];
		$hora        = $jarr['hora'];

		if ($this->buscarHoras($json))
		{
			$inputs = $this->browser->htmlObject->getElementsByTagName('input');
			$this->browser->post='';
			foreach ($inputs as $input)
			{
				if ($input->getAttribute('name')=='enviar')
					$this->browser->post['enviar']=$input->getAttribute('value');
			}
			$this->browser->post['imageField.x']="34";
			$this->browser->post['imageField.y']="9";
			$this->browser->post['hora']=$hora;
			$this->browser->post['libras']="N";

			$this->browser->post($this->urls['agendar_5'],"Agendando exame");
			// Hoje o sistema apresenta um erro ao agendar, mas agenda corretamente
			// Não há como reconhecer se a tarefa foi efetuada com sucesso ou não
			$sai = true;
		}

		return($sai);
	}

	function buscarIdLocalExame($id_geral_tipos_exames, $descricao, $endereco='', $bairro='', $codigo='')
	{
		$sai = 0;
		if ($codigo=='')
		{
			$sql = "SELECT * FROM geral_locais_exames
					WHERE id_geral_tipos_exames=$id_geral_tipos_exames AND descricao='$descricao'";

		} else {
			$sql = "SELECT * FROM geral_locais_exames
					WHERE id_geral_tipos_exames=$id_geral_tipos_exames AND codigo='$codigo'";
		}
		$rs = dbQuery($sql);
		if (count($rs)>0)
		{
			$sai = $rs[0]['id'];
			// Atualiza dados (para os casos de mundaça de endereço)
			$flds = '';
			if ($codigo<>'')
				$flds['codigo'] = $codigo;
			$flds['descricao']  = $descricao;
			$flds['endereco']   = $endereco;
			$flds['bairro']     = $bairro;
			dbUpdate('geral_locais_exames', $flds, $sai);
		} else {
			// Insere
			$flds                          = '';
			$flds['id_geral_tipos_exames'] = $id_geral_tipos_exames;
			$flds['codigo']                = $codigo;
			$flds['descricao']             = $descricao;
			$flds['endereco']              = gUcwords($endereco);
			$flds['bairro']                = gUcwords($bairro);
			$flds['bloqueado']             = '1';
			$sai = dbInsert('geral_locais_exames', $flds, true);
		}
		return($sai);
	}
}

class rj_examesPraticos {
	public $usuario      = '';
	public $senha        = '';
	public $cfc          = '';
	public $errorCode    = '';
	public $errorMessage = '';
	public $logado       = false;
	public $urls;

	function __construct($json)
	{
		$this->jarr    = cssDecode($json);
		$this->usuario = $this->jarr['usuario'];
		$this->senha   = $this->jarr['senha'];
		$this->cfc     = $this->jarr['cfc'];
		$this->browser = new gBrowser("{debug: true; timeout: 90}");
		$this->browser->defaultContentType = "text/html;charset=utf-8";
		$this->browser->usrAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:48.0) Gecko/20100101 Firefox/48.0';
		$this->urls['index'] = "https://refor.detran.rj.gov.br/ReforWeb/servlet/StartCISPage?PAGEURL=/cisnatural/NatLogon.html&xciParameters.natsession=ReforWeb";
		$this->urls['login_0'] = "https://refor.detran.rj.gov.br/ReforWeb/servlet/StartCISPage?PAGEURL=/cisnatural/NatLogon.html&xciParameters.natsession=ReforWeb";
		$this->urls['login_1'] = "https://refor.detran.rj.gov.br/ReforWeb/servlet/Connector";
	}
	/**
	 * Verifica se há conectividade com a Interprint
	 * @return boolean True/False (Tem ou não conectividade)
	 */
	function verificaConectividade()
	{
		// Testa conectividade
		$this->url = $this->urls['index'];
		$this->browser->get($this->url, 'Verificando conectividade');
		if ($this->browser->errorCode<>'')
		{
			$sai = false;
			$this->errorCode = $this->browser->errorCode;
			$this->errorMessage = $this->browser->errorMessage;
		} else {
			if (stripos($this->browser->html, 'NatLogon.html')!==false)
			{
				$sai = true;
				$this->errorCode = 0;
				$this->errorMessage = '';
			} else {
				$sai = false;
				$this->errorCode = '600';
				$this->errorMessage = 'Site fora do ar...';
			}

		}

		$sql = "SELECT * FROM webcfc_admin.integracao WHERE provedor='rj_exames_praticos' AND estado='rj'";
		$rs=dbQuery($sql);

		$flds = '';
		$flds['provedor']			= 'rj_exames_praticos';
		$flds['estado']				= 'rj';
		$flds['url']				= $this->url;
		$flds['erro_codigo']		= $this->errorCode;
		$flds['erro_mensagem']		= $this->errorMessage;
		$flds['online']				= ($sai ? '1' : '0');
		$flds['data_atualizacao']	= date('Y-m-d H:i:s');

		if (count($rs)>0)
		{
			dbUpdate('webcfc_admin.integracao', $flds, $rs[0]['id']);
		} else {
			dbInsert('webcfc_admin.integracao', $flds);
		}
		return($sai);
	}

	function login()
	{
		if (!$this->logado)
		{
			$this->url = $this->urls['index'];
			$this->browser->get($this->url,'Buscando cookie');
			if ($this->browser->errorCode<>'')
			{
				$sai = false;
				$this->errorCode = $this->browser->errorCode;
				$this->errorMessage = $this->browser->errorMessage;
			} else {
				// Obtém o SessionId
				$this->url = $this->urls['login_0'];
				$this->browser->get($this->url,'Obtendo sessionId');
				$p = stripos($this->browser->html, 'var m_currentSessionId');
				
				if ($p!==false)
				{
					$script = substr($this->browser->html, $p);
					$p = stripos($script, 'function goodBye');
					$script = substr($script, 0, $p);
					$this->sessionId = $this->browser->runJavascript($script, 'm_currentSessionId');

					//echo "sessionId: $this->sessionId";exit;

					/*
					ERRORMESSAGE=
					PAGEINITPARAM=
					SESSIONID=CASA156415_765699918783
					STAMP=1001
					XML=v2332004405062000135CASA156415_76569991878314856227231471468805648709604115com.softwareag.cis.adapter.uicrefor+A0000000dummyhttps://refor.detran.rj.gov.br/ReforWeb/uicrefor/A0000000.htmlonclickenviarfalsep0900008idusuarioUR849742p0500006senhagedeonp1500003pagePixelHeight524p1400004pagePixelWidth1437p1100002repeatIndex-1p2200008cISAddons.focusProjectuicreforp1900008cISAddons.focusPageA0000000p2300005cISAddons.focusItemNamesenhax079453654
					(100000000000 + parseInt( Math.random() * ( 999999999999-100000000000+1 ))
					var m_currentSessionId = "CASA156425";
					 */

					// $this->url = $this->urls['login_0'];
					// $this->browser->post='';
					// $this->browser->post['ERRORMESSAGE']='';
					// $this->browser->post['PAGEINITPARAM']='';
					// $this->browser->post['SESSIONID']=$sessionId;
					// $this->browser->post['STAMP']='';
					// $this->browser->post['XML']='';
					//
					// $this->browser->post($this->url,'Efetuando login');
					// if ($this->browser->errorCode=='')
					// {
					//
					// } else {
					// 	$sai = false;
					// 	$this->errorCode = $this->browser->errorCode;
					// 	$this->errorMessage = $this->browser->errorMessage;
					// }

				} else {
					$sai = false;
					$this->errorCode = '699';
					$this->errorMessage = 'Erro ao obter o sessionId';
				}




			}
			$this->logado=true;
		}
	}

	function logout()
	{
		if ($this->logado)
		{
			$this->browser->endSession();
			$this->logado=false;
		}
	}

	function consultar($json)
	{
		$sai                    = true;
		$jarr                   = cssDecode($json);
		$cpf                    = $jarr['cpf'];
		$renach                 = $jarr['renach'];
		$data_de                = $jarr['data_de'];
		$data_ate               = $jarr['data_ate'];
		$id_geral_locais_exames = $jarr['id_geral_locais_exames'];

		// Abre página inicial e pega cookie, verificando se o site está ok
		if ($this->login())
		{
			$this->url = $this->urls['index'];
			$this->browser->get($this->url,'Buscando cookie');
			if ($this->browser->errorCode<>'')
			{
				$sai = false;
				$this->errorCode = $this->browser->errorCode;
				$this->errorMessage = $this->browser->errorMessage;
			} else {
				if (stripos($this->browser->html, 'Agendar exame')!==false)
				{
					$this->browser->get($this->urls['consultar_1'],"Abrindo consulta");
					if ($this->browser->errorCode=='')
					{
						$this->browser->post['cpf']=$cpf;
						$this->browser->post['imageField.x']="34";
						$this->browser->post['imageField.y']="9";
						$this->browser->post['login']=$this->usuario;
						$this->browser->post['renach']=$renach;
						$this->browser->post['senha']=$this->senha;
						$this->browser->post['tipo']="leg";
						$this->browser->post($this->urls['consultar_2'],"Enviando dados");
						if (stripos($this->browser->html, 'Documento:')!==false)
						{
							$html=strip_tags($this->browser->html);
							$html=str_replace('&nbsp;&nbsp;',' ',$html);
							$html=str_replace('&nbsp;',' ',$html);
							$linhas = explode("\n", $html);
							$this->dados = '';
							for($f=0; $f<count($linhas); $f++)
							{
								$linha = autoencode(trim($linhas[$f]));
								if ($linha<>'')
								{
									$linhasTratadas[] = $linha;
								}
							}
							for($f=0; $f<count($linhasTratadas); $f++)
							{
								$linha = $linhasTratadas[$f];
								$proxLinha = $linhasTratadas[$f+1];
								if ($linha<>'')
								{
									switch($linha)
									{
										case 'Data / Hora:':
											$this->dados['data_bruta'] = $proxLinha;
											$this->dados['data'] = $this->browser->formatDate($proxLinha).':00';
											break;
										case 'Posto:':
											$this->dados['posto'] = $proxLinha;
											break;
										case 'Endereço:':
											$this->dados['endereco'] = $proxLinha;
											break;
										case 'Bairro:':
											$this->dados['bairro'] = $proxLinha;
											break;
										case 'Renach:':
											$this->dados['renach'] = $proxLinha;
											break;
										case 'Nome:':
											$this->dados['nome'] = $proxLinha;
											break;
										case 'CPF:':
											$this->dados['cpf'] = $proxLinha;
											break;
										case 'Data Nasc:':
											$this->dados['data_nascimento'] = $this->browser->formatDate($proxLinha);
											break;
									}
								}
							}
							$sai = true;
						} else {
							$sai = false;
							$this->errorCode = '602';
							$this->errorMessage = 'Este aluno não possui agendamento para o exame teórico no DETRAN.';
						}
					} else {
						$sai = false;
						$this->errorCode = '601';
						$this->errorMessage = 'Sistema do Detran com erro na página da consulta';
					}
				} else {
					$sai = false;
					$this->errorCode = '600';
					$this->errorMessage = 'Site fora do ar...';
				}
			}

		}
		return($sai);
	}




}

/**
 * Classe responsável pela integração com a Interprint para agendamento e presenças de aulas práticas e teóricas
 */
class interprint
{
	public $browser;

	// Parâmetros
	public $usuario              = '';
	public $usuarioNome          = '';
	public $sistema              = '';
	public $senha                = '';
	public $estado               = '';
	public $cpf                  = '';

	// Resultados
	public $agenda               = '';
	public $agendaBruta          = '';
	public $cabecalho            = '';
	public $instrutores          = '';
	public $servicos             = '';
	public $disciplinas          = '';
	public $horarios             = '';
	public $cadastrosDisponiveis = '';
	public $cadastrosEfetuados   = '';
	public $cadastrosMensagem	 = '';
	public $aulas                = '';

	public $inputsById           = '';
	public $inputsByName         = '';
	public $select               = '';
	public $errorCode            = '';
	public $errorMessage         = '';

	public $jarr                 = '';
	public $url                  = '';
	public $logado               = false;
	public $buscouAgenda         = false;

	public $urls;

	function __construct($json)
	{
		$this->jarr 							         = cssDecode($json);
		$this->url 								         = $this->jarr['url'];
		$this->estado 							         = $this->jarr['estado'];
		$this->usuario 							         = $this->jarr['usuario'];
		$this->senha 							         = $this->jarr['senha'];
		$this->cpf								         = $this->jarr['cpf'];

		$this->urls['ba']['login']				         = 'http://www.ba.cfc.interprint.com.br:8112/login.aspx';
		$this->urls['ba']['login_0']					 = 'http://www.ba.cfc.interprint.com.br:8112/principal.aspx?sis=1';
		$this->urls['ba']['logout']				         = 'http://www.ba.cfc.interprint.com.br:8112/LOGOUT.ASPX';
		$this->urls['ba']['cadastro_0']			         = 'http://www.ba.cfc.interprint.com.br:8112/CFC/CADCANDIDATO.ASPX';
		$this->urls['ba']['cadastro_1']			         = 'http://www.ba.cfc.interprint.com.br:8112/CFC/CadCandidato.aspx?opt=1';
		$this->urls['ba']['agenda']				         = 'http://www.ba.cfc.interprint.com.br:8112/CFC/CONSAGENDACANDIDATO.ASPX';
		$this->urls['ba']['agenda_grade']		         = 'http://www.ba.cfc.interprint.com.br:8112/CFC/AGENDAMENTO3.ASPX';
		$this->urls['ba']['agenda_pratico_incluir']      = 'http://www.ba.cfc.interprint.com.br:8112/CFC/Agendamento_Pratico.aspx';
		$this->urls['ba']['agenda_popup']                = 'http://www.ba.cfc.interprint.com.br:8112/CFC/Agendamento_Candidato.aspx';
		$this->urls['ba']['agenda_incluir']              = 'http://www.ba.cfc.interprint.com.br:8112/CFC/Agendamento_Candidato.aspx';
		$this->urls['ba']['agenda_incluir_1']            = 'http://www.ba.cfc.interprint.com.br:8112/CFC/Agendamento_Pratico.aspx';
		$this->urls['ba']['agenda_incluir_2']            = 'http://www.ba.cfc.interprint.com.br:8112/CFC/Agendamento3_Novo.aspx';
		$this->urls['ba']['instrutores_id']		         = 'http://www.ba.cfc.interprint.com.br:8112/CFC/CONSGRADETURMA.ASPX';
		$this->urls['ba']['servicos']			         = 'http://www.ba.cfc.interprint.com.br:8112/CFC/AGENDAMENTO3.ASPX';
		$this->urls['ba']['disciplinas']                 = 'http://www.ba.cfc.interprint.com.br:8112/CFC/AGENDAMENTO3.ASPX';
		$this->urls['ba']['aulas_periodo_0']             = 'http://www.ba.cfc.interprint.com.br:8112/CFC/CONSGRADETURMA.ASPX';
		$this->urls['ba']['aulas_periodo_1']             = 'http://www.ba.cfc.interprint.com.br:8112/CFC/ConsAgendaPorTurma.aspx';
		$this->urls['ba']['foto']                        = 'http://www.ba.cfc.interprint.com.br:8112/CFC/Imagem.aspx?Tipo=1&CPF=';

		$this->urls['ce']['login']				         = 'http://www.ce.cfc.interprint.com.br:8072/login.aspx';
		$this->urls['ce']['login_0']					 = 'http://www.ce.cfc.interprint.com.br:8072/principal.aspx?sis=1';
		$this->urls['ce']['logout']				         = 'http://www.ce.cfc.interprint.com.br:8072/LOGOUT.ASPX';
		$this->urls['ce']['cadastro_0']			         = 'http://www.ce.cfc.interprint.com.br:8072/CFC/CADCANDIDATO.ASPX';
		$this->urls['ce']['cadastro_1']			         = 'http://www.ce.cfc.interprint.com.br:8072/CFC/CadCandidato.aspx?opt=1';
		$this->urls['ce']['agenda']				         = 'http://www.ce.cfc.interprint.com.br:8072/CFC/CONSAGENDACANDIDATO.ASPX';
		$this->urls['ce']['agenda_grade']		         = 'http://www.ce.cfc.interprint.com.br:8072/CFC/AGENDAMENTO3.ASPX';
		$this->urls['ce']['agenda_pratico_incluir']      = 'http://www.ce.cfc.interprint.com.br:8072/CFC/Agendamento_Pratico.aspx';
		$this->urls['ce']['agenda_popup']                = 'http://www.ce.cfc.interprint.com.br:8072/CFC/Agendamento_Candidato.aspx';
		$this->urls['ce']['agenda_incluir']              = 'http://www.ce.cfc.interprint.com.br:8072/CFC/Agendamento_Candidato.aspx';
		$this->urls['ce']['agenda_incluir_1']            = 'http://www.ce.cfc.interprint.com.br:8072/CFC/Agendamento_Pratico.aspx';
		$this->urls['ce']['agenda_incluir_2']            = 'http://www.ce.cfc.interprint.com.br:8072/CFC/Agendamento3_Novo.aspx';
		$this->urls['ce']['instrutores_id']		         = 'http://www.ce.cfc.interprint.com.br:8072/CFC/CONSGRADETURMA.ASPX';
		$this->urls['ce']['instrutores_cpf']	         = 'http://www.ce.cfc.interprint.com.br:8072/CFC/MANUTPRESENCAINST.ASPX';
		$this->urls['ce']['servicos']			         = 'http://www.ce.cfc.interprint.com.br:8072/CFC/AGENDAMENTO3.ASPX';
		$this->urls['ce']['disciplinas']                 = 'http://www.ce.cfc.interprint.com.br:8072/CFC/AGENDAMENTO3.ASPX';
		$this->urls['ce']['aulas_periodo_0']             = 'http://www.ce.cfc.interprint.com.br:8072/CFC/CONSGRADETURMA.ASPX';
		$this->urls['ce']['aulas_periodo_1']             = 'http://www.ce.cfc.interprint.com.br:8072/CFC/ConsAgendaPorTurma.aspx';
		$this->urls['ce']['foto']                        = 'http://www.ce.cfc.interprint.com.br:8072/CFC/Imagem.aspx?Tipo=1&CPF=';

		$this->urls['rj']['login']				         = 'https://novocfcweb.detran.rj.gov.br/login.aspx';
		$this->urls['rj']['login_0']					 = 'https://novocfcweb.detran.rj.gov.br/principal.aspx?sis=1';
		$this->urls['rj']['logout']				         = 'https://novocfcweb.detran.rj.gov.br/LOGOUT.ASPX';
		$this->urls['rj']['instrutores_id']		         = 'https://novocfcweb.detran.rj.gov.br/CFC/CONSGRADETURMA.ASPX';
		$this->urls['rj']['instrutores_cpf']	         = 'https://novocfcweb.detran.rj.gov.br/CRT/MANUTPRESENCAINST.ASPX';
		$this->urls['rj']['servicos']			         = 'https://novocfcweb.detran.rj.gov.br/CFC/AGENDAMENTO3.ASPX';
		$this->urls['rj']['disciplinas']		         = 'https://novocfcweb.detran.rj.gov.br/CFC/AGENDAMENTO3.ASPX';
		$this->urls['rj']['agenda']				         = 'https://novocfcweb.detran.rj.gov.br/CFC/CONSAGENDACANDIDATO.ASPX';
		$this->urls['rj']['agenda_pratico_incluir']      = 'https://novocfcweb.detran.rj.gov.br/CFC/Agendamento_Pratico.aspx';
		$this->urls['rj']['cadastro_0']			         = 'https://novocfcweb.detran.rj.gov.br/CFC/CADCANDIDATO.ASPX';
		$this->urls['rj']['cadastro_1']			         = 'https://novocfcweb.detran.rj.gov.br/CFC/CadCandidato.aspx?opt=1';
		$this->urls['rj']['cadastro_2']			         = 'https://novocfcweb.detran.rj.gov.br/CFC/CadCandidato.aspx?opt=2&lista=1';
		$this->urls['rj']['agenda_grade']                = 'https://novocfcweb.detran.rj.gov.br/CFC/AGENDAMENTO3.ASPX';
		$this->urls['rj']['agenda_incluir']              = 'https://novocfcweb.detran.rj.gov.br/CFC/Agendamento_Candidato.aspx';
		$this->urls['rj']['agenda_incluir_1']            = 'https://novocfcweb.detran.rj.gov.br/CFC/Agendamento_Pratico.aspx';
		$this->urls['rj']['agenda_incluir_2']            = 'https://novocfcweb.detran.rj.gov.br/CFC/Agendamento3_Novo.aspx';
		$this->urls['rj']['agenda_popup']                = 'https://novocfcweb.detran.rj.gov.br/CFC/Agendamento_Candidato.aspx';
		$this->urls['rj']['aulas_periodo_0']             = 'https://novocfcweb.detran.rj.gov.br/CFC/CONSGRADETURMA.ASPX';
		$this->urls['rj']['aulas_periodo_1']             = 'https://novocfcweb.detran.rj.gov.br/CFC/ConsAgendaPorTurma.aspx';
		$this->urls['rj']['foto']                        = 'https://novocfcweb.detran.rj.gov.br/CFC/Imagem.aspx?Tipo=1&CPF=';





		$this->browser = new gBrowser("{debug: true; timeout: 90}");
		$this->browser->defaultContentType = "text/html;charset=utf-8";
		$this->browser->usrAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:48.0) Gecko/20100101 Firefox/48.0';
}

	/**
	 * Verifica se há conectividade com a Interprint
	 * @return boolean True/False (Tem ou não conectividade)
	 */
	function verificaConectividade()
	{
		// Testa conectividade
		$this->url = $this->urls[$this->estado]['login'];
		$this->browser->get($this->url);
		if (stripos($this->browser->html, 'LOGIN')!==false)
		{
			$sai = true;
			$this->errorCode = 0;
			$this->errorMessage = '';
		} else {
			$sai = false;
			$this->errorCode = '600';
			$this->errorMessage = 'Site fora do ar...';
		}

		$sql = "SELECT * FROM webcfc_admin.integracao WHERE provedor='interprint' AND estado='".$this->estado."'";
		$rs=dbQuery($sql);

		$flds = '';
		$flds['provedor']			= 'interprint';
		$flds['estado']				= $this->estado;
		$flds['url']				= $this->url;
		$flds['erro_codigo']		= $this->errorCode;
		$flds['erro_mensagem']		= $this->errorMessage;
		$flds['online']				= ($sai ? '1' : '0');
		$flds['data_atualizacao']	= date('Y-m-d H:i:s');

		if (count($rs)>0)
		{
			dbUpdate('webcfc_admin.integracao', $flds, $rs[0]['id']);
		} else {
			dbInsert('webcfc_admin.integracao', $flds);
		}
		return($sai);
	}

	/**
	 * Efetua login no sistema da Interprint
	 * @return boolean True/False - Login com sucesso
	 */
	function login()
	{
		$sai = false;
		if (!$this->logado)
		{
			// Formulário de login
			jLog('LIN Abrindo formulário de login', $this->browser->session, 'aviso');
			if ($this->verificaConectividade())
			{
				switch ($this->estado)
				{
					case 'rj':
					case 'ba':
					case 'ce':
						$this->browser->getHtmlObject(true);
						$this->browser->post['__LASTFOCUS'] = $this->browser->getField('id-input','__LASTFOCUS');
						$this->browser->post['__EVENTTARGET'] = $this->browser->getField('id-input','__EVENTTARGET');
						$this->browser->post['__EVENTARGUMENT'] = $this->browser->getField('id-input','__EVENTARGUMENT');
						$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
						$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
						$this->browser->post['ComputerName']='';
						$this->browser->post['btnLogin']='Entrar';
						$this->browser->post['txtSenha']=$this->senha;
						$this->browser->post['txtUsuario']=$this->usuario;
						jLog('LIN Enviado usuário e senha', $this->browser->session, 'aviso');

						$this->browser->post($this->url);

						if (strpos($this->browser->html, 'Usuário')!==false)
						{
							$sai = true;
							$this->browser->getHtmlObject(true);
							$this->usuarioNome = $this->browser->getField('id','CFCMaster_lblNmUsuario');
							$this->sistema =  $this->browser->getField('id','CFCMaster_lblNmSistema');
							jLog('LIN Login bem-sucedido. Nome do usuário: '.$this->usuarioNome, $this->browser->session, 'aviso');
							//$this->browser->get($this->urls[$this->estado]['login_0']);

						} else {
							$this->errorCode = '601';
							$this->errorMessage = 'Login falhou! Usuário: '.$this->usuario." / Senha: ".$this->senha;
							jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
						}
						break;
				}
				$this->logado = true;
			}
		} else {
			$sai = true;
		}
		return($sai);
	}

	/**
	 * Efetua logout no sistema da Interprint
	 */
	function logout()
	{
		if ($this->logado)
		{
			jLog('LIN Fazendo logout', $this->browser->session, 'aviso');
			$this->browser->get($this->urls[$this->estado]['logout']);
			$this->logado = false;
		}
	}

	/**
	 * Processa dados da agenda do CPF/Aluno obtidos e retorna array com valores na propriedade da Classe
	 */
	function processaAgenda()
	{
		jLog('AGE Processando agenda', $this->browser->session, 'aviso');

		switch ($this->estado)
		{
			case 'rj':
			case 'ba':
			case 'ce':
				$div = $this->browser->htmlObject->getElementById('CFCMaster_cphBody_pnlCandidato');
				if (is_object($div))
				{
					$table = $div->getElementsByTagName('table');
					if (is_object($table))
					{
						// Processa cabeçalho
						$tr = $table->item(0)->getElementsByTagName('tr');
						$td = $tr->item(0)->getElementsByTagName('td');
						$this->cabecalho['nome'] = $this->browser->htmlObject->getElementById('CFCMaster_cphBody_lblNomeCand')->nodeValue;
						$this->cabecalho['renach'] = $this->browser->format($td->item(1)->nodeValue);
						$this->cabecalho['cfc'] = $this->browser->format($td->item(3)->nodeValue);
						$this->cabecalho['data_abertura'] = $this->browser->format($td->item(5)->nodeValue, 'date');
						$this->cabecalho['motivo'] = $this->browser->format($td->item(7)->nodeValue);
						// Processa agendamentos
						$tr = $table->item(1)->getElementsByTagName('tr');
						for ($i = 1; $i < $tr->length; $i++) {
							$td = $tr->item($i)->getElementsByTagName('td');
							if ($td->length>3)
							{
								$row = '';
								for ($j = 0; $j < $td->length; $j++)
								{
									if ($j<7)
										$row[] = $this->browser->format(strip_tags($td->item($j)->nodeValue),'string');
									else
									{
										$td2 = $td->item($j)->getElementsByTagName('img');
										if ($td2->length>1)
										{
											for ($k = 0; $k < $td2->length; $k++)
											{
												$row[] = $td2->item($k)->getAttribute('src');
												$row[] = str_replace('"','',str_replace("'",'',$td2->item($k)->getAttribute('title')));
											}
										}
									}
								}
								$this->agendaBruta[] = $row;
							}
						}

					} else {
						$this->errorCode = '602';
						$this->errorMessage = 'Erro na interpretação dos dados HTML. Tabela não encontrada.';
						jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
					}

				} else {
					$this->errorCode = '602';
					$this->errorMessage = 'Erro na interpretação dos dados HTML. DIV principal não encontrado.';
					jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
				}
				break;
		}
	}

	/**
	 * Traduz array processo para outro array no formato do sistema Webcfc
	 */
	function traduzAgenda()
	{
		switch ($this->estado)
		{
			case 'rj':
				foreach ($this->agendaBruta as $row)
				{
					$row2['aluno_cpf'] = $this->cpf;
					$row2['curso'] = $row[3];
					$row2['data'] = $this->browser->format($row[1],'date').' '.substr($this->browser->format($row[2],'string'),0,5).':00';
					$row2['erro_biometria'] = 0;
					if (
							( strpos($row[7],'ok.jpg')!==false ) &&
							( strpos($row[9],'ok.jpg')!==false )
						)
					{
						$row2['presenca'] = $row2['data'];
						$row2['hora_entrada'] = substr($row[2],0,5);
						$row2['hora_saida'] = substr($row[2],8,5);
					} 
					else
					{
						if (
								( strpos($row[7],'ok.jpg')!==false ) ||
								( strpos($row[8],'ok.jpg')!==false ) ||
								( strpos($row[9],'ok.jpg')!==false )
							)
						{
							$row2['erro_biometria'] = 1;
							
							$num_cpf = gToNumbers($this->cpf);
							$sql = "SELECT p.id 
									FROM geral_pessoas p
									INNER JOIN geral_pessoas_fisicas f ON f.id_geral_pessoas = p.id
									WHERE CAST(AES_DECRYPT(UNHEX(f.cpf),'$AESKEY') AS CHAR(150)) = '$num_cpf'";
							$rsa = dbQuery($sql);
							if(count($rsa) > 0)
							{
								// identificando a aula
								$sql = "SELECT id, id_geral_pessoas_servicos_creditos 
										FROM aulas_teoricas
										WHERE id_geral_pessoas = " . intval($rsa[0]['id']) . " AND data = '{$row2['data']}' AND erro_biometria = 0";
								$rsat = dbQuery($sql);

								$aux_tabela = '';
								$aux_id_aula = 0;
								if(count($rsat) > 0)
								{
									$aux_tabela = 'aulas_teoricas';
									$aux_id_aula = intval($rsat[0]['id']);
									$id_geral_pessoas_servicos_creditos = intval($rsat[0]['id_geral_pessoas_servicos_creditos']);
								}
								else
								{
									$sql = "SELECT id, id_geral_pessoas_servicos_creditos
											FROM aulas_praticas
											WHERE id_geral_pessoas = " . intval($rsa[0]['id']) . " AND data = '{$row2['data']}' AND erro_biometria = 0";
									$rsap = dbQuery($sql);
									
									if(count($rsap) > 0)
									{
										$aux_tabela = 'aulas_praticas';
										$aux_id_aula = intval($rsap[0]['id']);
										$id_geral_pessoas_servicos_creditos = intval($rsap[0]['id_geral_pessoas_servicos_creditos']);
									}
								}
								
								if(intval($id_geral_pessoas_servicos_creditos) > 0)
								{
									$sql = "SELECT quantidade_utilizada
											FROM geral_pessoas_servicos_creditos
											WHERE id = $id_geral_pessoas_servicos_creditos";
									$rsq = dbQuery($sql); 
									
									if(intval($rsq[0]['quantidade_utilizada']) > 0)
									{	
										// Devolve o crédito
										$sql = "UPDATE geral_pessoas_servicos_creditos
												SET quantidade_utilizada = quantidade_utilizada - 1
												WHERE id = $id_geral_pessoas_servicos_creditos";
										dbQuery($sql);

										// Marca a aula como cancelada
										if($aux_tabela <> '' && $aux_id_aula > 0)
										{
											$sql = "UPDATE $aux_tabela SET cancelada = 1, id_geral_pessoas_cancelou = 2 WHERE id = $aux_id_aula";
											dbQuery($sql);
										}

										jLog("Falha na biometria: devolvendo o credito (id_geral_pessoas_servicos_creditos = $id_geral_pessoas_servicos_creditos)",'','infor');
									}
								}
							}
							
						}
						$row2['presenca'] = '0000-00-00 00:00:00';
						$row2['hora_entrada'] = '';
						$row2['hora_saida'] = '';
					}
					// às vezes a placa vem assim: LMP3C88 (Km: 999999 - 999999)
					$row2['placa'] = $row[5];
					$row2['instrutor'] = $row[6];
					$this->agenda[] = $row2;
				}
				break;

				case 'ba':
				case 'ce':
				foreach ($this->agendaBruta as $row)
				{
					$row2['aluno_cpf'] = $this->cpf;
					$row2['curso'] = str_replace('"','', str_replace("'",'',$row[3]));
					// às vezes a placa vem assim: LMP3C88 (Km: 999999 - 999999)
					$row2['placa'] = $row[5];
					$row2['instrutor'] = $row[6];

					$aulaData = $this->browser->format($row[1],'date').' '.substr($this->browser->format($row[2],'string'),0,5).':00';
					$aulaPresente = false;

					// $row2['data'] = $this->browser->format($row[1],'date').' '.substr($this->browser->format($row[2],'string'),0,5).':00';

					if (
						( strpos($row[ 7],'ok.jpg')!==false ) &&
						( strpos($row[ 9],'ok.jpg')!==false || $row[8]=='') &&
						( strpos($row[11],'ok.jpg')!==false )
						)
					{
						$aulaPresente = true;
						// if ($this->browser->format($row[8],'date') == 'Presença não registrada')
						// $row2['presenca'] = substr($this->browser->format($row[8],'date'),0,16).':00';
						// $row2['hora_entrada'] = substr($this->browser->format($row[8],'date'),11,5);
						// if ($row[4]==2)
						// 	$row2['hora_saida'] = date('H:i', strtotime('+49 minute',strtotime($row['data'])));
						// else
						// 	$row2['hora_saida'] = substr($this->browser->format($row[10],'date'),11,5);
					} else {
						// $row2['presenca'] = '0000-00-00 00:00:00';
						// $row2['hora_entrada'] = '';
						// $row2['hora_saida'] = '';
					}


					$ttlAulas = intval($row[4]);


					// O campo $row[4] retorna a quantidade de aulas que estão representadas por uma linha, portanto
					// Deve ser inserida a quantidade de linhas equivalente, quebrando hora de início e fim e usando
					// somente a presença da entrada e saída
					for ($a=0; $a<$ttlAulas; $a++)
					{
						$aulaDataFinal = date('Y-m-d H:i', strtotime('+'.calculaDuracao($aulaData).' minute', strtotime($aulaData))).':00';
						$row2['data'] = $aulaData;
						if ($aulaPresente)
						{
							if ($a==0) // primeira aula, então a presença é o horário real registrado pelo aluno
							{
								// Caso não tenha sido feito o registro pela biometria, mas tenha sido aceito (por telemetria)
								if ($this->browser->format($row[8],'date') == 'Presença não registrada')
									$row2['presenca'] = $aulaData;
								else
									$row2['presenca'] = substr($this->browser->format($row[8],'date'),0,16).':00';
							}
							else
								$row2['presenca'] = $aulaData;
							$row2['hora_entrada'] = substr($row2['presenca'], 11,5);
							// Última aula, a hora de saída será a registrada pela biometria
							if ($a == $ttlAulas-1)
								// Caso não tenha sido feito o registro pela biometria, mas tenha sido aceito (por telemetria)
								if ($this->browser->format($row[8],'date') == 'Presença não registrada')
									$row2['hora_saida'] = substr($aulaDataFinal, 11,5);
								else
								{
									$pos = 12;
									if ($ttlAulas<=3)
										$pos = 10;
									$row2['hora_saida'] = substr($this->browser->format($row[$pos],'date'),11,5);

								}
							else
								$row2['hora_saida'] = substr($aulaDataFinal, 11,5);
						} else
						{
							// Caso tenha QUALQUER um dos indicadores de falta, será falta para todas as aulas
							$row2['presenca'] = '0000-00-00 00:00:00';
							$row2['hora_entrada'] = '';
							$row2['hora_saida'] = '';
						}
						$this->agenda[] = $row2;
						$aulaData = $aulaDataFinal;
					}
				}
				break;
		}
		//gD($this->agenda);exit;
	}

	/**
	 * Busca dados da agenda de um CPF/Aluno (aulas passadas com presença e futuras)
	 */
	function buscaAgenda($json = "")
	{
		if ($json<>'')
		{
			$jarr = cssDecode($json);
			if ($jarr['cpf']<>'')
				$this->cpf = $jarr['cpf'];
			if ($jarr['renach']<>'')
				$this->renach = $jarr['renach'];
			$tipo = '0';
			if (isset($jarr['tipo']))
				$tipo = $jarr['tipo'];
		}
		$sai = false;
		switch ($this->estado)
		{
			case 'rj':
				if (!$this->buscouAgenda)
				{
					if ($this->login())
					{
						$this->browser->get($this->urls[$this->estado]['agenda']);
						$this->browser->getHtmlObject(true);
						$this->browser->post['CFCMaster$cphBody$TxtRJ'] = '';
						$this->browser->post['CFCMaster$cphBody$uf'] = 'RJ';
						$this->browser->post['CFCMaster$cphBody$btnConsultar'] = 'Consultar';
						$this->browser->post['CFCMaster$cphBody$rblTipoAgendamento'] = $tipo;
						$this->browser->post['CFCMaster$cphBody$txtCPF'] = gCpf($this->cpf);
						$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
						$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
						$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
						$this->browser->post['__EVENTARGUMENT'] = '';
						$this->browser->post['__EVENTTARGET'] = '';
						jLog('AGE Buscando agenda do CPF: '.$this->cpf, $this->browser->session, 'aviso');
						$this->browser->post($this->urls[$this->estado]['agenda']);
						// Preparando a próxima consulta
						$this->browser->getHtmlObject(true);
						$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
						$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
						$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
						$this->browser->post['__EVENTARGUMENT'] = '';
						$this->browser->post['__EVENTTARGET'] = '';
						$sai = true;
					}
				} else {
					$this->browser->post['CFCMaster$cphBody$TxtRJ'] = '';
					$this->browser->post['CFCMaster$cphBody$uf'] = 'RJ';
					$this->browser->post['CFCMaster$cphBody$btnConsultar'] = 'Consultar';
					$this->browser->post['CFCMaster$cphBody$rblTipoAgendamento'] = $tipo;
					$this->browser->post['CFCMaster$cphBody$txtCPF'] = gCpf($this->cpf);
					jLog('AGE Buscando agenda do CPF: '.$this->cpf, $this->browser->session, 'aviso');
					$this->browser->post($this->urls[$this->estado]['agenda']);
					$this->browser->getHtmlObject(true);
					$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
					$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
					$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
					$this->browser->post['__EVENTARGUMENT'] = '';
					$this->browser->post['__EVENTTARGET'] = '';
					$sai = true;
				}
				break;

				case 'ba':
				case 'ce':
				if (!$this->buscouAgenda)
				{
					if ($this->login())
					{
						$this->browser->get($this->urls[$this->estado]['agenda']);
						$this->browser->getHtmlObject(true);
						$this->browser->post['CFCMaster$cphBody$btnConsultar'] = 'Consultar';
						$this->browser->post['CFCMaster$cphBody$rblTipoAgendamento'] = $tipo;
						$this->browser->post['CFCMaster$cphBody$txtCPF'] = $this->cpf;
						$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
						$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
						$this->browser->post['__EVENTARGUMENT'] = '';
						$this->browser->post['__EVENTTARGET'] = '';
						jLog('AGE Buscando agenda do CPF: '.$this->cpf, $this->browser->session, 'aviso');
						$this->browser->post($this->urls[$this->estado]['agenda']);
						// Preparando a próxima consulta
						$this->browser->getHtmlObject(true);
						$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
						$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
						$this->browser->post['__EVENTARGUMENT'] = '';
						$this->browser->post['__EVENTTARGET'] = '';
						$sai = true;
					}
				} else {
					$this->browser->post['CFCMaster$cphBody$btnConsultar'] = 'Consultar';
					$this->browser->post['CFCMaster$cphBody$rblTipoAgendamento'] = $tipo;
					$this->browser->post['CFCMaster$cphBody$txtCPF'] = $this->cpf;
					jLog('AGE Buscando agenda do CPF: '.$this->cpf, $this->browser->session, 'aviso');
					$this->browser->post($this->urls[$this->estado]['agenda']);
					$this->browser->getHtmlObject(true);
					$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
					$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
					$this->browser->post['__EVENTARGUMENT'] = '';
					$this->browser->post['__EVENTTARGET'] = '';
					$sai = true;
				}
				break;
		}

		if ($sai)
		{
			// Processa dados e retorna array
			$this->processaAgenda();
			// Processa array e transforma no formato do Webcfc
			$this->traduzAgenda();
		}

		$this->buscouAgenda = true;
		return($sai);
	}


	/**
	 * Processa dados dos instrutores obtidos e retorna array com valores na propriedade da Classe
	 */
	function processaInstrutoresId()
	{
		jLog('INS Processando instrutores', $this->browser->session, 'aviso');

		switch ($this->estado)
		{
			case 'rj':
			case 'ba':
			case 'ce':


				if ($this->processaSelect('CFCMaster_cphBody_ddlInstrutor'))
				{
					//gD($this->select);exit;
					foreach ($this->select as $key=>$value)
					{
						$achou = false;
						foreach ($this->instrutores as $instrutor)
						{
							if ($instrutor['id'] == $value['id'])
								$achou = true;
						}
						if (!$achou)
						{
							$this->instrutores[] = array(
								'id'		=> $value['id'],
								'nome'		=> str_replace('"','',str_replace("'",'',$value['html']))
							);
						}
					}
				} else
				{
					$this->errorCode = '602';
					$this->errorMessage = 'Erro na interpretação dos dados HTML. SELECT não encontrado.';
					jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
				}


				// Aproveita e tenta buscar veículos
				if ($this->processaSelect('CFCMaster_cphBody_ddlVeiculo'))
				{
					//gD($this->select);exit;
					foreach ($this->select as $key=>$value)
					{
						if ($key<>'0')
						{
							$achou = false;
							foreach ($this->veiculos as $veiculo)
							{
								if ($veiculo['id'] == $value['id'])
									$achou = true;
							}
							if (!$achou)
							{
								$this->veiculos[] = array(
									'id'		=> $value['id'],
									'placa'		=> $value['html']
								);
							}
						}
					}
				}

				// $select = $this->browser->htmlObject->getElementById('CFCMaster_cphBody_ddlInstrutor');
				// if (is_object($select))
				// {
				// 	$option = $select->getElementsByTagName('option');
				// 	if (is_object($option))
				// 	{
				// 		for ($i = 0; $i < $option->length; $i++) {
				// 			$id = $option->item($i)->getAttribute('value');
				// 			$nome = $this->browser->format($option->item($i)->nodeValue);
				// 			if ($id>0)
				// 			{
				// 				$achou = false;
				// 				foreach ($this->instrutores as $instrutor)
				// 				{
				// 					if ($instrutor['id'] == $id)
				// 						$achou = true;
				// 				}
				// 				if (!$achou)
				// 				{
				// 					$this->instrutores[] = array(
				// 						'id'		=> $id,
				// 						'nome'		=> str_replace('"','',str_replace("'",'',$nome))
				// 					);
				// 				}
				// 			}
				// 		}
				//
				// 	} else {
				// 		$this->errorCode = '602';
				// 		$this->errorMessage = 'Erro na interpretação dos dados HTML. OPTION não encontrada.';
				// 		jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
				// 	}
				//
				// } else {
				// 	$this->errorCode = '602';
				// 	$this->errorMessage = 'Erro na interpretação dos dados HTML. SELECT não encontrado.';
				// 	jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
				// }

				break;
		}
	}

	/**
	 * Processa dados dos instrutores obtidos e retorna array com valores na propriedade da Classe
	 */
	function processaInstrutoresCpf()
	{
		jLog('INS Processando instrutores', $this->browser->session, 'aviso');

		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':
				$select = $this->browser->htmlObject->getElementById('CFCMaster_cphBody_ddlInstrutor');
				if (is_object($select))
				{
					$option = $select->getElementsByTagName('option');
					if (is_object($option))
					{
						for ($i = 0; $i < $option->length; $i++) {
							$cpf = $option->item($i)->getAttribute('value');
							$nome = $this->browser->format($option->item($i)->nodeValue);
							foreach ($this->instrutores as $key=>$value)
							{
								if ($value['nome'] == $nome)
								{
									$this->instrutores[$key]['cpf'] = $cpf;
								}
							}
						}

					} else {
						$this->errorCode = '602';
						$this->errorMessage = 'Erro na interpretação dos dados HTML. OPTION não encontrada.';
						jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
					}

				} else {
					$this->errorCode = '602';
					$this->errorMessage = 'Erro na interpretação dos dados HTML. SELECT não encontrado.';
					jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
				}
				break;
		}
	}

	/**
	 * Busca dados dos instrutores
	 */
	function buscaInstrutores()
	{
		$sai = false;
		switch ($this->estado)
		{
			case 'ce':
			case 'rj':
			case 'ba':
				if ($this->login())
				{
					jLog('INS Buscando ID dos instrutores 1/2', $this->browser->session, 'aviso');
					$this->browser->referer = $this->urls[$this->estado]['login_0'];
					$this->browser->get($this->urls[$this->estado]['instrutores_id']);
					$this->browser->getHtmlObject(true);

					$this->browser->post['CFCMaster$cphBody$rblTipoCurso'] = 'P';
					$this->browser->post['CFCMaster$cphBody$ddlInstrutor'] = '0';
					$this->browser->post['CFCMaster$cphBody$txtInicio'] = date('d/m/Y');
					$this->browser->post['CFCMaster$cphBody$txtTermino'] = date('d/m/Y');

					$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
					$this->browser->post['__EVENTARGUMENT'] = '';
					$this->browser->post['__EVENTTARGET'] = $this->browser->getField('id-input','__EVENTTARGET');
					$this->browser->post['__LASTFOCUS'] = '';
					$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
					$this->processaInstrutoresId();

					jLog('INS Buscando ID dos instrutores 2/2', $this->browser->session, 'aviso');
					$this->browser->post($this->urls[$this->estado]['instrutores_id']);
					$this->browser->getHtmlObject(true);
					$this->processaInstrutoresId();

					// jLog('INS Buscando CPF dos instrutores', $this->browser->session, 'aviso');
					// $this->browser->get($this->urls[$this->estado]['instrutores_cpf']);
					// $this->browser->getHtmlObject(true);
					// $this->processaInstrutoresCpf();

					$sai = true;
				}
				break;

				// if ($this->login())
				// {
				// 	jLog('INS Buscando ID dos instrutores 1/2', $this->browser->session, 'aviso');
				// 	$this->browser->get($this->urls[$this->estado]['instrutores_id']);
				// 	$this->browser->getHtmlObject(true);
				//
				// 	$this->browser->post['CFCMaster$cphBody$ScriptManager1'] = $this->browser->getField('id-input','CFCMaster$cphBody$ScriptManager1');
				// 	$this->browser->post['CFCMaster$cphBody$rblTipoBusca'] = 'I';
				// 	$this->browser->post['CFCMaster$cphBody$txtData'] = date('d/m/Y');
				//
				// 	$this->browser->post['__ASYNCPOST'] = 'true';
				// 	$this->browser->post['__EVENTARGUMENT'] = '';
				// 	$this->browser->post['__EVENTTARGET'] = $this->browser->getField('id-input','__EVENTTARGET');
				// 	$this->browser->post['__LASTFOCUS'] = '';
				// 	$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
				//
				// 	jLog('INS Buscando ID dos instrutores 2/2', $this->browser->session, 'aviso');
				// 	$this->browser->post($this->urls[$this->estado]['instrutores_id']);
				// 	$this->browser->getHtmlObject(true);
				// 	$this->processaInstrutoresId();
				// 	// Hoje (14/09/2016) não há como buscar o CPF do instrutor no sistema da Interprint
				// 	$sai = true;
				// }
				break;
		}
		return($sai);

	}

	/**
	 * Processa dados dos serviços obtidos e retorna array com valores na propriedade da Classe
	 */
	function processaServicos()
	{
		jLog('SRV Processando serviços', $this->browser->session, 'aviso');

		switch ($this->estado)
		{
			case 'rj':
			case 'ba':
			case 'ce':
				$servicos = $this->browser->getField('id-inc','CFCMaster_cphBody_0_header', 'string', 18);
				foreach ($servicos as $servico)
				{
					$s = explode(' - ', $servico);
					$this->servicos[] = array(
						'id'		=> $s[0],
						'descricao'	=> str_replace('"','',str_replace("'",'',$s[1]))
					);
				}
				break;
		}
	}


	/**
	 * Busca dados dos serviços
	 */
	function buscaServicos()
	{
		$sai = false;
		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':
				if ($this->login())
				{
					jLog('SRV Buscando serviços', $this->browser->session, 'aviso');
					$this->browser->get($this->urls[$this->estado]['servicos']);
					$this->browser->getHtmlObject(true);
					$this->processaServicos();
					$sai = true;
				}
				break;

		}
		return($sai);

	}

	/**
	 * Processa dados dos disciplinas obtidas e retorna array com valores na propriedade da Classe
	 */
	function processaDisciplinas()
	{
		jLog('DSC Processando disciplinas', $this->browser->session, 'aviso');

		switch ($this->estado)
		{
			case 'rj':
			case 'ba':
			case 'ce':
				$select = $this->browser->htmlObject->getElementById('CFCMaster_cphBody_Acord');
				if (is_object($select))
				{
					$label = $select->getElementsByTagName('label');
					if (is_object($label))
					{
						for ($i = 0; $i < $label->length; $i++) {
							$for        = explode('_',$label->item($i)->getAttribute('for'));
							$descricao  = $this->browser->format($label->item($i)->nodeValue);
							$id_servico = $for[0];
							$id         = $for[1];
							if ($id > 0 && $descricao <> 'Selecione o Dia' && $descricao<>'Manhã' && $descricao <> 'Tarde' && $descricao <> 'Noite')
							{
								$this->disciplinas[] = array(
									'id'		     => $id,
									'id_servico'     => $id_servico,
									'descricao'		 => str_replace('"','',str_replace("'",'',$descricao))
								);
							}
						}

					} else {
						$this->errorCode = '602';
						$this->errorMessage = 'Erro na interpretação dos dados HTML. OPTION não encontrada.';
						jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
					}
				}
				break;
		}
	}

	function processaInputs()
	{
		// Busca campos para submeter no próximo form
		$input = $this->browser->htmlObject->getElementsByTagName('input');
		$this->inputs = '';
		if (is_object($input))
		{
			$sai = true;
			for ($i = 0; $i < $input->length; $i++) {
				$id = $input->item($i)->getAttribute('id');
				$name = $input->item($i)->getAttribute('name');
				$value = $input->item($i)->getAttribute('value');
				$this->inputsById[$id] = array(
					'id'		=> $id,
					'name'		=> $name,
					'value'		=> $value
				);
				$this->inputsByName[$name] = array(
					'id'		=> $id,
					'name'		=> $name,
					'value'		=> $value
				);
			}
		} else
		{
			$sai = false;
		}
		return($sai);
	}

	function processaSelect($id)
	{
		// Busca campos para submeter no próximo form
		$select = $this->browser->htmlObject->getElementById($id);
		$this->select = '';
		if (is_object($select))
		{
			$sai = true;
			$option = $select->getElementsByTagName('option');
			for ($i = 0; $i < $option->length; $i++) {
				$id = $this->browser->format($option->item($i)->getAttribute('value'));
				$html = $this->browser->format($option->item($i)->nodeValue);
				if ($id <> '')
				{
					$this->select[] = array(
						'id'	=> $id,
						'html'	=> $html
					);
				}
			}
		} else {
			$sai = false;
		}
		return($sai);
	}

	/**
	 * Busca dados dos disciplinas
	 */
	function buscaDisciplinas()
	{
		$sai = false;
		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':
				if ($this->login())
				{
					jLog('DSC Buscando disciplinas', $this->browser->session, 'aviso');
					$this->browser->get($this->urls[$this->estado]['servicos']);
					$this->browser->getHtmlObject(true);
					$this->processaDisciplinas();
					$sai = true;
				}
				break;

		}
		return($sai);
	}

	function processaCadastroCandidato()
	{
		jLog('CAD Processando cadastro de candidatos', $this->browser->session, 'aviso');
		switch ($this->estado)
		{
			case 'rj':
			case 'ba':
			case 'ce':
				// Verifica se tem serviço disponível pra adicionar (na caixa de seleção esquerda)
				$this->cadastrosDisponiveis = array();
				$this->cadastrosEfetuados = array();

				$this->cadastrosMensagem = $this->browser->htmlObject->getElementById('CFCMaster_cphBody_lblMensagem')->nodeValue;
				$this->processaInputs();

				// Busca serviços disponíveis e já adicionados
				$select = $this->browser->htmlObject->getElementById('Select1');
				if (is_object($select))
				{
					$option = $select->getElementsByTagName('option');
					if (is_object($option))
					{
						for ($i = 0; $i < $option->length; $i++) {
							$id = $option->item($i)->getAttribute('value');
							$descricao = $this->browser->format($option->item($i)->nodeValue);
							if ($id>0)
							{
								$this->cadastrosDisponiveis[] = array(
									'id'		=> $id,
									'descricao'	=> $descricao
								);
							}
						}
					}
				} else {
					$this->errorCode = '602';
					$this->errorMessage = 'Erro na interpretação dos dados HTML. SELECT não encontrado.';
					jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
				}

				$select = $this->browser->htmlObject->getElementById('Select2');
				if (is_object($select))
				{
					$option = $select->getElementsByTagName('option');
					if (is_object($option))
					{
						for ($i = 0; $i < $option->length; $i++) {
							$id = $option->item($i)->getAttribute('value');
							$descricao = $this->browser->format($option->item($i)->nodeValue);
							if ($id>0)
							{
								$this->cadastrosEfetuados[] = array(
									'id'		=> $id,
									'descricao'	=> $descricao
								);
							}
						}
					}
				} else {
					$this->errorCode = '602';
					$this->errorMessage = 'Erro na interpretação dos dados HTML. SELECT não encontrado.';
					jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
				}

				break;
		}
	}

	function buscaCadastroCandidato($json)
	{
		global $gPath;
		$jarr = cssDecode($json);
		$id = intval($jarr['id']);
		$cpf = str_replace(".","",str_replace("-","",$jarr['cpf']));
		$renach = gToNumbers($jarr['renach']);
		$sai = false;
		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':
				if ($this->estado=='rj')
					$cpf =  gCpf($cpf);
				jLog('CAD Verificando cadastros do candidato...', $this->browser->session, 'aviso');
				if ($this->login())
				{
					$this->browser->get($this->urls[$this->estado]['cadastro_0']);
					$this->browser->getHtmlObject(true);
					if ($this->estado=='rj')
					{
						$this->browser->post['CFCMaster$cphBody$HiddenRenach'] = '';
						$this->browser->post['CFCMaster$cphBody$TextBox1'] = '';
						$this->browser->post['CFCMaster$cphBody$txtCPF'] = $cpf;
						$this->browser->post['CFCMaster$cphBody$txtObs'] = '';
						$this->browser->post['CFCMaster$cphBody$txtRENACH'] = $renach;

						$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
						$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
						$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
						$this->browser->post['lblCPF'] = '';
						$this->browser->post['lblCategoria'] = '';
						$this->browser->post['lblRENACH'] = '';
						$this->browser->post['txtAbertura'] = '';
						$this->browser->post['txtNome'] = '';
						$this->browser->post['txtValidade'] = '';

					} else
					{

						$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
						$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
						$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
						$this->browser->post['lblCPF'] = '';
						$this->browser->post['lblCategoria'] = '';
						$this->browser->post['lblMensagem'] = '';
						$this->browser->post['lblServico'] = '';
						$this->browser->post['txtCPF'] = $cpf;
						$this->browser->post['txtDtNasc'] = '';
						$this->browser->post['txtNome'] = '';
						$this->browser->post['txtRenach'] = '';
					}

					jLog("CAD Verificando cadastros do candidato > CPF: $cpf RENACH: $renach", $this->browser->session, 'aviso');
					$this->browser->post($this->urls[$this->estado]['cadastro_1']);
					$this->browser->getHtmlObject(true);
					$sai = true;
				}
				break;
		}
		if ($sai)
		{
			$this->processaCadastroCandidato();

			// Busca imagem
			if ($id>0)
			{
				if ($this->estado<>'rj') // Rio de Janeiro não tem foto
				{
					$file = file_get_contents($this->urls[$this->estado]['foto'].$cpf);
					gLog(">>> Buscando foto: ".$this->urls[$this->estado]['foto'].$cpf);
					if (strlen($file)>0)
						file_put_contents($gPath."files/geral_pessoas/".$id.".jpg", $file);
				}
			}
		}
		return($sai);

	}

	function cadastraCandidato($json)
	{
		$jarr = cssDecode($json);
		$cpf = str_replace(".","",str_replace("-","",$jarr['cpf']));
		$renach = gToNumbers($jarr['renach']);
		$sai = false;
		if ($this->buscaCadastroCandidato($json))
		{
			switch ($this->estado)
			{
				case 'ba':
				case 'ce':
				case 'rj':
					if (count($this->cadastrosDisponiveis)>0)
					{
						$cpf =  gCpf($cpf);
						$this->browser->post['CFCMaster$cphBody$HiddenRenach'] = '';
						$this->browser->post['CFCMaster$cphBody$TextBox1'] = '';
						$this->browser->post['CFCMaster$cphBody$txtCPF'] = $cpf;
						$this->browser->post['CFCMaster$cphBody$txtObs'] = ' ';
						$this->browser->post['CFCMaster$cphBody$txtRENACH'] = $renach;
						$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
						$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
						$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
						$this->browser->post['lblCPF'] = $this->inputsByName['lblCPF']['value'];
						$this->browser->post['lblCategoria'] = $this->inputsByName['lblCategoria']['value'];
						$this->browser->post['lblRENACH'] = $this->inputsByName['lblRENACH']['value'];
						$this->browser->post['txtAbertura'] = $this->inputsByName['txtAbertura']['value'];
						$this->browser->post['txtNome'] = $this->inputsByName['txtNome']['value'];
						$this->browser->post['txtValidade'] = $this->inputsByName['txtValidade']['value'];
						$this->browser->post($this->urls[$this->estado]['cadastro_2']);
						////echo $this->browser->html;
						$this->browser->getHtmlObject(true);
						$sai = true;

					} else
					{
						$this->errorCode = '603';
						$this->errorMessage = 'Nenhum novo serviço disponível para cadastro do candidato';
						jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
					}
					break;
			}
		}
		return($sai);
	}

	function converteHora($data, $dataInicial)
	{
		// 0000-00-00 00:00:00
		// rptHorarios_ctl10_div0
		// rptHorarios$ctl20$CheckBox0
		$hora = substr($data,11,5);
		$code = $this->horarios[$hora];
		if ($code<>'')
		{
			$datetime1 = new DateTime($data);
			$datetime2 = new DateTime($dataInicial);
			$interval = $datetime1->diff($datetime2);
			$code.=$interval->format('%d');
		}
		return ($code);
	}

	function buscaAgendaAluno($json, $datas)
	{
		$sai = false;

		$jarr = cssDecode($json);
		if (count($datas)>1)
			sort($datas);
		$cpf = str_replace(".","",str_replace("-","",$jarr['cpf']));
		$data = $datas[0];
		$renach = gToNumbers($jarr['renach']);
		$id_instrutor = $jarr['id_instrutor'];
		$id_curso = $jarr['id_curso'];
		$id_veiculo = $jarr['id_veiculo'];
		$categoria = $jarr['categoria'];
		$id_servico = $jarr['id_servico'];
		$minutos = $jarr['minutos'] == '' ? '00' : $jarr['minutos'];
		$p_t = ($id_veiculo>0) ? 'P' : 'T';
		$uf = strtoupper($this->estado);

		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':



				jLog('AGE Agendando aluno...', $this->browser->session, 'aviso');
				if ($this->login())
				{
					$dataBR = date('d/m/Y', strtotime($data));
					$hora = substr($data,11,5);
					$periodo = ($hora<'12:00') ? 'M' : ($hora>'18:00' ? 'N' : 'T');

					// Fase 1
					$this->browser->get($this->urls[$this->estado]['agenda_grade']);
					$this->browser->getHtmlObject(true);

					$this->browser->post['CFCMaster$cphBody$Acord_AccordionExtender_ClientState'] = '0';
					$this->browser->post['CFCMaster$cphBody$btnAgendamento'] = 'Button';
					$this->browser->post['CFCMaster$cphBody$hdiData'] = $dataBR;
					$this->browser->post['CFCMaster$cphBody$hdiPeriodo'] = '';
					$this->browser->post['CFCMaster$cphBody$hdiServi'] = $id_servico.'_'.$p_t.$id_curso;
					$this->browser->post['CFCMaster_cphBody_ToolkitScriptManager1_HiddenField'] = $this->browser->getField('id-input','CFCMaster_cphBody_ToolkitScriptManager1_HiddenField');

					$this->browser->post['DataServico'] = $dataBR;
					// $this->browser->post['lblRENACH'] = '';
					// $this->browser->post['txtAbertura'] = '';
					// $this->browser->post['txtNome'] = '';
					// $this->browser->post['txtValidade'] = '';

					$this->browser->post['__EVENTARGUMENT'] = '';
					$this->browser->post['__EVENTTARGET'] = '';
					$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
					$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
					$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');

					$this->browser->post['chkServico'] = 'on';


					jLog("AGE Agendando aluno > CPF: $cpf RENACH: $renach", $this->browser->session, 'aviso');
					$this->browser->post($this->urls[$this->estado]['agenda_grade']);
					$this->browser->getHtmlObject(true);

					// Fase 2 - Obtém janela de horários e define o Renach e CPF
					if ($p_t=='P')
						$url = $this->urls[$this->estado]['agenda_incluir_1']."?data=".$dataBR."&idInstrutor=".$id_instrutor."&idCurso=".$id_curso."&categoria=".$categoria;
					else
						$url = $this->urls[$this->estado]['agenda_incluir_1']."?data=".$dataBR."&idInstrutor=".$id_instrutor."&idCurso=".$id_curso;
					$this->browser->get($url);
					$this->browser->getHtmlObject(true);

					$this->browser->post['TxtRJ'] = $renach;
					$this->browser->post['btnBuscar'] = 'Buscar';
					if ($p_t=='P')
					{
						$this->browser->post['ddlMinutos'] = $minutos;
						$this->browser->post['ddlVeiculo'] = $id_veiculo;
					}
					$this->browser->post['txtCPF'] = $cpf;
					$this->browser->post['uf'] = $uf;

					$this->browser->post['__EVENTARGUMENT'] = '';
					$this->browser->post['__EVENTTARGET'] = '';
					$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
					$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
					$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
					$this->browser->post($url);
					$this->browser->getHtmlObject(true);


					// Fase 3 - Marcação em si

					// Pega o "value" do serviço (código interno usado pela Interprint que não é o Id)
					$id_select_servico = 0;
					$select = $this->browser->htmlObject->getElementById('ddlServicoPratico');
					if (is_object($select))
					{
						$option = $select->getElementsByTagName('option');
						if (is_object($option))
						{
							$id_select_servico = $option->item(0)->getAttribute('value');
						}
					}

					// Pega horários e respectivos Ids a partir da grade de horários
					$table = $this->browser->htmlObject->getElementsByTagName('table');
					$tr = $table->item(4)->getElementsByTagName('tr');
					for ($i = 1; $i < $tr->length; $i++) {
						$td = $tr->item($i)->getElementsByTagName('td');
						$tdHora = $this->browser->format($td->item(0)->nodeValue);
						$tdDiv = $td->item(1)->getElementsByTagName('div');
						$tdCode = $tdDiv->item(0)->getAttribute('id');
						$tdCode = str_replace('_','$',$tdCode);
						$tdCode = str_replace('div0','CheckBox',$tdCode);
						$this->horarios[$tdHora] = $tdCode;
					}

					$fez = false;
					foreach ($datas as $data)
					{
						$code = $this->converteHora($data, $datas[0]);
						if ($code<>'')
						{
							$this->browser->post[$code] = 'on';
							$fez = true;
						}
					}

					if ($fez)
					{
						//$this->browser->post['rptHorarios$ctl20$CheckBox0'] = 'on';
						$this->browser->post['btnAgendarPratico'] = 'Agendar Candidato';
						$this->browser->post['ddlServicoPratico'] = $id_select_servico;
						$this->browser->post['uf'] = $uf;

						$this->browser->post['__EVENTARGUMENT'] = '';
						$this->browser->post['__EVENTTARGET'] = '';
						$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
						$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
						$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');

						$this->browser->post($url);
						$this->browser->getHtmlObject(true);

						if (strpos($this->browser->html, 'Agendamento realizado com sucesso')!==false)
						{
							$sai = true;
						} else {
							$sai = false;
							$lbl = $this->browser->getField('id', 'lblResultado');
							$this->errorCode = '604';
							$this->errorMessage = 'Erro ao agendar aluno: '.$lbl;
							jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
						}
					} else {
						$sai = false;
						$this->errorCode = '604';
						$this->errorMessage = 'Erro ao agendar aluno: Nenhum horário compatível encontrado';
						jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');

					}
				}
				break;
		}
		return($sai);
	}




	/**
	 * Informa ao sistema da Interprint que um dia e horários específicos estão marcados para uma disciplina
	 * @param  string $json  JSON com parâmetros
	 * @return boolean        True/False (sucesso/falha)
	 */
	function agendaDisciplina($json)
	{
		$sai = false;

		$jarr = cssDecode($json);
		// $cpf = str_replace(".","",str_replace("-","",$jarr['cpf']));
		// $renach = gToNumbers($jarr['renach']);
		$id_instrutor = $jarr['id_instrutor'];
		$id_curso = $jarr['id_curso'];
		$id_sala = $jarr['id_sala'];
		$id_servico = $jarr['id_servico'];
		$dataInicio = $jarr['data'];
		$total = $jarr['total'];
		//$dataTermino = $jarr['data_termino'];
		$uf = strtoupper($this->estado);
		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':



				jLog('AGE Agendando disciplina...', $this->browser->session, 'aviso');
				if ($this->login())
				{
					$dataBR = date('d/m/Y', strtotime($dataInicio));
					$horaInicio = substr($dataInicio,11,5);
					$horaIntervalo = '';
					//$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+50 minute', strtotime($dataTermino))),11,5);
					switch ($total)
					{
						case 1:
							$horaIntervalo = '';
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaInicio).' minute', strtotime($dataInicio))),11,5);
							break;
						case 2:
							$horaIntervalo = '';
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaInicio).' minute', strtotime($dataInicio))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							break;
						case 3:
							$horaIntervalo = '';
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaInicio).' minute', strtotime($dataInicio))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							break;
						case 4:
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaInicio).' minute', strtotime($dataInicio))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							$horaIntervalo = $horaTermino;
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							break;
						case 5:
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaInicio).' minute', strtotime($dataInicio))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							$horaIntervalo = $horaTermino;
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							break;
						case 6:
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaInicio).' minute', strtotime($dataInicio))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							$horaIntervalo = $horaTermino;
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+'.calculaDuracao($horaTermino).' minute', strtotime($horaTermino))),11,5);
							break;
					}
					$periodo = ($horaInicio<'12:00') ? 'M' : ($horaInicio>=HORA_LIMITE_AULAS_50_MINUTOS ? 'N' : 'T');
					// Fase 1
					$this->browser->get($this->urls[$this->estado]['agenda_grade']);
					$this->browser->getHtmlObject(true);
					$this->browser->post['CFCMaster$cphBody$Acord_AccordionExtender_ClientState'] = '0';
					$this->browser->post['CFCMaster$cphBody$btnAgendamento'] = 'Button';
					$this->browser->post['CFCMaster$cphBody$hdiData'] = $dataBR;
					$this->browser->post['CFCMaster$cphBody$hdiPeriodo'] = $periodo;
					$this->browser->post['CFCMaster$cphBody$hdiServi'] = $id_servico.'_T'.$id_curso.',';
					$this->browser->post['CFCMaster_cphBody_ToolkitScriptManager1_HiddenField'] = $this->browser->getField('id-input','CFCMaster_cphBody_ToolkitScriptManager1_HiddenField');
					$this->browser->post['DataServico'] = $dataBR;
					$this->browser->post['__EVENTARGUMENT'] = '';
					$this->browser->post['__EVENTTARGET'] = '';
					$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
					$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
					$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
					$this->browser->post['chkServico'] = 'on';


					jLog("AGE Agendando disciplina (turma)", $this->browser->session, 'aviso');
					$this->browser->post($this->urls[$this->estado]['agenda_grade']);
					$this->browser->getHtmlObject(true);


					if (strpos($this->browser->html, 'Ocorreu um erro durante o processamento')!==false)
					{
						$sai = false;
						$this->errorCode = '605';
						$this->errorMessage = 'Erro ao agendar disciplina';
						jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
					} else {
						// Fase 2 - Obtém janela de horários e define o Renach e CPF
						$url = $this->urls[$this->estado]['agenda_incluir_2']."?tpCurso=T&data=".$dataBR."&hrInicio=".intval(str_replace(':','',$horaInicio))."&hrTermino=".intval(str_replace(':','',$horaTermino))."&idInstrutor=".$id_instrutor."&idCurso=".$id_curso."&categoria=&servico=".$id_servico;
						$url2 = $this->urls[$this->estado]['agenda_incluir_2']."?tpCurso=T&data=".urlencode($dataBR)."&hrInicio=".intval(str_replace(':','',$horaInicio))."&hrTermino=".intval(str_replace(':','',$horaTermino))."&idInstrutor=".$id_instrutor."&idCurso=".$id_curso."&categoria=&servico=".$id_servico;
						//$url = $this->urls[$this->estado]['agenda_incluir_2']."?tpCurso=T&data=".$dataBR."&hrInicio=600&hrTermino=2300&idInstrutor=".$id_instrutor."&idCurso=".$id_curso."&categoria=&servico=".$id_servico;
						$this->browser->get($url2);
						$this->browser->getHtmlObject(true);

						$this->browser->post['btnCriarTeorico'] = 'Criar Aula Teórica';

						// TODO - Mais de um horário com intervalo

						if ($this->estado == 'rj')
						{
							if ($id_sala ==  '')
							{
								$this->processaSelect('ddlSala');
								$id_sala = $this->select[0]['id'];
							}

							$this->browser->post['ddlSala'] = $id_sala;
							$this->browser->post['hdiServCurso'] = $this->browser->getField('id-input','hdiServCurso');
							$this->browser->post['txtHoraInicio'] = $horaInicio;
							$this->browser->post['txtHoraTermino'] = $horaTermino;
						} else
						{
							$this->browser->post['hdiServCurso'] = $this->browser->getField('id-input','hdiServCurso');
							$this->browser->post['txtHoraInicio'] = $horaInicio;
							$this->browser->post['txtHoraIntervalo'] = $horaIntervalo;
							$this->browser->post['txtHoraTermino'] = $horaTermino;

						}

						$this->browser->post['__EVENTARGUMENT'] = '';
						$this->browser->post['__EVENTTARGET'] = '';
						$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
						$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
						//$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');

						$this->browser->referer = $url2;
						$this->browser->useHttpBuildQuery = false;
						$this->browser->headers[] = 'Upgrade-Insecure-Requests: 1';

						$this->browser->post($url2);
						$this->browser->getHtmlObject(true);

						if (strpos($this->browser->html, 'Ocorreu um erro durante o processamento')!==false)
						{
							$sai = false;
							$this->errorCode = '605';
							$this->errorMessage = 'Erro de processamento ao agendar disciplina';
							jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
						} else {
							// Confirma se a disciplina foi agendada

							$this->browser->get($this->urls[$this->estado]['agenda_grade']);
							$this->browser->getHtmlObject(true);
							$this->browser->post['CFCMaster$cphBody$Acord_AccordionExtender_ClientState'] = '0';
							$this->browser->post['CFCMaster$cphBody$btnAgendamento'] = 'Button';
							$this->browser->post['CFCMaster$cphBody$hdiData'] = $dataBR;
							$this->browser->post['CFCMaster$cphBody$hdiPeriodo'] = $periodo;
							$this->browser->post['CFCMaster$cphBody$hdiServi'] = $id_servico.'_T'.$id_curso.',';
							$this->browser->post['CFCMaster_cphBody_ToolkitScriptManager1_HiddenField'] = $this->browser->getField('id-input','CFCMaster_cphBody_ToolkitScriptManager1_HiddenField');
							$this->browser->post['DataServico'] = $dataBR;
							$this->browser->post['__EVENTARGUMENT'] = '';
							$this->browser->post['__EVENTTARGET'] = '';
							$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
							$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
							$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
							$this->browser->post['chkServico'] = 'on';


							jLog("AGE Conferindo se disciplina foi realmente marcada", $this->browser->session, 'aviso');
							$this->browser->post($this->urls[$this->estado]['agenda_grade']);
							$this->browser->getHtmlObject(true);

							// Busca Id do elemento...
							$matches = "";
							$fez = false;
							$idGradeCurso = '';
							$idInstrutor = '';
							$horaInicioInt = intval(str_replace( ':','', $horaInicio));

							preg_match_all('/(_dados).*/i', $this->browser->html, $matches);

							foreach ($matches[0] as $match)
							{
								$campos = explode('"', $match);
								if (count($campos)>1)
								{
									//_dados["a6052632_25851"] = ["T", "600", "0", "650", "JEAN CARLO BAZANI DOS SANTOS", "", "0", "", "Renovação", "1", "T"];
									//_dados["a5813408_25851"] = ["P", "1030", "0", "1120", "JEAN CARLO BAZANI DOS SANTOS", "LEANDRO FERREIRA COSTA", "1", "LRW3466", "Primeira Habilitação","1","T"];
									$horaInicioInt = intval(str_replace( ':','', $horaInicio));
									$campoIds = $campos[1];$campoIds = explode('_', $campoIds);
									$campoTipo = $campos[3];
									$campoHora = $campos[5];
									$campoInst = $campos[11];
									$campoAlun = $campos[13];
									$campoPlac = $campos[17];
									$campoServ = $campos[19];
									$campoCurs = $campos[21];
									jLog("Checando H:$horaInicioInt I:$id_instrutor S:$servico C:$id_curso P:$placa = [$campoTipo] [".$campoHora."] [".$campoInst."] [".$campoServ."] [".$campoCurs."] [".$campoPlac."]", $this->browser->session);
									if ($campoTipo == 'T')
									{
										// Teórico
										if ($campoHora == $horaInicioInt )
										{
											$idGradeCurso = substr($campoIds[0],1);
											$idInstrutor = $campoIds[1];
											jLog("Checando novamente $idInstrutor = $id_instrutor", $this->browser->session);
											if ($idInstrutor == $id_instrutor)
											{
												$fez = true;
												jLog("Encontrado!", $this->browser->session);
											}
										}
									}
								}
							}

							if ($fez)
							{
								$sai = true;
							} else {
								$sai = false;
								$this->errorCode = '605';
								$this->errorMessage = 'Erro de processamento ao agendar disciplina';
								jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
							}
							// if (strpos($this->browser->html, 'Hora de')!==false && strpos($this->browser->html, 'incorreta.')!==false)
							// {
							// 	$sai = false;
							// 	$this->errorCode = '608';
							// 	$this->errorMessage = 'Hora de início ou término incorreta';
							// 	jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
							//
							// } else
							// {
							// 	$sai = true;
							// }
						}

					}
				}
				break;
		}
		return($sai);
	}



	function agendaAulaAluno_fase1($dataBR,$periodo,$id_servico,$p_t,$id_curso)
	{
		$this->browser->get($this->urls[$this->estado]['agenda_grade']);
		$this->browser->getHtmlObject(true);
		$this->browser->post['CFCMaster$cphBody$Acord_AccordionExtender_ClientState'] = '0';
		$this->browser->post['CFCMaster$cphBody$btnAgendamento'] = 'Button';
		$this->browser->post['CFCMaster$cphBody$hdiData'] = $dataBR;
		$this->browser->post['CFCMaster$cphBody$hdiPeriodo'] = $periodo;
		$this->browser->post['CFCMaster$cphBody$hdiServi'] = $id_servico.'_'.$p_t.$id_curso.',';
		$this->browser->post['CFCMaster_cphBody_ToolkitScriptManager1_HiddenField'] = $this->browser->getField('id-input','CFCMaster_cphBody_ToolkitScriptManager1_HiddenField');
		$this->browser->post['DataServico'] = $dataBR;
		$this->browser->post['__EVENTARGUMENT'] = '';
		$this->browser->post['__EVENTTARGET'] = '';
		$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
		$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
		$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
		$this->browser->post['chkServico'] = 'on';
		jLog("AGE Marcando data", $this->browser->session, 'aviso');
		$this->browser->post($this->urls[$this->estado]['agenda_grade']);
		$this->browser->getHtmlObject(true);
	}

	/**
	 * Informa ao sistema da Interprint que um dia e horários específicos estão marcados para uma disciplina
	 * @param  string $json  JSON com parâmetros
	 * @return boolean        True/False (sucesso/falha)
	 */
	function agendaAulaAluno($aulas, $p_t='T')
	{
		$sai = false;

		// $jarr = cssDecode($aulas);
		if ($p_t == 'P')
		{
			$id_instrutor = $aulas[0]['id_instrutor'];
			$id_curso     = $aulas[0]['id_curso'];
			$id_sala      = $aulas[0]['id_sala'];
			$id_servico   = $aulas[0]['id_servico'];
			$id_veiculo   = $aulas[0]['id_veiculo'];
			$dataInicio   = $aulas[0]['data'];
			$categoria    = $aulas[0]['categoria'];
			$cpf          = $aulas[0]['cpf'];
			$placa        = $aulas[0]['placa'];
			$renach       = gToNumbers($aulas[0]['renach']);
		} else
		{
			$id_instrutor = $aulas['id_instrutor'];
			$id_curso     = $aulas['id_curso'];
			$id_sala      = $aulas['id_sala'];
			$id_servico   = $aulas['id_servico'];
			$id_veiculo   = $aulas['id_veiculo'];
			$dataInicio   = $aulas['data'];
			$categoria    = $aulas['categoria'];
			$cpf          = $aulas['cpf'];
			$placa        = $aulas['placa'];
			$renach       = gToNumbers($aulas['renach']);
		}
		//$dataTermino = $jarr['data_termino'];

		$uf = strtoupper($this->estado);
		$txtEstado = 'Txt'.strtoupper($this->estado);
		// Converter datas em checkboxes da Interprint
		$f = 0;
		$hora = '06:00';
		$hora2 = $hora;
		if ($this->estado == 'ba')
		{
			$minInicial = '00';
			for ($g = 1; $g<21; $g++)
			{
				$cb = str_pad($g, 2, '0', STR_PAD_LEFT);
				if (!isset($checkBoxes[$hora2]))
				{
					$checkBoxes[$hora2] = array(
						'minInicial'		=> $minInicial,
						'checkbox'			=> $cb);
				}
				{
					$minutos[$minInicial][] = array(
						'hora'				=> $hora2,
						'checkbox'			=> $cb);
				}
				$hora2 = date('H:i', strtotime('+1 hour', strtotime($hora2)));
			}
		} else
		{
			while ($f<60)
			{
				$minInicial = str_pad($f, 2, '0', STR_PAD_LEFT);
				$hora2 = $hora;
				for ($g = 1; $g<21; $g++)
				{
					$cb = str_pad($g, 2, '0', STR_PAD_LEFT);
					if (!isset($checkBoxes[$hora2]))
					{
						$checkBoxes[$hora2] = array(
							'minInicial'		=> $minInicial,
							'checkbox'			=> $cb);
					}
					{
						$minutos[$minInicial][] = array(
							'hora'				=> $hora2,
							'checkbox'			=> $cb);
					}
					$hora2 = date('H:i', strtotime('+50 minute', strtotime($hora2)));
				}
				$hora = date('H:i', strtotime('+5 minute', strtotime($hora)));
				$f = $f+5;
			}

		}
//echo "checkboxes: <br>";gD($checkBoxes);echo "<hr>minutos: <br>";gD($checkBoxes);exit;

		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':



				jLog('AGE Agendando aula...', $this->browser->session, 'aviso');
				if ($this->login())
				{

					if ($p_t == "P")
					{
						// Aulas práticas

						$processouAoMenosUmaAula = false;
						jLog("=================================== Novo processamento de aula prática - ".$aulas[0]['data']);
						while ($aulas[0]['data']<>'')
						{
							if ($aulas[0]['simulador'] == true) {
								if ($this->agendaAulaSimulador($aulas) ) {
									$aulas[0]['processado'] = true;
									$processouAoMenosUmaAula = true;
								} 
// 								else {
// 									$aulas = array(); // Evitando loop infinito
// 								}
								
							} else {
								$id_instrutor = $aulas[0]['id_instrutor'];
								$id_curso     = $aulas[0]['id_curso'];
								$id_sala      = $aulas[0]['id_sala'];
								$id_servico   = $aulas[0]['id_servico'];
								$id_veiculo   = $aulas[0]['id_veiculo'];
								$dataInicio   = $aulas[0]['data'];
								$categoria    = $aulas[0]['categoria'];
								$cpf          = $aulas[0]['cpf'];
								$placa        = $aulas[0]['placa'];
								$renach       = gToNumbers($aulas[0]['renach']);
	
								$dataBR       = date('d/m/Y', strtotime($dataInicio));
								$horaInicio   = substr($dataInicio,11,5);
								$periodo      = ($horaInicio<'12:00') ? 'M' : ($horaInicio>=HORA_LIMITE_AULAS_50_MINUTOS ? 'N' : 'T');
								$horaTermino  = substr(date('Y-m-d H:i:s', strtotime('+50 minute', strtotime($dataInicio))),11,5);
	
								// Fase 1 - seleciona dia, tipo de atividade
								$this->agendaAulaAluno_fase1($dataBR,$periodo,$id_servico,$p_t,$id_curso);
	
								{
									$url = $this->urls[$this->estado]['agenda_pratico_incluir']."?data=".$dataBR."&idInstrutor=".$id_instrutor."&idCurso=".$id_curso."&categoria=".$categoria;
									$url2 = $this->urls[$this->estado]['agenda_pratico_incluir']."?data=".urlencode($dataBR)."&idInstrutor=".$id_instrutor."&idCurso=".$id_curso."&categoria=".$categoria;
	
									$this->browser->get($url);
									$this->browser->getHtmlObject(true);
									jLog('AGE Selecionando no grid de aulas práticas', $this->browser->session, 'aviso');
									$this->browser->post['__EVENTTARGET']     = '';
									$this->browser->post['__EVENTARGUMENT']   = '';
									$this->browser->post['__VIEWSTATE']       = $this->browser->getField('id-input','__VIEWSTATE');
									$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
									if ($this->estado == 'rj')
										$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
									$this->browser->post['btnBuscar'] = 'Buscar';
									$this->browser->post['ddlVeiculo'] = $id_veiculo;
									if ($this->estado <> 'ba')
									{
										$this->browser->post['ddlMinutos'] = $checkBoxes[$horaInicio]['minInicial'];
									}
									if ($this->estado == 'rj')
										$this->browser->post[$txtEstado] = $renach;
	
									$this->browser->post['txtCPF'] = $cpf;
									$this->browser->referer = $url;
									$this->browser->post($url2);
									$this->browser->getHtmlObject(true);
	
									$this->browser->post['__EVENTTARGET']     = '';
									$this->browser->post['__EVENTARGUMENT']   = '';
									$this->browser->post['__VIEWSTATE']       = $this->browser->getField('id-input','__VIEWSTATE');
									$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
	
									$dePara = '';
								}
								if ($this->estado == 'ba')
								{
									foreach ($aulas as $aulaKey=>$aula)
									{
										$diferencaEmSegundos = strtotime($aula['data']) - strtotime($dataInicio);
										$dia = floor($diferencaEmSegundos / (60 * 60 * 24));
										$hora = substr($aula['data'],11,5);
										$aulas[$aulaKey]['processado']=true;
										$processouAoMenosUmaAula = true;
										if (isset($checkBoxes[$hora]))
										{
											$this->browser->post['rptHorarios$ctl'.$checkBoxes[$hora]['checkbox'].'$CheckBox'.$dia] = 'on';
											
											if ($aula['dupla']) {
												$horamais1 = new DateTime($aula['data']);
												$horamais1->modify('+1 hour');
												$hora = $horamais1->format("H:i");
												$this->browser->post['rptHorarios$ctl'.$checkBoxes[$hora]['checkbox'].'$CheckBox'.$dia] = 'on';
											}
											
										} else
										{
											$hora = str_pad(intval(substr($aula['data'],11,2)-5),2,'0', STR_PAD_LEFT);
											$cb = 'rptHorarios$ctl'.$hora.'$CheckBox'.$dia;
											if (!isset($this->browser->post[$cb]))
											{
	
												$this->browser->post[$cb] = 'on';
												$aula['hora_de']   = str_pad(($hora+5),2,'0',STR_PAD_LEFT).':00';
												$aula['hora_para'] = substr($aula['data'],11,5);
												$aula['checkbox']  = $cb;
												$dePara[]          = $aula;
											}
										}
									}
								} else
								{
									$minInicial = $checkBoxes[$horaInicio]['minInicial'];
		//echo "checkBoxes:<br>";gD($checkBoxes);echo "<hr>Minutos<br>";gD($minutos);exit;
									// Verifica se existe outra aula que se enquadra neste perfil de horários e dentro de uma semana
									foreach ($aulas as $aulaKey=>$aula)
									{
										$diferencaEmSegundos = strtotime($aula['data']) - strtotime($dataInicio);
										$dia = floor($diferencaEmSegundos / (60 * 60 * 24));
										if ($dia<7)
										{
											// Procura outras aulas dentro da mesma grade (minuto inicial informado)
											foreach ($minutos[$minInicial] as $minuto)
											{
												if ($minuto['hora']==substr($aula['data'],11,5))
												{
		//echo "==> ".$aula['data']." = ".$minuto['hora']."<br>";
													$this->browser->post['rptHorarios$ctl'.$minuto['checkbox'].'$CheckBox'.$dia] = 'on';
													if ($aula['dupla'])
													{
														$this->browser->post['rptHorarios$ctl'.str_pad(intval($minuto['checkbox'])+1,2,'0',STR_PAD_LEFT).'$CheckBox'.$dia] = 'on';
													}
													$aulas[$aulaKey]['processado']=true;
													$processouAoMenosUmaAula = true;
												}
											}
										}
									}
								}
								if ($id_sala == '')
								{
									// Obter ddlServicoPratico
									$this->processaSelect('ddlServicoPratico');
									$id_sala = $this->select[0]['id'];
								}
	
								$this->browser->post['ddlServicoPratico'] = $id_sala;
								$this->browser->post['btnAgendarPratico'] = 'Agendar Candidato';
								$this->browser->referer = $url2;
								$this->browser->useHttpBuildQuery = false;
								$this->browser->headers[] = 'Upgrade-Insecure-Requests: 1';
		//gD($aulas);gD($this->browser->post);return;
								$this->browser->post($url2);
								$this->browser->getHtmlObject(true);
								$this->browser->setHeaders();
								//lblResultado
								if (strpos($this->browser->html, 'Ocorreu um erro durante o processamento')!==false)
								{
									$sai = false;
									$this->errorCode = '605';
									$this->errorMessage = 'Erro ao incluir aula prática do aluno';
									jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
									//echo $this->browser->html;
	
								} else
								{
									$msg = $this->browser->getField('id','lblResultado');
									if (strpos($msg,'com sucesso')!==false)
									{
										$sai = true;
										if (is_array($dePara))
										{
											$this->browser->getHtmlObject(true);
	
											$this->browser->post['__EVENTTARGET'] = '';
											$this->browser->post['__EVENTARGUMENT'] = '';
											$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
											$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
	
											jLog('>>>>>>>>>> Processando $dePara ' . json_encode($dePara));
											if ($this->estado == 'ba')
											{
												foreach ($aulas as $aula)
												{
													$diferencaEmSegundos = strtotime($aula['data']) - strtotime($dataInicio);
													$dia = floor($diferencaEmSegundos / (60 * 60 * 24));
													$hora = substr($aula['data'],11,5);
													if (isset($checkBoxes[$hora]))
													{
														$this->browser->post['rptHorarios$ctl'.$checkBoxes[$hora]['checkbox'].'$CheckBox'.$dia] = 'on';
													} else
													{
														$hora = str_pad(intval(substr($aula['data'],11,2)-5),2,'0', STR_PAD_LEFT);
														$cb = 'rptHorarios$ctl'.$hora.'$CheckBox'.$dia;
														if (!isset($this->browser->post[$cb]))
														{
															$this->browser->post[$cb] = 'on';
														}
													}
												}
											} else
											{
												// Acho que o código abaixo nunca é executado (por causa de $dePara)...
												$minInicial = $checkBoxes[$horaInicio]['minInicial'];
												foreach ($aulas as $aula)
												{
													$diferencaEmSegundos = strtotime($aula['data']) - strtotime($dataInicio);
													$dia = floor($diferencaEmSegundos / (60 * 60 * 24));
													foreach ($minutos[$minInicial] as $minuto)
													{
														if ($minuto['hora']==substr($aula['data'],11,5))
															$this->browser->post['rptHorarios$ctl'.$minuto['checkbox'].'$CheckBox'.$dia] = 'on';
													}
												}
											}
											if ($id_sala == '')
											{
												// Obter ddlServicoPratico
												$this->processaSelect('ddlServicoPratico');
												$id_sala = $this->select[0]['id'];
											}
	
											$this->browser->post['ddlServicoPratico'] = $id_sala;
											$this->browser->post['btnAgendarPratico'] = 'Agendar Candidato';
											$this->browser->referer = $url2;
											$this->browser->useHttpBuildQuery = false;
											$this->browser->headers[] = 'Upgrade-Insecure-Requests: 1';
											$this->browser->post($url2);
											$this->browser->getHtmlObject(true);
										}
									} else
									{
										$sai = false;
										$this->errorCode = '606';
										$this->errorMessage = $msg;
										jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
									}
								}
							}
							if ($processouAoMenosUmaAula)
							{
								// Reconstruindo o array aulas, removendo as aulas que foram processadas agora...
								$aulasNovo = '';
								foreach ($aulas as $aulaKey=>$aula)
								{
									if (!$aula['processado'])
									{
										$aulasNovo[] = $aula;
									}
								}
								$aulas = $aulasNovo;
							} else {
								// Se por algum motivo não processou nenhuma aula, sai do loop pra evitar travamento...
								$aulas = Array();
							}
						}

					} else
					{
						// Aulas teóricas
						jLog("=================================== Novo processamento de aula teórica - ".$aulas[0]['data']);

						$dataBR = date('d/m/Y', strtotime($dataInicio));
						$horaInicio = substr($dataInicio,11,5);
						$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+50 minute', strtotime($dataInicio))),11,5);
						$periodo = ($horaInicio<'12:00') ? 'M' : ($horaInicio>=HORA_LIMITE_AULAS_50_MINUTOS ? 'N' : 'T');

						// Fase 1 - seleciona dia, tipo de atividade
						$this->agendaAulaAluno_fase1($dataBR,$periodo,$id_servico,$p_t,$id_curso);


						if (strpos($this->browser->html, 'Ocorreu um erro durante o processamento')!==false)
						{
							$sai = false;
							$this->errorCode = '605';
							$this->errorMessage = 'Erro ao agendar aula teórica';
							jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
						} else {

							// Busca Id do elemento...
							$matches = "";
							$fez = false;
							$idGradeCurso = '';
							$idInstrutor = '';
							$horaInicioInt = intval(str_replace( ':','', $horaInicio));

							preg_match_all('/(_dados).*/i', $this->browser->html, $matches);

							foreach ($matches[0] as $match)
							{
								$campos = explode('"', $match);
								if (count($campos)>1)
								{
									//_dados["a6052632_25851"] = ["T", "600", "0", "650", "JEAN CARLO BAZANI DOS SANTOS", "", "0", "", "Renovação", "1", "T"];
									//_dados["a5813408_25851"] = ["P", "1030", "0", "1120", "JEAN CARLO BAZANI DOS SANTOS", "LEANDRO FERREIRA COSTA", "1", "LRW3466", "Primeira Habilitação","1","T"];
									$horaInicioInt = intval(str_replace( ':','', $horaInicio));
									$campoIds = $campos[1];$campoIds = explode('_', $campoIds);
									$campoTipo = $campos[3];
									$campoHora = $campos[5];
									$campoInst = $campos[11];
									$campoAlun = $campos[13];
									$campoPlac = $campos[17];
									$campoServ = $campos[19];
									$campoCurs = $campos[21];
									jLog("Checando T > $p_t H:$horaInicioInt I:$id_instrutor S:$servico C:$id_curso P:$placa = [$campoTipo] [".$campoHora."] [".$campoInst."] [".$campoServ."] [".$campoCurs."] [".$campoPlac."]", $this->browser->session);
									if ($campoTipo == $p_t)
									{
										// Teórico
										if ($campoHora == $horaInicioInt )
										{
											$idGradeCurso = substr($campoIds[0],1);
											$idInstrutor = $campoIds[1];
											jLog("Checando novamente $idInstrutor = $id_instrutor", $this->browser->session);
											if ($idInstrutor == $id_instrutor)
											{
												$fez = true;
												jLog("Encontrado!", $this->browser->session);
											}
										}
									}
								}
							}

							if ($fez)
							{
								// Fase 2 - Obtém janela de horários e define o Renach e CPF
								$url = $this->urls[$this->estado]['agenda_incluir']."?idGradeCurso=".$idGradeCurso."&tpCurso=".$p_t."&idInstrutor=".$id_instrutor;
								$this->browser->get($url);
								$this->browser->getHtmlObject(true);
								$post = '';
								$this->browser->post['__EVENTTARGET'] = '';
								$this->browser->post['__EVENTARGUMENT'] = '';
								$this->browser->post['__VIEWSTATE'] = ($this->browser->getField('id-input','__VIEWSTATE'));
								if ($this->estado == 'rj')
									$this->browser->post['__VIEWSTATEGENERATOR'] = ($this->browser->getField('id-input','__VIEWSTATEGENERATOR'));
								$this->browser->post['__EVENTVALIDATION'] = ($this->browser->getField('id-input','__EVENTVALIDATION'));
								$this->browser->post['txtCPF'] = $cpf;
								if ($this->estado == 'rj')
								{
									$this->browser->post['uf'] = strtoupper($this->estado);
									$this->browser->post[$txtEstado] = $renach;
								}
								$this->browser->post['btnConsultarCandidato'] = 'Consultar';
								$this->browser->referer = $url;
								$this->browser->useHttpBuildQuery = false;
								$this->browser->headers[] = 'Upgrade-Insecure-Requests: 1';
								$this->browser->post($url);
								$this->browser->getHtmlObject(true);
								$this->browser->referer = $url;

								if (strpos($this->browser->html, 'Ocorreu um erro durante o processamento')!==false)
								{
									$sai = false;
									$this->errorCode = '605';
									$this->errorMessage = 'Erro ao incluir aula do aluno';
									jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
									//echo $this->browser->html;
								} else {
									$post = '';
									$this->browser->post['__EVENTTARGET'] = '';
									$this->browser->post['__EVENTARGUMENT'] = '';
									$this->browser->post['__VIEWSTATE'] = ($this->browser->getField('id-input','__VIEWSTATE'));
									if ($this->estado == 'rj')
										$this->browser->post['__VIEWSTATEGENERATOR'] = ($this->browser->getField('id-input','__VIEWSTATEGENERATOR'));
									$this->browser->post['__EVENTVALIDATION'] = ($this->browser->getField('id-input','__EVENTVALIDATION'));
									//$this->browser->post['txtCPF'] = $cpf;
									if ($this->estado == 'rj')
									{
										$this->browser->post['uf'] = strtoupper($this->estado);
										//$this->browser->post[$txtEstado] = $renach;
									}
									$this->browser->post['btnAgendarCandidato'] = 'Agendar Candidato';
									$this->browser->referer = $url;
									$this->browser->useHttpBuildQuery = false;
									$this->browser->post($url);
									$this->browser->getHtmlObject(true);
									$this->browser->referer = '';
									$this->browser->useHttpBuildQuery = true;

									if (strpos($this->browser->html, 'Ocorreu um erro durante o processamento')!==false)
									{
										$sai = false;
										$this->errorCode = '605';
										$this->errorMessage = 'Erro ao incluir aula do aluno';
										jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
										//echo $this->browser->html;
									} else {
										$erro = $this->browser->getField('id','lblErroAgendamento');
										if ($erro == '')
										{
											$sai = true;

										} else
										{
											$sai = false;
											$this->errorCode = '606';
											$this->errorMessage = $erro;
											jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');

										}
									}
								}


							} else {
								$sai = false;
								$this->errorCode = '609';
								$this->errorMessage = 'Erro ao agendar aula do aluno - Instrutor não encontrado ou horário não encontrado na grade do Detran ('.$horaInicio.')';
								jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
							}
						}
					}

				}
				break;
		}
		return($sai);
	}
	

	/**
	 * Obtem os código dos simuladores da interprint. 
	 * @param  array $aulas
	 * @return boolean True/False (sucesso/falha)
	 */
	function buscaSimuladores($aulas)
	{
		$sai = false;
		
		jLog("interprint->buscaSimuladores");

		$id_instrutor = $aulas[0]['id_instrutor'];
		$id_curso     = $aulas[0]['id_curso'];
		$id_sala      = $aulas[0]['id_sala'];
		$id_servico   = $aulas[0]['id_servico'];
		$id_veiculo   = $aulas[0]['id_veiculo'];
		$dataInicio   = $aulas[0]['data'];
		$servico      = $aulas[0]['servico'];
		$categoria    = $aulas[0]['categoria'];
		$cpf          = $aulas[0]['cpf'];
		$placa        = $aulas[0]['placa'];
		$renach       = gToNumbers($aulas[0]['renach']);

		$uf = strtoupper($this->estado);
		$txtEstado = 'Txt'.strtoupper($this->estado);
		// Converter datas em checkboxes da Interprint
		$f = 0;
		$hora = '06:00';
		$hora2 = $hora;
		if ($this->estado == 'ba')
		{
			$minInicial = '00';
			for ($g = 1; $g<21; $g++)
			{
				$cb = str_pad($g, 2, '0', STR_PAD_LEFT);
				if (!isset($checkBoxes[$hora2]))
				{
					$checkBoxes[$hora2] = array(
						'minInicial'		=> $minInicial,
						'checkbox'			=> $cb);
				}
				{
					$minutos[$minInicial][] = array(
						'hora'				=> $hora2,
						'checkbox'			=> $cb);
				}
				$hora2 = date('H:i', strtotime('+1 hour', strtotime($hora2)));
			}
		} else
		{
			while ($f<60)
			{
				$minInicial = str_pad($f, 2, '0', STR_PAD_LEFT);
				$hora2 = $hora;
				for ($g = 1; $g<21; $g++)
				{
					$cb = str_pad($g, 2, '0', STR_PAD_LEFT);
					if (!isset($checkBoxes[$hora2]))
					{
						$checkBoxes[$hora2] = array(
							'minInicial'		=> $minInicial,
							'checkbox'			=> $cb);
					}
					{
						$minutos[$minInicial][] = array(
							'hora'				=> $hora2,
							'checkbox'			=> $cb);
					}
					$hora2 = date('H:i', strtotime('+50 minute', strtotime($hora2)));
				}
				$hora = date('H:i', strtotime('+5 minute', strtotime($hora)));
				$f = $f+5;
			}

		}
		
		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':

				jLog('SIM Obtendo simuladores... ', $this->browser->session, 'aviso');
				if ($this->login())
				{
					{
						$p_t = 'P';
						// Aulas de simulador
						jLog("=================================== SIM Novo processamento de aula pratica [Fake] - ".$aulas[0]['data']);

						$dataBR = date('d/m/Y', strtotime($dataInicio));
						$horaInicio = substr($dataInicio,11,5);
						$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+50 minute', strtotime($dataInicio))),11,5);
						$periodo = ($horaInicio<'12:00') ? 'M' : ($horaInicio>=HORA_LIMITE_AULAS_50_MINUTOS ? 'N' : 'T');

						// Fase 1 - seleciona dia, tipo de atividade
						$this->agendaAulaAluno_fase1($dataBR,$periodo,$id_servico,$p_t,$id_curso); // Abre a a grade de agendamento no dia da aula e selecionando a disciplina/curso


						if (strpos($this->browser->html, 'Ocorreu um erro durante o processamento')!==false)
						{
							$sai = false;
							$this->errorCode = '605';
							$this->errorMessage = 'Erro ao abrir tela de agendamento';
							jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
						} else {
							// Fase 2 - agendando a aula pratica de simulador (vazia)
							
							$hrInicio = str_replace(":", '', $horaInicio);
							$hrTermino = str_replace(":", '', $horaTermino);
							// https://cfcweb.detran.rj.gov.br/CFC/Agendamento3_Novo.aspx?tpCurso=P&data=27/12/2017&hrInicio=600&hrTermino=2300&idInstrutor=18367&idCurso=13&categoria=B&servico=1
							$url = $this->urls[$this->estado]['agenda_incluir_2'] . "?tpCurso=P&data=$dataBR&hrInicio=$hrInicio&hrTermino=$hrTermino&idInstrutor=$id_instrutor&idCurso=$id_curso&categoria=$categoria&servico=$id_servico";
							$url2 = $this->urls[$this->estado]['agenda_incluir_2'] . "?tpCurso=P&data=$dataBR&hrInicio=$hrInicio&hrTermino=$hrTermino&idInstrutor=$id_instrutor&idCurso=$id_curso&categoria=$categoria&servico=$id_servico";
							
							$this->browser->get($url);
							$this->browser->getHtmlObject(true);
							jLog('SIM Selecionando no grid de aulas práticas', $this->browser->session, 'aviso');
							$this->browser->post['__EVENTTARGET'] = '';
							$this->browser->post['__EVENTARGUMENT'] = '';
							$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input', '__VIEWSTATE');
							$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input', '__EVENTVALIDATION');
							if ($this->estado == 'rj')
								$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input', '__VIEWSTATEGENERATOR');
							
							$this->browser->post['hdiServCurso'] = $this->browser->getField('id-input', 'hdiServCurso');
							$this->browser->post['txtHoraInicio'] = $horaInicio;
							$this->browser->post['txtHoraTermino'] = $horaTermino;
							$this->browser->post['btnCriarTeorico'] = 'Criar Aula Prática';
							$this->browser->referer = $url;
							$this->browser->post($url2);
							$this->browser->getHtmlObject(true);
							
							$this->browser->post['__EVENTTARGET'] = '';
							$this->browser->post['__EVENTARGUMENT'] = '';
							$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input', '__VIEWSTATE');
							$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input', '__EVENTVALIDATION');
							
							// Fase 3 - abrindo a aula pratica e pesquisando pelo aluno
							
							$this->agendaAulaAluno_fase1($dataBR,$periodo,$id_servico,$p_t,$id_curso); // Volta à tela de agendamento ...
							
							// Busca Id da aula recém criada...
							$matches = "";
							$fez = false;
							$idGradeCurso = '';
							$idInstrutor = '';
							$horaInicioInt = intval(str_replace(':', '', $horaInicio));
							
							preg_match_all('/(_dados).*/i', $this->browser->html, $matches);
							
							jLog(json_encode($matches));
							
							foreach ($matches[0] as $match) {
								$campos = explode('"', $match);
								if (count($campos) > 1) {
									// _dados["a5813408_25851"] = ["P", "1030", "0", "1120", "JEAN CARLO BAZANI DOS SANTOS", "LEANDRO FERREIRA COSTA", "1", "LRW3466", "Primeira Habilitação","1","T"];
									$horaInicioInt = intval(str_replace(':', '', $horaInicio));
									$campoIds = $campos[1];
									$campoIds = explode('_', $campoIds);
									$campoTipo = $campos[3];
									$campoHora = $campos[5];
									$campoInst = $campos[11];
									$campoAlun = $campos[13];
									$campoPlac = $campos[17];
									$campoServ = $campos[19];
									$campoCurs = $campos[21];
									$campoServ = gToUpper(html_entity_decode($campoServ));
									
									$sql = "SELECT";
									
									jLog("Checando T:$p_t H:$horaInicioInt I:$id_instrutor S:$servico C:$id_curso P:$placa = [$campoTipo] [" . $campoHora . "] [" . $campoInst . "] [" . $campoServ . "] [" . $campoCurs . "] [" . $campoPlac . "]", $this->browser->session);
									if ($campoTipo == $p_t && $servico == $campoServ) {
										if ($campoHora == $horaInicioInt) {
											$idGradeCurso = substr($campoIds[0], 1);
											$idInstrutor = $campoIds[1];
											jLog("Checando novamente $idInstrutor = $id_instrutor", $this->browser->session);
											if ($idInstrutor == $id_instrutor) {
												$fez = true;
												jLog("Encontrado!", $this->browser->session);
												break;
											}
										}
									}
								}
							}
							
							if ($fez) {
								jLog('SIM Abrindo aula no grid e pesquisando pelo aluno', $this->browser->session, 'aviso');
								
								// https://cfcweb.detran.rj.gov.br/CFC/Agendamento_Candidato.aspx?idGradeCurso=17750546&tpCurso=P&idInstrutor=35761
								$url = $this->urls[$this->estado]['agenda_incluir'] . "?idGradeCurso=$idGradeCurso&tpCurso=P&idInstrutor=$idInstrutor";
								$url2 = $this->urls[$this->estado]['agenda_incluir'] . "?idGradeCurso=$idGradeCurso&tpCurso=P&idInstrutor=$idInstrutor";
								
								$this->browser->get($url);
								$this->browser->getHtmlObject(true);
								$this->browser->post['__EVENTTARGET'] = '';
								$this->browser->post['__EVENTARGUMENT'] = '';
								$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input', '__VIEWSTATE');
								$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input', '__EVENTVALIDATION');
								if ($this->estado == 'rj')
									$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input', '__VIEWSTATEGENERATOR');

								$this->browser->post['btnConsultarCandidato'] = 'Consultar';
								
								if ($this->estado == 'rj') {
									$this->browser->post[$txtEstado] = $renach;
								}
								$this->browser->post['txtCPF'] = $cpf;
								
								$this->browser->referer = $url;
								$this->browser->post($url2);
								$this->browser->getHtmlObject(true);

								// Salva os IDs dos simuladores na base do WEB•CFC
								if ($this->processaSelect('ddlSimulador')) {
									if(is_array($this->select)) {
										$sql = "SELECT placa FROM interprint_veiculos";
										$rsv = dbQuery($sql);
										
										foreach ($this->select as $interprint_veic) {
											$achou = false;
											foreach ($rsv as $v) {
												if ($interprint_veic['html'] == $v['placa']) {
													$achou = true;
													break;
												}
											}
											if (! $achou) {
												if ( intval($interprint_veic['id']) && $interprint_veic['html'] <> '') {
													$fields = '';
													$fields['id_externo'] = $interprint_veic['id'];
													$fields['placa'] = $interprint_veic['html'];
													
													jLog("Salvando novo simulador ({$interprint_veic['id']} => '{$interprint_veic['html']}')");
													$id_interpint_veiculos = dbInsert('interprint_veiculos', $fields, true);
													
													if ($id_interpint_veiculos > 0) {
														jLog("Associando o simulador ({$interprint_veic['html']}) ao ID da inteprint ({$interprint_veic['id']})");
														
														$sql = "UPDATE frota_veiculos 
																SET id_interprint_veiculos = '{$fields['id_externo']}' 
																WHERE placa = '{$fields['placa']}' AND situacao = 'Ativo'";
														dbQuery($sql);
														$sai = true;
													}
													
												}
											}
										}
									}
								}
								
								// Fase 4 - Apaga a aula vazia criada para obtenção dos simuladores
								
								$this->browser->post['__EVENTTARGET'] = 'lkbExcluirGrade';
								$this->browser->post['__EVENTARGUMENT'] = '';
								$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input', '__VIEWSTATE');
								$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input', '__EVENTVALIDATION');
								
								if ($this->estado == 'rj') {
									$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input', '__VIEWSTATEGENERATOR');
									$this->browser->post[$txtEstado] = $renach;
								}
								$this->browser->post['txtCPF'] = $cpf;
								$this->browser->referer = $url;
								$this->browser->post($url2);
								$this->browser->getHtmlObject(true);
							}
							
						}
					}

				}
				break;
		}
		jLog('Final da busca de simuladores');
		return($sai);
	}
	
	/**
	 * Agenda aula no simulador
	 * @param  array $aulas
	 * @return boolean True/False (sucesso/falha)
	 */
	function agendaAulaSimulador($aulas)
	{
		$sai = false;
		
		jLog("Iniciando agendamento no simulador");

		$id_instrutor = $aulas[0]['id_instrutor'];
		$id_curso     = $aulas[0]['id_curso'];
		$id_sala      = $aulas[0]['id_sala'];
		$id_servico   = $aulas[0]['id_servico'];
		$id_veiculo   = $aulas[0]['id_veiculo'];
		$dataInicio   = $aulas[0]['data'];
		$servico      = $aulas[0]['servico'];
		$categoria    = $aulas[0]['categoria'];
		$cpf          = $aulas[0]['cpf'];
		$placa        = $aulas[0]['placa'];
		$renach       = gToNumbers($aulas[0]['renach']);
		
		if(intval($id_curso) == 0) {
			
			$this->errorCode = '605';
			$this->errorMessage = 'Erro ao agendar aula em '.gDateTime($dataInicio).'. É necessário informar a disciplina da aula prática de simulador.';
			jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
			return false;
		}

		$uf = strtoupper($this->estado);
		$txtEstado = 'Txt'.strtoupper($this->estado);
		// Converter datas em checkboxes da Interprint
		$f = 0;
		$hora = '06:00';
		$hora2 = $hora;
		if ($this->estado == 'ba')
		{
			$minInicial = '00';
			for ($g = 1; $g<21; $g++)
			{
				$cb = str_pad($g, 2, '0', STR_PAD_LEFT);
				if (!isset($checkBoxes[$hora2]))
				{
					$checkBoxes[$hora2] = array(
						'minInicial'		=> $minInicial,
						'checkbox'			=> $cb);
				}
				{
					$minutos[$minInicial][] = array(
						'hora'				=> $hora2,
						'checkbox'			=> $cb);
				}
				$hora2 = date('H:i', strtotime('+1 hour', strtotime($hora2)));
			}
		} else
		{
			while ($f<60)
			{
				$minInicial = str_pad($f, 2, '0', STR_PAD_LEFT);
				$hora2 = $hora;
				for ($g = 1; $g<21; $g++)
				{
					$cb = str_pad($g, 2, '0', STR_PAD_LEFT);
					if (!isset($checkBoxes[$hora2]))
					{
						$checkBoxes[$hora2] = array(
							'minInicial'		=> $minInicial,
							'checkbox'			=> $cb);
					}
					{
						$minutos[$minInicial][] = array(
							'hora'				=> $hora2,
							'checkbox'			=> $cb);
					}
					$hora2 = date('H:i', strtotime('+50 minute', strtotime($hora2)));
				}
				$hora = date('H:i', strtotime('+5 minute', strtotime($hora)));
				$f = $f+5;
			}

		}
		
		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':

				jLog('SIM Obtendo simuladores... ', $this->browser->session, 'aviso');
				if ($this->login())
				{
					{
						$p_t = 'P';
						// Aulas de simulador
						jLog("=================================== SIM Novo processamento de aula pratica - ".$aulas[0]['data']);

						$dataBR = date('d/m/Y', strtotime($dataInicio));
						$horaInicio = substr($dataInicio,11,5);
						$horaTermino = substr(date('Y-m-d H:i:s', strtotime('+50 minute', strtotime($dataInicio))),11,5);
						$periodo = ($horaInicio<'12:00') ? 'M' : ($horaInicio>=HORA_LIMITE_AULAS_50_MINUTOS ? 'N' : 'T');

						// Fase 1 - seleciona dia, tipo de atividade
						jLog("SIM Abrindo tela de agendamento", $this->browser->session, 'aviso');
						$this->agendaAulaAluno_fase1($dataBR,$periodo,$id_servico,$p_t,$id_curso); // Abre a a grade de agendamento no dia da aula e selecionando a disciplina/curso


						if (strpos($this->browser->html, 'Ocorreu um erro durante o processamento')!==false)
						{
							$sai = false;
							$this->errorCode = '605';
							$this->errorMessage = 'Erro ao abrir tela de agendamento';
							jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
						} else {
							// Fase 2 - agendando a aula pratica de simulador (vazia)
							
							$hrInicio = str_replace(":", '', $horaInicio);
							$hrTermino = str_replace(":", '', $horaTermino);
							// https://cfcweb.detran.rj.gov.br/CFC/Agendamento3_Novo.aspx?tpCurso=P&data=27/12/2017&hrInicio=600&hrTermino=2300&idInstrutor=18367&idCurso=13&categoria=B&servico=1
							$url = $this->urls[$this->estado]['agenda_incluir_2'] . "?tpCurso=P&data=$dataBR&hrInicio=$hrInicio&hrTermino=$hrTermino&idInstrutor=$id_instrutor&idCurso=$id_curso&categoria=$categoria&servico=$id_servico";
							$url2 = $this->urls[$this->estado]['agenda_incluir_2'] . "?tpCurso=P&data=$dataBR&hrInicio=$hrInicio&hrTermino=$hrTermino&idInstrutor=$id_instrutor&idCurso=$id_curso&categoria=$categoria&servico=$id_servico";
							
							jLog('SIM Abrindo horário vago no grid de aulas práticas', $this->browser->session, 'aviso');
							$this->browser->get($url);
							$this->browser->getHtmlObject(true);
							
							$this->browser->post['__EVENTTARGET'] = '';
							$this->browser->post['__EVENTARGUMENT'] = '';
							$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input', '__VIEWSTATE');
							$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input', '__EVENTVALIDATION');
							if ($this->estado == 'rj')
								$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input', '__VIEWSTATEGENERATOR');
							
							$this->browser->post['hdiServCurso'] = $this->browser->getField('id-input', 'hdiServCurso');
							$this->browser->post['txtHoraInicio'] = $horaInicio;
							$this->browser->post['txtHoraTermino'] = $horaTermino;
							$this->browser->post['btnCriarTeorico'] = 'Criar Aula Prática';
							$this->browser->referer = $url;
							
							jLog('SIM Criando aula prática (vazia)', $this->browser->session, 'aviso');
							$this->browser->post($url2);
							$this->browser->getHtmlObject(true);
							
							$this->browser->post['__EVENTTARGET'] = '';
							$this->browser->post['__EVENTARGUMENT'] = '';
							$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input', '__VIEWSTATE');
							$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input', '__EVENTVALIDATION');
							
							// Fase 3 - abrindo a aula pratica e pesquisando pelo aluno
							
							$this->agendaAulaAluno_fase1($dataBR,$periodo,$id_servico,$p_t,$id_curso); // Volta à tela de agendamento ...
							
							// Busca Id da aula recém criada...
							$matches = "";
							$fez = false;
							$idGradeCurso = '';
							$idInstrutor = '';
							$horaInicioInt = intval(str_replace(':', '', $horaInicio));
							
							preg_match_all('/(_dados).*/i', $this->browser->html, $matches);
							
							jLog('SIM Recuperando ID da aula recém criada', $this->browser->session, 'aviso');
							jLog(json_encode($matches));
							
							foreach ($matches[0] as $match) {
								$campos = explode('"', $match);
								if (count($campos) > 1) {
									// _dados["a5813408_25851"] = ["P", "1030", "0", "1120", "JEAN CARLO BAZANI DOS SANTOS", "LEANDRO FERREIRA COSTA", "1", "LRW3466", "Primeira Habilitação","1","T"];
									$horaInicioInt = intval(str_replace(':', '', $horaInicio));
									$campoIds = $campos[1];
									$campoIds = explode('_', $campoIds);
									$campoTipo = $campos[3];
									$campoHora = $campos[5];
									$campoInst = $campos[11];
									$campoAlun = $campos[13];
									$campoPlac = $campos[17];
									$campoServ = $campos[19];
									$campoCurs = $campos[21];
									$campoServ = gToUpper(html_entity_decode($campoServ));
									
									jLog("SIM Checando T:$p_t H:$horaInicioInt I:$id_instrutor S:$servico C:$id_curso P:$placa = [$campoTipo] [" . $campoHora . "] [" . $campoInst . "] [" . $campoServ . "] [" . $campoCurs . "] [" . $campoPlac . "]", $this->browser->session);
									if ($campoTipo == $p_t && $servico == $campoServ) {
										if ($campoHora == $horaInicioInt) {
											$idGradeCurso = substr($campoIds[0], 1);
											$idInstrutor = $campoIds[1];
											jLog("SIM Checando novamente $idInstrutor = $id_instrutor", $this->browser->session);
											if ($idInstrutor == $id_instrutor) {
												$fez = true;
												jLog("SIM Aula encontrada!", $this->browser->session);
												break;
											}
										}
									}
								}
							}
							
							if ($fez) {
								// https://cfcweb.detran.rj.gov.br/CFC/Agendamento_Candidato.aspx?idGradeCurso=17750546&tpCurso=P&idInstrutor=35761
								$url = $this->urls[$this->estado]['agenda_incluir'] . "?idGradeCurso=$idGradeCurso&tpCurso=P&idInstrutor=$idInstrutor";
								$url2 = $this->urls[$this->estado]['agenda_incluir'] . "?idGradeCurso=$idGradeCurso&tpCurso=P&idInstrutor=$idInstrutor";
								
								jLog('SIM Abrindo aula no grid', $this->browser->session, 'aviso');
								$this->browser->get($url);
								$this->browser->getHtmlObject(true);
								
								$this->browser->post['__EVENTTARGET'] = '';
								$this->browser->post['__EVENTARGUMENT'] = '';
								$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input', '__VIEWSTATE');
								$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input', '__EVENTVALIDATION');
								if ($this->estado == 'rj')
									$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input', '__VIEWSTATEGENERATOR');

								$this->browser->post['btnConsultarCandidato'] = 'Consultar';
								
								if ($this->estado == 'rj') {
									$this->browser->post[$txtEstado] = $renach;
								}
								$this->browser->post['txtCPF'] = $cpf;
								
								$this->browser->referer = $url;
								jLog('SIM Pesquisando pelo aluno', $this->browser->session, 'aviso');
								$this->browser->post($url2);
								$this->browser->getHtmlObject(true);
							
								// Fase 4 - Confirma aluno na aula
								
								$this->browser->post['__EVENTTARGET'] = '';
								$this->browser->post['__EVENTARGUMENT'] = '';
								$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input', '__VIEWSTATE');
								$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input', '__EVENTVALIDATION');
								if ($this->estado == 'rj') {
									$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input', '__VIEWSTATEGENERATOR');
									$this->browser->post['uf'] = 'RJ';
								}
									
								$this->browser->post['ddlSimulador'] = $id_veiculo;
								$this->browser->post['btnAgendarCandidato'] = 'Agendar Candidato';
									
								jLog('SIM Agendando aluno na aula', $this->browser->session, 'aviso');
								$this->browser->referer = $url;
								$this->browser->post($url2);
								$this->browser->getHtmlObject(true);
								
								// Verificando se o aluno foi adicionado à aula
								$erroAgendamento = $this->browser->getField('id', 'lblErroAgendamento');
								jLog('SIM erro agendamento? => ' . $erroAgendamento);
								if($erroAgendamento <> '') {
									$sai = false;
									$this->errorCode = '606';
									$this->errorMessage = 'Erro ao agendar aula prática de simulador <br/>' . $erroAgendamento;
									jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
									
									// Apaga aula criada... 
									$this->browser->post['__EVENTTARGET'] = 'lkbExcluirGrade';
									$this->browser->post['__EVENTARGUMENT'] = '';
									$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input', '__VIEWSTATE');
									$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input', '__EVENTVALIDATION');
									
									if ($this->estado == 'rj') {
										$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input', '__VIEWSTATEGENERATOR');
										$this->browser->post[$txtEstado] = $renach;
									}
									
									$this->browser->post['txtCPF'] = $cpf;
									$this->browser->referer = $url;
									$this->browser->post($url2);
									$this->browser->getHtmlObject(true);
									
								} else {
									$sai = true;
									jLog('SIM Aula agendada!', $this->browser->session, 'aviso');
								}
							}
						}
					}

				}
				break;
		}
		jLog('SIM final do agendamento');
		return($sai);
	}





	function removeAgendamento($json, $data, $tipo = 'P')
	{
		$sai = false;
		if (is_array($data))
			$data = $data[0];
		$jarr = cssDecode($json);
		$dataInicio = $data;
		$dataTermino = $data;
		$cpf = gToNumbers($jarr['cpf']);
		$renach = gToNumbers($jarr['renach']);
		$id_instrutor = $jarr['id_instrutor'];
		$id_curso = $jarr['id_curso'];
		$id_sala = $jarr['id_sala'];
		$id_servico = $jarr['id_servico'];
		$uf = strtoupper($this->estado);
		$id_veiculo = $jarr['id_veiculo'];
		$placa = $jarr['placa'];
		$servico = $jarr['servico'];
		$categoria = $jarr['categoria'];
		$p_t = ($placa<>'') ? 'P' : 'T';
		$excluir_turma = intval($jarr['excluir_turma']); // parametro enviado apenas no json da classe Turma.

		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':

				jLog('AGE Removendo agendamento...', $this->browser->session, 'aviso');
				if ($this->login())
				{
					$dataBR = date('d/m/Y', strtotime($dataInicio));
					$horaInicio = substr($dataInicio,11,5);
					$horaTermino = substr(date('Y-m-d gi:s', strtotime('+50 minute', strtotime($dataTermino))),11,5);
					$periodo = ($horaInicio<'12:00') ? 'M' : ($horaInicio>=HORA_LIMITE_AULAS_50_MINUTOS ? 'N' : 'T');
					// Fase 1
					//$this->browser->referer = 'https://cfcweb.detran.rj.gov.br/principal.aspx?sis=1';
					$this->browser->get($this->urls[$this->estado]['agenda_grade']);
					$this->browser->getHtmlObject(true);
					preg_match_all('/(CombinedScripts).*/i', $this->browser->html, $matches);

					foreach ($matches[0] as $match)
					{
						$campos = explode('"', $match);
						$campos = explode('=', $campos[0]);
						$CFCMaster_cphBody_ToolkitScriptManager1_HiddenField = urldecode($campos[1]);
					}
					$this->browser->post['CFCMaster$cphBody$Acord_AccordionExtender_ClientState'] = '1';
					$this->browser->post['CFCMaster$cphBody$btnAgendamento'] = 'Button';
					$this->browser->post['CFCMaster$cphBody$hdiData'] = $dataBR;
					$this->browser->post['CFCMaster$cphBody$hdiPeriodo'] = $periodo;
					$this->browser->post['CFCMaster$cphBody$hdiServi'] = $id_servico.'_'.$p_t.$id_curso.',';
					$this->browser->post['CFCMaster_cphBody_ToolkitScriptManager1_HiddenField'] = $CFCMaster_cphBody_ToolkitScriptManager1_HiddenField;
					$this->browser->post['DataServico'] = $dataBR;
					$this->browser->post['chkServico'] = 'on';
					if ($this->estado=='ba')
					{
						$this->browser->post['Periodo1'] = 'on';
						$this->browser->post['Periodo5'] = 'on';
						$this->browser->post['Periodo6'] = 'on';
						$this->browser->post['Periodo7'] = 'on';
						$this->browser->post['Periodo9'] = 'on';
						$this->browser->post['PeriodoX'] = 'on';
					}
					$this->browser->post['__EVENTARGUMENT'] = '';
					$this->browser->post['__EVENTTARGET'] = '';
					$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
					$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
					$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
					jLog("AGE Removendo agendamento > CPF: $cpf RENACH: $renach", $this->browser->session, 'aviso');
					$this->browser->referer = $this->urls[$this->estado]['agenda_grade'];
					$this->browser->post($this->urls[$this->estado]['agenda_grade']);
					$this->browser->getHtmlObject(true);

					// Busca Id do elemento a excluir...

					$matches = "";
					$fez = false;
					$idGradeCurso = '';
					$idInstrutor = '';
					$horaInicioInt = intval(str_replace( ':','', $horaInicio));

					preg_match_all('/(_dados).*/i', $this->browser->html, $matches);

					foreach ($matches[0] as $match)
					{
						$campos = explode('"', $match);
						if (count($campos)>1)
						{
							//_dados["a6052632_25851"] = ["T", "600", "0", "650", "JEAN CARLO BAZANI DOS SANTOS", "", "0", "", "Renovação", "1", "T"];
							//_dados["a5813408_25851"] = ["P", "1030", "0", "1120", "JEAN CARLO BAZANI DOS SANTOS", "LEANDRO FERREIRA COSTA", "1", "LRW3466", "Primeira Habilitação","1","T"];
							$campoIds = $campos[1];$campoIds = explode('_', $campoIds);
							$campoTipo = $campos[3];
							$campoHora = $campos[5];
							$campoInst = $campos[11];
							$campoAlun = $campos[13];
							$campoPlac = $campos[17];
							$campoServ = $campos[19];
							$campoCurs = $campos[21];

							$campoServ = gToUpper(html_entity_decode($campoServ));

// 							jLog("Checando H:$horaInicioInt I:$id_instrutor S:$servico C:$id_curso P:$placa = [".$campoHora."] [".$campoInst."] [".$campoServ."] [".$campoCurs."] [".$campoPlac."]", $this->browser->session);
							jLog("Checando: Tipo[$p_t = $campoTipo], Hora[$horaInicioInt = $campoHora], Instrutor[$id_instrutor = {$campoIds[1]}], Servico[$servico = $campoServ], Curso[$id_curso = $campoCurs], Placa[$placa = $campoPlac]", $this->browser->session);
							if ($campoTipo == $p_t)
							{
								if ($p_t == 'T')
								{
									// Teórico
									if ($campoHora == $horaInicioInt && $campoServ == $servico /*&& $campoCurs == $id_curso*/) // Removendo, temporariamente, a verificacao da disciplina devido a inconsistencias na Valid
									{
										$idGradeCurso = substr($campoIds[0],1);
										$idInstrutor = $campoIds[1];
										jLog("Checando novamente $idInstrutor = $id_instrutor", $this->browser->session);
										if ($idInstrutor == $id_instrutor)
										{
											$fez = true;
											jLog("Encontrado!", $this->browser->session);
										}
									}
								} else {
									// Prático
									if (intval($jarr['simulador']) > 0) {
										$placa = ""; // A pagina da interprint nao informa placa dos simuladores, é preciso desconsiderá-la para desmarcar a aula
									}
									
									if ($campoPlac == $placa && $campoHora == $horaInicioInt && $campoServ == $servico )
									{
										$idGradeCurso = substr($campoIds[0],1);
										$idInstrutor = $campoIds[1];
										jLog("Checando novamente $idInstrutor = $id_instrutor", $this->browser->session);
										if ($idInstrutor == $id_instrutor)
										{
											$fez = true;
											jLog("Encontrado!", $this->browser->session);
										}
									}
								}
							}
						}
					}

					if ($fez)
					{
						// Fase 2 - Obtém janela de horários e define o Renach e CPF
						$url = $this->urls[$this->estado]['agenda_popup']."?idGradeCurso=".$idGradeCurso."&tpCurso=".$p_t."&idInstrutor=".$id_instrutor;
						$this->browser->referer = $url;
						$this->browser->get($url);
						$this->browser->getHtmlObject(true);

						if ($p_t == 'P')
						{
							// Remover aula prática

							//$this->browser->post['btnConsultarCandidato'] = 'Consultar';
							$this->browser->post['__EVENTARGUMENT'] = '';
							$this->browser->post['__VIEWSTATE'] = ($this->browser->getField('id-input','__VIEWSTATE'));
							if ($this->estado=='rj')
							{
								$this->browser->post['__EVENTTARGET'] = 'lkbExcluirGrade';
								$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
								$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
								$this->browser->post['txtCPF'] = '';
								$this->browser->post['uf'] = strtoupper($this->estado);
								$this->browser->post['TxtRJ'] = '';
							} else
							{
								$this->browser->post['__EVENTTARGET'] = 'lkbExcluirGrade';
								$this->browser->post['__EVENTVALIDATION'] = ($this->browser->getField('id-input','__EVENTVALIDATION'));
								$this->browser->post['txtCPF'] = '';
							}
							$this->browser->referer = $url;
							$this->browser->useHttpBuildQuery = false;
							$this->browser->headers[] = 'Upgrade-Insecure-Requests: 1';
							$this->browser->post($url);

							$this->browser->referer = '';

							if (strpos($this->browser->html, 'Ocorreu um erro durante o processamento')!==false)
							{
								$sai = false;
								$this->errorCode = '605';
								$this->errorMessage = 'Erro ao remover agendamento';
								jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
							} else {

								$sai = true;
							}
						} else
						{
							// Remover aula teórica

							$elementos = $this->browser->getField('id-inc', 'rptCandidatos_ctl01_Label2', 'string', 17,2);
							$elSel = 0;
							foreach ($elementos as $key => $el)
							{
								if ($el == $cpf)
								{
									$elSel = $key+1;
								}
							}
							
							if ($elSel == 0) {
								// Tentando de outra forma... 
								$fim = strpos($this->browser->html, $cpf);
								if($fim !== false) {
									$html_piece = substr($this->browser->html, 0, $fim);  
									$ini = strrpos($html_piece,'<span id="rptCandidatos_ctl');
									
									if($ini !== false) {
										$span = substr( $html_piece, $ini, ($fim-$ini) ); // <span id="rptCandidatos_ctl01_Label2">
										$span_aux = explode("_", $span);
										
										$elSel = intval(str_replace("ctl", '', $span_aux[1]));
									}
								}
								
								if($elSel == 0 && $excluir_turma == 0) { // se for remoção de aluno da turma
									/*	
									 	Aborta a tentativa, pois esta não excluiria o aluno, assim como não retornaria a mensagem de erro no HTML. 
									 	O aluno não era excluído da turma na Valid, mas, por ausência de erros, era excluído na turma do WEB•CFC.
									 */
									$this->errorCode = '605';
									$this->errorMessage = 'Erro ao remover agendamento: aluno não localizado na turma';
									jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
									return false;  
								}
							}

							$this->browser->post['__EVENTTARGET'] = '';
							$this->browser->post['__EVENTARGUMENT'] = '';
							$this->browser->post['__VIEWSTATE'] = ($this->browser->getField('id-input','__VIEWSTATE'));
							if ($this->estado=='rj')
							{
								if($excluir_turma) {
									$this->browser->post['__EVENTTARGET'] = 'lkbExcluirGrade';
								}
								$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
								$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
								$this->browser->post['txtCPF'] = '';
								$this->browser->post['uf'] = strtoupper($this->estado);
								$this->browser->post['TxtRJ'] = '';
							} else
							{
								$this->browser->post['__EVENTVALIDATION'] = ($this->browser->getField('id-input','__EVENTVALIDATION'));
								$this->browser->post['txtCPF'] = '';
							}
							$this->browser->post['rptCandidatos$ctl'.str_pad($elSel,2,'0',STR_PAD_LEFT).'$imbExcluir.x'] = '10';
							$this->browser->post['rptCandidatos$ctl'.str_pad($elSel,2,'0',STR_PAD_LEFT).'$imbExcluir.y'] = '6';
							$this->browser->referer = $url;
							$this->browser->useHttpBuildQuery = false;
							$this->browser->headers[] = 'Upgrade-Insecure-Requests: 1';
							$this->browser->post($url);

							$this->browser->referer = '';

							if (strpos($this->browser->html, 'Ocorreu um erro durante o processamento')!==false)
							{
								$sai = false;
								$this->errorCode = '605';
								$this->errorMessage = 'Erro ao remover agendamento';
								jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
							} else {

								$sai = true;
							}
						}

					} else {
						$sai = false;
						$this->errorCode = '606';
						$this->errorMessage = 'Erro ao remover agendamento: Horário/disciplina/instrutor não existe!';
						jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
					}


				}
				break;
		}
		return($sai);

	}

	function processaAulasPorPeriodo($id, $placa = "")
	{
		$sai = true;
		jLog('AUL Processando aulas por período', $this->browser->session, 'aviso');
		switch ($this->estado)
		{
			case 'rj':
			case 'ba':
			case 'ce':

				// Obtém dados do cabeçalho
				$estaAula['cfc'] = $this->browser->format($this->browser->htmlObject->getElementById('lblCFC')->nodeValue);
				$estaAula['curso'] = str_replace('"','',str_replace("'",'',$this->browser->format($this->browser->htmlObject->getElementById('lblCurso')->nodeValue)));
				$estaAula['servico'] = str_replace('"','',str_replace("'",'',$this->browser->format($this->browser->htmlObject->getElementById('lblServico')->nodeValue)));
				$estaAula['data'] = $this->browser->format($this->browser->htmlObject->getElementById('lblData')->nodeValue,'date');
				$estaAula['hora'] = $this->browser->format($this->browser->htmlObject->getElementById('lblHorario')->nodeValue);
				$estaAula['data_hora'] = $estaAula['data'].' '.substr($estaAula['hora'],0,5).':00';
				$estaAula['instrutor_cpf'] = $this->browser->format($this->browser->htmlObject->getElementById('lblCPFInstrutor')->nodeValue);
				$estaAula['instrutor_nome'] = str_replace('"','',str_replace("'",'',$this->browser->format($this->browser->htmlObject->getElementById('lblNomeInstrutor')->nodeValue)));
				$estaAula['veiculo'] = str_replace('"','',str_replace("'",'',$this->browser->format($this->browser->htmlObject->getElementById('lblVeiculo')->nodeValue)));
				$veiculo = explode(' ', $estaAula['veiculo']);
				$estaAula['placa'] = $veiculo[count($veiculo)-1];
				if ($estaAula['placa'] == '')
					$estaAula['placa'] = $placa;
				// Calculando a quantidade de aulas de acordo com o horário de inicio e fim
				$horario = $this->browser->format($this->browser->htmlObject->getElementById('lblHorario')->nodeValue);
				$horarios = explode(' - ', $horario);
				$minutosInicio = substr($horarios[0],0,2)*60+substr($horarios[0],3,2);
				$minutosFinal = substr($horarios[count($horarios)-1],0,2)*60+substr($horarios[count($horarios)-1],3,2);
				$ttlAulas = intval(($minutosFinal-$minutosInicio)/DURACAO_AULA_NOTURNA);

				$estaAula['aulas'] = $ttlAulas;

				//echo "Data: ".$estaAula['data']." - horario: $horario - ttlAulas: $ttlAulas<br>";

				$table = $this->browser->htmlObject->getElementById('tblCandidatos');
				if (is_object($table))
				{
					$tr = $table->getElementsByTagName('tr');

					$fez = false;
					$cnt = 1;
					for ($i = 0; $i < $tr->length; $i++) {
						$span = $tr->item($i)->getElementsByTagName('span');
						if (is_object($span))
						{
							$aluno = '';
							// Dados da aula
							for ($j = 0; $j < $span->length; $j++)
							{
								$idSpan = $span->item($j)->getAttribute('id');
								if ($idSpan == 'rptCandidatos_ctl'.str_pad($cnt, 2, '0',STR_PAD_LEFT).'_lblCPF')
								{
									$aluno['cpf'] = $span->item($j)->nodeValue;
								}
								if ($idSpan == 'rptCandidatos_ctl'.str_pad($cnt, 2, '0',STR_PAD_LEFT).'_lblRenach')
								{
									$aluno['renach'] = $span->item($j)->nodeValue;
								}
								if ($idSpan == 'rptCandidatos_ctl'.str_pad($cnt, 2, '0',STR_PAD_LEFT).'_lblNome')
								{
									$aluno['nome'] = str_replace('"','',str_replace("'",'',$span->item($j)->nodeValue));
								}
							}
							// Presença do aluno
							if (is_array($aluno))
							{
								$table2 = $tr->item($i)->getElementsByTagName('table');

								if (is_object($table2))
								{
									$p1 = $p2 = $p3 = '';
									$aluno['presenca_entrada'] = '0000-00-00 00:00:00';
									$aluno['presenca_intervalo'] = '0000-00-00 00:00:00';
									$aluno['presenca_saida'] = '0000-00-00 00:00:00';
									if ($this->estado == 'ba')
									{
										$p1 = substr($this->browser->format($this->browser->htmlObject->getElementById('rptCandidatos_ctl'.str_pad($cnt, 2, '0',STR_PAD_LEFT).'_imgInicioCandidato')->getAttribute('title')),11,5);
										if ($this->tipo=='T')
										{
											$ptmp = $this->browser->htmlObject->getElementById('rptCandidatos_ctl'.str_pad($cnt, 2, '0',STR_PAD_LEFT).'_imgIntervaloCandidato');
											if (is_object($ptmp))
												$p2 = substr($this->browser->format($ptmp->getAttribute('title')),11,5);
										}
										$p3 = substr($this->browser->format($this->browser->htmlObject->getElementById('rptCandidatos_ctl'.str_pad($cnt, 2, '0',STR_PAD_LEFT).'_imgSaidaCandidato')->getAttribute('title')),11,5);
									} else {
										$td = $table2->item($table2->length>1 ? 2: 0)->getElementsByTagName('td');
										$p1 = $this->browser->format($td->item(0)->nodeValue);
										$p3 = $this->browser->format($td->item(2)->nodeValue);
									}

									if (!is_numeric(substr($p1,0,2)))
										$p1 = '';
									if (!is_numeric(substr($p2,0,2)))
										$p2 = '';
									if (!is_numeric(substr($p3,0,2)))
										$p3 = '';

									if ($p1<>'')
										$aluno['presenca_entrada'] = $estaAula['data'].' '.$p1;
									if ($p2<>'')
										$aluno['presenca_intervalo'] = $estaAula['data'].' '.$p2;
									if ($p3<>'')
										$aluno['presenca_saida'] = $estaAula['data'].' '.$p3;

									$aulaPresente = true;
									// Qualquer ausência de digital, significa que o aluno faltou
									if ($ttlAulas<=3)
									{
										// Não tem intervalo
										if ($aluno['presenca_entrada'] == '0000-00-00 00:00:00' || $aluno['presenca_saida'] == '0000-00-00 00:00:00')
										{
											$aulaPresente = false;
										}
									} else
									{
										// Tem intervalo
										if ($aluno['presenca_entrada'] == '0000-00-00 00:00:00' || $aluno['presenca_saida'] == '0000-00-00 00:00:00' || $aluno['presenca_intervalo'] == '0000-00-00 00:00:00')
										{
											$aulaPresente = false;
										}
									}
								}

								$aulaData = $estaAula['data'].' '.$horarios[0].':00';
								for ($a=0; $a<$ttlAulas; $a++)
								{
									$aulaDataFinal = date('Y-m-d H:i', strtotime('+'.calculaDuracao($aulaData).' minute', strtotime($aulaData))).':00';
									$aluno['data'] = $aulaData;
									if ($aulaPresente)
									{
										if ($a==0) // primeira aula, então a presença é o horário real registrado pelo aluno
										{
											// Caso não tenha sido feito o registro pela biometria, mas tenha sido aceito (por telemetria)
											if ($p1 == 'Presença não registrada')
												$aluno['presenca'] = $aulaData;
											else
												$aluno['presenca'] = substr($aulaData,0,11).' '.$p1.':00';
										}
										else
											$aluno['presenca'] = $aulaData;
										$aluno['hora_entrada'] = substr($aluno['presenca'], 11,5);
										// Última aula, a hora de saída será a registrada pela biometria
										if ($a == $ttlAulas-1)
											// Caso não tenha sido feito o registro pela biometria, mas tenha sido aceito (por telemetria)
											if ($p3 == 'Presença não registrada')
												$aluno['hora_saida'] = substr($aulaDataFinal, 11,5);
											else
											{
												$aluno['hora_saida'] = $p3;

											}
										else
											$aluno['hora_saida'] = substr($aulaDataFinal, 11,5);
									} else
									{
										// Caso tenha QUALQUER um dos indicadores de falta, será falta para todas as aulas
										$aluno['presenca'] = '0000-00-00 00:00:00';
										$aluno['hora_entrada'] = '';
										$aluno['hora_saida'] = '';
									}
									$aulaData = $aulaDataFinal;
									$estaAula['alunos'][]=$aluno;
								}
								$fez = true;
								$cnt++;
							}
						}
					}
				} else {
					$sai = false;
					$this->errorCode = '602';
					$this->errorMessage = 'Erro na interpretação dos dados HTML. Tabela não encontrada.';
					jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');

				}
				if ($fez)
				{
					$this->aulas[]=$estaAula;
				}
				break;
		}
		return($sai);
	}

	/**
	 * Busca todas as aulas de um determinado período e retorna no atributo $this->aulas
	 * @param  string $json  JSON com parâmetros data_inicio e data_final
	 * @return boolean        True/False (sucesso/falha)
	 */
	function buscaAulasPorPeriodo($json)
	{
		$sai = false;
		if (is_array($data))
			$data = $data[0];
		$jarr = cssDecode($json);
		$dataInicio = $jarr['data_inicio'];
		$dataFinal = $jarr['data_final'];
		$this->tipo = $jarr['tipo'];

		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':

				jLog('AGE Buscando aulas por período...', $this->browser->session, 'aviso');
				if ($this->login())
				{
					// Fase 1
					$this->browser->get($this->urls[$this->estado]['aulas_periodo_0']);
					$this->browser->getHtmlObject(true);

					$dataInicioBR = date('d/m/Y', strtotime($dataInicio));
					$dataFinalBR = date('d/m/Y', strtotime($dataFinal));

					$this->browser->post['CFCMaster$cphBody$btnConsultar'] = 'Consultar';
					$this->browser->post['CFCMaster$cphBody$ddlInstrutor'] = '0';
					$this->browser->post['CFCMaster$cphBody$rblTipoCurso'] = $this->tipo;
					$this->browser->post['CFCMaster$cphBody$txtInicio'] = $dataInicioBR;
					$this->browser->post['CFCMaster$cphBody$txtTermino'] = $dataFinalBR;

					$this->browser->post['__EVENTARGUMENT'] = '';
					$this->browser->post['__EVENTTARGET'] = '';
					$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
					$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
					$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');

					jLog("AGE Buscando aulas por período", $this->browser->session, 'aviso');
					$this->browser->referer = $this->urls[$this->estado]['aulas_periodo_0'];
					$this->browser->post($this->urls[$this->estado]['aulas_periodo_0']);
					$this->browser->getHtmlObject(true);
					//$this->browser->getHtmlObject(true);
					//
					preg_match_all('/(ExibirCandidatos).*/i', $this->browser->html, $matches);

					$ids = '';
					$placas = '';
					$cnt=0;
					foreach ($matches[0] as $match)
					{
						$id = substr($match, strpos($match, '(')+1);
						$id = substr($id, 0, strpos($id, ')'));
						if (is_numeric($id))
						{
							$cnt++;
							$placas[] = $this->browser->getField('id','CFCMaster_cphBody_rptAulas_ctl'.str_pad($cnt,2,'0', STR_PAD_LEFT).'_tdVeiculo');
							$ids[] = $id;
						}
					}
					if (is_array($ids))
					{
						$sai = true;
						foreach ($ids as $key=>$id)
						{
							$this->browser->get($this->urls[$this->estado]['aulas_periodo_1'].'?ID='.$id);
							$this->browser->getHtmlObject(true);
							$sai = $this->processaAulasPorPeriodo($id, $placas[$key]);
						}

					} else  {
						$sai = false;
						$this->errorCode = '607';
						$this->errorMessage = "Nenhum agendamento no período solicitado: $dataInicioBR a $dataFinalBR";
						jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');

					}

				}
				break;
		}
		return($sai);
	}

	function processaTurmaPorPeriodo()
	{
		$sai = true;
		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':
				$table = $this->browser->htmlObject->getElementsByTagName('table');
				$this->aulas = array();
				if (is_object($table))
				{
					// Processa registros
					// $tr = $table->item(6)->getElementsByTagName('tr');
					$tr = $table->item(5)->getElementsByTagName('tr');
					for ($i = 1; $i < $tr->length; $i++) {
						$td = $tr->item($i)->getElementsByTagName('td');

						$estaAula['data'] = str_replace('"','',str_replace("'",'',$this->browser->format($td->item(0)->nodeValue,'date')));
						$estaAula['curso'] = str_replace('"','',str_replace("'",'',$this->browser->format($td->item(3)->nodeValue)));
						$estaAula['servico'] = str_replace('"','',str_replace("'",'',$this->browser->format($td->item(2)->nodeValue)));
						$estaAula['instrutor_nome'] = str_replace('"','',str_replace("'",'',$this->browser->format($td->item(6)->nodeValue)));
						// $estaAula['horario'] = str_replace('"','',str_replace("'",'',$this->browser->format($td->item(1)->nodeValue)));
						$estaAula['horario'] = substr($this->browser->format($td->item(1)->nodeValue),5,13);
						$horarios = explode(' - ', $estaAula['horario']);
						$minutosInicio = substr($horarios[0],0,2)*60+substr($horarios[0],3,2);
						$minutosFinal = substr($horarios[count($horarios)-1],0,2)*60+substr($horarios[count($horarios)-1],3,2);
						$ttlAulas = intval(($minutosFinal-$minutosInicio)/DURACAO_AULA_NOTURNA);
						$estaAula['aulas'] = $ttlAulas;

						$aulaData = $estaAula['data'].' '.$horarios[0].':00';
						for ($a=0; $a<$ttlAulas; $a++)
						{
							$aulaDataFinal = date('Y-m-d H:i', strtotime('+'.calculaDuracao($aulaData).' minute', strtotime($aulaData))).':00';
							$estaAula['data_hora'] = $aulaData;
							$aulaData = $aulaDataFinal;
							$this->aulas[]=$estaAula;
						}
					}
					$sai = true;
				} else {
					$sai = false;
					$this->errorCode = '602';
					$this->errorMessage = 'Erro na interpretação dos dados HTML. Tabela não encontrada.';
					jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');

				}
				break;
		}
		return($sai);
	}


	/**
	 * Busca todas as aulas de um determinado período e retorna no atributo $this->aulas
	 * @param  string $json  JSON com parâmetros data_inicio e data_final
	 * @return boolean        True/False (sucesso/falha)
	 */
	function buscaTurmaPorPeriodo($json)
	{
		$sai = false;
		if (is_array($data))
			$data = $data[0];
		$jarr = cssDecode($json);
		$dataInicio = $jarr['data_inicio'];
		$dataFinal = $jarr['data_final'];
		$this->tipo = $jarr['tipo'];

		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':

				jLog('AGE Buscando turma por período...', $this->browser->session, 'aviso');
				if ($this->login())
				{
					// Fase 1
					$this->browser->get($this->urls[$this->estado]['aulas_periodo_0']);
					$this->browser->getHtmlObject(true);

					$dataInicioBR = date('d/m/Y', strtotime($dataInicio));
					$dataFinalBR = date('d/m/Y', strtotime($dataFinal));

					$this->browser->post['CFCMaster$cphBody$btnConsultar'] = 'Consultar';
					$this->browser->post['CFCMaster$cphBody$ddlInstrutor'] = '0';
					$this->browser->post['CFCMaster$cphBody$rblTipoCurso'] = $this->tipo;
					$this->browser->post['CFCMaster$cphBody$txtInicio'] = $dataInicioBR;
					$this->browser->post['CFCMaster$cphBody$txtTermino'] = $dataFinalBR;

					$this->browser->post['__EVENTARGUMENT'] = '';
					$this->browser->post['__EVENTTARGET'] = '';
					$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
					$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
					$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');

					jLog("AGE Buscando aulas por período", $this->browser->session, 'aviso');
					$this->browser->referer = $this->urls[$this->estado]['aulas_periodo_0'];
					$this->browser->post($this->urls[$this->estado]['aulas_periodo_0']);
					$this->browser->getHtmlObject(true);

					$sai = $this->processaTurmaPorPeriodo();
				}
				break;
		}
		return($sai);
	}








	function editaAgendamento($json, $data, $tipo = 'P')
	{
		$sai = false;
		if (is_array($data))
			$data = $data[0];
		$jarr = cssDecode($json);
		$dataInicio = $data;
		$dataTermino = $data;
		$cpf = gToNumbers($jarr['cpf']);
		$renach = gToNumbers($jarr['renach']);
		$id_instrutor = $jarr['id_instrutor'];
		$id_curso = $jarr['id_curso'];
		$id_sala = $jarr['id_sala'];
		$id_servico = $jarr['id_servico'];
		$uf = strtoupper($this->estado);
		$id_veiculo = $jarr['id_veiculo'];
		$placa = $jarr['placa'];
		$servico = $jarr['servico'];
		$categoria = $jarr['categoria'];
		$para_hora = $jarr['para_hora'];
		$para_instrutor = $jarr['para_instrutor'];
		$para_veiculo = $jarr['para_veiculo'];
		$p_t = ($placa<>'') ? 'P' : 'T';

		switch ($this->estado)
		{
			case 'ba':
			case 'ce':
			case 'rj':

				jLog('AGE Editando agendamento...', $this->browser->session, 'aviso');
				if ($this->login())
				{
					$dataBR = date('d/m/Y', strtotime($dataInicio));
					$horaInicio = substr($dataInicio,11,5);
					$horaTermino = substr(date('Y-m-d gi:s', strtotime('+50 minute', strtotime($dataTermino))),11,5);
					$periodo = ($horaInicio<'12:00') ? 'M' : ($horaInicio>=HORA_LIMITE_AULAS_50_MINUTOS ? 'N' : 'T');
					// Fase 1
					//$this->browser->referer = 'https://cfcweb.detran.rj.gov.br/principal.aspx?sis=1';
					$this->browser->get($this->urls[$this->estado]['agenda_grade']);
					$this->browser->getHtmlObject(true);
					preg_match_all('/(CombinedScripts).*/i', $this->browser->html, $matches);

					foreach ($matches[0] as $match)
					{
						$campos = explode('"', $match);
						$campos = explode('=', $campos[0]);
						$CFCMaster_cphBody_ToolkitScriptManager1_HiddenField = urldecode($campos[1]);
					}
					$this->browser->post['CFCMaster$cphBody$Acord_AccordionExtender_ClientState'] = '1';
					$this->browser->post['CFCMaster$cphBody$btnAgendamento'] = 'Button';
					$this->browser->post['CFCMaster$cphBody$hdiData'] = $dataBR;
					$this->browser->post['CFCMaster$cphBody$hdiPeriodo'] = $periodo;
					$this->browser->post['CFCMaster$cphBody$hdiServi'] = $id_servico.'_'.$p_t.$id_curso.',';
					$this->browser->post['CFCMaster_cphBody_ToolkitScriptManager1_HiddenField'] = $CFCMaster_cphBody_ToolkitScriptManager1_HiddenField;
					$this->browser->post['DataServico'] = $dataBR;
					$this->browser->post['chkServico'] = 'on';
					if ($this->estado=='ba')
					{
						$this->browser->post['Periodo1'] = 'on';
						$this->browser->post['Periodo5'] = 'on';
						$this->browser->post['Periodo6'] = 'on';
						$this->browser->post['Periodo7'] = 'on';
						$this->browser->post['Periodo9'] = 'on';
						$this->browser->post['PeriodoX'] = 'on';
					}
					$this->browser->post['__EVENTARGUMENT'] = '';
					$this->browser->post['__EVENTTARGET'] = '';
					$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
					$this->browser->post['__VIEWSTATE'] = $this->browser->getField('id-input','__VIEWSTATE');
					$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');

					jLog("AGE Editando agendamento > CPF: $cpf RENACH: $renach", $this->browser->session, 'aviso');
					$this->browser->referer = $this->urls[$this->estado]['agenda_grade'];
					$this->browser->post($this->urls[$this->estado]['agenda_grade']);
					$this->browser->getHtmlObject(true);

					// Busca Id do elemento a excluir...

					$matches = "";
					$fez = false;
					$idGradeCurso = '';
					$idInstrutor = '';
					$horaInicioInt = intval(str_replace( ':','', $horaInicio));

					preg_match_all('/(_dados).*/i', $this->browser->html, $matches);

					foreach ($matches[0] as $match)
					{
						$campos = explode('"', $match);
						if (count($campos)>1)
						{
							//_dados["a6052632_25851"] = ["T", "600", "0", "650", "JEAN CARLO BAZANI DOS SANTOS", "", "0", "", "Renovação", "1", "T"];
							//_dados["a5813408_25851"] = ["P", "1030", "0", "1120", "JEAN CARLO BAZANI DOS SANTOS", "LEANDRO FERREIRA COSTA", "1", "LRW3466", "Primeira Habilitação","1","T"];
							$campoIds = $campos[1];$campoIds = explode('_', $campoIds);
							$campoTipo = $campos[3];
							$campoHora = $campos[5];
							$campoInst = $campos[11];
							$campoAlun = $campos[13];
							$campoPlac = $campos[17];
							$campoServ = $campos[19];
							$campoCurs = $campos[21];

							$campoServ = gToUpper(html_entity_decode($campoServ));

							jLog("Checando H:$horaInicioInt I:$id_instrutor S:$servico C:$id_curso P:$placa = [".$campoHora."] [".$campoInst."] [".$campoServ."] [".$campoCurs."] [".$campoPlac."]", $this->browser->session);
							if ($campoTipo == $p_t)
							{
								if ($p_t == 'T')
								{
									// Teórico
									if ($campoHora == $horaInicioInt && $campoServ == $servico && $campoCurs == $id_curso)
									{
										$idGradeCurso = substr($campoIds[0],1);
										$idInstrutor = $campoIds[1];
										jLog("Checando novamente $idInstrutor = $id_instrutor", $this->browser->session);
										if ($idInstrutor == $id_instrutor)
										{
											$fez = true;
											jLog("Encontrado!", $this->browser->session);
										}
									}
								} else {
									// Prático
									if(intval($jarr['simulador']) > 0 ) {
										$placa = "";
									}
									if ($campoPlac == $placa && $campoHora == $horaInicioInt && $campoServ == $servico )
									{
										$idGradeCurso = substr($campoIds[0],1);
										$idInstrutor = $campoIds[1];
										jLog("Checando novamente $idInstrutor = $id_instrutor", $this->browser->session);
										if ($idInstrutor == $id_instrutor)
										{
											$fez = true;
											jLog("Encontrado!", $this->browser->session);
										}
									}
								}
							}
						}
					}

					if ($fez)
					{
						// Fase 2 - Obtém janela de horários e define o Renach e CPF
						$url1 = $this->urls[$this->estado]['agenda_incluir_2']."?idGradeCurso=".$idGradeCurso."&tpCurso=".$p_t."&idInstrutor=".$id_instrutor;
						$url2 = $this->urls[$this->estado]['agenda_incluir']."?idGradeCurso=".$idGradeCurso."&tpCurso=".$p_t."&idInstrutor=".$id_instrutor;
						if ($para_hora<>'' && $this->estado<>'rj')
							$url = $url1;
						else
							$url = $url2;
						$this->browser->referer = $url;
						$this->browser->get($url);
						$this->browser->getHtmlObject(true);

						if ($p_t == 'P')
						{
							// Editar aula prática

							if ($para_hora=='')
							{
								if ($this->estado <> 'ba' && $para_instrutor>0)
								{
									$this->browser->post['__EVENTTARGET'] = 'lkbAlterarInstrutor';
									$this->browser->post['__EVENTARGUMENT'] = '';
									$this->browser->post['__VIEWSTATE'] = ($this->browser->getField('id-input','__VIEWSTATE'));
									$this->browser->post['__EVENTVALIDATION'] = ($this->browser->getField('id-input','__EVENTVALIDATION'));
									if ($this->estado=='rj')
									{
										$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
										$this->browser->post['uf'] = 'RJ';
										$this->browser->post['TxtRJ'] = '';
										$this->browser->post['rptCandidatos$ctl01$imbAlterarInstrutor.x'] = '11';
										$this->browser->post['rptCandidatos$ctl01$imbAlterarInstrutor.y'] = '9';
									}

								} else
								{
									$this->browser->post['__EVENTTARGET'] = '';
									$this->browser->post['__EVENTARGUMENT'] = '';
									$this->browser->post['__VIEWSTATE'] = ($this->browser->getField('id-input','__VIEWSTATE'));
									if ($this->estado=='rj')
									{
										$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
										$this->browser->post['uf'] = 'RJ';
										$this->browser->post['TxtRJ'] = '';
									}
									$this->browser->post['__EVENTVALIDATION'] = ($this->browser->getField('id-input','__EVENTVALIDATION'));
									if ($para_veiculo>0)
									{
										$this->browser->post['rptCandidatos$ctl01$imbAlterarVeiculo.x'] = '10';
										$this->browser->post['rptCandidatos$ctl01$imbAlterarVeiculo.y'] = '9';
									}
									if ($para_instrutor>0)
									{
										$this->browser->post['rptCandidatos$ctl01$imbAlterarInstrutor.x'] = '11';
										$this->browser->post['rptCandidatos$ctl01$imbAlterarInstrutor.y'] = '9';
									}

								}
								$this->browser->post['txtCPF'] = '';
								$this->browser->referer = $url;
								$this->browser->useHttpBuildQuery = false;
								$this->browser->headers[] = 'Upgrade-Insecure-Requests: 1';

								$this->browser->post($url);
								$this->browser->getHtmlObject(true);
							} else
							{
								if ($this->estado=='rj')
								{
									$this->browser->post['__EVENTTARGET'] = 'lkbAlterarHorario';
									$this->browser->post['__EVENTARGUMENT'] = '';
									$this->browser->post['__VIEWSTATE'] = ($this->browser->getField('id-input','__VIEWSTATE'));
									$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
									$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
									$this->browser->post['TxtRJ'] = '';
									$this->browser->post['txtCPF'] = '';
									$this->browser->post['uf'] = 'RJ';
									$this->browser->referer = $url;
									$this->browser->useHttpBuildQuery = false;
									$this->browser->headers[] = 'Upgrade-Insecure-Requests: 1';
									$this->browser->post($url2);
									$this->browser->getHtmlObject(true);

									$this->browser->referer = $url2;
									$this->browser->useHttpBuildQuery = true;
									$this->browser->get($url1);
									$this->browser->getHtmlObject(true);
									$url = $url1;
								}
							}


							//$this->browser->post['btnConsultarCandidato'] = 'Consultar';
							$this->browser->post['__EVENTTARGET'] = '';
							$this->browser->post['__EVENTARGUMENT'] = '';
							$this->browser->post['__VIEWSTATE'] = ($this->browser->getField('id-input','__VIEWSTATE'));
							if ($this->estado=='rj')
							{
								$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
							}
							$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
							if ($para_hora<>'')
							{
								$this->browser->post['btnAlterarHorario'] = 'Alterar Horário';
								$this->browser->post['txtHoraInicio'] = $para_hora;
								if ($this->estado == 'ba')
									$this->browser->post['txtHoraIntervalo'] = '';
								$this->browser->post['txtHoraTermino'] = date('H:i', strtotime('+'.calculaDuracao($para_hora).' minute', strtotime($para_hora)));
							} elseif ($para_instrutor>0)
							{
								$this->browser->post['btnAlterarInstrutor'] = 'Alterar';
								$this->browser->post['ddlInstrutor'] = $para_instrutor;

							} elseif ($para_veiculo>0)
							{
								$this->browser->post['btnAlterarVeiculo'] = 'Alterar';
								$this->browser->post['ddlTrocaVeiculo'] = $para_veiculo;
							}
							$this->browser->referer = $url;
							$this->browser->useHttpBuildQuery = false;
							$this->browser->headers[] = 'Upgrade-Insecure-Requests: 1';

							$this->browser->post($url);
							$this->browser->getHtmlObject(true);
							$this->browser->referer = '';

							$lblMensagem = $this->browser->getField('id','lblMensagem');
							$lblErroVeiculo = $this->browser->getField('id','lblErroVeiculo');
							$lblErroInstrutor = $this->browser->getField('id','lblErroInstrutor');
							if (strpos($this->browser->html,'Ocorreu um erro durante o processamento')===false && (strpos($this->browser->html, 'Grade foi alterada com sucesso')!==false || $lblMensagem.$lblErroVeiculo.$lblErroInstrutor == ''))
							{
								$sai = true;
							} else {
								$sai = false;
								$this->errorCode = '606';
								$this->errorMessage = str_replace('.','.<br>',"[".$lblMensagem.$lblErroVeiculo.$lblErroInstrutor."]");
								jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
							}
						} else
						{
							// Editar aula teórica

							$elementos = $this->browser->getField('id-inc', 'rptCandidatos_ctl01_Label2', 'string', 17,2);
							$elSel = 0;
							foreach ($elementos as $key => $el)
							{
								if ($el == $cpf)
								{
									$elSel = $key+1;
								}
							}

							$this->browser->post['__EVENTTARGET'] = '';
							$this->browser->post['__EVENTARGUMENT'] = '';
							$this->browser->post['__VIEWSTATE'] = ($this->browser->getField('id-input','__VIEWSTATE'));
							if ($this->estado=='rj')
							{
								$this->browser->post['__VIEWSTATEGENERATOR'] = $this->browser->getField('id-input','__VIEWSTATEGENERATOR');
								$this->browser->post['__EVENTVALIDATION'] = $this->browser->getField('id-input','__EVENTVALIDATION');
								$this->browser->post['txtCPF'] = '';
								$this->browser->post['uf'] = strtoupper($this->estado);
								$this->browser->post['TxtRJ'] = '';
							} else
							{
								$this->browser->post['__EVENTVALIDATION'] = ($this->browser->getField('id-input','__EVENTVALIDATION'));
								$this->browser->post['txtCPF'] = '';
							}
							$this->browser->post['rptCandidatos$ctl'.str_pad($elSel,2,'0',STR_PAD_LEFT).'$imbExcluir.x'] = '10';
							$this->browser->post['rptCandidatos$ctl'.str_pad($elSel,2,'0',STR_PAD_LEFT).'$imbExcluir.y'] = '6';
							$this->browser->referer = $url;
							$this->browser->useHttpBuildQuery = false;
							$this->browser->headers[] = 'Upgrade-Insecure-Requests: 1';
							$this->browser->post($url);

							$this->browser->referer = '';

							if (strpos($this->browser->html, 'Ocorreu um erro durante o processamento')!==false)
							{
								$sai = false;
								$this->errorCode = '605';
								$this->errorMessage = 'Erro ao remover agendamento';
								jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
							} else {

								$sai = true;
							}
						}

					} else {
						$sai = false;
						$this->errorCode = '606';
						$this->errorMessage = 'Erro ao remover agendamento: Horário/disciplina/instrutor não existe!';
						jLog($this->errorCode.' '.$this->errorMessage, $this->browser->session, 'error');
					}


				}
				break;
		}
		return($sai);

	}





}


 ?>
