<?php
/**
 * Manages versions history of records in model tables.
 * Keeps copies of records values, including many-to-many.
 * Contains methods to clenup versions of deleted records.
 */ 
class Versions
{
	/**
	 * Database manager
	 * @var object Database
	 */ 
	private $db;
	
	/**
	 * Pagination object
	 * @var object Pager
	 */
	public $pager;
	
	/**
	 * Name of current model
	 * @var string
	 */ 
	private $model;
	
	/**
	 * Table with versions
	 * @var string
	 */ 
	private $table;
	
	/**
	 * Id of current row of model
	 * @var int
	 */ 
	private $row_id;
	
	/**
	 * Current version number
	 * @var int
	 */ 
	private $version;
	
	/**
	 * Last version of row of current model
	 * @var int
	 */
	private $last_version;
	
	/**
	 * Params for url to pass in admin panel
	 * @var string
	 */ 
	private $url_params;
	
	/**
	 * Max number of keept versions of one model record
	 * @var int
	 */ 
	private $limit = 25;
	
	/**
	 * Number of all versions of current record
	 * @var int
	 */ 
	private $total;
	
	public function __construct(string $model, int $row_id)
	{
		$this -> db = Database :: instance();
		
		$this -> model = $model;
		$this -> row_id = $row_id;
		$this -> table = "versions";
		
		//Gets the number of last version of current record
		$this -> last_version = (int) $this -> db -> getCell("SELECT MAX(`version`) 
				   		 					     			  FROM `".$this -> table."` 
											 			      WHERE `model`='".$this -> model."'  
											 			      AND `row_id`='".$this -> row_id."'");
		
		//Total number of versions of current record
		$this -> total = $this -> db -> getCount($this -> table, "`model`='".$this -> model."'  
											 			  		   AND `row_id`='".$this -> row_id."'");
		
		$this -> pager = new Paginator($this -> total, 10);
	}
	
	public function setLimit(int $limit)
	{
		$this -> limit = $limit > 0 ? $limit : null;
	}
	
	public function getLimit()
	{
		return $this -> limit;
	}
	
	public function setVersion(int $version)
	{
		$this -> version = $version;

		return $this;
	}
	
	public function getVersion() 
	{ 
		return $this -> version; 
	}
	
	public function setUrlParams(string $url_params)
	{
		$this -> url_params = $url_params;

		return $this;
	}
		
	public function checkVersion(int $version)
	{
		return (bool) $this -> db -> getCount($this -> table, "`model`='".$this -> model."' 
																AND `version`='".intval($version)."'"); 
	}
	
	public function checkLastChanges(array $new_content)
	{
		$this -> version = $this -> last_version;
		$old_content = $this -> load(); //Loads the last saved version
		
		foreach($new_content as $field => $value) //If new content has new fields
			if(!isset($old_content[$field]))
				return true;
		
		foreach($old_content as $field => $value) //Check all fields
			if(isset($new_content[$field]) && $new_content[$field] != $value)
				return true; //If at least one field was changed its a new version
			
		return false;
	}
	
	public function display()
	{
		if(!$this -> last_version) //If it was no versions before
			return "<tr><td colspan=\"3\">".I18n :: locale('versions-no-yet')."</td></tr>\n";
			
		$html = "";
		
		$result = $this -> db -> query("SELECT * FROM `".$this -> table."` 
										WHERE `model`='".$this -> model."' 
										AND `row_id`='".$this -> row_id."'
										ORDER BY `version` DESC ".
										$this -> pager -> getParamsForSQL());
		$css_class = '';
		$users_names = (new Log) -> getElement('user_id'); 
		$users_names = $users_names -> getDataOfForeignKey();
				
		$page = $this -> pager -> getPage();
		$page = $page > 1 ? "&versions-page=".$page : "";
		
		while($row = $this -> db -> fetch($result, "ASSOC"))
		{
			$css_class = "";
			
			if($this -> version && $this -> version == $row['version'])
				$css_class = " class=\"active\"";
			else if($row['version'] == $this -> last_version && !$this -> version)
				$css_class = " class=\"active\"";
			
			$url_params = $this -> url_params.$page;
			
			if($row['version'] != $this -> last_version)
				$url_params .= "&version=".$row['version'];
			
			$user = isset($users_names[$row['user_id']]) ? $users_names[$row['user_id']] : 'Site';
			
			$html .= "<tr".$css_class.">\n";
			$html .= "<td class=\"number\">".$row['version']."</td>\n";
			$html .= "<td class=\"date\"><p class=\"date\"><span>".I18n :: locale("date")."</span> ";
			$html .= I18n :: dateFromSQL($row['date'], "no-seconds")."</p>\n";
			$html .= "<p class=\"user\"><span>".I18n :: locale("user")."</span> ".$user."</p></td>\n";
			$html .= "<td class=\"rollback\">";
			
			if(!$css_class)
				$html .= "<a href=\"?".$url_params."\">".I18n :: locale('rollback')."</a>";
			
			$html .= "</td>\n</tr>\n";
		}		
		
		return $html;
	}
	
	public function load()
	{
		if($this -> version) //Gats the content of version
				return Service :: unserializeArray($this -> db -> getCell(
											  "SELECT `content` FROM `".$this -> table."`  
								  			   WHERE `model`='".$this -> model."' 
								  			   AND `row_id`='".$this -> row_id."'  
											   AND `version`='".$this -> version."'"));	
	}
	
	public function save(array $content, mixed $user)
	{
		if(!$this -> limit || ($this -> last_version && !$this -> checkLastChanges($content)))
			return false; //If it was no any changes or versions are disallowed
			
		$dump = Service :: serializeArray($content); //Packs all fields
		$next_version_number = $this -> last_version + 1;
		
		$user_id = (is_object($user) && get_class($user) == "User") ? $user -> getId() : 0;
		
		//Puts new version into db
		$this -> db -> query("INSERT INTO `".$this -> table."`
							 (`model`,`row_id`,`version`,`content`,`user_id`,`date`)
							  VALUES('".$this -> model."','".$this -> row_id."','".$next_version_number."',
							  ".$this -> db -> secure($dump).",'".$user_id."',".$this -> db -> now('with-seconds').")");
		
		//Deletes versions which exceed the limit for this model
		if($this -> total + 1 > $this -> limit)
		{
			$ids = $this -> db -> getColumn("SELECT `id` FROM `".$this -> table."` 
								  		 	 WHERE `model`='".$this -> model."' 
								 		 	 AND `row_id`='".$this -> row_id."'
								  		 	 ORDER BY `date` DESC 
								  		 	 LIMIT ".($this -> limit + 1));
			
			if(count($ids))
				$this -> db -> query("DELETE FROM `".$this -> table."` 
									  WHERE `model`='".$this -> model."' 
									  AND `row_id`='".$this -> row_id."' 
									  AND `id` NOT IN(".implode(",", $ids).")");
		}
		
		return true;
	}
	
	public function clean()
	{		
		$model_object = new $this -> model();
		$files_fields = $model_object -> defineFilesTypesFields();
				
		$result = $this -> db -> query("SELECT * FROM `".$this -> table."` 
										WHERE `model`='".$this -> model."' 
										AND `row_id`='".$this -> row_id."'");

		//Deletes all files of all versions of record
		while($row = $this -> db -> fetch($result, "ASSOC"))
			$this -> cleanFiles(Service :: unserializeArray($row['content']), $files_fields);
		
		return $this -> cleanRecordVersions();
	}
	
	public function cleanRecordVersions()
	{
 		//Deletes all versions of record
 		$this -> db -> query("DELETE FROM `".$this -> table."` 
 							  WHERE `model`='".$this -> model."' 
 							  AND `row_id`='".$this -> row_id."'");
 		return $this;
 	}
	
	static public function cleanFiles(array $content, array $files_fields)
	{
		foreach($content as $field => $data)
			if(in_array($field, $files_fields))
			{
				//In case of multi images type
				if(strpos($data, '-*//*-') !== false || strpos($data, '(*') !== false || strpos($data, '[') !== false)
				{
					$files = [];

					foreach(MultiImagesModelElement :: unpackValue($data) as $image)
						$files[] = Service :: addFileRoot($image['image']);
				}
				else //Regular images and files
					$files = [Service :: addFileRoot($data)];

				foreach($files as $real_file) //Deletes files of record
					if($real_file && is_file($real_file))
						if(self :: checkModelFilesCopies($real_file))
							@unlink($real_file);
			}
	}
	
	static public function checkModelFilesCopies(string $file)
	{
		$folder = preg_replace("/\/$/", "", dirname($file));
		$folder = $folder_name = preg_replace("/.*\/([^\/]+)$/", "$1", $folder);
		$folder = explode("-", $folder);
		$file = basename($file);
		
		if(Registry :: checkModel($folder[0]) && ($folder[1] == "files" || $folder[1] == "images"))
		{
			$model = new $folder[0]();
			$fields = $model -> defineFilesTypesFields();

			if(!count($fields))
				return true;
				
			$query = [];
			
			foreach($fields as $field)
			{
				$search = "%/".$folder_name."/".$file."%";

				$query[] = "`".$field."` LIKE '".$search."'";

				//for sqlile
				$query[] = "`".$field."` LIKE '".str_replace('/', '\/', $search)."'";

				//for mysql
				$query[] = "`".$field."` LIKE '".str_replace('/', '\\\\\\\\/', $search)."'";
			}
			
			$found = $model -> db -> getCount($model -> getTable(), implode(" OR ", $query));
			
			return $found ? false : true;
		}
		
		return true;
	}
		
	public function displayPager()
	{
		$html = $this -> pager -> displayPagesAdmin();

		if(!$html)
			return '';

		return preg_replace("/page=(\d+)/", "versions-page=$1", $html);
	}
}
