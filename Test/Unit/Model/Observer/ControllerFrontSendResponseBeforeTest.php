<?php
/*
 * Copyright (C) 2025 emerchantpay Ltd.
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
 * @copyright   2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Test\Unit\Model\Observer;

use EMerchantPay\Genesis\Model\Observer\ControllerFrontSendResponseBefore;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test front controller response
 *
 * Class ControllerFrontSendResponseBeforeTest
 *
 * @covers ControllerFrontSendResponseBefore
 */
class ControllerFrontSendResponseBeforeTest extends AbstractObserverTest
{
    /**
     * @var ControllerFrontSendResponseBefore|MockObject
     */
    protected $observerInstance;

    /**
     * @covers ControllerFrontSendResponseBefore::execute
     */
    public function testExecuteNullResponse()
    {
        $this->observerMock->expects(self::once())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock->expects(self::once())
            ->method('getData')
            ->with('response')
            ->willReturn(null);

        $this->dataHelperMock->expects(self::never())
            ->method('createWebApiException');

        $this->restResponseMock->expects(self::never())
            ->method('setException');

        $this->getObserverInstance()->execute($this->observerMock);
    }

    /**
     * @covers ControllerFrontSendResponseBefore::execute
     */
    public function testExecuteDoNotOverrideCheckoutException()
    {
        $this->restResponseMock->expects(self::once())
            ->method('isException')
            ->willReturn(false);

        $this->eventMock->expects(self::once())
            ->method('getData')
            ->with('response')
            ->willReturn($this->restResponseMock);

        $this->observerMock->expects(self::once())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->checkoutSessionMock->expects(self::atLeastOnce())
            ->method('getEmerchantPayLastCheckoutError')
            ->willReturn('Sample Error Message');

        $this->dataHelperMock->expects(self::never())
            ->method('createWebApiException');

        $this->restResponseMock->expects(self::never())
            ->method('setException');

        $this->getObserverInstance()->execute($this->observerMock);
    }

    /**
     * @covers ControllerFrontSendResponseBefore::execute
     */
    public function testExecuteOverrideCheckoutException()
    {
        $checkoutErrorMessage='Checkout Error Message';

        $this->restResponseMock->expects(self::once())
            ->method('isException')
            ->willReturn(true);

        $this->eventMock->expects(self::once())
            ->method('getData')
            ->with('response')
            ->willReturn($this->restResponseMock);

        $this->observerMock->expects(self::once())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->checkoutSessionMock->expects(self::atLeastOnce())
            ->method('getEmerchantPayLastCheckoutError')
            ->willReturn($checkoutErrorMessage);

        $this->dataHelperMock->expects(self::once())
            ->method('createWebApiException')
            ->with(
                $checkoutErrorMessage,
                \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST
            )
            ->willReturn($this->webapiException);

        $this->restResponseMock->expects(self::once())
            ->method('setException')
            ->with($this->webapiException);

        $this->getObserverInstance()->execute($this->observerMock);
    }

    /**
     * @return string
     */
    protected function getObserverClassName()
    {
        return ControllerFrontSendResponseBefore::class;
    }
}
