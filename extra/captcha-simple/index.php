<?
session_start();

$img = imagecreatetruecolor(120, 27);
$back = imagecolorallocate($img, 255, 255, 255);
imagefill($img, 0, 0, $back);

$str = "";
$chars = array('a','b','c','d','e','f','g','h','i','j','k','m','n','p','q','r',
			   's','t','u','v','w','x','y','z','2','3','4','5','6','7','8','9');

$number = count($chars) - 1;
for($i = 0; $i < mt_rand(3,4); $i ++)
	$str .= $chars[mt_rand(0, $number)];
		
$number = strlen($str);
for($i = 0; $i < $number; $i ++)
{
	$colour = imagecolorallocate($img, 129, 2, 5); //Can be used mt_rand(100, 255)
	$x = mt_rand(2, 12) + $i * 20;
	$y = mt_rand(1, 10);
	imagechar($img, 5, $x, $y, $str[$i], $colour);
}

//The captcha image should not be cached
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate'); 
header('Cache-Control: post-check=0, pre-check=0', FALSE); 
header('Pragma: no-cache');
header("Content-Type: image/gif");

imagegif($img);
		
$_SESSION['captcha'] = $str;
?>