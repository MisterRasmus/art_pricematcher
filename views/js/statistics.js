/**
 * JavaScript för Statistics-fliken i Art PriceMatcher-modulen
 */
$(document).ready(function() {
    // Initiera datumväljare för filterintering
    initDateRangePicker();
    
    // Initiera alla grafer
    initCharts();
    
    // Hantera tabbar och bevara aktiv flik
    maintainActiveTab();
    
    // Hantera filtreringar
    handleFiltering();
    
    // Hantera modaler för detaljer
    handleDetailModals();
});

/**
 * Initiera datumväljare för filtrering
 */
function initDateRangePicker() {
    if ($.fn.daterangepicker) {
        $('#date-range').daterangepicker({
            ranges: {
               'Idag': [moment(), moment()],
               'Igår': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               'Senaste 7 dagarna': [moment().subtract(6, 'days'), moment()],
               'Senaste 30 dagarna': [moment().subtract(29, 'days'), moment()],
               'Denna månad': [moment().startOf('month'), moment().endOf('month')],
               'Förra månaden': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            startDate: moment().subtract(29, 'days'),
            endDate: moment(),
            locale: {
                format: 'YYYY-MM-DD'
            }
        });
    }
}

/**
 * Initiera alla grafer
 */
function initCharts() {
    initPriceUpdatesChart();
    initDiscountDistributionChart();
    initCompetitorComparisonChart();
    initCategoryDiscountChart();
}

/**
 * Initiera grafen för prisutveckling över tid
 */
function initPriceUpdatesChart() {
    const ctx = document.getElementById('priceUpdatesChart').getContext('2d');
    
    // Hämta historiska data via AJAX
    $.ajax({
        url: priceMatcherStatisticsAjaxUrl,
        type: 'POST',
        data: {
            action: 'getPriceUpdateTrend',
            days: 30
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderPriceUpdatesChart(ctx, response.data);
            } else {
                console.error('Failed to load price update trend data');
            }
        },
        error: function() {
            console.error('AJAX error when loading price update trend data');
        }
    });
}

/**
 * Rendera grafen för prisutveckling över tid
 */
function renderPriceUpdatesChart(ctx, data) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.dates,
            datasets: [
                {
                    label: 'Prismatchade produkter',
                    data: data.updates,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Aktiva rabatter',
                    data: data.active_discounts,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Antal produkter'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Prisutveckling senaste 30 dagarna'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                },
                legend: {
                    position: 'top'
                }
            }
        }
    });
}

/**
 * Initiera grafen för rabattdistribution
 */
function initDiscountDistributionChart() {
    const ctx = document.getElementById('discountDistributionChart').getContext('2d');
    
    // Hämta rabattdistributionsdata via AJAX
    $.ajax({
        url: priceMatcherStatisticsAjaxUrl,
        type: 'POST',
        data: {
            action: 'getDiscountDistribution'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderDiscountDistributionChart(ctx, response.data);
            } else {
                console.error('Failed to load discount distribution data');
            }
        },
        error: function() {
            console.error('AJAX error when loading discount distribution data');
        }
    });
}

/**
 * Rendera grafen för rabattdistribution
 */
function renderDiscountDistributionChart(ctx, data) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.ranges,
            datasets: [
                {
                    label: 'Antal produkter',
                    data: data.counts,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 99, 132, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Antal produkter'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Rabattspann (%)'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Fördelning av rabattstorlekar'
                },
                legend: {
                    display: false
                }
            }
        }
    });
}

/**
 * Initiera grafen för jämförelse mellan konkurrenter
 */
function initCompetitorComparisonChart() {
    const ctx = document.getElementById('competitorComparisonChart').getContext('2d');
    
    // Hämta konkurrentjämförelsedata via AJAX
    $.ajax({
        url: priceMatcherStatisticsAjaxUrl,
        type: 'POST',
        data: {
            action: 'getCompetitorComparison'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderCompetitorComparisonChart(ctx, response.data);
            } else {
                console.error('Failed to load competitor comparison data');
            }
        },
        error: function() {
            console.error('AJAX error when loading competitor comparison data');
        }
    });
}

