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

namespace EMerchantPay\Genesis\Test\Unit\Model\Method;

use EMerchantPay\Genesis\Helper\Data;
use EMerchantPay\Genesis\Logger\Logger;
use EMerchantPay\Genesis\Model\Config;
use EMerchantPay\Genesis\Model\Method\Checkout;
use EMerchantPay\Genesis\Test\Unit\AbstractTestCase;
use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Internet;
use Faker\Provider\Payment;
use Faker\Provider\en_US\Address;
use Faker\Provider\en_US\Person;
use Faker\Provider\en_US\PhoneNumber;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Base Test Method Class for Payment Method Models
 *
 * Class AbstractMethodTest
 */
abstract class AbstractMethodTest extends AbstractTestCase
{
    public const ORDER_AMOUNT = 1.05;

    public const CREDIT_CARD_VISA = '4200000000000000';

    public const API_LOGIN    = 'api_login-23e9b38424f6b1688aed91495fa4601a';
    public const API_PASSWORD = 'api_password-0631a4be134a6d344bec9a99ac6954d4';
    public const API_TOKEN    = 'api_token-c7dd1174bab427d2333b66b12a8ed703';

    /**
     * @var Checkout
     */
    protected $paymentMethodInstance;

    /**
     * @var string
     */
    protected $paymentMethodCode = null;
    /**
     * @var ReflectionClass
     */
    protected $paymentMethodReflection;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var InfoInterface|MockObject
     */
    protected $paymentMock;

    /**
     * @var Data|MockObject
     */
    protected $dataHelperMock;

    /**
     * @var Config|MockObject
     */
    protected $configHelperMock;

    /**
     * @var Session|MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var Logger|MockObject
     */
    protected $loggerHelperMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $psrLoggerMock;

    /**
     * @var (OrderPaymentRepositoryInterface&MockObject)|MockObject
     */
    protected $paymentRepositoryMock;

    /**
     * @return Checkout
     */
    protected function getPaymentMethodInstance()
    {
        return $this->paymentMethodInstance;
    }

    /**
     * @return ReflectionClass
     */
    protected function getPaymentMethodReflection()
    {
        return $this->paymentMethodReflection;
    }

    /**
     * @return mixed|string
     */
    protected function getPaymentMethodCode()
    {
        if ($this->paymentMethodCode === null) {
            $this->paymentMethodCode = $this->getPaymentMethodReflection()->getConstant('CODE');
        }

        return $this->paymentMethodCode;
    }

    /**
     * @return mixed
     */
    abstract protected function getPaymentMethodClassName();

    /**
     * @return void
     *
     * @throws ReflectionException
     */
    protected function init()
    {
        parent::init();

        $this->paymentMethodReflection = new ReflectionClass(
            $this->getPaymentMethodClassName()
        );
    }

