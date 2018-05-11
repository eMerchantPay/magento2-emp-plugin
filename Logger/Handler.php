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

namespace EMerchantPay\Genesis\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    public function __construct()
    {
        parent::__construct(
            new \Magento\Framework\Filesystem\Driver\File()
        );
        $this->setFilename('default');
    }

    /**
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
