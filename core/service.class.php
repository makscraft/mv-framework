<?php
/**
 * Static methods, helpers to use anywhere inside the project.
 */
class Service
{
	/**
	 * Adds 'IncludePath' setting (file root to project folder) to path.
	 * @return string
	 */
	static public function addFileRoot(string $path = '')
	{
		return Registry :: get("IncludePath").$path;
	}

	/**
	 * Removes 'IncludePath' string from path.
	 * Makes file suitable for html tag, which should not have the root server part of url.
	 * @return string
	 */
	static public function removeFileRoot(string $path = '')
	{
		$root = str_replace("/", "\/", Registry :: get("IncludePath"));
		return preg_replace("/^".$root."/", "", $path);
	}
	
	/**
	 * Removes 'DocumentRoot' string from path.
	 * Makes file suitable for html tag, which should not have the root server part of url.
	 * @return string
	 */
	static public function removeDocumentRoot(string $path = '')
	{
		$root = str_replace("/", "\/", Registry :: get("DocumentRoot"));
		return preg_replace("/^".$root."/", "", $path);
	}
	
	/**
	 * Removes document root and adds application base url (including folder if needed).
	 * @return string
	 */
	static public function addRootPath(string $path = '')
	{
		return Registry :: get("MainPath").self :: removeFileRoot($path);
	}

	/**
	 * Removes application folder from file's path.
	 * @return string
	 */
	static public function removeRootPath(string $path = '')
	{
		$root = str_replace("/", "\/", Registry :: get("MainPath"));
		return preg_replace("/^".$root."/", "", $path);
	}
	
	/**
	 * Adds domain name and application folder to file's path (if needed).
	 * @return string
	 */
	static public function setFullHttpPath(string $path = '')
	{
		$domain = Registry :: get("DomainName");
		return preg_replace("/\/$/", "", $domain).self :: addRootPath($path);
	}
	
	/**
	 * Returns the UNIX rights for the file (like 0774).
	 * @return string
	 */
	static public function getPermissions(string $file)
    {
	   $permissions = decoct(fileperms($file));
	   return substr($permissions, strlen($permissions) - 3, strlen($permissions));
    }
    /**
	 * Returns the extention of the file.
	 * @return string
	 */
	static public function getExtension(string $file)
	{	
		return strtolower(substr($file, strrpos($file, '.') + 1));
	}

	/**
	 * Returns the file name wihout extension.
	 * @return string
	 */
	static public function removeExtension(string $file)
	{
		return substr($file, 0, strrpos($file, '.'));
	}
	
	/**
	 * Translates name of the file to get ready for saving on HDD.
	 * @return string
	 */
	static public function translateFileName(string $file_name)
	{
		$file_name = I18n :: translitUrl(self :: removeExtension(trim($file_name)));
		
		if(!$file_name)
			return '';

		//Clean transformed name of file
		$file_name = str_replace("_", "-", $file_name);
		$file_name = preg_replace("/[^a-z0-9-]/ui", "", $file_name);		
		$file_name = preg_replace("/-+/", "-", $file_name);
		$file_name = preg_replace("/^-+/", "", $file_name);
		$file_name = preg_replace("/-+$/", "", $file_name);
		
		return $file_name;
	}

	/**
	 * Checks and prepares the name of the file to for saving on HDD.
	 * @return string
	 */
	static public function prepareFilePath(string $file_path)
	{
		$file_dir = dirname($file_path)."/";
		$extension = self :: getExtension(basename($file_path));
		$extension = ($extension == "jpeg") ? ".jpg" : ".".$extension;
		$file_name = self :: translateFileName(basename($file_path));
		
		if(!$file_name || file_exists($file_dir.$file_name.$extension))
		{
			$registry = Registry :: instance();
			$counter = intval($registry -> getDatabaseSetting('files_counter')) + 1;
			$registry -> setDatabaseSetting('files_counter', $counter);

			if(!$file_name) //File name is empty after transformation
				return $file_dir."f".$counter.$extension;
			else //File with such name was already uploaded
				return $file_dir.$file_name."-f".$counter.$extension;
		}
		else //File name has being transformed successfully
			return $file_dir.$file_name.$extension;
	}
	
	/**
	 * Returns random string, containing only numbers and lowercased letters.
	 * @param int $length needed length of result string
	 * @return string
	 */
	static public function randomString(int $length)
	{
		//Available symbols
		$chars = array('a','b','c','d','e','f','g','h','i','j','k','l','m',
					   'n','p','r','t','u','v','w','x','y','z',1,2,3,4,6,7,8,9);
		
		$arguments = func_get_args();
		
		if(isset($arguments[1]) && $arguments[1] == "only-letters")
			array_splice($chars, -8); //Only letters go into random array
		
		$number = count($chars) - 1;		
		$string = ""; //Result string

		for($i = 0; $i < $length; $i ++)
			$string .= $chars[mt_rand(0, $number)]; //Adds next random symbol
		
		return $string; 		
	}
	
