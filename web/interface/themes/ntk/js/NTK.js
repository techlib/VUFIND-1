//
// NTK.js - JavaScript for National Technical Library's VuFind
//

var NTK = {
  options : {
		"isbn" : false,
		"lang" : false,
	},
	toc : {
		callback : function(data) {
      if (data && data.length && ("toc_pdf_url" in data[0]) && ("toc_thumbnail_url" in data[0])) {
        data = data[0];
        
        var heading = document.createElement("h5"),
            strong = document.createElement("strong"),
            headingText = document.createTextNode(NTK.translate("Table of Contents"));
        strong.appendChild(headingText);
        heading.appendChild(strong);

        var img = document.createElement("img")
        img.src = data["toc_thumbnail_url"];
        img.alt = NTK.translate("TOC thumbnail");

        var link = document.createElement("a");
        link.href = data["toc_pdf_url"];
        link.title = NTK.translate("Table of Contents") + " (PDF)";
        link.appendChild(img);

			  var container = document.getElementById("ntk_toc");
        container.appendChild(heading);
        container.appendChild(link);
      }
		},
		init : function() {
			var baseUrl = "http://www.obalkyknih.cz/api/books?books=",
				permalink = "http://vufind.techlib.cz/vufind/Search/Results?lookfor="
					+ NTK.options.isbn				
					+ "&type=ISN&jumpto=1",
				params = {
					"bibinfo" : [{
						"isbn" : NTK.options.isbn
					}],
					"permalink" : permalink
				};
			var url = baseUrl + encodeURIComponent(YAHOO.lang.JSON.stringify(params));
      NTK.util.createScript(url);
		}
	},
	translate : (function() {
		var dict = {
      "Table of Contents" : {
        "cs" : "Obsah dokumentu"
      },
      "TOC thumbnail" : {
        "cs" : "Náhled obsahu"
      },
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
	})(),
	util : {
		autoFocus : function() {
			var searchBox = document.getElementById("lookfor");
			if (searchBox) {
				searchBox.focus();
			}
		},
    createScript : function (scriptUrl, encode) {
      // encode = {true; false}, určuje, zdali se má scriptUrl kódovat
      var newScript = document.createElement("script");
      newScript.src = encode ? encodeURI(scriptUrl) : scriptUrl;
      newScript.type = "text/javascript";
      newScript.charset = "utf-8";
      document.getElementsByTagName("head")[0].appendChild(newScript);
    }
	}
};

var obalky = {
  callback : function(data) {
    NTK.toc.callback(data);
  }
}

YAHOO.util.Event.onDOMReady(function() {
  NTK.util.autoFocus();
});
