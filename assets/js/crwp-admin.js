jQuery(function ($) {
    // Initialize Select2 with AJAX for existing selectors in the container.
    $('#crwp_repeater_container .crwp-content-type-select').select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250, // Delay to reduce server load
            data: function (params) {
                return {
                    q: params.term, // Search term
                    action: 'crwp_fetch_content_options',
                    nonce: crwp_admin.nonce
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: 'Select Post Type or Taxonomy Term',
        allowClear: true
    });

    // Initialize Select2 for role selection without AJAX.
    $('#crwp_repeater_container .crwp-role-select').select2();

    // Add new restriction item.
    $('.add-item').on('click', function () {
        const newIndex = $('#crwp_repeater_container .crwp_repeater_item').length;

        // Clone the template selects and set unique names for each new repeater item.
        const newContentTypeSelect = $('#crwp_template .crwp-content-type-select').clone();
        newContentTypeSelect.attr('name', `crwp_restrictions[${newIndex}][content_type]`);

        const newRoleSelect = $('#crwp_template .crwp-role-select').clone();
        newRoleSelect.attr('name', `crwp_restrictions[${newIndex}][role]`);

        // Create a new repeater item.
        const newItem = $('<div class="crwp_repeater_item"></div>');
        newItem.append(newContentTypeSelect);
        newItem.append(newRoleSelect);
        newItem.append('<button type="button" class="button remove-item">Remove</button>');

        // Append the new item to the repeater container.
        $('#crwp_repeater_container').append(newItem);

        // Initialize Select2 with AJAX for the new content type select.
        newContentTypeSelect.select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        action: 'crwp_fetch_content_options',
                        nonce: crwp_admin.nonce
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            minimumInputLength: 2,
            placeholder: 'Select Post Type or Taxonomy Term',
            allowClear: true
        });

        // Initialize Select2 for the new role select.
        newRoleSelect.select2();
    });

    // Remove restriction item.
    $('#crwp_repeater_container').on('click', '.remove-item', function () {
        $(this).closest('.crwp_repeater_item').remove();
    });
});
