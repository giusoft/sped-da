<?
/** Este arquivo contém a estrutura padrão para entrada de dados
 *  para aparelhos iPhone
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */



/** Class para gerar mensagens na tela
 * @package	gMsg
 * @author	giuliano
 * @version	1.0 29-10-2009 16:13
 */
class gMsg
{
	static function alert($title,$text="")
	{
		$out=new gOutput("{header: false}");
		echo $out->gMsgTitle($title);
		if ($text<>"")
			echo $out->gMsg($text);
	}
}



/** Class que cria um componente Tab
 * @package	gTab
 * @author	giuliano
 * @version	1.0 29-10-2009 16:13
 */
class gTab
{
	private $items;
	private $name;
	private $param;

	function __construct($json="")
	{
		$mtz=cssDecode($json);
		$this->name=$mtz['name'];$mtz=cssRemove($mtz,"name");
		$this->param=jsEncode($mtz);
		if ($this->name=="") $this->name='gfwTab';
	}

	function add($name,$json)
	{
		$this->items[]=array($name,$json);
	}

	function get()
	{
		$sai="";

		$cnt=0;
		//$sai.=jsMerge("{region:'right',deferredRender: false, resizeTabs: true, enableTabScroll: true, autoScroll: true, margins:'0 4 4 0', activeTab:0, minTabWidth: 120, tabWidth:120, enableTabScroll:true, width:'100%', height:'100%', tabPosition: 'top', defaults: {autoScroll:true}}",$this->param);
		$sai.=jsMerge("{region:'right',deferredRender: false, resizeTabs: true, enableTabScroll: true, autoScroll: true, activeTab:0, minTabWidth: 120, tabWidth:120, enableTabScroll:true, tabPosition: 'top', defaults: {autoScroll:true}}",$this->param);
		$sai=substr($sai,0,strlen($sai)-1).", items: [";
		//$sai.=implode(",",$this->items);
		$cnt=0;
		$items="";
		foreach ($this->items as $item)
		{
			$cnt++;
			$items[]="{id: 'tab$cnt', title: '".gT($item[0])."', items: [".$item[1]."]}";
		}
		$sai.=implode(",",$items);
		$sai.="]}\n";
		return($sai);
	}

	function render()
	{
		$param=$this->get();
		extjsVar($this->name,"Ext.TabPanel",$param);
	}

}

/** Class que cria um componente Panel
 * @package	gPanel
 * @author	giuliano
 * @version	1.0 29-10-2009 16:13
 */
class gPanel
{
	private $items;
	private $name;
	private $param;

	function __construct($json)
	{
		$mtz=cssDecode($json);
		$this->name=$mtz['name'];$mtz=cssRemove($mtz,"name");
		$this->param=jsEncode($mtz);
		if ($this->name=="") $this->name='gfwPanel';
	}

	function add($json)
	{
		$this->items[]=$json;
	}

	function get()
	{
		$sai="";

		$cnt=0;
		//$sai.=jsMerge("{xtype: 'panel', layout: 'fit', style: 'margin-top:15px',bodyStyle: 'padding:10px',autoScroll: true}",$this->param);
		$sai.=jsMerge("{xtype: 'panel', layout: 'fit', autoScroll: true}",$this->param);

		$sai=substr($sai,0,strlen($sai)-1).", tbar: [";
		$sai.=implode(",",$this->items);
		$sai.="]}\n";
		return($sai);
	}

	function render()
	{
		$param=$this->get();
		extjsVar($this->name,"Ext.Panel",$param);
	}
}

/** Class que cria um componente Table Layout
 * @package	gPanel
 * @author	giuliano
 * @version	1.0 29-10-2009 16:13
 */
class gTableLayout
{
	private $items;
	private $name;
	private $param;
	private $columns;

	function __construct($json)
	{
		$mtz=cssDecode($json);
		$this->name=$mtz['name'];$mtz=cssRemove($mtz,"name");
		$this->columns=$mtz['columns'];$mtz=cssRemove($mtz,"columns");
		if (intval($this->columns)==0) $this->columns=2;
		$this->param=jsEncode($mtz);
		if ($this->name=="") $this->name='gfwTableLayout';
	}

	function add($json)
	{
		$this->items[]=$json;
	}

