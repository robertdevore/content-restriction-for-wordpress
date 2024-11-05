jQuery(document).ready(function ($) {
    // Initialize Select2 for existing selectors
    $('#crwp_repeater_container .crwp-content-type-select, #crwp_repeater_container .crwp-role-select').select2();

    // Add new restriction item
    $('.add-item').on('click', function () {
        const newIndex = $('#crwp_repeater_container .crwp_repeater_item').length;

        // Clone the template selects and set unique names for each new repeater item
        const newContentTypeSelect = $('#crwp_template .crwp-content-type-select').clone();
        newContentTypeSelect.attr('name', `crwp_restrictions[${newIndex}][content_type]`);

        const newRoleSelect = $('#crwp_template .crwp-role-select').clone();
        newRoleSelect.attr('name', `crwp_restrictions[${newIndex}][role]`);

        // Create a new repeater item
        const newItem = $('<div class="crwp_repeater_item"></div>');
        newItem.append(newContentTypeSelect);
        newItem.append(newRoleSelect);
        newItem.append('<button type="button" class="button remove-item">Remove</button>');

        // Append the new item to the repeater container
        $('#crwp_repeater_container').append(newItem);

        // Initialize Select2 on the cloned selects
        newContentTypeSelect.select2();
        newRoleSelect.select2();
    });

    // Remove restriction item
    $('#crwp_repeater_container').on('click', '.remove-item', function () {
        $(this).closest('.crwp_repeater_item').remove();
    });
});
