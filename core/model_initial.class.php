<?php
/**
 * Initial class for models, contains the most basic objects like Registry and Database manager.
 * Also defines methods to process SQLcondtions and retreive records from the database.
 */
abstract class ModelInitial
{
	/**
	 * Settings manager.
	 * @var object Registry
	 */
	public $registry;
	
	/**
	 * Database manager.
	 * @var object Registry
	 */
	public $db;

	/**
	 * Current table name in database,
	 * @var string
	 */
	protected $table;
	
	/**
	 * Id of current row in database.
	 * @var int
	 */
	protected $id;

	/**
	 * Versions manager in admin panel.
	 * @var object Versions
	 */
	protected $versions;

	/**
	 * Checks if record with certain id exist in db table.
	 * @return bool 
	 */
	public function checkRecordById(int $id)
	{
		return (bool) $this -> db -> getCount($this -> table, "id='".intval($id)."'") > 0;
	}
	
	/**
	 * Looks for the record in database according to passed conditions.
	 * @return object|null Record object if found
	 */
	public function findRecord(array $params = []): ?Record
	{
		if(!count($params))
			return null;
			
		if(isset($params['table->']) && $params['table->'] !== $this -> table)
		{
			if(!Registry :: checkModel($params['table->']))
				Debug :: displayError("Unable to retreive record from table '".$params['table->']."', no such table, or such model.");

			$model_object = new (Registry :: getModelClassByTable($params['table->']));
		}
		else
			$model_object = $this;
		
		if($content = $this -> selectOne($params))
		{
			if($this -> registry -> getInitialVersion() < 1.11)
				$this -> id = $content['id'];
			
			if(Registry :: getInitialVersion() < 3.0)
			{
				foreach($model_object -> getElements() as $name => $object)
					if($object -> getType() == 'many_to_many')
					{
						$ids = $object -> setRelatedId($content['id']) -> getSelectedValues();
						$content[$name] = implode(',', $ids);
					}
			}
		}
		
		return $content ? new Record($content, $model_object) : null;
	}
	
	/**
	 * Looks for the record in database according to passed id.
	 * @return object|null Record object if found
	 */
	public function findRecordById(int $id): ?Record
	{
		return $this -> findRecord(['id' => $id]);
	}

	/**
	 * Looks for the record in database according to passed conditions.
	 * @param mixed $conditions can be id or array of conditions
	 * @return object|null Record object of found
	 */
	public function find(mixed $conditions): ?Record
	{
		if(is_int($conditions) || $conditions === (string) (int) $conditions)
			return $this -> findRecordById(intval($conditions));
		else if(is_array($conditions))
			return $this -> findRecord($conditions);

		return null;
	}
	
	/**
	 * Counts records in db matching passed conditions.
	 * @param array $conditions array of conditions, can be empty
	 * @return int number of suitable records
	 */
	public function countRecords(array $conditions = [])
	{
		$conditions["count->"] = "1";
		unset($conditions ["limit->"]);
				
		return (int) $this -> db -> getCell($this -> composeSQLQuery($conditions)); 		
	}
	
	/**
	 * Builds sql query from array of parameters (query constructor).
	 */
	public function composeSQLQuery(array $params = [])
	{
		$table = array_key_exists('table->', $params) ? $params['table->'] : $this -> table;
		$fields = array_key_exists("fields->", $params) ? str_replace("'", "", $params["fields->"]) : '*';
		$params_m2m = $this -> processParametersM2M($table, $params);

		if(array_key_exists("count->", $params))
			$fields = "COUNT(".(count($params_m2m['join']) ? 'DISTINCT `'.$table.'`.`id`' : '*').")";

		$query = "SELECT ".$fields." FROM `".$table."`";
		
		if(count($params_m2m['join']))
			$query .= ' '.implode(' ', $params_m2m['join']);
		
		$query .= $this -> processSQLConditions($params);

		return $query;	
	}
	
