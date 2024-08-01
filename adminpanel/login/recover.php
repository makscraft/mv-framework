<?
include_once "../../config/autoload.php";
sleep(1);

$login = new Login();
$i18n = I18n :: instance();
$region = I18n :: defineRegion();
$i18n -> setRegion($region);

if(isset($_GET["code"], $_GET["token"]) && $_GET["code"] && $_GET["token"])
	if(!$login -> checkNewPasswordParams($_GET["code"], $_GET["token"]))
	{
		$_SESSION['login']['message'] = I18n :: locale("password-not-confirmed");
		$_SESSION['login']['message-css'] = "errors";
		
		$login -> reload("login/");
	}
	else
		$login -> reload("login/recover.php");
		
if(!isset($_SESSION['login']['change-password']))
	$login -> reload("login/");

$fields = array(array("{password}", "password", "password", array("required" => true,
																					   "letters_required" => true,
																					   "digits_required" => true)),
					 array("{password-repeat}", "password", "password_repeat", array("required" => true,
																										  "letters_required" => true,
																										  "digits_required" => true)));

$form = new Form($fields);
$errors = array();

if(!empty($_POST))
{
	$form -> getDataFromPost() -> validate();
	
	foreach($form -> getErrors() as $error)
		$errors[] = $form -> displayOneError($error);
	
	if(!count($errors) && $form -> password != $form -> password_repeat)
		$errors[] = I18n :: locale("passwords-must-match");
	
	if(!isset($_SESSION["login"]["ajax-token"]) || $_SESSION["login"]["ajax-token"] != Login :: getAjaxInitialToken())
		$errors[] = I18n :: locale("error-wrong-token");

	if(!isset($_POST["js-token"]) || $_POST["js-token"] != Login :: getJavaScriptToken())
		$errors[] = I18n :: locale("error-wrong-token");

	if(!isset($_POST["admin-login-csrf-token"]) || $_POST["admin-login-csrf-token"] != Login :: getTokenCSRF())
		$errors[] = I18n :: locale("error-wrong-token");
	
	if(!count($errors))
	{
		$login -> saveNewPassword($_SESSION['login']['change-password'], $form -> password);
		$_SESSION['login']['message'] = I18n :: locale("password-confirmed");
		$_SESSION['login']['message-css'] = "success";
		 
		$login -> reload("login/");
	}
}

include $registry -> getSetting('IncludeAdminPath')."login/login-header.php";
?>
	<div id="container">
	   <div id="login-area">
           <div id="login-top"></div>
           <div id="login-middle">
	           <div id="header"><? echo I18n :: locale('password-restore'); ?></div>
               <form method="post" class="login-form">
                  <? echo $login -> displayLoginErrors($errors); ?>
                  <div class="line">
                     <div class="name"><? echo I18n :: locale('new-password'); ?></div>
                     <input class="password" type="password" name="password" value="<? echo $form -> password; ?>" autocomplete="off" />
                  </div>
                  <div class="line">
                     <div class="name"><? echo I18n :: locale('password-repeat'); ?></div>
                     <input class="password" type="password" name="password_repeat" value="<? echo $form -> password_repeat; ?>" autocomplete="off" />
                  </div>
                  <div class="submit">
                     <input class="submit" type="submit" value="<? echo I18n :: locale('restore'); ?>" />
                     <input type="hidden" name="admin-login-csrf-token" value="<? echo $login -> getTokenCSRF(); ?>" />
                  </div>
                  <div class="cancel">
                     <a href="<? echo $registry -> getSetting('AdminPanelPath'); ?>login/"><? echo I18n :: locale('cancel'); ?></a>
                  </div>               
               </form>
           </div>
           <div id="login-bottom"></div>
	   </div>
	</div>
</body>
</html>