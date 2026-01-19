<?

class AstMan {

 var $socket;
 var $error;
 
 function AstMan()
 {
   $this->socket = FALSE;
   $this->error = "";
 } 

 function Login($host="localhost", $username="admin", $password="amp111"){
   
   $this->socket = @fsockopen($host,"5038", $errno, $errstr, 1); 
   if (!$this->socket) {
     $this->error =  "Could not connect - $errstr ($errno)";
     return FALSE;
   }else{
     stream_set_timeout($this->socket, 1); 
 
     $wrets = $this->Query("Action: Login\r\nUserName: $username\r\nSecret: $password\r\nEvents: off\r\n\r\n"); 

     if (strpos($wrets, "Message: Authentication accepted") != FALSE){
       return true;
     }else{
		$this->error = "Could not login - Authentication failed";
		fclose($this->socket); 
		$this->socket = FALSE;
    return FALSE;
     }
   }
 }
 
 function Logout(){
   if ($this->socket){
     fputs($this->socket, "Action: Logoff\r\n\r\n"); 
     while (!feof($this->socket)) { 
       $wrets .= fread($this->socket, 8192); 
     } 
     fclose($this->socket); 
     $this->socket = "FALSE";
   }
  return; 
 }

 function Query($query,$fim="\r\n"){
   $wrets = "";
	$max=2000;
   if ($this->socket === FALSE)
     return FALSE;
     
   fputs($this->socket, $query); 
	$cnt=0;
   do
   {
		$cnt++;
     $line = fgets($this->socket, 4096);
     $wrets .= $line;
     $info = stream_get_meta_data($this->socket);
   }while (((($fim=="\r\n") && $line != $fim && $infotimed_out>'timed_out' == false ) || (($fim!="\r\n") && (strpos($line,$fim)===false)))&& ($cnt<$max) );
   return $wrets;
 }
 
 function GetError(){
   return $this->error;
 }
 
function Originate($channel,$context,$exten)
{
	$wrets = $this->Query("Action: Originate\r\nChannel: $channel\r\nContext: $context\r\nExten: $exten\r\nPriority: 1\r\n\r\n");
}

function SipPeers()
{
	$wrets = $this->Query("Action: Sippeers\r\n\r\n","PeerlistComplete");
	$peers="";
	$cnt=-1;
	$ar=split("\r",$wrets);
	foreach ($ar as $lin)
	{
		$com=split(":",$lin);
		$com[0]=trim($com[0]);
		$com[1]=trim($com[1]);
		if ($com[1]=="PeerEntry") // início
		{
			if ($cnt>=0)
			{
				$peers[]=array($nome,$tipo,$ip,$status);
			}
			$cnt++;
		}
		if ($com[0]=="ObjectName") $nome=$com[1];
		if ($com[0]=="ChanObjectType") $tipo=$com[1];
		if ($com[0]=="IPaddress") $ip=$com[1];
		if ($com[0]=="Status") $status=$com[1];
	}
	$peers[]=array($nome,$tipo,$ip,$status);
	return($peers);
}

function IaxPeers()
{
	$wrets = $this->Query("Action: Iaxpeers\r\n\r\n","peers");
	$peers="";
	$cnt=-1;
	$ar=split("\r",$wrets);
	foreach ($ar as $lin)
	{
		$com=split(":",$lin);
		$com[0]=trim($com[0]);
		$com[1]=trim($com[1]);
		if ($com[1]=="PeerEntry") // início
		{
			if ($cnt>=0)
			{
				$peers[]=array($nome,$tipo,$ip,$status);
			}
			$cnt++;
		}
		if ($com[0]=="ObjectName") $nome=$com[1];
		if ($com[0]=="ChanObjectType") $tipo=$com[1];
		if ($com[0]=="IPaddress") $ip=$com[1];
		if ($com[0]=="Status") $status=$com[1];
	}
	return($peers);
}

function Zapchannels()
{
	$wrets = $this->Query("Action: ZapShowChannels\r\n\r\n","ZapShowChannelsComplete");
	$peers="";
	$cnt=-1;
	$ar=split("\r",$wrets);
	foreach ($ar as $lin)
	{
		$com=split(":",$lin);
		$com[0]=trim($com[0]);
		$com[1]=trim($com[1]);
		if ($com[1]=="ZapShowChannels") // início
		{
			if ($cnt>=0)
			{
				$peers[]=array($nome,$tipo,$ip,$status);
			}
			$cnt++;
		}
		if ($com[0]=="Channel") $nome=$com[1];
		if ($com[0]=="Signalling") $tipo=$com[1];
		if ($com[0]=="Context") $ip=$com[1];
		if ($com[0]=="Alarm") $status=$com[1];
	}
	$peers[]=array($nome,$tipo,$ip,$status);
	return($peers);
}

function ExtensionState($exten)
{
	$sai=-1;
	$wrets = $this->Query("Action: ExtensionState\r\nExten: $exten\r\n\r\n","Status:");
	$ar=split("\r",$wrets);
	foreach ($ar as $lin)
	{
		$com=split(":",$lin);
		$com[0]=trim($com[0]);
		$com[1]=trim($com[1]);
		if ($com[0]=="Status") $sai=$com[1];
	}
	return($sai);
}

function GetDB($family, $key){
   $value = "";
 
   $wrets = $this->Query("Action: Command\r\nCommand: database get $family $key\r\n\r\n");
 
   if ($wrets){
     $value_start = strpos($wrets, "Value: ") + 7;
     $value_stop = strpos($wrets, "\n", $value_start);
    if ($value_start > 8){
       $value = substr($wrets, $value_start, $value_stop - $value_start);
     }
  }
   return $value;
 }
 
 function PutDB($family, $key, $value){
   $wrets = $this->Query("Action: Command\r\nCommand: database put $family $key $value\r\n\r\n");
 
  if (strpos($wrets, "Updated database successfully") != FALSE){
  return TRUE;
   }
   $this->error =  "Could not updated database";
   return FALSE;
 }
 
 function DelDB($family, $key){
   $wrets = $this->Query("Action: Command\r\nCommand: database del $family $key\r\n\r\n");

  if (strpos($wrets, "Database entry removed.") != FALSE){
  return TRUE;
   }
   $this->error =  "Database entry does not exist";
   return FALSE;
 }
 
 
 function GetFamilyDB($family){
   $wrets = $this->Query("Action: Command\r\nCommand: database show $family\r\n\r\n");
   if ($wrets){
     $value_start = strpos($wrets, "Response: Follows\r\n") + 19;
     $value_stop = strpos($wrets, "--END COMMAND--\r\n", $value_start);
    if ($value_start > 18){
       $wrets = substr($wrets, $value_start, $value_stop - $value_start);
     }
     $lines = explode("\n", $wrets);
     foreach($lines as $line){
       if (strlen($line) > 4){
         $value_start = strpos($line, ": ") + 2;
         $value_stop = strpos($line, " ", $value_start);
        $key = trim(substr($line, strlen($family) + 2, strpos($line, " ") - strlen($family) + 2));
         $value = trim(substr($line, $value_start));
       }
     }
     return $value;
  }
   return FALSE;
 }   
} 
?>