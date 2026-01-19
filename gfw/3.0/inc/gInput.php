<?php
/**
 *  gInput.php
 *
 * @author	Giuliano Nascimento
 * @version	3.0 07-04-2009 14:08
 */

include_once $gPathDefault . "gOutput.php";

// Testa se um array é associativo
function is_assoc( $array )
{
	return ( is_array( $array ) && 0 !== count( array_diff_key( $array, array_keys( array_keys( $array ) ) ) ) );
}

// Transforma uma string JSON (query, sp ou elementos) em uma query
function jcombo2query( $par )
{
	if ( strpos( $par, " " ) === false )
	{
		if ( sp( $par ) <> "" )
		// stored procedure
			$par = sp( $par );
		else
		// nome da tabela
			$par = "select * from $par";
	}
	return ( $par );
}

// Transforma uma string JSON (query, sp ou elementos) em array
// arrayJson = true  => retorna em formato JSON
//					false => retorna em array do PHP
function jcombo2array( $par, $limit = true, $filter = "", $arrayJson = true )
{
	$dados        = "";
	$displayField = "id";
	$flds         = array(
		 "'id'",
		"'text'"
	);
	if ( is_array( $par ) )
	{
		if ( count( $par ) == 0 )
		{
			$e = strtr( $par[ 0 ], "{}", "[]" );
		}
		elseif ( ( count( $par ) == 1 ) && ( strpos( $par, "{" ) !== false ) )
		{
			$e = strtr( $par[ 0 ], "{}", "[]" );
		}
		else
		{
			$displayField = "text";
			foreach ( $par as $key => $value )
			{
				if ( ( $filter == "" ) || ( $filter == $key ) || ( $filter == $value ) )
				{
					$value = str_replace( "'", "’", $value );
					if ( !$arrayJson )
						$dados[ $key ] = $value;
					else
						$dados[ ] = "['$key','$value']";
				}
			}
		}
	}
	elseif ( ( strpos( $par, "," ) !== false ) && ( strtoupper( substr( $par, 0, 6 ) ) <> "SELECT" ) )
	{
		if ( strpos( $par, "," ) !== false )
			$items = explode( ",", $par );
		else
			$items = explode( ";", $par );
		foreach ( $items as $item )
		{
			if ( ( $filter == "" ) || ( $filter == $item ) )
			{
				$item = str_replace( "'", "’", trim( $item ) );
				if ( !$arrayJson )
					$dados[ $item ] = $item;
				else
					$dados[ ] = "['$item','$item']";
			}
		}
	}
	else
	{

		if ( ( substr( $par, 0, 1 ) == "{" ) || ( substr( $par, 0, 1 ) == "[" ) )
		{
			// json
			$par = str_replace( "'", "", $par );
			$par = str_replace( "[{", "", $par );
			$par = str_replace( "}]", "", $par );
			$par = str_replace( "}, {", "},{", $par );
			$mtz = explode( "},{", $par );
			foreach ( $mtz as $value )
			{
				$el      = explode( ",", $value );
				$el[ 0 ] = trim( $el[ 0 ] );
				$el[ 1 ] = trim( str_replace( "'", "’", $el[ 1 ] ) );
				if ( ( $filter == "" ) || ( $filter == $el[ 0 ] ) || ( $filter == $el[ 1 ] ) )
				{
					if ( !$arrayJson )
						$dados[ $el[ 0 ] ] = $el[ 1 ];
					else
						$dados[ ] = "['" . $el[ 0 ] . "','" . $el[ 1 ] . "']";
				}
			}
		}
		else
		{
			$par = jcombo2query( $par );

			if ( $filter <> "" )
			{
				// Buscando nome ou alias da tabela...
				$parTmp = str_replace( "FROM ", "from ", $par );
				$parTmp = str_replace( "\n", " ", $parTmp );
				$parTmp = str_replace( "\t", "", $parTmp );
				$parTmp = explode( "from ", $parTmp );
				$parTmp = explode( " ", $parTmp[ 1 ] );
				$tab    = $parTmp[ 0 ];
				$ttab   = strtolower( $parTmp[ 1 ] );
				if ( strtolower( $parTmp[ 1 ] ) == "as" )
					$tab = $parTmp[ 2 ];
				elseif ( ( $ttab <> "" ) && ( $ttab <> "inner" ) && ( $ttab <> "left" ) && ( $ttab <> "right" ) && ( $ttab <> "outer" ) && ( $ttab <> "where" ) && ( $ttab <> "order" ) && ( $ttab <> "group" ) && ( $ttab <> "limit" ) )
					$tab = $parTmp[ 1 ];
				// Acrescentando "where id=filtro..."
				$ini   = strlen( $par );
				$where = " where ";
				$and   = "";
				if ( strpos( strtolower( $par ), "where " ) !== false )
				{
					$and   = " and ";
					$where = "";
					$ini   = strpos( strtolower( $par ), "where " ) + 6;
				}
				elseif ( strpos( strtolower( $par ), "order by " ) !== false )
				{
					$ini = strpos( strtolower( $par ), "order by " );
				}
				$id = substr( str_ireplace( "distinct ", "", $par ), 7 );
				if ( strpos( $id, " " ) < strpos( $id, "," ) )
					$id = substr( $id, 0, strpos( $id, " " ) );
				else
					$id = substr( $id, 0, strpos( $id, "," ) );
				if ( strpos( $id, "." ) !== false )
				{
					$m   = explode( ".", $id );
					$tab = $m[ 0 ];
					$id  = $m[ 1 ];
				}
				if ( trim( $id ) == '' )
					$id = 'id';
				$par = substr( $par, 0, $ini ) . $where . "$tab.$id='$filter' " . $and . substr( $par, $ini );
			}
			$rst = gDB::run( $par, 1 );
			if ( !$limit )
			{
				$ttlFields = $rst->FieldCount();
				$iddPos    = -1;
				unset( $flds );
				for ( $g = 0; $g < $ttlFields; $g++ )
				{
					$fld = $rst->FetchField( $g );
					if ( $fld->name <> 'idd' )
					{
						$flds[ ] = "'" . $fld->name . "'";
						//if (($displayField=='id') && ($rst->MetaType($fld)<>'I') && ($rst->MetaType($fld)<>'N'))
						if ( ( $displayField == 'id' ) )
							$displayField = $fld->name;
					}
					else
						$iddPos = $g;
				}
				while ( !$rst->EOF )
				{
					$els = "";
					for ( $a = 0; $a < $ttlFields; $a++ )
						if ( $a <> $iddPos )
						{
							/*
							if (!is_array($els))
							$els[]="'".trim(str_replace("'",".",$rst->fields[$a]))."'";
							else
							$els[1]=$els[1].trim(str_replace("'",".",$rst->fields[$a]))." ";

							*/
							if ( $a < 1 )
								$els[ ] = "'" . trim( str_replace( "'", ".", $rst->fields[ $a ] ) ) . "'";
							elseif ( $a == 1 )
								$els[ ] = "'" . str_replace( "'", "’", $rst->fields[ $a ] ) . "'";
							else
							{
								if ( trim( substr( $els[ 1 ], 0, strlen( $els[ 1 ] ) - 1 ) ) <> '' )
									$els[ 1 ] = str_replace( "'<br>", "'", "'" . substr( $els[ 1 ], 1, strlen( $els[ 1 ] ) - 2 ) . "<br>" . str_replace( "'", "’", $rst->fields[ $a ] ) . "'" );
								else
									$els[ 1 ] = "'" . str_replace( "'", "’", $rst->fields[ $a ] ) . "'";
							}

						}
					//$els[1]="'".$els[1]."'";
					if ( !$arrayJson )
						$dados[ str_replace( "'", "", $els[ 0 ] ) ] = str_replace( "'", "", $els[ 1 ] );
					else
						$dados[ ] = "[" . implode( ",", $els ) . "]";
					$rst->MoveNext();
				}
			}
			else
			{
				$ttlFields = $rst->FieldCount();
				$iddPos    = -1;
				unset( $flds );
				for ( $g = 0; $g < $ttlFields; $g++ )
				{
					$fld = $rst->FetchField( $g );
					if ( $fld->name <> 'idd' )
					{
						$flds[ ] = "'" . $fld->name . "'";
						//if (($displayField=='id') && ($rst->MetaType($fld)<>'I') && ($rst->MetaType($fld)<>'N'))
						if ( ( $displayField == 'id' ) )
							$displayField = $fld->name;
					}
					else
						$iddPos = $g;
				}
				while ( !$rst->EOF )
				{
					$els = "";
					$cnt = 0;
					for ( $a = 0; $a < $ttlFields; $a++ )
						if ( $a <> $iddPos )
						{
							if ( !is_array( $els ) )
								$els[ ] = "'" . trim( str_replace( "'", ".", $rst->fields[ $a ] ) ) . "'";
							else
								$els[ 1 ] = $els[ 1 ] . trim( str_replace( "'", ".", $rst->fields[ $a ] ) ) . " ";
						}
					$els[ 1 ] = "'" . $els[ 1 ] . "'";
					if ( !$arrayJson )
						$dados[ str_replace( "'", "", $els[ 0 ] ) ] = str_replace( "'", "", $els[ 1 ] );
					else
						$dados[ ] = "[" . implode( ",", $els ) . "]";
					$rst->MoveNext();
				}
			}
		}
	}
	if ( ( !$limit ) && ( $arrayJson ) )
	{

		$ddos = autoencode( implode( ",", $dados ) );
		unset( $dados );
		$dados[ ] = $displayField;
		$dados[ ] = "fields:[" . implode( ",", $flds ) . "],data:[$ddos]";
	}
	return ( $dados );
}

