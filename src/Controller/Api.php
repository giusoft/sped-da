<?php

namespace App\Controller;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use App\Model\Nfe;
use App\Model\Danfe;
use App\Model\Sefaz;

class Api
{
    public $corpoRequisicao;
    public $tools;
    public $classes;

    public function __construct()
    {
        $this->classes = [
            'danfe' => Danfe::class,
            'sefaz' => Sefaz::class,
            'nfe' => Nfe::class
        ];

        $this->inicializarAmbiente();
        $this->processarRequisicao();
    }


    public function inicializarAmbiente()
    {
        $this->corpoRequisicao = json_decode(file_get_contents('php://input'), true);
    }


    public function processarRequisicao()
    {

        if (!isset($this->corpoRequisicao['cnpj_emitente'])) {
            emitirErro("O campo 'cnpj_emitente' é obrigatório", 400);
        }

        $this->buscarCertificado();
        $this->chamarMetodoClasse();

    }


    public function buscarCertificado()
    {
        $cnpjLimpo = soNumeros($this->corpoRequisicao['cnpj_emitente']);
        if (strlen($cnpjLimpo) != 14) {
            emitirErro("CNPJ inválido: {$this->corpoRequisicao['cnpj_emitente']}", 400);
        }

        if (!isset($this->corpoRequisicao['empresa'])) {
            emitirErro("Os campos da empresa não foram informados", 400);
        }

        $senhaCertificado = openssl_decrypt(
            hex2bin($this->corpoRequisicao['empresa']['senhaCertificado']),   // Dados criptografados
            'AES-128-CBC',           // Modo de operação AES-128-CBC
            'emiteNota',             // Chave
            OPENSSL_RAW_DATA,        // Retorna os dados crus sem qualquer codificação
            str_repeat("\0", 16)     // IV (Vetor de Inicialização)
        );

        $certNome = "certificado.pfx";
        $certPath = __DIR__ . "/../Certificados/{$cnpjLimpo}/{$certNome}";

        if (!file_exists($certPath)) {
            emitirErro("Certificado não encontrado: {$certPath}", 400);
        }

        $certificate = Certificate::readPfx(
            file_get_contents($certPath),
            $senhaCertificado
        );

        $this->tools = new Tools(json_encode($this->corpoRequisicao['empresa']), $certificate);
        $this->tools->model('55');
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
            emitirErro("Os parâmetros 'rota' e 'recurso' são obrigatórios na URL (ex: index.php?rota=nfe&recurso=enviar)", 400);
        }

        if (!isset($this->classes[$rota]) || !class_exists($this->classes[$rota])) {
            emitirErro("A classe '{$rota}' não foi encontrada.", 404);
        }

        $classe = new $this->classes[$rota]($this);

        if (!method_exists($classe, $recurso)) {
            emitirErro("Método '{$recurso}' não encontrado na classe '{$rota}'.", 404);
        }

        return $classe->{$recurso}($this->corpoRequisicao);
    }

}