jQuery(function ($) {

    const updateCardContainer = (animate) => {
        const isPaymentCard = $('input[name="gopay_payment_method"]:checked').val() === 'PAYMENT_CARD';

        if (isPaymentCard) {
            animate ? $('#card_selection_container').slideDown() : $('#card_selection_container').show();
        } else {
            $('#request_card_token').prop('checked', false);
            animate ? $('#card_selection_container').slideUp() : $('#card_selection_container').hide();
        }
    };

    const updateSaveToken = (animate) => {
        const selectedCard = $('input[name="saved_card"]:checked').val();
        const showToken = !selectedCard || selectedCard === 'new';

        if (showToken) {
            animate ? $('#payment_wc_store_token').slideDown() : $('#payment_wc_store_token').show();
        } else {
            animate ? $('#payment_wc_store_token').slideUp() : $('#payment_wc_store_token').hide();
        }
    };

    $(document).on('change', 'input[name="gopay_payment_method"]', () => {
        updateCardContainer(true);
        updateSaveToken(true);
    });

    $(document).on('change', 'input[name="saved_card"]', () => {
        updateSaveToken(true);
    });

    // WooCommerce checkout refresh support
    $(document.body).on('updated_checkout', () => {
        updateCardContainer(false);
        updateSaveToken(false);
    });

    // Initial state
    updateCardContainer(false);
    updateSaveToken(false);
});
