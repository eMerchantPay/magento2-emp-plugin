<?php
/*
 * Copyright (C) 2018-2024 emerchantpay Ltd.
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
 * @copyright   2018-2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Block\Frontend;

use EMerchantPay\Genesis\Model\Config as BackendConfig;
use EMerchantPay\Genesis\Model\Method\Checkout;
use Magento\Framework\View\Element\Template;

/**
 * Get iframe payment processing option to the frontend
 *
 * Class Config
 */
class Config extends Template
{
    /**
     * @var BackendConfig
     */
    protected BackendConfig $_backendConfig;

    /**
     * @param Template\Context $context
     * @param BackendConfig $backendConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        BackendConfig    $backendConfig,
        array            $data = []
    ) {
        $this->_backendConfig = $backendConfig;
        parent::__construct($context, $data);
    }

    /**
     * Return for frontend is the payment in iframe enabled
     *
     * @return bool
     */
    public function isIframeProcessingEnabled(): bool
    {
        $this->_backendConfig->setMethodCode(Checkout::CODE);

        return $this->_backendConfig->isIframeProcessingEnabled();
    }
}
