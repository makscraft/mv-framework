<?php
/**
 * Routing manager, checks the requested url, analyzes it and includes needed template (view) to display the page.
 * Also removes dangerous symbols and GET params from the url.
 */
class Router
{   
   	/**
	 * Initial url from http request.
	 * @var string
	 */	
   	private $url;
   	
   	/**
	 * Parts of requested url.
	 * @var array
	 */
   	private $url_parts = [];  
   
   	/**
	 * Defined route, matching array element from config/routes.php file.
	 * @var string
	 */	
   	private $route;

	/**
	 * Route to 404 error page from config/routes.php file.
	 * @var string
	 */	
	private $route_404;

   	public function __construct()
   	{ 	
	 	//Cuts off the GET parameters and index.php from the url
      	$url = trim($_SERVER['REQUEST_URI']);
      	$url = str_replace(["'", 'index.php', '?'.$_SERVER['QUERY_STRING']], '', $url);
      	$parts = ($url != '/') ? explode('/', $url) : [];
      	
      	//Splits the url into single parts
      	foreach($parts as $part)
      		if(trim($part) != '')
      			$this -> url_parts[] = trim($part);
      	
      	//Cuts off application folder from url (if the site is not in the root folder of domain)
      	if($url_cut = $this -> defineUrlCut())
         	array_splice($this -> url_parts, 0, $url_cut);
         
        //Final clean url ready for searching route match
		$this -> url = count($this -> url_parts) ? '/'.implode('/', $this -> url_parts) : '/';

		//Older versions fix
		if($this -> url !== '/' && Registry :: getInitialVersion() < 3.0)
			$this -> url = implode('/', $this -> url_parts).'/';
   	}
   
	/**
	 * Returns current processed url.
	 * @return string
	 */
	public function getUrl()
	{
		return $this -> url;
	}

	/**
	 * Returns parts of current url (main index page has no parts).
	 * @return array
	 */
	public function getUrlParts()
	{
		return $this -> url_parts;
	}

	/**
	 * Returns current route (view file to display the response).
	 * @return string
	 */
	public function getRoute()
	{
		return $this -> route;
	}

	/**
	 * Sets current route to 404 error view.
	 * @return self
	 */
	public function setRoute404()
	{
		$this -> route = $this -> route_404;

		return $this;
	}

	/**
	 * Returns number of parts in current url (main index page has 0 parts).
	 * @return int
	 */
	public function countUrlParts()
	{
		return count($this -> url_parts);
	}
	
	/**
	 * Sets the url parts array.
	 * @param array $url_parts
	 * @return self
	 */
	public function setUrlParts(array $url_parts)
	{
		$this -> url_parts = $url_parts;

		return $this; 
	}
	
	/**
	 * Sets the url into router.
	 * @param string $url
	 * @return self
	 */
	public function setUrl(string $url)
	{
		$this -> url = $url;

		return $this; 
	}
	
	/**
	 * Returns the url part by it's index.
	 * @param int $index
	 * @return string|null
	 */
	public function getUrlPart(int $index = 0)
	{
		if(isset($this -> url_parts[$index]))
			return $this -> url_parts[$index];
	}
   
	/**
	 * Defines if we are at the index page of the application.
	 * @return bool
	 */
   	public function isIndex()
   	{
      	return !isset($this -> url_parts[0]);
   	}

