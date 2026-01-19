<?
class gDoc {

	function __construct(){
		$this->renderFile = 'file.doc';
		$this->renderType = 'DOC';
	}
	function __destruct(){}

	function begin() {
		$this->buffer = "<!DOCTYPE html>\n<html><body>";
		$this->buffer.='<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>'.$this->n;

	}

	function end() {
		$this->buffer.='</body></html>'.$this->n;
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
			$this->msg( "Erro de download", 'Dados já foram enviados ao cliente, não é possível realizar o download.' );
		Header( 'Content-Length: ' . strlen( $this->buffer ) );
		Header( 'Content-disposition: attachment; filename=' . $this->renderFile );

		echo $this->buffer;

	}

	function msgInfo(){}
	function msgJumbo(){}
	function msgError(){}
	function msgMaxiTitle(){}
	function msgFooter(){}
	function msgDefault(){}
	function msgLead(){}
	function msgBlockquote(){}
	function msgAlert(){}
	function msgForTitles(){}
	function msgDanger(){}
	function msgWarning(){}
	function msgSuccess(){}

	function msgTitle($txt) {
		$this->out('<h1>'.$txt.'</h1>');
	}

	function msgSubTitle($txt) {
		$this->out('<h2>'.$txt.'</h2>');
	}

	function msgMiniTitle($txt) {
		$this->out('<h3>'.$txt.'</h3>');
	}

	function msgFilter($txt) {
		$this->out('<i>'.$txt.'</i>');
	}

	function msg($txt) {
		$this->out($txt);
	}

	function out($txt) {
		$this->buffer.=autoencode($txt);
	}
	function br($n = 1) {
		for ($a=0; $a<$n; $a++)
			$this->out('<br>');
	}
	function hr() {
		$this->out('<hr>');
	}
	function small($txt) {
		$this->out('<small>'.$txt.'</small>');
	}
	function badge($txt){ return(" ".$txt." ");}
	function button() {}
	function ul() {}
	function icon() {}
	function dropdown() {}
	function nav() {}
	function addJavascript() {}
	function modal() {}

	function tableBegin() {
		$this->buffer.='<table>';
	}
    function tableTotal()
    {
    }

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

	function  tableLine(){}

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
			$row = html_entity_decode(str_ireplace("<br>","\n",$row));
			$row = autoencode(str_replace("\n", "<br>", $row));
			$row = strip_tags($row);
			if ($colspan<=1)
				$cols[]='<td>'.$row.'</td>';
			else
				$cols[]='<td colspan="'.$colspan.'">'.$row.'</td>';

		}
		$this->buffer.= '<tr>'.implode("",$cols)."</tr>\n";
	}

	function tableEnd() {
		$this->buffer.='</table>';
	}

	function table() {}

	public function label($conteudo) {
		return $conteudo;
	}
}
?>
