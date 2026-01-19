<?php
include $gPathLib.'xmpphp-0.1rc2-r77/XMPPHP/XMPP.php';

function gSendMessage($msg,$to="")
{
	if ($to=="")
		$to="all@broadcast.".gVar("jabber.server");
	$conn = new XMPPHP_XMPP(gVar("jabber.server"), 5222, gVar("jabber.user"), gVar("jabber.password"), 'xmpphp', gVar("jabber.server"), $printlog=False, $loglevel=LOGGING_INFO);
	$conn->connect();
	$conn->processUntil('session_start');
	$conn->message($to, $msg);
	$conn->disconnect();
}


?>
