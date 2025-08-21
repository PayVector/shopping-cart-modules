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
        'ko',
        'Magento_Checkout/js/model/quote'
    ],
    function (
        $,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        checkoutData,
        additionalValidators,
        url, ko, quote) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'PayVector_Payment/payment/payvector-direct',
                creditCardOwner: ko.observable(''),
                creditCardNumber: ko.observable(''),
                creditCardStartMonth: ko.observable(''),
                creditCardStartYear: ko.observable(''),
                creditCardExpMonth: ko.observable(''),
                creditCardExpYear: ko.observable(''),
                creditCardVerificationNumber: ko.observable(''),
                useSavedCard: ko.observable(false),
                selectedSavedCard: ko.observable(null),
                savedCards: ko.observableArray([]),
                lastUsedCard: ko.observable(null),
                browserUserAgent: navigator.userAgent,
                acceptHeader: navigator.userAgent,
                browserLanguage: navigator.language || navigator.userLanguage,
                screenWidth: screen.width,
                screenHeight: screen.height,
                colorDepth: screen.colorDepth,
                javaEnabled: navigator.javaEnabled(),
                timeZoneOffset: new Date().getTimezoneOffset(),
                selectedCardOption: ko.observable('new')

            },

            initialize: function () {
                this._super();
                this.lastUsedCard = ko.observable(window.checkoutConfig.payment.payvector_payment.tokens || null);
                var billingAddress = quote.billingAddress();
                if (billingAddress) {
                    var fullName = billingAddress.firstname + ' ' + billingAddress.lastname;
                    this.creditCardOwner(fullName);
                }
                return this;
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                var self = this;
                var emailValidationResult = customer.isLoggedIn();
                var loginFormSelector = 'form[data-role=email-with-possible-login]';

                // Validate guest email input if not logged in
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    var emailInput = $(loginFormSelector + ' input[name=username]');
                    emailValidationResult = emailInput.length && emailInput.valid();
                }

                if (
                    emailValidationResult &&
                    this.validate() &&
                    additionalValidators.validate()
                ) {
                    this.isPlaceOrderActionAllowed(false);

                    var placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    $.when(placeOrder)
                        .done(function (response) {
                            window.location.replace(url.build('payvector/standard/threedsecure'));
                        })
                        .fail(function (error) {

                            try {
                                const response = error.responseJSON;
                                const errData = JSON.parse(response.message);
                                alert(errData.message);
                                self.messageContainer.addErrorMessage({ message: errData.message || 'Payment failed.' });

                            } catch (e) {
                                self.messageContainer.addErrorMessage({ message: 'An error occurred during payment.' });
                            }
                            self.isPlaceOrderActionAllowed(true);
                        });

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



            getCcMonths: function () {
                return _.map([
                    { value: '01', month: '01' },
                    { value: '02', month: '02' },
                    { value: '03', month: '03' },
                    { value: '04', month: '04' },
                    { value: '05', month: '05' },
                    { value: '06', month: '06' },
                    { value: '07', month: '07' },
                    { value: '08', month: '08' },
                    { value: '09', month: '09' },
                    { value: '10', month: '10' },
                    { value: '11', month: '11' },
                    { value: '12', month: '12' }
                ], function (month) {
                    return month;
                });
            },

            getCcYears: function () {
                const currentYear = new Date().getFullYear();
                const years = [];

                for (let i = 0; i < 15; i++) {
                    years.push({ value: currentYear + i, year: (currentYear + i).toString() });
                }

                return years;
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
                        cc_number: this.creditCardNumber(),
                        cc_exp_month: this.creditCardExpMonth(),
                        cc_exp_year: this.creditCardExpYear(),
                        cc_cid: this.creditCardVerificationNumber(),
                        cc_owner: this.creditCardOwner(),
                        browser_user_agent: this.browserUserAgent,
                        accept_header: this.acceptHeader,
                        browser_language: this.browserLanguage,
                        screen_width: this.screenWidth,
                        screen_height: this.screenHeight,
                        color_depth: this.colorDepth,
                        java_enabled: this.javaEnabled,
                        timezone_offset: this.timeZoneOffset
                    });
                }

                return base;
            },
            validate: function () {
                const selectedCardOption = this.selectedCardOption();
                var ccCVV = this.creditCardVerificationNumber();
                if (selectedCardOption === 'saved') {
                    var isValid = true;
                    if (!/^\d{3,4}$/.test(ccCVV)) {
                        isValid = false;
                        alert('CVV must be 3 or 4 digits.');
                    }
                    return isValid;

                }
                var isValid = true;

                var ccNumber = this.creditCardNumber();
                var ccOwner = this.creditCardOwner();
                var ccExpMonth = this.creditCardExpMonth();
                var ccExpYear = this.creditCardExpYear();



                if (!ccOwner || ccOwner.length < 2) {
                    isValid = false;
                    alert('Card owner name is required and must be at least 2 characters.');
                }

                if (!/^\d{12,19}$/.test(ccNumber)) {
                    isValid = false;
                    alert('Card number must be between 12 and 19 digits.');
                }

                if (!/^\d{3,4}$/.test(ccCVV)) {
                    isValid = false;
                    alert('CVV must be 3 or 4 digits.');
                }

                if (!ccExpMonth || !ccExpYear || isNaN(ccExpMonth) || isNaN(ccExpYear)) {
                    isValid = false;
                    alert('Valid expiration month and year are required.');
                } else {
                    var now = new Date();
                    var currentMonth = now.getMonth() + 1;
                    var currentYear = now.getFullYear();

                    if (parseInt(ccExpYear) < currentYear ||
                        (parseInt(ccExpYear) === currentYear && parseInt(ccExpMonth) < currentMonth)) {
                        isValid = false;
                        alert('Card is expired.');
                    }
                }

                return isValid;
            }


        });
    }
);
