<?php

namespace App\Model;

use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;

date_default_timezone_set('America/Bahia');

class Sefaz
{
    private $tools;
    private $corpoRequisicao;
    private $db;
    private $requisicaoSalvar;

    public function __construct($dados)
    {
        $this->tools = $dados->tools;
        $this->corpoRequisicao = $dados->corpoRequisicao['empresa'];
        $this->db = $dados->db;
        $this->requisicaoSalvar = $dados->requisicaoSalvar;
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

            $this->requisicaoSalvar['idPessoasCriou'] = 1;
            $this->requisicaoSalvar['idGatilhos']     = 23;
            $this->requisicaoSalvar['sucesso']        = 1;
            $this->requisicaoSalvar['pendente']       = 0;
            $this->requisicaoSalvar['recebido']       = json_encode($dados);

            $this->db->salvarRequisicao($this->corpoRequisicao, $this->requisicaoSalvar);

            emitirSucesso($dados, 200);

        } catch (\Exception $e) {
            $this->requisicaoSalvar['idPessoasCriou']      = 1;
            $this->requisicaoSalvar['idGatilhos']          = 23;
            $this->requisicaoSalvar['sucesso']             = 0;
            $this->requisicaoSalvar['pendente']            = 1;
            $this->requisicaoSalvar['recebido']            = json_encode(['erro' => $e->getMessage()]);

            $this->db->salvarRequisicao($this->corpoRequisicao, $this->requisicaoSalvar);

            emitirErro($e->getMessage(), 500);
        }
    }
}