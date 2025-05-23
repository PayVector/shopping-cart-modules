<?php

class TransactionResult
{
	private $m_nStatusCode;
	private $m_szMessage;
	private $m_nPreviousStatusCode;
	private $m_szPreviousMessage;
	private $m_szCrossReference;
	private $m_szAddressNumericCheckResult;
	private $m_szPostCodeCheckResult;
	private $m_szCV2CheckResult;
	private $m_szThreeDSecureAuthenticationCheckResult;
	private $m_szFraudProtectionCheckResult;
	private $m_szCardType;
	private $m_szCardClass;
	private $m_szCardIssuer;
	private $m_nCardIssuerCountryCode;
	private $m_szCardNumberFirstSix;
	private $m_szCardNumberLastFour;
	private $m_szCardExpiryDate;
	private $m_nAmount;
	private $m_nCurrencyCode;
	private $m_szOrderID;
	private $m_szTransactionType;
	private $m_szTransactionDateTime;
	private $m_szOrderDescription;
	private $m_szCustomerName;
	private $m_szAddress1;
	private $m_szAddress2;
	private $m_szAddress3;
	private $m_szAddress4;
	private $m_szCity;
	private $m_szState;
	private $m_szPostCode;
	private $m_nCountryCode;
	private $m_szEmailAddress;
	private $m_szPhoneNumber;

	//Transparent Redirect Fields
	private $m_szMerchantID;
	private $m_szACSURL;
	private $m_szPaREQ;
	private $m_szCallbackUrl;
	private $m_szPaRES;

	public function getStatusCode()
	{
		return $this->m_nStatusCode;
	}

	public function setStatusCode($nStatusCode)
	{
		$this->m_nStatusCode = $nStatusCode;
	}

	public function getMessage()
	{
		return $this->m_szMessage;
	}

	public function setMessage($szMessage)
	{
		$this->m_szMessage = $szMessage;
	}

	public function getPreviousStatusCode()
	{
		return $this->m_nPreviousStatusCode;
	}

	public function setPreviousStatusCode($nPreviousStatusCode)
	{
		$this->m_nPreviousStatusCode = $nPreviousStatusCode;
	}

	public function getPreviousMessage()
	{
		return $this->m_szPreviousMessage;
	}

	public function setPreviousMessage($szPreviousMessage)
	{
		$this->m_szPreviousMessage = $szPreviousMessage;
	}

	public function getCrossReference()
	{
		return $this->m_szCrossReference;
	}

	public function setCrossReference($szCrossReference)
	{
		$this->m_szCrossReference = $szCrossReference;
	}

	public function getAddressNumericCheckResult()
	{
		return $this->m_szAddressNumericCheckResult;
	}

	public function setAddressNumericCheckResult($szAddressNumericCheckResult)
	{
		$this->m_szAddressNumericCheckResult = $szAddressNumericCheckResult;
	}

	public function getPostCodeCheckResult()
	{
		return $this->m_szPostCodeCheckResult;
	}

	public function setPostCodeCheckResult($szPostCodeCheckResult)
	{
		$this->m_szPostCodeCheckResult = $szPostCodeCheckResult;
	}

	public function getCV2CheckResult()
	{
		return $this->m_szCV2CheckResult;
	}

	public function setCV2CheckResult($szCV2CheckResult)
	{
		$this->m_szCV2CheckResult = $szCV2CheckResult;
	}

	public function getThreeDSecureAuthenticationCheckResult()
	{
		return $this->m_szThreeDSecureAuthenticationCheckResult;
	}

	public function setThreeDSecureAuthenticationCheckResult($szThreeDSecureAuthenticationCheckResult)
	{
		$this->m_szThreeDSecureAuthenticationCheckResult = $szThreeDSecureAuthenticationCheckResult;
	}

	public function getFraudProtectionCheckResult()
	{
		return $this->m_szFraudProtectionCheckResult;
	}

	public function setFraudProtectionCheckResult($szFraudProtectionCheckResult)
	{
		$this->m_szFraudProtectionCheckResult = $szFraudProtectionCheckResult;
	}

	public function getCardType()
	{
		return $this->m_szCardType;
	}

	public function setCardType($szCardType)
	{
		$this->m_szCardType = $szCardType;
	}

	public function getCardClass()
	{
		return $this->m_szCardClass;
	}

	public function setCardClass($szCardClass)
	{
		$this->m_szCardClass = $szCardClass;
	}

	public function getCardIssuer()
	{
		return $this->m_szCardIssuer;
	}

	public function setCardIssuer($szCardIssuer)
	{
		$this->m_szCardIssuer = $szCardIssuer;
	}

	public function getCardIssuerCountryCode()
	{
		return $this->m_nCardIssuerCountryCode;
	}

	public function setCardIssuerCountryCode($nCardIssuerCountryCode)
	{
		$this->m_nCardIssuerCountryCode = $nCardIssuerCountryCode;
	}

	public function getCardNumberFirstSix()
	{
		return $this->m_szCardNumberFirstSix;
	}

	public function setCardNumberFirstSix($szCardFirstSix)
	{
		$this->m_szCardNumberFirstSix = $szCardFirstSix;
	}

	public function getCardNumberLastFour()
	{
		return $this->m_szCardNumberLastFour;
	}

	public function setCardNumberLastFour($szCardLastFour)
	{
		$this->m_szCardNumberLastFour = $szCardLastFour;
	}

	public function getCardExpiryDate()
	{
		return $this->m_szCardExpiryDate;
	}

	public function setCardExpiryDate($szCardExpiryDate)
	{
		$this->m_szCardExpiryDate = $szCardExpiryDate;
	}

	public function getAmount()
	{
		return $this->m_nAmount;
	}

	public function setAmount($nAmount)
	{
		$this->m_nAmount = $nAmount;
	}

	public function getCurrencyCode()
	{
		return $this->m_nCurrencyCode;
	}

	public function setCurrencyCode($nCurrencyCode)
	{
		$this->m_nCurrencyCode = $nCurrencyCode;
	}

	public function getOrderID()
	{
		return $this->m_szOrderID;
	}

	public function setOrderID($szOrderID)
	{
		$this->m_szOrderID = $szOrderID;
	}

	public function getTransactionType()
	{
		return $this->m_szTransactionType;
	}

	public function setTransactionType($szTransactionType)
	{
		$this->m_szTransactionType = $szTransactionType;
	}

	public function getTransactionDateTime()
	{
		return $this->m_szTransactionDateTime;
	}

	public function setTransactionDateTime($szTransactionDateTime)
	{
		$this->m_szTransactionDateTime = $szTransactionDateTime;
	}

	public function getOrderDescription()
	{
		return $this->m_szOrderDescription;
	}

	public function setOrderDescription($szOrderDescription)
	{
		$this->m_szOrderDescription = $szOrderDescription;
	}

	public function getCustomerName()
	{
		return $this->m_szCustomerName;
	}

	public function setCustomerName($szCustomerName)
	{
		$this->m_szCustomerName = $szCustomerName;
	}

	public function getAddress1()
	{
		return $this->m_szAddress1;
	}

	public function setAddress1($szAddress1)
	{
		$this->m_szAddress1 = $szAddress1;
	}

	public function getAddress2()
	{
		return $this->m_szAddress2;
	}

	public function setAddress2($szAddress2)
	{
		$this->m_szAddress2 = $szAddress2;
	}

	public function getAddress3()
	{
		return $this->m_szAddress3;
	}

	public function setAddress3($szAddress3)
	{
		$this->m_szAddress3 = $szAddress3;
	}

	public function getAddress4()
	{
		return $this->m_szAddress4;
	}

	public function setAddress4($szAddress4)
	{
		$this->m_szAddress4 = $szAddress4;
	}

	public function getCity()
	{
		return $this->m_szCity;
	}

	public function setCity($szCity)
	{
		$this->m_szCity = $szCity;
	}

	public function getState()
	{
		return $this->m_szState;
	}

	public function setState($szState)
	{
		$this->m_szState = $szState;
	}

	public function getPostCode()
	{
		return $this->m_szPostCode;
	}

	public function setPostCode($szPostCode)
	{
		$this->m_szPostCode = $szPostCode;
	}

	public function getCountryCode()
	{
		return $this->m_nCountryCode;
	}

	public function setCountryCode($nCountryCode)
	{
		$this->m_nCountryCode = $nCountryCode;
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

	public function setMerchantID($szMerchantID)
	{
		$this -> m_szMerchantID = $szMerchantID;
	}

	public function getMerchantID()
	{
		return $this -> m_szMerchantID;
	}

	public function setACSURL($szACSURL)
	{
		$this -> m_szACSURL = $szACSURL;
	}

	public function getACSURL()
	{
		return $this -> m_szACSURL;
	}

	public function setPaREQ($szPaREQ)
	{
		$this -> m_szPaREQ = $szPaREQ;
	}

	public function getPaREQ()
	{
		return $this -> m_szPaREQ;
	}

	public function setCallbackUrl($szCallbackUrl)
	{
		$this -> m_szCallbackUrl = $szCallbackUrl;
	}

	public function getCallbackURL()
	{
		return $this -> m_szCallbackUrl;
	}

	public function setPaRES($szPaRES)
	{
		$this -> m_szPaRES = $szPaRES;
	}

	public function getPaRES()
	{
		return $this -> m_szPaRES;
	}
}

class ListItemList
{
	private $m_lilListItemList;
				
