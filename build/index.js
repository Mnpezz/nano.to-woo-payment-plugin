// File: build/index.js

const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;
const { decodeEntities } = window.wp.htmlEntities;
const { __ } = window.wp.i18n;

const settings = getSetting('nanopay_data', {});

const label = decodeEntities(settings.title) || __('NanoPay', 'nanopay');

const Content = () => {
    return decodeEntities(settings.description || '');
};

const NanoPayPaymentMethod = {
    name: 'nanopay',
    label: label,
    content: Object(window.wp.element.createElement)(Content, null),
    edit: Object(window.wp.element.createElement)(Content, null),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
    paymentMethodId: 'nanopay',
    
    // Add this method to handle payment processing
    processPayment: (payload, errorCallback, successCallback) => {
        // This is where you'd normally process the payment
        // For testing, we'll just log the payload and call the success callback
        console.log('Processing payment:', payload);
        
        // Simulate an API call
        setTimeout(() => {
            // If you want to test error handling, you can uncomment the following line:
            // return errorCallback('An error occurred while processing payment.');
            
            successCallback();
        }, 1000);
    },
};

registerPaymentMethod(NanoPayPaymentMethod);