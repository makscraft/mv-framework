<?
session_start();
require_once "kcaptcha.php";

$captcha = new Captcha();
$_SESSION['captcha'] = $captcha -> getKeyString();
?>