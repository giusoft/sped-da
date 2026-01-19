<?php

namespace inc;

use \PDO;
use \DOMDocument;
use \Exception;

class gNfse
{
    private string $URLdsig = 'http://www.w3.org/2000/09/xmldsig#';

    /**
     * URLCanonMeth
     * Instância do WebService
     * @var string
     */

    private string $URLCanonMeth = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';

    /**
     * URLSigMeth
     * Instância do WebService
     * @var string
     */
    private string $URLSigMeth = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';

    /**
     * URLTransfMeth_1
     * Instância do WebService
     * @var string
     */
    private string $URLTransfMeth_1 = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    /**
     * URLTransfMeth_2
     * Instância do WebService
     * @var string
     */
    private string $URLTransfMeth_2 = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';

    /**
     * URLDigestMeth
     * Instância do WebService
     * @var string
     */
    private string $URLDigestMeth = 'http://www.w3.org/2000/09/xmldsig#sha1';

    /**
     * cnpj
     * Cnpj da Giusoft
     * @var string
     */
    private $cnpj = '01.108.339/0001-79';

    /**
     * inscricaoMunicipal
     * Inscrição Municipal da Giusoft
     * @var string
     */
    //private $inscricaoMunicipal = '0010004335';

    public  $xml;
    public  $dadosCliente     = [];
    public  $erros            = [];
    public  $nota             = [];
    private string $priKEY    = '';
    private string $pubKEY    = '';
    private string $certKEY   = '';
    private string $idCliente = '';
    private $conn;
    private $certsDir;
    private $certName;
    private array $dadosEmpresa = [
        'cnpj'               => '01108339000179',
        'inscricaoMunicipal' => '0010004335011',
        'razao_social'       => 'GIUSOFT TECNOLOGIA LTDA - EPP',
        'path_certificado'   => '/var/www/html/gadmin/certificados/giusoft.pfx',
        'senha_certificado'  => 'giusoft'
    ];


    public function __construct($dadosEmpresa, $idCliente, $producao, string $ip='bd-webcfc.giusoft.com.br')
    {
        try {
            $this->conn = new PDO('mysql:host='.$ip.';dbname=gadmin', 'web', 'web');
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION, PDO::ATTR_PERSISTENT, true );
            $this->carregaCertificados();
            $this->idCliente    = $idCliente;
            $this->dadosCliente = $this->buscarDadosCliente($this->idCliente);
        } catch (Exception $e) {
            $this->erros[] = $e->getMessage();
        }

