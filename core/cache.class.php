<?php
/**
 * Cache manager class.
 * Keeps data in database or filesysytem and cleans it when needed.
 */
class Cache
{  
	/**
	 * ODatabase manager object.
	 * @var Database
	 */
	public $db;

	/**
	 * Key of cache fragment to store it in database.
	 * @var string
	 */
	private $current_key;
	
	/**
	 * Table name for saved html cached fragments.
	 * @var string
	 */
	private const CONTENT_TABLE = 'cache';
	
	/**
	 * Table which contains marks for dropping old cache data,
	 * @var string
	 */
	private const CLEANUP_TABLE = 'cache_clean';

	public function __construct()
	{
      	$this -> db = DataBase :: instance();
	}
	
	/**
	 * Checks if cache is enabled in config file.
	 */
	static public function checkIfEnabled()
	{
		return (bool) Registry :: get('EnableCache');
	}
	
	/**
	 * Saves content into cache table by key with models names for cleanup 
	 * after action in admin panel and optional lifetime value.
	 */
	public function save(string $key, mixed $content, mixed $models = [], int $lifetime = 0)
	{
		if(!self :: checkIfEnabled())
			return $content;
		
		if(!preg_match("/[A-z]/", $key))
			Debug :: displayError("Key '".$key."' of the cached item must contain letters.");
		
		$key = str_replace("'", '', $key);
		$checked_models = [];

		if(is_int($models))
		{
			$lifetime = $models;
			$models = [];
		}
		else if(is_array($models))
		{
			//Checks if passed models names are valid
			foreach($models as $value) 
				if(Registry :: checkModel($value))
					$checked_models[] = $value;
	
		}
		else if($models !== '*')
			return $content;

		if(Registry :: getInitialVersion() < 3.0)
		{
			//If this cache is deleted by any action in admin panel
			if(!count($checked_models))
				$checked_models[] = '*';
		}
		else //New version of code
		{
			if($models === '*')
				$checked_models[] = '*';

			$content = serialize($content);
			$lifetime = $lifetime > 0 ? time() + $lifetime : 0;

			if(random_int(1, 10) == 10)
				self :: cleanByLifetime();
		}
				
		$this -> db -> beginTransaction();

		$this -> cleanByKey($key); //Deletes old cache with such key

		if(Registry :: getInitialVersion() < 3.0)
			$query = "INSERT INTO `".self :: CONTENT_TABLE."` (`key`,`content`)
					  VALUES(".$this -> db -> secure($key).",".$this -> db -> secure($content).")";
		else
			$query = "INSERT INTO `".self :: CONTENT_TABLE."` (`key`,`content`,`until`)
					  VALUES(".$this -> db -> secure($key).",".$this -> db -> secure($content).", ".$lifetime.")";

		$this -> db -> query($query);
		
		foreach($checked_models as $model) //Adds links for cache cleaning
			$this -> db -> query("INSERT INTO `".self :: CLEANUP_TABLE."` (`key`,`model`)
								  VALUES(".$this -> db -> secure($key).",".$this -> db -> secure($model).")");
				
		$this -> db -> commitTransaction();
		
		return $content;
	}
	
	/**
	 * Saves the content in cache and returns it,
	 */
	public function saveAndDisplay(string $key, mixed $content, mixed $models = [], int $lifetime = 0)
	{
		$this -> save($key, $content, $models, $lifetime);
		
		echo $content;
	}
	
	/**
	 * Looks for and returns the content in cache by key.
	 */
	public function find(string $key)
	{
		if(!self :: checkIfEnabled())
			return;
		
		$value = $this -> db -> getRow("SELECT * FROM `".self :: CONTENT_TABLE."` 
									   	WHERE `key`=".$this -> db -> secure($key));

		if(is_array($value))
			if(Registry :: getInitialVersion() < 3.0)
				return $value['content'];
			else if(intval($value['until']) === 0 || time() < intval($value['until']))
				return unserialize($value['content']);
	}
	
	/**
	 * Looks for and outputs the content in cache by key.
	 */
	public function findAndDisplay(string $key)
	{
		if($content = $this -> find($key))
			echo $content;
		
		return $content ? true : false;
	}
	
	/**
	 * Removes cached value by key.
	 */
	static public function cleanByKey(string $key)
	{
		if(!self :: checkIfEnabled())
			return;
		
		$db = DataBase :: instance();
		
		$db -> query("DELETE FROM `".self :: CONTENT_TABLE."` WHERE `key`=".$db -> secure($key));
		$db -> query("DELETE FROM `".self :: CLEANUP_TABLE."` WHERE `key`=".$db -> secure($key));
	}
	
	/**
	 * Removes cached values by model name.
	 */
	static public function cleanByModel(string $model)
	{		
		if(!self :: checkIfEnabled())
			return;
		
		$keys = DataBase :: instance() -> getColumn("SELECT `key` FROM `".self :: CLEANUP_TABLE."` 
								 					 WHERE `model`='*' OR `model`='".$model."'");

		foreach($keys as $key)
			self :: cleanByKey($key);
	}

	/**
	 * Removes cached values by lifetime.
	 */
	static public function cleanByLifetime()
	{
		$keys = DataBase :: instance() -> getColumn("SELECT `key` FROM `".self :: CONTENT_TABLE."` 
								 					 WHERE `until`>'0' AND `until`<='".time()."'");

		foreach($keys as $key)
			self :: cleanByKey($key);		
	}
	
	/**
	 * Drops all cache in database tables.
	 */
	static public function cleanAll()
	{
		if(!self :: checkIfEnabled())
			return;
		
		Database :: $adapter -> clearTable(self :: CONTENT_TABLE);
		Database :: $adapter -> clearTable(self :: CLEANUP_TABLE);
	}
	
	/**
	 * Looks for the cache by key, if found return it, if not - starts buffering to get content.
	 * @return bool true if cache found and displyed
	 */
	public function displayOrStart(string $key)
	{
		if($content = $this -> find($key)) //We found cache by current key
		{
			echo $content;

			return true;
		}
		else //We have no cache with current key so we start the caching process
		{
			$this -> current_key = $key; //Saves the key name
			ob_start();

			return false;
		}
	}

	/**
	 * Stops buffering, takes data and saves it according to models and lifetime.
	 */
	public function stopAndSave(mixed $models = [], $lifetime = 0)
	{
		if(!$this -> current_key)
			return;
		
		$content = ob_get_flush(); //Getting cache data form buffer		
				
		if($content) //Save data if it's not empty
			$this -> save($this -> current_key, $content, $models, $lifetime);
		
		$this -> current_key = null;
	}

	/**
	 * Takes routes map from cache file for Router object,
	 */
	static public function getRoutesMapFromCache()
	{
		$file = Registry :: get('IncludePath').'userfiles/cache/';
		$file .= 'routes-map-'.Registry :: get('Build').'.php';

		if(!is_file($file))
			return;

		$time = filemtime(Registry :: get('IncludePath').'config/routes.php');
		$cache = include_once($file);

		if($time == $cache['RoutesFileTime'])
			return $cache;
	}

	/**
	 * Saves routes map into cache file.
	 */
	static public function createMainConfigFile($config_files)
	{
		$env = Registry:: get('IncludePath').'.env';

		if(!is_file($env))
			return;

		$settings = Registry :: getAllSettings();
		$settings['ConfigFilesHash'] = Service :: getFilesModificationTimesHash($config_files);
		$settings['EnvFileTime'] = filemtime($env);
		$settings['CheckConfigFilesUntil'] = time() + intval(Registry :: get('CacheFilesCheckTime') ?? 300);

		self :: saveConfigCacheIntoFile($settings, 'env');
	}

	/**
	 * Removes invalid cache files by key prefix.
	 */
	static public function cleanConfigCacheFilesByKey(string $key, string $folder = '')
	{
		$folder = Registry:: get('IncludePath').($folder ? $folder : 'userfiles/cache').'/';

		if(!is_dir($folder))
			return;

		$files = scandir($folder);

		foreach($files as $file)
			if(is_file($folder.$file) && strpos($file, $key.'-') === 0)
				if(Service :: getExtension($file) === 'php')
					unlink($folder.$file);
	}

	/**
	 * Saves cache from config files into cache folder.
	 */
	static public function saveConfigCacheIntoFile(array $data, string $file_key)
	{
		$cache_folder = Registry:: get('IncludePath').'userfiles/cache/';

		if(!is_dir($cache_folder))
			mkdir($cache_folder);

		self :: cleanConfigCacheFilesByKey($file_key);

		$content = "<?php\nreturn ".var_export($data, true).";?>\n";

		$file = $cache_folder.$file_key.'-'.(Registry :: get('Build') ?? '0').'.php';
		file_put_contents($file, $content);
	}
}