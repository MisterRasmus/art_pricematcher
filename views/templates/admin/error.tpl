{* Error template för Art PriceMatcher modul *}
<div class="alert alert-danger">
    <h4><i class="icon-warning"></i> {l s='An error occurred' mod='art_pricematcher'}</h4>
    <ul>
        {foreach from=$errors item=error}
            <li>{$error}</li>
        {/foreach}
    </ul>
    
    <p>
        <a href="{$smarty.server.HTTP_REFERER|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="icon-arrow-left"></i> {l s='Go back' mod='art_pricematcher'}
        </a>
        <a href="{$link->getAdminLink('AdminPriceMatcherController', true)}" class="btn btn-primary">
            <i class="icon-home"></i> {l s='Go to dashboard' mod='art_pricematcher'}
        </a>
    </p>
</div>