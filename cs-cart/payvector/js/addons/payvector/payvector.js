(function (_, $) {
    window.payvectorTogglePaymentType = function (selected) {

        var $cardDetailsInput = $(".payvector-card-details");
        var $cvvContainer = $(".payvector-cvv");
        var $cvvStored = $("#sc_payvector_cc_cvv2");

        if (selected === "new_card") {
            $cardDetailsInput.show();
            $cvvContainer.hide();
            $cvvStored.removeClass("cm-required").prop("disabled", true);
            $cardDetailsInput.find("input").prop("disabled", false);
        } else {
            $cardDetailsInput.hide();
            $cvvContainer.show();
            $cvvStored.addClass("cm-required").prop("disabled", false);
            $cardDetailsInput.find("input").prop("disabled", true);
        }
    };

    window.bindPayvector = function () {

        var $radios = $("input[name='pv_info[payvector_payment_type]']");
        if (!$radios.length) {
            console.log("Payvector radios not found");
            return;
        }

        $radios.off("change.payvector");

        // Bind fresh handlers
        $radios.on("change.payvector", function () {
            console.log("Payvector change:", this.value);
            window.payvectorTogglePaymentType(this.value);
        });

        // Trigger initial
        $radios.filter(":checked").trigger("change.payvector");
    };


    $.ceEvent('on', 'ce.commoninit', function () {
        window.bindPayvector();
    });

})(Tygh, Tygh.$);
