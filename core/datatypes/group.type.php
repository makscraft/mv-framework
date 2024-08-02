<?php
/**
 * Group datatype class. Contains links to the records of the same model.
 */
class GroupModelElement extends ModelElement
{
	protected $long_list = false;
	
	protected $name_field = 'name';
	
	protected $table;
	
	protected $id;
	
	public function setTable($table)
	{
		$this -> table = $table;

		return $this;
	}
	
	public function setId($id)
	{
		$this -> id = $id;

		return $this;
	}
	
	public function validate()
	{
		if($this -> required && !$this -> value) //If we check required value
			$this -> error = $this -> chooseError("required", "{error-required-enum}");
			
		return $this;
	}
	
	public function displayAdminFilter(mixed $data)
	{
		return EnumModelElement :: createAdminFilterHtml($this -> name, $data);
	}

	public function countGroupRecords($value)
	{
		if(!$value) return;
		
		$number = Database :: instance() -> getCount($this -> table, "`id` IN(".$value.")");
		
		return $number ? $number : "";
	}
	
	public function checkGroupRecords($value)
	{
		if(!$value) return;
			
		$ids = Database :: instance() -> getColumn("SELECT `id` FROM `".$this -> table."` WHERE `id` IN(".$value.")");
		
		return count($ids) ? implode(",", $ids) : "";
	}
	
	public function getTableRecords()
	{
		$db = Database :: instance();
		
		$result = $db -> query("SELECT `id`,`".$this -> name_field."` 
					 		    FROM `".$this -> table."` 
								ORDER BY `".$this -> name_field."` ASC");		
		
		$ids = $names = []; //Collects names and ids of records
		
		while($row = $db -> fetch($result, "ASSOC"))
		{
			$ids[] = $row['id'];
			$names[] = trim($row[$this -> name_field]) ? trim($row[$this -> name_field]) : '-';
		}
		
		if(count($ids) && count($names)) //Creates the array to use it in interface
			return array_combine($ids, $names);
		else
			return [];		
	}
	
	public function displayHtml()
	{
		$all_records = $this -> getTableRecords();
		$selected_ids = $this -> value ? array_unique(explode(",", $this -> value)) : [];
		$current_id = $this -> id ? " id=\"group-self-id-".$this -> id."\"" : "";
		
		$html = "<div class=\"m2m-wrapper group-datatype".($this -> long_list ? " with-search" : "")."\"".$current_id.">\n";
		$html .= "<div class=\"column\">\n";
		
		//Multi select tag with not selected elements
		$html .= "<div class=\"header\">".I18n :: locale("not-selected");
		
		if($this -> long_list)
			$html .= "\n<input type=\"text\" class=\"m2m-not-selected-search\" />\n";
		
		$html .= "</div>\n";

		$html .= "<select class=\"m2m-not-selected\" multiple=\"multiple\">\n";
		
		if(!$this -> long_list)
			foreach($all_records as $id => $name)
				if(!in_array($id, $selected_ids) && (!$this -> id || $this -> id != $id))
				{
					$html .= "<option value=\"".$id."\" title=\"";
					$html .= $name."\">".$name."</option>\n";
				}
				
		$html .= "</select>\n";		
		$html .= "</div>\n";
		
		//Buttons to move options of selects
		$html .= "<div class=\"m2m-buttons\">\n<span class=\"m2m-right\"></span>\n";
		$html .= "<span class=\"m2m-left\"></span></div>\n";
		
		$html .= "<div class=\"column\">\n<div class=\"header\">".I18n :: locale("selected");
		
		if($this -> long_list)
			$html .= "\n<input type=\"text\" class=\"m2m-selected-search\" />\n";

		$html .= "</div>\n";
		
		//Multi select tag with selected elements		
		$html .= "<select class=\"m2m-selected\" multiple=\"multiple\">\n";
		
		foreach($selected_ids as $id)
			if(isset($all_records[$id]))
			{
				$html .= "<option value=\"".$id."\" title=\"";
				$html .= $all_records[$id]."\">".$all_records[$id]."</option>\n";
			}
		
		$html .= "</select>\n";
		$html .= "</div>\n";
		
		$html .= "<div class=\"m2m-buttons\">\n<span class=\"m2m-up\"></span>\n";
		$html .= "<span class=\"m2m-down\"></span>\n</div>\n";
		
		$html .= "<input type=\"hidden\" value=\"".$this -> checkGroupRecords($this -> value)."\"";
		$html .= " name=\"".$this -> name."\" />\n";
		
		if($this -> long_list)
			$html .= "<div class=\"no-display search-buffer\"></div>\n";
		
		return $html."</div>".$this -> addHelpText();
	}
		
	public function getOptionsForSearch($request, $ids, $self_id)
	{
		$db = Database :: instance();
		$html = "";
		$request_like = str_replace("%", "[%]", $request);
		
		if($request_like == "")
			return "";
		
		$request_like = $db -> secure("%".$request_like."%");
		
		$where = ($ids && count($ids)) ? " WHERE `id` NOT IN(".implode(",", $ids).") " : "";		
		$where .= $where ? " AND " : " WHERE ";
		$where .= $self_id ? "`id`!='".$self_id."' AND " : "";
		$where .= "`".$this -> name_field."` LIKE ".$request_like;
		
		$result = $db -> query("SELECT `id`,`".$this -> name_field."` 
					  		    FROM `".$this -> table."`"
								.$where." 
								ORDER BY `".$this -> name_field."` ASC");
		
		while($row = $db -> fetch($result, "ASSOC"))
		{
			$html .= "<option title=\"".$row[$this -> name_field]."\" value=\"".$row['id']."\">";
			$html .= $row[$this -> name_field]."</option>\n";
		}
		
		return $html;
	}
	
	public function getDataForAutocomplete($request)
	{
		$db = Database :: instance();
		$result_rows = [];
		$request_like = str_replace("%", "[%]", $request);
		$request_like = $db -> secure("%".$request_like."%");
		
		$query = "SELECT `id`,`".$this -> name_field."` 
		 		  FROM `".$this -> table."`
				  WHERE `".$this -> name_field."` LIKE ".$request_like."
				  ORDER BY `".$this -> name_field."` ASC
				  LIMIT 10";
								
		$rows = $db -> getAll($query);
			
		foreach($rows as $row) //Collects suggestions
			$result_rows[$row['id']] = htmlspecialchars_decode($row[$this -> name_field], ENT_QUOTES);
		
		return array('query' => $request,  
					 'suggestions' => array_values($result_rows),
					 'data' => array_keys($result_rows));
	}
	
	public function checkValue($id)
	{
		$db = Database :: instance();
		
		return $db -> getCell("SELECT `".$this -> name_field."` 
							   FROM `".$this -> table."` 
							   WHERE `id`='".intval($id)."'");
	}
	
	public function getDataForMultiAction()
	{
		$options_xml = "<value id=\"\">".I18n :: locale("select-value")."</value>\n";
		$data_for_options = $this -> getTableRecords();
			
		if(is_array($data_for_options))
			foreach($data_for_options as $id => $name)
				$options_xml .= "<value id=\"".$id."\">".$name."</value>\n";
				
		return $options_xml;
	}
}
