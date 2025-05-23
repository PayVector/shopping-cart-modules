<?php

namespace Opencart\Catalog\Controller\Extension\Payvector\Payment;

/*
 * Product: PayVector Payment Gateway
 * Version: 1.1.0
 * Release Date: 2014.12.03
 *
 * Copyright (C) 2014 PayVector <support@payvector.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require __DIR__ . '/ThePaymentGateway/PaymentSystem.php';
require __DIR__ . '/ISOHelper.php';
require __DIR__ . '/PaymentFormHelper.php';

/**
 * Processes transactions with the PayVector Gateway
 */
class TransactionProcessor
{
	/**
	 * The Merchant's ID
	 * @var string
	 */
	private $merchantID;
	/**
	 * The Merchant's Password
	 * @var string
	 */
	private $merchantPassword;
	/**
	 * List of entry points to the gateway including the entry point metric used to determine which to poll first
	 * @var RequestGatewayEntryPointList
	 */
	private $rgeplRequestGatewayEntryPointList;
	/**
	 * ISO code of the currency in use
	 * @var string
	 */
	private $currencyCode;
	/**
	 * Amount the transaction is for in minor currency
	 * @var string|int
	 */
	private $amount;
	/**
	 * ID of the order associated with this transaction
	 * @var string|int
	 */
	private $orderID;
	/**
	 * Description of the order associated with this transaction
	 * @var string
	 */
	private $orderDescription;
	/**
	 * Whether the CV2 policy should be overridden from the merchant's MMS value
	 * @var bool
	 */
	private $overrideCV2;
	/**
	 * If $overrideCV2 is set then this should be a valid 2 character CV2Override string
	 * @var string
	 */
	private $CV2OverridePolicy;
	/**
	 * Whether the AVS policy should be overridden from the merchant's MMS value
	 * @var bool
	 */
	private $overrideAVS;
	/**
	 * If $overrideAVS is set then this should be a valid 4 character AVSOverride string
	 * @var string
	 */
	private $AVSOverridePolicy;
	/**
	 * CV2 number
	 * @var string|int
	 */
	private $cv2;
	/**
	 * Customer's name
	 * @var string
	 */
	private $customerName;
	/**
	 * Customer's billing address line 1
	 * @var string
	 */
	private $address1;
	/**
	 * Customer's billing address line 2
	 * @var string
	 */
	private $address2;
	/**
	 * Customer's billing address line 3
	 * @var string
	 */
	private $address3;
	/**
	 * Customer's billing address line 4
	 * @var string
	 */
	private $address4;
	/**
	 * Customer's billing city
	 * @var string
	 */
	private $city;
	/**
	 * Customer's billing state/region
	 * @var string
	 */
	private $state;
	/**
	 * Customer's billing postcode
	 * @var string
	 */
	private $postcode;
	/**
	 * ISO code of the customer's billing country
	 * @var string
	 */
	private $countryCode;
	/**
	 * Customer's email address
	 * @var string
	 */
	private $emailAddress;
	/**
	 * Customer's phone number
	 * @var string
	 */
	private $phoneNumber;
	/**
	 * Customer's IPAddress
	 * @var string
	 */
	private $IPAddress;
	/**
	 * Type of transaction - SALE/PREAUTH/CAPTURE/REFUND - defaults to SALE
	 * @var string
	 */
	private $transactionType;

	//3DSv2 Parameters
	private $JavaEnabled;
	private $JavaScriptEnabled;
	private $ScreenWidth;
	private $ScreenHeight;
	private $ScreenColourDepth;
	private $TimezoneOffset;
	private $Language;

	private $ChallengeNotificationURL;
	private $FingerprintNotificationURL;

	/**
	 * @param string $AVSOverridePolicy
	 */
	public function setAVSOverridePolicy($AVSOverridePolicy)
	{
		$this->AVSOverridePolicy = $AVSOverridePolicy;
	}
	/**
	 * @param string $CV2OverridePolicy
	 */
	public function setCV2OverridePolicy($CV2OverridePolicy)
	{
		$this->CV2OverridePolicy = $CV2OverridePolicy;
	}
	/**
	 * @param string $IPAddress
	 */
	public function setIPAddress($IPAddress)
	{
		$this->IPAddress = $IPAddress;
	}
	/**
	 * @param string $address1
	 */
	public function setAddress1($address1)
	{
		$this->address1 = $address1;
	}
	/**
	 * @param string $address2
	 */
	public function setAddress2($address2)
	{
		$this->address2 = $address2;
	}
	/**
	 * @param string $address3
	 */
	public function setAddress3($address3)
	{
		$this->address3 = $address3;
	}
	/**
	 * @param string $address4
	 */
	public function setAddress4($address4)
	{
		$this->address4 = $address4;
	}
	/**
	 * @param int|string $amount
	 */
	public function setAmount($amount)
	{
		if(is_int($amount))
		{
			$this->amount = (string) $amount;
		}
		else
		{
			$this->amount = $amount;
		}
	}
	/**
	 * @param string $city
	 */
	public function setCity($city)
	{
		$this->city = $city;
	}
	/**
	 * @param string $countryCode
	 */
	public function setCountryCode($countryCode)
	{
		$this->countryCode = $countryCode;
	}
	/**
	 * @param string $currencyCode
	 */
	public function setCurrencyCode($currencyCode)
	{
		$this->currencyCode = $currencyCode;
	}
	/**
	 * @param string $customerName
	 */
	public function setCustomerName($customerName)
	{
		$this->customerName = $customerName;
	}
	/**
	 * @param int|string $cv2
	 */
	public function setCV2($cv2)
	{
		if(is_int($cv2))
		{
			$this->cv2 = (string) $cv2;
		}
		else
		{
			$this->cv2 = $cv2;
		}
	}
	/**
	 * @param string $emailAddress
	 */
	public function setEmailAddress($emailAddress)
	{
		$this->emailAddress = $emailAddress;
	}
	/**
	 * @param string $merchantID
	 */
	public function setMerchantID($merchantID)
	{
		$this->merchantID = $merchantID;
	}
	/**
	 * @param string $merchantPassword
	 */
	public function setMerchantPassword($merchantPassword)
	{
		$this->merchantPassword = $merchantPassword;
	}
	/**
	 * @param string $orderDescription
	 */
	public function setOrderDescription($orderDescription)
	{
		$this->orderDescription = $orderDescription;
	}
	/**
	 * @param int|string $orderID
	 */
	public function setOrderID($orderID)
	{
		if(is_int($orderID))
		{
			$this->orderID = (string) $orderID;
		}
		else
		{
			$this->orderID = $orderID;
		}
	}
	/**
	 * @param boolean $overrideAVS
	 */
	public function setOverrideAVS($overrideAVS)
	{
		$this->overrideAVS = $overrideAVS;
	}
	/**
	 * @param boolean $overrideCV2
	 */
	public function setOverrideCV2($overrideCV2)
	{
		$this->overrideCV2 = $overrideCV2;
	}
	/**
	 * @param string $phoneNumber
	 */
	public function setPhoneNumber($phoneNumber)
	{
		$this->phoneNumber = $phoneNumber;
	}
	/**
	 * @param string $postcode
	 */
	public function setPostcode($postcode)
	{
		$this->postcode = $postcode;
	}
	/**
	 * @param RequestGatewayEntryPointList $rgeplRequestGatewayEntryPointList
	 */
	public function setRgeplRequestGatewayEntryPointList($rgeplRequestGatewayEntryPointList)
	{
		$this->rgeplRequestGatewayEntryPointList = $rgeplRequestGatewayEntryPointList;
	}
	/**
	 * @param string $state
	 */
	public function setState($state)
	{
		$this->state = $state;
	}
	/**
	 * @param string $transactionType
	 */
	public function setTransactionType($transactionType)
	{
		$this->transactionType = $transactionType;
	}

