<?php

/**
 * Classe responsável pela simulação de um navegador web - permitindo criar robôs
 * @author	giuliano
 * @version	1.0 27-03-2016
 */
class gBrowser
{
	public $jarr;
	public $contentType;
	public $header;
	public $url;
	public $domain;
	public $post;
	public $html;
	public $htmlObject;
	public $htmls;
	public $http_code;
	public $http_code_original;
	public $headers;
	public $ch;
	public $session;
	public $curl_error				= '';
	public $cookieFile				= '';
	public $captchaFile				= '';
	public $referer 				= '';
	public $redirect 				= '';
	public $viewState				= '';
	public $method 					= 'get';
	public $info;
	public $usrAgent				= "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:19.0) Gecko/20100101 Firefox/19.0";
	public $defaultContentType		= "text/html;charset=ISO-8859-1";

	public $errorCode 				= '';
	public $errorMessage			= '';

	// Opções
	public $timeout 				= 15;
	public $retry					= 5;
	public $returnHeader			= true;
	public $gzip 					= false;
	public $captcha 				= false;
	public $killCookieOnDestruct	= true;
	public $autoRedirect			= true;
	public $redirects 				= 0;
	public $maxRedirect				= 2;
	public $sendCount				= 0;
	public $useHttpBuildQuery		= true;
	public $debug 					= false;
	public $debugFileName			= '/tmp/gbrowser-debug';
	public $elementNotFound 		= false;
	public $redirectedTo			= '';
	public $ready 					= false;

	public function __construct($json = '')
	{
		// Parâmetros
		$this->jarr = is_array($json) ? $json : cssDecode($json);

		$this->timeout = ($this->jarr['timeout'] > 0 ? intval($this->jarr['timeout']) : $this->timeout);
		$this->retry = ($this->jarr['retry'] > 0 ? intval($this->jarr['retry']) : $this->retry);
		$this->gzip = ($this->jarr['gzip'] == 'true');
		$this->captcha = ($this->jarr['captcha'] == 'true');
		$this->debug = ($this->jarr['debug'] == 'true');
		$this->debugFileName = ($this->jarr['debugFileName'] != '' ? intval($this->jarr['debugFileName']) : $this->debugFileName);
		$this->setHeaders();
		$this->beginSession();
	}


	public function __destruct()
	{
		$this->endSession();
	}


	public function setHeaders(): void
	{
		// Cabeçalhos padrões
		$this->headers = "";
		$this->headers[] = "Connection: keep-alive";
		$this->headers[] = "Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
		$this->headers[] = "DNT: 1";
		$this->headers[] = "User-Agent: ".$this->usrAgent;
		$this->headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
		$this->contentType = $this->defaultContentType;
	}


	public function getSessionNumber(): string
	{
		return($this->session.' '.str_pad((string) $this->sendCount,2,'0', STR_PAD_LEFT));
	}


	public function beginSession(): void
	{
		$this->session = date("ymd").str_pad(random_int(1,999999), 6, "0", STR_PAD_LEFT);
		jLog("Início de sessão", $this->getSessionNumber(), "infor");
	}


	public function endSession($killCookieOnDestruct = true): void
	{
		$session = $this->getSessionNumber();
		if ($this->session != '') {
			jLog("Final de sessão", $session, "infor");
			$this->session = '';
			if ($this->killCookieOnDestruct || $killCookieOnDestruct) {
				jLog("Destruindo cookie e captcha", $session, "infor");
				$this->killCookie();
				$this->killCaptcha();
			}
		}
	}


	/**
	 * Remove o cookie do sistema de arquivos e da variável de sessão
	 * @author	Giuliano Nascimento
	 * @version	1.0 31-08-2016 12:21
	 */
	public function killCookie(): void
	{
		// Destroi Cookie
		if ($this->cookieFile == '') {
			$this->cookieFile = $_SESSION['gBrowserCookie'];
		}

		if ($this->cookieFile != '') {
			unlink($this->cookieFile);
			$this->cookieFile = '';
		}

		$_SESSION['gBrowserCookie'] = '';
	}


	/**
	 * Remove o captcha do sistema de arquivos e da variável de sessão
	 * @author	Giuliano Nascimento
	 * @version	1.0 31-08-2016 12:21
	 */
	public function killCaptcha(): void
	{
		// Destroi Cookie
		if (file_exists($this->captchaFile)) {
			unlink($this->captchaFile);
			$this->captchaFile = '';
		}
	}


	/**
	 * Cria um arquivo para armazenar o Cookie por uma página web
	 * @author	Giuliano Nascimento
	 * @version	1.0 29-03-2016 12:21
	 * @return string $ckfile Caminho do arquivo
	 */
	public function setCookie(): string
	{
		global $gSemente;

		//$ckfile = tempnam(sys_get_temp_dir(), "gjuridico-cookie-".$gSemente);
		$ckfile = '/tmp/gbrowser-'.$this->session . "-cookie.txt";
		$this->cookieFile = $ckfile;
		$_SESSION['gBrowserCookie'] = $ckfile;
		return($ckfile);
	}


	public function getHtmlObject($convertToUtf8 = false, $validate = false): bool
	{
		//$this->html = str_replace('&#160;',' ', str_replace('&nbsp;',' ', $html));
		if ($this->info['header_size'] > 0) {
			$this->header = substr((string) $this->html, 0, $this->info['header_size']);
			$this->html	  = trim(substr((string) $this->html, $this->info['header_size']));
			$this->html   = str_replace('&nbsp;','', $this->html);
		} else {
			$p = strpos((string) $this->html, "\n\n" );
			if ($p !== false) {
				$this->header = substr((string) $this->html, 0, $p);
				$this->html   = substr((string) $this->html, $p+2);
			}
		}

		if ($convertToUtf8) {
			$this->html = mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8');
		}

		$this->htmlObject = new DOMDocument();
		$this->htmlObject->preservWhiteSpace = FALSE; //elimina espaços em branco
		$this->htmlObject->formatOutput = FALSE;

		if ($validate) {
			$this->htmlObject->validateOnParse = true;
		}

		//file_put_contents('/tmp/teste.html', $this->html);
		return ($this->htmlObject->loadHTML($this->html));
	}


	public function setHtml($html): bool
	{
		$this->html = str_replace('&#160;',' ', str_replace('&nbsp;',' ', $html));
		$this->html = str_replace('RÉU','Réu', $this->html);
		$this->htmlObject = new DOMDocument();
		$this->htmlObject->preservWhiteSpace = FALSE; //elimina espaços em branco
		$this->htmlObject->formatOutput = FALSE;
		return ($this->htmlObject->loadHTML($this->html));
	}


