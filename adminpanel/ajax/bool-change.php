<? 
include "../../config/autoload.php";

$system = new System('ajax');
$system -> ajaxRequestContinueOrExit();

if(isset($_POST["id"], $_POST["admin-panel-csrf-token"]) && $_POST["admin-panel-csrf-token"] == $system -> getToken())
{
	$id = intval(preg_replace("/.*-(\d+)-.*/", "$1", $_POST['id']));
	$model = preg_replace("/.*-\d+-(.*)$/", "$1", $_POST['id']);
	$field = preg_replace("/^(.*)-\d+-.*$/", "$1", $_POST['id']);
	
	if($registry -> checkModel($model))
	{
		$system -> runModel($model);
		
		if($system -> model -> checkRecordById($id) && $system -> model -> checkIfFieldEditable($field) && 
		   $system -> model -> checkIfFieldVisible($field) && 
		   $system -> model -> checkDisplayParam('update_actions') && 
		   $system -> user -> checkModelRights($system -> model -> getModelClass(), "update"))
		{
			$system -> model -> setId($id) -> read();
			$element = $system -> model -> getElement($field);
			
			if($element -> getType() != "bool" || 
			   ($model == "users" && ($id == 1 || $id == $system -> user -> getId())) || 
			   !$element -> getProperty("quick_change"))
				exit();
								
			$value = $element -> getValue() ? 0 : 1;
			$argument = ($model == "users") ? 'self-update' : null;

			$system -> model -> setValue($field, $value) -> update($argument) -> read();
			
			if($system -> model -> getValue($field) != $value)
				exit();
			
			$css_class = $value ? "bool-true" : "bool-false";
			$bool_title = $value ? "switch-off" : "switch-on";
			
			header('Content-Type: application/json');
			echo json_encode([
				'css_class' => $css_class,
				'title' => I18n :: locale($bool_title)
			]);
		}
	}
}
?>