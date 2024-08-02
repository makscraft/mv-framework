<?php
/**
 * Export / import of models data into CSV files, which can be opend in MS Excel.
 */
class Csv
{
	//Allowed datatypes for export in csv
	private $export_types = array('bool','int','float','char','url','redirect',
								  'email','phone','enum','password',
					       		  'order','date','date_time');
	
	//Allowed encodings of csv files
	private $encodings = array("windows-1251", "utf-8");
	
	//Allowed separators of csv files
	private $separators = array("semicolon" => ";", "comma" => ",", "tabulation" => "\t");
	
	public function getEncodings() { return $this -> encodings; }
	
	public function getSeparators() { return $this -> separators; }
	
	public function getExportTypes() { return $this -> export_types; }
	
	public function displayFieldsLists($model)
	{
		//2 lists of fields to select columns of csv file
		$html = "<div class=\"m2m-wrapper csv-fields\">\n";		
		$html .= "<div class=\"column\">\n<div class=\"header\">".I18n :: locale("not-selected")."</div>\n";
		$html .= "<select class=\"m2m-not-selected\" multiple=\"multiple\">\n";
		$html .= "<option value=\"id\">Id</option>\n";
		
		foreach($model -> getElements() as $field)
			if(in_array($field -> getType(), $this -> export_types))
				if($model -> getModelClass() == "users" && $field -> getName() == "password_repeat")
					continue;
				else if($field -> getType() == "enum" && $field -> getProperty("foreign_key") && $field -> getProperty("is_parent"))
					continue;
				else
					$html .= "<option value=\"".$field -> getName()."\">".$field -> getCaption()."</option>\n";
		
		$html .= "</select>\n</div>\n";
		
		$html .= "<div class=\"m2m-buttons\">\n<span class=\"m2m-right\"></span>\n";
		$html .= "<span class=\"m2m-left\"></span></div>\n";
		
		$html .= "<div class=\"column\">\n<div class=\"header\">".I18n :: locale("selected")."</div>\n";		
		$html .= "<select class=\"m2m-selected\" multiple=\"multiple\"></select>\n</div>\n";
		
		$html .= "<div class=\"m2m-buttons\">\n<span class=\"m2m-up\"></span>\n";
		$html .= "<span class=\"m2m-down\"></span>\n</div>\n";		
		$html .= "<input type=\"hidden\" value=\"\" name=\"csv_fields\" />\n";
		
		return $html."</div>\n";
	}
	
