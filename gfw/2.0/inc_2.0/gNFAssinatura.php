<?php
/**
 * Este arquivo Ã© parte do projeto NFePHP - Nota Fiscal eletrÃ´nica em PHP.
 *
 * Este programa Ã© um software livre: vocÃª pode redistribuir e/ou modificÃ¡-lo
 * sob os termos da LicenÃ§a PÃºblica Geral GNU como Ã© publicada pela FundaÃ§Ã£o
 * para o Software Livre, na versÃ£o 3 da licenÃ§a, ou qualquer versÃ£o posterior.
 *
 * Este programa Ã© distribuÃ­do na esperanÃ§a que serÃ¡ Ãºtil, mas SEM NENHUMA
 * GARANTIA; sem mesmo a garantia explÃ­cita do VALOR COMERCIAL ou ADEQUAÃÃO PARA
 * UM PROPÃSITO EM PARTICULAR, veja a LicenÃ§a PÃºblica Geral GNU para mais
 * detalhes.
 *
 * VocÃª deve ter recebido uma cÃ³pia da LicenÃ§a Publica GNU junto com este
 * programa. Caso contrÃ¡rio consulte <http://www.fsfla.org/svnwiki/trad/GPLv3>.
 *
 * @package   NFePHP
 * @name      assinatura
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL v.3
 * @copyright 2009 &copy; NFePHP
 * @link      http://www.nfephp.org/
 * @author    {@link http://www.walkeralencar.com Walker de Alencar} <contato@walkeralencar.com>
 */

/**
 * assinatura
 *
 * @author  Roberto L. Machado <roberto.machado@superig.com.br>
 * @author  Djalma Fadel Junior <dfadel@ferasoft.com.br>
 */
 include_once "gNFCertificado.php";
class gNFAssinatura {
	private $certificado;

	function __construct() {
		$this->certificado = new gNFCertificado('Associacao.pfx');
	}

