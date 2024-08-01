<? 
include_once "../../config/autoload.php";

$login = new Login();
$i18n = I18n :: instance();
$region = I18n :: defineRegion();
$i18n -> setRegion($region);

if(isset($_GET['reason']) && in_array($_GET['reason'], array("ie", "js")))
	$reason = "error-".$_GET['reason'];
else
	$reason = "error-occured";

include $registry -> getSetting('IncludeAdminPath')."login/login-header.php";
?>

   <div id="container">
      <div id="login-area">
           <div id="login-middle">
              <div id="header" class="error"><? echo I18n :: locale('caution'); ?></div>
              <div class="errors">
                  <p><? echo I18n :: locale($reason); ?></p>
              </div>
              <a href="<? echo $registry -> getSetting('AdminPanelPath'); ?>login/" class="submit error-link"><? echo I18n :: locale('to-authorization-page'); ?></a>
           </div>
      </div>
   </div>
</body>
</html>