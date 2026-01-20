<?php

use Api\programacao as Programacao;
use Api\uma as Uma;

$ambiente = '';
if (in_array('teste', explode("/", (string) $_SERVER['REQUEST_URI']))) {
    $ambiente = '/teste';
}

require_once $_SERVER["DOCUMENT_ROOT"] . $ambiente . "/emitenota/giusoft/src/view/api/accesspoint.php";

class Api
{
    public $ip;

    public $token;

    public $headers;

    public $idArmazens;

    public $integracao;

    public $rotaCompleta;

    public $nomeArquivoLog;

    public $corpoRequisicao;

    public $idPessoasProprietario;

    public $ambienteDesenvolvimento = 0;

    public $request;
    public $response;

    public $empresa;

    public $privateKey;

    public function __construct()
    {
        $this->inicializarAmbienteApi();
        $this->processarRequisicao();
    }


    public function inicializarAmbienteApi()
    {
        $parametros = montarParametrosIntegracao();

        $this->nomeArquivoLog = "integracao_wms_" . $parametros['empresa'] . ".log";
        $parametros['transacao'] = false;

        $this->integracao = new Integracao($parametros);
        $this->empresa = $parametros['empresa'];

        $this->headers = apache_request_headers();
        if (!$this->headers) {
            $this->emitirErro("Cabecalhos HTTP ausentes", "400 Bad Request");
        }

        if (
            isset($this->headers['Content-Type'])
            && $this->headers['Content-Type'] != 'application/json'
            && apiSendoUsadaExternamente()
        ) {
            $this->emitirErro("Content-Type invalido", "415 Unsupported Media Type");
        }

        $this->ip = obterEnderecoIp();
        $this->rotaCompleta = $_SERVER['REQUEST_URI'];
        $this->corpoRequisicao = decodificarJson(file_get_contents('php://input'), $this->nomeArquivoLog);

    }


    public function processarRequisicao()
    {
        $this->validarAutenticacao();

        if (apiSendoUsadaExternamente()) {
            $this->chamarMetodoClasse();
        }
    }


    public function validarAutenticacao()
    {
        if ($this->ambienteDesenvolvimento == 1) {
            define("EXPJWT", "100000");
            $this->idPessoasProprietario = $_GET["idPessoasProprietario"];
            if ($_GET["rota"] == "autenticar") {
                $this->emitirErro("Ambiente de Desenvolvimento nao necessita de autenticacao");
            }

            if ($this->headers['Authorization']) {
                unset($this->headers['Authorization']);
            }

            if (!$_GET["idPessoasProprietario"]) {
                $this->emitirErro("Proprietario nao informado", "401 Unauthorized");
            }

            // Eh necessario obter o armazem aqui pois o ambiente de desenvolvimento nao faz a autenticacao
            $this->obterArmazem();

            return true;
        }

        define("EXPJWT", "3600");

        if (!apiSendoUsadaExternamente()) {
            return null;
        }

        if ($_GET["rota"] == "autenticar") {
            $this->autenticar($this->corpoRequisicao);
            exit;
        }

        if (empty($this->headers['Authorization'])) {
            header("X-Missing-Auth-Field: Authorization");
            $this->emitirErro("Cabecalho de autorizacao ausente", "401 Unauthorized");
        }

        $token = str_replace("Bearer ", "", $this->headers['Authorization']);
        $dadosToken = $this->obterDadosToken(null, $token);

        if (!$this->verificarSeTokenEhValido($dadosToken)) {
            $this->emitirErro("Token expirado", "401 Unauthorized");
        }

    }


    public function chamarMetodoClasse()
    {
        $this->validarLimiteRequisicoes();

        if (in_array('teste', explode("/", (string) $_SERVER['REQUEST_URI']))) {
            $ambiente = '/teste';
        }

        include_once $_SERVER["DOCUMENT_ROOT"] . $ambiente . "/emitenotaweb/giusoft/res/api/" . $_GET["rota"] . ".php";// tem que ser include porque nao pode danificar o funcionamento da aplicacao

        $classeCompleta = "Api\\" . ucfirst(trim((string) $_GET['rota']));
        if (!class_exists($classeCompleta)) {
            return $this->emitirErro(sprintf('A rota %s não existe', $classeCompleta));
        }

        $classe = new $classeCompleta($this);

        if (!method_exists($classe, $_GET["recurso"])) {
            return $this->emitirErro("Recurso " . $_GET["recurso"] . " nao encontrado para a rota " . $_GET["rota"] . "");
        }

        return $classe->{$_GET["recurso"]}($this->corpoRequisicao);
    }


