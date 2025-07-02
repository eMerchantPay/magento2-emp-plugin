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

namespace EMerchantPay\Genesis\Test\Unit\Model\Service;

use EMerchantPay\Genesis\Model\Config;
use EMerchantPay\Genesis\Model\Service\MultiCurrencyProcessingService;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class MultiCurrencyProcessingServiceTest
 *
 * Tests for MultiCurrencyProcessingService class
 */
class MultiCurrencyProcessingServiceTest extends TestCase
{
    /**
     * @var MultiCurrencyProcessingService
     */
    private $multiCurrencyProcessingService;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrencyInterfaceMock;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var string
     */
    private $methodCode = 'payment_method_code';

    /**
     * Common initialization for the tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->priceCurrencyInterfaceMock = $this->createMock(PriceCurrencyInterface::class);
        $this->orderMock = $this->createMock(Order::class);

        $this->multiCurrencyProcessingService = new MultiCurrencyProcessingService(
            $this->configMock,
            $this->priceCurrencyInterfaceMock
        );

        // Set the method code
        $this->multiCurrencyProcessingService->setMethodCode($this->methodCode);
    }

    /**
     * Test getWpfAmount method with multi-currency processing enabled
     *
     * @return void
     */
    public function testGetWpfAmountWithMultiCurrencyProcessingEnabled()
    {
        $this->configMock->expects($this->once())
            ->method('isFlagChecked')
            ->with($this->methodCode, 'multi_currency_processing')
            ->willReturn(true);

        $baseGrandTotal = 100.00;
        $this->orderMock->expects($this->once())
            ->method('getBaseGrandTotal')
            ->willReturn($baseGrandTotal);

        $result = $this->multiCurrencyProcessingService->getWpfAmount($this->orderMock);

        $this->assertEquals($baseGrandTotal, $result);
    }

    /**
     * Test getWpfAmount method with multi-currency processing disabled
     *
     * @return void
     */
    public function testGetWpfAmountWithMultiCurrencyProcessingDisabled()
    {
        $this->configMock->expects($this->once())
            ->method('isFlagChecked')
            ->with($this->methodCode, 'multi_currency_processing')
            ->willReturn(false);

        $totalDue = 150.00;
        $this->orderMock->expects($this->once())
            ->method('getTotalDue')
            ->willReturn($totalDue);

        $result = $this->multiCurrencyProcessingService->getWpfAmount($this->orderMock);

        $this->assertEquals($totalDue, $result);
    }

    /**
     * Test getOrderAmount method with multi-currency processing enabled
     *
     * @return void
     */
    public function testGetOrderAmountWithMultiCurrencyProcessingEnabled()
    {
        $this->configMock->expects($this->once())
            ->method('isFlagChecked')
            ->with($this->methodCode, 'multi_currency_processing')
            ->willReturn(true);

        $grandTotal = 200.00;
        $this->orderMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn($grandTotal);

        $amount = 250.00;

        $result = $this->multiCurrencyProcessingService->getOrderAmount($this->orderMock, $amount);

        $this->assertEquals($grandTotal, $result);
    }

    /**
     * Test getOrderAmount method with multi-currency processing disabled
     *
     * @return void
     */
    public function testGetOrderAmountWithMultiCurrencyProcessingDisabled()
    {
        $this->configMock->expects($this->once())
            ->method('isFlagChecked')
            ->with($this->methodCode, 'multi_currency_processing')
            ->willReturn(false);

        $amount = 250.00;

        $result = $this->multiCurrencyProcessingService->getOrderAmount($this->orderMock, $amount);

        $this->assertEquals($amount, $result);
    }

    /**
     * Test getOrderCurrency method with multi-currency processing enabled
     *
     * @return void
     */
    public function testGetOrderCurrencyWithMultiCurrencyProcessingEnabled()
    {
        $this->configMock->expects($this->once())
            ->method('isFlagChecked')
            ->with($this->methodCode, 'multi_currency_processing')
            ->willReturn(true);

        $orderCurrencyCode = 'EUR';
        $this->orderMock->expects($this->once())
            ->method('getOrderCurrencyCode')
            ->willReturn($orderCurrencyCode);

        $result = $this->multiCurrencyProcessingService->getOrderCurrency($this->orderMock);

        $this->assertEquals($orderCurrencyCode, $result);
    }

    /**
     * Test getOrderCurrency method with multi-currency processing disabled
     *
     * @return void
     */
    public function testGetOrderCurrencyWithMultiCurrencyProcessingDisabled()
    {
        $this->configMock->expects($this->once())
            ->method('isFlagChecked')
            ->with($this->methodCode, 'multi_currency_processing')
            ->willReturn(false);

        $baseCurrencyCode = 'USD';
        $this->orderMock->expects($this->once())
            ->method('getBaseCurrencyCode')
            ->willReturn($baseCurrencyCode);

        $result = $this->multiCurrencyProcessingService->getOrderCurrency($this->orderMock);

        $this->assertEquals($baseCurrencyCode, $result);
    }

    /**
     * Test convertAmount method with multi-currency processing enabled
     *
     * @return void
     */
    public function testConvertAmountWithMultiCurrencyProcessingEnabled()
    {
        $this->configMock->expects($this->once())
            ->method('isFlagChecked')
            ->with($this->methodCode, 'multi_currency_processing')
            ->willReturn(true);

        $amount            = 100.00;
        $convertedAmount   = 120.00;
        $orderCurrencyCode = 'EUR';

        $storeMock = $this->createMock(StoreInterface::class);

        $this->orderMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->orderMock->expects($this->once())
            ->method('getOrderCurrencyCode')
            ->willReturn($orderCurrencyCode);

        $this->priceCurrencyInterfaceMock->expects($this->once())
            ->method('convertAndRound')
            ->with($amount, $storeMock, $orderCurrencyCode)
            ->willReturn($convertedAmount);

        $result = $this->multiCurrencyProcessingService->convertAmount($amount, $this->orderMock);

        $this->assertEquals($convertedAmount, $result);
    }

    /**
     * Test convertAmount method with multi-currency processing disabled
     *
     * @return void
     */
    public function testConvertAmountWithMultiCurrencyProcessingDisabled()
    {
        $this->configMock->expects($this->once())
            ->method('isFlagChecked')
            ->with($this->methodCode, 'multi_currency_processing')
            ->willReturn(false);

        $amount = 100.00;

        $result = $this->multiCurrencyProcessingService->convertAmount($amount, $this->orderMock);

        $this->assertEquals($amount, $result);
    }

    /**
     * Test isMultiCurrencyProcessing method
     *
     * @return void
     */
    public function testIsMultiCurrencyProcessing()
    {
        $this->configMock->expects($this->once())
            ->method('isFlagChecked')
            ->with($this->methodCode, 'multi_currency_processing')
            ->willReturn(true);

        $result = $this->multiCurrencyProcessingService->isMultiCurrencyProcessing();

        $this->assertTrue($result);
    }

    /**
     * Test isMultiCurrencyProcessing method
     *
     * @return void
     */
    public function testIsMultiCurrencyProcessingDisabled()
    {
        $this->configMock->expects($this->once())
            ->method('isFlagChecked')
            ->with($this->methodCode, 'multi_currency_processing')
            ->willReturn(false);

        $result = $this->multiCurrencyProcessingService->isMultiCurrencyProcessing();

        $this->assertFalse($result);
    }
}