	/**
	 * Retorna o nome do arquivo de cookie atual
	 * @author	Giuliano Nascimento
	 * @version	1.0 29-03-2016 12:21
	 * @return string $ckfile Caminho do arquivo
	 */
	public function getCookie()
	{
		if ($this->cookieFile != "") {
			$ck = $this->cookieFile;
			jLog("COK - Usando cookie da classe [".$ck."]", $this->getSessionNumber());
		} else {
			$ck = $_SESSION['gBrowserCookie'];
			if ($ck == '') {
				$ck = $this->setCookie();
				jLog("COK - Novo cookie [".$ck."]", $this->getSessionNumber());
			} else {
				$this->cookieFile = $ck;
				jLog("COK - Usando cookie da sessão [".$ck."]", $this->getSessionNumber());
			}
		}

		$c = file_get_contents($this->cookieFile);
		$c = str_replace("\r","", $c);
		$c = explode("\n", $c);
		foreach ($c as $lin) {
			if (str_contains($lin,"\t")) {
				jLog("COK - ".$lin, $this->getSessionNumber());
			}
		}

		return ($ck);
	}


	public function enableGzip(): void
	{
		$this->gzip = true;
		$this->headers[] = "Accept-Encoding: gzip, deflate";
	}


	/**
	 * Salva o HTML atual em um array ($this->htmls)
	 */
	public function pushHtml(): void
	{
		$html = trim(substr((string) $this->html, $this->info['header_size']));
		$html = str_replace('&nbsp;','', $html);
		$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
		$this->htmls[] = $html;
	}


