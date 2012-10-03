<div data-role="page" id="Search-list-none">
  {include file="header.tpl"}
  <div data-role="content">
{*DM - pridany navrhy hledanych vyrazu*}
    <p>{translate text='nohit_prefix'} - <strong>{$lookfor|escape}</strong> - {translate text='nohit_suffix'} 
{if !empty($spellingSuggestions)}
Did you mean: 
{foreach from=$spellingSuggestions item=details key=term name=termLoop}
	{foreach from=$details.suggestions item=data key=word name=suggestLoop}
		<a href="{$data.replace_url|escape}">{$word|escape}</a>
		{if !$smarty.foreach.suggestLoop.last}, {/if}
	{/foreach}
{/foreach}
?
{/if}
<br><br></p>
{*DM - pridan panel s vyhledavacim polem, a tlacitkem na radit podle*}
<div class="searchandsortnone">
<form method="get" action="{$path}/Search/Results" data-ajax="false">
<table class="searchontop">
<tr>
<td class="count">
	{if $recordCount}
	<strong>{$recordStart}</strong> - <strong>{$recordEnd}</strong>
	      {translate text='of'} <strong>{$recordCount}</strong>
	      {if $searchType == 'basic'}{translate text='for'}:
	<strong>{$lookfor|escape:"html"}</strong>{/if}
	{/if}
</td>
<td class="search">
	<input type="search" placeholder="{translate text='Search'}" name="lookfor" id="searchForm_lookfor"/>
</td>
<td class="btn">
	<input type="submit" name="submit" value="{translate text="Find"}"/>
</td>
</tr>
</table>
</form>
</div>
{*DM*}
  </div>
  {include file="footer.tpl"}
</div>