    public function autenticar($credenciais)
    {
        $this->validarCamposObrigatorios($credenciais, ["usuario", "senha"]);
        $usuario = $this->obterUsuario($credenciais);
        $dadosToken = $this->obterDadosToken($usuario["id"]);

        if ($credenciais["codigoArmazem"] || !$this->idArmazens) {
            $this->obterArmazem($credenciais["codigoArmazem"]);
        }

        if ($dadosToken) {
            $this->response = ["token" => $dadosToken['token']];

            if ($this->verificarSeTokenEhValido($dadosToken)) {
                $this->emitirErro($this->response, "409 - Usuario ja possui token valido");
            }
        }

        $novoToken = $this->gerarToken($usuario["id"]);
        $this->finalizarRequisicao(true, ["token" => $novoToken["token"]], 200);
    }


    public function obterDadosToken($id, $token = null)
    {
        $where = sprintf("token = '%s'", $token);
        if (!$token) {
            $where = 'id_pessoas = ' . $id;
        }

        $sql = "SELECT
                    id_pessoas,
                    id_armazens,
                    hora_criacao,
                    hora_expiracao,
                    data_criacao,
                    expirado,
                    token
                FROM tokens
                WHERE expirado = 0
                    AND data_criacao = CURDATE()
                    AND ip_address = '{$this->ip}'
                    AND {$where}
                ORDER BY id
                DESC LIMIT 1";

        $dadosToken = $this->integracao->executarQuery($sql)[0];

        if ($dadosToken) {
            $this->idPessoasProprietario = $dadosToken['id_pessoas'];
            $this->idArmazens = $dadosToken['id_armazens'];
            unset($dadosToken['id_pessoas'], $dadosToken['id_armazens']);
            return $dadosToken;
        }

        return null;
    }


    public function obterUsuario($args)
    {
        $usuario = $args["usuario"];
        $senha = md5((string) $args["senha"]);

        $sql = sprintf("SELECT id FROM pessoas WHERE apelido = '%s' AND senha = '%s'", $usuario, $senha);
        $idUsuario  = $this->integracao->executarQuery($sql)[0]["id"];

        if (!$idUsuario) {
            $this->emitirErro("Usuario ou senha incorretos", "404");
        }

        $this->idPessoasProprietario = $idUsuario;

        return ["id" => $this->idPessoasProprietario];
    }


    public function obterArmazem($codigoArmazem = "")
    {
        $sql = "SELECT id
            FROM armazens
            WHERE descricao = '{$codigoArmazem}'
            LIMIT 1";

        if (!$codigoArmazem) {
            $sql = 'SELECT id_armazens AS id
                    FROM pessoas_armazens
                    WHERE cancelado = 0
                        AND id_pessoas = ' . $this->idPessoasProprietario;
        }

        $idArmazem = $this->integracao->executarQuery($sql)[0]['id'];

        if (!$idArmazem) {
            $this->emitirErro("Armazem nao encontrado", "404");
        }

        $this->idArmazens = $idArmazem;
    }


    public function verificarSeTokenEhValido($dadosToken)
    {
        $diferenca = ((int) strtotime(date('H:i:s'))) - ((int) $dadosToken['hora_criacao']);

        if (
            $diferenca >= EXPJWT
            || $dadosToken["data_criacao"] != date('Y-m-d')
            || $diferenca < 0
        ) {
            $this->integracao->executarQuery("UPDATE tokens SET expirado = 1 WHERE token = '" . $dadosToken["token"] . "'");
            return false;
        }

        return true;
    }


    public function gerarToken($id)
    {
        $this->gerarChavesAleatorias();
        $header = [
            "alg" => "RS256",
            "typ" => "JWS"
        ];

        $payload = [
            "iss" => "giusoft",               // (iss - Issuer) Empresa emissora do token
            "sub" => "wms_api",               // (sub - Subject) Assunto do token
            "aud" => 'wms_' . $this->empresa,  // (aud - Audience) Destinatário do token
            "iat" => time(),                  // (iat - Issued At) Data e hora em que o token foi emitido
            "exp" => time() + (int) EXPJWT,   // (exp - Expiration) Data e hora em que o token expirar
            "jti" => md5($this->empresa.$id), // (jti - JWT ID) ID do token
            "typ" => "Bearer",                // (typ - Type) Tipo do token
            "address" => md5((string) $this->ip),      // (address - IP Address) Endereço IP do usuário
            "date" => date('Y-m-d h:m:s'),    // (date - Date) Data em que o token foi emitido
            "arm" => $this->idArmazens        // (arm - Armazem) Codigo id do armazem
        ];

        $jwt = base64Encode(json_encode($header)) .".". base64Encode(json_encode($payload));
        $this->token = $jwt . '.' . gerarAssinatura($this->privateKey, $jwt, true);

        $dadosToken = [
            "id_pessoas" => $id,
            "id_armazens" => $this->idArmazens,
            "hora_criacao" => $payload["iat"],
            "hora_expiracao" => $payload["exp"],
            "token" => $this->token,
            "data_criacao" => $payload["date"],
            "expirado" => 0,
            "ip_address" => $this->ip
        ];

        if ($this->integracao->insertTable("tokens", $dadosToken, 1)) {
            return ["token" => $dadosToken["token"]];
        }

        return '';
    }


