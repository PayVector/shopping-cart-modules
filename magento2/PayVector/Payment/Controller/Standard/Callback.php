<?php

namespace PayVector\Payment\Controller\Standard;

class Callback extends \PayVector\Payment\Controller\PayVectorAbstract {

    public function execute() {        		                    
      $paymentMethod = $this->getPaymentMethod();
      if ($paymentMethod->validateResponse()) $actPath = 'checkout/onepage/success';
      else {
        $this->_checkoutSession->restoreQuote();        
        $actPath = 'checkout/onepage/failure';
      }
      $resultRedirect = $this->resultRedirectFactory->create();
      $resultRedirect->setPath($actPath);
      return $resultRedirect;
      
   }
}
