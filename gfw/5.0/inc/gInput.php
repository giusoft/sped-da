<?php
/**
 * Este arquivo contém a estrutura padrão para entrada de dados
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */
include_once $gPathDefault . "gOutput.php";

/**
 * Classe responsável pela criação de um menu (vertical[menu] ou horizontal[bar])
 * @package	gMenu
 * @author	Giuliano Nascimento <giusoft@hotmail.com>
 * @version	4.0 27-12-2014 12:14
 */
class g_Menu extends g_Stdout
{
	public $items = [];
	public $lists = [];
	public $pres  = [];
	public $separatorCount = 0;

	/**
	 * Adiciona um campo ao menu (sem exibí-lo)
	 * @author	giuliano
	 * @param string $json Parâmetros pra criação do campo em formato JSON {title: titulo; hint: dica de ajuda; level: nivel; icon: ícone; url: link}
	 * @param string $list Utilizado somente para Combolist (select) - array de elementos
	 * @param string $pre Mostra este HTML antes do texto
	 * @version	4.0 27-01-2014 12:19
	 */
	public function add($json, $list = "", $pre = ""): void
	{
		if (trim($json) !== "") {
			$mtz = cssDecode($json);
			$this->items[] = $mtz;
		}

		$this->pres[]  = $pre;
		$this->lists[] = $list;
	}

	/**
	 * Obtém array contendo os campos do formulário
	 * @author	giuliano
	 * @param string $js Código javascript
	 * @version	4.0 01-12-2013 10:50
	 */
	public function get()
	{
		return($this->items);
	}

