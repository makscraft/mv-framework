<?
include_once "../../config/autoload.php";
$system = new System();

$system -> detectModel();
$system -> user -> extraCheckModelRights($system -> model -> getModelClass(), "read");
$system -> registry -> setSetting("AdminPanelCSRFToken", $system -> getToken());

$url_params = $system -> model -> getAllUrlParams(array('model','parent','filter','pager'));

if(isset($_SESSION['mv']['settings'][$system -> model -> getModelClass()]['show-filters']))
	$show_filters_column = (bool) $_SESSION['mv']['settings'][$system -> model -> getModelClass()]['show-filters'];
else
	$show_filters_column = true;
	
$allowed_limits = array(5, 10, 15, 20, 30, 50, 100, 200, 300, 500);

if(isset($_GET['pager-limit']) && in_array(intval($_GET['pager-limit']), $allowed_limits))
{
	$system -> model -> pager -> setLimit(intval($_GET['pager-limit']));
	$_SESSION['mv']['settings']['pager-limit'] = $system -> model -> pager -> getLimit();
	$system -> user -> saveSettings($_SESSION['mv']['settings']);
	
	$system -> reload("model/?".$url_params);	
}

if(isset($_SESSION['mv']['settings']['pager-limit']))
{
	$system -> model -> pager -> setLimit($_SESSION['mv']['settings']['pager-limit']);
	$system -> model -> processUrlRarams();
	$url_params = $system -> model -> getAllUrlParams(array('model','parent','filter','pager'));
}

if(isset($_GET['sort-field'], $_GET['sort-order']))
{
	if($system -> model -> sorter -> setParams($_GET['sort-field'], $_GET['sort-order']))
	{
		$_SESSION['mv']['settings'][$system -> model -> getModelClass()]['sort']['field'] = $_GET['sort-field'];
		$_SESSION['mv']['settings'][$system -> model -> getModelClass()]['sort']['order'] = $_GET['sort-order'];
		
		$system -> user -> saveSettings($_SESSION['mv']['settings']);
		$url_params = $system -> model -> getAllUrlParams(array('model','parent','filter','pager'));
	}
	
	$system -> reload("model/?".$url_params);
}
else if(isset($_SESSION['mv']['settings'][$system -> model -> getModelClass()]['sort']))
{
	$field = $_SESSION['mv']['settings'][$system -> model -> getModelClass()]['sort']['field'];
	$order = $_SESSION['mv']['settings'][$system -> model -> getModelClass()]['sort']['order'];
	
	if($system -> model -> checkIfAdminSortingFieldAllowed($field))
		$system -> model -> sorter -> setParams($field, $order);
}

