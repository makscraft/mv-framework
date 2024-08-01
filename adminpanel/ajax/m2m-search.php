<?
include "../../config/autoload.php";

$system = new System('ajax');
$system -> ajaxRequestContinueOrExit();

if(isset($_GET['model'], $_GET['field'], $_GET['query']) && $registry -> checkModel($_GET['model']))
{
	$system -> runModel($_GET['model']);
	$object = $system -> model -> getElement($_GET['field']);	
	$ids = (isset($_GET['ids']) && $_GET['ids']) ? explode(",", $_GET['ids']) : false;
	$self_id = (isset($_GET['self_id']) && $_GET['self_id']) ? intval($_GET['self_id']) : false;
	$request = htmlspecialchars(trim($_GET['query']), ENT_QUOTES);
	
	if(is_array($ids) && count($ids))
		foreach($ids as $key => $id)
			$ids[$key] = intval($id);
	
	header("Content-Type: text/html");
	
	if($object)
		echo $object -> getOptionsForSearch($request, $ids, $self_id);
};
?>