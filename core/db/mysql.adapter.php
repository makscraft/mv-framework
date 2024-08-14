<?php
/**
 * MySQL database engine adapter.
 */
class MysqlAdapter extends DbAdapter
{
	public function runPDO()
	{
		$host = self :: $registry -> getSetting("DbHost");
		$user = self :: $registry -> getSetting("DbUser");
		$pass = self :: $registry -> getSetting("DbPassword");
		$name = self :: $registry -> getSetting("DbName");
		
		$pdo = new PDO("mysql:host=".$host.";dbname=".$name, $user, $pass, 
			            array(PDO :: MYSQL_ATTR_INIT_COMMAND => "SET NAMES \"UTF8\""));
								            
		$pdo -> setAttribute(PDO :: MYSQL_ATTR_USE_BUFFERED_QUERY, true);

		return $pdo;
	}
				
	public function unixTimeStamp($name)
	{
		if($name == 'now')
			return "UNIX_TIMESTAMP(NOW())";
		else
			return "UNIX_TIMESTAMP(`".$name."`)";
	}

	public function randomOrdering()
	{
		return 'ORDER BY RAND()';
	}

	public function regularExpression()
	{
		return 'RLIKE';
	}
	
	public function getTables()
	{
		return 'SHOW TABLES';
	}

	public function clearTable($table)
	{
		Database :: instance() -> query("TRUNCATE `".$table."`");
	}
	
	//Migrations methods
	
	public function defineColumnDataType($element)
	{
		$result = "";
		$type = $element -> getType();
		
		$varchar_types = ['char','url','redirect','email','phone','password','image','file','group'];
		
		if($type == "bool")
			$result = "tinyint(1)";
		else if($type == "int" || $type == "order" || $type == "parent")
			$result = "int(11)";
		else if($type == "float")
			$result = "float";
		else if($type == "date")
			$result = "date";
		else if($type == "date_time")
			$result = "datetime";
		else if($type == "text" || $type == "multi_images")
			$result = "text";
		else if(in_array($type, $varchar_types))
			$result = "varchar(250)";
		else if($type == "enum")
			$result = $element -> getProperty("foreign_key") ? "int(11)" : "varchar(100)";
									
		return $result;
	}
	
	public function ifTableExists($table)
	{
		$names = Database :: instance() -> getColumn("SHOW TABLES");
		
		return in_array($table, $names);
	}
	
	public function ifTableColumnExists($table, $column)
	{
		$data = Database :: instance() -> getAll("SHOW COLUMNS FROM `".$table."` WHERE `Field`='".$column."'");
		
		return (count($data) == 1 && isset($data[0])) ? true : false;
	}
	
	public function ifTableIndexExists($table, $index_name, $columns = 1)
	{
		$indexes = Database :: instance() -> getAll("SHOW INDEX FROM `".$table."` WHERE `Key_name`='".$index_name."'");
		
		return (count($indexes) == $columns && isset($indexes[0])) ? true : false;
	}
	
	public function addTable($model, $table)
	{
		$is_simple_model = (get_parent_class($model) == "ModelSimple");
		
		$sql = "CREATE TABLE `".$table."` (\n";
		$fields = $result_sql = [];
		
		if($is_simple_model)
			$sql .= "`key` varchar(100) NOT NULL, \n`value` text NOT NULL";
		else
		{
			$sql .= "`id` int(11) NOT NULL,\n";
			
			foreach($model -> getElements() as $element)
			{
				$type = $element -> getType();
				
				if($type == "text" && $element -> getProperty("virtual"))
					continue;
				
				if($type == "many_to_one")
					$result_sql = $this -> addManyToOneField($model, $element, $result_sql);
				else if($type == "many_to_many")
					$result_sql = $this -> addManyToManyTable($element, $result_sql);
				else
				{
					$datatype = $this -> defineColumnDataType($element);
					
					if(!$datatype)
						continue;
					
					$fields[] = "`".$element -> getName()."` ".$datatype." NOT NULL";
				}
			}
		}
		
		if(!$is_simple_model)
			$sql .= implode(", \n", $fields);
		
		$sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8;\n\n";
		
		if($is_simple_model)
			$sql .= "ALTER TABLE `".$table."` ADD PRIMARY KEY (`key`);\n";
		else
		{
			$sql .= "ALTER TABLE `".$table."` ADD PRIMARY KEY (`id`);\n";
			$sql .= "ALTER TABLE `".$table."` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;\n";
		}
		
		$final_sql = array("create-table-".$table => $sql);
		$final_sql = array_merge($final_sql, $result_sql);
		
		return $final_sql;
	}
	
