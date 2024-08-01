$(document).ready(function() //Makes name of module check whole current row of checkboxes after click
{	
    $("#rights-table tr:gt(0)").each(function()
	{
		$(this).find("td:first").click(function()
	    {
			 var all_boxes = $(this).parent().find(":checkbox");
				 
			 var boxes_total = $(this).parent().find(":checkbox").size();
			 var boxes_checked = $(this).parent().find(":checkbox:checked").size();
				 
			 if(boxes_total > boxes_checked) //If all checked we remove check
				 all_boxes.attr("checked", true);
			 else //If not all are checked we check all at one time
				 all_boxes.removeAttr("checked");
		});
	});
	
    var count = 1;
    
	$("#rights-table tr:first th:gt(0)")  //Makes name of action check whole current column of checkboxes after click
		.each(function()
		  {
			this.ind = count ++; //The number of column
			
			$(this).click(function()
				  {
					  var rows = $("#rights-table tr:gt(0)"); //Rows of modules with checkboxes
					  var position = this.ind; //Current column (action)
					  var boxes_total = 0;
					  var boxes_checked = 0;
					  
					  rows.each(function() //Counts checked checkboxes in the row
					  {
						  boxes_total += $(this).find("td:eq(" + position + "):has(:checkbox)").size();
						  boxes_checked += $(this).find("td:eq(" + position + "):has(:checkbox:checked)").size();
					  });
					  
					  rows.each(function() //If not all checked we check all, othewise we uncheck all
					  {
						  var box = $(this).find("td:eq(" + position + ") :checkbox");
						 
						  if(boxes_total > boxes_checked)
							  box.attr("checked", true);
						  else 
							  box.removeAttr("checked");
				      });
					 
				  });
		  });
});