	//3DSv2 Parameters
	public function setJavaEnabled($JavaEnabled)
	{
		$this->JavaEnabled = $JavaEnabled;
	}
	public function setJavaScriptEnabled($JavaScriptEnabled)
	{
		$this->JavaScriptEnabled = $JavaScriptEnabled;
	}
	public function setScreenWidth($ScreenWidth)
	{
		$this->ScreenWidth = $ScreenWidth;
	}
	public function setScreenHeight($ScreenHeight)
	{
		$this->ScreenHeight = $ScreenHeight;
	}
	public function setScreenColourDepth($ScreenColourDepth)
	{
		$this->ScreenColourDepth = $ScreenColourDepth;
	}
	public function setTimezoneOffset($TimezoneOffset)
	{
		$this->TimezoneOffset = $TimezoneOffset;
	}
	public function setLanguage($Language)
	{
		$this->Language = $Language;
	}
	public function setTimeZone($timezoneOffset)
	{
		$this->timezoneOffset = $timezoneOffset;
	}
	public function setChallengeNotificationURL($ChallengeNotificationURL)
	{
		$this->ChallengeNotificationURL = $ChallengeNotificationURL;
	}
	public function setFingerprintNotificationURL($FingerprintNotificationURL)
	{
		$this->FingerprintNotificationURL = $FingerprintNotificationURL;
	}
	/**
	 * Sets up initial variables required for a direct transaction
	 * @param string                       $merchantID                        Merchant's ID
	 * @param string                       $merchantPassword                  Merchant's Password
	 * @param RequestGatewayEntryPointList $rgeplRequestGatewayEntryPointList RequestGatewayEntryPointList from the database
	 * @param int                          $isoCurrencyCode                   3 digit ISO currency code
	 * @param int                          $amount                            Transaction total in minor currency
	 * @param string|int                   $orderID                           ID of the order
	 * @param string                       $orderDescription                  Description of the order
	 * @param string                       $customerName                      Customer's Name
	 * @param string                       $address1                          Customer's billing address line 1
	 * @param string                       $address2                          Customer's billing address line 2
	 * @param string                       $address3                          Customer's billing address line 3
	 * @param string                       $address4                          Customer's billing address line 4
	 * @param string                       $city                              Customer's billing city
	 * @param string                       $state                             Customer's billing state/region
	 * @param string                       $postcode                          Customer's billing post/zip code
	 * @param string                       $isoCountryCode                    3 digit ISO country code
	 * @param string                       $emailAddress                      Customer's email address
	 * @param string                       $phoneNumber                       Customer's phone number
	 * @param string                       $IPAddress                         IP address of the customer
	 * @param string                       $transactionType                   Type of transaction to process defaults to SALE
	 * @param string|int                   $cv2                               CV2 number printed on the customer's card
	 * @param bool                         $overrideCV2                       (Optional) TRUE if this transaction overrides the default CV2 policy set in the MMS, FALSE if using the default
	 * @param string                       $CV2OverridePolicy                 (Optional) 2 letter string containing a valid CV2OverridePolicy set in the MMS, FALSE if using the default
	 * @param bool                         $overrideAVS                       (Optional) TRUE if this transaction overrides the default AVS policy set in the MMS, FALSE if using the default
	 * @param string                       $AVSOverridePolicy                 (Optional) 4 letter string containing a valid AVSOverridePolicy described in the DirectIntegration document available on the MMS
	 */
	public function __construct(
		$merchantID = null,
		$merchantPassword = null,
		$rgeplRequestGatewayEntryPointList = null,
		$isoCurrencyCode = null,
		$amount = null,
		$orderID = null,
		$orderDescription = null,
		$customerName = null,
		$address1 = null,
		$address2 = null,
		$address3 = null,
		$address4 = null,
		$city = null,
		$state = null,
		$postcode = null,
		$isoCountryCode = null,
		$emailAddress = null,
		$phoneNumber = null,
		$IPAddress = null,
		$transactionType = TransactionType::Sale,
		$cv2 = null,
		$overrideCV2 = false,
		$CV2OverridePolicy = null,
		$overrideAVS = false,
		$AVSOverridePolicy = null
	)
	{
		$this->merchantID = $merchantID;
		$this->merchantPassword = $merchantPassword;
		$this->rgeplRequestGatewayEntryPointList = $rgeplRequestGatewayEntryPointList;
		$this->currencyCode = $isoCurrencyCode;
		if(is_int($amount))
		{
			$this->amount = (string) $amount;
		}
		else
		{
			$this->amount = $amount;
		}
		if(is_int($orderID))
		{
			$this->orderID = (string) $orderID;
		}
		else
		{
			$this->orderID = $orderID;
		}
		$this->orderDescription = $orderDescription;
		if(is_int($cv2))
		{
			$this->cv2 = (string) $cv2;
		}
		else
		{
			$this->cv2 = $cv2;
		}
		$this->customerName = $customerName;
		$this->address1 = $address1;
		$this->address2 = $address2;
		$this->address3 = $address3;
		$this->address4 = $address4;
		$this->city = $city;
		$this->state = $state;
		$this->postcode = $postcode;
		$this->countryCode = $isoCountryCode;
		$this->emailAddress = $emailAddress;
		$this->phoneNumber = $phoneNumber;
		$this->IPAddress = $IPAddress;
		$this->transactionType = $transactionType;
		$this->overrideCV2 = $overrideCV2;
		$this->CV2OverridePolicy = $CV2OverridePolicy;
		$this->overrideAVS = $overrideAVS;
		$this->AVSOverridePolicy = $AVSOverridePolicy;
	}