	function get()
	{
		$sai="";
		$columns=$this->columns;
		$cnt=0;
		//$sai.=jsMerge("{xtype: 'panel', layout: 'fit', style: 'margin-top:15px',bodyStyle: 'padding:10px',autoScroll: true}",$this->param);
		$sai.=jsMerge("{xtype: 'panel', layout: 'table', baseCls:'x-plain',autoScroll: true}",$this->param);
		$sai=substr($sai,0,strlen($sai)-1).",layoutConfig: {columns: $columns}, defaults: {frame:false, width:240, height: 200, autoScroll: true}, items: [";
		//$sai=substr($sai,0,strlen($sai)-2).", items: [";
		$sai.=implode(",",$this->items);
		$sai.="]}\n";

		return($sai);
	}

	function render()
	{
		$param=$this->get();
		extjsVar($this->name,"Ext.Panel",$param);
	}
}

/**
 *  Class que cria um componente para grupo de botões
 * @package	gButtonGroup
 * @author	giuliano
 * @version	1.0 29-10-2009 16:13
 */
class gButtonGroup
{
	private $items;
	private $name;
	private $param;
	private $columns;
	function __construct($json)
	{
		$this->columns=0;
		$mtz=cssDecode($json);
		//$mtz=cssMerge($mtz,"{height: '100%'}");
		$this->name=$mtz['name'];$mtz=cssRemove($mtz,"name");
		$this->param=jsEncode($mtz);
		if ($this->name=="") $this->name='gfwGroup';
	}

	function add($json)
	{
		$this->items[]=$json;
		if (strpos($json,"rowspan")==false)
		{
			$this->columns++;
		} else
		{
			$this->columns+=3;
		}
	}

	function get()
	{
		$sai="";

		$cnt=0;
		$col=intval($this->columns/3);
		$sai.=jsMerge("{xtype: 'buttongroup', autoHeight: false, height: 88, columns: $col}",$this->param);

		$sai=substr($sai,0,strlen($sai)-1).", items: [";
		$sai.=implode(",",$this->items);
		$sai.="]}\n";
		return($sai);
	}

	function render()
	{
		$param=$this->get();
		extjsVar($this->name,"Ext.ButtonGroup",$param);
	}
}

class gMenuItem
{
	static function getText($json)
	{
		$mtz=cssDecode($json);
		$url=$mtz['url'];
		if (strpos($url,"?")!==false)
			$url=substr($url,0,strpos($url,"?"));
		if (file_exists("res/".$url))
			$url="res/".$mtz['url'];
		else
			$url="online/".$mtz['url'];

		$sai="{xtype: 'tbbutton',id: '".$mtz['id']."', text: '".$mtz['text']."', tooltip: '".$mtz['tooltip']."', handler: function(f) {Ext.get('gfwScreen').dom.src='$url';}}";
		
		return($sai);
	}
	static function getIconText($json)
	{
		$mtz=cssDecode($json);
		$url=$mtz['url'];
		if (strpos($url,"?")!==false)
			$url=substr($url,0,strpos($url,"?"));
		if (file_exists("res/".$url))
			$url="res/".$mtz['url'];
		else
			$url="online/".$mtz['url'];
		$sai="{xtype: 'tbbutton', id: '".$mtz['id']."', cls: 'x-btn-as-arrow', scale: 'large', rowspan: 3, iconAlign: 'top', icon: 'gfw/img/icons/32x32/".$mtz['icon']."', tooltip: '".$mtz['tooltip']."', cls: 'x-btn-text-icon', text: '".$mtz['text']."', handler: function(f) {Ext.get('gfwScreen').dom.src='$url';}}";
		return($sai);
	}
}

/**
 *  Class que cria um formulário
 * @package	gInput
 * @author	giuliano
 * @version	1.0 29-10-2009 16:13
 */
