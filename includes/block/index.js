const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;
const { createElement, useState, useEffect } = window.wp.element;
const i18n = window.gopayI18n || {};


// Retrieving settings from data provided by PHP
const settings = getSetting('gopay_data', {});

// Function to check Apple Pay availability
const checkApplePayAvailability = () => {
	try {
		// Must be secure (HTTPS)
		if (!window.isSecureContext) {
			console.warn("Apple Pay requires HTTPS.");
			return false;
		}

		if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
			return true;
		}
	} catch (err) {
		console.error("Apple Pay check failed:", err);
	}

	return false;
};

// Function to filter available payment methods
const filterPaymentMethods = (methods) => {
	const applePayAvailable = checkApplePayAvailability();

	// Filter methods – remove Apple Pay if it is not available
	return methods.filter(method => {
		if (method.id === 'APPLE_PAY' && !applePayAvailable) {
			return false;
		}
		return true;
	});

};

// Remove Apple Pay from payment methods if it is not available
const filteredMethods = filterPaymentMethods(settings.paymentMethods);

// Component for selecting a GoPay payment method
const GoPayMethodSelection = (props) => {
	const [selectedMethod, setSelectedMethod] = useState('');
	const [selectedCard, setSelectedCard] = useState(
		settings.savedCards && settings.savedCards.length > 0 ? settings.savedCards[0].card_id : 'new'
	);
	const [requestCardToken, setRequestCardToken] = useState(false);
	const { eventRegistration, emitResponse } = props;
	const { onPaymentSetup } = eventRegistration;

	useEffect(() => {
		// Automatically select the first method if none is selected yet
		if (filteredMethods && filteredMethods.length > 0 && !selectedMethod) {
			setSelectedMethod(filteredMethods[0].id);
		}
	}, []);

	useEffect(() => {
		const unsubscribe = onPaymentSetup(() => {
			// Validate that a payment method is selected before submission
			if (!selectedMethod) {
				return {
					type: 'error',
					message: i18n.selectPaymentMethod,
				};
			}

			// Returning successful selection data to WooCommerce
			return {
				type: 'success',
				meta: {
					paymentMethodData: {
						gopay_payment_method: selectedMethod,
						saved_card: selectedCard,
						request_card_token: requestCardToken,
					},
				},
			};
		});

		// Cleanup callback subscription on unmount
		return () => unsubscribe();
	}, [onPaymentSetup, selectedMethod, selectedCard, requestCardToken]);

	// If no payment methods exist, render nothing
	if (!filteredMethods || !filteredMethods.length) {
		return null;
	}

	return createElement('div', { className: 'wc-gopay-payment-methods' },
		settings.description && createElement('p', { className: 'wc-gopay-description' }, settings.description),

		createElement('div', { className: 'wc-gopay-methods-list' },
			filteredMethods.map((method) =>
				createElement('div', {
					key: method.id,
					className: `wc-gopay-method ${selectedMethod === method.id ? 'selected' : ''}`,
					onClick: () => setSelectedMethod(method.id)
				},
					createElement('div', { className: 'wc-gopay-method-input' },
						createElement('input', {
							type: 'radio',
							name: 'gopay_payment_method',
							value: method.id,
							id: method.id,
							checked: selectedMethod === method.id,
							onChange: () => setSelectedMethod(method.id)
						}),
						createElement('span', {}, method.label),
						method.image && createElement('img', {
							src: method.image,
							alt: method.label,
							className: 'wc-gopay-method-image',
						})
					),
					method.id === 'PAYMENT_CARD' && selectedMethod === 'PAYMENT_CARD' && settings.isTokenizeEnabled ? createElement('div', { className: 'wc-gopay-card-selection' },
						createElement('div', { className: 'card_selection_container', id: 'card_selection_container' },

							(settings.savedCards && settings.savedCards.length > 0) ? createElement('div', { className: 'gopay-card-list', id: 'gopay-card-list' },
								createElement('div', { className: 'gopay-card-list__title' }, i18n.selectPaymentCard),
								createElement('div', { className: 'gopay-card-options' },
									// Saved Cards
									settings.savedCards.map((card) =>
										createElement('label', { key: card.card_id, className: `gopay-card-option ${selectedCard === card.card_id ? 'selected' : ''}` },
											createElement('input', {
												type: 'radio',
												name: 'saved_card',
												value: card.card_id,
												checked: selectedCard === card.card_id,
												onChange: () => { setSelectedCard(card.card_id); setRequestCardToken(false); }
											}),
											createElement('div', { className: 'gopay-card-option__content' },
												card.card_art_url && createElement('img', {
													src: card.card_art_url,
													alt: card.card_brand,
													className: 'gopay-card-option__art'
												}),
												createElement('div', { className: 'gopay-card-option__details' },
													createElement('span', { className: 'gopay-card-option__brand' }, card.card_brand),
													createElement('span', { className: 'gopay-card-option__number' }, card.real_masked_pan),
													createElement('span', { className: 'gopay-card-option__expiry' }, `(exp ${card.card_expiration})`)
												)
											)
										)
									),
									// New Card Option
									createElement('label', { className: `gopay-card-option is-new ${selectedCard === 'new' ? 'selected' : ''}` },
										createElement('input', {
											type: 'radio',
											name: 'saved_card',
											value: 'new',
											checked: selectedCard === 'new',
											onChange: () => setSelectedCard('new')
										}),
										createElement('div', { className: 'gopay-card-option__content' },
											createElement('div', { className: 'gopay-card-option__details' },
												createElement('span', { className: 'gopay-card-option__brand' }, i18n.newCard)
											)
										)
									)
								)
							) : null,

							selectedCard === 'new' && createElement('div', { className: 'payment_wc_store_token', id: 'payment_wc_store_token' },
								createElement('input', {
									type: 'checkbox',
									id: 'request_card_token',
									name: 'request_card_token',
									checked: requestCardToken,
									onChange: (e) => setRequestCardToken(e.target.checked)
								}),
								createElement('label', { htmlFor: 'request_card_token' }, i18n.saveCard),
							),
						)
					) : null
				)
			)
		)
	);
};

// Definition of the GoPay payment gateway
// The object is used by WooCommerce Blocks to register the gateway
const GoPayGateway = {
	name: 'wc_gopay_gateway',
	label: settings.title || i18n.gopay,
	content: createElement(GoPayMethodSelection),
	edit: createElement(GoPayMethodSelection),
	canMakePayment: () => true,
	ariaLabel: settings.title || i18n.gopay,
	supports: settings.supports || {
		features: ['products']
	}
};

registerPaymentMethod(GoPayGateway);
