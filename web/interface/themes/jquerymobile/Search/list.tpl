<div data-role="page" id="Search-list" class="results-page">
  {include file="header.tpl"}
  <div data-role="content">
    {*if $recordCount}
    <p>
      <strong>{$recordStart}</strong> - <strong>{$recordEnd}</strong>
      {translate text='of'} <strong>{$recordCount}</strong>
      {if $searchType == 'basic'}{translate text='for'}: <strong>{$lookfor|escape:"html"}</strong>{/if}
    </p>
    {/if*}
{*DM - pridan panel s vyhledavacim polem, a tlacitkem na radit podle*}
<div class="searchandsort">
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
	<input type="search" placeholder="{translate text='Searching in all fields'}" name="lookfor" id="searchForm_lookfor"/>
</td>
<td class="btn">
	<input type="submit" name="submit" value="{translate text="Find"}"/>
</td>
<td class="sort">
	{translate text='Sort'}:
</td>
<td class="select">
	<select name="sort" onChange="document.location.href = this.options[this.selectedIndex].value;">
		{foreach from=$sortList item=sortData key=sortLabel}
		<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected{/if}>{translate text=$sortData.desc}</option>
		{/foreach}
	</select>
	<noscript><input type="submit" value="{translate text="Set"}" /></noscript>
</td>
</tr>
</table>
</form>
</div>
{*DM*}

    {if $subpage}
      {include file=$subpage}
    {else}
      {$pageContent}
    {/if}
  </div>
  {include file="footer.tpl"}
</div>

{include file="Search/Recommend/SideFacets.tpl"}
