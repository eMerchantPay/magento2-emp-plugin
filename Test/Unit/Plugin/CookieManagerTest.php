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

namespace EMerchantPay\Genesis\Test\Unit\Plugin;

use EMerchantPay\Genesis\Plugin\CookieManager;
use Magento\Framework\Stdlib\Cookie\CookieMetadata;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use PHPUnit\Framework\TestCase;

/**
 * Class CookieManagerTest
 *
 * @covers \EMerchantPay\Genesis\Plugin\CookieManager
 */
class CookieManagerTest extends TestCase
{
    /**
     * @var CookieManager
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cookieMetadataFactoryMock;

    /**
     * @var PhpCookieManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $phpCookieManagerMock;

    /**
     * @var CookieMetadata|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cookieMetadataMock;

    protected function setUp(): void
    {
        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->phpCookieManagerMock = $this->getMockBuilder(PhpCookieManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieMetadataMock = $this->getMockBuilder(CookieMetadata::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setSameSite'])
            ->addMethods(['setSecure'])
            ->getMock();

        $this->cookieManager = new CookieManager($this->cookieMetadataFactoryMock);
    }

    public function testAroundSetPublicCookieWithNullMetadata()
    {
        $cookieName = 'test_cookie';
        $cookieValue = 'test_value';

        $this->cookieMetadataFactoryMock->expects($this->once())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->cookieMetadataMock);

        $this->cookieMetadataMock->expects($this->once())
            ->method('setSecure')
            ->with(true)
            ->willReturnSelf();

        $this->cookieMetadataMock->expects($this->once())
            ->method('setSameSite')
            ->with('none')
            ->willReturnSelf();

        $proceed = function ($cookieName, $cookieValue, $metadata) {
            return true;
        };

        $result = $this->cookieManager->aroundSetPublicCookie(
            $this->phpCookieManagerMock,
            $proceed,
            $cookieName,
            $cookieValue,
            null
        );

        $this->assertTrue($result);
    }

    public function testAroundSetPublicCookieWithMetadata()
    {
        $cookieName = 'test_cookie';
        $cookieValue = 'test_value';

        $this->cookieMetadataMock->expects($this->once())
            ->method('setSecure')
            ->with(true)
            ->willReturnSelf();

        $this->cookieMetadataMock->expects($this->once())
            ->method('setSameSite')
            ->with('none')
            ->willReturnSelf();

        $proceed = function ($cookieName, $cookieValue, $metadata) {
            return true;
        };

        $result = $this->cookieManager->aroundSetPublicCookie(
            $this->phpCookieManagerMock,
            $proceed,
            $cookieName,
            $cookieValue,
            $this->cookieMetadataMock
        );

        $this->assertTrue($result);
    }
}
