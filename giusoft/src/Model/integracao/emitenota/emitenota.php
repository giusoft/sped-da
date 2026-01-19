<?php

class Emitenota
{

    public function __construct($param)
    {
        $this->rotas = $this->montarRotas($param['rotas']);
    }


    public function autenticar($login, $password)
    {
       return true;
    }


    public function montarUrlCompleta($nomeRota)
    {
        return rtrim($this->urlBase, '/') . '/' . ltrim($this->rotas[$nomeRota]['url'], '/');
    }


    public function acessarRota($nomeRota, $body = array(), $dadosRequisicao)
    {
        $urlCompleta = $this->montarUrlCompleta($nomeRota);
        $dadosRequisicao['rotas'] = $this->rotas[$nomeRota];
        $body = json_encode($body);

        return $this->pontoAcesso->executarRequest($this->rotas[$nomeRota]['verbo'], "{$urlCompleta}", $body, $this->montarHeader($nomeRota), $dadosRequisicao);
    }


    public function montarHeader($function)
    {
        $header = array(
            'Content-Type' => 'application/json'
        );

        return $header;
    }


    public function montarRotas($rotasConsultadas)
    {
        $rotas = array();

        foreach ($rotasConsultadas as $rotasConsultada) {
            $rotas[$rotasConsultada['metodo']] = [
                'id'    => $rotasConsultada['id'],
                'url'   => $rotasConsultada['url_rota'],
                'verbo' => strtoupper($rotasConsultada['http_verbo']),
                'enviarTempoReal' => $rotasConsultada['enviar_tempo_real']
            ];
        }

        return $rotas;
    }


    public function enviarNfe($dados)
    {
        $dadosEnviarNfe = $this->montarDadosNfe($dados['dadosNfe']);

        return $this->acessarRota('enviarNfe', $dadosEnviarNfe);
    }


    public function estornarNfe($dados)
    {
        $dadosEstornarNfe = $this->montarDadosNfe($dados['dadosNfe']);
        $dadosEstornarNfe['refNfe'] = extrairNumeros($dados['dadosNfe']['refNfe']);

        return $this->acessarRota('estornarNfe', $dadosEstornarNfe);
    }


    public function cancelarNfe($dados)
    {
        $dadosCancelarNfe = array(
            'cnpj_emitente' => extrairNumeros($dados['empresa']['cnpjArmazem']),
            'chave' => extrairNumeros($dados['chave']),
            'protocolo' => extrairNumeros($dados['protocolo']),
            'justificativa' => removerAcentos($dados['justificativa']),
            'empresa' => $this->montarDadosEmpresa($dados['empresa'], $dados['config'])
        );

        return $this->acessarRota('cancelarNfe', $dadosCancelarNfe);
    }


    public function inutilizarNfe($dados)
    {
        $dadosInutilizarNfe = array(
            'cnpj_emitente' => extrairNumeros($dados['empresa']['cnpjArmazem']),
            'serie' => (int) $dados['serie'],
            'numero_inicial' => (int) $dados['numero_inicial'],
            'numero_final' => (int) $dados['numero_final'],
            'justificativa' => removerAcentos($dados['justificativa']),
            'empresa' => $this->montarDadosEmpresa($dados['empresa'], $dados['config'])
        );

        return $this->acessarRota('inutilizarNfe', $dadosInutilizarNfe);
    }


    public function cartaCorrecao($dados)
    {
        $dadosCartaCorrecao = array(
            'cnpj_emitente' => extrairNumeros($dados['empresa']['cnpjArmazem']),
            'chave' => extrairNumeros($dados['chave']),
            'correcao' => removerAcentos($dados['correcao']),
            'sequencial' => (int) $dados['sequencial'],
            'empresa' => $this->montarDadosEmpresa($dados['empresa'], $dados['config'])
        );

        return $this->acessarRota('cartaCorrecao', $dadosCartaCorrecao);

    }


    public function consultarNfe($dados)
    {
        $dadosConsultarNfe = array(
            'cnpj_emitente' => extrairNumeros($dados['cnpj_emitente']),
            'chave' => extrairNumeros($dados['chave'])
        );

        return $this->acessarRota('consultarNfe', $dadosConsultarNfe);
    }


    public function consultarStatusSefaz($dados)
    {
        $dadosConsultarStatusSefaz = array(
            'cnpj_emitente' => extrairNumeros($dados['cnpj_emitente'])
        );

        return $this->acessarRota('consultarStatusSefaz', $dadosConsultarStatusSefaz);
    }


