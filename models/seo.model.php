<?
class Seo extends ModelSimple
{
	protected $name = 'SEO параметры';
   
	protected $model_elements = [
		['Заголовок', 'char', 'title', ['help_text' => 'Значение заголовка (title) по умолчанию для всех страниц']],
		['Ключевые слова', 'text', 'keywords', ['help_text' => 'Ключевые слова (meta keywords) по умолчанию для всех страниц']],
      	['Описание', 'text', 'description', ['help_text' => 'Описание (meta description) по умолчанию для всех страниц']],
      	['Robots.txt', 'text', 'robots', ['help_text' => 'Содержимое файла robots.txt']],
		['Meta данные в head', 'text', 'meta_head', ['help_text' => 'Meta тэги, счетчики, плагины']],
		['Meta данные в body', 'text', 'meta_footer', ['help_text' => 'Счетчики и плагины']]
	];
	
	public function mergeParams(mixed $content, string $name_field = '')
	{
   		if(!$this -> data_loaded)
   			$this -> getDataFromDb();
   		
   		if($content === null)
			return;
   		
   		$seo_fields = ['title', 'keywords', 'description'];
   		$text_value = is_string($content) ? $content : '';
		
		foreach($seo_fields as $field)
		{
  			if(is_object($content) && $content -> $field)
				$this -> data[$field] = $content -> $field;
			else if($name_field || $text_value)
			{
				if(!isset($this -> data[$field]))
					$this -> data[$field] = '';
					
				if(!$text_value && $content -> $name_field)
					$text_value = $content -> $name_field;
				
				if(!$text_value)
					continue;

				$glue = ($field == 'keywords') ? ', ' : ' ';
				
				if($this -> data[$field])
					$this -> data[$field] = $text_value.$glue.$this -> data[$field];
				else
					$this -> data[$field] = $text_value;
			}
		}
		   		
		return $this;
	}
	
	public function displayMetaData($type)
	{
		if($type == 'head' && $this -> getValue('meta_head'))
			return htmlspecialchars_decode($this -> getValue('meta_head'), ENT_QUOTES);
		else if($type == 'footer' && $this -> getValue('meta_footer'))
			return htmlspecialchars_decode($this -> getValue('meta_footer'), ENT_QUOTES);
	}
}
?>