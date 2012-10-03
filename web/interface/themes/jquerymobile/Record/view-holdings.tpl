{*include file=$holdingsMetadata*}
{*DM - nove zobrazeni jednotek v mobilni verzi*}
{foreach from=$holdings item=holding key=location}
<table class="holdings" colspec="L8 C20 R50">
<tr><th>{translate text='due date'}</th><th>{translate text='signature 2'}</th><th>{translate text='collection'}</th></tr>


  {foreach from=$holding item=row}
    {if $row.barcode != ""}
    {/if}

	<tr><td>{if empty($row.duedate)}{translate text='Available'}{/if}{$row.duedate|escape}</td><td>{$row.sig2|escape}</td><td>{$row.collection_desc|escape}</td>

	{if !empty($row.duedate)}
		<td>
<a href="{$url}/Record/{$id|escape:'url'}/ExtendedHold?barcode={$row.item_id|escape}" target="_self">

	{*onClick="getLightbox('Record', 'ExtendedHold', '{$id|escape}', '{$row.item_id|escape}', '{translate text='Place a Hold'}'); return false;"*} 
            
	{translate text="Place a Hold"}

</a>
		</td>
	{/if}


	</tr>



  {/foreach}




</table>
{/foreach}



