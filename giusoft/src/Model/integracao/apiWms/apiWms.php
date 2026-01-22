<?php

use Api\programacao as Programacao;
use Api\uma as Uma;

if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
    $ambiente = '/teste';
}

require_once $_SERVER["DOCUMENT_ROOT"] . $ambiente . "/wms/giusoft/res/api/accesspoint.php";
require_once $_SERVER["DOCUMENT_ROOT"] . $ambiente . "/wms/giusoft/res/api/programacao.php";
require_once $_SERVER["DOCUMENT_ROOT"] . $ambiente . "/wms/giusoft/res/api/uma.php";
require_once $_SERVER["DOCUMENT_ROOT"] . $ambiente . "/wms/giusoft/res/api/index.php";

class ApiWms
{

    public $api;
    public $token;
    public $idPessoasProprietario;
    public $integracao;
    public $agrupamentoSaldo;

    public function __construct($params)
    {
        $this->agrupamentoSaldo = ["agruparPor" => "status" ];
        $this->rotas = $this->montarRotas($params["rotas"]);
        $this->api = new Api();
    }


    public function autenticar($usuario, $senha)
    {
        $args['usuario'] = $usuario;
        $args['senha']   = $senha;
        $args['codigoFilial'] = $_SESSION['filialAtualDescricao'];

        $user = $this->api->obterUsuario($args);
        $dataToken = $this->api->obterDadosToken($user["id"]);
        $urlCompleta = $this->montarUrlCompleta("autenticar");
        $dadosRequisicao['rotas'] = $this->rotas["autenticar"];

        if (!is_null($dataToken)) {
            $args = [];
            $args['token']          = $dataToken['token'];
            $args['expirado']       = $dataToken['expirado'];
            $args['idFilial']     = $user['filial'];
            $args['hora_criacao']   = $dataToken['hora_criacao'];
            $args['data_criacao']   = $dataToken['data_criacao'];
            $args['hora_expiracao'] = $dataToken['hora_expiracao'];

            if ($this->api->verificarSeTokenEhValido($args)) {
                $this->token = $dataToken['token'];
                $this->response = ["token" => $dataToken['token']];
                return true;
            }

        }

        $rs = $this->api->gerarToken($user["id"]);

        // Salva o novo Token Gerado em gerarToken
        if ($rs["token"]) {
            $this->token = $rs["token"];
            if ($this->idPessoasProprietario && $this->classeIntegracao) {
                $sql = "UPDATE gatilhos_configuracoes
                        SET ultimo_token_valido = '{$this->token}',
                            data_hora_token_valido = NOW()
                        WHERE id_pessoas_proprietario = {$this->idPessoasProprietario}
                            AND classe_integracao = '{$this->classeIntegracao}'";
                dbFastQuery($sql);
            }

            return true;
        }

        return false;
    }


    public function montarUrlCompleta($nomeRota)
    {
        return rtrim($this->urlBase, '/') . '/' . ltrim($this->rotas[$nomeRota]['url'], '/');
    }


    public function acessarRota($nomeRota, $body = array(), $dadosRequisicao)
    {

        $urlCompleta = $this->montarUrlCompleta($nomeRota);
        $dadosRequisicao['rotas'] = $this->rotas[$nomeRota];

        if (!$this->pontoAcesso->parametros['task']) {
            $body = json_encode($body);
        }

        return $this->pontoAcesso->executarRequest($this->rotas[$nomeRota]['verbo'], "{$urlCompleta}", $body, $this->montarHeader($nomeRota), $dadosRequisicao);
    }


    public function montarHeader($nomeRota)
    {
        $header = array(
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $this->token
        );

        if ($nomeRota == "autenticar") {
            unset($header["Authorization"]);
        }

        return $header;
    }


    public function montarRotas($rotasConsultadas)
    {
        $rotas = array();

        foreach ($rotasConsultadas as $rotasConsultada) {
            $rotas[$rotasConsultada['metodo']] = [
                'id'    => $rotasConsultada['id'],
                'url'   => $rotasConsultada['url_rota'],
                'verbo' => strtoupper($rotasConsultada['http_verbo']),
                'enviarTempoReal' => $rotasConsultada['enviar_tempo_real']
            ];
        }

        return $rotas;
    }


