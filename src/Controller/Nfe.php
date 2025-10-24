<?php

namespace App\Controller;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use App\Model\Nfe as NfeModel;

class Nfe
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

        if (!$this->corpoRequisicao['cnpj_emitente']) {
            throw new \Exception('O campo "cnpj_emitente" é obrigatório.');
        }

        $this->buscarCertificado();
        $this->chamarMetodoClasse();

    }


    public function buscarCertificado()
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $this->corpoRequisicao['cnpj_emitente']);
        if (strlen($cnpjLimpo) != 14) {
            throw new \Exception("CNPJ inválido: {$this->corpoRequisicao['cnpj_emitente']}");
        }

        $configPath = __DIR__ . "/../config/empresas/{$cnpjLimpo}.json";

        if (!file_exists($configPath)) {
            throw new \Exception("Arquivo de configuração não encontrado para o CNPJ: {$cnpjLimpo}");
        }
        $configJson = file_get_contents($configPath);
        $this->config = json_decode($configJson, true);

        $certNome = "certificado.pfx";
        $certSenha = $this->config['senhaCertificado'];
        $certPath = __DIR__ . "/../certificados/{$cnpjLimpo}/{$certNome}";
        if (!file_exists($certPath)) {
            throw new \Exception("Arquivo de certificado não encontrado: {$certPath}");
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
        $nfe = new NfeModel($this); // chama o modelo, não o controller

        $metodo = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        if (method_exists($nfe, $metodo[1])) {
            return $nfe->{$metodo[1]}();
        }

        return false;
    }


}