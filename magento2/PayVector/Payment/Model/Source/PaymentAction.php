<?php
namespace PayVector\Payment\Model\Source;
use Magento\Framework\Option\ArrayInterface;
class PaymentAction implements ArrayInterface
{
    const ACTION_AUTHORIZE        = 'authorize';         // PREAUTH
    const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture'; // SALE

    /**
     * Return hash method options array
     *
     * @return array
     */
    public function toOptionArray()
    {
       return [
            ['value' => self::ACTION_AUTHORIZE, 'label' => __('PREAUTH')],
            ['value' => self::ACTION_AUTHORIZE_CAPTURE, 'label' => __('SALE')],
        ];
    }
}