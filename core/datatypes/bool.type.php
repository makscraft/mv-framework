<?php
/**
 * Bool datatype class. Stores value in database as 0/1 integer value.
 */
class BoolModelElement extends ModelElement
{
	/**
	 * Default value when creating new record.
	 * @var bool
	 */
	protected $on_create = false;
	
	/**
	 * Allows quick change of value by clicking on tumbler in records table.
	 * @var bool
	 */
	protected $quick_change = true;

	public function prepareValue()
	{
		$this -> value = boolval($this -> value) ? 1 : 0;
		
		return $this -> value;
	}
	
	public function validate()
	{
		if($this -> required && !$this -> value)
			$this -> error = $this -> chooseError("required", "{error-required-bool}");
		
		return $this;
	}
	
	public function displayHtml()
	{
		$html = "<input type=\"checkbox\" name=\"".$this -> name."\"".$this -> addHtmlParams();
		$html .= $this -> value ? " checked=\"checked\" />" : " />";
		$html .= $this -> addHelpText();
		
		return $html;
	}

	public function displayHtmlTumbler()
	{
		$html = "<label class=\"switch\">";
		$html .= "<input type=\"checkbox\" name=\"".$this -> name."\"".$this -> addHtmlParams();
		$html .= $this -> value ? " checked=\"checked\" />" : " />";
		$html .= "<span class=\"slider\"></span>";
		$html .= "</label>\n";
		$html .= $this -> addHelpText();
		
		return $html;
	}
	
	public function setValue($value)
	{
		$this -> value = intval((bool) $value);

		return $this;
	}
			
	public function getValue()
	{ 
		return intval($this -> value);
	}

	public function displayAdminFilter(mixed $data)
	{
		return self :: createAdminFilterHtml($this -> name, $data);
	}

	static public function createAdminFilterHtml(string $name, mixed $data)
	{
		$options = [
			I18n :: locale('not-defined') => '', 
			I18n :: locale('yes') => '1', 
			I18n :: locale('no') => '0'
		];

		$value = isset($data['value']) ? (intval($data['value']) ? '1' : '0') : '';

		return Filter :: createSelectTag($name, $options, $value, 'backend');
	}
}
