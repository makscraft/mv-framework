<?php
/**
 * Parent for Record class, contains base methods for child class.  
 */
class Content
{
	/**
	 * Id of current record in db.
	 * @var int
	 */
	protected $id;
	
	/**
	 * Array of fields with their values from database row.
	 * @var array
	 */
	protected $content = [];
	
	/**
	 * Cache of enum fields captions for method getEnumTitle().
	 * @var array
	 */
	protected $enum_values = [];
	
	/**
	 * Object of model which this record belongs to.
	 * @var object
	 */
	protected $model;

	public function __construct(array $content = [])
	{
		$this -> passContent($content);
	}
	
	public function passContent(array $content)
	{
		if(is_array($content) && count($content))
		{
			$this -> content = $content;
			
			if(isset($content['id']) && $content['id'])
				$this -> id = intval($content['id']);
		}
			
		return $this;
	}
	
	public function getId()
	{
		return $this -> id;
	}
	
	public function getValue(string $key)
	{
		if(isset($this -> content[$key]))
			return $this -> content[$key];
	}
	
	public function setValue(string $key, mixed $value)
	{
		if(isset($this -> content[$key]))
			$this -> content[$key] = $value;
			
		return $this;
	}
	
	public function defineUrl(string $first_part)
	{
		$registry = Registry :: instance();
		$url = $registry -> getSetting("MainPath").$first_part."/";
		
		$arguments = func_get_args();
		
		$url_field = isset($arguments[1], $this -> content[$arguments[1]]) ? $arguments[1] : null;
		
		if($url_field && $this -> content[$url_field])
			return $url.$this -> content[$url_field]."/";
		else
			return $url.$this -> id."/";
	}
	
	public function extractImages(string $field)
	{
		if(!isset($this -> content[$field]) || !$this -> content[$field])
			return [];
			
		$arguments = func_get_args();
		$argument = (isset($arguments[1]) && $arguments[1]) ? $arguments[1] : null;
			
		return $this -> model -> extractImages($this -> content[$field], $argument);	
	}
	
	public function getFirstImage(string $field)
	{
		return isset($this -> content[$field]) ? $this -> model -> getFirstImage($this -> content[$field]) : '';
	}
	
	public function combineImages(string $field, array $images)
	{
		$result_images = [];
		
		if(count($images))
			if(isset($images[0]))
				$result_images = $images;
			else
				foreach($images as $image => $comment)
					$result_images[] = $comment ? $image."(*".preg_replace("/(\r)?\n/", "", $comment)."*)" : $image;
		
		if(isset($this -> content[$field]))
			$this -> content[$field] = implode("-*//*-", $result_images);
		
		return $this;
	}
	
	public function displayImage(string $field)
	{
		$arguments = func_get_args();
		$arguments[3] = (isset($arguments[1]) && $arguments[1]) ? $arguments[1] : null;
		$arguments[4] = (isset($arguments[2]) && $arguments[2]) ? $arguments[2] : null;
		
		$params = ModelInitial :: processImageArguments($arguments);
		
		if(isset($this -> content[$field]) && is_file(Service :: addFileRoot($this -> content[$field])))
		{
			$src = Registry :: instance() -> getSetting("MainPath").$this -> content[$field];
			return "<img".$params["css-class"]." src=\"".$src."\" alt=\"".$params["alt-text"]."\"".$params["title"]." />\n";
		}
		else
			return $params["no-image-text"];
	}
	
	public function resizeImage(string $field, int $width, int $height)
	{
		if(!isset($this -> content[$field]) || !$this -> content[$field])
			return;
			
		$arguments = func_get_args();
		$argument_3 = isset($arguments[3]) ? $arguments[3] : null;
		$argument_4 = isset($arguments[4]) ? $arguments[4] : null;
		
		return $this -> model -> resizeImage($this -> content[$field], $width, $height, $argument_3, $argument_4);
	}
	
	public function cropImage(string $field, int $width, int $height)
	{
		if(!isset($this -> content[$field]) || !$this -> content[$field])
			return;
			
		$arguments = func_get_args();
		$argument_3 = isset($arguments[3]) ? $arguments[3] : null;
		$argument_4 = isset($arguments[4]) ? $arguments[4] : null;
		
		return $this -> model -> cropImage($this -> content[$field], $width, $height, $argument_3, $argument_4);
	}	
	
	public function displayFileLink(string $field)
	{
		$arguments = func_get_args();
		$registry = Registry :: instance();
		
		$link_text = (isset($arguments[1]) && $arguments[1]) ? $arguments[1] : null;
		$no_file_text = (isset($arguments[2]) && $arguments[2]) ? $arguments[2] : null;
		
		if(isset($this -> content[$field]) && is_file(Service :: addFileRoot($this -> content[$field])))
		{
			$link_text = $link_text ? $link_text : basename($this -> content[$field]);

			return "<a target=\"_blank\" href=\"".$registry -> getSetting("MainPath").$this -> content[$field]."\">".$link_text."</a>\n";
		}
		else if($no_file_text)
			return $no_file_text;
	}
	
	public function wrapInParagraphs(string $field)
	{
		if(isset($this -> content[$field]) && trim($this -> content[$field]))
			return "<p>".str_replace(['<br>', '</br>'], "</p>\n<p>", nl2br($this -> content[$field]))."</p>\n";
	}	
}
