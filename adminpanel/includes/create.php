<?
if($system -> user -> checkModelRights($system -> model -> getModelClass(), "create"))
	$action = "location.href='".$registry -> getSetting('AdminPanelPath')."model/create.php?".$url_params."'";
else
	$action = "$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});";
?>

<input class="button-create" type="button" onclick="<? echo $action; ?>" value="<? echo I18n :: locale('create'); ?>" />
