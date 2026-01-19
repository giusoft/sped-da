<?php

use Aws\Credentials\Credentials;
use Aws\Rekognition\RekognitionClient;

include_once $gPathLib . gVar("lib.facebook");
include_once $gPathLib . ("twitteroauth/twitteroauth.php");
if ($gPathLib == "") {
   $gPathLib="/var/www/html/gfw/4.0/inc/lib";
}

require_once $gPathLib."/amazon/aws-autoloader.php";

/**
* Classe de conexão com o twitter
*/
class gAmazon
{

   public $api_key = '';
   public $api_secret = '';
   public $credentials = '';
   public $options;

   /**
   * Construtor
   * @param Json key chave de acesso
   * @param Json secret senha
   * @param Json oauth_calback url de redirecionamento
   * @return void
   */
   public function __construct($json = "")
   {
      $mtz = cssDecode($json);
      $this->api_key = $mtz['key'] != '' ? $mtz['key'] : gVar('twitter.key');
      $this->api_secret = $mtz['secret'] != '' ? $mtz['secret'] : gVar('twitter.secret');

      // $this->credentials = new Aws\Credentials\Credentials('AKIA2MK27B3GVPAHSRBT', 'jCqnncKHJvCMNzdtEeBmg3nrjCB3xlEsNnt6+Z6/');
      $this->credentials = new Credentials('AKIA2MK27B3G46LH3HUM', 'Lg+p/X7tKejYYuCli46CpDm59loYbD1wRH2H2tas');
      $this->options = [
         'version' => 'latest',
         'region' => 'us-east-1',
         'credentials' => $this->credentials,
         'httpOptions' => ['timeout' => 10000]
      ];

   }


   public function rekognition($photo)
   {
      $rekognition = new RekognitionClient($this->options);

      // Get local image
      $fp_image = fopen($photo, 'r');
      $image = fread($fp_image, filesize($photo));
      fclose($fp_image);

      // Call DetectFaces
      try {
         $result = $rekognition->DetectFaces(
            [
               'Image' => [
                  'Bytes' => $image
               ],
               'Attributes' => [
                  'ALL'
               ]
            ]
         );
      } catch(Exception $e) {

      }

      return($result);
   }


   public function detectText($photo)
   {
      $rekognition = new RekognitionClient($this->options);

      // Get local image
      $fp_image = fopen($photo, 'r');
      $image = fread($fp_image, filesize($photo));
      fclose($fp_image);

      // Call Detect text
      try {
         $result = $rekognition->detectText(
            [
               'Image' => [
                  'Bytes' => $image
               ],
               'Attributes' => [
                  'ALL'
               ]
            ]
         );
      } catch(Exception $e) {

      }

      return($result);
   }


   public function compare($photo1, $photo2)
   {
      $compare = new RekognitionClient($this->options);

      $fp_image = fopen($photo1, 'r');
      $image1   = fread($fp_image, filesize($photo1));
      fclose($fp_image);

      $fp_image = fopen($photo2, 'r');
      $image2 = fread($fp_image, filesize($photo2));
      fclose($fp_image);
      /**
       * Caso lance exceção durante o método "compareFaces", a variável $result não terá valor
       * O da variável $result é realizado pela função biometriafacial, no arquivo telemetria
       */
      try {
         $result = $compare->compareFaces([
             'SimilarityThreshold' => 80,
             'SourceImage' => [
                 'Bytes' => $image1,
             ],
             'TargetImage' => [ // REQUIRED
                 'Bytes' => $image2,
             ],
         ]);
      } catch(Exception $e) {

      }

      return $result;
   }

   public function detectLabels($photo1)
   {
      $compare = new RekognitionClient($this->options);

      $fp_image = fopen($photo1, 'r');
      $image1   = fread($fp_image, filesize($photo1));
      fclose($fp_image);

      /**
       * Caso lance exceção durante o método "compareFaces", a variável $result não terá valor
       * O da variável $result é realizado pela função biometriafacial, no arquivo telemetria
       */
      try {
         $result = $compare->detectLabels(
            [
               'Image' => [
                  'Bytes' => $image1,
               ],
               'MaxLabels' => 30
            ]
         );
      } catch(Exception $e) {

      }

      return $result;
   }

