{* Dashboard template for Art PriceMatcher module *}

<button type="button" class="btn btn-primary" data-toggle="collapse" data-target="#priceManagementCollapse"
    aria-expanded="false" aria-controls="priceManagementCollapse" style="text-transform: none;">
    {l s='How price matching works' mod='art_pricematcher'}
</button>

<br /><br />

<div class="collapse" id="priceManagementCollapse">
    <div class="well">
        <h4>{l s='How Price Matching Works' mod='art_pricematcher'}</h4>
        <ol>
            <li><strong>{l s='Download Prices:' mod='art_pricematcher'}</strong> {l s='First, download the latest prices from the competitor website.' mod='art_pricematcher'}</li>
            <li><strong>{l s='Compare Prices:' mod='art_pricematcher'}</strong> {l s='Compare the competitor prices with your prices and identify products that need price adjustments.' mod='art_pricematcher'}</li>
            <li><strong>{l s='Update Prices:' mod='art_pricematcher'}</strong> {l s='Apply the price changes based on your price matching strategy.' mod='art_pricematcher'}</li>
        </ol>
        <p>{l s='You can configure price matching strategies for each competitor in the Settings tab.' mod='art_pricematcher'}</p>
    </div>
</div>

<form method="post" action="{$form_action}" class="form-horizontal">
    <input type="hidden" name="submitPriceMatcher" value="1" />
    <input type="hidden" name="tab" value="dashboard" />
    
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Run Price Matching' mod='art_pricematcher'}
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Select Competitor' mod='art_pricematcher'}</label>
                <div class="col-lg-9">
                    <select name="competitor" class="form-control">
                        <option value="">{l s='-- Select a competitor --' mod='art_pricematcher'}</option>
                        {if isset($competitors) && $competitors|@count > 0}
                            {foreach from=$competitors item=competitor}
                                {if $competitor.active}
                                    <option value="{$competitor.name}">{$competitor.name}</option>
                                {/if}
                            {/foreach}
                        {/if}
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Action' mod='art_pricematcher'}</label>
                <div class="col-lg-9">
                    <div class="radio">
                        <label>
                            <input type="radio" name="action" value="download" checked="checked" />
                            {l s='Download Prices' mod='art_pricematcher'}
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="action" value="compare" />
                            {l s='Compare Prices' mod='art_pricematcher'}
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="action" value="update" />
                            {l s='Update Prices' mod='art_pricematcher'}
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="action" value="clean_expired" />
                            {l s='Clean Expired Discounts' mod='art_pricematcher'}
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <div class="col-lg-9 col-lg-offset-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="icon-play"></i> {l s='Run' mod='art_pricematcher'}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

{if isset($cron_token)}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-time"></i> {l s='Automation' mod='art_pricematcher'}
        </div>
        <div class="panel-body">
            <p>{l s='You can automate price matching using cron jobs. Here are the URLs you can use:' mod='art_pricematcher'}</p>
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Cron URL' mod='art_pricematcher'}</label>
                <div class="col-lg-9">
                    <div class="input-group">
                        <input type="text" class="form-control" value="{$cron_url}" readonly="readonly" />
                        <span class="input-group-btn">
                            <button class="btn btn-default copy-to-clipboard" type="button" data-clipboard-text="{$cron_url}">
                                <i class="icon-copy"></i> {l s='Copy' mod='art_pricematcher'}
                            </button>
                        </span>
                    </div>
                    <p class="help-block">{l s='This URL will run the full price matching process for all active competitors.' mod='art_pricematcher'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Cron Command' mod='art_pricematcher'}</label>
                <div class="col-lg-9">
                    <div class="input-group">
                        <input type="text" class="form-control" value="php {$module_dir}cronjob.php token={$cron_token}" readonly="readonly" />
                        <span class="input-group-btn">
                            <button class="btn btn-default copy-to-clipboard" type="button" data-clipboard-text="php {$module_dir}cronjob.php token={$cron_token}">
                                <i class="icon-copy"></i> {l s='Copy' mod='art_pricematcher'}
                            </button>
                        </span>
                    </div>
                    <p class="help-block">{l s='You can also run the cronjob directly using this command.' mod='art_pricematcher'}</p>
                </div>
            </div>
        </div>
    </div>
{/if}

{if isset($statistics) && is_array($statistics)}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-bar-chart"></i> {l s='Statistics' mod='art_pricematcher'}
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="well text-center">
                        <h4>{l s='Total Products' mod='art_pricematcher'}</h4>
                        <h2>{if isset($statistics.total_products)}{$statistics.total_products}{else}0{/if}</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="well text-center">
                        <h4>{l s='Products Compared' mod='art_pricematcher'}</h4>
                        <h2>{if isset($statistics.total_compared)}{$statistics.total_compared}{else}0{/if}</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="well text-center">
                        <h4>{l s='Products Updated' mod='art_pricematcher'}</h4>
                        <h2>{if isset($statistics.total_updated)}{$statistics.total_updated}{else}0{/if}</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="well text-center">
                        <h4>{l s='Active Discounts' mod='art_pricematcher'}</h4>
                        <h2>{if isset($statistics.active_discounts)}{$statistics.active_discounts}{else}0{/if}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}