	public function getCount()
	{
		return count($this->m_lilListItemList);
	}
	
	public function getAt($nIndex)
	{
		if ($nIndex < 0 ||
			$nIndex >= count($this->m_lilListItemList))
		{
			throw new Exception('Array index out of bounds');
		}
			
		return $this->m_lilListItemList[$nIndex];
	}
	
	public function add($szName, $szValue, $boIsSelected)
	{
		$liListItem = new ListItem($szName, $szValue, $boIsSelected);

		$this->m_lilListItemList[] = $liListItem;
	}

	public function toString()
	{
		$szReturnString = "";

		for ($nCount = 0; $nCount < count($this->m_lilListItemList); $nCount++)
		{
			$liListItem = $this->m_lilListItemList[$nCount];
			
			$szReturnString = $szReturnString."<option";

			if ($liListItem->getValue() != null &&
				$liListItem->getValue() != "")
			{
				$szReturnString = $szReturnString." value=\"".$liListItem->getValue()."\"";
			}

			if ($liListItem->getIsSelected() == true)
			{
				$szReturnString = $szReturnString." selected=\"selected\"";	
			}

			$szReturnString = $szReturnString.">".$liListItem->getName()."</option>\n";
		}

		return ($szReturnString);
	}

	//constructor
	public function __construct()
	{
        $this->m_lilListItemList = array();
	}
}
class ListItem
{
	private $m_szName;
   	private $m_szValue;
    private $m_boIsSelected;
    
    //public properties
    public function getName()
    {
    	return $this->m_szName;
    }
    
    public function getValue()
    {
    	return $this->m_szValue;
    }
   
    public function getIsSelected()
    {
    	return $this->m_boIsSelected;
    }
   	    