	public function addManyToManyTable($element, $result_sql)
	{
		$table_m2m = $element -> getProperty("linking_table");
		
		if($this -> ifTableExists($table_m2m))
			return $result_sql;
			
		$self_id = $element -> getSelfId();
		$opposite_id = $element -> getOppositeId();
		
		$m2m_sql = "CREATE TABLE `".$table_m2m."` (\n";
		$m2m_sql .= "`".$self_id."` int(11) NOT NULL,\n";
		$m2m_sql .= "`".$opposite_id."` int(11) NOT NULL\n";
		$m2m_sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;\n\n";
		
		$m2m_sql .= "ALTER TABLE `".$table_m2m."` ADD KEY `".$self_id."` (`".$self_id."`);\n";
		$m2m_sql .= "ALTER TABLE `".$table_m2m."` ADD KEY `".$opposite_id."` (`".$opposite_id."`);\n";
		
		$result_sql["create-table-".$table_m2m."-m2m"] = $m2m_sql;
		
		return $result_sql;
	}
	
	public function addManyToOneField($model, $element, $result_sql)
	{
		$related_model = $element -> getProperty('related_model');
		$related_model = new $related_model();
		$column = $related_model -> getNameOfForeignKeyField(get_class($model));
		$table = $related_model -> getTable();
		
		$table_exists = Database :: $adapter -> ifTableExists($table);
		$index_exists = false;
		
		if($table_exists)
			$index_exists = Database :: $adapter -> ifTableIndexExists($table, $column);
		
		if(!$table_exists || ($table_exists && !$index_exists))
		{
			$key = "add-index-".$table."-".$column."-m2o";
			$result_sql[$key] = $this -> addTableColumnIndex($related_model, $column);
		}
		
		return $result_sql;
	}
	
	public function addTableColumn($model, $column)
	{
		$datatype = $this -> defineColumnDataType($model -> getElement($column));
		
		return "ALTER TABLE `".$model -> getTable()."` ADD `".$column."` ".$datatype." NOT NULL;";
	}
	
	public function dropTableColumn($model, $column)
	{
		return "ALTER TABLE `".$model -> getTable()."` DROP `".$column."`;";
	}
	
	public function renameTableColumn($model, $old_column, $new_column)
	{
		$element = $model -> getElement($old_column);
		
		if(!$element)
			$element = $model -> getElement($new_column);
		
		$datatype = $this -> defineColumnDataType($element);
		
		return "ALTER TABLE `".$model -> getTable()."` CHANGE `".$old_column."` `".$new_column."` ".$datatype." NOT NULL;";
	}
	
	public function addTableColumnIndex($model, $column)
	{
		if(strpos($column, ",") === false)
			return "ALTER TABLE `".$model -> getTable()."` ADD KEY `".$column."` (`".$column."`);\n";
		
		$fields = preg_split("/\s*\,\s*/", $column);
		$name = implode("_", $fields);
		
		return "ALTER TABLE `".$model -> getTable()."` ADD KEY `".$name."` (`".implode("`, `", $fields)."`);\n";
	}
	
	public function dropTableColumnIndex($model, $column)
	{
		if(strpos($column, ",") === false)
			return "ALTER TABLE `".$model -> getTable()."` DROP INDEX `".$column."`;\n";
		
		$name = implode("_", preg_split("/\s*\,\s*/", $column));
			
		return "ALTER TABLE `".$model -> getTable()."` DROP INDEX `".$name."`;\n";
	}
}