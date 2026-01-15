<?
include_once "res/_classes/classes.php";

define('INICIO'             ,0);

define('IMPORTAR_DOCUMENTACAO', 1);
define('PROCESSAR_DOCUMENTACAO', 2);
define('CONFIRMACAO_IMPORTACAO_DOCUMENTACAO', 3);

define('IMPORTACAO_GWMS'    ,10);
define('IMPORTACAO_GWMS_0'  ,20);
define('IMPORTACAO_GWMS_1'  ,21);
define('IMPORTACAO_GWMS_2'  ,22);
define('IMPORTACAO_GWMS_3'  ,23);
define('IMPORTACAO_GWMS_4'  ,24);
define('IMPORTACAO_GWMS_5'  ,25);
define('IMPORTACAO_GWMS_6'  ,26);
define('IMPORTACAO_GWMS_7'  ,27);
define('IMPORTACAO_GWMS_8'  ,28);
define('IMPORTACAO_GWMS_9'  ,29);

define('CRIAR_UMAS'         ,30);
define('CRIAR_UMAS_TNL'     ,31);

define('IMPORTAR_SALDO'     ,40);
define('IMPORTAR_SALDO_TNL' ,41);

define('IMPORTAR_SENIOR'    ,50);
define('IMPORTAR_SENIOR_TNL',51);

define('RECALCULAR_SALDO', 60);
define('RECALCULAR_SALDO_TNL', 61);

define('TESTAR_JUNG'        ,70);
define('TESTAR_JUNG_TNL'    ,71);

define('BOMIX_LOTE_FABRICACAO',80);

define('BOMIX_POSICOES'		,90);
define('BOMIX_POSICOES_TNL'	,91);

define('CORRIGIR'           ,200);
define('LIMPA_PROGRAMACAO'  ,210);

define('IMPORTAR_CONSOLIDADO',     300);
define('IMPORTAR_CONSOLIDADO_TNL', 301);

define('LIMPAR_SALDO_WMS_NAO_TEM_PROTHEUS',     400);
define('LIMPAR_SALDO_WMS_NAO_TEM_PROTHEUS_TNL', 401);

define('IMPORTAR_ITENS_GWMS_ANTIGO', 500);
define('IMPORTAR_NOTAS_YPE', 600);

define('EXPURGO'           ,700);
define('CAPTURAR_UMAS_PRESAS_RESERVA', 800);

define('CORRIGE_SALDO_ANALITICO_NEGATIVO', 801);
define('DESATIVAR_UMAS_SEM_SALDO', 802);
define('AJUSTE_POSICAO_SIEMENS', 803);
//define('RECUPERACAO'       ,710);

define("FILTRO_ALIMENTACAO_UMA", 850);
define("ALIMENTACAO_UMA", 851);

if (in_array($gPage, array(
		IMPORTAR_DOCUMENTACAO,
		PROCESSAR_DOCUMENTACAO,
		CONFIRMACAO_IMPORTACAO_DOCUMENTACAO)
	)
) {
	$html .= $o->msgTitle("Importação de documentação do WMS");
}

$mesesDeExpurgo=(intval($_REQUEST['mesesDeExpurgo']) ? intval($_REQUEST['mesesDeExpurgo']) : "12");

if ($_REQUEST['gAjax'])
{
	$limite = "100";
	$tabelas['umas'] = "FROM umas WHERE data < DATE_SUB(NOW(), INTERVAL $mesesDeExpurgo MONTH) AND data_desativacao < DATE_SUB(NOW(), INTERVAL $mesesDeExpurgo MONTH) AND data_desativacao<>'0000-00-00 00:00:00' AND ativo=0";
	$tabelas['programacao'] = "FROM programacao WHERE data_cadastro < DATE_SUB(NOW(), INTERVAL $mesesDeExpurgo MONTH)";
	$tabelas['veiculos'] = "FROM veiculos_acessos WHERE data_chegada < DATE_SUB(NOW(), INTERVAL $mesesDeExpurgo MONTH)";
	$tabelas['inventarios'] = "FROM inventarios WHERE data < DATE_SUB(NOW(), INTERVAL $mesesDeExpurgo MONTH)";
	$tabelas['log'] = "FROM gfw_log WHERE date < DATE_SUB(NOW(), INTERVAL $mesesDeExpurgo MONTH)";
	$tabelas['contagens'] = "FROM contagens_umas WHERE data < DATE_SUB(NOW(), INTERVAL $mesesDeExpurgo MONTH)";

	switch ($cmd)
	{
		case 'umas_inativas':
			$sql = "SELECT * ".$tabelas['umas']." LIMIT $limite";
			$rs = dbQuery($sql);
			$ids = array();
			foreach($rs as $row)
			{
				$flds = array();
				foreach ($row as $key=>$value)
				{
					if (!is_numeric($key))
					{
						$flds[$key] = str_replace("'"," ",str_replace("'"," ",$value));
					}
				}
				dbInsert('expurgo_umas', $flds);
				$ids[] = $row['id'];
			}
			if (count($ids))
			{
				$sql = "INSERT INTO expurgo_umas_itens (SELECT * FROM umas_itens WHERE id_umas IN (".implode(",",$ids)."))";
				dbQuery($sql);
				$sql = "DELETE FROM umas_itens WHERE id_umas IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "INSERT INTO expurgo_umas_movimentos (SELECT * FROM umas_movimentos WHERE id_umas IN (".implode(",",$ids)."))";
				dbQuery($sql);
				$sql = "DELETE FROM umas_movimentos WHERE id_umas IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "DELETE FROM umas WHERE id IN (".implode(",",$ids).")";
				dbQuery($sql);
			}
			$sql = "SELECT count(id) ttl ".$tabelas['umas'];
			echo dbQuery($sql)[0]['ttl'];
			break;

		case 'programacoes_antigas':
			$sql = "SELECT * ".$tabelas['programacao']." LIMIT $limite";
			$rs = dbQuery($sql);
			$ids = array();
			foreach($rs as $row)
			{
				$flds = array();
				foreach ($row as $key=>$value)
				{
					if (!is_numeric($key))
					{
						$flds[$key] = $value;
					}
				}
				dbInsert('expurgo_programacao', $flds);
				$ids[] = $row['id'];
			}
			if (count($ids))
			{
				$sql = "INSERT INTO expurgo_programacao_itens (SELECT * FROM programacao_itens WHERE id_programacao IN (".implode(",",$ids)."))";
				dbQuery($sql);
				$sql = "DELETE FROM programacao_itens WHERE id_programacao IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "INSERT INTO expurgo_programacao_atividades (SELECT * FROM programacao_atividades WHERE id_programacao IN (".implode(",",$ids)."))";
				dbQuery($sql);
				$sql = "DELETE FROM programacao_atividades WHERE id_programacao IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "DELETE FROM programacao WHERE id IN (".implode(",",$ids).")";
				dbQuery($sql);
			}
			$sql = "SELECT count(id) ttl ".$tabelas['programacao'];
			echo dbQuery($sql)[0]['ttl'];
		break;


		case 'veiculos_antigos':
			$sql = "SELECT * ".$tabelas['veiculos']." LIMIT $limite";
			$rs = dbQuery($sql);
			$ids = array();
			foreach($rs as $row)
			{
				$flds = array();
				foreach ($row as $key=>$value)
				{
					if (!is_numeric($key))
					{
						$flds[$key] = $value;
					}
				}
				dbInsert('expurgo_veiculos_acessos', $flds);
				$ids[] = $row['id'];
			}
			if (count($ids))
			{
				$sql = "INSERT INTO expurgo_veiculos_acessos_programacoes (SELECT * FROM veiculos_acessos_programacoes WHERE id_veiculos_acessos IN (".implode(",",$ids)."))";
				dbQuery($sql);
				$sql = "DELETE FROM veiculos_acessos WHERE id IN (".implode(",",$ids).")";
				dbQuery($sql);
			}
			$sql = "SELECT count(id) ttl ".$tabelas['veiculos'];
			echo dbQuery($sql)[0]['ttl'];
		break;

		case 'inventarios_antigos':
			$sql = "SELECT * ".$tabelas['inventarios']." LIMIT $limite";
			$rs = dbQuery($sql);
			$ids = array();
			foreach($rs as $row)
			{
				$flds = array();
				foreach ($row as $key=>$value)
				{
					if (!is_numeric($key))
					{
						$flds[$key] = $value;
					}
				}
				$ids[] = $row['id'];
			}
			if (count($ids))
			{
				$sql = "DELETE FROM inventarios_skus WHERE id_inventarios IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "DELETE FROM inventarios WHERE id IN (".implode(",",$ids).")";
				dbQuery($sql);
			}
			$sql = "SELECT count(id) ttl ".$tabelas['inventarios'];
			echo dbQuery($sql)[0]['ttl'];
		break;

		case 'log':
			$sql = "DELETE ".$tabelas['log'];
			dbQuery($sql);

			$sql = "SELECT count(id) ttl ".$tabelas['log'];
			echo dbQuery($sql)[0]['ttl'];
		break;

		case 'tabelas_temporarias':
			$sql = "SELECT * ".$tabelas['contagens']." LIMIT $limite";
			$rs = dbQuery($sql);
			$ids = array();
			foreach($rs as $row)
			{
				$flds = array();
				foreach ($row as $key=>$value)
				{
					if (!is_numeric($key))
					{
						$flds[$key] = $value;
					}
				}
				$ids[] = $row['id'];
			}
			if (count($ids))
			{
				$sql = "DELETE FROM contagens_umas_itens WHERE id_contagens_umas IN (".implode(",",$ids).")";
				dbQuery($sql);
				$sql = "DELETE FROM contagens_umas WHERE id IN (".implode(",",$ids).")";
				dbQuery($sql);
			}
			$sql = "SELECT count(id) ttl ".$tabelas['contagens'];
			echo dbQuery($sql)[0]['ttl'];
		break;


	}
	exit;
}


