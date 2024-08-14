<?php
/**
 * Main parent class for all regular models.
 * Many methods and vars are extended from parent classes.
 * Contains methods for both application front and admin panel.
 */
class Model extends ModelBase
{
	/**
	 * Main title of current model in admin panel.
	 * @var string
	 */
	protected $name;

	/**
	 * Array of model fields with title, datatype and sql column name.
	 * @var array
	 */
	protected $model_elements = [];

	/**
	 * Array of parameters for admin panel like fields groups and hidden fields.
	 * @var array
	 */
	protected $model_display_params = [];

	/**
	 * Content storage for child simple model (ModelSimple class).
	 * @var array|null
	 */
	protected $data = null;

	public function __construct(array $row_data = [])
	{
		$this -> registry = Registry :: instance();
		$this -> db = Database :: instance();
		$this -> i18n = I18n :: instance();
		$this -> table = $this -> registry -> defineModelTableName(get_class($this));
		
		if(count($this -> model_elements))
		{
			foreach($this -> model_elements as $field)
				$this -> instanceElement($field);
		}
		else
		{
			$message = "To run the model '".get_class($this)."' you need to specify the model fields array in property ";
			$message .= "'model_elements' in model class.";
			Debug :: displayError($message);
		}
				
		foreach($this -> elements as $name => $object)
		{	
			$type = $object -> getType();
			
			if($type == 'many_to_many')
			{				
				$model_class = $object -> getProperty('related_model');
				
				if($model_class === '' || !class_exists($model_class))
				{
					$message = "You need to correctly specify 'related_model' ";
					$message .= "parameter for element '".$name."' in model '".get_class($this)."'.";
					Debug :: displayError($message);
				}

				if(Registry :: getInitialVersion() < 3.0 && $object -> getProperty('linking_table') === '')
				{
					$message = "You need to specify 'linking_table' ";
					$message .= "parameter for element '".$name."' in model '".get_class($this)."'.";
					Debug :: displayError($message);
				}
				else if(Registry :: getInitialVersion() >= 3.0 && $object -> getProperty('linking_table') !== '')
				{
					$message = "Since MV version 3.0 you don't need to specify 'linking_table' property ";
					$message .= " for element '".$name."' in model '".get_class($this)."'.";

					Debug :: displayError($message);
				}

				if(Registry :: getInitialVersion() >= 3.0)
					$object -> defineLinkingTable(get_class($this));
			}
			else if($type == 'many_to_one')
			{	
				$model_class = $object -> getProperty('related_model');
			
				if(!$model_class || !class_exists($model_class))
				{
					$message = "You need to correctly specify 'related_model' parameter for element '".$name."' ";
					$message .= "in model '".get_class($this)."'.";
					Debug :: displayError($message);
				} 
			}
			else if($type == 'enum')
			{
				if(!$object -> getProperty('foreign_key'))
				{
					$values_list = $object -> getProperty('values_list');
				
					if(isset($values_list[0]))
					{
						$message = "It's forbidden to set up value with key '0' for element '".$name."' ";
						$message .= "in model '".get_class($this)."'.";
						Debug :: displayError($message);
					}
				}
				
				if($object -> getProperty('is_parent') && $object -> getProperty('foreign_key'))
				{
					$model_object = $object -> getProperty('foreign_key');
					$model_object = new $model_object();
					
					if(!$model_object -> findElementByProperty('parent_for', get_class($this)))
					{					
						$message = "You need to set up field with type 'parent' and property 'parent_for' ";
						$message .= "in model '".$object -> getProperty('foreign_key')."', because you have ";
						$message .= "in model '".get_class($this)."' ";
						$message .= "the enum field '".$name."' with property 'is_parent' and foreign key '";
						$message .= $object -> getProperty('foreign_key')."'.";
						Debug :: displayError($message);
					}
				}
			}
			else if($type == 'parent')
			{
				if(!$this -> parent_field)
				{
					$object -> setSelfModel(get_class($this));
					$this -> parent_field = $name;
					
					if(isset($_GET[$this -> parent_field]))
					{
						if($_GET[$this -> parent_field] != 'all')
						{
							$this -> parent_id = intval($_GET[$this -> parent_field]);
							$this -> url_params['parent'] = $this -> parent_field."=".$this -> parent_id;
						}
					}
					else
						$this -> parent_id = -1;
				}
				else
					Debug :: displayError("Model '".get_class($this)."' can have only 1 field of 'parent' data type.");
				
				if($object -> getProperty('foreign_key'))
				{
					$message = "Element '".$name."' of model '".get_class($this)."' can not have 'foreign_key' property ";
					$message .= "because the type of element is 'parent'.";
					Debug :: displayError($message);
				}
			}
			else if($type == 'group')
				$object -> setTable($this -> table);
		}
		
		//Drops initial params, now all is taken from model object elements
		unset($this -> model_elements);
		
		//For admin panel
		$this -> url_params['model'] = "model=".strtolower(get_class($this));
				
		//Passed data to create new row in db
		if(count($row_data)) 
			$this -> getDataFromArray($row_data);
		
		//Passing params for admin panel for this model
		if(isset($this -> model_display_params))
		{   
			if(is_array($this -> model_display_params))
				foreach($this -> model_display_params as $name => $value)
					if(isset($this -> display_params[$name]))
						$this -> display_params[$name] = $value;
				
			unset($this -> model_display_params);
		}
		
		if(isset($this -> name) && preg_match("/^\{.*\}$/", $this -> name))
			$this -> name = I18n :: locale(preg_replace("/^\{(.*)\}$/", "$1", $this -> name));
			
		$this -> includes_folder = $this -> registry -> getSetting("IncludePath").$this -> includes_folder;
		$this -> root_path = $this -> registry -> getSetting("MainPath");
		$this -> include_path = $this -> registry -> getSetting("IncludePath");
	}

	public function getName()
	{
		if(isset($this -> name))
			return $this -> name;
		else
			return get_class($this);
	}
	
	public function setName($name)
	{
		$this -> name = $name;
	}
	
	public function checkModelName($name)
	{
		if($this -> registry -> getSetting('ForbiddenModelsNames'))
			if(in_array($name, $this -> registry -> getSetting('ForbiddenModelsNames')))
			{
				$message = "The name of model '".$name."' is forbidden, please try different one.";
				Debug :: displayError($message);
			}
	}
	
	public function isSimpleModel()
	{
		return (get_parent_class($this) === 'ModelSimple');
	}

	static public function elementsFactory(array $field_data, string $model_class = ''): object
	{
		//Defines extra params
		$extra_params = isset($field_data[3]) && is_array($field_data[3]) ? $field_data[3] : [];

		if($model_class !== '')
			$extra_params["model"] = strtolower($model_class);

		//Translates the name if needed
		if(preg_match("/^\{.*\}$/", $field_data[0]))
			$field_data[0] = I18n :: locale(preg_replace("/^\{(.*)\}$/", "$1", $field_data[0]));

		$map = [
			'date_time' => 'DateTimeModelElement',
			'multi_images' => 'MultiImagesModelElement',
			'many_to_many' => 'ManyToManyModelElement',
			'many_to_one' => 'ManyToOneModelElement'
		];

		//Defines datatype on current element (field)
		if(array_key_exists($field_data[1], $map))
			$datatype = $map[$field_data[1]];
		else
			$datatype = ucfirst($field_data[1])."ModelElement";

		return new $datatype(
			$field_data[0],
			$field_data[1],
			$field_data[2],
			$extra_params
		);
	}
	
	public function instanceElement($field_data)
	{
		self :: checkElement($field_data, get_class($this));
		$element = self :: elementsFactory($field_data, get_class($this));

		$this -> elements[$element -> getName()] = $element;
	}
	
	static public function checkElement(array $element_data, string $context = 'form')
	{
		$registry = Registry :: instance();
		$context = $context === 'form' ? $context : "model '".$context."'";
		
		if(count($element_data) < 3)
		{	
			$message = "The field description of ".$context." must have at least 3 parameters:";
			$message .= " caption (title), datatype and field name for sql table.";
			Debug :: displayError($message);
		}
		else if(!in_array($element_data[1], $registry -> getSetting('ModelsDataTypes')))
		{
			$message = "Undefined element data type '".$element_data[1]."' in ".$context.". ";
			$message .= "Allowed types are: ".implode(', ', $registry -> getSetting('ModelsDataTypes')).".";
			Debug :: displayError($message);
		}
		else if(in_array($element_data[2], $registry -> getSetting('ForbiddenFieldsNames')))
		{
			$message = "You can't use the field name '".$element_data[2]."' in ".$context;
			$message .= " or in any other model or form either.";
			Debug :: displayError($message);
		}
		else if($registry -> getInitialVersion() >= 1.1 && strpos($element_data[2], "-") !== false)
		{
			$message = "You can't use symbol '-' in field name '".$element_data[2]."' of ".$context;
			$message .= ", use '_' instead.";
			Debug :: displayError($message);
		}		
	}
	
