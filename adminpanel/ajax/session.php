<?
if(isset($_POST["check"]) || isset($_POST["continue"]) || isset($_POST["get-online-users"]))
{
	include "../../config/autoload.php";
	$system = new System("ajax");

	if(isset($_POST["check"]))
		echo $system -> ajaxRequestCheck() ? "1" : "";
	else if(isset($_POST["get-online-users"]))
	{
		$system -> ajaxRequestContinueOrExit();

		header('Content-Type: application/json');
		echo json_encode($system -> user -> session -> checkOnlineUsers());
	}
}
?>