	/**
	 * Generates the fields required for the HostedPaymentForm and returns them as an associative array
	 * @param  string $callbackURL               URL that the payment gateway should return to once the transaction has completed
	 * @param  string $serverResultURL           The merchant's external server URL used for SERVER result delivery method
	 * @param  string $preSharedKey              PreSharedKey as set in the merchant's MMS - used for the hashing function
	 * @param  string $hashMethod                Hash method to be used, should be set as in the merchant's MMS
	 * @param  string $resultDeliveryMethod      Method used to delivery the results of the transaction, can either be POST, SERVER or SERVER_PULL - defaults to POST
	 * @param  bool   $paymentFormDisplaysResult Whether the payment form displays the result to the user or passes it back to the merchant's system for display (optional)
	 * @return array                             Associative array containing all the details needed to build the form - array keys are exact matches to the format required in the form
	 */
	public function getHostedPaymentForm(
		$callbackURL,
		$serverResultURL,
		$preSharedKey,
		$hashMethod,
		$resultDeliveryMethod,
		$paymentFormDisplaysResult,
		$sessionHandler)
	{
		$sessionHandler->setSessionValue( 'payvector_transaction_is_cross_reference', false);

		$transactionDateTime = date('Y-m-d H:i:s P');

		$stringToHash =
			PaymentFormHelper::generateStringToHash(
				$this->merchantID,
				$this->merchantPassword,
				$this->amount,
				$this->currencyCode,
				$this->orderID,
				$this->transactionType,
				$transactionDateTime,
				$callbackURL,
				$this->orderDescription ?? '',
				$this->customerName ?? '',
				$this->address1 ?? '',
				$this->address2 ?? '',
				$this->address3 ?? '',
				$this->address4 ?? '',
				$this->city ?? '',
				$this->state ?? '',
				$this->postcode ?? '',
				$this->countryCode ?? '',
				"true",
				"false",
				"false",
				"false",
				"false",
				"false",
				$resultDeliveryMethod,
				$serverResultURL,
				PaymentFormHelper::boolToString($paymentFormDisplaysResult),
				$preSharedKey,
				$hashMethod,
				$this->emailAddress ?? '',
				$this->phoneNumber ?? ''
			);
		return array(
			'HashDigest' => PaymentFormHelper::calculateHashDigest($stringToHash, $preSharedKey, $hashMethod),
			'MerchantID' => $this->merchantID,
			'Amount' => (string) $this->amount,
			'CurrencyCode' => (string) $this->currencyCode,
			'EchoAVSCheckResult' => "true",
			'EchoCV2CheckResult' => "true",
			'EchoThreeDSecureAuthenticationCheckResult' => "true",
			'EchoCardType' => "true",
			'EchoCardNumberFirstSix' => "true",
			'EchoCardNumberLastFour' => "true",
			'OrderID' => (string) $this->orderID,
			'TransactionType' => $this->transactionType,
			'TransactionDateTime' => $transactionDateTime,
			'CallbackURL' => $callbackURL,
			'OrderDescription' => $this->orderDescription  ?? '',
			'CustomerName' => $this->customerName  ?? '',
			'Address1' => $this->address1 ?? '',
			'Address2' => $this->address2 ?? '',
			'Address3' => $this->address3 ?? '',
			'Address4' => $this->address4 ?? '',
			'City' => $this->city ?? '',
			'State' => $this->state ?? '',
			'Postcode' => $this->postcode ?? '',
			'CountryCode' => (string) $this->countryCode ?? '',
			'EmailAddress' => $this->emailAddress ?? '',
			'PhoneNumber' => $this->phoneNumber ?? '',
			'CV2Mandatory' => "true",
			'Address1Mandatory' => "false",
			'CityMandatory' => "false",
			'PostCodeMandatory' => "false",
			'StateMandatory' => "false",
			'CountryMandatory' => "false",
			'ResultDeliveryMethod' => $resultDeliveryMethod,
			'ServerResultURL' => $serverResultURL,
			'PaymentFormDisplaysResult' => PaymentFormHelper::boolToString($paymentFormDisplaysResult),
			'ServerResultURLCookieVariables' => "",
			'ServerResultURLFormVariables' => "",
			'ServerResultURLQueryStringVariables' => ""
		);
	}

	/**
	 * @param string $callbackURL  URL that the payment gateway should return to once the transaction has completed
	 * @param string $preSharedKey PreSharedKey as set in the merchant's MMS - used for the hashing function
	 * @param string $hashMethod   Hash method to be used, should be set as in the merchant's MMS
	 * @return array               Array containing all the details needed to build up the form - array keys are exact matches to the format required in the form
	 */
	public function getTransparentForm(
		$callbackURL,
		$preSharedKey,
		$hashMethod,
		$sessionHandler)
	{
		$sessionHandler->setSessionValue( 'payvector_transaction_is_cross_reference', false );
		$transactionDateTime = date('Y-m-d H:i:s P');
		$stringToHash = PaymentFormHelper::generateStringToHashInitial(
			$hashMethod,
			$preSharedKey,
			$this->merchantID,
			$this->merchantPassword,
			$this->amount,
			$this->currencyCode,
			$this->orderID,
			$this->transactionType,
			$transactionDateTime,
			$callbackURL,
			$this->orderDescription);
		$hashDigest = PaymentFormHelper::calculateHashDigest($stringToHash, $preSharedKey, $hashMethod);

		return array(
			'HashDigest' => $hashDigest,
			'MerchantID' => $this->merchantID,
			'Amount' => (string) $this->amount,
			'CurrencyCode' => (string) $this->currencyCode,
			'OrderID' => (string) $this->orderID,
			'TransactionType' => $this->transactionType,
			'TransactionDateTime' => $transactionDateTime,
			'CallbackURL' => $callbackURL,
			'OrderDescription' => $this->orderDescription,
			'Address1' => $this->address1,
			'Address2' => $this->address2,
			'Address3' => $this->address3,
			'Address4' => $this->address4,
			'City' => $this->city,
			'State' => $this->state,
			'Postcode' => $this->postcode,
			'CountryCode' => (string) $this->countryCode,
			'EmailAddress' => $this->emailAddress,
			'PhoneNumber' => $this->phoneNumber);
	}

