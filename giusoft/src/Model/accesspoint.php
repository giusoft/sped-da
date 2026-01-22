<?php

$ambiente = '';
if (in_array('teste', explode("/", (string) $_SERVER['REQUEST_URI']))) {
    $ambiente = '/teste';
}

require_once $_SERVER["DOCUMENT_ROOT"] . $ambiente . "/emitenota/giusoft/src/Model/integracao.php";

class PontoAcesso
{
    public $parametros;
    public $debug = 0;

    public $dadosEnviar;

    public $idPessoasProprietario = 1;

    public $integracao;

    public $objetoGenerico;

    public $lastStatusCode;

    public $responseHeaders = [];

    public function __construct($param)
    {
        $this->integracao = new Integracao(montarParametrosIntegracao($param['empresa']));
    }


    public function dispararJson($momento, $json)
    {
        $sql = "SELECT url_rota, http_verbo
                FROM gatilhos
                WHERE metodo = '{$momento}'";
        $resultados = $this->integracao->executarQuery($sql);

        $headers = ['Content-Type' => 'application/json'];

        foreach ($resultados as $linha) {
            $this->executarRequest(
                $linha["http_verbo"],
                $linha["url_rota"],
                $json,
                $headers
            );
        }
    }


    public function executarRequest($verboHttp, $url, $dados = [], $headers = [], $dadosRequisicao = [])
    {
        global $usrId;

        $curl = $this->configurarCurl($verboHttp, $url, $dados, $headers);

        $requisicaoEnviar = [];
        $requisicaoEnviar['idProgramacao']       = $this->parametros['idProgramacao'];
        $requisicaoEnviar['idPessoasCriou']      = $usrId ?: 1;
        $requisicaoEnviar['idGatilhos']          = $dadosRequisicao['rotas']['id'];
        $requisicaoEnviar['sucesso']             = 0;
        $requisicaoEnviar['pendente']            = 1;
        $requisicaoEnviar['recebido']            = '';
        $requisicaoEnviar['idGatilhoRequisicao'] = $dadosRequisicao['idGatilhoRequisicao'];
        $requisicaoEnviar['idGatilhoRequisicaoDetalhes'] = $dadosRequisicao['idGatilhoRequisicaoDetalhes'];

        $enviarTempoReal = $dadosRequisicao['rotas']['enviarTempoReal'];

        $requisicaoEnviar['numeroTentativa'] = $dadosRequisicao['numeroTentativa'] ?: 0;

        $dadosEnviar = $this->integracao->salvarRequisicao($this->dadosEnviar, $requisicaoEnviar);

        if (!$enviarTempoReal && !$this->parametros['task']) {
            return;
        }

        $resposta = curl_exec($curl);
        $this->lastStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $tempoExecucao = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
        $erro = curl_error($curl);
        $codigoErroCurl = curl_errno($curl);
        curl_close($curl);

        if ($this->debug) {
            error_log("*Debug: ENVIADO:: {$this->dadosEnviar}
                \n RECEBIDO:: " . str_replace("\\", "", json_encode($resposta)) .
                "\n TEMPO DE EXECUCAO:: {$tempoExecucao}s");
        }

        $dadosEnviar['requisitou'] = 1;
        if (!$erro && $this->lastStatusCode >= 200 && $this->lastStatusCode < 300) {
            $dadosEnviar['sucesso']  = 1;
            $dadosEnviar['pendente'] = 0;
            $dadosEnviar['recebido'] = $resposta;
            $dadosEnviar['tempo_execucao'] = $tempoExecucao;
            $this->integracao->salvarRequisicao($dados, $dadosEnviar);

            $resposta = [
                'statusHttp' => $this->lastStatusCode,
                'resposta' => $resposta
            ];

            return $resposta ?: true;
        } else {
            $resposta = [
                'statusHttp' => $this->lastStatusCode,
                'resposta' => $resposta,
                'erroCurl' => $codigoErroCurl . '-' . $erro
            ];
            $dadosEnviar['recebido'] = json_encode($resposta);
            $dadosEnviar['tempo_execucao'] = $tempoExecucao;
            $this->integracao->salvarRequisicao($dados, $dadosEnviar);

            return $resposta ?: false;
        }
    }


