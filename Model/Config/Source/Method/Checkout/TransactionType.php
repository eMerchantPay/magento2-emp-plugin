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

namespace EMerchantPay\Genesis\Model\Config\Source\Method\Checkout;

use \Genesis\API\Constants\Transaction\Types as GenesisTransactionTypes;
use \Genesis\API\Constants\Payment\Methods as GenesisPaymentMethods;

/**
 * Checkout Transaction Types Model Source
 * Class TransactionType
 * @package EMerchantPay\Genesis\Model\Config\Source\Method\Checkout
 */
class TransactionType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Builds the options for the MultiSelect control in the Admin Zone
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => GenesisTransactionTypes::ABNIDEAL, 'label' => __('ABN iDEAL')],
            ['value' => GenesisTransactionTypes::ALIPAY, 'label' => __('Alipay')],
            ['value' => GenesisTransactionTypes::AUTHORIZE, 'label' => __('Authorize')],
            ['value' => GenesisTransactionTypes::AUTHORIZE_3D, 'label' => __('Authorize (3D-Secure)')],
            ['value' => GenesisTransactionTypes::CASHU, 'label' => __('CashU')],
            ['value' => GenesisTransactionTypes::CITADEL_PAYIN, 'label' => __('Citadel')],
            ['value' => GenesisPaymentMethods::EPS, 'label' => __('eps')],
            ['value' => GenesisTransactionTypes::EZEEWALLET, 'label' => __('eZeeWallet')],
            ['value' => GenesisTransactionTypes::IDEBIT_PAYIN, 'label' => __('iDebit')],
            ['value' => GenesisTransactionTypes::INPAY, 'label' => __('INPay')],
            ['value' => GenesisTransactionTypes::INSTA_DEBIT_PAYIN, 'label' => __('InstaDebit')],
            ['value' => GenesisPaymentMethods::GIRO_PAY, 'label' => __('GiroPay')],
            ['value' => GenesisTransactionTypes::NETELLER, 'label' => __('Neteller')],
            ['value' => GenesisPaymentMethods::BCMC, 'label' => __('Mr.Cash')],
            ['value' => GenesisPaymentMethods::MYBANK, 'label' => __('MyBank')],
            ['value' => GenesisPaymentMethods::QIWI, 'label' => __('Qiwi')],
            ['value' => GenesisTransactionTypes::P24, 'label' => __('P24')],
            ['value' => GenesisTransactionTypes::PAYPAL_EXPRESS, 'label' => __('PayPal Express')],
            ['value' => GenesisTransactionTypes::PAYSAFECARD, 'label' => __('PaySafeCard')],
            ['value' => GenesisTransactionTypes::PAYSEC_PAYIN, 'label' => __('PaySec')],
            ['value' => GenesisTransactionTypes::PAYBYVOUCHER_SALE, 'label' => __('PayByVoucher (Sale)')],
            ['value' => GenesisTransactionTypes::PAYBYVOUCHER_YEEPAY, 'label' => __('PayByVoucher (oBeP)')],
            ['value' => GenesisPaymentMethods::PRZELEWY24, 'label' => __('Przelewy24')],
            ['value' => GenesisTransactionTypes::POLI, 'label' => __('POLi')],
            ['value' => GenesisPaymentMethods::SAFETY_PAY, 'label' => __('SafetyPay')],
            ['value' => GenesisTransactionTypes::SALE, 'label' => __('Sale')],
            ['value' => GenesisTransactionTypes::SALE_3D, 'label' => __('Sale (3D-Secure)')],
            ['value' => GenesisTransactionTypes::SDD_SALE, 'label' => __('SDD Sale')],
            ['value' => GenesisTransactionTypes::SOFORT, 'label' => __('SOFORT')],
            ['value' => GenesisPaymentMethods::TELEINGRESO, 'label' => __('TeleIngreso')],
            ['value' => GenesisPaymentMethods::TRUST_PAY, 'label' => __('TrustPay')],
            ['value' => GenesisTransactionTypes::TRUSTLY_SALE, 'label' => __('Trustly Sale')],
            ['value' => GenesisTransactionTypes::WEBMONEY, 'label' => __('WebMoney')],
            ['value' => GenesisTransactionTypes::WECHAT, 'label' => __('WeChat')]
        ];
    }
}
