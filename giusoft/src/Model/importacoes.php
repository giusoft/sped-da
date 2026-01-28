<?php

include_once "res/_classes/classes.php";

define("IMPORTAR_PROCESSA",200);
define("VER_EXEMPLOS",  300);

/*  -------------- IMPORTAÇÕES -------------- */

class Importacoes
{
	public $grupo               = "";
	public $modelo              = "";
	public $cabecalho           = "";
	public $registros           = "";
	public $validacoes          = "";
	public $multiplosCabecalhos = false;
	public $erros               = array();
	protected $persistencia;

	function __construct ()
	{
		// $this->persistencia = new Persistencia();
	}

	function obtemFormato($id)
	{
		$sql = "SELECT * FROM importacoes WHERE id='".$id."'";
		$rs = dbQuery($sql);
		$this->modelo['id']=$id;
		$this->modelo['grupo']=$rs[0]['grupo'];
		$this->modelo['descricao']=$rs[0]['descricao'];
		$sql = "SELECT * FROM importacoes_cabecalho WHERE id_importacoes='".$id."' ORDER BY ordem";
		$rs = dbQuery($sql);

		foreach ($rs as $row)
		{
			$this->modelo['cabecalho'][$row['nome']]=array(
				'nome'        => $row['nome'],
				'descricao'   => $row['descricao'],
				'tipo'        => $row['tipo'],
				'tamanho'     => $row['tamanho'],
				'campo_chave' => $row['campo_chave'],
				'campo_util'  => $row['campo_util'],
				'validacao'   => $row['validacao']
			);
		}
		$sql = "SELECT * FROM importacoes_registros WHERE id_importacoes='".$id."' ORDER BY ordem";
		$rs = dbQuery($sql);
		foreach ($rs as $row)
		{
			$this->modelo['registros'][$row['nome']]=array(
				'descricao'   => $row['descricao'],
				'tipo'        => $row['tipo'],
				'tamanho'     => $row['tamanho'],
				'campo_chave' => $row['campo_chave'],
				'campo_util'  => $row['campo_util'],
				'validacao'   => $row['validacao']
			);
		}
		//gD($sai);
	}


	function geraDados($deOnde)
	{
		$campos = array();
		foreach ($deOnde as $key=>$value)
		{
			$txt = '';
			switch($value['tipo'])
			{
				case 'Texto':
					$txt = 'AAAAAA('.$value['tamanho'].')';
					break;
				case 'Texto maiúsculas':
					$txt = 'AAAAAA('.$value['tamanho'].')';
					break;
				case 'Número inteiro':
					$txt = '999('.$value['tamanho'].')';
					break;
				case 'Número decimal':
					$txt = '999,99('.$value['tamanho'].')';
					break;
				case 'Data':
					$txt = 'DD-MM-YY';
					break;
				case 'Data hora':
					$txt = 'DD-MM-YY HH:MM';
					break;
				case 'Lógico':
					$txt = '1/0';
					break;
				case 'CNPJ':
					$txt = '99.999.999/9999-99';
					break;
				case 'CPF':
					$txt = '999.999.999-99';
					break;
			}
			$campos[$key]=$txt;
		}
		return($campos);
	}

	function exemploXML()
	{
		global $o;
		$txt = '<?xml version="1.0" encoding="UTF-8"?>'.$o->n;
		$txt.= '<'.gCleanField($_REQUEST['g']).'>'.$o->n;
		if (isset($this->modelo['cabecalho']))
		{
			$txt.='	<cabecalho>'.$o->n;
			foreach ($this->geraDados($this->modelo['cabecalho']) as $key=>$row)
			{
				$txt.='		<'.$key.'>'.$row.'</'.$key.'>'.$o->n;
			}
			$txt.='	</cabecalho>'.$o->n;
		}
		if (isset($this->modelo['registros']))
		{
			$txt.='	<registros>'.$o->n;
			$reg = '';
			for ($a=0; $a<2; $a++)
			{
				$reg.='		<registro>'.$o->n;
				foreach ($this->geraDados($this->modelo['registros']) as $key=>$row)
				{
					$reg.='			<'.$key.'>'.$row.'</'.$key.'>'.$o->n;
				}
				$reg.='		</registro>'.$o->n;
			}
			$txt.=$reg;
			$txt.='	</registros>'.$o->n;
		}
		$txt.= '</'.gCleanField($_REQUEST['g']).'>'.$o->n;
		return($txt);
	}

	function exemploJSON()
	{
		global $o;
		$txt.='{'.$o->n;
		if (isset($this->modelo['cabecalho']))
		{
			$txt.='	"cabecalho":{'.$o->n;
			foreach ($this->geraDados($this->modelo['cabecalho']) as $key=>$row)
			{
				$t[] ='"'.$key.'":"'.$row.'"';
			}
			$txt.='		'.implode(','.$o->n.'		',$t).$o->n;
			$txt.='	},'.$o->n;
		}
		if (isset($this->modelo['registros']))
		{
			$txt.='	"registros":'.$o->n;
			$txt.='	['.$o->n;
			$linhas = '';
			for ($a=0; $a<2; $a++)
			{
				$t = '';
				$linha = '';
				$linha.='		{'.$o->n;
				foreach ($this->geraDados($this->modelo['registros']) as $key=>$row)
				{
					$t[] ='"'.$key.'":"'.$row.'"';
				}
				$linha.='			'.implode(','.$o->n.'			',$t).$o->n;
				$linha.='		}';
				$linhas[]=$linha;
			}
			$txt.=implode(", ".$o->n.'',$linhas).$o->n;
			$txt.='	]'.$o->n;
		}
		$txt.='}'.$o->n;
		return($txt);
	}

	function exemploCSV()
	{
		global $o;
		if (isset($this->modelo['cabecalho']))
		{
			$dados = $this->geraDados($this->modelo['cabecalho']);
			$txt.='#'.implode(";", array_keys($dados)).$o->n;
			$txt.=implode(";", array_values($dados)).$o->n;
		}
		$fez = false;
		if (isset($this->modelo['registros']))
		{
			for ($a=0; $a<2; $a++)
			{
				$dados = $this->geraDados($this->modelo['registros']);
				if (!$fez)
				{
					$txt.='#'.implode(";", array_keys($dados)).$o->n;
				}
				$fez = true;
				$txt.=implode(";", array_values($dados)).$o->n;
			}
		}
		return($txt);
	}