	/**
	 * Returns random string, containing numbers and letters.
	 * @param int $length needed length of result string
	 * @return string
	 */
	static public function strongRandomString(int $length)
	{
		$chars = array('a','b','c','d','e','f','g','h','i','j','k','l','m',
					   'n','o','p','q','r','s','t','u','v','w','x','y','z',
					   'A','B','C','D','E','F','G','H','I','J','K','L','M',
					   'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
					   0,1,2,3,4,6,7,8,9);
		
		$number = count($chars) - 1;
		$string = '';
			
		for($i = 0; $i < $length; $i ++)
			$string .= $chars[mt_rand(0, $number)];
				
		return $string;
	}

	/**
	 * Encodes string into base64.
	 * @param string $value
	 * @return string
	 */
	static public function encodeBase64(string $value)
	{
		return base64_encode(trim($value));
	}

	/**
	 * Encodes string into base64.
	 * @param string $value
	 * @return string
	 */
	static public function decodeBase64(string $value)
	{
		return trim(base64_decode(trim($value)));
	}
	
	/**
	 * Serialization (packing) of array into base64 string (legacy method).
	 * @return string
	 */
	static public function serializeArray(array $arr)
	{
		$mass = [];
		
		foreach($arr as $key => $val)
			$mass[$key] = htmlspecialchars($val ?? '');

		return base64_encode(serialize($mass));
	}

	/**
	 * Unpacking of base64 string into array (legacy method).
	 * @return array
	 */
	static public function unserializeArray(string $var)
	{
		$mass = unserialize(base64_decode($var));
		
		if(!is_array($mass))
			return [];
		
		foreach($mass as $key => $val)
			$mass[$key] = htmlspecialchars_decode($val);
				
		return $mass;
	}	
	
	/**
	 * Rounds given number to 0.5
	 * @return float
	 */
	static public function roundTo05(mixed $number)
	{
		$int = floor($number); //Integer part
		$float = $number - $int; //Float part
		
		if($float < 0.25) //Rounding
			$float = 0;
		else if($float >= 0.25 && $float < 0.75)
			$float = 0.5;
		else if($float >= 0.75)
			$float = 1;
		
		//Final number with int and float parts
		return $int + $float;
	}
	
	/**
	 * Gets rid of html tags and cuts the tail of the text to fixed number of symbols according to words.
	 * @param string $text initial string (text)
	 * @param int $length required length of result
	 * @param string $end end of result if the text has being cut (optional), example '...'
	 * @return string
	 */
	static public function cutText(string $text, int $length, string $end = '')
	{
		$text = strip_tags($text);
		
		if($text === "")
			return "&nbsp;"; //Empty string after tags deleting

		if(mb_strlen($text, 'utf-8') <= $length) //If the text fit to needed length
			return $text;
		
		$text = mb_substr($text, 0, $length, 'utf-8'); //Cut of text
		
		 //Cut the possible part of last word
		return mb_substr($text, 0, mb_strrpos($text, ' ', 0, 'utf-8'), 'utf-8').$end;
	}

	/**
	 * Cleans double htmlspecialchars() function implementaion result.
	 * @return string
	 */
	static public function cleanHtmlSpecialChars(string $string = '')
	{
		//To avoid double effect from htmlspecialchars() in setValue() methods
		$search = ["&amp;amp;","&amp;quot;","&amp;#039;","&amp;gt;","&amp;lt;","&amp;#92;"];
		$replace = ["&amp;","&quot;","&#039;","&gt;","&lt;","&#92;"];

		return str_replace($search, $replace, $string);
	}
	
	/**
	 * Creates html table with checkbox / radio inputs from given data.
	 * @return string
	 */
	static public function displayOrderedFormTable(array $data, int $columns, mixed $checked, string $name)
	{
		$columns_number = intval($columns);
		$rows_number = ceil(count($data) / $columns_number);
		$arguments = func_get_args();
		$radio_buttons = (isset($arguments[4]) && $arguments[4] == "radio");
		$current_row = $current_column = 1;
		$table_data = [];
				
		foreach($data as $key => $title)
		{
			if($current_row > $rows_number)
			{
				$current_row = 1;
				$current_column ++;
			}
			
			$table_data[$current_row][$current_column] = array($key, $title);
			$current_row ++;
		}
		
		$css_class = $radio_buttons ? "enum-radio-choice" : "enum-multiple-choice";
		$html = "<table id=\"".$name."-ordered-table\" class=\"".$css_class."\">\n";
		
		for($i = 1; $i <= $rows_number; $i ++)
		{
			$html .= "<tr>\n";
			
			for($j = 1; $j <= $columns_number; $j ++)
				if(isset($table_data[$i][$j]))
				{
					$html .= "<td>\n<input id=\"".$name."-".$table_data[$i][$j][0]."\" ";
					$html .= "type=\"".($radio_buttons ? "radio" : "checkbox")."\" ";
					$html .= "name=\"".$name.($radio_buttons ? "" : "-".$table_data[$i][$j][0])."\"";
					
					if($radio_buttons && !is_array($checked) && $checked == $table_data[$i][$j][0])
						$html .= " checked=\"checked\"";
					else if(!$radio_buttons && in_array($table_data[$i][$j][0], $checked))
						$html .= " checked=\"checked\"";
						
					$html .= " value=\"".$table_data[$i][$j][0]."\" />\n";
					$html .= "<label for=\"".$name."-".$table_data[$i][$j][0]."\">".$table_data[$i][$j][1]."</label></td>\n";					
				}
			
			$html .= "</tr>\n";
		}
		
		return $html."</table>\n";
	}

