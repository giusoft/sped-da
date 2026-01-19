<?php
/** Este arquivo contém funcionalidades para geração de relatórios
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */
include_once $gPathDefault . "gPage.php";

/**
 * g_Report
 *
 * @uses     gPage
 *
 * @category Relatórios
 * @package  gfw
 * @author   Giuliano Nascimento <giusoft@hotmail.com>
 */
class g_Report extends gPage
{

    private $totals, $groups;
    public $rowCls = "", $showId = true;
    public $report;
    public $reportFields;

    /**
     * Adiciona total
     *
     * @param mixed $json Parâmetros (value, formula e fieldLabel).
     * @access public
     * @return mixed Value.
     */
    function addTotal($json)
    {
        $q = count($this->querys) - 1;
        $mtz = cssDecode($json);
        $name = $mtz['name'];
        $this->totals[$q][$name]['value'] = 0;
        $this->totals[$q][$name]['formula'] = $mtz['formula'];
        $this->totals[$q][$name]['fieldLabel'] = $mtz['fieldLabel'];
    }

    /**
     * Adiciona grupo
     *
     * @param mixed $json Parâmetros (name)
     * @access public
     * @return mixed Value.
     */
    function addGroup($json)
    {
        $q = count($this->querys) - 1;
        $mtz = cssDecode($json);
        $name = $mtz['name'];
        $this->groups[$q][$name] = $mtz['name'];
    }

    function onBeforePrintHeader($fields)
    {
        return($fields);
    }

    function onBeforePrintDetail($fields)
    {
        return($fields);
    }

    function onBeforePrint($fields)
    {
        
    }

    function onBeforePrintTotal($fields)
    {
        return($fields);
    }

    function onBeforePrintReport()
    {
        return;
    }

    function onAfterPrintHeader($fields)
    {
        
    }

    function onAfterPrintDetail($fields)
    {
        
    }

    function onAfterPrintTotal()
    {
        
    }

    function onAfterPrintReport()
    {
        
    }

    function onAfterEndPrint($fields)
    {
        
    }

    function doBeforeHtml()
    {
        
    }

    function doAfterHtml($rpt)
    {
        $this->gBody($rpt);
    }

