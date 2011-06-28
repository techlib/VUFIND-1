<script language="JavaScript" type="text/javascript" src="{$path}/js/ajax_common.js"></script>
<script language="JavaScript" type="text/javascript" src="{$path}/services/Search/ajax.js"></script>

<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
    
      <div>
        {if $lastsearch}
          <p>  <a href="{$lastsearch|escape}" class="backtosearch">&laquo; {translate text="Back to Search Results"}</a></p>
        {/if}

        {if $info}
        <h2>{$info.name|escape}</h2><br>
  
        {if $info.image}
        <img src="{$info.image}" alt="{$info.altimage|escape}" width="150px" class="alignleft">
        {/if}
        {$info.description|truncate_html:4500:"...":false}
        <p>
          <br clear="All"><a href="http://{$wiki_lang}.wikipedia.org/wiki/{$info.name|escape:"url"}" target="new"><span class="note">{translate text='wiki_link'}</span></a>
        </p>
        {/if}
  
        {* Listing Options *}
        <div class="yui-gc resulthead">
          <div class="yui-u first">
            {if $recordCount}
              {translate text="Showing"}
              <b>{$recordStart}</b> - <b>{$recordEnd}</b>
              {translate text='of'} <b>{$recordCount}</b>
              {translate text='for search'}: <b>'{$authorName|escape:"html"}'</b>,
            {/if}
            {translate text='query time'}: {$qtime}s
          </div>

          <div class="yui-u toggle">
            {if $viewList|@count gt 1}
              {foreach from=$viewList item=viewData key=viewLabel}
                {if !$viewData.selected}<a href="{$viewData.viewUrl|escape}" title="{translate text='Switch view to'} {translate text=$viewData.desc}" >{/if}
                <img src="{$path}/images/view_{$viewData.viewType}.png" {if $viewData.selected}title="{translate text=$viewData.desc} {translate text='view already selected'}"{/if}/>
                {if !$viewData.selected}</a>{/if}
              {/foreach}
              <br/>
            {/if}
            {if $limitList|@count gt 1}
             <form action="{$path}/Search/LimitResults" method="post">
              <label for="limit">{translate text='Results per page'}</label>
              <select id="limit" name="limit" onChange="document.location.href = this.options[this.selectedIndex].value;">
                {foreach from=$limitList item=limitData key=limitLabel}
                  <option value="{$limitData.limitUrl|escape}"{if $limitData.selected} selected="selected"{/if}>{$limitData.desc|escape}</option>
                {/foreach}
              </select>
              <noscript><input type="submit" value="{translate text="Set"}" /></noscript>
             </form>
            {/if}
            <form action="{$path}/Search/SortResults" method="post">
              <label for="sort">{translate text='Sort'}</label>
              <select id="sort" name="sort" onChange="document.location.href = this.options[this.selectedIndex].value;">
                {foreach from=$sortList item=sortData key=sortLabel}
                  <option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected{/if}>{translate text=$sortData.desc}</option>
                {/foreach}
              </select>
              <noscript><input type="submit" value="{translate text="Set"}" /></noscript>
            </form>
          </div>

        </div>
        {* End Listing Options *}

        {if $subpage}
          {include file=$subpage}
        {else}
          {$pageContent}
        {/if}

        {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
  
      </div>
      <div class="searchtools">
        <strong>{translate text='Search Tools'}:</strong>
        <a href="{$rssLink|escape}" class="feed">{translate text='Get RSS Feed'}</a>
        <a href="{$url}/Search/Email" class="mail" onClick="getLightbox('Search', 'Email', null, null, '{translate text="Email this"}'); return false;">{translate text='Email this Search'}</a>
      </div>
    </div>
  </div>
  
  {* Recommendations *}
  <div class="yui-b">
    {if $sideRecommendations}
      {foreach from=$sideRecommendations item="recommendations"}
        {include file=$recommendations}
      {/foreach}
    {/if}
  </div>
  {* End Recommendations *}

</div>
