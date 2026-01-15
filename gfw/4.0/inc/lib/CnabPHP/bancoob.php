<?
include 'vendor/autoload.php';
date_default_timezone_set("America/Bahia");
$codigo_banco = Cnab\Banco::BANCOOB;
$arquivo = new Cnab\Remessa\Cnab400\Arquivo($codigo_banco);

$arquivo->configure(array(
	'data_geracao'  => new DateTime(),
	'data_gravacao' => new DateTime(),
	'nome_fantasia' => 'Gomes e Liz', // CNPJ com 15 posições = 0 a esquerda
	'razao_social'  => 'Gomes e Liz CFC Ltda',  // sua razão social
	'cnpj'          => '09261980000150', // seu cnpj completo
	'logradouro'    => 'Rua Pedro Augusto Bossardi',
	'numero'        => '580',
	'bairro'        => 'Jd Menino Deus',
	'cidade'        => 'Quatro Barras',
	'uf'            => 'PR',
	'cep'           => '83420000',
	'agencia'       => '9999',
	'agencia_dv'       => '1',
	'codigo_cliente'     => '77777',
    'codigo_cliente_dv'     => '2',
	'sequencial_remessa' => '001'
));

// você pode adicionar vários boletos em uma remessa

 $nossoNumero = 1;
 $arquivo->insertDetalhe(array(
     'nosso_numero'      => $nossoNumero++,
     'conta'           => '0725',
     'conta_dv'           => '0725',
     'agencia_cobradora'  => '07250', // Prefixo da Cooperativa Com 5 dígitos
     'numero_contrato_garantia' => '00000',//Para Carteira 3 preencher com o  número do contrato sem DV
     'numero_contrato_garantia_dv' => '0',
     'numero_bordero' => '000000', //Numero do borderô: preencher em caso de carteira 3
     'numero_carteira' => '01', # 01 = Simples Com Registro  # 02 = Simples Sem Registro  # 03 = Garantida Caucionada
     'data_instrucao'    => new DateTime(),
     'data_cadastro'     => new DateTime(),
     'data_vencimento'   => new DateTime('2018-05-28'),
     'valor'             => '12.00',
     'sacado_nome'       => 'Bruno Coelho',
     'sacado_cpf'        => '027.990.235.25',
     'sacado_logradouro' => 'Rua Itagi',
     'sacado_cep'        => '42703-530',
     'sacado_bairro'     => 'Pitangueiras',
     'sacado_cidade'     => 'L. de Freitas',
     'sacado_uf'     => 'BA',
     'valor_desconto'   => 0.00, 
     'valor_multa'      => 0.00,
     'instrucao1'        => '00', // Ausência de instrução
     'instrucao2'        => '00',
     'especie'				=> '99' // 99 - Outros
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
