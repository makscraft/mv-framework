<?php
/**
 * SQLite database engine adapter.
 */
class SqliteAdapter extends DbAdapter
{
	public function runPDO()
	{
		$file = self :: $registry -> getSetting("IncludePath")."userfiles/database/sqlite/";
		$file .= self :: $registry -> getSetting("DbFile");
		
		if(file_exists($file))
			$pdo = new PDO("sqlite:".$file);
		else 
			Debug :: displayError("SQLite database file ~/userfiles/database/sqlite/".self :: $registry -> getSetting("DbFile")." not found.");

		if(!function_exists("great_fix_of_utf8_sqlite"))
		{
			function great_fix_of_utf8_sqlite($mask, $value) //Fix utf-8 case bug when using LIKE
			{
			    $mask = str_replace(array("%", "_"), array(".*?", "."), preg_quote($mask, "/"));
			    $mask = "/^".$mask."$/ui";
				
			    return preg_match($mask, strval($value));
			}
		}		
	
		$pdo -> sqliteCreateFunction("like", "great_fix_of_utf8_sqlite", 2);
		
		if(!function_exists("my_sqlite_regexp"))
		{
			function my_sqlite_regexp($regexp, $value)
			{
    			return (int) preg_match("/".$regexp."/", $value);
			}
		}
		
		$pdo -> sqliteCreateFunction("regexp", "my_sqlite_regexp", 2);
		
		return $pdo;
	}
		
	public function unixTimeStamp($name)
	{
		if($name == 'now')
			return "strftime('%s', 'now')";
		else
			return "strftime('%s', `".$name."`)";
	}

	public function randomOrdering()
	{
		return 'ORDER BY RANDOM()';
	}

	public function regularExpression()
	{
		return 'REGEXP';
	}

	public function getTables()
	{
		return "SELECT `name` FROM `sqlite_master` WHERE `type`='table'";
	}

	public function clearTable($table)
	{
		$db = Database :: instance();

		$db -> query("DELETE FROM `".$table."`");
		$db -> query("DELETE FROM `sqlite_sequence` WHERE `name`='".$table."'");
	}
	
	//Migrations methods
	
	public function defineColumnDataType($element)
	{
		$result = "";
		$type = $element -> getType();
		
		if($type == "bool" || $type == "int" || $type == "order" || $type == "parent")
			$result = "INTEGER";
		else if($type == "float")
			$result = "REAL";
		else if($type == "date" || $type == "date_time")
			$result = "NUMERIC";
		else if($type == "enum")
			$result = $element -> getProperty("foreign_key") ? "INTEGER" : "TEXT";
		else
			$result = "TEXT";
										
		return $result;
	}
	
	public function ifTableExists($table)
	{
		$names = Database :: instance() -> getColumn("SELECT `name` FROM `sqlite_master` WHERE `type`='table'");
		
		return in_array($table, $names);
	}
	
	public function ifTableColumnExists($table, $column)
	{
		$data = Database :: instance() -> getAll("PRAGMA table_info(`".$table."`)");
		
		foreach($data as $row)
			if(isset($row["name"]) && $row["name"] == $column)
				return true;
		
		return false;
	}
	
	public function ifTableIndexExists($table, $index_name)
	{
		$indexes = Database :: instance() -> getAll("SELECT `name` FROM `sqlite_master` 
													 WHERE `type`='index' AND `tbl_name`='".$table."'");
		
		$index_name = $table."_".$index_name;
		
		foreach($indexes as $row)
			if(isset($row["name"]) && $row["name"] == $index_name)
				return true;
				
		return false;
	}
	
	public function addTable($model, $table)
	{
		$is_simple_model = (get_parent_class($model) == "ModelSimple");
		
		$sql = "CREATE TABLE `".$table."` (\n";
		$fields = $result_sql = [];
		
		if($is_simple_model)
			$sql .= "`key` PRIMARY KEY NOT NULL, \n`value` TEXT";
		else
		{
			$sql .= "`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,\n";
				
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
								
					$fields[] = "`".$element -> getName()."` ".$datatype;
				}
			}
		}
			
		if(!$is_simple_model)
			$sql .= implode(", \n", $fields);
		
		$sql .= ");\n";
		
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
		$m2m_sql .= "`".$self_id."` INTEGER NOT NULL,\n";
		$m2m_sql .= "`".$opposite_id."` INTEGER NOT NULL);\n\n";
		
		$m2m_sql .= "CREATE INDEX `".$table_m2m."_".$self_id."` ON `".$table_m2m."` (`".$self_id."`);\n";
		$m2m_sql .= "CREATE INDEX `".$table_m2m."_".$opposite_id."` ON `".$table_m2m."` (`".$opposite_id."`);\n";

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
		
		return "ALTER TABLE `".$model -> getTable()."` ADD COLUMN `".$column."` ".$datatype.";";
	}
	
	public function addTableColumnIndex($model, $column)
	{
		$fields = preg_split("/\s*\,\s*/", $column);
		$name = (strpos($column, ",") === false) ? $column : implode("_", $fields);
		$name = $model -> getTable()."_".$name;
		
		return "CREATE INDEX `".$name."` ON `".$model -> getTable()."` (`".implode("`, `", $fields)."`);\n";
	}

	public function dropTableColumn($model, $column)
	{
		return "ALTER TABLE `".$model -> getTable()."` DROP COLUMN `".$column."`;";
	}
	
	public function dropTableColumnIndex($model, $column)
	{
		$fields = preg_split("/\s*\,\s*/", $column);
		$name = (strpos($column, ",") === false) ? $column : implode("_", $fields);
		$name = $model -> getTable()."_".$name;
		
		return "DROP INDEX `".$name."`;\n";
	}

	public function renameTableColumn($model, $old_column, $new_column)
	{
		$element = $model -> getElement($old_column);
		
		if(!$element)
			$element = $model -> getElement($new_column);
		
		$datatype = $this -> defineColumnDataType($element);
		
		return "ALTER TABLE `".$model -> getTable()."` RENAME COLUMN `".$old_column."` TO `".$new_column."`;";
	}
}