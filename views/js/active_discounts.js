/**
 * JavaScript för Active Discounts-fliken i Art PriceMatcher-modulen
 */
$(document).ready(function() {
    // Initiera tabellen med sortering
    initTable();
    
    // Hantera filtrering
    handleFiltering();
    
    // Hantera borttagning av rabatter
    handleDiscountRemoval();
    
    // Hantera förlängning av rabatter
    handleDiscountExtension();
    
    // Hantera rensning av utgångna rabatter
    handleCleanExpired();
    
    // Initiera tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

/**
 * Initiera datatabellen med sorteringsfunktionalitet
 */
function initTable() {
    var discountTable = $('#discount-table');
    
    // Sorteringsfunktion för datatabellen
    discountTable.find('th').click(function() {
        var table = $(this).parents('table').eq(0);
        var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()));
        this.asc = !this.asc;
        
        if (!this.asc) {
            rows = rows.reverse();
        }
        
        // Uppdatera ikonstilen för att visa sorteringsriktning
        table.find('th i').removeClass('icon-sort-down icon-sort-up').addClass('icon-sort');
        $(this).find('i').removeClass('icon-sort').addClass(this.asc ? 'icon-sort-up' : 'icon-sort-down');
        
        // Lägg till raderna i tabellen igen i sorterad ordning
        for (var i = 0; i < rows.length; i++) {
            table.append(rows[i]);
        }
    });
    
    // Sortera på utgångsdatum som standard (ascending)
    discountTable.find('th:nth-child(10)').click();
}

/**
 * Jämförelsefunktion för tabellsortering
 */
function comparer(index) {
    return function(a, b) {
        var valA = getCellValue(a, index);
        var valB = getCellValue(b, index);
        
        // Använd data-sort-value om det finns, annars använd cellens text
        var $cellA = $(a).children('td').eq(index);
        var $cellB = $(b).children('td').eq(index);
        
        if ($cellA.attr('data-sort-value')) {
            valA = $cellA.attr('data-sort-value');
        }
        
        if ($cellB.attr('data-sort-value')) {
            valB = $cellB.attr('data-sort-value');
        }
        
        // Avgör sorteringstyp baserat på th-attribut
        var sortType = $('#discount-table th').eq(index).attr('data-sort') || 'string';
        
        // Jämför baserat på datatyperna
        switch (sortType) {
            case 'int':
                return parseInt(valA) - parseInt(valB);
            case 'float':
                return parseFloat(valA) - parseFloat(valB);
            case 'date':
                return new Date(valA) - new Date(valB);
            default:
                return valA.localeCompare(valB);
        }
    };
}

/**
 * Hämta cellvärde för sortering
 */
function getCellValue(row, index) {
    return $(row).children('td').eq(index).text();
}

/**
 * Hantera filtrering av tabellen
 */
function handleFiltering() {
    // Sökfilter
    $('#discount-search').on('keyup', function() {
        var searchText = $(this).val().toLowerCase();
        filterTable(searchText, $('#competitor-filter').val());
    });
    
    // Konkurrentfilter
    $('#competitor-filter').on('change', function() {
        var competitorName = $(this).val();
        filterTable($('#discount-search').val().toLowerCase(), competitorName);
    });
    
    // Uppdateringsknapp
    $('#refresh-discounts').on('click', function() {
        refreshDiscounts();
    });
}

/**
 * Filtrera tabellen baserat på sökterm och konkurrent
 */
