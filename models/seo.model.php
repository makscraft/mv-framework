<?php
class Seo extends ModelSimple
{
	protected $name = 'SEO parameters';
   
	protected $model_elements = [
		['Title', 'char', 'title', ['help_text' => 'Default value of html meta title for all pages']],
		['Keywords', 'text', 'keywords', ['help_text' => 'Default value of html meta keywords for all pages']],
      	['Description', 'text', 'description', ['help_text' => 'Default value of html meta description for all pages']],
      	['Robots.txt', 'text', 'robots', ['help_text' => 'Content of robots.txt']],
		['Meta data in head', 'text', 'meta_head', ['help_text' => 'Meta tags, counters, plugins, verifications']],
		['Meta date in body', 'text', 'meta_footer', ['help_text' => 'Counters and plugins']]
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