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

namespace EMerchantPay\Genesis\Test\Unit\Model\Method;

use Magento\Sales\Model\Order;
use EMerchantPay\Genesis\Helper\Data as EMerchantPayDataHelper;
use Genesis\Api\Constants\Transaction\Types as GenesisTransactionTypes;

/**
 * Base Test Method Class for Payment Method Models
 *
 * Class AbstractMethodTest
 * @package EMerchantPay\Genesis\Test\Unit\Model\Method
 */
abstract class AbstractMethodTest extends \EMerchantPay\Genesis\Test\Unit\AbstractTestCase
{
    const ORDER_AMOUNT = 1.05;

    const CREDIT_CARD_VISA = '4200000000000000';

    const API_LOGIN    = 'api_login-23e9b38424f6b1688aed91495fa4601a';
    const API_PASSWORD = 'api_password-0631a4be134a6d344bec9a99ac6954d4';
    const API_TOKEN    = 'api_token-c7dd1174bab427d2333b66b12a8ed703';

    /**
     * @var \EMerchantPay\Genesis\Model\Method\Checkout
     */
    protected $paymentMethodInstance;

    /**
     * @var string
     */
    protected $paymentMethodCode = null;
    /**
     * @var \ReflectionClass
     */
    protected $paymentMethodReflection;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \Magento\Payment\Model\InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    /**
     * @var \EMerchantPay\Genesis\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataHelperMock;

    /**
     * @var \EMerchantPay\Genesis\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configHelperMock;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var \EMerchantPay\Genesis\Logger\Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerHelperMock;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $psrLoggerMock;

    /**
     * @return \EMerchantPay\Genesis\Model\Method\Checkout
     */
    protected function getPaymentMethodInstance()
    {
        return $this->paymentMethodInstance;
    }

    /**
     * @return \ReflectionClass
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

    abstract protected function getPaymentMethodClassName();

    protected function init()
    {
        parent::init();

        $this->paymentMethodReflection = new \ReflectionClass(
            $this->getPaymentMethodClassName()
        );
    }

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

        $this->scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getMock();

        $this->paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getOrder', 'getId', 'setAdditionalInformation', 'getAdditionalInformation',
                'setIsTransactionDenied', 'setIsTransactionClosed', 'decrypt', 'getCcLast4',
                'getParentTransactionId', 'getPoNumber', 'setIsTransactionPending', 'setTransactionAdditionalInfo',
                'getCcNumber', 'getCcExpYear', 'getCcExpMonth', 'getCcCid', 'getCcOwner',
                'setTransactionId', 'addData'
            ])
            ->getMock();

        $this->dataHelperMock = $this->getMockBuilder(\EMerchantPay\Genesis\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getMethodConfig',
                    'getNotificationUrl',
                    'getReturnUrl',
                    'genTransactionId',
                    'buildOrderUsage',
                    'buildOrderDescriptionText',
                    'getLocale',
                    'executeGatewayRequest',
                    'getGatewayResponseObject',
                    'maskException',
                    'lookUpAuthorizationTransaction',
                    'lookUpCaptureTransaction',
                    'lookUpVoidReferenceTransaction'
                ]
            )
            ->getMock();

        $this->configHelperMock = $this->getMockBuilder('EMerchantPay\Genesis\Model\Config')
            ->disableOriginalConstructor()
            ->setMethods(['getMethodCode', 'initGatewayClient', 'getScopeConfig'])
            ->getMock();

        $this->configHelperMock->expects(self::any())
            ->method('initGatewayClient')
            ->willReturn(null);

        $this->checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getQuote',
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

        $this->paymentMethodInstance = $this->getObjectManagerHelper()->getObject(
            $this->getPaymentMethodClassName(),
            [
                'scopeConfig'     => $this->scopeConfigMock,
                'moduleHelper'    => $this->dataHelperMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'loggerHelper'    => $this->loggerHelperMock
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

    protected function setupLoggerMocks()
    {
        $this->loggerHelperMock = $this->getMockBuilder(
            \EMerchantPay\Genesis\Logger\Logger::class
        )->getMock();

        $this->psrLoggerMock = $this->getMockBuilder(
            \Psr\Log\LoggerInterface::class
        )->getMock();

        $this->loggerHelperMock
            ->method('getLogger')
            ->willReturn(
                $this->psrLoggerMock
            );
    }

    /**
     * Get mock for order
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrderMock()
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getId',
                    'getIncrementId',
                    'getStoreId',
                    'getBillingAddress',
                    'getShippingAddress',
                    'getBaseCurrencyCode',
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
     * @return \PHPUnit_Framework_MockObject_MockObject
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
     * @return \Faker\Generator
     */
    protected function getFakerObject()
    {
        $faker = \Faker\Factory::create();
        $faker->addProvider(new \Faker\Provider\en_US\Person($faker));
        $faker->addProvider(new \Faker\Provider\Payment($faker));
        $faker->addProvider(new \Faker\Provider\en_US\Address($faker));
        $faker->addProvider(new \Faker\Provider\en_US\PhoneNumber($faker));
        $faker->addProvider(new \Faker\Provider\Internet($faker));

        return $faker;
    }
}
