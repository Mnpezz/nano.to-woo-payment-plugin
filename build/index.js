(function() {
    'use strict';
    
    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getSetting } = window.wc.wcSettings;
    const { decodeEntities } = window.wp.htmlEntities;
    const { __ } = window.wp.i18n;
    
    const settings = getSetting('nanoto_data', {});
    const label = decodeEntities(settings.title) || __('Nanoto', 'nanoto');
    
    const Content = () => {
        return decodeEntities(settings.description || '');
    };
    
    const NanotoPaymentMethod = {
        name: 'nanoto',
        label: label,
        content: Object(window.wp.element.createElement)(Content, null),
        edit: Object(window.wp.element.createElement)(Content, null),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports,
        },
        paymentMethodId: 'nanoto',
        
        processPayment: (payload, errorCallback, successCallback) => {
            console.log('Processing payment:', payload);
            
            setTimeout(() => {
                successCallback();
            }, 1000);
        },
    };
    
    registerPaymentMethod(NanotoPaymentMethod);
})();
