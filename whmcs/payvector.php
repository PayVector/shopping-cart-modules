<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function payvector_MetaData()
{
    return array(
        'DisplayName' => 'Payvector',
        'APIVersion' => '1.1',
    );
}

function payvector_config()
{
    if (!function_exists('getMerchantDetails')) {
        require_once (__DIR__ . '/payvector/Helper.php');
    }
    require_once (__DIR__ . '/payvector/payvector/TransactionProcessor.php');
    require_once (__DIR__ . '/payvector/payvector/ISOHelper.php');
    require_once (__DIR__ . '/payvector/payvector/PaymentFormHelper.php');

    require_once (__DIR__ . '/payvector/Config.php');

    $config = array(
        "FriendlyName" => array(
            "Type" => "System",
            "Value" => "Payvector"
        ),
        "gatewaymode" => array(
            "FriendlyName" => "Payment Gateway Mode",
            "Description" => "Select the payment mode (Direct API or Hosted Payment Form)<script>
(function() {
    function togglePayvectorFields() {
        var modeSelect = document.querySelector('[name$=\"[gatewaymode]\"], [name=\"gatewaymode\"]');
        if (!modeSelect) return;
        
        var isDirect = (modeSelect.value === 'Direct API');
        
        var fieldsToToggle = [
            'pskPreSharedKey',
            'hpfHashMethod',
            'hpfResultDeliveryMethod'
        ];
        
        fieldsToToggle.forEach(function(fieldName) {
            var field = document.querySelector('[name$=\"[' + fieldName + ']\"], [name=\"' + fieldName + '\"]');
            if (field) {
                var row = field.closest('tr');
                if (row) {
                    row.style.display = isDirect ? 'none' : '';
                }
            }
        });
    }
    
    var modeSelect = document.querySelector('[name$=\"[gatewaymode]\"], [name=\"gatewaymode\"]');
    if (modeSelect) {
        modeSelect.addEventListener('change', togglePayvectorFields);
    }
    
    togglePayvectorFields();
    document.addEventListener('DOMContentLoaded', togglePayvectorFields);
    
    var count = 0;
    var interval = setInterval(function() {
        togglePayvectorFields();
        if (++count > 10) clearInterval(interval);
    }, 200);
})();
</script>",
            "Type" => "dropdown",
            "Options" => "Direct API,Hosted Payment Form",
            "Default" => "Hosted Payment Form"
        ),
        "merchantid" => array(
            "FriendlyName" => "Merchant ID",
            "Type" => "text",
            "Size" => "20",
            "Default" => "",
            "Description" => "Enter your Merchant ID"
        ),
        "password" => array(
            "FriendlyName" => "Password",
            "Type" => "password",
            "Default" => "",
            "Description" => "Enter your Merchant Account Password"
        ),
        
        "pskPreSharedKey" => array(
            "FriendlyName" => "Hosted Form Pre Shared Key",
            "Type" => "text",
            "Description" => "Required for Hosted Payment Form"
        ),
        "hpfHashMethod" => array(
            "FriendlyName" => "Hosted Form Hash Method",
            "Type" => "dropdown",
            "Options" => "SHA1,MD5,HMACSHA1,HMACMD5",
            "Default" => "SHA1"
        ),
        "hpfResultDeliveryMethod" => array(
            "FriendlyName" => "Hosted Form Result Delivery Method",
            "Type" => "dropdown",
            "Options" => "POST,SERVER_PULL",
            "Default" => "POST"
        ),
        "showSavedCards" => array(
            "FriendlyName" => "Saved Cards",
            "Type" => "dropdown",
            "Options" => "True,False",
            "Default" => "True",
            "Description" => "Do you want to show saved cards?"
        )
    );
    return $config;
}

function payvector_storeremote($params)
{

    if (strtolower($params['action']) === "delete")
    {
        $Results["status"] = "success";
        $Results['rawdata']['RemoteAction'] = $params['action'];
        $Results['rawdata']['GatewayInformation'] = "Payvector Corporation does not support this action.";
    }
    else
    {
        $Results = payvector_process($params, __FUNCTION__, "PREAUTH");

        if ($Results['status'] != "success")
        {
            $_SESSION['StoreRemoteResultStatus'] = $Results['status'];
        }
        else
        {
            $_SESSION['StoreRemoteResultStatus'] = null;
        }

        if ($Results['gatewayid'] != NULL && $Results['gatewayid'] != $params['gatewayid'] && $Results['status'] == "success")
        {
            $params['gatewayid'] = $Results['gatewayid'];
            $VOID = payvector_process($params, __FUNCTION__, "VOID");
        }
    }
    return $Results;
}

