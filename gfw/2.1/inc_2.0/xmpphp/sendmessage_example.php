<?php

// activate full error reporting
error_reporting(E_ALL);

include 'XMPPHP/XMPP.php';

#Use XMPPHP_Log::LEVEL_VERBOSE to get more logging for error reports
#If this doesn't work, are you running 64-bit PHP with < 5.2.6?
$conn = new XMPPHP_XMPP('189.89.157.205', 5222, 'sistema', 'sistema', 'xmpphp', '189.89.157.205', true, XMPPHP_Log::LEVEL_VERBOSE);

try {
    $conn->useEncryption(false);
    $conn->connect();
    $conn->processUntil('session_start',6);
    $conn->presence();
    $conn->message('giuliano@jabber.giusoft.com.br', 'This is a test message!');
    $conn->disconnect();
} catch(XMPPHP_Exception $e) {
    die($e->getMessage());
}
