<?
include 'vendor/autoload.php';
date_default_timezone_set("America/Bahia");
$codigo_banco = Cnab\Banco::SICREDI;
$arquivo = new Cnab\Remessa\Cnab400\Arquivo($codigo_banco);

$arquivo->configure(array(
	'data_geracao'  => new DateTime(),
	'data_gravacao' => new DateTime(),
	'nome_fantasia' => 'ESTACAO A', // CNPJ com 15 posições = 0 a esquerda
	'razao_social'  => 'Centro de Formação de Condutores Estação a Araçaí',  // sua razão social
	'cnpj'          => '29302674000122', // seu cnpj completo
	'banco'         => '000', //código do banco (deveria ser 237, mas deu erro)
	'logradouro'    => 'Rua Jeronimo Busato Filho',
	'numero'        => '133',
	'bairro'        => 'Estância Pinhais',
	'cidade'        => 'Pinhais',
	'uf'            => 'PR',
	'cep'           => '83323-090',
	//'agencia'       => '0725',
	'conta'         => '59653',
    //'conta_dac'     => '1',
    'codigo_cedente'     => '59653',
	'sequencial_remessa' => '001'
));

// você pode adicionar vários boletos em uma remessa

$nossoNumero = 1;
$arquivo->insertDetalhe(array(
    'nosso_numero'      => $nossoNumero++,
    'agencia'           => '0725',
    'posto'             => '18',
    'byte_geracao'      => '2',
    'data_instrucao'    => new DateTime(),
    'data_cadastro'     => new DateTime(),
    'data_vencimento'   => new DateTime('2018-05-28'),
    'valor'             => '12.00',
    'sacado_nome'       => 'Bruno Coelho',
    'sacado_cpf'        => '027.990.235.25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530',
    'valor_desconto'   => 0.00, 
    'valor_multa'      => 0.00,
    'instrucao1'        => 'Instrução boleto1',
    'instrucao2'        => 'Instrução 2',
    'instrucao3'        => 'Instrução 3',
    'instrucao4'        => 'Instrução 4'
));

$arquivo->insertDetalhe(array(
    'nosso_numero'      => $nossoNumero++,
    'agencia'           => '0725',
    'posto'             => '18',
    'byte_geracao'      => '2',
    'data_instrucao'    => new DateTime(),
    'data_cadastro'     => new DateTime(),
    'data_vencimento'   => new DateTime('2018-05-28'),
    'valor'             => '120.00',
    'sacado_nome'       => 'Bruno Coelho',
    'sacado_cpf'        => '027.990.235.25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530',
    'valor_desconto'   => 0.00, 
    'valor_multa'      => 0.00,
    'instrucao1'        => 'Instrução boleto1',
    'instrucao2'        => 'Instrução 2',
    'instrucao3'        => 'Instrução 3',
    'instrucao4'        => 'Instrução 4'
));

$arquivo->insertDetalhe(array(
    'nosso_numero'      => $nossoNumero++,
    'agencia'           => '0725',
    'posto'             => '18',
    'byte_geracao'      => '2',
    'data_instrucao'    => new DateTime(),
    'data_cadastro'     => new DateTime(),
    'data_vencimento'   => new DateTime('2018-05-28'),
    'valor'             => '15.00',
    'sacado_nome'       => 'Bruno Coelho',
    'sacado_cpf'        => '027.990.235.25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530',
    'valor_desconto'   => 0.00, 
    'valor_multa'      => 0.00,
    'instrucao1'        => 'Instrução boleto1',
    'instrucao2'        => 'Instrução 2',
    'instrucao3'        => 'Instrução 3',
    'instrucao4'        => 'Instrução 4'
));

$arquivo->insertDetalhe(array(
    'nosso_numero'      => $nossoNumero++,
    'agencia'           => '0725',
    'posto'             => '18',
    'byte_geracao'      => '2',
    'data_instrucao'    => new DateTime(),
    'data_cadastro'     => new DateTime(),
    'data_vencimento'   => new DateTime('2018-05-28'),
    'valor'             => '95.00',
    'sacado_nome'       => 'Bruno Coelho',
    'sacado_cpf'        => '027.990.235.25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530',
    'valor_desconto'   => 0.00, 
    'valor_multa'      => 0.00,
    'instrucao1'        => 'Instrução boleto1',
    'instrucao2'        => 'Instrução 2',
    'instrucao3'        => 'Instrução 3',
    'instrucao4'        => 'Instrução 4'
));

$arquivo->insertDetalhe(array(
    'nosso_numero'      => $nossoNumero++,
    'agencia'           => '0725',
    'posto'             => '18',
    'byte_geracao'      => '2',
    'data_instrucao'    => new DateTime(),
    'data_cadastro'     => new DateTime(),
    'data_vencimento'   => new DateTime('2018-05-28'),
    'valor'             => '125.50',
    'sacado_nome'       => 'Bruno Coelho',
    'sacado_cpf'        => '027.990.235.25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530',
    'valor_desconto'   => 0.00, 
    'valor_multa'      => 0.00,
    'instrucao1'        => 'Instrução boleto1',
    'instrucao2'        => 'Instrução 2',
    'instrucao3'        => 'Instrução 3',
    'instrucao4'        => 'Instrução 4'
));

