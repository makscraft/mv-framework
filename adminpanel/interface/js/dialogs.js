
//Main object for opening alert and confirms
var dialogs = {

		multi_action_total: 0,
		
		prepareMessage: function(message, params)
		{
			var message_ = message;
			
			if(message_.match(/^\{\w+\}$/))
				message = mVobject.locale(message.replace(/\{(.*)\}/, "$1"), params);
			
			return message;
		},

		//Alert message with the only one button 'OK'
		showAlertMessage: function(message)
		{
			var params = (typeof(arguments[1]) == "object") ? arguments[1] : {};
				
			//Adds the text of message
			$("#message-alert div.message").html(this.prepareMessage(message, params));
	    	$("#message-alert").overlay(mVobject.paramsForDialogs).load();
		},
		
		//Message when delete one element pushing the button with cross "x"
		showConfirmMessage: function(message, url, name)
		{
			//Text of message from language file
			$("#message-confirm-delete div.message").html(this.prepareMessage(message, {name: name}));			
			$("#message-confirm-delete div.message").removeClass("update").removeClass("restore").removeClass("delete");
			
			var css_class = "update"; //Icon to show in message
			
			if(url.indexOf("action=delete") != -1)
				css_class = "delete";
			else if(message == "{add_image_comment}")
				css_class = "comment";
			
			$("#message-confirm-delete div.message").addClass(css_class);
			
			var href = url.match(/\.php/) ? url : mVobject.adminPanelPath + "model/?" + mVobject.urlParams + "&" + url;
			
			$("#message-confirm-delete #butt-ok").attr("name", "href->" + href);  	
	    				
			//Shows overlay window
	    	$("#message-confirm-delete").overlay(mVobject.paramsForDialogs).load();
		},
		
		showRenameMessage: function(message, form, name)
		{
			$("#message-confirm-delete div.message").html(this.prepareMessage(message, {name: name}));			
			$("#message-confirm-delete div.message").removeClass("restore").removeClass("delete").addClass("update");
			$("#message-confirm-delete #butt-ok").attr("name", "form->" + form);  				
	    	$("#message-confirm-delete").overlay(mVobject.paramsForDialogs).load();			
		},
		
		showDeleteFilesMessage: function()
		{
			var number = $("table.model-table tr:gt(0) :checkbox:checked").size();
			
			if(!number)
				return;
			
			var _this = this;
			
			$.ajax({
				type: "POST",
				dataType: "text",
				url: mVobject.adminPanelPath + "ajax/filemanager.php",
				data: "number_files=" + number,
				success: function(data)
				{
					var message = _this.prepareMessage('{delete_files}', {number_files: data});
					$("#message-confirm-delete div.message").addClass('delete').text(message);
					
					//Sets action to do if OK pressed
					$("#message-confirm-delete #butt-ok").attr("name", "form->#filemanager-form");

					//Shows overlay window
			    	$("#message-confirm-delete").overlay(mVobject.paramsForDialogs).load();
				}
			});
		},

		//Message for mass action under many elements at one time (delete or update) 
		showMultiActionMessage: function(multi_action, multi_value)
		{
			//Number of checked checkboxes in table
			let checked_number = parseInt($("table.model-table tr:gt(0) :checkbox:checked").size());
			
			if(!checked_number)
				return;
			else
				this.multi_action_total = checked_number;
			
			let ajax_params = "action=" + multi_action + "&value=" + multi_value;
			let checked_elements = [];
			
			$("table.model-table tr:gt(0) :checkbox:checked").each(function()
			{
				let id = parseInt($(this).attr("name").replace("item_", ""));
				
				if(id)
					checked_elements.push(id)
			});
			
			ajax_params += "&ids=" + checked_elements.join(',') + "&" + mVobject.urlParams;
			
			$.ajax({
				type: "POST",
				dataType: "xml",
				url: mVobject.adminPanelPath + "ajax/multi-actions.php",
				data: ajax_params,
				success: function(data)
				{
					if(!data)
					{
						location.reload();
						return;
					}
					
					var locale_params = {number: checked_number};
					
					if($(data).find('error').size())
					{
						dialogs.showAlertMessage('{error_data_transfer}');
						return;
					}

					if(multi_action == 'delete')
						message_key = 'delete_many';
					else if(multi_action == 'restore')
						message_key = 'restore_many';
					else
						message_key = 'update_many';
					
					locale_params.number_records = $(data).find('response').find('number_records').text();
					
					if(multi_action != 'delete' && multi_action != 'restore')
					{
						var type = $(data).find('response').find('type').text();
						locale_params.caption = $(data).find('response').find('caption').text();
						
						if(type == 'bool')
						{
							message_key += '_' + type;
							locale_params.value = $(data).find('response').find('value').text();							
						}
						else if(type == 'date' || type == 'date_time')
						{
							message_key += '_enum';
							var date_css_class = (type == 'date') ? "form-date-field" : "form-date-time-field";
							
							var html = "<div class=\"multi-value-select date-time-set\">";
							html += "<input type=\"text\" value=\"\" class=\"" + date_css_class + "\" /></div>";
							
							$("div.multi-value-select input").live("change", function()
							{
								var date_value = $.trim($(this).val());
								
								if(date_value)
								{
									var form_action = $("#model-table-form").attr("action");
									form_action = form_action.replace(/multi_value=[^&]+/, "multi_value=" + date_value);
									$("#model-table-form").attr("action", form_action);
								}
							});
						}
						else if(type == 'int' || type == 'float')
						{
							message_key += '_enum';
							multi_value = "";
							
							var html = "<div class=\"multi-value-select numeric-value\">";
							html += "<input type=\"text\" value=\"\" /></div>";
							
							$("div.multi-value-select input").live("change", function()
							{
								var numeric_value = $.trim($(this).val());
								
								if(numeric_value)
								{
									var form_action = $("#model-table-form").attr("action");
									form_action = form_action.replace(/multi_value=[^&]*/, "multi_value=" + numeric_value);
									$("#model-table-form").attr("action", form_action);									
								}
							});
						}
						else if(type == 'enum' || type == 'parent' || type == 'many_to_many' || type == 'group')
						{							
							if(type == 'many_to_many' || type == 'group')
								message_key += '_m2m_' + multi_value;
							else
								message_key += '_enum';
							
							var ids = (type == 'parent') ? checked_elements.join(',') : "";
							
							if($(data).find('response').find('long_list').text())
							{
								var html = "<div class=\"multi-value-select long-list\">";
								html += "<select class=\"long-list-multi-value\">";
								html += "<option value=\"search\">" + mVobject.locale("search_by_name") + "</option>";
								
								if($(data).find('response').find('empty_value').text() == "1")
									html += "<option value=\"reset\">" + mVobject.locale("not_defined") + "</option>";
								
								html += "</select>";
								html += "<input type=\"text\" value=\"\" class=\"autocomplete-multi\" />";
								html += "<input type=\"hidden\" class=\"autocomplete-multi-value\" ";
								html += "value=\"\" name=\"" + multi_action + "\" id=\"" + checked_elements.join(',') + "\" />";
								html += "</div>";
								
								$("select.long-list-multi-value").live("change", function()
								{
									var value = $.trim($(this).val());
									
									if(value == "reset")
										$(this).next().val("").attr("disabled", "disabled").next().val("0");
									else if(value == "search")
										$(this).next().val("").removeAttr("disabled").next().val("");
									else
										$(this).next().val("").next().val("");
								});
							}
							else
							{
								var html = "<div class=\"multi-value-select\"><select>";
								
								$(data).find('response').find('values_list').find('value').each(function()
								{
									if(!multi_value)
										multi_value = $(this).attr("id");
									
									html += "<option value=\"" + $(this).attr("id") + "\">" + $(this).text() + "</option>";
								});
								
								html += "</select></div>";							
							}
							
							$("div.multi-value-select select").live("change", function()
							{
								let value = $(this).val();
								
								if(value == "search")
									return;
								
								value = (value == "reset") ? "0" : value;
								
								let form_action = $("#model-table-form").attr("action");
								form_action = form_action.replace(/multi_value=[^&]*/, "multi_value=" + value);
								$("#model-table-form").attr("action", form_action);
							});
						}
					}
					
					//Sets action to do if OK pressed
					$("#message-confirm-delete #butt-ok").attr("name", "form->#model-table-form");
					
					let css_class = (multi_action == 'delete') ? 'delete' : 'update'; 
					$("#message-confirm-delete div.message").removeClass("update restore delete").addClass(css_class);
					
					let form_action = mVobject.adminPanelPath + "model/?" + mVobject.urlParams;
					form_action +=  "&multi_action=" + multi_action;
					
					if(type == 'many_to_many' || type == 'group')
					{
						form_action += "-m2m-" + multi_value;
						multi_value = 0;
					}
					else if(type == 'enum')
						multi_value = '';
					
					form_action += "&multi_value=" + multi_value;
					$("#model-table-form").attr("action", form_action);
					
					//Adds the text of message
					$("#message-confirm-delete div.message").html(mVobject.locale(message_key, locale_params)).append(html);

					//Displays modal window
					$("#message-confirm-delete").overlay(mVobject.paramsForDialogs).load();

					//Extra js plugins
					runAutocomplete("input.autocomplete-multi");
					$("input.form-date-time-field").datetimepicker({timeFormat: 'hh:mm', dateFormat: mVobject.dateFormat});
					$("input.form-date-field").datepicker({dateFormat: mVobject.dateFormat});
				}
			});
		},
		
		commentImage: function(id)
		{
			let file = id.split("/");
			file = file[file.length - 1];			
			
			let text = $.trim($("#image-comment-text").val().replace(/\*|\n|\t|\r/g, ''));
			let image = $("div.uploaded-images img[src$='" + file + "']");
			
			if(image.size())
				image.attr("title", text).parents("div.uploaded-images").find("div.images-wrapper:first span.left").trigger("click");

			$("#message-confirm-delete input.close").trigger("click");
		}
}

