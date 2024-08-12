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

use EMerchantPay\Genesis\Controller\AbstractAction;
use EMerchantPay\Genesis\Controller\Checkout\Index;
use EMerchantPay\Genesis\Controller\Checkout\Redirect;
use EMerchantPay\Genesis\Test\Unit\AbstractTestCase;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\ObjectManager\ObjectManager as ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\App\Response\Redirect as RedirectResponse;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AbstractControllerTest
 */
abstract class AbstractControllerTest extends AbstractTestCase
{
    /**
     * @var Index|Redirect
     */
    protected $controllerInstance;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Session|MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var OrderFactory|MockObject
     */
    protected $orderFactoryMock;

    /**
     * @var Order|MockObject
     */
    protected $orderMock;

    /**
     * @var RedirectResponse|MockObject
     */
    protected $redirectResponseMock;

    /**
     * @var HttpRequest|MockObject
     */
    protected $httpRequestMock;

    /**
     * @var MessageManager|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $responseInterfaceMock;

    /**
     * Get controller class name
     *
     * @return string
     */
    abstract protected function getControllerClassName();

    /**
     * Gets controllers instance
     *
     * @return AbstractAction
     */
    protected function getControllerInstance()
    {
        return $this->controllerInstance;
    }

    /**
     * Get mock for context
     *
     * @return MockObject
     */
    protected function getContextMock()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getResponse',
                    'getRedirect',
                    'getRequest',
                    'getMessageManager',
                    'getObjectManager'
                ]
            )
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
     *
     * @return MockObject
     */
    protected function getCheckoutSessionMock()
    {
        return $this->checkoutSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(
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
     * @return MockObject
     */
    protected function getOrderFactoryMock()
    {
        return $this->orderFactoryMock = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
    }

    /**
     * Get mock for order
     *
     * @return MockObject
     */
    protected function getOrderMock()
    {
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'loadByIncrementId',
                    'getId',
                    'getPayment'
                ]
            )
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
     *
     * @return MockObject
     */
    protected function getRedirectResponseMock()
    {
        return $this->redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['redirect'])
            ->getMock();
    }

    /**
     * Get mock for HTTP request
     *
     * @return MockObject
     */
    protected function getHttpRequestMock()
    {
        return $this->httpRequestMock = $this->getMockBuilder(HttpRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getParam',
                    'isPost',
                    'getPostValue'
                ]
            )
            ->getMock();
    }

    /**
     * Get mock for message manager
     *
     * @return MockObject
     */
    protected function getMessageManagerMock()
    {
        return $this->messageManagerMock = $this->getMockBuilder(MessageManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'addSuccessMessage',
                    'addWarningMessage',
                    'addErrorMessage'
                ]
            )
            ->getMock();
    }

    /**
     * Get mock for object manager
     *
     * @return MockObject
     */
    protected function getObjectManagerMock()
    {
        return $this->objectManagerMock = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
    }

    /**
     * Get mock for response interface
     *
     * @return ResponseInterface|MockObject
     */
    protected function getResponseMock()
    {
        return $this->responseInterfaceMock = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'setRedirect',
                'setHttpResponseCode',
                'setHeader',
                'setBody',
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
    protected function setUp(): void
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
