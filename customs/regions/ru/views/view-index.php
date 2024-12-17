<?php
$content = $mv -> pages -> defineCurrentPage($mv -> router);
$mv -> display404($content);
$mv -> seo -> mergeParams($content, 'name');

$user = (new Users) -> find(1);

include $mv -> views_path.'main-header.php';
?>
<main>
	<h1><?php echo $content -> name; ?></h1>
	
	<p class="settings"><span>Версия: <?php echo Registry::getCorePackageVersion().', '.Registry::get('DbEngine'); ?></span><br>
	Среда разработки: <?php echo Registry::get('APP_ENV'); ?><br>
	Папка проекта: <?php echo Registry::get('IncludePath'); ?><br>
	Отключить отладочную панель: настройка 'DebugPanel' в файле config/setup.php</p>

	<p class="settings"><a href="<?php echo Registry::get('AdminPanelPath'); ?>" target="_blank"><?php echo I18n::locale('admin-panel'); ?></a><br>
	Логин: <?php echo $user -> login; ?><br>
	Пароль: <?php echo Service::checkHash('root', $user -> password) ? 'root' : '******'; ?></p>

	<section class="grid index">
		<?php echo $content -> content; ?>
	</section>
</main>
<?php
include $mv -> views_path.'main-footer.php';
?>