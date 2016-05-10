<?php
/*
 * Copyright (C) 2016 eMerchantPay Ltd.
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
 * @copyright   2016 eMerchantPay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Controller\Ipn;

/**
 * Unified IPN controller for all supported EMerchantPay Payment Methods
 * Class Index
 * @package EMerchantPay\Genesis\Controller\Ipn
 */
class Index extends \EMerchantPay\Genesis\Controller\AbstractAction
{
    /**
     * Get the name of the IPN Class, used to handle the posted Notification
     * It is separated per Payment Method
     *
     * @return null|string
     */
    protected function getIpnClassName()
    {
        if ($this->isPostRequestExists('wpf_unique_id')) {
            return "CheckoutIpn";
        } else {
            return null;
        }
    }

    /**
     * Instantiate IPN model and pass IPN request to it
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        try {
            $postValues = $this->getPostRequest();

            $ipnClassName = $this->getIpnClassName();

            if (!isset($ipnClassName)) {
                $this->getResponse()->setHttpResponseCode(403);
            } else {
                $ipn = $this->getObjectManager()->create(
                    "EMerchantPay\\Genesis\\Model\\Ipn\\{$ipnClassName}",
                    ['data' => $postValues]
                );

                $responseBody = $ipn->handleGenesisNotification();
                $this->getResponse()
                    ->setHeader('Content-type', 'application/xml')
                    ->setBody($responseBody)
                    ->setHttpResponseCode(200)
                    ->sendResponse();
            }
        } catch (\Exception $e) {
            $this->getLogger()->critical($e);
            $this->getResponse()->setHttpResponseCode(500);
        }
    }
}
