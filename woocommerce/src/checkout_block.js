const settings_pv = window.wc.wcSettings.getSetting('payvector_data', {});
const { createElement, useEffect } = window.wp.element;
const { __ } = window.wp.i18n;
const label_pv = window.wp.htmlEntities.decodeEntities(settings_pv.title) || window.wp.i18n.__('PayVector for woocommerce', 'payvector');

function get3DSv2Params() {
    const setValue = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.value = value;
    };

    setValue("browserJavaEnabled", navigator.javaEnabled());
    setValue("browserLanguage", navigator.language || navigator.userLanguage);
    setValue("browserColorDepth", screen.colorDepth);
    setValue("browserScreenHeight", screen.height);
    setValue("browserScreenWidth", screen.width);
    setValue("browserTZ", new Date().getTimezoneOffset());
    setValue("browserUserAgent", navigator.userAgent);
}

const Content_pv = props => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup, onPaymentProcessing } = eventRegistration;

    useEffect(() => {
        get3DSv2Params();
        initializePayvectorUIBehavior();
        const handler = onPaymentSetup || onPaymentProcessing;

        const unsubscribe = handler(async () => {

            const formData = getPayvectorFormData();

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: formData,
                },
            };
        });
        return () => {
            unsubscribe();
        };
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup,
        onPaymentProcessing,
    ]);



    return createElement('div', {
        id: 'payvector-form-wrapper',
        dangerouslySetInnerHTML: { __html: settings_pv.html || '' }
    });
};

const getPayvectorFormData = () => {
    const wrapper = document.getElementById('payvector-form-wrapper');
    const inputs = wrapper ? wrapper.querySelectorAll('input, select, textarea') : [];
    const formData = {};

    inputs.forEach(input => {
        if (!input.name) return;

        if (input.type === 'radio') {
            // Only store the value if it's checked and hasn't already been set
            if (input.checked) {
                formData[input.name] = input.value;
            }
        } else {
            formData[input.name] = input.value;
        }
    });

    return formData;
};

const Block_Gateway_Pv = {
    name: 'payvector',
    label: label_pv,
    content: createElement(Content_pv),
    edit: createElement(Content_pv),
    canMakePayment: () => true,
    ariaLabel: label_pv,
    supports: {
        features: ['products', 'blocks', 'card', 'tokenization'],
    }
};

function initializePayvectorUIBehavior() {

    const $ = window.jQuery;

    const setupFields = () => {

        const paymentType = $("input[name='payment_type']");
        const storedCard = $("#storedCard");
        const newCard = $("#newCard");
        const cardDetailsInput = $(".pay_vector_cc_details");
        const captureMethod = $('#captureMethod');
        const cvvContainer = $(".pay_vector_cvv_sc");
        const cvv = $("#cvv");
        const requiredField = $("#payvector-required");
        const payvectorTable = $('#payvector-table');

        const hostedPaymentFormValue = 'Hosted Payment Form';


        if (captureMethod.val() === hostedPaymentFormValue) {
            cardDetailsInput.hide();
            cvvContainer.show();

            if (paymentType.val() === "new_card") {
                cvvContainer.hide();
                requiredField.hide();
                cvv.prop('disabled', true);
            }

            paymentType.on("click", function () {

                if ($(this).val() === "new_card") {
                    payvectorTable.hide();
                    cvvContainer.hide();
                    requiredField.hide();
                    cvv.prop('disabled', true);
                } else {
                    payvectorTable.show();
                    cvv.prop('disabled', false);
                    cvvContainer.show();
                    requiredField.show();
                }
            });
        } else {
            if (storedCard.is(":checked")) {
                cardDetailsInput.hide();
                cvvContainer.show();
            }

            paymentType.on("change", function () {
                if ($(this).val() === "new_card") {
                    cardDetailsInput.show();
                    cvvContainer.hide();
                } else {
                    cardDetailsInput.hide();
                    cvvContainer.show();
                }
            });

        }

    };

    // Run immediately
    setupFields();

    // Also re-run on ajaxComplete (if relevant)
    $(document).on("ajaxComplete", setupFields);
}

window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Pv);
