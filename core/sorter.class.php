<?php
/**
 * Class for sorting the rows when getting results from SQL queries.
 * Takes values from GET, checks them and passes into SQL query.
 * Usually we need to put the object inside the class where we perform select() method.
 */
class Sorter
{  
	/**
	 * Allowed fields for sorting the same as names of model fields.
	 * @var array
	 */ 
	protected $fields = [];

	/**
	 * Curent field of sorting.
	 * @var string
	 */ 
	protected $field = '';

	/**
	 * Current order of sorting (SQL ASC / DESC).
	 * @var string
	 */ 
	protected $order = ''; 

	/** 
	 * Url params from model.
	 * @var string
	 */
	private $url_params;

	/**
	 * Field param name of sorting in GET.
	 * @var string
	 */ 
	public $source_field;

	/**
	 * Order param name of sorting in GET.
	 * @var string
	 */ 
	public $source_order;

	public function __construct(array $fields = [])
	{
		$this -> source_field = 'sort-field'; //Vars in GET to take values from
		$this -> source_order = 'sort-order';
		$this -> fields = $fields;
	}
   
	/**
	 * Sets sorter params from outside.
	 * @return bool
	 */
	public function setParams(string $field, string $order)
	{
		$order = strtolower($order);

		if(array_key_exists($field, $this -> fields) && ($order === 'asc' || $order === 'desc'))
		{
			$this -> field = $field;
			$this -> order = $order;
			
			return true;
		}
		
		return false;
	}

	/**
	 * Takes sorter params from GET, according to current keys.
	 * @return self
	 */
	public function getDataFromGet()
	{
		if(isset($_GET[$this -> source_field], $_GET[$this -> source_order]))
			if($_GET[$this -> source_field] !== '' && $_GET[$this -> source_order] !== '')
				$this -> setParams($_GET[$this -> source_field], $_GET[$this -> source_order]);
		
		return $this;
	}

	/**
	 * Checks if the sorter has both valid params (field and order).
	 * @return bool
	 */
	public function hasParams()
	{
		return ($this -> field && $this -> order);
	}

	/**
	 * Returns current sort field.
	 * @return string
	 */
	public function getField()
	{
		return $this -> field;
	}

	/**
	 * Returns current sort order.
	 * @return string
	 */
	public function getOrder()
	{
		return $this -> order;
	}

	/**
	 * Returns name of GET param for sorter field.
	 * @return string
	 */
	public function getSourceField()
	{
		return $this -> source_field;
	}

	/**
	 * Sets name of GET param for sorter field.
	 * @return self
	 */
	public function setSourceField(string $value)
	{
		$this -> source_field = $value;

		return $this;
	}	

	/**
	 * Returns name of GET param for sorter order.
	 * @return string
	 */
	public function getSourceOrder()
	{
		return $this -> source_order;
	}

	/**
	 * Sets name of GET param for sorter order.
	 * @return self
	 */
	public function setSourceOrder(string $value)
	{
		$this -> source_order = $value;

		return $this;
	}	

	/**
	 * Returns datatype of current sort field.
	 * @return string
	 */
   	public function getFieldType()
   	{
		return $this -> field !== '' ? $this -> fields[$this -> field] : '';
   	}
   
	/**
	 * Sets current url params.
	 */
	public function setUrlParams(string $url_params = '')
	{
		if($url_params !== '')
			$this -> url_params = '?'.$url_params;
	}

	/**
	 * Generates string of sorting GET params according to current sort values.
	 * @return string
	 */
	public function getUrlParams()
	{
		if($this -> field && $this -> order)
        	return $this -> source_field.'='.$this -> field.'&'.$this -> source_order.'='.$this -> order;

		return '';
	}
	
	/**
	 * Adds to passed url string of sorter GET params.
	 * @return string
	 */
	public function addUrlParams(string $path)
	{		
		if($this -> field && $this -> order)
		{
			$path .= (strpos($path, '?') === false) ? '?' : '&';
        	$path .= $this -> getUrlParams();
		}
		
		return $path;
	}

	/**
	 * Returns parameters for query constructor, to use in Model :: select() method.
	 * @return string string like 'ORDER BY `name` ASC'.
	 */
   	public function getParamsForSQL()
   	{
		if(!$this -> field || !$this -> order)
		{
			$keys = array_keys($this -> fields);
			$this -> field = $keys[0];
			$this -> order = $this -> defineSortOrder($this -> field);   	  	  
		}

		$fix = (Registry :: get("DbEngine") == "sqlite") ? "COLLATE NOCASE " : "";

		if($this -> field && $this -> order)
			return " ORDER BY `".$this -> field."` ".$fix.strtoupper($this -> order);

		return '';
   	}

