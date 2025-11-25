const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;
const { createElement, useState, useEffect } = window.wp.element;
const { __ } = window.wp.i18n;


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
					message: __('Select GoPay Payment Methods...', 'gopay-gateway'),
				};
			}

			// Returning successful selection data to WooCommerce
			return {
				type: 'success',
				meta: {
					paymentMethodData: {
						gopay_payment_method: selectedMethod,
					},
				},
			};
		});

		// Cleanup callback subscription on unmount
		return () => unsubscribe();
	}, [onPaymentSetup, selectedMethod]);

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
							name: 'gopay-payment-method',
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
					)
				)
			)
		)
	);
};

// Definition of the GoPay payment gateway
// The object is used by WooCommerce Blocks to register the gateway
const GoPayGateway = {
	name: 'wc_gopay_gateway',
	label: settings.title || __('GoPay', 'gopay-gateway'),
	content: createElement(GoPayMethodSelection),
	edit: createElement(GoPayMethodSelection),
	canMakePayment: () => true,
	ariaLabel: settings.title || __('GoPay', 'gopay-gateway'),
	supports: settings.supports || {
		features: ['products']
	}
};

registerPaymentMethod(GoPayGateway);
