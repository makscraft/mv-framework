<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php echo $mv -> seo -> title; ?></title>
	<meta name="description" content="<?php echo $mv -> seo -> description; ?>">
	<meta name="keywords" content="<?php echo $mv -> seo -> keywords; ?>">

	<script type="text/javascript"> const rootPath = "<?php echo $mv -> root_path; ?>"; </script>
	<script type="text/javascript" src="<?php echo $mv -> media_path; ?>js/intro.js"></script>
	
	<link rel="stylesheet" type="text/css" href="<?php echo $mv -> media_path; ?>css/intro.css"></style>

	<?php echo $mv -> seo -> displayMetaData('head'); ?>
</head>
<body>
<header>
	<a class="logo" href="<?php echo $mv -> root_path; ?>">
		<img src="<?php echo Registry::get('AdminPanelPath'); ?>interface/images/logo.svg" alt="MV logo" />
	</a>
	<ul class="menu">
		<?php echo $mv -> pages -> displayMenu(-1); ?>
		<li><a href="<?php echo Registry::get('AdminPanelPath'); ?>" target="_blank"><?php echo I18n::locale('admin-panel'); ?></a></li>
	</ul>
</header>