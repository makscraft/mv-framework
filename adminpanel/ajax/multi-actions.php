<? 
include "../../config/autoload.php";

$system = new System('ajax');
$system -> ajaxRequestContinueOrExit();

//Process of garbage cleaup
if(isset($_POST['empty-recycle-bin']))
{
	$system -> runModel("garbage");
	
	if($system -> user -> checkModelRights("garbage", "delete"))
		if($_POST['empty-recycle-bin'] == "count") //If just count records to delete from garbage
		{
			if(!$garbage_number = $system -> db -> getCount("garbage"))
				exit();
			
			$arguments = array('number' => $garbage_number, 'records' => '*number');
			echo I18n :: locale('number-records', $arguments);
		}
		else if($_POST['empty-recycle-bin'] == "process" && isset($_POST['iterations-left'])) //Process of final delete of records
		{
			sleep(1);
			set_time_limit(180);			
			
			$system -> model -> emptyGarbage(50);
			
			if(intval($_POST['iterations-left']) == 1)
			{
				Filemanager :: makeModelsFilesCleanUp();
				$_SESSION["message"]["done"] = "delete";
			}
		}
	
	exit();
}

//Response when call bulk operation in main table of model in admin panel
$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
$xml .= "<response>\n";

if(isset($_POST['model']) && ($system -> registry -> checkModel($_POST['model']) || $_POST['model'] == 'garbage'))
{
	$ids = explode(',', $_POST['ids']);
	$simple_types = array("date", "date_time", "int", "float");
	
	if($_POST['action'] != 'delete' && $_POST['action'] != 'restore')
	{
		$system -> runModel(strtolower($_POST['model']));
		$object = $system -> model -> getElement($_POST['action']);
		
		if(!$object)
			$xml .= "<error>1</error>\n";
		else if($object -> getType() == 'bool')
		{
			$key = $_POST['value'] ? 'yes' : 'no';
			$xml .= "<value>".I18n :: locale($key)."</value>\n";
		}
		else if($object -> getType() == 'enum')
		{
			if($object -> getProperty("long_list"))
				$xml .= "<long_list>".intval($object -> getProperty("long_list"))."</long_list>\n";
			else
				$xml .= "<values_list>\n".$object -> getDataForMultiAction()."</values_list>\n";
			
			$empty_value = $object -> getProperty("required") ? false : $object -> getProperty("empty_value");			
			$xml .= "<empty_value>".intval($empty_value)."</empty_value>\n";
		}
		else if($object -> getType() == 'parent')
		{
			if($object -> getProperty("long_list"))
				$xml .= "<long_list>".intval($object -> getProperty("long_list"))."</long_list>\n";
			else
				$xml .= "<values_list>\n".$system -> model -> defineAvailableParents($ids)."</values_list>\n";
		}
		else if(($object -> getType() == 'many_to_many' ||  $object -> getType() == 'group') && 
		        ($_POST['value'] == "add" || $_POST['value'] == "remove"))
		{
			if($object -> getProperty("long_list"))
				$xml .= "<long_list>".intval($object -> getProperty("long_list"))."</long_list>\n";
			else
				$xml .= "<values_list>\n".$object -> getDataForMultiAction()."</values_list>\n";
		}
		else if(!in_array($object -> getType(), $simple_types))
			$xml .= "<error>1</error>\n";
			
		if($object)
		{
			$xml .= "<caption>".$object -> getCaption()."</caption>\n";
			$xml .= "<type>".$object -> getType()."</type>\n";
		}
	}
	
	if($_POST['action'] == 'delete' || $_POST['action'] == 'restore')
	{
		$arguments = array('number' => count($ids), 'records' => '*number');
		$xml .= "<number_records>".I18n :: locale('number-records', $arguments)."</number_records>\n";
	}
	else
	{
		$arguments = array('number' => count($ids), 'for-record' => '*number');
		$xml .= "<number_records>".I18n :: locale('number-for-records', $arguments)."</number_records>\n";
	}
}
else
	$xml .= "<error>1</error>\n";

$xml .= "</response>\n";

Http :: responseXml($xml);
?>