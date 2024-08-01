<?
include_once "../config/autoload.php";
$system = new System();

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<div class="index-models">
   <? echo $system -> menu -> displayModelsMenu(); ?>
</div>
<div id="index-icons">
	<ul>
		<li class="users">
	      <a href="<? echo $registry -> getSetting('AdminPanelPath'); ?>model/?model=users">
            <span><? echo I18n :: locale("users"); ?></span>
            <? echo I18n :: locale("index-users-icon"); ?>
          </a>
	    </li>
		  <li class="garbage">
	      <a href="<? echo $registry -> getSetting('AdminPanelPath'); ?>model/?model=garbage">
            <span><? echo I18n :: locale("garbage"); ?></span>
            <? echo I18n :: locale("index-garbage-icon"); ?>
          </a>
        </li>
		  <li class="history">
	      <a href="<? echo $registry -> getSetting('AdminPanelPath'); ?>model/?model=log">
            <span><? echo I18n :: locale("logs"); ?></span>
            <? echo I18n :: locale("index-history-icon"); ?>
          </a>
        </li>
		  <li class="filemanager">
	      <a href="<? echo $registry -> getSetting('AdminPanelPath'); ?>controls/filemanager.php">
            <span><? echo I18n :: locale("file-manager"); ?></span>
            <? echo I18n :: locale("index-file-manager-icon"); ?>
          </a>
	    </li>         
	</ul>
</div>
<?
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>