function payvector_capture($params)
{
    $showSavedCards = isset($params['showSavedCards']) ? $params['showSavedCards'] : 'True';
    if (isset($params['gatewaymode']) && $params['gatewaymode'] === 'Hosted Payment Form') {
        if ($showSavedCards !== 'False' && isset($params['gatewayid']) && !empty($params['gatewayid'])) {
            $Result = payvector_process($params, __FUNCTION__, "SALE");
            return $Result;
        }
        return array(
            'status' => 'declined',
            'rawdata' => array('message' => 'Hosted Payment Form mode does not support direct capture.')
        );
    }
    $Result = payvector_process($params, __FUNCTION__, "SALE");
    return $Result;
}

function payvector_link($params)
{
    if (basename($_SERVER['SCRIPT_NAME']) === 'cart.php') {
        return '<script>window.location.href="viewinvoice.php?id=' . (int)$params['invoiceid'] . '";</script>';
    }

    if (basename($_SERVER['SCRIPT_NAME']) !== 'viewinvoice.php') {
        return '';
    }

    if (isset($params['gatewaymode']) && $params['gatewaymode'] === 'Direct API') {
        if (!function_exists('payvector_getDirectApiHtml')) {
            require_once (__DIR__ . '/payvector/Helper.php');
        }
        return payvector_getDirectApiHtml($params);
    }
    
    if (!function_exists('payvector_getHostedFormHtml')) {
        require_once (__DIR__ . '/payvector/Helper.php');
    }
    return payvector_getHostedFormHtml($params);
}

function payvector_nolocalcc($params = array())
{
    try {
        $mode = \WHMCS\Database\Capsule::table('tblpaymentgateways')
            ->where('gateway', 'payvector')
            ->where('setting', 'gatewaymode')
            ->value('value');
        if ($mode === 'Hosted Payment Form') {
            return true;
        }
    } catch (\Exception $e) {}

    if (isset($params['gatewaymode']) && $params['gatewaymode'] === 'Hosted Payment Form') {
        return true;
    }
    return false;
}

function payvector_3dsecure($params)
{

    if (!isset($_SESSION['StoreRemoteResultStatus']))
    {
        $Result = payvector_process($params, __FUNCTION__, "SALE");
        return $Result;
    }
    else
    {
        $Result = $_SESSION['StoreRemoteResultStatus'];
        $_SESSION['StoreRemoteResultStatus'] = null;
        return $Result;
    }
}

function payvector_refund($params)
{
    $Result = payvector_process($params, __FUNCTION__, "REFUND");
    return $Result;
}

function getAmount($Amount, $nExponent, $GetDecimalisedAmount)
{

    $nAmount = 0;

    $Amount = round($Amount, $nExponent);
    $Power = pow(10, $nExponent);
    if ($GetDecimalisedAmount)
    {
        $nAmount = $Amount / $Power;
    }
    else
    {
        $nAmount = $Amount * $Power;
    }

    return $nAmount;
}




