<? $hide_css = (isset($hide_captcha) && $hide_captcha) ? " hidden" : ""; ?> 
<div class="line<? echo $hide_css; ?>">
   <div class="name"><? echo I18n :: locale('captcha'); ?></div>
   <div class="captcha">
       <img src="<? echo $registry -> getSetting('AdminPanelPath'); ?>login/captcha/" alt="<? echo I18n :: locale('captcha'); ?>" />
       <input type="text" name="captcha" autocomplete="off" />
   </div>   
</div>