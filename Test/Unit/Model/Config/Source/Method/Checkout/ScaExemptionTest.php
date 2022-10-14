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

namespace Unit\Model\Config\Source\Method\Checkout;

use EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\ScaExemption;
use Genesis\API\Constants\Transaction\Parameters\ScaExemptions;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\ScaExemption
 * @package Unit\Model\Config\Source\Method\Checkout
 */
class ScaExemptionTest extends TestCase
{
    /**
     * @covers \EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\ChallengeIndicator::toOptionArray
     */
    public function testToOptionArray()
    {
        $data = [];
        $sourceModel = new ScaExemption();

        $availableExemptions = [
            ScaExemptions::EXEMPTION_LOW_RISK  => 'Low Risk',
            ScaExemptions::EXEMPTION_LOW_VALUE => 'Low Value'
        ];

        foreach ($availableExemptions as $value => $label) {
            array_push(
                $data,
                [
                    'value' => $value,
                    'label' => __($label)
                ]
            );
        }

        $this->assertEquals(
            $data,
            $sourceModel->toOptionArray()
        );
        $this->assertEquals(
            count($availableExemptions),
            count($sourceModel->toOptionArray())
        );
        $this->assertNotEmpty(
            $sourceModel->toOptionArray()
        );
    }
}
