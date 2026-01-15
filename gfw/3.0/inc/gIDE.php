<?
/** Este arquivo contém a aplicação de editor de código (IDE)
 *  de algum conteúdo
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */

$myself=$PHP_SELF;
$myself=explode("/",$myself);
$myself=$myself[count($myself)-1];

$gPathDefault=$_SERVER['DOCUMENT_ROOT']."/".substr($PHP_SELF,0,strrpos($PHP_SELF,'/'))."/";
$gIncludes="gOutput.php";
include_once $gPathDefault."gConf.php";
 
/** Classe responsável pela estrutura básica da apresentação visual
 * @package	gDefaultOutput
 * @author	giuliano
 * @version	1.0 07-04-2009 10:50
 */
class gIDE extends g_Output
{
	var $style=gIDE_HTML;
	var $file="";
	var $content="";
	var $title="";
	
	
	/** Monta todo o código HTML de cabeçalho da página Web
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function __construct($renderTo=gRENDER_REMOTE)
	{
		parent::__construct($renderTo);
	}	
	
	function setTitle($val)	{$this->title=$val;}
	function setStyle($val)	{$this->style=$val;}
	function setFile($val)	{$this->file=$val;}
	function setContent($val)	
	{
		if ($this->style==gIDE_AUTO)
		{
			$this->style=gIDE_PHP;
			$words=explode(" ",trim(strtoupper($val)));
			if (($words[0]=="SELECT") || ($words[0]=="INSERT")|| ($words[0]=="DELETE")|| ($words[0]=="UPDATE"))
			{
				$this->style=gIDE_SQL;
			}
			
		}
		if ($this->style==gIDE_SQL)
		{
			$val=$this->sqlFormat($val);
		}
		
		$this->content=$val;
	}
	
	/** Edita código
	 * @author	giuliano
	 * @version	1.0 07-04-2009 13:44
	 * @param content string Conteúdo
	 */
	function gCodeEditor($titulo,$conteudo)
	{
		global $http_lib;
		global $myself;
		
		$this->gOut($this->nl."<script language='Javascript' type='text/javascript' src='$http_lib".gVar("lib.editarea")."edit_area/edit_area_full.js'></script>".$this->nl);
		?>
		<script language='Javascript' type='text/javascript'>
	
		editAreaLoader.init({
			id: "gCodeEditor"	// id of the textarea to transform	
			,start_highlight: true	
			,font_size: "9"
			,font_family: "verdana, monospace"
			,language: "pt"
			,allow_resize: "y"
			,allow_toggle: false
			,word_wrap: true			
			,toolbar: "new_document, save, load, |, charmap, |, search, go_to_line, |, undo, redo, |, select_font, syntax_selection, |, change_smooth_selection, highlight, reset_highlight"
			,syntax_selection_allow: "css,html,js,php,xml,c,cpp,sql,basic"
			,load_callback: "gCodeLoad"
			,save_callback: "gCodeSave"
			,plugins: "charmap"
			,charmap_default: "arrows"
			<?			
			if ($this->style==gIDE_SQL) echo ',syntax: "sql"';
			if ($this->style==gIDE_CSS) echo ',syntax: "css"';
			if ($this->style==gIDE_PHP) echo ',syntax: "php"';
			if ($this->style==gIDE_HTML) echo ',syntax: "html"';
			?>
			
		});
		
		
		// callback functions
		function gCodeSave(id, content){
			alert("Here is the content of the EditArea '"+ id +"' as received by the save callback function:\n"+content);
		}
		
		function gCodeLoad(id){
			editAreaLoader.setValue(id, "The content is loaded from the load_callback function into EditArea");
		}
		
		function test_setSelectionRange(id){
			editAreaLoader.setSelectionRange(id, 100, 150);
		}
		
		function test_getSelectionRange(id){
			var sel =editAreaLoader.getSelectionRange(id);
			alert("start: "+sel["start"]+"\nend: "+sel["end"]); 
		}
		
		function test_setSelectedText(id){
			text= "[REPLACED SELECTION]"; 
			editAreaLoader.setSelectedText(id, text);
		}
		
		function test_getSelectedText(id){
			alert(editAreaLoader.getSelectedText(id)); 
		}
		
		function editAreaLoaded(id){
			if(id=="example_2")
			{
				open_file1();
				open_file2();
			}
		}
		
		function open_file1()
		{
			var new_file= {id: "to\\ Ã© # â¬ to", text: "$authors= array();\n$news= array();", syntax: 'php', title: 'beautiful title'};
			editAreaLoader.openFile('example_2', new_file);
		}
		
		function open_file2()
		{
			var new_file= {id: "Filename", text: "<a href=\"toto\">\n\tbouh\n</a>\n<!-- it's a comment -->", syntax: 'html'};
			editAreaLoader.openFile('example_2', new_file);
		}
		
		function close_file1()
		{
			editAreaLoader.closeFile('example_2', "to\\ Ã© # â¬ to");
		}
		
		function toogle_editable(id)
		{
			editAreaLoader.execCommand(id, 'set_editable', !editAreaLoader.execCommand(id, 'is_editable'));
		}
		</script>
		
		<?
		
		
		
		//$this->gOut("<script language='Javascript' type='text/javascript' src='$http_lib".gVar("lib.editarea")."gfw.js'></script>".$this->nl);
		$this->gOut("<b>$titulo</b><br>".$this->nl);
		$this->gOut("<form action='$myself' method='post'>".$this->nl);
		$this->gOut("<textarea id='gCodeEditor' name='gFileContent' style='width: 100%; height: 100%'>$conteudo</textarea>".$this->nl);
		$this->gOut("</form>".$this->nl);
		
		
	}
	
	/** Monta todo o IDE
	 * @author	giuliano
	 * @version	1.0 07-04-2009 13:44
	 * @param content string Conteúdo
	 */
	function gShowIDE()
	{
		$this->gCodeEditor($this->title,$this->content);
	}
	
	function __destruct()
	{
		parent::__destruct();
	}	
}

$out=new gIDE();

$out->setTitle($title);
$out->setStyle($style);
$out->setFile($file);
$out->setContent(urldecode($content));

$out->gShowIDE();
?>
