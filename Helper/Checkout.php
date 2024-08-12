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

namespace EMerchantPay\Genesis\Helper;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Checkout workflow helper
 *
 * Class Checkout
 */
class Checkout
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @param Session                  $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Session                  $checkoutSession,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderRepository = $orderRepository;
    }

    /**
     * Get an Instance of the Magento Checkout Session
     *
     * @return Session
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * Cancel last placed order with specified comment message
     *
     * @param string $comment Comment appended to order history
     *
     * @return bool True if order cancelled, false otherwise
     *
     * @throws LocalizedException
     */
    public function cancelCurrentOrder($comment)
    {
        $order = $this->getCheckoutSession()->getLastRealOrder();
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment);
            $this->_orderRepository->save($order);

            return true;
        }

        return false;
    }

    /**
     * Restores quote
     *
     * @return bool
     */
    public function restoreQuote()
    {
        return $this->getCheckoutSession()->restoreQuote();
    }
}
