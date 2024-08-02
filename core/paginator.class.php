<?php
/**
 * Class for splitting the long lists of records into pages.
 * Creates parts of SQL queries to use in LIMIT constructions.
 */
class Paginator
{
   	/**
	 * Total number of items in list (usually in current table).
	 * @var int
	 */ 
   	private $total;
   
   	/**
	 * Limit of items to show per one page.
	 * @var int
	 */ 
   	private $limit;
   
   	/**
	 * Total number of pages according to $this -> total and $this -> limit.
	 * @var int
	 */ 
   	private $intervals;
   
   	/**
	 * First element to show from current page according to params.
	 * @var int
	 */ 
   	private $start = 0;
   
   	/**
	 * Current number of page to show (usually passed from GET).
	 * @var int
	 */ 
   	private $page = 1;

   	/**
	 * Params for url string.
	 * @var string
	 */
   	private $url_params = '';
        
	/**
	 * Gets the limits form GET and counts the needed params.
	 */
   	public function __construct(int $total, int $limit)
   	{
      	$this -> total = $total;
		$this -> setLimit($limit);
   	}

	/**
	 * Sets the new limit and recounts the params according to new value.
	 */
	public function setLimit(int $limit)
	{
	   $this -> limit = intval($limit);
	   $this -> intervals = (int) ceil($this -> total / $this -> limit);

	   if(!empty($_GET) && isset($_GET['page'])) //If we have current page number in GET we take it
		  $this -> definePage(intval($_GET['page'])); //and determine the current elements to show on the page
	}
 
	/**
	 * Sets new total number and recounts the params according to new value.
	 */
	public function setTotal(int $total) 
	{
	   $this -> total = $total;
	   $this -> setLimit($this -> limit); //Recount the params
	}

	/**
	 * Sets new current page number and recounts the params according to new value.
	 */
	public function setPage(int $page) 
	{
		$this -> definePage($page);
	}
   
	/**
	 * Returns total number of items in paginator.
	 * @return int
	 */
   	public function getTotal()
	{
		return $this -> total;
	}

	/**
	 * Returns limit of items per page in paginator.
	 * @return int
	 */
	public function getLimit()
	{
		return $this -> limit;
	}

	/**
	 * Returns current page number.
	 * @return int
	 */
	public function getPage()
	{
		return $this -> page;
	}   	

	/**
	 * Returns last page number.
	 * @return int
	 */
   	public function getLast()
	{
		return $this -> intervals;
	}
   	
	/**
	 * Returns index of first item (for SQL or array selection), according to current params.
	 * @return int
	 */
	public function getStart()
	{
		return $this -> start;
	}

	/**
	 * Checks if the current paginator has pages quantity greater than 2.
	 * @return bool
	 */
	public function hasPages()
	{
		return ($this -> intervals > 1); 
	}

	/**
	 * Determines the limits and checks the cases when the page number doesn't exists.
	 */
   	public function definePage(int $page)
   	{
      	if($page <= 1)
         	$this -> page = 1;
      	else if($page > $this -> intervals)
         	$this -> page = $this -> intervals;
      	else
         	$this -> page = $page;
         
     	$this -> start = ($this -> page - 1) * $this -> limit;
   	}   

	/**
	 * Creates string for SQL query to set limits of selected items (to use it like LIMIT 5,8).
	 * @return string like LIMIT 20,10
	 */
   	public function getParamsForSQL()
   	{
       	return ' LIMIT '.$this -> start.','.$this -> limit; 
   	}

	/**
	 * Returns parameters for query constructor, to use in Model :: select() method.
	 * @return string string like '10,30' to use in 'limit->' parameter.
	 */
	public function getParamsForSelect()
   	{
   		return $this -> start.','.$this -> limit;
   	}

	/**
	 * Returns pagination conditions for sql query constructor.
	 * @return array conditions for select() and find() methods, like ['limit->' => '5,10']
	 */
	public function getConditions()
	{
		return ['limit->' => $this -> getParamsForSelect()];
	}
   
	/**
	 * Sets initial url parameters for current pagination.
	 * @return self
	 */
	public function setUrlParams(string $url_params)
	{
		$this -> url_params = $url_params ? '?'.$url_params : '';
			
		return $this;
	}
	

	/**
	 * Returns string of url GET params.
	 * @return string like page=23
	 */
	public function getUrlParams()
	{	
		return $this -> page > 1 ? 'page='.$this -> page : '';
	}
	
