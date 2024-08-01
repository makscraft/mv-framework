<?
include "../../config/autoload.php";

$system = new System('ajax');
$system -> ajaxRequestContinueOrExit();

$time_limit = $system -> registry -> getSetting("CsvUploadTimeLimit");
$time_limit = $time_limit ? $time_limit : 300;
set_time_limit($time_limit);
	
$csv = new Csv();
$result = array("error" => "error-not-all-params", "message" => "");
$update_orders = array("update-and-create", "create-only", "update-only");

if(isset($_FILES["csv_file"], $_POST["model"], $_POST["csv_fields"], $_POST["csv_separator"], 
		 $_POST["csv_encoding"], $_POST["csv_update_order"]))
	if($system -> registry -> checkModel($_POST["model"]) && $_POST["csv_fields"])
		if($system -> user -> checkModelRights($_POST["model"], "update") && in_array($_POST["csv_update_order"], $update_orders))
			if(array_key_exists($_POST["csv_separator"], $csv -> getSeparators()) && 
			   in_array($_POST["csv_encoding"], $csv -> getEncodings())) //Checks incoming params
			{
				$result["error"] = "";
				$model = new $_POST["model"]();
				$headers = false;
				
				//Checks if all passed fields are correct and allowed
				foreach(explode(",", $_POST["csv_fields"]) as $field)
					if($field == "id")
						$fields[] = "id";
					else if($field && $model -> getElement($field) && 
					   		in_array($model -> getElement($field) -> getType(), $csv -> getExportTypes()))
								$fields[] = $field;
						
				if(count($fields) != count(explode(",", $_POST["csv_fields"])))
					$result["error"] = "error-not-all-params";
				
				if(!$result["error"])
					if(Service :: getExtension($_FILES['csv_file']['name']) != "csv")
						$result["error"] = "error-wrong-csv-file";
					else
					{
						//Initial data processing
						$data = trim(file_get_contents($_FILES['csv_file']['tmp_name']));					
						$data = ($_POST["csv_encoding"] != "utf-8") ? iconv($_POST["csv_encoding"], "utf-8", $data) : $data;
						
						$data = preg_replace("/(\n)+/", "", $data);
						$data = preg_replace("/\"+/", "\"", $data);
						$data = preg_replace("/'+/", "'", $data);
						$data = preg_split("/(\r)+/", $data);
						$separators = $csv -> getSeparators();
						
						foreach($data as $key => $string)
						{
							if(!$key && isset($_POST["csv_headers"])) //If first line is headers
							{
								unset($data[$key]);
								$headers = true;
								continue;
							}
							
							//Converts string into array of fields
							$data[$key] = explode($separators[$_POST["csv_separator"]], $string);
						}
						
						$upload_result = $csv -> updateFromCSVFile($data, $model, $fields, $_POST["csv_update_order"], $headers);						
					}									
			}
			
if($result["error"])
	$result["message"] = I18n :: locale($result["error"]);
else if(isset($upload_result))
{
	if(count($upload_result["created_ids"]) || count($upload_result["updated_ids"])) //Update was done
	{
		$result["message"] = I18n :: locale('update-was-sucessfull');
		
		if(count($upload_result["created_ids"]))
			$result["message"] .= "<br />".I18n :: locale('created-records').": ".count($upload_result["created_ids"]);
		
		if(count($upload_result["updated_ids"]))
			$result["message"] .= "<br />".I18n :: locale('updated-records').": ".count($upload_result["updated_ids"]);
	}
	else //If nothing was created or updated
	{
		$result["error"] = 1;
		$result["message"] = I18n :: locale('update-was-failed');
	}
	
	$many_declined_strings = false;
	
	if(count($upload_result["declined_strings"]) > 1000) //Cut off tail of long list of strings numbers
	{
		$count = 0;
		
		foreach($upload_result["declined_strings"] as $key => $value)
			if(++ $count > 1000)
				unset($upload_result["declined_strings"][$key]);
		
		$many_declined_strings = true;
	}

	if(count($upload_result["declined_strings"]))
	{
		$result["message"] .= "<br />".I18n :: locale('declined-strings').": ";
		$result["message"] .= implode(", ", array_keys($upload_result["declined_strings"]));
		
		if($many_declined_strings)
			$result["message"] .= ", ...";
	}
}

header('Content-Type: application/json');
echo json_encode($result);
?>