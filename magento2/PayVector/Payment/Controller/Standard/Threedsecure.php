<?php

namespace PayVector\Payment\Controller\Standard;

class Threedsecure extends \PayVector\Payment\Controller\PayVectorAbstract 
{
    

public function execute()
{        
    $postData = $this->getRequest()->getPostValue();
    $paymentMethod = $this->getPaymentMethod();
    $data = $paymentMethod->handleThreeDSResults($postData);
    $blockdata = $data['blockdata'];
    $template = $data['template'] ?? 'landingform';
    $template = $data['template'] ?? 'landingform';
    $resultPageFactory = $this->_objectManager->get(\Magento\Framework\View\Result\PageFactory::class);
    $page = $resultPageFactory->create();    
    $page->getConfig()->getTitle()->set(__('3D Secure'));
    $page->getConfig()->setPageLayout('1column'); 
    $layout = $page->getLayout();
    $block = $layout->createBlock(\Magento\Framework\View\Element\Template::class);
    
    if ($template == 'finalform') {
      $resultRedirect = $this->resultRedirectFactory->create();
      if ($blockdata['PayAction']) {
          $resultRedirect->setPath('checkout/onepage/success');
      } else {
        $this->_checkoutSession->restoreQuote();
        $resultRedirect->setPath('checkout/onepage/failure');          
      }      
      return $resultRedirect;

    }
    else if ($template == 'landingform') {
        $block->setTemplate('PayVector_Payment::threedsecurelandingform.phtml');    
        $block->setData('blockdata', $blockdata);
    } else {
        
        $block->setTemplate('PayVector_Payment::threedsecure.phtml');    
        $block->setData('blockdata', $blockdata);    
        $html = $block->toHtml();
        $this->getResponse()->setBody($html);
        return;
    }   
    
    $content = $layout->getBlock('page.main.title');
    if ($content) {
        $content->append($block);              
    }
    return $page;
}

}
