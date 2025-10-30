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
    public $config;
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

        $configPath = __DIR__ . "/../Config/empresas/{$cnpjLimpo}.json";

        if (!file_exists($configPath)) {
            emitirErro("Arquivo de configuração não encontrado para o CNPJ: {$cnpjLimpo}", 400);
        }
        $configJson = file_get_contents($configPath);
        $this->config = json_decode($configJson, true);

        $certNome = "certificado.pfx";
        $certSenha = $this->config['senhaCertificado'];
        $certPath = __DIR__ . "/../Certificados/{$cnpjLimpo}/{$certNome}";
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
        $dadosUrl = explode('/', trim($_SERVER['REQUEST_URI'], '/'));

        if (!class_exists($this->classes[$dadosUrl[0]])) {
            emitirErro("A classe {$classe} não existe");
        }

        $classe = new $this->classes[$dadosUrl[0]]($this);

        if (!method_exists($classe, $dadosUrl[1])) {
            emitirErro("Método " . $dadosUrl[1] . " não encontrado na classe " . $dadosUrl[0] . "");
        }

        return $classe->{$dadosUrl[1]}($this->corpoRequisicao);
    }

}