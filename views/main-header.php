<!DOCTYPE html>
<html>
<head>
	<title><? echo $mv -> seo -> title; ?></title>
	<meta name="description" content="<? echo $mv -> seo -> description; ?>" />
	<meta name="keywords" content="<? echo $mv -> seo -> keywords; ?>" />

	<script type="text/javascript"> const rootPath = "<? echo $mv -> root_path; ?>"; </script>
	<script type="text/javascript" src="<? echo $mv -> media_path; ?>js/intro.js"></script>
	
	<link rel="stylesheet" type="text/css" href="<? echo $mv -> media_path; ?>css/intro.css"></style>

	<? echo $mv -> seo -> displayMetaData('head'); ?>
</head>
<body>
<header>
	<ul class="menu">
		<? echo $mv -> pages -> displayMenu(-1); ?>
		<li><a href="<? echo Registry :: get('AdminPanelPath'); ?>" target="_blank">Административная панель</a></li>
	</ul>
</header>