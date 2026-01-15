<?php

namespace Api;

class Item
{
    protected $api;

    public function __construct($api)
    {
        $this->api = $api;
    }

    public function cadastrar($args)
    {
        /*
            verifica se o objeto item existe no json
            verifica se item existe no banco de dados, se não existe: cadastrar item
            dados: itens.descricao, itens.ncm, itens.codigo, itens.codigo_barras, itens_skus.palete_lastro (opcional), itens_skus.palete_altura  (opcional), quantidade, sigla unidade, descricao unidade, largura, altura, comprimento,
            se parametro $gParam["USA_REGRA_PALETIZACAO"]["ativo"]==1 obrigar informarnorma de paletizacao

            se tudo der errado ou tiver um fluxo interrompido, finalizar o processamento e transação
        */
        /*
            "item": {
                "descricao": "FRANGO CONGELADO GURJAO",
                "codigo": "10247852",
                "codigoBarras": "89748967",
                "ncm": "205010",
                "paleteLastro": "10",
                "paleteAltura": "10",
                "siglaUnidade": "UN",
                "quantidade": "1",
                "altura": "10",
                "largura": "10",
                "comprimento": "10",
                "descricaoUnidade": "Unidade",
            }
        */
        if (!$args['item']) {
            $this->api->emitirErro('Estrutura de dados nao reconhecida para esta rota');
        }

        $mtz = $args['item'];

        $this->api->validarCamposObrigatorios($mtz, array('descricao', 'ncm', 'codigo', 'codigoBarras', 'quantidade', 'siglaUnidade', 'descricaoUnidade', 'altura', 'comprimento'));

        $itemExiste = $this->verificarSeItemExiste($mtz['codigoBarras'], $mtz['siglaUnidade']);
        if ($itemExiste) {
            $this->api->emitirErro('Item ja cadastrado');
        }

        if ($this->api->gParam["USA_REGRA_PALETIZACAO"]["ativo"]) {
            if (!isset($mtz['paleteLastro']) || !isset($mtz['paleteAltura'])) {
                $this->api->emitirErro("A regra de paletização está ativa, mas os campos 'paleteLastro' e 'paleteAltura' não foram informados.");
            }
        }

        $dados = array();
        $dados['ncm']           = substr($mtz['ncm'], 0, 10);
        $dados['nome']          = substr($mtz['descricao'], 0, 149);
        $dados['descricao']     = substr($mtz['descricao'], 0, 254);
        $dados['unidade']       = substr($mtz['siglaUnidade'], 0, 2);
        $dados['descricaoUnidade'] = substr($mtz['descricaoUnidade'], 0, 2);
        $dados['codigo']        = substr($mtz['codigo'], 0, 19);
        $dados['altura']        = (float) substr($mtz['altura'], 0, 14);
        $dados['largura']       = (float) substr($mtz['largura'], 0, 14);
        $dados['comprimento']   = (float) substr($mtz['comprimento'], 0, 14);
        $dados['quantidade']    = (float) substr($mtz['quantidade'], 0, 14);
        $dados['codigo_barras'] = substr($mtz['codigoBarras'], 0, 59);
        $dados['palete_lastro'] = (float) substr($mtz['paleteLastro'], 0, 14);
        $dados['palete_altura'] = (float) substr($mtz['paleteAltura'], 0, 14);
        $dados['id_pessoas_proprietario'] = $this->api->idPessoasProprietario;

        // dados acima que não possui na funcao cadastrarItem (ncm, sigla, altura, largura, quantidade, comprimento, palete_lastro e palete_altura)
        $detalhesSku = $this->api->integracao->cadastrarItem($dados, 1);


        if ($this->api->integracao->erros) {
            $this->api->emitirErro($this->api->integracao->erros);
        }

        $this->api->finalizarRequisicao(true, array(
            'item' => array(
                'id' => $detalhesSku['id'],
                'codigoBarras' => $detalhesSku['codigoBarras'],
                'siglaUnidade' => $detalhesSku['siglaUnidade']
            )
        ), 200);

    }

