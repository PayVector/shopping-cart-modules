import { patch } from '@web/core/utils/patch';
import { PaymentForm } from '@payment/interactions/payment_form';

patch(PaymentForm.prototype, {

    // #=== DOM MANIPULATION ===#

    /**
     * Prepare the inline form of PayVector for payment processing.
     *
     * @override method from @payment/interactions/payment_form
     * @private
     * @param {number} providerId - The id of the selected payment option's provider.
     * @param {string} providerCode - The code of the selected payment option's provider.
     * @param {number} paymentOptionId - The id of the selected payment option.
     * @param {string} paymentMethodCode - The code of the selected payment method, if any.
     * @param {string} flow - The online payment flow of the selected payment option.
     * @return {void}
     */
    async _prepareInlineForm(providerId, providerCode, paymentOptionId, paymentMethodCode, flow) {
        if (providerCode !== 'payvector') {
            await super._prepareInlineForm(...arguments);
            return;
        }
        if (flow === 'token') {
            return;
        }

        const radio = document.querySelector('input[name="o_payment_radio"]:checked');
        const inlineForm = radio ? this._getInlineForm(radio) : null;

        // Read the mode written by the backend into data-payvector-mode.
        // DO NOT rely on the presence of #payvector_direct_form — that div is always
        // rendered in the template regardless of integration mode.
        const container = inlineForm ? inlineForm.querySelector('#payvector-direct-container') : null;
        const pvMode = container ? container.getAttribute('data-payvector-mode') : null;

        const updateFlow = () => {
            const hasSaved = container && container.getAttribute('data-has-saved-card') === '1';
            const savedRadio = inlineForm ? inlineForm.querySelector('input[name="payvector_saved_card"]:checked') : null;
            const isSaved = hasSaved && savedRadio && savedRadio.value === 'saved';
            if (pvMode === 'direct' || isSaved) {
                this._setPaymentFlow('direct');
            } else {
                this._setPaymentFlow('redirect');
            }
        };

        if (inlineForm) {
            const selectors = inlineForm.querySelectorAll('input[name="payvector_saved_card"]:not([type="hidden"])');
            selectors.forEach(el => {
                el.removeEventListener('change', updateFlow);
                el.addEventListener('change', updateFlow);
            });
        }
        updateFlow();
    },

    // #=== PAYMENT FLOW ===#

    /**
     * Process the direct payment flow for PayVector.
     *
     * @override method from @payment/interactions/payment_form
     * @private
     * @param {string} providerCode - The code of the selected payment option's provider.
     * @param {number} paymentOptionId - The id of the selected payment option.
     * @param {string} paymentMethodCode - The code of the selected payment method, if any.
     * @param {object} processingValues - The processing values of the transaction.
     * @return {void}
     */
    async _processDirectFlow(providerCode, paymentOptionId, paymentMethodCode, processingValues) {
        if (providerCode !== 'payvector') {
            await super._processDirectFlow(...arguments);
            return;
        }

        const radio = document.querySelector('input[name="o_payment_radio"]:checked');
        const inlineForm = radio ? this._getInlineForm(radio) : null;
        const container = inlineForm ? inlineForm.querySelector('#payvector-direct-container') : null;
        const pvMode = container ? container.getAttribute('data-payvector-mode') : null;

        const hasSaved = container && container.getAttribute('data-has-saved-card') === '1';
        const savedRadio = inlineForm ? inlineForm.querySelector('input[name="payvector_saved_card"]:checked') : null;
        const isSaved = hasSaved && savedRadio && savedRadio.value === 'saved';

        // Safety guard: if pvMode is hosted and it's not a saved card, redirect flow must be used
        if (pvMode !== 'direct' && !isSaved) {
            this._setPaymentFlow('redirect');
            return;
        }

        const directForm = inlineForm ? (inlineForm.querySelector('#payvector_direct_form') || inlineForm.querySelector('form')) : null;

        if (directForm) {
            // Update hidden values with transaction reference and provider ID if needed
            let refInput = directForm.querySelector('input[name="reference"]');
            if (refInput && processingValues.reference) {
                refInput.value = processingValues.reference;
            }
            let providerInput = directForm.querySelector('input[name="provider_id"]');
            if (providerInput && this.paymentContext.providerId) {
                providerInput.value = this.paymentContext.providerId;
            }

            // Populate 3DS v2 browser details before form submit
            const javaEl = directForm.querySelector('#pv_browser_java_enabled');
            if (javaEl) javaEl.value = navigator.javaEnabled ? navigator.javaEnabled() : false;
            const langEl = directForm.querySelector('#pv_browser_language');
            if (langEl) langEl.value = navigator.language || navigator.userLanguage || 'en-US';
            const depthEl = directForm.querySelector('#pv_browser_color_depth');
            if (depthEl) depthEl.value = screen.colorDepth || 24;
            const heightEl = directForm.querySelector('#pv_browser_screen_height');
            if (heightEl) heightEl.value = screen.height || 1080;
            const widthEl = directForm.querySelector('#pv_browser_screen_width');
            if (widthEl) widthEl.value = screen.width || 1920;
            const tzEl = directForm.querySelector('#pv_browser_tz');
            if (tzEl) tzEl.value = new Date().getTimezoneOffset();

            // Submit the card details directly to /payment/payvector/direct_payment
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/payment/payvector/direct_payment';

            // Collect all inputs and add them to the dynamic form
            const inputs = directForm.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.name && !input.disabled) {
                    if (input.type === 'radio' && !input.checked) return;
                    if (input.type === 'checkbox' && !input.checked) return;

                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = input.name;
                    hiddenField.value = input.value;
                    form.appendChild(hiddenField);
                }
            });

            document.body.appendChild(form);
            form.submit();
        } else {
            // Fallback to status if form is missing
            window.location = '/payment/status';
        }
    },

});
