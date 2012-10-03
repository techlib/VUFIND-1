<div data-role="header" data-theme="b">
{*DM - uprava horni listy - zruseno tlacitko v pravem rohu a odkaz pridan do nadpisu + iknoy*}
<div class="homeicon"> 
<h1><a id="home" rel="external" href="{$path}/Search/Home"><img class="bottom" width="20" height="20" src="{$path}/interface/themes/ntkmobile/css/blue-home-icon.png"><span style="vertical-align:bottom"> {$pageTitle|trim:':/'|translate|escape} </span><img class="bottom" width="20" height="20" src="{$path}/interface/themes/ntkmobile/css/blue-home-icon.png"></a></h1>
</div>

  {* display the search button everywhere except /Search/Home 
  {if !($module == 'Search' && $pageTemplate == 'home.tpl') }
    <a rel="external" href="{$path}/Search/Home" data-icon="search"  class="ui-btn-right">
    {translate text="Home"}
    </a>
  {/if}
  *}
  {* if a module has header-navbar.tpl, then use it *}
  {assign var=header_navbar value="$module/header-navbar.tpl"|template_full_path}
  {if !empty($header_navbar)}
    {include file=$header_navbar}
  {/if}
</div>
