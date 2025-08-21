<?php

namespace PayVector\Payment\Model;

use \Magento\Framework\View\Asset\Repository;
use \Magento\Customer\Model\Session as CustomerSession;
use \Magento\Vault\Api\PaymentTokenRepositoryInterface;
use \Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{

    
    protected $methodCode = \PayVector\Payment\Model\Payment::PAYVECTOR_PAYMENT_CODE;
    
    
    protected $method;
    protected $assetRepo;    
    protected $customerSession; 
    protected $orderCollectionFactory;         	

    public function __construct(\Magento\Payment\Helper\Data $paymenthelper, Repository $assetRepo){
        $this->method = $paymenthelper->getMethodInstance($this->methodCode);
        $this->assetRepo = $assetRepo;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->customerSession = $objectManager->get(CustomerSession::class);                    
    }

   public function getConfig()
   {
        if (!$this->method->isAvailable()) {
            return [];
        }

        $config = [
            'payment' => [
                'payvector_payment' => [
                    'getPaymentSrc' => $this->getPaymentSrc(),
                    'mode' => $this->method->getPaymentMode(),
                ]
            ]
        ];
        
         if ($this->customerSession->isLoggedIn()) {            
            $config['payment']['payvector_payment']['tokens'] = $this->method->getLastOrderCardData();
        }

        return $config;
    }

    public function getPaymentSrc()
    {
        return $this->assetRepo->getUrl('PayVector_Payment::images/payvector.png');
    }    
}
