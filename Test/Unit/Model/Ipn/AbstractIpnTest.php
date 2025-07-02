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

namespace EMerchantPay\Genesis\Test\Unit\Model\Ipn;

use EMerchantPay\Genesis\Helper\Data as DataHelper;
use EMerchantPay\Genesis\Model\Config;
use EMerchantPay\Genesis\Model\Ipn\AbstractIpn;
use EMerchantPay\Genesis\Model\Ipn\CheckoutIpn;
use EMerchantPay\Genesis\Test\Unit\AbstractTestCase;
use Genesis\Api\Constants\Transaction\States;
use Genesis\Api\Constants\Transaction\Types;
use Genesis\Api\Notification as Notification;
use Genesis\Config as GenesisConfig;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Status\History;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface as Logger;
use stdClass;
use EMerchantPay\Genesis\Model\Service\MultiCurrencyProcessingService;

/**
 * Class AbstractIpnTest
 */

abstract class AbstractIpnTest extends AbstractTestCase
{
    /**
     * @var CheckoutIpn
     */
    protected $ipnInstance;

    /**
     * @var array $postParams
     */
    protected $postParams;

    /**
     * @var stdClass $reconciliationObj
     */
    protected $reconciliationObj;

    /**
     * @var string $customerPwd
     */
    protected $customerPwd;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var OrderFactory|MockObject
     */
    protected $orderFactoryMock;

    /**
     * @var OrderSender|MockObject
     */
    protected $orderSenderMock;

    /**
     * @var CreditmemoSender|MockObject
     */
    protected $creditmemoSenderMock;

    /**
     * @var Logger|MockObject
     */
    protected $loggerMock;

    /**
     * @var DataHelper|MockObject
     */
    protected $dataHelperMock;

    /**
     * @var Notification|MockObject
     */
    protected $notificationMock;

    /**
     * @var Config|MockObject
     */
    protected $configHelperMock;

    /**
     * @var MultiCurrencyProcessingService|MockObject
     */
    protected $multiCurrencyProcessingServiceMock;

    /**
     * Gets IPN model class name
     *
     * @return string
     */
    abstract protected function getIpnClassName();

    /**
     * Creates reconciliation object
     *
     * @return stdClass
     */
    abstract protected function createReconciliationObj();

    /**
     * Get mock for data helper
     *
     * @return DataHelper|MockObject
     */
    abstract protected function getDataHelperMock();

    /**
     * Get mock for payment
     *
     * @return OrderPaymentInterface|MockObject
     */
    abstract protected function getPaymentMock();

    /**
     * Gets IPN model instance
     *
     * @return CheckoutIpn
     */
    protected function getIpnInstance()
    {
        return $this->ipnInstance;
    }

    /**
     * Creates signature param for the IPN POST request
     *
     * @param string $unique_id
     * @param string $customerPwd
     *
     * @return string
     */
    protected static function createSignature($unique_id, $customerPwd)
    {
        return hash('sha1', $unique_id . $customerPwd);
    }

    /**
     * Get mock for context
     *
     * @return Context|MockObject
     */
    protected function getContextMock()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock($this->contextMock);

