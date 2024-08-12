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

namespace EMerchantPay\Genesis\Model\Config\Source\Method\Checkout;

use EMerchantPay\Genesis\Helper\Data;
use EMerchantPay\Genesis\Model\Traits\ExcludedTransactionTypes;
use EMerchantPay\Genesis\Model\Traits\RecurringTransactionTypes;
use Genesis\Api\Constants\Payment\Methods as GenesisPaymentMethods;
use Genesis\Api\Constants\Transaction\Names;
use Genesis\Api\Constants\Transaction\Parameters\Mobile\ApplePay\PaymentTypes as ApplePaymentTypes;
use Genesis\Api\Constants\Transaction\Parameters\Mobile\GooglePay\PaymentTypes as GooglePaymentTypes;
use Genesis\Api\Constants\Transaction\Parameters\Wallets\PayPal\PaymentTypes as PayPalPaymentTypes;
use Genesis\Api\Constants\Transaction\Types as GenesisTransactionTypes;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Checkout Transaction Types Model Source
 *
 * Class TransactionType
 */
class TransactionType implements OptionSourceInterface
{
    use ExcludedTransactionTypes;
    use RecurringTransactionTypes;

    /**
     * Builds the options for the MultiSelect control in the Admin Zone
     *
     * @return array
     */
    public function toOptionArray()
    {
        $data = [];

        $transactionTypes = GenesisTransactionTypes::getWPFTransactionTypes();
        $excludedTypes    = array_merge(
            $this->getRecurringTransactionTypes(),
            $this->getExcludedTransactionTypes()
        );

        // Exclude Transaction Types
        $transactionTypes = array_diff($transactionTypes, $excludedTypes);

        // Add Google Payment types
        $googlePayTypes = array_map(
            function ($type) {
                return Data::GOOGLE_PAY_TRANSACTION_PREFIX . $type;
            },
            [
                GooglePaymentTypes::AUTHORIZE,
                GooglePaymentTypes::SALE
            ]
        );

        // Add PayPal Payment types
        $payPalTypes = array_map(
            function ($type) {
                return Data::PAYPAL_TRANSACTION_PREFIX . $type;
            },
            [
                PayPalPaymentTypes::AUTHORIZE,
                PayPalPaymentTypes::SALE,
                PayPalPaymentTypes::EXPRESS
            ]
        );

        // Add Apple Pay Payment types
        $applePayTypes = array_map(
            function ($type) {
                return Data::APPLE_PAY_TRANSACTION_PREFIX . $type;
            },
            [
                ApplePaymentTypes::AUTHORIZE,
                ApplePaymentTypes::SALE
            ]
        );

        $transactionTypes = array_merge(
            $transactionTypes,
            $googlePayTypes,
            $payPalTypes,
            $applePayTypes
        );
        asort($transactionTypes);

        foreach ($transactionTypes as $type) {
            $name = Names::getName($type);
            if (!GenesisTransactionTypes::isValidTransactionType($type)) {
                $name = strtoupper($type);
            }

            $data[] = [
                'value' => $type,
                'label' => __($name)
            ];
        }

        return $data;
    }
}
