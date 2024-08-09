<?php
/**
 * File manager class of admin panel of MV framework.
 * Operates with files and folders, can also cleanup old useless files. 
 */
class Filemanager
{
	/**
	 * Object with settings.
	 * @var Registry
	 */
	public $registry;

	/**
	 * File which is shown by default usually the first in directory.
	 * @var string
	 */ 
	private $first_file;
	
	/**
	 * Imager object to create small copies of images.
	 * @var Imager
	 */ 
	private $imager;
	
	/**
	 * Paginator object to split list of files into some pages.
	 * @var Paginator
	 */ 
	public $pager;
		
	/**
	 * User object to check the rights to manage files.
	 * @var User
	 */ 
	public $user;

	/**
	 * Current path we show the files from.
	 * @var string
	 */ 
	private $path;
	
	/**
	 * Total number of files in directory including dierctories and '..' (but not '.').
	 * @var int
	 */ 
	private $total;
		
	/**
	 * Shows if we have GET parameters in current URL
	 * @var string
	 */ 
	protected $url_params;
	
	/**
	 * CSRF token from System core class.
	 * @var string
	 */ 
	private $token;

	/**
	 * Limit of files to process during the one run of cleanup.
	 * @var int
	 */ 
	private static $cleanup_limit = 10000;

	/**
	 * Process files of current cleaup run.
	 * @var int
	 */ 
	private static $cleanup_count;
	
	public function __construct()
	{
		//Checks and path to get the files to read from, counts files.
		$this -> registry = Registry :: instance(); //Object with global settings
		
		//If we just enter into file manager we go to this directory
		//Path wich will be opened by default
		if(!isset($_SESSION['mv']['file-manager']['path']) || !$_SESSION['mv']['file-manager']['path']) 
			$_SESSION['mv']['file-manager']['path'] = $this -> registry -> getSetting('FilesPath')."images/";
		//If we get into next directory
		else if(isset($_GET['folder']) && is_dir($_SESSION['mv']['file-manager']['path'].$_GET['folder']."/"))
			$_SESSION['mv']['file-manager']['path'] = $_SESSION['mv']['file-manager']['path'].$_GET['folder']."/";
		//Gets back to previous directory
		else if(isset($_GET['back']) && $_SESSION['mv']['file-manager']['path'] != $this -> registry -> getSetting('FilesPath')) 
			$_SESSION['mv']['file-manager']['path'] = preg_replace("/^(.*\/)[^\/]+\/?$/", "$1", $_SESSION['mv']['file-manager']['path']);
		
		if(isset($_GET['folder']) || isset($_GET['back']))
			$this -> reload();
		
		$this -> path = $_SESSION['mv']['file-manager']['path']; //Path that we will use to get files from
		$this -> total = $this -> countFiles($this -> path); //Total number of files in current dir
		$limit = isset($_SESSION['mv']['settings']['pager-limit']) ? intval($_SESSION['mv']['settings']['pager-limit']) : 10;
		
		$this -> imager = new Imager();
		$this -> pager = new Paginator($this -> total, $limit); //To split the whole list into pages
	}

	public function getPath() { return $this -> path; }
	public function getFirstFile() { return $this -> first_file; }
	
	public function setUser(User $user)
	{ 
		$this -> user = $user;
		
		return $this;
	}
	
	public function setFirstFile($file) 
	{
		if(file_exists($this -> path.$file)) 
			$this -> first_file = $file;
		
		return $this;
	}
	
	public function setToken($token)
	{
		$this -> token = $token;
		return $this;
	}
	
	public function openFolder()
	{
		$arguments = func_get_args();
		
		//Trys to open current directory to read the files
		if(isset($arguments[0]) && $arguments[0])
			$folder = $arguments[0];
		else
			$folder = $this -> path;
			
		$descriptor = @opendir($folder);
		
		if(!$descriptor)
		{
			$_SESSION['mv']['file-manager']['path'] = "";
			Debug :: displayError("Unable to open the directory ".$folder);
		}
		else 
			return $descriptor;
	}
	
	public function countFiles($path)
	{
		//Counts the total number of files in directory for pagination
		$folder = $this -> openFolder();
		$count = 0;
				
		while(false !== ($file = readdir($folder)))
		{
			if($file == '.') //We don't count the directory itself
				continue;
			
			if($file == '..' && $this -> path == $this -> registry -> getSetting('FilesPath'))
				continue;
							
			if(is_file($path.$file) || is_dir($path.$file)) //Counts only real files and folders
				$count ++;
		}
		
		//We don't count 'up-one-level dir' if we are at the end path
		return ($this -> path == $this -> registry -> getSetting('FilesPath')) ? -- $count : $count;
	}
	
