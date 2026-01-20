<?php

include __DIR__ . "/res/classes.php";

$html .= $o->msgTitle("Mensagens");
$agora = date('Y-m-d H:i:s');
switch ($gPage) {

	case 0:
		# Mostra mensagens não lidas e pesquisa
		$caixa=(int)$_REQUEST['caixa'];

		$diasSemana=[];
		$diasSemana[]='Domingo';
		$diasSemana[]='Segunda';
		$diasSemana[]='Terça';
		$diasSemana[]='Quarta';
		$diasSemana[]='Quinta';
		$diasSemana[]='Sexta';
		$diasSemana[]='Sábado';
		$html.=$o->button("{icon: plus; style: info; title: Nova; href: ".$o->page."&gPage=1}");
		$html.=$o->button("{icon: search; style: default; title: Pesquisar; href: ".$o->page."&gPage=4}");
		if ($caixa !== 0) {
			$html.=$o->button("{icon: inbox-in; style: default; title: Entrada; href: ".$o->page."&gPage=0&caixa=0}");
			$html.=$o->button("{icon: inbox-out; active: true; style: default; title: Saída; href: ".$o->page."&gPage=0&caixa=1}");
		} else {
			$html.=$o->button("{icon: inbox-in; active: true; style: default; title: Entrada; href: ".$o->page."&gPage=0&caixa=0}");
			$html.=$o->button("{icon: inbox-out; style: default; title: Saída; href: ".$o->page."&gPage=0&caixa=1}");
		}

		$html .= '<br><br>';

		$where = '';
		if (
			$_REQUEST['de'] > 0
			|| $_REQUEST['para'] > 0
			|| $_REQUEST['mensagem'] != ''
			|| $_REQUEST['data_de'] != ''
			|| $_REQUEST['data_ate'] != ''
		) {
			$flt = [];
			if ($de > 0) {
				$flt[] = 'id_pessoas_de='.$de;
			}

			if ($para > 0) {
				$flt[] = 'id_pessoas_para='.$para;
			}

			if ($data_de > 0) {
				$flt[] = "data>='".gDBDate($data_de)."'";
			}

			if ($data_ate > 0) {
				$flt[] = "data<='".gDBDate($data_ate)."'";
			}

			if ($mensagem != '') {
				$flt[] = "mensagem like '%".$mensagem."%'";
			}

			$where = "AND ".implode(' AND ',$flt);
		}

		$tipoCaixa = "(id_pessoas_para=" . $usrId . ")";
  		if ($caixa) {
			$tipoCaixa = "(id_pessoas_de=" . $usrId . ")";
		}

		$sql = "SELECT m.*,
					CAST(AES_DECRYPT(UNHEX(p.nome),'".$AESKEY."') AS CHAR(150)) para,
					CAST(AES_DECRYPT(UNHEX(d.nome),'".$AESKEY."') AS CHAR(150)) de
				FROM mensagens m
				LEFT JOIN pessoas d ON m.id_pessoas_de=d.id
				LEFT JOIN pessoas p ON m.id_pessoas_para=p.id
				WHERE " . $tipoCaixa . " AND
					(data>=DATE_SUB('{$agora}', INTERVAL 30 DAY) OR lida=0)
					".$where."
				ORDER BY m.data DESC";
		$rs = dbQuery($sql);
		if ($rs) {
			$html.="Mensagens lidas com mais de 30 dias não são exibidas. Use a opção persquisar caso queira consultá-las.<BR><BR>";
			$html.=$o->tableBegin('big', true);
			$mtz = [];
			$mtz[] = '->Id';
			if (!$caixa) {
				$mtz[]='<-Opções';
			}

			$mtz[]="<-Tipo";
			$mtz[]='<-Mensagem';
			$mtz[]='<>Data';
   			if ($caixa) {
				$mtz[]='<-Para';
			} else {
				$mtz[]='<-De';
			}

			$mtz[] = '<>Lida';
			$mtz[] = '<>Resp.';
			$html .= $o->tableRow($mtz, 'header');
			foreach ($rs as $row) {
				$cor = 'detail';
				if ($row['parametros'] != '') {
					$cor='danger';
				}

				$mtz = [];
				$mtz[] = '->'.$row['id'];
				$btns = '';
				if ($row['lida'] == 1) {
					$cor = 'active';
				} elseif ($row['id_pessoas_para']==$usrId) {
					$btns .= $o->button("{icon: check; title: Lida; hint: Marcar mensagem como lida; size: small; href: ".$o->page."&gPage=3&gId=".$row['id']."}");
				}

				if ($row['respondida'] == 0 && $row['id_pessoas_para'] == $usrId && $row['tipo'] > 0) {
					$btns.=$o->button("{icon: reply; title: Responder; hint: Responder mensagem ao remetente; size: small; href: ".$o->page."&gPage=5&gId=".$row['id']."}");
				}

				if (!$caixa) {
					$mtz[]='<-'.$btns;
				}

				$mtz[] = match ($row['tipo']) {
					1 => "<-Desconto",
					2 => "<-Aprovação",
					default => "<-Normal",
				};

				$mtz[] = '<-'.nl2br((string) $row['mensagem']);
				$mtz[] = '<>'.gDateTime($row['data']).$o->small('<br>'.$diasSemana[date('w', strtotime((string) $row['data']))]);

    			if ($caixa) {
					$mtz[]='<-'.$row['para'];
				} else {
					$mtz[]='<-'.$row['de'];
				}

				$mtz[]='<>'.gCheck($row['lida']);
				$mtz[]='<>'.gCheck($row['respondida']);
				$html.=$o->tableRow($mtz, $cor);
			}

			$html.=$o->tableEnd();
		} else {
			$html.=$o->msgInfo("Nenhuma mensagem por enquanto...");
		}

		break;

	case 1:
		# Nova mensagem
		$frm = new gForm("{columns: 1}");
		$frm->add("{name: de; type: show; value: ".$usrName."}");
		$frm->add("{name: para; type: combo; items: ".$sp['combo_funcionarios']."}");
		$frm->add("{name: mensagem; type: textarea; }");
		$frm->add("{name: gPage; type: hidden; value: 2; }");
		$frm->addFormMessage("Ao selecionar [* Indiferente] no campo do destinatário, a mensagem será encaminhada para toda a equipe.");
		$html.=$frm->render($o);
		break;

	case 2:
		if ((int) $_REQUEST['respondida'] == 1) {
			dbQuery("UPDATE mensagens SET lida = 1, respondida = 1 WHERE id = ".$gId);
		}

		$flds = '';
		$flds['id_pessoas_de'] = $usrId;
		$flds['id_pessoas_para'] = (int) $_REQUEST['para'];
		$flds['mensagem'] = gCleanField($_REQUEST['mensagem']);
		if (enviaMensagem(0,$flds)) {
			redirect($o->page."&gPage=0");
		} else {
			$html.=$o->msgDanger("A mensagem não pode ser enviada");
			$html.=$backButton;
		}

		break;

	case 3:
		# Marcar como Lida/Não lida
		$sql="UPDATE mensagens SET lida = 1-lida WHERE id_pessoas_para = " . $usrId . " AND id = " . $gId;
		dbQuery($sql);
		redirect($o->page."&gPage=0");
		break;

	case 4:
		# Pesquisar
		$html.=$o->msgSubTitle("Pesquisar");
		$frm = new gForm("{columns: 1}");
		$frm->row(
			$frm->add("{name: de; type: combo; items: ".$sp['combo_funcionarios']."}"),
			$frm->add("{name: para; type: combo; items: ".$sp['combo_funcionarios']."}")
		);
		$frm->row(
			$frm->add("{name: data_de; type: date}"),
			$frm->add("{name: data_ate; fieldLabel: Data até; type: date}")
		);
		$frm->row(
			$frm->add("{name: tipo; type: combo; items: {'Normal','Desconto','Aprovado', 'Negado'}; }"),
			$frm->add("{name: mensagem; type: text; }")
		);
		$frm->add("{name: gPage; type: hidden; value: 0; }");
		$html.=$frm->render($o);

	case 5:
		// Pra ter certeza que o usuário pode responder esta mensagem
		$sql="SELECT m.*,
					CAST(AES_DECRYPT(UNHEX(p.nome),'".$AESKEY."') AS CHAR(150)) para,
					CAST(AES_DECRYPT(UNHEX(d.nome),'".$AESKEY."') AS CHAR(150)) de
				FROM mensagens m
				LEFT JOIN pessoas d ON m.id_pessoas_de=d.id
				LEFT JOIN pessoas p ON m.id_pessoas_para=p.id
				WHERE m.id_pessoas_para=".$usrId." AND m.id=".$gId;
		$rs = dbQuery($sql);
		if ($rs) {
			switch ($rs[0]['tipo']) {
				case 1:
					$id_pessoas_aluno = $rs[0]['id_relacionado'];
					$valor = $rs[0]['parametros'];
					# Solicitação de desconto
					$sql = "SELECT CAST(AES_DECRYPT(UNHEX(nome),'".$AESKEY."') AS CHAR(150)) nome
							FROM pessoas
							WHERE id=".$id_pessoas_aluno;
					$rsa = dbQuery($sql);
					if ($rsa) {
						$html.=$o->msgSubTitle("Solicitação de desconto");
						$frm = new gForm("{columns: 2}");
						$frm->addFormMessage("Deixe o campo <b>valor solicitado</b> = 0 (zero) para reprovar o desconto.");
						$frm->add("{name: solicitado_por; type: show; value: ".$rs[0]['de']."}");
						$frm->add("{name: mensagem; type: show; value: ".nl2br((string) $rs[0]['mensagem'])."}");
						$frm->add("{name: valor_solicitado; type: number; value: ".gFloat($valor)."}");
						$frm->add("{name: metodo; fieldLabel: Método; allowBlank: false; type: combo; items: {'Diluir desconto em todas as parcelas', 'Aplicar somente na primeira parcela', 'Aplicar somente na última parcela'}}");
						$frm->add("{name: gPage; type: hidden; value: 6; }");
						$frm->add("{name: gId; type: hidden; value: ".$gId."; }");
						$html .= $frm->render($o);
						$html .= mostraCabecalhoAluno($id_pessoas_aluno, false);
						$html .= mostraFinanceiroAluno($id_pessoas_aluno);
						$html .= $o->msgSubTitle('Serviços x créditos do aluno');
						$html .= mostraCreditosAluno($id_pessoas_aluno);
					}

					break;

				default:
					# Resposta simples de mensagem
					$citacao = "&nbsp;\n\n---\nEm ".gDateTime($rs[0]['data'])." - ".$rs[0]['de'].":\n\n>".str_replace("\n","\n> ",$rs[0]['mensagem']);
					$frm = new gForm("{columns: 1}");
					$frm->row(
						$frm->add("{name: de; type: show; value: ".$usrName."}"),
						$frm->add("{name: em_resposta_a;type: show; value: ".$rs[0]['de']."}")
					);
					$frm->add("{name: para; type: hidden; value: ".$rs[0]['id_pessoas_de']."}");
					$frm->add("{name: mensagem; type: textarea; value: ".$citacao."}");
					$frm->add("{name: respondida; type: hidden; value: 1}");
					$frm->add("{name: gPage; type: hidden; value: 2; }");
					$html.=$frm->render($o);
			}

		} else {
			$html.="Esta mensagem não existe!";
		}

		break;

	case 6:
		# Libera desconto

		// Procura mensagem pra garantir a segurança e buscar alguns campos...
		$sql = "SELECT m.*,
					CAST(AES_DECRYPT(UNHEX(p.nome),'".$AESKEY."') AS CHAR(150)) para,
					CAST(AES_DECRYPT(UNHEX(d.nome),'".$AESKEY."') AS CHAR(150)) de,
					CAST(AES_DECRYPT(UNHEX(r.nome),'".$AESKEY."') AS CHAR(150)) aluno,
					r.apelido matricula
				FROM mensagens m
				LEFT JOIN pessoas d ON m.id_pessoas_de=d.id
				LEFT JOIN pessoas p ON m.id_pessoas_para=p.id
				LEFT JOIN pessoas r ON m.id_relacionado = r.id
				WHERE m.id_pessoas_para = " . $usrId . " AND m.id = " . $gId;
		$rs = dbQuery($sql);
		if ($rs) {

			$id_pessoas_aluno = $rs[0]['id_relacionado'];
			$valor = $rs[0]['parametros'];
			$valor_solicitado = floatval(gDBFloat($_REQUEST['valor_solicitado'])); // na verdade é o valor aprovado pois o usuário pode ter alterado
			if ($valor_solicitado > 0) {
				// Desconto aprovado
				$metodo = gCleanField($_REQUEST['metodo']);

				// Registra o desconto no financeiro
				$erros = [];
				switch ($metodo) {
					case 'Diluir desconto em todas as parcelas':

						$fez=false;
						if ($rs[0]['id_fin_lancamentos']) {
							$idLan=(int)$rs[0]['id_fin_lancamentos'];
							// Verifica se já houve algum pagamento efetivado...
							$sql="SELECT max(data_efetivacao) data_efetivacao, count(id) ttl FROM fin_parcelas WHERE valor > 0 AND id_fin_lancamentos=".$idLan;
							$rsPar=dbQuery($sql);

							// Só altera se está tudo ok...
							if ($rsPar[0]['data_efetivacao']=='0000-00-00') {
								$qtd = $rsPar[0]['ttl'];
								$diluir = (($valor_solicitado)/$qtd);
								$diluirUltima = $valor_solicitado-($diluir*$qtd);
								$sql="SELECT * FROM fin_parcelas WHERE valor > 0 AND id_fin_lancamentos=".$idLan." ORDER BY id";
								$rsPar = dbQuery($sql);
								$cnt=0;
								$valor_solicitado = 0;
								foreach ($rsPar as $par) {
									$cnt++;
									if (count($par) == $cnt) {
										$diluirUltima = $diluir;
									}

									if ($par['valor']-$diluir < 0) {
										$diluir = $par['valor'];
									}

									$valor_solicitado += $diluir;
									dbQuery("UPDATE fin_parcelas SET valor = valor-".$diluir." WHERE id=".$par['id']);
									$fez = true;
								}
							} else {
								$erros[] = "Já existe alguma parcela paga, portanto não é possível conceder desconto. Caso necessário você pode solicitar ao financeiro para fazê-lo.";
							}

						}

						break;

					case 'Aplicar somente na primeira parcela':

						$fez = false;
						if ($rs[0]['id_fin_lancamentos']) {
							$idLan = (int)$rs[0]['id_fin_lancamentos'];
							// Verifica se já houve algum pagamento efetivado...
							$sql = "SELECT max(data_efetivacao) data_efetivacao, count(id) ttl FROM fin_parcelas WHERE valor > 0 AND id_fin_lancamentos=".$idLan;
							$rsPar = dbQuery($sql);

							// Só altera se está tudo ok...
							if ($rsPar[0]['data_efetivacao'] == '0000-00-00') {
								$sql="SELECT * FROM fin_parcelas WHERE valor > 0 AND id_fin_lancamentos=".$idLan." ORDER BY id";
								$rsPar = dbQuery($sql);
								$cnt=0;
								if ($rsPar[0]['valor'] < $valor_solicitado) {
									$valor_solicitado=$rsPar[0]['valor'];
									$erros[]="O valor da 1ª parcela era inferior ao valor do desconto. A parcela ficou com valor <b>R$ 0,00 (zero)</b>";
								}

								dbQuery("UPDATE fin_parcelas SET valor=valor-".$valor_solicitado." WHERE id=".$rsPar[0]['id']);
								$fez=true;
							} else {
								$erros[] = "Já existe alguma parcela paga, portanto não é possível conceder desconto. Caso necessário você pode solicitar ao financeiro para fazê-lo.";
							}

						}

						break;

					case 'Aplicar somente na última parcela':

						$fez=false;
						if ($rs[0]['id_fin_lancamentos']) {
							$idLan=(int)$rs[0]['id_fin_lancamentos'];
							// Verifica se já houve algum pagamento efetivado...
							$sql = "SELECT max(data_efetivacao) data_efetivacao, count(id) ttl FROM fin_parcelas WHERE valor > 0 AND id_fin_lancamentos=".$idLan;
							$rsPar = dbQuery($sql);

							// Só altera se está tudo ok...
							if ($rsPar[0]['data_efetivacao'] == '0000-00-00') {
								$sql = "SELECT * FROM fin_parcelas WHERE valor > 0 AND id_fin_lancamentos=".$idLan." ORDER BY id DESC";
								$rsPar = dbQuery($sql);
								$cnt = 0;
								if ($rsPar[0]['valor'] < $valor_solicitado) {
									$valor_solicitado=$rsPar[0]['valor'];
									$erros[]="O valor da última parcela era inferior ao valor do desconto. A parcela ficou com valor <b>R$ 0,00 (zero)</b>";
								}

								dbQuery("UPDATE fin_parcelas SET valor=valor-".$valor_solicitado." WHERE id=".$rsPar[0]['id']);
								$fez = true;
							} else {
								$erros[] = "Já existe alguma parcela paga, portanto não é possível conceder desconto. Caso necessário você pode solicitar ao financeiro para fazê-lo.";
							}

						}

						break;

					default:
						$erros[]="Método de aplicação do desconto não existe: [".$metodo."]";

				}

				// Responde mensagem
				if (!is_array($erros)) {
					$flds='';
					$flds['id_pessoas_de'] = $usrId;
					$flds['id_pessoas_para'] = (int) $rs[0]['id_pessoas_de'];
					$aluno = $rs[0]['aluno'] . ' (' . $rs[0]['matricula'] . ')';
					$flds['mensagem'] = sprintf('Desconto aprovado para %s. Valor: ', $aluno).gFloat($valor_solicitado).". \n\n".$mensagem;
					$flds['parametros'] = $valor_solicitado;
					$flds['id_relacionado'] = $id_pessoas_aluno;

					if (!enviaMensagem(2,$flds)) {
						$erros[]="O desconto foi concedido mas ocorreu algum erro ao enviar a mensagem para quem o solicitou. Informe pessoalmente.";
					}

					// Marca como respondida
					dbQuery("UPDATE mensagens SET lida = 1, respondida = 1 WHERE id = " . $gId);
				}

				if (is_array($erros)) {
					$html .= $o->msgWarning("Ocorreu algum problema ao conceder o desconto: <br><Br>".implode("<br>• ", $erros));
				} else {
					$html .= $o->msgSuccess("Desconto autorizado");
				}
			} else {
				// Marca como respondida
				dbQuery("UPDATE mensagens SET lida = 1, respondida = 1 WHERE id = " . $gId);

				// Desconto não foi aprovado
				$flds='';
				$flds['id_pessoas_de'] = $usrId;
				$flds['id_pessoas_para'] = (int) $rs[0]['id_pessoas_de'];
				$flds['mensagem'] = "Desconto negado. Valor solicitado: ".gFloat($valor).".\n\n".$mensagem;
				$flds['parametros'] = $valor_solicitado;
				enviaMensagem(3,$flds);
				$html.=$o->msgSuccess("Desconto negado");
			}
		} else {
			$html .= "Esta mensagem não existe!";
		}

		$html .= $o->button("{icon: back; caption: Voltar; href: " . $o->page . "}");
		break;
}


function enviaMensagem($tipo, $campos)
{
	if ($campos['id_pessoas_de'] > 0 && $campos['id_pessoas_para'] > 0) {
		gLog("Mensagem enviada!");
		$campos['tipo'] = $tipo;
		$campos['data'] = date("Y-m-d H:i:s");
		dbInsert('mensagens', $campos);
		return true;
	}

	return false;
}
