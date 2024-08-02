<?php
/**
 * Many to many datatype class. Works with values in linking tables in database.
 */ 
class ManyToManyModelElement extends ModelElement
{
	protected $related_model = '';
	
	protected $linking_table = '';
	
	protected $related_id;
	
	protected $all_ids_and_names;
	
	protected $selected_ids = [];
	
	protected $name_field = 'name';
	
	protected $display_table = false;
	
	protected $long_list = false;

	protected $empty_value = '';

	public function defineLinkingTable(string $class_name)
	{
		if($this -> linking_table !== '')
			return;

		$first = strtolower($class_name);
		$second = strtolower($this -> related_model);

		$this -> linking_table = $first > $second ? $first.'_'.$second : $second.'_'.$first;
	}
	
	public function setRelatedId($related_id)
	{
		$this -> related_id = intval($related_id);
		
		return $this;
	}
	
	public function getValuesList()
	{
		return $this -> all_ids_and_names;
	}
	
	public function setDisplayTable($value)
	{
		$this -> display_table = intval($value);
		
		if(!is_array($this -> all_ids_and_names))
			$this -> all_ids_and_names = $this -> getDataOfManyToMany(); //All names and ids of elements
		
		return $this;
	}
	
	public function setValue($values)
	{
		$this -> selected_ids = [];
		
		if(!$values)
			return;
		
		$values = explode(',', $values);
		
		if(count($values))
			foreach($values as $value)
				$this -> selected_ids[] = intval($value);
			
		$this -> selected_ids = array_unique($this -> selected_ids);
			
		return $this;
	}
	
	public function getValue()
	{
		return count($this -> selected_ids) ? implode(",", array_unique($this -> selected_ids)) : false;
	}

	public function validate()
	{
		if($this -> required && !count($this -> selected_ids)) //If we check required value
			$this -> error = $this -> chooseError("required", "{error-required-enum}");
			
		return $this;
	}	
	
	public function getSelfId()
	{
		return $this -> name."_id";
	}
	
	public function getOppositeId()
	{
		if(substr_count($this -> linking_table, $this -> name) >= 2)
		{
			$message = "The name of element '".$this -> name."' is found in table '".$this -> linking_table."' ";
			$message .= "more than one time. You need to change the name of this field to avoid this error.";

			Debug :: displayError($message);
		}
		
		return preg_replace("/_?".$this -> name."_?/", "", $this -> linking_table)."_id";
	}
	
	public function getSelectedIds()
	{
		return $this -> selected_ids;
	}
		
	public function displayHtml()
	{
		if(!is_array($this -> all_ids_and_names))
			$this -> all_ids_and_names = $this -> getDataOfManyToMany(); //All names and ids of elements
			
		if(!count($this -> selected_ids))
			$this -> selected_ids = $this -> getSelectedValues(); //Selected elements (rows) in linking table
			
		if($this -> display_table) //The flag is also the number of columns to split the table
			return $this -> displayAsTable($this -> display_table);
		
		$data_selected = $data_not_selected = []; //To split the values in 2 lists
		
		if(count($this -> selected_ids))
			$this -> selected_ids = $this -> orderSelectedIds($this -> selected_ids);
		
		foreach($this -> selected_ids as $id) //Adds names for selected ids
			if(array_key_exists($id, $this -> all_ids_and_names))
				$data_selected[$id] = $this -> all_ids_and_names[$id];
			
		foreach($this -> all_ids_and_names as $id => $name) //Takes out selected elements
			if(!in_array($id, $this -> selected_ids))
				$data_not_selected[$id] = $name;
		
		$html = "<div class=\"m2m-wrapper".($this -> long_list ? " with-search" : "")."\">\n";
		$html .= "<div class=\"column\">\n";

		//Multi select tag with not selected elements
		$html .= "<div class=\"header\">".I18n :: locale("not-selected");
		
		if($this -> long_list)
			$html .= "\n<input type=\"text\" class=\"m2m-not-selected-search\" />\n";
		
		$html .= "</div>\n";
		$html .= "<select class=\"m2m-not-selected\" multiple=\"multiple\">\n";
		
		foreach($data_not_selected as $id => $name)
			$html .= "<option value=\"".$id."\" title=\"".$name."\">".$name."</option>\n";
			
		$html .= "</select>\n";
		$html .= "</div>\n";
		
		//Buttons to move options of selects
		$html .= "<div class=\"m2m-buttons\">\n<span class=\"m2m-right\"></span>\n";
		$html .= "<span class=\"m2m-left\"></span></div>\n";
		
		$html .= "<div class=\"column\">\n";
		$html .= "<div class=\"header\">".I18n :: locale("selected");
		
		if($this -> long_list)
			$html .= "\n<input type=\"text\" class=\"m2m-selected-search\" />\n";
		
		$html .= "</div>\n";
				
		//Multi select tag with selected elements
		$html .= "<select class=\"m2m-selected\" multiple=\"multiple\">\n";
		
		foreach($data_selected as $id => $name)
			$html .= "<option value=\"".$id."\" title=\"".$name."\">".$name."</option>\n";
		
		$html .= "</select>\n";		
		$html .= "</div>\n";
		
		$html .= "<input type=\"hidden\" value=\"".implode(',',$this -> selected_ids)."\"";
		$html .= " name=\"".$this -> name."\" />\n";
		
		if($this -> long_list)
			$html .= "<div class=\"no-display search-buffer\"></div>\n";
		
		return $html."</div>".$this -> addHelpText();
	}

