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

namespace EMerchantPay\Genesis\Model\Config\Source\Method\Checkout;

use Genesis\Api\Constants\Transaction\Parameters\ScaExemptions;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Checkout Bank codes Model Source
 *
 * Class BankCode
 */
class ScaExemption implements OptionSourceInterface
{
    /**
     * @var array
     */
    public $exemptions = [
        ScaExemptions::EXEMPTION_LOW_RISK  => 'Low Risk',
        ScaExemptions::EXEMPTION_LOW_VALUE => 'Low Value'
    ];

    /**
     * Builds the options for the MultiSelect control in the Admin Zone
     *
     * @return array
     */
    public function toOptionArray()
    {
        $data = [];

        foreach ($this->exemptions as $value => $label) {
            $data[] = [
                'value' => $value,
                'label' => __($label)
            ];
        }

        return $data;
    }
}
