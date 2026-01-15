<?php
/**
 * Este arquivo contém a estrutura padrão para apresentação de
 *  páginas renderizadas automaticamente
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */
include_once $gPathDefault . "gUI.php";
include_once $gPathDefault . "gInput.php";

class gPage extends gUI
{
	public $bufferPre='';
	public $bufferPos='';
	public $bufferJavascript='';

	function __construct($json="")
	{
		parent::__construct($json);
	}


	function showPage($json="")
	{
		global $html;
		$this->json=$json;
		$this->jarr=cssDecode($json);

		if ($this->jarr['showOk']=='false')
			$this->showOk=false;
		if (stripos($this->jarr['permissions'],'S')!==false)
			$this->select=true;
		if (stripos($this->jarr['permissions'],'I')!==false)
			$this->insert=true;
		if (stripos($this->jarr['permissions'],'U')!==false || stripos($this->jarr['permissions'],'F')!==false || stripos($this->jarr['permissions'],'W')!==false)
			$this->update=true;
		if (stripos($this->jarr['permissions'],'D')!==false)
			$this->delete=true;

		if (count($this->dictionaries)>0)
		{
			foreach ($this->dictionaries as $name=>$jarr)
			{
				//$this->dictionaries[]=$jarr;
				if (isset($this->tableFields[$jarr['name']]))
				{
					foreach ($jarr as $el => $value)
					{
						$this->tableFields[$jarr['name']][$el]=$value;
						$gDB[$this->tableAlias]['fields'][$jarr['name']][$el]=$value;
					}

					foreach ($jarr as $el => $value)
					{
						if ($el=="type")
						{
							$align=gCheckAlignByType($this->tableFields[$jarr['name']]['type']);
							$this->tableFields[$jarr['name']]['align']=$align;
							$gDB[$this->tableAlias]['fields'][$jarr['name']]['align']=$align;
							switch ($value)
							{
								case "combo":
									$arr=$this->getQueryData($jarr['name'],$gDB[$this->tableAlias]['fields'][$jarr['name']]['items']);
									break;
							}
						}
					}
				}
			}
		}
		$o=new gInput();
		$html.='<div id="bodyContent" data-ng-view="" style="margin: 12px">'."\n";
		parent::run($o,$html);
		$html.='</div>'."\n";
		$this->bufferPre=$o->bufferPre;
		$this->bufferPos=$o->bufferPos;
		$this->bufferJavascript=$o->bufferJavascript;
		$o->begin();
		$o->out($html);
		$o->end();
	}
}


?>
