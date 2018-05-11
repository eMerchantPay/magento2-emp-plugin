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

namespace EMerchantPay\Genesis\Model\Ipn;

/**
 * Checkout Method IPN Handler Class
 * Class CheckoutIpn
 * @package EMerchantPay\Genesis\Model\Ipn
 */
class CheckoutIpn extends \EMerchantPay\Genesis\Model\Ipn\AbstractIpn
{
    /**
     * @return string
     */
    protected function getPaymentMethodCode()
    {
        return \EMerchantPay\Genesis\Model\Method\Checkout::CODE;
    }

    /**
     * Update Pending Transactions and Order Status
     * @param \stdClass $responseObject
     * @throws \Exception
     */
    protected function processNotification($responseObject)
    {
        $payment = $this->getPayment();

        $this->getModuleHelper()->updateTransactionAdditionalInfo(
            $responseObject->unique_id,
            $responseObject,
            true
        );

        if (isset($responseObject->payment_transaction)) {
            $payment_transaction = $responseObject->payment_transaction;

            $payment
                ->setLastTransId(
                    $payment_transaction->unique_id
                )
                ->setTransactionId(
                    $payment_transaction->unique_id
                )
                ->setParentTransactionId(
                    $responseObject->unique_id
                )
                ->setIsTransactionPending(
                    $this->getShouldSetCurrentTranPending(
                        $payment_transaction
                    )
                )
                ->setShouldCloseParentTransaction(
                    true
                )
                ->setIsTransactionClosed(
                    $this->getShouldCloseCurrentTransaction(
                        $payment_transaction
                    )
                )
                ->setPreparedMessage(
                    $this->createIpnComment(
                        $payment_transaction->message
                    )
                )
                ->resetTransactionAdditionalInfo(

                );

            $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
                $payment,
                $payment_transaction
            );

            $this->registerPaymentNotification(
                $payment,
                $payment_transaction
            );

            $payment->save();
        }

        $this->getModuleHelper()->setOrderState(
            $this->getOrder(),
            isset($payment_transaction)
                ? $payment_transaction->status
                : $responseObject->status
        );
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $payment
     * @param \stdClass $payment_transaction
     */
    protected function registerPaymentNotification(
        \Magento\Sales\Api\Data\OrderPaymentInterface $payment,
        \stdClass $payment_transaction
    ) {
        $transactionType = $payment_transaction->transaction_type;

        if ($this->getModuleHelper()->getShouldCreateAuthNotification($transactionType)) {
            $payment->registerAuthorizationNotification(
                $payment_transaction->amount
            );

            return;
        }

        if ($this->getModuleHelper()->getShouldCreateCaptureNotification($transactionType)) {
            $payment->registerCaptureNotification(
                $payment_transaction->amount
            );
        }
    }
}
