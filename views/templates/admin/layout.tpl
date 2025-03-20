{* Huvudlayout för Art PriceMatcher modulen *}
<div class="bootstrap">
    {if isset($errors) && $errors|count > 0}
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <ul>
                {foreach from=$errors item=error}
                    <li>{$error}</li>
                {/foreach}
            </ul>
        </div>
    {/if}
    
    {if isset($debug_menu) && $debug_menu}
        <div class="alert alert-info">
            <h4>Menu Items Debug:</h4>
            <pre>{$debug_menu_items|escape:'html'}</pre>
        </div>
    {/if}
    
    <div class="row">
        <div class="col-lg-3">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-list"></i> {l s="Navigation" mod="art_pricematcher"}
                </div>
                <div class="list-group">
                    {foreach from=$menu_items item=item}
                        <a href="{$item.url}" class="list-group-item {if $item.active}active{/if}">
                            <i class="icon-{$item.icon}"></i> {$item.name}
                        </a>
                    {/foreach}
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            {$content}
        </div>
    </div>
    
    <div class="row" style="margin-top: 20px;">
        <div class="col-lg-12 text-center">
            <p class="text-muted">
                {l s="ART PriceMatcher" mod="art_pricematcher"} v{$module_version} | PrestaShop v{$ps_version}
            </p>
        </div>
    </div>
</div>

{* Include JavaScript for handling various functions *}
<script type="text/javascript">
    $(document).ready(function() {
        // Add any JavaScript functionality here
    });
</script>