<?
/** Este arquivo contém a estrutura padrão para entrada de dados WEB
 *  (Internet Explorer, Firefox, Chrome, Opera, Safari)
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */


/** Função para gerar um store de dados em formato ExtJS
 * @package	gMsg
 * @author	giuliano
 * @version	1.0 29-10-2009 16:13
 * Cinco formas de usar:
 *  1: $nome=Nome do objeto, $dados=array de duas dimensões com os dados
 *  2: $nome=Nome do objeto, $dados=nome da tabela (usa os dois primeiros campos)
 *  3: $nome=Nome do objeto, $dados=query completa
 *  4: $nome=Nome do objeto e ao mesmo tempo, nome da tabela
 *  5: $nome=Nome do objeto e ao mesmo tempo, nome do SP
 */
function gStore($nome,$dados="")
{
	$sai="var $nome = new Ext.data.ArrayStore({id: 'id', fields:['id','descricao'],data:[";
	$el="";
	if (is_array($dados))
	{
		// Array de dados
		foreach ($dados as $key=>$value)
		{
			$el[]="['$key','$value']";
		}
	} else
	{
		// Query
		$dados=jcombo2query($dados);
//		echo "<pre>";print_r($dados);
		$rs=gDB::run($dados);
		$ttlfld=$rs->FieldCount();
		for ($g_t=0; $g_t<$ttlfld; $g_t++)
		{
			$fld=$rs->FetchField($g_t);
			$fldnames[]=$fld->name;

		}
		$rs=gDB::run($dados);
		$ttlfld=$rs->FieldCount();
		for ($g_t=0; $g_t<$ttlfld; $g_t++)
		{
			$fld=$rs->FetchField($g_t);
			$fldnames[]=$fld->name;

		}

		$sai="var $nome = new Ext.data.ArrayStore({id: '".$fldnames[0]."', fields:['".implode("','",$fldnames)."'],data:[";
		while (!$rs->EOF)
		{
			$t="";
			//$el[]="['".autoencode($rs->fields[0])."','".autoencode($rs->fields[1])."']";
			for ($a=0; $a<$ttlfld; $a++)
				$t[]="'".autoencode($rs->fields[$a])."'";
			$el[]="[".implode(",",$t)."]";
			$rs->MoveNext();
		}
	}
	$sai.=implode(",",$el);
	$sai.="]});";
	return ($sai);
}


class gGraphic
{
	public $el;
	public $json;

	function __construct($json)
	{
		$this->json=$json;
	}
	function add($json)
	{
		$this->el[]=$json;
	}
	function get()
	{
		$mtz=cssDecode($this->json);

		$sai="";
		$title=$mtz['title'];
		$renderTo='onBody';
		if ($mtz['renderTo']<>"")
			$renderTo=$mtz['renderTo'];

		$w="300";
		$h="300";
		if ($mtz['size']=="normal")
		{
			$w="606";
			$h="600";
		}
		if ($mtz['size']=="big")
		{
			$w="909";
			$h="600";
		}

		if ($mtz['size']=="wide")
		{
			$w="606";
			$h="300";
		}
		if ($mtz['size']=="full")
		{
			$w="'100%'";
			$h="440";
		}
		foreach ($this->el as $el)
		{
			$lin=cssDecode($el);
			if (floatval($lin['value'])>0)
				$dados[]="{fieldLabel: '".$lin['fieldLabel']."', value: ".floatval($lin['value'])."}";
		}
		$dados=implode(",",$dados);
		$sai="
				 var store = new Ext.data.JsonStore({
					  fields: ['fieldLabel', 'value'],
					  data: [$dados]
				 });

";
		switch ($mtz['type'])
		{
			case "pie":

				$sai.="

				 new Ext.Panel({
					  width: $w,
					  height: $h,
					  title: '$title',
					  renderTo: '$renderTo',
					  items: {
							store: store,
							xtype: 'piechart',
							dataField: 'value',
							categoryField: 'fieldLabel',
							extraStyle:
							{
								 legend:
								 {
									  display: 'bottom',
									  padding: 5,
									  font:
									  {
											family: 'Tahoma',
											size: 10
									  }
								 }
							}
					  }
				 });
				 //grf.render(document.body);
				";
				break;
			case "line":
				$sai.="
				new Ext.Panel({
					  title: '$title',
					  renderTo: '$renderTo',
					  width:$w,
					  height:$h,
					  layout:'fit',

					  items: {
							xtype: 'linechart',
							store: store,
							xField: 'fieldLabel',
							yField: 'value',
						listeners: {
							itemclick: function(o){
								var rec = store.getAt(o.index);
								Ext.example.msg('Item selecionado', 'Você escolheu {0}.', rec.get('fieldLabel')+': '+reg.get('value'));
							}
						}
					  }
				 });
";
				break;
			case "bar":
				$sai.="
				new Ext.Panel({
					  title: '$title',
					  renderTo: '$renderTo',
					  width:$w,
					  height:$h,
					  layout:'fit',

					  items: {
							xtype: 'columnchart',
							store: store,
							xField: 'fieldLabel',
							yField: 'value',
						listeners: {
							itemclick: function(o){
								var rec = store.getAt(o.index);
								Ext.example.msg('Item selecionado', 'Você escolheu {0}.', rec.get('fieldLabel')+': '+reg.get('value'));
							}
						}
					  }
				 });
";
				break;
		}
		return($sai);
	}