switch($gPage)
{
	case INICIO:
		$html .= $o->msgTitle("Ferramentas");
		$html .= $o->button("{title: Recalcular umas_saldos; icon: calculator; style: primary; size: big; href: ".$o->page."&gPage=".RECALCULAR_SALDO."}");
		$html .= $o->button("{title: Criar UMAs em lote; icon: barcode; style: primary; size: big; href: ".$o->page."&gPage=".CRIAR_UMAS."}");
		$html .= $o->button("{title: Importar UMAs gWMS antigo; icon: random; style: primary; size: big; href: ".$o->page."&gPage=".IMPORTAR_SALDO."}");
		$html .= $o->button("{title: Importar Documentação; icon: download; style: primary; size: big; href: " . $o->page . "&gPage=" . IMPORTAR_DOCUMENTACAO . "}");
		$html .= $o->button("{title: Importar Senior/SILT; icon: file-import; style: primary; size: big; href: ".$o->page."&gPage=".IMPORTAR_SENIOR."}");
		$html .= $o->button("{title: Testar Jungheinrich; icon: forklift; style: primary; size: big; href: ".$o->page."&gPage=".TESTAR_JUNG."}");
		$html .= $o->button("{title: Data Fab.Bomix; hint: Recalcular data_fabricacao de acordo com o lote (Bomix); icon: check; style: primary; size: big; href: ".$o->page."&gPage=".BOMIX_LOTE_FABRICACAO."}");
		$html .= $o->button("{title: Corrigir; icon: check; style: primary; size: big; href: ".$o->page."&gPage=".CORRIGIR."}");
		$html .= $o->button("{title: Posicoes Bomix; icon: map; style: primary; size: big; href: ".$o->page."&gPage=".BOMIX_POSICOES."}");
		$html .= $o->button("{title: Consolidado Bomix; icon: download; style: primary; size: big; href: ".$o->page."&gPage=".IMPORTAR_CONSOLIDADO."}");
		$html .= $o->button("{title: Limpa programação; hint: Limpa registros não relacionados entre programacao e programacao_itens; icon: trash; style: primary; size: big; href: ".$o->page."&gPage=".LIMPA_PROGRAMACAO."}");
		$html .= $o->button("{title: Limpa saldo wms; hint: Limpa saldo que tem no wms, mas não existe no protheus; icon: ban; style: primary; size: big; href: ".$o->page."&gPage=".LIMPAR_SALDO_WMS_NAO_TEM_PROTHEUS."}");
		$html .= $o->button("{title: Alimentação UMA; hint: Alimentação de UMA; icon: file-import; style: primary; size: big; href: ".$o->page."&gPage=" . FILTRO_ALIMENTACAO_UMA . "}");
		$html .= $o->button("{title: Expurgo; hint: Transferir dados antigos para arquivo morto; icon: trash; style: danger; size: big; href: ".$o->page."&gPage=".EXPURGO."}");
		//		$html .= $o->button("{title: Recuperar; hint: Transferir dados do arquivo morto para produção novamente; icon: recycle; style: danger; size: big; href: ".$o->page."&gPage=".RECUPERACAO."}");
		break;

	case IMPORTACAO_GWMS:
		$html.=$o->msgTitle("Importação gWMS Antigo");
		$frm=new gForm("{columns: 3}");
		$frm->row(
			$frm->add("{name: cnpj; fieldLabel: CNPJ; type: text; hint: CNPJ do cliente}"),
			$frm->add("{name: bd; fieldLabel: Banco de dados; type: text; hint: Nome do BD}")
		);
		$frm->add("{name: dados; fieldLabel: Dados do cliente; type: checkbox; value: 0}");
		$frm->add("{name: itens; fieldLabel: Itens; type: checkbox;}");
		$frm->add("{name: saldos; fieldLabel: Saldo inventário; type: checkbox;}");
		$frm->add("{name: nfs; fieldLabel: Notas fiscais; type: checkbox;}");
		$frm->add("{name: umas; fieldLabel: UMAs; type: checkbox;}");
		$frm->add("{name: programacoes; fieldLabel: Programações ativas; type: checkbox;}");
		$frm->add("{name: gPage; type: hidden; value: ".IMPORTACAO_GWMS_0.";}");

		$html.=$frm->render($o);
		break;

	case IMPORTACAO_GWMS_0:
		$html.=$o->msgTitle("Importação gWMS Antigo");

		$cnpj = gJustNumbers($_REQUEST['cnpj']);
		$bd = gCleanField($_REQUEST['bd']);

		$sql = "SELECT P.*, PJ.id idPj, PJ.razao_social, PJ.cnpj, PJ.insc_estadual, PJ.insc_municipal, PJ.site
				FROM ".$bd."_wms.pessoas P
				LEFT JOIN ".$bd."_wms.pessoas_juridicas PJ ON P.id=PJ.id_pessoas
				WHERE PJ.cnpj='".$cnpj."'";
		$camposVelho = dbFastQuery($sql)[0];
		if ($camposVelho['id']>0)
		{
			if ($camposVelho['ativo']==0)
			{
				dbFastQuery("TRUNCATE TABLE cidades");
				dbFastQuery("INSERT INTO cidades (SELECT id, id_estados, sigla, descricao, distancia_da_sede FROM ".$bd."_wms.cidades)");

				// Verifica se já existe no sistema atual
				$sql = "SELECT P.*, PJ.id idPj, PJ.razao_social, PJ.cnpj, PJ.insc_estadual, PJ.insc_municipal, PJ.site
						FROM pessoas P
						LEFT JOIN pessoas_juridicas PJ ON P.id=PJ.id_pessoas
						WHERE PJ.cnpj='".$cnpj."'";
				$camposNovo = dbFastQuery($sql)[0];
				$cmps = array();
				$cmps['tipo'] = 'J';
				$cmps['apelido'] = $camposVelho['apelido'];
				$cmps['nome'] = $camposVelho['nome'];
				$cmps['senha'] = $camposVelho['senha'];
				$cmps['email'] = $camposVelho['email'];
				$cmps['situacao'] = $camposVelho['situacao'];
				$cmps['cliente'] = $camposVelho['cliente'];
				$cmps['fornecedor'] = $camposVelho['fornecedor'];
				$cmps['terceirizado'] = $camposVelho['terceirizado'];
				$cmps['telefone'] = $camposVelho['telefone'];
				$cmps['celular'] = $camposVelho['celular'];
				$cmps['ramal'] = $camposVelho['ramal'];
				$cmps['site'] = $camposVelho['site'];
				$cmps['emitir_nfe'] = $camposVelho['emitir_nfe'];
				$cmps['data_cadastro'] = $camposVelho['data_cadastro'];
				if ($camposNovo['cnpj']<>'')
				{
					$msg = "Cliente modificado!";
					$gId = $camposNovo['id'];
					// Já existe... atualiza alguns dados
					dbUpdate('pessoas', $cmps, $gId);
				} else {
					// Não existe... insere
					$msg = "Cliente adicionado!";
					$gId = dbInsert('pessoas', $cmps, true);
				}
				$cmps = array();
				$cmps['id_pessoas'] = $gId;
				$cmps['fiscal'] = '1';
				$cmps['cnpj'] = $camposVelho['cnpj'];
				$cmps['insc_estadual'] = $camposVelho['insc_estadual'];
				$cmps['insc_municipal'] = $camposVelho['insc_municipal'];
				$cmps['razao_social'] = $camposVelho['razao_social'];
				$cmps['site'] = $camposVelho['site'];
				$cmps['matriz'] = $camposVelho['matriz'];
				$cmps['observacoes'] = $camposVelho['informacoes_publicas'];
				if ($camposNovo['cnpj']<>'')
				{
					$idPj = $camposNovo['idPj'];
					// Já existe... atualiza alguns dados
					dbUpdate('pessoas_juridicas', $cmps, $idPj);
				} else {
					// Não existe... insere
					dbInsert('pessoas_juridicas', $cmps);
				}
				$sql = "SELECT E.*
						FROM ".$bd."_wms.pessoas_enderecos E
						WHERE E.id_pessoas='".$camposVelho['id']."'";
				$endVelho = dbFastQuery($sql)[0];

				$end = array();
				$end['id_pessoas'] = $gId;
				$end['endereco'] = $endVelho['endereco'];
				$end['complemento'] = $endVelho['complemento'];
				$end['bairro'] = $endVelho['bairro'];
				$end['numero'] = $endVelho['numero'];
				$end['aplicacao'] = $endVelho['aplicacao'];
				$end['cep'] = $endVelho['cep'];
				$end['id_enderecos_cidades'] = $endVelho['id_cidades'];
				$end['id_enderecos_estados'] = $endVelho['id_estados'];
				$end['id_enderecos_paises'] = $endVelho['id_paises'];

				if ($endNovo['id']>0)
				{
					$id = $endNovo['id'];
					// Já existe... atualiza alguns dados
					dbUpdate('pessoas_enderecos', $end, $id);
				} else {
					// Não existe... insere
					dbInsert('pessoas_enderecos', $end);
				}
				$html.=$o->msgInfo($msg);
				$html.=$o->button("{title: Próximo; icon: arrow-right; href: ".$o->page."&bd=$bd&gPage=".IMPORTACAO_GWMS_1."&gIdVelho=".$camposVelho['id']."&gId=".$gId."&cnpj=$cnpj&itens=$itens&umas=$umas&saldos=$saldos&nfs=$nfs}");
			} else {
				$html.=$o->msgDanger("Cliente inativo");
			}
		} else {
			$html.=$o->msgDanger("Cliente não encontrado");
		}
	break;

	case IMPORTAR_ITENS_GWMS_ANTIGO:
		/*
		$id_pessoas_proprietario_sistema_antigo=90;
		$id_pessoas_proprietario=3940;
		//90-3940
		//

		$sql="SELECT
				i.*, g.descricao grupo, t.descricao tipo
			  FROM logic_gsapp_wms.itens i
			  LEFT JOIN logic_gsapp_wms.grupos g ON g.id = i.id_grupos
			  LEFT JOIN logic_gsapp_wms.tipos t ON t.id = i.id_tipos
			  WHERE i.id_pessoas_proprietario=".$id_pessoas_proprietario_sistema_antigo;
		$itens=dbQuery($sql);
		foreach ($itens as $item)
		{
			$sql="SELECT * FROM grupos WHERE descricao='".$item["grupo"]."'";
			$grupo=(dbQuery($sql)[0]);

			$sql="SELECT * FROM tipos WHERE descricao='".$item["tipo"]."'";
			$tipo=(dbQuery($sql)[0]);

			$mtz=array();
			$mtz["id_pessoas_proprietario"]=$id_pessoas_proprietario;
			$mtz["id_pessoas_fornecedor"]=$id_pessoas_proprietario;
			$mtz["id_grupos"]=intval($grupo["id"]);
			$mtz["id_tipos"]=intval($tipo["id"]);
			$mtz["ativo"]=0;
			$mtz["apto"]=0;
			$mtz["codigo"]=$item["codigo"];
			$mtz["codigo_barras"]=$item["codigo_barras"];
			$mtz["nome"]=$item["nome"];
			$mtz["descricao"]=$item["descricao"];
			$mtz["id_prioridades_saida"]=0;
			$mtz["prazo_validade"]=$item["prazo_validade"];
			$mtz["shelf_life"]=$item["shelf_life"];
			$mtz["faz_picking"]=0;
			$mtz["data_cadastro"]=date('Y-m-d H:i:s');
			$mtz["id_pessoas_criou"]=1;
			//$mtz["data_critica"]=$item["data_critica"];
			$mtz["temperatura_ideal"]=$item["temperatura_ideal"];
			$mtz["temperatura_limite"]=$item["temperatura_limite"];
			$mtz["ncm"]=$item["ncm"];
			$mtz["id_itens_skus_operacao"]=$item["id_itens_skus_operacao"];
			//$mtz["sugerir"]=$item["sugerir"];
			//$mtz["faturar"]=$item["faturar"];
			$id_item=dbInsert("itens", $mtz, true);

			$sql="SELECT * FROM logic_gsapp_wms.itens_skus WHERE id_itens=".$item["id"];
			$skus=dbQuery($sql);
			foreach ($skus as $sku)
			{
				// Recuperar unidade
				$sql="SELECT * FROM logic_gsapp_wms.unidades WHERE id=".$sku["id_unidades"];
				$unidade_sistema_antigo=(dbQuery($sql)[0]);

				$sql="SELECT * FROM unidades WHERE sigla='".$unidade_sistema_antigo["sigla"]."'";
				$unidade=(dbQuery($sql)[0]);

				$mtz=array();
				$mtz["id_itens"]=$id_item;
				$mtz["codigo"]=$sku["codigo"];
				$mtz["codigo_barras"]=$sku["codigo_barras"];
				$mtz["nome"]=$sku["nome"];
				$mtz["id_pessoas_criou"]=1;
				$mtz["data_cadastro"]=date('Y-m-d H:i:s');
				$mtz["id_unidades"]=$unidade["id"];
				$mtz["quantidade"]=$sku["quantidade"];
				$mtz["peso_liquido"]=$sku["peso_liquido"];
				$mtz["peso_bruto"]=$sku["peso_bruto"];
				$mtz["largura"]=$sku["largura"];
				$mtz["altura"]=$sku["altura"];
				$mtz["comprimento"]=$sku["comprimento"];
				$mtz["palete_lastro"]=$sku["palete_lastro"];
				$mtz["palete_altura"]=$sku["palete_altura"];
				$mtz["codigo_barras_alternativo"]=$sku["codigo_barras2"];
				$mtz["ativo"]=0;
				$id_item_sku=dbInsert("itens_skus", $mtz, true);
			}
		}
		*/
	break;

	case IMPORTAR_NOTAS_YPE:
		$in_programacao=array(8508);
		$sql="SELECT * FROM inventarios_finalizados WHERE id_programacao in (".implode(",", $in_programacao).")";
		$inventarios_finalizados=dbQuery($sql);
		foreach ($inventarios_finalizados as $inventario_finalizado)
		{
			$sql="
				  SELECT
					n.*
				  FROM logic_gsapp_wms.umas_itens ui
				  LEFT JOIN logic_gsapp_wms.umas u on u.id = ui.id_umas
				  LEFT JOIN logic_gsapp_wms.notas_itens ni on ni.id = ui.id_notas_itens
				  LEFT JOIN logic_gsapp_wms.notas n on n.id = ni.id_notas
				  WHERE u.codigo_barras='".$inventario_finalizado['uma_antiga']."'
				  AND ni.id_notas>0
				  GROUP BY ni.id_notas
				";
			$uma_sistema_antigo=(dbQuery($sql)[0]);
			$nota=(dbQuery($sql)[0]);

			$sql="SELECT * FROM logic_gsapp_wms.notas_itens WHERE id_notas=".intval($nota['id']);
			$notas_itens=dbQuery($sql);

			// Importar nota item.
			foreach ($notas_itens as $nota_item)
			{
				// Achar SKU no sistema novo.



			}

			// Importar nota

			gD($notas_itens);
			exit;



		}
	break;


	case IMPORTACAO_GWMS_1:
		dbFastQuery("TRUNCATE TABLE grupos");
		dbFastQuery("INSERT INTO grupos (SELECT id, 1, '', descricao FROM ".$bd."_wms.grupos)");
		dbFastQuery("TRUNCATE TABLE tipos");
		dbFastQuery("INSERT INTO tipos (SELECT id, descricao FROM ".$bd."_wms.tipos)");
		dbFastQuery("TRUNCATE TABLE unidades");
		dbFastQuery("INSERT INTO unidades (SELECT id, descricao, '',0,0,0,0,0,0,0 FROM ".$bd."_wms.unidades)");

		$sql = "SELECT I.*
				FROM ".$bd."_wms.itens I
				WHERE I.id_pessoas_proprietario=".$gIdVelho."
				AND I.id NOT IN (SELECT id FROM itens WHERE id_pessoas_proprietarios=".$gId.")";
		$rs = dbQuery($sql);
		foreach ($rs as $item)
		{
			$sql = "SELECT SK.*
					FROM ".$bd."_wms.itens_skus SK
					WHERE I.id_itens=".$item['id'];
			$rskus = dbQuery($sql);

			$cmps = array();
			$cmps['ativo'] = $item['ativo'];
			$cmps['id_pessoas_proprietario'] = $gId;
			$cmps['id_grupos'] = $item['grupos'];
			$cmps['id_tipos'] = $item['tipos'];
			foreach ($rskus as $sku)
			{

			}

		}
		$html.=$o->msgInfo("Itens importados");
		$html.=$o->button("{title: Próximo; icon: arrow-right; href: ".$o->page."&bd=$bd&gPage=".IMPORTACAO_GWMS_1."&gIdVelho=".$camposVelho['id']."&gId=".$gId."&cnpj=$cnpj&itens=$itens&umas=$umas&saldos=$saldos&nfs=$nfs}");
	break;

	case CRIAR_UMAS:
		$html.=$o->msgTitle("Criar UMAs");
		$frm=new gForm("{columns: 3}");
		$frm->add("{name: umas; fieldLabel: UMAs; type: number; value: 1;}");
		$frm->add("{name: gPage; type: hidden; value: ".CRIAR_UMAS_TNL.";}");
		$html.=$frm->render($o);
	break;

	case CRIAR_UMAS_TNL:
		$html.=$o->msgTitle("Criar UMAs");
		$persistencia = new UMA();
		$campos['data'] = date("Y-m-d H:i:s");
		$campos['id_armazens'] = $_SESSION['armazemAtualId'];
		$campos['id_tipos_umas'] = 1;
		for ($a=0; $a<$umas; $a++)
		{
			$campos['id'] = ($a+1);
			$campos['codigo_barras']=formataUMA($a+1);
			dbFastInsert('umas', $campos);
		}
		$html.=$o->msgInfo("UMAs criadas: $umas");
	break;


	case IMPORTAR_SALDO:
		$html.=$o->msgTitle("Importar UMAs do gWMS antigo");
		$frm=new gForm("{columns: 3}");
		$frm->add("{name: cnpj; fieldLabel: CNPJ; type: text; hint: CNPJ do cliente}");
		$frm->add("{name: bd; fieldLabel: Banco de dados; type: text; hint: Nome do BD}");
		$frm->add("{name: criar_umas; fieldLabel: Criar novas UMAs; type: checkbox; value: 0}");
		$frm->add("{name: simular; fieldLabel: Simular sem salvar; type: checkbox; value: 1}");
		$frm->add("{name: gPage; type: hidden; value: ".IMPORTAR_SALDO_TNL.";}");

		$html.=$frm->render($o);
	break;

	case IMPORTAR_SALDO_TNL:
		$o->PDFEnabled=true;
		$o->DOCEnabled=true;
		$o->XLSEnabled=true;
		$o->CSVEnabled=true;

		$persistencia = new UMA();
		$html.=$o->msgTitle("Importar UMAs do gWMS antigo");

		$id_armazens = $_SESSION['armazemAtualId'];
		$simular = gDBCheck($_REQUEST['simular']);
		$criar_umas = gDBCheck($_REQUEST['criar_umas']);


		$cnpj = gJustNumbers($_REQUEST['cnpj']);
		$bd = gCleanField($_REQUEST['bd']);
		$sql = "SELECT P.*, PJ.id idPj, PJ.razao_social, PJ.cnpj, PJ.insc_estadual, PJ.insc_municipal, PJ.site
				FROM ".$bd."_wms.pessoas P
				LEFT JOIN ".$bd."_wms.pessoas_juridicas PJ ON P.id=PJ.id_pessoas
				WHERE PJ.cnpj='".$cnpj."'";
		$camposVelho = dbFastQuery($sql)[0];
		$gIdVelho = intval($camposVelho['id']);

		$sql = "SELECT P.*, PJ.id idPj, PJ.razao_social, PJ.cnpj, PJ.insc_estadual, PJ.insc_municipal, PJ.site
				FROM pessoas P
				LEFT JOIN pessoas_juridicas PJ ON P.id=PJ.id_pessoas
				WHERE PJ.cnpj='".$cnpj."'";
		$camposNovo = dbFastQuery($sql)[0];
		$gIdNovo = intval($camposNovo['id']);

		$ruas = array();

		if ($gIdVelho>0)
		{

			// Cache de posições
			$sql = "SELECT * FROM posicoes";
			$rsp = dbQuery($sql);
			$posicoes = array();
			foreach ($rsp as $row)
			{
				$posicoes[$row['codigo_barras']] = $row['id'];
			}
			// Cache de itens
			$sql = "SELECT SK.*
					FROM itens_skus SK
					LEFT JOIN itens I ON SK.id_itens=I.id
					WHERE I.id_pessoas_proprietario=".$gIdNovo;
			$rsp = dbQuery($sql);
			$itens = array();
			foreach ($rsp as $row)
			{
				$itens[$row['codigo']] = $row['id'];
			}

			// Busca itens no sistema antigo
			$sql = "
					SELECT * FROM
					(SELECT min(ui.data) data,
						posicoes.codigo_barras posicao, umas.codigo_barras uma, its.codigo, max(n.numero) nota_entrada,
						ui.data_fabricacao, ui.data_validade,uni.descricao unidade, ui.lote, ui.serial,
						ui.reservada,ui.separada, ui.avariada,ui.bloqueada,
						max(ui.peso_liquido) peso_liquido, max(ui.peso_bruto) peso_bruto, max(ui.m2) m2, max(ui.m3) m3, max(ui.valor) valor,
						sum(IF(ui.tipo='E',ui.quantidade,ui.quantidade*-1)) quantidade
					FROM ".$bd."_wms.umas
					LEFT JOIN ".$bd."_wms.umas_itens ui ON (umas.id=ui.id_umas)
					LEFT JOIN ".$bd."_wms.itens_skus its ON ui.id_itens_skus=its.id
					LEFT JOIN ".$bd."_wms.itens i ON its.id_itens=i.id
					LEFT JOIN ".$bd."_wms.posicoes ON umas.id_posicoes = posicoes.id
					LEFT JOIN ".$bd."_wms.pessoas pp ON i.id_pessoas_proprietario=pp.id
					LEFT JOIN ".$bd."_wms.unidades uni ON its.id_unidades=uni.id
					LEFT JOIN ".$bd."_wms.notas_itens ni ON ni.id=ui.id_notas_itens
					LEFT JOIN ".$bd."_wms.notas n ON ni.id_notas=n.id
					WHERE umas.ativo = 1 AND umas.e = 0 AND (ui.tipo in ('E','S')) AND i.id_pessoas_proprietario=".$gIdVelho."
					GROUP BY its.id, pp.nome, umas.codigo_barras, ui.reservada,ui.separada, ui.avariada, ui.bloqueada, posicoes.codigo_barras, i.nome,
					uni.descricao, its.quantidade, its.codigo, ui.lote, ui.serial
					order by umas.codigo_barras
					) S WHERE quantidade>0 AND reservada=0 AND separada=0 AND avariada=0 AND bloqueada=0" ;
//gD($sql);gD($itens);exit;
			$rs = dbQuery($sql);
			if (count($rs)>0)
			{
				$html.=$o->tableBegin("big",true);
				$mtz = array();
				$mtz[]="<-Posição";
				$mtz[]="<-UMA";
				$mtz[]="<-Código";
				$mtz[]="<-Unidade";
				$mtz[]="<-Lote";
				$mtz[]="<>Fabricação";
				$mtz[]="<>Validade";
				$mtz[]="->Quantidade";
				$mtz[]="<-NF";
				$mtz[]="<-Erros";
				$html.=$o->tableRow($mtz,"header");
				foreach ($rs as $row)
				{
					$mtz = array();
					$mtz[]="<-".$row['posicao'];
					$mtz[]="<-".$row['uma'];
					$mtz[]="<-".$row['codigo'];
					$mtz[]="<-".$row['unidade'];
					$mtz[]="<-".$row['lote'];
					$mtz[]="<>".gDate($row['data_fabricacao']);
					$mtz[]="<>".gDate($row['data_validade']);
					$mtz[]="->".gFloat($row['quantidade']);
					$mtz[]="<-".$row['nota_entrada'];

					$id_umas = intval(substr($row['uma'],3));
					$id_posicoes=intval($posicoes[$row['posicao']]);
					$id_itens_skus=intval($itens[$row['codigo']]);

					if ($id_posicoes>0)
					{
						$ruas[substr($row['posicao'],0,6)] = 1;
					}

					$erros = array();
					if ($id_umas==0)
					{
						$erros[]=$row['uma']." não existe";
					}
					if ($id_posicoes==0)
					{
						$erros[]=$row['posicao']." não existe";
					}
					if ($id_itens_skus==0)
					{
						$erros[]=$row['codigo']." não existe";
					}
					if (count($erros)==0)
					{
						if (!$simular)
						{
							if ($criar_umas)
							{
								$cmps = array();
								$cmps['codigo_externo'] = $row['uma'];
								$cmps['data'] = date('Y-m-d H:i:s');
								$cmps['data_ativacao'] = $cmps['data'];
								$cmps['posicionada'] = '1';
								$cmps['id_posicoes'] = $id_posicoes;
								$uma['complemento'] = "Importada SILT";
								$id_umas = $persistencia->criaUMA($cmps);
							} else {
								$sql = "UPDATE umas SET complemento='Importada gWMS',data='".$row['data']."', data_ativacao='".$row['data']."', posicionada=1, id_posicoes=$id_posicoes WHERE id=".$id_umas;
								dbFastQuery($sql);
							}
							$cmps = array();
							$cmps['inventario']              = '1';
							$cmps['tipo']                    = '+';
							$cmps['id_umas']                 = $id_umas;
							$cmps['id_pessoas_proprietario'] = $gIdNovo;
							$cmps['id_armazens']             = $id_armazens;
							$cmps['id_itens_skus']           = $id_itens_skus;
							$cmps['observacoes']             = $row['nota_entrada'];
							$cmps['data']                    = $row['data'];
							$cmps['data_fabricacao']         = $row['data_fabricacao'];
							$cmps['data_validade']           = $row['data_validade'];
							$cmps['lote']                    = $row['lote'];
							$cmps['peso_bruto']              = $row['peso_bruto'];
							$cmps['peso_liquido']            = $row['peso_liquido'];
							$cmps['m2']                      = $row['m2'];
							$cmps['m3']                      = $row['m3'];
							$cmps['quantidade']              = $row['quantidade'];
							$cmps['valor']                   = $row['valor'];
							$cmps['serial']                  = $row['serial'];
							dbFastInsert('umas_itens', $cmps);
						}

						$mtz[] = "<-Ok";
						$html.=$o->tableRow($mtz,"detail");
					} else {
						$mtz[] = "<-".$o->small($o->ul($erros));
						$html.=$o->tableRow($mtz,"danger");
					}



				}
				$html.=$o->tableEnd();

				$html.=$o->msgSubTitle("Ruas encontradas:");
				$r = array();
				foreach ($ruas as $rua=>$valor)
				{
					$r[]=$rua;
				}
				$html.=$o->ul($r);
			} else {
				$html.=$o->msgDanger("Nenhum registro encontrado");
			}

		} else {
			$html.=$o->msgDanger("Empresa não encontrada");
		}
	break;

	case IMPORTAR_SENIOR:
		$html.=$o->msgTitle("Importar planilha SENIOR/SILT");
		$html.=$o->msg("Use o relatório <b>Estoque Local por Lote</b>");
		$frm=new gForm();
		$frm->add("{name: id_pessoas_proprietario; fieldLabel: Cliente; type: combo; items: ".$sp["combo_clientes"]."}");
		$frm->add("{name: simular; fieldLabel: Simular sem salvar; type: checkbox; value: 1}");
		$frm->add("{name: arquivo; fieldLabel: Arquivo; type: file}");
		$frm->add("{name: gPage; type: hidden; value: ".IMPORTAR_SENIOR_TNL.";}");
		$html.=$frm->render($o);
	break;

	case IMPORTAR_SENIOR_TNL:
	$linhas = explode("\n", str_replace("\r","",file_get_contents($_FILES['arquivo']['tmp_name'])));

	$persistencia = new UMA();
	$html.=$o->msgTitle("Importar planilha SENIOR/SILT");

	$id_armazens = $_SESSION['armazemAtualId'];
	$id_pessoas_proprietario = intval($_REQUEST['id_pessoas_proprietario']);
	$simular = gDBCheck($_REQUEST['simular']);
	$agora = date("Y-m-d H:i:s");

	if (count($linhas)>0)
	{
		// Cache de posições
		$sql = "SELECT * FROM posicoes";
		$rsp = dbQuery($sql);
		$posicoes = array();
		foreach ($rsp as $row)
		{
			$posicoes[$row['codigo_barras']] = $row['id'];
		}
		// Cache de itens
		$sql = "SELECT SK.*
				FROM itens_skus SK
				LEFT JOIN itens I ON SK.id_itens=I.id
				WHERE I.id_pessoas_proprietario=".$id_pessoas_proprietario;
		$rsp = dbQuery($sql);
		$itens = array();
		foreach ($rsp as $row)
		{
			$itens[$row['codigo']] = $row['id'];
		}


		$html.=$o->tableBegin("big",true);
		$mtz = array();
		$mtz[]="->Id";
		$mtz[]="<-Local/Posição";
		$mtz[]="<-Tipo local";
		$mtz[]="<-Lote/UMA"; //3
		$mtz[]="<-Estado";
		$mtz[]="<-Estoque UN";
		$mtz[]="<-Pendência UN";
		$mtz[]="<-Adicionar UN";
		$mtz[]="<-Disponível UN";
		$mtz[]="<-Cód.produto"; //9
		$mtz[]="<-Cód.produto Depost.";
		$mtz[]="<-Produto";
		$mtz[]="<-Barra";
		$mtz[]="<-Embalagem";
		$mtz[]="<-Fator";
		$mtz[]="<-Estoque na emb.";
		$mtz[]="<-Pendência na emb.";
		$mtz[]="<-Adicionar na emb.";
		$mtz[]="<-Disponível na emb.";
		$mtz[]="<-Cód. depositante";
		$mtz[]="<-Depositante";
		$mtz[]="<-Buffer";
		$mtz[]="<-Local ativo";
		$mtz[]="<-Lote liberado";
		$mtz[]="<-Período validade";
		$mtz[]="<-Dias vencidos";
		$mtz[]="<-Data vencimento";
		$mtz[]="<-Data fabricação";
		$mtz[]="<-Prazo validade";
		$mtz[]="<-Prazo comercial.";
		$mtz[]="<-Prazo crítico";
		$mtz[]="<-Ordem recebim.";
		$mtz[]="<-Tipo recebim.";
		$mtz[]="<-Classificação";
		$mtz[]="<-Nota fiscal";
		$mtz[]="<-Data entrada";
		$mtz[]="<-Data alocação";
		$mtz[]="<-Data bloqueio";
		$mtz[]="<-Motivo bloqueio";
		$mtz[]="<-Usuário bloqueio";
		$mtz[]="<-Data liberação";
		$mtz[]="<-Motivo liberação";
		$mtz[]="<-Usuário liberação";
		$mtz[]="<-Lote indústria";
		$mtz[]="<-Lote adicional";
		$mtz[]="<-Peso (KG)";
		$mtz[]="<-Setor";
		$mtz[]="<-Região";
		$mtz[]="<-Família";
		$mtz[]="<-Marca";
		$mtz[]="<-Id Produto";
		$mtz[]="<-Id Depositante";
		$mtz[]="<-Nº contrato";
		$mtz[]="<-Entidade pag.";
		$mtz[]="<-CNPJ";
		$mtz[]="<-Insc. Est.";
		$mtz[]="<-Pode importar?";
		foreach ($mtz as $key=>$value)
		{
			$mtz[$key] = $mtz[$key]."<br>".$key;
		}
		$html.=$o->tableRow($mtz,"header");
		$primeira = true;
		$umas = array();

		foreach ($linhas as $nr => $linha)
		{
			if (!$primeira)
			{
				$cols = explode(";", $linha);
				$erros = array();
				if (!$simular)
				{
					//02.006.010.05.02
					//2-14-010-4-1
					//2-99-061-1-1
					$procurarPosicao = "0".substr($cols[1],0,1).".0".substr($cols[1],1,2).".".substr($cols[1],3,3).".0".substr($cols[1],6,1).".0".substr($cols[1],7,1);

					$id_posicoes     = intval($posicoes[$procurarPosicao]);
					$id_itens_skus   = intval($itens[$cols[9]]);
					$data_validade   = gDBDateTime(str_replace("-20","-",str_replace("/","-",$cols[26])));
					$data_fabricacao = gDBDateTime(str_replace("-20","-",str_replace("/","-",$cols[27])));
					$data_entrada    = gDBDateTime(str_replace("-20","-",str_replace("/","-",$cols[35])));
					$nota_fiscal     = $cols[34];

					if ($id_posicoes==0)
					{
						$erros[]="Posição ñ encontr.";
					}
					if ($id_itens_skus==0)
					{
						$erros[]="Produto ñ encontr.";
					}

					if (count($erros)==0)
					{
						$uma  = array();
						$item = array();

						$item['inventario']              = '1';
						$item['tipo']                    = '+';
						$item['id_pessoas_proprietario'] = $id_pessoas_proprietario;
						$item['quantidade']              = $cols[5];
						$item['data']                    = $data_entrada;
						$item['data_fabricacao']         = $data_fabricacao;
						$item['data_validade']           = $data_validade;
						$item['observacoes']             = $nota_fiscal;
						$item['id_armazens']             = $id_armazens;
						$item['id_itens_skus']           = $id_itens_skus;
						$item['lote']                    = $cols[43];
						$item['valor']                   = 0;
						$item['m2']                      = 0;
						$item['m3']                      = 0;

						if ($cols[4]=='DANIFICADO')
						{
							$item['avariada'] = 1;
						}
						$uma['codigo_externo'] = $cols[3];
						$uma['id_posicoes']    = $id_posicoes;
						// $uma['posicaoWMS']     = $procurarPosicao;
						// $uma['posicaoSILT']    = $cols[1];
						$uma['posicionada']    = 1;
						$uma['complemento']    = "Importada SILT";
						$uma['item']           = $item;
						$umas[]=$uma;
					}
				}

				$mtz = array();
				foreach ($cols as $col)
				{
					$mtz[]="<-".$col;
				}

				if (count($erros)==0)
				{
					$mtz[]="<-Sim";
					$html.=$o->tableRow($mtz,"detail");
				} else {
					$mtz[]="<-".$o->small($o->ul($erros));
					$html.=$o->tableRow($mtz,"danger");
				}
			}
			$primeira=false;
		}
		$html.=$o->tableEnd();

//		gD($umas);
	} else {
		$html.=$o->msgInfo("Nenhum registro encontrado");
	}

	break;

    case RECALCULAR_SALDO:
        $html.=$o->msgTitle("Recalcular saldos para tabela umas_saldos");
        $frm = new gForm("{columns: 2}");
        $frm->add("{name: id_pessoas_proprietario; fieldLabel: Cliente; type: combo; items: " . $sp["combo_clientes"] . "}");
        $frm->add("{name: gPage; type: hidden; value: " . RECALCULAR_SALDO_TNL . ";}");
        $html .= $frm->render($o);

    break;

	case RECALCULAR_SALDO_TNL:

        $id_pessoas_proprietario = intval($_REQUEST['id_pessoas_proprietario']);

		$html.=$o->msgTitle("Recálculo da tabela umas_saldos");
		$persistencia = new UMA();

		// Ajustando UMAs que estão como ativas mas não tem saldo
		$sql = "SELECT * FROM (
					SELECT U.id, SUM(IF(UI.cancelada=0,UI.quantidade,0)) quantidade
					FROM umas U
					LEFT JOIN umas_itens UI ON U.id=UI.id_umas
					WHERE U.ativo=1
					GROUP BY U.id
				) UU WHERE UU.quantidade<=0";
		$rs = dbQuery($sql);
		$idUmas = array();
		foreach ($rs as $row)
		{
			$idUmas[]=$row['id'];
		}
		if (count($idUmas)>0)
		{
			$agora = date("Y-m-d H:i:s");
			$sql = "UPDATE umas SET data_desativacao='".$agora."', ativo=0, posicionada=0 WHERE id IN (".implode(",",$idUmas).")";
			dbQuery($sql);
			$html.=$o->msgFilter(count($idUmas)." UMAs foram desativadas por não possuirem saldo");
		}

        $extra = "";
        if ($id_pessoas_proprietario>0)
        {
            $extra = "AND id=$id_pessoas_proprietario";
            $sql = "SELECT UI.id_umas id FROM umas_saldos UI
                    INNER JOIN itens_skus SK ON UI.id_itens_skus=SK.id
                    INNER JOIN itens I ON SK.id_itens=I.id
                    WHERE I.id_pessoas_proprietario=".$id_pessoas_proprietario;
            $ids = array();
            foreach(dbQuery($sql) as $row)
            {
                $ids[] = $row['id'];
                if (count($ids)>100)
                {
                    dbQuery("DELETE FROM umas_saldos WHERE id_umas IN (".implode(",",$ids).")");
                    $ids = array();
                }
            }
            if (count($ids)>0)
            {
                dbQuery("DELETE FROM umas_saldos WHERE id_umas IN (".implode(",",$ids).")");
            }
        } else {
            dbQuery("TRUNCATE TABLE umas_saldos");
        }

        $sql = "SELECT * FROM pessoas WHERE cliente=1 AND situacao='Ativo' $extra";
        $rsClientes = dbFastQuery($sql);
        $cnt=0;
        foreach($rsClientes as $cliente)
        {
            $saldos = $persistencia->obtemUMAsComSaldo("UI.cancelada=0 AND UI.id_pessoas_proprietario=".$cliente['id']);
            foreach ($saldos as $saldo)
            {
                $cmps = array();
                $cmps['id_umas'] = $saldo['id'];
                $cmps['id_itens_skus'] = $saldo['id_itens_skus'];
                $cmps['lote'] = $saldo['lote'];
                $cmps['data_validade'] = $saldo['data_validade'];
                $cmps['data_fabricacao'] = $saldo['data_fabricacao'];
                $cmps['quantidade'] = $saldo['quantidade'];
                $cmps['reservada'] = $saldo['reservada'];
                $cmps['separada'] = $saldo['separada'];
                $cmps['avariada'] = $saldo['avariada'];
                $cmps['bloqueada'] = $saldo['bloqueada'];
                dbFastInsert('umas_saldos', $cmps);
                $cnt++;
            }

        }
		$html.=$o->msg("Total de UMAs processadas: ".$cnt);
	break;

	case TESTAR_JUNG:
		$html.=$o->msgTitle("Testar Jungheinrich");
		$frm=new gForm("{columns: 2}");
		$frm->add("{name: id_equipamentos; allowBlank: false; fieldLabel: Equipamento; type: combo; ; value: ".$_REQUEST['comando']."; items: ".$sp['combo_equipamentos']."}");
		$frm->add("{name: comando; type: upperText; value: ".$_REQUEST['comando']."}");
		$frm->add("{name: gPage; type: hidden; value: ".TESTAR_JUNG_TNL.";}");

		$html.=$frm->render($o);
		$html.=$o->msgInfo("Use a vírgula ao invés de ponto-e-vírgula");
		$html.=$o->msg("Exemplo: L,02.005.001.02.02");
	break;

	case TESTAR_JUNG_TNL:
		$cmd = gCleanField($_REQUEST['comando']);
		$json = "{cmd: ".$cmd."; id_equipamentos: ".intval($_REQUEST['id_equipamentos'])."}";
		jungComando($json);
		redirect($o->page."&gPage=".TESTAR_JUNG."&comando=".$cmd."&id_equipamentos=".$_REQUEST['id_equipamentos']);
	break;


	case BOMIX_LOTE_FABRICACAO:
		$sql = "SELECT UI.lote FROM umas_itens UI INNER JOIN umas U ON U.id=UI.id_umas
				WHERE U.ativo=1 AND UI.cancelada=0 GROUP BY UI.lote";
		$rs  = dbQuery($sql);
		$cnt = 0;
		$html.=$o->msgTitle("LOTE PARA DATA_FABRICAÇÃO/BOMIX");
		foreach ($rs as $row)
		{
			$data_fabricacao = calcularDataFabricacaoPeloLoteBomix($row['lote']);
			$data_validade   = date("Y-m-d", strtotime("+730 day", strtotime($data_fabricacao)));

			$sql = "UPDATE umas_itens SET data_fabricacao='".$data_fabricacao."',data_validade='".$data_validade."' WHERE cancelada=0 AND data_fabricacao<>'".$data_fabricacao."' AND lote='".$row['lote']."'";
			dbQuery($sql);
			$html.=$sql."<br>";

			$html.=$row['lote']." [".$row['data_fabricacao']." • ".$data_fabricacao."] --- [".$row['data_validade']." • ".$data_validade."]<br>";
			$cnt++;
		}
		$html.="<br>CONCLUÍDO. TOTAL MODIFICADO: ".$cnt;
	break;


	case BOMIX_POSICOES:
		$html.=$o->msgTitle("Atualizar posição - Bomix");
		$frm=new gForm("{columns: 2}");
		$frm->add("{name: arquivo; fieldLabel: Arquivo XLS; type: file;}");
		$frm->add("{name: gPage; type:hidden; value:".BOMIX_POSICOES_TNL."}");
		$html.=$frm->render($o);
	break;


	case BOMIX_POSICOES_TNL:
		$tipoArquivo = $_FILES["arquivo"]["type"];
		if($tipoArquivo=="text/csv")
		{
			$naoEncontradas = "";
			$arquivo = file($_FILES['arquivo']['tmp_name']);
			foreach ($arquivo as $lin) {
				$pos = explode(",",$lin);
				if($pos[0] == "Ativo")
					continue;

				$sql="SELECT id FROM posicoes WHERE codigo_barras='".trim($pos[5])."'";
				$rs=dbQuery($sql)[0];
				if($rs['id']){
					$picking = trim($pos[1]) == "Sim" ? 1 : 0;
					$obs = trim($pos[7]);
					$lado = trim($pos[6]);
					$aux = explode(".",trim($pos[14]));
					$rua = str_pad($aux[0],2,"0",STR_PAD_LEFT);
					$predio = str_pad($aux[1],2,"0",STR_PAD_LEFT);
					$andar = $aux[2];
					$novaPosicao = $rua.".".$predio.".".$andar;
					$sql="UPDATE posicoes
							SET codigo_barras='$novaPosicao',
							picking = $picking,
							observacoes = '$obs',
							quantidade_posicionada = 0,
							modulo='0',
							lado='$lado',
							rua='$rua',
							predio='$predio',
							andar='$andar'
							WHERE id=".$rs['id'];
					dbQuery($sql);
				}
				else{
					$naoEncontradas[]=trim($pos[5]);
				}
			}

			if($naoEncontradas){
				$html.=$o->msg("Posições não encontradas.");
				foreach ($naoEncontradas as $key => $value) {
					$html.=$value."<BR>";
				}
			}
		}

	break;


	case CORRIGIR:
		// AJUSTANDO umas com problema da inter
		// Inventário reCpetiu as contagens várias vezes

		$sql = "SELECT UI.* FROM umas_itens UI
				WHERE id_armazens=2 AND inventario=1 AND data>='2019-06-19 17:49:00' ORDER BY id_umas, id_itens_skus, lote, quantidade, data_validade, data_fabricacao";
		$rs = dbQuery($sql);
		$regAnterior = [];
		$apagar = [];
		foreach ($rs as $row)
		{
			if (
					$regAnterior['id_umas']==$row['id_umas'] &&
					$regAnterior['id_itens_skus']==$row['id_itens_skus'] &&
					$regAnterior['lote']==$row['lote'] &&
					$regAnterior['quantidade']==$row['quantidade'] &&
					$regAnterior['data_validade']==$row['data_validade'] &&
					$regAnterior['data_fabricacao']==$row['data_fabricacao']
				)
			{
				$apagar[] = $row['id'];
			}
			$regAnterior = $row;
		}

		$sql = "DELETE FROM umas_itens WHERE id IN (".implode(",",$apagar).")";
		dbQuery($sql);
		atualizarAtivacaoUMA(array_column($rs, 'id_umas'));
	break;


	case IMPORTAR_DOCUMENTACAO:

        if ($usrId != 1) {
            $html .= $o->msgDanger("Usuário sem permissão para realizar este procedimento");
            $html .= $backButton;
            break;
        }

        $frm = new gForm("{columns: 3}");
        $frm->add("{name: arquivoDocumentacao; type: file; allowBlank: true; fieldLabel: Documentação WMS;}");
        $frm->add("{name: gPage; type: hidden; value: " . PROCESSAR_DOCUMENTACAO . "}");
        $html .= $frm->render($o);

        $html .= $o->msgFilter("O tamanho máximo permitido para a inclusão de arquivos é de 5Mb");
        break;

    case PROCESSAR_DOCUMENTACAO:

        if ($_FILES['arquivoDocumentacao']['error'] == UPLOAD_ERR_NO_FILE) {
            $html .= $o->msgDanger("Nenhum arquivo foi selecionado");
            $html .= $backButton;
            break;
        }

        $tipoArquivo = strtolower(pathinfo($_FILES['arquivoDocumentacao']['name'], PATHINFO_EXTENSION));

        if ($tipoArquivo != 'md') {
            $html .= $o->msgDanger("O arquivo selecionado não é um arquivo .md");
            $html .= $backButton;
            break;
        }

        if ($_FILES['arquivoDocumentacao']['size'] > 5000000) { // 5000000 eh igual a 5MB
            $html .= $o->msgDanger("O arquivo excede o tamanho limite de 5MB");
            $html .= $backButton;
            break;
        }

        $diretorioDocumentacaoWms = $gPath . "res/system/chatbot/documentacao_wms";
        $diretorioDocumentacaoWmsAntigos = $gPath . "res/system/chatbot/documentacao_wms_antigos";

        if (!is_dir($diretorioDocumentacaoWmsAntigos)) {
            mkdir($diretorioDocumentacaoWmsAntigos, 0777, true);
        }

        $uniqId = uniqid();
        $documentacoesWmsMovidos = [];
        $documentacoesWmsExistentes = glob($diretorioDocumentacaoWms ."/manual_wms*");

        foreach ($documentacoesWmsExistentes as $documentacaoWmExistente) {
            if (!is_file($documentacaoWmExistente)) {
                continue;
            }

            $nomeDocumentacao = $diretorioDocumentacaoWmsAntigos
                . "/" . pathinfo($documentacaoWmExistente)['filename']
                . "_" . $uniqId
                . "." . pathinfo($documentacaoWmExistente)['extension'];
            $documentosWmsMovidos[] = $nomeDocumentacao;
            rename($documentacaoWmExistente, $nomeDocumentacao);
        }

        $destino = $diretorioDocumentacaoWms . "/manual_wms.md";

        if (!move_uploaded_file($_FILES['arquivoDocumentacao']['tmp_name'], $destino)) {
            $html .= $o->msgDanger('Erro ao mover o arquivo para o diretório de destino');

            foreach ($documentacoesWmsMovidos as $documentacaoWmsMovido) {
                if (!is_file($documentacaoWmsMovido)) {
                    continue;
                }

                $documentacaoWmsRestaurado = substr(pathinfo($documentacaoWmsMovido)['basename'], 0, strrpos(pathinfo($documentacaoWmsMovido)['basename'], '_'));
                $nomeOriginal = $diretorioDocumentacaoWms . "/" . $documentacaoWmsRestaurado . ".md";
                rename($documentacaoWmsMovido, $nomeOriginal);
            }

            $html .= $backButton;
            break;
        }

        chmod($destino, 0664);

        redirect($o->page . '&gPage=' . CONFIRMACAO_IMPORTACAO_DOCUMENTACAO . '&gId=' . $gId);
        break;


    case CONFIRMACAO_IMPORTACAO_DOCUMENTACAO:
        $html .= $o->msgSuccess("Importação realizada com sucesso");
        $html .= $o->button('{hint: Voltar; title: Voltar; sytle: info; icon: arrow-left; url: ' . $o->page . '&gPage=' . IMPORTAR_DOCUMENTACAO . '&gId=' . $gId . '}');
        break;


	case IMPORTAR_CONSOLIDADO:
		$html.=$o->msgTitle("Bomix - Importação de planilha consolidada");
		$frm=new gForm("{columns: 2}");
		$frm->add("{name: arquivo; fieldLabel: Arquivo XLS; type: file;}");
		$frm->add("{name: gPage; type:hidden; value:".IMPORTAR_CONSOLIDADO_TNL."}");
		$html.=$frm->render($o);
	break;

	case IMPORTAR_CONSOLIDADO_TNL:
		include_once $gPath."_classes/classes.php";
		$html.=$o->msgTitle("Bomix - Importação de planilha consolidada");
		$arquivo = ($_FILES['arquivo']['tmp_name']);

		// $path=$gPath."files/arquivos";
		// $arquivo=$gPath."files/arquivos/bomix_importacao.xlsx";
		// $existePasta=file_exists($path);
		// $existeArquivo=file_exists($arquivo);
		// if (!$existePasta)
		// {
		// 	$html.=$o->msgDanger("Pasta 'arquivos' não encontrada dentro de /wms/bomix/files");
		// 	return;
		// }
		// if (!$existeArquivo)
		// {
		// 	$html.=$o->msgDanger("Arquivo 'bomix_importacao.csv' não encontrado dentro da pasta /wms/bomix/files/arquivos");
		// 	return;
		// }
		//$inputFileName = $_FILES['arquivo']['tmp_name'];

		global $gPathLib;
		include_once($gPathLib.'/phpexcel/Classes/PHPExcel.php');
		$objPHPExcel = new PHPExcel();
		PHPExcel_Settings::setLocale('pt_br');
		$objPHPExcel = PHPExcel_IOFactory::load($arquivo);
		$max_lin = $objPHPExcel->setActiveSheetIndex(0)->getHighestRow();
		$csv = '';
		$lins = array();
		for($L=1; $L<=$max_lin; $L++)
		{
			$cols = array();
			for ($C=0; $C<24; $C++)
			{
				$celula = obtemColuna($C).$L;
				$col = gCleanField($objPHPExcel->getActiveSheet()->getCell($celula)->getCalculatedValue());
				$cols[] = $col;
			}
			$lins[]=implode(";",$cols);
		}
		$csv = implode("\n", $lins);
		$umaClass= new UMA();
		$row = 0;
		$verificaUmas[]=array();
		$umas=array();
		$registros=explode("\n", $csv);
		unset($registros[0]);
		unset($registros[1]);

		$dataAtualizacao = date("Y-m-d H:i:s");

		foreach ($registros as $linha_completa)
		{
			$linha=explode(";", $linha_completa);
			if (!empty($linha[0])  && $linha[0]<>"GRUPO")
			{
				if (in_array($linha[1], $verificaUmas))
				{
					/* Montar item */
					foreach ($umas as $uma)
					{
						if ($uma->gaiola->codigo == $linha[1])
						{
							$item=array();
							$item["gaiola"]=$linha[1];
							$item["uma"]=$linha[1];
							$item["codigo_sku"]=$linha[2];
							$item["descricao_item"]=$linha[3];
							//$item["area"]=$linha[4];
							$item["codigo_posicao"]=$linha[5];
							$item["lote"]=$linha[6];
							$item["quantidade"]=$linha[7];
							$item["data_atualizacao"]=$dataAtualizacao;
							$uma->gaiola->itens[]=$item;
						}
					}
				} else
				{
					$verificaUmas[]=$linha[1];
					$uma=new \stdClass();
					$uma->gaiola = new \stdClass();
					$uma->gaiola->posicao=$linha[5];
					$uma->gaiola->codigo=$linha[1];
					$uma->gaiola->itens=array();

					/* Montar item */
					$item=array();
					$item["gaiola"]=$linha[1];
					$item["uma"]=$linha[1];
					$item["codigo_sku"]=$linha[2];
					$item["descricao_item"]=$linha[3];
					//$item["area"]=$linha[4];
					$item["codigo_posicao"]=$linha[5];
					$item["lote"]=$linha[6];
					$item["quantidade"]=$linha[7];
					$item["data_atualizacao"]=$dataAtualizacao;
					$uma->gaiola->itens[]=$item;
					$umas[]=$uma;
				}
			}
		}
		if (count($umas)>0)
		{
			foreach ($umas as $uma)
			{
				$posicao=dbQuery("SELECT id FROM posicoes WHERE codigo_barras='{$uma->gaiola->posicao}'");
				$idPosicao=(count($posicao)>0)
					? $posicao[0]["id"]
					: 0;

				$campos['ativo']                  = 1;
				$campos['conferida']              = 1;
				$campos['id_tipos_umas']          = 1;
				$campos['id_posicoes']            = $idPosicao;
				$campos['id_posicoes_posicionar'] = 0;
				$campos['id_programacao']         = 0;
				$campos['id_programacao_itens']   = 0;
				$campos['id_armazens']            = 1;
				$campos['posicionada']            = ($idPosicao > 0) ? 1 : 0;
				$campos['data']                   = $dataAtualizacao;
				$campos['data_conferencia']       = $dataAtualizacao;
				$campos['id_contagens']           = 0;
				$idUMA=$umaClass->criaUMA($campos);

				foreach ($uma->gaiola->itens as $itemGaiola)
				{
					$sql = "SELECT id FROM umas WHERE codigo_barras='".$itemGaiola['uma']."'";
					$rst = dbQuery($sql);
					if (intval($rst[0]['id'])>0)
					{
						$idUMA = intval($rst[0]['id']);
						dbQuery("UPDATE umas SET ativo=1, id_posicoes=".$idPosicao.", posicionada=1, data_conferencia='".$dataAtualizacao."' WHERE id=".$idUMA);
					}

					$idUmaInformado = $itemGaiola['uma'];
					$sqlItem="SELECT SK.id, SK.id_itens, I.id_pessoas_proprietario FROM itens_skus SK
								  LEFT JOIN itens I ON I.id = SK.id_itens
								  WHERE SK.codigo='".$itemGaiola["codigo_sku"]."' AND SK.id_unidades='1'";
					$item=dbQuery($sqlItem);
					if (count($item)>0)
					{
						$item=$item[0];
						$item["id_itens_skus"]=$item["id"];
						$umaItem=$umaClass->preparaCamposDoItem($idUMA, $item);
						$umaItem["lote"]=$itemGaiola["lote"];
						if (substr($umaItem["lote"],0,3)=='CON')
						{
							$umaItem["data_fabricacao"]=date("Y-m-d");
							$umaItem["data_validade"]=date("Y-m-d", strtotime("+730 day"));
						} else {
							$umaItem["data_fabricacao"]=calcularDataFabricacaoPeloLoteBomix($itemGaiola["lote"]);
							$umaItem["data_validade"]=calcularDataValidade2AnosPelaDataFabricacao($umaItem["data_fabricacao"]);
						}
						$umaItem["faturar"]=1;
						$umaItem["id_armazens"]=intval($_SESSION['armazemAtualId']);
						$umaItem["tipo"]="+";
						$umaItem["quantidade"]=$itemGaiola["quantidade"];
						dbInsert("umas_itens", $umaItem);
						atualizarAtivacaoUMA($idUMA);

						$indicarPosicao=$umaClass->indicaPosicao($item["id_itens"]);
						if ($indicaPosicao>0)
						{
							$uma=dbQuery("SELECT posicionada FROM umas WHERE id='{$idUMA}'")[0];
							if ($uma["posicionada"]==0)
							{
								$mtz=array();
								$mtz["id_posicoes_posicionar"]=$indicaPosicao;
								dbUpdate("umas", $mtz, $idUMA);
							}
						}
					} else
					{
						/* Inativar UMA */
						$mtz=array();
						$mtz["ativo"]=0;
						dbUpdate("umas", $mtz, $idUMA);
					}
				}
			}
			$html.=$o->msgSuccess("Arquivo importado com sucesso");
		} else
		{
			$html.=$o->msgSuccess("Nenhum registro encontrado");
		}
	break;

	case IMPORTAR_CONSOLIDADO_CONFIRMAR:

	break;

	case LIMPA_PROGRAMACAO:
		$html.=$o->msgTitle("Limpar Programações de Entrada inconsistentes");
		limpaProgramacoesComProblema();
		$html.=$o->msgSuccess("Limpeza efetuada");
	break;



	case LIMPAR_SALDO_WMS_NAO_TEM_PROTHEUS:
		$html.=$o->msgTitle("Limpar saldo que tem no WMS, mas não existe no protheus");
		$frm=new gForm("{columns: 3}");
		$frm->add("{name: codigo; fieldLabel: Código; type: text; hint: Código do item}");
		$frm->add("{name: gPage; type: hidden; value: ".LIMPAR_SALDO_WMS_NAO_TEM_PROTHEUS_TNL.";}");
		$html.=$frm->render($o);
	break;

	case LIMPAR_SALDO_WMS_NAO_TEM_PROTHEUS_TNL:
		$html.=$o->msgTitle("Limpar saldo que tem no WMS, mas não existe no protheus");
		$sql="SELECT itens_skus.*
				FROM itens_skus
				INNER JOIN itens ON itens.id = itens_skus.id_itens
				WHERE itens_skus.codigo NOT IN(SELECT codigo_sku FROM totvs_saldo_atual WHERE local IN('CT','LO','SE') GROUP BY codigo_sku) ";
		if($codigo<>"")
			$sql.=" AND itens_skus.codigo='$codigo' ";
		$sql.="GROUP BY itens_skus.id";
		$rs=dbQuery($sql);
		foreach ($rs as $row) {
			$persistencia = new UMA();
			$saldos = $persistencia->obtemUMAsComSaldo("UI.id_itens_skus=".$row['id']);
			foreach ($saldos as $saldo) {
				$persistencia->zerarUMA($saldo['id'],$saldo['id_itens_skus']);
			}
		}
		$html.=$o->msgSuccess("Limpeza efetuada");
	break;

	case EXPURGO:
		$html.=$o->msgTitle("Expurgo");
		$html.=$o->msgSubTitle("Transferir dados não utilizados há $mesesDeExpurgo meses para arquivo morto");
		$html.=$o->msg("Selecione quais tipos de dados deverão ser expurgados:");
		$frm = new gForm("{columns: 6}");
		$frm->add("{name: gPage; type: hidden; value: ".(EXPURGO+1)."}");
		$frm->add("{name: programacoes_antigas; hint: Mais de um ano; type: checkbox; value: 0}");
		$frm->add("{name: umas_inativas; type: checkbox; value: 0}");
		$frm->add("{name: veiculos_antigos; type: checkbox; value: 0}");
		//$frm->add("{name: notas_fiscais_antigas; type: checkbox; value: 0}");
		$frm->add("{name: inventarios_antigos; type: checkbox; value: 0}");
		$frm->add("{name: tabelas_temporarias; type: checkbox; value: 0}");
		$frm->add("{name: log; type: checkbox; value: 0}");
		$frm->add("{name: mesesDeExpurgo; fieldLabel: Meses a ignorar; type: text; value: 12}");
		$html.=$frm->render($o);
	break;

	case (EXPURGO+1):
		$umas_inativas = gDBCheck($_REQUEST['umas_inativas']);
		$programacoes_antigas = gDBCheck($_REQUEST['programacoes_antigas']);
		$veiculos_antigos = gDBCheck($_REQUEST['veiculos_antigos']);
		$notas_fiscais_antigas = gDBCheck($_REQUEST['notas_fiscais_antigas']);
		$inventarios_antigos = gDBCheck($_REQUEST['inventarios_antigos']);
		$tabelas_temporarias = gDBCheck($_REQUEST['tabelas_temporarias']);
		$mesesDeExpurgo = intval($_REQUEST['mesesDeExpurgo']);
		$log = gDBCheck($_REQUEST['log']);



		$html.=$o->msgTitle("Expurgo");
		$html.=$o->msgSubTitle("Transferir dados não utilizados há $mesesDeExpurgo meses para arquivo morto");
		$html.=$o->hr();

		// Primeiro verifica se tabelas existem e se são iguais:
		$tabelas = [ "programacao", "programacao_itens", "umas", "umas_itens", "umas_movimentos", "veiculos_acessos", "veiculos_acessos_programacoes" ];
		$todosIguais = true;
		$erros = array();
		$PDO  = new PDO( 'mysql:host=localhost;dbname=wms_'.$EMPRESA, 'web', 'web' );
		$sql = "SHOW TABLES";
		$rs = $PDO->query( $sql );
		$tabelasNoBanco= $rs->fetchAll( PDO::FETCH_ASSOC );
		//gDR($tabelasNoBanco);
		foreach ($tabelas as $tabela)
		{
			$tabelaExiste = false;
			foreach($tabelasNoBanco as $row)
			{
				if ($row["Tables_in_wms_".$EMPRESA]==$tabela)
				{
					$tabelaExiste = true;
				}
			}
			if ($tabelaExiste)
			{
				$sql = "describe ".$tabela;
				$rs = $PDO->query( $sql );
				$rsO = $rs->fetchAll( PDO::FETCH_ASSOC );
				$sql = "describe expurgo_".$tabela;
				$rs = $PDO->query( $sql );
				$rsD = $rs->fetchAll( PDO::FETCH_ASSOC );

				foreach ($rsO as $key=>$value)
				{
					if ($rsO[$key]['Field']!=$rsD[$key]['Field'])
					{
						$todosIguais = false;
						$erros[] = "Estruturas diferentes: ".$tabela." e expurgo_".$tabela;
					}
				}

			} else {
				$erros[] = "Tabela não existe: expurgo_$tabela";
			}

		}

		if (count($erros))
		{
			$html.=$o->msgDanger("Infelizmente não será possível expurgar pois ocorreram alguns erros: <br><br> ".$o->ul($erros));
			$html.=$o->msg("Edite a estrutura das tabelas para que fiquem iguais, <b>com excessão do campo ID que não pode ser autonumerado</b>.");

		} else {
			$html.="<div class='row'>";

			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.="		Programações antigas";
			$html.="	</div>";
			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.='		<div id="programacoes_antigas_num">---</div>';
			$html.="	</div>";
			$html.="	<div class='col-xs-8 col-sm-8 col-md-8 col-lg-8'>";
			$html.='		<div class="progress"><div id="programacoes_antigas" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>';
			$html.="	</div>";

			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.="		UMAs inativas";
			$html.="	</div>";
			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.='		<div id="umas_inativas_num">---</div>';
			$html.="	</div>";
			$html.="	<div class='col-xs-8 col-sm-8 col-md-8 col-lg-8'>";
			$html.='		<div class="progress"><div id="umas_inativas" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>';
			$html.="	</div>";

			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.="		Veículos antigos";
			$html.="	</div>";
			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.='		<div id="veiculos_antigos_num">---</div>';
			$html.="	</div>";
			$html.="	<div class='col-xs-8 col-sm-8 col-md-8 col-lg-8'>";
			$html.='		<div class="progress"><div id="veiculos_antigos" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>';
			$html.="	</div>";

			// $html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			// $html.="		NFs antigas";
			// $html.="	</div>";
			// $html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			// $html.='		<div id="notas_fiscais_antigas_num">---</div>';
			// $html.="	</div>";
			// $html.="	<div class='col-xs-8 col-sm-8 col-md-8 col-lg-8'>";
			// $html.='		<div class="progress"><div id="notas_fiscais_antigas" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>';
			// $html.="	</div>";

			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.="		Inventários antigos";
			$html.="	</div>";
			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.='		<div id="inventarios_antigos_num">---</div>';
			$html.="	</div>";
			$html.="	<div class='col-xs-8 col-sm-8 col-md-8 col-lg-8'>";
			$html.='		<div class="progress"><div id="inventarios_antigos" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>';
			$html.="	</div>";

			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.="		Tabelas temporárias";
			$html.="	</div>";
			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.='		<div id="tabelas_temporarias_num">---</div>';
			$html.="	</div>";
			$html.="	<div class='col-xs-8 col-sm-8 col-md-8 col-lg-8'>";
			$html.='		<div class="progress"><div id="tabelas_temporarias" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>';
			$html.="	</div>";

			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.="		Logs";
			$html.="	</div>";
			$html.="	<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2'>";
			$html.='		<div id="logs_num">---</div>';
			$html.="	</div>";
			$html.="	<div class='col-xs-8 col-sm-8 col-md-8 col-lg-8'>";
			$html.='		<div class="progress"><div id="logs" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>';
			$html.="	</div>";


			$html.="</div>";

			if ($umas_inativas)
			{
				$js = "
				umas_ttl = 0;
				function umas_inativas()
				{
					$.ajax({
						url: '".$o->page."&gAjax=1&mesesDeExpurgo=$mesesDeExpurgo&cmd=umas_inativas',
						context: document.body
					})
					.done(function(data,textStatus){
						valor = parseInt(data);
						if (umas_ttl==0)
						{
							umas_ttl = valor;
						} else {
							x = Math.round((100*valor)/umas_ttl);
							$('#umas_inativas').css('width', x +'%').attr('aria-valuenow', x);
							$('#umas_inativas_num').html(data+' ('+x+'%)');
						}
						if (valor>0)
						{
							umas_inativas();
						}
					});
				}
				umas_inativas();
				";
				$o->addJavascript($js);
			}

			if ($programacoes_antigas)
			{
				$js = "
				programacoes_ttl = 0;

				function programacoes_antigas()
				{
					$.ajax({
						url: '".$o->page."&gAjax=1&mesesDeExpurgo=$mesesDeExpurgo&cmd=programacoes_antigas',
						context: document.body
					})
					.done(function(data,textStatus){
						valor = parseInt(data);
						if (programacoes_ttl==0)
						{
							programacoes_ttl = valor;
						} else {
							x = Math.round((100*valor)/programacoes_ttl);
							$('#programacoes_antigas').css('width', x +'%').attr('aria-valuenow', x);
							$('#programacoes_antigas_num').html(data+' ('+x+'%)');
						}
						if (valor>0)
						{
							programacoes_antigas();
						}
					});
				}
				programacoes_antigas();
				";
				$o->addJavascript($js);
			}

			if ($veiculos_antigos)
			{
				$js = "
				veiculos_ttl = 0;

				function veiculos_antigos()
				{
					$.ajax({
						url: '".$o->page."&gAjax=1&mesesDeExpurgo=$mesesDeExpurgo&cmd=veiculos_antigos',
						context: document.body
					})
					.done(function(data,textStatus){
						valor = parseInt(data);
						if (veiculos_ttl==0)
						{
							veiculos_ttl = valor;
						} else {
							x = Math.round((100*valor)/veiculos_ttl);
							$('#veiculos_antigos').css('width', x +'%').attr('aria-valuenow', x);
							$('#veiculos_antigos_num').html(data+' ('+x+'%)');
						}
						if (valor>0)
						{
							veiculos_antigos();
						}
					});
				}
				veiculos_antigos();
				";
				$o->addJavascript($js);
			}


			if ($inventarios_antigos)
			{
				$js = "
				inventarios_antigos_ttl = 0;

				function inventarios_antigos()
				{
					$.ajax({
						url: '".$o->page."&gAjax=1&mesesDeExpurgo=$mesesDeExpurgo&cmd=inventarios_antigos',
						context: document.body
					})
					.done(function(data,textStatus){
						valor = parseInt(data);
						if (inventarios_antigos_ttl==0)
						{
							inventarios_antigos_ttl = valor;
						} else {
							x = Math.round((100*valor)/inventarios_antigos_ttl);
							$('#inventarios_antigos').css('width', x +'%').attr('aria-valuenow', x);
							$('#inventarios_antigos_num').html(data+' ('+x+'%)');
						}
						if (valor>0)
						{
							inventarios_antigos();
						}
					});
				}
				inventarios_antigos();
				";
				$o->addJavascript($js);
			}

			if ($tabelas_temporarias)
			{
				$js = "
				tabelas_temporarias_ttl = 0;

				function tabelas_temporarias()
				{
					$.ajax({
						url: '".$o->page."&gAjax=1&mesesDeExpurgo=$mesesDeExpurgo&cmd=tabelas_temporarias',
						context: document.body
					})
					.done(function(data,textStatus){
						valor = parseInt(data);
						if (tabelas_temporarias_ttl==0)
						{
							tabelas_temporarias_ttl = valor;
						} else {
							x = Math.round((100*valor)/tabelas_temporarias_ttl);
							$('#tabelas_temporarias').css('width', x +'%').attr('aria-valuenow', x);
							$('#tabelas_temporarias_num').html(data+' ('+x+'%)');
						}
						if (valor>0)
						{
							tabelas_temporarias();
						}
					});
				}
				tabelas_temporarias();
				";
				$o->addJavascript($js);
			}

			if ($log)
			{
				$js = "
				log_ttl = 0;

				function log()
				{
					$.ajax({
						url: '".$o->page."&gAjax=1&mesesDeExpurgo=$mesesDeExpurgo&cmd=log',
						context: document.body
					})
					.done(function(data,textStatus){
						valor = parseInt(data);
						if (log_ttl==0)
						{
							log_ttl = valor;
						} else {
							x = Math.round((100*valor)/log_ttl);
							$('#log').css('width', x +'%').attr('aria-valuenow', x);
							$('#log_num').html(data+' ('+x+'%)');
						}
						if (valor>0)
						{
							log();
						}
					});
				}
				log();
				";
				$o->addJavascript($js);
			}


		}

	break;
	case CAPTURAR_UMAS_PRESAS_RESERVA:
		$sql = "SELECT id FROM umas WHERE ativo=1 AND posicionada=1";
		$rs = dbQuery($sql);
		foreach ($rs as $uma) {
			$sql = "
				SELECT  os,
				        codigo,
				        max(reservada) reservada,
				        max(separada) separada,
				        max(avariada) avariada,
				        max(bloqueada) bloqueada,
				        id_mov_reserva
				FROM (
				    SELECT
				            programacao.os,
				            itens_skus.codigo,
				            SUM(umas_itens.quantidade) AS qtd,
				            umas_itens.reservada,
				            umas_itens.separada,
				            umas_itens.avariada,
				            umas_itens.bloqueada,
				            min(umas_itens.id) id_mov_reserva
				        FROM umas_itens
				        LEFT JOIN umas ON umas.id = umas_itens.id_umas
				        LEFT JOIN programacao ON programacao.id = umas_itens.id_programacao
				        LEFT JOIN itens_skus ON itens_skus.id = umas_itens.id_itens_skus
				        WHERE umas_itens.id_umas = ".$uma['id']."
				            AND umas_itens.cancelada = 0
				        GROUP BY umas_itens.id_programacao,
				            umas_itens.id_itens_skus,
				            umas_itens.reservada,
				            umas_itens.separada,
				            umas_itens.avariada,
				            umas_itens.bloqueada
				        HAVING qtd <> 0
				) t GROUP BY os;";
			$rs2 = dbQuery($sql);
			foreach ($rs2 as $os) {
				if ($os['reservada'] && $os['separada']) {
					echo 'UMA'.$uma['id']." tem movimentacao indevida para esta OS".$os['os'].' Item cod.: '.$os['codigo'].'<br>';
					$sql = "SELECT
							id_umas,
							id_umas_origem,
							id_programacao,
							id_programacao_itens,
							id_pessoas_proprietario,
							'1' AS id_pessoas_criou,
							id_pessoas_cancelou,
							id_armazens,
							id_veiculos_acessos,
							id_notas_itens,
							id_notas_itens_saida,
							id_itens_skus,
							id_tipos_operacao,
							codigo_externo,
							'-' AS tipo,
							cancelada,
							faturar,
							reservada,
							separada,
							avariada,
							bloqueada,
							'0' AS entrada,
							'1' AS saida,
							data,
							data_cancelamento,
							data_fabricacao,
							data_validade,
							data_validade_antiga,
							(-quantidade) AS quantidade,
							peso_liquido,
							peso_bruto,
							m2,
							m3,
							temperatura,
							valor,
							quantidade_temp,
							lote,
							`serial`,
							inventario,
							'UMAs separadas sem saída da reserva' AS observacoes,
							ajuste_saldo FROM umas_itens WHERE id= ".$os['id_mov_reserva'];;
					$result = dbQuery($sql)[0];
					$result = excluirIndicesNumericos($result);

					$sql = "INSERT INTO umas_itens (".implode(', ', array_keys($result)).") VALUES ('".implode("', '", array_values($result))."')";
					atualizarAtivacaoUMA($result['id_umas']);

					echo $sql.'<br><br>';
					if ($_REQUEST['ajustar']) {
						$result = dbQuery($sql);
						echo "executou<br>";
					}
				}
			}
		}
		exit;
		break;


	case CORRIGE_SALDO_ANALITICO_NEGATIVO:
		/*
			Na Intermarítima, o proprietário Lubritec estava com saldo negativo no relatorio de saldo analitico por movimentacao indevida de UMAs.
			Esta ferramenta propõe a correção deste problema
		*/

		$html = $o->msgTitle('Análise de umas e notas');
		if (!$_REQUEST['analisar']) {
			$form = new gForm("{columns: 2}");
			$form->add('{name: id_pessoas_proprietario; fieldLabel: Proprietário; type: combo; items: '.$sp['combo_proprietarios'].'; allowBlank: false; value: 2070;}');
			$form->add('{name: uma; fieldLabel: UMA; type: text;}');
			$form->add('{name: data_de; fieldLabel: Data criação de; type: date;}');
			$form->add('{name: data_ate; fieldLabel: Data criação até; type: date;}');
			$form->add('{name: analisar; value: 1; type: hidden;}');
			$form->add('{name: gPage; id: gPage; type: hidden; value: '.CORRIGE_SALDO_ANALITICO_NEGATIVO.';}');
			$html .= $form->render($o);
			break;
		}

		$where = array();

		if ($_REQUEST['data_de'] && $_REQUEST['data_de'] != '00-00-00')
			$where[] = "date(umas.data) >= ".gDBDate($_REQUEST['data_de']);
		if ($_REQUEST['data_ate'] && $_REQUEST['data_ate'] != '00-00-00')
			$where[] = "date(umas.data) <= ".gDBDate($_REQUEST['data_ate']);
		if ($_REQUEST['id_pessoas_proprietario'])
			$where[] = "umas_itens.id_pessoas_proprietario = ".$_REQUEST['id_pessoas_proprietario'];
		if ($_REQUEST['uma'])
			$where[] = "umas.codigo_barras = '".$_REQUEST['uma']."' OR umas.id = ".$_REQUEST['uma'];
		$where = implode(" AND ", $where);


		//consulto todas as umas do cliente (proprietario)
		$sql = "SELECT umas.id
			FROM umas
			LEFT JOIN umas_itens ON umas_itens.id_umas = umas.id
			WHERE ".$where."
			AND umas_itens.cancelada = 0
			GROUP BY umas.id LIMIT 1000000;";
			echo $sql.'<br>';
		$rs = dbquery($sql);
		// var_dump($rs);
		// exit;