    /**
     * @return int
     */
    protected function getGeneratedOrderId()
    {
        return $this->getFakerObject()->randomNumber(8);
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMock();

        $this->paymentMock = $this->getMockBuilder(Order\Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'addData', 'decrypt', 'getAdditionalInformation', 'getCcExpMonth', 'getCcExpYear', 'getCcLast4',
                'getCcOwner', 'getId', 'getOrder', 'getParentTransactionId', 'getPoNumber', 'setAdditionalInformation',
                'setIsTransactionClosed', 'setIsTransactionPending', 'setTransactionAdditionalInfo', 'setTransactionId',
            ])
            ->addMethods(['setIsTransactionDenied', 'getCcNumber', 'getCcCid'])
            ->getMock();

        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'buildOrderDescriptionText', 'buildOrderUsage', 'executeGatewayRequest', 'genTransactionId',
                    'getGatewayResponseObject', 'getLocale', 'getMethodConfig', 'getNotificationUrl', 'getReturnUrl',
                    'lookUpAuthorizationTransaction', 'lookUpCaptureTransaction',  'lookUpVoidReferenceTransaction',
                    'maskException',
                ]
            )
            ->getMock();

        $this->configHelperMock = $this->getMockBuilder('EMerchantPay\Genesis\Model\Config')
            ->disableOriginalConstructor()
            ->onlyMethods(['getMethodCode', 'initGatewayClient', 'getScopeConfig'])
            ->getMock();

        $this->configHelperMock->expects(self::any())
            ->method('initGatewayClient')
            ->willReturn(null);

        $this->checkoutSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->addMethods(
                [
                    'setEmerchantPayLastCheckoutError',
                    'setEmerchantPayCheckoutRedirectUrl',
                    'getEmerchantPayLastCheckoutError',
                    'getEmerchantPayCheckoutRedirectUrl'
                ]
            )
            ->getMock();

        $this->configHelperMock->expects($this->any())
            ->method('getMethodCode')
            ->willReturn(
                $this->getPaymentMethodCode()
            );

        $this->dataHelperMock->expects($this->once())
            ->method('getMethodConfig')
            ->willReturn(
                $this->configHelperMock
            );

        $this->setupLoggerMocks();

        $this->paymentRepositoryMock = $this->getMockBuilder(OrderPaymentRepositoryInterface::class)
            ->getMock();

        $this->paymentMethodInstance = $this->getObjectManagerHelper()->getObject(
            $this->getPaymentMethodClassName(),
            [
                'scopeConfig'       => $this->scopeConfigMock,
                'moduleHelper'      => $this->dataHelperMock,
                'checkoutSession'   => $this->checkoutSessionMock,
                'loggerHelper'      => $this->loggerHelperMock,
                'paymentRepository' => $this->paymentRepositoryMock
            ]
        );

        $this->configHelperMock->expects(self::any())
            ->method('getScopeConfig')
            ->willReturn(
                $this->scopeConfigMock
            );

        $this->configHelperMock->setMethodCode(
            $this->getPaymentMethodCode()
        );

        $this->paymentMethodInstance->setInfoInstance(
            $this->paymentMock
        );

        $this->assertInstanceOf(
            $this->getPaymentMethodClassName(),
            $this->getPaymentMethodInstance()
        );

        $this->assertEquals(
            $this->dataHelperMock,
            $this->getPaymentMethodInstance()->getModuleHelper()
        );

        $this->assertEquals(
            $this->getPaymentMethodCode(),
            $this->configHelperMock->getMethodCode()
        );

        $this->assertEquals(
            $this->configHelperMock,
            $this->getPaymentMethodInstance()->getConfigHelper()
        );
    }

    /**
     * @return void
     */
    protected function setupLoggerMocks()
    {
        $this->loggerHelperMock = $this->getMockBuilder(
            Logger::class
        )->getMock();

        $this->psrLoggerMock = $this->getMockBuilder(
            LoggerInterface::class
        )->getMock();

        $this->loggerHelperMock
            ->method('getLogger')
            ->willReturn(
                $this->psrLoggerMock
            );
    }

    /**
     * Get mock for order
     *
     * @return MockObject
     */
    protected function getOrderMock()
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getId',
                    'getIncrementId',
                    'getStoreId',
                    'getBillingAddress',
                    'getShippingAddress',
                    'getOrderCurrencyCode',
                    'getBaseTaxAmount',
                    'getRemoteIp',
                    '__wakeup'
                ]
            )
            ->getMock();

        return $orderMock;
    }

    /**
     * Get Mock for Order Address
     *
     * @return MockObject
     */
    protected function getOrderAddressMock()
    {
        $faker = $this->getFakerObject();

        $expectations = [
            'getFirstname'  => $faker->firstName,
            'getLastname'   => $faker->lastName,
            'getStreetLine' => $faker->streetAddress,
            'getPostcode'   => $faker->postcode,
            'getCity'       => $faker->city,
        ];

        $orderAddress = $this->getMockBuilder(\Magento\Sales\Model\Order\Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        foreach ($expectations as $methodName => $expectation) {
            if (is_array($expectation)) {
                foreach ($expectation as $methodParam => $returnValue) {
                    $orderAddress->expects(static::any())
                        ->method($methodName)
                        ->with($methodParam)
                        ->willReturn(
                            $returnValue
                        );
                }

                continue;
            }

            $orderAddress->expects(static::any())
                ->method($methodName)
                ->willReturn(
                    $expectation
                );
        }

        return $orderAddress;
    }

    /**
     * Builds a Faker Object
     *
     * @return Generator
     */
    protected function getFakerObject()
    {
        $faker = Factory::create();
        $faker->addProvider(new Person($faker));
        $faker->addProvider(new Payment($faker));
        $faker->addProvider(new Address($faker));
        $faker->addProvider(new PhoneNumber($faker));
        $faker->addProvider(new Internet($faker));

        return $faker;
    }
}
