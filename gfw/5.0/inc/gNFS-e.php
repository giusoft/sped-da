<?php
include "/var/www/html/gfw/4.0/inc/lib/nfephp-3.10/libs/NFe/ConvertNFePHP.class.php";
include "/var/www/html/gfw/4.0/inc/lib/nfephp-3.10/libs/NFe/ToolsNFePHP.class.php";


class gNFSe extends ConvertNFePHP
{

    public $config          = [];
    public $certsDir        = '';
    public $certNome        = '';
    public $priKEY          = '';
    public $pubKEY          = '';
    public $certKEY         = '';
    public $erros           = [];
    public $x509certdata    = '';

    function __construct($config)
    {
        $this->config=$config;
    }


    public function carregaCertificados()
    {
        try {
            if (!function_exists('openssl_pkcs12_read')) {
                $msg = "Função não existente: openssl_pkcs12_read!!";
                throw new Exception($msg);
            }
            //$certDir = dirname($this->config['path_certificado']);
            $certDir = $this->config['path_certificado'];
            if (!is_dir($certDir)) {
                $msg = "Dados do certificado inválido";
                throw new Exception($msg);
            }
            $this->certsDir = $certDir;
            $this->certNome = $this->config['cnpj'].'.pfx';
            //monta o path completo com o nome da chave privada
            $this->priKEY = $this->certsDir . '/' . $this->soNumeros($this->config['cnpj']) . '_priKEY.pem';
            //monta o path completo com o nome da chave prublica
            $this->pubKEY =  $this->certsDir . '/' . $this->soNumeros($this->config['cnpj']) . '_pubKEY.pem';
            //monta o path completo com o nome do certificado (chave publica e privada) em formato pem
            $this->certKEY = $this->certsDir . '/' . $this->soNumeros($this->config['cnpj']) . '_certKEY.pem';

            //monta o caminho completo até o certificado pfx
            $pfxCert = $this->certsDir . '/' . $this->certNome;
            //verifica se o arquivo existe
            if (!file_exists($pfxCert)) {
                $msg = "Certificado não encontrado!! $pfxCert";
                throw new Exception($msg);
            }

            //carrega o certificado em um string
            $pfxContent = file_get_contents($pfxCert);
            //carrega os certificados e chaves para um array denominado $x509certdata
            if (!openssl_pkcs12_read($pfxContent, $x509certdata, $this->config['senha_certificado'])) {
                $msg = "O certificado não pode ser lido!! Provavelmente corrompido ou com formato inválido!!";
                throw new Exception($msg);
            }

            $this->x509certdata = $x509certdata;
            //aqui verifica se existem as chaves em formato PEM
            //se existirem pega a data da validade dos arquivos PEM
            //e compara com a data de validade do PFX
            //caso a data de validade do PFX for maior que a data do PEM
            //deleta dos arquivos PEM, recria e prossegue
            $flagNovo = false;
            if (file_exists($this->pubKEY)) {
                $cert = file_get_contents($this->pubKEY);
                if (!$data = openssl_x509_read($cert)) {
                    //arquivo não pode ser lido como um certificado
                    //então deletar
                    $flagNovo = true;
                } else {
                    //pegar a data de validade do mesmo
                    $cert_data = openssl_x509_parse($data);
                    // reformata a data de validade;
                    $ano = substr((string) $cert_data['validTo'], 0, 2);
                    $mes = substr((string) $cert_data['validTo'], 2, 2);
                    $dia = substr((string) $cert_data['validTo'], 4, 2);
                    //obtem o timeestamp da data de validade do certificado
                    $dValPubKey = gmmktime(0, 0, 0, $mes, $dia, $ano);
                    //compara esse timestamp com o do pfx que foi carregado
                    if ($dValPubKey < $this->pfxTimestamp) {
                        //o arquivo PEM é de um certificado anterior
                        //então apagar os arquivos PEM
                        $flagNovo = true;
                    } //fim teste timestamp

                } //fim read pubkey
            } else {
                //arquivo não localizado
                $flagNovo = true;
            } //fim if file pubkey

            //verificar a chave privada em PEM
            if (!file_exists($this->priKEY)) {
                //arquivo não encontrado
                $flagNovo = true;
            }

            //verificar o certificado em PEM
            if (!file_exists($this->certKEY)) {
                //arquivo não encontrado
                $flagNovo = true;
            }

            //criar novos arquivos PEM
            if ($flagNovo) {
                if (file_exists($this->pubKEY)) {
                    unlink($this->pubKEY);
                }

                if (file_exists($this->priKEY)) {
                    unlink($this->priKEY);
                }

                if (file_exists($this->certKEY)) {
                    unlink($this->certKEY);
                }

                //recriar os arquivos pem com o arquivo pfx
                if (!file_put_contents($this->priKEY, $x509certdata['pkey'])) {
                    $msg = "Impossivel gravar no diretório!!! Permissão negada!!";
                    throw new Exception($msg);
                }

                //acrescenta a cadeia completa dos certificados se estiverem
                //inclusas no arquivo pfx, caso contrario deverão ser inclusas
                //manualmente para acessar os serviços em GO
                $aCer = $x509certdata['extracerts'];
                $chain = '';
                $chain .= "$cert";
            }

            file_put_contents($this->pubKEY, $x509certdata['cert']);
            foreach ($aCer as $cert) {
                file_put_contents($this->certKEY, $x509certdata['cert'] . $chain);
            }
        } catch (Exception $e) {
            $this->erros[] = ($e->getMessage());
            return false;
        }
        return true;
    }