$arquivo->insertDetalhe(array(
    'nosso_numero'      => $nossoNumero++,
    'agencia'           => '0725',
    'posto'             => '18',
    'byte_geracao'      => '2',
    'data_instrucao'    => new DateTime(),
    'data_cadastro'     => new DateTime(),
    'data_vencimento'   => new DateTime('2018-05-28'),
    'valor'             => '5.98',
    'sacado_nome'       => 'Bruno Coelho',
    'sacado_cpf'        => '027.990.235.25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530',
    'valor_desconto'   => 0.00, 
    'valor_multa'      => 0.00,
    'instrucao1'        => 'Instrução boleto1',
    'instrucao2'        => 'Instrução 2',
    'instrucao3'        => 'Instrução 3',
    'instrucao4'        => 'Instrução 4'
));

$arquivo->insertDetalhe(array(
    'nosso_numero'      => $nossoNumero++,
    'agencia'           => '0725',
    'posto'             => '18',
    'byte_geracao'      => '2',
    'data_instrucao'    => new DateTime(),
    'data_cadastro'     => new DateTime(),
    'data_vencimento'   => new DateTime('2018-08-28'),
    'valor'             => '92.01',
    'sacado_nome'       => 'Bruno Coelho',
    'sacado_cpf'        => '027.990.235.25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530',
    'valor_desconto'   => 0.00, 
    'valor_multa'      => 0.00,
    'instrucao1'        => 'Instrução boleto1',
    'instrucao2'        => 'Instrução 2',
    'instrucao3'        => 'Instrução 3',
    'instrucao4'        => 'Instrução 4'
));

$arquivo->insertDetalhe(array(
    'nosso_numero'      => $nossoNumero++,
    'agencia'           => '0725',
    'posto'             => '18',
    'byte_geracao'      => '2',
    'data_instrucao'    => new DateTime(),
    'data_cadastro'     => new DateTime(),
    'data_vencimento'   => new DateTime('2018-07-28'),
    'valor'             => '5.00',
    'sacado_nome'       => 'Bruno Coelho',
    'sacado_cpf'        => '027.990.235.25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530',
    'valor_desconto'    => 0.00, 
    'valor_multa'       => 0.00,
    'instrucao1'        => 'Instrução boleto1',
    'instrucao2'        => 'Instrução 2',
    'instrucao3'        => 'Instrução 3',
    'instrucao4'        => 'Instrução 4'
));

$arquivo->insertDetalhe(array(
    'nosso_numero'      => $nossoNumero++,
    'agencia'           => '0725',
    'posto'             => '18',
    'byte_geracao'      => '2',
    'data_instrucao'    => new DateTime(),
    'data_cadastro'     => new DateTime(),
    'data_vencimento'   => new DateTime('2018-06-28'),
    'valor'             => '9.00',
    'sacado_nome'       => 'Bruno Coelho',
    'sacado_cpf'        => '027.990.235.25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530',
    'valor_desconto'   => 0.00, 
    'valor_multa'      => 0.00,
    'instrucao1'        => 'Instrução boleto1',
    'instrucao2'        => 'Instrução 2',
    'instrucao3'        => 'Instrução 3',
    'instrucao4'        => 'Instrução 4'
));

$arquivo->insertDetalhe(array(
    'nosso_numero'      => $nossoNumero++,
    'agencia'           => '0725',
    'posto'             => '18',
    'byte_geracao'      => '2',
    'data_instrucao'    => new DateTime(),
    'data_cadastro'     => new DateTime(),
    'data_vencimento'   => new DateTime('2018-06-28'),
    'valor'             => '8.00',
    'sacado_nome'       => 'Bruno Coelho',
    'sacado_cpf'        => '027.990.235.25',
    'sacado_logradouro' => 'Rua Itagi',
    'sacado_cep'        => '42703-530',
    'valor_desconto'   => 0.00, 
    'valor_multa'      => 0.00,
    'instrucao1'        => 'Instrução boleto1',
    'instrucao2'        => 'Instrução 2',
    'instrucao3'        => 'Instrução 3',
    'instrucao4'        => 'Instrução 4'
));

$codigo_beneficiario='59653';
$mes = date("n");
switch ($mes) {
    case 10:
        $mes='O';
    break;
    case 11:
        $mes='N';
    break;
    case 12:
        $mes='D';
    break;
}

$dia=date("d");
$nome_remessa = $codigo_beneficiario . $mes . $dia.'.CRM';
$arquivo->save("/tmp/$nome_remessa");

$arquivoNome = "/tmp/$nome_remessa";
header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="'.$nome_remessa.'"');
header('Content-Type: application/octet-stream');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($arquivoNome));
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Expires: 0');
readfile($arquivoNome);
