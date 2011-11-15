<script>
{literal}
YAHOO.example.Timing = function() {

	//create shortcut for YAHOO.util.Event:
	var Event = YAHOO.util.Event;

	//the returned object here will be assigned
	//to YAHOO.example.Timing and its members will
	//then be publicly available:
	return {

		//we'll use this handler for onAvailable, onContentReady, //and onDOMReady:
		fnHandler: function(message) {

                        if(arguments.length > 2) { 
  	                  openurl = arguments[2];
                        }
                          getResolverLinks(message, "0", "");
			//onDOMReady uses the Custom Event signature, with the object
			//passed in as the third argument:

			
		},

		init: function() {
			
			//assign onContentReady handler:
			Event.onAvailable("openUrlEmbed0", this.fnHandler, "{/literal}{$openUrlBase|escape}?{$openURL|escape}{literal}");

		}

	}

}();
//initialize the example:
YAHOO.example.Timing.init();
{/literal}
</script>

<h3>{translate text=SFX}</h3>
{*  tady se pusti javascript na spravne openurl: <a href={$openUrlBase|escape}?{$openURL|escape}>SFX NTK</a> *}
<span id="openUrlEmbed0"> </span>
<span id="openUrlLink0" />

