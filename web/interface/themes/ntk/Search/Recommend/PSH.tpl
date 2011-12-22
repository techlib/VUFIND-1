<div class="yui-g authorbox">
<p><strong>{translate text="Amend subject search"}:</strong></p> 
<table class="facetsTop navmenu narrow_begin psh"> 
  {if !empty($broader)}
    <tr class="broader">
      <th colspan="{$topFacetSettings.cols}">{translate text="More general search"}:</th>
    </tr>
    {foreach from=$broader item=heading name="innerLoop"}
      {if $smarty.foreach.innerLoop.iteration % $topFacetSettings.cols == 1}
      <tr>
      {/if}
      <td><a href="/Search/Results?type=psh_facet&lookfor=%22{$heading|escape}%22">{$heading}</a></td>
      {if $smarty.foreach.innerLoop.iteration % $topFacetSettings.cols == 0 || $smarty.foreach.innerLoop.last}
      </tr>
      {/if}
    {/foreach}
  {/if}
  {if !empty($narrower)}
    <tr class="narrower">
      <th colspan="{$topFacetSettings.cols}">{translate text="More specific search"}:</th>
    </tr>
    {foreach from=$narrower item=heading name="innerLoop"}
      {if $smarty.foreach.innerLoop.iteration % $topFacetSettings.cols == 1}
      <tr>
      {/if}
      <td><a href="/Search/Results?type=psh_facet&lookfor=%22{$heading|escape}%22">{$heading}</a></td>
      {if $smarty.foreach.innerLoop.iteration % $topFacetSettings.cols == 0 || $smarty.foreach.innerLoop.last}
      </tr>
      {/if}
    {/foreach}
  {/if}
  {if !empty($see_also)}
    <tr class="related">
      <th colspan="{$topFacetSettings.cols}">{translate text="Related searches"}:</th>
    </tr>
    {foreach from=$see_also item=heading name="innerLoop"}
      {if $smarty.foreach.innerLoop.iteration % $topFacetSettings.cols == 1}
      <tr>
      {/if}
      <td><a href="/Search/Results?type=psh_facet&lookfor=%22{$heading|escape}%22">{$heading}</a></td>
      {if $smarty.foreach.innerLoop.iteration % $topFacetSettings.cols == 0 || $smarty.foreach.innerLoop.last}
      </tr>
      {/if}
    {/foreach}
  {/if}
</table>
</div>
