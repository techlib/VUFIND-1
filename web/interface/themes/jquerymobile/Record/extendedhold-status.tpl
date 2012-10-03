<p>{if $error}{translate text="Hold request has failed. The reason is"}:&nbsp;{$error_str|translate}{else}{translate text="hold_success"}{/if}</p>
{*DM - pridan panel s vyhledavacim polem, a tlacitkem na radit podle*}
<div class="searchandsortnone">
<form method="get" action="{$path}/Search/Results" data-ajax="false">
<table class="searchontop">
<tr>
<td class="search">
	<input type="search" placeholder="{translate text='Search'}" name="lookfor" id="searchForm_lookfor" value="{$lookfor|escape}"/>
</td>
<td class="btn">
	<input type="submit" name="submit" value="{translate text="Find"}"/>
</td>
</tr>
</table>
</form>
</div>
{*DM*}

