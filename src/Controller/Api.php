<?php

namespace App\Controller;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use App\Model\Nfe;
use App\Model\Danfe;
use App\Model\Sefaz;
use App\Model\Certificado;
use App\Model\db;
use App\Model\Item;

class Api
{
    public $corpoRequisicao;
    public $tools;
    public $classes;
    public $db;
    public $requisicaoSalvar = [];

    public function __construct()
    {
        $this->classes = [
            'danfe' => Danfe::class,
            'sefaz' => Sefaz::class,
            'nfe' => Nfe::class,
            'certificado' => Certificado::class,
            'db' => DB::class,
            'item' => Item::class
        ];

        $parametros = ['caminhoSetup' => '/var/www/html/setup.php'];

        $this->db = new DB($parametros);

        $this->inicializarAmbiente();
        $this->processarRequisicao();
    }


    public function inicializarAmbiente()
    {
        $conteudo = file_get_contents('php://input');

        $this->corpoRequisicao = json_decode($conteudo, true);

        $requisicaoSalvar = [];
        $requisicaoSalvar['sucesso']         = 0;
        $requisicaoSalvar['pendente']        = 1;
        $requisicaoSalvar['recebido']        = '';
        $requisicaoSalvar['idPessoasCriou']  = 1;

        $this->requisicaoSalvar = $this->db->salvarRequisicao($this->corpoRequisicao, $requisicaoSalvar);

        $this->gravarLog($conteudo);
    }


    public function gravarLog($conteudo)
    {
        $arquivoLog = __DIR__ . '/../storage/log/emitenota.log';

        if (!file_exists($arquivoLog)) {
            return;
        }

        $data = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '-';
        $uri = $_SERVER['REQUEST_URI'] ?? '-';

        $texto = "[$data] IP: $ip | URI: $uri\nPAYLOAD: $conteudo\n" . str_repeat("-", 50) . "\n";

        file_put_contents($arquivoLog, $texto, FILE_APPEND);
    }


    public function processarRequisicao()
    {

        $rota = $_GET['rota'] ?? '';
        if ($rota == 'nfe' || $rota == 'sefaz') {
            $this->inicializarNFe();
        }

        $this->chamarMetodoClasse();

    }


    public function inicializarNFe()
    {
        try {

            $this->validarCamposObrigatorios($this->corpoRequisicao, array('cnpj_emitente', 'empresa'));

            $cnpjLimpo = soNumeros($this->corpoRequisicao['cnpj_emitente']);
            if (strlen($cnpjLimpo) != 14) {
                $this->emitirErro("CNPJ inválido: {$this->corpoRequisicao['cnpj_emitente']}", 400);
            }

            if (!$this->corpoRequisicao['empresa']['senhaCertificado']) {
                $this->emitirErro("Campo obrigatorio 'senhaCertificado' nao informado.", 400);
            }

            $certificado = $this->carregarCertificado($cnpjLimpo);

            $this->tools = new Tools(json_encode($this->corpoRequisicao['empresa']), $certificado);
            $this->tools->model('55');

        } catch (\Exception $e) {
            error_log("Erro ao processar certificado: " . $e->getMessage());
            $this->emitirErro("Erro ao processar requisição", 500, $e->getMessage());
        }
    }


    public function carregarCertificado(string $cnpjLimpo)
    {
        try {

            $certPath = __DIR__ . "/../storage/certificados/{$cnpjLimpo}/certificado.pfx";

            if (!file_exists($certPath) && !isset($this->corpoRequisicao["certificado"])) {
                $this->emitirErro("Certificado não encontrado!", 400);
            }

            $certificadoConteudo = isset($this->corpoRequisicao["certificado"])
                ? base64_decode($this->corpoRequisicao["certificado"])
                : file_get_contents($certPath);

            $senhaCertificado = desencriptar($this->corpoRequisicao['empresa']['senhaCertificado'], $this->corpoRequisicao['empresa']['chave'] ?? '');
            return Certificate::readPfx($certificadoConteudo, $senhaCertificado);

        } catch (\Exception $e) {
            error_log("Erro ao processar certificado: " . $e->getMessage());
            $this->emitirErro("Erro ao processar certificado", 500, traduzirErroCertificado($e->getMessage()));
        }
    }


    public function chamarMetodoClasse()
    {
        $rota = null;
        if (isset($_GET['rota'])) {
            $rota = $_GET['rota'];
        }

        $recurso = null;
        if (isset($_GET['recurso'])) {
            $recurso = $_GET['recurso'];
        }

        if (!$rota || !$recurso) {
            $this->emitirErro("Os parâmetros 'rota' e 'recurso' são obrigatórios na URL (ex: index.php?rota=nfe&recurso=enviar)", 400);
        }

        if (!isset($this->classes[$rota]) || !class_exists($this->classes[$rota])) {
            $this->emitirErro("A classe '{$rota}' não foi encontrada.", 404);
        }

        $classe = new $this->classes[$rota]($this);

        if (!method_exists($classe, $recurso)) {
            $this->emitirErro("Método '{$recurso}' não encontrado na classe '{$rota}'.", 404);
        }

        return $classe->{$recurso}($this->corpoRequisicao);
    }


    public function validarCamposObrigatorios($params, $campos)
    {
        foreach ($campos as $campo) {
            if (!isset($params[$campo])) {
                $this->emitirErro("Campo obrigatorio '{$campo}' nao informado.");
            }
        }
    }


    public function emitirSucesso($mensagem = 'Operacao concluida com sucesso', $codigoHttp = 200, $dados = [])
    {
        $resposta = [
            'sucesso' => true,
            'status' => $codigoHttp,
            'mensagem' => $mensagem,
        ];

        if ($dados) {
            $resposta['detalhes'] = $dados;
        }

        $this->requisicaoSalvar['idPessoasCriou'] = 1;
        $this->requisicaoSalvar['idGatilhos']     = 10;
        $this->requisicaoSalvar['sucesso']        = 1;
        $this->requisicaoSalvar['pendente']       = 0;
        $this->requisicaoSalvar['recebido']       = json_encode($resposta);

        $this->db->salvarRequisicao($this->corpoRequisicao, $this->requisicaoSalvar);

        finalizarRequisicao($resposta, $codigoHttp);
    }

    public function emitirErro($mensagem, $codigoHttp = 400, $dadosExtras = [])
    {
        $resposta = [
            'sucesso' => false,
            'status' => $codigoHttp,
            'mensagem' => $mensagem,
        ];

        if ($dadosExtras) {
            $resposta['detalhes'] = $dadosExtras;
        }

        $this->requisicaoSalvar['idPessoasCriou'] = 1;
        $this->requisicaoSalvar['idGatilhos']     = 10;
        $this->requisicaoSalvar['sucesso']        = 0;
        $this->requisicaoSalvar['pendente']       = 1;
        $this->requisicaoSalvar['recebido']       = json_encode($resposta);

        $this->db->salvarRequisicao($this->corpoRequisicao, $this->requisicaoSalvar);

        finalizarRequisicao($resposta, $codigoHttp);
    }

}