class gForm extends g_Form
{
	/** Adiciona um campo de formulario
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $json JSON com parâmetros
	 * @param string $par Parâmetros adicionais (matriz para Combo, query, etc.)
	 */
	function add($json,$par="",$listener="")
	{
		$this->originalFields[]=$json;
		$mtz=cssDecode($json);
		//echo "$json<br><pre align='left'>";var_dump( $mtz);echo "</pre>";
		$name=$mtz['name'];
		$fieldLabel=$mtz['fieldLabel'];
		if ($name=="")
			$name=gString2Field($fieldLabel);
		if ($fieldLabel=="")
			$fieldLabel=gField2String($name);

		$mtz['name']=$name;
		$mtz['fieldLabel']=$fieldLabel;
		$type=$mtz['type'];
		if ($type=="comboMultiSelection")
			$type="combo";
		$ememo=false;
		if ($type=="textarea")
		{
			$ememo=true;
			$this->hasTextarea=true;
		}
		if ($type=="memo")
		{
			$ememo=true;
			$this->hasTextarea=true;
		}
		$mtz['id']=$mtz['name'];
		if ($type<>'exclude')
		{
			$mtz=cssRemove($mtz,'type');

			$sai=jsEncode($mtz);
			$sai=jsMerge($this->types[$type],$sai);

			if ( ($type=="positive") || ($type=="positiveInteger") || ($type=="number") || ($type=="integer") )
			{
				$virg=strpos(gVar("global.numformat"),",");
				$pont=strpos(gVar("global.numformat"),".");
				if ($virg>$pont)
				{
					$pos=$virg;
					$decimalSeparator=",";
				} else
				{
					$pos=$pont;
					$decimalSeparator=".";
				}
				$decimalPrecision=strlen(substr(gVar("global.numformat"),$pos))-1;
				$sai=jsMerge($sai,"{decimalSeparator: '$decimalSeparator',  decimalPrecision: $decimalPrecision}");
			}
			if ($type=="combo")
			{
				//echo $json;
			}
			if ($listener<>"")
				$sai=substr(trim($sai),0,strlen(trim($sai))-1).",$listener}";
			if ($ememo)
				$this->fields[]=str_replace("'",'"',str_replace("\n",'\n',$sai));
			else
				$this->fields[]=$sai;

		}
		return;
	}

	function getFields()
	{
		return($this->fields);
	}

	/** Monta um formulário
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $name Nome do objeto
	 * @param string $param JSON ou matriz com parâmetros
	 * @param string $items JSON ou matriz com items
	 * @return mixed $sai
	 */
	function get()
	{
		global $gDevice;
		$json=$this->json;

		$mtz=cssDecode($json);

		$url=$mtz['url'];
		$cols=$mtz['columns'];
		$mtz=cssRemove($mtz,"columns");

		$name=$mtz['name'];
		$mtz['title']=gT($mtz['title']);
		$title=$mtz['title'];

		if ($url=="")
			$mtz['url']=$_SERVER["PHP_SELF"];
		if ($title=="")
			$title="NoNameForm";
		if ($name=="")
		{
			$name=gString2Field($title); // chapa o texto
			$mtz['name']=$name;
		}
		$this->name=$name;
		// 1 coluna
		$defaults="{bodyStyle:'padding:5px 5px 5px 5px', style: 'text-align: left', labelWidth:120, frame:true, defaultType:'textfield', collapsible: false, submitEmptyText: true, standardSubmit: true, modal:true, width: '100%', height: '100%'}";
		//$defaultsBtns="{text: '".gT("save")."', handler: function() {".$name.".getForm().getEl().dom.action = '$url';".$name.".getForm().getEl().dom.method = 'POST';".$name.".getForm().submit();}}";
		if ($mtz['button']<>"")
		{
			$defaultsBtns="{text: '".gT("Aplicar")."', handler: function(){".$mtz['button']."}}";
		} else
		{
			$defaultsBtns="{text: '".gT("Voltar")."', handler: function(){history.go(-1)}},";
			$defaultsBtns.="{text: '".gT("Salvar")."', handler: function() {".$name.".getForm().getEl().dom.action = '$url';".$name.".getForm().getEl().dom.method = 'POST';".$name.".getForm().submit();}}";
		}
		if (($cols>1) && ($gDevice=="web"))
		{
			// 2 ou mais colunas
			//$defaults="{bodyStyle:'padding:5px 5px 0', labelWidth: 130, frame:true, width: '100%', renderTo: 'document.body', layout:'column'}";
			$defaults="{bodyStyle:'padding:5px 5px 0', style: 'text-align: left', labelWidth: 130, collapsible: false, submitEmptyText: true, frame:true, layout:'column'}";
			$param=jsMerge($defaults,jsEncode($mtz));
			$colsper=1/$cols;
			$param=substr($param,0,strlen($param)-1).", defaults: {layout: 'form', border: false, bodyStyle: 'padding:4px'},";
			$param.="items: [";
			$col="{xtype:'fieldset', columnWidth: $colsper, collapsible: false, autoHeight:true, defaultType: 'textfield'}";
			$rows="";
			$fator=intval(count($this->fields)/$cols+0.5);
			for ($c=0; $c<$cols; $c++)
			{
				$newItens="";
				for ($b=$c*$fator; $b<($c+1)*$fator; $b++)
					$newItens[]=$this->fields[$b];
				$rows[]=extjsPasteItems($col,$newItens);
			}
			$param.=implode(",",$rows)."]}";
			$param=str_replace(",]","]",$param);
			$param=extjsPasteButtons($param,$defaultsBtns);
		} else
		{
			// 1 coluna
			$param=jsMerge($defaults,jsEncode($mtz));
			$param=extjsPasteItems($param,$this->fields);
			$param=extjsPasteButtons($param,$defaultsBtns);
		}
		// Limpa apos o uso
		$this->fields="";
		$this->buttons="";
		return($param);
	}

