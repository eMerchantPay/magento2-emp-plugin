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

namespace EMerchantPay\Genesis\Controller\Ipn;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Unified IPN controller for all supported EMerchantPay Payment Methods
 * Class Index
 */
class Index extends \EMerchantPay\Genesis\Controller\AbstractAction implements CsrfAwareActionInterface
{
    /**
     * Skip CSRF validation for the received request from the Gateway
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * No validation required
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Get the name of the IPN Class, used to handle the posted Notification. It is separated per Payment Method
     *
     * @return null|string
     */
    protected function getIpnClassName()
    {
        switch (true) {
            case $this->isPostRequestExists('wpf_unique_id'):
                $className = 'CheckoutIpn';
                break;
            default:
                $className = null;
        }

        return $className;
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
                $this->getResponse()->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);

                return;
            }

            $ipn = $this->getObjectManager()->create(
                "EMerchantPay\\Genesis\\Model\\Ipn\\{$ipnClassName}",
                ['data' => $postValues]
            );

            $responseBody = $ipn->handleGenesisNotification();
            $this
                ->getResponse()
                    ->setHeader('Content-type', 'application/xml')
                    ->setBody($responseBody)
                    ->setHttpResponseCode(\Magento\Framework\Webapi\Response::HTTP_OK)
                    ->sendResponse();
        } catch (\Exception $e) {
            $this->getLogger()->critical($e);
            $this->getResponse()->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR);
        }
    }
}
