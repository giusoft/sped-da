<?
function gs_rss($rdf,$cache,$itens)
{
	gRSS($rdf,$cache,$itens);
}







class HTTPRequest
{
   var $_fp;        // HTTP socket
   var $_url;        // full URL
   var $_host;        // HTTP host
   var $_protocol;    // protocol (HTTP/HTTPS)
   var $_uri;        // request URI
   var $_port;        // port
  
   // scan url
   function _scan_url()
   {
       $req = $this->_url;
      
       $pos = strpos($req, '://');
       $this->_protocol = strtolower(substr($req, 0, $pos));
      
       $req = substr($req, $pos+3);
       $pos = strpos($req, '/');
       if($pos === false)
           $pos = strlen($req);
       $host = substr($req, 0, $pos);
      
       if(strpos($host, ':') !== false)
       {
           list($this->_host, $this->_port) = explode(':', $host);
       }
       else
       {
           $this->_host = $host;
           $this->_port = ($this->_protocol == 'https') ? 443 : 80;
       }
      
       $this->_uri = substr($req, $pos);
       if($this->_uri == '')
           $this->_uri = '/';
   }
  
   // constructor
   function HTTPRequest($url)
   {
       $this->_url = $url;
       $this->_scan_url();
   }
  
   // download URL to string
   function DownloadToString()
   {
       $crlf = "\r\n";
      
       // generate request
       $req = 'GET ' . $this->_uri . ' HTTP/1.0' . $crlf
           .    'Host: ' . $this->_host . $crlf
           .    $crlf;
      
       // fetch
       //$this->_fp = fsockopen(($this->_protocol == 'https' ? 'ssl://' : '') . $this->_host, $this->_port);

		 $this->_fp = fsockopen(($this->_protocol == 'https' ? 'ssl://' : '') . $this->_host, $this->_port,$errno, $errstr, 15);

		 //echo "site: ".($this->_protocol == 'https' ? 'ssl://' : '') . $this->_host. " porta: ".$this->_port. " erro: $errstr<br>";
		//$req="GET ".$this->_uri." HTTP/1.1\r\nAccept: */*\r\nAccept-Language: de-ch\r\nAccept-Encoding: gzip, deflate\r\nUser-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)\r\nHost: ".$this->_host.":".$this->_port."\r\nConnection: Keep-Alive\r\n\r\n";

		//echo "<br>$req<br>";

		fwrite($this->_fp, $req);

		 //stream_set_timeout($this->_fp, 4);
       while(is_resource($this->_fp) && $this->_fp && !feof($this->_fp))
           $response .= fread($this->_fp, 1024);
       fclose($this->_fp);
       // split header and body
       $pos = strpos($response, $crlf . $crlf);
       if($pos === false)
           return($response);
       $header = substr($response, 0, $pos);
       $body = substr($response, $pos + 2 * strlen($crlf));
      
       // parse headers
       $headers = array();
       $lines = explode($crlf, $header);
       foreach($lines as $line)
           if(($pos = strpos($line, ':')) !== false)
               $headers[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos+1));
      
       // redirection?
       if(isset($headers['location']))
       {
           $http = new HTTPRequest($headers['location']);
           return($http->DownloadToString($http));
       }
       else
       {
           return($body);
       }
   }
}


