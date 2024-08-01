<?
include_once "../../config/autoload.php";

$system = new System();
$system -> detectModel();

$system -> user -> extraCheckModelRights($system -> model -> getModelClass(), "read");

$system -> model -> setId(-1) -> getDataFromDb();
$current_tab = $system -> model -> checkCurrentTab();

$system -> runVersions();
$url_params = $system -> model -> getAllUrlParams(array('model'));

if($current_tab)
	$url_params .= "&current-tab=".$current_tab;
			
$system -> versions -> setUrlParams($url_params);

if(isset($_GET['action']) && $_GET['action'] == 'update' && !empty($_POST))
{
	$url_params = "model/index-simple.php?model=".$system -> model -> getModelClass();
	$form_errors = $system -> model -> getDataFromPost() -> validate();
	
	if(!isset($_POST["admin-panel-csrf-token"]) || $_POST["admin-panel-csrf-token"] != $system -> getToken())
	{
		$system -> model -> addError(I18n :: locale("error-wrong-token"));
		$form_errors = true;
	}
	
	if(!$form_errors)
	{
		$system -> db -> beginTransaction();
		$system -> model -> update("backend");
		$system -> db -> commitTransaction();
				
		$_SESSION["message"]["updated"] = true;
		
		if($current_tab)
			$url_params .= "&current-tab=".$current_tab;
		
		$system -> reload($url_params);
	}
}
else if(isset($_GET['version']) && intval($_GET['version']))
{	
	if($system -> versions -> checkVersion($_GET['version']))
	{
		$system -> versions -> setVersion($_GET['version']);
		$system -> passVersionContent();
	}
	else
		$system -> displayInternalError("error-wrong-record");
}
else
	$system -> model -> getDataFromDb() -> passDataFromDb();
	
include $registry -> getSetting("IncludeAdminPath")."includes/header.php";
?>

<script type="text/javascript" src="<? echo $registry -> getSetting('AdminPanelPath'); ?>interface/js/form.js"></script>  

<div id="columns-wrapper">
   <div id="model-form">
      <div class="column-inner">
         <h3 class="column-header with-navigation">
            <?
            	echo $system -> model -> getName();
            	echo "<span class=\"header-info\">".I18n :: locale("simple-module")."</span>";
            	
              	if($version = $system -> versions -> getVersion())
              		echo "<span class=\"header-info\">".I18n :: locale("version-loaded").$version."</span>\n";
            ?>
			<span id="header-navigation">
				<input class="button-light" type="button" id="top-save-button" value="<? echo I18n :: locale('update'); ?>" />
				<input class="button-dark button-back" type="button" onclick="location.href='<? echo $registry -> getSetting('AdminPanelPath'); ?>'" value="<? echo I18n :: locale('cancel'); ?>" />
			</span>			            
         </h3>       
		 <?      
		      if(isset($form_errors) && $form_errors)
		          echo $system -> model -> displayFormErrors();
		      else if(isset($_SESSION["message"]["updated"]))
		          echo "<div class=\"form-no-errors\"><p>".I18n :: locale('done-update')."</p></div>\n";
		          
		      unset($_SESSION["message"]);
		          
			  if($file_name = $system -> model -> checkIncludeCode("index-top.php"))
			  	  include $file_name;
			  	  
		 	  $form_action = "?model=".$system -> model -> getModelClass()."&action=update";

		 	  if($current_tab)
		 	  	  $form_action .= "&current-tab=".$current_tab;
		 ?>
	     <form class="model-elements-form" method="post" id="<? echo $system -> model -> getModelClass(); ?>" enctype="multipart/form-data" action="<? echo $form_action?>">
         <? 
         	 $form_html = $system -> model -> displayModelFormInAdminPanel($current_tab);
			  	  
             if(is_array($form_html))
               	  echo $form_html[1];
         ?>
	      <table>
	         <?
	              echo is_array($form_html) ? $form_html[0] : $form_html;
		   	  	  
	              if($file_name = $system -> model -> checkIncludeCode("index-form.php"))
			   	       include $file_name;
	         ?>
	         <tr class="model-form-navigation">
	           <td colspan="2" class="bottom-navigation">
                <?
                   	if($system -> user -> checkModelRights($system -> model -> getModelClass(), "update"))
                   		$submit_button = "type=\"button\" id=\"submit-button\"";
                   	else
                   		$submit_button = "type=\"button\" onclick=\"$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});\"";
                ?>                 
	           <input class="button-light" <? echo $submit_button; ?> value="<? echo I18n :: locale('update'); ?>" />
	           <input class="button-dark" onclick="location.href='<? echo $registry -> getSetting('AdminPanelPath'); ?>'" type="button" value="<? echo I18n :: locale('cancel'); ?>" />
               <input type="hidden" name="admin-panel-csrf-token" value="<? echo $system -> getToken(); ?>" />
	         </td>
	      </tr>
	      </table>          
	   </form>
       <? 
		  if($file_name = $system -> model -> checkIncludeCode("index-bottom.php"))
		  	  include $file_name;
       ?>
      </div>
   </div>
   <div id="model-versions">
      <div class="column-inner">
         <h3><? echo I18n :: locale('versions-history'); ?></h3>
	     <? include $registry -> getSetting("IncludeAdminPath")."includes/versions.php"; ?>
      </div>
   </div>
   <div class="clear"></div>
</div>
<?
include $registry -> getSetting("IncludeAdminPath")."includes/footer.php";
?>