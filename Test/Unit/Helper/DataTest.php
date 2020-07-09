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

use EMerchantPay\Genesis\Helper\Data as EMerchantPayDataHelper;
use Genesis\API\Constants\Transaction\States as GenesisTransactionStates;
use Genesis\API\Constants\Transaction\Types as GenesisTransactionTypes;

/**
 * Class DataTest
 * @package EMerchantPay\Genesis\Test\Unit\Helper
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DataTest extends \EMerchantPay\Genesis\Test\Unit\AbstractTestCase
{
    /**
     * @var EMerchantPayDataHelper
     */
    protected $moduleHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \Magento\Framework\App\Helper\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\Store\Model\Store|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeMock;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var \Magento\Framework\Locale\Resolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeResolverMock;

    /**
     * @return \Magento\Sales\Model\Order\Payment\Transaction|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPaymentTransactionMock()
    {
        return $this->getMockBuilder('\Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getAdditionalInformation'
                ]
            )
            ->getMock();
    }

    /**
     * @return \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrderMock()
    {
        $orderConfigMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Config::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getStateDefaultStatus'
                ]
            )
            ->getMock();

        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getConfig',
                    'setState',
                    'setStatus',
                    'getInvoiceCollection',
                    'registerCancellation',
                    'setCustomerNoteNotify',
                    'save'
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
    protected function setUp()
    {
        parent::setUp();

        $this->setUpBasicMocks();
        $this->setUpContextMock();
        $this->setUpStoreManagerMock();

        $this->moduleHelper = $this->getObjectManagerHelper()->getObject(
            EMerchantPayDataHelper::class,
            [
                'context'        => $this->contextMock,
                'storeManager'   => $this->storeManagerMock,
                'localeResolver' => $this->localeResolverMock
            ]
        );
    }

    /**
     * Sets up basic mock objects used in other Context and StoreManager mocks.
     */
    protected function setUpBasicMocks()
    {
        $this->scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getMock();

        $this->storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this->getMockBuilder(\Magento\Framework\Url::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();

        $this->localeResolverMock = $this->getMockBuilder(\Magento\Framework\Locale\Resolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLocale'])
            ->getMock();
    }

    /**
     * Sets up Context mock
     */
    protected function setUpContextMock()
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScopeConfig', 'getUrlBuilder'])
            ->getMock();

        $this->contextMock->expects(static::any())
            ->method('getScopeConfig')
            ->willReturn(
                $this->scopeConfigMock
            );

        $this->contextMock->expects(static::any())
            ->method('getUrlBuilder')
            ->willReturn(
                $this->urlBuilderMock
            );
    }

    /**
     * Sets up StoreManager mock.
     */
    protected function setUpStoreManagerMock()
    {
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getUrlBuilder'])
            ->getMock();

        $this->storeManagerMock->expects(static::any())
            ->method('getStore')
            ->willReturn(
                $this->storeMock
            );

        $this->storeManagerMock->expects(static::any())
            ->method('getUrlBuilder')
            ->willReturn(
                $this->urlBuilderMock
            );
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

        foreach ($data['urls'] as $index => $notificationUrlData) {
            $this->urlBuilderMock->expects(static::at($index))
                ->method('getUrl')
                ->with(
                    'emerchantpay/ipn',
                    [
                        '_store'  =>
                            $this->storeMock,
                        '_secure' =>
                            $notificationUrlData['secure'] === null
                                ? true
                                : $notificationUrlData['secure']
                    ]
                )
                ->willReturn(
                    "{$notificationUrlData['protocol']}://{$data['domainName']}/{$data['routePath']}/index/"
                );
        }

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

        $this->assertStringStartsWith("{$orderId}_", $transactionId);

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
            ->with(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS
            )
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
     * @covers EMer
     */
    public function testGetPaymentAdditionalInfoValue()
    {
        /**
         * @var $paymentMock \Magento\Payment\Model\InfoInterface|\PHPUnit_Framework_MockObject_MockObject
         */
        $paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getTransactionAdditionalInfo'
                ]
            )
            ->getMock();

        $paymentMock->expects(static::exactly(2))
            ->method('getTransactionAdditionalInfo')
            ->willReturn(
                [
                    \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => [
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

    public function testSetTokenByPaymentTransaction()
    {
        $declinedSaleTransactionMock = $this->getPaymentTransactionMock();

        $declinedSaleTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS
            )
            ->willReturn(
                [
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_STATUS           =>
                        GenesisTransactionStates::DECLINED,
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        GenesisTransactionTypes::SALE,
                ]
            );

        $this->moduleHelper->setTokenByPaymentTransaction($declinedSaleTransactionMock);

        $this->assertNull(
            \Genesis\Config::getToken()
        );

        $gatewayTerminalToken = 'gateway_token_098f6bcd4621d373cade4e832627b4f6';

        $approvedSaleTransactionMock = $this->getPaymentTransactionMock();

        $approvedSaleTransactionMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS
            )
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
            \Genesis\Config::getToken()
        );
    }

    /**
     * @covers EMerchantPayDataHelper::maskException()
     */
    public function testMaskException()
    {
        $exceptionMessage = 'Exception Message';

        $this->expectException(\Magento\Framework\Webapi\Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->moduleHelper->maskException(
            new \Genesis\Exceptions\ErrorAPI(
                $exceptionMessage,
                \Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR
            )
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getArrayFromGatewayResponse()
     */
    public function testGetArrayFromGatewayResponse()
    {
        $gatewayResponse = new \stdClass();
        $gatewayResponse->status = GenesisTransactionStates::APPROVED;
        $gatewayResponse->message = 'Gateway Response Message Text';
        $gatewayResponse->transaction_type = GenesisTransactionTypes::PAYBYVOUCHER_SALE;

        $arrObj = $this->moduleHelper->getArrayFromGatewayResponse($gatewayResponse);

        $this->assertTrue(
            is_array($arrObj)
        );

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
         * @var $orderConfigMock \Magento\Sales\Model\Order\Config|\PHPUnit_Framework_MockObject_MockObject
         */
        $orderConfigMock = $orderMock->getConfig();

        $orderMock->expects(static::once())
            ->method('save')
            ->willReturnSelf();

        $orderMock->expects(static::once())
            ->method('setState')
            ->willReturnSelf();

        $orderMock->expects(static::once())
            ->method('setStatus')
            ->willReturnSelf();

        $orderConfigMock->expects(static::once())
            ->method('getStateDefaultStatus')
            ->with(
                \Magento\Sales\Model\Order::STATE_PROCESSING
            )
            ->willReturn(
                \Magento\Sales\Model\Order::STATE_PROCESSING
            );

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
         * @var $orderConfigMock \Magento\Sales\Model\Order\Config|\PHPUnit_Framework_MockObject_MockObject
         */
        $orderConfigMock = $orderMock->getConfig();

        $orderMock->expects(static::exactly(2))
            ->method('save')
            ->willReturnSelf();

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
            ->with(
                \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
            )
            ->willReturn(
                \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
            );

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
         * @var $orderConfigMock \Magento\Sales\Model\Order\Config|\PHPUnit_Framework_MockObject_MockObject
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

        $orderMock->expects(static::exactly(2))
            ->method('save')
            ->willReturnSelf();

        $orderConfigMock->expects(static::exactly(2))
            ->method('getStateDefaultStatus');

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
         * @var $orderConfigMock \Magento\Sales\Model\Order\Config|\PHPUnit_Framework_MockObject_MockObject
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

        $orderMock->expects(static::exactly(2))
            ->method('save')
            ->willReturnSelf();

        $orderConfigMock->expects(static::exactly(2))
            ->method('getStateDefaultStatus');

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
                \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_ALLOW
            )
            ->willReturn('USD,EUR,GBP');

        $globalAllowedCurrencyCodes = $this->moduleHelper->getGlobalAllowedCurrencyCodes();

        $this->assertTrue(
            is_array($globalAllowedCurrencyCodes)
        );

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
            ->with(
                \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_ALLOW
            )
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

        $this->assertTrue(
            is_array($allowedCurrenciesOptions)
        );

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
                \Magento\Framework\Locale\Resolver::DEFAULT_LOCALE
            );

        $gatewayLocale = $this->moduleHelper->getLocale('de');

        $this->assertTrue(
            \Genesis\API\Constants\i18n::isValidLanguageCode($gatewayLocale)
        );

        $this->assertEquals(
            $gatewayLocale,
            substr(
                \Magento\Framework\Locale\Resolver::DEFAULT_LOCALE,
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
        $danishLocale = 'da_DK';
        $defaultLocale = 'en';

        $this->localeResolverMock->expects(static::once())
            ->method('getLocale')
            ->willReturn(
                $danishLocale
            );

        $gatewayLocale = $this->moduleHelper->getLocale($defaultLocale);

        $this->assertTrue(
            \Genesis\API\Constants\i18n::isValidLanguageCode($gatewayLocale)
        );

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
            ->with(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS
            )
            ->willReturn(
                [
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        \Genesis\API\Constants\Transaction\Types::CAPTURE
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
            ->with(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS
            )
            ->willReturn(
                [
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        \Genesis\API\Constants\Transaction\Types::PAYSAFECARD
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
            ->with(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS
            )
            ->willReturn(
                [
                    EMerchantPayDataHelper::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE =>
                        \Genesis\API\Constants\Transaction\Types::SALE
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
                \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D
            )
        );

        $this->assertFalse(
            $this->moduleHelper->getIsTransactionThreeDSecure(
                \Genesis\API\Constants\Transaction\Types::AUTHORIZE
            )
        );

        $this->assertTrue(
            $this->moduleHelper->getIsTransactionThreeDSecure(
                \Genesis\API\Constants\Transaction\Types::SALE_3D
            )
        );

        $this->assertFalse(
            $this->moduleHelper->getIsTransactionThreeDSecure(
                \Genesis\API\Constants\Transaction\Types::SALE
            )
        );
    }

    /**
     * @covers EMerchantPayDataHelper::getErrorMessageFromGatewayResponse()
     */
    public function testGetSuccessErrorMessageFromGatewayResponse()
    {
        $successfulGatewayResponseMessage = 'Transaction successful!';
        $successfulGatewayResponseTechMessage = 'Transaction has been processed successfully!';

        $validGatewayResponseWithMessage = $this->getSampleGatewayResponse(
            \Genesis\API\Constants\Transaction\States::APPROVED,
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE,
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
            \Genesis\API\Constants\Transaction\States::DECLINED,
            \Genesis\API\Constants\Transaction\Types::SALE
        );

        $gatewayResponseMessage = $this->moduleHelper->getErrorMessageFromGatewayResponse(
            $validGatewayResponseWithMessage
        );

        $this->assertEquals(
            EMerchantPayDataHelper::GENESIS_GATEWAY_ERROR_MESSAGE_DEFAULT,
            $gatewayResponseMessage
        );
    }
}