    public function validaCertificado()
    {
        if (!$this->x509certdata) {
            //$x509certdata = '';
            $pfxContent = file_get_contents($this->config['path_certificado'].'/'.$this->config['cnpj'].'.pfx');
            $senha = file_get_contents($this->config['path_certificado'].'/passwd');
            $r = openssl_pkcs12_read($pfxContent, $x509certdata, $senha);
            $cert = $x509certdata['cert'];
        } else {
            $cert = $this->x509certdata['cert'];
        }

        try {
            if ($cert == '') {
                $msg = "O certificado é um parâmetro obrigatorio.";
                //throw new nfephpException($msg);
            }

            if (!$data = openssl_x509_read($cert)) {
                $msg = "O certificado não pode ser lido pelo SSL - $cert .";
                //throw new nfephpException($msg);
            }

            $flagOK = true;
            $errorMsg = "";
            $cert_data = openssl_x509_parse($data);

            // reformata a data de validade;
            $ano = substr((string) $cert_data['validTo'], 0, 2);
            $mes = substr((string) $cert_data['validTo'], 2, 2);
            $dia = substr((string) $cert_data['validTo'], 4, 2);
            //obtem o timestamp da data de validade do certificado
            $dValid = gmmktime(0, 0, 0, $mes, $dia, $ano);

            // obtem o timestamp da data de hoje
            $dHoje = gmmktime(0, 0, 0, date("m"), date("d"), date("Y"));

            // compara a data de validade com a data atual
            if ($dValid < $dHoje) {
                $flagOK = false;
                $errorMsg = "A Validade do certificado expirou em [" .$dia.'/'.$mes.'/'.$ano."]";
            } else {
                $flagOK = $flagOK && true;
            }

            //diferença em segundos entre os timestamp
            $diferenca = $dValid - $dHoje;
            // convertendo para dias
            $diferenca = round($diferenca /(60*60*24), 0);
            //carregando a propriedade
            $daysToExpire = $diferenca;
            // convertendo para meses e carregando a propriedade
            $numM = ($ano * 12 + $mes);
            $numN = (date("y") * 12 + date("m"));
            //numero de meses até o certificado expirar
            $monthsToExpire = ($numM-$numN);
            return [
                'status' => $flagOK,
                'error'=> $errorMsg,
                'meses'=> $monthsToExpire,
                'dias' => $daysToExpire,
                'cert_data' => $cert_data
            ];
        } catch (nfephpException $e) {
            $this->erros[] = ($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
    }

    function soNumeros($var)
    {
        return (preg_replace('/[^0-9]+/i ', '', (string) $var));
    }


}

?>