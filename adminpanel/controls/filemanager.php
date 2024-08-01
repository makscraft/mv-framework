<?
include_once "../../config/autoload.php";

$system = new System();
$system -> user -> extraCheckModelRights("file_manager", "read");

$filemanager = new Filemanager();
$filemanager -> setUser($system -> user) -> setToken($system -> getToken());
$url_params = $filemanager -> pager -> getUrlParams();

if(isset($_GET["done"]) || isset($_GET["error"]))
{
	$key = isset($_GET["done"]) ? "done" : "error";	
	$_SESSION["message"][$key] = $_GET[$key]; 
	$filemanager -> reload($url_params);
}
else
	$url_params = $url_params ? "&".$url_params : "";

$allowed_limits = array(5, 10, 15, 20, 30, 50, 100, 200, 300, 500);

if(isset($_GET['pager-limit']) && in_array(intval($_GET['pager-limit']), $allowed_limits))
{
	$filemanager -> pager -> setLimit(intval($_GET['pager-limit']));
	$_SESSION['mv']['settings']['pager-limit'] = $filemanager -> pager -> getLimit();
	$system -> user -> saveSettings($_SESSION['mv']['settings']);	
	$filemanager -> reload();
}
else if(isset($_SESSION['mv']['settings']['pager-limit']))
	$filemanager -> pager -> setLimit($_SESSION['mv']['settings']['pager-limit']);

foreach(array('create-folder','upload-file','delete-many','delete-file','action','delete-folder') as $action)
	if(isset($_GET[$action]))
		if(!isset($_GET["token"]) || $_GET["token"] != $system -> getToken())
			$filemanager -> reload("error=error-wrong-token");

if(isset($_GET['create-folder'], $_POST['new-folder']))
{
	$system -> user -> extraCheckModelRights("file_manager", "create");
	$filemanager -> reload($filemanager -> createFolder($_POST['new-folder']).$url_params);
}
else if(isset($_GET['upload-file'], $_FILES['new-file']))
{
	$system -> user -> extraCheckModelRights("file_manager", "create");
	$filemanager -> reload($filemanager -> uploadFile($_FILES['new-file']).$url_params);
}
else if(isset($_GET['delete-many']) && !empty($_POST) && $filemanager -> checkDeleteMany())
{
	$system -> user -> extraCheckModelRights("file_manager", "delete");
	$result = $filemanager -> deleteManyFiles();
	$result = $result ? "done=done-delete" : "error=not-deleted";
	$filemanager -> reload($result.$url_params);
}
else if(isset($_GET['delete-file']) || isset($_GET['delete-folder']))
{
	$result = isset($_GET['delete-file']) ? $filemanager -> deleteFile($_GET['delete-file']) : $filemanager -> deleteFolder($_GET['delete-folder']);
	$result = $result ? "done=done-delete" : "error=not-deleted";
	$filemanager -> reload($result.$url_params);
}
else if(isset($_GET['action']) && $_GET['action'] == 'buffer-paste')
{
	$result = $filemanager -> pasteFromBuffer();
	
	if(strpos($result, "error=") === false)
		$result = $result ? 'done=done-operation' : 'error=error-failed';
	
	$filemanager -> reload($result.$url_params);
}
else if(isset($_GET['action'], $_POST['old-name'], $_POST['new-name']) && $_GET['action'] == 'rename')
{
	$result = $filemanager -> renameFileOrFolder(trim($_POST['old-name']), trim($_POST['new-name']));
	
	if(strpos($result, "error=") === false)
		$result = $result ? 'done=done-operation' : 'error=error-failed';
		
	$filemanager -> reload($result.$url_params);
}

CacheMedia :: addJavaScriptFile([
	Registry :: get('AdminFolder').'/interface/js/jquery.contextmenu.js',
	Registry :: get('AdminFolder').'/interface/js/file-manager.js'
]);

