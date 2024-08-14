<?php
/**
 * Filters manager in admin panel and front of application.
 * Collects data from GET and composes SQL query fragment as a  result.
 */
class Filter
{
	/**
	 * Model's fields, settings for filtering.
	 * @var array
	 */
	private $fields = [];
	
	/**
	 * URL parametes for merge with other params.
	 * @var string
	 */
	private $url_params;
	
	/**
	 * Model object, source of current filters.
	 * @var object
	 */ 
	private $model;

	/**
	 * Custom (not from model) filters fields.
	 * @var array
	 */
	public $custom_filters_ids = [];

	/**
	 * Filter field of m2o type, connected with sorter.
	 * @var string
	 */
	public $allowed_count_field;

	/**
	 * Conditions list for numeric values.
	 * @var array
	 */
	public const NUMERIC_CONDITIONS = [
		'eq' => '=', 'neq' => '!=', 'gt' => '>', 'lt' => '<', 'gte' => '>=', 'lte' => '<='
	];
	
	public function __construct(array $fields, string $source = '', object $model = null)
	{
		if(is_object($model) && Registry :: checkModel($model -> getModelClass()))
			$this -> model = $model;

		//If the filters start from application front, we simply pass their data
		if($source === 'frontend')
		{
			$this -> fields = $fields;
						
			return;
		}

		$as_intervals = ['int', 'float', 'order', 'many_to_one', 'date', 'date_time'];
		$as_numerics = ['int', 'float', 'order', 'many_to_one'];
		
		//Regular filters construction for admin panel
		foreach($fields as $name => $data)
			if($data['type'] != 'password')
			{
				$this -> fields[$name] = $data;
				
				//If filter value was passed into GET
				if(isset($_GET[$name]) || isset($_GET[$name."-from"]) || isset($_GET[$name."-to"]))
				{
					$type = $this -> fields[$name]['type'];
					$checked_value = '';
					
					//Interval fields processing
					if(in_array($type, $as_intervals))
					{
						$checked_value = $conditions = [];

						if(in_array($type, $as_numerics))
							foreach(['from', 'to'] as $key)
								if(isset($_GET[$name."-cond-".$key]))
									if(array_key_exists($_GET[$name."-cond-".$key], self :: NUMERIC_CONDITIONS))
										$conditions[$key] = $_GET[$name."-cond-".$key];
						
						foreach(['from', 'to'] as $key)
							if(isset($_GET[$name."-".$key]) && $_GET[$name."-".$key] !== '')
								$checked_value[$key] = $this -> checkFieldValue($type, $_GET[$name."-".$key]);
					}
					else //Regular field
						$checked_value = $this -> checkFieldValue($type, $_GET[$name], $data);

					//Passes the value into filter data set
					//If value was taken from GET and checked before
					if(!in_array($type, $as_intervals) && $checked_value !== '')
						$this -> fields[$name]['value'] = $checked_value; 
					else if(in_array($type, $as_intervals))
					{
						if(!in_array($type, $as_numerics))
							$this -> fields[$name]['value'] = $checked_value;
						else
						{
							foreach(['from', 'to'] as $key)
								if(!isset($conditions[$key]) || !isset($checked_value[$key]))
									unset($conditions[$key], $checked_value[$key]);

							$this -> fields[$name]['value'] = $checked_value;
							$this -> fields[$name]['conditions'] = $conditions;
						}
					}
				}
			}
	}
	
	public function addFilter(string $caption, string $type, string $name, array $params = [])
	{
		$types = array("bool", "char", "int", "float", "enum", "text");

		if(!in_array($type, $types))
			return $this;
		
		$this -> fields[$name] = array("type" => $type, "caption" => $caption);
		
		if($type == "enum")
		{
			$this -> fields[$name]["values_list"] = [];
			
			if(isset($params["values_list"]) && is_array($params["values_list"]) && count($params["values_list"]))
				$this -> fields[$name]["values_list"] = $params["values_list"];
			else if(isset($params["foreign_key"]) && $params["foreign_key"])
			{
				$object = $this -> model -> getElement($name);
				$this -> fields[$name]["long_list"] = $object -> getProperty("long_list");
				
				if(!$this -> fields[$name]["long_list"])
				{
					$object -> defineValuesList(get_class($this -> model));
					$this -> fields[$name]["values_list"] = $object -> getProperty("values_list");
				}
			}
			
			if(isset($params["empty_value"]) && $params["empty_value"])
				$this -> fields[$name]["empty_value"] = $params["empty_value"];
			else
				$this -> fields[$name]["empty_value"] = I18n :: locale("not-defined");
		}		
		
		if(isset($_GET[$name]))
		{
			$value = self :: checkFieldValue($type, trim($_GET[$name]), $this -> fields[$name]);
		
			if($value != "")
				$this -> fields[$name]["value"] = $value;
		}
		else
			foreach($_GET as $key => $value)
				if($key == $name."-from" || $key == $name."-to")
				{
					$condition = $key == $name."-from" ? "gte" : "lte";
					$value = Filter :: checkFieldValue($type, trim($value), $this -> fields[$name]);
			
					if($value != "")
						$this -> fields[$name]["conditions"][$condition] = $value;
				}
		
		return $this;
	}
	
