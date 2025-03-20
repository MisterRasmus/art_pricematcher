{* Competitors template för Art PriceMatcher modul *}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-plus"></i> {l s='Add New Competitor' mod='art_pricematcher'}
    </div>
    <div class="panel-body">
        <p>
            {l s='Add a new competitor to the price matching system. This will create a standardizer script for the competitor.' mod='art_pricematcher'}
        </p>

        <form method="post" action="{$form_action}" class="form-horizontal">
            <input type="hidden" name="submitAddCompetitor" value="1" />
            <input type="hidden" name="tab" value="competitors" />

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Competitor Name' mod='art_pricematcher'}
                </label>
                <div class="col-lg-9">
                    <input type="text" name="competitor_name" class="form-control" required />
                    <p class="help-block">{l s='This name will be used to create the competitor class file.' mod='art_pricematcher'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Competitor URL' mod='art_pricematcher'}
                </label>
                <div class="col-lg-9">
                    <input type="text" name="competitor_url" class="form-control" />
                    <p class="help-block">{l s='URL to the competitor\'s website (optional).' mod='art_pricematcher'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Enable Cron Jobs' mod='art_pricematcher'}
                </label>
                <div class="col-lg-9">
                    <div class="well">
                        <div class="form-group">
                            <label class="control-label col-lg-4">
                                {l s='Enable Download' mod='art_pricematcher'}
                            </label>
                            <div class="col-lg-8">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="competitor_cron_download" value="1" />
                                        {l s='Enable automatic download for this competitor' mod='art_pricematcher'}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-4">
                                {l s='Enable Compare' mod='art_pricematcher'}
                            </label>
                            <div class="col-lg-8">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="competitor_cron_compare" value="1" />
                                        {l s='Enable automatic price comparison for this competitor' mod='art_pricematcher'}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-4">
                                {l s='Enable Update' mod='art_pricematcher'}
                            </label>
                            <div class="col-lg-8">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="competitor_cron_update" value="1" />
                                        {l s='Enable automatic price updates for this competitor' mod='art_pricematcher'}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" class="btn btn-default pull-right" name="submitAddCompetitor">
                    <i class="process-icon-plus"></i> {l s='Add Competitor' mod='art_pricematcher'}
                </button>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-users"></i> {l s='Competitors Management' mod='art_pricematcher'}
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped table-bordered" id="competitors-table">
            <thead>
                <tr>
                    <th>{l s='ID' mod='art_pricematcher'}</th>
                    <th>{l s='Name' mod='art_pricematcher'}</th>
                    <th>{l s='URL' mod='art_pricematcher'}</th>
                    <th>{l s='Status' mod='art_pricematcher'}</th>
                    <th>{l s='Cron Jobs' mod='art_pricematcher'}</th>
                    <th>{l s='Last Update' mod='art_pricematcher'}</th>
                    <th>{l s='Actions' mod='art_pricematcher'}</th>
                </tr>
            </thead>
            <tbody>
                {if isset($competitors) && $competitors|@count > 0}
                    {foreach from=$competitors item=competitor}
                        <tr>
                            <td>{$competitor.id_competitor}</td>
                            <td>{$competitor.name}</td>
                            <td>
                                {if $competitor.url}
                                    <a href="{$competitor.url}" target="_blank">{$competitor.url|truncate:40:"..."}</a>
                                {else}
                                    <em>{l s='Not set' mod='art_pricematcher'}</em>
                                {/if}
                            </td>
                            <td>
                                {if $competitor.active}
                                    <span class="badge badge-success">{l s='Active' mod='art_pricematcher'}</span>
                                {else}
                                    <span class="badge badge-danger">{l s='Inactive' mod='art_pricematcher'}</span>
                                {/if}
                            </td>
                            <td>
                                <div class="label-container">
                                    {if $competitor.cron_download}
                                        <span class="label label-info" title="{l s='Download' mod='art_pricematcher'}">D</span>
                                    {else}
                                        <span class="label label-default" title="{l s='Download' mod='art_pricematcher'}">D</span>
                                    {/if}
                                    
                                    {if $competitor.cron_compare}
                                        <span class="label label-info" title="{l s='Compare' mod='art_pricematcher'}">C</span>
                                    {else}
                                        <span class="label label-default" title="{l s='Compare' mod='art_pricematcher'}">C</span>
                                    {/if}
                                    
                                    {if $competitor.cron_update}
                                        <span class="label label-info" title="{l s='Update' mod='art_pricematcher'}">U</span>
                                    {else}
                                        <span class="label label-default" title="{l s='Update' mod='art_pricematcher'}">U</span>
                                    {/if}
                                </div>
                            </td>
                            <td>
                                {if $competitor.date_upd != '0000-00-00 00:00:00'}
                                    {$competitor.date_upd|date_format:"%Y-%m-%d %H:%M:%S"}
                                {else}
                                    {l s='Never' mod='art_pricematcher'}
                                {/if}
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <i class="icon-cog"></i> {l s='Actions' mod='art_pricematcher'} <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="javascript:void(0);" class="js-edit-competitor" data-id="{$competitor.id_competitor}">
                                                <i class="icon-pencil"></i> {l s='Edit Settings' mod='art_pricematcher'}
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{$link->getAdminLink('AdminPriceMatcherController', true, [], ['tab' => 'dashboard', 'action' => 'download', 'competitor' => $competitor.name])}">
                                                <i class="icon-download"></i> {l s='Download Prices' mod='art_pricematcher'}
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{$link->getAdminLink('AdminPriceMatcherController', true, [], ['tab' => 'dashboard', 'action' => 'compare', 'competitor' => $competitor.name])}">
                                                <i class="icon-refresh"></i> {l s='Compare Prices' mod='art_pricematcher'}
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{$link->getAdminLink('AdminPriceMatcherController', true, [], ['tab' => 'dashboard', 'action' => 'update', 'competitor' => $competitor.name])}">
                                                <i class="icon-tag"></i> {l s='Update Prices' mod='art_pricematcher'}
                                            </a>
                                        </li>
                                        <li class="divider"></li>
                                        <li>
                                            <a href="javascript:void(0);" class="js-toggle-competitor" data-id="{$competitor.id_competitor}" data-status="{if $competitor.active}0{else}1{/if}">
                                                {if $competitor.active}
                                                    <i class="icon-ban"></i> {l s='Disable' mod='art_pricematcher'}
                                                {else}
                                                    <i class="icon-check"></i> {l s='Enable' mod='art_pricematcher'}
                                                {/if}
                                            </a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="js-delete-competitor text-danger" data-id="{$competitor.id_competitor}" data-name="{$competitor.name}">
                                                <i class="icon-trash"></i> {l s='Delete' mod='art_pricematcher'}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                {else}
                    <tr>
                        <td colspan="7" class="text-center">{l s='No competitors found' mod='art_pricematcher'}</td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>

{if isset($competitors) && $competitors|@count > 0}
    {foreach from=$competitors item=competitor}
        <div class="panel competitor-settings-panel" id="competitor-panel-{$competitor.id_competitor}" style="display: none;">
            <div class="panel-heading">
                <i class="icon-cog"></i> {l s='Settings for' mod='art_pricematcher'} {$competitor.name|escape:'html':'UTF-8'}
                <button type="button" class="close js-close-competitor-panel">
                    <span>&times;</span>
                </button>
            </div>
            <div class="panel-body">
                <form method="post" action="{$form_action}" class="form-horizontal">
                    <input type="hidden" name="submitCompetitorSettings" value="1" />
                    <input type="hidden" name="tab" value="competitors" />
                    <input type="hidden" name="id_competitor" value="{$competitor.id_competitor}" />
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Competitor Status' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="competitor[{$competitor.id_competitor}][active]"
                                        value="1" {if $competitor.active}checked="checked" {/if} />
                                    {l s='Enable this competitor' mod='art_pricematcher'}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Competitor URL' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <input type="text" name="competitor[{$competitor.id_competitor}][url]"
                                class="form-control" value="{$competitor.url|escape:'html':'UTF-8'}" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Enable Cron Jobs' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="well">
                                <div class="form-group">
                                    <label class="control-label col-lg-4">
                                        {l s='Enable Download' mod='art_pricematcher'}
                                    </label>
                                    <div class="col-lg-8">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox"
                                                    name="competitor[{$competitor.id_competitor}][cron_download]"
                                                    value="1"
                                                    {if isset($competitor.cron_download) && $competitor.cron_download}checked="checked" {/if} />
                                                {l s='Enable automatic download for this competitor' mod='art_pricematcher'}
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-lg-4">
                                        {l s='Enable Compare' mod='art_pricematcher'}
                                    </label>
                                    <div class="col-lg-8">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox"
                                                    name="competitor[{$competitor.id_competitor}][cron_compare]"
                                                    value="1"
                                                    {if isset($competitor.cron_compare) && $competitor.cron_compare}checked="checked" {/if} />
                                                {l s='Enable automatic price comparison for this competitor' mod='art_pricematcher'}
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-lg-4">
                                        {l s='Enable Update' mod='art_pricematcher'}
                                    </label>
                                    <div class="col-lg-8">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox"
                                                    name="competitor[{$competitor.id_competitor}][cron_update]"
                                                    value="1"
                                                    {if isset($competitor.cron_update) && $competitor.cron_update}checked="checked" {/if} />
                                                {l s='Enable automatic price updates for this competitor' mod='art_pricematcher'}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Competitor-Specific Discount Settings' mod='art_pricematcher'}
                        </label>
                        <div class="col-lg-9">
                            <div class="alert alert-info">
                                {l s='These settings will override the global discount settings for this competitor.' mod='art_pricematcher'}
                            </div>
                            
                            <div class="well">
                                <div class="form-group">
                                    <label class="control-label col-lg-4">
                                        {l s='Override Global Settings' mod='art_pricematcher'}
                                    </label>
                                    <div class="col-lg-8">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox"
                                                    name="competitor[{$competitor.id_competitor}][override_discount_settings]"
                                                    value="1" class="js-toggle-competitor-settings"
                                                    data-competitor-id="{$competitor.id_competitor}"
                                                    {if isset($competitor.override_discount_settings) && $competitor.override_discount_settings}checked="checked" {/if} />
                                                {l s='Use specific discount settings for this competitor' mod='art_pricematcher'}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="competitor-specific-settings" id="competitor-settings-{$competitor.id_competitor}" 
                                    {if !isset($competitor.override_discount_settings) || !$competitor.override_discount_settings}style="display: none;"{/if}>
                                    
                                    <div class="form-group">
                                        <label class="control-label col-lg-4">
                                            {l s='Discount Strategy' mod='art_pricematcher'}
                                        </label>
                                        <div class="col-lg-8">
                                            <select name="competitor[{$competitor.id_competitor}][discount_strategy]" class="form-control">
                                                <option value="margin"
                                                    {if isset($competitor.discount_strategy) && $competitor.discount_strategy == 'margin'}selected="selected" {/if}>
                                                    {l s='Maintain Minimum Margin' mod='art_pricematcher'}
                                                </option>
                                                <option value="discount"
                                                    {if isset($competitor.discount_strategy) && $competitor.discount_strategy == 'discount'}selected="selected" {/if}>
                                                    {l s='Limit Maximum Discount' mod='art_pricematcher'}
                                                </option>
                                                <option value="both"
                                                    {if isset($competitor.discount_strategy) && $competitor.discount_strategy == 'both'}selected="selected" {/if}>
                                                    {l s='Both (Margin and Discount)' mod='art_pricematcher'}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="control-label col-lg-4">
                                            {l s='Minimum Margin (%)' mod='art_pricematcher'}
                                        </label>
                                        <div class="col-lg-8">
                                            <div class="input-group">
                                                <input type="text" name="competitor[{$competitor.id_competitor}][min_margin_percent]" 
                                                    class="form-control" value="{if isset($competitor.min_margin_percent)}{$competitor.min_margin_percent|escape:'html':'UTF-8'}{else}{$settings.global.min_margin_percent|escape:'html':'UTF-8'}{/if}" />
                                                <span class="input-group-addon">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="control-label col-lg-4">
                                            {l s='Maximum Discount (%)' mod='art_pricematcher'}
                                        </label>
                                        <div class="col-lg-8">
                                            <div class="input-group">
                                                <input type="text" name="competitor[{$competitor.id_competitor}][max_discount_percent]" 
                                                    class="form-control" value="{if isset($competitor.max_discount_percent)}{$competitor.max_discount_percent|escape:'html':'UTF-8'}{else}{$settings.global.max_discount_percent|escape:'html':'UTF-8'}{/if}" />
                                                <span class="input-group-addon">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="control-label col-lg-4">
                                            {l s='Price Underbid (Kr)' mod='art_pricematcher'}
                                        </label>
                                        <div class="col-lg-8">
                                            <div class="input-group">
                                                <input type="text" name="competitor[{$competitor.id_competitor}][price_underbid]" 
                                                    class="form-control" value="{if isset($competitor.price_underbid)}{$competitor.price_underbid|escape:'html':'UTF-8'}{else}{$settings.global.price_underbid|escape:'html':'UTF-8'}{/if}" />
                                                <span class="input-group-addon">Kr</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="control-label col-lg-4">
                                            {l s='Minimum Price Threshold (Kr)' mod='art_pricematcher'}
                                        </label>
                                        <div class="col-lg-8">
                                            <div class="input-group">
                                                <input type="text" name="competitor[{$competitor.id_competitor}][min_price_threshold]" 
                                                    class="form-control" value="{if isset($competitor.min_price_threshold)}{$competitor.min_price_threshold|escape:'html':'UTF-8'}{else}{$settings.global.min_price_threshold|escape:'html':'UTF-8'}{/if}" />
                                                <span class="input-group-addon">Kr</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="control-label col-lg-4">
                                            {l s='Discount Validity (Days)' mod='art_pricematcher'}
                                        </label>
                                        <div class="col-lg-8">
                                            <div class="input-group">
                                                <input type="text" name="competitor[{$competitor.id_competitor}][discount_validity_days]" 
                                                    class="form-control" value="{if isset($competitor.discount_validity_days)}{$competitor.discount_validity_days|escape:'html':'UTF-8'}{else}{$settings.global.discount_validity_days|escape:'html':'UTF-8'}{/if}" />
                                                <span class="input-group-addon">{l s='days' mod='art_pricematcher'}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="control-label col-lg-4">
                                            {l s='Clean Expired Discounts' mod='art_pricematcher'}
                                        </label>
                                        <div class="col-lg-8">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" 
                                                        name="competitor[{$competitor.id_competitor}][clean_expired_discounts]" 
                                                        value="1"
                                                        {if isset($competitor.clean_expired_discounts) && $competitor.clean_expired_discounts}checked="checked" {/if} />
                                                    {l s='Automatically clean expired discounts during price updates' mod='art_pricematcher'}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-default pull-right" name="submitCompetitorSettings">
                            <i class="process-icon-save"></i> {l s='Save Settings' mod='art_pricematcher'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    {/foreach}
{/if}

{* Bekräftelsemodal för borttagning *}
<div class="modal fade" id="delete-competitor-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{l s='Confirm Deletion' mod='art_pricematcher'}</h4>
            </div>
            <div class="modal-body">
                <p>{l s='Are you sure you want to delete this competitor?' mod='art_pricematcher'}</p>
                <p class="text-danger">{l s='This will permanently remove the competitor and all related price matching settings.' mod='art_pricematcher'}</p>
                <p id="delete-competitor-name"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Cancel' mod='art_pricematcher'}</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-competitor">
                    <i class="icon-trash"></i> {l s='Delete' mod='art_pricematcher'}
                </button>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
.label-container {
    display: inline-block;
}
.label-container .label {
    margin-right: 3px;
    display: inline-block;
    min-width: 20px;
    text-align: center;
}
</style>