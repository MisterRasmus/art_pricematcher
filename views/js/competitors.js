/**
 * JavaScript för Competitors-fliken i Art PriceMatcher-modulen
 */
$(document).ready(function() {
    // Hantera visning av inställningar för konkurrenter
    handleCompetitorSettings();
    
    // Hantera aktivering/inaktivering av konkurrenter
    handleCompetitorToggle();
    
    // Hantera borttagning av konkurrenter
    handleCompetitorDeletion();
    
    // Initiera tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

/**
 * Hantera visning och dölning av konkurrentinställningar
 */
function handleCompetitorSettings() {
    // Visa konkurrentinställningspanel när redigera-knappen klickas
    $('.js-edit-competitor').on('click', function() {
        var competitorId = $(this).data('id');
        $('.competitor-settings-panel').hide();
        $('#competitor-panel-' + competitorId).show();
    });
    
    // Stäng konkurrentinställningspanel
    $('.js-close-competitor-panel').on('click', function() {
        $(this).closest('.competitor-settings-panel').hide();
    });
    
    // Växla konkurrentspecifika inställningar när kryssrutan klickas
    $('.js-toggle-competitor-settings').on('change', function() {
        var competitorId = $(this).data('competitor-id');
        var settingsContainer = $('#competitor-settings-' + competitorId);
        
        if ($(this).is(':checked')) {
            settingsContainer.slideDown();
        } else {
            settingsContainer.slideUp();
        }
    });
}

/**
 * Hantera aktivering/inaktivering av konkurrenter
 */
function handleCompetitorToggle() {
    $('.js-toggle-competitor').on('click', function() {
        var competitorId = $(this).data('id');
        var newStatus = $(this).data('status');
        var statusText = newStatus == 1 ? 'enable' : 'disable';
        
        if (confirm('Are you sure you want to ' + statusText + ' this competitor?')) {
            toggleCompetitorStatus(competitorId, newStatus);
        }
    });
}

/**
 * Ändra status för en konkurrent via AJAX
 */
function toggleCompetitorStatus(competitorId, status) {
    $.ajax({
        url: currentIndex + '&token=' + token + '&ajax=1',
        type: 'POST',
        data: {
            controller: 'AdminPriceMatcherController',
            action: 'toggleCompetitor',
            tab: 'competitors',
            id_competitor: competitorId,
            status: status
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Uppdatera sidan för att visa den nya statusen
                location.reload();
            } else {
                showErrorMessage(response.message || 'Failed to update competitor status.');
            }
        },
        error: function() {
            showErrorMessage('An error occurred while updating competitor status.');
        }
    });
}

/**
 * Hantera borttagning av konkurrenter
 */
function handleCompetitorDeletion() {
    // Visa bekräftelsedialog när delete-knappen klickas
    $('.js-delete-competitor').on('click', function() {
        var competitorId = $(this).data('id');
        var competitorName = $(this).data('name');
        
        $('#delete-competitor-name').text('Competitor: ' + competitorName);
        $('#delete-competitor-modal').data('id', competitorId).modal('show');
    });
    
    // Hantera bekräftelse av borttagning
    $('#confirm-delete-competitor').on('click', function() {
        var competitorId = $('#delete-competitor-modal').data('id');
        deleteCompetitor(competitorId);
    });
}

/**
 * Ta bort en konkurrent via AJAX
 */
function deleteCompetitor(competitorId) {
    $.ajax({
        url: currentIndex + '&token=' + token + '&ajax=1',
        type: 'POST',
        data: {
            controller: 'AdminPriceMatcherController',
            action: 'deleteCompetitor',
            tab: 'competitors',
            id_competitor: competitorId
        },
        dataType: 'json',
        success: function(response) {
            $('#delete-competitor-modal').modal('hide');
            
            if (response.success) {
                // Uppdatera sidan för att ta bort den borttagna konkurrenten
                location.reload();
            } else {
                showErrorMessage(response.message || 'Failed to delete competitor.');
            }
        },
        error: function() {
            $('#delete-competitor-modal').modal('hide');
            showErrorMessage('An error occurred while deleting the competitor.');
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