	public function composeCSVFile($model, $fields, $separator, $encoding, $headers)
	{
		$columns = $csv = [];
		$model -> loadRelatedData();
		$registry = Registry :: instance();
		$decimal_mark = $registry -> getSetting("DecimalMark");
		
		foreach($fields as $field) //Collect all columns
			if($field == "id" || in_array($model -> getElement($field) -> getType(), $this -> export_types))
				$columns[] = $field;
				
		if(!count($columns))
			return "";
			
		//Extracts data from database
		$columns_sql = "`".implode("`,`", $columns)."`";
		$rows = Database :: instance() -> getAll("SELECT ".$columns_sql." 
												  FROM `".$model -> getTable()."`
												  ORDER BY `".$model -> getNameField()."` ASC");
		
		if($headers) //First line of columns captions if needed
		{
			$string = [];
			
			foreach($columns as $field)
				if($field == "id")
					$string[] = "Id";
				else
					$string[] = $model -> getElement($field) -> getCaption();
			
			$string = implode($separator, $string);
			$csv[] = ($encoding != "utf-8") ? @iconv("utf-8", $encoding, $string) : $string;
		}
		
		//Data process and packing into single csv file
		foreach($rows as $row)
		{
			$string = [];
			
			foreach($columns as $field)
			{
				$value = trim($row[$field]);
				
				if($field != "id") //Special data transformations
				{
					$type = $model -> getElement($field) -> getType();
				
					if($type == "date" || $type == "date_time")
						$value = I18n :: formatDate($value, "no-seconds");
					else if($type == "enum")
						$value = $model -> getEnumTitle($field, $value);
					else if($type == "float" && $separator != "," && $decimal_mark && $decimal_mark != ".")
						$value = str_replace(".", $decimal_mark, $value);
				}
				
				$value = is_null($value) ? "" : $value;
				$value = htmlspecialchars_decode($value, ENT_QUOTES);
				$value = str_replace($separator, "", $value); //Remove separator from line
				
				$string[] = $value;
			}
			
			$string = implode($separator, $string); //Creates string and converts encoding
			$csv[] = ($encoding != "utf-8") ? @iconv("utf-8", $encoding, $string) : $string;
		}
		
		return implode("\r\n", $csv); //Glues string into file
	}
	
	public function updateFromCSVFile($data, $model, $fields, $update_order, $headers)
	{
		$data_processed = [];
		$columns_number = count($fields);
		$db = Database :: instance();
		$result = array("updated_ids" => [], "created_ids" => [], "declined_strings" => []);
		
		$allow_update = ($update_order == "update-and-create" || $update_order == "update-only");
		$allow_create = ($update_order == "update-and-create" || $update_order == "create-only");
		
		foreach($fields as $key => $value) //Security process for fields names
			$fields[$key] = str_replace("'", "", $value);
		
		$string_number = $headers ? 1 : 0;
		
		foreach($data as $row)
		{
			$string_number ++;
			
			if(count($row) == $columns_number) //If all fields are expected
			{
				$fields_with_names = [];
				$empty_values = 0;
				
				foreach($row as $index => $value) //Checks if all values of string are empty
				{
					$row[$index] = trim($value);
					
					if($row[$index] == "")
						$empty_values ++;
				}
				
				if($columns_number == $empty_values) //If all values are empty we skip this string
				{
					$result["declined_strings"][$string_number] = "empty-string";
					continue;
				}
				
				if(in_array("id", $fields)) //Checks that if id was passed so it was integer
				{
					$id = $row[array_search("id", $fields)];

					if(!is_numeric($id) && $id != "")
					{
						$result["declined_strings"][$string_number] = "not-integer-id";
						continue;
					}
				}
				
				foreach($row as $index => $value) //Datatypes preparing
				{					
					if($fields[$index] == "id")
					{
						$fields_with_names[$fields[$index]] = intval($value);
						continue;
					}
					
					$type = $model -> getElement($fields[$index]) -> getType();
					
					if($type == "date" || $type == "date_time")
					{
						if(!preg_match("/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}(:\d{2})?)?$/", $value))
							$value = I18n :: dateForSQL($value);
					}
					else if($type == "enum")
					{
						$value = htmlspecialchars($value, ENT_QUOTES);
						$value = Service :: cleanHtmlSpecialChars($value);
						$value = $model -> getElement($fields[$index]) -> getKeyUsingName($value);						
					}
					else if($type == "int" || $type == "order")
						$value = intval($value);
					else if($type == "float")
						$value = floatval(str_replace(",", ".", $value));
					else if($type == "bool")
						$value = $value ? 1 : 0;
					else
						$value = htmlspecialchars($value, ENT_QUOTES);
						
					$value = Service :: cleanHtmlSpecialChars($value);
					
					$fields_with_names[$fields[$index]] = $value;
				}
				
				//Check id and data for not suitable values
				if(array_key_exists("id", $fields_with_names) && $fields_with_names["id"])
				{
					if(!$allow_update)
						$result["declined_strings"][$string_number] = "update-forbidden";
					else if(!$db -> getCount($model -> getTable(), "`id`='".$fields_with_names["id"]."'"))
						$result["declined_strings"][$string_number] = "not-exists";
					else if(!$this -> checkUniqueValues($model, $fields_with_names, $fields_with_names["id"]))
						$result["declined_strings"][$string_number] = "not-unique";
				}
				else
				{
					if(!$allow_create)
						$result["declined_strings"][$string_number] = "create-forbidden";					
					else if(!$this -> checkUniqueValues($model, $fields_with_names, false))
						$result["declined_strings"][$string_number] = "not-unique";
				}
				
				//If current sring contains errors we skip it
				if(isset($result["declined_strings"][$string_number]))
					continue;

				$data_processed[] = $fields_with_names; //String is ready for update
			}
			else
				$result["declined_strings"][$string_number] = "not-equal-columns";
		}
		
		$db -> beginTransaction();
		
		foreach($data_processed as $row) //Update and create of records
		{
			if(in_array("id", $fields) && isset($row["id"]) && $row["id"])
			{
				$id = intval($row["id"]);
				
				if($id && $allow_update)
				{
					$query = [];
					
					foreach($row as $field => $value)
						if($field != "id")
							$query[] = "`".$field."`=".$db -> secure($value);
							
					$query = "UPDATE `".$model -> getTable()."` SET ".implode(", ", $query)." WHERE `id`='".$id."'";
					$result["updated_ids"][] = $id;
					
					$db -> query($query);
				}
			}
			else if($allow_create)
			{					
				$names = $values = [];
					
				foreach($row as $field => $value)
					if($field != "id")
					{
						$names[] = "`".$field."`";
						$values[] = $db -> secure($value);
					}
				
				$query = "INSERT INTO `".$model -> getTable()."`(".implode(", ", $names).") 
					      VALUES(".implode(", ", $values).")";
				
				$result["created_ids"][] = $model -> db -> lastId();
				
				$db -> query($query);
			}
		}
		
		$db -> commitTransaction();

		return $result;
	}
	
	private function checkUniqueValues($model, $row, $id)
	{		
		foreach($model -> getElements() as $name => $object)
			if($object -> getProperty("unique") && isset($row[$name]) && $row[$name])
			{
				$condition = "`".$name."`='".$row[$name]."'";

				if($id)
					$condition .= " AND `id`!='".$id."'";

				if($model -> db -> getCount($model -> getTable(), $condition))
					return false;
			}
		
		return true;
	}
}
