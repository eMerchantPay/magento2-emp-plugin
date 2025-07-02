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

namespace Unit\Model\Config\Source\Method\Checkout;

use EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\ChallengeIndicator;
use EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\ScaExemption;
use Genesis\Api\Constants\Transaction\Parameters\ScaExemptions;
use PHPUnit\Framework\TestCase;

/**
 * @covers ScaExemption
 */
class ScaExemptionTest extends TestCase
{
    /**
     * @covers ChallengeIndicator::toOptionArray
     */
    public function testToOptionArray()
    {
        $data                = [];
        $sourceModel         = new ScaExemption();
        $availableExemptions = $sourceModel->exemptions;

        foreach ($availableExemptions as $value => $label) {
            $data[] = [
                'value' => $value,
                'label' => __($label)
            ];
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
