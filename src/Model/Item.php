<?php

namespace App\Model;

class Item
{
    private $api;
    private $db;

    public function __construct($api)
    {
        $this->api = $api;
        $this->db  = $api->db;
    }


    public function cadastrar($args)
    {
        if (!isset($args['item'])) {
            $this->api->emitirErro('Estrutura de dados nao reconhecida para esta rota');
        }

        $mtz = $args['item'];

        $this->api->validarCamposObrigatorios($mtz, [
            'descricao',
            'ncm',
            'codigo',
            'codigoBarras',
            'siglaUnidade',
            'descricaoUnidade',
            'altura',
            'comprimento'
        ]);

        if (!is_numeric($mtz['ncm']) || strlen($mtz['ncm']) != 8) {
			$this->api->emitirErro("O NCM informado não é válido. Verifique se o código foi digitado corretamente");
		}

        $this->verificarDuplicataSku(1, "", $mtz['codigoBarras']); ### Ver de onde vamos pegar esse id do cliente

        $dados = [];
        $dados['ncm']                     = substr($mtz['ncm'], 0, 10);
        $dados['nome']                    = substr($mtz['descricao'], 0, 149);
        $dados['apto']                    = (int) ($mtz['apto'] ?? 0);
        $dados['ativo']                   = (int) ($mtz['ativo'] ?? 0);
        $dados['codigo']                  = substr($mtz['codigo'], 0, 19);
        $dados['altura']                  = (float) substr($mtz['altura'], 0, 14);
        $dados['largura']                 = (float) substr($mtz['largura'], 0, 14);
        $dados['unidade']                 = substr($mtz['siglaUnidade'], 0, 2);
        $dados['descricao']               = substr($mtz['descricao'], 0, 254);
        $dados['comprimento']             = (float) substr($mtz['comprimento'], 0, 14);
        $dados['codigo_barras']           = substr($mtz['codigoBarras'], 0, 59);
        $dados['descricaoUnidade']        = substr($mtz['descricaoUnidade'], 0, 29);
        $dados['id_pessoas_proprietario'] = 1; ### Ver de onde vamos pegar esse id do cliente

        $detalhesSku = $this->cadastrarItem($dados, 1);

        $this->api->emitirSucesso("Item cadastrado com sucesso", 200, [
            'id' => $detalhesSku['id'],
            'codigoBarras' => $detalhesSku['codigoBarras'],
            'siglaUnidade' => $detalhesSku['siglaUnidade']
        ]);

    }


    public function editar($args)
    {
        if (!isset($args['item'])) {
            $this->api->emitirErro('Estrutura de dados nao reconhecida para esta rota');
        }

        $item = $args['item'];
        if (isset($args['item']['sku'])) {
            $itemSku = $args['item']['sku'];
        }

        $this->api->validarCamposObrigatorios($item, [
            'id',
            'descricao',
            'ncm',
            'codigo',
            'codigoBarras'
        ]);

        if ($itemSku) {
            $this->api->validarCamposObrigatorios($itemSku, [
                'id_itens_skus',
                'siglaUnidade',
                'descricaoUnidade',
                'altura',
                'largura',
                'comprimento'
            ]);
        }

        if (!is_numeric($item['ncm']) || strlen($item['ncm']) != 8) {
			$this->api->emitirErro("O NCM informado não é válido. Verifique se o código foi digitado corretamente");
		}

        // $item['id'] = id do item
        $this->verificarDuplicataSku(1, $item['id']); ### Ver de onde vamos pegar esse id do cliente

        $dados = [];
        $dados['ncm']                     = substr($item['ncm'], 0, 10);
        $dados['nome']                    = substr($item['descricao'], 0, 149);
        $dados['apto']                    = (int) ($item['apto'] ?? 0);
        $dados['ativo']                   = (int) ($item['ativo'] ?? 0);
        $dados['codigo']                  = substr($item['codigo'], 0, 19);
        $dados['altura']                  = (float) substr($itemSku['altura'], 0, 14);
        $dados['id_itens']                 = (int) $item['id'];
        $dados['largura']                 = (float) substr($itemSku['largura'], 0, 14);
        $dados['unidade']                 = substr($itemSku['siglaUnidade'], 0, 2);
        $dados['descricao']               = substr($item['descricao'], 0, 254);
        $dados['comprimento']             = (float) substr($itemSku['comprimento'], 0, 14);
        $dados['codigo_barras']           = substr($item['codigoBarras'], 0, 59);
        $dados['descricaoUnidade']        = substr($itemSku['descricaoUnidade'], 0, 29);
        $dados['id_pessoas_proprietario'] = 1; ### Ver de onde vamos pegar esse id do cliente

        $detalhesSku = $this->editarItem($dados);

        if ($itemSku) {
            $dados['id_itens_skus'] = (int) $itemSku['id_itens_skus'];
            $detalhesSku['sku'] = $this->editarSku($dados);
        }

        $this->api->emitirSucesso("Item cadastrado com sucesso", 200, $detalhesSku);
    }


