<?
include "../../config/autoload.php";

$system = new System('ajax');
$system -> ajaxRequestContinueOrExit();

if(isset($_POST['model'], $_POST['id']) && $system -> registry -> checkModel($_POST['model']))
{			
	$system -> runModel($_POST['model']);
	$system -> model -> setId($_POST['id']);
	
	$url_params = $system -> model -> getAllUrlParams(array('parent','model','filter','pager','id'));
	$current_tab = $system -> model -> checkCurrentTab();

	if($current_tab)
		$url_params .= "&current-tab=".$current_tab;
		
	$system -> runVersions();
	$system -> versions -> setUrlParams($url_params);
	
	$id_check = ($_POST['id'] == -1) ? true : $system -> model -> checkRecordById($system -> model -> getId());
	
	if(isset($_POST['version']) && intval($_POST['version']) && $id_check)
		if($system -> versions -> checkVersion($_POST['version']))
			$system -> versions -> setVersion($_POST['version']);

	if(isset($_POST['versions-pager-limit']) && intval($_POST['versions-pager-limit']))
	{
		$_SESSION['mv']['settings']['versions-pager-limit'] = intval($_POST['versions-pager-limit']);
		$system -> user -> saveSettings($_SESSION['mv']['settings']);
	}
	
	header('Content-Type: text/html');
	include $system -> registry -> getSetting('IncludeAdminPath')."includes/versions.php";
}
?>