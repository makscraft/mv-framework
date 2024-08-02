<?php
/**
 * Date datatype class, parent for datetime datatype.
 * Connected with I18n class.
 */
class DateModelElement extends CharModelElement
{
	protected $display_selects = false;
	
	protected $now_on_create = true;
	
	protected $selects_values = array("dd" => "", "mm" => "", "yyyy" => "");
	
	protected $years = false;
	
	protected $format = true;

	public function validate()
	{
		parent :: validate();
		
		if(get_class($this) == "DateModelElement")
			if(!$this -> display_selects && $this -> value && !I18n :: checkDateFormat($this -> value))
				$this -> error = $this -> chooseError("format", "{error-date-format}");
			else if($this -> display_selects && $this -> value )
				if(!$this -> selects_values["dd"] || !$this -> selects_values["mm"] || !$this -> selects_values["yyyy"])
					$this -> error = $this -> chooseError("required", "{error-date-values}");
		
		return $this;
	}

	public function displayAdminFilter(mixed $data)
	{
		$value_from = $data['value']['from'] ?? '';
		$value_to = $data['value']['to'] ?? '';
		$css_class = ($data['type'] == "date") ? "form-date-field" : "form-date-time-field";

		$html = "<table class=\"admin-date-time-filter\"><tr><td>\n";
		$html .= I18n :: locale('date-from')."</td><td><input type=\"text\" class=\"".$css_class."\" ";
		$html .= "name=\"".$this -> name."-from\" value=\"".$value_from."\" /></td></tr><tr><td>\n";
		$html .= I18n :: locale('date-to')."</td><td><input type=\"text\"class=\"".$css_class."\" ";
		$html .= "name=\"".$this -> name."-to\" value=\"".$value_to."\" />\n";
		$html .= "</td></tr></table>\n";

		return $html;
	}
		
	public function setDisplaySelects($display_selects) 
	{ 
		$this -> display_selects = $display_selects; 
		return $this; 
	}
	
	public function getDisplaySelects()
	{
		return $this -> display_selects;
	}
	
	public function setValue($value)
	{
		if(!$value)
		{
			$this -> value = "";
			$this -> selects_values = array("dd" => "", "mm" => "", "yyyy" => "");
			
			return $this;
		}
		
		if($this -> display_selects)
		{
			if(!preg_match("/^\d{4}-\d{2}-\d{2}/", $value))
				$date = $value = I18n :: dateForSQL($value);
			else
				$date = $value;
			
			$date = preg_replace("/^(\d{4}-\d{2}-\d{2}).*/", "$1", $date);
			$date = explode("-", $date);
			
			if(isset($date[0], $date[1], $date[2]) && $date[2] >= 1 && $date[2] <= 31 && $date[1] >= 1 && $date[1] <= 12)
			{
				$this -> selects_values = array("dd" => intval($date[2]),
												"mm" => intval($date[1]),
												"yyyy" => intval($date[0]));
				
				$this -> value = I18n :: dateFromSQL($value);
			}
		}
		else
			parent :: setValue($value);
		
		return $this;
	}
	
	public function setSelectValue($key, $value)
	{
		if(array_key_exists($key, $this -> selects_values))
		{
			if(!$this -> value)
				if(get_class($this) == "DateModelElement")
					$this -> value = I18n :: getDateFormat();
				else
					$this -> value = I18n :: getDateTimeFormat().":00";
				
			$this -> selects_values[$key] = ($value != "") ? intval($value) : "";

			$value = ($value < 10) ? "0".$value : $value; 
			
			$this -> value = str_replace($key, $value, $this -> value);
		}
		
		return $this;
	}
	
	public function getValue()
	{
		$arguments = func_get_args();
		
		if(isset($arguments[0]) && $arguments[0] == "sql" && !is_null($this -> value))
		{
			if(preg_match("/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2})?(:\d{2})?$/", $this -> value))
			  	return $this -> value; 
			else
				return I18n :: dateForSQL($this -> value);
		}
		else
			return $this -> value;
	}
	
	public function displayHtml()
	{
		if($this -> display_selects)
			return $this -> displayAsSelects();
		else
		{
			$html = parent :: displayHtml();
			
			if(strpos($html, "form-text-input"))
				return str_replace("form-text-input", "form-text-input form-date-field", $html);
			
			return str_replace("<input", "<input class=\"form-date-field\"", $html);
		}
	}
	
	public function displayAsSelects()
	{
		$value = $this -> selects_values['dd'];
		$error_class = ($value == "") ? " not-selected" : " selected";
		
		$html = "<select name=\"select-dd-".$this -> name."\" class=\"form-day-select".$error_class."\">\n";
		$html .= "<option value=\"\">".I18n :: locale('day')."</option>\n";
		
		for($i = 1; $i <= 31; $i ++)
		{
			$selected = ($value && $value == $i) ? " selected=\"selected\"" : "";
			$html .= "<option value=\"".$i."\"".$selected.">".$i."</option>\n";
		}
		
		$html .= "</select>\n";
		
		$value = $this -> selects_values['mm'];
		$error_class = ($value == "") ? " not-selected" : " selected";
		
		$html .= "<select name=\"select-mm-".$this -> name."\" class=\"form-month-select".$error_class."\">\n";
		$html .= "<option value=\"\">".I18n :: locale('month')."</option>\n";
		
		for($i = 1; $i <= 12; $i ++)
		{
			$selected = ($value && $value == $i) ? " selected=\"selected\"" : "";
			$html .= "<option value=\"".$i."\"".$selected.">".I18n :: getMonth($i)."</option>\n";
		}
		
		$html .= "</select>\n";
		
		$value = $this -> selects_values['yyyy'];
		$error_class = ($value == "") ? " not-selected" : " selected";
		
		$html .= "<select name=\"select-yyyy-".$this -> name."\" class=\"form-year-select".$error_class."\">\n";
		$html .= "<option value=\"\">".I18n :: locale('year')."</option>\n";
		
		$years = $this -> years ? explode(",", $this -> years) : false;
		
		if(isset($years[0], $years[1]) && $years[0] < $years[1] && $years[0] && $years[1])
		{
			$year_from = intval($years[0]);
			$year_to = intval($years[1]);
		}
		else
		{
			$year_from = 1920;
			$year_to = intval(date("Y"));
		}
		
		for($i = $year_to; $i >= $year_from; $i --)
		{
			$selected = ($value && $value == $i) ? " selected=\"selected\"" : "";
			$html .= "<option value=\"".$i."\"".$selected.">".$i."</option>\n";
		}
		
		$html .= "</select>\n";
		
		return (get_class($this) == "DateModelElement") ? $html.$this -> addHelpText() : $html;
	}
}
