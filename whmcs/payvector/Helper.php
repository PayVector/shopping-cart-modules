<?php

if (!function_exists('payvector_getSavedCardDetails')) {
    function payvector_getSavedCardDetails($userId) {
        $saved_gateway_id = '';
        $last_four = '';
        $card_type = '';
        if ($userId <= 0) {
            return array('', '', '');
        }

        try {

            $paymethod = \WHMCS\Database\Capsule::table('tblpaymethods')
                ->join('tblcreditcards', 'tblpaymethods.id', '=', 'tblcreditcards.pay_method_id')
                ->where('tblpaymethods.userid', $userId)
                ->where('tblpaymethods.gateway', 'payvector')
                ->select('tblcreditcards.card_data as token', 'tblcreditcards.last_four', 'tblcreditcards.card_type')
                ->first();
            if ($paymethod && !empty($paymethod->token)) {
                $decrypted = function_exists('decrypt') ? decrypt($paymethod->token) : $paymethod->token;
                if (!empty($decrypted)) {
                    $saved_gateway_id = $decrypted;
                    $last_four = $paymethod->last_four;
                    $card_type = $paymethod->card_type;
                }
            }
        } catch (\Exception $e) {}

        try {

            if (empty($saved_gateway_id)) {
                $client = \WHMCS\Database\Capsule::table('tblclients')->where('id', $userId)->first();
                if ($client && !empty($client->gatewayid)) {
                    $saved_gateway_id = $client->gatewayid;
                    $last_four = $client->cardlastfour;
                    
                    try {
                        $ref = \WHMCS\Database\Capsule::table('payvector_cross_reference')
                            ->where('customer_id', $userId)
                            ->where('cross_reference', $saved_gateway_id)
                            ->first();
                        if ($ref && !empty($ref->card_type)) {
                            $card_type = $ref->card_type;
                        }
                    } catch (\Exception $e) {}
                }
            }
        } catch (\Exception $e) {}

        try {

            if (empty($saved_gateway_id)) {
                $ref = \WHMCS\Database\Capsule::table('payvector_cross_reference')
                    ->where('customer_id', $userId)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($ref && !empty($ref->cross_reference)) {
                    $saved_gateway_id = $ref->cross_reference;
                    $last_four = $ref->last_four;
                    $card_type = $ref->card_type;
                }
            }
        } catch (\Exception $e) {}

        return array($saved_gateway_id, $last_four, $card_type);
    }
}

if (!function_exists('getMerchantDetails')) {
    function getMerchantDetails($params, $CallingFunction)
    {
        return array(
            "DebugMode" => false,
            "MerchantID" => $params['merchantid'],
            "Password" => $params['password']
        );
    }
}

if (!function_exists('payvector_getISOCountryCode')) {
    function payvector_getISOCountryCode($country_code) {
        require_once (__DIR__ . '/payvector/ISOHelper.php');
        $iso_country_list = \ISOHelper::getISOCountryList();
        if (!empty($country_code) && $iso_country_list->getISOCountry($country_code, $iso_country)) {
            return $iso_country->getISOCode();
        }
        return '';
    }
}