	/**
	 * Sends the transaction request to the gateway and returns a response object by reference
	 * @param  string                            $cardNumber      Customer's card number
	 * @param  string                            $expiryDateMonth Month the card expires on in 2 digit numeric format
	 * @param  string                            $expiryDateYear  Year the card expires on in 2 digit numeric format
	 * @param  string                            $issueNumber     (optional) Issue number of the customer's card
	 * @return CardDetailsFinalTransactionResult                  Returns a CardDetailsTransactionResult object that implements FinalTransactionResult methods
	 */
	public function doCardDetailsTransaction(
		$cardNumber,
		$expiryDateMonth,
		$expiryDateYear,
		$issueNumber,
		$sessionHandler)
	{

		$sessionHandler->setSessionValue( 'payvector_transaction_is_cross_reference', false);
		$cdtCardDetailsTransaction = new \net\thepaymentgateway\paymentsystem\CardDetailsTransaction($this->rgeplRequestGatewayEntryPointList);
		//set fields shared with a CardDetailsTransaction
		$cdtCardDetailsTransaction = $this->setSharedFields($cdtCardDetailsTransaction);
		//set fields specific to a CardDetailsTransaction
		$cdtCardDetailsTransaction->getCardDetails()->setCardName($this->customerName);
		$cdtCardDetailsTransaction->getCardDetails()->setCardNumber($cardNumber);
		if($expiryDateMonth != "")
		{
			$cdtCardDetailsTransaction->getCardDetails()->getExpiryDate()->getMonth()->setValue($expiryDateMonth);
		}
		if($expiryDateYear != "")
		{
			$cdtCardDetailsTransaction->getCardDetails()->getExpiryDate()->getYear()->setValue(substr($expiryDateYear, -2));
		}
		$cdtCardDetailsTransaction->getCardDetails()->setIssueNumber($issueNumber);
		$cdtCardDetailsTransaction->getCardDetails()->setCV2($this->cv2);
		/**
		 * @var $cdtrCardDetailsTransactionResult CardDetailsTransactionResult
		 * @var $todTransactionOutputData TransactionOutputData
		 */
		$transactionProcessed = $cdtCardDetailsTransaction->processTransaction(
			$cdtrCardDetailsTransactionResult,
			$todTransactionOutputData);
			
		return new CardDetailsFinalTransactionResult($transactionProcessed, $cdtCardDetailsTransaction, $cdtrCardDetailsTransactionResult, $todTransactionOutputData, $sessionHandler);
	}

	/**
	 * Sends the cross reference transaction request to the gateway and returns a response object by reference
	 * @param  string                               $crossReference             CrossReference - this is returned from any successful transaction
	 * @param  bool                                 $threeDSecureOverridePolicy Whether the 3DSecure check should be run for this transaction - defaults to true
	 * @return CrossReferenceFinalTransactionResult                             Returns a CrossReferenceTransactionResult object that implements FinalTransactionResult methods
	 */
	public function doCrossReferenceTransaction(
		$crossReference,
		$threeDSecureOverridePolicy,
		$sessionHandler)
	{

		$sessionHandler->setSessionValue( 'payvector_transaction_is_cross_reference', true);
		$crtCrossReferenceTransaction = new \net\thepaymentgateway\paymentsystem\CrossReferenceTransaction($this->rgeplRequestGatewayEntryPointList);
		//set fields shared with a CardDetailsTransaction
		$crtCrossReferenceTransaction = $this->setSharedFields($crtCrossReferenceTransaction);
		//set fields specific to a CrossReferenceTransaction
		$crtCrossReferenceTransaction->getTransactionDetails()->getMessageDetails()->setCrossReference($crossReference);
		$crtCrossReferenceTransaction->getOverrideCardDetails()->setCV2($this->cv2);
		$crtCrossReferenceTransaction->getTransactionDetails()->getTransactionControl()->getThreeDSecureOverridePolicy()->setValue($threeDSecureOverridePolicy);
		/**
		 * @var $crtrCrossReferenceTransactionResult CrossReferenceTransactionResult
		 * @var $todTransactionOutputData TransactionOutputData
		 */
		$transactionProcessed = $crtCrossReferenceTransaction->processTransaction(
			$crtrCrossReferenceTransactionResult,
			$todTransactionOutputData);

		return new CrossReferenceFinalTransactionResult($transactionProcessed, $crtCrossReferenceTransaction, $crtrCrossReferenceTransactionResult, $todTransactionOutputData, $sessionHandler);
	}