	public function displayPath()
	{
		$p1 = $this -> registry -> getSetting('FilesPath');
		
		$p1 = $p2 = preg_replace("/.*(\/.*\/$)/", "$1", $p1);
		$p2 = str_replace('/', '\/', $p2);
		$p2 = preg_replace("/.*".$p2."(.*)/", "$1", $this -> path);
		
		return str_replace('/', ' / ', $p1.$p2);
	}	
		
	public function display()
	{	
		//Shows the list of all files / folders in directories in according to current page.
		clearstatcache();
		
		$folder = $this -> openFolder();

		//Pager object calculates the first and last positions to display
		$count = 0;
		$start = $this -> pager -> getStart();
		$limit = $this -> pager -> getLimit();
		$stop = $start + $limit;
		
		$html = "";
		
		while(false !== ($file = readdir($folder)))
		{		
			if($file == '.') //Just skip it
				continue;

			if($file == '..') //This directory should go first in list of files
			{
				//If we are not allowed to go any upper we skip the '..' directory
				 if($this -> path == $this -> registry -> getSetting('FilesPath'))
					 break;
				
				if($count >= $start && $count < $stop) //If we are in needed interval we display the link
				{
					$html .= "<tr>\n<td><span class=\"folder-up\"></span></td>\n<td class=\"folder-name\">\n";
					$html .= "<a id=\"upper-folder\" href=\"?back\" rel=\"back\">".I18n :: locale('upper')."</a>\n</td>\n";
					//Link to get back up
					$html .= "<td><input type=\"hidden\" id=\"view_".$count."\" value=\"?back\" />\n";

					//Action to paste data form buffer
					$href = "javascript:fileManager.pasteFromBuffer('".$_SERVER['PHP_SELF'].(($this -> url_params ? '&' : '?')."action=buffer-paste&token=".$this -> token."'").")";
					$href = $this -> user -> checkModelRightsJS('file_manager', 'update', $href);
					
					$html .= "<input type=\"hidden\" id=\"paste_".$count."\" value=\"".$href."\" />\n";
					
					$html .= " - </td>\n";
					$html .= "<td> - </td>\n</tr>\n";
				}
				
				$count ++;
				break;
			}
			
			if($count >= $stop)
				return $html;
		}
		
		if($this -> path == $this -> registry -> getSetting('FilesPath')."database/")
			return $html."<tr><td colspan=\"4\">".I18n :: locale("forbidden-directory")."</td></tr>\n";
		
		rewinddir($folder); //Now we need to display all folder but not files
		
		while(false !== ($file = readdir($folder)))
		{	
			if(is_dir($this -> path.$file) && $file != '..' && $file != '.') //Skip this directories
			{
				 if($count >= $start && $count < $stop) //If we are in needed interval we display the link
				 {
					 $html .= "<tr><td><span class=\"folder\"></span></td>\n";
					 $html .= "<td class=\"folder-name\">";
					 $html .= "<a name=\"".$file."\" href=\"?folder=".$file."\" rel=\"folder\">".$file."</a></td>\n";
					 $html .= "<td>\n";
					
					 $actions = $this -> displayFolderActions($file); //Gets actions to implement with context menu
					
					 foreach($actions as $key => $val) //Adds the actions in hidden fields to call them later
						 $html .= "<input type=\"hidden\" id=\"".$key."_".$file."\" value=\"".$val."\" />\n";
					 
					$html .= I18n :: convertFileSize($this -> defineFolderSize($this -> path.$file))."</td>\n";
					$html .= "<td>".I18n :: timestampToDate(filemtime($this -> path.$file))."</td>\n";
					
					$html .= "</tr>\n";

				 }
				 $count ++;
			}

		if($count >= $stop)
			return $html;
		}

		rewinddir($folder);
		
		while(false !== ($file = readdir($folder)))
		{		
			if(is_file($this -> path.$file) && !preg_match('/^\./', $file))
			{
				if($count >= $start && $count < $stop) //If we are in needed interval we display the link
				{
					$file_name = $file;
					$html .= "<tr>\n";
					$html .= "<td><input type=\"checkbox\" name=\"delete_".$count."\" value=\"".urlencode($file_name)."\" /></td>\n";					
					$html .= "<td><a href=\"javascript:;\" name=\"".$file_name."\" rel=\"file\">".$file."</a></td>\n";
					$html .= "<td>\n";

					$actions = $this -> displayFileActions($file);  //Gets actions to implement with context menu
					
					foreach($actions as $key => $val) 
						$html .= "<input type=\"hidden\" id=\"".$key."_".$file."\" value=\"".$val."\" />\n";
					
					$html .= I18n :: convertFileSize(filesize($this -> path.$file))."</td>\n";
					$html .= "<td>".I18n :: timestampToDate(filemtime($this -> path.$file))."</td>\n";
					$html .= "</tr>\n";
					
					if(!$this -> first_file) //Sets the first file to display it's image by default
						$this -> first_file = $file_name;
				}
				$count ++;
			}
			
			if($count >= $stop) //If we at the end of current interval we get out
				return $html;
		}
		
		closedir($folder);

		return $html;
	}
	
