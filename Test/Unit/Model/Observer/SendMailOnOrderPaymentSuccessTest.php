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
use EMerchantPay\Genesis\Model\Observer\SendMailOnOrderPaymentSuccess;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount as InvokedCountMatcher;

class SendMailOnOrderPaymentSuccessTest extends AbstractObserverTest
{
    /**
     * @var array|(Order&MockObject)|MockObject
     */
    protected $orderIds = [];

    /**
     * @covers SalesOrderBeforeSaveObserver::execute()
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function testExecuteWithSend(): void
    {
        $this->setExpects($this->once(), false);

        $this->configHelper->expects($this->once())
            ->method('getPaymentConfirmationEmailEnabled')
            ->with(Checkout::CODE)
            ->willReturn(true);

        $this->sendMailOnOrderPaymentSuccess->execute($this->observerMock);
    }

    /**
     * @covers SalesOrderBeforeSaveObserver::execute()
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function testWithPaymentConfirmationEmailEnabledFalse(): void
    {
        $this->setExpects($this->once(), false);

        $this->configHelper->expects($this->once())
            ->method('getPaymentConfirmationEmailEnabled')
            ->with(Checkout::CODE)
            ->willReturn(false);

        $this->sendMailOnOrderPaymentSuccess->execute($this->observerMock);
    }

    /**
     * @covers SalesOrderBeforeSaveObserver::execute()
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function testExecuteWithoutOrder(): void
    {
        $this->setExpects($this->never(), true);

        $this->sendMailOnOrderPaymentSuccess->execute($this->observerMock);
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

        $this->orderModel      = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderSender     = $this->getMockBuilder(OrderSender::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sendMailOnOrderPaymentSuccess = new SendMailOnOrderPaymentSuccess(
            $this->orderModel,
            $this->orderSender,
            $this->checkoutSession,
            $this->configHelper
        );
    }

    /**
     * Helper method to set common count matchers
     *
     * @param InvokedCountMatcher $expect
     * @param bool                $emptyOrderIds
     *
     * @return void
     */
    protected function setExpects(InvokedCountMatcher $expect, bool $emptyOrderIds): void
    {
        $this->methodInstance->expects(clone $expect)
            ->method('getCode')
            ->willReturn(Checkout::CODE);
        $this->paymentInterfaceMock->expects(clone $expect)
            ->method('getMethodInstance')
            ->willReturn($this->methodInstance);
        $this->orderMock->expects(clone $expect)
            ->method('getPayment')
            ->willReturn($this->paymentInterfaceMock);

        $this->orderIds = null;

        if (!$emptyOrderIds) {
            $this->orderIds[] = $this->orderMock;
        }

        $this->createMock = $this->getMockBuilder(\stdclass::class)
            ->addMethods(['load'])
            ->getMock();

        $this->createMock->expects(clone $expect)
            ->method('load')
            ->willReturn($this->orderMock);
        $this->orderModel->expects(clone $expect)
            ->method('create')
            ->willReturn($this->createMock);

        $this->eventMock->expects($this->once())
            ->method('getOrderIds')
            ->willReturn($this->orderIds);

        $this->observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->eventMock);
    }

    /**
     * @return string
     */
    protected function getObserverClassName(): string
    {
        return SendMailOnOrderPaymentSuccess::class;
    }
}
