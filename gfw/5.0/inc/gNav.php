<?php

/* 
 * Classe para criar menu de dinamicamente
 */

class gNav{

	public $format;
	public $navDirection;
	public $comboItems;
	protected $dropdown;
	protected $status;
	protected $force_icon_title = false;

	/**
	 * Construtor da classe
	 *
	 * @param type $id
	 */
	public function __construct(protected $id = 0, protected $type = 'horizontal')
	{
		$this->id = $id;
		$this->dropdown = false;
		$this->type = $type;
		$this->status = 'active';
	}


	/**
	 * Altera valor de uma variavel de configuracao
	 * @param string nome da variaval
	 * @param type valor para variavel
	 *
	 */
	public function setConfig($var,$value): void
	{
		$this->{$var} = $value;
	}


	/**
	 * Converte matriz para o formato de atributos html
	 * @param
	 */
	protected function toAttr($data)
	{
		if (!$data) {
			return;
		}

		$r = '';
		foreach($data as $attr => $value){
			$r .= ' '.$attr.'="'.$value.'"';
		}
		return $r;
	}


	/**
	 * Cria a tag html <a> para ser utilizada no menu
	 *
	 * @param array $data
	 * @return string tag em html
	 */
	protected function navAnchor($data, $params = [], $caret = false)
	{
		if (!$data) {
			return;
		}

		extract($data);

		$target = !empty($target)? ' target="'.$target.'"' : '';
		$icon = !empty($icon)? '<span class="fa fa-'.$icon.'"></span> ' : '';
		$caret = $caret? ' <span class="caret"></span>' : '';

		if (!$this->force_icon_title) {
			$title = ($show_title) ? $title : '';
			$icon = ($show_icon) ? $icon : '';
		}

		return '<a href="'.$anchor.'"'.$target.$this->toAttr($params).'>'.$icon.$title.$caret.'</a>';
	}


	/**
	 * Formata exibicao do menu
	 * @param string disposicao vertical/horizontal
	 */
	protected function navFormat($type = 'horizontal')
	{

		$dropdown['li'] = ['class' => 'dropdown'];
		$dropdown['ul'] = ['class' => 'dropdown-menu'];
		$dropdown['a'] = ['class' => 'dropdown-toggle', 'data-toggle' => 'dropdown'];

		switch ($type)
		{
			case 'horizontal':
				$this->dropdown = true;
				$this->format['dropdown'] = $dropdown;
				$this->format['ul1']['class'] = 'nav navbar-nav';
			break;

			case 'vertical':
				$this->dropdown = false;
				$this->format['ul1']['class'] = 'nav';
			break;
		}

		$this->navDirection = $type;

	}

	/**
	 * Retorna uma formatacao desejada
	 *
	 * @param type formatacao
	 * @return type
	 */
	protected function getNavFormat($index)
	{
		if ($index && !empty($this->format[$index])) {
			return $this->format[$index];
		}

  		return;
	}


	/**
	 * Populando o menu com o array de itens
	 *
	 * @param array $nav dados de cada item
	 * @param int $index iniciar itens a partir de um determinado indice
	 * @return string retorna itens do menu em html
	 */
	protected function navItems(array $nav,$index=0): string
	{

		$ul1_attr = $this->toAttr($this->getNavFormat('ul1'));

		$class = ($index == 0) ? $ul1_attr:'';

		$html = '';
		if ($nav !== []) {
			$html .= '<ul'.$class.'>';
			if ($index > 0) {
				$html .= '<li>';
				foreach ($nav[$index] as $child => $data) {
					$a_attr = ['data-id' => $child, 'data-parent-id' => $index];
					$html .= $this->navAnchor($data,$a_attr) . (array_key_exists($child, $nav) ? $this->navItems($nav,$child) : '');
				}

				$html .= '</li>';
			} else {
				foreach ($nav as $parent => $children) {
					$li_attr = '';
					$ul_attr = '';
					$a_attr = '';
					$a_caret = false;

					if (count($children) > 1 && $this->dropdown) {
						$get_dropdown = $this->getNavFormat('dropdown');
						$ul_attr = $this->toAttr($get_dropdown['ul']);
						$li_attr = $this->toAttr($get_dropdown['li']);
						$a_attr =  $get_dropdown['a'];
						$a_caret = true;
					}

					$a_attr['data-id'] = $parent;
					$a_attr['data-parent-id'] = $parent;

					if (!empty($children[$parent])) {

						if (!empty($children[$parent]['hint'])) {
							$popover_placement = $this->navDirection == 'horizontal'? 'bottom' : 'right';
							$li_attr .= $this->toAttr(['data-toggle' => 'popover', 'data-trigger' => 'hover', 'data-placement' => $popover_placement, 'data-content' => $children[$parent]['hint']]);
						}

						$html .= '<li'.$li_attr.'>';
						$html .= $this->navAnchor($children[$parent],$a_attr,$a_caret);
						$html .= '<ul'.$ul_attr.'>';
						foreach ($children as $child => $data) {
							if ($parent !== $child) {
								$li2_attr = '';

								if (!empty($data['hint'])) {
									$li2_attr = $this->toAttr(['data-toggle' => 'popover', 'data-trigger' => 'hover', 'data-placement' => 'right', 'data-content' => $data['hint']]);
								}

								$a_attr = ['data-id' => $child, 'data-parent-id' => $parent];
								$html .= '<li'.$li2_attr.'>
											'.$this->navAnchor($data,$a_attr)
											.(array_key_exists($child, $nav)? $this->navItems($nav,$child) : '').'
										</li>';
							}

						}

						$html .= '</ul>';
						$html .= '</li>';
					}
				}
			}
			$html.= '</ul>';
		}
		return $html;
	}