	public function removeFilter(string $field)
	{
		if(isset($this -> fields[$field]))
			unset($this -> fields[$field]);
		
		return $this;
	}
	
	public function setAllowedCountFilter(string $field)
	{
		if(array_key_exists($field, $this -> fields) && $this -> fields[$field]['type'] === 'many_to_one')
			 $this -> allowed_count_field = $field;
		
		return $this;
	}
	
	static public function checkFieldValue(string $type, mixed $value)
	{
		$checked_value = '';
		$arguments = func_get_args(); //We need the list of possible enum values
		$db = Database :: instance();
		
		//Check of fields values of different types
		if(in_array($type, array('char','url','redirect','email','text','phone')))
		{
			$value = str_replace("+", "%2B", $value);
			$checked_value = urldecode($value);
		}
		else if($type == 'enum' || $type == 'group')
		{
			if($value == "*" || $value == "-")
				$checked_value = $value;
			else if(isset($arguments[2]['long_list'], $arguments[2]['foreign_key']) && $arguments[2]['long_list'])
			{				
				if($db -> getCount($arguments[2]['foreign_key'], "`id`='".intval($value)."'"))
					$checked_value = $value;
			}
			else if(isset($arguments[2]['table']) && $arguments[2]['table'])
			{
				if($db -> getCount($arguments[2]['table'], "`id`='".intval($value)."'"))
					$checked_value = $value;				
			}
			else if(isset($arguments[2]['values_list']) && array_key_exists($value, $arguments[2]['values_list']))
				$checked_value = htmlspecialchars(urldecode($value));
		}
		else if($type == 'parent')
		{
			if($value == 'all')
				$checked_value = 'all';
			else if(isset($arguments[2]['long_list'], $arguments[2]['table']) && $arguments[2]['long_list'])
			{
				$value = intval($value);
				
				if($value == -1)
					$checked_value = -1;
				else if(Database :: instance() -> getCount($arguments[2]['table'], "`id`='".$value."'"))
					$checked_value = $value;
			}
			else if(isset($arguments[2]['values_list']) && array_key_exists(intval($value), $arguments[2]['values_list']))
			 	$checked_value = intval($value);
		}
		else if($type == 'many_to_many')
		{
			if($value == "*" || $value == "-")
				$checked_value = $value;
			else if(is_numeric($value))
				$checked_value = strval(intval($value));
		}
		else if(in_array($type, array('int','order','many_to_one')) && is_numeric($value))
			$checked_value = strval(intval($value));
		else if($type == 'float' && is_numeric($value))
			$checked_value = strval(floatval($value));
		else if(in_array($type, array('bool','image','file','multi_images')) && ($value == '0' || $value == '1'))
			$checked_value = $value;
		else if($type == 'date' && I18n :: checkDateFormat($value))
				$checked_value = $value;
		else if($type == 'date_time' && I18n :: checkDateFormat($value, "with-time"))
				$checked_value = $value;
				
		return $checked_value;
	}
	
	public function getParamsForSQL()
	{
		$conditions = self :: NUMERIC_CONDITIONS;
		$sql = [];

		 //Sql query construction		
		foreach($this -> fields as $name => $data)
		{
			if(isset($data['value']) && !is_array($data['value']))
				$data['value'] = htmlspecialchars($data['value'], ENT_QUOTES);
			
			if($data['type'] == 'many_to_one' || $data['type'] == 'many_to_many')
				continue; //This filters we apply in core class
			else if(in_array($data['type'], ['int','float','order'])) //Numeric intervals
			{
				if(isset($data['conditions'], $data['value']) && is_array($data['value']))
					foreach(['from', 'to'] as $key)
						if(isset($data['value'][$key], $data['conditions'][$key]))
							if(isset($conditions[$data['conditions'][$key]]) && $data['value'][$key] !== '')
								$sql[] = "`".$name."`".$conditions[$data['conditions'][$key]]."'".$data['value'][$key]."'";
			}
			else if($data['type'] == 'date' || $data['type'] == 'date_time') //Date values
			{
				if(isset($data['value']['from']) && $data['value']['from'])
					$sql[] = "`".$name."`>='".I18n :: dateForSQL($data['value']['from'])."'";
				
				if(isset($data['value']['to']) && $data['value']['to'])
					$sql[] = "`".$name."`<='".(I18n :: dateForSQL($data['value']['to']))."'";
			}
			else if(($data['type'] == 'enum' || $data['type'] == 'bool') && 
					 isset($data['value']) && $data['value'] != '') //Enum values
			{
				if($data['value'] == "*")
					$sql[] = "`".$name."`!='' AND `".$name."`!='0' AND `".$name."` IS NOT NULL";
				else if($data['value'] == "-")
					$sql[] = "(`".$name."`='' OR `".$name."`='0' OR `".$name."` IS NULL)";
				else
					$sql[] = "`".$name."`='".$data['value']."'";
			}			
			else if(in_array($data['type'], array('image','file','multi_images')) && isset($data['value']))
				$sql[] = $data['value'] ? "`".$name."` LIKE '%.%'"  : "`".$name."`=''";
			else if($data['type'] == 'parent' && isset($data['value']) && $data['value'] != '')
			{
				if($data['value'] != 'all')
					$sql[] = "`".$name."`='".$data['value']."'";
			}
			else if($data['type'] == 'group' && isset($data['value']) && $data['value'])
			{
				if($data['value'] == "*")
					$sql[] = "`".$name."`!='' AND `".$name."` IS NOT NULL";
				else if($data['value'] == "-")
					$sql[] = "(`".$name."`='' OR `".$name."` IS NULL)";
				else
				{
					$operator = Database :: $adapter -> regularExpression();
					$value = intval($data['value']);
					
					$regexp = "`".$name."` ".$operator." '^".$value."$' OR ";
					$regexp .= "`".$name."` ".$operator." '^".$value.",' OR ";
					$regexp .= "`".$name."` ".$operator." ',".$value."$' OR ";
					$regexp .= "`".$name."` ".$operator." ',".$value.",'";					
					$sql[] = "(".$regexp.")";
				}
			}			
			else if(isset($data['value']) && $data['value'] != '') //String values
			{
				if(Registry :: get('DbEngine') == 'sqlite')
					$sql[] = "`".$name."` REGEXP '".$data['value']."'";
				else
					$sql[] = "`".$name."` LIKE '%".$data['value']."%'";
			}
		}

		return implode(" AND ", $sql); //Glues the params into one string
	}
	
