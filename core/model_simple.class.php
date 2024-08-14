<?php
/**
 * Simple type of model, managing key / value storage.
 * Usually this type of model keeps settings and other data 
 * which does not need to be represented as many similar rows (records).
 */
class ModelSimple extends Model
{
	/**
	 * Lazy loading, true if we got the data from DB already.
	 * @var bool
	 */
	protected $data_loaded = false;
	
	/**
	 * Runs the model, checking its fields for errors.
	 */
	public function __construct()
	{
		//Some types can't be used in simple models		
		$forbidden_types = ['parent','order','many_to_one','many_to_many','group'];
		
		if(isset($this -> model_elements) && count($this -> model_elements))
			foreach($this -> model_elements as $field)
				if(in_array($field[1], $forbidden_types))
					Debug :: displayError("Data type '".$field[1]."' (field '".$field[2]."' in model '".get_class($this)."') is not allowed in simple models.");
				else if($field[1] == 'enum' && isset($field[3]) && array_key_exists('foreign_key', $field[3]))
					Debug :: displayError("Data type 'enum' (field '".$field[2]."' in model '".get_class($this)."') can not have 'foreign key' parameter in simple models.");
					
		parent :: __construct();
	}
	
	/**
	 * Getter to retreive fields values.
	 */
	public function __get($key)
	{
		return $this -> getValue($key);
	}
	
	/**
	 * Setter to set fieldes values.
	 */
	public function __set($key, $value)
	{
		return $this -> setValue($key, $value);	
	}	
	
	/**
	 * Loads values from database into model object.
	 * @return self
	 */
	public function getDataFromDb()
	{
		$this -> data = [];
		
		$result = $this -> db -> query("SELECT * FROM `".$this -> table."`");		
		
		while($row = $this -> db -> fetch($result, "ASSOC"))
			$this -> data[$row['key']] = $row['value'];
			
		foreach($this -> elements as $name => $object)
			if(!isset($this -> data[$name]))
			{
				$this -> db -> query("INSERT INTO `".$this -> table."`(`key`,`value`) VALUES('".$name."','')");
				$this -> data[$name] = null;
			}
			
		$this -> data_loaded = true;
			
		return $this;
	}
	
	/**
	 * Sets values from database into model object (for admin panel).
	 * @return self
	 */
	public function passDataFromDb()
	{
		$this -> read($this -> data);
				
		return $this;
	}
	
	/**
	 * Lazy load of models fields data from db.
	 * @return array
	 */
	public function loadData()
	{
		if(!$this -> data_loaded)
			$this -> getDataFromDb();
				
		return $this -> data;
	}
	
	/**
	 * Creates new Record object with data of current model.
	 * @return object Record
	 */
	public function loadIntoRecord()
	{
		if(!$this -> data_loaded)
			$this -> getDataFromDb();

		if(is_array($this -> data) && count($this -> data))
			$this -> data['id'] = -1;
		
		return new Record($this -> data, $this);
	}
	
