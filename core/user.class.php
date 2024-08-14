<?php
/**
 * Manages the users and their rights in admin panel.
 * Main class for checking the authorization of users in MV admin area.
 * Also manages the users righs to access to the MV modules.
 */
class User
{
	/**
	 * Object with settings.
	 * @var object Registry
	 */ 
	public $registry;
   
	/**
	 * Database manager object.
	 * @var object Database
	 */ 
	public $db;

	/**
	 * UserSession object to control the session.
	 * @var object UserSession
	 */  
	public $session;
	
	/**
	 * Current SQL table name.
	 * @var string
	 */ 
	private const TABLE = 'users';
	
	/**
	 * Table with users rights.
	 * @var string
	 */ 
	private const RIGHTS_TABLE = 'users_rights';
	
	/**
	 * All data related to current user (login, name, ...)
	 * @var array
	 */ 
	private $content;
	
	/**
	 * Id of current user.
	 * @var int
	 */ 
	private $id;
	
	/**
	 * Error message text.
	 * @var string
	 */
	private $error;

	/**
	 * List of current user's rights.
	 * @var array
	 */ 
	private $rights = [];
	
	public function __construct($id)
	{
		//Sets tables and needed objects, also gets the users rights
		$this -> registry = Registry :: instance(); //Langs and settings
		$this -> db = DataBase :: instance(); //Manages database

		 //Current user's data
		$this -> content = $this -> db -> getRow("SELECT * FROM `".self :: TABLE."` 
		                                          WHERE `id`=".intval($id));
		
		if(!isset($this -> content['id']) || !$this -> content['id'])
		{
			$this -> content = null;
			return;
		}
											
		$this -> id = $this -> content['id']; //Sets user id
		
		//Gets user's rights in amdin panel
		$this -> rights = $this -> db -> getAll("SELECT * FROM `".self :: RIGHTS_TABLE."` 
		                                         WHERE `user_id`='".$this -> id."'");
		
		//Changes the format of rights for the class methods
		$this -> rights = Users :: arrangeRights($this -> rights);
		
		if($this -> id)  //Object to control the session for this user	
			$this -> session = new UserSession($this -> id);
	}
	
	public function getContent() { return $this -> content; }
	public function getId() { return $this -> id; }
 	public function getField($field) { return $this -> content[$field]; }
 	public function getError() { return $this -> error; }

	public function checkUserLogin()
	{ 		
 		if(!isset($_SESSION['mv']['user']['id']) || !is_array($this -> content))
 			return false;

 		//We check user's password in db according to passed login
 		$password = md5($this -> content['password']); //Hash of password to compare
		
		if($_SESSION['mv']['user']['id'] != $this -> content['id'] || $_SESSION['mv']['user']['password'] != $password
		   || !$this -> session -> checkSession())
			return false;
		else if($this -> id != 1 && !$this -> content['active'])
			return false;
		else
		{
			$this -> session -> continueSession(); //Continues the current session in db
			return true;
		}

		return false;
	}
	 	
 	static public function updateLoginData($login, $password)
 	{
 		$_SESSION['mv']['user']['login'] = $login;
 		
 		if($password)
 		{
 			$password = (Registry :: instance() -> getInitialVersion() >= 2.2) ? $password : md5($password);
 			$_SESSION['mv']['user']['password'] = md5($password);
 		}
 	}
 	
 	public function saveSettings($settings)
 	{
 		$settings = base64_encode(json_encode($settings));
 		
 		$this -> db -> query("UPDATE `".self :: TABLE."` 
 							  SET `settings`=".$this -> db -> secure($settings)." 
 							  WHERE `id`='".$this -> id."'");
 	}
 	
 	public function loadSettings()
 	{
 		$data = $this -> db -> getCell("SELECT `settings` 
 										FROM `".self :: TABLE."`  
 										WHERE `id`='".$this -> id."'");
 		
 		return json_decode(base64_decode($data), true);
 	}
 	
 	public function updateSetting($key, $value)
 	{
 		$settings = $this -> loadSettings();
 		$settings[$key] = $value;
 		$this -> saveSettings($settings);
 		
 		return $this;
 	}
 		
	public function checkModelRights($module, $right)
	{
		//Checks if the right for the modele is exists.
		//Root user has access to any module othe users must have rights via policy
		if($this -> id == 1) 
			return true;
			
		$all_modules = array_merge(array_keys(Registry :: get('ModelsLower')), 
								  ["users", "log", "garbage", "file_manager"]);
		
		$module = strtolower($module);
		
		if(!isset($this -> rights[$module]) || !in_array($module, $all_modules))
			return false;
				
		return (bool) $this -> rights[$module][$right];
	}

	public function extraCheckModelRights($module, $right)
	{
		//Check the rights inside the any amdin panel page related to module (edit, create, ...) and redirects if no right
		if(!$this -> checkModelRights($module, $right))
		{
			$this -> error = I18n :: locale("error-no-rights");
            include $this -> registry -> getSetting("IncludeAdminPath")."controls/internal-error.php";
		}
	}
	
	public function checkModelRightsJS($module, $right, $href)
	{
		if($this -> checkModelRights($module, $right))
    		return $href;
		else
			return "javascript:$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});";
	}
	
	public function getUserSkin()
	{
		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		
		if(isset($_SESSION['mv']['settings']['skin']) && $_SESSION['mv']['settings']['skin'])
			if($_SESSION['mv']['settings']['skin'] == "none")
				return "none";
			else if(is_dir($path.$_SESSION['mv']['settings']['skin']) && 
					is_file($path.$_SESSION['mv']['settings']['skin']."/skin.css"))
				return $_SESSION['mv']['settings']['skin'];
	}
	
	public function getAvailableSkins()
	{
		$skins = array("mountains");
		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		
		$folders = scandir($path);
		
		foreach($folders as $folder)
			if(!preg_match("/^\./", $folder) && $folder != "mountains" && $folder != "default")
				$skins[] = $folder;

		$skins[] = "none";
				
		return $skins;
	}
	
	public function setUserSkin($name) 
	{
		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		
		if((is_dir($path.$name) && is_file($path.$name."/skin.css")) || $name == "none")
		{
			$_SESSION['mv']['settings']['skin'] = $name;
			$this -> updateSetting("skin", $name);
			return 1;
		}		
	}
	
	public function displayUserSkinSelect()
	{
		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		$html = "<select name=\"admin-panel-skin\" id=\"user-settings-skin-select\">\n";
		$folders = array("none") + scandir($path);
		
		foreach($folders as $folder)
			if(!preg_match("/^\./", $folder))
			{
				$selected = "";
				
				if(isset($_POST['admin-panel-skin']) && $_POST['admin-panel-skin'] == $folder)
					$selected = ' selected="selected"';
				else if(empty($_POST) && isset($_SESSION['mv']['settings']['skin']) && 
						$_SESSION['mv']['settings']['skin'] == $folder)
					$selected = ' selected="selected"';
				
				$html .= "<option".$selected." value=\"".$folder."\">".$folder."</option>\n";
			}
		
		return $html."</select>\n";
	}
}