	public function defineFolderSize($path)
	{
	    $file_size = 0;
	    $dir = scandir($path);
	    
	    foreach($dir as $file)
	        if (($file != '.') && ($file != '..'))
	            if(is_dir($path.'/'.$file))
	                 $file_size += $this -> defineFolderSize($path.'/'.$file);
	            else
	                 $file_size += filesize($path.'/'.$file);
	    
	    return $file_size;
	}

	public function displayFolderActions($folder)
	{
		//Makes the javascripts for actions of context menu for folders
		$actions = [];
		$actions['view'] = "?folder=".$folder;
		
		$href = "javascript:fileManager.pasteFromBuffer('".$_SERVER['PHP_SELF']."?token=".$this -> token."&action=buffer-paste".$this -> pager -> addPage(1)."')";
		$actions['paste'] = $this -> user -> checkModelRightsJS('file_manager', 'update', $href); //Paste action
		
		$href = "javascript:dialogs.showConfirmMessage('{delete_folder}', '".$_SERVER['PHP_SELF']."?token=".$this -> token."&delete-folder=".$folder.$this -> pager -> addPage(1)."', '".$folder."')";				 
		$actions['delete'] = $this -> user -> checkModelRightsJS('file_manager', 'delete', $href); //Delete action
		
		$href = "javascript:fileManager.renameFileOrFolder('".$folder."','folder')";
		$actions['rename'] = $this -> user -> checkModelRightsJS('file_manager', 'update', $href); //Rename action
		
		$href = "javascript:fileManager.addToBuffer('cut','".$this -> path.$folder."')";
		$actions['cut'] = $this -> user -> checkModelRightsJS('file_manager', 'update', $href); //Cut into buffer
		
		$href = "javascript:fileManager.pasteFromBuffer('".$_SERVER['PHP_SELF']."?token=".$this -> token."&action=buffer-paste".$this -> pager -> addPage(1)."')";
		$actions['paste'] = $this -> user -> checkModelRightsJS('file_manager', 'update', $href);  //Paste action
		
		
		return $actions; //Array of actions (js actions for A tags)	
	}

	public function displayFileActions($file)
	{
		//Makes the javascripts for actions of context menu for files.
		$file_name = $file;
		$actions = array();

		$href = "javascript:dialogs.showConfirmMessage('{delete_file}', '".$_SERVER['PHP_SELF']."?token=".$this -> token."&delete-file=".$file_name.$this -> pager -> addPage(1)."', '".$file_name."')";	
		$actions['delete'] = $this -> user -> checkModelRightsJS('file_manager', 'delete', $href); //Delete action
		
		$href = "javascript:fileManager.renameFileOrFolder('".$file."', 'file')";
		$actions['rename'] = $this -> user -> checkModelRightsJS('file_manager', 'update', $href); //Rename action

		$file_path = Service :: addRootPath($this -> path.$file);
		$actions['view'] = "javascript:fileManager.showFile('".$file_path."')";
		
		$href = "javascript:fileManager.addToBuffer('copy','".$this -> path.$file."')";
		$actions['copy'] = $this -> user -> checkModelRightsJS('file_manager', 'update', $href); //Copy to buffer
		
		$href = "javascript:fileManager.addToBuffer('cut','".$this -> path.$file."')";
		$actions['cut'] = $this -> user -> checkModelRightsJS('file_manager', 'update', $href); //Cut into buffer
		
		$href = "javascript:fileManager.pasteFromBuffer('".$_SERVER['PHP_SELF']."?token=".$this -> token."&action=buffer-paste".$this -> pager -> addPage(1)."')";
		$actions['paste'] = $this -> user -> checkModelRightsJS('file_manager', 'update', $href);  //Paste action
		
		return $actions; //Array of actions (js actions for A tags)
	}
	