function filterTable(searchText, competitorName) {
    $('#discount-table tbody tr').each(function() {
        var row = $(this);
        
        // Hoppa över raden om det är "inga rabatter hittades"
        if (row.hasClass('no-discounts')) {
            return;
        }
        
        var productName = row.data('product').toLowerCase();
        var reference = row.data('reference').toLowerCase();
        var competitor = row.data('competitor');
        
        // Kontrollera om raden matchar söktexten
        var matchSearch = searchText === '' || 
                         productName.indexOf(searchText) > -1 || 
                         reference.indexOf(searchText) > -1;
        
        // Kontrollera om raden matchar konkurrentfiltret
        var matchCompetitor = competitorName === '' || competitor === competitorName;
        
        // Visa/dölj raden baserat på filtreringsresultat
        if (matchSearch && matchCompetitor) {
            row.show();
        } else {
            row.hide();
        }
    });
    
    // Kontrollera om några rader är synliga
    if ($('#discount-table tbody tr:visible').length === 0) {
        // Om ingen "ingen rabatter"-rad finns, skapa en
        if ($('#discount-table tbody tr.no-discounts').length === 0) {
            $('#discount-table tbody').append('<tr class="no-discounts"><td colspan="11" class="text-center">' + 
                                           'No matching discounts found</td></tr>');
        } else {
            $('#discount-table tbody tr.no-discounts').show();
        }
    } else {
        // Dölj "inga rabatter"-raden om den finns
        $('#discount-table tbody tr.no-discounts').hide();
    }
}

/**
 * Uppdatera rabattlistan med AJAX
 */
function refreshDiscounts() {
    var competitorName = $('#competitor-filter').val();
    var searchText = $('#discount-search').val();
    
    // Visa en laddningsspinner
    $('#refresh-discounts').html('<i class="icon-refresh icon-spin"></i>');
    
    // Skicka AJAX-förfrågan
    $.ajax({
        url: currentIndex + '&token=' + token + '&ajax=1',
        type: 'POST',
        data: {
            controller: 'AdminPriceMatcherController',
            action: 'getFilteredDiscounts',
            tab: 'active_discounts',
            competitor: competitorName,
            search: searchText,
            page: 1
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateTable(response.discounts);
                updatePagination(response.pagination);
            } else {
                showErrorMessage(response.message || 'Failed to refresh discounts.');
            }
        },
        error: function() {
            showErrorMessage('An error occurred while refreshing discounts.');
        },
        complete: function() {
            // Återställ knapptext
            $('#refresh-discounts').html('<i class="icon-refresh"></i> Refresh');
        }
    });
}

/**
 * Uppdatera tabellen med nya data
 */
function updateTable(discounts) {
    var tbody = $('#discount-table tbody');
    tbody.empty();
    
    if (discounts.length === 0) {
        tbody.append('<tr class="no-discounts"><td colspan="11" class="text-center">No active price matches found</td></tr>');
        return;
    }
    
    // Lägg till de nya rabatterna
    for (var i = 0; i < discounts.length; i++) {
        var discount = discounts[i];
        var row = $('<tr></tr>')
            .attr('data-id', discount.id_active_discount)
            .attr('data-competitor', discount.competitor_name)
            .attr('data-product', discount.product_name)
            .attr('data-reference', discount.reference);
        
        // Skapa alla celler
        row.append('<td>' + discount.id_active_discount + '</td>');
        
        // Produktnamn med länk
        var productLink = '<td><a href="' + adminProductLink + '&id_product=' + discount.id_product + '&updateproduct" target="_blank">';
        productLink += discount.product_name + '</a></td>';
        row.append(productLink);
        
        row.append('<td>' + discount.reference + '</td>');
        row.append('<td>' + discount.competitor_name + '</td>');
        
        // Prisceller med data-sort-value
        row.append('<td data-sort-value="' + discount.original_price + '">' + discount.original_price_formatted + '</td>');
        row.append('<td data-sort-value="' + discount.competitor_price + '">' + discount.competitor_price_formatted + '</td>');
        row.append('<td data-sort-value="' + discount.new_price + '">' + discount.new_price_formatted + '</td>');
        
        // Rabatt med färgkod
        var discountClass = discount.discount_percent > 10 ? 'badge-danger' : 
                           (discount.discount_percent > 5 ? 'badge-warning' : 'badge-success');
        row.append('<td data-sort-value="' + discount.discount_percent + '"><span class="badge ' + discountClass + '">' + 
                  discount.discount_formatted + '</span></td>');
        
        // Datumceller med data-sort-value
        row.append('<td data-sort-value="' + discount.date_add_timestamp + '">' + discount.date_add_formatted + '</td>');
        
        // Utgångsdatum med färgkodning
        var expirationClass = discount.days_left <= 2 ? 'text-danger' : 
                             (discount.days_left <= 5 ? 'text-warning' : 'text-success');
        var daysLeftText = discount.days_left > 0 ? '(' + discount.days_left + ' days)' : '(Today)';
        
        row.append('<td data-sort-value="' + discount.date_expiration_timestamp + '">' +
                  '<span class="' + expirationClass + '">' + discount.date_expiration_formatted + ' ' + daysLeftText + '</span></td>');
        
        // Åtgärdsknappar
        var actions = '<td class="text-center"><div class="btn-group">' +
                      '<button type="button" class="btn btn-default btn-sm extend-discount" data-id="' + discount.id_active_discount + '" data-toggle="tooltip" title="Extend by 7 days">' +
                      '<i class="icon-calendar"></i></button>' +
                      '<button type="button" class="btn btn-danger btn-sm remove-discount" data-id="' + discount.id_active_discount + '" data-toggle="tooltip" title="Remove discount">' +
                      '<i class="icon-trash"></i></button>' +
                      '</div></td>';
        row.append(actions);
        
        tbody.append(row);
    }
    
    // Återinitiera sortering och tooltips
    initTable();
    $('[data-toggle="tooltip"]').tooltip();
}

