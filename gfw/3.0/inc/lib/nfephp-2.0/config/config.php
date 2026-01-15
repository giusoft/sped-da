<?
/**
 * Parametros de configuração do sistema
 *
**/

// tipo de ambiente esta informação deve ser editada pelo sistema
// 1-Produção 2-Homologação
// esta variável será utilizada para direcionar os arquivos e
// estabelecer o contato com o SEFAZ
$ambiente=;

//esta variável contêm o nome do arquivo com todas as url dos webservices do sefaz
//incluindo a versao dos mesmos, pois alguns estados não estão utilizando as
//mesmas versões
$arquivoURLxml="";

//Nome da Empresa
$empresa="";

//Sigla da UF
$UF="";

//Código da UF
$cUF="";

//Número do CNPJ
$cnpj="";

//Nome do certificado que deve ser colocado na pasta certs da API
$certName="";

//Senha da chave privada
$keyPass="";

//Senha de decriptaçao da chave, normalmente não é necessaria
$passPhrase="";

//Diretório onde serão mantidos os arquivos com as NFe em xml
//a partir deste diretório serão montados todos os subdiretórios do sistema
//de manipulação e armazenamento das NFe
$arquivosDir="";

//URL base da API, passa a ser necessária em virtude do uso dos arquivos wsdl
//para acesso ao ambiente nacional
$baseurl="";

//Versão em uso dos shemas
$scheme["1.10"]="";
$scheme["2.00"]="";

//Configuração do DANFE
$danfeFormato=""; //P-Retrato L-Paisagem 
$danfePapel=""; //Tipo de papel utilizado 
$danfeCanhoto=; //se verdadeiro imprime o canhoto na DANFE 
$danfeLogo=""; //passa o caminho para o LOGO da empresa 

//Configuração do email
$mailFROM="";
$maillHOST="";
$mailUSER="";
$mailPASS="";

?>