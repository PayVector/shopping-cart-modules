define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, renderList) {
    'use strict';
    var configMode = window.checkoutConfig.payment.payvector_payment.mode;
    var componentPath = (configMode === 'hosted')
        ? 'PayVector_Payment/js/view/payment/method-renderer/payvector-hosted'
        : 'PayVector_Payment/js/view/payment/method-renderer/payvector-direct';

    renderList.push({
        type: 'payvector_payment',
        component: componentPath
    });

    return Component.extend({});
})
