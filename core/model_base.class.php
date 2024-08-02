<?php
/**
 * Base class of any model (regular or simple typed).
 * Contains main objects and properties.
 * Defines many operations which will be used in child classes.
 */
abstract class ModelBase extends ModelInitial
{	
	/**
	 * Localization manager.
	 * @var object I18n class instance
	 */
	public $i18n;
	
	/**
	 * Pagination manager.
	 * @var object Paginator class
	 */
	public $paginator;
	
	/**
	 * Filters manager.
	 * @var object Filter class
	 */
	public $filter;
	
	/**
	 * Sorting manager.
	 * @var object Sorter class
	 */
	public $sorter;
	
	/**
	 * Current user of admin panel.
	 * @var object User class
	 */
	protected $user;
	
	/**
	 * Elements, fields of current model.
	 * @var array of datatypes objects
	 */
	protected $elements;
		
	/**
	 * Name of parent type (tree builder) field of model.
	 * @var string
	 */
	protected $parent_field;
	
	/**
	 * Id of current parent in tree structure.
	 * @var int
	 */
	protected $parent_id;
	
	/**
	 * Errors list after validation in admin panel.
	 * @var array
	 */
	protected $errors = [];
	
	/**
	 * GET params to pass in url when in admin panel.
	 * @var array
	 */
	protected $url_params = [];
	
	/**
	 * Total number of records in current model (with conditions).
	 * @var int
	 */
	protected $total;
	
	/**
	 * Name field of model, which is used to show record in admin panel.
	 * @var string
	 */
	protected $name_field = 'name';
	
	/**
	 * Additional field for record name in admin panel (added to $name_field).
	 * @var string
	 */
	protected $name_field_extra = '';
	
	/**
	 * SQL query for main table with model's records in admin panel.
	 * @var string
	 */
	protected $sql_for_table;
	
	/**
	 * Cache of enum fields captions for method getEnumTitle().
	 * @var array
	 */
	protected $enum_values = [];
	
	/**
	 * Additional display parameters of model in admin panel.
	 * @var array
	 */
	protected $display_params = [
		"visible_fields" => "*",
		"hidden_fields" => [],
		"editable_fields" => "*",
		"not_editable_fields" => [],
		"create_actions" => true,
		"update_actions" => true,
		"delete_actions" => true,
		"mass_actions" => true,		
		"fields_groups" => [],
		"default_table_columns" => [],		
		"default_filters" => [],
		"show_empty_default_filters" => false,
		"redirects_to_models" => [],
		"foreign_keys_admin_links" => false,
		"versions_limit" => "config"
	];
	
	/**
	 * URL path from root of application (including folder, APP_FOLDER in .env)
	 * @var string
	 */
	public $root_path;	
	
	/**
	 * Folder with extra parts of code to include into admin panel interface.
	 * @var string
	 */
	public $includes_folder = "customs/models/";
	
	/**
	 * File system path to the document root of the project.
	 * @var string
	 */
	public $include_path;

	/**
	 * List with migrations to perform.
	 * @var array
	 */
	protected $migrations;
	
	public function getParentField() { return $this -> parent_field; }
	public function getParentId() { return $this -> parent_id; }
	public function getTable() { return $this -> table; }
	public function getId() { return $this -> id; }
	public function getElements() { return $this -> elements; }
	public function getErrors() { return $this -> errors; }

	public function getNameField()
	{
		return $this -> tryToFindNameField();
	}
	
	public function setParentId($parent_id)
	{
		$this -> parent_id = intval($parent_id);
	}
	
	public function setId($id)
	{ 
		$this -> id = intval($id);

		if($this -> id)
			$this -> url_params['id'] = 'id='.$this -> id;
			
		return $this; 
	}
		
	public function setUser(User $user)
	{
		$this -> user = $user;
	}

	public function addElement(array $element_data)
	{
		if(!isset($this -> elements[$element_data[2]]))
			$this -> instanceElement($element_data);
			
		return $this;
	}
	
	public function getElement(string $name)
	{
		if(isset($this -> elements[$name]))
			return $this -> elements[$name];
	}
	
