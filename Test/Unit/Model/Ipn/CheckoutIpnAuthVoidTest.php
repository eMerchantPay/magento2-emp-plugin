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

namespace EMerchantPay\Genesis\Test\Unit\Model\Ipn;

use EMerchantPay\Genesis\Model\Ipn\CheckoutIpn;
use Genesis\Api\Constants\Transaction\States;
use Genesis\Api\Constants\Transaction\Types;
use stdClass;

/**
 * Class CheckoutIpnRefundedTest
 *
 * @covers CheckoutIpn
 */

class CheckoutIpnAuthVoidTest extends CheckoutIpnTest
{
    public const RECONCILIATION_TRANSACTION_TYPE   = Types::AUTHORIZE;

    /**
     * Creates reconciliation object
     *
     * @return stdClass
     */
    protected function createReconciliationObj()
    {
        $this->reconciliationObj = parent::createReconciliationObj();

        $this->reconciliationObj->status           = States::VOIDED;
        $this->reconciliationObj->transaction_type = self::RECONCILIATION_TRANSACTION_TYPE;

        return $this->reconciliationObj;
    }
}
