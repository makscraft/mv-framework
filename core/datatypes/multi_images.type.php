<?php
/**
 * Multi images datatype class. Keeps images gallery in the one SQL table cell. 
 */
class MultiImagesModelElement extends CharModelElement
{
	protected $max_size = false;
	
	protected $max_width = false;
	
	protected $max_height = false;
	
	protected $allowed_extensions = [];
	
	protected $allowed_mime_types = [];

	protected $files_folder;
	
	public function getOverriddenProperty($property)
	{
		$registry = Registry :: instance();
		
		$settings = ["max_size" => "MaxImageSize", 
					 "max_width" => "MaxImageWidth", 
					 "max_height" => "MaxImageHeight", 
					 "allowed_extensions" => "AllowedImages", 
					 "allowed_mime_types" => "DefaultImagesMimeTypes"];
		
		if(isset($this -> $property) && array_key_exists($property, $settings))
			if(is_array($this -> $property))
				return count($this -> $property) ? $this -> $property : $registry -> getSetting($settings[$property]);
			else
				return $this -> $property ? $this -> $property : $registry -> getSetting($settings[$property]);
	}
	
	public function validate()
	{
		if($this -> error)
			return $this;

		$this -> unique = false;
		parent :: validate();

		$images = self :: unpackValue($this -> value);
		$checked = [];
		
		foreach($images as $image)
			if(is_file($image['image']) && @getimagesize($image['image']))
				$checked[] = $image;

		$this -> value = json_encode($checked);

		return $this; 
	}

	public function displayAdminFilter(mixed $data)
	{
		return BoolModelElement :: createAdminFilterHtml($this -> name, $data);
	}
	
	public function displayHtml()
	{
		$arguments = func_get_args();
		
		if(isset($arguments[0]) && $arguments[0] == "frontend")
			return $this -> displayHtmlInFrom();
		
		$html = "<div class=\"images-area\" id=\"area-images-".$this -> name."\">\n";
		
		if($this -> value == '' || $this -> value == '[]')
			$html .= "<p class=\"no-images\">".I18n :: locale('no-images')."</p>\n";

		$html .= "<div class=\"uploaded-images\">\n";
		$images = self :: unpackValue($this -> value);
		$imager = new Imager();
		
		foreach($images as $image)
		{
			$comment = $image['comment'] ?? '';
			$image = $image['image'];
			
			if(is_file($image))
			{
				$src = $imager -> compress($image, 'admin', 150, 150);

				$html .= "<div class=\"images-wrapper\">\n";
				$html .= "<div class=\"controls\" id=\"".$image."\">\n";
				$html .= "<span class=\"first\" title=\"".I18n :: locale("move-first")."\"></span> ";
				$html .= "<span class=\"left\" title=\"".I18n :: locale("move-left")."\"></span>";
				$html .= "<span class=\"right\" title=\"".I18n :: locale("move-right")."\"></span> ";
				$html .= "<span class=\"last\" title=\"".I18n :: locale("move-last")."\"></span>";
				$html .= "<span class=\"comment\" title=\"".I18n :: locale("add-edit-comment")."\"></span>";
				$html .= "<span class=\"delete\" title=\"".I18n :: locale("delete")."\"></span>";					
				$html .= "</div>\n";
				$html .= "<a href=\"".Registry :: get("MainPath");
				$html .= Service :: removeFileRoot($image)."\" target=\"_blank\">\n";
				$html .= "<img src=\"".$src."\" alt=\"".basename($image)."\" title=\"".$comment."\" /></a></div>\n";
			}
		}
		
		$html .= "</div>\n";
		$maximum = ini_get("max_file_uploads");
		$maximum = $maximum <= 20 ? $maximum : 20;
		
		$html .= "<div class=\"upload-buttons\">\n<div class=\"upload-one\" ";
		$html .= "id=\"max-quantity-".$maximum."\">\n";
		$html .= "<p class=\"upload-text\">".I18n :: locale('maximum-files-one-time', ["number" => $maximum])."</p>\n";
		$html .= "<input type=\"file\" multiple id=\"multi-images-".$this -> name."\" ";
		$html .= "name=\"multi-images-".$this -> name."[]\" />\n";
		$html .= "<div class=\"loading\"></div>\n";
		$html .= "<input type=\"hidden\" name=\"".$this -> name."\" value=\"".base64_encode(strval($this -> value))."\" /></div>\n";
		$html .= "</div></div>\n".$this -> addHelpText();
		
		return $html;
	}

