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
	<p>Версия: <?php echo number_format(Registry :: getVersion(), 1).', '.Registry :: get('DbEngine'); ?><br>
	Среда разработки: <?php echo Registry :: get('APP_ENV'); ?><br>
	Папка проекта: <?php echo Registry :: get('IncludePath'); ?><br>
	Отключить отладочную панель: настройка 'DebugPanel' в файле config/setup.php<br><br>
	<a href="<?php echo Registry :: get('AdminPanelPath'); ?>" target="_blank">><?php echo I18n :: locale('admin-panel'); ?></a><br>
	Логин: <?php echo $user -> login; ?><br>
	Пароль: <?php echo Service :: checkHash('root', $user -> password) ? 'root' : '******'; ?></p>
	<?php echo $content -> content; ?>
</section>
<?php
include $mv -> views_path.'main-footer.php';
?>