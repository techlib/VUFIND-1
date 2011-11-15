<div>
  {if !empty($electronic)}
    <div class="openurls">
      <strong>{translate text="Electronic"}</strong>
      <ul>
        {foreach from=$electronic item=link}
          <li>
            {if $link.href}
              <img src="http://sfx.techlib.cz/sfxlcl41/img/misc/services-19/getFullTxt.png" style="height:15px"/>
              <a href="{$link.href|escape}" title="{$link.service_type|escape}">{$link.title|replace:"/sfxlcl41/":"http://sfx.techlib.cz/sfxlcl41/"}</a> {$link.coverage|escape}

            {else}
              {$link.title|escape} {$link.coverage|escape}
            {/if}
          </li>
        {/foreach}
      </ul>
    </div>
  {/if}
  {if !empty($print)}
    <div class="openurls">
      <strong>{translate text="Holdings"}</strong>
      <ul>
        {foreach from=$print item=link}
          <li>
             <img src="http://sfx.techlib.cz/sfxlcl41/img/misc/services-19/getSearch.png" style="height:15px"/>
            {if $link.href}
              <a href="{$link.href|escape}" title="{$link.service_type|escape}">{$link.title|replace:"/sfxlcl41/":"http://sfx.techlib.cz/sfxlcl41/"}</a> {$link.coverage|escape}
            {else}
              {$link.title|escape} {$link.coverage|escape}
            {/if}
          </li>
        {/foreach}
      </ul>
    </div>
  {/if}
  <div class="openurls">
    <strong><a href="{$openUrlBase|escape}?{$openUrl|escape}">{translate text="More options"}</a></strong>
    {if !empty($services)}
      <ul>
        {foreach from=$services item=link}
          {if $link.href}
            <li>
              <a href="{$link.href|escape}" title="{$link.service_type|escape}">{$link.title|replace:"/sfxlcl41/":"http://sfx.techlib.cz/sfxlcl41/"}</a>
            </li>
          {/if}
        {/foreach}
      </ul>
    {/if}
  </div>
</div>
