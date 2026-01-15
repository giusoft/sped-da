<?
class gCsv {

	function __construct(){
		$this->renderFile = 'file.csv';
		$this->renderType = 'CSV';
	}

	function __destruct(){}
	function begin(): void {
		$this->buffer = '';
	}

	function end(): void {
		$_ENV = $_SERVER;
		if (
			isset($_ENV[ 'HTTP_USER_AGENT' ])
			&& strpos( (string) $_ENV[ 'HTTP_USER_AGENT' ], 'MSIE 6')
		) {
			header( 'Content-type: application/' . $this->renderType );
			header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
			header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" );
			header( "Pragma: public" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		} elseif (
			isset($_ENV[ 'HTTP_USER_AGENT' ] )
			&& strpos( (string) $_ENV[ 'HTTP_USER_AGENT' ], 'MSIE 5.5')
		) {
			Header( 'Content-Type: application/dummy' );
		} else {
			Header( 'Content-Type: application/octet-stream' );
		}

		if (headers_sent()) {
			$this->gError( "Erro de download", 'Dados já foram enviados ao cliente, não é possível realizar o download.' );
		}
		Header( 'Content-Length: ' . strlen( (string) $this->buffer ) );
		Header( 'Content-disposition: attachment; filename=' . $this->renderFile );
		echo $this->buffer;
	}
	function msgTitle() {}
	function msgInfo(){}
	function msgSubTitle() {}
	function msgMiniTitle() {}
	function msgFilter() {}
	function msg() {}
	function out() {}
	function br() {}
	function hr() {}

	function small($txt)
	{
		return($txt);
	}

	function badge(string $txt): string
	{
		return(" ".$txt." ");
	}

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
		$removerCampos = $_REQUEST['gTableRemoveFields'];
		$newCols = [];
		$contador = count($fields);
		for ($a = 0; $a < $contador; $a++) {
			$achou = false;
			foreach ($removerCampos as $remover ) {
				if ($remover == $fields[$a]) {
					$achou = true;
				}
			}

			if (!$achou) {
				$newCols[]=$colMatrix[$a];
			}
		}
		return($this->tableRow($newCols, $style, $add, $event));
	}

	function tableLine(){}

	function tableRow($mtz, $c1,$c2,$c3,$c4): void {
		$cols = [];
		foreach ($mtz as $row) {
			if (str_starts_with((string) $row, "~")) {
				$colspan = substr((string) $row, 1, 1);
				if ((ord(substr((string) $row, 2, 1)) > 47) && (ord(substr((string) $row, 2, 1)) < 58)) {
					$colspan.=substr((string) $row, 2, 1);
					if ((ord(substr((string) $row, 3, 1)) > 47) && (ord(substr((string) $row, 3, 1)) < 58)) {
						$colspan.=substr((string) $row, 3, 1);
						$row = substr((string) $row, 4);
					} else {
						$row = substr((string) $row, 3);
					}
				} else {
					$row = substr((string) $row, 2);
				}
				$colspan = intval($colspan);
			}

			if (str_starts_with((string) $row, "->")) {
				$align = "R";
				$row = substr((string) $row, 2);
			}

			if (str_starts_with((string) $row, "<-")) {
				$align = "L";
				$row = substr((string) $row, 2);
			}

			if (str_starts_with((string) $row, "<>")) {
				$align = "C";
				$row = substr((string) $row, 2);
			}

			for ($c = 1; $c < intval($colspan); $c++) {
				$cols[] = '';
			}

			$row = html_entity_decode((string) $row);
			$row = mb_convert_encoding(str_replace("\n", ". ", $row), 'ISO-8859-1');
			if (
				strlen($row) == 8
				&& substr($row,2,1) === '-'
				&& substr($row,5,1) === '-'
			) {
				$row = gDBDate($row);
			}
			$cols[] = trim(strip_tags($row));

		}
		$this->buffer .= implode(";",$cols)."\n";
	}

	function tableEnd() {

	}

	function table() {}
}
?>
