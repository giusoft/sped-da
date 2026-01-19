<?php

namespace Cnab\Remessa\Cnab400;

class Arquivo implements \Cnab\Remessa\IArquivo
{
    public $header;
    public $trailer;
    public $detalhes = array();
    public $rateios = array();
    public $mensagensSicred = array();
    private $_data_gravacao;
    private $_data_geracao;
    public $banco;
    public $codigo_banco;
    public $configuracao = array();
    public $layout_versao;
    const   QUEBRA_LINHA = "\r\n";

    public function __construct($codigo_banco, $layout_versao = null)
    {
        $this->codigo_banco = $codigo_banco;
        $this->layout_versao = $layout_versao;
        $this->banco = \Cnab\Banco::getBanco($this->codigo_banco);
        //$this->data_gravacao = date('dmY');
    }

    public function configure(array $params)
    {
        $campos = array(
            'data_geracao', 'data_gravacao', 'nome_fantasia', 'razao_social', 'cnpj', 'logradouro', 'numero', 'bairro',
            'cidade', 'uf', 'cep'
        );
        switch ($this->codigo_banco) {
            case \Cnab\Banco::CEF:
                $campos[] = 'operacao';
                $campos[] = 'codigo_cedente';
                $campos[] = 'codigo_cedente_dac';
            case \Cnab\Banco::BRADESCO:
                $campos[] = 'codigo_cedente';
                $campos[] = 'sequencial_remessa';
                break;
            case \Cnab\Banco::BANCO_DO_BRASIL:
                $campos[] = 'agencia_dac';
                $campos[] = 'conta_dac';
                break;
            case \Cnab\Banco::SICREDI:
                $campos[] = 'codigo_cedente';
                $campos[] = 'sequencial_remessa';
                $campos[] = 'conta';
                break;
            case \Cnab\Banco::BANCOOB:
                $campos[] = 'sequencial_remessa';
                $campos[] = 'agencia';
                $campos[] = 'agencia_dv';
                $campos[] = 'codigo_cliente';
                $campos[] = 'codigo_cliente_dv';
                break;
            default:
                $campos[] = 'conta_dac';
                break;
        }
        
        foreach ($campos as $campo) {
            if (array_key_exists($campo, $params)) {
                if (strpos($campo, 'data_') === 0 && !($params[$campo] instanceof \DateTime)) {
                    throw new \Exception("config '$campo' need to be instance of DateTime");
                }
                $this->configuracao[$campo] = $params[$campo];
            } else {
                throw new \Exception('Configuração "'.$campo.'" need to be set');
            }
        }

        foreach ($campos as $key) {
            if (!array_key_exists($key, $params)) {
                throw new Exception('Configuração "'.$key.'" dont exists');
            }
        }

        $this->data_geracao = $this->configuracao['data_geracao'];
        $this->data_gravacao = $this->configuracao['data_gravacao'];

        $this->header = new Header($this);

        $this->header->codigo_banco = $this->banco['codigo_do_banco'];
        $this->header->nome_banco = $this->banco['nome_do_banco'];

        if($this->codigo_banco <> \Cnab\Banco::BRADESCO
            && $this->codigo_banco <> \Cnab\Banco::SICREDI
            && $this->codigo_banco <> \Cnab\Banco::BANCOOB
        )
        {
            $this->header->agencia = $this->configuracao['agencia'];
            $this->header->conta = $this->configuracao['conta'];
            $this->header->nome_empresa = $this->configuracao['nome_fantasia'];
        }

        switch ($this->codigo_banco) {
            case \Cnab\Banco::CEF:
                $this->header->codigo_cedente = $this->configuracao['codigo_cedente'];
            case \Cnab\Banco::BRADESCO:
                $this->header->codigo_cedente = $this->configuracao['codigo_cedente'];
                $this->header->sequencial_remessa = $this->configuracao['sequencial_remessa'];
                $this->header->razao_social = $this->configuracao['razao_social'];
                break;
            case \Cnab\Banco::SICREDI:
                $this->header->codigo_cedente = $this->configuracao['codigo_cedente'];
                $this->header->cnpj = $this->configuracao['cnpj'];
                $this->header->sequencial_remessa = $this->configuracao['sequencial_remessa'];
                break;
            case \Cnab\Banco::BANCO_DO_BRASIL:
                $this->header->agencia_dv = $this->configuracao['agencia_dac'];
                $this->header->conta_dv = $this->configuracao['conta_dac'];
                break;  
            case \Cnab\Banco::BANCOOB:
                $this->header->sequencial_remessa = $this->configuracao['sequencial_remessa'];
                $this->header->agencia = $this->configuracao['agencia'];
                $this->header->agencia_dv = $this->configuracao['agencia_dv'];
                $this->header->codigo_cliente = $this->configuracao['codigo_cliente'];
                $this->header->codigo_cliente_dv = $this->configuracao['codigo_cliente_dv'];
                $this->header->nome_empresa = $this->configuracao['razao_social'];
            break;
            default:
                $this->header->conta_dv = $this->configuracao['conta_dac'];
                break;
        }

        if($this->codigo_banco <> \Cnab\Banco::SICREDI)
            $this->header->data_geracao = $this->configuracao['data_geracao']->format('dmy');
        else
            $this->header->data_geracao = $this->configuracao['data_geracao']->format('Ymd');
    }

