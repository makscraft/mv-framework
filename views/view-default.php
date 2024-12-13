<?php
$content = $mv -> pages -> defineCurrentPage($mv -> router);
$mv -> display404($content);
$mv -> seo -> mergeParams($content, 'name');

include $mv -> views_path.'main-header.php';
?>
<main>
	<section class="grid">
		<?php echo $content -> content; ?>
	</section>
</main>
<?php
include $mv -> views_path.'main-footer.php';
?>