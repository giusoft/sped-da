<?php


/** Transforma uma string CSS em um array
 * @author	giuliano
 * @version	1.0 12-06-2009 16:21
 * @param string $css String CSS
 * @param boolean $format Remove \n e \t ?
 * @return mixed $mtz Descrição da variável
 */
function cssNetDecode($css)
{
	$sai="";
	//$css=utf8_encode($css);
	if (strpos($css,"[")!==false)
	{
		$b=strpos($css,"[")+1;
		for($a=$b; $a<strlen($css); $a++)
		{
			if ($css[$a]=="{") $css[$a]="^";
			if ($css[$a]=="}") $css[$a]="`";
			if ($css[$a]=="]") break;
		}
	}

	//echo "JSON: $css <br>";

	$items="";
	if (strpos($css,"items:")!==false)
	{
		$i=explode("items:",$css);
		if (substr(trim($i[1]),0,1)=="'")
		{
			$items=substr(trim($i[1]),1);
			$ini=strpos($items,"'");
			$fim=strrpos($items,"'");
			$css=$i[0].substr($items,$ini+2)."}";
			$items=substr($items,0,$ini);
			$items=str_replace("\"","'",$items);
			//echo "ini $ini fim $fim css: $items<br><Br>";
			//gLog(">>>>>>> $items");
		}
	}
	$css=str_replace("{","",$css);
	$css=str_replace("}","",$css);
	//echo "JSON: $css - items: $items<br>";

	//echo "css: $css <br><br>\n\n<pre>";
	//$array= preg_split("/[;]*\\\"([^\\\"]+)\\\"[;]*|" . "[;]*'([^']+)'[;]*|" . "[;]+/", $css, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	//$array= preg_split("/;+/", $css, 0, PREG_SPLIT_NO_EMPTY);
	$array=explode(";",$css);

	foreach ($array as $value)
	{
		$value=trim($value);
		$key=substr($value,0,strpos($value,":"));
		$value=trim(str_replace("'","",substr($value,strpos($value,":")+1)));
		$new=array($key,$value);
		if (trim($new[0])<>"")
		{
			$val=trim($new[1]);
			$val=str_replace("^","{",$val);
			$val=str_replace("`","}",$val);
			if (substr($val,0,1)=="[")
			{
				if (strpos($val,"|")!==false)
					$val=explode("|",substr($val,1,strlen($val-3)));
			}
			$sai[trim($new[0])]=$val;
		}
	}
	if ($items<>"")
		$sai['items']=trim($items);
	foreach ($sai as $key=>$item)
	{
		//echo "($key)$item <br>";
		if (substr($item,0,10)=="--(encode)")
			$sai[$key]=base64_decode(substr($item,10));
	}

	return ($sai);
}



/** Lê um site remoto e retorna seu conteúdo HTML
 * @author	giuliano
 * @version	1.0 10-10-2012 08:22
 */
class gNetLayout
{
	public $layout='';
	public $name='';
	public $starts='';
	public $ends='';


	function __construct($json)
	{
		$mtz=cssNetDecode($json);
		$this->name=$mtz['name'];
	}

	/** Adiciona UM CAMPO à definição de layout
	 *
	 * @param type $json Exemplo: {name: numero; tags: {td: tdCorpoForm; td: 1}}
	 */
	function add($json)
	{

		// Removendo "tags" do JSON
		$semTags=$json;
		$p1=strrpos($json,"{");
		$p2=strpos($json,"}");
		if (($p1>0) && ($p2>0))
		{
			$elems='';
			$tags=explode(";",substr($json,$p1+1,$p2-$p1-1));
			foreach ($tags as $tag)
			{
				$el=explode(":",$tag);
				for ($a=0; $a<count($el); $a++)
				{
					$el[$a]=trim($el[$a]);
				}
				$elems[]=$el;
			}
			$semTags=substr($json,0,$p1-1)."}";
		}
		$mtz=cssDecode($semTags);
		//$this->layout[$mtz['name']]=$elems;
		$name=$mtz['name'];
		unset($mtz['name']);
		$this->layout[$name]=$mtz;
	}

	function getName()
	{
		return ($this->name);
	}

	function getNames()
	{
		return (explode("|",$this->name));
	}

