<div class="span-18{if $sidebarOnLeft} push-5 last{/if}">
  {if $user->cat_username}
    <h3>{translate text='Your Fines'}</h3>
    {$finesData}
  {else}
    {include file="MyResearch/catalog-login.tpl"}
  {/if}
</div>
<div class="span-5 {if $sidebarOnLeft}pull-18 sidebarOnLeft{else}last{/if}">
  {include file="MyResearch/menu.tpl"}
</div>
<div class="clear"></div>