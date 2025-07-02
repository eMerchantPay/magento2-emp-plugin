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

namespace EMerchantPay\Genesis\Controller\Checkout;

use EMerchantPay\Genesis\Controller\AbstractAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session;
use Psr\Log\LoggerInterface;

/**
 * Front Controller for RedirectUrl Method
 * it gets the WPF URL
 *
 * Class RedirectUrl
 */
class RedirectUrl extends AbstractAction
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @param Context         $context
     * @param LoggerInterface $logger
     * @param Session         $checkoutSession
     * @param JsonFactory     $resultJsonFactory
     */
    public function __construct(
        Context         $context,
        LoggerInterface $logger,
        Session         $checkoutSession,
        JsonFactory     $resultJsonFactory
    ) {
        parent::__construct($context, $logger);

        $this->checkoutSession = $checkoutSession;
        $this->jsonFactory     = $resultJsonFactory;
    }

    /**
     * Return the WPF URL
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $url    = $this->checkoutSession->getEmerchantPayCheckoutRedirectUrl();

        return $result->setData(['redirectUrl' => $url]);
    }
}