    /** Processa comandos enviados pela própria página
     * @author	giuliano
     * @version	1.0 16-06-2009 14:29
     * @param string $command Comando a ser processado
     */
    function process()
    {
        global $http_base;
        $command = $_REQUEST['process'];
        $json = $this->json;
        $processou = false;
        $sai = "";
        $fields = gCleanField($_REQUEST);
        if ($command == "") {
            if (is_array($this->filters)) {
                // Se existem filtros, mostra formulário, pra depois que ativar, mostrar o relatório
                $this->parentConstruct($json);
                $mtz = cssDecode($json);
                $title = $mtz['title'];
                if ($title == "") {
                    $title = gT("Relatório");
                }
                $name = gString2Field($title);
                $subTitle = gT('Selecione os filtros para busca');
                $frm = new gForm("{title: $title; subTitle: '$subTitle'; button: 'goFilter()'; frame: false; standardSubmit:true; columns: 2; url: '" . $this->page . "'}");
                $frm->add("{name: g; type: hidden; value: " . $_REQUEST['g'] . "}");
                $frm->add("{name: process; type: hidden; value: showReport}");
                $frm->add("{name: filter; type: hidden; value: 'on'}");
                $flds = $this->filters[0];
                if (gVar("global.site") <> "Alitem") {
                    $flds->add("{name: 'gExportTo'; fieldLabel: 'Tipo de saída'; type: 'combo'; value: 'Web'; items: {'Web','PDF','Excel','OpenOffice','Documento','CSV'}}");
                }

                $this->tabFilter($flds, $frm);
                $frm->render($json);
                $this->senchaTitle = $title;
                $this->senchaButtonsBottom = "{text: '" . gT("Confirmar") . "', iconCls: 'refresh', handler: function(){f=Ext.get('gForm');f.dom.submit()}}";

                $filtros = "";
                foreach ($flds->filter as $flt) {
                    $filtros[] = $flt['name'] . ": Ext.get('" . $flt['name'] . "').getValue()\n";
                }
                $filtros = "," . implode(",", $filtros);
                $goFilter = "
				function goFilter()
				{
					$name.getForm().submit();
				}";
                extjsDo($goFilter);
                $this->gBody();
            } else {
                // Se não existem filtros, mostra direto o relatório
                $command = "showReport";
            }
        }
        if (($command == "showReport") || ($command == "link")) {
            $this->loadQuerys();
            $this->parentConstruct($json);
            $jsn = $mtz = cssDecode($json);

            $this->reportTitle = $mtz['title'];
            $this->reportSubTitle = $mtz['subtitle'];
            $this->reportFilter = $mtz['filter'];

            $this->doBeforeHtml();
            $this->report = '';

            $this->report.=$this->msgTitle($mtz['title']);
            if ($mtz['subtitle'] <> "") {
                $this->report.=$this->msgSubTitle($mtz['subtitle']);
            }
            if (is_array($this->filters)) {
                $flds = "";
                // Tem filtros, portanto mostra o que foi selecionado
                $filter = $this->filters[0];
                $flts = $filter->get();
                foreach ($flts as $fld) {
                    $op = ": ";
                    if (substr($fld['name'], 0, 5) == "from_") {
                        $op = ">=";
                    }
                    if (substr($fld['name'], 0, 3) == "to_") {
                        $op = "<=";
                    }
                    foreach ($_REQUEST as $name => $value) {
                        $tName = explode("__", $name);
                        if (count($tName > 1)) {
                            $tName = $tName[1];
                        } else {
                            $tName = "";
                        }
                        if ((($fld['name'] == $name) || ($fld['name'] == $tName)) && ($value <> "")) {
                            $f = $fld['type'];
                            if ($f == "combo") {
                                if (trim($value) == "") {
                                    unset($_REQUEST[$name]);
                                    $f = 'nullcombo';
                                } else {
                                    $dados = jcombo2array($fld['items'], true, $value);
                                    foreach ($dados as $dadosEl) {
                                        $dadosEl = substr($dadosEl, 2);
                                        $dadosEl = substr($dadosEl, 0, strlen($dadosEl) - 2);
                                        $el = explode("','", $dadosEl);
                                        if (($el[0] == $value) || ($el[1] == $value)) {
                                            $value = $el[1];
                                            break;
                                        }
                                    }
                                }
                            }
                            if ($f == "comboMultiSelection") {
                                if (!is_numeric($value[0])) {
                                    unset($_REQUEST[$name]);
                                    $f = 'nullcombo';
                                } else {
                                    $newValue = "";
                                    foreach ($value as $val) {
                                        $dados = jcombo2array($fld['items'], true, $val);
                                        foreach ($dados as $dadosEl) {
                                            $dadosEl = substr($dadosEl, 2);
                                            $dadosEl = substr($dadosEl, 0, strlen($dadosEl) - 2);
                                            $el = explode("','", $dadosEl);
                                            if (($el[0] == $val) || ($el[1] == $val)) {
                                                $newValue[] = $el[1];
                                                break;
                                            }
                                        }
                                    }
                                    $value = implode(", ", $newValue);
                                }
                            }
                            switch($f) {
                                case 'nullcombo':
                                    break;
                                case 'checkbox':
                                    if ($value == "on") {
                                        $flds[] = $fld['fieldLabel'] . $op . gT("Sim");
                                    } else {
                                        $flds[] = $fld['fieldLabel'] . $op . gT("Não");
                                    }
                                    break;
                                case 'datetime':
                                    $flds[] = $fld['fieldLabel'] . $op . substr(gDateTime($value), 0, 14);
                                    break;
                                default:
                                    $flds[] = $fld['fieldLabel'] . $op . $value;
                            }
                        }
                    }
                }
            }
            $qry = $this->querys[0];
            $tab = $qry->tables[0]['name'];
            $fk = "id_$tab";
            $paginacaoBaixo = "";
            $paginacaoAlto = "";
            $pagAtual = intval($_REQUEST['actualPage']);
            if ($pagAtual == 0) {
                $pagAtual++;
            }

            for ($q = 0; $q < count($this->querys); $q++) {
                $this->actualQuery = $q;
                $query = $this->querys[$this->actualQuery];

                $sql = "SELECT " . $query->sections['select'] . " FROM " . $query->sections['from'];
                $sql.=$this->addFilterOnWhere($query->sections['where']);
                if ($query->sections['group by'] <> "") {
                    $sql.=" GROUP BY " . $query->sections['group by'];
                }
                if ($query->sections['order by'] <> "") {
                    $sql.=" ORDER BY " . $query->sections['order by'];
                }
                $rs = $query->run($sql);
                if (($q == 0) && ($this->pagination)) {
                    $tag = "";
                    $ttlRecords = $rs->RecordCount();
                    $tag1 = "";
                    $ttlPaginas = intval($ttlRecords / $this->maxRows);
                    if ($ttlPaginas <> ($ttlRecords / $this->maxRows)) {
                        $ttlPaginas++;
                    }
                    if ($ttlPaginas > 1) {
                        $http = "http";
                        if ($_SERVER['SERVER_PORT'] == 443)
                            $http = "https";

                        if ($gurl == "")
                            $gurl = $_SERVER['HTTP_HOST'];
                        $pag = $http . "://" . $gurl . $this->page;

                        foreach ($_REQUEST as $key => $value) {
                            if (($key <> "g") && ($key <> "actualPage")) {
                                $pag.="&$key=$value";
                            }
                        }
                        $ini = (($pagAtual - 1) * $this->maxRows);
                        $pipe = "<span style='color: #d0d0d0'>|</span>";
                        $paginacaoBaixo = "Página atual: $pagAtual $pipe Total de páginas: $ttlPaginas $pipe Total registros: $ttlRecords $pipe Tamanho da página: " . $this->maxRows;
                        $m = $ttlPaginas;
                        $t = 10;
                        $tm = intval($t / 2);
                        if ($ttlPaginas > $t) {
                            $m = $t;
                        }
                        $mi = 1;
                        $lnkAntes = "";
                        $lnkDepois = "";
                        if ($m < $ttlPaginas) {
                            if ($pagAtual > 2) {
                                $lnk = $pag . "&actualPage=1";
                                $paginacaoAlto.="<a href='$lnk' style='font-size: 125%; color: #d0d0d0; border: 1px solid #d0d0d0; padding: 2px; margin-top: 2px; margin-right: 4px'>1</a>";
                                $paginacaoAlto.="<span style='font-size: 125%; color: #d0d0d0; border: 1px solid #d0d0d0; padding: 2px; margin-top: 2px; margin-right: 4px'>...</span>";
                                $mi = $pagAtual;
                                $m = $mi + $t;
                                if ($m >= $ttlPaginas) {
                                    $m = $ttlPaginas - 1;
                                    $mi = $ttlPaginas - $t;
                                }
                                if (intval($pagAtual - $tm > 1) && (intval($pagAtual + $tm) < $ttlPaginas)) {
                                    $mi = intval($pagAtual - $tm);
                                    $m = intval($pagAtual + $tm);
                                }
                            }
                        }
                        for ($a = $mi; $a <= $m; $a++) {
                            $lnk = $pag . "&actualPage=$a";
                            if ($a == ($pagAtual - 1))
                                $lnkAntes = $lnk;
                            if ($a == ($pagAtual + 1))
                                $lnkDepois = $lnk;
                            if ($a == $pagAtual)
                                $paginacaoAlto.="<span style='font-size: 125%; border: 1px solid #d0d0d0; padding: 2px; margin-top: 2px; margin-right: 4px'>$a</span>";
                            else
                                $paginacaoAlto.="<a href='$lnk' style='font-size: 125%; color: #d0d0d0; border: 1px solid #d0d0d0; padding: 2px; margin-top: 2px; margin-right: 4px'>$a</a>";
                        }
                        if ($m <> $ttlPaginas) {
                            $lnk = $pag . "&actualPage=$ttlPaginas";
                            if (($m + 1) < $ttlPaginas)
                                $paginacaoAlto.="<span style='font-size: 125%; color: #d0d0d0; border: 1px solid #d0d0d0; padding: 2px; margin-top: 2px; margin-right: 4px'>...</span>";
                            $paginacaoAlto.="<a href='$lnk' style='font-size: 125%; color: #d0d0d0; border: 1px solid #d0d0d0; padding: 2px; margin-top: 2px; margin-right: 4px'>$ttlPaginas</a>";
                        }
                        $tag.=$this->tableBegin("big", false);
                        $tag.="<tr><td style='width: 5%'>";
                        if ($lnkAntes <> "")
                            $tag.=$this->image("{url: 32/a0004; href: $lnkAntes; hint: Página anterior}");
                        $tag.="</td><td style='text-align: center; vertical-align: middle; display: table-cell'>$paginacaoAlto</td>";
                        $tag.="<td style='width: 5%; text-align: right'>";
                        if ($lnkDepois <> "")
                            $tag.=$this->image("{url: 32/a0005; href: $lnkDepois; hint: Página seguinte}");
                        $tag.="</td></tr>";
                        $tag.=$this->tableEnd();
                        $this->report.=$tag;
                        $rs = $query->run($sql, 0, gD_NOTRANS, $ini, $this->maxRows);
                    }
                }

                $query = $this->querys[$this->actualQuery];

                $campos = "";
                $tipos = "";
                $mostrarGrupo = '';
                foreach ($query->fields as $campo) {
                    $mostra = true;
                    foreach ($this->groups[$this->actualQuery] as $grupo) {
                        if ($grupo == $campo['alias']) {
                            $mostrarGrupo = $grupo;
                            $mostra = false;
                        }
                    }

                    if (($mostra) && (($campo['alias'] <> 'id') && ($campo['alias'] <> 'id') && ($campo['alias'] <> 'idd') && ($campo['alias'] <> $fk)) || (($this->showId) && ($campo['alias'] == "id"))) {
                        $key = $campo['alias'];
                        $nkey = $campo['fieldLabel'];
                        $type = $campo['type'];
                        // Caso exista(m) dicionário(s), usa...
                        if (is_array($this->dictionarys)) {
                            foreach ($this->dictionarys as $dictObj) {
                                $dict = $dictObj->get();
                                foreach ($dict as $dkey => $d) {
                                    if ($key == $dkey) {
                                        $nkey = $d['fieldLabel'];
                                        $type = $d['type'];
                                        $isize = $d['size'];
                                        break;
                                    }
                                }
                            }
                        }

                        $pre = "<-";
                        if (($type == "date") || ($type == "datetime")) {
                            $pre = "<>";
                        }
                        if (($type == "integer") || (($type == "number"))) {
                            $pre = "->";
                        }
                        $campos[] = $pre . $nkey;
                        $camposLimpos[] = $campo['alias'];
                        $tipos[$key] = $type;
                    }
                }
                $this->reportFields = $tipos;
                $arr = "";
                $this->rowCls = "header";
                $this->onBeforePrintReport();
                if ($this->querysTitles[$this->actualQuery] <> '') {
                    $this->report.=$this->msgSubTitle($this->querysTitles[$this->actualQuery]);
                }
                if (is_array($flds)) {
                    $this->report.=$this->msgFilter(implode(" / ", $flds));
                }
                $this->report.=$this->tableBegin("big", true);
                if (!$rs->EOF) {
                    if ($mtz['showHeader'] <> 'false') {
                        $campos = $this->onBeforePrintHeader($campos);
                        $this->report.=$this->tableRow($campos, $this->rowCls);
                        $this->report.=$this->onAfterPrintHeader($campos);
                    }
                    $grupoAtual = '';
                    $grupoTotais = '';
                    $sim = gT("Sim");
                    $nao = gT("Não");
                    while(!$rs->EOF) {
                        if (($mostrarGrupo <> '') && ($rs->fields[$mostrarGrupo] <> $grupoAtual)) {
                            $arr[] = array("~" . $rs->fields[$mostrarGrupo]);
                            $grupoTotais = '';
                        }
                        $grupoAtual = $rs->fields[$mostrarGrupo];
                        foreach ($rs->fields as $key => $value) {

                            if (!is_numeric($key)) {
                                $fld = $query->getField($key);
                                $type = $fld['type'];

                                // Caso exista(m) dicionário(s), usa...
                                if (is_array($this->dictionarys)) {
                                    foreach ($this->dictionarys as $dictObj) {
                                        $dict = $dictObj->get();
                                        foreach ($dict as $dkey => $d) {
                                            if ($key == $dkey) {
                                                if ($d['type'] <> "")
                                                    $type = $d['type'];
                                                if ($d['type'] == "combo") {
                                                    $dados = jcombo2array($d['items']);
                                                    foreach ($dados as $dadosEl) {
                                                        $dadosEl = substr($dadosEl, 2);
                                                        $dadosEl = substr($dadosEl, 0, strlen($dadosEl) - 2);
                                                        $el = explode("','", $dadosEl);
                                                        if (($el[0] == $value) || ($el[1] == $value)) {
                                                            $value = $el[1];
                                                            $rs->fields[$key] = $value;
                                                            break;
                                                        }
                                                    }
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                                if ($type == 'datetime') {
                                    $rs->fields[$key] = gDateTime($value);
                                }
                                if ($type == 'date') {
                                    $rs->fields[$key] = gDate($value);
                                }
                                if ($type == 'number') {
                                    $rs->fields[$key] = gFloat($value);
                                }
                                if ($type == 'checkbox') {
                                    if ($value == 1) {
                                        $rs->fields[$key] = $sim;
                                    } else {
                                        $rs->fields[$key] = $nao;
                                    }
                                }
                                if ($type == 'password') {
                                    $rs->fields[$key] = "******";
                                }
                                if ($type == 'cpf') {
                                    if ($value <> "")
                                        $rs->fields[$key] = substr($value, 0, 3) . "." . substr($value, 3, 3) . "." . substr($value, 6, 3) . "-" . substr($value, -2);
                                }
                            } else {
                                // excluir elemento com chave numerica
                                unset($rs->fields[$key]);
                            }
                        }
                        $arr[] = $rs->fields;
                        $rs->MoveNext();
                    }

                    $aux = count($arr);
                    $cont = 0;

                    foreach ($arr as $linhas) {
                        $cont++;
                        $mtz = "";
                        if (is_array($linhas)) {
                            if (substr($linhas[0], 0, 1) == "~") { // grupo
                                $mtz = '';
                                $mtz[] = "~" . (count($campos)) . substr($linhas[0], 1);
                                $this->report.=$this->tableRow($mtz, 'group');
                            } else {
                                $this->rowCls = "detail";
                                if ($jsn['rowStyle'] <> "") {
                                    $this->rowCls = $jsn['rowStyle'];
                                }

                                $this->report.=$this->onBeforePrint($linhas);

                                $linhas = $this->onBeforePrintDetail($linhas);
                                foreach ($linhas as $key => $value) {
                                    $ttl = $this->totals[$q];
                                    if (isset($ttl[$key])) {
                                        $v = gDBFloat($value);
                                        if ($ttl[$key]['formula'] == "sum") {
                                            $this->totals[$q][$key]['value'] = ($this->totals[$q][$key]['value'] + $v);
                                        }
                                        if ($ttl[$key]['formula'] == "avg") {
                                            $this->totals[$q][$key]['value'] = ($this->totals[$q][$key]['value'] + $v);
                                            $avgTotal++;
                                        }
                                        if ($ttl[$key]['formula'] == "count") {
                                            $this->totals[$q][$key]['value'] = $ttl[$key]['value'] + 1;
                                        }
                                        if ($ttl[$key]['formula'] == "max") {
                                            if ($v > $ttl[$key]['value']) {
                                                $this->totals[$q][$key]['value'] = $v;
                                            }
                                        }
                                        if ($ttl[$key]['formula'] == "min") {
                                            if (($v <= $ttl[$key]['value']) || ($ttl[$key]['value'] == 0)) {
                                                $this->totals[$q][$key]['value'] = $v;
                                            }
                                        }
                                    }

                                    if (($mostrarGrupo <> $key) && ((($key <> "idd") && ($key <> "id")) || (($this->showId) && ($key == "id")))) {
                                        // Conversão especial por conta dos campos com aliases
                                        if ((intval(substr($value, 0, 4)) > 1000) && (substr($value, 4, 1) == "-") && (substr($value, 7, 1) == "-")) {
                                            $this->reportFields[$key] = "date";
                                            $value = gDate($value);
                                        }
                                        $pre = "<-";
                                        if (($this->reportFields[$key] == "date") || ($this->reportFields[$key] == "datetime"))
                                            $pre = "<>";
                                        if (($this->reportFields[$key] == "integer") || (($this->reportFields[$key] == "number")))
                                            $pre = "->";
                                        if ($this->reportFields[$key] == "image") {
                                            $size = '';
                                            if (trim($isize) <> '') {
                                                $size = "$isize/";
                                            }
                                            $value = $this->gImage("{url: {$size}$value}");
                                            $pre = "<-";
                                        }

                                        $mtz[] = $pre . autoencode($value);
                                    }
                                }
                                if (is_array($linhas)) {
                                    $this->report.=$this->tableRow($mtz, $this->rowCls);
                                }
                                $this->report.=$this->onAfterPrintDetail($linhas);
                                if ($aux == $cont) {
                                    $this->report.=$this->onAfterEndPrint($linhas);
                                }
                            }
                        }
                    }
                    if (count($arr) > 1) {
                        $linhas = $arr[0];
                        $mtz = "";
                        $fez = false;
                        foreach ($camposLimpos as $key) {
                            if (($key <> $mostrarGrupo) && ((($key <> "idd") && ($key <> "id")) || (($this->showId) && ($key == "id")))) {
                                $value = $this->totals[$q][$key]['value'];

                                if ($this->reportFields[$key] == "number") {
                                    $value = gFloat($value);
                                }
                                if (isset($this->totals[$q][$key])) {
                                    $formula = gT($this->totals[$q][$key]['formula']);
                                    if ($formula == "avg") {
                                        $mtz[] = "-><acronym title='" . gT($formula) . "'>" . gFloat($value / $avgTotal) . "</acronym>";
                                    } else {
                                        $mtz[] = "-><acronym title='" . gT($formula) . "'>" . $value . "</acronym>";
                                    }
                                    $fez = true;
                                } else {
                                    $mtz[] = "";
                                }
                            }
                        }
                        $this->rowCls = "total";
                        $mtz = $this->onBeforePrintTotal($mtz);
                        if ($fez) {
                            $this->report.=$this->tableRow($mtz, $this->rowCls);
                        }
                    }
                    $this->report.=$this->onAfterPrintTotal();
                    $this->report.=$this->tableEnd();
                    $this->report.=$this->onAfterPrintReport();
                    $this->report.=$table;
                } else {
                    $this->report.=$this->msgAlert(gT("Nenhuma informação encontrada"));
                }
                $this->report.=$paginacaoBaixo;
            }
            $this->doAfterHtml($this->report);
        }

        $this->processed = $processou;
        return ($processou);
    }

    function showReport($json)
    {
        $this->json = $json;
        $mtz = cssDecode($json);
        if ($mtz['pagination'] == "true") {
            $this->pagination = true;
        }
        if (intval($mtz['maxRows']) > 0) {
            $this->maxRows = intval($mtz['maxRows']);
        }
        if (($mtz['showId'] == "false") || ($mtz['showId'] == "off")) {
            $this->showId = false;
        }

        if (!$this->process()) {
            
        }
    }

    function showPage($json)
    {
        $this->showReport($json);
    }
}

$device = $gDevice;
if ((($gOs == "ios") || ($gOs == "android")) && ($gDevice == "mobile")) {
    $device = "iphone";
}
$inc = $gPathDefault . "dev" . gBAR . strtolower($device) . gBAR . "gReport.php";


if (file_exists($inc)) {
    include_once $inc;
} else {
    $out = new g_Output();
    $out->gError("Erro", "dispositivo de acesso ao sistema não encontrado: <br>inc: $inc<br>$device");
}
?>