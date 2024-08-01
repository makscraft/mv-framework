<?
include "../../config/autoload.php";

if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']))
	exit();

if(!empty($_POST))
	session_start();

if(isset($_POST["data"], $_SESSION["login"]) && $_POST["data"])
	$_SESSION["login"]["ajax-token"] = preg_replace("/\W/", "", trim($_POST["data"]));

if(isset($_POST["login"], $_POST["password"]))
{	
	$login = new Login();

	$errors = [];
	$result = ["errors" => "", "action" => "", "captcha" => false];
	$login_attempts = $login -> checkAllAttemptsFromIp();
	$login_filled = isset($_POST['login']) ? trim(htmlspecialchars($_POST['login'], ENT_QUOTES)) : "";
	$password_filled = isset($_POST['password']) ? trim(htmlspecialchars($_POST['password'], ENT_QUOTES)) : "";

	if(!$login_filled)
		$errors[] = I18n :: locale("complete-login");
	
	if(!$password_filled)
		$errors[] = I18n :: locale("complete-password");

	if($login_attempts >= Login :: ATTEMPTS_NUMBER)
	{
		if(!isset($_POST['captcha']) || !trim($_POST['captcha']))
			$errors[] = I18n :: locale("complete-captcha");
		else if(!isset($_SESSION['login']['captcha']) || md5(trim($_POST['captcha'])) != $_SESSION['login']['captcha'])
			$errors[] = I18n :: locale("wrong-captcha");

		sleep(1);
	}

	if($login_attempts + 1 >= Login :: ATTEMPTS_NUMBER)
	{
		$result["captcha"] = time();
		$_SESSION['login']['captcha'] = Service :: randomString(7);
	}

	if($login_filled && $password_filled && !count($errors))
	{
		if($login_attempts < Login :: ATTEMPTS_NUMBER)
			$login -> addNewLoginAttempt($login_filled);

		if(!isset($_SESSION["login"]["ajax-token"]) || $_SESSION["login"]["ajax-token"] != Login :: getAjaxInitialToken())
		{
			unset($_SESSION["login"]["ajax-token"]);
			$errors[] = I18n :: locale("error-wrong-token");
			$result["action"] = "reload";
		}

		if(!isset($_POST["js-token"]) || $_POST["js-token"] != Login :: getJavaScriptToken())
		{
			$errors[] = I18n :: locale("error-wrong-token");
			$result["action"] = "reload";
		}

		if(!isset($_POST["admin-login-csrf-token"]) || $_POST["admin-login-csrf-token"] != Login :: getTokenCSRF())
		{
			$errors[] = I18n :: locale("error-wrong-token");
			$result["action"] = "reload";
		}

		if(!count($errors))
		{
			if($id = $login -> loginUser($login_filled, $password_filled))
			{
				if(isset($_POST['remember']) && $_POST['remember'])
					$login -> rememberUser($id);
			
				unset($_SESSION['login'], $_SESSION['login_captcha']);
			
				$user = new User($id);
				$user -> updateSetting('region', I18n :: defineRegion());
				
				$result["action"] = "start";
			}
			else
				$errors[] = I18n :: locale("login-failed");
		}
	}

	if(count($errors))
		$result["errors"] = $login -> displayLoginErrors($errors);

	header('Content-Type: application/json');
	echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
?>