<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
<title><? echo I18n :: locale('mv'); ?></title>
<? 
$admin_panel_path = $registry -> getSetting('AdminPanelPath');
$admin_media_path = Registry :: get('AdminFolder').'/interface/';
$cache_drop = CacheMedia :: instance() :: getDropMark();

CacheMedia :: addCssFile([
	$admin_media_path.'css/style.css',
	$admin_media_path.'css/ui.css'
]);

if(Router :: isLocalHost())
	echo CacheMedia :: getInitialFiles('css');
else
	echo CacheMedia :: getCssCache();
?>
<script type="text/javascript" src="<? echo $admin_panel_path; ?>interface/js/mv.js<? echo $cache_drop; ?>"></script>
<script type="text/javascript">
mVobject.mainPath = "<? echo $registry -> getSetting('MainPath'); ?>";
mVobject.adminPanelPath = "<? echo $admin_panel_path; ?>"; 
mVobject.urlParams = "<? if(isset($system -> model)) echo $system -> model -> getAllUrlParams(array('pager','filter','model','parent','id')); ?>";
<?
if(isset($system -> model))
   echo "mVobject.currentModel = \"".$system -> model -> getModelClass()."\";\n";

if(isset($system -> model -> sorter))
   echo "mVobject.sortField = \"".$system -> model -> sorter -> getField()."\";\n";

if(isset($system -> model))
{
	$parent = $system -> model -> findForeignParent();
	$linked_order_fields = $system -> model -> findDependedOrderFilters();	
}

if(isset($parent) && is_array($parent) && isset($system -> model -> filter))
	if(!$system -> model -> filter -> allowChangeOrderLinkedWithEnum($parent['name']))
		echo "mVobject.relatedParentFilter = \"".$parent['caption']."\";\n";

if(isset($linked_order_fields) && count($linked_order_fields))
	foreach($linked_order_fields as $name => $data)
		if(!$system -> model -> filter -> allowChangeOrderLinkedWithEnum($data[0]))
			echo "mVobject.dependedOrderFields.".$name." = \"".$data[1]."\";\n";
		
$has_applied_filters = (int) (isset($system -> model -> filter) && $system -> model -> filter -> ifAnyFilterApplied());
echo "mVobject.hasAppliedFilters = ".$has_applied_filters.";\n";      
      
if(isset($system -> model -> filter))
   if($caption = $system -> model -> filter -> ifFilteredByAllParents())
      echo "mVobject.allParentsFilter = \"".$caption."\";\n";
   else if(isset($system -> model -> pager))
      echo "mVobject.startOrder = ".($system -> model -> pager -> getStart() + 1).";\n";
	  
$region = $registry -> getSetting('Region');
?>
mVobject.dateFormat = "<? echo str_replace("yyyy", "yy", I18n :: getDateFormat()); ?>";
</script>
<?

CacheMedia :: addJavaScriptFile([
	$admin_media_path.'js/jquery.js',
	$admin_media_path.'js/jquery-ui.js',
	$admin_media_path.'js/form.js',
	$admin_media_path.'js/jquery.overlay.js',
	$admin_media_path.'js/dialogs.js',
	$admin_media_path.'js/date-time.js',
	$admin_media_path.'js/jquery.autocomplete.js',
	$admin_media_path.'js/modal.js',
	$admin_media_path.'js/utils.js',
	Registry :: get('AdminFolder').'/i18n/'.$region.'/jquery.ui.datepicker-'.$region.'.js'
]);

if($region != "en")
	CacheMedia :: addJavaScriptFile([
		Registry :: get('AdminFolder').'/i18n/'.$region.'/jquery-ui-timepicker-'.$region.'.js'
	]);


if(Router :: isLocalHost())
	echo CacheMedia :: getInitialFiles('js');
else
	echo CacheMedia :: getJavaScriptCache();
?>
<script type="text/javascript" src="<? echo $admin_panel_path; ?>ajax/autocomplete.php?locale=<? echo $region; ?>"></script>

<?
$skin = $system -> user -> getUserSkin();

if($skin)
{
	if($skin != "none")
	{
		$skin = $admin_panel_path."interface/skins/".$skin."/skin.css".$cache_drop;
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$skin."\" id=\"skin-css\" />\n";
	}
}
else
{
   $skins = $system -> user -> getAvailableSkins();
   echo "<script type=\"text/javascript\">$(document).ready(function() { openSkinChooseDialog([\"".implode("\",\"", $skins)."\"]); });</script>\n";
}
?>

<link rel="icon" href="<? echo $admin_panel_path; ?>interface/images/favicon.svg" type="image/x-icon" />
<link rel="shortcut icon" href="<? echo $admin_panel_path; ?>interface/images/favicon.svg" type="image/x-icon" />
</head>
<body>
<? include $registry -> getSetting("IncludeAdminPath")."includes/noscript.php"; ?>
<div id="container">
   <div id="header">
	      <div class="inner">
	      <a id="logo" href="<? echo $admin_panel_path; ?>">
		     <img src="<? echo $admin_panel_path; ?>interface/images/logo.svg<? echo $cache_drop; ?>" alt="MV logo" />
	      </a>
	      <div id="models-buttons">
	         <ul>
	            <li>
	                <span><? echo I18n :: locale("modules"); ?></span>
					<div id="models-list">
						<? echo $system -> menu -> displayModelsMenu(); ?>
					</div>
	            </li>
	         </ul>
	      </div>
	      <div id="header-search">
				<form action="<? echo $admin_panel_path; ?>controls/search.php" method="get">
	   			   <div>
                      <?
                      	  $header_search_value = "";
                      	  
                      	  if(isset($search_text) && preg_match("/\/search\.php$/", $_SERVER["SCRIPT_FILENAME"]))
                      	  	$header_search_value = $search_text;
                      ?>
				      <input class="string" type="text" name="text" placeholder="<? echo I18n :: locale('search-in-all-modules'); ?>" value="<? echo $header_search_value; ?>" />
				      <input type="submit" class="search-button" value="<? echo I18n :: locale('find'); ?>" />
				   </div>
				</form>
		    </div>      
	      <div id="user-settings">
	       <ul>
	         <li id="user-name"><span class="skin-color"><? echo $system -> user -> getField('name'); ?></span></li>
	         <li><a href="<? echo $admin_panel_path; ?>controls/user-settings.php"><? echo I18n :: locale("my-settings"); ?></a></li>
	         <? $logout_link = $admin_panel_path."login?logout=".Login :: getLogoutToken(); ?>
	         <li><a href="<? echo $registry -> getSetting('MainPath') ?>" target="_blank"><? echo I18n :: locale("to-site"); ?></a></li>
	         <li><a href="<? echo $logout_link; ?>"><? echo I18n :: locale("exit"); ?></a></li>
	       </ul>
	      </div>
      </div>
   </div>
   <? echo $system -> displayWarningMessages(); ?>