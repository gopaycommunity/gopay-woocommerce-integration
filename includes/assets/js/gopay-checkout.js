jQuery(function ($) {

    const toggleCardUI = () => {
        const selectedMethod = $('input[name="gopay_payment_method"]:checked').val();

        $('.payment_wc_store_token').slideDown();

        if (selectedMethod === 'PAYMENT_CARD') {
            $('.card_selection_container').slideDown();
        } else {
            $('#request_card_token').prop('checked', false);

            $('.saved_card_select').val('new');

            $('.card_selection_container').slideUp();
        }
    }

    const toggleSaveCheckbox = () => {
        const selectedCard = $('.saved_card_select').val();

        if (selectedCard === 'new') {
            $('.payment_wc_store_token').slideDown();
        } else {
            $('.payment_wc_store_token').slideUp();
        }
    }

    $(document).on('change', 'input[name="gopay_payment_method"]', () => {
        toggleCardUI();
    });

    $(document).on('change', '.saved_card_select', () => {
        toggleSaveCheckbox();
    });

    // WooCommerce refresh support
    $(document.body).on('updated_checkout', () => {
        toggleCardUI();
        toggleSaveCheckbox();
    });

    // Initial state
    toggleCardUI();
    toggleSaveCheckbox();
});