        return $this->contextMock;
    }

    /**
     * Get mock for order factory
     *
     * @return OrderFactory|MockObject
     */
    protected function getOrderFactoryMock()
    {
        $this->orderFactoryMock = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->orderFactoryMock->expects(self::once())
            ->method('create')
                ->willReturn($this->getOrderMock());

        return $this->orderFactoryMock;
    }

    /**
     * Get mock for order sender
     *
     * @return OrderSender|MockObject
     */
    protected function getOrderSenderMock()
    {
        return $this->orderSenderMock = $this->getMockBuilder(OrderSender::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get mock for credit memo sender
     *
     * @return CreditmemoSender|MockObject
     */
    protected function getCreditmemoSenderMock()
    {
        return $this->creditmemoSenderMock = $this->getMockBuilder(CreditmemoSender::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get mock for logger
     * @return Logger|MockObject
     */
    protected function getLoggerMock()
    {
        return $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * Get mock for order
     *
     * @return Order|MockObject
     */
    protected function getOrderMock()
    {
        list($incrementId) = explode('-', $this->reconciliationObj->transaction_id);

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByIncrementId','getId','getPayment', 'addCommentToStatusHistory'])
            ->getMock();

        $orderMock->expects(self::atLeastOnce())
            ->method('getId')
                ->willReturn(1);

        $orderMock->expects(self::once())
            ->method('getPayment')
                ->willReturn($this->getPaymentMock());

        $orderMock->expects(self::once())
            ->method('loadByIncrementId', 'getId')
            ->with($incrementId)
                ->willReturn($orderMock);

        $orderMock->expects(self::once())
            ->method('addCommentToStatusHistory')
                ->willReturn($this->getOrderStatusHistoryMock());

        return $orderMock;
    }

    /**
     * Get Mock for OrderStatusHistory
     *
     * @return History|MockObject
     */
    protected function getOrderStatusHistoryMock()
    {
        $statusHistory = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setIsCustomerNotified'])
            ->getMock();

        $statusHistory->expects(self::once())
            ->method('setIsCustomerNotified')
                ->willReturnSelf();

        return $statusHistory;
    }

    /**
     * Get notification function name based on transaction type
     *
     * @param string $transaction_type
     *
     * @return string
     */
    protected function getNotificationFunctionName($transaction_type)
    {
        $result=null;
        switch ($transaction_type) {
            case Types::AUTHORIZE:
            case Types::AUTHORIZE_3D:
                $result = 'registerAuthorizationNotification';
                break;
            case Types::SALE:
            case Types::SALE_3D:
                $result = 'registerCaptureNotification';
                break;
            default:
                break;
        }

        return $result;
    }

    /**
     * @param stdClass $responseObject
     * @return bool
     */
    protected function getShouldSetCurrentTranPending($responseObject)
    {
        return $responseObject->status != States::APPROVED;
    }

    /**
     * Get if Authorize or Capture event should be executed
     *
     * @param $status
     *
     * @return InvokedCount
     */
    protected function getShouldExecuteAuthoirizeCaptureEvent($status)
    {
        if (States::APPROVED == $status) {
            return self::once();
        }

        return self::never();
    }

    /**
     * @param stdClass $responseObject
     *
     * @return bool
     */
    protected function getShouldCloseCurrentTransaction($responseObject)
    {
        $voidableTransactions = [
            Types::AUTHORIZE,
            Types::AUTHORIZE_3D
        ];

        return !in_array($responseObject->transaction_type, $voidableTransactions);
    }

    /**
     * mock function replacing EMerchantPay\Genesis\Model\Config::initGatewayClient
     */
    protected function initGatewayClientMock()
    {
        GenesisConfig::setPassword(
            $this->customerPwd
        );
    }

    /**
     * Get mock for notification
     *
     * @return Notification|MockObject
     */
    protected function getNotificationMock()
    {
        $reconciliationObj = $this->createReconciliationObj();

        $this->notificationMock = $this->createMock(
            Notification::class,
            [
                'isAuthentic',
                'initReconciliation',
                'getReconciliationObject'
            ],
            [
                $this->postParams
            ]
        );

        $this->notificationMock->expects(self::once())
            ->method('isAuthentic')
                ->willReturn(true);

        $this->notificationMock->expects(self::once())
            ->method('initReconciliation')
                ->willReturn(new stdClass());

        $this->notificationMock->expects(self::once())
            ->method('getReconciliationObject')
                ->willReturn($reconciliationObj);

        return $this->notificationMock;
    }

    /**
     * Get mock for model config
     *
     * @return Config|MockObject
     */
    protected function getConfigHelperMock()
    {
        $this->configHelperMock = $this->getMockBuilder('EMerchantPay\Genesis\Model\Config')
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'initGatewayClient',
                    'getCheckoutTitle'
                ]
            )
            ->addMethods(['initReconciliation'])
            ->getMock();

        $this->configHelperMock->expects(self::once())
            ->method('initGatewayClient')
                ->willReturn($this->initGatewayClientMock());

        $this->configHelperMock->expects(self::never())
            ->method('initReconciliation')
                ->willReturn($this->getNotificationMock());

        $this->configHelperMock->expects(self::atLeastOnce())
            ->method('getCheckoutTitle')
                ->willReturn('sample reconciliation message');

        return $this->configHelperMock;
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ipnInstance = $this->getObjectManagerHelper()->getObject(
            $this->getIpnClassName(),
            $this->createParams()
        );

        $this->assertInstanceOf(
            $this->getIpnClassName(),
            $this->getIpnInstance()
        );
    }

    /**
     * @covers AbstractIpn::handleGenesisNotification()
     */
    public function testGenesisNotification()
    {
        $this->ipnInstance->handleGenesisNotification();
    }

    /**
     * Creates constructor parameters
     *
     * @return array
     */
    public function createParams()
    {
        $this->getConfigHelperMock();

        $this->getContextMock();
        $this->getOrderFactoryMock();
        $this->getOrderSenderMock();
        $this->getCreditmemoSenderMock();
        $this->getLoggerMock();
        $this->getDataHelperMock();

        $this->assertInstanceOf(
            Context::class,
            $this->contextMock
        );

        $this->assertInstanceOf(
            OrderFactory::class,
            $this->orderFactoryMock
        );

        $this->assertInstanceOf(
            OrderSender::class,
            $this->orderSenderMock
        );

        $this->assertInstanceOf(
            CreditmemoSender::class,
            $this->creditmemoSenderMock
        );

        $this->assertInstanceOf(
            Logger::class,
            $this->loggerMock
        );

        $this->assertInstanceOf(
            DataHelper::class,
            $this->dataHelperMock
        );

        $this->multiCurrencyProcessingServiceMock = $this->createMock(MultiCurrencyProcessingService::class);

        $this->multiCurrencyProcessingServiceMock->method('getWpfAmount')
            ->willReturn(271.97);

        $constructorParams = [
            'context'                        => $this->contextMock,
            'orderFactory'                   => $this->orderFactoryMock,
            'orderSender'                    => $this->orderSenderMock,
            'creditmemoSender'               => $this->creditmemoSenderMock,
            'logger'                         => $this->loggerMock,
            'moduleHelper'                   => $this->dataHelperMock,
            'multiCurrencyProcessingService' => $this->multiCurrencyProcessingServiceMock,
            'data'                           => $this->postParams
        ];

        return $constructorParams;
    }
}