    public function insertDetalhe(array $boleto, $tipo = 'remessa')
    {   
        $dateVencimento = $boleto['data_vencimento'] instanceof \DateTime ? $boleto['data_vencimento'] : new \DateTime($boleto['data_vencimento']);
        $dateCadastro = $boleto['data_cadastro']   instanceof \DateTime ? $boleto['data_cadastro']   : new \DateTime($boleto['data_cadastro']);

        $detalhe = new Detalhe($this);
        $complementos = array();

        if ($tipo == 'remessa') {
            if(\Cnab\Banco::SICREDI <> $this->codigo_banco){

                $detalhe->codigo_ocorrencia = !empty($boleto['codigo_de_ocorrencia']) ? $boleto['codigo_de_ocorrencia'] : '1';
                if (\Cnab\Banco::BRADESCO <> $this->codigo_banco) {
                    $detalhe->codigo_inscricao = 2;
                    $detalhe->numero_inscricao = $this->prepareText($this->configuracao['cnpj'], '.-/');
                }
            }
            if(\Cnab\Banco::SICREDI == $this->codigo_banco){
                
                $agencia    = $boleto['agencia'];
                $posto      = $boleto['posto'];
                $cedente    = $this->header->codigo_cedente;
                $ano        = date("y");
                $byte       = (int) $boleto['byte_geracao'] ? (int) $boleto['byte_geracao'] : 2;
                $sequencial = str_pad($boleto['nosso_numero'], 5,0,STR_PAD_LEFT);
                $nossoNumeroCompleto=$ano.$byte.$sequencial.$this->mod11Sicred($agencia,$posto,$cedente,$ano,$byte,$sequencial);

                $detalhe->nosso_numero = $nossoNumeroCompleto;
                $detalhe->seu_numero = substr(str_pad($boleto['nosso_numero'], 10,0,STR_PAD_LEFT),0,10);
                $data_instrucao = $boleto['data_instrucao']   instanceof \DateTime ? $boleto['data_instrucao']   : new \DateTime($boleto['data_instrucao']);
                $detalhe->data_instrucao = $data_instrucao->format('Ymd');
                $detalhe->data_vencimento = $dateVencimento;
                $detalhe->valor_titulo = $boleto['valor'];
                $detalhe->data_emissao = $dateCadastro->format('dmy');
                $detalhe->sacado_numero_inscricao = $this->prepareText($boleto['sacado_cpf'], '.-/');
                $detalhe->nome = $this->prepareText($boleto['sacado_nome']);
                $detalhe->sacado_logradouro = $this->prepareText($boleto['sacado_logradouro']);
                $detalhe->cep = $this->prepareText($boleto['sacado_cep'], '.-/');
                $detalhe->valor_desconto = $boleto['valor_desconto'];
                $detalhe->valor_multa = $boleto['valor_desconto'];
                
                $mensagem = new Mensagem($this);
                $mensagem->nosso_numero = $detalhe->nosso_numero;
                $mensagem->instrucao01 = $this->prepareText($boleto['instrucao1']);
                $mensagem->instrucao02 = $this->prepareText($boleto['instrucao2']);
                $mensagem->instrucao03 = $this->prepareText($boleto['instrucao3']);
                $mensagem->instrucao03 = $this->prepareText($boleto['instrucao4']);
                $this->mensagensSicred[$nossoNumeroCompleto] = $mensagem;
            }
            elseif (\Cnab\Banco::BANCOOB == $this->codigo_banco){
                $detalhe->codigo_inscricao = "02";
                $detalhe->numero_inscricao = $this->prepareText($this->configuracao['cnpj'], '.-/');
                $detalhe->agencia =  $this->header->agencia;
                $detalhe->agencia_dv =  $this->header->agencia_dv;
                $detalhe->conta =  $boleto['conta'];
                $detalhe->conta_dv =  $boleto['conta_dv'];

                $nossoNumero = substr(str_pad($boleto['nosso_numero'], 11,"0",STR_PAD_LEFT), 0,11);
                $nossoNumeroDV = $this->modulo_11($nossoNumero);
                $detalhe->nosso_numero = $nossoNumero.$nossoNumeroDV;

                $detalhe->numero_contrato_garantia = $boleto['numero_contrato_garantia'];
                $detalhe->numero_contrato_garantia_dv = $boleto['numero_contrato_garantia_dv'];

                $detalhe->numero_bordero = $boleto['numero_bordero'];

                $detalhe->numero_carteira = $boleto['numero_carteira'];
                $detalhe->numero_documento = substr(str_pad($boleto['nosso_numero'], 10,"0",STR_PAD_LEFT), 0,11);

                $detalhe->vencimento = $dateVencimento;

                $detalhe->valor_titulo = $boleto['valor'];
                $detalhe->agencia_cobradora = $boleto['agencia_cobradora'];
                $detalhe->especie = $boleto['especie'] == "" ? '99'  : $boleto['especie'];
                $detalhe->data_emissao =  $dateCadastro->format('dmy');
                $detalhe->instrucao1 =  $boleto['instrucao1'] =="" ? '00' : $boleto['instrucao1'];
                $detalhe->instrucao2 =  $boleto['instrucao2'] =="" ? '00' : $boleto['instrucao2'];
                
                //Dados do pagador
                $detalhe->sacado_codigo_inscricao =  "01";
                $detalhe->sacado_numero_inscricao =  $this->prepareText($boleto['sacado_cpf'], '.-/');
                $detalhe->nome = $this->prepareText($boleto['sacado_nome']);
                $detalhe->logradouro = $this->prepareText($boleto['sacado_logradouro']);
                $detalhe->bairro = $this->prepareText($boleto['sacado_bairro'], '.-/');
                $detalhe->cep = $this->prepareText($boleto['sacado_cep'], '.-/');
                $detalhe->cidade = substr($this->prepareText($boleto['sacado_cidade'], '.-/'),0,15);
                $detalhe->estado = substr($this->prepareText($boleto['sacado_uf'], '.-/'),0,2);
                


            }
            elseif (\Cnab\Banco::BRADESCO == $this->codigo_banco) {
                $detalhe->conta = $boleto['conta'];
                $detalhe->conta_dv = $boleto['conta_dv'];
                $detalhe->agencia = $boleto['agencia'];
                $detalhe->nome = $this->prepareText($boleto['sacado_nome']);
                //$digitoNossoNumero=$this->modulo_11($boleto['agencia'].$boleto['nosso_numero']);
                $digitoNossoNumero=$this->mod11Base7($boleto['carteira'], $boleto['nosso_numero']);
                $detalhe->digito_nosso_numero = $digitoNossoNumero;
                $detalhe->rateio = $boleto['rateio'] ? 'R' : ' ';
            } else if (\Cnab\Banco::CEF == $this->codigo_banco) {
                $detalhe->codigo_cedente = $this->header->codigo_cedente;
                $detalhe->taxa_de_permanencia = $boleto['taxa_de_permanencia'];
                $detalhe->mensagem = $boleto['mensagem'];
                $detalhe->data_multa = $boleto['data_multa'];
                $detalhe->valor_multa = $boleto['valor_multa'];
            } else if (\Cnab\Banco::BANCO_DO_BRASIL == $this->codigo_banco) {
                $detalhe->agencia = $this->header->agencia;
                $detalhe->agencia_dv = $this->header->agencia_dv;
                $detalhe->conta = $this->header->conta;
                $detalhe->conta_dv = $this->header->conta_dv;
                $detalhe->numero_convenio = $boleto['numero_convenio'];
                $detalhe->variacao_carteira = $boleto['variacao_carteira'];
                $detalhe->tipo_cobranca = (isset($boleto['tipo_cobranca']) ?: '');

            } else {
                $detalhe->agencia = $this->header->agencia;
                $detalhe->conta = $this->header->conta;
                $detalhe->conta_dv = $this->header->conta_dv;
                $detalhe->codigo_instrucao = '0';
                $detalhe->qtde_moeda = '0'; # Este campo deverá ser preenchido com zeros caso a moeda seja o Real.
                $detalhe->codigo_carteira = 'I';
                $detalhe->uso_banco = '';
                $detalhe->data_mora = $boleto['data_multa'];

                if ($boleto['valor_multa'] > 0) {
                    /*
                    // Não está presente na documentação disponibilizada no site
                    // os valores de multa devem ser configurados com o gerente da sua conta
                    $detalheMulta = new DetalheMulta($this);
                    if(@$boleto['tipo_multa'] == 'porcentagem')
                        $detalheMulta->codigo_multa = 2;
                    else if(!@$boleto['tipo_multa'] || $boleto['tipo_multa'] == 'valor')
                        $detalheMulta->codigo_multa = 1;
                    else
                        throw new Exception('tipo de multa inválido, deve ser "porcentagem" ou "valor"');
                    $detalheMulta->data_multa = $boleto['data_multa'];
                    $detalheMulta->valor_multa = $boleto['valor_multa'];
                    $complementos[] = $detalheMulta;
                    */
                }
            }
            /*
               Deve ser preenchido na remessa somente quando utilizados, na posição 109-110, os códigos de
               ocorrência 35 – Cancelamento de Instrução e 38 – Cedente não concorda com alegação do sacado. Para
               os demais códigos de ocorrência este campo deverá ser preenchido com zeros.
            */
            if(\Cnab\Banco::SICREDI <> $this->codigo_banco
                && \Cnab\Banco::BANCOOB <> $this->codigo_banco
            ){
                $detalhe->uso_empresa = isset($boleto['uso_empresa']) 
                                  ? $boleto['uso_empresa'] 
                                  : $boleto['nosso_numero'];
                $detalhe->nosso_numero = $boleto['nosso_numero'];

                $detalhe->numero_carteira = $boleto['carteira'];
                $detalhe->numero_documento = $boleto['numero_documento'];
                $detalhe->vencimento = $dateVencimento->format('dmy');
                $detalhe->valor_titulo = $boleto['valor'];
                $detalhe->aceite = empty($boleto['aceite']) ? 'N' : $boleto['aceite'];
                
                if (\Cnab\Banco::BRADESCO == $this->codigo_banco) {
                    switch (intval($boleto['instrucao1'])) {
                        case 1:
                            // Protesto
                            $detalhe->instrucao1 = '06';
                            $detalhe->instrucao2 = intval($boleto['instrucao2']) > 5 ? str_pad($boleto['instrucao2'],2,0,STR_PAD_LEFT) : '05';
                            break;
                        case 2:
                            // Decurso de prazo
                            $detalhe->instrucao1 = '18';
                            $detalhe->instrucao2 = intval($boleto['instrucao2']) > 0 ? str_pad($boleto['instrucao2'],2,0,STR_PAD_LEFT) : '00';
                            break;
                        default:
                            $detalhe->instrucao1 = '00';
                            $detalhe->instrucao2 = '00';
                            break;
                    }
                    
                } else {
                    $detalhe->instrucao1 = $boleto['instrucao1'];
                    $detalhe->instrucao2 = $boleto['instrucao2'];
                }
                
                $detalhe->especie = $boleto['especie'];
                $detalhe->data_emissao = $dateCadastro->format('dmy');

                $sacado_tipo = @$boleto['sacado_tipo'] or $sacado_tipo = 'cpf';
                if ($sacado_tipo == 'cnpj') {
                    $detalhe->sacado_codigo_inscricao = '2';
                    /*
                     * @todo Trocar espécie
                     */
                    $detalhe->sacado_numero_inscricao = $this->prepareText($boleto['sacado_cnpj'], '.-/');
                    $detalhe->nome = $this->prepareText($boleto['sacado_razao_social']);
                } else {
                    $detalhe->sacado_codigo_inscricao = '1';
                    /*
                     * @todo Trocar espécie
                     */
                    $detalhe->sacado_numero_inscricao = $this->prepareText($boleto['sacado_cpf'], '.-/');
                    $detalhe->nome = $this->prepareText($boleto['sacado_nome']);
                }
                
                $detalhe->logradouro = $this->prepareText($boleto['sacado_logradouro']);
                if (\Cnab\Banco::BRADESCO <> $this->codigo_banco){
                    $detalhe->cidade = $this->prepareText($boleto['sacado_cidade']);
                    $detalhe->estado = $boleto['sacado_uf'];
                    $detalhe->bairro = $this->prepareText($boleto['sacado_bairro']);
                    $detalhe->prazo = $boleto['prazo'];
                }
                $detalhe->cep = str_replace('-', '', $boleto['sacado_cep']);
                if (\Cnab\Banco::BRADESCO == $this->codigo_banco)
                {
                    $detalhe->sacador = $this->prepareText($boleto["mensagem"]);
                } else
                {
                    $detalhe->sacador = $this->prepareText($this->configuracao['nome_fantasia']);
                }
                $detalhe->juros_um_dia   = $boleto['juros_de_um_dia'];
                $detalhe->desconto_ate   = $boleto['data_desconto'];
                $detalhe->valor_desconto = $boleto['valor_desconto'];
            }
            
        } elseif ($tipo == 'baixa') {
            $detalhe->codigo_inscricao = '0';
            $detalhe->numero_inscricao = '0';
            $detalhe->agencia = $this->header->agencia;
            $detalhe->conta = $this->header->conta;
            $detalhe->conta_dac = $this->header->dac;
            $detalhe->codigo_instrucao = '0';
            /*
               Deve ser preenchido na remessa somente quando utilizados, na posição 109-110, os códigos de
               ocorrência 35 – Cancelamento de Instrução e 38 – Cedente não concorda com alegação do sacado. Para
               os demais códigos de ocorrência este campo deverá ser preenchido com zeros.
            */
            $detalhe->codigo_ocorrencia = $boleto['codigo_de_ocorrencia'];
            $detalhe->uso_empresa = $boleto['nosso_numero'];
            $detalhe->nosso_numero = $boleto['nosso_numero'];
            $detalhe->qtde_moeda = '0'; # Este campo deverá ser preenchido com zeros caso a moeda seja o Real.
            $detalhe->numero_carteira = $boleto['carteira'];
            $detalhe->codigo_carteira = 'I';
            $detalhe->uso_banco = '';
            $detalhe->numero_documento = $boleto['numero_documento'];
            $detalhe->vencimento = '0';
            $detalhe->valor_titulo = $boleto['valor'];
            $detalhe->aceite = ' ';
            $detalhe->data_emissao = '0';
            $detalhe->sacado_codigo_inscricao = '2';
            $detalhe->especie = ' ';
            $detalhe->sacado_numero_inscricao = '0';
            $detalhe->juros_um_dia = $boleto['juros_de_um_dia'];
            $detalhe->data_juros = $boleto['data_juros'];

            $detalhe->nome = ' ';
            $detalhe->logradouro = ' ';
            $detalhe->bairro = ' ';
            $detalhe->cep = '0';
            $detalhe->cidade = ' ';
            $detalhe->estado = ' ';
            $detalhe->sacador = ' ';
        } else {
            throw new Exception('Tipo de $detalhe desconhecido');
        }

        if(\Cnab\Banco::SICREDI <> $this->codigo_banco){
            if (\Cnab\Banco::BRADESCO == $this->codigo_banco) {
            $detalhe->codigo_banco = '000';
            } else {     
                $detalhe->codigo_banco = $this->banco['codigo_do_banco'];
            }    
        }
        
        $this->detalhes[] = $detalhe;

        $tem=false;
        if($boleto['rateio'] || $boleto['rateio']=="R"){
            $rateio = new Rateio($this);
            $rateio->identificacao_empresa_banco =  str_pad($boleto['carteira'], 3,0,STR_PAD_LEFT).
                                                    str_pad($boleto['agencia'],5,0,STR_PAD_LEFT).
                                                    str_pad($boleto['conta'],7,0,STR_PAD_LEFT).
                                                    str_pad($boleto['conta_dv'],1,0,STR_PAD_LEFT);
            $rateio->identificacao_titulo_banco =str_pad((int) $boleto['nosso_numero'],11,0,STR_PAD_LEFT) . $detalhe->digito_nosso_numero;
            $rateio->codigo_calculo_rateio      = (int) $boleto['codigo_calculo_rateio'] > 0 ? (int) $boleto['codigo_calculo_rateio'] : 2;
            $rateio->tipo_valor_informado       = (int) $boleto['tipo_valor_informado']  > 0 ? (int) $boleto['tipo_valor_informado'] : 2;
            $cnt=0;
            $tem=false;
            foreach ($boleto['rateio_info'] as $rateio_info) {
                $cnt++;
                
                $rateio->{'codigo_agencia_beneficiario_'.$cnt} = $rateio_info['agencia'];
                $rateio->{'digito_agencia_beneficiario_'.$cnt} = $rateio_info['agencia_dv'];
                $rateio->{'numero_conta_beneficiario_'.$cnt} = $rateio_info['conta'];
                $rateio->{'digito_conta_beneficiario_'.$cnt} = $rateio_info['conta_dv'];
                $rateio->{'valor_beneficiario_'.$cnt} = $rateio_info['valor'];
                $rateio->{'nome_beneficiario_'.$cnt} = $rateio_info['nome'];
                $rateio->{'parcela_beneficiario_'.$cnt} = $rateio_info['parcela'];
                $rateio->{'floating_beneficiario_'.$cnt} = $rateio_info['quantidade_dias_cred'];
                $rateio->{'codigo_banco_beneficiario_'.$cnt} = $this->codigo_banco;

                if($cnt==3){
                    $this->rateios[$boleto['nosso_numero'] . $detalhe->digito_nosso_numero][] = $rateio;
                    $cnt=0;
                    $tem=false;

                    $rateio = new Rateio($this);
                    $rateio->identificacao_empresa_banco =  str_pad($boleto['carteira'], 3,0,STR_PAD_LEFT).
                                                            str_pad($boleto['agencia'],5,0,STR_PAD_LEFT).
                                                            str_pad($boleto['conta'],7,0,STR_PAD_LEFT).
                                                            str_pad($boleto['conta_dv'],1,0,STR_PAD_LEFT);
                    $rateio->identificacao_titulo_banco = str_pad((int) $boleto['nosso_numero'],11,0,STR_PAD_LEFT) . $detalhe->digito_nosso_numero;
                    
                    $rateio->codigo_calculo_rateio      = (int) $boleto['codigo_calculo_rateio'] > 0 ? (int) $boleto['codigo_calculo_rateio'] : 2;
                    $rateio->tipo_valor_informado       = (int) $boleto['tipo_valor_informado']  > 0 ? (int) $boleto['tipo_valor_informado'] : 2;
                }
                else
                    $tem=true;
            }
            if($tem)
                $this->rateios[$boleto['nosso_numero'] . $detalhe->digito_nosso_numero][] = $rateio;
        }

        foreach ($complementos as $complemento) {
            $this->detalhes[] = $complemento;
        }
    }

