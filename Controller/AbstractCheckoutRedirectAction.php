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

use EMerchantPay\Genesis\Helper\Checkout;
use EMerchantPay\Genesis\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Base Checkout Redirect Controller Class
 * Class AbstractCheckoutRedirectAction
 * @package EMerchantPay\Genesis\Controller
 */
abstract class AbstractCheckoutRedirectAction extends \EMerchantPay\Genesis\Controller\AbstractCheckoutAction
{
    /**
     * @var Checkout
     */
    protected $_checkoutHelper;

    /**
     * @param Context         $context
     * @param LoggerInterface $logger
     * @param Session         $checkoutSession
     * @param OrderFactory    $orderFactory
     * @param Checkout        $checkoutHelper
     * @param ResultFactory   $resultFactory
     * @param UrlInterface    $urlBuilder
     */
    public function __construct(
        Context         $context,
        LoggerInterface $logger,
        Session         $checkoutSession,
        OrderFactory    $orderFactory,
        Checkout        $checkoutHelper,
        ResultFactory   $resultFactory,
        UrlInterface    $urlBuilder
    ) {
        parent::__construct($context, $logger, $checkoutSession, $orderFactory, $resultFactory, $urlBuilder);
        $this->_checkoutHelper = $checkoutHelper;
    }

    /**
     * Get an Instance of the Magento Checkout Helper
     *
     * @return Checkout
     */
    protected function getCheckoutHelper()
    {
        return $this->_checkoutHelper;
    }

    /**
     * Handle Success Action
     *
     * @param bool $iframeRedirect
     *
     * @return ResponseInterface|Raw|null
     */
    protected function executeSuccessAction(bool $iframeRedirect)
    {
        $response = null;
        if ($this->getCheckoutSession()->getLastRealOrderId()) {
            $this->getMessageManager()->addSuccess(__("Your payment is complete"));

            $response = $this->redirectToCheckoutOnePageSuccess($iframeRedirect);
        }

        return $response;
    }

    /**
     * Handle Cancel Action from Payment Gateway
     *
     * @param bool $iframeRedirect
     *
     * @return ResponseInterface|Raw
     */
    protected function executeCancelAction(bool $iframeRedirect)
    {
        $this->getCheckoutHelper()->cancelCurrentOrder('');
        $this->getCheckoutHelper()->restoreQuote();

        return $this->redirectToCheckoutCart($iframeRedirect);
    }

    /**
     * Get the redirect action
     *      - success
     *      - cancel
     *      - failure
     *
     * @return string
     */
    protected function getReturnAction()
    {
        return $this->getRequest()->getParam('action');
    }

    /**
     * Select redirect action based on the URL parameter
     *
     * @param string $action
     * @param bool $iframeRedirect
     *
     * @return ResponseInterface|void|null
     */
    protected function redirectAction(string $action, bool $iframeRedirect = false)
    {
        switch ($action) {
            case Data::ACTION_RETURN_SUCCESS:
                return $this->executeSuccessAction($iframeRedirect);

            case Data::ACTION_RETURN_CANCEL:
                $this->getMessageManager()->addWarning(
                    __("You have successfully canceled your order")
                );

                return $this->executeCancelAction($iframeRedirect);

            case Data::ACTION_RETURN_FAILURE:
                $this->getMessageManager()->addError(
                    __("Please, check your input and try again!")
                );

                return $this->executeCancelAction($iframeRedirect);

            default:
                $this->getResponse()->setHttpResponseCode(
                    Exception::HTTP_UNAUTHORIZED
                );
        }
    }
}