	public function setUrlParams(string $url_params)
	{
		if($url_params)
			$this -> url_params = "?".$url_params;	
	} 
	
	public function getUrlParams(string $in_admin = '')
	{
		//Makes string of GET params
		$params = [];
		
		if(!count($this -> fields))
			return;
				
		if($in_admin === 'admin')
		{
			foreach($this -> fields as $name => $data)
				if($data['type'] == 'date' || $data['type'] == 'date_time')
				{
					if(isset($data['value']['from']) && $data['value']['from'])
						$params[$name.'-from'] = $data['value']['from'];
	
					if(isset($data['value']['to']) && $data['value']['to'])
						$params[$name.'-to'] = $data['value']['to'];
				}
				else if(in_array($data['type'], array('int','float','order','many_to_one')))
				{
					if(isset($data['conditions']) && is_array($data['conditions']))
						foreach(['from', 'to'] as $key)
							if(isset($data['value'][$key], $data['conditions'][$key]))
							{
								$params[$name.'-'.$key] = $data['value'][$key];
								$params[$name."-cond-".$key] = $data['conditions'][$key];
							}
				}
				else if(isset($data['value']) && $data['value'] != '')
					$params[$name] = $data['value'];
		}
		else
		{
			foreach($this -> fields as $name => $data)
				if(in_array($data['type'], array('date','date_time','int','float','order')) &&
				    (!isset($data['display_single_field']) || !$data['display_single_field']))
				{
					if(isset($data['conditions']['gte']) && $data['conditions']['gte'] != '')
						$params[$name.'-from'] = $data['conditions']['gte'];
					
					if(isset($data['conditions']['lte']) && $data['conditions']['lte'] != '')
						$params[$name.'-to'] = $data['conditions']['lte'];
				}
				else if(isset($data['value']) && $data['value'] != '')
					$params[$name] = $data['value'];
		}
		
		$result = [];
		
		foreach($params as $name => $value)
			$result[] = $name."=".($data['type'] != "enum" ? urlencode($value) : $value);
		
		return implode('&', $result);
	}

	public function addUrlParams(string $path)
   	{
   		if($this -> hasParams())
   		{
			$path .= (strpos($path, "?") === false) ? "?" : "&";     
       		$path .= $this -> getUrlParams();
   		}
		
		return $path;
   	}
   	
   	public function setCaption(string $field, string $caption)
   	{
   		if(isset($this -> fields[$field]))
   			$this -> fields[$field]["caption"] = $caption;
   		
   		return $this;
   	}
   	
   	public function getValue(string $field)
   	{
   		if(isset($this -> fields[$field]))
   			if(in_array($this -> fields[$field]['type'], array('date','date_time','int','float','order')) && 
   				(!isset($this -> fields[$field]['display_single_field']) || !$this -> fields[$field]['display_single_field']))
   			{
   				$arguments = func_get_args();
   				$condition = (isset($arguments[1]) && $arguments[1]) ? $arguments[1] : false;
   				
   				if($condition == "from")
   					$condition = "gte";
   				else if($condition == "to")
   					$condition = "lte";
   				
   				if($condition && isset($this -> fields[$field]['conditions'][$condition]))
   					return $this -> fields[$field]['conditions'][$condition];
   			}
   			else if(isset($this -> fields[$field]['value']) && $this -> fields[$field]['value'] != '')
   				return $this -> fields[$field]['value'];
   	}
   	