	/**
	 * Itens para o combo
	 *
	 * @param array $nav dados de cada item
	 * @param int $index iniciar itens a partir de um determinado indice
	 * @return string retorna itens do menu em html
	 */

	protected function comboItems(array $nav, $index = 0, string $prefix = '')
	{

		if ($nav !== []) {
			if ($index > 0) {
				foreach ($nav[$index] as $child => $data) {
					$this->comboItems[$data['id']]= $prefix.$data['title'];
					if (array_key_exists($child, $nav)) {
						$this->comboItems($nav,$child,$prefix.$data['title'].' » ');
					}
				}
			} else {
				foreach ($nav as $parent => $children) {

					if (!empty($children[$parent])) {
						$this->comboItems[$children[$parent]['id']] = $prefix.$children[$parent]['title'];

						foreach ($children as $child => $data) {
							if ($parent !== $child) {
								$this->comboItems[$data['id']]= $prefix.$children[$parent]['title'].' » '.$data['title'];
								if (array_key_exists($child, $nav)) {
									$this->comboItems($nav,$child,$prefix.$children[$parent]['title'].' » '.$data['title'].' » ');
								}
							}

						}
					}
				}
			}
		}

		return (empty($this->comboItems))? [] : $this->comboItems;
	}


	/**
	 * Renderizar menu
	 */
	public function render($id = 0, $type = '')
	{

		$id = $id ?: $this->id;
		$type = $type ?: $this->type;

		$sql_status = match ($this->status) {
			'active' => ' AND status = 1',
			'inactive' => ' AND status = 1',
			default => '',
		};

		$nav = [];

		// Query para trazer itens do menu e armazenar em um array
		$sql = 'SELECT * FROM gfw_nav_children WHERE id_nav =' . $id.$sql_status.' ORDER BY sequence';
		$rs = dbQuery($sql);
		if ($rs) {
			foreach ($rs as $field) {
				$parent = ($field['id_parent'] == 0) ? $field['id'] : $field['id_parent'];
				$nav[$parent][$field['id']] = $field;
			}
		}

		$this->navFormat($type);

		return $this->navItems($nav);
	}


	/**
	 * Força exibir titulo e icone
	 */
	public function showTitleAndIcon(): void
	{
		$this->force_icon_title = true;
	}


	/**
	 * Renderizar menu
	 */
	public function renderToCombo($id = 0)
	{

		$id = $id ?: $this->id;

		if (!$id) {
			return [];
		}

		$nav = [];

		// Query para trazer itens do menu e armazenar em um array
		$sql = 'SELECT * FROM gfw_nav_children WHERE id_nav = ' . $id.' ORDER BY sequence';
		$rs = dbQuery($sql);
		if ($rs) {
			foreach ($rs as $field){
				$parent = ($field['id_parent'] == 0)? $field['id'] : $field['id_parent'];
				$nav[$parent][$field['id']] = $field;
			}
		}
		return $this->comboItems($nav);
	}
}