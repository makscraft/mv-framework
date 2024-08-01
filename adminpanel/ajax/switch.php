<?
include "../../config/autoload.php";

$system = new System('ajax');
$system -> ajaxRequestContinueOrExit();

if(isset($_POST['switch-off']) && $_POST['switch-off'] == "warnings")
{
	$_SESSION['mv']['closed-warnings'] = true;
	echo "1";
}
?>