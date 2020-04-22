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

namespace EMerchantPay\Genesis\Model\Traits;

use Magento\Payment\Model\InfoInterface;

/**
 * Trait PaymentMethodBehaviour
 *
 * Describes the capabilities of the payment methods
 *
 * @package EMerchantPay\Genesis\Model\Traits
 */
trait PaymentMethodBehaviour
{
    /**
     * @return bool
     */
    public function canOrder()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canAuthorize()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canCapture()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canCapturePartial()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canCaptureOnce()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canRefund()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canRefundPartialPerInvoice()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canVoid()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canUseInternal()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canUseCheckout()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canEdit()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canFetchTransactionInfo()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isGateway()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isOffline()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isInitializeNeeded()
    {
        return false;
    }

    /**
     * @param string $country
     *
     * @return bool
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function canUseForCurrency($currencyCode)
    {
        return $this->getModuleHelper()->isCurrencyAllowed(
            $this->getCode(),
            $currencyCode
        );
    }


    /**
     * @return bool
     */
    public function canReviewPayment()
    {
        return false;
    }

    /**
     * @param InfoInterface $payment
     * @param string $transactionId
     *
     * @return array
     * @SuppressWarnings(PHPMD)
     * @codingStandardsIgnoreStart
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        return [];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this|MethodInterface
     * @SuppressWarnings(PHPMD)
     * @codingStandardsIgnoreStart
     */
    public function initialize($paymentAction, $stateObject)
    {
        return $this;
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param InfoInterface $payment
     *
     * @return bool|false
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD)
     * @codingStandardsIgnoreStart
     */
    public function acceptPayment(InfoInterface $payment)
    {
        if (!$this->canReviewPayment()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The payment review action is unavailable.'));
        }
        return false;
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param InfoInterface $payment
     *
     * @return bool|false
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD)
     * @codingStandardsIgnoreStart
     */
    public function denyPayment(InfoInterface $payment)
    {
        if (!$this->canReviewPayment()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The payment review action is unavailable.'));
        }
        return false;
        // @codingStandardsIgnoreEnd
    }
}