    public function gerarDanfe($dados)
    {
        $dadosGerarDanfe = array(
            'cnpj_emitente' => extrairNumeros($dados['empresa']['cnpjArmazem']),
            'chave' => extrairNumeros($dados['chave']),
            'xml' => base64_encode($dados['xml']),
            'empresa' => $this->montarDadosEmpresa($dados['empresa'], $dados['config'])
        );

        return $this->acessarRota('gerarDanfe', $dadosGerarDanfe);
    }


    public function gerarDanfeCancelamento($dados)
    {
        $dadosGerarDanfe = array(
            'cnpj_emitente' => extrairNumeros($dados['empresa']['cnpjArmazem']),
            'chave' => extrairNumeros($dados['chave']),
            'xml' => base64_encode($dados['xml']),
            'xml_cancelamento' => base64_encode($dados['xml_cancelamento']),
            'empresa' => $this->montarDadosEmpresa($dados['empresa'], $dados['config'])
        );

        return $this->acessarRota('gerarDanfeCancelamento', $dadosGerarDanfe);
    }


    public function gerarDanfeCce($dados)
    {
        $dadosGerarDanfeCce = array(
            'cnpj_emitente' => extrairNumeros($dados['empresa']['cnpjArmazem']),
            'chave' => extrairNumeros($dados['chave']),
            'xml' => base64_encode($dados['xml']),
            'empresa' => $this->montarDadosEmpresa($dados['empresa'], $dados['config'])
        );

        return $this->acessarRota('gerarDanfeCce', $dadosGerarDanfeCce);
    }


    public function gerarDanfeEmLote($dados)
    {
        $documentosFormatados = [];

        // Se for apenas finalização, não precisa processar documentos
        if (isset($dados['documentos']) && !empty($dados['documentos'])) {
            foreach ($dados['documentos'] as $doc) {
                $xmlBase64 = base64_encode($doc['xml']);
                $documentosFormatados[] = array(
                    'chave' => extrairNumeros($doc['chave']),
                    'xml' => $xmlBase64,
                    'tipo' => $doc['tipo'] ?: 'nfe'
                );
            }
        }

        $dadosEmpresaApi = null;
        if (isset($dados['empresa']) && isset($dados['config'])) {
            $dadosEmpresaApi = $this->montarDadosEmpresa($dados['empresa'], $dados['config']);
        }

        $dadosRequest = array(
            'cnpj_emitente' => extrairNumeros($dados['empresa']['cnpjArmazem']),
            'empresa' => $dadosEmpresaApi,
            'documentos' => $documentosFormatados,
            'id_lote' => $dados['id_lote'],
            'acao' => $dados['acao'],
            'com_pdf' => $dados['com_pdf'] ?: 0,
            'com_xml' => $dados['com_xml'] ?: 0
        );

        return $this->acessarRota('gerarDanfeEmLote', $dadosRequest);
    }


    public function montarDadosEmpresa($dadosEmpresa, $dadosConfigEmpresa)
    {
        return array(
            "razaosocial" => $dadosEmpresa['razaoSocial'],
            "siglaUF" => $dadosEmpresa['siglaUf'],
            "cnpj" => $dadosEmpresa['cnpjArmazem'],
            "cmun" => $dadosEmpresa['codigoIbgeMunicipio'],
            "cPais" => $dadosEmpresa['cPais'],
            "xPais" => $dadosEmpresa['xPais'],
            "cUF" => $dadosEmpresa['codigoIbgeEstado'],
            "cnae" => $dadosEmpresa['cnaeArmazem'],
            "xmun" => $dadosEmpresa['municipioDescricao'],
            "ie" => $dadosEmpresa['inscricaoEstadualArmazem'],
            "im" => $dadosEmpresa['inscricaoMunicipalArmazem'],
            "logradouro" => $dadosEmpresa['enderecoArmazem'],
            "numero" => $dadosEmpresa['numeroArmazem'],
            "complemento" => $dadosEmpresa['enderecoComplementoArmazem'],
            "bairro" => $dadosEmpresa['enderecoBairroArmazem'],
            "cep" => $dadosEmpresa['cepArmazem'],
            "fone" => $dadosEmpresa['telefoneArmazem'],
            "schemes" => $dadosConfigEmpresa['schemes'],
            "tpAmb" => (int) $dadosConfigEmpresa['tpAmb'],
            "regime" => $dadosConfigEmpresa['regime'],
            "versao" => $dadosConfigEmpresa['versao_xml'],
            "senhaCertificado" => $dadosConfigEmpresa['senha_certificado'],
            "usarContingenciaIbsCbs" => $dadosConfigEmpresa['usa_contingencia_ibs_cbs,'],
            "desativarImpostosAntigos" => $dadosConfigEmpresa['	desativar_impostos_antigos'],
        );
    }


