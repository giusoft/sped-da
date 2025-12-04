<?php

namespace App\Model;

use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;

date_default_timezone_set('America/Bahia');


class Sefaz
{
    private $tools;
    private $corpoRequisicao;

    public function __construct($dados)
    {
        $this->tools = $dados->tools;
        $this->corpoRequisicao = $dados->corpoRequisicao['empresa'];
    }


    public function consultarStatusSefaz()
    {
        try {
            $response = $this->tools->sefazStatus();

            $stdCl = new Standardize($response);
            $std = $stdCl->toStd();

            $ehProducao = ($std->tpAmb ?? 0) == 1 || ($this->corpoRequisicao['tpAmb'] ?? 0) == 1;

            emitirSucesso([
                'operacional'        => ($std->cStat ?? 0) == 107,
                'status_code'        => $std->cStat ?? null,
                'motivo'             => $std->xMotivo ?? 'Resposta desconhecida',
                'ambiente'           => $ehProducao ? 'Produção' : 'Homologação',
                'uf'                 => $std->cUF ?? $this->corpoRequisicao['siglaUF'],
                'data_hora_consulta' => date('d/m/Y H:i:s'),
                'tempo_medio_ms'     => $std->tMed ?? null
            ], 200);

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }
}