CacheMedia :: addCssFile([
	Registry :: get('AdminFolder').'/interface/css/style-filemanager.css'
]);

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<div id="columns-wrapper">
    <div id="files-table">
         <div class="column-inner">
            <h3 class="column-header"><? echo I18n :: locale('file-manager'); ?></h3>
	            <?
	            	$done_keys = array('folder-created','file-uploaded','file-deleted','folder-deleted',
	            					   'done-delete','done-operation');
	            	
	            	$error_keys = array('folder-not-created','file-exists','folder-exists','upload-file-error',
			            				'wrong-filemanager-type','not-deleted','bad-folder-name','bad-file-name',
			            				'error-failed','bad-extetsion','error-wrong-token');
	            	
	            	if(isset($_SESSION["message"]['done']) && in_array($_SESSION["message"]['done'], $done_keys))
          				echo "<div class=\"form-no-errors\"><p>".I18n :: locale($_SESSION["message"]['done'])."</p></div>\n";
         			else if(isset($_SESSION["message"]['error']) && in_array($_SESSION["message"]['error'], $error_keys))
         				echo "<div class=\"form-errors\"><p>".I18n :: locale($_SESSION["message"]['error'])."</p></div>\n";
         				
         			unset($_SESSION["message"]);
	            ?>
               <div id="path">
                  <? echo $filemanager -> displayPath(); ?>
               </div>
               <form id="filemanager-form" method="post" action="?delete-many<? echo $url_params."&token=".$system -> getToken(); ?>">
		            <table class="model-table filemanager">
						<tr>
		                  <th class="check-all"><input type="checkbox" /></th>
						  <th class="middle"><? echo I18n :: locale('name'); ?></th>
						  <th class="middle"><? echo I18n :: locale('size'); ?></th>
						  <th class="middle"><? echo I18n :: locale('last-change'); ?></th>
						</tr>
                        <? echo $filemanager -> display(); ?>
		            </table>
	                <div id="navigation">
		                 <? 
		                     if($system -> user -> checkModelRights("file_manager", "delete"))
		                        $submit_button = " onclick=\"dialogs.showDeleteFilesMessage()\"";
		                     else
		                        $submit_button = " onclick=\"$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});\"";
		                 ?>
	                     <div class="buttons">
	                        <input class="button-light" type="button" <? echo $submit_button; ?> value="<? echo I18n :: locale('delete-checked'); ?>" />
                            <input type="hidden" name="admin-panel-csrf-token" value="<? echo $system -> getToken(); ?>" />
	                     </div>
	                     <div class="pager-limit">
	                        <span><? echo I18n :: locale('pager-limit'); ?></span>
					        <select>
					            <? echo $filemanager -> pager -> displayPagerLimits($allowed_limits); ?>
					        </select>
	                        <input type="hidden" value="filemanager" />
	                     </div>
	                     <? echo $filemanager -> pager -> displayPagesAdmin(); ?>
	                </div>
                    <div class="clear"></div>
                </form>
                <? 
                     if($system -> user -> checkModelRights("file_manager", "create"))
                         $submit_button = "type=\"submit\"";
                     else
                         $submit_button = "type=\"button\" onclick=\"$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});\"";
                ?>
                <div id="upload-file">
                     <h3><? echo I18n :: locale('upload-file'); ?></h3>
                     <form action="?upload-file&token=<? echo $system -> getToken().$url_params; ?>" method="post" enctype="multipart/form-data">
                        <div>
                           <input type="file" name="new-file" />
                           <p><input class="button-light" <? echo $submit_button; ?> value="<? echo I18n :: locale('upload'); ?>" /></p>
                        </div>
                     </form>
                </div>
                <div id="create-folder">
                     <h3><? echo I18n :: locale('create-folder'); ?></h3>
                     <form action="?create-folder&token=<? echo $system -> getToken().$url_params; ?>" method="post">
                        <div>
                           <input type="text" class="borderd" name="new-folder" />
                           <p><input class="button-light" <? echo $submit_button; ?> value="<? echo I18n :: locale('create'); ?>" /></p>
                        </div>
                     </form>                     
                </div>
		        <ul id="filemanager-menu" class="context-menu">
		           <li class="view"><a href="#view"><? echo I18n :: locale('read'); ?></a></li>
		           <li class="cut"><a href="#cut"><? echo I18n :: locale('cut'); ?></a></li>
		           <li class="copy"><a href="#copy"><? echo I18n :: locale('copy'); ?></a></li>
		           <li class="paste"><a href="#paste"><? echo I18n :: locale('paste'); ?></a></li>
		           <li class="rename"><a href="#rename"><? echo I18n :: locale('rename'); ?></a></li>
		           <li class="delete"><a href="#delete"><? echo I18n :: locale('delete'); ?></a></li>
		        </ul>                
         </div>
    </div>
    <div id="files-params">
            <h3><? echo I18n :: locale('file-params'); ?></h3>
            <table id="file-params-table">
               <tr>
                  <td colspan="2" id="file-image">
                     <?
                         if(isset($_SESSION['mv']['just-uploaded-file']))
                         {
                         	$filemanager -> setFirstFile($_SESSION['mv']['just-uploaded-file']);
                         	unset($_SESSION['mv']['just-uploaded-file']);
                         }
                         
                     	 if($filemanager -> getFirstFile())
							echo $filemanager -> displayImage($filemanager -> getFirstFile());
                     ?>
                  </td>
               </tr>
               <tr>
                  <td colspan="2" id="file-data">
                     <table>
	                     <?
							if($filemanager -> getFirstFile())
	                     		echo $filemanager -> displayFileParams($filemanager -> getFirstFile());
	                     ?>                        
                     </table>
                  </td>
               </tr>
            </table>
    </div>
    <div class="clear"></div>
 </div>
<?
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>