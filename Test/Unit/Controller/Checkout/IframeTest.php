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
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Action\Context;

/**
 * Test Iframe jailbreak
 *
 * Class IframeTest
 *
 * @covers IframeController
 */
class IframeTest extends AbstractControllerTest
{
    /**
     * @var ResultFactory
     */
    protected $resultFactoryMock;

    /**
     * @var UrlInterface
     */
    protected $urlBuilderMock;

    /**
     * @var HttpRequest
     */
    protected $requestMock;

    /**
     * @var HttpResponse
     */
    protected $responseMock;

    /**
     * @var RedirectFactory
     */
    protected $redirectFactoryMock;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var IframeControllerk
     */
    protected $iframeController;

    /**
     * @covers IframeController::execute
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
     * @covers IframeController::execute
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
     * @covers IframeController::execute
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
     * @covers IframeController::execute
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
     * @covers IframeController::execute
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

    /**
     * Gets controller's fully qualified class name
     *
     * @return string
     */
    protected function getControllerClassName(): string
    {
        return IframeController::class;
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp(); // Call the parent setUp to initialize common mocks

        $this->setUpCommonMocks();

        $this->requestMock         = $this->createMock(HttpRequest::class);
        $this->responseMock        = $this->createMock(HttpResponse::class);
        $this->redirectFactoryMock = $this->createMock(RedirectFactory::class);
        $this->storeManagerMock    = $this->createMock(StoreManagerInterface::class);
        $this->messageManagerMock  = $this->createMock(ManagerInterface::class);

        // Create a mock for the context, which includes all its dependencies
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getRequest',
                    'getResponse',
                    'getResultRedirectFactory',
                    'getMessageManager',
                ]
            )
            ->addMethods(['getStoreManager'])
            ->getMock();

        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->method('getResponse')->willReturn($this->responseMock);
        $this->contextMock->method('getResultRedirectFactory')->willReturn($this->redirectFactoryMock);
        $this->contextMock->method('getStoreManager')->willReturn($this->storeManagerMock);
        $this->contextMock->method('getMessageManager')->willReturn($this->messageManagerMock);

        $this->iframeController = $this->getObjectManagerHelper()->getObject(
            $this->getControllerClassName(),
            [
                'context'         => $this->getContextMock(),
                'logger'          => $this->createMock(LoggerInterface::class),
                'checkoutSession' => $this->checkoutSessionMock,
                'orderFactory'    => $this->orderFactoryMock,
                'resultFactory'   => $this->resultFactoryMock,
                'urlBuilder'      => $this->urlBuilderMock
            ]
        );
    }

    /**
     * Sets up common mocks for test methods
     */
    private function setUpCommonMocks(bool $expectRaw = false, string $expectedUrl = '')
    {
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->addMethods(['getLastRealOrderId'])
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

            $this->resultFactoryMock->expects($this->once())
                ->method('create')
                ->with(ResultFactory::TYPE_RAW)
                ->willReturn($rawMock);
        }

        $this->urlBuilderMock = $this->createMock(UrlInterface::class);
        if ($expectedUrl !== '') {
            $this->urlBuilderMock->expects($this->once())
                ->method('getUrl')
                ->willReturn($expectedUrl);
        }

        $this->controllerInstance = $this->getObjectManagerHelper()->getObject(
            $this->getControllerClassName(),
            [
                'context'         => $this->getContextMock(),
                'logger'          => $this->createMock(LoggerInterface::class),
                'checkoutSession' => $this->checkoutSessionMock,
                'orderFactory'    => $this->orderFactoryMock,
                'resultFactory'   => $this->resultFactoryMock,
                'urlBuilder'      => $this->urlBuilderMock
            ]
        );
    }
}
