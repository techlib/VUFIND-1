{* Your footer *}
<div><p><strong>{translate text='Search Options'}</strong></p>
  <ul>
    <li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
    <li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
  </ul>
</div>
<div><p><strong>{translate text='Find More'}</strong></p>
  <ul>
    <li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li>
    <li><a href="{$path}/AlphaBrowse/Home">{translate text='Browse Alphabetically'}</a></li>
    {*
    Zakomentováno, dokud nebude implementováno. (JM)
    <li><a href="{$path}/Search/Reserves">{translate text='Course Reserves'}</a></li>
    <li><a href="{$path}/Search/NewItem">{translate text='New Items'}</a></li>
    *}
  </ul>
</div>
<div><p><strong>{translate text='Need Help?'}</strong></p>
  <ul>
    <li><a href="{$url}/Help/Home?topic=search" onClick="window.open('{$url}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">{translate text='Search Tips'}</a></li>
    <li><a href="{$url}/Help/Home?topic=about" onClick="window.open('{$url}/Help/Home?topic=about', 'About', 'width=625, height=510'); return false;">{translate text="About VuFind"}</a></li>
    <li><a href="http://www.ptejteseknihovny.cz/">{translate text='Ask a Librarian'}</a></li>
    {* <li><a href="#">{translate text='FAQs'}</a></li> *}
  </ul>
</div>
<div><p><strong>{translate text='In collaboration with'}</strong></p>
  <ul>
    <li><a href="http://www.obalkyknih.cz/">Obálky knih</a></li>
  </ul>
</div>
<br clear="all">
{* Comply with Serials Solutions terms of service -- this is intentionally left untranslated. *}
{if $module == "Summon"}Powered by Summon™ from Serials Solutions, a division of ProQuest.{/if}

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-2503184-12']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
