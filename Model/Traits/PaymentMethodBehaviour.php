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

namespace EMerchantPay\Genesis\Model\Traits;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;

/**
 * Trait PaymentMethodBehaviour
 *
 * Describes the capabilities of the payment methods
 */
trait PaymentMethodBehaviour
{
    /**
     * Ordering is possible
     *
     * @return bool
     */
    public function canOrder()
    {
        return true;
    }

    /**
     * Authorize is possible
     *
     * @return bool
     */
    public function canAuthorize()
    {
        return true;
    }

    /**
     * Capture is possible
     *
     * @return bool
     */
    public function canCapture()
    {
        return true;
    }

    /**
     * Partial capture is possible
     *
     * @return bool
     */
    public function canCapturePartial()
    {
        return true;
    }

    /**
     * Capture once is possible
     *
     * @return bool
     */
    public function canCaptureOnce()
    {
        return true;
    }

    /**
     * Refund is possible
     *
     * @return bool
     */
    public function canRefund()
    {
        return true;
    }

    /**
     * Partial refund per invoice is possible
     *
     * @return bool
     */
    public function canRefundPartialPerInvoice()
    {
        return true;
    }

    /**
     * Void is possible
     *
     * @return bool
     */
    public function canVoid()
    {
        return true;
    }

    /**
     * Internal use is possible
     *
     * @return bool
     */
    public function canUseInternal()
    {
        return true;
    }

    /**
     * Checkout is possible
     *
     * @return bool
     */
    public function canUseCheckout()
    {
        return true;
    }

    /**
     * Edit is possible
     *
     * @return bool
     */
    public function canEdit()
    {
        return true;
    }

    /**
     * Transaction info can be fetched
     *
     * @return bool
     */
    public function canFetchTransactionInfo()
    {
        return false;
    }

    /**
     * It is Gateway
     *
     * @return bool
     */
    public function isGateway()
    {
        return true;
    }

    /**
     * It is online
     *
     * @return bool
     */
    public function isOffline()
    {
        return false;
    }

    /**
     * No initialization needed
     *
     * @return bool
     */
    public function isInitializeNeeded()
    {
        return false;
    }

    /**
     * Can be used in every country
     *
     * @param string $country
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD)
     * @codingStandardsIgnoreStart
     */
    public function canUseForCountry($country)
    {
        return true;
        // @codingStandardsIgnoreEnd
    }

    /**
     * Checks base currency against the allowed currency
     *
     * @param string $currencyCode
     *
     * @return bool
     *
     * @throws LocalizedException
     */
    public function canUseForCurrency($currencyCode)
    {
        return $this->getModuleHelper()->isCurrencyAllowed(
            $this->getCode(),
            $currencyCode
        );
    }


    /**
     * Can't review payment
     *
     * @return bool
     */
    public function canReviewPayment()
    {
        return false;
    }

    /**
     * Fetch the transaction information
     *
     * @param InfoInterface $payment
     * @param string        $transactionId
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD)
     * @codingStandardsIgnoreStart
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        return [];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Initialization
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this|MethodInterface
     *
     * @SuppressWarnings(PHPMD)
     * @codingStandardsIgnoreStart
     */
    public function initialize($paymentAction, $stateObject)
    {
        return $this;
        // @codingStandardsIgnoreEnd
    }

    /**
     * Accept the payment
     *
     * @param InfoInterface $payment
     *
     * @return bool|false
     *
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD)
     * @codingStandardsIgnoreStart
     */
    public function acceptPayment(InfoInterface $payment)
    {
        if (!$this->canReviewPayment()) {
            throw new LocalizedException(__('The payment review action is unavailable.'));
        }

        return false;
        // @codingStandardsIgnoreEnd
    }

    /**
     * Deny the payment
     *
     * @param InfoInterface $payment
     *
     * @return bool|false
     *
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD)
     * @codingStandardsIgnoreStart
     */
    public function denyPayment(InfoInterface $payment)
    {
        if (!$this->canReviewPayment()) {
            throw new LocalizedException(__('The payment review action is unavailable.'));
        }

        return false;
        // @codingStandardsIgnoreEnd
    }
}