	/**
	 * @param   string XML
	 * @param   string tagID
	 * @return  mixed (FALSE se erro, senÃ£o string XML assinado)
	 **/
	function assinaXML($sXML,$tagName="",$tagID="") {
		if($sXML=="") {
			echo "<font color=red>ERRO(s):</font><br />";

			echo "<font color=red>-Nenhum arquivo XML encontrado!</font><br />";
			exit;
		}

/*
//  Create an instance of a certificate store object, load a PFX file,
//  locate the certificate we need, and use it for signing.
//  (a PFX file may contain more than one certificate.)
$certStore = new COM("Chilkat.CertStore");

//  The 1st argument is the filename, the 2nd arg is the
//  PFX file's password:
$success = $certStore->LoadPfxFile('Associacao.pfx','associacao');
if ($success != true) {
    print $certStore->lastErrorText() . "\n";
    exit;
}

$cert = $certStore->FindCertBySubject('Chilkat Software, Inc.');
if (is_null($cert)) {
    print $certStore->lastErrorText() . "\n";
    exit;
}

$pkey = $cert->ExportPrivateKey();
if (is_null($pkey)) {
    print $cert->lastErrorText() . "\n";
    exit;
}

//  Get the private key in XML format:
$pkeyXml = $pkey->getXml();

$rsa = new COM("Chilkat.Rsa");

//  Any string argument automatically begins the 30-day trial.
$success = $rsa->UnlockComponent('30-day trial');
if ($success != true) {
    print 'RSA component unlock failed' . "\n";
    exit;
}

//  Import the private key into the RSA component:
$success = $rsa->ImportPrivateKey($pkeyXml);
if ($success != true) {
    print $rsa->lastErrorText() . "\n";
    exit;
}

//  This example will sign a string, and receive the signature
//  in a hex-encoded string.  Therefore, set the encoding mode
//  to "hex":
$rsa->EncodingMode = 'hex';

$strData = 'This is the string to be signed.';

//  Sign the string using the sha-1 hash algorithm.
//  Other valid choices are "md2" and "md5".
$hexSig = $rsa->signStringENC($strData,'sha-1');

print $hexSig . "\n";

print 'Success!' . "\n";
exit;

*/


/*
# enable warnings
ini_set( 'track_errors', 1);
ini_set('error_reporting', E_ALL | E_STRICT);

# this is a sample relaxNG definition
$rng = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<grammar ns="" xmlns="http://relaxng.org/ns/structure/1.0"
  datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">
  <start>
    <element name="apple">
      <element name="pear">
        <data type="NCName"/>
      </element>
    </element>
  </start>
</grammar>
EOT;

# well formed xml, but invalid per schema
# too many pears
$bad_xml =<<<EOT
<?xml version="1.0"?>
<apple>
  <pear>Pear</pear>
  <pear>Pear</pear>
</apple>
EOT;

# well formed xml and valid per schema
$good_xml =<<<EOT
<?xml version="1.0"?>
<apple>
  <pear>Pear</pear>
</apple>
EOT;

# this function does the work, it tests the relaxNG in the string $rng
# against the xml in string $xml
Function relaxNG ( $xml, $rng ) {
        $dom_xml = new DomDocument;
        $dom_xml->loadXML($xml);

        if ( $dom_xml->relaxNGValidateSource ( $rng ) ) {
                echo "Good\n";
        } else {
                echo $php_errormsg . "\n";
        }
}

# test the good xml, will echo:
#    Good
relaxNG ($good_xml, $rng);

# test the bad xml, will echo:
#    Did not expect element pear there
relaxNG ($bad_xml, $rng);
exit;
*/


		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = false;
		$dom->loadXML($sXML);
		/*
		if ($dom->validate()) {
			 //echo "Documento valido!\n";
		}
		else
		{
			echo "Documento invalido!";
		}
*/
		$root = $dom->documentElement;
		//echo $tagName."<br>".$tagID;
		$dados="";
		$tagNameEncontrada=false;
		$nodes = $dom->getElementsByTagName($tagName);
		foreach ($nodes as $node)
		{
			foreach ($node->attributes AS $name=>$domAttr)
			{
				//echo $domAttr->value."<br>";
				if ($domAttr->value==$tagID)
				{
					$dados = $node->C14N(FALSE, FALSE, NULL, NULL);
					//echo $dados;exit;
				}
			}
			$tagNameEncontrada=true;
		}
		//exit;
/*
		if($tagID=="") { // atraves do nome da tag
			$node = $dom->getElementsByTagName($tagID)->item(0);
			$Id = trim($node->getAttribute("Id"));
			$idnome = ereg_replace('[^0-9]', '', $Id);

			//extrai os dados da tag para uma string
			$dados = $node->C14N(FALSE, FALSE, NULL, NULL);
		}
		else { // atraves do ID (busca em qualquer tag que tiver este ID)
			$Id = $tagID;
			$idnome = ereg_replace('[^0-9]', '', $tagID);

			$nodeById=$dom->getElementById($tagID);
			$dados= $nodeById->C14N(FALSE, FALSE, NULL, NULL);
		}
*/
		if ($dados<>"")
		{
			//calcular o hash dos dados
			$hashValue = hash('sha1', $dados, TRUE);

			//converte o valor para base64 para serem colocados no xml
			$digValue = base64_encode($hashValue);

			//monta a tag da assinatura digital
			$Signature = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Signature');
			$root->appendChild($Signature);
			$SignedInfo = $dom->createElement('SignedInfo');
			$Signature->appendChild($SignedInfo);

			//Cannocalization
			$newNode = $dom->createElement('CanonicalizationMethod');
			$SignedInfo->appendChild($newNode);
			$newNode->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');

			//SignatureMethod
			$newNode = $dom->createElement('SignatureMethod');
			$SignedInfo->appendChild($newNode);
			$newNode->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');

			//Reference
			$Reference = $dom->createElement('Reference');
			$SignedInfo->appendChild($Reference);
			$Reference->setAttribute('URI', '#'.$Id);

			//Transforms
			$Transforms = $dom->createElement('Transforms');
			$Reference->appendChild($Transforms);

			//Transform
			$newNode = $dom->createElement('Transform');
			$Transforms->appendChild($newNode);
			$newNode->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');

			//Transform
			$newNode = $dom->createElement('Transform');
			$Transforms->appendChild($newNode);
			$newNode->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');

			//DigestMethod
			$newNode = $dom->createElement('DigestMethod');
			$Reference->appendChild($newNode);
			$newNode->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');

			//DigestValue
			$newNode = $dom->createElement('DigestValue', $digValue);
			$Reference->appendChild($newNode);

			// extrai os dados a serem assinados para uma string
			$dados = $SignedInfo->C14N(FALSE, FALSE, NULL, NULL);
//echo $dados;exit;
			//inicializa a variavel que vai receber a assinatura
			$signature = '';

			//executa a assinatura digital usando o resource da chave privada
			$resp = openssl_sign($dados, $signature, openssl_pkey_get_private($this->certificado->sPrivateKey));

			//codifica assinatura para o padrao base64
			$signatureValue = base64_encode($signature);

			//SignatureValue
			$newNode = $dom->createElement('SignatureValue', $signatureValue);
			$Signature->appendChild($newNode);

			//KeyInfo
			$KeyInfo = $dom->createElement('KeyInfo');
			$Signature->appendChild($KeyInfo);

			//X509Data
			$X509Data = $dom->createElement('X509Data');
			$KeyInfo->appendChild($X509Data);

			//X509Certificate
			$newNode = $dom->createElement('X509Certificate', $this->certificado->sPublicKey);
			$X509Data->appendChild($newNode);

			
		}
		else {
			echo "<font color=red>ERRO(s):</font><br />";
			
			echo "<font color=red>-TagID $tagID nao encontrada!</font><br />";
			
			if(!$tagNameEncontrada) {
				echo "<font color=red>-TagName $tagName nao encontrada!</font><br />";
			}
			// Encerra a aplicacao devido a existencia dos erros acima
			exit;
		}
		//grava na string o objeto DOM
		return $dom->saveXML();
	}
	
