<?php
/**
 * Datetime datatype class, connected with I18n class.
 */
class DateTimeModelElement extends DateModelElement
{
	protected $selects_values = array("dd" => "", "mm" => "", "yyyy" => "", "hh" => "", "mi" => "");
	
	public function validate()
	{
		$this -> unique = false;
		
		parent :: validate();
		
		if(!$this -> display_selects && $this -> value && !I18n :: checkDateFormat($this -> value, "with-time"))
			$this -> error = $this -> chooseError("format", "{error-date-time-format}");
		else if($this -> display_selects && $this -> value)
			foreach(array_keys($this -> selects_values) as $key)
				if($this -> selects_values[$key] === "")
				{
					$this -> error = $this -> chooseError("required", "{error-date-time-values}");
					break;
				}
					
		return $this;
	}

	public function setValue($value)
	{
		if(!$value)
		{
			$this -> value = "";
			$this -> selects_values = array("dd" => "", "mm" => "", "yyyy" => "", "hh" => "", "mi" => "");
			
			return $this;
		}
		
		if($this -> display_selects)
		{
			if(!preg_match("/^\d{4}-\d{2}-\d{2}/", $value))
				$date = $value = I18n :: dateForSQL($value);
			else
				$date = $value;
				
			$date = explode(" ", $date);
			$time = isset($date[1]) ? explode(":", $date[1]) : array(0, 0);	
			$date = explode("-", $date[0]);
			
			if(isset($date[0], $date[1], $date[2], $time[0], $time[1]) 
			   && $date[2] >= 1 && $date[2] <= 31 && $date[1] >= 1 && $date[1] <= 12
			   && $time[0] >= 0 && $time[0] <= 23 && $time[1] >= 0 && $time[1] <= 59)
			{
				$this -> selects_values = array("dd"   => intval($date[2]), 
												"mm"   => intval($date[1]), 
												"yyyy" => intval($date[0]), 
												"hh"   => intval($time[0]), 
												"mi"   => intval($time[1]));
				
				$this -> value = I18n :: dateFromSQL($value);
			}
		}
		else
			parent :: setValue($value);
		
		return $this;
	}	
	
	public function displayHtml()
	{
		if(!$this -> display_selects)
			return str_replace("form-date-field", "form-date-time-field", parent :: displayHtml());

		$html = parent :: displayHtml();
		$value = $this -> selects_values['hh'];
		$error_class = ($value == "") ? " not-selected" : " selected";
		
		$html .= "<select name=\"select-hh-".$this -> name."\" class=\"form-hours-select".$error_class."\">\n";
		$html .= "<option value=\"\">".I18n :: locale('hours')."</option>\n";
		
		for($i = 0; $i <= 23; $i ++)
		{
			$selected = ($value !== "" && $value == $i) ? " selected=\"selected\"" : "";
			$html .= "<option value=\"".$i."\"".$selected.">".($i < 10 ? "0".$i : $i)."</option>\n";
		}
		
		$html .= "</select>\n";
		
		$value = $this -> selects_values['mi'];
		$error_class = ($value == "") ? " not-selected" : " selected";
		
		$html .= "<select name=\"select-mi-".$this -> name."\" class=\"form-minutes-select".$error_class."\">\n";
		$html .= "<option value=\"\">".I18n :: locale('minutes')."</option>\n";
		
		for($i = 0; $i <= 59; $i ++)
		{
			$selected = ($value !== "" && $value == $i) ? " selected=\"selected\"" : "";
			$html .= "<option value=\"".$i."\"".$selected.">".($i < 10 ? "0".$i : $i)."</option>\n";
		}
		
		$html .= "</select>\n";
		
		return $html.$this -> addHelpText();
	}
}
