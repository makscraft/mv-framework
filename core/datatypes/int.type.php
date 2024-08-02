<?php
/**
 * Integer datatype class. Numeric datatype with own properties.
 */
 class IntModelElement extends CharModelElement
{
	protected $format = "/^-?[1-9]\d*$/";
	
	protected $zero_allowed = true;
	
	protected $positive = false;
	
	public function prepareValue()
	{
		$this -> value = intval($this -> value);
		
		return $this -> value;
	}
	
	public function validate()
	{
		$arguments = func_get_args();
		parent :: validate($arguments[0], $arguments[1]);
		
		if(!$this -> zero_allowed && $this -> value == '0')
			$this -> error = $this -> chooseError("zero_allowed", "{error-zero-forbidden}");
		else if($this -> required && $this -> value != '0' && !$this -> value)
			$this -> error = $this -> chooseError("required", "{error-required}");	
		else if($this -> value && !preg_match($this -> format, $this -> value))
			$this -> error = $this -> chooseError("format", "{error-not-".$this -> type."}");
		else if($this -> positive && $this -> value && $this -> value < 0)
			$this -> error = $this -> chooseError("positive", "{error-not-positive}");

		return $this;
	}

	public function displayAdminFilter(mixed $data)
	{
		return self :: createAdminFilterHtml($this -> name, $data);
	}

	static public function createAdminFilterHtml(string $name, mixed $data)
	{
		$options = [I18n :: locale('not-defined') => ''];
		$more = [' &equals; ', ' &ne; ', ' &gt; ', ' &lt; ', ' &ge; ', ' &le; '];
		$more = array_combine($more, array_keys(Filter :: NUMERIC_CONDITIONS));
		$options = array_merge($options, $more);

		$value_from = $data['value']['from'] ?? '';
		$value_to = $data['value']['to'] ?? '';
		$selected_from = $data['conditions']['from'] ?? '';
		$selected_to = $data['conditions']['to'] ?? '';

		$html = "<div class=\"numeric\">\n";
		$html .= Filter :: createSelectTag($name.'-cond-from', $options, $selected_from, 'backend');
		$html .= "<input type=\"text\" name=\"".$name."-from\" value=\"".$value_from."\" />\n";
		$html .= "</div><div class=\"numeric\">\n";
		$html .= Filter :: createSelectTag($name.'-cond-to', $options, $selected_to, 'backend');
		$html .= "<input type=\"text\" name=\"".$name."-to\" value=\"".$value_to."\" />\n";
		$html .= "</div>\n";

		return $html;
	}
} 