	/**
	 * Many to many sql conditions processing.
	 */
	public function processParametersM2M(string $table, array &$params)
	{
		$params_m2m = ['join' => [], 'where' => []];

		foreach($params as $key => $value)
			if(strpos($key, "->m2m") !== false && $value !== '' && $value != 0)
			{
				if(is_array($value) && !count($value))
					continue;

				$name = str_replace("->m2m", "", $key);
				
				if(array_key_exists('table->', $params))
				{
					$model_object = new (Registry :: getModelClassByTable($params['table->']));
					$element = $model_object -> getElement($name);
				}
				else
					$element = $this -> getElement($name);

				if($element === null)
					Debug :: displayError("Undefined field '".$name."' in query parameters.");
				
				$table_m2m = $element -> getProperty('linking_table');
				$opposite_id = $element -> getOppositeId();
				$params_m2m['join'][] = "JOIN `".$table_m2m."` ON `".$table_m2m."`.`".$opposite_id."`=`".$table."`.`id`";

				if(is_array($value))
				{
					foreach($value as $k => $v)
						$value[$k] = intval($v);

					$where = "`".$table_m2m."`.`".$name."_id` IN (".implode(',', $value).")";
				}
				else
					$where = "`".$table_m2m."`.`".$name."_id`='".intval($value)."'";

				$params[$key] = $where;
				$params_m2m['where'][] = $where;
			}
		
		return $params_m2m;
	}
	
	/**
	 * Builds sql query from array of parameters (query constructor).
	 */
	static public function processSQLConditions(array $params)
	{
		$registry = Registry :: instance();
		$db = Database :: instance();

		$where = [];
		$query = $order = $limit = '';
		$special_keys = ['table->', 'count->', 'fields->', 'group->by', 'extra->', 'order->double'];
		
		foreach($params as $key => $value)
		{
			$key = str_replace("'", "", $key);
			
			if(in_array($key, $special_keys))
				continue;
			
			if(strpos($key, "->m2m") !== false)
			{
				$where[] = $value;
				continue;
			}			
			
			if(strpos($key, "->like") !== false || strpos($key, "->not-like") !== false)
			{
				$field = str_replace(array("->like", "->not-like"), "", $key);
				$condition = strpos($key, "->like") ? "LIKE" : "NOT LIKE";
				
				$where[] = "`".$field."` ".$condition." ".$db -> secure("%".$value."%");
			}
			else if($key == "order->asc" || $key == "order->desc")
			{
				$order = strtoupper(str_replace("order->", "", $key));
				$order = " ORDER BY `".str_replace("'", "", $value)."` ".$order;
			}
			else if($key == "order->in" && $value)
			{
				if($registry -> getSetting("DbEngine") == "sqlite")
					continue;
				
				$order = " ORDER BY FIELD(id, ".str_replace("'", "", $value).")";
			}			
			else if($key == "order->" && $value == "random")
				$order = ' '.Database :: $adapter -> randomOrdering();
			else if(is_array($value) || strpos($key, "->in") !== false || strpos($key, "->not-in") !== false)
			{
				$field = str_replace(["->in", "->not-in"], "", $key);
				$condition_in = (is_array($value) || strpos($key, "->in") !== false); 
				
				if($registry -> getInitialVersion() >= 2.0)
				{
					$values = is_array($value) ? $value : explode(',', $value);
						
					foreach($values as $k => $v)
					{
						$v = trim($v);
						
						if($v != "")
							$values[$k] = "'".str_replace("'", "", $v)."'";
						else
							unset($values[$k]);
					}
						
					$value = implode(",", $values);
				}
				else //Old version of code
				{
					if(preg_match("/^[\d\s,']+$/", $value))
						$value = str_replace("'", "", $value);
					else
					{
						$values = explode(",", $value);
							
						foreach($values as $k => $v)
							$values[$k] = "'".str_replace("'", "", trim($v))."'";
							
						$value = implode(",", $values);
					}
				}
				
				$where[] = $field." ".($condition_in ? "IN" : "NOT IN")."(".$value.")";
			}
			else if($key == "limit->")
				$limit = " LIMIT ".trim(str_replace("LIMIT", "", str_replace("'", "", $value)));
			else if(strpos($key, "!=") !== false || strpos($key, ">") !== false || strpos($key, "<") !== false)
			{
				$condition = preg_replace("/.*(!=|>=?|<=?)$/", "$1", $key);
				$key = str_replace($condition, "", $key);
				$where[] = "`".$key."`".$condition.$db -> secure($value);
			}
			else if($key == "id")
				$where[] = "`id`='".intval($value)."'";			
			else
			{
				$value = is_null($value) ? '' : $value;
				$where[] = "`".$key."`=".$db -> secure($value);
			}
		}
				
		if(array_key_exists("extra->", $params))
			$where[] = $params["extra->"];				
		
		$query = count($where) ? " WHERE ".implode(" AND ", $where) : "";
		
		if(array_key_exists("group->by", $params))
			$query .= " GROUP BY ".str_replace(["'", ','], ['', ','], $params["group->by"]);
			
		if($order && array_key_exists("order->double", $params))
		{
			$values = explode("->", $params["order->double"]);
			
			if(count($values) == 2 && ($values[1] == "asc" || $values[1] == "desc"))
				$order .= ", `".$values[0]."` ".strtoupper($values[1]);
		}

		return $query.$order.$limit;
	}
	
