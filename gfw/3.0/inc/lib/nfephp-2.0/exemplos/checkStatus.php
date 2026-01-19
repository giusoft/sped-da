<?php
require_once('../libs/ToolsNFePHP.class.php');

$nfe = new ToolsNFePHP;
header('Content-type: text/html; charset=UTF-8');

$sUF = 'AM;BA;CE;DF;ES;GO;MG;MS;MT;PE;PR;RS;SP';
$sUF = 'BA';
$tpAmb= '2';

$aUF = explode(';',$sUF);

if($tpAmb == 1){
    $sAmb='Produção';
} else {
    $sAmb='Homologação';
}
foreach ($aUF as $UF){
    echo $UF . '[' . $sAmb . '] - ' . '<BR>';
    $resp = $nfe->statusServico($UF,$tpAmb,2);
    echo '<PRE>';
    echo htmlspecialchars($resp);
    echo '</PRE><BR>';
    echo $nfe->errMsg.'<BR>';
    echo '<PRE>';
    echo htmlspecialchars($nfe->soapDebug);
    echo '</PRE><BR>';
    echo $UF . '[' . $sAmb . '] - ' . $resp['xMotivo'] . '<BR>';
    flush();
}
?>