	/**
	 * Prepara a requisição
	 * @param  string $url [description]
	 * @return [type]      [description]
	 */
	public function prepare($url = ""): void
	{
		if ($url == "") {
			$url = $this->url;
		}

		jLog("*** - Iniciando: [".$url."]", $this->getSessionNumber(), 'aviso');

		$this->viewState = '';
		if (!$this->useHttpBuildQuery) {
			$bodyData = '';
			/** Metodo 1 */
			$boundary = md5(time());
			foreach ($this->post as $key => $value) {
				$bodyData .= "--$boundary\r\nContent-Disposition: form-data; name=\"$key\"\r\n\r\n".($value)."\r\n";
			}

			$bodyData .= "--$boundary--";
			$this->headers[] = "Content-Type: multipart/form-data; boundary=$boundary";
			$this->headers[] = "Accept-Encoding: gzip, deflate";
			//$this->header[]="Content-Length: ".strlen($bodyData);
			/** Metodo 2
			$boundary=md5(time());
			foreach($this->post as $key => $value){
				$bodyData.= "--$boundary\r\nContent-Disposition: form-data; name=\"$key\"\r\n\r\n".urlencode($value)."\r\n";
			}
			$bodyData.= "--$boundary--";
			$this->headers[]="Content-Type: multipart/form-data; boundary=$boundary";
			$this->headers[]="Content-Length: ".strlen($bodyData);
			*/
			/** Método 3
			$bodyArray = '';
			foreach($this->post as $key => $value){
				$bodyArray[]= $key."=".urlencode($value);
			}
			$bodyData = implode('&', $bodyArray);
			$this->headers[]="Content-Type: application/x-www-form-urlencoded";
			$this->headers[]="Content-Length: ".strlen($bodyData);
			$this->headers[]="Accept-Encoding: gzip, deflate";
			*/
			/** Método 4
			$bodyData = http_build_query($this->post);
			$this->headers[]="Content-Length: ".strlen($bodyData);
			$this->headers[]="Accept-Encoding: gzip, deflate";
			*/
			$this->gzip = true;
		} elseif ($this->contentType != '') {
			$this->headers[] = "Content-Type: ".$this->contentType;
		}

		if ($this->referer != '') {
			$this->headers[] = "Referer: ".$this->referer;
		}

		$this->headers[] = "Host: ".str_replace('http://','',str_replace('https://','',$this->domain));

		$ckfile = $this->getCookie();
		$this->ch = curl_init ($url);

		if (
			($_SERVER['SERVER_ADDR'] != '::1')
			&& ($_SERVER['SERVER_ADDR'] != 'localhost')
			&& ($_SERVER['SERVER_ADDR'] != '127.0.0.1')
		) {
			curl_setopt($this->ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR']); // necessário para usar como saída
		}

		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false); // desativa a conferência do SSL
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, ($ckfile));
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, ($ckfile));
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout); // em segundos
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

		if ($this->returnHeader) {
			curl_setopt($this->ch, CURLOPT_HEADER, true);
		} else {
			curl_setopt($this->ch, CURLOPT_HEADER, false);
		} // retorna o cabeçalho HTTP junto ao HTML

		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); // se tiver um redirecionamento, vai atrás dele
		if ($this->gzip) {
			curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip, deflate, br');
		}

		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
		foreach ($this->headers as $h) {
			jLog("HEA [".$h."]", $this->getSessionNumber());
		}

		if ($this->method == 'post') {

			if ($this->useHttpBuildQuery) {
				curl_setopt ($this->ch, CURLOPT_POST, true);

				if (is_array($this->post)) {
					curl_setopt($this->ch, CURLOPT_POSTFIELDS,http_build_query($this->post)); // parâmetros
				} else {
					jLog("PST PARM = ".$this->post, $this->getSessionNumber());
					curl_setopt($this->ch, CURLOPT_POSTFIELDS,$this->post); // parâmetros
				}

			} else {
				//curl_setopt ($this->ch, CURLOPT_HTTPHEADER,array("Expect:"));
				curl_setopt ($this->ch, CURLOPT_POST, 1);
				curl_setopt ($this->ch, CURLOPT_POSTFIELDS, $bodyData);
				//jLog("PST boundary/urlencoded = $bodyData", $this->getSessionNumber());
			}

			foreach ($this->post as $key => $value) {
				jLog("PST [$key] = [$value]", $this->getSessionNumber());
			}
		}

		$this->ready = true;
	}


	/**
	 * Finaliza a conexão CURL
	 * @author	Giuliano Nascimento
	 * @version	1.0 29-03-2016 12:21
	 */
	public function close(): void
	{
		// Vê se tem ViewState (jboss)
		$this->viewState = $this->getElement('input', 'ViewState', 'value');
		$this->info = curl_getinfo($this->ch);
		curl_close($this->ch);

		if ($this->debug) {
			$file = $this->debugFileName.'-'.$this->sendCount.'.html';
			$html = "<!--\n\n" . strtoupper((string) $this->method) . ' ' . $this->url . "\n\n=> Headers:\n" . implode("\n",$this->headers) . "\n\n";

			if ($this->method == 'post') {
				$html .= "=> Post parameters:\n";

				if (is_array($this->post)) {

					foreach ($this->post as $key => $value) {
						$html .= $key . "=[$value]\n";
					}

				} else {
					$html .= $this->post;
				}

				$html .= "\n";
			}

			$html .= "=> Response headers:\n";
			$html .= substr((string) $this->html, 0, $this->info['header_size']);
			$html .= "\n\n-->\n\n";
			$html .=  substr((string) $this->html, $this->info['header_size']);
			file_put_contents($file, $html);
		}

		$this->setHeaders();
		$this->post = '';
		$this->ready = false;
		jLog("*** - Finalizando", $this->getSessionNumber(), 'aviso');

		if ($this->autoRedirect && str_contains((string) $this->html, 'Ajax-Response: redirect')) {

			if ($this->redirects < $this->maxRedirect) {
				jLog('RED Encontrado redirecionamento AJAX!', $this->getSessionNumber());
				$this->redirects++;
				preg_match_all('/Location: (.*)/', (string) $this->html, $matches);

				if ($matches[1][0] !== '') {
					$url = $this->domain . $matches[1][0];
					jLog('RED Redirecionar para: '.$url, $this->getSessionNumber());
					$this->redirect = $url;
					//$this->get($url);
				}

			} else {
				jLog('RED Máximo de redirecionamentos ('.$this->maxRedirect.') foi alcançado!', $this->getSessionNumber());
			}
		}
	}


	public function setUrl($url=""): void
	{
		if ($url != "") {
			$this->url = trim(str_replace("\r","",str_replace("\n","",$url)));
			$parse = parse_url((string) $url);
			$this->domain = parse_url((string) $url, PHP_URL_SCHEME).'://'.parse_url((string) $url, PHP_URL_HOST);
			$port = parse_url((string) $url, PHP_URL_PORT);

			if ($port != '80' && $port != '') {
				$this->domain.=':'.parse_url((string) $url, PHP_URL_PORT);
			}

		}
	}


	/**
	 * Busca conteúdo da página web informada
	 * @author	Giuliano Nascimento
	 * @version	1.0 29-03-2016 12:21
	 */
	public function get($url = "", string $label = "", $autoRedirect = false)
	{
		$ok = true;
		$this->method = "get";
		$this->sendCount++;
		$this->contentType = $this->defaultContentType;
		jLog("GET - [".$label."]", $this->getSessionNumber(), 'aviso');
		$this->setUrl($url);

		$this->returnHeader = true;

		if (!$this->ready) {
			$this->prepare();
		}

		jLog("GET - Abrindo...", $this->getSessionNumber());
		$this->html = curl_exec($this->ch);
		$this->pushHtml();
		$this->http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		$this->curl_error = curl_error($this->ch);

		if ($this->curl_error !== '') {
			$ok = false;
			$this->errorCode 		= '600';
			$this->errorMessage 	= $this->curl_error;
			jLog($this->curl_error, $this->getSessionNumber(),'error');
		}

		// Notou-se em Novembro de 2014 que o servidor do PJe retorna vários HEADERs pra confundir o robo
		// O http_code obtido através da função do PHP (acima) retorna um código falso!
		// O último HEADER é o que deve ser considerado
		$codes=explode('HTTP/1.1', $this->html);
		$redir = false;
		foreach ($codes as $code) {
			$code = trim($code);
			if (str_starts_with($code, '302 ')) {
				$this->http_code=302;
				//$ok = false;
				$redir = true;
			}

			if (str_starts_with($code, '200 ') && $this->http_code=='') {
				$this->http_code = 200;
				//$ok = false;
			}
		}

		jLog("GET - Concluído [".$this->http_code."]", $this->getSessionNumber());
		if ($this->ready) {
			$this->close();
		}

		if ($autoRedirect && $redir) {
			$codes = explode('Location: ', $this->html);
			$html = $this->html;
			$fez  = false;
			foreach ($codes as $code) {
				if (str_starts_with($code, 'http') && !$fez) {
					//$fez = true;
					$this->url = trim(substr($code, 0, strpos($code, "\n")));
					$this->redirectedTo = $this->url;
					$this->setHeaders();
					$this->get('', 'Auto-redirecionando...', true);
					$this->getHtmlObject();
					$html .= $this->html;
				}

				$this->html = $html;
			}
		}
		return ($ok);
	}


	/**
	 * Busca conteúdo da página web informada
	 * @author	Giuliano Nascimento
	 * @version	1.0 29-03-2016 12:21
	 */
	public function post($url="", string $label = "", $autoRedirect = false)
	{
		$ok = true;
		$this->method = "post";
		$this->sendCount++;
		jLog("PST - [".$label."]", $this->getSessionNumber(), 'aviso');
		$this->contentType = "application/x-www-form-urlencoded; charset=UTF-8";
		$this->setUrl($url);

		$this->returnHeader = true;
		if (!$this->ready) {
			$this->prepare();
		}

		jLog("PST - Abrindo...", $this->getSessionNumber());
		$this->html = curl_exec($this->ch);
		$this->pushHtml();
		$this->http_code  = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		$this->curl_error = curl_error($this->ch);

		if ($this->curl_error !== '') {
			$ok = false;
			$this->errorCode 	= '600';
			$this->errorMessage = $this->curl_error;
			jLog($this->curl_error, $this->getSessionNumber(),'error');
		}

		// Notou-se em Novembro de 2014 que o servidor do PJe retorna vários HEADERs pra confundir o robo
		// O http_code obtido através da função do PHP (acima) retorna um código falso!
		// O último HEADER é o que deve ser considerado
		$codes = explode('HTTP/1.1', $this->html);
		foreach ($codes as $code) {
			$code = trim($code);
			if (str_starts_with($code, '302 ')) {
				$this->http_code = '302';
				$redir = true;
			}

			if (str_starts_with($code, '200 ') && $this->http_code == '') {
				$this->http_code='200';
			}
		}

		jLog("PST - Concluído [".$this->http_code."]", $this->getSessionNumber());
		if ($this->ready) {
			$this->close();
		}

		if ($ok && $autoRedirect && $redir) {
			$codes = explode('Location: ', $this->html);
			$html  = $this->html;
			$fez   = false;
			foreach ($codes as $code) {
				if (str_starts_with($code, 'http') && !$fez) {
					$fez = true;
					$this->url = trim(substr($code, 0, strpos($code, "\n")));
					$this->redirectedTo = $this->url;
					$ok = $this->get('', 'Auto-redirecionando...', true);
					$this->getHtmlObject();
					$html .= $this->html;
				}

				$this->html = $html;
			}
		}
		return ($ok);
	}


	/**
	 * Obtém atributo ou string do elemento HTML informado (mesmo em HTMLs mal formados)
	 * @param  string $tag  Tag HTML (a, input, ...)
	 * @param  string $sign Texto que define qual tag buscar
	 * @param  string $attr Qual atributo deve ter seu valor retornado
	 * @return string       Resultado
	 */
	public function getElement(string $tag, $sign, ?string $attr = ""): string
	{
		$sai = '';
		$matches = '';

		preg_match_all('/<'.$tag.'[^>]+>/i', (string) $this->html, $matches);
		if ($attr == '') {
			$sai = $matches[0][0];
		} else {
			foreach ($matches[0] as $row) {
				if (stripos($row, $sign) !== false) {
					preg_match_all('/('.$attr.')=("[^"]*")/i',$row, $src);
					$sai = $src[2][0];
				}
			}
		}

		return (str_replace('"','', $sai));
	}


	/**
	 * Obtém arquivo de imagem do captcha na última página acessada
	 * @param  string $sign Fragmento de texto dentro da tag que indica que é um captcha. Exemplo: captchaImg
	 * @param  string $type Tipo de arquivo (jpeg, png ou gif)
	 * @param  string $optimize Indica se deve otimizar a imagem recebida
	 * @param  string $tag Tag que contém o captcha (normalmente "img")
	 * @return array $captcha Retorna vazio se não encontrou o captcha e um array com dados se encontrou
	 */
	public function getCaptcha($sign = "", string $type = "jpeg", $optimize = false, string $tag = "img"): array|string
	{
		global $gPathTmp, $http_tmp, $usrId;
		$this->method = "get";
		$captcha = $link_remoto = "";
		preg_match_all('/<'.$tag.'[^>]+>/i', (string) $this->html, $matches);
		foreach ($matches[0] as $row) {
			if (stripos($row, $sign) !== false) {
				preg_match_all('/(src)=("[^"]*")/i',$row, $src);
				$link_remoto = $this->domain . str_replace('"','',$src[2][0]);
			}
		}

		if ($link_remoto != '') {
			$this->sendCount++;
			$this->setUrl();
			$img_file_name		= 'imagem-'. $this->session . "." .$type;
			$this->returnHeader	= false;
			$this->headers[]	= "Accept: */*";
			$this->headers[]	= "Referer: ".$this->url;

			jLog("CPT - [Buscando imagem captcha]", $this->getSessionNumber(), 'aviso');
			$this->prepare($link_remoto);
			jLog("CPT - Trazendo captcha...", $this->getSessionNumber());
			$output = curl_exec($this->ch);
			$this->close();
			//while (stripos($output, 'decodeURIComponent')!==false)
			// {
			jLog("CPT - Javascript encontrado!", $this->getSessionNumber());
			$output = $this->javascriptRedirect($output, $link_remoto, true, false);
			// }

			$link_local 			= $gPathTmp.$img_file_name;
			$link_www 				= $http_tmp."/".$img_file_name;
			$this->captchaFile		= $link_local;
			$captcha['link_remoto']	= $link_remoto;
			$captcha['link_local']	= $link_local;
			$captcha['link_www']	= $link_www;

			if ($optimize) {
				$img_data = imagecreatefromstring($output);
				$this->optimize($img_data,$link_local);
			} else {
				file_put_contents($link_local, $output);
			}

			// Salva captcha no sistema de arquivos local
			jLog("CPT - Captcha encontrado!", $this->getSessionNumber());
			jLog("CPT - Remoto: $link_remoto", $this->getSessionNumber());
			jLog("CPT - Local : $link_local", $this->getSessionNumber());
			jLog("CPT - Web   : $link_www", $this->getSessionNumber());
		} else {
			jLog("CPT - Captcha não encontrado: [$sign]", $this->getSessionNumber(),'error');
			$this->errorCode = "601";
			$this->errorMessage = "Captcha não encontrado!";
		}

		$this->captcha = $captcha;
		return($captcha);
	}


	/**
	 * Reconhece dígitos de uma imagem, retornando string correspondente
	 * @param  string $img_file_name Nome do arquivo no sistema local
	 * @return string Texto reconhecido
	 */
	public function ocr($img_file_name = "", string $force = "digits" ): string
	{
		/*
		Para o correto funcionamento desta função, o sofwtare tesseract deve estar devidamente instalaado
		https://code.google.com/p/tesseract-ocr/

		Comando padrão: tesseract [imagem-jpeg] [arquivo-texto] -l eng digits

		Pra melhorar o ocr, edite o arquivo: /usr/local/share/tessdata/configs/digits
		E substitua o conteúdo por:

		tessedit_char_whitelist i

		*/
		global $gPathTmp, $usrId;

		if ($img_file_name == "") {
			$img_file_name = $this->captchaFile;
		}

		if ($img_file_name == "") {
			$img_file_name = $this->captcha['link_local'];
		}

		if (str_contains((string) $img_file_name,'.png')) {
			// Convertendo de PNG pra JPEG (pra funcionar com o Tesseract)
			$input = imagecreatefrompng($img_file_name);
			[$width, $height] = getimagesize($img_file_name);
			$output = imagecreatetruecolor($width, $height);
			$white = imagecolorallocate($output,  255, 255, 255);
			imagefilledrectangle($output, 0, 0, $width, $height, $white);
			imagecopy($output, $input, 0, 0, 0, 0, $width, $height);
			$img_file_name.='.jpeg';
			imagejpeg($output, $img_file_name);
		}

		$ocrFile = '/tmp/gbrowser-'.$this->session . "-ocr";

		$tess = "/usr/local/bin/tesseract";
		if (!file_exists($tess)) {
			$tess = "/opt/local/bin/tesseract";
		}

		$cmd = $tess . " " . $img_file_name . " $ocrFile -l eng -psm 8 " . $force;
		//$cmd="tesseract ".$gPathTmp.$img_file_name." $ocrFile -l eng -psm 8";
		gLog("=== Executando OCR: $cmd");
		$sai = shell_exec($cmd);
		$ocrFile .= '.txt';
		$ocr = file_get_contents($ocrFile);
		//unlink($ocrFile);
		$ocr = str_replace("\n","",$ocr);
		$ocr = str_replace("\r","",$ocr);
		$ocr = trim(str_replace(" ","",$ocr));

		if ($ocr === "") {
			// Se não conseguiu reconhecer, tenta de outra maneira
			$cmd = $tess . " " . $img_file_name . " $ocrFile -l eng " . $force;
			$sai = shell_exec($cmd);
			$ocrFile .= '.txt';
			$ocr = file_get_contents($ocrFile);
			//unlink($ocrFile);
			$ocr = str_replace("\n","",$ocr);
			$ocr = str_replace("\r","",$ocr);
			$ocr = trim(str_replace(" ","",$ocr));
		}

		//unlink ($img_file_name);
		jLog("CPT - Texto : $ocr", $this->getSessionNumber());
		return($ocr);
	}


	public function optimize($img_data,$img_file_name): void
	{
		global $usrId, $http_tmp, $gPathTmp;
		jLog("CPT - Otimizando imagem...", $this->getSessionNumber());
		// Obtém tamanho
		$w = imagesx($img_data);
		$h = imagesy($img_data);

		// Melhora a imagem
		imagefilter($img_data, IMG_FILTER_CONTRAST, 30);

		// Transforma tudo em preto com fundo branco
		$lim = 160;
		$preto  = imagecolorallocate($img_data, 0, 0, 0);
		$branco = imagecolorallocate($img_data, 255,255,255);

		for ($x = 0; $x < $w; $x++) {

			for ($y = 0; $y < $h; $y++) {
				$rgb = imagecolorat($img_data, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				if (($r < $lim) || ($g < $lim) || ($b < $lim)) {
					imagesetpixel($img_data, ($x),($y), $preto);
				} else {
					imagesetpixel($img_data, ($x),($y), $branco);
				}

			}

		}

		// tira pontos soltos
		for ($x = 1; $x < $w-1; $x++) {

			for ($y = 1; $y < $h-1; $y++) {
				$rgb0 = imagecolorat($img_data, $x-1, $y);
				$r0 = ($rgb0 >> 16) & 0xFF;
				$g0 = ($rgb0 >> 8) & 0xFF;
				$b0 = $rgb0 & 0xFF;
				$rgb1 = imagecolorat($img_data, $x, $y);
				$r1 = ($rgb1 >> 16) & 0xFF;
				$g1 = ($rgb1 >> 8) & 0xFF;
				$b1 = $rgb1 & 0xFF;
				$rgb2 = imagecolorat($img_data, $x+1, $y);
				$r2 = ($rgb2 >> 16) & 0xFF;
				$g2 = ($rgb2 >> 8) & 0xFF;
				$b2 = $rgb2 & 0xFF;

				if ($r1 == 0 && $r0 == 255 && $r2 == 255) {
					imagesetpixel($img_data, ($x),($y), $branco);
				}

			}

		}

		// Bordas limpas
		for ($x = 0; $x < $w; $x++) {
			imagesetpixel($img_data, ($x),(0), $branco);
			imagesetpixel($img_data, ($x),(0), $branco);
			imagesetpixel($img_data, ($x),($h-1), $branco);
			imagesetpixel($img_data, ($x),($h-2), $branco);
		}

		for ($y = 0; $y < $h; $y++) {
			imagesetpixel($img_data, (0),($y), $branco);
			imagesetpixel($img_data, (1),($y), $branco);
			imagesetpixel($img_data, ($w-1),($y), $branco);
			imagesetpixel($img_data, ($w-2),($y), $branco);
		}

		// Busca linhas horizontais e remove
		for ($y = 0; $y < $h; $y++) {
			$tem = false;
			$temIni =- 1;
			$temFim =- 1;
			for ($x=0; $x < $w; $x++) {
				$rgb = imagecolorat($img_data, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if (($r+$g+$b) == 0) {
					if ($tem) {
						// Continua identificando
						$temFim = $x;
					} else {
						// Primeira identificacao
						$temIni = $x;
						$temFim = $x;
						$tem = true;
					}
				} else {
					// Terminou uma linha
					if ($tem && ($temFim-$temIni)>$tamMin) {
						for ($a = $temIni; $a<=$temFim; $a++) {
							$afaz = true;
							// Verifica se tem pixel acima
							if ($y > 0) {
								$rgb = imagecolorat($img_data, $a, $y-1);
								$r = ($rgb >> 16) & 0xFF;
								$g = ($rgb >> 8) & 0xFF;
								$b = $rgb & 0xFF;

								if (($r+$g+$b) == 0) {
									$afaz=false;
								}
							}

							if ($y < $h) {
								$rgb = imagecolorat($img_data, $a, $y+1);
								$r = ($rgb >> 16) & 0xFF;
								$g = ($rgb >> 8) & 0xFF;
								$b = $rgb & 0xFF;
								if (($r+$g+$b) == 0) {
									$afaz = false;
								}
							}

							if ($afaz) {
								imagesetpixel($img_data, ($a),($y), $branco);
							}
						}
					}

					$temIni =- 1;
					$temFim =- 1;
					$tem = false;
				}
			}

			// Terminou uma linha
			if ($tem && ($temFim-$temIni) > $tamMin) {
				for ($a = $temIni; $a <= $temFim; $a++) {
					$afaz = true;
					// Verifica se tem pixel acima
					if ($y > 0) {
						$rgb = imagecolorat($img_data, $a, $y-1);
						$r = ($rgb >> 16) & 0xFF;
						$g = ($rgb >> 8) & 0xFF;
						$b = $rgb & 0xFF;
						if (($r+$g+$b) == 0) {
							$afaz = false;
						}
					}

					if ($y < $h) {
						$rgb=imagecolorat($img_data, $a, $y+1);
						$r = ($rgb >> 16) & 0xFF;
						$g = ($rgb >> 8) & 0xFF;
						$b = $rgb & 0xFF;
						if (($r+$g+$b) == 0) {
							$afaz = false;
						}
					}

					if ($afaz) {
						imagesetpixel($img_data, ($a),($y), $branco);
					}
				}
			}

		}

		// Busca linhas verticais e remove
		for ($x = 0; $x < $w; $x++) {
			$tem = false;
			$temIni =- 1;
			$temFim =- 1;
			for ($y = 0; $y < $h; $y++) {
				$rgb = imagecolorat($img_data, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if (($r+$g+$b) == 0) {
					if ($tem) {
						// Continua identificando
						$temFim = $y;
					} else {
						// Primeira identificacao
						$temIni = $y;
						$temFim = $y;
						$tem = true;
					}
				} else {
					// Terminou uma linha
					if ($tem && ($temFim-$temIni)>$tamMin) {
						for ($a = $temIni; $a <= $temFim; $a++) {
							$afaz = true;
							// Verifica se tem pixel do lado esquerdo
							if ($x > 0) {
								$rgb = imagecolorat($img_data, ($x-1), $a);
								$r = ($rgb >> 16) & 0xFF;
								$g = ($rgb >> 8) & 0xFF;
								$b = $rgb & 0xFF;
								if (($r+$g+$b) == 0) {
									$afaz = false;
								}
							}

							// Verifica se tem pixel do lado direito
							if ($x < $w) {
								$rgb = imagecolorat($img_data, ($x+1), $a);
								$r = ($rgb >> 16) & 0xFF;
								$g = ($rgb >> 8) & 0xFF;
								$b = $rgb & 0xFF;
								if (($r+$g+$b) == 0) {
									$afaz = false;
								}
							}

							if ($afaz) {
								imagesetpixel($img_data, ($x),($a), $branco);
							}
						}
					}

					$temIni =- 1;
					$temFim =- 1;
					$tem = false;
				}
			}

			// Terminou uma linha
			if ($tem && ($temFim-$temIni) > $tamMin) {
				for ($a = $temIni; $a <= $temFim; $a++) {
					$afaz = true;
					// Verifica se tem pixel do lado esquerdo
					if ($x > 0) {
						$rgb = imagecolorat($img_data, ($x-1), $a);
						$r = ($rgb >> 16) & 0xFF;
						$g = ($rgb >> 8) & 0xFF;
						$b = $rgb & 0xFF;
						if (($r+$g+$b) == 0) {
							$afaz = false;
						}
					}

					// Verifica se tem pixel do lado direito
					if ($x < $w) {
						$rgb = imagecolorat($img_data, ($x+1), $a);
						$r = ($rgb >> 16) & 0xFF;
						$g = ($rgb >> 8) & 0xFF;
						$b = $rgb & 0xFF;
						if (($r+$g+$b) == 0) {
							$afaz = false;
						}
					}

					if ($afaz) {
						imagesetpixel($img_data, ($x),($a), $branco);
					}
				}
			}

		}

		// Salva pra usar o comando via shell
		if (str_contains((string) $img_file_name,'png')) {
			imagepng($img_data, $img_file_name);
		} else {
			imagejpeg($img_data, $img_file_name, 100);
		}
	}


	public function runJavascript(string $script,$mostrar=""): string
	{
		global $usrId;

		$sai = '';
		$jsCommand = '/usr/local/bin/js17 -f ';
		$printCommand = 'print';
		if (!file_exists($jsCommand)) {
			$jsCommand = '/usr/local/bin/js';
			$printCommand = 'print';

			if (!file_exists($jsCommand)) {
				$jsCommand = '/usr/local/bin/node';
				$printCommand = 'console.log';

				if (!file_exists($jsCommand)) {
					$jsCommand = '/usr/bin/node';
					$printCommand = 'console.log';
				}

			}
		}

		$script .= $printCommand."($mostrar)";

		$fileName = "/tmp/gbrowser-run-javascript-$usrId-".$_SERVER['SERVER_ADDR'].".js";
		$fileNameLog = "/tmp/gbrowser-run-javascript-$usrId-".$_SERVER['SERVER_ADDR'].".log";
		file_put_contents($fileName, $script);

		if (file_exists($jsCommand)) {
			jLog("Executando javascript: $fileName", $this->session, 'aviso');
			$cmd = $jsCommand . " $fileName";
			$sai = trim(shell_exec($cmd));
			//$sai=trim(file_get_contents($fileNameLog));

		} else {
			jLog("Executor de javascript não encontrado! Procedimento abortado! $jsCommand", $this->session, 'erro');
		}

		jLog("Resultado do javascript: [$sai]", $this->session, 'aviso');
		return($sai);
	}


	public function javascriptRedirect($html,string $baseUrl='', $getCookie = false, $usePost = true)
	{
		global $usrId;

		$jsCommand = '/usr/local/bin/js17';
		$printCommand = 'print';
		if (!file_exists($jsCommand)) {
			$jsCommand = '/usr/local/bin/node';
			$printCommand = 'console.log';
		}

		// Identifica se o código HTML realmente tem um bloqueio em Javascript (para ser executado somente por um browser real)
		if (stripos((string) $html,"decode_action()") !== false) {
			// Extrai a URL
			$url = substr((string) $html,stripos((string) $html,"action=")+8);
			$url = substr($url,0,stripos($url,'"'));
			$url = str_replace('br//','br/',urldecode($baseUrl.$url));

			// Extrai código javascript
			$script = substr((string) $html,stripos((string) $html,"<script"));
			$script = substr($script,stripos($script, ">")+1);
			$script = substr($script,0,strpos($script,'</'));

			// Extrai campos ocultos
			$campos = $html;
			for ($a = 0; $a < 6; $a++) {
				$campos = substr((string) $campos,stripos((string) $campos,'name=')+6);
				$campo  = substr($campos,0,stripos($campos,'"'));
				$campos = substr($campos,stripos($campos,'value=')+7);
				$valor  = substr($campos,0,stripos($campos,'"'));
				$hidden[$campo] = $valor;
			}

			// Modifica código javascript
			$campo = substr($script,stripos($script,'value=')+6);
			$campo = substr($campo,0,stripos($campo,';'));
			$script = substr($script,0,strrpos($script,"document.forms"));
			$script .= $printCommand."(decode_string($campo));\n";
			$script .= "}\ntest();";
			$script = str_replace("\r","",$script);
			$fileName = "/tmp/gbrowser-javascript-$usrId-".$_SERVER['SERVER_ADDR'].".js";
			$fileNameLog = "/tmp/gbrowser-javascript-$usrId-".$_SERVER['SERVER_ADDR'].".log";
			file_put_contents($fileName, $script);
			if (file_exists($jsCommand)) {
				$cmd = $jsCommand." -f $fileName > $fileNameLog";
				$sai = shell_exec($cmd);
				$string = trim(file_get_contents($fileNameLog));

				$this->post = "";
				if ($usePost) {
					$cnt = 0;
					foreach ($hidden as $key=>$value) {
						$this->post[$key] = $cnt == 1 ? $string : $value;
						$cnt++;
					}
					$this->useHttpBuildQuery = false;
					$this->post($url, "Redirecionamento por javascript");
					$this->useHttpBuildQuery = true;
				} else {
					$this->prepare($url);
					$html = curl_exec($this->ch);
					$this->close();
				}

			} else {
				jLog("Executor de javascript não encontrado! Procedimento abortado! $jsCommand", $this->session, 'erro');
			}
		}
		return($html);
	}


	public function formatDate($dataHora): string
	{
		$txt  = str_replace('&nbsp;',' ',$dataHora);
		$txt  = str_replace("\t",'',$txt);
		$txt  = str_replace("\n",'',$txt);
		$data = substr(trim($txt),0,10);
		$sai  = $data;

		$sep = '-';
		if (str_contains($data, '/')) {
			$sep = '/';
		}

		$dt = explode($sep, $data);
		if ($dt[2] > 1800) {
			$sai = $dt[2]."-".substr('0'.$dt[1],-2)."-".substr('0'.$dt[0],-2);
		} else {
			$sai = "20".$dt[2]."-".substr('0'.$dt[1],-2)."-".substr('0'.$dt[0],-2);
		}

		if (strlen((string) $dataHora) > 10) {
			$sai .= substr((string) $dataHora,10);
		}

		return (trim($sai));
	}


	public function format($txt, $format = 'string')
	{
		if ($txt != '') {
			$txt = superTrim(strip_tags((string) $txt));
			switch ($format) {
				case 'stringFormatted':
					$txt = gUcWords($txt);
				case 'string':
					$txt = str_replace("\n", '', $txt);
					$txt = str_replace('R?u','Réu', $txt);
					break;
				case 'textarea':
				case 'process':
					break;
				case 'date':
					$txt = $this->formatDate(strip_tags($txt));
					break;
			}
		}

		return($txt);
	}


	public function getField($method, $id, $format = "string", $parm = '', $size = 1)
	{
		$sai = [];
		switch ($method) {
			case 'name-select':
				/* Exemplo:
				<select name="sexo">
					<option value="0"> &lt;selecione&gt; </option>
					<option value="MASCULINO">Masculino</option>
					<option selected="selected" value="FEMININO">Feminino</option>
				</select>
				*/
				$achou = false;
				$el = $this->htmlObject->getElementsByTagName('select');
				foreach ($el as $node) {
					if ($node->getAttribute('name')==$id) {
						$achou = true;
						$el2 = $node->getElementsByTagName('option');
						foreach ($el2 as $node2) {
							if ($node2->hasAttribute('selected')) {
								$sai = $this->format($node2->nodeValue, $format);
							}
						}
					}
				}

				if (!$achou) {
					$this->elementNotFound = true;
					$sai = [];
				}

				break;

				case 'name-select-value':
					/* Exemplo:
					<select name="sexo">
						<option value="0"> &lt;selecione&gt; </option>
						<option value="MASCULINO">Masculino</option>
						<option selected="selected" value="FEMININO">Feminino</option>
					</select>
					*/
					$achou = false;
					$el = $this->htmlObject->getElementsByTagName('select');
					foreach ($el as $node) {
						if ($node->getAttribute('name')==$id) {
							$achou = true;
							$el2 = $node->getElementsByTagName('option');
							foreach ($el2 as $node2) {
								if ($node2->hasAttribute('selected')) {
									$sai = $this->format($node2->getAttribute('value'), $format);
								}
							}
						}
					}
					if (!$achou) {
						$this->elementNotFound = true;
						$sai = [];
					}
					break;

			case 'id-select':
				/* Exemplo:
				<select id="sexo">
					<option value="0"> &lt;selecione&gt; </option>
					<option value="MASCULINO">Masculino</option>
					<option selected="selected" value="FEMININO">Feminino</option>
				</select>
				*/
				$el = $this->htmlObject->getElementById($id);
				if (is_object($el)) {
					$el = $el->getElementsByTagName('option');
					foreach ($el as $node) {
						if ($node->hasAttribute('selected')) {
							$sai = $this->format($node->nodeValue, $format);
						}
					}
				} else {
					$this->elementNotFound = true;
					$sai = [];
				}
				break;

			case 'id-select-value':
				/* Exemplo:
					<select id="sexo">
						<option value="0"> &lt;selecione&gt; </option>
						<option value="MASCULINO">Masculino</option>
						<option selected="selected" value="FEMININO">Feminino</option>
					</select>
				*/
				$el = $this->htmlObject->getElementById($id);
				if (is_object($el)) {
					$el = $el->getElementsByTagName('option');
					foreach ($el as $node) {
						if ($node->hasAttribute('selected')) {
							$sai = $this->format($node->getAttribute('value'), $format);
						}
					}
				} else {
					$this->elementNotFound = true;
					$sai = [];
				}
				break;


			# Obtém o conteúdo a partir do ID (tudo que estiver dentro da tag)
			case 'id-input':
				/* Exemplo:
					<input type="hidden" name="__LASTFOCUS" id="__LASTFOCUS" value="" />
				*/
				$el = $this->htmlObject->getElementById($id);
				if (is_object($el)) {
					$sai = $this->format($el->getAttribute('value'), $format);
				} else {
					$this->elementNotFound = true;
					$sai = [];
				}
				break;

			case 'name-input':
				/* Exemplo:
					<input type="hidden" name="__LASTFOCUS" id="__LASTFOCUS" value="" />
				*/
				$achou = false;
				$el = $this->htmlObject->getElementsByTagName('input');
				foreach ($el as $node) {
					if ($node->getAttribute('name') == $id) {
						$achou = true;
						$sai = $node->getAttribute('value');
					}
				}

				if (!$achou) {
					$this->elementNotFound = true;
					$sai = [];
				}
				break;

			# Obtém o conteúdo a partir do ID (tudo que estiver dentro da tag)
			case 'id':

				/* Exemplo:
				<span id="j_id68:processoPartesPoloPassivoResumidoList:0:j_id215">
					<div style="width: 100%;" class="">
						<span style="font-weight: bold">
							SABEMI SEGURADORA SA - CNPJ: 87.163.234/0001-38 (REQUERIDO)
						</span>
					</div>
				</span>
				*/
				$el = $this->htmlObject->getElementById($id);
				if (is_object($el)) {
					$sai = $this->format($this->htmlObject->getElementById($id)->nodeValue, $format);
				} else {
					$this->elementNotFound = true;
					$sai = [];
				}
				break;

			# Obtém o conteúdo a partir do Name (tudo que estiver dentro da tag)
			case 'name':

				/* Exemplo:
				<span name="j_id68:processoPartesPoloPassivoResumidoList:0:j_id215">
					<div style="width: 100%;" class="">
						<span style="font-weight: bold">
							SABEMI SEGURADORA SA - CNPJ: 87.163.234/0001-38 (REQUERIDO)
						</span>
					</div>
				</span>
				*/
				$el = $this->htmlObject;
				$achou=false;
				foreach ($el as $node) {
					if ($node->getAttribute('name') == $id) {
						$achou = true;
						$sai = $node->format($el->nodeValue, $format);
					}
				}

				if (!$achou) {
					$this->elementNotFound = true;
					$sai = [];
				}
				break;

			# Obtém o conteúdo a partir do ID (tudo que estiver dentro da tag), incrementando o Id até não achar mais
			case 'id-inc':

				/* Exemplo:
				<span id="j_id68:processoPartesPoloPassivoResumidoList:0:j_id215">
					<div style="width: 100%;" class="">
						<span style="font-weight: bold">
							SABEMI SEGURADORA SA - CNPJ: 87.163.234/0001-38 (REQUERIDO)
						</span>
					</div>
				</span>
				*/

				$cnt = substr((string) $id, $parm, $size);
				$faz = true;
				while ($faz) {
					$idCnt = substr((string) $id,0, $parm).$cnt.substr((string) $id,$parm+$size);
					$el = $this->htmlObject->getElementById($idCnt);
					if ($el == null) {
						$faz = false;
					} else {
						$sai[] = $this->format($el->nodeValue, $format);
					}
					$cnt++;
				}
				break;

			# Obtém o conteúdo a partir do ID, e dentro dele, a TAG que contém a classe 'value'
			case 'java-value-class':
				/* Exemplo:
				<div id="j_id68:processoTrfViewView:j_id74:j_id75">
					<div class="propertyView" style=" width:  !important; ">
						<div class="name">N&uacute;mero Processo
						</div>
						<div class="value ">
							<div style="width: 100%;" class="">
							0814640-70.2015.8.15.2001
							</div>
						</div>
						<br style="height: 1px; visibility: hidden; margin-bottom: 1px;" />
					</div>
				</div>
				 */
				$tab = $this->htmlObject->getElementById($id);
				if (is_object($tab)) {
					$and = $tab->getElementsByTagName('div');
					for ($i = 0; $i < $and->length; $i++) {
						if (trim((string) $and->item($i)->getAttribute('class')) === 'value') {
							$sai = $this->format($and->item($i)->nodeValue, $format);
						}
					}
				} else {
					$this->elementNotFound = true;
					$sai = [];
				}
				break;
		}
		return($sai);
	}

}


class gBrowserJuridico extends gBrowser
{
	public $jarr;
 	public $ocr     = '';
	public $postTmp = '';
	public $captcha = '';

	public function step_setCookie()
	{
		// Primeiro chama a página só pra salvar o cookie...
		return ($this->get('','Obtendo Cookie'));
	}


	public function step_getCaptcha($optimize = true, $captchaName = 'captchaImg'): void
	{
		// Depois pega o conteúdo da página com o captcha atualizado (já com o cookie)
		$this->get('', 'Obtendo Captcha');
		// Depois obtém o Captcha
		$this->captcha = $this->getCaptcha($captchaName, 'jpeg', $optimize);
	}


	public function step_ocr(): void
	{
		// Faz o OCR
		$this->ocr = $this->ocr('', 'numbers');
	}


	public function step_post($usarDetalhe = true)
	{
		if ($this->viewState != '') {
			$this->post['javax.faces.ViewState']=$this->viewState;
		}

		if ($this->jarr['captchaField'] != '') {
			$this->post[$this->jarr['captchaField']]=$this->ocr;
		}

		$this->post('', 'Enviando parâmetros');

		if (str_contains((string) $this->html, (string) $this->jarr['captchaError'])) {
			$ok = false;
			$this->errorCode = "602";
			$this->errorMessage = "Captcha incorreto!";
			jLog($this->errorCode . ' ' . $this->errorMessage, $this->getSessionNumber(),'error');
		} elseif (str_contains((string) $this->html, (string) $this->jarr['notFound'])) {
			$ok = false;
			$this->errorCode = "601";
			$this->errorMessage = "Não encontrado!";
			jLog($this->errorCode . ' ' . $this->errorMessage, $this->getSessionNumber(),'error');
		} else {
			if ($usarDetalhe) {
				$this->referer = $url;
				// Obtendo o link dos detalhes no meio do conteúdo HTML...
				$url = $this->getElement('a', 'DetalheProcessoConsultaPublica', 'onclick');
				$url = substr($url, strpos($url, ',')+2);
				$url = substr($url, 0, strpos($url,"'"));
				$url = $this->domain . $url;
				$this->enableGzip();
				$this->get($url, 'Obtendo dados do processo');
			}
			$ok = true;
		}

		return($ok);
	}


	public function getData($url)
	{
		$this->postTmp = $this->post;
		$this->setUrl($url);
		// Pega o cookie da sessão
		$this->step_setCookie();
		// Pega o captcha
		$this->step_getCaptcha();
		// Interpreta o captcha
		$this->step_ocr();
		// Envia parâmetros
		$this->post = $this->postTmp;
		$ok = $this->step_post();
		$this->getHtmlObject();

		$this->endSession();
		return($ok);
	}


	public function getCaptchaForm($url)
	{
		$this->setUrl($url);
		// Pega o cookie da sessão
		$this->step_setCookie();
		// Pega o captcha
		$this->step_getCaptcha();
		return($ok);
	}


	public function getDataFromForm()
	{
		// Envia parâmetros
		$ok = $this->step_post();
		$this->endSession();
		return($ok);
	}

}


/** Registra a string enviada no arquivo de log
 * @author	giuliano
 * @version	1.0 27-03-2016
 */
function jLog(string $texto, ?string $prefixo = '', $style = ''): void
{
	global $debug;
	if ($debug !== false) {
		$txt = ($prefixo != ''?$prefixo . "\t":"") . $texto;

		$txt = match ($style) {
			'error' => "\033[1;00;31m" . "[Erro ]\t" . $txt . "\033[1;00;37m",
			'aviso' => "\033[1;00;32m" . "[Aviso]\t" . $txt . "\033[1;00;37m",
			'infor' => "\033[1;00;34m" . "[Infor]\t" . $txt . "\033[1;00;37m",
			'query' => "\033[1;00;33m" . "[Query]\t" . $txt . "\033[1;00;37m",
			default => "[-----]\t" . $txt,
		};

		$txt = date('Y-m-d H:i:s')." [".$_SERVER['SERVER_ADDR'] ."]"." ".$txt;
		if (file_exists('/tmp/ramdisk')) {
			file_put_contents('/tmp/ramdisk/gbrowser.log',$txt."\n",FILE_APPEND);
		} else {
			file_put_contents('/var/log/gbrowser.log',$txt."\n",FILE_APPEND);
		}

	}
}
