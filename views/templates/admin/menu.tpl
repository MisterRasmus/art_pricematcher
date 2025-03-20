{* Sidomeny för Art PriceMatcher modulen *}
<div class="list-group">
    {foreach from=$menu_items item=item}
        <a href="{$item.url}" class="list-group-item {if $item.active}active{/if}">
            <i class="icon-{$item.icon}"></i> {$item.name}
        </a>
    {/foreach}
</div>

{* Visa version och länkar *}
<div class="panel" style="margin-top: 20px;">
    <div class="panel-heading">
        <i class="icon-info-circle"></i> {l s='Information' mod='art_pricematcher'}
    </div>
    <div class="panel-body">
        <p>
            <a href="https://www.art.se" target="_blank" rel="noopener noreferrer">
                <i class="icon-external-link"></i> {l s='Documentation' mod='art_pricematcher'}
            </a>
        </p>
        <p>
            <a href="https://www.art.se/support" target="_blank" rel="noopener noreferrer">
                <i class="icon-life-ring"></i> {l s='Support' mod='art_pricematcher'}
            </a>
        </p>
    </div>
</div>