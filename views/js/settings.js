/**
 * JavaScript för Settings-fliken i Art PriceMatcher-modulen
 */
$(document).ready(function() {
    // Initiera Select2 för bättre dropdown-menyer
    initSelect2();
    
    // Initiera token-generatorn
    initTokenGenerator();
    
    // Hantera kopiering av cron-URLs
    initClipboardCopy();
    
    // Behåll aktiv tabb efter siduppdatering
    maintainActiveTab();
});

/**
 * Initiera Select2 för kategori och tillverkarval
 */
function initSelect2() {
    // Kategori-väljare
    if ($.fn.select2) {
        $('.select2-categories').select2({
            placeholder: pricematcherTranslations.selectCategories,
            allowClear: true,
            width: '100%'
        });
        
        // Tillverkar-väljare
        $('.select2-manufacturers').select2({
            placeholder: pricematcherTranslations.selectManufacturers,
            allowClear: true,
            width: '100%'
        });
        
        // Kundgrupp-väljare
        $('.select2-groups').select2({
            placeholder: pricematcherTranslations.selectGroups,
            allowClear: true,
            width: '100%'
        });
    }
}

/**
 * Initiera token-generatorn
 */
function initTokenGenerator() {
    $('#generate_token').click(function() {
        var token = '';
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        for (var i = 0; i < 32; i++) {
            token += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('input[name="cron_token"]').val(token);
        
        // Även uppdatera cron-URL:er med den nya token
        $('.cron-url').each(function() {
            var url = $(this).val();
            url = url.replace(/token=[a-zA-Z0-9]+/, 'token=' + token);
            $(this).val(url);
        });
        
        // Visa bekräftelsemeddelande
        showSuccessMessage(pricematcherTranslations.tokenGenerated);
    });
}

/**
 * Initiera kopiera-till-urklipp funktionalitet
 */
function initClipboardCopy() {
    $('.copy-to-clipboard').click(function() {
        var text = $(this).data('clipboard-text');
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(text).select();
        document.execCommand("copy");
        $temp.remove();
        
        // Ändra knappens ikon till en bekräftelse
        var $button = $(this);
        $button.html('<i class="icon-check"></i>');
        
        // Återställ knappen efter 2 sekunder
        setTimeout(function() {
            $button.html('<i class="icon-copy"></i>');
        }, 2000);
        
        // Visa bekräftelsemeddelande
        showSuccessMessage(pricematcherTranslations.copied);
    });
}

/**
 * Behåll aktiv tabb efter siduppdatering
 */
function maintainActiveTab() {
    // Get the active tab from URL hash or localStorage
    var activeTab = window.location.hash;
    if (!activeTab) {
        activeTab = localStorage.getItem('pricematcher_active_settings_tab');
    }
    
    // If we have an active tab, switch to it
    if (activeTab) {
        $('.nav-tabs a[href="' + activeTab + '"]').tab('show');
    }
    
    // Store the currently active tab in localStorage when changed
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var activeTab = $(e.target).attr('href');
        localStorage.setItem('pricematcher_active_settings_tab', activeTab);
    });
}

/**
 * Visa framgångsmeddelande
 * 
 * @param {string} message - Meddelande att visa
 */
function showSuccessMessage(message) {
    $.growl.notice({ message: message });
}

/**
 * Visa felmeddelande
 * 
 * @param {string} message - Meddelande att visa
 */
function showErrorMessage(message) {
    $.growl.error({ message: message });
}