<?
class Blocks extends Model
{
	protected $name = 'Текстовые блоки';
	
	protected $model_elements = [
		['Активация', 'bool', 'active', ['on_create' => true]],
		['Название', 'char', 'name', ['required' => true]],
		['Содержание', 'text', 'content', ['rich_text' => true]]
	];
}
?>