echo '<pre>';
		foreach ($rs as $row) {
			echo "<br>Verificando UMA".$row['id']."<br>";
			//consultar em cada UMA suas repectivas + separacao - separacao
			/*
			$sql .= "SELECT id, id_umas, lote, tipo, cancelada, faturar, reservada, separada, avariada, bloqueada, entrada, quantidade, valor
			FROM umas_itens
			WHERE id_umas = '".$row['id']."'
			AND separada = '1'
			AND cancelada = '0';";
			$movimentacaoSeparacao = dbQuery($sql);
			*/

			//agrupo
			//pego as movimentacoes que estao erradas

			$sql = "SELECT id,
				        id_programacao_itens,
				        sum(quantidade) qtd,
				        separada,
				        tipo,
				        id_itens_skus,
				        id_pessoas_proprietario,
				        lote,
				        data_fabricacao,
				        data_validade,
				        id_notas_itens,
				        valor
				FROM umas_itens
				WHERE id_umas = '".$row['id']."'
					AND cancelada = 0
					AND separada = 1
				GROUP BY id_programacao_itens,
				    id_itens_skus,
				    id_pessoas_proprietario,
				    lote,
				    data_fabricacao,
				    data_validade,
				    id_notas_itens
				HAVING qtd > 0;";
			$movimentacoesSeparacao = dbQuery($sql);
			foreach ($movimentacoesSeparacao as $mov) {
				//procura o par
				$sql = "
					SELECT
					    *
					FROM
					    umas_itens
					WHERE
					    id_programacao_itens = '".$mov['id_programacao_itens']."'
					    AND separada = 1
					    AND tipo     = '-'
					    AND cancelada = 0
					    AND (
					    	lote  <> '".$mov['lote']."'
					    	OR data_fabricacao <> '".$mov['data_fabricacao']."'
					    	OR data_validade   <> '".$mov['data_validade']."'
					    	OR id_notas_itens  <> '".$mov['id_notas_itens']."'
					    ) AND id_itens_skus  = '".$mov['id_itens_skus']."'
					    AND id_pessoas_proprietario  = '".$mov['id_pessoas_proprietario']."'
					    AND id_umas  = '".$row['id']."'
						AND abs(quantidade) = '".$mov['qtd']."'
						";
				$rsa = dbQuery($sql);

				if (
					count($rsa) == 1
					&& !in_array($row['id'], array(1377543, 1381958, 1418458, 1423346, 1432985))
				) {
					echo "<br>Corrigindo UMA: ".$row['id'].'<br>';
					$sql = "UPDATE umas_itens
						SET valor = '".$mov['valor']."',
						data_fabricacao = '".$mov['data_fabricacao']."',
						data_validade = '".$mov['data_validade']."',
						id_notas_itens = '".$mov['id_notas_itens']."' WHERE id =".$rsa[0]['id'];
					dbquery($sql);
					atualizarAtivacaoUMA($mov['id_umas']);
					print_r([[$sql, $mov]], 0);
				} elseif (count($rsa) > 1) {
					/*
					echo '<br>'.$sql.'<br>';
					$sql = "SELECT  id,
									id_programacao_itens,
									id_notas_itens,
									lote,
									data_fabricacao,
									data_validade,
									valor,
									quantidade,
									tipo
							FROM umas_itens
							WHERE id_programacao_itens = '".$mov['id_programacao_itens']."'
							    AND separada = 1
							    AND cancelada = 0
							    AND (
							    	lote  <> '".$mov['lote']."'
							    	OR data_fabricacao <> '".$mov['data_fabricacao']."'
							    	OR data_validade   <> '".$mov['data_validade']."'
							    	OR id_notas_itens  <> '".$mov['id_notas_itens']."'
							    ) AND id_itens_skus  = '".$mov['id_itens_skus']."'
							    AND id_pessoas_proprietario  = '".$mov['id_pessoas_proprietario']."'
							    AND id_umas  = '".$row['id']."'
								AND abs(quantidade) = '".$mov['qtd']."'";
					$rsb = dbQuery($sql);
					$movimentosEntSeparacao = array_filter($rsb, function($movimentacao) {
						return $movimentacao['tipo'] == '+';
					});
					gD([$rsb]);

					$movimentosSaiSeparacao = array_filter($rsa, function($movimentacao) {
						return $movimentacao['tipo'] == '-';
					});

					if ((count($movimentosEntSeparacao)/count($movimentosSaiSeparacao)) == 1) {
						foreach ($movimentosEntSeparacao as $chave => $movE) {
							$sql = "UPDATE umas_itens
								SET valor = '".$movimentosSaiSeparacao[$chave]['valor']."',
								data_fabricacao = '".$movimentosSaiSeparacao[$chave]['data_fabricacao']."',
								data_validade = '".$movimentosSaiSeparacao[$chave]['data_validade']."',
								id_notas_itens = '".$movimentosSaiSeparacao[$chave]['id_notas_itens']."' WHERE id =".$$movimentosSaiSeparacao[$chave]['id'];
							#dbquery($sql);
						}
					}
					*/
				}
			}
			//exit;


		}

