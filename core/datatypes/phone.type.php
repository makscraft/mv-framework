<?php
/**
 * Phone datatype class. Most properties are inherited from Char datatype.
 */
class PhoneModelElement extends CharModelElement
{
	protected $format = "/^[\+\d\s\(\)-]+$/";
	
	protected $min_length = 6;
	
	public function validate()
	{
		$arguments = func_get_args();
		parent :: validate($arguments[0], $arguments[1]);
		
		if(!$this -> error && $this -> value)
			if(!preg_match($this -> format, $this -> value))
				$this -> error = $this -> chooseError("format", "{error-phone-format}");
		
		return $this;
	}
}
