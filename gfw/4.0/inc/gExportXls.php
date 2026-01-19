<?
class gXls {
	public $title="";
	public $subtitle="";
	public $filter="";

	function __construct(){
		$this->renderFile = 'file.xlsx';
		$this->renderType = 'XLS';
	}
	function __destruct(){}

	function begin() {
		$this->buffer = '';
	}

	function obtemColuna($C)
	{
		if ($C<=25)
		{
			$chr = 65+$C;
			$col=chr($chr);
		}
		elseif($C<51){
			$chr = 65+($C-25);
			$col="A".chr($chr);
		}
		else{
			$chr = 65+($C-50);
			$col="B".chr($chr);
		}

		return($col);
	}

	function obtemCelula($C,$L)
	{
		$C = intval($C);
		$L = intval($L);

		$col = $this->obtemColuna($C);
		$lin = $L;

		//if($lin==5)
		//echo "Linha: $lin Col:$col / $C  =".$lin.$col."   => $chr<BR>";

		return($col.$lin);
	}
	function end() {

		global $gPathLib, $usrName;

		include_once($gPathLib.'/phpexcel/Classes/PHPExcel.php');
		$objPHPExcel = new PHPExcel();
		PHPExcel_Settings::setLocale('pt_br');
		$objPHPExcel->getProperties()->setCreator($usrName);
		$objPHPExcel->getProperties()->setLastModifiedBy($usrName);
		$objPHPExcel->getProperties()->setTitle($this->title);
		$objPHPExcel->getProperties()->setSubject($this->subtitle);

		$objPHPExcel->getActiveSheet()->setCellValue('A1',  $this->filter);
		$objPHPExcel->getActiveSheet()->setCellValue('B1', '');
		$objPHPExcel->getActiveSheet()->setCellValue('C1', '');
		$objPHPExcel->getActiveSheet()->setCellValue('D1', '');
		$objPHPExcel->getActiveSheet()->setCellValue('E1', '');
		$objPHPExcel->getActiveSheet()->mergeCells("A1:B1");
		$objPHPExcel->getActiveSheet()->mergeCells("A1:C1");
		$objPHPExcel->getActiveSheet()->mergeCells("A1:D1");
		$objPHPExcel->getActiveSheet()->mergeCells("A1:E1");
		$objPHPExcel->getActiveSheet()->mergeCells("A1:F1");
		//echo "<textarea>".$this->buffer."</textarea>";exit;

		$linhas = explode("\n", $this->buffer);
		foreach ($linhas as $L=>$linha)
		{
			// Solução temporaria, reimplementar melhor.
			if (strstr($linha, "~") && count(explode(";", $linha))>2)
			{
				$cols=explode(";", $linha);
				$replace="";
				$newReplace="";
				foreach ($cols as $col)
				{
					$newCols=array();
					if (strstr($col, "~"))
					{
						$qtd=intval(str_replace("~", "", $col));
						$qtd=$qtd-2;
						for ($i=0; $i<$qtd; $i++)
						{
							$newCols[]="";
						}
						$newCols[]="";
						$newReplace=implode(";", $newCols);
						$replace=$col;
					}
				}
				$linha=str_replace($replace, $newReplace, $linha);
			}
			// ---------------------------------------------------
			// ---------------------------------------------------
			// Código original.

			$colunas = explode(';',$linha);
			foreach ($colunas as $C=>$coluna)
			{
				$primeiraCelula = "";
				if(substr($coluna,0,1) == "~")
				{
					$colspan = (int) str_replace("~", "", $coluna);
					for ($c=1; $c<intval($colspan); $c++)
					{
						$celula = $this->obtemCelula($C,$L+2);


						$objPHPExcel->getActiveSheet()->setCellValue($celula,  "");
						$objPHPExcel->getActiveSheet()->getStyle($celula)->getAlignment()->setWrapText(true);

						if($primeiraCelula=="")
							$primeiraCelula = $celula;
						else
						{
							$objPHPExcel->getActiveSheet()->mergeCells($primeiraCelula.":".$celula);
						}
					}
				}
				else
				{
					if ($L==0)
					{
						// Células com largura auto-ajustável
						//$this->obtemColuna($C);
						$objPHPExcel->getActiveSheet()->getColumnDimension($this->obtemColuna($C))->setAutoSize(true);
					}

					$celula = $this->obtemCelula($C,$L+2);
					//$objPHPExcel->getActiveSheet()->getStyle('B2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
					$objPHPExcel->getActiveSheet()->setCellValue($celula,  $coluna);
					$objPHPExcel->getActiveSheet()->getStyle($celula)->getAlignment()->setWrapText(true);
				}
			}
		}
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		ob_end_clean();
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="file.xlsx"');
		header('Cache-Control: max-age=0');


		$objWriter->save('php://output');


		// $HTTP_ENV_VARS = $_SERVER;
		// if ( isset( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ] ) and strpos( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ], 'MSIE 6' ) )
		// {
		// 	header( 'Content-type: application/' . $this->renderType );
		// 	header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		// 	header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" );
		// 	header( "Pragma: public" );
		// 	header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		// }
		// elseif ( isset( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ] ) and strpos( $HTTP_ENV_VARS[ 'HTTP_USER_AGENT' ], 'MSIE 5.5' ) )
		// 	Header( 'Content-Type: application/dummy' );
		// else
		// 	Header( 'Content-Type: application/octet-stream' );
		// if ( headers_sent() )
		// 	$this->gError( "Erro de download", 'Dados já foram enviados ao cliente, não é possível realizar o download.' );
		// Header( 'Content-Length: ' . strlen( $this->buffer ) );
		// Header( 'Content-disposition: attachment; filename=' . $this->renderFile );

		// echo file_get_contents($file);
	}