	public function passElement($object)
	{
		if(is_object($object))
			$this -> elements[$object -> getName()] = $object;
			
		return $this;
	}
	
	public function removeElement(string $name)
	{
		if(isset($this -> elements[$name]))
			unset($this -> elements[$name]);
			
		return $this;
	}
	
	public function checkDisplayParam($name)
	{
		if(isset($this -> display_params[$name]))
			return (bool) $this -> display_params[$name];
	}
	
	public function setDisplayParam($name, $value)
	{
		if(isset($this -> display_params[$name]))
			$this -> display_params[$name] = $value;
			
		return $this;
	}
	
	public function getDisplayParam($name)
	{
		if(isset($this -> display_params[$name]))
			return $this -> display_params[$name];			
	}
	
	public function checkIfFieldVisible($name)
	{
		if($this -> display_params['visible_fields'] == "*")
			return !in_array($name, $this -> display_params['hidden_fields']);
		else
			return in_array($name, $this -> display_params['visible_fields']);
	}
	
	public function checkIfFieldEditable($name)
	{
		if($this -> display_params['not_editable_fields'] == "*")
			return false;
		else if($this -> display_params['editable_fields'] == "*")
			return !in_array($name, $this -> display_params['not_editable_fields']);
		else
			return in_array($name, $this -> display_params['editable_fields']);
	}
	
	public function getEditableFields()
	{
		$fields = [];
		
		foreach($this -> elements as $name => $object)
			if($this -> checkIfFieldEditable($name))
				$fields[] = $name;
				
		return count($fields) ? $fields : false;		
	}
	
	public function getFieldsToDisplay()
	{
		if(isset($this -> display_params['table_fields']) && is_array($this -> display_params['table_fields']))
			return $this -> display_params['table_fields'];
			
		return [];
	}
	
	public function tryToFindNameField()
	{
		$name = false;
		
		if($this -> name_field && array_key_exists($this -> name_field, $this -> elements))
			$name = $this -> name_field;
		else if(array_key_exists('name', $this -> elements) && $this -> elements['name'] -> getType() == 'char')
			$name = 'name';
		else if($object = $this -> findElementByProperty('type', 'email'))
			$name = $object -> getName();
		else if($object = $this -> findElementByProperty('type', 'char'))
			$name = $object -> getName();
			
		return $name;
	}
	
	public function getUrlParam($name)
	{
		if(isset($this -> url_params[$name]))
			return $this -> url_params[$name];
	}
	
	public function setUrlParam($name, $value)
	{
		if(isset($this -> url_params[$name]))
			$this -> url_params[$name] = $value;
		
		return $this;
	}
	
	public function getAllUrlParams($allowed_params)
	{
		$all_params = [];
		
		foreach($this -> url_params as $name => $value)
			if(($allowed_params == '*' || in_array($name, $allowed_params)) && $value)
				$all_params[] = $value;
		
		if(count($all_params))
			return implode('&', $all_params);
	}
	
	public function passUrlParams($object, $allowed_params)
	{
		$all_params = $this -> getAllUrlParams($allowed_params);

		if($all_params)
			$object -> setUrlParams($all_params);
			
		return $this;
	}
	
	public function processUrlRarams()
	{
		$this -> url_params['pager'] = $this -> pager -> getUrlParams();
		$this -> url_params['filter'] = $this -> filter -> getUrlParams("admin");
		$this -> url_params['sorter'] = $this -> sorter -> getUrlParams();
		
		$this -> passUrlParams($this -> pager, array('model','parent','filter'));
		$this -> passUrlParams($this -> filter, array('model','parent'));
		$this -> passUrlParams($this -> sorter, array('model','parent','filter'));
		
		return $this;
	}
		
	public function checkFieldByType($type)
	{
		foreach($this -> elements as $name => $object)
			if($object -> getType() == $type)
				return $name;
				
		return false;
	}
	
	public function findElementByProperty($property, $value)
	{
		$value = strtolower($value);
		
		foreach($this -> elements as $name => $object)
			if(!is_null($object -> getProperty($property)) && strtolower($object -> getProperty($property)) == $value)
				return $object;
	}
	