	/**
	 * Sets transaction details shared between CardDetails and CrossReference Transactions and then returns the object
	 * @param  CardDetailsTransaction|CrossReferenceTransaction $toTransactionObject Transaction object to be updated with the shared details
	 * @return CardDetailsTransaction|CrossReferenceTransaction                      Updated transaction object
	 */
	private function setSharedFields($toTransactionObject)
	{
		//set transaction details
		$toTransactionObject->getMerchantAuthentication()->setMerchantID($this->merchantID);
		$toTransactionObject->getMerchantAuthentication()->setPassword($this->merchantPassword);
		$toTransactionObject->getTransactionDetails()->getMessageDetails()->setTransactionType($this->transactionType);
		$toTransactionObject->getTransactionDetails()->getTransactionControl()->getEchoCardType()->setValue(true);
		$toTransactionObject->getTransactionDetails()->getTransactionControl()->getEchoAmountReceived()->setValue(true);
		$toTransactionObject->getTransactionDetails()->getTransactionControl()->getEchoAVSCheckResult()->setValue(true);
		$toTransactionObject->getTransactionDetails()->getTransactionControl()->getEchoCV2CheckResult()->setValue(true);
		//$toTransactionObject->getTransactionDetails()->getTransactionControl()->getThreeDSecureOverridePolicy()->setValue(true);
		$toTransactionObject->getTransactionDetails()->getTransactionControl()->getDuplicateDelay()->setValue(60);
		$toTransactionObject->getTransactionDetails()->getThreeDSecureBrowserDetails()->getDeviceCategory()->setValue(0);
		$toTransactionObject->getTransactionDetails()->getThreeDSecureBrowserDetails()->setAcceptHeaders("*/*");
		$toTransactionObject->getTransactionDetails()->getThreeDSecureBrowserDetails()->setUserAgent($_SERVER["HTTP_USER_AGENT"]);

		//3DSv2 Parameters 

		if ($this->JavaEnabled != "") {
			$toTransactionObject->getTransactionDetails()->getThreeDSecureBrowserDetails()->getJavaEnabled()->setValue($this->JavaEnabled);
		}
		if ($this->JavaScriptEnabled != "") {
			$toTransactionObject->getTransactionDetails()->getThreeDSecureBrowserDetails()->getJavaScriptEnabled()->setValue($this->JavaScriptEnabled);
		}
		if ($this->ScreenWidth != "") {
			$toTransactionObject->getTransactionDetails()->getThreeDSecureBrowserDetails()->getScreenWidth()->setValue($this->ScreenWidth);
		}
		if ($this->ScreenHeight != "") {
			$toTransactionObject->getTransactionDetails()->getThreeDSecureBrowserDetails()->getScreenHeight()->setValue($this->ScreenHeight);
		}
		if ($this->ScreenColourDepth != "") {
			$toTransactionObject->getTransactionDetails()->getThreeDSecureBrowserDetails()->getScreenColourDepth()->setValue($this->ScreenColourDepth);
		}
		if ($this->TimezoneOffset != "") {
			$toTransactionObject->getTransactionDetails()->getThreeDSecureBrowserDetails()->getTimeZone()->setValue($this->TimezoneOffset);

		}
		$toTransactionObject->getTransactionDetails()->getThreeDSecureBrowserDetails()->setLanguage($this->Language);

		$toTransactionObject->getTransactionDetails()->getThreeDSecureNotificationDetails()->setChallengeNotificationURL($this->ChallengeNotificationURL);
		$toTransactionObject->getTransactionDetails()->getThreeDSecureNotificationDetails()->setFingerprintNotificationURL($this->FingerprintNotificationURL);
		
		$toTransactionObject->getTransactionDetails()->getCurrencyCode()->setValue($this->currencyCode);
		$toTransactionObject->getTransactionDetails()->getAmount()->setValue($this->amount);
		$toTransactionObject->getTransactionDetails()->setOrderID($this->orderID);
		$toTransactionObject->getTransactionDetails()->setOrderDescription($this->orderDescription);
		if($this->overrideAVS)
		{
			$toTransactionObject->getTransactionDetails()->getTransactionControl()->setAVSOverridePolicy($this->AVSOverridePolicy);
		}
		if($this->overrideCV2)
		{
			$toTransactionObject->getTransactionDetails()->getTransactionControl()->setCV2OverridePolicy($this->CV2OverridePolicy);
		}
		//set customer details
		$toTransactionObject->getCustomerDetails()->getBillingAddress()->setAddress1($this->address1);
		$toTransactionObject->getCustomerDetails()->getBillingAddress()->setAddress2($this->address2);
		$toTransactionObject->getCustomerDetails()->getBillingAddress()->setAddress3($this->address3);
		$toTransactionObject->getCustomerDetails()->getBillingAddress()->setAddress4($this->address4);
		$toTransactionObject->getCustomerDetails()->getBillingAddress()->setCity($this->city);
		$toTransactionObject->getCustomerDetails()->getBillingAddress()->setState($this->state);
		$toTransactionObject->getCustomerDetails()->getBillingAddress()->setPostCode($this->postcode);
		$toTransactionObject->getCustomerDetails()->setEmailAddress($this->emailAddress);
		$toTransactionObject->getCustomerDetails()->setPhoneNumber($this->phoneNumber);
		$toTransactionObject->getCustomerDetails()->setCustomerIPAddress($this->IPAddress);

		return $toTransactionObject;
	}

	/**
	 * Checks with the gateway whether 3DSecure was successful
	 * @param  string|int                         $crossReference PaymentGateway CrossReference from the initial transaction
	 * @param  string                             $PaRES          The base64 encoded PaRES string returned by the interaction with the ACS server
	 * @throws Exception
	 * @return ThreeDSecureFinalTransactionResult                 Returns a ThreeDSecureFinalTransactionResult object that implements FinalTransactionResult methods
	 */
	public function check3DSecureResult(
		$crossReference,
		$PaRES,
		$sessionHandler)
	{
		$tdsaThreeDSecureAuthentication = new \net\thepaymentgateway\paymentsystem\ThreeDSecureAuthentication($this->rgeplRequestGatewayEntryPointList);
		$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setMerchantID($this->merchantID);
		$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setPassword($this->merchantPassword);
		$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCrossReference($crossReference);
		$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setPaRES($PaRES);

		$transactionProcessed = $tdsaThreeDSecureAuthentication->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);

		return new ThreeDSecureFinalTransactionResult($transactionProcessed, $tdsaThreeDSecureAuthentication, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $sessionHandler);
	}
}

interface PayVectorSessionHandler
{
	public function initialiseSession();
	public function setSessionValue($key, $value);
	public function getSessionValue($key);
	public function unsetSessionValue($key);
}

class NativeSessionHandler implements PayVectorSessionHandler
{
	public function initialiseSession()
	{
		if(session_id() == '')
		{
			//assume session isn't started
			session_start();
		}
	}
	public function setSessionValue($key, $value)
	{
		$_SESSION[$key] = $value;
	}
	public function getSessionValue($key)
	{
		return $_SESSION[$key];
	}
	public function unsetSessionValue($key)
	{
		unset($_SESSION[$key]);
	}
}

/**
 * Interface FinalTransactionResult - Provides a common set of accessor methods for Direct/API result objects and the Hosted Payment Form result object, should return null if the
 *                                    property does not exist in the implementing class
 */