	public function displayImage($file)
	{
		//We use Imager object to display the small copy of image in file manager.
		$file = $this -> path.$file;
		$html = "";

		//We don't resize svg images
	    if(is_file($file) && Service :: getExtension($file) == "svg" && mime_content_type($file) == "image/svg+xml")
	    	return "<img src=\"".Service :: removeDocumentRoot($file)."\" alt=\"".basename($file)."\" />\n";
		
		if(file_exists($file) && @getimagesize($file))
		{
			$this -> imager -> setImage($file);
			$this -> cleanTmpFiles(); //Delete temporary files

			//Makes a new name of temporary file
			$tmp_file = $this -> registry -> getSetting("FilesPath")."tmp/tmp-filemanager-".Service :: randomString(10);
			$tmp_file .= ".".$this -> imager -> type;
			
			@copy($file, $tmp_file);
			$tmp_file = $this -> imager -> compress($tmp_file, "filemanager", 230, 230); //Resize the image
			$html .= "<img src=\"".$tmp_file."\" alt=\"".basename($file)."\" />\n";
		}
		else
			$html .= "<p class=\"simple-message\">".I18n :: locale('no-image')."</p>\n";
		
		return $html;
	}
	
	public function displayFileParams($file)
	{
		//Shows the params of file like size and name.
		$html = "";
		$file = $this -> path.$file;
		
		if(file_exists($file))
		{
			$html .= "<tr>\n<td class=\"name\">".I18n :: locale('name').":</td>\n";
			$html .= "<td class=\"value\">".basename($file)."</td>\n</tr>\n";
			
			$mass = @getimagesize($file);
			
			if(isset($mass[0], $mass[1]) && $mass[0] && $mass[1])
			{
				$html .= "<tr><td class=\"name\">".I18n :: locale('width').":</td>\n";
				$html .= "<td class=\"value\">".$mass[0]."</td></tr>\n";
				$html .= "<tr><td class=\"name\">".I18n :: locale('height').":</td>\n";
				$html .= "<td class=\"value\">".$mass[1]."</td></tr>\n";
			}
			
			$html .= "<tr><td class=\"params\">".I18n :: locale('size').":</td>\n<td>";
			$html .= I18n :: convertFileSize(filesize($file))."</td></tr>\n";
		}
		
		return $html;
	}
		
	public function deleteFile($file)
	{
		//Deletes the one file from current folder.	
		if(file_exists($this -> path.$file))
			return @unlink($this -> path.$file);
	}

	public function deleteManyFiles()
	{
		//If we have many files to delete at one time we do it one by one.
		$deleted = 0;
		
		foreach($_POST as $key => $file)
			if(preg_match("/^delete_\d+$/", $key))
				if($this -> deleteFile($file))
					$deleted ++;
			
		return $deleted;
	}
	
	public function checkDeleteMany()
	{
		//Check if we have many files to delete
		foreach($_POST as $key => $val)
			if(preg_match("/^delete_\d+$/", $key)) //Deleted file must have a proper prefix
				return true;
		
		return false;
	}
	
	public function deleteFolder($folder)
	{
		//Deletes directory from current folder
		if(file_exists($this -> path.$folder))
			return @rmdir($this -> path.$folder);
	}
	
	public function uploadFile($file)
	{
		//Uploads the new file to the server into current folder
		clearstatcache();
		
		if(!isset($file['name']) || !$file['name'])
			return;
		
		$extension = Service :: getExtension($file['name']); //Get file's extension
		$extension = ($extension == "jpeg") ? "jpg" : $extension;
		
		$new_file = Service :: removeExtension($file['name']);
		$new_file = str_replace(" ", "_", $new_file);		
		$new_file = $this -> path.$new_file.".".$extension;
		
		if(file_exists($new_file))
			return "error=file-exists";
		else if((isset($file['error']) && $file['error'] == 1) || !is_uploaded_file($file['tmp_name']))
			return "error=upload-file-error";
        
		//Checking the type of file to make sure we are not apploading any wrong types
		if($this -> checkFileExtension($new_file))
			$result = move_uploaded_file($file['tmp_name'], $new_file); //Copying new file
		else
			return "error=wrong-filemanager-type";
			
		if($result)
			$_SESSION['mv']['just-uploaded-file'] = basename($new_file);
		
		return $result ? "done=file-uploaded" : "error=upload-file-error";
	}
	
