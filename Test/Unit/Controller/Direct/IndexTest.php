<?php
/*
 * Copyright (C) 2017 eMerchantPay Ltd.
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
 * @author      eMerchantPay
 * @copyright   2017 eMerchantPay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Test\Unit\Controller\Direct;

use EMerchantPay\Genesis\Controller\Direct\Index as IndexController;

/**
 * Class IndexTest
 * @covers \EMerchantPay\Genesis\Controller\Direct\Index
 * @package EMerchantPay\Genesis\Test\Unit\Controller\Direct
 */
class IndexTest extends \EMerchantPay\Genesis\Test\Unit\Controller\AbstractControllerTest
{
    /**
     * Gets controller's fully qualified class name
     * @return string
     */
    protected function getControllerClassName()
    {
        return IndexController::class;
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Direct\Index::execute()
     */
    public function testExecuteFailWhenLastRealOrderIdIsNull()
    {
        $this->checkoutSessionMock->expects(self::once())
            ->method('getLastRealOrderId')
            ->willReturn(null);

        $this->orderMock->expects(self::never())
            ->method('getId');

        $this->checkoutSessionMock->expects(self::never())
            ->method('getEmerchantPayCheckoutRedirectUrl');

        $this->responseInterfaceMock->expects(self::never())
            ->method('setRedirect');

        $this->redirectResponseMock->expects(self::never())
            ->method('redirect');

        $this->getControllerInstance()->execute();
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Direct\Index::execute()
     */
    public function testExecuteFailWhenRedirectUrlIsNull()
    {
        $this->checkoutSessionMock->expects(self::once())
            ->method('getLastRealOrderId')
            ->willReturn(1);

        $this->orderMock->expects(self::once())
            ->method('getId')
            ->willReturn(1);

        $this->checkoutSessionMock->expects(self::once())
            ->method('getEmerchantPayCheckoutRedirectUrl')
            ->willReturn(null);

        $this->responseInterfaceMock->expects(self::never())
            ->method('setRedirect');

        $this->redirectResponseMock->expects(self::once())
            ->method('redirect')
            ->with(
                $this->getControllerInstance()->getResponse(),
                \EMerchantPay\Genesis\Controller\AbstractCheckoutAction::ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_PATH,
                \EMerchantPay\Genesis\Controller\AbstractCheckoutAction::ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_ARGS
            );

        $this->getControllerInstance()->execute();
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Direct\Index::execute()
     */
    public function testExecuteSuccessfulRedirectToTheRedirectUrl()
    {
        $redirectUrl='https://sample.redirect.url/sample/path';

        $this->checkoutSessionMock->expects(self::once())
            ->method('getLastRealOrderId')
            ->willReturn(1);

        $this->orderMock->expects(self::once())
            ->method('getId')
            ->willReturn(1);

        $this->checkoutSessionMock->expects(self::once())
            ->method('getEmerchantPayCheckoutRedirectUrl')
            ->willReturn($redirectUrl);

        $this->responseInterfaceMock->expects(self::once())
            ->method('setRedirect')
            ->with($redirectUrl);

        $this->redirectResponseMock->expects(self::never())
            ->method('redirect');

        $this->getControllerInstance()->execute();
    }
}
