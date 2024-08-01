/**
 * Simple jQuery modal window.
 * Examples:
 * 
 * $.modalWindow.open(message);
 * $.modalWindow.open(message, {url: link});
 * $.modalWindow.open(message, {form: $("#my-form")});
 * $.modalWindow.open(message, {event: function() { ... } });
 * $.modalWindow.open(message, {event: action, css_class: "my-class", extra_html: "<p>text...</p>"});
 * $.modalWindow.close();
 */

$(document).ready(function()
{
	$.modalWindow = {
		
	cancel_text: mVobject.locale("cancel"),
	
	extra_html: "",
	
	form: false,
	
	url: false,
	
	event: false,
	
	open: function(message, params = {})
	{
		var _this_ = this;
				
		if(typeof(params.cancel_text) != "undefined")
			this.cancel_text = params.cancel_text;
		
		if(typeof(params.extra_html) != "undefined")
			this.extra_html = params.extra_html;
		
		if(typeof(params.form) != "undefined")
			this.form = params.form;
		else if(typeof(params.url) != "undefined")
			this.url = params.url;
		else if(typeof(params.event) != "undefined")
			this.event = params.event;
		
		this.createHtml(message);
		
		if(typeof(params.css_class) != "undefined")
			$("#custom-modal-window").addClass(params.css_class);
		
		if(!this.form && !this.url && !this.event)
		{
			$("#modal-button-cancel").remove();
			
			$("#modal-button-ok").on("click", function()
			{
				_this_.close();
			});
		}
		else
		{
			$("#modal-button-ok").on("click", function()
			{
				if(_this_.form)
					$(_this_.form).submit();
				else if(_this_.url)
					location.href = _this_.url;
				else if(_this_.event)
					_this_.event();
			});
		}
		
		$("#custom-modal-window span.close, #modal-button-cancel").on("click", function()
		{
			_this_.close();
		});
		
		$("#overlay, #custom-modal-window").fadeIn(300);
	},
	
	createHtml: function(message)
	{
		var html = "";
		
		if(!$("#overlay").length)
			html += "<div id=\"overlay\"></div>";
		
		html += "<div id=\"custom-modal-window\"><span class=\"close\"></span>";
		html += "<div class=\"message\">" + message + "</div><div class=\"extra\">" + this.extra_html + "</div>";
		html += "<div class=\"buttons\"><input id=\"modal-button-ok\" type=\"button\" value=\"OK\" class=\"modal-button\" />";
		html += "<input id=\"modal-button-cancel\" type=\"button\" value=\"" + this.cancel_text + "\" class=\"modal-button cancel\" />";
		html += "</div></div>";
		
		$("body").append(html);
		$("#overlay, #custom-modal-window").hide();
	},
	
	close: function()
	{
		$("#overlay, #custom-modal-window").fadeOut(300, function()
		{
			$("#custom-modal-window").remove();
		});
		
		this.form = this.url = this.event = false;
		this.extra_html = "";
	}};
});