	/** Retorna com os campos do layout em formato array
	 *
	 * @return type Layout
	 */
	function getLayout()
	{
		return ($this->layout);
	}
}

class gNet
{
	public $layoutObj='',$layoutId,$layoutName, $layoutStarts,$layoutEnds;
	public $json='',$param='';
	public $html='', $htmlTags='';
	public $tratamento,$tratamentoId,$tratamentoTipo;
	public $fields='';
	public $cookieFile="";

	function __construct($param="")
	{
		$this->cookieFile=$_SESSION['gCookie'];
		$this->json=$param;
		$this->param=cssNetDecode($param);
	}

	/** Adiciona um layout para identificação de campos dentro de uma página HTML
	 *
	 * @param type $layout Objeto gNetLayout
	 */
	function addLayout($layout)
	{
		$this->layoutObj[]=$layout;
	}

	function getFields()
	{
		return($this->fields);
	}


	function htmlDateFormat($data)
	{
		$sai=$data;
		$sep='-';
		if (strpos($data,'/')!==false)
			$sep='/';
		$data=trim(str_replace(chr(194).chr(160),'',$data));
		$dt=explode($sep,$data);
		if ($dt[0]<99)
		{
			$dt[0]=substr(trim($dt[0]),-2);
			$dt[1]=trim($dt[1]);
			$dt[2]=trim($dt[2]);
			if ($dt[2]>1800)
			{
				$sai=$dt[2]."-".$dt[1]."-".$dt[0];
			} else
			{
				$sai="20".$dt[2]."-".$dt[1]."-".$dt[0];
			}
		}
		return($sai);
	}

	function htmlTextClean($txt)
	{
		$txt=strip_tags($txt);
		$txt=str_replace("'",' ',$txt);
		$txt=str_replace("~i","í",$txt);
		if (substr($txt,0,1)==chr(10))
			$txt=substr($txt,1);
		$txt=trim($txt);
		return($txt);
	}

	function prepareHtml()
	{
		$this->html=mb_convert_encoding($this->html, 'HTML-ENTITIES', "UTF-8");
		$this->html=str_replace("&iacute;","~i",$this->html);
		$this->html=str_replace("í;","~i",$this->html);
		$this->html=html_entity_decode($this->html,ENT_NOQUOTES,'UTF-8');
		$this->html=str_replace(chr(9),'',$this->html);
		$this->html=str_replace(chr(13),'',$this->html);
		$this->html=str_replace("<br>",chr(10),$this->html);
		$this->html=str_replace("<br/>",chr(10),$this->html);
		$this->html=str_replace("<br />",chr(10),$this->html);
		while(substr_count($this->html,"<script") != 0)
		{
			$p1=stripos($this->html,"<script");
			$p2=stripos($this->html,"</script>");
			$this->html=substr($this->html,0,($p1-1)).substr($this->html,($p2+9));
		}
		while(substr_count($this->html,"<style") != 0)
		{
			$p1=stripos($this->html,"<style");
			$p2=stripos($this->html,"</style>");
			$this->html=substr($this->html,0,($p1-1)).substr($this->html,($p2+8));
		}
		while(substr_count($this->html,"  ") != 0){
			$this->html = str_replace("  "," ",$this->html);
		}
		$this->html = str_replace(chr(10).' ',chr(10),$this->html);
		while(substr_count($this->html,chr(10).chr(10)) != 0){
			$this->html = str_replace(chr(10).chr(10),chr(10),$this->html);
		}

	}

	function setCookie($ckfile)
	{
		$this->cookieFile=$ckfile;
	}

	function getCookie()
	{
		if (trim($this->cookieFile)<>"")
			$ck=trim($this->cookieFile);
		else
			$ck=$_SESSION['gCookie'];
		return ($ck);
	}

	function getCaptcha($url,$style=0)
	{
		global $out,$http_tmp,$usrId,$gPathTmp;
		$cookie_file = $this->getCookie();
		// Define parametros
		$my_site = $http_tmp;

		// Pra ficar igual ao JBoss
		$mt=str_replace('.','',round(microtime(true),3));

		// Pega cookie em $url[0]
		$ch = curl_init ($url[0]);
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec ($ch);
		curl_close($ch);
		// Obtém imagem, e salva localmente
		$ch = curl_init ();
		if (strpos($url[1],"https")!==false)
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt ($ch, CURLOPT_URL, $url[1]);
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_file);

		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec ($ch); // the real thing

