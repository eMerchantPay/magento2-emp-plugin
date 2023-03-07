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

namespace EMerchantPay\Genesis\Test\Unit\Helper;

use EMerchantPay\Genesis\Helper\Threeds;
use Magento\Sales\Model\Order;

/**
 * Class ThreedsTest
 * @package EMerchantPay\Genesis\Test\Unit\Helper
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ThreedsTest extends \EMerchantPay\Genesis\Test\Unit\AbstractTestCase
{
    /**
     * @var Threeds
     */
    protected $threedsHelper;

    /**
     * @var \Magento\Framework\App\Helper\Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $timezoneMock;

    /**
     * @var \EMerchantPay\Genesis\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $moduleHelper;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $orderMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Customer\Model\Customer
     */
    protected $customerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFacotryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpBasicMocks();
        $this->setUpContextMock();
        $this->setUpOrderMock();
        $this->setUpModuleHelperMock();
        $this->setUpCustomerMock();

        $this->threedsHelper = $this->getObjectManagerHelper()->getObject(
            Threeds::class,
            [
                'context'                => $this->contextMock,
                'orderCollectionFactory' => $this->orderCollectionFacotryMock,
                'moduleHelper'           => $this->moduleHelper,
                'timezone'               => $this->timezoneMock
            ]
        );
    }

    /**
     * @covers Threeds::__construct
     */
    public function testHasInstance()
    {
        $this->assertEquals('EMerchantPay\Genesis\Helper\Threeds', get_class($this->threedsHelper));
    }

    /**
     * @covers Threeds::fetchShippingIndicator()
     */
    public function testFetchShippingIndicator()
    {
        $this->assertEquals(
            'digital_goods',
            $this->threedsHelper->fetchShippingIndicator(false, $this->orderMock)
        );

        $this->assertEquals(
            'other',
            $this->threedsHelper->fetchShippingIndicator(true, $this->orderMock)
        );
    }

    /**
     * @covers Threeds::fetchReorderItemsIndicator()
     */
    public function testReorderItemIndicator()
    {
        $this->assertEquals('reordered', $this->threedsHelper->fetchReorderItemsIndicator($this->orderMock));

        $this->setUpOrderMock(2);

        $this->assertEquals('first_time', $this->threedsHelper->fetchReorderItemsIndicator($this->orderMock));
    }

    /**
     * @covers Threeds::fetchUpdateIndicator()
     */
    public function testFetchUpdateIndicator()
    {
        $this->assertEquals('more_than_60days', $this->threedsHelper->fetchUpdateIndicator($this->customerMock));
    }

    /**
     * @covers Threeds::fetchPasswordChangeIndicator()
     */
    public function testFetchPasswordChangeIndicator()
    {
        $this->assertEquals(
            'more_than_60days',
            $this->threedsHelper->fetchPasswordChangeIndicator($this->customerMock)
        );
    }

    /**
     * @covers Threeds::fetchShippingAddressUsageIndicator()
     */
    public function testFetchShippingAddressUsageIndicator()
    {
        $this->assertEquals(
            'more_than_60days',
            $this->threedsHelper->fetchShippingAddressUsageIndicator('2021-01-31 12:12:12')
        );
    }

    /**
     * @covers Threeds::fetchShippingAddressDateFirstUsed()
     */
    public function testFetchShippingAddressDateFirstUsed()
    {
        $this->assertEquals(
            '2021-01-30 12:12:12',
            $this->threedsHelper->fetchShippingAddressDateFirstUsed($this->orderMock)
        );
    }

    /**
     * @covers Threeds::fetchTransactionActivityLast24Hours()
     */
    public function testFetchTransactionActivityLast24Hours()
    {
        $this->assertEquals(
            1,
            $this->threedsHelper->fetchTransactionActivityLast24Hours($this->orderMock)
        );
    }

    /**
     * @covers Threeds::fetchTransactionActivityLast24Hours()
     */
    public function testFetchTransactionActivityPreviousYear()
    {
        $this->assertEquals(
            1,
            $this->threedsHelper->fetchTransactionActivityPreviousYear($this->orderMock)
        );
    }

    /**
     * @covers Threeds::fetchPurchasedCountLastHalfYear()
     */
    public function testFetchPurchasedCountLastHalfYear()
    {
        $this->assertEquals(
            1,
            $this->threedsHelper->fetchPurchasedCountLastHalfYear($this->orderMock)
        );
    }

    /**
     * @covers Threeds::getSortedCustomerAddress()
     */
    public function testGetSortedCustomerAdddresses()
    {
        $this->assertEquals(
            [
                [
                    'product_id' => 1,
                    'created_at' => '2022-01-30 12:12:12',
                    'updated_at' => '2022-01-31 12:12:12'
                ],
                [
                   'product_id' => 1,
                   'created_at' => '2021-01-30 12:12:12',
                   'updated_at' => '2021-01-31 12:12:12'
                ]
            ],
            $this->threedsHelper->getSortedCustomerAddress($this->customerMock)
        );
    }

    /**
     * @covers Threeds::fetchFirstOrderDate()
     */
    public function testFetchFirstOrderDate()
    {
        $this->assertEquals(
            '2021-01-30 12:12:12',
            $this->threedsHelper->fetchFirstOrderDate($this->orderMock)
        );
    }

    /**
     * @covers Threeds::fetchRegistrationIndicator()
     */
    public function testFetchRegistrationIndicator()
    {
        $this->assertEquals(
            'more_than_60days',
            $this->threedsHelper->fetchRegistrationIndicator(
                $this->orderMock,
                $this->threedsHelper->fetchFirstOrderDate($this->orderMock)
            )
        );
    }

    /**
     * Timezone Date Stub
     *
     * @return \DateTime
     */
    public function dateCallback($args)
    {
        if (!empty($args)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $args);
        }

        return new \DateTime();
    }

    /**
     * Sets up basic mock objects used in other Context and StoreManager mocks.
     */
    protected function setUpBasicMocks()
    {
        $this->timezoneMock = $this->getMockBuilder(
            \Magento\Framework\Stdlib\DateTime\Timezone::class
        )
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'date'
                ]
            )
            ->getMock();

        $this->timezoneMock->expects(static::any())
            ->method('date')
            ->will($this->returnCallback([$this, 'dateCallback']));

        $this->orderCollectionFacotryMock = $this->getMockBuilder(
            \Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'create'
                ]
            )
            ->getMock();
        $this->orderCollectionFacotryMock->expects(static::any())
            ->method('create')
            ->willReturn($this->getOrderCollectionMock());
    }

    /**
     * Sets up Context mock
     */
    protected function setUpContextMock()
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Order Mock
     */
    protected function setUpOrderMock($productId = 1)
    {
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCustomerId',
                    'getBillingAddress',
                    'getShippingAddress',
                    'getAllVisibleItems'
                ]
            )
            ->getMock();

        $this->orderMock->expects(static::any())
            ->method('getAllVisibleItems')
            ->willReturn([$this->getItemMock($productId)]);

        $this->orderMock->expects(static::any())
            ->method('getCustomerId')
            ->willReturn(1);

        $this->orderMock->expects(static::any())
            ->method('getShippingAddress')
            ->willReturn($this->getAddressesMock());
    }

    /**
     * Module Helper Mock
     */
    protected function setUpModuleHelperMock()
    {
        $this->moduleHelper = $this->getMockBuilder(\EMerchantPay\Genesis\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getMethodConfig',
                    'getCustomerOrders'
                ]
            )
            ->getMock();

        $this->moduleHelper->expects(static::any())
            ->method('getCustomerOrders')
            ->willReturn($this->getOrderCollectionMock());
    }

    /**
     * Customer Mock
     */
    protected function setUpCustomerMock()
    {
        $this->customerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getAddresses',
                    'getCreatedAt',
                    'getUpdatedAt'
                ]
            )
            ->getMock();

        $this->customerMock->expects(static::any())
            ->method('getAddresses')
            ->willReturn(
                [
                   0 => $this->getAddressesMock(),
                   1 => $this->getAddressesMock('2022-01-30 12:12:12', '2022-01-31 12:12:12')
                ]
            );

        $this->customerMock->expects(static::any())
            ->method('getCreatedAt')
            ->willReturn('2021-01-30 12:12:12');
        $this->customerMock->expects(static::any())
            ->method('getUpdatedAt')
            ->willReturn('2021-01-31 12:12:12');
    }

    /**
     * Create Item Instance Mock
     */
    protected function getItemMock($productId = 1)
    {
        $itemMock = $this->getMockBuilder('\Magento\Sales\Model\Order\Item')
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->expects(static::any())
            ->method('getProductId')
            ->willReturn($productId);

        return $itemMock;
    }

    /**
     * Create Order Address Mock
     */
    protected function getAddressesMock($createdAt = '2021-01-30 12:12:12', $updatedAt = '2021-01-31 12:12:12')
    {
        $address = $this->getMockBuilder(\Magento\Customer\Model\Address::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getData',
                    'getCustomerAddressId'
                ]
            )
            ->getMock();

        $address->expects(static::any())
            ->method('getData')
            ->willReturn(
                [
                    'product_id' => 1,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt
                ]
            );

        $address->expects(static::any())
            ->method('getCustomerAddressId')
            ->willReturn(1);

        return $address;
    }

    /**
     * Magento Order Collection Mock
     */
    protected function getOrderCollectionMock()
    {
        $orderCollectionMock = $this->getMockBuilder(
            \Magento\Sales\Model\ResourceModel\Order\Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'join',
                    'addFieldToFilter',
                    'setOrder',
                    'getSelect',
                    'setPageSize',
                    'getSize',
                    'getFirstItem',
                    'rewind',
                    'current',
                    'key',
                    'next',
                    'valid',
                    '_beforeLoad',
                    '_fetchAll',
                    '_afterLoad',
                    'getData',
                    'getNewEmptyItem',
                    'beforeAddLoadedItem'
                ]
            )
            ->getMock();

        $orderCollectionMock
            ->method('beforeAddLoadedItem')
            ->willReturn($orderCollectionMock);

        $orderCollectionMock
            ->method('_beforeLoad')
            ->willReturn($orderCollectionMock);

        $orderCollectionMock
            ->method('_afterLoad')
            ->willReturn($orderCollectionMock);

        $orderCollectionMock
            ->method('_fetchAll')
            ->willReturn([]);

        $orderCollectionMock->expects(static::any())
            ->method('join')
            ->willReturn($orderCollectionMock);

        $orderCollectionMock->expects(static::any())
            ->method('addFieldToFilter')
            ->willReturn($orderCollectionMock);

        $orderCollectionMock->expects(static::any())
            ->method('setOrder')
            ->willReturn($orderCollectionMock);

        $orderCollectionMock->expects(static::any())
            ->method('getSelect')
            ->willReturn($this->getMagentoSelectMock());

        $orderCollectionMock->expects(static::any())
            ->method('setPageSize')
            ->willReturn($orderCollectionMock);

        $orderCollectionMock->expects(static::any())
            ->method('getSize')
            ->willReturn(1);

        $orderCollectionMock->expects(static::any())
            ->method('getFirstItem')
            ->willReturn($this->getDataObjectMock());

        $iterator = new \ArrayIterator([$this->orderMock]);

        $orderCollectionMock->expects(static::any())
            ->method('rewind')
            ->willReturnCallback(function () use ($iterator): void {
                $iterator->rewind();
            });

        $orderCollectionMock->expects(static::any())
            ->method('current')
            ->willReturnCallback(function () use ($iterator) {
                return $iterator->current();
            });

        $orderCollectionMock->expects(static::any())
            ->method('key')
            ->willReturnCallback(function () use ($iterator) {
                return $iterator->key();
            });

        $orderCollectionMock->expects(static::any())
            ->method('next')
            ->willReturnCallback(function () use ($iterator): void {
                $iterator->next();
            });

        $orderCollectionMock->expects(static::any())
            ->method('valid')
            ->willReturnCallback(function () use ($iterator): bool {
                return $iterator->valid();
            });

        $orderCollectionMock
            ->method('getNewEmptyItem')
            ->willReturn($this->orderMock);

        $orderCollectionMock
            ->method('getData')
            ->willReturn([[$orderCollectionMock]]);

        return $orderCollectionMock;
    }

    /**
     * Magento Framework DB Select Mock
     */
    protected function getMagentoSelectMock()
    {
        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'reset',
                    'columns'
                ]
            )
            ->getMock();

        $selectMock->expects(static::any())
            ->method('reset')
            ->willReturn($selectMock);

        $selectMock->expects(static::any())
            ->method('columns')
            ->willReturn($selectMock);

        return $selectMock;
    }

    /**
     * Magento Framework Data Object Mock
     */
    protected function getDataObjectMock()
    {
        $dataMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCreatedAt'
                ]
            )
            ->getMock();

        $dataMock->expects(static::any())
            ->method('getCreatedAt')
            ->willReturn('2021-01-30 12:12:12');

        return $dataMock;
    }
}