function payvector_process(&$params, $CallingFunction, $TransactionType)
{
    require_once (__DIR__ . '/payvector/payvector/TransactionProcessor.php');
    require_once (__DIR__ . '/payvector/payvector/ISOHelper.php');
    require_once (__DIR__ . '/payvector/payvector/PaymentFormHelper.php');
    require_once (__DIR__ . '/payvector/Helper.php');
    require_once (__DIR__ . '/payvector/Config.php');

    $StoreGatewayID = FALSE;
    $StoreCardLastFour = FALSE;
    
    $UserID = $params['clientdetails']['userid'];
    $FirstName = $params['clientdetails']['firstname'];
    $LastName = $params['clientdetails']['lastname'];
    $CustomerName = isset($_REQUEST['payvector_cc_owner']) && !empty($_REQUEST['payvector_cc_owner']) ? $_REQUEST['payvector_cc_owner'] : "{$FirstName} {$LastName}";
    $MerchantDetails = getMerchantDetails($params, $CallingFunction);

    $tp = new \TransactionProcessor();
    $tp->setMerchantID($MerchantDetails['MerchantID']);
    $tp->setMerchantPassword($MerchantDetails['Password']);

    
    $paymentProcessorDomain = "payvector.net";
    $rgepl = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();      
    $rgepl->add("https://gw1." . $paymentProcessorDomain, 100, 2);
    $rgepl->add("https://gw2." . $paymentProcessorDomain, 200, 2);
    $rgepl->add("https://gw3." . $paymentProcessorDomain, 300, 2);
    $tp->setRgeplRequestGatewayEntryPointList($rgepl);

    $amountDecimal = $params['amount'];
    if ($CallingFunction == "payvector_storeremote") {
        $amountDecimal = 1.01;
    }
    list($isoCurrency, $scaledAmount) = payvector_setAmountFromCurrency($params['currency'], $amountDecimal);
    
    $tp->setCurrencyCode($isoCurrency);
    $tp->setAmount($scaledAmount);

    switch ($CallingFunction)
    {
        case "payvector_3dsecure" :
            if (!$useSavedCard) {
                $StoreGatewayID = TRUE;
                $StoreCardLastFour = TRUE;
            }
            $tp->setOrderID($params['invoiceid']);
            $tp->setOrderDescription("Order ID: " . $params['invoiceid']);
            break;

        case "payvector_storeremote" :
            if ($TransactionType != "VOID")
            {
                $StoreGatewayID = TRUE;
                $StoreCardLastFour = TRUE;
            }
            $tp->setOrderID(strval(time()));
            $tp->setOrderDescription("Token Storage Setup: " . substr($params['cardnum'], -4, 4));
            break;

        case "payvector_capture" :
            if (!$useSavedCard) {
                $StoreGatewayID = TRUE;
                $StoreCardLastFour = TRUE;
            }
            $tp->setOrderID($params['invoiceid']);
            $tp->setOrderDescription("Order ID: " . $params['invoiceid']);
            break;

        case "payvector_refund" :
            $tp->setOrderID($params['invoiceid']);
            $tp->setOrderDescription("Refund for Invoice: " . $params['invoiceid']);
            break;
    }

    $tp->setCustomerName($CustomerName);
    
    $address1 = isset($_REQUEST['address1']) ? $_REQUEST['address1'] : $params['clientdetails']['address1'];
    $address2 = isset($_REQUEST['address2']) ? $_REQUEST['address2'] : $params['clientdetails']['address2'];
    $city = isset($_REQUEST['city']) ? $_REQUEST['city'] : $params['clientdetails']['city'];
    $state = isset($_REQUEST['state']) ? $_REQUEST['state'] : $params['clientdetails']['state'];
    $postcode = isset($_REQUEST['postcode']) ? $_REQUEST['postcode'] : $params['clientdetails']['postcode'];
    $country = isset($_REQUEST['country']) ? $_REQUEST['country'] : $params['clientdetails']['country'];

    $tp->setAddress1($address1);
    $tp->setAddress2($address2);
    $tp->setCity($city);
    $tp->setState($state);
    $tp->setPostcode($postcode);
    $tp->setCountryCode(payvector_getISOCountryCode($country));

    $tp->setEmailAddress($params['clientdetails']['email']);
    $tp->setPhoneNumber(isset($_REQUEST['phonenumber']) ? $_REQUEST['phonenumber'] : $params['clientdetails']['phone']);
    $tp->setIPAddress(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');
    $tp->setTransactionType($TransactionType);

    
    $javaEnabled = (isset($_POST['payvector_browser_java_enabled']) && $_POST['payvector_browser_java_enabled'] !== '') ? $_POST['payvector_browser_java_enabled'] : 'false';
    $screenWidth = (isset($_POST['payvector_browser_screen_width']) && $_POST['payvector_browser_screen_width'] !== '') ? $_POST['payvector_browser_screen_width'] : '1920';
    $screenHeight = (isset($_POST['payvector_browser_screen_height']) && $_POST['payvector_browser_screen_height'] !== '') ? $_POST['payvector_browser_screen_height'] : '1080';
    $colorDepth = (isset($_POST['payvector_browser_color_depth']) && $_POST['payvector_browser_color_depth'] !== '') ? $_POST['payvector_browser_color_depth'] : '24';
    $timezone = (isset($_POST['payvector_browser_tz']) && $_POST['payvector_browser_tz'] !== '') ? $_POST['payvector_browser_tz'] : '0';
    
    $acceptLang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5) : 'en-GB';
    $language = (isset($_POST['payvector_browser_language']) && $_POST['payvector_browser_language'] !== '') ? $_POST['payvector_browser_language'] : $acceptLang;

    $tp->setJavaEnabled($javaEnabled);
    $tp->setJavaScriptEnabled('true');
    $tp->setScreenWidth($screenWidth);
    $tp->setScreenHeight($screenHeight);
    $tp->setScreenColourDepth($colorDepth);
    $tp->setTimezoneOffset($timezone);
    $tp->setLanguage($language);

    $systemUrl = $params['systemurl'];
    $showSavedCards = isset($params['showSavedCards']) ? $params['showSavedCards'] : 'True';
    $useSavedCard = (!isset($_REQUEST['payvector_saved_card']) || $_REQUEST['payvector_saved_card'] !== 'new');
    $cardType = $useSavedCard ? 'saved' : 'new';
    
    $callbackUrl = rtrim($systemUrl, '/') . '/modules/gateways/payvector/Callback/3DSecureCallback.php?payvector_card_type=' . $cardType;
    $tp->setChallengeNotificationURL($callbackUrl);
    $tp->setFingerprintNotificationURL($callbackUrl);

    
    $showSavedCards = isset($params['showSavedCards']) ? $params['showSavedCards'] : 'True';
    $useSavedCard = (!isset($_REQUEST['payvector_saved_card']) || $_REQUEST['payvector_saved_card'] !== 'new');
    
    if ($showSavedCards !== 'False' && $useSavedCard && isset($params['gatewayid']) && !empty($params['gatewayid']) && ($CallingFunction == "payvector_capture" || $CallingFunction == "payvector_refund" || $CallingFunction == "payvector_3dsecure")) {
        $cv2 = isset($_REQUEST['payvector_saved_cc_cvv']) ? $_REQUEST['payvector_saved_cc_cvv'] : (isset($_REQUEST['cccvv']) ? $_REQUEST['cccvv'] : $params['cccvv']);
        $tp->setCV2($cv2);
        $result = $tp->doCrossReferenceTransaction($params['gatewayid'], false, $_SESSION);
    } else {
        $ccNumber = isset($_REQUEST['ccnumber']) ? $_REQUEST['ccnumber'] : $params['cardnum'];
        $expiryMonth = isset($_REQUEST['ccexpirymonth']) ? $_REQUEST['ccexpirymonth'] : substr($params['cardexp'], 0, 2);
        $expiryYear = isset($_REQUEST['ccexpiryyear']) ? $_REQUEST['ccexpiryyear'] : substr($params['cardexp'], 2, 2);
        $cvv = isset($_REQUEST['cccvv']) ? $_REQUEST['cccvv'] : $params['cccvv'];
        $issueNum = isset($_REQUEST['cardissuenum']) ? $_REQUEST['cardissuenum'] : $params['cardissuenum'];
        
        $tp->setCV2($cvv);
        $result = $tp->doCardDetailsTransaction($ccNumber, $expiryMonth, $expiryYear, $issueNum, $_SESSION);
        
        if (!empty($ccNumber)) {
            $_SESSION['payvector_saved_last4'] = substr(str_replace(' ', '', $ccNumber), -4);
        }
    }

    require_once (__DIR__ . '/payvector/HandleTransactionResults.php');
    return HandleTransactionResults($result, $tp, $params, $CallingFunction, $StoreGatewayID, $StoreCardLastFour);
}


