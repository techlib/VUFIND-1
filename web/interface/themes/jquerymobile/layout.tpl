<!DOCTYPE html> 
<html> 
  <head>
    <meta charset="utf-8"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="viewport" content="width=device-width, minimum-scale=1, maximum-scale=1"/> 
    <title>{$site.title|escape}</title>

    {* Set global javascript variables *}
    <script type="text/javascript">
    //<![CDATA[
      var path = '{$path}';
    //]]>
    </script>

    <link rel="stylesheet" href="http://code.jquery.com/mobile/1.0a4/jquery.mobile-1.0a4.min.css" />
    <script type="text/javascript" src="http://code.jquery.com/jquery-1.5.1.min.js"></script>
    {js filename="common.js"}
    <script type="text/javascript" src="http://code.jquery.com/mobile/1.0a4/jquery.mobile-1.0a4.min.js"></script>
    {js filename="jquery.cookie.js"}
    {js filename="cart_cookie.js"}
    {js filename="cart.js"}    
    {css filename="styles.css"}
    {css filename="formats.css"}
  </head> 
  <body>
    {include file="$module/$pageTemplate"}   
    <div data-role="dialog" id="Language-dialog">
      <div data-role="header" data-theme="d" data-position="inline">
        <h1>{translate text="Language"}</h1>
      </div>
      <div data-role="content">
        {if is_array($allLangs) && count($allLangs) > 1}
        <form method="post" name="langForm" action="#" id="langForm" data-ajax="false">
          <div data-role="fieldcontain">
            <label for="langForm_mylang">{translate text="Language"}:</label>
            <select id="langForm_mylang" name="mylang">
              {foreach from=$allLangs key=langCode item=langName}
                <option value="{$langCode}"{if $userLang == $langCode} selected="selected"{/if}>{translate text=$langName}</option>
              {/foreach}
            </select>
            <input type="submit" value="{translate text='Set'}" />
          </div>
        </form>
        {/if}
      </div>
    </div>
  </body>
</html>