        if ($producao) {
            $this->urlservico = 'http://lftributos.metropolisweb.com.br:9090/webservicenfse/nfse/services?wsdl';
        } else {
            $this->urlservico = 'http://lftributos.metropolisweb.com.br/webservicenfsehomologa/nfse/services?wsdl';
        }
    }


    public function setDadosNfse($nota): void
    {
        $this->nota = $nota;
    }


    public function geraXmlNfse(): void
    {

        $idRps  = 'rps1';
        $idLote = 'lote1';
        $valorTributoIBPT = 'Valor Aproximado dos Tributos IBPT (17,25% - R$ '.number_format($this->nota['valorTributoIBPT'], 2, ',', ' ').")";
        //cria o objeto DOM para o xml
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;

        $Rps = $dom->createElement('Rps');

        $infRps = $dom->createElement('InfRps');
        $infRps->setAttribute('id', 'rps:' . $idRps);

        // Identificação
        $IdentificacaoRps = $dom->createElement('IdentificacaoRps');
        $Numero           = $dom->createElement('Numero', $this->nota['numero']);
        $Serie            = $dom->createElement('Serie', $this->nota['serie']);
        $Tipo             = $dom->createElement('Tipo', $this->nota['tipo']);
        $IdentificacaoRps->appendChild($Numero);
        $IdentificacaoRps->appendChild($Serie);
        $IdentificacaoRps->appendChild($Tipo);

        $infRps->appendChild($IdentificacaoRps);
        $infRps->appendChild($dom->createElement('DataEmissao', date('Y-m-d') . 'T' . date('H:i:s')));
        $infRps->appendChild($dom->createElement('NaturezaOperacao', $this->nota['naturezaOperacao']));
        $infRps->appendChild($dom->createElement('RegimeEspecialTributacao', $this->nota['RegimeEspecialTributacao']));
        $infRps->appendChild($dom->createElement('OptanteSimplesNacional', $this->nota['optanteSimplesNacional']));
        $infRps->appendChild($dom->createElement('IncentivadorCultural', $this->nota['IncentivadorCultural']));
        $infRps->appendChild($dom->createElement('Status', $this->nota['status']));


        foreach ($this->nota['itens'] as $item) {
            $Servico         = $dom->createElement('Servico');
            // Valores
            $Valores         = $dom->createElement('Valores');
            $ValorServicos   = $dom->createElement('ValorServicos', number_format($item['valor'], 2, '.', ''));
            $ValorDeducoes   = $dom->createElement('ValorDeducoes', number_format($item['valorDeducoes'], 2, '.', ''));
            $ValorPis        = $dom->createElement('ValorPis', number_format($item['valorPis'], 2, '.', ''));
            $ValorCofins     = $dom->createElement('ValorCofins', number_format($item['valorCofins'], 2, '.', ''));
            $ValorIr         = $dom->createElement('ValorIr', number_format($item['valorIr'], 2, '.', ''));
            $ValorCsll       = $dom->createElement('ValorCsll', number_format($item['valorCsll'], 2, '.', ''));
            $IssRetido       = $dom->createElement('IssRetido', $item['issRetido']);
            $ValorIss        = $dom->createElement('ValorIss', number_format($item['valorIss'], 2, '.', ''));
            $ValorIssRetido  = $dom->createElement('ValorIssRetido', number_format($item['valorIssRetido'], 2, '.', ''));
            $OutrasRetencoes = $dom->createElement('OutrasRetencoes', number_format($item['outrasRetencoes'], 2, '.', ''));
            $BaseCalculo     = $dom->createElement('BaseCalculo', number_format($item['baseCalculo'], 2, '.', ''));
            $Aliquota        = $dom->createElement('Aliquota', number_format($item['aliquota'], 2, '.', ''));
            $DescontoIncondicionado = $dom->createElement('DescontoIncondicionado', number_format($item['descontoIncondicionado'], 2, '.', ''));
            $DescontoCondicionado   = $dom->createElement('DescontoCondicionado', number_format($item['descontoCondicionado'], 2, '.', ''));

            $Valores->appendChild($ValorServicos);
            $Valores->appendChild($ValorDeducoes);
            $Valores->appendChild($ValorPis);
            $Valores->appendChild($ValorCofins);
            $Valores->appendChild($ValorIr);
            $Valores->appendChild($ValorCsll);
            $Valores->appendChild($IssRetido);
            $Valores->appendChild($ValorIss);
            $Valores->appendChild($ValorIssRetido);
            $Valores->appendChild($OutrasRetencoes);
            $Valores->appendChild($BaseCalculo);
            $Valores->appendChild($Aliquota);
            $Valores->appendChild($DescontoIncondicionado);
            $Valores->appendChild($DescontoCondicionado);

            // Detalhes do serviço
            $ItemListaServico = $dom->createElement('ItemListaServico', trim((string) $item['itemListaServico']));
            $CodigoCnae       = $dom->createElement('CodigoCnae', $this->tiraEstranhos($this->nota['codigoCnae']));
            $CodigoTributacaoMunicipio = $dom->createElement('CodigoTributacaoMunicipio', trim((string) $item['codigoTributacaoMunicipio']));
            $Discriminacao   = $dom->createElement('Discriminacao', str_replace('@','&#xd;', $this->tiraAcentos($item['discriminacao'])).'&#xd;'.str_replace('@','&#xd;', $this->nota['observacao']).'&#xd;'.$valorTributoIBPT.'&#xd;&#xd;'.str_replace('@','&#xd;', $this->nota['artigo']));
            $CodigoMunicipio = $dom->createElement('CodigoMunicipio', '2919207');

            $Servico->appendChild($Valores);
            $Servico->appendChild($ItemListaServico);
            $Servico->appendChild($CodigoCnae);
            $Servico->appendChild($CodigoTributacaoMunicipio);
            $Servico->appendChild($Discriminacao);
            $Servico->appendChild($CodigoMunicipio);
            $infRps->appendChild($Servico);
        }

        // Prestador
        $Prestador          = $dom->createElement('Prestador');
        $Cnpj               = $dom->createElement('Cnpj', $this->dadosEmpresa['cnpj']);
        $InscricaoMunicipal = $dom->createElement('InscricaoMunicipal', $this->soNumerosIsento($this->tiraPontos($this->dadosEmpresa['inscricaoMunicipal'])));

        $Prestador->appendChild($Cnpj);
        $Prestador->appendChild($InscricaoMunicipal);

        // TSomador
        $Tomador              = $dom->createElement('Tomador');
        $IdentificacaoTomador = $dom->createElement('IdentificacaoTomador');
        $CpfCnpj              = $dom->createElement('CpfCnpj');

        $TomadorCpf  = $dom->createElement('Cpf', $this->soNumeros($this->dadosCliente['cpf']));
        $TomadorCnpj = $dom->createElement('Cnpj', $this->soNumeros($this->dadosCliente['cnpj']));

        if ($this->dadosCliente['cpf'] != '') {
            $CpfCnpj->appendChild($TomadorCpf);
        } else {
            $CpfCnpj->appendChild($TomadorCnpj);
        }

        $InscricaoMunicipalCliente = $dom->createElement('InscricaoMunicipal', $this->soNumeros($this->dadosCliente['inscricaoMunicipal']));
        $IdentificacaoTomador->appendChild($CpfCnpj);
        $IdentificacaoTomador->appendChild($InscricaoMunicipalCliente);

        $RazaoSocial     = $dom->createElement('RazaoSocial', $this->tiraAcentos($this->dadosCliente['razaoSocial']));
        $EEndereco       = $dom->createElement('Endereco');
        $Endereco        = $dom->createElement('Endereco', $this->tiraAcentos(trim((string) $this->dadosCliente['endereco'])));
        $Numero          = $dom->createElement('Numero', $this->tiraAcentos(trim((string) $this->dadosCliente['enderecoNumero'])));
        $Bairro          = $dom->createElement('Bairro', $this->tiraAcentos(trim((string) $this->dadosCliente['enderecoBairro'])));
        $CodigoMunicipio = $dom->createElement('CodigoMunicipio', $this->soNumeros($this->dadosCliente['enderecoIbgeMunicipio']));
        $Uf              = $dom->createElement('Uf', trim((string) $this->dadosCliente['enderecoUf']));
        $Cep             = $dom->createElement('Cep', $this->soNumeros($this->dadosCliente['enderecoCep']));

        $EEndereco->appendChild($Endereco);
        $EEndereco->appendChild($Numero);
        $EEndereco->appendChild($Bairro);
        $EEndereco->appendChild($CodigoMunicipio);
        $EEndereco->appendChild($Uf);
        $EEndereco->appendChild($Cep);

        $Tomador->appendChild($IdentificacaoTomador);
        $Tomador->appendChild($RazaoSocial);
        $Tomador->appendChild($EEndereco);
        $Contato = $dom->createElement('Contato');

        if ($this->dadosCliente['email'] != '') {
            $Email   = $dom->createElement('Email', $this->tiraAcentos($this->dadosCliente['email']));
            $Contato->appendChild($Email);
        }

        if ($this->dadosCliente['telefone'] != '') {
            $Telefone = $dom->createElement('Telefone', $this->tiraAcentos($this->soNumeros($this->dadosCliente['telefone'])));
            $Contato->appendChild($Telefone);
        }
        $Tomador->appendChild($Contato);

        $infRps->appendChild($Prestador);
        $infRps->appendChild($Tomador);

        // Serviços
        $Rps->appendChild($infRps);

        $ListaRps           = $dom->createElement('ListaRps');
        $ListaRps->appendChild($Rps);

        $LoteRps            = $dom->createElement('LoteRps');
        $LoteRps->setAttribute('id', $idLote);
        $LoteRps->setAttribute('versao', '1.00');

        $NumeroLote         = $dom->createElement('NumeroLote', $this->nota['numeroLote']);
        $QuantidadeRps      = $dom->createElement('QuantidadeRps', 1);
        $Cnpj               = $dom->createElement('Cnpj', $this->dadosEmpresa['cnpj']);
        $InscricaoMunicipal = $dom->createElement('InscricaoMunicipal', $this->soNumerosIsento($this->tiraPontos($this->dadosEmpresa['inscricaoMunicipal'])));

        $EnviarLoteRpsEnvio = $dom->createElement('EnviarLoteRpsEnvio');
        $EnviarLoteRpsEnvio->setAttribute('xmlns', 'http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd');
        $EnviarLoteRpsEnvio->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $EnviarLoteRpsEnvio->setAttribute('xsi:schemaLocation', 'http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd file:///C:/temp/nfse.xsd');

        $LoteRps->appendChild($NumeroLote);
        $LoteRps->appendChild($Cnpj);
        $LoteRps->appendChild($InscricaoMunicipal);
        $LoteRps->appendChild($QuantidadeRps);
        $LoteRps->appendChild($ListaRps);

        $EnviarLoteRpsEnvio->appendChild($LoteRps);

        $dom->appendChild($EnviarLoteRpsEnvio);

        $xml = $dom->saveXML();
        $this->xml = $this->tratarXmlParaEnvio($xml);
        $this->assina('InfRps');
    }


    public function enviarXML($xmlSoap)
    {
        $resultado = '';
        $url       = $this->urlservico;
        $header    = [
            'Content-Type:text/xml',
            'Accept:*/*',
            'Content-Length: '.strlen((string) $xmlSoap),
            'SOAPAction: '
        ];

        $soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL,            $url);
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($soap_do, CURLOPT_TIMEOUT,        10);
        curl_setopt($soap_do, CURLOPT_PORT,           9090);
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($soap_do, CURLOPT_SSLVERSION,     4);
        curl_setopt($soap_do, CURLOPT_SSLCERT,        $this->certKEY);
        curl_setopt($soap_do, CURLOPT_SSLKEY,         $this->priKEY);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($soap_do, CURLOPT_POST,           true);
        curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $xmlSoap);
        curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $header);
        curl_setopt($soap_do, CURLOPT_HEADER,         false);
        curl_setopt($soap_do, CURLOPT_TIMEOUT,        30);
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 30);
        $resultado = curl_exec($soap_do);

        if (!empty(curl_error($soap_do))) {
            $xmlMensagemErro =
                '<ListaMensagemRetorno>'
                   .'<MensagemRetorno>'
                        .'<Codigo>'  .curl_errno($soap_do).'</Codigo>'
                        .'<Mensagem>'.curl_error($soap_do).'</Mensagem>'
                    .'</MensagemRetorno>'
                .'</ListaMensagemRetorno>';
            $resultado = $xmlMensagemErro;
        }

        $resultado = $this->verificarErroRequisicao($resultado);
        curl_close ($soap_do);

        return $resultado;
    }


    public function assina($tagid): ?bool
    {
        $docxml = $this->xml;
        if ($tagid == '') {
            $this->errMsg = 'Uma tag deve ser indicada para que seja assinada!!';
            $this->errStatus = TRUE;
            return FALSE;
        }

        if ($docxml == '') {
            $this->errMsg = 'Um xml deve ser passado para que seja assinado!!';
            $this->errStatus = TRUE;
            return FALSE;
        }

        // obter o chave privada para a ssinatura
        $fp = fopen($this->priKEY, 'r');
        $priv_key = fread($fp, 8192);
        fclose($fp);
        $pkeyid = openssl_get_privatekey($priv_key);
        // limpeza do xml com a retirada dos CR, LF e TAB
        $order = ["\r\n", "\n", "\r", "\t"];
        $replace = '';

        $docxml = str_replace($order, $replace, $docxml);
        // carrega o documento no DOM
        $xmldoc = new DOMDocument();
        $xmldoc->preservWhiteSpace = FALSE; // elimina espaços em branco
        $xmldoc->formatOutput = FALSE;
        // muito importante deixar ativadas as opçoes para limpar os espacos em branco e as tags vazias
        $xmldoc->loadXML($docxml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        // $root = $xmldoc->documentElement;

        //extrair a tag com os dados a serem assinados
        $node = $xmldoc->getElementsByTagName($tagid)->item(0);

        if ($tagid == "InfRps") {
            $nodeSignature = $xmldoc->getElementsByTagName('Rps')->item(0);
        } elseif ($tagid == 'CancelarNfseEnvio') {
            $nodeSignature = $xmldoc->getElementsByTagName('CancelarNfseEnvio')->item(0);
        } else {
            $nodeSignature = $xmldoc->getElementsByTagName('EnviarLoteRpsEnvio')->item(0);
        }

        $id = trim($node->getAttribute("id"));
        preg_replace('/[^0-9]/', '', $id);
        //extrai os dados da tag para uma string
        $dados = $node->C14N(FALSE, FALSE, NULL, NULL);

        $digValue = base64_encode(hash('sha1', $dados, TRUE));

        //monta a tag da assinatura digital
        $Signature = $xmldoc->createElementNS($this->URLdsig, 'Signature');
        $nodeSignature->appendChild($Signature);

        $SignedInfo = $xmldoc->createElement('SignedInfo');
        $Signature->appendChild($SignedInfo);
        //Cannocalization
        $newNode = $xmldoc->createElement('CanonicalizationMethod');
        $SignedInfo->appendChild($newNode);
        $newNode->setAttribute('Algorithm', $this->URLCanonMeth);
        //SignatureMethod
        $newNode = $xmldoc->createElement('SignatureMethod');
        $SignedInfo->appendChild($newNode);
        $newNode->setAttribute('Algorithm', $this->URLSigMeth);
        //Reference
        $Reference = $xmldoc->createElement('Reference');
        $SignedInfo->appendChild($Reference);
        $Reference->setAttribute('URI', '#' . $id);
        //Transforms
        $Transforms = $xmldoc->createElement('Transforms');
        $Reference->appendChild($Transforms);
        //Transform
        $newNode = $xmldoc->createElement('Transform');
        $Transforms->appendChild($newNode);
        $newNode->setAttribute('Algorithm', $this->URLTransfMeth_1);

        if (($tagid != 'InfRps') && ($tagid != 'LoteRps')) {
            //Transform
            $newNode = $xmldoc->createElement('Transform');
            $Transforms->appendChild($newNode);
            $newNode->setAttribute('Algorithm', $this->URLTransfMeth_2);
        }

        //DigestMethod
        $newNode = $xmldoc->createElement('DigestMethod');
        $Reference->appendChild($newNode);
        $newNode->setAttribute('Algorithm', $this->URLDigestMeth);
        //DigestValue
        $newNode = $xmldoc->createElement('DigestValue', $digValue);
        $Reference->appendChild($newNode);
        // extrai os dados a serem assinados para uma string
        $dados = $SignedInfo->C14N(FALSE, FALSE, NULL, NULL);
        //inicializa a variavel que irá receber a assinatura
        $signature = '';
        //executa a assinatura digital usando o resource da chave privada
        openssl_sign($dados, $signature, $pkeyid);
        //codifica assinatura para o padrao base64
        $signatureValue = base64_encode((string) $signature);
        //SignatureValue
        $newNode = $xmldoc->createElement('SignatureValue', $signatureValue);
        $Signature->appendChild($newNode);
        //KeyInfo
        $KeyInfo = $xmldoc->createElement('KeyInfo');
        $Signature->appendChild($KeyInfo);
        //X509Data
        $X509Data = $xmldoc->createElement('X509Data');
        $KeyInfo->appendChild($X509Data);
        //carrega o certificado sem as tags de inicio e fim
        $cert = $this->pCleanCerts($this->pubKEY);
        //X509Certificate
        $newNode = $xmldoc->createElement('X509Certificate', $cert);
        $X509Data->appendChild($newNode);
        //grava na string o objeto DOM
        //$xmldoc2->appendChild($Signature);

        $docxml2 = $xmldoc->saveXML();
        // libera a memoria
        openssl_free_key($pkeyid);
        //retorna o documento assinado
        $this->xml = trim($docxml2);
    } //fim signXML

    /**
     * enviarRps
     * Envia lote de Notas Fiscais de Serviço para a SEFAZ.
     * Este método pode enviar uma ou mais NFe para o SEFAZ, desde que,
     * o tamanho do arquivo de envio não ultrapasse 500kBytes
     * Este processo enviará somente até 50 NFe em cada Lote
     *
     * @name enviarRps
     * @param   array   $aNFe notas fiscais em xml uma em cada campo do array unidimensional MAX 50
     * @param   integer $idLote o id do lote e um numero que deve ser gerado pelo sistema
     * @return  mixed   False ou array ['cStat'=>103,','xMotivo'=>'Lote aceito']
     **/
    public function enviarRps()
    {

        if ($this->errStatus) {
            return;
        }

        $aNFSe = $this->xml;

        $sNFSe = str_ireplace('<?xml version="1.0" encoding="utf-8"?>', '', $aNFSe);
        $sNFSe = str_ireplace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>', '', $sNFSe);

        $cabec = '<cabecalho xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>1.00</versaoDados></cabecalho>';
        $sNFSe = str_replace('<?xml version="1.0"?>', '', $sNFSe);
        $dados = trim(str_replace("\n", "", $sNFSe));

        $data = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:end="http://endpoint.nfse.ws.webservicenfse.edza.com.br/">'
                    .'<soapenv:Header/>'
                    .'<soapenv:Body>'
                        .'<end:RecepcionarLoteRps>'
                            .'<RecepcionarLoteRpsRequest>'
                                .'<nfseCabecMsg><![CDATA[' . $cabec . ']]></nfseCabecMsg>'
                                .'<nfseDadosMsg><![CDATA[' . $dados . ']]></nfseDadosMsg>'
                            .'</RecepcionarLoteRpsRequest>'
                        .'</end:RecepcionarLoteRps>'
                    .'</soapenv:Body>'
                .'</soapenv:Envelope>';
        return $this->enviarXML($data);
    }


    public function gerarNfse(array $dados) {
        $valorTributoIBPT = (((float)$dados['valorTotal'] * 17.25) / 100);
        $valorTributoIBPT = number_format($valorTributoIBPT, 2);
        $descricao        = $dados['descricao'];
        $discriminacao    = $dados['descricao'];
        $artigo           = $dados['artigo'];
        $atividade        = $dados['atividade'];
        $observacao       = $dados['observacao'];
        $valorTotal       = $dados['valorTotal'];
        $itemListaServico = $dados['itemListaServico'];
        $idLancamentos    = $dados['idLancamentos'];
        $idLancamentos    = explode(',', (string) $idLancamentos);

        //trata lista de servico
        $listaServicos   = ['01.03', '01.01', '01.05', '01.07', '01.08'];
        $listaAtividades = ['1010158', '6311900', '2651500', '6201501', '6204000', '6209100'];

        //1 - consulta ultimo lote
        $lote = $this->gerarNovoLote();

        //definicao de dados da nota
        $nota = [];
        $nota['numero']     = $lote;
        $nota['numeroLote'] = $lote;
        $nota['serie'] = "1";
        $nota['tipo']  = "1";
        $nota['naturezaOperacao']         = "1";
        $nota['RegimeEspecialTributacao'] = "6";
        $nota['optanteSimplesNacional']   = "1";
        $nota['IncentivadorCultural']     = "2";
        $nota['status'] = "1";
        $nota['itens'][0]['valor'] = $valorTotal;
        $nota['itens'][0]['valorDeducoes'] = "0.00";
        $nota['itens'][0]['valorPis']  = "0.00";
        $nota['itens'][0]['valorCsll'] = "0.00";
        $nota['itens'][0]['issRetido'] = "2";
        $nota['itens'][0]['valorIss']  = ($nota['itens'][0]['valor'] * 0.03);
        $nota['itens'][0]['valorIssRetido']  = "0.00";
        $nota['itens'][0]['valorCofins']     = "0.00";
        $nota['itens'][0]['valorIr']         = "0.00";
        $nota['itens'][0]['outrasRetencoes'] = "0.00";
        $nota['itens'][0]['baseCalculo'] = $nota['itens'][0]['valor'];
        $nota['itens'][0]['aliquota']    = '3.00';
        $nota['itens'][0]['descontoIncondicionado'] = "0.00";
        $nota['itens'][0]['descontoCondicionado']   = "0.00";
        $nota['itens'][0]['itemListaServico']          = $listaServicos[$itemListaServico];
        $nota['itens'][0]['codigoTributacaoMunicipio'] = "0103"; //codigo de tributacao do municipio de Lauro de Freitas
        $nota['itens'][0]['discriminacao']   = $discriminacao;
        $nota['itens'][0]['codigoMunicipio'] = "2929206"; //codigo do municipio de Lauro de Freitas, que e o codigo da empresa
        $nota['artigo']     = $artigo;
        $nota['codigoCnae'] = $listaAtividades[$atividade];
        $nota['descricao']  = $descricao;
        $nota['observacao'] = $observacao;
        $nota['valorTributoIBPT']  = $valorTributoIBPT;
        $this->setDadosNfse($nota);
        $this->geraXmlNfse();
        $dadosRetorno = $this->enviarRps();

        /*$dadosRetorno = "<?xml version='1.0' encoding='UTF-8'?>".'<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/"><S:Body><ns2:RecepcionarLoteRpsResponse xmlns:ns2="http://endpoint.nfse.ws.webservicenfse.edza.com.br/"><RecepcionarLoteRpsResponse><outputXML><?xml version="1.0" encoding="UTF-8" standalone="no"?><EnviarLoteRpsResposta xmlns="http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd"><NumeroLote>45</NumeroLote><DataRecebimento>2020-10-30T15:24:35.997-03:00</DataRecebimento><Protocolo>HY4OZHQFD0SCYIERK8RVROIHODH0KYAC4X9CPRC0KTU9UZOFUI</Protocolo></EnviarLoteRpsResposta></outputXML></RecepcionarLoteRpsResponse></ns2:RecepcionarLoteRpsResponse></S:Body></S:Envelope>';*/

        if (stristr((string) $dadosRetorno, "EnviarLoteRpsResposta")) {
            $dataEmissao     = date("Y-m-d H:i:s");
            $dadosRetorno    = $this->extrairXml('<EnviarLoteRpsResposta xmlns="http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd">', '</EnviarLoteRpsResposta>', $dadosRetorno);
            $xmlDadosRetorno = simplexml_load_string((string) $dadosRetorno);
            $cliente         = $this->idCliente;
            $pessoaEmitiu    = $_SESSION['usrId'];
            $lote            = $xmlDadosRetorno->NumeroLote;
            $protocolo       = $xmlDadosRetorno->Protocolo;

            $idNfseInserida = $this->cadastrarNfse(
                $cliente,
                $pessoaEmitiu,
                $dataEmissao,
                $protocolo,
                $valorTotal,
                $descricao,
                $observacao,
                $idLancamentos,
                $serie,
                $lote
            );
            return 'EnviarLoteRpsResposta';###melhorar este retorno
        } else {
            $query = "DELETE FROM nfse_numeros WHERE lote = ".$lote;
            $this->executarQuery($query);
            return $dadosRetorno;
        }
    }


    public function tratarXmlParaEnvio($xmlEnvio): array|string
    {
        $xmlEnvio = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '<?xml version="1.0" encoding="UTF-8" standalone="no"?>', $xmlEnvio);
        $xmlEnvio = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>', '', $xmlEnvio);
        $xmlEnvio = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xmlEnvio);
        $xmlEnvio = str_replace('\n'  ,' ' ,$xmlEnvio);
        $xmlEnvio = str_replace('  '  ,' '  ,$xmlEnvio);
        $xmlEnvio = str_replace('  '  ,' '  ,$xmlEnvio);
        $xmlEnvio = str_replace('  '  ,' '  ,$xmlEnvio);
        $xmlEnvio = str_replace('  '  ,' '  ,$xmlEnvio);
        $xmlEnvio = str_replace('  '  ,' '  ,$xmlEnvio);
        return str_replace('> <' ,'><' , $xmlEnvio);
    }


    public function carregaCertificados($testaVal = true): bool
    {
        try {
            if (!function_exists('openssl_pkcs12_read')) {
                $msg = "Função não existente: openssl_pkcs12_read!!";
                throw new Exception($msg);
            }

            $certDir = dirname((string) $this->dadosEmpresa['path_certificado']);
            if (!is_dir($certDir)) {
                $msg = "Dados do certificado inválido";
                throw new Exception($msg);
            }

            $this->certsDir = $certDir;
            $this->certName = basename((string) $this->dadosEmpresa['path_certificado']);
            //monta o path completo com o nome da chave privada
            $this->priKEY = $this->certsDir . '/' . $this->soNumeros($this->dadosEmpresa['cnpj']) . '_priKEY.pem';
            //monta o path completo com o nome da chave prublica
            $this->pubKEY =  $this->certsDir . '/' . $this->soNumeros($this->dadosEmpresa['cnpj']) . '_pubKEY.pem';
            //monta o path completo com o nome do certificado (chave publica e privada) em formato pem
            $this->certKEY = $this->certsDir . '/' . $this->soNumeros($this->dadosEmpresa['cnpj']) . '_certKEY.pem';

            //monta o caminho completo até o certificado pfx
            $pfxCert = $this->certsDir . '/' . $this->certName;
            //verifica se o arquivo existe
            if (!file_exists($pfxCert)) {
                $msg = "Certificado não encontrado!! $pfxCert";
                throw new Exception($msg);
            }

            //carrega o certificado em um string
            $pfxContent = file_get_contents($pfxCert);
            //carrega os certificados e chaves para um array denominado $x509certdata
            if (!openssl_pkcs12_read($pfxContent, $x509certdata, $this->dadosEmpresa['senha_certificado'])) {
                $msg = "O certificado não pode ser lido!! Provavelmente corrompido ou com formato inválido!!";
                throw new Exception($msg);
            }

            //aqui verifica se existem as chaves em formato PEM
            //se existirem pega a data da validade dos arquivos PEM
            //e compara com a data de validade do PFX
            //caso a data de validade do PFX for maior que a data do PEM
            //deleta dos arquivos PEM, recria e prossegue
            $flagNovo = false;
            if (file_exists($this->pubKEY)) {
                $cert = file_get_contents($this->pubKEY);

                if (!$data = openssl_x509_read($cert)) {
                    //arquivo não pode ser lido como um certificado então deletar
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
    } //fim loadCerts


    /**
     * cleanCerts
     * Retira as chaves de inicio e fim do certificado digital
     * para inclusão do mesmo na tag assinatura do xml
     *
     * @name cleanCerts
     * @param    $certFile
     * @return   mixed false ou string contendo a chave digital limpa
     */
    protected function pCleanCerts($certFile): false|string
    {
        try {
            //inicializa variavel
            $data = '';
            //carregar a chave publica do arquivo pem
            if (!$pubKey = file_get_contents($certFile)) {
                $msg = "Arquivo não encontrado - $certFile .";
                throw new Exception($msg);
            }

            //carrega o certificado em um array usando o LF como referencia
            $arCert = explode("\n", $pubKey);
            foreach ($arCert as $curData) {
                //remove a tag de inicio e fim do certificado
                if (
                    !str_starts_with($curData, '-----BEGIN CERTIFICATE')
                    && !str_starts_with($curData, '-----END CERTIFICATE')
                ) {
                    //carrega o resultado numa string
                    $data .= trim($curData);
                }
            }
        } catch (Exception $e) {
            $this->erros[] = ($e->getMessage());
            return false;
        }

        return $data;
    } //fim cleanCerts


    function tiraAcentos($t): string|false
    {
        global $gPathLib;
        // $antes = html_entity_decode($t);
        if ($gPathLib != "") {
            $pa = ["a", "e", "i", "o", "u", "o", "o", "a", "e"];
            $de = ["á", "é", "í", "ó", "ú", "°", "º", "ª", "&"];
            $t  = str_replace($de, $pa, $t);
            $de = ["à", "è", "ì", "ò", "ù"];
            $t  = str_replace($de, $pa, $t);
            $de = ["â", "ê", "î", "ô", "û"];
            $t  = str_replace($de, $pa, $t);
            $t  = str_replace("ã", "a", $t);
            $t  = str_replace("õ", "o", $t);
            $t  = str_replace("ç", "c", $t);
            $t  = str_replace("Ç", "C", $t);
            $t  = $this->autoencode($t);
            $pa = ["A", "E", "I", "O", "U"];
            $de = ["Á", "É", "Í", "Ó", "Ú"];
            $t  = str_replace($de, $pa, $t);
            $de = ["À", "È", "Ì", "Ò", "Ù"];
            $t  = str_replace($de, $pa, $t);
            $de = ["Â", "Ê", "Î", "Ô", "Û"];
            $t  = str_replace($de, $pa, $t);
            $de = ["Ã", "Õ", "Ç"];
            $pa = ["A", "O", "C"];
            $t  = str_replace($de, $pa, $t);
            $de = [""];
            $pa = ["E"];
            $t = str_replace($de, $pa, $t);
        } else {
            $t = strtr($t, mb_convert_encoding("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&", 'ISO-8859-1'), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
            $t = strtr($t, mb_convert_encoding("áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&", 'UTF-8', 'ISO-8859-1'), "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
            $t = strtr($t, "áéíóúàèìòùâêîôûãõÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕçÇº°ª&", "aeiouaeiouaeiouaoAEIOUAEIOUAEIOUAOcCooae");
        }

        return iconv('ISO-8859-1', 'ASCII//TRANSLIT//IGNORE', $t);
    }


    function tiraEstranhos($var): string|array|null
    {
        $var = $this->tiraAcentos($var);
        return (preg_replace('/[^a-z0-9\+\-\=\.\,\!\?\:\;\@\%\&\(\)\{\}\<\>\[\]\s\'\$\/]+/i ', '', (string) $var));
    }


    function soNumeros($var): string|array|null
    {
        return (preg_replace('/[^0-9]+/i ', '', (string) $var));
    }


    function soNumerosIsento($var)
    {
        $var = $this->soNumeros($var);
        if ($var == "") {
            return "ISENTO";
        }

        return ($var);
    }


    function tiraPontos($t_st): array|string
    {
        return (str_replace('/', '', str_replace(")", "", str_replace("(", "", str_replace(" ", "", str_replace(".", "", str_replace("-", "", $t_st)))))));
    }


    public function consultarProtocolo(string $idNfse): string
    {
        $query = "SELECT nfse.protocolo FROM nfse WHERE nfse.id = ". $idNfse;
        $protocolo = $this->executarQuery($query);
        $protocolo = $protocolo->fetch(PDO::FETCH_ASSOC);
        $protocolo = htmlentities($protocolo['protocolo']);

        return $protocolo;
    }


    public function consultarNfse(string $protocolo)
    {
        $xmlEnviar = "<?xml version='1.0' encoding='UTF-8'?>"
                        .'<ConsultarLoteRpsEnvio id="'.uniqid().'" xmlns="http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd">'
                            .'<Prestador>'
                                .'<Cnpj>01108339000179</Cnpj>'
                                .'<InscricaoMunicipal>'.$this->dadosEmpresa['inscricaoMunicipal'].'</InscricaoMunicipal>'
                            .'</Prestador>'
                            .'<Protocolo>'.$protocolo.'</Protocolo>'
                        .'</ConsultarLoteRpsEnvio>';
        $this->xml = $xmlEnviar;

        $cabecalho = '<cabecalho xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>1.00</versaoDados></cabecalho>';
        $xmlDadosSoap = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">'
                            .'<Body>'
                                .'<ConsultarLoteRps xmlns="http://endpoint.nfse.ws.webservicenfse.edza.com.br/">'
                                    .'<ConsultarLoteRpsRequest xmlns="">'
                                        .'<nfseCabecMsg><![CDATA[' . $cabecalho . ']]></nfseCabecMsg>'
                                        .'<nfseDadosMsg><![CDATA[' . $xmlEnviar . ']]></nfseDadosMsg>'
                                    .'</ConsultarLoteRpsRequest>'
                                .'</ConsultarLoteRps>'
                            .'</Body>'
                        .'</Envelope>';

        $dadosNfse = $this->enviarXML($xmlDadosSoap);

        if (stristr((string) $dadosNfse, 'Nfse')) {
            $dadosNfse = $this->extrairXml('<Nfse>', '</Nfse>', htmlspecialchars_decode((string) $dadosNfse));
            $dadosNfse = str_replace('&','&amp;',$dadosNfse);
            $dadosNfse = str_replace("'", "", $dadosNfse);
        }

        return $dadosNfse;
    }


    public function consultarXmlNfse(string $idNfse)
    {
        $query   = "SELECT xml FROM nfse WHERE id = ".$idNfse;
        $xmlNfse = $this->executarQuery($query);
        $xmlNfse = $xmlNfse->fetch(PDO::FETCH_ASSOC);

        return $xmlNfse['xml'];
    }


    public function cancelarNfse(string $idNfse, string $idPessoa, $numeroNota)
    {
        $objetoDom                     = new DOMDocument('1.0', "UTF-8");
        $objetoDom->formatOutput       = true;
        $objetoDom->preserveWhiteSpace = false;

        $CancelarNfseEnvio     = $objetoDom->createElement('CancelarNfseEnvio');
        $Pedido                = $objetoDom->createElement('Pedido');
        $InfPedidoCancelamento = $objetoDom->createElement('InfPedidoCancelamento');
        $IdentificacaoNfse     = $objetoDom->createElement('IdentificacaoNfse');
        $Numero                = $objetoDom->createElement('Numero', $numeroNota);
        $Cnpj                  = $objetoDom->createElement('Cnpj', '01108339000179');
        $InscricaoMunicipal    = $objetoDom->createElement('InscricaoMunicipal', $this->dadosEmpresa['inscricaoMunicipal']);
        $CodigoMunicipio       = $objetoDom->createElement('CodigoMunicipio', 2919207);
        $CodigoCancelamento    = $objetoDom->createElement('CodigoCancelamento', '');

        $CancelarNfseEnvioAtributo     = $objetoDom->createAttribute('xmlns');
        $PedidoAtributo                = $objetoDom->createAttribute('xmlns');
        $InfPedidoCancelamentoAtributo = $objetoDom->createAttribute('id');

        $CancelarNfseEnvioAtributo->value     = 'http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd';
        $PedidoAtributo->value                = 'http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd';
        $InfPedidoCancelamentoAtributo->value = uniqid();

        $CancelarNfseEnvio->appendChild($Pedido);
        $CancelarNfseEnvio->appendChild($CancelarNfseEnvioAtributo);
        $Pedido->appendChild($InfPedidoCancelamento);
        $Pedido->appendChild($PedidoAtributo);
        $InfPedidoCancelamento->appendChild($IdentificacaoNfse);
        $InfPedidoCancelamento->appendChild($CodigoCancelamento);
        $InfPedidoCancelamento->appendChild($InfPedidoCancelamentoAtributo);
        $IdentificacaoNfse->appendChild($Numero);
        $IdentificacaoNfse->appendChild($Cnpj);
        $IdentificacaoNfse->appendChild($InscricaoMunicipal);
        $IdentificacaoNfse->appendChild($CodigoMunicipio);
        $objetoDom->appendChild($CancelarNfseEnvio);

        $xmlEnvio = $objetoDom->saveXML();
        $xmlEnvio = $this->tratarXmlParaEnvio($xmlEnvio);

        $this->xml   = $xmlEnvio;
        $this->assina("CancelarNfseEnvio");

        $cabecalho = '<cabecalho xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>1.00</versaoDados></cabecalho>';
        $xmlDadosSoap = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">'
                            .'<Body>'
                                .'<CancelarNfse xmlns="http://endpoint.nfse.ws.webservicenfse.edza.com.br/">'
                                    .'<CancelarNfseRequest xmlns="">'
                                        .'<nfseCabecMsg><![CDATA[' . $cabecalho . ']]></nfseCabecMsg>'
                                        .'<nfseDadosMsg><![CDATA[' . $this->xml . ']]></nfseDadosMsg>'
                                    .'</CancelarNfseRequest>'
                                .'</CancelarNfse>'
                           .'</Body>'
                        .'</Envelope>';

        $retorno = $this->enviarXML($xmlDadosSoap);

        if(!stristr((string) $retorno, 'Erro:')) {
           $cancelaNfse = "
                UPDATE nfse
                SET nfse.id_pessoas_cancelou='".$idPessoa
                    ."',nfse.data_cancelamento='".date("Y-m-d H:i:s")
                    ."',nfse.cancelada='1'"
                    ." WHERE nfse.id=".$idNfse;
            $this->executarQuery($cancelaNfse);

            $cancelaNota = "
                UPDATE fin_notas
                JOIN fin_lancamentos ON fin_lancamentos.id_fin_notas = fin_notas.id
                SET  fin_notas.cancelada = 1
                WHERE fin_lancamentos.id_nfse = ".$idNfse;
            $this->executarQuery($cancelaNota);
            $this->atualizarNumeroNotaLancamento($idNfse, "");

        }

        return $retorno;
    }


    public function gerarDanfeComProtocolo($protocolo)
    {
        $nfseEnvelopada    = $this->consultarNfse($protocolo);
        $xml               = simplexml_load_string((string) $nfseEnvelopada);
        $cnpj              = $xml->InfNfse->PrestadorServico->IdentificacaoPrestador->Cnpj;
        $numeroNfse        = $xml->InfNfse->Numero;
        $codigoVerificacao = $xml->InfNfse->CodigoVerificacao;

        return $this->gerarLinkDanfe($cnpj, $numeroNfse, $codigoVerificacao);
    }


    public function gerarLinkDanfe(string $cnpj, string $numeroNfse, string $codigoVerificacao): string
    {
        return 'http://lftributos.metropolisweb.com.br:8181/'
                .'metropolisWEB/nfe/verificaAutenticidadeNfePublico.do?'
                .'metodo=executarPrepararRelatorio'
                .'&tipoPrestador=PJ'
                .'&cnpjPrestador=' . $cnpj
                .'&numeroNfe=' . $numeroNfse
                .'&codigoVerificacao=' . $codigoVerificacao;
    }


    public function extrairXml($tagInicial, string $tagFinal, $xml): string
    {
        if(trim((string) $xml) !== '') {
            $xml            = htmlspecialchars_decode((string) $xml);
            $posicaoInicial = strpos($xml, (string) $tagInicial);
            $xmlSemInicio   = substr($xml, $posicaoInicial);
            $posicaoFinal   = strpos($xmlSemInicio, $tagFinal);
            $xmlExtraido    = substr($xmlSemInicio, 0, $posicaoFinal);
            $xmlExtraido .= $tagFinal;
        }

        return $xmlExtraido;
    }


    public function verificarErroRequisicao($resultado)
    {
        $dadosResultado = htmlspecialchars_decode((string) $resultado);
        if (
            stripos($dadosResultado, 'InfNfse') === false
            && stripos($dadosResultado, '<Protocolo>') === false
        ) {
            $dadosResultado = $this->extrairXml('<ListaMensagemRetorno>', '</ListaMensagemRetorno>', $dadosResultado);
            $dadosMensagemErro = simplexml_load_string((string) $dadosResultado);

            foreach ($dadosMensagemErro->MensagemRetorno as $mensagemRetorno) {
                $codigoErro = $mensagemRetorno->Codigo;
                $mensagem   = $mensagemRetorno->Mensagem;
                $correcao   = $mensagemRetorno->Correcao;
            }

            $resultado = "Código de erro: ".$codigoErro
                        ."<br> Erro: ".$mensagem
                        ."<br> Solução sugerida: ".$correcao;
        }

        return $resultado;
    }


    public function consultarIdNfse(string $idNota)
    {
        $consultarIdNfse = "SELECT nfse.id
                            FROM nfse
                            JOIN fin_lancamentos ON fin_lancamentos.id_nfse = nfse.id
                            JOIN fin_notas ON fin_notas.id = fin_lancamentos.id_fin_notas
                            WHERE fin_notas.id = " . $idNota;
        $idNfse = $this->executarQuery($consultarIdNfse);
        $idNfse = $idNfse->fetch(PDO::FETCH_ASSOC);

        return $idNfse['id'];
    }


    public function consultarSituacao(string $idNfse)
    {
        $consultaSituacao = "
            SELECT CONCAT(
                nfse.situacao,
                IF(nfse.cancelada = '1', ' - CANCELADA', '')
            ) AS situacao
            FROM nfse
            WHERE nfse.id = " . $idNfse;
        $situacao = $this->executarQuery($consultaSituacao);
        $situacao = $situacao->fetch(PDO::FETCH_ASSOC);

        return $situacao['situacao'];
    }


    public function atualizarSituacao(string $idNfse)
    {
        $protocolo    = $this->consultarProtocolo($idNfse);
        $xmlDadosNfse = $this->consultarNfse($protocolo);

        if (!empty($xmlDadosNfse) && !stristr((string) $xmlDadosNfse, 'Erro:')) {

            $this->conn->beginTransaction();
            //1 - Atualiza o xml da NFSe de acordo com a consulta
            $this->atualizarXmlNfse($xmlDadosNfse, $idNfse);

            //2 - atualiza situacao da NFSe
            $atualizaSituacao = "UPDATE nfse
                                SET nfse.situacao = 'APROVADA', nfse.confirmada = '1'
                                WHERE nfse.id = " . $idNfse;
            $this->executarQuery($atualizaSituacao);

            //3 - Consulta proximo lote da NFSe
            $xmlDadosNfse = simplexml_load_string((string) $xmlDadosNfse);
            $serie        = $xmlDadosNfse->InfNfse->IdentificacaoRps->Serie;
            $numero       = $xmlDadosNfse->InfNfse->Numero;
            //$proximoLote  = $this->gerarNovoLote();

            //4 - atualiza serie e numero da NFSe
            $ret = $this->atualizarNfseNumeros($serie, $numero, $idNfse);

            //5 - Atualiza numero da NFSe na tabela fin_lancamentos
            $this->atualizarNumeroNotaLancamento($idNfse, $numero);

             //6 - Atualiza numero da NFSe na tabela de fin_notas
            $this->atualizarNumeroNota($idNfse, $numero);

            //7 - Conclui as transacoes
            $this->conn->commit();

        }

        return $xmlDadosNfse;
    }


    public function consultarLoteGerado()
    {
        $selectUltimoLote = "SELECT lote FROM nfse_numeros WHERE id = ".$this->conn->lastInsertId();
        $ultimoLote       = $this->executarQuery($selectUltimoLote);

        return $ultimoLote->fetch(PDO::FETCH_ASSOC)['lote'];
    }


    public function inserirNfse(string $cliente, string $pessoaEmitiu, string $dataEmissao, string $protocolo)
    {
        $insertNfse = "
            INSERT INTO nfse
                        (id_pessoas_cliente,
                        id_pessoas_emitiu,
                        data_emissao,
                        protocolo,
                        emitida,
                        situacao)
            VALUES('"
                    .$cliente     ."','"
                    .$pessoaEmitiu."','"
                    .$dataEmissao ."','"
                    .$protocolo   ."','"
                    ."1','"
                    ."SUBMETIDA"
                    ."'),";
        $insertNfse     = substr($insertNfse, 0, -1);
        $insertNfse .= ";";
        $this->executarQuery($insertNfse);

        return $this->conn->lastInsertId();
    }


    public function cadastrarNota (
        string $idCliente,
        string $pessoaEmitiu,
        string $dataEmissao,
        string $valorTotal,
        string $descricao,
        string $observacao
    ) {

        $insertNota = "
            INSERT INTO fin_notas
            (
            id_pessoas,
            id_pessoas_criou,
            data_criacao,
            data_emissao,
            valor,
            descricao,
            observacoes,
            cancelada,
            id_pessoas_filial)
            VALUES('".$idCliente     ."','"
                      .$pessoaEmitiu  ."','"
                      .$dataEmissao   ."',' "
                      .$dataEmissao   ."','"
                      .$valorTotal    ."', '"
                      .$descricao     ."','"
                      .$observacao    ."', '0', '0');";
        $this->executarQuery($insertNota);

        return $this->conn->lastInsertId();
    }


    public function atualizarNfLancamentos(string $idNfseInserida, $idLancamentos, string $idNotaInserida): void
    {
        $updateLancamento = "
            UPDATE fin_lancamentos
            SET fin_lancamentos.id_nfse      = ".$idNfseInserida
            ." ,fin_lancamentos.id_fin_notas = ".$idNotaInserida
            ." WHERE fin_lancamentos.id IN (";

        foreach ($idLancamentos as $idLancamento) {
            $updateLancamento .= "'".$idLancamento."',";
        }

        $updateLancamento = substr($updateLancamento, 0, -1);
        $updateLancamento .=  ");";

        $this->executarQuery($updateLancamento);
    }


    public function atualizarXmlNfse(string $xmlNfse, string $idNfseInserida): void
    {
        $updateXmlNfse = "UPDATE nfse SET nfse.xml = '".$xmlNfse . "' WHERE nfse.id = " . $idNfseInserida . ";";
        $this->executarQuery($updateXmlNfse);
    }


    public function inserirNfseNumeros($ultimoLote, $idNfseInserida): void
    {
        $insertNfseNumeros = "UPDATE nfse_numeros SET id_nfse = ". (int) $idNfseInserida." WHERE lote = {$ultimoLote};";
        $this->executarQuery($insertNfseNumeros);
    }


    public function atualizarNfseNumeros(string $serie, string $numero, string $idNfse): void
    {
        $atualizarNfseNumeros = "
            UPDATE nfse_numeros
            SET serie  = '" . $serie . "',
                numero = '" . $numero . "'
            WHERE id_nfse = '" . $idNfse . "';";
        $this->executarQuery($atualizarNfseNumeros);
    }


    public function atualizarNumeroNota(string $idNfse, string $numero): void
    {
        $atualizaNumeroNota  = "
            UPDATE fin_notas
            JOIN   fin_lancamentos ON fin_lancamentos.id_fin_notas = fin_notas.id
            SET    fin_notas.numero = IFNULL(CONCAT(fin_notas.numero, '_', '".$numero."'), CONCAT('_', '".$numero."'))
            WHERE  fin_lancamentos.id_nfse = " . $idNfse;
        $this->executarQuery($atualizaNumeroNota);
    }


    public function atualizarNumeroNotaLancamento(string $idNfse, $numero): void
    {
        if ($numero == "") {
            $numero = "SUBSTRING_INDEX(fin_lancamentos.numero, '_', 1) ";
        } else {
            $numero = "CONCAT(fin_lancamentos.numero, '_', '".$numero."') ";
        }

        $atualizaNumeroNotaLancamento = "UPDATE gadmin.fin_lancamentos SET numero = {$numero} WHERE fin_lancamentos.id_nfse = " . $idNfse;
        $this->executarQuery($atualizaNumeroNotaLancamento);
    }


    public function gerarNovoLote()
    {
        $insereLote  = "INSERT INTO gadmin.nfse_numeros (lote)
                        SELECT MAX(lote)+1 FROM nfse_numeros;";
        $this->executarQuery($insereLote);

        return $this->consultarLoteGerado();
    }


    public function executarQuery($query)
    {
        try {
            $query = $this->conn->prepare($query);
            if ($query->execute()) {
                return $query;
            }

            echo $sql;
            echo "Procedimento com erros!";
            print_r($this->conexao->errorInfo());
        } catch (PDOException) {
            echo 'Erro ao conectar banco de dados!<br>';
        }
    }


    public function gerarXmlNfse($dadosNfse): never
    {
        //este echo deve ficar habilitado para que este procedimento ocorra
        echo $dadosNfse;
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment;filename="'.basename('NFSE_XML.xml').'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize('NFSE_XML.xml'));
        readfile('NFSE_XML.xml');
        exit;
    }


    public function verificarDadosCliente(array $dadosCliente)
    {
        $retorno = true;
        $mensagem = "";
        if ($dadosCliente['razaoSocial'] == '') {
            $mensagem .= "- Razão social<br>";
        }

        if ($dadosCliente['cnpj'] == '') {
            $mensagem .= "- CNPJ<br>";
        }
        /*
            if($dadosCliente['inscricaoMunicipal'] == '') {
                $mensagem .= "- Inscrição Municipal<br>";
            }
        */
        if ($dadosCliente['endereco'] == '') {
            $mensagem .= "- Endereço<br>";
        }

        if ($dadosCliente['enderecoNumero'] == '') {
            $mensagem .= "- Número de endereço<br>";
        }

        if ($dadosCliente['enderecoBairro'] == '') {
            $mensagem .= "- Bairro<br>";
        }

        if ($dadosCliente['municipio'] == '') {
            $mensagem .= "- Município<br>";
        }

        if ($dadosCliente['enderecoCep'] == '') {
            $mensagem .= "- CEP<br>";
        }

        if ($dadosCliente['enderecoUf'] == '') {
            $mensagem .= "- UF<br>";
        }

        /*
            if($dadosCliente['enderecoIbgeMunicipio'] == '') {
                $mensagem .= "- Endereço IBGE do município<br>";
            }
        */
        if ($mensagem !== "") {
            $mensagem = "Este cliente possui irregularidades no cadastro.<br> Dados faltantes:<br>".$mensagem;
            $retorno  = $mensagem;
        }

        return $retorno;
    }


    public function buscarDadosCliente($idCliente)
    {

        $consultaDadosCliente = "
            SELECT  pessoas.id,
                    pessoas_juridicas.razao_social AS razaoSocial,
                    pessoas_juridicas.cnpj,
                    pessoas_juridicas.insc_municipal AS inscricaoMunicipal,
                    pessoas_enderecos.endereco,
                    pessoas_enderecos.numero      AS enderecoNumero,
                    pessoas_enderecos.bairro      AS enderecoBairro,
                    enderecos_cidades.descricao   AS municipio,
                    pessoas_enderecos.cep         AS enderecoCep,
                    enderecos_estados.sigla       AS enderecoUf,
                    enderecos_cidades.codigo_ibge AS enderecoIbgeMunicipio,
                    pessoas_juridicas.email_financeiro AS email,
                    TRIM(pessoas.telefone) AS telefone
            FROM pessoas
            JOIN pessoas_juridicas      ON pessoas_juridicas.id_pessoas = pessoas.id
            LEFT JOIN pessoas_enderecos ON pessoas_enderecos.id_pessoas = pessoas.id
            LEFT JOIN enderecos_cidades ON enderecos_cidades.id = pessoas_enderecos.id_enderecos_cidades
            LEFT JOIN enderecos_estados ON enderecos_estados.id = pessoas_enderecos.id_enderecos_estados
            WHERE pessoas.id = ".(int) $idCliente.";";
        $dadosCliente = $this->executarQuery($consultaDadosCliente);
        return $dadosCliente->fetch(PDO::FETCH_ASSOC);
    }


    public function cadastrarNfse (
        $cliente,
        $pessoaEmitiu,
        $dataEmissao,
        $protocolo,
        $valorTotal,
        $descricao,
        $observacao,
        $idLancamentos,
        $serie,
        $proximoLote
    ) {
        $this->conn->beginTransaction();
        //1 - insere nfse ent. nfse
        $idNfseInserida = $this->inserirNfse(
            $cliente,
            $pessoaEmitiu,
            $dataEmissao,
            $protocolo);

        //2 - cadastra nota na ent. fin_notas
        $descricao  = str_replace('@', ' ', $descricao);
        $observacao = str_replace('@', ' ', $observacao);

        $idNotaInserida = $this->cadastrarNota(
            $cliente,
            $pessoaEmitiu,
            $dataEmissao,
            $valorTotal,
            $descricao,
            $observacao);

        //3 - atualiza fin_lancamentos com id da nfse
        $this->atualizarNfLancamentos($idNfseInserida, $idLancamentos, $idNotaInserida);

        //4 - Atualiza o id da nova NFSe para o numero do lote gerado
        $this->inserirNfseNumeros(
            $this->nota['numeroLote'],
            $idNfseInserida
        );

        //conclui procedimento no banco
        $this->conn->commit();

        return $idNfseInserida;
    }


    public function extrairNomeServico($xmlServico): string
    {
        $posicaoNomeServico = stripos((string) $xmlServico, '&#');
        return substr((string) $xmlServico, 0, $posicaoNomeServico);
    }


    public function pxml($xml, $exit = true): void
    {
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = FALSE;
        $dom->loadXML($xml);
        $dom->formatOutput = TRUE;
        echo '<textarea rows=100 cols=200>'.$dom->saveXML().'</textarea>';
        if ($exit) {
            exit;
        }
    }


    public function extrairConteudoTagsXML($xml, string $tagInicial, string $tagFinal): string
    {
        //a tag inicial deve ser a tag que se deseja pegar
        //a tag final e a tag anterior a que se deseja pegar
        $xml            = htmlspecialchars_decode((string) $xml);
        $posicaoInicial = strpos($xml, '<'.$tagInicial.'>');
        $xmlSemInicio   = substr($xml, $posicaoInicial);
        $posicaoFinal   = strpos($xmlSemInicio, '</'.$tagFinal.'>');

        return substr($xmlSemInicio, 0, $posicaoFinal);
    }


    public function autoencode($s) //encode if necessary
    {
    //if (!mb_check_encoding($s,'UTF-8'))
        if (!$this->check_utf8($s)) {
            return mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
        }

        return $s;
    }


    public function check_utf8($str): bool {
        $len = strlen((string) $str);
        for($i = 0; $i < $len; $i++){
            $c = ord($str[$i]);
            if ($c > 128) {
                if ($c > 247) {
                    return false;
                } elseif ($c > 239) {
                    $bytes = 4;
                } elseif ($c > 223) {
                    $bytes = 3;
                } elseif ($c > 191) {
                    $bytes = 2;
                } else {
                    return false;
                }

                if (($i + $bytes) > $len) {
                    return false;
                }

                while ($bytes > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bytes--;
                }
            }
        }
        return true;
    }
}


/*
    CREATE TABLE nfse_numeros (
      id bigint NOT NULL AUTO_INCREMENT,
      serie int DEFAULT NULL,
      lote int DEFAULT NULL,
      numero bigint DEFAULT '0',
      PRIMARY KEY (id)
    ) ENGINE=InnoDB;

    ALTER TABLE fin_lancamentos
        ADD COLUMN id_nfse bigint DEFAULT 0
        AFTER id_fin_ccustos;

    CREATE TABLE nfse (
        id BIGINT(20) PRIMARY KEY AUTO_INCREMENT,
        id_pessoas_cliente BIGINT(20) DEFAULT 0,
        id_pessoas_emitiu BIGINT(20) DEFAULT 0,
        id_pessoas_cancelou BIGINT(20) DEFAULT 0,
        data_emissao DATETIME,
        data_cancelamento DATETIME,
        numero VARCHAR(20),
        xml LONGTEXT,
        protocolo VARCHAR(255),
        emitida TINYINT(4) DEFAULT 0,
        confirmada TINYINT(4) DEFAULT 0,
        cancelada TINYINT(4) DEFAULT 0
    );

    INSERT INTO gadmin.gfw_menus
    ( idd, active, show_at, locale, keyword, title, content, link, file, icon, `type`, order1, order2)
    VALUES( 1, 1, 2, 'pt_BR', 'NFSE', 'TESTE NFSE', 'Gestão de Notas Fiscais de Serviço', 'nfse', 'financeiro/nfse_teste.php', 'file-text-o', 'dropdownLink', 6, 3);

    ALTER TABLE nfse ADD COLUMN situacao varchar(30) DEFAULT 0;
    ALTER TABLE nfse_numeros ADD COLUMN id_nfse BIGINT(20) DEFAULT 0;
*/



// <p>Esta mensagem refere-se à Nota Fiscal de Serviços Eletrônica No. 20204477 emitida pelo prestador de serviços:</p>

// <p>Razão Social: GIUSOFT TECNOLOGIA LTDA - EPP</p>
// <p>E-mail: jonhson@paccpe.com.br</p>
// <p>Inscrição: 0010004335</p>
// <p>CNPJ: 01.108.339/0001-79</p>

// <p>Alternativamente, acesse o portal http://www.laurodefreitas.ba.gov.br e verifique a autenticidade desta NFS-e informando os dados a seguir:</p>
// <p>CNPJ do Prestador = 01.108.339/0001-79</p>
// <p>Número da NFS-e = 20204477</p>
// <p>Código de Verificação = 42DE77868</p>

// <p>MUNICIPIO DE LAURO DE FREITAS</p>
// <p>http://www.laurodefreitas.ba.gov.br</p>

// <p>* Este e-mail foi enviado automaticamente pelo Sistema de Notas Fiscais de Serviços Eletrônica (NFS-e) da GIUSOFT Tecnologia. Em caso de dúvidas, entre em contato com</p>

// <p>Endereço do Servidor: https://lftributos.metropolisweb.com.br/metropolisWEB/nfe/notaFiscalEletronica.do</p>