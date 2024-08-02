<?php
/**
 * Localization manager class.
 * Works with translations, dates and numbers.
 * Takes source files from /adminpanel/i18n/ and /customs/i18n/ folders.
 */
class I18n
{
	/**
	 * Singleton pattern instance.
	 * @var object self
	 */
	private static $instance;
	
	/**
	 * Current region key (en, am, de, ru).
	 * @var string
	 */
	private static  $region;
	
	/**
	 * Current format of date.
	 * @var string
	 */ 
	private static $date_format;
	
	/** 
	 * Delimeter of date parts (".", "/" or "-").
	 * @var string
	 */
	private static $date_separator;
	
	/**
	 * National rules for numbers translations.
	 * @var array
	 */ 
	private static $plural_rules;
	
	/**
	 * Special translations of letters for non-english languages.
	 * @var array
	 */ 
	private static $translit_rules;
	
	/**
	 * Main translations list for region.
	 * @var array
	 */ 
	private static $translation;
	
	/**
	 * Names of months.
	 * @var array
	 */
	private static $month;
	
	/**
	 * Additional months' names for non-english languages.
	 * @var array
	 */ 
	private static $month_case;
	
	/**
	 * Week days names.
	 * @var array
	 */ 
	private static $week_days;
	
	/**
	 * Decimal mark for float numbers.
	 * @var string
	 */ 
	private static $decimal_mark;
	
	private function __construct() {}
	
	/**
	 * Creates the self singleton object.
	 */
	static public function instance()
	{
		if(!isset(self :: $instance))
			self :: $instance = new self();
			
		return self :: $instance;
	}
	
	/**
	 * Sets the current region and loads translation rules from files.
	 * @param string $region
	 */
	static public function setRegion($region)
	{
		$registry = Registry :: instance();	
		$registry  -> setSetting("AmericanFix", false);
		
		//American locale is the same as UK with only different date format, and it works with 'en' folder.
		if($region == 'am' || $region == 'us')
		{
			$region = "en";
			$registry -> setSetting("Region", "en") -> setSetting("AmericanFix", true);
		}
		else
			$registry -> setSetting("Region", $region);
		
		self :: $region = $region;			
		$region_folder = $registry -> getSetting("IncludeAdminPath")."i18n/".$region."/";
		
		//Main locale settings and translations
		if(is_dir($region_folder) && file_exists($region_folder."locale.php"))
		{
			include $region_folder."locale.php";
			
			if(isset($regionalData, $regionalData['date_format'], $regionalData['translation'], $regionalData['plural_rules']))
			{
				self :: $date_format = $registry  -> getSetting("AmericanFix") ? "mm/dd/yyyy" : $regionalData['date_format'];
				self :: $plural_rules = $regionalData['plural_rules'];
				self :: $translation = $regionalData['translation'];
				self :: $month = $regionalData['month'];
				self :: $month_case = $regionalData['month_case'];
				self :: $week_days = $regionalData['week_days'];
				self :: $decimal_mark = $regionalData['decimal_mark'];
				
				$registry -> setSetting("DecimalMark", $regionalData['decimal_mark']);
				
				self :: defineDateSeparator($regionalData['date_format']);
			}
			
			//Special file for non-english laguages to convert names into Latin
			if(file_exists($region_folder."translit.php"))
			{
				include $region_folder."translit.php";
				
				if(isset($translitRules))
					self :: $translit_rules = $translitRules;
			}
			
			//Additional custom translations if exist 
			$extra = $registry -> getSetting("IncludePath")."customs/i18n/locale-".$region.".php";
			
			if(file_exists($extra))
			{
				include $extra;
				
				if(isset($translations) && count($translations))
					self :: $translation = array_merge(self :: $translation, $translations);
			}
		}
	}
	
	/**
	 * Return current region key.
	 * @return string like en, am, de
	 */
	static public function getRegion()
	{
		return self :: $region;
	}
	
	/**
	 * Defines and saves the date separator from date format string.
	 * @param string $date_format
	 * 
	 */
	static public function defineDateSeparator($date_format)
	{
	    if(strpos($date_format, '.') !== false)
	        self :: $date_separator = '.';
        else if(strpos($date_format, '/') !== false)
            self :: $date_separator = '/';
        else if(strpos($date_format, '-') !== false)
            self :: $date_separator = '-';
	}
	
