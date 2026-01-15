<?
/*
Para ajuda:



Para testar, abra no navegador:

http://app.giusoft.com.br/amazon/teste.php

*/

error_reporting(E_ALL);
require "aws-autoloader.php";
$credentials = new Aws\Credentials\Credentials('AKIA2MK27B3GVPAHSRBT', 'jCqnncKHJvCMNzdtEeBmg3nrjCB3xlEsNnt6+Z6/');
$options = [
    'version' => 'latest',
    'region' => 'us-east-1',
    'credentials' => $credentials
];
//'credentials' => $credentials
    use Aws\Rekognition\RekognitionClient;

    $rekognition = new RekognitionClient($options);
    // Get local image
    $photo = '/var/www/html/webcfc/go/giusoft/files/geral_pessoas/202.jpg';
    $photo = '/var/www/html/webcfc/admin/files/temora/webcfc_go_corumbaiba/2019/webcfc_go_corumbaiba.4751661.jpg';
    $photo = 'img1.jpg';
    $fp_image = fopen($photo, 'r');
    $image = fread($fp_image, filesize($photo));
    fclose($fp_image);

    // Call DetectFaces
    $result = $rekognition->DetectFaces(array(
       'Image' => array(
          'Bytes' => $image,
       ),
       'Attributes' => array('ALL')
       )
    );
   
echo "<pre>";
print_r($result);
echo "</pre>";

 // Display info for each detected person
    print 'People: Image position and estimated age' . PHP_EOL;
    for ($n=0;$n<sizeof($result['FaceDetails']); $n++){

      print 'Position: ' . $result['FaceDetails'][$n]['BoundingBox']['Left'] . " "
      . $result['FaceDetails'][$n]['BoundingBox']['Top']
      . PHP_EOL
      . 'Age (low): '.$result['FaceDetails'][$n]['AgeRange']['Low']
      .  PHP_EOL
      . 'Age (high): ' . $result['FaceDetails'][$n]['AgeRange']['High']
      .  PHP_EOL . PHP_EOL;
    }
echo "2..";
