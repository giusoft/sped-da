<?php

namespace App\Model;

use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;

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

            $cStat = null;
            if ($std->cStat) {
                $cStat = $std->cStat;
            }

            $xMotivo = 'Resposta desconhecida';
            if ($std->xMotivo) {
                $xMotivo = $std->xMotivo;
            }

            $ambiente = 'Homologação';
            if ($std->tpAmb == 1 || $this->corpoRequisicao['tpAmb'] == 1) {
                $ambiente = 'Produção';
            }

            $codigoUf = $this->corpoRequisicao['siglaUF'];
            if ($std->cUF) {
                $codigoUf = $std->cUF;
            }

            $tempoMedio = null;
            if (isset($std->tMed)) {
                $tempoMedio = $std->tMed;
            }

            emitirSucesso([
                'operacional' => ($cStat == 107),
                'status_code' => $cStat,
                'motivo' => $xMotivo,
                'ambiente' => $ambiente,
                'uf' => $codigoUf,
                'data_hora_consulta' => $std->dhRecbto,
                'tempo_medio_ms' => $tempoMedio
            ], 200);

        } catch (\Exception $e) {
            emitirErro($e->getMessage(), 500);
        }
    }
}