    //constructor
    public function __construct($szName, $szValue, $boIsSelected)
    {
    	$this->m_szName = $szName;
    	$this->m_szValue = $szValue;
    	$this->m_boIsSelected = $boIsSelected;
    }
}

class PaymentFormHelper
{
	public static function boolToString($boBool)
	{
		$szReturnValue = "false";
		if($boBool)
		{
			$szReturnValue = "true";
		}

		return ($szReturnValue);
	}
	public static function stringToBool($szString)
	{
		$boReturnValue = false;
		if(strToUpper($szString) == "TRUE")
		{
			$boReturnValue = true;
		}

		return ($boReturnValue);
	}
	public static function base64UrlEncode($szString) 
	{
		// Encode the string in base64
		$szBase64 = base64_encode($szString);

		// Replace '+' with '-', '/' with '_', and remove trailing '=' characters
		$szUrlSafe = rtrim(strtr($szBase64, '+/', '-_'), '=');

		return $szUrlSafe;
	}
	public static function base64UrlDecode($szString) 
	{
		// Replace URL-safe characters with standard Base64 characters
		$szBase64 = strtr($szString, '-_', '+/');

		// Add necessary padding to make the length a multiple of 4
		$nPadding = strlen($szBase64) % 4;
		if ($nPadding) 
		{
			$szBase64 .= str_repeat('=', 4 - $nPadding);
		}

		// Decode the base64 string
		return base64_decode($szBase64);
	}
	public static function addStringToStringList($szExistingStringList, $szStringToAdd)
	{
		$szCommaString = "";
		if(strlen($szStringToAdd) == 0)
		{
			$szReturnString = $szExistingStringList;
		}
		else
		{
			if(strlen($szExistingStringList) != 0)
			{
				$szCommaString = ", ";
			}
			$szReturnString = $szExistingStringList . $szCommaString . $szStringToAdd;
		}

		return ($szReturnString);
	}	
	public static function parseNameValueStringIntoArray($szNameValueString, $boURLDecodeValues)
	{
		// break the reponse into an array
		// first break the variables up using the "&" delimter
		$aPostVariables = explode("&", $szNameValueString);
		$aParsedVariables = array();
		foreach($aPostVariables as $szVariable)
		{
			// for each variable, split is again on the "=" delimiter
			// to give name/value pairs
			$aSingleVariable = explode("=", $szVariable);
			$szName = $aSingleVariable[0];
			if(!$boURLDecodeValues)
			{
				$szValue = $aSingleVariable[1];
			}
			else
			{
				$szValue = urldecode($aSingleVariable[1]);
			}
			$aParsedVariables[$szName] = $szValue;
		}

		return ($aParsedVariables);
	}
	public static function getSiteSecureBaseURL()
	{
		$szReturnString = "";
		$szPortString = "";
		$szProtocolString = "";

		if (array_key_exists('HTTPS', $_SERVER) && $_SERVER["HTTPS"] == "on")
		{
			$szProtocolString = "https://";
			if (array_key_exists('SERVER_PORT', $_SERVER) && $_SERVER["SERVER_PORT"] != 443)
			{
				$szPortString = ":".$_SERVER["SERVER_PORT"];
			}
		}
		else
		{
			$szProtocolString = "http://";			
			if (array_key_exists('SERVER_PORT', $_SERVER) && $_SERVER["SERVER_PORT"] != 80)
			{
				$szPortString = ":".$_SERVER["SERVER_PORT"];
			}
		}

		$szReturnString = $szProtocolString.$_SERVER["SERVER_NAME"].$szPortString.$_SERVER["SCRIPT_NAME"];
		
		$boFinished = false;
		$LoopIndex = strlen($szReturnString) - 1;

		while ($boFinished == false &&
			   $LoopIndex >= 0)
		{
			if ($szReturnString[$LoopIndex] == "/")
			{
				$boFinished = true;
				$szReturnString = substr($szReturnString, 0, $LoopIndex + 1);
			}
			$LoopIndex--;;
		}

		return ($szReturnString);
	}
	public static function createExpiryDateMonthListItemList($ExpiryDateMonth)
	{
		$lilExpiryDateMonthList = new ListItemList();

		for ($LoopIndex = 1; $LoopIndex <= 12; $LoopIndex++)
		{
			$DisplayMonth = $LoopIndex;
			if ($LoopIndex < 10)
			{
				$DisplayMonth = "0".$LoopIndex;
			}
			if ($ExpiryDateMonth != "" &&
			    $ExpiryDateMonth == $LoopIndex)
			{
				$lilExpiryDateMonthList->add($DisplayMonth, $DisplayMonth, true);
			}
			else
			{
				$lilExpiryDateMonthList->add($DisplayMonth, $DisplayMonth, false);
			}
		}

		return ($lilExpiryDateMonthList);
	}
	public static function createExpiryDateYearListItemList($ExpiryDateYear)
	{
		$ThisYear = date("Y");
		$ThisYearPlusTen = $ThisYear + 10;
	
		$lilExpiryDateYearList = new ListItemList();

		for ($LoopIndex = $ThisYear; $LoopIndex <= $ThisYearPlusTen; $LoopIndex++)
		{
			$ShortYear=substr($LoopIndex, strlen($LoopIndex)-2, 2);
			if ($ExpiryDateYear != "" &&
			    $ExpiryDateYear == $ShortYear)
			{
				$lilExpiryDateYearList->add($LoopIndex, $ShortYear, true);
			}
			else
			{
				$lilExpiryDateYearList->add($LoopIndex, $ShortYear, false);
			}
		}

		return ($lilExpiryDateYearList);
	}
	public static function createISOCountryListItemList($CountryShort, $iclISOCountryList)
	{
		$lilISOCountryList = new ListItemList();

		$FirstZeroPriorityGroup = true;
		for ($LoopIndex = 0; $LoopIndex < $iclISOCountryList->getCount()-1; $LoopIndex++)
		{
			if ($iclISOCountryList->getAt($LoopIndex)->getListPriority() == 0 &&
				$FirstZeroPriorityGroup == true)
			{
				$lilISOCountryList->add("--------------------", "-1", false);
				$FirstZeroPriorityGroup = false;
			} 

			if ($CountryShort != "" &&
				$CountryShort != -1 &&
				$CountryShort == $iclISOCountryList->getAt($LoopIndex)->getCountryShort())
			{
				$lilISOCountryList->add($iclISOCountryList->getAt($LoopIndex)->getCountryName(), $iclISOCountryList->getAt($LoopIndex)->getCountryShort(), true);
			}
			else
			{
				$lilISOCountryList->add($iclISOCountryList->getAt($LoopIndex)->getCountryName(), $iclISOCountryList->getAt($LoopIndex)->getCountryShort(), false);
			}
		}

		return ($lilISOCountryList);
	}
	public static function getTransactionReferenceFromQueryString(
		$aQueryStringVariables,
		&$szCrossReference,
		&$szOrderID,
		&$szHashDigest,
		&$szOutputMessage)
	{
		$szHashDigest = "";
		$szOutputMessage = "";
		$boErrorOccurred = false;
		try
		{
			// hash digest
			if(isset($aQueryStringVariables["HashDigest"]))
			{
				$szHashDigest = $aQueryStringVariables["HashDigest"];
			}
			// cross reference of transaction
			if(!isset($aQueryStringVariables["CrossReference"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [CrossReference] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szCrossReference = $aQueryStringVariables["CrossReference"];
			}
			// order ID (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aQueryStringVariables["OrderID"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [OrderID] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szOrderID = $aQueryStringVariables["OrderID"];
			}
		}
		catch(Exception $e)
		{
			$boErrorOccurred = true;
			$szOutputMessage = $e->getMessage();
		}

		return (!$boErrorOccurred);
	}
	public static function getTransactionResultFromPostVariables($aFormVariables, &$trTransactionResult, &$szHashDigest, &$szOutputMessage)
	{
		$trTransactionResult = null;
		$szHashDigest = "";
		$szOutputMessage = "";
		$boErrorOccurred = false;
		try
		{
			// hash digest
			if(isset($aFormVariables["HashDigest"]))
			{
				$szHashDigest = $aFormVariables["HashDigest"];
			}
			// transaction status code
			if(!isset($aFormVariables["StatusCode"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [StatusCode] not received");
				$boErrorOccurred = true;
			}
			else
			{
				if($aFormVariables["StatusCode"] == null ||
				   $aFormVariables["StatusCode"] == "")
				{
					$nStatusCode = null;
				}
				else
				{
					$nStatusCode = intval($aFormVariables["StatusCode"]);
				}
			}
			// transaction message
			if(!isset($aFormVariables["Message"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [Message] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szMessage = $aFormVariables["Message"];
			}
			// status code of original transaction if this transaction was deemed a duplicate
			if(!isset($aFormVariables["PreviousStatusCode"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [PreviousStatusCode] not received");
				$boErrorOccurred = true;
			}
			else
			{
				if($aFormVariables["PreviousStatusCode"] == null ||
				   $aFormVariables["PreviousStatusCode"] == "")
				{
					$nPreviousStatusCode = null;
				}
				else
				{
					$nPreviousStatusCode = intval($aFormVariables["PreviousStatusCode"]);
				}
			}
			// status code of original transaction if this transaction was deemed a duplicate
			if(!isset($aFormVariables["PreviousMessage"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [PreviousMessage] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szPreviousMessage = $aFormVariables["PreviousMessage"];
			}
			// cross reference of transaction
			if(!isset($aFormVariables["CrossReference"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [CrossReference] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szCrossReference = $aFormVariables["CrossReference"];
			}
			// result of the address numeric check (only returned if EchoAVSCheckResult is set as true on the input)
			if(isset($aFormVariables["AddressNumericCheckResult"]))
			{
				$szAddressNumericCheckResult = $aFormVariables["AddressNumericCheckResult"];
			}
			// result of the post code check (only returned if EchoAVSCheckResult is set as true on the input)
			if(isset($aFormVariables["PostCodeCheckResult"]))
			{
				$szPostCodeCheckResult = $aFormVariables["PostCodeCheckResult"];
			}
			// result of the CV2 check (only returned if EchoCV2CheckResult is set as true on the input)
			if(isset($aFormVariables["CV2CheckResult"]))
			{
				$szCV2CheckResult = $aFormVariables["CV2CheckResult"];
			}
			// result of the 3DSecure check (only returned if EchoThreeDSecureAuthenticationCheckResult is set as true on the input)
			if(isset($aFormVariables["ThreeDSecureAuthenticationCheckResult"]))
			{
				$szThreeDSecureAuthenticationCheckResult = $aFormVariables["ThreeDSecureAuthenticationCheckResult"];
			}
			// card type (only returned if EchoCardType is set as true on the input)
			if(isset($aFormVariables["CardType"]))
			{
				$szCardType = $aFormVariables["CardType"];
			}
			// card class (only returned if EchoCardType is set as true on the input)
			if(isset($aFormVariables["CardClass"]))
			{
				$szCardClass = $aFormVariables["CardClass"];
			}
			// card issuer (only returned if EchoCardType is set as true on the input)
			if(isset($aFormVariables["CardIssuer"]))
			{
				$szCardIssuer = $aFormVariables["CardIssuer"];
			}
			// card issuer country code (only returned if EchoCardType is set as true on the input)
			if(isset($aFormVariables["CardIssuerCountryCode"]))
			{
				if($aFormVariables["CardIssuerCountryCode"] == null ||
				   $aFormVariables["CardIssuerCountryCode"] == "")
				{
					$nCardIssuerCountryCode = null;
				}
				else
				{
					$nCardIssuerCountryCode = intval($aFormVariables["CardIssuerCountryCode"]);
				}
			}
			if(isset($aFormVariables["CardNumberFirstSix"]))
			{
				$szCardNumberFirstSix = $aFormVariables["CardNumberFirstSix"];
			}
			if(isset($aFormVariables["CardNumberLastFour"]))
			{
				$szCardNumberLastFour = $aFormVariables["CardNumberLastFour"];
			}
			// amount (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aFormVariables["Amount"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [Amount] not received");
				$boErrorOccurred = true;
			}
			else
			{
				if($aFormVariables["Amount"] == null ||
				   $aFormVariables["Amount"] == "")
				{
					$nAmount = null;
				}
				else
				{
					$nAmount = intval($aFormVariables["Amount"]);
				}
			}
			// currency code (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aFormVariables["CurrencyCode"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [CurrencyCode] not received");
				$boErrorOccurred = true;
			}
			else
			{
				if($aFormVariables["CurrencyCode"] == null ||
				   $aFormVariables["CurrencyCode"] == "")
				{
					$nCurrencyCode = null;
				}
				else
				{
					$nCurrencyCode = intval($aFormVariables["CurrencyCode"]);
				}
			}
			// order ID (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aFormVariables["OrderID"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [OrderID] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szOrderID = $aFormVariables["OrderID"];
			}
			// transaction type (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aFormVariables["TransactionType"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [TransactionType] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szTransactionType = $aFormVariables["TransactionType"];
			}
			// transaction date/time (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aFormVariables["TransactionDateTime"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [TransactionDateTime] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szTransactionDateTime = $aFormVariables["TransactionDateTime"];
			}
			// order description (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aFormVariables["OrderDescription"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [OrderDescription] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szOrderDescription = $aFormVariables["OrderDescription"];
			}
			// customer name (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aFormVariables["CustomerName"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [CustomerName] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szCustomerName = $aFormVariables["CustomerName"];
			}
			// address1 (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aFormVariables["Address1"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [Address1] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szAddress1 = $aFormVariables["Address1"];
			}
			// address2 (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aFormVariables["Address2"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [Address2] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szAddress2 = $aFormVariables["Address2"];
			}
			// address3 (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aFormVariables["Address3"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [Address3] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szAddress3 = $aFormVariables["Address3"];
			}
			// address4 (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aFormVariables["Address4"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [Address4] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szAddress4 = $aFormVariables["Address4"];
			}
			// city (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aFormVariables["City"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [City] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szCity = $aFormVariables["City"];
			}
			// state (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aFormVariables["State"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [State] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szState = $aFormVariables["State"];
			}
			// post code (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aFormVariables["PostCode"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [PostCode] not received");
				$boErrorOccurred = true;
			}
			else
			{
				$szPostCode = $aFormVariables["PostCode"];
			}
			// country code (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aFormVariables["CountryCode"]))
			{
				$szOutputMessage = PaymentFormHelper::addStringToStringList($szOutputMessage, "Expected variable [CountryCode] not received");
				$boErrorOccurred = true;
			}
			else
			{
				if($aFormVariables["CountryCode"] == null ||
				   $aFormVariables["CountryCode"] == "")
				{
					$nCountryCode = null;
				}
				else
				{
					$nCountryCode = intval($aFormVariables["CountryCode"]);
				}
			}
			// email address (only returned if in the input)
			if(isset($aFormVariables["EmailAddress"]))
			{
				$szEmailAddress = $aFormVariables["EmailAddress"];
			}
			// phone number (only returned if in the input)
			if(isset($aFormVariables["PhoneNumber"]))
			{
				$szPhoneNumber = $aFormVariables["PhoneNumber"];
			}
			if(!$boErrorOccurred)
			{
				$trTransactionResult = new TransactionResult();
				$trTransactionResult->setStatusCode($nStatusCode); // transaction status code
				$trTransactionResult->setMessage($szMessage); // transaction message
				$trTransactionResult->setPreviousStatusCode($nPreviousStatusCode); // status code of original transaction if duplicate transaction
				$trTransactionResult->setPreviousMessage($szPreviousMessage); // status code of original transaction if duplicate transaction
				$trTransactionResult->setCrossReference($szCrossReference); // cross reference of transaction
				if(isset($szAddressNumericCheckResult))
				{
					$trTransactionResult->setAddressNumericCheckResult($szAddressNumericCheckResult); // address numeric check result
				}
				if(isset($szPostCodeCheckResult))
				{
					$trTransactionResult->setPostCodeCheckResult($szPostCodeCheckResult); // post code check result
				}
				if(isset($szCV2CheckResult))
				{
					$trTransactionResult->setCV2CheckResult($szCV2CheckResult); // CV2 check result
				}
				if(isset($szThreeDSecureAuthenticationCheckResult))
				{
					$trTransactionResult->setThreeDSecureAuthenticationCheckResult($szThreeDSecureAuthenticationCheckResult); // 3DSecure check result
				}
				if(isset($szCardType))
				{
					$trTransactionResult->setCardType($szCardType); // card type
				}
				if(isset($szCardClass))
				{
					$trTransactionResult->setCardClass($szCardClass); // card class
				}
				if(isset($szCardIssuer))
				{
					$trTransactionResult->setCardIssuer($szCardIssuer); // card issuer
				}
				if(isset($nCardIssuerCountryCode))
				{
					$trTransactionResult->setCardIssuerCountryCode($nCardIssuerCountryCode); // card issuer country code
				}
				if(isset($szCardNumberFirstSix))
				{
					$trTransactionResult->setCardNumberFirstSix($szCardNumberFirstSix);
				}
				if(isset($szCardNumberLastFour))
				{
					$trTransactionResult->setCardNumberLastFour($szCardNumberLastFour);
				}
				$trTransactionResult->setAmount($nAmount); // amount echoed back
				$trTransactionResult->setCurrencyCode($nCurrencyCode); // currency code echoed back
				$trTransactionResult->setOrderID($szOrderID); // order ID echoed back
				$trTransactionResult->setTransactionType($szTransactionType); // transaction type echoed back
				$trTransactionResult->setTransactionDateTime($szTransactionDateTime); // transaction date/time echoed back
				$trTransactionResult->setOrderDescription($szOrderDescription); // order description echoed back
				// the customer details that were actually
				// processed (might be different
				// from those passed to the payment form)
				$trTransactionResult->setCustomerName($szCustomerName);
				$trTransactionResult->setAddress1($szAddress1);
				$trTransactionResult->setAddress2($szAddress2);
				$trTransactionResult->setAddress3($szAddress3);
				$trTransactionResult->setAddress4($szAddress4);
				$trTransactionResult->setCity($szCity);
				$trTransactionResult->setState($szState);
				$trTransactionResult->setPostCode($szPostCode);
				$trTransactionResult->setCountryCode($nCountryCode);
				if(isset($szEmailAddress))
				{
					$trTransactionResult->setEmailAddress($szEmailAddress); // customer's email address
				}
				if(isset($szPhoneNumber))
				{
					$trTransactionResult->setPhoneNumber($szPhoneNumber); // customer's phone number
				}
			}
		}
		catch(Exception $e)
		{
			$boErrorOccurred = true;
			$szOutputMessage = $e->getMessage();
		}

		return (!$boErrorOccurred);
	}	
	public static function getTransactionResultFromPaymentFormHandler(
		$szPaymentFormResultHandlerURL,
		$szMerchantID,
		$szPassword,
		$szCrossReference,
		&$trTransactionResult,
		&$szOutputMessage)
	{
		$szOutputMessage = "";
		$trTransactionResult = null;
		try
		{
			// use curl to post the cross reference to the
			// payment form to query its status
			$cCURL = curl_init();
			// build up the post string
			$szPostString = "MerchantID=" . urlencode($szMerchantID) . "&Password=" . urlencode($szPassword) . "&CrossReference=" . urlencode($szCrossReference);
			curl_setopt($cCURL, CURLOPT_URL, $szPaymentFormResultHandlerURL);
			//curl_setopt($cCURL, CURLOPT_DNS_SERVERS, '8.8.8.8,1.1.1.1');
			curl_setopt($cCURL, CURLOPT_POST, true);
			curl_setopt($cCURL, CURLOPT_POSTFIELDS, $szPostString);
			curl_setopt($cCURL, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($cCURL, CURLOPT_SSL_VERIFYPEER, true);
			//check if a certificate list is specified in the php.ini file, otherwise use the bundled one
			$caInfoSetting = ini_get("curl.cainfo");
			if(empty($caInfoSetting))
			{
				curl_setopt($cCURL, CURLOPT_CAINFO, __DIR__ . "/ThePaymentGateway/cacert.pem");
			}
			// read the response
			$szResponse = curl_exec($cCURL);
			$szErrorNo = curl_errno($cCURL);
			$ezErrorMsg = curl_error($cCURL);
			curl_close($cCURL);

			if($szResponse == "")
			{
				$boErrorOccurred = true;
				$szOutputMessage = "Received empty response from payment form hander";
				error_log("Error Number: ".$szErrorNo);
				error_log("Error Message: ".$ezErrorMsg);
			}
			else
			{
				try
				{
					// parse the response into an array
					$aParsedPostVariables = PaymentFormHelper::parseNameValueStringIntoArray($szResponse, true);
					if(!isset($aParsedPostVariables["StatusCode"]) OR intval($aParsedPostVariables["StatusCode"]) != 0)
					{
						$boErrorOccurred = true;
						// the message field is expected if the status code is non-zero
						if(!isset($aParsedPostVariables["Message"]))
						{
							$szOutputMessage = "Received invalid response from payment form hander [" . $szResponse . "]";
						}
						else
						{
							$szOutputMessage = $aParsedPostVariables["Message"];
						}
					}
					else
					{
						// status code is 0, so	get the transaction result
						if(!isset($aParsedPostVariables["TransactionResult"]))
						{
							$boErrorOccurred = true;
							$szOutputMessage = "No transaction result in response from payment form hander [" . $szResponse . "]";
						}
						else
						{
							// parse the URL decoded transaction result field into a name value array
							$aTransactionResultArray = PaymentFormHelper::parseNameValueStringIntoArray(urldecode($aParsedPostVariables["TransactionResult"]), false);
							// parse this array into a transaction result object
							if(!PaymentFormHelper::getTransactionResultFromPostVariables($aTransactionResultArray, $trTransactionResult, $szHashDigest, $szErrorMessage))
							{
								$boErrorOccurred = true;
								$szOutputMessage =
									"Error [" .
									$szErrorMessage .
									"] parsing transaction result [" .
									urldecode($aParsedPostVariables["TransactionResult"]) .
									"] in response from payment form hander [" .
									$szResponse .
									"]";
							}
							else
							{
								$boErrorOccurred = false;
							}
						}
					}
				}
				catch(Exception $e)
				{
					$boErrorOccurred = true;
					$szOutputMessage = "Exception [" . $e->getMessage() . "] when processing response from payment form handler [" . $szResponse . "]";
				}
			}
		}
		catch(Exception $e)
		{
			$boErrorOccurred = true;
			$szOutputMessage = $e->getMessage();
		}

		return (!$boErrorOccurred);
	}
	public static function validateTransactionResult_POST(
		$szMerchantID,
		$szPassword,
		$szPreSharedKey,
		$szHashMethod,
		$aPostVariables,
		&$trTransactionResult,
		&$szValidateErrorMessage)
	{
		$szValidateErrorMessage = "";
		$trTransactionResult = null;
		// read the transaction result variables from the post variable list
		if(!PaymentFormHelper::getTransactionResultFromPostVariables($aPostVariables, $trTransactionResult, $szHashDigest, $szOutputMessage))
		{
			$boErrorOccurred = true;
			$szValidateErrorMessage = $szOutputMessage;
		}
		else
		{
			// now need to validate the hash digest
			$szStringToHash = PaymentFormHelper::generateStringToHash2($szMerchantID, $szPassword, $trTransactionResult, $szPreSharedKey, $szHashMethod);
			$szCalculatedHashDigest = PaymentFormHelper::calculateHashDigest($szStringToHash, $szPreSharedKey, $szHashMethod);

			// does the calculated hash match the one that was passed?
			if(strToUpper($szHashDigest) != strToUpper($szCalculatedHashDigest))
			{
				$boErrorOccurred = true;
				
				$szValidateErrorMessage = "Hash digests don't match - possible variable tampering";
			}
			else
			{
				$boErrorOccurred = false;
			}
		}

		return (!$boErrorOccurred);
	}
	public static function validateTransactionResult_SERVER(
		$szMerchantID,
		$szPassword,
		$szPreSharedKey,
		$szHashMethod,
		$aQueryStringVariables,
		&$trTransactionResult,
		&$szValidateErrorMessage)
	{
		$szValidateErrorMessage = "";
		$trTransactionResult = null;
		// read the transaction reference variables from the query string variable list
		if(!PaymentFormHelper::getTransactionReferenceFromQueryString($aQueryStringVariables, $szCrossReference, $szOrderID, $szHashDigest, $szOutputMessage))
		{
			$boErrorOccurred = true;
			$szValidateErrorMessage = $szOutputMessage;
		}
		else
		{
			// now need to validate the hash digest
			$szStringToHash = PaymentFormHelper::generateStringToHash3($szMerchantID, $szPassword, $szCrossReference, $szOrderID, $szPreSharedKey, $szHashMethod);
			$szCalculatedHashDigest = PaymentFormHelper::calculateHashDigest($szStringToHash, $szPreSharedKey, $szHashMethod);
			// does the calculated hash match the one that was passed?
			if(strToUpper($szHashDigest) != strToUpper($szCalculatedHashDigest))
			{
				$boErrorOccurred = true;
				$szValidateErrorMessage = "Hash digests don't match - possible variable tampering";
			}
			else
			{
				// use the cross reference and/or the order ID to pull the
				// transaction results out of storage
				if(!PaymentFormHelper::getTransactionResultFromStorage($szCrossReference, $szOrderID, $trTransactionResult, $szOutputMessage))
				{
					$szValidateErrorMessage = $szOutputMessage;
					$boErrorOccurred = true;
				}
				else
				{
					$boErrorOccurred = false;
				}
			}
		}

		return (!$boErrorOccurred);
	}
	public static function validateTransactionResult_SERVER_PULL(
		$szMerchantID,
		$szPassword,
		$szPreSharedKey,
		$szHashMethod,
		$aQueryStringVariables,
		$szPaymentFormResultHandlerURL,
		&$trTransactionResult,
		&$szValidateErrorMessage)
	{
		$szValidateErrorMessage = "";
		$trTransactionResult = null;
		// read the transaction reference variables from the query string variable list
		if(!PaymentFormHelper::getTransactionReferenceFromQueryString($aQueryStringVariables, $szCrossReference, $szOrderID, $szHashDigest, $szOutputMessage))
		{
			$boErrorOccurred = true;
			$szValidateErrorMessage = $szOutputMessage;
		}
		else
		{
			// now need to validate the hash digest
			$szStringToHash = PaymentFormHelper::generateStringToHash3($szMerchantID, $szPassword, $szCrossReference, $szOrderID, $szPreSharedKey, $szHashMethod);
			$szCalculatedHashDigest = PaymentFormHelper::calculateHashDigest($szStringToHash, $szPreSharedKey, $szHashMethod);
			
			// does the calculated hash match the one that was passed?
			if(strToUpper($szHashDigest) != strToUpper($szCalculatedHashDigest))
			{
				$boErrorOccurred = true;
				$szValidateErrorMessage = "Hash digests don't match - possible variable tampering";
			}
			else
			{			
				// use the cross reference and/or the order ID to pull the
				// transaction results out of storage
				if(!PaymentFormHelper::getTransactionResultFromPaymentFormHandler(
					$szPaymentFormResultHandlerURL,
					$szMerchantID,
					$szPassword,
					$szCrossReference,
					$trTransactionResult,
					$szOutputMessage))
				{
					$szValidateErrorMessage = "Error querying transaction result [" . $szCrossReference . "] from [" . $szPaymentFormResultHandlerURL . "]: " . $szOutputMessage;
					$boErrorOccurred = true;
				}
				else
				{
					$boErrorOccurred = false;
				}
			}
		}

		return (!$boErrorOccurred);
	}
	public static function calculateHashDigest($szInputString, $szPreSharedKey, $szHashMethod)
	{
		switch($szHashMethod)
		{
			case "MD5":
				$hashDigest = md5($szInputString);
				break;
			case "SHA1":
				$hashDigest = sha1($szInputString);
				break;
			case "HMACMD5":
				$hashDigest = hash_hmac("md5", $szInputString, $szPreSharedKey);
				break;
			case "HMACSHA1":
				$hashDigest = hash_hmac("sha1", $szInputString, $szPreSharedKey);
				break;
		}
		return ($hashDigest);
	}
	public static function generateStringToHash(
		$szMerchantID,
		$szPassword,
		$szAmount,
		$szCurrencyCode,
		$szOrderID,
		$szTransactionType,
		$szTransactionDateTime,
		$szCallbackURL,
		$szOrderDescription,
		$szCustomerName,
		$szAddress1,
		$szAddress2,
		$szAddress3,
		$szAddress4,
		$szCity,
		$szState,
		$szPostCode,
		$szCountryCode,
		$szCV2Mandatory,
		$szAddress1Mandatory,
		$szCityMandatory,
		$szPostCodeMandatory,
		$szStateMandatory,
		$szCountryMandatory,
		$szResultDeliveryMethod,
		$szServerResultURL,
		$szPaymentFormDisplaysResult,
		$szPreSharedKey,
		$szHashMethod,
		$szEmailAddress,
		$szPhoneNumber)
	{
		$szReturnString = "";
		switch($szHashMethod)
		{
			case "MD5":
				$boIncludePreSharedKeyInString = true;
				break;
			case "SHA1":
				$boIncludePreSharedKeyInString = true;
				break;
			case "HMACMD5":
				$boIncludePreSharedKeyInString = false;
				break;
			case "HMACSHA1":
				$boIncludePreSharedKeyInString = false;
				break;
		}
		if($boIncludePreSharedKeyInString)
		{
			$szReturnString = "PreSharedKey=" . $szPreSharedKey . "&";
		}
		$szReturnString .=
			"MerchantID=" .
			$szMerchantID .
			"&Password=" .
			$szPassword .
			"&Amount=" .
			$szAmount .
			"&CurrencyCode=" .
			$szCurrencyCode .
			"&EchoAVSCheckResult=true" .
			"&EchoCV2CheckResult=true" .
			"&EchoThreeDSecureAuthenticationCheckResult=true" .
			"&EchoCardType=true" .
			"&EchoCardNumberFirstSix=true" .
			"&EchoCardNumberLastFour=true" .
			"&OrderID=" .
			$szOrderID .
			"&TransactionType=" .
			$szTransactionType .
			"&TransactionDateTime=" .
			$szTransactionDateTime .
			"&CallbackURL=" .
			$szCallbackURL .
			"&OrderDescription=" .
			$szOrderDescription .
			"&CustomerName=" .
			$szCustomerName .
			"&Address1=" .
			$szAddress1 .
			"&Address2=" .
			$szAddress2 .
			"&Address3=" .
			$szAddress3 .
			"&Address4=" .
			$szAddress4 .
			"&City=" .
			$szCity .
			"&State=" .
			$szState .
			"&PostCode=" .
			$szPostCode .
			"&CountryCode=" .
			$szCountryCode .
			"&EmailAddress=" .
			$szEmailAddress .
			"&PhoneNumber=" .
			$szPhoneNumber .
			"&CV2Mandatory=" .
			$szCV2Mandatory .
			"&Address1Mandatory=" .
			$szAddress1Mandatory .
			"&CityMandatory=" .
			$szCityMandatory .
			"&PostCodeMandatory=" .
			$szPostCodeMandatory .
			"&StateMandatory=" .
			$szStateMandatory .
			"&CountryMandatory=" .
			$szCountryMandatory .
			"&ResultDeliveryMethod=" .
			$szResultDeliveryMethod .
			"&ServerResultURL=" .
			$szServerResultURL .
			"&PaymentFormDisplaysResult=" .
			$szPaymentFormDisplaysResult .
			"&ServerResultURLCookieVariables=" .
			"&ServerResultURLFormVariables=" .
			"&ServerResultURLQueryStringVariables=";

		return ($szReturnString);
	}
    public static function generateStringToHash2(
		$szMerchantID,
		$szPassword,
		TransactionResult $trTransactionResult,
		$szPreSharedKey,
		$szHashMethod)
	{
		$szReturnString = "";
		switch($szHashMethod)
		{
			case "MD5":
				$boIncludePreSharedKeyInString = true;
				break;
			case "SHA1":
				$boIncludePreSharedKeyInString = true;
				break;
			case "HMACMD5":
				$boIncludePreSharedKeyInString = false;
				break;
			case "HMACSHA1":
				$boIncludePreSharedKeyInString = false;
				break;
		}
		if($boIncludePreSharedKeyInString)
		{
			$szReturnString = "PreSharedKey=" . $szPreSharedKey . "&";
		}
		$szReturnString =
			$szReturnString .
			"MerchantID=" .
			$szMerchantID .
			"&Password=" .
			$szPassword .
			"&StatusCode=" .
			$trTransactionResult->getStatusCode() .
			"&Message=" .
			$trTransactionResult->getMessage() .
			"&PreviousStatusCode=" .
			$trTransactionResult->getPreviousStatusCode() .
			"&PreviousMessage=" .
			$trTransactionResult->getPreviousMessage() .
			"&CrossReference=" .
			$trTransactionResult->getCrossReference();
		// include the option variables if they are present
		$addressNumericCheckResult = $trTransactionResult->getAddressNumericCheckResult();
		if(isset($addressNumericCheckResult))
		{
			$szReturnString .= "&AddressNumericCheckResult=" . $addressNumericCheckResult . "&PostCodeCheckResult=" . $trTransactionResult->getPostCodeCheckResult();
		}
		$CV2CheckResult = $trTransactionResult->getCV2CheckResult();
		if(isset($CV2CheckResult))
		{
			$szReturnString .= "&CV2CheckResult=" . $CV2CheckResult;
		}
		$threeDSecureAuthenticationCheckResult = $trTransactionResult->getThreeDSecureAuthenticationCheckResult();
		if(isset($threeDSecureAuthenticationCheckResult))
		{
			$szReturnString .= "&ThreeDSecureAuthenticationCheckResult=" . $threeDSecureAuthenticationCheckResult;
		}
		$cardType = $trTransactionResult->getCardType();
		if(isset($cardType))
		{
			$szReturnString .=
				"&CardType=" .
				$cardType .
				"&CardClass=" .
				$trTransactionResult->getCardClass() .
				"&CardIssuer=" .
				$trTransactionResult->getCardIssuer() .
				"&CardIssuerCountryCode=" .
				$trTransactionResult->getCardIssuerCountryCode();
		}
		$szReturnString .=
			"&CardNumberFirstSix=" . $trTransactionResult->getCardNumberFirstSix() .
			"&CardNumberLastFour=" . $trTransactionResult->getCardNumberLastFour() .
			"&Amount=" .
			$trTransactionResult->getAmount() .
			"&CurrencyCode=" .
			$trTransactionResult->getCurrencyCode() .
			"&OrderID=" .
			$trTransactionResult->getOrderID() .
			"&TransactionType=" .
			$trTransactionResult->getTransactionType() .
			"&TransactionDateTime=" .
			$trTransactionResult->getTransactionDateTime() .
			"&OrderDescription=" .
			$trTransactionResult->getOrderDescription() .
			"&CustomerName=" .
			$trTransactionResult->getCustomerName() .
			"&Address1=" .
			$trTransactionResult->getAddress1() .
			"&Address2=" .
			$trTransactionResult->getAddress2() .
			"&Address3=" .
			$trTransactionResult->getAddress3() .
			"&Address4=" .
			$trTransactionResult->getAddress4() .
			"&City=" .
			$trTransactionResult->getCity() .
			"&State=" .
			$trTransactionResult->getState() .
			"&PostCode=" .
			$trTransactionResult->getPostCode() .
			"&CountryCode=" .
			$trTransactionResult->getCountryCode();
		$emailAddress = $trTransactionResult->getEmailAddress();
		if(isset($emailAddress))
		{
			$szReturnString .= "&EmailAddress=" . $emailAddress;
		}
		$phoneNumber = $trTransactionResult->getPhoneNumber();
		if(isset($phoneNumber))
		{
			$szReturnString .= "&PhoneNumber=" . $phoneNumber;
		}

		return ($szReturnString);
	}
	public static function generateStringToHash3(
		$szMerchantID,
		$szPassword,
		$szCrossReference,
		$szOrderID,
		$szPreSharedKey,
		$szHashMethod)
	{
		$szReturnString = "";
		switch($szHashMethod)
		{
			case "MD5":
				$boIncludePreSharedKeyInString = true;
				break;
			case "SHA1":
				$boIncludePreSharedKeyInString = true;
				break;
			case "HMACMD5":
				$boIncludePreSharedKeyInString = false;
				break;
			case "HMACSHA1":
				$boIncludePreSharedKeyInString = false;
				break;
		}
		if($boIncludePreSharedKeyInString)
		{
			$szReturnString = "PreSharedKey=" . $szPreSharedKey . "&";
		}
		$szReturnString = $szReturnString . "MerchantID=" . $szMerchantID . "&Password=" . $szPassword . "&CrossReference=" . $szCrossReference . "&OrderID=" . $szOrderID;

		return ($szReturnString);
	}
	//Transparent Redirect Methods
	public static function generateStringToHashTransparent(
		$szMerchantID,
		$szPassword,
		$szAmount,
		$szCurrencyCode,
		$szOrderID,
		$szTransactionType,
		$szTransactionDateTime,
		$szCallbackURL,
		$szOrderDescription,
		$szCustomerName,
		$szAddress1,
		$szAddress2,
		$szAddress3,
		$szAddress4,
		$szCity,
		$szState,
		$szPostCode,
		$szCountryCode,
		$szSecretKey)
	{
		$szReturnString = "";
		$szReturnString = "MerchantID=" . $szMerchantID .
			"&Password=" . $szPassword .
			"&Amount=" . $szAmount .
			"&CurrencyCode=" . $szCurrencyCode .
			"&OrderID=" . $szOrderID .
			"&TransactionType=" . $szTransactionType .
			"&TransactionDateTime=" . $szTransactionDateTime .
			"&CallbackURL=" . $szCallbackURL .
			"&OrderDescription=" . $szOrderDescription .
			"&CustomerName=" . $szCustomerName .
			"&Address1=" . $szAddress1 .
			"&Address2=" . $szAddress2 .
			"&Address3=" . $szAddress3 .
			"&Address4=" . $szAddress4 .
			"&City=" . $szCity .
			"&State=" . $szState .
			"&PostCode=" . $szPostCode .
			"&CountryCode=" . $szCountryCode .
			"&SecretKey=" . $szSecretKey;

		return ($szReturnString);
}
	public static function generateStringToHashInitial(
		$szHashMethod,
		$szPreSharedKey,
		$szMerchantID,
		$szPassword,
		$szAmount,
		$szCurrencyCode,
		$szOrderID,
		$szTransactionType,
		$szTransactionDateTime,
		$szCallbackURL,
		$szOrderDescription)
	{
		$szReturnString = "";
		$boIncludePreSharedKeyInString = false;
		switch($szHashMethod)
		{
			case "MD5":
				$boIncludePreSharedKeyInString = true;
				break;
			case "SHA1":
				$boIncludePreSharedKeyInString = true;
				break;
			case "HMACMD5":
				$boIncludePreSharedKeyInString = false;
				break;
			case "HMACSHA1":
				$boIncludePreSharedKeyInString = false;
				break;
		}
		if(boIncludePreSharedKeyInString)
		{
			$szReturnString = "PreSharedKey=" . $szPreSharedKey . "&";
		}
		$szReturnString .=
			"MerchantID=" . $szMerchantID .
			"&Password=" . $szPassword .
			"&Amount=" . $szAmount .
			"&CurrencyCode=" . $szCurrencyCode .
			"&EchoAVSCheckResult=true" .
			"&EchoCV2CheckResult=true" .
			"&EchoThreeDSecureAuthenticationCheckResult=true" .
			"&EchoCardType=true" .
			"&EchoCardNumberFirstSix=true" .
			"&EchoCardNumberLastFour=true" .
			"&OrderID=" . $szOrderID .
			"&TransactionType=" . $szTransactionType .
			"&TransactionDateTime=" . $szTransactionDateTime .
			"&CallbackURL=" . $szCallbackURL .
			"&OrderDescription=" . $szOrderDescription;

		return $szReturnString;
	}
	public static function generateStringToHash3DSecureAuthenticationRequired($trTransactionResult, $szHashMethod, $szPreSharedKey, $szMerchantID, $szPassword)
	{
		$szReturnString = "";
		$boIncludePreSharedKeyInString = false;
		switch($szHashMethod)
		{
			case "MD5":
				$boIncludePreSharedKeyInString = true;
				break;
			case "SHA1":
				$boIncludePreSharedKeyInString = true;
				break;
			case "HMACMD5":
				$boIncludePreSharedKeyInString = false;
				break;
			case "HMACSHA1":
				$boIncludePreSharedKeyInString = false;
				break;
		}
		if(boIncludePreSharedKeyInString)
		{
			$szReturnString = "PreSharedKey=" . $szPreSharedKey . "&";
		}
		$szReturnString .=
			"MerchantID=" .
			$szMerchantID .
			"&Password=" .
			$szPassword .
			"&StatusCode=" .
			$trTransactionResult ->getStatusCode() .
			"&Message=" .
			$trTransactionResult ->getMessage() .
			"&CrossReference=" .
			$trTransactionResult ->getCrossReference() .
			"&OrderID=" .
			$trTransactionResult ->getOrderID() .
			"&TransactionDateTime=" .
			$trTransactionResult ->getTransactionDateTime() .
			"&ACSURL=" .
			$trTransactionResult ->getACSURL() .
			"&PaREQ=" .
			$trTransactionResult ->getPaREQ();

		return $szReturnString;
	}
	public static function generateStringToHash3DSecurePostAuthentication(
		$szHashMethod,
		$szPreSharedKey,
		$szMerchantID,
		$szPassword,
		$szCrossReference,
		$szTransactionDateTime,
		$szCallbackURL,
		$szPaRES)
	{
		$szReturnString = "";
		$boIncludePreSharedKeyInString = false;
		switch($szHashMethod)
		{
			case "MD5":
				$boIncludePreSharedKeyInString = true;
				break;
			case "SHA1":
				$boIncludePreSharedKeyInString = true;
				break;
			case "HMACMD5":
				$boIncludePreSharedKeyInString = false;
				break;
			case "HMACSHA1":
				$boIncludePreSharedKeyInString = false;
				break;
		}
		if(boIncludePreSharedKeyInString)
		{
			$szReturnString = "PreSharedKey=" . $szPreSharedKey . "&";
		}
		$szReturnString .=
			"MerchantID=" .
			$szMerchantID .
			"&Password=" .
			$szPassword .
			"&CrossReference=" .
			$szCrossReference .
			"&TransactionDateTime=" .
			$szTransactionDateTime .
			"&CallbackURL=" .
			$szCallbackURL .
			"&PaRES=" .
			$szPaRES;

		return $szReturnString;
	}
	public static function generateStringToHashPaymentComplete($trTransactionResult, $szHashMethod, $szPreSharedKey, $szMerchantID, $szPassword)
	{
		$szReturnString = null;
		$boIncludePreSharedKeyInString = false;
		switch($szHashMethod)
		{
			case "MD5":
				$boIncludePreSharedKeyInString = true;
				break;
			case "SHA1":
				$boIncludePreSharedKeyInString = true;
				break;
			case "HMACMD5":
				$boIncludePreSharedKeyInString = false;
				break;
			case "HMACSHA1":
				$boIncludePreSharedKeyInString = false;
				break;
		}
		if(boIncludePreSharedKeyInString)
		{
			$szReturnString = "PreSharedKey=" . $szPreSharedKey . "&";
		}
		$szReturnString .=
			"MerchantID=" .
			$szMerchantID .
			"&Password=" .
			$szPassword .
			"&StatusCode=" .
			$trTransactionResult ->getStatusCode() .
			"&Message=" .
			$trTransactionResult ->getMessage() .
			"&PreviousStatusCode=" .
			$trTransactionResult ->getPreviousStatusCode() .
			"&PreviousMessage=" .
			$trTransactionResult ->getPreviousMessage() .
			"&CrossReference=" .
			$trTransactionResult ->getCrossReference();

		// include the option variables if they are present
		$addressNumericCheckResult = $trTransactionResult->getAddressNumericCheckResult();
		if(isset($addressNumericCheckResult))
		{
			$szReturnString .= "&AddressNumericCheckResult=" . $addressNumericCheckResult . "&PostCodeCheckResult=" . $trTransactionResult->getPostCodeCheckResult();
		}
		$cv2CheckResult = $trTransactionResult->getCV2CheckResult();
		if(isset($cv2CheckResult))
		{
			$szReturnString .= "&CV2CheckResult=" . $trTransactionResult ->getCV2CheckResult();
		}
		$threeDSecureAuthenticationCheckResult = $trTransactionResult->getThreeDSecureAuthenticationCheckResult();
		if(isset($threeDSecureAuthenticationCheckResult))
		{
			$szReturnString .= "&ThreeDSecureAuthenticationCheckResult=" . $threeDSecureAuthenticationCheckResult;
		}
		$fraudProtectionCheckResult = $trTransactionResult->getFraudProtectionCheckResult();
		if(isset($fraudProtectionCheckResult))
		{
			$szReturnString .= "&FraudProtectionCheckResult=" . $fraudProtectionCheckResult;
		}
		$cardType = $trTransactionResult->getCardType();
		if(isset($cardType))
		{
			$szReturnString .=
				"&CardType=" .
				$cardType .
				"&CardClass=" .
				$trTransactionResult->getCardClass() .
				"&CardIssuer=" .
				$trTransactionResult->getCardIssuer() .
				"&CardIssuerCountryCode=" .
				$trTransactionResult->getCardIssuerCountryCode();
		}
		$cardNumberFirstSix = $trTransactionResult->getCardNumberFirstSix();
		if(isset($cardNumberFirstSix))
		{
			$szReturnString .= "&CardNumberFirstSix=" . $cardNumberFirstSix;
		}
		$cardNumberLastFour = $trTransactionResult->getCardNumberLastFour();
		if(isset($cardNumberLastFour))
		{
			$szReturnString .= "&CardNumberLastFour=" . $cardNumberLastFour;
		}
		$cardExpiryDate = $trTransactionResult->getCardExpiryDate();
		if(isset($cardExpiryDate))
		{
			$szReturnString .= "&CardExpiryDate=" . $cardExpiryDate;
		}

		$szReturnString .= "&Amount=" .
			$trTransactionResult ->getAmount() .
			"&CurrencyCode=" .
			$trTransactionResult ->getCurrencyCode() .
			"&OrderID=" .
			$trTransactionResult ->getOrderID() .
			"&TransactionType=" .
			$trTransactionResult ->getTransactionType() .
			"&TransactionDateTime=" .
			$trTransactionResult ->getTransactionDateTime() .
			"&OrderDescription=" .
			$trTransactionResult ->getOrderDescription() .
			"&Address1=" .
			$trTransactionResult ->getAddress1() .
			"&Address2=" .
			$trTransactionResult ->getAddress2() .
			"&Address3=" .
			$trTransactionResult ->getAddress3() .
			"&Address4=" .
			$trTransactionResult ->getAddress4() .
			"&City=" .
			$trTransactionResult ->getCity() .
			"&State=" .
			$trTransactionResult ->getState() .
			"&PostCode=" .
			$trTransactionResult ->getPostCode() .
			"&CountryCode=" .
			$trTransactionResult ->getCountryCode() .
			"&EmailAddress=" .
			$trTransactionResult ->getEmailAddress() .
			"&PhoneNumber=" .
			$trTransactionResult ->getPhoneNumber();

		return ($szReturnString);
	}
	private static function get3DSecureAuthenticationRequiredFromPostVariables($aVariables, &$trTransactionResult)
	{
		$szHashDigest = "";
		$szErrorMessage = "";
		$boErrorActive = false;
		try
		{
			$trTransactionResult = new TransactionResult();
			// hash digest
			if(isset($aVariables["HashDigest"]))
			{
				$szHashDigest = $aVariables["HashDigest"];
			}
			// transaction status code
			if(!isset($aVariables["StatusCode"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [StatusCode] not received");
				$boErrorActive = true;
			}
			else
			{
				if($aVariables["StatusCode"] == "")
				{
					$trTransactionResult ->setStatusCode(null);
				}
				else
				{
					$trTransactionResult ->setStatusCode(intval($aVariables["StatusCode"]));
				}
			}
			// transaction message
			if(!isset($aVariables["Message"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [Message] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setMessage($aVariables["Message"]);
			}
			// cross reference of transaction
			if(!isset($aVariables["CrossReference"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [CrossReference] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setCrossReference($aVariables["CrossReference"]);
			}
			// currency code (same as value passed into payment form - echoed back out by payment form)
			// order ID (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aVariables["OrderID"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [OrderID] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setOrderID($aVariables["OrderID"]);
			}
			// transaction date/time (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aVariables["TransactionDateTime"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [TransactionDateTime] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setTransactionDateTime($aVariables["TransactionDateTime"]);
			}
			// order description (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aVariables["ACSURL"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [ACSURL] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setACSURL($aVariables["ACSURL"]);
			}
			// address1 (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aVariables["PaREQ"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [PaREQ] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setPaREQ($aVariables["PaREQ"]);
			}
		}
		catch(Exception $e)
		{
			$boErrorActive = true;
			$szErrorMessage = $e ->getMessage();
		}

		return (!$boErrorActive);
	}
	private static function get3DSecurePostAuthenticationFromPostVariables($aVariables, &$trTransactionResult)
	{
		//$this->trTransactionResult = null;
		$szErrorMessage = "";
		$boErrorActive = false;
		try
		{
			$trTransactionResult = new TransactionResult();
			// cross reference of transaction
			if(!isset($aVariables["MD"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [MD] not received");
				$boErrorActive = true;
			}
			else
			{
				//$trTransactionResult -> setCrossReference($aVariables["MD"]);
				$szCrossReference = $aVariables["MD"];
			}
			if(!isset($aVariables["PaRes"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [PaRes] not received");
				$boErrorActive = true;
			}
			else
			{
				//$trTransactionResult -> setPaRES($aVariables["PaRes"]);
				$szPaRES = $aVariables["PaRes"];
			}
		}
		catch(Exception $e)
		{
			$boErrorActive = true;
			$szErrorMessage = $e ->getMessage();
		}

		return (!$boErrorActive);
	}
	private static function getTransactionCompleteResultFromPostVariables($aVariables, &$trTransactionResult)
	{
		//$this->trTransactionResult = null;
		$szErrorMessage = "";
		$boErrorActive = false;
		try
		{
			$trTransactionResult = new TransactionResult();
			// hash digest
			if(isset($aVariables["HashDigest"]))
			{
				$szHashDigest = $aVariables["HashDigest"];
			}
			// transaction status code
			if(!isset($aVariables["StatusCode"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [StatusCode] not received");
				$boErrorActive = true;
			}
			else
			{
				if($aVariables["StatusCode"] == "")
				{
					$trTransactionResult ->setStatusCode(null);
				}
				else
				{
					$trTransactionResult ->setStatusCode(intval($aVariables["StatusCode"]));
				}
			}
			// transaction message
			if(!isset($aVariables["Message"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [Message] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setMessage($aVariables["Message"]);
			}
			// status code of original transaction if this transaction was deemed a duplicate
			if(!isset($aVariables["PreviousStatusCode"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [PreviousStatusCode] not received");
				$boErrorActive = true;
			}
			else
			{
				if($aVariables["PreviousStatusCode"] == "")
				{
					$trTransactionResult ->setPreviousStatusCode(null);
				}
				else
				{
					$trTransactionResult ->setPreviousStatusCode(intval($aVariables["PreviousStatusCode"]));
				}
			}
			// status code of original transaction if this transaction was deemed a duplicate
			if(!isset($aVariables["PreviousMessage"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [PreviousMessage] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setPreviousMessage($aVariables["PreviousMessage"]);
			}
			// cross reference of transaction
			if(!isset($aVariables["CrossReference"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [CrossReference] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setCrossReference($aVariables["CrossReference"]);
			}
			if(isset($aVariables["AddressNumericCheckResult"]))
			{
				if($aVariables["AddressNumericCheckResult"] == "")
				{
					$trTransactionResult ->setAddressNumericCheckResult(null);
				}
				else
				{
					$trTransactionResult ->setAddressNumericCheckResult($aVariables["AddressNumericCheckResult"]);
				}
			}
			if(isset($aVariables["PostCodeCheckResult"]))
			{
				if($aVariables["PostCodeCheckResult"] == "")
				{
					$trTransactionResult ->setPostCodeCheckResult(null);
				}
				else
				{
					$trTransactionResult ->setPostCodeCheckResult($aVariables["PostCodeCheckResult"]);
				}
			}
			if(isset($aVariables["CV2CheckResult"]))
			{
				if($aVariables["CV2CheckResult"] == "")
				{
					$trTransactionResult ->setCV2CheckResult(null);
				}
				else
				{
					$trTransactionResult ->setCV2CheckResult($aVariables["CV2CheckResult"]);
				}
			}
			if(isset($aVariables["ThreeDSecureAuthenticationCheckResult"]))
			{
				if($aVariables["ThreeDSecureAuthenticationCheckResult"] == "")
				{
					$trTransactionResult ->setThreeDSecureAuthenticationCheckResult(null);
				}
				else
				{
					$trTransactionResult ->setThreeDSecureAuthenticationCheckResult($aVariables["ThreeDSecureAuthenticationCheckResult"]);
				}
			}
			if(isset($aVariables["FraudProtectionCheckResult"]))
			{
				if($aVariables["FraudProtectionCheckResult"] == "")
				{
					$trTransactionResult ->setFraudProtectionCheckResult(null);
				}
				else
				{
					$trTransactionResult ->setFraudProtectionCheckResult($aVariables["ThreeDSecureAuthenticationCheckResult"]);
				}
			}
			if(isset($aVariables["CardType"]))
			{
				if($aVariables["CardType"] == "")
				{
					$trTransactionResult ->setCardType(null);
				}
				else
				{
					$trTransactionResult ->setCardType($aVariables["CardType"]);
				}
			}
			if(isset($aVariables["CardClass"]))
			{
				if($aVariables["CardClass"] == "")
				{
					$trTransactionResult ->setCardClass(null);
				}
				else
				{
					$trTransactionResult ->setCardClass($aVariables["CardClass"]);
				}
			}
			if(isset($aVariables["CardIssuer"]))
			{
				if($aVariables["CardIssuer"] == "")
				{
					$trTransactionResult ->setCardIssuer(null);
				}
				else
				{
					$trTransactionResult ->setCardIssuer($aVariables["CardIssuer"]);
				}
			}
			if(isset($aVariables["CardIssuerCountryCode"]))
			{
				if($aVariables["CardIssuerCountryCode"] == "")
				{
					$trTransactionResult ->setCardIssuerCountryCode(null);
				}
				else
				{
					$trTransactionResult ->setCardIssuerCountryCode(intval($aVariables["CardIssuerCountryCode"]));
				}
			}
			if(isset($aVariables["CardNumberFirstSix"]))
			{
				if($aVariables["CardNumberFirstSix"] == "")
				{
					$trTransactionResult->setCardNumberFirstSix(null);
				}
				else
				{
					$trTransactionResult->setCardNumberFirstSix($aVariables["CardNumberFirstSix"]);
				}
			}
			if(isset($aVariables["CardNumberLastFour"]))
			{
				if($aVariables["CardNumberLastFour"] == "")
				{
					$trTransactionResult->setCardNumberLastFour(null);
				}
				else
				{
					$trTransactionResult->setCardNumberLastFour($aVariables["CardNumberLastFour"]);
				}
			}
			if(isset($aVariables["CardExpiryDate"]))
			{
				if($aVariables["CardExpiryDate"] == "")
				{
					$trTransactionResult->setCardExpiryDate(null);
				}
				else
				{
					$trTransactionResult->setCardExpiryDate($aVariables["CardExpiryDate"]);
				}
			}
			// amount (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aVariables["Amount"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [Amount] not received");
				$boErrorActive = true;
			}
			else
			{
				if($aVariables["Amount"] == "")
				{
					$trTransactionResult ->setAmount(null);
				}
				else
				{
					$trTransactionResult ->setAmount($aVariables["Amount"]);
				}
			}
			if(!isset($aVariables["CurrencyCode"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [CurrencyCode] not received");
				$boErrorActive = true;
			}
			else
			{
				if($aVariables["CurrencyCode"] == "")
				{
					$trTransactionResult ->setCurrencyCode(null);
				}
				else
				{
					$trTransactionResult ->setCurrencyCode($aVariables["CurrencyCode"]);
				}
			}
			// currency code (same as value passed into payment form - echoed back out by payment form)
			// order ID (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aVariables["OrderID"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [OrderID] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setOrderID($aVariables["OrderID"]);
			}
			// transaction type (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aVariables["TransactionType"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [TransactionType] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setTransactionType($aVariables["TransactionType"]);
			}
			// transaction date/time (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aVariables["TransactionDateTime"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [TransactionDateTime] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setTransactionDateTime($aVariables["TransactionDateTime"]);
			}
			// order description (same as value passed into payment form - echoed back out by payment form)
			if(!isset($aVariables["OrderDescription"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [OrderDescription] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setOrderDescription($aVariables["OrderDescription"]);
			}
			// address1 (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aVariables["Address1"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [Address1] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setAddress1($aVariables["Address1"]);
			}
			// address2 (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aVariables["Address2"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [Address2] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setAddress2($aVariables["Address2"]);
			}
			// address3 (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aVariables["Address3"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [Address3] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setAddress3($aVariables["Address3"]);
			}
			// address4 (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aVariables["Address4"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [Address4] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setAddress4($aVariables["Address4"]);
			}
			// city (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aVariables["City"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [City] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setCity($aVariables["City"]);
			}
			// state (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aVariables["State"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [State] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setState($aVariables["State"]);
			}
			// post code (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aVariables["PostCode"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [PostCode] not received");
				$boErrorActive = true;
			}
			else
			{
				$trTransactionResult ->setPostCode($aVariables["PostCode"]);
			}
			// country code (not necessarily the same as value passed into payment form - as the customer can change it on the form)
			if(!isset($aVariables["CountryCode"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [CountryCode]mail not received");
				$boErrorActive = true;
			}
			else
			{
				if($aVariables["CountryCode"] == "")
				{
					$trTransactionResult ->setCountryCode(null);
				}
				else
				{
					$trTransactionResult ->setCountryCode($aVariables["CountryCode"]);
				}
			}
			if(!isset($aVariables["EmailAddress"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [EmailAddress] not received");
				$boErrorActive = true;
			}
			else
			{
				if($aVariables["EmailAddress"] == "")
				{
					$trTransactionResult ->setEmailAddress(null);
				}
				else
				{
					$trTransactionResult ->setEmailAddress($aVariables["EmailAddress"]);
				}
			}
			if(!isset($aVariables["PhoneNumber"]))
			{
				$szErrorMessage = PaymentFormHelper::addStringToStringList($szErrorMessage, "Expected variable [PhoneNumber] not received");
				$boErrorActive = true;
			}
			else
			{
				if($aVariables["PhoneNumber"] == "")
				{
					$trTransactionResult ->setPhoneNumber(null);
				}
				else
				{
					$trTransactionResult ->setPhoneNumber($aVariables["PhoneNumber"]);
				}
			}
		}
		catch(Exception $e)
		{
			$boErrorActive = true;
			$szErrorMessage = $e ->getMessage();
		}

		return (!$boErrorActive);
	}
}
