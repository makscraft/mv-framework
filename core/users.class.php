<?php
/**
 * Model class of admin panel users.
 * Also manages users rights which allow to access admin panel models.
 */ 
class Users extends Model
{
	protected $name = "{users}";

	private $models_list = [];
	
	protected $model_elements = array(
		array("{active}", "bool", "active", array("on_create" => true)),
		array("{name-person}", "char", "name", array("required" => true, "unique" => true)),
		array("{email}", "email", "email", array("required" => true, "unique" => true)),
		array("{login}", "char", "login", array("required" => true, "unique" => true)),
		array("{password}", "password", "password", array("required" => true,
														  "letters_required" => true, 
														  "digits_required" => true)),
				
		array("{password-repeat}", "password", "password_repeat", array("required" => true, 
														  				"letters_required" => true, 
														 				"digits_required" => true)),
			
		array("{date-registered}", "date_time", "date_registered"),
		array("{date-last-visit}", "date_time", "date_last_visit"),
		array("{operations}", "many_to_one", "operations", array("related_model" => "Log"))
	);
			
	protected $model_display_params = array(		
		
		"not_editable_fields" => array('date_registered', 'date_last_visit')
	);		
			
	private $rights_table = "users_rights";
	
	private $users_rights = false;
			
	public function __construct()
	{
		parent :: __construct();
		
		$this -> includes_folder = $this -> registry -> getSetting("IncludeAdminPath")."includes/";
		$this -> models_list = array_merge(array_keys(Registry :: get('ModelsLower')), ["users", "log", "garbage"]);
	}
				
	public function validate()
	{
		$password = $this -> elements['password'] -> getValue();
		$password_repeat = $this -> elements['password_repeat'] -> getValue();
		
		if(($password || $password_repeat) && $password_repeat != $password)
			$this -> errors[] = "{passwords-must-match}";
		
		return parent :: validate();
	}
	
	public function create()
	{
		unset($this -> elements['password_repeat']);
		$rights = $this -> getRightsFromPost();
		
		$this -> elements['date_registered'] -> setValue(I18n :: getCurrentDateTime());
		
		$this -> sendUserInfo("created") -> hashUserPassword();
		$this -> id = parent :: create(array("rights" => $this -> packRights($rights)));
		$this -> updateRights($rights);		
		
		return $this -> id;
	}
	
	public function read()
	{
		if(isset($this -> elements['password_repeat']))
			$this -> elements['password_repeat'] -> setRequired(false);
		
		$arguments = func_get_args();
		
		//If we load old rights from version
		if(isset($arguments[0]) && is_array($arguments[0]))
		{
			if(isset($arguments[0]['rights'])) //If we have old rights we unpack them to show in table
				$this -> users_rights = $this -> unpackRights($arguments[0]['rights']);
			else
				$this -> users_rights = [];
		}
		else
			$arguments[0] = false;
		
		if($this -> users_rights === false)
			$this -> getUsersRights();

		return parent :: read($arguments[0]);
	}
	
	public function update()
	{
		unset($this -> elements['password_repeat']);
		
		$arguments = func_get_args();
				
		if(isset($arguments[0]) && $arguments[0] == 'self-update')
			$rights = $this -> getUsersRights();
		else
			$rights = $this -> getRightsFromPost();

		$this -> updateRights($rights) -> sendUserInfo("updated") -> hashUserPassword();
		
		if($this -> id == 1 || $this -> user -> getId() == $this -> id)
			$this -> elements['active'] -> setValue(1);
		
		if($_SESSION['mv']['user']['id'] == $this -> id) //Puts new data into session to avoid the break of login
			User :: updateLoginData($this -> getValue('login'), $this -> getValue('password'));
		
		return parent :: update(array("rights" => $this -> packRights($rights)));
	}
	
	public function hashUserPassword()
	{
		if($this -> getValue("password") && $this -> registry -> getInitialVersion() >= 2.2)
		{
			$hash = Service :: makeHash(trim($this -> getValue("password")));
			$this -> setValue("password", $hash);
		}
		
		return $this;
	}
		
	public function afterFinalDelete($id)
	{
		$this -> setId($id) -> updateRights([]);
	}
	
	public function displayUsersRights()
	{
		$html = "";
		
		$models_names = [];
		
		foreach($this -> models_list as $model)
		{
			$model_object = new $model();
			$models_names[$model] = $model_object -> getName();
		}
		
		$models_names["file_manager"] = I18n :: locale("file-manager");
		
		natsort($models_names); //A-z sorting of names list

		//Displays module name and right (set rights are checked)
		foreach($models_names as $model => $name) 
		{
			if($model != "file_manager")
				$model_object = new $model();
			
			$rights = array('create', 'read', 'update', 'delete');
			
			$html .= "<tr>\n";
			$html .= "<td class=\"model-name\">".$name."</td>\n";
			
			foreach($rights as $right)
			{
				$html .= "<td>\n";
				
				if(get_parent_class($model_object) == "ModelSimple" && ($right == "delete" || $right == "create"))
					$html .= "<span>-</span>";
				else if($name == "file_manager" || $right == "read" || $model_object -> checkDisplayParam($right."_actions"))
					$html .= "<input name=\"".$model."_".$right."_right\" type=\"checkbox\" ".$this -> checkRight($model, $right)." />\n";
				else
					$html .= "<span>-</span>";
					
				$html .= "</td>\n";
			}
			
			$html .= "</tr>\n";		
		}		
		
		return $html;
	}
	
