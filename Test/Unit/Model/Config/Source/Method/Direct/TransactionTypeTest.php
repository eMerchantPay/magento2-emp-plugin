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

namespace EMerchantPay\Genesis\Test\Unit\Model\Config\Source\Method\Direct;

use \Genesis\API\Constants\Transaction\Types as TransactionTypes;

/**
 * Class TransactionTypeTest
 *
 * @covers \EMerchantPay\Genesis\Model\Config\Source\Method\Direct\TransactionType
 * @package EMerchantPay\Genesis\Test\Unit\Model\Config\Source\Method\Direct
 */
class TransactionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \EMerchantPay\Genesis\Model\Config\Source\Method\Direct\TransactionType::toOptionArray()
     */
    public function testToOptionArray()
    {
        $sourceModel = new \EMerchantPay\Genesis\Model\Config\Source\Method\Direct\TransactionType();

        $this->assertEquals(
            [

                [
                    'value' => TransactionTypes::AUTHORIZE,
                    'label' => __('Authorize'),
                ],
                [
                    'value' => TransactionTypes::AUTHORIZE_3D,
                    'label' => __('Authorize (3D-Secure)'),
                ],
                [
                    'value' => TransactionTypes::SALE,
                    'label' => __('Sale'),
                ],
                [
                    'value' => TransactionTypes::SALE_3D,
                    'label' => __('Sale (3D-Secure)'),
                ]
            ],
            $sourceModel->toOptionArray()
        );
    }
}
