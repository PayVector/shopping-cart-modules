<?php
	namespace net\thepaymentgateway\paymentsystem;
	use Opencart\System\Library\Extension\Payvector\Lib;

	require_once("TPG_Common.php");
	require_once("SOAP.php");

	/*****************/
	/* Input classes */
	/*****************/
	class RequestGatewayEntryPoint extends GatewayEntryPoint
	{
		private $m_nRetryAttempts;

	  	public function getRetryAttempts()
	  	{
	  		return $this->m_nRetryAttempts;
	  	}

		//constructor
	  	public function __construct($szEntryPointURL, $nMetric, $nRetryAttempts)
	   	{
	   		//do NOT forget to call the parent constructor too
	   		//parent::GatewayEntryPoint($szEntryPointURL, $nMetric);
	   		GatewayEntryPoint::__construct($szEntryPointURL, $nMetric);

	     	$this->m_nRetryAttempts = $nRetryAttempts;
	   	}
	}

	class RequestGatewayEntryPointList
	{
		private $m_lrgepRequestGatewayEntryPoint;

		public function getAt($nIndex)
		{
			if ($nIndex < 0 ||
				$nIndex >= count($this->m_lrgepRequestGatewayEntryPoint))
			{
				throw new \Exception("Array index out of bounds");
			}

			return $this->m_lrgepRequestGatewayEntryPoint[$nIndex];
		}

		public function getCount()
		{
			return count($this->m_lrgepRequestGatewayEntryPoint);
		}

		public function sort($ComparerClassName, $ComparerMethodName)
		{
			usort($this->m_lrgepRequestGatewayEntryPoint, array("$ComparerClassName","$ComparerMethodName"));
		}

		public function add($EntryPointURL, $nMetric, $nRetryAttempts)
		{
			array_push($this->m_lrgepRequestGatewayEntryPoint, new RequestGatewayEntryPoint($EntryPointURL, $nMetric, $nRetryAttempts));
		}

		//constructor
		public function __construct()
		{
			$this->m_lrgepRequestGatewayEntryPoint = array();
		}
	}

	class GenericVariable
	{
		private $m_szName;
	   	private $m_szValue;

	   	public function getName()
	   	{
	   		return $this->m_szName;
	   	}
	   	public function setName($name)
	   	{
	   		$this->m_szName = $name;
	   	}
	   	public function getValue()
	   	{
	   		return $this->m_szValue;
	   	}
	   	public function setValue($value)
	   	{
	   		$this->m_szValue = $value;
	   	}

	   	//constructor
	   	public function __construct()
	    {
	    	$this->m_szName = "";
	    	$this->m_szValue = "";
	    }
	}

	class GenericVariableList
	{
		private $m_lgvGenericVariableList;

		public function getAt($intOrStringValue)
		{
			$nCount = 0;
			$boFound = false;
			$gvGenericVariable = null;

			if (is_int($intOrStringValue))
			{
				if ($intOrStringValue < 0 ||
					$intOrStringValue >= count($this->m_lgvGenericVariableList))
				{
					throw new \Exception("Array index out of bounds");
				}

				return $this->m_lgvGenericVariableList[$intOrStringValue];
			}
			elseif (is_string($intOrStringValue))
			{
				if ($intOrStringValue == null ||
					$intOrStringValue == "")
				{
					return (null);
				}

				while (!$boFound &&
						$nCount < count($this->m_lgvGenericVariableList))
				{
					if (strtoupper($this->m_lgvGenericVariableList[$nCount]->getName()) ==
						strtoupper($intOrStringValue))
					{
						$gvGenericVariable = $this->m_lgvGenericVariableList[$nCount];
						$boFound = true;
					}
					$nCount++;
				}

				return $gvGenericVariable;
			}
			else
			{
				throw new \Exception("Invalid parameter type:$intOrStringValue");
			}
		}

		public function getCount()
		{
			return count($this->m_lgvGenericVariableList);
		}

		public function add($szName, $szValue)
		{
			if ($szName != null &&
				$szName != "")
			{
				$genericVariable = new GenericVariable();
				$genericVariable->setName($szName);
				$genericVariable->setValue($szValue);
	        	array_push($this->m_lgvGenericVariableList, $genericVariable);
			}
		}

		//constructor
		public function __construct()
		{
			$this->m_lgvGenericVariableList = array();
		}
	}

	class CustomerDetails
	{
		private $m_adBillingAddress;
	    private $m_szEmailAddress;
	    private $m_szPhoneNumber;
	    private $m_szCustomerIPAddress;

	    public function getBillingAddress()
	    {
	    	return $this->m_adBillingAddress;
	    }
	    public function getEmailAddress()
	    {
	    	return $this->m_szEmailAddress;
	    }
	    public function setEmailAddress($emailAddress)
	    {
	    	$this->m_szEmailAddress = $emailAddress;
	    }
	    public function getPhoneNumber()
	    {
	    	return $this->m_szPhoneNumber;
	    }
	    public function setPhoneNumber($phoneNumber)
	    {
	    	$this->m_szPhoneNumber = $phoneNumber;
	    }
	    public function getCustomerIPAddress()
	    {
	    	return $this->m_szCustomerIPAddress;
	    }
	    public function setCustomerIPAddress($IPAddress)
	    {
	    	$this->m_szCustomerIPAddress = $IPAddress;
	    }

	    //constructor
	    public function __construct()
	    {
	    	$this->m_adBillingAddress = new BillingAddress();
	    	$this->m_szEmailAddress = "";
	    	$this->m_szPhoneNumber = "";
	    	$this->m_szCustomerIPAddress = "";
	    }
	}

	class BillingAddress
	{
		private $m_szAddress1;
	    private $m_szAddress2;
	    private $m_szAddress3;
	    private $m_szAddress4;
	    private $m_szCity;
	    private $m_szState;
	    private $m_szPostCode;
	    private $m_nCountryCode;

	    public function getAddress1()
	    {
	    	return $this->m_szAddress1;
	    }
	    public function setAddress1($address1)
	    {
	    	$this->m_szAddress1 = $address1;
	    }
	    public function getAddress2()
	    {
	    	return $this->m_szAddress2;
	    }
	    public function setAddress2($address2)
	    {
	    	$this->m_szAddress2 = $address2;
	    }
	    public function getAddress3()
	    {
	    	return $this->m_szAddress3;
	    }
	    public function setAddress3($address3)
	    {
	    	$this->m_szAddress3 = $address3;
	    }
	    public function getAddress4()
	    {
	    	return $this->m_szAddress4;
	    }
	    public function setAddress4($address4)
	    {
	    	$this->m_szAddress4 = $address4;
	    }
	    public function getCity()
	    {
	    	return $this->m_szCity;
	    }
	    public function setCity($city)
	    {
	    	$this->m_szCity = $city;
	    }
	    public function getState()
	    {
	    	return $this->m_szState;
	    }
	    public function setState($state)
	    {
	    	$this->m_szState = $state;
	    }
	    public function getPostCode()
	    {
	    	return $this->m_szPostCode;
	    }
	    public function setPostCode($postCode)
	    {
	    	$this->m_szPostCode = $postCode;
	    }
	    public function getCountryCode()
	    {
	  		return $this->m_nCountryCode;
	    }

	    //constructor
	    public function __construct()
	    {
	    	$this->m_szAddress1 = "";
	    	$this->m_szAddress2 = "";
	    	$this->m_szAddress3 = "";
	    	$this->m_szAddress4 = "";
	    	$this->m_szCity = "";
	    	$this->m_szState = "";
	    	$this->m_szPostCode = "";
	    	$this->m_nCountryCode = new \net\thepaymentgateway\common\NullableInt();
	    }
	}

	abstract class CreditCardDate
	{
		private  $m_nMonth;
	    private $m_nYear;

	    public function getMonth()
	    {
	    	return $this->m_nMonth;
	    }
	    public function getYear()
	    {
	    	return $this->m_nYear;
	    }

	    //constructor
	    public function __construct()
	    {
	    	$this->m_nMonth = new \net\thepaymentgateway\common\NullableInt();
	    	$this->m_nYear = new \net\thepaymentgateway\common\NullableInt();
	    }
	}

	class ExpiryDate extends CreditCardDate
	{
	    public function __construct()
	    {
	      	parent::__construct();
		}
	}

	class StartDate extends CreditCardDate
	{
	    public function __construct()
	    {
	      	parent::__construct();
		}
	}

	class CardDetails
	{
		private $m_szCardName;
	    private $m_szCardNumber;
	    private $m_edExpiryDate;
	    private $m_sdStartDate;
	    private $m_szIssueNumber;
	    private $m_szCV2;

	    public function getCardName()
	    {
	    	return $this->m_szCardName;
	    }
	    public function setCardName($cardName)
	    {
	    	$this->m_szCardName = $cardName;
	    }
	    public function getCardNumber()
	    {
	    	return $this->m_szCardNumber;
	    }
	    public function setCardNumber($cardNumber)
	    {
	    	$this->m_szCardNumber = $cardNumber;
	    }
	    public function getExpiryDate()
	    {
	    	return $this->m_edExpiryDate;
	    }
	    public function getStartDate()
	    {
	    	return $this->m_sdStartDate;
	    }
	    public function getIssueNumber()
	    {
	    	return $this->m_szIssueNumber;
	    }
	    public function setIssueNumber($issueNumber)
	    {
	    	$this->m_szIssueNumber = $issueNumber;
	    }
	    public function getCV2()
	    {
	    	return $this->m_szCV2;
	    }
	    public function setCV2($cv2)
	    {
	    	$this->m_szCV2 = $cv2;
	    }

	    //constructor
	    public function __construct()
	    {
	    	$this->m_szCardName = "";
	    	$this->m_szCardNumber = "";
	    	$this->m_edExpiryDate = new ExpiryDate();
	    	$this->m_sdStartDate = new StartDate();
	    	$this->m_szIssueNumber = "";
	    	$this->m_szCV2 = "";
	    }
	}

	class OverrideCardDetails extends CardDetails
	{
	    public function __construct()
	    {
			parent::__construct();
		}
	}

	class MerchantAuthentication
	{
		private $m_szMerchantID;
	    private $m_szPassword;

	    public function getMerchantID()
	    {
	    	return $this->m_szMerchantID;
	    }
	    public function setMerchantID($merchantID)
	    {
	    	$this->m_szMerchantID = $merchantID;
	    }
	    public function getPassword()
	    {
	    	return $this->m_szPassword;
	    }
	    public function setPassword($password)
	    {
	    	$this->m_szPassword = $password;
	    }

	    //constructor
	    public function __construct()
	    {
	    	$this->m_szMerchantID = "";
	    	$this->m_szPassword = "";
	    }
	}

	class MessageDetails
	{
		private $m_szTransactionType;
	    private $m_boNewTransaction;
	    private $m_szCrossReference;
        private $m_szClientReference;
        private $m_szPreviousClientReference;

	    public function getTransactionType()
	    {
	    	return $this->m_szTransactionType;
	    }
	    public function setTransactionType($transactionType)
	    {
	    	$this->m_szTransactionType = $transactionType;
	    }
	    public function getNewTransaction()
	    {
	    	return $this->m_boNewTransaction;
	    }
	    public function getCrossReference()
	    {
	    	return $this->m_szCrossReference;
	    }
	    public function setCrossReference($crossReference)
	    {
	    	$this->m_szCrossReference = $crossReference;
	    }
	    public function getClientReference()
	    {
	    	return $this->m_szClientReference;
	    }
	    public function setClientReference($clientReference)
	    {
	    	$this->m_szClientReference = $clientReference;
	    }
	    public function getPreviousClientReference()
	    {
	    	return $this->m_szPreviousClientReference;
	    }
	    public function setPreviousClientReference($previousClientReference)
	    {
	    	$this->m_szPreviousClientReference = $previousClientReference;
	    }
	    //constructor
	    public function __construct()
	    {
	    	$this->m_szTransactionType = "";
	    	$this->m_szCrossReference = "";
	    	$this->m_boNewTransaction = new \net\thepaymentgateway\common\NullableBool();
            $this->m_szClientReference = "";
            $this->m_szPreviousClientReference = "";
	    }
	}

	class TransactionDetails
	{
		private $m_mdMessageDetails;
	    private $m_nAmount;
	    private $m_nCurrencyCode;
        private $m_szCaptureEnvironment;
        private $m_boContinuousAuthority;
	    private $m_szOrderID;
	    private $m_szOrderDescription;
	    private $m_tcTransactionControl;
	    private $m_tdsbdThreeDSecureBrowserDetails;
		private $m_tdsptThreeDSecurePassthroughData;
        private $m_tdsndThreeDSecureNotificationDetails;
        private $m_tdsaeThreeDSecureAuthenticationExemption;

	    public function getMessageDetails()
	    {
	    	return $this->m_mdMessageDetails;
	    }
	    public function getAmount()
	    {
	    	return $this->m_nAmount;
	    }
	    public function getCurrencyCode()
	    {
	    	return $this->m_nCurrencyCode;
	    }
	    public function getCaptureEnvironment()
	    {
	    	return $this->m_szCaptureEnvironment;
	    }
	    public function setCaptureEnvironment($captureEnvironment)
	    {
	    	$this->m_szCaptureEnvironment = $captureEnvironment;
	    }
	    public function getContinuousAuthority()
	    {
	    	return $this->m_boContinuousAuthority;
	    }
	   	public function getOrderID()
	    {
	    	return $this->m_szOrderID;
	    }
	    public function setOrderID($orderID)
	    {
	    	$this->m_szOrderID = $orderID;
	    }
	    public function getOrderDescription()
	    {
	    	return $this->m_szOrderDescription;
	    }
	    public function setOrderDescription($orderDescription)
	    {
	    	$this->m_szOrderDescription = $orderDescription;
	    }
	    public function getTransactionControl()
	    {
	    	return $this->m_tcTransactionControl;
	    }
	    public function getThreeDSecureBrowserDetails()
	    {
	    	return $this->m_tdsbdThreeDSecureBrowserDetails;
	    }
	    public function getThreeDSecurePassthroughData()
	    {
	    	return $this->m_tdsptThreeDSecurePassthroughData;
	    }
	    public function getThreeDSecureNotificationDetails()
	    {
	    	return $this->m_tdsndThreeDSecureNotificationDetails;
	    }
	    public function getThreeDSecureAuthenticationExemption()
	    {
	    	return $this->m_tdsaeThreeDSecureAuthenticationExemption;
	    }

	    //constructor
	    public function __construct()
	    {
			$this->m_mdMessageDetails = new MessageDetails();
	    	$this->m_nAmount = new \net\thepaymentgateway\common\NullableInt();
            $this->m_szCaptureEnvironment = "";
            $this->m_boContinuousAuthority = new \net\thepaymentgateway\common\NullableBool();
	    	$this->m_nCurrencyCode = new \net\thepaymentgateway\common\NullableInt();
	    	$this->m_szOrderID = "";
	    	$this->m_szOrderDescription = "";
	    	$this->m_tcTransactionControl = new TransactionControl();
	    	$this->m_tdsbdThreeDSecureBrowserDetails = new ThreeDSecureBrowserDetails();
			$this->m_tdsptThreeDSecurePassthroughData = new ThreeDSecurePassthroughData();
			$this->m_tdsndThreeDSecureNotificationDetails = new ThreeDSecureNotificationDetails();
			$this->m_tdsaeThreeDSecureAuthenticationExemption = new ThreeDSecureAuthenticationExemption();
	    }
	}

	class ThreeDSecureBrowserDetails
	{
		private $m_nDeviceCategory;
	    private $m_szAcceptHeaders;
	    private $m_szUserAgent;
		private $m_szLanguage;
        private $m_nScreenHeight;
        private $m_nScreenWidth;
        private $m_nScreenColourDepth;
        private $m_nTimeZone;
        private $m_boJavaEnabled;
        private $m_boJavaScriptEnabled;

	    public function getDeviceCategory()
	    {
	    	return $this->m_nDeviceCategory;
	    }
	    public function getAcceptHeaders()
	    {
	    	return $this->m_szAcceptHeaders;
	    }
	    public function setAcceptHeaders($acceptHeaders)
	    {
	    	$this->m_szAcceptHeaders = $acceptHeaders;
	    }
	    public function getUserAgent()
	    {
	    	return $this->m_szUserAgent;
	    }
	    public function setUserAgent($userAgent)
	    {
	    	$this->m_szUserAgent = $userAgent;
	    }
		public function getLanguage()
	    {
	    	return $this->m_szLanguage;
	    }
	    public function setLanguage($language)
	    {
	    	$this->m_szLanguage = $language;
	    }
		public function getScreenHeight()
	    {
	    	return $this->m_nScreenHeight;
	    }
		public function getScreenWidth()
	    {
	    	return $this->m_nScreenWidth;
	    }
		public function getScreenColourDepth()
	    {
	    	return $this->m_nScreenColourDepth;
	    }
		public function getTimeZone()
	    {
	    	return $this->m_nTimeZone;
	    }
		public function getJavaEnabled()
	    {
	    	return $this->m_boJavaEnabled;
	    }
		public function getJavaScriptEnabled()
	    {
	    	return $this->m_boJavaScriptEnabled;
	    }

	    //constructor
	    public function __construct()
	    {
	    	$this->m_nDeviceCategory = new \net\thepaymentgateway\common\NullableInt();
	    	$this->m_szAcceptHeaders = "";
	    	$this->m_szUserAgent = "";
			$this->m_szLanguage = "";
			$this->m_nScreenHeight = new \net\thepaymentgateway\common\NullableInt();
			$this->m_nScreenWidth = new \net\thepaymentgateway\common\NullableInt();
			$this->m_nScreenColourDepth = new \net\thepaymentgateway\common\NullableInt();
			$this->m_nTimeZone = new \net\thepaymentgateway\common\NullableInt();
			$this->m_boJavaEnabled = new \net\thepaymentgateway\common\NullableBool();
			$this->m_boJavaScriptEnabled = new \net\thepaymentgateway\common\NullableBool();
	    }
	}

	class TransactionControl
	{
		private $m_boEchoCardType;
	    private $m_boEchoAVSCheckResult;
	    private $m_boEchoCV2CheckResult;
	   	private $m_boEchoAmountReceived;
	    private $m_nDuplicateDelay;
	    private $m_szAVSOverridePolicy;
	    private $m_szCV2OverridePolicy;
	    private $m_boThreeDSecureOverridePolicy;
	    private $m_szAuthCode;
	    private $m_lgvCustomVariables;

	    public function getEchoCardType()
	    {
	    	return $this->m_boEchoCardType;
	    }
	    public function getEchoAVSCheckResult()
	    {
	    	return $this->m_boEchoAVSCheckResult;
	    }
	    public function getEchoCV2CheckResult()
	    {
	    	return $this->m_boEchoCV2CheckResult;
	    }
	    public function getEchoAmountReceived()
	    {
	    	return $this->m_boEchoAmountReceived;
	    }
	    public function getDuplicateDelay()
	    {
	    	return $this->m_nDuplicateDelay;
	    }
	    public function getAVSOverridePolicy()
	    {
	    	return $this->m_szAVSOverridePolicy;
	    }
	    public function setAVSOverridePolicy($AVSOverridePolicy)
	    {
	    	$this->m_szAVSOverridePolicy = $AVSOverridePolicy;
	    }
	    public function getCV2OverridePolicy()
	    {
	    	return $this->m_szCV2OverridePolicy;
	    }
	    public function setCV2OverridePolicy($CV2OverridePolicy)
	    {
	    	$this->m_szCV2OverridePolicy = $CV2OverridePolicy;
	    }
	    public function getThreeDSecureOverridePolicy()
	    {
	    	return $this->m_boThreeDSecureOverridePolicy;
	    }
	    public function getAuthCode()
	    {
	    	return $this->m_szAuthCode;
	    }
	    public function setAuthCode($authCode)
	    {
	    	$this->m_szAuthCode = $authCode;
	    }
	    public function getCustomVariables()
	    {
	    	return $this->m_lgvCustomVariables;
	    }

	    //constructor
	    public function __construct()
	    {
	    	$this->m_boEchoCardType = new \net\thepaymentgateway\common\NullableBool();
	    	$this->m_boEchoAVSCheckResult = new \net\thepaymentgateway\common\NullableBool();
	    	$this->m_boEchoCV2CheckResult = new \net\thepaymentgateway\common\NullableBool();
	    	$this->m_boEchoAmountReceived = new \net\thepaymentgateway\common\NullableBool();
	    	$this->m_nDuplicateDelay = new \net\thepaymentgateway\common\NullableInt();
	    	$this->m_szAVSOverridePolicy = "";
	    	$this->m_szCV2OverridePolicy = "";
	    	$this->m_boThreeDSecureOverridePolicy = new \net\thepaymentgateway\common\NullableBool();
	    	$this->m_szAuthCode = "";
	    	$this->m_lgvCustomVariables = new GenericVariableList();
	    }
	}

	class ThreeDSecureInputData
	{
		private $m_szCrossReference;
	    private $m_szMethodData;
		private $m_szCRES;

	    public function getCrossReference()
	    {
	    	return $this->m_szCrossReference;
	    }
	    public function setCrossReference($crossReference)
	    {
	    	$this->m_szCrossReference = $crossReference;
	    }
	    public function getMethodData()
	    {
	    	return $this->m_szMethodData;
	    }
	    public function setMethodData($methodData)
	    {
	    	$this->m_szMethodData = $methodData;
	    }
		public function getCRES()
	    {
	    	return $this->m_szCRES;
	    }
	    public function setCRES($CRES)
	    {
	    	$this->m_szCRES = $CRES;
	    }

	    //constructor
	    public function __construct()
	    {
	    	$this->m_szCrossReference = "";
	    	$this->m_szMethodData = "";
			$this->m_szCRES = "";
	    }
	}

	class ThreeDSecureEnvironmentData
	{
		private $m_szCrossReference;
	    private $m_szMethodData;

	    public function getCrossReference()
	    {
	    	return $this->m_szCrossReference;
	    }
	    public function setCrossReference($crossReference)
	    {
	    	$this->m_szCrossReference = $crossReference;
	    }
	    public function getMethodData()
	    {
	    	return $this->m_szMethodData;
	    }
	    public function setMethodData($methodData)
	    {
	    	$this->m_szMethodData = $methodData;
	    }

	    //constructor
	    public function __construct()
	    {
	    	$this->m_szCrossReference = "";
	    	$this->m_szMethodData = "";
	    }
	}

	class ThreeDSecureNotificationDetails
	{
		private $m_szFingerprintNotificationURL;
	    private $m_szChallengeNotificationURL;

	    public function getFingerprintNotificationURL()
	    {
	    	return $this->m_szFingerprintNotificationURL;
	    }
	    public function setFingerprintNotificationURL($fingerprintNotificationURL)
	    {
	    	$this->m_szFingerprintNotificationURL = $fingerprintNotificationURL;
	    }
	    public function getChallengeNotificationURL()
	    {
	    	return $this->m_szChallengeNotificationURL;
	    }
	    public function setChallengeNotificationURL($challengeNotificationURL)
	    {
	    	$this->m_szChallengeNotificationURL = $challengeNotificationURL;
	    }

	    //constructor
	    public function __construct()
	    {
	    	$this->m_szFingerprintNotificationURL = "";
	    	$this->m_szChallengeNotificationURL = "";
	    }
	}

	class ThreeDSecurePassthroughData
	{
	 	private $m_szEnrolmentStatus;
	    private $m_szAuthenticationStatus;
	    private $m_szECI;
	    private $m_szAuthenticationValue;
	    private $m_szTransactionIdentifier;
		private $m_szThreeDSecureVersion;

	    function getEnrolmentStatus()
	    {
	    	return $this->m_szEnrolmentStatus;
	    }
	    public function setEnrolmentStatus($enrolmentStatus)
	    {
	    	$this->m_szEnrolmentStatus = $enrolmentStatus;
	    }
	    function getAuthenticationStatus()
	    {
	    	return $this->m_szAuthenticationStatus;
	    }
	    public function setAuthenticationStatus($authenticationStatus)
	    {
	    	$this->m_szAuthenticationStatus = $authenticationStatus;
	    }
	    function getECI()
	    {
	    	return $this->m_szECI;
	    }
	    public function setECI($ECI)
	    {
	    	$this->m_szECI = $ECI;
	    }
	    function getAuthenticationValue()
	    {
	    	return $this->m_szAuthenticationValue;
	    }
	    public function setAuthenticationValue($authenticationValue)
	    {
	    	$this->m_szAuthenticationValue = $authenticationValue;
	    }
	    function getTransactionIdentifier()
	    {
	    	return $this->m_szTransactionIdentifier;
	    }
	    public function setTransactionIdentifier($transactionIdentifier)
	    {
	    	$this->m_szTransactionIdentifier = $transactionIdentifier;
	    }
	    function getThreeDSecureVersion()
	    {
	    	return $this->m_szThreeDSecureVersion;
	    }
	    public function setThreeDSecureVersion($threeDSecureVersion)
	    {
	    	$this->m_szThreeDSecureVersion = $threeDSecureVersion;
	    }

	    //constructor
	    function __construct()
	    {
	     	$this->m_szEnrolmentStatus = "";
	        $this->m_szAuthenticationStatus = "";
	        $this->m_szECI = "";
	        $this->m_szAuthenticationValue = "";
	        $this->m_szTransactionIdentifier = "";
			$this->m_szThreeDSecureVersion = "";
	    }
	}

	class ThreeDSecureAuthenticationExemption
	{
		private $m_szExemptionType;
	    private $m_szExemptionReason;
		private $m_szExemptionIndicator;

	    public function getExemptionType()
	    {
	    	return $this->m_szExemptionType;
	    }
	    public function setExemptionType($exemptionType)
	    {
	    	$this->m_szExemptionType = $exemptionType;
	    }
	    public function getExemptionReason()
	    {
	    	return $this->m_szExemptionReason;
	    }
	    public function setExemptionReason($exemptionReason)
	    {
	    	$this->m_szExemptionReason = $exemptionReason;
	    }
		public function getExemptionIndicator()
	    {
	    	return $this->m_szExemptionIndicator;
	    }
	    public function setExemptionIndicator($exemptionIndicator)
	    {
	    	$this->m_szExemptionIndicator = $exemptionIndicator;
	    }

	    //constructor
	    public function __construct()
	    {
	    	$this->m_szExemptionType = "";
	    	$this->m_szExemptionReason = "";
			$this->m_szExemptionIndicator = "";
	    }
	}

	/******************/
	/* Output classes */
	/******************/
	class Issuer
	{
		private $m_szIssuer;
		private $m_nISOCode;

		public function getValue()
		{
			return $this->m_szIssuer;
		}

		public function getISOCode()
		{
			return $this->m_nISOCode;
		}

		//constructor
	    public function __construct($szIssuer, \net\thepaymentgateway\common\NullableInt $nISOCode = null)
	    {
	        $this->m_szIssuer = $szIssuer;
	        $this->m_nISOCode = $nISOCode;
	    }
	}

	class CardTypeData
	{
	    private $m_szCardType;
	    private $m_iIssuer;
	    private $m_boLuhnCheckRequired;
	    private $m_szIssueNumberStatus;
	    private $m_szStartDateStatus;

	    public function getCardType()
	    {
	        return $this->m_szCardType;
	    }

	    public function getIssuer()
	    {
	    	return $this->m_iIssuer;
	    }

	    public function getLuhnCheckRequired()
	    {
	        return $this->m_boLuhnCheckRequired;
	    }

	    public function getIssueNumberStatus()
	    {
	        return $this->m_szIssueNumberStatus;
	    }

	    public function getStartDateStatus()
	    {
	        return $this->m_szStartDateStatus;
	    }

	    //constructor
	    public function __construct($szCardType, $iIssuer, \net\thepaymentgateway\common\NullableBool $boLuhnCheckRequired = null, $szIssueNumberStatus, $szStartDateStatus)
	    {
	        $this->m_szCardType = $szCardType;
	        //$this->m_szIssuer = $szIssuer;
	        $this->m_iIssuer = $iIssuer;
	        $this->m_boLuhnCheckRequired = $boLuhnCheckRequired;
	        $this->m_szIssueNumberStatus = $szIssueNumberStatus;
	        $this->m_szStartDateStatus = $szStartDateStatus;
	    }
	}

	class GatewayEntryPoint
	{
		private $m_szEntryPointURL;
	    private $m_nMetric;

	 	public function getEntryPointURL()
	 	{
	 		return $this->m_szEntryPointURL;
	 	}

	    public function getMetric()
	    {
	    	return $this->m_nMetric;
	    }

		public function toXmlString()
		{
			$szXmlString = "<GatewayEntryPoint EntryPointURL=\"" . $this -> m_szEntryPointURL . "\" Metric=\"" . $this -> m_nMetric . "\" />";

			return ($szXmlString);
		}

	    //constructor
	    public function __construct($szEntryPointURL, $nMetric)
	    {
			$this->m_szEntryPointURL = $szEntryPointURL;
			$this->m_nMetric = $nMetric;
	    }
	}

	class GatewayEntryPointList
	{
	    private $m_lgepGatewayEntryPoint;

	    public function getAt($nIndex)
	    {
	        if ($nIndex < 0 ||
		     	$nIndex >= count($this->m_lgepGatewayEntryPoint))
		     {
		  	 	throw new Exception("Array index out of bounds");
		     }

	        return $this->m_lgepGatewayEntryPoint[$nIndex];
	    }

	    public function getCount()
	    {
	        return count($this->m_lgepGatewayEntryPoint);
	    }

	    public function add($GatewayEntrypointOrEntrypointURL, $nMetric)
	    {
	    	array_push($this->m_lgepGatewayEntryPoint, new GatewayEntryPoint($GatewayEntrypointOrEntrypointURL, $nMetric));
	    }

	    //constructor
	    public function __construct()
	    {
	       $this->m_lgepGatewayEntryPoint = array();
	    }
	}

	class PreviousTransactionResult
	{
		private $m_nStatusCode;
	    private $m_szMessage;

	    function getStatusCode()
	    {
	    	return $this->m_nStatusCode;
	    }

	    function getMessage()
	    {
	    	return $this->m_szMessage;
	    }

	    function __construct(\net\thepaymentgateway\common\NullableInt $nStatusCode = null,
	    					 $szMessage)
	    {
	    	$this->m_nStatusCode = $nStatusCode;
	    	$this->m_szMessage = $szMessage;
	    }
	}

	class GatewayOutput
	{
	    private $m_nStatusCode;
	    private $m_szMessage;
	    private $m_lszErrorMessages;

	    public function getStatusCode()
	    {
	        return $this->m_nStatusCode;
	    }
	    public function  getMessage()
	    {
	        return $this->m_szMessage;
	    }
	    public function  getErrorMessages()
	    {
	        return $this->m_lszErrorMessages;
	    }

	    //constructor
	    public function __construct($nStatusCode, $szMessage, \net\thepaymentgateway\common\StringList $lszErrorMessages = null)
	    {
		    $this->m_nStatusCode = $nStatusCode;
			$this->m_szMessage = $szMessage;
			$this->m_lszErrorMessages = $lszErrorMessages;
	    }
	}

	class PaymentMessageGatewayOutput extends GatewayOutput
	{
	    private $m_ptdPreviousTransactionResult;
	    private $m_boAuthorisationAttempted;

	    public function  getPreviousTransactionResult()
	    {
	        return $this->m_ptdPreviousTransactionResult;
	    }

	    public function  getAuthorisationAttempted()
	    {
	        return $this->m_boAuthorisationAttempted;
	    }
	    //constructor
	    public function __construct($nStatusCode, $szMessage, \net\thepaymentgateway\common\NullableBool $boAuthorisationAttempted = null, PreviousTransactionResult $ptdPreviousTransactionResult = null, \net\thepaymentgateway\common\StringList $lszErrorMessages = null)
	    {
			parent::__construct($nStatusCode, $szMessage, $lszErrorMessages);
			$this->m_boAuthorisationAttempted = $boAuthorisationAttempted;
			$this->m_ptdPreviousTransactionResult = $ptdPreviousTransactionResult;
		}
	}

	class CardDetailsTransactionResult extends PaymentMessageGatewayOutput
	{
	    public function __construct($nStatusCode, $szMessage, \net\thepaymentgateway\common\NullableBool $boAuthorisationAttempted = null, PreviousTransactionResult $ptdPreviousTransactionResult = null, \net\thepaymentgateway\common\StringList $lszErrorMessages = null)
	    {
			parent::__construct($nStatusCode, $szMessage, $boAuthorisationAttempted, $ptdPreviousTransactionResult, $lszErrorMessages);
		}
	}
	class CrossReferenceTransactionResult extends PaymentMessageGatewayOutput
	{
	    public function __construct($nStatusCode, $szMessage, \net\thepaymentgateway\common\NullableBool $boAuthorisationAttempted = null, PreviousTransactionResult $ptdPreviousTransactionResult = null, \net\thepaymentgateway\common\StringList $lszErrorMessages = null)
	    {
			parent::__construct($nStatusCode, $szMessage, $boAuthorisationAttempted, $ptdPreviousTransactionResult, $lszErrorMessages);
		}
	}
	class ThreeDSecureEnvironmentResult extends PaymentMessageGatewayOutput
	{
	    public function __construct($nStatusCode, $szMessage, \net\thepaymentgateway\common\NullableBool $boAuthorisationAttempted = null, PreviousTransactionResult $ptdPreviousTransactionResult = null, \net\thepaymentgateway\common\StringList $lszErrorMessages = null)
	    {
			parent::__construct($nStatusCode, $szMessage, $boAuthorisationAttempted, $ptdPreviousTransactionResult, $lszErrorMessages);
		}
	}
	class ThreeDSecureAuthenticationResult extends PaymentMessageGatewayOutput
	{
	    public function __construct($nStatusCode, $szMessage, \net\thepaymentgateway\common\NullableBool $boAuthorisationAttempted = null, PreviousTransactionResult $ptdPreviousTransactionResult = null, \net\thepaymentgateway\common\StringList $lszErrorMessages = null)
	    {
			parent::__construct($nStatusCode, $szMessage, $boAuthorisationAttempted, $ptdPreviousTransactionResult, $lszErrorMessages);
		}
	}
	class GetGatewayEntryPointsResult extends GatewayOutput
	{
	    public function __construct($nStatusCode, $szMessage, \net\thepaymentgateway\common\StringList $lszErrorMessages = null)
	    {
			parent::__construct($nStatusCode, $szMessage, $lszErrorMessages);
		}
	}
	class GetCardTypeResult extends GatewayOutput
	{
	    public function __construct($nStatusCode, $szMessage, \net\thepaymentgateway\common\StringList $lszErrorMessages = null)
	    {
			parent::__construct($nStatusCode, $szMessage, $lszErrorMessages);
		}
	}

	class ThreeDSecureOutputData
	{
		private $m_szPaREQ;
	   	private $m_szACSURL;
		private $m_szThreeDSecureVersion;
		private $m_szCREQ;
        private $m_szMethodURL;
        private $m_szMethodData;

	   	public function getPaREQ()
	   	{
			return $this->m_szPaREQ;
	   	}

	   	public function getACSURL()
	   	{
	       	return ($this->m_szACSURL);
	   	}

		public function getThreeDSecureVersion()
	   	{
	       	return ($this->m_szThreeDSecureVersion);
	   	}

		public function getCREQ()
	   	{
	       	return ($this->m_szCREQ);
	   	}

	   	public function getMethodURL()
	   	{
	       	return ($this->m_szMethodURL);
	   	}

	   	public function getMethodData()
	   	{
	       	return ($this->m_szMethodData);
	   	}

	   	//constructor
	   	public function __construct($szPaREQ, $szACSURL, $szThreeDSecureVersion, $szCREQ, $szMethodURL, $szMethodData)
	   	{
			$this->m_szPaREQ = $szPaREQ;
	       	$this->m_szACSURL = $szACSURL;
			$this->m_szThreeDSecureVersion = $szThreeDSecureVersion;
			$this->m_szCREQ = $szCREQ;
			$this->m_szMethodURL = $szMethodURL;
			$this->m_szMethodData = $szMethodData;
	   	}
	}

	class GetGatewayEntryPointsOutputData extends BaseOutputData
	{
	   	//constructor
	   	function __construct(GatewayEntryPointList $lgepGatewayEntryPoints = null)
	   	{
	      	parent::__construct($lgepGatewayEntryPoints);
	   	}
	}

	class TransactionOutputData extends BaseOutputData
	{
		private $m_szCrossReference;
		private $m_szAuthCode;
	    private $m_szAddressNumericCheckResult;
	    private $m_szPostCodeCheckResult;
	    private $m_szThreeDSecureAuthenticationCheckResult;
	    private $m_szCV2CheckResult;
	    private $m_ctdCardTypeData;
	    private $m_nAmountReceived;
	    private $m_tdsodThreeDSecureOutputData;
	    private $m_lgvCustomVariables;

	    public function getCrossReference()
	    {
	        return $this->m_szCrossReference;
	    }

	    public function getAuthCode()
	    {
	        return $this->m_szAuthCode;
	    }

	    public function getAddressNumericCheckResult()
	    {
	       	return $this->m_szAddressNumericCheckResult;
	    }

	    public function getPostCodeCheckResult()
	    {
			return $this->m_szPostCodeCheckResult;
	    }

	    public function getThreeDSecureAuthenticationCheckResult()
	    {
	        return $this->m_szThreeDSecureAuthenticationCheckResult;
	    }

	    public function getCV2CheckResult()
	    {
	    	return $this->m_szCV2CheckResult;
	    }

	    public function getCardTypeData()
	    {
	        return $this->m_ctdCardTypeData;
	    }

	    public function getAmountReceived()
	    {
	       	return $this->m_nAmountReceived;
	    }

	    public function getThreeDSecureOutputData()
	    {
	       	return $this->m_tdsodThreeDSecureOutputData;
	    }

	    public function getCustomVariables()
	    {
	       	return $this->m_lgvCustomVariables;
	    }

	 	//constructor
	    public function __construct($szCrossReference,
									$szAuthCode,
	    							$szAddressNumericCheckResult,
	    							$szPostCodeCheckResult,
	    							$szThreeDSecureAuthenticationCheckResult,
	    							$szCV2CheckResult,
	    							CardTypeData $ctdCardTypeData = null,
	    							\net\thepaymentgateway\common\NullableInt $nAmountReceived = null,
	    							ThreeDSecureOutputData $tdsodThreeDSecureOutputData = null,
	    							GenericVariableList $lgvCustomVariables = null,
	    							GatewayEntryPointList $lgepGatewayEntryPoints = null)
	    {
	     	//first calling the parent constructor
	        parent::__construct($lgepGatewayEntryPoints);

		   	$this->m_szCrossReference = $szCrossReference;
			$this->m_szAuthCode = $szAuthCode;
			$this->m_szAddressNumericCheckResult = $szAddressNumericCheckResult;
			$this->m_szPostCodeCheckResult = $szPostCodeCheckResult;
			$this->m_szThreeDSecureAuthenticationCheckResult = $szThreeDSecureAuthenticationCheckResult;
			$this->m_szCV2CheckResult = $szCV2CheckResult;
			$this->m_ctdCardTypeData = $ctdCardTypeData;
			$this->m_nAmountReceived = $nAmountReceived;
			$this->m_tdsodThreeDSecureOutputData = $tdsodThreeDSecureOutputData;
			$this->m_lgvCustomVariables = $lgvCustomVariables;
	    }
	}

	class GetCardTypeOutputData extends BaseOutputData
	{
		private $m_ctdCardTypeData;

	   	public function getCardTypeData()
	   	{
	   		return $this->m_ctdCardTypeData;
	   	}

	  	//constructor
	   	public function __construct(CardTypeData $ctdCardTypeData = null,
	   								GatewayEntryPointList $lgepGatewayEntryPoints = null)
	   	{
	      	parent::__construct($lgepGatewayEntryPoints);

	      	$this->m_ctdCardTypeData = $ctdCardTypeData;
	   	}
	}

	class BaseOutputData
	{
	   	private $m_lgepGatewayEntryPoints;

	   	public function getGatewayEntryPoints()
	   	{
	      	return $this->m_lgepGatewayEntryPoints;
	   	}

	   	//constructor
	   	public function __construct(GatewayEntryPointList $lgepGatewayEntryPoints = null)
	   	{
	      	$this->m_lgepGatewayEntryPoints = $lgepGatewayEntryPoints;
	   	}
	}


	/********************/
	/* Gateway messages */
	/********************/
	class GetGatewayEntryPoints extends GatewayTransaction
	{
	  	function processTransaction(GetGatewayEntryPointsResult &$ggeprGetGatewayEntryPointsResult = null, GetGatewayEntryPointsOutputData &$ggepGetGatewayEntryPointsOutputData = null)
	   	{
	      	$boTransactionSubmitted = false;
	      	$sSOAPClient;
	      	$lgepGatewayEntryPoints;

	      	$ggepGetGatewayEntryPointsOutputData = null;
	      	$goGatewayOutput = null;

	      	$sSOAPClient = new \net\thepaymentgateway\common\soap\SOAP("GetGatewayEntryPoints", GatewayTransaction::getSOAPNamespace());

	      	$boTransactionSubmitted = GatewayTransaction::processTransactionBase($sSOAPClient, "GetGatewayEntryPointsMessage", "GetGatewayEntryPointsResult", "GetGatewayEntryPointsOutputData", $goGatewayOutput, $lgepGatewayEntryPoints);

	      	if ($boTransactionSubmitted)
	      	{
				$ggeprGetGatewayEntryPointsResult = $goGatewayOutput;

	      		$ggepGetGatewayEntryPointsOutputData = new GetGatewayEntryPointsOutputData($lgepGatewayEntryPoints);
	      	}

	      	return $boTransactionSubmitted;
	   	}

	   	//constructor
	   	public function __construct(RequestGatewayEntryPointList $lrgepRequestGatewayEntryPoints = null,
	   								$nRetryAttempts = 1,
	   								\net\thepaymentgateway\common\NullableInt $nTimeout = null)
	 	{
	   		if ($nRetryAttempts == null &&
	   			$nTimeout == null)
	   		{
	   			GatewayTransaction::__construct($lrgepRequestGatewayEntryPoints, 1, null);
	   		}
	   		else
	   		{
	   			GatewayTransaction::__construct($lrgepRequestGatewayEntryPoints, $nRetryAttempts, $nTimeout);
	   		}
	   	}
	}


	class CardDetailsTransaction extends GatewayTransaction
	{
		private $m_tdTransactionDetails;
	    private $m_cdCardDetails;
	    private $m_cdCustomerDetails;

	    public function getTransactionDetails()
	    {
	    	return $this->m_tdTransactionDetails;
	   	}
	    public function getCardDetails()
	    {
	     	return $this->m_cdCardDetails;
	    }
	   	public function getCustomerDetails()
	    {
	    	return $this->m_cdCustomerDetails;
	    }

	   	public function processTransaction(CardDetailsTransactionResult &$cdtrCardDetailsTransactionResult = null, TransactionOutputData &$todTransactionOutputData = null)
	   	{
	     	$boTransactionSubmitted = false;
	        $sSOAPClient;
	        $lgepGatewayEntryPoints = null;
			$goGatewayOutput = null;

	      	$todTransactionOutputData = null;
	        $cdtrCardDetailsTransactionResult = null;

	        $sSOAPClient = new \net\thepaymentgateway\common\soap\SOAP("CardDetailsTransaction", parent::getSOAPNamespace());

	    	// transaction details
	       	if ($this->m_tdTransactionDetails != null)
	        {
	       		if ($this->m_tdTransactionDetails->getAmount() != null)
	          	{
	            	if ($this->m_tdTransactionDetails->getAmount()->getHasValue())
	                {
	                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails", "Amount", (string)$this->m_tdTransactionDetails->getAmount()->getValue());
	                }
	            }
	            if ($this->m_tdTransactionDetails->getCurrencyCode() != null)
	          	{
	            	if ($this->m_tdTransactionDetails->getCurrencyCode()->getHasValue())
	                {
	                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails", "CurrencyCode", (string)$this->m_tdTransactionDetails->getCurrencyCode()->getValue());
	                }
	            }
            	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getCaptureEnvironment()))
                {
                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails", "CaptureEnvironment", $this->m_tdTransactionDetails->getCaptureEnvironment());
               	}
	            if ($this->m_tdTransactionDetails->getContinuousAuthority() != null)
	          	{
	            	if ($this->m_tdTransactionDetails->getContinuousAuthority()->getHasValue())
	                {
	                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails", "ContinuousAuthority", (string)$this->m_tdTransactionDetails->getContinuousAuthority()->getValue());
	                }
	            }
	            if ($this->m_tdTransactionDetails->getMessageDetails() != null)
	            {
	            	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getMessageDetails()->getClientReference()))
	                {
	                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.MessageDetails", "ClientReference", $this->m_tdTransactionDetails->getMessageDetails()->getClientReference());
	               	}
	            	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getMessageDetails()->getTransactionType()))
	                {
	                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.MessageDetails", "TransactionType", $this->m_tdTransactionDetails->getMessageDetails()->getTransactionType());
	               	}
	            }
	            if ($this->m_tdTransactionDetails->getTransactionControl() != null)
	           	{
	             	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getTransactionControl()->getAuthCode()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.AuthCode", $this->m_tdTransactionDetails->getTransactionControl()->getAuthCode());
	                }
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getThreeDSecureOverridePolicy() != null)
	                {
    	                if ($this->m_tdTransactionDetails->getTransactionControl()->getThreeDSecureOverridePolicy()->getHasValue())
                        {
	                	    $sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.ThreeDSecureOverridePolicy", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getTransactionControl()->getThreeDSecureOverridePolicy()->getValue()));
                        }
	               	}
	               	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getTransactionControl()->getAVSOverridePolicy()))
	                {
	                	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.AVSOverridePolicy", $this->m_tdTransactionDetails->getTransactionControl()->getAVSOverridePolicy());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getTransactionControl()->getCV2OverridePolicy()))
	                {
	                	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.CV2OverridePolicy", ($this->m_tdTransactionDetails->getTransactionControl()->getCV2OverridePolicy()));
	                }
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getDuplicateDelay() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getTransactionControl()->getDuplicateDelay()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.DuplicateDelay", (string)$this->m_tdTransactionDetails->getTransactionControl()->getDuplicateDelay()->getValue());
	                    }
	                }
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoCardType() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoCardType()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.EchoCardType", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getTransactionControl()->getEchoCardType()->getValue()));
	                    }
	                }
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.EchoAVSCheckResult", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult()->getValue()));
	                  	}
	                }
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.EchoAVSCheckResult", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult()->getValue()));
	                    }
	                }
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoCV2CheckResult() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoCV2CheckResult()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.EchoCV2CheckResult", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getTransactionControl()->getEchoCV2CheckResult()->getValue()));
	                    }
	               	}
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAmountReceived() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAmountReceived()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.EchoAmountReceived", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getTransactionControl()->getEchoAmountReceived()->getValue()));
	                    }
	                }
	          	}
	          	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails() != null)
	            {
	            	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getAcceptHeaders()))
	                {
	                	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.AcceptHeaders", $this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getAcceptHeaders());
	                }
	                if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getDeviceCategory() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getDeviceCategory()->getHasValue())
	                    {
	                    	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails", "DeviceCategory", (string)$this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getDeviceCategory()->getValue());
	                    }
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getUserAgent()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.UserAgent", $this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getUserAgent());
	                }
					if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getLanguage()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.Language", $this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getLanguage());
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenHeight() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenHeight()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.ScreenHeight", (string)$this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenHeight()->getValue());
	                    }
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenWidth() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenWidth()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.ScreenWidth", (string)$this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenWidth()->getValue());
	                    }
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenColourDepth() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenColourDepth()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.ScreenColourDepth", (string)$this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenColourDepth()->getValue());
	                    }
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getTimeZone() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getTimeZone()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.TimeZone", (string)$this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getTimeZone()->getValue());
	                    }
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaEnabled() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaEnabled()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.JavaEnabled", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaEnabled()->getValue()));
	                    }
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaScriptEnabled() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaScriptEnabled()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.JavaScriptEnabled", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaScriptEnabled()->getValue()));
	                    }
	                }
	            }
				if ($this->m_tdTransactionDetails->getThreeDSecurePassthroughData() != null)
                {
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getEnrolmentStatus()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData", "EnrolmentStatus", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getEnrolmentStatus());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getAuthenticationStatus()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData", "AuthenticationStatus", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getAuthenticationStatus());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getECI()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData", "ECI", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getECI());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getAuthenticationValue()))
                    {
                        $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData.AuthenticationValue", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getAuthenticationValue());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getTransactionIdentifier()))
                    {
                        $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData.TransactionIdentifier", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getTransactionIdentifier());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getThreeDSecureVersion()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData", "ThreeDSecureVersion", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getThreeDSecureVersion());
                    }
                }
                if ($this->m_tdTransactionDetails->getThreeDSecureNotificationDetails() != null)
                {
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureNotificationDetails()->getFingerprintNotificationURL()))
                    {
                        $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureNotificationDetails.FingerprintNotificationURL", $this->m_tdTransactionDetails->getThreeDSecureNotificationDetails()->getFingerprintNotificationURL());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureNotificationDetails()->getChallengeNotificationURL()))
                    {
                        $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureNotificationDetails.ChallengeNotificationURL", $this->m_tdTransactionDetails->getThreeDSecureNotificationDetails()->getChallengeNotificationURL());
                    }
                }
                if ($this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption() != null)
                {
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionType()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecureAuthenticationExemption", "ExemptionType", $this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionType());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionReason()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecureAuthenticationExemption", "ExemptionReason", $this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionReason());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionIndicator()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecureAuthenticationExemption", "ExemptionIndicator", $this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionIndicator());
                    }
                }
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getOrderID()))
	           	{
	             	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.OrderID", $this->m_tdTransactionDetails->getOrderID());
	            }
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getOrderDescription()))
	            {
	                $sSOAPClient->addParam("PaymentMessage.TransactionDetails.OrderDescription", $this->m_tdTransactionDetails->getOrderDescription());
	            }
	        }
	        // card details
	        if ($this->m_cdCardDetails != null)
	        {
	        	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCardDetails->getCardName()))
	            {
	            	$sSOAPClient->addParam("PaymentMessage.CardDetails.CardName", $this->m_cdCardDetails->getCardName());
	            }
	        	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCardDetails->getCV2()))
	            {
	                $sSOAPClient->addParam("PaymentMessage.CardDetails.CV2", $this->m_cdCardDetails->getCV2());
	            }
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCardDetails->getCardNumber()))
	            {
	                $sSOAPClient->addParam("PaymentMessage.CardDetails.CardNumber", $this->m_cdCardDetails->getCardNumber());
	            }
	            if ($this->m_cdCardDetails->getExpiryDate() != null)
	            {
	                if ($this->m_cdCardDetails->getExpiryDate()->getMonth() != null)
	                {
	                	if ($this->m_cdCardDetails->getExpiryDate()->getMonth()->getHasValue())
	                    {
	                    	$sSOAPClient->addParamAttribute("PaymentMessage.CardDetails.ExpiryDate", "Month", (string)$this->m_cdCardDetails->getExpiryDate()->getMonth()->getValue());
	                    }
	                }
	                if ($this->m_cdCardDetails->getExpiryDate()->getYear() != null)
	                {
	                    if ($this->m_cdCardDetails->getExpiryDate()->getYear()->getHasValue())
	                    {
	                    	$sSOAPClient->addParamAttribute("PaymentMessage.CardDetails.ExpiryDate", "Year", (string)$this->m_cdCardDetails->getExpiryDate()->getYear()->getValue());
	                    }
	               	}
	            }
	            if ($this->m_cdCardDetails->getStartDate() != null)
	            {
	                if ($this->m_cdCardDetails->getStartDate()->getMonth() != null)
	                {
	                	if ($this->m_cdCardDetails->getStartDate()->getMonth()->getHasValue())
	                    {
	                    	$sSOAPClient->addParamAttribute("PaymentMessage.CardDetails.StartDate", "Month", (string)$this->m_cdCardDetails->getStartDate()->getMonth()->getValue());
	                    }
	                }
	                if ($this->m_cdCardDetails->getStartDate()->getYear() != null)
	                {
	                    if ($this->m_cdCardDetails->getStartDate()->getYear()->getHasValue())
	                    {
	                    	$sSOAPClient->addParamAttribute("PaymentMessage.CardDetails.StartDate", "Year", (string)$this->m_cdCardDetails->getStartDate()->getYear()->getValue());
	                    }
	                }
	            }
	        	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCardDetails->getIssueNumber()))
	            {
	               	$sSOAPClient->addParam("PaymentMessage.CardDetails.IssueNumber", $this->m_cdCardDetails->getIssueNumber());
	            }
	        }
	        // customer details
	        if ($this->m_cdCustomerDetails != null)
	        {
	        	if ($this->m_cdCustomerDetails->getBillingAddress() != null)
	            {
	             	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getAddress1()))
	                {
	                	$sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.Address1", $this->m_cdCustomerDetails->getBillingAddress()->getAddress1());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getAddress2()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.Address2", $this->m_cdCustomerDetails->getBillingAddress()->getAddress2());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getAddress3()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.Address3", $this->m_cdCustomerDetails->getBillingAddress()->getAddress3());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getAddress4()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.Address4", $this->m_cdCustomerDetails->getBillingAddress()->getAddress4());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getCity()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.City", $this->m_cdCustomerDetails->getBillingAddress()->getCity());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getState()))
	                {
	                  	$sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.State", $this->m_cdCustomerDetails->getBillingAddress()->getState());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getPostCode()))
	                {
	                   	$sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.PostCode", $this->m_cdCustomerDetails->getBillingAddress()->getPostCode());
	                }
	                if ($this->m_cdCustomerDetails->getBillingAddress()->getCountryCode() != null)
	                {
	                  	if ($this->m_cdCustomerDetails->getBillingAddress()->getCountryCode()->getHasValue())
	                    {
	                   		$sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.CountryCode", (string)$this->m_cdCustomerDetails->getBillingAddress()->getCountryCode()->getValue());
	                    }
	                }
	      		}
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getEmailAddress()))
	            {
	            	$sSOAPClient->addParam("PaymentMessage.CustomerDetails.EmailAddress", $this->m_cdCustomerDetails->getEmailAddress());
	            }
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getPhoneNumber()))
	            {
	              	$sSOAPClient->addParam("PaymentMessage.CustomerDetails.PhoneNumber", $this->m_cdCustomerDetails->getPhoneNumber());
	            }
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getCustomerIPAddress()))
	            {
	            	$sSOAPClient->addParam("PaymentMessage.CustomerDetails.CustomerIPAddress", $this->m_cdCustomerDetails->getCustomerIPAddress());
	            }
	       	}

	       	$boTransactionSubmitted = GatewayTransaction::processTransactionBase($sSOAPClient, "PaymentMessage", "CardDetailsTransactionResult", "TransactionOutputData", $goGatewayOutput, $lgepGatewayEntryPoints);

			if ($boTransactionSubmitted)
			{
				$cdtrCardDetailsTransactionResult = SharedFunctions::getPaymentMessageGatewayOutput($sSOAPClient->getXmlTag(), "CardDetailsTransactionResult", $goGatewayOutput);

				$todTransactionOutputData = SharedFunctions::getTransactionOutputData($sSOAPClient->getXmlTag(), $lgepGatewayEntryPoints);
			}

			return ($boTransactionSubmitted);
		}

		public function __construct(RequestGatewayEntryPointList $lrgepRequestGatewayEntryPoints = null,
	     							$nRetryAttempts = 1,
	     							\net\thepaymentgateway\common\NullableInt $nTimeout = null)
	  	{
	    	parent::__construct($lrgepRequestGatewayEntryPoints, $nRetryAttempts, $nTimeout);

	        $this->m_tdTransactionDetails = new TransactionDetails();
	        $this->m_cdCardDetails = new CardDetails();
	        $this->m_cdCustomerDetails = new CustomerDetails();
	    }

	}
	class CrossReferenceTransaction extends GatewayTransaction
	{
		private $m_tdTransactionDetails;
	    private $m_ocdOverrideCardDetails;
	    private $m_cdCustomerDetails;

	    public function getTransactionDetails()
	    {
			return $this->m_tdTransactionDetails;
	    }
	    public function getOverrideCardDetails()
	    {
	    	return $this->m_ocdOverrideCardDetails;
	    }
	    public function getCustomerDetails()
	    {
	    	return $this->m_cdCustomerDetails;
	    }

	    public function processTransaction(CrossReferenceTransactionResult &$crtrCrossReferenceTransactionResult = null, TransactionOutputData &$todTransactionOutputData = null)
	    {
	    	$boTransactionSubmitted = false;
	        $sSOAPClient;
	        $lgepGatewayEntryPoints = null;

	        $todTransactionOutputData = null;
	        $goGatewayOutput = null;

	        $sSOAPClient = new \net\thepaymentgateway\common\soap\SOAP("CrossReferenceTransaction", GatewayTransaction::getSOAPNamespace());
	      	// transaction details
	        if ($this->m_tdTransactionDetails != null)
	        {
	        	if ($this->m_tdTransactionDetails->getAmount() != null)
	          	{
	             	if ($this->m_tdTransactionDetails->getAmount()->getHasValue())
	                {
	               		$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails", "Amount", (string)$this->m_tdTransactionDetails->getAmount()->getValue());
	                }
	            }
	            if ($this->m_tdTransactionDetails->getCurrencyCode() != null)
	            {
	                if ($this->m_tdTransactionDetails->getCurrencyCode()->getHasValue())
	                {
	                    $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails", "CurrencyCode", (string)$this->m_tdTransactionDetails->getCurrencyCode()->getValue());
	                }
	            }
            	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getCaptureEnvironment()))
                {
                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails", "CaptureEnvironment", $this->m_tdTransactionDetails->getCaptureEnvironment());
               	}
	            if ($this->m_tdTransactionDetails->getContinuousAuthority() != null)
	          	{
	            	if ($this->m_tdTransactionDetails->getContinuousAuthority()->getHasValue())
	                {
	                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails", "ContinuousAuthority", (string)$this->m_tdTransactionDetails->getContinuousAuthority()->getValue());
	                }
	            }
	            if ($this->m_tdTransactionDetails->getMessageDetails() != null)
	            {
	            	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getMessageDetails()->getClientReference()))
	                {
	                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.MessageDetails", "ClientReference", $this->m_tdTransactionDetails->getMessageDetails()->getClientReference());
	               	}
	            	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getMessageDetails()->getPreviousClientReference()))
	                {
	                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.MessageDetails", "PreviousClientReference", $this->m_tdTransactionDetails->getMessageDetails()->getPreviousClientReference());
	               	}
	            	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getMessageDetails()->getTransactionType()))
	                {
	                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.MessageDetails", "TransactionType", $this->m_tdTransactionDetails->getMessageDetails()->getTransactionType());
	               	}
	                if ($this->m_tdTransactionDetails->getMessageDetails()->getNewTransaction() != null)
	                {
	                    if ($this->m_tdTransactionDetails->getMessageDetails()->getNewTransaction()->getHasValue())
	                    {
	                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.MessageDetails", "NewTransaction", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getMessageDetails()->getNewTransaction()->getValue()));
	                    }
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getMessageDetails()->getCrossReference()))
	                {
	                	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.MessageDetails", "CrossReference", $this->m_tdTransactionDetails->getMessageDetails()->getCrossReference());
	                }
	           	}
	           	if ($this->m_tdTransactionDetails->getTransactionControl() != null)
	           	{
	             	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getTransactionControl()->getAuthCode()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.AuthCode", $this->m_tdTransactionDetails->getTransactionControl()->getAuthCode());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getTransactionControl()->getAVSOverridePolicy()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.AVSOverridePolicy", $this->m_tdTransactionDetails->getTransactionControl()->getAVSOverridePolicy());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getTransactionControl()->getCV2OverridePolicy()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.CV2OverridePolicy", $this->m_tdTransactionDetails->getTransactionControl()->getCV2OverridePolicy());
	                }
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getDuplicateDelay() != null)
	                {
	                    if ($this->m_tdTransactionDetails->getTransactionControl()->getDuplicateDelay()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.DuplicateDelay", (string)($this->m_tdTransactionDetails->getTransactionControl()->getDuplicateDelay()->getValue()));
	                    }
	                }
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoCardType() != null)
	                {
	                    if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoCardType()->getHasValue())
	                    {
	                   		$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.EchoCardType", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getTransactionControl()->getEchoCardType()->getValue()));
	                    }
	                }
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult() != null)
	                {
	                  	if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.EchoAVSCheckResult", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult()->getValue()));
	                    }
	                }
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult() != null)
	                {
	                  	if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.EchoAVSCheckResult", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getTransactionControl()->getEchoAVSCheckResult()->getValue()));
	                    }
	                }
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoCV2CheckResult() != null)
	                {
	                    if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoCV2CheckResult()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.EchoCV2CheckResult", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getTransactionControl()->getEchoCV2CheckResult()->getValue()));
	                    }
	              	}
	                if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAmountReceived() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getTransactionControl()->getEchoAmountReceived()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.TransactionControl.EchoAmountReceived", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getTransactionControl()->getEchoAmountReceived()->getValue()));
		               	}
	                }
	         	}
	            if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails() != null)
	            {
	            	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getAcceptHeaders()))
	                {
	                	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.AcceptHeaders", $this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getAcceptHeaders());
	                }
	                if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getDeviceCategory() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getDeviceCategory()->getHasValue())
	                    {
	                    	$sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails", "DeviceCategory", (string)$this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getDeviceCategory()->getValue());
	                    }
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getUserAgent()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.UserAgent", $this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getUserAgent());
	                }
					if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getLanguage()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.Language", $this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getLanguage());
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenHeight() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenHeight()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.ScreenHeight", (string)$this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenHeight()->getValue());
	                    }
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenWidth() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenWidth()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.ScreenWidth", (string)$this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenWidth()->getValue());
	                    }
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenColourDepth() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenColourDepth()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.ScreenColourDepth", (string)$this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getScreenColourDepth()->getValue());
	                    }
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getTimeZone() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getTimeZone()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.TimeZone", (string)$this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getTimeZone()->getValue());
	                    }
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaEnabled() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaEnabled()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.JavaEnabled", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaEnabled()->getValue()));
	                    }
	                }
					if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaScriptEnabled() != null)
	                {
	                	if ($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaScriptEnabled()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureBrowserDetails.JavaScriptEnabled", \net\thepaymentgateway\common\SharedFunctions::boolToString($this->m_tdTransactionDetails->getThreeDSecureBrowserDetails()->getJavaScriptEnabled()->getValue()));
	                    }
	                }
	            }
				if ($this->m_tdTransactionDetails->getThreeDSecurePassthroughData() != null)
                {
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getEnrolmentStatus()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData", "EnrolmentStatus", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getEnrolmentStatus());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getAuthenticationStatus()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData", "AuthenticationStatus", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getAuthenticationStatus());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getECI()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData", "ECI", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getECI());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getAuthenticationValue()))
                    {
                        $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData.AuthenticationValue", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getAuthenticationValue());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getTransactionIdentifier()))
                    {
                        $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData.TransactionIdentifier", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getTransactionIdentifier());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getThreeDSecureVersion()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecurePassthroughData", "ThreeDSecureVersion", $this->m_tdTransactionDetails->getThreeDSecurePassthroughData()->getThreeDSecureVersion());
                    }
                }
                if ($this->m_tdTransactionDetails->getThreeDSecureNotificationDetails() != null)
                {
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureNotificationDetails()->getFingerprintNotificationURL()))
                    {
                        $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureNotificationDetails.FingerprintNotificationURL", $this->m_tdTransactionDetails->getThreeDSecureNotificationDetails()->getFingerprintNotificationURL());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureNotificationDetails()->getChallengeNotificationURL()))
                    {
                        $sSOAPClient->addParam("PaymentMessage.TransactionDetails.ThreeDSecureNotificationDetails.ChallengeNotificationURL", $this->m_tdTransactionDetails->getThreeDSecureNotificationDetails()->getChallengeNotificationURL());
                    }
                }
                if ($this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption() != null)
                {
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionType()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecureAuthenticationExemption", "ExemptionType", $this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionType());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionReason()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecureAuthenticationExemption", "ExemptionReason", $this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionReason());
                    }
                    if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionIndicator()))
                    {
                        $sSOAPClient->addParamAttribute("PaymentMessage.TransactionDetails.ThreeDSecureAuthenticationExemption", "ExemptionIndicator", $this->m_tdTransactionDetails->getThreeDSecureAuthenticationExemption()->getExemptionIndicator());
                    }
                }
				if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getOrderID()))
	            {
	           		$sSOAPClient->addParam("PaymentMessage.TransactionDetails.OrderID", $this->m_tdTransactionDetails->getOrderID());
	            }
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdTransactionDetails->getOrderDescription()))
	            {
	                $sSOAPClient->addParam("PaymentMessage.TransactionDetails.OrderDescription", $this->m_tdTransactionDetails->getOrderDescription());
	            }
	        }
	        // card details
	       	if ($this->m_ocdOverrideCardDetails != null)
	        {
	        	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_ocdOverrideCardDetails->getCardName()))
	            {
	            	$sSOAPClient->addParam("PaymentMessage.OverrideCardDetails.CardName", $this->m_ocdOverrideCardDetails->getCardName());
	            }
	        	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_ocdOverrideCardDetails->getCV2()))
	            {
	                $sSOAPClient->addParam("PaymentMessage.OverrideCardDetails.CV2", $this->m_ocdOverrideCardDetails->getCV2());
	            }
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_ocdOverrideCardDetails->getCardNumber()))
	            {
	                $sSOAPClient->addParam("PaymentMessage.OverrideCardDetails.CardNumber", $this->m_ocdOverrideCardDetails->getCardNumber());
	            }
	            if ($this->m_ocdOverrideCardDetails->getExpiryDate() != null)
	            {
	                if ($this->m_ocdOverrideCardDetails->getExpiryDate()->getMonth() != null)
	                {
	                	if ($this->m_ocdOverrideCardDetails->getExpiryDate()->getMonth()->getHasValue())
	                    {
	                    	$sSOAPClient->addParamAttribute("PaymentMessage.OverrideCardDetails.ExpiryDate", "Month", (string)$this->m_ocdOverrideCardDetails->getExpiryDate()->getMonth()->getValue());
	                    }
	                }
	                if ($this->m_ocdOverrideCardDetails->getExpiryDate()->getYear() != null)
	                {
	                    if ($this->m_ocdOverrideCardDetails->getExpiryDate()->getYear()->getHasValue())
	                    {
	                    	$sSOAPClient->addParamAttribute("PaymentMessage.OverrideCardDetails.ExpiryDate", "Year", (string)$this->m_ocdOverrideCardDetails->getExpiryDate()->getYear()->getValue());
	                    }
	                }
	            }
	            if ($this->m_ocdOverrideCardDetails->getStartDate() != null)
	            {
	              	if ($this->m_ocdOverrideCardDetails->getStartDate()->getMonth() != null)
	                {
	                	if ($this->m_ocdOverrideCardDetails->getStartDate()->getMonth()->getHasValue())
	                    {
	                    	$sSOAPClient->addParamAttribute("PaymentMessage.OverrideCardDetails.StartDate", "Month", (string)$this->m_ocdOverrideCardDetails->getStartDate()->getMonth()->getValue());
	                    }
	                }
	                if ($this->m_ocdOverrideCardDetails->getStartDate()->getYear() != null)
	                {
	                   	if ($this->m_ocdOverrideCardDetails->getStartDate()->getYear()->getHasValue())
	                    {
	                    	$sSOAPClient->addParamAttribute("PaymentMessage.OverrideCardDetails.StartDate", "Year", (string)$this->m_ocdOverrideCardDetails->getStartDate()->getYear()->getValue());
	                    }
	                }
	            }
	        	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_ocdOverrideCardDetails->getIssueNumber()))
	            {
	               	$sSOAPClient->addParam("PaymentMessage.CardDetails.IssueNumber", $this->m_ocdOverrideCardDetails->getIssueNumber());
	            }
	        }
	        // customer details
			if ($this->m_cdCustomerDetails != null)
	        {
	        	if ($this->m_cdCustomerDetails->getBillingAddress() != null)
	            {
	             	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getAddress1()))
	                {
	                	$sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.Address1", $this->m_cdCustomerDetails->getBillingAddress()->getAddress1());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getAddress2()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.Address2", $this->m_cdCustomerDetails->getBillingAddress()->getAddress2());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getAddress3()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.Address3", $this->m_cdCustomerDetails->getBillingAddress()->getAddress3());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getAddress4()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.Address4", $this->m_cdCustomerDetails->getBillingAddress()->getAddress4());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getCity()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.City", $this->m_cdCustomerDetails->getBillingAddress()->getCity());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getState()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.State", $this->m_cdCustomerDetails->getBillingAddress()->getState());
	                }
	                if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getBillingAddress()->getPostCode()))
	                {
	                    $sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.PostCode", (string)$this->m_cdCustomerDetails->getBillingAddress()->getPostCode());
	                }
	                if ($this->m_cdCustomerDetails->getBillingAddress()->getCountryCode() != null)
	                {
	                    if ($this->m_cdCustomerDetails->getBillingAddress()->getCountryCode()->getHasValue())
	                    {
	                    	$sSOAPClient->addParam("PaymentMessage.CustomerDetails.BillingAddress.CountryCode", (string)$this->m_cdCustomerDetails->getBillingAddress()->getCountryCode()->getValue());
	                    }
	                }
	         	}
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getEmailAddress()))
	            {
	            	$sSOAPClient->addParam("PaymentMessage.CustomerDetails.EmailAddress", $this->m_cdCustomerDetails->getEmailAddress());
	            }
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getPhoneNumber()))
	            {
	                $sSOAPClient->addParam("PaymentMessage.CustomerDetails.PhoneNumber", $this->m_cdCustomerDetails->getPhoneNumber());
	            }
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_cdCustomerDetails->getCustomerIPAddress()))
	            {
	                $sSOAPClient->addParam("PaymentMessage.CustomerDetails.CustomerIPAddress", $this->m_cdCustomerDetails->getCustomerIPAddress());
	            }
	        }

	        $boTransactionSubmitted = GatewayTransaction::processTransactionBase($sSOAPClient, "PaymentMessage", "CrossReferenceTransactionResult", "TransactionOutputData", $goGatewayOutput, $lgepGatewayEntryPoints);

	       	if ($boTransactionSubmitted)
	        {
				$crtrCrossReferenceTransactionResult = SharedFunctions::getPaymentMessageGatewayOutput($sSOAPClient->getXmlTag(), "CrossReferenceTransactionResult", $goGatewayOutput);

	        	$todTransactionOutputData = SharedFunctions::getTransactionOutputData($sSOAPClient->getXmlTag(), $lgepGatewayEntryPoints);
	        }

	        return $boTransactionSubmitted;
	    }

	    //constructor
	    public function __construct(RequestGatewayEntryPointList $lrgepRequestGatewayEntryPoints = null,
	    							$nRetryAttempts = 1,
	    							\net\thepaymentgateway\common\NullableInt $nTimeout = null)
	    {
	    	GatewayTransaction::__construct($lrgepRequestGatewayEntryPoints, $nRetryAttempts, $nTimeout);

		    $this->m_tdTransactionDetails = new TransactionDetails();
	      	$this->m_ocdOverrideCardDetails = new OverrideCardDetails();
	       	$this->m_cdCustomerDetails = new CustomerDetails();
	    }
	}

	class ThreeDSecureEnvironment extends GatewayTransaction
	{
		private $m_tdsedThreeDSecureEnvironmentData;

		public function getThreeDSecureEnvironmentData()
		{
			return $this->m_tdsedThreeDSecureEnvironmentData;
		}

		public function processTransaction(ThreeDSecureEnvironmentResult &$tdserThreeDSecureEnvironmentResult = null, TransactionOutputData &$todTransactionOutputData = null)
		{
			$boTransactionSubmitted = false;
	        $sSOAPClient;
	        $lgepGatewayEntryPoints = null;

	        $todTransactionOutputData = null;
	        $goGatewayOutput = null;

	       	$sSOAPClient = new \net\thepaymentgateway\common\soap\SOAP("ThreeDSecureEnvironment", GatewayTransaction::getSOAPNamespace());
	       	if ($this->m_tdsedThreeDSecureEnvironmentData != null)
	        {
	        	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdsedThreeDSecureEnvironmentData->getCrossReference()))
	            {
	                $sSOAPClient->addParamAttribute("ThreeDSecureMessage.ThreeDSecureEnvironmentData", "CrossReference", $this->m_tdsedThreeDSecureEnvironmentData->getCrossReference());
	            }
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdsedThreeDSecureEnvironmentData->getMethodData()))
	            {
	            	$sSOAPClient->addParam("ThreeDSecureMessage.ThreeDSecureEnvironmentData.MethodData", $this->m_tdsedThreeDSecureEnvironmentData->getMethodData());
	            }
	        }

	        $boTransactionSubmitted = GatewayTransaction::processTransactionBase($sSOAPClient, "ThreeDSecureMessage", "ThreeDSecureEnvironmentResult", "TransactionOutputData", $goGatewayOutput, $lgepGatewayEntryPoints);

	        if ($boTransactionSubmitted)
	      	{
				$tdserThreeDSecureEnvironmentResult = SharedFunctions::getPaymentMessageGatewayOutput($sSOAPClient->getXmlTag(), "ThreeDSecureEnvironmentResult", $goGatewayOutput);

	        	$todTransactionOutputData = SharedFunctions::getTransactionOutputData($sSOAPClient->getXmlTag(), $lgepGatewayEntryPoints);
	        }

	        return $boTransactionSubmitted;
		}

		//constructor
		public function __construct(RequestGatewayEntryPointList $lrgepRequestGatewayEntryPoints = null,
									$nRetryAttempts = 1,
									\net\thepaymentgateway\common\NullableInt $nTimeout = null)
	 	{
	    	GatewayTransaction::__construct($lrgepRequestGatewayEntryPoints, $nRetryAttempts, $nTimeout);

	    	$this->m_tdsedThreeDSecureEnvironmentData = new ThreeDSecureEnvironmentData();
	    }
	}

	class ThreeDSecureAuthentication extends GatewayTransaction
	{
		private $m_tdsidThreeDSecureInputData;

		public function getThreeDSecureInputData()
		{
			return $this->m_tdsidThreeDSecureInputData;
		}

		public function processTransaction(ThreeDSecureAuthenticationResult &$tdsarThreeDSecureAuthenticationResult = null, TransactionOutputData &$todTransactionOutputData = null)
		{
			$boTransactionSubmitted = false;
	        $sSOAPClient;
	        $lgepGatewayEntryPoints = null;

	        $todTransactionOutputData = null;
	        $goGatewayOutput = null;

	       	$sSOAPClient = new \net\thepaymentgateway\common\soap\SOAP("ThreeDSecureAuthentication", GatewayTransaction::getSOAPNamespace());
	       	if ($this->m_tdsidThreeDSecureInputData != null)
	        {
	        	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdsidThreeDSecureInputData->getCrossReference()))
	            {
	                $sSOAPClient->addParamAttribute("ThreeDSecureMessage.ThreeDSecureInputData", "CrossReference", $this->m_tdsidThreeDSecureInputData->getCrossReference());
	            }
	            if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdsidThreeDSecureInputData->getMethodData()))
	            {
	            	$sSOAPClient->addParam("ThreeDSecureMessage.ThreeDSecureInputData.MethodData", $this->m_tdsidThreeDSecureInputData->getMethodData());
	            }
				if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_tdsidThreeDSecureInputData->getCRES()))
	            {
	            	$sSOAPClient->addParam("ThreeDSecureMessage.ThreeDSecureInputData.CRES", $this->m_tdsidThreeDSecureInputData->getCRES());
	            }
	        }

	        $boTransactionSubmitted = GatewayTransaction::processTransactionBase($sSOAPClient, "ThreeDSecureMessage", "ThreeDSecureAuthenticationResult", "TransactionOutputData", $goGatewayOutput, $lgepGatewayEntryPoints);

	        if ($boTransactionSubmitted)
	      	{
				$tdsarThreeDSecureAuthenticationResult = SharedFunctions::getPaymentMessageGatewayOutput($sSOAPClient->getXmlTag(), "ThreeDSecureAuthenticationResult", $goGatewayOutput);

	        	$todTransactionOutputData = SharedFunctions::getTransactionOutputData($sSOAPClient->getXmlTag(), $lgepGatewayEntryPoints);
	        }

	        return $boTransactionSubmitted;
		}

		//constructor
		public function __construct(RequestGatewayEntryPointList $lrgepRequestGatewayEntryPoints = null,
									$nRetryAttempts = 1,
									\net\thepaymentgateway\common\NullableInt $nTimeout = null)
	 	{
	    	GatewayTransaction::__construct($lrgepRequestGatewayEntryPoints, $nRetryAttempts, $nTimeout);

	    	$this->m_tdsidThreeDSecureInputData = new ThreeDSecureInputData();
	    }
	}

	class GetCardType extends GatewayTransaction
	{
		private $m_szCardNumber;

		public function getCardNumber()
		{
			return $this->m_szCardNumber;
		}
		public function setCardNumber($cardNumber)
		{
			$this->m_szCardNumber = $cardNumber;
		}

		public function processTransaction(GetCardTypeResult &$gctrGetCardTypeResult = null, GetCardTypeOutputData &$gctodGetCardTypeOutputData = null)
		{
			$boTransactionSubmitted = false;
	        $sSOAPClient;
	       	$lgepGatewayEntryPoints = null;
	        $ctdCardTypeData = null;

	       	$gctodGetCardTypeOutputData = null;
	        $goGatewayOutput = null;

	      	$sSOAPClient = new \net\thepaymentgateway\common\soap\SOAP("GetCardType", GatewayTransaction::getSOAPNamespace());
	      	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_szCardNumber))
	       	{
	        	$sSOAPClient->addParam("GetCardTypeMessage.CardNumber", $this->m_szCardNumber);
	        }

	        $boTransactionSubmitted = GatewayTransaction::processTransactionBase($sSOAPClient, "GetCardTypeMessage", "GetCardTypeResult", "GetCardTypeOutputData", $goGatewayOutput, $lgepGatewayEntryPoints);

	        if ($boTransactionSubmitted)
	        {
				$gctrGetCardTypeResult = $goGatewayOutput;

	        	$ctdCardTypeData = SharedFunctions::getCardTypeData($sSOAPClient->getXmlTag(), "GetCardTypeOutputData.CardTypeData");

	            $gctodGetCardTypeOutputData = new GetCardTypeOutputData($ctdCardTypeData, $lgepGatewayEntryPoints);
			}
	        return $boTransactionSubmitted;
		}

		//constructor
		public function __construct(RequestGatewayEntryPointList $lrgepRequestGatewayEntryPoints = null,
									$nRetryAttempts = 1,
	                           		\net\thepaymentgateway\common\NullableInt $nTimeout = null)
	  	{
	    	GatewayTransaction::__construct($lrgepRequestGatewayEntryPoints, $nRetryAttempts, $nTimeout);

	    	$this->m_szCardNumber = "";
	    }
	}

	abstract class GatewayTransaction
	{
	    private $m_maMerchantAuthentication;
	    private $m_lrgepRequestGatewayEntryPoints;
	    private $m_nRetryAttempts;
	    private $m_nTimeout;
	    private $m_szSOAPNamespace = "https://www.thepaymentgateway.net/";
	    private $m_szLastRequest;
		private $m_szLastResponse;
		private $m_eLastException;
		private $m_szEntryPointUsed;

	   	public function getMerchantAuthentication()
	   	{
	      	return $this->m_maMerchantAuthentication;
	   	}
	   	public function getRequestGatewayEntryPoints()
	   	{
	    	return $this->m_lrgepRequestGatewayEntryPoints;
	   	}
	   	public function getRetryAttempts()
	   	{
	      	return $this->m_nRetryAttempts;
	   	}
	   	public function getTimeout()
	   	{
	      	return $this->m_nTimeout;
	   	}
	   	public function getSOAPNamespace()
	   	{
	      	return $this->m_szSOAPNamespace;
	   	}
	   	public function setSOAPNamespace($value)
	   	{
	      	$this->m_szSOAPNamespace = $value;
	   	}
	   	public function getLastRequest()
	   	{
	   		return $this->m_szLastRequest;
	   	}
	   	public function getLastResponse()
	   	{
	   		return $this->m_szLastResponse;
	   	}
	   	public function getLastException()
	   	{
	   		return $this->m_eLastException;
	   	}
	   	public function getEntryPointUsed()
	   	{
	   		return $this->m_szEntryPointUsed;
	   	}
	   	public static function compare($x, $y)
	   	{
	      	$rgepFirst = null;
	      	$rgepSecond = null;

	      	$rgepFirst = $x;
	      	$rgepSecond = $y;

	      	return (GatewayTransaction::compareGatewayEntryPoints($rgepFirst, $rgepSecond));
	   	}
	   	private static function compareGatewayEntryPoints(RequestGatewayEntryPoint $rgepFirst, RequestGatewayEntryPoint $rgepSecond)
	   	{
			$nReturnValue = 0;
	      	// returns >0 if rgepFirst greater than rgepSecond
	      	// returns 0 if they are equal
	      	// returns <0 if rgepFirst less than rgepSecond

	      	// both null, then they are the same
	      	if ($rgepFirst == null &&
	          	$rgepSecond == null)
	   		{
	        	$nReturnValue = 0;
	        }
	      	// just first null? then second is greater
	      	elseif ($rgepFirst == null &&
		    		$rgepSecond != null)
	      	{
	        	$nReturnValue = 1;
	        }
	      	// just second null? then first is greater
	      	elseif ($rgepFirst != null  && $rgepSecond == null)
	      	{
	        	$nReturnValue = -1;
	        }
	      	// can now assume that first & second both have a value
	      	elseif ($rgepFirst->getMetric() == $rgepSecond->getMetric())
	        {
	        	$nReturnValue = 0;
	        }
	      	elseif ($rgepFirst->getMetric() < $rgepSecond->getMetric())
	        {
	        	$nReturnValue = -1;
	        }
	      	elseif ($rgepFirst->getMetric() > $rgepSecond->getMetric())
		    {
				$nReturnValue = 1;
	  	    }

	      	return $nReturnValue;
	   	}
	   	protected function processTransactionBase(\net\thepaymentgateway\common\soap\SOAP $sSOAPClient, $szMessageXMLPath, $szGatewayOutputXMLPath, $szTransactionMessageXMLPath, GatewayOutput &$goGatewayOutput = null, GatewayEntryPointList &$lgepGatewayEntryPoints = null)
	   	{
			$boTransactionSubmitted = false;
		    $nOverallRetryCount = 0;
		    $nOverallGatewayEntryPointCount = 0;
		    $nGatewayEntryPointCount = 0;
		    $nErrorMessageCount = 0;
		    $rgepCurrentGatewayEntryPoint;
		    $nStatusCode;
		    $szMessage = null;
		    $lszErrorMessages;
		    $szString;
		    $sbXMLString;
		    $szXMLFormatString;
		    $nCount = 0;
		    $szEntryPointURL;
		    $nMetric;
		    $gepGatewayEntryPoint = null;

	      	$lgepGatewayEntryPoints = null;
	      	$goGatewayOutput = null;

			$this->m_szEntryPointUsed = null;

	      	if ($sSOAPClient == null)
	      	{
	        	return false;
	      	}

	       	// populate the merchant details
	       	if ($this->m_maMerchantAuthentication != null)
	       	{
	        	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_maMerchantAuthentication->getMerchantID()))
	          	{
	            	$sSOAPClient->addParamAttribute($szMessageXMLPath. ".MerchantAuthentication", "MerchantID", $this->m_maMerchantAuthentication->getMerchantID());
	          	}
	          	if (!\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($this->m_maMerchantAuthentication->getPassword()))
	          	{
	             	$sSOAPClient->addParamAttribute($szMessageXMLPath. ".MerchantAuthentication", "Password", $this->m_maMerchantAuthentication->getPassword());
	          	}
	       	}

	      	// first need to sort the gateway entry points into the correct usage order
	       	$number = $this->m_lrgepRequestGatewayEntryPoints->sort("\\net\\thepaymentgateway\\paymentsystem\\GatewayTransaction","Compare");

	       	// loop over the overall number of transaction attempts
	       	while (!$boTransactionSubmitted &&
	       			$nOverallRetryCount < $this->m_nRetryAttempts)
	       	{
	       		$nOverallGatewayEntryPointCount = 0;

	       		// loop over the number of gateway entry points in the list
	            while (!$boTransactionSubmitted &&
	                 	$nOverallGatewayEntryPointCount < $this->m_lrgepRequestGatewayEntryPoints->getCount())
	          	{
					$rgepCurrentGatewayEntryPoint = $this->m_lrgepRequestGatewayEntryPoints->getAt($nOverallGatewayEntryPointCount);

					// ignore if the metric is "-1" this indicates that the entry point is offline
	              	if ($rgepCurrentGatewayEntryPoint->getMetric() >= 0)
	                {
	              		$nGatewayEntryPointCount = 0;
	                 	$sSOAPClient->setURL($rgepCurrentGatewayEntryPoint->getEntryPointURL());

	                    // loop over the number of times to try this specific entry point
	                    while (!$boTransactionSubmitted &&
	                          	$nGatewayEntryPointCount < $rgepCurrentGatewayEntryPoint->getRetryAttempts())
	                  	{
	                    	if ($sSOAPClient->sendRequest())
	                        {
                                if ($sSOAPClient->getXmlTag()->getIntegerValue($szGatewayOutputXMLPath.".StatusCode", $nStatusCode))
                                {
	    							// a status code of 50 means that this entry point is not to be used
		    						if ($nStatusCode != 50)
			    					{
				    					$this->m_szEntryPointUsed = $rgepCurrentGatewayEntryPoint->getEntryPointURL();

		                            	// the transaction was submitted
		                            	$boTransactionSubmitted = true;

							    		$sSOAPClient->getXmlTag()->getStringValue($szGatewayOutputXMLPath.".Message", $szMessage);

                                        $nErrorMessageCount = 0;
                                        $lszErrorMessages = new \net\thepaymentgateway\common\StringList();
                                        $szXmlFormatString1 = $szGatewayOutputXMLPath.".ErrorMessages.MessageDetail[";
                                        $szXmlFormatString2 = "].Detail";

                                        while ($sSOAPClient->getXmlTag()->getStringValue($szXmlFormatString1.$nErrorMessageCount.$szXmlFormatString2, $szString))
                                        {
                                            $lszErrorMessages->add($szString);

                                            $nErrorMessageCount++;
                                        }

								    	$goGatewayOutput = new GatewayOutput($nStatusCode, $szMessage, $lszErrorMessages);

		                                // look to see if there are any gateway entry points
    		                            $nCount = 0;
                                        $szXmlFormatString1 = $szGatewayOutputXMLPath.".GatewayEntryPoints.GatewayEntryPoint[";
                                        $szXmlFormatString2 = "].";

		                                while ($sSOAPClient->getXmlTag()->getStringValue($szXmlFormatString1.$nCount.$szXmlFormatString2.".EntryPointURL", $szEntryPointURL))
                                        {
                                            if (!$sSOAPClient->getXmlTag()->getIntegerValue($szXmlFormatString1.$nCount.$szXmlFormatString2.".Metric", $nMetric))
                                            {
                                                $nMetric = -1;
                                            }
                                            $gepGatewayEntryPoint = new GatewayEntryPoint($szEntryPointURL, $nMetric);
                                            if ($lgepGatewayEntryPoints == null)
                                            {
                                                $lgepGatewayEntryPoints = new GatewayEntryPointList();
                                            }
                                            $lgepGatewayEntryPoints->add($gepGatewayEntryPoint);
                                            $nCount++;
                                        }
								    }
                                }
	                    	}

	                        $nGatewayEntryPointCount++;
	                  	}
	              	}
	                $nOverallGatewayEntryPointCount++;
	       		}
	       		$nOverallRetryCount++;
	   		}
	   		$this->m_szLastRequest = $sSOAPClient->getSOAPPacket();
	   		$this->m_szLastResponse = $sSOAPClient->getLastResponse();
	   		$this->m_eLastException = $sSOAPClient->getLastException();

	   		return $boTransactionSubmitted;
		}

		public function __construct(RequestGatewayEntryPointList $lrgepRequestGatewayEntryPoints = null,
									$nRetryAttempts = 1,
									\net\thepaymentgateway\common\NullableInt $nTimeout = null)
		{
			$this->m_maMerchantAuthentication = new MerchantAuthentication();
			$this->m_lrgepRequestGatewayEntryPoints = $lrgepRequestGatewayEntryPoints;
			$this->m_nRetryAttempts = $nRetryAttempts;
			$this->m_nTimeout = $nTimeout;
		}
	}

	class SharedFunctions
	{
		public static function getTransactionOutputData(\net\thepaymentgateway\common\XmlTag $xtTransactionOutputDataXmlTag, GatewayEntryPointList $lgepGatewayEntryPoints = null)
		{
            $szCrossReference = "";
            $szAddressNumericCheckResult = "";
            $szPostCodeCheckResult = "";
            $szThreeDSecureAuthenticationCheckResult = "";
            $szCV2CheckResult = "";
            $nAmountReceived = null;
            $szPaREQ = "";
            $szACSURL = "";
			$szThreeDSecureVersion = "";
            $szCREQ = "";
            $szMethodData = "";
            $szMethodURL = "";
            $ctdCardTypeData = null;
            $tdsodThreeDSecureOutputData = null;
            $lgvCustomVariables = null;
            $nCount = 0;
            $szAuthCode = "";
            $todTransactionOutputData = null;

            if ($xtTransactionOutputDataXmlTag == null)
            {
                return (null);
            }

            $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.CrossReference", $szCrossReference);
            $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.AuthCode", $szAuthCode);
            $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.AddressNumericCheckResult", $szAddressNumericCheckResult);
            $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.PostCodeCheckResult", $szPostCodeCheckResult);
            $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.ThreeDSecureAuthenticationCheckResult", $szThreeDSecureAuthenticationCheckResult);
            $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.CV2CheckResult", $szCV2CheckResult);

            $ctdCardTypeData = SharedFunctions::getCardTypeData($xtTransactionOutputDataXmlTag, "TransactionOutputData.CardTypeData");
            if ($xtTransactionOutputDataXmlTag->getIntegerValue("TransactionOutputData.AmountReceived", $nTempValue))
            {
                $nAmountReceived = new \net\thepaymentgateway\common\NullableInt($nTempValue);
            }

			if (!$xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.ThreeDSecureOutputData.ThreeDSecureVersion", $szThreeDSecureVersion) ||
				\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($szThreeDSecureVersion))
            {
                $szThreeDSecureVersion = "1.1.0";
            }
            else
            {
                $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.ThreeDSecureOutputData.ThreeDSecureVersion", $szThreeDSecureVersion);
            }

            $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.ThreeDSecureOutputData.PaREQ", $szPaREQ);
            $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.ThreeDSecureOutputData.ACSURL", $szACSURL);
            $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.ThreeDSecureOutputData.CREQ", $szCREQ);
            $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.ThreeDSecureOutputData.MethodURL", $szMethodURL);
            $xtTransactionOutputDataXmlTag->getStringValue("TransactionOutputData.ThreeDSecureOutputData.MethodData", $szMethodData);

            $tdsodThreeDSecureOutputData = new ThreeDSecureOutputData($szPaREQ, $szACSURL, $szThreeDSecureVersion, $szCREQ, $szMethodURL, $szMethodData);

            $nCount = 0;
            $szXmlFormatString1 = "TransactionOutputData.CustomVariables.GenericVariable[";
            $szXmlFormatString2 = "]";

            while ($xtTransactionOutputDataXmlTag->getStringValue($szXmlFormatString1.$nCount.$szXmlFormatString2.".Name", $szName))
            {
                if (!$xtTransactionOutputDataXmlTag->getValue($szXmlFormatString1.$nCount.$szXmlFormatString2.".Value", $szValue))
                {
                    $szValue = "";
                }
                if ($lgvCustomVariables == null)
                {
                    $lgvCustomVariables = new GenericVariableList();
                }
                $lgvCustomVariables->add($szName, $szValue);
                $nCount++;
            }

            $todTransactionOutputData = new TransactionOutputData($szCrossReference,
                $szAuthCode,
                $szAddressNumericCheckResult,
                $szPostCodeCheckResult,
                $szThreeDSecureAuthenticationCheckResult,
                $szCV2CheckResult,
                $ctdCardTypeData,
                $nAmountReceived,
                $tdsodThreeDSecureOutputData,
                $lgvCustomVariables,
                $lgepGatewayEntryPoints);

            return ($todTransactionOutputData);
		}

		public static function getCardTypeData(\net\thepaymentgateway\common\XmlTag $xtGetCardTypeXmlTag, $szCardTypeDataXMLPath)
		{
            $ctdCardTypeData = null;
            $boTempValue;
            $nISOCode = null;
            $boLuhnCheckRequired = null;
            $szStartDateStatus = null;
            $szIssueNumberStatus = null;
            $szCardType;
            $szIssuer = null;
            $nTemp;
            $iIssuer;

            if ($xtGetCardTypeXmlTag == null)
            {
                return (null);
            }

            if ($xtGetCardTypeXmlTag->getStringValue($szCardTypeDataXMLPath.".CardType", $szCardType))
            {
                $xtGetCardTypeXmlTag->getStringValue($szCardTypeDataXMLPath.".Issuer", $szIssuer);
                if ($xtGetCardTypeXmlTag->getIntegerValue($szCardTypeDataXMLPath.".Issuer.ISOCode", $nTemp))
                {
                    $nISOCode = new \net\thepaymentgateway\common\NullableInt($nTemp);
                }
                $iIssuer = new Issuer($szIssuer, $nISOCode);
                if ($xtGetCardTypeXmlTag->getBooleanValue($szCardTypeDataXMLPath.".LuhnCheckRequired", $boTempValue))
                {
                    $boLuhnCheckRequired = new \net\thepaymentgateway\common\NullableBool($boTempValue);
                }
                $xtGetCardTypeXmlTag->getStringValue($szCardTypeDataXMLPath.".IssueNumberStatus", $szIssueNumberStatus);
                $xtGetCardTypeXmlTag->getStringValue($szCardTypeDataXMLPath.".StartDateStatus", $szStartDateStatus);
                $ctdCardTypeData = new CardTypeData($szCardType, $iIssuer, $boLuhnCheckRequired, $szIssueNumberStatus, $szStartDateStatus);
            }

            return ($ctdCardTypeData);
		}

	   	public static function getPaymentMessageGatewayOutput(\net\thepaymentgateway\common\XmlTag $xtMessageResultXmlTag, $szGatewayOutputXMLPath, GatewayOutput $goGatewayOutput = null)
	   	{
            $nTempValue = 0;
            $boAuthorisationAttempted = null;
            $boTempValue;
            $nPreviousStatusCode = null;
            $szPreviousMessage = null;
            $ptrPreviousTransactionResult = null;
            $pmgoPaymentMessageGatewayOutput;

            if ($xtMessageResultXmlTag == null)
            {
                return (null);
            }

            if ($xtMessageResultXmlTag->getBooleanValue($szGatewayOutputXMLPath.".AuthorisationAttempted", $boTempValue))
            {
                $boAuthorisationAttempted = new \net\thepaymentgateway\common\NullableBool($boTempValue);
            }

            // check to see if there is any previous transaction data
            if ($xtMessageResultXmlTag->getIntegerValue($szGatewayOutputXMLPath.".PreviousTransactionResult.StatusCode", $nTempValue))
            {
                $nPreviousStatusCode = new \net\thepaymentgateway\common\NullableInt($nTempValue);
            }
            $xtMessageResultXmlTag->getStringValue($szGatewayOutputXMLPath.".PreviousTransactionResult.Message", $szPreviousMessage);
            if ($nPreviousStatusCode != null &&
                !\net\thepaymentgateway\common\SharedFunctions::isStringNullOrEmpty($szPreviousMessage))
            {
                $ptrPreviousTransactionResult = new PreviousTransactionResult($nPreviousStatusCode, $szPreviousMessage);
            }

			$pmgoPaymentMessageGatewayOutput = new PaymentMessageGatewayOutput($goGatewayOutput->getStatusCode(),
                                                                               $goGatewayOutput->getMessage(),
                                                                               $boAuthorisationAttempted,
                                                                               $ptrPreviousTransactionResult,
                                                                               $goGatewayOutput->getErrorMessages());

	   		return ($pmgoPaymentMessageGatewayOutput);
		}
	}
?>