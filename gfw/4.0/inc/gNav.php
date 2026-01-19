<?php

/* 
 * Classe para criar menu de dinamicamente
 */

class gNav{
		
	protected $id;
	
	protected $dropdown;
	
	protected $type;
	
	protected $status;
	
	protected $force_icon_title = false;

	/**
	 * Construtor da classe
	 * 
	 * @param type $id
	 */		 
	public function __construct($id = 0, $type='horizontal') {
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
	public function setConfig($var,$value){
		$this->{$var} = $value;
	}
	
	/**
	 * Converte matriz para o formato de atributos html
	 * @param 
	 */
	protected function toAttr($data){		
		if(!count($data)) return;
		$r = '';
		foreach($data as $attr => $value){
			$r.= ' '.$attr.'="'.$value.'"';
		}
		return $r;
	}

	/**
	 * Cria a tag html <a> para ser utilizada no menu
	 * 
	 * @param array $data
	 * @return string tag em html
	 */
	protected function navAnchor($data,$params=array(),$caret=false){		
		if(empty($data)) return;

		extract($data);
				
		$target = !empty($target)? ' target="'.$target.'"' : '';
		$icon = !empty($icon)? '<span class="fa fa-'.$icon.'"></span> ' : '';
		$caret = $caret? ' <span class="caret"></span>' : '';
		
		if(!$this->force_icon_title){
			$title = ($show_title)? $title : '';
			$icon = ($show_icon)? $icon : '';
		}

		$html = '<a href="'.$anchor.'"'.$target.$this->toAttr($params).'>'.$icon.$title.$caret.'</a>';

		return $html;
	}
	
	/**
	 * Formata exibicao do menu
	 * @param string disposicao vertical/horizontal
	 */
	protected function navFormat($type = 'horizontal'){
				
		$dropdown['li'] = array('class' => 'dropdown');
		$dropdown['ul'] = array('class' => 'dropdown-menu');
		$dropdown['a'] = array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown');
		
		switch($type){
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
	protected function getNavFormat($index){
		if($index && !empty($this->format[$index])){			
			return $this->format[$index];
		}		
	}

	/**
	 * Populando o menu com o array de itens
	 * 
	 * @param array $nav dados de cada item
	 * @param int $index iniciar itens a partir de um determinado indice
	 * @return string retorna itens do menu em html
	 */
	protected function navItems($nav,$index=0){
		
		$ul1_attr = $this->toAttr($this->getNavFormat('ul1'));
		
		$class = ($index==0)? $ul1_attr:'';
		
		$html = '';
		if($nav){
			$html.= '<ul'.$class.'>';	
			if($index > 0){
				$html.= '<li>';					
				foreach($nav[$index] as $child => $data){
					$a_attr = array('data-id' => $child, 'data-parent-id' => $index);
					$html.= $this->navAnchor($data,$a_attr)
							.(array_key_exists($child, $nav)? $this->navItems($nav,$child) : '');					
				}
				$html.= '</li>';
			}else{
				foreach($nav as $parent => $children){
					$li_attr = '';
					$ul_attr = '';
					$a_attr = '';
					$a_caret = false;
									
					if(count($children) > 1 && $this->dropdown){
						$get_dropdown = $this->getNavFormat('dropdown');
						$ul_attr = $this->toAttr($get_dropdown['ul']);
						$li_attr = $this->toAttr($get_dropdown['li']);
						$a_attr =  $get_dropdown['a'];
						$a_caret = true;						
					}
					
					$a_attr['data-id'] = $parent;
					$a_attr['data-parent-id'] = $parent;
						
					if(!empty($children[$parent])){
						
						if(!empty($children[$parent]['hint'])){
							$popover_placement = $this->navDirection == 'horizontal'? 'bottom' : 'right';
							$li_attr .= $this->toAttr(array('data-toggle' => 'popover', 'data-trigger' => 'hover', 'data-placement' => $popover_placement, 'data-content' => $children[$parent]['hint']));
						}
						
						$html.= '<li'.$li_attr.'>';
							$html.= $this->navAnchor($children[$parent],$a_attr,$a_caret);
							$html.= '<ul'.$ul_attr.'>';
							foreach($children as $child => $data){							
								if($parent !== $child){
									$li2_attr = '';
									if(!empty($data['hint'])){
										$li2_attr = $this->toAttr(array('data-toggle' => 'popover', 'data-trigger' => 'hover', 'data-placement' => 'right', 'data-content' => $data['hint']));
									}
									
									$a_attr = array('data-id' => $child, 'data-parent-id' => $parent);
									$html.= '<li'.$li2_attr.'>
												'.$this->navAnchor($data,$a_attr)
												.(array_key_exists($child, $nav)? $this->navItems($nav,$child) : '').'
											</li>';
								}

							}
							$html.= '</ul>';
						$html.= '</li>';
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
	
	protected function comboItems($nav,$index=0,$prefix=''){		
		
		if($nav){			
			if($index > 0){
				foreach($nav[$index] as $child => $data){
					$this->comboItems[$data['id']]= $prefix.$data['title'];
					if(array_key_exists($child, $nav))
						$this->comboItems($nav,$child,$prefix.$data['title'].' » ');
				}
			}else{
				foreach($nav as $parent => $children){					
						
					if(!empty($children[$parent])){
						$this->comboItems[$children[$parent]['id']]= $prefix.$children[$parent]['title'];

						foreach($children as $child => $data){							
							if($parent !== $child){
								$this->comboItems[$data['id']]= $prefix.$children[$parent]['title'].' » '.$data['title'];
								if(array_key_exists($child, $nav))
									$this->comboItems($nav,$child,$prefix.$children[$parent]['title'].' » '.$data['title'].' » ');
							}

						}
					}
				}
			}
		}
		return (!empty($this->comboItems))? $this->comboItems : array();
	}

	/**
	 * Renderizar menu
	 */
	public function render($id = 0, $type = ''){
		
		$id = ($id)? $id : $this->id;
		$type = ($type)? $type : $this->type;
				
		switch($this->status){
			case 'active':
				$sql_status = ' AND status = 1';
			break;
			case 'inactive':
				$sql_status = ' AND status = 1';
			break;
			case 'all':
			default:	
				$sql_status = '';
			break;
		}
		

		$nav = array();		
		
		// Query para trazer itens do menu e armazenar em um array
		$sql='SELECT * FROM gfw_nav_children WHERE id_nav =' . $id.$sql_status.' ORDER BY sequence';
		$rs=dbQuery($sql);
		if(count($rs)){
			foreach ($rs as $field){
				$parent = ($field['id_parent'] == 0)? $field['id'] : $field['id_parent'];
				$nav[$parent][$field['id']] = $field;							
			}
		}
		
		$this->navFormat($type);		

		return $this->navItems($nav);
	}
	
	/**
	 * Força exibir titulo e icone
	 */
	public function showTitleAndIcon(){
		$this->force_icon_title = true;
	}
	
	/**
	 * Renderizar menu
	 */
	public function renderToCombo($id = 0){
		
		$id = ($id)? $id : $this->id;
		
		if(!$id) return array();

		$nav = array();		
		
		// Query para trazer itens do menu e armazenar em um array
		$sql='SELECT * FROM gfw_nav_children WHERE id_nav =' . $id.' ORDER BY sequence';
		$rs=dbQuery($sql);
		if(count($rs)){
			foreach ($rs as $field){
				$parent = ($field['id_parent'] == 0)? $field['id'] : $field['id_parent'];
				$nav[$parent][$field['id']] = $field;							
			}
		}
		return $this->comboItems($nav);
	}
}