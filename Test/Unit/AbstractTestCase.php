<?php
/*
 * Copyright (C) 2016 eMerchantPay Ltd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      eMerchantPay
 * @copyright   2016 eMerchantPay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Test\Unit;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class AbstractTestCase
 * @package EMerchantPay\Genesis\Test\Unit
 */
abstract class AbstractTestCase extends \PHPUnit\Framework\TestCase
{
    const SAMPLE_REDIRECT_URL       = 'https://example.com/action/redirect/to';
    const SAMPLE_NOTIFICATION_URL   = 'https://example.com/action/notify';
    const SAMPLE_RETURN_SUCCESS_URL = 'https://example.com/action/success';
    const SAMPLE_RETURN_CANCEL_URL  = 'https://example.com/action/cancel';
    const SAMPLE_RETURN_FAILURE_URL = 'https://example.com/action/failure';

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    protected function init()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
    }

    /**
     * @param string $status
     * @param string $transactionType
     * @param string string $message
     * @param string string $technicalMessage
     * @param array $additionalParams
     * @return \stdClass
     */
    protected function getSampleGatewayResponse(
        $status,
        $transactionType = null,
        $message = '',
        $technicalMessage = '',
        $additionalParams = []
    ) {
        $response = new \stdClass();
        $response->status = $status;
        $response->unique_id = $this->generateUniqueId();

        if ($transactionType) {
            $response->transaction_type = $transactionType;
            $response->currency = 'USD';
            $response->amount = '23.56';
            $response->transaction_id = $this->generateUniqueId();
        }

        if (!empty($message)) {
            $response->message = $message;
        }

        if (!empty($technicalMessage)) {
            $response->technical_message = $technicalMessage;
        }

        if (is_array($additionalParams)) {
            foreach ($additionalParams as $key => $value) {
                $response->{$key} = $value;
            }
        }

        return $response;
    }

    /**
     * @param mixed|array $keys
     * @param array $arr
     * @return bool
     */
    protected function getArrayHasKeys($keys, array $arr)
    {
        if ($keys && !is_array($keys)) {
            return array_key_exists($keys, $arr);
        }

        foreach ($keys as $key) {
            if (!array_key_exists($key, $arr)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    protected function generateUniqueId()
    {
        return sha1(uniqid());
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->init();
    }

    protected function getObjectManagerHelper()
    {
        return $this->objectManagerHelper;
    }

    /**
     * Asserts that an array has specified keys.
     *
     * @param array  $keys
     * @param array  $array
     * @param string $message
     */
    public function assertArrayHasKeys(array $keys, $array, $message = '')
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey(
                $key,
                $array,
                $message
            );
        }
    }

    /**
     * Asserts that an array has number of keys.
     *
     * @param int    $keysCount
     * @param array  $array
     * @param string $message
     */
    public function assertArrayKeysCount($keysCount, $array, $message = '')
    {
        $this->assertTrue(
            $keysCount == count($array),
            $message
        );
    }

    /**
     * Asserts that an array has an item with specific value.
     *
     * @param mixed  $value
     * @param array  $array
     * @param string $message
     */
    public function assertArrayHasValue($value, $array, $message = '')
    {
        $this->assertTrue(
            in_array($value, $array),
            $message
        );
    }

    /**
     * Asserts that an array has items with specific values.
     *
     * @param array  $values
     * @param array  $array
     * @param string $message
     */
    public function assertArrayHasValues(array $values, $array, $message = '')
    {
        foreach ($values as $value) {
            $this->assertArrayHasValue(
                $value,
                $array,
                $message
            );
        }
    }
}
