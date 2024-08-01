<?
include_once "../../config/autoload.php";

$system = new System();
$system -> detectModel();

if(!$system -> model -> checkDisplayParam("update_actions") || 
   !$system -> user -> checkModelRights($system -> model -> getModelClass(), "read"))
{
	$system -> displayInternalError("error-no-rights");
}

if(isset($_SESSION['mv']['pager-limit']))
{
	$system -> model -> pager -> setLimit($_SESSION['mv']['settings']['pager-limit']);
	$system -> model -> processUrlRarams();
}

if(isset($_GET['id']) && $_GET['id'])
{
	if($system -> model -> checkRecordById($_GET['id']))
		$system -> model -> setId($_GET['id']);
	else
		$system -> displayInternalError("error-wrong-record");
	
	$url_params = $system -> model -> getAllUrlParams(array('parent','model','filter','pager','id'));
	$back_url_params = $system -> model -> getAllUrlParams(array('parent','model','filter','pager'));
	
	$current_tab = $system -> model -> checkCurrentTab();

	if($current_tab)
		$url_params .= "&current-tab=".$current_tab;	
	
	$system -> runVersions();
	$system -> versions -> setUrlParams($url_params);

	if(!isset($_GET['action']))
	{
		if(isset($_GET['version']) && intval($_GET['version']))
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
			$system -> model -> read();
	}	
	else if($_GET['action'] == 'update' && !empty($_POST))
	{
		$system -> user -> extraCheckModelRights($system -> model -> getModelClass(), "update");
		$editable_fields = $system -> model -> getEditableFields(); 
		$form_errors = $system -> model -> getDataFromPost() -> validate($editable_fields);
		
		if(!isset($_POST["admin-panel-csrf-token"]) || $_POST["admin-panel-csrf-token"] != $system -> getToken())
		{
			$system -> model -> addError(I18n :: locale("error-wrong-token"));
			$form_errors = true;
		}
		
		if(!$form_errors)
		{
			$system -> db -> beginTransaction();
			$system -> model -> update();
			$system -> db -> commitTransaction();
			
			$path = "model/";
			
			if(isset($_GET['continue']))
				$path .= "update.php?".$url_params;
			else
				$path .= "?". str_replace("&current-tab=".$current_tab, "", $url_params);
				
			$_SESSION["message"]["updated"] = true;
			$_SESSION["message"]["done"] = "update";

			$system -> reload($path);
		}
	}
}
else
	$system -> displayInternalError("error-params-needed");

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>

<div id="columns-wrapper">
	<div id="model-form">
	      <div class="column-inner">
	         <h3 class="column-header with-navigation">
                <?
                	echo $system -> model -> getName();
                	echo "<span class=\"header-info\">".I18n :: locale("update-record")."</span>";
                	
                	if($version = $system -> versions -> getVersion())
                		echo "<span class=\"header-info\">".I18n :: locale("version-loaded").$version."</span>\n";
                ?>
				<span id="header-navigation">
					<? if($system -> model -> getEditableFields() !== false): ?>
						<input class="button-light" type="button" id="top-save-button" value="<? echo I18n :: locale('save'); ?>" />
					<? endif; ?>
					<input class="button-dark button-back" type="button" onclick="location.href='<? echo $registry -> getSetting('AdminPanelPath').'model/?'.$back_url_params; ?>'" value="<? echo I18n :: locale('cancel'); ?>" />             
				</span>				
             </h3>
	   		 <?      
			      if(isset($form_errors) && $form_errors)
			          echo $system -> model -> displayFormErrors();
			      else if(isset($_SESSION["message"]['updated']) || isset($_SESSION["message"]['created']))
			      {
			      	  $key = isset($_SESSION["message"]['updated']) ? 'done-update' : 'created-now-edit';
			          echo "<div class=\"form-no-errors\"><p>".I18n :: locale($key)."</p></div>\n";
			      }
			      
			      unset($_SESSION["message"]);
			      
		      	  if($file_name = $system -> model -> checkIncludeCode("update-top.php"))
			     	  include $file_name;
			      
		      	  if($file_name = $system -> model -> checkIncludeCode("action-top.php"))
			     	  include $file_name;
			 ?>
		    <form method="post" id="<? echo $system -> model -> getModelClass(); ?>" enctype="multipart/form-data" action="?<? echo $url_params; ?>&action=update" class="model-elements-form">
              <?
              	  $form_html = $system -> model -> displayModelFormInAdminPanel($current_tab);
	              
	              if(is_array($form_html))
	                 echo $form_html[1];
              ?>          
		      <table>
		         <?
		         	echo is_array($form_html) ? $form_html[0] : $form_html;
		      	    
		            if($file_name = $system -> model -> checkIncludeCode("update-form.php"))
			     	    include $file_name;
			     	
		            if($file_name = $system -> model -> checkIncludeCode("action-form.php"))
			     	    include $file_name;
			     ?>
		         <tr class="model-form-navigation">
			         <td colspan="2" class="bottom-navigation">
                        <? 
                        	if($system -> user -> checkModelRights($system -> model -> getModelClass(), "update"))
                        	{
                        		$submit_button = "type=\"button\" id=\"submit-button\"";
                        		$continue_button = "id=\"continue-button\"";
                        	}
                        	else
                        	{
                        		$submit_button = "type=\"button\" onclick=\"$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});\"";
                        		$continue_button = "onclick=\"$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});\"";
                        	}
                        ?>
                        <? if($system -> model -> getEditableFields() !== false): ?>
			            <input class="button-light" <? echo $submit_button; ?> value="<? echo I18n :: locale('save'); ?>" />
	                    <input class="button-light" type="button" <? echo $continue_button; ?> value="<? echo I18n :: locale('update-and-continue'); ?>" />                        
                        <input class="button-dark" id="model-cancel" type="button" rel="<? echo $registry -> getSetting('AdminPanelPath')."model/?".$back_url_params; ?>" value="<? echo I18n :: locale('cancel'); ?>" />
                        <? endif; ?>
                        <input type="hidden" name="admin-panel-csrf-token" value="<? echo $system -> getToken(); ?>" />
			         </td>
		         </tr>
		      </table>
		   </form>
           <? 
		   	  if($file_name = $system -> model -> checkIncludeCode("update-bottom.php"))
			   	  include $file_name;
			  
		   	  if($file_name = $system -> model -> checkIncludeCode("action-bottom.php"))
			   	  include $file_name;
		   ?>
	   </div>
	</div>
	<div id="model-versions">
	   <div class="column-inner">
	      <h3><? echo I18n :: locale('versions-history'); ?></h3>
          <? include $registry -> getSetting('IncludeAdminPath')."includes/versions.php"; ?>
	   </div>
	</div>
   <div class="clear"></div>
</div>
<?
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>