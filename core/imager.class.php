<?php
/**
 * Resizes and/or cuts the images to needed size for thumbnails.
 * Calculates the ratio of width and height.
 * Creates new small images depending on image type.
 */
class Imager
{
   /**
    * Initial image file location.
    * @var string
    */
   private $image;
   
   /**
    * Type (extension) of image .jpg, .gif or .png
    * @var string
    */
   public $type;
   
   /**
    * Width of inintial image in pixels.
    * @var int
    */
   private $width;
   
   /**
    * Height of inintial image in pixels.
    * @var int
    */
   private $height;
   
   /**
    * Quality of created jpg images.
    * @var int
    */   
   private $jpg_quality = 90;
   
   /**
    * Internal flag says if the file was created erlier.
    * @var bool
    */
   private $created_erlier = false;
      
   public function __construct(string $image = '')
   {
      //At the begining we should get our image, it's type and size    
      if($image !== '' && is_file($image))
      {
         $this -> image = $image; //Sets the current image
         $image_params = $this -> defineImageParams($this -> image);
         
         if(!is_array($image_params))
         {
         	$this -> type = null;
         	return;
         }
         
         $this -> type = $image_params["type"];
         $this -> width = $image_params["width"];
         $this -> height = $image_params["height"];
      }
      else
         $this -> type = null;
      
      if($quality = intval(Registry :: get('JpgQuality')))
         $this -> jpg_quality = $quality;
   }
   
   public function setImage($image)
   {
      //If we deside to get another image after the Imager object is constructed
      self :: __construct($image);
   }
   
   public function wasCreatedErlier()
   {
   	return $this -> created_erlier;
   }
   
   public function prepare($location, $prefix)
   {
      $this -> setImage($location);
      
      $path = dirname($location)."/".$prefix."/"; //Folder name
      $file = basename($location); //File name
      $new_file = $path.$file; //Path of small copy of image
            
      if(!is_dir($path))
          @mkdir($path);
      
      return $new_file;
   }
   
   public function removePngTransparency($pattern)
   {
	   imagealphablending($pattern, false);
	   imagesavealpha($pattern, true);
	   $transparent = imagecolorallocatealpha($pattern, 255, 255, 255, 127);
	   imagefill($pattern, 0, 0, $transparent);
	   
	   return $pattern;
   }
   
   public function defineImageParams($image)
   {
      $type = $width = $height = false;
      $image_params = @getimagesize($image);

      if(isset($image_params['mime']))
      {
         //Defines the mime type of the image
         switch($image_params['mime']) 
         {
            case('image/gif'): $type = "gif";
               break;
            case('image/jpeg'): $type = "jpg";
               break;
            case('image/png'): $type = "png";
               break;
            case('image/webp'): $type = "webp";
               break;
            default: $type = false;
               return false;      
         }
            
         $width = $image_params[0];
         $height = $image_params[1];
      }
      else
         return false;

      return ["type" => $type, "width" => $width, "height" => $height];
   }
   
   private function createImageFromSource($type, $image)
   {
      switch($type)
      {
         case('gif'):
            return imagecreatefromgif($image);
         case('jpg'):
            return imagecreatefromjpeg($image);
         case('png'):
            return imagecreatefrompng($image);
         case('webp'):
            return imagecreatefromwebp($image);
      }
   }
   
   private function saveNewImage($type, $image, $file_name)
   {
      switch($type)
      {
         case('gif'): imagegif($image, $file_name);
            break;
         case('jpg'): imagejpeg($image, $file_name, $this -> jpg_quality);
            break;
         case('png'): imagepng($image, $file_name);
            break;
         case('webp'): imagewebp($image, $file_name);
            break;            
      }
   }
   
   public function compress($location, $prefix, $width, $height)
   {
      //Main function which executes the resize process with image
      //It creates new image or copies old one if it is smaller than we need

      if(!$location || !is_file($location))
         return '';

      if(Service :: getExtension($location) == "svg" && mime_content_type($location) == "image/svg+xml")
         return Service :: removeDocumentRoot($location); //We don't resize svg images

   	$new_file = $this -> prepare($location, $prefix);
   	  
   	if(is_file($new_file)) //If the small copy already exists
   	{
         $this -> created_erlier = true;
         return Service :: removeDocumentRoot($new_file);
   	}
      
      if(!$this -> type || !$this -> image)
         return '';
      
      if($this -> width > $width || $this -> height > $height)
         $ratio = min($width / $this -> width, $height / $this -> height); //Defines the ratio
      else
         $ratio = 0; //If our image is smaller than the potential frame we set ratio to 0
      
      if($ratio)
      {
         $new_width = intval($ratio * $this -> width); //Sets new dimentions
         $new_height = intval($ratio * $this -> height);
      }
      else if($this -> type) //If our image is smaller than the potential frame we just copy it whitout resizing
      {
         @copy($this -> image, $new_file);
         return Service :: removeDocumentRoot($new_file);
      }
      else
         return '';
      
      $pattern = imagecreatetruecolor($new_width, $new_height); //We create a new clear image
      
      //Depending on the type of image we run the compression function
      switch($this -> type)
      {
         case('gif'):
         		   $image = imagecreatefromgif($this -> image);
                  imagecopyresampled($pattern, $image, 0, 0, 0, 0, $new_width, $new_height, $this -> width, $this -> height);
                  imagegif($pattern, $new_file);
                  break;
         case('jpg'): 
                  $image = imagecreatefromjpeg($this -> image);
                  imagecopyresampled($pattern, $image, 0, 0, 0, 0, $new_width, $new_height, $this -> width, $this -> height);
                  imageinterlace($pattern, 1); //Sets interlacing to image
                  imagejpeg($pattern, $new_file, $this -> jpg_quality);
                  break;
         case('png'):
         		   $pattern = $this -> removePngTransparency($pattern);
                  $image = imagecreatefrompng($this -> image);
                  imagecopyresampled($pattern, $image, 0, 0, 0, 0, $new_width, $new_height, $this -> width, $this -> height);
                  imagepng($pattern, $new_file);
                  break;
         case('webp'):
                  if(!function_exists('imagecreatefromwebp') || !function_exists('imagewebp'))
                     return '';

                  $image = imagecreatefromwebp($this -> image);
                  imagecopyresampled($pattern, $image, 0, 0, 0, 0, $new_width, $new_height, $this -> width, $this -> height);
                  imagewebp($pattern, $new_file);
                  break;                  
         default:
         	return '';
      }
      
      imagedestroy($pattern); //Delete used pattern
      
      return Service :: removeDocumentRoot($new_file);
   }
   
