<?php
/**
 * Password datatype class. Most properties are inherited from Char datatype.
 */
class PasswordModelElement extends CharModelElement
{
	protected $min_length = 6;
	
	protected $letters_required = false;
	
	protected $digits_required = false;
	
	public function prepareValue()
	{
		parent :: prepareValue();

		if(Registry :: getInitialVersion() < 2.2)
			$this -> value = md5($this -> value);
		
		return $this -> value;
	}

	public function validate()
	{
		$this -> unique = false;
				
		parent :: validate();
				
		if(!$this -> error && $this -> value)
			if($this -> letters_required && !preg_match("/\D/iu", $this -> value))
				$this -> error = "{error-letters-required}";
			else if($this -> digits_required && !preg_match("/\d/", $this -> value))
				$this -> error = "{error-digits-required}";
		
		return $this;
	}
	
    public function displayHtml()
    {
        return str_replace("type=\"text\"", "type=\"password\"",  parent :: displayHtml());
    }
}
