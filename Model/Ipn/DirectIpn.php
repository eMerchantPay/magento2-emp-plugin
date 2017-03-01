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
 * Direct Method IPN Handler Class
 * Class DirectIpn
 * @package EMerchantPay\Genesis\Model\Ipn
 */
class DirectIpn extends \EMerchantPay\Genesis\Model\Ipn\AbstractIpn
{
    /**
     * Gets payment method code
     * @return string
     */
    protected function getPaymentMethodCode()
    {
        return \EMerchantPay\Genesis\Model\Method\Direct::CODE;
    }

    /**
     * Updates Transactions and Order Status
     * @param \stdClass $responseObject
     * @throws \Exception
     */
    protected function processNotification($responseObject)
    {
        $payment = $this->getPayment();

        $this->getModuleHelper()->updateTransactionAdditionalInfo(
            $responseObject->unique_id,
            $responseObject,
            false
        );

        $payment
            ->setLastTransId(
                $responseObject->unique_id
            )
            ->setTransactionId(
                $responseObject->unique_id
            )
            ->setIsTransactionPending(
                $this->getShouldSetCurrentTranPending(
                    $responseObject
                )
            )
            ->setIsTransactionClosed(
                false
            )
            ->setPreparedMessage(
                $this->createIpnComment(
                    $responseObject->message
                )
            )
            ->resetTransactionAdditionalInfo();

        switch ($responseObject->transaction_type) {
            case \Genesis\API\Constants\Transaction\Types::AUTHORIZE:
            case \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D:
                $payment->registerAuthorizationNotification($responseObject->amount);
                break;
            case \Genesis\API\Constants\Transaction\Types::SALE:
            case \Genesis\API\Constants\Transaction\Types::SALE_3D:
                $payment->registerCaptureNotification($responseObject->amount);
                break;
            default:
                break;
        }

        $payment->save();

        $this->getModuleHelper()->setOrderState(
            $this->getOrder(),
            $responseObject->status,
            ''
        );
    }
}