	public function setValue(string $field, mixed $value, string $condition = '')
   	{
   		if($condition === "from" || $condition === "to")
   			$condition = $condition === "from" ? "gte" : "lte";
   		
   		if(isset($this -> fields[$field]))
   		{
   			if(($this -> fields[$field]['type'] == "enum" || $this -> fields[$field]['type'] == "many_to_many") && 
   				isset($this -> fields[$field]["display_checkbox"]) && $this -> fields[$field]["display_checkbox"])
   			{
   				$checked_values = [];
   				 
   				foreach(explode(",", $value) as $checked)
   					if(trim($checked) && array_key_exists(trim($checked), $this -> fields[$field]["values_list"]))
   						$checked_values[] = trim($checked);
   				
   					 
				$this -> fields[$field]["value"] = count($checked_values) ? implode(",", $checked_values) : "";
				
				return $this;
   			}

  		    $value = $this -> checkFieldValue($this -> fields[$field]['type'], $value, $this -> fields[$field]);
   				   			
   		    if(in_array($this -> fields[$field]['type'], array('date','date_time','int','float','order')))
	   		{	   			
	   			if($condition !== '')
	   				$this -> fields[$field]['conditions'][$condition] = $value;
	   			else
	   				$this -> fields[$field]['value'] = $value;
	   		}
	   		else
	   			$this -> fields[$field]['value'] = $value;
   		}
   		
   		return $this;
   	}	
	
	public function displayAdminFiltersFieldsSelects(mixed $default_filters, mixed $show_empty_default_filters)
	{
		$html = ["add" => [], "remove" => []];
		$count = 0;
		$limit = (count($this -> fields) > 7) ? 7 : count($this -> fields);
		$any_applied = $this -> ifAnyFilterApplied();
		$default_filters = (is_array($default_filters) && count($default_filters)) ? $default_filters : null;
		$sorted_filters = [];
		
		foreach($this -> fields as $name => $data)
			$sorted_filters[$data["caption"]] = $name;
		
		foreach($sorted_filters as $caption => $name)
		{
			if(!$default_filters) //No special default filters
				$key = (($any_applied && !$this -> ifApplied($name)) || (!$any_applied && ++ $count > $limit)) ? "add" : "remove";
			else
				if(!$any_applied) //No applied filters show only default ones
					$key = in_array($name, $default_filters) ? "remove" : "add";
				else
					if($this -> ifApplied($name)) //Show applied filter
						$key = "remove";
					else //Adds default filters if needed
						$key = ($show_empty_default_filters && in_array($name, $default_filters)) ? "remove" : "add";

			$html[$key][$caption] = "<option value=\"".$name."\">".$caption."</option>\n";
		}
		
		//Ordering the list by a-z
		ksort($html["add"]);
		ksort($html["remove"]);
		
		return ["add" => implode("", $html["add"]), "remove" => implode("", $html["remove"])];
	}
	
	public function displayAdminFilters(mixed $default_filters, mixed $show_empty_default_filters)
	{
		$count = 0;
		
		$limit = (count($this -> fields) > 7) ? 7 : count($this -> fields);
		$any_applied = $this -> ifAnyFilterApplied();
		
		if(!is_array($default_filters) && array_key_exists($default_filters, $this -> fields))
			$only_filter = $default_filters;
		else 
			$only_filter = false;
			
		$default_filters = (is_array($default_filters) && count($default_filters)) ? $default_filters : false;
		
		if($only_filter) //Only one filter (adding in AJAX)
			$filters_to_show = array($only_filter);
		else //Many filters to show
		{
			$filters_to_show = []; //Array of current filters
			
			if(!$default_filters) //If no option of default filters
			{
				foreach($this -> fields as $name => $data) //Collecting only applied or regular default filters 
					if(($any_applied && !$this -> ifApplied($name)) || (!$any_applied && ++ $count > $limit))
						continue;
					else
						$filters_to_show[] = $name;
			}
			else //We set up the needed default filters 
			{
				if(!$any_applied) //No filters applied we show only default filters
					foreach($default_filters as $name)
						if(array_key_exists($name, $this -> fields))
							$filters_to_show[] = $name;
								
				if($any_applied) //Some filters set we show them first
					foreach($this -> fields as $name => $data)
						if($this -> ifApplied($name))
							$filters_to_show[] = $name;
						
				//If we have the option to show not applied default filters
				if($any_applied && $show_empty_default_filters)
					foreach($default_filters as $name)
						if(array_key_exists($name, $this -> fields) && !in_array($name, $filters_to_show))
							$filters_to_show[] = $name; //Adds missing default filters
			}
		}

		$html = '';

		foreach($filters_to_show as $name) //Creates html for admin panel filters fields
		{
			$data = $this -> fields[$name];

			$html .= "<div class=\"filter-name\" id=\"filter-".$name."\">".$data['caption']."</div>\n";
			$html .= "<div class=\"filter-input\">\n";
			$html .= $this -> model -> getElement($name) -> displayAdminFilter($data);
			$html .= "</div>\n";
		}

		return $html;
	}
	