    public function excluir($args)
    {
        if (!isset($args['item'])) {
            $this->api->emitirErro('Estrutura de dados nao reconhecida para esta rota');
        }

        $mtz = $args['item'];

        if ((!isset($mtz['codigo']) && !isset($mtz['codigoBarras'])) && !$mtz['id']) {
            $this->api->emitirErro('As chaves codigo ou codigoBarras devem ser informadas');
        }

        $where = [];
        $whereSku = [];

        if (isset($mtz['id'])) {
            $where[] = "(id = '" . gCleanField($mtz['id']) . "')";
            $whereSku[] = "(id_itens = '" . gCleanField($mtz['id']) . "')";
        }

        if (isset($mtz['codigo'])) {
            $where[] = "(codigo = '" . gCleanField($mtz['codigo']) . "')";
            $whereSku[] = "(codigo = '" . gCleanField($mtz['codigo']) . "')";
        }

        if (isset($mtz['codigoBarras'])) {
            $where[] = "(codigo_barras = '" . gCleanField($mtz['codigoBarras']) . "')";
            $whereSku[] = "(codigo_barras = '" . gCleanField($mtz['codigoBarras']) . "')";
        }

        $where = implode(" AND ", $where);
        $whereSku = implode(" AND ", $whereSku);

        $atualizar = [];
        $atualizar[] = "ativo = 0";
        $atualizar[] = "data_alteracao = '" . date('Y-m-d H:i:s') . "'";
        $atualizar[] = "id_pessoas_alterou = 1";
        $atualizar = implode(", ", $atualizar);

        $sql = "UPDATE itens SET {$atualizar} WHERE {$where}";
        $this->db->executarQuery($sql);

        $sql = "UPDATE itens_skus SET {$atualizar} WHERE {$whereSku}";
        $this->db->executarQuery($sql);

        $this->api->emitirSucesso("Item excluido com sucesso", 200);
    }


    public function listar($args)
    {
        if (!isset($args['item'])) {
            $this->api->emitirErro('Estrutura de dados nao reconhecida para esta rota');
        }

        $mtz = $args['item'];

        if ((!isset($mtz['codigo']) && !isset($mtz['codigoBarras'])) && !$mtz['id']) {
            $this->api->emitirErro('As chaves codigo ou codigoBarras devem ser informadas');
        }

        $this->api->validarCamposObrigatorios($mtz, array('siglaUnidade'));

        $where = [];
        $where[] = "(I.id_pessoas_proprietario = 1)"; ### Ver de onde vamos pegar esse id do cliente

        if (isset($mtz['id'])) {
            $where[] = "(SK.id = '" . gCleanField($mtz['id']) . "')";
        }

        if (isset($mtz['codigo'])) {
            $where[] = "(SK.codigo = '" . gCleanField($mtz['codigo']) . "')";
        }

        if (isset($mtz['codigoBarras'])) {
            $where[] = "(SK.codigo_barras = '" . gCleanField($mtz['codigoBarras']) . "')";
        }

        if ($mtz['siglaUnidade']) {
            $sql = "SELECT id FROM unidades WHERE sigla = '" . gCleanField($mtz['siglaUnidade']) . "' LIMIT 1";
            $rs = $this->db->executarQuery($sql);
            if (!$rs) {
                $this->api->emitirErro('A sigla de unidade informada nao existe na base de dados');
            }

            $where[] = "(UN.id = " . $rs[0]['id'] . ")";
        }

        $where = implode(" AND ", $where);

        $sql = "SELECT
                    SK.id,
                    UN.sigla AS sigla_unidade,
                    SK.codigo,
                    SK.altura,
                    SK.largura,
                    SK.codigo_barras,
                    SK.comprimento,
                    I.ncm,
                    I.nome,
                    I.descricao,
                    I.ativo
			    FROM itens I
			    JOIN itens_skus SK ON I.id = SK.id_itens
                JOIN unidades UN ON UN.id = SK.id_unidades
			    WHERE {$where}
			    ORDER BY I.ativo DESC, SK.ativo DESC LIMIT 1";
        $idItensSkus = $this->db->executarQuery($sql)[0];
        if (!$idItensSkus) {
            $this->api->emitirErro("Nada encontrado a partir dos filtros especificados");
        }

        $retorno = [
            'id' => $idItensSkus['id'],
            'ativo' => $idItensSkus['ativo'],
            'codigo' => $idItensSkus['codigo'],
            'codigoBarras' => $idItensSkus['codigo_barras'],
            'ncm' => $idItensSkus['ncm'],
            'nome' => $idItensSkus['nome'],
            'descricao' => $idItensSkus['descricao'],
            'siglaUnidade' => $idItensSkus['sigla_unidade'],
            'altura' => $idItensSkus['altura'],
            'largura' => $idItensSkus['largura'],
            'comprimento' => $idItensSkus['comprimento']
        ];

        $this->api->emitirSucesso("Item encontrado", 200, $retorno);
    }