function jcombo2store( $par, $formatoJson = true )
{
	if ( $formatoJson )
	{
		$dados = jcombo2array( $par, false );
		$sai   = "displayField: '" . $dados[ 0 ] . "', store: new Ext.data.ArrayStore({id: 'id',align: 'left', " . $dados[ 1 ] . "})";
	}
	else
	{
		$sai = jcombo2array( $par, false, "", false );
	}
	return ( $sai );
}

function removeEstranhos( $txt )
{
	$search = array(
		 '%E2%80%99',
		'%E2%80%99',
		'%E2%80%98',
		'%E2%80%9C',
		'%E2%80%9D',
		'%0D'
	);
	$search = array(
		 '%0D'
	);
	$txt    = urlencode( $txt );
	$sai    = str_replace( $search, '', $txt );
	$sai    = urldecode( $sai );
	return ( $sai );
}

function senchaInterface( $title, $html, $items = "", $buttonsTop = "", $buttonsBottom = "", $interface = "", $instructions = "", $scroll = "both" )
{
	global $gBASE, $gApp, $gDevice, $http_base, $extjsInUse;
	$bkg    = $http_base . "pub/img/bgtapp.png";
	$name   = "senchaInterface";
	//$page=$_SERVER["PHP_SELF"]."?g=".$_REQUEST['g'];
	$inicio = $http_base . "login.php?gIdApp=" . $_SESSION[ 'gApp' ];
	if ( is_object( $html ) )
	{
		$html->render();
		$buttonsBottom = $html->buttons;
	}
	else
	{
		$html = str_replace( "\t", " ", str_replace( "\a", "", str_replace( "'", "\"", str_replace( "\n", "", str_replace( "'", "\\'", nl2br( $html ) ) ) ) ) );
		$html = trim( $html );
		//$html=removeEstranhos($html);
		$flds = '';
		if ( $html <> '' )
			$flds[ ] = "{html: '" . $html . "'}";
		if ( !empty( $items ) )
		{
			if ( is_array( $items ) )
				foreach ( $items as $item )
				{
					$flds[ ] = $item;
				}
			else
			{
				$flds[ ] = $items;
			}
		}
	}
	$bTop = $bBot = "";
	if ( !empty( $buttonsTop ) )
	{
		if ( !is_array( $buttonsTop ) )
		{
			$buttonsTop = array(
				 $buttonsTop
			);
		}
		$bTop = implode( ",", $buttonsTop );
	}
	if ( $instructions <> '' )
		$instructions = "instructions: '$instructions',";
	// Quando acessando aplicativo no perfil de outro usuário
	if ( $_SESSION[ 'usrClient' ] > 0 )
	{
		$sql   = "select id,nome from pessoas where id=" . $_SESSION[ 'usrIdd' ];
		$rs    = gFastQuery( $sql );
		$title = $rs->fields[ 'nome' ];
		if ( ( !empty( $buttonsBottom ) ) && ( !is_array( $buttonsBottom ) ) )
			$buttonsBottom = array(
				 $buttonsBottom
			);
		$buttonsBottom[ ] = "{text: 'Seu perfil',iconCls: 'globe2',handler: function (){document.location='" . $http_base . "login.php?t=start';}}";
	}

	if ( !empty( $buttonsBottom ) )
	{
		if ( !is_array( $buttonsBottom ) )
		{
			$buttonsBottom = array(
				 $buttonsBottom
			);
		}
		$bBot = implode( ",", $buttonsBottom ) . ",";
	}
	if ( $_SESSION[ 'usrClient' ] == 0 )
		$bBot .= "{text: '" . gT( "Início" ) . "', ui: 'action', iconCls: 'home',handler: function (){document.location='$inicio'}},";
	$frm = "";
	if ( ( !is_object( $html ) ) && ( !$extjsInUse ) )
	{
		if ( $interface == "carousel" )
		{
			$frm .= "
			 gForm = new Ext.Carousel({
				id: 'gIndex',
				layout: {type: 'vbox',align: 'stretch'},
				defaults: {flex: 1},
				direction: 'horizontal',
				items: [
					" . implode( ",", $flds ) . "
				]
			});
			";
		}
		elseif ( $interface == "panel" )
		{
			$frm .= "

			gForm = new Ext.Panel({
					standardSubmit: true,
					$instructions
					items: [
						" . implode( ',', $flds ) . "
					]
			})
			";
		}
		else
		{
			$frm .= "

			gForm = new Ext.form.FormPanel({
					standardSubmit: true,
					$instructions
					items: [
						" . implode( ',', $flds ) . "
					]
			})
			";
		}
	}
	$frm .= "
		gBarTitle = new Ext.Toolbar({
			title: '" . $title . "',
			ui: 'dark',
			cls: 'card',
			dock : 'top'
		});
	";

	if ( !empty( $buttonsTop ) )
	{
		$frm .= "
		gBarTop = new Ext.Toolbar({
			dock : 'top',
			ui: 'light',
			items: [
				$bTop
			]
		});
		";

	}


	{
		$frm .= "
		gBarBottom = new Ext.TabBar({
			cls: 'card',
			ui: 'light',
			dock : 'bottom',
			layout: {pack: 'center'},
			items: [
				$bBot
				{text: '" . gT( "Menu" ) . "', ui: 'action', iconCls: 'more',handler: function (){document.location='" . $inicio . "&t=mmenu'}},
				{text: '" . gT( "Sair" ) . "', ui: 'action',iconCls: 'power_on',handler: function (){document.location='$http_base" . gVar( 'page.logout' ) . "'}},
			]
		});
	";
	}
	//			{text: 'Voltar', ui: 'back', iconCls: 'arrow_left'},
	//			{xtype: 'spacer'},
	//			{text: 'Avançar',ui: 'forward', iconCls: 'arrow_right'}

	if ( $interface == "login" )
	{
		$frm .= "
			$name = new Ext.Panel({
				id: 'id$name',
				xtype: 'form',
				standardSubmit: true,
				url: '$url',
				layout: 'fit',
				fullscreen: true,
				items: [{
					scroll: {direction : '$scroll', eventTarget : 'parent'},
					xtype: 'container',
					items: [ gForm ]
				}]
			})

		";
	}
	elseif ( $interface == "carousel" )
	{
		$frm .= "
			$name = new Ext.Panel({
				id: 'id$name',
				xtype: 'form',
				standardSubmit: true,
				url: '$url',
				layout: 'fit',
				style: {background: 'url($bkg)'},
				fullscreen: true,
				dockedItems: [gBarTitle " . ( !empty( $buttonsTop ) ? ",gBarTop" : "" ) . " ,gBarBottom],
				items: [gForm]
			})

		";
	}
	else
	{
		$frm .= "
			$name = new Ext.Panel({
				id: 'id$name',
				xtype: 'form',
				standardSubmit: true,
				url: '$url',
				layout: 'fit',
				fullscreen: true,
				style: {background: 'url($bkg)'},
				dockedItems: [gBarTitle " . ( !empty( $buttonsTop ) ? ",gBarTop" : "" ) . " ,gBarBottom],
				items: [{
					scroll: {direction : '$scroll', eventTarget : 'parent'},
					xtype: 'container',
					items: [ gForm ]
				}]
			})

		";
	}

	extjsDo( $frm );
	extjsDo( "$name.render('beforeBody');" );
	return ( $frm );
}

