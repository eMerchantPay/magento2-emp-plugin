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

namespace EMerchantPay\Genesis\Test\Unit\Block\Frontend;

use EMerchantPay\Genesis\Block\Frontend\Config as ConfigBlock;
use EMerchantPay\Genesis\Model\Config as BackendConfig;
use EMerchantPay\Genesis\Model\Method\Checkout;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test the iframe settings handler
 *
 * Class ConfigTest
 *
 * @covers ConfigBlock
 */
class ConfigTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var BackendConfig|MockObject
     */
    private $backendConfigMock;

    /**
     * @var ConfigBlock
     */
    private $configBlock;

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock       = $this->createMock(Context::class);
        $this->backendConfigMock = $this->createMock(BackendConfig::class);

        $this->configBlock = new ConfigBlock(
            $this->contextMock,
            $this->backendConfigMock
        );
    }
}
