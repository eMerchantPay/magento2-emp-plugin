<?php
/*
 * Copyright (C) 2018-2025 emerchantpay Ltd.
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
 * @copyright   2018-2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Test\Unit\Helper;

use EMerchantPay\Genesis\Block\Frontend\Config as FrontendConfig;
use EMerchantPay\Genesis\Helper\Data as EMerchantPayDataHelper;
use EMerchantPay\Genesis\Model\ConfigFactory;
use EMerchantPay\Genesis\Test\Unit\AbstractTestCase;
use Exception;
use Genesis\Api\Constants\Transaction\States as GenesisTransactionStates;
use Genesis\Api\Constants\Transaction\Types as GenesisTransactionTypes;
use Genesis\Api\Constants\i18n;
use Genesis\Config as GenesisConfig;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Exception as WebApiException;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use stdClass;

/**
 * Test Data class
 *
 * Class DataTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class DataTest extends AbstractTestCase
{
    /**
     * @var EMerchantPayDataHelper
     */
    protected $moduleHelper;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var Resolver|MockObject
     */
    protected $localeResolverMock;

    /**
     * @var Transaction
     */
    protected $transactionMock;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepositoryMock;

    /**
     * @var (PaymentData&MockObject)|MockObject
     */
    protected $paymentDataMock;

    /**
     * @var (ConfigFactory&MockObject)|MockObject
     */
    protected $configFactoryMock;

    /**
     * @var (Session&MockObject)|MockObject
     */
    protected $customerSessionMock;

    /**
     * @var (FrontendConfig&MockObject)|MockObject
     */
    protected $configMock;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    protected $orderRepositoryMock;

    /**
     * @var ObjectManagerInterface|MockObjectiframetest
     */
    protected $objectManagerMock;

    /**
     * @return Transaction|MockObject
     */
    protected function getPaymentTransactionMock()
    {
        return $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdditionalInformation', 'save', 'setIsClosed', 'load', 'getId'])
            ->getMock();
    }

    /**
     * @return (PaymentData&MockObject)|MockObject
     */
    protected function getPaymentDataMock()
    {
        return $this->getMockBuilder(PaymentData::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return (ConfigFactory&MockObject)|MockObject
     */
    protected function getConfigFactoryMock()
    {
        return $this->getMockBuilder(ConfigFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return (Session&MockObject)|MockObject
     */
    protected function getCustomerSessionMock()
    {
        return $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return (FrontendConfig&MockObject)|MockObject
     */
    protected function getConfigMock()
    {
        return $this->getMockBuilder(FrontendConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return (TransactionRepositoryInterface&MockObject)|MockObject
     */
    protected function getPaymentTransactionRepositoryMock()
    {
        return $this->getMockBuilder(TransactionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save', 'getList', 'get', 'delete', 'create'])
            ->getMock();
    }

    /**
     * @return (OrderRepositoryInterface&MockObject)|MockObject
     */
    protected function getOrderRepositoryMock()
    {
        return $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save', 'get', 'getList', 'delete'])
            ->getMock();
    }

    /**
     * @return Order|MockObject
     */
    protected function getOrderMock()
    {
        $orderConfigMock = $this->getMockBuilder(OrderConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStateDefaultStatus'])
            ->getMock();

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getConfig',
                    'setState',
                    'setStatus',
                    'getInvoiceCollection',
                    'registerCancellation',
                    'setCustomerNoteNotify',
                ]
            )
            ->getMock();

        $orderMock->expects(static::any())
            ->method('getConfig')
            ->willReturn($orderConfigMock);

        $orderMock->expects(static::atLeastOnce())
            ->method('setStatus')
            ->willReturn($orderMock);

        $orderMock->expects(static::atLeastOnce())
            ->method('setState')
            ->willReturn($orderMock);

        return $orderMock;
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBasicMocks();
        $this->setUpContextMock();
        $this->setUpStoreManagerMock();
        $this->orderRepositoryMock       = $this->getOrderRepositoryMock();
        $this->transactionMock           = $this->getPaymentTransactionMock();
        $this->transactionRepositoryMock = $this->getPaymentTransactionRepositoryMock();
        $this->paymentDataMock           = $this->getPaymentDataMock();
        $this->configFactoryMock         = $this->getConfigFactoryMock();
        $this->customerSessionMock       = $this->getCustomerSessionMock();
        $this->configMock                = $this->getConfigMock();

        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock->method('create')
            ->with(Transaction::class)
            ->willReturn($this->transactionMock);

        $this->moduleHelper = $this->getMockBuilder(EMerchantPayDataHelper::class)
            ->setConstructorArgs([
                'transactionRepository'     => $this->transactionRepositoryMock,
                'objectManager'             => $this->objectManagerMock,
                'context'                   => $this->contextMock,
                'storeManager'              => $this->storeManagerMock,
                'localeResolver'            => $this->localeResolverMock,
                'orderRepository'           => $this->orderRepositoryMock,
                'paymentData'               => $this->paymentDataMock,
                'configFactory'             => $this->configFactoryMock,
                'customerSession'           => $this->customerSessionMock,
                'config'                    => $this->configMock,
            ])
            ->onlyMethods(['getPaymentTransaction', 'setTransactionAdditionalInfo'])
            ->getMock();
    }

    /**
     * Sets up basic mock objects used in other Context and StoreManager mocks.
     */
    protected function setUpBasicMocks()
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMock();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this->getMockBuilder(\Magento\Framework\Url::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUrl'])
            ->getMock();

        $this->localeResolverMock = $this->getMockBuilder(Resolver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLocale'])
            ->getMock();
    }

    /**
     * Sets up Context mock
     */
    protected function setUpContextMock()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getScopeConfig',
                    'getUrlBuilder'
                ]
            )
            ->getMock();

        $this->contextMock->expects(static::any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);

        $this->contextMock->expects(static::any())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);
    }

    /**
     * Sets up StoreManager mock.
     */
    protected function setUpStoreManagerMock()
    {
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManager::class)
            ->disableOriginalConstructor()
            ->addMethods(['getUrlBuilder'])
            ->onlyMethods(['getStore'])
            ->getMock();

        $this->storeManagerMock->expects(static::any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeManagerMock->expects(static::any())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);
    }

    /**
     * @covers EMerchantPayDataHelper::getNotificationUrl()
     */
    public function testGetNotificationUrl()
    {
        $data = [
            'routePath'  => 'emerchantpay/ipn',
            'domainName' => 'magento2-domain-here.com',
            'urls'       => [
                [
                    'secure' => true,
                    'protocol' => 'https'
                ],
                [
                    'secure' => false,
                    'protocol' => 'http'
                ],

                [
                    'secure' => null,
                    'protocol' => 'https'
                ],
            ]
        ];

        $this->storeMock->expects(static::once())
            ->method('isCurrentlySecure')
            ->willReturn(true);

        $conditions = [];
        $returns    = [];

        foreach ($data['urls'] as $index => $notificationUrlData) {
            $conditions[$index] = [
                'emerchantpay/ipn',
                [
                    '_store'  =>
                        $this->storeMock,
                    '_secure' =>
                        $notificationUrlData['secure'] === null
                            ? true
                            : $notificationUrlData['secure']
                ]
            ];
            $returns[$index] = "{$notificationUrlData['protocol']}://{$data['domainName']}/{$data['routePath']}/index/";
        }

        $this->urlBuilderMock->expects(static::exactly(count($data['urls'])))
            ->method('getUrl')
            ->withConsecutive(...$conditions)
            ->willReturnOnConsecutiveCalls($returns);

        foreach ($data['urls'] as $notificationUrlData) {
            $this->moduleHelper->getNotificationUrl(
                $notificationUrlData['secure']
            );
        }
    }

    /**
     * @covers EMerchantPayDataHelper::genTransactionId()
     */
    public function testGenTransactionId()
    {
        $orderId = 20;

        $transactionId = $this->moduleHelper->genTransactionId($orderId);

        $this->assertStringStartsWith("{$orderId}-", $transactionId);

        $anotherTransactionId = $this->moduleHelper->genTransactionId($orderId);

        $this->assertNotEquals($transactionId, $anotherTransactionId);
    }

    /**
     * @covers EMerchantPayDataHelper::getTransactionAdditionalInfoValue()
     */
    public function testGetTransactionAdditionalInfoValue()
    {
        $transactionMock = $this->getPaymentTransactionMock();

        $transactionMock->expects(static::exactly(3))
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_REDIRECT_URL     =>
                        'https://example.com/redirect/url',
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_STATUS           =>
                        GenesisTransactionStates::PENDING_ASYNC,
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        GenesisTransactionTypes::AUTHORIZE_3D
                ]
            );

        $this->assertEquals(
            GenesisTransactionStates::PENDING_ASYNC,
            $this->moduleHelper->getTransactionStatus(
                $transactionMock
            )
        );

        $this->assertEquals(
            GenesisTransactionTypes::AUTHORIZE_3D,
            $this->moduleHelper->getTransactionTypeByTransaction(
                $transactionMock
            )
        );

        $this->assertNull(
            $this->moduleHelper->getTransactionTerminalToken(
                $transactionMock
            )
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getTransactionAdditionalInfoValue()
     */
    public function testGetPaymentAdditionalInfoValue()
    {
        /**
         * @var $paymentMock InfoInterface|MockObject
         */
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTransactionAdditionalInfo'])
            ->getMock();

        $paymentMock->expects(static::exactly(2))
            ->method('getTransactionAdditionalInfo')
            ->willReturn(
                [
                    Transaction::RAW_DETAILS => [
                        EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_STATUS           =>
                            GenesisTransactionStates::APPROVED,
                        EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                            GenesisTransactionTypes::AUTHORIZE
                    ]
                ]
            );

        $this->assertEquals(
            GenesisTransactionTypes::AUTHORIZE,
            $this->moduleHelper->getPaymentAdditionalInfoValue(
                $paymentMock,
                EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE
            )
        );

        $this->assertNull(
            $this->moduleHelper->getPaymentAdditionalInfoValue(
                $paymentMock,
                EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_REDIRECT_URL
            )
        );
    }

    /**
     * @covers EMerchantPayDataHelper::setTokenByPaymentTransaction()
     *
     * @return void
     */
    public function testSetTokenByPaymentTransaction()
    {
        $declinedSaleTransactionMock = $this->getPaymentTransactionMock();

        $declinedSaleTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_STATUS           =>
                        GenesisTransactionStates::DECLINED,
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        GenesisTransactionTypes::SALE,
                ]
            );

        $this->moduleHelper->setTokenByPaymentTransaction($declinedSaleTransactionMock);

        $this->assertNull(GenesisConfig::getToken());

        $gatewayTerminalToken = 'gateway_token_098f6bcd4621d373cade4e832627b4f6';

        $approvedSaleTransactionMock = $this->getPaymentTransactionMock();

        $approvedSaleTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_STATUS           =>
                        GenesisTransactionStates::APPROVED,
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        GenesisTransactionTypes::SALE,
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TERMINAL_TOKEN   =>
                        $gatewayTerminalToken
                ]
            );

        $this->moduleHelper->setTokenByPaymentTransaction($approvedSaleTransactionMock);

        $this->assertEquals(
            $gatewayTerminalToken,
            GenesisConfig::getToken()
        );
    }

    /**
     * @covers EMerchantPayDataHelper::maskException()
     */
    public function testMaskException()
    {
        $exceptionMessage = 'Exception Message';

        $this->expectException(WebApiException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->moduleHelper->maskException(
            new Exception(
                $exceptionMessage,
                WebApiException::HTTP_INTERNAL_ERROR
            )
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getArrayFromGatewayResponse()
     */
    public function testGetArrayFromGatewayResponse()
    {
        $gatewayResponse = new stdClass();
        $gatewayResponse->status           = GenesisTransactionStates::APPROVED;
        $gatewayResponse->message          = 'Gateway Response Message Text';
        $gatewayResponse->transaction_type = GenesisTransactionTypes::PAYSAFECARD;

        $arrObj = $this->moduleHelper->getArrayFromGatewayResponse($gatewayResponse);

        $this->assertTrue(is_array($arrObj));

        $this->assertArrayHasKeys(
            [
                'status',
                'message',
                'transaction_type'
            ],
            $arrObj
        );
    }

    /**
     * @covers EMerchantPayDataHelper::setOrderState($order, GenesisTransactionStates::APPROVED)
     */
    public function testSetOrderStateProcessing()
    {
        $orderMock = $this->getOrderMock();

        /**
         * @var $orderConfigMock Order\Config|MockObject
         */
        $orderConfigMock = $orderMock->getConfig();

        $orderMock->expects(static::once())
            ->method('setState')
            ->willReturnSelf();

        $orderMock->expects(static::once())
            ->method('setStatus')
            ->willReturnSelf();

        $orderConfigMock->expects(static::once())
            ->method('getStateDefaultStatus')
            ->with(
                Order::STATE_PROCESSING
            )
            ->willReturn(
                Order::STATE_PROCESSING
            );

        $this->orderRepositoryMock->expects(static::once())
            ->method('save')
            ->with($orderMock);

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::APPROVED
        );
    }

    /**
     * @covers EMerchantPayDataHelper::setOrderState($order, GenesisTransactionStates::APPROVED)
     */
    public function testSetOrderStatePending()
    {
        $orderMock = $this->getOrderMock();

        /**
         * @var $orderConfigMock Order\Config|MockObject
         */
        $orderConfigMock = $orderMock->getConfig();

        $orderMock->expects(static::exactly(2))
            ->method('setState')
            ->willReturnSelf();

        $orderMock->expects(static::exactly(2))
            ->method('setStatus')
            ->willReturnSelf();

        $orderMock->expects(static::never())
            ->method('registerCancellation');

        $orderConfigMock->expects(static::exactly(2))
            ->method('getStateDefaultStatus')
            ->with(Order::STATE_PENDING_PAYMENT)
            ->willReturn(Order::STATE_PENDING_PAYMENT);

        $this->orderRepositoryMock->expects(static::exactly(2))
            ->method('save')
            ->with($orderMock);

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::PENDING
        );

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::PENDING_ASYNC
        );
    }

    /**
     * @covers EMerchantPayDataHelper::setOrderState($order, GenesisTransactionStates::APPROVED)
     */
    public function testSetOrderStateErrorOrDeclined()
    {
        $orderMock = $this->getOrderMock();

        /**
         * @var $orderConfigMock Order\Config|MockObject
         */
        $orderConfigMock = $orderMock->getConfig();

        $orderMock->expects(static::exactly(2))
            ->method('setState');

        $orderMock->expects(static::exactly(2))
            ->method('setStatus');

        $orderMock->expects(static::exactly(2))
            ->method('getInvoiceCollection')
            ->willReturn([]);

        $orderMock->expects(static::exactly(2))
            ->method('registerCancellation')
            ->willReturnSelf();

        $orderMock->expects(static::exactly(2))
            ->method('setCustomerNoteNotify')
            ->willReturnSelf();

        $orderConfigMock->expects(static::exactly(2))
            ->method('getStateDefaultStatus');

        $this->orderRepositoryMock->expects(static::exactly(2))
            ->method('save')
            ->with($orderMock);

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::ERROR
        );

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::DECLINED
        );
    }

    /**
     * @covers EMerchantPayDataHelper::setOrderState($order, GenesisTransactionStates::APPROVED)
     */
    public function testSetOrderStateOnPaymentTimeoutOrVoid()
    {
        $orderMock = $this->getOrderMock();

        /**
         * @var $orderConfigMock Order\Config|MockObject
         */
        $orderConfigMock = $orderMock->getConfig();

        $orderMock->expects(static::exactly(2))
            ->method('setState');

        $orderMock->expects(static::exactly(2))
            ->method('setStatus');

        $orderMock->expects(static::exactly(2))
            ->method('getInvoiceCollection')
            ->willReturn([]);

        $orderMock->expects(static::exactly(2))
            ->method('registerCancellation')
            ->willReturnSelf();

        $orderMock->expects(static::exactly(2))
            ->method('setCustomerNoteNotify')
            ->willReturnSelf();

        $orderConfigMock->expects(static::exactly(2))
            ->method('getStateDefaultStatus');

        $this->orderRepositoryMock->expects(static::exactly(2))
            ->method('save')
            ->with($orderMock);

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::TIMEOUT
        );

        $this->moduleHelper->setOrderState(
            $orderMock,
            GenesisTransactionStates::VOIDED
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getGlobalAllowedCurrencyCodes()
     */
    public function testGetGlobalAllowedCurrencyCodes()
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                Currency::XML_PATH_CURRENCY_ALLOW
            )
            ->willReturn('USD,EUR,GBP');

        $globalAllowedCurrencyCodes = $this->moduleHelper->getGlobalAllowedCurrencyCodes();

        $this->assertTrue(is_array($globalAllowedCurrencyCodes));

        $this->assertArrayKeysCount(
            3,
            $globalAllowedCurrencyCodes
        );

        $this->assertArrayHasValues(
            [
                'USD',
                'EUR',
                'GBP'
            ],
            $globalAllowedCurrencyCodes
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getGlobalAllowedCurrenciesOptions()
     */
    public function testGetGlobalAllowedCurrenciesOptions()
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(Currency::XML_PATH_CURRENCY_ALLOW)
            ->willReturn('USD,EUR,GBP');

        $allowedCurrenciesOptions = $this->moduleHelper->getGlobalAllowedCurrenciesOptions(
            [
                [
                    'value' => 'USD'
                ],
                [
                    'value' => 'GBP'
                ],
                [
                    'value' => 'AUD'
                ]
            ]
        );

        $this->assertTrue(is_array($allowedCurrenciesOptions));

        $this->assertArrayKeysCount(2, $allowedCurrenciesOptions);

        $this->assertEquals(
            [
                [
                    'value' => 'USD'
                ],
                [
                    'value' => 'GBP'
                ],
            ],
            $allowedCurrenciesOptions
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getLocale()
     */
    public function testGetDefaultLocale()
    {
        $this->localeResolverMock->expects(static::once())
            ->method('getLocale')
            ->willReturn(
                Resolver::DEFAULT_LOCALE
            );

        $gatewayLocale = $this->moduleHelper->getLocale('de');

        $this->assertTrue(
            i18n::isValidLanguageCode($gatewayLocale)
        );

        $this->assertEquals(
            $gatewayLocale,
            substr(
                Resolver::DEFAULT_LOCALE,
                0,
                2
            )
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getLocale()
     */
    public function testGetUnsupportedGatewayLocale()
    {
        $danishLocale = 'fa_AF';
        $defaultLocale = 'en';

        $this->localeResolverMock->expects(static::once())
            ->method('getLocale')
            ->willReturn(
                $danishLocale
            );

        $gatewayLocale = $this->moduleHelper->getLocale($defaultLocale);

        $this->assertTrue(i18n::isValidLanguageCode($gatewayLocale));

        $this->assertEquals(
            $gatewayLocale,
            $defaultLocale
        );
    }

    /**
     * @covers EMerchantPayDataHelper::canRefundTransaction()
     */
    public function testCanRefundCaptureTransaction()
    {
        $captureTransactionMock = $this->getPaymentTransactionMock();
        $captureTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE => GenesisTransactionTypes::CAPTURE
                ]
            );

        $this->assertTrue(
            $this->moduleHelper->canRefundTransaction(
                $captureTransactionMock
            )
        );
    }

    /**
     * @covers EMerchantPayDataHelper::canRefundTransaction()
     */
    public function testCanRefundPaySafeCardTransaction()
    {
        $captureTransactionMock = $this->getPaymentTransactionMock();
        $captureTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        GenesisTransactionTypes::PAYSAFECARD
                ]
            );

        $this->assertFalse(
            $this->moduleHelper->canRefundTransaction(
                $captureTransactionMock
            )
        );
    }

    /**
     * @covers EMerchantPayDataHelper::canRefundTransaction()
     */
    public function testCanRefundSaleTransaction()
    {
        $captureTransactionMock = $this->getPaymentTransactionMock();
        $captureTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(Transaction::RAW_DETAILS)
            ->willReturn(
                [
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE => GenesisTransactionTypes::SALE
                ]
            );

        $this->assertTrue(
            $this->moduleHelper->canRefundTransaction(
                $captureTransactionMock
            )
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getIsTransactionThreeDSecure()
     */
    public function testGetIsTransactionThreeDSecure()
    {
        $this->assertTrue(
            $this->moduleHelper->getIsTransactionThreeDSecure(
                GenesisTransactionTypes::AUTHORIZE_3D
            )
        );

        $this->assertFalse(
            $this->moduleHelper->getIsTransactionThreeDSecure(
                GenesisTransactionTypes::AUTHORIZE
            )
        );

        $this->assertTrue(
            $this->moduleHelper->getIsTransactionThreeDSecure(
                GenesisTransactionTypes::SALE_3D
            )
        );

        $this->assertFalse(
            $this->moduleHelper->getIsTransactionThreeDSecure(
                GenesisTransactionTypes::SALE
            )
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getErrorMessageFromGatewayResponse()
     */
    public function testGetSuccessErrorMessageFromGatewayResponse()
    {
        $successfulGatewayResponseMessage     = 'Transaction successful!';
        $successfulGatewayResponseTechMessage = 'Transaction has been processed successfully!';

        $validGatewayResponseWithMessage = $this->getSampleGatewayResponse(
            GenesisTransactionStates::APPROVED,
            GenesisTransactionTypes::AUTHORIZE,
            $successfulGatewayResponseMessage,
            $successfulGatewayResponseTechMessage
        );

        $gatewayResponseMessage = $this->moduleHelper->getErrorMessageFromGatewayResponse(
            $validGatewayResponseWithMessage
        );

        $this->assertStringStartsWith(
            $successfulGatewayResponseMessage,
            $gatewayResponseMessage
        );

        $this->assertStringEndsWith(
            $successfulGatewayResponseTechMessage,
            $gatewayResponseMessage
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getErrorMessageFromGatewayResponse()
     */
    public function testGetFailedErrorMessageFromGatewayResponse()
    {
        $validGatewayResponseWithMessage = $this->getSampleGatewayResponse(
            GenesisTransactionStates::DECLINED,
            GenesisTransactionTypes::SALE
        );

        $gatewayResponseMessage = $this->moduleHelper->getErrorMessageFromGatewayResponse(
            $validGatewayResponseWithMessage
        );

        $this->assertEquals(
            EMerchantPayDataHelper::GENESIS_GATEWAY_ERROR_MESSAGE_DEFAULT,
            $gatewayResponseMessage
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getErrorMessageFromGatewayResponse()
     */
    public function testGetPendingAsyncSuccessErrorMessageFromGatewayResponse()
    {
        $successfulGatewayResponseMessage          = 'Transaction successful!';
        $successfulGatewayResponseTechnicalMessage = 'Transaction has been processed successfully!';

        $validGatewayResponseWithMessage = $this->getSampleGatewayResponse(
            GenesisTransactionStates::PENDING_ASYNC,
            GenesisTransactionTypes::REFUND,
            $successfulGatewayResponseMessage,
            $successfulGatewayResponseTechnicalMessage
        );

        $gatewayResponseMessage = $this->moduleHelper->getErrorMessageFromGatewayResponse(
            $validGatewayResponseWithMessage
        );

        $this->assertStringStartsWith(
            $successfulGatewayResponseMessage,
            $gatewayResponseMessage
        );

        $this->assertStringEndsWith(
            $successfulGatewayResponseTechnicalMessage,
            $gatewayResponseMessage
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getReturnUrl
     */
    public function testGetReturnUrl()
    {
        $moduleCode          = 'emerchantpay_checkout';
        $returnAction        = EMerchantPayDataHelper::ACTION_RETURN_SUCCESS;
        $expectedUrlIframe   = 'https://example.com/emerchantpay/checkout/iframe/action/success';
        $expectedUrlRedirect = 'https://example.com/emerchantpay/checkout/redirect/action/success';

        // Mocking the Config class to return true for isIframeProcessingEnabled
        $configMock = $this->createMock(FrontendConfig::class);
        $configMock->expects($this->exactly(2))
            ->method('isIframeProcessingEnabled')
            ->willReturnOnConsecutiveCalls(true, false);

        // Use reflection to set the protected property _config
        $reflectionClass = new ReflectionClass(EMerchantPayDataHelper::class);
        $configProperty  = $reflectionClass->getProperty('_config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($this->moduleHelper, $configMock);

        // Mocking store
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        // Mocking getUrl with correct parameters
        $this->urlBuilderMock->expects($this->exactly(2))
            ->method('getUrl')
            ->withConsecutive(
                [
                    'emerchantpay/checkout/iframe',
                    [
                        '_store'  => $this->storeMock,
                        '_secure' => null,
                        'action'  => $returnAction
                    ]
                ],
                [
                    'emerchantpay/checkout/redirect',
                    [
                        '_store'  => $this->storeMock,
                        '_secure' => null,
                        'action'  => $returnAction
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls($expectedUrlIframe, $expectedUrlRedirect);

        // Test iframe URL generation
        $actualUrlIframe = $this->moduleHelper->getReturnUrl($moduleCode, $returnAction);
        $this->assertEquals($expectedUrlIframe, $actualUrlIframe);

        // Test redirect URL generation
        $actualUrlRedirect = $this->moduleHelper->getReturnUrl($moduleCode, $returnAction);
        $this->assertEquals($expectedUrlRedirect, $actualUrlRedirect);
    }

    /**
     * @covers EMerchantPayDataHelper::updateTransactionAdditionalInfo
     *
     * @return void
     *
     * @throws Exception
     */
    public function testUpdateTransactionAdditionalInfoTransactionFound()
    {
        $transactionId          = '123';
        $responseObject         = new stdClass();
        $shouldCloseTransaction = true;

        $this->transactionMock->method('load')
            ->with($transactionId, 'txn_id')
            ->willReturnSelf();

        $this->transactionMock->method('getId')
            ->willReturn(1);

        $this->moduleHelper->method('getPaymentTransaction')
            ->with($transactionId)
            ->willReturn($this->transactionMock);

        $this->moduleHelper->expects($this->once())
            ->method('setTransactionAdditionalInfo')
            ->with($this->transactionMock, $responseObject);

        $this->transactionMock->expects($this->once())
            ->method('setIsClosed')
            ->with(true);

        $this->transactionRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->transactionMock);

        $result = $this->moduleHelper->updateTransactionAdditionalInfo(
            $transactionId,
            $responseObject,
            $shouldCloseTransaction
        );

        $this->assertTrue($result);
    }

    /**
     * @covers EMerchantPayDataHelper::updateTransactionAdditionalInfo
     *
     * @return void
     *
     * @throws Exception
     */
    public function testUpdateTransactionAdditionalInfoTransactionNotFound()
    {
        $transactionId          = '123';
        $responseObject         = new stdClass();
        $shouldCloseTransaction = false;

        $this->moduleHelper = $this->getMockBuilder(EMerchantPayDataHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPaymentTransaction', 'setTransactionAdditionalInfo'])
            ->getMock();

        $this->moduleHelper->method('getPaymentTransaction')
            ->with($transactionId)
            ->willReturn(null);

        $this->moduleHelper->expects($this->never())
            ->method('setTransactionAdditionalInfo');

        $this->transactionRepositoryMock->expects($this->never())
            ->method('save');

        $result = $this->moduleHelper->updateTransactionAdditionalInfo(
            $transactionId,
            $responseObject,
            $shouldCloseTransaction
        );

        $this->assertFalse($result);
    }
}
