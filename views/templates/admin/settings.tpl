{* Settings template för Art PriceMatcher modul *}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Global Settings' mod='art_pricematcher'}
    </div>
    <div class="panel-body">
        <form method="post" action="{$form_action}" class="form-horizontal">
            <input type="hidden" name="submitSettings" value="1" />
            <input type="hidden" name="tab" value="settings" />
            
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#tab-general" aria-controls="tab-general" role="tab" data-toggle="tab">
                        <i class="icon-cog"></i> {l s='General' mod='art_pricematcher'}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab-cron" aria-controls="tab-cron" role="tab" data-toggle="tab">
                        <i class="icon-time"></i> {l s='Cron Settings' mod='art_pricematcher'}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab-discounts" aria-controls="tab-discounts" role="tab" data-toggle="tab">
                        <i class="icon-tag"></i> {l s='Discount Settings' mod='art_pricematcher'}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab-exclusions" aria-controls="tab-exclusions" role="tab" data-toggle="tab">
                        <i class="icon-ban"></i> {l s='Exclusions' mod='art_pricematcher'}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tab-notifications" aria-controls="tab-notifications" role="tab" data-toggle="tab">
                        <i class="icon-envelope"></i> {l s='Notifications' mod='art_pricematcher'}
                    </a>
                </li>
            </ul>
            
            <div class="tab-content panel">
                {* General Settings Tab *}
                <div role="tabpanel" class="tab-pane active" id="tab-general">
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Module Status' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="active" value="1" {if isset($settings.active) && $settings.active}checked="checked"{/if} />
                                    {l s='Enable Price Matcher Module' mod='art_pricematcher'}
                                </label>
                            </div>
                            <p class="help-block">
                                {l s='When disabled, no price adjustments will be made automatically.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                </div>
                
                {* Cron Settings Tab *}
                <div role="tabpanel" class="tab-pane" id="tab-cron">
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Cron Token' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <input type="text" name="cron_token" class="form-control" value="{if isset($settings.cron_token)}{$settings.cron_token|escape:'html':'UTF-8'}{/if}" />
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" id="generate_token">
                                        <i class="icon-random"></i> {l s='Generate' mod='art_pricematcher'}
                                    </button>
                                </span>
                            </div>
                            <p class="help-block">
                                {l s='Security token required for cron jobs. Keep this secret.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Cron URLs' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="alert alert-info">
                                <p>{l s='Use these URLs to set up your cron jobs:' mod='art_pricematcher'}</p>
                                <ul>
                                    <li>
                                        <strong>{l s='Download:' mod='art_pricematcher'}</strong> 
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="{$cron_urls.download|escape:'html':'UTF-8'}" readonly />
                                            <span class="input-group-btn">
                                                <button class="btn btn-default copy-to-clipboard" type="button" data-clipboard-text="{$cron_urls.download|escape:'html':'UTF-8'}">
                                                    <i class="icon-copy"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </li>
                                    <li class="mt-2">
                                        <strong>{l s='Compare:' mod='art_pricematcher'}</strong> 
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="{$cron_urls.compare|escape:'html':'UTF-8'}" readonly />
                                            <span class="input-group-btn">
                                                <button class="btn btn-default copy-to-clipboard" type="button" data-clipboard-text="{$cron_urls.compare|escape:'html':'UTF-8'}">
                                                    <i class="icon-copy"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </li>
                                    <li class="mt-2">
                                        <strong>{l s='Update:' mod='art_pricematcher'}</strong> 
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="{$cron_urls.update|escape:'html':'UTF-8'}" readonly />
                                            <span class="input-group-btn">
                                                <button class="btn btn-default copy-to-clipboard" type="button" data-clipboard-text="{$cron_urls.update|escape:'html':'UTF-8'}">
                                                    <i class="icon-copy"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </li>
                                    <li class="mt-2">
                                        <strong>{l s='Clean Expired:' mod='art_pricematcher'}</strong> 
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="{$cron_urls.clean|escape:'html':'UTF-8'}" readonly />
                                            <span class="input-group-btn">
                                                <button class="btn btn-default copy-to-clipboard" type="button" data-clipboard-text="{$cron_urls.clean|escape:'html':'UTF-8'}">
                                                    <i class="icon-copy"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Recommended Cron Schedule' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>{l s='Operation' mod='art_pricematcher'}</th>
                                            <th>{l s='Recommended Schedule' mod='art_pricematcher'}</th>
                                            <th>{l s='Cron Expression' mod='art_pricematcher'}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{l s='Download' mod='art_pricematcher'}</td>
                                            <td>{l s='Once per day (morning)' mod='art_pricematcher'}</td>
                                            <td><code>0 6 * * *</code></td>
                                        </tr>
                                        <tr>
                                            <td>{l s='Compare' mod='art_pricematcher'}</td>
                                            <td>{l s='Once per day (after download)' mod='art_pricematcher'}</td>
                                            <td><code>30 6 * * *</code></td>
                                        </tr>
                                        <tr>
                                            <td>{l s='Update' mod='art_pricematcher'}</td>
                                            <td>{l s='Once per day (after compare)' mod='art_pricematcher'}</td>
                                            <td><code>0 7 * * *</code></td>
                                        </tr>
                                        <tr>
                                            <td>{l s='Clean Expired' mod='art_pricematcher'}</td>
                                            <td>{l s='Once per day (midnight)' mod='art_pricematcher'}</td>
                                            <td><code>0 0 * * *</code></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                {* Discount Settings Tab *}
                <div role="tabpanel" class="tab-pane" id="tab-discounts">
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Discount Strategy' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <select name="discount_strategy" class="form-control">
                                <option value="margin" {if isset($settings.discount_strategy) && $settings.discount_strategy == 'margin'}selected="selected"{/if}>
                                    {l s='Maintain Minimum Margin' mod='art_pricematcher'}
                                </option>
                                <option value="discount" {if isset($settings.discount_strategy) && $settings.discount_strategy == 'discount'}selected="selected"{/if}>
                                    {l s='Limit Maximum Discount' mod='art_pricematcher'}
                                </option>
                                <option value="both" {if isset($settings.discount_strategy) && $settings.discount_strategy == 'both'}selected="selected"{/if}>
                                    {l s='Both (Margin and Discount)' mod='art_pricematcher'}
                                </option>
                            </select>
                            <p class="help-block">
                                {l s='Choose how to calculate discounts: based on maintaining a minimum margin, limiting the maximum discount percentage, or both.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Minimum Margin (%)' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <input type="text" name="min_margin_percent" class="form-control" value="{if isset($settings.min_margin_percent)}{$settings.min_margin_percent|escape:'html':'UTF-8'}{else}30{/if}" />
                                <span class="input-group-addon">%</span>
                            </div>
                            <p class="help-block">
                                {l s='The minimum profit margin to maintain when applying discounts.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Maximum Discount (%)' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <input type="text" name="max_discount_percent" class="form-control" value="{if isset($settings.max_discount_percent)}{$settings.max_discount_percent|escape:'html':'UTF-8'}{else}24{/if}" />
                                <span class="input-group-addon">%</span>
                            </div>
                            <p class="help-block">
                                {l s='The maximum discount percentage allowed when matching competitor prices.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Minimum Discount (%)' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <input type="text" name="min_discount_percent" class="form-control" value="{if isset($settings.min_discount_percent)}{$settings.min_discount_percent|escape:'html':'UTF-8'}{else}5{/if}" />
                                <span class="input-group-addon">%</span>
                            </div>
                            <p class="help-block">
                                {l s='The minimum discount percentage required to trigger a price match.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Price Underbid (Kr)' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <input type="text" name="price_underbid" class="form-control" value="{if isset($settings.price_underbid)}{$settings.price_underbid|escape:'html':'UTF-8'}{else}5{/if}" />
                                <span class="input-group-addon">Kr</span>
                            </div>
                            <p class="help-block">
                                {l s='Amount to undercut competitor prices by.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Minimum Price Threshold (Kr)' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <input type="text" name="min_price_threshold" class="form-control" value="{if isset($settings.min_price_threshold)}{$settings.min_price_threshold|escape:'html':'UTF-8'}{else}100{/if}" />
                                <span class="input-group-addon">Kr</span>
                            </div>
                            <p class="help-block">
                                {l s='Minimum price threshold for products. Products below this price will not be discounted.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Discount Validity (Days)' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <input type="text" name="discount_days_valid" class="form-control" value="{if isset($settings.discount_days_valid)}{$settings.discount_days_valid|escape:'html':'UTF-8'}{else}2{/if}" />
                                <span class="input-group-addon">{l s='days' mod='art_pricematcher'}</span>
                            </div>
                            <p class="help-block">
                                {l s='Number of days that price discounts will remain active.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Customer Groups' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <select name="discount_customer_groups[]" class="form-control select2-groups" multiple="multiple" style="width: 100%;">
                                {foreach from=$customer_groups key=id_group item=group_name}
                                    <option value="{$id_group}" {if isset($settings.discount_customer_groups) && in_array($id_group, $settings.discount_customer_groups)}selected="selected"{/if}>
                                        {$group_name}
                                    </option>
                                {/foreach}
                            </select>
                            <p class="help-block">
                                {l s='Select customer groups that will receive the discounted prices.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Max Discount Behavior' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <select name="max_discount_behavior" class="form-control">
                                <option value="partial" {if isset($settings.max_discount_behavior) && $settings.max_discount_behavior == 'partial'}selected="selected"{/if}>
                                    {l s='Apply Partial Discount (up to maximum)' mod='art_pricematcher'}
                                </option>
                                <option value="skip" {if isset($settings.max_discount_behavior) && $settings.max_discount_behavior == 'skip'}selected="selected"{/if}>
                                    {l s='Skip Product Entirely' mod='art_pricematcher'}
                                </option>
                            </select>
                            <p class="help-block">
                                {l s='Choose how to handle products that would exceed the maximum discount: apply a partial discount up to the maximum, or skip the product entirely.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Clean Expired Discounts' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="clean_expired_discounts" value="1" {if isset($settings.clean_expired_discounts) && $settings.clean_expired_discounts}checked="checked"{/if} />
                                    {l s='Automatically clean expired discounts during price updates' mod='art_pricematcher'}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                {* Exclusions Tab *}
                <div role="tabpanel" class="tab-pane" id="tab-exclusions">
                    <div class="alert alert-info">
                        <p>
                            <i class="icon-info-circle"></i> {l s='Products in excluded categories or with excluded manufacturers will not be price-matched, even if they appear in competitor price lists.' mod='art_pricematcher'}
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Excluded Categories' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <select name="excluded_categories[]" class="form-control select2-categories" multiple="multiple" style="width: 100%;">
                                {foreach from=$categories key=id_category item=category_name}
                                    <option value="{$id_category}" {if isset($settings.excluded_categories) && in_array($id_category, $settings.excluded_categories)}selected="selected"{/if}>
                                        {$category_name}
                                    </option>
                                {/foreach}
                            </select>
                            <p class="help-block">
                                {l s='Select categories to exclude from price matching. Subcategories will also be excluded.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Excluded Manufacturers' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <select name="excluded_manufacturers[]" class="form-control select2-manufacturers" multiple="multiple" style="width: 100%;">
                                {foreach from=$manufacturers key=id_manufacturer item=manufacturer_name}
                                    <option value="{$id_manufacturer}" {if isset($settings.excluded_manufacturers) && in_array($id_manufacturer, $settings.excluded_manufacturers)}selected="selected"{/if}>
                                        {$manufacturer_name}
                                    </option>
                                {/foreach}
                            </select>
                            <p class="help-block">
                                {l s='Select manufacturers to exclude from price matching.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Minimum Stock' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <input type="text" name="min_stock" class="form-control" value="{if isset($settings.min_stock)}{$settings.min_stock|escape:'html':'UTF-8'}{else}1{/if}" />
                                <span class="input-group-addon">{l s='units' mod='art_pricematcher'}</span>
                            </div>
                            <p class="help-block">
                                {l s='Minimum quantity of stock required to apply price matching. Products with stock below this level will be excluded.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Excluded Supplier References' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <textarea name="excluded_references" class="form-control" rows="4">{if isset($settings.excluded_references)}{$settings.excluded_references|escape:'html':'UTF-8'}{/if}</textarea>
                            <p class="help-block">
                                {l s='Enter supplier references to exclude from price matching, one per line. You can use wildcards (*) at the start or end, e.g. "ART*" to exclude all references starting with "ART".' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                </div>
                
                {* Notifications Tab *}
                <div role="tabpanel" class="tab-pane" id="tab-notifications">
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Enable Email Notifications' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="email_notifications" value="1" {if isset($settings.email_notifications) && $settings.email_notifications}checked="checked"{/if} />
                                    {l s='Send email notifications after price updates' mod='art_pricematcher'}
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Email Recipients' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <textarea name="email_recipients" class="form-control" rows="3">{if isset($settings.email_recipients)}{$settings.email_recipients|escape:'html':'UTF-8'}{/if}</textarea>
                            <p class="help-block">{l s='Enter email addresses separated by commas' mod='art_pricematcher'}</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Notification Frequency' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <select name="email_frequency" class="form-control">
                                <option value="always" {if isset($settings.email_frequency) && $settings.email_frequency == 'always'}selected="selected"{/if}>
                                    {l s='After Every Update (potentially multiple times per day)' mod='art_pricematcher'}
                                </option>
                                <option value="daily" {if isset($settings.email_frequency) && $settings.email_frequency == 'daily'}selected="selected"{/if}>
                                    {l s='Daily Summary' mod='art_pricematcher'}
                                </option>
                                <option value="weekly" {if isset($settings.email_frequency) && $settings.email_frequency == 'weekly'}selected="selected"{/if}>
                                    {l s='Weekly Summary' mod='art_pricematcher'}
                                </option>
                            </select>
                            <p class="help-block">
                                {l s='Choose how often to send notification emails.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Notification Threshold' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <input type="text" name="notification_threshold" class="form-control" value="{if isset($settings.notification_threshold)}{$settings.notification_threshold|escape:'html':'UTF-8'}{else}15{/if}" />
                                <span class="input-group-addon">%</span>
                            </div>
                            <p class="help-block">
                                {l s='Only send notifications for price changes exceeding this percentage.' mod='art_pricematcher'}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="panel-footer">
                <button type="submit" class="btn btn-default pull-right" name="submitPriceMatcherSettings">
                    <i class="process-icon-save"></i> {l s='Save Settings' mod='art_pricematcher'}
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.mt-2 {
    margin-top: 10px;
}
.tab-content {
    padding: 20px 15px;
    border-left: 1px solid #ddd;
    border-right: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
}
</style>