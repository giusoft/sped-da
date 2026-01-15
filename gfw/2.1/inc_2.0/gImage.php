<?
include "gConf.php";
include $gPathDefault."gDB.php";

if(empty($w))
	$w=320;
if(empty($h))
	$h=240;
$MAX_WIDTH=$w;
$MAX_HEIGHT=$h;

if(empty($gId))
{
	$img="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";
	header("Content-type: image/jpeg");
	echo base64_decode($img);
}
else
{
	if(empty($t))
	{
		$table="geral_imagens";
	}
	$rs=gQuery("select imagem, imagem_tipo from ".$t." where id=$gId");
	/*
	else
	{
		$rs=gQuery("select imagem, imagem_tipo from est_itens where id=$gId");
	}
	*/
	$img_tipo = "image/jpeg";
	if (!$rs->EOF)
	{
		$string_img = $rs->fields['imagem'];
		$img_tipo = $rs->fields['imagem_tipo'];
	}
	
	if($string_img=="")
	{
		$img = imagecreate(80, 20);

	    imagecolorallocate($img,241,241,241);
	     
	    $c  = imagecolorallocate($img,153,153,153);
	    $c1 = imagecolorallocate($img,0,0,0);
	    imagestring($img, 2, 2, 2, 'SEM IMAGEM',$c1 );
		// Mostra a imagem 
		header('Content-type: $img_tipo');
		imagejpeg($img);
	}
	else
	{	
		$img = imagecreatefromstring(base64_decode($string_img));
		if($img)
		{
		    // Pega o tamanho da imagem e proporção de resize
		    $width  = imagesx($img);
		    $height = imagesy($img);
		    $scale  = min($MAX_WIDTH/$width, $MAX_HEIGHT/$height);

		    // Se a imagem é maior que o permitido, encolhe ela!
		    if ($scale < 1)
			 {
		        $new_width = floor($scale*$width);
		        $new_height = floor($scale*$height);

		        // Cria uma imagem temporária
				$tmp_img = imagecreatetruecolor($new_width, $new_height);
				
				// Criar algumas cores
			    $vermelho = imagecolorallocate($tmp_img, 255, 0, 0);
			    $verde    = imagecolorallocate($tmp_img, 0, 255, 0);
			    $azul     = imagecolorallocate($tmp_img, 0, 0, 255);
			    $preto    = imagecolorallocate($tmp_img, 0, 0, 0);
			    $branco   = imagecolorallocate($tmp_img, 255, 255, 255);
				
				// pintar o fundo de branco
				imagefill($tmp_img, 0, 0, $branco);

		        // Copia e resize a imagem velha na nova
		        imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		        //imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		        imagedestroy($img);
		        $img = $tmp_img;
					// Mostra a imagem 
					header('Content-type: $img_tipo');
					imagejpeg($img);
		    } else
		    {
				header('Content-type: $img_tipo');		    	
				echo base64_decode($string_img);
	    	}
		} 

		// Cria uma imagem de erro se necessário
		if (!$img)
		{
		    $img = imagecreate($MAX_WIDTH, $MAX_HEIGHT);

		    imagecolorallocate($img,241,241,241);
		     
		    $c  = imagecolorallocate($img,153,153,153);
		    $c1 = imagecolorallocate($img,0,0,0);
		     
		    imageline($img,0,0,$MAX_WIDTH,$MAX_HEIGHT,$c);
		    imageline($img,$MAX_WIDTH,0,0,$MAX_HEIGHT,$c);
		    imagestring($img, 2, 52, 55, 'Erro ao carregar a imagem',$c1 );
			// Mostra a imagem 
			header('Content-type: $img_tipo');
			imagejpeg($img);
		}
	}

}

?>