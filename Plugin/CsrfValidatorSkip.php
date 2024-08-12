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

namespace EMerchantPay\Genesis\Plugin;

use Closure;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;

/**
 * Skip CSRF validation for the notification URL
 *
 * Class CsrfValidatorSkip
 */
class CsrfValidatorSkip
{
    /**
     * Use our validator instead of the original
     *
     * @param CsrfValidator    $subject
     * @param Closure          $proceed
     * @param RequestInterface $request
     * @param ActionInterface  $action
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundValidate(
        CsrfValidator    $subject,
        Closure          $proceed,
        RequestInterface $request,
        ActionInterface  $action
    ) {
        if ($request->getModuleName() == 'emerchantpay') {
            return;
        }
        $proceed($request, $action);
    }
}