	function msg() {}
	function msgInfo(){}
	function msgJumbo(){}
	function msgError(){}
	function msgMaxiTitle(){}
	function msgFooter(){}
	function msgDefault(){}
	function msgLead(){}
	function msgBlockquote(){}
	function msgAlert(){}
	function msgMiniTitle() {}
	function msgForTitles(){}
	function msgDanger(){}
	function msgWarning(){}
	function msgSuccess(){}

	function msgTitle($txt) {
		$this->title = $txt;
	}

	function msgSubTitle($txt) {
		$this->subtitle = $txt;
	}

	function msgFilter($txt) {
		$this->filter = $txt;
	}

	function out() {}
	function br() {}
	function hr() {}
	function small($txt) {return($txt);}
	function badge($txt){ return(" ".$txt." ");}
	function button() {}
	function ul() {}
	function icon() {}
	function dropdown() {}
	function nav() {}
	function addJavascript() {}
	function modal() {}
	function tableBegin() {}

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
    function tableTotal()
    {
    }
	function tableRow($mtz, $c1,$c2,$c3,$c4) {
		$cols = '';
		foreach ($mtz as $row)
		{
			$colspan="";
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
				$row = str_replace(",",".",str_replace(".","",substr($row, 2)));
				//$row = substr($row, 2);
			}
			if (substr($row, 0, 2) == "<-") {
				$align = "L";
				$row = substr($row, 2)."  ";
			}
			if (substr($row, 0, 2) == "<>") {
				$align = "C";
				$row = substr($row, 2);
			}
			//for ($c=1; $c<intval($colspan); $c++)
			//{
			if((int) $colspan)
				$cols[] = '~'.intval($colspan);
			//}

			$row = limpaString(html_entity_decode($row));
			$row = utf8_decode(str_replace("\n", ". ", $row));
			$row = limpaString(strip_tags($row));

			$cols[]=($row);

		}
		$this->buffer.= implode(";",$cols)."\n";
	}

	function tableEnd() {

	}

	function table() {}

	public function rowTags() {}
	public function label($txt) {
		return($txt);
	}
}
?>