    public function listDetalhes()
    {
        return $this->detalhes;
    }

    private function prepareText($text, $remove = null)
    {
        $result = strtoupper($this->removeAccents(trim(html_entity_decode($text))));
        if ($remove) {
            $result = str_replace(str_split($remove), '', $result);
        }

        return $result;
    }

    private function removeAccents($string)
    {
        return preg_replace(
            array(
                    '/\xc3[\x80-\x85]/',
                    '/\xc3\x87/',
                    '/\xc3[\x88-\x8b]/',
                    '/\xc3[\x8c-\x8f]/',
                    '/\xc3([\x92-\x96]|\x98)/',
                    '/\xc3[\x99-\x9c]/',

                    '/\xc3[\xa0-\xa5]/',
                    '/\xc3\xa7/',
                    '/\xc3[\xa8-\xab]/',
                    '/\xc3[\xac-\xaf]/',
                    '/\xc3([\xb2-\xb6]|\xb8)/',
                    '/\xc3[\xb9-\xbc]/',
            ),
            str_split('ACEIOUaceiou', 1),
            $this->isUtf8($string) ? $string : utf8_encode($string)
        );
    }

    private function isUtf8($string)
    {
        return preg_match('%^(?:
                 [\x09\x0A\x0D\x20-\x7E]
                | [\xC2-\xDF][\x80-\xBF]
                | \xE0[\xA0-\xBF][\x80-\xBF]
                | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}
                | \xED[\x80-\x9F][\x80-\xBF]
                | \xF0[\x90-\xBF][\x80-\xBF]{2}
                | [\xF1-\xF3][\x80-\xBF]{3}
                | \xF4[\x80-\x8F][\x80-\xBF]{2}
                )*$%xs',
                $string
        );
    }

    public function __set($name, $value)
    {
        if (strpos($name, 'data_') === 0) {
            if ($value instanceof \DateTime) {
                $property = "_$name";
                $this->$property = $value;
            } else {
                throw new InvalidArgumentException("$nome need to be instance of DateTime");
            }
        } else {
            throw new Exception("property '$name' dont exists");
        }
    }

    public function getText()
    {
        $numero_sequencial = 1;

        $this->header->numero_sequencial = $numero_sequencial++;

        // valida os dados
        if (!$this->header->validate()) {
            throw new \InvalidArgumentException($this->header->last_error);
        }

        $dados = $this->header->getEncoded().self::QUEBRA_LINHA;

        foreach ($this->detalhes as $detalhe) {
            $detalhe->numero_sequencial = $numero_sequencial++;
            if (!$detalhe->validate()) {
                throw new \InvalidArgumentException($detalhe->last_error);
            }
            $dados .= $detalhe->getEncoded().self::QUEBRA_LINHA;

            if(count($this->rateios)){
                foreach ($this->rateios[(String) $detalhe->nosso_numero.$detalhe->digito_nosso_numero] as $key => $rateio) {
                    $rateio->numero_sequencial = $numero_sequencial++;
                    if (!$rateio->validate()) {
                        throw new \InvalidArgumentException($rateio->last_error);
                    }
                    $dados .= $rateio->getEncoded().self::QUEBRA_LINHA;
                }    
            }
            // echo "<textarea>$dados</textarea>";
            // exit;
            if($this->codigo_banco == \Cnab\Banco::SICREDI){ //Mensagem
                $mensagem=$this->mensagensSicred[(String) $detalhe->nosso_numero];
                $mensagem->numero_sequencial = $numero_sequencial++;

                if (!$mensagem->validate()) {
                    throw new \InvalidArgumentException($mensagem->last_error);
                }
                $dados.=$mensagem->getEncoded().self::QUEBRA_LINHA;
            }
        }
        
        $this->trailer = new Trailer($this);
        $this->trailer->numero_sequencial = $numero_sequencial++;
        if($this->codigo_banco == \Cnab\Banco::SICREDI)
            $this->trailer->codigo_beneficiario = $this->configuracao['conta'];

        if (!$this->trailer->validate()) {
            throw new \InvalidArgumentException($this->trailer->last_error);
        }

        $dados .= $this->trailer->getEncoded().self::QUEBRA_LINHA;
        // echo '<textarea style="width:100%;">'.$dados.'</textarea>';
        // exit;
        return $dados;
    }

    public function countDetalhes()
    {
        return count($this->detalhes);
    }

    public function save($filename)
    {
        $text = $this->getText();
        file_put_contents($filename, $text);
    }

    public function modulo_11($num, $base=9, $r=0) {
    $soma = 0;
    $fator = 2; 
    for ($i = strlen($num); $i > 0; $i--) {
        $numeros[$i] = substr($num,$i-1,1);
        $parcial[$i] = $numeros[$i] * $fator;
        $soma += $parcial[$i];
        if ($fator == $base) {
            $fator = 1;
        }
        $fator++;
    }
    if ($r == 0) {
        $soma *= 10;
        $digito = $soma % 11;
        
        if($this->codigo_banco == \Cnab\Banco::SICREDI && ($digito == 10 || $digito == 11))
            $digito = "0";
        elseif ($digito == 10) {
            $digito = "X";
        }
        
        if (strlen($num) == "43") {
            //então estamos checando a linha digitável
            if ($digito == "0" or $digito == "X" or $digito > 9) {
                    $digito = 1;
            }
        }
        return $digito;
    } 
    elseif ($r == 1){
        $resto = $soma % 11;
        return $resto;
    }
}


