<?php
require_once('config/config.php');
require_once('libs/ToolsNFePHP.class.php');

//cores
$cRed = '#FF0000';
$cGreen = '#00CC00';

//versão do php
$phpversion = str_replace('-','',substr(PHP_VERSION, 0, 6));

$phpver = convVer($phpversion);
if ($phpver > '050200'){
    $phpcor = $cGreen;
} else {
    $phpcor = $cRed;
}

//url
$guessed_url = 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
$guessed_url = rtrim(dirname($guessed_url), 'install');

//path
$pathdir = dirname( __FILE__ );

//teste dos modulos
$modules = new moduleCheck();

//curl
$modcurl = false;
if($modcurl = $modules->isLoaded('curl')) { // Testa se curl esta carregado
  $modcurl_ver = $modules->getModuleSetting('curl', 'cURL Information'); // Get specific information about a setting in curl
}
$cCurl = $cRed;
$curlver = ' N&atilde;o instalado !!!';
if ($modcurl){
    $curlver = convVer($modcurl_ver);
    if($curlver > '071002'){
        $curlver = ' vers&atilde;o ' . $modcurl_ver;
        $cCurl = $cGreen;
    }
}

//dom
$moddom = false;
if($moddom = $modules->isLoaded('dom')) { // Testa se curl esta carregado
  $moddom_enable = $modules->getModuleSetting('dom', 'DOM/XML');
  $moddom_libxml = $modules->getModuleSetting('dom', 'libxml Version');
}
$cDOM = $cRed;
$domver = ' N&atilde;o instalado !!!';
if ($modcurl){
    $domver = convVer($moddom_libxml);
    if($domver > '020600' && $moddom_enable=='enabled' ){
        $domver = ' libxml vers&atilde;o ' . $moddom_libxml;
        $cDOM = $cGreen;
    } else {
        $domver = '';
    }
}

//mcrypt
$modmcrypt = false;
if($modmcrypt = $modules->isLoaded('mcrypt')) { // Testa se curl esta carregado
  $modmcrypt_ver = $modules->getModuleSetting('mcrypt', 'Version');
}
$cmcry = $cRed;
$mcryver = ' N&atilde;o instalado !!!';
if($modmcrypt){
    $mcryver = convVer($modmcrypt_ver);
    if($mcryver  > '010101'){
        $cmcry = $cGreen;
        $mcryver = ' vers&atilde;o ' . $modmcrypt_ver;
    }
}

//soap
$modsoap = false;
if($modsoap = $modules->isLoaded('soap')) { // Testa se curl esta carregado
  $modsoap_enable = $modules->getModuleSetting('soap', 'Soap Client');
}
$cSOAP = $cRed;
$soapver = ' N&atilde;o instalado !!!';
if($modsoap){
    if($modsoap_enable=='enabled'){
        $cSOAP = $cGreen;
        $soapver = $modsoap_enable;
    }
}

//teste de escrita no diretorio dos certificados
$filen = $pathdir.DIRECTORY_SEPARATOR.'certs'.DIRECTORY_SEPARATOR.'teste.txt';
$cdCerts = $cRed;
$wdCerts= ' Sem permiss&atilde;o !!';
if ( file_put_contents($filen, "teste\r\n")){
    $cdCerts = $cGreen;
    $wdCerts= ' Permiss&atilde;o OK';
    unlink($filen);
}

//verificação da validade do certificado
$nfe = new ToolsNFePHP();
if ($nfe->certDaysToExpire > 0){
    $certVal = "Certificado v&aacute;lido (" . $nfe->certDaysToExpire . ' dias para expirar.)';
} else {
    $certVal = "Certificado INV&Aacute;LIDO !!!";
}