   public function detectFaces($photo1)
   {
      $compare = new RekognitionClient($this->options);

      $fp_image = fopen($photo1, 'r');
      $image1 = fread($fp_image, filesize($photo1));
      fclose($fp_image);
      /**
       * Caso lance exceção durante o método "compareFaces", a variável $result não terá valor
       * O da variável $result é realizado pela função biometriafacial, no arquivo telemetria
       */
      try {
         $result = $compare->detectFaces(
            [
               'Attributes' =>  [
                  "ALL"
               ],
               'Image' => [
                  'Bytes' => $image1,
               ]
            ]
         );
      } catch(Exception $e) {

      }

      return $result;
   }

}

/**
* Classe de conexão com o twitter
*/
class gTwitter
{

   public $connection;
   public $api_key = '';
   public $api_secret = '';
   public $oauth_calback = '';
   public $request_token = '';
   public $token = '';

   /**
   * Construtor
   * @param Json key chave de acesso
   * @param Json secret senha
   * @param Json oauth_calback url de redirecionamento
   * @return void
   */
   public function __construct($json = "")
   {
      $mtz = cssDecode($json);
      $this->api_key = $mtz['key'] != '' ? $mtz['key'] : gVar('twitter.key');
      $this->api_secret = $mtz['secret'] != '' ? $mtz['secret'] : gVar('twitter.secret');
      $this->oauth_calback = $mtz['oauth_calback'] != '' ? $mtz['oauth_calback'] : gVar('twitter.oauth_calback');

   }

   /**
   * Envia para a tela de login do twitter
   */
   public function connect(): void
   {
      $this->connection = new TwitterOAuth($this->api_key, $this->api_secret);
      $this->request_token = $this->connection->getRequestToken($this->oauth_calback);
      gLog("===>> Request token:  ".$this->request_token);
      if ($this->request_token) {
         $this->token = $this->request_token['oauth_token'];
         gLog("===>> Salvando tokens em SESSION (".$this->token." - ".$this->request_token['oauth_token_secret'].")");
         $_SESSION['requestToken'] = $this->token;
         $_SESSION['requestTokenSecret'] = $this->request_token['oauth_token_secret'];
         session_write_close();
         switch($this->connection->http_code) {
            case 200:
               $url = $this->connection->getAuthorizeURL($this->token);
               gLog("===>> Abrindo twitter: ".$url."&g=index");
               //redirect to Twitter .
               header('Location: ' . $url.'&g=index');
               break;

            default:
               echo "Connection with twitter Failed";
               break;
         }
      } else { //error receiving request token
         echo "Error Receiving Request Token";
      }
   }
}

/**
* Facebook API
*  https://developers.facebook.com/docs/reference/php
* Responsável pelo login/logout no facebook
*
*/
class gFacebook
{

   public $fbUserProfile;
   public $fbError;
   public $api_key = '';
   public $api_secret = '';
   public $fb;
   public $fbUserId = 0;
   public $dbUserProfile = '';

   /**
   * Construtor
   * @param Json key chave de acesso
   * @param Json secret senha
   * @return void
   */
   public function __construct($json = "")
   {
      $mtz = cssDecode($json);
      $this->api_key = $mtz['key'] != '' ? $mtz['key'] : gVar('facebook.key');
      $this->api_secret = $mtz['secret'] != '' ? $mtz['secret'] : gVar('facebook.secret');

      $config = [
         'appId' => $this->api_key,
         'secret' => $this->api_secret,
         'fileUpload' => false, // optional
         'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
      ];

      if (gVar("lib.facebook") == 'facebook-php-sdk-master/src/facebook.php') {
         $this->fb = new Facebook($config);
      } else {
         // API v.5
         $this->fb = new Facebook\Facebook($config);
      }
   }


   /**
   * Redireciona o usuário para acessar Facebook e autoriza a aplicação.
   * Parametro scope é a lista de permissões solicitadas ao usuário.
   * @return type
   */
   public function getLoginUrl()
   {
      if (gVar("lib.facebook") == 'facebook-php-sdk-master/src/facebook.php') {
         return($this->fb->getLoginUrl(['scope' => 'user_birthday, email']));
      }

      return;
   }


   /**
   * Retorna o objeto do usuario logado
   * @return type
   */
   public function getProfile()
   {
      if (gVar("lib.facebook") == 'facebook-php-sdk-master/src/facebook.php') {
         $this->fbUserId = $this->fb->getUser();
         if ($this->fbUserId) {
            try {
               $this->fbUserProfile = $this->fb->api('/me', 'GET');
               $sai = $this->fbUserProfile;
            } catch (FacebookApiException $e) {
               $this->fbError = $e->getMessage();
               $sai = false;
            }
         }
      }

      return($sai);
   }


