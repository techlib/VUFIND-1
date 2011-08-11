{include file=$holdingsMetadata}
{foreach from=$holdings item=holding key=location}

{*
// Zbytečný JavaScript od MZK zakomentován (JM)
  {js filename="yui/container-min.js"}
  {js filename="mzk.js"}


<script type="text/javascript">
   translate_signature1 = "{translate text='signature1'|escape:'quotes'}";
   translate_signature2 = "{translate text='signature2'|escape:'quotes'}";
   translate_information_about_item = "{translate text='Information about item'|escape:'quotes'}";
   translate_barcode = "{translate text='barcode'|escape:'quotes'}";
   translate_no_of_loans = "{translate text='no of loans'|escape:'quotes'}";
   translate_note = "{translate text='note'|escape:'quotes'}";
</script>
*}

<h3>{translate text=$location}</h3>
<table cellpadding="2" cellspacing="0" border="0" class="citation" summary="{translate text='Holdings details from'} {translate text=$location}">
  <tr>
    <td>{translate text='item status'}</td>
    <td>{translate text='due date'}</td>
    <td>{translate text='sublibrary'}</td>
    <td>{translate text='collection'}</td>
    <td>{translate text='signature 2'}</td>
    <td></td>
  </tr>
  {foreach from=$holding item=row}
    {if $row.barcode != ""}
  <tr>
    <td>
      {if $row.availability} <!-- == "Y" -->
         <span class="available">{$row.status}</span>
      {else}
         <span class="checkedout">{$row.status|escape}</span>
      {/if}
      {if $row.reserve}
         <a href="{$url}/Record/{$id|escape:'url'}/ExtendedHold?barcode={$row.group|escape:'url'}"
            onClick="getLightbox('Record', 'ExtendedHold', '{$id|escape}', '{$row.group|escape}', '{translate text='PutHold'}'); return false;" >
            {translate text="Place a Hold"}
         </a>
         <!-- <a href="{$url}/Record/{$id|escape:'url'}/ExtendedHold">
            {translate text="Place a Hold"}
         </a> -->
      {/if}
    </td>
    <td>
        {$row.duedate|escape}
    </td>
    <td>
        {$row.sub_lib_desc|escape}
    </td>
    <td>
        {$row.collection_desc|escape}
    </td>
    {*
    <td>
        {$row.sig1|escape}
    </td>
    *}
    <td>
        {$row.sig2|escape}
    </td>
    <td>
        {$row.description|escape}
    </td>
  </tr>
    {/if}
  {/foreach}
</table>
{/foreach}

{*
<link rel="stylesheet" type="text/css" media="print" href="/interface/themes/ntk/css/calendar.css" />
{js filename="calendar-min.js"}
{js filename="calendar.js"}
*}