	/**
	 * Gera saída formatada em HTML do formulário
	 * @author	giuliano
	 * @param $output class Objeto output a renderizar
	 * @version	4.0 01-12-2013 10:50
	 */
	public function render(&$output = '', $extra='')
	{
		global $gDevice;

		$jarr   = $this->jarr;
		$type   = 'bar';
		$style  = '';
		$title  = '';
		$logo   = '';
		$fixed  = '';
		$icon   = '';
		$url    = '';
		$sai    = '';
		$addCss = '';

		$this->separatorCount++;

		if ($jarr['style'] != '') {
			$style = $jarr['style'];
		}

		if ($jarr['title'] != '') {
			$title = $jarr['title'];
		}

		if ($jarr['type'] != '') {
			$type = $jarr['type'];
		}

		if ($jarr['logo'] != '') {
			$logo = $jarr['logo'];
		}

		if ($jarr['url'] != '') {
			$url = $jarr['url'];
		}

		if ($jarr['fixed'] != '') {
			$fixed = $jarr['fixed'];
		}

		if ($jarr['maxHeight'] != '') {
			$addCss .= '; maxHeight: '.$jarr['maxHeight'];
		}

		if ($jarr['icon'] != '') {
			$icon = $jarr['icon'];
		}

		if (!is_object($output)) {
			$output = new gOutput();
		}

		// Preparando items para o nav
		$dd = false;
		$ddTitle   = '';
		$ddIcon    = '';
		$ddLinks   = [];
		$ddUrl     = '';
		$ddType    = $type;
		$ddPopover = [];
		$ddsTitle  = '';
		$ddsIcon   = '';
		$ddsLinks  = [];
		$ddsUrl    = '';
		$ddsType   = $type;

		foreach ($this->items as $key => $item) {
			switch ($item['type']) {
				case 'html':
					if (
						($ddsLinks)
						&& (($ddsTitle != '')
						|| ($ddsIcon != ''))
					) {
						//$items[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
						$ddLinks[$ddsIcon.$ddsTitle]=$output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
						$ddsLinks = [];
						$ddsTitle = '';
					}

					$items[$this->lists[$key]] = '';
					break;

				case 'dropdown':
					if (
						($ddsLinks)
						&& (($ddsTitle != '')
						|| ($ddsIcon != ''))
					) {
						//$items[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
						$ddLinks[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
						$ddsLinks = [];
						$ddsTitle = '';
					}

					if (
						($ddLinks)
						&& (($ddTitle != '')
						|| ($ddIcon != ''))
					) {
						$items[$ddIcon.$ddTitle] = $output->dropdown("{title: " . $ddIcon.$ddTitle . "; type: " . $ddType. "; url: " . $ddUrl . ";".$ddPopover."}", $ddLinks, $ddPopover);
						$ddLinks   = [];
						$ddTitle   = '';
						$ddPopover = [];
					}

					$ddTitle = gT($item[ 'title' ]);
					$ddUrl   = $item[ 'url' ];
					$ddLinks = [];
					$ddIcon  = $this->chooseIcon($item['icon']);
					break;

				case 'separator':
					if (
						($ddsLinks !== [])
						&& (($ddsTitle != '')
						|| ($ddsIcon != ''))
					) {
						//$items[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
						$ddLinks[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
						$ddsLinks = [];
						$ddsTitle = '';
					}

					$ddLinks['sep'.$this->separatorCount] = '-';
					$this->separatorCount++;
					break;

				case 'dropdownLink':
					if (
						($ddsLinks !== [])
						&& (($ddsTitle != '')
						|| ($ddsIcon != ''))
					) {
						//$items[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
						$ddLinks[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
						$ddsLinks = [];
						$ddsTitle = '';
					}

					$icon = $this->pres[$key].$this->chooseIcon($item['icon']);
					$ddLinks[$icon.gT($item['title'])] = $item['url'];
					$ddPopover[$icon.gT($item['title'])] = gT($item['popover']);
					break;

				case 'submenu':
					if (
						($ddsLinks !== [])
						&& (($ddsTitle != '')
						|| ($ddsIcon != ''))
					) {
						//$items[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
						$ddLinks[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
						$ddsLinks = [];
						$ddsTitle = '';
					}

					$ddsTitle = gT($item[ 'title' ]);
					$ddsUrl   = $item[ 'url' ];
					$ddsLinks = [];
					$ddsIcon  = $this->chooseIcon($item['icon']);
					break;

				case 'submenuLink':

					if ($output->bootstrapVersao == 3) {
						$icon = $this->chooseIcon($item['icon']);
						$ddsLinks[$icon.gT($item['title'])] = $item['url'];

					} else {
						if (
							($ddsLinks !== [])
							&& (($ddsTitle != '')
							|| ($ddsIcon != ''))
						) {
							//$items[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
							$ddLinks[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
							$ddsLinks = [];
							$ddsTitle = '';
						}

						$icon = $this->pres[$key].$this->chooseIcon($item['icon']);
						$ddLinks[$icon.gT($item['title'])] = $item['url'];
						$ddPopover[$icon.gT($item['title'])] = gT($item['popover']);

					}
					break;

				default:
					if (
						($ddLinks !== [])
						&& (($ddTitle != '')
						|| ($ddIcon != ''))
					) {
						$items[$ddIcon.$ddTitle] = $output->dropdown("{title: " . $ddIcon.$ddTitle . "; type: " . $ddType. "; url: " . $ddUrl . "}", $ddLinks,$ddPopover);
						$ddLinks   = [];
						$ddTitle   = '';
						$ddIcon    = '';
						$ddPopover = [];
					}

					$icon = $this->chooseIcon($item['icon']);
					$iurl = $item['url'];
					if ($iurl == '') {
						$iurl = '#';
					}

					$items[$icon.gT($item['title'])] = $iurl;
					break;
			}
		}

		if (
			($ddsLinks !== [])
			&& (($ddsTitle != '')
			|| ($ddsIcon != ''))
		) {
			//$items[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
			$ddLinks[$ddsIcon.$ddsTitle] = $output->submenu("{title: " . $ddsIcon.$ddsTitle . "; type: " . $ddsType. "; url: " . $ddsUrl . "}", $ddsLinks);
		}

		if (
			($ddLinks !== [])
			&& (($ddTitle != '')
			|| ($ddIcon != ''))
		) {
			$items[$ddIcon.$ddTitle] = $output->dropdown("{title: " . $ddIcon.$ddTitle . "; type: " . $ddType. "; url: " . $ddUrl . "}", $ddLinks);
		}

		if (
			$output->bootstrapVersao == 3
			&& ($jarr['style'] == ''
			|| $jarr['style'] == 'default')
		) {
			$jarr['style'] = 'dark';
		}

		if ($this->debug) {
			$sai = "\n\n\n<!-- Navbar / START -->\n\n";
		}

		$sai .= '<div class="'.$output->bootstrapTags['hidden-print'].'" id="menuContent">'."\n";
		$sai .= $output->nav("{title: $title; logo: $logo; type: $type; style: $style; fixed: $fixed; url: $url ".$addCss."}", $items, $extra);
		$sai .= '</div>'."\n";
		if ($this->debug) {
			$sai .= "\n<!-- Navbar / END -->\n\n";
		}

		$sai .= $js;
		return($sai);
	}
}


/**
 * Class que cria um componente Calendário
 * @package	gCalendar
 * @author	giuliano
 * @version	1.0 08-02-2012 15:49
 */
class gCalendar
{

	public $buffer = "";
	public $events = '', $dayEvents = '';
	public $json = '', $today = '';
 	public $url = '';
	public $mtz, $meses;

	function __construct($json = "")
	{
		$this->json = $json;
		$this->mtz  = cssDecode($this->json);
		$this->url  = $_SERVER["PHP_SELF"] . "?g=" . $_REQUEST['g'] . "&calShow=" . $_REQUEST['showCal'];

		$hoje = date("Y-m-d H:i:s");
		if (!empty($this->mtz['today'])) {
			$hoje = $this->mtz['today'];
		}

		if ($_REQUEST['date'] != '') {
			$hoje = date("Y-m-d H:i:s", strtotime((string) $_REQUEST['date']));
		}

		$this->today = $hoje;
		$this->meses[] = '';
		$this->meses[] = gT('jan.long');
		$this->meses[] = gT('feb.long');
		$this->meses[] = gT('mar.long');
		$this->meses[] = gT('apr.long');
		$this->meses[] = gT('may.long');
		$this->meses[] = gT('jun.long');
		$this->meses[] = gT('jul.long');
		$this->meses[] = gT('aug.long');
		$this->meses[] = gT('sep.long');
		$this->meses[] = gT('oct.long');
		$this->meses[] = gT('nov.long');
		$this->meses[] = gT('dec.long');
	}


	/**
	 *
	 * @param type $evento
	 */
	public function add($evento): void
	{

		$readOnly = false;
		if (!empty($mtz['readOnly'])) {
			$readOnly = (strtolower((string) $mtz['readOnly']) === "true");
		}

		$json = cssDecode($evento);
		$json['dateStart'] = substr((string) $json['dateStart'], 0, 16);
		$json['dateEnd']   = substr($json['dateEnd'], 0, 16);

		$img = '';
		if ($json['image'] != '') {
			$img = $json['image'];
		}

		$url = "";
		if ($json['url'] != '') {
			$url = "&" . $json['url'];
		}

		$url = "<a href='" . $this->url . "edit&date=" . $json['dateStart'] . "&gId=" . $json['id'] . $json['gId'] . "$url' class='g-cal-day-url'>";
		$this->events[$json['dateStart']] = [$json['title'], $json['details'], $img, $json['dateEnd'], $url];
	}

	public function get(): void
	{
		// Day

		$mtz = $this->mtz;

		$hIni = 8;
		$hFim = 18;
		$hSal = 60;

		$readOnly = false;
		if (!empty($mtz['readOnly'])) {
			$readOnly = (strtolower((string) $mtz['readOnly']) === "true");
		}

		if (!empty($mtz['startTime'])) {
			$hIni = intval($mtz['startTime']);
		}

		if (!empty($mtz['endTime'])) {
			$hFim = intval($mtz['endTime']);
		}

		if (!empty($mtz['stepTime'])) {
			$hSal = intval($mtz['stepTime']);
		}

		if (($hIni < 0) || ($hIni > 23)) {
			$hIni = 8;
		}

		if (($hFim < $hIni) || ($hFim > 23)) {
			$hIni = 23;
		}

		if (($hSal < 1) || ($hSal > 60)) {
			$hIni = 60;
		}

		$purl = "";
		if ($mtz['url'] != '') {
			$purl = "&" . $mtz['url'];
		}

		if (!empty($mtz['startTime'])) {

			for ($h = $hIni; $h <= $hFim; $h++) {
				$hora = str_pad($h, 2, '0', STR_PAD_LEFT) . ":";

				for ($s = 0; $s < 60; $s+=$hSal) {
					$horaMinuto = $hora . str_pad($s, 2, '0', STR_PAD_LEFT);
					$url = '';

					if (!$readOnly) {
						if ((substr((string) $this->today, 0, 10) . " " . $horaMinuto) >= date("Y-m-d H:i")) {
							$url = "<a href='" . $this->url . "new&date=" . substr((string) $this->today, 0, 10) . " " . $horaMinuto . "$purl' class='g-cal-day-url'>";
						} else {
							$url = "";
						}
						//gLog("==================== ".($this->today." ".$horaMinuto));
					}

					$this->dayEvents[$horaMinuto] = ['', '', '', '', $url];
				}

			}

		}

		foreach ($this->events as $diaHora => $evento) {
			if (substr((string) $this->today, 0, 10) === substr((string) $diaHora, 0, 10)) {
				$hora = substr((string) $diaHora, 11, 5);
				/*
				  foreach ($this->dayEvents as $key=>$value)
				  {
				  if (($key>$hora) && ($key<substr($evento[3],11,5)))
				  unset($this->dayEvents[$key]);
				  }
				 *
				 */
				$this->dayEvents[$hora] = $evento;
			}
		}
	}

	/**
	 *
	 * @param type $m
	 * @param type $checaHoje
	 * @return type
	 */
	public function calendar($m, $checaHoje = true): string
	{
		$sem = [];
		$sem[] = gT("sun");
		$sem[] = gT("mon");
		$sem[] = gT("tue");
		$sem[] = gT("wed");
		$sem[] = gT("thu");
		$sem[] = gT("fri");
		$sem[] = gT("sat");

		$sai .= "<div style='padding: 4px'>";
		$sai .= "<table class='table table-condensed'>";
		$sai .= "<tr>";
		if ($checaHoje) {
			$sai .= "<td></td>";
		}

		for ($ds = 0; $ds < 7; $ds++) {
			$sai .= "<td>";
			$sai .= $sem[$ds];
			$sai .= "</td>";
		}

		$sai .= "</tr>";
		$a = 0;
		$dataIni = substr((string) $this->today, 0, 8) . "01";
		$dtt = explode("-", substr($dataIni, 0, 10));

		while(date("w", strtotime($dataIni)) != 0) {
			$dt = explode("-", substr($dataIni, 0, 10));
			$dataIni = date("Y-m-d", mktime(0, 0, 0, $dt[1], $dt[2] - 1, $dt[0]));
		}

		$lnk   = $this->url . "day&date=";
		$datas = '';
		foreach ($this->events as $ev => $value) {
			$datas[substr((string) $ev, 0, 10)] = "1";
		}

		$outroMes = -2;
		$meses = $this->meses;
		for ($l = 0; $l < 6; $l++) {
			$sai .= "<tr>";
			$a++;
			if ($checaHoje) {
				$dataTmp = date("Y-m-d", mktime(0, 0, 0, $dtt[1] + $outroMes, $dtt[2], $dtt[0]));
				$sai .= "<td class=''>";
				$sai .= "<a href='$lnk$dataTmp' class='btn btn-default btn-xs' style='width: 80px'>" . strtoupper(substr((string) $meses[intval(substr($dataTmp, 5, 2))], 0, 3)) . "</a>";
				$sai .= "</td>";
			}

			$outroMes++;
			for ($ds = 0; $ds < 7; $ds++) {
				$dt  = explode("-", substr($dataIni, 0, 10));
				$css = $pre = $pos = $sty = "";
				if (date("w", strtotime($dataIni)) == 0) {
					$css = "";
					$sty = "style='color: #ddd'";
				}

				if (($dataIni === substr((string) $this->today, 0, 10)) && $checaHoje){
					$css = "label label-warning";
				}

				if ($dataIni === date("Y-m-d")){
					$css = "label label-primary";
				}

				if ($datas[$dataIni] === "1"){
					$css .= "label label-default";
				}

				if ($dt[1] !== substr((string) $this->today, 5, 2)){
					$css = "";
					$sty = "style='color: #ddd'";
				}

				$sai .= "<td id='sp$m$a' class='text-center'>".$pre;
				$sai .= "<a href='$lnk$dataIni' class='$css' $sty>" . intval($dt[2]) . "</a>";
				$sai .= $pos."</td>";
				$dataIni = date("Y-m-d", mktime(0, 0, 0, $dt[1], $dt[2] + 1, $dt[0]));
				$a++;
			}

			$sai .= "</tr>";
		}

		$sai .= "<tr>";
		$sai .= "</tr>";
		$sai .= "</table>";
		$sai .= "</div>";
		return ($sai);
	}


	/**
	 *
	 * @return type
	 */
	public function day(): string
	{
		global $o;

		$this->get();
		$sai .= "<table class='table table-condensed g-cal-day'>";
		//$sai.="<tr class='g-cal-day-event'><td class='g-cal-day-time'></td><td class='g-msg-subtitle' >".today($this->today)."</td></tr>";
		foreach ($this->dayEvents as $hora => $evento) {

			$horaFim = substr((string) $evento[3], 11, 5);
			if (($hora == $horaFim) || ($horaFim == "00:00") || ($horaFim == "")) {
				$horaFim = '';
			} else {
				$horaFim = gT(" às ") . $horaFim;
			}

			$sai .= "<tr class=''>";
			//$sai.="<td class='g-cal-day-time'>".$hora." ".$horaFim."</td>";
			$sai .= "<td class='text-right'>" . $hora . "</td>";
			$sai .= "<td class=''>";
			if ($evento[4] != '') {
				$sai .= $evento[4];
			}

			$sai .= "<span class=''>";
			if ($evento[2] != '') {
				$sai .= "<img src='" . $o->imagePath($evento[2]) . "' class='g-image' style='padding-right:3px'>" . $evento[0] . "&nbsp;";
			} else {
				$sai .= $evento[0] . "&nbsp;";
			}

			$sai .= "</span>" . $evento[1] . "&nbsp;";
			if ($evento[4] != '') {
				$sai .= "</a>";
			}

			$sai .= "</td>";
			$sai .= "</tr>";
		}
		//$sai.="<tr class='g-cal-day-event'><td class='g-cal-day-time'></td><td class='g-cal-day-detail' style='padding: 0px'></td></tr>";
		$sai .= "</table>";
		return($sai);
	}


	/**
	 *
	 * @global type $gDevice
	 * @return type
	 */
	public function showDay()
	{
		global $gDevice;

		if ($_REQUEST["calShow"] == "year") {
			$sai = $this->showYear();
		} elseif ($_REQUEST["calShow"] == "month") {
			$sai = $this->showMonth();
		} else {
			$sai = "";
			$lnk = $this->url;
			$dt  = explode(gT("monthyearseparator"), today($this->today));
			$ds  = explode(",", $dt[0]);

			$sai .= '<div class="row">';
			$sai .= '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-6" style="font-size: 62pt"><span class="label label-warning">';
			$sai .= intval(substr((string) $this->today, 8, 2));
			$sai .= "</span></div>";
			$sai .= "<div class='col-lg-4 col-md-4 col-sm-4 col-xs-6'><h2>";
			$sai .= $ds[0] . "</h2>";
			$sai .= "<a class='btn btn-default btn-lg' href='{$lnk}month&date={$this->today}'>" . ucfirst($dt[1]) . "</a>&nbsp;";
			$sai .= "<a class='btn btn-default btn-lg' href='{$lnk}year&date={$this->today}'>" . ucfirst($dt[2]) . "</a><br>";
			$sai .= "</div>";
			$sai .= "<div class='col-lg-4 col-md-4 col-sm-4 col-xs-12'>";
			$sai .= $this->calendar(intval(substr((string) $this->today, 5, 2)));
			$sai .= "</div>";
			$sai .= $this->day();
			$sai .= "</div>";
		}

		return($sai);
	}


	/**
	 *
	 * @return type
	 */
	public function showMonth()
	{
		global $o;

		if ($_REQUEST["calShow"] == "year") {
			$sai = $this->showYear();
		} elseif ($_REQUEST["calShow"] == "day") {
			$sai = $this->showDay();
		} else {
			$sai .= "<div style='padding: 3px; background: white'><table class='table table-condensed' width='100%'>";
			$this->get();
			$lnk = $this->url;
			$dt  = explode(gT(" de "), today($this->today));
			//$sai.="<h1>".$this->today."</h1>";
			$sai .= "<table class='table table-condensed g-cal-day'>";
			$sai .= "<tr><td colspan='3'><button class='btn btn-lg btn-primary'>" . ucfirst($dt[1]) . gT(" de "). '</button>';
			$sai .= "<a class='btn btn-lg btn-primary' href='{$lnk}year&date={$this->today}'>" . ucfirst($dt[2]) . "</a><br>&nbsp;";

			$sai .= "</td></tr>";

			$day = '';
			$fez = false;
			foreach ($this->events as $hora => $evento) {

				if (substr((string) $hora, 0, 7) === substr((string) $this->today, 0, 7)) {
					$fez = true;
					if ($day !== substr((string) $hora, 0, 10)) {
						$dt = explode(gT(" de "), today(str_replace("h",":00:00",$hora)));

						$sai .= "<tr><td colspan='3'>";
						$sai .= "<a class='btn btn-sm btn-default' style='width: 160px' href='{$lnk}day&date={$hora}'>" . ucfirst($dt[0]) . "</a>";
						$sai .= "</td></tr>";
					}

					$day = substr((string) $hora, 0, 10);

					$sai .= "<tr>";
					$sai .= "<td>";

					if ($evento[4] != '') {
						$sai .= $evento[4];
					}

					$sai .= "<span>";

					if ($evento[2] != '') {
						$sai .= "<img src='" . $o->gImagePath($evento[2]) . "' class='g-image' style='padding-right:3px'>" . $evento[0] . "&nbsp;";
					} else {
						$sai .= $evento[0] . "&nbsp;";
					}

					$sai .= "</span>" . $evento[1] . "&nbsp;";
					if ($evento[4] != '') {
						$sai .= "</a>";
					}

					$sai .= "</td>";
					$horaFim = substr((string) $evento[3], 11, 5);

					if (
						(substr((string) $hora, 11, 5) === $horaFim)
						|| ($horaFim === "00:00") || $horaFim === ''
					) {
						$horaFim = '';
					} else {
						$horaFim = ' '.gT("às").' ' . $horaFim;
					}

					$sai .= "<td>" . substr((string) $hora, 11, 5) . $horaFim . "</td>";
					$sai .= "</tr>";
				}
			}

			if (!$fez) {
				$sai .= "<tr><td><span class='alert alert-warning'>" . gT("Nenhum evento encontrado") . "</span></td></tr>";
			}

			$sai .= "</table>";
			$sai .= "</div>";
		}

		return($sai);
	}


	/**
	 *
	 * @return type
	 */
	public function showYear()
	{
		if ($_REQUEST["calShow"] == "day") {
			$sai = $this->showDay();
		} elseif ($_REQUEST["calShow"] == "month") {
			$sai = $this->showMonth();
		} else {
			$lnk = $this->url;
			$anoPassado = (intval(substr((string) $this->today, 0, 4)) - 1) . substr((string) $this->today, 4);
			$anoProximo = (intval(substr((string) $this->today, 0, 4)) + 1) . substr((string) $this->today, 4);
			$dt  = explode(gT(" de "), today($this->today));
			$ds  = explode(",", $dt[0]);

			$sai .= '<div class="row">';
			$sai .= '<div class="col-lg-7 col-md-8 col-sm-12 col-xs-12">';

			$sai .= "<div style='padding: 3px; background: white'><table width='100%'>";
			$sai .= "<table style='padding: 4px'>";
			$sai .= "<tr>";
			$sai .= "<td align='center'><a href='{$lnk}year&date=$anoPassado' class='btn btn-lg btn-default'>" . (intval(substr((string) $this->today, 0, 4)) - 1) . "</a></td>";
			$sai .= "<td align='center'><button class='btn btn-lg btn-primary'>" . substr((string) $this->today, 0, 4) . "</button></td>";
			$sai .= "<td align='center' ><a href='{$lnk}year&date=$anoProximo' class='btn btn-lg btn-default'>" . (intval(substr((string) $this->today, 0, 4)) + 1) . "</a></td>";
			$sai .= "</tr>";
			$m  = 0;
			$meses = $this->meses;
			$js = "";
			$today = $this->today;
			$y = substr((string) $today, 0, 4);

			for ($l = 0; $l < 4; $l++) {

				$sai .= "<tr valign='top'>";
				for ($c = 0; $c < 3; $c++) {
					$m++;
					$this->today = $y . "-" . str_pad($m, 2, "0", STR_PAD_LEFT) . "-01";
					$sai .= "<td width='33%' align='center'>";

					if (substr((string) $today, 5, 2) === substr($this->today, 5, 2)) {
						$this->today = $today;
						$sai .= "<a class='btn btn-sm btn-primary' href='{$lnk}month&date={$this->today}'>" . ucfirst((string) $meses[$m]) . "</a>";
					} else {
						$sai .= "<a class='btn btn-sm btn-default' href='{$lnk}month&date={$this->today}'>" . ucfirst((string) $meses[$m]) . "</a>";

					}

					$sai .= $this->calendar($m, false);
					$sai .= "<br></td>";
				}

				$sai .= "</tr>";
			}

			$sai .= "</table>";
			$sai .= "<br></div>";

			$sai .= '</div>';
			$sai .= '<div class="col-lg-5 col-md-4 col-sm-12 col-xs-12">';

			foreach ($this->events as $hora => $evento) {
				$sai .= '&nbsp;'.gDateTime($hora).' » '.$evento[0].'<br>';
			}

			$sai .= '</div>';
			$sai .= '</div>';
			$this->today = $today;
		}

		return($sai);
	}
}


// Funções úteis
/**
 * Testa se um array é associativo
 * @param type $array
 * @return type
 */
function is_assoc($array): bool
{
	return (is_array($array) && [] !== array_diff_key($array, array_keys(array_keys($array))));
}


/** Class que cria um componente Table Layout (Para compatibilidade com gFW3)
 * @author	giuliano
 * @version	1.0 25-06-2015 09:27
 */
class gTableLayout
{
	private $items;
	private $name;
	private $param;
	private $columns;

	public function __construct($json)
	{
		$mtz = cssDecode($json);
		$this->name = $mtz['name'];
		$mtz = cssRemove($mtz, "name");
		$this->columns = $mtz['columns'];
		$mtz = cssRemove($mtz, "columns");

		if (intval($this->columns) == 0) {
			$this->columns = 2;
		}

		$this->param = $mtz;
	}


	public function add($json): void
	{
		$this->items[] = $json;
	}


	public function get(): string
	{
		$sai = "";
		$n = "\n";
		$sai .= $n . '<!-- gTableLayout --><br>' . $n;

		if ($this->param['title']) {
			$sai .= '<h1 style="text-align: center">'.$this->param['title'].'</h1><hr>';
		}

		//$sai.='<div style="margin: 8px" class="panel panel-default"><div class="panel-body">'.$n;
		$tam = intval(12/$this->columns);
		$cnt = 0;
		foreach ($this->items as $it) {

			$items = '';
			if (is_array($it)) {
				$items = $it;
			} else {
				$items[] = $it;
			}

			foreach ($items as $item) {
				$cnt++;
				if ($cnt/$this->columns == intval($cnt/$this->columns)) {

					if ($cnt > 1) {
						$sai .= '</div>'.$n;
					}

					$sai .= '<div class="row">'.$n;
				}

				$sai .= '	<div class="col-lg-'.$tam.' col-md-'.$tam.' col-sm-6 col-xs-12">'.$n;
				$sai .= $item.$n;
				$sai .= '	</div>'.$n;
			}
		}

		//$sai.='</div>'.$n;
		$sai  .=  '</div></div>'.$n;
		$sai  .=  $n;
		return $sai;
	}


	public function render(): void
	{
		global $out;

		$out->buffer .= $this->get();
	}

}
$device = $gDevice;
$device = "web";

if ($device === "web") {
	$tipo = 0;
	if ($_REQUEST['gPDF'] == 1) {
		$tipo = 1;
	}

	if ($_REQUEST['gXLS'] == 1) {
		$tipo = 2;
	}

	if ($_REQUEST['gDOC'] == 1) {
		$tipo = 3;
	}

	if ($_REQUEST['gCSV'] == 1) {
		$tipo = 4;
	}

	if ($_REQUEST['gXML'] == 1) {
		$tipo = 5;
	}

	switch ($tipo) {
		case 1:
			include_once $gPath . "gfw/inc/gExportPdf.php";
			class gInput extends gPdf {}
			class gMenu extends g_Menu {}
			class gForm {
				function add(){}
				function row(){}
				function render(){}
			}
			break;

		case 2:
			include_once $gPath . "gfw/inc/gExportXls.php";
			class gInput extends gXls {}
			class gMenu extends g_Menu {}
			class gForm {
				function add(){}
				function row(){}
				function render(){}
			}
			break;

		case 3:
			include_once $gPath . "gfw/inc/gExportDoc.php";
			class gInput extends gDoc {}
			class gMenu extends g_Menu {}
			class gForm {
				function add(){}
				function row(){}
				function render(){}
			}
			break;

		case 4:
			include_once $gPath . "gfw/inc/gExportCsv.php";
			class gInput extends gCsv {}
			class gMenu extends g_Menu {}
			class gForm {
				function add(){}
				function row(){}
				function render(){}
			}
			break;

		case 5:
			include_once $gPath . "gfw/inc/gExportXml.php";
			class gInput extends gXml {}
			class gMenu extends g_Menu {}
			class gForm {
				function add(){}
				function row(){}
				function render(){}
			}
			break;

		default:
			class gInput extends gOutput {}
			class gMenu extends g_Menu {}
			//class gForm extends g_Form {}

			/**
			 * Classe responsável pela criação de um formulário
			 * @package	gForm
			 * @author	Giuliano Nascimento <giusoft@hotmail.com> e Sérgio Félix
			 * @version	4.0 31-12-2014 13:40
			 */
			class gForm extends g_Stdout
			{

				private string $output = '';

				public $ajson;
				public $fields;
				public $lists;
				public $extra = [];
				public $buttonNextCaption = '';
				public $buttonBackCaption = '';
				public $buttons;
				public $buttonsJavascript;
				public $firstElementName='';
				public $on='on';
				public $off='off';

				//multipart/form-data
				private string $enctype = '';
				private string $class_offset = '';
				private string $class_label = '';
				private string $class_element = '';
				private array $rows = [];
				private array $groups = [];
				private string $size = '';
				private bool $has_file_multiple = false;
				private array $input_hidden = [];
				private string $customHTML = "";

				private bool $showSubmit = true;
				/**
				 * Prepara ambiente para geração de conteúdo HTML para o formulário
				 * @author	giuliano
				 * @param $json Parâmetros em formato JSON: {debug: [true,false]; onlyBody: [true,false] (gera só código do meio da página)}
				 * @version	4.0 01-12-2013 10:50
				 */
				public function __construct($json = '', $ajson = '')
				{
					global $http_lib, $http_css, $http_inc, $http_img, $gDevice, $gLang, $o;
					parent::__construct($json);
					// $jarr=$this->jarr;
					$this->on  = gT('Sim');
					$this->off = gT('Não');
					$bootstrapAddonsPath = $http_lib . gVar("lib.bootstrap_addons");
					//$select					= $http_lib . gVar("lib.select");
					$selectize			 = $http_lib . gVar("lib.selectize");
					$moment				 = $http_lib . gVar("lib.moment");
					$lang = str_replace("_","-",$gLang);
					// CSS
					// DateTime picker
					if ($this->bootstrapVersao == 3) {
						$this->out('<link href="' . $bootstrapAddonsPath . 'bootstrap-datetimepicker-master/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css" media="screen">', gLOC_PRE,2);

						// Multiselect
						$this->out('<link href="' . $bootstrapAddonsPath . 'bootstrap-multiselect/bootstrap-multiselect.css" rel="stylesheet" type="text/css" media="screen">', gLOC_PRE);

						// Select (mais bonito)
						if ($select) {
							$this->out('<link href="' . $select . 'bootstrap-select.min.css" rel="stylesheet">', gLOC_PRE);
						}

						if ($selectize) {
							$this->out('<link href="' . $selectize . 'dist/css/selectize.bootstrap'.$this->bootstrapVersao.'.css" rel="stylesheet">', gLOC_PRE,-2);
						}

					} else {
						$this->out('<link href="' . $bootstrapAddonsPath . 'tempusdominus-bootstrap-4/tempusdominus-bootstrap-4.min.css" rel="stylesheet" type="text/css" media="screen">', gLOC_PRE,2);

						// Select
						$this->out('<link href="' . $bootstrapAddonsPath . 'bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet" type="text/css" media="screen">', gLOC_PRE);
					}

					// JS
					// Moment - biblioteca javascript para tratamento de datas - multi-idioma
					$this->out('<script src="' . $moment . '"></script>', gLOC_POS,2);

					// DateTime picker
					if ($this->bootstrapVersao == 3) {
						$this->out('<script src="' . $bootstrapAddonsPath . 'bootstrap-datetimepicker-master/build/js/bootstrap-datetimepicker.min.js"></script>', gLOC_POS);
						$this->out('<script src="' . $bootstrapAddonsPath . 'bootstrap-datetimepicker-master/src/js/locales/bootstrap-datetimepicker.' . $lang . '.js"></script>', gLOC_POS);

						// Multiselect
						$this->out('<script src="' . $bootstrapAddonsPath . 'bootstrap-multiselect/bootstrap-multiselect.js"></script>', gLOC_POS);

						// Touchspin
						$this->out('<script src="' . $bootstrapAddonsPath . 'bootstrap-touchspin-master/bootstrap-touchspin/bootstrap.touchspin.js"></script>', gLOC_POS);

						// Select
						if ($select) {
							$this->out('<script src="' . $select . 'bootstrap-select.min.js"></script>', gLOC_POS);
						}

						if ($selectize) {
							$this->out('<script src="' . $selectize . 'dist/js/standalone/selectize.min.js"></script>', gLOC_POS);
						}

					} else {
						//$this->out('<script src="' . $bootstrapAddonsPath . 'popper.min.js"></script>', gLOC_POS);
						$this->out('<script src="' . $bootstrapAddonsPath . 'tempusdominus-bootstrap-4/tempusdominus-bootstrap-4.min.js"></script>', gLOC_POS);

						// Select
						$this->out('<script src="' . $bootstrapAddonsPath . 'bootstrap-select/dist/js/bootstrap-select.min.js"></script>', gLOC_POS);
					}

					// Se $ajson existe, então os campos foram passados como um array... adiciona logo então...
					if (is_array($ajson)) {
						foreach ($ajson as $fld) {
							$this->add($fld);
						}
					}

					if (!empty($this->jarr['class'])) {
						$this->class = $this->jarr['class'];
					}

					if (!empty($this->jarr['title'])) {
						$this->title = $this->jarr['title'];
						unset($this->jarr['title']);
					}

					if (!empty($this->jarr['size'])) {
						$this->size = $this->jarr['size'];
						unset($this->jarr['size']);
					}

					if ($this->jarr['showSubmit'] == "false") {
						$this->showSubmit = false;
					}

					$this->setButtonNextCaption($this->jarr['buttonNextCaption']);

					if (
						!empty($this->jarr['class'])
						&& in_array('form-horizontal', explode(' ',(string) $this->jarr['class']))
					) {
						$this->class_offset  = 'col-xs-offset-4 col-sm-offset-3 col-md-offset-2 col-lg-offset-2';
						$this->class_label   = 'col-xs-4 col-sm-3 col-md-2 col-lg-2';
						$this->class_element = 'col-xs-8 col-sm-9 col-md-10 col-lg-10';
					}

					$this->formId = !empty($this->jarr['id']) ? $this->jarr['id'] : 'form_'.uniqid();
				}


				/**
				 * Adiciona um botão ao formulário (sem exibí-lo)
				 * @author	giuliano
				 * @param string $json Parâmetros pra criação do campo em formato JSON
				 * @version	4.0 01-12-2013 10:50
				 */
				public function addButton($json, $javascript=''): void
				{
					$this->buttons[] = cssDecode($json);
					$this->buttonsJavascript[] = $javascript;
				}


				/**
				 * Adiciona código HTML após os objetos do formulário e antes dos botões
				 * @author	giuliano
				 * @param string $json Parâmetros pra criação do campo em formato JSON
				 * @version	4.0 28-02-2020 18:50
				 */
				public function addHTML($html): void
				{
					$this->customHTML = $html;
				}


				/**
				 * Adiciona um campo ao formulário (sem exibí-lo)
				 * @author	giuliano
				 * @param string $json Parâmetros pra criação do campo em formato JSON
				 * @param string $list Utilizado para Combolist (select) - array de elementos, ou Wysiwyg (memo)
				 * @param string $extra
				 * @version	4.0 01-12-2013 10:50
				 */
				public function add($json, $list = [], $extra = [])
				{

					if (trim($json) !== "") {

						$mtz = cssDecode($json);
						if ($mtz['type'] != 'exclude') {
							$name = $mtz['name'];
							$fieldLabel = $mtz['fieldLabel'];
							$type = $mtz['type'];

							if ($name == "") {
								$name = gString2Field($fieldLabel);
							}

							if (($type != 'label') && ($fieldLabel == "")) {
								$fieldLabel = gField2String($name);
							}

							if ($type == "html" || ($type == 'show' && $name == '')) {
								$name = random_int(1000,9999);
								$fieldLabel = '';
							}

							$mtz['name'] = $name;
							$mtz['fieldLabel']   = gT($fieldLabel);
							$this->fields[$name] = $mtz;
							$this->lists[$name]  = $list;
							$this->extra[$name]  = $extra;
						}

						return $name;
					}

				}


				public function addFormMessage($txt): void
				{
					global $http_lib;

					$txt .= '<hr>';
					if (str_contains($txt,'fancybox')) {
						$js = '	$(document).ready(function(){
									$(".fancybox-default").fancybox({
											type : "iframe"
									});
								});';
						$this->addJavaScript($js);
						$this->out('<script src="' . $http_lib . 'jquery-fancybox-2.1.5/jquery.fancybox.js"></script>', gLOC_POS);
						$this->out('<link href="' . $http_lib . 'jquery-fancybox-2.1.5/jquery.fancybox.css" rel="stylesheet">', gLOC_PRE);
					}

					$this->output .= $txt;
				}


				/**
				 * Adiciona uma nova linha de campos
				 * @author	Sérgio
				 * @param string $arg Campos dos formulários passados como parâmetros individuais
				 * @version	4.0 01-12-2014 10:50
				 */
				public function row(): void
				{

					if (func_num_args()) {
						$this->rows[] = array_filter(func_get_args());
					}

				}


				public function group($json): void
				{
					$jarr = cssDecode($json);
					$this->groups[count($this->rows)] = $jarr;
				}


				/**
				 * Cria um elemento a partir do atributos passado por add
				 *
				 * @author Sérgio Félix
				 * @global type $gPathLib
				 * @global type $gAjax
				 * @global type $http_lib
				 * @param string $name nome do elemento
				 * @param boolean $withLabel inclui o label?
				 * @return string $html do elemento
				 */
				public function element($name, $withLabel = true)
				{
					global $gPathLib, $http_lib, $o;

					$small = $this->size == 'small' ? true : false;

					if (empty($this->fields[trim($name)])) {
						return;
					}

					$name = trim($name);

					// Utilizado nos campos de data e hora
					$dateLang   = str_replace("_", "-", gVar('global.language'));
					$dateFormat = strtoupper(gVar("global.dateformat"));
					$dateTimeFormat = strtoupper(gVar("global.dateformat")) . " HH:mm";
					$timeFormat = "HH:mm:ss";

					// Dados do campo
					$field = $this->fields[$name];

					//$id = trim($name).'_'.uniqid();
					$id = trim($name);

					// Tipo padrao de campo
					$type = 'text';

					// Label
					$label = '';

					// Elemento
					$element = '';

					// Addon
					$addon = '';

					// Tipo
					if (!empty($this->fields[$name]['type'])) {
						$type = $this->fields[$name]['type'];
						unset($this->fields[$name]['type']);
					}

					// Label
					if (!empty($field['fieldLabel'])) {
						$attr = [];
						$attr['for']   = $id;
						$attr['class'] = 'control-label text-left'.($this->class_label? ' '.$this->class_label:'');

						if ($small) {
							$field['fieldLabel'] = tagMe('small',$field['fieldLabel']);
						}

						$label = tagMe('label',$field['fieldLabel'],$this->renderAttr($attr));
					}

					if ($this->jarr['style'] == "inline") {
						$label = "";
					}

					// Atributos padrao campo
					$attr = [];
					$attr['name'] = trim($name);
					$attr['id']   = $id;

					if (is_array($this->lists[$name])) {
						$attr['value'] = ($this->lists[$name]) ? trim((string) $this->lists[$name][0]) : $field['value'];
					} else {
						$attr['value'] = ($this->lists[$name] != '') ? trim((string) $this->lists[$name]) : $field['value'];
					}

					$attr['class'] = 'form-control'.($small? ' input-sm' : '');
					$attr['type']  = $type;

					$value = $attr['value'];

					$attr['list'] = !empty($this->lists[$name]) && is_array($this->lists[$name])? $this->lists[$name] : [];

					if (!empty($field['maxLength'])) {
						$attr['maxlength'] = $field['maxLength'];
					}

					if (!empty($field['maxlength'])) {
						$attr['maxlength'] = $field['maxLength'];
					}

					// Campo obrigatorio
					if (
						!empty($field['allowBlank'])
						&& (
							$field['allowBlank'] == 'false'
							|| $field['allowBlank'] == 'off'
						)
					) {
						$attr['required'] = 'required';
					}

					// Placeholder
					if (!empty($field['hint'])) {
						$attr['placeholder'] = $field['hint'];
					}

					if (!empty($field['operator'])) {
						$attr['operator'] = $field['operator'];
					}

					if (
						$this->firstElementName == ''
						&& $type != 'hidden'
						&& $type != 'show'
					) {
						$this->firstElementName = $name;
					}

					// Configurando os atributos para cada tipo de elemento
					switch($type) {
						case 'label':
						case 'show':
							$attr = ['class' => 'text-left'];
							switch ($field['format']) {
								case 'number':
								case 'numeric':
								case 'integer':
									$value = gFloat($value);
									break;

								case 'date':
									$value = gDate($value);
									break;

								case 'datetime':
								case 'dateTime':
									$value = gDateTime($value);
									break;

								case 'time':
									$value = gTime($value);
									break;

								case 'checkbox':
									$value = gCheck($value);
									break;

								case 'big':
									$value = '<span style="font-size: 150%">'.$value.'</span>';
									break;
							}

							break;

						case 'barcode':
						case 'upperText':
							$attr['onblur'] = 'this.value=this.value.toUpperCase()';
							break;

						case 'lowerText':
							$attr['onblur'] = 'this.value=this.value.toLowerCase()';
							break;

						case 'upperFirstWordText':
							$attr['onblur'] = 'vUFWText(this)';
							break;

						case 'upperFirstLetterText':
							$attr['onblur'] = 'vUFText(this)';
							break;

						case 'cpf':
							$attr['type'] = 'text';
							$attr['maxlength'] = 11;
							break;

						case 'ncm':
							$attr['type'] = 'text';
							$attr['maxlength'] = 10;
							$attr['onkeyup'] = 'vNCM(this)';
							break;

						case 'cnpj':
							$attr['type'] = 'text';
							$attr['maxlength'] = 14;
							$attr['onblur'] = 'this.value=this.value.toUpperCase()';
							break;

						case 'cpfcnpj':
							$attr['type'] = 'text';
							$attr['maxlength'] = 14;
							break;

						case 'search':
							//$attr['type'] = 'search';
							break;

						case 'color':
							//$attr['type'] = 'color';
							break;

						case 'text':
						case 'selectcode':
							break;

						case 'phone':
							$attr['type'] = 'tel';
							$attr['maxlength'] = 15;
							$attr['onblur'] = 'this.value=this.value.toUpperCase()';
							break;

						case 'url':
							$attr['type'] = 'url';
							$attr['parsley-trigger'] = 'focusout';
							$attr['parsley-type'] = 'urlstrict';
							break;

						case 'number':
							$attr['parsley-trigger'] = 'focusout';
							$attr['parsley-type'] = 'number';

							if (str_starts_with(gVar("global.numformat"), "0.000,00")) {
								$attr['type'] = "numberBr";
								$attr['parsley-type'] = "numberBr";
							}
							$attr['step'] = 'any';
							break;

						case 'ip':
							$attr['type'] = 'text';
							$attr['parsley-trigger'] = 'focusout';
							$attr['parsley-regexp'] = '([1-9][0-9]{0,1}|1[013-9][0-9]|12[0-689]|2[01][0-9]|22[0-3])([.]([1-9]{0,1}[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])){2}[.]([1-9][0-9]{0,1}|1[0-9]{2}|2[0-4][0-9]|25[0-4])';
							$attr['maxlength'] = 15;
							break;

						case 'container':
							$attr['type'] = 'text';
							$attr['parsley-trigger'] = 'focusout';
							$attr['parsley-regexp'] = '[A-Za-z]{4}[0-9]{6,7}';
							$attr['maxlength'] = 11;
							break;

						case 'interpos':
							$attr['type'] = 'text';
							$attr['parsley-trigger'] = 'focusout';
							$attr['parsley-regexp'] = '[0-9]{1}[A-Za-z]{1}[0-9]{2}[0-9]{2}[0-9]{1}';
							$attr['maxlength'] = 11;
							break;

						case 'plate':
							$attr['type'] = 'text';
							$attr['parsley-trigger'] = 'focusout';
							$attr['parsley-regexp'] = '[A-Z]|[a-z]{3}[0-9]{4}';
							$attr['maxlength'] = 7;
							break;

						case 'integer':
							$attr['type'] = 'text';
							$attr['parsley-trigger'] = 'focusout';
							$attr['parsley-type'] = 'number';

							if (str_starts_with(gVar("global.numformat"), "0.000,00")) {
								$attr['type'] = "numberBr";
								$attr['parsley-type'] = "numberBr";
							}
							$max = !empty($field['maxValue']) ? (float) $field['maxValue'] : 999999999;
							$min = !empty($field['minValue']) ? (float) $field['minValue'] : -999999999;

							$this->addJavascript('$("#' . $id . '").TouchSpin({
									min: ' . intval($min).',
									max: ' . intval($max).',
									decimals: 0,
									boostat: 5,
									verticalbuttons: true,
									mousewheel: false,
									maxboostedstep: 100,
									step: 1
								});'
							);

							break;

						case 'percent':
							$attr['type'] = 'text';
							$attr['step'] = 'any';

							$this->addJavascript('$("#' . $id . '").TouchSpin({
									postfix: "%",
									min: 0,
									max: 100,
									decimals: 2,
									boostat: 5,
									verticalbuttons: true,
									mousewheel: false,
									maxboostedstep: 100,
									step: 0.1
								});'
							);
							break;

						case 'image':
							$attr['type'] = 'image';
							$this->enctype = 'multipart/form-data';
							break;

						case 'file':
							$attr['type'] = 'file';
							$this->enctype = 'multipart/form-data';
							break;

						case 'checkBox':
						case 'checkbox':
							$attr['parsley-group'] = 'gChkGroup';

							unset($attr['class']);

							if (gDBCheck($field['value'])==1) {
								$attr['checked'] ='checked';
							}

							break;

						case 'memo':
							unset($attr['type']);
							unset($attr['class']);
							//unset($attr['list']);
							$id = trim($name);
							$attr['id'] = $id;
							$attr['name'] = $name;
							$attr['height'] = 150;
							$attr['formId'] = $this->formId;

							$extra = ['indent', 'style', 'align', 'image', 'h1', 'h2', 'h3', 'h4', 'code', 'alerts'];
							foreach($extra as $param){
								if (array_key_exists($param,$field)) {
									$attr[$param] = $field[$param];
								}
							}

							if ($this->fields[$name]['height'] != '') {
								$attr['height'] = $this->fields[$name]['height'];
							}

							break;

						case 'date':
							$attr['type'] = 'text';
							$addon = 'calendar';
							$attr_js = [
								'format: "' . $dateFormat . '"',
								'locale: "' . $dateLang . '"'
							];

							if ($dateLang == 'pt-BR') {
								$attr_js[] ="tooltips: {
									today: 'Ir para hoje',
									clear: 'Limpar seleção',
									close: 'Fechar',
									selectMonth: 'Selecionar mês',
									prevMonth: 'Mês anterior',
									nextMonth: 'Próximo mês',
									selectYear: 'Selecionar ano',
									prevYear: 'Ano anterior',
									nextYear: 'Próximo ano',
									selectDecade: 'Selecionar década',
									prevDecade: 'Década anterior',
									nextDecade: 'Próxima década',
									prevCentury: 'Século anterior',
									nextCentury: 'Próximo século'
								}";
							}

							if (!empty($fieldl['startDate'])) {
								$attr_js[] = 'minDate:"' . $field['startDate'] . '"';
							}

							if (!empty($fieldl['endDate'])) {
								$attr_js[] = 'maxDate:"' . $field['endDate'] . '"';
							}

							if ($attr['operator'] == 'range') {
								$jarr1 = $jarr;
								$jarr2 = $jarr;
								$jarr1['name'] .= '__FROM';
								$jarr2['name'] .= '__TO';
								$this->filters[$jarr1['name']] = $jarr1;
								$this->filters[$jarr2['name']] = $jarr2;
							}

							break;

						case 'time':
							$dateFormat = "HH:mm";
							$attr['type'] = 'text';
							$addon = 'clock-o';
							$attr_js = [
								'format: "' . $dateFormat . '"',
								'locale: "' . $dateLang . '"'
							];

							if ($dateLang == 'pt-BR') {
								$attr_js[] = "tooltips: {
									pickHour: 'Selecionar hora',
									pickMinute: 'Selecionar minuto',
									incrementHour: 'Aumentar hora',
									decrementHour: 'Diminuir hora',
									incrementMinute: 'Aumentar minuto',
									decrementMinute: 'Diminuir minuto'
								}";
							}

							if (!empty($field['startTime'])) {
								$attr_js[] = 'minDate:"' . $field['startTime'] . '"';
							}

							if (!empty($field['endTime'])) {
								$attr_js[] = 'maxDate:"' . $field['endTime'] . '"';
							}

							break;

						case 'dateTime':
							$attr['type'] = 'text';
							$attr['type'] = 'datetime';
							$addon = 'calendar';
							$attr_js = [
								'format: "' . $dateTimeFormat . '"',
								'locale: "pt-BR"',
								'sideBySide: true',
								'showClose: true',
								'icons: {
									time: "fal fa-clock-o",
									date: "fal fa-calendar",
									up: "fal fa-chevron-up",
									down: "fal fa-chevron-down",
									previous: "fal fa-chevron-left",
									next: "fal fa-chevron-right",
									today: "fal fa-sun-o",
									clear: "fal fa-trash",
									close: "fal fa-close"
								}'
							];

