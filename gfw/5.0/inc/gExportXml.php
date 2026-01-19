<?
class gXml {

    public $n = "\n";
    public $pulmao = '';

	function __construct(){
		$this->renderFile = 'file.xml';
		$this->renderType = 'XML';
	}
	function __destruct(){}

	function begin(): void {
        $this->pulmao = '<?xml version="1.0" encoding="UTF-8"?>'.$this->n;
        $this->pulmao.= '<pagina>'.$this->n;

	}

	function end(): void {
        $this->pulmao.= '</pagina>'.$this->n;
		$_ENV = $_SERVER;
		if (isset( $_ENV[ 'HTTP_USER_AGENT' ])
			&& strpos( (string) $_ENV[ 'HTTP_USER_AGENT' ], 'MSIE 6' )
		) {
			header('Content-type: application/' . $this->renderType);
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT");
			header("Pragma: public");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		} elseif (isset( $_ENV[ 'HTTP_USER_AGENT' ] ) && strpos( (string) $_ENV[ 'HTTP_USER_AGENT' ], 'MSIE 5.5')) {
			Header( 'Content-Type: application/dummy' );
		} else {
			Header( 'Content-Type: application/octet-stream' );
		}

		if (headers_sent()) {
			$this->gError( "Erro de download", 'Dados já foram enviados ao cliente, não é possível realizar o download.' );
		}

		Header( 'Content-Length: ' . strlen( $this->pulmao ) );
        Header( 'Content-disposition: attachment; filename=' . $this->renderFile );
        echo $this->pulmao;
	}

	function msgTitle($msg): void {
        $this->pulmao.=tagMe('titulo', $msg).$this->n;
    }

	function msgSubTitle($msg): void {
        $this->pulmao.=tagMe('subTitulo', $msg).$this->n;
    }

	function msgMiniTitle($msg): void {
        $this->pulmao.=tagMe('miniTitulo', $msg).$this->n;
    }

	function msgFilter($msg): void {
        $this->pulmao.=tagMe('filtros', $msg).$this->n;
    }

	function msgInfo($msg): void {
        $this->pulmao.=tagMe('mensagemInformacao', $msg).$this->n;
    }

	function msgWarning($msg): void {
        $this->pulmao.=tagMe('mensagemAviso', $msg).$this->n;
    }

	function msgSuccess($msg): void {
        $this->pulmao.=tagMe('mensagemSucesso', $msg).$this->n;
    }

	function msgDanger($msg): void {
        $this->pulmao.=tagMe('mensagemGrave', $msg).$this->n;
    }

	function msg($msg): void {
        $this->pulmao.=tagMe('mensagem', $msg);
    }

    function out($content, $location = gLOC_INLINE, $indent = 0)
    {
        if ($location == gLOC_INLINE) {
            return($content);
        }

        return;
    }

    function label(string $txt): string
    {
        return($txt.' ');
    }

	function br(): string {
        return("<br />");
    }

	function hr() {}

	function small($txt){
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

	function tableBegin(): void {
        $this->pulmao.='<tabela>';
    }

    function tableTotal(){}
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
				$colspan=intval($colspan);
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
			//$row = html_entity_decode($row);
			//$row = utf8_decode(str_replace("\n", ". ", $row));
			if (strlen((string) $row)==8 && substr((string) $row,2,1) === '-' && substr((string) $row,5,1) === '-') {
				$row = gDBDate($row);
            }
            $row = str_ireplace("<BR>"," - ",$row);
            $row = str_ireplace("<BR/>"," - ",$row);
            $row = str_replace("&nbsp;"," ",$row);
            $cols[]=trim(strip_tags($row));
            //$cols[]=trim($row);
		}
        //$this->pulmao.= implode(";",$cols)."\n";
        $this->pulmao.='<linha>'.$this->n;
        foreach ($cols as $col) {
            if ($c1=="image") {
                $xxx = explode("~", $col);
                $img = tagMe("imagem", str_replace("/var/www/html/webcfc","https://webcfc.com.br",$xxx[0])).$this->n;
                $txt = tagMe("descricao", $xxx[1]).$this->n;
                $this->pulmao.=tagMe('coluna', $img.$txt);
            } else {
                $this->pulmao.=tagMe('coluna', $col);
            }

        }
        $this->pulmao.='</linha>'.$this->n;
	}

	function tableEnd(): void {
        $this->pulmao.='</tabela>';
	}

	function table() {}
}
