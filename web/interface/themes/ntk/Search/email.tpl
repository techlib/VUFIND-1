<div align="left">
  {if $message}<div class="error">{$message|translate}</div>{/if}

  <form action="{$url}/Search/Email" method="post" onSubmit='SendURLEmail(this.elements[&quot;to&quot;].value, 
    this.elements[&quot;from&quot;].value, this.elements[&quot;message&quot;].value,
    {* Pass translated strings to Javascript -- ugly but necessary: *}
    {literal}{{/literal}sending: &quot;{translate text='email_sending'}&quot;, 
     success: &quot;{translate text='email_success'}&quot;,
     failure: &quot;{translate text='email_failure'}&quot;{literal}}{/literal}
    ); return false;'>
    <input type="hidden" name="url" value="{$searchURL|escape:"html"}">
    <p>
      <strong>{translate text='To'}:</strong>
    </p>
    <p>
      <input type="text" name="to" size="40" />
    </p>
    <p>
      <strong>{translate text='From'}:</strong>
    </p>
    <p>
      <input type="text" name="from" size="40" />
    </p>
    <p>
      <strong>{translate text='Message'}:</strong>
    </p>
    <p>
      <textarea name="message" rows="3" cols="40"></textarea>
    </p>
    <p>
      <input type="submit" name="submit" value="{translate text='Send'}" />
    </p>
  </form>
</div>