    public function gerarChavesAleatorias()
    {
        $chavePrivada = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($chavePrivada, $chavePrivadaExportada);

        $this->privateKey = $chavePrivadaExportada;
    }


	public function validarCamposObrigatorios($params, $campos)
    {
        foreach ($campos as $campo) {
            if (!$params[$campo]) {
                $this->emitirErro(["erro" => 'Campo obrigatorio ' . $campo . ' nao informado.']);
            }
        }
    }


    public function emitirErro($mensagem, $status = "400 Bad Request")
    {
        if (is_array($mensagem)) {
            $mensagem = implode(';', $mensagem);
        }

        gLog('Erro: ' . $mensagem, 1, $this->nomeArquivoLog);
        $this->finalizarRequisicao(false, ["erro" => $mensagem], $status);
    }


    public function finalizarRequisicao($sucesso, $msg = '', $status = null)
    {
        $this->request->sucesso = (int) $sucesso;

        $dadosRequisicao = [
            "rota"      => ($_GET["rota"] != 'autenticar') ? $_GET["rota"] : 'index',
            "recurso"   => ($_GET["rota"] == 'autenticar') ? $_GET["rota"] : $_GET["recurso"]
        ];

        $this->salvarRequisicaoApi($msg, $this->corpoRequisicao);
        $this->registrarConsumoApi($sucesso, $dadosRequisicao['rota'], $dadosRequisicao['recurso']);

        header('HTTP/1.1 ' . $status);
        formatarJson($msg);
    }


    public function registrarConsumoApi($sucesso = 0, $rota = '', $recurso = '')
    {
        $sql = "SELECT
                    id,
                    quantidade_sucessos,
                    quantidade_erros
                FROM pessoas_consumo_api
                WHERE
                    id_pessoas_proprietario = '{$this->idPessoasProprietario}'
                    AND rota = '{$rota}'
                    AND recurso = '{$recurso}'
                    AND data_inicio >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
                    AND data_fim <= LAST_DAY(CURRENT_DATE)
                LIMIT 1";

        $rs = $this->integracao->executarQuery($sql)[0];

        $sucesso = (int) $sucesso;
        $erros = (int) !$sucesso;

        if ($rs) {
            $sql = "UPDATE pessoas_consumo_api
                    SET quantidade_sucessos = quantidade_sucessos + {$sucesso},
                        quantidade_erros = quantidade_erros + {$erros}
                    WHERE id = " . $rs["id"];
            $this->integracao->executarQuery($sql);
            return;
        }

        $sql = sprintf("SELECT quantidade_contratada FROM pessoas_consumo_api WHERE id_pessoas_proprietario = '%s' ORDER BY id DESC LIMIT 1", $this->idPessoasProprietario);
        $quantidadeContratada = $this->integracao->executarQuery($sql)[0]['quantidade_contratada'];

        $dadosPessoasConsumoApi = [
            'id_pessoas_proprietario' => $this->idPessoasProprietario,
            'data_inicio' => date('Y-m-01'), // pega o primeiro dia do mes
            'data_fim' => date('Y-m-t'), // pega o último dia do mes
            'quantidade_sucessos' => $sucesso,
            'quantidade_erros' => $erros,
            'rota' => $rota,
            'recurso' => $recurso,
            'quantidade_contratada' => (int) $quantidadeContratada
        ];

        $this->integracao->insertTable("pessoas_consumo_api", $dadosPessoasConsumoApi);
    }