	public function createFolder($name)
	{
		$name = trim(strval($name));
		
		if($name == "")
			return;
		
		if(file_exists($this -> path.$name))
			return "error=folder-exists";
		else if(preg_match("/[^\w-]/", $name))
			return "error=bad-folder-name";
		
		// Creates a new folder (directory) in current directory
		return @mkdir($this -> path.$name) ? "done=folder-created" : "error=folder-not-created";
	}
	
	public function cleanTmpFiles()
	{
		$registry = Registry :: instance(); //Object with global settings
		$tmp_folder = $registry -> getSetting('FilesPath')."tmp/";
		$descriptor = $this -> openFolder($tmp_folder);		

		while(false !== ($file = readdir($descriptor)))
			if(preg_match("/^tmp-filemanager-/", $file))
				@unlink($tmp_folder.$file);
		
		$tmp_folder .= "filemanager/";
		$descriptor = $this -> openFolder($tmp_folder);
		
		while(false !== ($file = readdir($descriptor)))
			if(preg_match("/^tmp-filemanager-/", $file))
				@unlink($tmp_folder.$file);		
	}
			
	public function pasteFromBuffer()
	{
		clearstatcache();
		
		if(!isset($_SESSION['mv']['file-manager']['buffer']['type'], $_SESSION['mv']['file-manager']['buffer']['value']))
			return false;
		
		//Pastes file from buffer into current folder.
		$path_file = $_SESSION['mv']['file-manager']['buffer']['value']; //Full path of file
		
		//Copy from buffer into current folder
		if(is_file($path_file) && $_SESSION['mv']['file-manager']['buffer']['type'] == 'copy')
		{
			if(file_exists($this -> path.basename($path_file)))
				return "error=file-exists";
			
			$result = copy($path_file, $this -> path.basename($path_file));
			
			if($result)
				$_SESSION['mv']['just-uploaded-file'] = basename($path_file);
				
			return $result;
		}
		//Copy from buffer into current dir and remove file from parent dir
		else if($_SESSION['mv']['file-manager']['buffer']['type'] == 'cut')
		{
			if(is_file($path_file))
			{
				if(file_exists($this -> path.basename($path_file)))
					return "error=file-exists";
					
				$result = rename($path_file, $this -> path.basename($path_file));
				
				if($result)
					$_SESSION['mv']['just-uploaded-file'] = basename($path_file);					
					
				return $result;
			}
			else if(is_dir($path_file))
			{
				$name = preg_replace("/^.*\/([^\/]+)\/?$/", "$1", $path_file);

				if(file_exists($this -> path."/".$name))
					return "error=folder-exists";
					
				return rename($path_file, $this -> path.$name);
			}
			
			$_SESSION['mv']['file-manager']['buffer'] = array();
		}
	}
		
	public function renameFileOrFolder($old_name, $new_name)
	{
		if(!file_exists($this -> path.$old_name))
			return "error=failed";
			
		if(is_file($this -> path.$old_name))
		{
			if(file_exists($this -> path.$new_name))
				return "error=file-exists";
			else if(!$this -> checkFileExtension($new_name))
				return "error=bad-extetsion";
			else if(preg_match("/[^\w-\.]/", $new_name))
				return "error=bad-file-name";
				
			$_SESSION['mv']['just-uploaded-file'] = basename($new_name);	
		}
		else if(is_dir($this -> path.$old_name))
		{
			if(file_exists($this -> path.$new_name))
				return "error=folder-exists";
			else if(preg_match("/[^\w-]/", $new_name))
				return "error=bad-folder-name";				
		}
		
		return rename($this -> path.$old_name, $this -> path.$new_name);
	}
	
	public function checkFileExtension($name)
	{
		$extension = Service :: getExtension($name);
		
		return ($extension && in_array($extension, $this -> registry -> getSetting('AllowedFiles')));
	} 
	