	function render()
	{
		$param=$this->get();
		extjsDo($param);
	}
}

/** Class para gerar mensagens na tela
 * @package	gMsg
 * @author	giuliano
 * @version	1.0 29-10-2009 16:13
 */
class gMsg
{
	static function alert($title,$text)
	{
		$param="{title: '".gT("alert")."', width: 300, height: 100,  html: '<div style=\"text-align: center; margins: 3px 3px 3px 3px\">".gT("logintimeout")."</div>'}";
		extjsVar("msg","Ext.Window",$param);
		extjsdo("msg.show();");
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
			if ((strpos($item[1]," ")===false) && (strpos($item[1],"\n")===false))
				$i="{id: 'tab$cnt', title: '".$item[0]."', items: [".$item[1]."]}";
			else
				$i="{id: 'tab$cnt', title: '".$item[0]."', frame: true, html: '".str_replace("\n",'\n',str_replace("'","\"",$item[1]))."'}";
			$items[]=$i;
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
		$sai.=jsMerge("{xtype: 'panel', layout: 'table', baseCls:'x-plain',autoScroll: true,bodyStyle: 'padding:0px'}",$this->param);
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

	public $hiddenFields, $script='';

	function add($json,$par="",$listener="")
	{
		if (trim($json)<>"")
		{
			$this->originalFields[]=$json;
			$mtz=cssDecode($json);
			$name=$mtz['name'];
			$fieldLabel=$mtz['fieldLabel'];
			$type=$mtz['type'];
			
			if ($name=="")
				$name=gString2Field($fieldLabel);
			if ($fieldLabel=="")
				$fieldLabel=gField2String($name);

			if ($type=="comboMultiSelection")
			{
				//if (stripos($name,"[]")===false)
					$name.="[]";
			}
			
			$mtz['name']=$name;
			$mtz['fieldLabel']=gT($fieldLabel);

			$ememo=false;
			
			if ($type=="gps")
			{
				$type="exclude";
				$this->hasGps=$name;
			}
			if ($type=="hidden")
			{
				$mtz['value']=str_replace("\r","",str_replace("\n",'\n',$mtz['value']));
			}
			if ($type=="textarea")
			{
				$ememo=true;
				$this->hasTextarea=true;
				//$mtz['value']=str_replace("\r","",str_replace("\n",'\n',$mtz['value']));
			}
			if ($type=="memo")
			{
				$ememo=true;
				$this->hasTextarea=true;
				//$mtz['value']=str_replace("\r","",str_replace("\n",'xxx',$mtz['value']));
				//		alert();
				//$this->script.="tmp=encodeBase64(Ext.getCmp('".$name."').getValue());Ext.getCmp('".$name."').setValue(tmp);";
				//$this->script.="tmp=htmlentities(Ext.getCmp('".$name."').getValue(),'ENT_QUOTES');Ext.getCmp('".$name."').setValue(tmp);";
				//$this->script.="tmp=Ext.getCmp('".$name."').getValue(); tmp=tmp.replace('\"','');Ext.getCmp('".$name."').setValue(tmp);";
			}
			if (substr($type,0,5)=="combo")
			{
				$items=$mtz['items'];
				unset($mtz['items']);
				//$mtz=cssRemove($mtz,'items');
				$mtz['name']=$type.$name;
				$mtz['hiddenName']=$name;
				$mtz['hiddenId']=$name;
			}
			if ($type=="checkbox")
			{
				if (isset($mtz['value']))
				{
					if ((intval($mtz['value'])==1) || ($mtz['value']=="on") || ($mtz['value']=="true"))
					{
						$mtz['checked']="true";
					}
					unset($mtz['value']);
				}
			}
			$mtz['id']=$mtz['name'];

			if ($type<>'exclude')
			{
				$mtz=cssRemove($mtz,'type');
				$typ=jsDecode($this->types[$type]);
				foreach ($typ as $key=>$value)
				{
					if (!isset($mtz[$key]))
					{
						$mtz[$key]=str_replace("'","’",$value);
					}
				}
				$sai=jsEncode($mtz);
				$sai=str_replace("@__me",$name,$sai);
				if ($mtz['validator']=="vDate")
				{
					//vDate(o,fmt,nulo,msgerro)
					$fmt=gVar("global.dateformat");
					$nul=$mtz['allowBlank']=='false'?"false":"true";
					$sai=substr($sai,0,strlen($sai)-4).",'$fmt',$nul,'".gT("Data inválida!")."')}}";
				} else
				{
				}

				if ($type=='file')
					$this->upload=true;
				/*
				$sai=jsEncode($mtz);
				$sai=jsMerge($this->types[$type],$sai);
				*/
				if ( ($type=="positive") || ($type=="positiveInteger") || ($type=="number") || ($type=="integer") )
				{
					$max=gVar("global.numlength");
                    if($mtz['maxLength']<>"")
                        $max=$mtz['maxLength'];
                    else
                    	$max=20;
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
					$sai=jsMerge($sai,"{maxLength: $max, decimalSeparator: '$decimalSeparator',  decimalPrecision: $decimalPrecision}");
					//gLog("=>>>> SAAII : $sai");
				}
				if ($type=='cpf')
				{
					$script="Ext.apply(Ext.form.VTypes, {
						vtCpf:  function(v) {
						return /^\d{1,11}$/.test(v);
						},
						vtCpfText: '".gT("must be an cpf")."',
						vtCpfMask: /[\d\.]/i
					});";
					$this->extjsVTypes[$type]=$script;
				}
				/*
				if ($type=="password")
				{
					$script="Ext.apply(Ext.form.VTypes, {
	   password: function(value, field)
	   {
	      if (field.initialPasswordField)
	      {
	         var pwd = Ext.getCmp(field.initialPasswordField);
	         this.passwordText = 'Confirmation does not match your intial password entry.';
	         return (value == pwd.getValue());
	      }

	      this.passwordText = 'Passwords must be at least 5 characters, containing either a number, or a valid special character (!@#$%^&*()-_=+)';

	      var hasSpecial = value.match(/[0-9!@#\$%\^&\*\(\)\-_=\+]+/i);
	      var hasLength = (value.length &gt;= 5);

	      return (hasSpecial && hasLength);
	   },

	   passwordText: 'Passwords must be at least 5 characters, containing either a number, or a valid special character (!@#$%^&*()-_=+)',
	});";
					$this->extjsVTypes[$type]=$script;
				}
				*/
				if ($type=="ip")
				{
					//
					// /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/
					$script="Ext.apply(Ext.form.VTypes, {
				 vtIPAddress:  function(v) {
					  return /^([1-9][0-9]{0,1}|1[013-9][0-9]|12[0-689]|2[01][0-9]|22[0-3])([.]([1-9]{0,1}[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])){2}[.]([1-9][0-9]{0,1}|1[0-9]{2}|2[0-4][0-9]|25[0-4])$/.test(v);
				 },
				 vtIPAddressText: '".gT("must be an ip address")."',
				 vtIPAddressMask: /[\d\.]/i
				});";
					$this->extjsVTypes[$type]=$script;
				}
				if ($type=='plate')
				{
					$script="Ext.apply(Ext.form.VTypes, {
						vtplate:  function(v) {
						return /^[A-Za-z]{3}[0-9]{4}$/.test(v);
						},
						vtplateText: '".gT("must be an plate")."',
						vtplateMask: /[a-zA-Z0-9]/
					});";
					$this->extjsVTypes[$type]=$script;
				}
				if ($type=='container')
				{
					$script="Ext.apply(Ext.form.VTypes, {
						vtcontainer:  function(v) {
						return /^[A-Za-z]{4}[0-9]{6,7}$/.test(v);
						},
						vtcontainerText: '".gT("must be an container")."',
						vtcontainerMask: /[a-zA-Z0-9]/
					});";
					$this->extjsVTypes[$type]=$script;
				}
				if ($type=='interpos')
				{
					$script="Ext.apply(Ext.form.VTypes, {
						vtinterpos:  function(v) {
						return /^[0-9]{1}[A-Za-z]{1}[0-9]{2}[0-9]{2}[0-9]{1}$/.test(v);
						},
						vtinterposText: '".gT("Tem que ser uma posição válida. Ex. 1A01011")."',
						vtinterposMask: /[a-zA-Z0-9]/
					});";
					$this->extjsVTypes[$type]=$script;
				}
				if (substr($type,0,5)=="combo")
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
						$store=jcombo2store($par);
						$sai=substr($sai,0,strlen($sai)-2).", $store } ";
					}
					//$script="var gCombo$name=new Ext.data.ArrayStore({id: 0,fields: ['id','text'],data: [[1, 'item1'], [2, 'item2']]});";
					//$this->extjsVTypes[$type.$name]=$script;
				}
				if ($listener<>"")
					$sai=substr(trim($sai),0,strlen(trim($sai))-1).",$listener}";

				if ($type=="hidden")
				{
					$this->hiddenFields[]=$sai;
				} else
				{
					if ($ememo)
						$this->fields[]=str_replace("'",'"',str_replace("\r",'',str_replace("\n",'\n',$sai)));
					else
						$this->fields[]=$sai;
				}

			}
		}
		return;
	}

	function getFields()
	{
		$f=$this->fields;
		foreach ($this->hiddenFields as $i)
			$f[]=$i;
		return($f);
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
		global $gDevice,$gOs,$browser;
		$script=$this->script;

		//echo "<pre>";print_r($this->fields);echo "</pre>";exit;
		extjsVTypes($this->extjsVTypes);

		$json=$this->json;

		$mtz=cssDecode($json);
		if ($mtz['standardSubmit']=='')
			$mtz['standardSubmit']='true';

		$url=trim($mtz['url']);
		$cols=$mtz['columns'];
		$mtz=cssRemove($mtz,"columns");

		$name=$mtz['name'];
		$title=gT($mtz['title']);
		$mtz['title']=$title;
		$subTitle='';

		if ($url=="")
			$mtz['url']=$_SERVER["PHP_SELF"]."?g=".$_REQUEST['g'];
		$url=trim($mtz['url']);
		if ($title=="")
			$title="NoNameForm";
		if ($name=="")
		{
			$name=gString2Field($title); // chapa o texto
			$mtz['name']=$name;
		}
		if (($mtz['subTitle']<>'') && (stripos($browser,"msie")===false))
		{
			$subTitle="title: '<span class=\\'g-msg-minititle\\'>&nbsp;".gT($mtz['subTitle'])."</span>', ";
		}
		unset($mtz['subTitle']);
		$instr='';
		if ($mtz['instructions']<>'')
		{
			$instr="{xtype: 'label', style: {color: 'grey', padding: '6px'},html: '".gT($mtz['instructions'])."'}";
			$this->fields[]=$instr;
		}

		$mtz=cssRemove($mtz,"instructions");
		$mtz=cssRemove($mtz,"subTitle");
		$this->name=$name;
		$this->json=cssEncode($mtz);
		$nomeBotaoAvancar=gT('Confirmar');
		if ($this->buttonNextCaption<>'')
			$nomeBotaoAvancar=$this->buttonNextCaption;
		$nomeBotaoVoltar=gT('Voltar');
		if ($this->buttonBackCaption<>'')
			$nomeBotaoVoltar=$this->buttonBackCaption;

		if ($this->upload)
		{
			$upload="fileUpload:true,";
		}
		if ($this->hasGps<>'')
		{
			$gpsJs="+'&".$this->hasGps."='+gGeoLocation; ";
		}
		if ($this->toolbar===false)
		{
			// 1 coluna
			$defaults="{bodyStyle:'padding:5px 5px 5px 5px', style: 'text-align: left', $upload monitorValid:true, labelWidth:140, frame:true, defaultType:'textfield', collapsible: false, submitEmptyText: true, standardSubmit: true, modal:true, width: '100%', height: '100%' }";
			//$defaultsBtns="{text: '".gT("save")."', handler: function() {".$name.".getForm().getEl().dom.action = '$url';".$name.".getForm().getEl().dom.method = 'POST';".$name.".getForm().submit();}}";
			if ($mtz['button']<>"")
			{
				$defaultsBtns="{text: '".gT("Aplicar")."', formBind:true, handler: function(){".$mtz['button']."}}";
			} else
			{
				if (intval($_REQUEST['gPage'])>0)
					$defaultsBtns="{text: '".$nomeBotaoVoltar."', handler: function(){m=new Ext.LoadMask(Ext.getBody(),{msg:\"".gT("Carregando...")."\"});m.show();history.go(-1)}},";
				//$defaultsBtns="{text: '".$nomeBotaoVoltar."', handler: function(btn){document.location.href='".$this->page."'}},";
				$defaultsBtns.="{text: '".$nomeBotaoAvancar."', formBind:true, handler: function(btn) {".$script." m=new Ext.LoadMask(Ext.getBody(),{msg:\"".gT("Carregando...")."\"});m.show();".$name.".getForm().getEl().dom.action = '$url'$gpsJs;".$name.".getForm().getEl().dom.method = 'POST';".$name.".getForm().submit();}}";
			}
			if (($cols>1) && ($gDevice=="web"))
			{
				// 2 ou mais colunas
				//$defaults="{bodyStyle:'padding:5px 5px 0', labelWidth: 130, frame:true, width: '100%', renderTo: 'document.body', layout:'column'}";

				$defaults="{bodyStyle:'padding:5px 5px 0', style: 'text-align: left', $upload monitorValid:true, labelWidth: 130, collapsible: false, submitEmptyText: true, frame:true, layout:'column'}";
				$param=jsMerge($defaults,jsEncode($mtz));
				$colsper=1/$cols;
				$param=substr($param,0,strlen($param)-1).", defaults: {layout: 'form', border: false, bodyStyle: 'padding:4px'},";
				$param.="items: [";
				$rows="";

				$fator=intval(count($this->fields)/$cols+0.5);
				for ($c=0; $c<$cols; $c++)
				{
					$col="{xtype:'fieldset', $subTitle columnWidth: $colsper, collapsible: false, autoHeight:true, defaultType: 'textfield'}";
					if (($subTitle<>'') && (stripos($browser,"msie")===false))
						$subTitle="title: '<span class=\\'g-msg-minititle\\'>&nbsp;</span>', ";
					$newItens="";
					for ($b=$c*$fator; $b<($c+1)*$fator; $b++)
						$newItens[]=$this->fields[$b];
					if ($c==$cols-1)
					{
						foreach ($this->hiddenFields as $i)
							$newItens[]=$i;
					}
					$rows[]=extjsPasteItems($col,$newItens);
				}
				$param.=implode(",",$rows)."]}";
				$param=str_replace(",]","]",$param);
				$param=extjsPasteButtons($param,$defaultsBtns);
				foreach ($this->hiddenFields as $i)
					$this->fields[]=$i;
			} else
			{
				// 1 coluna
				$col="{xtype:'fieldset', $subTitle collapsible: false, autoHeight:true, defaultType: 'textfield'}";
				$param=jsMerge($defaults,jsEncode($mtz));
				//$param=jsMerge($defaults,$col);
				foreach ($this->hiddenFields as $i)
					$this->fields[]=$i;
				$param=extjsPasteItems($param,$this->fields);
				$param=extjsPasteButtons($param,$defaultsBtns);
			}
		} else
		{
			foreach ($this->hiddenFields as $i)
				$this->fields[]=$i;
			foreach ($this->fields as $key=>$i)
				$this->fields[$key]=str_replace("emptyText: '".gT("Selecione...")."', ",'',str_replace("fieldLabel","emptyText",$i));
			//$this->fields[]="{text: ' ".gT("Aplicar")." ', handler}";
			$param="{width: '100%', renderTo: 'beforeBody'}";
			$param=extjsPasteItems($param,$this->fields);
			$param=substr($param,0,  strlen($param)-2);
			$param.=",{text: ' ".gT("Aplicar")." ',
	handler: function() {filterGo()}

				}";
			$param.="]}
";
		}

		return($param);
	}

	function render()
	{
		$param=$this->get();
		$name=$this->name;
		$json=$this->json;
		$mtz=cssDecode($json);
		$url=$mtz['url'];

		if ($this->toolbar===false)
		{
			extjsVar($name,"Ext.FormPanel",$param);
			if (!$this->hasTextarea)
			{
				if ($mtz['button']<>"")
				{
					$keymap="new Ext.KeyMap(document, {
					  key: Ext.EventObject.ENTER,
					  fn: function(k,e){
							".$mtz['button']."
					  }
					});";

				} else
				{
					$keymap="new Ext.KeyMap(document, {
					  key: Ext.EventObject.ENTER,
					  fn: function(k,e){

							$name.getForm().getEl().dom.action ='$url';
							$name.getForm().getEl().dom.method = 'POST';
							$name.getForm().submit();
					  }
					});";
				}
			}
			extjsDo($keymap);
			extjsDo("$name.render('onBody');");
		} else
		{

			$name=$mtz['name'];
			$title=$mtz['title'];

			if ($url=="")
				$mtz['url']=$this->page;
			if ($title=="")
				$title="NoNameForm";
			if ($name=="")
			{
				$name=gString2Field($title); // chapa o texto
				$mtz['name']=$name;
			}
			$this->name=$name;
			$keymap="new Ext.KeyMap(document, {
			  key: Ext.EventObject.ENTER,
			  fn: function(k,e){filterGo()}
			});";
			$func="
			function filterGo()
			{
				var w=Ext.getBody();
				w.mask();

				Ext.Ajax.request({
					url: '".$this->page."',
					params: {
							ajax: 'true'";
			foreach ($this->fields as $i)
			{
				$m=jsonDecode($i,",");
				$n1=$n2=$m['name'];
				if ($m['xtype']=="'combo'")
					$n1=$m['hiddenId'];
				$n1=str_replace("'",'',$n1);
				$func.=",\n$n1: Ext.getCmp($n2).getValue() ";
			}
			$func.="
						},
					success : function(result, operation){
						w.unmask();
						//Ext.Msg.alert(Ext.getCmp('').getValue());
						//jsonData = Ext.util.JSON.decode(result.responseText);
						document.getElementById('onBody').innerHTML=result.responseText;
					},
					failure : function(){
						w.unmask();
						Ext.Msg.alert('Salvar', 'Houve uma falha ao conectar com o servidor');
					}

				});
			}
				";



			extjsVar("tb".$name,"Ext.Toolbar",$param);
			extjsVar($name,"Ext.FormPanel","{title: '$title', items: tb$name}");
			//extjsDo("tb$name.render(document.body);");
			extjsDo($func);
			extjsDo($keymap);
			extjsDo("$name.render('beforeBody');");
			//'beforeBody'
		}
		if ($this->hasGps<>'')
		{
			$gpsJs="gGPS(); ";
			addJavaScript($gpsJs);
		}

		// Limpa apos o uso
		$this->fields="";
		$this->buttons="";

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

		/*
		if (is_array($this->extjsVTypes))
		{
			foreach ($this->extjsVTypes as $js)
			{
				extjsDo($js);
			}
		}
		*/
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
		// só executa este código uma vez!
		if (!$this->statusEnd)
		{
		}
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
