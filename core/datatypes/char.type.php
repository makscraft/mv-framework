<?php
/**
 * Char datatype class, parent for many other datatypes.
 */
class CharModelElement extends ModelElement
{
	protected $length;
	
	protected $min_max_length;
	
	protected $regexp;
	
	protected $captcha;
	
	protected $hidden = false;

	protected $rules = [];

	protected $allowed_rules = [];

	protected $placeholder = '';

	public function setValue(mixed $value)
	{
		$this -> value = trim(strval($value));
				    
		return $this;
	}

	public function prepareValue()
	{
		$this -> value = trim(strval($this -> value));
		
		return $this -> value;
	}
	
	public function validate()
	{
		if($this -> required && strval($this -> value) === '') //If we check required value
		{
			
			if($this -> type == "enum" || $this -> type == "many_to_many" || $this -> type == "group")
				$this -> error = $this -> chooseError("required", "{error-required-enum}");
			else if($this -> type == "file")
				$this -> error = $this -> chooseError("required", "{error-required-file}");
			else if($this -> type == "image")
				$this -> error = $this -> chooseError("required", "{error-required-image}");
			else if($this -> type == "multi_images")
				$this -> error = $this -> chooseError("required", "{error-required-multi-images}");
			else
				$this -> error = $this -> chooseError("required", "{error-required}");
		}
		else if($this -> unique && $this -> value) //If we check unique value
		{
			$arguments = func_get_args();
			
			//Class of model to check the unique value
			$class_name = (isset($arguments[0]) && $arguments[0]) ? $arguments[0] : '';
			
			//If id was passed (update action) we should know it
			$id = (isset($arguments[1]) && $arguments[1]) ? intval($arguments[1]) : null;
			
			if($class_name)
				if($this -> checkUniqueValue($class_name, $id)) //If the value is not unique
					$this -> error = $this -> chooseError("unique" ,"{error-unique-value}");
		}
		
		if(!$this -> error && strval($this -> value) !== '')
			if($this -> length && mb_strlen($this -> value, "utf-8") != $this -> length)
				$this -> error = $this -> chooseError("length", "{error-length}");
			else if($this -> min_length && mb_strlen($this -> value, "utf-8") < $this -> min_length)
				$this -> error = $this -> chooseError("min_length", "{error-min-length}");
			else if($this -> max_length && mb_strlen($this -> value, "utf-8") > $this -> max_length)
				$this -> error = $this -> chooseError("max_length", "{error-max-length}");
			else if($this -> regexp && !preg_match($this -> regexp, $this -> value))
				$this -> error = $this -> chooseError("regexp", "{error-regexp}");
			
		return $this;
	}

	public function displayHtml()
	{
		$value = $this -> value ? $this -> value : '';
		$value = htmlspecialchars($value, ENT_QUOTES);
		$value = Service :: cleanHtmlSpecialChars($value);
		$value = $this -> captcha ? '' : $value;
		
		$type = $this -> hidden ? "hidden" : "text";
		$css = $this -> addHtmlParams() ? $this -> addHtmlParams() : " class=\"form-text-input\"";

		if($this -> placeholder !== '')
			$css .= ' placeholder="'.$this -> placeholder.'"';
		
		$html = "<input type=\"".$type."\" name=\"".$this -> name."\"";
		$html .= $css." value=\"".$value."\" />\n".$this -> addHelpText();
		
		return $html;
	}
	
	public function checkUniqueValue($class_name, $id)
	{
		$object = new $class_name(); //Object of module
		
		$query = "SELECT COUNT(*) FROM `".$object -> getTable()."` 
				  WHERE `".$this -> name."`=".$object -> db -> secure($this -> value)."";
		
		if($id) //If we update the element
			$query .= " AND `id`!='".$id."'";
			
		return (bool) $object -> db -> getCell($query);
	}
	
	protected function setCaptchaSource($source)
	{
		$registry = Registry :: instance();
		$this -> captcha = $registry -> getSetting("MainPath").$source;
	}
	
	public function setRule($rule)
	{
		if(!count($rule) || !isset($rule[0]) || !in_array($rule[0], $this -> allowed_rules))
			return $this;
			
		if($rule[0] == "unique")
		{
			if(!isset($rule[1]))
				$message = "{error-unique-value}";
			
			$this -> rules[] = array("type" => "unique", "message" => $message);
			
			return $this;
		}
		else
		{
			if(isset($rule[2]))
				$message = $rule[2];
			else if($rule[0] == "min_length")
				$message = "{error-min-length}";
			else if($rule[0] == "max_length")
				$message = "{error-max-length}";
			else if($rule[0] == "must_match")
				$message = "{error-must-match}";
			else
				$message = "{error-".$rule[0]."}";
		}
		
		$value = false;
		
		if($rule[0] == "lenght" || $rule[0] == "min_length" || $rule[0] == "max_length")
			$value = intval($rule[1]);
		else if($rule[0] == "in_array" && count($rule[1]))
			$value = $rule[1];
		else if($rule[0] == "regexp" || $rule[0] == "must_match")
			$value = trim($rule[1]);
		
		if($value)
			$this -> rules[] = array("type" => $rule[1], "value" => $value, "message" => $message);
		
		return $this;
	}

	public function displayAdminFilter(mixed $data)
	{
		$value = isset($data['value']) ? htmlspecialchars($data['value'], ENT_QUOTES) : '';

		return "<input class=\"text\" type=\"text\" name=\"".$this -> name."\" value=\"".$value."\" />\n";
	}
}
