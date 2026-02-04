<?php
$content = $mv -> pages -> defineCurrentPage($mv -> router);
$mv -> display404($content);
$mv -> seo -> mergeParams($content, 'name');

$user = (new Users) -> find(1);

include $mv -> views_path.'main-header.php';
?>
<main>
	<h1><?php echo $content -> name; ?></h1>
	
	<p class="settings"><span>Version: <?php echo Registry::getCorePackageVersion().', '.Registry::get('DbEngine'); ?></span><br>
	Environment: <?php echo Registry::get('APP_ENV'); ?><br>
	Project directory: <?php echo Registry::get('IncludePath'); ?><br>
	Switch off the debug panel: setting 'DebugPanel' in config/setup.php file</p>

	<p class="settings"><a href="<?php echo Registry::get('AdminPanelPath'); ?>" target="_blank"><?php echo I18n::locale('admin-panel'); ?></a><br>
	Login: <?php echo $user -> login; ?><br>
	Password: <?php echo Service::checkHash('root', $user -> password) ? 'root' : '******'; ?></p>

	<section class="banner">
		<a href="https://github.com/makscraft/workshop-mv" target="_blank">
			<img src="<?php echo $mv -> media_path; ?>/images/workshop.png" alt="MV workshop" />
		</a>
	</section>

	<section class="grid index">
		<?php echo $content -> content; ?>
	</section>
</main>
<?php
include $mv -> views_path.'main-footer.php';
?>