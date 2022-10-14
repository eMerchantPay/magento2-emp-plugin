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

use EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\ChallengeIndicator;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\Control\ChallengeIndicators;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\ChallengeIndicator
 * @package Unit\Model\Config\Source\Method\Checkout
 */
class ChallengeIndicatorTest extends TestCase
{
    /**
     * @covers \EMerchantPay\Genesis\Model\Config\Source\Method\Checkout\ChallengeIndicator::toOptionArray
     */
    public function testToOptionArray()
    {
        $data = [];
        $sourceModel = new ChallengeIndicator();

        $availableCHallengeIndicators = [
            ChallengeIndicators::NO_PREFERENCE          => 'No Preference',
            ChallengeIndicators::NO_CHALLENGE_REQUESTED => 'No Challenge Requested',
            ChallengeIndicators::PREFERENCE             => 'Preference',
            ChallengeIndicators::MANDATE                => 'Mandate'
        ];

        foreach ($availableCHallengeIndicators as $value => $label) {
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
            count($availableCHallengeIndicators),
            count($sourceModel->toOptionArray())
        );
        $this->assertNotEmpty(
            $sourceModel->toOptionArray()
        );
    }
}
