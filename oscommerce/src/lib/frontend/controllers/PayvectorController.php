<?php
namespace frontend\controllers;

use Yii;

require_once(Yii::getAlias('@common') . '/modules/orderPayment/lib/payvector/TransactionProcessor.php');

class PayvectorController extends Sceleton {

    public $enableCsrfValidation = false;

    public function beforeAction($action) {        
        $this->enableCsrfValidation = false;
        \Yii::$app->request->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionThreeDs() {
        global $cart;
        $postData = \Yii::$app->request->post();
        

        if (!empty($postData) && (isset($postData['cres']) || isset($postData['threeDSMethodData']) || isset($postData['PaRes']) || isset($postData['MD']))) {
            
            $payment_module = $this->getPaymentModule();
            if (!$payment_module) {
                return $this->redirect(['checkout/index', 'payment_error' => 'payvector', 'paytype' => 'new','error' => 'Payment module not found']);
            }

            $processor = new \TransactionProcessor();
            $processor->setMerchantID($payment_module->mid);
            $processor->setMerchantPassword($payment_module->pass);
            $processor->setRgeplRequestGatewayEntryPointList($payment_module->getEntryPointList());

            $threeDSMethodData = $postData['threeDSMethodData'] ?? '';
            $cres = $postData['cres'] ?? '';
            $threeDSSessionData = $postData['threeDSSessionData'] ?? '';
            
            $crossReference = $_SESSION['payvector_md'] ?? '';
            $order_id = \Yii::$app->request->get('order_id');
            if (empty($order_id)) {
                $order_id = $_SESSION['payvector_order_id'] ?? null;
            }
            
            if (empty($order_id)) {
                return $this->redirect(['checkout/index', 'payment_error' => 'payvector', 'paytype' => 'new','error' => 'Session expired during 3DS verification']);
            }
            $order = new \common\classes\Order($order_id);

            if (!empty($cres)) {
                // 3DS v2 Step 3/4: ACS Challenge Response (CRes) received
                
                $finalCrossReference = \PaymentFormHelper::base64UrlDecode($threeDSSessionData);
                $finalCrossReference = $finalCrossReference ? $finalCrossReference : $crossReference; //Fallback if decode fails or empty

                $tdsa = new \net\thepaymentgateway\paymentsystem\ThreeDSecureAuthentication($payment_module->getEntryPointList());
                $tdsa->getMerchantAuthentication()->setMerchantID($payment_module->mid);
                $tdsa->getMerchantAuthentication()->setPassword($payment_module->pass);
                $tdsa->getThreeDSecureInputData()->setCrossReference($finalCrossReference);
                $tdsa->getThreeDSecureInputData()->setCRES($cres);
                
                $authenticationResult = null;
                $outputData = null;
                $boProcessed = $tdsa->processTransaction($authenticationResult, $outputData);
                
                $finalResult = new \ThreeDSecureFinalTransactionResult($boProcessed, $tdsa, $authenticationResult, $outputData, $_SESSION);
                $payment_module->_handleTransactionResult($finalResult, $processor, $order, 'new');
                
                
                if (is_object($cart)) {
                    $cart->reset(true);
                }

                return $this->render('three-ds.tpl', [
                    'acs_url' => \Yii::$app->urlManager->createAbsoluteUrl(['checkout/success']),
                    'target' => '_top',
                    'form_params' => []
                ]);
            } elseif (!empty($threeDSMethodData)) {                
                $tdse = new \net\thepaymentgateway\paymentsystem\ThreeDSecureEnvironment($payment_module->getEntryPointList());
                $tdse->getMerchantAuthentication()->setMerchantID($payment_module->mid);
                $tdse->getMerchantAuthentication()->setPassword($payment_module->pass);
                $tdse->getThreeDSecureEnvironmentData()->setCrossReference($crossReference);
                $tdse->getThreeDSecureEnvironmentData()->setMethodData($threeDSMethodData);
                
                $authenticationResult = null;
                $outputData = null;
                $boProcessed = $tdse->processTransaction($authenticationResult, $outputData);
                
                if ($authenticationResult->getStatusCode() === 3) {
                    $creq = $outputData->getThreeDSecureOutputData()->getCREQ();
                    $sessionData = \PaymentFormHelper::base64UrlEncode($outputData->getCrossReference());
                    
                    return $this->render('three-ds.tpl', [
                        'acs_url' => $outputData->getThreeDSecureOutputData()->getACSURL(),
                        'target' => 'threeDSecureFrame',
                        'form_params' => [
                            'creq' => $creq,
                            'threeDSSessionData' => $sessionData
                        ]
                    ]);
                } else {
                    $finalResult = new \ThreeDSecureFinalTransactionResult($boProcessed, $tdse, $authenticationResult, $outputData, $_SESSION);
                    $payment_module->_handleTransactionResult($finalResult, $processor, $order, 'new');
                    
                    if (is_object($cart)) {
                        $cart->reset(true);
                    }
                    return $this->redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/success']));
                }
            } else {
                
                $md = $postData['MD'] ?? $crossReference;
                $paRes = $postData['PaRes'] ?? '';
                
                $result = $processor->check3DSecureResult($md, $paRes, $_SESSION);
                $payment_module->_handleTransactionResult($result, $processor, $order, 'new');
                
                if (is_object($cart)) {
                    $cart->reset(true);
                }
                return $this->redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/success']));
            }
        }
        
        $session_data = \Yii::$app->session->get('payvector_3ds_data');
        
        if (empty($session_data) || empty($session_data['acs_url'])) {
             return $this->redirect(['checkout/index']); 
        }

        $acs_url = $session_data['acs_url'];        
        $params = $session_data['params'] ?? [];
        
        
        if (!isset($params['TermUrl']) && isset($session_data['term_url'])) {
            $params['TermUrl'] = $session_data['term_url'];
        }

        
        $target = $session_data['target'] ?? 'threeDSecureFrame';
        

        return $this->render('three-ds.tpl', [
            'acs_url' => $acs_url,
            'form_params' => $params,
            'target' => $target
        ]);
    }

    public function actionThreedsecureReturn() {
         $postData = $_POST;
         
         $order_id = $_GET['order_id'] ;         

         return $this->render('three-ds-return.tpl', [
            'form_url' => \Yii::$app->urlManager->createAbsoluteUrl(['payvector/three-ds', 'order_id' => $order_id]),
            'form_params' => $postData
         ]);
    }

    protected function getPaymentModule() {        
        $order_id = \Yii::$app->request->get('order_id');
        
        if (empty($order_id)) {
            $order_id = $_SESSION['payvector_order_id'] ?? null;
        }
        
        if (empty($order_id)) {
            return null;
        }

        $order = new \common\classes\Order($order_id);
        $payment_class = $order->info['payment_class'];
        
        if (empty($payment_class)) {
            $payment_class = 'payvector';
        }
        $manager = new \common\services\OrderManager(new \common\services\storages\DbStorage());
        $manager->getOrderInstanceWithId('\common\classes\Order', $order_id);
        $payment_modules = new \common\classes\payment($payment_class, $manager);
        
        if (isset($payment_modules->include_modules) && is_array($payment_modules->include_modules)) {
            foreach ($payment_modules->include_modules as $module) {
                if ($module->code === 'payvector') {
                    return $module;
                }
            }
        }
        
        return null;
    }
}