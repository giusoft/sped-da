<?php

namespace Api;

class Empresa
{
    public function __construct(protected $api) {}

    public function cadastrarClienteFinal($args)
    {
        /*
            verifica se o objeto item existe no json
            verifica se item existe no banco de dados, se não existe: cadastrar item
            dados: itens.descricao, itens.ncm, itens.codigo, itens.codigo_barras, itens_skus.palete_lastro (opcional), itens_skus.palete_altura  (opcional), quantidade, sigla unidade, descricao unidade, largura, altura, comprimento, 
            se parametro $gParam["USA_REGRA_PALETIZACAO"]["ativo"]==1 obrigar informarnorma de paletizacao

            se tudo der errado ou tiver um fluxo interrompido, finalizar o processamento e transação
        */

        /*
            "empresa": {
                "nome": "TESTE EMPRESA UNIKLOG",
                "codigoSistemaExterno": "84857452452545",
                "razaoSocial": "UNIKLOG DISTRIBUIDORA",
                "cnpj": "12345678901234",
                "inscEstadual": "12345678901234",
                "inscMunicipal": "12345678901234",
                "complemento": "Salvador",
                "endereco": "Rua Itagi",
                "cep": "42717-454"
            }
        */

        if (!$args['empresa']) {
            $this->api->emitirErro('Estrutura de dados nao reconhecida para esta rota');
        }

        $mtz = $args['empresa'];

        $this->api->validarCamposObrigatorios   ($mtz, ['nome', 'codigoSistemaExterno', 'razaoSocial', 'endereco', 'cep', 'cnpj']);
        $mtz['codigoSistemaExterno'] = substr((string) $mtz['codigoSistemaExterno'], 0, 50);
        $mtz['cnpj'] = substr((string) preg_replace("/[^0-9]/", "", (string) $mtz['cnpj']), 0, 14);
        $empresaExiste = $this->verificarSeEmpresaExiste($mtz['codigoSistemaExterno'], $mtz['cnpj']);

        if ($empresaExiste) {
            $this->api->emitirErro('Empresa ja cadastrada');
        }

        $pessoa = [];
        $pessoa['tipo']             = 'J';
        $pessoa['nome']             = substr((string) $mtz['nome'], 0, 100);
        $pessoa['apelido']          = substr((string) $mtz['nome'], 0, 50);
        $pessoa['situacao']         = 'Ativo';
        $pessoa['cliente_final']    = 1;
        $pessoa['id_pessoas_criou'] = $this->api->idPessoasProprietario;

        $juridico = [];
        $juridico['codigo_sistema_externo'] = $mtz['codigoSistemaExterno'];
		$juridico['razao_social'] = substr((string) $mtz['razaoSocial'], 0, 100);
		$juridico['observacoes']  = "Criado automaticamente pela api";
        $juridico['cnpj'] = $mtz['cnpj'];
        $juridico['insc_estadual'] = substr((string) preg_replace("/[^0-9]/", "", (string) $mtz['inscEstadual']), 0, 14);
        $juridico['insc_municipal'] = substr((string) preg_replace("/[^0-9]/", "", (string) $mtz['inscMunicipal']), 0, 14);

        $endereco = [];
		$endereco['cep'] = substr((string) preg_replace("/[^0-9]/", "", (string) $mtz['cep']), 0, 9);
		$endereco['endereco'] = substr((string) $mtz['endereco'], 0, 100);
		$endereco['complemento'] = substr((string) $mtz['complemento'], 0, 100);

        $detalhesEmpresa = $this->api->integracao->cadastrarEmpresa($pessoa, $juridico, $endereco, 1);

        return $this->api->finalizarRequisicao(true, [
            'empresa' => [
                'id' => $detalhesEmpresa['id'],
                'codigoSistemaExterno' =>  $detalhesEmpresa['codigoSistemaExterno']
            ]
        ], 200);
    }

    public function verificarSeEmpresaExiste($codigoSistemaExterno, $cnpj)
    {
        $sql = "SELECT
                    pessoas_juridicas.id_pessoas
                FROM pessoas_juridicas
                WHERE pessoas_juridicas.codigo_sistema_externo = '" . gCleanField($codigoSistemaExterno) . "'
                    AND pessoas_juridicas.cnpj = '" . gCleanField($cnpj) . "'
                LIMIT 1";
       return (int) $this->api->integracao->executarQuery($sql)[0]['id_pessoas'];
    }

    public function listar($args)
    {
        /*
            "empresa": {
                "codigoSistemaExterno": "7891095000494",
                "cnpj": "12345678901234"
            }
        */

        if (!$args['empresa']) {
            $this->api->emitirErro('Estrutura de dados nao reconhecida para esta rota');
        }

        $mtz = $args['empresa'];

        $this->api->validarCamposObrigatorios($mtz, ['codigoSistemaExterno']);

        $where = [];
        if ($mtz['codigoSistemaExterno']) {
            $where[] = "(PJ.codigo_sistema_externo = '" . gCleanField($mtz['codigoSistemaExterno']) . "')";
        }

        if ($mtz['cnpj']) {
            $where[] = "(PJ.cnpj = '" . gCleanField($mtz['cnpj']) . "')";
        }

        $where = implode(' AND ', $where);

        $sql = "SELECT
					P.id,
                    P.nome,
					P.tipo,
					PJ.razao_social,
					PJ.cnpj,
					PJ.insc_estadual,
					PJ.insc_municipal,
					PJ.codigo_sistema_externo,
					PE.complemento,
					PE.endereco,
					PE.numero,
					PE.cep
					FROM pessoas P
                LEFT JOIN pessoas_juridicas PJ ON PJ.id_pessoas = P.id
                LEFT JOIN pessoas_enderecos PE ON PE.id_pessoas = P.id
                WHERE " . $where . "
                LIMIT 1;";
        $empresa = $this->api->integracao->executarQuery($sql)[0];
        if (!$empresa) {
            $this->api->emitirErro('Nenhuma empresa encontrada');
        }

        $retorno = [
            'empresa' => [
                'id' => $empresa['id'],
                'nome' => $empresa['nome'],
                'tipo' => $empresa['tipo'],
                'razaoSocial' => $empresa['razao_social'],
                'cnpj' => $empresa['cnpj'],
                'inscricaoEstadual' => $empresa['insc_estadual'],
                'inscricaoMunicipal' => $empresa['insc_municipal'],
                'codigoSistemaExterno' => $empresa['codigo_sistema_externo'],
                'complemento' => $empresa['complemento'],
                'endereco' => $empresa['endereco'],
                'cep' => $empresa['cep']
            ]
        ];

        return $this->api->finalizarRequisicao(true, $retorno, 200);
    }
}