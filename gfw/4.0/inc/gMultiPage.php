<?php
/**
 * Este arquivo contém métodos para apresentação de múltiplas páginas usando apenas um arquivo php
 *
 * @author	giuliano
 * @version	1.0 17-12-2012 14:08
 */
include_once $gPathDefault . "gInput.php";

/**
 * Classe responsável pelo tratamento de múltiplas funcionalidades em um arquivo
 * @package	gMultiPage
 * @author	Giuliano Nascimento <giusoft@hotmail.com>
 * @version	4.0 01-12-2013 10:50
 */
class gMultiPage extends gInput
{

	public $actualPage, $pages, $jsons;
	public $breadcrumb = false;

	/**
	 * Adiciona uma nova página
	 * @author	giuliano
	 * @param $json Parâmetros em formato JSON:
	 * @param title: Título da página
	 * @param subTitle: Sub-título da página
	 * @param buttonNextCaption: Texto do botão "Avançar"
	 * @param buttonBackCaption: Texto do botão "Voltar"
	 * @param buttonBack: Número negativo com a quantidade de páginas a voltar
	 * @param info, error, alert ou warning: Mensagem adicional a exibir
	 * @version	4.0 17-12-2013 10:50
	 */
	function add($json, $page)
	{
		if (is_object($page)) {
			$mtz = cssDecode($json);
			$jsn = cssDecode($page->json);
			if ($mtz['title'] <> '')
				$jsn['title'] = $mtz['title'];
			$page->json = cssEncode($jsn);
		}
		$this->pages[] = $page;
		$this->jsons[] = $json;
	}

	/**
	 *
	 * @param type $json
	 * @param type $page
	 */
	function set($json, $page)
	{
		$mtz = cssDecode($json);
		foreach ($jsons as $j) {
			$jsn = cssDecode($j);
			if ($jsn['name'] == $jsn['name']) {
				$this->pages[] = $page;
			}
		}
	}

