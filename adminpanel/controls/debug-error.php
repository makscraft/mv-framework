<?
$cache_drop = CacheMedia :: getDropMark();
$admin_panel_path = Registry :: get('AdminPanelPath');
$version = Registry :: getVersion();
$engine = ', '.Registry :: get('DbEngine');
$version_initial = Registry :: getInitialVersion();
$version_initial = ($version_initial != $version) ? $version_initial : null;

$backtrace = debug_backtrace();
unset($backtrace[0], $backtrace[1]);

foreach($backtrace as $key => $data)
	unset($backtrace[$key]['object'], $backtrace[$key]['type']);
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
		<meta name="robots" content="noindex,nofollow" />
		<title>MV framework</title>

		<link rel="stylesheet" type="text/css" href="<? echo $admin_panel_path; ?>interface/css/style-debug.css<? echo $cache_drop; ?>" />
		<link rel="icon" href="<? echo $admin_panel_path; ?>interface/images/favicon.svg" type="image/x-icon" />
		<link rel="shortcut icon" href="<? echo $admin_panel_path; ?>interface/images/favicon.svg" type="image/x-icon" />		
	</head>
	<body class="debug-page">
		<div id="debug-area">
			<header>
				<div class="inner">
					<img src="<? echo $admin_panel_path; ?>interface/images/logo.svg<? echo $cache_drop; ?>" alt="MV logo" />
					<div class="version">MV framework, version <? echo number_format($version, 1).$engine; ?></div>
					<? if($version_initial): ?>
						<div class="version-initial">updated from version <? echo number_format($version_initial, 1); ?></div>
					<? endif; ?>
				</div>
			</header>
			<section class="content">
				<div class="inner">
					<h1>Internal Script Error</h1>
					<? if(isset($debug_error) && $debug_error): ?>
						<h3><? echo $debug_error; ?></h3>
					<?
						endif;

						if(isset($debug_code) && $debug_code): 
					?>
						<pre class="code"><? echo $debug_code; ?></pre>
					<? endif; ?>
					<pre class="backtrace"><? print_r($backtrace); ?></pre>
				</div>
			</section>
		</div>
	</body>
</html>