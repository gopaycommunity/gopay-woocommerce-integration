jQuery(function ($) {

    // Show confirmation
    $(document).on('click', '#gopay-delete-card', function (e) {
        e.preventDefault();

        var wrapper = $(this).closest('#gopay-delete-wrapper');

        wrapper.find('#gopay-delete-card').hide();
        wrapper.find('#gopay-delete-confirm').fadeIn(150);
    });

    // Cancel delete
    $(document).on('click', '#gopay-confirm-cancel', function (e) {
        e.preventDefault();

        var wrapper = $(this).closest('#gopay-delete-wrapper');

        wrapper.find('#gopay-delete-confirm').hide();
        wrapper.find('#gopay-delete-card').fadeIn(150);
    });

    // Confirm delete using AJAX request
    $(document).on('click', '#gopay-confirm-yes', function (e) {
        e.preventDefault();

        var button = $(this);
        var cardId = button.data('card-id');
        var nonce = button.data('nonce');

        $.post(GoPayCardsAjax.ajaxurl, {
            action: 'gopay_delete_card',
            card_id: cardId,
            nonce: nonce
        }, function (response) {
            if (response.success) {
                $('#gopay-card-row-' + cardId)
                    .fadeOut(300, function () { $(this).remove(); });
            } else {
                console.log('Error while deleting payment card.');
            }
        });
    });

});