    public function cadastrarItem($dados)
	{
		$mtz['apto']   = $dados['apto'] ?? 0;
		$mtz['ativo']  = $dados['ativo'] ?? 0;
		$mtz['codigo'] = $dados['codigo'];
		$mtz['nome'] = $dados['nome'];
		$mtz['ncm'] = $dados['ncm'];
		$mtz['codigo_barras'] = $dados['codigo_barras'];
		$mtz['data_cadastro'] = date('Y-m-d H:i:s');
		$mtz['descricao'] = $dados['descricao'] ?? $dados['codigo'];
		$mtz['id_pessoas_criou'] = 1; ### Ver de onde vamos pegar esse id do cliente
		$mtz['id_pessoas_proprietario'] = 1; ### Ver de onde vamos pegar esse id do cliente
		$mtz['observacoes'] = 'Criado através de integração';

		$idItens = $this->db->insertTable('itens', $mtz);
		if (!$idItens) {
            $this->api->emitirErro('Erro de banco de dados:  Não foi possível cadastrar os itens');
		}

		$mtz['id_itens'] = $idItens;
		$mtz['unidade']  = $dados['unidade'];
		$mtz['descricao_unidade'] = $dados['descricaoUnidade'];
		$mtz['largura'] = $dados['largura'];
		$mtz['altura'] = $dados['altura'];
		$mtz['comprimento'] = $dados['comprimento'];
		$idItensSkus = $this->cadastrarSku($mtz);

        return [
            'id' => $idItensSkus,
            'codigoBarras' => $dados['codigo_barras'],
            'siglaUnidade' => $dados['unidade']
        ];
	}


	public function cadastrarSku($dados)
	{
		$idUnidades = 1;
		if (trim($dados['unidade'])) {
			$sql = "SELECT id FROM unidades WHERE sigla = '" . gCleanField($dados['unidade']) . "' LIMIT 1";
			$idUnidades = ($this->db->executarQuery($sql)[0]['id']);
			if (!$idUnidades) {
				$mtz = [];
				$mtz['sigla'] = $dados['unidade'];
				$mtz['descricao'] = $dados['descricao_unidade'];
				$idUnidades = $this->db->insertTable('unidades', $mtz);
			}
		}

		$mtz = [];
		$mtz['ativo'] = $dados['ativo'];
		$mtz['id_itens'] = $dados['id_itens'];
		$mtz['id_unidades'] = $idUnidades;
		$mtz['id_pessoas_criou'] = 1; ### Ver de onde vamos pegar esse id do cliente
		$mtz['data_cadastro'] = date('Y-m-d H:i:s');
		$mtz['codigo'] = $dados['codigo'];
		$mtz['codigo_barras'] = $dados['codigo_barras'];
		$mtz['largura'] = $dados['largura'];
		$mtz['altura'] = $dados['altura'];
		$mtz['comprimento'] = $dados['comprimento'];
		$mtz['nome'] = $dados['nome'];

		$idItensSkus = $this->db->insertTable('itens_skus', $mtz);
		if (!$idItensSkus) {
			$this->api->emitirErro('Erro de banco de dados: Não foi possível cadastrar os SKUs');
		}

		return $idItensSkus;
	}


