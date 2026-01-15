<?
class gCsv {

	function __construct(){
		$this->renderFile = 'file.csv';
		$this->renderType = 'CSV';
	}
	function __destruct(){}

	function begin() {
		$this->buffer = '';
	}

	function end() {
		$HTTP_ENV_VARS = $_SERVER;
		if ( isset( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ] ) and strpos( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ], 'MSIE 6' ) )
		{
			header( 'Content-type: application/' . $this->renderType );
			header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
			header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" );
			header( "Pragma: public" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		}
		elseif ( isset( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ] ) and strpos( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ], 'MSIE 5.5' ) )
			Header( 'Content-Type: application/dummy' );
		else
			Header( 'Content-Type: application/octet-stream' );
		if ( headers_sent() )
			$this->msgAlert( "Erro de download", 'Dados já foram enviados ao cliente, não é possível realizar o download.' );
		Header( 'Content-Length: ' . strlen( $this->buffer ) );
		Header( 'Content-disposition: attachment; filename=' . $this->renderFile );
		echo $this->buffer;
	}
	function msgTitle() {}
	function msgInfo(){}
	function msgSubTitle() {}
	function msgMiniTitle() {}
	function msgFilter() {}
	function msg() {}
	function msgJumbo(){}
	function msgError(){}
	function msgMaxiTitle(){}
	function msgFooter(){}
	function msgDefault(){}
	function msgLead(){}
	function msgBlockquote(){}

	function msgAlert($msg)
	{
		$msg = str_replace('•','-',$msg);
		$this->msg($msg);
	}

	function msgForTitles($msg, $align='L', $size=9, $type='', $height=4)
	{
		$msg = str_replace('•','-',$msg);
		if (!$this->started)
		{
			$this->started=true;
			$this->AddPage();
		}
		$this->SetFont(gVar("pdf.font"),$type,$size);
		//Cell(float w [, float h [, string txt [, mixed border [, int ln [, string align [, boolean fill [, mixed link]]]]]]])
		$this->Cell(0,$height,$msg,0,1, $align);
	}

	function msgDanger($msg)
	{
		$msg = str_replace('•','-',$msg);
		$fonttam=$this->detailFontSize;
		$this->filter=$msg;
		//$this->SetTitle($this->title, $this->subtitle, $this->filter);
		$align=$this->defaultAlign;
		if (!$this->firstFilter)
		{
			$this->msgForTitles($msg, $align, $fonttam+2 , '', 6);
		}
	}

	function msgWarning($msg)
	{
		$msg = str_replace('•','-',$msg);
		$fonttam=$this->detailFontSize;
		$this->filter=$msg;
		//$this->SetTitle($this->title, $this->subtitle, $this->filter);
		$align=$this->defaultAlign;
		if (!$this->firstFilter)
		{
			$this->msgForTitles($msg, $align, $fonttam+2 , '', 6);
		}
	}

	function msgSuccess($msg)
	{
		$msg = str_replace('•','-',$msg);
		$fonttam=$this->detailFontSize;
		$this->filter=$msg;
		//$this->SetTitle($this->title, $this->subtitle, $this->filter);
		$align=$this->defaultAlign;
		if (!$this->firstFilter)
		{
			$this->msgForTitles($msg, $align, $fonttam+2 , '', 6);
		}
	}

	

	function out() {}
	function br() {}
	function hr() {}
	function small($txt){ return($txt);}
	function badge($txt){ return(" ".$txt." ");}
	function button() {}
	function ul() {}
	function icon() {}
	function dropdown() {}
	function nav() {}
	function addJavascript() {}
	function modal() {}

	function tableBegin() {}
    function tableTotal() {}

	/**
	 * Monta estrutura de uma linha da tabela, removendo os campos informados no $_REQUEST['gTableRemoveFields']
	 * @author	giuliano
	 * @version	4.0 01-12-2013 10:50
	 * @param string $fields Array contendo o nome/índice dos campos/colunas
	 * @param string $colMatrix Array contendo as colunas
	 * @param string $style Estilo da linha
	 * @param string $add Parametros adicionais
	 * @param string $event Adicionar algum tratamento de evento via javascript
	 * @return type
	 */
	function removeFieldsTableRow($fields, $colMatrix, $style = "detail", $add = "", $event = "")
	{
		$mtz = "";
		$removerCampos = $_REQUEST['gTableRemoveFields'];
		$newCols = "";
		for ($a = 0; $a<count($fields); $a++)
		{
			$achou = false;
			foreach ($removerCampos as $remover )
			{
				if ($remover == $fields[$a])
					$achou = true;
			}
			if (!$achou)
				$newCols[]=$colMatrix[$a];
		}
		return($this->tableRow($newCols, $style, $add, $event));
	}

	function tableLine(){}

	function tableRow($mtz, $c1,$c2,$c3,$c4) {
		$cols = '';
		foreach ($mtz as $row)
		{
			if (substr($row, 0, 1) == "~") {
				$colspan = substr($row, 1, 1);
				if ((ord(substr($row, 2, 1)) > 47) && (ord(substr($row, 2, 1)) < 58)) {
					$colspan.=substr($row, 2, 1);
					if ((ord(substr($row, 3, 1)) > 47) && (ord(substr($row, 3, 1)) < 58)) {
						$colspan.=substr($row, 3, 1);
						$row = substr($row, 4);
					} else {
						$row = substr($row, 3);
					}
				} else {
					$row = substr($row, 2);
				}
				$colspan=intval($colspan);
			}
			if (substr($row, 0, 2) == "->") {
				$align = "R";
				$row = substr($row, 2);
			}
			if (substr($row, 0, 2) == "<-") {
				$align = "L";
				$row = substr($row, 2);
			}
			if (substr($row, 0, 2) == "<>") {
				$align = "C";
				$row = substr($row, 2);
			}
			for ($c=1; $c<intval($colspan); $c++)
			{
				$cols[] = '';
			}
			$row = html_entity_decode($row);
			$row = utf8_decode(str_replace("\n", ". ", $row));
			if (strlen($row)==8 && substr($row,2,1)=='-' && substr($row,5,1)=='-')
			{
				$row = gDBDate($row);
			}
			$cols[]=trim(strip_tags($row));

		}
		$this->buffer.= implode(";",$cols)."\n";
	}

	function tableEnd() {

	}

	function table() {}

	public function label($conteudo) {
		return $conteudo;
	}
}
?>
