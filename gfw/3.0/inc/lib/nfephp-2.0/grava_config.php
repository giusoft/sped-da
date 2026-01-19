<?php

$texto = "<?\n";
$texto .= "/**\n * Parametros de configuração do sistema\n *\n**/\n\n";
$texto .= "// tipo de ambiente esta informação deve ser editada pelo sistema\n";
$texto .= "// 1-Produção 2-Homologação\n";
$texto .= "// esta variável será utilizada para direcionar os arquivos e\n";
$texto .= "// estabelecer o contato com o SEFAZ\n";
$texto .= '$ambiente=' . $_POST['ambiente'] .";\n";
$texto .= "\n";
$texto .= "//esta variável contêm o nome do arquivo com todas as url dos webservices do sefaz\n";
$texto .= "//incluindo a versao dos mesmos, pois alguns estados não estão utilizando as\n";
$texto .= "//mesmas versões\n";
$texto .= '$arquivoURLxml="' . $_POST['urlws'] . '"' .";\n";
$texto .= "\n";
$texto .= "//Nome da Empresa\n";
$texto .= '$empresa="' . $_POST['razao'] . '"' .";\n";
$texto .= "\n";
$texto .= "//Sigla da UF\n";
$texto .= '$UF="' . $_POST['siglauf'] . '"' .";\n";
$texto .= "\n";

$cUFlist = array('AC'=>'12',
                 'AL'=>'27',
                 'AM'=>'13',
                 'AP'=>'16',
                 'BA'=>'29',
                 'CE'=>'23',
                 'DF'=>'53',
                 'ES'=>'32',
                 'GO'=>'52',
                 'MA'=>'21',
                 'MG'=>'31',
                 'MS'=>'50',
                 'MT'=>'51',
                 'PA'=>'15',
                 'PB'=>'25',
                 'PE'=>'26',
                 'PI'=>'22',
                 'PR'=>'41',
                 'RJ'=>'33',
                 'RN'=>'24',
                 'RO'=>'11',
                 'RR'=>'14',
                 'RS'=>'43',
                 'SC'=>'42',
                 'SE'=>'28',
                 'SP'=>'35',
                 'TO'=>'17'
                  );

$texto .= "//Código da UF\n";
$texto .= '$cUF="' . $cUFlist[$_POST['siglauf']] . '"' .";\n";
$texto .= "\n";
$texto .= "//Número do CNPJ\n";
$texto .= '$cnpj="' . $_POST['numcnpj'] . '"' .";\n";
$texto .= "\n";
$texto .= "//Nome do certificado que deve ser colocado na pasta certs da API\n";
$texto .= '$certName="' . $_POST['pfx'] . '"' .";\n";
$texto .= "\n";
$texto .= "//Senha da chave privada\n";
$texto .= '$keyPass="' . $_POST['keysenha'] . '"' .";\n";
$texto .= "\n";
$texto .= "//Senha de decriptaçao da chave, normalmente não é necessaria\n";
$texto .= '$passPhrase="' . $_POST['passe'] . '"' .";\n";
$texto .= "\n";
$texto .= "//Diretório onde serão mantidos os arquivos com as NFe em xml\n";
$texto .= "//a partir deste diretório serão montados todos os subdiretórios do sistema\n";
$texto .= "//de manipulação e armazenamento das NFe\n";
$texto .= '$arquivosDir="' . $_POST['dirnfe'] . '"' .";\n";
$texto .= "\n";
$texto .= "//URL base da API, passa a ser necessária em virtude do uso dos arquivos wsdl\n";
$texto .= "//para acesso ao ambiente nacional\n";
$texto .= '$baseurl="' . $_POST['urlapi'] . '"' .";\n";
$texto .= "\n";
$texto .= "//Versão em uso dos shemas\n";
$texto .= '$scheme["1.10"]="' . $_POST['schema1'] . '"' .";\n";
$texto .= '$scheme["2.00"]="' . $_POST['schema2'] . '"' .";\n";
$texto .= "\n";
$texto .= "//Configuração do DANFE\n";
$texto .= '$danfeFormato="' . $_POST['formato'] . '"' ."; //P-Retrato L-Paisagem \n";
$texto .= '$danfePapel="' . $_POST['papel'] . '"' ."; //Tipo de papel utilizado \n";
$texto .= '$danfeCanhoto=' . $_POST['canhoto'] . '' ."; //se verdadeiro imprime o canhoto na DANFE \n";
$texto .= '$danfeLogo="' . $_POST['logo'] . '"' ."; //passa o caminho para o LOGO da empresa \n";
$texto .= "\n";
$texto .= "//Configuração do email\n";
$texto .= '$mailFROM="' . $_POST['emitente'] . '"' .";\n";
$texto .= '$maillHOST="' . $_POST['smtp'] . '"' .";\n";
$texto .= '$mailUSER="' . $_POST['user'] . '"' .";\n";
$texto .= '$mailPASS="' . $_POST['password'] . '"' .";\n";
$texto .= "\n";
$texto .= "?>";

if ( !file_put_contents('config/config.php', $texto) ){
    echo "Erro durante a gravação do arquivo de configuração!!";
} else {
    echo "Sucesso!!";
}

?>
