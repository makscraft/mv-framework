<?
include_once "../../config/autoload.php";
$system = new System();

$file = isset($_GET["view"]) ? preg_replace("/[^\w-]/", "", $_GET["view"]) : false;

$old_path = $system -> registry -> getSetting("IncludePath")."models/customs/";
$new_path = $system -> registry -> getSetting("IncludePath")."customs/adminpanel/";

if(!$file || (!is_file($old_path.$file.".php") && !is_file($new_path.$file.".php")))
	$system -> displayInternalError("error-page-not-found");
else
	include_once is_file($new_path.$file.".php") ? $new_path.$file.".php" : $old_path.$file.".php";
?>