interface FinalTransactionResult
{
	/**
	 * Returns the integration method used for this transaction
	 * @return string
	 */
	public function getIntegrationMethod();
	/**
	 * Returns the transaction method used by this transaction (either TransactionMethod::CardDetailsTransaction, TransactionMethod::CrossReferenceTransaction or
	 * TransactionMethod::ThreeDSecureTransaction).  Only applicable for the Direct API integration method
	 * @return null|string
	 */
	public function getTransactionMethod();
	/**
	 * Returns the order ID as sent to the gateway
	 * @return string
	 */
	public function getOrderID($sessionHandler);
	/**
	 * True if the transaction was processed (irrespective of result), false on failure
	 * @return bool
	 */
	public function transactionProcessed();
	/**
	 * True if the transaction was successful, false on failure
	 * @return bool
	 */
	public function transactionSuccessful();
	/**
	 * Returns the list of gateway entry points used for the high availability system. Only applicable for the Direct API integration method
	 * @return GatewayEntryPointList
	 */
	public function getGatewayEntryPointList();
	/**
	 * Indicates whether the transaction was actually sent to the acquirer for authorisation, or whether it failed before authorisation. Only applicable for the Direct API
	 * integration method
	 * @return bool
	 */
	public function authorisationAttempted();
	/**
	 * Returns the status code associated with this transaction
	 * @return int
	 */
	public function getStatusCode();
	/**
	 * Returns the previous status code associated with this transaction if this transaction was a duplicate
	 * @return int
	 */
	public function getPreviousStatusCode();
	/**
	 * Returns the gateway message associated with this transaction, passes the previous message if the transaction was a duplicate
	 * @return string
	 */
	public function getMessage();
	/**
	 * True if this transaction is a duplicate, false otherwise
	 * @return bool
	 */
	public function duplicateTransaction();
	/**
	 * Returns the cross reference of this transaction
	 * @return string
	 */
	public function getCrossReference();
	/**
	 * If the transaction was successful then the auth code is returned, otherwise null. Only applicable for the Direct API integration method
	 * @return string|null
	 */
	public function getAuthCode();
	/**
	 * If requested by the transaction then returns the result of the address numeric check (PASSED, FAILED, PARTIAL, NOT_CHECKED or UNKNOWN). If not requested then null is returned
	 * @return string|null
	 */
	public function getAddressNumericCheckResult();
	/**
	 * If requested by the transaction then returns the result of the post code check (PASSED, FAILED, PARTIAL, NOT_CHECKED or UNKNOWN). If not requested then null is returned
	 * @return string|null
	 */
	public function getPostCodeCheckResult();
	/**
	 * If requested by the transaction then returns the result of the CV2 check (PASSED, FAILED, NOT_CHECKED or UNKNOWN). If not requested then null is returned
	 * @return string|null
	 */
	public function getCV2CheckResult();
	/**
	 * Returns any error messages associated with this transaction
	 * @return string|null
	 */
	public function getErrorMessage();
	/**
	 * Returns the card type of the customers card as detected by the gateway. If not requested then null is returned
	 * @return string|null
	 */
	public function getCardType();
	/**
	 * The card issuer (if known) as detected by the gateway. If not requested then null is returned
	 * @return string
	 */
	public function getCardIssuer();
	/**
	 * The first six digits of the card number (if available)
	 * @return string|null
	 */
	public function getCardFirstSix($sessionHandler);
	/**
	 * The last four digits of the card number (if available)
	 * @return string|null
	 */
	public function getCardLastFour($sessionHandler);
	/**
	 * Returns the ThreeDSecureOutputData required to generate the 3DS form if required by this transaction, otherwise returns null. Only applicable for the Direct API integration
	 * method
	 * @return ThreeDSecureOutputData|null
	 */
	public function getThreeDSecureOutputData();
	/**
	 * Gets the user facing error message
	 * @return string
	 */
	public function getUserFriendlyMessage();
}

abstract class BaseFinalTransactionResult implements FinalTransactionResult
{
	/**
	 * Integration method used in this transaction
	 * @var null
	 */
	protected $integrationMethod = IntegrationMethod::NONE;
	/**
	 * True if the transaction processed correctly (even with a negative result), false on failure
	 * @var bool
	 */
	protected $transactionProcessed;

	public function getIntegrationMethod()
	{
		return $this->integrationMethod;
	}
	public function getTransactionMethod()
	{
		return null;
	}
	public function getOrderID($sessionHandler)
	{
		return null;
	}
	public function transactionProcessed()
	{
		return $this->transactionProcessed;
	}
	public function transactionSuccessful()
	{
		if($this->getStatusCode() === TransactionResultCode::Successful || $this->getPreviousStatusCode() === TransactionResultCode::Successful)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	public function getGatewayEntryPointList()
	{
		return null;
	}
	public function authorisationAttempted()
	{
		return null;
	}
	public function getStatusCode()
	{
		return null;
	}
	public function getPreviousStatusCode()
	{
		return null;
	}
	public function getMessage()
	{
		return null;
	}
	public function duplicateTransaction()
	{
		return null;
	}
	public function getCrossReference()
	{
		return null;
	}
	public function getAuthCode()
	{
		return null;
	}
	public function getAddressNumericCheckResult()
	{
		return null;
	}
	public function getPostCodeCheckResult()
	{
		return null;
	}
	public function getCV2CheckResult()
	{
		return null;
	}
	public function getErrorMessage()
	{
		return null;
	}
	public function getCardType()
	{
		return null;
	}
	public function getCardIssuer()
	{
		return null;
	}
	public function getCardFirstSix($sessionHandler)
	{
		return null;
	}
	public function getCardLastFour($sessionHandler)
	{
		return null;
	}
	public function getThreeDSecureOutputData()
	{
		return null;
	}
	public function getUserFriendlyMessage()
	{
		if($this->getStatusCode() === 0 || $this->getPreviousStatusCode() === 0)
		{
			return "Transaction Successful. " . $this->getMessage();
		}
		else if($this->getStatusCode() === 4 || $this->getPreviousStatusCode() === 4)
		{
			return "Card Referred";
		}
		else if($this->getStatusCode() === 5 || $this->getPreviousStatusCode() === 5)
		{
			return "Card Declined";
		}
		else
		{
			return "Error";
		}
	}
}

/**
 * Class DirectTransactionResult
 * Implements common accessor methods for a Direct/API transaction
 */
abstract class DirectFinalTransactionResult extends BaseFinalTransactionResult
{
	protected $integrationMethod = IntegrationMethod::DirectAPI;
	/**
	 * Transaction method used in this transaction
	 * @var null|string
	 */
	protected $transactionMethod = TransactionMethod::NONE;
	/**
	 * Transaction Object that was sent to the gateway
	 * @var CardDetailsTransaction|CrossReferenceTransaction|ThreeDSecureAuthentication
	 */
	protected $toTransactionObject;
	/**
	 * Transaction Result Object built from values returned by the gateway
	 * @var PaymentMessageGatewayOutput
	 */
	protected $troTransactionResultObject;
	/**
	 * Transaction Output Data Object built from values returned by the gateway
	 * @var TransactionOutputData
	 */
	protected $todTransactionOutputData;