   public function crop($location, $prefix, $width, $height)
   {
      if(!$location || !is_file($location))
         return '';

      if(Service :: getExtension($location) == "svg" && mime_content_type($location) == "image/svg+xml")
         return Service :: removeDocumentRoot($location); //We don't resize svg images
            
   	$new_file = $this -> prepare($location, $prefix);
   	  
   	if(is_file($new_file)) //If the small copy already exists
   	{
         $this -> created_erlier = true;
         return Service :: removeDocumentRoot($new_file);
   	}
   	  	
      if(!$this -> type || !$this -> image) 
         return '';

      if($this -> type == 'webp')
         if(!function_exists('imagecreatefromwebp') || !function_exists('imagewebp'))
            return '';
		 
       //If our image is smaller than the potential frame we just copy it whitout croping
      if($this -> width <= $width && $this -> height <= $height)
      {      	 
         @copy($this -> image, $new_file);
         return Service :: removeDocumentRoot($new_file);
      }		 
   	
      $ratio = max($width / $this -> width, $height / $this -> height);
      
      $new_width = intval($this -> width * $ratio);
      $new_height = intval($this -> height * $ratio);
      
      $x_point = ($new_width > $width) ? intval(($new_width - $width) / 2)  : 0;
      $y_point = ($new_height > $height) ? intval(($new_height - $height) / 2)  : 0;
      
      $pattern = imagecreatetruecolor($new_width, $new_height);
      $croped_image = imagecreatetruecolor($width, $height);
      
      if($this -> type == "png")
      {
         $pattern = $this -> removePngTransparency($pattern);
         $croped_image = $this -> removePngTransparency($croped_image);
      }
      
      $image = $this -> createImageFromSource($this -> type, $this -> image);

      imagecopyresampled($pattern, $image, 0, 0, 0, 0, $new_width, $new_height, $this -> width, $this -> height);
      
      if($this -> type == "jpg")
         imageinterlace($pattern, 1); //Interlasing for jpg
	  
      //Cropping process
      imagecopy($croped_image, $pattern, 0, 0, $x_point, $y_point, $new_width, $new_height);	  
            
      $this -> saveNewImage($this -> type, $croped_image, $new_file);  
      
      imagedestroy($pattern); //Delete used patterns
      imagedestroy($croped_image);
	  
      return Service :: removeDocumentRoot($new_file);
   }
   
   public function addWatermark($image, $stamp, $margin_top, $margin_bottom, $margin_left, $margin_right)
   {
      $stamp = Service :: addFileRoot($stamp);      
      $file_name = $image = Service :: addFileRoot($image);
      $image_params = $this -> defineImageParams($image);
      
      if(!$image_params["type"]) 
         return '';
      
      $image = $this -> createImageFromSource($image_params["type"], $image);
      $stamp_params = $this -> defineImageParams($stamp);
       
      if(is_file($stamp) && $stamp_params)
      {
         $stamp = $this -> createImageFromSource($stamp_params["type"], $stamp);
         $stamp_width = imagesx($stamp);
         $stamp_height = imagesy($stamp);
         $image_width = imagesx($image);
         $image_height = imagesy($image);
               
         $position_x = ($image_width - $stamp_width) / 2;
         $position_y = ($image_height - $stamp_height) / 2;
         
         if(is_numeric($margin_right))
            $position_x -= $margin_right;
         else if(is_numeric($margin_left))
            $position_x = $margin_left;
               
         if(is_numeric($margin_bottom))
            $position_y -= $margin_bottom;
         else if(is_numeric($margin_top))
            $position_y = $margin_top;
               
         imagecopy($image, $stamp, $position_x, $position_y, 0, 0, $stamp_width, $stamp_height);
      }
      
      $this -> saveNewImage($image_params["type"], $image, $file_name);
   }
}