	/**
	 * Main method to get translation by key.
	 * @param string $key
	 * @return string
	 */
	static public function locale($key)
	{
		//Gets language string for lacalization
		if(isset(self :: $translation[$key]) && self :: $translation[$key] != "")
		{
			$string = self :: $translation[$key];
			$string = preg_replace("/'([^']+)'/", "&laquo;$1&raquo;", $string);
			
			$arguments = func_get_args();
			
			if(isset($arguments[1]) && is_array($arguments[1]))
				foreach($arguments[1] as $pattern => $value)
					if(preg_match("/^\*[a-z-_]+$/", $value) && array_key_exists(str_replace('*', '', $value), $arguments[1]))
					{
						$number = $arguments[1][str_replace('*', '', $value)];
						$defined_type = 'other';
						
						//Implemens plural translation rules for numbers.
						foreach(self :: $plural_rules as $type => $re)
							if(is_numeric($number) && preg_match($re, $number) && isset(self :: $translation[$pattern][$type]))
							{
								$defined_type = $type;
								break;
							}
						
						$string = str_replace('['.$pattern.']', self :: $translation[$pattern][$defined_type], $string);						
					}
					else
						$string = str_replace('{'.$pattern.'}', $value, $string);
			
			return $string;
		}
		else
			return "{".$key."_".self :: $region."}"; //If key not found we show the key + lang prefix
	}
	
	/**
	 * Returns formatted integer number according to current region rules.
	 * @return string
	 */
	static public function formatIntNumber(mixed $value)
	{
		return number_format($value, 0, self :: $decimal_mark, " ");
	}
	
	/**
	 * Returns formatted float number according to current region rules.
	 * @return string
	 */
	static public function formatFloatNumber(mixed $value, int $decimals = 2)
	{		
		return number_format($value, $decimals, self :: $decimal_mark, " ");
	}
	
	/**
	 * Returns week day name for passed date.
	 * @return string
	 */	
	static public function getWeekDayName(string $date)
	{
		$day_of_week = date("w", strtotime($date));
		$day_of_week = $day_of_week ? $day_of_week - 1 : 6;
		
		if(isset(self :: $week_days[$day_of_week]))
			return self :: $week_days[$day_of_week];

		return '';
	}
	
	/**
	 * Checks if passed date has a correct format.
	 * @return mixed
	 */
	static public function checkDateFormat(string $date)
	{
		$re = "/^".str_replace(["d","m","y",".","/"], ["\d","\d","\d","\.","\/"], self :: $date_format);
		
		$arguments = func_get_args();
		$re .= (isset($arguments[1]) && $arguments[1] == "with-time") ? "(\s\d\d:\d\d(:\d\d)?)$/" : "$/";
		
		return preg_match($re, $date);
	}
	
	/**
	 * Transforms date from current regional format to SQL format.
	 * @return string
	 */	
	static public function dateForSQL(string $date)
	{
		if(!preg_match("/^\d{2,4}(\.|-|\/)\d{2,4}(\.|-|\/)\d{2,4}(\s\d{2}:\d{2}(:\d{2})?)?$/", $date))
			return '';
		
		if(preg_match('/\s\d\d:\d\d(:\d\d)?$/', $date))
		{
			$parts = explode(' ', $date);
			$date_parts = explode(self :: $date_separator, $parts[0]);
			$time = $parts[1];
		}
		else
			$date_parts = explode(self :: $date_separator, $date);
			
		if(count($date_parts) != 3)
			return '';
			
		$positions = array_flip(explode(self :: $date_separator, self :: $date_format));
		
		$result = $date_parts[$positions['yyyy']];
		$result .= '-'.$date_parts[$positions['mm']];
		$result .= '-'.$date_parts[$positions['dd']];
		$result .= isset($time) ? ' '.$time : '';
		
		return $result;
	}
	