	static public function createSelectTag(string $name, array $options, mixed $selected, string $extra = '')
	{
		$html = "<select".($name ? " name=\"".$name."\"" : "");
		
		if($extra !== "backend")
			$html .= " id=\"filter-".$name."\"";
		
		$html .= ">\n";

		if($extra !== '' && $extra !== "backend")
			$html .= "<option value=\"\">".$extra."</option>\n";

		foreach($options as $name => $value)
		{
			$html .= "<option value=\"".$value."\"";
			
			if($selected != "" && strval($selected) == $value)
				$html .=  " selected=\"selected\"";
				
			$html .= ">".$name."</option>\n";
		}

		return $html."</select>\n";
	}
	
	public function getComplexFilters()
	{
		$filters = [];
		
		foreach($this -> fields as $name => $data)
			if($data['type'] == 'many_to_one')
			{
				if(isset($data['value'], $data['conditions']) && is_array($data['conditions']) && count($data['conditions']))
					$filters[$name] = $data;
			}
			else if($data['type'] == 'many_to_many' && isset($data['value']) && $data['value'] != '')
				$filters[$name] = $data;
		
		return $filters;
	}
	
	public function ifFilteredByAllParents()
	{
		foreach($this -> fields as $name => $data)
			if($data['type'] == 'parent' && isset($data['value']) && $data['value'] == 'all')
				return $data['caption'];
		
		return false;
	}
	
	public function ifApplied(string $name)
	{
		return isset($this -> fields[strtolower($name)]['value']);
	}
	
	public function ifAnyFilterApplied()
	{
		$conditions = array_keys(self :: NUMERIC_CONDITIONS);
		
		foreach($this -> fields as $data)
			if(isset($data['value']) && $data['value'] != '')
				return true;
			else if(isset($data['conditions']) && count($data['conditions']))
				foreach($conditions as $key)
					if(isset($data['conditions'][$key]) && $data['conditions'][$key] != '')
						return true;
		
		return false;
	}
	
	public function hasParams()
	{
		return $this -> ifAnyFilterApplied();
	}

	/**
	 * Returns filters conditions for sql query constructor.
	 * @return array conditions for select() and find() methods, like ['active' => '1', 'name' => 'hello']
	 */
   	public function getConditions()
   	{
		//To transform the condition
   		$conditions = self :: NUMERIC_CONDITIONS; 
   		
   		$sql = [];
   		
   		foreach($this -> fields as $name => $data)
   			if(in_array($data['type'], ['int','float','order','enum','bool','date','date_time', 'parent']) && 
   			   isset($data['value']) && $data['value'] != '' && !isset($data['conditions']))
   			{
   				if($data['type'] == 'date' || $data['type'] == 'date_time')
   					$sql[$name] = I18n :: dateForSQL($data['value']); //Single field with no intervals
   				else if($data['type'] == 'enum' && isset($data["display_checkbox"]) && $data["display_checkbox"])
   					$sql[$name."->in"] = $data['value']; //Multiple choise enum filter				
   				else
					$sql[$name] = $data['value'];
   			}
   			else if(in_array($data['type'], ['image','file','multi_images']) && 
   				isset($data['value']) && $data['value'] != '')
   			{
   				if($data['value'] == 0)
				   $sql[$name."->not-like"] = "."; //If image string value was not filled
   				else
   					$sql[$name."->like"] = "."; //If we search image file
   			}
   			else if($data['type'] == "many_to_many" && isset($data['value']) && $data['value'] != '')
   			{
				$element = $this -> model -> getElement($name);
				$sql[$name.'->m2m'] = $element -> getValuesForFilter($data['value']);
   			}
			else if(in_array($data['type'], ['char','text','phone','url','redirect','email']) && 
					isset($data['value']) && $data['value'] != '')
				$sql[$name."->like"] = htmlspecialchars($data['value'], ENT_QUOTES);
			else if(isset($data['conditions']))
				foreach($data['conditions'] as $condition => $value)
				{
					if($data['type'] == 'date' || $data['type'] == 'date_time')
						$value = I18n :: dateForSQL($value);
					
					
					$sql[$name.$conditions[$condition]] = $value;
				}
	   		
		return $sql;
   	}
   	
