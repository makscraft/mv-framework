<?
include "../../config/autoload.php";

$system = new System('ajax');
$system -> ajaxRequestContinueOrExit();
	
$filemanager = new Filemanager();
$filemanager -> setUser($system -> user);

if(isset($_POST['number_files']) && intval($_POST['number_files']))
{
	header("Content-Type: text/plain");

	//Translates the string with plural rules
	echo I18n :: locale('number-files', array('number' => intval($_POST['number_files']), 'files' => '*number'));
	exit();
}

if(isset($_POST['show-image'])) //Check if we have file to show
{
	header("Content-Type: text/html"); //We will give html back
	
	echo "<tr><td colspan=\"2\" id=\"file-image\">\n";
	echo $filemanager -> displayImage($_POST['show-image']); //Output of image of text abiut no image
	echo "</td></tr>\n";
	echo "<tr><td colspan=\"2\" id=\"file-data\">\n<table>\n";
	echo $filemanager -> displayFileParams($_POST['show-image']); //Output of file parameters
	echo "</table></td></tr>\n";
	exit();
}

//Adds new file into buffer
if(isset($_POST['type'], $_POST['value']))
{
	if(!$system -> user -> checkModelRights('file_manager', 'update'))
		exit("no-rights");

	//If type is correct we add data into buffer
	if(($_POST['type'] == 'cut' || $_POST['type'] == 'copy') && file_exists($_POST['value']))
		$_SESSION['mv']['file-manager']['buffer'] = array('type' => $_POST['type'], 'value' => $_POST['value']);
	
	echo "1";
}
?>