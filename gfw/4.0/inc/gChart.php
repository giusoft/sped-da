<?php

include_once $gPathDefault . "gInput.php";

class gHTMLChart extends gOutput{

    private $color = '';
    private $parseTime = true;
    public $formatAsDate = false;

        /**
     * Tipo de grafico
     *
     * @var string
     */
    private $type = '';

    /**
     * Nomes para os valores de Y
     *
     * @var array
     */
    private $y_label = array();
    private $x_labels = "";

    /**
     * Parametros da classe Morris
     *
     * @var array
     */
    private $param = array();

    /**
     * Relaciona os valores de X em Y
     *
     * @var array
     */
    private $xy_list = array();

    /*
     * Largura e Altura do elemento grafico
     *
     * @var string
     */
    private $dimensions = '';

    /**
     * Construtor da função
     * @param string
     * @param string
     * @param string
     */
    function __construct($type = '', $width = '', $height = '') {
        $this->setType($type);
        $this->setDimensions($width,$height);
    }

    /**
     * Informa o conjunto de cores a ser utilizado
     * @param string $color Pode ser: color, vivid, red, green, blue
     */
    public function setColor($color = ''){

        $this->color = $color;

        return true;
    }

    /**
     * Informa o tipo de grafico a ser criado
     * @param string Tipos podem ser: Bar, Line, Area, Donut
     * @return boolean true ou false
     */
    public function setType($type = ''){

        $this->type = ($type)? $type : 'Bar';

        return true;
    }

    /**
     * Seta parametro para classe Morris
     * @param string Indice
     * @param string Valor
     * @return boolean true ou false
     */
    public function setParam(){

        if(func_num_args() < 2) return false;

        $this->param[func_get_arg(0)] = func_get_arg(1);

        return true;
    }

    /**
     * Seta parametro para classe Morris
     * @param string Indice
     * @param string Valor
     * @return boolean true ou false
     */
    public function setParseTime($parse){

        $this->parseTime = $parse;
        return true;
    }

    /**
     * Insere os Rotulos para cada valor em Y
     * @return boolean true ou false
     */
    public function setDimensions($w, $h){
        if(empty($w) && empty($h)) return false;

        $w = (substr($w,-1) == '%')? $w : $w.'px';
        $h = (substr($h,-1) == '%')? $h : $h.'px';

        $this->dimensions = ' style="width:'.$w.';height:'.$h.';"';

        return true;
    }

    /**
     * Seta nomes para os valores em Y
     * @return boolean true ou false
     */
    public function setYlabel(){

        if(!func_num_args()) return false;

        $this->y_label = func_get_args();

        return true;
    }

    /**
     * Seta nomes para os valores em Y
     * @return boolean true ou false
     */
    public function setXlabels($txt){
		$this->x_labels = $txt;
        return true;
    }

    /**
     * Seta valor em X e associa os valores XY
     * @param string Label/valor X
     * @param string Demais argumentos sera setado em Y e associado a X
     * @return boolean true ou false
     */
    public function addAssocXY(){
    		$argl[]='a';
    		$argl[]='b';
			$argl[]='c';
			$argl[]='d';
			$argl[]='e';
			$argl[]='f';
			$argl[]='g';
        if(func_num_args() < 2) return false;

        $key = func_get_arg(0);

        if($this->type == 'Donut'){
            $value = func_get_arg(1);
        }else{
            $value = array();
            foreach(func_get_args() as $k => $v){
            	if($k == 0) continue;
            	$value[$argl[$k]] = $v;
            }
        }

        $this->xy_list[$key] = $value;

        return true;
    }


