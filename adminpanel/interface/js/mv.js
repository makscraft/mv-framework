/**
 * JS object with settings and custom functions for MV framework
 */
var mVobject = {

	//Root path of the project
	mainPath: "",	
	
	//Root path of administrator panel for urls
	adminPanelPath: "",
		
	//Parameters to add to urls
	urlParams: "",
	
	//Name of sorting field
	sortField: "",
	
	//Filter of parent type field when ordering the child model rows
	relatedParentFilter: "",
	
	//Order fields which depend on other fileds of model
	dependedOrderFields: [],
	
	//When ordering the record in table its the first order number
	startOrder: "",
	
	//Name of parent filter
	allParentsFilter: false,
	
	//Name of region for i18n
	region: "",
	
	//Array of local words and settings 
	localePackage: {},
	
	//Keys for translit, initial letters
	translitKeys: [],
	
	//Values for translit, to replaae the keys
	translitValues: [],	
	
	//Parameters for overlay with alert / confirm message 
	paramsForDialogs: {mask: {color: "#000", loadSpeed: 200, opacity: 0.4}, top: "20%", closeOnClick: false, load: true},
	
	locale: function(key) //Gets the string according to current language
	{
		if(this.localePackage[key]) //Is loader in header
		{
			var string = this.localePackage[key].replace("\n", "<br />");
			string = string.replace(/'([^'\s]+)'/g, "&laquo;$1&raquo;");
			
			if(typeof(arguments[1]) == "object")
				for(value in arguments[1])
					string = string.replace("{" + value + "}", arguments[1][value]);
			
			return string;
		}
		else
			return "{" + key + "_" + this.region + "}"; //To see if we don't have needed message translated
	}
};