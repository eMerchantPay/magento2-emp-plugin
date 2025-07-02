<?php
/*
 * Copyright (C) 2018-2025 emerchantpay Ltd.
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
 * @copyright   2018-2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
declare(strict_types=1);

namespace EMerchantPay\Genesis\Test\Unit\Controller\Checkout;

use EMerchantPay\Genesis\Test\Unit\Controller\AbstractControllerTest;
use EMerchantPay\Genesis\Controller\Checkout\RedirectUrl;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

/**
 * Test RedirectUrl
 *
 * Class RedirectUrlTest
 *
 * @covers RedirectUrlController
 */
class RedirectUrlTest extends AbstractControllerTest
{
    /** @var JsonFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $jsonFactoryMock;

    /** @var Json|\PHPUnit\Framework\MockObject\MockObject */
    private $jsonResultMock;

    protected function getControllerClassName()
    {
        return RedirectUrl::class;
    }

    protected function setUp(): void
    {
        // Create mocks before parent setup
        $this->jsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->jsonResultMock  = $this->createMock(Json::class);

        parent::setUp();

        $this->jsonFactoryMock
            ->method('create')
            ->willReturn($this->jsonResultMock);

        $this->controllerInstance = $this->getObjectManagerHelper()->getObject(
            $this->getControllerClassName(),
            [
                'checkoutSession'   => $this->checkoutSessionMock,
                'context'           => $this->contextMock,
                'resultJsonFactory' => $this->jsonFactoryMock,
                'logger'            => $this->createMock(LoggerInterface::class)
            ]
        );
    }

    public function testExecuteReturnsRedirectUrlInJsonResponse()
    {
        $expectedUrl = 'https://example.com/payment';

        $this->checkoutSessionMock
            ->method('getEmerchantPayCheckoutRedirectUrl')
            ->willReturn($expectedUrl);

        $this->jsonResultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['redirectUrl' => $expectedUrl])
            ->willReturnSelf();

        $result = $this->getControllerInstance()->execute();

        $this->assertSame($this->jsonResultMock, $result);
    }
}
