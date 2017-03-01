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

namespace EMerchantPay\Genesis\Test\Unit\Controller\Checkout;

use EMerchantPay\Genesis\Controller\Checkout\Redirect as RedirectController;

/**
 * Class RedirectTest
 * @covers \EMerchantPay\Genesis\Controller\Checkout\Redirect
 * @package EMerchantPay\Genesis\Test\Unit\Controller\Checkout
 */
class RedirectTest extends \EMerchantPay\Genesis\Test\Unit\Controller\AbstractControllerTest
{
    /**
     * Gets controller's fully qualified class name
     * @return string
     */
    protected function getControllerClassName()
    {
        return RedirectController::class;
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Checkout\Redirect::execute()
     */
    public function testExecuteFailWhenLastRealOrderIdIsNull()
    {
        $this->httpRequestMock->expects(self::once())
            ->method('getParam')
            ->with('action')
            ->willReturn(\EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_SUCCESS);

        $this->checkoutSessionMock->expects(self::atLeastOnce())
            ->method('getLastRealOrderId')
            ->willReturn(null);

        $this->orderMock->expects(self::never())
            ->method('getId');

        $this->redirectResponseMock->expects(self::never())
            ->method('redirect');

        $this->getControllerInstance()->execute();
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Checkout\Redirect::execute()
     */
    public function testExecuteSuccessReturnAction()
    {
        $this->httpRequestMock->expects(self::once())
            ->method('getParam')
            ->with('action')
            ->willReturn(\EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_SUCCESS);

        $this->checkoutSessionMock->expects(self::atLeastOnce())
            ->method('getLastRealOrderId')
            ->willReturn(1);

        $this->orderMock->expects(self::never())
            ->method('getId');

        $this->redirectResponseMock->expects(self::atLeastOnce())
            ->method('redirect')
            ->with(
                $this->getControllerInstance()->getResponse(),
                \EMerchantPay\Genesis\Controller\AbstractCheckoutAction::ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_PATH,
                \EMerchantPay\Genesis\Controller\AbstractCheckoutAction::ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_ARGS
            );

        $this->getControllerInstance()->execute();
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Checkout\Redirect::execute()
     */
    public function testExecuteCancelReturnAction()
    {
        $this->httpRequestMock->expects(self::once())
            ->method('getParam')
            ->with('action')
            ->willReturn(\EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_CANCEL);

        $this->checkoutSessionMock->expects(self::never())
            ->method('getLastRealOrderId');

        $this->orderMock->expects(self::never())
            ->method('getId');

        $this->redirectResponseMock->expects(self::once())
            ->method('redirect')
            ->with(
                $this->getControllerInstance()->getResponse(),
                \EMerchantPay\Genesis\Controller\AbstractCheckoutAction::ROUTE_PATTERN_CHECKOUT_CART_PATH,
                \EMerchantPay\Genesis\Controller\AbstractCheckoutAction::ROUTE_PATTERN_CHECKOUT_CART_ARGS
            );

        $this->getControllerInstance()->execute();
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Checkout\Redirect::execute()
     */
    public function testExecuteFailureReturnAction()
    {
        $this->httpRequestMock->expects(self::once())
            ->method('getParam')
            ->with('action')
            ->willReturn(\EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_FAILURE);

        $this->checkoutSessionMock->expects(self::never())
            ->method('getLastRealOrderId');

        $this->orderMock->expects(self::never())
            ->method('getId');

        $this->redirectResponseMock->expects(self::once())
            ->method('redirect')
            ->with(
                $this->getControllerInstance()->getResponse(),
                \EMerchantPay\Genesis\Controller\AbstractCheckoutAction::ROUTE_PATTERN_CHECKOUT_CART_PATH,
                \EMerchantPay\Genesis\Controller\AbstractCheckoutAction::ROUTE_PATTERN_CHECKOUT_CART_ARGS
            );

        $this->getControllerInstance()->execute();
    }

    /**
     * @covers \EMerchantPay\Genesis\Controller\Checkout\Redirect::execute()
     */
    public function testExecuteUnsupportedReturnAction()
    {
        $this->httpRequestMock->expects(self::once())
            ->method('getParam')
            ->with('action')
            ->willReturn('');

        $this->checkoutSessionMock->expects(self::never())
            ->method('getLastRealOrderId');

        $this->orderMock->expects(self::never())
            ->method('getId');

        $this->redirectResponseMock->expects(self::never())
            ->method('redirect');

        $this->getControllerInstance()->execute();
    }
}