	/**
	 * @param bool                        $transactionProcessed       True if the transaction was processed regardless of status, false otherwise
	 * @param GatewayTransaction          $toTransactionObject        CardDetailsTransaction|CrossReferenceTransaction|ThreeDSecureAuthentication
	 * @param PaymentMessageGatewayOutput $troTransactionResultObject PaymentMessageGatewayOutput
	 * @param TransactionOutputData       $todTransactionOutputData   TransactionOutputData
	 */
	public function __construct($transactionProcessed, $toTransactionObject, $troTransactionResultObject, $todTransactionOutputData, $sessionHandler)
	{
		$this->transactionProcessed = $transactionProcessed;
		$this->toTransactionObject = $toTransactionObject;
		$this->troTransactionResultObject = $troTransactionResultObject;
		$this->todTransactionOutputData = $todTransactionOutputData;

		//Save orderID in the session if we need to pass through the 3DS page
		if($this->getStatusCode() === 3)
		{			
			$sessionHandler->setSessionValue( 'payvector_transaction_order_id', $this->getOrderID($sessionHandler));
		}
	}
	public function getTransactionMethod()
	{
		return $this->transactionMethod;
	}
	public function getOrderID($sessionHandler)
	{
		return $this->toTransactionObject->getTransactionDetails()->getOrderID();
	}
	public function authorisationAttempted()
	{
		return $this->troTransactionResultObject->getAuthorisationAttempted();
	}
	public function getStatusCode()
	{
		if($this->transactionProcessed)
		{
			$statusCode = $this->troTransactionResultObject->getStatusCode();
		}
		else
		{
			$statusCode = 30;
		}
		return $statusCode;
	}
	public function getPreviousStatusCode()
	{
		$previousTransactionResult = $this->troTransactionResultObject->getPreviousTransactionResult();
		if(isset($previousTransactionResult) && $this->troTransactionResultObject->getPreviousTransactionResult()->getStatusCode()->getHasValue())
		{
			return $this->troTransactionResultObject->getPreviousTransactionResult()->getStatusCode()->getValue();
		}
		else
		{
			return null;
		}
	}
	public function getMessage()
	{
		if($this->transactionProcessed)
		{
			if($this->troTransactionResultObject->getStatusCode() === 20)
			{
				$message = $this->troTransactionResultObject->getPreviousTransactionResult()->getMessage();
			}
			else
			{
				$message = $this->troTransactionResultObject->getMessage();
			}
		}
		else
		{
			$message = "Could not communicate with payment gateway";
		}
		return $message;
	}
	public function duplicateTransaction()
	{
		if($this->troTransactionResultObject->getStatusCode() === 20)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	public function getCrossReference()
	{
		return $this->todTransactionOutputData->getCrossReference();
	}
	public function getAuthCode()
	{
		return $this->todTransactionOutputData->getAuthCode();
	}
	public function getErrorMessage()
	{
		if($this->transactionProcessed)
		{
			$error = $this->troTransactionResultObject->getMessage() . ", ";
			if($this->troTransactionResultObject->getErrorMessages()->getCount() > 0)
			{
				for($loopIndex = 0; $loopIndex < $this->troTransactionResultObject->getErrorMessages()->getCount(); $loopIndex++)
				{
					$error .= $this->troTransactionResultObject->getErrorMessages()->getAt($loopIndex) . ", ";
				}
			}
			$error = rtrim($error, ", ");
		}
		else
		{
			$error = "Could not communicate with the payment gateway.";
		}

		return $error;
	}
	public function getCardType()
	{
		return $this->todTransactionOutputData->getCardTypeData()->getCardType();
	}
	/**
	 * @return GatewayEntryPointList
	 */
	public function getGatewayEntryPointList()
	{
		return $this->todTransactionOutputData->getGatewayEntryPoints();
	}
	public function getThreeDSecureOutputData()
	{
		return $this->todTransactionOutputData->getThreeDSecureOutputData();
	}
	public function getAddressNumericCheckResult()
	{
		return $this->todTransactionOutputData->getAddressNumericCheckResult();
	}
	public function getPostCodeCheckResult()
	{
		return $this->todTransactionOutputData->getPostCodeCheckResult();
	}
	public function getCV2CheckResult()
	{
		return $this->todTransactionOutputData->getCV2CheckResult();
	}
	public function getCardIssuer()
	{
		return $this->todTransactionOutputData->getCardTypeData()->getIssuer();
	}
}

class CardDetailsFinalTransactionResult extends DirectFinalTransactionResult
{
	protected $transactionMethod = TransactionMethod::CardDetailsTransaction;

	/**
	 * @param bool                         $transactionProcessed       True if the transaction was processed regardless of status, false otherwise
	 * @param CardDetailsTransaction       $toTransactionObject        CardDetailsTransaction Object
	 * @param CardDetailsTransactionResult $troTransactionResultObject CardDetailsTransactionResult Object
	 * @param TransactionOutputData        $todTransactionOutputData   TransactionOutputData
	 */
	public function __construct($transactionProcessed,$toTransactionObject, $troTransactionResultObject, $todTransactionOutputData, $sessionHandler)
	{
		parent::__construct($transactionProcessed, $toTransactionObject, $troTransactionResultObject, $todTransactionOutputData, $sessionHandler);
		$sessionHandler->setSessionValue( 'payvector_transaction_card_first_six', substr($toTransactionObject->getCardDetails()->getCardNumber(), 0, 6));
		$sessionHandler->setSessionValue( 'payvector_transaction_card_last_four', substr($toTransactionObject->getCardDetails()->getCardNumber(), -4, 4) );
	}

	public function getCardFirstSix($sessionHandler)
	{
		$cardNumber = $this->toTransactionObject->getCardDetails()->getCardNumber();
		if(isset($cardNumber) && is_string($cardNumber) && strlen($cardNumber) > 5)
		{
			return substr($cardNumber, 0, 6);
		}
		else
		{
			return null;
		}
	}

	public function getCardLastFour($sessionHandler)
	{
		$cardNumber = $this->toTransactionObject->getCardDetails()->getCardNumber();
		if(isset($cardNumber) && is_string($cardNumber) && strlen($cardNumber) > 3)
		{
			return substr($cardNumber, -4, 4);
		}
		else
		{
			return null;
		}
	}
}

class CrossReferenceFinalTransactionResult extends DirectFinalTransactionResult
{
	protected $transactionMethod = TransactionMethod::CrossReferenceTransaction;
}

class ThreeDSecureFinalTransactionResult extends DirectFinalTransactionResult
{
	protected $transactionMethod = TransactionMethod::ThreeDSecureTransaction;
	/**
	 * @var string|int
	 */
	private $cardFirstSix;
	/**
	 * @var string|int
	 */
	private $cardLastFour;
	/**
	 * @var string|int
	 */
	private $orderID;

	/**
	 * This gives the results of the 3D Secure authentication check – will be PASSED, FAILED or UNKNOWN
	 * @return string
	 */
	public function getThreeDSecureAuthenticationCheckResult()
	{
		return $this->todTransactionOutputData->getThreeDSecureAuthenticationCheckResult();
	}
	public function getCardFirstSix($sessionHandler)
	{
  	  	$cardFirstSix = $sessionHandler->getSessionValue('payvector_transaction_card_first_six');
		if (!empty($cardFirstSix)) 
		{
			$this->cardFirstSix = $sessionHandler->getSessionValue('payvector_transaction_card_first_six');		
			$sessionHandler->unsetSessionValue('payvector_transaction_card_first_six');
		}
		return $this->cardFirstSix;
	}
	public function getCardLastFour($sessionHandler)
	{
		$cardLastFour = $sessionHandler->getSessionValue('payvector_transaction_card_last_four');
		if (!empty($cardLastFour)) 
		{			
			$this->cardLastFour = $sessionHandler->getSessionValue('payvector_transaction_card_last_four');			
			$sessionHandler->unsetSessionValue('payvector_transaction_card_last_four');
		}
		return $this->cardLastFour;
	}
	public function getOrderID($sessionHandler)
	{
		$orderID = $sessionHandler->getSessionValue('payvector_transaction_order_id');
		if (!empty($orderID))  
		{
			$this->orderID = $sessionHandler->getSessionValue('payvector_transaction_order_id');			
			$sessionHandler->unsetSessionValue('payvector_transaction_order_id');
		}
		return $this->orderID;
	}
}

/**
 * Class HostedPaymentFormTransactionResult
 * Implements common accessor methods for a Hosted Payment Form transaction
 */
class HostedPaymentFormFinalTransactionResult extends BaseFinalTransactionResult
{
	protected $integrationMethod = IntegrationMethod::HostedPaymentForm;
	/**
	 * Transaction Result
	 * @var TransactionResult
	 */
	private $trTransactionResult;