	/**
	 *
	 * @global type $gOs
	 * @global type $gDevice
	 * @global type $gBASE
	 * @global type $gApp
	 * @param type $fields
	 */
	function render($fields)
	{
		global $gOs, $gDevice, $gBASE, $gApp, $gFWMute, $o;
		$dock = "bottom";
		if ($gDevice == "tablet")
			$dock = "top";

		$pag = intval($fields['gPage']);
		$jsn = cssDecode($this->jsons[$pag]);

		$nomeBotaoAvancar = gT('Avançar');
		if ($jsn['buttonNextCaption'] <> '')
			$nomeBotaoAvancar = $jsn['buttonNextCaption'];
		$nomeBotaoVoltar = gT('Voltar');
		if ($jsn['buttonBackCaption'] <> '')
			$nomeBotaoVoltar = $jsn['buttonBackCaption'];
		$voltar = "-1";
		if ($jsn['buttonBack'] <> '')
			$voltar = $jsn['buttonBack'];
		$html = '';
		$instr = $jsn['instructions'];
		$name = "gForm";
		$flds = '';
		$html = '';
		if ($jsn['name'] <> '')
			$name = $jsn['name'];
		if ($jsn['title'] <> '')
			$html.=$this->msgTitle($jsn['title']);
		if (!$this->breadcrumb) {
			if ($jsn['subTitle'] <> '')
				$html.=$this->msgSubTitle($jsn['subTitle']);
		}
		if ($jsn['info'] <> '')
			$html.=$this->msgInfo($jsn['info']);
		if ($jsn['alert'] <> '')
			$html.=$this->msgAlert($jsn['alert']);
		if ($jsn['warning'] <> '')
			$html.=$this->msgWarning($jsn['warning']);
		if ($jsn['error'] <> '')
			$html.=$this->msgError($jsn['error']);
		$html = $this->out($html);
		if ($this->breadcrumb) {
			// Busca campos...
			$flds = '';
			foreach ($_REQUEST as $key => $value) {
				if (($key <> 'gPage') && ($key <> 'g') && ($key <> 'PHPSESSID')) {
					$breadcrumbFlds.="&$key=$value";
				}
			}

			// Monta breadcrumb
			$breadcrumb = '<ol class="breadcrumb">' . $this->n;
			for ($a = 0; $a < $pag; $a++) {
				$tjsn = cssDecode($this->jsons[$a]);
				$breadcrumb.='<li><a href="' . $this->page . '&gPage=' . $a . $breadcrumbFlds . '">' . $tjsn['subTitle'] . '</a></li>' . $this->n;
			}
			$tjsn = cssDecode($this->jsons[$a]);
			$breadcrumb.='<li class="active">' . $tjsn['subTitle'] . '</li>' . $this->n;
			$breadcrumb.='</ol>' . $this->n;
			$html.=$this->out($breadcrumb);
		}

		if (!is_object($this->pages[$pag])) {
			// HTML...
			$fvoltar = "";
			$favancar = "";
			$mostrouAvancar = false;
			$mostrouVoltar = false;
			if ($this->breadcrumb) {
				if ($pag > 0) {
					if ($nomeBotaoVoltar <> 'no') {
						$mostrouVoltar = true;
						$fvoltar.=$o->button("{caption: '$nomeBotaoVoltar'; type: button;}", "window.location.href='" . $this->page . "&gPage=" . ($pag - 1) . $breadcrumbFlds . "';");
					}
				}
				if ($pag < count($this->pages) - 1) {
					if ($nomeBotaoAvancar <> 'no') {
						$mostrouAvancar = true;
						$favancar.=$o->button("{caption: '$nomeBotaoAvancar'; type: button; style: primary; url: document.nextForm.submit();}", "window.location.href='" . $this->page . "&gPage=" . ($pag + 1) . $breadcrumbFlds . "';");
					}
				}
			} else {
				$fvoltar = "<form name='backForm' style='display: inline' action=" . $this->page . " method='post'>";
				$fvoltar.="<input type='hidden' name='gPage' value='" . ($pag - 1) . "'>";
				$favancar = "<form name='nextForm' style='display: inline' action=" . $this->page . " method='post'>";
				$favancar.="<input type='hidden' name='gPage' value='" . ($pag + 1) . "'>";
				foreach ($fields as $key => $value) {
					if (($key <> 'gPage') && ($key <> 'PHPSESSID')) {
						$fvoltar.='<input type="hidden" id="' . $key . '" name="' . $key . '" value="' . $value . '">';
						$favancar.='<input type="hidden" id="' . $key . '" name="' . $key . '" value="' . $value . '">';
					}
				}
				foreach ($_REQUEST as $key => $value) {
					if (($key <> 'gPage') && ($key <> 'PHPSESSID') && (empty($fields[$key]))) {
						$fvoltar.="<input type='hidden' name='$key' value='$value'>";
					}
				}
				if ($pag > 0) {
					if ($nomeBotaoVoltar <> 'no') {
						$mostrouVoltar = true;
						if ($fields['gPageBack'] <> '' && is_numeric($voltar))
							$fvoltar.=$o->button("{caption: '$nomeBotaoVoltar'; tag: button;}", "window.location.href=\"" . $this->page . "&gPage=" . ($pag - 1) . "&" . base64_decode($fields['gPageBack']) . "\";");
						else
						{
							if (is_numeric($voltar))
								$fvoltar=$o->button("{caption: '$nomeBotaoVoltar'; tag: button; url: history.go($voltar)}");
							else
							{
								$fvoltar=$o->button("{caption: '$nomeBotaoVoltar'; tag: button;}", "window.location.href='" . $voltar . "';");
							}
						}
					}
				}
				if ($pag < count($this->pages) - 1) {
					if ($nomeBotaoAvancar <> 'no') {
						$mostrouAvancar = true;
						$favancar.=$o->button("{caption: '$nomeBotaoAvancar'; tag: button; style: primary; url: document.nextForm.submit();}");
					}
				}
				$fvoltar.="</form>";
				$favancar.="</form>";
			}
			$html.=$this->out($this->pages[$pag]);
			$html.=$this->out($fvoltar . $favancar);
		} else {
			// Formulário
			$frm = $this->pages[$pag];
			$mtz = $frm->jarr;
			$instr = $mtz['instructions'];
			$this->pageTitle = $mtz['title'];
			$frm->add("{name: 'gPage'; type: hidden; value: '" . ($pag + 1) . "'}");
			$flds = $frm->fields;
			foreach ($fields as $key => $value) {
				if (($key <> 'gPage') && ($key <> 'PHPSESSID')) {
					$tem = false;
					foreach ($flds as $fld) {
						if ($fld['name'] == $key)
							$tem = true;
					}
					if (!$tem)
						$frm->add("{name: '$key'; type: hidden; value: '$value'}");
				}
			}
			$frm->setButtonNextCaption($nomeBotaoAvancar);
			$frm->setButtonBackCaption($nomeBotaoVoltar);
			$html = $this->out($frm->render());
		}
		$this->body($html);
	}

	function onBeforeShowPage($fields)
	{
		$this->actualPage = $fields['gPage'];
		return($fields);
	}

	/**
	 * Mostra a página correspondente (gPage)
	 * @author	giuliano
	 * @param $json Parâmetros em formato JSON:
	  title: Título da página
	  breadcrumb: Mostrar barra superior caminho de links (true/false)

	 * @version	4.0 17-12-2013 10:50
	 */
	function showMultiPage($json)
	{
		global $html, $gFWMute, $gPage;
		// A condição abaixo apaga o conteúdo da variável html tendo em vista que o código antigo gfw3 pode deixar
		// conteúdo nesta variável e a mesma é utilizada pelo index.php para o código gfw4
		if ($gFWMute===true)
			$html='';
		$jarr = cssDecode($json);
		if (($jarr['breadcrumb'] == 'true') || ($jarr['breadcrumb'] == 'on'))
			$this->breadcrumb = true;
		$this->showToolBar = false;
		foreach ($_GET as $key => $value) {
			$fields[$key] = trim($value);
		}
		foreach ($_POST as $key => $value) {
			$fields[$key] = trim($value);
		}
		$fields = $this->onBeforeShowPage($fields, $gPage);
		$this->render($fields);
	}
}

?>
