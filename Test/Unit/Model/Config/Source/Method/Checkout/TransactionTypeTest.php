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

namespace EMerchantPay\Genesis\Test\Unit\Model\Config\Source\Method\Checkout;

use EMerchantPay\Genesis\Helper\Checkout;
use EMerchantPay\Genesis\Helper\Data;
use Genesis\API\Constants\Transaction\Names;
use \Genesis\API\Constants\Transaction\Types as GenesisTransactionTypes;
use \Genesis\API\Constants\Payment\Methods as GenesisPaymentMethods;

/**
 * Class TransactionTypeTest
 *
 * @covers \EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\TransactionType
 * @package EMerchantPay\Genesis\Test\Unit\Model\Config\Source\Method\Checkout
 */
class TransactionTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\TransactionType::toOptionArray()
     */
    public function testToOptionArray()
    {
        $data        = [];
        $sourceModel = new \EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\TransactionType();

        $transactionTypes = GenesisTransactionTypes::getWPFTransactionTypes();
        $excludedTypes    = Checkout::getRecurringTransactionTypes();

        // Exclude PPRO transaction. This is not standalone transaction type
        array_push($excludedTypes, GenesisTransactionTypes::PPRO);

        // Exclude Transaction Types
        $transactionTypes = array_diff($transactionTypes, $excludedTypes);

        // Add PPRO types
        $pproTypes = array_map(
            function ($type) {
                return $type . Data::PPRO_TRANSACTION_SUFFIX;
            },
            GenesisPaymentMethods::getMethods()
        );
        $transactionTypes = array_merge($transactionTypes, $pproTypes);
        asort($transactionTypes);

        foreach ($transactionTypes as $type) {
            $name = Names::getName($type);
            if (!GenesisTransactionTypes::isValidTransactionType($type)) {
                $name = strtoupper($type);
            }

            array_push(
                $data,
                [
                    'value' => $type,
                    'label' => __($name)
                ]
            );
        }

        $this->assertEquals(
            $data,
            $sourceModel->toOptionArray()
        );
    }
}