	/**
	 * Determines the current route (view file), depending on requested url (url parts).
	 * @return string view file name, or fires the error if the view file was not found
	 */
	public function defineRoute()
	{
		if(Registry :: getInitialVersion() < 3.0)
			return $this -> defineRouteOldVersions();

		//Tries to get routes map from cache, or creates new one
		if(null === $map = Cache :: getRoutesMapFromCache())
		{
			require_once Registry :: get('IncludePath').'config/routes.php';
			$map = $this -> analyzeRoutesList($mvFrontendRoutes) -> createRoutesMap($mvFrontendRoutes);
		}

		$count = $this -> countUrlParts();
		$exact = implode('/', $this -> url_parts);

		//Typical, most popular routes are being searched faster with higher priority
		if($this -> url === '/' || $count === 0)
			$this -> route = $map['Map']['/'];
		else if(array_key_exists($exact, $map['Map']))
			$this -> route = $map['Map'][$exact];
		else if(array_key_exists($exact.'/?', $map['Map']))
			$this -> route = $map['Map'][$exact.'/?'];
		else if($count === 2)
		{
			$key = $this -> url_parts[0].'/*';
			$key_extra = $this -> url_parts[0].'/*/?';
			
			if(array_key_exists($key, $map['Map']))
				$this -> route = $map['Map'][$key];
			else if(array_key_exists($key_extra, $map['Map']))
				$this -> route = $map['Map'][$key_extra];
		}
		else //More complex routes with longer search time
		{
			$parts = $this -> url_parts;

			for($i = $count - 1; $i > 0; $i --)
			{
				$parts[$i] = '*';
				$key = implode('/', $parts);
				$key_extra = $key.'/?';

				if(array_key_exists($key, $map['Map']))
				{
					$this -> route = $map['Map'][$key];
					break;
				}
				else if(array_key_exists($key_extra, $map['Map']))
				{
					$this -> route = $map['Map'][$key_extra];
					break;
				}
			}
		}
		
		//Fallbak route of no matches found
		if(!$this -> route)
			$this -> route = $map['Map']['fallback'];

		//Name of needed file (view) to include in ~/index.php
		$file = Registry :: get('IncludePath').'views/'.$this -> route;
		      
      	if(!file_exists($file))
         	Debug :: displayError("Router: the file of requested view not found ".$file);

		$this -> route_404 = $map['Map']['e404'];
		
      	return $file;
	}
    
	/**
	 * Old version of route search.
	 */
   	public function defineRouteOldVersions()
   	{
		require_once Registry :: get('IncludePath').'config/routes.php';
		
		$this -> route_404 = $mvFrontendRoutes['404'];
		
		if(isset($this -> url_parts[0], $this -> url_parts[1]))
			$long_path = $this -> url_parts[0]."/".$this -> url_parts[1]; //In case of long route format (2 steps)
		else
			$long_path = false;
   	 	
   	 	//Includes required file of view to display site page.
      	if($this -> isIndex()) //Index page of site
          	$this -> route = $mvFrontendRoutes['index'];
      	else if(array_key_exists($this -> url, $mvFrontendRoutes)) //Exact route
          	$this -> route = $mvFrontendRoutes[$this -> url];
        else if($long_path && count($this -> url_parts) == 3 && array_key_exists($long_path."/*/", $mvFrontendRoutes))
        	$this -> route = $mvFrontendRoutes[$long_path."/*/"]; 
        else if($long_path && array_key_exists($long_path."->", $mvFrontendRoutes))
        	$this -> route = $mvFrontendRoutes[$long_path."->"];          	
        else if(count($this -> url_parts) == 2 && array_key_exists($this -> url_parts[0]."/*/", $mvFrontendRoutes))
        	$this -> route = $mvFrontendRoutes[$this -> url_parts[0]."/*/"]; //Special route format 2 parts
        else if(array_key_exists($this -> url_parts[0]."->", $mvFrontendRoutes))
          	$this -> route = $mvFrontendRoutes[$this -> url_parts[0]."->"]; //Special format
     	else //Default route
          	$this -> route = $mvFrontendRoutes['default'];
		
		//Name of needed file (view)
		$file = Registry :: get('IncludePath').'views/'.$this -> route;
      
      	if(!file_exists($file))
         	Debug :: displayError('Router: the file of requested view not found '.$file);
         	
      	return $file; //Name of view file to include
   	}

