<?php
/**
 * Class for detecting bugs, time measuring and browser detection.
 * Also displays errors during the work code.
 */
class Debug
{	
	/**
	 * Says if we need to display the debug info at the end of the site page.
	 * @var bool
	 */
	private $show_info = false;

	/**
	 * Start time for worktime measuring.
	 * @var int
	 */
	public $time_start;

	/**
	 * End time for worktime measuring.
	 * @var int
	 */
	public $time_stop;
		
	public function __construct($show_info = false)
	{
		//Starts the time measuring if the flag is passed
		if($show_info)
		{
			$this -> show_info = true;
			$this -> timeStart();
		}
	}

	/**
	 * Starts timer to find out the time of code execution.
	 */
	public function timeStart()
	{
		$this -> time_start = gettimeofday();
	}
	
	/**
	 * Stops timer to get the worktime interval.
	 */
	public function timeStop()
	{
		$this -> time_stop = gettimeofday();
	}	
	
	/**
	 * Displays variable in 'pre' tags and prints the value with print_r() function.
	 */
	public static function pre(mixed $var)
	{
		echo "\n<pre>";
		print_r($var);
		echo "</pre>\n";
	}

	/**
	 * Displays variable in 'pre' tags and executes exit() function at the end.
	 */
	public static function exit(mixed $var = null)
	{
		if(ob_get_length())
			ob_end_clean();

		if($var !== null)
			self :: pre($var);

		exit();
	}

	/**
	 * Detects and returns the name of user's browser.
	 * @return string like 'chrome', 'safari', etc.
	 */
	static public function browser()
	{
		if(!isset($_SERVER['HTTP_USER_AGENT']))
			return '';		
		
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		
		if(self :: isMobile())
		{
			if(strpos($agent, 'crios/') !== false)
				return 'chrome';
			if(preg_match('/opt\/\d+/', $agent))
				return 'opera';
			if(strpos($agent, 'fxios/') !== false)
				return 'firefox';
			if(strpos($agent, 'edgios/') !== false)
				return 'edge';
			if(strpos($agent, 'safari') !== false && strpos($agent, 'version/') !== false)
				return 'safari';
		}
		
		if(preg_match('/opr\/\d+/', $agent) || preg_match('/opera/', $agent))
			return 'opera';			
		if(strpos($agent, 'yabrowser') !== false)
			return 'yandex';
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'Edge') !== false)
			return 'edge';
		if(strpos($agent, 'chrome') !== false)
			return 'chrome';
		if(strpos($agent, 'firefox') !== false)
			return 'firefox';
		if(strpos($agent, 'msie') !== false || strpos($agent, 'trident/') !== false)
			return 'ie';
		if(strpos($agent, 'safari') !== false)
			return 'safari';

		return '';
	}
	
	/**
	 * Detects if the browser is a smartphone and returns it's name.
	 * @return string like 'iphone', 'android', etc.
	 */
	static public function isMobile()
	{
		if(!isset($_SERVER['HTTP_USER_AGENT']))
			return '';

		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	
		if(strpos($agent, 'windows phone') !== false)
			return 'windows';
		if(strpos($agent, 'android') !== false && strpos($agent, 'mobile') !== false)
			return 'android';
		if(strpos($agent, 'iphone') !== false)
			return 'iphone';
		if(strpos($agent, 'ipod') !== false)
			return 'ipod';
		if(strpos($agent, 'blackberry') !== false)
			return 'blackberry';
		if(strpos($agent, 'iemobile') !== false)
			return 'ie';

		return '';
	}

	/**
	 * Detects if the browser is a tablet and returns it's name.
	 * @return string like 'android', 'ipad', etc.
	 */
	static public function isTablet()
	{
		if(!isset($_SERVER['HTTP_USER_AGENT']))
			return '';
	
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	
		if(strpos($agent, 'windows') !== false && strpos($agent, 'touch') !== false)
			return 'windows';
		if(strpos($agent, 'ipad') !== false)
			return 'ipad';
		if(strpos($agent, 'android') !== false && strpos($agent, 'mobile') === false)
			return 'android';

		return '';
	}

	/**
	 * Returns time between start and stop.
	 * @return float time inteval value
	 */
	static public function getWorkTime()
	{
		$start = Registry :: get('WorkTimeStart');
		$stop = gettimeofday();
		$time_sec = $stop['sec'] - $start['sec'];
		$time_msec = ($stop['usec'] - $start['usec']) / 1000000;
		
		return $time_sec + $time_msec;
	}
	
	/**
	 * Old deprecated method, kept for compability.
	 */
	public function displayInfo(Router $router) {}
	
	/**
	 * Displays and logs errors occured during runtime, shows 404 in production mode.
	 * @param string $error text of error
	 * @param string $file full path to file
	 * @param int $line line number in file
	 * @param bool $exit says if we must stop the application
	 */
	static public function displayError(string $error, string $file = '', int $line = 0, bool $exit = true)
	{
		if(ob_get_length()) //Top buffer cleaning
			ob_end_clean();

		if(ob_get_length()) //In case of cache enabled
			ob_end_clean();

		if(Registry :: get('Mode') !== 'production')
		{
			$debug_error = $error;

			if($file !== '' && $line !== 0)
				$debug_code = self :: getErrorCodeFragment($file, $line);

			$screen = Registry :: get('IncludeAdminPath').'controls/debug-error.php';

			if(file_exists($screen))
				include($screen);
			else
				echo $error;
		}
		else
		{
			Log :: add($error);
			
			if(!headers_sent())
			{
				if(isset($GLOBALS['mv']))
				{
					if($exit)
						$GLOBALS['mv'] -> display404();
				}
				else if($exit)
					include Registry :: get('IncludeAdminPath').'controls/production-error.php';
			}
		}
		
		Registry :: set('ErrorAlreadyLogged', true);
		
		if($exit)
			exit();
	}

	/**
	 * Creates html code fragment with highlighted code string.
	 * @param string $file absolute file path
	 * @param int $line number of line in file
	 */
	static public function getErrorCodeFragment(string $file, int $line)
	{
		$start = $line > 5 ? $line - 5 : 0;

		$code = file($file);
		$count = 1;

		foreach($code as $number => $string)
			$code[$number] = '<span class="line">'.($count ++).'</span> '.htmlspecialchars($string);

		$code[$line - 1] = '<code>'.$code[$line - 1].'</code>';
		$code = array_slice($code, $start, 9);

		return implode('', $code);
	}
}