exit;
		break;


	case DESATIVAR_UMAS_SEM_SALDO:
		$sql = "
		   SELECT umas.id
		   FROM umas
		   LEFT JOIN umas_itens ON umas_itens.id_umas = umas.id
		   WHERE 	umas.ativo = 1
		          	AND umas_itens.cancelada = 0
		            AND umas.id_armazens = 1
		   GROUP BY umas.id
		   HAVING count(umas_itens.id) > 0
		   ORDER BY umas.id DESC";
		$umas = dbQuery($sql);
		$persistencia = new UMA("{os: false}");
		foreach ($umas as $uma) {
		    $where = 'U.id = '.$uma['id'];
		    $rs = $persistencia->obtemUMAsComSaldo($where, true, 0, "", 0, "", 0, "", "", "");
		    if (!$rs) {
		        echo "<br>UMA".$uma['id'];
		        $sql = "UPDATE umas
		                SET     ativo = 0,
		                       data_desativacao = NOW(),
		                       observacoes = 'Desativ. via script-sem saldo'
		                WHERE id = ".$uma['id'];
		        if ($_REQUEST['corrigir'] == 1) {
		            dbQuery($sql);
		            atualizarAtivacaoUMA($uma['id']);
		        }
		    }
		}

        exit;
		break;


	case AJUSTE_POSICAO_SIEMENS:
		//consulto posicoes nao existentes/erradas na tabela posicoes
		//se posicao nao existir, cria
		//alimenta saldo
		//se posicao existir
			//da update modificando codigo e rua/limit 1
			//da update na UMA pra constar o novo codigo_barras/limit 1
		//se posicao existir mais de uma pega a primeira
			//da update modificando codigo e rua/limit 1
			//da update na UMA pra constar o novo codigo_barras/limit 1
		/*	-- caso 1 -- posicao nao existe */
		$codigos = array('1 10 4 9 3',
'1 10 5 9 2',
'1 10 5 9 3',
'1 10 6 9 1',
'1 10 6 9 2',
'1 10 7 9 1',
'1 110 2 1 3',
'1 110 2 2 1',
'1 110 2 2 2',
'1 110 2 2 3',
'1 110 3 1 1',
'1 110 3 1 2',
'1 110 3 1 3',
'1 110 3 2 1',
'1 110 3 2 2',
'1 110 3 2 3',
'1 110 4 1 1',
'1 110 4 1 2',
'1 110 4 1 3',
'1 110 4 2 1',
'1 110 4 2 2',
'1 110 4 2 3',
'1 110 5 1 1',
'1 110 5 1 2',
'1 110 5 1 3',
'1 110 5 2 1',
'1 110 5 2 2',
'1 110 5 2 3',
'1 110 6 1 1',
'1 110 6 1 2',
'1 110 6 1 3',
'1 110 6 2 1',
'1 110 6 2 2',
'1 110 6 2 3',
'1 110 7 1 1',
'1 110 7 1 2',
'1 110 7 1 3',
'1 110 7 2 1',
'1 110 7 2 2',
'1 110 7 2 3',
'1 120 2 7 1',
'1 120 2 7 2',
'1 120 2 8 1',
'1 120 2 8 2',
'1 120 2 8 3',
'1 120 2 9 1',
'1 120 2 9 2',
'1 120 2 9 3',
'1 120 3 3 1',
'1 120 3 3 2',
'1 120 3 3 3',
'1 120 3 4 1',
'1 120 3 4 2',
'1 120 3 4 3',
'1 120 3 5 1',
'1 120 3 5 2',
'1 120 3 5 3',
'1 120 3 6 1',
'1 120 3 6 2',
'1 120 3 6 3',
'1 120 3 7 1',
'1 120 3 7 2',
'1 120 3 7 3',
'1 120 3 8 1',
'1 120 3 8 2',
'1 120 3 8 3',
'1 120 3 9 1',
'1 120 3 9 2',
'1 120 3 9 3',
'1 120 4 3 1',
'1 120 4 3 2',
'1 120 4 4 1',
'1 120 4 4 2',
'1 120 4 5 1',
'1 120 4 5 2',
'1 120 4 5 3',
'1 120 4 6 1',
'1 120 4 6 2',
'1 120 4 6 3',
'1 120 4 7 1',
'1 120 4 7 2',
'1 120 4 7 3',
'1 120 4 8 1',
'1 120 4 8 2',
'1 120 4 8 3',
'1 120 4 9 1',
'1 120 4 9 2',
'1 120 4 9 3',
'7 1 33 1 17 1 35 1 1');
		// echo '<pre>';var_dump(count($codigos));exit;
		foreach ($codigos as $codigoInventariado) {
			$sql = "SELECT id FROM umas_verificar WHERE posicao ='{$codigoInventariado}'";
			$foiInventariado = dbQuery($sql)[0];
			if (!$foiInventariado) {
				echo "Nao consta na umas_verificar {$codigoInventariado} <br>"; continue;
			}

			$codigoErrado = explode(' ', $codigoInventariado);
			$codigoErrado[1] = substr($codigoErrado[1], 0, -1);
			$codigoErrado = implode(' ', $codigoErrado);

			$sql = "SELECT COUNT(id) qtd FROM posicoes WHERE codigo_barras = '{$codigoErrado}'";
			$posicaoExisteIndevidamente = dbQuery($sql)[0]['qtd'];
			 // echo '<pre>';var_dump('Codigo certo: ' . $codigoInventariado . '   codigo errado: ' . $codigoErrado . '<br>'); continue;
			// $posicaoExisteIndevidamente = ($posicaoExisteIndevidamente == 1);
			if ($posicaoExisteIndevidamente == 1) {
				$sql = "SELECT id FROM umas WHERE codigo_barras = '{$codigoErrado}'";
				$idUMA = dbQuery($sql)[0]['id'];
				if ($idUMA) {
					$novoCodigoDaPosicao = $codigoInventariado;
					$novaRuaDaPosicao = explode(' ', $codigoInventariado)[1];
					//corrige posicao
					$sql = "
						UPDATE posicoes
						SET codigo_barras = '$novoCodigoDaPosicao',
							rua = '$novaRuaDaPosicao'
						WHERE codigo_barras = '{$codigoErrado}';";
					dbQuery($sql);
					//corrige codigo da UMA
					$sql = "
						UPDATE umas
						SET codigo_barras = '$novoCodigoDaPosicao'
						WHERE codigo_barras = '{$codigoErrado}'";
					$rs = dbQuery($sql);
					echo "Posicao {$codigoErrado} corrigido para {$novoCodigoDaPosicao} com rua {$novaRuaDaPosicao}<br>";
				} else {
					echo "UMA de posicao {$codigoInventariado} não existe <br>";
				}

				//UPDATE posicoes SET codigo_barras = '$novoCodigoDaPosicao', rua='$novaRuaDaPosicao' WHERE codigo_barras = '$antigoCodigoDaPosicao';
			} elseif ($posicaoExisteIndevidamente == 0) {
				$sql = "SELECT id FROM posicoes WHERE codigo_barras = '{$codigoInventariado}'";
				$rs = dbQuery($sql);
				if (!$rs) {
					$criarDepois[] = $codigoInventariado;
				}
			}
			// '1 12 2 6 3';
			//SELECT * FROM posicoes WHERE codigo_barras ='1 12 2 6 3'; //tem posicao, so uma

		}
		var_dump("Criar depois: '" . implode("', '", $criarDepois . "'"));
		 // nao tem umas_verificar
		// SELECT * FROM umas WHERE codigo_barras ='1 12 2 6 3'; // tem UMA
		// UPDATE umas SET codigo_barras = '$novoCodigoPosicao' WHERE codigo_barras = '$antigoCodigoDaPosicao';
		// SELECT id_itens_skus, quantidade FROM umas_itens WHERE id_umas = 274;