/**
 * Rendera grafen för jämförelse mellan konkurrenter
 */
function renderCompetitorComparisonChart(ctx, data) {
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: data.competitors,
            datasets: [
                {
                    label: 'Matchningsfrekvens (%)',
                    data: data.match_rates,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2
                },
                {
                    label: 'Prisskillnad (%)',
                    data: data.price_diffs,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            elements: {
                line: {
                    tension: 0.2
                }
            },
            scales: {
                r: {
                    angleLines: {
                        display: true
                    },
                    suggestedMin: 0,
                    suggestedMax: 100
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Jämförelse mellan konkurrenter'
                }
            }
        }
    });
}

/**
 * Initiera grafen för prissänkningar per kategori
 */
function initCategoryDiscountChart() {
    const ctx = document.getElementById('categoryDiscountChart').getContext('2d');
    
    // Hämta kategoridata via AJAX
    $.ajax({
        url: priceMatcherStatisticsAjaxUrl,
        type: 'POST',
        data: {
            action: 'getCategoryDiscounts'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderCategoryDiscountChart(ctx, response.data);
            } else {
                console.error('Failed to load category discount data');
            }
        },
        error: function() {
            console.error('AJAX error when loading category discount data');
        }
    });
}

/**
 * Rendera grafen för prissänkningar per kategori
 */
function renderCategoryDiscountChart(ctx, data) {
    new Chart(ctx, {
        type: 'horizontalBar',
        data: {
            labels: data.categories,
            datasets: [
                {
                    label: 'Antal prismatchade produkter',
                    data: data.discount_counts,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Genomsnittlig rabatt (%)',
                    data: data.avg_discounts,
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Värde'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Kategori'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Prissänkningar per kategori'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        }
    });
}

/**
 * Hantera tabbar och bevara aktiv flik
 */
function maintainActiveTab() {
    // Hämta aktiv flik från URL hash eller localStorage
    var activeTab = window.location.hash;
    if (!activeTab) {
        activeTab = localStorage.getItem('pricematcher_active_statistics_tab');
    }
    
    // Om vi har en aktiv flik, växla till den
    if (activeTab) {
        $('.nav-tabs a[href="' + activeTab + '"]').tab('show');
    }
    
    // Spara den aktiva fliken i localStorage när den ändras
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var activeTab = $(e.target).attr('href');
        localStorage.setItem('pricematcher_active_statistics_tab', activeTab);
    });
}

/**
 * Hantera filtreringar för operationslistan
 */
function handleFiltering() {
    // Operation-filter
    $('#operation-filter, #competitor-filter, #date-range').on('change', function() {
        applyFilters();
    });
    
    // Återställ filter
    $('#reset-filter').on('click', function() {
        $('#operation-filter').val('');
        $('#competitor-filter').val('');
        $('#date-range').val('');
        applyFilters();
    });
}

/**
 * Tillämpa filter på operationslistan
 */
function applyFilters() {
    const operationType = $('#operation-filter').val();
    const competitorId = $('#competitor-filter').val();
    const dateRange = $('#date-range').val();
    
    // Genomför AJAX-anrop för att hämta filtrerade data
    $.ajax({
        url: priceMatcherStatisticsAjaxUrl,
        type: 'POST',
        data: {
            action: 'getFilteredOperations',
            operation_type: operationType,
            competitor_id: competitorId,
            date_range: dateRange
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Uppdatera operationstabellen
                updateOperationsTable(response.data.operations);
                // Uppdatera pagineringen
                updatePagination(response.data.pagination);
            } else {
                showErrorMessage(response.message || 'Failed to filter operations.');
            }
        },
        error: function() {
            showErrorMessage('An error occurred while filtering operations.');
        }
    });
}

/**
 * Uppdatera operationstabellen med ny data
 */