							if ($dateLang == 'pt-BR') {
								$attr_js[] = "tooltips: {
									today: 'Ir para hoje',
									clear: 'Limpar seleção',
									close: 'Fechar',
									selectMonth: 'Selecionar mês',
									prevMonth: 'Mês anterior',
									nextMonth: 'Próximo mês',
									selectYear: 'Selecionar ano',
									pickHour: 'Selecionar hora',
									pickMinute: 'Selecionar minuto',
									incrementHour: 'Aumentar hora',
									decrementHour: 'Diminuir hora',
									incrementMinute: 'Aumentar minuto',
									decrementMinute: 'Diminuir minuto',
									prevYear: 'Ano anterior',
									nextYear: 'Próximo ano',
									selectDecade: 'Selecionar década',
									prevDecade: 'Década anterior',
									nextDecade: 'Próxima década',
									prevCentury: 'Século anterior',
									nextCentury: 'Próximo século'
								}";
							}

							if (!empty($fieldl['startDate'])) {
								$attr_js[] = 'minDate:"' . $field['startDate'] . '"';
							}

							if (!empty($fieldl['endDate'])) {
								$attr_js[] = 'maxDate:"' . $field['endDate'] . '"';
							}

							if ($attr['operator'] == 'range') {
								$jarr1 = $jarr;
								$jarr2 = $jarr;
								$jarr1['name'] .= '__FROM';
								$jarr2['name'] .= '__TO';
								$this->filters[$jarr1['name']] = $jarr1;
								$this->filters[$jarr2['name']] = $jarr2;
							}

							break;

						case 'textarea':
							$attr['rows'] = 3;
							if (isset($field['rows'])) {
								$attr['rows'] = $field['rows'];
							}

							break;

						case 'code':
							$attr['rows']  = 10;
							$attr['style'] = 'font-family: Courier';

							if (isset($field['rows'])) {
								$attr['rows'] = $field['rows'];
							}
							break;

						case 'timezonepicker':
							$attr['type'] = 'select';
							$attr['id']   = 'edit-date-default-timezone';
							$attr['list'] = [];
							if (!$this->hasAttr('required',$attr)) {
								$attr['list'][0] = '* '.gT("Indiferente");
							}

							### BIBLIOTECA timezonepicker está deprecated ###
							// $attr['list'] += (include $gPathLib.'timezonepicker/timezonedata.php');
							break;

						case 'comboMultiSelection':
							$attr['multiple'] = 'multiple';
							$attr['name'] .= '[]';

							$js_maxItems = 'maxItems: 100,';
							if (strpos((string) $field['value'],",")) {
								$field['value'] = jcombo2array($field['value']);
								//$attr['value'] =  jcombo2array($field['value']);
							}
							break;

						case 'combo':
							$attr['type'] = 'select';
							$attr['class'] = 'selectpicker';
							break;

						case 'select':
							if ($type != 'comboMultiSelection') {
								$js_maxItems = 'maxItems: 1,';
							}

							if (!empty($field['value'])) {
								$attr['value'] = (is_array($field['value'])) ? $field['value'] : [$field['value']];
							} else {
								$attr['value'] = '';
							}

							if (
								(!$this->hasAttr('required',$attr))
								&& (!$this->hasAttr('multiple', $attr))
							) {
								$attr['list'][0] = '* '.gT("Indiferente");
							}

							if (!$this->hasAttr('placeholder', $attr)) {
								$attr['placeholder'] = gT("select");
							}

							if (!empty($field['items'])) {
								$arr = jcombo2array($field['items']);
								if (is_array($arr)) {
									$attr['list'] = jcombo2array($field['items']) + $attr['list'];
								} else {
									$attr['list'] = $attr['list'];
								}

							}

							if (!empty($field['comboTarget']) && !empty($field['comboTargetValues'])) {
								global $gAjax;
                                $gAjax->comboDynamicValues($id,$field['comboTarget'],$field['comboTargetValues'],$field['otherParameters']);
							}

							break;

						case 'email':
						case 'password':
							$value = SENHA_NAO_MODIFICADA;
							break;

						case 'text':
						case 'selectcode':
							break;

					}

					// Criando elemento
					switch($type) {
						case 'html':
							$element = $attr['value'];
							break;

						case 'label':
							$element = $label;
							break;

						case 'show':
							$element = tagMe('p', $value, $attr);
							break;

						case 'image':
						case 'file':
							if ($this->attr('multiple',$field) && $field['multiple'] == 'true') {
								$attr['name'] = $field['name'].'[]';
								$attr['multiple'] = $field['multiple'];
								$this->has_file_multiple = true;
							}

							$title = $type == 'image' ? gT("Selecionar imagem") : gT("Selecionar arquivo");

							$element = '<div class="input-group">
											<div class="fileinput fileinput-new" data-provides="fileinput">
												<span class="btn btn-default btn-file">
													<span class="fileinput-new">
														'.$title.'
													</span>
													<span class="fileinput-exists">'.gT("Alterar").'</span>
													'.$this->elementTag($attr).'
												</span>
												<span class="fileinput-filename pull-left"></span>
												<a href="#" class="close fileinput-exists" data-dismiss="fileinput" style="float: none">&times;</a>
											</div>
										</div>';

							if (
								$this->attr('multiple',$field)
								&& $field['multiple'] == 'true'
							) {
								$element .= '<p><a class="add-other-file" href="" data-input="'.$name.'" data-max="'.(!empty($field['multipleMax'])? intval($field['multipleMax']) : 0).'" data="'.htmlspecialchars('<div class="fileinput-multiple"><a href="" class="btn btn-default fileinput-remove pull-left"  data-input="'.$name.'"><span class="fal fa-eraser"></span> </a> '.$element.'</div>').'">'.gT('Adicionar outro arquivo', ENT_QUOTES).'</a></p>';
							}
							break;

						case 'checkBox':
						case 'checkbox':
							$attr['on']  = $field['on'];
							$attr['off'] = $field['off'];
							$element = '<div>' . $this->elementTag($attr).'</div>';

							if (!empty($field['onChange'])) {
								$js = "function getValueByBlur" . $field['name'] . "() {var v=\$('#" . $field['name']."').prop('checked'); " . $field['onChange'] . "(v)};\$('#".$field['name']."').change(getValueByBlur" . $field['name'] . ");";
								$this->addJavascript($js);
							}

							break;

						case 'code':
							$value = $attr['value'];
							if (isset($this->lists[$field['name']])) {
								$value = $this->lists[$field['name']];
							}

							$attr['value'] = $value;
							$element = $this->elementTag($attr);
							break;

						case 'memo':
							$value = $attr['value'];
							if ($this->lists[$field['name']]) {
								$value = $this->lists[$field['name']];
							}

							if ($field['base64'] == 'true') {
								$attr['base64'] = 'true';
							}

							$element = $this->wysiwyg(cssEncode($attr), $value, $this->extra[$field['name']] );
							break;

						case 'recaptcha':
							$element = recaptcha_get_html(gVar("recaptcha.publicKey"), $error);
							if (!empty($_SERVER['HTTPS'])) {
								$element = str_replace('http','https',$element);
							}

							break;

						case 'recaptchav2':
							$element = '<div class="g-recaptcha" data-sitekey="'.gVar("recaptcha.publicKey").'"></div>
							 			<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
							break;

						case 'date':
						case 'time':
						case 'dateTime':
							$element = $this->elementTag($attr);
							$this->addJavascript("$('#" . $id . "').datetimepicker(" . '{' . implode(",", $attr_js) . '}' . ");");
							$element = tagMe('div',$element.$this->addon($addon),'class="input-group"');
							if (!empty($field['onBlur'])) {
								$js = "function getValueByBlur".$field['name']."() {var v=\$('#".$field['name']."').val(); ".$field['onBlur']."(v)};\$('#".$field['name']."').blur(getValueByBlur".$field['name'].");";
								$this->addJavascript($js);
							}

							if (!empty($field['onFocus'])) {
								$js = "function getValueByFocus".$field['name']."() {var v=\$('#".$field['name']."').val(); ".$field['onFocus']."(v)};\n\$('#".$field['name']."').focus(getValueByFocus".$field['name'].");";
								$this->addJavascript($js);
							}

							break;

						### BIBLIOTECA timezonepicker está deprecated ###
						// case 'timezonepicker':
						// 	$timZoneLib = $http_lib . 'timezonepicker/';
						// 	include $gPathLib . 'timezonepicker/includes/parser.inc';
						// 	$timezones = timezone_picker_parse_files(600, 300, $gPathLib.'timezonepicker/tz_world.txt', $gPathLib.'timezonepicker/tz_islands.txt');

						// 	$this->out('<script src="' . $timZoneLib . 'lib/jquery.maphilight.min.js"></script>', gLOC_POS);
						// 	$this->out('<script src="' . $timZoneLib . 'lib/jquery.timezone-picker.min.js"></script>', gLOC_POS);
						// 	$this->addJavascript('
						// 		$(document).ready(function() {
						// 			  $("#timezone-image").timezonePicker({
						// 				target: "#edit-date-default-timezone",
						// 				countryTarget: "#edit-site-default-country"
						// 			  });

						// 			  $("#timezone-detect").click(function() {
						// 				$("#timezone-image").timezonePicker("detectLocation");
						// 			  });
						// 		});'
						// 	);

						// 	$element = '<div class="input-group">
						// 					' . $this->elementTag($attr) . '
						// 					<br/>
						// 					<div id="timezone-picker">
						// 						<img id="timezone-image" src="' . $timZoneLib . 'images/blue-marble-600.jpg" width="600" height="300" usemap="#timezone-map" />
						// 						<img class="timezone-pin" src="' . $timZoneLib . 'images/pin.png" style="padding-top: 4px;" />
						// 						<map name="timezone-map" id="timezone-map">';
						// 	foreach ($timezones as $timezone_name => $timezone) {
						// 		foreach ($timezone['polys'] as $coords) {
						// 			$element .= '<area data-timezone="' . $timezone_name . '" data-country="' . $timezone['country'] . '" data-pin="' . implode(',', $timezone['pin']) . '" data-offset="' . $timezone['offset'] . '" shape="poly" coords="' . implode(',', $coords) . '" />';
						// 		}

						// 		foreach ($timezone['rects'] as $coords) {
						// 			$element .= '<area data-timezone="' . $timezone_name . '" data-country="' . $timezone['country'] . '" data-pin="' . implode(',', $timezone['pin']) . '" data-offset="' . $timezone['offset'] . '" shape="rect" coords="' . implode(',', $coords) . '" />';
						// 		}
						// 	}

						// 	$element .= '		</map>
						// 						<br/>
						// 					</div>
						// 				</div>';

						// 	if ($this->hasAttr('openModal', $field) && $field['openModal'] == 'true') {
						// 		$btn_title = $field['value'] ?: gT('Selecionar Fuso Horário');
						// 		$element = '<div class="modal fade" id="modal-timezone">
						// 					<div class="modal-dialog" style="width:643px">
						// 						<div class="modal-content">
						// 							<div class="modal-header">
						// 								<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
						// 								<h4 class="modal-title">'.gT('Time Zone').'</h4>
						// 							</div>
						// 							<div class="modal-body">
						// 								' . $element . '
						// 							</div>
						// 							<div class="modal-footer">
						// 								<button type="button" class="btn btn-default" data-dismiss="modal">'.gT('Cancelar').'</button>
						// 								<button type="button" class="btn btn-primary btn-timezone-confirma"  data-dismiss="modal">'.gT('Confirmar').'</button>
						// 							</div>
						// 						</div>
						// 					</div>
						// 				</div>'.
						// 				$o->button("{type: button; title: " . $btn_title . "; name: btn-timezone-modal; style: default; openModal:modal-timezone;}");

						// 				$this->addJavascript('
						// 					$(document).ready(function() {
						// 						$(".btn-timezone-confirma").click(function(){
						// 							if($("#edit-date-default-timezone").val()){
						// 								$("[name=btn-timezone-modal]").html($("#edit-date-default-timezone").val());
						// 							}
						// 						});
						// 					});
						// 				');
						// 	}

						// 	break;

						case 'select':
						case 'combo':
						case 'comboMultiSelection':
							$element = $this->elementTag($attr);

							if (
								gVar("lib.selectize") != ''
								&& empty($field['disableSelectize'])
								&& $this->bootstrapVersao == 3
							) {
								if (!empty($field['allowNew']) && $field['allowNew'] == 'true') {
									$this->addJavascript("\$select_" . $id . " = \$('#" . $id . "').selectize({delimiter: ',', ".$js_maxItems." persist: false, createOnBlur: true, create: true, onInitialize:function(){\$('#".$id."').next('.selectize-control').find('div').removeClass('required','parsley-validated');$('#".$id."').next('.selectize-control').find('input').removeAttr('required');} });".$this->n);
								} else {
									$this->addJavascript("\$select_" . $id . " = \$('#" . $id . "').selectize({delimiter: ',', ".$js_maxItems." persist: false, onInitialize:function(){\$('#".$id."').next('.selectize-control').find('div').removeClass('required','parsley-validated');$('#".$id."').next('.selectize-control').find('input').removeAttr('required');}});".$this->n);
								}

								if (!empty($field['onChange'])) {
									$this->addJavascript('var control' . $field['name'] . ' = $select_' . $field['name'] . '[0].selectize;control' . $field['name'] . '.on("change", ' . str_replace('()','',$field['onChange']) . ');' . $this->n);
								}

								if (!empty($field['onBlur'])) {
									$this->addJavascript('var control' . $field['name'] . ' = $select_' . $field['name'] . '[0].selectize;control' . $field['name'] . '.on("blur", ' . str_replace('()','',$field['onBlur']) . ');' . $this->n);
								}

								if (!empty($field['onFocus'])) {
									$this->addJavascript('var control' . $field['name'] . ' = $select_' . $field['name'] . '[0].selectize;control' . $field['name'] . '.on("focus", '.str_replace('()','',$field['onFocus']) . ');' . $this->n);
								}

								if (!empty($field["onlySearch"]) && $field["onlySearch"] > 0) {
									$search = $field["onlySearch"];
									$js = "
										$(function () {
											var select = " . $id . ";
											var itensSelect = $('#'+select.name).siblings('div')[0].children[1].children[0];
											itensSelect.setAttribute('style', 'display:none');
											var inputPesquisa=$('#'+select.name).siblings('div')[0].children[0].children[1];
											inputPesquisa.onkeyup=function (e) {
												var digito=e.target.value;
												var total=" . $search . ";
												if (digito.length>=total)
												{
													itensSelect.setAttribute('style', '');
												} else
												{
													itensSelect.setAttribute('style', 'display:none;');
												}
											}
										});
									";
									$this->addJavascript($js);
									//$this->addJavascript($js);
								}

							}
							break;

						case 'barcode':
							$style = "<style>
										canvas.drawing, canvas.drawingBuffer {
											position: absolute;
											left: 0;
											top: 0;
										}
										#scanner-container.viewport {
										    position: relative;
										}

										#scanner-container.viewport > canvas, #scanner-container.viewport > video {
										    max-width: 75%;
										    width: 75%;
										}

										canvas.drawing, canvas.drawingBuffer {
										    position: absolute;
										    left: 0;
										    top: 0;
										}
								    </style>";
					    	$this->out($style, gLOC_PRE);

							if (gVar("lib.quagga") != "") {
								$quagga = $http_lib . gVar("lib.quagga");
								$this->out('<script src="'.$quagga.'"></script>', gLOC_POS);
								$js = "
									var _scannerIsRunning = false;
									function startScanner() {
										console.log('here');
										Quagga.init({
											inputStream: {
												name: 'Live',
												type: 'LiveStream',
												target: document.querySelector('#scanner-container'),
												constraints: {
													aspectRatio: {min: 1, max: 100},
													facingMode: 'environment'
												},
											},
											locator: {
												patchSize: 'medium',
												halfSample: true
											},
											frequency: 10,
											decoder: {
												readers: [
													{
														format: 'code_128_reader',
														config: {}
													}
												],
												debug: {
													showCanvas: true,
													showPatches: true,
													showFoundPatches: true,
													showSkeleton: true,
													showLabels: true,
													showPatchLabels: true,
													showRemainingPatchLabels: true,
													boxFromPatches: {
														showTransformed: true,
														showTransformedBox: true,
														showBB: true
													},
												locate: true
												}
											},

										}, function (err) {
											if (err) {
												console.log(err);
												return
											}
											console.log('Initialization finished. Ready to start');
											Quagga.start();

											// Set flag to is running
											_scannerIsRunning = true;
										});

										Quagga.onProcessed(function (result) {
											var drawingCtx = Quagga.canvas.ctx.overlay,
											drawingCanvas = Quagga.canvas.dom.overlay;

											if (result) {
												if (result.boxes) {
													drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute('width')), parseInt(drawingCanvas.getAttribute('height')));
													result.boxes.filter(function (box) {
														return box !== result.box;
													}).forEach(function (box) {
														Quagga.ImageDebug.drawPath(box, { x: 0, y: 1 }, drawingCtx, { color: 'green', lineWidth: 2 });
													});
												}

												if (result.box) {
													Quagga.ImageDebug.drawPath(result.box, { x: 0, y: 1 }, drawingCtx, { color: '#00F', lineWidth: 2 });
												}

												if (result.codeResult && result.codeResult.code) {
													Quagga.ImageDebug.drawPath(result.line, { x: 'x', y: 'y' }, drawingCtx, { color: 'red', lineWidth: 3 });
												}
											}
										});

										Quagga.onDetected(function (result) {
											$('#scanner-container').hide();
											//document.getElementById('scanner-container').style.display='none';
											_scannerIsRunning = false;
											var id = '#".$attr['id']."';
											$(id).val(result.codeResult.code);
											//console.log('Barcode detected and processed : [' + result.codeResult.code + ']', result);
										});
									}

									document.getElementById('btnBarcode').addEventListener('click', function () {
										if (_scannerIsRunning) {
											$('#scanner-container').hide();
										} else {
											$('#scanner-container').show();
											startScanner();
										}
									}, false);
								";

							}

							if (gVar("lib.html5-qrcode")) {
								$form_attr = $this->jarr;
								$qrcode = $http_lib . gVar("lib.html5-qrcode");
								$this->out('<script src="'.$qrcode.'minified/html5-qrcode.min.js"></script>', gLOC_POS);
								$js = "
									function onScanSuccess(decodedText, decodedResult) {
										// 	handle the scanned code as you like, for example:
										var id = '#".$attr['id']."';
										$(id).val(decodedText);
										console.log(idForm);
										console.log(`Code matched = ${decodedText}`, decodedResult);
										html5QrcodeScanner.clear();
										$(idForm).submit();
									}

									function onScanFailure(error) {
										// handle scan failure, usually better to ignore and keep scanning.
										// for example:
										// console.warn(`Code scan error = ${error}`);
									}

									let html5QrcodeScanner = new Html5QrcodeScanner(
										\"scanner-container\",
										{ fps: 10, qrbox: {width: 250, height: 250} },
										/* verbose= */ false);
									html5QrcodeScanner.render(onScanSuccess, onScanFailure);
									$('#scanner-container').hide();

									document.getElementById('btnBarcode').addEventListener('click', function () {
										if ($('#scanner-container').is(':visible')) {
											$('#scanner-container').hide();
										} else {
											$('#scanner-container').show();
										}
									}, false);

								";
							}

					    	$this->addJavascript($js);
							$element = $this->elementTag($attr);
							break;

						case 'selectcode':
							//global $o;
							$element = '<div class="input-group">
									      <input name="'.$field['name'].'" id="'.$fields['name'].'" type="text" class="form-control" placeholder="'.$field['hint'].'" value="'.$field['value'].'" onfocus="this.select()">
									      <span class="input-group-btn">
									        ' . gOutput::button("{icon: search; type: button;openModal: modalSelectCode" . $field['name'] . ";}",$field['name'] . "getSelectCode(" . $field['gId'] . ")") . '
									      </span>
									    </div>';

							$div = '<div id="contentSelectCode_'.$field['name'].'">Carregando dados...</div>';
							$element .= $o->modal("{title: ".$field['title']."; size: big; confirm: false; name:modalSelectCode".$field['name']."}",$div);

							$data = "";
							if ($field['getParameters'] != "") {
								$f = explode(",",(string) $field['getParameters']);
								foreach ($f as $i) {
									$par[] = "$i:$('#$i').val()";
								}

								$data = "data:{".implode(",",$par)."},";
							}

							$js = "
								function " . $field['name'] . "getSelectCode(id) {
									$(document).ready(function()
									{
										$.ajax(
										{
											url: '".$field['href']."',
											$data
											type: 'GET',
											success: function(data)
											{
												document.querySelector('#contentSelectCode_".$field['name']."').innerHTML = data;
											},
											error: function()
											{
												document.querySelector('#".$field['name']."').innerHTML = 'Erro ao carregar dados. Tente novamente mais tarde';
											}
										});
									});
								}";
							$this->addJavascript($js);
							break;

						default:
							if (!empty($field['onBlur'])) {
								$js = "function getValueByBlur" . $field['name'] . "() {var v=\$('#".$field['name'] . "').val(); " . $field['onBlur'] . "(v)};\$('#".$field['name'] . "').blur(getValueByBlur" . $field['name'] . ");";
								$this->addJavascript($js);
							}

							if (!empty($field['onFocus'])) {
								$js = "function getValueByFocus".$field['name']."() {var v=\$('#".$field['name']."').val(); ".$field['onFocus']."(v)};\n\$('#".$field['name']."').focus(getValueByFocus".$field['name'].");";
								$this->addJavascript($js);
							}

							if (!empty($field['onKeyPress'])) {
								//$js="function getValueByKeyPress".$field['name']."(event) {var v=\$('#".$field['name']."').val(); ".$field['onKeyPress']."(v,event)};\n\$('#".$field['name']."').keypress(getValueByKeyPress".$field['name']."(event));";
								$js = "\$('#" . $field['name'] . "').keypress(function(event) {var v=\$('#".$field['name']."').val(); ".$field['onKeyPress']."(v,event)});\n";
								$this->addJavascript($js);
							}

							$element = $this->elementTag($attr);
						break;
					}

					if ($type == 'hidden') {
						return;
					}

					if ($this->hasAttr('help', $field)) {
						$element .= '<span class="help-block">' . $field['help'] . '</span>';
					}

					if ($this->class_element) {
						$element = tagMe('div',$element,'class="'.$this->class_element.'"');
					}

					if ($this->jarr['style'] == 'table') {
						$content = $element;
					} elseif ($this->jarr['inline'] != 'true' || $type == "html") {
						if ($type == "html") {
							$content = tagMe('div',$element,'id="field-'.$field['name'].'" class="form-group"').$this->n;
						} elseif ($withLabel) {
							$content = tagMe('div',$label.$element,'id="field-'.$field['name'].'" class="form-group"').$this->n;
						} else {
							$content = tagMe('div',$element,'id="field-'.$field['name'].'" class="form-group"').$this->n;
						}

					} elseif ($withLabel) {
						$content = tagMe('div','<div class="text-left col-lg-3 col-md-3">'.$label.'</div><div class="text-left col-lg-9 col-md-9">'.$element.'</div>','id="field-'.$field['name'].'" class="form-group"').$this->n;
					} else {
						$content = tagMe('div','<div class="text-left col-lg-9 col-md-9">'.$element.'</div>','id="field-'.$field['name'].'" class="form-group"').$this->n;
					}

					unset($this->fields[$name]);
					return $content;
				}


				/**
				 * Cria um elemento de formulario
				 *
				 * @param array Matriz associativa com os atributos para criar o elemento
				 * @return string HTML do elemento
				 */
				public function elementTag($attr){

					// Tipo
					$type = !empty($attr['type']) ? $attr['type'] : 'text';

					// Valor
					$value = '';

					// List
					$list = [];
					if (!empty($attr['list'])) {
						$list = $attr['list'];
						unset($attr['list']);
					}

					if (in_array($type,['select', 'combo', 'textarea', 'code', 'hidden'])) {
						if (is_array($attr['value']) && count($attr['value']) == 1) {
                            $value = $attr['value'][0];
                        } else {
                            $value = $attr['value'];
                        }
						if (!empty($attr['type'])) {
							unset($attr['type']);
						}

						if (!empty($attr['value'])) {
							unset($attr['value']);
						}
					}
					$output = '';

					switch($type) {
						case 'combo':
						case 'select':
							$output   = '<select ' . $this->renderAttr($attr) . '>';
							//if(!$this->hasAttr('required',$attr))
							//	$output .= '<option value=""></option>';
							$selected = false;
							if ($list) {
								foreach ($list as $opt_value => $opt_label) {
									// $output.= '<option value="'.$opt_value.'"'.(!$selected && ((in_array($opt_value,$value)) || (in_array($opt_label,$value)) || ($opt_value==$value) || ($opt_label==$value) || ($value=='' && ($opt_value=='0'))) ? ' selected="selected"' : '').'>'.$opt_label.'</option>';
									// if($attr['multiple']<>"multiple")
									// 	$selected=(in_array($opt_value,$value) || in_array($opt_label,$value)) ? $selected : $selected;
									if (is_array($value)) {
										$achou = false;
										foreach ($value as $v) {
											if ($v == $opt_value || $v == $opt_label) {
												$achou = true;
											}
										}

										if ($achou) {
											$output .= '<option value="' . $opt_value . '" selected="selected">'.$opt_label.'</option>';
										} else {
											$output .= '<option value="' . $opt_value . '">'.$opt_label.'</option>';
										}
									} else {
										if (is_array($opt_value)) {
											if (
												!$selected
												&& (
													(in_array($opt_value,$value))
   													|| (in_array($opt_label,$value))
   													|| ((string) $opt_value == (string) $value)
   													|| ((string) $opt_label === (string) $value)
   													|| ($value == '' && ($opt_value=='0'))
   												)
   											) {
   												$sel = ' selected="selected" ';
   												$selected = true;
   											} else {
   												$sel = '';
   											}
										} elseif (
											!$selected
											&& (
												( (string) $opt_value === (string) $value )
												|| ( (string) $opt_label === (string) $value )
												|| ($value=='' && ($opt_value=='0'))
											)
										) {
											$sel = ' selected="selected" ';
											$selected = true;
										} else {
											$sel = '';
										}
										$output .= ' <option value="'.$opt_value.'"'.$sel.'>'.$opt_label.'</option>';
										// $selected=(in_array($opt_value,$value) || in_array($opt_label,$value)) ? true : $selected;
									}
								}
							}
							$output .= '</select>';
							break;

						case 'textarea':
							$output = '<textarea '.$this->renderAttr($attr).'>'.$value.'</textarea>';
							break;

						case 'code':
							$output = '<textarea wrap="off" '.$this->renderAttr($attr).'>'.$value.'</textarea>';
							break;

						case 'checkBox':
						case 'checkbox':
							$on  = $this->on;
							$off = $this->off;
							if (isset($attr['on'])) {
								$on = $attr['on'];
								unset($attr['on']);
							}

							if (isset($attr['off'])) {
								$off = $attr['off'];
								unset($attr['off']);
							}

							if ($list) {
								$attr['name'] .= '[]';
								foreach($list as $value => $label){
									$attr['value'] = $value;
									$output .= '<label class="checkbox-inline"><input '.$this->renderAttr($attr).' data-toggle="toggle" data-on="'.$on.'" data-off="'.$off.'" data-onstyle="success" data-offstyle="danger"/>'.$label.'</label>';
									//$output.= '<label class="checkbox-inline"><input '.$this->renderAttr($attr).' />'.$label.'</label>';
								}

							} else {
								$output .= '<label class="checkbox-inline"><input '.$this->renderAttr($attr).' data-toggle="toggle" data-on="'.$on.'" data-off="'.$off.'"  data-onstyle="success" data-offstyle="danger"/>'.$label.'</label>';
								//$output = '<label><input '.$this->renderAttr($attr).' /></label>';
							}

							break;

						case 'hidden':
							$this->input_hidden[] = '<input type="hidden" id="'.$attr['id'].'" name="'.$attr['name'].'" value="'.$value.'" />';
							break;

						case 'barcode':
							$output = '<div class="input-group">';
							$output .= '<input '.$this->renderAttr($attr).' />';
							$output .= '<div class="input-group-addon" style="border: 0px; padding: 0px"><button class="btn btn-info" type="button" id="btnBarcode" value="Liga/desliga scanner"><i class="fa fa-barcode"></i> Scanner</button></div>';
							$output .= '</div><br><div id="scanner-container" class="viewport">';
							$output .= '</div>';
							break;

						default:
							$output = '<input '.$this->renderAttr($attr).' />';
							break;
					}

					return $output;
				}


				/**
				 * Verifica se existe um atributo
				 *
				 * @param type $needle
				 * @param type $haystack
				 * @return type
				 */
				private function hasAttr(string $needle,$haystack)
				{
					return is_array($haystack) && array_key_exists($needle, $haystack);
				}


				/**
				 * Retorna valor se existir em um array
				 *
				 * @param type $needle
				 * @param type $haystack
				 * @return type
				 */
				private function attr(string $needle,$haystack)
				{
					return is_array($haystack) && array_key_exists($needle, $haystack)? $haystack[$needle] : '';
				}


				/**
				 * Cria addon para input
				 *
				 * @param string nome icone
				 * @return string
				 */
				private function addon($name) 
				{
					if (!$name) {
						return;
					}
		
					return '<span class="input-group-addon"><span class="'.$this->iconFont.' '.$this->iconFont.'-'.$name.'"></span></span>';
				}


				/**
				 * Renderiza botoes para o form
				 *
				 * @return string
				 */
				public function renderButtons()
				{
					global $o;

					if ($this->jarr['enabled'] == 'false') {
						$buttons = $o->msgWarning("Não é possível confirmar os dados no momento");
						$this->input_hidden = '';
					} else {
						$buttons = '';

						// Titulo para o botao confirmar
						$submitBtn = $this->buttonNextCaption != '' ?  $this->buttonNextCaption : gT('Confirmar');

						// Botao voltar
						if ($this->buttonBackCaption != '') {
							if (is_object($o)) {
								$buttons .= $o->button("{icon: caret-left; type: button; title: " . $this->buttonBackCaption . "; size: $this->size; style: default; href:back").'<div class="hidden-lg hidden-md hidden-sm"><br /></div>';
							} else {
								$buttons . $o->button("{icon: caret-left; type: button; title: " . $this->buttonBackCaption . "; size: $this->size; style: default; href:back").'<div class="hidden-lg hidden-md hidden-sm"><br /></div>';
							}
						}

						// Botao confirmar
						if ($this->showSubmit) {
							if (is_object($o)) {
								if ($this->jarr["onClickSubmit"]) {
									$click = $this->jarr["onClickSubmit"]."(this)";
									$buttons .= $o->button("{id: gSubmitButton; icon: check; type: button;  name: submit_default; hint: ".gT('Clique para enviar os dados').";title: " . $submitBtn . "; size: $this->size; style: primary}", $click).'<div class="hidden-lg hidden-md hidden-sm"><br /></div>';
								} else {
									$buttons .= $o->button("{id: gSubmitButton; icon: check; type: submit;  name: submit_default; hint: ".gT('Clique para enviar os dados').";title: " . $submitBtn . "; size: $this->size; style: primary}").'<div class="hidden-lg hidden-md hidden-sm"><br /></div>';
								}
							} else {
								$buttons .= $o->button("{id: gSubmitButton; icon: check; type: submit; name: submit_default; hint: ".gT('Clique para enviar os dados').";title: " . $submitBtn . "; size: $this->size; style: primary}").'<div class="hidden-lg hidden-md hidden-sm"><br /></div>';
							}

						}

						// Botoes adicionais
						if (is_array($this->buttons)) {
							foreach ($this->buttons as $indx => $btn) {
								$btnStyle = "primary";
								$btnType  = "button";
								$btnIcon  = "";

								if ($btn['style'] != '') {
									$btnStyle = $btn['style'];
								}

								if ($btn['type'] != '') {
									$btnType = $btn['type'];
								}

								if ($btn['icon'] != '') {
									$btnIcon = 'icon: '.$btn['icon'].'; ';
								}

								if ($btn['showWait'] != '') {
									$btnshowWait = 'showWait: '.$btn['showWait'].'; ';
								}

								if ($btn['onClick'] != '') {
									$btnOnClick = 'onClick: '.$btn['onClick'].'; ';
								}

								if ($btn['target'] != '') {
									$btnTarget = 'target: ' . $btn['target'] . ';';
								}

								$btnConfirm = '';
								$buttons .= $o->button("{".$btnIcon." ".$btnConfirm." ".$btnTarget. " " . $btnshowWait. " " . $btnOnClick." type: $btnType; name: " . $btn['name'] . "; title: " . $btn['title'] . "; size: $this->size; style: $btnStyle; url: " . $btn['url'] . "; href: " . $btn['href'] ."}", $this->buttonsJavascript[$indx]).'<div class="hidden-lg hidden-md hidden-sm" style="height: 1px"><br/></div>';
							}
						}

						if (
							$this->jarr['style'] != "inline"
							|| $this->jarr['forceSubmit'] == "true"
						) {
							if ($this->class_element) {
								$buttons = tagMe('div',$buttons,'class="'.$this->class_offset.' '.$this->class_element.'"');
							}

							$buttons = tagMe('div',$buttons,'class="form-group"');
						}
					}

					return $buttons;

				}


				/**
				 * Formata atributos para os elementos
				 *
				 * @param array Associado atributo e valor
				 * @return string
				 */
				private function renderAttr($attr)
				{

					if (!$attr && !is_array($attr)) {
						return;
					}

					$data = [];

					foreach ($attr as $param => $value) {
						if ($value) {
							$data[] = $param.'="'.$value.'"';
						}
					}

					return implode(' ',$data);
				}


				/**
				 * Renderiza os input do tipo hidden
				 *
				 * @return string
				 */
				public function renderInputHidden()
				{
					$output = '';
					if ($this->input_hidden) {
						foreach($this->input_hidden as $element) {
							$output.= $element;
						}
					}

					return $output;
				}


				/**
				 * Abre tag form com seus devidos parametros
				 */
				public function openTag()
				{
					$form_attr = $this->jarr;

					// Id form
					$form_attr['id'] = $this->formId;

					// Validando com parsley
					if (
						$this->hasAttr('autoValidate',$form_attr)
						&& $form_attr['autoValidate'] == "false"
						|| $form_attr['autoValidate'] == "off"
					) {
						$form_attr['parsley-validate'] = "";
						$this->addJavascript('$("#'.$form_attr['id'].'").parsley();');
					}

					if ($this->hasAttr('focus',$form_attr)) {
						if ($form_attr['focus'] != 'false') {
							$this->addJavascript("$('#".$form_attr['focus']."').focus();");
						}
						unset($form_attr['focus']);
					} else {
						$this->addJavascript("$('#".$this->firstElementName."').focus();");
					}

					if ($this->enctype) {
						$form_attr['enctype'] = $this->enctype;
					}

					if ($this->hasAttr('title',$form_attr)) {
						unset($form_attr['title']);
					}

					if ($this->hasAttr('url',$form_attr)) {
						$form_attr['action'] = $form_attr['url'];
						unset($form_attr['url']);
					}

					if (!$this->hasAttr('method',$form_attr)) {
						$form_attr['method'] = 'post';
					}

					if ($this->hasAttr('autoValidate',$form_attr)) {
						unset($form_attr['autoValidate']);
					}

					if ($this->jarr['inline'] != 'true' || $this->jarr['forceSubmit'] == "true") {
						$js = "
							var idForm='#".$form_attr["id"]."';
							$(idForm).submit(function (e) {
								$('#gSubmitButton').attr('disabled', 'disabled');
								showWait();
								setTimeout(function () {
									$('#gSubmitButton').removeAttr('disabled');
								}, 5000);
							});
						";
						$this->addJavascript($js);
						return '<form '.$this->renderAttr($form_attr).'>';
					}
     				return '<form class="form-horizontal" '.$this->renderAttr($form_attr).'>';
				}


				/**
				 * Javascript para o form
				 */
				public function scriptsJS(): void
				{

					// Script para input multiplos file
					if ($this->has_file_multiple) {
						$this->addJavascript('
							$(document).ready(function() {
								mult_max = [];
								$(".add-other-file").on("click",function(e) {
									e.preventDefault();
									input = $(this).attr("data-input");
									max = $(this).attr("data-max");
									data = $(this).attr("data");

									if (typeof mult_max[input] == "undefined") {
										mult_max[input] = 0;
									}

									if (max > 0 && mult_max[input] >= max) {
										alert("'.gT('Limite máximo excedido').'");
										return false;
									}

									$(this).parent().before(data);
									mult_max[input] += 1;
								});
								$(".form-group").delegate(".fileinput-remove","click",function(e) {
									e.preventDefault();
									input = $(this).attr("data-input");
									$(this).parent().remove();
									mult_max[input] -= 1;
								});
							});
						');
					}

				}


				/**
				 * Fecha tag form
				 */
				public function closeTag()
				{
					return '</form>';
				}


				public function setButtonNextCaption($caption): void
				{
					$this->buttonNextCaption = $caption;
				}


				public function setButtonBackCaption($caption): void
				{
					$this->buttonBackCaption = $caption;
				}


				/**
				 * Renderiza todo formulario
				 *
				 * @param string Objeto que estara recebendo dados para carregar no buffet
				 * @return type
				 */
				public function render(&$output = '')
				{

					global $o;

					// Linhas com elementos
					$elem = '';

					switch ($this->jarr['style']) {
						case 'table':
							if (!empty($this->jarr['columns'])) {
								$row = [];
								$poeCabecalho = false;
								$elem .= $output->tableBegin('big', true, true, false, false, false);
								$cnt = 0;
								foreach (array_keys($this->fields) as $name) {
									if ($this->fields[$name]['type'] != 'hidden') {
										if ($this->fields[$name]['fieldLabel'] != "") {
											$row[] = '<-'.$this->fields[$name]['fieldLabel'];
										}

										$row[] = $this->element($name, false);
										$cnt++;
									} else {
										$this->element($name, false);
									}

									if ($cnt == $this->jarr['columns']) {
										$elem .= $output->tableRow($row, '');
										$row = '';
										$cnt = 0;
									}
								}

								if ($cnt <= $this->jarr['columns'] && $cnt > 0) {
									$elem .= $output->tableRow($row, '');
									$row = '';
								}

								$elem .= $output->tableEnd();
							} elseif ($this->rows) {
								$elem .= $output->tableBegin('big', true, true, false, false, false);
								foreach ($this->rows as $elements) {
									if (!$elements) {
										continue;
									}

									$row = [];
									foreach ($elements as $element) {
										if (
											($this->fields[$element]['type'] != 'hidden')
											&& ($this->fields[$element]['type'] != "")
										) {
											$row[] = '<-'.$this->element($element, false);
										} elseif ($this->fields[$element]['type'] != "") {
											$this->element($element, false);
										} else {
											$row[] = '<-'.$this->element($element, false);
										}
									}

									$elem .= $output->tableRow($row, '');
								}
								$elem .= $output->tableEnd();
							}

							// Abre form
							$this->output .= $this->openTag();

							// Elementos
							$this->output .= $elem;

							// Elementos hidden
							$this->output .= $this->renderInputHidden();

							// Conteúdo extra
							$this->output .= $this->customHTML;

							// Botoes
							$this->output .= $this->renderButtons();

							// Fecha form
							$this->output .= $this->closeTag();

							// Se tiver titulo insere o panel
							if ($this->title) {
								$this->output = $output->panel($this->output, $this->title);
							}

							break;

						default:
							$camposVisiveis = 0;
							if ($this->rows) {
								//if (!empty($this->jarr['columns'])
								$cnt = 0;
								$precisaFecharTag = false;
								$iniciouGrupos = false;

								if ($this->groups) {
									// Verifica se foi definido collapsed em algum momento
									$achou = false;
									$primeiroGrupo = -1;
									foreach ($this->groups as $key => $group) {
										if ($primeiroGrupo ==- 1) {
											$primeiroGrupo = $key;
										}

										if ($group['collapsed'] == 'true') {
											$achou = true;
										}
									}
									// Não foi definido, então define o primeiro grupo
									if (!$achou) {
										$this->groups[$primeiroGrupo]['collapsed'] = 'true';
									}
								}

								foreach ($this->rows as $elements) {
									if (isset($this->groups[$cnt])) {

										if (!$iniciouGrupos) {
											$elem .= $this->n . "<div class='panel-group' id='accordion_{$cnt}'>";
											$iniciouGrupos = true;
										}

										if ($precisaFecharTag) {
											$elem .= $this->n;
											$elem .= "			</div>".$this->n;
											$elem .= "		</div>".$this->n;
											$elem .= "	</div>".$this->n;
										}

										$precisaFecharTag = true;
										$in = $this->groups[$cnt]['collapsed']=='true' ? 'in' : '';
										$elem .= "	<div class='panel panel-default'>".$this->n;
										$elem .= "		<div class='panel-heading'>".$this->n;
										$elem .= "			<h4 class='panel-title'><a data-toggle='collapse' title='Clique aqui para expandir ou contrair' data-parent='#accordion_{$cnt}' href='#collapse_{$cnt}'>".$this->groups[$cnt]['title']."</a></h4>".$this->n;
										$elem .= "		</div>".$this->n;
										$elem .= "		<div id='collapse_{$cnt}' class='panel-collapse collapse $in'>".$this->n;
										$elem .= "			<div class='panel-body'>".$this->n;
									}

									if (!$elements) {
										continue;
									}

									$col_lg = round(12 / count($elements));
									$col_md = $col_lg;
									$col_xs = 12;
									$col_sm = 12;

									$class = 'col-xs-'.$col_xs.' col-sm-'.$col_sm.' col-md-'.$col_md.' col-lg-'.$col_lg;
									$row = [];
									foreach ($elements as $element) {
										if (
											($this->fields[$element]['type'] != 'hidden')
											&& ($this->fields[$element]['type'] != "")
										) {
											$row .= tagMe('div',$this->element($element),'class="'.$class.'"');
											$camposVisiveis++;
										} elseif ($this->fields[$element]['type'] != "") {
											$this->element($element);
										} else {
											$row .= tagMe('div',$element,'class="'.$class.'"');
										}
									}

									$elem .= tagMe('div',$row,'class="row"');
									$cnt++;
								}

								if ($this->groups) {
									$elem .= $this->n;
									$elem .= "			</div>".$this->n;
									$elem .= "		</div>".$this->n;
									$elem .= "	</div>".$this->n;
									$elem .= "</div>".$this->n.$this->n;
								}
							}

							if (!empty($this->jarr['columns'])) {
								$col_lg = round(12 / $this->jarr['columns']);
								$col_md = $col_lg;
           						switch ($col_lg) {
									case 2:
										$col_xs = 12;
										$col_sm = 4;
										break;
									case 3:
									case 4:
									case 6:
										$col_xs = 12;
										$col_sm = 6;
										break;
									case 12:
									default:
										$col_xs = 12;
										$col_sm = 12;
										break;
								}
								$class = 'col-xs-'.$col_xs.' col-sm-'.$col_sm.' col-md-'.$col_md.' col-lg-'.$col_lg;
								$row = [];
								$poeCabecalho = false;
								foreach (array_keys($this->fields) as $name) {
									if ($this->fields[$name]['type'] != 'hidden') {
										$row .= tagMe('div',$this->element($name),'class="'.$class.'"');
										if ($poeCabecalho) {
											$elem .= tagMe('div', $row, 'class="row"');
											$row = [];
										}
										//$poeCabecalho=!$poeCabecalho;
									} else {
										$this->element($name);
									}
								}

								if ($this->jarr['style']=="inline") {
									$row .= tagMe('div',$this->renderButtons(),'class="'.$class.'"');
								}

								if ($row != '') {
									$elem .= tagMe('div',$row,'class="row"');
								}
							} elseif (!$this->rows && (count($this->fields) > 3)) {
								// Elementos soltos
								// Se é um form simples, sem colunas, e tem menos de 5 campos, mostra em 2 colunas
								$class = 'col-xs-12 col-sm-6 col-md-6 col-lg-6';
								$row = [];
								$poeCabecalho = false;
								foreach (array_keys($this->fields) as $name) {

									if ($this->fields[$name]['type'] != 'hidden') {
										$camposVisiveis++;
										$row .= tagMe('div',$this->element($name),'class="'.$class.'"');
										if ($poeCabecalho) {
											$elem .= tagMe('div',$row,'class="row"');
											$row = [];
										}
										$poeCabecalho=!$poeCabecalho;
									} else {
										$this->element($name);
									}

								}

								if ($this->jarr['style'] == "inline") {
									$row .= tagMe('div',$this->renderButtons(),'class="'.$class.'"');
								}
								// if ($camposVisiveis==1 && $_REQUEST['g']<>'signin')
								// 	$row.= tagMe('div',tagMe('labe',"&nbsp;<br>").$this->renderButtons(),'class="'.$class.'"');
								if ($row != '') {
									$elem .= tagMe('div',$row,'class="row"');
								}

							} else {
								// Senão, mostra em 1 coluna (mais usado para telas de login)
								foreach(array_keys($this->fields) as $name){
									$elem .= $this->element($name);
								}
							}

							// Abre form
							$this->output .= $this->openTag();

							// Elementos
							$this->output .= $elem;

							// Elementos hidden
							$this->output .= $this->renderInputHidden();

							$this->output .= $this->customHTML;

							// Botoes
							//if ($camposVisiveis>1 || $_REQUEST['g']=='signin')
							if (
								$this->jarr['style'] != "inline"
								|| $this->jarr['forceSubmit'] == 'true'
							) {
								$this->output .= $this->renderButtons();
							}

							// Fecha form
							$this->output .= $this->closeTag();

							// Se tiver titulo insere o panel
							if ($this->title) {
								$this->output = $o->panel($this->output, $this->title);
							}

						break;
					}

					// Scripts
					$this->scriptsJS();

					if (is_object($output)) {
						$this->sendScripts($output);
						// $output->out($this->bufferPre,gLOC_PRE);
						// $output->out($this->bufferPos,gLOC_POS);
						// foreach ($this->bufferJavascript as $js)
						// 	$output->addJavascript($js);

						return $this->output;
					}

     				return [
						$this->bufferPre,
						$this->output,
						$this->bufferPos,
						$this->bufferJavascript
					];
				}

				/**
				Transfere scripts pré e pós HTML para outro objeto
				Útil para criar campos de formulários, sem criar formulários

				Exemplo de uso:

				$o = new gInput();
				$o->begin();

				$frm = new gForm();
				$frm->add("{name: id_frota_veiculos; type: combo; allowBlank: false; items: ".$sp['combo_frota_veiculos']."}");
				$frm->add("{name: data; type: date; value: ".gDate($this->inicioDataHora)."}");
				$html.=$frm->element('data', false);
				$html.=$frm->element('id_frota_veiculos', false);
				$frm->sendScripts($o);

				$o->out($html);
				$o->end();

				*/
				public function sendScripts(&$output): void
				{
					$output->out($this->bufferPre,gLOC_PRE);
					$output->out($this->bufferPos,gLOC_POS);
					foreach ($this->bufferJavascript as $js) {
						$output->addJavascript($js);
					}
				}
			}

	}

} else {

	$inc = $gPathDefault . "dev" . gBAR . strtolower($device) . gBAR . "gInput.php";
	if (file_exists($inc)) {
		include_once $inc;
	} else {
		$out = new g_Output();
		$out->gError("Erro", "dispositivo de acesso ao sistema não encontrado: <br>inc: $inc<br>$device");
	}
}

