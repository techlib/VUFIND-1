<script language="JavaScript" type="text/javascript" src="{$path}/js/ajax_common.js"></script>
<script language="JavaScript" type="text/javascript" src="{$path}/services/Search/ajax.js"></script>

{* Main Listing *}
<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      {if !$recordCount}
        <div class="page">{translate text="course_reserves_empty_list"}</div>
      {else}
        {* Listing Options *}
        <div class="yui-ge resulthead">
          <div class="yui-u first">
            {translate text="Showing"}
            <b>{$recordStart}</b> - <b>{$recordEnd}</b>
            {translate text='of'} <b>{$recordCount}</b>
            {translate text='Reserves'}</b>
          </div>
  
          <div class="yui-u toggle">
            {translate text='Sort'}
            <select name="sort" onChange="document.location.href = this.options[this.selectedIndex].value;">
            {foreach from=$sortList item=sortData key=sortLabel}
              <option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected{/if}>{translate text=$sortData.desc}</option>
            {/foreach}
            </select>
          </div>
  
        </div>
        {* End Listing Options *}
  
        {if $subpage}
          {include file=$subpage}
        {else}
          {$pageContent}
        {/if}
  
        {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
        <div class="searchtools">
          <strong>{translate text='Search Tools'}:</strong>
          <a href="{$rssLink|escape}" class="feed">{translate text='Get RSS Feed'}</a>
          <a href="{$url}/Search/Email" class="mail" onClick="getLightbox('Search', 'Email', null, null, '{translate text="Email this"}'); return false;">{translate text='Email this Search'}</a>
        </div>
      {/if}
    </div>
    {* End Main Listing *}
  </div>


</div>