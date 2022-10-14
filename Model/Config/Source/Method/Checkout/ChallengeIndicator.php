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

use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\Control\ChallengeIndicators;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Checkout Bank codes Model Source
 * Class BankCode
 * @package EMerchantPay\Genesis\Model\Config\Source\Method\Checkout
 */
class ChallengeIndicator implements OptionSourceInterface
{
    protected $indicators = [
        ChallengeIndicators::NO_PREFERENCE          => 'No Preference',
        ChallengeIndicators::NO_CHALLENGE_REQUESTED => 'No Challenge Requested',
        ChallengeIndicators::PREFERENCE             => 'Preference',
        ChallengeIndicators::MANDATE                => 'Mandate'
    ];

    public function toOptionArray()
    {
        $data = [];

        foreach ($this->indicators as $constant => $display) {
            array_push(
                $data,
                [
                    'value' => $constant,
                    'label' => __($display)
                ]
            );
        }

        return $data;
    }
}
