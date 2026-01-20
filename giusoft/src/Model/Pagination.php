<?
class Pagination
{
	public $numero_pagina;
	public $numero_registro_por_pagina;
	public $total_paginas;
	public $total_rs;
	public $iniciar;
	public $proxima_pagina;
	public $anterior_pagina;


	public function controlarQuantidadePaginas($sql, $qtdMinimaPaginas)
	{
		$this->qtdMinimaPaginas = $qtdMinimaPaginas;
		$totalRegistros = 0;
		if ($_REQUEST["gPagination"] >= 10 || !$qtdMinimaPaginas) {
			$this->qtdMinimaPaginas = 0;
			$totalRegistros = dbFastQuery("SELECT COUNT(id) ttl FROM ({$sql}) H");
		}

		return $totalRegistros;
	}


	public function addPagination($rs, $por_pagina)
	{
		$this->total_rs = $rs[0]['ttl'];
		$this->numero_registro_por_pagina = $por_pagina;

		if (isset($_REQUEST["gPagination"]))
		{
			$this->numero_pagina=intval($_REQUEST["gPagination"]);
		} else
		{
			$this->numero_pagina=1;
		}
		if ($this->numero_pagina>1)
		{
			$this->anterior_pagina=$this->numero_pagina-1;
		}
		if ($this->numero_pagina<$this->total_rs)
		{
			$this->proxima_pagina=$this->numero_pagina+1;
		}

		$this->total_paginas = $this->qtdMinimaPaginas;
		if (!$this->qtdMinimaPaginas) {
			$this->total_paginas = ceil($this->total_rs / $this->numero_registro_por_pagina);
		}

		$this->iniciar = ($this->numero_pagina-1) * $this->numero_registro_por_pagina;

		if ($this->iniciar<0)
		{
			$this->iniciar=0;
		}
		return ($this);
	}

	public function render($json='{}')
	{
		global $o;
		if (
				$_REQUEST['gPDF']==0 &&
				$_REQUEST['gXLS']==0 &&
				$_REQUEST['gDOC']==0 &&
				$_REQUEST['gCSV']==0
			)
		{
			$jarr=cssDecode($json);
			$id=isset($jarr["id"])
					? $jarr["id"]
					: "g";

			$size=isset($jarr["size"])
				? $jarr["size"]
				: "sm";

			$style=isset($jarr["style"])
				? $jarr["style"]
				: "";
			if ($this->total_rs>0 || $this->qtdMinimaPaginas) {
				$html="<nav aria-label='Page navigation {$id}-nav' style={$style}>";
				$html.="<ul class='pagination pagination-{$size}'>";
				$frm.="<form action='' id='{$id}-formRequest' method='POST'>";
				foreach ($_REQUEST as $key => $value) {
					$frm .= "<input type='hidden' name='{$key}' value='{$value}'/>";
				}

				$frm.="<input type='hidden' name='gPagination' id='{$id}-gPagination' value=''/>";
				$frm.="</form>";
				$html.=$frm;
				if ($this->numero_pagina==1)
				{
					$html.="<li style='cursor:pointer;' class='page-item disabled'><a class='page-link' href='javascript:;'>&laquo;</a></li>";
				} else
				{
					$rota=$o->page."&gPagination=".$this->anterior_pagina;
					$html.="<li class='page-item'><a id='{$id}-btnAnterior' class='page-link' style='cursor:pointer;'>&laquo;</a></li>";
					$js="$('#{$id}-btnAnterior').on('click', function () {
						$('#{$id}-formRequest').attr('action', '".$rota."');
						$('#{$id}-gPagination').val('".$this->anterior_pagina."');
						$('#{$id}-formRequest').submit();
					})";
					$o->addJavascript($js);
				}

				if ($this->numero_pagina-5>0)
				{
					$iniciar=($this->numero_pagina-5);
				} else
				{
					$iniciar=1;
				}
				$finalizar=$this->numero_pagina+5;
				$cnt=0;
				for ($i=$iniciar; $i<=$this->total_paginas; $i++)
				{
					  $cnt++;
					  $rota=$o->page."&gPagination=".$i;
					  if ($i==$this->numero_pagina)
					  {
					  		$html.='<li class="page-item active"><a id="'.$id.'-btnPagination'.$i.'" class="page-link">'.$i.'</a></li>';
					  } else
					  {
					  		$html.='<li style="cursor:pointer;" class="page-item"><a id="'.$id.'-btnPagination'.$i.'" class="page-link">'.$i.'</a></li>';
					  }

					  $js="$('#{$id}-btnPagination".$i."').on('click', function () {
								$('#{$id}-formRequest').attr('action', '".$rota."');
								$('#{$id}-gPagination').val('".$i."');
								$('#{$id}-formRequest').submit();
							})";
					  $o->addJavascript($js);
					  if ($cnt==10)
					  {
					  		break;
					  }
				}

				if ($this->numero_pagina==$this->total_paginas)
				{
					$html.="<li class='page-item disabled'><a class='page-link' href='javascript:;'>&raquo;</a></li>";
				} else
				{
					$rota=$o->page."&gPagination=".$this->proxima_pagina;
					$html.="<li class='page-item'><a class='page-link' id='{$id}-btnProximo'>&raquo;</a></li>";
					$js="$('#{$id}-btnProximo').on('click', function () {
						$('#{$id}-formRequest').attr('action', '".$rota."');
						$('#{$id}-gPagination').val('".$this->proxima_pagina."');
						$('#{$id}-formRequest').submit();
					})";
					$o->addJavascript($js);
				}
				$html.="</ul>";
				$html.="</nav>";
				return ($html);
			}
		}
	}
}