	private function checkRight($model, $right)
	{
		if(isset($_POST[$model."_".$right."_right"]) || 
		  (empty($_POST) && isset($this -> users_rights[$model], $this -> users_rights[$model][$right]) && 
		  	$this -> users_rights[$model][$right]))
			return " checked=\"checked\"";
	}
	
	public function updateRights($all_rights)
	{
		$this -> db -> query("DELETE FROM `".$this -> rights_table."` 
		                      WHERE `user_id`='".$this -> id."' OR `user_id`=''"); //Deletes all old rights
				
		foreach($all_rights as $key => $val) //Updates the table with rights
			$this -> db -> query("INSERT INTO `".$this -> rights_table."`(`user_id`,`module`,`create`,`read`,`update`,`delete`) 
					  VALUES('".$this -> id."','".$key."','".$val['create']."','".$val['read']."',
					  '".$val['update']."','".$val['delete']."')");
			
		return $this;
	}
	
	public function getRightsFromPost()
	{
		$all_rights = [];

		foreach($_POST as $key => $val) //Take checked rights from POST
			if(preg_match("/(create|read|update|delete)_right$/", $key))
			{
				$right = [];
				$right[0] = preg_replace("/^(\w+)_(create|read|update|delete)_right$/", "$1", $key);
				$right[1] = preg_replace("/^(\w+)_(create|read|update|delete)_right$/", "$2", $key);
				
				if(!array_key_exists($right[0], $all_rights) && (in_array($right[0], $this -> models_list) || $right[0] == "file_manager"))
					$all_rights[$right[0]] = array("create" => 0, "read" => 0, "update" => 0, "delete" => 0);
					
				if(array_key_exists($right[0], $all_rights) && array_key_exists($right[1], $all_rights[$right[0]]))
					$all_rights[$right[0]][$right[1]] = 1;
			}

		return $all_rights;
	}
	
	public function packRights($rights)
	{
		$packed_rights = [];
		
		foreach($rights as $key => $val)
			$packed_rights[] = $key."->"."create-".$val['create']."_read-".$val['read'].
									 "_update-".$val['update']."_delete-".$val['delete'];
		
		return implode("-*//*-", $packed_rights);		
	}
	
	public function unpackRights($rights)
	{
		$users_rights = [];
		
		$rights = explode("-*//*-", $rights); //Rows of modules rights
		
		foreach($rights as $right)
		{
			$current_right = explode("->", $right); //Name and values of rights
			$possible_rights = array("create", "read", "update", "delete");
				
			if(isset($current_right[0], $current_right[1]) && (in_array($current_right[0], $this -> models_list) || $current_right[0] == "file_manager"))
			{
				$users_rights[$current_right[0]] = []; //Next allowed model
				
				foreach(explode("_", $current_right[1]) as $right_type)
				{
					$right_data = explode("-", $right_type); //Type and value of right
					
					if(isset($right_data[0], $right_data[1]) && in_array($right_data[0], $possible_rights) && 
					  ($right_data[1] == 0 || $right_data[1] == 1))
						$users_rights[$current_right[0]][$right_data[0]] = $right_data[1]; //Right goes to table
				}
			}
		}
		
		return $users_rights;
	}
	
	public function getUsersRights()
	{
		$result = $this -> db -> getAll("SELECT * FROM `".$this -> rights_table."` 
										 WHERE `user_id`='".$this -> id."'");

		$this -> users_rights = $this -> arrangeRights($result);
		
		return $this -> users_rights;
	}
	
	static public function arrangeRights($data)
	{
		$models = array_merge(array_keys(Registry :: get('ModelsLower')), ["users", "log", "garbage"]);
		$rights = [];
		
		foreach($data as $row)
			if(in_array($row['module'], $models) || $row['module'] == "file_manager")
					$rights[$row['module']] = array('create' => $row['create'], 'read' => $row['read'], 
											   		'update' => $row['update'], 'delete' => $row['delete']);
					
		return $rights;
	}
	
	public function sendUserInfo($type)
	{
		$email = $this -> elements["email"] -> getValue();
		$email = $this -> elements["name"] -> getValue()." <".$email.">";
		$password = $this -> elements["password"] -> getValue();
		$password = $password ? $password : mb_strtolower(I18n :: locale("no-changes"), "utf-8");
		
		if($email && isset($_POST["send_admin_info_email"]) && $_POST["send_admin_info_email"])
		{
			$url = $this -> registry -> getSetting("HttpAdminPanelPath");
			
			$message = "<p>".$this -> elements["name"] -> getValue().",<br />\n";
			$message .= I18n :: locale("user-account-".$type)."</p>\n";
			$message .= "<ul>\n<li>".$this -> elements["email"] -> getCaption().": ";
			$message .= $this -> elements["email"] -> getValue()."</li>\n";
			$message .= "<li>".$this -> elements["login"] -> getCaption().": ".$this -> elements["login"] -> getValue()."</li>\n";
			$message .= "<li>".$this -> elements["password"] -> getCaption().": ".$password."</li>\n";
			$message .= "<li>URL: <a href=\"".$url."\">".$url."</a></li>\n</ul>\n";
			
			Email :: send($email, I18n :: locale('user-data'), $message);
		}
		
		return $this;
	}
}
