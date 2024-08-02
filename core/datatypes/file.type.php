<?php
/**
 * File datatype class. Manages files inputs and file uploading.
 */
class FileModelElement extends CharModelElement
{
	protected $file_name;
	
	protected $allowed_extensions = [];
	
	protected $allowed_mime_types = [];
	
	protected $files_folder;
	
	protected $transform_file_name = true;
	
	protected $max_size;

	protected $multiple;

	protected $multiple_files = [];

	protected $max_width;
	
	protected $max_height;	
	
	public function setAllowedExtensions($values)
	{
		if(is_array($values))
			$this -> allowed_extensions = $values;
 
		return $this;
	}
	
	public function setAllowedMimeTypes($values)
	{
		if(is_array($values))
			$this -> allowed_mime_types = $values;
 
		return $this;
	}
		
	public function validate()
	{
		if(!$this -> error)
		{
			$this -> unique = false;
			parent :: validate();
		}
			
		return $this;
	}

	public function displayAdminFilter(mixed $data)
	{
		return BoolModelElement  :: createAdminFilterHtml($this -> name, $data);
	}
	
	public function defineFolderEnd()
	{
		$class_lower = strtolower(get_class($this));
		$name = str_replace(['modelelement', '_model_element'], '', $class_lower);

		return $name.'s';
	}

	public function packUploadedFileData()
	{
		$token = Registry :: get('SecretCode');
		$token = Service :: createHash($this -> file_name.$token.$this -> value);

		$value = [$this -> file_name, $this -> value, $token];
		
		return Service :: encodeBase64(json_encode($value));
	}

	public function unpackUploadedFileData(string $value)
	{
		$value = json_decode(Service :: decodeBase64($value), true);
		
		if(!is_array($value) || count($value) !== 3 || !is_file($value[1]))
			return null;

		$token = Registry :: get('SecretCode');
		$token = Service :: createHash($value[0].$token.$value[1]);

		if($token === $value[2])
			return ['name' => $value[0], 'path' => $value[1]];
		
		return null;
	}

	public function displayHtml()
	{
		$html = '';
		$arguments = func_get_args();
		$form_frontend = (isset($arguments[0]) && $arguments[0] == "frontend");

		if($this -> value && file_exists($this -> value))
		{
			$size = $this -> file_name ? " (".I18n :: convertFileSize(filesize($this -> value)).")" : "";
			
			$css_class = $form_frontend ? "file-params" : "file-input";
			
			$html = "<div class=\"".$css_class."\">\n<span class=\"file\">".$this -> file_name;
			$html .= $size."</span>\n";
			$html .= "<input type=\"hidden\" value=\"".$this -> packUploadedFileData()."\" ";
			$html .= "name=\"value-".$this -> name."\" />\n";
			
			if($form_frontend)
				$html .= "<span class=\"delete file\">".I18n :: locale('delete')."</span>\n";
			else
				$html .= "<span class=\"delete\" title=\"".I18n :: locale('delete')."\"></span>\n";
						
			if(!$form_frontend)
			{
				$html .= "<p><a target=\"_blank\" class=\"download\" href=\"";
				$html .= Service :: removeDocumentRoot($this -> value)."\">";
				$html .= I18n :: locale('view-download')."</a></p>\n";
			}
			
			$html .= "</div>\n";
		}
		
		$css_class = ($this -> value && file_exists($this -> value)) ? ($form_frontend ? " hidden" : " no-display") : "";
		
		$html .= "<div class=\"file-input".$css_class."\"".$this -> addHtmlParams().">\n";
		$html .= "<input type=\"file\" name=\"".$this -> name."\" value=\"\" />\n";
		$html .= $this -> addHelpText()."</div>\n";
		
		return $html;
	}

	public function displayMultipleHtml()
	{
		$html = "<div class=\"multiple-files-data\">\n";
		$salt = Registry :: get('SecretCode');

		foreach($this -> multiple_files as $file)
		{
			$html .= "<div class=\"file-element\">\n";
			$html .= "<span class=\"file\">".$file["name"]."</span>\n";
			$html .= "<span class=\"delete multiple-file\">".I18n :: locale('delete')."</span>\n";
			$html .= "<input type=\"hidden\" value=\"".Service :: serializeArray($file)."\" ";
			$html .= "name=\"multiple-".$this -> name."-".md5($file["file"].$salt)."\" />\n";			
			$html .= "</div>\n";
		}

		$html .= "<div class=\"file-input\"".$this -> addHtmlParams().">\n";
		$html .= "<input type=\"file\" multiple name=\"".$this -> name."[]\" value=\"\" />\n";
		$html .= $this -> addHelpText()."</div>\n";		

		return $html."</div>\n";
	}
	