function updateOperationsTable(operations) {
    const tbody = $('#operations-table tbody');
    tbody.empty();
    
    if (!operations || operations.length === 0) {
        tbody.append('<tr><td colspan="8" class="text-center">Inga operationer hittades</td></tr>');
        return;
    }
    
    // Lägg till varje operation
    operations.forEach(function(op) {
        let row = $('<tr></tr>');
        
        // Datum
        row.append('<td>' + op.execution_date + '</td>');
        
        // Operationstyp
        let opTypeLabel = '';
        if (op.operation_type === 'download') {
            opTypeLabel = '<span class="label label-info">Nedladdning</span>';
        } else if (op.operation_type === 'compare') {
            opTypeLabel = '<span class="label label-primary">Jämförelse</span>';
        } else if (op.operation_type === 'update') {
            opTypeLabel = '<span class="label label-success">Uppdatering</span>';
        } else if (op.operation_type === 'clean') {
            opTypeLabel = '<span class="label label-warning">Rensa utgångna</span>';
        }
        row.append('<td>' + opTypeLabel + '</td>');
        
        // Konkurrent
        row.append('<td>' + op.competitor_name + '</td>');
        
        // Produkter bearbetade
        row.append('<td>' + op.products_processed + '</td>');
        
        // Framgångsfrekvens
        row.append(`
            <td>
                <div class="progress" style="margin-bottom: 0;">
                    <div class="progress-bar" role="progressbar" 
                        aria-valuenow="${op.success_rate}" 
                        aria-valuemin="0" 
                        aria-valuemax="100" 
                        style="width: ${op.success_rate}%;">
                        ${op.success_rate}%
                    </div>
                </div>
            </td>
        `);
        
        // Varaktighet
        row.append('<td>' + op.execution_time.toFixed(2) + ' sekunder</td>');
        
        // Initierad av
        let initiatedLabel = '';
        if (op.initiated_by === 'cron') {
            initiatedLabel = '<span class="label label-default">Cron</span>';
        } else if (op.initiated_by === 'manual') {
            initiatedLabel = '<span class="label label-primary">Manuell</span>';
        }
        row.append('<td>' + initiatedLabel + '</td>');
        
        // Detaljer
        row.append(`
            <td>
                <button type="button" class="btn btn-default btn-xs view-operation-details" data-id="${op.id_statistic}">
                    <i class="icon-search"></i> Visa detaljer
                </button>
            </td>
        `);
        
        tbody.append(row);
    });
}

/**
 * Uppdatera pagineringen
 */
function updatePagination(pagination) {
    if (!pagination) {
        return;
    }
    
    // Uppdatera text om antal visade poster
    $('.pagination-info').text(
        'Visar ' + pagination.start + ' till ' + pagination.end + 
        ' av ' + pagination.total + ' poster'
    );
    
    // Uppdatera pagineringsknappar
    const paginationElement = $('.pagination');
    if (paginationElement.length) {
        let html = '';
        
        // Föregående-knapp
        html += '<li class="' + (pagination.current_page == 1 ? 'disabled' : '') + '">';
        html += '<a href="' + (pagination.current_page > 1 ? 
            pagination.pagination_link + '&page=' + (pagination.current_page - 1) : '#') + '">';
        html += '<i class="icon-angle-left"></i></a></li>';
        
        // Sidnummerknappar
        for (let i = Math.max(1, pagination.current_page - 2); 
             i <= Math.min(pagination.total_pages, pagination.current_page + 2); i++) {
            html += '<li class="' + (pagination.current_page == i ? 'active' : '') + '">';
            html += '<a href="' + pagination.pagination_link + '&page=' + i + '">' + i + '</a></li>';
        }
        
        // Nästa-knapp
        html += '<li class="' + (pagination.current_page == pagination.total_pages ? 'disabled' : '') + '">';
        html += '<a href="' + (pagination.current_page < pagination.total_pages ? 
            pagination.pagination_link + '&page=' + (pagination.current_page + 1) : '#') + '">';
        html += '<i class="icon-angle-right"></i></a></li>';
        
        paginationElement.html(html);
    }
}

