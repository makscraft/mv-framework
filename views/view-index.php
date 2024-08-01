<?
$content = $mv -> pages -> defineCurrentPage($mv -> router);
$mv -> display404($content);
$mv -> seo -> mergeParams($content, 'name');

include $mv -> views_path.'main-header.php';
?>
<section class="content">
	<h1><? echo $content -> name; ?></h1>
	<p><img src="<? echo $mv -> root_path; ?>adminpanel/interface/images/logo.svg" alt="MV logo" /></p>
	<p>Версия: <? echo number_format(Registry :: getVersion(), 1).', '.Registry :: get('DbEngine'); ?><br>
	Папка проекта: <? echo Registry :: get('IncludePath'); ?><br>
	Отключить отладочную панель: настройка 'DebugPanel' в файле config/setup.php<br>
	<a href="<? echo Registry :: get('AdminPanelPath'); ?>" target="_blank">Административная панель</a>,
	логин: root, пароль: root</p>
	<? echo $content -> content; ?>
</section>
<?
include $mv -> views_path.'main-footer.php';
?>