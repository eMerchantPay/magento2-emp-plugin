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

namespace EMerchantPay\Genesis\Test\Unit\Controller;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Store\App\Response\Redirect as RedirectResponse;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\ObjectManager\ObjectManager as ObjectManager;
use Magento\Framework\App\ResponseInterface;

/**
 * Class AbstractControllerTest
 * @package EMerchantPay\Genesis\Test\Unit\Controller
 */
abstract class AbstractControllerTest extends \EMerchantPay\Genesis\Test\Unit\AbstractTestCase
{
    /**
     * @var \EMerchantPay\Genesis\Controller\Checkout\Index|
     *      \EMerchantPay\Genesis\Controller\Checkout\Redirect
     */
    protected $controllerInstance;

    /**
     * @var \Magento\Framework\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var \Magento\Sales\Model\OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderFactoryMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Store\App\Response\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $redirectResponseMock;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpRequestMock;

    /**
     * @var \Magento\Framework\Message\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseInterfaceMock;

    /**
     * Get controller class name
     * @return string
     */
    abstract protected function getControllerClassName();

    /**
     * Gets controllers instance
     * @return \EMerchantPay\Genesis\Controller\AbstractAction
     */
    protected function getControllerInstance()
    {
        return $this->controllerInstance;
    }

    /**
     * Get mock for context
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContextMock()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResponse','getRedirect','getRequest','getMessageManager','getObjectManager'])
            ->getMock($this->contextMock);

        $this->contextMock->expects(self::any())
            ->method('getResponse')
            ->willReturn(
                $this->responseInterfaceMock
            );

        $this->contextMock->expects(self::any())
            ->method('getRedirect')
            ->willReturn(
                $this->redirectResponseMock
            );

        $this->contextMock->expects(self::any())
            ->method('getRequest')
            ->willReturn(
                $this->httpRequestMock
            );

        $this->contextMock->expects(self::any())
            ->method('getMessageManager')
            ->willReturn(
                $this->messageManagerMock
            );

        $this->contextMock->expects(self::any())
            ->method('getObjectManager')
            ->willReturn(
                $this->objectManagerMock
            );

        return $this->contextMock;
    }

    /**
     * Get mock for checkout session
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCheckoutSessionMock()
    {
        return $this->checkoutSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getLastRealOrderId',
                    'getEmerchantPayCheckoutRedirectUrl',
                    'setEmerchantPayCheckoutRedirectUrl'
                ]
            )
            ->getMock();
    }

    /**
     * Get mock for order factory
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrderFactoryMock()
    {
        return $this->orderFactoryMock = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
    }

    /**
     * Get mock for order
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrderMock()
    {
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadByIncrementId','getId','getPayment'])
            ->getMock();

        $this->orderMock->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        $this->orderFactoryMock->expects(self::any())
            ->method('create')
            ->willReturn(
                $this->orderMock
            );

        $this->orderMock->expects(self::any())
            ->method('loadByIncrementId', 'getId')
            ->willReturn(
                $this->orderMock
            );
        return $this->orderMock;
    }

    /**
     * Get mock for redirect response
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRedirectResponseMock()
    {
        return $this->redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->setMethods(['redirect'])
            ->getMock();
    }

    /**
     * Get mock for HTTP request
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getHttpRequestMock()
    {
        return $this->httpRequestMock = $this->getMockBuilder(HttpRequest::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam','isPost','getPostValue'])
            ->getMock();
    }

    /**
     * Get mock for message manager
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMessageManagerMock()
    {
        return $this->messageManagerMock = $this->getMockBuilder(MessageManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['addSuccess','addWarning','addError'])
            ->getMock();
    }

    /**
     * Get mock for object manager
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getObjectManagerMock()
    {
        return $this->objectManagerMock = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
    }

    /**
     * Get mock for response interface
     * @return \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getResponseMock()
    {
        return $this->responseInterfaceMock = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setRedirect','setHttpResponseCode',
                'setHeader','setBody','sendResponse'
            ])
            ->getMockForAbstractClass();
    }

    /**
     * Creates controller instance
     */
    protected function createControllerInstance()
    {
        $this->controllerInstance = $this->getObjectManagerHelper()->getObject(
            $this->getControllerClassName(),
            [
                'checkoutSession'   => $this->checkoutSessionMock,
                'orderFactory'      => $this->orderFactoryMock,
                'context'           => $this->contextMock
            ]
        );

        $this->assertInstanceOf(
            $this->getControllerClassName(),
            $this->getControllerInstance()
        );
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->getCheckoutSessionMock();
        $this->getOrderFactoryMock();
        $this->getOrderMock();
        $this->getRedirectResponseMock();
        $this->getHttpRequestMock();
        $this->getMessageManagerMock();
        $this->getObjectManagerMock();
        $this->getResponseMock();
        $this->getContextMock();

        $this->createControllerInstance();
    }
}
