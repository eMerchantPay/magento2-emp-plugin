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

namespace EMerchantPay\Genesis\Test\Unit\Model\Ipn;

use EMerchantPay\Genesis\Model\Ipn\DirectIpn;
use EMerchantPay\Genesis\Helper\Data as DataHelper;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class CheckoutIpnRefundedTest
 * @covers \EMerchantPay\Genesis\Model\Ipn\DirectIpn
 * @package EMerchantPay\Genesis\Test\Unit\Model\Ipn
 */

class DirectIpnAuthVoidTest extends \EMerchantPay\Genesis\Test\Unit\Model\Ipn\DirectIpnTest
{
    const RECONCILIATION_TRANSACTION_TYPE   = \Genesis\API\Constants\Transaction\Types::AUTHORIZE;

    /**
     * Creates reconciliation object
     * @return \stdClass
     */
    protected function createReconciliationObj()
    {
        $this->reconciliationObj = parent::createReconciliationObj();

        $this->reconciliationObj->status           = \Genesis\API\Constants\Transaction\States::VOIDED;
        $this->reconciliationObj->transaction_type = self::RECONCILIATION_TRANSACTION_TYPE;

        return $this->reconciliationObj;
    }
}
