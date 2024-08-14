<?php
/**
 * Class contains all base methods related to database queries.
 * Uses PDO driver and works on singleton pattern.
 */
class Database
{
	/**
	 * Instance of singleton pattern to keep only one copy of object.
	 * @var object
	 */
	private static $instance;
	
	/**
	 * PDO object to execute queries.
	 * @var object
	 */
	public static $pdo;
	
	/**
	 * Object with settings.
	 * @var object
	 */
	private static $registry;

	/**
	 * Adapter with db engine specific commands.
	 * @var object
	 */
	public static $adapter;
	
	/**
	 * Storage with SQL queries for statistics.
	 * @var array
	 */
	public static $total = [];
	
	/**
	 * Database engine type.
	 * @var string
	 */
	public static $engine;
	
	private function __construct() {}
	
	public function __call($method, $params)
	{
		$param = $params[0] ?? null;
		
		if(method_exists(self :: $adapter, $method))
			return self :: $adapter -> $method($param);
		else
			Debug :: displayError("You must define method '".$method."' in ".get_class(self :: $adapter)." class.");
	}
	
	/**
	 * Establishes the connection with DB from config settings.
	 */
	static public function instance()
	{
		if(!isset(self :: $instance))
		{
			self :: $instance = new self();			
			self :: $registry = Registry :: instance();
			
			$db_engine = self :: $registry -> getSetting("DbEngine");
			
			if(!in_array($db_engine, PDO :: getAvailableDrivers()))
			{
				$message = "The PDO driver for database '".$db_engine."' is not available. ";
				$message .= "Please install the needed PDO driver or check the database config settings.";

				Debug :: displayError($message);
			}
			 
			self :: $engine = $db_engine;
			$base_db_adapter = self :: $registry -> getSetting("IncludePath")."core/db/base.adapter.php";
			$db_adapter_file = self :: $registry -> getSetting("IncludePath")."core/db/".$db_engine.".adapter.php";
			
			if(file_exists($db_adapter_file))
			{
				require_once $base_db_adapter;
				require_once $db_adapter_file;
				
				$db_adapter_class = ucfirst($db_engine."Adapter");
				self :: $adapter = new $db_adapter_class();
			}
			else
				Debug :: displayError("Database adapter class required ~/core/db/".$db_engine.".adapter.php"); 
			
			try
			{
				self :: $pdo = self :: $adapter -> runPDO();
			}
			catch(PDOException $error)
			{
				if(Registry :: onDevelopment())
					Debug :: displayError($error -> getMessage());
				else
				{
					self :: $registry -> setSetting("ErrorAlreadyLogged", true);
					Log :: add($error -> getMessage()." \r\n".$error -> getTraceAsString());
				}
				
				exit();
			}
						            
			self :: $pdo -> setAttribute(PDO :: ATTR_ERRMODE, PDO :: ERRMODE_EXCEPTION);
			
			if(self :: $engine == "mysql")
			{
				$sql_mode = self :: $registry -> getSetting("DbMode");
				
				if(!$sql_mode && self :: $registry -> getInitialVersion() < 2.4)
					$sql_mode = "NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION";
				
				if($sql_mode)
					self :: $pdo -> query("SET `sql_mode`='".$sql_mode."'");
			}
			
			//Sets local time zone if defined
			if(self :: $engine == "mysql" && $time_zone = self :: $registry -> getSetting("TimeZone"))
			{
				date_default_timezone_set($time_zone);
				self :: $pdo -> query("SET `time_zone`='".date('P')."'");
			}
		}
			
		return self :: $instance;
	}
	
	/**
	 * Disconnects database adapter.
	 */
	static public function close()
	{
		self :: $pdo = self :: $engine = self :: $instance = self :: $adapter = self :: $registry = null;
		self :: $total = [];
	}
	
	/**
	 * Makes value safe before insert into database.
	 */
	static public function secure($value)
	{		
		return self :: $pdo -> quote($value);
	}
	
	/**
	 * Starts the transaction.
	 */
	public function beginTransaction()
	{
		self :: $pdo -> beginTransaction();
	}

	/**
	 * Commits the current transaction.
	 */
	public function commitTransaction()
	{
		if(method_exists(self :: $pdo, 'inTransaction'))
		{
			if(self :: $pdo -> inTransaction())
				self :: $pdo -> commit();
		
			return;
		}

		self :: $pdo -> commit();
	}

	/**
	 * Executes one SQL query and puts it into the storage for debug panel.
	 */
	public function query(string $query)
	{
		if(Registry :: get('DebugPanel') === true)
			self :: $total[] = $query;
		
		try
		{
			if($result = self :: $pdo -> query($query))
				return $result;
		}
		catch(PDOException $error)
		{
			if(Registry :: onDevelopment())
				Debug :: displayError($error -> getMessage());
			else
			{
				Registry :: set('ErrorAlreadyLogged', true);
				$trace = $error -> getTrace();

				Log :: add($error -> getMessage()."\r\n".$trace[0]["args"][0]." \r\n".$error -> getTraceAsString());
			}
			
			exit();		
		}
	}
	
	/**
	 * Adopts result of SQL query to get the data from it.
	 */
	static public function fetch(mixed $result, string $format = '')
	{
		if($format === 'NUM')
			return $result -> fetch(PDO :: FETCH_NUM);
		else
			return $result -> fetch(PDO :: FETCH_ASSOC);
	}
	
	/**
	 * Gets the id in table created by the last SQL insert query.
	 */
	static public function lastId()
	{
		return (int) self :: $pdo -> lastInsertId();
	}
	
	/**
	 * Gets one cell from table row.
	 */
	public function getCell(string $query)
	{
		return $this -> query($query) -> fetchColumn();
	}
	
	/**
	 * Gets one row from table.
	 */
	public function getRow(string $query)
	{
		return $this -> query($query) -> fetch(PDO :: FETCH_ASSOC);
	}
	
	/**
	 * Gets one column from table.
	 */
	public function getColumn(string $query)
	{
		$result = $this -> query($query);
		$data = [];
		
		while($row = $this -> fetch($result, 'NUM'))
			$data[] = $row[0];

		return $data;
	}

	/**
	 * Get one or more rows from table and put them in array with ids as keys.
	 */
	public function getAll(string $query)
	{
		$data = [];
		$result = $this -> query($query);

		while($row = $this -> fetch($result, 'ASSOC'))
			if(isset($row['id']))
				$data[$row['id']] = $row;
			else
				$data[] = $row;

		return $data;
	}
	
	/**
	 * Counts the number of rows in SQL query result with parameters (optional).
	 */
	public function getCount(string $table, string $where = '')
	{
		$query = "SELECT COUNT(*) FROM `".$table."`";
		
		if($where !== '') //If we have the params we add them to query
			$query .= " WHERE ".$where;
			
		$result = $this -> getCell($query);
		
		return $result ? $result : 0;
	}

	/**
	 * Returnt dirrerence between 2 timestamps. 
	 */
	public function unixTimeStampDiff(string $from, string $to)
	{
		return "(".$this -> unixTimeStamp($from)." - ".$this -> unixTimeStamp($to).")";
	}
	
	/**
	 * Returns the array of all tables of current DB.
	 */
	public function getTables()
	{
		$tables = [];
		$result = $this -> query(self :: $adapter -> getTables());
		
		while($row = $this -> fetch($result, "NUM"))
			$tables[] = $row[0];
		
		return $tables;
	}
}
