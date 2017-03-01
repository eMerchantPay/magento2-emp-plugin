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

namespace EMerchantPay\Genesis\Test\Unit\Model\Config\Source\Method\Checkout;

use \Genesis\API\Constants\Transaction\Types as TransactionTypes;
use \Genesis\API\Constants\Payment\Methods as GenesisPaymentMethods;

/**
 * Class TransactionTypeTest
 *
 * @covers \EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\TransactionType
 * @package EMerchantPay\Genesis\Test\Unit\Model\Config\Source\Method\Checkout
 */
class TransactionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\TransactionType::toOptionArray()
     */
    public function testToOptionArray()
    {
        $sourceModel = new \EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\TransactionType();

        $this->assertEquals(
            [

                [
                    'value' => TransactionTypes::ABNIDEAL,
                    'label' => __('ABN iDEAL'),
                ],
                [
                    'value' => TransactionTypes::AUTHORIZE,
                    'label' => __('Authorize'),
                ],
                [
                    'value' => TransactionTypes::AUTHORIZE_3D,
                    'label' => __('Authorize (3D-Secure)'),
                ],
                [
                    'value' => TransactionTypes::CASHU,
                    'label' => __('CashU'),
                ],
                [
                    'value' => GenesisPaymentMethods::EPS,
                    'label' => __('eps'),
                ],
                [
                    'value' => GenesisPaymentMethods::GIRO_PAY,
                    'label' => __('GiroPay'),
                ],
                [
                    'value' => TransactionTypes::NETELLER,
                    'label' => __('Neteller'),
                ],
                [
                    'value' => GenesisPaymentMethods::QIWI,
                    'label' => __('Qiwi'),
                ],
                [
                    'value' => TransactionTypes::PAYSAFECARD,
                    'label' => __('PaySafeCard'),
                ],
                [
                    'value' => TransactionTypes::PAYBYVOUCHER_SALE,
                    'label' => __('PayByVoucher (Sale)'),
                ],
                [
                    'value' => TransactionTypes::PAYBYVOUCHER_YEEPAY,
                    'label' => __('PayByVoucher (oBeP)'),
                ],
                [
                    'value' => GenesisPaymentMethods::PRZELEWY24,
                    'label' => __('Przelewy24'),
                ],
                [
                    'value' => TransactionTypes::POLI,
                    'label' => __('POLi'),
                ],
                [
                    'value' => GenesisPaymentMethods::SAFETY_PAY,
                    'label' => __('SafetyPay'),
                ],
                [
                    'value' => TransactionTypes::SALE,
                    'label' => __('Sale'),
                ],
                [
                    'value' => TransactionTypes::SALE_3D,
                    'label' => __('Sale (3D-Secure)'),
                ],
                [
                    'value' => TransactionTypes::SOFORT,
                    'label' => __('SOFORT'),
                ],
                [
                    'value' => GenesisPaymentMethods::TELEINGRESO,
                    'label' => __('TeleIngreso'),
                ],
                [
                    'value' => GenesisPaymentMethods::TRUST_PAY,
                    'label' => __('TrustPay'),
                ],
                [
                    'value' => TransactionTypes::WEBMONEY,
                    'label' => __('WebMoney'),
                ]
            ],
            $sourceModel->toOptionArray()
        );
    }
}