define('gWIKI_INDEX',  			0);
define('gWIKI_EDIT',   			1);
define('gWIKI_SEARCH',			2);
define('gWIKI_ATTACHMENTS', 	3);
define('gWIKI_ATTACHMENTS_SAVE',4);

function gAddMarkdownRequirements($conteudo = "", $mostraAjuda = true): void
{
	global $o, $gPage, $usrId, $http_lib, $gPathLib;

	if (!file_exists($gPathLib.gVar("lib.highlight").'build/highlight.js')){
		return;
	}

	$o->out('<script src="'.$http_lib . gVar("lib.markdown-it").'"></script>', gLOC_POS);
	$o->out('<script src="'.$http_lib . gVar("lib.markdown-it-emoji").'"></script>', gLOC_POS);
	$o->out('<script src="'.$http_lib . gVar("lib.markdown-it-attrs-master").'"></script>', gLOC_POS);
	$o->out('<script src="'.$http_lib . gVar("lib.highlight").'build/highlight.js"></script>', gLOC_POS);
	$o->out('<link rel="stylesheet" href="'.$http_lib . gVar("lib.highlight").'src/styles/default.css">', gLOC_POS);
	$o->out('<script src="https://cdn.jsdelivr.net/npm/mermaid@8.4.0/dist/mermaid.min.js"></script>');

	if ($conteudo == "") {

		$js = "
			hljs.initHighlightingOnLoad();
			mermaid.initialize({startOnLoad:true});

			function run() {
				var text = document.getElementById('conteudo').value;
				var target = document.getElementById('targetDiv');
				var md = window.markdownit({html:true})
										.use(window.markdownitEmoji)
										.use(window.markdownItAttrs);
				var result = md.render(text);
				result = result.replace(/img src/g, 'img class=\"img img-rounded img-responsive\" src');
				target.innerHTML = result;
				mermaid.initialize({startOnLoad:true});
			}

			function ajuda()
			{
				a = '<h2 style=\"margin-top: 0px\">Ajuda</h2>';
				a += '<br><b>Parágrafos</b><br>&nbsp;&nbsp;';
				a += 'O parágrafo deve terminar com 2 saltos de linha para o espaçamento necessário para o próximo.';
				a += '<br><b>Título</b><br>&nbsp;&nbsp;';
				a += '# Use a tecla sustenido (ou jogo da velha)';
				a += '<br><b>Sub-título</b><br>&nbsp;&nbsp;';
				a += '## Use a tecla sustenido duas vezes (ou jogo da velha)';
				a += '<br><b>Citação</b><br>&nbsp;&nbsp;';
				a += '> Use o sinal de maior';
				a += '<br><b>Itálico</b><br>&nbsp;&nbsp;';
				a += '* Use um asterisco antes e outro depois do texto *';
				a += '<br><b>Negrito</b><br>&nbsp;&nbsp;';
				a += '** Use dois asteriscos antes e depois do texto **';
				a += '<br><b>Tachado</b><br>&nbsp;&nbsp;';
				a += '~~ Use dois tils antes e depois do texto ~~';
				a += '<br><b>Bloco de código</b><br>&nbsp;&nbsp;';
				a += '``` Agudo invertido no ínicio e no final do bloco ```';
				a += '<br><b>Listas não numeradas</b><br>&nbsp;&nbsp;';
				a += '- Sinal de menos<br>';
				a += '&nbsp;&nbsp;+ Sinal de mais<br>';
				a += '&nbsp;&nbsp;* Sinal de asterisco';
				a += '<br><b>Listas numeradas</b><br>&nbsp;&nbsp;';
				a += '1. Número<br>';
				a += '&nbsp;&nbsp;2. Outro número';
				a += '<br><b>Emojis</b><br>&nbsp;&nbsp;';
				a += ':smile: ou ;-)';
				a += '<br><b>Botão</b><br>&nbsp;&nbsp;';
				a += '{.btn .btn-success}';
				a += '<br><b>Label</b><br>&nbsp;&nbsp;';
				a += '{.label .label-success}';

				a += '<br><b>Link</b><br>&nbsp;&nbsp;';
				a += '\< http://google.com.br \><br>';
				a += '&nbsp;&nbsp;[Título](http://google.com.br)';
				a += '<br><b>Imagem</b><br>&nbsp;&nbsp;';
				a += '![Alt text](url\/to\/image \"Título\")';
				a += '<br><b>Imagem responsiva</b><br>&nbsp;&nbsp;';
				a += '![Alt text](url\/to\/image \"Título\"){.img .img-responsive}';
				a += '<br><br><a target=\"_new\" href=\"https://blog.da2k.com.br/2015/02/08/aprenda-markdown/\">Ajuda completa (mais comandos)</a><br>';
				a += '<a target=\"_new\" href=\"https://mermaid-js.github.io/mermaid-live-editor/#/edit/\">Criar gráficos online</a><br>';
				if (document.getElementById('targetDiv') !== null) {
					document.getElementById('targetDiv').innerHTML = a;
				}
			}

			$('#conteudo').keyup(function(){
				run();
			});
		";

		if ($mostraAjuda) {
			$js .= "ajuda();";
		}

	} else {
		$js = "
			hljs.initHighlightingOnLoad();
			var text = \"".$conteudo."\";
			var target = document.getElementById('targetDiv');
			var md = window.markdownit({html:true})
							.use(window.markdownItAttrs)
							.use(window.markdownitEmoji);
			//md = window.markdownit();

			var result = md.render(text);
			result = result.replace(/img src/g, 'img class=\"img img-rounded img-responsive\" src');
			target.innerHTML = result;
			mermaid.initialize({startOnLoad:true});
		";

	}

	$o->addJavascript($js);

}


