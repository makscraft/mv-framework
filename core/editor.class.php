<?php
/**
 * WW editor class, created to add editor to the textareas in admin panel.
 */
class Editor
{
	/**
	 * Makes sure we already added base js scripts on the page.
	 * @var bool
	 */
   	private static $instance = false;

	static public function run(string $id, int $height)
	{
		$html = '';

		$region = Registry :: get('Region');
		$region = ($region == 'en' || $region == 'am' || $region == 'us') ? 'uk' : $region;
		$upload_path = Registry :: get('AdminPanelPath').'controls/upload.php?ck-image';
  
		if(!self :: $instance)
		{
			$path = Registry :: get("AdminPanelPath")."interface/ckeditor/";
			$html .= "<script type=\"text/javascript\" src=\"".$path."ckeditor.js\"></script>\n";
			$html .= "<script type=\"text/javascript\" src=\"".$path."translations/".$region.".js\"></script>\n";

			self :: $instance = true;
		}

		$html .= "<style type=\"text/css\"> textarea#".$id." + div .ck-editor__editable_inline{min-height: ".$height."px; max-height: 800px} </style>\n";
		$html .= "<script type=\"text/javascript\">
					ClassicEditor
					.create(document.querySelector('#".$id."'), {

						removePlugins: ['ImageInsert', 'MediaEmbedToolbar'],
						toolbar: {
							items: ['sourceEditing', 'undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 
									'underline', 'bulletedList', 'numberedList', 'blockQuote', 'link', 
									'fontColor', 'uploadImage', 'insertTable', 'mediaEmbed', 'code']
						},
						heading: {
							options: [
								{ model: 'paragraph', title: 'Paragraph' },
								{ model: 'heading1', view: 'h1', title: 'Heading 1' },
								{ model: 'heading2', view: 'h2', title: 'Heading 2' },
								{ model: 'heading3', view: 'h3', title: 'Heading 3' }
							]
						},
						image: { insert: {type: 'side'} },
						mediaEmbed: { previewsInData: true },
						language: '".$region."',
						htmlSupport: {allow: [{classes: true, styles: true}]},
						simpleUpload: {
							uploadUrl: '".$upload_path."',
							withCredentials: true
						}
					})
					.then(editor => {
						window.editor = editor;
					})
					.catch(err => {
						console.error(err.stack);
					});
				  </script>\n";   
		
		return $html;
	}
	
	static public function createFilesJSON()
	{
		$registry = Registry :: instance();
		$path = $registry -> getSetting("FilesPath")."tmp/files.json";
		$folder = $registry -> getSetting("FilesPath")."files/";
		$url = $registry -> getSetting("MainPath")."userfiles/files/";
		$json = [];
   	   
  		clearstatcache();
		
		$directory = @opendir($folder);
		
		if($directory)
			while(false !== ($file = readdir($directory)))
			{
				if($file == "." || $file == "..")
					continue;
				
				if(is_file($folder.$file))
				{
					$json[] = array("name" => "",
										 "title" => $file,
										 "link" => $url.$file, 
										 "size" => I18n :: convertFileSize(filesize($folder.$file)));	
				}
			}
				
		file_put_contents($path, json_encode($json));
  	}
   
	static public function createImagesJSON()
	{
		$registry = Registry :: instance();
		$path = $registry -> getSetting("FilesPath")."tmp/images.json";
		$folder = $registry -> getSetting("FilesPath")."images/";
		$url = $registry -> getSetting("MainPath")."userfiles/images/";
		$json = [];
		
		clearstatcache();
		
		$directory = @opendir($folder);
		$imager = new Imager();
		
		if($directory)
			while(false !== ($file = readdir($directory)))
			{
				if($file == "." || $file == "..")
					continue;
				
				if(is_file($folder.$file))
				{
					$extension = Service :: getExtension($file);
					
					if(!in_array($extension, $registry -> getSetting("AllowedImages")))
						continue;
					
					$tmp_name = $registry -> getSetting("FilesPath")."tmp/".$file;
					$thumb_name = $registry -> getSetting("FilesPath")."tmp/redactor/".$file;
					
					if(!is_file($thumb_name))
					{
						$imager -> setImage($folder.$file);
						@copy($folder.$file, $tmp_name);
						$thumb_name = $imager -> compress($tmp_name, "redactor", 100, 75);
						@unlink($tmp_name);
					}
					
					$json[] = array("thumb" => Service :: removeDocumentRoot($thumb_name),
										 "image" => $url.$file,
										 "title" => $file);
				}
				
			}
				
		file_put_contents($path, json_encode($json));
   }
}
