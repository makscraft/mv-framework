<?
global $system;
$db = DataBase :: instance();
$registry = Registry :: instance();
$i18n = I18n :: instance();

$path = $registry -> getSetting("AdminPanelPath");

if($system -> getError() == I18n :: locale("error-wrong-record") && isset($_GET["model"]))
	$path .= "model/?model=".$_GET["model"];

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<div id="columns-wrapper">
   <div id="model-table">
      <div class="column-inner">
         <h3 class="column-header"><? echo I18n :: locale("caution"); ?></h3>
            <div class="form-errors">
               <p>
	         	<? 
	         		if($system -> user -> getError())
	         			echo $system -> user -> getError();
	         		else if($system -> getError())
	         			echo $system -> getError();
	         	?>
               </p> 
            </div>
            <input class="button-light" onclick="location.href='<? echo $path; ?>'" type="button" value="<? echo I18n :: locale('back'); ?>" />
       </div>
   </div>
</div>
<?
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
exit();
?>