	public function runPagerFilterSorter()
	{
		$sorter_data = $filters_data = [];
				
		foreach($this -> elements as $name => $object)
		{
			$type = $object -> getType();
			$sorter_data[$name] = $type;
			
			if($object -> getType() == "text" && $object -> getProperty("display_method"))
				continue;
			else if($object -> getType() == 'many_to_one' || $object -> getType() == 'many_to_many')
				continue;
			
			if($this -> checkIfFieldVisible($name))
				$filters_data[$name] = ['type' => $type, 'caption' => $object -> getCaption()];
			else
				continue;
		
			if($type == 'parent' && !$object -> getProperty("long_list"))
				$object -> getAvailbleParents($this -> table);
			
			if($type == 'enum' || $type == 'group')
			{
				$filters_data[$name]['long_list'] = $object -> getProperty('long_list');
				
				if($type == 'enum')
				{
					if($filters_data[$name]['long_list'])
						if($object -> getProperty('foreign_key'))
							$filters_data[$name]['foreign_key'] = strtolower($object -> getProperty('foreign_key'));
							
					if($object -> getProperty('is_parent') && $object -> getProperty('show_parent'))
						$filters_data[$name]['show_parent'] = true;
					
					$filters_data[$name]['values_list'] = $object -> getProperty('values_list');
					$filters_data[$name]['model'] = $this -> getTable();
					$filters_data[$name]['name_field'] = $object -> getProperty('name_field');
					$filters_data[$name]['name_field_extra'] = $object -> getProperty('name_field_extra');
				}
				else
				{
					if(!$filters_data[$name]['long_list'])
						$filters_data[$name]['values_list'] = $object -> getTableRecords();
					
					$filters_data[$name]['element'] = $object;
					$filters_data[$name]['table'] = $this -> getTable();
				}
			}
			/*
			else if($type == 'many_to_many')
			{
				$filters_data[$name]['long_list'] = $object -> getProperty('long_list');
				
				if(!$filters_data[$name]['long_list'])
					$filters_data[$name]['values_list'] = $object -> getDataOfManyToMany();
					
				$filters_data[$name]['element'] = $object;
			}
			*/
		}
		
		$sorter_data['id'] = 'int';
		$this -> sorter = new Sorter($sorter_data);
		$this -> filter = new Filter($filters_data, '', $this);
		
		$condition = '';
		
		if($this -> parent_field && $this -> parent_id)
			$condition = "`".$this -> parent_field."`='".$this -> parent_id."'";
		
		$this -> total = $this -> db -> getCount($this -> table, $condition);
		$this -> paginator = new Paginator($this -> total, 10);
		
		$this -> processUrlRarams();
		
		return $this;
	}
	
	public function loadRelatedData()
	{
		foreach($this -> elements as $name => $object)
		{
			$type = $object -> getType();
			
			if($type == 'parent' && !$object -> getProperty("long_list"))
				$object -> getAvailbleParents($this -> table); //Allowed parents
			else if($type == 'enum' && !$object -> getProperty("long_list"))
				$object -> defineValuesList(get_class($this)); //Load related values from other models
			else if($type == 'many_to_one')
			{
				if($object -> getProperty("related_field"))
					continue;
				
				$model_class = $object -> getProperty('related_model');
				$model_object = new $model_class(); //Object which has keeps the current object (self) as foreign key

				//Gets the name of field under the foreign key and puts it to needed element
				$object -> setRelatedFeild($model_object -> getNameOfForeignKeyField(get_class($this)));
			}
		}
		
		return $this;
	}
		
	public function getDataFromArray($source)
	{
		foreach($source as $name => $value)
			if(isset($this -> elements[$name]))
			{
				$type = $this -> elements[$name] -> getType();
				
				if($type == "date")
					$this -> elements[$name] -> setDisplaySelects(false); 
				
				if(($type == "file" || $type == "image") && is_file($value))
					$this -> elements[$name] -> setRealValue($value, "");
				else if($type == "order" || $type == "parent")
				{
					if($type == "order" && !$value)
					{
						if($parent_field = $this -> getParentField())
							if(isset($source[$parent_field]) && $source[$parent_field])
								$this -> setParentId($source[$parent_field]);
							
						$this -> setLastOrder($this -> elements[$name]);
					}
					else
						$this -> elements[$name] -> setValue(intval($value));
				}
				else
					$this -> elements[$name] -> setValue($value);
			}

		return $this;
	}
	
	public function getDataFromPost()
	{
		$this -> getDataFromArray($_POST);
		
		$row = (get_parent_class($this) != "ModelSimple") ? $this -> getById() : $this -> loadData();

		//Tmp files cleanup
		Filemanager :: deleteOldFiles($this -> registry -> getSetting("FilesPath")."tmp/");
		Filemanager :: deleteOldFiles($this -> registry -> getSetting("FilesPath")."tmp/filemanager/");
		
		foreach($this -> elements as $name => $object)
		{
			$type = $object -> getType();
			
			if(!$this -> checkIfFieldEditable($name) && isset($row[$name]))
			{
				if($type == "date" || $type == "date_time")
				{
					$this -> elements[$name] -> setDisplaySelects(false);					
					$object -> setValue(I18n :: dateFromSQL($row[$name], "no-seconds"));
				}
				else if($type == 'multi_images')
					$object -> setValuesWithRoot($row[$name]);
				else if($type == 'image' || $type == 'file')
				{
					$file = Service :: addFileRoot($row[$name]);
					
					if(is_file($file))
						$object -> setRealValue($file, basename($file));
				}		
				else
					$object -> setValue($row[$name]);				
			}
			else if($type == 'image' || $type == 'file')
			{
				if(isset($_FILES[$name]) && !isset($_POST['value-'.$name]))
					$object -> setValue($_FILES[$name]);
				else if(isset($_POST['value-'.$name]) && $_POST['value-'.$name])
				{
					$data = $object -> unpackUploadedFileData($_POST['value-'.$name]);
					
					if(is_array($data))
						$object -> setRealValue($data['path'], $data['name']);
				}
			}
			else if($type == 'multi_images')
				$object -> setValue(base64_decode($object -> getValue()));
		}
		
		return $this;
	}
	
	public function checkForChildren()
	{
		$children = false;
		$arguments = func_get_args();
		$we_search_parents = (isset($arguments[0]) && $arguments[0] == 'parents-search');
		
		if($this -> parent_field && $this -> parent_id)
		{
			$object = $this -> elements[$this -> parent_field];
			$children = (int) $this -> db -> getCount($this -> table, "`".$this -> parent_field."`='".$this -> id."'");
					
			if((!$children || $we_search_parents) && $object -> getProperty('parent_for'))
			{
				$related_model = $object -> getProperty('parent_for'); //Name of related model
				$related_object = new $related_model(); //Object of related model
	
				//Model element of parent type which shows parent (value from first model)
				$related_object_element = $related_object -> findElementByProperty('foreign_key', get_class($this));
				
				if($related_object_element -> getProperty('is_parent')) //One more check that it's the needed element
				{
					$field = $related_object_element -> getName(); //Name of other parent field
					$children = (int) $this -> db -> getCount($related_object -> getTable(), "`".$field."`='".$this -> id."'");
				}			
			}
			
			if($children)
				return $children;
		}
		
		//If we we serching parents we stop here
		if($we_search_parents)
			return false; 
		
		//Checks if we have foreign key fields in other models
		foreach(array_keys(Registry :: get('ModelsLower')) as $model)
			if($model != $this -> getModelClass())
			{
				$model_object = new $model(); //Creates the objects of models to check foreign keys
				
				foreach($model_object -> getElements() as $name => $object)
					if($object -> getProperty('foreign_key'))
						if($this -> getModelClass() == strtolower($object -> getProperty('foreign_key')))
						{
							$number = (int) $this -> db -> getCount($model_object -> getTable(), "`".$name."`='".$this -> id."'");
							
							if($number)
								return $model;
						}
			}
		
		return $children;
	}
	
