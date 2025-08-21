define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'ko'
    ],
    function (
        $,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        checkoutData,
        additionalValidators,
        url, ko) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'PayVector_Payment/payment/payvector-hosted',
                lastUsedCard: ko.observable(null),
                creditCardVerificationNumber: ko.observable(''),
                useSavedCard: ko.observable(false),
                savedCards: ko.observableArray([]),
                selectedCardOption: ko.observable('new')
            },

            initialize: function () {
                this._super();
                this.lastUsedCard = ko.observable(window.checkoutConfig.payment.payvector_payment.tokens || null);
                console.log("cards", this.lastUsedCard)
                return this;
            },
            getData: function () {
                const selectedCardOption = this.selectedCardOption();

                const base = {
                    method: this.getCode(),
                    additional_data: {
                        use_saved_card: false,
                        cc_cid: '',
                    }
                };

                if (selectedCardOption === 'saved') {
                    base.additional_data.use_saved_card = true;
                    base.additional_data.cc_cid = this.creditCardVerificationNumber();
                } else {
                    base.additional_data = Object.assign(base.additional_data, {
                        use_saved_card: false,
                    });
                }

                return base;
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },

            selectPaymentMethod: function () {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                this.useSavedCard(this.savedCards().length > 0);
                return true;
            },
            getPaymentSrc: function () {
                return window.checkoutConfig.payment[this.getCode()].getPaymentSrc;
            },

            afterPlaceOrder: function () {
                if (this.selectedCardOption() === 'saved') window.location.replace(url.build('payvector/standard/threedsecure'));
                else window.location.replace(url.build('payvector/standard/redirect/'));
            }
        });
    }
);
