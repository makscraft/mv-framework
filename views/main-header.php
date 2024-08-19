<!DOCTYPE html>
<html>
<head>
	<title><?php echo $mv -> seo -> title; ?></title>
	<meta name="description" content="<?php echo $mv -> seo -> description; ?>" />
	<meta name="keywords" content="<?php echo $mv -> seo -> keywords; ?>" />

	<script type="text/javascript"> const rootPath = "<?php echo $mv -> root_path; ?>"; </script>
	<script type="text/javascript" src="<?php echo $mv -> media_path; ?>js/intro.js"></script>
	
	<link rel="stylesheet" type="text/css" href="<?php echo $mv -> media_path; ?>css/intro.css"></style>

	<?php echo $mv -> seo -> displayMetaData('head'); ?>
</head>
<body>
<header>
	<ul class="menu">
		<?php echo $mv -> pages -> displayMenu(-1); ?>
		<li><a href="<?php echo Registry :: get('AdminPanelPath'); ?>" target="_blank">Административная панель</a></li>
	</ul>
</header>