	/**
	 * Escapes with '/' found regexp characters in string.
	 * @return string
	 */
	static public function prepareRegularExpression(string $string)
	{
		$search = array("+", ".", "*", "?", "(", ")", "^", "$", "[", "]", "/", "{", "}");
		$replace = array("\+", "\.", "\*", "\?", "\(", "\)", "\^", "\\$", "\[", "\]", "\/", "\{", "\}");
		
		return str_replace($search, $replace, $string);
	}
	
	/**
	 * Checks if PHP session is already started.
	 * @return bool
	 */
	static public function sessionIsStarted()
	{
		if(version_compare(phpversion(), '5.4.0', '>='))			
			return session_status() === PHP_SESSION_ACTIVE;
		else
			return session_id() === '' ? false : true;
	}

	/**
	 * Creates hash from given string.
	 * @return string
	 */
	static public function makeHash(string $string, int $cost = 10)
	{
		if(Registry :: instance() -> getInitialVersion() < 2.2)
			return md5($string);
		
		$options = array("cost" => $cost);
		return password_hash($string, PASSWORD_DEFAULT, $options);
	}
	
	/**
	 * Compares hash value with given string.
	 * @return string
	 */
	static public function checkHash(string $string, string $hash)
	{
		if(Registry :: instance() -> getInitialVersion() < 2.2)
			return (md5($string) == $hash);
		
		return password_verify($string, $hash);
	}

	/**
	 * Creates hash by algorithm from given string.
	 * @param string $string initial string
	 * @param string $algo optional param of algorithm name (can be 'random')
	 * @return string
	 */
	static public function createHash(string $string, string $algo = '')
	{
		$allowed = ["sha224", "sha256", "sha384", "sha512/224", "sha512/256", "sha512", "sha3-224", "sha3-256", "sha3-384",
					"sha3-512", "ripemd160", "ripemd256", "ripemd320", "whirlpool", "tiger160,3", "tiger192,3",
					"tiger160,4", "tiger192,4", "snefru", "snefru256", "gost", "gost-crypto", "haval160,3", "haval192,3", 
					"haval224,3", "haval256,3", "haval160,4", "haval192,4", "haval224,4", "haval256,4", "haval160,5", 
					"haval192,5", "haval224,5", "haval256,5"];

		$system_algos = hash_algos();

		if($algo !== '' && in_array($algo, $allowed) && in_array($algo, $system_algos))
			return hash($algo, $string);

		$code = Registry :: get("SecretCode");
		$code = (int) preg_replace("/\D/", "", $code);

		if($algo == "random" && $code)
		{
			$index = (int) $code % count($allowed);

			if(isset($allowed[$index]) && in_array($allowed[$index], $system_algos))
				return hash($allowed[$index], $string);
			else
				return hash("sha256", $string);
		}

		return hash("sha256", $string);
	}

	/**
	 * Adds modification times for array of files.
	 * @param array $files array of files pathes
	 * @return array keys - files' names, values - modification times
	 */
	static public function addFilesWithModificationTimes(array $files)
    {
        $times = [];

        foreach($files as $file)
            $times[$file] = filemtime($file);

        return $times;
    }

	/**
	 * Creates md5 hash with modification times for array of files.
	 * @param array $files array of files pathes
	 * @return string md5 hash value
	 */
	static public function getFilesModificationTimesHash(array $files)
	{
		$hash = '';

		foreach($files as $file)
			$hash .= filemtime($file);

		return md5($hash);
	}

	/**
	 * Returns hash value, containing files pathes and their modification times.
	 * @param array $files array of files pathes
	 * @return string md5() hash string
	 */
    static public function createFilesModificationTimesHash(array $files)
    {
        $hash = [];
        $files = self :: addFilesWithModificationTimes($files);

        foreach($files as $file => $time)
            $hash[] = md5($file.$time);
        
        return md5(implode('', $hash));
    }

	/**
	 * Mixes digits on the number with random characters (not numbers).
	 * @param int $number initial number to mix
	 * @param int $length final approximate string length
	 * @return string
	 */
	static public function mixNumberWithLetters(int $number, int $lenght)
	{
		$random = self :: strongRandomString($lenght);
		$signs = str_split(preg_replace('/\d/', '*', $random));
		$number = str_split(strval($number));

		foreach($signs as $index => $sign)
			if($sign == '*')
				if(null !== $one = array_shift($number))
					$signs[$index] = $one;
				else
					break;

		return str_replace('*', '', implode('', $signs)).implode('', $number);
	}
}