	/**
	 * Transforms date from SQL format to current regional format.
	 * @return string
	 */	
	static public function dateFromSQL(string $date)
	{
		if(!preg_match("/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2})?(:\d{2})?$/", $date) || 
			preg_match("/^0{4}-0{2}-0{2}(\s0{2}:0{2})?(:0{2})?$/", $date))
			return '';
		
		$parts = explode(' ', $date);
		$parts[0] = explode('-', $parts[0]);
		$date_parts = array('yyyy' => $parts[0][0], 'mm' => $parts[0][1], 'dd' => $parts[0][2]);
		
		$positions = explode(self :: $date_separator, self :: $date_format);
		
		$result = [];
		
		foreach($positions as $key)
			$result[] = $date_parts[$key];
		
		$result = implode(self :: $date_separator, $result);
		
		if(isset($parts[1]))
		{
			$time_parts = explode(":", $parts[1]);

			$arguments = func_get_args();
			
			if(isset($arguments[1]))
				if($arguments[1] == "no-seconds")
					unset($time_parts[2]);
				else if($arguments[1] == "only-date")
					$time_parts = [];
			
			if(count($time_parts))
				$result .= " ".implode(':', $time_parts);
		}
			
		return $result;
	}
	
	/**
	 * Transforms date from SQL format to passed format or current regional format.
	 * @return string
	 */	
	static public function formatDate(string $date, string $format = '')
	{		
		if($format === '' || $format === 'no-seconds' || $format === 'only-date')
			return  self :: dateFromSQL($date, $format);
		else
			return date($format, self :: dateToTimestamp($date));
	}

	/**
	 * Sets current date format value.
	 */
	static public function setDateFormat(string $format)
	{
	    if(preg_match("/^(d|m|y){2,4}(\.|\/)(d|m|y){2,4}(\.|\/)(d|m|y){2,4}$/", $format))
	    {
	       self :: $date_format = $format;
	       self :: defineDateSeparator($format);
	    }
	}
	
	/**
	 * Returns current decimal mark.
	 * @return string
	 */
	static public function getDecimalMark()
	{
		return self :: $decimal_mark;
	}
	
	/**
	 * Returns current date format.
	 * @return string
	 */
	static public function getDateFormat()
	{
		return self :: $date_format;
	}
	
	/**
	 * Returns current date and time format.
	 * @return string
	 */
	static public function getDateTimeFormat()
	{
		return self :: $date_format." hh:mi";
	}
	
	/**
	 * Returns current date value, according to timezone.
	 * @return string
	 */
	static public function getCurrentDate()
	{
		$arguments = func_get_args();
		$date = date("Y-m-d");
		
		return (isset($arguments[0]) && $arguments[0] == "SQL") ? $date : self :: dateFromSQL($date);
	}
	
	/**
	 * Returns current date and time value, according to timezone.
	 * @return string
	 */
	static public function getCurrentDateTime()
	{
		$arguments = func_get_args();
		$date = date("Y-m-d H:i:s");
		
		return (isset($arguments[0]) && $arguments[0] == "SQL") ? $date : self :: dateFromSQL($date);
	}
	
	/**
	 * Converts timestamp value to formatted date value.
	 * @return string
	 */
	static public function timestampToDate($timestamp)
	{
		return self :: dateFromSQL(date("Y-m-d H:i:s", $timestamp));	
	}
	
	/**
	 * Converts date value to timestamp.
	 * @return int
	 */
	static public function dateToTimestamp($date)
	{
		if(!preg_match("/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2})?(:\d{2})?$/", $date))
			return 0;
		
		$date = explode(" ", $date);
		$day = explode("-", $date[0]);
		$time = isset($date[1]) ? explode(":", $date[1]) : array(0, 0, 0);
		
		return mktime(intval($time[0]), intval($time[1]), intval($time[2]), 
					  intval($day[1]), intval($day[2]), intval($day[0]));
	}
	
	/**
	 * Converts the size of file from bites to KB or MB.
	 * @return float
	 */
	static public function convertFileSize($size)
	{
		if($size >= 1048576)
			return round($size / 1048576, 1)." ".I18n :: locale("size-mb");
			
		if($size >= 1024)
			return round($size / 1024)." ".I18n :: locale("size-kb");
		else
			return round($size / 1024, 2)." ".I18n :: locale("size-kb");
	}
	