/**
 *  Class que cria um formulário
 * @package	gInput
 * @author	Giuliano Nascimento
 * @version	1.0 29-10-2009 16:13
 */
class g_Form
{
	public $extjsVTypes = "";
	public $buttons = "";
	public $name;
	public $page = "";
	public $hasTextarea = false;
	public $hasGps = '';
	public $upload = false;

	public $json;
	public $types = "";
	public $fields = "";
	public $toolbar = false;
	public $buttonBackCaption = '';
	public $buttonNextCaption = '';
	public $originalFields;

	function getOriginalFields( )
	{
		return ( $this->originalFields );
	}

	function setButtonBackCaption( $txt )
	{
		$this->buttonBackCaption = $txt;
	}
	function setButtonNextCaption( $txt )
	{
		$this->buttonNextCaption = $txt;
	}

	function __construct( $json )
	{
		$mtz = cssDecode( $json );
		if ( $mtz[ 'toolbar' ] <> "" )
		{
			$this->toolbar = $mtz[ 'toolbar' ];
			unset( $mtz[ 'toolbar' ] );
			$json = cssEncode( $mtz );
		}
		$this->json = $json;

		$this->page                            = $_SERVER[ "PHP_SELF" ];
		$this->types[ "hidden" ]               = "{xtype: 'hidden'}";
		$this->types[ "show" ]                 = "{xtype: 'displayfield'}";
		$this->types[ "label" ]                = "{xtype: 'label', cls: 'x-form-item', anchor: '100%'}";
		$this->types[ "text" ]                 = "{xtype: 'textfield', anchor: '90%', allowBlank: true}";
		$this->types[ "textarea" ]             = "{xtype: 'textarea', anchor: '90%', allowBlank: true}";
		$this->types[ "memo" ]                 = "{xtype: 'htmleditor', enableLinks: false, enableSourceEdit: false, anchor: '90%', allowBlank: true}";
		$this->types[ "upperText" ]            = "{xtype: 'textfield', anchor: '90%', convertToUpperCase: true, allowBlank: true}";
		$this->types[ "lowerText" ]            = "{xtype: 'textfield', anchor: '90%', convertToLowerCase: true, allowBlank: true}";
		$this->types[ "upperFirstWordText" ]   = "{xtype: 'textfield', anchor: '90%', allowBlank: true, validator: 'vUFWText'}";
		$this->types[ "upperFirstLetterText" ] = "{xtype: 'textfield', anchor: '90%', allowBlank: true,validator: 'vUFText'}";
		$this->types[ "password" ]             = "{xtype: 'textfield', inputType: 'password', allowBlank: false}";
		$this->types[ "url" ]                  = "{xtype: 'textfield', anchor: '90%', vtype: 'url', allowBlank: true }";
		$this->types[ "email" ]                = "{xtype: 'textfield', anchor: '90%', vtype: 'email', allowBlank: true}";
		$this->types[ "number" ]               = "{xtype: 'numberfield', allowDecimals: true, allowBlank: true}";
		$this->types[ "integer" ]              = "{xtype: 'numberfield', allowDecimals: false, allowBlank: true}";
		$this->types[ "positive" ]             = "{xtype: 'numberfield', allowNegative: false, allowBlank: true}";
		$this->types[ "positiveInteger" ]      = "{xtype: 'numberfield', allowNegative: false, allowDecimals: false, allowBlank: true}";
		$this->types[ "integerPositive" ]      = "{xtype: 'numberfield', allowNegative: false, allowDecimals: false, allowBlank: true}";
		$this->types[ "positive" ]             = "{xtype: 'numberfield', allowNegative: false, allowBlank: true}";
		//$this->types["date"]="{xtype: 'datefield', dateFormat: 'd-m-y', format: 'd-m-y', altFormats: 'd/m/Y|j/n/Y|j/n/y|j/m/y|d/n/y|j/m/Y|d/m/Y|d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|d-m-Y', allowBlank: true, validateOnBlur: true, validationEvent: 'blur', validator: 'vDate'}";
		$this->types[ "date" ]                 = "{xtype: 'datefield', dateFormat: 'd-m-y', format: 'd-m-y', altFormats: 'd/m/Y|j/n/Y|j/n/y|j/m/y|d/n/y|j/m/Y|d/m/Y|d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|d-m-Y', allowBlank: true}";
		$this->types[ "datetime" ]             = "{xtype: 'datetime', dateFormat: 'd-m-y', hiddenFormat: 'c',dateConfig: { altFormats:'Y-m-d H:i:s|d-m-y'}, timeFormat: 'H:i', timeConfig: {width: '20px', allowBlank: true, increment:60}, format: 'd-m-y H:i', allowBlank: true}";
		$this->types[ "time" ]                 = "{xtype: 'timefield', format: 'H:i', allowBlank: true, width: 80}";
		$this->types[ "ip" ]                   = "{xtype: 'textfield', vtype:'vtIPAddress', minLength: 7, maxLength: 15, allowBlank: true}";
		$this->types[ "ncm" ]                  = "{xtype: 'textfield', minLength: 10, maxLength: 10, allowBlank: true, validator: 'vNCM'}";
		$this->types[ "plate" ]                = "{xtype: 'textfield', convertToUpperCase: true, vtype: 'vtplate', minLength: 7, maxLength: 7, allowBlank: true}";
		$this->types[ "checkbox" ]             = "{xtype: 'rcheckbox'}";
		$this->types[ "cpf" ]                  = "{xtype: 'textfield', vtype: 'vtCpf', minLength: 11, maxLength: 11, allowBlank: true}";
		$this->types[ "cnpj" ]                 = "{xtype: 'textfield', minLength: 14, maxLength: 14, allowBlank: true}";
		$this->types[ "combo" ]                = "{xtype: 'combo', anchor: '90%', forceSelection: true, selectOnFocus:true, typeAhead: true, triggerAction: 'all', mode: 'local', emptyText: '" . gT( "Selecione..." ) . "', valueField: 'id'}";
		$this->types[ "comboMultiSelection" ]  = "{xtype: 'superboxselect', allowQueryAll: 1, anchor: '90%', typeAhead: true, forceSelection: true, triggerAction: 'all', mode: 'local', emptyText: '" . gT( "Selecione um ou vários..." ) . "', valueField: 'id'}";

		// $this->types["container"]="{xtype: 'textfield', convertToUpperCase: true, vtype: 'vtcontainer', minLength: 10, maxLength: 11, allowBlank: true,  validateOnBlur: true, validationEvent: 'blur', validator: 'vCntr'}";
		$this->types[ "container" ] = "{xtype: 'textfield', convertToUpperCase: true, vtype: 'vtcontainer', minLength: 11, maxLength: 11, allowBlank: true}";
		$this->types[ "interpos" ]  = "{xtype: 'textfield', convertToUpperCase: true, vtype: 'vtinterpos', minLength: 7, maxLength: 11, allowBlank: true}";
		$this->types[ "file" ]      = "{xtype: 'fileuploadfield', anchor: '90%', emptyText: '" . gT( "Selecione um arquivo" ) . "', buttonText: '', buttonCfg: {iconCls: 'b2002_16'}}";
		$this->types[ "image" ]     = "{xtype: 'fileuploadfield', anchor: '90%', emptyText: '" . gT( "Selecione um arquivo" ) . "', buttonText: '', buttonCfg: {iconCls: 'b2002_16'}}";
	}
}

