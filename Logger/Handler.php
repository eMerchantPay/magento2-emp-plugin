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

namespace EMerchantPay\Genesis\Logger;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /**
     * The constructor
     */
    public function __construct()
    {
        parent::__construct(
            new File()
        );
        $this->setFilename('default');
    }

    /**
     * Set file name for logging
     *
     * @param string $fileName
     */
    public function setFilename($fileName)
    {
        $this->fileName = $this->url =
            BP . '/var/log/emerchantpay/' .
            $fileName
            . '.log';
    }
}
