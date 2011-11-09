var translate = (function() {
	var dict = {
		"View Records" : {
			"cs" : "Zobrazit záznamy"
		}
	};
	return function(text) {
		if (text in dict && OPTIONS.lang in dict[text]) {
			return dict[text][OPTIONS.lang];
		}
		else {
			return text;
		}
	};
})();