	/**
	 * Returns sorting conditions for sql query constructor.
	 * @return array conditions for select() and find() methods, like ['order->asc' => 'name']
	 */
	public function getConditions()
	{
		return ['order->'.$this -> order => $this -> field];
	}
   
	/**
	 * Generates sorting html link for admin panel.
	 * @return string
	 */
   	public function createAdminLink(string $caption, string $field)
  	{
   		$params = $this -> url_params ? $this -> url_params."&" : "?";
   		$params .= $this -> source_field."=".$field."&".$this -> source_order."=";
   		
		if(array_key_exists($field, $this -> fields) && $this -> fields[$field] == 'order')
   			$params .= 'asc';
   		else
   			$params .= $this -> defineSortOrder($field);
   		
   		$css_class = '';
   		
   		if($field == $this -> field)
   			if($this -> order == "asc")
   				$css_class = " class=\"active-asc\"";
   			else 
   				$css_class = " class=\"active-desc\"";
   		
		return "<a".$css_class." href=\"".$params."\">".$caption."</a>\n";
   	}
   
	/**
	 * Determines current sorting order.
	 * @return string
	 */
   	public function defineSortOrder(string $field)
   	{
   	   if($this -> field && $this -> order && $this -> field == $field)
			return ($this -> order == 'desc') ? 'asc' : 'desc';
   	  
   	  $initial_orders = [
   	  	  'id' => 'asc',
	   	  'bool' => 'desc',
	   	  'int' => 'desc',
	   	  'float' => 'desc',
	   	  'char' => 'asc',
	   	  'url' => 'asc',
	   	  'redirect' => 'asc',
	   	  'email' => 'asc',
	   	  'enum' => 'asc',
	   	  'parent' => 'desc',
	   	  'order' => 'asc',
	   	  'date' => 'desc',
	   	  'date_time' => 'desc',
	   	  'image' => 'desc',
	   	  'multi_images' => 'desc',
   	  	  'file' => 'asc',
	   	  'many_to_one' => 'desc',
	   	  'many_to_many' => 'desc'
	  ];
   	  
   	  if(array_key_exists($field, $this -> fields))
   	  	if(array_key_exists($this -> fields[$field], $initial_orders))
   	  		return $initial_orders[$this -> fields[$field]];
   	  	else
   	  		return 'asc';

		return '';
   	}
   	
	/**
	 * Generates sorting html link for one model field.
	 * @return string
	 */
   	public function displayLink(string $field, string $title, string $path = '', string $reverse = '')
   	{
   		$order = $this -> defineSortOrder($field);
   		$css_class = '';
   		$path .= (strpos($path, "?") === false) ? "?" : "&";
		
		if(!($this -> field && $this -> order && $this -> field == $field))
   			if($reverse === "reverse")
   				$order = ($order == 'desc') ? 'asc' : 'desc';
   		
   		if($field == $this -> field)
   			if($this -> order == "asc")
   				$css_class = " class=\"active-asc\"";
   			else 
   				$css_class = " class=\"active-desc\"";
   		
   		$html = "<a href=\"".$path.$this -> source_field."=".$field."&";
   		$html .= $this -> source_order."=".$order."\"".$css_class.">";
   		
   		return $html.$title."</a>\n";
   	}
   	
	/**
	 * Generates sorting html link for one model field.
	 * @return string
	 */
   	public function displaySingleLink(string $field, string $order, string $title, string $path = '')
   	{
   		$css_class = '';
   		$path .= (strpos($path, "?") === false) ? "?" : "&";
   		
   		if($field == $this -> field && $order == $this -> order)
   			$css_class = " class=\"active\"";
   		
   		$html = "<a href=\"".$path.$this -> source_field."=".$field."&";
   		$html .= $this -> source_order."=".$order."\"".$css_class.">";
   		
   		return $html.$title."</a>\n";
   	}
	
	/**
	 * Generates html options for select tag for one model field.
	 * @return string
	 */
	public function displaySelectOptions(array $data)
   	{
   	   	$html = '';
   	   	
   	   	foreach($data as $field)
   	   	{
   	   		$selected = '';
   	   				
   			if($field[0] == $this -> field && $field[1] == $this -> order)
   				$selected = " selected=\"selected\"";
   	   		
   			$html .= "<option value=\"".$this -> source_field."=".$field[0]."&";
   			$html .= $this -> source_order."=".$field[1]."\"".$selected.">".$field[2]."</option>\n";
   	   	}
   	   	
   	   	return $html;
   	}
}