	public function setRealValue($value, $file_name)
	{
		if(self :: checkTmpFileBeforeUpload($value) === false)
			return;
		
		if(is_file($value))
		{
			$this -> value = $value;
			$this -> file_name = $file_name;
		}
		else
			$this -> value = $this -> file_name = '';
	}
	
	public function setValue($file_data)
	{
		if(!isset($file_data['name'], $file_data['tmp_name']) || !$file_data['name']) //File was not uploaded
		{
			$this -> value = $this -> file_name = '';
			$this -> multiple_files = [];
			
			return $this;
		}
		
		$image_type = (get_class($this) == 'ImageModelElement');		
		$registry = Registry :: instance();
		$extension = Service :: getExtension($file_data['name']);
		
		$max_image_size = $this -> max_size ? $this -> max_size : $registry -> getSetting("MaxImageSize");
		$max_file_size = $this -> max_size ? $this -> max_size : $registry -> getSetting("MaxFileSize");

		if($image_type)
		{
			$max_image_width = $this -> max_width ? $this -> max_width : $registry -> getSetting("MaxImageWidth");
			$max_image_height = $this -> max_height ? $this -> max_height : $registry -> getSetting("MaxImageHeight");
		}
		
		if(isset($file_data['error']) && $file_data['error']) //File uploading error process
		{
			if($file_data['error'] == 1) //If file is too heavy
				if($image_type)
					$this -> error = $this -> chooseError("max_size", '{too-heavy-image}');
				else
					$this -> error = $this -> chooseError("max_size", '{too-heavy-file}');
		}		
		else if(count($this -> allowed_extensions) && !in_array($extension, $this -> allowed_extensions))
			$this -> error = $this -> chooseError("allowed_extensions", "{wrong-".($image_type ? "images" : "file")."-type}");
		else if(count($this -> allowed_mime_types) && !in_array($file_data['type'], $this -> allowed_mime_types))
			$this -> error = $this -> chooseError("allowed_mime_types", "{wrong-files-type}");
		else if($image_type)
		{
			$default_images_mimes = $registry -> getSetting('DefaultImagesMimeTypes');
			
			if(!count($this -> allowed_extensions) && !in_array($extension, $registry -> getSetting('AllowedImages')))
				$this -> error = '{wrong-images-type}';					
			else if(!count($this -> allowed_mime_types) && !in_array($file_data['type'], $default_images_mimes))
				$this -> error = '{wrong-files-type}';
			else if($file_data['size'] > $max_image_size)
				$this -> error = $this -> chooseError("max_size", '{too-heavy-image}');
			else if($extension != "svg")
			{
				$size = @getimagesize($file_data['tmp_name']);
				
				//Takes size of image and checks for too big images
				if($size[0] > $max_image_width)
					$this -> error = $this -> chooseError("max_width", "{too-large-image}");
				else if($size[1] > $max_image_height)
					$this -> error = $this -> chooseError("max_height", "{too-large-image}");
		  	}
		}
	  	else if(!$image_type)
	  	{
			if(!count($this -> allowed_extensions) && !in_array($extension, $registry -> getSetting('AllowedFiles')))
				$this -> error = '{wrong-files-type}';
	  		else if($file_data['size'] > $max_file_size)
				$this -> error = $this -> chooseError("max_size", '{too-heavy-file}');
	  	}		
		
		if($this -> error) //If it was any type of error we don't copy the file and go back
			return;
		
		if($this -> transform_file_name)
			$initial_name = Service :: translateFileName($file_data['name']);
		else
			$initial_name = Service :: removeExtension(trim($file_data['name']));
		
		$tmp_name = Service :: randomString(30); //New name of file
			
		$this -> file_name = $file_data['name']; //Pass the name of file
		
		if($initial_name) //Add name of file in latin letters
			$tmp_name = $initial_name."-".$tmp_name;
			
		//Path to copy the file
		$this -> value = $registry -> getSetting('FilesPath')."tmp/".$tmp_name.".".$extension;
		
		if(is_uploaded_file($file_data['tmp_name']))
			move_uploaded_file($file_data['tmp_name'], $this -> value); //Copy the file into temorary folder
        	
        return $this;
	}