	/**
	 * Updates fields values in database.
	 * @return self
	 */
 	public function update()
	{
		if(!$this -> data_loaded)
			$this -> getDataFromDb(); //Load db data of this model
			
		$arguments = func_get_args();
		$backend = ((isset($arguments[0]) && $arguments[0] == "backend") || $this -> getModelClass() == "seo");
		
		if(!$backend) //If we update model from frontend
		{
			$source = $this -> data;
			
			//Adds roots for files and images to check before update
			foreach($this -> elements as $name => $object)
				if($object -> getType() == "file" || $object -> getType() == "image")
					$source[$name] = Service :: addFileRoot((string) $source[$name]);
			
			$this -> getDataFromArray($source);
		}
			
		$version_dump = [];
		
		//Does not run transaction if it's already started 
		$in_transaction = (method_exists(Database :: $pdo, "inTransaction")) ? Database :: $pdo -> inTransaction() : true;
		
		if(!$in_transaction)
			$this -> db -> beginTransaction();
		
		foreach($this -> elements as $name => $object)
			if(!$backend || ($backend && $this -> checkIfFieldEditable($name)))
			{
				$type = $object -> getType();
				
				if($type != 'password' && $type != 'multi_images')
					$this -> prepareElementValue($object);
				else if($type == 'multi_images')
					$object -> copyImages(get_class($this), $this -> data[$name]);
				else
				{
					$db_value = $this -> db -> getCell("SELECT `value` 
														FROM `".$this -> table."` 
														WHERE `key`='".$name."'");
										
					//If password was not filled on update we take old value					
					if(!$object -> getValue())
						$object -> setValue($this -> data[$name]);
					else
						if($object -> getValue() == $db_value)
							$object -> setValue($this -> data[$name]);
						else
							$this -> prepareElementValue($object);						
				}
				
				$param = ($type == 'date' || $type == 'date_time') ? "sql" : "";
				$value = Service :: cleanHtmlSpecialChars($object -> getValue($param));
				
				if($type == 'image' || $type == 'file')
					$value = Service :: removeFileRoot($value);
						
				$this -> db -> query("UPDATE `".$this -> table."` 
									  SET `value`=".$this -> db -> secure($value)." 
									  WHERE `key`='".$name."'");
				
				$version_dump[$name] = $this -> data[$name] = $value;
			}
			else
				$version_dump[$name] = $this -> data[$name];
				
		if($backend)
		{
			if(method_exists($this, "afterUpdate"))
				$this -> afterUpdate($version_dump);

			$this -> versions = new Versions($this -> table, -1); //Writes new version
			$versions_limit = $this -> getVersionsLimit();
			$this -> versions -> setLimit($versions_limit);
			
			//Writes to log if new version was saved or versions are disallowed
			if(!$versions_limit || $this -> versions -> save($version_dump, $this -> user))
				Log :: write($this -> getModelClass(), $this -> id, $this -> getName(), $this -> user -> getId(), "update");
				
			Cache :: cleanByModel($this -> getModelClass());
		}
			
		if(!$in_transaction)
			$this -> db -> commitTransaction();
			
		return $this;
	}
	
	/**
	 * Returns the value of one field.
	 * @return mixed
	 */
	public function getValue(string $field)
	{
		if(!$this -> data_loaded)
			$this -> getDataFromDb();

		if(isset($this -> data[$field]))
			return $this -> data[$field];
	}

	/**
	 * Sets the value of one field.
	 * @return self
	 */
	public function setValue(string $field, mixed $value)
	{
		if(!$this -> data_loaded)
			$this -> getDataFromDb();
			
		if(isset($this -> elements[$field]))
			$this -> data[$field] = trim($value);
			
		return $this;
	}
	
	/**
	 * Returns the value of enum field by key.
	 * @return mixed
	 */
	public function getEnumTitle(string $field, mixed $key)
	{
		if(!$this -> data_loaded)
			$this -> getDataFromDb();
		
		if(isset($this -> elements[$field]) && $this -> elements[$field] -> getType() == "enum")
			if($key || $key === 0)
			{
				$values = $this -> elements[$field] -> getValuesList();
				
				if(array_key_exists($key, $values))
					return $values[$key];
			}
			else if(isset($this -> data[$field]))
				return $this -> elements[$field] -> getValueName($this -> data[$field]);
	}
	
	/**
	 * Returns title (caption) of selected value of enum fileld.
	 * @return string
	 */
	public function getSelectedEnumTitle(string $field)
	{
		return $this -> getEnumTitle($field, false);
	}
	
	/**
	 * Increases (or decreases) the value of integer field.
	 * @return self
	 */
	public function increaseCounter(string $field, int $value = 1, $type = 'increase')
	{
		if(!$this -> data_loaded)
			$this -> getDataFromDb();
		
		if($type === 'decrease')
			$value *= -1;
			
		if(isset($this -> elements[$field]) && $this -> elements[$field] -> getType() === "int")
		{
			$this -> data[$field] = (int) $this -> data[$field];
			$this -> data[$field] += $value;
			$this -> update();
		}
		
		return $this;
	}
	
	/**
	 * Decreases the value of integer field.
	 * @return self
	 */
	public function decreaseCounter(string $field, int $value = 1)
	{		
		return $this -> increaseCounter($field, $value, 'decrease');		
	}
	
	/**
	 * Returns the glued values of all email fields in model.
	 * @return string
	 */
	public function combineEmails(array $fields = [])
	{
		if(!$this -> data_loaded)
			$this -> getDataFromDb();
		
		$emails = [];
		
		foreach($this -> elements as $name => $object)
			if(!count($fields) || in_array($name, $fields))
				if($object -> getType() == "email" && isset($this -> data[$name]) && $this -> data[$name])
					$emails[] = $this -> data[$name];
				
		return implode(', ', $emails);
	}
	
	public function __call($method, $arguments)
	{
		if($method == "loadContent")
			return $this -> loadIntoRecord();		
		else if($method == "updateData")
			return $this -> update();
		else
			Debug :: displayError("Call to undefiend method '".$method."' of simple model '".get_class($this)."'.");			
	}
}