    public function configurarCurl($verboHttp, $url, $dadosParaEnvio = array(), $headers = array())
    {
        $curl = curl_init();
        $this->responseHeaders = [];

        $that = $this;
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $header) use ($that) {
            $len = strlen($header);
            $header_parts = explode(':', $header, 2);
            if (count($header_parts) < 2) {
                return $len;
            }

            $header_name = strtolower(trim($header_parts[0]));
            $header_value = trim($header_parts[1]);

            $that->responseHeaders[$header_name][] = $header_value;
            return $len;
        });

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, (int) $this->objetoGenerico->tempoLimiteCurl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $verboHttp = strtoupper($verboHttp);
        if (in_array($verboHttp, ['GET', 'DELETE']) && !empty($dadosParaEnvio)) {
            $url .= '?' . http_build_query($dadosParaEnvio);
        }

        curl_setopt($curl, CURLOPT_URL, $url);

        // Configurar a requisição com base no método HTTP
        if ($verboHttp === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            if (isset($headers["Content-Type"]) && $headers["Content-Type"] === 'application/json') {
                $this->dadosEnviar = is_array($dadosParaEnvio) ? json_encode($dadosParaEnvio) : $dadosParaEnvio;
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->dadosEnviar);
            }

            if (isset($headers["Content-Type"]) && $headers["Content-Type"] === 'application/x-www-form-urlencoded') {
                $this->dadosEnviar = http_build_query($dadosParaEnvio);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->dadosEnviar);
            }
        } elseif (in_array($verboHttp, ['PUT', 'PATCH'])) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $verboHttp);
            $this->dadosEnviar = is_array($dadosParaEnvio) ? json_encode($dadosParaEnvio) : $dadosParaEnvio;
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->dadosEnviar);
        } elseif ($verboHttp === 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($verboHttp === 'GET') {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }

        $this->setHeaders($curl, $headers);

        return $curl;
    }


    public function setHeaders($curl, $headers)
    {
        $listaCabecalhos = [];
        foreach ($headers as $chave => $valor) {
            $listaCabecalhos[] = sprintf('%s: %s', $chave, $valor);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $listaCabecalhos);
    }


    public function acionarEventoMomento($momento, $parametros)
    {
        $this->parametros = $parametros;

        if ($parametros['idPessoasProprietario']) {
            $this->idPessoasProprietario = $parametros['idPessoasProprietario'];
        }

        $sql = "SELECT
                    gatilhos_configuracoes.classe_integracao
                FROM
                    gatilhos_configuracoes
                JOIN gatilhos ON
                    gatilhos.id_gatilhos_configuracoes = gatilhos_configuracoes.id
                    AND gatilhos_configuracoes.id_pessoas_proprietario = {$this->idPessoasProprietario}
                WHERE
                    gatilhos.metodo = '{$momento}'
                    AND gatilhos.ativo = 1";
        $rs = $this->integracao->executarQuery($sql);

        if (!$rs) {
            if ($this->debug) {
                error_log(sprintf('*Debug => metodo %s nao cadastrado para este proprietario: ', $momento) . $this->idPessoasProprietario);
            }
        }

        foreach ($rs as $row) {

            if ($parametros['temRetorno']) {
                ###TODO:: pode falhar se tiver muitas integracoes, enviando apenas para uma e deixando de enviar para as demais
                return $this->dispararEvento($row['classe_integracao'], $momento, $parametros);
            }

            $this->dispararEvento($row['classe_integracao'], $momento, $parametros);

        }
    }


    public function dispararEvento($classe, $metodo, $parametros)
    {
        $sql = "SELECT
                    gatilhos.*,
                    gatilhos_configuracoes.usuario,
                    " . decriptBanco('senha') . " AS senha,
                    gatilhos_configuracoes.url_base,
                    gatilhos_configuracoes.tempo_limite
                FROM
                    gatilhos_configuracoes
                JOIN gatilhos ON
                    gatilhos.id_gatilhos_configuracoes = gatilhos_configuracoes.id
                WHERE
                    gatilhos_configuracoes.classe_integracao = '{$classe}'
                    AND gatilhos_configuracoes.id_pessoas_proprietario = {$this->idPessoasProprietario}
                    AND gatilhos.ativo = 1";
        $rotas = $this->integracao->executarQuery($sql);
        $this->objetoGenerico = $this->instanciarClasse($classe, ['rotas' => $rotas]);
        $this->objetoGenerico->pontoAcesso = $this;

        $this->objetoGenerico->integracao = $this->integracao;

        if (!$rotas) {
            if ($this->debug) {
                error_log(sprintf("*Debug => Nenhuma configuracao encontrada para classe: '%s'", $classe));
            }
        }

        $this->objetoGenerico->usuario = $rotas[0]['usuario'];
        $this->objetoGenerico->senha = $rotas[0]['senha'];
        $this->objetoGenerico->urlBase = $rotas[0]['url_base'];
        $this->objetoGenerico->tempoLimiteCurl = $rotas[0]['tempo_limite'];
        $this->objetoGenerico->classeIntegracao = $classe;
        $this->objetoGenerico->idPessoasProprietario = $this->idPessoasProprietario;

        ### se a requisao for enviar_tempo_real = 0, então não precisa autenticar
        if ($this->objetoGenerico->rotas[$metodo]['enviarTempoReal'] == 0 && !$parametros['task']) {
            $this->chamarMetodoClasse($this->objetoGenerico, $metodo, $parametros);
            return true;
        }

        ### so autenticar se for pra enviar mesmo, se for so pra armazenar o json entao nao precisa
        $autenticou = $this->objetoGenerico->autenticar($this->objetoGenerico->usuario, $this->objetoGenerico->senha);

        if ($parametros['task']) {
            return true; // devemos interromper o fluxo aqui porque nao precisamos chamar o metodo para montar o json, afinal o json ja existe no banco
        }

        if ($autenticou) {
            if ($metodo == "autenticar") {
                return true;
            }

            return $this->chamarMetodoClasse($this->objetoGenerico, $metodo, $parametros);
        }

        if ($this->debug) {
            error_log(sprintf('*Debug => Erro ao autenticar classe %s. Usuario: ', $classe) . $rotas[0]['usuario'] . ' Senha: ' . $rotas[0]['senha']);
        }

    }


    public function instanciarClasse($classe, $parametroClasse)
    {
        $dir = "";
        foreach (glob(__DIR__ . "/*") as $arquivo) {
            if (is_file($arquivo)){
                $classesAuxiliares[] = str_replace(".php", "", basename($arquivo));
            }
        }

        if (in_array('teste', explode("/", $_SERVER['REQUEST_URI']))) {
            $ambiente = '/teste';
        }
        $basePath = $_SERVER["DOCUMENT_ROOT"] . $ambiente . '/emitenota/giusoft/src';
        $pathClassesIntegracao = $basePath . '/Model/integracao/*';
        $classesIntegracao = [];

        foreach (glob($pathClassesIntegracao) as $subPath) {
            $classesIntegracao[] = str_replace($basePath . '/Model/integracao/', '', $subPath);
        }

        if (in_array($classe, $classesAuxiliares) && $dir == "") {
            $dir = __DIR__ . "/{$classe}.php";
            if ($classe == "index") {
                $classe = "Api";
            }
        }

        if (in_array(lcfirst($classe), $classesIntegracao) && $dir == "") {
            $dir = $basePath . "/Model/integracao/" . lcfirst($classe) . "/" . lcfirst($classe) . ".php";
        }

        if (file_exists($dir)) {
            require_once $dir;
            if ($classe == "api_wms") {
                $classe = "Api";
            }
            if (class_exists(ucfirst($classe))) {
                return new $classe($parametroClasse);
            }
        }

    }


    public function chamarMetodoClasse($objectClass, $metodo, $parametros)
    {
        $className = $objectClass::class;
        if (!method_exists($objectClass, $metodo)) {
            if ($this->debug) {
                error_log(sprintf('Metodo %s nao encontrado na classe %s', $metodo, $className));
            }

            return null;
        }

        return $objectClass->{$metodo}($parametros);
    }


    public function testarConexao($classe, $parametros)
    {
        if ($parametros['idPessoasProprietario']) {
            $this->idPessoasProprietario = $parametros['idPessoasProprietario'];
        }

        $metodo = "autenticar";

        return $this->dispararEvento($classe, $metodo, $parametros);
    }


    public function getLastStatusCode()
    {
        return $this->lastStatusCode;
    }

    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }
}
