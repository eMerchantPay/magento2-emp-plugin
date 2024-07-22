<?php
/*
 * Copyright (C) 2018-2024 emerchantpay Ltd.
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
 * @copyright   2018-2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
declare(strict_types=1);

namespace EMerchantPay\Genesis\Test\Unit\Controller\Checkout;

use EMerchantPay\Genesis\Controller\Checkout\Iframe as IframeController;
use EMerchantPay\Genesis\Helper\Data;
use EMerchantPay\Genesis\Test\Unit\Controller\AbstractControllerTest;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

/**
 * Class IframeTest
 *
 * @covers \EMerchantPay\Genesis\Controller\Checkout\Iframe
 */
class IframeTest extends AbstractControllerTest
{
    /**
     * Gets controller's fully qualified class name
     * @return string
     */
    protected function getControllerClassName(): string
    {
        return IframeController::class;
    }

    /**
     * Sets up common mocks for test methods
     */
    private function setUpCommonMocks(bool $expectRaw = false, string $expectedUrl = '')
    {
        $resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        if ($expectRaw) {
            $rawMock = $this->createMock(Raw::class);
            $rawMock->expects($this->once())
                ->method('setHeader')
                ->with('Content-Type', 'text/html')
                ->willReturnSelf();

            if ($expectedUrl !== '') {
                $expectedHtml = sprintf(
                    '<html><body><script type="text/javascript">if (window.top !== window.self) ' .
                    '{window.top.location.href = "%s";} else {window.location.href = "%s";}</script></body></html>',
                    $expectedUrl,
                    $expectedUrl
                );

                $rawMock->expects($this->once())
                    ->method('setContents')
                    ->with($expectedHtml)
                    ->willReturnSelf();
            }

            $resultFactoryMock->expects($this->once())
                ->method('create')
                ->with(ResultFactory::TYPE_RAW)
                ->willReturn($rawMock);
        }

        $urlBuilderMock = $this->createMock(UrlInterface::class);
        if ($expectedUrl !== '') {
            $urlBuilderMock->expects($this->once())
                ->method('getUrl')
                ->willReturn($expectedUrl);
        }

        $this->controllerInstance = $this->getObjectManagerHelper()->getObject(
            $this->getControllerClassName(),
            [
                'context' => $this->getContextMock(),
                'logger' => $this->createMock(LoggerInterface::class),
                'checkoutSession' => $this->checkoutSessionMock,
                'orderFactory' => $this->orderFactoryMock,
                'resultFactory' => $resultFactoryMock,
                'urlBuilder' => $urlBuilderMock
            ]
        );
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Checkout\Iframe::execute
     */
    public function testExecuteFailWhenLastRealOrderIdIsNull()
    {
        $this->httpRequestMock->expects($this->once())
            ->method('getParam')
            ->with('action')
            ->willReturn(Data::ACTION_RETURN_SUCCESS);

        $this->checkoutSessionMock->expects($this->atLeastOnce())
            ->method('getLastRealOrderId')
            ->willReturn(null);

        $this->redirectResponseMock->expects($this->never())
            ->method('redirect');

        $this->setUpCommonMocks();

        $result = $this->controllerInstance->execute();

        $this->assertNull($result);
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Checkout\Iframe::execute
     */
    public function testExecuteSuccessReturnAction()
    {
        $this->httpRequestMock->expects($this->once())
            ->method('getParam')
            ->with('action')
            ->willReturn(Data::ACTION_RETURN_SUCCESS);

        $this->checkoutSessionMock->expects($this->atLeastOnce())
            ->method('getLastRealOrderId')
            ->willReturn(1);

        $expectedUrl = 'https://example.com/checkout/onepage/success';
        $this->setUpCommonMocks(true, $expectedUrl);

        $result = $this->controllerInstance->execute();

        $this->assertInstanceOf(Raw::class, $result);
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Checkout\Iframe::execute
     */
    public function testExecuteCancelReturnAction()
    {
        $this->httpRequestMock->expects($this->once())
            ->method('getParam')
            ->with('action')
            ->willReturn(Data::ACTION_RETURN_CANCEL);

        $this->checkoutSessionMock->expects($this->never())
            ->method('getLastRealOrderId');

        $expectedUrl = 'https://example.com/checkout/cart';
        $this->setUpCommonMocks(true, $expectedUrl);

        $result = $this->controllerInstance->execute();

        $this->assertInstanceOf(Raw::class, $result);
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Checkout\Iframe::execute
     */
    public function testExecuteFailureReturnAction()
    {
        $this->httpRequestMock->expects($this->once())
            ->method('getParam')
            ->with('action')
            ->willReturn(Data::ACTION_RETURN_FAILURE);

        $expectedUrl = 'https://example.com/checkout/cart';
        $this->setUpCommonMocks(true, $expectedUrl);

        $result = $this->controllerInstance->execute();

        $this->assertInstanceOf(Raw::class, $result);
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Checkout\Iframe::execute
     */
    public function testExecuteUnsupportedReturnAction()
    {
        $this->httpRequestMock->expects($this->once())
            ->method('getParam')
            ->with('action')
            ->willReturn('');

        $this->setUpCommonMocks();

        $result = $this->controllerInstance->execute();

        $this->assertNull($result);
    }
}
