<?
class gXml {

    public $n = "\n";
    public $pulmao = '';

	function __construct(){
		$this->renderFile = 'file.xml';
		$this->renderType = 'XML';
	}
	function __destruct(){}

	function begin() {
        $this->pulmao = '<?xml version="1.0" encoding="UTF-8"?>'.$this->n;
        $this->pulmao.= '<pagina>'.$this->n;

	}

	function end() {
        $this->pulmao.= '</pagina>'.$this->n;
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
			$this->gError( "Erro de download", 'Dados já foram enviados ao cliente, não é possível realizar o download.' );
		Header( 'Content-Length: ' . strlen( $this->pulmao ) );
        Header( 'Content-disposition: attachment; filename=' . $this->renderFile );
        echo $this->pulmao;
	}

	function msgJumbo(){}
	function msgError(){}
	function msgMaxiTitle(){}
	function msgFooter(){}
	function msgDefault(){}
	function msgLead(){}
	function msgBlockquote(){}
	function msgForTitles(){}

	function msgTitle($msg) {
        $this->pulmao.=tagMe('titulo', $msg).$this->n;
    }
	function msgSubTitle($msg) {
        $this->pulmao.=tagMe('subTitulo', $msg).$this->n;
    }
	function msgMiniTitle($msg) {
        $this->pulmao.=tagMe('miniTitulo', $msg).$this->n;
    }
	function msgFilter($msg) {
        $this->pulmao.=tagMe('filtros', $msg).$this->n;
    }
	function msgInfo($msg) {
        $this->pulmao.=tagMe('mensagemInformacao', $msg).$this->n;
    }
	function msgWarning($msg) {
        $this->pulmao.=tagMe('mensagemAviso', $msg).$this->n;
    }
	function msgSuccess($msg) {
        $this->pulmao.=tagMe('mensagemSucesso', $msg).$this->n;
    }
	function msgDanger($msg) {
        $this->pulmao.=tagMe('mensagemGrave', $msg).$this->n;
    }
	function msg($msg) {
        $this->pulmao.=tagMe('mensagem', $msg);
    }
	function msgAlert($msg)
	{
		$this->pulmao.=tagMe('alerta', $msg);
	}

    function out($content, $location = gLOC_INLINE, $indent = 0)
    {
        if ($location == gLOC_INLINE)
        {
            return($content);
        }
    }
    function label($txt)
    {
        return($txt.' ');
    }
	function br() {
        return("<br />");
    }
	function hr() {}
	function small($txt){
        return($txt);
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
        $this->pulmao.='<tabela>';
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
			//$row = html_entity_decode($row);
			//$row = utf8_decode(str_replace("\n", ". ", $row));
			if (strlen($row)==8 && substr($row,2,1)=='-' && substr($row,5,1)=='-')
			{
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
        foreach ($cols as $col)
        {
            if ($c1=="image")
            {
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

	function tableEnd() {
        $this->pulmao.='</tabela>';
	}

	function table() {}
}