    public function montarDadosNfe($dados)
    {
        // Monta array de produtos
        $produtos = array();

        foreach ($dados['item'] as $item) {

            $impostos = array(
                'icms' => array(
                    'orig' => (int) $item['origem'],
                    'CST' => (int) $item['icms_cst'],
                    'modBC' => (int) $item['icms_modalidadebc'],
                    'aliquota' => (float) $item['aliquotaICMS'],
                    'pRedBC' => (float) $item['pRedBCST'],
                    'vBC' => (float) $item['vBC'],
                    'vICMSST' => (float) $item['vICMSST'],
                    'vICMSSTRet' => (float) $item['vICMSSTRet'],
                    'pICMSST' => (float) $item['pICMSST'],
                    'modBCST' => (int) $item['modBCST'],
                    'pMVAST' => (float) $item['pMVAST'],
                    'pRedBCST' => (float) $item['pRedBCST'],
                    'vBCST' => (float) $item['vBCST'],
                    'vBCFCP' => (float) $item['vBCFCP'],
                    'pFCP' => (float) $item['pFCP'],
                    'vBCFCPST' => (float) $item['vBCFCPST'],
                    'pFCPST' => (float) $item['pFCPST']
                )
            );

            if (!empty($item['ipi_cst'])) {
                $impostos['ipi'] = array(
                    'CST' => (int) $item['ipi_cst'],
                    'aliquota' => (float) $item['pIPI'],
                    'cEnq' =>  removerEstranhos($item['ipi_cEnq'])
                );
            }

            if (!empty($item['pis_cst'])) {
                $impostos['pis'] = array(
                    'CST' => (int) $item['pis_cst'],
                    'aliquota' => (float) $item['pPIS']
                );
            }

            if (!empty($item['cofins_cst'])) {
                $impostos['cofins'] = array(
                    'CST' => (int) $item['cofins_cst'],
                    'aliquota' => (float) $item['pCOFINS']
                );
            }

            if (isset($item['CSTIS']) && !empty($item['CSTIS'])) {
                $impostos['is'] = array(
                    'CSTIS' => extrairNumeros($item['CSTIS']),
                    'cClassTribIS' => extrairNumeros($item['cClassTribIS']),
                    'vBCIS' => (float) $item['vBCIS'],
                    'pIS' => (float) $item['pIS'],
                    'vIS' => (float) $item['vIS'],
                    'uTrib' => removerEstranhos($item['uTrib']),
                    'qTrib' => (float) $item['qTrib']
                );
            }

            if (isset($item['cst_ibs_cbs']) && !empty($item['cst_ibs_cbs'])) {

                $cst = extrairNumeros($item['cst_ibs_cbs']);

                $ibscbs = [
                    'CST' => $cst,
                    'cClassTrib' => extrairNumeros($item['cclasstrib_ibs_cbs']),
                    'indDoacao' => (int) $item['indDoacao']
                ];

                $cstsCalculoPadrao = ['000', '010', '011', '200', '220', '221', '222', '510', '515', '550', '830'];

                if (in_array($cst, $cstsCalculoPadrao)) {

                    $ibscbs['vBC'] = (float) $item['vbc_ibs_cbs'];

                    $ibscbs['gIBSUF_pIBSUF'] = (float) $item['gIBSUF_pIBSUF'];
                    $ibscbs['gIBSMun_pIBSMun'] = (float) $item['gIBSMun_pIBSMun'];
                    $ibscbs['gCBS_pCBS'] = (float) $item['gCBS_pCBS'];

                    $ibscbs['gIBSUF_vIBSUF'] = (float) $item['gIBSUF_vIBSUF'];
                    $ibscbs['gIBSUF_vDevTrib'] = (float) $item['gIBSUF_vDevTrib'];
                    $ibscbs['gIBSMun_vIBSMun'] = (float) $item['gIBSMun_vIBSMun'];
                    $ibscbs['gIBSMun_vDevTrib'] = (float) $item['gIBSMun_vDevTrib'];
                    $ibscbs['gCBS_vCBS'] = (float) $item['gCBS_vCBS'];
                    $ibscbs['gCBS_vDevTrib'] = (float) $item['gCBS_vDevTrib'];

                    if (in_array($cst, ['011', '200', '515'])) {
                        $ibscbs['gIBSUF_pRedAliq'] = (float) $item['gIBSUF_pRedAliq'];
                        $ibscbs['gIBSUF_pAliqEfet'] = (float) $item['gIBSUF_pAliqEfet'];

                        $ibscbs['gIBSMun_pRedAliq'] = (float) $item['gIBSMun_pRedAliq'];
                        $ibscbs['gIBSMun_pAliqEfet'] = (float) $item['gIBSMun_pAliqEfet'];

                        $ibscbs['gCBS_pRedAliq'] = (float) $item['gCBS_pRedAliq'];
                        $ibscbs['gCBS_pAliqEfet'] = (float) $item['gCBS_pAliqEfet'];
                    }

                    if ($cst === '515') {
                        $ibscbs['gIBSUF_pDif'] = (float) $item['gIBSUF_pDif'];
                        $ibscbs['gIBSUF_vDif'] = (float) $item['gIBSUF_vDif'];

                        $ibscbs['gIBSMun_pDif'] = (float) $item['gIBSMun_pDif'];
                        $ibscbs['gIBSMun_vDif'] = (float) $item['gIBSMun_vDif'];

                        $ibscbs['gCBS_pDif'] = (float) $item['gCBS_pDif'];
                        $ibscbs['gCBS_vDif'] = (float) $item['gCBS_vDif'];
                    }

                    if ($cst === '222') {
                        $ibscbs['pRedutorBC'] = (float) $item['pRedutorBC'];
                    }

                } elseif ($cst === '620') {
                    $ibscbs['qBCMono'] = (float) $item['qBCMono'];
                    $ibscbs['adRemIBS'] = (float) $item['adRemIBS'];
                    $ibscbs['vIBSMono'] = (float) $item['vIBSMono'];
                    $ibscbs['adRemCBS'] = (float) $item['adRemCBS'];
                    $ibscbs['vCBSMono'] = (float) $item['vCBSMono'];

                } elseif ($cst === '800') {
                    $ibscbs['vIBS'] = (float) $item['vIBSTransf'];
                    $ibscbs['vCBS'] = (float) $item['vCBSTransf'];

                } elseif ($cst === '810') {
                    $ibscbs['competApur'] = $item['competApur'] ?: date('Y-m');
                    $ibscbs['tpCredPresIBSZFM'] = $item['tpCredPresIBSZFM'];
                    $ibscbs['vCredPresIBSZFM'] = (float) $item['vCredPresIBSZFM'];

                } elseif ($cst === '811') {
                    $ibscbs['competApur'] = $item['competApur'] ?: date('Y-m');
                    $ibscbs['vIBSAjuste'] = (float) $item['vIBSAjuste'];
                    $ibscbs['vCBSAjuste'] = (float) $item['vCBSAjuste'];

                }

                $impostos['ibscbs'] = $ibscbs;
            }


            $produto = array(
                'codigo' => removerEstranhos($item['codigo']),
                'nfEntrada' => removerEstranhos($item['NumeroEntrada']),
                'serieEntrada' => removerEstranhos($item['SerieEntrada']),
                'numeroCliente' => removerEstranhos($item['NumeroCliente']),
                'descricao' => removerEstranhos($item['descricao']),
                'ncm' => extrairNumeros($item['ncm']),
                'cfop' => extrairNumeros($item['cfop']),
                'unidade' => removerEstranhos($item['unidade']),
                'quantidade' => (float) $item['quantidade'],
                'valorUnitario' => (float) $item['valor'],
                'impostos' => $impostos
            );

            $produtos[] = $produto;
        }

        // Monta JSON final
        $dadosNfe = array(
            'cnpj_emitente' => extrairNumeros($dados['empresa']['cnpjArmazem']),
            'naturezaOperacao' => removerAcentos($dados['operacao']),
            'sistema' => $dados['sistema'],
            'informacoesAdicionais' => removerAcentos($dados['infAdFisco']),
            'informacoesContribuinte' => removerAcentos($dados['informacoesContribuinte']),
            'dataHoraContingencia' => $dados['NfeNumeroEOperacao']['dataHoraContingencia'],
            'modoOperacao' => (int) $dados['NfeNumeroEOperacao']['modoOperacao'],
            'numeroNota' => (int) $dados['NfeNumeroEOperacao']['numeroNota'],
            'serie' => (int) $dados['NfeNumeroEOperacao']['serie'],
            'finNFe' => (int) $dados['finalidadeOperacao'], // Esse valor será 3 quando for estorno - 1 = normal, 3 = ajuste
            'idDest' => $dados['idDest'],
            'tipoOperacao' => $dados['tipoOperacao'],
            'tPag' => (int) $dados['tPag'],
            'vPag' => (float) $dados['vPag'],
            'refNFe' => $dados['refNfe'],
            'cliente' => array(
                'nome' => removerEstranhos($dados['cliente']['razaoSocial']),
                'endereco' => removerEstranhos($dados['cliente']['endereco']),
                'numero' => removerEstranhos($dados['cliente']['enderecoNumero']),
                'cnpj' => extrairNumeros($dados['cliente']['cnpj']),
                'cpf' => extrairNumeros($dados['cliente']['cpf']),
                'bairro' => removerEstranhos($dados['cliente']['enderecoBairro']),
                'codigoMunicipio' => extrairNumeros($dados['cliente']['enderecoIbgeMunicipio']),
                'municipio' => removerEstranhos($dados['cliente']['enderecoMunicipio']),
                'ie' => $dados['cliente']['inscricaoEstadual'],
                'uf' => strtoupper(trim($dados['cliente']['enderecoUf'])),
                'indIEDest' => (int) $dados['indIEDest'],
                'cPais' => (int) (isset($dados['cliente']['enderecoCodigoPais']) ? $dados['cliente']['enderecoCodigoPais'] : 1058),
                'cep' => extrairNumeros($dados['cliente']['enderecoCep'])
            ),
            'empresa' => $this->montarDadosEmpresa($dados['empresa'], $dados['config']),
            'produtos' => $produtos,
            'transporte' => array(
                'modalidadeFrete' => extrairNumeros($dados['modalidadeFrete']) ,
                'transportadora' => array(
                    'cnpj' => extrairNumeros($dados['transportadora']['transportadoraCnpj']),
                    'cpf' => extrairNumeros($dados['transportadora']['transportadoraCpf']),
                    'razaoSocial' => removerEstranhos($dados['transportadora']['transportadoraRazaoSocial']),
                    'inscricaoEstadual' => extrairNumeros($dados['transportadora']['transportadoraInscricaoEstadual']),
                    'endereco' => removerEstranhos($dados['transportadora']['transportadoraEndereco']),
                    'municipio' => removerEstranhos($dados['transportadora']['transportadoraEnderecoMunicipio']),
                    'uf' => strtoupper(trim($dados['transportadora']['transportadoraEnderecoUf'])),
                    'cep' => extrairNumeros($dados['transportadora']['transportadoraEnderecoCep'])
                ),
                'veiculo' => array(
                    'placa' => removerEstranhos($dados['veiculoPlaca']),
                    'uf' => strtoupper(trim($dados['veiculoPlacaUf'])),
                    'rntc' => removerEstranhos($dados['codigoAntt'])
                ),
                'volumes' => array(
                    'quantidade' => (int) $dados['quantidadeVolume'],
                    'especie' => $dados['especieTransportada'],
                    'marca' => $dados['especieMarca'],
                    'numeracao' => '', // verificar a numeração
                    'pesoLiquido' => $dados['totalPesoLiquido'],
                    'pesoBruto' => $dados['totalPesoBruto']
                )
            )
        );

        return $dadosNfe;
    }


    public function importarPfx($dados)
    {
        $dadosCertificado = array(
            'cnpj_emitente' => extrairNumeros($dados['cnpj_armazem']),
            'certificado' => $dados['certificado']
        );

        return $this->acessarRota('importarPfx', $dadosCertificado);
    }


    public function buscarDadosCertificado($dados)
    {
        $dadosCertificado = array(
            'cnpj_emitente' => extrairNumeros($dados['cnpj_armazem']),
            'senhaCertificado' => $dados['senhaCertificado'],
            'certificado' => $dados['certificado'] ?: ''
        );

        return $this->acessarRota('buscarDadosCertificado', $dadosCertificado);
    }
}
