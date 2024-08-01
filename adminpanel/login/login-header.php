<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex, nofollow" />
<title><? echo I18n :: locale("mv"); ?></title>
<? $cache_drop = CacheMedia :: getDropMark(); ?>
<link rel="stylesheet" type="text/css" href="<? echo $registry -> getSetting("AdminPanelPath"); ?>interface/css/style-login.css<? echo $cache_drop; ?>" />

<link rel="icon" href="<? echo $registry -> getSetting("AdminPanelPath"); ?>interface/images/favicon.svg" type="image/x-icon" />
<link rel="shortcut icon" href="<? echo $registry -> getSetting("AdminPanelPath"); ?>interface/images/favicon.svg" type="image/x-icon" />

<?
$is_loading_page = preg_match("/\/loading\.php/", $_SERVER["REQUEST_URI"]);

if($is_loading_page)
{
	$url = $registry -> getSetting("AdminPanelPath");
	
	if(isset($_SESSION["login-back-url"]) && $_SESSION["login-back-url"])
	{
		$url .= $_SESSION["login-back-url"];
		unset($_SESSION["login-back-url"]);
	}

	echo "<meta http-equiv=\"refresh\" content=\"1; URL=".$url."\" />\n";
}
	
if(stripos($_SERVER["REQUEST_URI"], "/login/error.php") === false)
	include $registry -> getSetting("IncludeAdminPath")."includes/noscript.php";

if(!$is_loading_page):
?>

<script type="text/javascript">
	let adminPanelPath = "<? echo $registry -> getSetting("AdminPanelPath"); ?>";
</script>
<script type="text/javascript" src="<? echo $registry -> getSetting("AdminPanelPath"); ?>interface/js/jquery.js"></script>
<script type="text/javascript" src="<? echo $registry -> getSetting("AdminPanelPath"); ?>interface/js/login.js<? echo $cache_drop; ?>"></script>
<script type="text/javascript">
	$(document).ready(function() { $("form div.submit").append("<input type=\"hidden\" name=\"js-token\" value=\"<? echo Login :: getJavaScriptToken(); ?>\" />"); });
</script>
<? if(!isset($_SESSION["login"]["ajax-token"])): ?>
<script type="text/javascript"> $(document).ready(function(){ $.post(adminPanelPath + "ajax/login.php", {"data": "<? echo Login :: getAjaxInitialToken(); ?>"}); }); </script>
<? endif; ?>
<? endif; ?>
</head>
<body>