function payvector_array_breakdown($var, $level = 1)
{

    $type;
    $return;

    $HNL = "</br>";

    if (is_array($var))
    {
        $type = "Array";
    }
    elseif (is_object($var))
    {
        $type = "Object";
    }
    else
    {
        $type = NULL;
    }

    if ($type != NULL)
    {

        if ($level == 1)
        {
            $return .= "<pre class=\"xdebug-var-dump\" dir=\"ltr\">";
            $return .= "<b>$type</b>";
        }
        else
        {
            $return .= " <b>$type</b>";
        }
        $return .= "<i>(size=" . count($var) . ") {</i>\n";

        if ($type == "Array")
        {

            foreach ($var as $key => $value)
            {

                $return .= str_repeat("\t", $level) . "'$key'";
                $return .= " <font color=\"#888a85\">=></font>";

                if (is_array($value) || is_object($value))
                {

                    $return .= payvector_array_breakdown($value, $level + 1);
                }
                else
                {

                    $type2 = gettype($value);
                    $return .= " <small>$type2</small>";
                    $return .= " <font color=";
                    if ($type2 == "string")
                    {
                        $return .= "\"#cc0000\">'$value'";
                    }
                    else
                    {
                        $return .= "\"#4e9a06\">$value";
                    }
                    $return .= "</font>";
                    $return .= " <i>(length=" . count($value) . ")</i>";
                }

                $return .= "\n";
            }
        }
        else
        {

            foreach ($var as $key => $value)
            {

                $return .= str_repeat("\t", $level) . "'$key'";
                $return .= " <font color=\"#888a85\">=></font>";

                if (is_array($value) || is_object($value))
                {

                    $return .= payvector_array_breakdown($value, $level + 1);
                }
                else
                {

                    $type2 = gettype($value);
                    $return .= " <small>$type2</small>";
                    $return .= " <font color=";
                    if ($type2 == "string")
                    {
                        $return .= "\"#cc0000\">'$value'";
                    }
                    else
                    {
                        $return .= "\"#4e9a06\">$value";
                    }
                    $return .= "</font>";
                    $return .= " <i>(length=" . count($value) . ")</i>";
                }
                $return .= "\n";
            }
        }

        $return .= str_repeat("\t", $level - 1) . "}";
    }

    return $return;
}

final class payvectorDatabase
{

    protected static $g_bActiveError;
    protected static $g_szErrorMessage;
    protected static $g_szQueryString;

    public function __constructor()
    {
    }