   	public function display(mixed $allowed = '', string $condition = '')
   	{
   		$fields = array_keys($this -> fields);
   		$single_field = $one_interval_part = false;
   		$html = '';

		if(is_array($allowed)) //If we pass fields names to display
			$fields = $allowed;
		else if(array_key_exists($allowed, $this -> fields)) //If only one field to display
		{
				$single_field = $allowed;
				$fields = [$allowed];
		}
		
		//If we display only one part of interval filter (from or to)		
		if($single_field && ($condition === "from" || $condition === "to"))
			$one_interval_part = $condition;			
   			
   		foreach($fields as $name)
   			if(array_key_exists($name, $this -> fields))
	   		{
	   			$data = $this -> fields[$name];
	   			
	   			if($data['type'] == 'many_to_many' && (!isset($data['display_checkbox']) || !$data['display_checkbox']))
	   			{
	   				$value = (isset($data["value"]) && $data["value"] != "") ? $data["value"] : "";
	   				$empty_value = I18n :: locale('not-defined');
	   				
	   				if(isset($data['empty_value']) && $data['empty_value'])
	   					$empty_value = $data['empty_value'];
	   				
	   				$html .= $this -> createSelectTag($name, array_flip($data['values_list']), $value, $empty_value);

	   				continue;
	   			}
					
				//If its a bool filter in checkbox input
	   			if(in_array($data['type'], ['bool','image','file','multi_images']))
		   			if(isset($data['display_checkbox']) && $data['display_checkbox'])
		   			{
		   				$html .= "<div class=\"filter-input filter-checkbox\">\n".$this -> displayCheckbox($name);
		   				$html .= "<label for=\"filter-".$name."\">".$data['caption']."</label>\n</div>\n";
						
		   				continue;
		   			}					
	   			
	   			if(!$single_field) //Interval field which shuld look like one input
	   				$html .= "<div class=\"filter-name\">".$data['caption']."</div>\n";
	   			
				if(!$single_field)
					$html .= "<div class=\"filter-input\">\n";
					
				$value = (isset($data['value']) && $data['value'] != '') ? htmlspecialchars($data['value'], ENT_QUOTES) : ''; 
				
				if(in_array($data['type'], ['bool','image','file','multi_images']))
				{
					$select_data = [
						I18n :: locale('not-defined') => '', 
						I18n :: locale('yes') => '1', 
						I18n :: locale('no') => '0'
					];

					$html .= $this -> createSelectTag($name, $select_data, $value);
				}
				else if(in_array($data['type'], ['char','url','redirect','email','text','phone']))
					$html .= "<input class=\"text\" id=\"filter-".$name."\" type=\"text\" name=\"".$name."\" value=\"".$value."\" />\n";
				else if($data['type'] == 'enum' || $data['type'] == 'parent')
				{
					$select_data = count($data['values_list']) ? array_flip($data['values_list']) : [];
					$empty_value = (isset($data['empty_value']) && $data['empty_value']) ? $data['empty_value'] : false;
					
					if(isset($data['long_list']) && $data['long_list'])
					{
						$value = $value_title = '';
						
						if(isset($data["value"]) && $data["value"])
						{
							$foreign_key = (isset($data["foreign_key"]) && $data["foreign_key"]) ? $data["foreign_key"] : false;
							$object_params = array("long_list" => true, "foreign_key" => $foreign_key);
							$object_params["values_list"] = $data["values_list"];
							
							if($data['type'] == 'enum')
								$object = new EnumModelElement($data["caption"], "enum", $name, $object_params);
							else
							{
								$object_params["model"] = $data["table"];
								$object = new ParentModelElement($data["caption"], "parent", $name, $object_params);
							}

							if($object -> checkValue($data["value"]))
							{
								$value = $data["value"];
								$value_title = $object -> getValueName($value);
							}
						}
			
						$html_ = "<input class=\"autocomplete-input\" type=\"text\"  value=\"".$value_title."\" />\n";
		    			$html_ .= "<input type=\"hidden\" name=\"".$name."\" value=\"".$value."\" />\n";		    
					}
					else if($data['type'] == 'enum' && isset($data['display_checkbox']) && $data['display_checkbox'])
					{
						if(isset($data['empty_value']) && $data['empty_value'] && 
						   isset($data['empty_checkbox']) && $data['empty_checkbox'])
						{
							$values = array('' => $data['empty_value']);
							
							foreach($data['values_list'] as $k => $v)
								$values[$k] = $v;								
						}
						else
							$values = $data['values_list'];
						
						$object_params = array("values_list" => $values,
											   "multiple_choice" => $data['display_checkbox']);
						
						$object = new EnumModelElement($data["caption"], "enum", $name, $object_params);
						
						if(isset($data["value"]) && $data["value"])
							$object -> setValue($data["value"]);
						
						$html_ = $object -> displayAsCheckboxes();
					}
					else if($data['type'] == 'enum' && isset($data['display_radio']) && $data['display_radio'])
					{						
						$checked = (isset($data["value"]) && $data["value"] != "") ? $data["value"] : "";
						$columns = intval($data['display_radio']);
						$html_ = Service :: displayOrderedFormTable($data['values_list'], $columns, $checked, $name, "radio");
					}
					else
						$html_ = $this -> createSelectTag($name, $select_data, $value, $empty_value);
					
					$html .= $html_;
				}
	   			else if(in_array($data['type'], ['date','date_time','int','float','order']))
				{
					if(isset($data['display_single_field']) && $data['display_single_field'])
						$html .= $this -> displaySingleField($name);
					else
					{						
						if($data['type'] == 'date' || $data['type'] == 'date_time')
						{
							$text_from = I18n :: locale("date-from");
							$text_to = I18n :: locale("date-to");
						}
						else
						{
							$text_from = I18n :: locale("number-from");
							$text_to = I18n :: locale("number-to");						
						}
						
						$value = (isset($data['conditions']['gte']) && $data['conditions']['gte'] != '') ? $data['conditions']['gte'] : '';
						
						if(!$one_interval_part)
							$html .= "<span id=\"filter-".$name."-title-from\" class=\"filter-interval-name\">".$text_from."</span>\n";
						
						if(!$one_interval_part || $one_interval_part == "from")
						{
							$html .= "<input class=\"text interval\" id=\"filter-".$name."-from\" type=\"text\" ";
							$html .= "name=\"".$name."-from\" value=\"".$value."\" />\n";
						}
						
						$value = (isset($data['conditions']['lte']) && $data['conditions']['lte'] != '') ? $data['conditions']['lte'] : '';
						
						if(!$one_interval_part)
							$html .= "<span id=\"filter-".$name."-title-to\" class=\"filter-interval-name\">".$text_to."</span>\n";
						
						if(!$one_interval_part || $one_interval_part == "to")
						{						
							$html .= "<input class=\"text interval\" id=\"filter-".$name."-to\" type=\"text\" ";
							$html .= "name=\"".$name."-to\" value=\"".$value."\" />\n";
						}
					}
				}
				else if($data['type'] == 'many_to_many')
				{
					$checked = (isset($data["value"]) && $data["value"]) ? explode(",", $data["value"]) :  [];
					$html .= Service :: displayOrderedFormTable($data['values_list'], $data['display_checkbox'], $checked, $name);
				}
				
				if(!$single_field)
					$html .= "</div>\n";
	   		}
	   		
	   	return $html;
   	}
	