	public function reload()
	{
		$arguments = func_get_args();
		$params = "";
		
		if(isset($arguments[0]) && $arguments[0])
			$params = "?".$arguments[0];
		
		header("Location: ".$_SERVER['PHP_SELF'].$params);
		exit();
	}
	
	static public function cleanModelImages($path) 
	{
		//Deletes temporary images which are not reladted to any image from main folder
		//There must be dir with initial images and dirs like tmp, tmpsmall with tumbs

		if(!is_dir($path))
			return;
		
		clearstatcache();
		
		$tmp_folders = $parents = []; //Folders with temporary images and array of deleted images in main folder
		$dir = opendir($path);

		$registry = Registry :: instance();
		$step = (int) $registry -> getDatabaseSetting("admin_cleanup_step");
		$start = $step * self :: $cleanup_limit;
		$stop = $start + self :: $cleanup_limit;
		
		while(false !== ($file = readdir($dir)))
		{
			if($file == "." || $file == "..")
				continue;
			
			if(filetype($path.$file) == "dir") //Collects all temporary directories
				$tmp_folders[] = $file;
			
			if(filetype($path.$file) == "file") //Files (parents) of temporary copies
			{
				self :: $cleanup_count ++;

				if(self :: $cleanup_count < $start)
					continue;
				else if(self :: $cleanup_count > $stop)
					break;

				$parents[] = $file;
			}
		}

		if(self :: $cleanup_count > $stop)
		{
			$registry -> setDatabaseSetting("admin_cleanup_step", $step + 1);
			self :: $cleanup_count = "stop";
		}

		closedir($dir);
		
		foreach($tmp_folders as $folder) //Search the temporary copies of deleted file
		{
			$sub_dir = $path.$folder."/"; //Directory to open

			if(!is_dir($sub_dir))
				continue;

			$dir = opendir($sub_dir);
			
			while(false !== ($file = readdir($dir)))
			{
				if($file == "." || $file == ".." || filetype($sub_dir.$file) != "file")
					continue;
								
				$real_name = str_replace($folder."_", "", $file); //Takes real name of file

				if(!in_array($real_name, $parents)) //If the temp copy not related to any initial file we delete it
					@unlink($sub_dir.$file);
			}			
		    
			closedir($dir);
		}
	}
	
	static public function makeModelsFilesCleanUp()
	{
		$models = array_keys(Registry :: get('ModelsLower'));
		$path = Registry :: get("FilesPath")."models/";
		
		foreach($models as $model)
		{
			if(is_dir($path.$model."-images/"))
				Filemanager :: cleanModelImages($path.$model."-images/");

			if(self :: $cleanup_count == "stop")
				return;

			if(is_dir($path.$model."-files/"))
				Filemanager :: cleanModelImages($path.$model."-files/");

			if(self :: $cleanup_count == "stop")
				return;
		}

		if(self :: $cleanup_count != "stop")
			Registry :: setDatabaseSetting("admin_cleanup_step", 0);
	}
	
	static public function deleteOldFiles($path)
	{
		if(!is_dir($path))
			return;

		clearstatcache();
		$dir = opendir($path);
		
		if($dir)
			while(false !== ($file = readdir($dir)))
			{
				if($file == "." || $file == "..")
					continue;
					
				if(filetype($path.$file) == "file" && (time() - filemtime($path.$file)) >= 10800)
					@unlink($path.$file);
			}
	}

	static public function deleteResizedCopiesOfOneImage($image_path)
	{
		if(!is_file($image_path))
			return;

		clearstatcache();

		$path = dirname($image_path);
		$image_name = basename($image_path);
		$path = preg_replace("/\/$/", "", $path)."/";
		$tmp_folders = [];
		$dir = opendir($path);
		
		while(false !== ($file = readdir($dir)))
		{
			if($file == "." || $file == "..")
				continue;
			
			if(filetype($path."/".$file) == "dir")
				$tmp_folders[] = $file;			
		}

		closedir($dir);
		
		foreach($tmp_folders as $folder)
		{
			$sub_dir = $path.$folder."/";
			$dir = opendir($sub_dir);
			
			while(false !== ($file = readdir($dir)))
			{
				if($file == "." || $file == ".." || filetype($sub_dir.$file) != "file")
					continue;
								
				if($file == $image_name)
				{
					@unlink($sub_dir.$image_name);
					break;
				}
			}
		    
			closedir($dir);
		}		
	}
}
