
<script type="text/javascript" src="<? echo $registry -> getSetting("AdminPanelPath"); ?>interface/js/rights-table.js"></script>

<tr>
   <td class="field-name"><? echo I18n :: locale("users-rights"); ?></td>
   <td class="field-content">
      <table cellpadding="0" cellspacing="0" id="rights-table">
	      <tr>
		      <th class="modules-rights"><? echo I18n :: locale('modules')." / ".I18n :: locale('operations'); ?></th>
		      <th><? echo I18n :: locale('create'); ?></th>
		      <th><? echo I18n :: locale('read'); ?></th>
		      <th><? echo I18n :: locale('edit'); ?></th>
		      <th><? echo I18n :: locale('delete'); ?></th>
	      </tr>
	      <?
	      	  echo $system -> model -> displayUsersRights(); 
	      ?>
      </table>      
   </td>
</tr>
<tr>
   <td class="field-name" colspan="2">
      <? $checked = (isset($_POST["send_admin_info_email"]) && $_POST["send_admin_info_email"]) ? " checked=\"checked\"" : ""; ;?>
      <input type="checkbox"<? echo $checked; ?> id="send-admin-info-email" name="send_admin_info_email" value="1" />
      <label for="send-admin-info-email"><? echo I18n :: locale("send-user-info"); ?></label>
   </td>
</tr>
