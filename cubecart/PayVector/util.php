<?php

class Errors {

    const NoIntegrationMethodSelected = "No integration method selected";
    const NoTransactionMethodSelected = "No transaction method selected";
    const NoTransactionTypeSelected = "No transaction type selected";
    const NoIntegrationSourceSpecified = "No transaction source specified";

    #
    const NoDomainSpecified = "No gateway domain specified";

    # Merchat Details
    const NoMerchantIDSpecified = "No merchant ID specified";
    const NoPasswordSpecified = "No password specified";

    # Multiple Transaction Types
    const NoAmountSpecified = "No amount specified";
    const NoCurrencySpecified = "No transaction currency specified";
    Const InvalidCurrencySpecified = "Invalid currency specified";

    # Order Details Errors
    const NoOrderIDSpecified = "No OrderID specified";
    const NoOrderDescriptionSpecified = "No OrderDescription Specified";

    # Card Details Transaction
    const NoCardNumberSpecified = "No card number specified";
    const NoCardCV2Specified = "No CV2 specified";
    const NoCardExpiryDate = "No card expiry date specified";
    const InvalidCardExpiryDate = "Invalid card expiry date specified";

    # Cross Reference Transaction
    const NoCrossReferenceSpecified = "No cross reference specified for a cross reference transaction";

    # ThreeDSecure Transaction
    const NoMDSpecified = "No MD specified for a ThreeDSecure transaction";
    const NoPaRESSpecified = "No PaRES specified for a ThreeDSecure transaction";

    # Hosted Payment Form
    const NoHostedFormMethodSelected = "No HostedFormMethod type selected";
    const NoReturnTypeSelected = "No return type select for a Hosted Payment Form integration";
    const NoHashMethodSelected = "No hash method select for a Hosted Payment Form integration";
    const NoPreSharedKeySpecified = "No PreSharedKey specified";
    const NoResultReturnTypeSelected = "No server result return type selected";
    const NoResultDeliveryMethodSelected = "No Result Delivery Method selected";
    const NoCallbackURLSpecified = "No CallbackURL specified";
    const NoHostedTransactionResponseSpecified = "No hosted transaction response specified";

    # Transparent Redirect Errors
    const NoTransparentRedirectMethodSelected = "No TransparentRedirectMethod type selected";

    # No Communication
    const NoCommunicationWithGateway = "Couldn't communicate with payment gateway";

}



class AVSPolicy {

    const NONE = NULL;
    const FailIfEitherFail = "E";
    const FailOnlyIfBothFail = "B";
    const FailOnlyIfAddressFails = "A";
    const FailOnlyIfPostCodeFails = "P";
    const DoNotFail = "N";

}

class AVSPartialAddress {

    const NONE = NULL;
    const PASS = "P";
    const FAIL = "F";

}

class AVSPartialPostCode {

    const NONE = NULL;
    const PASS = "P";
    const FAIL = "F";

}

class AVSResultsUnknown {

    const NONE = NULL;
    const PASS = "P";
    const FAIL = "F";

}

class CV2OverridePolicy {

    const NONE = NULL;
    const PASS = "P";
    const FAIL = "F";

}

class CV2ResultsUnknown {

    const NONE = NULL;
    const PASS = "P";
    const FAIL = "F";

}


abstract class PayVectorSQL {

    const tblGEP_EntryPoints = "PayVector_GEP_EntryPoints";
    const tblHPF_Transactions = "PayVector_HPF_Transactions";
    const tbl3DS_Transactions = "PayVector_3DS_Transactions";
    const tblHPF_SERVER_Results = "PayVector_HPF_SERVER_Results";
    const tblCRT_CrossReference = "PayVector_CRT_CrossReference";

    public static $g_szQueryString;

    public function __constructor() {

    }

    public static function TableExists($szTableName) {
        self::$g_szQueryString = "SHOW TABLES LIKE '$szTableName'";
        return self::$g_szQueryString;
    }

    public static function createCRT_CrossReference() {

        self::$g_szQueryString = "
            CREATE TABLE IF NOT EXISTS `" . self::tblCRT_CrossReference . "`
            (
                `UserID`                varchar(255)    NOT NULL,
                `CrossReference`        varchar(24)     NOT NULL,
                `CardLastFour`          text(4)         NOT NULL,
                `CardType`        varchar(25)     NOT NULL,                                                
                `TransactionDateTime`   DateTime        NOT NULL,
                PRIMARY KEY (`CrossReference`)
            );";

        return self::$g_szQueryString;
    }

