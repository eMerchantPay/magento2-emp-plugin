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

namespace EMerchantPay\Genesis\Plugin;

use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadata;

/**
 * Class CookieManager
 *
 * Sets SameSite cookie to none
 */
class CookieManager
{
    /**
     * @var CookieMetadataFactory
     */
    protected $_cookieMetadataFactory;

    /**
     * @param CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(CookieMetadataFactory $cookieMetadataFactory)
    {
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * Set SameSite cookie to none
     *
     * @param PhpCookieManager    $subject
     * @param callable            $proceed
     * @param string              $cookieName
     * @param string              $cookieValue
     * @param CookieMetadata|null $metadata
     *
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSetPublicCookie(
        PhpCookieManager $subject,
        callable         $proceed,
        string           $cookieName,
        string           $cookieValue,
        CookieMetadata   $metadata = null
    ): mixed {
        if ($metadata === null) {
            $metadata = $this->_cookieMetadataFactory->createPublicCookieMetadata();
        }

        $metadata->setSecure(true);
        $metadata->setSameSite('none');

        return $proceed($cookieName, $cookieValue, $metadata);
    }
}
