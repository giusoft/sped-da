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
			$items[]="{id: 'tab$cnt', title: '".$item[0]."', items: [".$item[1]."]}";
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

	public $tabButtons;

	function __construct($json)
	{
		global $useExtInterface;
		$useExtInterface=false;
		parent::__construct($json);

		$this->types["hidden"]="{xtype: 'hiddenfield'}";
		$this->types["show"]="{xtype: 'displayfield'}";
		$this->types["label"]="{xtype: 'label', cls: 'x-form-item', anchor: '100%'}";
		$this->types["text"]="{xtype: 'textfield', required: false}";
		$this->types["textarea"]="{xtype: 'textareafield', required: false}";
		$this->types["memo"]="{xtype: 'textareafield', required: false}";
		$this->types["upperText"]="{xtype: 'textfield', convertToUpperCase: true, required: false}";
		$this->types["lowerText"]="{xtype: 'textfield', convertToLowerCase: true, required: false}";
		$this->types["upperFirstWordText"]="{xtype: 'textfield', required: false, autoCapitalize: true}";
		$this->types["upperFirstLetterText"]="{xtype: 'textfield', required: false, autoCapitalize: true}";
		$this->types["password"]="{xtype: 'passwordfield', required: true, useClearIcon: true}";
		$this->types["url"]="{xtype: 'urlfield', required: false }";
		$this->types["email"]="{xtype: 'emailfield', useClearIcon: true}";
		$this->types["number"]="{xtype: 'numberfield', required: false}";
		$this->types["integer"]="{xtype: 'numberfield', allowDecimals: false, required: false}";
		$this->types["positive"]="{xtype: 'numberfield', allowNegative: false, required: false}";
		$this->types["positiveInteger"]="{xtype: 'numberfield', allowNegative: false, allowDecimals: false, required: false}";
		$this->types["integerPositive"]="{xtype: 'numberfield', allowNegative: false, allowDecimals: false, required: false}";
		$this->types["positive"]="{xtype: 'numberfield', allowNegative: false, required: false}";
		//$this->types["date"]="{xtype: 'datefield', dateFormat: 'd-m-y', format: 'd-m-y', altFormats: 'd/m/Y|j/n/Y|j/n/y|j/m/y|d/n/y|j/m/Y|d/m/Y|d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|d-m-Y', required: false, validateOnBlur: true, validationEvent: 'blur', validator: 'vDate'}";
		//$this->types["date"]="{xtype: 'datepickerfield', required: false}";
		$this->types["date"]="{xtype: 'textfield', convertToLowerCase: true,  maxLength: 8, required: false}";
		$this->types["datetime"]="{xtype: 'textfield', convertToLowerCase: true,  maxLength: 17, required: false}";
		//$this->types["datetime"]="{xtype: 'datetime', dateFormat: 'd-m-y', hiddenFormat: 'c',dateConfig: { altFormats:'Y-m-d H:i:s|d-m-y'}, timeFormat: 'H:i', timeConfig: {width: '20px', required: false, increment:60}, format: 'd-m-y H:i', required: false}";
		$this->types["time"]="{xtype: 'textfield', convertToLowerCase: true,  maxLength: 5, required: false}";
		//$this->types["time"]="{xtype: 'timefield', format: 'H:i', required: false, width: 80}";
		$this->types["ip"]="{xtype: 'textfield', vtype:'vtIPAddress', minLength: 7, maxLength: 15, required: false}";
		$this->types["ncm"]="{xtype: 'textfield', minLength: 10, maxLength: 14, required: false, validator: 'vNCM'}";
		$this->types["plate"]="{xtype: 'textfield', convertToUpperCase: true, vtype: 'vtplate', minLength: 7, maxLength: 7, required: false}";
		$this->types["checkbox"]="{xtype: 'checkboxfield'}";
		$this->types["cpf"]="{xtype: 'textfield', vtype: 'vtCpf', minLength: 11, maxLength: 11, required: false}";
		$this->types["cnpj"]="{xtype: 'textfield', minLength: 14, maxLength: 14, required: false}";
		$this->types["combo"]="{xtype: 'selectfield'}";
		//$this->types["container"]="{xtype: 'textfield', convertToUpperCase: true, vtype: 'vtcontainer', minLength: 10, maxLength: 11, required: false,  validateOnBlur: true, validationEvent: 'blur', validator: 'vCntr'}";
		$this->types["container"]="{xtype: 'textfield', convertToUpperCase: true, vtype: 'vtcontainer', minLength: 10, maxLength: 11, required: false}";
		$this->types["interpos"]="{xtype: 'textfield', convertToUpperCase: true, vtype: 'vtinterpos', minLength: 7, maxLength: 11, required: false}";
		$this->types["file"]="{xtype: 'fileuploadfield', emptyText: '".gT("Selecione um arquivo")."', buttonText: '', buttonCfg: {iconCls: 'b2002_16'}}";
		$this->types["image"]="{xtype: 'fileuploadfield', emptyText: '".gT("Selecione um arquivo")."', buttonText: '', buttonCfg: {iconCls: 'b2002_16'}}";
	}
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
		$mtz['label']=$fieldLabel;
		unset($mtz['fieldLabel']);

		$type=$mtz['type'];
		if ($type=="comboMultiSelection")
			$type="combo";
		if (isset($mtz['allowBlank']))
		{
			$ab=$mtz['allowBlank'];
			$mtz['required']=($ab=='false'?"true":"false");
			//echo "<h1>$name ab: $ab <pre>";print_r($mtz);exit;
			unset ($mtz['allowBlank']);
		}

		if (isset($mtz['emptyText']))
		{
			$mtz['placeHolder']=$mtz['emptyText'];
			unset ($mtz['emptyText']);
		}
		$items=$mtz['items'];
		unset($mtz['items']);

		//$mtz['labelWidth']="'0'";
		$ememo=false;
		if ($type=="gps")
		{
			$type="exclude";
			$this->hasGps=$name;
		}
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
		if ($type=="show")
			$type="label";
		$mtz['id']=$mtz['name'];

		if ($name=="id")
			$type="hidden";

		if ($type=="label")
		{
			$type="show";
		}
		//gLog("=============== $name = $type");
		if ($type<>'exclude')
		{
			$mtz=cssRemove($mtz,'type');

			if ($type=="combo")
			{
				//$mtz=cssRemove($mtz,'items');
				$mtz['name']="combo".$name;
				$mtz['hiddenName']=$name;
				$mtz['hiddenId']=$name;
				//echo $json;
			}
			if ($type=="datetime")
			{
				$novo=$mtz['value'];
				if ($novo<>'')
				{
					if (substr($novo,4,1)=="-")
					{
						$novo=gDateTime($novo);
						$mtz['value']=$novo;
					}
				}
			}
			if ($type=="date")
			{
				$novo=$mtz['value'];
				if ($novo<>'')
				{
					if (substr($novo,4,1)=="-")
					{
						$novo=gDate($novo);
						$mtz['value']=$novo;
					}
				}
			}
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
					/* Como especificar os dados da lista do combo?
					 * 1. Através de array de elementos
					 * 2. Passando o parâmetro items: <nome> onde <nome>=nome de uma tabela do banco
					 * 3. Passando o parâmetro items: <nome> onde <nome>=nome de um sp (stored procedure)
					 * 4. Passando uma query como parâmetro em items
					 * 5. Passando array em formato JSON
					 */

					if (!is_array($par))
					{
						if ($items<>'')
						{
							$par=$items;
						}
						else
							$par=$mtz['name'];
					}
					if ($mtz['remote']=='true')
					{
						$i=$_REQUEST['gId'];
						if ($_REQUEST['i']<>"")
							$i=$_REQUEST['i'];
						$q=$_REQUEST['q'];
						$sai=substr($sai,0,strlen($sai)-2).", displayField: 'text',
	store: new Ext.data.Store({
			reader: new Ext.data.JsonReader({
				fields: ['id','text'],
			  root: 'rows'
			}),
			proxy: new Ext.data.HttpProxy({url: '".$this->page."'}),
			baseParams: {
				process: 'remotecombo',
				itm: '$par',
				i: '$i',
				q: '$q',
				p: '',
				v: ''
			},
			autoLoad: true
		})
	}
			";
			//proxy: new Ext.data.HttpProxy({url: '".$this->page."?process=remotecombo&itm=$par&i=$i&q=$q'}),
					} else
					{
						$par=str_replace("\"","'",$par);

						$dados=jcombo2store($par,false);
						$opt='';$pre='';
						foreach ($dados as $key=>$value)
						{
							$opt[]="{text: '$value', value: '$key'}";
						}

						if ($mtz['required']<>"true")
							$pre="{text: '".gT("Selecione...")."', value: '0'}, ";
						$sai=substr($sai,0,strlen($sai)-2).", options: [$pre ".implode(",",$opt)."] }";
						// Metodo antigo (com Store)
						//$store=jcombo2store($par,false);
						//$sai=substr($sai,0,strlen($sai)-2).", $store } ";




					}
					//$script="var gCombo$name=new Ext.data.ArrayStore({id: 0,fields: ['id','text'],data: [[1, 'item1'], [2, 'item2']]});";
					//$this->extjsVTypes[$type.$name]=$script;
				}
			/*
			if ($type=="date")
			{
				$sai=substr($sai,0,strlen($sai)-2).", picker: {yearFrom: 1940, yearTo: 2039, slotOrder: ['day', 'month', 'year']} }";
			}
			*/
			if ($type=="show")
			{
				$sai="{html: '<div style=\\'text-align: center; padding: 4px;\\'>".$mtz['value']."</div>'}";
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
		$defaults="{bodyStyle:'padding:5px 5px 5px 5px', style: 'text-align: left', labelWidth:120, frame:true, defaultType:'textfield', collapsible: false, submitEmptyText: true, standardSubmit: true, method: 'POST', modal:true, width: '100%', height: '100%'}";
		$defaults="{fieldset:}";

		$nomeBotaoAvancar=gT('Confirmar');
		$avancarIcone="refresh";
		if ($this->buttonNextCaption<>'')
		{
			$nomeBotaoAvancar=$this->buttonNextCaption;
			$avancarIcone="arrow_right";
		}
		$nomeBotaoVoltar=gT('Voltar');
		if ($this->buttonBackCaption<>'')
			$nomeBotaoVoltar=$this->buttonBackCaption;

		if ($url=="")
			$mtz['url']=$this->page."?g=".$_REQUEST['g'];
		$url=$mtz['url'];
		if ($this->hasGps<>'')
		{
			$gpsJs="+'&".$this->hasGps."='+gGeoLocation; ";
		}
		$doSave="var f=Ext.get('gForm');f.dom.action='$url'$gpsJs;f.dom.method = 'POST';f.dom.submit();";
		//$doSave="alert('Oi')";
		if ($mtz['button']<>"")
		{
			$this->buttons[]="{iconCls: 'refresh', text: '".gT("Aplicar")."', handler: function(){".$mtz['button']."}}";
		} else
		{
			if ((intval($_REQUEST['gPage'])>0) && ($nomeBotaoVoltar<>'no'))
				$this->buttons[]="{iconCls: 'arrow_left', text: '$nomeBotaoVoltar', handler: function(){history.go(-1)}}";
			if ($nomeBotaoAvancar<>'no')
				$this->buttons[]="{iconCls: '$avancarIcone', text: '$nomeBotaoAvancar', handler: function() {".$doSave."}}";

		}
		// 1 coluna
		$param=jsMerge($defaults,jsEncode($mtz));
		$param=extjsPasteItems($param,$this->fields);
		return($param);
	}

	function render()
	{
		global $gBASE,$gDevice,$extjsInUse;
		$json=$this->json;
		$mtz=cssDecode($json);
		$param=$this->get();
		$dock="bottom";
		if ($gDevice=="tablet")
		{
			$dock="top";
		}
		$mtz['title']=gT($mtz['title']);
		$mtz['subTitle']=gT($mtz['subTitle']);
		$name=$mtz['name'];
		$title=$mtz['title'];
		$subtitle=$mtz['subtitle'];
		if ($subtitle<>'')
			$subtitle="title: '$subtitle', ";
		$instr="";
		if ($mtz['instructions']<>'')
			$instr="instructions: '".$mtz['instructions']."',";
		if ($url=="")
			$mtz['url']=$this->page."?g=".$_REQUEST['g'];
		if ($title=="")
			$title="NoNameForm";
		if ($name=="")
		{
			$name=gString2Field($title); // chapa o texto
			$mtz['name']=$name;
		}
		$this->name=$name;

		$url=$mtz['url'];


		$gApp=$_SESSION['gApp'];
		$titulo=$_SESSION['gAPPName'];
		if ($title<>'')
			$titulo=$title;
		// ======================= formulário
		$frm="
{
		id: 'gForm',
		items: [{
				xtype: 'fieldset',
				standardSubmit: true, $subtitle $instr
					items: [
						".implode(',',$this->fields)."
					]
				}]
}
			";
		// ======================= painel (menu)
		/*
		$pnl=
"			{
				title: '$titulo', xtype: 'form', id: 'id$name',
				fullscreen: true,
				scroll: {direction : 'vertical',eventTarget : 'parent'},
				url: '$url',
				dockedItems: [
					{
						dock: 'top',
						xtype: 'toolbar',
						title: '$titulo',
						items: [
							{
							dock: 'left',
							text: ' ',
							ui: 'back',
							handler: function ()
								{
									document.location=\"\/".$gBASE."\/login.php?g=&gIdApp=$gApp&t=mmenu\";
								}
							}
						]
					},
				  {
						dock: '$dock',
						xtype: 'tabbar',
						ui: 'light',
						layout: {pack: 'center'},
						items: [
							".implode(',',$this->buttons)."
						]
				  }
				],
				items: [{
					defaults: {               // defaults are applied to items, not the container
						autoScroll:true,

					},
					xtype: 'fieldset',
					instructions: 'Informe os dados solicitados acima',
					items: [ gForm ]
				}]
			}";
		 *
		 */

		if ($this->hasGps<>'')
		{
			$gpsJs="+'&".$this->hasGps."='+gGeoLocation; ";
		}
		$save="
var doSave = function()
{
	var f = gForm.getEl();

	f.dom.action = '$url'$gpsJs;
	f.dom.method = 'POST';
	gForm.submit();

}			";

		extjsVar("gForm","Ext.form.FormPanel",$frm);
		//extjsDo($save);
		$extjsInUse=true;

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
		parent::__construct($json);
		$this->useBuffer=true;
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
		global $useHtmlBuffer;
		$useHtmlBuffer=true;
		$this->outputRecording=true;
		return($sai);
	}

	/** Monta todo o código de final da página
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function gEnd()
	{
		global $htmlBuffer,$useExtInterface;
		global $gBASE;
		$this->outputRecording=false;
		// só executa este código uma vez!
		if (!$this->statusEnd)
		{
			//if ($useExtInterface)
			{
				$this->senchaHtml=$htmlBuffer.$this->buffer;
				$titulo=$_SESSION['gAPPName'];
				if ($this->pageTitle<>'')
					$this->senchaTitle=$this->pageTitle;
				senchaInterface($this->senchaTitle,$this->senchaHtml,$this->senchaItems,$this->senchaButtonsTop, $this->senchaButtonsBottom,$this->senchaInterface,$this->senchaInstructions,$this->senchaScroll);
			}
			extjsRender();
			$htmlBuffer='';
			$this->buffer='';
		}
		parent::gBegin();
		$sai=parent::gEnd();
		return($sai);
	}


	function screen($json,$url,$html,$itemAdicional="")
	{

		$jsn=cssDecode($json);
		$name="gScreen";

		$flds=$btns="";


		$mtz=jsonDecode($json,";",true);
		$name=stripQuote($mtz['name']);
		$title=stripQuote($mtz['title']);
		if (($title=="") && ($this->actualQuery==0))
			$title="NoNameGrid";
		if ($name=="")
		{
			$name=gFieldReverse($title); // chapa o texto
		}


		// Grid em si...
		// =============menu
		$this->extScreen($jsn['title'],$name,$url,$html,$itemAdicional);


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
