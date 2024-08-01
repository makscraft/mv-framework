<?
include_once "../../config/autoload.php";
$system = new System();

$system -> runModel("users");
$system -> model -> setId($system -> user -> getId()) -> read();
$system -> model -> setDisplayParam('hidden_fields', array('active'));
$system -> model -> setDisplayParam('not_editable_fields', array('date_registered', 'date_last_visit'));

$regions_values = I18n :: getRegionsOptions();
$system -> model -> addElement(array("{language}", "enum", "region", array("values_list" => $regions_values)));
$system -> model -> setValue("region", $_SESSION['mv']['settings']['region']);

$migrations_path = $registry -> getSetting('AdminPanelPath')."controls/migrate.php";

if(isset($_GET['action']) && $_GET['action'] == 'update' && !empty($_POST))
{
	$form_errors = $system -> model -> getDataFromPost() -> validate();
	
	if(!isset($_POST["admin-panel-csrf-token"]) || $_POST["admin-panel-csrf-token"] != $system -> getToken())
	{
		$system -> model -> addError(I18n :: locale("error-wrong-token"));
		$form_errors = true;
	}
		
	if(!$form_errors)
	{
		if($system -> model -> getValue("region"))
		{
			$system -> user -> updateSetting("region", $system -> model -> getValue("region"));
			$_SESSION['mv']['settings']['region'] = $system -> model -> getValue("region");
			
			I18n :: saveRegion($system -> model -> getValue("region"));
		}
			
		$system -> model -> removeElement("region");
		
		$system -> db -> beginTransaction();
		$system -> model -> update("self-update");
		$system -> db -> commitTransaction();
		
		$_SESSION["message"]["done"] = "update";
		
		if(isset($_POST["admin-panel-skin"]) && $_POST["admin-panel-skin"])
			$system -> user -> setUserSkin($_POST["admin-panel-skin"]);
		
		$system -> reload("controls/user-settings.php");
	}
}

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<script type="text/javascript">
$(document).ready(function()
{
	$("tr:has(select[name='admin-panel-skin'])").insertAfter("tr:has(input[name='password_repeat'])");
	$("tr:has(select[name='region'])").insertAfter("tr:has(input[name='password_repeat'])");
	$("select[name='region'] option[value='']").remove();
});   
</script>

<div id="columns-wrapper">
    <div id="model-form" class="one-column">
         <h3 class="column-header with-navigation"><? echo I18n :: locale('my-settings'); ?></h3>
         <?
          	  if(isset($form_errors) && $form_errors)
		          echo $system -> model -> displayFormErrors();
		      else if(isset($_SESSION["message"]["done"]))
		          echo "<div class=\"form-no-errors\"><p>".I18n :: locale('done-update')."</p></div>\n";
			          
		      unset($_SESSION["message"]);
         ?>
	     <form method="post" id="<? echo $system -> model -> getModelClass(); ?>" enctype="multipart/form-data" action="?action=update" class="model-elements-form">
	          <table>
		          <? echo $system -> model -> displayModelFormInAdminPanel(); ?>
                  <tr>
                     <td class="field-name"><? echo I18n :: locale("admin-panel-skin"); ?></td>
                     <td class="field-content">                        
                        <? echo $system -> user -> displayUserSkinSelect(); ?>
                     </td>
                  </tr>
                  <tr>
	                  <td colspan="2" class="bottom-navigation">
				         <? if($system -> user -> getId() == 1): ?>
				            <input class="button-light" type="button" onclick="location.href='<? echo $migrations_path; ?>'" value="Migrations" />
				         <? endif; ?>
                         <input class="button-light" type="button" id="submit-button" value="<? echo I18n :: locale('save'); ?>" />
                         <input class="button-dark" onclick="location.href='<? echo $registry -> getSetting('AdminPanelPath'); ?>'" type="button" value="<? echo I18n :: locale('cancel'); ?>" />
                         <input type="hidden" name="admin-panel-csrf-token" value="<? echo $system -> getToken(); ?>" />
	                  </td>
                  </tr>                  
	         </table>
         </form>
    </div>         
 </div>
<?
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>