	/**
	 * Returns translated name of passed month.
	 * @return string
	 */
	static public function getMonth($number)
	{
		return self :: $month[$number - 1] ?? '';
	}
	
	/**
	 * Returns translated name of passed month according to language rules.
	 * @return string
	 */
	static public function getMonthCase($number)
	{
		return self :: $month_case[$number - 1] ?? '';
	}
	
	/**
	 * Transforms passed string to URL, containing only latin letters and '-' symbol.
	 * @return string
	 */
	static public function translitUrl(string $string)
	{
		$url = mb_strtolower($string, "utf-8");
		
		if(self :: $translit_rules && count(self :: $translit_rules))
			$url = strtr($url, self :: $translit_rules);
		else
			$url = str_replace(" ", "-", $url);
		
		$url = htmlspecialchars_decode($url, ENT_QUOTES);
		$url = str_replace("_", "-", $url);
		$url = preg_replace("/[^a-z0-9-]+/ui", "", $url);		
		$url = preg_replace("/-+/", "-", $url);
		$url = preg_replace("/^-?(.*[^-]+)-?$/", "$1", $url);
			 
		return ($url == "-") ? "" : $url;		
	}
	
	/**
	 * Returns array of future options to select the region.
	 * @return array
	 */
	static public function getRegionsOptions()
	{
		$registry = Registry :: instance();
		$values = [];
		$regions = $registry -> getSetting('SupportedRegions');
		
		if(!is_array($regions) || !count($regions))
		{
			$region = $registry -> getSetting('Region');
			$regions = ($region == "en" && $registry -> getSetting('AmericanFix')) ? ['am'] : [$region];
		}
		
		foreach($regions as $region)
		{
			if($region == 'am' || $region == 'us')
			{
				$values[$region] = "English (US)";
				continue;
			}
			
			$path = $registry -> getSetting('IncludeAdminPath')."i18n/".$region."/locale.php";
			
			if(!is_file($path))
				continue;
				
			include $path;
			
			$values[$region] = $regionalData["caption"];
		}
		
		return $values;
	}
	
	/**
	 * Returns html select options with available regions.
	 * @return string html options
	 */
	static public function displayRegionsSelect(string $active = '')
	{
		$html = "";
		
		foreach(self :: getRegionsOptions() as $key => $caption)
		{
			$selected = ($active == $key) ? ' selected="selected"' : "";
			$html .= "<option value=\"".$key."\"".$selected.">".$caption."</option>\n";
		}
		
		return $html;
	}
	
	/**
	 * Checks if passed region is available.
	 * @return bool
	 */
	static public function checkRegion(string $region)
	{
		$registry = Registry :: instance();
		$regions = $registry -> getSetting('SupportedRegions');
		$regions = (is_array($regions) && count($regions)) ? $regions : array($registry -> getSetting('Region'));
		
		return in_array($region, $regions);
	}

	/**
	 * Creates cookie key to store region value.
	 * @return string
	 */
	static public function createRegionCookieKey()
	{
		$code = substr(Registry :: get('SecretCode'), 5, 5).Debug :: browser();
		$code .= Registry :: get('DomainName').Registry :: get('AdminFolder');

		return 'region_'.substr(md5($code), 0, 8);
	}
	
	/**
	 * Defines current region by config setting or cookies.
	 * @return string region key
	 */
	static public function defineRegion()
	{
		$key = self :: createRegionCookieKey();

		if(isset($_COOKIE[$key]) && self :: checkRegion((string) $_COOKIE[$key]))
			return (string) $_COOKIE[$key];
		else
		{
			$region = Registry :: get('Region');

			return ($region == 'en' && Registry :: get('AmericanFix')) ? 'am' : $region;
		}
	}
	
	/**
	 * Saves current selected region of admin panel into special cookie.
	 */
	static public function saveRegion(string $region)
	{		
		if(!self :: checkRegion($region))
			return;

		$key = self :: createRegionCookieKey();
		$time = time() + 3600 * 24 * 365;

		Http :: setCookie($key, $region, ['expires' => $time, 'path' => Registry :: get('AdminPanelPath')]);
	}
}
