<?
$cache_drop = CacheMedia :: getDropMark();
$admin_panel_path = Registry :: get('AdminPanelPath');
$version = Registry :: getVersion();
$engine = Registry :: get('DbEngine');
$worktime = Debug :: getWorkTime();
$route = '~/views/'.$GLOBALS['mv'] -> router -> getRoute();
$cookie_path = Registry :: get('MainPath');
$wrapped = isset($_COOKIE['_mv_debug_panel']) && $_COOKIE['_mv_debug_panel'] == 'wrapped' ? ' wrapped' : '';
?>
<link rel="stylesheet" type="text/css" href="<? echo $admin_panel_path; ?>interface/css/style-debug.css<? echo $cache_drop; ?>" />
<div class="mv-debug-panel<? echo $wrapped; ?>">
    <img id="mv-debug-panel-logo" src="<? echo $admin_panel_path; ?>interface/images/logo.svg<? echo $cache_drop; ?>" alt="MV logo" />
    <div>Build: <? echo (int) Registry :: get('Build'); ?></div>
    <div>Worktime: <? echo round($worktime, 4); ?> sec.</div>
    <div>View: <? echo $route; ?></div>
    <div class="sql-section">
        <span class="number">SQL queries: <? echo count(Database :: $total); ?></span>
        <span id="mv-debug-panel-queries" class="mv-debug-panel-button">view all</span>
    </div>
    <div>Memory peak: <? echo I18n :: convertFileSize(memory_get_peak_usage()); ?></div>
    <div>MV version: <? echo number_format($version, 1); ?></div>
    <div id="mv-debug-panel-queries-list">
        <? foreach(Database :: $total as $count => $query): ?>
            <div><span><? echo $count + 1; ?></span><div><? echo $query; ?></div></div>
        <? endforeach; ?>
    </div>
    <div>PHP: <? echo PHP_VERSION; ?></div>
</div>
<script>
    window.onload = function()
    {
        let sql_list_bottom = document.getElementsByClassName('mv-debug-panel')[0].offsetHeight + 3;

        document.getElementById('mv-debug-panel-queries').onclick = function()
        {
            let object = document.getElementById('mv-debug-panel-queries-list');
            object.classList.toggle('active');
            object.style.bottom = sql_list_bottom + 'px';
        }

        document.getElementById('mv-debug-panel-logo').onclick = function()
        {
            let cookies = document.cookie.split(';');
            let current = '';
            let expires = new Date();
            expires.setTime(expires.getTime() + (30 * 60 * 60 * 24 * 1000));
            expires = '; expires=' + expires.toUTCString();
            
            for(index in cookies)
                if(cookies[index].match(/_mv_debug_panel=/))
                    current = cookies[index].split('=')[1].replace(' ', '');

            current = current == '' ? 'wrapped' : '';
            document.cookie = '_mv_debug_panel=' + current + expires + '; path=<? echo $cookie_path; ?>';

            panel = document.getElementsByClassName('mv-debug-panel')[0].classList.toggle('wrapped');
        }
    }
</script>