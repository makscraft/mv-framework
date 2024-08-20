<?php
$content = $mv -> pages -> defineCurrentPage($mv -> router);
$mv -> display404($content);
$mv -> seo -> mergeParams($content, 'name');

$user = (new Users) -> find(1);

include $mv -> views_path.'main-header.php';
?>
<section class="content">
	<h1><?php echo $content -> name; ?></h1>
	<p><img src="<?php echo $mv -> root_path; ?>adminpanel/interface/images/logo.svg" alt="MV logo" /></p>
	<p>Version: <?php echo number_format(Registry :: getVersion(), 1).', '.Registry :: get('DbEngine'); ?><br>
	Environment: <?php echo Registry :: get('APP_ENV'); ?><br>
	Project directory: <?php echo Registry :: get('IncludePath'); ?><br>
	Switch off the debug panel: setting 'DebugPanel' in config/setup.php file<br><br>
	<a href="<?php echo Registry :: get('AdminPanelPath'); ?>" target="_blank">Admin panel</a><br>
	Login: <?php echo $user -> login; ?><br>
	Password: <?php echo Service :: checkHash('root', $user -> password) ? 'root' : '******'; ?></p>
	<?php echo $content -> content; ?>
</section>
<?php
include $mv -> views_path.'main-footer.php';
?>