/**
 * Calculo o modulo 11 conforme determinação do sicred
 * @param  string $agencia
 * @param  string $posto
 * @param  string $cedente
 * @param  string $ano
 * @param  string $byte
 * @param  string $sequencial
 * @return int
 */
public function mod11Sicred($agencia,$posto,$cedente,$ano,$byte,$sequencial){
    $agencia=str_pad(intval($agencia), 4, "0", STR_PAD_LEFT);
    $posto=str_pad(intval($posto), 2, "0", STR_PAD_LEFT);
    $cedente = str_pad(intval($cedente), 5, "0", STR_PAD_LEFT);
    $sequencial = str_pad(intval($sequencial), 5, "0", STR_PAD_LEFT);
    
    $numeroCompleto = $agencia.$posto.$cedente.$ano.$byte.$sequencial;
    return $this->modulo_11($numeroCompleto);
}

/**
 * Calcula o modulo 11 com base 7, conforme determinado pelo Bradesco
 * 
 * @param string $carteira
 * @param string $nosoNumero
 * @return string|number
 */
public function mod11Base7($carteira, $nosoNumero)
{
	$sai = "";
	
	$nosoNumero = str_pad(intval($nosoNumero), 11, "0", STR_PAD_LEFT); 	// forçando a formatação correta
	$carteira = str_pad(intval($carteira), 3, "0", STR_PAD_LEFT); 		// forçando a formatação correta
	
	$arr1 = array();
	$arr1[] = (int) substr($carteira,0,1);
	$arr1[] = (int) substr($carteira,1,1);
	$arr1[] = (int) substr($carteira,2,1);
	for($i=0; $i<12;$i++) {
		$arr1[] = (int) substr($nosoNumero,$i,1);
	}
	$arr2 = array(3,2,7,6,5,4,3,2,7,6,5,4,3,2); // sequencia de quatorze numeros em base 7
	
	$res = 0;
	for ($i=0; $i<=13; $i++) {
		$res += ($arr1[$i] * $arr2[$i]);
		// echo "$res + ({$arr1[$i]} * {$arr2[$i]}) = $res<br/>"; 
	}
	
	$divisor = 11;
	$mod = $res % $divisor;
	
	if ($mod == 0) {
		$sai = 0;
	} elseif($mod == 1) {
		$sai = 'P';
	} else {
		$sai = ($divisor - $mod);
	}
	
	return $sai;
}

}