/**
 *  Class que cria um componente para grupo de botões
 * @package	gButtonGroup
 * @author	Giuliano Nascimento
 * @version	1.0 29-10-2009 16:13
 */
class g_Input extends gOutput
{
	public $senchaTitle = "";
	public $senchaHtml = "";
	public $senchaItems = "";
	public $senchaButtonsTop = "";
	public $senchaButtonsBottom = "";
	public $senchaItemsStyle = "";
	public $senchaInterface = "";
	public $senchaInstructions = "";
	public $senchaScroll = "both";


	// Uso nos formularios
	static $ondesktop = false;

	function __construct( $json )
	{
		$mtz             = cssDecode( $json );
//		echo "<pre>"; var_dump($json); exit;
		if($mtz[ 'jquery' ] <> "")
			$mtz[ 'jquery' ] = "false";
		$json            = cssEncode( $mtz );
		if ( $mtz[ 'title' ] <> '' )
			$this->title = $mtz[ 'title' ];
		if ( $mtz[ 'scroll' ] <> "" )
			$this->senchaScroll = $mtz[ 'scroll' ];

		parent::__construct( $json );
		if ( ( !$this->ondesktop ) && ( intval( $_SESSION[ "usrId" ] ) == 0 ) )
		{
			//========= Usuário não autenticado
			gMsg::alert( gT( "alert" ), gT( "logintimeout" ) );
			$this->gBody();
			exit;
		}
	}




