{if !empty($banner_enabled) && $banner_enabled == 1}
    <div id="textbanner" class="noselect">
        <div class="textbanner-container {if !empty($banner_link)}pointer{/if}" {if !empty($banner_link)}onclick="window.location.href = '{$banner_link}';"{/if}>
            {$banner_text}
        </div>
    </div>
{/if}