	public function displayCheckbox(string $field)
   	{
   		$html = $checked = '';
   		$allowed_types = ['bool','image','file','multi_images'];
   		
   		if(isset($this -> fields[$field]) && in_array($this -> fields[$field]['type'], $allowed_types))
   		{
   			if(isset($this -> fields[$field]['value']) && $this -> fields[$field]['value'] != '')
   				$checked = " checked=\"checked\"";
   			
   			$html .= "<input id=\"filter-".$field."\" type=\"checkbox\" ";
			$html .= "name=\"".$field."\" value=\"1\"".$checked." />\n";
   		}
   		
   		return $html;
   	}
   	
	public function displaySingleField(string $field, string $condition = '')
   	{
   		$html = '';
   		$from_or_to = false;

   		if($condition === "from" || $condition === "to")
   			$from_or_to = $condition;		
   		
   		if(isset($this -> fields[$field]) && 
   		   in_array($this -> fields[$field]['type'], array('date','date_time','int','float','order')))
   		{
   			$value = '';
   			
   			if($from_or_to)
   			{
   				$condition = ($from_or_to == "from") ? "gte" : "lte";
   				
   				if(isset($this -> fields[$field]['conditions'][$condition]) && $this -> fields[$field]['conditions'][$condition] != '')
   					$value = $this -> fields[$field]['conditions'][$condition];
   				
	   			$html .= "<input class=\"text\" id=\"filter-".$field."-".$from_or_to."\" ";
	   			$html .= "type=\"text\" name=\"".$field."-".$from_or_to."\" ";
   			}
   			else
   			{
   				if(isset($this -> fields[$field]['value']) && $this -> fields[$field]['value'] != '')
   					$value = $this -> fields[$field]['value'];
   				
   				$html = "<input class=\"text\" id=\"filter-".$field."\" type=\"text\" name=\"".$field."\" ";
   			}
			
   			$html .= "value=\"".$value."\" />\n";
   		}
   		
   		return $html;
   	}   	
   	
   	public function setDisplayCheckbox(string $field)
   	{
   		$allowed_types = ['bool','image','file','multi_images'];
   		
   		if(isset($this -> fields[$field]) && in_array($this -> fields[$field]['type'], $allowed_types))
   			$this -> fields[$field]['display_checkbox'] = true;
   			
   		return $this;
   	}
   	
   	public function setDisplaySingleField(string $field)
   	{
		$allowed_types = ['date','date_time','int','float','order'];

		if(isset($this -> fields[$field]) && in_array($this -> fields[$field]['type'], $allowed_types))
			$this -> fields[$field]['display_single_field'] = true;
   			
   		return $this;
   	}
   	
   	public function filterValuesList(string $field, array $params)
   	{
   		$allowed_types = ['enum', 'parent', 'many_to_many'];
   		
   		if(isset($this -> fields[$field]) && $this -> model && count($params) && 
   		   in_array($this -> fields[$field]['type'], $allowed_types))
   		{
   			$element = $this -> model -> getElement($field);
   			$class = $this -> model -> getModelClass();
   			$this -> fields[$field]["values_list"] = $element -> filterValuesList($params, $class) -> getValuesList();
   			
   			if(!isset($this -> fields[$field]["long_list"]) || !$this -> fields[$field]["long_list"])
   				$this -> removeUnavailableValues($field);
   		}
		
		return $this;
   	}
	