$(document).ready(function()
{    
	$("#message-confirm-delete :button").click(function() //If the button of confirm window was pressed
    {
    	if($(this).attr("id") == "butt-ok") //If OK was pressed
    	{
    		let check_date_value = "#message-confirm-delete div.date-time-set input";
    		let check_numeric_value = "#message-confirm-delete div.numeric-value input";
    		let check_enum_value = "#message-confirm-delete input.autocomplete-multi-value";
    		
    		if(($(check_date_value).size() && !$.trim($(check_date_value).val())) || 
    		   ($(check_numeric_value).size() && $.trim($(check_numeric_value).val()) == "") || 
    		   ($(check_enum_value).size() && $.trim($(check_enum_value).val()) == ""))
    			return;
    		else if($("div.multi-value-select select").size() && !$("div.multi-value-select select").val())
    			return;
				
			let data = $(this).attr("name").split("->"); //Analyzes action to do
			
			if(dialogs.multi_action_total >= 1 && data[0] == 'form')
				$(this).parent().addClass('loader').children().addClass('hidden').off('click');

			dialogs.multi_action_total = 0;

	        if(data[0] == "href") //If we need go go by link
	        	location.href = data[1];
	        else if(data[0] == "form") //If we need to submit the form
	        	$(data[1]).submit();
	        else if(data[0] == "comment" && data[1]) //Multi images comment
        		dialogs.commentImage(data[1]);
	        else if(data[0] == "method" && typeof(window[data[1]]) == "function")
	        	window[data[1]]();
    	}
    });
});