	function render()
	{
		global $htmlBuffer;
		$name=$this->name;
		$json=$this->json;
		$mtz=cssDecode($json);
		
		$url=$mtz['url'];
		$mtz['title']=gT($mtz['title']);
		gMsg::alert($mtz['title']);
		$htmlBuffer.="<form style='padding: 0px; margin: 0px;' action='$url' name='".$mtz['name']."' method='POST'>\n";
		$htmlBuffer.="<table style='width: 100%; padding: 0px 4px 4px 4px;'>\n";
		$pri="";
		foreach ($this->fields as $fld)
		{
			$m=jsDecode($fld);
			if (($pri=="") && ($m['xtype']<>'hidden'))
			{
				$pri=$m['id'];
			}
			$w="";
			if ($m['xtype']=="combo")
				$w=" width='40%'";
			if (($m['xtype']<>'label') && ($m['xtype']<>'hidden'))
				$htmlBuffer.="    <tr><td align='left' $w>".$m['fieldLabel']."</td><td align='left'>";
			switch ($m['xtype'])
			{
				case 'displayfield':
					$htmlBuffer.="<span style=\"font-weight: bold; font-size: 11px\">".$m['value']."</span>";
					break;
				case 'textfield':
					$extra="";
					if ($m['maxLength']<>"") $extra[]=" maxlength='".$m['maxLength']."' size='".($m['maxLength']+3)."'";
					if (is_array($extra))
						$extra=implode(" ",$extra);
					if ($m['inputType']=='password')
						$htmlBuffer.="<input type='password' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' $extra>";
					else
						$htmlBuffer.="<input type='text' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' $extra>";
					break;
				case 'textarea':
					$extra="";
					if (is_array($extra))
						$extra=implode(" ",$extra);
					$htmlBuffer.="<textarea rows='3' cols='22' id='".$m['id']."' name='".$m['name']."' $extra>".$m['value']."</textarea>";
					break;
				case 'numberfield':
					$extra="";
					if ($m['maxLength']<>"") $extra[]=" maxlength='".$m['maxLength']."' size='".($m['maxLength']+3)."'";
					if (is_array($extra))
						$extra=implode(" ",$extra);
					$htmlBuffer.="<input type='text' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' $extra>";
					break;
				case 'datefield':
					$extra="";
					if (is_array($extra))
						$extra=implode(" ",$extra);
					$htmlBuffer.="<input type='text' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' maxlength='8' size='10' $extra>";
					break;
				case 'timefield':
					$extra="";
					if (is_array($extra))
						$extra=implode(" ",$extra);
					$htmlBuffer.="<input type='text' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' maxlength='8' size='10' $extra>";
					break;
				case 'datetime':
					$extra="";
					if (is_array($extra))
						$extra=implode(" ",$extra);
					$htmlBuffer.="<input type='text' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."' maxlength='14'  size='16' $extra>";
					break;
				case 'rcheckbox':
					$extra="";
				if (($m['value']=="1") || ($m['value']=="on") || ($m['value']=="true")) $extra[]="checked";
					if (is_array($extra))
						$extra=implode(" ",$extra);
					$htmlBuffer.="<input type='checkbox' id='".$m['id']."' name='".$m['name']."' $extra>";
					break;
				case 'label':
					$txt=$m['text'];
					if ($m['html']<>"")
						$txt=$m['html'];
					$htmlBuffer.="<tr><td colspan='2' align='center'>".$txt."</td>";
					break;
				case 'hidden':
					$htmlBuffer.="<input type='hidden' id='".$m['id']."' name='".$m['name']."' value='".$m['value']."'>";
					break;
				case 'combo':
					$extra="";
					if (is_array($extra))
						$extra=implode(" ",$extra);
					$items=jcombo2array($m['items']);
					$htmlBuffer.="<select id='".$m['id']."' name='".$m['name']."' $extra>";
					foreach ($items as $item)
					{
						$item=substr($item,1,strlen($item)-2);
						$item=str_replace("'","",$item);
						
						$el=explode(",",$item);
						$sel="";
						if ((trim($m['value'])==$el[0]) )
							$sel=" selected";
						$htmlBuffer.="<option $sel value='".$el[0]."'>".autoencode($el[1])."</option>";
					}
					$htmlBuffer.="</select>";
					break;

			}
			$htmlBuffer.="</td></tr>\n";
		}
		if ($pri=="")
			$pri="submitButton";
		$htmlBuffer.="  </table>\n";
		$htmlBuffer.="<tr><td align='center' colspan='2'><input id='submitButton' type='submit' value='".gT("Confirmar")."' onclick='this.disabled=true,this.form.submit();'></td></tr>";
		$htmlBuffer.="</form><br><br>\n";
		$htmlBuffer.="<script language='javascript'>\n";
		$htmlBuffer.="function focusIt(){\n	var el=document.getElementById('$pri');\n	el.focus();\n}\n";
		//$htmlBuffer.="onload = focusIt();";
		$htmlBuffer.="
 var  oField;
  if (document.forms.length > 0) {
    for (var i=0; i < document.forms[0].elements.length; ++i) {
      oField = document.forms[0].elements[i];
      if ((oField.type != \"hidden\") && (oField.type != \"select-one\")) {
        if (oField.disabled!=true) {
          oField.focus();
          break;
        }
      }
    }
  }			";
		$htmlBuffer.="focusIt()\n</script>\n";
	}

}




/**
 *  Class que cria um componente para grupo de botões
 * @package	gButtonGroup
 * @author	giuliano
 * @version	1.0 29-10-2009 16:13
 */
class gInput extends g_Input
{	

