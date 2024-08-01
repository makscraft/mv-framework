
<script type="text/javascript">
$(document).ready(function()
{
	$.ajax({
		type: "POST",
		dataType: "json",
		url: mVobject.adminPanelPath + "ajax/session.php",
		data: "get-online-users=1",
		success: function(data)
		{           
            for(var i=0; i < data.length; i ++)
            	$("table.model-table :checkbox[name='item_" + data[i] + "']").parent().next()
                                                                             .append("<small class=\"online\">online</small>");
		}
	});
});
</script>