function gRSS($rdf,$cache,$itens,$leiamais=false)
{
/*
//	rss-br-linux.php 1.0
//
//      Augusto Campos (brain@matrix.com.br)
//
//      Para incluir as manchetes do br-linux.org na sua página em PHP,
//      basta copiar este script para o mesmo diretório da sua página e
//      acrescentar a ela uma chamada como a seguinte:
//
//      <? include("rss-br-linux.php"); ?>
//
//      Este script acessa o br-linux.org apenas uma vez a cada 30 minutos,
//      otimizando o tráfego de rede.
//
/////////////////////////////////////////////////////////////////////////
//      Modified from a newsbackend called "freshmeat.cgi"
//
//	Version:	2.0.4
//
//	Author:		Kalle Kiviaho - kivi@chl.chalmers.se
//	Lastmod:	1999-09-09
//	Homepage:	http://swamp.chl.chalmers.se/backends/
//	Customização:
*/
// o que vai aparecer antes de cada item:
//$link_prefix	=	"<tr><td class='noticias'>:: ";

// o que vai aparecer após cada item:
//$link_postfix	=	"</td></tr>";
$link_postfix	=	"<br>";
// o arquivo de cache
$cache_file	=	"/tmp/rss-".$cache.".cache";



// número máximo de notícias que pode ser lido
$max_items	=	$itens;

// frame de destino. se tiver dúvidas, mantenha o valor "_top"
$target		=	"_new";

//	Fim das configurações


//$backend	=	"http://brlinux.linuxsecurity.com.br:8080/noticias/index.rdf";
$backend	= 	$rdf;
$items		=	0;
$time		=	split(" ", microtime());
$cache_time	=	1800;

srand((double)microtime()*1000000);
$cache_time_rnd	=	300 - rand(0, 600);

if (( (!(file_exists($cache_file))) || ((filectime($cache_file) + $cache_time - $time[1]) + $cache_time_rnd < 0) || (!(filesize($cache_file))) ) ){

//$r = new HTTPRequest($backend);
//$s=$r->DownloadToString();




//$s=getcontent("http://br-linux.org", 80, "rss/brlinux-iso.xml");



  $fpread = fopen("/tmp/".$backend, 'r');

  $s="";
	while (!feof ($fpread)) {

		$s.= fgets($fpread, 4096)."\n";

	}

	fclose ($fpread);

	if ($s!="")

	{

	//echo "$backend<br>Dados: $s";
		$fpwrite = fopen($cache_file, 'w');
		if(!$fpwrite) {
			echo "$errstr ($errno)<br>\n";
			exit;
		} else 
		{
			$linhas=split("\n",$s);
			$le=false;
			foreach ($linhas as $buffer)
			{
				$buffer = ltrim(Chop($buffer));
				if (((substr($buffer,0,6) == "<item>")||(substr($buffer,0,9) == "<item rdf")) && ($items < $max_items)) 
				{
					$le=true;
				}
				if ($le)
				{
					if (substr($buffer,0,7) == "<title>")
					{
						$title = ereg_replace( "<title>", "", $buffer );
						$title = ereg_replace( "</title>", "", $title );
					}
					if (substr($buffer,0,13) == "<description>")
					{
						$description= ereg_replace( "<description>", "", $buffer );
						$description= ereg_replace( "</description>", "", $description);
					}
					if (substr($buffer,0,6) == "<link>")
					{
						$link = ereg_replace( "<link>", "", $buffer );
						$link= ereg_replace( "</link>", "", $link );
					}
				}
				if ((substr($buffer,0,7) == "</item>") && $le)
				{
					$le=false;
					//fputs($fpwrite, "$link_prefix<spam class='noticias' HREF=\"$link\" TARGET=\"$target\">$title</spam><br>$description $link_postfix");
					$description=str_replace("&lt;i&gt;","",$description);
					$description=str_replace("&lt;/i&gt;","",$description);
					$description=str_replace("&amp;ldquo;","",$description);
					$description=str_replace("/","/ ",$description);
					$description=str_replace("?","? ",$description);
					$description=str_replace("/ / ","//",$description);
					$description=substr($description,0,150)."...";
					if ($leiamais==true)
					{
						fputs($fpwrite, "$link_prefix<font class='noticias'>$title</font><font class='noticias_corpo'><br>$description</font>$link_postfix");
						fputs($fpwrite, "<br style='line-height:4px '>");
						fputs($fpwrite, "<div align='right'><img alt='' src='images/arr_gr.jpg' class='abs'>&nbsp;&nbsp;<a href='$link' target='$target' class='green'>leia mais </a></div><br style='line-height:4px '>");
						fputs($fpwrite, "<div style=\"height:1px; background-image:url(images/dot.gif) \"><img alt=\"\" src=\"images/spacer.gif\"></div>");
					} else
					{
						fputs($fpwrite, "$link_prefix <a href='$link' target='$target' class='noticias'>$title</a>$link_postfix");
					}

					$items++;
				}
			}
		}
	//	fputs($fpwrite, "$link_prefix<A class=link HREF=http://br-linux.org/ TARGET=\"$target\">Mais em br-linux.org</A>$link_postfix");
		fclose($fpwrite);

	}
}
if (file_exists($cache_file)) {
    $sai=file_get_contents($cache_file);
//	include($cache_file);
}
return($sai);
}
?>
