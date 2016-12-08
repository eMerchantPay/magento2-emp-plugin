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

            switch ($payment_transaction->transaction_type) {
                case \Genesis\API\Constants\Transaction\Types::AUTHORIZE:
                case \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D:
                    $payment->registerAuthorizationNotification($payment_transaction->amount);
                    break;
                case \Genesis\API\Constants\Transaction\Types::ABNIDEAL:
                case \Genesis\API\Constants\Transaction\Types::CASHU:
                case \Genesis\API\Constants\Transaction\Types::NETELLER:
                case \Genesis\API\Constants\Transaction\Types::PAYBYVOUCHER_SALE:
                case \Genesis\API\Constants\Transaction\Types::PAYBYVOUCHER_YEEPAY:
                case \Genesis\API\Constants\Transaction\Types::PAYSAFECARD:
                case \Genesis\API\Constants\Transaction\Types::PPRO:
                case \Genesis\API\Constants\Transaction\Types::SALE:
                case \Genesis\API\Constants\Transaction\Types::SALE_3D:
                case \Genesis\API\Constants\Transaction\Types::SOFORT:
                    $payment->registerCaptureNotification($payment_transaction->amount);
                    break;
                default:
                    break;
            }

            //if (!$this->getOrder()->getEmailSent()) {
            //    $this->_orderSender->send($this->getOrder());
            //}

            $payment->save();
        }

        $this->getModuleHelper()->setOrderState(
            $this->getOrder(),
            isset($payment_transaction)
                ? $payment_transaction->status
                : $responseObject->status
        );
    }
}
