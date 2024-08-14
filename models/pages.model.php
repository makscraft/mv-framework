<?php
class Pages extends Model
{
	protected $name = 'Меню страниц';
	
	protected $model_elements = [
		['Активировать', 'bool', 'active', ['on_create' => true]],
		['Отображать в меню', 'bool', 'in_menu', ['on_create' => true]],
		['Название', 'char', 'name', ['required' => true]],
		['Заголовок', 'char', 'title'],
		['Родительский раздел', 'parent', 'parent'],
		['Ссылка', 'url', 'url', ['unique' => true, 'translit_from' => 'name']],
		['Редирект', 'redirect', 'redirect'],
		['Позиция', 'order', 'order'],
		['Содержание', 'text', 'content', ['rich_text' => true]]
	];
	
	public function defineCurrentPage(Router $router)
	{
		$url_parts = $router -> getUrlParts();
		
		if($router -> isIndex())
			$params = ['url' => 'index', 'active' => 1];
		else if(count($url_parts) == 1)
			$params = ['url' => $url_parts[0], 'active' => 1];
		else if(count($url_parts) == 2 && $url_parts[0] == 'page' && is_numeric($url_parts[1]))
			$params = ['id' => $url_parts[1], 'active' => 1];
		else
			return null;
		
		if($content = $this -> find($params))
			$this -> id = $content -> id;
		
		return $content;
	}
	
	public function displayMenu(int $parent)
	{
		$rows = $this -> select([
			'parent' => $parent,
			'active' => 1,
			'in_menu' => 1,
			'order->asc' => 'order'
		]);

		$html = '';
		
		foreach($rows as $row)
		{
			$css = $this -> id == $row['id'] ? ' class="active"' : '';
			
			if($row['redirect'])
				$url = $row['redirect'];
			else if($row['url'] == 'index')
				$url = $this -> root_path;
			else
				$url = $this -> root_path.($row['url'] ? $row['url'] : 'page/'.$row['id']);
			
			$html .= "<li".$css."><a href=\"".$url."\">".$row['name']."</a></li>\n";
		}

		return $html;
	}
}