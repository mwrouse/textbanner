{if !empty($banner_enabled) && $banner_enabled == 1}
    <div id="textbanner" class="noselect">
        <a class="textbanner-container {if empty($banner_link)}no-pointer{/if}" href="{if !empty($banner_link)}{$banner_link}{else}#{/if}">
            {$banner_text}
        </a>
    </div>
{/if}