	/**
	 * Adds to passed url the string of pagination GET params.
	 * @return string path with current page param
	 */
	public function addUrlParams(string $path)
	{
		if($this -> page <= 1)
			return $path;

		$path .= (strpos($path, '?') === false) ? '?' : '&';    
		
		return $path.'page='.$this -> page;
	}
  
	/**
	 * Returns get param string like (?|&)page=2
	 * @return string
	 */
   public function addPage(string $get) 
   {  	  
	  	if($this -> intervals > 1)
      	  	return $get ? '&page='.$this -> page : '?page='.$this -> page;

		return '';
   }
   
   /**
	* Chaeck if we have any pages before or after the current one.
	* @return bool
    */
   public function checkPrevNext(string $type)
   {
      	if($type == 'next')
         	return ($this -> page + 1 <= $this -> intervals);
      	else if($type == 'prev')
         	return ($this -> page - 1 > 0);

		return false;
   }
   
   /**
	* Generates pagination list of html links for admin panel.
    * @return string html code
    */
   public function displayPagesAdmin()
   {
      	if($this -> intervals < 2)
         	return '';
      
      	$html = "<div class=\"pager\">\n<div>\n<span>".I18n :: locale('page')."</span>\n";

      	//In case if we have more than 10 pages we need to show only 10 current ones and show the link for next pages
      	if($this -> intervals > 10) 
      	{
         	$totint = ceil($this -> intervals / 10); //Total number of intervals (by 10)
         	$cint  = ceil($this -> page / 10); //Current interval (by 10)

         	if($cint == 1) //If we at the first interval
            	$int_start = ($cint - 1) * 10 + 1;
         	else
            	$int_start = ($cint - 1) * 10;
         
         	if($cint < $totint)
            	$int_end = $int_start + 10;
         	else
            	$int_end = $this -> intervals;

         	if($cint != 1 && $cint != $totint)
            	$int_end ++;
      	}
      	else //If the number of page less then 10
      	{
         	$int_start = 1;
         	$int_end = $this -> intervals;
      	}
      
      	if($this -> intervals > 10 && $cint != 1)  //To display link for very first page
      	{
         	$html .= "<a href=\"".$this -> url_params;
         	$html .= (strpos($this -> url_params, "?") !== false) ? ("&page=1\"") : ("?page=1\"");
         	$html .= " class=\"pager-first\"></a>\n";
      	}
      
      	//And now we need to add page numbers for our path
      	for($i = $int_start; $i <= $int_end; $i ++)
      	{
         	$html .= "<a href=\"".$this -> url_params;
         	$html .= (strpos($this -> url_params, "?") !== false) ? ("&page=".$i."\"") : ("?page=".$i."\"");
         
         	if($i == $this -> page) //Highlights the current page in the interval
           	 	$html .= " class=\"active\"";
         
         	if($this -> intervals > 10) //In case of overflow we use sign '<' and '>', reffering to the next 10 or less pages.
         	{
            	if($i == $int_start && $i != 1)
               		$html .=  " class=\"pager-prev\">";
            	else if($i == $int_end && ($i != $this -> intervals || $totint != $cint))
               		$html .=  " class=\"pager-next\">";
            	else
               		$html .=  ">".$i;
         	}
        	 else
            	$html .=  ">".$i;
         
         	$html .=  "</a>\n";
      	} 
      
      	if($this -> intervals > 10 && $cint != $totint) //To display link for very last page
      	{
         	$html .= "<a href=\"".$this -> url_params;
         	$html .= (strpos($this -> url_params, "?") !== false) ? ("&page=".$this -> intervals."\"") : ("?page=".$this -> intervals."\"");
         	$html .= " class=\"pager-last\"></a>\n";
      	}
      	
      	$html .=  "</div>\n</div>\n";
      
      	return $html;
   	}
   
   /**
	* Displays select options (limits of elements per page).
    * @return string html options for select tag
    */	
   	public function displayPagerLimits(array $values)
   	{
   	  	$html = "";
   	  
   	  	foreach($values as $value)
   	  	{
   	  	 	$html .= "<option value=\"".$value."\"";
   	  	 
   	  	 	if($value == $this -> limit)
   	  	 		$html .= " selected=\"selected\"";
   	  	 
   	  	 	$html .= ">".$value."</option>\n";
   	  	}

   	  	return $html;
   	}
	