    public function editarItem($dados)
	{
        $mtz['ncm']                = $dados['ncm'];
		$mtz['nome']               = $dados['nome'];
		$mtz['apto']               = $dados['apto'] ?? 0;
		$mtz['ativo']              = $dados['ativo'] ?? 0;
        $mtz['codigo']             = $dados['codigo'];
		$mtz['descricao']          = $dados['descricao'] ?? $dados['codigo'];
		$mtz['codigo_barras']      = $dados['codigo_barras'];
		$mtz['data_alteracao']     = date('Y-m-d H:i:s');
		$mtz['id_pessoas_alterou'] = 1; ### Ver de onde vamos pegar esse id do cliente
		$mtz['id_pessoas_proprietario'] = 1; ### Ver de onde vamos pegar esse id do cliente

		$this->db->updateTable('itens', $mtz, $dados['id_itens']);

        return [
            'id' => $dados['id_itens'],
            'ncm' => $dados['ncm'],
            'nome' => $dados['nome'],
            'apto' => $dados['apto'],
            'ativo' => $dados['ativo'],
            'codigo' => $dados['codigo'],
            'descricao' => $dados['descricao'],
            'codigoBarras' => $dados['codigo_barras'],
        ];

	}

    ### Ver de onde vamos pegar o id_itens_skus (Vamos fazer a edição de item_sku separado como é no WMS?)
	public function editarSku($dados)
	{
		$idUnidades = 1;
		if (trim($dados['unidade'])) {
			$sql = "SELECT id FROM unidades WHERE sigla = '" . gCleanField($dados['unidade']) . "' LIMIT 1";
			$idUnidades = ($this->db->executarQuery($sql)[0]['id']);
			if (!$idUnidades) {
				$mtz = [];
				$mtz['sigla'] = $dados['unidade'];
				$mtz['descricao'] = $dados['descricao_unidade'];
				$idUnidades = $this->db->insertTable('unidades', $mtz);
			}
		}

		$mtz = [];
		$mtz['nome']               = $dados['nome'];
		$mtz['ativo']              = $dados['ativo'];
		$mtz['codigo']             = $dados['codigo'];
		$mtz['altura']             = $dados['altura'];
		$mtz['largura']            = $dados['largura'];
		$mtz['id_itens']           = $dados['id_itens'];
		$mtz['id_unidades']        = $idUnidades;
		$mtz['comprimento']        = $dados['comprimento'];
		$mtz['codigo_barras']      = $dados['codigo_barras'];
		$mtz['data_alteracao']     = date('Y-m-d H:i:s');
		$mtz['id_pessoas_alterou'] = 1; ### Ver de onde vamos pegar esse id do cliente

		$this->db->updateTable('itens_skus', $mtz, $dados['id_itens_skus']);

		return [
            'id_itens_skus'     => $dados['id_itens_skus'],
            'ativo'             => $dados['ativo'],
            'altura'            => $dados['altura'],
            'largura'           => $dados['largura'],
            'unidade'           => $dados['unidade'],
            'comprimento'       => $dados['comprimento'],
            'descricaoUnidade'  => $dados['descricaoUnidade'],
        ];
	}


    public function verificarDuplicataSku($idPessoasProprietario, $idItens = '', $codigoBarras = '')
	{
		$where = [];
		if ($idItens) {
			$where[] = "skuReferencia.id_itens = {$idItens}";
			$where[] = "skuComparado.id_itens <> {$idItens}";
		}

		if ($codigoBarras) {
			$where[] = "skuReferencia.codigo_barras = '" . $codigoBarras . "'";
		}

		$where = implode(" AND ", $where);

		$sql = "SELECT
					skuComparado.id_itens,
					skuComparado.id AS id_itens_skus,
					CONCAT(itens.codigo, '-', itens.nome) AS itemDetalhes
				FROM itens_skus skuReferencia
				LEFT JOIN itens_skus skuComparado ON skuComparado.codigo_barras = skuReferencia.codigo_barras
				JOIN itens ON itens.id = skuComparado.id_itens
				WHERE itens.id_pessoas_proprietario = {$idPessoasProprietario} AND {$where}
				GROUP BY skuComparado.id_itens";
		$skus = $this->db->executarQuery($sql);
		if (!$skus) {
			return false;
		}

		$msg = [];
		foreach ($skus as $sku) {
			$msg[] = "Esse SKU já está sendo utilizado no cadastro do item: " . $sku['id_itens_skus'] . " - " . $sku['itemDetalhes'];
		}

		$this->api->emitirErro("Erros encontrados", 400, $msg);

	}

}