<div class="footer-text"><a href="#" class="standard-view" rel="external">{translate text="Go to Standard View"}</a></div>

<div data-role="footer" data-theme="b">
  {* if a module has footer-navbar.tpl, then use it, otherwise use default *}
  {assign var=footer_navbar value="$module/footer-navbar.tpl"|template_full_path}
  {if !empty($footer_navbar)}
    {* include module specific navbar *}
    {include file=$footer_navbar}
  {else}
    <div data-role="navbar">
      <ul>
        {* default to Language, Account and Logout buttons *}
        <li><a data-rel="dialog" href="#Language-dialog" data-transition="pop">{translate text="Language"}</a></li>
        <li><a rel="external" href="{$path}/MyResearch/Home">{translate text="Account"}</a></li>
        {if $user}
          <li><a rel="external" href="{$path}/MyResearch/Logout">{translate text="Logout"}</a></li>          
        {/if}
      </ul>
    </div>
  {/if}
</div>

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
