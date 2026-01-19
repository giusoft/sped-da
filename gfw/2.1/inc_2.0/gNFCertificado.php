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
 * @name      certificado
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL v.3
 * @copyright 2009 &copy; NFePHP
 * @link      http://www.nfephp.org/
 * @author    {@link http://www.walkeralencar.com Walker de Alencar} <contato@walkeralencar.com>
 */

/**
 * certificado
 *
 * @author  Roberto L. Machado <roberto.machado@superig.com.br>
 * @author  Djalma Fadel Junior <dfadel@ferasoft.com.br>
 */
class gNFCertificado {
    public $certificateFile;    // path/file do certificado p12 (pfx) tipo A1
    public $privateKeyFile;     // path/file da chave privada (nao precisa existir)
    public $publicKeyFile;      // path/file da chave publica (nao precisa existir)
    public $sPrivateKey;        // string da chave privada
    public $sPublicKey;         // string do certificado (chave publica)
    public $passKey;            // senha
    public $passPhrase;         // 

    function __construct($certificateFile=_NFE_CERTIFICATE_FILE) {

        $this->certificateFile  = $certificateFile;
        $this->privateKeyFile   = _NFE_PRIVATEKEY_FILE;
        $this->publicKeyFile    = _NFE_PUBLICKEY_FILE;

        $this->passKey          = _NFE_PASSKEY;
        $this->passPhrase       = _NFE_PASSPHRASE;

        openssl_pkcs12_read(file_get_contents($this->certificateFile), $x509cert, _NFE_PASSKEY);

        // chave publica (certificado)
        $aCert = explode("\n", $x509cert['cert']);
        foreach ($aCert as $curData) {
            if (strncmp($curData, '-----BEGIN CERTIFICATE', 22) != 0 && strncmp($curData, '-----END CERTIFICATE', 20) != 0 ) {
                $this->sPublicKey.= trim($curData);
            }
        }

        // chave privada
        $this->sPrivateKey = $x509cert['pkey'];


        if (!file_exists($this->privateKeyFile)) {
            file_put_contents($this->privateKeyFile, $x509cert['pkey']);
        }

        if (!file_exists($this->publicKeyFile)) {
            file_put_contents($this->publicKeyFile, $x509cert['cert']);
        }
    }

    function isValid() {
    }

}