	public function anyElementHasProperty($property)
	{
		foreach($this -> elements as $name => $object)
			if($object -> getProperty($property))
				return $name;
	}
	
	public function getModelClass()
	{
		return strtolower(get_class($this));
	}
	
	public function getById(mixed $id = null)
	{
		if($id !== null)
			$id = intval($id);
		else if($this -> id)
			$id = $this -> id;
			
		if(isset($id) && $id)
			return $this -> db -> getRow("SELECT * FROM `".$this -> table."` WHERE `id`='".$id."'");
		else
			return null;
	}
	
	public function getCaption(string $name)
	{
		if(isset($this -> elements[$name]))
			return $this -> elements[$name] -> getCaption();
	}
	
	public function getValue(string $name)
	{
		if(isset($this -> elements[$name]))
			return $this -> elements[$name] -> getValue();
	}

	public function setValue(string $name, mixed $value)
	{
		if(isset($this -> elements[$name]))
			$this -> elements[$name] -> setValue($value);
			
		return $this;
	}
	
	public function getNameOfForeignKeyField(string $class_name)
	{
		foreach($this -> elements as $name => $object)
			if($object -> getProperty('foreign_key') == $class_name)
				return $name;
	}
	
	public function getDataForActionsMenu()
	{
		if($this -> getModelClass() == 'garbage')
			return array(array('name' => 'restore', 'type' => 'restore', 'caption' => I18n :: locale('restore')));
		
		if(!$this -> checkDisplayParam('update_actions'))
			return [];
		
		$data = [];
		$allowed_types = array('bool','enum','parent','many_to_many','group','date','date_time','int','float');
		
		foreach($this -> elements as $name => $object)
			if(in_array($object -> getType(), $allowed_types) && $this -> checkIfFieldEditable($name) && 
			   $this -> checkIfFieldVisible($name))
			{
				if($object -> getType() == 'enum' && $object -> getProperty("unique"))
					continue;
				
				$field = array('name' => $name, 'type' => $object -> getType(), 'caption' => $object -> getCaption());
				
				if($object -> getType() == 'bool')
					array_unshift($data, $field);
				else
					$data[] = $field;
			}
		
		return $data;
	}
	
