<?
if(isset($_POST['email']) && trim($_POST['email']))
	$email = htmlspecialchars(trim($_POST['email']), ENT_QUOTES);
else
	$email = "";
?>
<form method="post" class="login-form">
   <? echo $login -> displayLoginErrors($errors); ?>
   <div class="line">
      <div class="name"><? echo I18n :: locale('email'); ?></div>
      <input type="text" name="email" value="<? echo $email; ?>" autocomplete="off" />
   </div>
   <? include $registry -> getSetting('IncludeAdminPath')."login/captcha.php"; ?>
  <div class="submit">
     <input class="submit" type="submit" value="<? echo I18n :: locale('restore'); ?>" />
     <input type="hidden" name="admin-login-csrf-token" value="<? echo $login -> getTokenCSRF(); ?>" />
  </div>
  <div class="cancel">
     <a href="<? echo $registry -> getSetting('AdminPanelPath'); ?>login/"><? echo I18n :: locale('cancel'); ?></a>
  </div>
</form>