    /**
     * Função para renderizar o grafico
     * @param object Recebera os dados para exibir o grafico
     * @return boolean true ou false
     */
    function render(&$o,$print = true){

        global $http_lib;

        if(empty($this->y_label) && empty($this->xy_list)) return false;

        $y_label = $this->y_label;
        $x_labels = $this->x_labels;
        $xy_list = $this->xy_list;
        $type = ($this->type)? $this->type : 'Bar';
        $param = $this->param;
        $dimensions = $this->dimensions;

        // id elemento chart
        $tag_id = 'gChart-'.uniqid();

        $type = (!empty($type))? $type : 'Bar';

        $script = "
                    Morris.".$type."({
                        element: '".$tag_id."'";

        // Adiciona parametros personalizados
        if(count($param)){
            foreach($param as $k => $v){
                $script .= ','.$k.': '.(strpos($v, 'new Array') !== false? $v : '"'.$v.'"');
            }
        }

        // Dados para inserir no grafico
        if (gVar('global.language')=='pt_BR')
        {
            $script .="
                        ,yLabelFormat: function (x,data) { return x.toLocaleString().replace('.',',');}
                        ,formatter: function (x,data) { return x.toLocaleString().replace('.',',');}
                        ";
            if ($this->formatAsDate)
            {
                $script.="
                            ,dateFormat: function (x) { return new Date(x).toLocaleDateString(); }
                            ,xLabelFormat: function (x) { return new Date(x).toLocaleDateString(); }
                            ";
            }
    //         $script.="
    //                     ,xLabelFormat: function(x){
    //                         d = new Date(x);
    // x = ('0' + (d.getDay() + 1)).slice(-2) + '-';
    // x = x+('0' + (d.getMonth() + 1)).slice(-2) + '-';
    // x = x+('0' + (d.getYear())).slice(-2);
    // return x;}";
        }
		if ($x_labels<>'')
		{
			$script.=",xLabels: '".$x_labels."'";
		}
        if ($this->color<>'')
        {
            $script .= "
                            ,colors: ['".implode("','",getGraphColors($this->color))."']
                            ,lineColors: ['".implode("','",getGraphColors($this->color))."']
                            ,barColors: ['".implode("','",getGraphColors($this->color))."']";
        }
        if (!$this->parseTime)
        {
            $script .= "
                            ,parseTime: false";

        }
        $script .= "
                        ,data: [";
        if($type != 'Donut'){
            if(count($xy_list)){
                $data =  array();
                foreach($xy_list as $label => $y){
                    $tmp = '{x: "'.$label.'"';
                    if(is_array($y) && count($y)){
                        foreach($y as $k => $v){
                            $tmp .= ', '.$k.': "'.$v.'"';
                            $ks[$k]=$k;
                        }
                    }
                    $tmp .= '}';
                    $data[] = $tmp;
                }
                $script .= implode(',',$data);
            }
            $klabel = array_keys($y_label);
            $script .= "    ]
                            ,xkey: 'x'";
            $script .= (count($klabel))? "
                            ,ykeys: ['".implode("','",array_keys($ks))."']" : "";
            $script .= (count($y_label))? "
                            ,labels: ['".implode("','",$y_label)."']" : "";

        }else{
            if(count($xy_list)){
                $data =  array();
                foreach($xy_list as $label => $y){
                    $data[] = '{label: "'.$label.'", value: "'.$y.'"}';
                }
                $script .= implode(',',$data);
            }
            $script .= "]";
        }
        $script .= "

                    });";

        $o->addJavascript($script,gLOC_POS);

        $lib = '    <link rel="stylesheet" href="'.$http_lib.'jquery-morris.js-0.5.1/morris.css">
                    <script src="'.$http_lib.'jquery-morris.js-0.5.1/raphael-min.js"></script>
                    <script src="'.$http_lib.'jquery-morris.js-0.5.1/morris.min.js"></script>';
        $o->out($lib,gLOC_POS);

        $this->y_label = array();
        $this->xy_list = array();

        $container = '<div id="'.$tag_id.'" class="show-gchart"'.$dimensions.'></div>';

        return ($print)? $o->out($container) : $container;

    }

    /**
     * Limpa dados inseridos
     * @return boolean true ou false
     */
    private function reset(){

        $this->type = '';
        $this->y_label = array();
        $this->xy_list = array();
        $this->param = array();
        $this->dimensions = '';

        return true;
    }
}


                // $chart = new gChart("Bar", "100%", "350");
                // $chart->setParam('xLabelAngle',60);
                // $chart->setParam('gridTextSize',10);
                // $chart->setColor('color');
                // $chart->setYlabel('Capacidade','Descarregado');

                // foreach ($rs as $row)
                // {
                //     $cap = $row['capacidade_hold'.$row['hold']];
                //     $chart->addAssocXY('H'.$row['hold'].'/MT', $cap,$row['ttl']);

                // }
                // $html.=$chart->render($o,false);












class gPDFChart {
    private $color = '';

    /**
     * Tipo de grafico
     *
     * @var string
     */
    private $type = '';

    /**
     * Nomes para os valores de Y
     *
     * @var array
     */
    private $y_label = array();
    private $x_labels = "";

    /**
     * Parametros da classe Morris
     *
     * @var array
     */
    private $param = array();

    /**
     * Relaciona os valores de X em Y
     *
     * @var array
     */
    private $xy_list = array();

    /*
     * Largura e Altura do elemento grafico
     *
     * @var string
     */
    private $dimensions = '';

