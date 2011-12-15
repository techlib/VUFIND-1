//
// NTK.js - JavaScript for National Technical Library's VuFind
//

var NTK = {
  options : {
		"isbn" : false,
		"lang" : false,
	},
  psh : {
    callbackDescription : function(data) {
      var container = YAHOO.util.Dom.getElementsByClassName("authorbox");
      container = (container && !!container.length) ? container[0] : false;
      if (container) {
        // Vytvořit DOM
        var fragment = document.createElement("table"),
          thead = document.createElement("thead"),
          tbody = document.createElement("tbody"),
          theadRow = document.createElement("tr");

        thead.appendChild(theadRow);
        fragment.appendChild(thead);
        fragment.appendChild(tbody);
        fragment.setAttribute("class", "facetsTop navmenu narrow_begin");

        // Nadřazené heslo

        // Příbuzná hesla

        // Podřazená hesla
        container.appendChild(fragment);
      }
    },
    callbackID : function(data) {
      // Získat z dat ID hesla
      var conceptID = false;
      if (conceptID) {
        // Získat informace o heslu PSH
        var url = "http://data.ntkcz.cz/prohlizeni_psh/getjson?"
          + "subject_id="
          + conceptID
          + "&lang="
          + NTK.options.lang
          + "&callback="
          + "NTK.psh.callbackDescription";
        NTK.util.createScript(url);
      }
    },
    getDOMFragment : function(heading, labels) {
      var fragment = document.createElement("table"),
          thead = document.createElement("thead"),
          tbody = document.createElement("tbody"),
          theadRow = document.createElement("tr");

        thead.appendChild(theadRow);
        fragment.appendChild(thead);
        fragment.appendChild(tbody);
        fragment.setAttribute("class", "facetsTop navmenu narrow_begin");
      return fragment;
    },
    getSearchUrl : function(conceptLabel) {
      var url = "/vufind/Search/Results?"
        + "lookfor="
        + encodeURIComponent(conceptLabel)
        + "&type=Subject";
      return url;
    },
    init : function() {
      // Rozhodnout, zdali bylo zadáno heslo PSH
        // Můžeme to filtrovat jenom na vyhledávání, kdy je zvolena faceta "PSH" (resp. "Předmět")
        // Zdali uživatel zadal PSH, zjistíme až z pokusného vyhledání ID hesla
      var searchType = NTK.util.getURLParam("type");
      if (searchType && (searchType === "Subject")) {
        // Získat heslo PSH ze stránky
        var conceptLabel = NTK.util.getURLParam("lookfor"),
          urlTest = (href.indexOf("Search/Results") != -1) ? true : false;
        if (conceptLabel && urlTest) {
          // Získat ID hesla
          var url = "http://data.ntkcz.cz/prohlizeni_psh/get_subject_id?"
            + "input="
            + encodeURIComponent(conceptLabel)
            + "&lang="
            + NTK.options.lang
            + "&callback="
            + "NTK.psh.callbackID";
          NTK.util.createScript(url);
        }
      }
    }
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
    },
    getURLParam : function(param) {
      var href = window.location.href,
        value = href.match(new RegExp(param + "=([^&]+)"));
      value = (value && value[1]) ? value[1] : false;
      return value;
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