{if isset($download_results) && is_array($download_results)}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-download"></i> {l s='Download Results' mod='art_pricematcher'}
        </div>
        <div class="panel-body">
            {if isset($download_results.success) && $download_results.success}
                <div class="alert alert-success">
                    <p>{l s='Successfully downloaded prices from' mod='art_pricematcher'} <strong>{$download_results.competitor}</strong></p>
                    <p>{l s='File saved to:' mod='art_pricematcher'} <code>{$download_results.file}</code></p>
                    <p>{l s='Products found:' mod='art_pricematcher'} <strong>{$download_results.products_found}</strong></p>
                    <p>{l s='Execution time:' mod='art_pricematcher'} <strong>{$download_results.execution_time|string_format:"%.2f"}</strong> {l s='seconds' mod='art_pricematcher'}</p>
                </div>
            {else}
                <div class="alert alert-danger">
                    <p>{l s='Failed to download prices from' mod='art_pricematcher'} <strong>{$download_results.competitor}</strong></p>
                    <p>{l s='Error:' mod='art_pricematcher'} <strong>{$download_results.error}</strong></p>
                </div>
            {/if}
        </div>
    </div>
{/if}

{if isset($compare_results) && is_array($compare_results)}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-exchange"></i> {l s='Comparison Results' mod='art_pricematcher'}
        </div>
        <div class="panel-body">
            {if isset($compare_results.success) && $compare_results.success}
                <div class="alert alert-success">
                    <p>{l s='Successfully compared prices for' mod='art_pricematcher'} <strong>{$compare_results.competitor}</strong></p>
                    <p>{l s='Products found:' mod='art_pricematcher'} <strong>{$compare_results.products_found}</strong></p>
                    <p>{l s='Products matched:' mod='art_pricematcher'} <strong>{$compare_results.products_matched}</strong></p>
                    <p>{l s='Products with lower price:' mod='art_pricematcher'} <strong>{$compare_results.products_lower}</strong></p>
                    <p>{l s='Execution time:' mod='art_pricematcher'} <strong>{$compare_results.execution_time|string_format:"%.2f"}</strong> {l s='seconds' mod='art_pricematcher'}</p>
                </div>
            {else}
                <div class="alert alert-danger">
                    <p>{l s='Failed to compare prices for' mod='art_pricematcher'} <strong>{$compare_results.competitor}</strong></p>
                    <p>{l s='Error:' mod='art_pricematcher'} <strong>{$compare_results.error}</strong></p>
                </div>
            {/if}
        </div>
    </div>
{/if}

{if isset($update_results) && is_array($update_results)}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-refresh"></i> {l s='Price Update Results' mod='art_pricematcher'}
        </div>
        <div class="panel-body">
            {if isset($update_results.success) && $update_results.success}
                <div class="alert alert-success">
                    <p>{l s='Successfully updated prices for' mod='art_pricematcher'} <strong>{$update_results.competitor}</strong></p>
                    <p>{l s='Products checked:' mod='art_pricematcher'} <strong>{$update_results.total_checked}</strong></p>
                    <p>{l s='Products updated:' mod='art_pricematcher'} <strong>{$update_results.updated_count}</strong></p>
                    <p>{l s='Products skipped:' mod='art_pricematcher'} <strong>{$update_results.skipped_count}</strong></p>
                    {if isset($update_results.cleaned_discounts)}
                        <p>{l s='Expired discounts cleaned:' mod='art_pricematcher'} <strong>{$update_results.cleaned_discounts}</strong></p>
                    {/if}
                    <p>{l s='Execution time:' mod='art_pricematcher'} <strong>{$update_results.execution_time|string_format:"%.2f"}</strong> {l s='seconds' mod='art_pricematcher'}</p>
                </div>
            {else}
                <div class="alert alert-danger">
                    <p>{l s='Failed to update prices for' mod='art_pricematcher'} <strong>{$update_results.competitor}</strong></p>
                    <p>{l s='Error:' mod='art_pricematcher'} <strong>{$update_results.error}</strong></p>
                </div>
            {/if}
        </div>
    </div>
{/if}

{if isset($clean_expired_discounts_results) && is_array($clean_expired_discounts_results)}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-trash"></i> {l s='Clean Expired Discounts Results' mod='art_pricematcher'}
        </div>
        <div class="panel-body">
            {if isset($clean_expired_discounts_results.success) && $clean_expired_discounts_results.success}
                <div class="alert alert-success">
                    <p>{l s='Successfully cleaned expired discounts' mod='art_pricematcher'}</p>
                    <p>{l s='Discounts removed:' mod='art_pricematcher'} <strong>{$clean_expired_discounts_results.removed_count}</strong></p>
                    <p>{l s='Execution time:' mod='art_pricematcher'} <strong>{$clean_expired_discounts_results.execution_time|string_format:"%.2f"}</strong> {l s='seconds' mod='art_pricematcher'}</p>
                </div>
            {else}
                <div class="alert alert-danger">
                    <p>{l s='Failed to clean expired discounts' mod='art_pricematcher'}</p>
                    <p>{l s='Error:' mod='art_pricematcher'} <strong>{$clean_expired_discounts_results.error}</strong></p>
                </div>
            {/if}
        </div>
    </div>
{/if}