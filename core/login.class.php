<?php
/**
 * Class for authorization of users in admin panel.
 * Also contains methods for auto login and recovering admin's password.
 * Creates CSRF token for login form and recover password form.
 */
class Login
{
	/**
	 * Table with admins accounts
	 * @var string
	 */
	private const TABLE = 'users';
	
	/**
	 * Settings manager singleton object
	 * @var object Registry
	 */
	private $registry;
	
	/**
	 * Database manager singleton object
	 * @var object Database
	 */
	private $db;
	
	/**
	 * Internationlization manager object
	 * @var object I18n
	 */
	private $i18n;

	/**
	 * Login attemps befor showing captcha
	 */
	public const ATTEMPTS_NUMBER = 3;

	public function __construct()
	{
		$this -> registry = Registry :: instance();
		$this -> db = DataBase :: instance();
		$this -> i18n = I18n :: instance();
		
		$time_zone = $this -> registry -> getSetting('TimeZone');
		
		if($time_zone)
			date_default_timezone_set($time_zone);

		if(!Service :: sessionIsStarted())
			session_start();

		if(!isset($_SESSION['login']['token']))
		{
			$_SESSION['login']['token'] = Service :: strongRandomString(50);
			$_SESSION['login']['ip'] = $_SERVER["REMOTE_ADDR"];
			$_SESSION['login']['browser'] = Debug :: browser();
		}
		
		unset($_SESSION['mv']); //Deletes old admin session data
	}
	
	static public function getTokenCSRF()
	{
		$string = $_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"].$_SESSION["login"]["token"];

		return Service :: createHash($string, "random");
	}

	static public function createHashKey()
	{
		$key = Registry :: get('DomainName').Debug :: browser().Registry :: get('AdminFolder');
		$key = substr(Registry :: get('SecretCode'), 0, 15);

		return substr(md5($key), 10, 10);
	}

	static public function getAjaxInitialToken()
	{
		$string = Debug :: browser().$_SESSION["login"]["token"].$_SERVER["HTTP_USER_AGENT"];

		return Service :: createHash($string, "random");
	}

	static public function getJavaScriptToken()
	{
		$string = self :: getTokenCSRF().self :: getAjaxInitialToken();
		$string .= $_SESSION['login']['token'];

		return preg_replace("/\D/", "", $string);
	}	
	
	static public function getLogoutToken()
	{
		$string = $_SERVER["REMOTE_ADDR"].session_id().$_SERVER["HTTP_USER_AGENT"];

	    return Service :: createHash($string, "sha224");
	}

	static public function getAutoLoginCookieName()
	{
		return 'auth_'.self :: createHashKey();
	}

	public function getAutoLoginParams(int $id)
	{
		$secret = Registry :: get('SecretCode');
		$number = array_sum(str_split(preg_replace('/\D/', '', md5($secret))));
		$user = $this -> db -> getRow("SELECT * FROM `".self :: TABLE."` WHERE `id`='".$id."'");
		
		$first = Service :: mixNumberWithLetters($id + $number, 100);

		$second = $user["email"].$user["id"].$user["login"].$user["password"].$secret.Debug :: browser();
		$second = password_hash($second, PASSWORD_DEFAULT, ["cost" => 12]);
	    $second = str_replace('$2y$12$', '', $second);

		$separator = str_split(preg_replace('/[\W\d]/', '', $secret))[0] ?? 'a';
		
		return [
			'key' => $this -> getAutoLoginCookieName(),
			'token' => str_replace($separator, '', $first).$separator.$second
		];
	}

	public function unpackAutoLoginParams(string $token)
	{
		$secret = Registry :: get('SecretCode');
		$number = array_sum(str_split(preg_replace('/\D/', '', md5($secret))));
		$separator = str_split(preg_replace('/[\W\d]/', '', $secret))[0] ?? 'a';

		$parts = preg_split('/'.$separator.'/', $token, 2);
		$id = intval(preg_replace('/\D/', '', $parts[0])) - $number;

		if($id <= 0 || count($parts) !== 2 || $parts[1] == '')
			return false;

		return ['id' => $id, 'token' => '$2y$12$'.$parts[1]];		
	}
	
