{* Huvudlayout för Art PriceMatcher modulen *}
<div class="bootstrap">
    {if isset($errors) && count($errors) > 0}
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h4>{l s='Error' mod='art_pricematcher'}</h4>
            <ul>
                {foreach from=$errors item=error}
                    <li>{$error}</li>
                {/foreach}
            </ul>
        </div>
    {/if}
    
    {if isset($confirmations) && count($confirmations) > 0}
        <div class="alert alert-success">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {foreach from=$confirmations item=confirmation}
                {$confirmation}<br />
            {/foreach}
        </div>
    {/if}
    
    <div class="row">
        {* Vänster sidomeny *}
        <div class="col-md-3">
            {$menu}
        </div>
        
        {* Huvudinnehåll *}
        <div class="col-md-9">
            {$content}
        </div>
    </div>
    
    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12 text-center">
            <p class="text-muted">
                {l s='ART PriceMatcher' mod='art_pricematcher'} v{$module_version} | 
                {l s='Compatible with PrestaShop' mod='art_pricematcher'} v{$ps_version}
            </p>
        </div>
    </div>
</div>

{* Inkludera JavaScript för att hantera olika funktioner *}
<script type="text/javascript">
    $(document).ready(function() {
        // Kopiera till Urklipp-funktion för cron-URL:er
        $('.copy-to-clipboard').click(function() {
            var text = $(this).data('clipboard-text');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(text).select();
            document.execCommand("copy");
            $temp.remove();
            
            // Visa bekräftelse
            $(this).html('<i class="icon-check"></i> {l s='Copied' mod='art_pricematcher'}');
            var button = $(this);
            setTimeout(function() {
                button.html('<i class="icon-copy"></i> {l s='Copy' mod='art_pricematcher'}');
            }, 2000);
        });
    });
</script>