	/**
	 * @param $trTransactionResult TransactionResult Result object generated at the end of the HPF/Transparent Redirect transaction
	 */
	public function __construct($trTransactionResult)
	{
		$this->transactionProcessed = true;
		$this->trTransactionResult = $trTransactionResult;
	}
	public function getOrderID($sessionHandler)
	{
		return $this->trTransactionResult->getOrderID();
	}
	public function getStatusCode()
	{
		return $this->trTransactionResult->getStatusCode();
	}
	public function getPreviousStatusCode()
	{
		return $this->trTransactionResult->getPreviousStatusCode();
	}
	public function getMessage()
	{
		if($this->trTransactionResult->getStatusCode() === 20)
		{
			return $this->trTransactionResult->getPreviousMessage();
		}
		else
		{
			return $this->trTransactionResult->getMessage();
		}
	}
	public function duplicateTransaction()
	{
		if($this->trTransactionResult->getStatusCode() === 20)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	public function getCrossReference()
	{
		return $this->trTransactionResult->getCrossReference();
	}
	public function getErrorMessage()
	{
		return $this->trTransactionResult->getMessage();
	}
	public function getCardType()
	{
		return $this->trTransactionResult->getCardType();
	}
	public function getCardFirstSix($sessionHandler)
	{
		return $this->trTransactionResult->getCardNumberFirstSix();
	}
	public function getCardLastFour($sessionHandler)
	{
		return $this->trTransactionResult->getCardNumberLastFour();
	}
	public function getAddressNumericCheckResult()
	{
		return $this->trTransactionResult->getAddressNumericCheckResult();
	}
	public function getPostCodeCheckResult()
	{
		return $this->trTransactionResult->getPostCodeCheckResult();
	}
	public function getCV2CheckResult()
	{
		return $this->trTransactionResult->getCV2CheckResult();
	}
	public function getCardIssuer()
	{
		return $this->trTransactionResult->getCardIssuer();
	}
}

/**
 * Class TransparentRedirectFinalTransactionResult
 * Implements common accessor methods for a Transparent Redirect transaction
 */
class TransparentRedirectFinalTransactionResult extends HostedPaymentFormFinalTransactionResult
{
	protected $integrationMethod = IntegrationMethod::TransparentRedirect;
}

//Define quasi enums
abstract class BasicEnum
{
	/**
	 * @var null
	 */
	const NONE = null;
	/**
	 * @var array|null
	 */
	private static $constCache = null;

	private function __construct() {}

	/**
	 * @return array|null
	 */
	private static function getConstants()
	{
		if (self::$constCache === NULL)
		{
			$reflect = new ReflectionClass(get_called_class());
			self::$constCache = $reflect->getConstants();
		}
		return self::$constCache;
	}

	/**
	 * @param  string $name   Key to compare against
	 * @param  bool   $strict True if you want a case sensitive check
	 * @return bool           True on matching key, false on non-match
	 */
	public static function isValidName($name, $strict = false)
	{
		$constants = self::getConstants();
		if ($strict)
		{
			return array_key_exists($name, $constants);
		}
		$keys = array_map('strtolower', array_keys($constants));
		return in_array(strtolower($name), $keys);
	}

	/**
	 * @param  string|int $value
	 * @return bool
	 */
	public static function isValidValue($value)
	{
		$values = array_values(self::getConstants());
		return in_array($value, $values, $strict = true);
	}
}

final class IntegrationMethod extends BasicEnum
{
	/**
	 * @var string
	 */
	const DirectAPI = "Direct API";
	/**
	 * @var string
	 */
	const HostedPaymentForm = "Hosted Payment Form";
	/**
	 * @var string
	 */
	const TransparentRedirect = "Transparent Redirect";
}
final class TransactionMethod extends BasicEnum
{
	/**
	 * @var string
	 */
	const CardDetailsTransaction = "CardDetailsTransaction";
	/**
	 * @var string
	 */
	const CrossReferenceTransaction = "CrossReferenceTransaction";
	/**
	 * @var string
	 */
	const ThreeDSecureTransaction = "ThreeDSecureTransaction";
}
final class TransactionResultCode extends BasicEnum
{
	/**
	 * @var int
	 */
	const Successful = 0;
	/**
	 * @var int
	 */
	const Incomplete = 3;
	/**
	 * @var int
	 */
	const Referred = 4;
	/**
	 * @var int
	 */
	const Declined = 5;
	/**
	 * @var int
	 */
	const DuplicateTransaction = 20;
	/**
	 * @var int
	 */
	const Failed = 30;
}
final class TransactionType extends BasicEnum
{
	/**
	 * @var string
	 */
	const Sale = "SALE";
	/**
	 * @var string
	 */
	const PreAuth = "PREAUTH";
	/**
	 * @var string
	 */
	const Refund = "REFUND";
	/**
	 * @var string
	 */
	const Collection = "COLLECTION";
	/**
	 * @var string
	 */
	const Void = "VOID";
	/**
	 * @var string
	 */
	const Retry = "Retry";
}
final class HashMethod extends BasicEnum
{
	/**
	 * @var string
	 */
	const SHA1 = "SHA1";
	/**
	 * @var string
	 */
	const MD5 = "MD5";
	/**
	 * @var string
	 */
	const HMACSHA1 = "HMACSHA1";
	/**
	 * @var string
	 */
	const HMACMD5 = "HMACMD5";
}
final class ResultDeliveryMethod extends BasicEnum
{
	/**
	 * @var string
	 */
	const POST = "POST";
	/**
	 * @var string
	 */
	const SERVER = "SERVER";
	/**
	 * @var string
	 */
	const SERVER_PULL = "SERVER_PULL";
}
final class SaleType extends BasicEnum
{
	/**
	 * @var string
	 */
	const NewSale = "new_card";
	/**
	 * @var string
	 */
	const CrossReferenceSale = "stored_card";
}