	public function applyMultiAction(string $action, mixed $value)
	{
		$current_action = $current_value = $current_m2m_condition = $current_type = $error = false;
		
		if(preg_match("/-m2m-(add|remove)$/", $action)) //m2m and group type actions
		{
			$current_m2m_condition = preg_replace("/.*-m2m-(add|remove)$/", "$1", $action);
			$action = preg_replace("/-m2m-(add|remove)$/", "", $action);
		}
		
		$simple_types = array('date','date_time','int','float');
		
		//Special check of value for action when update numbers and dates
		if($action != "delete" && $action != "restore")
			foreach($this -> getDataForActionsMenu() as $data)
				if($data['name'] == $action && in_array($data['type'], $simple_types))
				{
					$this -> elements[$data['name']] -> setValue($value) -> validate(false, false);
					
					if($this -> elements[$data['name']] -> getError())
					{
						$error = array($this -> elements[$data['name']] -> getCaption(), 
									   $this -> elements[$data['name']] -> getError(), 
									   $data['name']);
						
						$error = Model :: processErrorText($error, $this -> elements[$data['name']]);
						return "<div class=\"datatype-error form-errors\"><p>".$error."</p></div>\n";
					}
				}
				
		if($action == "delete" || $action == "restore")
		{
			$current_action = $action;
			$current_value = true;
		}
		else //Defines the update action params
			foreach($this -> getDataForActionsMenu() as $data)
				if($data['name'] == $action)
					if(($data['type'] == 'bool' && ($value == 0 || $value == 1))
						|| (in_array($data['type'], array('enum', 'parent', 'many_to_many', 'group')) 
						    && $this -> elements[$data['name']] -> checkValue($value))
						|| ($data['type'] == "date" && I18n :: checkDateFormat($value))
						|| ($data['type'] == "date_time" && I18n :: checkDateFormat($value, "with-time"))
						|| $data['type'] == "int" || $data['type'] == "float")
					{
						$current_action = $action;
						$current_value = $value;
						$current_type = $data['type'];
						
						if($data['type'] == "date" || $data['type'] == "date_time")
							$current_value = I18n :: dateForSQL($current_value);
						
						if($data['type'] == 'enum' && $current_value == "0")
							if(!$this -> elements[$data['name']] -> getProperty("foreign_key"))
								$current_value = "";
					}

		$selected_ids = [];
		
		foreach($_POST as $key => $val) //Takes ids of updated records form POST
			if(preg_match("/^item_\d+$/", $key))
				$selected_ids[] = intval(str_replace("item_", "", $key));
		
		if(!$current_action || $current_value === false || !count($selected_ids))
			return false; //If the action was not passed correctly
			
		//Prevents self deactivation of users and root user also
		if($this -> getModelClass() == "users" && $current_action == "active")
		{
			if($this -> user -> getId() != 1)
				if(in_array($this -> user -> getId(), $selected_ids))
					unset($selected_ids[array_search($this -> user -> getId(), $selected_ids)]);
			
			if(in_array(1, $selected_ids))
				unset($selected_ids[array_search(1, $selected_ids)]);
		}
				
		if($current_action == 'delete' || $current_action == 'restore')
		{
			$errors_buffer = [];
			
			foreach($selected_ids as $id) //Deletes or restores records
			{
				if($this -> getModelClass() == "users" && $id == 1)
				{
					$error = "not-deleted=root";
					continue;
				}
				
				$this -> setId($id);

				if($current_action == 'restore') //Restore operation process
				{
					if($this -> $current_action() === false) //If we can not restore this record
						$error = $this -> displayFormErrors();
				}
				else //Delete action process
				{
					$children_model = $this -> checkForChildren(); //Child records in self or FK models
					
					if($children_model)
						$error = "not-deleted".(is_numeric($children_model) ? "" : "=".$children_model);	
					else //If no linked records we delete the record
					{
						$this -> $current_action();
						
						if(count($this -> errors)) //If in beforeDelete() trigger were added any errors
						{
							foreach($this -> errors as $one_error)
								if($one_error && !in_array($one_error, $errors_buffer))
									$errors_buffer[] = $one_error;
									
							$this -> errors = [];
						}
					}
				}
			}
			
			if($current_action == 'delete' && count($errors_buffer)) //Custom delete errors collecting
				$error = $this -> dropErrors() -> addErrors($errors_buffer) -> displayFormErrors();
		}
		else //Regular update of records
		{
			$new_versions = [];
			
			foreach($selected_ids as $id) //Update records one by one
			{
				$version_dump = $old_fields = $this -> getById($id); //Dump to save the version
				$updated_fields = [];
				
				if($current_type != "many_to_many" && $current_type != "group")
					$version_dump[$current_action] = $updated_fields[$current_action] = $current_value; //Changes in version					
				
				if(method_exists($this, "beforeUpdate"))
				{
					$result_values = $this -> beforeUpdate($id, $old_fields, $version_dump);
					
					if(is_array($result_values) && count($result_values)) //If some values were changed
						foreach($result_values as $name => $value)
						{
							$changed_field = $this -> getElement($name);
							
							if($changed_field && $changed_field -> getType() != "many_to_many" && 
							   $changed_field -> getType() != "group")
							{
								$value = Service :: cleanHtmlSpecialChars(htmlspecialchars($value, ENT_QUOTES));
								$version_dump[$name] = $updated_fields[$name] = $value;
							}
						}
				}
				
				if(count($updated_fields)) //Multi action including beforeUpdate changes
				{
					$query = [];
					
					foreach($updated_fields as $name => $value)
						$query[] = "`".$name."`=".$this -> db -> secure($value);
						
					$this -> db -> query("UPDATE `".$this -> table."` 
							  			  SET ".implode(",", $query)." 
							  			  WHERE `id`='".$id."'");
				}

				unset($version_dump['id']);
				
				foreach($this -> elements as $name => $object)
					if($object -> getType() == 'many_to_many') //Pass actual m2m data from linking tables
						$version_dump[$name] = implode(',', $object -> setRelatedId($id) -> getSelectedValues());
						
				if($current_type == "many_to_many" || $current_type == "group") //Update version dump if m2m field
				{
					$m2m_values = explode(',', $version_dump[$current_action]); //Old values
					
					if($current_m2m_condition == "add" && !in_array($current_value, $m2m_values))
						$m2m_values[] = $current_value; //Adds value if needed
					else if($current_m2m_condition == "remove" && array_search($current_value, $m2m_values) !== false)
						unset($m2m_values[array_search($current_value, $m2m_values)]); //Removes value if needed
						
					foreach($m2m_values as $key => $value) //Filters empty values
						if(empty($value))
							unset($m2m_values[$key]);
						
					if($current_type == "group") //Group type update
					{
						if(array_search($id, $m2m_values) !== false) //Filters self id
							unset($m2m_values[array_search($id, $m2m_values)]);
						
						$this -> db -> query("UPDATE `".$this -> table."` 
					  		  			      SET `".$current_action."`='".implode(',', $m2m_values)."' 
					 				     	  WHERE `id`='".$id."'");
					}
					
					$version_dump[$current_action] = implode(',', $m2m_values); //Saves version dump
				}
				
				$versions_limit = $this -> getVersionsLimit();
				
				if($versions_limit) //If versions writing is enabled
				{
					$versions = new Versions($this -> table, $id);
					$versions -> setLimit($versions_limit);
					$versions -> save($version_dump, $this -> user); //Saves a new version	
				}
				
				$new_versions[$id] = $version_dump;
			}
						
			if($current_type == "many_to_many")
				$this -> elements[$current_action] -> multiAction($selected_ids, $current_value, $current_m2m_condition);
			
			if(method_exists($this, "afterUpdate"))
				foreach($selected_ids as $id)
					$this -> afterUpdate($id, $new_versions[$id]);
			
			//Add into log one record about all updated records
			$arguments = array("ids_number" => count($selected_ids), "records" => "*ids_number");
			
			Log :: write($this -> getModelClass(), -1, I18n :: locale('affected-records', $arguments), $this -> user -> getId(), "update");
			Cache :: cleanByModel($this -> getModelClass());
		}
		
		return $error;
	}
	
	public function tryToDefineName(array $content)
	{
		//Specially set name field in model class "$name_field = 'abc'"
		if(isset($content[$this -> name_field]) && $content[$this -> name_field])
		{
			$type = $this -> elements[$this -> name_field] -> getType();
			$name = "";
			$fields = array($this -> name_field => $type);
			
			if($this -> name_field_extra && isset($content[$this -> name_field_extra]))
				if($this -> name_field_extra == "id")
					$fields["id"] = "id";
				else
					$fields[$this -> name_field_extra] = $this -> elements[$this -> name_field_extra] -> getType();
					
			foreach($fields as $field => $type)
				if($type == 'enum')
				{
					if($this -> elements[$field] -> getProperty('foreign_key'))
						$data = $this -> elements[$field] -> getDataOfForeignKey();
					else
						$data = $this -> elements[$field] -> getValuesList();
					
					if(isset($data[$content[$field]]))
						$name .= $data[$content[$field]]." ";
				}
				else if($type == 'text' && $this -> elements[$field] -> getProperty("show_in_admin"))
					$name = Service :: cutText($content[$field], $this -> elements[$field] -> getProperty("show_in_admin"), " ...")." ";
				else if(($type == 'date' || $type == 'date_time') && preg_match("/^\d{4}-\d{2}-\d{2}/", $content[$field]))
					$name .= I18n :: dateFromSQL($content[$field])." ";
				else if(!in_array($type, array("file", "image", "multi_images", "many_to_one", "many_to_many", "password")))
					$name .= $content[$field]." ";
			
			if($name)
				return trim($name);
		}
		
		//If we have char type field with name 'name'
		foreach($this -> elements as $name => $object)
			if($name == 'name' && $object -> getType() == 'char' && isset($content[$name]) && $content[$name])
				return $content[$name];

		//Any email field
		foreach($this -> elements as $name => $object)
			if($object -> getType() == 'email' && isset($content[$name]) && $content[$name])
				return $content[$name];
		
		//Any char field
		foreach($this -> elements as $name => $object)
			if($object -> getType() == 'char' && isset($content[$name]) && $content[$name])
				return $content[$name];
				
		return isset($content['id']) ? I18n :: locale('record')." ".$content['id'] : '-';
	}
	
	public function findForeignParent()
	{
		foreach($this -> elements as $name => $object)
			if($object -> getProperty('is_parent') && $object -> getProperty('foreign_key'))
			{
				$class_name = $object -> getProperty('foreign_key');
				$related_object = new $class_name();
				$related_object_element = $related_object -> findElementByProperty('parent_for', get_class($this));
				
				if($related_object_element)
					return array('name' => $name, 'caption' => $object -> getCaption(), 'object' => $related_object_element);
				else
					return false;
			}
			
		return false;
	}
	
	public function findDependedOrderFilters()
	{
		//Searches order type fields with attribute "depend_on_enum")
		$results = [];
		
		foreach($this -> elements as $name => $object)
			if($object -> getType() == "order" && $object -> getProperty("depend_on_enum"))
			{
				$element = $this -> getElement($object -> getProperty("depend_on_enum"));
				
				if(is_object($element) && $element -> getType() == "enum")
					$results[$name] = array($object -> getProperty("depend_on_enum"), $element -> getCaption());
				else
				{
					echo "</script>\n</head>\n";
					$message = "Wrong value of 'depend_on_enum' option in field '".$name."' of model '".get_class($this)."'. ";
					$message .= "Use a name of enum field of current model.";
					Debug :: displayError($message);
				}
			}
		
		return $results;
	}
	
	public function checkIncludeCode(string $file_name)
	{
		$new_file = $this -> includes_folder.$this -> getModelClass()."-".$file_name;
		
		if(is_file($new_file))
			return $new_file;
		else
		{
			$old_file = $this -> registry -> getSetting("IncludePath")."models/includes/";
			$old_file .= $this -> getModelClass()."-".$file_name;
			
			return is_file($old_file) ? $old_file : false;
		}
	}
		
	public function addError(mixed $error)
	{
		$this -> errors[] = $error;

		return $this;
	}
	
	public function removeError(string $key)
	{
		if(is_numeric($key) && isset($this -> errors[$key]))
			unset($this -> errors[$key]);
		
		return $this;
	}
	
	public function addErrors(array $errors)
	{
		foreach($errors as $error)
			$this -> addError($error);
		
		return $this;
	}	
	
	public function dropErrors()
	{
		$this -> errors = [];
		return $this;
	}
	
	public function setRequiredFields(mixed $names)
	{
		if(!$names)
			return;
		
		if($names == '*')
			$names = array_keys($this -> elements);
		
		if(is_array($names))
			foreach($names as $name)
				if(isset($this -> elements[$name]))
					$this -> elements[$name] -> setRequired(true);
				
		return $this;
	}
	
	public function setLastOrder(object $object)
	{
		//Sets the last order number for order type field
		$condition = "";
		
		if($this -> parent_field && $this -> parent_id)
			$condition = " AND `".$this -> parent_field."`='".$this -> parent_id."'";
		
		$number = $object -> getLastNumber($this -> table, $condition) + 1;
		$object -> setValue($number);
		
		return $number;
	}
	
	public function checkUniqueFields(array $content)
	{
		//When we restore he record from garbage we must chrck all unique fields if model
		foreach($this -> elements as $name => $object)
			if($object -> getProperty("unique") && isset($content[$name]) && $content[$name])
				if($this -> db -> getCount($this -> table, "`".$name."`='".$content[$name]."'"))
					return $object -> getCaption();
	}
	
	public function checkCurrentTab()
	{
		if(count($this -> display_params["fields_groups"]))
			if(isset($_GET['current-tab']) && is_numeric($_GET['current-tab']))
				return intval($_GET['current-tab']);
	}
	
	public function defineFilesTypesFields()
	{
		$files_types = ['image','file','multi_images']; //Data type with files
		$files_fields = [];
		
		//Looks for the data types which use uploaded files
		foreach($this -> elements as $name => $object)
			if(in_array($object -> getType(), $files_types))
				$files_fields[] = $name;
		
		return $files_fields;
	}
	
	public function getVersionsLimit()
	{
		if(isset($this -> display_params["versions_limit"]))
			if(is_numeric($this -> display_params["versions_limit"]) || !$this -> display_params["versions_limit"])
				return $this -> display_params["versions_limit"];
			else if($this -> display_params["versions_limit"] == "config")
			{
				$limit = $this -> registry -> getSetting("ModelVersionsLimit");
				return ($limit === false || is_numeric($limit)) ? $limit : 25;
			}
			
		return 25;
	}
	
	public function getPagerLimitForQuickEdit()
	{
		$php_limit = ini_get("max_input_vars") - 5;
		$model_limit = is_object($this -> pager) ? $this -> pager -> getLimit() : 10;
		$allowed_fields = array("char", "url", "redirect", "email", "phone", "int", "float");
		$pager_limits = array(5, 10, 15, 20, 30, 50, 100, 200, 300, 500);
		$target_fields = 0;
		
		foreach($this -> getFieldsToDisplay() as $field)
			if(isset($this -> elements[$field]) && $this -> checkIfFieldEditable($field))
				if(in_array($this -> elements[$field] -> getType(), $allowed_fields))
					$target_fields ++;
				
		$total_inputs = $this -> pager -> getTotal() * $target_fields;
		
		if(!$target_fields || $target_fields * $model_limit <= $php_limit)
			return 0;
		else if($total_inputs <= $php_limit)
			return 0;
		else
			for($i = (count($pager_limits) - 1); $i >= 0; $i --)
				if($target_fields * $pager_limits[$i] <= $php_limit)
					return $pager_limits[$i];
					
		return 0;
	}
	
	public function prepareFileForRecord(string $file, string $field)
	{
		$field = $this -> getElement($field);
		$types = ["file", "image", "multi_images"];
		$file_name = basename($file);
		$file_name = Service :: translateFileName($file_name).".".Service :: getExtension($file_name);
		
		if(!is_file($file) || !Service :: getExtension($file) || !is_object($field) || !in_array($field -> getType(), $types))
			return '';
		
		$folder = $this -> getModelClass()."-".($field -> getType() == "file" ? "files" : "images");
		$folder = $this -> registry -> getSetting('FilesPath')."models/".$folder."/";
		
		if(!is_file($folder.$file_name))
			$moved_file = $folder.$file_name;
		else
		{
			$counter = intval($this -> registry -> getDatabaseSetting('files_counter')) + 1;
			$moved_file = $folder.Service :: removeExtension($file_name)."-f".$counter.".".Service :: getExtension($file_name);
			$this -> registry -> setDatabaseSetting('files_counter', $counter);				
		}
		
		if(!is_dir(dirname($moved_file)))
			@mkdir(dirname($moved_file));
				
		@copy($file, $moved_file);
		
		return is_file($moved_file) ? $moved_file : '';
	}
	
	public function getMigrations()
	{
		if(isset($this -> migrations) && is_array($this -> migrations))
			return $this -> migrations;
	}
	
	public function checkIfAdminSortingFieldAllowed(string $field)
	{
	    if($field == "id")
	        return true;
	    
		if(!isset($this -> elements[$field]))
			return false;
		
		$type = $this -> elements[$field] -> getType();
		
		if($type == "group" || ($type == "many_to_one" && !$this -> elements[$field] -> getProperty('allow_sorting')))
			return false;
				
		return true;
	}

	public function __get($name)
	{
		if($name === 'pager')
			return $this -> paginator;
	}

	public function __isset($name)
	{
		if($name === 'pager')
			return isset($this -> paginator);
	}
}
