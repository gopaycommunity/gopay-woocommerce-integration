jQuery(function ($) {

    const toggleCardUI = () => {
        const selectedMethod = $('input[name="gopay_payment_method"]:checked').val();

        $('#payment_wc_store_token').slideDown();

        if (selectedMethod === 'PAYMENT_CARD') {
            $('#card_selection_container').slideDown();
        } else {
            $('#request_card_token').prop('checked', false);

            $('#saved_card').val('new');

            $('#card_selection_container').slideUp();
        }
    }

    const toggleSaveCheckbox = () => {
        const selectedCard = $('#saved_card').val();

        if (!selectedCard || selectedCard === 'new') {
            $('#payment_wc_store_token').slideDown();
        } else {
            $('#payment_wc_store_token').slideUp();
        }
    }

    $(document).on('change', 'input[name="gopay_payment_method"]', () => {
        toggleCardUI();
    });

    $(document).on('change', '#saved_card', () => {
        toggleSaveCheckbox();
    });

    // WooCommerce refresh support
    $(document.body).on('updated_checkout', () => {
        toggleCardUI();
        toggleSaveCheckbox();
    });

    const toggleCompactLayout = () => {
        const $container = $('#payment_wc_select_card');
        if ($container.length) {
            $container.toggleClass('compact-layout', $container.width() < 300);
        }
    };

    $(window).on('resize', toggleCompactLayout);
    $(document.body).on('updated_checkout', toggleCompactLayout);
    toggleCompactLayout();

    // Initial state
    toggleCardUI();
    toggleSaveCheckbox();
});
