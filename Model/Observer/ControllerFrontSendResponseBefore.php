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

namespace EMerchantPay\Genesis\Model\Observer;

use EMerchantPay\Genesis\Helper\Data;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Webapi\ErrorProcessor;
use Magento\Framework\Webapi\Exception as WebApiException;
use Magento\Framework\Webapi\Rest\Response;

/**
 * Observer Class (called just before the Response on the Front Site is sent)
 * Used to overwrite the Exception on the Checkout Page
 *
 * Class ControllerFrontSendResponseBefore
 */
class ControllerFrontSendResponseBefore implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_moduleHelper;

    /**
     * @var ErrorProcessor
     */
    protected $_errorProcessor;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * SalesOrderPaymentPlaceEnd constructor.
     * @param Data           $moduleHelper
     * @param ErrorProcessor $errorProcessor
     * @param Session        $checkoutSession
     */
    public function __construct(
        Data           $moduleHelper,
        ErrorProcessor $errorProcessor,
        Session        $checkoutSession
    ) {
        $this->_moduleHelper    = $moduleHelper;
        $this->_errorProcessor  = $errorProcessor;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Execute method
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $response = $observer->getEvent()->getData('response');

            if ($response && $this->getShouldOverrideCheckoutException($response)) {
                /** @var Response $response */

                $maskedException = $this->getModuleHelper()->createWebApiException(
                    $this->getEmerchantPayLastCheckoutError(),
                    WebApiException::HTTP_BAD_REQUEST
                );

                $response->setException($maskedException);
                $this->clearEmerchantPayLastCheckoutError();
            }
        // @codingStandardsIgnoreStart
        } catch (Exception $e) {
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            /**
             * Just hide any exception (if occurs) when trying to override exception message
             */
        }
        // @codingStandardsIgnoreEnd
    }

    /**
     * Should we override the Checkout exception
     *
     * @param Response $response
     *
     * @return bool
     */
    protected function getShouldOverrideCheckoutException($response)
    {
        return
            ($this->getEmerchantPayLastCheckoutError()) &&
            ($response instanceof Response) &&
            (method_exists($response, 'isException')) &&
            ($response->isException());
    }

    /**
     * Retrieves the last error message from the session, which has occurred on the checkout page
     *
     * @return mixed
     */
    protected function getEmerchantPayLastCheckoutError()
    {
        return $this->getCheckoutSession()->getEmerchantPayLastCheckoutError();
    }

    /**
     * Clears the last error from the session, which occurs on the checkout page
     *
     * @return void
     */
    protected function clearEmerchantPayLastCheckoutError()
    {
        $this->getCheckoutSession()->setEmerchantPayLastCheckoutError(null);
    }

    /**
     * Return module helper
     *
     * @return Data
     */
    protected function getModuleHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * Return the error processor
     *
     * @return ErrorProcessor
     */
    protected function getErrorProcessor()
    {
        return $this->_errorProcessor;
    }

    /**
     * Return the Checkout session
     *
     * @return Session
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}
