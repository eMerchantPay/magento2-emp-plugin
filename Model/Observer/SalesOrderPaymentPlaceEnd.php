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

namespace EMerchantPay\Genesis\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Observer Class (called just after the Sales Order has been Places)
 * Class SalesOrderPaymentPlaceEnd
 * @package EMerchantPay\Genesis\Model\Observer
 */
class SalesOrderPaymentPlaceEnd implements ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;
    /**
     * @var \EMerchantPay\Genesis\Helper\Data
     */
    protected $_moduleHelper;

    /**
     * SalesOrderPaymentPlaceEnd constructor.
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \EMerchantPay\Genesis\Helper\Data $moduleHelper
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \EMerchantPay\Genesis\Helper\Data $moduleHelper
    ) {
        $this->_storeManager = $storeManager;
        $this->_moduleHelper = $moduleHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getEvent()->getData('payment');

        $this->updateOrderStatusToNew($payment);
    }

    /**
     * Update OrderStatus for the new Order
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    protected function updateOrderStatusToNew($payment)
    {
        $order = $payment->getOrder();

        $configHelper = $this->getModuleHelper()->getMethodConfig(
            $payment->getMethod()
        );

        $this->getModuleHelper()->setOrderStatusByState(
            $order,
            $configHelper->getOrderStatusNew()
        );

        $order->save();
    }

    /**
     * Get an Instance of the Module Helper Object
     * @return \EMerchantPay\Genesis\Helper\Data
     */
    protected function getModuleHelper()
    {
        return $this->_moduleHelper;
    }
}