	/** Monta todo o código de início da página
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 10:50
	 */
	function gBegin( )
	{
		// só executa este código uma vez!
		$sai = parent::gBegin();
		return ( $sai );
	}

	/** Monta todo o código de final da página
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 10:50
	 */
	function gEnd( )
	{
		$sai = '';
		// só executa este código uma vez!
		if ( !$this->statusEnd )
		{
			$sai = parent::gEnd();
		}
		return ( $sai );
	}

	/** Concui o objeto
	 * @author	Giuliano Nascimento
	 * @version	1.0 07-04-2009 10:50
	 */
	function __destruct( )
	{
		// checa qual o tipo de página a ser gerada (Web, PDF, XLS, etc.)
		// monta código de saída
		$this->gEnd();
		parent::__destruct();
	}

}
$device = $gDevice;
if ( ( ( $gOs == "ios" ) || ( $gOs == "android" ) ) && ( $gDevice == "mobile" ) )
	$device = "iphone";

$inc = $gPathDefault . "dev" . gBAR . strtolower( $device ) . gBAR . "gInput.php";
if ( file_exists( $inc ) )
{
	include_once $inc;
}
else
{
	$out = new g_Output();
	$out->gError( "Erro", "dispositivo de acesso ao sistema não encontrado: $device<br>inc: $inc" );
}

?>