class gWiki
{

	public $html = "";
	public $page = "";
	public $id = 0;

	public function decode($conteudo, $prepararJavascript = true): string|array
	{
		global $LOCALHOST;

		$sai = str_replace('”', '"', $conteudo);
		$sai = str_replace("‘", "'", $sai);
		$sai = str_replace("«", "{", $sai);
		$sai = str_replace("»", "}", $sai);
		$sai = str_replace("^g", "<div class='mermaid'>", $sai);
		$sai = str_replace("g^", "</div>", $sai);
		// $sai = str_replace('<div class="mermaid">', "^g", $sai);
		// $sai = str_replace('</div>', "g^", $sai);

		if ($prepararJavascript) {
			$sai = str_replace("'","\'", $sai);
			$sai = str_replace('"','\"', $sai);
			$sai = str_replace("\n",'\n',$sai);
			$sai = str_replace("\r",'',  $sai);
			$sai = str_replace("\t",'\t',$sai);
		}

		if ($LOCALHOST) {
			return str_replace('https://app.giusoft.com.br/','http://localhost/', $sai);
		}

		return($sai);
	}


	public function encode($conteudo): array|string
	{
		$sai = str_replace('"', '”', $conteudo);
		$sai = str_replace("'", "‘", $sai);
		$sai = str_replace("{", "«", $sai);
		return(str_replace("}", "»", $sai));
	}