		$img_file_name =  "imagem-$usrId.gif";
		$img_data = imagecreatefromstring($output);
		imagegif($img_data, $gPathTmp.$img_file_name);
		$img_link = $http_tmp."/".$img_file_name;
		$sai['link']=$img_link;
		return($sai);
	}

	/** Abre um endereço Web e retorna página
	 *
	 * @param type $url URL
	 * @return type string Página em formato HTML, sem &nbsp;
	 */
	function siteOpen($url,$post='',$cookie=false,$captcha=false, $json=false)
	{
		global $usrId;
		$ok=true;
		$usrAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:36.0) Gecko/20100101 Firefox/36.0";
		if (is_array($url))
		{
			gLog("---> Abrindo URL[0]: ".$url[0]);
			gLog("---> Abrindo URL[1]: ".$url[1]);
		} else
			gLog("---> Abrindo URL: $url");
		if ($this->html=='')
		{
			if ($captcha)
			{
				$cookie_file = $this->getCookie();
//echo "<pre>";print_r($_SESSION);echo "</pre><br>";
//echo $url."<br> Cookie: $cookie_file<br><pre>".file_get_contents($cookie_file)."</pre><br><hr><pre>";print_r($post);echo "</pre><br>";

				$ch = curl_init();
				curl_setopt($ch,CURLOPT_URL,$url);
				if (is_array($post))
				{
					unset($post['jsessionid']);
					if (isset($post['javax_faces_ViewState']))
						$post['javax.faces.ViewState']=$post['javax_faces_ViewState'];
					unset($post['javax_faces_ViewState']);
					curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
				}
				if (strpos($url,"https")!==false)
					curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch,CURLOPT_COOKIEFILE, $cookie_file);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_USERAGENT, $usrAgent);


				$ok= curl_exec($ch);
				$this->html =$ok;
