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

namespace EMerchantPay\Genesis\Test\Unit\Model\Observer;

use EMerchantPay\Genesis\Helper\Data as DataHelper;
use EMerchantPay\Genesis\Model\Config;
use EMerchantPay\Genesis\Model\Observer\ControllerFrontSendResponseBefore;
use EMerchantPay\Genesis\Model\Observer\SalesOrderBeforeSaveObserver;
use EMerchantPay\Genesis\Test\Unit\AbstractTestCase;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Webapi\ErrorProcessor;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Rest\Response as RestResponse;
use Magento\Payment\Model\Method\InstanceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

/**
 * Class AbstractObserverTest
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class AbstractObserverTest extends AbstractTestCase
{
    /**
     * @var ControllerFrontSendResponseBefore|MockObject
     */
    protected $observerInstance;

    /**
     * @var DataHelper|MockObject
     */
    protected $dataHelperMock;

    /**
     * @var ErrorProcessor|MockObject
     */
    protected $errorProcessorMock;

    /**
     * @var Session|MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var Observer|MockObject
     */
    protected $observerMock;

    /**
     * @var Event|MockObject
     */
    protected $eventMock;

    /**
     * @var RestResponse|MockObject
     */
    protected $restResponseMock;

    /**
     * @var WebapiException|MockObject
     */
    protected $webapiException;

    /**
     * @var (Config&MockObject)|MockObject
     */
    protected $configHelper;

    /**
     * @var (InstanceFactory&MockObject)|MockObject
     */
    protected $methodInstance;

    /**
     * @var (Order&MockObject)|MockObject
     */
    protected $orderMock;

    /**
     * @var SalesOrderBeforeSaveObserver
     */
    protected $sendMailOnOrderPaymentSuccess;

    /**
     * @var (OrderFactory&MockObject)|MockObject
     */
    protected $orderModel;

    /**
     * @var (OrderSender&MockObject)|MockObject
     */
    protected $orderSender;

    /**
     * @var (Session&MockObject)|MockObject
     */
    protected $checkoutSession;

    /**
     * @var stdClass
     */
    protected $createMock;

    /**
     * @var (OrderPaymentInterface&MockObject)|MockObject
     */
    protected $paymentInterfaceMock;

    abstract protected function getObserverClassName();

    /**
     * Gets observer's instance
     *
     * @return ControllerFrontSendResponseBefore|
     *
     * @covers SalesOrderBeforeSaveObserver
     */
    protected function getObserverInstance()
    {
        return $this->observerInstance;
    }

    /**
     * Get mock for data helper
     * @return DataHelper|MockObject
     */
    protected function getDataHelperMock()
    {
        return $this->dataHelperMock = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createWebApiException'])
            ->getMock();
    }

    /**
     * Get mock for error processor
     * @return ErrorProcessor|MockObject
     */
    protected function getErrorProcessorMock()
    {
        return $this->errorProcessorMock = $this->getMockBuilder(ErrorProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get mock for checkout session
     * @return Session|MockObject
     */
    protected function getCheckoutSessionMock()
    {
        return $this->checkoutSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(
                [
                    'getEmerchantPayLastCheckoutError',
                    'setEmerchantPayLastCheckoutError'
                ]
            )
            ->getMock();
    }

    /**
     * Get mock for event observer
     *
     * @return Observer|MockObject
     */
    protected function getObserverMock()
    {
        return $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvent'])
            ->getMock();
    }

    /**
     * Get mock for event
     *
     * @return Event|MockObject
     */
    protected function getEventMock()
    {
        return $this->eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
    }

    /**
     * Get mock for Webapi REST response
     *
     * @return RestResponse|MockObject
     */
    protected function getRestResponseMock()
    {
        return $this->restResponseMock = $this->getMockBuilder(RestResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'setException',
                    'isException'
                ]
            )
            ->getMock();
    }

    /**
     * Get mock for Webapi exception
     *
     * @return WebapiException|MockObject
     */
    protected function getWebapiException()
    {
        return $this->webapiException = $this->getMockBuilder(WebapiException::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Helper class for common mocks
     *
     * @return void
     */
    protected function getSendMailOnOrderPaymentSuccessMocks(): void
    {
        $this->methodInstance = $this->getMockBuilder(InstanceFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCode'])
            ->getMock();

        $this->paymentInterfaceMock = $this->getMockBuilder(OrderPaymentInterface::class)
            ->addMethods(['getMethodInstance'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->onlyMethods(
                [
                    'getPayment',
                    'setCanSendNewEmailFlag',
                ]
            )
            ->addMethods(['setSendEmail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->configHelper = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods(
                [
                    'getEvent',
                    'getOrder',
                    'getOrderIds'
                ]
            )
            ->getMock();
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getDataHelperMock();
        $this->getErrorProcessorMock();
        $this->getCheckoutSessionMock();
        $this->getObserverMock();
        $this->getEventMock();
        $this->getRestResponseMock();
        $this->getWebapiException();

        $this->observerInstance = $this->getObjectManagerHelper()->getObject(
            $this->getObserverClassName(),
            [
                'moduleHelper'    => $this->dataHelperMock,
                'errorProcessor'  => $this->errorProcessorMock,
                'checkoutSession' => $this->checkoutSessionMock
            ]
        );

        $this->assertInstanceOf(
            $this->getObserverClassName(),
            $this->getObserverInstance()
        );
    }
}
