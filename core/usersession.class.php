<?php
/**
 * Manages sessions data of admministrators during the work in admin panel.
 * Contains some methods to increase the security of sessions.
 */
class UserSession
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
	 * Table with users sessions.
	 * @var string
	 */ 
	private $table;
	
	/**
	 * Table with users data.
	 * @var string
	 */ 
	private $users_table;
	
	/**
	 * Id of current user.
	 * @var int
	 */ 
	private $id;
	
	public function __construct(int $id)
	{
		//Pass the current admin id and sets tables and db object.
		
		$this -> registry = Registry :: instance();
		$this -> db = DataBase :: instance();
		$this -> id = intval($id);
		$this -> table = "users_sessions";
		$this -> users_table = "users";
	}
	
	public function startSession()
	{
		//Begins new session for just authorized user
		//Cleans up the old sessions data and incorrect users
		$this -> db -> query("DELETE FROM `".$this -> table."` 
		                      WHERE `user_id` NOT IN (SELECT `id` FROM `".$this -> users_table."`)
		                      OR `session_id`='".session_id()."' 
							  OR (".$this -> db -> unixTimeStamp('now')."-".$this -> db -> unixTimeStamp('last_hit').") > 
							  ".$this -> registry -> getSetting("SessionLifeTime"));
		
		 //Begins session for new user and saves user's data in DB
		$this -> db -> query("INSERT INTO ".$this -> table."(`user_id`,`session_id`,`ip_address`,`user_agent`,`last_hit`)
		                      VALUES('".$this -> id."','".session_id()."','".ip2long($_SERVER['REMOTE_ADDR'])."',
						       '".md5($_SERVER['HTTP_USER_AGENT'])."', ".$this -> db -> now().")");
	}
	
	public function stopSession()
	{
		//Deletes session of current admin from table
		$this -> db -> query("DELETE FROM `".$this -> table."` WHERE `session_id`='".session_id()."'");
	}

	public function checkSession() 
	{
		//Checks if the current user's session is valid
		
		//Time between last hit and current hit
		if($this -> registry -> getSetting("DbEngine") == "sqlite")
		{
			$time = $this -> db -> getCell("SELECT `last_hit`  
							                FROM `".$this -> table."` 
											WHERE `user_id`='".$this -> id."' 
											AND `session_id`='".session_id()."'");
			
			$time = time() - I18n :: dateToTimestamp($time);
		}
		else
			$time = $this -> db -> getCell("SELECT (".$this -> db -> unixTimeStamp('now')."-".
											$this -> db -> unixTimeStamp('last_hit').")  
							                FROM `".$this -> table."` 
											WHERE `user_id`='".$this -> id."' 
											AND `session_id`='".session_id()."'");
												
		$user = $this -> db -> getCount($this -> table, "`user_id`='".$this -> id."'
		                                                AND `ip_address`='".ip2long($_SERVER['REMOTE_ADDR'])."' 
														AND `user_agent`='".md5($_SERVER['HTTP_USER_AGENT'])."'
														AND `session_id`='".session_id()."'"); 
														 //Makes sure it is the same user as previous
		
		return (bool)($time < $this -> registry -> getSetting("SessionLifeTime") && $user == 1); //Checks both conditions
	}

	public function continueSession()
	{
		//Makes new time stamp for current user session
		$this -> db -> query("UPDATE `".$this -> table."` 
		                      SET `last_hit`=".$this -> db -> now()." 
							  WHERE `user_id`='".$this -> id."' 
							  AND `session_id`='".session_id()."'");
	}
			
	public function checkOnlineUsers()
	{
		//Checks the time from last user's hit in admin panel to say if this user is online
		//Time between last hit and current moment
		if($this -> registry -> getSetting("DbEngine") == "sqlite")
		{
			$all_ids = $this -> db -> getAll("SELECT `user_id`, `last_hit` FROM `".$this -> table."`");
			$users_ids = array();
			
			foreach($all_ids as $row)
				if(time() - I18n :: dateToTimestamp($row["last_hit"]) <= $this -> registry -> getSetting("UserOnlineTime"))
					if(!in_array($row["user_id"], $users_ids))
						$users_ids[] = $row["user_id"];
		}
		else
			$users_ids = $this -> db -> getColumn("SELECT `user_id` FROM `".$this -> table."` 
											       WHERE(".$this -> db -> unixTimeStamp('now')."-".
											       $this -> db -> unixTimeStamp('last_hit').") <=
											       ".$this -> registry -> getSetting("UserOnlineTime").
											       " GROUP BY `user_id`");
		
		array_unique($users_ids);
				
		return $users_ids;
	}
}