/**
 * Uppdatera pagineringen med nya data
 */
function updatePagination(pagination) {
    if (!pagination) {
        return;
    }
    
    // Uppdatera text om antal visade poster
    var paginationInfo = 'Showing ' + pagination.start + ' to ' + pagination.end + ' of ' + pagination.total + ' entries';
    $('.pagination-info').text(paginationInfo);
    
    // Uppdatera pagineringsknappar om de finns
    if ($('.pagination').length) {
        var paginationHtml = '';
        
        // Föregående-knapp
        paginationHtml += '<li class="' + (pagination.current_page == 1 ? 'disabled' : '') + '">';
        paginationHtml += '<a href="' + (pagination.current_page > 1 ? '#" onclick="changePage(' + (pagination.current_page - 1) + '); return false;' : '#') + '">';
        paginationHtml += '<i class="icon-angle-left"></i></a></li>';
        
        // Sidnummerknappar
        for (var i = 1; i <= pagination.total_pages; i++) {
            paginationHtml += '<li class="' + (pagination.current_page == i ? 'active' : '') + '">';
            paginationHtml += '<a href="#" onclick="changePage(' + i + '); return false;">' + i + '</a></li>';
        }
        
        // Nästa-knapp
        paginationHtml += '<li class="' + (pagination.current_page == pagination.total_pages ? 'disabled' : '') + '">';
        paginationHtml += '<a href="' + (pagination.current_page < pagination.total_pages ? '#" onclick="changePage(' + (pagination.current_page + 1) + '); return false;' : '#') + '">';
        paginationHtml += '<i class="icon-angle-right"></i></a></li>';
        
        $('.pagination').html(paginationHtml);
    }
}

/**
 * Byta sida vid paginering
 */
function changePage(page) {
    // Här kan man implementera mer avancerad paginering via AJAX
    // För nu använder vi bara omladdning av sidan med page-parameter
    var url = window.location.href;
    
    // Ta bort eventuell befintlig page-parameter
    url = url.replace(/&page=\d+/, '');
    
    // Lägg till den nya page-parametern
    url += url.indexOf('?') > -1 ? '&page=' + page : '?page=' + page;
    
    window.location.href = url;
}

/**
 * Hantera borttagning av rabatter
 */