	public function displayHtmlInFrom()
	{
		$images = self :: unpackValue($this -> value);

		if(count($images))
			$html = "<div class=\"form-multi-images-wrapper\">\n";
		else
			$html = '';

		$imager = new Imager();
		$salt = Registry :: get('SecretCode');

		foreach($images as $index => $image)
		{
			$src = $imager -> compress($image['image'], 'filemanager', 150, 150);
			$hash = md5($salt.$image['image']);
			
			$html .= "<div><img src=\"".$src."\" alt=\"".basename($image['image'])."\" />\n";
			$html .= "<span class=\"delete multiple-image\">".I18n :: locale('delete')."</span>\n";
			$html .= "<input type=\"hidden\" name=\"value-".$this -> name."-".($index + 1)."-".$hash."\" ";
			$html .= "value=\"".basename($image['image'])."\" />\n</div>\n";
		}

		if(count($images))
			$html .= "</div>\n";

		$pack = Service :: encodeBase64(strval($this -> value));

		$html .= "<input type=\"file\" multiple name=\"".$this -> name."[]\" id=\"multi-images-".$this -> name."\" />\n";
		$html .= "<input type=\"hidden\" name=\"value-".$this -> name."\" value=\"".$pack."\" />\n";

		return $html;
	}
	
	public function uploadImage($file_data, $value)
	{
		$input_value = self :: unpackValue($value);
		$extension = Service :: getExtension($file_data['name']);
		$extension = ($extension == "jpeg") ? "jpg" : $extension;
		
		if(!in_array($extension, $this -> getOverriddenProperty("allowed_extensions")) || 
		   !is_uploaded_file($file_data['tmp_name']) || 
		   !in_array($file_data['type'], $this -> getOverriddenProperty("allowed_mime_types")))
		{
			$this -> error = "wrong-images-type";
			return;
		}
		
		if((isset($file_data['error']) && $file_data['error'] == 1) || 
		   (isset($file_data['size']) && $file_data['size'] > $this -> getOverriddenProperty("max_size")))
		{
			$this -> error = "too-heavy-image";
			return;
		}
		
		$size = @getimagesize($file_data['tmp_name']);
		
		//Takes size of image and checks for too big images
		if(!$size || $size[0] > $this -> getOverriddenProperty("max_width") || 
			$size[1] > $this -> getOverriddenProperty("max_height"))
		{
			$this -> error = "too-large-image";
			return;
		}
		else
		{
			$initial_name = Service :: translateFileName($file_data['name']);
			$tmp_name = Service :: randomString(30); //New name of file
			
			if($initial_name) //Add name of file in latin letters
				$tmp_name = $initial_name."-".$tmp_name;
			
			$tmp_name = Registry :: get('FilesPath')."tmp/".$tmp_name.".".$extension;
           	move_uploaded_file($file_data['tmp_name'], $tmp_name);
           		
			$input_value[] = ['image' => $tmp_name, 'comment' => ''];
			
           	return [$tmp_name, $input_value];
		}
	}

	public function defineTargetFolder(string $model_name = '')
	{
		if($this -> files_folder)
		{
			$folder = preg_replace("/^\/?(.*)\/?$/", '$1', $this -> files_folder);
			return	Registry :: get('FilesPath').$folder.'/';
		}
		else
			return	Registry :: get('FilesPath').'models/'.$model_name.'-images/';
	}