exit;
		break;

	case FILTRO_ALIMENTACAO_UMA:
		$html .= $o->msgTitle("Alimentação de UMA");
		$frm = new gForm("{columns: 2}");
		$frm->add("{name: posicoes; fieldLabel: Arquivo CSV; type: file;}");
		$frm->add("{name: zerarSaldo; fieldLabel: Zerar saldo; type: checkbox;}");
		$frm->add("{name: gPage; type: hidden; value: " . ALIMENTACAO_UMA . "}");
		$html .= $frm->render($o);
		break;

	case ALIMENTACAO_UMA:
		$persistencia = new UMA();

		$html .= $o->msgTitle("Alimentação de UMA");

		$file = file_get_contents($_FILES['posicoes']['tmp_name']);

		if ($_FILES['posicoes']['type'] != 'text/csv') {
			$html .= $o->msgDanger('Importar somente arquivos CSV');
			$html .= $backButton;
			break;
		}

		$arquivo = explode("\n", $file);
		foreach ($arquivo as $chave => $coluna) {
			if (!$coluna) {
				unset($arquivo[$chave]);
			}
		}

		foreach ($arquivo as $numeroLinha => $rs) {
			$coluna = explode(';', $rs);

			$uma = $coluna[0];
			$verificarUma = dbQuery("
				SELECT 	umas.id AS id_umas,
						posicoes.id AS id_posicoes
				FROM umas
				LEFT JOIN posicoes ON posicoes.codigo_barras = umas.codigo_barras
				WHERE posicoes.codigo_barras = '{$uma}'")[0];

			if (!$verificarUma['id_umas'] || !$verificarUma['id_posicoes'] && false) {
				$html .= $o->msgDanger("UMA {$uma} inválida, verificar se a UMA existe");
				$html .= $backButton;
				//break 2;
			}

			$sku = $coluna[1];
			$verificarItem = dbQuery("SELECT itens_skus.id, itens.id_pessoas_proprietario
				FROM itens_skus LEFT JOIN itens ON itens.id = itens_skus.id_itens
				WHERE itens_skus.codigo_barras = '{$sku}'
					AND itens_skus.ativo = 1")[0];

			$quantidade = $coluna[2];

			if ($verificarUma && $verificarItem) {
				if (gDBcheck($_REQUEST['zerarSaldo'])) {
					$saldo = $persistencia->obtemUMAsComSaldo("U.codigo_barras='{$uma}'");
					if ($saldo[0]['quantidade'] > 0) {
						$zerarUma[]  = $verificarUma[0]['id_umas'];
						$zerarItem[] = $verificarItem[0]['id'];
					}
				}

				$mtz = array();
				$mtz['id_umas'] = $verificarUma['id_umas'];
				$mtz['id_itens_skus'] = $verificarItem['id'];
				$mtz['quantidade'] = $quantidade;
				$mtz['data'] = date('Y-m-d H:i:s');
				$mtz['id_pessoas_proprietario'] = $verificarItem['id_pessoas_proprietario'];
				$mtz['id_pessoas_criou'] = $usrId;
				$mtz['id_armazens'] = $_SESSION['armazemAtualId'];
				$mtz['tipo'] = '+';
				$mtz['faturar'] = '1';
				$mtz['entrada'] = '1';
				$mtz['entrada'] = '1';
				$mtz['inventario'] = '1';
				$mtz['lote'] = '';
				$mtz['serial'] = '';
				$mtz['observacoes'] = 'Alim. ' . date('Y-m-d');
				if ($quantidade) {//nao inserir saldo 0 do item
					$insereInventario .= "('" . implode("','", array_values($mtz)) . "'),";
				}
			}
			if (!$verificarUma) {
				$erros[] = 'UMA ' . $uma . ' não cadastrada';
			}

			if (!$verificarItem) {
				$erros[] = 'Item ' . $sku . ' não cadastrado ou desativado';
			}
		}

		if ($erros) {
			$html .= $o->msgDanger($o->ul($erros));
			$html .= $backButton;
			break;
		}

		if (gDBCheck($_REQUEST['zerarSaldo'])) {
			foreach ($zerarUma as $chave => $uma) {
				$zerarUmaComSaldo = $persistencia->zerarUMA($uma, $zerarItem[$chave]);
			}
		}

		$insereInventario = "INSERT INTO umas_itens (" . implode(",", array_keys($mtz)) . ") VALUES " . substr($insereInventario, 0, -1);
		dbQuery($insereInventario);
		atualizarAtivacaoUMA(array_column($mtz, 'id_umas'));
		$html .= $o->msgSuccess("UMAs alimentadas com sucesso");
		$html .= $backButton;
		break;
}



function criarPosicao(){}
function criarUMA($idPosicao){}
function alimentarSaldo($idUma, $idItensSkus, $quantidade){}


function obtemColuna($C)
{
	if ($C<=25)
	{
		$chr = 65+$C;
		$col=chr($chr);
	}
	elseif($C<51){
		$chr = 65+($C-25);
		$col="A".chr($chr);
	}
	else{
		$chr = 65+($C-50);
		$col="B".chr($chr);
	}

	return($col);
}


