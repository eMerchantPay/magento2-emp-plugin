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

namespace EMerchantPay\Genesis\Test\Unit\Block\Frontend;

use EMerchantPay\Genesis\Block\Frontend\Config as ConfigBlock;
use EMerchantPay\Genesis\Model\Config as BackendConfig;
use EMerchantPay\Genesis\Model\Method\Checkout;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigTest
 *
 * @covers \EMerchantPay\Genesis\Block\Frontend\Config
 */
class ConfigTest extends TestCase
{
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;

    /**
     * @var BackendConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $backendConfigMock;

    /**
     * @var ConfigBlock
     */
    private $configBlock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->backendConfigMock = $this->createMock(BackendConfig::class);

        $this->configBlock = new ConfigBlock(
            $this->contextMock,
            $this->backendConfigMock
        );
    }

    public function testIsIframeProcessingEnabledTrue()
    {
        $this->backendConfigMock->expects($this->once())
            ->method('setMethodCode')
            ->with(Checkout::CODE);

        $this->backendConfigMock->expects($this->once())
            ->method('isIframeProcessingEnabled')
            ->willReturn(true);

        $this->assertTrue($this->configBlock->isIframeProcessingEnabled());
    }

    public function testIsIframeProcessingEnabledFalse()
    {
        $this->backendConfigMock->expects($this->once())
            ->method('setMethodCode')
            ->with(Checkout::CODE);

        $this->backendConfigMock->expects($this->once())
            ->method('isIframeProcessingEnabled')
            ->willReturn(false);

        $this->assertFalse($this->configBlock->isIframeProcessingEnabled());
    }
}