	public function loginUser(string $login, string $password)
	{
		if(!isset($_SERVER["HTTP_USER_AGENT"]))
			return false;
		
		$row = $this -> db -> getRow("SELECT * FROM `".self :: TABLE."` 
							  	      WHERE `login`=".$this -> db -> secure($login));
		
		$arguments = func_get_args();
		$autologin = (isset($arguments[2]) && $arguments[2] == "autologin");
		
		//Compares the data came from user and status of user. If the user in blocked we don't let in
		if($row && $row['login'] == $login && ($row['active'] || $row['id'] == 1) && 
		  (Service :: checkHash($password, $row['password']) || ($autologin && $row['password'] == $password)))
		{
			$_SESSION['mv']['user']['id'] = $row['id'];
			$_SESSION['mv']['user']['password'] = md5($row['password']);
			$_SESSION['mv']['user']['token'] = Service :: strongRandomString(50);
			
			$data = "`date_last_visit`=".$this -> db -> now('with-seconds');
			
			if(!$row["date_registered"] || $row["date_registered"] == "0000-00-00 00:00:00")
				$data .= ", `date_registered`=".$this -> db -> now('with-seconds');
			
			//Updates the last visit of user
			$this -> db -> query("UPDATE `".self :: TABLE."` SET ".$data." WHERE `id`='".$row['id']."'");
						
			$session = new UserSession($row['id']); //Start new session for this user
			$session -> startSession();
			
			return $row['id'];
		}
		else
			return false;
	}
	
	public function reload(string $path = '')
	{
		header("Location: ".$this -> registry -> getSetting("AdminPanelPath").$path);
		exit();		
	}
	
	public function sendUserPassword(array $user_data)
	{		
		$code = Service :: strongRandomString(30); //Code to confirm the changes from email
		$key = Service :: strongRandomString(32);
		$token = Service :: makeHash($user_data['email'].$user_data['id'].$key);
		$token = str_replace("$2y$10$", "", $token);
		
		$this -> addPasswordToConfirm($user_data['id'], $key, $code);
		
		//Link for confirmation
		$link = $this -> registry -> getSetting("HttpAdminPanelPath")."login/recover.php?code=".$code;
		$link .= "&token=".$token;
		
		$time = floor($this -> registry -> getSetting("NewPasswordLifeTime") / 3600);
   		$arguments = array("number" => $time, "in-hour" => "*number");
   		
   		//Message text
		$message = "<p>".$user_data['name'].",<br />\n";
		$message .= $this -> i18n -> locale("change-password")."</p>\n";
		$message .= "<p>".$this -> i18n -> locale("confirm-time", $arguments)."</p>\n";
   		$message .= "<p><a href=\"".$link."\">".$link."</a></p>\n";
   		
   		$subject = $this -> i18n -> locale("password-restore");
		
		return Email :: send($user_data['name']." <".$user_data['email'].">", $subject, $message);
	}
	
	public function displayLoginErrors(mixed $errors)
	{	
		if(!is_array($errors) || !count($errors))
			return '';
		
		$html = '';
		
		foreach($errors as $error)
			$html .= "<p>".$error."</p>\n";
			
		return "<div class=\"errors\">".$html."</div>\n";
	}
	
	public function checkUserEmail(string $email)
	{	
		if($this -> db -> getCount(self :: TABLE, "`email`=".$this -> db -> secure($email)) == 1)
			return $this -> db -> getRow("SELECT * FROM `".self :: TABLE."` 
										  WHERE `email`=".$this -> db -> secure($email)." 
										  AND `active`='1'");
		return false;
	}
	
	public function addPasswordToConfirm(int $user_id, string $key, string $code)
	{
		//Adds new password into DB wait list to confirm from email.
		
		$table = 'users_passwords'; //Table with passwords for confirmation
		$time = $this -> registry -> getSetting("NewPasswordLifeTime");
		
		$this -> db -> query("DELETE FROM `".$table."` WHERE (".$this -> db -> unixTimeStamp('now')."-
							  ".$this -> db -> unixTimeStamp('date').") > ".$time." 
							  OR `user_id`='".$user_id."'"); //Deletes old not valid passwords from list
		
		//Adds new password to wait for the confirmation
		$this -> db -> query("INSERT INTO `".$table."`(`user_id`,`date`,`password`,`code`)
		                      VALUES('".$user_id."', ".$this -> db -> now().",'".$key."', '".$code."')");		
	}
	
	public function checkNewPasswordParams(string $code, string $token)
	{
		$table = "users_passwords"; //Table with passwords for confirmation
		$time = $this -> registry -> getSetting("NewPasswordLifeTime");

		 //Checks if the password exist according to special code and it has valid time
		$row = $this -> db -> getRow("SELECT * FROM `".$table."`
		                              WHERE (".$this -> db -> unixTimeStamp('now')."-".
									  $this -> db -> unixTimeStamp('date').") < ".$time." 
									  AND `code`=".$this -> db -> secure($code));
		
		if($row && isset($row['user_id']))
		{
			if($user = $this -> db -> getRow("SELECT * FROM `".self :: TABLE."` WHERE `id`='".$row['user_id']."'"))
			{
				$string = $user['email'].$user['id'].$row['password'];
				
				if($this -> registry -> getInitialVersion() >= 2.2)
				    $token = "$2y$10$".$token;
				
				if(Service :: checkHash($string, $token))
					$_SESSION['login']['change-password'] = $row['user_id'];
			}			
			
			//Deletes data from list
			$this -> db -> query("DELETE FROM `".$table."` WHERE `user_id`='".$row['user_id']."'");
			
			return true;
		}
		
		return false;
	}
	
	public function saveNewPassword(int $user_id, string $new_password)
	{
		$this -> db -> query("UPDATE `".self :: TABLE."` 
							  SET `password`='".Service :: makeHash($new_password)."'
							  WHERE `id`='".$user_id."'");
	}

	public function addNewLoginAttempt(string $login)
	{
		$login = $this -> db -> secure($login);
		
		$this -> db -> query("INSERT INTO users_logins (`login`,`date`,`ip_address`,`user_agent`) 
		                      VALUES(".$login.",".$this -> db -> now().",'".ip2long($_SERVER['REMOTE_ADDR'])."',
							  '".md5($_SERVER['HTTP_USER_AGENT'])."')");
	}

	public function checkAllAttemptsFromIp()
	{
		$time = (int) $this -> registry -> getSetting("LoginCaptchaLifeTime");
		$time = $time ? $time : 3600;
		$table = "users_logins";
		
		//Deletes all old data form table after required period of time
		$this -> db -> query("DELETE FROM `".$table."` 
							  WHERE (".$this -> db -> unixTimeStamp('now')."-".
							  $this -> db -> unixTimeStamp('date').") > ".$time);
							   
		//Checks all attempts to login from current ip address
		return $this -> db -> getCount("users_logins", "`ip_address`='".ip2long($_SERVER['REMOTE_ADDR'])."' 
										AND ((".$this -> db -> unixTimeStamp('now')."-".
							   			$this -> db -> unixTimeStamp('date').") < ".$time.")");
	}
	
	public function rememberUser(int $id)
	{
		$params = $this -> getAutoLoginParams($id);    
    
		$time = Registry :: get('AutoLoginLifeTime');
		$time = $time ? time() + $time : time() + 3600 * 24 * 31;
				
		$options = ['expires' => $time, 'path' => Registry :: get('AdminPanelPath')];

		Http :: setCookie($params['key'], $params['token'], $options);
	}
	
	public function cancelRemember()
	{
		$cookie = $this -> getAutoLoginCookieName();

		$time = Registry :: get('AutoLoginLifeTime');
		$time = $time ? time() + $time : time() + 3600 * 24 * 31;

		$options = ['expires' => $time, 'path' => Registry :: get('AdminPanelPath')];
		Http :: setCookie($cookie, '', $options);

		return $this;
	}
	
	public function autoLogin(string $token)
	{
		if(false === $params = $this -> unpackAutoLoginParams($token))
			return false;
		
		$user = $this -> db -> getRow("SELECT * FROM `".self :: TABLE."` WHERE `id`='".$params['id']."'");

		if(!is_array($user))
			return false;

		$secret = Registry :: get('SecretCode');
		$check = $user["email"].$user["id"].$user["login"].$user["password"].$secret.Debug :: browser();

		if(password_verify($check, $params["token"]))
			return $this -> loginUser($user['login'], $user['password'], 'autologin');

		return false;
	}
}
