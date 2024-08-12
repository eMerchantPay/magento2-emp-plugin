<?php
/*
 * Copyright (C) 2018-2023 emerchantpay Ltd.
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
 * @copyright   2018-2023 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Test\Unit\Model\Observer;

use EMerchantPay\Genesis\Model\Method\Checkout;
use EMerchantPay\Genesis\Model\Observer\SalesOrderBeforeSaveObserver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;

class SalesOrderBeforeSaveObserverTest extends AbstractObserverTest
{
    /**
     * @var SalesOrderBeforeSaveObserver
     */
    protected $salesOrderBeforeSaveObserver;

    /**
     * @covers SalesOrderBeforeSaveObserver::execute()
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function testExecute(): void
    {
        $this->methodInstance->expects($this->once())
            ->method('getCode')
            ->willReturn(Checkout::CODE);

        $this->salesOrderBeforeSaveObserver->execute($this->observerMock);
    }

    /**
     * @covers SalesOrderBeforeSaveObserver::execute()
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function testExecuteWithOurPaymentMethod(): void
    {
        $this->methodInstance->expects($this->once())
            ->method('getCode')
            ->willReturn(Checkout::CODE);

        $this->configHelper->expects($this->once())
            ->method('getPaymentConfirmationEmailEnabled')
            ->with(Checkout::CODE)
            ->willReturn(true);

        $this->orderMock->expects($this->once())
            ->method('setCanSendNewEmailFlag')
            ->with(false)
            ->willReturn(false);
        $this->orderMock->expects($this->once())
            ->method('setSendEmail')
            ->with(false)
            ->willReturn(false);

        $this->salesOrderBeforeSaveObserver->execute($this->observerMock);
    }

    /**
     * @covers SalesOrderBeforeSaveObserver::execute()
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function testExecuteWithOtherPaymentMethod(): void
    {
        $otherCheckoutCode = 'other_checkout';
        $this->methodInstance->expects($this->once())
            ->method('getCode')
            ->willReturn($otherCheckoutCode);

        $this->configHelper->expects($this->once())
            ->method('getPaymentConfirmationEmailEnabled')
            ->with($otherCheckoutCode)
            ->willReturn(false);

        $this->orderMock->expects($this->never())
            ->method('setCanSendNewEmailFlag');
        $this->orderMock->expects($this->never())
            ->method('setSendEmail');

        $this->salesOrderBeforeSaveObserver->execute($this->observerMock);
    }

    /**
     * @covers SalesOrderBeforeSaveObserver::execute()
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function testWithPaymentConfirmationEmailEnabledFalse()
    {
        $this->methodInstance->expects($this->once())
            ->method('getCode')
            ->willReturn(Checkout::CODE);

        $this->configHelper->expects($this->once())
            ->method('getPaymentConfirmationEmailEnabled')
            ->with(Checkout::CODE)
            ->willReturn(false);

        $this->orderMock->expects($this->never())
            ->method('setCanSendNewEmailFlag')
            ->with(false)
            ->willReturn(false);
        $this->orderMock->expects($this->never())
            ->method('setSendEmail')
            ->with(false)
            ->willReturn(false);

        $this->salesOrderBeforeSaveObserver->execute($this->observerMock);
    }

    /**
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getSendMailOnOrderPaymentSuccessMocks();

        $this->paymentInterfaceMock->expects($this->once())
            ->method('getMethodInstance')
            ->willReturn($this->methodInstance);

        $this->orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->paymentInterfaceMock);

        $this->eventMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->observerMock   = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvent'])
            ->getMock();
        $this->observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->salesOrderBeforeSaveObserver = new SalesOrderBeforeSaveObserver($this->configHelper);
    }

    /**
     * @return string
     */
    protected function getObserverClassName(): string
    {
        return SalesOrderBeforeSaveObserver::class;
    }
}