//echo "\n\n\n\n\n $ok \n\n\n\n\n\n";exit;
			} elseif ($cookie)
			{
				if (is_array($url))
				{
					$ckfile = tempnam(sys_get_temp_dir(), "alitem-cookie-".$usrId."-");
					$this->setCookie($ckfile);

					// Obtendo o cookie
					$ch = curl_init ($url[0]);
					curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
					$output = curl_exec ($ch);

					// Usando o cookie
					$ch = curl_init ($url[1]);


					if ($json)
					{
						$bodyData = array (
						  'json' => json_encode($post)
						);
						$bodyStr = http_build_query($bodyData);

						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch,CURLOPT_HTTPHEADER,array (
							"Content-Type: application/json; charset=utf-8",
							"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
							"X-Requested-With: XMLHttpRequest",
							"Content-Length: ".strlen($post)
						));
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
					} else
					{
						curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
						curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
						if ((is_array($post)) || ($post<>""))
						{
							curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
							curl_setopt ($ch, CURLOPT_FRESH_CONNECT, 1);
							curl_setopt ($ch, CURLOPT_AUTOREFERER, true);

						}
					}
					$this->html = curl_exec ($ch);
					curl_close ($ch);
					//unlink($ckfile);
				} else
				{
					$ckfile=$this->getCookie();
					//echo "<h1>$ckfile</h1>";
					$ch = curl_init ($url);
					curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
					$this->html = curl_exec ($ch);
					curl_close ($ch);
					//unlink($ckfile);
				}
			} else
			{
				$ch = curl_init();
				if (is_array($post))
				{
					$defaults = array(
						CURLOPT_USERAGENT => $userAgent,
						CURLOPT_POST => 1,
						CURLOPT_AUTOREFERER => true,
//						CURLOPT_HEADER => 0,
						CURLOPT_URL => $url,
						CURLOPT_FRESH_CONNECT => 1,
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_FORBID_REUSE => 1,
						CURLOPT_TIMEOUT => 4,
						CURLOPT_POSTFIELDS => http_build_query($post)
					);
					curl_setopt_array($ch, $defaults);
				} else
				{
					curl_setopt ($ch, CURLOPT_USERAGENT, $userAgent);
					curl_setopt ($ch, CURLOPT_URL, $url);
					curl_setopt ($ch, CURLOPT_HEADER, 0);
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
				}
				//ob_start();

				$this->html = curl_exec ($ch);
				curl_close ($ch);
				//$this->html = ob_get_contents();
				//ob_end_clean();
			}

			$this->html=str_replace('&nbsp;','',$this->html);
			if (stripos($this->html,"ISO-8859")!==false)
			{
				// Necessário para corrigir o BUG do ->loadHTML (quebra o acento "í")
				/*
				$html=urlencode($this->html);
				$html=str_replace("%ED","i",$html);
				$this->html=urldecode($html);
				 *
				 */

				// Mudando ISO pra UTF
				$this->html=utf8_encode($this->html);
				if (stripos($this->html,"ISO-8859-1")!==false)
					$this->html=str_ireplace("ISO-8859-1","UTF-8",$this->html);
				if (stripos($this->html,"ISO-8859")!==false)
					$this->html=str_ireplace("ISO-8859","UTF-8",$this->html);
			}
		}
		return ($ok);
	}

	/**
	 * Verifica dentro da página qual o layout a ser utilizado
	 * @return type boolean (Layout encontrado ou não) - $this->layout
	 */
	function searchLayout($debug=0)
	{
		$sai=false;
		$acheiEm=100000;
		$tmp=$this->layoutObj;
		$this->tratamentoId=-1;
		foreach ($tmp as $index=>$layout)
		{
			if ($acheiEm==100000)
			{
				$keys=$layout->getNames();
				foreach ($keys as $key)
				{
					$b=stripos($this->html,$key);
					if (($b!==false) && ($b<$acheiEm))
					{
						if ($debug>0)
						{
							echo "<h1>Achei layout [$key] em $b</h1>";
						}
						$acheiEm=$b;
						$this->layoutId=$index; //tratamentoId
						$this->layoutName=$key; //tratamentoTipo
						$this->layout=$layout->getLayout();
						$sai=true;
					} else
					{
						$b=stripos(html_entity_decode($this->html),$key);
						if (($b!==false) && ($b<$acheiEm))
						{
							if ($debug>0)
							{
								echo "<h1>Achei layout [$key] em $b</h1>";
							}
							$acheiEm=$b;
							$this->layoutId=$index; //tratamentoId
							$this->layoutName=$key; //tratamentoTipo
							$this->layout=$layout->getLayout();
							$sai=true;
						} else
						{
						}
					}
				}
			}

		}
		return ($sai);
	}

	function attachExtract($txt)
	{
		$sai='';
		$p1=stripos($txt,"<a ");
		// TST    : <a href="http://aplicacao5.tst.jus.br/ju_consultaDocumento/despacho.do?anoProcInt=2008&numProcInt=250791&dtaPublicacaoStr=23/09/2011 19:00:00" class="linkfree">
		// TRT Rio: <img src="/portal/imagens/arquivo_download.gif" align="middle" onclick="openPopupVisualizarDocumento('/portal/downloadArquivoPdf.do?sqDocumento=20749787', 640, 480);" style="cursor:pointer;">
		if ($p1!==false)
		{
			$txt=explode("href=",$txt);
			$txt=$txt[1];
			$sep=' ';
			if (strpos($txt,'"')!==false)
				$sep='"';
			$txt=explode($sep,$txt);
			$sai=$txt[1];
		} else
		{
			$p1=stripos($txt,"<img ");
			if ($p1!==false)
			{
				$txt=substr($txt,$p1);
				$txt=explode("'",$txt);
				$sai=$txt[1];
			}
		}
		return($sai);
	}

	function getTags($tag,$start,$end="",$delimiter="",$debug=0)
	{
		$html=$this->html;
		$tagStart="<".$tag;
		$tagEnd="</".$tag;

		$p=stripos($html,$start);
		if ($p===false)
		{
			$start=html_entity_decode($start);
			$p=stripos($html,$start);
		}

		if ($p!==false)
		{
			$txt=substr($html,($p+strlen($start)));
			if ($end<>"")
			{
				$txt=substr($txt,0,stripos($txt,$end));
			}
			if ($delimiter<>"")
				$txts=explode($delimiter,$txt);
			else
				$txts=array($txt);

			foreach ($txts as $txt)
			{
				$primeiro=true;
				while((stripos($txt,$tagStart) !== false) && ($txt<>''))
				{
					$p1=stripos($txt,$tagStart);
					$p2=stripos($txt,$tagStart,($p1+1));
					if ($p2===false)
						$p2=strlen($txt);
					$p3=stripos($txt,$tagEnd);
					if ($p3!==false)
					{
						if (($p3<$p2) && ($p3>0))
							$p2=$p3;
					}
					$sujo=substr($txt,($p1),($p2-$p1));
					$sujo=str_replace("  "," ",$sujo);
					$sujo=str_replace("  "," ",$sujo);
					$sujo=str_replace("  "," ",$sujo);
					$limpo=$this->htmlTextClean($sujo);
					$txt=substr($txt,$p2);
					$this->htmlTags[$tag][($start."~".$end)][]=array($limpo,$sujo,$primeiro);
					$primeiro=false;
				}
			}
			//echo "TAGS: tag $tag start $start end $end delim $delimiter <pre>";print_r($this->htmlTags);exit;
		}
	}

	/** Trata HTML retornando campos conforme Layout
	 * Layout deve ter os campos:
	 *   name: nome do campo
	 *   type: tipo (text=procura uma vez, array=procura em loop)
	 *   start: inicio do HTML a ser considerado
	 *   end: final do HTML a ser considerado
	 *   index: número ou números separados por vírgula (se forem vários, concatena)
	 *   separator: separador para ser usado quando houverem vários campos
	 *
	 * Para o tipo "array", também:
	 *   id: posicao para o id
	 *   content: posição ou posições para o conteúdo (separados por vírgula)
	 *	  step: salto
	 *   delimiter: a cada delimitador encontrado, marca a próxima tag encontrada
	 *
	 * @return $campos array Campos encontrados
	 */
	function parse($debug=0)
	{
		$this->prepareHtml(); // remove <script> e <style>, e converte pra UTF-8
		error_reporting(E_ALL);
		foreach ($this->layout as $campoBd=>$param)
		{
gLog("===> A ... $campoBd");

			$tag=$param['tag'];
			$index=$param['index'];
			$start=$param['start'];
			$end=$param['end'];
			$delimiter=$param['delimiter'];
			$separator=($param['separator']<>''?$param['separator']:chr(10));
gLog("===> A1 ... $campoBd");
			$indexes=explode(",",$index);
gLog("===> A2 ... $campoBd");
			$buscarPorId=is_numeric($indexes[0]);
gLog("===> B ... $campoBd");
			if (!isset($this->htmlTags[$tag][$start."~".$end]))
			{
				$this->getTags($tag,$start,$end,$delimiter, $debug);
				//gLog("===> $campoBd => start: $start / end: $end / delimiter: $delimiter / tag: $tag / valor: $index / buscarPorId: $buscarPorId<br>\n");
				if ($debug>0)
				{
					echo "\n\n<br>==== $campoBd => start: $start / end: $end / delimiter: $delimiter / tag: $tag / valor: $index / buscarPorId: $buscarPorId<br>\n";
					if (($debug>1) || ($campoBd<>"andamentos"))
					{
						foreach ($this->htmlTags[$tag][$start."~".$end] as $i=>$v)
						{
							echo "========== $i = ".str_replace(chr(10),".",$v[0])."<br>\n";
							foreach ($v as $i=>$v2)
							{
							//	echo "========== $i = ".str_replace(chr(10),".",$v2[0])."\n";
							}
						}
					}
				}
			}
gLog("===> C ... $campoBd");
			if ($param['type']=="array")
			{
				gLog("===> C1 ... $campoBd");
				// Usando delimitador...
				if ($delimiter<>'')
				{
					$i=0;
					$ttl=count($this->htmlTags[$tag][$start."~".$end]);
					while ($i<$ttl)
					{
						$id=$this->htmlTags[$tag][$start."~".$end][($i)][0];
						$content=$attach='';
						$i++;
						while (($i<$ttl) && (!$this->htmlTags[$tag][$start."~".$end][($i)][2]))
						{
							if ($this->htmlTags[$tag][$start."~".$end][($i)][0]<>'')
							{
								$content[]=$this->htmlTags[$tag][$start."~".$end][($i)][0];
								$attach[]=$this->attachExtract($this->htmlTags[$tag][$start."~".$end][($i)][1]);
							}
							$i++;
						}
						if (trim($id)<>'')
						{
							$campos[$campoBd][]=array($id,trim(str_ireplace("<br>"," ",implode($separator,$content))),trim(str_ireplace("<br>"," ",implode(chr(10),$attach))));
						}
					}
				} else
				{

				// Array indexado normal...
					if ($buscarPorId)
					{
						$i=$indexes[0];
						$posId=$param['id'];
						$posContent=$param['content'];
						$posContents=explode(",",$posContent);
						$posStep=$param['step'];
						$ttl=count($this->htmlTags[$tag][$start."~".$end]);
						//echo "***** i=$i - posId: $posId - posContent: $posContent - posStep: $posStep - ttl: $ttl\n";
						while ($i<$ttl)
						{
							$id=$this->htmlTags[$tag][$start."~".$end][($i+$posId)][0];
							$content=$attach='';
							foreach ($posContents as $posContent)
							{
								$atx=$this->attachExtract($this->htmlTags[$tag][$start."~".$end][($i+$posContent)][1]);
								if (($this->htmlTags[$tag][$start."~".$end][($i+$posContent)][0]<>'') || ($atx<>''))
								{
									$content[]=$this->htmlTags[$tag][$start."~".$end][($i+$posContent)][0];
									$attach[]=$atx;
								}

							}
							$i+=$posStep;
							if (trim($id)<>'')
							{
								$campos[$campoBd][]=array($id,trim(str_ireplace("<br>"," ",implode($separator,$content))),trim(str_ireplace("<br>"," ",implode(chr(10),$attach))));
							}
						}
					}
				}
			} else // type: text
			{
				gLog("===> C2 ... $campoBd");
				if ($buscarPorId)
				{
					foreach ($indexes as $indx)
					{
						if ($this->htmlTags[$tag][$start."~".$end][$indx][0]<>'')
						{
							$faz=true;
							if ($param['find']<>'')
							{
								$faz=(stripos($this->htmlTags[$tag][$start."~".$end][$indx][1],$param['find'])===false?false:true);
							}
							if ($faz)
								$campos[$campoBd][]=$this->htmlTags[$tag][$start."~".$end][$indx][0];
						}
					}
					$campos[$campoBd]=trim(str_ireplace("<br>"," ",implode($separator,$campos[$campoBd])));
				} else
				{
					foreach ($this->htmlTags[$tag][$start."~".$end] as $el)
					{
						foreach ($indexes as $indx)
						{
							if (stripos($el[1],$indx)!==false)
							{
								if ($el[0]<>'')
									$campos[$campoBd][]=str_replace("<br>"," ",$el[0]);
							}
						}
					}
					$campos[$campoBd]=trim(str_ireplace("<br>"," ",implode($separator,$campos[$campoBd])));
				}
			}
gLog("===> Z ... $campoBd");
		}
gLog("===> Zz ... $campoBd");
		if ($debug>1)
		{
			echo "\n\nCampos:\n\n";
			echo "<pre>";print_r($campos);
			echo "</pre>\n\n\n\n\n\n";
		}

		if (is_array($this->fields))
		{
			foreach ($campos as $key=>$value)
				$this->fields[$key]=$value;
		} else
		{
			$this->fields=$campos;
		}
gLog("===> Zzz ... $campoBd");
	}


	function run($url,$post,$cookie)
	{
		$error=0;
		// Abre a URL
		if ($this->siteOpen($url,$post,$cookie))
		{
			// Verifica se o layout da página confere com algum informado
			if ($this->searchLayout())
			{
				// Layout encontrado...
				$this->parse(0);
			} else
			{
				$error=2; // Layout não encontrado
			}
		} else
		{
			$error=1; // Não foi possível abrir a URL
		}
		return($error);
	}

	function runHtml($html)
	{
		$error=0;
		$this->html=$html;
		// Verifica se o layout da página confere com algum informado
		if ($this->searchLayout())
		{
			// Layout encontrado...
			$this->parse(0);
		} else
		{
			$error=2; // Layout não encontrado
		}
		return($error);
	}


	function parseHtml()
	{

	}


	function __destruct()
	{
		//unlink($this->cookieFile);
	}
}




















?>