	/**
	 * Retrieves number of database rows as arrays, according to conditions.
	 * @param array $conditions like ['active' => 1, 'limit->' => 10]
	 * @return array of rows (where ids are keys)
	 */
	public function select(array $conditions = [])
	{	
		return $this -> db -> getAll($this -> composeSQLQuery($conditions));
	}
	
	/**
	 * Retrieves one database row as array, according to conditions.
	 * @param array $conditions like ['active' => 1]
	 * @return array one row from database
	 */
	public function selectOne(array $conditions = [])
	{
		$conditions['limit->'] = '1';

		return $this -> db -> getRow($this -> composeSQLQuery($conditions));
	}
	
	/**
	 * Retrieves one database column as array, according to conditions.
	 * @param array $conditions like ['active' => 1, 'fields->' => 'name'], 'fields->' is required
	 * @return array of column values
	 */
	public function selectColumn($conditions = [])
	{
		return $this -> db -> getColumn($this -> composeSQLQuery($conditions));
	}

	/**
	 * Updates database rows, according to params and conditions.
	 * @param array $params fields to change like ['active' => 0]
	 * @param array $conditions (optional) which records, like ['price<=' => 100]
	 * @return self
	 */
	public function updateManyRecords(array $params, array $conditions = [])
	{
		$table = $this -> table;
		
		if(isset($conditions['table->']))
		{
			$table = $conditions['table->'];
			unset($conditions['table->']);
		}
		
		$fields = [];
		
		foreach($params as $field => $value)
		{
			$value = htmlspecialchars(trim($value), ENT_QUOTES);
			$value = Service :: cleanHtmlSpecialChars($value);
			$fields[] = $field."=".$this -> db -> secure($value);
		}
			
		$where = $this -> processSQLConditions($conditions);
		$this -> db -> query("UPDATE ".$table." SET ".implode(",", $fields).$where);
		
		return $this;
	}
	
