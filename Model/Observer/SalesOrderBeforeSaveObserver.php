<?php
/*
 * Copyright (C) 2021 emerchantpay Ltd.
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
 * @copyright   2021 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Model\Observer;

use EMerchantPay\Genesis\Model\Config;
use EMerchantPay\Genesis\Model\Method\Checkout as GenesisCheckoutPaymentMethod;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

class SalesOrderBeforeSaveObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $_configHelper;

    /**
     * @param Config $configHelper
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        Config $configHelper
    ) {
        $this->_configHelper = $configHelper;
    }

    /**
     * Execute method
     *
     * @param Observer $observer
     *
     * @return $this
     *
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order      = $observer->getEvent()->getOrder();
        $methodCode = $order->getPayment()->getMethodInstance()->getCode();

        if (!$this->_configHelper->getPaymentConfirmationEmailEnabled($methodCode)) {
            return $this;
        }

        if (in_array($methodCode, [GenesisCheckoutPaymentMethod::CODE])) {
            $order->setCanSendNewEmailFlag(false);
            $order->setSendEmail(false);
        }

        return $this;
    }
}
