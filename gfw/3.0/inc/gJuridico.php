<?php
include_once "gNet.php";
include_once "gBrowser.php";

define ("TENTATIVAS_CAPTCHA",6);
define ("TENTATIVAS_COOKIE",4);

// ============================================================================
// Rotinas específicas para o sistema Jurídico
// ============================================================================

function atualiza($numero='', $id_orgaos=0, $id_grupos=0, $html='', $busca=false, $id_req_situacoes=0, $ja=false, $setor='*nenhum*')
{
	$jur=new gJuridico();
	return ($jur->atualiza($numero, $id_orgaos, $id_grupos, $html, $busca, $id_req_situacoes, $ja, $setor));
}


function ehPje($numero)
{
	global $forceEhPje;

	$sai=false;
	if (isset($forceEhPje))
	{
		$sai=$forceEhPje;
	} else
	{
		$numero=trim($numero);
		//0010093-94.2013.5.01.0072
		//if (((substr($numero,16,4)=='5.01') || (substr($numero,16,4)=='5.05') || (substr($numero,16,4)=='5.03')) && (substr($numero,11,4)>=2013) && (substr($numero,0,7)>"0009999"))


		$codigo=substr($numero,16,4);

		if (substr($numero,11,4)>=2013)
		{
			//echo $codigo."<<===";exit;
			switch ($codigo)
			{

				case '5.01': // TRT RJ
					// 0531 = Teresópolis - sem PJe
					// 0431 = Cabo Frio - sem PJe
					// 0281, 0282, 0283 = Campos
					// 0262 = São Gonçalo

					$regiao=substr($numero,21,4)." ";
					//$sempje=" 0262 0263 0281 0341 0342 0343 0401 0411 0431 0432 0501 0511 ";
					$sempje=" xxxx ";
					if (strpos($sempje,$regiao)===false)
					{
						// Comecando em 2013, somente a partir da numeracao 10000
						//0010386-23.2012.5.01.0000

						if ( ((substr($numero,11,4)>=2013) && (substr($numero,0,7)>"0009999")) )
							$sai=true;

					} else
					{
						if ((($regiao=="0431 ") || ($regiao=="0432 ")) && (substr($numero,0,7)>"0009999"))
						{
							$sai=true;
						}
					}
					break;

				case '5.02':
					if ( substr($numero,11,4) > 2014 || (substr($numero,11,4) <= 2014 && substr($numero,0,7)>="1000000") )
						$sai=true;
				break;

				case '5.03': // TRT MG
					if ( ((substr($numero,11,4)==2015) && (substr($numero,0,7)>"0009999")) || (substr($numero,11,4)>2015) || ((substr($numero,11,4)<=2014) && (substr($numero,0,7)>="10000")) )
						$sai=true;
					break;

				case '5.04': // TRT RS
					$sai=true;
					break;

				case '5.05': // TRT BA
					if ((substr($numero,21,4)<>'0191') && (substr($numero,21,4)<>'0196') && (substr($numero,21,4)<>'0195')  && (substr($numero,21,4)<>'0371') && (substr($numero,21,4)<>'0611') && (substr($numero,21,4)<>'0612'))
					{
						if (substr($numero,11,4)>=2014)
							$sai=true;

						if(((substr($numero,11,4) == 2013) && (substr($numero,0,7)>="10000")))
							$sai=true;
					}
					break;
				case '5.06': // TRT PE
					$sai=true;
					break;

				case '5.07': // TRT CE
					$sai=true;
					break;

				case '5.08': // TRT PA
					$sai=true;
					break;

				case '5.09': // TRT PR
					//echo "Número: ".$numero." <BR> ".substr($numero,0,7). "<br>Regiao: ".substr($numero,21,4);exit;
					//if ( ((substr($numero,11,4)==2014) && (substr($numero,0,7)>"0009999")) || (substr($numero,11,4)>2014) )
				//if ( ((substr($numero,11,4)==2014) && (substr($numero,0,7)>"0009999")) || (substr($numero,11,4)>2013) )
					if ( ((substr($numero,11,4)==2014) && (substr($numero,0,7)>"0009999")) || ((substr($numero,11,4)>2013) && (substr($numero,21,4) <> '0009') && (substr($numero,21,4) <> '0041') && (substr($numero,21,4) <> '0002') ) )
						$sai=true;
					break;

				case '5.10': // TRT DF Brasília
					if ( ((substr($numero,11,4)==2013) && (substr($numero,0,7)>"0009999")) || ((substr($numero,11,4)==2014) && (substr($numero,0,7)>"0009999")) || (substr($numero,11,4)>2015) )
						$sai=true;
					break;

				case '5.12': // TRT SC
					if( (substr($numero,11,4) >= 2014) && (substr($numero,21,4) <> '0045') ) // Físico: 0045
						$sai=true;
					break;

				case '5.13': // TRT Paraíba
					$sai=true;
					break;

				case '5.14': // TRT Rondônia e Acre
					$sai=true;
					break;

				case '5.15': // TRT Campinas/SP
					if ( ((substr($numero,11,4)==2013) && (substr($numero,0,7)>"0009999")) || (substr($numero,11,4)>2013) )
						$sai=true;
					break;

				case '5.16': // TRT Maranhão
					$sai=true;
					break;

				case '5.17': // TRT Esp. Santo
					$sai=true;
					break;

				case '5.18': // TRT Goiás
					if (substr($numero,11,4)>=2013)
						$sai=true;
					break;

				case '5.19': // TRT Alagoas
					$sai=true;
					break;

				case '5.20': // TRT Sergipe
					$sai=true;
					break;

				case '5.21': // TRT Natal/RN
					$sai=true;
					break;

				case '5.22': // TRT Piauí
					$sai=true;
					break;

				case '5.23': // TRT Piauí
					$sai=true;
					break;

				case '5.24': // TRT Mato Grosso do Sul
					if ( ((substr($numero,11,4)==2013) && (substr($numero,0,7)>"0009999")) || (substr($numero,11,4)>2013) )
						$sai=true;
					break;



				// TJ PJe

				case '8.15': // TJ PB
				case '8.17': // TJ PE
				case '8.18': // TJ PI
				case '8.20': // TJ RN
				case '8.22': // TJ RO
				case '8.23': // TJ RR
					$sai = true;
					break;

			}
		} else
		{
			switch ($codigo)
			{
				case '5.01':
					if (substr($numero,21,4)=='0000')
					{
						$sai=true;
					}
					break;
			}
		}
		// if ($sai)
		// 	gLog("===> Identificado como processo PJe");
	}
	return ($sai);
}

function ocr($img_file_name)
{
	/*
	Para o correto funcionamento desta função, o sofwtare tesseract deve estar devidamente instalaado
	https://code.google.com/p/tesseract-ocr/

	Pra melhorar o ocr, edite o arquivo: /usr/local/share/tessdata/configs/digits
	E substitua o conteúdo por:

	tessedit_char_whitelist 0123456789abcdefghijklmnopqrstuvwxyz

	*/

	global $gPathTmp, $usrId;

	$ocrFile="/tmp/alitem-ocr-$usrId";
	$tess="/usr/local/bin/tesseract";
	if (!file_exists($tess))
		$tess="/opt/local/bin/tesseract";
	$cmd=$tess." ".$gPathTmp.$img_file_name." $ocrFile -l eng -psm 8 digits";
	//$cmd="tesseract ".$gPathTmp.$img_file_name." $ocrFile -l eng -psm 8";
	$sai=shell_exec($cmd);
	$ocrFile.='.txt';
	$ocr=file_get_contents($ocrFile);
	unlink($ocrFile);
	$ocr=str_replace("\n","",$ocr);
	$ocr=str_replace("\r","",$ocr);
	$ocr=trim(str_replace(" ","",$ocr));
	return($ocr);
}

function melhoraCaptcha($img_data,$img_file_name)
{
	global $usrId, $http_tmp, $gPathTmp;
	gLog("===> Melhorando captcha...");
	// Obtém tamanho
	$w=imagesx($img_data);
	$h=imagesy($img_data);

	// Melhora a imagem
	imagefilter($img_data, IMG_FILTER_CONTRAST, 30);

	// Transforma tudo em preto com fundo branco
	$lim=160;
	$preto = imagecolorallocate($img_data, 0, 0, 0);
	$branco = imagecolorallocate($img_data, 255,255,255);

	for ($x=0; $x<$w; $x++)
	{
		for ($y=0; $y<$h; $y++)
		{
			$rgb=imagecolorat($img_data, $x, $y);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			if (($r<$lim) || ($g<$lim) || ($b<$lim))
			{
				imagesetpixel($img_data, ($x),($y), $preto);
			} else
			{
				imagesetpixel($img_data, ($x),($y), $branco);
			}
		}
	}


	// tira pontos soltos
	for ($x=1; $x<$w-1; $x++)
	{
		for ($y=1; $y<$h-1; $y++)
		{
			$rgb0=imagecolorat($img_data, $x-1, $y);
			$r0 = ($rgb0 >> 16) & 0xFF;
			$g0 = ($rgb0 >> 8) & 0xFF;
			$b0 = $rgb0 & 0xFF;
			$rgb1=imagecolorat($img_data, $x, $y);
			$r1 = ($rgb1 >> 16) & 0xFF;
			$g1 = ($rgb1 >> 8) & 0xFF;
			$b1 = $rgb1 & 0xFF;
			$rgb2=imagecolorat($img_data, $x+1, $y);
			$r2 = ($rgb2 >> 16) & 0xFF;
			$g2 = ($rgb2 >> 8) & 0xFF;
			$b2 = $rgb2 & 0xFF;

			if ($r1==0 && $r0==255 && $r2==255)
			{
				imagesetpixel($img_data, ($x),($y), $branco);
			}
		}
	}

	// Bordas limpas
	for ($x=0; $x<$w; $x++)
	{
		imagesetpixel($img_data, ($x),(0), $branco);
		imagesetpixel($img_data, ($x),(0), $branco);
		imagesetpixel($img_data, ($x),($h-1), $branco);
		imagesetpixel($img_data, ($x),($h-2), $branco);
	}
	for ($y=0; $y<$h; $y++)
	{
		imagesetpixel($img_data, (0),($y), $branco);
		imagesetpixel($img_data, (1),($y), $branco);
		imagesetpixel($img_data, ($w-1),($y), $branco);
		imagesetpixel($img_data, ($w-2),($y), $branco);
	}

	// Busca linhas horizontais e remove
	for ($y=0; $y<$h; $y++)
	{
		$tem=false;
		$temIni=-1;
		$temFim=-1;
		for ($x=0; $x<$w; $x++)
		{
			$rgb=imagecolorat($img_data, $x, $y);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			if (($r+$g+$b)==0)
			{
				if ($tem)
				{
					// Continua identificando
					$temFim=$x;
				} else
				{
					// Primeira identificacao
					$temIni=$x;
					$temFim=$x;
					$tem=true;
				}
			} else
			{
				if ($tem)
				{
					// Terminou uma linha
					if (($temFim-$temIni)>$tamMin)
					{
						for ($a=$temIni; $a<=$temFim; $a++)
						{
							$afaz=true;
							// Verifica se tem pixel acima
							if ($y>0)
							{
								$rgb=imagecolorat($img_data, $a, $y-1);
								$r = ($rgb >> 16) & 0xFF;
								$g = ($rgb >> 8) & 0xFF;
								$b = $rgb & 0xFF;
								if (($r+$g+$b)==0)
									$afaz=false;
							}
							if ($y<$h)
							{
								$rgb=imagecolorat($img_data, $a, $y+1);
								$r = ($rgb >> 16) & 0xFF;
								$g = ($rgb >> 8) & 0xFF;
								$b = $rgb & 0xFF;
								if (($r+$g+$b)==0)
									$afaz=false;
							}
							if ($afaz)
								imagesetpixel($img_data, ($a),($y), $branco);
						}
					}
				}
				$temIni=-1;
				$temFim=-1;
				$tem=false;
			}
		}
		if ($tem)
		{
			// Terminou uma linha
			if (($temFim-$temIni)>$tamMin)
			{
				for ($a=$temIni; $a<=$temFim; $a++)
				{
					$afaz=true;
					// Verifica se tem pixel acima
					if ($y>0)
					{
						$rgb=imagecolorat($img_data, $a, $y-1);
						$r = ($rgb >> 16) & 0xFF;
						$g = ($rgb >> 8) & 0xFF;
						$b = $rgb & 0xFF;
						if (($r+$g+$b)==0)
							$afaz=false;
					}
					if ($y<$h)
					{
						$rgb=imagecolorat($img_data, $a, $y+1);
						$r = ($rgb >> 16) & 0xFF;
						$g = ($rgb >> 8) & 0xFF;
						$b = $rgb & 0xFF;
						if (($r+$g+$b)==0)
							$afaz=false;
					}
					if ($afaz)
						imagesetpixel($img_data, ($a),($y), $branco);
				}
			}
		}

	}

	// Busca linhas verticais e remove
	for ($x=0; $x<$w; $x++)
	{
		$tem=false;
		$temIni=-1;
		$temFim=-1;
		for ($y=0; $y<$h; $y++)
		{
			$rgb=imagecolorat($img_data, $x, $y);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			if (($r+$g+$b)==0)
			{
				if ($tem)
				{
					// Continua identificando
					$temFim=$y;
				} else
				{
					// Primeira identificacao
					$temIni=$y;
					$temFim=$y;
					$tem=true;
				}
			} else
			{
				if ($tem)
				{
					// Terminou uma linha
					if (($temFim-$temIni)>$tamMin)
					{
						for ($a=$temIni; $a<=$temFim; $a++)
						{
							$afaz=true;
							// Verifica se tem pixel do lado esquerdo
							if ($x>0)
							{
								$rgb=imagecolorat($img_data, ($x-1), $a);
								$r = ($rgb >> 16) & 0xFF;
								$g = ($rgb >> 8) & 0xFF;
								$b = $rgb & 0xFF;
								if (($r+$g+$b)==0)
									$afaz=false;
							}
							// Verifica se tem pixel do lado direito
							if ($x<$w)
							{
								$rgb=imagecolorat($img_data, ($x+1), $a);
								$r = ($rgb >> 16) & 0xFF;
								$g = ($rgb >> 8) & 0xFF;
								$b = $rgb & 0xFF;
								if (($r+$g+$b)==0)
									$afaz=false;
							}
							if ($afaz)
								imagesetpixel($img_data, ($x),($a), $branco);
						}
					}
				}
				$temIni=-1;
				$temFim=-1;
				$tem=false;
			}
		}
		if ($tem)
		{
			// Terminou uma linha
			if (($temFim-$temIni)>$tamMin)
			{
				for ($a=$temIni; $a<=$temFim; $a++)
				{
					$afaz=true;
					// Verifica se tem pixel do lado esquerdo
					if ($x>0)
					{
						$rgb=imagecolorat($img_data, ($x-1), $a);
						$r = ($rgb >> 16) & 0xFF;
						$g = ($rgb >> 8) & 0xFF;
						$b = $rgb & 0xFF;
						if (($r+$g+$b)==0)
							$afaz=false;
					}
					// Verifica se tem pixel do lado direito
					if ($x<$w)
					{
						$rgb=imagecolorat($img_data, ($x+1), $a);
						$r = ($rgb >> 16) & 0xFF;
						$g = ($rgb >> 8) & 0xFF;
						$b = $rgb & 0xFF;
						if (($r+$g+$b)==0)
							$afaz=false;
					}
					if ($afaz)
						imagesetpixel($img_data, ($x),($a), $branco);
				}
			}
		}

	}

	// Salva pra usar o comando via shell
	if (strpos($img_file_name,'png')!==false)
		imagepng($img_data, $img_file_name);
	else
		imagejpeg($img_data, $img_file_name, 100);
}


/** Lê um site do tribunal e atualiza o cabeçalho e seus andamentos
 * @author	giuliano
 * @version	1.0 10-10-2012 08:22
 */
class gJuridico extends gNet
{

	public $records='', $startDate='', $raizAnexo='';
	public $campos, $andamentos, $dataInicio, $net;

	public $responseString = "";
	public $responseXML =  "";
	public $length = 0;
	public $size =0;

	public $curlData="";
	public $curlLength=0;
	public $lastError="";
	public $lastRedir="";
	public $captchaManual=false;
	public $trt='trt1';
	public $viewState='';
	public $estaNoTST=true;

	public $PJeHttp='http';
	public $maisDeUm=false;
	public $http_code='';
	public $usrAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:36.0) Gecko/20100101 Firefox/36.0";

	function parse($debug=0)
	{
		parent::parse($debug);

		$andamentos='';
		if (isset($this->fields['andamentos']))
			$andamentos=$this->fields['andamentos'];
		$dataInicio="2399-01-01";
		foreach ($andamentos as $andamento)
		{
			$data=superTrim($andamento[0]);
			$txt=superTrim($andamento[1]);
			//$txt='xxx'.ord(substr($txt,0,1))." ".ord(substr($txt,1,1));
			$anexo='';
			if ($andamento[2]<>'')
				$anexo=$this->raizAnexo.$andamento[2];
			$grau=intval(superTrim($andamento[3]));
			if ($grau==0)
				$grau=1;
			$anexo=str_replace("#", "", $anexo);
			$data=$this->htmlDateFormat($data);

			// Ajustes para o TST
			$txt=str_replace("Movimentação".chr(10).":".chr(10), "", $txt);
			$txt=str_replace("Local".chr(10).":".chr(10), "Local: ", $txt);
			$txt=str_replace("Petição".chr(10).":".chr(10), "Petição: ", $txt);

			// Ajustes para o TRT Minas - 5a
			$txt=str_replace("1".chr(10), "1a Instância".chr(10), $txt);
			$txt=str_replace("2".chr(10), "2a Instância".chr(10), $txt);
			$txt=str_replace("3".chr(10), "3a Instância".chr(10), $txt);
			$txt=str_ireplace(chr(10)."ver | baixar", "", $txt);


			$and['data']=$data;
			$and['descricao']=$txt;
			$and['anexo']=$anexo;
			$and['grau']=$grau;
			$achei=false;
			// Pra evitar duplicação
			foreach ($this->records as $rec)
			{
				if (
					($rec['data']==$and['data'])&&
					($rec['descricao']==$and['descricao'])&&
					($rec['grau']==$and['grau'])&&
					($rec['anexo']==$and['anexo'])
				)
				{
					$achei=true;
				}
			}
			if (!$achei)
			{
				$this->records[]=$and;
				if (($data<$dataInicio)&&($data<>'20--'))
					$dataInicio=$data;
			}
		}

		$this->startDate=$dataInicio;
		unset($this->fields['andamentos']);
		foreach ($this->fields as $campo=>$txt)
		{
			$txt=str_ireplace("Adv. de (1): ", "", $txt);
			$txt=str_ireplace("Adv. de (2): ", "", $txt);
			$txt=str_ireplace("Adv. de (3): ", "", $txt);
			$txt=str_ireplace(" (1)", "", $txt);
			$txt=str_ireplace(" (2)", "", $txt);
			$txt=str_ireplace(" (3)", "", $txt);
			$this->fields[$campo]=superTrim($txt);
		}
		$num=$this->fields['numero'];
		$novoNum='';
		for ($a=0; $a<strlen($num); $a++)
		{
			if ((is_numeric($num[$a]))||($num[$a]=='.')||($num[$a]=='/')||($num[$a]=='-'))
				$novoNum.=$num[$a];
		}
		$this->fields['numero']=substr($novoNum, 0, 25);
		if ($debug>0)
		{
			echo "===> startDate: ".$dataInicio."<br>";
		}
	}

	function setRaizAnexo($caminho)
	{
		$this->raizAnexo=$caminho;
	}

	function getStartDate()
	{
		return(str_replace(' ','',$this->startDate));
	}

	function getRecords()
	{
		return($this->records);
	}



	function atualizaAndamentos($id_processos,$id_orgaos)
	{
		global $usrId, $usrIdd;

		$inserirTarefas='';
		$cancelarTarefas='';
		// 1=Audiência
		// 5=Providência

		// PJe

		$inserirTarefas['audi&ecirc;ncia una marcada']=1;
		$inserirTarefas['audi&ecirc;ncia una designada']=1;
		$inserirTarefas['audi&ecirc;ncia execução marcada']=1;
		$inserirTarefas['audi&ecirc;ncia instrução marcada']=1;
		$inserirTarefas['audi&ecirc;ncia instrução designada']=1;
		$inserirTarefas['audi&ecirc;ncia instrução redesignada']=1;
		$inserirTarefas['audi&ecirc;ncia conciliação marcada']=1;
		$inserirTarefas['audi&ecirc;ncia concilia&ccedil;&atilde;o em conhecimento designada']=1;

		$inserirTarefas['audi&ecirc;ncia execu&ccedil;&atilde;o marcada']=1;
		$inserirTarefas['audi&ecirc;ncia instru&ccedil;&atilde;o marcada']=1;
		$inserirTarefas['audi&ecirc;ncia instru&ccedil;&atilde;o designada']=1;
		$inserirTarefas['audi&ecirc;ncia instru&ccedil;&atilde;o redesignada']=1;
		$inserirTarefas['audi&ecirc;ncia concilia&ccedil;&atilde;o marcada']=1;

		$inserirTarefas['audi&ecirc;ncia inicial marcada']=1;
		$inserirTarefas['audi&ecirc;ncia julgamento marcada']=5;
		$inserirTarefas['audi&ecirc;ncia inicial designada']=1;

		$inserirTarefas['audi&ecirc;ncia inicial redesignada']=1;
		$inserirTarefas['audi&ecirc;ncia una redesignada']=1;
		$inserirTarefas['audi&ecirc;ncia instru&ccedil;&atilde;o redesignada']=1;
		$inserirTarefas['audi&ecirc;ncia julgamento redesignada']=1;
		$inserirTarefas['audi&ecirc;ncia raz&otilde;es finais designada']=1;
		$inserirTarefas['audi&ecirc;ncia raz&otilde;es finais redesignada']=1;

		// Rio

		$inserirTarefas['audiência una marcada']=1;
		$inserirTarefas['audiência una-ordinário marcada']=1;
		$inserirTarefas['audiência execução marcada']=1;
		$inserirTarefas['audiência instrução marcada']=1;
		$inserirTarefas['audiência conciliação marcada']=1;
		$inserirTarefas['audiência inicial marcada']=1;
		$inserirTarefas['audiência julgamento marcada']=5;
		$inserirTarefas['audiência conciliação em conhecimento designada']=1;
		$inserirTarefas['audiência conciliação em execução designada']=1;


		$inserirTarefas['audiência una designada']=1;
		$inserirTarefas['audiência execução designada']=1;
		$inserirTarefas['audiência instrução designada']=1;
		$inserirTarefas['audiência conciliação designada']=1;
		$inserirTarefas['audiência inicial designada']=1;
		$inserirTarefas['audiência una-ordinário designada']=1;
		$inserirTarefas['audiência julgamento designada']=5;
		$inserirTarefas['audiência razões finais designada']=5;
		$inserirTarefas['audiência razões finais redesignada']=5;

		$inserirTarefas['audiência una adiada']=1;
		$inserirTarefas['audiência una-ordinário adiada']=1;
		$inserirTarefas['audiência execução adiada']=1;
		$inserirTarefas['audiência instrução adiada']=1;
		$inserirTarefas['audiência conciliação adiada']=1;
		$inserirTarefas['audiência inicial adiada']=1;
		$inserirTarefas['audiência julgamento adiada']=5;


		// Minas
		$inserirTarefas['audiência de instrução']=1;
		$inserirTarefas['audiência inicial']=1;
		$inserirTarefas['audiência una']=1;
		$inserirTarefas['audiência de instrução']=1;
		$inserirTarefas['audiência para tentativa de conciliação']=1;
		$inserirTarefas['audiência de decisão ']=5;
		$inserirTarefas['audiência decisão ']=5;

		// SP
		$inserirTarefas['marcação de audiência']=5;

		// TJ Rio
		$inserirTarefas['audiência marcada']=1;
		$inserirTarefas['sessão de julgamento']=1;
		$inserirTarefas['sessao de julgamento']=1;

		// Cancelamento de audiências
		$cancelarTarefas['audiência una cancelada']=1;
		$cancelarTarefas['audiência execução cancelada']=1;
		$cancelarTarefas['audiência instrução cancelada']=1;
		$cancelarTarefas['audiência conciliação cancelada']=1;
		$cancelarTarefas['audiência inicial cancelada']=1;

		$arquivamento[]="arquivado em definitivo";
		$arquivamento[]="arquivado definitivamente";
		$arquivamento[]="arquivados os autos definitivamente";
		$arquivamento[]="extinto o processo por";
		$arquivamento[]="arquivamento tipo de arquivamento: definitivo";
		$arquivamento[]="arquivamento";

		$desarquivamento[]="desarquivado";
		$desarquivamento[]="cancelado - arquivado definitivamente";

		$atualizou=0;

		// Montando cache pra evitar inserir registros duplicados sem ter que acessar o banco
		$procAnt='';
		$procAnt[]='';

		$voltouProTRT=false;

		$modular=false;

		$sql="select * from processos_andamentos where id_processos=$id_processos order by data";
		$rs=gQuery($sql);
		$jaArquivou=false;
		$this->estaNoTST=false;
		while (!$rs->EOF)
		{
			$procAnt[]=$rs->fields['grau'].substr($rs->fields['data'],0,19).$rs->fields['descricao'];

			/*
			// Mudando a situação automaticamente para Desarquivado
			if ($jaArquivou)
			{
				foreach ($desarquivamento as $txtArquivamento)
				{
					if (stripos($rs->fields['descricao'], $txtArquivamento)!==false)
					{
						$jaArquivou=false;
						$sql="UPDATE processos SET data_arquivamento='0000-00-00',data_situacao='".date("Y-m-d H:i:s")."',id_situacoes=13 WHERE id_situacoes=3 AND id=$id_processos";
						gQuery($sql);
					}
				}
			}
			// Mudando a situação automaticamente para Arquivado
			if (!$jaArquivou)
			{
				foreach ($arquivamento as $txtArquivamento)
				{
					if (stripos($rs->fields['descricao'], $txtArquivamento)!==false)
					{
						$jaArquivou=true;
						$sql="UPDATE processos SET data_arquivamento='".$rs->fields['data']."',data_situacao='".date("Y-m-d H:i:s")."', id_situacoes=3 WHERE id_situacoes<>4 AND id_situacoes<>5 AND data_arquivamento='0000-00-00' AND id=$id_processos";
						gQuery($sql);
					}
				}
			}
			*/

			if ($modular)
			{
				//REMETIDOS OS AUTOS.
				if ((stripos($rs->fields['descricao'], 'remetido ao TST')!==false)||
					((stripos($rs->fields['descricao'], 'REMETIDOS OS AUTOS')!==false)&&(stripos($rs->fields['descricao'], 'Tribunal Superior do Trabalho')!==false))
				)
				{
					if ((!$temRelacionados && !$voltouProTRT))
					{
						$sql="update processos set id_orgaos=3 where id=$id_processos";
						//gQuery($sql);
						$tst=true;
					}
					$this->estaNoTST=true;
				}


				// Mudando o órgão para o TRT
				if (
					((stripos($rs->fields['descricao'], 'Tribunal Regional do Trabalho da ')!==false)&&
					(
					(stripos($rs->fields['descricao'], 'Remetidos os autos para o ')!==false)||
					(stripos($rs->fields['descricao'], 'Retorno do TST')!==false)||
					(stripos($rs->fields['descricao'], 'Remetido ao TRT de origem')!==false)
					))||
					(
					(stripos($rs->fields['descricao'], 'CANCELADO - REMETIDOS OS AUTOS')!==false)&&
					(stripos($rs->fields['descricao'], 'Setor Destino: Tribunal Superior do Trabalho')!==false)
					)
				)
				{
					$voltouProTRT=true;
					$tst=false;
					//$sql="select id from orgaos where descricao like '%".$andamento['descricao']."%'";

					// $sql="select id_orgaos id from processos_andamentos where id_orgaos<>3 and id_processos=$id_processos order by data limit 1";
					// $rst=gQuery($sql);
					// $idt=intval($rst->fields['id']);
					// $sql="update processos set id_orgaos=$idt where id=$id_processos";
					// gQuery($sql);
				}
			}
			$rs->MoveNext();
		}

	//echo "<pre>";print_r($procAnt);echo "</pre>";

		// Tratando andamentos ----------------------------------------
		foreach ($this->andamentos as $andamento)
		{
			if (strlen($andamento['data'])<=12)
				$andamento['data']=trim($andamento['data'])." 00:00:00";
			else
				$andamento['data']=substr($andamento['data'],0,19);
			if (strlen($andamento['data'])==16)
				$andamento['data'].=":00";

			$dt=$andamento['data'];
			//$dt=explode("-", $andamento['data']);
			//if ((intval($dt[0])>0)&&(intval($dt[1])>0)&&(intval($dt[2])>0))
			if ((trim($dt)<>"20--") && ($dt>"0000-00-00"))
			{
				$grau = 0;
				if (isset($andamento['grau']))
					$grau=intval($andamento['grau']);
				if ($grau==0)
					$grau=1;
				$andamento['grau']=$grau;
//				if ($id_orgaos==5)
				{
					$id_situacoes=0;

				}
				$andamento['data'] = substr(str_replace("  ", " ", trim($andamento['data'])),0,19);
//echo "<pre>Grau|Data|Descricao: [".$andamento['grau']."|".$andamento['data']."|".$andamento['descricao']."]</pre><br>";exit;

				if ((strpos($andamento['data'],"20--")===false) && (strlen($andamento['data'])>=10)&&(array_search($andamento['grau'].$andamento['data'].$andamento['descricao'], $procAnt)===false))
				{
					gLog("===> Checando: ".$andamento['data']." <=> ".preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", $andamento['data']));
					if (preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", $andamento['data'], $output_array))
					{
						$procAnt[]=$andamento['data'].$andamento['descricao'];

						$atualizou++;
						$anexo='';
						$andDesc=str_replace('"','“',str_replace("'","‘",$andamento['descricao']));
						$sql="insert into processos_andamentos (id_processos,grau, data,descricao,anexo,id_orgaos) values ($id_processos,'".$andamento['grau']."','".$andamento['data']."','".$andDesc."','".$andamento['anexo']."',$id_orgaos)";
						//echo "[".$andamento['data'].$andamento['descricao']."]<br>";
						gQuery($sql);
					}
				}

				// Inserir tarefa automaticamente -------------------------
				$fez=false;
				foreach ($inserirTarefas as $tipoTarefa=>$id_tipos_tarefas)
				{
//echo "Testando: ".$tipoTarefa."<br>";
					if ((!$fez) && (stripos($andamento['descricao'], $tipoTarefa)!==false)
						&&((stripos($andamento['descricao'], ' às ')!==false)||(stripos($andamento['descricao'], ' Às ')!==false)||(stripos($andamento['descricao'], ':')!==false)))
					{
						if (strpos($andamento['descricao'],'Adiamento')===false)
						{
	//echo "- Primeiro if - ok<br>";
							$dataTarefa=extraiData($andamento['descricao']);
	//echo "- Data extraida: ".$dataTarefa."<br>";
							if (($dataTarefa>=date("Y-m-d")) && (substr($dataTarefa,0,10)<>"0000-00-00"))
							{
	//echo "- Segundo if - ok<br>";
								$titulo=ucfirst($tipoTarefa);
								$detalhes=$andamento['descricao'];
								$detalhes=str_replace(chr(10).chr(10), chr(10), $andamento['descricao']);
								$sql="select * from tarefas where id_processos=$id_processos and data_inicio='$dataTarefa' and titulo='$titulo'";
								$rst=gQuery($sql);
								if ($rst->EOF)
								{
									$sql="insert into tarefas
												(id_pessoas_cadastrou,id_tipos_tarefas,data_cadastro,data_inicio,data_final,titulo,detalhes,id_processos) values
												($usrIdd,$id_tipos_tarefas,'".date("Y-m-d")."','$dataTarefa','$dataTarefa','".$titulo."','".$detalhes."',$id_processos)";
									gQuery($sql);
									$fez=true;
								}
							}
						}
					}
				}


				foreach ($cancelarTarefas as $tipoTarefa=>$id_tipos_tarefas)
				{
//echo "Testando: ".$tipoTarefa."<br>";
					if ((!$fez) && (stripos($andamento['descricao'], $tipoTarefa)!==false)
						&&((stripos($andamento['descricao'], ' às ')!==false)||(stripos($andamento['descricao'], ' Às ')!==false)||(stripos($andamento['descricao'], ':')!==false)))
					{
//echo "- Primeiro if - ok<br>";
						$dataTarefa=extraiData($andamento['descricao']);
//echo "- Data extraida: ".$dataTarefa."<br>";
						if (($dataTarefa>=date("Y-m-d")) && (substr($dataTarefa,0,10)<>"0000-00-00"))
						{
//echo "- Segundo if - ok<br>";
							$titulo=ucfirst($tipoTarefa);
							$detalhes=$andamento['descricao'];
							$detalhes=str_replace(chr(10).chr(10), chr(10), $andamento['descricao']);
							$sql="select * from tarefas where id_processos=$id_processos and data_inicio='$dataTarefa' and id_tipos_tarefas=$id_tipos_tarefas";
							$rst=gQuery($sql);
							if ($rst->EOF)
							{
								$sql="update tarefas set cancelada=1 where id=".$rst->fields['id'];
								gQuery($sql);
								$fez=true;
							}
						}
					}
				}


			}
		}

	}

	function atualiza($numero='', $id_orgaos=0, $id_grupos=0, $html='', $busca=false, $id_req_situacoes=0, $jaProcessado=false, $setor='*nenhum*')
	{
		global $tratamentos, $usrIdd, $usrId;
		global $out;
		$this->maisDeUm=false;
		$numero=trim($numero);

		$sql="select * from orgaos where id=".intval($id_orgaos);
		$rs=gQuery($sql);

		$site=$rs->fields['site'];

		$numeracaoLivre=true;
		if ($rs->fields['numeracao_livre']==0)
		{
			$numeracaoLivre=false;
			$mascara="#######-##.####.#.##.####";
			$numero=campoMascara($numero, $mascara);
		}

		$url=$site;
		$post='';
		$cookie=false;
		$debug=0;
		$jaExiste=false;
		if ($numero<>'')
			$busca=false;
		$ok=false;
		if (!$jaProcessado)
		{
			switch ($id_orgaos)
			{
				case 1: // TRT Rio
					gLog("---> Processando $numero - TRT Rio");
					$ok=$this->processaTRTRJ($numero, $html);
					break;
				case 2: // TRT Minas
					gLog("---> Processando $numero - TRT MG");
					$ok=$this->processaTRTMG($numero, $html);
					break;
				case 6: // TRT - São Paulo
					gLog("---> Processando $numero - TRT SP");
					$ok=$this->processaTRTSP($numero, $html);
					break;
				case 7: // TRT - Bahia
					gLog("---> Processando $numero - TRT BA");
					$ok=$this->processaTRTBA($numero, $html);
					break;
				case 3: // TST
					gLog("---> Processando $numero - TST");
					$ok=$this->processaTST($numero, $html);
					break;
				case 5: // TJ Rio
					gLog("---> Processando $numero - TJ RJ");
					$ok=$this->processaTJRJ($numero, $html);
					break;
				case 8: // TJ BA
					gLog("---> Processando $numero - TJ BA");
					$ok=$this->processaTJBA($numero, $html);
					break;
				case 16: // TRF 2a região -  Rio
					gLog("---> Processando $numero - TRF 2A");
					$ok=$this->processaTRF2A($numero, $html);
					break;
				case 17: // JF RJ
					gLog("---> Processando $numero - JF RJ");
					$ok=$this->processaJFRJ($numero, $html);
					break;
				case 18: // STJ
					gLog("---> Processando $numero - STJ");
					$ok=$this->processaSTJ($numero, $html);
					break;
				case 19: // STF
					gLog("---> Processando $numero - STF");
					$ok=$this->processaSTF($numero, $html);
					break;
				case 21: // TRT CE
					gLog("---> Processando $numero - TRT CE");
					$ok=$this->processaTRTCE($numero, $html);
					break;
				case 10: // TRT PE
					gLog("---> Processando $numero - TRT PE");
					$ok=$this->processaTRTPE($numero, $html);
					break;
				case 23: // TRT AL
					gLog("---> Processando $numero - TRT AL");
					$ok=$this->processaTRTAL($numero, $html);
					break;
				case 24: // TRT SE
					gLog("---> Processando $numero - TRT SE");
					$ok=$this->processaTRTSE($numero, $html);
					break;
				case 25: // TRT MA
					gLog("---> Processando $numero - TRT MA");
					$ok=$this->processaTRTMA($numero, $html);
					break;
				case 26: // TRT ES
					gLog("---> Processando $numero - TRT ES");
					$ok=$this->processaTRTES($numero, $html);
					break;

				case 27: // TRT PI
					gLog("---> Processando $numero - TRT PI");
					$ok=$this->processaTRTPI($numero, $html);
					break;

				case 11: // TRT PA
					gLog("---> Processando $numero - TRT PA");
					$ok=$this->processaTRTPA($numero, $html);
					break;

				case 12: // TRT DF
					gLog("---> Processando $numero - TRT DF");
					$ok=$this->processaTRTDF($numero, $html);
					break;

				case 28: // TRT PB
					gLog("---> Processando $numero - TRT PB");
					$ok=$this->processaTRTPB($numero, $html);
					break;

				case 29: // TRT Camp/SP
					gLog("---> Processando $numero - TRT Camp/SP");
					$ok=$this->processaTRTCampSP($numero, $html);
					break;

				case 30: // TRT RN
					gLog("---> Processando $numero - TRT RN");
					$ok=$this->processaTRTRN($numero, $html);
					break;

				case 31: // TRT RN
					gLog("---> Processando $numero - TRT RS");
					$ok=$this->processaTRTRS($numero, $html);
					break;

				case 32: // TRT MS
					gLog("---> Processando $numero - TRT MS");
					$ok=$this->processaTRTMS($numero, $html);
					break;

				case 33: // TRT GO
					gLog("---> Processando $numero - TRT GO");
					$ok=$this->processaTRTGO($numero, $html);
					break;

				case 37: // TRT GO
					gLog("---> Processando $numero - TRT RO e AC");
					$ok=$this->processaTRTROeAC($numero, $html);
					break;

				case 9:  // TRT PR
					gLog("---> Processando $numero - TRT PR");
					$ok=$this->processaTRTPR($numero, $html);
					break;

				case 38: // TRT MT
					gLog("---> Processando $numero - TRT MT");
					$ok=$this->processaTRTMT($numero, $html);
					break;





				case 50: //TJ PB
					gLog("---> Processando $numero - TJ PB");
					$ok=$this->processaTJPB($numero, $html);
					break;

				case 51: //TJ PE
					gLog("---> Processando $numero - TJ PE");
					$ok=$this->processaTJPE($numero, $html);
					break;

				case 53: //TJ PI
					gLog("---> Processando $numero - TJ PI");
					$ok=$this->processaTJPI($numero, $html);
					break;

				case 54: //TJ RN
					gLog("---> Processando $numero - TJ RN");
					$ok=$this->processaTJRN($numero, $html);
					break;

				case 55: //TJ RO
					gLog("---> Processando $numero - TJ RO");
					$ok=$this->processaTJRO($numero, $html);
					break;

				case 56: //TJ RR
					gLog("---> Processando $numero - TJ RR");
					$ok=$this->processaTJRR($numero, $html);
					break;

/*

Pra incluir um novo TRT:

1. Adicione a condição do case (aqui)
2. Crie o método processaTRT... (copiando do AL, SE, MA ou ES)
3. Altere a função ehPje pra reconhecer o processo do novo tribunal como PJe

PJe com Captcha:
TRT 5.03
TRT 5.18

*/

				case 1000:
					break;
			}
		} else
			$ok=true;
		// ============================ INICIO DO TRATAMENTO

		$dataInicio=$this->dataInicio;

		//echo "datInicio: $dataInicio";exit;
		if ($dataInicio=="2399-01-01")
			$dataInicio="0000-00-00";
		if ($ok)
		{
			$campos=$this->campos;
			gLog("==> Encontrados ==> Andamentos: ".count($this->andamentos)." - Campos: ".count($this->campos));
			// Ajustando campos ----------------------------------------------

			if ($campos['numero']=='')
			{
				if (soNumeros($campos['numero_alternativo'])>0)
				{
					$campos['numero']=$numero;
				}
				else
				{
					if (strpos($this->net->html, $numero)!==false)
						$campos['numero']=$numero;
				}
			}
			if (isset($campos['numero_alternativo']))
				unset($campos['numero_alternativo']);

			if ($campos['reu2']<>'')
			{
				$campos['reu'].="<br> ".$campos['reu2'];
				unset($campos['reu2']);
			}

			if (($campos['autor2']<>'')&&($campos['autor2']<>'Réu'))
			{
				$campos['autor'].="<br> ".$campos['autor2'];
			}
			if (isset($campos['autor2']))
				unset($campos['autor2']);

			$temRelacionados=false;
			if ($campos['relacionados']<>'')
			{
				$campos['observacoes']="Processo relacionado: ".$campos['relacionados'];
				$temRelacionados=true;
			}
			if (isset($campos['relacionados']))
				unset($campos['relacionados']);

			if ($campos['recursos']<>'')
			{
				if ($campos['observacoes']<>'')
					$campos['observacoes'].=chr(10);
				$campos['observacoes'].="Recursos: ".$campos['recursos'];
			}
			if (isset($campos['recursos']))
				unset($campos['recursos']);

			if ($id_orgaos==3)
			{
				$n='';
				$cn=$campos['numero'];
				for ($a=0; $a<strlen($cn); $a++)
				{
					if ((($cn[$a]>="0")&&($cn[$a]<="9"))||($cn[$a]==".")||($cn[$a]=="-")||($cn[$a]=="/"))
					{
						$n.=$cn[$a];
					}
				}
				$n=substr($n, 1);
				$n=substr($n, 0, strlen($n)-1);
				$campos['numero']=str_pad($n, strlen($mascara), "0", STR_PAD_LEFT);
			}
			if ($id_orgaos==2)
			{
				/*
				  $sep="(";
				  if (strpos($campos['autor_advogado'],"-")!==false)
				  $sep="-";
				  $oab=explode($sep,$campos['autor_advogado']);
				  $campos['autor_advogado']=trim($oab[0]);
				  $campos['autor_advogado_oab']=trim($oab[1]);
				  $oab=explode($sep,$campos['reu_advogado']);
				  $campos['reu_advogado']=trim($oab[0]);
				  $campos['reu_advogado_oab']=trim($oab[1]);
				 *
				 */
			}
			// Confere se capturou certo antes de salvar...
			$html='';
//echo "entrous 1<br>";
			if (!empty($campos['numero']))
			{
//echo "entrous 2<br>";
				$agora=date("Y-m-d H:i:s");
				$campos['data_inicio']=$dataInicio;

				if ($campos['autor']<>'')
					$campos['autor']=gUcwords($campos['autor']);
				else
					unset($campos['autor']);

				if ($campos['autor_advogado']<>'')
				{
					$tadv="autor_advogado";
					$campos[$tadv]=gUcwords($campos[$tadv]);
					$adv=explode(chr(10), $campos[$tadv]);
					$oab='';
					$newAd='';
					foreach ($adv as $ad)
					{
						if (stripos($ad, "-")!==false)
						{
							$tmp=explode("-", $ad);
							$newAd[]=trim($tmp[0]);
							$oab[]=trim($tmp[1]);
						}
						elseif (stripos($ad, "(")!==false)
						{
							$tmp=explode("(", $ad);
							$newAd[]=trim($tmp[0]);
							$oab[]=trim(str_replace(")", '', $tmp[1]));
						}
						else
						{
							$newAd[]=$ad;
						}
					}
					$campos[$tadv]=implode(chr(10), $newAd);
					if (is_array($oab))
					{
						$campos[$tadv.'_oab']=implode(chr(10), $oab);
					}
				} else
					unset($campos['autor_advogado']);

				if ($campos['autor_advogado_oab']<>'')
					$campos['autor_advogado_oab']=strtoupper($campos['autor_advogado_oab']);
				else
					unset($campos['autor_advogado_oab']);

				if ($campos['reu']<>'')
					$campos['reu']=gUcwords($campos['reu']);
				else
					unset($campos['reu']);

				if ($campos['reu_advogado']<>'')
				{
					$tadv="reu_advogado";
					$campos[$tadv]=gUcwords($campos[$tadv]);
					$adv=explode(chr(10), $campos[$tadv]);
					$oab='';
					$newAd='';
					foreach ($adv as $ad)
					{
						if (stripos($ad, "-")!==false)
						{
							$tmp=explode("-", $ad);
							$newAd[]=trim($tmp[0]);
							$oab[]=trim($tmp[1]);
						}
						elseif (stripos($ad, "(")!==false)
						{
							$tmp=explode("(", $ad);
							$newAd[]=trim($tmp[0]);
							$oab[]=trim(str_replace(")", '', $tmp[1]));
						}
						else
						{
							$newAd[]=$ad;
						}
					}
					$campos[$tadv]=implode(chr(10), $newAd);
					if (is_array($oab))
					{
						$campos[$tadv.'_oab']=implode(chr(10), $oab);
					}
				} else
					unset($campos['reu_advogado']);

				if ($campos['reu_advogado_oab']<>'')
					$campos['reu_advogado_oab']=strtoupper($campos['reu_advogado_oab']);
				else
					unset($campos['reu_advogado_oab']);

				$campos['data_atualizacao']=$agora;

				// Salvando no banco de dados -------------------------------------

				if ($numeracaoLivre)
					$sql="select * from processos where (numero='".$numero."')";
				else
					$sql="select * from processos where (numero='".$numero."' or sonumero='".soNumeros($numero)."')";
				$rs=gQuery($sql);
//echo "<h1>".$rs->fields['idd']."</h1><br>";
				if ($setor<>'*nenhum*')
					$campos['setor']=$setor;
				if ($rs->EOF)
				{

					$campos['numero']=trim($numero);
					$campos['sonumero']=soNumeros($numero);
					$campos['numero_pasta']=geraNumeroInterno($id_grupos);
					$campos['data_criacao']=$agora;
					$campos['data_patrocinio']=$agora;
					$pje=0;
					if (ehPje(trim($numero)))
						$pje=1;
					$campos['pje']=$pje;
					if ($id_req_situacoes>0)
						$campos['id_situacoes']=$id_req_situacoes;

					// >>>> Remover o nome do advogado do nome do autor -  ao atualizar o processo
					if(isset($campos['autor']))
					{
						$aut = explode('Advogado',$campos['autor']);
						$aut = explode('advogado',$aut[0]);
						$campos['autor']=$aut[0];
					}
					// <<<<

					$sql="insert into processos (id_orgaos,id_grupos,data_situacao,".implode(",", array_keys($campos)).") values ($id_orgaos,$id_grupos,'".date("Y-m-d H:i:s")."','".implode("','", array_values($campos))."')";
					gQuery($sql);
					$sql="select id from processos where id_orgaos=$id_orgaos and numero='".$numero."'";
					$rs=gQuery($sql);
				} else
				{
					$jaExiste=true;
					unset($campos['numero']);
					$campos['sonumero']=soNumeros($numero);
					unset($campos['data_inicio']); // Se o processo já existir, não edita data_inicio (data_distribuição)
					if ($rs->fields['inverter_reu_autor']==1)
					{
						$tReu=$campos['reu'];
						$tAdv=$campos['reu_advogado'];
						$campos['reu']=$campos['autor'];
						$campos['reu_advogado']=$campos['autor_advogado'];
						$campos['autor']=$tReu;
						$campos['autor_advogado']=$tAdv;
					}

					if ($campos['reu']=='')
						unset($campos['reu']);
					if ($campos['reu_advogado']=='')
						unset($campos['reu_advogado']);
					if ($campos['autor']=='')
						unset($campos['autor']);
					if ($campos['autor_advogado']=='')
						unset($campos['autor_advogado']);
					$sql="update processos set ";
					$campos['pje']=intval(ehPje($numero));
					$campos['servidor']=$_SERVER['SERVER_ADDR'];
					// Se for TJ RJ e situação "Arquivado" não salva os campos abaixo (pois podem ter sido alterados manualmente)
					if (($rs->fields['id_orgaos']==4)||(($rs->fields['id_orgaos']==5)&&($rs->fields['id_situacoes']==3))&&($rs->fields['reu']<>'')&&($rs->fields['autor']<>''))
					{
						unset($campos['reu']);
						unset($campos['autor']);
						unset($campos['reu_advogado']);
						unset($campos['autor_advogado']);
						unset($campos['descricao']);
						unset($campos['observacoes']);
					}

					if ($rs->fields['substituir_autor_por']<>'')
					{
						$campos['autor']=gUcwords($rs->fields['substituir_autor_por']);
					}
					if ($rs->fields['substituir_reu_por']<>'')
					{
						$campos['reu']=gUcwords($rs->fields['substituir_reu_por']);
					}

					// Evitando apagar o nome do autor
					if($campos['autor'] == '')
					{
						unset($campos['autor']);
					}

					// >>>> Remover o nome do advogado do nome do autor -  ao atualizar o processo
					if(isset($campos['autor']))
					{
						$aut = explode('Advogado',$campos['autor']);
						$aut = explode('advogado',$aut[0]);
						$campos['autor']=$aut[0];
					}
					// <<<<

					foreach ($campos as $key=>$value)
						$sql.=$key."='".str_replace("'","’",$value)."',";
					$sql=substr($sql, 0, strlen($sql)-1)." where id=".$rs->fields['id'];
					gQuery($sql);
					$campos['data_criacao']=$rs->fields['data_criacao'];
				}
				$id_processos=$rs->fields['id'];


				$this->atualizaAndamentos($id_processos, $id_orgaos);


				if ($busca)
				{
					if ($jaExiste)
						$html.=$out->msgError("Processo já cadastrado").$out->msgMiniTitle("Informações atualizadas pelo site do orgão. Andamentos incluídos: $atualizou");
					else
						$html.=$out->msgMiniTitle("Informações capturadas do site do orgão");
					$html.=processoCabecalho($campos);
					$html.=$out->msgSubTitle("Andamentos");
					$html.=$out->tableBegin("big", true);
					foreach ($this->andamentos as $andamento)
					{
						$dt=explode("-", $andamento['data']);
						if ((intval($dt[0])>0)&&(intval($dt[1])>0)&&(intval($dt[2])>0))
						{
							$mtz="";
							$mtz[]="<-".gDate($andamento['data']);
							$mtz[]="<-".$andamento['descricao'];
							$html.=$out->tableRow($mtz, "light");
						}
					}
					$html.=$out->tableEnd();
				}
			}
		} else
		{
			if ($numeracaoLivre)
				$sql="select * from processos where (numero='".$numero."')";
			else
				$sql="select * from processos where (numero='".$numero."' or sonumero='".soNumeros($numero)."')";
			$rs=gQuery($sql);
			$inserido=false;
			$agora=date('Y-m-d H:i:s');
			if ($rs->EOF)
			{
				$campos['numero']=trim($numero);
				$campos['sonumero']=soNumeros($numero);
				$campos['data_criacao']=$agora;
				$campos['numero_pasta']=geraNumeroInterno($id_grupos);
				if ($setor<>'*nenhum*')
					$campos['setor']=$setor;
				$pje=0;
				if (ehPje(trim($numero)))
					$pje=1;
				$campos['pje']=$pje;
				if ($id_req_situacoes>0)
					$campos['id_situacoes']=$id_req_situacoes;

				// >>>> Remover o nome do advogado do nome do autor -  ao atualizar o processo
				if(isset($campos['autor']))
				{
					$aut = explode('Advogado',$campos['autor']);
					$aut = explode('advogado',$aut[0]);
					$campos['autor']=$aut[0];
				}
				// <<<<

				$sql="insert into processos (id_orgaos,id_grupos,data_situacao,".implode(",", array_keys($campos)).") values ($id_orgaos,$id_grupos,'".date("Y-m-d H:i:s")."','".implode("','", array_values($campos))."')";
				gQuery($sql);
				$inserido=true;
			}

			$err=$this->lastError;
			if ($err<>"")
				$err="<br><br>$err";
			if (is_object($out))
			{
				if ($inserido)
					$html=$out->msgError("O processo $numero foi adicionado com sucesso, entretanto não foi possível atualizar neste momento no site ".$rs->fields['descricao'].$err);
				else
					$html=$out->msgError("Não foi adicionar o processo $numero neste momento no site ".$rs->fields['descricao'].$err."<br>Aguarde 15 minutos e tente novamente");
			}
			else
				$html="Não foi possível atualizar o processo $numero no site ".$rs->fields['descricao'].$err."<br>Entretanto o processo foi inserido no sistema.";
		}
		$_SESSION['gCookieUltimoOrgao']=$id_orgaos;
		$_SESSION['gCookieUltimoNumero']=$numero;
		$_SESSION['gCookieUltimoHora']=date("Y-m-d H:i:s");

		// Se o processo for trabalhista e teve um andamento indicando que foi pro TST
		// Então busca andamentos também no TST além do TRT de origem
		if ($id_orgaos<>3 && $this->estaNoTST)
			$this->atualiza($numero,3,$id_grupos);
		return($html);
	}

	function getPJeAntigoCookie($url,$style=0)
	{
		global $out,$http_tmp,$usrId,$gPathTmp;

		$sai="";
		$trt=$this->trt;
		$usrAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:19.0) Gecko/20100101 Firefox/19.0";
		$cookie=true;
		$ckfile=$this->getCookie();

		//==================================== PASSO 1 - Obter o Cookie


		// Carrega página pra obter o Cookie
		// O site do PJe espertamente força um TIMEOUT interrompendo o envio da página
		// Além de ficar lento, não vem o conteúdo completo da página
		// Pra evitar isto, seta-se o TIMEOUT do curl pra 2 segundos, obtém o cookie
		// e carrega novamente a página, solicitando o conteúdo a partir do byte 3006

		$faz=true;
		$cntFaz=0;
		$timeout=5;
		if ((date("H")>10) && (date("H")<5))
			$timeout=10;

		while ($faz)
		{
			$cntFaz++;
			gLog("===> PJe: buscando cookie - Timeout $timeout - url: $url)");
			$ch = curl_init ($url);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);
			curl_setopt ($ch, CURLOPT_HTTPHEADER, array (
					"Connection:	keep-alive",
					"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
					"Accept-Encoding:	gzip, deflate",
					"DNT: 1",
					"Host: pje.$trt.jus.br"));
			curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_HEADER, 1);
			curl_setopt($x, CURLOPT_ENCODING, '');
			curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout); //timeout in seconds
			//curl_setopt( $ch, CURLOPT_REFERER, 'http://www.trt16.jus.br/site/index.php?acao=conteudo/pje/index.php');
			curl_setopt ($ch, CURLOPT_VERBOSE, true);
			curl_setopt ($ch, CURLOPT_HEADER, true);


			$html = curl_exec ($ch);
			$http_code = trim(curl_getinfo($ch, CURLINFO_HTTP_CODE));

			gLog("===> PJe: buscando cookie - Retornou: ".strlen($html)." caracteres");
//echo "\n\n\n\n\n\n=====\n$html\n=====";exit;
			curl_close ($ch);
			// Verifica se conseguiu carregar o cookie
			if (file_get_contents($ckfile)<>"")
			{
				$ok=true;
				$faz=false;
			}
			// Tenta pegar o cookie 5 vezes, aumentando o timeout cada tentativa
			if ($cntFaz>=TENTATIVAS_COOKIE)
			{
				$faz=false;
				$ok=false;
			}
			$timeout+=5;
		}

		$this->viewState="";
		if (!$ok)
		{
			gLog("===> PJe: ".TENTATIVAS_COOKIE." tentativas de buscar o cookie. Leitura cancelada.");
			$this->lastError="Não foi possível buscar o cookie do site $url";
		} else
		{
			if (stripos($html,"Detectada utilizacao excessiva"))
			{
				gLog("===> PJe: Detectada utilização excessiva");
				$ok=false;
				$this->lastError="Não é possível acessar o site do PJe no momento. Aguarde alguns minutos e tente novamente.";
			} else
			{
				$p=strpos($html,'meta http-equiv="Refresh"');
				if ($p!==false)
				{
					//echo "$url\n\n\n\n\n ".nl2br(htmlentities($html))." \n\n\n\n";exit;
					//<meta http-equiv="Refresh" content="0; URL=pages/consultas/ConsultaProcessual.seam"/>
					$p2=strpos($html,"URL=", $p);
					$redir=substr($html,$p2+4);
					$p3=strpos($redir,'"');
					$redir=substr($redir,0,$p3);
					curl_close ($ch);
					$baseUrl=substr($url,0,stripos($url,'br/')+3);
					$redir=$baseUrl.'consultaprocessual/'.$redir;
					gLog("===> PJe: Refresh para: $redir");
					$ch = curl_init ($redir);
					curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
					$url=$redir;
					if (strpos($redir,"https")!==false)
						curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt ($ch, CURLOPT_TIMEOUT, 15); //timeout in seconds
					curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
					curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);

					//curl_setopt ($ch, CURLOPT_POST, true);
					//curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
					curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
					//curl_setopt ($ch, CURLOPT_REFERER, $referer);
					curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt ($ch, CURLOPT_VERBOSE, true);
					curl_setopt ($ch, CURLOPT_HEADER, true);
					$html = curl_exec ($ch);
					$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				}

				$p=strpos($html,'id="javax.faces.ViewState');
				if ($p>0)
				{
					$this->viewState=substr($html,$p+34);
					$this->viewState=substr($this->viewState,0,strpos($this->viewState,'"'));
					gLog("===> PJe: Cookie e viewState obtido com sucesso: ".$this->viewState);
				} else
				{
					//echo "$url\n\n\n\n\n ".nl2br(htmlentities($html))." \n\n\n\n";exit;


					gLog("===> PJe: Erro no retorno. Nenhuma informação válida encontrada (viewState)");
					$ok=false;
					$this->lastError="Não é possível acessar o site do PJe no momento. Aguarde alguns minutos e tente novamente.";
				}
			}

		}
		return($ok);
	}



	function getPJeCookie($url,$style=0)
	{
		global $out,$http_tmp,$usrId,$gPathTmp;

		$sai="";
		$trt=$this->trt;
		$usrAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:19.0) Gecko/20100101 Firefox/19.0";
		$cookie=true;
		$ckfile=$this->getCookie();
		//$ckfile = tempnam(sys_get_temp_dir(), "alitem-cookie-".$usrId);

		//==================================== PASSO 1 - Obter o Cookie


		// Carrega página pra obter o Cookie
		// O site do PJe espertamente força um TIMEOUT interrompendo o envio da página
		// Além de ficar lento, não vem o conteúdo completo da página
		// Pra evitar isto, seta-se o TIMEOUT do curl pra 2 segundos, obtém o cookie
		// e carrega novamente a página, solicitando o conteúdo a partir do byte 3006

		$faz=true;
		$cntFaz=0;
		$timeout=5;
		if ((date("H")>10) && (date("H")<5))
			$timeout=10;

		gLog(">>>> chfile: $ckfile - gCookie: ".$_SESSION['gCookie']);
		while ($faz)
		{
			$cntFaz++;
			gLog("===> PJe: buscando cookie (timeout $timeout): $url");
			$ch = curl_init ($url);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);
			if ($trt=='trt22')
			{
				curl_setopt ($ch, CURLOPT_HTTPHEADER,array (
						"Connection:	keep-alive",
						"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
						"DNT: 1",
						"Host: pje.$trt.jus.br"));
			} else
			{
				curl_setopt ($ch, CURLOPT_HTTPHEADER,array (
						"Connection: keep-alive",
						"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
						"Accept-Encoding: gzip, deflate",
						"Host: consultapje.$trt.jus.br"));
				curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
			}
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_HEADER, 1);
			if (strpos($url,"https")!==false)
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout); //timeout in seconds
			$html = curl_exec ($ch);
			curl_close ($ch);
			file_put_contents("/tmp/alitem-url-debug-cookie-".$_SERVER['SERVER_ADDR'].".log", str_replace("\r","",$html));
			//file_put_contents('/tmp/cookie.txt', file_get_contents('/tmp/alitem-24-CURLCOOKIE')."\n\n\n", FILE_APPEND);
			// Verifica se conseguiu carregar o cookie
			if (file_get_contents($ckfile)<>"")
			{
				$ok=true;
				$faz=false;
			} else
			{
				if (strpos($html, 'decodeURIComponent')!==false)
				{
					$baseUrl=substr($url,0,stripos($url,'br/')+3);
					$html = $this->processaJavascript($html, $baseUrl);
					gLog(">>> Cookie content: ".file_get_contents($ckfile));
					if (file_get_contents($ckfile)<>"")
					{
						$ok=true;
						$faz=false;
					}

				}
			}
			// Tenta pegar o cookie 5 vezes, aumentando o timeout cada tentativa
			if ($cntFaz>=TENTATIVAS_COOKIE)
			{
				$faz=false;
				$ok=false;
			}
			$timeout+=5;
		}

		$this->viewState="";
		if (!$ok)
		{
			gLog("===> PJe: ".TENTATIVAS_COOKIE." tentativas de buscar o cookie. Leitura cancelada.");
			$this->lastError="Não foi possível buscar o cookie do site $url";
		} else
		{
			if (stripos($html,"Detectada utilizacao excessiva")!==false )
			{
				gLog("===> PJe: Detectada utilização excessiva");
				$ok=false;
				$this->lastError="Não é possível acessar o site do PJe no momento. Aguarde alguns minutos e tente novamente.";
			} else
			{
//echo "$url\n\n\n\n\n ".nl2br(htmlentities($html))." \n\n\n\n";exit;
				$p=strpos($html,'id="javax.faces.ViewState');
				if ($p>0)
				{
					$this->viewState=substr($html,$p+34);
					$this->viewState=substr($this->viewState,0,strpos($this->viewState,'"'));
					gLog("===> PJe: Cookie e viewState obtido com sucesso: ".$this->viewState);
				} else
				{
					gLog("===> PJe: Erro no retorno. Detectada utilização excessiva");
					$ok=false;
					$this->lastError="Não é possível acessar o site do PJe no momento. Aguarde alguns minutos e tente novamente.";
				}
			}

		}
		return($ok);
	}


	function ___getPJeCaptcha($url,$style=0)
	{
		global $out,$http_tmp,$usrId,$gPathTmp;
		$sai="";
		$trt=$this->trt;

		$usrAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:19.0) Gecko/20100101 Firefox/19.0";
		$cookie=true;
		$ckfile= $this->getCookie();
		$u=$url;
		$url =$u[0];
		$url2=$u[1];
		$urlCaptcha=$u[2];








		/*
		 * Em 08/2013 foi implementada uma página intermediária com código javascript
		 * que efetua um cálculo para chamar novamente a mesma página, passando
		 * os parâmetros calculados. Medida criada para evitar Robos.
		 */

		gLog("===> PJe: buscando página javascript");

		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
		curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_REFERER, $url);
		curl_setopt ($ch, CURLOPT_TIMEOUT, 20);

		curl_setopt ($ch, CURLOPT_HTTPHEADER,array (
			"Connection: keep-alive",
			"Accept: image/png,image/*;q=0.8,*/*;q=0.5",
			"DNT: 1",
			"Host: pje.$trt.jus.br",
			"Range: bytes=3006-"));
		$html = curl_exec ($ch);
		curl_close ($ch);


		if (stripos($html, "de ambiente")!==false)
		{
			$sai="erro:login_com_certificado";
		} else
		{
			// Extrai código javascript
			$script=substr($html,stripos($html,"<script"));
			$script=substr($script,stripos($script, ">")+1);
			$script=substr($script,0,strpos($script,'</'));

			// Extrai campos ocultos
			$campos=substr($html,stripos($html,"form method"));
			for ($a=0; $a<6; $a++)
			{
				$campos=substr($campos,stripos($campos,'name=')+6);
				$campo=substr($campos,0,stripos($campos,'"'));
				$campos=substr($campos,stripos($campos,'value=')+7);
				$valor=substr($campos,0,stripos($campos,'"'));
				$hidden[$campo]=$valor;
			}

			// Modifica código javascript
			$campo=substr($script,stripos($script,'value=')+6);
			$campo=substr($campo,0,stripos($campo,';'));
			$script=substr($script,0,stripos($script,"document.forms"));
			$script.="print(decode_string($campo));\n";
			$script.="}\ntest();";
			$script=str_replace("\r","",$script);
			$fileName="/tmp/alitem-pje-$usrId-".$_SERVER['SERVER_ADDR'].".js";
			$fileNameLog="/tmp/alitem-pje-$usrId-".$_SERVER['SERVER_ADDR'].".log";
			file_put_contents($fileName, $script);
			$cmd="/usr/local/bin/js17 -f $fileName > $fileNameLog";
			$sai=shell_exec($cmd);
			$string=trim(file_get_contents($fileNameLog));

			$post="";
			$cnt=0;
			foreach ($hidden as $key=>$value)
			{
				if ($cnt==1)
					$post[$key]=$string;
				else
					$post[$key]=$value;
				$cnt++;
			}

//$post['TSf2b23a_rf']='0';
			gLog("===> PJe: buscando captcha");
			$ch = curl_init();
			$defaults = array(
				CURLOPT_INTERFACE => $_SERVER['SERVER_ADDR'],
				CURLOPT_POST => 1,
				CURLOPT_AUTOREFERER => true,
				CURLOPT_URL => $url,
				CURLOPT_FRESH_CONNECT => 1,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_FORBID_REUSE => 1,
				CURLOPT_TIMEOUT => 10,
				CURLOPT_COOKIEFILE=> $ckfile,
				CURLOPT_POSTFIELDS => http_build_query($post)
			);
			curl_setopt_array($ch, $defaults);
			$this->net->html = html_entity_decode(autoencode(curl_exec ($ch)));
			curl_close ($ch);


			$html=$this->net->html;

//echo "<h1>$url<br></h1><br><br><pre>";print_r($hidden)."\n\n".print_r($post);echo "</pre><hr>";
//echo $url."<br><br><pre>";print_r($hidden)."\n\n".print_r($post);echo "</pre>".str_replace("script","s",$html);exit;


			//==================================== PASSO 2 - Obter o captcha


			// Chamando a página novamente, só que enviando o cookie desta vez
			// Pra obter o Captcha

			//$ch = curl_init ($url);
			//curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);
			//curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
			//curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			//curl_setopt ($ch, CURLOPT_REFERER, $url);
			//curl_setopt ($ch, CURLOPT_HTTPHEADER,array (
			//	"Connection: keep-alive",
			//	"Accept: image/png,image/*;q=0.8,*/*;q=0.5",
			//	"DNT: 1",
			//	"Host: pje.$trt.jus.br",
			//	"Range: bytes=3006-"));
			//$html = curl_exec ($ch);
			//curl_close ($ch);

			if (stripos($html,"Detectada")===false)
			{
				// /primeirograu/seam/resource/captcha;jsessionid=55DDA22CE26D64FB91E2AD932BAC89D9.pje01-jb-ext-h1?code=1942


				// O HTML resultante contém o link para o Captcha
				$captcha=substr($html,strpos($html,"captcha?")+8);
				$captcha=substr($captcha,0,strpos($captcha,'"'));
				//<input type="hidden" name="javax.faces.ViewState" id="javax.faces.ViewState" value="j_id6" autocomplete="off" />
				$viewState=substr($html,strpos($html,'id="javax.faces.ViewState')+34);

				$viewState=substr($viewState,0,strpos($viewState,'"'));
				gLog("===> Captcha: $captcha");
	//echo $urlCaptcha."<br><br>captcha: $captcha<br><br>viewState: $viewState<br><br><textarea cols=180 rows=50>".str_replace("script","s",$html)."</textarea>";exit;
		//gLog("===> viewState: [".$viewState."] ");

				// Busca IMG do Captcha e cria arquivo igual localmente
				$ch = curl_init ($urlCaptcha.$captcha);
				curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
				curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);
				curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
				$output = curl_exec ($ch);
				curl_close ($ch);

				$img_file_name = "alitem-img-".$usrId."-".date("is").".png";

				$img_link = $http_tmp."/".$img_file_name;
				$img_data = imagecreatefromstring($output);
				melhoraCaptcha($img_data,$gPathTmp.$img_file_name);
				imagepng($img_data, $gPathTmp.$img_file_name);
	//echo "<br><br>img: $img_link<br><br>";exit;

//echo "<h1>".$urlCaptcha."[$captcha]"."</h1><br><br><pre>";print_r($hidden)."\n\n".print_r($post);echo "</pre><textarea cols=200 rows=100>".str_replace("script","cript",$html)."</textarea>";exit;

			gLog("===> PJe captcha: $captcha - $img_link");
				$sai['code']=$captcha;
				$sai['link']=$img_link;
				$sai['file']=$gPathTmp.$img_file_name;
				$sai['viewState']=$viewState;

			} else
			{
				$sai="erro";
			}
		}


		return($sai);

	}

	function getCaptcha($url,$style=0,$post="")
	{
		global $out,$http_tmp,$usrId,$gPathTmp;

		$sai="";
		$cookie_file = $this->getCookie();
		if ($style==1) // TRT Rio PJe
		{

			gLog("---> Abrindo URL: ".$url[1]);

			$img_file_name = "imagem-$usrId.png";

			$header="";
			$header[]="Accept: image/png,image/*;q=0.8,*/*;q=0.5";
			$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
			$header[]="Connection: keep-alive";
			$header[]='Referer: http://consulta.trtrio.gov.br/portal/processoListar.do';
			$header[]="Host: consulta.trtrio.gov.br";
			$header[]="DNT: 1";

			//Busca 2x o captcha, pois a primeira sempre dá errado...
			$ch = curl_init ();
			curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_USERAGENT, $this->usrAgent);
			curl_setopt($ch, CURLOPT_URL, $url[1]);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_exec ($ch);

			// 2x
			$ch = curl_init ();
			curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_USERAGENT, $this->usrAgent);
			curl_setopt($ch, CURLOPT_URL, $url[1]);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec ($ch);

			file_put_contents($gPathTmp.$img_file_name, $output);
			$img_link = $http_tmp."/".$img_file_name;
			$sai['link']=$img_link;




		} else
		{

			// Define parametros
			$my_site = $http_tmp;

			// Pra ficar igual ao JBoss
			$mt=str_replace('.','',round(microtime(true),3));

			// Pega cookie em $url[0]
			gLog("---> Abrindo URL: ".$url[0]);


			$ch = curl_init ($url[0]);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			if ($style==6)
			{
				$header="";
				$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
				$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
				//$header[]="Accept-Encoding: gzip, deflate";
				//$header[]="Cache-Control: no-cache";
				$header[]="Connection: keep-alive";
				$header[]='Referer: http://aplicacoes5.trtsp.jus.br/consultasphp/public/index.php/primeirainstancia';
				$header[]="Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
				$header[]="Host: aplicacoes5.trtsp.jus.br";
				$header[]="DNT: 1";
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_USERAGENT, $this->usrAgent);
				//curl_setopt ($ch, CURLOPT_HEADER, true);
			}

			curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_file);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec ($ch);
			curl_close($ch);

			if ($style==6) // TRT SP
			{
				// Busca o sid...
				$pos=strpos($output,"/captcha/image.php?id=");
				$sid=substr($output,($pos+22));
				$sid=substr($sid,0,strpos($sid,'"'));
				$url[1]=$url[1]."?id=$sid";
				$pos=strpos($output,'captcha[id]');
				$captchaId=substr($output,$pos);
				$pos=strpos($captchaId,'value=');
				$captchaId=substr($captchaId,$pos+7);
				$captchaId=substr($captchaId,0,strpos($captchaId,'"'));
			}

			if ($style==2) // TRT Minas
			{

				$cookie=explode(chr(10),file_get_contents($cookie_file));
				foreach ($cookie as $el)
				{
					if ((substr($el,0,1)<>"#") && (trim($el)<>""))
					$cLinha=$el;
				}
				$cEl=explode(chr(9),$cLinha);
				$jsessionid=$cEl[6];

				$pos=strrpos($output,"javax.faces.ViewState");
				$view=explode("\"",substr($output,$pos));
				$faces=$view[2];

				$pos=strpos($output,"?f=");
				$view=explode("\"",substr($output,$pos+3));
				$f=$view[0];

				$url[1]= $url[1].";jsessionid=$jsessionid?f=".$f;
			}
			if ($style==5) //TJ RJ
			{
				// Faz um redirecionamento pra outra página com parametros processados
				if (strpos($output,"Redirect to")!==false)
				{
					$redir=substr($output,strpos($output,"numProcesso=")+12);
					$redir=trim(substr($redir,0,strpos($redir,'<')));
					$sai['tipo']="numProcesso";
					$sai['numProcesso']=$redir;
					$url[1]=$url[1].$redir;
				} else
				{
					// Mostra vários links
					$busca="http://www4.tjrj.jus.br/consultaProcessoWebV2/consultaProc.do";
					$p=strpos($output,$busca);
					if ($p!==false)
					{
						$redir=substr($output,strpos($output,"numProcesso=")+12);
						$redir=trim(substr($redir,0,strpos($redir,"'")));
						$sai['tipo']="numProcesso";
						$sai['numProcesso']=$redir;
						$links[]=substr($output,$p,strpos($output,"'",$p));
						$url[1]=substr($output,$p,strpos($output,"'",$p));
					} else
					{
						// Não tem o link que exige captcha, então abre outro processo só pra forçar o captcha
						$sai['numProcesso']='2011.209.012493-8';
						$url[1]=$url[1].$sai['numProcesso'];
					}
				}

					gLog("---> Abrindo URL: ".$url[1]);
					$ch = curl_init ();
					curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
					curl_setopt ($ch, CURLOPT_URL, $url[1]);
					curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_file);
					curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_file);

					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
					$output = curl_exec ($ch); // the real thing

					$url[1]=$url[2];

			}
			if ($style==32) // TRT Mato Grosso do Sul
			{

				$pos=strrpos($output,"javax.faces.ViewState");
				$view=explode("\"",substr($output,$pos));
				$faces=$view[2];
			}

			if ($style==33) // TRT GO - PENDENTE!!!!
			{
//<img width="300" height="57" src="https://www.google.com/recaptcha/api/image?c=03AHJ_VutjJmkf4OEvUlnIR4p6ywPW-ph8RcxV95C1C5-TbtRb8RDlCHGXSzQEAaoeny1fyFWvWxzwMc-sGzWJ37dmQ8AjM9zfLWG6ei2qQN-93JPJXF2q9r8E7Gkc2k0o5PQogjCH7ooLnnLTnWXONGomXeKYe6jG0bZRkOEVtppRMJDBbbiPR5rWFeSFRVm5v0ZfPNjdr5ZgrsHb0Xd72Fn-wu7FZZKeHamzm8dAgOCROfBW5chyRCQDUqfUmxW41l-03QZndpo8N-ZhoIOVTeD1jc17Vv7HtA&amp;th=,0yKw314jMwd5sje2BMDMqEyylTrwAAAAQKAAAAAG2AC7RkuJzJ1XnwlQnW3qvzfjeOh7O2LQ6vaBhlQniS1PdmtDtkdqYHasAvGh_AsEdE4Ufi2MgLubKJyYc81QtpUvSj9UxP7Oqic4hKszcDhgy0lR3Ba9wVSQuhzRxxx09rkHsHeiL2UqFsNavvXGC9wRYGrJY5i4Ol2xvE-CEeKfD13m1IG_MjSiEUdLrqWk4TVngNlVcKtVdYK0w1qtGIVBSnnHhoEF-5fdRapK1zXGRBeGdAdg-ckVqnoL1Q" alt="Imagem de desafio reCAPTCHA" id="recaptcha_challenge_image">
				$pos=strpos($output,"?c=");
				$view=explode("\"",substr($output,$pos+3));
				$f=$view[0];

				$url[1]= $url[1].";jsessionid=$jsessionid?f=".$f;

			}

			/**
			 Obtém imagem, e salva localmente
			 */

			gLog("---> Abrindo URL: ".$url[1]);
			$ch = curl_init ();
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			if ($style==8) // TJ BA
			{
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
				$img_file_name =  "imagem-$usrId.jpg";
			} else
			{
				$img_file_name =  "imagem-$usrId.gif";
			}
			if ($style==6)
			{
				$header="";
				$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
				$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
				//$header[]="Accept-Encoding: gzip, deflate";
				//$header[]="Cache-Control: no-cache";
				$header[]="Connection: keep-alive";
				$header[]='Referer: http://aplicacoes5.trtsp.jus.br/consultasphp/public/index.php/primeirainstancia';
				$header[]="Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
				$header[]="Host: aplicacoes5.trtsp.jus.br";
				$header[]="DNT: 1";
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_USERAGENT, $this->usrAgent);
				//curl_setopt ($ch, CURLOPT_HEADER, true);
			}

			curl_setopt ($ch, CURLOPT_URL, $url[1]);
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_file);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_file);

			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec ($ch); // the real thing
			$img_data = imagecreatefromstring($output);
			imagegif($img_data, $gPathTmp.$img_file_name);

			$img_link = $http_tmp."/".$img_file_name;
			$sai['link']=$img_link;
			if ($style==2) // MG
			{
				$sai['faces']=$faces;
				$sai['f']=$f;
				$sai['jsessionid']=$jsessionid;
			}
			if ($style==6) // SP
			{
				$sai['sid']=$sid;
				$sai['captchaId']=$captchaId;
			}
			if ($style==32) // TRT Mato Grosso do Sul
			{
				$sai['faces']=$faces;
			}

		}
		return($sai);
	}



	function captchaForm(&$frm, $id_orgaos,$id_grupos,$numero="")
	{
		global $out,$http_tmp,$usrId,$gPathTmp;

		$fez=false;

		// ****************************************************************
		if (($id_orgaos==1) && (!ehPje($numero)) && ($this->captchaManual)) // TRT RJ
		{
			$fez=true;

			$url[0]="http://consulta.trtrio.gov.br/portal/processoListar.do";
			$url[1]="http://consulta.trtrio.gov.br/portal/simplecaptchaimg";
			$res=$this->getCaptcha($url,$id_orgaos);
			$img="<img src=".$res['link'].">";

			//$html->add("{name: processo; fieldLabel: Processo; type: show; value: ".$rs->fields['numero']."}");
			$frm->add("{name: imagem; fieldLabel: Imagem; type: show; value: $img}");
			$frm->add("{name: captcha; fieldLabel: Dígitos da imagem; type: text; maxLength: 5; value:}");
			// $frm->add("{type: hidden; name: jsessionid; value:".$res['jsessionid']."}");
			// $frm->add("{type: hidden; name: faces; value:".$res['faces']."}");
			// $frm->add("{type: hidden; name: f; value:".$res['f']."}");
			$frm->add("{type: hidden; name: id_orgaos; value:".$id_orgaos."}");
			$frm->add("{type: hidden; name: id_grupos; value:".$id_grupos."}");

		}

		if ($id_orgaos==2 && (!ehPje($numero))) // Minas - Captcha
		{
			$fez=true;

			$url[0]="http://as1.trt3.jus.br/consulta/consulta.htm";
			$url[1]="http://as1.trt3.jus.br/consulta/seam/resource/captcha";
			$res=$this->getCaptcha($url,$id_orgaos);
			$img="<img src=".$res['link'].">";

			//$html->add("{name: processo; fieldLabel: Processo; type: show; value: ".$rs->fields['numero']."}");
			$frm->add("{name: imagem; fieldLabel: Imagem; type: show; value: $img}");
			$frm->add("{name: captcha; fieldLabel: Dígitos da imagem; type: text; maxLength: 5; value:}");
			$frm->add("{type: hidden; name: jsessionid; value:".$res['jsessionid']."}");
			$frm->add("{type: hidden; name: faces; value:".$res['faces']."}");
			$frm->add("{type: hidden; name: f; value:".$res['f']."}");
			$frm->add("{type: hidden; name: id_orgaos; value:".$id_orgaos."}");
			$frm->add("{type: hidden; name: id_grupos; value:".$id_grupos."}");
		}
		if ($id_orgaos==5 && date("H")<20 && date("H")>7) // TJ RJ
		{
			$fez=true;
			$url[0]="http://www4.tjrj.jus.br/numeracaoUnica/faces/index.jsp?numProcesso=$numero";
			$url[1]="http://www4.tjrj.jus.br/consultaProcessoWebV2/consultaProc.do?v=2&FLAGNOME=&back=1&tipoConsulta=publica&numProcesso=";
			$url[2]="http://www4.tjrj.jus.br/consultaProcessoWebV2/captcha?v=2";
			$post="";
			$post["N"]="";
			$post["form:btnumero"]="";
			$post["form:btorigem"]="1";
			$post["form:commandButton3"]="Pesquisar";
			$post["form:id"]="";
			$post["form:nprotocolo"]="";
			$post["form:selectOneRadio1"]="1";
			$post["form:tipoConsulta"]="ifp";
			$post["parte1ProcCNJ"]=substr($numero,0,15);
			$post["parte2ProcCNJ"]=substr($numero,15,4);
			$post["selOpcaoNumeracao"]="1";
			$post["tipoUsuario"]="";
			$res=$this->getCaptcha($url,$id_orgaos,$post);
			$img="<img src=".$res['link'].">";

			//$html->add("{name: processo; fieldLabel: Processo; type: show; value: ".$rs->fields['numero']."}");
			$frm->add("{name: imagem; fieldLabel: Imagem; type: show; value: $img}");
			$frm->add("{name: captcha; fieldLabel: Dígitos da imagem; type: text; maxLength: 5; value:}");
			$frm->add("{type: hidden; name: numProcesso; value:".$res['numProcesso']."}");
			$frm->add("{type: hidden; name: id_orgaos; value:".$id_orgaos."}");
			$frm->add("{type: hidden; name: id_grupos; value:".$id_grupos."}");
		}
		if ($id_orgaos==6) // SP - Captcha
		{
			$fez=true;
			$url[0]="http://aplicacoes5.trtsp.jus.br/consultasphp/public/index.php/primeirainstancia";
			$url[1]="http://aplicacoes5.trtsp.jus.br/consultasphp/public/captcha/image.php";
			//$url[1]="http://aplicacoes5.trtsp.jus.br/consultasphp/securimage/securimage_show.php";
			$res=$this->getCaptcha($url,$id_orgaos);
			$img="<img src=".$res['link'].">";

			//$html->add("{name: processo; fieldLabel: Processo; type: show; value: ".$rs->fields['numero']."}");
			$frm->add("{name: imagem; fieldLabel: Imagem; type: show; value: $img}");
			$frm->add("{name: captcha; fieldLabel: Dígitos da imagem; type: text; maxLength: 5; value:}");
			$frm->add("{type: hidden; name: jsessionid; value:".$res['jsessionid']."}");
			$frm->add("{type: hidden; name: faces; value:".$res['faces']."}");
			$frm->add("{type: hidden; name: sid; value:".$res['sid']."}");
			$frm->add("{type: hidden; name: captchaId; value:".$res['captchaId']."}");
			$frm->add("{type: hidden; name: id_orgaos; value:".$id_orgaos."}");
			$frm->add("{type: hidden; name: id_grupos; value:".$id_grupos."}");
		}
		if ($id_orgaos==8) // TJ BA
		{
			$fez=true;
			$url[0]="https://projudi.tjba.jus.br/projudi";
			$url[1]="https://projudi.tjba.jus.br/projudi/captcha.jpg";
			$res=$this->getCaptcha($url,$id_orgaos);
			$img="<img src=".$res['link'].">";

			//$html->add("{name: processo; fieldLabel: Processo; type: show; value: ".$rs->fields['numero']."}");
			$frm->add("{name: imagem; fieldLabel: Imagem; type: show; value: $img}");
			$frm->add("{name: captcha; fieldLabel: Dígitos da imagem; type: text; maxLength: 5; value:}");
			$frm->add("{type: hidden; name: jsessionid; value:".$res['jsessionid']."}");
			$frm->add("{type: hidden; name: faces; value:".$res['faces']."}");
			$frm->add("{type: hidden; name: sid; value:".$res['sid']."}");
			$frm->add("{type: hidden; name: id_orgaos; value:".$id_orgaos."}");
			$frm->add("{type: hidden; name: id_grupos; value:".$id_grupos."}");
		}
		if ($id_orgaos==100009) // Paraná (desativado)
		{
			$fez=true;
			$url[0]="http://www.trt9.jus.br/internet_base/processocnjsel.do";
			$url[1]="http://www.trt9.jus.br/internet_base/processosel.do?evento=ImagemCaptcha";
			$res=$this->getCaptcha($url,$id_orgaos);
			$img="<img src=".$res['link'].">";

			//$html->add("{name: processo; fieldLabel: Processo; type: show; value: ".$rs->fields['numero']."}");
			$frm->add("{name: imagem; fieldLabel: Imagem; type: show; value: $img}");
			$frm->add("{name: captcha; fieldLabel: Dígitos da imagem; type: text; maxLength: 5; value:}");
			$frm->add("{type: hidden; name: jsessionid; value:".$res['jsessionid']."}");
			$frm->add("{type: hidden; name: faces; value:".$res['faces']."}");
			$frm->add("{type: hidden; name: f; value:".$res['f']."}");
			$frm->add("{type: hidden; name: id_orgaos; value:".$id_orgaos."}");
			$frm->add("{type: hidden; name: id_grupos; value:".$id_grupos."}");
		}

		if ($id_orgaos==12) // DF
		{
			$fez=true;
			$url[0]="http://www.trt10.jus.br";
			$url[1]="http://www.trt10.jus.br/lib/imagem.php";
			$res=$this->getCaptcha($url,$id_orgaos);
			$img="<img src=".$res['link'].">";

			//$html->add("{name: processo; fieldLabel: Processo; type: show; value: ".$rs->fields['numero']."}");
			$frm->add("{name: imagem; fieldLabel: Imagem; type: show; value: $img}");
			$frm->add("{name: captcha; fieldLabel: Dígitos da imagem; type: text; maxLength: 6; value:}"); //form1:j_id90
			$frm->add("{type: hidden; name: id_orgaos; value:".$id_orgaos."}");
			$frm->add("{type: hidden; name: id_grupos; value:".$id_grupos."}");
		}
		if ($id_orgaos==31 && !ehPje($numero)) // Rio Grande do Sul
		{
			$fez=true;
			$url[0]="http://www.trt4.jus.br/portal/portal/trt4/consultas/consulta_rapida/ConsultaProcessualWindow?nroprocesso=".$numero."&action=2";
			$url[1]="http://www.trt4.jus.br/consulta-processual-portlet/servlet/GenerateCaptcha";
			$res=$this->getCaptcha($url,$id_orgaos);
			$img="<img src=".$res['link'].">";

			//$html->add("{name: processo; fieldLabel: Processo; type: show; value: ".$rs->fields['numero']."}");
			$frm->add("{name: imagem; fieldLabel: Imagem; type: show; value: $img}");
			$frm->add("{name: captcha; fieldLabel: Dígitos da imagem; type: text; maxLength: 6; value:}"); //form1:j_id90
			$frm->add("{type: hidden; name: jsessionid; value:".$res['jsessionid']."}");
			$frm->add("{type: hidden; name: faces; value:".$res['faces']."}");
			$frm->add("{type: hidden; name: id_orgaos; value:".$id_orgaos."}");
			$frm->add("{type: hidden; name: id_grupos; value:".$id_grupos."}");
		}

		if ($id_orgaos==32) // Mato Grosso do Sul
		{
			$fez=true;
			$url[0]="http://www.trt24.jus.br/www_trtms/pages/ConsultaProcessual.jsf";
			$url[1]="http://www.trt24.jus.br/www_trtms/faces/myFacesExtensionResource/org.apache.myfaces.custom.captcha.CAPTCHARenderer/14248620/?captchaSessionKeyName=mySessionKeyName";
			$res=$this->getCaptcha($url,$id_orgaos);
			$img="<img src=".$res['link'].">";

			//$html->add("{name: processo; fieldLabel: Processo; type: show; value: ".$rs->fields['numero']."}");
			$frm->add("{name: imagem; fieldLabel: Imagem; type: show; value: $img}");
			$frm->add("{name: captcha; fieldLabel: Dígitos da imagem; type: text; maxLength: 6; value:}"); //form1:j_id90
			$frm->add("{type: hidden; name: jsessionid; value:".$res['jsessionid']."}");
			$frm->add("{type: hidden; name: faces; value:".$res['faces']."}");
			$frm->add("{type: hidden; name: id_orgaos; value:".$id_orgaos."}");
			$frm->add("{type: hidden; name: id_grupos; value:".$id_grupos."}");
		}

/*
		if ($id_orgaos==33) // Goiás
		{
			$fez=true;
			$url[0]="http://sistemas.trt18.jus.br/consultasPortal/pages/Processuais/ListaProcessos.seam?p_num_cnj=Numero&p_dig_cnj=Digito&p_ano_cnj=Ano&x=14&y=11";
			$url[1]="https://www.google.com/recaptcha/api/image?";
			$res=$this->getCaptcha($url,$id_orgaos);
			$img="<img src=".$res['link'].">";

			//$html->add("{name: processo; fieldLabel: Processo; type: show; value: ".$rs->fields['numero']."}");
			$frm->add("{name: imagem; fieldLabel: Imagem; type: show; value: $img}");
			$frm->add("{name: captcha; fieldLabel: Dígitos da imagem; type: text; maxLength: 6; value:}"); //form1:j_id90
			$frm->add("{type: hidden; name: jsessionid; value:".$res['jsessionid']."}");
			$frm->add("{type: hidden; name: javax.faces.ViewState; value:".$res['faces']."}");
			$frm->add("{type: hidden; name: id_orgaos; value:".$id_orgaos."}");
			$frm->add("{type: hidden; name: id_grupos; value:".$id_grupos."}");
		}
*/

		//numeroProcessoCNJ
	//	$frm->add("{name: id_orgaos; type: hidden; value: $id_orgao}");
		return($fez);
	}


	/**
	 * Processa página usando captcha informado
	 * @param  integer  $id_orgaos [description]
	 * @param  integer  $id_grupos [description]
	 * @param  boolean $adicionar [description]
	 * @param  string  $numero    [description]
	 * @param  string  $setor     [description]
	 * @return [type]             [description]
	 */
	function captchaProcessa($id_orgaos,$id_grupos, $adicionar=true,$numero="", $setor="")
	{
		$html="";

		$usrId=$_SESSION['usrId'];
		$mascara="#######-##.####.#.##.####";
		if ($numero<>"")
			$numeroOriginal=$numero;
		else
			$numeroOriginal=$_REQUEST['numero'];
		$numero=campoMascara($numeroOriginal,$mascara);
		$jsessionid=$_REQUEST['jsessionid'];
		$faces=$_REQUEST['faces'];
		$captcha=$_REQUEST['captcha'];
		$captchaId=$_REQUEST['captchaId'];
		$id_situacoes=$_REQUEST['id_situacoes'];
		$m="atualizado";
		if ($adicionar)
			$m="adicionado";
		$this->net->html='';
		$post="";
		$semCapctha=false;
		if ($id_orgaos==2 && (!ehPje($numero))) // MG
		{
			$url="http://as1.trt3.jus.br/consulta/consulta.htm;jsessionid=".$jsessionid;
			$post['jsessionid']=$jsessionid;
			$post['javax.faces.ViewState']=$faces;
			$post['campo:j_id18']=$numero;
			$post['campo:j_id20']='';
			$post['campo:verifyCaptcha']=$captcha;
			$post['campo:j_id40']='Pesquisar';
			$post['campo']='campo';

			// $header='';
			// $header['DNT']='1';
			// $header['Host']='as1.trt3.jus.br';
			// $header['Referer']='http://as1.trt3.jus.br/consulta/consulta.htm';
			// $header['User-Agent']=$this->usrAgent;
			// $this->html=$this->processaUrl($url, $header, $post, $numero);
			// if ($this->html<>'')
			if ($this->siteOpen($url,$post,true,true))
			{
				$sql="select id from processos where numero='$numero'";
				$rs=gQuery($sql);
				if ($rs->EOF)
				{
					$pje=0;
					if (ehPje(trim($numero)))
						$pje=1;
					$sql="insert into processos
						(id_pessoas_criou,pje,numero_pasta,data_inicio,data_criacao,numero,sonumero,id_grupos,id_orgaos,id_situacoes) values
						($usrId,$pje,'".geraNumeroInterno($id_grupos)."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."','$numero','".soNumeros($numero)."',$id_grupos,$id_orgaos,".intval($id_situacoes).")";
					gQuery($sql);
				}
				if ($this->html=='')
					$html.="Processo <b>não</b> atualizado: $numero <br>";
				else
					$atualizou=$this->atualiza($numero,$id_orgaos,$id_grupos,$this->html,true);
				if ($atualizou<>'')
				{
					$html.=$atualizou;
					$sql="update processos set erro=1 where numero='".$numero."' or sonumero='".soNumeros($numero)."'";
					//gQuery($sql);
				} else
				{
					$html.="Processo $m: ".$numero;
					$a++;
				}
			} else
			{
				$html.="Processo <b>não</b> $m: ".$numero;
				$a++;
			}
		} elseif ($id_orgaos==1 && (!ehPje($numero)))
		{
			$cookie_file = $this->getCookie();
			$url="http://consulta.trtrio.gov.br/portal/processoListar.do";


			$header="";
			$header[]="Accept: image/png,image/*;q=0.8,*/*;q=0.5";
			$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
			//$header[]="Accept-Encoding: gzip, deflate";
			//$header[]="Cache-Control: no-cache";
			$header[]="Connection: keep-alive";
			$header[]='Referer: http://consulta.trtrio.gov.br/portal/processoListar.do';
			//$header[]="Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
			$header[]="Host: consulta.trtrio.gov.br";
			$header[]="DNT: 1";

			$post="";
			$post['acao']='listarProcessos';
			$post['anoNumeroAntigo']='';
			$post['chaveOrgaoJulgador']='';
			$post['cidadeNumeroAntigo']='';
			$post['codigoCaptcha']=$captcha;
			$post['codigoLetraCarteiraOAB']='';
			$post['codigoUf']='';
			$post['indicadorTipoNumeroProcesso']='CNJ';
			$post['indicadorTipoNumeroProcessoAnterior']='';
			$post['nomeAdvogado']='';
			$post['numeroAntigo']='';
			$post['numeroProcesso2009']='';
			$post['numeroProcessoCNJ']=$numero;
			$post['numeroRegistroOAB']='';
			$post['paginacao']='false';
			$post['scrollTop']='0';
			$post['tipoNumeroAntigo1']='';
			$post['tipoNumeroAntigo2']='';
			$post['tipoRecurso']='';
			$post['varaNumeroAntigo']='';

			gLog("---> Abrindo URL com captcha: ".$url);

			$ch = curl_init ();
			curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_USERAGENT, $this->usrAgent);
			//curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($post));
			$html1 = curl_exec ($ch); // the real thing
			file_put_contents('/tmp/giu.html', $html1);

			if (strpos($html1,'captcha incorreto')===false)
			{

				$sql="select id from processos where numero='$numero'";
				$rs=gQuery($sql);
				if ($rs->EOF)
				{
					$pje=0;
					$sql="insert into processos
						(id_pessoas_criou,pje,numero_pasta,data_inicio,data_criacao,numero,sonumero,id_grupos,id_orgaos,id_situacoes) values
						($usrId,$pje,'".geraNumeroInterno($id_grupos)."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."','$numero','".soNumeros($numero)."',$id_grupos,$id_orgaos,".intval($id_situacoes).")";
					gQuery($sql);
				}
				//$this->html='';
				$atualizou=$this->atualiza($numero,$id_orgaos,$id_grupos,$html1,true);
				if ($atualizou<>'')
				{
					$html.=$atualizou;
					$sql="update processos set erro=1 where numero='".$numero."' or sonumero='".soNumeros($numero)."'";
					//gQuery($sql);
				} else
				{
					$html.="Processo $m: ".$numero."<br><br>".$net->lastError;
					$a++;
				}
			} else
			{
				$net->lastError="Captcha incorreto!";
				$html.="Processo <b>não</b> $m: ".$numero."<br><br>".$net->lastError;
				$a++;
			}
		} elseif ($id_orgaos==5) // TJ RJ
		{
			if ($captcha<>"")
			{
				// Primeiro, destrava o captcha
				$url="http://www4.tjrj.jus.br/consultaProcessoWebV2/consultaProc.do?captcha=$captcha&numProcesso=".trim($_REQUEST['numProcesso']);
			}
			else
				$url="http://www4.tjrj.jus.br/consultaProcessoWebV2/consultaProc.do?numProcesso=".trim($_REQUEST['numProcesso']);
			$post="";

			if ($this->siteOpen($url,$post,true,true))
			{
				$html1=$this->html;
				$url="http://www4.tjrj.jus.br/numeracaoUnica/faces/index.jsp?&numProcesso=".$numero;
				$this->siteOpen($url,$post,true,true);
				$html2=$this->html;
				$sql="select id from processos where numero='$numero'";
				$rs=gQuery($sql);
				if ($rs->EOF)
				{
					$pje=0;
					if (ehPje($numero))
						$pje=1;
					$sql="insert into processos
						(id_pessoas_criou,pje,numero_pasta,data_inicio,data_criacao,numero,sonumero,id_grupos,id_orgaos,id_situacoes) values
						($usrId,$pje,'".geraNumeroInterno($id_grupos)."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."','$numero','".soNumeros($numero)."',$id_grupos,$id_orgaos,".intval($id_situacoes).")";
					gQuery($sql);
				}
				//$this->html='';
				$atualizou=$this->atualiza($numero,$id_orgaos,$id_grupos,$html1,true);
				if ($atualizou<>'')
				{
					$html.=$atualizou;
					$sql="update processos set erro=1 where numero='".$numero."' or sonumero='".soNumeros($numero)."'";
					//gQuery($sql);
				} else
				{
					$html.="Processo $m: ".$numero."<br><br>".$net->lastError;
					$a++;
				}

				$atualizou=$this->atualiza($numero,$id_orgaos,$id_grupos,$html2,true);

			} else
			{
				$html.="Processo <b>não</b> $m: ".$numero."<br><br>".$net->lastError;
				$a++;
			}
		} elseif ($id_orgaos==6) // SP
		{
			$url="http://aplicacoes5.trtsp.jus.br/consultasphp/public/index.php/primeirainstancia";
//			$post['ct_captcha']=$captcha;

			$post['captcha[id]']=$captchaId;
			$post['captcha[input]']=$captcha;
			$post['processo']=str_replace("-","",str_replace("/","",str_replace(".","",$numero)));

			if ($this->siteOpen($url,$post,true,true))
			{
				//echo "dados: jsessionid: $jsessionid faces: $faces captcha: $captcha<br><pre>".$net->html;exit;
				$sql="select id from processos where numero='$numero'";
				$rs=gQuery($sql);
				if ($rs->EOF)
				{
					$pje=0;
					if (ehPje($numero))
						$pje=1;
					$sql="insert into processos
						(id_pessoas_criou,pje,numero_pasta,data_inicio,data_criacao,numero,sonumero,id_grupos,id_orgaos,id_situacoes) values
						($usrId,$pje,'".geraNumeroInterno($id_grupos)."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."','$numero','".soNumeros($numero)."',$id_grupos,$id_orgaos,".intval($id_situacoes).")";
					gQuery($sql);
				}
				if ($this->html=='')
					$html.="Processo <b>não</b> atualizado: $numero <br>";
				else
					$atualizou=$this->atualiza($numero,$id_orgaos,$id_grupos,$this->html,true);
				if ($atualizou<>'')
				{
					$html.=$atualizou;
					$sql="update processos set erro=1 where numero='".$numero."' or sonumero='".soNumeros($numero)."'";
					//gQuery($sql);
				} else
				{
					$html.="Processo $m: ".$numero."<br><br>".$net->lastError;
					$a++;
				}
			} else
			{
				$html.="Processo <b>não</b> $m: ".$numero."<br><br>".$net->lastError;
				$a++;
			}
		} elseif ($id_orgaos==8) // TJ BA
		{
			$url="https://projudi.tjba.jus.br/projudi/buscas/ProcessosParte";

			//$url="https://projudi.tjba.jus.br/projudi/buscas/ProcessosParte?captcha=$captcha&nome=&numeroProcesso=$numero";
			$post['numeroProcesso']=$numero;
			$post['nome']="";
			$post['captcha']=$captcha;

			$usrAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:19.0) Gecko/20100101 Firefox/19.0";
			$cookie_file = $this->getCookie();

			gLog("---> Abrindo URL: $url");
			gLog("---> Captcha: $captcha - $numero - $cookie_file");


			$header="";
			$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
			$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
			//$header[]="Accept-Encoding: gzip, deflate";
			//$header[]="Cache-Control: no-cache";
			$header[]="Connection: keep-alive";
			$header[]='Referer: https://projudi.tjba.jus.br/projudi/PaginaPrincipal.jsp';
			$header[]="Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
			$header[]="Host: projudi.tjba.jus.br";


			$ch = curl_init();
			curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			//curl_setopt($ch,CURLOPT_ENCODING , "gzip");
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($post));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_USERAGENT, $usrAgent);
//			curl_setopt ($ch, CURLOPT_VERBOSE, true);


			$ok= curl_exec($ch);
			$this->html =$ok;

			//if ($this->siteOpen($url,$post,true,true))
			if (strpos($ok,"DADOS DO PROCESSO")!==false)
			{
				//echo "dados: jsessionid: $jsessionid faces: $faces captcha: $captcha<br><pre>".$net->html;exit;
				$sql="select id from processos where numero='$numero'";
				$rs=gQuery($sql);
				if ($rs->EOF)
				{
					$sql="insert into processos
						(id_pessoas_criou,pje,numero_pasta,data_inicio,data_criacao,numero,sonumero,id_grupos,id_orgaos,id_situacoes) values
						($usrId,0,'".geraNumeroInterno($id_grupos)."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."','$numero','".soNumeros($numero)."',$id_grupos,$id_orgaos,".intval($id_situacoes).")";
					gQuery($sql);
				}
				if ($this->html=='')
					$html.="Processo <b>não</b> atualizado: $numero <br>";
				else
					$atualizou=$this->atualiza($numero,$id_orgaos,$id_grupos,$this->html,true);
				if ($atualizou<>'')
				{
					$html.=$atualizou;
					$sql="update processos set erro=1 where numero='".$numero."' or sonumero='".soNumeros($numero)."'";
					//gQuery($sql);
				} else
				{
					$html.="Processo $m: ".$numero."<br><br>".$net->lastError;
					$a++;
				}
			} else
			{
				$html.="Processo <b>não</b> $m: ".$numero."<br><br>".$net->lastError;
				$a++;
			}

		} elseif ($id_orgaos==100009) // PR (desativado)
		{
//http://www.trt9.jus.br/internet_base/processocnjsel.do#
			//redirect('/internet_base/processoman.do?evento=Editar&chPlc=AAAS41ABaAAJSCbAAI')
			$url="http://www.trt9.jus.br/internet_base/processocnjsel.do;jsessionid=".$jsessionid;

			$post['jsessionid']=$jsessionid;
			//$post['javax.faces.ViewState']=$faces;
			//$post['campo:verifyCaptcha']=$captcha;
			$post['modoPlc']='consultaPlc';
			$post['evento']='F9-Pesquisar';
			$post['unicoCnjAno_Arg']=substr($numero,11,4);
			$post['unicoCnjDigito_Arg']=substr($numero,8,2);
			$post['unicoCnjJustica_Arg']='5';
			$post['unicoCnjNumero_Arg']=substr($numero,0,7);
	//$net->siteOpen($url,$post,true,true);

			if ($this->siteOpen($url,$post,true,true))
			{
				$sql="select id from processos where numero='$numero'";
				$rs=gQuery($sql);
				if ($rs->EOF)
				{
					$pje=0;
					if (ehPje($numero))
						$pje=1;
					$sql="insert into processos
						(id_pessoas_criou,pje,numero_pasta,data_inicio,data_criacao,numero,sonumero,id_grupos,id_orgaos,id_situacoes) values
						($usrId,$pje,'".geraNumeroInterno($id_grupos)."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."','$numero','".soNumeros($numero)."',$id_grupos,$id_orgaos,".intval($id_situacoes).")";
					gQuery($sql);
				}
				if ($this->html=='')
					$html.="Processo <b>não</b> atualizado: $numero <br>";
				else
					$atualizou=$this->atualiza($numero,$id_orgaos,$id_grupos,$this->html,true);
				if ($atualizou<>'')
				{
					$html.=$atualizou;
					$sql="update processos set erro=1 where numero='".$numero."' or sonumero='".soNumeros($numero)."'";
					//gQuery($sql);
				} else
				{
					$html.="Processo $m: ".$numero;
					$a++;
				}
			}
		} elseif ($id_orgaos==12) // DF
		{
			$url="http://www.trt10.jus.br/index.php";
			$post['ano']='';
			$post['num']='';
			$post['vara']='';
			$post['num_II']=substr($numero,0,7);
			$post['ano_II']=substr($numero,11,4);
			$post['vara_II']=substr($numero,22,3);
			$post['captcha']=$captcha;
			$post['mod']='servicos/consultasap/validar_caracteres.php';

			if ($this->siteOpen($url,$post,true,true))
			{
				$sql="select id from processos where numero='$numero'";
				$rs=gQuery($sql);
				if ($rs->EOF)
				{
					$pje=0;
					if (ehPje($numero))
						$pje=1;
					$sql="insert into processos
						(id_pessoas_criou,pje,numero_pasta,data_inicio,data_criacao,numero,sonumero,id_grupos,id_orgaos,id_situacoes) values
						($usrId,$pje,'".geraNumeroInterno($id_grupos)."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."','$numero','".soNumeros($numero)."',$id_grupos,$id_orgaos,".intval($id_situacoes).")";
					gQuery($sql);
				}

				if ($this->html=='')
					$html.="Processo <b>não</b> atualizado: $numero <br>";
				else
					$atualizou=$this->atualiza($numero,$id_orgaos,$id_grupos,$this->html,true);
				if ($atualizou<>'')
				{
					$html.=$atualizou;
					$sql="update processos set erro=1 where numero='".$numero."' or sonumero='".soNumeros($numero)."'";
					//gQuery($sql);
				} else
				{
					$html.="Processo $m: ".$numero;
					$a++;
				}

			} else
			{
				$html.="Processo <b>não</b> $m: ".$numero;
				$a++;
			}
		} elseif ($id_orgaos==31 && !ehPje($numero)) // RS (Rio Grande do Sul)
		{
			$url="http://www.trt4.jus.br/portal/portal/trt4/consultas/consulta_rapida/ConsultaProcessualWindow?action=1";

			$post['acao']='lista';
			$post['hidden_nroprocesso']=$numero;
			$post['nroprocesso']=$numero;
			$post['operation']='doConsultaRapida';
			$post['reescreveValores']='S';
			$post['svc']='consultaBean';
			$post['captcha_senha']=$captcha;
			$post['controle']='1';

			if ($this->siteOpen($url,$post,true,true))
			{
				$sql="select id from processos where numero='$numero'";
				$rs=gQuery($sql);
				if ($rs->EOF)
				{
					$pje=0;
					if (ehPje($numero))
						$pje=1;
					$sql="insert into processos
						(id_pessoas_criou,pje,numero_pasta,data_inicio,data_criacao,numero,sonumero,id_grupos,id_orgaos,id_situacoes) values
						($usrId,$pje,'".geraNumeroInterno($id_grupos)."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."','$numero','".soNumeros($numero)."',$id_grupos,$id_orgaos,".intval($id_situacoes).")";
					gQuery($sql);
				}
				if ($this->html=='')
					$html.="Processo <b>não</b> atualizado: $numero <br>";
				else
				{
					$url="http://www.trt4.jus.br/portal/portal/trt4/consultas/consulta_rapida/ConsultaProcessualWindow?svc=consultaBean&nroprocesso=".$numero."&operation=doProcesso&action=2&intervalo=90";
					$post='';
					$this->html='';
					$this->siteOpen($url,$post,true,true);
					$atualizou=$this->atualiza($numero,$id_orgaos,$id_grupos,$this->html,true);
				}
				if ($atualizou<>'')
				{
					$html.=$atualizou;
					$sql="update processos set erro=1 where numero='".$numero."' or sonumero='".soNumeros($numero)."'";
					//gQuery($sql);
				} else
				{
					$html.="Processo $m: ".$numero;
					$a++;
				}


			} else
			{
				$html.="Processo <b>não</b> $m: ".$numero;
				$a++;
			}
		} elseif ($id_orgaos==32) // MS
		{
			$url="http://www.trt24.jus.br/www_trtms/pages/ConsultaProcessual.jsf";

			$post['form1']='form1';
			$post['form1:cnj_numero']=substr($numero,0,7);
			$post['form1:cnj_dv']=substr($numero,8,2);
			$post['form1:cnj_ano']=substr($numero,11,4);
			$post['form1:cnj_just']='5';
			$post['form1:cnj_trib']='24';
			$post['form1:cnj_origem']=substr($numero,21,4);
			$post['form1:j_id90']=$captcha;
			$post['form1:j_id86']='Consultar';
			$post['javax.faces.ViewState']=$faces;

			if ($this->siteOpen($url,$post,true,true))
			{
				$sql="select id from processos where numero='$numero'";
				$rs=gQuery($sql);
				if ($rs->EOF)
				{
					$pje=0;
					if (ehPje($numero))
						$pje=1;
					$sql="insert into processos
						(id_pessoas_criou,pje,numero_pasta,data_inicio,data_criacao,numero,sonumero,id_grupos,id_orgaos,id_situacoes) values
						($usrId,$pje,'".geraNumeroInterno($id_grupos)."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."','$numero','".soNumeros($numero)."',$id_grupos,$id_orgaos,".intval($id_situacoes).")";
					gQuery($sql);
				}
				if ($this->html=='')
					$html.="Processo <b>não</b> atualizado: $numero <br>";
				else
					$atualizou=$this->atualiza($numero,$id_orgaos,$id_grupos,$this->html,true);
				if ($atualizou<>'')
				{
					$html.=$atualizou;
					$sql="update processos set erro=1 where numero='".$numero."' or sonumero='".soNumeros($numero)."'";
					//gQuery($sql);
				} else
				{
					$html.="Processo $m: ".$numero;
					$a++;
				}


			} else
			{
				$html.="Processo <b>não</b> $m: ".$numero;
				$a++;
			}
		} else
		{
			$semCapctha=true;
			// Tribunais sem captcha
			$sql="select * from orgaos where id=$id_orgaos";
			$rs=gQuery($sql);
			// Processo usa numeração CNJ?
			if ($rs->fields['numeracao_livre']==1)
			{
				$numero=$numeroOriginal;
			}
			$this->lastError='';
			$html=$this->atualiza($numero,$id_orgaos,$id_grupos,'',true,$id_situacoes,false, $setor);
			if ($this->lastError<>'')
			{
				$html.="Processo $numero não foi atualizado.<br><br><b>".$this->lastError."</b>";
			} else
			{
				// Verificando se o processo foi realmente incluído...
				$sql="select id from processos where numero='$numero'";
				$rs=gQuery($sql);
				if (!$rs->EOF)
				{
					$html.="Processo $numero $m com sucesso";
				} else
				{
					$html.="Processo $numero não foi $m.";
				}

			}

		}
		if ($semCapctha)
			gLog("---> Sem captcha - Processado: ".strip_tags($html));
		else
			gLog("---> Captcha processado: ".strip_tags($html));
		return($html);
	}



	// ==========================================================================
	// Processamentos
	// ==========================================================================

	function processaJavascript($html,$baseUrl='', $getCookie = false)
	{
		global $usrId;

		$jsCommand='/usr/local/bin/js17';

//echo "\n\n\n".htmlentities($html);exit;
		// Identifica se o código HTML realmente tem um bloqueio em Javascript (para ser executado somente por um browser real)
		if (stripos($html,"decode_action()")!==false)
		{
			gLog("===> Bloqueio por Javascript encontrado....");
			// Extrai a URL
			$url=substr($html,stripos($html,"action=")+8);
			$url=substr($url,0,stripos($url,'"'));
			$url=str_replace('br//','br/',urldecode($baseUrl.$url));

			gLog("===> Redirecionar para: $url");
			// Extrai código javascript
			$script=substr($html,stripos($html,"<script"));
			$script=substr($script,stripos($script, ">")+1);
			$script=substr($script,0,strpos($script,'</'));


			// Extrai campos ocultos
			$campos=$html;
			for ($a=0; $a<6; $a++)
			{
				$campos=substr($campos,stripos($campos,'name=')+6);
				$campo=substr($campos,0,stripos($campos,'"'));
				$campos=substr($campos,stripos($campos,'value=')+7);
				$valor=substr($campos,0,stripos($campos,'"'));
				$hidden[$campo]=$valor;
			}

			// Modifica código javascript
			$campo=substr($script,stripos($script,'value=')+6);
			$campo=substr($campo,0,stripos($campo,';'));
			$script=substr($script,0,strrpos($script,"document.forms"));
			//$script=substr($script,stripos($script,"function test()"));
			$script.="print(decode_string($campo));\n";
			$script.="}\ntest();";
			$script=str_replace("\r","",$script);
			$fileName="/tmp/alitem-trtrj-$usrId-".$_SERVER['SERVER_ADDR'].".js";
			$fileNameLog="/tmp/alitem-trtrj-$usrId-".$_SERVER['SERVER_ADDR'].".log";
//echo "=>\n\n\n".$script."\n\n\n=>";exit;
			file_put_contents($fileName, $script);
			if (file_exists($jsCommand))
			{
				$cmd=$jsCommand." -f $fileName > $fileNameLog";
				$sai=shell_exec($cmd);
				$string=trim(file_get_contents($fileNameLog));

				$post="";
				$cnt=0;
				foreach ($hidden as $key=>$value)
				{
					if ($cnt==1)
						$post[$key]=$string;
					else
						$post[$key]=$value;
					$cnt++;
				}
				$ch = curl_init();
				$defaults = array(
					CURLOPT_INTERFACE => $_SERVER['SERVER_ADDR'],
					CURLOPT_POST => 1,
					CURLOPT_AUTOREFERER => true,
	//						CURLOPT_HEADER => 0,
					CURLOPT_URL => $url,
					CURLOPT_FRESH_CONNECT => 1,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_FORBID_REUSE => 1,
					CURLOPT_TIMEOUT => 15,
					CURLOPT_COOKIEFILE=> $ckfile,
					CURLOPT_POSTFIELDS => http_build_query($post)
				);
				curl_setopt_array($ch, $defaults);
				if ($getCookie)
					curl_setopt ($ch, CURLOPT_COOKIEJAR, $this->getCookie());
				$html=curl_exec ($ch);
			} else
			{
				gLog("===> Executor de javascript não encontrado! Procedimento abortado! $jsCommand");
			}
			unlink($fileName);
			unlink($fileNameLog);
//echo "url: $url<br>\n\nhtml 2:\n\n\n\n\n\n$html";exit;
		}
		return($html);
	}




	function processaPJeCaptcha($trt,$numero,$grau="primeirograu")
	{
		global $out,$http_tmp,$usrId,$usrIdd, $gPathTmp;

		$http=$this->PJeHttp;
		$host="pje";
		if ($trt=='trt1')
			$host="consultapje";

		$sql="select * from parametros where idd=".$usrIdd;
		$rs=gFastQuery($sql);
		$em=$rs->fields['pje_acesso'];

		gLog("===> Processando PJeCaptcha [$em] $numero >>>>>>>>>>>>>>>>>>>>>>>>>>>> ");

		$sql="update parametros set pje_acesso=".time()." where idd=".$usrIdd;
		gFastQuery($sql);

		$ok=true;
		$post='';
		$cookie=false;
		$debug=0;
		$jaExiste=false;
		$tamMin=4;

		$this->trt=$trt;


		$usrAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:19.0) Gecko/20100101 Firefox/19.0";
		$usrAgent = $this->usrAgent;
		$cookie=true;

		$urlCookie=$http."://$host.$trt.jus.br/consultaprocessual/pages/consultas/ConsultaProcessual.seam";
		$urlConsulta=$http."://$host.$trt.jus.br/consultaprocessual/pages/consultas/ConsultaProcessual.seam";

		$ok = $this->getPJeCookie($urlCookie);
		if ($ok)
		{
			$html=$this->processaPJeCaptcha_envia($urlConsulta, $numero, $usrAgent);
			if ($html=="")
			{
				$ok=false;
				$this->lastError="A consulta ao PJe não retornou dados válidos. Tente novamente mais tarde.";
				gLog("===> PJe: ".$this->lastError);
			} elseif (substr($html,0,6)=="varios")
			{
				$ok=substr($html,7);
			} else
			{
				if (strpos($html, 'inesperada aconteceu')!==false)
				{
					$ok=false;
					$this->lastError="A consulta ao PJe retornou um erro inesperado. Tente novamente mais tarde.";
					gLog("===> PJe: ".$this->lastError);
				} else
				{
					$grau = 1;
					if (strpos($html, 'Detalhes do Processo de 2') !== false)
						$grau = 2;
					$ok=$this->processaPJe_html($numero, $html, $grau);
					//file_put_contents('/tmp/giu.html', $html);
				}
			}
		}

		return($ok);
	}

	function processaPJeCaptcha_envia($url, $numero, $usrAgent)
	{
		$ok=true;
		$cookie=true;
		$redir='';
		$ckfile=$this->getCookie();
		$trt=$this->trt;
		$http=$this->PJeHttp;
		$host="pje";
		if ($trt=='trt1')
			$host="consultapje";

		gLog("===> PJeCaptcha: Enviando parametros: $url (viewState ".$this->viewState." - ckfile $ckfile)");


		/*

		Resumo (em 29/04/2015):

		PASSO 1 - ConsultaProcessos - Busca o processo. Retornará com erro.
			REDIR 1 - errorSession
		PASSO 2 - ConsultaProcessos - Volta pra ConsultaProcesso e recebe o cookie.
		PASSO 3 - ConsultaProcessos - Busca o processo novamente. Redirecionará duas vezes:
			REDIR 1 - ListaProcessos
			REDIR 2 - DetalhaProcessos

		*/

		gLog("===== [  P A S S O   -  1  ] =====");


		$post='';

		$post['consultaProcFormFormatForm']="consultaProcFormFormatForm";
		switch ($trt)
		{
			case "trt1":
				$post['j_id49']="true";
				break;
			case "trt4":
				$post['j_id51']="true";
				break;
			case "trt18":
				$post['j_id59']="true";
				break;
			default:
				$post['j_id52']="true";
		}

		$post['numeroFormatDecorate:numero_format']=$numero;
		$post['consultarNumFormat']="Pesquisar";
		$post['javax.faces.ViewState']=$this->viewState;

		$header="";
		$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
		$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
		$header[]="Connection: keep-alive";
		$header[]="Referer: ".$http."://$host.$trt.jus.br/consultaprocessual/pages/consultas/ConsultaProcessual.seam";
		$header[]="DNT: 1";
		$header[]="Host: $host.$trt.jus.br";


		$arq="===> Numero: $numero\n";
		$arq.="===> Data: ".date("Y-m-d H:i:s")."\n";
		$arq.="===> URL: $url\n";
		$s="";
		foreach ($post as $key=>$row)
		{
			$s.=$key."=".$row."\n";
		}
		$arq.="===> Post: \n$s\n";
		gLog("===> URL (1) ============= ".str_replace("\n"," | ",$s));

		$cookie=true;
		$baseUrl=substr($url,0,stripos($url,'br/')+3);
		$ckfile=$this->getCookie();

		// Chamando URL
		$bodyData = http_build_query($post);
		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
		//if ($trt=="trt1")
		{
			$boundary=md5(time());
			$bodyData = '';
			foreach($post as $key => $value){
				$bodyData.= "--$boundary\r\nContent-Disposition: form-data; name=\"$key\"\r\n\r\n$value\r\n";
			}
			$bodyData.= "--$boundary--";
			$header[]="Content-Type: multipart/form-data; boundary=$boundary";
			//$header[]="Content-Length: ".strlen($bodyData);
			$header[]="Accept-Encoding: gzip, deflate";
			curl_setopt ($ch, CURLOPT_ENCODING, 'gzip, deflate');
			$arq.="===> Body Data:\n".$bodyData."\n\n";
		}
		$s="";
		foreach ($header as $key=>$row)
		{
			$s.=$row."\n";
		}
		$arq.="===> Header: \n$s";
		$arq.="User-Agent: ".$this->usrAgent."\n\n";
		gLog("===> URL (2) Headers: ".str_replace("\n"," | ",$s));


		if (strpos($url,"https")!==false)
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		if (is_array($header))
			curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
		curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);

		if (is_array($post))
		{
			curl_setopt ($ch, CURLOPT_POST, true);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
		}
		//curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt ($ch, CURLOPT_VERBOSE, true);
		curl_setopt ($ch, CURLOPT_HEADER, true);

		$html = curl_exec ($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		// Notou-se em Novembro de 2014 que o servidor do PJe retorna vários HEADERs pra confundir o robo
		// O http_code obtido através da função do PHP (acima) retorna um código falso!
		// O último HEADER é o que deve ser considerado
		$codes=explode('HTTP/1.1', $html);
		$http_code='';
		foreach ($codes as $code)
		{
			$code=trim($code);
			if (substr($code,0,4)=='302 ')
				$http_code='302';
			if (substr($code,0,4)=='200 ' && $http_code=='')
				$http_code='200';
		}

		$arq.="===> Codigo de resposta: $http_code\n";

		$arq.=str_replace("\r","",$html);

		// Se encontrar 302, significa que o servidor redirecionou a página para outro endereço
		// O código abaixo identifica esta página e redireciona automaticamente, buscando os respectivos dados
		if ($http_code == '302')
		{
			$headers = explode("\n",$html);
			$j = count($headers);
			$redir=$url;
			for($i = 0; $i < $j; $i++){
				if(strpos($headers[$i],"Location:") !== false)
				{
					$redir = trim(str_replace("Location:","",$headers[$i]));
					break;
				}
			}
			curl_close ($ch);
			$this->lastRedir=$redir;
			$arq.="\n===> Redirecionamento: $redir\n\n";
			gLog("===> URL (3) Redir: $redir");
			$ch = curl_init ($redir);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			if (strpos($redir,"https")!==false)
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout em segundos
			curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
			curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);
			curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_VERBOSE, true);
			curl_setopt ($ch, CURLOPT_HEADER, true);
			$html = curl_exec ($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$headers = explode("\n",$html);
			$j = count($headers);
			$redir=$url;
			for($i = 0; $i < $j; $i++){
				if(strpos($headers[$i],"Location:") !== false)
				{
					$redir = trim(str_replace("Location:","",$headers[$i]));
					break;
				}
			}
			curl_close ($ch);

			// Descobrindo os códigos de retorno à requisição e considerando somente o último
			$codes=explode('HTTP/1.1', $html);
			$http_code='';
			foreach ($codes as $code)
			{
				$code=trim($code);
				if (substr($code,0,4)=='302 ')
					$http_code='302';
				if (substr($code,0,4)=='200 ' && $http_code=='')
					$http_code='200';
			}
			$arq.="===> Codigo de resposta: $http_code\n";
			$arq.=str_replace("\r","",$html);



			if ($http_code=="302")
			{
				$this->lastRedir=$redir;
				$arq.="\n===> Redirecionamento: $redir\n\n";
				gLog("===> URL (5) Redir: $redir");
				$ch = curl_init ($redir);
				curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
				if (strpos($redir,"https")!==false)
					curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout em segundos
				curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
				curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);
				curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
				curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
				curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt ($ch, CURLOPT_VERBOSE, true);
				curl_setopt ($ch, CURLOPT_HEADER, true);
				$html = curl_exec ($ch);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close ($ch);

				// Descobrindo os códigos de retorno à requisição e considerando somente o último
				$codes=explode('HTTP/1.1', $html);
				$http_code='';
				foreach ($codes as $code)
				{
					$code=trim($code);
					if (substr($code,0,4)=='302 ')
						$http_code='302';
					if (substr($code,0,4)=='200 ' && $http_code=='')
						$http_code='200';
				}
				$arq.="===> Codigo de resposta: $http_code\n";
				$arq.=str_replace("\r","",$html);

			}
		}
		$arq.="\n\n\n\n";
		file_put_contents("/tmp/alitem-url-debug-".$_SERVER['SERVER_ADDR'].".log", $arq);


		if (strpos($html, 'Request Rejected')!==false)
		{
			gLog("===> PJe: Requisição rejeitada!");
			$ok=false;
			$html='';
			$this->lastError="Não é possível acessar o site do PJe no momento. Aguarde alguns minutos e tente novamente.";
		}

		if ($ok)
		{

			gLog("===== [  P A S S O   -  2  ] =====");



			$header="";
			$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
			$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
			$header[]="Connection: keep-alive";
			$header[]="Referer: ".$this->lastRedir;
			$header[]="DNT: 1";
			$header[]="Host: $host.$trt.jus.br";

			$ch = curl_init ($url);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);
			curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
			curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_HEADER, 1);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
			$html = curl_exec ($ch);
			curl_close ($ch);

			if (stripos($html,"Detectada utilizacao excessiva")!==false )
			{
				gLog("===> PJe: Detectada utilização excessiva!");
				$ok=false;
				$html='';
				$this->lastError="Não é possível acessar o site do PJe no momento. Aguarde alguns minutos e tente novamente.";
			}

			if ($ok)
			{

				gLog("===== [  P A S S O   -  3  ] =====");


				$html=$this->processaUrl($url, $header, $post, $numero, true);


				$baseUrl=substr($url,0,stripos($url,'br/')+3);

				if (stripos($html, 'Processos Encontrados') !== false)
				{
					$this->maisDeUm=true;
					gLog("===> PJe: Retornou mais de uma instância ".strlen($html)." caracteres - código: " . $http_code);
					gLog("===> PJe: Limpando dados - mais de um grau encontrado");
					$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros

					//<img src="/consultaprocessual/img/proc1g_32.png" style="border:0; padding-right:3px; vertical-align: middle;width:23px;">RTOrd-0010297-13.2013.5.01.0049
					//<a href="/consultaprocessual/pages/consultas/DetalhaProcesso.seam?p_seq=10297&amp;p_dig=13&amp;p_ano=2013&amp;p_vara=49&amp;p_num_pje=41222&amp;p_grau_pje=1&amp;dt_autuacao=02%2F04%2F2013&amp;conversationPropagation=begin" id="consultaProcs:0:procId" style="font-size: 14px;" class="linkProcesso"><img src="/consultaprocessual/img/proc1g_32.png" style="border:0; padding-right:3px; vertical-align: middle;width:23px;">RTOrd-0010297-13.2013.5.01.0049</a>
					$faz=true;
					$htmlOrig=$html;
					$cnt=0;
					$referer=$url;
					while ($faz)
					{
						$p=strpos($htmlOrig,'<a href="/consultaprocessual/pages/consultas/DetalhaProcesso.seam?p_seq=');
						$ok='';
						if ($p>0)
						{
							$cnt++;
							$grau_pje=1;
							$pg=strpos($htmlOrig,'p_grau_pje=');
							if ($pg!==false)
							{
								$grau_pje=substr($htmlOrig,$pg+11,1);
							}
							$p=strpos($htmlOrig,'"',$p)+2;
							$htmlOrig=substr($htmlOrig,$p);
							$pFim=strpos($htmlOrig,'"');
							$url=$baseUrl.substr($htmlOrig,0,$pFim);
							$htmlOrig=substr($htmlOrig,$pFim+1);
							$url=str_replace('&amp;','&',$url);
							gLog("===> PJe: Varios ($cnt) grau $grau_pje => Processando URL: $url");



							$html=$this->processaUrl($url, $header, "", $numero);


							// $ch = curl_init ($url);

							// if (strpos($url,"https")!==false)
							// 	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
							// curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
							// curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
							// curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);
							// curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
							// curl_setopt ($ch, CURLOPT_POST, false);
							// //curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
							// //curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
							// curl_setopt ($ch, CURLOPT_REFERER, $referer);

							// curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
							// curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
							// //curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

							// curl_setopt ($ch, CURLOPT_VERBOSE, true);
							// curl_setopt ($ch, CURLOPT_HEADER, true);

							// $html = curl_exec ($ch);
							// $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							// if ($http_code == '302')
							// {
							// 	//file_put_contents('/tmp/response.html', $html);exit;
							// 	$headers = explode("\n",$html);
							// 	$j = count($headers);
							// 	$redir=$url;
							// 	for($i = 0; $i < $j; $i++){
							// 	// if we find the Location header strip it and fill the redir var
							// 		if(strpos($headers[$i],"Location:") !== false)
							// 		{
							// 			$redir = trim(str_replace("Location:","",$headers[$i]));
							// 			break;
							// 		}
							// 	}
							// 	curl_close ($ch);

							// 	gLog("===> PJe: Varios ($cnt) => Redirecionamento (1): $redir");
							// 	$ch = curl_init ($redir);
							// 	if (strpos($redir,"https")!==false)
							// 		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
							// 	curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
							// 	curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
							// 	curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);
							// 	curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
							// 	//curl_setopt ($ch, CURLOPT_POST, true);
							// 	//curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
							// 	curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
							// 	//curl_setopt ($ch, CURLOPT_REFERER, $referer);
							// 	curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
							// 	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
							// 	curl_setopt ($ch, CURLOPT_VERBOSE, true);
							// 	curl_setopt ($ch, CURLOPT_HEADER, true);
							// 	$html = curl_exec ($ch);
							// 	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							// }


		//echo "Dados:<br>$url<br>$bodyData<pre>";htmlentities(print_r($response)); print_r($post);echo "</pre>";echo "<br><br>\n\n\n\n\n\nHTML:\n\n[ ".htmlentities($html)." ]\n\n\n\n\n\n";exit;

							gLog("===> PJe: Varios ($cnt) => Retornou ".strlen($html)." caracteres - código: " . $http_code);
							$html=$this->processaJavascript($html,$baseUrl);
							$ok.=$this->processaPJe_html($numero,$html,$grau_pje);
						} else
						{
							$faz=false;
							if ($cnt==0)
								$html='varios;'.$ok;
						}
					}


				} else
				{
					gLog("===> PJe: Retornou ".strlen($html)." caracteres ");

					$html=$this->processaJavascript($html,$baseUrl);
				}
			}

		}
		//@file_put_contents('/var/www/html/alitem/pub/tmp/atualiza.html', $html);
		return($html);

	}













	// Só está sendo usado pro PJe TRT RJ
	function processaPJe($trt,$numero,$grau="primeirograu")
	{
		global $out,$http_tmp,$usrId,$usrIdd, $gPathTmp;

		$sql="select * from parametros where idd=".$usrIdd;
		$rs=gFastQuery($sql);
		$em=$rs->fields['pje_acesso'];

		gLog("===> Processando PJe [$em] $numero >>>>>>>>>>>>>>>>>>>>>>>>>>>> ");

		$sql="update parametros set pje_acesso=".time()." where idd=".$usrIdd;
		gFastQuery($sql);

		$ok=true;
		$post='';
		$cookie=false;
		$debug=0;
		$jaExiste=false;
		$tamMin=4;

		$this->trt=$trt;


		$usrAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:19.0) Gecko/20100101 Firefox/19.0";
		$usrAgent = $this->usrAgent;
		$cookie=true;

		$urlCookie="https://consultapje.trt1.jus.br/consultaprocessual/pages/consultas/ConsultaProcessual.seam";
		$urlConsulta="https://consultapje.trt1.jus.br/consultaprocessual/pages/consultas/ConsultaProcessual.seam";

		$ok = $this->getPJeCookie($urlCookie);
		if ($ok)
		{
			$html=$this->processaPJe_envia($urlConsulta, $numero, $usrAgent);
			if ($html=="")
			{
				$ok=false;
				$this->lastError="A consulta ao PJe não retornou dados válidos. Tente novamente mais tarde.";
				gLog("===> PJe: ".$this->lastError);
			} elseif (substr($html,0,6)=="varios")
			{
				$ok=substr($html,7);
			} else
			{
				$grau = 1;
				if (strpos($html, 'Detalhes do Processo de 2') !== false)
					$grau = 2;
				$ok=$this->processaPJe_html($numero,$html, $grau);
			}
		}


		return($ok);
	}

	function processaPJe_envia($url, $numero, $usrAgent)
	{
		$ok=true;
		$cookie=true;
		$redir='';
		$ckfile=$this->getCookie();
		$trt=$this->trt;

		gLog("===> PJe: Enviando parametros: $url (viewState ".$this->viewState." - ckfile $ckfile)");


		/*

		Resumo (em 29/04/2015):

		PASSO 1 - ConsultaProcessos - Busca o processo. Retornará com erro.
			REDIR 1 - errorSession
		PASSO 2 - ConsultaProcessos - Volta pra ConsultaProcesso e recebe o cookie.
		PASSO 3 - ConsultaProcessos - Busca o processo novamente. Redirecionará duas vezes:
			REDIR 1 - ListaProcessos
			REDIR 2 - DetalhaProcessos

		*/

		gLog("===== [  P A S S O   -  1  ] =====");


		$post='';

		$post['consultaProcFormFormatForm']="consultaProcFormFormatForm";
		$post['j_id49']="true";
		$post['numeroFormatDecorate:numero_format']=$numero;
		$post['consultarNumFormat']="Pesquisar";
		$post['javax.faces.ViewState']=$this->viewState;

		$header="";
		$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
		$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
		$header[]="Connection: keep-alive";
		$header[]="Referer: https://consultapje.trt1.jus.br/consultaprocessual/pages/consultas/ConsultaProcessual.seam";
		$header[]="DNT: 1";
		$header[]="Host: consultapje.trt1.jus.br";







		$arq="===> Numero: $numero\n";
		$arq.="===> Data: ".date("Y-m-d H:i:s")."\n";
		$arq.="===> URL: $url\n";
		$s="";
		foreach ($post as $key=>$row)
		{
			$s.=$key."=".$row."\n";
		}
		$arq.="===> Post: \n$s\n";
		gLog("===> URL (1) ============= ".str_replace("\n"," | ",$s));

		$cookie=true;
		$baseUrl=substr($url,0,stripos($url,'br/')+3);
		$ckfile=$this->getCookie();

		// Chamando URL
		$bodyData = http_build_query($post);
		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);

		if ($trt=="trt1")
		{
			$boundary=md5(time());
			$bodyData = '';
			foreach($post as $key => $value){
				$bodyData.= "--$boundary\r\nContent-Disposition: form-data; name=\"$key\"\r\n\r\n$value\r\n";
			}
			$bodyData.= "--$boundary--";
			$header[]="Content-Type: multipart/form-data; boundary=$boundary";
			//$header[]="Content-Length: ".strlen($bodyData);
			$header[]="Accept-Encoding: gzip, deflate";
			curl_setopt ($ch, CURLOPT_ENCODING, 'gzip, deflate');
			$arq.="===> Body Data:\n".$bodyData."\n\n";
		}
		$s="";
		foreach ($header as $key=>$row)
		{
			$s.=$row."\n";
		}
		$arq.="===> Header: \n$s";
		$arq.="User-Agent: ".$this->usrAgent."\n\n";
		gLog("===> URL (2) Headers: ".str_replace("\n"," | ",$s));


		if (strpos($url,"https")!==false)
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		if (is_array($header))
			curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
		curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);

		if (is_array($post))
		{
			curl_setopt ($ch, CURLOPT_POST, true);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
		}
		//curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt ($ch, CURLOPT_VERBOSE, true);
		curl_setopt ($ch, CURLOPT_HEADER, true);

		$html = curl_exec ($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		// Notou-se em Novembro de 2014 que o servidor do PJe retorna vários HEADERs pra confundir o robo
		// O http_code obtido através da função do PHP (acima) retorna um código falso!
		// O último HEADER é o que deve ser considerado
		$codes=explode('HTTP/1.1', $html);
		$http_code='';
		foreach ($codes as $code)
		{
			$code=trim($code);
			if (substr($code,0,4)=='302 ')
				$http_code='302';
			if (substr($code,0,4)=='200 ' && $http_code=='')
				$http_code='200';
		}

		$arq.="===> Codigo de resposta: $http_code\n";

		$arq.=str_replace("\r","",$html);

		// Se encontrar 302, significa que o servidor redirecionou a página para outro endereço
		// O código abaixo identifica esta página e redireciona automaticamente, buscando os respectivos dados
		if ($http_code == '302')
		{
			$headers = explode("\n",$html);
			$j = count($headers);
			$redir=$url;
			for($i = 0; $i < $j; $i++){
				if(strpos($headers[$i],"Location:") !== false)
				{
					$redir = trim(str_replace("Location:","",$headers[$i]));
					break;
				}
			}
			curl_close ($ch);
			$this->lastRedir=$redir;
			$arq.="\n===> Redirecionamento: $redir\n\n";
			gLog("===> URL (3) Redir: $redir");
			$ch = curl_init ($redir);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			if (strpos($redir,"https")!==false)
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout em segundos
			curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
			curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);
			curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_VERBOSE, true);
			curl_setopt ($ch, CURLOPT_HEADER, true);
			$html = curl_exec ($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$headers = explode("\n",$html);
			$j = count($headers);
			$redir=$url;
			for($i = 0; $i < $j; $i++){
				if(strpos($headers[$i],"Location:") !== false)
				{
					$redir = trim(str_replace("Location:","",$headers[$i]));
					break;
				}
			}
			curl_close ($ch);

			// Descobrindo os códigos de retorno à requisição e considerando somente o último
			$codes=explode('HTTP/1.1', $html);
			$http_code='';
			foreach ($codes as $code)
			{
				$code=trim($code);
				if (substr($code,0,4)=='302 ')
					$http_code='302';
				if (substr($code,0,4)=='200 ' && $http_code=='')
					$http_code='200';
			}
			$arq.="===> Codigo de resposta: $http_code\n";
			$arq.=str_replace("\r","",$html);



			if ($http_code=="302")
			{
				$this->lastRedir=$redir;
				$arq.="\n===> Redirecionamento: $redir\n\n";
				gLog("===> URL (5) Redir: $redir");
				$ch = curl_init ($redir);
				curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
				if (strpos($redir,"https")!==false)
					curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout em segundos
				curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
				curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);
				curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
				curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
				curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt ($ch, CURLOPT_VERBOSE, true);
				curl_setopt ($ch, CURLOPT_HEADER, true);
				$html = curl_exec ($ch);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close ($ch);

				// Descobrindo os códigos de retorno à requisição e considerando somente o último
				$codes=explode('HTTP/1.1', $html);
				$http_code='';
				foreach ($codes as $code)
				{
					$code=trim($code);
					if (substr($code,0,4)=='302 ')
						$http_code='302';
					if (substr($code,0,4)=='200 ' && $http_code=='')
						$http_code='200';
				}
				$arq.="===> Codigo de resposta: $http_code\n";
				$arq.=str_replace("\r","",$html);

			}
		}
		$arq.="\n\n\n\n";
		file_put_contents("/tmp/alitem-url-debug-".$_SERVER['SERVER_ADDR'].".log", $arq);


		if (strpos($html, 'Request Rejected')!==false)
		{
			gLog("===> PJe: Requisição rejeitada!");
			$ok=false;
			$html='';
			$this->lastError="Não é possível acessar o site do PJe no momento. Aguarde alguns minutos e tente novamente.";
		}

		if ($ok)
		{

			gLog("===== [  P A S S O   -  2  ] =====");



			$header="";
			$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
			$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
			$header[]="Connection: keep-alive";
			$header[]="Referer: ".$this->lastRedir;
			$header[]="DNT: 1";
			$header[]="Host: consultapje.trt1.jus.br";

			$ch = curl_init ($url);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);
			curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
			curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_HEADER, 1);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
			$html = curl_exec ($ch);
			curl_close ($ch);

			if (stripos($html,"Detectada utilizacao excessiva")!==false )
			{
				gLog("===> PJe: Detectada utilização excessiva!");
				$ok=false;
				$html='';
				$this->lastError="Não é possível acessar o site do PJe no momento. Aguarde alguns minutos e tente novamente.";
			}

			if ($ok)
			{

				gLog("===== [  P A S S O   -  3  ] =====");


				$html=$this->processaUrl($url, $header, $post, $numero, true);


				$baseUrl=substr($url,0,stripos($url,'br/')+3);


				if (stripos($html, 'Processos Encontrados') !== false)
				{
					$this->maisDeUm=true;
					gLog("===> PJe: Retornou mais de uma instância ".strlen($html)." caracteres - código: " . $http_code);
					gLog("===> PJe: Limpando dados - mais de um grau encontrado");
					$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros

					//<img src="/consultaprocessual/img/proc1g_32.png" style="border:0; padding-right:3px; vertical-align: middle;width:23px;">RTOrd-0010297-13.2013.5.01.0049
					//<a href="/consultaprocessual/pages/consultas/DetalhaProcesso.seam?p_seq=10297&amp;p_dig=13&amp;p_ano=2013&amp;p_vara=49&amp;p_num_pje=41222&amp;p_grau_pje=1&amp;dt_autuacao=02%2F04%2F2013&amp;conversationPropagation=begin" id="consultaProcs:0:procId" style="font-size: 14px;" class="linkProcesso"><img src="/consultaprocessual/img/proc1g_32.png" style="border:0; padding-right:3px; vertical-align: middle;width:23px;">RTOrd-0010297-13.2013.5.01.0049</a>
					$faz=true;
					$htmlOrig=$html;
					$cnt=0;
					$referer=$url;
					while ($faz)
					{
						$p=strpos($htmlOrig,'<a href="/consultaprocessual/pages/consultas/DetalhaProcesso.seam?p_seq=');
						$ok='';
						if ($p>0)
						{
							$cnt++;
							$grau_pje=1;
							$pg=strpos($htmlOrig,'p_grau_pje=');
							if ($pg!==false)
							{
								$grau_pje=substr($htmlOrig,$pg+11,1);
							}
							$p=strpos($htmlOrig,'"',$p)+2;
							$htmlOrig=substr($htmlOrig,$p);
							$pFim=strpos($htmlOrig,'"');
							$url=$baseUrl.substr($htmlOrig,0,$pFim);
							$htmlOrig=substr($htmlOrig,$pFim+1);
							$url=str_replace('&amp;','&',$url);
							gLog("===> PJe: Varios ($cnt) grau $grau_pje => Processando URL: $url");
							$ch = curl_init ($url);
							curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);

							if (strpos($url,"https")!==false)
								curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
							curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
							curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);
							curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
							curl_setopt ($ch, CURLOPT_POST, false);
							//curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
							//curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
							curl_setopt ($ch, CURLOPT_REFERER, $referer);

							curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
							curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
							//curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

							curl_setopt ($ch, CURLOPT_VERBOSE, true);
							curl_setopt ($ch, CURLOPT_HEADER, true);

							$html = curl_exec ($ch);
							$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							if ($http_code == '302')
							{
								//file_put_contents('/tmp/response.html', $html);exit;
								$headers = explode("\n",$html);
								$j = count($headers);
								$redir=$url;
								for($i = 0; $i < $j; $i++){
								// if we find the Location header strip it and fill the redir var
									if(strpos($headers[$i],"Location:") !== false)
									{
										$redir = trim(str_replace("Location:","",$headers[$i]));
										break;
									}
								}
								curl_close ($ch);

								gLog("===> PJe: Varios ($cnt) => Redirecionamento (1): $redir");
								$ch = curl_init ($redir);
								curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
								if (strpos($redir,"https")!==false)
									curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
								curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
								curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
								curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);
								curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
								//curl_setopt ($ch, CURLOPT_POST, true);
								//curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
								curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
								//curl_setopt ($ch, CURLOPT_REFERER, $referer);
								curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
								curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
								curl_setopt ($ch, CURLOPT_VERBOSE, true);
								curl_setopt ($ch, CURLOPT_HEADER, true);
								$html = curl_exec ($ch);
								$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							}

		//echo "Dados:<br>$url<br>$bodyData<pre>";htmlentities(print_r($response)); print_r($post);echo "</pre>";echo "<br><br>\n\n\n\n\n\nHTML:\n\n[ ".htmlentities($html)." ]\n\n\n\n\n\n";exit;

							gLog("===> PJe: Varios ($cnt) => Retornou ".strlen($html)." caracteres - código: " . $http_code);
							$html=$this->processaJavascript($html,$baseUrl);
							$ok.=$this->processaPJe_html($numero,$html,$grau_pje);
						} else
						{
							$faz=false;
							if ($cnt==0)
								$html='varios;'.$ok;
						}
					}


				} else
				{
					gLog("===> PJe: Retornou ".strlen($html)." caracteres ");

					$html=$this->processaJavascript($html,$baseUrl);
				}
			}

		}

		return($html);

	}





/*
$html   : código da página
$busca  : texto que indica que a próxima TAG será usada
$tag    : conteúdo desta TAG retornará
*/
	function processaPJe_campo($html, $busca, $tag)
	{
		$sai='';
		$html=str_replace("&nbsp;",' ',$html);
		$p=stripos($html,$busca);
		if ($p>0)
		{
			$s=substr($html,$p+strlen($busca));
			$p=stripos($s,'<'.$tag);
			if ($p>0)
			{
				$s=substr($s,$p);
				$p=stripos($s,'</'.$tag);
				$pos=intval($p+strlen($tag)+3);
				//echo " p=$p pos=$pos len=".strlen($s);
				$sai=strip_tags(substr($s,0,$pos));
				$sai=str_replace("(",'',$sai);
				$sai=trim(str_replace(")",'',$sai));
				//$sai=substr($s,0,60);
			}
		}
		return($sai);
	}

	/*
	Obtém partes em um quadro de uma consulta PJe
	Retorna um array contendo array(parte, nome), onde parte pode ser "parte" ou "representante" (advogado ou procurador)
	*/
	function obtemPartesPJe($html,$id)
	{
		$sai = '';
		$fim = false;
		$cnt = 0;
		while (!$fim)
		{
			$cnt++;
			// Busca o ícone pra identificar o tipo de parte
			$p=strpos($html,$id.":icon");
			if ($p !== false)
			{
				$pPng = strpos($html,'.png',$p);
				if ($pPng !== false)
				{
					$parte = substr($html,$pPng-5,5);
					if ($parte == "tante")
						$parte = "representante";
					$pTxt = strpos($html,$id.":text", $p);
					if ($gTxt !== false)
					{
						$nome = substr($html, strpos($html,'>', $pTxt)+1);
						$pFim = strpos($nome, '<');
						$html = substr($nome, $pFim);
						$nome = substr($nome, 0, $pFim);
						$sai[] = array($parte, $nome);
					} else
					{
						$fim=true;
					}
				} else
				{
					$fim = true;
				}
			} else
			{
				$fim = true;
			}
		}
		return($sai);
	}
	function processaPJe_html($numero,$html, $grau=1)
	{
		if (!$this->maisDeUm)
		{
			$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
			gLog("===> PJe: Limpando dados - grau $grau");
		}
		gLog("===> PJe: Processando dados obtidos - grau $grau");


		$this->campos['numero']=$numero;
		$this->campos['orgao_julgador_atual']=$this->processaPJe_campo($html,'Detalhes do Processo','font');

		// Só atualiza os campos reu, autor e advogados se for 1ª instância

		if ($grau==1)
		{
			if (strpos($html, 'RECLAMANTE(S)')!==false)
			{
				$this->campos['autor']=$this->processaPJe_campo($html,'RECLAMANTE(S):','tbody');

				$htmlReclamante=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelDetalhes_body')+50);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['autor'],'',$htmlReclamante)));
				if (!is_array($advogados))
				{
					$htmlAdv=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelMais_body')+46);
					$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
					$p=strpos($htmlAdv,'ADVOGADO: ');
					if ($p!==false)
					{
						$adv=substr($htmlAdv,$p+10);
						$adv=substr($adv,0,strpos($adv,'<'));
						$advogados=array($adv);
					}
				}
				$this->campos['autor_advogado']=trim(implode("\n",$advogados));

				// Tem que melhorar este aqui... 0010058-61.2013.5.01.0064
				$this->campos['reu']=$this->processaPJe_campo($html,'RECLAMADO(S)','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamadaFieldDecorate:panelDetalhes_body')+49);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['reu'],'',$htmlReclamante)));
				$this->campos['reu_advogado']=trim(implode("\n",$advogados));
				if (!is_array($advogados))
				{
					$htmlAdv=substr($html,strpos($html,'partesReclamadoFieldDecorate:panelMais_body')+46);
					$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
					$p=strpos($htmlAdv,'ADVOGADO: ');
					if ($p!==false)
					{
						$adv=substr($htmlAdv,$p+10);
						$adv=substr($adv,0,strpos($adv,'<'));
						$advogados=array($adv);
					}
				}
			}

			if (strpos($html, 'AUTOR(S)')!==false)
			{
				$this->campos['autor']=$this->processaPJe_campo($html,'AUTOR(S):','tbody');

				$htmlReclamante=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelDetalhes_body')+50);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['autor'],'',$htmlReclamante)));
				if (!is_array($advogados))
				{
					$htmlAdv=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelMais_body')+46);
					$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
					$p=strpos($htmlAdv,'ADVOGADO: ');
					if ($p!==false)
					{
						$adv=substr($htmlAdv,$p+10);
						$adv=substr($adv,0,strpos($adv,'<'));
						$advogados=array($adv);
					}
				}
				$this->campos['autor_advogado']=trim(implode("\n",$advogados));

				// Tem que melhorar este aqui... 0010058-61.2013.5.01.0064
				$this->campos['reu']=$this->processaPJe_campo($html,'R&Eacute;U(S)','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamadaFieldDecorate:panelDetalhes_body')+49);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['reu'],'',$htmlReclamante)));
				$this->campos['reu_advogado']=trim(implode("\n",$advogados));
				if (!is_array($advogados))
				{
					$htmlAdv=substr($html,strpos($html,'partesReclamadoFieldDecorate:panelMais_body')+46);
					$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
					$p=strpos($htmlAdv,'ADVOGADO: ');
					if ($p!==false)
					{
						$adv=substr($htmlAdv,$p+10);
						$adv=substr($adv,0,strpos($adv,'<'));
						$advogados=array($adv);
					}
				}
			}


			if (strpos($html, 'EXEQUENTE(S)')!==false)
			{
				$this->campos['autor']=$this->processaPJe_campo($html,'EXEQUENTE(S):','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelDetalhes_body')+50);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['autor'],'',$htmlReclamante)));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$adv=substr($html,strpos($html,'ADVOGADO_: ')+11);
					$adv=substr($adv,0,strpos($adv,':'));
					$advogados=array($adv);
				}
				$this->campos['autor_advogado']=trim(implode("\n",$advogados));

				// Tem que melhorar este aqui... 0010058-61.2013.5.01.0064
				$this->campos['reu']=$this->processaPJe_campo($html,'EXECUTADO(S)','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamadaFieldDecorate:panelDetalhes_body')+49);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['reu'],'',$htmlReclamante)));
				$this->campos['reu_advogado']=trim(implode("\n",$advogados));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$htmlAdv=substr($html,strpos($html,'partesReclamadoFieldDecorate:panelMais_body')+46);
					$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
					$p=strpos($htmlAdv,'ADVOGADO: ');
					if ($p!==false)
					{
						$adv=substr($htmlAdv,$p+10);
						$adv=substr($adv,0,strpos($adv,'<'));
						$advogados=array($adv);
					}
				}
				$this->campos['reu_advogado']=$this->campos['reu_advogado']."\n".trim(implode("\n",$advogados));
			}


			if (strpos($html, 'REQUERENTE(S)')!==false)
			{
				$this->campos['autor']=$this->processaPJe_campo($html,'REQUERENTE(S):','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelDetalhes_body')+50);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['autor'],'',$htmlReclamante)));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$adv=substr($html,strpos($html,'ADVOGADO_: ')+11);
					$adv=substr($adv,0,strpos($adv,':'));
					$advogados=array($adv);
				}
				$this->campos['autor_advogado']=trim(implode("\n",$advogados));

				// Tem que melhorar este aqui... 0010058-61.2013.5.01.0064
				$this->campos['reu']=$this->processaPJe_campo($html,'REQUERIDO(S)','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamadaFieldDecorate:panelDetalhes_body')+49);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['reu'],'',$htmlReclamante)));
				$this->campos['reu_advogado']=trim(implode("\n",$advogados));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$htmlAdv=substr($html,strpos($html,'partesReclamadoFieldDecorate:panelMais_body')+46);
					$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
					$p=strpos($htmlAdv,'ADVOGADO: ');
					if ($p!==false)
					{
						$adv=substr($htmlAdv,$p+10);
						$adv=substr($adv,0,strpos($adv,'<'));
						$advogados=array($adv);
					}
				}
				$this->campos['reu_advogado']=$this->campos['reu_advogado']."\n".trim(implode("\n",$advogados));
			}


			if (strpos($html, 'PERITO /')!==false)
			{
				$this->campos['autor']=$this->processaPJe_campo($html,'PERITO /','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelDetalhes_body')+50);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['autor'],'',$htmlReclamante)));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$adv=substr($html,strpos($html,'ADVOGADO_: ')+11);
					$adv=substr($adv,0,strpos($adv,':'));
					$advogados=array($adv);
				}
				$this->campos['autor_advogado']=trim(implode("\n",$advogados));

				// Tem que melhorar este aqui... 0010058-61.2013.5.01.0064
				$this->campos['reu']=$this->processaPJe_campo($html,'EXECUTADO(S)','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamadaFieldDecorate:panelDetalhes_body')+49);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['reu'],'',$htmlReclamante)));
				$this->campos['reu_advogado']=trim(implode("\n",$advogados));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$htmlAdv=substr($html,strpos($html,'partesReclamadoFieldDecorate:panelMais_body')+46);
					$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
					$p=strpos($htmlAdv,'ADVOGADO: ');
					if ($p!==false)
					{
						$adv=substr($htmlAdv,$p+10);
						$adv=substr($adv,0,strpos($adv,'<'));
						$advogados=array($adv);
					}
				}
				$this->campos['reu_advogado']=$this->campos['reu_advogado']."\n".trim(implode("\n",$advogados));
			}



			if (strpos($html, 'CONSIGNAT&Aacute;RIO(S)')!==false)
			{
				$this->campos['autor']=$this->processaPJe_campo($html,'CONSIGNAT&Aacute;RIO(S)','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelDetalhes_body')+50);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['autor'],'',$htmlReclamante)));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$adv=substr($html,strpos($html,'ADVOGADO_: ')+11);
					$adv=substr($adv,0,strpos($adv,':'));
					$advogados=array($adv);
				}
				$this->campos['autor_advogado']=trim(implode("\n",$advogados));

				// Tem que melhorar este aqui... 0010058-61.2013.5.01.0064
				$this->campos['reu']=$this->processaPJe_campo($html,'CONSIGNANTE(S)','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamadaFieldDecorate:panelDetalhes_body')+49);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['reu'],'',$htmlReclamante)));
				$this->campos['reu_advogado']=trim(implode("\n",$advogados));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$htmlAdv=substr($html,strpos($html,'partesReclamadoFieldDecorate:panelMais_body')+46);
					$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
					$p=strpos($htmlAdv,'ADVOGADO: ');
					if ($p!==false)
					{
						$adv=substr($htmlAdv,$p+10);
						$adv=substr($adv,0,strpos($adv,'<'));
						$advogados=array($adv);
					}
				}
				$this->campos['reu_advogado']=$this->campos['reu_advogado']."\n".trim(implode("\n",$advogados));
			}


			if (strpos($html, 'LEGIS(S)')!==false)
			{
				$this->campos['autor']=$this->processaPJe_campo($html,'R&Eacute;U(S)','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelDetalhes_body')+50);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['autor'],'',$htmlReclamante)));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$adv=substr($html,strpos($html,'ADVOGADO_: ')+11);
					$adv=substr($adv,0,strpos($adv,':'));
					$advogados=array($adv);
				}
				$this->campos['autor_advogado']=trim(implode("\n",$advogados));

				// Tem que melhorar este aqui... 0010058-61.2013.5.01.0064
				$this->campos['reu']=$this->processaPJe_campo($html,'LEGIS(S)','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamadaFieldDecorate:panelDetalhes_body')+49);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['reu'],'',$htmlReclamante)));
				$this->campos['reu_advogado']=trim(implode("\n",$advogados));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$htmlAdv=substr($html,strpos($html,'partesReclamadoFieldDecorate:panelMais_body')+46);
					$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
					$p=strpos($htmlAdv,'ADVOGADO: ');
					if ($p!==false)
					{
						$adv=substr($htmlAdv,$p+10);
						$adv=substr($adv,0,strpos($adv,'<'));
						$advogados=array($adv);
					}
				}
				$this->campos['reu_advogado']=$this->campos['reu_advogado']."\n".trim(implode("\n",$advogados));
			}

			if (strpos($html, 'IMPETRANTE(S)')!==false)
			{
				$autor = '';
				$autor_advogado = '';
				$reu = '';
				$reu_advogado = '';

				// Impetrante
				$partes = $this->obtemPartesPJe($html, "j_id150");
				foreach ($partes as $parte)
				{
					if ($parte[0]=="parte")
						$autor[]=$parte[1];
					else
						$autor_advogado[]=$parte[1];
				}

				// Terceiro
				$partes = $this->obtemPartesPJe($html, "j_id176");
				foreach ($partes as $parte)
				{
					if ($parte[0]=="parte")
						$reu[]=$parte[1];
					else
						$reu_advogado[]=$parte[1];
				}

				$this->campos['autor']=implode("\n",$autor);
				$this->campos['autor_advogado']=implode("\n",$autor_advogado);
				$this->campos['reu']=implode("\n",$reu);
				$this->campos['reu_advogado']=implode("\n",$reu_advogado);


				$this->campos['autor']=$autor[0];

				// $this->campos['autor']=$this->processaPJe_campo($html,'IMPETRANTE(S)','tbody');
				// $htmlReclamante=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelDetalhes_body')+50);
				// $htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				// $advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['autor'],'',$htmlReclamante)));
				// if (!is_array($advogados) || $advogados[0]=="")
				// {
				// 	$adv=substr($html,strpos($html,'ADVOGADO_: ')+11);
				// 	$adv=substr($adv,0,strpos($adv,':'));
				// 	$advogados=array($adv);
				// }
				// $this->campos['autor_advogado']=trim(implode("\n",$advogados));

			}

			if (strpos($html, 'SUSCITADO(S)')!==false)
			{
				$this->campos['autor']=$this->processaPJe_campo($html,'SUSCITADO(S):','tbody');

				$htmlReclamante=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelDetalhes_body')+50);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['autor'],'',$htmlReclamante)));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$adv=substr($html,strpos($html,'ADVOGADO_: ')+11);
					$adv=substr($adv,0,strpos($adv,':'));
					$advogados=array($adv);
				}
				$this->campos['autor_advogado']=trim(implode("\n",$advogados));

				// Tem que melhorar este aqui... 0010058-61.2013.5.01.0064
				$this->campos['reu']=$this->processaPJe_campo($html,'SUSCITANTE(S)','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamadaFieldDecorate:panelDetalhes_body')+49);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['reu'],'',$htmlReclamante)));
				$this->campos['reu_advogado']=trim(implode("\n",$advogados));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$htmlAdv=substr($html,strpos($html,'partesReclamadaFieldDecorate:panelMais_body')+46);
					$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
					$p=strpos($htmlAdv,'ADVOGADO: ');
					if ($p!==false)
					{
						$adv=substr($htmlAdv,$p+10);
						$adv=substr($adv,0,strpos($adv,'<'));
						$advogados=array($adv);
					}
				}
				$this->campos['reu_advogado']=$this->campos['reu_advogado']."\n".trim(implode("\n",$advogados));
			}


		} else
		{
			if (strpos($html, 'SUSCITADO(S)')!==false)
			{
				$this->campos['autor']=$this->processaPJe_campo($html,'SUSCITADO(S):','tbody');

				$htmlReclamante=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelDetalhes_body')+50);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['autor'],'',$htmlReclamante)));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$adv=substr($html,strpos($html,'ADVOGADO_: ')+11);
					$adv=substr($adv,0,strpos($adv,':'));
					$advogados=array($adv);
				}
				$this->campos['autor_advogado']=trim(implode("\n",$advogados));

				// Tem que melhorar este aqui... 0010058-61.2013.5.01.0064
				$this->campos['reu']=$this->processaPJe_campo($html,'SUSCITANTE(S)','tbody');
				$htmlReclamante=substr($html,strpos($html,'partesReclamadaFieldDecorate:panelDetalhes_body')+49);
				$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));
				$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['reu'],'',$htmlReclamante)));
				$this->campos['reu_advogado']=trim(implode("\n",$advogados));
				if (!is_array($advogados) || $advogados[0]=="")
				{
					$htmlAdv=substr($html,strpos($html,'partesReclamadaFieldDecorate:panelMais_body')+46);
					$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
					$p=strpos($htmlAdv,'ADVOGADO: ');
					if ($p!==false)
					{
						$adv=substr($htmlAdv,$p+10);
						$adv=substr($adv,0,strpos($adv,'<'));
						$advogados=array($adv);
					}
				}
				$this->campos['reu_advogado']=$this->campos['reu_advogado']."\n".trim(implode("\n",$advogados));
			}


		}

		if (strpos($html, 'COATORA(S)')!==false)
		{
			$this->campos['reu']=$this->processaPJe_campo($html,'COATORA(S)','tbody');
			$htmlReclamante=substr($html,strpos($html,'partesReclamanteFieldDecorate:panelDetalhes_body')+50);
			$htmlReclamante=substr($htmlReclamante,0,strpos($htmlReclamante, '<script'));


			$advogados=explode('ADVOGADO: ',rip_tags(str_replace($this->campos['reu'],'',$htmlReclamante)));
			$this->campos['autor']=$advogados[0];
			unset($advogados[0]);
			$this->campos['autor_advogado']=trim(implode("\n",$advogados));
			$this->campos['reu_advogado']='';
			// if (!is_array($advogados) || $advogados[0]=="")
			// {
			// 	$htmlAdv=substr($html,strpos($html,'partesReclamadoFieldDecorate:panelMais_body')+46);
			// 	$htmlAdv=substr($htmlAdv,0,strpos($htmlAdv,'<script'));
			// 	$p=strpos($htmlAdv,'ADVOGADO: ');
			// 	if ($p!==false)
			// 	{
			// 		$adv=substr($htmlAdv,$p+10);
			// 		$adv=substr($adv,0,strpos($adv,'<'));
			// 		$advogados=array($adv);
			// 	}
			// }
			// $this->campos['reu_advogado']=$this->campos['reu_advogado']."\n".trim(implode("\n",$advogados));

		}


		// Andamentos

		$html=substr($html,strpos($html,'Movimento / Documento'));
		//$p=strpos($html,'panelExpedientes')+18;
		$p=strpos($html,'panelExpedientes');
		if ($p==0)
			$p=strpos($html,'<input type=');
		if ($p==0)
			$p=strpos($html,'Consulta Processual PJe');
		$html=substr($html,0,$p);

		// Extraindo somente andamentos
		$and=substr($html,strpos($html,'<tbody id="consultaProcessos:tb">'));
		$and=str_replace('</div></td>','~</div></td>',$and);
		$and=rip_tags($and);
		$and=str_replace('Movimento / Documento','',$and);
		$linhas=explode("~",$and);

//echo "Linhas:<pre>";print_r($linhas);echo "html:\n\n\n\n\n$html\n\n\n\n\n";exit;

		$ehData=true;
		for($a=0; $a<count($linhas); $a++)
		{
			if ($ehData)
			{
				$lin1=$linhas[$a];
				$lin2=$linhas[$a+1];
				$data=htmlFormataData(trim($lin1));
				$desc=trim(str_replace("&nbsp;","",$lin2));
				if (($data<>"20--") && ($desc<>""))
				{
					$rec='';
					$rec['grau']=intval($grau);
					$rec['data']=$data;
					$rec['descricao']=$desc;
					$this->net->records[]=$rec;
				}
			}
			$ehData=!$ehData;
		}

//if($grau==2) {
	//echo "<pre>";print_r($this->campos);echo "\n";print_r($advogados);echo "\n\n$and\n\n\n";print_r($this->net->records);echo "</pre>";exit;
//}

		$this->menorData($this->net->records);
		//$this->campos=$this->net->getFields();
		$this->andamentos=$this->net->getRecords();
	//echo "<pre>";print_r($this->campos);print_r($this->andamentos);echo "</pre>";exit;

		$ok=true;
		return($ok);
	}


	function processaPJeAntigo($trt,$numero,$grau="primeirograu")
	{
		global $out,$http_tmp,$usrId,$usrIdd, $gPathTmp;

		$sql="select * from parametros where idd=".$usrIdd;
		$rs=gFastQuery($sql);
		$em=$rs->fields['pje_acesso'];

		gLog("===> Processando PJe Antigo [$em] $numero * * * * * ");

		$sql="update parametros set pje_acesso=".time()." where idd=".$usrIdd;
		gFastQuery($sql);

		$ok=true;
		$post='';
		$cookie=false;
		$debug=0;
		$jaExiste=false;
		$tamMin=4;

		$this->trt=$trt;


		$usrAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:19.0) Gecko/20100101 Firefox/19.0";
		$cookie=true;

		if (($trt=='trt7') || ($trt=='trt5')|| ($trt=='trt6'))
		{
			$urlCookie="https://pje.$trt.jus.br/primeirograu/ConsultaPublica/listView.seam";
			$urlConsulta1="https://pje.$trt.jus.br/primeirograu/ConsultaPublica/listView.seam";
			$urlConsulta2="https://pje.$trt.jus.br/primeirograu/ConsultaPublica/DetalheProcessoConsultaPublica/listView.seam";
		} else
		{
			$urlCookie=$this->PJeHttp."://pje.$trt.jus.br/primeirograu/ConsultaPublica/listView.seam";
			$urlConsulta1=$this->PJeHttp."://pje.$trt.jus.br/primeirograu/ConsultaPublica/listView.seam";
			$urlConsulta2=$this->PJeHttp."://pje.$trt.jus.br/primeirograu/ConsultaPublica/DetalheProcessoConsultaPublica/listView.seam";
		}

		$ok = $this->getPJeAntigoCookie($urlCookie);
		if ($ok)
		{
			$html=$this->processaPJeAntigo_envia($urlConsulta1, $urlConsulta2, $numero, $usrAgent);
			if (stripos($html,"Movimenta&ccedil;&otilde;es do Processo")===false)
			{
				gLog("===> PJe: Não foi identificada a string que informa que os dados foram retornados");
				$ok=false;
				$this->lastError="Não é possível consultar PJe $trt neste momento. Tente novamente mais tarde.";
			} else
			{
				$ok=$this->processaPJeAntigo_html($numero,$html);
			}
		}
		$ckfile=$this->getCookie();
		unlink($ckfile);

		return($ok);
	}


	function processaPJeAntigo_envia($url1, $url2, $numero, $usrAgent)
	{
		global $usrId;

		gLog("===> PJe: Enviando parametros (antigo)");
		$cookie=true;

		$ckfile=$this->getCookie();

		$trt=$this->trt;

		// Busca cabeçalho do processo

		$post='';

		$post['AJAX:EVENTS_COUNT']="1";
		$post['AJAXREQUEST']="_viewRoot";
		$post['autoScroll']="";
		$post['gF']="gF";
		$post['gF:j_id50']="1";
		$post['gF:j_id51']="false";
		$post['gF:nPDecoration:nP']=$numero;
		$post['gF:sB']="gF:sB";
		$post['javax.faces.ViewState']=$this->viewState;

		$header="";
		$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
		$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
		$header[]="Cache-Control: no-cache";
		$header[]="Connection: keep-alive";
		$header[]="Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
		$header[]="DNT: 1";
		$header[]="Host: pje.$trt.jus.br";

		$bodyData = http_build_query($post);
		$ch = curl_init ($url1);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
		if (strpos($url1,"https")!==false)
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
		curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);

		curl_setopt ($ch, CURLOPT_POST, true);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
		curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');

		curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);

		$response = curl_exec ($ch);
//if ($usrId==2) {	echo $response;exit; }
		$parts = explode("\r\n\r\nHTTP/", $response);
		$parts = (count($parts) > 1 ? 'HTTP/' : '').array_pop($parts);
		list($headerResponse, $html) = explode("\r\n\r\n", $parts, 2);

		curl_close ($ch);
		$baseUrl=substr($url,0,stripos($url,'br/')+3);
		$html=$this->processaJavascript($html,$baseUrl);


		// Buscando página de detalhes
		$url=substr($html,strpos($html,"?signedIdProcessoTrf"));
		$url=$url2.substr($url,0,strpos($url,"'"));


		$header="";
		$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
		$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
		$header[]="Cache-Control: no-cache";
		$header[]="Connection: keep-alive";
		$header[]="Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
		$header[]="DNT: 1";
		$header[]="Host: pje.$trt.jus.br";

		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
		if (strpos($url,"https")!==false)
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
		curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);
		curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');

		curl_setopt ($ch, CURLOPT_POST, false);
		//curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
		curl_setopt ($ch, CURLOPT_AUTOREFERER, true);

		curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

		$html = curl_exec ($ch);

//echo "URL:<br>$url<br>$bodyData<pre>";print_r($post);echo "</pre>";echo "<br><br>\n\n\n\n\n\nHTML:\n\n[ ".htmlentities($html)." ]\n\n\n\n\n\n";exit;
		return($html);
	}







	function processaPJeNovo($trt,$numero,$grau="primeirograu")
	{
		global $out,$http_tmp,$usrId,$usrIdd,$gPathTmp;

		$sql="select * from parametros where idd=".$usrIdd;
		$rs=gFastQuery($sql);
		$em=$rs->fields['pje_acesso'];

		gLog("===> Processando PJe Novo [$em] $numero * * * * * ");

		$sql="update parametros set pje_acesso=".time()." where idd=".$usrIdd;
		gFastQuery($sql);

		$ok=true;
		$post='';
		$cookie=false;
		$debug=0;
		$jaExiste=false;
		$tamMin=4;

		$this->trt=$trt;


		$usrAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:19.0) Gecko/20100101 Firefox/19.0";
		$cookie=true;

		//$urlCookie="https://pje.$trt.jus.br/primeirograu/ConsultaPublica/listView.seam";
		$urlCookie=$this->PJeHttp."://pje.$trt.jus.br/consultaprocessual/pages/consultas/ConsultaProcessual.seam";
		$urlConsulta1=$this->PJeHttp."://pje.$trt.jus.br/consultaprocessual/pages/consultas/ConsultaProcessual.seam";
		$urlConsulta2=$this->PJeHttp."://pje.$trt.jus.br/consultaprocessual/pages/consultas/ListaProcessos.seam";

		$ok = $this->getPJeAntigoCookie($urlCookie);
		if ($ok)
		{
			$html=$this->processaPJeNovo_envia($urlConsulta1, $urlConsulta2, $numero, $usrAgent);
//echo "\n\n\n\n\n\n\n\n$html\n\n\n\n\n\n\n";
			if (stripos($html,"Consulta processual realizada de acordo com a")===false)
			{
				$ok=false;
				$this->lastError="Não é possível consultar PJe $trt neste momento. Tente novamente mais tarde.";
			} elseif (substr($html,0,6)=="varios")
			{
				$ok=substr($html,7);
			} else
			{
				$ok=$this->processaPJeNovo_html($numero,$html);
			}
		}
		$ckfile=$this->getCookie();
		unlink($ckfile);

		return($ok);
	}


	function processaUrl($url, $header="", $post="", $numero="", $append=false)
	{
		global $gPathTmp,$http_tmp,$usrId;

		// Esta rotina processa uma URL gerando o arquivo /tmp/alitem-url-debug.log para depuração (envio e retorno de dados)
		$arq="===> Numero: $numero\n";
		$arq.="===> Data: ".date("Y-m-d H:i:s")."\n";
		$arq.="===> URL: $url\n";
		$s="";
		foreach ($post as $key=>$row)
		{
			$s.=$key."=".$row."\n";
		}
		$arq.="===> Post: \n$s\n";
		gLog("===> URL (1) $url | post: ".str_replace("\n"," | ",$s));

		$cookie=true;
		$baseUrl=substr($url,0,stripos($url,'br/')+3);
		$ckfile=$this->getCookie();
		$trt=$this->trt;

		// Chamando URL
		$bodyData = http_build_query($post);
		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);

		if ($trt=="trt1")
		{
			$boundary=md5(time());
			$bodyData = '';
			foreach($post as $key => $value){
				$bodyData.= "--$boundary\r\nContent-Disposition: form-data; name=\"$key\"\r\n\r\n$value\r\n";
			}
			$bodyData.= "--$boundary--";
			$header[]="Content-Type: multipart/form-data; boundary=$boundary";
			//$header[]="Content-Length: ".strlen($bodyData);
			$header[]="Accept-Encoding: gzip, deflate";
			curl_setopt ($ch, CURLOPT_ENCODING, 'gzip, deflate');
			$arq.="===> Body Data:\n".$bodyData."\n\n";
		}
		$s="";
		foreach ($header as $key=>$row)
		{
			$s.=$row."\n";
		}
		$arq.="===> Header: \n$s";
		$arq.="User-Agent: ".$this->usrAgent."\n\n";
		gLog("===> URL (2) Headers: ".str_replace("\n"," | ",$s));


		if (strpos($url,"https")!==false)
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		if (is_array($header))
			curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
		curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);

		if (is_array($post))
		{
			curl_setopt ($ch, CURLOPT_POST, true);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
		}
		//curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt ($ch, CURLOPT_VERBOSE, true);
		curl_setopt ($ch, CURLOPT_HEADER, true);

		$html = curl_exec ($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		// Notou-se em Novembro de 2014 que o servidor do PJe retorna vários HEADERs pra confundir o robo
		// O http_code obtido através da função do PHP (acima) retorna um código falso!
		// O último HEADER é o que deve ser considerado
		$codes=explode('HTTP/1.1', $html);
		$http_code='';
		foreach ($codes as $code)
		{
			$code=trim($code);
			if (substr($code,0,4)=='302 ')
				$http_code='302';
			if (substr($code,0,4)=='200 ' && $http_code=='')
				$http_code='200';
		}

		$arq.="===> Codigo de resposta: $http_code\n";

		$arq.=str_replace("\r","",$html);

		// Se encontrar 302, significa que o servidor redirecionou a página para outro endereço
		// O código abaixo identifica esta página e redireciona automaticamente, buscando os respectivos dados
		if ($http_code == '302')
		{
			$headers = explode("\n",$html);
			$j = count($headers);
			$redir=$url;
			for($i = 0; $i < $j; $i++){
				if(strpos($headers[$i],"Location:") !== false)
				{
					$redir = trim(str_replace("Location:","",$headers[$i]));
					break;
				}
			}
			curl_close ($ch);
			$this->lastRedir=$redir;
			$arq.="\n===> Redirecionamento: $redir\n\n";
			gLog("===> URL (3) Redir: $redir");
			$ch = curl_init ($redir);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			if (strpos($redir,"https")!==false)
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout em segundos
			curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
			curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);
			curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
			curl_setopt ($ch, CURLOPT_VERBOSE, true);
			curl_setopt ($ch, CURLOPT_HEADER, true);
			$html = curl_exec ($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$headers = explode("\n",$html);
			$j = count($headers);
			$redir=$url;
			for($i = 0; $i < $j; $i++){
				if(strpos($headers[$i],"Location:") !== false)
				{
					$redir = trim(str_replace("Location:","",$headers[$i]));
					break;
				}
			}
			curl_close ($ch);

			// Descobrindo os códigos de retorno à requisição e considerando somente o último
			$codes=explode('HTTP/1.1', $html);
			$http_code='';
			foreach ($codes as $code)
			{
				$code=trim($code);
				if (substr($code,0,4)=='302 ')
					$http_code='302';
				if (substr($code,0,4)=='200 ' && $http_code=='')
					$http_code='200';
			}
			$arq.="===> Codigo de resposta: $http_code\n";
			$arq.=str_replace("\r","",$html);


			if (strpos($this->lastRedir,'CaptchaProcesso.seam')!==false)
			{
				gLog("===> URL NECESSITA DE CAPTCHA (ident. da URL)");
				$urlCaptcha=substr($redir,0,strpos($redir,'/consultaprocessual')).'/consultaprocessual/seam/resource/captcha';
				$cnt=0;
				$cntMax=15;
				while ($cnt<$cntMax)
				{
					$cnt++;
					gLog("===> URL >>> Obtendo Captcha: ".$urlCaptcha);
					$ch = curl_init ();
					curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);

					curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt ($ch, CURLOPT_URL, $urlCaptcha);
					curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
					$output = curl_exec ($ch); // the real thing

					$img_file_name =  "imagem-$usrId-".date("isu").".jpg";
					$img_data = imagecreatefromstring($output);
					gLog("===> URL >>> Captcha salvo: ".$http_tmp."/".$img_file_name);
					melhoraCaptcha($img_data,$gPathTmp.$img_file_name);
					//gLog("===> URL >>> Executando OCR: ".$cmd);
					$ocr=ocr($img_file_name);
					gLog("===> URL >>> OCR: [ ".$ocr." ] ");
					$img_link = $http_tmp."/".$img_file_name;
					//echo "<br><img src='".$img_link."' border='0'><br><br><h1>$ocr</h1>";
					$ocrOk=false;
					if ($ocr<>"")
					{
						$html=$this->processaUrl_tentaCaptcha($html, $ocr);
						if ($html<>'')
							$ocrOk=true;
					}

					if (!$ocrOk)
					{
						$ch = curl_init ($redir);
						curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
						if (strpos($redir,"https")!==false)
							curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout em segundos
						curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
						curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);
						curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
						curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
						curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
						curl_setopt ($ch, CURLOPT_VERBOSE, true);
						curl_setopt ($ch, CURLOPT_HEADER, false);
						$html = curl_exec ($ch);
						$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						curl_close ($ch);
					} else
					{
						gLog("===> URL >>> OCR PASSOU !!!");
						$cnt=$cntMax+1;
					}
				}
				$http_code='200';
			}


			if ($http_code=="302")
			{
				$this->lastRedir=$redir;
				$arq.="\n===> Redirecionamento: $redir\n\n";
				gLog("===> URL (4) Redir: $redir");
				$ch = curl_init ($redir);
				curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
				if (strpos($redir,"https")!==false)
					curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout em segundos
				curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
				curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);
				curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
				curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
				curl_setopt ($ch, CURLOPT_VERBOSE, true);
				curl_setopt ($ch, CURLOPT_HEADER, true);
				$html = curl_exec ($ch);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$headers = explode("\n",$html);
				$j = count($headers);
				$redir=$url;
				for($i = 0; $i < $j; $i++){
					if(strpos($headers[$i],"Location:") !== false)
					{
						$redir = trim(str_replace("Location:","",$headers[$i]));
						break;
					}
				}
				curl_close ($ch);


				// Descobrindo os códigos de retorno à requisição e considerando somente o último
				$codes=explode('HTTP/1.1', $html);
				$http_code='';
				foreach ($codes as $code)
				{
					$code=trim($code);
					if (substr($code,0,4)=='302 ')
						$http_code='302';
					if (substr($code,0,4)=='200 ' && $http_code=='')
						$http_code='200';
				}
				$arq.="===> Codigo de resposta: $http_code\n";
				$arq.=str_replace("\r","",$html);



				if ($http_code=="302")
				{
					$this->lastRedir=$redir;
					$arq.="\n===> Redirecionamento: $redir\n\n";
					gLog("===> URL (5) Redir: $redir");
					$ch = curl_init ($redir);
					curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
					if (strpos($redir,"https")!==false)
						curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout em segundos
					curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
					curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);
					curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
					curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
					curl_setopt ($ch, CURLOPT_VERBOSE, true);
					curl_setopt ($ch, CURLOPT_HEADER, false);
					$html = curl_exec ($ch);
					$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close ($ch);


					if (strpos($html,'resource/captcha')!==false)
					{
						gLog("===> URL NECESSITA DE CAPTCHA");
						$urlCaptcha=substr($redir,0,strpos($redir,'/consultaprocessual')).'/consultaprocessual/seam/resource/captcha';
						$cnt=0;
						$cntMax=15;
						while ($cnt<$cntMax)
						{
							$cnt++;
							gLog("===> URL >>> Obtendo Captcha: ".$urlCaptcha);
							$ch = curl_init ();
							curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);

							curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt ($ch, CURLOPT_URL, $urlCaptcha);
							curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
							curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
							$output = curl_exec ($ch); // the real thing

							$img_file_name =  "imagem-$usrId-".date("isu").".jpg";
							$img_data = imagecreatefromstring($output);
							gLog("===> URL >>> Captcha salvo: ".$http_tmp."/".$img_file_name);
							melhoraCaptcha($img_data,$gPathTmp.$img_file_name);
							gLog("===> URL >>> Executando OCR: ".$cmd);
							$ocr=ocr($img_file_name);
							gLog("===> URL >>> OCR: [ ".$ocr." ] ");
							$img_link = $http_tmp."/".$img_file_name;
							//echo "<br><img src='".$img_link."' border='0'><br><br><h1>$ocr</h1>";
							$ocrOk=false;
							if ($ocr<>"")
							{
								$html=$this->processaUrl_tentaCaptcha($html, $ocr);
								if ($html<>'')
									$ocrOk=true;
							}

							if (!$ocrOk)
							{
								$ch = curl_init ($redir);
								curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
								if (strpos($redir,"https")!==false)
									curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
								curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout em segundos
								curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
								curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);
								curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
								curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
								curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
								curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');
								curl_setopt ($ch, CURLOPT_VERBOSE, true);
								curl_setopt ($ch, CURLOPT_HEADER, false);
								$html = curl_exec ($ch);
								$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
								curl_close ($ch);
							} else
							{
								gLog("===> URL >>> OCR PASSOU !!!");
								$cnt=$cntMax+1;
							}
						}
					}
				}

			}
		}
		// // Mudando ISO pra UTF
		// if (stripos($html,"ISO-8859")!==false)
		// {
		// 	$html=utf8_encode($html);
		// 	if (stripos($html,"ISO-8859-1")!==false)
		// 		$html=str_ireplace("ISO-8859-1","UTF-8",$html);
		// 	if (stripos($html,"ISO-8859")!==false)
		// 		$html=str_ireplace("ISO-8859","UTF-8",$html);
		// }

		$arq.="\n\n\n\n";
		if ($append)
			file_put_contents("/tmp/alitem-url-debug-".$_SERVER['SERVER_ADDR'].".log", $arq, FILE_APPEND);
		else
			file_put_contents("/tmp/alitem-url-debug-".$_SERVER['SERVER_ADDR'].".log", $arq);
		return($html);
	}


	function processaUrl_tentaCaptcha($html, $ocr)
	{
		if ($html<>'')
		{
			$ok=true;
			$cookie=true;
			$redir='';
			$ckfile=$this->getCookie();
			$trt=$this->trt;
			$host="pje";
			if ($trt=='trt1')
				$host='consultapje';
			$http=$this->PJeHttp;
			$pag = new DOMDocument();
			$pag->preservWhiteSpace = FALSE; //elimina espaços em branco
			$pag->formatOutput = FALSE;
			$pag->loadHTML($html);
			file_put_contents("/tmp/alitem-tentaCaptcha-".$_SERVER['SERVER_ADDR'].".html",$html);
			gLog("===> URL >>> Tentando (1): Tenta usar o OCR obtido no Captcha");

			$url=$http."://$host.$trt.jus.br/consultaprocessual/pages/consultas/CaptchaProcesso.seam";
			$post='';
			$post['consultaProcFormForm']="consultaProcFormForm";
			$post['consultaProcFormDecorate:verifyCaptcha']=$ocr;
			if ($trt=='trt3' || $trt=='trt4')
				$post['j_id51']="true";
			if ($trt=='trt18')
				$post['j_id54']="true";
			$post['consultar']="Consultar";
			$post['p_ano']="";
			$post['p_vara']="";
			$post['p_nome']="";
			$post['p_ano2']="";
			$post['p_vara2']="";
			$post['dt_autuacao']="";
			$post['num_pje']=$pag->getElementById('num_pje')->getAttribute('value');
			$post['grau_pje']=$pag->getElementById('grau_pje')->getAttribute('value');
			$post['javax.faces.ViewState']=$pag->getElementById('javax.faces.ViewState')->getAttribute('value');

			$header="";
			$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
			$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
			$header[]="Connection: keep-alive";
			//$header[]="Referer: https://$host.$trt.jus.br/consultaprocessual/pages/consultas/CaptchaProcesso.seam";
			$header[]="DNT: 1";
			$header[]="Host: $host.$trt.jus.br";

			$s="";
			foreach ($post as $key=>$row)
			{
				$s.=$key."=".$row."\n";
			}

			gLog("===> URL >>> Tentando (2): $url | post: ".str_replace("\n"," | ",$s));

			$cookie=true;
			$baseUrl=substr($url,0,stripos($url,'br/')+3);
			$ckfile=$this->getCookie();

			// Chamando URL
			$bodyData = http_build_query($post);
			$ch = curl_init ($url);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);

			$boundary=md5(time());
			$bodyData = '';
			foreach($post as $key => $value){
				$bodyData.= "--$boundary\r\nContent-Disposition: form-data; name=\"$key\"\r\n\r\n$value\r\n";
			}
			$bodyData.= "--$boundary--";
			$header[]="Content-Type: multipart/form-data; boundary=$boundary";
			//$header[]="Content-Length: ".strlen($bodyData);
			$header[]="Accept-Encoding: gzip, deflate";
			curl_setopt ($ch, CURLOPT_ENCODING, 'gzip, deflate');


			if (strpos($url,"https")!==false)
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
			if (is_array($header))
				curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
			curl_setopt ($ch, CURLOPT_USERAGENT, $this->usrAgent);

			if (is_array($post))
			{
				curl_setopt ($ch, CURLOPT_POST, true);
				curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
			}
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt ($ch, CURLOPT_AUTOREFERER, true);

			curl_setopt ($ch, CURLOPT_VERBOSE, true);
			curl_setopt ($ch, CURLOPT_HEADER, true);

			$html = curl_exec ($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if (strpos($html,'Verifica&ccedil;&atilde;o incorreta')!==false || strpos($html,'incorrect response')!==false || strpos($html,'errorSession.seam')!==false)
			{
				$html='';
			}
		}
		return($html);
	}



	function processaPJeNovo_envia($url1, $url2, $numero, $usrAgent)
	{
		global $usrId;

		gLog("===> PJe: Enviando parametros (novo): $url1");
		$cookie=true;
		$baseUrl=substr($url1,0,stripos($url1,'br/')+3);

		$ckfile=$this->getCookie();

		$trt=$this->trt;

		// Busca cabeçalho do processo

		$post='';

		$post['numeroFormatDecorate:numero_format']=$numero;
		$post['j_id49']='true';
		$post['consultaProcFormFormatForm']='consultaProcFormFormatForm';
		$post['consultarNumFormat']='Pesquisar';
		$post['javax.faces.ViewState']=$this->viewState;

		$header="";
		$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
		$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
		$header[]="Cache-Control: no-cache";
		$header[]="Connection: keep-alive";
		$header[]="Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
		$header[]="DNT: 1";
		$header[]="Host: pje.$trt.jus.br";

		$html=$this->processaUrl($url1,$header,$post, $numero);










/*
		$bodyData = http_build_query($post);
		$ch = curl_init ($url1);
		if (strpos($url1,"https")!==false)
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
		curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);

		curl_setopt ($ch, CURLOPT_POST, true);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
		curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt ($ch, CURLOPT_ENCODING, 'gzip,deflate');

		curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt ($ch, CURLOPT_VERBOSE, true);
		curl_setopt ($ch, CURLOPT_HEADER, true);

		$html = curl_exec ($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (strpos($html, 'HTTP/1.1 302 Moved Temporarily')!==false)
			$http_code = '302';


		gLog("===> PJe: Código HTTP do envio: $http_code");

		if ($http_code == '302')
		{
			file_put_contents('/tmp/response.html', $html);exit;
			$headers = explode("\n",$html);
			$j = count($headers);
			$redir=$url;
			for($i = 0; $i < $j; $i++){
			// if we find the Location header strip it and fill the redir var
				if(strpos($headers[$i],"Location:") !== false)
				{
					$redir = trim(str_replace("Location:","",$headers[$i]));
					break;
				}
			}
			curl_close ($ch);

			gLog("===> PJe: Redirecionamento (1): $redir");
			$ch = curl_init ($redir);
			if (strpos($redir,"https")!==false)
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
			curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
			curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);

			//curl_setopt ($ch, CURLOPT_POST, true);
			//curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
			curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
			//curl_setopt ($ch, CURLOPT_REFERER, $referer);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_VERBOSE, true);
			curl_setopt ($ch, CURLOPT_HEADER, true);
			$html = curl_exec ($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		}
*/

		if (stripos($html, 'Processos Encontrados') !== false)
		{
			$this->maisDeUm=true;
			gLog("===> PJe: Retornou mais de uma instância ".strlen($html)." caracteres - código: " . $http_code);
			gLog("===> PJe: Limpando dados - mais de um grau encontrado");
			$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros

			//<img src="/consultaprocessual/img/proc1g_32.png" style="border:0; padding-right:3px; vertical-align: middle;width:23px;">RTOrd-0010297-13.2013.5.01.0049
			//<a href="/consultaprocessual/pages/consultas/DetalhaProcesso.seam?p_seq=10297&amp;p_dig=13&amp;p_ano=2013&amp;p_vara=49&amp;p_num_pje=41222&amp;p_grau_pje=1&amp;dt_autuacao=02%2F04%2F2013&amp;conversationPropagation=begin" id="consultaProcs:0:procId" style="font-size: 14px;" class="linkProcesso"><img src="/consultaprocessual/img/proc1g_32.png" style="border:0; padding-right:3px; vertical-align: middle;width:23px;">RTOrd-0010297-13.2013.5.01.0049</a>
			$faz=true;
			$htmlOrig=$html;
			$cnt=0;
			$referer=$url;
			while ($faz)
			{
				$p=strpos($htmlOrig,'<a href="/consultaprocessual/pages/consultas/DetalhaProcesso.seam?p_seq=');
				$ok='';
				if ($p>0)
				{
					$cnt++;
					$grau_pje=1;
					$pg=strpos($htmlOrig,'p_grau_pje=');
					if ($pg!==false)
					{
						$grau_pje=substr($htmlOrig,$pg+11,1);
					}
					$p=strpos($htmlOrig,'"',$p)+2;
					$htmlOrig=substr($htmlOrig,$p);
					$pFim=strpos($htmlOrig,'"');
					$url=$baseUrl.substr($htmlOrig,0,$pFim);
					$htmlOrig=substr($htmlOrig,$pFim+1);
					$url=str_replace('&amp;','&',$url);
					gLog("===> PJe: Varios ($cnt) grau $grau_pje => Processando URL: $url");
					$ch = curl_init ($url);
					curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);

					if (strpos($url,"https")!==false)
						curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
					curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
					curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);

					curl_setopt ($ch, CURLOPT_POST, false);
					//curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
					//curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
					curl_setopt ($ch, CURLOPT_REFERER, $referer);

					curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
					//curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

					curl_setopt ($ch, CURLOPT_VERBOSE, true);
					curl_setopt ($ch, CURLOPT_HEADER, true);

					$html = curl_exec ($ch);
					$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					if ($http_code == '302')
					{
						//file_put_contents('/tmp/response.html', $html);exit;
						$headers = explode("\n",$html);
						$j = count($headers);
						$redir=$url;
						for($i = 0; $i < $j; $i++){
						// if we find the Location header strip it and fill the redir var
							if(strpos($headers[$i],"Location:") !== false)
							{
								$redir = trim(str_replace("Location:","",$headers[$i]));
								break;
							}
						}
						curl_close ($ch);

						gLog("===> PJe: Varios ($cnt) => Redirecionamento (1): $redir");
						$ch = curl_init ($redir);
						curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
						if (strpos($redir,"https")!==false)
							curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt ($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
						curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
						curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);

						//curl_setopt ($ch, CURLOPT_POST, true);
						//curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
						curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
						//curl_setopt ($ch, CURLOPT_REFERER, $referer);
						curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
						curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt ($ch, CURLOPT_VERBOSE, true);
						curl_setopt ($ch, CURLOPT_HEADER, true);
						$html = curl_exec ($ch);
						$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					}

	//echo "Dados:<br>$url<br>$bodyData<pre>";htmlentities(print_r($response)); print_r($post);echo "</pre>";echo "<br><br>\n\n\n\n\n\nHTML:\n\n[ ".htmlentities($html)." ]\n\n\n\n\n\n";exit;

					gLog("===> PJe: Varios ($cnt) => Retornou ".strlen($html)." caracteres - código: " . $http_code);
					$html=$this->processaJavascript($html,$baseUrl);
					$ok.=$this->processaPJeNovo_html($numero,$html,$grau_pje);
				} else
				{
					$faz=false;
					if ($cnt==0)
						$html='varios;'.$ok;
				}
			}

		}


//echo "URL:<br>$url<br>$bodyData<pre>";print_r($post);echo "</pre>";echo "<br><br>\n\n\n\n\n\nHTML:\n\n[ ".htmlentities($html)." ]\n\n\n\n\n\n";exit;
		return($html);
	}


//Nenhum Processo Encontrado

	function processaPJeNovo_html($numero,$html, $grau=1)
	{
		gLog("===> PJe: Processando dados obtidos - grau $grau");
		if (!$this->maisDeUm)
		{
			$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
			gLog("===> PJe: Limpando dados - grau $grau");
		}
		// Cortando topo e rodapé
		$html=substr($html,strpos($html,"Detalhes do Processo"));
		$p=strpos($html,"DOCTYPE");
		if ($p>0)
			$p=$p-2;
		$html=substr($html,0,$p);

		// Obtendo campos
		$this->campos['numero']=$numero;
		$this->campos['orgao_julgador_atual']=$this->processaPJe_campo($html,$numero,'font');

		// Polo ativo (reclamante)
		$polo="";
		if (strpos($html,"RECLAMANTE(S):")!==false)
		{
			$polo=substr($html,strpos($html,"RECLAMANTE(S):")+14);
			$polo=substr($polo,0,strpos($polo,"RECLAMADO(S):"));
		}
		if (strpos($html,"AUTOR(S):")!==false)
		{
			$polo=substr($html,strpos($html,"AUTOR(S):")+9);
			$polo=substr($polo,0,strpos($polo,"R&Eacute;U(S):"));
		}
		if (strpos($html,"EXEQUENTE(S):")!==false)
		{
			$polo=substr($html,strpos($html,"EXEQUENTE(S):")+13);
			$polo=substr($polo,0,strpos($polo,"EXECUTADO(S):"));
		}
		if (strpos($html,"CONSIGNAT&Aacute;RIO(S):")!==false)
		{
			$polo=substr($html,strpos($html,"CONSIGNAT&Aacute;RIO(S):")+17);
			$polo=substr($polo,0,strpos($polo,"CONSIGNANTE(S):"));
		}

		if (strpos($html,"REQUERENTE(S):")!==false)
		{
			$polo=substr($html,strpos($html,"REQUERENTE(S):")+14);
			$polo=substr($polo,0,strpos($polo,"REQUERIDO(S):"));
		}
		if (strpos($html,"RECORRIDO(S):")!==false)
		{
			$polo=substr($html,strpos($html,"RECORRIDO(S):")+13);
			$polo=substr($polo,0,strpos($polo,"</dd>"));
		}
		$polo=str_replace('<!-- :  -->','',$polo);
		$polo=str_replace("</td>","\n</td>",$polo);
		$polo=trim($polo);
		$polo=str_replace("ADVOGADO:","\nADVOGADO:", $polo);
		$polo=strip_html_tags($polo);
		$polo=strip_tags($polo);
		$polo=str_replace("(+ 1)","", $polo);
		$polo=str_replace("(+ 2)","", $polo);
		$polo=str_replace("(+ 3)","", $polo);
		$polo=str_replace("(+ 4)","", $polo);
		$polo=str_replace("(+ 5)","", $polo);
		$polo=str_replace("(+ 6)","", $polo);
		$linhas=explode("\n",$polo);

//echo "\n\n\n\n$polo\n\n\n\n\n";
//		file_put_contents('/tmp/polo.txt', $polo);
		$pes="";
		$adv="";
		for ($a=0; $a<count($linhas);$a++)
		{
			$lin=trim($linhas[$a]);
			if ($lin<>"")
			{
				if (substr($lin,0,8)=="ADVOGADO")
				{
					$txt=substr(trim($lin),10);
					$adv=str_replace($txt,'',$adv);
					$adv.=$txt."\n";
				} else
				{
					$txt=trim($lin);
					$pes=str_replace($txt,'',$pes);
					$pes.=$txt."\n";
				}
			}
		}
		if (strpos($html,"RECORRIDO(S):")===false)
		{
			$this->campos['autor']=trim($pes);
			$this->campos['autor_advogado']=trim(autoencode($adv));
		}

		// Polo passivo (reclamado)
		if (strpos($html,"RECLAMADO(S):")!==false)
		{
			$polo=substr($html,strpos($html,"RECLAMADO(S):")+13);
			$polo=substr($polo,0,strpos($polo,"Movimento(s) / Documento(s)"));
		}
		if (strpos($html,"R&Eacute;U(S):")!==false)
		{
			$polo=substr($html,strpos($html,"R&Eacute;U(S):")+14);
			$polo=substr($polo,0,strpos($polo,"Movimento(s) / Documento(s)"));
		}
		if (strpos($html,"EXECUTADO(S):")!==false)
		{
			$polo=substr($html,strpos($html,"EXECUTADO(S):")+13);
			$polo=substr($polo,0,strpos($polo,"Movimento(s) / Documento(s)"));
		}
		if (strpos($html,"CONSIGNANTE(S):")!==false)
		{
			$polo=substr($html,strpos($html,"CONSIGNANTE(S):")+15);
			$polo=substr($polo,0,strpos($polo,"Movimento(s) / Documento(s)"));
		}
		if (strpos($html,"REQUERIDO(S):")!==false)
		{
			$polo=substr($html,strpos($html,"REQUERIDO(S):")+13);
			$polo=substr($polo,0,strpos($polo,"Movimento(s) / Documento(s)"));
		}
		if (strpos($html,"RECORRENTE(S):")!==false)
		{
			$polo=substr($html,strpos($html,"RECORRENTE(S):")+14);
			$polo=substr($polo,0,strpos($polo,"</dd>"));
		}

		$polo=str_replace('<!-- :  -->','',$polo);
		$polo=str_replace("</td>","\n</td>",$polo);
		$polo=trim($polo);
		$polo=str_replace("ADVOGADO:","\nADVOGADO:", $polo);
		$polo=strip_html_tags($polo);
		$polo=strip_tags($polo);
		if (strpos($polo,"Ver na &Iacute;ntegra")!==false)
			$polo=substr($polo,0,strpos($polo,"Ver na &Iacute;ntegra"));
		$polo=str_replace("(+ 1)","", $polo);
		$polo=str_replace("(+ 2)","", $polo);
		$polo=str_replace("(+ 3)","", $polo);
		$polo=str_replace("(+ 4)","", $polo);
		$polo=str_replace("(+ 5)","", $polo);
		$polo=str_replace("(+ 6)","", $polo);

//		$polo = html_entity_decode(strip_tags($polo));
		$linhas=explode("\n",$polo);
		$pes="";
		$adv="";
		for ($a=0; $a<count($linhas);$a++)
		{
			$lin=trim($linhas[$a]);
			if ($lin<>"")
			{
				if (substr($lin,0,8)=="ADVOGADO")
				{
					$txt=substr(trim($lin),10);
					$adv=str_replace($txt,'',$adv);
					$adv.=$txt."\n";
				} else
				{
					$txt=trim($lin);
					$pes=str_replace($txt,'',$pes);
					$pes.=$txt."\n";
				}
			}
		}
		if (strpos($pes,'Recorrido(')!==false)
			$pes=substr($pes,0,strpos($pes,'Recorrido('));

		if (strpos($html,"RECORRIDO(S):")===false)
		{
			$this->campos['reu']=trim($pes);
			$this->campos['reu_advogado']=trim(autoencode($adv));
		}


		// Execução provisória


//echo "<pre>";print_r($polo);echo "</pre><br><br>";
		// Andamentos
		if (strpos($html,'<tbody id="consultaProcessos:tb')!==false)
			$and=substr($html,strpos($html,'<tbody id="consultaProcessos:tb'));
		else
			$and=substr($html,strpos($html,"Movimento / Documento"));

		$and=substr($and,0,strpos($and,"</tbody"));
		$and=substr($and,strpos($and,"<tbody"));
		$and=str_replace('</td>','</td>'."\n",$and);
		$and=strip_tags($and);
//echo "\n\n\n\n\n$and\n\n\n\n\n";
		$linhas=explode("\n",$and);
		$pes="";
		$adv="";
		for ($a=0; $a<count($linhas);$a++)
		{
			$lin=trim($linhas[$a]);
			if ($lin<>"")
			{
				if ((substr($lin,2,1)=='/') && (substr($lin,5,1)=='/') && (substr($lin,10,1)==' '))
				{
					$data=htmlFormataData($lin);
					$desc=trim(autoencode($linhas[$a+3]));
					if (($data<>"20--") && ($desc<>""))
					{
						$rec='';
						$rec['grau']=intval($grau);
						$rec['data']=$data;
						$rec['descricao']=$desc;
						$this->net->records[]=$rec;
					}
				}
			}
		}


		$this->menorData($this->net->records);
		$this->andamentos=$this->net->getRecords();
//echo "Campos: <pre>";print_r($this->campos);print_r($this->andamentos);echo "</pre>";
		$ok=true;
		return($ok);
	}







	function processaPJeAntigo_html($numero,$html)
	{
		gLog("===> PJe: Processando dados obtidos");
		$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
		// Cortando topo e rodapé
		$html=substr($html,strpos($html,"Dados do Processo")+23);
		$html=substr($html,0,strpos($html,"<style "));

		// Obtendo campos
		$this->campos['numero']=$numero;
		$this->campos['orgao_julgador_atual']=$this->processaPJe_campo($html,'&Oacute;rg&atilde;o Julgador','div');
		$this->campos['data_inicio']=$this->processaPJe_campo($html,'Data da Distribui&ccedil;&atilde;o','div');
		$this->campos['descricao']=$this->processaPJe_campo($html,'Classe Judicial','div');

		// Polo ativo (reclamante)
		$polo=substr($html,strpos($html,"Polo Ativo")+16);
		$polo=substr($polo,0,strrpos($polo,"<div"));
		$polo=substr($polo,strpos($polo,"<table "));
		$polo=strip_tags(substr($polo,0,strpos($polo,"</table")+8));
		$polo=str_replace("Participa&ccedil;&atilde;oTipo de Participa&ccedil;&atilde;o","",$polo);
		$linhas=explode("\n",$polo);
		$pes="";
		$adv="";
		for ($a=0; $a<count($linhas);$a++)
		{
			$lin=trim($linhas[$a]);
			if ($lin=="ADVOGADO")
				$adv.=substr(trim($linhas[$a-1]),4)."\n";
			if (strpos($lin,"RECLAMA")!==false)
				$pes.=trim($linhas[$a-1])."\n";
			if (strpos($lin,"AUTOR")!==false)
				$pes.=trim($linhas[$a-1])."\n";
		}
		$this->campos['autor']=trim($pes);
		$this->campos['autor_advogado']=trim(autoencode($adv));

		// Polo passivo (reclamada)
		$polo=substr($html,strpos($html,"Polo Passivo")+17);
		$polo=substr($polo,0,strrpos($polo,"<div"));
		$polo=substr($polo,strpos($polo,"<table "));
		$polo=strip_tags(substr($polo,0,strpos($polo,"</table")+8));
		$polo=str_replace("Participa&ccedil;&atilde;oTipo de Participa&ccedil;&atilde;o","",$polo);
		$linhas=explode("\n",$polo);
		$pes="";
		$adv="";
		for ($a=0; $a<count($linhas);$a++)
		{
			$lin=trim($linhas[$a]);
			if ($lin=="ADVOGADO")
				$adv.=substr(trim($linhas[$a-1]),4)."\n";
			if (strpos($lin,"RECLAMA")!==false)
				$pes.=trim($linhas[$a-1])."\n";
			if (strpos($lin,"R&Eacute;U")!==false)
				$pes.=trim($linhas[$a-1])."\n";
		}
		$this->campos['reu']=trim($pes);
		$this->campos['reu_advogado']=trim($adv);

		// Andamentos
		$and=substr($html,strpos($html,"Movimento"));
		$and=substr($and,0,strrpos($and,"</tbody"));
		$and=substr($and,strpos($and,"<tbody"));
		$and=str_replace('</td>','</td>'."\n",$and);
		$and=strip_tags($and);

		$linhas=explode("\n",$and);
		$pes="";
		$adv="";
		for ($a=0; $a<count($linhas);$a++)
		{
			$lin=trim($linhas[$a]);
			if ($lin<>"")
			{
				//16/12/2013 12:34:31 - Expedido(a) Notifica&ccedil;&atilde;o a(o) destinat&aacute;rio

				$fld[0]=substr($lin,0,19);
				$fld[1]=substr($lin,22);
				$data=htmlFormataData(trim($fld[0]));
				$desc=trim(autoencode($fld[1]));
				if (($data<>"20--") && ($desc<>""))
				{
					$rec='';
					$rec['data']=$data;
					$rec['descricao']=$desc;
					$this->net->records[]=$rec;
				}
			}
		}


		$this->menorData($this->net->records);
		$this->andamentos=$this->net->getRecords();
//		echo "Campos: <pre>";print_r($this->campos);print_r($this->andamentos);echo "<br><br>Polo: \n\n\n<pre>";print_r($linhas);echo "</pre>\n\n\n<br><br>";echo "</pre>\n\n\n\n\n$html\n\n\n\n\n\n";exit;
		$ok=true;
		return($ok);
	}








	function receiveResponse($curlHandle,$xmldata)
	{
		echo "\n\n\n\nLendo: \n\n".$xmldata."\n\n\n\n";
		$this->curlData.=$xmldata;
		$this->curlLength+=strlen($xmldata);
		return ($this->curlLength);
	}


	// Extrai um valor de campo no formato HTML resultante de uma consulta no PJe
	function extractPJeField($html, $id)
	{
		/*
		 *

	<td class="rich-table-cell" id="consultaProcessoList:0:j_id315" colspan="1" rowspan="1">
		<div style="width: 100%;" class="">
			<span style="">0010093-94.2013.5.01.0072</span>
		</div>
	</td>

		 */
		$sai="";
		$p1=strpos($html,$id);
		if ($p1!==false)
		{
			$html=substr($html,$p1);
			$p2=strpos($html,"<div");
			if ($p2!==false)
			{
				$html=substr($html,$p2);
				$html=substr($html,0,strpos($html,"<//div")+6);
				$sai=strip_tags($html);
			}
		}
		return($sai);
	}


	function setaCookie()
	{
		global $usrId;
		$ckfile = tempnam(sys_get_temp_dir(), "alitem-cookie-".$usrId."-".date("Hm")."-");
		$this->setCookie($ckfile);
	}













	function processaTJPJe($url, $numero, $html = "")
	{
		$ok = true;
		if ($html == '')
		{
			$robo = new OrgaoTJPJe("{title: TJ-Pje; url: ".$url."}");
			if ($robo->buscaProcesso($numero))
			{

			} else {
				$this->lastError=$robo->errorCode." - ".$robo->errorMessage;
				$ok = false;
			}
			$this->net->html = $robo->html;
		} else {
			$this->net->html = $html;
		}
		if ($ok)
		{

			gLog($numero.' - Processando dados obtidos...');
			$robo->padronizaDados();
			$this->campos = $robo->campos;
			$this->andamentos = $robo->andamentos;
		} else {
			gLog($numero . " - Erro: ".$tjpje->errorCode.' - '.$tjpje->errorMessage);
		}
		return($ok);
	}

	function processaTJPB($numero, $html = "")
	{
		// Variáveis
		global $usrId;
		$ok = false;
		if (ehPje($numero))
		{
			gLog("$numero - É PJe!");
			$ok=$this->processaTJPJe('https://pje.tjpb.jus.br/pje/ConsultaPublica/listView.seam',$numero, $html);
		}
		return($ok);
	}

	function processaTJPE($numero, $html = "")
	{
		// Variáveis
		global $usrId;
		$ok = false;
		if (ehPje($numero))
		{
			gLog("$numero - É PJe!");
			$ok=$this->processaTJPJe('https://pje.tjpe.jus.br/1g/ConsultaPublica/listView.seam',$numero, $html);
		}
		return($ok);
	}

	function processaTJRN($numero, $html = "")
	{
		// Variáveis
		global $usrId;
		$ok = false;
		if (ehPje($numero))
		{
			gLog("$numero - É PJe!");
			$ok=$this->processaTJPJe('https://pje.tjrn.jus.br/consulta1grau/ConsultaPublica/listView.seam',$numero, $html);
		}
		return($ok);
	}

	function processaTJRO($numero, $html = "")
	{
		// Variáveis
		global $usrId;
		$ok = false;
		if (ehPje($numero))
		{
			gLog("$numero - É PJe!");
			$ok=$this->processaTJPJe('http://pje.tjro.jus.br/pg/ConsultaPublica/listView.seam',$numero, $html);
		}
		return($ok);
	}

	function processaTJRR($numero, $html = "")
	{
		// Variáveis
		global $usrId;
		$ok = false;
		if (ehPje($numero))
		{
			gLog("$numero - É PJe!");
			$ok=$this->processaTJPJe('http://pje.tjrr.jus.br/pje/ConsultaPublica/listView.seam',$numero, $html);
		}
		return($ok);
	}


		function processaTJPI($numero, $html = "")
		{
			// Variáveis
			global $usrId;
			$ok = false;
			if (ehPje($numero))
			{
				gLog("$numero - É PJe!");
				$ok=$this->processaTJPJe('https://tjpi.pje.jus.br/pje/ConsultaPublica/listView.seam',$numero, $html);
			}
			return($ok);
		}











	function processaTRTRJ($numero, $html)
	{
		// Variáveis
		global $usrId;

		$ok=true;
		$post='';
		$cookie=true;
		$debug=0;
		$jaExiste=false;
		//0010093-94.2013.5.01.0072
		//if ((substr($numero,11,4)>=2013) && (substr($numero,0,7)>="0000305")) // suspeito que é a partir de 0010000
		//if ((substr($numero,11,4)>=2013) && (substr($numero,0,7)>"0009999")) // suspeito que é a partir de 0010000
		$this->PJeHttp="https";



		if (ehPje($numero))
		{
			// PJe
			// if ($usrId==2)
			// {
				$url = "https://consultapje.trt1.jus.br/consultaprocessual/pages/consultas/ConsultaProcessual.seam";
				$robo = new OrgaoTRTPJe("{title: TRT-Pje; url: ".$url."}");
				if ($robo->buscaProcesso($numero))
				{
					if ($robo->padronizaDados())
					{
						$this->campos = $robo->campos;
						$this->andamentos = $robo->andamentos;
					} else
					{
						$this->lastError=$robo->errorCode." - ".$robo->errorMessage;
						$ok = false;
					}
				} else {
					$this->lastError=$robo->errorCode." - ".$robo->errorMessage;
					$ok = false;
				}
				$this->net->html = $robo->html;

			// } else {
			// 	// Mecanismo velho
			// 	if ($html=="")
			// 		//$ok=$this->processaPJe("trt1",$numero);
			// 		$ok=$this->processaPJeCaptcha("trt1",$numero);
			// 	else
			// 		$ok=$this->processaPJe_html($numero,$html);
			// }

		} else
		{
			gLog("===> TRT RJ - Antigo $numero");
			// Método antigo

			$post='';
			$post['numeroProcessoCNJ']=$numero;
			$post['paginacao']="false";
			$post['indicadorTipoNumeroProcesso']="CNJ";
			$post['varaNumeroAntigo']="001";
			$post['cidadeNumeroAntigo']="RJ";
			$post['tipoNumeroAntigo1']="RT";
			$post['tipoNumeroAntigo2']="AA";
			$post['tipoRecurso']="";
			$post['numeroAntigo']="";
			$post['nomeAdvogado']="";
			$post['anoNumeroAntigo']="";
			$post['numeroProcesso2009']="";
			$post['codigoUf']=" ";
			$post['numeroRegistroOAB']="";
			$post['codigoLetraCarteiraOAB']=" ";
			$post['scrollTop']="0";

			$bodyData = http_build_query($post);
			$parametros = '?'.$bodyData;
/*
paginacao=false
&indicadorTipoNumeroProcesso=CNJ
&numeroProcessoCNJ=0000140-19.2011.5.01.0059
&varaNumeroAntigo=
&cidadeNumeroAntigo=
&tipoNumeroAntigo1=RT&tipoNumeroAntigo2=AA&tipoRecurso=&numeroAntigo=&anoNumeroAntigo=&numeroProcesso2009=&nomeAdvogado=&codigoUf=+&numeroRegistroOAB=&codigoLetraCarteiraOAB=+&scrollTop=0

	http://consulta.trtrio.gov.br/portal/processoListar.do
?numeroProcessoCNJ=0000140-19.2011.5.01.0059
&paginacao=false
&indicadorTipoNumeroProcesso=CN%20J
&varaNumeroAntigo=001
&cidadeNumeroAntigo=RJ
&tipoNumeroAntigo1=RT
&tipoNumeroAntigo2=AA
&tipoRecurso=
&numeroAntigo=
&nomeAdvogado=
&anoNumeroAntigo=
&numeroProcesso2009=
&codigoUf=%20
&numeroRe%20gistroOAB=
&codigoLetraCarteiraOAB=%20
&scrollTop=0

anoNumeroAntigo=
cidadeNumeroAntigo=
codigoLetraCarteiraOAB=
codigoUf=
indicadorTipoNumeroProcesso=CNJ
nomeAdvogado=
numeroAntigo=
numeroProcesso2009=
numeroProcessoCNJ=0000140-19.2011.5.01.0059
numeroRegistroOAB=
paginacao=false
scrollTop=0
tipoNumeroAntigo1=RT
tipoNumeroAntigo2=AA
tipoRecurso=
varaNumeroAntigo=
*/
			$url="http://consulta.trtrio.gov.br/portal/processoListar.do".$parametros;
			$raizAnexo="http://consulta.trtrio.gov.br";
			//echo $url."<br><br>\n\n\n";

			gLog("===> URL: ". $url);
			$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
			$this->net->setRaizAnexo($raizAnexo);


			// Layouts para busca

			$netLayout=new gNetLayout("{name: 'TRAMITAÇÃO PREFERENCIAL'}");
			$netLayout->add("{type: text; name: numero; start:Corpo inicio; end:Andamentos; tag: td; index: 3}");
			$netLayout->add("{type: text; name: descricao; start:Corpo inicio; end:Andamentos; tag: td; index: 6}");
			$netLayout->add("{type: text; name: orgao_julgador_atual; start:Corpo inicio; end:Andamentos; tag: td; index: 15}");
			$netLayout->add("{type: text; name: justica_origem; start:Corpo inicio; end:Andamentos; tag: td; index: 16}");
			$netLayout->add("{type: text; name: setor_origem; start:Corpo inicio; end:Andamentos; tag: td; index: 17}");
			$netLayout->add("{type: text; name: localizacao; start:Corpo inicio; end:Andamentos; tag: td; index: 21}");
			$netLayout->add("{type: text; name: autor; start:=".chr(34)."divPolosAtivos; end:/fieldset; tag: td; index: 0,4,8,12}");
			$netLayout->add("{type: text; name: autor_advogado; start:".chr(34)."divPolosAtivos; end:/fieldset; tag: td; index: 2,6,10,14}");
			$netLayout->add("{type: text; name: autor_advogado_oab; start:".chr(34)."divPolosAtivos; end:/fieldset; tag: td; index: 3,7,11,15}");
			$netLayout->add("{type: text; name: reu; start:".chr(34)."divPolosPassivos; end:/fieldset; tag: td; index: 0,4,8,12}");
			$netLayout->add("{type: text; name: reu_advogado; start:".chr(34)."divPolosPassivos; end:/fieldset; tag: td; index: 2,6,10,14}");
			$netLayout->add("{type: text; name: reu_advogado_oab; start:".chr(34)."divPolosPassivos; end:/fieldset; tag: td; index: 3,7,11,15}");
			$netLayout->add("{type: text; name: recursos; start:".chr(34)."divRecursos; end:/fieldset; tag: td; index: 0,1,2,3; separator:,}");
			$netLayout->add("{type: text; name: relacionados; start:".chr(34)."divProcessosRel; end:/fieldset; tag: td; index: 0}");

			$netLayout->add("{type: array; name: andamentos; start:".chr(34)."divAndamentos; end:Recursos; tag: td; index: 0; id: 0; content: 1,2; step: 5}");

			$this->net->addLayout($netLayout);

			$netLayout=new gNetLayout("{name: 'Dados do processo'}");
			$netLayout->add("{type: text; name: numero; start:Corpo inicio; end:Andamentos; tag: td; index: 1}");
			$netLayout->add("{type: text; name: descricao; start:Corpo inicio; end:Andamentos; tag: td; index: 4}");
			$netLayout->add("{type: text; name: orgao_julgador_atual; start:Corpo inicio; end:Andamentos; tag: td; index: 13}");
			$netLayout->add("{type: text; name: justica_origem; start:Corpo inicio; end:Andamentos; tag: td; index: 14}");
			$netLayout->add("{type: text; name: setor_origem; start:Corpo inicio; end:Andamentos; tag: td; index: 15}");
			$netLayout->add("{type: text; name: localizacao; start:Corpo inicio; end:Andamentos; tag: td; index: 19}");
			$netLayout->add("{type: text; name: autor; start:=".chr(34)."divPolosAtivos; end:/fieldset; tag: td; index: 0,4,8,12}");
			$netLayout->add("{type: text; name: autor_advogado; start:".chr(34)."divPolosAtivos; end:/fieldset; tag: td; index: 2,6,10,14}");
			$netLayout->add("{type: text; name: autor_advogado_oab; start:".chr(34)."divPolosAtivos; end:/fieldset; tag: td; index: 3,7,11,15}");
			$netLayout->add("{type: text; name: reu; start:".chr(34)."divPolosPassivos; end:/fieldset; tag: td; index: 0,4,8,12}");
			$netLayout->add("{type: text; name: reu_advogado; start:".chr(34)."divPolosPassivos; end:/fieldset; tag: td; index: 2,6,10,14}");
			$netLayout->add("{type: text; name: reu_advogado_oab; start:".chr(34)."divPolosPassivos; end:/fieldset; tag: td; index: 3,7,11,15}");
			$netLayout->add("{type: text; name: recursos; start:".chr(34)."divRecursos; end:/fieldset; tag: td; index: 0,1,2,3; separator:,}");
			$netLayout->add("{type: text; name: relacionados; start:".chr(34)."divProcessosRel; end:/fieldset; tag: td; index: 0}");

			$netLayout->add("{type: array; name: andamentos; start:".chr(34)."divAndamentos; end:Recursos; tag: td; index: 0; id: 0; content: 1,2; step: 5}");
			$this->net->addLayout($netLayout);

			// Carregamento de dados

			if ($html=='')
			{
//				$this->net->siteOpen($url, $post, $cookie);
				$this->setaCookie();

				$header="";
				$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
				$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
				$header[]="Cache-Control: no-cache";
				$header[]="Connection: keep-alive";
				$header[]="Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
				$header[]="DNT: 1";
				$header[]="Host: consulta.trtrio.gov.br";

				$ch = curl_init ($url);
				curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
				curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
				curl_setopt ($ch, CURLOPT_USERAGENT, $usrAgent);

				curl_setopt ($ch, CURLOPT_POST, true);
				curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
				curl_setopt ($ch, CURLOPT_AUTOREFERER, true);

				curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
				//curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
				$this->net->html = curl_exec ($ch);
				curl_close ($ch);
			} else
			{
				$this->net->html = $html;
				gLog("===> Lendo dados de HTML enviado pela função...");
			}

			/*
			 * Em 06/2013 foi implementada uma página intermediária com código javascript
			 * que efetua um cálculo para chamar novamente a mesma página, passando
			 * os parâmetros calculados. Medida criada para evitar Robôs.
			 */


			$baseUrl=substr($url,0,stripos($url,'br/')+3);
			$res=$this->processaJavascript($this->net->html,$baseUrl);

			$this->net->html = html_entity_decode(autoencode($res));

gLog("===> 1 ...");
			// Processa dados recebidos
			$this->net->searchLayout();
gLog("===> 2 ...");
			$this->net->parse($debug);
gLog("===> 3 ...");
			$this->dataInicio=$this->net->getStartDate();
			$this->campos=$this->net->getFields();
			$this->andamentos=$this->net->getRecords();


			if (count($this->andamentos)==0)
			{
gLog("===> 4 ...");
				//linhaAndamentos
				$pag = new DOMDocument();
				$pag->preservWhiteSpace = FALSE; //elimina espaços em branco
				$pag->formatOutput = FALSE;

				$pag->loadHTML($this->net->html);
				$a = $pag->getElementById('linhaAndamentos');
				$andam = $a->getElementsByTagName('tr');
				for ($j = 1; $j < $andam->length-1; $j++)
				{
					$este =  $andam->item($j)->getElementsByTagName('td');
					$data =  str_replace('&nbsp;','',strip_tags($este->item(0)->nodeValue));
					$desc =  trim(strip_tags($este->item(2)->nodeValue));
					$rec='';
					$rec['data']=htmlFormataData($data);
					$rec['descricao']=$desc;
					//$rec['anexo']=$anexo;
					$this->net->records[]=$rec;
				}
				$this->dataInicio=$this->net->getStartDate();
				$this->andamentos=$this->net->getRecords();

			}
//echo "<pre>";print_r($this->campos);print_r($this->andamentos);echo "</pre>";exit;
		}


		return($ok);
	}

	function processaTRTMG($numero, $html)
	{
		// Variáveis

		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe
			if ($html=="")
				$ok=$this->processaPJeCaptcha("trt3",$numero);
			else
				$ok=$this->processaPJe_html($numero,$html);
		} else
		{

			$url="http://www.mg.trt.gov.br/";
			//http://as1.trt3.jus.br/consulta/consulta.htm;jsessionid=CDC5073B6128E20A62EEDB0F52636395
			$raizAnexo='http://as1.trt3.jus.br/consulta/consulta.htm';
			$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
			$this->net->setRaizAnexo($raizAnexo);
			// Layouts para busca

			$netLayout=new gNetLayout("{name: 'Agravante(s)'}");

			$netLayout->add("{type: text; name: numero; start:Processo; end:mite Processual; tag: td; index: 1}");
			$netLayout->add("{type: text; name: descricao; start:Processo; end:mite Processual; tag: td; index: 5}");
			$netLayout->add("{type: text; name: orgao_julgador_atual; start:Processo; end:mite Processual; tag: td; index: 7}");

			$netLayout->add("{type: text; name: autor; start:>Agravado(s); end:<table>; tag: td; index: 0}");
			$netLayout->add("{type: text; name: autor_advogado; start:>Agravado(s); end:>Processos Relacionados; tag: td; index: 3,4,5; find: oab}");

			$netLayout->add("{type: text; name: reu; start:>Agravantes(s); end:<table>; tag: td; index: 1,6}");
			$netLayout->add("{type: text; name: reu_advogado; start:>Agravante(s); end:>Distribui; tag: td; index: 4,9}");

			$netLayout->add("{type: array; name: andamentos; start:Documento<; end:Informação atualizada em; tag: td; index: 0; id: 1; content: 0,2,3,4; step: 5}");
			$this->net->addLayout($netLayout);



			$netLayout=new gNetLayout("{name: 'Ver partes da 1a'}");

			$netLayout->add("{type: text; name: numero; start:Processo; end:mite Processual; tag: td; index: 1}");
			$netLayout->add("{type: text; name: descricao; start:Processo; end:mite Processual; tag: td; index: 5}");
			$netLayout->add("{type: text; name: orgao_julgador_atual; start:Processo; end:mite Processual; tag: td; index: 7}");
			$netLayout->add("{type: text; name: autor; start:>RECLAMANTE; end:>Reclamado; tag: td; index: 1,2}");
			$netLayout->add("{type: text; name: autor_advogado; start:>RECLAMANTE; end:>Reclamado; tag: td; index: 4,5}");
			$netLayout->add("{type: text; name: reu; start:>Reclamado; end:>Distribui; tag: td; index: 1,2}");
			$netLayout->add("{type: text; name: reu_advogado; start:>Reclamado; end:>Distribui; tag: td; index: 4,5}");
			$netLayout->add("{type: text; name: relator; start:>Relator(a); end:>Distribui; tag: td; index: 0}");

			$netLayout->add("{type: array; name: andamentos; start:Documento<; end:Informação atualizada em; tag: td; index: 0; id: 1; content: 0,2,3,4; step: 5}");
			$this->net->addLayout($netLayout);

			$netLayout=new gNetLayout("{name: 'Ver partes da 2a'}");

			$netLayout->add("{type: text; name: numero; start:Processo; end:mite Processual; tag: td; index: 1}");
			$netLayout->add("{type: text; name: descricao; start:Processo; end:mite Processual; tag: td; index: 5}");
			$netLayout->add("{type: text; name: orgao_julgador_atual; start:Processo; end:mite Processual; tag: td; index: 7}");
			$netLayout->add("{type: text; name: autor; start:>RECLAMANTE; end:>Reclamado; tag: td; index: 1,2}");
			$netLayout->add("{type: text; name: autor_advogado; start:>RECLAMANTE; end:>Reclamado; tag: td; index: 4,5}");
			$netLayout->add("{type: text; name: reu; start:>Reclamado; end:>Distribui; tag: td; index: 1,2}");
			$netLayout->add("{type: text; name: reu_advogado; start:>Reclamado; end:>Distribui; tag: td; index: 4,5}");
			$netLayout->add("{type: text; name: relator; start:>Relator(a); end:>Distribui; tag: td; index: 0}");

			$netLayout->add("{type: array; name: andamentos; start:Documento<; end:Informação atualizada em; tag: td; index: 0; id: 1; content: 0,2,3,4; step: 5}");
			$this->net->addLayout($netLayout);

			$netLayout=new gNetLayout("{name: 'o De Cumprimento'}");

			$netLayout->add("{type: text; name: numero; start:Processo; end:mite Processual; tag: td; index: 1}");
			$netLayout->add("{type: text; name: descricao; start:Processo; end:mite Processual; tag: td; index: 5}");
			$netLayout->add("{type: text; name: orgao_julgador_atual; start:Processo; end:mite Processual; tag: td; index: 7}");
			$netLayout->add("{type: text; name: autor; start:>REQUERENTE; end:>Requerido; tag: td; index: 1}");
			$netLayout->add("{type: text; name: autor_advogado; start:>REQUERENTE; end:>Requerido; tag: td; index: 4}");
			$netLayout->add("{type: text; name: reu; start:>Requerido; end:>Distribui; tag: td; index: 1,6}");
			$netLayout->add("{type: text; name: reu_advogado; start:>Requerido; end:>Distribui; tag: td; index: 4,9}");
			$netLayout->add("{type: text; name: relator; start:>Relator(a); end:>Distribui; tag: td; index: 0}");

			$netLayout->add("{type: array; name: andamentos; start:Documento<; end:Informação atualizada em; tag: td; index: 0; id: 1; content: 0,2,3,4; step: 5}");
			$this->net->addLayout($netLayout);


			$netLayout=new gNetLayout("{name: 'RECLAMANTE (s)'}");

			$netLayout->add("{type: text; name: numero; start:Processo; end:mite Processual; tag: td; index: 1}");
			$netLayout->add("{type: text; name: descricao; start:Processo; end:mite Processual; tag: td; index: 5}");
			$netLayout->add("{type: text; name: orgao_julgador_atual; start:Processo; end:mite Processual; tag: td; index: 7}");

			$netLayout->add("{type: text; name: autor; start:>RECLAMANTE (s); end:>Reclamado; tag: td; index: 0}");
			$netLayout->add("{type: text; name: autor_advogado; start:>RECLAMANTE (s); end:>Reclamado; tag: td; index: 3,4,5; find: oab}");
			$netLayout->add("{type: text; name: reu; start:>Reclamado; end:>Distribui; tag: td; index: 1,6}");
			$netLayout->add("{type: text; name: reu_advogado; start:>Reclamado; end:>Distribui; tag: td; index: 4,9}");
			$netLayout->add("{type: text; name: relator; start:>Relator(a); end:>Distribui; tag: td; index: 0}");

			$netLayout->add("{type: array; name: andamentos; start:Documento<; end:Informação atualizada em; tag: td; index: 0; id: 1; content: 0,2,3,4; step: 5}");
			$this->net->addLayout($netLayout);


			$netLayout=new gNetLayout("{name: 'AUTOR (s)'}");

			$netLayout->add("{type: text; name: numero; start:Processo; end:mite Processual; tag: td; index: 1}");
			$netLayout->add("{type: text; name: descricao; start:Processo; end:mite Processual; tag: td; index: 5}");
			$netLayout->add("{type: text; name: orgao_julgador_atual; start:Processo; end:mite Processual; tag: td; index: 7}");

			$netLayout->add("{type: text; name: autor; start:>AUTOR (s); end:>Reu; tag: td; index: 0}");
			$netLayout->add("{type: text; name: autor_advogado; start:>AUTOR (s); end:>Reu; tag: td; index: 3,4,5; find: oab}");
			$netLayout->add("{type: text; name: reu; start:>Reu; end:>Distribui; tag: td; index: 1,6}");
			$netLayout->add("{type: text; name: reu_advogado; start:>Reu; end:>Distribui; tag: td; index: 4,9}");
			$netLayout->add("{type: text; name: relator; start:>Relator(a); end:>Distribui; tag: td; index: 0}");

			$netLayout->add("{type: array; name: andamentos; start:Documento<; end:Informação atualizada em; tag: td; index: 0; id: 1; content: 0,2,3,4; step: 5}");
			$this->net->addLayout($netLayout);

			$netLayout=new gNetLayout("{name: '>R&eacute'}");

			$netLayout->add("{type: text; name: numero; start:Processo; end:mite Processual; tag: td; index: 1}");
			$netLayout->add("{type: text; name: descricao; start:Processo; end:mite Processual; tag: td; index: 5}");
			$netLayout->add("{type: text; name: orgao_julgador_atual; start:Processo; end:mite Processual; tag: td; index: 7}");

			$netLayout->add("{type: text; name: autor; start:>Autor; end:<table>; tag: td; index: 0}");
			$netLayout->add("{type: text; name: autor_advogado; start:>Autor; end:>Reu; tag: td; index: 3,4,5; find: oab}");
			$netLayout->add("{type: text; name: reu; start:>Réu; end:<table>; tag: td; index: 1,6}");
			$netLayout->add("{type: text; name: reu_advogado; start:Réu; end:>Distribui; tag: td; index: 4,9}");
			$netLayout->add("{type: text; name: relator; start:>Relator(a); end:>Distribui; tag: td; index: 0}");

			$netLayout->add("{type: array; name: andamentos; start:Documento<; end:Informação atualizada em; tag: td; index: 0; id: 1; content: 0,2,3,4; step: 5}");
			$this->net->addLayout($netLayout);


			$netLayout=new gNetLayout("{name: 'AUTOR'}");

			$netLayout->add("{type: text; name: numero; start:Processo; end:mite Processual; tag: td; index: 1}");
			$netLayout->add("{type: text; name: descricao; start:Processo; end:mite Processual; tag: td; index: 5}");
			$netLayout->add("{type: text; name: orgao_julgador_atual; start:Processo; end:mite Processual; tag: td; index: 7}");

			$netLayout->add("{type: text; name: autor; start:>AUTOR; end:<table>; tag: td; index: 0}");
			$netLayout->add("{type: text; name: autor_advogado; start:>AUTOR; end:>Reu; tag: td; index: 3,4,5; find: oab}");
			$netLayout->add("{type: text; name: reu; start:>Reu; end:<table>; tag: td; index: 1,6}");
			$netLayout->add("{type: text; name: reu_advogado; start:>Reu; end:>Distribui; tag: td; index: 4,9}");
			$netLayout->add("{type: text; name: relator; start:>Relator(a); end:>Distribui; tag: td; index: 0}");

			$netLayout->add("{type: array; name: andamentos; start:Documento<; end:Informação atualizada em; tag: td; index: 0; id: 1; content: 0,2,3,4; step: 5}");
			$this->net->addLayout($netLayout);


			$netLayout=new gNetLayout("{name: 'Origem:'}");

			$netLayout->add("{type: text; name: numero; start:Processo; end:mite Processual; tag: td; index: 1}");
			//$netLayout->add("{type: text; name: descricao; start:Processo; end:mite Processual; tag: td; index: 4}");
			$netLayout->add("{type: text; name: justica_origem; start:Processo; end:mite Processual; tag: td; index: 5}");

			$netLayout->add("{type: text; name: autor; start:>Recorrente(s); end:(s); tag: td; index: 1}");
			$netLayout->add("{type: text; name: autor_advogado; start:>Recorrente(s); end:(s); tag: td; index: 3}");
			$netLayout->add("{type: text; name: reu; start:>Recorrido; end:>Julgador; tag: td; index: 1,3; find: span}");
			$netLayout->add("{type: text; name: reu_advogado; start:>Recorrido; end:>Julgador; tag: td; index:3,5,7; find: oab}");
			$netLayout->add("{type: text; name: relator; start:>Relator(a); end:>Julgador; tag: td; index: 1}");

			$netLayout->add("{type: array; name: andamentos; start:Documento<; end:Informação atualizada em; tag: td; index: 0; id: 1; content: 0,2,3,4; step: 5}");
			$this->net->addLayout($netLayout);

			// Carregamento de dados
			if ($html=='')
			{
				$this->net->siteOpen($url, $post, $cookie);
			} else
			{
				$this->net->html=$html;
			}

			$this->net->searchLayout();
			$this->net->parse($debug);

			$this->dataInicio=$this->net->getStartDate();
			$this->campos=$this->net->getFields();
			$this->andamentos=$this->net->getRecords();

			$html = $this->net->html;

			$doc = new DOMDocument();
			$doc->preservWhiteSpace = FALSE; //elimina espaços em branco
			$doc->formatOutput = FALSE;
			$doc->loadHTML($html);


			// 2a Instância
			if (stripos($html,'Ver Partes da 1a')!==false)
			{
				// Para os processos em 2a instância, busca reclamante e reclamado de outro lugar...
				$id='j_id13:j_id65:panelContentTable';
				if (stripos($html,$id)!==false)
					$tab = $doc->getElementById($id);
				else
				{
					$id='j_id13:j_id59:panelContentTable';
					$tab = $doc->getElementById($id);
				}

				$and = $tab->getElementsByTagName('tbody');
				$and = $and->item(0)->getElementsByTagName('td');
				for ($i = 0; $i < $and->length; $i++) {
					$item = strip_tags($and->item($i)->nodeValue);
					if ($and->item($i)->getAttribute('class')=='')
					{
						if ($item=='RECLAMANTE (s)')
						{
							$this->campos['autor']=htmlLimpaTxt($and->item($i+1)->nodeValue);
							$this->campos['autor_advogado']=htmlLimpaTxt($and->item($i+3)->nodeValue);
						}
						if ($item=='Reclamado (s)')
						{
							$this->campos['reu']=htmlLimpaTxt($and->item($i+1)->nodeValue);
							$this->campos['reu_advogado']=htmlLimpaTxt($and->item($i+3)->nodeValue);
						}
					}
				}
			}

			// Andamentos
			$and='';
			$this->andamentos='';

			$id='';
			$tbodys=$doc->getElementsByTagName('tbody');
			for ($i=0; $i<$tbodys->length; $i++)
			{
				$id=trim($tbodys->item($i)->getAttribute('id'));
				if ($id!='')
					$i=$tbodys->length;
			}
			if ($id!='')
			{
				$andam = $doc->getElementById($id);
				$andam = $andam->getElementsByTagName('tr');
				for ($i = 0; $i < $andam->length; $i++) {
					$tds = $andam->item($i)->getElementsByTagName('td');
					$grau = htmlLimpaTxt($tds->item(0)->nodeValue);
					$data = htmlFormataData(htmlLimpaTxt($tds->item(1)->nodeValue));
					$desc = trim(htmlLimpaTxt($tds->item(2)->nodeValue)."\n".htmlLimpaTxt($tds->item(3)->nodeValue));
					$and['grau']=$grau;
					$and['data']=$data;
					$and['descricao']=$desc;
					$this->andamentos[]=$and;
				}
				$this->dataInicio=$this->net->getStartDate();
			}


			// $and='';
			// $this->andamentos='';

			// $andam = $doc->getElementById('j_id152:j_id153:tb');
			// $andam = $andam->getElementsByTagName('tr');
			// for ($i = 0; $i < $andam->length; $i++) {
			// 	$tds = $andam->item($i)->getElementsByTagName('td');
			// 	$grau = htmlLimpaTxt($tds->item(0)->nodeValue);
			// 	$data = htmlFormataData(htmlLimpaTxt($tds->item(1)->nodeValue));
			// 	$desc = trim(htmlLimpaTxt($tds->item(2)->nodeValue)."\n".htmlLimpaTxt($tds->item(3)->nodeValue));
			// 	$and['grau']=$grau;
			// 	$and['data']=$data;
			// 	$and['descricao']=$desc;
			// 	$this->andamentos[]=$and;
			// }
			$this->dataInicio=$this->net->getStartDate();
		}
		return($ok);
	}

	function processaTRTSP($numero, $html)
	{
		// Variáveis
		$ok=false;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe
			if ($html=="")
				$ok=$this->processaPJeCaptcha("trtsp",$numero);
			else
				$ok=$this->processaPJe_html($numero,$html);
		} else
		{
			$raizAnexo='';
			$cookie=true;

			$post['ano']=substr($numero, 11, 4);
			$post['comarca']='001';
			$post['junta']=substr($numero, 22, 3);
			$post['processo']=intval(substr($numero, 0, 5));
			$post['tipo']=1;
			$erros=0;

			$url='';
			$url[0]="http://www.trtsp.jus.br/";
			$url[1]="http://aplicacoes5.trtsp.jus.br/consultasphp/public/index.php/primeirainstancia/primeira";

			$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
			$this->net->setRaizAnexo($raizAnexo);

			// Layouts para busca

			$netLayout=new gNetLayout("{name: ''}");
			$this->net->addLayout($netLayout);

			// Carregamento de dados

			if ($html=='')
			{
				$this->net->siteOpen($url, $post, $cookie);
			}
			else
			{
				$this->net->html=$html;
			}
	//echo $this->net->html;exit;
			if (stripos($this->net->html,"Captcha incorreto")!==false)
			{
				$this->lastError="Dígitos informados não conferem com a imagem apresentada.";
			} elseif (stripos($this->net->html, "Acompanhamento Processual em 1")!==false)
			{
				$ok=true;
				$this->net->html=substr($this->net->html, strpos($this->net->html, "<pre>"));
				$this->net->html=substr($this->net->html, 0, strpos($this->net->html, "</pre>"));
				$linhas=explode(chr(10), $this->net->html);

				$tipo='';
				$andamentos=false;
				$cnta=-1;
				$dataInicio="2399-01-01";
				$this->net->fields['numero']=$numero;
				for ($a=0; $a<count($linhas); $a++)
				{
					$linha=$linhas[$a];
					if (trim($linha)<>"")
					{
						//Autor       : RENATO SILVEIRA BEZERRA
						$p1=trim(substr($linha, 0, 12));
						$p2=trim(substr($linha, 14));
						$data=$this->net->htmlDateFormat($p1);
						if ($p1<>"")
							$tipo=$p1;
						if (substr($data, 0, 4)<>"20--")
							$tipo=$data;
						if (stripos($linha, "</pre>")!==false)
							$andamentos=false;
						//echo $a.") $tipo - ".$p2."<br>";
						if ($tipo=="Processo")
						{
							$this->net->fields['localizacao']=trim(substr($linhas[$a], 14));
							$this->net->fields['orgao_julgador_atual']=substr(trim(substr($linhas[($a+2)], 14)), 0, 9);
							$this->net->fields['descricao']=gUcwords(trim(substr($linhas[($a+6)], 14)));
							$tipo="x";
						}
						if ($tipo=="Autor")
						{
							$this->net->fields['autor']=gUcwords($p2);
							$tipo="x";
						}
						if ($tipo=="Advogado")
						{
							$this->net->fields['autor_advogado']=gUcwords($p2);
							$tipo="x";
						}
						if ($tipo=="Réu")
						{
							$this->net->fields['reu']=gUcwords($p2);
							$tipo="x";
						}
						if ($andamentos)
						{
							if ($dataAtual<>$tipo)
							{
								$cnta++;
							}
							if (!is_array($this->net->records[$cnta]))
							{
								$and['data']=$tipo;
								$and['descricao']=$p2;
								$this->net->records[$cnta]=$and;
							}
							else
							{
								$and['data']=$this->net->records[$cnta]['data'];
								$and['descricao']=$this->net->records[$cnta]['descricao'].chr(10).$p2;
								$this->net->records[$cnta]=$and;
							}
							if (($dataAtual<$dataInicio)&&($dataAtual<>''))
								$dataInicio=$dataAtual;

							$dataAtual=$tipo;
						}
						if ($tipo=="Data(s)")
							$andamentos=true;
					}
				}
				$this->net->startDate=$dataInicio;
			}
			$this->dataInicio=$this->net->getStartDate();
			$this->campos=$this->net->getFields();
			$this->andamentos=$this->net->getRecords();
		}
		return($ok);
	}













	function processaTJBA($numero, $html)
	{
		// Variáveis
		$ok=false;
		$raizAnexo='';
		$cookie=true;

		$erros=0;

		if ($html<>"")
		{
			$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
			$this->net->setRaizAnexo($raizAnexo);
			// Removendo scripts
			$faz=true;
			while ($faz)
			{
				$p1=strpos($html, '<script');
				if ($p1!==false)
				{
					$p2=strpos($html, '</script>' , $p1);
					if ($p2>0)
						$html=substr($html, 0, $p1) . substr($html,$p2+9);
				} else
				{
					$faz=false;
				}
			}
			$html=autoencode($html);
			$html=strip_tags($html);
			$linhasSemTratar=explode("\n",$html);
			$this->net->fields['numero']=$numero;
			$this->net->fields['titulo_autor']='Promovente';
			$this->net->fields['titulo_reu']='Promovido';
			$promovente=false;
			$andamentos=0;
			for ($a=0; $a<count($linhasSemTratar); $a++)
			{
				$lin=trim($linhasSemTratar[$a]);
				if ($lin<>"")
				{
					$linhas[]=$lin;
				}
			}
			for ($a=0; $a<count($linhas); $a++)
			{
				$lin=trim($linhas[$a]);
				//echo '> '.$lin."\n";
				switch ($lin)
				{
					case 'Promovente':
						$this->net->fields['autor']=($linhas[$a+6]);
						$promovente=true;
						break;

					case 'Promovido':
						$this->net->fields['reu']=($linhas[$a+6]);
						$promovente=false;
						break;

					case 'Advogado':
						if ($promovente)
						{
							$this->net->fields['autor_advogado'].=($linhas[$a+3]." - ".$linhas[$a+4]);
							if ((($linhas[$a+5])<>'Endereço') && (($linhas[$a+5])<>'Promovido') && (($linhas[$a+5])<>'Endere&ccedil;o'))
								$this->net->fields['autor_advogado'].="\n".($linhas[$a+5]." - ".$linhas[$a+6]);
						} else
						{
							$this->net->fields['reu_advogado'].=($linhas[$a+3]." - ".$linhas[$a+4]);
							if ((($linhas[$a+5])<>'Endereço') && (($linhas[$a+5])<>'Testemunha')  && (($linhas[$a+5])<>'Endere&ccedil;o'))
								$this->net->fields['reu_advogado'].="\n".($linhas[$a+5]." - ".$linhas[$a+6]);
						}
						break;
					case 'Assunto:':
						$this->net->fields['descricao']=($linhas[$a+1]);
						break;
					case 'Ju&iacute;zo:':
						$this->net->fields['justica_origem']=($linhas[$a+1]);
						break;
					case 'Valor da Causa:':
						$this->net->fields['observacoes'].="Valor da causa:<br>".($linhas[$a+1]);
						break;
					case 'Eventos do Processo':
						$andamentos=$a+4;
						break;
				}
			}
			$baseAnexo="https://projudi.tjba.jus.br/projudi/";
			$this->net->records='';
			if ($andamentos>0)
			{
				$a=$andamentos;
				$rep=0;
				$faz=true;
				while ($faz)
				{
					$data=$linhas[$a];
					if ((strlen($data)==8) && (substr($data,2,1)=='/') && (substr($data,5,1)=='/'))
					{
						$descr=$linhas[$a-1];

						$rec='';
						$rec['data']=htmlFormataData($data);
						$rec['descricao']=$descr;
						$this->net->records[]=$rec;
					}
					$a++;
					if ($a>count($linhas))
						$faz=false;
				}

			}
			$ok=true;
			$this->dataInicio=$this->net->getStartDate();
			$this->campos=$this->net->getFields();
			$this->andamentos=$this->net->getRecords();
		}


		return($ok);
	}








	function processaTRTBA($numero, $html)
	{

		// Variáveis
		$ok=true;


		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe
			if ($html=="")
				$ok=$this->processaPJeNovo("trt5",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{

			$raizAnexo='';
			$cookie=false;
			$post['_ano_proc_cnj']=substr($numero, 11, 4);
			$post['_cod_justica_cnj']='5';
			$post['_cod_vara_cnj']=substr($numero, 21, 4);
			$post['_dig_verif_cnj']=substr($numero, 8, 2);
			$post['_processo_antigo']='1';
			$post['_recursos']='sim';
			$post['_regiao_cnj']=substr($numero, 18, 2);
			$post['_seq_proc_cnj']=substr($numero, 0, 7);
			$url="http://www.trt5.jus.br/consultaprocessos/modelo/consultaProcesso8.asp?";
/*
			$post='';
			$post['pagina']='consultaDeProcesso';
			$post['_seq_cnj']=substr($numero, 0, 7);
			$post['_dig_cnj']=substr($numero, 8, 2);
			$post['_ano_cnj']=substr($numero, 11, 4);
			$post['_justica_cnj']='5';
			$post['_regiao_cnj']=substr($numero, 18, 2);
			$post['_vara_cnj']=substr($numero, 21, 4);
			$post['tipo_numero']='1';
			$url="http://www.trt5.jus.br/default.asp?";
*/
			/*
			pagina=consultaDeProcesso&
			_seq_cnj=0000388&
			_dig_cnj=43&
			_ano_cnj=2014&
			_justica_cnj=5&
			_regiao_cnj=05&
			_vara_cnj=0196&tipo_numero=1";
			*/


			foreach ($post as $key=>$value)
				$url.=$key."=".$value."&";
			$post='';

			$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
			$this->net->setRaizAnexo($raizAnexo);

			// Layouts para busca

			$netLayout=new gNetLayout("{name: ''}");
			$this->net->addLayout($netLayout);

			// Carregamento de dados

			if ($html=='')
			{
				$this->net->siteOpen($url, $post, $cookie);
			}
			else
			{
				$this->net->html=$html;
			}
//echo "\n\n\n\n\n".$this->net->html."\n\n\n\n\n\n";exit;

			$jHtml=json_decode(autoencode($this->net->html), true);

			// Cabecalho
			$this->net->fields['numero']=trim(substr($jHtml['processo']['numeracao_unica'], 0, 25));
			if ($this->net->fields['numero']<>'')
			{
				$this->net->fields['autor']=trim($jHtml['processo']['reclamante']);
				$this->net->fields['autor_advogado']=trim($jHtml['processo']['advreclamante']);
				$this->net->fields['reu']=trim($jHtml['processo']['reclamado']);
				$this->net->fields['reu_advogado']=trim($jHtml['processo']['advreclamado']);
				$this->net->fields['descricao']=gUcWords(trim($jHtml['processo']['desc_tipo_proc']));
				$this->net->fields['orgao_julgador_atual']=gUcWords(trim($jHtml['processo']['nome_vara']));
				$this->net->fields['localizacao']=gUcWords(trim($jHtml['processo']['nome_vara']));
				$this->net->fields['justica_origem']="Trabalhista";

				if ($jHtml['processo']['desc_tipo_parte1']=="Recorrente")
				{
					$tmp=$this->net->fields;
					$this->net->fields['autor']=$this->net->fields['reu'];
					$this->net->fields['autor_advogado']=$this->net->fields['reu_advogado'];
					$this->net->fields['reu']=$tmp['autor'];
					$this->net->fields['reu_advogado']=$tmp['autor_advogado'];

					$a=$this->net->fields['autor'];
					if (strtolower(substr($a,-3))==' sa' || strtolower(substr($a,-5))==' ltda' ||
						strtolower(substr($a,-5))==' s.a.' || strtolower(substr($a,-6))==' ltda.' || (stripos($a,'ltda')!==false))
					{
						$tmp=$this->net->fields;
						$this->net->fields['autor']=$this->net->fields['reu'];
						$this->net->fields['autor_advogado']=$this->net->fields['reu_advogado'];
						$this->net->fields['reu']=$tmp['autor'];
						$this->net->fields['reu_advogado']=$tmp['autor_advogado'];
					}
				}
				// Corrigindo formato dos advogados
				$adv=explode(";", $this->net->fields['autor_advogado']);
				$nadv='';
				foreach ($adv as $ad)
				{
					$ad=trim($ad);
					if (substr($ad, 6, 1)=="-")
					{
						$newAdv=substr($ad, 10);
						$oab=substr($ad, 7, 2).substr($ad, 0, 6);
						$nadv.=$newAdv." - ".$oab.chr(10);
					} else
						$nadv.=$ad.chr(10);
				}
				$nadv=substr($nadv, 0, strlen($nadv)-1);
				$this->net->fields['autor_advogado']=$nadv;
				$adv=explode(";", $this->net->fields['reu_advogado']);
				$nadv='';
				foreach ($adv as $ad)
				{
					$ad=trim($ad);
					if (substr($ad, 6, 1)=="-")
					{
						$newAdv=substr($ad, 10);
						$oab=substr($ad, 7, 2).substr($ad, 0, 6);
						$nadv.=$newAdv." - ".$oab.chr(10);
					} else
						$nadv.=$ad.chr(10);
				}
				$nadv=substr($nadv, 0, strlen($nadv)-1);
				$this->net->fields['reu_advogado']=$nadv;
				$cnta=0;

				foreach ($jHtml['tramitacoes'] as $tramit)
				{
					$and['data']=htmlFormataData(substr($tramit['data_hora_trami'], 0, 10));
					$and['descricao']=gUcWords($tramit['texto_trami']);
					$and['codigo']=gUcWords($tramit['cod_trami']);
					//$and['arquivo']="http://www.trt5.jus.br/consultaprocessos/modelo/consulta_documento_blob.asp?v_id=";
					$this->net->records[$cnta]=$and;
					$cnta++;
				}
				foreach ($jHtml['documentos'] as $tramit)
				{
					$cod=$tramit['cod_trami'];
					if ($tramit['extensao_arquivo']=="PDF")
					{
						foreach ($this->net->records as $id=>$and)
						{
							if ($and['codigo']==$cod)
							{
								$and['anexo']="http://www.trt5.jus.br/consultaprocessos/modelo/consulta_documento_blob.asp?v_id=".$tramit['rowid'];
								$this->net->records[$id]=$and;
							}
						}
					}
				}
				//data_aud
				if (trim($jHtml['processo']['data_aud'])<>"")
				{
					$and="";
					$and['data']=htmlFormataData(trim($jHtml['processo']['data_aud']))." ".trim($jHtml['processo']['hora_aud']);
					$and['descricao']="Audiência marcada para ".trim($jHtml['processo']['data_aud'])." às ".trim($jHtml['processo']['hora_aud']);
					$this->net->records[$cnta]=$and;
				}
			}

			$this->menorData($this->net->records);
			$this->campos=$this->net->getFields();
			$this->andamentos=$this->net->getRecords();
		}
		return($ok);
	}

	function processaTRTCE($numero, $html)
	{

		// Variáveis
		$ok=true;
		/*
		if (ehPje($numero))
		{
			// PJe
			if ($html=="")
				$ok=$this->processaPJeAntigo("trt7",$numero);
			else
				$ok=$this->processaPJeAntigo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		*/

		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt7",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}

		return($ok);
	}

	function processaTRTPE($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt6",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		return($ok);
	}

	function processaTRTPA($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";

		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt8",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		return($ok);
	}

	function processaTRTES($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";

		if (ehPje($numero))
		{
			// PJe
			if ($html=="")
				$ok=$this->processaPJeNovo("trtes",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		return($ok);
	}

	function processaTRTAL($numero, $html)
	{

		// Variáveis
		$ok=true;

		/*
		if (ehPje($numero))
		{
			// PJe
			if ($html=="")
				$ok=$this->processaPJeAntigo("trt19",$numero);
			else
				$ok=$this->processaPJeAntigo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		*/
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt19",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}

		return($ok);
	}

	function processaTRTRN($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt21",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}

		return($ok);
	}


	function processaTRTRS($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeCaptcha("trt4",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
//echo "\n\n\n\n\n\n\n".$html;
			$html=utf8_decode($html);
			if (strpos($html,"Dados do processo")!==false)
			{
				$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
				$this->net->setRaizAnexo($raizAnexo);

				// Cabecalho
				$this->net->fields['numero']=$numero;

				$doc = new DOMDocument();
				$doc->preservWhiteSpace = FALSE; //elimina espaços em branco
				$doc->formatOutput = FALSE;
				$doc->loadHTML($html);

				$cab = $doc->getElementById('cabecalho_resultado')->getElementsByTagName('tr');
				for ($i = 0; $i < $cab->length; $i++) {
					$item = $cab->item($i);
					$th=trim($item->getElementsByTagName('th')->item(0)->nodeValue);
					$td=trim($item->getElementsByTagName('td')->item(0)->nodeValue);
					if ($th=="Classe")
						$this->net->fields['descricao']=$td;
					if ($th=="Vara do Trabalho")
						$this->net->fields['justica_origem']=$td;
					if ($th=="Reclamante Principal")
						$this->net->fields['autor']=$td;
					if ($th=="Procurador Rte. Princ.")
					{
						$tda=explode(" - ",$td);
						$this->net->fields['autor_advogado']=$tda[1]." (".$tda[0].")";
					}
					if ($th=="Reclamada Principal")
						$this->net->fields['reu']=$td;
					if ($th=="Procurador Rda. Princ.")
					{
						$tda=explode(" - ",$td);
						$this->net->fields['reu_advogado']=$tda[1]." (".$tda[0].")";
					}
				}

				$and = $doc->getElementById('corpo_resultado')->getElementsByTagName('tr');
				for ($i = 0; $i < $and->length; $i++) {
					$item = $and->item($i);
					$data=trim($item->getElementsByTagName('th')->item(0)->nodeValue);
					$th=trim($item->getElementsByTagName('th')->item(1)->nodeValue);
					$td=trim($item->getElementsByTagName('td')->item(0)->nodeValue);
					$desc=gUcWords(str_replace('.',' ',$td));
					if (strpos($data,'/')!==false)
					{
						$rec='';
						$rec['data']=htmlFormataData($data);
						$rec['descricao']=$desc;
						$this->net->records[]=$rec;
					} else
					{
						$last=count($this->net->records)-1;
						$this->net->records[$last]['descricao'].="<br>".$desc;
					}
				}
//				gD($this->net->fields); gD($this->net->records);exit;


				$this->menorData($this->net->records);
				$this->campos=$this->net->getFields();
				$this->andamentos=$this->net->getRecords();

			} else
			{
				$ok=false;
			}
		}

		return($ok);
	}

	function processaTRTMS($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt24",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			if (strpos($html,"PROCESSO N&deg;: ".$numero)!==false)
			{
				$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
				$this->net->setRaizAnexo($raizAnexo);

				// Diminuindo o HTML (excluindo o desnecessário)
				$html=substr($html,strpos($html,"CONSULTA PELO"));
				$html=substr($html,0,strpos($html,'id="footer"'));

				// Cabecalho
				$this->net->fields['numero']=$numero;
				$this->net->fields['justica_origem']=getPos($html, '<b>Origem:</b>', '</td>');
				$this->net->fields['descricao']=getPos($html, '<b>Classe:</b>', '</td>');
				$this->net->fields['autor']=getPos($html, '<b>Reclamante</b>', '</td>', 1);
				$this->net->fields['autor_advogado']=getPos($html, 'Advogado</b>', '</td>', 1);

				$html=substr($html,strpos($html, '<b>Reclamada</b>'));
				$this->net->fields['reu']=getPos($html, '<b>Reclamada</b>', '</td>', 1);
				$this->net->fields['reu_advogado']=getPos($html, 'Advogado</b>', '</td>', 1);

				$html2=substr($html,0,strpos($html,'ltimo andamento:'));
				// Falta processar TODOS os reús e advogados

				$html=substr($html,strpos($html,'<b>ANDAMENTOS</b>'));
				$html=substr($html,strpos($html,'<table>'));
				$html=str_replace('</table><span id="frameAndamento" style="display:none"><table>   ','', $html);
				$html=substr($html,0,strpos($html,'</span>'));
				$html=str_replace('</span>','', $html);
				$html="<html>\n".$html."\n</html>\n";

				$doc = new DOMDocument();
				$doc->preservWhiteSpace = FALSE; //elimina espaços em branco
				$doc->formatOutput = FALSE;
				$doc->loadHTML($html);
				$and = $doc->getElementsByTagName('tr');
				for ($i = 0; $i < $and->length; $i++) {
					$item = $and->item($i);
					$data=$item->getElementsByTagName('td')->item(0)->nodeValue;
					$desc=gUcWords(str_replace('.',' ',$item->getElementsByTagName('td')->item(1)->nodeValue));
					if (strpos($data,'/')!==false)
					{
						$rec='';
						$rec['data']=htmlFormataData($data);
						$rec['descricao']=$desc;
						$this->net->records[]=$rec;
					}
				}

				//gD($this->net->fields); gD($this->net->records);
				//echo "\n\n\n\n\n\n\n".$html;

				$this->menorData($this->net->records);
				$this->campos=$this->net->getFields();
				$this->andamentos=$this->net->getRecords();

			} else
			{
				$ok=false;
			}
		}

		return($ok);
	}


	function processaTRTPI($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="http";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt22",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}

		return($ok);
	}

	function processaTRTSE($numero, $html)
	{

		// Variáveis
		$ok=true;
		/*
		if (ehPje($numero))
		{
			// PJe
			if ($html=="")
				$ok=$this->processaPJeAntigo("trt20",$numero);
			else
				$ok=$this->processaPJeAntigo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		*/
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt20",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		return($ok);
	}

	function processaTRTMA($numero, $html)
	{

		// Variáveis
		$ok=true;

		/*
		if (ehPje($numero))
		{
			// PJe
			if ($html=="")
				$ok=$this->processaPJeAntigo("trt16",$numero);
			else
				$ok=$this->processaPJeAntigo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		*/
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt16",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}

		return($ok);
	}

	function processaTRTDF($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe
			if ($html=="")
				$ok=$this->processaPJeNovo("trt10",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$this->net=new gJuridico("{clean: true}");

			$raiz='http://www.trt10.jus.br';
//echo "TRT DF\n\n\n\n$html";

			$autor =  getPos($html,'Reclamante:','</td>');
			$autor_advogado =  getPos($html,'Reclamante:','</td>',1);
			$autor_advogado = trim(substr($autor_advogado,strpos($autor_advogado,'Advogado:')+9));
			$reu =  getPos($html,'Reclamado:','</td>');
			$reu_advogado =  getPos($html,'Reclamado:','</td>',1);
			$reu_advogado = trim(substr($reu_advogado,strpos($reu_advogado,'Advogado:')+9));

			$this->net->fields['numero']=$numero;
			$this->net->fields['autor']=$autor;
			$this->net->fields['autor_advogado']=$autor_advogado;

			$this->net->fields['reu']=$reu;
			$this->net->fields['reu_advogado']=$reu_advogado;
			// Andamentos

			if (strpos($html,'<h3>Andamentos</h3>')!==false)
			{
				$htmlAnd=substr($html,strpos($html,'<h3>Andamentos</h3>'));
				$htmlAnd=substr($htmlAnd,0,strpos($htmlAnd,'</table>')+8);
				$htmlAnd='<html>'.$htmlAnd.'</html>';

				$pag = new DOMDocument();
				$pag->preservWhiteSpace = FALSE; //elimina espaços em branco
				$pag->formatOutput = FALSE;

				$pag->loadHTML($htmlAnd);
				$andam = $pag->getElementsByTagName('tr');
				for ($j = 1; $j < $andam->length-1; $j++)
				{
					$este =  $andam->item($j)->getElementsByTagName('td');
					$data =  str_replace('&nbsp;','',strip_tags($este->item(0)->nodeValue));
					$desc =  trim(strip_tags($este->item(2)->nodeValue));
					$anexo = $este->item(1)->nodeValue;
					$anexo = $raiz.substr($anexo,strpos($anexo,"'")+1);
					$anexo = substr($anexo,0,strpos($anexo,"'"));
					$rec='';
					$rec['data']=htmlFormataData($data);
					$rec['descricao']=$desc;
					//$rec['anexo']=$anexo;
					$this->net->records[]=$rec;
				}
			}
//gD($this->net->fields);gD($this->net->records);exit;

			$this->menorData($this->net->records);
			$this->campos=$this->net->getFields();
			$this->andamentos=$this->net->getRecords();
		}
		return($ok);
	}

	function processaTRTPB($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt13",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		return($ok);
	}

	function processaTRTROeAC($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt14",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		return($ok);
	}

	function processaTRTPR($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe
			if ($html=="")
				$ok=$this->processaPJeCaptcha("trt9",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=true;
			$raizAnexo='';
			$url[0]="http://www.trt9.jus.br/internet_base/inicial.do?evento=cookie";
			$url[1]="http://www.trt9.jus.br/internet_base/processocnjsel.do";

			$post='';
			$post["unicoCnjNumero_Arg"]=substr($numero, 0, 7);
			$post["unicoCnjDigito_Arg"]=(substr($numero, 8, 2));
			$post["unicoCnjAno_Arg"]=(substr($numero, 11, 4));
			$post["unicoCnjJustica_Arg"]=substr($numero, 16, 1);
			$post["unicoCnjTribunal_Arg"]=substr($numero, 18, 2);
			$post["unicoCnjOrigem_Arg"]=substr($numero, 21, 4);
			$post["evento"]="F9-Pesquisar";
			$post["indExcDetPlc"]="";
			$post["lookupCorrentePlc"]="";
			$post["modoPlc"]="consultaPlc";
			$post["ordenacaoPlc"]="";

			$this->net=new gJuridico("{clean: true}");
			$this->net->siteOpen($url, $post, true);
			$html=$this->net->html;


			$pag = new DOMDocument();
			$pag->preservWhiteSpace = FALSE; //elimina espaços em branco
			$pag->formatOutput = FALSE;
			$pag->loadHTML($html);
			// Buscando Autor e Réu somente se for o processo da primeira instância (pra não confundir)
			if (stripos($html,"SOBRE O PROCESSO")!==false)
			{
				$post='';
				$header="";
				$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
				$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
				$header[]="Cache-Control: no-cache";
				$header[]="Connection: keep-alive";
				//$header[]="Referer: ".$referer;
				//$header[]="Host: 	sistemas.trt18.jus.br";

				$items = $pag->getElementById('processo')->getElementsByTagName('div');

				for ($j = 0; $j < $items->length; $j++)
				{
					$linha = $items->item($j)->nodeValue;

					if (strpos($linha,':')!==false)
					{
						$el = explode(':',$linha);
						$campo=htmlLimpaTxt($el[0]);
						$valor=htmlLimpaTxt($el[1]);

						switch ($campo)
						{
							case 'AUTOR':
								$autor = str_ireplace('exibir Advogados','',$valor);
								// Busca advogados

								$par=$items->item($j)->getElementsByTagName('a')->item(0)->getAttribute('onclick');
								$par = str_replace("janela('",'',$par);
								$par = str_replace("')",'',$par);
								$url="http://www.trt9.jus.br/internet_base/".$par;
								$htmlAdv=$this->processaUrl($url, $header, '', $numero);

								$adv = new DOMDocument();
								$adv->preservWhiteSpace = FALSE; //elimina espaços em branco
								$adv->formatOutput = FALSE;
								$adv->loadHTML($htmlAdv);

								$tabelas=$adv->getElementsByTagName('table');
								$fez=false;
								for ($g=0; $g < $tabelas->length; $g++)
								{
									if ($tabelas->item($g)->getAttribute('class')=="delimitador tabelaFormulario")
									{
										$pessoa=$tabelas->item($g)->getElementsByTagName('td');
										if (!$fez)
										{
											// Autor
											$autor_advogado=htmlLimpaTxt($pessoa->item(3)->nodeValue);
										} else
										{
											// Réu
											$reu_advogado=htmlLimpaTxt($pessoa->item(3)->nodeValue);
										}
										$fez=true;
									}
								}
								break;

							case 'RÉU':
								$reu = str_ireplace('exibir Advogados','',$valor);
								break;

							case 'Origem':
								$origem = gUcwords($valor);
								break;
						}
					}
				}

				// Andamentos
gLog("---- Andamentos ----");
				$par=$pag->getElementsByTagName('a');
				$fez=false;
				for ($j=0; $j<$par->length; $j++)
				{
					$href=$par->item($j)->getAttribute('href');
					if (strpos($href,'exibeHistoricosAntigos=S')!==false && !$fez)
					{
						$fez=true;
						$url="http://www.trt9.jus.br".$href;
						$htmlAnd=$this->processaUrl($url, $header, '', $numero);

						$and = new DOMDocument();
						$and->preservWhiteSpace = FALSE; //elimina espaços em branco
						$and->formatOutput = FALSE;
						$and->loadHTML($htmlAnd);

						$tabelas=$and->getElementsByTagName('table');
						for ($g=0; $g < $tabelas->length; $g++)
						{
							if ($tabelas->item($g)->getAttribute('class')=="tab_historico")
							{
								$linhas = $tabelas->item($g)->getElementsByTagName('td');
								for ($h=0; $h < $linhas->length; $h++)
								{
									if ($linhas->item($h)->getAttribute('class')=="data_historico")
									{
										$data = htmlLimpaTxt($linhas->item($h)->nodeValue);
										$desc = htmlLimpaTxt($linhas->item($h+1)->nodeValue);
										$rec='';
										$rec['grau']=1;
										$rec['data']=htmlFormataData($data);
										$rec['descricao']=$desc;
										$this->net->records[]=$rec;
									}
								}
							}
						}
					}
				}

				$this->net->fields['numero']=$numero;
				$this->net->fields['justica_origem']=$origem;

				$this->net->fields['autor']=$autor;
				$this->net->fields['autor_advogado']=$autor_advogado;

				$this->net->fields['reu']=$reu;
				$this->net->fields['reu_advogado']=$reu_advogado;

				$this->menorData($this->net->records);
				$this->campos=$this->net->getFields();
				$this->andamentos=$this->net->getRecords();

			} else
			{
				$ok=false;
			}
			/*
			// Andamentos 1º grau
			if (strpos($html,'consultaProcessos:tb')!==false)
			{
				$andam = $pag->getElementById('consultaProcessos:tb')->getElementsByTagName('tr');
				for ($j = 0; $j < $andam->length; $j++)
				{
					$este =  $andam->item($j)->getElementsByTagName('td');
					$data =  $este->item(0)->nodeValue;
					$desc =  trim(strip_tags($este->item(1)->nodeValue));
					$rec='';
					$rec['grau']=1;
					$rec['data']=htmlFormataData($data);
					$rec['descricao']=$desc;
					$this->net->records[]=$rec;
				}
			}
			*/
		}
		return($ok);
	}

	function processaTRTGO($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe
			if ($html=="")
				$ok=$this->processaPJeCaptcha("trt18",$numero);
			else
				$ok=$this->processaPJe_html($numero,$html);
		} else
		{
			$ok=true;
			$raizAnexo='';
			$url[0]="http://www.trt18.jus.br/";
			$url[1]="http://sistemas.trt18.jus.br/consultasPortal/pages/Processuais/ListaProcessos.seam";
			$post='';
			$post["p_num_cnj"]=substr($numero, 0, 7);
			$post["p_dig_cnj"]=(substr($numero, 8, 2));
			$post["p_ano_cnj"]=(substr($numero, 11, 4));
			$post["x"]="21";
			$post["y"]="11";

			$this->net=new gJuridico("{clean: true}");
			$this->net->siteOpen($url, $post, true);
			$html=$this->net->html;

			// Busca links para as consultas de processo (1ª e 2ª instancia)
			$doc = new DOMDocument();
			$doc->preservWhiteSpace = FALSE; //elimina espaços em branco
			$doc->formatOutput = FALSE;
			$doc->loadHTML($html);
			$and = $doc->getElementsByTagName('a');

			for ($i = 0; $i < $and->length; $i++)
			{
				$item = $and->item($i);
				$href = trim($item->getAttribute("href"));

				// Processa somente os links para as consultas de processos
				if (strpos($href,"DetalhaProcesso")!==false)
				{
					$url="http://sistemas.trt18.jus.br".$href;

					$post='';

					$header="";
					$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
					$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
					$header[]="Cache-Control: no-cache";
					$header[]="Connection: keep-alive";
					//$header[]="Referer: ".$referer;
					$header[]="Host: 	sistemas.trt18.jus.br";

					$html=$this->processaUrl($url, $header, $post, $numero);

					$pag = new DOMDocument();
					$pag->preservWhiteSpace = FALSE; //elimina espaços em branco
					$pag->formatOutput = FALSE;
					$pag->loadHTML($html);
					$pag->loadHTML($html);

					// Buscando Autor e Réu somente se for o processo da primeira instância (pra não confundir)
					if ((stripos($html,"AUTOR:")!==false) || (stripos($html,"Reclamante(s)")!==false))
					{

						$item = $pag->getElementById('partesReclamanteFieldDecorate');
						$autor= $item->getElementsByTagName('select')->item(0)->nodeValue;
						$autor= strip_tags(str_replace('</option>',"\n</option>",$autor));

						$item = $pag->getElementById('advPartesReclamanteDecorate');
						$autor_advogado= $item->getElementsByTagName('select')->item(0)->nodeValue;
						$autor_advogado= strip_tags(str_replace('</option>',"\n</option>",$autor_advogado));

						$item = $pag->getElementById('partesReclamadaFieldDecorate');
						$reu= $item->getElementsByTagName('select')->item(0)->nodeValue;
						$reu= strip_tags(str_replace('</option>',"\n</option>",$reu));

						$item = $pag->getElementById('advPartesReclamadaDecorate');
						$reu_advogado= $item->getElementsByTagName('select')->item(0)->nodeValue;
						$reu_advogado= strip_tags(str_replace('</option>',"\n</option>",$reu_advogado));

						$this->net->fields['numero']=$numero;
						$this->net->fields['autor']=$autor;
						$this->net->fields['autor_advogado']=$autor_advogado;

						$this->net->fields['reu']=$reu;
						$this->net->fields['reu_advogado']=$reu_advogado;
					}

					// Andamentos 1º grau
					if (strpos($html,'consultaProcessos:tb')!==false)
					{
						$andam = $pag->getElementById('consultaProcessos:tb')->getElementsByTagName('tr');
						for ($j = 0; $j < $andam->length; $j++)
						{
							$este =  $andam->item($j)->getElementsByTagName('td');
							$data =  $este->item(0)->nodeValue;
							$desc =  trim(strip_tags($este->item(1)->nodeValue));
							$rec='';
							$rec['grau']=1;
							$rec['data']=htmlFormataData($data);
							$rec['descricao']=$desc;
							$this->net->records[]=$rec;
						}
					}
					// Andamentos 2º grau
					if (strpos($html,'consultaProcessos2g:tb')!==false)
					{
						$andam = $pag->getElementById('consultaProcessos2g:tb')->getElementsByTagName('tr');
						for ($j = 0; $j < $andam->length; $j++)
						{
							$este =  $andam->item($j)->getElementsByTagName('td');
							$data =  $este->item(0)->nodeValue;
							$desc =  trim(strip_tags($este->item(1)->nodeValue));
							$rec='';
							$rec['grau']=2;
							$rec['data']=htmlFormataData($data);
							$rec['descricao']=$desc;
							$this->net->records[]=$rec;
						}
					}

				}
			}

			$this->menorData($this->net->records);
			$this->campos=$this->net->getFields();
			$this->andamentos=$this->net->getRecords();

		}
		return($ok);
	}

	function processaTRTCampSP($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt15",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		return($ok);
	}

	function processaTRTMT($numero, $html)
	{

		// Variáveis
		$ok=true;
		$this->PJeHttp="https";
		if (ehPje($numero))
		{
			// PJe

			if ($html=="")
				$ok=$this->processaPJeNovo("trt23",$numero);
			else
				$ok=$this->processaPJeNovo_html($numero,$html);
		} else
		{
			$ok=false;
		}
		return($ok);
	}

	function processaTST($numero, $html)
	{
			$ok=true;
			$raizAnexo='';

			$cookie=true;
			$post["numeroTst"]=substr($numero, 0, 7);
			$post["digitoTst"]=(substr($numero, 8, 2));
			$post["anoTst"]=(substr($numero, 11, 4));
			$post["orgaoTst"]=(substr($numero, 16, 1));
			$post["tribunalTst"]=(substr($numero, 18, 2));
			$post["varaTst"]=(substr($numero, 21, 4));
			$post["conscsjt"]="";
			$post["consulta"]="Consultar";

			$url='';
			$url[0]='http://www.tst.jus.br';
			$url[1]='http://aplicacao5.tst.jus.br/consultaProcessual/consultaTstNumUnica.do?';
			foreach ($post as $key=>$value)
				$url[1].=$key."=".$value."&";
			$url[1]=substr($url[1], 0, strlen($url[1])-1);

			$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
			$this->net->setRaizAnexo($raizAnexo);
			$this->net->siteOpen($url, $post, true);
			$html=$this->net->html;

			if(strpos($html, "Este processo n")===false)
			{
				// Cabecalho
				$this->net->fields['numero']=$numero;

				// Verifica se o processo passou por algum tribunal, se passou, não atualiza autor e réu
				$rsa=gQuery("SELECT a.* FROM processos p
									LEFT JOIN processos_andamentos a on p.id=a.id_processos
									WHERE a.id_orgaos<>3 AND p.numero='".$numero."'");
				if ($rsa->EOF)
				{
					$this->net->fields['reu']=getPos($html, '<b>Agravante(s): </b>', '</font>',0);
					$this->net->fields['reu_advogado']=getPos($html, '<td><b>Advogado: </b>', '</font>', 0);

					$this->net->fields['autor']=getPos($html, '<b>Agravado(s): </b>', '</font>', 0);
					$this->net->fields['autor_advogado']=getPos($html, '<b>Advogado: </b>', '</font>', 0);
				}

				//Movimentação
				$html= preg_replace('/\s/',' ',$html); // Tirando quebras de linhas
				$html=substr($html,strpos($html,'Acompanhamento Processual</th>'));
				$html=substr($html,strpos($html,'</tr>')+5);
				$html=str_replace("</tr> </table>",'', $html);
				$html=str_replace("</table>",'', $html);
				$html=str_replace('<table width="100%"> <tr>','', $html);
				$html=str_replace('<table width="100%">','', $html);
				$ind = strpos($html,"<!-- Retirada linha em");
				$html= substr($html,0,$ind);

				$doc = new DOMDocument();
				$doc->preservWhiteSpace = FALSE; //elimina espaços em branco
				$doc->formatOutput = FALSE;
				$doc->loadHTML($html);
				$and = $doc->getElementsByTagName('tr');

				for ($i = 0; $i < $and->length; $i++)
				{
					$item = $and->item($i);
					$i++;
					$item2 = $and->item($i);
					$data=$item->getElementsByTagName('td')->item(0)->nodeValue;
					$desc=autoencode(gUcWords(str_replace('.',' ',$item2->getElementsByTagName('td')->item(0)->nodeValue)));

					$rec='';
					$rec['data']=htmlFormataData($data);
					$rec['descricao']=$desc;
					$this->net->records[]=$rec;
				}

				$this->menorData($this->net->records);
				$this->campos=$this->net->getFields();
				$this->andamentos=$this->net->getRecords();
			}
			else
				$ok=false;






/*

		$ok=true;
		$raizAnexo='';

		$cookie=true;
		$post["num_proc"]=intval(substr($numero, 0, 7));
		$post["dig_proc"]=(substr($numero, 8, 2));
		$post["ano_proc"]=(substr($numero, 11, 4));
		$post["num_orgao"]=(substr($numero, 16, 1));
		$post["TRT_proc"]=(substr($numero, 18, 2));
		$post["vara_proc"]=(substr($numero, 21, 4));
		$post["novoportal"]=1;

		$url='';
		$url[0]='http://www.tst.gov.br';
		$url[1]='http://ext02.tst.jus.br/pls/ap01/ap_proc100.dados_processos?';
		foreach ($post as $key=>$value)
			$url[1].=$key."=".$value."&";
		$url[1]=substr($url[1], 0, strlen($url[1])-1);

		$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
		$this->net->setRaizAnexo($raizAnexo);

		// Layouts para busca

		$netLayout=new gNetLayout("{name: 'Pesquisa Processual'}");
		$netLayout->add("{type: text; name: numero; start:LINHA DE CABECALHO; end:TABELA DE ANDAMENTO; tag: td; index: 3}");
		//$netLayout->add("{type: text; name: autor_advogado; start:Recorrente(s); end:Recorrido(s); tag: td; index: 3,5,7,9}");
		//$netLayout->add("{type: text; name: reu_advogado; start:Recorrido(s); end:FIM DA LINHA DE PARTES E ADVOGADOS; tag: td; index: 3,5,7,9}");
		$netLayout->add("{type: text; name: orgao_julgador_atual; start:Órgão Judicante; end:FIM DA LINHA DE CABECALHO; tag: span; index: 0}");
		$netLayout->add("{type: text; name: relator; start:Órgão Judicante; end:FIM DA LINHA DE CABECALHO; tag: span; index: 1}");

		$netLayout->add("{type: array; name: andamentos; start:>Andamento do processo; end:/body; tag: td; delimiter: <!--LINHA DE ANDAMENTO DO PROCESSO-->; index:0; id: 0; content: 2,3,4,5,6,7; attached: a}");
		$this->net->addLayout($netLayout);

		// Carregamento de dados

		if ($html=='')
		{
			$this->net->siteOpen($url, $post, $cookie);
			$this->net->searchLayout();
			$this->net->parse($debug);
			$erros=0;
		}
		else
		{
			$this->net->html=$html;
			$this->net->searchLayout();
			$this->net->parse($debug);
			$erros=0;
		}
		$this->dataInicio=$this->net->getStartDate();
		$this->campos=$this->net->getFields();
		$this->andamentos=$this->net->getRecords();
*/

		return($ok);
	}

	function processaTJRJ($numero, $html)
	{
		// Variáveis

		$ok=true;
		$raizAnexo='';
		if ($numero<>'')
			$busca=false;
		$post='';

		$cookie=true;
		$url='';
		//$url[]="http://portaltj.tjrj.jus.br/web/guest";
		//$url[]="http://srv85.tjrj.jus.br/numeracaoUnica/faces/index.jsp?numProcesso=$numero";

		//$url[]="http://www4.tjrj.jus.br/web/guest";
		//$url[]="http://www4.tjrj.jus.br/numeracaoUnica/faces/index.jsp?numProcesso=$numero";
/*
N=
form:btnumero=
form:btorigem=1
form:commandButton3=Pesquisar
form:id=
form:nprotocolo=
form:selectOneRadio1=1
form:tipoConsulta=ifp
parte1ProcCNJ=0355916-55.2011
parte2ProcCNJ=0001
selOpcaoNumeracao=1
*/
		$url="http://www4.tjrj.jus.br/numeracaoUnica/faces/index.jsp?numProcesso=$numero";
			// http://www4.tjrj.jus.br/numeracaoUnica/faces/index.jsp?numProcesso=0355916-55.2011.8.19.0001

		$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
		$this->net->setRaizAnexo($raizAnexo);

		// Layouts para busca

		$netLayout=new gNetLayout("{name: 'ARQUIVADO EM DEFINITIVO'}"); // Primeira Instancia
		$netLayout->add("{type: text; name: numero; start:form; end:footer; tag: td; index: 2}}");
		$netLayout->add("{type: text; name: orgao_julgador_atual; start:form; end:footer; tag: td; index: 9}}");
		//$netLayout->add("{type: text; name: descricao; start:<table; end:footer; tag: td; index: 23}}");

		$this->net->addLayout($netLayout);

		$netLayout=new gNetLayout("{name: 'Consulta Processual por Número - 2ª Instância'}");
		$netLayout->add("{type: text; name: numero; start:<table; end:rodape; tag: td; index: 2}}");
		//$netLayout->add("{type: text; name: descricao; start:<table; end:rodape; tag: td; index: 5}}");
		$netLayout->add("{type: text; name: orgao_julgador_atual; start:<table; end:rodape; tag: td; index: 9}}");

		$this->net->addLayout($netLayout);

		$netLayout=new gNetLayout("{name: 'Prioridade - Pessoa Idosa'}");
		$netLayout->add("{type: text; name: numero; start:<table; end:footer; tag: td; index: 2}}");
		//$netLayout->add("{type: text; name: descricao; start:<table; end:footer; tag: td; index: 25}}");
		$netLayout->add("{type: text; name: orgao_julgador_atual; start:<table; end:footer; tag: td; index: 11}}");

		$this->net->addLayout($netLayout);

		$netLayout=new gNetLayout("{name: 'Juizado Especial'}");
		$netLayout->add("{type: text; name: numero; start:<table; end:footer; tag: td; index: 2}}");
		//$netLayout->add("{type: text; name: descricao; start:<table; end:footer; tag: td; index: 28}}");
		$netLayout->add("{type: text; name: orgao_julgador_atual; start:<table; end:footer; tag: td; index: 11}}");

		$this->net->addLayout($netLayout);

		$netLayout=new gNetLayout("{name: '- Primeira inst'}");
		$netLayout->add("{type: text; name: numero; start:<table; end:footer; tag: td; index: 2}}");
		//$netLayout->add("{type: text; name: descricao; start:<table; end:footer; tag: td; index: 24}}");
		$netLayout->add("{type: text; name: orgao_julgador_atual; start:<table; end:footer; tag: td; index: 10}}");

		$this->net->addLayout($netLayout);


		$netLayout=new gNetLayout("{name: 'Processo n'}");
		$netLayout->add("{type: text; name: numero; start:<table; end:footer; tag: td; index: 2}}");

		$this->net->addLayout($netLayout);


		// Carregamento de dados



		if ($html=='')
		{
			$this->net->siteOpen($url, $post, $cookie);
			$this->net->searchLayout();
			$this->net->parse($debug);
		}
		else
		{
			$this->net->html=$html;
			$this->net->searchLayout();
			$this->net->parse($debug);
		}

		$tenta=0;
		$debug=0;
		$procura=false;
		if ($html=="")
			$html=$this->net->html;
//echo "$url\n\n\n\n\n\n\n$html\n\n\n\n\n\n\n";exit;
		if ((stripos($html, "Internal Server Error")===false)&&(stripos($html, "Um erro inesperado ocorreu")===false))
		{
			$agravo=false;
			if (stripos($html, "Listar Todos Movimentos")!==false)
			{

				$post="";
				$cookie=true;

				// Identifica se a consulta é normal ou ajax
				//if ((stripos($html, "consultaMov.do")===false) || (stripos($html, "consultaProc.do")!==false))
				if (stripos($html, "consultaMov.do")===false)
				{
					$agravo=true;
					$procura=false;
					gLog("\n\n============= Processando AJAX - uma página\n\n");
					$this->processaTJRJ_ajax($html);
				}
				else
				{
					//http://www4.tjrj.jus.br/consultaProcessoWebV2/consultaMov.do?v=2&numProcesso=2008.001.034284-3&acessoIP=internet&tipoUsuario=
					$u=substr($html, stripos($html, "consultaMov.do"));
					$u=substr($u, 0, stripos($u, "'"));
					$u="http://www4.tjrj.jus.br/consultaProcessoWebV2/".$u."&captcha=".$_REQUEST['captcha'];
					$url=$u;
//echo "<h1>$url</h1>";
					//$url[]="http://portaltj.tjrj.jus.br/web/guest";
					//$url[]=$u;
				}




				$this->net->html='';
				$this->net->htmlTags='';
				$this->net->siteOpen($url, $post, $cookie);
				$html=$this->net->html;
				//echo "===== ".$this->net->html."<pre>";print_r($url); print_r($this->net->htmlTags);exit;
				// Testar 0005431-30.2011.8.19.0000


			}
			if (!$agravo)
			{
				gLog("\n\n============= Processando NORMAL - uma página\n\n");
				$procura=$this->processaTJRJ_normal($numero);
			}
		} else
			$ok=false;

		// Vários links para consultas, portanto, varre um por um...
		$html=substr($html,stripos($html,"<form"));
		$cnt=0;
		$procura=true;
		if ($procura) gLog("\n\n============= Processando várias páginas\n\n");
		while ($procura)
		{
			if ((strlen($html)>5)&&($cnt<5))
			{
//echo "============html:<br><br><br>$html";exit;
				$p1=stripos($html, "http:");
				$pf=stripos($html, "</form");
				if (($p1!==false) && ($pf>$p1))
				{
					$cnt++;
					$html=substr($html, $p1);
					$p2=stripos($html, "'");
					$href=$this->net->htmlTextClean(substr($html, 0, $p2));
					$href=str_replace("&amp;", "&", $href);
					$this->net->html='';
					$this->net->htmlTags='';
					gLog("\n\n==========> Processando: $href\n\n");




					//$this->net->siteOpen($href, '', true);
					//$html2=$this->net->html;
					if (stripos($html2, "consultaMov.do")===false)
						$href.="&captcha=".$_REQUEST['captcha'];
					$ckfile = $this->getCookie();
					$ch = curl_init();
					curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
					curl_setopt($ch,CURLOPT_URL,$href);
					curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
					$html2 = curl_exec ($ch);
					curl_close ($ch);

					$agravo=false;
					$post="";

//echo "\n\n\n\n\n $html2 \n\n\n\n\n";exit;
					if (stripos($html2, "Listar Todos Movimentos")!==false)
					{

						// Identifica se a consulta é normal ou ajax
						if (stripos($html2, "consultaMov.do")===false)
						{
							gLog("====> Consulta todos mov. AJAX");
							$agravo=true;
							$this->processaTJRJ_ajax($html2);
						}
						else
						{
							gLog("====> Consulta todos mov. NORMAL");

							$u=substr($html2, stripos($html2, "consultaMov.do"));
							$u=substr($u, 0, stripos($u, "'"));
							$u="http://www4.tjrj.jus.br/consultaProcessoWebV2/".$u."&captcha=".$_REQUEST['captcha'];
							$url="";
							$url[]="http://portaltj.tjrj.jus.br/web/guest";
							$url[]=$u;
						}

						$this->net->html='';
						$this->net->htmlTags='';
						$this->net->siteOpen($url, $post, true);
//echo "===== ".$this->net->html."<pre>";print_r($url); print_r($this->net->htmlTags);exit;
						// Testar 0005431-30.2011.8.19.0000
					} else
					{
						gLog("====> Não tem opção de listar todos");
					}
					if (!$agravo)
					{
						$this->processaTJRJ_normal($numero);
					}
					$html=substr($html,$p2);
				}
				else
				{
					$procura=false;
				}
			}
			else
			{
				$procura=false;
			}
		}

		$this->menorData($this->net->records);
		$this->campos=$this->net->getFields();
		$this->andamentos=$this->net->getRecords();
//echo "<br><hr><br><pre>";print_r($this->campos);print_r($this->andamentos);echo "</pre>";
		return($ok);
	}





	function processaTJRJ_normal($numero)
	{
		global $debug;
		// Array = (posição da data), (posição do campo), separadora, (posição do campo)...
		$and["Arquivamento"]
			=array(2,3,' ',4,chr(10),5,' ',6,chr(10),7,' ',8,chr(10),9,' ',10);
		$and["Publicado"]
			=array(2,3,' ',4);
		$and["Remessa"]
			=array(4,1,' ',2,chr(10),5,' ',6);
		$and["Decisão - Reforma de decisão anterior"]
			=array(2,3,' ',4,chr(10),5,' ',6);
		$and["Recebidos os autos"]
			=array(2,3,' ',4);
		$and["Publicado&nbsp; Decisão"]
			=array(2,3,' ',4);
		$and["Digitação de Documentos"]
			=array(2,5,' ',6);
		$and["Ato Ordinatório Praticado"]
			=array(2,4);
		$and["Conclusão ao Juiz"]
			=array(2,4);
		$and["Despacho -"]
			=array(2,3,' ',4,chr(10),5,' ',6);
		$and["Sentença"]
			=array(2,4);
		$and["Decisão -"]
			=array(2,4,chr(10),5,chr(10),6);
		$and["Audiência"]
			=array(2,3,' ',4);
		$and["Juntada"]
			=array(2,3,chr(10),4,chr(10),5,' ',6,chr(10),7,' ',8);
		$and["Vista ao Advogado"]
			=array(4,1,' ',2,chr(10),5,' ',6);
		$and["Distribuição Dependência"]
			=array(2,3,' ',4);
		$and["Distribuição Sorteio"]
			=array(2,3,' ',4);
		$and["Trânsito em Julgado - Baixa"]
			=array(2);
		$and["Despacho - Proferido despacho de mero expediente"]
			=array(2,4,chr(10),5,' ',6);

		gLog(">>>>>> html apos captcha");
		gLog($this->net->html);
		gLog("<<<<<<");
//echo "Aqui ERRO:<br><textarea rows=25 cols=160>".$this->net->html."</textarea>";
		$html=substr($this->net->html, stripos($this->net->html, "<form"));
		if (stripos($html,"</form")!==false)
			$html=str_replace("</form>", "", $html);
		$procura=true;
		if (stripos($html, "Processo n")!==false)
		{
//echo "\n<h1>entrou 1</h1>\n";
			$this->net->html=$html;
			$this->net->searchLayout($debug);
			$this->net->parse($debug);

			// Buscando despachos...
			$despachos="";
			$desp=explode('^',str_ireplace('<input type="HIDDEN" name="','^',$this->net->html));
			foreach ($desp as $d)
			{
//echo "\n<h1>entrou 2</h1>\n";
				if (substr($d,0,7)=="descMov")
				{
					$descMov=substr($d,7);
					$descMov=substr($descMov,0,strpos($descMov,chr(34)));
					$despTxt=substr($d,strpos($d,"value=")+7);
					$despTxt=substr($despTxt,0,strpos($despTxt,chr(34))-1);
					$despTxt=str_replace("~","í",$despTxt);
					$despTxt=str_replace(chr(34),"",$despTxt);
					$despTxt=str_replace("'","",$despTxt);
					$despachos[$descMov]=$despTxt;
				}
			}
			//echo "despachos:<br><pre>";print_r($despachos);exit;
			$procura=false;
			if (stripos($this->net->fields['autor_advogado'], "-")!==false)
			{
				// Inverte OAB do advogado
				$tmp=explode("-", $this->net->fields['autor_advogado']);
				$this->net->fields['autor_advogado']=trim($tmp[1]." - ".$tmp[0]);
			}
			$this->net->fields["relator"]="";
 //echo "===== $url <pre>";print_r($this->net->htmlTags);exit;
			$tmp=$this->net->htmlTags["td"]["<table~footer"];
			if (!is_array($tmp))
				$tmp=$this->net->htmlTags["td"]["<table~rodape"];

			$a=0;
			while ($a<count($tmp))
			{
//echo "\n<h1>entrou 3</h1>\n";
				$tmp[$a][0]=autoencode(trim($tmp[$a][0]));
//echo "<br>$a. =>".$tmp[$a][0];
				// Andamentos
				if (stripos($tmp[$a][0], "Tipo do Movimento:")!==false)
				{
					$a++;
					$campos=array(2);
					foreach ($and as $andTipo=>$andCampos)
					{
						if (stripos($tmp[$a][0],$andTipo)!==false)
						{
							$campos=$andCampos;
						}
					}
					$rec['data']=$this->net->htmlDateFormat($tmp[($a+$campos[0])][0]);
					$rec['descricao']=$tmp[$a][0].chr(10);
					$max=$campos[count($campos)-1]-1;
					for ($c=1; $c<count($campos); $c++)
					{
						if (($tmp[($a+$campos[$c])][0]=="Tipo do Movimento:") || ($tmp[($a+$campos[$c]+1)][0]=="Tipo do Movimento:"))
						{
							// Se encontrar "Tipo do Movimento" pelo meio, interrompe a concatenação dos TD
							$max=$campos[$c]-2;
							$c=count($campos);
						} else
						{
							if (is_numeric($campos[$c]))
							{
								if (stripos($tmp[($a+$campos[$c])][1],"popdespacho")!==false)
								{

									$u=substr($tmp[($a+$campos[$c])][1], stripos($tmp[($a+$campos[$c])][1], "&numMov=")+8);
									$u=substr($u, 0, stripos($u, "&"));
									$rec['descricao'].=$despachos[$u];
								} else
								{
									$rec['descricao'].=$tmp[($a+$campos[$c])][0];
								}
							} else
							{
								$rec['descricao'].=$campos[$c];
							}
						}
					}
					$this->net->records[]=$rec;
					$a=$a+$max;
				}

				if (stripos($tmp[$a][0], "Distribuído em")!==false)
				{
					$s=str_replace(chr(10), "", $tmp[$a][0]);
					$s=trim(substr($s, stripos($s, "Distribuído em")+15));
					$rec['data']=$this->net->htmlDateFormat($s);
					$rec['descricao']="Distribuição";
					$this->net->records[]=$rec;
				}
				elseif ((stripos($tmp[$a][0], "Fase:")!==false) || (stripos($tmp[$a][0], "FASE ATUAL:")!==false))
				{
//echo "*** ".$tmp[$a][0]." = ".$tmp[$a+3][0]."<br>";
					$rec['data']=$this->net->htmlDateFormat($tmp[$a+3][0]);
					$rec['descricao']=$this->net->htmlTextClean($tmp[$a+1][0]);
					$this->net->records[]=$rec;
					$a++;$a++;$a++;
				}
				elseif (stripos($tmp[$a][0], "Turma Recursal:")!==false)
				{
					$this->net->fields["orgao_julgador_atual"]=$this->net->htmlTextClean($tmp[$a+1][0]);
					$a++;
				}
				elseif (stripos($tmp[$a][0], "Classe:")!==false)
				{
					$this->net->fields["descricao"]=$this->net->htmlTextClean($tmp[$a+1][0]);
					$a++;
				}
				elseif (stripos($tmp[$a][0], "Relator:")!==false)
				{
					$this->net->fields["relator"]=gUcwords(str_ireplace("Classe:","",$tmp[$a+1][0]));
					$a++;
				}
				elseif (($a<15) && ((stripos($tmp[$a][0], "Vara Cível")!==false)||(stripos($tmp[$a][0], "Juizado Especial")!==false)||(stripos($tmp[$a][0], "Comarca d")!==false)||(substr($tmp[$a][0], 0, 10)=="Regional d")))
				{
					if (stripos($this->net->fields["justica_origem"],$tmp[$a][0])===false)
					{
						if ((stripos($tmp[$a][0], "Juizado Especial")!==false) || (substr($tmp[$a][0], 0, 9)=="Comarca d") || (substr($tmp[$a][0], 0, 10)=="Regional d") || ((stripos($tmp[$a][0], "Vara Cível")!==false) && (stripos($this->net->fields["justica_origem"], "Vara Cível")===false)))
						{
							if ($this->net->fields["justica_origem"]<>"")
								$this->net->fields["justica_origem"].=" - ";
							$this->net->fields["justica_origem"].=$tmp[$a][0];
							$this->net->fields["localizacao"]=$this->net->fields["justica_origem"];
						}
					}
					$a=$a+2;
				}
				elseif ((substr($tmp[$a][0], 0, 5)=="Autor")||(stripos($tmp[$a][0], "Embargado")!==false)||(stripos($tmp[$a][0],"Exequente")!==false)||(stripos($tmp[$a][0],"Requerente")!==false)||(stripos($tmp[$a][0],"Recorrido")!==false))
				{
					if ((stripos($tmp[$a+1][0], "e outro(s)...")===false)&&(stripos($this->net->fields["autor"], $tmp[$a+1][0])===false))
					{
						if ($this->net->fields["autor"]<>"")
							$this->net->fields["autor"].=chr(10);
						$this->net->fields["autor"].=trim($tmp[$a+1][0]);
					}
				}
				elseif ((substr($tmp[$a][0], 0, 3)=="Réu")|| (substr($tmp[$a][0], 0, 3)=="Ré") || (stripos($tmp[$a][0], "Embargante")!==false)||(stripos($tmp[$a][0],"Executado")!==false)||(stripos($tmp[$a][0],"Falecido")!==false)||(stripos($tmp[$a][0],"Recorrente")!==false))
				{
					if ((stripos($tmp[$a+1][0], "e outro(s)...")===false)&&(stripos($this->net->fields["reu"], $tmp[$a+1][0])===false))
					{
						if ($this->net->fields["reu"]<>"")
							$this->net->fields["reu"].=chr(10);
						$this->net->fields["reu"].=trim($tmp[$a+1][0]);
					}
				}
				elseif (stripos($tmp[$a][0], "Advogado(s)")!==false)
				{
					// O TJ RJ não identifica qual advogado é de qual lado...

					//$adv=str_replace(chr(10),'',$tmp[$a+1][0]);
					$adv=$tmp[$a+1][0];
					if (stripos($adv, "-")!==false)
					{
						$ad=explode(chr(10), $adv);
						$adv=$ad[2]." - ".$ad[0];
						if (count($ad)>3)
							$adv.=chr(10).$ad[5]." - ".$ad[3];
						if (count($ad)>6)
							$adv.=chr(10).$ad[8]." - ".$ad[6];
						if (count($ad)>9)
							$adv.=chr(10).$ad[11]." - ".$ad[9];
						if (count($ad)>12)
							$adv.=chr(10).$ad[14]." - ".$ad[12];
					}
					if ((trim($tmp[$a+2][0])=="") && (stripos($tmp[$a+3][0],"-")!==false))
					{
						$ad=explode(chr(10), $tmp[$a+3][0]);
						$adv.=chr(10).$ad[2]." - ".$ad[0];

					}
					if ((trim($tmp[$a+4][0])=="") && (stripos($tmp[$a+5][0],"-")!==false))
					{
						$ad=explode(chr(10), $tmp[$a+5][0]);
						$adv.=chr(10).$ad[2]." - ".$ad[0];

					}
					if ((trim($tmp[$a+6][0])=="") && (stripos($tmp[$a+7][0],"-")!==false))
					{
						$ad=explode(chr(10), $tmp[$a+7][0]);
						$adv.=chr(10).$ad[2]." - ".$ad[0];

					}
					if ((trim($tmp[$a+8][0])=="") && (stripos($tmp[$a+9][0],"-")!==false))
					{
						$ad=explode(chr(10), $tmp[$a+9][0]);
						$adv.=chr(10).$ad[2]." - ".$ad[0];

					}
					$this->net->fields["autor_advogado"]=$adv;
				}
				elseif (stripos(str_replace(chr(10), '', $this->net->htmlTextClean($tmp[$a][0])), "Pr&oacute;xima Audi&ecirc;ncia")!==false)
				{
					if ((strlen($tmp[$a+3][0])==5))
					{
						$rec['data']=$this->net->htmlDateFormat($tmp[$a+1][0]);
						$rec['descricao']="Audiência marcada para ".$tmp[$a+1][0]." às ".$tmp[$a+3][0];
						$this->net->records[]=$rec;
					}
				}

				if (stripos($tmp[$a][0], "Processo(s) Apensado(s)")!==false)
				{
					relacionaProcesso($numero, $tmp[$a+1][0], "apensos");
				}

				if (stripos($tmp[$a][0], "Processo(s) no Tribunal")!==false)
				{
					//gLog("==============> no Tribunal: $numero");
					relacionaProcesso($numero, $tmp[$a+1][0], "tribunal");
				}

				if (stripos($tmp[$a][0], "Processo(s) no Conselho")!==false)
				{
					relacionaProcesso($numero, $tmp[$a+1][0], "conselho");
				}
				$a++;
			}
//echo "<br>Andamentos encontrados:<br><pre>";print_r($this->net->records);echo "</pre>";exit;
		}
		return($procura);
	}

	function processaTJRJ_ajax($html)
	{
		global $debug;
		// Agravo (2a instancia)
		// Pega numero antigo

		$u=substr($html, stripos($html, "ConsultaProcesso.aspx")+24);
		$u=substr($u, 0, stripos($u, chr(34)));
		$post='{"nAntigo":"'.$u.'"}';

		// Busca dados do processo pra pegar codDoc
		$url="";
		$url[]="http://portaltj.tjrj.jus.br/web/guest";
		//$url[]="http://webserver2.tjrj.jus.br/ejud/WS/ConsultaEjud.asmx/DadosProcesso";
		$url[]="http://www4.tjrj.jus.br/ejud/WS/ConsultaEjud.asmx/DadosProcesso";

		$this->net->html='';
		$this->net->htmlTags='';
		$this->net->siteOpen($url, $post, true, false, true);

		$html=substr($this->net->html, 5);
		$html=substr($html, 0, strlen($html)-1);
		$html=utf8_encode($html);
		$mtz=json_decode($html);
		if ($mtz->CodCNJ<>'')
		{
			$codDoc=$mtz->CodDoc;
			$this->net->fields['numero']=$mtz->CodCNJ;
			$this->net->fields["descricao"]=$mtz->DescrClasse;
			$this->net->fields["orgao_julgador_atual"]=$mtz->OrgaoJulgador;
			$this->net->fields["relator"]=$mtz->Relator;
			$this->net->fields["justica_origem"]=$mtz->Vara.chr(10).$mtz->DescrOrigem;
			$this->net->fields["localizacao"]=$this->net->fields["justica_origem"];
			$autores="";
			$reus="";
			$advA="";
			$advR="";
			$c=count($mtz->Partes);
			for ($p=0; $p<$c; $p++)
			{
				$elTipo=$mtz->Partes[$p]->Tipo;
				$elNome=$mtz->Partes[$p]->Nome;
				$elNome=str_replace("DR(a). ", "", $elNome);
				if ($elTipo=="Autor")
					$autores[]=$elNome;
				if ($elTipo=="Reu")
					$reus[]=$elNome;
				if ($elTipo=="Advogado")
				{
					if (is_array($reus))
					{
						$advR[]=$elNome;
					}
					else
					{
						$advA[]=$elNome;
					}
				}
			}
			$this->net->fields["autor"]=implode(chr(10), $autores);
			$this->net->fields["reu"]=implode(chr(10), $reus);
			$this->net->fields["autor_advogado"]=implode(chr(10), $advA);
			$this->net->fields["reu_advogado"]=implode(chr(10), $advR);


			// Andamentos

			$post='{"codDoc":"'.$codDoc.'"}';

			// Busca dados do processo pra pegar codDoc
			$url="";
			$url[]="http://portaltj.tjrj.jus.br/web/guest";
			//$url[]="http://webserver2.tjrj.jus.br/ejud/WS/ConsultaEjud.asmx/ConsultarMovimentos";
			$url[]="http://www4.tjrj.jus.br/ejud/WS/ConsultaEjud.asmx/ConsultarMovimentos";

			$this->net->html='';
			$this->net->htmlTags='';
			$this->net->siteOpen($url, $post, true, false, true);

			$html=substr($this->net->html, 5);
			$html=substr($html, 0, strlen($html)-1);
			$html=utf8_encode($html);
			$mtz=json_decode($html);

			foreach ($mtz as $andamento)
			{
				$c=count($andamento->DadosMovimentos);
				$txt="";
				$txt[]=$andamento->Descr;
				for ($p=1; $p<$c; $p++)
				{
					$txt[]=$andamento->DadosMovimentos[$p]->Descr.": ".$andamento->DadosMovimentos[$p]->Valor;
				}
				$rec['data']=$this->net->htmlDateFormat(substr($andamento->DadosMovimentos[0]->Valor, 0, 10));
				$rec['descricao']=implode(chr(10), $txt);
				$this->net->records[]=$rec;
			}

			//echo "===== <pre>"; print_r($mtz); print_r($this->net->records);exit;
			//echo "<pre>";print_r($this->net->fields);exit;
			//http://webserver2.tjrj.jus.br/ejud/WS/ConsultaEjud.asmx/DadosProcesso
			//http://webserver2.tjrj.jus.br/ejud/WS/ConsultaEjud.asmx/ConsultarMovimentos
		}

	}


	function processaTRF2A($numero, $html)
	{
		// Variáveis

		$ok=true;
		$numeroAlt=substr($numero, 11, 4).substr($numero, 21, 4).substr($numero, 0, 7);
		$parametros="?proc=$numeroAlt&amp;mov=1";
		$url="http://www.trf2.gov.br/cgi-bin/pingres-allen".$parametros;
		$raizAnexo="";
		$tenta=0;
		$procura=false;

		$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
		$this->net->setRaizAnexo($raizAnexo);

		// Layouts para busca
		$netLayout=new gNetLayout("{name: 'Processo:'}");
		$netLayout->add("{type: text; name: numero; start:<div; end:; tag: p; index: 0}");

		$this->net->addLayout($netLayout);

		// Carregamento de dados

		if ($html=='')
		{
			$this->net->siteOpen($url, $post, $cookie);
		}
		else
		{
			$this->net->html=$html;
		}

		if ((stripos($html, "Internal Server Error")===false)&&(stripos($html, "Um erro inesperado ocorreu")===false))
		{
			$html=utf8_encode($this->net->html);
			$procura=true;
			$cnt=0;
			if (stripos($html, "Processo:")!==false)
			{
				$this->net->html=$html;
				$this->net->searchLayout($debug);
				$this->net->parse($debug);
				$procura=false;
				//echo "===== $url <pre>";print_r($this->net->htmlTags);exit;
				$tmp=$this->net->htmlTags["p"]["<div~"];
				for ($a=0; $a<count($tmp); $a++)
				{
					if (stripos($tmp[$a][0], "Processo:")!==false)
					{
						$this->net->fields['numero']=trim(substr($tmp[$a][0], 13, 25));
					}
					elseif (stripos($tmp[$a][0], "APTE ")!==false)
					{
						$this->net->fields['reu']=trim(substr($tmp[$a][0], 5));
						$this->net->fields['reu_advogado']=trim(substr($tmp[$a+1][0], 5));
					}
					elseif (stripos($tmp[$a][0], "APDO ")!==false)
					{
						$this->net->fields['autor']=trim(substr($tmp[$a][0], 5));
						$this->net->fields['autor_advogado']=trim(substr($tmp[$a+1][0], 5));
					}
					elseif (substr($tmp[$a][0], 0, 7)=="RELATOR")
					{
						$this->net->fields['relator']=trim(substr($tmp[$a][0], 9));
					}
					elseif (stripos($tmp[$a][0], "LOCALIZA")!==false)
					{
						$this->net->fields['localizacao']=trim(substr($tmp[$a][0], 14));
					}
					elseif (stripos($tmp[$a][0], "Em ")!==false)
					{
						$s=substr($tmp[$a][0], 3, 10);
						$desc=trim($tmp[$a+1][1]);
						$desc=str_ireplace("Rio de <BR>Janeiro", "Rio de Janeiro", $desc);
						$desc=trim(strip_tags(str_replace("<BR>", chr(10), $desc)));
						$rec['data']=$this->net->htmlDateFormat($s);
						$rec['descricao']=trim(substr($tmp[$a][0], 16)).chr(10).$desc;
						$this->net->records[]=$rec;
					}
				}
			}

			$this->menorData($this->net->records);
		}

		$this->campos=$this->net->getFields();
		$this->andamentos=$this->net->getRecords();

		return($ok);
	}



	function processaJFRJ($numero, $html)
	{
		global $usrId;
		$ok=true;
		$raizAnexo='';
		if ($numero<>'')
			$busca=false;
		$usarCookie=true;
		$post='';
		$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
		// Carregamento de dados

		$ckfile = tempnam(sys_get_temp_dir(), "alitem-cookie-".$usrId."-");

		// Obtendo o EstatCont e Cookies
		$url="http://procweb.jfrj.jus.br/portal/consulta/cons_procs.asp";

		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
		//echo "<h1>$ckfile</h1>";
		curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
		curl_setopt ($ch, CURLOPT_HTTPHEADER,array ("Connection:	keep-alive","Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"));
		curl_setopt ($ch, CURLOPT_REFERER, "http://www.jfrj.jus.br/?id_info=8");
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
		//curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		//curl_setopt(CURLOPT_FILE, '/tmp/cookies_file');
		$html = curl_exec ($ch);
		curl_close ($ch);
//echo $html;exit;

		// Enviando parametros do processo
		$EstatCont=substr($html,stripos($html,'name="EstatCont"')+24);
		$EstatCont=substr($EstatCont,0,stripos($EstatCont,'"'));
		$post="";
		$post["A"]="";
		$post["Botao"]="Pesquisar";
		$post["C"]="";
		$post["CampoFoco"]="";
		$post["CodAdv"]="";
		$post["CodDoc"]="";
		$post["CodLoc"]="";
		$post["CodOAB"]="";
		$post["CodTipDocPess"]="";
		$post["EstatCont"]=$EstatCont;
		$post["FecharSessao"]="";
		$post["Localidade"]="0";
		$post["NomeAdv"]="";
		$post["NomeParte"]="";
		$post["NumDocPess"]="";
		$post["NumInq"]="";
		$post["NumProc"]=$numero;

		$post["TipDocPess"]="0";
		$post["UsarCaptcha"]="S";
		$post["Validar"]="";
		$post["baixado"]="0";
		$post["captcha"]="";
		$post["captchacode"]="1";
		$post["gabarito"]="1";
		$post["resposta"]="1";
		$url="http://procweb.jfrj.jus.br/portal/consulta/cons_procs.asp";
		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
		$bodyData = http_build_query($post);
		//echo "bodyData: <pre>";print_r($bodyData);echo "</pre>Size: ".strlen($bodyData);
		curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
		curl_setopt ($ch, CURLOPT_HTTPHEADER,array (
			"Connection:	keep-alive",
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Content-Type	application/x-www-form-urlencoded"),
			"Content-Length: ".strlen($bodyData));
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($ch, CURLOPT_POST, true);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);
		curl_setopt ($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt ($ch, CURLOPT_AUTOREFERER, true);

		$html = curl_exec ($ch);
		curl_close ($ch);

		// Buscando IDNumConsProc e Procs
		$url="http://procweb.jfrj.jus.br/portal/consulta/reslistproc.asp?SelectProc=";
		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
		curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
		curl_setopt ($ch, CURLOPT_HTTPHEADER,array ("Connection:	keep-alive","Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"));
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt ($ch, CURLOPT_REFERER, "http://procweb.jfrj.jus.br/portal/consulta/resconsproc.asp");
		$html = curl_exec ($ch);
		curl_close ($ch);
		//name="IDNumConsProc" value="41904548">
		//option ID=14425503 value=14425503 ACDC
		$IDNumConsProc=substr($html,stripos($html,'name="IDNumConsProc"')+28);
		$IDNumConsProc=substr($IDNumConsProc,0,stripos($IDNumConsProc,'"'));
		$CodDoc=substr($html,stripos($html,'option ID=')+10);
		$CodDoc=substr($CodDoc,0,stripos($CodDoc,' '));

		$url="http://procweb.jfrj.jus.br/portal/consulta/resinfoproc.asp?CodDoc=$CodDoc&IDNumConsProc=$IDNumConsProc&CodUsuWeb=";
		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
		curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
		curl_setopt ($ch, CURLOPT_HTTPHEADER,array ("Connection:	keep-alive","Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"));
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt ($ch, CURLOPT_REFERER, "http://procweb.jfrj.jus.br/portal/consulta/resconsproc.asp");
		$html = utf8_encode(curl_exec ($ch));
		curl_close ($ch);
		//echo $html;exit;
		if ((stripos($html,"<textarea")!==false) && (stripos($html,$numero)!==false))
		{
			$html=substr($html,stripos($html,"<textarea"));
			$html=substr($html,stripos($html,$numero));
			$html=substr($html,0,stripos($html,"</textarea"));
		//echo "$html";
			$linhas=explode(chr(10),$html);
			$desc=$linhas[1];
			if (stripos($desc,"-")!==false)
				$desc=trim(substr($desc,stripos($desc,"-")+1));
			$this->net->fields['numero']=$numero;
			$this->net->fields['descricao']=gUcwords($desc);
			foreach ($linhas as $lin)
			{
				if (stripos($lin,"Localização atual")!==false)
				{
					$this->net->fields['orgao_julgador_atual']=trim(substr($lin,stripos($lin,":")+1));
					$this->net->fields['localizacao']=trim(substr($lin,stripos($lin,":")+1));
				}
			}

			$url="http://procweb.jfrj.jus.br/portal/consulta/resinfopartes2.asp?CodDoc=$CodDoc";
			$ch = curl_init ($url);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
			curl_setopt ($ch, CURLOPT_HTTPHEADER,array ("Connection:	keep-alive","Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"));
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt ($ch, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt ($ch, CURLOPT_REFERER, "http://procweb.jfrj.jus.br/portal/consulta/resconsproc.asp");
			$html = utf8_encode(curl_exec ($ch));
			curl_close ($ch);
			$html=substr($html,stripos($html,"<form"));
			$html=substr($html,0,stripos($html,"</form"));
			$html=strip_tags($html);
			$linhas=explode(chr(10),$html);
			foreach ($linhas as $a=>$lin)
			{
				if (strpos($lin,"AUTOR")!==false)
				{
					$this->net->fields['autor']=gUcwords(trim($linhas[$a+5]));
				}
				if (trim($lin)=="REU")
				{
					$this->net->fields['reu']=gUcwords(trim($linhas[$a+5]));
				}
				if (strpos($lin,"ADVOGADO")!==false)
				{
					$adv=explode(" - ",trim($linhas[$a+5]));
					$adv=$adv[1]." - ".$adv[0];
					$this->net->fields['autor_advogado']=gUcwords($adv);
				}
			}

			$url="http://procweb.jfrj.jus.br/portal/consulta/resinfomov2.asp?CodDoc=$CodDoc";
			$ch = curl_init ($url);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
			curl_setopt ($ch, CURLOPT_HTTPHEADER,array ("Connection:	keep-alive","Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"));
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt ($ch, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt ($ch, CURLOPT_REFERER, "http://procweb.jfrj.jus.br/portal/consulta/resconsproc.asp");
			$html = utf8_encode(curl_exec ($ch));
			curl_close ($ch);
			$html=substr($html,stripos($html,"<form"));
			$html=substr($html,0,stripos($html,"</form"));
			$html=strip_tags($html);
			$linhas=explode(chr(10),$html);
			foreach ($linhas as $a=>$lin)
			{
				$lin=trim($lin);
				$descr=trim($linhas[$a+5]);
				if ((substr($lin,2,1)=="/") && (substr($lin,5,1)=="/") && (substr($lin,13,1)==":"))
				{
					$data=substr($lin,0,10);
					$hora=trim(substr($lin,11));
					$rec['data']=$this->net->htmlDateFormat($data)." ".$hora;
					$rec['descricao']=$descr;
					$this->net->records[]=$rec;
				}
			}
			$this->net->html=$html;
			$this->menorData($this->net->records);

			$this->campos=$this->net->getFields();
			$this->andamentos=$this->net->getRecords();

		} else
		{
			$ok=false;
		}
	//echo "$html";
	//echo "<pre>";print_r($this->net->fields);print_r($this->net->records);exit;

		// Partes: http://procweb.jfrj.jus.br/consulta/resinfopartes2.asp?CodDoc=$CodDoc

		// Andamentos: http://procweb.jfrj.jus.br/consulta/resinfomov2.asp?CodDoc=$CodDoc

		// Valor da causa: http://procweb.jfrj.jus.br/consulta/resinfocompl2.asp?CodDoc=$CodDoc
		unlink($this->cookieFile);

		return($ok);
	}

	function processaSTJ($numero, $html)
	{
		/*
		 * Observações:
		 *
		 * Os processos não seguem o padrão do número único, e se for informado
		 * somente o número, podem retornar vários processos. Exemplo:
		 * 1298081 retorna: REsp 1298081, Ag 1298081, EREsp 1298081
		 * E eles são processos diferentes!
		 *
		 * Caso retornem vários processos, o sistema rejeitará, obrigando o usuário
		 * a inserir um de cada vez com o nome completo.
		 */

		$ok=true;
		$raizAnexo='';
		if ($numero<>'')
			$busca=false;
		$post='';

		$cookie=true;
		$url='';
		//$url[]="http://www.stj.jus.br/webstj/processo/Justica/valida.asp";
		//$url[]="http://www.stj.jus.br/webstj/processo/Justica/pagina_lista.asp";

		$ckfile1 = tempnam(sys_get_temp_dir(), "alitem-cookie-".$usrId."-1");
		$ckfile2 = tempnam(sys_get_temp_dir(), "alitem-cookie-".$usrId."-2");

		// Obtendo o primeiro Cookie
		$url="http://www.stj.gov.br/portal_stj/publicacao/engine.wsp";
		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile1);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		$html = curl_exec ($ch);
		curl_close ($ch);

		// Enviando parametros do processo e obtendo segundo cookie (este será usado pra obter o processo)
		$post="";
		//$post['num_pro']=str_replace(" ","+",$numero);
		$post['num_pro']=$numero;
		$post['optTipo']="I";
		$post['chkordem']="DESC";
		$post['chkMorto']="MORTO";
		//echo "<pre>";print_r($post);exit;
		$url="http://www.stj.jus.br/webstj/processo/Justica/valida.asp";
		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);

		$bodyData = http_build_query($post);
		curl_setopt ($ch, CURLOPT_REFERER, "http://www.stj.gov.br/portal_stj/publicacao/engine.wsp");
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile2);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_POST, true);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $bodyData);

		$html = curl_exec ($ch);
		curl_close ($ch);


/*
		echo "=================<br>".$numero."<br>";
		echo "=================<br>".$ckfile1."<br>";
		echo nl2br(file_get_contents($ckfile1));
		echo "=================<br>".$ckfile2."<br>";
		echo nl2br(file_get_contents($ckfile2));
		echo "=================<br>";
*/

		$url="http://www.stj.jus.br/webstj/processo/Justica/pagina_lista.asp";
		$ch = curl_init ($url);
		curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);

		$bodyData = http_build_query($post);
		curl_setopt ($ch, CURLOPT_REFERER, "http://www.stj.gov.br/portal_stj/publicacao/engine.wsp");
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile2);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);

		$html = curl_exec ($ch);
		curl_close ($ch);


		//echo "<br><br>".$html;
		if (stripos($html,"Object Moved")!==false)
		{
			$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros
			// Layouts para busca
			$netLayout=new gNetLayout("{name: 'PROCESSO'}"); // Primeira Instancia
			$netLayout->add("{type: text; name: numero; start:<table; end:footer; tag: td; index: 2}}");
			$this->net->addLayout($netLayout);

			$p1=stripos($html, "detalhe.asp?numreg=");
			$html=substr($html, $p1);
			$p2=stripos($html, '"');
			$href=substr($html, 0, $p2);
			$href=str_replace("&amp;", "&", $href);
			$url="http://www.stj.jus.br/webstj/processo/Justica/$href";

			$ch = curl_init ($url);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);

			$bodyData = http_build_query($post);
			curl_setopt ($ch, CURLOPT_REFERER, "http://www.stj.gov.br/portal_stj/publicacao/engine.wsp");
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile2);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);

			$html2 = curl_exec ($ch);
			curl_close ($ch);

			$this->processaSTJ_normal($numero,$html2);
			$this->menorData($this->net->records);
			$this->campos=$this->net->getFields();
			$this->andamentos=$this->net->getRecords();
			$ok=true;
			//echo "<pre>";print_r($campos);
		} else
		{
			$ok=false;

			/*
			// Vários links para consultas, portanto, varre um por um...
			$html=substr($html,stripos($html,"Processos encontrados"));
			//echo "<br><br>".$html;
			$cnt=0;
			$achados="";

			$this->net=new gJuridico("{clean: true}"); // força a limpeza dos registros

			// Layouts para busca
			$netLayout=new gNetLayout("{name: 'PROCESSO'}"); // Primeira Instancia
			$netLayout->add("{type: text; name: numero; start:<table; end:footer; tag: td; index: 2}}");
			$this->net->addLayout($netLayout);

			while ($procura)
			{
				if ((strlen($html)>5)&&($cnt<15))
				{
					//echo "============html:<br><br><br>$html";exit;
					$p1=stripos($html, "detalhe.asp?numreg=");
					if ($p1!==false)
					{
						$cnt++;
						$html=substr($html, $p1);
						$p2=stripos($html, '"');
						$href=substr($html, 0, $p2);
						$href=str_replace("&amp;", "&", $href);

						if ($achados[$href]=="")
						{
							$achados[$href]=1;
							$url="http://www.stj.jus.br/webstj/processo/Justica/$href";
							$ch = curl_init ($url);

							$bodyData = http_build_query($post);
							curl_setopt ($ch, CURLOPT_REFERER, "http://www.stj.gov.br/portal_stj/publicacao/engine.wsp");
							curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile2);
							curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);

							$html2 = curl_exec ($ch);
							curl_close ($ch);


							if (stripos($html2, "Processos")!==false)
							{
								//echo ".....processando: $url ".crc32($html2)."<br>";
								$this->processaSTJ_normal($numero,$html2);
							}
						}
						$html=substr($html,$p2);
					}
					else
					{
						$procura=false;
					}
				}
				else
				{
					$procura=false;
				}
			}
			*/
		}



		return($ok);

	}

	function processaSTJ_normal($numero,$html)
	{
		$html=substr($html,stripos($html,"PROCESSO"));
		$html=utf8_encode($html);
		if ((stripos($html, "Internal Server Error")===false)&&(stripos($html, "Um erro inesperado ocorreu")===false))
		{
			$this->net->htmlTags='';
			$this->net->html=$html;
			$this->net->searchLayout($debug);
			$this->net->parse($debug);
			$this->net->fields['numero']=$numero;
			$procura=false;

			//echo "<BR><HR><BR>---------------<pre>";print_r($this->net->htmlTags)."</PRE>";
			$tmp=$this->net->htmlTags["td"]["<table~footer"];
			$obs="";
			for ($a=0; $a<count($tmp); $a++)
			{
				if (($tmp[$a][0]=="EMBARGANTE") || ($tmp[$a][0]=="AGRAVANTE") || ($tmp[$a][0]=="RECORRENTE"))
				{
					$this->net->fields['titulo_autor']=gUcwords($tmp[$a][0]);
					$this->net->fields['autor']=trim($tmp[$a+2][0]);
					if ($tmp[$a+3][0]=="ADVOGADO")
					{
						$adv=trim($tmp[$a+5][0]);
						$adv=explode(" - ",$adv);
						$this->net->fields['autor_advogado']=$adv[0];
						$this->net->fields['autor_advogado_oab']=$adv[1];

					}
				}
				elseif (($tmp[$a][0]=="EMBARGADO") || ($tmp[$a][0]=="AGRAVADO") || ($tmp[$a][0]=="RECORRIDO"))
				{
					$this->net->fields['titulo_reu']=gUcwords($tmp[$a][0]);
					$this->net->fields['reu']=trim($tmp[$a+2][0]);
					if ($tmp[$a+3][0]=="ADVOGADO")
					{
						$adv=trim($tmp[$a+5][0]);
						$adv=explode(" - ",$adv);
						$this->net->fields['reu_advogado']=$adv[0];
						$this->net->fields['reu_advogado_oab']=$adv[1];

					}
				}
				elseif ($tmp[$a][0]=="RELATOR(A)")
				{
					$this->net->fields['relator']=trim($tmp[$a+2][0]);
				}
				elseif ($tmp[$a][0]=="ASSUNTO")
				{
					$this->net->fields['descricao']=trim($tmp[$a+2][0]);
				}
				elseif ($tmp[$a][0]=="PROCESSO")
				{
					$obs[]=trim($tmp[$a+3][0])." - ".trim($tmp[$a+4][0])." - ".trim($tmp[$a+5][0]);
				}
				elseif (substr($tmp[$a][0],0,8)=="VOLUMES:")
				{
					$obs[]=trim($tmp[$a-1][0])." - ".trim($tmp[$a][0])." - ".trim($tmp[$a+1][0]);
				}
				elseif ($tmp[$a][0]=="LOCALIZAÇÃO")
				{
					$this->net->fields['localizacao']=trim($tmp[$a+2][0]);
				}
				elseif ((substr($tmp[$a][0],2,1)=='/') && ((htmlentities($tmp[$a+1][0])=='&Acirc;&nbsp;-&Acirc;&nbsp;') || (htmlentities($tmp[$a+1][0])=='&nbsp;-&nbsp;')))
				{
					// é andamento...
					$desc=$tmp[$a+4][0];
					if ((htmlentities($desc)<>"&nbsp;-&nbsp;") && (htmlentities($desc)<>"&Acirc;&nbsp;-&Acirc;&nbsp;") && (htmlentities($desc)<>" - "))
					{
						$desc=gUcwords($desc);
						$rec="";
						$rec['data']=$this->net->htmlDateFormat($tmp[$a][0]);
						$rec['descricao']=$desc;
						if (stripos($tmp[$a+4][1],"A HREF")!==false)
						{
							// tem anexo...
							$anexo=$tmp[$a+4][1];
							$anexo=substr($anexo,strpos($anexo,'"')+1);
							$anexo=substr($anexo,0,strpos($anexo,'"'));
							$anexo="http://www.stj.jus.br".$anexo;
							//echo "anexo: $anexo";exit;
							$rec['anexo']=$anexo;

						}
						$this->net->records[]=$rec;
					}
				}
			}
			if (is_array($obs))
			{
				$this->net->fields['observacoes']=implode("<br>",$obs);
			}
		}
		//echo "<pre>";print_r($this->net->records);exit;
	}

	function processaSTF($numero, $html)
	{

	}

	function menorData($reg)
	{
		$dataInicio="2399-01-01";
		foreach ($reg as $key=>$val)
		{
			$rec=$reg[$key];
			if (($rec['data']<$dataInicio)&&(substr($rec['data'], 0, 4)<>"20--")&&(substr($rec['data'], 4, 1)=="-"))
				$dataInicio=$rec['data'];
		}
		$this->dataInicio=$dataInicio;
	}


	function destaqueProcessa($html)
	{
		global $usrId, $usrIdd;
		$html=strip_tags($html,'<b>');
		$html=str_replace("Clique aqui para imprimir esta Publicação","",$html);
		$publicacoes=explode('<b>Código:</b>',$html);
		foreach ($publicacoes as $pub)
		{
			$codigo='';
			$processo='';
			$data='';
			$diario='';
			$detalhes='';
			$nome='';
			$teor='';
			$pub=str_replace('<br>','',$pub);
			$linhas=explode("\n",$pub);
			$primeiraLinha=true;
			foreach ($linhas as $linha) {
				$linha=trim($linha);
				if ($linha<>'')
				{
					if ($primeiraLinha)
						$codigo=$linha;
					$primeiraLinha=false;
					$p=strpos($linha,'</b>');
					if ($p!==false)
					{
						$p+=4;
						$campo=trim(substr($linha,0,$p));
						$valor=trim(substr($linha,$p));
					} else
					{
						$campo=$linha;
						$valor=$linha;
					}
					//echo "=> $campo = $valor <br>\n";
					switch ($campo)
					{
						case '<b>Data:</b>':
							$data=htmlFormataData($valor);
							break;

						case '<b>Número de Processo:</b>':
							$processo=formataNumeroProcesso($valor);
							break;

						case '<b>Diário:</b>':
							$diario=$valor;
							break;

						case '<b>Detalhamento:</b>':
							$detalhes=$valor;
							break;

						case '<b>Teor:</b>':
							$teor=''.$valor;
							break;
						case '<b>Nome Pesquisado:</b>':
							$nome=$valor;
							break;

						default:
							$teor.=$linha."<br />";
							break;
					}
				}
			}
//echo "===> Código: $codigo Data: $data Processo: $processo Diario: $diario Detalhe: $detalhe <br>\nTeor: $teor <br><br>\n\n";
			if ($processo<>'')
			{
				$sql="SELECT id FROM processos where numero='$processo' where idd=$usrIdd";
				$rsp=gFastQuery($sql);
				while (!$rsp->EOF)
				{
					$id_processos=$rsp->fields['id'];
					$sql="SELECT * FROM processos_publicacoes WHERE id_processos=$id_processos AND codigo='$codigo' AND idd=$usrIdd";
					$rsPub=gFastQuery($sql);
					if ($rsPub->EOF)
					{
						$detlhes=str_replace("'","‘", str_replace('"',"“", $detalhes));
						$teor=str_replace("'","‘", str_replace('"',"“", $teor));
						// Não existe, então inclui..
						$sql="INSERT INTO processos_publicacoes
								(idd,id_processos,codigo,data,diario,detalhes,teor,nome_encontrado) values
								($usrIdd, $id_processos,'$codigo','$data','$diario','$detalhes','$teor','$nome')";
						gFastQuery($sql);
					}
					$rsp->MoveNext();
				}
			}
		}
		return($html);
	}


	function destaqueBusca($buscarData="")
	{
		global $usrId, $usrIdd;

		$sql="select * from parametros where idd=".$usrIdd;
		$rs=gFastQuery($sql);
		$d_usuario=trim($rs->fields['destaque_usuario']);
		$d_senha=trim($rs->fields['destaque_senha']);

		if (($d_usuario<>'') && ($d_senha<>''))
		{

			$url = "http://gerenciador.destaque.adv.br/default.aspx";

			$ckfile = $this->getCookie($url);
			unlink($ckfile);
			$useragent = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.2 (KHTML, like Gecko) Chrome/5.0.342.3 Safari/533.2';

			$username = $d_usuario;
			$password = $d_senha;

			/**
			    Pegando __VIEWSTATE & __EVENTVALIDATION pra poder fazer o login
			 */
			gLog("==> Destaque: 1. Buscando parâmetros pra fazer o login - idd=$usrIdd");

			$header="";
			$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
			$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
			$header[]="Accept-Encoding: gzip, deflate";
			$header[]="Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
			$header[]="Host: gerenciador.destaque.adv.br";
			$header[]="Reeferer: http://gerenciador.destaque.adv.br/default.aspx";

			$ch = curl_init($url);
			curl_setopt ($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
			curl_setopt($ch, CURLOPT_HTTPHEADER,$header);

			$html = curl_exec($ch);
			$http_code = trim(curl_getinfo($ch, CURLINFO_HTTP_CODE));

			curl_close($ch);

			preg_match('~<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="(.*?)" />~', $html, $viewstate);
			preg_match('~<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="(.*?)" />~', $html, $eventValidation);

			$viewstate = $viewstate[1];
			$eventValidation = $eventValidation[1];

$sql="INSERT INTO
		() VALUES
		()";
dbQuery($sql);

			/**
			 Fazendo o Login
			 */
			gLog("==> Destaque: 2. Fazendo o login ($d_usuario, $d_senha)");

			$header="";
			$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
			$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
			$header[]="Accept-Encoding: gzip, deflate";
			$header[]="Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
			$header[]="Host: gerenciador.destaque.adv.br";
			$header[]="Reeferer: http://gerenciador.destaque.adv.br/default.aspx";

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch, CURLOPT_REFERER, $url);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER,$header);

			// Collecting all POST fields
			$postfields = array();
			$postfields['__VIEWSTATE'] = $viewstate;
			$postfields['__EVENTVALIDATION'] = $eventValidation;
			$postfields['txtUsuario'] = $username;
			$postfields['txtSenha'] = $password;
			$postfields['btoEnviar'] = 'ENTRAR';

			$post_url = '';
			$posts = array();
			foreach ($postfields AS $key=>$value)
				$posts[] = $key.'='.urlencode($value);
			$post_url=implode("&",$posts);
			gLog("==> Destaque: 3. Parâmetros usados no login: ".$post_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_url);

			$html = curl_exec($ch);
			$http_code = trim(curl_getinfo($ch, CURLINFO_HTTP_CODE));

			curl_close ($ch);


			/**
			 Seguindo a página de Login
			 */
			gLog("==> Destaque: 4. Seguindo o login ");

			$header="";
			$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
			$header[]="Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
			$header[]="Accept-Encoding: gzip, deflate";
			$header[]="Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
			$header[]="Host: gerenciador.destaque.adv.br";
			$header[]="Reeferer: http://gerenciador.destaque.adv.br/default.aspx";

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt($ch, CURLOPT_URL, 'http://gerenciador.destaque.adv.br/Main.aspx');
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_REFERER, $url);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, true);

			$html = curl_exec($ch);
			curl_close ($ch);

			preg_match('~<input type="hidden" name="__EVENTTARGET" id="__EVENTTARGET" value="(.*?)" />~', $html, $eventtarget);
			preg_match('~<input type="hidden" name="__EVENTARGUMENT" id="__EVENTARGUMENT" value="(.*?)" />~', $html, $eventargument);
			preg_match('~<input type="hidden" name="__LASTFOCUS" id="__LASTFOCUS" value="(.*?)" />~', $html, $lastfocus);
			preg_match('~<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="(.*?)" />~', $html, $viewstate);
			preg_match('~<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="(.*?)" />~', $html, $eventValidation);

			$eventtarget = $eventtarget[1];
			$eventargument = $eventargument[1];
			$lastfocus = $lastfocus[1];
			$viewstate = $viewstate[1];
			$eventValidation = $eventValidation[1];



			/**
			 Pegando parametros pra poder buscar as publicações
			 */
			$url="http://gerenciador.destaque.adv.br/publicacao/VisualizarPublicacoes.aspx";
			gLog("==> Destaque: 5. Buscando parâmetros pra pesquisar por publicações");
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_REFERER, 'http://gerenciador.destaque.adv.br/Main.aspx');
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$html = curl_exec($ch);
			curl_close ($ch);

			preg_match('~<input type="hidden" name="__EVENTTARGET" id="__EVENTTARGET" value="(.*?)" />~', $html, $eventtarget);
			preg_match('~<input type="hidden" name="__EVENTARGUMENT" id="__EVENTARGUMENT" value="(.*?)" />~', $html, $eventargument);
			preg_match('~<input type="hidden" name="__LASTFOCUS" id="__LASTFOCUS" value="(.*?)" />~', $html, $lastfocus);
			preg_match('~<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="(.*?)" />~', $html, $viewstate);
			preg_match('~<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="(.*?)" />~', $html, $eventValidation);

			$eventtarget = $eventtarget[1];
			$eventargument = $eventargument[1];
			$lastfocus = $lastfocus[1];
			$viewstate = $viewstate[1];
			$eventValidation = $eventValidation[1];


			/**
			 Buscando as publicações
			 */
			gLog("==> Destaque: 6. Obtendo publicacões");
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_REFERER, $url);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$postfields = array();
			$postfields['__EVENTTARGET'] = $eventtarget;
			$postfields['__EVENTARGUMENT'] = $eventargument;
			$postfields['__LASTFOCUS'] = $lastfocus;
			$postfields['__VIEWSTATE'] = $viewstate;
			$postfields['__EVENTVALIDATION'] = $eventValidation;
			$postfields['__ASYNCPOST'] = 'true';
			$postfields['ctl00$ContentPlaceHolder1$btoBuscar'] = 'Buscar';
			$postfields['ctl00$ContentPlaceHolder1$cboData'] = '01/01/1990';
			$postfields['ctl00$ContentPlaceHolder1$cboDiario'] = '0';
			$postfields['ctl00$ContentPlaceHolder1$cboEstado'] = '0';
			$postfields['ctl00$ContentPlaceHolder1$cboFiltro'] = '0';
			$postfields['ctl00$ContentPlaceHolder1$cboStatus'] = '1';
			$postfields['ctl00$ContentPlaceHolder1$txtPesquisa'] = '';
			$postfields['ctl00$ScriptManager1'] = 'ctl00$ContentPlaceHolder1$UpdatePanel5|ctl00$ContentPlaceHolder1$btoBuscar';
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
			$html = curl_exec($ch);
			curl_close ($ch);

			$eventargument='';
			$eventtarget='';
			$lastfocus='';
			$viewstate='';
			$eventValidation='';

			$linhas=explode("|",$html);
			for($a=0; $a<count($linhas); $a++)
			{
				$linha=$linhas[$a];
				if ($linha=='__EVENTTARGET')
					$eventtarget=$linhas[$a+1];
				if ($linha=='__EVENTARGUMENT')
					$eventargument=$linhas[$a+1];
				if ($linha=='__LASTFOCUS')
					$lastfocus=$linhas[$a+1];
				if ($linha=='__VIEWSTATE')
					$viewstate=$linhas[$a+1];
				if ($linha=='__EVENTVALIDATION')
					$eventValidation=$linhas[$a+1];
			}

			$p=strpos($html, 'ctl00$ContentPlaceHolder1$cboData');
			$datas='';
			if ($p!==false)
			{
				$htmlDatas=substr($html,$p);
				$htmlDatas=substr($htmlDatas,strpos($htmlDatas,'<option'));
				$htmlDatas=substr($htmlDatas,0,strpos($htmlDatas,'</select>'));

				$faz=true;
				while ($faz)
				{
					$p=strpos($htmlDatas,'value="');
					if ($p!==false)
					{
						$htmlDatas=substr($htmlDatas,$p+7);
						$p2=strpos($htmlDatas,'"');
						if ($p2!==false)
						{
							$datas[]=substr($htmlDatas,0,$p2);
							$htmlDatas=substr($htmlDatas,$p2);
						} else
						{
							$faz=false;
						}
					} else
					{
						$faz=false;
					}
				}
			}

			gLog("==> Destaque: 7. Datas encontradas: ".implode(" ",$datas));

			if (count($datas)>0)
			{

				$data=$datas[0];
				if ($buscarData<>"")
				{
					if (strpos($buscarData,'-')!==false)
						$buscarData=str_replace('-','/',$buscarData);
					if (strlen($buscarData)<9)
						$buscarData=substr($buscarData,0,6).'20'.substr($buscarData,6);
					$data=$buscarData." 00:00:00";
				}
				$cboStatus='-1';

				/**
				 Mudando a visualização pra poder buscar dados completos
				 */
				gLog("==> Destaque: 8. Processando data: ".$data." (já verificado)");
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_REFERER, $url);
				curl_setopt($ch, CURLOPT_VERBOSE, 1);
				curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				// Collecting all POST fields
				$postfields = array();


				$postfields['__EVENTTARGET'] = 'ctl00$ContentPlaceHolder1$rbtoFormatoRelatorio';
				$postfields['__EVENTARGUMENT'] = $eventargument;
				$postfields['__LASTFOCUS'] = $lastfocus;
				$postfields['__VIEWSTATE'] = $viewstate;
				$postfields['__EVENTVALIDATION'] = $eventValidation;
				$postfields['__ASYNCPOST'] = 'true';
				$postfields['ctl00$ContentPlaceHolder1$cboData'] = $data;
				$postfields['ctl00$ContentPlaceHolder1$cboDiario'] = '0';
				$postfields['ctl00$ContentPlaceHolder1$cboEstado'] = '0';
				$postfields['ctl00$ContentPlaceHolder1$cboFiltro'] = '0';
				$postfields['ctl00$ContentPlaceHolder1$cboStatus'] = $cboStatus;
				$postfields['ctl00$ContentPlaceHolder1$txtPesquisa'] = '';
				$postfields['ctl00$ContentPlaceHolder1$formatoGrid'] = 'rbtoFormatoRelatorio';
				$postfields['ctl00$ContentPlaceHolder1$Publicacao'] = 'rbtTodasPublicacaoSim';
				$postfields['ctl00$ScriptManager1'] = 'ctl00$ContentPlaceHolder1$UpdatePanel8|ctl00$ContentPlaceHolder1$rbtoFormatoRelatorio';
				//gLog("==> Destaque: 8. Parâmetros usados nos publicações: ".http_build_query($postfields));

				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
				$html = curl_exec($ch);
				curl_close ($ch);

//echo "\n\n\n\n\n\n".($html)."\n\n\n\n\n\n";
				$eventargument='';
				$eventtarget='';
				$lastfocus='';
				$viewstate='';
				$eventValidation='';

				$linhas=explode("|",$html);
				for($a=0; $a<count($linhas); $a++)
				{
					$linha=$linhas[$a];
					if ($linha=='__EVENTTARGET')
						$eventtarget=$linhas[$a+1];
					if ($linha=='__EVENTARGUMENT')
						$eventargument=$linhas[$a+1];
					if ($linha=='__LASTFOCUS')
						$lastfocus=$linhas[$a+1];
					if ($linha=='__VIEWSTATE')
						$viewstate=$linhas[$a+1];
					if ($linha=='__EVENTVALIDATION')
						$eventValidation=$linhas[$a+1];
					if ($linha=='ctl00_ContentPlaceHolder1_UpdatePanel11')
					{
						$html=$linhas[$a+1];
						$html=$this->destaqueProcessa($html);
					}
				}


				$cboStatus='0';
				/**
				 Mudando a visualização pra poder buscar dados completos
				 */
				gLog("==> Destaque: 9. Processando data: ".$data." (não verificado)");
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_REFERER, $url);
				curl_setopt($ch, CURLOPT_VERBOSE, 1);
				curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				// Collecting all POST fields
				$postfields = array();

				$postfields['__EVENTTARGET'] = 'ctl00$ContentPlaceHolder1$rbtoFormatoRelatorio';
				$postfields['__EVENTARGUMENT'] = $eventargument;
				$postfields['__LASTFOCUS'] = $lastfocus;
				$postfields['__VIEWSTATE'] = $viewstate;
				$postfields['__EVENTVALIDATION'] = $eventValidation;
				$postfields['__ASYNCPOST'] = 'true';
				$postfields['ctl00$ContentPlaceHolder1$cboData'] = $data;
				$postfields['ctl00$ContentPlaceHolder1$cboDiario'] = '0';
				$postfields['ctl00$ContentPlaceHolder1$cboEstado'] = '0';
				$postfields['ctl00$ContentPlaceHolder1$cboFiltro'] = '0';
				$postfields['ctl00$ContentPlaceHolder1$cboStatus'] = $cboStatus;
				$postfields['ctl00$ContentPlaceHolder1$txtPesquisa'] = '';
				$postfields['ctl00$ContentPlaceHolder1$formatoGrid'] = 'rbtoFormatoRelatorio';
				$postfields['ctl00$ContentPlaceHolder1$Publicacao'] = 'rbtTodasPublicacaoSim';
				$postfields['ctl00$ScriptManager1'] = 'ctl00$ContentPlaceHolder1$UpdatePanel8|ctl00$ContentPlaceHolder1$rbtoFormatoRelatorio';

				//gLog("==> Destaque: 8. Parâmetros usados nos publicações: ".http_build_query($postfields));

				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
				$html = curl_exec($ch);
				curl_close ($ch);

//echo "\n\n\n\n\n\n".($html)."\n\n\n\n\n\n";

				$eventargument='';
				$eventtarget='';
				$lastfocus='';
				$viewstate='';
				$eventValidation='';

				$linhas=explode("|",$html);
				for($a=0; $a<count($linhas); $a++)
				{
					$linha=$linhas[$a];
					if ($linha=='__EVENTTARGET')
						$eventtarget=$linhas[$a+1];
					if ($linha=='__EVENTARGUMENT')
						$eventargument=$linhas[$a+1];
					if ($linha=='__LASTFOCUS')
						$lastfocus=$linhas[$a+1];
					if ($linha=='__VIEWSTATE')
						$viewstate=$linhas[$a+1];
					if ($linha=='__EVENTVALIDATION')
						$eventValidation=$linhas[$a+1];
					if ($linha=='ctl00_ContentPlaceHolder1_UpdatePanel11')
					{
						$html=$linhas[$a+1];
						$html=$this->destaqueProcessa($html);
					}
				}











			}



			/**
			 Logout
			 */
			gLog("==> Destaque: 10. Logout");
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_REFERER, 'http://gerenciador.destaque.adv.br/Main.aspx');
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$html = curl_exec($ch);
			curl_close ($ch);

			unlink($ckfile);

		}






	}


}

// ============================================================================
// Funções diversas
// ============================================================================


function extraiData($txt)
{
	$txt=str_replace("Às","às",$txt);
	$txt=str_replace("Para ","para ",$txt);
	if (stripos($txt, " data ")!==false)
	{
		$d1=explode(" data ", $txt);
		$d2=explode(" às ", $d1[1]);
	} elseif (stripos($txt, "para")!==false)
	{
		$txt=substr($txt, strripos($txt, "para"));
		$d1=explode("para ", $txt);
		$d2=explode(" às ", $d1[1]);
	} elseif (stripos($txt, " (")!==false)
	{
		$txt=trim(substr($txt, stripos($txt, "(")+1));
		$d2=explode(" ", $txt);
	} else
	{
		$txt=substr($txt, strripos($txt, "para"));
		$d1=explode("para ", $txt);
		$d2=explode(" às ", $d1[1]);
	}
	$data=substr(trim($d2[0]), 6, 4)."-".substr(trim($d2[0]), 3, 2)."-".substr(trim($d2[0]), 0, 2);
	$hora=substr(trim($d2[1]), 0, 5).":00";
	$sai=$data." ".$hora;
	return($sai);
}

function htmlLimpaTxt($txt)
{
	$txt=strip_tags($txt);
	$txt=str_replace("'", '', $txt);
	$txt=str_replace(chr(9), '', $txt);
	$txt=str_replace(chr(10), '', $txt);
	$txt=str_replace(chr(13), '', $txt);
	$txt=str_replace(chr(173), '', $txt);
	$txt=trim($txt);

	return($txt);
}

function formataNumeroProcesso($numero)
{
	//00100652420135010009
	//0010065-24.2013.5.01.0009
	$sai=substr($numero,0,7)."-".substr($numero,7,2).'.'.substr($numero,9,4).'.'.substr($numero,13,1).'.'.substr($numero,14,2).'.'.substr($numero,16,4);
	return($sai);
}

function htmlFormataData($dataHora)
{
	$txt=str_replace('&nbsp;',' ',$dataHora);
	$data=substr($dataHora,0,10);
	$sai=$data;
	$sep='-';
	if (strpos($data, '/')!==false)
		$sep='/';
	$dt=explode($sep, $data);
	if ($dt[2]>1800)
	{
		$sai=$dt[2]."-".substr('0'.$dt[1],-2)."-".substr('0'.$dt[0],-2);
	}
	else
	{
		$sai="20".$dt[2]."-".substr('0'.$dt[1],-2)."-".substr('0'.$dt[0],-2);
	}
	if (strlen($dataHora)>10)
		$sai.=substr($dataHora,10);
	$sai=trim($sai);
	return($sai);
}

function limpaTexto($txt)
{
	$sai='';
	for ($a=0; $a<strlen($txt); $a++)
	{
		if ($txt[$a]>=chr(32))
			$sai.=$txt[$a];
	}
	return($sai);
}

function obtemNumeroDoBanco($numero)
{
	$numero=soNumeros($numero);
	$sql="select p.*,o.mascara
		from processos p
		left join orgaos o on p.id_orgaos=o.id
		where p.sonumeros like '%$numero'";
	$rs=gQuery($sql);
	if (!$rs->EOF)
		$numero=campoMascara($numero, $rs->fields['mascara']);
	return($numero);
}

function relacionaProcesso($numero, $processos, $tipo)
{
	global $usrId;
	// Obtém apensos que já estão cadastrados para este processo
	$sql="select p.* from processos p where p.numero='$numero'";
	$rsProc=gQuery($sql);
	if (!$rsProc->EOF)
	{
		$id_processos=$rsProc->fields['id'];

		$sql="select a.*
				from processos p
				left join processos_$tipo a on p.id=a.id_processos
				where p.numero='$numero'";
		$rsA=gQuery($sql);
		$apensosBD=array();
		while (!$rsA->EOF)
		{
			$apensosBD[]=$rsA->fields[('id_processos_'.$tipo)];
			$rsA->MoveNext();
		}
		$processos=explode(chr(10), $processos);

		foreach ($processos as $proc)
		{
			//0097325-21.2010.8.19.0001
			//0298935-74.2009.8.19.0001
			//0035173-39.2012.8.19.0203
			//echo ("=========apenso:  $proc");
			if (strlen($proc)==25)
			{
				// Procura pra ver se já está no BD
				$sql="select * from processos where numero='$proc'";
				$rsTmp=gQuery($sql);
				// Se não existir, cadastra
				if ($rsTmp->EOF)
				{
					$pje=0;
					if (ehPje($proc))
						$pje=1;
					$sql="insert into processos
							(numero,numero_pasta,id_orgaos,id_grupos,id_situacoes,id_pessoas_criou,data_criacao) values
							('$proc','".geraNumeroInterno($rsProc->fields['id_grupos'])."',5,".intval($rsProc->fields['id_grupos']).",".intval($rsProc->fields['id_situacoes']).",$usrId,'".date("Y-m-d H:i:s")."')";
					gQuery($sql);
					$sql="select * from processos where numero='$proc'";
					$rsTmp=gQuery($sql);
				}
				if (!in_array($proc, $apensosBD))
				{
					$sql="insert into processos_$tipo (id_processos,id_processos_$tipo) values ($id_processos,".$rsTmp->fields['id'].")";
					gQuery($sql);
				}
			}
		}
	}
}

function campoTamanho($mascara)
{
	$tam=strlen(str_replace(".", '', str_replace('/', '', str_replace("-", '', $mascara))));
	return($tam);
}

function soNumeros($valor)
{
	$nValor='';
	// Só pega os números
	for ($a=0; $a<strlen($valor); $a++)
	{
		$v=$valor[$a];
		if ((intval($v)>0)||(($v=='0')&&($nValor<>'')))
		{
			$nValor.=$v;
		}
	}
	return($nValor);
}

function campoMascara($valor, $mascara)
{
	$valor=soNumeros($valor);
	//$valor=str_replace("/","",str_replace("-","",str_replace(".","",$nValor)));
	$valor=substr("0000000000000000000000000000000".$valor, -intval(campoTamanho($mascara)));
	$cnt=0;
	$sai='';
	for ($a=0; $a<strlen($mascara); $a++)
	{
		if (($mascara[$a]<>'.')&&($mascara[$a]<>'/')&&($mascara[$a]<>'-'))
		{
			$sai.=$valor[$cnt];
			$cnt++;
		} else
			$sai.=$mascara[$a];
	}
	return($sai);
}

function campoObtemParametros($campos)
{
	$sai="";
	if (strpos($campos, chr(13))===false)
	{
		$flds[]=$campos;
	}
	else
	{
		$flds=explode(chr(13), $campos);
	}
	foreach ($flds as $campo)
	{
		$formato=explode(":", $campo);
		$s['fieldLabel']=$formato[0];
		$s['name']=$formato[1];
		$s['type']=$formato[2];
		$s['mask']=$formato[3];
		$tam=campoTamanho($s['mask']);
		$s['maxLength']=$tam;
		$s['allowBlank']="false";

		$sai[]=$s;
	}
	return($sai);
}

function campoFormata($campo)
{
	$cmps='';
	foreach ($campo as $key=>$value)
	{
		if ($key<>'mask')
			$cmps[]=$key.": ".$value;
	}
	$sai="{".implode(";", $cmps)."}";
	return($sai);
}

function geraNumeroInterno($id_grupos, $numeroSugerido=0)
{
	$numeroFormatado="";
	$sql="select * from grupos where id=".intval($id_grupos);
	$rs=gQuery($sql);
	$sigla=trim(strtoupper($rs->fields['sigla']));
	if ($rs->fields['autonumerar']==1)
	{
		$sql="select * from numeracao where sigla='$sigla'";
		$rs=gQuery($sql);
		if ($rs->EOF)
		{
			$sql="insert into numeracao (sigla,ultimo_numero) values ('$sigla',0)";
			gQuery($sql);
			$numero=1;
			if ($sigla<>"")
				$numeroFormatado=$sigla."-";
			$numeroFormatado.=str_pad($numero, 6, "0", STR_PAD_LEFT);
		} else
		{
			$ok=false;
			$numero=$rs->fields['ultimo_numero'];
			while (!$ok)
			{
				$numero=$numero+1;
				if ($sigla<>"")
					$numeroFormatado=$sigla."-";
				else
					$numeroFormatado="";
				$numeroFormatado.=str_pad($numero, 6, "0", STR_PAD_LEFT);
				$sql="select * from processos where numero_pasta='$numeroFormatado'";
				$rsn=gQuery($sql);
				if ($rsn->EOF)
					$ok=true;
			}
		}
		if ($numeroSugerido>0)
		{
			$numeroFormatado="";
			if ($sigla<>"")
				$numeroFormatado=$sigla."-";
			$numeroFormatado.=str_pad($numeroSugerido, 6, "0", STR_PAD_LEFT);
			if ($numeroSugerido>$numero)
				$numero=$numeroSugerido;
		}
		$sql="update numeracao set ultimo_numero=$numero where sigla='$sigla'";
		gQuery($sql);
	} else
	{
		if ($numeroSugerido>0)
			$numeroFormatado=str_pad($numeroSugerido, 6, "0", STR_PAD_LEFT);
	}
	return($numeroFormatado);
}




function rip_tags($string) {

	 // ----- remove HTML TAGs -----
	 $string = preg_replace ('/<[^>]*>/', ' ', $string);

	 // ----- remove control characters -----
	 $string = str_replace("\r", '', $string);    // --- replace with empty space
	 $string = str_replace("\n", ' ', $string);   // --- replace with space
	 $string = str_replace("\t", ' ', $string);   // --- replace with space

	 // ----- remove multiple spaces -----
	 $string = trim(preg_replace('/ {2,}/', ' ', $string));

	 return $string;

}



function strip_html_tags( $text )
{
	$text = preg_replace(
		array(
          // Remove invisible content
			'@<head[^>]*?>.*?</head>@siu',
			'@<style[^>]*?>.*?</style>@siu',
			'@<script[^>]*?.*?</script>@siu',
			'@<object[^>]*?.*?</object>@siu',
			'@<embed[^>]*?.*?</embed>@siu',
			'@<applet[^>]*?.*?</applet>@siu',
			'@<noframes[^>]*?.*?</noframes>@siu',
			'@<noscript[^>]*?.*?</noscript>@siu',
			'@<noembed[^>]*?.*?</noembed>@siu'
			),
		array(
			' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '
			),
		$text );
	return strip_tags( $text );
}

// Obtém valor com parâmetros de busca onde o ínicio é $pre e o final é $pos
// Ignora qualquer TAG HTML no meio
function getPos($html, $pre, $pos, $index=0)
{
	$sai='';
	$p1=stripos($html,$pre);
	if ($p1!==false)
	{
		$p1=$p1+strlen($pre);
		$html=substr($html,$p1);
		$p2=stripos($html,$pos);
		for ($i=0; $i<$index; $i++)
		{
			$p2=stripos($html,$pos, $p2+1);
		}
		if ($p2!==false)
		{
			$sai=substr($html,0,$p2);
			$sai=strip_tags($sai);
			$sai=autoencode(html_entity_decode($sai));
			$sai=trim($sai);
		}
	}
	return($sai);
}


function strip_tags_content($text, $tags = '', $invert = FALSE) {

  preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
  $tags = array_unique($tags[1]);

  if(is_array($tags) AND count($tags) > 0) {
	 if($invert == FALSE) {
		return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
	 }
	 else {
		return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
	 }
  }
  elseif($invert == FALSE) {
	 return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
  }
  return $text;
}


function superTrim($txt)
{
	return(trim(str_replace(chr(194).chr(160),'',$txt)));
}




























class Orgao extends gBrowserJuridico
{
	public $urls;
	public $title;
	public $numero = '';
	public $campos;
	public $andamentos;

	function __construct($json = "")
	{
		$this->jarr=cssDecode($json);
		$this->numero = $this->jarr['numero'];
		if (isset($this->jarr['title']))
			$this->title = $this->jarr['title'];
		if (isset($this->jarr['url']))
			$this->urls[] = $this->jarr['url'];
		jLog('[ '.$this->title.' ] --------------------------------- INICIO', $this->session,'infor');
		parent::__construct($json);
	}

	function __destruct()
	{
		jLog('[ '.$this->title.' ] --------------------------------- FINAL', $this->session,'infor');

	}

	function mostraResultado()
	{
		echo "<br><b><i>Campos obtidos:</i></b><br><pre>";
		print_r($this->campos);
		echo "</pre><br><br><b><i>Andamentos:</i></b><br><pre>";
		print_r($this->andamentos);
		echo "</pre>";
		exit;
	}
}






class OrgaoTRTPJe extends Orgao
{
	public $trt       = 'trt1';
	public $baseUrl   = '';
	public $num_pje   = '';
	public $grau_pje  = '';
	public $viewState = '';

	function __construct($json)
	{
		$this->jarr=cssDecode($json);
		if (isset($this->jarr['url']))
			$this->urls[] = $this->jarr['url'];
		$result = parse_url($this->urls[0]);
		$this->baseUrl = $result['scheme']."://".$result['host'];
		if (isset($this->jarr['baseUrl']))
			$this->baseUrl = $this->jarr['baseUrl'];
		if (isset($this->jarr['title']))
			$this->title = $this->jarr['title'];
		if (isset($this->jarr['trt']))
			$this->trt = $this->jarr['trt'];

		$css = "{debug: true; captcha: true; captchaField: fPP:j_id146:verifyCaptcha; captchaError: Caracteres digitados de forma incorreta; notFound: Sua pesquisa não encontrou nenhum processo disponível; getHtmlObject: true}";
		parent::__construct($css);
		$this->timeout = 30;
	}

	function obtemCaptcha($numero = '')
	{
		$sai = true;
		if ($numero == '')
			$numero = $this->numero;
		else
			$this->numero = $numero;

		jLog($numero.' - Abrindo URL: '.$this->urls[0], $this->session);


		$erroEncontrado = false;

		for ($a=0; $a<4; $a++)
		{
			if ($a==0 || $erroEncontrado)
			{
				$this->setUrl($this->urls[0]);

				// 01 - Obtem cookie
				if ($this->get('','Obtendo Cookie'))
				{
					// Verifica se existe javascript para chamar próxima página....
					if (stripos($this->html, 'decodeURIComponent'))
					{
						// 02 - Calcula javascript
						$html = $this->javascriptRedirect($this->html, $this->baseUrl, true);
					}

					// 03 - Depois pega o conteúdo da página com o captcha atualizado (já com o cookie)
					$this->post = '';
					$this->post['consultaProcFormFormatForm'] = 'consultaProcFormFormatForm';
					switch ($this->trt)
					{
						case "trt1":
							$this->post['j_id62'] = "true";
							break;
						case "trt4":
							$this->post['j_id51'] = "true";
							break;
						case "trt18":
							$this->post['j_id59'] = "true";
							break;
						default:
							$this->post['j_id52'] = 'true';
					}
					$this->post['numeroFormatDecorate:numero_format'] = $numero;
					$this->post['consultarNumFormat']                 = 'Pesquisar';
					$this->post['javax.faces.ViewState']              = 'j_id1';
					$newUrl = $this->urls[0];

					// $cookie = file_get_contents($this->cookieFile);
					// $cookie = explode("\n", $cookie);
					// foreach ($cookie as $lin)
					// {
					// 	if (strpos($lin,'JSESSIONID')!==false)
					// 	{
					// 		$lin = explode("\t", $lin);
					// 		$newUrl.=';jsessionid='.$lin[6];
					// 	}
					// }
					//
					$this->setUrl($newUrl);

					$this->useHttpBuildQuery = false;
					$this->referer = $this->urls[0];
					$this->headers[] = "Origin: consultapje.".$this->trt.'.jus.br';
					$this->headers[] = "Upgrade-Insecure-Requests: 1";
					if ($this->post($newUrl, 'Informando número do processo', true))// auto-redirect
					{
						$this->useHttpBuildQuery = true;
						if (strpos($this->redirectedTo,'ConsultaProcessual')!==false)
						{
							$this->cid = substr($this->redirectedTo, strpos($this->redirectedTo,'cid')+4);
							if ($this->get('https://consultapje.trt1.jus.br/consultaprocessual/pages/consultas/ListaProcessos.seam?numero_unic='.$numero.'&cid='.$this->cid, 'Forçando página', true))
							{
								if (stripos($this->html, 'decodeURIComponent'))
								{
									// 02 - Calcula javascript
									$html = $this->javascriptRedirect($this->html, $this->baseUrl, true);
								}
							}
						}
						$this->getHtmlObject();

					} else
					{
						// Obtendo o cid
						$this->useHttpBuildQuery = true;
						$sai = false;
						$erroEncontrado = false;
						$this->errorCode 		= '600';
						$this->errorMessage 	= $this->curl_error;
					}

					$this->setUrl($this->urls[0]);

					// A primeira do período sempre retorna um redirecionamento pra página errorSession
					// Então é necessário chamar novamente

					if (strpos($this->redirectedTo,'errorSession.seam')!==false)
					{
						jLog("Erro na sessão, tentando de novo", $this->getSessionNumber(), 'aviso');
						$this->killCookie();
						$erroEncontrado = true;
					}
					if (trim($this->html) == '')
					{
						jLog("HTML vazio, tentando de novo", $this->getSessionNumber(), 'aviso');
						$this->killCookie();
						$erroEncontrado = true;
					}

				} else
				{
					$sai = false;
					$erroEncontrado = false;
					$this->errorCode 		= '600';
					$this->errorMessage 	= $this->curl_error;
				}

			}
		}

		if ($sai)
		{
			// Obtendo o cid
			$this->cid       = substr($this->redirectedTo, strpos($this->redirectedTo,'cid')+4);

			// Obtém campos dinâmicos referentes ao processo informado
			$this->viewState = $this->getField('id-input', 'javax.faces.ViewState');
			$this->num_pje   = $this->getField('id-input', 'num_pje');
			$this->grau_pje  = $this->getField('id-input', 'grau_pje');

			// Depois obtém o Captcha
			$captcha         = $this->getCaptcha('captcha', 'jpeg', true);
		}

		return($sai);

	}

	function obtemDadosDoProcesso()
	{
		$sai = false;
		// Submete dados do processo inclusive com o captcha
		$this->post                                           = '';
		$this->post['consultaProcFormForm']                   = 'consultaProcFormForm';
		$this->post['j_id57']                                 = 'true';
		$this->post['consultaProcFormDecorate:verifyCaptcha'] = $this->ocr;
		$this->post['consultar']                              = 'Consultar';
		$this->post['p_ano']                                  = '';
		$this->post['p_vara']                                 = '';
		$this->post['p_nome']                                 = '';
		$this->post['p_ano2']                                 = '';
		$this->post['p_vara2']                                = '';
		$this->post['dt_autuacao']                            = '';
		$this->post['numero_unic']                            = '';
		$this->post['num_pje']                                = $this->num_pje;
		$this->post['grau_pje']                               = $this->grau_pje;
		$this->post['javax.faces.ViewState']                  = $this->viewState;

		switch ($this->trt)
		{
			case "trt1":
				$newUrl = "https://consultapje.trt1.jus.br/consultaprocessual/pages/consultas/CaptchaProcesso.seam?num_pje=".$this->num_pje."&grau_pje=".$this->grau_pje."&cid=".$this->cid;
				break;
		}
		$this->useHttpBuildQuery = false;
		$this->post($newUrl, 'Obtendo dados do processo', true); // auto-redirect
		$this->useHttpBuildQuery = true;
		if (strpos($this->html, 'Processo PJe:')!==false)
		{
			foreach ($this->htmls as $html)
			{
				if (strpos($html, 'Processo PJe:')!==false)
				{

					$this->html = $html;
					file_put_contents("/tmp/tmp.html", $html);
					$this->getHtmlObject();
					$sai = true;
				}
			}

		} else {
			$sai = false;
			if (strpos($this->html, 'incorrect response')!==false)
			{
				jLog("Captcha incorreto", $this->getSessionNumber(), 'erro');
				$this->errorCode = "602";
				$this->errorMessage = "Captcha incorreto!";
			} elseif (strpos($this->html, 'Digite o c&oacute;digo de confirma&ccedil;&atilde;o')!==false)
			{
				jLog("Captcha solicitado novamente", $this->getSessionNumber(), 'erro');
				$this->errorCode = "603";
				$this->errorMessage = "Captcha solicitado novamente!";
			} else {
				jLog("Erro ao obter o captcha", $this->getSessionNumber(), 'erro');
				$this->errorCode = "604";
				$this->errorMessage = "Erro ao obter o captcha!";
			}
		}
		return($sai);
	}

	function buscaProcesso($numero = '')
	{
		$sai = true;
		//$this->killCookie();
		if ($this->obtemCaptcha($numero))
		{
			// // Interpreta o captcha
			$this->ocr = $this->ocr('', 'digits');

			if ($this->ocr <> '')
			{
				$this->obtemDadosDoProcesso($numero);

			} else {
				$this->errorCode = "601";
				$this->errorMessage = "Captcha não encontrado!";
				$sai = false;
			}
		} else
		{
			if (strpos($this->errorMessage,'timed out')!==false)
			$this->errorCode 		= '600';
			$this->errorMessage 	= 'O site do tribunal demorou muito para responder.';

		}
		//$this->endSession(true);

		return($sai);
	}


	function padronizaDados($numero = '')
	{
		$sai = true;
		if ($numero == '')
			$numero = $this->numero;
		else
			$this->numero = $numero;

		$numero = $this->numero;
		jLog($numero.' - Padronizando dados obtidos...', $this->session);

		if (strpos($this->html, 'id="consultaProcessos"')===false)
		{
			// Se algum dos campos solicitados não foi encontrado, registra no log e não retorna nada...
			jLog($numero.' - Andamentos não encontrados no HTML!', $this->session, 'error');
			$this->errorCode = '610';
			$this->errorMessage = 'Andamentos não encontrados no HTML';
			$this->lastError=$this->errorCode." - ".$this->errorMessage;
			$sai = false;
		} else
		{

			$this->campos = '';
			$this->campos['numero']=$numero;
			$this->campos['autor']=$this->getField('id', 'partesReclamanteFieldDecorate:j_id149:childs','stringFormatted');;
			$this->campos['reu']=$this->getField('id', 'partesReclamadaFieldDecorate:j_id175:childs','stringFormatted');;

			$andamentosData = $this->getField('id-inc', 'consultaProcessos:0:j_id253','date',18);
			$andamentosDescricao = $this->getField('id-inc', 'consultaProcessos:0:j_id257','string', 18);

			for ($a=0; $a<count($andamentosData); $a++)
			{
				$rec='';
				$rec['data']=$andamentosData[$a];
				$rec['descricao']=$andamentosDescricao[$a];
				$this->andamentos[]=$rec;
			}
			jLog($numero.' - Padronizado! (total: '.count($this->andamentos).')', $this->session);
		}
		return($sai);
	}

}








class OrgaoTJPJe extends Orgao
{
	function __construct($json)
	{
		$this->jarr=cssDecode($json);
		if (isset($this->jarr['url']))
			$this->urls[] = $this->jarr['url'];
		if (isset($this->jarr['title']))
			$this->title = $this->jarr['title'];
		$css = "{debug: true; captcha: true; captchaField: fPP:j_id146:verifyCaptcha; captchaError: Caracteres digitados de forma incorreta; notFound: Sua pesquisa não encontrou nenhum processo disponível; getHtmlObject: true}";
		parent::__construct($css);

	}
	function buscaProcesso($numero = '')
	{
		$sai = true;
		if ($numero == '')
			$numero = $this->numero;
		else
			$this->numero = $numero;

		jLog($numero.' - Abrindo URL: '.$this->urls[0], $this->session);
		$this->post['AJAX:EVENTS_COUNT']='1';
		$this->post['AJAXREQUEST']='_viewRoot';
		$this->post['autoScroll']='';
		$this->post['fPP']='fPP';
		$this->post['fPP:Decoration:estadoComboOAB']='org.jboss.seam.ui.NoSelectionConverter.noSelectionValue';
		$this->post['fPP:Decoration:j_id142']='';
		$this->post['fPP:Decoration:numeroOAB']='';
		$this->post['fPP:consultaSearchFields']='true';
		$this->post['fPP:dnp:nomeParte']='';
		$this->post['fPP:dpDec:documentoParte']='';
		$this->post['fPP:j_id103:classeProcessualProcessoHidden']='';
		$this->post['fPP:j_id93:nomeAdv']='';
		$this->post['fPP:numProcessoDecoration:numProcesso']=$numero;
		$this->post['fPP:searchProcessos']='fPP:searchProcessos';
		$this->post['tipoMascaraDocumento']='on';

		if (!$this->getData($this->urls[0]))
		{
			$this->lastError=$this->errorCode." - ".$this->errorMessage;
			$sai = false;
		}
		return($sai);
	}

	function padronizaDados($numero = '')
	{
		$sai = true;
		if ($numero == '')
			$numero = $this->numero;
		else
			$this->numero = $numero;

		$numero = $this->numero;
		jLog($numero.' - Padronizando dados obtidos...', $this->session);

		$value['numero']			 = $this->getField('java-value-class', 'j_id62:processoTrfViewView:j_id68:j_id69');
		$value['data_distribuicao']	 = $this->getField('java-value-class', 'j_id62:processoTrfViewView:j_id80:j_id81', 'date');
		$value['classe_judicial']	 = $this->getField('java-value-class', 'j_id62:processoTrfViewView:j_id91:j_id92', 'stringFormatted');
		$value['assunto']			 = $this->getField('java-value-class', 'j_id62:processoTrfViewView:j_id102:j_id103','stringFormatted');
		$value['orgao_julgador']	 = $this->getField('java-value-class', 'j_id62:processoTrfViewView:j_id114:j_id115');
		$value['polo_ativo']		 = $this->getField('id-inc', 'j_id62:processoPartesPoloAtivoResumidoList:0:j_id190','stringFormatted',43);
		$value['polo_passivo']		 = $this->getField('id-inc', 'j_id62:processoPartesPoloPassivoResumidoList:0:j_id239','stringFormatted',45);
		$value['andamentos']		 = $this->getField('id-inc', 'j_id62:processoEvento:0:j_id293', 'string', 22);

		if ($this->elementNotFound)
		{
			// Se algum dos campos solicitados não foi encontrado, registra no log e não retorna nada...
			jLog($numero.' - Elemento não encontrado no HTML!', $this->session, 'error');
			$this->errorCode = '600';
			$this->errorMessage = 'Elemento não encontrado no HTML';
			$this->lastError=$this->errorCode." - ".$this->errorMessage;
			$sai = false;
		} else
		{
			$autores           = '';
			$autores_advogados = '';
			$reus              = '';
			$reus_advogados    = '';
			$captionAtivo      = '';
			$captionPassivo    = '';

			foreach ($value['polo_ativo'] as $polo)
			{
				$polo = str_replace('. ','', $polo);
				if (strpos($polo, '(Advogado)')!==false)
				{
					$autores_advogados[]=str_replace('(Advogado)','',$polo);
				} else
				{
					if (strpos($polo,'(')!==false && $captionAtivo == '')
					{
						$captionAtivo = str_replace(')','',substr($polo, strpos($polo, '(')+1));
						$polo = str_replace("($captionAtivo)", '', $polo);
					}
					$autores[]=$polo;
				}
			}

			foreach ($value['polo_passivo'] as $polo)
			{
				$polo = str_replace('. ','', $polo);
				if (strpos($polo, '(Advogado)')!==false)
				{
					$reus_advogados[]=str_replace('(Advogado)','',$polo);
				} else
				{
					if (strpos($polo,'(')!==false && $captionPassivo == '')
					{
						$captionPassivo = str_replace(')','',substr($polo, strpos($polo, '(')+1));
						$polo = str_replace("($captionPassivo)", '', $polo);
					}
					$reus[]=$polo;
				}
			}

			$this->campos = '';
			$this->campos['numero']=$numero;
			$this->campos['data_inicio']=$value['data_distribuicao'];
			$this->campos['descricao']=$value['assunto'];
			$this->campos['orgao_julgador_atual']=$value['orgao_julgador'];
			$this->campos['autor']=implode("\n",$autores);
			$this->campos['autor_advogado']=implode("\n",$autores_advogados);
			$this->campos['reu']=implode("\n",$reus);
			$this->campos['reu_advogado']=implode("\n",$reus_advogados);
			$this->campos['titulo_autor']=$captionAtivo;
			$this->campos['titulo_reu']=$captionPassivo;

			$cpfcnpj = '';
			if (stripos($autores[0],'cpf:')!==false)
			{
				$cpfcnpj = substr($autores[0],stripos($autores[0],'cpf:')+5);
			}
			if (stripos($autores[0],'cnpj:')!==false)
			{
				$cpfcnpj = substr($autores[0],stripos($autores[0],'cnpj:')+6);
			}
			$cpfcnpj = str_replace('.', '',str_replace(' ','',str_replace('-','',$cpfcnpj)));
			$this->campos['autor_cpfcnpj'] = $cpfcnpj;
			$this->campos['cpfcnpj'] = $cpfcnpj;

			$cpfcnpj = '';
			if (stripos($reus[0],'cpf:')!==false)
			{
				$cpfcnpj = substr($reus[0],stripos($reus[0],'cpf:')+5);
			}
			if (stripos($reus[0],'cnpj:')!==false)
			{
				$cpfcnpj = substr($reus[0],stripos($reus[0],'cnpj:')+6);
			}
			$cpfcnpj = str_replace('.', '',str_replace(' ','',str_replace('-','',$cpfcnpj)));
			$this->campos['reu_cpfcnpj'] = $cpfcnpj;

			foreach ($value['andamentos'] as $and)
			{
				//27/07/2016 12:42:19 - Conclusos para despacho
				$data = substr($and, 0, 19);
				$desc = trim(substr($and, 22));
				$rec='';
				$rec['data']=$this->formatDate($data);
				$rec['descricao']=$this->format($desc,'stringFormatted');
				$this->andamentos[]=$rec;
			}
			jLog($numero.' - Padronizado!', $this->session);
		}
		return($sai);
	}
}





?>
