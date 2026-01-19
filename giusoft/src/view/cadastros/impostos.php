<?php

define("INICIO", 0);
define("CST_ICMS", 100);
define("CST_PIS", 101);
define("CST_IPI", 102);
define("CST_COFINS", 103);
define("CST_IBS_CBS", 104);
define("CCLASSTRIB_IBS_CBS", 105);

switch ($tipoImposto) {
	case CST_ICMS:
		$o->page .= '&tipoImposto=' . CST_ICMS;
	    include_once $gPathDefault . "gUI.php";
	    $ui = new gUI("{title: Cadastros de impostos (CST ICMS); table: imp_icms_cst; permissions: SIUD;}");
	    $ui->run($o, $html);
		break;

    case CST_PIS:
		$o->page .= '&tipoImposto=' . CST_PIS;
	    include_once $gPathDefault . "gUI.php";
	    $ui = new gUI("{title: Cadastros de impostos (CST PIS); table: imp_pis_cst; permissions: SIUD;}");
	    $ui->run($o, $html);
		break;

    case CST_IPI:
		$o->page .= '&tipoImposto=' . CST_IPI;
	    include_once $gPathDefault . "gUI.php";
	    $ui = new gUI("{title: Cadastros de impostos (CST IPI); table: imp_ipi_cst; permissions: SIUD;}");
	    $ui->run($o, $html);
		break;

    case CST_COFINS:
		$o->page .= '&tipoImposto=' . CST_COFINS;
	    include_once $gPathDefault . "gUI.php";
	    $ui = new gUI("{title: Cadastros de impostos (CST COFINS); table: imp_cofins_cst; permissions: SIUD;}");
	    $ui->run($o, $html);
		break;

    case CST_IBS_CBS:
		$o->page .= '&tipoImposto=' . CST_IBS_CBS;
	    include_once $gPathDefault . "gUI.php";
	    $ui = new gUI("{title: Cadastros de impostos (CST IBS CBS); table: imp_ibs_cbs_cst; permissions: SIUD;}");
	    $ui->run($o, $html);
		break;

    case CCLASSTRIB_IBS_CBS:
		$o->page .= '&tipoImposto=' . CCLASSTRIB_IBS_CBS;
	    include_once $gPathDefault . "gUI.php";
	    $ui = new gUI("{title: Cadastros de impostos (CCLASSTRIB IBS CBS); table: cclasstrib_ibs_cbs; permissions: SIUD;}");
	    $ui->run($o, $html);
		break;

	default:
		$html .= $o->msgTitle("Cadastros de impostos");
		$html .= $o->button("{title: CST ICMS; icon: badge-percent; style: primary; size: big; href: " . $o->page . "&tipoImposto=" . CST_ICMS . ";}");
        $html .= $o->button("{title: CST PIS; icon: receipt; style: primary; size: big; href: " . $o->page . "&tipoImposto=" . CST_PIS . ";}");
        $html .= $o->button("{title: CST IPI; icon: boxes; style: primary; size: big; href: " . $o->page . "&tipoImposto=" . CST_IPI . ";}");
        $html .= $o->button("{title: CST COFINS; icon: coins; style: primary; size: big; href: " . $o->page . "&tipoImposto=" . CST_COFINS . ";}");
        $html .= $o->button("{title: CST IBS CBS; icon: shopping-cart; style: primary; size: big; href: " . $o->page . "&tipoImposto=" . CST_IBS_CBS . ";}");
        $html .= $o->button("{title: CCLASSTRIB IBS CBS; icon: list; style: primary; size: big; href: " . $o->page . "&tipoImposto=" . CCLASSTRIB_IBS_CBS . ";}");
		break;
}