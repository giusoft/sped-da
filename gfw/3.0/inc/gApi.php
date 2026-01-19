<?php

/**
 * API para concentrar todas as chamadas a APIs de fornecedores externos
 */
class gApi
{

	private $api_secret='';
	private $api_key='';
	private $qid='';
	private $timestamp='';
	public $lastResponse='';
	public $mac='';

	/** Construtor
	  @param Json key chave de acesso
	  @param Json secret senha
	  @return void
	 */
	function __construct($json)
	{
		$mtz=cssDecode($json);
		$this->api_key=$mtz['key'];
		$this->api_secret=$mtz['secret'];
		$this->mac=$mtz['mac'];
	}

	/** =========================================================================
	 *  GOOGLE Translate API
	 *  https://code.google.com/apis
	 *  Responsável pela tradução de textos
	 *  =========================================================================
	 */

	/** Traduz texto pelo Google Translate
	 *
	 * @param string $text Texto a ser traduzido
	 * @param string $source Língua de origem
	 * @param string $target Língua de destino
	 * @return string Texto traduzido
	 */
	function googleTranslate($text,$source="pt_BR",$target="en")
	{
		$url="https://www.googleapis.com/language/translate/v2?key=".$this->api_key."&source=$source&target=$target&q=".urlencode($text);
		gLog($url);
		$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$response=trim(curl_exec($ch));
		gLog("==> Google res: $response");
		if (strpos($response,"translatedText")!==false)
		{
			$response=trim(str_replace("}","",str_replace("]","",$response)));
			$response=substr($response,strpos($response,"translatedText")+18);
			$response=substr($response,0,strlen($response)-1);
		} else
		{
			$response="";
		}
		return($response);
	}


    /**
     * Obtém áudio de um texto
     *
     * @param string $text Texto a ser traduzido
     * @param string $target Idioma
     * @return string Texto traduzido
     */
    function googleVoice($text, $target = "en", $filename = "/tmp/voice.mp3")
    {
      $url='http://translate.google.com/translate_tts?ie=UTF-8&tl='.$target.'&q=' . urlencode($text) . '&client=t';
      gLog($url);
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
      curl_setopt( $ch, CURLOPT_REFERER, 'http://translate.google.com/');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
      curl_setopt($ch, CURLOPT_HEADER, false);
      $response = curl_exec($ch);
      file_put_contents($filename, $response);
      //return($response);
    }

	/** =========================================================================
	 *  iQEngines
	 *  https://www.iqengines.com/
	 *  Responsável pelo reconhecimento de imagens e códigos de barra
	 *  =========================================================================
	 */

	function setKey($key)
	{
		$this->api_key=$key;
	}

	function setSecret($secret)
	{
		$this->api_secret=$secret;
	}

	function setQid($qid)
	{
		$this->qid=$qid;
	}
	function setTimestamp($timestamp)
	{
		$this->timestamp=$timestamp;
	}

	/** Submete imagem e obtém texto com o reconhecimento (tenta 5 vezes)
	 *
	 * @param filePath $file Caminho do arquivo de imagem (tem que estar local)
	 * @return json Resultado da consulta
	 */
	function CamFind_process($file, $type="image/png", $lat,$lon)
	{
		$fname=tempnam(sys_get_temp_dir(), 'camfind-');
		$fname.=".".str_replace("image/","",$type);
		move_uploaded_file($file,$fname);
		$file=$fname;
		//copy($file,$fname);
		$this->qid='';
		// Submete imagem para avaliação
		$response=$this->CamFind_query($file,$type,$lat,$lon);
		gLog("===> CamFind: Query $file $response");
		if ($this->qid<>'')
		{
			$tentativa=0;
			$delay=5;
			$ok=false;
			// Tenta 5 vezes...
			while ($tentativa<20)
			{
				$tentativa++;
				sleep($delay);
				$delay=3;
				$response=$this->CamFind_result();
				$obj=json_decode($response);
				gLog("===> CamFind: Result - Tentativa $tentativa, Delay $delay: $response");
				if ($obj->status=="completed")
				{
					$ok=true;
					$tentativa=20;
					$response='{"labels": "'.$obj->name.'"}';
				}
			}
			if (!$ok)
				$response='{"data": {"error": "timeout", "id": "'.$this->qid.'"}}';
			gLog("===> CamFind: Process - $response");
		} else
		{
			$response='{"data": {"error": "cant be blank"}}';
		}
		@unlink($file);
		return($response);
	}



