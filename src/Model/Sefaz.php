<?php

namespace App\Model;

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;

class Sefaz
{

    private $corpoRequisicao;
    private $config;
    private $tools;
    private $default;

    public function __construct($dados)
    {
        $this->corpoRequisicao = $dados->corpoRequisicao;
        $this->config = $dados->config;
        $this->tools = $dados->tools;
        // $this->carregarDadosDefault();
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
            if ($std->tpAmb == 1 || $this->config['tpAmb'] == 1) {
                $ambiente = 'Produção';
            }

            $codigoUf = $this->config['siglaUF'];
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