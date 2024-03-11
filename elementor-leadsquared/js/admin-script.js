jQuery(document).ready(function($) {
    $('#retrieve-leadsquared-schema').click(function(e) {
        e.preventDefault();
        var button = $(this); // Reference the button for later use
        button.prop('disabled', true); // Disable the button to prevent multiple clicks
        $('#schema-update-status').text('Retrieving schema...');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'retrieve_leadsquared_schema',
                security: myPluginAjax.security,
            },
            success: function(response) {
                if (response.success) {
                    $('#schema-update-status').html('<p>Schema retrieved and stored successfully.</p>');
                    // Optionally, update other elements based on the retrieved schema
                } else {
                    $('#schema-update-status').html('<p>Failed to retrieve schema: ' + response.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#schema-update-status').html('<p>Error: ' + error + '</p>');
            },
            complete: function() {
                button.prop('disabled', false); // Re-enable the button
            }
        });
    });
});

jQuery(document).ready(function($) {
    function initializeLeadSquaredSelect() {
        $('.elementor-leadquared-select select').each(function() {
            // Initialize Select2
            $(this).select2({
                width: '100%',
                placeholder: "Select a field",
                allowClear: true
            });
        });
    }

    // Initial initialization
    initializeLeadSquaredSelect();

    // Reinitialize for dynamically added repeater fields
    $(document).on('click', '.elementor-repeater-add', function() {
        setTimeout(initializeLeadSquaredSelect, 300); // Adjust timing as necessary
    });

    // If you're using Elementor's JS hooks for a more precise control:
    elementor.channels.editor.on('section:activated', initializeLeadSquaredSelect);
});