	/** Submete imagem para reconhecimento
	 *
	 * @param filePath $file Caminho do arquivo de imagem (tem que estar local)
	 * @return json Resultado da operação
	 */
	function CamFind_query($file, $type="image/png", $lat=0,$lon=0)
	{
		date_default_timezone_set('UTC');
		$url="https://camfind.p.mashape.com/image_requests";

		$filename=basename($file);
		$img='@'.$file;
/*
		$maxLargura=400;
		$maxAltura=400;
		list($largura, $altura) = getimagesize($arq);
		if ($largura>$altura)
		{
			$nAltura=intval(($maxLargura*$altura)/$largura);
			$nLargura=$maxLargura;
		} else
		{
			$nLargura=intval(($maxAltura*$largura)/$altura);
			$nAltura=$maxAltura;
		}

		// Converte pra jpg e, se necessário pra um tamanho menor
		if (stripos($type,"png")!==false)
		{
			$source = imagecreatefrompng($file);
			if (($largura>$maxLargura) || ($altura>$maxAltura))
			{
				$thumb = imagecreatetruecolor($nLargura, $nAltura);
				imagecopyresized($thumb, $source, 0, 0, 0, 0, $nLargura, $nAltura, $largura, $altura);
				imagejpeg($thumb,$file,75);
			} else
				imagejpeg($source,$file,75);
		} else
		{
			if (($largura>$maxLargura) || ($altura>$maxAltura))
			{
				$source = imagecreatefromjpeg($file);
				$thumb = imagecreatetruecolor($nLargura, $nAltura);
				imagecopyresized($thumb, $source, 0, 0, 0, 0, $nLargura, $nAltura, $largura, $altura);
				imagejpeg($thumb,$file);
			}
		}
		//copy($file,"/tmp/arquivo.jpg");
*/
		$fields['image_request[locale]']='en_US';
		$fields['image_request[image]']=$img;
		if (($lat+$lon)!=0)
		{
			$fields['image_request[latitude]']=$lat;
			$fields['image_request[longitude]']=$lon;
		}
		$fields_string="";
		foreach ($fields as $key=>$value)
		{
			$fields_string .= $key.'='.$value.'&';
		}
		rtrim($fields_string, '&');
//gLog("====>api 1> fields: $fields_string");
		$ch=curl_init($url);
		//curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_HTTPHEADER,array (
				"X-Mashape-Authorization: ".$this->api_key
				));
		// header: X-Mashape-Authorization: xmhvqGtD86zCrDLDKwhTXB8Aol0odz32
		$response=curl_exec($ch);