	/**
	 * Displays html links of pagination (current page is in the center of interval).
	 * @return string html code
	 */
   	public function display(string $path, bool $smart = false)
   	{
      	if($this -> intervals < 2) //If numer of pages less than 2
         	return '';
      
      	$html = '';
		$path = Registry :: get('SitePath').$path;
      	$interval = []; //Pages numbers to display
      
      	$current_left = ceil($this -> page - 1); //Number of pages from left side
      	$current_right = ceil($this -> intervals - $this -> page); //Number of pages from right side
      
      	if($current_left > 5 && $current_right > 5) //If we are at the middle
      	{
         	$i = $current_left - 4; //5 previous elemets
         	
         	while($i < $this -> page + 6 && $i < $this -> intervals)
            	$interval[] = $i ++;
      	}
      	else if($current_left > 5) //10 elements form the end
      	{
         	$i = $this -> intervals;
         	
         	while($i > $this -> intervals - 10 && $i > 0)
            	array_unshift($interval, $i --);
      	}
      	else if($current_right > 5) //10 element form beginning
      	{
         	$i = 1;
         	
         	while($i < 11 && $i <= $this -> intervals)
            	$interval[] = $i ++;
      	}
      	else
      	{
         	$i = 1;
         	
         	while($i <= $this -> intervals)
            	$interval[] = $i ++;       
      	}
      
      	$arguments = func_get_args();
      	$extra_params = (isset($arguments[2])) ? $arguments[2] : '';
      
      	if($smart) //Url part of page number to  replace it with integer value
         	$pattern = "page/number/".$extra_params."\"";
      	else
         	$pattern = (strpos($path, "?")) ? ("&page=number\"") : ("?page=number\"");
      
      	$first = false;
         
      	foreach($interval as $value) //Adds pages links one by one
      	{
         	$html .= "<a href=\"".$path.str_replace("number", $value, $pattern); //Adds number of page
         
         	$css_class = "";
			
		 	if(!$first)
		 	{
				$css_class = "first";
				$first = true;
		 	}
			
		  	if($value == $this -> page) //Highlights the current page in the interval
				$css_class .= $css_class ? " active" : "active";

		 	if($css_class)
				$html .= " class=\"".$css_class."\"";			
			
         	$html .= ">".$value."</a>\n";
      	}
      
      	if($current_left > 5 && $this -> intervals > 10) //If we need to add the very first page
         	$html = "<a class=\"very-first\" href=\"".$path.str_replace("number", 1, $pattern).">...</a>\n".$html;
            
      	if($current_right > 5 && $this -> intervals > 10) //Id we add very last page
         	$html .= "<a class=\"very-last\" href=\"".$path.str_replace("number", $this -> intervals, $pattern).">...</a>\n";     
      
      	return $html;
   	}
   
	/**
	 * Displays html select options with limits values or links with limits values.
	 * @return string html code
	 */
   	public function displayLimits(array $limits, string $path, string $format = 'links')
   	{
   		$html = '';
   		$options = $format === "options";
   		$path .= (strpos($path, "?") === false) ? "?" : "&";
   		
   	    foreach($limits as $limit)
   	    {
   	    	$url = $path."pager-limit=".$limit;
   	    	$html .= $options ? "<option value=\"".$url."\"" : "<a href=\"".$url."\"";
   	    	
   	    	if($this -> limit == $limit)
   	    		$html .= $options ? " selected=\"selected\"" : " class=\"active\"";
				
			$html .= ">".$limit.($options ? "</option>\n" : "</a>\n");
   	    }
   	    	
   	    return $html;
   	}
   	
	/**
	 * Displays html link for previous page (if exists).
	 * @return string html link code
	 */	
   	public function displayPrevLink(string $caption, string $path)
   	{
   		if($this -> checkPrevNext("prev") && $caption)
   		{
   			$path .= (strpos($path, "?") === false) ? "?" : "&";
   			$path .= "page=".($this -> page - 1);
			
   			return "<a class=\"pager-prev\" href=\"".$path."\">".$caption."</a>\n";
   		}

		return '';
   	}

	/**
	 * Displays html link for next page (if exists).
	 * @return string html link code
	 */	
   	public function displayNextLink(string $caption, string $path)
   	{
   		if($this -> checkPrevNext("next") && $caption)
   		{
   			$path .= (strpos($path, "?") === false) ? "?" : "&";
   			$path .= "page=".($this -> page + 1);

   			return "<a class=\"pager-next\" href=\"".$path."\">".$caption."</a>\n";
   		}

	   return '';
   	}

	   public function __call($method, $arguments)
	   {		
		   if($method === "getIntervals")
			   return $this -> getLast();
	   }
}