   /**
   * Salva a foto do peril do usuario no servidor.
   *
   * O nome do arquivo de foto é o ID do facebook do usuário.
   * É passado como parametro o tamanho pré-definido da foto.
   *
   * Referencia: https://developers.facebook.com/docs/graph-api/reference/user/picture/
   * @param string $type Tamanho pré-defindo de imagem. Tipos suportados: square,small,normal,large
   * @return boolean
   */
   public function saveProfilePicture($type = 'normal'): ?bool
   {
      if (gVar("lib.facebook") == 'facebook-php-sdk-master/src/facebook.php') {
         $this->fbUserId = $this->fb->getUser();
         if ($this->fbUserId) {
            try {
               $img = file_get_contents('https://graph.facebook.com/' . $this->fbUserId . "/picture?type=$type");
               $file = 'wcr/pub/img/photos/' . $this->fbUserId . '.jpg';
               file_put_contents($file, $img);
            } catch (FacebookApiException $e) {
               $this->fbError = $e->getMessage();
               return false;
            }
         }
      }

   }


   public function logout(): void
   {
      if (gVar("lib.facebook") == 'facebook-php-sdk-master/src/facebook.php') {
         $this->fb->destroySession();
      }
   }

   /**
   * Gera HTML para botão "Like/Share"
   */
   public function jsLikeButton(): string
   {
      return('<div class="fb-like" data-share="true" data-width="450" data-show-faces="true"></div>');
   }

