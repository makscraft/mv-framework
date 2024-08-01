<?
include "../../config/autoload.php";

$system = new System('ajax');
$system -> ajaxRequestContinueOrExit();
	
if(isset($_POST['model'], $_POST['admin-panel-csrf-token']) && $system -> registry -> checkModel($_POST['model']) && 
   trim($_POST['model']) != "users")
{
	$result = array("general_errors" => "", "updated" => 0, "wrong_fields" => []);
	$data = [];
	$system -> runModel($_POST['model']);

	$token = $system -> model -> getTable().$system -> user -> getField("login");
	$token = md5($token.$system -> user -> getField("password"));
	
	if($_POST["admin-panel-csrf-token"] != $system -> getToken())
		$result["general_errors"] = "<p>".I18n :: locale("error-wrong-token")."</p>";
	
	foreach($_POST as $key => $value) //Collecting fields and values for operation
		if(preg_match("/^quick-edit-.*-\d+$/", $key))
		{
			$values = explode("-", $key);
			$data[intval($values[3])][$values[2]] = trim($value);
		}
		
	$unique_fields = [];
	
	foreach($system -> model -> getElements() as $name => $object) //All fields which must have unique values
		if($object -> getProperty("unique"))
			$unique_fields[$name] = [];
			
	foreach($data as $id => $values) //Data processing and validation
	{
		if(!$system -> model -> checkRecordById($id))
			continue;
			
		$has_errors = $system -> model -> drop() -> read($id) -> getDataFromArray($values) -> validate(array_keys($values));
		
		if($has_errors) //In case of move of values of unique fields to avoid not correct errors
		{
			$errors = $system -> model -> getErrors();
			
			foreach($errors as $key => $error)
				if(is_array($error) && isset($error[1], $error[2]) && $error[1] == "{error-unique-value}")
				{
					$query = "SELECT `id` FROM `".$system -> model -> getTable()."` 
							  WHERE `".$error[2]."`=".$system -> model -> db -> secure($values[$error[2]]);
					
					$record_id = $system -> model -> db -> getCell($query);
					
					if($record_id && array_key_exists($record_id, $data) && $data[$record_id][$error[2]] != $values[$error[2]])
					{
						$system -> model -> removeError($key);
						
						if(!count($system -> model -> getErrors()))
							$has_errors = false;
					}
				}
		}
		
		if(!$has_errors) //Extra unique fileds check if we have new same values
			foreach($values as $key => $value)
				if(isset($unique_fields[$key]) && $value != "")
					if(!in_array($value, $unique_fields[$key]))
						$unique_fields[$key][] = $value;
					else
					{
						$system -> model -> addError(array($system -> model -> getCaption($key), "{error-unique-value}", $key));
						$has_errors = true;
					}
		
		if($has_errors)
		{
			$errors = $system -> model -> getErrors();
			
			foreach($errors as $error)
			{
				if(is_array($error) && isset($error[2]))
				{
					$error_text = Model :: processErrorText($error, $system -> model -> getElement($error[2]));
					$result["wrong_fields"][] = "#quick-edit-".$error[2]."-".$id;
				}
				else
					$error_text = $error;
				
				if(strpos($result["general_errors"], $error_text) === false)
					$result["general_errors"] .= "<p>".$error_text."</p>";
			}				
		}
	}

	if(!count($result["wrong_fields"]) && !$result["general_errors"]) //Final data updating if no errors
	{
		$system -> db -> beginTransaction();
		
		foreach($data as $id => $values)
		{
			if(!$system -> model -> checkRecordById($id) || !count($values))
				continue;
				
			$system -> model -> drop() -> read($id) -> getDataFromArray($values) -> update();
			$result['updated'] ++;
		}
		
		$system -> db -> commitTransaction();
		
		if($result['updated'])
			$_SESSION["message"]["done"] = "update";
	}
	
	$result["wrong_fields"] = implode(",", $result["wrong_fields"]);

	header('Content-Type: application/json');
	echo json_encode($result);
}
?>