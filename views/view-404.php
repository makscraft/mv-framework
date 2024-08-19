<?php
$content = $mv -> pages -> findRecord(array('url' => 'e404'));
$mv -> seo -> mergeParams($content, 'name');

include $mv -> views_path.'main-header.php';
?>
<section class="content">
	<h1><?php echo $content -> name; ?></h1>
	<?php echo $content -> content; ?>
</section>
<?php
include $mv -> views_path.'main-footer.php';
?>