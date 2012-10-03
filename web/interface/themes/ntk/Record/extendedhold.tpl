<link rel="stylesheet" type="text/css" href="/interface/themes/aleph/css/calendar.css"/>
{if ( $order > 1)}
    <div class="error">{translate text="Item is requested. Your request sequence for this item is:"} {$order|escape}</div>
{/if}
<div class="yui-skin-sam">
<form method="post" action="{$url}{$formTargetPath|escape}" name="popupForm" id="puthold"
      onSubmit='PutHold(&quot;{$id|escape}&quot;, this.elements[&quot;to&quot;].value,
                this.elements[&quot;comment&quot;].value, this.elements[&quot;item&quot;].value,
                this.elements[&quot;location&quot;][this.elements[&quot;location&quot;].selectedIndex].value,
                {* Pass translated strings to Javascript -- ugly but necessary: *}
                {literal}{{/literal}sending: &quot;{translate text='sms_sending'}&quot;, 
                 success: &quot;{translate text='hold_success'}&quot;,
                 failure: &quot;{translate text='hold_failure'}&quot;{literal}}{/literal}
                ); return false;'>
  <input type="hidden" name="item" value="{$item|escape}">
  <table>
  <tr>
    <td>{translate text="Delivery location"}: </td>
    <td>
      <select name="location">
        {foreach from=$locations key=val item=details}
        <option value="{$val}">{$details|escape}</option>
        {/foreach}
      </select>
    </td>
  </tr>
  <tr>
    <td>{translate text="Last interest date"}: </td>
    <td>
      <!-- <input type="text" name="to" value="{$last_interest_date|escape}" id="calendar" autocomplete="off"> -->
      <input type="text" name="to" value="{$last_interest_date|escape}" id="calendar" autocomplete="off">
    </td>
  </tr>
  <tr>
    <td>{translate text="Comment"}: </td>
    <td>
      <input type="text" name="comment">
    </td>
  </tr>
  <tr>
    <td></td>
    <td><input type="submit" name="submit" value='{translate text="PutHold"}'></td>
  </tr>
  </table>
  <div id="cal1Container">
    <!-- <script src="/interface/themes/aleph/js/calendar.js"/> -->
    <img src="/interface/themes/aleph/images/transparent.gif" onload="(function() {ldelim} var e = document.createElement('script'); e.setAttribute('src', '/interface/themes/ntk/js/stub.js'); document.getElementsByTagName('head')[0].appendChild(e); {rdelim} )();"/>
  </div>
</form>
</div>
