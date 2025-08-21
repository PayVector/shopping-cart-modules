<?php
namespace PayVector\Payment\Model\Source;
use Magento\Framework\Option\ArrayInterface;
class HashMethod implements ArrayInterface
{
    const HASH_METHOD_MD5 = 'md5';
	const HASH_METHOD_SHA1 = 'sha1';
	const HASH_METHOD_HMACMD5 = 'hmacmd5';
	const HASH_METHOD_HMACSHA1 = 'hmacsha1';

    /**
     * Return hash method options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::HASH_METHOD_MD5, 'label' => __('MD5')],
            ['value' => self::HASH_METHOD_SHA1, 'label' => __('SHA1')],
            ['value' => self::HASH_METHOD_HMACMD5, 'label' => __('HMACMD5')],
            ['value' => self::HASH_METHOD_HMACSHA1, 'label' => __('HMACSHA1')],
        ];
    }
}