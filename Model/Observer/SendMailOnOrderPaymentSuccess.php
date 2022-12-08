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

namespace EMerchantPay\Genesis\Model\Observer;

use EMerchantPay\Genesis\Model\Method\Checkout;
use Magento\Framework\Event\ObserverInterface;

/**
 * Observer Class called on checkout_onepage_controller_success_action_sendmail event
 *
 * Class SendMailOnOrderPaymentSuccess
 * @package EMerchantPay\Genesis\Model\Observer
 */
class SendMailOnOrderPaymentSuccess implements ObserverInterface
{
    /**
     * @var \EMerchantPay\Genesis\Model\Config
     */
    protected $_configHelper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderModel;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @param \Magento\Sales\Model\OrderFactory $orderModel
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \EMerchantPay\Genesis\Model\Config $configHelper
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderModel,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Checkout\Model\Session $checkoutSession,
        \EMerchantPay\Genesis\Model\Config $configHelper
    ) {
        $this->orderModel = $orderModel;
        $this->orderSender = $orderSender;
        $this->checkoutSession = $checkoutSession;
        $this->_configHelper = $configHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();

        if (empty($orderIds)) {
            return;
        }

        $order = $this->orderModel->create()->load($orderIds[0]);
        $methodCode = $order->getPayment()->getMethodInstance()->getCode();

        if (!in_array($methodCode, [Checkout::CODE])) {
            return;
        }

        if ($this->_configHelper->getPaymentConfirmationEmailEnabled($methodCode)) {
            $this->orderSender->send($order, true);
        }
    }
}
