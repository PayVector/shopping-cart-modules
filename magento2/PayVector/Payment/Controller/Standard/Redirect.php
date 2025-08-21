<?php

namespace PayVector\Payment\Controller\Standard;


class Redirect extends \PayVector\Payment\Controller\PayVectorAbstract {		
    public function execute() {		
        $this->getPaymentMethod()->getPayVectorUrl();        
    }

}
