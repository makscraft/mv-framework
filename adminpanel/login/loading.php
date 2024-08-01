<? 
include_once "../../config/autoload.php";
$i18n = I18n :: instance();
$region = I18n :: defineRegion();
$i18n -> setRegion($region);

if(!isset($_SESSION))
	session_start();

include $registry -> getSetting("IncludeAdminPath")."login/login-header.php";
?>
   <div id="container">
      <div id="login-area">
         <div id="login-middle">
            <div id="header"><? echo I18n :: locale('get-ready'); ?></div>
         </div>
      </div>
   </div>
</body>
</html>