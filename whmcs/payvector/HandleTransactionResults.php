<?php
function HandleTransactionResults($result, $processor, $params, $CallingFunction, $StoreGatewayID = false, $StoreCardLastFour = false)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $gateway = getGatewayVariables("payvector");
    $UserID = $params['clientdetails']['userid'];
    $OrderID = $params['invoiceid'];

    $cardType = isset($_REQUEST['payvector_saved_card']) ? $_REQUEST['payvector_saved_card'] : (isset($_GET['payvector_card_type']) ? $_GET['payvector_card_type'] : '');
    if (empty($cardType) && ($CallingFunction == "DisplayTransactionResult" || $CallingFunction == "ReceiveTransactionResult")) {
        $cardType = 'new';
    }
    $TransactionSuccessful = FALSE;
    $Results = array();

    if (!$result->transactionProcessed())
    {
        $systemUrl = preg_replace('/^https?:/', '', $gateway["systemurl"]);
        $errorMsg = $result->getErrorMessage();
        $redirecturl = $systemUrl . '/viewinvoice.php?id=' . $OrderID . '&paymentfailed=true&paymenterrormsg=' . urlencode($errorMsg) . '&payvector_card_type=' . urlencode($cardType);
        $Results["status"] = "failed";
        $Results['rawdata']['ErrorMessage'] = $errorMsg;
        
        $_SESSION['PaymentError'] = $errorMsg;
        $_SESSION['paymenterrormsg'] = $errorMsg;
        $_SESSION['paymenterror'] = $errorMsg;

        if ($CallingFunction != "payvector_storeremote")
        {
            logTransaction("Payvector Corporation", $Results['rawdata'], "error");
            sendMessage("Credit Card Payment failed", $OrderID);
        }
    }
    else
    {
        $CrossReference = $result->getCrossReference();
        $Results['rawdata']['InvoiceID'] = $OrderID;
        $Results['rawdata']['UserID'] = $UserID;
        $Results['rawdata']['Amount'] = $params['amount'];
        $Results['rawdata']['FunctionName'] = $CallingFunction;
        $Results['rawdata']['TransactionType'] = $CallingFunction == "3DSecureCallback" ? "SALE" : $params['paymentmethod'];
        $Results['rawdata']['CrossReference'] = $CrossReference;
        $Results['rawdata']['GatewayMessage'] = $result->getMessage();

        $statusCode = $result->getStatusCode();

        switch ($statusCode)
        {
            case 0 :
                $systemUrl = preg_replace('/^https?:/', '', rtrim($gateway["systemurl"], '/'));
                $redirecturl = $systemUrl . '/viewinvoice.php?id=' . $OrderID . '&paymentsuccess=true';
                $Results["status"] = "success";
                $TransactionSuccessful = TRUE;

                if ($CallingFunction != "payvector_storeremote")
                {
                    addInvoicePayment($OrderID, $CrossReference, $params['amount'], 0, "payvector", "on");
                    logTransaction("Payvector Corporation", $Results['rawdata'], "success");
                    sendMessage("Credit Card Payment Confirmation", $OrderID);
                }
                else
                {
                    $Results["gatewayid"] = $CrossReference;
                }

                if ($StoreGatewayID && (!isset($params['showSavedCards']) || $params['showSavedCards'] !== 'False'))
                {
                    $lastFour = $StoreCardLastFour ? $result->getCardLastFour($_SESSION) : null;
                    if (empty($lastFour) && isset($_SESSION['payvector_saved_last4'])) {
                        $lastFour = $_SESSION['payvector_saved_last4'];
                    }
                    if (empty($lastFour) && isset($_POST['CardNumberLastFour'])) {
                        $lastFour = $_POST['CardNumberLastFour'];
                    }
                    if (empty($lastFour) && isset($_POST['CardLastFour'])) {
                        $lastFour = $_POST['CardLastFour'];
                    }
                    if (empty($lastFour) && isset($_POST['CardLast4'])) {
                        $lastFour = $_POST['CardLast4'];
                    }
                    $cardType = $result->getCardType();
                    if (empty($cardType)) {
                        $cardType = 'Card';
                    }
                    $expiryDate = method_exists($result, 'getCardExpiryDate') ? $result->getCardExpiryDate() : null;

                    payvectorDatabase::saveCardPayMethod($UserID, $CrossReference, $lastFour, $expiryDate, $cardType);

                    
                    try {
                        payvectorDatabase::insertCrossReference($UserID, $CrossReference, $cardType, $lastFour);
                    } catch (\Exception $e) {
                        logTransaction("Payvector Corporation", ['Error' => $e->getMessage()], "error");
                    }
                }
                break;

            case 3 :
                $Is3DSecureEnrolled = TRUE;

                if ($CallingFunction != "payvector_storeremote")
                {
                    logTransaction("Payvector Corporation", $Results['rawdata'], "3DS Required");
                    
                    
                    payvectorDatabase::insert3DS_Transaction($CrossReference, $UserID, $params['currency'], $params['amount'], date('Y-m-d H:i:s P'));

                    $threeDSecureOutput = $result->getThreeDSecureOutputData();
                    if ($threeDSecureOutput && method_exists($threeDSecureOutput, 'getMethodURL') && $threeDSecureOutput->getMethodURL() != '') {
                        
                        $acsUrl = $threeDSecureOutput->getMethodURL();
                        $methodData = $threeDSecureOutput->getMethodData();
                        
                        $_SESSION['payvector_3ds_data'] = array(
                            'acs_url' => $acsUrl,
                            'target'  => 'threeDSecureFrame',
                            'params'  => array(
                                'ThreeDSMethodData' => $methodData
                            ),
                            'version' => '2',
                            'invoiceid' => $OrderID,
                            'crossref' => $CrossReference
                        );
                    } else {
                        
                        $acsUrl = $result->getThreeDSecureACSURL();
                        $paReq = $result->getThreeDSecurePaREQ();
                        
                        $_SESSION['payvector_3ds_data'] = array(
                            'acs_url' => $acsUrl,
                            'target'  => 'threeDSecureFrame',
                            'params'  => array(
                                'PaReq' => $paReq,
                                'TermUrl' => rtrim($gateway['systemurl'], '/') . '/modules/gateways/payvector/Callback/3DSecureCallback.php?payvector_card_type=' . $cardType,
                                'MD' => $CrossReference
                            ),
                            'version' => '1',
                            'invoiceid' => $OrderID,
                            'crossref' => $CrossReference
                        );
                    }

                    $acsUrlEsc = htmlspecialchars($_SESSION['payvector_3ds_data']['acs_url'], ENT_QUOTES, 'UTF-8');
                    $targetEsc = htmlspecialchars($_SESSION['payvector_3ds_data']['target'], ENT_QUOTES, 'UTF-8');
                    
                    $logoHtml = '';
                    $logoPath = __DIR__ . '/logo.png';
                    if (file_exists($logoPath)) {
                        $logoData = base64_encode(file_get_contents($logoPath));
                        $logoHtml = '<div class="logo" style="margin-bottom: 20px;"><img src="data:image/png;base64,' . $logoData . '" alt="Payvector Logo" style="max-height: 50px;" /></div>';
                    }

                    $Results = '<div style="text-align:center; padding: 20px;">
                        ' . $logoHtml . '
                        <h3>3D Secure Verification</h3>
                        <p>Please wait while we redirect you to your card issuer for verification...</p>
                        <iframe name="' . $targetEsc . '" width="100%" height="700" frameborder="0"></iframe>
                        <form id="threeds_form" action="' . $acsUrlEsc . '" method="POST" target="' . $targetEsc . '">';
                    
                    foreach ($_SESSION['payvector_3ds_data']['params'] as $k => $v) {
                        $Results .= '<input type="hidden" name="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '" />';
                    }
                    
                    $Results .= '</form>
                        <script type="text/javascript">
                            var form = document.getElementById("threeds_form");
                            if(form) form.submit();
                        </script>
                    </div>';
                }
                else
                {
                    $Results["status"] = "failed";
                }
                break;

            case 5 :
                $systemUrl = preg_replace('/^https?:/', '', $gateway["systemurl"]);
                $errorMsg = $result->getErrorMessage();
                $redirecturl = $systemUrl . '/viewinvoice.php?id=' . $OrderID . '&paymentfailed=true&paymenterrormsg=' . urlencode($errorMsg) . '&payvector_card_type=' . urlencode($cardType);
                $Results["status"] = "declined";
                
                $_SESSION['PaymentError'] = $errorMsg;
                $_SESSION['paymenterrormsg'] = $errorMsg;
                $_SESSION['paymenterror'] = $errorMsg;

                if ($CallingFunction != "payvector_storeremote")
                {
                    logTransaction("Payvector Corporation", $Results['rawdata'], "Declined");
                    sendMessage("Credit Card Payment failed", $OrderID);
                }
                break;

            case 20 :
                $systemUrl = preg_replace('/^https?:/', '', $gateway["systemurl"]);
                $Results["status"] = "success";
                $redirecturl = $systemUrl . '/viewinvoice.php?id=' . $OrderID . '&paymentsuccess=true';

                
                if ($result->duplicateTransaction())
                {
                    $TransactionSuccessful = TRUE;
                    $Results["status"] = "success";
                    addInvoicePayment($OrderID, $CrossReference, $params['amount'], 0, "payvector", "on");
                    sendMessage("Credit Card Payment Confirmation", $OrderID);
                }
                else
                {
                    $Results["status"] = "failed";
                    $errorMsg = $result->getErrorMessage();
                    $redirecturl = $systemUrl . '/viewinvoice.php?id=' . $OrderID . '&paymentfailed=true&paymenterrormsg=' . urlencode($errorMsg) . '&payvector_card_type=' . urlencode($cardType);
                    
                    $_SESSION['PaymentError'] = $errorMsg;
                    $_SESSION['paymenterrormsg'] = $errorMsg;
                    $_SESSION['paymenterror'] = $errorMsg;
                }

                logTransaction("Payvector Corporation", $Results['rawdata'], $Results["status"]);
                break;

            case 30 :
            default :
                $Results["status"] = "failed";
                $systemUrl = preg_replace('/^https?:/', '', $gateway["systemurl"]);
                $errorMsg = $result->getErrorMessage();
                $redirecturl = $systemUrl . '/viewinvoice.php?id=' . $OrderID . '&paymentfailed=true&paymenterrormsg=' . urlencode($errorMsg) . '&payvector_card_type=' . urlencode($cardType);
                
                $_SESSION['PaymentError'] = $errorMsg;
                $_SESSION['paymenterrormsg'] = $errorMsg;
                $_SESSION['paymenterror'] = $errorMsg;
                
                logTransaction("Payvector Corporation", $Results['rawdata'], "error");
                sendMessage("Credit Card Payment failed", $OrderID);
                break;
        }
    }

    $redirecthtml = '<html>
                        <head>
                            <title>' . htmlspecialchars($gateway["CompanyName"], ENT_QUOTES, 'UTF-8') . '</title>
                            <script type="text/javascript">top.location="' . $redirecturl . '";</script>
                        </head>
                        <body>
                            <p>Payment Processing Completed. Please wait while you are redirected back to the client area...</p>
                        </body>
                    </html>';

    switch ($CallingFunction)
    {
        case "payvector_capture" :
        case "payvector_3dsecure" :
            if (isset($Is3DSecureEnrolled) && $Is3DSecureEnrolled)
            {
                $return = $Results;
            }
            else
            {
                if ($Results['status'] != "failed")
                {
                    $return = $Results["status"];
                }
                else
                {
                    $return = $redirecthtml;
                }
            }
            break;
        case "ReceiveTransactionResult" :
        case "DisplayTransactionResult" :
            $return = $redirecthtml;
            break;
        case "3DSecureCallback" :
            $return = $redirecthtml;
            break;
        default :
            $return = $Results;
            break;
    }

    return $return;
}
