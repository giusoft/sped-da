<?
include 'vendor/autoload.php';
date_default_timezone_set("America/Bahia");
$codigo_banco = Cnab\Banco::SICREDI;
$arquivo = new Cnab\Remessa\Cnab400\Arquivo($codigo_banco);



$arquivo->configure(array(
	'data_geracao'  => new DateTime(),
	'data_gravacao' => new DateTime(),
	'nome_fantasia' => '006538059000114  CFC CEPAUTO LTDA - ME', // CNPJ com 15 posições = 0 a esquerda
	'razao_social'  => 'SINDAUTO',  // sua razão social
	'cnpj'          => '01.706.994/0001-29 ', // seu cnpj completo
	'banco'         => '000', //código do banco (deveria ser 237, mas deu erro)
	'logradouro'    => 'Av. Tancredo Neves',
	'numero'        => '969',
	'bairro'        => 'Caminho das Árvores',
	'cidade'        => 'Salvador',
	'uf'            => 'BA',
	'cep'           => '41820-020',
	'agencia'       => '232',
	'conta'         => '20020',
    'conta_dac'     => '4',
    'codigo_cedente'     => '7948100',
	'sequencial_remessa' => '013'
));
$nossoNumero = '25';
//Dados do rateio
$rateio  = array();

//Primeiro beneficiário
$rateio[0]['agencia']="232";
$rateio[0]['agencia_dv']="1";
// $rateio[0]['conta']="19127";
// $rateio[0]['conta_dv']="4";
$rateio[0]['conta']="19127";
$rateio[0]['conta_dv']="2";
$rateio[0]['valor']=5.50;
$rateio[0]['nome'] = "SINDICATO DAS AUTO-ESCOLAS";
$rateio[0]['parcela'] = "";
$rateio[0]['quantidade_dias_cred'] = 0;
$rateio[0]['identificacao_titulo_banco'] = $nossoNumero;

// Segundo beneficiário
$rateio[1]['agencia']="3566";
$rateio[1]['agencia_dv']="1";
$rateio[1]['conta']="0037774";
$rateio[1]['conta_dv']="0";
$rateio[1]['valor']=1.50;
$rateio[1]['nome'] = "GIUSOFT TECNOLOGIA LTDA EPP";
$rateio[1]['parcela'] = "";
$rateio[1]['quantidade_dias_cred'] = 0;
$rateio[1]['identificacao_titulo_banco'] = $nossoNumero;

// você pode adicionar vários boletos em uma remessa
$arquivo->insertDetalhe(array(
    'codigo_de_ocorrencia' => 1, // 1 = Entrada de título, futuramente poderemos ter uma constante
    'nosso_numero'      => $nossoNumero,
    'numero_documento'  => $nossoNumero,
    'agencia'           => '232',
    // 'conta'             => '19127',
    // 'conta_dv'          => '2', 
    'conta'             => '20020',
    'conta_dv'          => '4',
    'carteira'          => '009',
    'especie'           => Cnab\Especie::BRADESCO_OUTROS, // Você pode consultar as especies Cnab\Especie
    'valor'             => 10.00, // Valor do boleto
    'instrucao1'        => '00', // 1 = Protestar com (Prazo) dias, 2 = Devolver após (Prazo) dias, futuramente poderemos ter uma constante
    'instrucao2'        => '00', // preenchido com zeros
    'sacado_nome'       => 'Bruno Coelho Ferreira', // O Sacado é o cliente, preste atenção nos campos abaixo
    'sacado_tipo'       => 'cpf', //campo fixo, escreva 'cpf' (sim as letras cpf) se for pessoa fisica, cnpj se for pessoa juridica
    'sacado_cpf'        => '027.990.235-25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530', // sem hífem
    'sacado_uf'         => 'BA',
    'data_vencimento'   => new DateTime('2018-04-28'),
    'data_cadastro'     => new DateTime(),
    'juros_de_um_dia'     => 0.0, // Valor do juros de 1 dia'
    'data_desconto'       => "000000",
    'valor_desconto'      => 0.0, // Valor do desconto
    'prazo'               => 0, // prazo de dias para o cliente pagar após o vencimento
    'taxa_de_permanencia' => '0', //00 = Acata Comissão por Dia (recomendável), 51 Acata Condições de Cadastramento na CAIXA
    'mensagem'            => 'Mensalidade de teste',
    'data_multa'          => "000000", // data da multa
    'valor_multa'         => 0.0, // valor da multa
    'rateio'              => true,
    'rateio_info'         => $rateio,
    'codigo_calculo_rateio'=> 2,
    'tipo_valor_informado' => 2
));