/**
 * Hantera modaler för detaljvy
 */
function handleDetailModals() {
    // Visa konkurrentdetaljer
    $(document).on('click', '.view-competitor-details', function() {
        const competitorId = $(this).data('id');
        $('#competitor-details-modal').modal('show');
        loadCompetitorDetails(competitorId);
    });
    
    // Visa operationsdetaljer
    $(document).on('click', '.view-operation-details', function() {
        const operationId = $(this).data('id');
        $('#operation-details-modal').modal('show');
        loadOperationDetails(operationId);
    });
}

/**
 * Ladda konkurrentdetaljer via AJAX
 */
function loadCompetitorDetails(competitorId) {
    // Visa laddningsindikator
    $('#competitor-details-modal .loading-content').show();
    $('#competitor-details-modal .competitor-details-content').hide();
    
    // Hämta detaljdata via AJAX
    $.ajax({
        url: priceMatcherStatisticsAjaxUrl,
        type: 'POST',
        data: {
            action: 'getCompetitorDetails',
            competitor_id: competitorId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Dölj laddningsindikator
                $('#competitor-details-modal .loading-content').hide();
                
                // Uppdatera modal med data
                updateCompetitorDetailModal(response.data);
                
                // Visa innehåll
                $('#competitor-details-modal .competitor-details-content').show();
            } else {
                showErrorMessage(response.message || 'Failed to load competitor details.');
                $('#competitor-details-modal').modal('hide');
            }
        },
        error: function() {
            showErrorMessage('An error occurred while loading competitor details.');
            $('#competitor-details-modal').modal('hide');
        }
    });
}

/**
 * Uppdatera modalen för konkurrentdetaljer med data
 */
function updateCompetitorDetailModal(data) {
    // Uppdatera grundläggande information
    $('#competitor-name').text(data.name);
    $('#competitor-products').text(data.total_products);
    $('#competitor-match-rate').text(data.match_rate + '%');
    $('#competitor-last-update').text(data.last_update);
    
    // Rendera konkurrentjämförelsediagram
    renderCompetitorPriceComparisonChart(data);
    
    // Rendera trenddiagram
    renderCompetitorTrendsChart(data);
    
    // Rendera kategoridiagram
    renderCompetitorCategoriesChart(data);
    
    // Uppdatera topproduktstabell
    updateTopProductsTable(data.top_products);
}

/**
 * Rendera prisfördelningsdiagram för konkurrent
 */
