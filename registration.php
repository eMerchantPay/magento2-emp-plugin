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

// phpcs:disable
// phpcs:disable Magento2.Security.Superglobal.SuperglobalUsageWarning
if (is_array($_SERVER) && array_key_exists('PLUGIN_ENV', $_SERVER) && $_SERVER['PLUGIN_ENV'] == 'linter') {
    return;
}
// phpcs:enable
// phpcs:enable Magento2.Security.Superglobal.SuperglobalUsageWarning

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'EMerchantPay_Genesis',
    __DIR__
);
