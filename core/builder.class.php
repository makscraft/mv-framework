<?php
/**
 * Main front object of application. Contains all common variables, models, plugins and pathes.
 * Accessors create models objects on the fly, by __call() method.
 * Plugins objects are being created right at the Builder instanse.
 */
class Builder
{
	/**
	 * Router object to define the view for the current request. 
	 * @var object Router
	 */
	public $router; 
   
	/**
	 * Settings manager.
	 * @var object Registry
	 */
	public $registry;
   
	/**
	 * Database manager.
	 * @var object Database
	 */
	public $db;
	
	/**
	 * Cache manager object.
	 * @var object Cache
	 */ 
	public $cache;
	
	/**
	 * Path from root of the application, including subfolder.
	 * @var string
	 */
	public $root_path;
	
	/**
	 * Path to css, images, fonts and js files.
	 * @var string
	 */
	public $media_path;
	
	/**
	 * Absolute path from server root to include the files.
	 * @var string
	 */
	public $include_path;
	
	/**
	 * Absolute path from server root to the views files (templates).
	 * @var string
	 */
	public $views_path;

	/**
	 * Application domain name.
	 * @var string
	 */
	public $domain;

	/**
	 * Available models, their objects are being created by __call() method (lazy load).
	 * @var array
	 */
	private $models = [];

	/**
	 * Available plugins, their objects are being created in Builder __construct() method.
	 * @var array of objects
	 */
	private $plugins = [];
	
	/**
	 * Sets all variables and runs required objects.
	 */
	public function __construct()
	{
		ob_start();
		
		$this -> registry = Registry :: instance(); //Langs and settings
      	$this -> db = DataBase :: instance(); //Manages database
      
      	if($this -> registry -> get('SessionSupport') && !Service :: sessionIsStarted())
        	session_start(); //Starts the session if needed
      
      	$this -> router = new Router(); //Object to analyze the requested page
      	
      	if(count($this -> router -> getUrlParts()) == 1) //Redirect to index page in some cases
      		if($this -> router -> getUrlPart(0) == "index" || $this -> router -> getUrlPart(0) == "index.php")
      		{
      			header($_SERVER["SERVER_PROTOCOL"]." 301 Moved Permanently");
      			header("Location: ".$this -> registry -> get('MainPath'));
      			exit(); 
      		}
      
		//Popular pathes setting
      	$this -> include_path = $this -> registry -> get('IncludePath');
      	$this -> views_path = $this -> include_path."views/";      	
      	$this -> root_path = $this -> registry -> get('MainPath');
      	$this -> media_path = $this -> root_path.'media/';
      	$this -> domain = $this -> registry -> get('DomainName');
      	
		//Sets local time zone if defined
		if($time_zone = $this -> registry -> get('TimeZone'))
			date_default_timezone_set($time_zone);
			
		//Starts all enabled plugins
		foreach($this -> registry -> get('PluginsLower') as $plugin => $lower)
			$this -> plugins[$lower] = new $plugin();
		
		//Creates cache manager if it's enabled
		if($this -> registry -> get('EnableCache'))
			$this -> cache = new Cache();
	}
	
	/**
	 * Returns models and plugins objects by name, creating them on the fly.
	 * @return object|null
	 */
	public function __get($name)
	{
		$name = strtolower($name);

		if(isset($this -> models[$name]))
			return $this -> models[$name];
		else if(isset($this -> plugins[$name]))
			return $this -> plugins[$name];

		//Automatic models objects lazy creating
		if(Registry :: checkModel($name))
		{
			$models = Registry :: get('ModelsLower');

			if(isset($models[$name]))
				return $this -> models[$models[$name]] = new $name();
			else if(in_array($name, $models))
			{
				$key = array_search($name, $models);

				return $this -> models[$name] = new $key();
			}
		}

		$plugins = Registry :: get('PluginsLower');

		if(isset($plugins[$name]))
			return $this -> plugins[$plugins[$name]];
	}
	
	/**
	 * Executes http redirect (initially to the app root).
	 * @param string $path additional part, is being added to root path
	 */
	public function redirect($path = '')
	{
		$path = preg_replace('/^\//', '', $path);

		header("Location: ".$this -> registry -> get("MainPath").$path);
		exit();
	}
	
	/**
	 * Executes http redirect to the same URL without GET query string.
	 * @param string $part additional part or GET params, will be added to current url
	 */
	public function reload($part = '')
	{
		$path = str_replace([$_SERVER["QUERY_STRING"], "?"], "", $_SERVER["REQUEST_URI"]);
		
		header("Location: ".$path.$part);
		exit();		
	}
	
	/**
	 * Displays 404 page by sending http header and including required view file.
	 */
	public function display404()
	{
		$arguments = func_get_args();

		//If any kind of not false or not null datatype param has being passed.
		if(isset($arguments[0]) && $arguments[0])
			return;
		
		$mv = $this;
			
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");

		if(file_exists($this -> views_path.'before-view.php'))
			include $this -> views_path.'before-view.php';

		$this -> router -> setRoute404();
		include $this -> views_path.$mv -> router -> getRoute();

		$this -> displayDebugPanel();
		exit();
	}
	
	/**
	 * Checks if needed part of url exists and has numeric type (optional).
	 * @param int $index integer key in url parts array
	 * @param string $condition optional value 'numeric'
	 * @return string part or url or executes 404 redirect if part does not exist
	 */
	public function checkUrlPart($index, $condition = '')
	{
		$url_parts = $this -> router -> getUrlParts();

		if(!isset($url_parts[$index]) || !$url_parts[$index])
			$this -> display404();
		
		if($condition === 'numeric' && !is_numeric($url_parts[$index]))
			$this -> display404();
			
		return $url_parts[$index];
	}

	/**
	 * Displays debug panel at the bottom of the window, depending on environment.
	 * To see the panel set 'DebugPanel' => true in config/setup.php.
	 * On production you must be logged in into admin panel to see debug panel.
	 */
	public function displayDebugPanel()
	{
		if(Registry :: get('DebugPanel'))
		{
			if(Registry :: onDevelopment())
				include_once Registry :: get('IncludeAdminPath').'controls/debug-panel.php';
			else if(isset($_SESSION['mv']['user']['id']))
			{
				if((new User($_SESSION['mv']['user']['id'])) -> checkUserLogin())
					include_once Registry :: get('IncludeAdminPath').'controls/debug-panel.php';
			}
		}
	}
}
