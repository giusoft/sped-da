<?php

class gReport extends g_Report
{

	function doAfterHtml($rpt)
	{
		$this->senchaTitle=$this->pageTitle;
		parent::doAfterHtml($rpt);
	}


}

?>
