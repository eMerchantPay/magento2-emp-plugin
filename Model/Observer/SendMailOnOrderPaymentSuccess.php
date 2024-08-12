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

use EMerchantPay\Genesis\Model\Config;
use EMerchantPay\Genesis\Model\Method\Checkout as MethodCheckout;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Observer Class called on checkout_onepage_controller_success_action_sendmail event
 *
 * Class SendMailOnOrderPaymentSuccess
 */
class SendMailOnOrderPaymentSuccess implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $_configHelper;

    /**
     * @var OrderFactory
     */
    protected $orderModel;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @param OrderFactory $orderModel
     * @param OrderSender  $orderSender
     * @param Session      $checkoutSession
     * @param Config       $configHelper
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        OrderFactory $orderModel,
        OrderSender  $orderSender,
        Session      $checkoutSession,
        Config       $configHelper
    ) {
        $this->orderModel      = $orderModel;
        $this->orderSender     = $orderSender;
        $this->checkoutSession = $checkoutSession;
        $this->_configHelper   = $configHelper;
    }

    /**
     * Execute method
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();

        if (empty($orderIds)) {
            return;
        }

        $order = $this->orderModel->create()->load($orderIds[0]);
        $methodCode = $order->getPayment()->getMethodInstance()->getCode();

        if (!in_array($methodCode, [MethodCheckout::CODE])) {
            return;
        }

        if ($this->_configHelper->getPaymentConfirmationEmailEnabled($methodCode)) {
            $this->orderSender->send($order, true);
        }
    }
}