	public function copyImages($model_name)
	{
		clearstatcache();
		
		$registry = Registry :: instance();	
		$images = self :: unpackValue($this -> value);
		$model_name = strtolower($model_name); //Name of current model
		
		$path = $this -> defineTargetFolder($model_name); //Folder to copy file
		$counter = intval($registry -> getDatabaseSetting('files_counter'));
		
		$moved_images = [];
		
		if(!is_dir($path)) 
			@mkdir($path);
				
		foreach($images as $image)
		{
			$comment = $image['comment'] ?? '';
			$image = $image['image'];
			$check = FileModelElement :: checkTmpFileBeforeUpload($image);
				
			if($check === true) //If image is located in temporary folder
			{
				if(strpos(basename($image), "-") !== false)
				{
					$new_value = Service :: removeExtension(basename($image));
					$new_value = substr($new_value, 0, -31);
					$check_new_value = $path.$new_value.".".Service :: getExtension($image);
			
					if(file_exists($check_new_value))
						$moved_image = $path.$new_value."-f".(++ $counter).".".Service :: getExtension($image);
					else
						$moved_image = $check_new_value;					
				}
				else
					$moved_image = $path."f".(++ $counter).".".Service :: getExtension(basename($image));
				
				if(!is_file($moved_image)) //Moves the file into model folder
					@rename($image, $moved_image);
						
				$moved_images[] = ['image' => $moved_image, 'comment' => $comment];
			}
			else if($check === 'moved') //Image already uploaded
				$moved_images[] = ['image' => $image, 'comment' => $comment];
			else
			{
				$image = Service :: addFileRoot($image);
				
				if(is_file($image))
					$moved_images[] = ['image' => $image, 'comment' => $comment];
			}
		}
		
		foreach($moved_images as $key => $image) //Cuts off the file root
			$moved_images[$key]['image'] = Service :: removeFileRoot($image['image']);
		
		$this -> value = json_encode($moved_images);
		$registry -> setDatabaseSetting('files_counter', $counter);
	}
	
	public function deleteImages($images)
	{
		$images = self :: unpackValue($images);
		
		foreach($images as $image)
		{
			$image = Service :: addFileRoot($image['images']);
			
			if(is_file($image))
				@unlink($image);
		}			
	}
	
	public function setValuesWithRoot($images)
	{
		$images = self :: unpackValue($images);
		
		foreach($images as $key => $image) //Add current system file root for images
		{
			$file = Service :: addFileRoot($image['image']);
			
			if(is_file($file))
				$images[$key]['image'] = $file;
			else
				unset($images[$key]);
		}
			
		$this -> value = json_encode($images);
	}
	
	static public function packValue(mixed $value)
	{
		if(!is_string($value) && !is_array($value))
			return '';

		$result = [];

		if(is_string($value))
			$result[] = ['image' => $value, 'comment' => ''];
		else
			foreach($value as $key => $data)
			{
				if(is_numeric($key) && is_string($data))
					$result[] = ['image' => $data, 'comment' => ''];

				if(is_array($data) && isset($data['image']) && is_string($data['image']))
					$result[] = ['image' => $data['image'], 'comment' => $data['comment'] ?? ''];
			}

		return count($result) ? json_encode($result) : '';
	}

	static public function unpackValue(mixed $value)
	{
		if(is_array($value))
			return $value;

		$value = trim(strval($value));

		if($value === '[]' || $value === '')
			return [];

		$result = [];
		
		if(strpos($value, '-*//*-') !== false || strpos($value, '(*') !== false)
		{
			$images = explode("-*//*-", $value);

			foreach($images as $image)
				if($image)
				{
					$comment = '';

					if(strpos($image, "(*") !== false)
					{
						$data = explode("(*", $image);
						$image = $data[0];
						$comment = str_replace("*)", "", $data[1]);
					}

					$result[] = ['image' => $image, 'comment' => $comment];
				}
		}
		else if(strpos($value, 'userfiles/') === 0)
			$result[] = ['image' => $value, 'comment' => ''];
		else if(strpos($value, '[') !== false)
			$result = json_decode($value, true);

		return $result;
	}

	public function processMultipleImagesInForm($images, $old_value)
	{
		$value = $errors = [];
		$old = self :: unpackValue($old_value);
		$old = is_array($old) ? $old : [];
		$salt = Registry :: get('SecretCode');

		foreach($old as $image)
			foreach($_POST as $key => $val)
				if(preg_match('/^value-'.$this -> name.'-\d+-\w+$/', $key) && $val == basename($image['image']))
				{
					$hash = preg_replace('/.*-(\w+)$/', '$1', $key);
					$check = md5($salt.$image['image']);

					if($hash === $check)
					{
						$value[] = $image;
						continue 2;
					}
				}
		
		foreach($images as $image)
		{
			$this -> error = '';
			$one = $this -> uploadImage($image, []);

			if(isset($one[0]) && is_file($one[0]) && $this -> error === '')
				$value[] = ['image' => $one[0], 'comment' => ''];

			if($this -> error !== '')
			{
				$error = [$this -> caption, '{'.$this -> error.'}', $this -> name];
				$error = Model :: processErrorText($error, $this);
				
				$errors[] = $image['name'].' '.$error;
			}
		}

		$this -> value = count($value) ? json_encode($value) : '';
		$this ->  error = implode('<br>', $errors);
	}
}
