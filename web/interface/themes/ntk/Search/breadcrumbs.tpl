{if $searchId}
<em>{translate text="Search"}: {$lookfor|escape:"html"}</em>
{elseif $pageTemplate=="newitem.tpl" || $pageTemplate=="newitem-list.tpl"}
<em>{translate text="New Items"}</em>
{elseif $pageTemplate=="view-alt.tpl"}
<em>{translate text=$subTemplate|replace:'.tpl':''}</em>
{elseif $pageTemplate!=""}
<em>{translate text=$pageTemplate|replace:'.tpl':''|capitalize}</em>
{/if}