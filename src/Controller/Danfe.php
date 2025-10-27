<?php

namespace App\Controller;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use App\Model\Danfe as DanfeModel;

class Danfe
{
    public $corpoRequisicao;
    public $config;
    public $tools;


    public function __construct()
    {
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

        $configPath = __DIR__ . "/../config/empresas/{$cnpjLimpo}.json";

        if (!file_exists($configPath)) {
            emitirErro("Arquivo de configuração não encontrado para o CNPJ: {$cnpjLimpo}", 400);
        }
        $configJson = file_get_contents($configPath);
        $this->config = json_decode($configJson, true);

        $certNome = "certificado.pfx";
        $certSenha = $this->config['senhaCertificado'];
        $certPath = __DIR__ . "/../certificados/{$cnpjLimpo}/{$certNome}";
        if (!file_exists($certPath)) {
            emitirErro("Arquivo de certificado não encontrado: {$certPath}", 400);
        }

        $certificate = Certificate::readPfx(
            file_get_contents($certPath),
            $certSenha
        );

        $this->tools = new Tools(json_encode($this->config), $certificate);
        $this->tools->model('55');
    }


    public function chamarMetodoClasse()
    {
        $nfe = new DanfeModel($this);

        $metodo = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        if (method_exists($nfe, $metodo[1])) {
            return $nfe->{$metodo[1]}();
        }

        return false;
    }


}