<?php
$content = $mv -> pages -> defineCurrentPage($mv -> router);
$mv -> display404($content);
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