    public function salvarRequisicaoApi($enviado, $recebido)
    {

        $metodo = $_GET["rota"];
        if (isset($_GET['recurso']) && !empty($_GET['recurso'])) {
            $metodo .= "_" . $_GET['recurso'];
        }

        $sql = "SELECT gatilhos.id
                FROM gatilhos_configuracoes
                JOIN gatilhos ON gatilhos.id_gatilhos_configuracoes = gatilhos_configuracoes.id
                WHERE gatilhos.metodo = '" . $metodo . "'
                    AND gatilhos_configuracoes.classe_integracao = 'ApiWms'
                    AND gatilhos_configuracoes.id_pessoas_proprietario = '{$this->idPessoasProprietario}'";
        $idGatilhos = $this->integracao->executarQuery($sql)[0]['id'];

        if ($idGatilhos) {
            $dadosRequisicao = [];
            $dadosRequisicao['id_pessoas_criou'] = $this->idPessoasProprietario;
            $dadosRequisicao['id_gatilhos'] = $idGatilhos;

            $dadosRequisicao['pendente'] = (int) !$this->request->sucesso;
            $dadosRequisicao['enviado'] = base64_encode(json_encode(corrigirCodificacaoArray($enviado), JSON_UNESCAPED_UNICODE));
            $idGatilhoRequisicao = $this->integracao->insertTable("gatilhos_requisicoes", $dadosRequisicao, 1); // 1 = para retornar o id depois de inserir

            $dadosRequisicaoDetalhes = [];
            $dadosRequisicaoDetalhes['id_gatilhos_requisicoes'] = $idGatilhoRequisicao;
            $dadosRequisicaoDetalhes['data_hora'] = date("Y-m-d H:i:s");
            $dadosRequisicaoDetalhes['recebido'] = base64_encode(json_encode(corrigirCodificacaoArray($recebido), JSON_UNESCAPED_UNICODE));
            $dadosRequisicaoDetalhes['sucesso'] = $this->request->sucesso;
            $dadosRequisicaoDetalhes['numero_tentativa'] = 1;
            $dadosRequisicaoDetalhes['tempo_execucao'] = (int) (microtime(true) - $_SERVER["REQUEST_TIME"]);
            $this->integracao->insertTable("gatilhos_requisicoes_detalhes", $dadosRequisicaoDetalhes);
        }
    }


    public function validarLimiteRequisicoes()
    {
        $sql = "SELECT
                    PGC.limite_requisicoes_periodo,
                    COUNT(GRD.id) AS total,
                    MIN(GRD.data_hora) AS primeira_requisicao
                FROM gatilhos_configuracoes PGC
                LEFT JOIN gatilhos PG
                    ON PG.id_gatilhos_configuracoes = PGC.id
                LEFT JOIN gatilhos_requisicoes GR
                    ON GR.id_gatilhos = PG.id
                LEFT JOIN gatilhos_requisicoes_detalhes GRD
                    ON GRD.id_gatilhos_requisicoes = GR.id
                    AND GRD.data_hora >= (NOW() - INTERVAL
                        CAST(SUBSTRING_INDEX(PGC.limite_requisicoes_periodo, '/', -1) AS UNSIGNED) MINUTE)
                    AND GRD.sucesso = 1
                WHERE PGC.id_pessoas_proprietario = '{$this->idPessoasProprietario}'
                    AND PG.passiva = 1
                    AND PGC.classe_integracao = 'ApiWms'
                LIMIT 1";
        $rs = $this->integracao->executarQuery($sql)[0];

        if (!$rs || !$rs['limite_requisicoes_periodo']) {
            return true; // sem limite configurado
        }

        [$limite, $tempoMin] = explode("/", (string) $rs['limite_requisicoes_periodo']);

        if ($rs['total'] >= $limite) {
            $primeiraReq = strtotime((string) $rs['primeira_requisicao']);
            $tempoPassado = time() - $primeiraReq;
            $tempoRestante = $tempoMin * 60 - $tempoPassado;

            $this->emitirErro(sprintf('Você excedeu o limite de %s requisições a cada %s minuto(s). Seu acesso será liberado em ', $limite, $tempoMin) . floor($tempoRestante / 60) . " minuto(s) e " . $tempoRestante % 60 . " segundo(s).");
            exit;
        }

        return true;
    }


    public static function validarParametrosApi()
    {

        if (!apiSendoUsadaExternamente()) {
            return true;
        }

        $_GET = array_map('gCleanField', $_GET);

        if (empty($_GET['rota'])) {
            header("HTTP/1.1 400");
            formatarJson(["erro" => "Rota nao informada"]);
        }

        if (empty($_GET['recurso']) && $_GET['rota'] != "autenticar") {
            header("HTTP/1.1 400");
            formatarJson(["erro" => "Recurso nao informado"]);
        }

        return true;
    }
}


if (Api::validarParametrosApi()) {
    $api = new Api();
}
