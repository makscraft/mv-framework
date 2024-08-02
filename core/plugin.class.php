<?php
/**
 * Parent base class for MV plugins.
 * Plugins must be activated by adding into array in config/plugins.php.
 * Objects of plugins are being constructed automatically in Builder $mv object.
 */
abstract class Plugin extends ModelInitial
{
	/**
	 * Url from the domain root including the app folder (if actual)
	 * @var string
	 */
    public $root_path;

   	/**
	 * Pagination manager.
	 * @var object Paginator
	 */
	public $paginator;
	
	public function __construct()
	{
		$this -> registry = Registry :: instance(); 
		$this -> db = DataBase :: instance();
		$this -> table = $this -> registry -> definePluginTableName(get_class($this));
		$this -> root_path = $this -> registry -> get('MainPath');
	}
	
	public function getTable()
	{
		return $this -> table;
	}
	
	public function getId()
	{
		return $this -> id; 
	}

	public function runPaginator(int $total, int $limit, mixed $current = null)
	{
		$this -> paginator = new Paginator($total, $limit);

		if(is_numeric($current))
			$this -> paginator -> definePage(intval($current));
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

	public function __call($method, $arguments)
	{		
		if($method == 'runPager')
			return $this -> runPaginator($arguments[0], $arguments[1], $arguments[2] ?? null);
	}
}