    private static function createCrossReferenceTable()
    {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS `payvector_cross_reference` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `customer_id` INT NOT NULL,
                    `cross_reference` VARCHAR(255) NOT NULL,
                    `card_type` VARCHAR(50),
                    `last_four` VARCHAR(4),
                    `date_added` DATETIME
                )
            ";
            \WHMCS\Database\Capsule::connection()->getPdo()->exec($sql);
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: createCrossReferenceTable - " . $e->getMessage();
            return false;
        }
    }

    public static function insertCrossReference($customer_id, $cross_reference, $card_type, $last_four)
    {
        if (!self::table_exists('payvector_cross_reference')) {
            self::createCrossReferenceTable();
        }

        try {
            $pdo = \WHMCS\Database\Capsule::connection()->getPdo();
            
            
            $checkStmt = $pdo->prepare("SELECT `id` FROM `payvector_cross_reference` WHERE `customer_id` = :customer_id LIMIT 1");
            $checkStmt->execute([':customer_id' => $customer_id]);
            $existingId = $checkStmt->fetchColumn();

            if ($existingId) {
                
                $sql = "UPDATE `payvector_cross_reference` SET `cross_reference` = :cross_reference, `card_type` = :card_type, `last_four` = :last_four, `date_added` = NOW() WHERE `id` = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':cross_reference' => $cross_reference,
                    ':card_type' => $card_type,
                    ':last_four' => $last_four,
                    ':id' => $existingId
                ]);
            } else {
                
                $sql = "INSERT INTO `payvector_cross_reference` (`customer_id`, `cross_reference`, `card_type`, `last_four`, `date_added`) VALUES (:customer_id, :cross_reference, :card_type, :last_four, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':customer_id' => $customer_id,
                    ':cross_reference' => $cross_reference,
                    ':card_type' => $card_type,
                    ':last_four' => $last_four
                ]);
            }
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: insertCrossReference - " . $e->getMessage();
            return false;
        }
    }

    public static function updateCrossReference($customer_id, $cross_reference, $card_type, $last_four)
    {
        if (!self::table_exists('payvector_cross_reference')) {
            self::createCrossReferenceTable();
        }

        try {
            $sql = "UPDATE `payvector_cross_reference` SET `cross_reference` = :cross_reference, `card_type` = :card_type, `last_four` = :last_four, `date_added` = NOW() WHERE `customer_id` = :customer_id";
            $stmt = \WHMCS\Database\Capsule::connection()->getPdo()->prepare($sql);
            $stmt->execute([
                ':customer_id' => $customer_id,
                ':cross_reference' => $cross_reference,
                ':card_type' => $card_type,
                ':last_four' => $last_four
            ]);
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: updateCrossReference - " . $e->getMessage();
            return false;
        }
    }

    public static function getCrossReference($customer_id)
    {
        $cross_references = array();

        try {
            $sql = "SELECT * FROM `payvector_cross_reference` WHERE `customer_id` = :customer_id ORDER BY `date_added` DESC";
            $stmt = \WHMCS\Database\Capsule::connection()->getPdo()->prepare($sql);
            $stmt->execute([':customer_id' => $customer_id]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($results as $row) {
                $cross_references[] = $row;
            }
            return $cross_references;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: getCrossReference - " . $e->getMessage();
            return $cross_references;
        }
    }

    public static function HasActiveError()
    {
        return self::$g_bActiveError;
    }

    public static function GetErrorMessage()
    {
        return self::$g_szErrorMessage;
    }

    protected static function table_exists($szTableName)
    {
        try {
            $result = \WHMCS\Database\Capsule::connection()->getPdo()->query(payvectorSQL::TableExists($szTableName));
            return $result && $result->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    private static function createGEP_EntryPoints()
    {
        try {
            \WHMCS\Database\Capsule::connection()->getPdo()->exec(payvectorSQL::createGEP_EntryPoints());
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: createGEP_EntryPoints - " . $e->getMessage();
            return false;
        }
    }

    public static function insertGEP_EntryPoint($GatewayEntryPointURL, $szTransactionDateTime, $RemoveDataThreshold_AmountInMinutes)
    {
        if (!self::table_exists(payvectorSQL::tblGEP_EntryPoints))
        {
            if (!self::createGEP_EntryPoints())
            {
                echo self::$g_szErrorMessage;
            }
        }

        self::deleteGEP_EntryPoint($RemoveDataThreshold_AmountInMinutes);

        try {
            \WHMCS\Database\Capsule::connection()->getPdo()->exec(payvectorSQL::insertGEP_EntryPoint($GatewayEntryPointURL, $szTransactionDateTime));
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: insertGEP_EntryPoint - " . $e->getMessage();
            return false;
        }
    }

    public static function selectGEP_EntryPoint($RemoveDataThreshold_AmountInMinutes)
    {
        if (!self::table_exists(payvectorSQL::tblGEP_EntryPoints))
        {
            if (!self::createGEP_EntryPoints())
            {
                echo self::$g_szErrorMessage;
            }
        }

        try {
            $result = \WHMCS\Database\Capsule::connection()->getPdo()->query(payvectorSQL::selectGEP_EntryPoint($RemoveDataThreshold_AmountInMinutes));
            if ($result) {
                while ($row = $result->fetch(\PDO::FETCH_BOTH)) {
                    return $row['GatewayEntryPoint'];
                }
            }
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: selectGEP_EntryPoint - " . $e->getMessage();
        }
    }

    private static function deleteGEP_EntryPoint($RemoveDataThreshold_AmountInMinutes)
    {
        try {
            \WHMCS\Database\Capsule::connection()->getPdo()->exec(payvectorSQL::deleteGEP_EntryPoint($RemoveDataThreshold_AmountInMinutes));
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: deleteGEP_EntryPoint - " . $e->getMessage();
            return false;
        }
    }

    private static function create3DS_Transactions()
    {
        try {
            \WHMCS\Database\Capsule::connection()->getPdo()->exec(payvectorSQL::create3DS_Transactions());
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: create3DS_Transactions - " . $e->getMessage();
            return false;
        }
    }

    public static function insert3DS_Transaction($szCrossReference, $szUserID, $iccISOCurrencyCode, $nAmount, $szTransactionDateTime)
    {
        if (!self::table_exists(payvectorSQL::tbl3DS_Transactions))
        {
            if (!self::create3DS_Transactions())
            {
                echo self::$g_szErrorMessage;
            }
        }

        self::delete3DS_HistoricTransactions();

        try {
            \WHMCS\Database\Capsule::connection()->getPdo()->exec(payvectorSQL::insert3DS_Transaction($szCrossReference, $szUserID, $iccISOCurrencyCode, $nAmount, $szTransactionDateTime));
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: insert3DS_Transaction - " . $e->getMessage();
            return false;
        }
    }

    public static function select3DS_UserID($CrossReference)
    {
        try {
            $result = \WHMCS\Database\Capsule::connection()->getPdo()->query(payvectorSQL::select3DS_UserID($CrossReference));
            if ($result) {
                while ($row = $result->fetch(\PDO::FETCH_BOTH)) {
                    return $row['UserID'];
                }
            }
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: select3DS_UserID - " . $e->getMessage();
        }
    }

    public static function select3DS_ISOCurrencyCode($CrossReference)
    {
        try {
            $result = \WHMCS\Database\Capsule::connection()->getPdo()->query(payvectorSQL::select3DS_ISOCurrencyCode($CrossReference));
            if ($result) {
                while ($row = $result->fetch(\PDO::FETCH_BOTH)) {
                    return $row['ISOCurrencyCode'];
                }
            }
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: select3DS_ISOCurrencyCode - " . $e->getMessage();
        }
    }

    public static function select3DS_Amount($CrossReference)
    {
        try {
            $result = \WHMCS\Database\Capsule::connection()->getPdo()->query(payvectorSQL::select3DS_Amount($CrossReference));
            if ($result) {
                while ($row = $result->fetch(\PDO::FETCH_BOTH)) {
                    return $row['Amount'];
                }
            }
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: select3DS_Amount - " . $e->getMessage();
        }
    }

    public static function delete3DS_Transaction($CrossReference, $UserID)
    {
        try {
            \WHMCS\Database\Capsule::connection()->getPdo()->exec(payvectorSQL::delete3DS_Transaction($CrossReference, $UserID));
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: delete3DS_Transaction - " . $e->getMessage();
            return false;
        }
    }

    private static function delete3DS_HistoricTransactions()
    {
        try {
            \WHMCS\Database\Capsule::connection()->getPdo()->exec(payvectorSQL::delete3DS_HistoricTransactions());
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: delete3DS_HistoricTransactions - " . $e->getMessage();
            return false;
        }
    }

    private static function createHPF_RESULTS()
    {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS `payvector_hpf_server_results` (
                    `cross_reference` VARCHAR(255) PRIMARY KEY,
                    `response_data` TEXT NOT NULL,
                    `date_added` DATETIME NOT NULL
                )
            ";
            \WHMCS\Database\Capsule::connection()->getPdo()->exec($sql);
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: createHPF_RESULTS - " . $e->getMessage();
            return false;
        }
    }

    public static function insertHPF_SERVER_Results($aResponseVariables)
    {
        if (!self::table_exists('payvector_hpf_server_results'))
        {
            self::createHPF_RESULTS();
        }

        if (!self::$g_bActiveError)
        {
            try {
                $CrossReference = isset($aResponseVariables['CrossReference']) ? $aResponseVariables['CrossReference'] : '';
                $response_data = json_encode($aResponseVariables);
                
                $sql = "INSERT INTO `payvector_hpf_server_results` (`cross_reference`, `response_data`, `date_added`) 
                        VALUES (:cross_reference, :response_data, NOW()) 
                        ON DUPLICATE KEY UPDATE `response_data` = :response_data";
                $stmt = \WHMCS\Database\Capsule::connection()->getPdo()->prepare($sql);
                $stmt->execute(array(
                    ':cross_reference' => $CrossReference,
                    ':response_data' => $response_data
                ));
                return true;
            } catch (\Exception $e) {
                self::$g_bActiveError = TRUE;
                self::$g_szErrorMessage = "ERROR: insertHPF_SERVER_Results - " . $e->getMessage();
                return false;
            }
        }
    }

    public static function selectHPF_SERVER_Results($CrossReference)
    {
        $results = array();
        try {
            $sql = "SELECT `response_data` FROM `payvector_hpf_server_results` WHERE `cross_reference` = :cross_reference";
            $stmt = \WHMCS\Database\Capsule::connection()->getPdo()->prepare($sql);
            $stmt->execute(array(':cross_reference' => $CrossReference));
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && !empty($row['response_data'])) {
                $results = json_decode($row['response_data'], true);
            }
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: selectHPF_SERVER_Results - " . $e->getMessage();
        }
        return $results;
    }

    private static function deleteHPF_HistoricResults()
    {
        try {
            $sql = "DELETE FROM `payvector_hpf_server_results` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            \WHMCS\Database\Capsule::connection()->getPdo()->exec($sql);
            return true;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: deleteHPF_HistoricResults - " . $e->getMessage();
            return false;
        }
    }

    public static function setGatewayID($UserID, $GatewayID, $CardLastFour = null)
    {
        $return = FALSE;

        if ($CardLastFour == null)
        {
            self::$g_szQueryString = "  UPDATE tblclients
                                        SET `gatewayid` = '$GatewayID'
                                        WHERE `id` = '$UserID';";
        }
        else
        {
            self::$g_szQueryString = "  UPDATE tblclients
                                        SET `gatewayid` = '$GatewayID',
                                            `cardlastfour` = '$CardLastFour'
                                        WHERE `id` = '$UserID';";
        }

        try {
            \WHMCS\Database\Capsule::connection()->getPdo()->exec(self::$g_szQueryString);
            $return = TRUE;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: setGatewayID - " . $e->getMessage();
        }

        return $return;
    }

    public static function saveCardPayMethod($UserID, $GatewayID, $CardLastFour = null, $ExpiryDate = null, $CardType = null)
    {
        self::setGatewayID($UserID, $GatewayID, $CardLastFour);

        if (function_exists('createCardPayMethod')) {
            try {
                $type = !empty($CardType) ? $CardType : 'Visa';
                $expiry = '';
                if (!empty($ExpiryDate)) {
                    $digits = preg_replace('/\D/', '', $ExpiryDate);
                    if (strlen($digits) == 4) {
                        $expiry = $digits;
                    }
                }
                if (empty($expiry)) {
                    $expiry = '1235';
                }

                createCardPayMethod(
                    $UserID,
                    'payvector',
                    $CardLastFour,
                    $expiry,
                    $type,
                    null,
                    null,
                    $GatewayID
                );
            } catch (\Exception $e) {
                logTransaction("Payvector Corporation", ['Error' => 'createCardPayMethod failed: ' . $e->getMessage()], "error");
            }
        }
    }

    public static function setDefaultGateway($UserID, $InvoiceID, $CrossReference, $OldGatewayName, $NewGatewayName)
    {
        $return = FALSE;

        self::$g_szQueryString = "  UPDATE tblclients
                                    SET `defaultgateway` = '$NewGatewayName', `gatewayid` = '$CrossReference'
                                    WHERE `id` = '$UserID';";

        try {
            $pdo = \WHMCS\Database\Capsule::connection()->getPdo();
            $pdo->exec(self::$g_szQueryString);

            self::$g_szQueryString = "  UPDATE tblinvoices
                                        SET `paymentmethod` = '$NewGatewayName'
                                        WHERE `id` = '$InvoiceID'
                                            AND `userid` = '$UserID'
                                            AND `paymentmethod` = '$OldGatewayName'
                                            AND `status` = 'Paid'
                                            ";

            $pdo->exec(self::$g_szQueryString);

            self::$g_szQueryString = "  SELECT `relid`
                                        FROM tblinvoiceitems
                                        WHERE `userid` = '$UserID'
                                            AND `invoiceid` = '$InvoiceID'
                                            AND `paymentmethod` = '$OldGatewayName';";

            $stmt = $pdo->query(self::$g_szQueryString);
            $relid = null;
            if ($stmt) {
                while ($row = $stmt->fetch(\PDO::FETCH_BOTH)) {
                    $relid = $row['relid'];
                }
            }

            if ($relid !== null) {
                self::$g_szQueryString = "  UPDATE tblinvoiceitems
                                            SET `paymentmethod` = '$NewGatewayName'
                                            WHERE `userid` = '$UserID'
                                                AND `paymentmethod` = '$OldGatewayName'
                                                AND `relid` = '$relid';";

                $pdo->exec(self::$g_szQueryString);

                self::$g_szQueryString = "  UPDATE tblhosting
                                            SET `paymentmethod` = '$NewGatewayName'
                                            WHERE `userid` = '$UserID'
                                                AND `paymentmethod` = '$OldGatewayName'
                                                AND `orderid` = '$relid';";

                $pdo->exec(self::$g_szQueryString);
            }

            self::$g_szQueryString = "  UPDATE tblaccounts
                                        SET `gateway` = '$NewGatewayName'
                                        WHERE `userid` = '$UserID'
                                            AND `gateway` = '$OldGatewayName'
                                            AND `transid` = '$CrossReference';";

            $pdo->exec(self::$g_szQueryString);

            self::$g_szQueryString = "  UPDATE tblorders
                                        SET `paymentmethod` = '$NewGatewayName'
                                        WHERE `userid` = '$UserID'
                                            AND `paymentmethod` = '$OldGatewayName'
                                            AND `invoiceid` = '$InvoiceID';";

            $pdo->exec(self::$g_szQueryString);
            $return = TRUE;
        } catch (\Exception $e) {
            self::$g_bActiveError = TRUE;
            self::$g_szErrorMessage = "ERROR: setDefaultGateway - " . $e->getMessage();
        }
        return $return;
    }

}