function renderCompetitorPriceComparisonChart(data) {
    const ctx = document.getElementById('competitor-price-comparison-chart').getContext('2d');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Lägre priser', 'Högre priser', 'Samma pris'],
            datasets: [{
                data: [data.lower_prices, data.higher_prices, data.same_prices],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 206, 86, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Rendera trenddiagram för konkurrent
 */
function renderCompetitorTrendsChart(data) {
    const ctx = document.getElementById('competitor-trends-chart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.trend.dates,
            datasets: [
                {
                    label: 'Antal matchade produkter',
                    data: data.trend.matches,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Genomsnittlig prisavvikelse (%)',
                    data: data.trend.price_diffs,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Antal produkter'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    title: {
                        display: true,
                        text: 'Prisavvikelse (%)'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Trender över tid'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        }
    });
}

/**
 * Rendera kategoridiagram för konkurrent
 */
function renderCompetitorCategoriesChart(data) {
    const ctx = document.getElementById('competitor-categories-chart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.categories.names,
            datasets: [
                {
                    label: 'Matchade produkter',
                    data: data.categories.counts,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Antal produkter'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Kategori'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Produktfördelning per kategori'
                },
                legend: {
                    display: false
                }
            }
        }
    });
}

/**
 * Uppdatera tabellen för topprodukter
 */
function updateTopProductsTable(products) {
    const tbody = $('#top-products-table tbody');
    tbody.empty();
    
    if (!products || products.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center">Inga produkter hittades</td></tr>');
        return;
    }
    
    // Lägg till varje produkt
    products.forEach(function(product) {
        let row = $('<tr></tr>');
        
        // Produkt
        row.append('<td>' + product.name + '</td>');
        
        // Kategori
        row.append('<td>' + product.category + '</td>');
        
        // Vårt pris
        row.append('<td>' + product.our_price + '</td>');
        
        // Konkurrentpris
        row.append('<td>' + product.competitor_price + '</td>');
        
        // Skillnad
        let diffClass = parseFloat(product.difference) < 0 ? 'text-danger' : 'text-success';
        row.append('<td class="' + diffClass + '">' + product.difference + '%</td>');
        
        // Senast uppdaterad
        row.append('<td>' + product.last_updated + '</td>');
        
        tbody.append(row);
    });
}

/**
 * Ladda operationsdetaljer via AJAX
 */
function loadOperationDetails(operationId) {
    // Visa laddningsindikator
    $('#operation-details-modal .loading-content').show();
    $('#operation-details-modal .operation-details-content').hide();
    
    // Hämta detaljdata via AJAX
    $.ajax({
        url: priceMatcherStatisticsAjaxUrl,
        type: 'POST',
        data: {
            action: 'getOperationDetails',
            operation_id: operationId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Dölj laddningsindikator
                $('#operation-details-modal .loading-content').hide();
                
                // Uppdatera modal med data
                updateOperationDetailModal(response.data);
                
                // Visa innehåll
                $('#operation-details-modal .operation-details-content').show();
            } else {
                showErrorMessage(response.message || 'Failed to load operation details.');
                $('#operation-details-modal').modal('hide');
            }
        },
        error: function() {
            showErrorMessage('An error occurred while loading operation details.');
            $('#operation-details-modal').modal('hide');
        }
    });
}

/**
 * Uppdatera modalen för operationsdetaljer med data
 */
function updateOperationDetailModal(data) {
    // Uppdatera grundläggande information
    $('#operation-type').html(getOperationTypeLabel(data.operation_type));
    $('#operation-competitor').text(data.competitor_name);
    $('#operation-date').text(data.execution_date);
    $('#operation-time').text(data.execution_time + ' sekunder');
    $('#operation-initiator').html(getInitiatorLabel(data.initiated_by));
    $('#operation-products').text(data.products_processed);
    $('#operation-success-rate').text(data.success_rate + '%');
    
    // Rendera resultatdiagram
    renderOperationResultsChart(data);
    
    // Uppdatera loggområdet
    $('#operation-log').text(data.log);
}

/**
 * Få HTML för operationstyp
 */
function getOperationTypeLabel(operationType) {
    let opTypeLabel = '';
    if (operationType === 'download') {
        opTypeLabel = '<span class="label label-info">Nedladdning</span>';
    } else if (operationType === 'compare') {
        opTypeLabel = '<span class="label label-primary">Jämförelse</span>';
    } else if (operationType === 'update') {
        opTypeLabel = '<span class="label label-success">Uppdatering</span>';
    } else if (operationType === 'clean') {
        opTypeLabel = '<span class="label label-warning">Rensa utgångna</span>';
    }
    return opTypeLabel;
}

/**
 * Få HTML för initierare
 */
function getInitiatorLabel(initiator) {
    let initiatedLabel = '';
    if (initiator === 'cron') {
        initiatedLabel = '<span class="label label-default">Cron</span>';
    } else if (initiator === 'manual') {
        initiatedLabel = '<span class="label label-primary">Manuell</span>';
    }
    return initiatedLabel;
}

/**
 * Rendera resultatdiagram för operation
 */
function renderOperationResultsChart(data) {
    const ctx = document.getElementById('operation-results-chart').getContext('2d');
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Lyckade', 'Misslyckade', 'Överhoppade'],
            datasets: [{
                data: [data.results.success, data.results.failure, data.results.skipped],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
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