if(isset($_GET['id'], $_GET['action']) && intval($_GET['id']) && $system -> model -> getModelClass() != 'log')
{
    $check_token = $system -> model -> generateSingleActionToken($_GET['id']);
    
    if(!isset($_GET["admin-model-action-token"]) || $_GET["admin-model-action-token"] != $check_token)
	{
		$_SESSION["message"]["token-error"] = true;
		$system -> reload("model/?".$url_params);
	}
		
	if($_GET['action'] == 'delete' && $system -> model -> checkDisplayParam('delete_actions'))
	{
		$system -> user -> extraCheckModelRights($system -> model -> getModelClass(), "delete");
		
		if(!$system -> model -> checkRecordById($_GET['id']))
			$system -> displayInternalError("error-wrong-record");						
			
		if($system -> model -> getModelClass() == 'users' && intval($_GET['id']) == 1)
		{
			$_SESSION["message"]["not-deleted"] = "root";
			$system -> reload("model/?".$url_params);
		}
		
		if($result = $system -> model -> setId($_GET['id']) -> checkForChildren())
		{
			$_SESSION["message"]["not-deleted"] = $registry -> checkModel($result) ? $result : false;			
			$system -> reload("model/?".$url_params);
		}
		else
		{
			$system -> db -> beginTransaction();
			$system -> model -> setId($_GET['id']) -> delete();
			$system -> db -> commitTransaction();
			
			if(!count($system -> model -> getErrors()))
				$_SESSION["message"]["done"] = "delete";
			else
				$_SESSION["message"]["custom-errors"] = $system -> model -> displayFormErrors();
			
			$system -> reload("model/?".$url_params);
		}
	}
	else if($_GET['action'] == 'restore' && $system -> model -> getModelClass() == 'garbage')
	{
		$system -> user -> extraCheckModelRights($system -> model -> getModelClass(), "update");
		
		$system -> db -> beginTransaction();
		$completed = $system -> model -> setId($_GET['id']) -> restore();
		$system -> db -> commitTransaction();
		
		if($completed !== false)
			$_SESSION["message"]["done"] = "restore";
		else
			$_SESSION["message"]["custom-errors"] = $system -> model -> displayFormErrors();
			
		$system -> reload("model/?".$url_params);
	}
}
else if(isset($_GET['multi_action'], $_GET['multi_value']) && !empty($_POST))
	if($system -> model -> checkDisplayParam('update_actions') && $system -> model -> checkDisplayParam('mass_actions'))
	{
		set_time_limit(300);
		
		if(!isset($_POST["admin-panel-csrf-token"]) || $_POST["admin-panel-csrf-token"] != $system -> getToken())
		{
			$_SESSION["message"]["token-error"] = true;
			$system -> reload("model/?".$url_params);
		}

		$multi_action = ($_GET['multi_action'] == 'delete') ? 'delete' : 'update';
		$system -> user -> extraCheckModelRights($system -> model -> getModelClass(), $multi_action);
		
		$system -> db -> beginTransaction();
		$error = $system -> model -> applyMultiAction($_GET['multi_action'], urldecode($_GET['multi_value']));
		$system -> db -> commitTransaction();
		
		$done = ($_GET['multi_action'] == 'delete' || $_GET['multi_action'] == 'restore') ? $_GET['multi_action'] : 'update';
		
		if(!$error)
			$_SESSION["message"]["done"] = $done;
		else if($_GET['multi_action'] == 'restore' || ($_GET['multi_action'] == 'delete' && !preg_match("/^not-deleted/", $error)))
			$_SESSION["message"]["custom-errors"] = $error;
		else if(strpos($error, "datatype-error form-errors"))
			$_SESSION["message"]["custom-errors"] = $error;			
		else
		{
			$error = explode("=", $error);
			$_SESSION["message"][$error[0]] = isset($error[1]) ? $error[1] : false;
		}

		$system -> reload("model/?".$url_params);
	}

if(isset($_SESSION['mv']['settings'][$system -> model -> getModelClass()]['display-fields']))
	$system -> model -> defineTableFields($_SESSION['mv']['settings'][$system -> model -> getModelClass()]['display-fields']);
else
	$system -> model -> defineTableFields();

