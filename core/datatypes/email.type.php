<?php
/**
 * Email datatype class. Most properties are inherited from Char datatype.
 */
 class EmailModelElement extends CharModelElement
{
	protected $format = "/^[-a-z0-9_\.]+@[-a-z0-9_\.]+\.[a-z]{2,5}$/i";
	
	public function validate()
	{
		$arguments = func_get_args();
		parent :: validate($arguments[0], $arguments[1]);
		
		if(!$this -> error && $this -> value)
			if(!preg_match($this -> format, $this -> value) || strpos($this -> value, "..") !== false)
				$this -> error = $this -> chooseError("format", "{error-email-format}");
		
		return $this;
	}
} 
