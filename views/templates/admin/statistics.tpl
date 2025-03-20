{* Statistics template för Art PriceMatcher modul *}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-bar-chart"></i> {l s='Price Matching Statistics' mod='art_pricematcher'}
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3">
                <div class="panel widget-stats">
                    <div class="panel-heading bg-success">
                        <h4 class="text-center">{l s='Total Products' mod='art_pricematcher'}</h4>
                    </div>
                    <div class="panel-body">
                        <h2 class="text-center">{if isset($statistics.total_products)}{$statistics.total_products}{else}0{/if}</h2>
                    </div>
                    <div class="panel-footer">
                        <small>{l s='Active products in store' mod='art_pricematcher'}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel widget-stats">
                    <div class="panel-heading bg-info">
                        <h4 class="text-center">{l s='Products Compared' mod='art_pricematcher'}</h4>
                    </div>
                    <div class="panel-body">
                        <h2 class="text-center">{if isset($statistics.total_compared)}{$statistics.total_compared}{else}0{/if}</h2>
                    </div>
                    <div class="panel-footer">
                        <small>{l s='Products found in competitor data' mod='art_pricematcher'}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel widget-stats">
                    <div class="panel-heading bg-warning">
                        <h4 class="text-center">{l s='Products Updated' mod='art_pricematcher'}</h4>
                    </div>
                    <div class="panel-body">
                        <h2 class="text-center">{if isset($statistics.total_updated)}{$statistics.total_updated}{else}0{/if}</h2>
                    </div>
                    <div class="panel-footer">
                        <small>{l s='Total products with price changes' mod='art_pricematcher'}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel widget-stats">
                    <div class="panel-heading bg-danger">
                        <h4 class="text-center">{l s='Active Discounts' mod='art_pricematcher'}</h4>
                    </div>
                    <div class="panel-body">
                        <h2 class="text-center">{if isset($statistics.active_discounts)}{$statistics.active_discounts}{else}0{/if}</h2>
                    </div>
                    <div class="panel-footer">
                        <small>{l s='Current active price matches' mod='art_pricematcher'}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{* Trend-grafer för de senaste 30 dagarna *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-line-chart"></i> {l s='Price Matching Trends (30 Days)' mod='art_pricematcher'}
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#tab-price-updates" aria-controls="tab-price-updates" role="tab" data-toggle="tab">
                            {l s='Price Updates' mod='art_pricematcher'}
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tab-discount-distribution" aria-controls="tab-discount-distribution" role="tab" data-toggle="tab">
                            {l s='Discount Distribution' mod='art_pricematcher'}
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tab-competitor-comparison" aria-controls="tab-competitor-comparison" role="tab" data-toggle="tab">
                            {l s='Competitor Comparison' mod='art_pricematcher'}
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tab-price-drop-categories" aria-controls="tab-price-drop-categories" role="tab" data-toggle="tab">
                            {l s='Price Drops by Category' mod='art_pricematcher'}
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="tab-price-updates">
                        <div class="chart-container" style="position: relative; height:400px;">
                            <canvas id="priceUpdatesChart"></canvas>
                        </div>
                    </div>
                    
                    <div role="tabpanel" class="tab-pane" id="tab-discount-distribution">
                        <div class="chart-container" style="position: relative; height:400px;">
                            <canvas id="discountDistributionChart"></canvas>
                        </div>
                    </div>
                    
                    <div role="tabpanel" class="tab-pane" id="tab-competitor-comparison">
                        <div class="chart-container" style="position: relative; height:400px;">
                            <canvas id="competitorComparisonChart"></canvas>
                        </div>
                    </div>
                    
                    <div role="tabpanel" class="tab-pane" id="tab-price-drop-categories">
                        <div class="chart-container" style="position: relative; height:400px;">
                            <canvas id="categoryDiscountChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{* Competitor Statistics *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-users"></i> {l s='Competitor Statistics' mod='art_pricematcher'}
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="competitor-stats-table">
                <thead>
                    <tr>
                        <th>{l s='Competitor' mod='art_pricematcher'}</th>
                        <th>{l s='Products in List' mod='art_pricematcher'}</th>
                        <th>{l s='Products Matched' mod='art_pricematcher'}</th>
                        <th>{l s='Match Rate' mod='art_pricematcher'}</th>
                        <th>{l s='Lower Prices' mod='art_pricematcher'}</th>
                        <th>{l s='Higher Prices' mod='art_pricematcher'}</th>
                        <th>{l s='Avg. Price Difference' mod='art_pricematcher'}</th>
                        <th>{l s='Details' mod='art_pricematcher'}</th>
                    </tr>
                </thead>
                <tbody>
                    {if isset($competitor_stats) && $competitor_stats|@count > 0}
                        {foreach from=$competitor_stats item=stat}
                            <tr>
                                <td>{$stat.name}</td>
                                <td>{$stat.total_products|intval}</td>
                                <td>{$stat.products_matched|intval}</td>
                                <td>
                                    <div class="progress" style="margin-bottom: 0;">
                                        <div class="progress-bar" role="progressbar" 
                                            aria-valuenow="{$stat.match_rate}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100" 
                                            style="width: {$stat.match_rate}%;">
                                            {$stat.match_rate}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-danger">{$stat.lower_prices|intval}</span>
                                </td>
                                <td>
                                    <span class="badge badge-success">{$stat.higher_prices|intval}</span>
                                </td>
                                <td>
                                    {if $stat.avg_price_diff < 0}
                                        <span class="text-danger">{$stat.avg_price_diff|string_format:"%.2f"}%</span>
                                    {else}
                                        <span class="text-success">+{$stat.avg_price_diff|string_format:"%.2f"}%</span>
                                    {/if}
                                </td>
                                <td>
                                    <button type="button" class="btn btn-default btn-xs view-competitor-details" data-id="{$stat.id_competitor}">
                                        <i class="icon-search"></i> {l s='View Details' mod='art_pricematcher'}
                                    </button>
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="8" class="text-center">{l s='No competitor statistics available' mod='art_pricematcher'}</td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>
    </div>
</div>

{* Recent Operations Table *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-history"></i> {l s='Recent Operations' mod='art_pricematcher'}
    </div>
    <div class="panel-body">
        <div class="row mb-2">
            <div class="col-md-4">
                <select id="operation-filter" class="form-control">
                    <option value="">{l s='All Operations' mod='art_pricematcher'}</option>
                    <option value="download">{l s='Download' mod='art_pricematcher'}</option>
                    <option value="compare">{l s='Compare' mod='art_pricematcher'}</option>
                    <option value="update">{l s='Update' mod='art_pricematcher'}</option>
                    <option value="clean">{l s='Clean Expired' mod='art_pricematcher'}</option>
                </select>
            </div>
            <div class="col-md-4">
                <select id="competitor-filter" class="form-control">
                    <option value="">{l s='All Competitors' mod='art_pricematcher'}</option>
                    {foreach from=$competitors item=competitor}
                        <option value="{$competitor.id_competitor}">{$competitor.name}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" id="date-range" class="form-control" placeholder="{l s='Date Range' mod='art_pricematcher'}" readonly />
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="button" id="reset-filter">
                            <i class="icon-refresh"></i> {l s='Reset' mod='art_pricematcher'}
                        </button>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="operations-table">
                <thead>
                    <tr>
                        <th>{l s='Date' mod='art_pricematcher'}</th>
                        <th>{l s='Operation' mod='art_pricematcher'}</th>
                        <th>{l s='Competitor' mod='art_pricematcher'}</th>
                        <th>{l s='Products Processed' mod='art_pricematcher'}</th>
                        <th>{l s='Success Rate' mod='art_pricematcher'}</th>
                        <th>{l s='Duration' mod='art_pricematcher'}</th>
                        <th>{l s='Initiated By' mod='art_pricematcher'}</th>
                        <th>{l s='Details' mod='art_pricematcher'}</th>
                    </tr>
                </thead>
                <tbody>
                    {if isset($operations) && $operations|@count > 0}
                        {foreach from=$operations item=op}
                            <tr>
                                <td>{$op.execution_date|date_format:"%Y-%m-%d %H:%M:%S"}</td>
                                <td>
                                    {if $op.operation_type == 'download'}
                                        <span class="label label-info">{l s='Download' mod='art_pricematcher'}</span>
                                    {elseif $op.operation_type == 'compare'}
                                        <span class="label label-primary">{l s='Compare' mod='art_pricematcher'}</span>
                                    {elseif $op.operation_type == 'update'}
                                        <span class="label label-success">{l s='Update' mod='art_pricematcher'}</span>
                                    {elseif $op.operation_type == 'clean'}
                                        <span class="label label-warning">{l s='Clean Expired' mod='art_pricematcher'}</span>
                                    {/if}
                                </td>
                                <td>{$op.competitor_name}</td>
                                <td>{$op.products_processed|intval}</td>
                                <td>
                                    <div class="progress" style="margin-bottom: 0;">
                                        <div class="progress-bar" role="progressbar" 
                                            aria-valuenow="{$op.success_rate}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100" 
                                            style="width: {$op.success_rate}%;">
                                            {$op.success_rate}%
                                        </div>
                                    </div>
                                </td>
                                <td>{$op.execution_time|string_format:"%.2f"} {l s='seconds' mod='art_pricematcher'}</td>
                                <td>
                                    {if $op.initiated_by == 'cron'}
                                        <span class="label label-default">{l s='Cron' mod='art_pricematcher'}</span>
                                    {elseif $op.initiated_by == 'manual'}
                                        <span class="label label-primary">{l s='Manual' mod='art_pricematcher'}</span>
                                    {/if}
                                </td>
                                <td>
                                    <button type="button" class="btn btn-default btn-xs view-operation-details" data-id="{$op.id_statistic}">
                                        <i class="icon-search"></i> {l s='View Details' mod='art_pricematcher'}
                                    </button>
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="8" class="text-center">{l s='No operations recorded' mod='art_pricematcher'}</td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>
        
        {* Pagination *}
        {if isset($pagination)}
            <div class="row">
                <div class="col-md-6">
                    <p>{l s='Showing' mod='art_pricematcher'} {$pagination.start} {l s='to' mod='art_pricematcher'} {$pagination.end} {l s='of' mod='art_pricematcher'} {$pagination.total} {l s='entries' mod='art_pricematcher'}</p>
                </div>
                <div class="col-md-6 text-right">
                    <ul class="pagination">
                        <li {if $pagination.current_page == 1}class="disabled"{/if}>
                            <a href="{if $pagination.current_page > 1}{$pagination.pagination_link}&page={$pagination.current_page-1}{else}#{/if}">
                                <i class="icon-angle-left"></i>
                            </a>
                        </li>
                        
                        {for $page=max(1, $pagination.current_page-2) to min($pagination.total_pages, $pagination.current_page+2)}
                            <li {if $pagination.current_page == $page}class="active"{/if}>
                                <a href="{$pagination.pagination_link}&page={$page}">{$page}</a>
                            </li>
                        {/for}
                        
                        <li {if $pagination.current_page == $pagination.total_pages}class="disabled"{/if}>
                            <a href="{if $pagination.current_page < $pagination.total_pages}{$pagination.pagination_link}&page={$pagination.current_page+1}{else}#{/if}">
                                <i class="icon-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        {/if}
    </div>
</div>

{* Competitor Details Modal *}
<div class="modal fade" id="competitor-details-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{l s='Competitor Details' mod='art_pricematcher'}</h4>
            </div>
            <div class="modal-body">
                <div class="text-center loading-content">
                    <i class="icon-refresh icon-spin"></i> {l s='Loading competitor data...' mod='art_pricematcher'}
                </div>
                <div class="competitor-details-content" style="display: none;">
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#modal-tab-summary" aria-controls="modal-tab-summary" role="tab" data-toggle="tab">
                                {l s='Summary' mod='art_pricematcher'}
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#modal-tab-trends" aria-controls="modal-tab-trends" role="tab" data-toggle="tab">
                                {l s='Price Trends' mod='art_pricematcher'}
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#modal-tab-categories" aria-controls="modal-tab-categories" role="tab" data-toggle="tab">
                                {l s='Categories' mod='art_pricematcher'}
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#modal-tab-top-products" aria-controls="modal-tab-top-products" role="tab" data-toggle="tab">
                                {l s='Top Products' mod='art_pricematcher'}
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active" id="modal-tab-summary">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="panel">
                                        <div class="panel-heading bg-info">
                                            <h4>{l s='Basic Information' mod='art_pricematcher'}</h4>
                                        </div>
                                        <div class="panel-body">
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <th>{l s='Name' mod='art_pricematcher'}</th>
                                                        <td id="competitor-name"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>{l s='Total Products' mod='art_pricematcher'}</th>
                                                        <td id="competitor-products"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>{l s='Match Rate' mod='art_pricematcher'}</th>
                                                        <td id="competitor-match-rate"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>{l s='Last Price List Update' mod='art_pricematcher'}</th>
                                                        <td id="competitor-last-update"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="panel">
                                        <div class="panel-heading bg-info">
                                            <h4>{l s='Price Comparison' mod='art_pricematcher'}</h4>
                                        </div>
                                        <div class="panel-body">
                                            <div class="chart-container" style="position: relative; height:250px;">
                                                <canvas id="competitor-price-comparison-chart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="modal-tab-trends">
                            <div class="chart-container" style="position: relative; height:400px;">
                                <canvas id="competitor-trends-chart"></canvas>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="modal-tab-categories">
                            <div class="chart-container" style="position: relative; height:400px;">
                                <canvas id="competitor-categories-chart"></canvas>
                            </div>
                        </div>
                        
                        <div role="tabpanel" class="tab-pane" id="modal-tab-top-products">
                            <div class="table-responsive">
                                <table class="table table-striped" id="top-products-table">
                                    <thead>
                                        <tr>
                                            <th>{l s='Product' mod='art_pricematcher'}</th>
                                            <th>{l s='Category' mod='art_pricematcher'}</th>
                                            <th>{l s='Our Price' mod='art_pricematcher'}</th>
                                            <th>{l s='Competitor Price' mod='art_pricematcher'}</th>
                                            <th>{l s='Difference' mod='art_pricematcher'}</th>
                                            <th>{l s='Last Updated' mod='art_pricematcher'}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="6" class="text-center">{l s='Loading products...' mod='art_pricematcher'}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Close' mod='art_pricematcher'}</button>
            </div>
        </div>
    </div>
</div>

{* Operation Details Modal *}
<div class="modal fade" id="operation-details-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{l s='Operation Details' mod='art_pricematcher'}</h4>
            </div>
            <div class="modal-body">
                <div class="text-center loading-content">
                    <i class="icon-refresh icon-spin"></i> {l s='Loading operation data...' mod='art_pricematcher'}
                </div>
                <div class="operation-details-content" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="panel">
                                <div class="panel-heading bg-info">
                                    <h4>{l s='Operation Summary' mod='art_pricematcher'}</h4>
                                </div>
                                <div class="panel-body">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <th>{l s='Operation Type' mod='art_pricematcher'}</th>
                                                <td id="operation-type"></td>
                                            </tr>
                                            <tr>
                                                <th>{l s='Competitor' mod='art_pricematcher'}</th>
                                                <td id="operation-competitor"></td>
                                            </tr>
                                            <tr>
                                                <th>{l s='Execution Date' mod='art_pricematcher'}</th>
                                                <td id="operation-date"></td>
                                            </tr>
                                            <tr>
                                                <th>{l s='Execution Time' mod='art_pricematcher'}</th>
                                                <td id="operation-time"></td>
                                            </tr>
                                            <tr>
                                                <th>{l s='Initiated By' mod='art_pricematcher'}</th>
                                                <td id="operation-initiator"></td>
                                            </tr>
                                            <tr>
                                                <th>{l s='Products Processed' mod='art_pricematcher'}</th>
                                                <td id="operation-products"></td>
                                            </tr>
                                            <tr>
                                                <th>{l s='Success Rate' mod='art_pricematcher'}</th>
                                                <td id="operation-success-rate"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="panel">
                                <div class="panel-heading bg-info">
                                    <h4>{l s='Results Breakdown' mod='art_pricematcher'}</h4>
                                </div>
                                <div class="panel-body">
                                    <div class="chart-container" style="position: relative; height:250px;">
                                        <canvas id="operation-results-chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="panel">
                        <div class="panel-heading bg-info">
                            <h4>{l s='Operation Log' mod='art_pricematcher'}</h4>
                        </div>
                        <div class="panel-body">
                            <pre id="operation-log" style="max-height: 250px; overflow-y: auto;"></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Close' mod='art_pricematcher'}</button>
            </div>
        </div>
    </div>
</div>

<style>
.widget-stats {
    margin-bottom: 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.widget-stats .panel-footer {
    padding: 5px 15px;
    background-color: #f8f8f8;
    border-top: 1px solid #ddd;
    color: #777;
}
.bg-success {
    background-color: #dff0d8;
}
.bg-info {
    background-color: #d9edf7;
}
.bg-warning {
    background-color: #fcf8e3;
}
.bg-danger {
    background-color: #f2dede;
}
.tab-content {
    padding: 20px 15px;
    border-left: 1px solid #ddd;
    border-right: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}
.mb-2 {
    margin-bottom: 15px;
}
.chart-container {
    min-height: 300px;
    padding: 10px;
}
</style>