$system -> model -> createSqlForTable();

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<div id="columns-wrapper">
  <div id="model-table-wrapper"<? if(!$show_filters_column) echo ' class="hidden-filters"'; ?>>
   <div id="model-table">
         <h3 class="column-header">
             <? 
               	 echo $system -> model -> getName();
                         	 	
            	 if($system -> model -> filter -> ifAnyFilterApplied())
            	 {
            	 	echo "<span class=\"header-info\">".I18n :: locale("filtration-applied")."</span>";
            	 	$number_of_records = $system -> model -> pager -> getTotal();
            	 	echo "<span class=\"header-info\">";
            	 	
            	 	if($number_of_records)
            	 	{
            	 		$number_of_records = I18n :: formatIntNumber($number_of_records);
            	 		
            	 		$i18n_arguments = array('number' => $number_of_records, 'records' => '*number',
            	 								'number_found' => $number_of_records, 'found' => '*number_found');
            	 		
						echo I18n :: locale('found-records', $i18n_arguments);
            	 	}
            	 	else
            	 		echo I18n :: locale('no-records-found');
            	 	
            	 	echo "</span>\n";
            	 }
            	 else
            	 {
            	 	$total_records = $system -> model -> db -> getCount($system -> model -> getTable());
            	 	$i18n_arguments = array('number' => $total_records, 'records' => '*number');
            	 	
            	 	if($total_records)
            	 	{
            	 		$string = "<span class=\"header-info\">".I18n :: locale('number-records', $i18n_arguments)."</span>";
            	 		echo str_replace($total_records, I18n :: formatIntNumber($total_records), $string);
            	 	}
            	 }

				if($system -> model -> checkDisplayParam('create_actions'))
         			include $registry -> getSetting('IncludeAdminPath')."includes/create.php";
             ?>
         </h3>
         <?
         	if(isset($_SESSION["message"]["done"]) && in_array($_SESSION["message"]["done"], array('create','update','delete','restore')))
          		echo "<div class=\"form-no-errors\"><p>".I18n :: locale('done-'.$_SESSION["message"]["done"])."</p></div>\n";
			else if(isset($_SESSION["message"]['not-deleted']))
         	{
         		if($_SESSION["message"]['not-deleted'] == 'root')
         			$message = 'no-delete-root';
         		else if($_SESSION["message"]['not-deleted'])
         			$message = 'no-delete-model';
         		else
         			$message = 'no-delete-parent';
         		
         		$arguments = [];
         		
         		if($_SESSION["message"]['not-deleted'] && $_SESSION["message"]['not-deleted'] != 'root' && 
         		   $registry -> checkModel($_SESSION["message"]['not-deleted']))
         		{
         			$model_class = trim($_SESSION["message"]['not-deleted']);
         			$object = new $model_class();
         			$arguments['module'] = $object -> getName();
         		}
         		
         		echo "<div class=\"form-errors\"><p>".I18n :: locale($message, $arguments)."</p></div>\n";         		
         	}
         	else if(isset($_SESSION["message"]["custom-errors"]) && $_SESSION["message"]["custom-errors"])
			      echo $_SESSION["message"]["custom-errors"];
         	else if(isset($_SESSION["message"]["token-error"]))
         	{
			      echo "<div class=\"form-errors\"><p>".I18n :: locale("error-failed")." ";
			      echo I18n :: locale("error-wrong-token")."</p></div>\n";
         	}
			      
			unset($_SESSION["message"]);

	        if($system -> model -> getParentField() && $system -> model -> getParentId())
	        {
	            echo "<div class=\"parents-path\">\n";
	            echo $system -> model -> displayParentsPath($system -> model -> getParentId())."</div>\n";
	        }
	         
		   	if($file_name = $system -> model -> checkIncludeCode("index-top.php"))
				include $file_name;
         ?>
         <div id="top-navigation">
	         <?
	         	$multi_actions_menu = $system -> menu -> displayMultiActionMenu($system -> model, $system -> user);
	         	$model_class = $system -> model -> getModelClass();
	         	
	         	if($system -> model -> checkDisplayParam('mass_actions') && $system -> model -> checkDisplayParam('update_actions') && 
	         	   $model_class != "users" && $model_class != "garbage")
	         	{
	         		$quick_limit = "quick-limit-".$system -> model -> getPagerLimitForQuickEdit();
	         		$quick_edit_buttons = "<input id=\"".$quick_limit."\" class=\"button-light mass-quick-edit\" type=\"button\" ";
	         		$quick_edit_buttons .= "value=\"".I18n :: locale('quick-edit')."\" />\n";
	         		$quick_edit_buttons .= "<input class=\"button-light save-quick-edit\" type=\"button\" ";
	         		$quick_edit_buttons .= "value=\"".I18n :: locale('save')."\" />\n";
	         		$quick_edit_buttons .= "<input class=\"button-dark cancel-quick-edit\" type=\"button\" ";
	         		$quick_edit_buttons .= "value=\"".I18n :: locale('cancel')."\" />\n";	         		
	         		
	         		echo str_replace("</div>", $quick_edit_buttons."</div>", $multi_actions_menu);
	         	}
	         	else if($model_class == "garbage")
	         	{
	         		$rights_css = $system -> user -> checkModelRights("garbage", "delete") ? "" : " has-no-rights";
	         		$button_empty = "<input class=\"button-light".$rights_css."\" id=\"empty-recycle-bin\" type=\"button\" ";
	         		$button_empty .= "value=\"".I18n :: locale('empty-recylce-bin')."\" />\n";
	         		
	         		echo str_replace("</div>", $button_empty."</div>", $multi_actions_menu);
	         	}
	         	else
	         		echo $multi_actions_menu;
	         ?>
            <div id="fields-list">
               <input class="button-light<? if($show_filters_column) echo " no-display"; ?>" type="button" id="show-filters" value="<? echo I18n :: locale('filters'); ?>" />
               <input class="button-list" type="button" id="fields-list-button" value="<? echo I18n :: locale('display-fields'); ?>" />
               <div class="list">
                     <div class="m2m-wrapper">
                        <div class="column">
					       <div class="header"><? echo I18n :: locale("not-selected"); ?></div>
		                   <select class="m2m-not-selected" multiple="multiple">
		                           <? 
		                              $selects_html = $system -> menu -> displayTableFields($system -> model);
		                              echo $selects_html['not-selected'];
		                           ?>
		                   </select>                      
                        </div>					    
					    <div class="m2m-buttons">
						    <span class="m2m-right" title="<? echo I18n :: locale('move-selected'); ?>"></span>
						    <span class="m2m-left" title="<? echo I18n :: locale('move-not-selected'); ?>"></span>						
                        </div>
                        <div class="column">
                           <div class="header"><? echo I18n :: locale("selected"); ?></div>
					       <select class="m2m-selected" multiple="multiple">
                              <? echo $selects_html['selected']; ?>
					       </select>
                        </div>
                        <div class="m2m-buttons">
                           <span class="m2m-up" title="<? echo I18n :: locale('move-up'); ?>"></span>
                           <span class="m2m-down" title="<? echo I18n :: locale('move-down'); ?>"></span>						
                        </div>
					    <input type="hidden" value="" name="display-table-fields" />
					 </div>
                     <div class="controls">
                        <input class="apply button-light" type="button" value="<? echo I18n :: locale('apply') ?>" />
                        <input class="cancel button-dark" value="<? echo I18n :: locale('cancel') ?>" type="button" />
                     </div>
               </div>
               <?
               		if($system -> model -> getModelClass() != "log" && $system -> model -> getModelClass() != "garbage")
               			include $registry -> getSetting('IncludeAdminPath')."includes/operations.php";
               ?>
            </div>
         </div>         
         <form id="model-table-form" method="post" action="?<? echo $system -> model -> getAllUrlParams(array('model','parent','filter','pager')); ?>">
            <? echo $system -> model -> displaySortableTable(); ?>
            <input type="hidden" name="admin-panel-csrf-token" value="<? echo $system -> getToken(); ?>" />
         </form>
         <? echo str_replace('class="multi-actions-menu"', 'class="multi-actions-menu" id="bottom-actions-menu"', $multi_actions_menu); ?>
       <div class="pager-limit">
         <span><? echo I18n :: locale('pager-limit'); ?></span>
         <select>
            <? echo $system -> model -> pager -> displayPagerLimits($allowed_limits); ?>
         </select>
         <input type="hidden" value="<? echo $system -> model -> getAllUrlParams(array('model','parent','filter')); ?>" />
       </div>
       <div class="clear"></div>
         <?
			echo $system -> model -> pager -> displayPagesAdmin();
			
		   	if($file_name = $system -> model -> checkIncludeCode("index-bottom.php"))
			    include $file_name;
         ?>
       </div>
    </div>	
	<div id="model-filters"<? if(!$show_filters_column) echo ' class="no-display"'; ?>>
	       <h3><? echo I18n :: locale('filters'); ?>
               <span><input id="hide-filters" type="button" value="<? echo I18n :: locale('hide') ?>" /></span>
           </h3>
		   <div id="admin-filters">
		      <? 
		            $system -> model -> filter -> setAllowedCountFilter($system -> model -> sorter -> getField());
		            $default_filters = $system -> model -> getDisplayParam("default_filters");
		            $show_empty_default_filters = $system -> model -> getDisplayParam("show_empty_default_filters");
		            echo $system -> model -> filter -> displayAdminFilters($default_filters, $show_empty_default_filters);
		            
		            include $registry -> getSetting('IncludeAdminPath')."includes/filters-manager.php";
		      ?>
		      <div class="controls">
		         <input type="hidden" name="initial-form-params" value="<? echo $system -> model -> getAllUrlParams(array('sorter','model','parent')); ?>" />
                 <input class="button-light" type="button" id="filters-submit" value="<? echo I18n :: locale('apply-filters'); ?>" />
		         <input class="button-dark" type="button" id="filters-reset" value="<? echo I18n :: locale('reset'); ?>" />
		      </div>
		   </div>         
	</div>
</div>
<?
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>