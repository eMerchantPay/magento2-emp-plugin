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

namespace EMerchantPay\Genesis\Helper;

use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\PasswordChangeIndicators;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\RegistrationIndicators;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\ShippingAddressUsageIndicators;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\UpdateIndicators;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\ReorderItemIndicators;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\ShippingIndicators;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Collection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * Helper Class for all Payment Methods
 *
 * Class Data
 * @package EMerchantPay\Genesis\Helper
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Threeds extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CURRENT_TRANSACTION_INDICATOR       = 'current_transaction';
    const LESS_THAN_30_DAYS_INDICATOR         = 'less_than_30_days';
    const MORE_30_LESS_60_DAYS_INDICATOR      = 'more_30_less_60_days';
    const MORE_THAN_60_DAYS_INDICATOR         = 'more_than_60_days';

    const DATE_FORMAT                         = 'Y-m-d';

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Data
     */
    protected $_moduleHelper;

    /**
     * @var TimezoneInterface $_timezone
     */
    protected $_timezone;

    /**
     * @var CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Context $context
     * @param CollectionFactory $_orderCollectionFactory
     * @param Data $moduleHelper
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Context $context,
        CollectionFactory $orderCollectionFactory,
        Data $moduleHelper,
        TimezoneInterface $timezone
    ) {
        $this->_objectManager          = $objectManager;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_moduleHelper           = $moduleHelper;
        $this->_timezone               = $timezone;

        parent::__construct($context);
    }

    /**
     * Fetch the Shipping Indicator from the Order Data
     *
     * @param bool $hasPhysicalProducts Indicates if the Order has only virtual products
     * @param Order $order
     * @return string
     */
    public function fetchShippingIndicator($hasPhysicalProducts, $order)
    {
        $indicator = ShippingIndicators::OTHER;

        if (!$hasPhysicalProducts) {
            $indicator = ShippingIndicators::DIGITAL_GOODS;
        }

        $hasAddressInfo = $order->getBillingAddress() !== null && $order->getShippingAddress() !== null;

        if ($hasAddressInfo &&
            !$order->getCustomerIsGuest() &&
            $order->getBillingAddress()->getCustomerAddressId() &&
            $order->getShippingAddress()->getCustomerAddressId()
        ) {
            $indicator = ShippingIndicators::STORED_ADDRESS;
        }

        if ($hasAddressInfo && $this->isSameAddressData($order->getBillingAddress(), $order->getShippingAddress())) {
            $indicator = ShippingIndicators::SAME_AS_BILLING;
        }

        return $indicator;
    }

    /**
     * @param Order $order
     * @return string
     */
    public function fetchReorderItemsIndicator($order)
    {
        if (!is_numeric($order->getCustomerId())) {
            return ReorderItemIndicators::FIRST_TIME;
        }

        $orders       = $this->getModuleHelper()->getCustomerOrders($order->getCustomerId());
        $orderedItems = $this->getCustomerOrderedItems($orders);

        foreach ($order->getAllVisibleItems() as $item) {
            if (in_array($item->getProductId(), $orderedItems)) {
                return ReorderItemIndicators::REORDERED;
            }
        }

        return ReorderItemIndicators::FIRST_TIME;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return string
     */
    public function fetchUpdateIndicator($customer)
    {
        if (!$customer) {
            return UpdateIndicators::CURRENT_TRANSACTION;
        }

        return $this->getUpdateIndicatorValue($customer);
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return string
     */
    public function fetchPasswordChangeIndicator($customer)
    {
        $indicator   = null;
        $createdDate = $this->getTimezone()->date($customer->getCreatedAt());
        $updatedDate = $this->getTimezone()->date($customer->getUpdatedAt());
        $today       = $this->getTimezone()->date();

        switch ($this->fetchIndicator($updatedDate, $today)) {
            case static::CURRENT_TRANSACTION_INDICATOR:
                $indicator = PasswordChangeIndicators::DURING_TRANSACTION;
                break;
            case static::LESS_THAN_30_DAYS_INDICATOR:
                $indicator = PasswordChangeIndicators::LESS_THAN_30DAYS;
                break;
            case static::MORE_30_LESS_60_DAYS_INDICATOR:
                $indicator = PasswordChangeIndicators::FROM_30_TO_60_DAYS;
                break;
            case static::MORE_THAN_60_DAYS_INDICATOR:
                $indicator = PasswordChangeIndicators::MORE_THAN_60DAYS;
                break;
        }

        if ($indicator !== PasswordChangeIndicators::DURING_TRANSACTION &&
            $createdDate->format(static::DATE_FORMAT) == $updatedDate->format(static::DATE_FORMAT)
        ) {
            $indicator = PasswordChangeIndicators::NO_CHANGE;
        }

        return $indicator;
    }

    /**
     * Get the Shipping Address Usage Indicator
     *
     * @param string $shippingAddresFistUsed
     * @return string
     */
    public function fetchShippingAddressUsageIndicator($shippingAddresFistUsed)
    {
        $updatedAt         = $this->getTimezone()->date($shippingAddresFistUsed);
        $today             = $this->getTimezone()->date();

        switch ($this->fetchIndicator($updatedAt, $today)) {
            case static::LESS_THAN_30_DAYS_INDICATOR:
                return ShippingAddressUsageIndicators::LESS_THAN_30DAYS;
            case static::MORE_30_LESS_60_DAYS_INDICATOR:
                return ShippingAddressUsageIndicators::FROM_30_TO_60_DAYS;
            case static::MORE_THAN_60_DAYS_INDICATOR:
                return ShippingAddressUsageIndicators::MORE_THAN_60DAYS;
            default:
                return ShippingAddressUsageIndicators::CURRENT_TRANSACTION;
        }
    }

    /**
     * Get the date of the first used Shipping Address
     *
     * @param Order $order
     * @return string
     */
    public function fetchShippingAddressDateFirstUsed($order)
    {
        $customerId        = $order->getCustomerId();
        $shippingAddressId = $order->getShippingAddress()->getCustomerAddressId();

        $shippingAddresses = $this->getOrdersWithShippingAddress($customerId, $shippingAddressId);

        return $shippingAddresses->setPageSize(1)->getFirstItem()->getCreatedAt();
    }

    /**
     * Extract the Customer Transaction Count for the last 24 hours
     *
     * @param $order
     * @return void
     */
    public function fetchTransactionActivityLast24Hours($order)
    {
        $from  = $this->getTimezone()->date()->sub(new \DateInterval('PT24H'));
        $today = $this->getTimezone()->date();

        $collection = $this->getTransactionActivityForPeriod(
            $order->getCustomerId(),
            $from->format(static::DATE_FORMAT),
            $today->format(static::DATE_FORMAT)
        );

        return $collection->getSize();
    }

    /**
     * Extract the Customer Transaction Count for the last year
     *
     * @param $order
     * @return void
     */
    public function fetchTransactionActivityPreviousYear($order)
    {
        $lastYear = $this->getTimezone()->date()->sub(new \DateInterval('P1Y'));

        $collection = $this->getTransactionActivityForPeriod(
            $order->getCustomerId(),
            $lastYear->format('Y-01-01 00:00:00'),
            $lastYear->format('Y-12-31 23:59:59')
        );

        return $collection->getSize();
    }

    /**
     * Extract the Successful Customer Transaction for last 6 months
     *
     * @param $order
     * @return int
     */
    public function fetchPurchasedCountLastHalfYear($order)
    {
        $from  = $this->getTimezone()->date()->sub(new \DateInterval('P6M'));
        $today = $this->getTimezone()->date();

        $orders = $this->getModuleHelper()->getCustomerOrders(
            $order->getCustomerId(),
            [Order::STATE_PAYMENT_REVIEW, Order::STATE_COMPLETE, Order::STATE_PROCESSING, Order::STATE_PENDING_PAYMENT],
            $from->format(static::DATE_FORMAT . ' 00:00:00'),
            $today->format(static::DATE_FORMAT . ' 23:59:59')
        );

        return $orders->getSize();
    }

    /**
     * Sort the Array of Addresses
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @return array
     */
    public function getSortedCustomerAddress($customer, $sort = SORT_DESC)
    {
        $addresses = [];
        foreach ($customer->getAddresses() as $address) {
            $addresses[] = $address->getData();
        }

        array_multisort(
            array_map('strtotime', array_column($addresses, 'updated_at')),
            $sort,
            $addresses
        );

        return $addresses;
    }

    /**
     * Get the First Order via emerchantpay checkout payment method
     *
     * @param $order
     * @return mixed
     */
    public function fetchFirstOrderDate($order)
    {
        $orders = $this->getModuleHelper()->getCustomerOrders($order->getCustomerId());

        return $orders->setPageSize(1)->getFirstItem()->getCreatedAt();
    }

    /**
     * Get the Registration Indicator
     *
     * @param \Magento\Sales\Model\Order $order
     * @param $firsOrderDate
     * @return string
     */
    public function fetchRegistrationIndicator($order, $firsOrderDate)
    {
        if ($order->getCustomerIsGuest()) {
            return RegistrationIndicators::GUEST_CHECKOUT;
        }

        $orderDateObject = $this->getTimezone()->date($firsOrderDate);
        $today           = $this->getTimezone()->date();

        switch ($this->fetchIndicator($orderDateObject, $today)) {
            case static::LESS_THAN_30_DAYS_INDICATOR:
                return RegistrationIndicators::LESS_THAN_30DAYS;
            case static::MORE_30_LESS_60_DAYS_INDICATOR:
                return RegistrationIndicators::FROM_30_TO_60_DAYS;
            case static::MORE_THAN_60_DAYS_INDICATOR:
                return RegistrationIndicators::MORE_THAN_60DAYS;
            default:
                return RegistrationIndicators::CURRENT_TRANSACTION;
        }
    }

    /**
     * Compare the given dates and return appropriate Update Interval values
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @return string
     */
    protected function getUpdateIndicatorValue($customer)
    {
        $addresses = $this->getSortedCustomerAddress($customer);

        if (empty($addresses)) {
            return UpdateIndicators::CURRENT_TRANSACTION;
        }

        $latestAddressDate = $this->getTimezone()->date($addresses[0]['updated_at']);
        $today             = $this->getTimezone()->date();

        switch ($this->fetchIndicator($latestAddressDate, $today)) {
            case static::LESS_THAN_30_DAYS_INDICATOR:
                return UpdateIndicators::LESS_THAN_30DAYS;
            case static::MORE_30_LESS_60_DAYS_INDICATOR:
                return UpdateIndicators::FROM_30_TO_60_DAYS;
            case static::MORE_THAN_60_DAYS_INDICATOR:
                return UpdateIndicators::MORE_THAN_60DAYS;
            default:
                return UpdateIndicators::CURRENT_TRANSACTION;
        }
    }

    /**
     * Compare billing and shipping addresses
     *
     * @param \Magento\Sales\Model\Order\Address $billingAddress
     * @return bool
     */
    protected function isSameAddressData($billingAddress, $shippingAddress)
    {
        $billing = [
            $billingAddress->getFirstname(),
            $billingAddress->getLastname(),
            $billingAddress->getStreetLine(1),
            $billingAddress->getStreetLine(2),
            $billingAddress->getPostcode(),
            $billingAddress->getCity(),
            $billingAddress->getRegionCode(),
            $billingAddress->getCountryId()
        ];

        $shipping = [
            $shippingAddress->getFirstname(),
            $shippingAddress->getLastname(),
            $shippingAddress->getStreetLine(1),
            $shippingAddress->getStreetLine(2),
            $shippingAddress->getPostcode(),
            $shippingAddress->getCity(),
            $shippingAddress->getRegionCode(),
            $shippingAddress->getCountryId()
        ];

        return count(array_diff($billing, $shipping)) === 0;
    }

    /**
     * Return all Item IDs that the customer have been purchased
     *
     * @param $orders
     * @return array
     */
    protected function getCustomerOrderedItems($orders)
    {
        $items = [];

        foreach ($orders as $order) {
            foreach ($order->getAllVisibleItems() as $item) {
                $items[] = $item->getProductId();
            }
        }

        return $items;
    }

    /**
     * @param \DateTime $date
     * @param \DateTime $compareWith
     * @return string
     */
    protected function fetchIndicator($date, $compareWith)
    {
        $indicator = static::CURRENT_TRANSACTION_INDICATOR;

        /** @var \DateInterval $updateInterval */
        $updateInterval = $date->diff($compareWith);

        if (0 < $updateInterval->days && $updateInterval->days < 30) {
            $indicator = static::LESS_THAN_30_DAYS_INDICATOR;
        }

        if (30 <= $updateInterval->days && $updateInterval->days < 60) {
            $indicator = static::MORE_30_LESS_60_DAYS_INDICATOR;
        }

        if ($updateInterval->days > 60) {
            $indicator = static::MORE_THAN_60_DAYS_INDICATOR;
        }

        return $indicator;
    }

    /**
     * Retrieve all shipping addresses used from the orders
     *
     * @param $customerId
     * @param $shippingAddressId
     * @return Collection
     */
    protected function getOrdersWithShippingAddress($customerId, $shippingAddressId)
    {
        $collection = $this->getOrderCollectionFactory()->create();

        $collection
            ->join(
                ['payment' => 'sales_order_payment'],
                'main_table.entity_id = payment.parent_id',
                ['method']
            )
            ->join(
                ['address' => 'sales_order_address'],
                'main_table.entity_id = address.parent_id',
                ['customer_address_id', 'address_type']
            )
            ->addFieldToFilter('payment.method', \EMerchantPay\Genesis\Model\Method\Checkout::CODE)
            ->addFieldToFilter('address.customer_address_id', $shippingAddressId)
            ->addFieldToFilter('main_table.customer_id', $customerId)
            ->addFieldToFilter('address.address_type', 'shipping')
            ->setOrder('main_table.created_at', 'asc');

        $collection->getSelect()->columns(
            ['address.*', 'main_table.customer_id', 'main_table.created_at', 'main_table.updated_at']
        );

        return $collection;
    }

    /**
     * Retrieve all transactions for Customer Id
     *
     * @param $customerId
     * @param $fromTime
     * @param $toTime
     * @return mixed
     */
    protected function getTransactionActivityForPeriod($customerId, $fromTime, $toTime)
    {
        $collection = $this->getOrderCollectionFactory()->create();

        $collection
            ->join(
                ['payment' => 'sales_order_payment'],
                'main_table.entity_id = payment.parent_id',
                ['method']
            )
            ->join(
                ['payment' => 'sales_order_payment'],
                'main_table.entity_id = payment.parent_id'
            )
            ->addFieldToFilter('payment.method', \EMerchantPay\Genesis\Model\Method\Checkout::CODE)
            ->addFieldToFilter('main_table.customer_id', $customerId)
            ->addFieldToFilter(
                'main_table.created_at',
                ['from' => $fromTime, 'to' => $toTime]
            )
            ->setOrder('main_table.created_at', 'asc');

        return $collection;
    }

    /**
     * Get an Instance of the Magento Object Manager
     *
     * @return ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        return $this->_objectManager;
    }

    /**
     * Get an Instance of the Plugin Helper
     *
     * @return Data
     */
    protected function getModuleHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * @return TimezoneInterface
     */
    protected function getTimezone()
    {
        return $this->_timezone;
    }

    /**
     * @return CollectionFactory
     */
    protected function getOrderCollectionFactory()
    {
        return $this->_orderCollectionFactory;
    }
}