	/**
	 * Deletes database rows, according to conditions.
	 * @param array $conditions like ['price' => 0, 'active' => 1]
	 * @return self
	 */
	public function deleteManyRecords($conditions)
	{
		$table = $this -> table;
		
		if(isset($conditions['table->']))
		{
			$table = $conditions['table->'];
			unset($conditions['table->']);
		}
		
		$where = $this -> processSQLConditions($conditions);
		
		if($where)
		{
			$ids = $this -> db -> getColumn("SELECT id FROM ".$table." ".$where);

			if(count($ids))
			{
				$this -> db -> query("DELETE FROM ".$table." ".$where);
				
				if(isset($this -> elements) && is_array($this -> elements))
					foreach($this -> elements as $name => $object)
						if($object -> getType() === 'many_to_many')
							foreach($ids as $id)
								$this -> db -> query("DELETE FROM ".$object -> getProperty('linking_table')."
													  WHERE ".$object -> getOppositeId()."='".$id."'");
			}
		}
		
		return $this;		
	}
	
	/**
	 * Removes all records from table.
	 * @param string $table if empty, then initial model teble will be cleared
	 */
	public function clearTable(string $table = '')
	{
		Database :: $adapter -> clearTable($table !== '' ? $table : $this -> table);
				
		return $this;
	}
	
	/**
	 * Processes extra parameters for image resizing.
	 * @return array
	 */
	static public function processImageArguments(array $arguments)
	{
		$params = [];
		$params["title"] = $params["css-class"] = "";
		
		if(isset($arguments[3]) && is_array($arguments[3]))
		{
			$params = $arguments[3];
			$params["alt-text"] = isset($params["alt-text"]) ? $params["alt-text"] : "";
			$params["title"] = (isset($params["title"]) && $params["title"]) ? ' title="'.$params["title"].'"' : "";
			$params["css-class"] = isset($params["css-class"]) ? ' class="'.$params["css-class"].'"' : "";
			$params["no-image-text"] = isset($params["no-image-text"]) ? $params["no-image-text"] : "";
		}
		else
		{
			$params["alt-text"] = (isset($arguments[3]) && $arguments[3]) ? $arguments[3] : "";
			$params["no-image-text"] = (isset($arguments[4]) && $arguments[4]) ? $arguments[4] : "";
		}
		
		return $params;
	}
	
	/**
	 * Resizes the image, keeps the initial ratio of width and height.
	 * @return string img html tag
	 */
	public function resizeImage(string $image, int $width, int $height)
	{		
		$arguments = func_get_args();
		$argument_3 = isset($arguments[3]) ? $arguments[3] : null;
		$argument_4 = isset($arguments[4]) ? $arguments[4] : null;
		
		return $this -> cropImage($image, $width, $height, $argument_3, $argument_4, 'resize');		
	}
	
	/**
	 * Resizes and crops the image, does not keep the initial ratio of width and height.
	 * @return string img html tag
	 */
	public function cropImage(string $image, int $width, int $height)
	{
		$arguments = func_get_args();
		$params = self :: processImageArguments($arguments);
		$image = Service :: addFileRoot((string) $image);
		
		if(!$image || !is_file($image))
			return $params["no-image-text"];
		
		$folder = $this -> table."_".$width."x".$height;

		$imager = new Imager();
		$method = (isset($arguments[5]) && $arguments[5] == "resize") ? "compress" : "crop";

		$src = $imager -> $method($image, $folder, $width, $height);
		$file = Service :: removeFileRoot($this -> registry -> getSetting("DocumentRoot").$src);
		
		if(isset($params["watermark"]) && !$imager -> wasCreatedErlier())
		{
			$margin_top = isset($params["watermark-margin-top"]) ? intval($params["watermark-margin-top"]) : false;
			$margin_bottom = isset($params["watermark-margin-bottom"]) ? intval($params["watermark-margin-bottom"]) : false;
			$margin_left = isset($params["watermark-margin-left"]) ? intval($params["watermark-margin-left"]) : false;
			$margin_right = isset($params["watermark-margin-right"]) ? intval($params["watermark-margin-right"]) : false;
						
			$imager -> addWatermark($file, $params["watermark"], $margin_top, $margin_bottom, $margin_left, $margin_right);
		}
		
		if(isset($params["only-source"]) && $params["only-source"])
			return $src;
			
		return "<img".$params["css-class"]." src=\"".$src."\" alt=\"".$params["alt-text"]."\"".$params["title"]." />\n";		
	}
	
	/**
	 * Takes first image path, in multi_images datatype value.
	 * @param string $value db cell value
	 * @return string
	 */
	static public function getFirstImage(string $value)
	{
		$images = MultiImagesModelElement :: unpackValue($value);

		return $images[0]['image'] ?? '';
	}
	
	/**
	 * Unpacks multi_images datatype value into the array.
	 * @param string $value db cell value
	 * @param string $no_comments (optional) if passed 'no-comments' will return only images
	 * @return array of images and comments
	 */
	static public function extractImages(string $value, ?string $no_comments = '')
	{
		$images = MultiImagesModelElement :: unpackValue($value);
		$no_comments = $no_comments == 'no-comments';
		$result = [];

		if(!$no_comments && Registry :: getInitialVersion() >= 3.0)
			return $images;

		foreach($images as $image)
			if($no_comments)
				$result[] = $image['image'];
			else
				$result[$image['image']] = $image['comment'];
			
		return $result;
	}
}	