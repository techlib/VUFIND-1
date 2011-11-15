//
// NTK.js - JavaScript for National Technical Library's VuFind
//

var NTK = {
        options : {},
	init : (function() {
		YAHOO.util.Event.onDOMReady(function() {
        		var searchBox = document.getElementById("lookfor");
	        	if (searchBox) {
                		searchBox.focus();
        		}
		});
	})(),
	translate : (function() {
		var dict = {
			"View Records" : {
				"cs" : "Zobrazit záznamy"
			}
		};
		return function(text) {
			if (text in dict && NTK.options.lang in dict[text]) {
				return dict[text][NTK.options.lang];
			}
			else {
				return text;
			}
		};
	})()
};