	public function label(array $tag): string
	{
		global $o;
		match ($tag['id']) {
			1 => $sai .= $o->button("{title: ".$tag['descricao']."; size: tiny; style: primary; href: index.php?gSearch=%3A".$tag['descricao']."}"),
			2 => $sai .= $o->button("{title: ".$tag['descricao']."; size: tiny; style: success; href: index.php?gSearch=%3A".$tag['descricao']."}"),
			3 => $sai .= $o->button("{title: ".$tag['descricao']."; size: tiny; style: warning; href: index.php?gSearch=%3A".$tag['descricao']."}"),
			4 => $sai .= $o->button("{title: ".$tag['descricao']."; size: tiny; style: info; href: index.php?gSearch=%3A".$tag['descricao']."}"),
			5 => $sai .= $o->button("{title: ".$tag['descricao']."; size: tiny; style: danger; href: index.php?gSearch=%3A".$tag['descricao']."}"),
			default => $sai .= $o->button("{title: ".$tag['descricao']."; size: tiny;style; href: index.php?gSearch=%3A".$tag['descricao']."}"),
		};
		return($sai);
	}


	public function adicionaEstilosPersonalizados(): void
	{
		$this->html .= '
			<style>
				#targetDiv {
					margin-top: 8px
				}

				#targetDiv h1 {
					margin-top: 0;
					padding-bottom: 16px;
					padding-top: 16px;
					text-shadow: 4px 4px 10px #999;
				}

				#targetDiv h2 {
					margin-top: 0;
					padding-bottom: 16px;
					padding-top: 16px;
					text-shadow: 4px 4px 10px #999;
				}

				#targetDiv h3 {
					margin-bottom: 0px;
					padding-bottom: 16px;
					padding-top: 16px;
					border-top: 1px solid #ddd;
					font-size: 28px;
					font-style: normal;
					font-variant: all-petite-caps;
					font-weight: bold;
				}

				#targetDiv h4 {
					margin-bottom: 0px;
					padding-bottom: 16px;
					padding-top: 16px;
					font-size: 18px;
					font-style: normal;
					font-weight: bold;
				}

				table {
					border: 1px solid #d0d0d0;
					width: 100%;
					text-align: center;
					border-collapse: collapse;
				}

				table td, table th {
					border: 1px solid #d0d0d0;
					padding: 3px 4px;
				}

				table tbody td {
				}

				table td:nth-child(even) {
					background: #EBEBEB;
				}

				table thead {
					background: #d0d0d0;
					border-bottom: 1px solid #333333;
				}

				table thead th {
					font-size: 15px;
					font-weight: bold;
					color: #333333;
					text-align: center;
					border-left: 1px solid #333333;
				}

				table thead th:first-child {
					border-left: none;
				}

				table tfoot {
					font-size: 14px;
					font-weight: bold;
					color: #333333;
					border-top: 1px solid #333333;
				}

				table tfoot td {
				}

			</style>
		';
	}


	public function __construct($msgInicial)
	{
		global $o, $gPage, $gId, $http_lib, $usrId, $gPath, $http_base;

		$gPathUsrFiles  = $gPath."files/wiki/";
		$http_usr_files = $http_base."files/wiki/";

		if ($_REQUEST['lock'] > 0) {
			if ($gId == 0) {
				dbQuery("UPDATE wiki SET bloqueado=1-bloqueado WHERE id = 1 AND (id_pessoas_criou = $usrId OR " . ($usrId == 1 ? "1=1" : "1=0") . ")");
			} else {
				dbQuery("UPDATE wiki SET bloqueado=1-bloqueado WHERE id = " . $gId . " AND (id_pessoas_criou = $usrId OR " . ($usrId == 1 ? "1=1" : "1=0") . ")");
			}

		}

		if ($gPage == gWIKI_INDEX && $gId == 0) {
			$rs = dbQuery("SELECT * FROM wiki WHERE id = 1");
			if ($rs) {
				$this->id = 1;
			}
		} else {
			$this->id = $gId;
		}

		if ($gPage == gWIKI_EDIT && $gId == 0) {
			$this->html .= $o->msgTitle("Nova página");
		} elseif ($gPage == gWIKI_INDEX && $this->id == 0) {
			$this->html .= $o->msgTitle($msgInicial);
		} else {
			if ($_REQUEST['gSearch'] != "") {
				if (is_numeric($_REQUEST['gSearch'])) {
					$this->id = intval($_REQUEST['gSearch']);
				} else {
					$this->id = -1;
				}
			}

			if ($this->id > 0) {
				$this->open();
				$this->html .= $o->msgTitle($this->page['titulo']);
			} else {
				$this->html .= $o->msgTitle("Busca por '".gCleanField($_REQUEST['gSearch'])."'");
			}
		}

		$this->toolbar();

		if ($gPage == gWIKI_ATTACHMENTS) {
			$this->html .= $o->msgSubTitle("Adicionar arquivo anexo a esta página");
			$frm = new gForm("{columns: 2}");
			$frm->add("{name: descricao; type: text; }");
			$frm->add("{name: arquivo; type: file; }");
			$frm->add("{name: gPage; type: hidden; value: ".gWIKI_ATTACHMENTS_SAVE."}");
			$frm->add("{name: gId; type: hidden; value: $gId}");
			$this->html .= $frm->render($o);

			$sql = "SELECT * FROM wiki_anexos WHERE id_wiki=$gId";
			$rs  = dbQuery($sql);
			if ($rs) {
				$this->html .= $o->msgSubTitle("Documentos atuais anexados");
				$this->html .= $o->tableBegin('big', true);
				foreach ($rs as $row) {
					$mtz = [];
					$fileName = $row['id'].'.'.substr((string) $row['tipo'],strpos((string) $row['tipo'],'/')+1);
					$filePath = $http_usr_files.$fileName;
					$prev = $o->button('{icon: paperclip; style: info; href: '.$filePath);

					if (
						str_contains((string) $row['tipo'], 'jpeg')
						|| str_contains((string) $row['tipo'], 'jpg')
						|| str_contains((string) $row['tipo'], 'png')
						|| str_contains((string) $row['tipo'], 'gif')
					) {
						$prev = "<a href='$filePath'><img width='150' class='img img-responsive' src='$filePath'></a>";
					}

					$mtz[] = "<-" . $prev;
					$mtz[] = "<-" . $row['descricao'].$o->br().$o->small($row['nome'].$o->br().$row['tipo']);
					$mtz[] = "<-" . $filePath;
					$this->html .= $o->tableRow($mtz, 'detail');
				}

				$this->html .= $o->tableEnd('big', true);
			}

		} elseif ($gPage == gWIKI_ATTACHMENTS_SAVE) {
			$tamanhoMaximo = 4000000;
			if (intval($_FILES['arquivo']['size']) > 0) {
				// Verifica tamanho do arquivo
				if ($_FILES['arquivo']['size'] > $tamanhoMaximo) {
					$erros[] = 'Arquivo em tamanho muito grande! A imagem deve ser de no máximo ' . $tamanhoMaximo . ' bytes.';
				}

				if (is_array($erros)) {
					$erro = true;
					$msgErro = "Não foi possível salvar o arquivo de imagem." . $o->br(2);
					$msgErro .= $o->li($erros);
					$msgErro .= $o->br() . 'Envie outro arquivo...';
					$this->html .= $o->br() . $o->msgDanger($msgErro);
					return;
				}

				$flds = array();
				$flds['id_wiki']   = $gId;
				$flds['nome'] 	   = $_FILES['arquivo']['name'];
				$flds['tipo'] 	   = $_FILES['arquivo']['type'];
				$flds['descricao'] = gCleanField($_REQUEST['descricao']);

				// Evita falha de upload por inexitência do diretório
				if (!file_exists($gPathUsrFiles)) {
					mkdir($gPathUsrFiles);
					chmod($gPathUsrFiles,755);
				}

				$fileId   = dbInsert('wiki_anexos', $flds, true);
				$fileName = $fileId.'.'.substr($_FILES['arquivo']['type'],strpos($_FILES['arquivo']['type'],'/')+1);
				$ok = move_uploaded_file($_FILES['arquivo']['tmp_name'], $gPathUsrFiles . $fileName);
				chmod($gPathUsrFiles . $fileName, 0644); // evita ação de hackers
				gLog("===> Imagem salva: ".$gPathUsrFiles . $fileName . " (".$_FILES['arquivo']['tmp_name'].")");
			}

			redirect($o->page."&gPage=".gWIKI_ATTACHMENTS."&gId=".$gId);

		} else {
			if ($this->id > 0 && $gPage != gWIKI_EDIT) {
				$this->adicionaEstilosPersonalizados();
				$this->html .= $o->br(1);
				$sql = "SELECT T.id, T.descricao
						FROM wiki_versoes_tags WT
						LEFT JOIN wiki_tags T ON WT.id_wiki_tags=T.id
						WHERE WT.id_wiki_versoes=".$this->page['id']. "
						ORDER BY T.id";
				$rst = dbQuery($sql);
				$this->html .= $o->label($this->id);
				foreach ($rst as $row) {
					$this->html.=$this->label($row);
				}

				$this->html .= $o->small(($this->page['aprovado'] ? "Aprovado":"Reprovado").($this->page['data_mostrar']=='0000-00-00 00:00:00' ? "" : " • Mostrar em ".gDateTime($this->page['data_mostrar']))." • ".($this->page['data_ocultar']=='0000-00-00 00:00:00' ? "" : " • Ocultar em ".gDateTime($this->page['data_ocultar']))." • Criado em ".gDateTime($this->page['data_criacao'])." por ".$this->page['criou']." • Última versão: ".gDateTime($this->page['data_versao'])." por ".$this->page['versionou']. " • Nº da versão: ".$this->page['versao']);
				//$this->html.=$o->hr(1);
				$this->html .= '<div id="targetDiv" class="well"></div>';
				$conteudo = $this->decode($this->page['conteudo']);
				gAddMarkdownRequirements($conteudo, false);
				// $o->out('<script src="'.$http_lib . gVar("lib.markdown-it").'"></script>', gLOC_POS);
				// $o->out('<script src="'.$http_lib . gVar("lib.markdown-it-emoji").'"></script>', gLOC_POS);
				// $o->out('<script src="'.$http_lib . gVar("lib.markdown-it-attrs-master").'"></script>', gLOC_POS);
				// $o->out('<script src="'.$http_lib . gVar("lib.highlight").'build/highlight.js"></script>', gLOC_POS);
				// $o->out('<link rel="stylesheet" href="'.$http_lib . gVar("lib.highlight").'src/styles/default.css">', gLOC_POS);
				// $o->out('<script src="https://cdn.jsdelivr.net/npm/mermaid@8.4.0/dist/mermaid.min.js"></script>');
				// //$this->html = '<script src="'.$http_lib . gVar("lib.mermaid").'"></script>';
				// $js="
				// 	hljs.initHighlightingOnLoad();
				// 	var text = \"".$conteudo."\";
				// 	var target = document.getElementById('targetDiv');
				// 	var md = window.markdownit({html:true})
				// 					.use(window.markdownItAttrs)
				// 					.use(window.markdownitEmoji);
				// 	//md = window.markdownit();
				// 	var result = md.render(text);
				// 	target.innerHTML = result;
				// 	mermaid.initialize({startOnLoad:true});
				// ";
				// $o->addJavascript($js);
			} elseif ($gPage != gWIKI_EDIT) {
				$buscar = gCleanField($_REQUEST['gSearch']);
				if (str_starts_with($buscar, ":")) {
					$filtros = [];
					if (str_contains($buscar,",")) {
						$itens = explode(",", substr($buscar,1));
						foreach ($itens as $item) {
							$filtros[] = "WT.descricao LIKE '".$item."'";
						}

					} else {
						$filtros[] = "WT.descricao LIKE '".substr($buscar,1)."'";
					}

					$filtro = "(".implode(" OR ", $filtros).")";
					$this->html.=$o->msgSubTitle("Páginas com a tag ".$o->label(substr($buscar,1)));
				} elseif (intval($buscar) > 0) {
					$filtro = "W.id=".intval($buscar);
				} else {
					$filtro = "WV.titulo LIKE '%$buscar%' OR WV.conteudo LIKE '%$buscar%'";
					if ($buscar != "") {
						$this->html.=$o->msgSubTitle("Páginas que contenham ".$o->label($buscar));
					}
				}

				$sql = "SELECT DISTINCT
							W.id id_page,
							W.data_criacao,
							W.bloqueado,
							W.aprovado,
							W.data_mostrar,
							W.data_ocultar,
							P.apelido criou,
							PV.apelido versionou,
							WV.titulo,
							WV.conteudo,
							WV.id id_versao
						FROM wiki W
						LEFT JOIN wiki_versoes WV ON W.id=WV.id_wiki
						LEFT JOIN wiki_versoes_tags WVT ON WV.id=WVT.id_wiki_versoes
						LEFT JOIN wiki_tags WT ON WVT.id_wiki_tags=WT.id
						LEFT JOIN pessoas P ON W.id_pessoas_criou = P.id
						LEFT JOIN pessoas PV ON WV.id_pessoas_versao = PV.id
						WHERE (".$filtro.") AND WV.ativo=1 AND ((W.data_mostrar='0000-00-00 00:00:00') OR ('".date("Y-m-d H:i:s")."' BETWEEN W.data_mostrar AND W.data_ocultar)) ORDER BY W.id";
				$rs = dbQuery($sql);
				$this->html .= $o->hr(1);
				$cnt = 0;
				foreach ($rs as $row) {
   					$cnt++;
					$sql = "SELECT WT.*
							FROM wiki_versoes_tags WVT
							LEFT JOIN wiki_tags WT ON WVT.id_wiki_tags=WT.id
							WHERE WVT.id_wiki_versoes=".$row['id_versao']."
							ORDER BY WT.id";
					$rst  = dbQuery($sql);
					$tags = "";
					foreach ($rst as $rt) {
						$tags .= $this->label($rt);
					}

					$lock = ($row['bloqueado'] ? $o->chooseIcon('lock','fa', 'tiny') : $o->chooseIcon('unlock','fa', 'tiny'));
   					$conteudo = $o->small(strip_tags(str_replace('\n',' • ', str_replace("#","", str_replace("~"," ", substr((string) $this->decode($row['conteudo']),0,200))))));
					$this->html .= '<a href="'.$o->page.'&gPage='.gWIKI_INDEX.'&gId='.$row['id_page'].'">'.$row['id_page'].". ".$row['titulo'].'</a><br>'. $lock.$conteudo;
					$this->html .= $o->small($o->br().$tags." Criado por ".$row['criou']." em ".gDateTime($row['data_criacao'])).$o->br(2);
				}
			}

			if ($gPage == gWIKI_EDIT) {
				$this->editPage();
			}
		}

	}

	public function toolbar(): void
	{
		global $o, $usrId, $gPage, $usrName;

		$this->html .= '<form role="form" class="form-inline" style="display: inline" action="'.$o->page.'&gPage='.gWIKI_SEARCH.'">';
		$this->html .= '<div class="form-group">';
		$this->html .= $o->button("{icon: home; size: small; title: Início; hint: Volta pra página inicial;href: ".$o->page."&gPage=".gWIKI_INDEX."}");
		$this->html .= $o->button("{icon: plus; size: small; title: Nova; hint: Nova página Wiki; href: ".$o->page."&gPage=".gWIKI_EDIT."}");
		$this->html .= '<input class="form-control input-sm" style="width: 200px" placeholder="'.gT('Procurar por').'" name="gSearch" type="text" value="'.gCleanField($_REQUEST['gSearch']).'"> ';
		$this->html .= '<button type="submit" class="btn btn-default btn-sm"><span class="fa fa-search"></span></button>';
		$this->html .= '</div>'.$o->n.$o->n;
		$this->html .= '</form>';
		$this->html .=  $o->label($usrName);

		if ($this->id > 0) {
			if (!$this->page['bloqueado']) {
				$this->html .= $o->button("{size: small; icon: pencil; title: Editar; href: ".$o->page."&gPage=".gWIKI_EDIT."&gId=".$this->page['id_page']."}");
				$this->html .= $o->button("{size: small; icon: paperclip; title: Anexos; href: ".$o->page."&gPage=".gWIKI_ATTACHMENTS."&gId=".$this->page['id_page']."}");
			}

			if ($usrId == $this->page['id_pessoas_criou'] || $usrId == 1) {
				if ($this->page['bloqueado']) {
					$this->html .= $o->button("{size: small; icon: lock; title: Desbloquear; style: danger; href: ".$o->page."&gPage=".$gPage."&gId=".$this->id."&lock=1}");

				} else {
					$this->html .= $o->button("{size: small; icon: unlock; title: Bloquear; style: success; href: ".$o->page."&gPage=".$gPage."&gId=".$this->id."&lock=1}");

				}
			}
		}
		$this->html .= $o->br();

	}


	public function open(): void
	{
		global $o;

		if ($this->id == 0) {
			$this->id = 1;
		}

		$sql = "SELECT
					W.id id_page,
					W.data_criacao,
					W.bloqueado,
					W.aprovado,
					W.data_mostrar,
					W.data_ocultar,
					P.apelido criou,
					PV.apelido versionou,
					WV.*
				FROM wiki W
				LEFT JOIN wiki_versoes WV ON W.id=WV.id_wiki
				LEFT JOIN pessoas P ON W.id_pessoas_criou = P.id
				LEFT JOIN pessoas PV ON WV.id_pessoas_versao = PV.id
				WHERE W.id=".$this->id." AND WV.ativo=1 ORDER BY WV.id DESC";
		$rs = dbQuery($sql);

		if (!$rs) {
			$sql = "SELECT
						W.id id_page,
						W.data_criacao,
						W.bloqueado,
						W.aprovado,
						W.data_mostrar,
						W.data_ocultar,
						P.apelido criou,
						PV.apelido versionou,
						WV.*
					FROM wiki W
					LEFT JOIN wiki_versoes WV ON W.id=WV.id_wiki
					LEFT JOIN pessoas P ON W.id_pessoas_criou = P.id
					LEFT JOIN pessoas PV ON WV.id_pessoas_versao = PV.id
					WHERE W.id=1 AND WV.ativo=1 ORDER BY WV.id DESC";
			$rs = dbQuery($sql);
		}

		$this->page = $rs[0];
		$this->page['criou'] = ucfirst((string) $this->page['criou']);
		$this->page['versionou'] = ucfirst((string) $this->page['versionou']);
		$this->id = intval($this->page['id_page']);
	}

	public function editPage(): void
	{
		global $o, $gPage, $usrId, $http_lib;

		gAddMarkdownRequirements('', true);

		// $o->out('<script src="'.$http_lib . gVar("lib.markdown-it").'"></script>', gLOC_POS);
		// $o->out('<script src="'.$http_lib . gVar("lib.markdown-it-emoji").'"></script>', gLOC_POS);
		// $o->out('<script src="'.$http_lib . gVar("lib.markdown-it-attrs-master").'"></script>', gLOC_POS);
		// $o->out('<script src="'.$http_lib . gVar("lib.highlight").'build/highlight.js"></script>', gLOC_POS);
		// $o->out('<link rel="stylesheet" href="'.$http_lib . gVar("lib.highlight").'src/styles/default.css">', gLOC_POS);
		// $o->out('<script src="https://cdn.jsdelivr.net/npm/mermaid@8.4.0/dist/mermaid.min.js"></script>');
		// $js="

		// 	hljs.initHighlightingOnLoad();
		// 	mermaid.initialize({startOnLoad:true});

		// 	function run() {
		// 		var text = document.getElementById('conteudo').value;
		// 		var target = document.getElementById('targetDiv');
		// 		var md = window.markdownit({html:true})
		// 								.use(window.markdownitEmoji)
		// 								.use(window.markdownItAttrs);
		// 		var result = md.render(text);
		// 		target.innerHTML = result;
		// 		mermaid.initialize({startOnLoad:true});
		// 	}

		// 	function ajuda()
		// 	{
		// 		a = '<h2 style=\"margin-top: 0px\">Ajuda</h2>';
		// 		a+= '<br><b>Parágrafos</b><br>&nbsp;&nbsp;';
		// 		a+= 'O parágrafo deve terminar com 2 saltos de linha para o espaçamento necessário para o próximo.';
		// 		a+= '<br><b>Título</b><br>&nbsp;&nbsp;';
		// 		a+= '# Use a tecla sustenido (ou jogo da velha)';
		// 		a+= '<br><b>Sub-título</b><br>&nbsp;&nbsp;';
		// 		a+= '## Use a tecla sustenido duas vezes (ou jogo da velha)';
		// 		a+= '<br><b>Citação</b><br>&nbsp;&nbsp;';
		// 		a+= '> Use o sinal de maior';
		// 		a+= '<br><b>Itálico</b><br>&nbsp;&nbsp;';
		// 		a+= '* Use um asterisco antes e outro depois do texto *';
		// 		a+= '<br><b>Negrito</b><br>&nbsp;&nbsp;';
		// 		a+= '** Use dois asteriscos antes e depois do texto **';
		// 		a+= '<br><b>Tachado</b><br>&nbsp;&nbsp;';
		// 		a+= '~~ Use dois tils antes e depois do texto ~~';
		// 		a+= '<br><b>Bloco de código</b><br>&nbsp;&nbsp;';
		// 		a+= '``` Agudo invertido no ínicio e no final do bloco ```';
		// 		a+= '<br><b>Listas não numeradas</b><br>&nbsp;&nbsp;';
		// 		a+= '- Sinal de menos<br>';
		// 		a+= '&nbsp;&nbsp;+ Sinal de mais<br>';
		// 		a+= '&nbsp;&nbsp;* Sinal de asterisco';
		// 		a+= '<br><b>Listas numeradas</b><br>&nbsp;&nbsp;';
		// 		a+= '1. Número<br>';
		// 		a+= '&nbsp;&nbsp;2. Outro número';
		// 		a+= '<br><b>Emojis</b><br>&nbsp;&nbsp;';
		// 		a+= ':smile: ou ;-)';
		// 		a+= '<br><b>Botão</b><br>&nbsp;&nbsp;';
		// 		a+= '{.btn .btn-success}';
		// 		a+= '<br><b>Label</b><br>&nbsp;&nbsp;';
		// 		a+= '{.label .label-success}';

		// 		a+= '<br><b>Link</b><br>&nbsp;&nbsp;';
		// 		a+= '\< http://google.com.br \><br>';
		// 		a+= '&nbsp;&nbsp;[Título](http://google.com.br)';
		// 		a+= '<br><b>Imagem</b><br>&nbsp;&nbsp;';
		// 		a+= '![Alt text](url\/to\/image \"Título\")';
		// 		a+= '<br><b>Imagem responsiva</b><br>&nbsp;&nbsp;';
		// 		a+= '![Alt text](url\/to\/image \"Título\"){.img .img-responsive}';
		// 		a+= '<br><br><a target=\"_new\" href=\"https://blog.da2k.com.br/2015/02/08/aprenda-markdown/\">Ajuda completa (mais comandos)</a><br>';
		// 		a+= '<a target=\"_new\" href=\"https://mermaid-js.github.io/mermaid-live-editor/#/edit/\">Criar gráficos online</a><br>';
		// 		document.getElementById('targetDiv').innerHTML = a;
		// 	}

		// 	$('#conteudo').keyup(function(){
		// 	  run();
		// 	});

		// 	ajuda();

		// ";
		// $o->addJavascript($js);
		$titulo 	  = gCleanField($_REQUEST['titulo']);
		$conteudo     = ($_REQUEST['conteudo']);
		$data_mostrar = gDBDateTime($_REQUEST['data_mostrar']);
		$data_ocultar = gDBDateTime($_REQUEST['data_ocultar']);
		$aprovado 	  = gDBCheck($_REQUEST['aprovado']);

		$tags = $_REQUEST['tags'];

		if ($titulo != "") {
			if ($this->id == 0) {
				$mtz = [];
				$mtz['data_criacao'] = date("Y-m-d H:i:s");
				$mtz['id_pessoas_criou'] = $usrId;
				$this->id = dbInsert("wiki", $mtz, true);
			}

			$sql = "UPDATE wiki_versoes SET ativo = 0 WHERE id_wiki = " . $this->id;
			dbQuery($sql);

			$mtz = [];
			$mtz['data_mostrar'] = $data_mostrar;
			$mtz['data_ocultar'] = $data_ocultar;
			$mtz['aprovado'] = $aprovado;
			if ($aprovado) {
				$mtz['id_pessoas_aprovou'] = $usrId;
			}

			dbUpdate("wiki", $mtz, $this->id);

			$mtz = [];
			$mtz['id_wiki'] 		  = $this->id;
			$mtz['data_versao'] 	  = date("Y-m-d H:i:s");
			$mtz['id_pessoas_versao'] = $usrId;
			$mtz['versao'] 	 		  = intval(dbQuery("SELECT COUNT(id) ttl FROM wiki_versoes WHERE id_wiki=".$this->id)[0]['ttl'])+1;
			$mtz['titulo'] 	 		  = $titulo;
			$mtz['conteudo'] 		  = $this->encode($conteudo);
			$gIdVersao = dbInsert("wiki_versoes", $mtz, true);

			$mtz = [];
			$mtz['id_wiki_versoes'] = $gIdVersao;
			foreach ($tags as $tag) {
				$mtz['id_wiki_tags'] = $tag;
				dbInsert("wiki_versoes_tags", $mtz);
			}

			redirect($o->page."&gPage=".gWIKI_SEARCH."&gId=".$this->id);
		} else {
			$sql = "SELECT * FROM wiki_versoes_tags WHERE id_wiki_versoes=".intval($this->page['id'])." ORDER BY id_wiki_tags";
			$rs  = dbQuery($sql);
			$tags = [];
			foreach ($rs as $row) {
				$tags[] = $row['id_wiki_tags'];
			}

			$conteudo = str_replace("“",'"',$this->decode($this->page['conteudo'], false));
			$conteudo = str_replace("<div class='mermaid'>", "^g", $conteudo);
			$conteudo = str_replace("</div>", "g^", $conteudo);

			$this->adicionaEstilosPersonalizados();
			$frm = new gForm("{columns: 2}");
			$frm->row(
				$frm->add("{name: titulo; filedLabel: Título; hint: Descreva o título de forma sucinta porém representativa; type: text; value: ".$this->page['titulo']."}"),
				$frm->add("{name: tags; hint: Selecione palavras chave que identifiquem facilmente o conteúdo; type: comboMultiSelection; value: ".implode(", ",$tags)."; items: SELECT id, descricao FROM wiki_tags ORDER BY descricao; }")
			);

			$frm->row(
				$frm->add("{name: data_mostrar; type: dateTime; hint: Data que este conteúdo será exibido; value: ".$this->page['data_mostrar']."}"),
				$frm->add("{name: data_ocultar; type: dateTime; hint: Data que este conteúdo será ocultado; value: ".$this->page['data_ocultar']."}"),
				$frm->add("{name: aprovado; type: checkbox; hint: Se o conteúdo será mostrado ou não; value: ".$this->page['aprovado']."}")
			);

			//$frm->add("{name: conteudo; hint: Conteúdo; rows: 8; type: textarea; value: ".str_replace("~","\n",$this->page['conteudo'])."}");
			//$frm->add("{name: conteudo_html; type: html; value: <div class='row'><div class='col-lg-6 col-md-6 col-sm-12 col-xs-12'><textarea name='conteudo' id='conteudo' rows='15' class='form-control'>".$conteudo."</textarea></div><div class='col-lg-6 col-md-6 col-sm-12 col-xs-12'><dir id='targetDiv' class='well'></div> </div></div>}");

			$frm->add("{name: gId; type: hidden; value: ".$this->id."}");
			$frm->add("{name: gPage; type: hidden; value: ".$gPage."}");

			$frm->addHTML("<div class='row'><div class='col-lg-6 col-md-6 col-sm-12 col-xs-12'><textarea name='conteudo' id='conteudo' style='height: 600px' class='form-control'>".$conteudo."</textarea></div><div class='col-lg-6 col-md-6 col-sm-12 col-xs-12'><dir id='targetDiv' style='height: 600px; display: block; overflow-y: scroll; margin: 0px' class='well'></div> </div></div>");
			$frm->addButton("{title: Ajuda; style: success; showWait: false; onClick: ajuda()}");
			$this->html .= $o->br();
			$this->html .= $frm->render($o);
			//$this->html.="<div class='row'><div class='col-lg-6 col-md-6 col-sm-12 col-xs-12'><textarea name='conteudo' id='conteudo' style='height: 600px' class='form-control'>".$conteudo."</textarea></div><div class='col-lg-6 col-md-6 col-sm-12 col-xs-12'><dir id='targetDiv' style='height: 600px; display: block; overflow-y: scroll; margin: 0px' class='well'></div> </div></div>";
		}
	}


	public function render($o)
	{
		return($this->html);
	}

}