	// Uso nos formularios
	static $ondesktop=false;
	
	function __construct($json)
	{
		//$this->bodyEvent=" onload='focusIt()'";		
		parent::__construct($json);
	}



//======================================= funcoes gerais =======================================
	

	/** Monta uma janela extJS
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $name Nome do objeto
	 * @param string $param JSON ou matriz com parâmetros
	 * @param string $items JSON ou matriz com items
	 * @return mixed $sai
	 */
	function window($name,$param="",$items="")
	{
		$param=extjsPasteItems($param,$items);
		extjsVar($name,"Ext.Window",$param);
		extjsDo("$name.show();");
	}
	
	/** Monta um painel
	 * @author	giuliano
	 * @version	1.0 16-06-2009 14:29
	 * @param string $name Nome do objeto
	 * @param string $param JSON ou matriz com parâmetros
	 * @param string $items JSON ou matriz com items
	 * @return mixed $sai
	 */
	function panel($json,$processJson=true)
	{
		if (substr_count($json,";")>substr_count($json,","))
		{
			$mtz=cssDecode($json);
		} else
		{
			$mtz=jsDecode($json);
		}
		$url=$mtz['url'];
		$name=$mtz['name'];
		$mtz['title']=gT($mtz['title']);
		$title=$mtz['title'];

		if ($url=="")
			$mtz['url']=$_SERVER["PHP_SELF"];
		if ($title=="")
			$title="NoNamePanel";
		if ($name=="")
		{
			$name=gFieldReverse($title); // chapa o texto
			$mtz['name']=$name;
		}
		/*
		$defaults="{width: '200'; height: '100'}";
		$param=cssMerge($defaults,$mtz);
		*/
		extjsVar($name,"Ext.Panel",$json);
		if (is_array($this->extjsVTypes))
		{
			foreach ($this->extjsVTypes as $js)
			{
				extjsDo($js);
			}
		}
		extjsDo("$name.render(document.body);");
		//$this->gBody();
	}

	/** Monta todo o código de início da página
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function gBegin()
	{
		// só executa este código uma vez!
		extjsRender();
		$sai=parent::gBegin();
		return($sai);
	}
	
	/** Monta todo o código de final da página
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function gEnd()
	{
		global $htmlBuffer;
		// só executa este código uma vez!
		if (!$this->statusEnd)
		{
		}
		echo $htmlBuffer;
		$htmlBuffer="";
		$sai=parent::gEnd();
		return($sai);
	}
	
	/** Concui o objeto
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function __destruct()
	{
		// checa qual o tipo de página a ser gerada (Web, PDF, XLS, etc.)
		// monta código de saída
		$this->gEnd();
		parent::__destruct();
	}
	
}

?>
