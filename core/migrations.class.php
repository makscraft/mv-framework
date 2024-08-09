<?php
/**
 * Class for managing database modifications from admin panel.
 */
class Migrations
{
	/**
	 * Object with settings and configurations of the system.
	 * @var Registry
	 */
	private $registry;
	
	/**
	 * Database manager object.
	 * @var Database
	 */
	private $db;
	
	/**
	 * List of currently found migrations.
	 * @var array
	 */
	private $migrations_list = [];

	/**
	 * Marks if we run migrations in the CLI.
	 * @var bool
	 */
	private $in_console = false;
	
	public function __construct($in_console = false)
	{
		$this -> registry = Registry :: instance();
		$this -> db = Database :: instance();
		$this -> in_console = $in_console;
	}

	public function getMigrationsList()
	{
		return $this -> migrations_list;
	}
	
	public function getMigrationsQuantity()
	{
		return count($this -> migrations_list);
	}
	
	public function scanModels()
	{
		if($this -> in_console)
			$models = Registry :: get('Models');
		else
			$models = array_keys(Registry :: get('ModelsLower'));

		$sql = [];
		
		foreach($models as $model_name)
		{
			if(!$this -> in_console)
				$model_name = mb_strtolower($model_name, "utf-8");

			$model_object = new $model_name();

			$table = $model_object -> getTable();
			$table_exists = Database :: $adapter -> ifTableExists($table);
			
			if(!$table_exists)
			{				
				$data = Database :: $adapter -> addTable($model_object, $table);
				$sql = array_merge($sql, $data);
			}
			else if(!$model_object -> isSimpleModel())
			{
				foreach($model_object -> getElements() as $element)
				{
					$column = $element -> getName();
					$type = $element -> getType();
					
					if($type == "text" && $element -> getProperty("virtual"))
						continue;
					
					if($type == "many_to_many")
					{
						$sql = Database :: $adapter -> addManyToManyTable($element, $sql);
						continue;
					}
					
					if($type == "many_to_one")
					{
						$sql = Database :: $adapter -> addManyToOneField($model_object, $element, $sql);
						continue;
					}
					
					if(!Database :: $adapter -> ifTableColumnExists($table, $column))
					{
						$key = "add-column-".$table."-".$column;
						$data = Database :: $adapter -> addTableColumn($model_object, $column);
						$sql[$key] = $data;
					}
				}
			}
			
			$model_migrations = $model_object -> getMigrations();
			
			if($model_migrations && !$model_object -> isSimpleModel())
			{
				foreach($model_migrations as $key => $data)
				{
					if(!is_array($data) || !count($data))
						continue;
					
					if($key == "add_index" || $key == "drop_index")
					{
						$to_drop = ($key == "drop_index");
						
						foreach($data as $field)
						{
							$index_name = $field;
							$columns = 1;
							
							if(strpos($field, ",") !== false)
							{
								$parts = preg_split("/\s*\,\s*/", $field);
								$columns = count($parts);
								$index_name = implode("_", $parts);
							}
							
							if($table_exists && !$to_drop)
								if(Database :: $adapter -> ifTableIndexExists($table, $index_name, $columns))
									continue;
								
							if($table_exists && $to_drop)
								if(!Database :: $adapter -> ifTableIndexExists($table, $index_name, $columns))
									continue;
							
							if($to_drop)
							{
								$key = "drop-index-".$table."-".$field;
								$sql[$key] = Database :: $adapter -> dropTableColumnIndex($model_object, $field);
							}
							else
							{
								$key = "add-index-".$table."-".$field;
								$sql[$key] = Database :: $adapter -> addTableColumnIndex($model_object, $field);
							}
						}
					}
					else if($key == "drop_column")
					{
						foreach($data as $field)
						{
							if(!$table_exists || !Database :: $adapter -> ifTableColumnExists($table, $field))
								continue;
							
							$key = "drop-column-".$table."-".$field;
							$sql[$key] = Database :: $adapter -> dropTableColumn($model_object, $field);
						}
					}
					else if($key == "rename_column")
						foreach($data as $old_name => $new_name)
						{
							if(!$table_exists || !Database :: $adapter -> ifTableColumnExists($table, $old_name))
								continue;
							
							$key = "rename-column-".$table."-".$old_name;
							$sql[$key] = Database :: $adapter -> renameTableColumn($model_object, $old_name, $new_name);
							
							if(isset($sql["add-column-".$table."-".$new_name]))
								unset($sql["add-column-".$table."-".$new_name]);
						}
				}
			}
		}
		
		foreach($sql as $key => $query)
			if(preg_match("/^create-table-/", $key))
				$this -> migrations_list[$key] = $query;
		
		foreach($sql as $key => $query)
			if(preg_match("/^add-column-/", $key))
				$this -> migrations_list[$key] = $query;
		
		foreach($sql as $key => $query)
			if(!preg_match("/^create-table-/", $key) && !preg_match("/^add-column-/", $key))
				$this -> migrations_list[$key] = $query;
		
		return $this;
	}
	
	public function displayMigrationsList()
	{
		if(!count($this -> migrations_list))
			return "<p>Migrations not found.</p>\n";
		
		$html = "";
		
		foreach($this -> migrations_list as $key => $sql)
		{
			$can_run = true;
			
			if(strpos($key, "add-index-") !== false)
			{
				$parts = explode("-", $key);
				
				if(!Database :: $adapter -> ifTableExists($parts[2]))
					$can_run = false;
			}
			
			$html .= "<div>\n";
			
			if($can_run)
			{
				$html .= "<input type=\"button\" id=\"migration-".md5($key)."\" class=\"button-light run-one-migration\"";
				$html .= " value=\"Run migration\" />\n";
			}
			
			$html .= "<p>".nl2br($sql)."</p>\n";
			$html .= "</div>\n";
		}
		
		return $html;
	}
	
	public function checkMigrationKeyToken($token)
	{
		foreach($this -> migrations_list as $key => $sql)
			if(md5($key) == $token)
				return $key;
	}
	
	public function createAllMigrationsToken()
	{
		$token = implode("/", array_keys($this -> migrations_list)).$_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"];
		
		return md5($token);
	}
	
	public function runMigrations($key)
	{
		$data = ($key == "all") ? array_values($this -> migrations_list) : array($this -> migrations_list[$key]);
		$sql_list = [];
		
		foreach($data as $string)
			foreach(explode(";", $string) as $query)
				if(trim($query) != "")
					$sql_list[] = trim($query);

		foreach($sql_list as $sql)
			$this -> db -> query($sql);
	}

	public function getMigrationsShortList()
	{
		$result = [];

		foreach($this -> migrations_list as $sql)
		{
			$sql = str_replace(["\n", "\r"], [' ', ''], $sql);

			if(strpos($sql, 'CREATE TABLE ') !== false)
			{				
				$sql = substr($sql, 0, 50);
				$sql = preg_replace('/\s+\S+$/', '', $sql).' ...';
			}

			$result[] = $sql;
		}

		return $result;
	}
}
