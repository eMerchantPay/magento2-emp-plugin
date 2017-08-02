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

namespace EMerchantPay\Genesis\Logger;

/**
 * Class Logger
 *
 * By default the logger writes to
 * ./var/log/emerchantpay/default.log
 *
 * @package EMerchantPay\Genesis\Logger
 */
class Logger extends \Magento\Payment\Model\Method\Logger
{

    public function __construct()
    {
        $handlers = [
            new Handler()
        ];
        $logger = new \Monolog\Logger(
            'EMP',
            $handlers
        );

        parent::__construct($logger);
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param string $fileName
     * @return bool
     */
    public function setFilename($fileName)
    {
        $handlers = $this->getLogger()->getHandlers();
        if (isset($handlers[0]) === true) {
            $handlers[0]->setFilename($fileName);
            return true;
        }
        return false;
    }
}
