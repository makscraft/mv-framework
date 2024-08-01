<?
include "../../config/autoload.php";

$system = new System('ajax');

if(isset($_GET["locale"]) && I18n :: getRegion() == $_GET["locale"])
{
	$keys = array("delete-one", "delete-many", "delete-one-finally", "delete-many-finally", "restore-one", 
				  "restore-many", "update-many-bool", "update-many-enum", "update-many-m2m-add", "update-many-m2m-remove", 
				  "sort-by-column", "all-parents-filter", "parent-filter-needed", "error-data-transfer", "no-rights", 
				  "delete-files", "delete-file", "delete-folder", "rename-file", "rename-folder", "add-image-comment", 
				  "not-uploaded-files", "select-fields", "select-csv-file", "quick-edit-limit", "choose-skin",
				  "search-by-name", "add-edit-comment", "move-left", "move-right", "move-first", "move-last", "delete",
				  "not-defined", "no-images", "cancel");
	
	$data = array();
	
	header("Content-Type: application/javascript; charset=utf-8");

	echo "mVobject.region = \"".I18n :: getRegion()."\";\n\n";
	echo "mVobject.localePackage = {\n";
	
	foreach($keys as $key)
		$data[] = str_replace("-", "_", $key).': '.'"'.I18n :: locale($key).'"';
	
	echo implode(",\n", $data);
	
	echo "\n};";
	exit();
}

$system -> ajaxRequestContinueOrExit();

if(isset($_POST['action'], $_POST['string']) && $_POST['action'] == "translit")
{
	echo I18n :: translitUrl(trim($_POST['string']));
	exit();
}

if(isset($_GET['model'], $_GET['field'], $_GET['query']) && $system -> registry -> checkModel($_GET['model']))
{
	$system -> runModel($_GET['model']);
	$request = htmlspecialchars(trim($_GET['query']), ENT_QUOTES);
	$object = $system -> model -> getElement($_GET['field']);
	
	if($object)
	{
		if($object -> getType() == "parent")
		{			
			$object -> setSelfModel(get_class($system -> model));
			
			if(isset($_GET['id']))
			{				
				$object -> setSelfId(intval($_GET['id'])) -> getAvailbleParents($system -> model -> getTable());				
				$result = $object -> getDataForAutocomplete($request, $system -> db);
			}
			else if(isset($_GET['ids']) && $_GET['ids'])
			{
				$ids = explode(",", $_GET['ids']);
				$object -> getAvailbleParents($system -> model -> getTable());
				$result = $system -> model -> getParentsForMultiAutocomplete($request, $ids);
			}
			else
			{
				$object -> getAvailbleParents($system -> model -> getTable());
				$result = $object -> getDataForAutocomplete($request, $system -> db);
			}
		}
		else
			$result = $object -> getDataForAutocomplete($request, $system -> db);
			
		if(isset($result["query"]))
			$result["query"] = htmlspecialchars_decode($result["query"], ENT_QUOTES);
			
		header('Content-Type: application/json');
		echo json_encode($result);
	}
}
?>