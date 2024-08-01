
mVobject.fileManagerAjaxPath = mVobject.adminPanelPath + "ajax/filemanager.php";

var fileManager = 
{	
	showFile: function(file)
	{
		window.open(file);
	},
	
	//Puts the new value into buffer on the server side
	addToBuffer: function(type, value)
	{
		 $.ajax({ //Ajax adds to buffer on the server required data to use it later (paste)
				type: "POST",
				url: mVobject.fileManagerAjaxPath,
				data: 'type=' + type + '&value=' + value,
				success: function(data)
				{
					if(!data)
						location.reload();
					else if(data == "no-rights")
						$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});
				} 
			});		
	},
	
	//Takes the value from buffer
	pasteFromBuffer: function(href)
	{
		location.href = href;
	},
	
	//Action to rename file or directory
	renameFileOrFolder: function(file_name, type)
	{
		dialogs.showRenameMessage(mVobject.locale("rename_" + type, {name: file_name}), "#rename-form", file_name);
		
		var current_page = location.href.match(/page=\d+/) ? "&" + location.href.replace(/.*(page=\d+).*/, "$1") : "";
		var token = "&token=" + $("input[name='admin-panel-csrf-token']").val();
		
		var form = "<form id=\"rename-form\" action=\"?action=rename" + current_page + token + "\" method=\"post\">";
		form += "<input type=\"text\" id=\"file-rename\" name=\"new-name\" value=\"" + file_name + "\" />";
		form += "<input type=\"hidden\" name=\"old-name\" value=\"" + file_name + "\" /></form>";
		
		$("#message-confirm-delete div.message").append(form);
	}
};

$(document).ready(function()
{	
	$("#filemanager-form a[rel='file']").click(function() //Change of image in right DIV
    {
		$("#file-image").empty().addClass("loader"); //Removes file parameters
		  
		 $.ajax({ //Ajax gets from server required image to show it in file manager
					type: "POST",
					dataType: "html",
					url: mVobject.fileManagerAjaxPath,
					data: "show-image=" + $(this).attr("name"),
					success: function(data) //Updates the DIV with image and TRs with parameters
					{
						if(!data)
							location.reload();
						else
							$("#file-params-table").empty().append(data);
					} 
				});
	});
									 
	//Context menu activation
	$("#files-table a").contextMenu(
		{
			menu: 'filemanager-menu'
		},
													   
		function(action, element, position)  //Function to implement the action
		{
			//Tries to find action in hidden fields, related to current element
			var href = element.parent().next().find(":hidden[id^='" + action + "']").val();
			
			$("#files-table a").removeClass("cut-element"); //Makes regular color back to all elements
			
			if(action == "cut") //If we cut the element we mark it with different color
				element.addClass("cut-element");
			
			if(typeof(href)!="undefined") //Makes an action if it was found
				location.href = href;
		});
});