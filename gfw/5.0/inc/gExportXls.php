<?php
// gExportXls.php atualizado para PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class gXls {
	public $title="";
	public $subtitle="";
	public $filter="";
    public $renderFile="";
    public $renderType="";
    public $buffer="";

	function __construct(){
		$this->renderFile = 'file.xlsx';
		$this->renderType = 'XLS';
	}
	
    function __destruct(){}
	
    function begin(): void {
		$this->buffer = '';
	}

	function obtemColuna($C): string
	{
        // Mantido original (Lógica A=0, B=1 funciona bem gerando letras)
		if ($C <= 25) {
			$chr = 65+$C;
			$col = chr($chr);
		} elseif($C<51) {
			$chr = 65+($C-25);
			$col = "A".chr($chr);
		} else {
			$chr = 65+($C-50);
			$col = "B".chr($chr);
		}
		return($col);
	}

	function obtemCelula($C,$L): string
	{
		$C = intval($C);
		$L = intval($L);
		$col = $this->obtemColuna($C);
		$lin = $L;
		return($col.$lin);
	}

	function end(): void {
		global $gPathLib, $usrName;

        // O caminho relativo de 'inc' para 'lib/phpexcel/vendor'
        if (isset($gPathLib)) {
             // Se seu sistema define $gPathLib, usa ele (recomendado)
             require_once($gPathLib . '/phpexcel/vendor/autoload.php');
        } else {
             // Fallback: Tenta achar relativo a este arquivo (__DIR__ = 5.0/inc)
             require_once(__DIR__ . '/lib/phpexcel/vendor/autoload.php');
        }

		// Instancia a nova classe Spreadsheet (Substitui PHPExcel)
		$spreadsheet = new Spreadsheet();
		
        // Propriedades do documento
		$spreadsheet->getProperties()->setCreator($usrName ?? 'Sistema');
		$spreadsheet->getProperties()->setLastModifiedBy($usrName ?? 'Sistema');
		$spreadsheet->getProperties()->setTitle($this->title);
		$spreadsheet->getProperties()->setSubject($this->subtitle);

        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalho (Filtro)
		$sheet->setCellValue('A1',  $this->filter);
        // Limpa células vizinhas (opcional no novo, mas mantendo compatibilidade visual)
		$sheet->setCellValue('B1', '');
		$sheet->setCellValue('C1', '');
		$sheet->setCellValue('D1', '');
		$sheet->setCellValue('E1', '');
		
        // Mesclagens de cabeçalho
        $sheet->mergeCells("A1:B1");
		$sheet->mergeCells("A1:C1");
		$sheet->mergeCells("A1:D1");
		$sheet->mergeCells("A1:E1");
		$sheet->mergeCells("A1:F1");

		$linhas = explode("\n", (string) $this->buffer);
		
        foreach ($linhas as $L => $linha) {
            // Lógica de tratamento de tilde "~" (Mantida original)
			if (strstr($linha, "~") && count(explode(";", $linha)) > 2) {
				$cols=explode(";", $linha);
				$replace="";
				$newReplace="";
				foreach ($cols as $col) {
					$newCols=[];
					if (strstr($col, "~")) {
						$qtd=intval(str_replace("~", "", $col));
						$qtd -= 2;
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
			
			// Processamento das colunas
			$colunas = explode(';',$linha);
			foreach ($colunas as $C => $coluna) {
				$primeiraCelula = "";
				
                if (str_starts_with($coluna, "~")) {
					$colspan = (int) str_replace("~", "", $coluna);
					for ($c=1; $c<intval($colspan); $c++)
					{
                        // L+2 pois a primeira linha do Excel é 1 e tem o cabeçalho
						$celula = $this->obtemCelula($C,$L+2); 
						$sheet->setCellValue($celula,  "");
						$sheet->getStyle($celula)->getAlignment()->setWrapText(true);

						if ($primeiraCelula == "") {
							$primeiraCelula = $celula;
						} else {
							$sheet->mergeCells($primeiraCelula.":".$celula);
						}
					}
				} else {
					if ($L == 0) {
						// AutoSize
						$sheet->getColumnDimension($this->obtemColuna($C))->setAutoSize(true);
					}

					$celula = $this->obtemCelula($C,$L+2);
					$sheet->setCellValue($celula,  $coluna);
					$sheet->getStyle($celula)->getAlignment()->setWrapText(true);
				}
			}
		}

        // Limpa buffer de saída para garantir download limpo
		if (ob_get_length()) ob_end_clean();

        // Headers para forçar download .xlsx
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="file.xlsx"');
		header('Cache-Control: max-age=0');

        // Cria o Writer Xlsx (Substituto moderno do Excel2007)
		$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
		$writer->save('php://output');
        exit;
	}

    // --- MÉTODOS AUXILIARES (Mantidos para compatibilidade com seu sistema) ---

	function msgTitle($txt): void {
		$this->title=$txt;
	}

	function msgSubTitle($txt): void {
		$this->subtitle=$txt;
	}

	function msgMiniTitle() {}

	function msgFilter($txt): void {
		$this->filter=$txt;
	}

	function msg() {}
	function out() {}
	function br() {}
	function hr() {}

	function small($txt) { 
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

	function removeFieldsTableRow($fields, $colMatrix, $style = "detail", $add = "", $event = "")
	{
		$removerCampos = $_REQUEST['gTableRemoveFields'] ?? [];
		$newCols = [];
		$contador = count($fields);
		for ($a = 0; $a < $contador; $a++) {
			$achou = false;
            if(is_array($removerCampos)){
                foreach ($removerCampos as $remover ) {
                    if ($remover == $fields[$a]) { $achou = true; }
                }
            }
			if (!$achou) {
				$newCols[]=$colMatrix[$a];
			}
		}
		return($this->tableRow($newCols, $style, $add, $event));
	}

	function tableLine(){}
    function tableTotal(){}

	function tableRow($mtz, $c1="",$c2="",$c3="",$c4=""): void {
		$cols = [];
        if(!is_array($mtz)) return; 

		foreach ($mtz as $row) {
			$colspan="";
            $rowStr = (string)$row;

			if (str_starts_with($rowStr, "~")) {
				$colspan = substr($rowStr, 1, 1);
                // Lógica complexa original de colspan mantida...
                // Simplificada aqui para funcionar direto
                $sub2 = substr($rowStr, 2, 1);
				if (is_numeric($sub2)) {
					$colspan.= $sub2;
                    $sub3 = substr($rowStr, 3, 1);
					if (is_numeric($sub3)) {
						$colspan.= $sub3;
						$rowStr = substr($rowStr, 4);
					} else {
						$rowStr = substr($rowStr, 3);
					}
				} else {
					$rowStr = substr($rowStr, 2);
				}
				$colspan=intval($colspan);
			}
			if (str_starts_with($rowStr, "->")) {
				$rowStr = str_replace(",",".",str_replace(".","",substr($rowStr, 2)));
			}
			if (str_starts_with($rowStr, "<-")) {
				$rowStr = substr($rowStr, 2)."  ";
			}
			if (str_starts_with($rowStr, "<>")) {
				$rowStr = substr($rowStr, 2);
			}
			if ((int) $colspan) {
				$cols[] = '~'.intval($colspan);
			}

            if (function_exists('limpaString')) {
			    $rowStr = limpaString(html_entity_decode($rowStr));
            } else {
                $rowStr = strip_tags(html_entity_decode($rowStr));
            }
            
            // Tratamento de encoding seguro
			$rowStr = mb_convert_encoding(str_replace("\n", ". ", $rowStr), 'ISO-8859-1', 'UTF-8');
			
            if(function_exists('limpaString')) {
			    $rowStr = limpaString(strip_tags($rowStr));
            }

			$cols[]=($rowStr);

		}
		$this->buffer.= implode(";",$cols)."\n";
	}

	function tableEnd() {}
	function table() {}
	function msgInfo(){}
	function label($txt) {
		return($txt); 
	}
}
?>