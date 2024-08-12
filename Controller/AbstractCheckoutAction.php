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

namespace EMerchantPay\Genesis\Controller;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Base Checkout Controller Class
 *
 * Class AbstractCheckoutAction
 */
abstract class AbstractCheckoutAction extends AbstractAction
{
    public const ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_PATH = 'checkout/onepage/success';
    public const ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_ARGS = [];

    public const ROUTE_PATTERN_CHECKOUT_CART_PATH = 'checkout/cart';
    public const ROUTE_PATTERN_CHECKOUT_CART_ARGS = [];

    public const ROUTE_PATTERN_CHECKOUT_FRAGMENT_PAYMENT_PATH = 'checkout';
    public const ROUTE_PATTERN_CHECKOUT_FRAGMENT_PAYMENT_ARGS = ['_fragment' => 'payment'];

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var ResultFactory
     */
    private $_resultFactory;

    /**
     * @var UrlInterface
     */
    private $_urlBuilder;

    /**
     * @param Context         $context
     * @param LoggerInterface $logger
     * @param Session         $checkoutSession
     * @param OrderFactory    $orderFactory
     * @param ResultFactory   $resultFactory
     * @param UrlInterface    $urlBuilder
     */
    public function __construct(
        Context         $context,
        LoggerInterface $logger,
        Session         $checkoutSession,
        OrderFactory    $orderFactory,
        ResultFactory   $resultFactory,
        UrlInterface    $urlBuilder
    ) {
        parent::__construct($context, $logger);

        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory    = $orderFactory;
        $this->_resultFactory   = $resultFactory;
        $this->_urlBuilder      = $urlBuilder;
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
     * Get an Instance of the Magento Order Factory
     *
     * It can be used to instantiate an order
     *
     * @return OrderFactory
     */
    protected function getOrderFactory()
    {
        return $this->_orderFactory;
    }

    /**
     * Get an Instance of the current Checkout Order Object
     *
     * @return Order
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
     *
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
     *
     * @param bool $iframeRedirect
     *
     * @return ResponseInterface|Raw
     */
    protected function redirectToCheckoutOnePageSuccess(bool $iframeRedirect)
    {
        return $this->selectResponse(
            $iframeRedirect,
            [
                self::ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_PATH,
                self::ROUTE_PATTERN_CHECKOUT_ONEPAGE_SUCCESS_ARGS
            ]
        );
    }

    /**
     * Does a redirect to the Checkout Cart Page
     *
     * @param bool $iframeRedirect
     *
     * @return ResponseInterface|Raw
     */
    protected function redirectToCheckoutCart(bool $iframeRedirect)
    {
        return $this->selectResponse(
            $iframeRedirect,
            [
                self::ROUTE_PATTERN_CHECKOUT_CART_PATH,
                self::ROUTE_PATTERN_CHECKOUT_CART_ARGS
            ]
        );
    }

    /**
     * Return html code with embedded js in <script> tag to break the iframe jail
     *
     * @param string $redirectPath
     * @param array  $params
     *
     * @return Raw
     */
    private function breakIframeAndRedirect(string $redirectPath, array $params)
    {
        $redirectUrl = $this->_urlBuilder->getUrl($redirectPath, ['_query' => $params]);

        $html = '<html><body>';
        $html .= '<script type="text/javascript">';
        $html .= 'if (window.top !== window.self) {';
        $html .= 'window.top.location.href = "' . $redirectUrl . '";';
        $html .= '} else {';
        $html .= 'window.location.href = "' . $redirectUrl . '";';
        $html .= '}';
        $html .= '</script>';
        $html .= '</body></html>';

        /** @var Raw $result */
        $result = $this->_resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('Content-Type', 'text/html');
        $result->setContents($html);

        return $result;
    }

    /**
     * Return response based on if iframe payment processing is used or not
     *
     * @param bool  $iframeRedirect
     * @param array $returnUrl
     *
     * @return ResponseInterface|Raw
     */
    private function selectResponse(bool $iframeRedirect, array $returnUrl)
    {
        if ($iframeRedirect) {
            return $this->breakIframeAndRedirect(...$returnUrl);
        }

        return $this->_redirect(...$returnUrl);
    }
}