    public static function insertCRT_NewCardDetailsTransaction($szUserID, $szCrossReference, $szCardLastFour, $szCardType,  $szTransactionDateTime) {

        self::$g_szQueryString = "
            INSERT INTO " . self::tblCRT_CrossReference . "
            (
                `UserID`,
                `CrossReference`,
                `CardLastFour`,
                `CardType`,                
                `TransactionDateTime`                
            )
            VALUES
            (
                '$szUserID',
                '$szCrossReference',
                '$szCardLastFour',
                '$szCardType',
                '$szTransactionDateTime'
                
            );";

        return self::$g_szQueryString;
    }

    public static function updateCRT_CardDetails($szUserID, $szCrossReference, $szCardLastFour, $szCardType,  $szTransactionDateTime) {

        self::$g_szQueryString = "
            UPDATE " . self::tblCRT_CrossReference . "
            SET `CrossReference`        = '$szCrossReference',
                `CardLastFour`          = '$szCardLastFour',
                `CardType`       = '$szCardType',                
                `TransactionDateTime`   = '$szTransactionDateTime'                
            WHERE 
                `UserID` = '$szUserID';";

        return self::$g_szQueryString;
    }

    public static function updateCRT_TransactionDetails($szUserID, $szOriginCrossReference, $szNewCrossReference, $szTransactionDateTime) {

        self::$g_szQueryString = "
            UPDATE " . self::tblCRT_CrossReference . "
            SET `CrossReference`        = '$szNewCrossReference',
                `TransactionDateTime`   = '$szTransactionDateTime'                
            WHERE `UserID`              = '$szUserID'
                AND `CrossReference`    = '$szOriginCrossReference'
                ;";

        return self::$g_szQueryString;
    }

    public static function deleteCRT_CardDetailsSpecific($szUserID, $szCardLastFour) {

        self::$g_szQueryString = "
            DELETE FROM " . self::tblCRT_CrossReference . "
            WHERE `UserID` = '$szUserID'
                AND `CardLastFour` = '$szCardLastFour'
            ;";

        return self::$g_szQueryString;

    }

    public static function deleteCRT_CardDetailsAllExceptSpecificCrossReference($szUserID, $szCrossReference) {

        self::$g_szQueryString = "
            DELETE FROM " . self::tblCRT_CrossReference . "
            WHERE `UserID` = '$szUserID'
                AND `CrossReference` != '$szCrossReference'
            ;";

        return self::$g_szQueryString;

    }

    public static function deleteCRT_CardDetailsAll($szUserID) {

        self::$g_szQueryString = "
            DELETE FROM " . self::tblCRT_CrossReference . "
            WHERE `UserID` = '$szUserID'
            ;";

        return self::$g_szQueryString;

    }

    public static function selectCRT_CrossReference($szUserID) {

        self::$g_szQueryString = "
            SELECT `CrossReference`
            FROM " . self::tblCRT_CrossReference . "
            WHERE `UserID` = '$szUserID';";

        return self::$g_szQueryString;
    }

    
    public static function selectCRT_CrossReferenceDetails($szUserID) {

        self::$g_szQueryString = "
            SELECT `CrossReference`,`CardLastFour`,`CardType`,`TransactionDateTime`
            FROM " . self::tblCRT_CrossReference . "
            WHERE `UserID` = '$szUserID'                
            ORDER BY `TransactionDateTime` DESC
            ;";

        return self::$g_szQueryString;
    }

    public static function createGEP_EntryPoints() {

        self::$g_szQueryString = "                
			CREATE TABLE IF NOT EXISTS `" . self::tblGEP_EntryPoints . "`
                (
                    `ID`                      INT(11)   NOT NULL AUTO_INCREMENT,
                    `GatewayEntryPointObject` LONGTEXT  NOT NULL,
                    `TransactionDateTime`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`ID`)
                );";
                