	/**
	 * Creates cached routes map for faster search.
	 * @param array list of routes from ~/config/routes.php
	 * @return array
	 */
	private function createRoutesMap($mvFrontendRoutes)
	{
		$map = [];

		foreach($mvFrontendRoutes as $route => $view)
		{
			$view = preg_replace('/^\//', '', $view);

			if($route == '/' || $route == 'index')
				$map['/'] = $view;
			else if($route == 'default' || $route == 'fallback')
				$map['fallback'] = $view;
			else if($route == '404' || $route == 'e404')
				$map['e404'] = $view;
			else
			{
				$route = preg_replace('/^\/?(.+[^\/])\/?$/', '$1', $route);
				$map[$route] = $view;
			}
		}

		$file = Registry :: get('IncludePath').'config/routes.php';

		$data = [
			'Build' => Registry :: get('Build') ?? 0,
			'Time' => time(),
			'RoutesFileTime' => filemtime($file),
			'Map' => $map
		];

		Cache :: cleanConfigCacheFilesByKey('routes-map');
		Cache :: saveConfigCacheIntoFile($data, 'routes-map');

		return $data;
	}

	/**
	 * Checks routes array for mistakes.
	 * @param array list of routes from ~/config/routes.php
	 * @return self
	 */
	private function analyzeRoutesList(array $routes)
	{
		$error = null;

		foreach($routes as $route => $view)
		{
			if(strpos($route, '->') !== false)
				$error = ", routes with sign '->' are not allowed in MV version >= 3.0";
			else if(str_replace('/', '', $route) === '?' || str_replace('/', '', $route) === '*')
				$error = ", such routes are the same as 'fallback' or '/' route.";
			else if(substr_count($route, '?') > 1 || preg_match('/\/?\?\/?[^\/]/', $route))
				$error = ", routes may have only one sign '?', and it must be located at the last part of the route.";
			else if(preg_match('/^\/?(\?|\*)/', $route))
				$error = ", routes can not start with signs '?' or '*'.";
			else if(preg_match('/\/\*\/?[^\/\*\?]/', $route))
				$error = ", one or more dynamic parts signs '*' must be located at the end of route only.";
			else if(preg_match('/\/\s*\//', $route) || trim($route) === '' || $route == 0)
				$error = ", such kind of route is not supported.";

			if($error)
				Debug :: displayError("Invalid route '".$route."' in file ~/config/routes.php ".$error);
		}

		return $this;
	}
   
	/**
	 * Checks if the current host is localhost.
	 * @return bool
	 */
   	static public function isLocalHost()
   	{
   	  	return ($_SERVER["REMOTE_ADDR"] == "127.0.0.1" || $_SERVER["REMOTE_ADDR"] == "::1");
   	}

	/**
	 * Checks if the current connection is https.
	 * @return bool
	 */
   	static public function isHttps()
   	{
   		return (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off");
   	}
   	
	/**
	 * Determines if the site is not in the root folder of domain.
	 * @return int number of url parts to cut off
	 */
   	private function defineUrlCut()
   	{
      	$site_path = Registry :: get('MainPath');
      
      	if($site_path != '/')
         	return substr_count($site_path, '/') - 1;
      	else
         	return 0;
   	}
   	
	/**
	 * Looks for the current page in GET data (for pagination object).
	 * @return int number of page
	 */
   	public function defineCurrentPage(mixed $start_key)
   	{
   		if(is_numeric($start_key))
   		{
	   		if(isset($this -> url_parts[$start_key]) && $this -> url_parts[$start_key] == "page" && 
	   		   isset($this -> url_parts[$start_key + 1]) && is_numeric($this -> url_parts[$start_key + 1]))
	   		   return intval($this -> url_parts[$start_key + 1]);
   		}
   		else if(isset($_GET[$start_key]) && $_GET[$start_key])
   			return intval($_GET[$start_key]);

		return 1;
   	}
   	
	/**
	 * Old method...
	 */
   	public function defineSelectParams(int $index, string $url_field = '')
   	{	
   		if(!isset($this -> url_parts[$index]) || $this -> url_parts[$index] === '')
   			return false;
		else if(is_numeric($this -> url_parts[$index]))
			return array('id' => intval($this -> url_parts[$index]));
		else if($url_field)
			return array($url_field => $this -> url_parts[$index]);
		else
			return false;		
   	}
}
