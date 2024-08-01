<?
include_once "../../config/autoload.php";
$system = new System();

if(isset($_GET["text"]) && trim($_GET["text"]) != "")
	$search_text = trim(htmlspecialchars(urldecode($_GET["text"]), ENT_QUOTES));
else
	$search_text = "";

$search_text = Service :: cleanHtmlSpecialChars($search_text);	
	
if($search_text && mb_strlen($search_text) > 1)
	$result = $system -> searchInAllModels($search_text);
else
	$result = array("number" => 0, "html" => array());
	
$url_params = $search_text ? "text=".$search_text : "";
	
$pager = new Paginator($result["number"], 10);
$allowed_limits = array(5, 10, 15, 20, 30, 50, 100, 200, 300, 500);

if(isset($_GET['pager-limit']) && in_array(intval($_GET['pager-limit']), $allowed_limits))
{
	$pager -> setLimit(intval($_GET['pager-limit']));
	$_SESSION['mv']['settings']['pager-limit'] = $pager -> getLimit();
	$system -> user -> saveSettings($_SESSION['mv']['settings']);
		
	$system -> reload("controls/search.php".($url_params ? "?".$url_params : ""));	
}

if(isset($_SESSION['mv']['settings']['pager-limit']))
	$pager -> setLimit($_SESSION['mv']['settings']['pager-limit']);

$pager -> setUrlParams($url_params);
$html = array_slice($result["html"], $pager -> getStart(), $pager -> getLimit());
$html = implode("", $html);

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<div id="columns-wrapper">
    <div id="index-search" class="search-page">
         <h3 class="column-header"><? echo I18n :: locale('search'); ?>
            <span class="header-info"><? echo I18n :: locale('results-found'); ?>: <? echo $result["number"]; ?></span>
         </h3>
         <div id="search-results">
            <? echo $html; ?>
         </div>
         <div id="search-pager">
            <div class="pager-limit">
	            <span><? echo I18n :: locale('pager-limit'); ?></span>
	            <select>
	            	<? echo $pager -> displayPagerLimits($allowed_limits); ?>
	            </select>
                <input type="hidden" value="controls/search.php?<? echo $url_params; ?>" />
	         </div>
             <? echo $pager -> displayPagesAdmin(); ?>
             <div class="clear"></div>	         
         </div>    
    </div>            
</div>
<?
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>