//teste do diretorio de arquivo dos xml
$aDir = explode(DIRECTORY_SEPARATOR,$arquivosDir);
$dirstring='';
$cDir = $cRed;
$wdDir = 'FALHA';
$obsDir= ' Sem permiss&atilde;o !!';
for($x = 0;$x<count($aDir)-1;$x++){
    $dirstring .= $aDir[$x].DIRECTORY_SEPARATOR;
}
if ( is_dir($dirstring) ){
    if ($teste = mkdir($dirstring . "_teste", 0777)){
        $cDir = $cGreen;
        $wdDir= ' Permiss&atilde;o OK';
        $obsDir = $arquivosDir;
        rmdir($dirstring . "_teste");
    } else {
        $wdDir= ' Sem permiss&atilde;o !! '. $arquivosDir;
    }
} else {
    $obsDir= ' O diret&oacute;rio indicado n&atilde;o existe !! ( ' . $dirstring . ' )';
}


//tipo de ambiente
if($ambiente == 1){
    $selAmb2 = '';
    $selAmb1 = 'selected';
} else {
    $selAmb1 = '';
    $selAmb2 = 'selected';
}

//unidade da federação
$duf = "\$selUF$UF = \"".'selected'."\";";
eval($duf);

//danfe formato
if ($danfeFormato=='P'){
    $selFormP = 'selected';
    $selFormL = '';
} else {
    $selFormL = 'selected';
    $selFormP = '';
}

//danfe canhoto
if ($danfeCanhoto){
    $selCanh1 = 'selected';
    $selCanh0 = '';
} else {
    $selCanh0 = 'selected';
    $selCanh1 = '';
}

//função para padronização do numero de versões de 2.7.2 para 020702
function convVer($ver){
    $aVer = explode('.',$ver);
    $nver = str_pad($aVer[0], 2, "0", STR_PAD_LEFT) . str_pad($aVer[1], 2, "0", STR_PAD_LEFT) . str_pad($aVer[2], 2, "0", STR_PAD_LEFT);
    return $nver;
}


//classe de verificação dos modulos instalados no PHP
class moduleCheck {

  public $Modules;

  //function parseModules() {
  function __construct() {
   ob_start(); // Stop output of the code and hold in buffer
   phpinfo(INFO_MODULES); // get loaded modules and their respective settings.
   $data = ob_get_contents(); // Get the buffer contents and store in $data variable
   ob_end_clean(); // Clear buffer

   $data = strip_tags($data,'<h2><th><td>'); // Keep only the items in the <h2>,<th> and <td> tags

   // Use regular expressions to filter out needed data
   // Replace everything in the <th> tags and put in <info> tags
   $data = preg_replace('/<th[^>]*>([^<]+)<\/th>/',"<info>\\1</info>",$data);

   // Replace everything in <td> tags and put in <info> tags
   $data = preg_replace('/<td[^>]*>([^<]+)<\/td>/',"<info>\\1</info>",$data);

   // Split the data into an array
   $vTmp = preg_split('/(<h2>[^<]+<\/h2>)/',$data,-1,PREG_SPLIT_DELIM_CAPTURE);
   $vModules = array();
   $count = count($vTmp);
   for ($i=1;$i<$count; $i+=2) { // Loop through array and add 2 instead of 1

    if (preg_match('/<h2>([^<]+)<\/h2>/',$vTmp[$i],$vMat)) { // Check to make sure value is a module

     $moduleName = trim($vMat[1]); // Get the module name
     $vTmp2 = explode("\n",$vTmp[$i+1]);
     foreach ($vTmp2 AS $vOne) {
       $vPat = '<info>([^<]+)<\/info>'; // Specify the pattern we created above
       $vPat3 = "/$vPat\s*$vPat\s*$vPat/"; // Pattern for 2 settings (Local and Master values)
       $vPat2 = "/$vPat\s*$vPat/"; // Pattern for 1 settings
       if (preg_match($vPat3,$vOne,$vMat)) { // This setting has a Local and Master value
         $vModules[$moduleName][trim($vMat[1])] = array(trim($vMat[2]),trim($vMat[3]));
       } elseif (preg_match($vPat2,$vOne,$vMat)) { // This setting only has a value
         $vModules[$moduleName][trim($vMat[1])] = trim($vMat[2]);
       }
     }

    }
   }
   $this->Modules = $vModules; // Store modules in Modules variable
  }

