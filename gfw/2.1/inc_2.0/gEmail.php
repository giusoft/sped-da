<?
require("phpmailer/class.phpmailer.php");

/**Permite o envio de email passando como parâmetro nos campos para e cc vários endereços.
@param String $remetente
@param String $para
@param String $cc
@param String $assunto
@return void
*/
function gSendEmail($remetente,$para,$cc,$assunto,$conteudo,$anexo="",$site="",$embeddedImage="")
{
	
?>
	<script>
	window.setTimeout('gWaitRemove()',5000)
	document.getElementById('gWait').style.display='inline';
	</script>
<?
	$sep=",";
	if (strpos($para,";")>0)
		$sep=";";

	$VETemailpara = explode($sep,$para);
	$Qemailpara = count($VETemailpara);
	$VETemailcopia = explode($sep,$cc);
	$Qemailcopia = count($VETemailcopia);
	if ((gVar("global.smtpserver").gVar("smtp.server")=="") || (gVar("global.smtpserver")=="localhost")|| (gVar("smtp.server")=="localhost"))
	{
		if ($remetente=="")
			$remetente=gSessionLoad("usr_email");
		if ($remetente=="")
			$remetente="root";
		if ($para<>"")
		{
			for($j=0;$j<$Qemailpara;$j++)
			{
				gLog("Enviando email para ".$VETemailpara[$j].": '$assunto'");
				mail($VETemailpara[$j], $assunto, $conteudo,"From: $remetente\nReply-To: $remetente\nX-Mailer: PHP/" . phpversion(),"-f".$remetente);
			}
		}
		if ($cc<>"")
		{
			for($j=0;$j<$Qemailcopia;$j++)
			{
				gLog("Enviando cópia de email para ".$VETemailcopia[$j].": '$assunto'");
				mail($VETemailcopia[$j], $assunto, $conteudo,"From: $remetente\nReply-To: $remetente\nX-Mailer: PHP/" . phpversion(),"-f".$remetente);
			}
		}
	} else
	{
		$mail = new PHPMailer();
		$mail->SetLanguage("br");
		$mail->IsSMTP(); // mandar via SMTP

		if(gVar("smtp.port") <> "")
			$mail->Port=gVar("smtp.port");

		if (gVar("global.smtpserver")!="")
		{
			$mail->Host = gVar("global.smtpserver"); // Seu servidor smtp
			$mail->FromName = gVar("global.smtpfromname");
			$mail->From= gVar("global.smtpfrom");
			if (gVar("global.smtpuser")!="")
			{
				$mail->SMTPAuth = true; // smtp autenticado
				$mail->Username = gVar("global.smtpuser"); // usuÃ¡rio deste servidor smtp
				$mail->Password = gVar("global.smtppassword"); // senha
			} else
			{
				$mail->SMTPAuth = false; // smtp autenticado
			}
		}
		else
		{
			$mail->Host = gVar("smtp.server"); // Seu servidor smtp
			$mail->FromName = gVar("smtp.fromname");
			$mail->From= gVar("smtp.from");
			if (gVar("smtp.user")!="")
			{
				$mail->SMTPAuth = true; // smtp autenticado
				$mail->Username = gVar("smtp.user"); // usuÃ¡rio deste servidor smtp
				$mail->Password = gVar("smtp.password"); // senha
				gLog("Enviando e-mail autenticado: ".gVar("smtp.user")."/".gVar("smtp.password"));
			} else
			{
				$mail->SMTPAuth = false; // smtp autenticado
			}
		}
		$mail->WordWrap = 50; // set word wrap
		//$mail->AddAttachment("/var/tmp/file.tar.gz"); // attachment

		if ($anexo<>"")
			$mail->AddAttachment("$anexo"); // attachment
		//$mail->AddAttachment("/tmp/image.jpg", "new.jpg");

		if(is_array($embeddedImage))
		{
			 /*
			    OBS: no corpo do email DEVE conter uma referencia para imagem (tag img),
			    do contrario as imagens serao apenas anexadas. Exemplo: "<img alt=\"\" src=\"cid:imgbody{$i}\">"
			    http://phpmailer.worxware.com/?pg=tutorial
			 */
			for($i=0; $i < count($embeddedImage); $i++)
			{
				$ext = explode('.', $embeddedImage[$i]);
				$mail->AddEmbeddedImage("{$embeddedImage[$i]}","imgbody{$i}","teste{$i}.{$ext[1]}");
		    }
		}
		elseif($embeddedImage <> '')
		{
			$ext = explode('.', $embeddedImage);
			$mail->AddEmbeddedImage("$embeddedImage",'imgbody0',"teste{$i}.{$ext[1]}");
		}

		$mail->IsHTML(true); // send as HTML
		
		$mail->Subject = $assunto;
		$mail->Body = nl2br($conteudo);
		$mail->AltBody = $conteudo;
			
		if ($para<>"")
		{
			for($j=0;$j<$Qemailpara;$j++)
			{
				$faz=true;
				for ($k=0; $k<$j;$k++)
				{
					if ($VETemailpara[$k]==$VETemailpara[$j]) $faz=false;
				}
				if (($faz) && (strpos($VETemailpara[$j],"@")>0))
				{
					$mail->AddAddress($VETemailpara[$j]);
					gLog("Enviando e-mail para ".$VETemailpara[$j].": '$assunto'");
				}
			}
		}
		if ($cc<>"")
		{
			for($j=0;$j<$Qemailcopia;$j++)
			{
				$faz=true;
				for ($k=0; $k<$Qemailpara;$k++)
				{
					if ($VETemailpara[$k]==$VETemailcopia[$j]) $faz=false;
				}
				for ($k=0; $k<$j;$k++)
				{
					if ($VETemailcopia[$k]==$VETemailcopia[$j]) $faz=false;
				}
				if (($faz) && (strpos($VETemailpara[$j],"@")>0))
				{
					$mail->AddReplyTo($VETemailcopia[$j]);
					gLog("Enviando cópia de e-mail para ".$VETemailcopia[$j].": '$assunto'");
				}
			}
		}
		if(!$mail->Send())
		{
			gLog("Erro de envio de e-mail: ".$mail->ErrorInfo);
		}
		
	}
?>
	<script>
	document.getElementById('gWait').style.display='none';
	</script>
<?
}
?>