$nossoNumero = '26';
//Dados do rateio
$rateio  = array();

//Primeiro beneficiário
$rateio[0]['agencia']="232";
$rateio[0]['agencia_dv']="1";
$rateio[0]['conta']="19127";
$rateio[0]['conta_dv']="2";
$rateio[0]['valor']=9.50;
$rateio[0]['nome'] = "SINDICATO DAS AUTO-ESCOLAS";
$rateio[0]['parcela'] = "";
$rateio[0]['quantidade_dias_cred'] = 0;
$rateio[0]['identificacao_titulo_banco'] = $nossoNumero;

// você pode adicionar vários boletos em uma remessa
$arquivo->insertDetalhe(array(
    'codigo_de_ocorrencia' => 1, // 1 = Entrada de título, futuramente poderemos ter uma constante
    'nosso_numero'      => $nossoNumero,
    'numero_documento'  => $nossoNumero,
    'agencia'           => '232',
    // 'conta'             => '19127',
    // 'conta_dv'          => '2', 
    'conta'             => '20020',
    'conta_dv'          => '4',
    'carteira'          => '009',
    'especie'           => Cnab\Especie::BRADESCO_OUTROS, // Você pode consultar as especies Cnab\Especie
    'valor'             => 10.00, // Valor do boleto
    'instrucao1'        => '00', // 1 = Protestar com (Prazo) dias, 2 = Devolver após (Prazo) dias, futuramente poderemos ter uma constante
    'instrucao2'        => '00', // preenchido com zeros
    'sacado_nome'       => 'Bruno Coelho Ferreira', // O Sacado é o cliente, preste atenção nos campos abaixo
    'sacado_tipo'       => 'cpf', //campo fixo, escreva 'cpf' (sim as letras cpf) se for pessoa fisica, cnpj se for pessoa juridica
    'sacado_cpf'        => '027.990.235-25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530', // sem hífem
    'sacado_uf'         => 'BA',
    'data_vencimento'   => new DateTime('2018-04-25'),
    'data_cadastro'     => new DateTime(),
    'juros_de_um_dia'     => 0.0, // Valor do juros de 1 dia'
    'data_desconto'       => "000000",
    'valor_desconto'      => 0.0, // Valor do desconto
    'prazo'               => 0, // prazo de dias para o cliente pagar após o vencimento
    'taxa_de_permanencia' => '0', //00 = Acata Comissão por Dia (recomendável), 51 Acata Condições de Cadastramento na CAIXA
    'mensagem'            => 'Mensalidade de teste',
    'data_multa'          => "000000", // data da multa
    'valor_multa'         => 0.0, // valor da multa
    'rateio'              => true,
    'rateio_info'         => $rateio,
    'codigo_calculo_rateio'=> 2,
    'tipo_valor_informado' => 2
));

// para salvar
$nome_remessa = "CB" . date('dm') . "01.REM";
$arquivo->save("/tmp/$nome_remessa");

// $arquivoNome = "/tmp/$nome_remessa";
// header('Content-Description: File Transfer');
// header('Content-Disposition: attachment; filename="'.$nome_remessa.'"');
// header('Content-Type: application/octet-stream');
// header('Content-Transfer-Encoding: binary');
// header('Content-Length: ' . filesize($arquivoNome));
// header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
// header('Pragma: public');
// header('Expires: 0');
// readfile($arquivoNome);