  // Quick check if module is loaded
  // Returns true if loaded, false if not
  public function isLoaded($moduleName) {
    if($this->Modules[$moduleName]) {
      return true;
    }
    return false;
  } // End function isLoaded

  // Get a module setting
  // Can be a single setting by specifying $setting value or all settings by not specifying $setting value
  public function getModuleSetting($moduleName, $setting = '') {
    // check if module is loaded before continuing
    if($this->isLoaded($moduleName)==false) {
      return 'Modulo não carregado'; // Module not loaded so return error
    }

    if($this->Modules[$moduleName][$setting]) { // You requested an individual setting
      return $this->Modules[$moduleName][$setting];
    } elseif(empty($setting)) { // List all settings
      return $this->Modules[$moduleName];
    }
    // If setting specified and no value found return error
    return 'Setting not found';
  } // End function getModuleSetting

  // List all php modules installed with no settings
  public function listModules() {
    foreach($this->Modules as $moduleName=>$values) { // Loop through modules
      // $moduleName is the key of $this->Modules, which is also module name
      $onlyModules[] = $moduleName;
    }
    return $onlyModules; // Return array of all module names
  } // End function listModules();
}


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Install NFePHP</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="nfephp.css" rel="stylesheet" type="text/css">
</head>
<body>
<div align="center">
  <table width="70%" border="0" align="center">
    <tr>
      <td width="41%"><div align="center">
          <h2>Instala&ccedil;&atilde;o NFePHP</h2>
        </div></td>
      <td width="14%">&nbsp;</td>
      <td width="45%"><div align="center"><img src="images/logo_nfephp.jpg" width="163" height="50"></div></td>
    </tr>
    <tr>
      <td colspan="3"><p>Esta rotina ir&aacute; verificar as condi&ccedil;&otilde;es
          da sua instala&ccedil;&atilde;o do PHP, se todas as necessidades para
          o funcionamento do API foram satisfeitas. Tamb&eacute;m fornece os meios
          para corrigir o arquivo de configura&ccedil;&atilde;o(config.php).</p>
        <p> <em>ATEN&Ccedil;&Acirc;O : Para utilizar a classe de envio de emails
          do API &eacute; necess&aacute;rio usar o &quot;pear&quot; e instalar
          a classe &quot;MAIL&quot;.</em></p>
        </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr bgcolor="#CC9933">
      <td> <div align="center"><strong>Modulos</strong></div></td>
      <td> <div align="center"><strong>Status</strong></div></td>
      <td> <div align="center"><strong>Comentario</strong></div></td>
    </tr>
    <tr bgcolor="#FFFF99">
      <td>PHP vers&atilde;o <?=$phpversion;?></td>
      <td bgcolor="<?=$phpcor;?>"><div align="center">ok</div></td>
      <td>A vers&atilde;o do PHP deve ser 5.2 ou maior</td>
    </tr>
    <tr bgcolor="#FFFF99">
      <td>cURL <?=$curlver?></td>
      <td bgcolor="<?=$cCurl;?>"><div align="center">ok</div></td>
      <td>A vers&atilde;o do cURL deve ser 7.10.2 ou maior</td>
    </tr>
    <tr bgcolor="#FFFF99">
      <td>DOM <?=$domver;?></td>
      <td bgcolor="<?=$cDOM;?>"><div align="center">ok</div></td>
      <td>O vers&atilde;o do libxml deve ser 2.7.0 ou maior</td>
    </tr>
    <tr bgcolor="#FFFF99">
      <td>SOAP </td>
      <td bgcolor="<?=$cSOAP;?>"><div align="center">ok</div></td>
      <td><?=$soapver;?></td>
    </tr>
    <tr bgcolor="#FFFF99">
      <td>mCrypt <?=$mcryver;?></td>
      <td bgcolor="<?=$cmcry;?>"><div align="center">ok</div></td>
      <td>sem comentarios</td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr bgcolor="#666666">
      <td colspan="3"><font color="#FFFFFF"><strong>Permiss&atilde;o de escrita</strong></font></td>
    </tr>
    <tr>
      <td colspan="3"><table width="90%" border="0" align="center">
          <tr bgcolor="#FFFFCC">
            <td>Diretorio certs</td>
            <td bgcolor="<?=$cdCerts;?>"><div align="center"><?=$wdCerts;?></div></td>
            <td>O diret&oacute;rio deve ter premiss&atilde;o de escrita</td>
          </tr>
          <tr bgcolor="#FFFFCC">
            <td>Diretorio NFe</td>
            <td bgcolor="<?=$cDir;?>"><div align="center"><?=$wdDir;?></div></td>
            <td bgcolor="#FFFFCC"><?=$obsDir;?></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
        </table></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td colspan="3"><form action="grava_config.php" method="post" name="formSave" id="formSave">
          <table width="90%" border="0" align="center">
            <tr bgcolor="#000000">
              <td width="40%"> <div align="center"><font color="#FFFFFF"><strong>Configura&ccedil;&otilde;es</strong></font></div></td>
              <td width="32%"> <div align="center"><font color="#FFFFFF"><strong>SetUp</strong></font></div></td>
              <td width="28%"> <div align="center"><font color="#FFFFFF"><strong>Coment&aacute;rios</strong></font></div></td>
            </tr>
            <tr bordercolor="#666666">
              <td><div align="right">Tipo de ambiente</div></td>
              <td><select name="ambiente" size="1" id="ambiente">
                      <option value="1" <?=$selAmb1;?>>Produ&ccedil;&atilde;o</option>
                  <option value="2" <?=$selAmb2;?>>Homologa&ccedil;&atilde;o</option>
                </select></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">URL dos WebServices</div></td>
              <td><input name="urlws" type="text" id="" value="<?=$arquivoURLxml?>" size="30" maxlength="200"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td height="26"><div align="right">Raz&atilde;o Social</div></td>
              <td><input name="razao" type="text" id="razao" value="<?=$empresa;?>" size="30" maxlength="200"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">Unidade da Federa&ccedil;&atilde;o do Emitente</div></td>
              <td><select name="siglauf" size="1">
                  <option value="AC" <?=$selUFAC;?>>AC</option>
                  <option value="AL" <?=$selUFAL;?>>AL</option>
                  <option value="AM" <?=$selUFAM;?>>AM</option>
                  <option value="AP" <?=$selUFAP;?>>AP</option>
                  <option value="BA" <?=$selUFBA;?>>BA</option>
                  <option value="CE" <?=$selUFCE;?>>CE</option>
                  <option value="DF" <?=$selUFDF;?>>DF</option>
                  <option value="ES" <?=$selUFES;?>>ES</option>
                  <option value="GO" <?=$selUFGO;?>>GO</option>
                  <option value="MA" <?=$selUFMA;?>>MA</option>
                  <option value="MG" <?=$selUFMG;?>>MG</option>
                  <option value="MS" <?=$selUFMS;?>>MS</option>
                  <option value="MT" <?=$selUFMT;?>>MT</option>
                  <option value="PA" <?=$selUFPA;?>>PA</option>
                  <option value="PB" <?=$selUFPB;?>>PB</option>
                  <option value="PE" <?=$selUFPE;?>>PE</option>
                  <option value="PI" <?=$selUFPI;?>>PI</option>
                  <option value="PR" <?=$selUFPR;?>>PR</option>
                  <option value="RJ" <?=$selUFRJ;?>>RJ</option>
                  <option value="RN" <?=$selUFRN;?>>RN</option>
                  <option value="RO" <?=$selUFRO;?>>RO</option>
                  <option value="RR" <?=$selUFRR;?>>RR</option>
                  <option value="RS" <?=$selUFRS;?>>RS</option>
                  <option value="SC" <?=$selUFSC;?>>SC</option>
                  <option value="SE" <?=$selUFSE;?>>SE</option>
                  <option value="SP" <?=$selUFSP;?>>SP</option>
                  <option value="TO" <?=$selUFTO;?>>TO</option>
                </select></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">Numero do CNPJ do emitente</div></td>
              <td><input name="numcnpj" type="text" id="numcnpj" value="<?=$cnpj;?>" size="14" maxlength="14"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">Nome do arquivo pfx (Certificado)</div></td>
              <td><input name="pfx" type="text" id="pfx" value="<?=$certName;?>" size="30" maxlength="200"></td>
              <td><?=$certVal;?></td>
            </tr>
            <tr>
              <td><div align="right">Senha da chave privada</div></td>
              <td><input name="keysenha" type="text" id="keysenha" value="<?=$keyPass;?>" size="20" maxlength="30"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">Senha de Decripta&ccedil;ao</div></td>
              <td><input name="passe" type="text" id="passe" value="<?=$passPhrase;?>" size="20" maxlength="30"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">URL base da API</div></td>
              <td><input name="urlapi" type="text" id="urlapi" value="<?=$baseurl;?>" size="30" maxlength="200"></td>
              <td><?=$guessed_url;?></td>
            </tr>
            <tr>
              <td><div align="right">Path completo</div></td>
              <td><input name="caminho" type="text" id="caminho" value="<?=$pathdir;?>" size="30" maxlength="200"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">Diret&oacute;rio de arquivo das NFe</div></td>
              <td><input name="dirnfe" type="text" id="dirnfe" value="<?=$arquivosDir;?>" size="30" maxlength="200"></td>
              <td></td>
            </tr>
            <tr>
              <td colspan="3" bgcolor="#999999"><strong>Schemas</strong></td>
            </tr>
            <tr>
              <td><div align="right">Vers&atilde;o 1.10</div></td>
              <td><input name="schema1" type="text" id="schema1" value="<?=$scheme['1.10'];?>" size="30" maxlength="200"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">Vers&atilde;o 2.00</div></td>
              <td><input name="schema2" type="text" id="schema2" value="<?=$scheme['2.00'];?>" size="30" maxlength="200"></td>
              <td>&nbsp;</td>
            </tr>
            <tr bgcolor="#999999">
              <td colspan="3"><strong>Configura&ccedil;&atilde;o do DANFE</strong></td>
            </tr>
            <tr>
              <td><div align="right">Formato</div></td>
              <td><select name="formato" id="formato">
                      <option value="P" <?=$selFormP;?>>Portraite</option>
                  <option value="L" <?=$selFormL;?>>Landscape</option>
                </select></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">Papel</div></td>
              <td><input name="papel" type="text" id="papel" value="<?=$danfePapel;?>" size="2" maxlength="2"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">Canhoto</div></td>
              <td><select name="canhoto" size="1" id="canhoto">
                      <option value="1" <?=$selCanh1;?>>TRUE</option>
                  <option value="0" <?=$selCanh0;?>>FALSE</option>
                </select></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">Logo</div></td>
              <td><input name="logo" type="text" id="logo" value="<?=$danfeLogo;?>" size="30" maxlength="200"></td>
              <td>&nbsp;</td>
            </tr>
            <tr bgcolor="#999999">
              <td colspan="3"><strong>Configura&ccedil;&atilde;o do email</strong></td>
            </tr>
            <tr>
              <td><div align="right">Emitente</div></td>
              <td><input name="emitente" type="text" id="emitente" value="<?=$mailFROM;?>" size="30" maxlength="100"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">SMTP</div></td>
              <td><input name="smtp" type="text" id="smtp" value="<?=$maillHOST;?>" size="30" maxlength="100"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">USER</div></td>
              <td><input name="user" type="text" id="user" value="<?=$mailUSER;?>" size="30" maxlength="100"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><div align="right">Password</div></td>
              <td><input name="password" type="text" id="password" value="<?=$mailPASS;?>" size="20" maxlength="30"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <td><input name="Gravar" type="submit" id="Gravar" value="Gravar"></td>
              <td>&nbsp;</td>
            </tr>
          </table>
          <div align="center"></div>
        </form></td>
    </tr>
  </table>
</div>
</body>
</html>