if (!function_exists('payvector_setAmountFromCurrency')) {
    function payvector_setAmountFromCurrency($currency_code, $amount) {
        require_once (__DIR__ . '/payvector/ISOHelper.php');
        $amount = number_format($amount, 2, '.', '');
        $iso_currency_list = \ISOHelper::getISOCurrencyList();
        $isoCurrencyCode = '';
        
        if (!empty($currency_code) && $iso_currency_list->getISOCurrency($currency_code, $iso_currency)) {
            $isoCurrencyCode = $iso_currency->getISOCode();
            $amount = (string) $amount;
            $amount = round($amount * ("1" . str_repeat(0, $iso_currency->getExponent())));
        }
        
        return array($isoCurrencyCode, $amount);
    }
}
if (!function_exists('payvector_getCommonStylesAndLogo')) {
    function payvector_getCommonStylesAndLogo() {
        $html = '<style>
            .payment-btn-container { width: 100% !important; max-width: 100% !important; display: block !important; clear: both !important; }
            .invoice-container .row .col-md-5, .invoice-container .row .col-sm-5, .invoice-sidebar { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
            .invoice-container .row .col-md-7, .invoice-container .row .col-sm-7 { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
        </style>';
        $logoPath = __DIR__ . '/logo.png';
        if (file_exists($logoPath)) {
            $html .= '<div class="logo" style="margin-bottom: 20px; text-align: center;"><img src="data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) . '" alt="Payvector Logo" style="max-height: 50px;" /></div>';
        }
        return $html;
    }
}

if (!function_exists('payvector_getHostedFormHtml')) {
    function payvector_getHostedFormHtml($params) {
        require_once (__DIR__ . '/payvector/TransactionProcessor.php');
        require_once (__DIR__ . '/payvector/ISOHelper.php');
        require_once (__DIR__ . '/payvector/PaymentFormHelper.php');

        $tp = new \TransactionProcessor();
        $MerchantDetails = getMerchantDetails($params, 'payvectorhosted_link');
        $tp->setMerchantID($MerchantDetails['MerchantID']);
        $tp->setMerchantPassword($MerchantDetails['Password']);


        $paymentProcessorDomain = "payvector.net";
        $rgepl = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();      
        $rgepl->add("https://gw1." . $paymentProcessorDomain, 100, 2);
        $rgepl->add("https://gw2." . $paymentProcessorDomain, 200, 2);
        $rgepl->add("https://gw3." . $paymentProcessorDomain, 300, 2);
        $tp->setRgeplRequestGatewayEntryPointList($rgepl);

        $Amount = $params['amount'] ? $params['amount'] : 0.01;
        list($isoCurrency, $scaledAmount) = payvector_setAmountFromCurrency($params['currency'], $Amount);

        $tp->setCurrencyCode($isoCurrency);
        $tp->setAmount($scaledAmount);
        $tp->setOrderID($params['invoiceid']);
        $tp->setOrderDescription($params['companyname'] . ": Invoice " . $params['invoiceid']);
        $tp->setTransactionType('SALE');

        $tp->setCustomerName("{$params['clientdetails']['firstname']} {$params['clientdetails']['lastname']}");
        $tp->setAddress1($params['clientdetails']['address1']);
        $tp->setAddress2($params['clientdetails']['address2']);
        $tp->setAddress3($params['clientdetails']['address3']);
        $tp->setAddress4($params['clientdetails']['address4']);
        $tp->setCity($params['clientdetails']['city']);
        $tp->setState($params['clientdetails']['state']);
        $tp->setPostcode($params['clientdetails']['postcode']);
        $tp->setCountryCode(payvector_getISOCountryCode($params['clientdetails']['country']));
        $tp->setEmailAddress($params['clientdetails']['email']);
        $tp->setPhoneNumber($params['clientdetails']['phone']);
        $tp->setIPAddress(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');

        $systemUrl = rtrim($params['systemurl'], '/');
        $callbackUrl = $systemUrl . '/modules/gateways/payvector/Callback/Hosted/DisplayTransactionResult.php';
        
        $serverResultUrl = '';
        $paymentFormDisplaysResult = true;
        if ($params['hpfResultDeliveryMethod'] === 'SERVER') {
            $serverResultUrl = $systemUrl . '/modules/gateways/payvector/Callback/Hosted/ReceiveTransactionResult.php';
            $paymentFormDisplaysResult = false;
        }

        $formFields = $tp->getHostedPaymentForm(
            $callbackUrl,
            $serverResultUrl,
            $params['pskPreSharedKey'],
            $params['hpfHashMethod'],
            $params['hpfResultDeliveryMethod'],
            $paymentFormDisplaysResult,
            $_SESSION
        );



        $html = payvector_getCommonStylesAndLogo();

        
        $userId = isset($params['clientdetails']['userid']) ? $params['clientdetails']['userid'] : (isset($_SESSION['uid']) ? $_SESSION['uid'] : 0);
        list($saved_gateway_id, $last_four, $card_type) = payvector_getSavedCardDetails($userId);

        $showSavedCards = (!isset($params['showSavedCards']) || $params['showSavedCards'] !== 'False');
        $has_saved_card = ($showSavedCards && !empty($saved_gateway_id));

        if ($has_saved_card) {
            $directActionUrl = $systemUrl . '/modules/gateways/payvector/Callback/DirectPayment.php';
            $hostedActionUrl = 'https://mms.payvector.net/Pages/PublicPages/PaymentForm.aspx';
            
            $attempted_card_type = isset($_GET['payvector_card_type']) ? $_GET['payvector_card_type'] : '';
            $paymentErrorMsg = isset($_GET['paymenterrormsg']) ? $_GET['paymenterrormsg'] : '';
            $selected_card = 'saved';
            if (!empty($attempted_card_type)) {
                $selected_card = $attempted_card_type;
            }

            $html .= '<div id="payvector-hosted-container" style="text-align: left; width: 100%; margin: 30px auto; padding: 25px; border: 1px solid #e0e0e0; border-radius: 8px; background: #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">';
            $html .= '<h4 style="margin: 0 0 20px 0; color: #333333; font-weight: 600; text-align: center;">Pay with Credit Card</h4>';
            
            if (!empty($paymentErrorMsg)) {
                $html .= '<div class="alert alert-danger text-center" style="margin-bottom: 20px; font-weight: bold; font-size: 14px;">' . htmlspecialchars($paymentErrorMsg) . '</div>';
            }

            $html .= '<form method="POST" action="' . htmlspecialchars($directActionUrl) . '" id="payvector_hosted_combined_form">';
            $html .= '<input type="hidden" name="invoiceid" value="' . htmlspecialchars($params['invoiceid']) . '" />';
            
            
            $html .= '<div style="margin-bottom: 15px;">';
            $html .= '<label style="font-weight: normal; cursor: pointer; display: block;">';
            $html .= '<input type="radio" name="payvector_saved_card" value="saved" class="payvector-hosted-selector" style="margin-right: 10px;"' . ($selected_card === 'saved' ? ' checked' : '') . ' /> ';
            $card_display_info = 'Use saved card ending in ' . htmlspecialchars($last_four);
            if (!empty($card_type)) {
                $card_display_info .= ' (' . htmlspecialchars($card_type) . ')';
            }
            $html .= $card_display_info;
            $html .= '</label>';
            
            $saved_cvv_style = $selected_card === 'saved' ? 'display: block;' : 'display: none;';
            $html .= '<div id="payvector-saved-cvv" style="margin-top: 10px; margin-left: 25px; ' . $saved_cvv_style . '">';
            $html .= 'CVV <input type="text" name="payvector_saved_cc_cvv" size="4" maxlength="4" style="width: 60px; display: inline-block; margin-left: 10px;" class="form-control input-sm" />';
            $html .= '</div>';
            $html .= '</div>';

            
            $html .= '<div style="margin-bottom: 15px;">';
            $html .= '<label style="font-weight: normal; cursor: pointer; display: block;">';
            $html .= '<input type="radio" name="payvector_saved_card" value="new" class="payvector-hosted-selector" style="margin-right: 10px;"' . ($selected_card === 'new' ? ' checked' : '') . ' /> ';
            $html .= 'Redirect to Payment Form (New Card)';
            $html .= '</label>';
            
            $redirect_msg_style = $selected_card === 'new' ? 'display: block;' : 'display: none;';
            $html .= '<div id="payvector-redirect-msg" style="margin-top: 10px; margin-left: 25px; ' . $redirect_msg_style . '">';
            $html .= '<p style="color: #666666; font-size: 14px; line-height: 1.5; margin-bottom: 10px;">You will be securely redirected to the Payvector payment processor to enter your card details.</p>';
            $html .= '</div>';
            $html .= '</div>';

            
            $html .= '<div id="payvector-hpf-inputs-container">';
            foreach ($formFields as $key => $value) {
                $html .= '<input type="hidden" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" />';
            }
            $html .= '</div>';

            $html .= '<div style="text-align: center; margin-top: 25px;">';
            $html .= '<input type="submit" value="Pay Now" class="btn btn-success btn-lg" style="min-width: 180px; font-weight: bold; padding: 12px 30px;" />';
            $html .= '</div>';
            $html .= '</form>';
            $html .= '</div>';

            $html .= '<script type="text/javascript">
                function togglePayVectorHostedFields() {
                    var selected = document.querySelector(\'input[name="payvector_saved_card"]:checked\');
                    if (!selected) return;
                    var isNew = (selected.value === "new");
                    var form = document.getElementById("payvector_hosted_combined_form");
                    var savedCvvField = document.getElementById("payvector-saved-cvv");
                    var redirectMsg = document.getElementById("payvector-redirect-msg");
                    var hpfContainer = document.getElementById("payvector-hpf-inputs-container");
                    
                    if (isNew) {
                        if (form) form.action = "' . htmlspecialchars($hostedActionUrl) . '";
                        if (savedCvvField) savedCvvField.style.display = "none";
                        if (redirectMsg) redirectMsg.style.display = "block";
                        if (hpfContainer) {
                            var inputs = hpfContainer.getElementsByTagName("input");
                            for (var i = 0; i < inputs.length; i++) {
                                inputs[i].disabled = false;
                            }
                        }
                    } else {
                        if (form) form.action = "' . htmlspecialchars($directActionUrl) . '";
                        if (savedCvvField) savedCvvField.style.display = "block";
                        if (redirectMsg) redirectMsg.style.display = "none";
                        if (hpfContainer) {
                            var inputs = hpfContainer.getElementsByTagName("input");
                            for (var i = 0; i < inputs.length; i++) {
                                inputs[i].disabled = true;
                            }
                        }
                    }
                }
                
                function attachPayVectorHostedEvents() {
                    var selectors = document.querySelectorAll(".payvector-hosted-selector");
                    for (var i = 0; i < selectors.length; i++) {
                        selectors[i].addEventListener("change", togglePayVectorHostedFields);
                    }
                    togglePayVectorHostedFields();
                }
                
                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", attachPayVectorHostedEvents);
                } else {
                    attachPayVectorHostedEvents();
                }
            </script>';
            
            return $html;
        }

        $html .= '<div style="text-align: center; padding: 35px 20px; font-family: sans-serif; border: 1px solid #e0e0e0; border-radius: 8px; background: #ffffff; width: 100%; margin: 30px auto; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">';
        $html .= '<h4 style="margin: 15px 0 10px 0; color: #333333; font-weight: 600;">Secure Hosted Payment</h4>
            <p style="color: #666666; font-size: 14px; line-height: 1.5; margin-bottom: 25px;">Please click the button below to be securely redirected to the Payvector payment processor to complete your payment.</p>
            <form action="https://mms.payvector.net/Pages/PublicPages/PaymentForm.aspx" method="post" id="payvector_hosted_redirect_form">';
        foreach ($formFields as $key => $value) {
            $html .= '<input type="hidden" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" />';
        }
        $html .= '<input type="submit" value="Pay Now" class="btn btn-success btn-lg" style="min-width: 180px; font-weight: bold; padding: 12px 30px;" />';
        $html .= '</form>
        </div>';

        return $html;
    }
}

if (!function_exists('payvector_getDirectApiFieldsHtml')) {
    function payvector_getDirectApiFieldsHtml($params) {
        $showSavedCards = (!isset($params['showSavedCards']) || $params['showSavedCards'] !== 'False');
        $attempted_card_type = isset($_GET['payvector_card_type']) ? $_GET['payvector_card_type'] : '';
        $paymentErrorMsg = isset($_GET['paymenterrormsg']) ? $_GET['paymenterrormsg'] : '';
        
        $html = '<div id="payvector-direct-fields-container" style="text-align: left; width: 100%; margin-top: 15px;">';
        
        if (!empty($paymentErrorMsg)) {
            $html .= '<div class="alert alert-danger text-center" style="margin-bottom: 20px; font-weight: bold; font-size: 14px;">' . htmlspecialchars($paymentErrorMsg) . '</div>';
        }
        

        $userId = isset($params['clientdetails']['userid']) ? $params['clientdetails']['userid'] : (isset($_SESSION['uid']) ? $_SESSION['uid'] : 0);
        list($saved_gateway_id, $last_four, $card_type) = payvector_getSavedCardDetails($userId);
        
        $has_saved_card = ($showSavedCards && !empty($saved_gateway_id));
        $selected_card = $has_saved_card ? 'saved' : 'new';
        if (!empty($attempted_card_type)) {
            $selected_card = $attempted_card_type;
        }
        
        if ($has_saved_card) {
            $html .= '<div style="margin-bottom: 15px;">';
            $html .= '<label style="font-weight: normal; cursor: pointer; display: block;">';
            $html .= '<input type="radio" name="payvector_saved_card" value="saved" class="payvector-card-selector" style="margin-right: 10px;"' . ($selected_card === 'saved' ? ' checked' : '') . ' /> ';
            
            $card_display_info = 'Use saved card ending in ' . htmlspecialchars($last_four);
            if (!empty($card_type)) {
                $card_display_info .= ' (' . htmlspecialchars($card_type) . ')';
            }
            $html .= $card_display_info;
            $html .= '</label>';
            
            $saved_cvv_style = $selected_card === 'saved' ? 'display: block;' : 'display: none;';
            $html .= '<div id="payvector-saved-cvv" class="payvector-saved-cvv" style="margin-top: 10px; margin-left: 25px; ' . $saved_cvv_style . '">';
            $html .= 'CVV <input type="text" name="payvector_saved_cc_cvv" size="4" maxlength="4" style="width: 60px; display: inline-block; margin-left: 10px;" class="form-control input-sm" />';
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '<div style="margin-bottom: 15px;">';
            $html .= '<label style="font-weight: normal; cursor: pointer; display: block;">';
            $html .= '<input type="radio" name="payvector_saved_card" value="new" class="payvector-card-selector" style="margin-right: 10px;"' . ($selected_card === 'new' ? ' checked' : '') . ' /> ';
            $html .= 'New Card';
            $html .= '</label>';
            $html .= '</div>';
        } else {
            $html .= '<input type="hidden" name="payvector_saved_card" value="new" />';
        }
        
        $new_card_style = $selected_card === 'new' ? 'display: block;' : 'display: none;';
        
        $html .= '<div id="payvector-new-card" class="payvector-new-card" style="' . $new_card_style . ' padding-left: 25px;">';
        
        $firstName = isset($params['clientdetails']['firstname']) ? $params['clientdetails']['firstname'] : '';
        $lastName = isset($params['clientdetails']['lastname']) ? $params['clientdetails']['lastname'] : '';
        $cc_owner = htmlspecialchars(trim($firstName . ' ' . $lastName));
        $html .= '<div style="margin-bottom: 15px;">';
        $html .= '<label style="display: block; margin-bottom: 5px; color: #555;">Credit Card Owner</label>';
        $html .= '<input type="text" name="payvector_cc_owner" value="' . $cc_owner . '" class="form-control" style="width: 100%; max-width: 350px;" />';
        $html .= '</div>';
        
        $html .= '<div style="margin-bottom: 15px;">';
        $html .= '<label style="display: block; margin-bottom: 5px; color: #555;">Credit Card Number</label>';
        $html .= '<input type="text" name="ccnumber" autocomplete="off" class="form-control" style="width: 100%; max-width: 350px;" />';
        $html .= '</div>';
        
        $html .= '<div style="margin-bottom: 15px;">';
        $html .= '<label style="display: block; margin-bottom: 5px; color: #555;">Expiry Date</label>';
        $html .= '<div style="display: flex; gap: 10px; align-items: center;">';
        $html .= '<select name="ccexpirymonth" class="form-control" style="width: auto;">';
        for ($i = 1; $i <= 12; $i++) {
            $m = sprintf('%02d', $i);
            $html .= '<option value="' . $m . '">' . $m . '</option>';
        }
        $html .= '</select> <span>/</span> ';
        $html .= '<select name="ccexpiryyear" class="form-control" style="width: auto;">';
        $currentYear = (int)date('y');
        $currentFullYear = (int)date('Y');
        for ($i = 0; $i < 10; $i++) {
            $y = sprintf('%02d', $currentYear + $i);
            $fy = $currentFullYear + $i;
            $html .= '<option value="' . $y . '">' . $fy . '</option>';
        }
        $html .= '</select>';
        $html .= '</div></div>';
        
        $html .= '<div style="margin-bottom: 15px;">';
        $html .= '<label style="display: block; margin-bottom: 5px; color: #555;">CVV</label>';
        $html .= '<input type="text" name="cccvv" size="4" maxlength="4" autocomplete="off" style="width: 80px;" class="form-control" />';
        $html .= '</div>';
        
        $html .= '</div>';
        
        $html .= '<input type="hidden" name="payvector_browser_java_enabled" id="payvector_browser_java_enabled" value="">
        <input type="hidden" name="payvector_browser_language" id="payvector_browser_language" value="">
        <input type="hidden" name="payvector_browser_color_depth" id="payvector_browser_color_depth" value="">
        <input type="hidden" name="payvector_browser_screen_height" id="payvector_browser_screen_height" value="">
        <input type="hidden" name="payvector_browser_screen_width" id="payvector_browser_screen_width" value="">
        <input type="hidden" name="payvector_browser_tz" id="payvector_browser_tz" value="">
        <input type="hidden" name="payvector_browser_user_agent" id="payvector_browser_user_agent" value="">';
        
        $html .= '</div>';
        
        $html .= '<script type="text/javascript">
            function togglePayVectorFields() {
                var selected = document.querySelector(\'input[name="payvector_saved_card"]:checked\');
                if (!selected) return;
                var isNew = (selected.value === "new");
                var newCardFields = document.getElementById("payvector-new-card");
                var savedCvvField = document.getElementById("payvector-saved-cvv");
                
                if (newCardFields) newCardFields.style.display = isNew ? "block" : "none";
                if (savedCvvField) savedCvvField.style.display = isNew ? "none" : "block";
            }
            
            function getPayVector3DSv2Params() {
                if (document.getElementById("payvector_browser_java_enabled")) document.getElementById("payvector_browser_java_enabled").value = navigator.javaEnabled();
                if (document.getElementById("payvector_browser_language")) document.getElementById("payvector_browser_language").value = navigator.language || navigator.userLanguage;
                if (document.getElementById("payvector_browser_color_depth")) document.getElementById("payvector_browser_color_depth").value = screen.colorDepth;
                if (document.getElementById("payvector_browser_screen_height")) document.getElementById("payvector_browser_screen_height").value = screen.height;
                if (document.getElementById("payvector_browser_screen_width")) document.getElementById("payvector_browser_screen_width").value = screen.width;
                if (document.getElementById("payvector_browser_tz")) document.getElementById("payvector_browser_tz").value = new Date().getTimezoneOffset();
                if (document.getElementById("payvector_browser_user_agent")) document.getElementById("payvector_browser_user_agent").value = navigator.userAgent;
            }
            
            function attachPayVectorEvents() {
                var selectors = document.querySelectorAll(".payvector-card-selector");
                for (var i = 0; i < selectors.length; i++) {
                    selectors[i].addEventListener("change", togglePayVectorFields);
                }
                togglePayVectorFields();
                getPayVector3DSv2Params();
            }
            
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", attachPayVectorEvents);
            } else {
                attachPayVectorEvents();
            }
        </script>';
        
        return $html;
    }
}

if (!function_exists('payvector_getDirectApiHtml')) {
    function payvector_getDirectApiHtml($params) {
        $systemUrl = rtrim($params['systemurl'], '/');
        $actionUrl = $systemUrl . '/modules/gateways/payvector/Callback/DirectPayment.php';
        
        $html = payvector_getCommonStylesAndLogo();
        $html .= '<div id="payvector-direct-container" style="text-align: left; width: 100%; margin: 30px auto; padding: 25px; border: 1px solid #e0e0e0; border-radius: 8px; background: #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">';

        
        $html .= '<h4 style="margin: 0 0 20px 0; color: #333333; font-weight: 600; text-align: center;">Pay with Credit Card</h4>';
        
        $html .= '<form method="POST" action="' . htmlspecialchars($actionUrl) . '" id="payvector_direct_form">';
        $html .= '<input type="hidden" name="invoiceid" value="' . htmlspecialchars($params['invoiceid']) . '" />';
        
        $html .= payvector_getDirectApiFieldsHtml($params);
        
        $html .= '<div style="text-align: center; margin-top: 25px;">';
        $html .= '<input type="submit" value="Pay Now" class="btn btn-success btn-lg" style="min-width: 180px; font-weight: bold; padding: 12px 30px;" />';
        $html .= '</div>';
        
        $html .= '</form>';
        $html .= '</div>';
        
        return $html;
    }
}
