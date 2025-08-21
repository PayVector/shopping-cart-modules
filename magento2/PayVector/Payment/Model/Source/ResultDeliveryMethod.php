<?php
namespace PayVector\Payment\Model\Source;
use Magento\Framework\Option\ArrayInterface;

class ResultDeliveryMethod implements ArrayInterface
{
    const RESULT_DELIVERY_METHOD_POST = 'POST';    
    const RESULT_DELIVERY_METHOD_SERVER_PULL = 'SERVER_PULL';

    /**
     * Return result delivery method options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::RESULT_DELIVERY_METHOD_POST, 'label' => __('Post')],            
            ['value' => self::RESULT_DELIVERY_METHOD_SERVER_PULL, 'label' => __('Server Pull')],
        ];
    }
}