        return self::$g_szQueryString;
    }
    
    public static function insertGEP_EntryPointsPlaceholder() {

        self::$g_szQueryString = "                
            INSERT INTO `" . self::tblGEP_EntryPoints . "` (`GatewayEntryPointObject`, `TransactionDateTime`)
            	VALUES('PlaceHolder',NOW() - INTERVAL 30 MINUTE);";
                

        return self::$g_szQueryString;
    }
    
    public static function updateGEP_EntryPoint($GatewayEntryPointListXMLString) {
    	self::$g_szQueryString = "
    	UPDATE " . self::tblGEP_EntryPoints . "
    	SET GatewayEntryPointObject = '$gatewayEntryPointListXMLString', TransactionDateTime = CURRENT_TIMESTAMP;";
		
		return self::$g_szQueryString;
    }

    public static function selectGEP_EntryPoint() {

        self::$g_szQueryString = "
			SELECT `GatewayEntryPointObject`, MAX(`TransactionDateTime`)
            FROM " . self::tblGEP_EntryPoints . "
            WHERE `TransactionDateTime` >= now() - interval 10 minute;";

        return self::$g_szQueryString;
    }



    public static function createHPF_RESULTS() {

        self::$g_szQueryString = "
            CREATE TABLE IF NOT EXISTS `" . self::tblHPF_SERVER_Results . "`
            (
                `HashDigest`                            text(64)    NOT NULL,
                `MerchantID`                            text(15)    NOT NULL,
                `StatusCode`                            text(3)     NOT NULL,
                `Message`                               text(512)   ,
                `PreviousStatusCode`                    text(3)     ,
                `PreviousMessage`                       text(512)   ,
                `CrossReference`                        varchar(24) NOT NULL,
                `Amount`                                text(13)    NOT NULL,
                `CurrencyCode`                          text(3)     NOT NULL,
                `OrderID`                               text(50)    NOT NULL,
                `TransactionType`                       text(50)    NOT NULL,
                `TransactionDateTime`                   DateTime    NOT NULL,
                `OrderDescription`                      text(256)   ,
                `CustomerName`                          text(100)   ,
                `Address1`                              text(100)   ,
                `Address2`                              text(100)   ,
                `Address3`                              text(100)   ,
                `Address4`                              text(100)   ,
                `City`                                  text(100)   ,
                `State`                                 text(100)   ,
                `PostCode`                              text(100)   ,
                `CountryCode`                           text(3)     ,
                `EmailAddress`                          text(256)   ,
                `PhoneNumber`                           text(50)    ,
                `CardType`                              text(100)   ,
                `CardClass`                             text(100)   ,
                `CardIssuer`                            text(256)   ,
                `CardIssuerCountryCode`                 text(3)     ,
                `AddressNumericCheckResult`             text(50)    ,
                `PostCodeCheckResult`                   text(50)    ,
                `CV2CheckResult`                        text(50)    ,
                `ThreeDSecureAuthenticationCheckResult` text(50)    ,

                PRIMARY KEY (`CrossReference`)
            );";

        return self::$g_szQueryString;
    }

    public static function insertHPF_SERVER_Results($aResponseVariables) {

        self::$g_szQueryString = "
            INSERT INTO " . self::tblHPF_SERVER_Results . "
            (";

        foreach ($aResponseVariables as $key => $value) {
            self::$g_szQueryString .= "
                        `" . $key . "`,";
        }

        self::$g_szQueryString = substr(self::$g_szQueryString, 0, strlen(self::$g_szQueryString) - 1);

        self::$g_szQueryString .= "
            )
            VALUES
            (
            ";

        foreach ($aResponseVariables as $key => $value) {
            self::$g_szQueryString .= "
                        '" . $value . "',";
        }
        self::$g_szQueryString = substr(self::$g_szQueryString, 0, strlen(self::$g_szQueryString) - 1);

        self::$g_szQueryString .= "   );";

        return self::$g_szQueryString;
    }

    public static function selectHPF_SERVER_Results($CrossReference) {

        $results = array();

        self::$g_szQueryString = "
            SELECT *
            FROM " . self::tblHPF_SERVER_Results . "
            WHERE `CrossReference` = '$CrossReference';";

        return self::$g_szQueryString;
    }

    public static function deleteHPF_HistoricResults() {

        self::$g_szQueryString = "
            DELETE FROM " . self::tblHPF_SERVER_Results . "
            WHERE `TransactionDateTime` < SUBDATE(NOW(), INTERVAL 2 DAY);";

        return self::$g_szQueryString;
    }

}