	/* VALIDAÇÃO DOS CAMPOS E FORMATAÇÃO PARA O PADRÃO SQL */
	function formataValor($valor, $formato)
	{
		global $sp;
		switch($formato['tipo'])
		{
			case 'Texto':
				$valor = gCleanField(substr($valor, 0, $formato['tamanho']));
				break;
			case 'Maiúsculas':
				$valor = gCleanField(substr($valor, 0, $formato['tamanho']));
				break;
			case 'Texto maiúsculas':
				$valor = gCleanField(substr(strtoupper($valor), 0, $formato['tamanho']));
				break;
			case 'Número':
				$valor = gCleanField(substr($valor, 0, $formato['tamanho']));
				break;
			case 'Número inteiro':
				$valor = intval($valor);
				if (substr($formato['validacao'],0,5)=="combo")
				{
					if (!isset($this->validacoes[$formato['validacao']]))
					{
						$sql = $sp[$formato['validacao']];
						$this->validacoes[$formato['validacao']] = dbQuery($sql);
					}
					$ok = false;
					foreach ($this->validacoes[$formato['validacao']] as $row)
					{
						if ($row['id']==$valor)
						{
							$ok = true;
						}
					}
					if (!$ok)
					{
						$this->erros[]="Erro na validação do campo: ". $formato['descricao']." - valor inválido: $valor";
					}
				} else if ($formato['validacao']==">0")
				{



					if ($valor <= 0)
					{
						$this->erros[] = "Erro na validação do campo: " . $formato['descricao'] . " - o valor deve ser maior que 0";
					}
				}
				break;
			case 'Número decimal':
				$valor = gDBFloat($valor);
				if ($formato["validacao"]==">0")
				{
					if ($valor <= 0)
					{
						$this->erros[] = "Erro na validação do campo: " . $formato['descricao'] . " - o valor deve ser maior que 0";
					}
				}
				break;
			case 'Data':

				if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
					$valor = gDBDate($valor);
				}

				if (
					$formato['validacao'] == ">0"
					&& strtotime($valor) == strtotime("0000-00-00")
				) {
					$this->erros[] = "Erro na validação do campo: " . $formato["descricao"] . " - valor inválido: {$valor}";
				}

				if ($formato['validacao'] == ">HOJE") {
					if (strtotime($valor) <= strtotime(date('Y-m-d'))) {
						$this->erros[] = "Erro na validação do campo: " . $formato["descricao"] . " - a data deve ser maior do que a data de hoje.";
					}
				}
				break;

			case 'Data hora':
				$valor = gDBDateTime($valor);

				if (
					$formato['validacao'] == ">0"
					&& strtotime($valor)  == strtotime("0000-00-00 00:00:00")
				) {
					$this->erros[] = "Erro na validação do campo: " . $formato["descricao"] . " - valor inválido: {$valor}";
				}

				if ($formtado['validacao']==">HOJE")
				{
					if (strtotime($valor) <= strtotime(date('Y-m-d'))) {
						$this->erros[] = "Erro na validação do campo: " . $formato["descricao"] . " - a data deve ser maior do que a data de hoje.";
					}
				}
				break;
			case 'Lógico':
				$valor = gDBCheck($valor);
				break;
			case 'CNPJ':
				$valor = trim(str_replace('-','',str_replace('/','',str_replace(',','',str_replace('.','',$valor)))));
				break;
			case 'CPF':
				$valor = trim(str_replace('-','',str_replace('/','',str_replace(',','',str_replace('.','',$valor)))));
				break;
			case 'Unidade do Item':
				$valor=preg_replace("/[^a-zA-Z0-9]/", "", $valor);
				$rs=dbQuery("SELECT id FROM unidades WHERE sigla='$valor'");
				$valor = $rs[0]['id'];
			break;
		}
		return($valor);
	}

	/* CONFIGURAÇÃO DA TABELA PARA EXIBIÇÃO DO USUÁRIO FINAL */
	function campoTable ($valor, $tipo, $validar = false)
	{
		switch($tipo) {
			case 'Texto':
				$valor = "<- " . $valor;
				break;
			case 'Maiúsculas':
				$valor = "<- " . $valor;
				break;
			case 'Texto maiúsculas':
				$valor = "<- " . $valor;
				break;
			case 'Número':
				$valor = "-> " . $valor;
				break;
			case 'Número inteiro':
				$valor = "-> " . $valor;
				break;
			case 'Número decimal':
				$valor = ($validar) ? gFloat($valor) : $valor;
				$valor = "-> " . $valor;
				break;
			case 'Data':
				$valor = ($validar) ? gDate($valor) : $valor;
				$valor = "<> " . $valor;
				break;
			case 'Data hora':
				$valor = ($validar) ? gDateTime($valor) : $valor;
				$valor = "<> " . $valor;
				break;
			case 'Lógico':
				$valor = "-> " . $valor;
				break;
			default:
				$valor = "<- " . $valor;
				break;
		}
		return $valor;
	}

	/* FUNÇÂO PARA VALIDAR SE REGISTRO JÁ NÃO FOI CARREGADO BASEADO NOS CAMPOS CHAVE */
	function validarArquivo ($campos, $registro, $tabela) {
		global $gParam;

		$where = "id > 0";
		foreach ($campos as $campo) {
			$chave = $campo["nome"];
			$valor = $registro[$campo["nome"]];
			$where .= " AND $chave = '$valor'";
		}

		if ($gParam['PERMITIR_ITENS_IGUAIS']['ativo'] && $registro['id_pessoas_proprietario']) {
			$where .= " AND id_pessoas_proprietario = " . $registro['id_pessoas_proprietario'];
		}

		$totalExistente = dbFastQuery("SELECT COUNT(id) AS total FROM $tabela WHERE $where")[0]['total'];

		return $totalExistente;
	}

	/* FUNÇÃO PARA VERIFICAR QUAIS CAMPOS A TABELA POSSUI DOS PASSADOS NO ARQUIVO */
	function validarCampos ($tabela, $registro) {
		global $usrId;
		$itens = dbQuery("SELECT * FROM $tabela limit 1");
		$camposItem  = $itens[0];
		$novoItem 	 = [];
		foreach ($camposItem as $keyCampo => $valueCampo)
		{
			if (isset($registro[$keyCampo]))
				$novoItem[$keyCampo] = $registro[$keyCampo];

			if ($keyCampo=="data_cadastro")
				$novoItem["data_cadastro"] = date('Y-m-d H:i:s');

			if ($keyCampo=="id_pessoas_criou")
				$novoItem["id_pessoas_criou"] = $usrId;
		}
		return $novoItem;
	}

	/* PERSISTENCIA DA PROGRAMAÇÃO */
	function novaProgramacao ($tipoProgramacao)
	{
		global $usrId, $gPath, $id_filial, $gParam;
		include_once $gPath."res/_classes/operacao.php";
		$programacao = new Programacao();

		$agora = date('Y-m-d H:i:s');
		$dataPrevisao = $agora;
		if ($gParam['ACRESCIMO_MINUTOS_DATA_PREVISAO_IMPORTAR']['ativo']) {
			$dataPrevisao = date('Y-m-d H:i:s', strtotime('+' . ((int) ($gParam['ACRESCIMO_MINUTOS_DATA_PREVISAO_IMPORTAR']['valor'])) . ' minute'));
		}

		if ($this->multiplosCabecalhos)
		{
			foreach ($this->cabecalho as $cab)
			{
				$numero_cliente=$cab['numero_cliente'];
				$idProprietario = intval($cab["id_pessoas_proprietario"]);
				if ($idProprietario==0)
				{
					$cnpj = $cab["cnpj_depositante"];
					if ($cnpj=="")
					{
						$cnpj = $cab["cnpj_emitente"];
					}
					if ($cnpj=="")
					{
						$cnpj = $cab["cnpj"];
					}
					if ($cnpj<>"")
					{
						$sql = "SELECT P.id FROM pessoas_juridicas J LEFT JOIN pessoas P ON J.id_pessoas=P.id WHERE J.cnpj='".$cnpj."'";
						$idProprietario = intval(dbQuery($sql)[0]['id']);
					}
				}
				if ($idProprietario>0)
				{

					// Só cria se tiver um numero novo do pedido do cliente...
					$sql = "
							SELECT id FROM programacao
							WHERE id_pessoas_proprietario=$idProprietario
							AND (data_cadastro>'".date("Y-m-d H:i:s", strtotime("-90 day"))."')
							AND cancelada=0
							AND (numero_cliente='".$numero_cliente."' OR recno='".$numero_cliente."')";

					$rs = dbQuery($sql);
					if (count($rs)==0)
					{
						foreach ($this->registros as $registro)
						{

							if ($registro['numero_cliente']==$cab["numero_cliente"])
							{
								$codigoItem = $registro["codigo"];
								$idUnidade=0;
								if (isset($registro['unidade']))
								{
									$sql="SELECT id FROM unidades WHERE sigla='".gToUpper(gCleanField($registro['unidade']))."' LIMIT 1";
									$unidade=dbQuery($sql);
									if (count($unidade)>0)
									{
										$unidade=$unidade[0];
										$idUnidade=$unidade['id'];
									}
								} else
								{
									$idUnidade  = intval($registro["id_unidades"]);
								}
								$where=array();
								$where[]="(itens.id_pessoas_proprietario=".$idProprietario.")";
								$where[]="(itens_skus.codigo='".$codigoItem."' OR itens_skus.codigo_barras='".$codigoItem."' OR itens_skus.codigo_barras_alternativo='".$codigoItem."')";
								if (intval($idUnidade)>0)
								{
									$where[]="(itens_skus.id_unidades=".$idUnidade.")";
								}
								$filtro=implode(" AND ", $where);
								$sql = "
										SELECT
											itens_skus.id
										FROM itens
										INNER JOIN itens_skus ON itens.id = itens_skus.id_itens
										WHERE {$filtro}
									";
								$sku = dbQuery($sql);
								if (count($sku)==0)
								{
									$this->erros[] = "O item de código [$codigoItem] não está cadastrado.";
								}
							}
						}

						if (count($this->erros)==0)
						{
							$id_filial=$_SESSION["filialAtualId"];
							$os = $programacao->novaOS();
							$cabecalho["id_pessoas_criou"]        = $usrId;
							$cabecalho["data_cadastro"]           = $agora;
							if (isset($cabecalho["data_previsao"]) && intval($cabecalho["data_previsao"])>0)
							{
								$cabecalho["data_previsao"]=gDBDate($cabecalho["data"]);
								$cabecalho["data_previsao_inicial"]=gDBDate($cabecalho["data"]);
							} else
							{
								$cabecalho["data_previsao"]           = $dataPrevisao;
								$cabecalho["data_previsao_inicial"]   = $dataPrevisao;
							}

							$cabecalho["id_tipos_programacao"]    = $tipoProgramacao;
							$cabecalho["id_filial"]             = $_SESSION["filialAtualId"];//$_SESSION["Usrfilial"];
							$cabecalho["os"]                      = $os;
							$cabecalho["numero_cliente"]          = $numero_cliente;
							$cabecalho["recno"]=$numero_cliente;
							$cabecalho['id_pessoas_proprietario'] = $idProprietario;
							$cabecalho["liberada"]                = 0;
							$cabecalho["importada"]				  = 1;
							$idProgramacao  = dbInsert("programacao", $cabecalho, true);
							foreach ($this->registros as $registro)
							{
								if ($registro['numero_cliente']==$cab["numero_cliente"])
								{
									$codigoItem = $registro["codigo"];
									$idUnidade=0;
									if (isset($registro['unidade']))
									{
										$sql="SELECT id FROM unidades WHERE sigla='".gToUpper(gCleanField($registro['unidade']))."' LIMIT 1";
										$unidade=dbQuery($sql);
										if (count($unidade)>0)
										{
											$unidade=$unidade[0];
											$idUnidade=$unidade['id'];
										}
									} else
									{
										$idUnidade  = intval($registro["id_unidades"]);
									}
									$where=array();
									$where[]="(itens.id_pessoas_proprietario=".$idProprietario.")";
									$where[]="(itens_skus.codigo='".$codigoItem."' OR itens_skus.codigo_barras='".$codigoItem."' OR itens_skus.codigo_barras_alternativo='".$codigoItem."')";
									if (intval($idUnidade)>0)
									{
										$where[]="(itens_skus.id_unidades=".$idUnidade.")";
									}
									$filtro=implode(" AND ", $where);
									$sql = "SELECT
												itens_skus.id
											FROM itens
											INNER JOIN itens_skus ON itens.id = itens_skus.id_itens
											WHERE {$filtro}";

									$sku = dbQuery($sql);
									$reg["id_programacao"] = $idProgramacao;
									$reg["id_itens_skus"]  = $sku[0]["id"];
									$reg["quantidade"]     = $registro["quantidade"];
									$reg["valor"]          = $registro["valor"];
									dbInsert("programacao_itens", $reg);
								}
							}
						}
					} else {
						$this->erros[] = "Este número de pedido já foi adicionado: [$numero_cliente].";
					}
				} else {
					$this->erros[] = "O CNPJ deste pedido não está cadastrado.";
				}
			}
		} else
		{

			$idProprietario = intval($this->cabecalho["id_pessoas_proprietario"]);
			if ($idProprietario==0)
			{
				$cnpj = $this->cabecalho["cnpj_depositante"];
				if ($cnpj=="")
				{
					$cnpj = $this->cabecalho["cnpj_emitente"];
				}
				if ($cnpj=="")
				{
					$cnpj = $this->cabecalho[0]["cnpj"];
				}
				if ($cnpj<>"")
				{
					$sql = "SELECT P.id FROM pessoas_juridicas J LEFT JOIN pessoas P ON J.id_pessoas=P.id WHERE J.cnpj='".$cnpj."'";
					$idProprietario = intval(dbQuery($sql)[0]['id']);
				}
			}

			if ($idProprietario==0)
			{

				// Verificar se não existe no modelo de importação.
				$idProprietario=gFieldById("importacoes", intval($_REQUEST["modelo"]), "id_pessoas_proprietario");
			}

			$idProprietario = (int) ($idProprietario ?: $_REQUEST['id_pessoas_proprietario']);
			if ($idProprietario==0) {
				$this->erros[]="<b>A importação não pode continuar pois não foi possível encontrar o seu proprietário</b>";
			}


			// Antes de criar a programação verificar se os itens existem.
			foreach ($this->registros as $registro)
			{
				$codigoItem=$registro["codigo"];
				if (!empty($codigoItem))
				{
					$where=array();
					$where[]="(SK.codigo='{$codigoItem}' OR SK.codigo_barras='{$codigoItem}' OR SK.codigo_barras_alternativo='{$codigoItem}')";
					$where[]="(I.id_pessoas_proprietario=".$idProprietario.")";
					if (isset($registro["id_unidades"]))
					{
						$where[]="SK.id_unidades=".intval($registro["id_unidades"]);
					}
					if (isset($registro["unidade"]))
					{
						$where[]="U.sigla='".$registro["unidade"]."'";
					}
					$where[]="(SK.ativo=1)";
					$where[]="(I.ativo=1)";
					$where=implode(" AND ", $where);
					$sql="SELECT
						I.ativo ativo_item, SK.codigo, SK.id id_itens_skus,
						SK.ativo ativo_sku
						FROM itens_skus SK
						LEFT JOIN itens I ON I.id = SK.id_itens
						LEFT JOIN unidades U ON U.id = SK.id_unidades
						WHERE {$where}";
					$sku=dbQuery($sql);
					if (count($sku)==0)
					{
						$this->erros[]="Item (<b>{$codigoItem}</b>) não encontrado. Por favor faça o cadastro dos item, e verifique se está ativo.";
					} else if (count($sku)>1)
					{
						$this->erros[]="Houve duplicidade de código no item (<b>{$codigoItem}</b>). Por favor verifique e tente novamente.";
					} else
					{
						// Verificar se item está ativo
						$sku=$sku[0];
						if ($sku["ativo_item"]==0 || $sku["ativo_sku"]==0)
						{
							$this->erros[]="Item (<b>{$codigoItem}</b>) se encontra inativo. Por favor ative e tente novamente.";
						}
					}

					if (isset($registro["uma"]))
					{
						$where=array();
						$where[]="(U.codigo_barras='".formataUMA($registro["uma"])."')";
						$where[]="(UI.id_itens_skus=".intval($sku["id_itens_skus"]).")";
						$where[]="(UI.cancelada=0)";
						$where[]="(U.ativo=1)";
						$sql="SELECT
								U.id
							  FROM umas_itens UI
							  LEFT JOIN umas U ON U.id = UI.id_umas
							  WHERE ".implode(" AND ", $where);
						$uma=dbQuery($sql);
						if (count($uma)==0)
						{
							$this->erros[]="<b>".formataUMA($registro["uma"])."</b> Não tem o item: <b>".$sku["codigo"]."</b>";
						}
					}
				}
			}

			if (count($this->erros) == 0) {

				$id_filial=$_SESSION["filialAtualId"];
				$os = $programacao->novaOS();
				$cabecalho["id_pessoas_criou"]      = $usrId;
				$cabecalho["data_cadastro"]         = $agora;

				if (intval($this->cabecalho["data"])>0)
				{
					$cabecalho["data_previsao"]=$this->cabecalho["data"];
					$cabecalho["data_previsao_inicial"]=$this->cabecalho["data"];
				} else {
					$cabecalho["data_previsao"]         = $dataPrevisao;
					$cabecalho["data_previsao_inicial"] = $dataPrevisao;
				}

				$cabecalho["id_tipos_programacao"]  = $tipoProgramacao;
				$cabecalho["id_filial"]           = $_SESSION["filialAtualId"];

				$cabecalho["os"]                    = $os;
				$cabecalho["numero_cliente"]        = $this->cabecalho['numero_cliente'];
				$cabecalho["recno"] = $this->cabecalho["numero_cliente"];
				$cabecalho['id_pessoas_proprietario'] = $idProprietario;
				$idProgramacao  = dbInsert("programacao", $cabecalho, true);
				foreach ($this->registros as $registro)
				{
					$codigoItem = $registro["codigo"];
					if (!empty($codigoItem))
					{
						$where=array();
						$where[]="(SK.codigo='{$codigoItem}' OR SK.codigo_barras='{$codigoItem}' OR SK.codigo_barras_alternativo='{$codigoItem}')";
						$where[]="(I.id_pessoas_proprietario=".$idProprietario.")";
						$where[]="(I.ativo=1 AND SK.ativo=1)";
						if ($registro["id_unidades"])
						{
							$where[]="SK.id_unidades=".intval($registro["id_unidades"]);
						}
						if ($registro["unidade"])
						{
							$where[]="U.sigla='".$registro["unidade"]."'";
						}
						$where=implode(" AND ", $where);

						// Obtem sku pelo código e proprietário.
						$sql="SELECT
								SK.id id_itens_skus
							FROM itens_skus SK
							LEFT JOIN itens I ON I.id = SK.id_itens
							LEFT JOIN unidades U ON U.id = SK.id_unidades
							WHERE {$where}";
						$sku = dbQuery($sql);
						$reg["id_programacao"] = $idProgramacao;
						$reg["id_itens_skus"]  = $sku[0]["id_itens_skus"];
						if (isset($registro["quantidade"]))
						{
							$reg["quantidade"]     = gDBFloat($registro["quantidade"]);
						}
						if (isset($registro["valor"]))
						{
							$reg["valor"]          = $registro["valor"];
						}
						if (isset($registro["lote"]))
						{
							$reg["lote"]=$registro["lote"];
						}

						if (isset($registro["uma"]))
						{
							$reg["uma"]=formataUMA($registro["uma"]);
						}

						if (isset($registro["uma_destino"]))
						{
							$reg["uma_destino"]=$registro["uma_destino"];
						}

						if (isset($registro["data_validade"]))
						{
							$reg["data_validade"]=$registro["data_validade"];
						}

						if (isset($registro["data_fabricacao"]))
						{
							$reg["data_fabricacao"]=$registro['data_fabricacao'];
						}
						dbInsert("programacao_itens", $reg);
					}
				}
			}
		}
	}

	/* PERSISTÊNCIA DO ITEM */
	function novoItem ()
	{
		global $usrId;
		$tabela = "itens";
		/* OBTENDO CAMPOS PARA VALIDAÇÃO */
		//echo "<pre>";var_dump($this->registros);exit;
		$camposChave = dbQuery("SELECT nome FROM importacoes_registros where campo_chave = '1' and id_importacoes = '1'");
		$idProprietario = intval($this->cabecalho["id_pessoas_proprietario"]);
		if ($idProprietario==0)
		{
			$idProprietario = intval($_REQUEST['id_pessoas_proprietario']);
		}
		if ($idProprietario==0)
		{
			$this->erros[]="<b>A importação não pode continuar pois não foi possível encontrar o seu proprietário</b>";
			return;
		}

		foreach ($this->registros as $key => $registro) {
			$registro["id_pessoas_proprietario"] = $idProprietario;
			$idUnidade = $registro["id_unidades"];
			if (isset($registro['unidade'])) {
				$sql = "SELECT id FROM unidades WHERE sigla = '" . gToUpper(gCleanField($registro['unidade'])) . "' LIMIT 1";
				$idUnidade = dbFastQuery($sql)[0]['id'];
			}
			/* VALIDAR SE O ITEM NÃO JÁ FOI IMPORTADO */
			$testeItem = $this->validarArquivo($camposChave, $registro, "itens");

			$persistencia = new Itens();
			$skuDuplicado = $persistencia->verificarDuplicataSku($registro['id_pessoas_proprietario'], '', $registro['codigo_barras']);
			if ($skuDuplicado) {
				continue;
			}

			/* SALVAR ITEM E CRIAR UM SKU PARA O ITEM*/
			if ($testeItem != 0) {
				continue;
			}

			$registro['descricao'] = $registro['nome'];
			$novoItem = $this->validarCampos("itens", $registro);
			$idItem = dbInsert("itens", $novoItem, true);

			$registro["id_itens"] = $idItem;
			$registro["id_unidades"] = (int) $idUnidade;
			$registro["quantidade"] = gDBFloat($registro["quantidade"]);
			$registro["peso_liquido"] = gDBFloat($registro["peso_liquido"]);
			$registro["peso_bruto"] = gDBFloat($registro["peso_bruto"]);
			$registro["altura"] = gDBFloat($registro["altura"]);
			$registro["largura"] = gDBFloat($registro["largura"]);
			$registro["comprimento"] = gDBFloat($registro["comprimento"]);
			$registro = $this->validarCampos("itens_skus", $registro);

			dbInsert("itens_skus", $registro);
		}
	}


	/* PROCESSAR E TRATAR ARQUIVO XML */

	function processaXML($arquivo)
	{
		global $html,$o;
		$html.=$o->msgSubTitle("Processamento de arquivo CSV");
		$xml = simplexml_load_string($arquivo);
		/* VERIFICAR CONVERSÃO DO ARQUIVO */
		$sai = is_string($arquivo) && (is_object($xml) || is_array($xml))
			? true
			: false;
		if ($sai) {
			/* TRATAR CABEÇALHO */
			if (isset($this->modelo['cabecalho']))
			{
				$cabecalho = $xml->cabecalho[0];
				if ($cabecalho<>"")
				{
					$cab = [];
					foreach ($this->modelo["cabecalho"] as $key => $value)
					{
						$cab[$key] = $this->formataValor($cabecalho->$key, $value);
					}
					$this->cabecalho = $cab;
				} else
				{
					$this->erros[] = "Arquivo deveria ter um cabeçalho, mas não tem.";
				}
			}


			/* TRATAR REGISTROS DO ARQUIVO */
			if (isset($this->modelo['registros']))
			{
				$registros = $xml->registros->registro;
				if (count($registros) > 0 && $registros<>"")
				{
					foreach ($registros as $registro) {
						$reg = [];
						foreach ($this->modelo['registros'] as $key=>$value)
						{
							if (isset($registro->$key))
								$reg[$key] = $this->formataValor($registro->$key, $value);
							else
								$this->erros[] = "Não identificamos o campo $key no seu arquivo.";
						}
						$this->registros[] = $reg;
					}
				} else {
					$sai = false;
					$this->erros[] = "Nenhum registro encontrado.";
				}
			}
		} else
		{
			$sai = false;
			$this->erros[] = "Formato do arquivo incompatível (XML).";
		}
		/*
		$doc = new DOMDocument();
		$doc->preservWhiteSpace = FALSE; //elimina espaços em branco
		$doc->formatOutput = FALSE;
		$sai = $doc->loadXML($arquivo);
		if ($sai) {
			if (isset($this->modelo['cabecalho']))
			{
				$cabecalho = $doc->getElementsByTagName('cabecalho');
				gD(json_encode($cabecalho));
				if ($cabecalho->length>0)
				{

					// Tem cabeçalho, então processa-o
					foreach ($this->modelo['cabecalho'] as $key=>$value)
					{
						$item = $cabecalho->getElementsByTagName($key);
						if ($item->length>0)
						{
							$valor = $item->item(0)->nodeValue;
							$this->cabecalho[$key] = $valor;
						}
					}
				} else {
					$sai = false;
					$this->erros[] = "Deveria ter um cabeçalho e não tem.";
				}
			}
			$fez = false;
			if (isset($this->modelo['registros']))
			{
				// Tem registros, então processa-os
				$registros = $doc->getElementsByTagName('registro');
				if ($registros->length>0)
				{
					for($a = 0; $a<$registros->length; $a++)
					{
						$registroAtual = $registros->item($a);
						foreach ($this->modelo['registros'] as $key=>$value)
						{
							$valor = $registroAtual->getElementsByTagName($key)->item(0)->nodeValue;
							$this->registros[$key] = $valor;
						}
					}
				} else {
					$sai = false;
					$this->erros[] = "Deveria ter um cabeçalho e não tem.";
				}
				foreach ($this->modelo['registros'] as $key=>$value)
				{
					$valor = $registros->getElementsByTagName($key)->item(0)->nodeValue;;
					$this->registros[$key] = $valor;
				}
			}
			gD($cabecalho);
			gD($registros);

		} else {
			$sai = false;
			$this->erros[] = "Formato do arquivo incompatível (XML).";
		}
		return($sai);
		*/

		return ($sai);
	}

	/* PROCESSAR E TRATAR ARQUIVOS JSON */
	function processaJSON($arquivo)
	{

		global $html,$o;
		$html.=$o->msgSubTitle("Processamento de arquivo JSON");
		/* VERIFICAR CONVERSÃO DO ARQUIVO */
		$sai  = (is_string($arquivo) && (is_object(json_decode($arquivo))) || is_array(json_decode($arquivo)))
			? true
			: false;
		$json = json_decode($arquivo);
		if ($sai)
		{
			/* TRATANDO CABECALHO */
			if (isset($this->modelo['cabecalho']))
			{
				$cabecalho = $json->cabecalho;
				if ($cabecalho<>"")
				{
					$cab = [];
					foreach ($this->modelo['cabecalho'] as $key=>$value)
					{
						$cab[$key] = $this->formataValor($cabecalho->$key, $value);
					}
					$this->cabecalho = $cab;
				} else
				{
					$sai = false;
					$this->erros[] = "Arquivo deveria ter um cabeçalho, mas não tem.";
				}
			}

			/* TRATANDO REGISTROS */
			if (isset($this->modelo['registros']))
			{
				$registros = $json->registros;
				if (count($registros) > 0)
				{
					foreach ($registros as $registro)
					{
						$reg = [];
						foreach ($this->modelo['registros'] as $key=>$value)
						{
							/* VALIDAR SE EXISTE CHAVE */
							if (isset($registro->$key))
								$reg[$key] = $this->formataValor($registro->$key, $value);
							else
								$this->erros[] = "Chave $key não existe no modelo do arquivo";
						}
						$this->registros[] = $reg;
					}
				} else
				{
					$this->erros[] = "Nenhum registro encontrado.";
				}
			}
		} else {
			$sai = false;
			$this->erros[] = "Formato do arquivo incompatível (JSON).";
		}
		return($sai);
	}

	/* PROCESSAR E TRATAR ARQUIVOS CSV */
	function processaCSV($arquivo, $tipo="CSV")
	{
		global $html,$o;
		$html.=$o->msgSubTitle("Processamento de arquivo ".$tipo);
		$linhasComComentario = explode("\n",$arquivo);
		$linhas 	 = [];
		$descritivos = [];
		foreach ($linhasComComentario as $linha)
		{
			$linha = trim($linha);
			if (substr($linha,0,1)<>'#') {
				$linhas[] = $linha;
			} else {
				$descritivos[] = $linha;
			}
		}
		if (count($linhas)>0)
		{
			$sai = true;
			/* TRATAR CABEÇALHO */
			if (isset($this->modelo['cabecalho']))
			{
				/* TENDO MAIS DE UM DESCRITIVO O ARQUIVO TEM CABEÇALHO */
				if (count($descritivos) > 1) {
					$linha = explode(';',$linhas[0]);
					$cnt = 0;
					foreach ($this->modelo['cabecalho'] as $nome=>$formato)
					{
						$this->cabecalho[$nome] = $this->formataValor($linha[$cnt],$formato);
						$cnt++;
					}
				} else
				{
					$this->erros[] = "Arquivo deveria ter um cabeçalho, mas não tem.";
				}
			}

			/* TRATAR REGISTRO */
			if (isset($this->modelo['registros'])) {
				$cnt = 0;
				foreach ($linhas as $linha) {
					/* DEFINIR O CONTADOR DE ACORDO COM O PADRÃO DO ARQUIVO, COM OU SEM CABEÇALHO */
					$contadorCondicionalCabecalho = (count($descritivos) > 1) ? $cnt>0 : $cnt>=0;

					if ($contadorCondicionalCabecalho) {
						$colunas = explode(";", $linha);

						if (empty($linha) || trim(str_replace(';', '', $linha)) === '') {
							continue;
						}

						$reg = array();
						$r 	 = 0;
						foreach ($this->modelo['registros'] as $nome=>$formato)
						{

							$reg[$nome] = $this->formataValor($colunas[$r], $formato);
							$r++;
						}
						//echo "<pre>";var_dump($reg);exit;
						$this->registros[] = $reg;
					}
					$cnt++;
				}

			}
		} else {
			$sai = false;
			$this->erros[] = "Formato do arquivo incompatível (CSV).";
		}
		//echo "<pre>";var_dump($this->registro);exit;
		return($sai);
	}


	/* PROCESSAR E TRATAR ARQUIVOS TAMANHO FIXO */
	function processaTamanhoFixo($arquivo, $arquivo_auxiliar)
	{
		global $html,$o;
		$this->multiplosCabecalhos = true;
		$html.=$o->msgSubTitle("Processamento de arquivo Tamanho Fixo");
		$linhasCabecalho = explode("\n",str_replace("\r","",$arquivo));
		$linhasDados = explode("\n",str_replace("\r","",$arquivo_auxiliar));
		if (count($linhasCabecalho)>0 && count($linhasDados)>0)
		{
			$sai = true;

			// Tratar cabecalho.
			if (isset($this->modelo['cabecalho']))
			{
				$cnt = 0;
				$reg = array();
				foreach ($linhasCabecalho as $linha)
				{
					$pos = 0;
					$reg = array();
					foreach ($this->modelo['cabecalho'] as $nome=>$formato)
					{
						if ($formato['campo_util'])
						{
							$reg[$nome] = $this->formataValor(substr($linha,$pos,$formato['tamanho']),$formato);
						}
						$pos+=$formato['tamanho'];
					}
					$this->cabecalho[]=$reg;
					$cnt++;
				}
			}

			/* TRATAR REGISTRO */
			if (isset($this->modelo['registros']))
			{
				$cnt = 0;
				foreach ($linhasDados as $linha)
				{
					$pos = 0;
					$reg = array();
					foreach ($this->modelo['registros'] as $nome=>$formato)
					{
						if ($formato['campo_util'])
						{
							$reg[$nome] = $this->formataValor(substr($linha,$pos,$formato['tamanho']),$formato);
						}
						$pos+=$formato['tamanho'];
					}
					$this->registros[] = $reg;
					$cnt++;
				}
			}
			// gD($this->cabecalho);
			// gD($this->registros);
		} else {
			$sai = false;
			$this->erros[] = "Formato do(s) arquivo(s) incompatível(veis) (Tamanho fixo).";
		}
		return($sai);
	}

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

	function processaExcel($inputFileName)
	{
		global $gPathLib;
		$inputFileName = $_FILES['arquivo']['tmp_name'];
		include_once($gPathLib.'/phpexcel/Classes/PHPExcel.php');
		$objPHPExcel = new PHPExcel();
		PHPExcel_Settings::setLocale('pt_br');
		$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
		$max_lin = $objPHPExcel->setActiveSheetIndex(0)->getHighestRow();
		//echo "<pre>";var_dump($objPHPExcel);exit;

		$csv = '';
		$lins = array();
		for($L=1; $L<=$max_lin; $L++)
		{
			$cols = array();
			for ($C=0; $C<24; $C++)
			{
				$celula = $this->obtemColuna($C).$L;
				$col = gCleanField($objPHPExcel->getActiveSheet()->getCell($celula)->getCalculatedValue());
				$cols[] = $col;
			}
			$lins[]=implode(";",$cols);
		}
		$lins[0] = '#'.$lins[0];
		if (isset($this->modelo['cabecalho']))
		{
			$lins[2] = '#'.$lins[0];
		}
		$csv = implode("\n", $lins);
		$this->processaCSV($csv, 'Excel');
	}

	/* MÉTODO PRINCIPAL PARA O PROCESSAMENTO DA IMPORTAÇÃO */
	function processar($grupo)
	{
		global $o, $gPage, $usrId, $gId, $sp;
		$this->grupo = $grupo;
		$gPageImportar = intval($_REQUEST['gPageImportar']);

		if ($_REQUEST["cmd"])
		{
			$sql = "SELECT id, descricao FROM importacoes WHERE grupo='".$grupo."'";
			if (intval($_REQUEST["id_pessoas_proprietario"])>0)
			{
				$sql.=" AND id_pessoas_proprietario=".intval($_REQUEST["id_pessoas_proprietario"]);
			}
			echo json_encode(dbQuery($sql));
			exit;
		}


		switch ($gPageImportar)
		{
			case 0:
				// gD($grupo);
				$html .= $o->msgSubTitle("Importação de dados");
				$html .= $o->msg("Utilize esta opção para importar dados de outro sistema/cliente para processamento no emiteNota.");
				$frm   = new gForm("{columns: 2}");
				$sql   = "SELECT id, descricao FROM importacoes WHERE grupo = '" . $grupo . "'";
				$frm->add("{onChange:mudouProprietario; fieldLabel:Proprietário; name:id_pessoas_proprietario; type:combo; allowBlank:true; items:" . $sp["combo_clientes"] . ";}");
				$frm->add("{name: modelo; type: combo; allowBlank: false; items: " . $sql . " }");
				//$frm->add("{name: formato; type: combo; allowBlank: false; items:{XML,JSON,CSV} }");
				$frm->add("{name: arquivo; fieldLabel: Principal (.inf, .anf, .csv, .xml, .json);type: file;}");
				$frm->add("{name: arquivo_auxiliar; fieldLabel: Complementar (.idt, .adt, .csv, .xml, .json);type: file;}");
				$frm->add("{name: gPage; type: hidden; value: " . $gPage . ";}");
				$frm->add("{name: gPageImportar; type: hidden; value: " . IMPORTAR_PROCESSA . ";}");
				$frm->addButton("{icon: file-alt; title: Ver modelo; style: info; size: normal;}onClick:verModelo();");
				$html .= $frm->render($o);

				$solicitacaoRemoverCabecalho = "";
				if ($grupo == 'itens') {
					$solicitacaoRemoverCabecalho = "<br> Importe CSV sem título";
				}

				$html .= $o->msgInfo("Clique em [Ver modelo] para ver os exemplos." . $solicitacaoRemoverCabecalho);

				$rotaAjax = $o->page . "&gPage=62" . "&gAjs=1&cmd=mudouProprietario&id_pessoas_proprietario=";
				$rotaAjaxVerModelo = $o->page . "&gPage=" . $gPage . "&gPageImportar=" . VER_EXEMPLOS;
				$js = "function mudouProprietario(id)
					{
						showWait();
						$.ajax({
	                        method: 'GET',
	                        url: '".$rotaAjax."'+id,
	                        success: function (resp) {
	                            hideWait();
	                            var importacoes = JSON.parse(resp);
	                            if (importacoes.length)
	                            {
	                            	var selectImportacoes = \$select_modelo[0].selectize;
	                            	selectImportacoes.clearOptions();
									for (let i=0; i<importacoes.length; i++)
	                                {
	                                	selectImportacoes.addOption({value: importacoes[i].id, text: importacoes[i].descricao});
	                                }
	                                selectImportacoes.setValue(importacoes[0].id);
	                            }
	                        }
                    	});
					 }


					function verModelo()
					{
						let input = document.querySelector('#modelo');
						let id = input.value;
						let rota = '{$rotaAjaxVerModelo}' + '&modelo=' + id
						showWait();
						$.ajax({
							method: 'GET',
							url: rota,
							success: function (resp) {
								window.location = rota
							}
						});

						hideWait();
					  }
					 ";
				$html .= $o->addJavascript($js);
			break;

			case VER_EXEMPLOS:

				$modelo = intval($_REQUEST['modelo']);

				$this->obtemFormato($modelo);

				$html .= $o->msgSubTitle($this->modelo['descricao']);
				$html .= $o->hr();
				$html .= $o->msgSubTitle("Exemplos dos formatos de arquivos compatíveis:");
				$html .= $o->tableBegin("big", true);
				$mtz   = array();
				$mtz[] = "<-XML";
				$mtz[] = "<-JSON";
				$mtz[] = "<-CSV " . $o->label('Preferencial', 'danger');
				$html .= $o->tableRow($mtz, "header");
				$mtz   = array();
				$mtz[] = "<-" . $o->small(nl2br(str_replace("	","&nbsp;&nbsp;&nbsp;",htmlspecialchars($this->exemploXML()))));
				$mtz[] = "<-" . $o->small(nl2br(str_replace("	","&nbsp;&nbsp;&nbsp;",htmlspecialchars($this->exemploJSON()))));
				$mtz[] = "<-" . $o->small(nl2br(str_replace("	","&nbsp;&nbsp;&nbsp;",htmlspecialchars($this->exemploCSV()))));
				$html .= $o->tableRow($mtz, "detail");
				$mtz = array();
				$mtz[] = "~3->" . $o->button("{icon: download; title: Baixar modelo CSV; style: info; size: normal;}onClick:baixarModelo();");
				$html .= $o->tableRow($mtz, "detail");
				$html .= $o->tableEnd();
				$html .= $o->hr();

				$rotaAjaxImpressao = $o->page . "&gPage=" . IMPRIMIR_MODELO;
				$js = "function baixarModelo()
				{
					let rota = '{$rotaAjaxImpressao}' + '&gId=' + {$modelo}
					showWait();
					$.ajax({
						method: 'GET',
						url: rota,
						success: function (resp) {
							window.location = rota
						}
					});

					hideWait();
				  }
				 ";
				$html .= $o->addJavascript($js);
				if (isset($this->modelo['cabecalho'])) {
					$html .= $o->msgSubTitle("Formatação do cabeçalho:");
					$html .= $o->tableBegin("big", true);
					$mtz   = array();
					$mtz[] = "->Nº";
					$mtz[] = "<-Nome";
					$mtz[] = "<-Descrição";
					$mtz[] = "<-Tipo";
					$mtz[] = "<-Tamanho";
					$mtz[] = "<-Validação";
					$html .= $o->tableRow($mtz, "header");
					$cnt   = 0;
					foreach ($this->modelo['cabecalho'] as $campo=>$valores) {
						$cnt++;
						$mtz   = array();
						$mtz[] = "->" . $cnt;
						$mtz[] = "<-" . $campo;
						$mtz[] = "<-" . $valores['descricao'];
						$mtz[] = "<-" . $valores['tipo'];
						$mtz[] = "<-" . $valores['tamanho'];
						$mtz[] = "<-" . $valores['validacao'];
						$html .= $o->tableRow($mtz, "detail");
					}
					$html .= $o->tableEnd();
				}

				if (isset($this->modelo['registros'])) {
					$html .= $o->msgSubTitle("Formatação dos registros:");
					$html .= $o->tableBegin("big", true);
					$mtz   = array();
					$mtz[] = "->Nº";
					$mtz[] = "<-Nome";
					$mtz[] = "<-Descrição";
					$mtz[] = "<-Tipo";
					$mtz[] = "<-Tamanho";
					$mtz[] = "<-Validação";
					$html .=$o->tableRow($mtz, "header");
					$cnt   = 0;
					foreach ($this->modelo['registros'] as $campo=>$valores) {
						$cnt++;
						$mtz   = array();
						$mtz[] = "->" . $cnt;
						$mtz[] = "<-" . $campo;
						$mtz[] = "<-" . $valores['descricao'];
						$mtz[] = "<-" . $valores['tipo'];
						$mtz[] = "<-" . $valores['tamanho'];
						$mtz[] = "<-" . $valores['validacao'];
						$html .= $o->tableRow($mtz, "detail");
					}
					$html .= $o->tableEnd();
					$html .= $o->msgSubTitle("Você pode enviar um arquivo para teste usando a opção abaixo:");
					$frm   = new gForm();
					$frm->add("{name: arquivo; type: file;}");
					$frm->add("{name: modelo; type: hidden; value: " . $modelo . " }");
					$frm->add("{name: teste; type: hidden; value: 1}");
					$frm->add("{name: gPage; type: hidden; value: " . $gPage . ";}");
					$frm->add("{name: gPageImportar; type: hidden; value: " . IMPORTAR_PROCESSA . ";}");
					$html .= $frm->render($o);
				}
				break;

			case IMPORTAR_PROCESSA:

				/*
				if (intval($_REQUEST["modelo"])==5)
				{
					// Conferir se é entrada ou saída
					$grupo=gFieldById("importacoes", intval($_REQUEST["modelo"]), "grupo");
					if ($grupo=="programacao_saida")
					{
						$idTipoProgramacao=2;
					} else if ($grupo=="programacao_entrada")
					{
						$idTipoProgramacao=1;
					} else
					{
						$html.=$o->msgWarning("Sua solicitação não pode ser processada.");
						$html.=$backButton;
						return;
					}
					$rota="index.php?g=nf_entrada&gPage=40&gProgramacao=".$idTipoProgramacao."&gBack=1";
					redirect($rota);
				}
				*/
				$modelo = intval($_REQUEST['modelo']);

				$this->obtemFormato($modelo);
				// echo "<pre>";var_dump($modelo);
				// exit;
				$html.=$o->msgSubTitle($this->modelo['descricao']);


				if ($_FILES['arquivo']['name']=='' && false)
				{
					$html.=$o->msg("As instruções abaixo são direcionadas para equipes de desenvolvimento de software para integração de outros sistemas com o emiteNota.");
					$html.=$o->hr();
					$html.=$o->msgSubTitle("Exemplos dos formatos de arquivos compatíveis:");
					$html.=$o->tableBegin("big", true);
					$mtz = array();
					$mtz[]="<-XML";
					$mtz[]="<-JSON";
					$mtz[]="<-CSV ".$o->label('Preferencial', 'danger');
					$html.=$o->tableRow($mtz, "header");
					$mtz = array();
					$mtz[]="<-".$o->small(nl2br(str_replace("	","&nbsp;&nbsp;&nbsp;",htmlspecialchars($this->exemploXML()))));
					$mtz[]="<-".$o->small(nl2br(str_replace("	","&nbsp;&nbsp;&nbsp;",htmlspecialchars($this->exemploJSON()))));
					$mtz[]="<-".$o->small(nl2br(str_replace("	","&nbsp;&nbsp;&nbsp;",htmlspecialchars($this->exemploCSV()))));
					$html.=$o->tableRow($mtz, "detail");
					$html.=$o->tableEnd();
					$html.=$o->hr();
					if (isset($this->modelo['cabecalho']))
					{
						$html.=$o->msgSubTitle("Formatação do cabeçalho:");
						$html.=$o->tableBegin("big", true);
						$mtz = array();
						$mtz[]="->Nº";
						$mtz[]="<-Nome";
						$mtz[]="<-Descrição";
						$mtz[]="<-Tipo";
						$mtz[]="<-Tamanho";
						$mtz[]="<-Validação";
						$html.=$o->tableRow($mtz, "header");
						$cnt = 0;
						foreach ($this->modelo['cabecalho'] as $campo=>$valores) {
							$cnt++;
							$mtz = array();
							$mtz[]="->".$cnt;
							$mtz[]="<-".$campo;
							$mtz[]="<-".$valores['descricao'];
							$mtz[]="<-".$valores['tipo'];
							$mtz[]="<-".$valores['tamanho'];
							$mtz[]="<-".$valores['validacao'];
							$html.=$o->tableRow($mtz, "detail");
						}
						$html.=$o->tableEnd();
					}

					if (isset($this->modelo['registros']))
					{
						$html.=$o->msgSubTitle("Formatação dos registros:");
						$html.=$o->tableBegin("big", true);
						$mtz = array();
						$mtz[]="->Nº";
						$mtz[]="<-Nome";
						$mtz[]="<-Descrição";
						$mtz[]="<-Tipo";
						$mtz[]="<-Tamanho";
						$mtz[]="<-Validação";
						$html.=$o->tableRow($mtz, "header");
						$cnt = 0;
						foreach ($this->modelo['registros'] as $campo=>$valores) {
							$cnt++;
							$mtz = array();
							$mtz[]="->".$cnt;
							$mtz[]="<-".$campo;
							$mtz[]="<-".$valores['descricao'];
							$mtz[]="<-".$valores['tipo'];
							$mtz[]="<-".$valores['tamanho'];
							$mtz[]="<-".$valores['validacao'];
							$html.=$o->tableRow($mtz, "detail");
						}
						$html.=$o->tableEnd();
						$html.=$o->msgSubTitle("Você pode enviar um arquivo para teste usando a opção abaixo:");
						$frm = new gForm();
						$frm->add("{name: arquivo; type: file;}");
						$frm->add("{name: modelo; type: hidden; value: ".$modelo." }");
						$frm->add("{name: teste; type: hidden; value: 1}");
						$frm->add("{name: gPage; type: hidden; value: ".$gPage.";}");
						$frm->add("{name: gPageImportar; type: hidden; value: ".IMPORTAR_PROCESSA.";}");
						$html.=$frm->render($o);
					}
				} else {
					$arquivo = trim(@file_get_contents($_FILES['arquivo']['tmp_name']));
					$arquivo_auxiliar = trim(@file_get_contents($_FILES['arquivo_auxiliar']['tmp_name']));
					$tipoArquivo = $_FILES["arquivo"]["type"];
					if ($arquivo<>"")
					{
						if (substr($arquivo,0,1)=="<")
						{
							// XML
							if ($tipoArquivo == "text/xml")
								$ok = $this->processaXML($arquivo);
							else
							{
								$ok = false;
								$html .= $o->msgDanger("Arquivo XML inválido");
							}
						} elseif (substr($arquivo,0,1)=="{")
						{
							// JSON
							if ($tipoArquivo == "application/json")
								$ok = $this->processaJSON($arquivo);
							else
							{
								$ok = false;
								$html.=$o->msgDanger("Arquivo JSON inválido");
							}
						} elseif (substr($arquivo,0,1)==";" || $tipoArquivo=='text/csv')
						{
							// CSV
							if ($tipoArquivo == "text/csv")
								$ok = $this->processaCSV($arquivo);
							else
							{
								$ok = false;
								$html.=$o->msgDanger("Arquivo CSV inválido");
							}
						} else
						{


							if ($tipoArquivo == "application/octet-stream")
								$ok = $this->processaTamanhoFixo($arquivo, $arquivo_auxiliar);
							elseif ($tipoArquivo == "application/vnd.ms-excel" || $tipoArquivo == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
							{
								$ok = $this->processaExcel($arquivo);
							}
							else
							{
								$ok = false;
								$html.=$o->msgDanger("Arquivo de importação inválido");
							}
						}


						if (count($this->erros)==0)
						{
							if (isset($this->modelo["cabecalho"])) {
								$html .= $o->msgSubTitle("Cabeçalho: ");
								$mtz = [];
								foreach ($this->modelo["cabecalho"] as $key => $value) {
									//if ($value['campo_util'])
									{
										$mtz[] = $this->campoTable($key, $value["tipo"]);
									}
								}
								$html .= $o->tableBegin("big", true,true);
								$html .= $o->tableRow($mtz, "header");
								if ($this->multiplosCabecalhos)
								{
									foreach ($this->cabecalho as $registro) {
										$mtzRegistros = [];
										foreach ($this->modelo["cabecalho"] as $key => $value) {
											//if ($value['campo_util'])
											{

												$registroValor = $this->campoTable($registro[$key], $value["tipo"], true);
												$mtzRegistros[] = $registroValor;
											}
										}
										$html .= $o->tableRow($mtzRegistros, "detail");
									}
								} else {
									$mtzRegistros   = [];
									foreach ($this->modelo["cabecalho"] as $key => $value)
									{
										//if ($value['campo_util'])
										{
											$mtzRegistros[] = $this->campoTable($this->cabecalho[$key], $value["tipo"], true);
										}
									}
									$html .= $o->tableRow($mtzRegistros, "detail");

								}
								$html .= $o->tableEnd();
							}
							// echo "<pre>";
							// var_dump($this->registros);exit;
							if (isset($this->modelo["registros"])) {
								$html .= $o->msgSubTitle("Registros: ");
								$html.=$o->tableBegin("big", true, true);
								$mtz = [];
								foreach ($this->modelo["registros"] as $key => $value)
								{
									//if ($value['campo_util'])
									{
										$mtz[] = $this->campoTable($key, $value["tipo"]);
									}
								}

								$html .= $o->tableRow($mtz, "header");
								foreach ($this->registros as $registro) {
									$mtzRegistros = [];
									foreach ($this->modelo["registros"] as $key => $value) {
										//if ($value['campo_util'])
										{
											$registroValor = $this->campoTable($registro[$key], $value["tipo"], true);
											$mtzRegistros[] = $registroValor;
										}
									}
									$html .= $o->tableRow($mtzRegistros, "detail");
								}
								$html .= $o->tableEnd();
							}

							if (isset($_REQUEST['teste']) && $_REQUEST['teste'] == 1)
							{
								$html.=$o->msgInfo("Arquivo processado para teste. Nenhum registro será adicionado, modificado ou excluído.");
							} else
							{

								/* ARQUIVO PROCESSADO CORRETAMENTE, FAZER PERSISTÊNCIA */
								if ( (isset($_FILES["arquivo"]) && strstr($_FILES["arquivo"]["name"], ".anf") ) && ( isset($_FILES["arquivo_auxiliar"]) && strstr($_FILES["arquivo_auxiliar"]["name"], ".adt")) )
								{
									$programacoes=array();
									for ($i=0; $i<count($this->cabecalho); $i++)
									{
										$cabecalho=$this->cabecalho[$i];
										$registros=$this->registros;
										$sql="SELECT PR.id, PR.numero_cliente, P.apelido, PJ.cnpj FROM programacao PR
											  LEFT JOIN pessoas P ON P.id = PR.id_pessoas_proprietario AND P.cliente=1
											  LEFT JOIN pessoas_juridicas PJ ON PJ.id_pessoas = P.id
											  WHERE
											  	(PR.reservada='1')
											  	AND (PR.numero_cliente='".$cabecalho["numero_cliente"]."' OR PR.recno='".$cabecalho["numero_cliente"]."')";
										$programacao=dbQuery($sql);
										if (count($programacao)==0 || empty($cabecalho["numero_cliente"]))
										{
											if (empty($cabecalho["numero_cliente"]))
											{
												$this->erros[]="Nenhum número de pedido encontrado.";
											} else
											{
												$this->erros[]="Nenhuma programação de saída foi reservada.";
											}
										} else
										{
											/* Validar o cnpj removendo a mascara para evitar casos em que o cnpj são salvos com mascara.*/
											$cnpj=str_replace(".", "", $programacao[0]["cnpj"]);
											$cnpj=str_replace("-", "", $cnpj);
											$cnpj=str_replace("/", "", $cnpj);
											if ($cnpj<>$cabecalho["cnpj_emitente"])
											{
												$this->erros[]="CNPJ do proprietário não foi encontrado.";
											}
											if (!in_array($programacao[0]["id"], $programacoes))
											{
												$observacao="RETORNO REF. A NOTA FISCAL NUM: ".$cabecalho["numseq"];
												$observacao.=" SERIE: ".$cabecalho["serie_nf"];
												$observacao.=" EMISSAO: ".date("d/m/Y", strtotime($cabecalho["data_emissao"]));
												$observacao.=" DEST: ".$cabecalho["nome_destinatario"];
												$observacao.=" CNPJ: ".$cabecalho["cnpj_destinatario"];
												$observacao.=" END: ".$cabecalho["endereco_destinatario"];
												if (!empty($cabecalho["numero_endereco_destinatario"]))
												{
													$observacao.=" N: ".$cabecalho["numero_endereco_destinatario"];
												}
												$observacao.=" -CID: ".$cabecalho["cidade_destinatario"];
												$observacao.=" -BAIRRO: ".$cabecalho["bairro_destinatario"];
												$observacao.=" UF: ".$cabecalho["estado_destinatario"];
												$obj_programacao["id"]=$programacao[0]["id"];
												$obj_programacao["observacoes"]=$observacao;
												$obj_programacao["numseq"]=$cabecalho["numseq"];
												$programacoes[]=$obj_programacao;
											}
										}

										if (count($this->erros)==0)
										{
											foreach ($registros as $registro)
											{
												if ($registro["numero_cliente"]==$programacao[0]["numero_cliente"])
												{
													$where=array();
													$where[]=" (PI.id_programacao='".$programacao[0]["id"]."') ";
													$where[]=" (PI.valor='".$registro["valor"]."') ";
													$where[]=" (SK.codigo='".$registro["codigo"]."' OR SK.codigo_barras='".$registro["codigo"]."') ";
													$where=implode(" AND ", $where);
													$sql="SELECT PI.* FROM programacao_itens PI LEFT JOIN itens_skus SK ON PI.id_itens_skus = SK.id WHERE {$where}";
													$confereProgramacaoItem=dbQuery($sql);
													if (count($confereProgramacaoItem)==0)
													{
														$descricaoItem=$registro["codigo"]." • ".$registro["descricao"];
														//$this->erros[]="Item ({$descricaoItem}) do arquivo ainda não existe no pedido (".$cabecalho["numero_cliente"].").";
													}
												}
											}
										}
									}

									if (count($this->erros)==0)
									{
										foreach ($programacoes as $programacao)
										{
											$numseq=$programacao["numseq"];
											$id_programacao=$programacao["id"];
											$observacoes=$programacao["observacoes"];
											// Encontrando o número do cliente.
											$sql="SELECT
													numero_cliente
												  FROM programacao
												  WHERE id=".intval($id_programacao);
											$programacao=(dbQuery($sql)[0]);
											$mtz=array();
											$mtz["liberada"]=1;
											if (!empty($observacoes))
											{
												$mtz["detalhes_nfe"]=$observacoes;
											}
											if (!empty($numseq))
											{
												$mtz["numseq"]=$numseq;
											}
											dbUpdate("programacao", $mtz, $id_programacao);
											// Registrar em programacao atividades
											$mtz=array();
											$mtz["id_programacao"]=$id_programacao;
											$mtz["id_pessoas"]=$usrId;
											$mtz["id_tipos_atividades"]=7;
											$mtz["id_itens_skus"]=0;
											$mtz["cancelada"]=0;
											$mtz["data"]=date("Y-m-d H:i:s");
											$mtz["quantidade"]=0;
											$mtz["descricao"]="Liberou saída pelo arq. de faturamento. Número: ".$programacao["numero_cliente"];

											dbInsert("programacao_atividades", $mtz);
										}
										$html .= $o->msgInfo("Arquivo processado corretamente.");
									} else
									{
										$html .= $o->msgDanger("Não foi possível processar o arquivo, pois foram encontrados os seguintes erros:".$o->ul($this->erros));
									}
								} else
								{
									
									switch ($grupo)
									{
										case "itens":
											$this->novoItem();
											break;
										case "programacao_entrada":
											$this->novaProgramacao(1);
											break;
										case "programacao_saida":
											$this->novaProgramacao(2);
											break;
									}
									if (count($this->erros)>0)
									{
										$html .= $o->msgDanger("Não foi possível importar o arquivo, pois foram encontrados os seguintes erros:".$o->ul($this->erros));
									} else {
										$html .= $o->msgInfo("Arquivo processado corretamente.");
									}
								}
							}
						} else {
							/* HOUVE ERROS NA IMPORTAÇÃO DO ARQUIVO. */

							$html .= $o->msgDanger("Não foi possível importar o arquivo, pois foram encontrados os seguintes erros:" . $o->ul($this->erros));
						}
						$html.=$o->button("{icon: arrow-left; caption: Voltar; hint: Voltar; style: info; size: normal; href: ".$o->page."&gPage=".$_REQUEST["gPage"]."}");
					} else {
						$html.=$o->msgDanger("O arquivo está vazio");
					}
				}
			break;
		}
		return($html);
	}

}