	public function addOptionToValuesList(string $field, mixed $value, string $title)
   	{
   	    if(isset($this -> fields[$field]))
   	        if($this -> fields[$field]["type"] == "enum" || $this -> fields[$field]["type"] == "parent")
   	        {
   	            $this -> fields[$field]["values_list"][$value] = $title;
   	            
   	            if(isset($_GET[$field]) && $_GET[$field] == $value)
   	                $this -> fields[$field]["value"] = $value;
   	        }
   	        
        return $this;
   	}

	public function removeOptionFromValuesList(string $field, mixed $key)
   	{
   	    if(isset($this -> fields[$field]))
   	        if($this -> fields[$field]["type"] == "enum" || $this -> fields[$field]["type"] == "parent")
   	        	if(array_key_exists($key, $this -> fields[$field]["values_list"]))
   	        	{
   	        		unset($this -> fields[$field]["values_list"][$key]);
   	        		$this -> removeUnavailableValues($field);
   	        	}
		
        return $this;
   	}

   	public function getValuesList(string $field)
   	{
   	    if(isset($this -> fields[$field]))
   	        if($this -> fields[$field]["type"] == "enum" || $this -> fields[$field]["type"] == "parent")
   	        	return $this -> fields[$field]["values_list"];
   	}   	

   	public function setValuesList(string $field, array $values_list)
   	{
   	    if(isset($this -> fields[$field]) && is_array($values_list))
   	        if($this -> fields[$field]["type"] == "enum" || $this -> fields[$field]["type"] == "parent")
   	        {
   	        	$this -> fields[$field]["values_list"] = $values_list;
   	        	$this -> removeUnavailableValues($field);
   	        }
   	}

   	private function removeUnavailableValues(string $field)
   	{
    	if(isset($this -> fields[$field]["value"]))
    	{
    		$parts = explode(',', $this -> fields[$field]["value"]);

    		foreach($parts as $index => $part)
    			if(!array_key_exists($part, $this -> fields[$field]["values_list"]))
    				unset($parts[$index]);

    		if(!count($parts))
    			unset($this -> fields[$field]["value"]);
    		else
    			$this -> fields[$field]["value"] = implode(',', $parts);
    	}
   	}
	
	public function setEnumEmptyValueTitle(string $field, string $title)
   	{
   		if(isset($this -> fields[$field]) && $this -> fields[$field]['type'] == "enum")
   			if(isset($this -> fields[$field]['empty_value']))
   				$this -> fields[$field]['empty_value'] = $title;
				
		return $this;
   	}
   	
   	public function setManyToManyEmptyValueTitle(string $field, string $title)
   	{
   		if(isset($this -> fields[$field]) && $this -> fields[$field]['type'] == "many_to_many")
			$this -> fields[$field]['empty_value'] = $title;

		return $this;
   	}
	
	public function setDisplayEnumRadio(string $field, int $columns)
   	{
   		$html = "";
   		
   	   	if(isset($this -> fields[$field]))
   	   		$this -> fields[$field]["display_radio"] = $columns;
   		
   		return $this;
   	}
   	
   	public function setDisplayEnumCheckboxes(string $field, int $columns, bool $empty_checkbox = false)
   	{
   		$db = Database :: instance();
		
   		if(isset($this -> fields[$field]) && 
   		   ($this -> fields[$field]["type"] == "enum" || $this -> fields[$field]["type"] == "many_to_many"))
   		{
   			$this -> fields[$field]["display_checkbox"] = intval($columns);
   			
   			if($empty_checkbox)
   				$this -> fields[$field]["empty_checkbox"] = true;
   			
			$checked_values = [];
   			
			if(isset($_GET[$field]) && $_GET[$field] != '') //If we have glued keys in GET
			{
				$checked_values = [];
				
				foreach(explode(",", $_GET[$field]) as $value)
					if($value && isset($this -> fields[$field]["foreign_key"], $this -> fields[$field]["long_list"]) && 
					   $this -> fields[$field]["long_list"])
					{
						if($db -> getCount($this -> fields[$field]["foreign_key"], "`id`='".intval($value)."'"))
							$checked_values[] = intval($value);
					}
					else if($value && array_key_exists($value, $this -> fields[$field]["values_list"]))
						$checked_values[] = $value;
				
				$this -> fields[$field]["value"] = count($checked_values) ? implode(",", $checked_values) : "";
			}
			else
			{
		   		foreach($this -> fields[$field]["values_list"] as $key => $value)
					if(isset($_GET[$field."-".$key]) && $_GET[$field."-".$key] == $key)
						$checked_values[] = $key;
						
				if(count($checked_values))
					$this -> fields[$field]["value"] = implode(",", $checked_values);						
			}	
   		}
   		
   		return $this;
   	}
   	
   	public function setDisplayCheckboxTable(string $field, int $columns)
   	{
   		return $this -> setDisplayEnumCheckboxes($field, $columns);
   	}
   	
   	public function allowChangeOrderLinkedWithEnum(string $field)
   	{
   		return ($this -> ifApplied($field) && $this -> getValue($field) != '*' && $this -> getValue($field) != '-');
   	}
}