	public function displayAsSelect()
	{
		if(!is_array($this -> all_ids_and_names))
			$options = $this -> all_ids_and_names = $this -> getDataOfManyToMany(); 

		$empty_value = $this -> empty_value === '' ? I18n :: locale('not-defined') : $this -> empty_value;
		$selected = $this -> selected_ids[0] ?? '';

		$html = "<select name=\"".$this -> name."\">\n";
		$html .= "<option value=\"\">".$empty_value."</option>\n";
		
		foreach($options as $value => $name)
		{
			$html .= "<option value=\"".$value."\"";
			
			if($selected != "" && $selected == $value)
				$html .=  " selected=\"selected\"";
				
			$html .= ">".$name."</option>\n";
		}

		return $html."</select>\n";
	}
	
	public function getSelectedValues()
	{
		$object = new $this -> related_model();
		
		if(intval($this -> related_id))
			return $object -> db -> getColumn("SELECT DISTINCT `".$this -> getSelfId()."` 
					  						   FROM `".$this -> linking_table."`
											   WHERE `".$this -> getOppositeId()."`='".$this -> related_id."'");
		else
			return [];
	}
	
	public function getValuesForFilter($ids)
	{
		$checked_ids = [];
		
		foreach(explode(",", $ids) as $id)
			if(intval($id))
				$checked_ids[] = intval($id);
			
		return $checked_ids;
	}	
	
	public function loadSelectedValues()
	{
		$this -> selected_ids = $this -> getSelectedValues();
		
		return $this;
	}
	
	public function getDataOfManyToMany()
	{
		$object = new $this -> related_model(); //Other model object
		$where = "";
		
		$arguments = func_get_args();
		
		if($this -> long_list && !isset($arguments[0]))
			if(count($this -> selected_ids))
				$where = " WHERE `id` IN(".implode(",", $this -> selected_ids).") ";
			else
				return [];
		
		if(isset($arguments[1]) && $arguments[1])
			$result = $object -> db -> query($arguments[1]);
		else
			$result = $object -> db -> query("SELECT `id`,`".$this -> name_field."` 
											  FROM `".$object -> getTable()."`".$where." 
											  ORDER BY `".$this -> name_field."` ASC");		
		
		$ids = $names = []; //Collects names and ids of records
		
		while($row = $object -> db -> fetch($result, "ASSOC"))
		{
			$ids[] = $row['id'];
			$names[] = trim($row[$this -> name_field]) ? trim($row[$this -> name_field]) : '-';
		}

		if(count($ids) && count($names)) //Creates the array to use it in interface
			return array_combine($ids, $names);
		else
			return [];			
	}
	
	public function countDataForOneRecord($related_id)
	{
		$object = new $this -> related_model();
		
		$query = "SELECT `".$this -> getSelfId()."` 
				  FROM `".$this -> linking_table."`
				  WHERE `".$this -> getOppositeId()."`='".$related_id."'";
		
		$linked_ids = $object -> db -> getColumn($query);
		
		if(!count($linked_ids))
			return 0;
						
		return (int) $object -> db -> getCount($object -> getTable(), "`id` IN(".implode(",", $linked_ids).")");
	}
	
	public function displayAdminTableLink($related_id)
	{
		$number = $this -> countDataForOneRecord($related_id);
		
		if($number)
		{
			$object = new $this -> related_model();
			$field = str_replace("_id", "", $this -> getOppositeId());
			
			if($object -> getElement($field))
			{
				$params = "?model=".strtolower($this -> related_model)."&";
				$params .= $field."=".$related_id;
				$number = "<a class=\"to-children\" href=\"".$params."\">".$number."</a>\n";
			}
			
			return $number;
		}
		
		return "-";
	}
	
	public function getDataForOneElement($related_id)
	{
		$this -> setRelatedId($related_id);
		
		if(!is_array($this -> all_ids_and_names))
			$this -> all_ids_and_names = $this -> getDataOfManyToMany();
		
		$selected_ids = $this -> getSelectedValues();		
		$names = [];
		 
		foreach($selected_ids as $id)
			$names[] = $this -> all_ids_and_names[$id];
			
		$this -> setRelatedId(null);
			
		return count($names) ? implode(",<br />", $names) : "-";
	}
	
	public function displayAsTable($columns)
	{
		return Service :: displayOrderedFormTable($this -> all_ids_and_names, $columns, $this -> selected_ids, $this -> name);			
	}
	
	public function setValuesFromCheckboxes()
	{
		if(!is_array($this -> all_ids_and_names))
			return $this;
		
		foreach($this -> all_ids_and_names as $key => $value)
			if(isset($_POST[$this -> name."-".$key]) && $_POST[$this -> name."-".$key] == $key)
				$this -> selected_ids[] = $key;
				
		return $this;
	}
	
	public function getOptionsForSearch($request, $ids)
	{
		$html = "";
		$object = new $this -> related_model();
		$request_like = str_replace("%", "[%]", $request);
		
		if($request_like == "")
			return "";
		
		$request_like = $object -> db -> secure("%".$request_like."%");
		
		$where = ($ids && count($ids)) ? " WHERE `id` NOT IN(".implode(",", $ids).") " : "";		
		$where .= $where ? " AND " : " WHERE ";
		$where .= "`".$this -> name_field."` LIKE ".$request_like;
		
		$result = $object -> db -> query("SELECT `id`,`".$this -> name_field."` 
										  FROM `".$object -> getTable()."`"
										  .$where." 
										  ORDER BY `".$this -> name_field."` ASC");
		
		while($row = $object -> db -> fetch($result, "ASSOC"))
		{
			$html .= "<option title=\"".$row[$this -> name_field]."\" value=\"".$row['id']."\">";
			$html .= $row[$this -> name_field]."</option>\n";
		}
		
		return $html;
	}
	
	public function orderSelectedIds($ids)
	{
		if(!is_array($ids) || !count($ids))
			return;
		
		$object = new $this -> related_model();
		
		return $object -> db -> getColumn("SELECT `id` 
										   FROM `".$object -> getTable()."` 
										   WHERE `id` IN(".implode(",", $ids).")
										   ORDER BY `".$this -> name_field."` ASC");
	}
	
	public function getDataForMultiAction()
	{
		$options_xml = "<value id=\"\">".I18n :: locale("select-value")."</value>\n";

		$data_for_options = $this -> getDataOfManyToMany("for_multi_action");

		if(is_array($data_for_options))
			foreach($data_for_options as $id => $name)
				$options_xml .= "<value id=\"".$id."\">".$name."</value>\n";
				
		return $options_xml;
	}
	
	public function getDataForAutocomplete(string $request, Database $db)
	{
		$result_rows = [];
		$request_like = str_replace("%", "[%]", $request);
		$request_like = $db -> secure("%".$request_like."%");		
		$object = new $this -> related_model(); //Other model object
		
		$rows = $object -> db -> getAll("SELECT `id`,`".$this -> name_field."` 
										 FROM `".$object -> getTable()."`
										 WHERE `".$this -> name_field."` LIKE ".$request_like." 
										 ORDER BY `".$this -> name_field."` ASC 
										 LIMIT 10");

		foreach($rows as $row) //Collects suggestions
				$result_rows[$row['id']] = htmlspecialchars_decode($row[$this -> name_field], ENT_QUOTES);
		
		return ['query' => $request,  
				'suggestions' => array_values($result_rows),
				'data' => array_keys($result_rows)];
	}
	
	public function checkValue($id)
	{
		$row = (new $this -> related_model()) -> getById(intval($id));
		
		if($row && isset($row[$this -> name_field]))
			return $row[$this -> name_field];
	}
	
	public function multiAction($updated_ids, $action_id, $action_name)
	{
		$object = new $this -> related_model();
		
		if(!count($updated_ids) || !$action_id)
			return;
		
		if($action_name == "remove")
			$object -> db -> query("DELETE FROM `".$this -> linking_table."`
								    WHERE `".$this -> getOppositeId()."` IN(".implode(",", $updated_ids).")
									AND `".$this -> getSelfId()."`='".$action_id."'");
		else if($action_name == "add")
		{
			foreach($updated_ids as $id)
			{
				$condition = "`".$this -> getOppositeId()."`='".$id."' AND `".$this -> getSelfId()."`='".$action_id."'";
				
				if(!$object -> db -> getCount($this -> linking_table, $condition))
					$object -> db -> query("INSERT INTO `".$this -> linking_table."` 
										    (`".$this -> getOppositeId()."`,`".$this -> getSelfId()."`)
											VALUES ('".$id."', '".$action_id."')");
			}
		}
	}
	
	public function getDataForMessage($ids)
	{
		if(!$ids)
			return;
		
		$object = new $this -> related_model();
		$message = [];
		
		$rows = $object -> db -> getAll("SELECT `id`,`".$this -> name_field."` 
										 FROM `".$object -> getTable()."`
										 WHERE `id` IN(".$ids.")
										 ORDER BY `".$this -> name_field."` ASC");
		
		foreach($rows as $row)
			$message[] = $row[$this -> name_field];
			
		return implode(", ", $message);
	}
	
	public function filterValuesList($params)
	{
		if(!is_array($params) || !count($params))
			return $this;
			
		$object = new $this -> related_model();
		
		$query = "SELECT `id`,`".$this -> name_field."` 
				  FROM `".$object -> getTable()."`".Model :: processSQLConditions($params);
		
		$this -> all_ids_and_names = $this -> getDataOfManyToMany("filter", $query);

		return $this;
	}
}
