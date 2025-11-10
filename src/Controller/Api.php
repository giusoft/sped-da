<?php

namespace App\Controller;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use App\Model\Nfe;
use App\Model\Danfe;
use App\Model\Sefaz;
use App\Model\db;

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

        $parametros = array(
            'caminhoSetup' => '/var/www/html/wms/logiclog/setup.php'
        );

        $db = new DB($parametros);

        $sql = "SELECT
                    schemes,
                    tpAmb,
                    regime,
                    versao_xml,
                    " . desencriptar('senhaCertificado') . " AS senhaCertificado
                FROM armazens_notas WHERE cnpj = '{$cnpjLimpo}'";
        $config = $db->executarQuery($sql);

        if (!$config) {
            emitirErro("CNPJ informado nao possui certificado valido ou nao existe", 400);
        }

        $certNome = "certificado.pfx";
        $certSenha = $config[0]['senhaCertificado'];
        $certPath = __DIR__ . "/../Certificados/{$cnpjLimpo}/{$certNome}";

        $this->corpoRequisicao['empresa']['schemes'] = $config[0]['schemes'];
        $this->corpoRequisicao['empresa']['tpAmb']   = $config[0]['tpAmb'];
        $this->corpoRequisicao['empresa']['regime']  = $config[0]['regime'];
        $this->corpoRequisicao['empresa']['versao']  = $config[0]['versao_xml'];

        if (!file_exists($certPath)) {
            emitirErro("Certificado não encontrado: {$certPath}", 400);
        }

        $certificate = Certificate::readPfx(
            file_get_contents($certPath),
            $certSenha
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