    /**
     * Construtor da função
     * @param string
     * @param string
     * @param string
     */
    function __construct($type = 'Bar', $width = '100%', $height = '350') {
        $this->setType($type);
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Informa o conjunto de cores a ser utilizado
     * @param string $color Pode ser: color, vivid, red, green, blue
     */
    public function setColor($color = ''){

        $this->color = $color;

        return true;
    }

    /**
     * Informa o tipo de grafico a ser criado
     * @param string Tipos podem ser: Bar, Line, Area, Donut
     * @return boolean true ou false
     */
    public function setType($type = ''){

        $this->type = ($type)? $type : 'Bar';

        return true;
    }

    /**
     * Seta parametro para classe Morris
     * @param string Indice
     * @param string Valor
     * @return boolean true ou false
     */
    public function setParam(){

        if(func_num_args() < 2) return false;

        $this->param[func_get_arg(0)] = func_get_arg(1);

        return true;
    }

    /**
     * Insere os Rotulos para cada valor em Y
     * @return boolean true ou false
     */
    public function setDimensions($w, $h){
        if(empty($w) && empty($h)) return false;

        $w = (substr($w,-1) == '%')? $w : $w.'px';
        $h = (substr($h,-1) == '%')? $h : $h.'px';

        $this->dimensions = ' style="width:'.$w.';height:'.$h.';"';

        return true;
    }

    /**
     * Seta nomes para os valores em Y
     * @return boolean true ou false
     */
    public function setYlabel(){

        if(!func_num_args()) return false;

        $this->y_label = func_get_args();

        return true;
    }

    /**
     * Seta nomes para os valores em Y
     * @return boolean true ou false
     */
    public function setXlabels($txt){

        $this->x_labels = $txt;

        return true;
    }

    /**
     * Seta valor em X e associa os valores XY
     * @param string Label/valor X
     * @param string Demais argumentos sera setado em Y e associado a X
     * @return boolean true ou false
     */
    public function addAssocXY(){
            $argl[]='a';
            $argl[]='b';
            $argl[]='c';
        if(func_num_args() < 2) return false;

        $key = func_get_arg(0);

        if($this->type == 'Donut'){
            $value = func_get_arg(1);
        }else{
            $value = array();
            foreach(func_get_args() as $k => $v){
                if($k == 0) continue;
                $value[$argl[$k]] = $v;
            }
        }

        $this->xy_list[$key] = $value;

        return true;
    }


    /**
     * Função para renderizar o grafico
     * @param object Recebera os dados para exibir o grafico
     * @return boolean true ou false
     */
    function render(&$o,$print = true)
    {
        $o->SetGraphColors($this->color);
        $valX = $o->GetX();
        $valY = $o->GetY();
        $w = $this->width;
        if ($w=='100%')
            $w = $o->getPageWidth()-2;

        switch ($this->type)
        {
            case 'Bar':
                $valX = $o->GetX();
                $valY = $o->GetY();
                if ($valY>$o->GetPageHeight()-68)
                {
                    $o->AddPage();
                    $valX = $o->GetX();
                    $valY = $o->GetY();
                }
                $data = '';
                foreach ($this->xy_list as $key=>$item)
                {
                    $cols = '';
                    $cols[]=$key;
                    foreach ($item as $col)
                        $cols[]=$col;
                    $data[] = $cols;
                }

                $o->SetX(16);
                $h = intval(50*intval($this->height)/350);
                $o->ColumnChart($w, $h, $data, null, array(100,100,100),0,4,$this->y_label);
                $o->SetXY($valX, $valY + $h+8);
                $o->out('');
                break;

            case 'Donut':
                $valX = $o->GetX();
                $valY = $o->GetY();
                if ($valY>$o->GetPageHeight()-68)
                {
                    $o->AddPage();
                    $valX = $o->GetX();
                    $valY = $o->GetY();
                }
                $data = $this->xy_list;
                $o->SetX(16);
                $h = intval(50*intval($this->height)/350);
                $o->PieChart($w, $h, $data, '%l (%p)', array(100,100,100));
                $o->SetXY($valX, $valY + $h+20);
                $o->out('');
                break;

        }
    }

    function addJavascript(){}
}





if (intval($_REQUEST['gPDF'])>0)
{
    class gChart extends gPDFChart {}
} else
{
    class gChart extends gHTMLChart {}
}

/* SAMPLES */

/*
$chart = new gChart("Line", "300", "200");

$chart->setParam('xLabels','month');
$chart->setYlabel('Giusoft','Toca da Traíra','Caranga','Porto Brasil');
$chart->addAssocXY('2014-05-12',1,2,3,4,5);
$chart->addAssocXY('2014-04-12',10,20,30,40,50);
$chart->addAssocXY('2014-03-12',25,20,10,30,40);
$chart->addAssocXY('2014-02-12',5,10,15,20,25);
$chart->render($o);

$chart->setType('Bar');
$chart->setYlabel('Giusoft','Toca da Traíra','Caranga','Porto Brasil','Teste');
$chart->addAssocXY('Janeiro',1,2,3,4,5,30);
$chart->addAssocXY('Fevereiro',10,20,30,40,50);
$chart->addAssocXY('Março',25,20,10,30,40);
$chart->addAssocXY('Abril',5,10,15,20,25);
$chart->render($o);

$chart->setType('Area');
$chart->setYlabel('Giusoft','Toca da Traíra','Caranga','Porto Brasil');
$chart->addAssocXY('2011',1,2,3,4,5);
$chart->addAssocXY('2012',10,20,30,40,50);
$chart->addAssocXY('2013',25,20,10,30,40);
$chart->addAssocXY('2014',5,10,15,20,25);
$chart->render($o);

$chart->setType('Donut');
$chart->setDimensions('50%','30%');
$chart->addAssocXY('Janeiro',10);
$chart->addAssocXY('Fevereiro',20);
$chart->addAssocXY('Março',30);
$chart->addAssocXY('Abril',40);
$chart->render($o);

 */
