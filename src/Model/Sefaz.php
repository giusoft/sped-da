<?php

namespace App\Model;

use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;

date_default_timezone_set('America/Bahia');

class Sefaz
{
    private $tools;
    private $corpoRequisicao;
    private $api;

    public function __construct($args)
    {
        $this->api = $args;
        $this->tools = $args->tools;
        $this->corpoRequisicao = $args->corpoRequisicao['empresa'];
    }


    public function consultarStatusSefaz()
    {

        try {
            $response = $this->tools->sefazStatus();

            $stdCl = new Standardize($response);
            $std = $stdCl->toStd();

            $ehProducao = ($std->tpAmb ?? 0) == 1 || ($this->corpoRequisicao['tpAmb'] ?? 0) == 1;

            $dados = [];
            $dados['operacional']        = ($std->cStat ?? 0) == 107;
            $dados['status_code']        = $std->cStat ?? null;
            $dados['motivo']             = $std->xMotivo ?? 'Resposta desconhecida';
            $dados['ambiente']           = $ehProducao ? 'Produção' : 'Homologação';
            $dados['uf']                 = $std->cUF ?? $this->corpoRequisicao['siglaUF'];
            $dados['data_hora_consulta'] = date('d/m/Y H:i:s');
            $dados['tempo_medio_ms']     = $std->tMed ?? null;

            $this->api->emitirSucesso($dados, 200);

        } catch (\Exception $e) {
            $this->api->emitirErro($e->getMessage(), 500);
        }
    }
}