class payvectorSQL
{
    const tblGEP_EntryPoints = 'payvector_gep_entry_points';
    const tbl3DS_Transactions = 'payvector_3ds_transactions';
    const tblHPF_SERVER_Results = 'payvector_hpf_server_results';

    public static function TableExists($szTableName)
    {
        return "SELECT 1 FROM `$szTableName` LIMIT 1";
    }

    public static function createGEP_EntryPoints()
    {
        return "CREATE TABLE IF NOT EXISTS `payvector_gep_entry_points` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `url` VARCHAR(255) NOT NULL,
            `date_added` DATETIME NOT NULL
        )";
    }

    public static function insertGEP_EntryPoint($GatewayEntryPointURL, $szTransactionDateTime)
    {
        return "INSERT INTO `payvector_gep_entry_points` (`url`, `date_added`) VALUES ('$GatewayEntryPointURL', '$szTransactionDateTime')";
    }

    public static function selectGEP_EntryPoint($RemoveDataThreshold_AmountInMinutes)
    {
        return "SELECT * FROM `payvector_gep_entry_points` WHERE `date_added` >= DATE_SUB(NOW(), INTERVAL $RemoveDataThreshold_AmountInMinutes MINUTE)";
    }

    public static function deleteGEP_EntryPoint($RemoveDataThreshold_AmountInMinutes)
    {
        return "DELETE FROM `payvector_gep_entry_points` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL $RemoveDataThreshold_AmountInMinutes MINUTE)";
    }

    public static function create3DS_Transactions()
    {
        return "CREATE TABLE IF NOT EXISTS `payvector_3ds_transactions` (
            `cross_reference` VARCHAR(255) PRIMARY KEY,
            `user_id` INT NOT NULL,
            `currency` VARCHAR(3) NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `date_added` DATETIME NOT NULL
        )";
    }

    public static function insert3DS_Transaction($szCrossReference, $szUserID, $iccISOCurrencyCode, $nAmount, $szTransactionDateTime)
    {
        return "INSERT INTO `payvector_3ds_transactions` (`cross_reference`, `user_id`, `currency`, `amount`, `date_added`) VALUES ('$szCrossReference', '$szUserID', '$iccISOCurrencyCode', '$nAmount', '$szTransactionDateTime')";
    }

    public static function select3DS_UserID($CrossReference)
    {
        return "SELECT `user_id` AS UserID FROM `payvector_3ds_transactions` WHERE `cross_reference` = '$CrossReference'";
    }

    public static function select3DS_ISOCurrencyCode($CrossReference)
    {
        return "SELECT `currency` AS ISOCurrencyCode FROM `payvector_3ds_transactions` WHERE `cross_reference` = '$CrossReference'";
    }

    public static function select3DS_Amount($CrossReference)
    {
        return "SELECT `amount` AS Amount FROM `payvector_3ds_transactions` WHERE `cross_reference` = '$CrossReference'";
    }

    public static function delete3DS_Transaction($CrossReference, $UserID)
    {
        return "DELETE FROM `payvector_3ds_transactions` WHERE `cross_reference` = '$CrossReference' AND `user_id` = '$UserID'";
    }

    public static function delete3DS_HistoricTransactions()
    {
        return "DELETE FROM `payvector_3ds_transactions` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    }

    public static function createHPF_RESULTS()
    {
        return "CREATE TABLE IF NOT EXISTS `payvector_hpf_server_results` (
            `cross_reference` VARCHAR(255) PRIMARY KEY,
            `response_data` TEXT NOT NULL,
            `date_added` DATETIME NOT NULL
        )";
    }

    public static function insertHPF_SERVER_Results($aResponseVariables)
    {
        $CrossReference = isset($aResponseVariables['CrossReference']) ? $aResponseVariables['CrossReference'] : '';
        $data = json_encode($aResponseVariables);
        return "INSERT INTO `payvector_hpf_server_results` (`cross_reference`, `response_data`, `date_added`) VALUES ('$CrossReference', '$data', NOW()) ON DUPLICATE KEY UPDATE `response_data` = '$data'";
    }

    public static function selectHPF_SERVER_Results($CrossReference)
    {
        return "SELECT `response_data` FROM `payvector_hpf_server_results` WHERE `cross_reference` = '$CrossReference'";
    }

    public static function deleteHPF_HistoricResults()
    {
        return "DELETE FROM `payvector_hpf_server_results` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
}
