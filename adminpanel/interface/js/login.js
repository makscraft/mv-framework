$(document).ready(function()
{
   $("#select-login-region").change(function() 
   { 
      location.href = adminPanelPath + "login/?region=" + $(this).val(); 
   });

   $("form.login-form input.submit").on("click", function()
   {
      $.ajax({
           type: "POST",
           dataType: "json",
           data: $("form.login-form").serialize(),
           url: adminPanelPath + "ajax/login.php",
           success: function(data)
           {
               if(data.action == "start" && !data.errors)
               {
                  location.href = adminPanelPath + "login/loading.php";
                  return;
               }

               let errors = $("form.login-form div.errors").length;
               $("form.login-form").find("div.errors, div.success").remove();
               
               if(data.errors)
               {
                  $("form.login-form").prepend(data.errors);
                  $("form.login-form div.errors").hide();

                  if(errors)
                     $("form.login-form div.errors").fadeIn(500);
                  else
                     $("form.login-form div.errors").show(500);
               }

               if(data.action == "reload")
               {
                  setTimeout(function(){ location.reload(); }, 2000);
                  return;
               }

               if(data.captcha)
               {
                  let new_captcha = $('<img src="' + adminPanelPath + 'login/captcha/?' + data.captcha + '" />');

                  $(new_captcha).load(function()
                  {
                      $("form.login-form div.captcha > img").replaceWith(new_captcha);
                      $("form.login-form div.captcha input").val("");

                      $("form.login-form div.captcha").parent().removeClass("hidden");
                  });
               }
           }
      });
   });

   $("form.login-form input[type='text'], form.login-form input[type='password']").on("keyup", function(event)
   {
      if(event.keyCode == 13)
         $("form.login-form input.submit").click();
   });
});