    public function listar($args)
    {
        /*
            verifica se o objeto item existe no json - OK
            verifica se item existe no banco de dados, retornar dados caso exista, retornar mensagem de não existência caso nao exista
            dados: código de barras, ncm (opcional), código, unidade -> ou código ou código de barras deve existir + unidade
            responder com: código, descrição, flags de item, norma de paletização, palete lastro e altura...
        */
        /*
            "item": {
                "id": "767"
                "codigo": "6917D81A"
                "codigoBarras": "7891760874467"
                "siglaUnidade": "um"
            }
        */

        if (!$args['item']) {
            $this->api->emitirErro('Estrutura de dados nao reconhecida para esta rota');
        }

        $mtz = $args['item'];

        if (!($mtz['codigo'] || $mtz['codigoBarras'])) {
            $this->api->emitirErro('As chaves codigo ou codigoBarras devem ser informadas');
        }

        $this->api->validarCamposObrigatorios($mtz, array('siglaUnidade'));

        $where = array();
        $where[] = "(I.id_pessoas_proprietario = '" . $this->api->idPessoasProprietario . "')";

        if ($mtz['codigo']) {
            $where[] = "(SK.codigo = '".gCleanField($mtz['codigo'])."')";
        }

        if ($mtz['codigoBarras']) {
            $where[] = "(SK.codigo_barras = '" . gCleanField($mtz['codigoBarras']) . "')";
        }

        if ($mtz['siglaUnidade']) {
            $sql = "SELECT id FROM unidades WHERE sigla = '" . gCleanField($mtz['siglaUnidade']) . "' LIMIT 1";
            $rs = $this->api->integracao->executarQuery($sql);
            if (!$rs) {
                $this->api->emitirErro('A sigla de unidade informada nao existe na base de dados');
            }

            $where[] = "(UN.id=" . $rs[0]['id'] . ")";
        }

        if ($mtz['id']) {
            $where[] = "(SK.id = '" . gCleanField($mtz['id']) . "')";
        }
        $where = implode(" AND ", $where);

        $sql = "SELECT
                    SK.id,
                    UN.sigla AS sigla_unidade,
                    SK.codigo,
                    SK.altura,
                    SK.largura,
                    SK.codigo_barras,
                    SK.quantidade,
                    SK.comprimento,
                    SK.palete_lastro,
                    SK.palete_altura,
                    I.ncm,
                    I.nome,
                    I.descricao,
                    I.exige_lote,
                    I.ativo
			    FROM itens I
			    JOIN itens_skus SK ON I.id = SK.id_itens
                JOIN unidades UN ON UN.id = SK.id_unidades
			    WHERE {$where}
			    ORDER BY I.ativo DESC, SK.ativo DESC LIMIT 1";
        $idItensSkus = $this->api->integracao->executarQuery($sql)[0];
        if (!$idItensSkus) {
            $this->api->emitirErro("Nada encontrado a partir dos filtros especificados");
        }

        $retorno = [
            'item' => [
                'id' => $idItensSkus['id'],
                'siglaUnidade' => $idItensSkus['sigla_unidade'],
                'codigo' => $idItensSkus['codigo'],
                'altura' => $idItensSkus['altura'],
                'largura' => $idItensSkus['largura'],
                'codigoBarras' => $idItensSkus['codigo_barras'],
                'quantidade' => $idItensSkus['quantidade'],
                'comprimento' => $idItensSkus['comprimento'],
                'paleteLastro' => $idItensSkus['palete_lastro'],
                'paleteAltura' => $idItensSkus['palete_altura'],
                'ncm' => $idItensSkus['ncm'],
                'nome' => $idItensSkus['nome'],
                'descricao' => $idItensSkus['descricao'],
                'ativo' => $idItensSkus['ativo']
            ]
        ];

        if ($this->api->integracao->erros) {
            $this->api->emitirErro($this->api->integracao->erros);
        }

        $this->api->finalizarRequisicao(true, $retorno, 200);
    }

    public function verificarSeItemExiste($codigoBarras, $siglaUnidade)
    {
        $sql = "SELECT
                    SK.id
                FROM itens I
                JOIN itens_skus SK ON I.id = SK.id_itens
                JOIN unidades U ON U.id = SK.id_unidades
                WHERE SK.codigo_barras = '" . gCleanField($codigoBarras) . "'
                    AND I.id_pessoas_proprietario = '{$this->api->idPessoasProprietario}'
                    AND U.sigla = '" . gCleanField($siglaUnidade) . "'
                ORDER BY I.ativo DESC, SK.ativo DESC LIMIT 1";
        return (int) $this->api->integracao->executarQuery($sql);
    }
}