	/**
	 *
	 * @param   string XML
	 * @param   string tagID
	 * @return  mixed (FALSE se erro, senao string XML assinado)
	 **/
	function assinaXMLNovo($xml,$tagName="",$tagID="") {


		if ($dados<>"")
		{
			//calcular o hash dos dados
			$hashValue = hash('sha1', $dados, TRUE);

			//converte o valor para base64 para serem colocados no xml
			$digValue = base64_encode($hashValue);

			$assinatura='<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
				<SignedInfo>
					<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
					<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
					<Reference URI="#'.$Id.'">
						<Transforms>
							<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
							<Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
						</Transforms>
						<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
						<DigestValue>'.$DigestValue.'</DigestValue>
					</Reference>
				</SignedInfo>
				<SignatureValue></SignatureValue>
				<KeyInfo>
					<X509Data>
						<X509Certificate></X509Certificate>
					</X509Data>
				</KeyInfo>
			</Signature>';

			// extrai os dados a serem assinados para uma string
			$dados = $SignedInfo->C14N(FALSE, FALSE, NULL, NULL);

			//inicializa a variavel que vai receber a assinatura
			$signature = '';

			//executa a assinatura digital usando o resource da chave privada
			$resp = openssl_sign($dados, $signature, openssl_pkey_get_private($this->certificado->sPrivateKey));

			//codifica assinatura para o padrao base64
			$signatureValue = base64_encode($signature);

			//SignatureValue
			$newNode = $dom->createElement('SignatureValue', $signatureValue);
			$Signature->appendChild($newNode);

			//KeyInfo
			$KeyInfo = $dom->createElement('KeyInfo');
			$Signature->appendChild($KeyInfo);

			//X509Data
			$X509Data = $dom->createElement('X509Data');
			$KeyInfo->appendChild($X509Data);

			//X509Certificate
			$newNode = $dom->createElement('X509Certificate', $this->certificado->sPublicKey);
			$X509Data->appendChild($newNode);

			
		}
		else {
			echo "<font color=red>ERRO(s):</font><br />";
			
			echo "<font color=red>-TagID $tagID nao encontrada!</font><br />";
			
			if(!$tagNameEncontrada) {
				echo "<font color=red>-TagName $tagName nao encontrada!</font><br />";
			}
			// Encerra a aplicacao devido a existencia dos erros acima
			exit;
		}
		//grava na string o objeto DOM
		return $dom->saveXML();
	}
///////////////////////////////////////////////////////////

	# this function does the work, it tests the relaxNG in the string $rng
	# against the xml in string $xml
	function relaxNG ( $xml, $rng ) {
			  $dom_xml = new DomDocument;
			  $dom_xml->loadXML($xml);

			  if ( $dom_xml->relaxNGValidateSource ( $rng ) ) {
						 echo "Good\n";
			  } else {
						 echo $php_errormsg . "\n";
			  }
	}

		/*
	FUNÇAo NOVA - VERIFICAR.... ADICIONADA EM 19-01-2009
	  Xml Signature Verify With Php
	*/
	function isValid() 
	{

		// $data is assumed to contain the data to be signed

		// fetch certificate from file and ready it
		$fp = fopen("Associacao.pfx", "r");
		$cert = fread($fp, 8192);
		fclose($fp);

		// state whether signature is okay or not
		// use the certificate, not the public key
		$ok = openssl_verify($data, $signature, $cert);
		if ($ok == 1) {
		echo "good";
		} elseif ($ok == 0) {
		echo "bad";
		} else {
		echo "ugly, error checking signature";
		}
    }	

}
?>