	static public function checkTmpFileBeforeUpload($file)
	{
		if(!is_string($file) || $file === '')
			return false;

		$tmp_folder = Service :: prepareRegularExpression(Registry :: get('FilesPath').'tmp/');
		$extension = Service :: getExtension($file);
		$deny = ['phtml', 'php', 'php3', 'php4', 'php5', 'inc', 'pl', 'pm', 'cgi', 'lib', 'py', 'asp', 'aspx', 
				 'jsp', 'jspx', 'jsw', 'jsv', 'jspf', 'cfm', 'cfml', 'cfc', 'dbm'];

		if(in_array($extension, $deny))
			return false;

		if(strpos($file, '..') !== false || strpos($file, './') !== false || !is_file($file))
			return false;

		if(!preg_match('/^'.$tmp_folder.'[^\/]+$/', $file))
			return 'moved';

		return true;
	}
	
	public function copyFile(string $model_name = '')
	{
		$check = self :: checkTmpFileBeforeUpload($this -> value);

		//If we don't have the image file or it's the alredy uploaded image
		if($check === false)
			return '';
		else if($check === 'moved')		
			return $this -> value;
		
		$registry = Registry :: instance();
	
		//Name of current model, or we upload via front form without model
		$model_name = $model_name !== '' ? strtolower($model_name) : '';
		
		//Folder to copy file
		if($model_name === '')
		{
			$folder = preg_replace("/^\/?(.*)\/?$/", "$1", $this -> files_folder);
			$path = $registry -> getSetting('FilesPath').$folder."/";
		}
		else //Admin panel file uploading
			$path = $registry -> getSetting('FilesPath')."models/".$model_name."-".$this -> defineFolderEnd()."/";
		
		$counter = intval($registry -> getDatabaseSetting('files_counter')) + 1;
		
		if(strpos(basename($this -> value), "-") !== false)
		{
			$new_value = Service :: removeExtension(basename($this -> value));
			$new_value = substr($new_value, 0, -31);
			
			$extension = Service :: getExtension($this -> value);
			$extension = ($extension === "jpeg") ? "jpg" : $extension;
			
			$check_new_value = $path.$new_value.".".$extension;
			
			if(file_exists($check_new_value))
				$new_value = $path.$new_value."-f".$counter.".".$extension;
			else
				$new_value = $check_new_value;
		}
		else
			$new_value = $path."f".$counter.".".Service :: getExtension($this -> value); //Simple name of file
		
		if(!file_exists($new_value)) //If this file was not copied before
		{
			if(!is_dir($path)) //Makes the target folder if needed
				@mkdir($path);
				
			if(is_file($this -> value)) //Moves the file to the target folder
				@rename($this -> value, $new_value);
		}
		
		$registry -> setDatabaseSetting('files_counter', $counter);
		$this -> value = $new_value;
		
		return $new_value;
	}

	public function removeFileRoot()
	{
		$this -> value = Service :: removeFileRoot($this -> value);
	}

	public function deleteFile($file)
	{
		if(is_file($file))
			@unlink($file);			
	}

	//Multiple files processing

	public function getMultipleFiles()
	{
		return $this -> multiple_files;
	}

	public function getMultipleFilesPaths()
	{
		$paths = [];

		foreach($this -> multiple_files as $file)
			$paths[] = $file['file'];

		return $paths;
	}	

	public function setMultipleFiles($files)
	{
		if(is_array($files))
			$this -> multiple_files = $files;

		$this -> value = count($this -> multiple_files) ? count($this -> multiple_files) : '';
	}

	public function addMultipleFile($object)
	{
		if(is_file($object -> getProperty("value")))
			$this -> multiple_files[] = [
				"name" => $object -> getProperty("file_name"),
				"file" => $object -> getValue()
			];

		$this -> value = count($this -> multiple_files) ? count($this -> multiple_files) : '';

		return $this;
	}
}