function handleDiscountRemoval() {
    // Visa bekräftelsedialog vid klick på borttagningsknappen
    $(document).on('click', '.remove-discount', function() {
        var discountId = $(this).data('id');
        $('#confirm-delete').data('id', discountId).modal('show');
    });
    
    // Bekräfta borttagningen
    $('#confirm-delete-button').on('click', function() {
        var discountId = $('#confirm-delete').data('id');
        removeDiscount(discountId);
    });
}

/**
 * Ta bort rabatt via AJAX
 */
function removeDiscount(discountId) {
    $.ajax({
        url: currentIndex + '&token=' + token + '&ajax=1',
        type: 'POST',
        data: {
            controller: 'AdminPriceMatcherController',
            action: 'removeDiscount',
            tab: 'active_discounts',
            id_discount: discountId
        },
        dataType: 'json',
        success: function(response) {
            $('#confirm-delete').modal('hide');
            
            if (response.success) {
                // Ta bort raden från tabellen
                $('tr[data-id="' + discountId + '"]').fadeOut(400, function() {
                    $(this).remove();
                    
                    // Kontrollera om tabellen är tom
                    if ($('#discount-table tbody tr').length === 0) {
                        $('#discount-table tbody').append('<tr class="no-discounts"><td colspan="11" class="text-center">' + 
                                                        'No active price matches found</td></tr>');
                    }
                });
                
                showSuccessMessage(response.message || 'Discount successfully removed.');
            } else {
                showErrorMessage(response.message || 'Failed to remove discount.');
            }
        },
        error: function() {
            $('#confirm-delete').modal('hide');
            showErrorMessage('An error occurred while removing the discount.');
        }
    });
}

/**
 * Hantera förlängning av rabatter
 */
function handleDiscountExtension() {
    $(document).on('click', '.extend-discount', function() {
        var discountId = $(this).data('id');
        var days = 7; // Standard är 7 dagar
        
        extendDiscount(discountId, days);
    });
}

/**
 * Förläng rabatt via AJAX
 */
function extendDiscount(discountId, days) {
    $.ajax({
        url: currentIndex + '&token=' + token + '&ajax=1',
        type: 'POST',
        data: {
            controller: 'AdminPriceMatcherController',
            action: 'extendDiscount',
            tab: 'active_discounts',
            id_discount: discountId,
            days: days
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Uppdatera hela tabellen för att få rätt datum
                refreshDiscounts();
                
                showSuccessMessage(response.message || 'Discount extended successfully.');
            } else {
                showErrorMessage(response.message || 'Failed to extend discount.');
            }
        },
        error: function() {
            showErrorMessage('An error occurred while extending the discount.');
        }
    });
}

/**
 * Hantera rensning av utgångna rabatter
 */
function handleCleanExpired() {
    $('#clean-expired').on('click', function() {
        if (confirm('Are you sure you want to remove all expired discounts?')) {
            cleanExpiredDiscounts();
        }
    });
}

/**
 * Rensa utgångna rabatter via AJAX
 */
function cleanExpiredDiscounts() {
    // Visa en laddningsspinner
    $('#clean-expired').html('<i class="icon-refresh icon-spin"></i>');
    
    $.ajax({
        url: currentIndex + '&token=' + token + '&ajax=1',
        type: 'POST',
        data: {
            controller: 'AdminPriceMatcherController',
            action: 'cleanExpiredDiscounts',
            tab: 'active_discounts'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                refreshDiscounts();
                showSuccessMessage(response.message || 'Expired discounts cleaned successfully.');
            } else {
                showErrorMessage(response.message || 'Failed to clean expired discounts.');
            }
        },
        error: function() {
            showErrorMessage('An error occurred while cleaning expired discounts.');
        },
        complete: function() {
            // Återställ knapptext
            $('#clean-expired').html('<i class="icon-trash"></i> Clean Expired');
        }
    });
}

/**
 * Visa framgångsmeddelande
 */
function showSuccessMessage(message) {
    $.growl.notice({ message: message });
}

/**
 * Visa felmeddelande
 */
function showErrorMessage(message) {
    $.growl.error({ message: message });
}