class gPagination
{
	public $numero_pagina;
	public $numero_registro_por_pagina;
	public $total_paginas;
	public $total_rs;
	public $iniciar;
	public $proxima_pagina;
	public $anterior_pagina;

	public function addPagination($rs, $por_pagina): static
	{
		$this->total_rs=$rs[0]['ttl'];

		$this->numero_registro_por_pagina=$por_pagina;
		$this->numero_pagina = isset($_REQUEST["gPagination"]) ? intval($_REQUEST["gPagination"]) : 1;
		if ($this->numero_pagina > 1) {
			$this->anterior_pagina = $this->numero_pagina-1;
		}

		if ($this->numero_pagina < $this->total_rs) {
			$this->proxima_pagina = $this->numero_pagina+1;
		}

		$this->total_paginas=ceil($this->total_rs / $this->numero_registro_por_pagina);
		$this->iniciar = ($this->numero_pagina-1) * $this->numero_registro_por_pagina;

		if ($this->iniciar < 0) {
			$this->iniciar = 0;
		}

		return ($this);
	}

	public function render($json = '{}'): ?string
	{
		global $o;

		if (
			$_REQUEST['gPDF'] == 0
			&& $_REQUEST['gXLS'] == 0
			&& $_REQUEST['gDOC'] == 0
			&& $_REQUEST['gCSV'] == 0
		) {
			$jarr = cssDecode($json);
			$id   = isset($jarr["id"]) ?? "g";

			$size = isset($jarr["size"]) ?? "sm";

			$style = isset($jarr["style"]) ?? "";

			if ($this->total_rs > 0) {
				$html = "<nav aria-label='Page navigation {$id}-nav' style={$style}>";
				$html .= "<ul class='pagination pagination-{$size}'>";
				$frm  .= "<form action='' id='{$id}-formRequest' method='POST'>";
				foreach ($_REQUEST as $key => $value) {
					$frm .= "<input type='hidden' name='{$key}' value='{$value}'/>";
					//$frm.="<input type='hidden' name='gPagination' value=''/>";
				}

				$frm  .= "<input type='hidden' name='gPagination' id='{$id}-gPagination' value=''/>";
				$frm  .= "</form>";
				$html .= $frm;
				if ($this->numero_pagina == 1) {
					$html .= "<li style='cursor:pointer;' class='page-item disabled'><a class='page-link' href='javascript:;'>&laquo;</a></li>";
				} else {
					$rota = $o->page."&gPagination=".$this->anterior_pagina;
					$html .= "<li class='page-item'><a id='{$id}-btnAnterior' class='page-link' style='cursor:pointer;'>&laquo;</a></li>";
					$js = "$('#{$id}-btnAnterior').on('click', function () {
						$('#{$id}-formRequest').attr('action', '".$rota."');
						$('#{$id}-gPagination').val('".$this->anterior_pagina."');
						$('#{$id}-formRequest').submit();
					})";
					$o->addJavascript($js);
				}

				$iniciar = ($this->numero_pagina-5>0) ? $this->numero_pagina-5 : 1;
				$finalizar = $this->numero_pagina + 5;
				$cnt = 0;

				for ($i = $iniciar; $i <= $this->total_paginas; $i++) {
					$cnt++;
					$rota = $o->page."&gPagination=".$i;
					if ($i == $this->numero_pagina) {
						$html .= '<li class="page-item active"><a id="'.$id.'-btnPagination'.$i.'" class="page-link">'.$i.'</a></li>';
					} else {
						$html .= '<li style="cursor:pointer;" class="page-item"><a id="'.$id.'-btnPagination'.$i.'" class="page-link">'.$i.'</a></li>';
					}

					$js = "$('#{$id}-btnPagination".$i."').on('click', function () {
							$('#{$id}-formRequest').attr('action', '".$rota."');
							$('#{$id}-gPagination').val('".$i."');
							$('#{$id}-formRequest').submit();
						})";
					$o->addJavascript($js);

					if ($cnt == 10) {
						break;
					}
				}

				if ($this->numero_pagina == $this->total_paginas) {
					$html .= "<li class='page-item disabled'><a class='page-link' href='javascript:;'>&raquo;</a></li>";
				} else {
					$rota = $o->page."&gPagination=".$this->proxima_pagina;
					$html .= "<li class='page-item'><a class='page-link' id='{$id}-btnProximo'>&raquo;</a></li>";
					$js = "$('#{$id}-btnProximo').on('click', function () {
						$('#{$id}-formRequest').attr('action', '".$rota."');
						$('#{$id}-gPagination').val('".$this->proxima_pagina."');
						$('#{$id}-formRequest').submit();
					})";
					$o->addJavascript($js);
				}
				$html .= "</ul>";
				$html .= "</nav>";
				return ($html);
			}
		}

	}
}
