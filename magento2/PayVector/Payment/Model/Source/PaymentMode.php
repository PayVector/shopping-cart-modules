<?php
namespace PayVector\Payment\Model\Source;
use Magento\Framework\Option\ArrayInterface;

class PaymentMode implements ArrayInterface
{
    const PAYMENT_MODE_DIRECT_API = 'direct';
    const PAYMENT_MODE_HOSTED_PAYMENT_FORM = 'hosted';
    const PAYMENT_MODE_TRANSPARENT_REDIRECT = 'transparent';

    /**
     * Return payment integration method options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::PAYMENT_MODE_DIRECT_API, 'label' => __('Direct (API)')],
            ['value' => self::PAYMENT_MODE_HOSTED_PAYMENT_FORM, 'label' => __('Hosted Payment Form')],           
        ];
    }
}