	public function defineAvailableParents($checked_ids)
	{
		$options_xml = "<value id=\"\">".I18n :: locale("select-value")."</value>\n";
		$options_xml .= "<value id=\"-1\">".I18n :: locale('root-catalog')."</value>\n";
		
		foreach($checked_ids as $key => $id)
			$checked_ids[$key] = intval($id);
		
		$denied_ids = $checked_ids;
		$object = $this -> elements[$this -> parent_field]; //Object of parent type
		
		foreach($checked_ids as $id) //Gets all child records of selected records
		{
			$object -> defineChildElements($this -> getTable(), $id);
			
			foreach($object -> getChildRecords() as $child)
				if(!in_array($child, $denied_ids)) //We cant use child records as parents
					$denied_ids[] = $child;
		}
		
		//Gets all possible parents exept for children from previous step
		$next_step_ids = $this -> db -> getColumn("SELECT `id` FROM `".$this -> getTable()."` 
												   WHERE `id` NOT IN(".implode(',', $denied_ids).")");
		
		//Checks for child records in related models
		foreach($next_step_ids as $id)
			if($this -> checkForChildren('parents-search'))
				$denied_ids[] = $id;
		
		//Filters the parents
		$next_step_ids = $this -> db -> getColumn("SELECT `id` FROM `".$this -> getTable()."` 
												   WHERE `id` NOT IN(".implode(',', $denied_ids).")");		
		$allowed_parents = [];
		
		foreach($next_step_ids as $id) //Finally takes out lost records with have no full path to the root catalog
			if(in_array(-1, $object -> getParentsForOneRecord($this -> getTable(), $id)))
				$allowed_parents[] = $id;
				
		$values_list = $object -> getProperty('values_list'); //They checked for max depth
		
		$values_to_sort = [];
		
		foreach($allowed_parents as $id) //Wraps data in xml
			if(isset($values_list[$id]))
				$values_to_sort[$id] = $values_list[$id];

		natcasesort($values_to_sort);
		
		$arguments = func_get_args();
		
		if(isset($arguments[1]) && $arguments[1] == "autocomplete")
		{
			$values_to_sort[-1] = I18n :: locale('root-catalog');
			return $values_to_sort;
		}
		
		foreach($values_to_sort as $id => $name)
			$options_xml .= "<value id=\"".$id."\">".$name."</value>\n";

		return $options_xml;
	}
	
	public function getParentsForMultiAutocomplete($request, $ids)
	{
		//Autocomplete for parent type if long list option is on
		$request_re = Service :: prepareRegularExpression(trim($request));
		$result_rows = $this -> defineAvailableParents($ids, "autocomplete");
				
		foreach($result_rows as $key => $value)
			if(!preg_match("/".$request_re."/ui", $value))
				unset($result_rows[$key]);
		
		foreach($result_rows as $key => $value)
			$result_rows[$key] = htmlspecialchars_decode($value, ENT_QUOTES);
		
		return array('query' => $request,  
					 'suggestions' => array_values($result_rows),
					 'data' => array_keys($result_rows));
	}
	
	public function validate()
	{
		$arguments = func_get_args();
		$fields = (isset($arguments[0]) && is_array($arguments[0])) ? $arguments[0] : false;
		$simple_model = (get_parent_class($this) == "ModelSimple");
		
		foreach($this -> elements as $name => $object)
			if(!$fields || in_array($name, $fields))
			{
				if($object -> getType() == 'password')
					if(($this -> id && !$simple_model) || ($simple_model && $this -> data[$name]))
						$object -> setRequired(false); //We may not fill the password on update even if it was required				
								
				if($object -> validate(get_class($this), $this -> id) -> getError()) //Collects all errors
	            	$this -> errors[] = array($object -> getCaption(), $object -> getError(), $name);
			}
		
      	return (bool) count($this -> errors);		
	}
	
	public function updateManyToManyTables(mixed $fields = null)
	{
		foreach($this -> elements as $name => $object)
			if($object -> getType() === 'many_to_many') //If its M2M element
			{
				//Update from admin panel with not editable m2m fields
				if($fields === 'update' && (!$this -> checkIfFieldEditable($name) || !$this -> checkIfFieldVisible($name)))
					continue; //TODO think about m2m hidden and visible fields

				//If we update record from front we pass fields to update
				if(is_array($fields) && !in_array($object -> getName(), $fields))
					continue;
				
				$table = $object -> getProperty('linking_table'); //Table with first_id, second_id							
				$self_id = $object -> getSelfId(); //Name of M2M field in table
				$opposite_id = $object -> getOppositeId(); //Name of other field in M2M table
				$selected_ids = $object -> getSelectedIds(); //Selected ids of M2M field by current element

				//Deletes all links from table to rewrite them
				$this -> db -> query("DELETE FROM `".$table."` 
									  WHERE `".$opposite_id."`='".$this -> id."'");
				
				//Adds links one by one
				if(is_array($selected_ids) && count($selected_ids))
					foreach($selected_ids as $value)
						$this -> db -> query("INSERT INTO `".$table."`(`".$self_id."`,`".$opposite_id."`) 
											  VALUES ('".$value."','".$this -> id."')");
			}
	}
	
	public function prepareElementValue($object)
	{
		$type = $object -> getType();
	
		if($type == 'order' && !$object -> getValue())
			$this -> setLastOrder($object);
		else if ($type == 'image' || $type == 'file')
			$object -> copyFile(get_class($this));
		else if($type == 'multi_images')
			$object -> copyImages(get_class($this));
		else 
			$object -> prepareValue();
	}
	
	public function create()
	{
		$fields = $values = $fields_and_values = $version_dump = [];
		
		$arguments = func_get_args();
		
		//If we have passeed some extra data for version dump
		if(isset($arguments[0]) && is_array($arguments[0]))
			$version_dump = $arguments[0];
		
		foreach($this -> elements as $name => $object)
		{
			$type = $object -> getType();
			$this -> prepareElementValue($object);
			
			if($type == 'text' && $object -> getProperty('virtual'))
				continue;
				
			if($name == 'id' || $type == 'many_to_one' || $type == 'many_to_many')
			{
				if($type == 'many_to_many')
					if($object -> getSelectedIds())
					{
						$selected_ids = $object -> orderSelectedIds($object -> getSelectedIds());
						$version_dump[$name] = implode(',', $selected_ids); //TODO add beforeCreate() ability
					}
					else
						$version_dump[$name] = '';
						
				continue;
			}
			else
			{
				$param = ($type == 'date' || $type == 'date_time') ? 'sql' : '';
				$value = $object -> getValue($param);
				
				if($type == 'image' || $type == 'file')
					$value = Service :: removeFileRoot((string) $value);
					
				$value = Service :: cleanHtmlSpecialChars((string) $value);				
				$version_dump[$name] = $fields_and_values[$name] = $value;
			}			
		}
		
		if(method_exists($this, "beforeCreate")) //Trigger before creating a record 
		{
			$result_values = $this -> beforeCreate($version_dump);
			
			if(is_array($result_values) && count($result_values)) //If some values were changed
				foreach($result_values as $name => $value)
					if(array_key_exists($name, $fields_and_values))
					{
						$value = Service :: cleanHtmlSpecialChars(htmlspecialchars($value, ENT_QUOTES));
						$version_dump[$name] = $fields_and_values[$name] = $value;
					}					
		}
		
		foreach($fields_and_values as $name => $value) //Prepearing values for SQL query
		{
			$fields[] = "`".$name."`";
			$values[] = $this -> db -> secure($value);
		}
		
		$this -> db -> query("INSERT INTO `".$this -> table."`(".implode(",", $fields).") 
				  			  VALUES(".implode(",", $values).")");
		
		$this -> id = (int) $this -> db -> lastId();		
		
		if($this -> getVersionsLimit())
		{
			$this -> versions = new Versions($this -> getModelClass(), $this -> id);		
			$this -> versions -> save($version_dump, $this -> user);
		}
		
		$version_dump['id'] = $this -> id;
		$name = $this -> tryToDefineName($version_dump);
		
		if($this -> user)
			Log :: write($this -> getModelClass(), $this -> id, $name, $this -> user -> getId(), "create");
		
		$this -> updateManyToManyTables();
		
		if(method_exists($this, "afterCreate"))
			$this -> afterCreate($this -> id, $version_dump);
			
		Cache :: cleanByModel($this -> getModelClass());
		
		return $this -> id;
	}
	
	public function read()
	{
		$arguments = func_get_args();
		$load_version = false;
		
		if(isset($arguments[0]) && is_numeric($arguments[0]) && intval($arguments[0]))
			$this -> id = intval($arguments[0]);
			
		if(!$this -> id) return $this;		
		
		if(isset($arguments[0]) && is_array($arguments[0]))
		{
			$row = $arguments[0]; //Gets the content from argument (maybe old version)
			$load_version = true;
		}
		else		
			$row = $this -> getById(); //Loads the content from db

		foreach($this -> elements as $name => $object)
		{
			$type = $object -> getType();
			
			if(isset($row[$name]))
			{
				if($type == 'image' || $type == 'file')
					$object -> setRealValue(Service :: addFileRoot($row[$name]), basename($row[$name]));
				else if($type == 'multi_images')
					$object -> setValuesWithRoot($row[$name]);
				else if($type == 'date' || $type == 'date_time')
				{
					if($type == 'date')
						$object -> setDisplaySelects(false); 
					
					$object -> setValue(I18n :: dateFromSQL($row[$name], "no-seconds"));
				}
				else if($type == 'password')
				{
					if(get_parent_class($this) == "ModelSimple" && !$this -> data[$name])
						continue;
					else
						$object -> setRequired(false) -> setValue("");
				}
				else if($type == 'many_to_many')
					$object -> setValue($row[$name]);
				else
					$object -> passValue($row[$name]);
			}
			else if($type == 'many_to_many')
				if(!$load_version)
					$object -> setRelatedId($this -> id) -> loadSelectedValues();
				else
					$object -> setValue(false);
					
			if($type == 'parent')
				$object -> setSelfId($this -> id) -> getAvailbleParents($this -> table);
		}
		
		return $this;
	}
	
	public function update()
	{
		if(!$this -> id)
			return $this;
		
		$values = $version_dump = $fields_and_values = [];
		
		$arguments = func_get_args();
		
		if(isset($arguments[0]) && is_array($arguments[0]))
			$version_dump = $arguments[0]; //Pass some extra data for dump
		
		$old_content = $this -> getById();
		
		foreach($this -> elements as $name => $object)
		{
			$type = $object -> getType();
			
			if($type == 'text' && $object -> getProperty("virtual"))
				continue;
			
			if($type != 'password' && $type != 'multi_images')
				$this -> prepareElementValue($object);
			else if($type == 'multi_images')
				$object -> copyImages(get_class($this), $old_content[$name]);					
			else if($type == 'password')
				if(is_null($object -> getValue()) || trim($object -> getValue() == ''))
					$object -> setValue($old_content[$name]);
				else
					$this -> prepareElementValue($object);
										
			$param = ($type == 'date' || $type == 'date_time') ? 'sql' : '';

			$value = $object -> getValue($param);
			
			if($type == 'image' || $type == 'file')
				$value = Service :: removeFileRoot($value ?? '');
			
			if(is_string($value))
				$value = Service :: cleanHtmlSpecialChars($value);

			if($name != 'id' && $type != 'many_to_many' && $type != 'many_to_one')
				if($this -> checkIfFieldEditable($name)) //Only editable fields can be updated
					$fields_and_values[$name] = $value;
			
			if(!$this -> checkIfFieldEditable($name) && $type != 'many_to_many' && $type != 'many_to_one')
				$version_dump[$name] = $old_content[$name];
			else if($type == 'many_to_many' && $object -> getSelectedIds())
			{
				$selected_ids = $object -> orderSelectedIds($object -> getSelectedIds());
				$version_dump[$name] = implode(',', $selected_ids);
			}
			else if($type != 'many_to_one')		
				$version_dump[$name] = $value;
		}		
		
		if(method_exists($this, "beforeUpdate"))
		{
			$result_values = $this -> beforeUpdate($this -> id, $old_content, $version_dump);
			
			if(is_array($result_values) && count($result_values)) //If some values were changed
				foreach($result_values as $name => $value)
					if(array_key_exists($name, $fields_and_values))
					{
						$value = Service :: cleanHtmlSpecialChars(htmlspecialchars($value, ENT_QUOTES));
						$version_dump[$name] = $fields_and_values[$name] = $value;
					}				
		}
			
		foreach($fields_and_values as $name => $value) //Prepearing values for SQL query
			$values[] = "`".$name."`=".$this -> db -> secure($value);

		$this -> db -> query("UPDATE `".$this -> table."` 
							  SET ".implode(",", $values)." 
							  WHERE `id`='".$this -> id."'");
		
		$this -> versions = new Versions($this -> table, $this -> id);
		$versions_limit = $this -> getVersionsLimit();
		
		if(!$versions_limit)
			$allow_write_log = true;
		else
		{
			$this -> versions -> setLimit($versions_limit);
			$allow_write_log = $this -> versions -> save($version_dump, $this -> user);
		}
		
		if($allow_write_log && count($values))
		{
			$version_dump['id'] = $this -> id;
			$name = $this -> tryToDefineName($version_dump);
			
			if($this -> user)
				Log :: write($this -> getModelClass(), $this -> id, $name, $this -> user -> getId(), "update");
		}
			
		$this -> updateManyToManyTables("update");
		
		if(method_exists($this, "afterUpdate"))
			$this -> afterUpdate($this -> id, $version_dump);
			
		Cache :: cleanByModel($this -> getModelClass());
				
		return $this;
	}
	
	public function delete()
	{
		$arguments = func_get_args();
		
		if(isset($arguments[0]) && is_numeric($arguments[0]))
			$this -> id = intval($arguments[0]);
		
		if(!$this -> id || !$content = $this -> getById())
			return $this;
		
		if(isset($arguments[0]) && is_array($arguments[0]))
			$content = array_merge($content, $arguments[0]);
			
		if(method_exists($this, "beforeDelete"))
			if($this -> beforeDelete($this -> id, $content) === false)
				return $this; //Stop operation and exit if false was returned by pre action

		$name = $this -> tryToDefineName($content); //Name for this element in garbage
				
		//Deletes related data from many-to-many tables
		foreach($this -> elements as $field => $object)
			if($object -> getType() == "many_to_many")
			{
				//Save m2m data for garbage
				$content[$field] = $object -> setRelatedId($this -> id) -> loadSelectedValues() -> getValue();
				
				$this -> db -> query("DELETE FROM `".$object -> getProperty("linking_table")."` 
						  		      WHERE `".$object -> getOppositeId()."`='".$this -> id."'");
			}

		$garbage = new Garbage(); //Puts all data into garbage
		$garbage -> save($this -> getModelClass(), $this -> id, $name, $content);
		
		//And deletes it from current table
		$this -> db -> query("DELETE FROM `".$this -> table."` WHERE `id`='".$this -> id."'");
		
		$name = $this -> tryToDefineName($content);
		
		if($this -> user)
			Log :: write($this -> getModelClass(), $this -> id, $name, $this -> user -> getId(), "delete");
			
		if(method_exists($this, "afterDelete"))
			$this -> afterDelete($this -> id, $content);
			
		$this -> drop();
		
		Cache :: cleanByModel($this -> getModelClass());
		
		return $this;
	}
	
	public function drop()
	{
		$this -> id = null;
		$this -> errors = [];
		
		foreach($this -> elements as $field => $object)
		{
			$object -> setValue("");
			$object -> setError("");
		}
		
		return $this;
	}
					
	public function displayModelFormInAdminPanel()
	{
		$html = $tabs_html = "";
		$current_tab = 1;
		$arguments = func_get_args();
		$action_create = false;
		$ordered_fields = [];
		
		if(isset($arguments[0]) && $arguments[0] == "create")
		{
			if(isset($arguments[1]) && $arguments[1])
				$current_tab = intval($arguments[1]);
				
			$action_create = true;
		}
		else if(isset($arguments[0]))
			$current_tab = intval($arguments[0]);
			
		if(count($this -> display_params["fields_groups"]))
		{
			$index = 1;
			$fields_groups = [];
			$tabs_html = "<div id=\"model-form-tabs\">\n<ul>\n";
			
			foreach($this -> display_params["fields_groups"] as $name => $fields_list)
				if(count($fields_list))
				{
					$active = ($index == $current_tab) ? " class=\"active\"" : "";
					$hidden_tr = ($current_tab == $index) ? "" : " no-display";
					$tabs_html .= "<li".$active." id=\"tab-group-".$index."\">".$name."</li>";
					
					foreach($fields_list as $field)
						if(isset($this -> elements[$field]))						
						{				
							$fields_groups[$field] = "tab-group-".$index.$hidden_tr;
							$ordered_fields[] = $field;
						}
					
					$index ++;
				}
				
			$tabs_html .= "</ul></div>\n";
		}
		else
		{
			$ordered_fields = array_keys($this -> elements);
			$fields_groups = false;
		}
		
		foreach($ordered_fields as $name)
		{
			//If this field is hiddent we skip it
			if(!$this -> checkIfFieldVisible($name))
				continue;
				
			if($fields_groups && !array_key_exists($name, $fields_groups))
				continue;
			
			$object = $this -> elements[$name];
			$type = $object -> getType();
			
			if(($type == 'many_to_many' || $type == 'many_to_one') && $this -> id)
				$object -> setRelatedId($this -> id);
			else if($type == 'order' && !$object -> getValue())
				$this -> setLastOrder($object);
			
			$caption = $object -> getCaption();
			
			if($object -> getProperty('required'))
				$caption .= "<span class=\"required\">*</span>";
				
			$css_class = "";
			
			if(in_array($type, array("char", "email", "password", "url", "redirect", "phone")))
				$css_class = " text";
			else if(in_array($type, array("int", "float", "order")))
				$css_class = " numeric";
			else if($type == "date" || $type == "date_time")
			{
				$css_class = " date";
				
				if($type == "date")
					$object -> setDisplaySelects(false); 
			}
				
			if($action_create && empty($_POST)) //Sets default values for date and bool fields
				if($type == "bool" && $object -> getProperty("on_create"))
					$object -> setValue(true);
				else if($type == "date" && $object -> getProperty("now_on_create"))
					$object -> setValue(I18n :: getCurrentDate());
				else if($type == "date_time" && $object -> getProperty("now_on_create"))
				{
					$current_date_time = I18n :: getCurrentDateTime();
					$current_date_time = preg_replace("/(\d{2}):(\d{2}):\d{2}/", "$1:$2", $current_date_time);
					$object -> setValue($current_date_time);
				}
					
			if($type == "group" && $this -> id)
				$object -> setId($this -> id);					
			
			//Passes the special flag when create new record
			if(!$action_create && !$this -> checkIfFieldEditable($name))
				$css_class .= " not-editable-field";

			if($object -> getError())
				$css_class .= " error-field";
							
			$html .= "<tr";
				
			if($fields_groups)
				$html .= " class=\"".$fields_groups[$name]."\"";
			
			$html .= ">\n<td class=\"field-name\">".$caption."</td>\n";
			$html .= "<td class=\"field-content".$css_class."\">\n";

			if($type == "text" && $object -> getProperty("display_method") && 
			   method_exists($this, $object -> getProperty("display_method")))
			{
				$method = $object -> getProperty("display_method");
				$html .= $this -> $method($object -> getValue());
			}
			else if($type == "bool")
				$html .= $object -> displayHtmlTumbler();
			else
				$html .= $object -> displayHtml();

			$html .= "</td>\n</tr>\n";
		}

		return $fields_groups ? [$html, $tabs_html] : $html;
	}
	
	static public function processErrorText($error, $object)
	{
		//Preparing for operations
		$registry = Registry :: instance();
		$type = false;
		
		if(!is_array($error))
			$error = array('', $error);
			
		$arguments = array('field' => $error[0]);
		$needs_translation = false;
		
		if(is_object($object))
			$type = $object -> getType();
			
		if(preg_match("/^\{.*\}$/", $error[1]))
		{
			$needs_translation = true;
			$error[1] = preg_replace("/^\{(.*)\}$/", "$1", $error[1]);
		}
		
		//Extracting the settings
		$images_formats = implode(', ', $registry -> getSetting("AllowedImages"));
		$max_image_size = $registry -> getSetting("MaxImageSize");
		$max_image_width = $registry -> getSetting("MaxImageWidth");
		$max_image_height = $registry -> getSetting("MaxImageHeight");
			
		$files_formats = false;
		$max_file_size = $registry -> getSetting("MaxFileSize");
		
		//Overriding of settings
		if($type == "image" || $type == "multi_images")
		{
			$property = $object -> getProperty('allowed_extensions');
			$images_formats = (is_array($property) && count($property)) ? implode(', ', $property) : $images_formats;

			$property = $object -> getProperty('max_size');
			$max_image_size = $property ? $property : $max_image_size;
				
			$property = $object -> getProperty('max_width');
			$max_image_width = $property ? $property : $max_image_width;
				
			$property = $object -> getProperty('max_height');
			$max_image_height = $property ? $property : $max_image_height;
				
		}
		else if($type == "file")
		{
			$property = $object -> getProperty('max_size');
			$max_file_size = $property ? $property : $max_file_size;
				
			$property = $object -> getProperty('allowed_extensions');
			$files_formats = (is_array($property) && count($property)) ? implode(', ', $property) : $files_formats;				
		}
		
		//Adding the parameters for error messages
		switch($error[1])
		{
			case 'error-date-format': $arguments['date_format'] = I18n :: getDateFormat();
				break;
			case 'error-date-time-format': $arguments['date_time_format'] = I18n :: getDateTimeFormat();
				break;
			case 'error-short-password': $arguments['min_length'] = $object -> getProperty('min_length');
										 $arguments['symbol'] = '*min_length'; //TODO remove password error?
				break;
			case 'error-length': $arguments['length'] = $object -> getProperty('length');
								 $arguments['symbol'] = '*length';					
			case 'error-min-length': $arguments['min_length'] = $object -> getProperty('min_length');
									 $arguments['symbol'] = '*min_length';
				break;
			case 'error-max-length': $arguments['max_length'] = $object -> getProperty('max_length');
								     $arguments['symbol'] = '*max_length';
				 break;
			case 'wrong-images-type': $arguments['formats'] = $images_formats;
				break;
			case 'wrong-file-type': if($files_formats) $arguments['formats'] = $files_formats;
				break;
			case 'too-heavy-file': $arguments['weight'] = I18n :: convertFileSize($max_file_size);
				break;
			case 'too-heavy-image': $arguments['weight'] = I18n :: convertFileSize($max_image_size);
				break;
			case 'too-large-image': $arguments['size'] = $max_image_width." x ".$max_image_height;
				break;
			case 'error-must-match': $arguments['field_1'] = $error[0]; $arguments['field_2'] = $error[3];
		}
		
		//The translation operation
		$text = $needs_translation ? I18n :: locale($error[1], $arguments) : $error[1];
		
		return preg_replace("/'([^']+)'/", "&laquo;$1&raquo;", $text);
	}
	
	public function displayFormErrors()
	{
		if(!count($this -> errors)) 
			return;
		
		$html = "<div class=\"form-errors\">\n";
		
		foreach($this -> errors as $error)
		{
			$object = is_array($error) && isset($error[2], $this -> elements[$error[2]]) ? $this -> elements[$error[2]] : false;
			$html .= "<p>".Model :: processErrorText($error, $object)."</p>\n";
		}
		
		return $html."</div>\n";
	}
		
	private function createComplexSql($type, $name, $group_by)
	{
		if($type == 'many_to_many') //If we need to count m2m values
		{
			$other_table = $this -> elements[$name] -> getProperty('linking_table');
			$other_field = $this -> table."_id";
		}
		else if($type == 'many_to_one') //If we count m2o values
		{
			$other_table = strtolower($this -> elements[$name] -> getProperty('related_model'));
			$other_field = (new $other_table) -> findElementByProperty('foreign_key', get_class($this));
			$other_field = $other_field -> getName();
		}

		//Sql query gets fields of current table and counts related fields of other related table
		$query = "SELECT ".$this -> table.".*, COUNT(".$other_table.".`".$other_field."`) AS `".$name."` 
				  FROM `".$this -> table."` 
				  LEFT JOIN `".$other_table."` 
				  ON ".$this -> table.".`id`=".$other_table.".`".$other_field."`";
		
		$query .= $group_by ? "GROUP BY ".$this -> table.".`id`" : "";
		
		return $query;
	}
	
	public function createSqlForTable()
	{
		$complex_filters = $this -> filter -> getComplexFilters();
		$sort_field_type = $this -> sorter -> getFieldType();
		$sort_field_name = $this -> sorter -> getField();
		$filter_params = $this -> filter -> getParamsForSQL();
		$filter_params = preg_replace("/(`\w+`)/", $this -> table.".$1", $filter_params);
		$sorter_params = $this -> sorter -> getParamsForSQL();
				
		if($sort_field_type == 'many_to_many' || $sort_field_type == 'many_to_one')
			$query = $this -> createComplexSql($sort_field_type , $sort_field_name, false);
		else if($sort_field_type == 'enum' && $this -> elements[$sort_field_name] -> getProperty('foreign_key'))
		{
			//In case of foreign key sorting we need to put the real names of foreign key fields from other table
			//and sort by them the result of sql query
			$class_name = $this -> elements[$sort_field_name] -> getProperty('foreign_key');
			$object = new $class_name();
			$foreign_sort_field = $object -> getTable().".`".$this -> elements[$sort_field_name] -> getProperty('name_field')."`";
			
			$query = "SELECT ".$this -> table.".* ";
			$query .= "FROM `".$this -> table."` LEFT JOIN `".$object -> getTable()."` ";
			$query .= "ON ".$this -> table.".`".$sort_field_name."`=".$object -> getTable().".`id`";
		}
		else //Normal sorting by regular simple fields
			$query = "SELECT * FROM `".$this -> table."`";
				
		$query .= $filter_params ? " WHERE ".$filter_params : ""; //Adds all filters into sql query
		
		if($this -> parent_field && $this -> parent_id) //Adds parent field as condition if it was passed
		{
			$query .= $filter_params ? " AND " : " WHERE ";
			$query .= " ".$this -> table.".`".$this -> parent_field."`='".$this -> parent_id."'";
		}
				
		if(($sort_field_type == 'many_to_many' || $sort_field_type == 'many_to_one'))
			$query .= " GROUP BY ".$this -> table.".`id`"; //Some sql for complex fields
		
		if($filter_params) //If we have any filters we must recount the number of rows to display
		{
			$this -> total = $this -> db -> getCount($this -> table, $this -> filter -> getParamsForSQL());
			$this -> paginator -> setTotal($this -> total);	 //Passes new total number of rows into pager
		}
		
		if($sorter_params) //Order of sorting for sql result
			if(isset($foreign_sort_field) && $foreign_sort_field) //If we sort by foreign key
				$query .= preg_replace("/`\w+`/", $foreign_sort_field, $sorter_params);
			else
				$query .= $sorter_params;
			
		$query .= $this -> paginator -> getParamsForSQL(); //Adds pager limits for sql query

		$this -> sql_for_table = $query;

		return $this;
	}
		
	public function defineTableFields()
	{
		$fields = $passed_fields = [];
		
		if($this -> display_params['mass_actions'])
			if($this -> display_params['update_actions'] || $this -> display_params['delete_actions'])
				$fields[] = 'checkbox'; //Checkbox to select the element for multi action
		
		$arguments = func_get_args();
		
		if(isset($arguments[0]) && $arguments[0]) //Fields from settings of user
			$passed_fields = explode(',', $arguments[0]);
		else if(count($this -> display_params['default_table_columns'])) //Fields from model settings
			$passed_fields = $this -> display_params['default_table_columns'];
		
		if(count($passed_fields)) //Passing displayed fields from session
			foreach($passed_fields as $field)
				if((array_key_exists($field, $this -> elements) && $this -> checkIfFieldVisible($field)) || $field == 'id')
					$fields[] = $field;
							
		//If fields are not setuped we will determine available field to display
		if(!count($fields) || (count($fields) == 1 && $fields[0] == 'checkbox'))
		{
			$count = 0;
			$max_fields = 5; //Max number of fields (columns)
			
			if($name_field = $this -> tryToFindNameField()) //Field for first column
				if($this -> checkIfFieldVisible($name_field))
				{
					$fields[] = $name_field;
					$count ++;
				}
			
			//If we have image field we show it after name
			$image_field = $this -> checkFieldByType('multi_images');
			$image_field = $image_field ? $image_field : $this -> checkFieldByType('image');
						
			if($image_field && $this -> checkIfFieldVisible($image_field))
			{
				$fields[] = $image_field;
				$count ++;
			}
			
			//If last column is actions like update / delete
			if($this -> display_params['update_actions'] || $this -> display_params['delete_actions'])
				$count ++;
			
			//And here we add fields one by one while we have space
			foreach($this -> elements as $name => $object)
				if($object -> getType() != 'password' && $object -> getType() != 'order' && 
				   $this -> checkIfFieldVisible($name))
				{
					if($object -> getType() == 'text' && !$object -> getProperty('show_in_admin'))
						continue;
					
					if($count < $max_fields && !in_array($name, $fields))
					{
						$fields[] = $name;
						$count ++;
					}
				}

			$order_field = $this -> checkFieldByType('order');
			
			if($order_field && $this -> checkIfFieldVisible($order_field))
			{
				array_pop($fields);			
				$fields[] = $order_field;
			}
		}
		
		if($this -> display_params['update_actions'] || $this -> display_params['delete_actions'])
			$fields[] = 'actions'; //Actions at the last column
		
		$this -> display_params['table_fields'] = $fields;
		
		return $fields;
	}
	
	public function displaySortableTable()
	{
		if(!isset($this -> display_params['table_fields']) || !count($this -> display_params['table_fields']))
			$this -> display_params['table_fields'] = $this -> defineTableFields();
		
		if(!$this -> sql_for_table)
			$this -> createSqlForTable();
		
		$html = "<table class=\"model-table\">\n<tr>\n";

		foreach($this -> display_params['table_fields'] as $name)
		{
			$css_class = "";
				
			if($name == "checkbox")
			{
				$caption = "<input type=\"checkbox\" />";
				$css_class = " class=\"check-all\"";
			}
			else if($name == "actions")
			{
				$caption = I18n :: locale('operations');
				$css_class = " class=\"actions\"";
			}
			else if($name == "id")
			{
				$caption = "Id";
				$css_class = " class=\"middle\"";
			}
			else
			{
				$caption = $this -> elements[$name] -> getProperty('caption');
				$css_class = " class=\"middle\"";
			}

			$type = isset($this -> elements[$name]) ? $this -> elements[$name] -> getType() : false;
			$skip = ($type == "parent" || $type == "group");
			
			if($name != "checkbox" && $name != "actions" && ($name == "id" || !$skip))
			{
				if($type == "many_to_one")
				{
					if($this -> elements[$name] -> getProperty('allow_sorting'))
						$caption = $this -> sorter -> createAdminLink($caption, $name);
				}
				else
					$caption = $this -> sorter -> createAdminLink($caption, $name);
			}
			else if(isset($this -> elements[$name]) && $type == "parent")
				$caption = I18n :: locale('child-records');				
			
			$html .= "<th".$css_class.">".$caption."</th>\n";
		}
				
		$html .= "</tr>\n";
		
		$foreign_keys = [];
		
		//Gets all possible values of all fields with foreign keys
		foreach($this -> elements as $name => $object)
			if($object -> getProperty('foreign_key') && !$object -> getProperty('long_list'))
				$foreign_keys[$name] = $object -> getDataOfForeignKey();
		
		if(!$this -> total)
		{
			$html .= "<tr class=\"no-hover\"><td colspan=\"30\">".$this -> i18n -> locale('empty-list');
			$html .= "</td>\n</tr>\n</table>\n";

			return $html;
		}
		
		$result = $this -> db -> query($this -> sql_for_table);
		
		while($row = $this -> db -> fetch($result, "ASSOC"))
		{
			$row_initial = $row;
			$name_for_actions = $this -> tryToDefineName($row);
			$html .= "<tr>\n";
			
			//If we can do actions with elements we add a checkbox
			if($this -> display_params['mass_actions'])
				if($this -> display_params['update_actions'] || $this -> display_params['delete_actions'])
					$html .= "<td><input type=\"checkbox\" name=\"item_".$row['id']."\" /></td>\n";
			
			foreach($this -> display_params['table_fields'] as $name)
				if($name != "checkbox" && $name != "actions") //If it's field from db
				{
					$type = $name != 'id' ? $this -> elements[$name] -> getType() : 'id';
					
					if($name == 'name' && $this -> parent_field)
						if($model_redirect = $this -> checkModelRedirect($row)) //If redirect to other model
							$row[$name] = "<a class=\"to-children\" href=\"?model=".$model_redirect."\">".$row[$name]."</a>\n";
						else if($this -> elements[$this -> parent_field] -> checkAllowedDepth($row['id'], get_class($this)))
						{
							//Adds link for the parent type name if depth allowes it
							$clear_name = $row[$name];
							$row[$name] = "<a href=\"?model=".$this -> getModelClass()."&".$this -> parent_field."=";
							$row[$name] .= $row['id']."\">".$clear_name."</a>\n";
						}
					
					if($name == "name" && $this -> getModelClass() == "log" && $this -> registry -> checkModel($row["module"]))
					{
						$model_object = $row["module"];
						$model_object = new $model_object();
						$simple_model = (get_parent_class($model_object) == "ModelSimple");
						
						if(!$simple_model && $this -> countRecords(array("table->" => $row["module"], "id" => $row["row_id"])))
							$row[$name] = "<a href=\"update.php?model=".$row["module"]."&id=".$row["row_id"]."\">".$row[$name]."</a>\n";
						else if($simple_model)
						    $row[$name] = "<a href=\"index-simple.php?model=".$row["module"]."\">".$row[$name]."</a>\n";
					}
				
					if(isset($foreign_keys[$name])) //If we need to show the value of foreign key
					{
						if(isset($foreign_keys[$name][$row[$name]]))
						{
							if($this -> display_params['foreign_keys_admin_links']) //Link to foreign key model record
							{
								$href = "update.php?model=".strtolower($this -> elements[$name] -> getProperty('foreign_key'));
					   			$row[$name] = "<a href=\"".$href."&id=".$row[$name]."\">".$foreign_keys[$name][$row[$name]]."</a>\n";
							}
					   		else
					   			$row[$name] = $foreign_keys[$name][$row[$name]];
						}
						else
						{
							$empty_caption = $this -> elements[$name] -> getProperty('empty_value');
							$row[$name] = (is_string($empty_caption) && !is_numeric($empty_caption)) ? $empty_caption : "-";
						}
					}
					else if($type == 'enum')
					{
						$save_value = $row[$name];
						
						//Gets all possible values of enum element and determines the current one
						if($this -> elements[$name] -> getProperty('long_list'))
						{
							$row[$name] = $this -> elements[$name] -> getValueName($row[$name]);
							
							if($this -> elements[$name] -> getProperty('foreign_key') && $row[$name] && 
							   $this -> elements[$name] -> getProperty('is_parent') && 
							   $this -> elements[$name] -> getProperty('show_parent'))
							   $row[$name] .= " (".$this -> elements[$name] -> getNameOfParentOfForeignKey($save_value).")";
						}
						else
						{
							$values = $this -> elements[$name] -> getProperty('values_list');
							$empty_text = $this -> elements[$name] -> getProperty('empty_value');
							$empty_text = is_bool($empty_text) ? "-" : $empty_text;
							$row[$name] = isset($values[$row[$name]]) ? $values[$row[$name]] : $empty_text;
						}
						
						if($row[$name] && $this -> elements[$name] -> getProperty('foreign_key') && 
						   $this -> display_params['foreign_keys_admin_links'])
						   {
								$href = "update.php?model=".strtolower($this -> elements[$name] -> getProperty('foreign_key'));
					   			$row[$name] = "<a href=\"".$href."&id=".$save_value."\">".$row[$name]."</a>\n";
						   }
					}
					else if($type == 'int')
						$row[$name] = I18n :: formatIntNumber(intval($row[$name]));
					else if($type == 'float')
					{
						$float_number = explode(".", $row[$name]);
						$row[$name] = I18n :: formatIntNumber($float_number[0]);
						
						if(isset($float_number[1]) && $float_number[1])
							$row[$name] .= I18n :: getDecimalMark().$float_number[1];
					}
					else if($type == 'many_to_many')
						$row[$name] = $this -> elements[$name] -> displayAdminTableLink($row['id']);
					else if($type == 'group' && $row[$name])
						$row[$name] = $this -> elements[$name] -> countGroupRecords($row[$name]);
					else if($type == 'many_to_one')
					{
						$this -> elements[$name] -> setRelatedId($row['id']);
						
						if(isset($special) && $special == '[count]' || $this -> elements[$name] -> getProperty('display_count'))						
							$row[$name] = $this -> elements[$name] -> countChildElements(get_class($this));
						else
							$row[$name] = $this -> elements[$name] -> displayHtml();
					}
					else if($type == 'order')
					{
						if($this -> checkDisplayParam('update_actions'))
							$row[$name] = $this -> elements[$name] -> displayHtmlForTable($row[$name], $name."_".$row['id']);							
					}
					else if($type == 'image')
						$row[$name] = $this -> displayAdminImage(Service :: addFileRoot($row[$name]));
					else if($type == 'multi_images')
					{
						$images_list = MultiImagesModelElement :: unpackValue($row[$name]);
						$row[$name] = $this -> displayAdminImage(Service :: addFileRoot($images_list[0]['image'] ?? ''));
					}
					else if($type == 'file')
						$row[$name] = basename($row[$name]);
					else if($type == 'date' || $type == 'date_time')
						$row[$name] = I18n :: dateFromSQL((string) $row[$name], "no-seconds");
					else if($type == 'parent')
							$row[$name] = $this -> elements[$name] -> countChildElements(get_class($this), $name, $row['id']);
					else if($type == 'bool')
					{
						$css_class = $row[$name] ? "bool-true" : "bool-false";
						
						if($this -> elements[$name] -> getProperty("quick_change") && $this -> checkDisplayParam('update_actions'))
						{
							$bool_title = $row[$name] ? "switch-off" : "switch-on";
							$row[$name] = "<span id=\"".$name."-".$row['id']."-".$this -> table."\" class=\"bool-field ";
							$row[$name] .= $css_class."\" title=\"".I18n :: locale($bool_title)."\"><span class=\"slider\"></span></span>";
						}
						else
							$row[$name] = "<span class=\"bool-field off ".$css_class."\"><span class=\"slider\"></span></span>";
					}
					else if($type == "text" && $this -> elements[$name] -> getProperty("show_in_admin"))
					{
						if($row[$name])
							$row[$name] = Service :: cutText($row[$name], $this -> elements[$name] -> getProperty("show_in_admin"), " ...");
						else
							$row[$name] = '';
					}
					
					if(trim(strval($row[$name])) == '' && $type != 'bool')
						$row[$name] = "-";
						
					if(method_exists($this, "processAdminModelTableFields"))
					{
						$processed_value = $this -> processAdminModelTableFields($name, $row_initial);
						$row[$name] = !is_null($processed_value) ? $processed_value : $row[$name];
					}						
						
					$css_quick_change = "";
					
					if($this -> checkIfFieldEditable($name))
						if(in_array($type, array("char", "url", "redirect", "email", "phone")))
							$css_quick_change = ' id="quick-edit-'.$name.'-'.$row['id'].'" class="edit-string"';
						else if($type == "int" || $type == "float")
							$css_quick_change = ' id="quick-edit-'.$name.'-'.$row['id'].'" class="edit-number"';
					
					$html .= "<td".$css_quick_change.">".$row[$name]."</td>\n"; //Value goes to the table
					
					unset($special);
				}
			
			$arguments = func_get_args();
			$garbage_model = (isset($arguments[0]) && $arguments[0] == 'garbage');
			
			//Actions links (if allowed)
			if($this -> display_params['update_actions'] || $this -> display_params['delete_actions'] || $garbage_model)
			{
				$html .= "<td>";

				if($garbage_model)
					$html .= $this -> makeAdminLink($row['id'], 'restore', $name_for_actions);
				else if($this -> display_params['update_actions']) //Link to update the element
					$html .= $this -> makeAdminLink($row['id'], 'update', $name_for_actions);
					
				if($this -> display_params['delete_actions'] || $garbage_model) //Link to delete
					$html .= $this -> makeAdminLink($row['id'], 'delete', $name_for_actions);
					
				$html .= "</td>\n";
			}
			
			$html .= "</tr>\n";
		}
		
		$html .= "</table>\n";
		
		return $html;
	}
	
	public function checkModelRedirect($row)
	{
		if(isset($this -> display_params["redirects_to_models"]) && count($this -> display_params["redirects_to_models"]))
			foreach($row as $field => $value)
				if(isset($this -> display_params["redirects_to_models"][$field][$value]) && 
					$this -> registry -> checkModel($this -> display_params["redirects_to_models"][$field][$value]))
					return strtolower($this -> display_params["redirects_to_models"][$field][$value]);
					
		return false;
	}
	
	public function displayAdminImage($path)
	{
		if(!$path || !is_file($path))
			return "-";
		
		$imager = new Imager();
		$src = $imager -> compress($path, 'admin', 150, 150);
		
		return "<img class=\"in-table\" src=\"".$src."\" alt=\"".basename($path)."\" />";
	}
	
	public function uploadMultiImages($name)
	{
		if(!isset($this -> elements[$name]) || $this -> elements[$name] -> getType() != 'multi_images')
			return [];

		$images_data = $errors_text = $wrong_files = $uploaded_images = [];
		$old_data = [];
		
		if(isset($_POST[$name]) && trim($_POST[$name]) != '')
			$old_data = json_decode(base64_decode(trim($_POST[$name])), true);	
		
		if(isset($_FILES["multi-images-".$name]))
			foreach($_FILES["multi-images-".$name] as $section => $data)
				foreach($data as $key => $value)
					$images_data[$key][$section] = $value;
		
		foreach($images_data as $image)
		{
			$result = $this -> elements[$name] -> uploadImage($image, $old_data);
			$error = $this -> elements[$name] -> getError();
			
			if(!$error)
			{
				$old_data = $result[1];

				$uploaded_images[] = [
					'small' => (new Imager()) -> compress($result[0], 'admin', 150, 150),
					'original' => $result[0]
				];
			}
			else
			{
				$arguments = ['field' => $this -> elements[$name] -> getCaption()];
				
				if($error == 'wrong-images-type')
					$arguments['formats'] = implode(', ', $this -> elements[$name] -> getOverriddenProperty("allowed_extensions"));
				else if($error == 'too-heavy-image')
					$arguments['weight'] = I18n :: convertFileSize($this -> elements[$name] -> getOverriddenProperty("max_size"));
				else if($error == 'too-large-image')
				{
					$arguments['size'] = $this -> elements[$name] -> getOverriddenProperty("max_width")." x ";
					$arguments['size'] .= $this -> elements[$name] -> getOverriddenProperty("max_height");
				}
						
				$error = $this -> i18n -> locale($error, $arguments);
				$error = preg_replace("/'([^']+)'/", "&laquo;$1&raquo;", $error);
				
				if(!in_array($error, $errors_text))
					$errors_text[] = $error;
				
				$wrong_files[] = $image['name'];
			}
			
			$this -> elements[$name] -> setError('');
		}
		
		return [
			"input_value" => base64_encode(json_encode($old_data)),
			"uploaded_images" => $uploaded_images,
			"wrong_files" => implode(", ", $wrong_files),
			"error" => implode("<br>", $errors_text)
		];
	}
		
	public function makeAdminLink($id, $action_type, $name)
	{
		$has_right = ($action_type == 'delete') ? 'delete' : 'update';
		$has_right = $this -> user -> checkModelRights($this -> getModelClass(), $has_right);
		$name = str_replace(["&quot;", "&#039;", "&lt;script&gt;", "<script>"], "", $name); //Fix js message bug
		$token = $this -> generateSingleActionToken($id);
		
		if($action_type == 'delete' || $action_type == 'restore')
		{
			$html = "<a title=\"".I18n :: locale($action_type)."\" id=\"";
			
			if($has_right)
			{
			    $html .= "model-".$action_type."-".$id."-".$token."\" data=\"".$name;
			}
			else
			    $html .= "model-".$action_type."-deny";
			
			$html .= "\" class=\"single-action action-".$action_type."\"></a>\n";
		}
		else if($action_type == 'update')
		{
			$html = "<a title=\"".I18n :: locale("edit")."\" href=\"";
			$html .= $this -> registry -> getSetting('AdminPanelPath')."model/";
			$html .= "update.php?".$this -> getAllUrlParams(array('parent','model','pager','filter'));
			$html .= "&id=".$id."\" class=\"single-action action-".$action_type."\"></a>\n";
		}
		
		return $html;
	}
	
	public function generateSingleActionToken($id)
	{
	    $token = $this -> registry -> getSetting("AdminPanelCSRFToken").$this -> getName();
	    $token = Service :: createHash($_SERVER["HTTP_USER_AGENT"].$token.$id.$_SERVER["REMOTE_ADDR"]);
	    
	    return $token;
	}
	
	public function displayParentsPath($start_id)
	{
		$html = "<span>".I18n :: locale('root-catalog')."</span>";
		
		if($start_id != -1)
		{
			$html = "<a href=\"?model=".$this -> getModelClass()."&".$this -> parent_field."=-1\">";
			$html .= I18n :: locale('root-catalog')."</a> ";
			
			$data = $this -> elements[$this -> parent_field] -> displayPath($start_id);
			
			$length = count($data);
			$i = 1;
			
			if($length)
				foreach($data as $id => $name)
					if($i ++ < $length)
						$html .= "/ <a href=\"?model=".$this -> getModelClass()."&".$this -> parent_field."=".$id."\">".$name."</a> ";
					else
						$html .= " / <span>".$name."</span>";			
		}
		
		return $html;
	}
	
	public function updateOrderField($field, $data)
	{
		$this -> db -> beginTransaction();
		
		foreach($data as $id => $order)
			if($id > 0 && $order > 0)
				$this -> db -> query("UPDATE `".$this -> table."` 
									  SET `".$field."`=".$this -> db -> secure($order)." 
									  WHERE `id`='".$id."'");
				
		Cache :: cleanByModel($this -> getModelClass());
		
		$this -> db -> commitTransaction();		
	}
	
	//
	//All next methods are related to app front
	//But can be used also in admin panel addons
	//
	
	public function getFieldProperty($field, $property)
	{
		if(isset($this -> elements[$field]))
			if($this -> elements[$field] -> hasProperty($property))
				return $this -> elements[$field] -> getProperty($property);
	}
	
	public function setFieldProperty($field, $property, $value)
	{		
		if(isset($this -> elements[$field]))
			if($this -> elements[$field] -> hasProperty($property))
				$this -> elements[$field] -> setProperty($property, $value);
			
		return $this;
	}	
	
	public function getEmptyRecord()
	{
		$empty_content = [];
		
		foreach($this -> elements as $name => $object)
			if($object -> getType() != 'many_to_one')
				$empty_content[$name] = "";
		
		return new Record($empty_content, clone $this);
	}

	public function findRecordOrGetEmpty(array $params)
	{
		$record = $this -> findRecord($params);

		return is_object($record) ? $record : $this -> getEmptyRecord();
	}
			
	public function runPaginator(int $total, int $limit, mixed $current = null)
	{
		$this -> paginator = new Paginator($total, $limit);

		if(is_numeric($current))
			$this -> paginator -> definePage(intval($current));
	}
	
	public function runSorter(array $fields, string $default_field = '', string $default_order = '')
	{
		$fields_data = [];
		
		foreach($fields as $field)
			if($object = $this -> getElement($field))
				$fields_data[$field] = $object -> getType();
			else if($field == "id")
				$fields_data[$field] = "int";
		
		$this -> sorter = new Sorter($fields_data);
		$this -> sorter -> getDataFromGet();
		
		if(!$this -> sorter -> hasParams())
			if($default_field !== '' && $default_order !== '')
				$this -> sorter -> setParams($default_field, $default_order);
	}
	
	public function runFilter($fields)
	{
		$forbidden_types = ['password','many_to_one','group'];
		$filters_data = [];
		
		foreach($fields as $field) //Transforms interval paramn
		{
			if(isset($_GET[$field."-from"]))
			{
				$_GET[$field."-gte"] = $_GET[$field."-from"];
				unset($_GET[$field."-from"]);
			}
			
			if(isset($_GET[$field."-to"]))
			{
				$_GET[$field."-lte"] = $_GET[$field."-to"];
				unset($_GET[$field."-to"]);							
			}
		}
		
		foreach($fields as $field)
			if($object = $this -> getElement($field))
				if(!in_array($object -> getType(), $forbidden_types))
				{
					$filters_data[$field]['type'] = $type = $object -> getType();
					$filters_data[$field]['caption'] = $object -> getCaption();
					
					if($type == "enum" || $type == "parent")
					{
						$filters_data[$field]['long_list'] = $object -> getProperty('long_list');
						
						if($filters_data[$field]['long_list'])
						{
							if($object -> getProperty('foreign_key'))
								$filters_data[$field]['foreign_key'] = strtolower($object -> getProperty('foreign_key'));
							else if($type == "parent")
								$filters_data[$field]['table'] = $this -> getTable();
						}							
						else
							if($type == "enum")
								$object -> defineValuesList(get_class($this));
							else
								$object -> getAvailbleParents($this -> table, "for-frontend-filter");
						
						$filters_data[$field]['values_list'] = $object -> getProperty('values_list');
						
						if(!$object -> getProperty('empty_value'))
							$object -> setProperty('empty_value', true);
							
						$empty_value = $object -> getProperty('empty_value');
						$empty_text = is_bool($empty_value) ? I18n :: locale("not-defined") : $empty_value;
						$filters_data[$field]['empty_value'] = $empty_text;
					}
					else if($type == "many_to_many")
					{
						$filters_data[$field]['long_list'] = $object -> getProperty('long_list');						
						$filters_data[$field]['values_list'] = $object -> getDataOfManyToMany();						
					}
					
					if(isset($_GET[$field])) //Takes values of filters from GET
					{
						$value = Filter :: checkFieldValue($type, trim($_GET[$field]), $filters_data[$field]);
						
						if($value != '')
							$filters_data[$field]['value'] = $value;
					}
					else 
						foreach($_GET as $key => $val)
							if(preg_match("/^".$field."-(eq|neq|gt|lt|gte|lte)$/", $key))
							{
								$condition = preg_replace("/^".$field."-(eq|neq|gt|lt|gte|lte)$/", "$1", $key);
								$value = Filter :: checkFieldValue($type, trim($val), $filters_data[$field]);
								
								if($value != '')
									$filters_data[$field]['conditions'][$condition] = $value;
							}
				}

		$this -> filter = new Filter($filters_data, 'frontend', clone $this);
				
		return $this;
	}
	
	public function createRecord($params)
	{
		if(!is_array($params) || !count($params))
			return null;
		
		$fields = $values = [];

		foreach($params as $field => $value)
			if($object = $this -> getElement($field))
				if($object -> getType() != 'many_to_many')
				{	
					$fields[] = "`".$field."`";
					$value = Service :: cleanHtmlSpecialChars($value);
					
					$version = $this -> registry -> getInitialVersion();
					
					if($object -> getType() == "password" && $value != "" && ($version >= 1.1 && $version < 2.2))
						$values[] = "'".md5($value)."'";
					else
						$values[] = $this -> db -> secure($value);
				}
				
		if(method_exists(Database :: $pdo, "inTransaction"))
			$in_transaction = Database :: $pdo -> inTransaction();
		else
			$in_transaction = true;
		
		if(!$in_transaction)
			$this -> db -> beginTransaction();

		$this -> db -> query("INSERT INTO `".$this -> table."`(".implode(",", $fields).") 
				  			  VALUES(".implode(",", $values).")");
		
		$new_id = $this -> db -> lastId();
		//TODO add option to save the version from record for all CRUD
		
		foreach($params as $field => $value)
			if($object = $this -> getElement($field))
				if($object -> getType() == 'many_to_many')
				{
					$object -> setRelatedId($new_id) -> setValue($value);
					$this -> setId($new_id);
				}
		
		$this -> updateManyToManyTables();
		
		if(!$in_transaction)
			$this -> db -> commitTransaction();
		
		return $new_id;
	}	
	
	public function updateRecord($id, $fields)
	{
		if(!is_array($fields) || !count($fields))
			return;
			
		$checked_fields = $m2m_fields = [];
		$content = $this -> db -> getRow("SELECT * FROM `".$this -> table."` WHERE `id`='".$id."'");

		foreach($fields as $field => $value)
			if($object = $this -> getElement($field))
				if($object -> getType() === 'many_to_many')
				{
					$object -> setRelatedId($id) -> setValue($value);
					$this -> setId($id);

					$m2m_fields[] = $field;
				}				
				else
				{
					if($object -> getType() === 'password')
					{
						if($value == '' || ($value != '' && isset($content[$field]) && $content[$field] == $value))
							continue;
						else
						{
							$version = $this -> registry -> getInitialVersion();
							$value = ($version >= 1.1 && $version < 2.2) ? "'".md5($value)."'" : "'".$value."'";
						}
					}	
					else if($object -> getType() != 'text')
						$value = $this -> db -> secure($value);
					else
						$value = "'".$value."'";
	
					$checked_fields[] = "`".$field."`=".Service :: cleanHtmlSpecialChars($value);					
				}
				
		if(!count($checked_fields))
			return;
		
		if(method_exists(Database :: $pdo, 'inTransaction'))
			$in_transaction = Database :: $pdo -> inTransaction();
		else
			$in_transaction = true;
		
		if(!$in_transaction)
			$this -> db -> beginTransaction();

		$this -> db -> query("UPDATE `".$this -> table."` SET ".implode(",", $checked_fields)." WHERE `id`='".$id."'");
		$this -> updateManyToManyTables($m2m_fields);
		
		if(!$in_transaction)
			$this -> db -> commitTransaction();
		
		return $this;
	}
		
	public function deleteRecord($id)
	{
		if(method_exists(Database :: $pdo, "inTransaction"))
			$in_transaction = Database :: $pdo -> inTransaction();
		else
			$in_transaction = true;
		
		if(!$in_transaction)
			$this -> db -> beginTransaction();
		
		$content = $this -> db -> getRow("SELECT * FROM `".$this -> table."` WHERE `id`='".$id."'");
		$this -> db -> query("DELETE FROM `".$this -> table."` WHERE `id`='".$id."'");
		
		foreach($this -> elements as $object)
			if($object -> getType() == "many_to_many")
				$this -> db -> query("DELETE FROM `".$object -> getProperty("linking_table")."` 
						  		      WHERE `".$object -> getOppositeId()."`='".$id."'");
		
		$versions = new Versions($this -> table, $id);
		$versions -> clean() -> cleanFiles($content, $this -> defineFilesTypesFields());

		if(!$in_transaction)
			$this -> db -> commitTransaction();
			
		$this -> drop();
		
		return $this;
	}
	
	public function getEnumTitle(string $field, mixed $key)
	{
		if(!$object = $this -> getElement($field))
			return;
			
		if(!isset($this -> enum_values[$field][$key]))
		{
			if($object -> getProperty("long_list")) //If long list we get only one cell
				//Some inside cache to keep the extracted values
				$this -> enum_values[$field][$key] = $object -> getValueName($key);
			else 
			{
				$object -> defineValuesList(); //We take full values list
					
				//Some inside cache to keep the extracted values
				$this -> enum_values[$field] = $object -> getValuesList();									
			}
				
			if(isset($this -> enum_values[$field][$key]))
				return $this -> enum_values[$field][$key];
		}
		else //Value already in cache
			return $this -> enum_values[$field][$key];
	}
	
	public function getEnumValues($field)
	{
		if(!$object = $this -> getElement($field))
			return [];
		
		return $object -> getValuesList();
	}
	
	public function displayEnumOptions($field)
	{
		$html = "";
		
		if(!$object = $this -> getElement($field))
			return $html;
		
		$data = $object -> getValuesList();
		
		$arguments = func_get_args();
		$selected = isset($arguments[1]) ? $arguments[1] : false;
		
		if($empty_value = $object -> getProperty("empty_value"))
		{
			$empty_text = is_bool($empty_value) ? I18n :: locale("not-defined") : $empty_value;
			$html .= "<option value=\"\">".$empty_text."</option>\n";
		}
		
		foreach($data as $key => $value)
		{
			$html .= "<option value=\"".$key."\"";
			
			if($selected == $key)
				$html .= " selected=\"selected\"";
			
			$html .= ">".$value."</option>\n";
		}
		
		return $html;
	}
	
	public function getParents(int $id)
	{
		if($this -> parent_field && isset($this -> elements[$this -> parent_field]))
			return $this -> elements[$this -> parent_field] -> displayPath($id);
	}
	
	public function getChildren(int $id)
	{
		if($this -> parent_field && isset($this -> elements[$this -> parent_field]))
			$records = $this -> elements[$this -> parent_field] -> defineChildElements($this -> table, $id);
			
		if(!count($records))
			return [];
			
		$name_field = $this -> elements[$this -> parent_field] -> getProperty("name_field");
		
		$rows = $this -> db -> getAll("SELECT `id`,`".$name_field."`
						   	           FROM `".$this -> table."`
							           WHERE `id` IN(".implode(",", $records).")");
				
		$records = array_flip($records);
		
		foreach($rows as $row)
			if(array_key_exists($row['id'], $records))
				$records[$row['id']] = $row[$name_field];
		
		return $records;
	}
	
	public function displayBreadcrumbs(int $id, string $url_first, $url_field = '')
	{
		$parents = [];
		$linked_tree = false;
		
		if($object = $this -> findElementByProperty("is_parent", true)) //If its related model
		{
			$related_model = $object -> getProperty('foreign_key'); //Search for field in other model
			$related_object = new $related_model();
			$related_object_element = $related_object -> findElementByProperty('parent_for', get_class($this));
			
			if($related_object_element) //If models are linked we get the parents
			{
				$parent_row = $this -> db -> getRow("SELECT * 
													 FROM `".$this -> table."` 
													 WHERE `id`='".$id."'");
				
				$parents = $related_object -> getParents($parent_row[$object -> getName()]); //Takes parent from related model
				$linked_tree = true;
			}			
		}
		else
			$parents = $this -> getParents($id); //Single model with parent field
		
		$length = count($parents);
		$url = $this -> root_path;
		$html = $url_field = '';
		
		if($this -> getModelClass() == "pages" && $url_field === '')
			$url_field = "url";

		if($length) //If we have any parents we start composing html path
		{
			$table_for_urls = $linked_tree ? $related_object -> getTable() : $this -> table;
			
			if($url_field)
				$url_data = $this -> db -> getAll("SELECT `id`,`".$url_field."` 
												   FROM `".$table_for_urls."` 
												   WHERE `id` IN(".implode(",", array_keys($parents)).")");				
			else
				$url_data = false;
			
			foreach($parents as $id => $name)
			{
				$length --;
				$href = $url;
				
				if($url_data && array_key_exists($id, $url_data) && $url_data[$id][$url_field]) //If we have url field
				{
					if($this -> getModelClass() == "pages") //Pages model has no first url part
						$href .= $url_data[$id][$url_field]."/";
					else
						$href .= $url_first."/".$url_data[$id][$url_field]."/";
				} 
				else 
					$href .= $url_first."/".$id."/"; //Simple url with just id
				
				if(($length && !$linked_tree) || $linked_tree)
					$html .= "<a href=\"".$href."\">".$name."</a>\n"; //Links path
				else
					$html .= "<span>".$name."</span>\n"; //Last span with no link
			}
			
			if($linked_tree && isset($parent_row, $parent_row[$this -> name_field]))
				$html .= "<span>".$parent_row[$this -> name_field]."</span>\n";
		}
			
		return $html;
	}
	
	public function findActiveRecordByDefault(Router $router)
	{
		$url_parts = $router -> getUrlParts();
		$record = false;
		
		if(count($url_parts) == 2)
			if(is_numeric($url_parts[1]))
				$record = $this -> findRecord(array("id" => $url_parts[1], "active" => 1));
			else
				$record = $this -> findRecord(array("url" => $url_parts[1], "active" => 1));
					
		return $record;
	}
		
	public function __call($method, $arguments)
	{		
		if($method == "countTotal")
		{
			if(isset($arguments[0]) && $arguments[0])
				return $this -> countRecords($arguments[0]);
			else
				return $this -> countRecords();
		}
		else if($method == "runPager")
			return $this -> runPaginator($arguments[0], $arguments[1], $arguments[2] ?? null);
		else if($method == "findContent")
			return $this -> findRecord($arguments[0]);
		else if($method == "findContentById")
			return $this -> findRecordById($arguments[0]);
		else
		{
			$trace = debug_backtrace();
			$message = "Call to undefiend method '".$method."' of model '".get_class($this)."'";
			$message .= ', in line '.$trace[0]['line'].' of file ~'.Service :: removeDocumentRoot($trace[0]['file']);

			Debug :: displayError($message, $trace[0]['file'], $trace[0]['line']);
		}
	}
}
