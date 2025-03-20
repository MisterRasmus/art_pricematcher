{* Active Discounts template för Art PriceMatcher modul *}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-tags"></i> {l s='Active Price Matches' mod='art_pricematcher'}
    </div>
    <div class="panel-body">
        <p>{l s='This page shows all active price matches created by the Price Matcher module.' mod='art_pricematcher'}</p>
        
        <div class="alert alert-info">
            <p>
                <i class="icon-info-circle"></i> {l s='Price matches are automatically removed when they expire. You can configure the expiration period in the Settings tab.' mod='art_pricematcher'}
            </p>
        </div>
        
        {* Filter och sökfunktioner *}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="icon-search"></i></span>
                        <input type="text" id="discount-search" class="form-control" placeholder="{l s='Search by product name or reference...' mod='art_pricematcher'}">
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <select id="competitor-filter" class="form-control">
                        <option value="">{l s='All Competitors' mod='art_pricematcher'}</option>
                        {foreach from=$competitors item=competitor}
                            <option value="{$competitor.name}">{$competitor.name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <button type="button" id="refresh-discounts" class="btn btn-primary">
                    <i class="icon-refresh"></i> {l s='Refresh' mod='art_pricematcher'}
                </button>
                <button type="button" id="clean-expired" class="btn btn-default">
                    <i class="icon-trash"></i> {l s='Clean Expired' mod='art_pricematcher'}
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="discount-table">
                <thead>
                    <tr>
                        <th data-sort="int">{l s='ID' mod='art_pricematcher'} <i class="icon-sort"></i></th>
                        <th data-sort="string">{l s='Product' mod='art_pricematcher'} <i class="icon-sort"></i></th>
                        <th data-sort="string">{l s='Reference' mod='art_pricematcher'} <i class="icon-sort"></i></th>
                        <th data-sort="string">{l s='Competitor' mod='art_pricematcher'} <i class="icon-sort"></i></th>
                        <th data-sort="float">{l s='Original Price' mod='art_pricematcher'} <i class="icon-sort"></i></th>
                        <th data-sort="float">{l s='Competitor Price' mod='art_pricematcher'} <i class="icon-sort"></i></th>
                        <th data-sort="float">{l s='New Price' mod='art_pricematcher'} <i class="icon-sort"></i></th>
                        <th data-sort="float">{l s='Discount' mod='art_pricematcher'} <i class="icon-sort"></i></th>
                        <th data-sort="date">{l s='Created' mod='art_pricematcher'} <i class="icon-sort"></i></th>
                        <th data-sort="date">{l s='Expires' mod='art_pricematcher'} <i class="icon-sort"></i></th>
                        <th>{l s='Actions' mod='art_pricematcher'}</th>
                    </tr>
                </thead>
                <tbody>
                    {if isset($active_discounts) && $active_discounts|@count > 0}
                        {foreach from=$active_discounts item=discount}
                            <tr data-id="{$discount.id_active_discount}" data-competitor="{$discount.competitor_name}" data-product="{$discount.product_name}" data-reference="{$discount.reference}">
                                <td>{$discount.id_active_discount}</td>
                                <td>
                                    <a href="{$link->getAdminLink('AdminProducts', true, ['id_product' => $discount.id_product, 'updateproduct' => ''])}" target="_blank">
                                        {$discount.product_name|truncate:40:"..."}
                                    </a>
                                </td>
                                <td>{$discount.reference}</td>
                                <td>{$discount.competitor_name}</td>
                                <td data-sort-value="{$discount.original_price}">{$discount.original_price_formatted}</td>
                                <td data-sort-value="{$discount.competitor_price}">{$discount.competitor_price_formatted}</td>
                                <td data-sort-value="{$discount.new_price}">{$discount.new_price_formatted}</td>
                                <td data-sort-value="{$discount.discount_percent}">
                                    <span class="badge {if $discount.discount_percent > 10}badge-danger{elseif $discount.discount_percent > 5}badge-warning{else}badge-success{/if}">
                                        {$discount.discount_formatted}
                                    </span>
                                </td>
                                <td data-sort-value="{$discount.date_add_timestamp}">{$discount.date_add_formatted}</td>
                                <td data-sort-value="{$discount.date_expiration_timestamp}">
                                    <span class="{if $discount.days_left <= 2}text-danger{elseif $discount.days_left <= 5}text-warning{else}text-success{/if}">
                                        {$discount.date_expiration_formatted} 
                                        {if $discount.days_left > 0}
                                            ({$discount.days_left} {if $discount.days_left == 1}{l s='day' mod='art_pricematcher'}{else}{l s='days' mod='art_pricematcher'}{/if})
                                        {else}
                                            ({l s='Today' mod='art_pricematcher'})
                                        {/if}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm extend-discount" data-id="{$discount.id_active_discount}" data-toggle="tooltip" title="{l s='Extend by 7 days' mod='art_pricematcher'}">
                                            <i class="icon-calendar"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm remove-discount" data-id="{$discount.id_active_discount}" data-toggle="tooltip" title="{l s='Remove discount' mod='art_pricematcher'}">
                                            <i class="icon-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr class="no-discounts">
                            <td colspan="11" class="text-center">{l s='No active price matches found' mod='art_pricematcher'}</td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>
        
        {* Paginering *}
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
                        
                        {for $page=1 to $pagination.total_pages}
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

{* Bekräftelsemodal för borttagning *}
<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="confirm-delete-label">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="confirm-delete-label">{l s='Confirm Removal' mod='art_pricematcher'}</h4>
            </div>
            <div class="modal-body">
                <p>{l s='Are you sure you want to remove this price match? This will return the product to its original price.' mod='art_pricematcher'}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Cancel' mod='art_pricematcher'}</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-button">{l s='Remove' mod='art_pricematcher'}</button>
            </div>
        </div>
    </div>
</div>