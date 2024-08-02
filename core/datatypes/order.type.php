<?php
/**
 * Order datatype class. Numeric datatype, which keeps positions of records in SQL table.
 */
class OrderModelElement extends IntModelElement
{
	protected $positive = true;
	
	protected $depend_on_enum = false;
	
	public function validate()
	{
		$arguments = func_get_args();
		parent :: validate($arguments[0], $arguments[1]);
		
		if($this -> error == '{error-not-order}')
			$this -> error = '{error-not-int}';

		return $this;
	}

	public function displayHtml()
	{
		$arguments = func_get_args();
		
		if(isset($arguments[0]) && $arguments[0] && $arguments[0] != "frontend")
			$this -> value = intval($arguments[0]);
			
		return parent :: displayHtml();
	}
	
	public function displayHtmlForTable($value, $row_id)
	{		
		$html = "<div class=\"move_position_".$this -> name." ordering-area\">\n";
		$html .= "<span title=\"".I18n :: locale('move-first')."\" class=\"top\"></span>\n";
		$html .= "<span title=\"".I18n :: locale('move-up')."\" class=\"up\"></span>\n";
		$html .= "<span id=\"row_".$row_id."\" class=\"number\">".intval($value)."</span>\n";
		$html .= "<span title=\"".I18n :: locale('move-down')."\" class=\"down\"></span>\n";
		$html .= "<span title=\"".I18n :: locale('move-last')."\" class=\"bottom\"></span>\n";

		return $html."</div>\n";
	}
	
	public function getLastNumber($model_table, $condition)
	{
		$db = Database :: instance();
		
		return (int) $db -> getCell("SELECT MAX(`".$this -> name."`) 
								     FROM `".$model_table."` 
								     WHERE `".$this -> name."`!=''".$condition);
		
	}
}
