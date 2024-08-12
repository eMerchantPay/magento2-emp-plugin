<?php
/*
 * Copyright (C) 2018 emerchantpay Ltd.
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
 * @author      emerchantpay
 * @copyright   2018 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Test\Unit\Controller\Ipn;

use EMerchantPay\Genesis\Controller\Ipn\Index as IndexController;
use EMerchantPay\Genesis\Model\Ipn\CheckoutIpn;
use EMerchantPay\Genesis\Test\Unit\Controller\AbstractControllerTest;
use Magento\Framework\Webapi\Response;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test Notifications
 *
 * Class IndexTest
 *
 * @covers IndexController
 */
class IndexTest extends AbstractControllerTest
{
    /**
     * @var CheckoutIpn|MockObject
     */
    protected $checkoutIpnMock;

    /**
     * Gets controller's fully qualified class name
     *
     * @return string
     */
    protected function getControllerClassName()
    {
        return IndexController::class;
    }

    /**
     * Get mock for Checkout IPN
     *
     * @return CheckoutIpn|MockObject
     */
    protected function getCheckoutIpnMock()
    {
        return $this->checkoutIpnMock = $this->getMockBuilder(CheckoutIpn::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['handleGenesisNotification'])
            ->getMock();
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getCheckoutIpnMock();
    }

    /**
     * @covers IndexController::execute()
     */
    public function testExecuteHttpRequestIsNotPost()
    {
        $this->httpRequestMock->expects(self::once())
            ->method('isPost')
            ->willReturn(false);

        $this->getControllerInstance()->execute();
    }

    /**
     * @covers IndexController::execute()
     */
    public function testExecutePostWithoutId()
    {
        $postParams = [];

        $this->httpRequestMock->expects(self::once())
            ->method('isPost')
            ->willReturn(true);

        $this->httpRequestMock->expects(self::atLeastOnce())
            ->method('getPostValue')
            ->willReturn($postParams);

        $this->responseInterfaceMock->expects(self::atLeastOnce())
            ->method('setHttpResponseCode')
            ->with(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN)
            ->willReturnSelf();

        $this->getControllerInstance()->execute();
    }

    /**
     * @covers IndexController::execute()
     */
    public function testExecutePostWithWpfUniqueId()
    {
        $postParams = [
            'wpf_unique_id' => '12345678901234567890123456789012',
            'signature'     => '1234567890123456789012345678901234567890'
        ];

        $responseBody = self::getResponseBody($postParams);

        $this->httpRequestMock->expects(self::once())
            ->method('isPost')
            ->willReturn(true);

        $this->httpRequestMock->expects(self::any())
            ->method('getPostValue')
            ->willReturn($postParams);

        $this->objectManagerMock->expects(self::once())
            ->method('create')
            ->with(
                'EMerchantPay\Genesis\Model\Ipn\CheckoutIpn',
                [
                    'data' => $postParams
                ]
            )
            ->willReturn($this->checkoutIpnMock);

        $this->checkoutIpnMock->expects(self::once())
            ->method('handleGenesisNotification')
            ->willReturn($responseBody);

        $this->responseInterfaceMock->expects(self::once())
            ->method('setHeader')
            ->with('Content-type', 'application/xml')
            ->willReturnSelf();

        $this->responseInterfaceMock->expects(self::once())
            ->method('setBody')
            ->with($responseBody)
            ->willReturnSelf();

        $this->responseInterfaceMock->expects(self::atLeastOnce())
            ->method('setHttpResponseCode')
            ->with(Response::HTTP_OK)
            ->willReturnSelf();

        $this->responseInterfaceMock->expects(self::once())
            ->method('sendResponse');

        $this->getControllerInstance()->execute();
    }

    /**
     * Gets array element if exists
     *
     * @param array $array
     * @param string $key
     *
     * @return string
     */
    protected static function getArrayElement($array, $key)
    {
        return array_key_exists($key, $array) ? $array[$key] : null;
    }

    /**
     * Generates the expected XML response from the payment gateway
     *
     * @param array $postParams
     *
     * @return string|null
     */
    protected static function getResponseBody($postParams)
    {
        $wpf_unique_id = self::getArrayElement($postParams, 'wpf_unique_id');

        return ($wpf_unique_id) ? '<?xml version="1.0" encoding="UTF-8"?>
<notification_echo>
  <wpf_unique_id>' . $wpf_unique_id . '</wpf_unique_id>
</notification_echo>
'
            : null;
    }
}
