{* Sidomeny för Art PriceMatcher modulen *}
<div class="list-group">
    {foreach from=$menu_items item=item}
        <a href="{$item.url}" class="list-group-item {if $item.active}active{/if}">
            <i class="icon-{$item.icon}"></i> {$item.name}
        </a>
    {/foreach}
</div>

<style>
.list-group {
    margin-bottom: 20px;
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0,0,0,.1);
}

.list-group-item {
    position: relative;
    display: block;
    padding: 10px 15px;
    margin-bottom: -1px;
    background-color: #fff;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #555;
}

.list-group-item:first-child {
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
}

.list-group-item:last-child {
    margin-bottom: 0;
    border-bottom-right-radius: 4px;
    border-bottom-left-radius: 4px;
}

.list-group-item.active {
    z-index: 2;
    color: #fff;
    background-color: #25b9d7;
    border-color: #25b9d7;
}

.list-group-item i {
    margin-right: 10px;
    vertical-align: middle;
}
</style>