   /**
   * Gera Script Javascript necessário para funcionar o botão "Like/Share"
   */
   public function jsLikeButtonScript(): string
   {
      return('
         window.fbAsyncInit = function() {
            FB.init({
               appId      : \''.gVar("facebook.key").'\',
               xfbml      : true,
               version    : \'v2.5\'
            });
         };

         (function(d, s, id){
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) {return;}
            js = d.createElement(s); js.id = id;
            js.src = "//connect.facebook.net/en_US/sdk.js";
            fjs.parentNode.insertBefore(js, fjs);
         }(document, \'script\', \'facebook-jssdk\'));
      ');

   }
}

/**
* API para concentrar todas as chamadas a APIs de fornecedores externos
*/
class gApi
{

   private string $api_secret = '';
   private $api_key = '';
   private string $qid = '';
   private $timestamp = '';
   public $lastResponse = '';
   public $mac = '';
   public $fb;
   public $fbUserId = 0;
   public $fbUserProfile = '';
   public $fbError = '';

   /**
   * Construtor
   * @param Json key chave de acesso
   * @param Json secret senha
   * @return void
   */
   public function __construct($json)
   {
      $mtz = cssDecode($json);
      $this->api_key = $mtz['key'];
      $this->api_secret = $mtz['secret'];
      $this->mac = $mtz['mac'];
   }
   /** =========================================================================
   *  GOOGLE Translate API
   *  https://code.google.com/apis
   *  Responsável pela tradução de textos
   *  =========================================================================
   */


   /**
   * Traduz texto pelo Google Translate
   *
   * @param string $text Texto a ser traduzido
   * @param string $source Língua de origem
   * @param string $target Língua de destino
   * @return string Texto traduzido
   */
   public function googleTranslate($text, $source = "pt_BR", $target = "en")
   {
      $s = $source==$target ? "" : "&source=$source";

      $url = "https://www.googleapis.com/language/translate/v2?key=" . $this->api_key . "$s&target=$target&q=" . urlencode($text);
      //gLog($url);
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
      curl_setopt($ch, CURLOPT_HEADER, false);
      $response = trim(curl_exec($ch));
      //gLog("==> Google res: $response");

      if (str_contains($response, "translatedText")) {
         $resp = json_decode($response);
         return $resp->data->translations[0]->translatedText;
      }

      return("");
   }


   /**
   * Obtém áudio de um texto
   *
   * @param string $text Texto a ser traduzido
   * @param string $target Idioma
   * @return string Texto traduzido
   */
   public function googleVoice($text, $target = "en", $filename = "/tmp/voice.mp3"): void
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
   public function setKey($key): void
   {
      $this->api_key = $key;
   }


   public function setSecret($secret)
   {
      $this->api_secret = $secret;
   }


   public function setQid($qid): void
   {
      $this->qid = $qid;
   }


   public function setTimestamp($timestamp)
   {
      $this->timestamp = $timestamp;
   }


   /**
   * Submete imagem e obtém texto com o reconhecimento (tenta 5 vezes)
   *
   * @param filePath $file Caminho do arquivo de imagem (tem que estar local)
   * @return json Resultado da consulta
   */
   public function CamFind_process($file, $type = "image/png", $lat, $lon): string
   {
      $fname = tempnam(sys_get_temp_dir(), 'camfind-');
      $fname .= "." . str_replace("image/", "", $type);
      move_uploaded_file($file, $fname);
      $file = $fname;
      //copy($file,$fname);
      $this->qid = '';
      // Submete imagem para avaliação
      $response = $this->CamFind_query($file, $type, $lat, $lon);
      gLog("===> CamFind: Query $file $response");

      if ($this->qid !== '') {
         $tentativa = 0;
         $delay = 5;
         $ok = false;
         // Tenta 5 vezes...
         while($tentativa < 20) {
            $tentativa++;
            sleep($delay);
            $delay = 3;
            $response = $this->CamFind_result();
            $obj = json_decode($response);
            gLog("===> CamFind: Result - Tentativa $tentativa, Delay $delay: $response");
            if ($obj->status == "completed") {
               $ok = true;
               $tentativa = 20;
               $response = '{"labels": "' . $obj->name . '"}';
            }
         }
         if (!$ok) {
            $response = '{"data": {"error": "timeout", "id": "' . $this->qid . '"}}';
         }

         gLog("===> CamFind: Process - $response");
      } else {
         $response = '{"data": {"error": "cant be blank"}}';
      }

      @unlink($file);
      return($response);
   }


   /**
   * Submete imagem para reconhecimento
   *
   * @param filePath $file Caminho do arquivo de imagem (tem que estar local)
   * @return json Resultado da operação
   */
   public function CamFind_query($file, $type = "image/png", $lat = 0, $lon = 0): bool|string
   {
      date_default_timezone_set('UTC');
      $url = "https://camfind.p.mashape.com/image_requests";
      // $filename = basename($file);
      $img = '@' . $file;
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
      $fields['image_request[locale]'] = 'en_US';
      $fields['image_request[image]'] = $img;

      if (($lat + $lon) != 0) {
         $fields['image_request[latitude]'] = $lat;
         $fields['image_request[longitude]'] = $lon;
      }

      $fields_string = "";
      foreach ($fields as $key => $value) {
         $fields_string .= $key . '=' . $value . '&';
      }
      rtrim($fields_string, '&');
      //gLog("====>api 1> fields: $fields_string");
      $ch = curl_init($url);
      //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Mashape-Authorization: " . $this->api_key]);
      // header: X-Mashape-Authorization: xmhvqGtD86zCrDLDKwhTXB8Aol0odz32
      $response = curl_exec($ch);
      //gLog("====> api 2> response: $response");
      $obj = json_decode($response);
      $this->qid = $obj->token;
      $this->lastResponse = $response;
      return($response);
   }


   /**
   * Busca resultado do reconhecimento de uma imagem já submetida
   *
   * @param type $qid
   * @return json Resultado da consulta
   */
   public function CamFind_result($qid = ""): bool|string
   {
      date_default_timezone_set('UTC');
      $url = "https://camfind.p.mashape.com/image_responses/" . $this->qid;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Mashape-Authorization: " . $this->api_key]);
      return(curl_exec($ch));
   }


   public function setIdBing($id = ''){
      $this->api_key = (empty($id))? false : $id;
      return $this->api_key;
   }


   public function bingTranslation($text = '', string $source = '', string $target = ''): ?string{

      if (empty($this->api_key) && empty($text) && empty($source) && empty($target)) {
         return '';
      }

      $ch = curl_init('https://api.datamarket.azure.com/Bing/MicrosoftTranslator/v1/Translate?Text=%27'.rawurlencode((string) $text).'%27&From=%27'.$source.'%27&To=%27'.$target.'%27');
      curl_setopt($ch, CURLOPT_USERPWD, $this->api_key.':'.$this->api_key);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($ch);
      curl_close($ch);
      preg_match("/<d:Text?.*>(.*)<\/d:Text>/", $response, $matches);
      return $matches[1];
   }
}
