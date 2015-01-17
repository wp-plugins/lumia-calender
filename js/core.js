jQuery(document).ready(function() {
    jQuery( '#event-from' ).Lumia_DatePicker({
        direction: true,
        pair: jQuery( '#event-to' )
    });

    jQuery( '#event-to' ).Lumia_DatePicker({
        direction: true
    });
});