    public function consultarProgramacaoEntrada($dadosGatilho)
    {
        $this->api->idPessoasProprietario = $this->idPessoasProprietario;
        $programacao = new Programacao($this->api);

        $dadosGatilho['enviarJson'] = 1;
        $dadosGatilho['programacaoEntrada'] = ["id" => $dadosGatilho['idProgramacao']];

        return $programacao->consultarProgramacaoEntrada($dadosGatilho);
    }


    public function consultarProgramacaoSaida($dadosGatilho)
    {
        $this->api->idPessoasProprietario = $this->idPessoasProprietario;
        $programacao = new Programacao($this->api);

        $dadosGatilho['enviarJson'] = 1;
        $dadosGatilho['programacaoSaida'] = ["id" => $dadosGatilho['idProgramacao']];

        return $programacao->consultarProgramacaoSaida($dadosGatilho);
    }


    public function consultarSaldoAtual($dadosGatilho)
    {
        $this->api->idPessoasProprietario = $this->idPessoasProprietario;
        $programacao = new Uma($this->api);

        $dadosGatilho['enviarJson'] = 1;
        $dadosGatilho['saldo'] = $this->agrupamentoSaldo;

        return $programacao->consultarSaldo($dadosGatilho);
    }


    /****************************** GATILHOS ******************************/
    public function iniciarEntrada($dadosGatilho)
    {
        return $this->acessarRota('iniciarEntrada', corrigirCodificacaoArray(
            $this->consultarProgramacaoEntrada($dadosGatilho)
        ));
    }


    public function finalizarEntrada($dadosGatilho)
    {
        return $this->acessarRota('finalizarEntrada', corrigirCodificacaoArray(
            $this->consultarProgramacaoEntrada($dadosGatilho)
        ));
    }


    public function desfazerTudoEntrada($dadosGatilho)
    {
        return $this->acessarRota('desfazerTudoEntrada', corrigirCodificacaoArray(
            $this->consultarProgramacaoEntrada($dadosGatilho)
        ));
    }


    public function enviarRelatorioSaldoAtual($dadosGatilho)
    {
        return $this->acessarRota('enviarRelatorioSaldoAtual', corrigirCodificacaoArray(
            $this->consultarSaldoAtual($dadosGatilho)
        ));
    }


    public function desfazerEtapaOsSaida($dadosGatilho)
    {
        return $this->acessarRota('desfazerEtapaOsSaida', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }


    public function desfazerTudoOsSaida($dadosGatilho)
    {
        return $this->acessarRota('desfazerTudoOsSaida', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }


    public function iniciarSeparacaoOsSaida($dadosGatilho)
    {
        return $this->acessarRota('iniciarSeparacaoOsSaida', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }


    public function finalizarConferenciaOsSaida($dadosGatilho)
    {
        return $this->acessarRota('finalizarConferenciaOsSaida', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }


    public function finalizarSeparacaoOsSaida($dadosGatilho)
    {
        return $this->acessarRota('finalizarSeparacaoOsSaida', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }


    public function finalizarSaidaOsSaida($dadosGatilho)
    {
        return $this->acessarRota('finalizarSaidaOsSaida', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }


    public function desfazerEtapaOsApanha($dadosGatilho)
    {
        return $this->acessarRota('desfazerEtapaOsApanha', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }


    public function desfazerTudoOsApanha($dadosGatilho)
    {
        return $this->acessarRota('desfazerTudoOsApanha', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }


    public function finalizarConferenciaOsApanha($dadosGatilho)
    {
        return $this->acessarRota('finalizarConferenciaOsApanha', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }


    public function iniciarSeparacaoOsApanha($dadosGatilho)
    {
        return $this->acessarRota('iniciarSeparacaoOsApanha', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }


    public function finalizarSeparacaoOsApanha($dadosGatilho)
    {
        return $this->acessarRota('finalizarSeparacaoOsApanha', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }


    public function finalizarSaidaOsApanha($dadosGatilho)
    {
        return $this->acessarRota('finalizarSaidaOsApanha', corrigirCodificacaoArray(
            $this->consultarProgramacaoSaida($dadosGatilho)
        ));
    }

}