//gLog("====> api 2> response: $response");
		$obj=json_decode($response);
		$this->qid=$obj->token;
		$this->lastResponse=$response;
		return($response);
	}

	/** Busca resultado do reconhecimento de uma imagem já submetida
	 *
	 * @return json Resultado da consulta
	 */
	function CamFind_result($qid="")
	{
		date_default_timezone_set('UTC');
		$url="https://camfind.p.mashape.com/image_responses/".$this->qid;
		$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,array (
				"X-Mashape-Authorization: ".$this->api_key
				));

		$response=curl_exec($ch);
		return($response);
	}




	/** Submete imagem e obtém texto com o reconhecimento (tenta 5 vezes)
	 *
	 * @param filePath $file Caminho do arquivo de imagem (tem que estar local)
	 * @return json Resultado da consulta
	 */
	function iQEngines_process($file, $type="image/png")
	{
		$fname=tempnam('/tmp','iq-');
		copy($file,$fname);
		$file=$fname;

		// Submete imagem para avaliação
		$response=$this->iQEngines_query($file,$type);
		gLog("===> iQEngines: Query $file $response");
		if ($response=='{"data": {"error": 0}}')
		{
			$tentativa=0;
			$delay=5;
			$ok=false;
			// Tenta 5 vezes...
			while ($tentativa<20)
			{
				$tentativa++;
				sleep($delay);
				$delay=3;
				$response=$this->iQEngines_result();
				gLog("===> iQEngines: Result - Tentativa $tentativa, Delay $delay: $response");
				if (strpos($response, "labels")!==false)
				{
					$ok=true;
					$tentativa=20;
				}
			}
			if (!$ok)
				$response='{"data": {"error": "timeout", "id": "'.$this->qid.'"}}';
			gLog("===> iQEngines: Process - $response");
		}
		@unlink($file);
		return($response);
	}

	/** Submete imagem para reconhecimento
	 *
	 * @param filePath $file Caminho do arquivo de imagem (tem que estar local)
	 * @return json Resultado da operação
	 */
	function iQEngines_query($file, $type="image/png")
	{
		date_default_timezone_set('UTC');
		$url="http://api.iqengines.com/v1.2/query/";

		$filename=basename($file);
		$img='@'.$file;

		$maxLargura=400;
		$maxAltura=400;
		list($largura, $altura) = getimagesize($arq);
		if ($largura>$altura)
		{
			$nAltura=intval(($maxLargura*$altura)/$largura);
			$nLargura=$maxLargura;
		} else
		{
			$nLargura=intval(($maxAltura*$largura)/$altura);
			$nAltura=$maxAltura;
		}

		// Converte pra jpg e, se necessário pra um tamanho menor
		if (stripos($type,"png")!==false)
		{
			$source = imagecreatefrompng($file);
			if (($largura>$maxLargura) || ($altura>$maxAltura))
			{
				$thumb = imagecreatetruecolor($nLargura, $nAltura);
				imagecopyresized($thumb, $source, 0, 0, 0, 0, $nLargura, $nAltura, $largura, $altura);
				imagejpeg($thumb,$file,75);
			} else
				imagejpeg($source,$file,75);
		} else
		{
			if (($largura>$maxLargura) || ($altura>$maxAltura))
			{
				$source = imagecreatefromjpeg($file);
				$thumb = imagecreatetruecolor($nLargura, $nAltura);
				imagecopyresized($thumb, $source, 0, 0, 0, 0, $nLargura, $nAltura, $largura, $altura);
				imagejpeg($thumb,$file);
			}
		}
		//copy($file,"/tmp/arquivo.jpg");

		$json='1';
		$this->timestamp=date('YmdHis');

		$raw_string='api_key'.$this->api_key.'img'.$filename.'json'.$json.'time_stamp'.$this->timestamp;
		$api_sig=hash_hmac("sha1", $raw_string, $this->api_secret, false);
		$this->qid=$api_sig;

		$fields=array(
			'api_key'=>$this->api_key,
			'img'=>$img,
			'api_sig'=>$api_sig,
			'time_stamp'=>$this->timestamp,
			'json'=>$json,
			'device_id' => $mac
		);

		//$fields['img']=$fields['img']

		$fields_string="";
		foreach ($fields as $key=>$value)
		{
			$fields_string .= $key.'='.$value.'&';
		}
		rtrim($fields_string, '&');
		$ch=curl_init($url);
		//gLog("===> iQEngines: URL: ".$url.$fields_string);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$response=curl_exec($ch);

		$this->lastResponse=$response;
		return($response);
	}

	/** Busca resultado do reconhecimento de uma imagem já submetida
	 *
	 * @return json Resultado da consulta
	 */
	function iQEngines_result($qid="")
	{
		date_default_timezone_set('UTC');
		$url="http://api.iqengines.com/v1.2/result/";

		if ($qid<>"")
		{
			$this->qid=$qid;
		}
		$this->timestamp=date('YmdHis');

		$img='@'.realpath($file);
		$filename=basename($file);

		$json='1';
		$raw_string='api_key'.$this->api_key.'json'.$json.'qid'.$this->qid.'time_stamp'.$this->timestamp;
		$api_sig=hash_hmac("sha1", $raw_string, $this->api_secret, false);

		$fields=array(
			'api_key'=>$this->api_key,
			'api_sig'=>$api_sig,
			'qid'=>$this->qid,
			'time_stamp'=>$this->timestamp,
			'json'=>$json
		);
		$fields_string="";
		foreach ($fields as $key=>$value)
		{
			$fields_string .= $key.'='.$value.'&';
		}
		rtrim($fields_string, '&');

		$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$response=curl_exec($ch);
		return($response);
	}

	function iQEngines_update()
	{
		date_default_timezone_set('UTC');

		$url="http://api.iqengines.com/v1.2/update/";

		$timestamp=date('YmdHis');
		$json='1';

		$raw_string='api_key'.$this->api_key.'json'.$json.'time_stamp'.$this->timestamp;
		$api_sig=hash_hmac("sha1", $raw_string, $this->api_secret, false);

		$fields=array(
			'api_key'=>$this->api_key,
			'api_sig'=>$api_sig,
			'time_stamp'=>$this->timestamp,
			'json'=>$json
		);

		$fields_string="";
		foreach ($fields as $key=>$value)
		{
			$fields_string .= $key.'='.$value.'&';
		}
		rtrim($fields_string, '&');

		$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$response=curl_exec($ch);

		$this->lastResponse=$response;
		return($response);
	}

}

?>
