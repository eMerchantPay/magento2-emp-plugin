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

namespace EMerchantPay\Genesis\Controller\Checkout;

/**
 * Front Controller for Checkout Method
 * it does a redirect to the WPF
 * Class Index
 * @package EMerchantPay\Genesis\Controller\Checkout
 */
class Index extends \EMerchantPay\Genesis\Controller\AbstractCheckoutAction
{
    /**
     * Redirect to Genesis WPF
     *
     * @return void
     */
    public function execute()
    {

        $order = $this->getOrder();

        if (isset($order)) {
            $redirectUrl = $this->getCheckoutSession()->getEmerchantPayCheckoutRedirectUrl();

            if (isset($redirectUrl)) {
                $this->getResponse()->setRedirect($redirectUrl);

                return;
            }

            $this->redirectToCheckoutFragmentPayment();
        }
    }
}
