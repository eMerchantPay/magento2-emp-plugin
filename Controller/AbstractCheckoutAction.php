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

namespace EMerchantPay\Genesis\Controller;

/**
 * Base Checkout Controller Class
 * Class AbstractCheckoutAction
 * @package EMerchantPay\Genesis\Controller
 */
abstract class AbstractCheckoutAction extends \EMerchantPay\Genesis\Controller\AbstractAction
{
    const ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_PATH = 'checkout/onepage/success';
    const ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_ARGS = [];

    const ROUTE_PATTERN_CHECKOUT_CART_PATH = 'checkout/cart';
    const ROUTE_PATTERN_CHECKOUT_CART_ARGS = [];

    const ROUTE_PATTERN_CHECKOUT_FRAGMENT_PAYMENT_PATH = 'checkout';
    const ROUTE_PATTERN_CHECKOUT_FRAGMENT_PAYMENT_ARGS = ['_fragment' => 'payment'];

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        parent::__construct($context, $logger);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
    }

    /**
     * Get an Instance of the Magento Checkout Session
     * @return \Magento\Checkout\Model\Session
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * Get an Instance of the Magento Order Factory
     * It can be used to instantiate an order
     * @return \Magento\Sales\Model\OrderFactory
     */
    protected function getOrderFactory()
    {
        return $this->_orderFactory;
    }

    /**
     * Get an Instance of the current Checkout Order Object
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        $orderId = $this->getCheckoutSession()->getLastRealOrderId();

        if (!isset($orderId)) {
            return null;
        }

        $order = $this->getOrderFactory()->create()->loadByIncrementId(
            $orderId
        );

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    /**
     * Does a redirect to the Checkout Payment Page
     * @return void
     */
    protected function redirectToCheckoutFragmentPayment()
    {
        $this->_redirect(
            self::ROUTE_PATTERN_CHECKOUT_FRAGMENT_PAYMENT_PATH,
            self::ROUTE_PATTERN_CHECKOUT_FRAGMENT_PAYMENT_ARGS
        );
    }

    /**
     * Does a redirect to the Checkout Success Page
     * @return void
     */
    protected function redirectToCheckoutOnePageSuccess()
    {
        $this->_redirect(
            self::ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_PATH,
            self::ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_ARGS
        );
    }

    /**
     * Does a redirect to the Checkout Cart Page
     * @return void
     */
    protected function redirectToCheckoutCart()
    {
        $this->_redirect(
            self::ROUTE_PATTERN_CHECKOUT_CART_PATH,
            self::ROUTE_PATTERN_CHECKOUT_CART_ARGS
        );
    }
}
