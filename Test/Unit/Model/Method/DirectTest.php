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

use EMerchantPay\Genesis\Model\Method\Direct as DirectPaymentMethod;
use Magento\Framework\DataObject as MagentoDataObject;
use Magento\Quote\Api\Data\PaymentInterface as MagentoPaymentInterface;
use Magento\Sales\Model\Order;
use Genesis\API\Constants\Transaction\Types as GenesisTransactionTypes;
use Magento\Payment\Model\Method\AbstractMethod as AbstractPaymentMethod;

/**
 * Class DirectTest
 * @covers \EMerchantPay\Genesis\Model\Method\Direct
 * @package EMerchantPay\Genesis\Test\Unit\Model\Method
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DirectTest extends \EMerchantPay\Genesis\Test\Unit\Model\Method\AbstractMethodTest
{
    /**
     * @return string
     */
    protected function getPaymentMethodClassName()
    {
        return DirectPaymentMethod::class;
    }

    /**
     * @covers DirectPaymentMethod::getConfigData()
     */
    public function testGetPaymentAction()
    {
        $this->scopeConfigMock->expects($this->at(0))
            ->method('getValue')
            ->with("payment/{$this->getPaymentMethodCode()}/transaction_type", 'store', null)
            ->willReturn(
                GenesisTransactionTypes::AUTHORIZE_3D
            );

        $this->scopeConfigMock->expects($this->at(1))
            ->method('getValue')
            ->with("payment/{$this->getPaymentMethodCode()}/transaction_type", 'store', null)
            ->willReturn(
                GenesisTransactionTypes::SALE_3D
            );

        $this->assertEquals(
            AbstractPaymentMethod::ACTION_AUTHORIZE,
            $this->getPaymentMethodInstance()->getConfigPaymentAction()
        );

        $this->assertEquals(
            AbstractPaymentMethod::ACTION_AUTHORIZE_CAPTURE,
            $this->getPaymentMethodInstance()->getConfigPaymentAction()
        );
    }

    /**
     * @covers DirectPaymentMethod::isThreeDEnabled()
     */
    public function testIsThreeDEnabled()
    {
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with("payment/{$this->getPaymentMethodCode()}/transaction_type", 'store', null)
            ->willReturn(
                GenesisTransactionTypes::SALE_3D
            );

        $this->assertTrue(
            $this->getPaymentMethodInstance()->isThreeDEnabled()
        );
    }

    /**
     * @covers DirectMethodModel::isThreeDEnabled()
     */
    public function testIsThreeDDisabled()
    {
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with("payment/{$this->getPaymentMethodCode()}/transaction_type", 'store', null)
            ->willReturn(
                GenesisTransactionTypes::SALE
            );

        $this->assertFalse(
            $this->getPaymentMethodInstance()->isThreeDEnabled()
        );
    }

    /**
     * @covers DirectMethodModel::authorize()
     * @return void
     */
    public function testSuccessfulAuthorize()
    {
        $this->prepareSuccessTransactionExpectations(
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE
        );

        $this->getPaymentMethodInstance()->authorize(
            $this->paymentMock,
            static::ORDER_AMOUNT
        );
    }

    /**
     * @covers DirectPaymentMethod::authorize()
     */
    public function testFailedAuthorize()
    {
        $this->dataHelperMock->expects(self::never())
            ->method('lookUpAuthorizationTransaction')
            ->withAnyParameters();

        $this->prepareFailedTransactionExpectations(
            GenesisTransactionTypes::AUTHORIZE
        );

        $this->getPaymentMethodInstance()->authorize(
            $this->paymentMock,
            static::ORDER_AMOUNT
        );
    }

    /**
     * @covers DirectMethodModel::authorize()
     * @return void
     */
    public function testSuccessfulAuthorizeThreeD()
    {
        $this->prepareSuccessTransactionExpectations(
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D
        );

        $this->getPaymentMethodInstance()->authorize(
            $this->paymentMock,
            static::ORDER_AMOUNT
        );
    }

    /**
     * @covers DirectPaymentMethod::authorize()
     */
    public function testFailedAuthorizeThreeD()
    {
        $this->dataHelperMock->expects(self::never())
            ->method('lookUpAuthorizationTransaction')
            ->withAnyParameters();

        $this->prepareFailedTransactionExpectations(
            GenesisTransactionTypes::AUTHORIZE_3D
        );

        $this->getPaymentMethodInstance()->authorize(
            $this->paymentMock,
            static::ORDER_AMOUNT
        );
    }

    /**
     * @covers DirectMethodModel::capture()
     * @return void
     */
    public function testSuccessfulSale()
    {
        $this->dataHelperMock->expects(self::once())
            ->method('lookUpAuthorizationTransaction')
            ->with($this->paymentMock)
            ->willReturn(null);

        $this->prepareSuccessTransactionExpectations(
            \Genesis\API\Constants\Transaction\Types::SALE
        );

        $this->getPaymentMethodInstance()->capture(
            $this->paymentMock,
            static::ORDER_AMOUNT
        );
    }

    /**
     * @covers DirectPaymentMethod::capture()
     */
    public function testFailedSale()
    {
        $this->dataHelperMock->expects(self::once())
            ->method('lookUpAuthorizationTransaction')
            ->with($this->paymentMock)
            ->willReturn(null);

        $this->prepareFailedTransactionExpectations(
            GenesisTransactionTypes::SALE
        );

        $this->getPaymentMethodInstance()->capture(
            $this->paymentMock,
            static::ORDER_AMOUNT
        );
    }

    /**
     * @covers DirectMethodModel::capture()
     * @return void
     */
    public function testSuccessfulSaleThreeD()
    {
        $this->dataHelperMock->expects(self::once())
            ->method('lookUpAuthorizationTransaction')
            ->with($this->paymentMock)
            ->willReturn(null);

        $this->prepareSuccessTransactionExpectations(
            \Genesis\API\Constants\Transaction\Types::SALE_3D
        );

        $this->getPaymentMethodInstance()->capture(
            $this->paymentMock,
            static::ORDER_AMOUNT
        );
    }

    /**
     * @covers DirectPaymentMethod::capture()
     */
    public function testFailedSaleThreeD()
    {
        $this->dataHelperMock->expects(self::once())
            ->method('lookUpAuthorizationTransaction')
            ->with($this->paymentMock)
            ->willReturn(null);

        $this->prepareFailedTransactionExpectations(
            GenesisTransactionTypes::SALE_3D
        );

        $this->getPaymentMethodInstance()->capture(
            $this->paymentMock,
            static::ORDER_AMOUNT
        );
    }

    /**
     * @param string $transactionType
     * @return void
     */
    protected function prepareFailedTransactionExpectations($transactionType)
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with("payment/{$this->getPaymentMethodCode()}/transaction_type", 'store', null)
            ->willReturn(
                $transactionType
            );

        $orderMock = $this->getOrderMock();

        $this->paymentMock->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $orderMock->expects(static::once())
            ->method('getBillingAddress')
            ->willReturn(
                $this->getOrderAddressMock()
            );

        $orderMock->expects(static::once())
            ->method('getShippingAddress')
            ->willReturn(
                $this->getOrderAddressMock()
            );

        $exceptionMessage = 'Please verify the supplied parameters to the request!';

        $this->dataHelperMock->expects(self::once())
            ->method('maskException')
            ->with(
                $this->callback(function (\Exception $exception) {
                    if ($exception instanceof \Genesis\Exceptions\ErrorParameter) {
                        return true;
                    }

                    return false;
                })
            )->willThrowException(
                $this->dataHelperMock->createWebApiException(
                    $exceptionMessage
                )
            );

        $this->dataHelperMock->expects(static::never())
            ->method('getGatewayResponseObject');

        $this->paymentMock->expects(self::never())
            ->method('setTransactionId')
            ->withAnyParameters();

        $this->paymentMock->expects(static::never())
            ->method('setIsTransactionClosed')
            ->withAnyParameters();

        $this->paymentMock->expects(static::never())
            ->method('setIsTransactionPending')
            ->withAnyParameters();

        $this->paymentMock->expects(self::never())
            ->method('setTransactionAdditionalInfo')
            ->withAnyParameters();

        $this->checkoutSessionMock->expects(self::never())
            ->method('setEmerchantPayCheckoutRedirectUrl')
            ->withAnyParameters();

        $this->expectException(\Magento\Framework\Webapi\Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
    }

    /**
     * @param string $transactionType
     * @return void
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function prepareSuccessTransactionExpectations($transactionType)
    {
        $orderId = $this->getGeneratedOrderId();

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with("payment/{$this->getPaymentMethodCode()}/transaction_type", 'store', null)
            ->willReturn(
                $transactionType
            );

        $isThreeDSecureTransaction = $this->getPaymentMethodInstance()->isThreeDEnabled();

        $orderMock = $this->getOrderMock();

        $this->paymentMock->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $orderMock->expects(self::once())
            ->method('getIncrementId')
            ->willReturn($orderId);

        $this->dataHelperMock->expects(self::once())
            ->method('genTransactionId')
            ->with($orderId)
            ->willReturn(
                sprintf(
                    '%s_%s',
                    $orderId,
                    sha1(uniqid())
                )
            );

        $orderMock->expects(self::once())
            ->method('getRemoteIp')
            ->willReturn(
                $this->getFakerObject()->ipv4
            );

        $this->dataHelperMock->expects(self::once())
            ->method('buildOrderDescriptionText')
            ->with($orderMock)
            ->willReturn('1 x Product Name');

        $this->dataHelperMock->expects(self::never())
            ->method('getLocale')
            ->willReturn('en');

        $orderMock->expects(static::once())
            ->method('getBaseCurrencyCode')
            ->willReturn('USD');

        $this->paymentMock->expects(self::once())
            ->method('getCcNumber')
            ->willReturn(
                static::CREDIT_CARD_VISA
            );

        $this->paymentMock->expects(self::once())
            ->method('getCcOwner')
            ->willReturn(null);

        $this->paymentMock->expects(self::once())
            ->method('getCcExpMonth')
            ->willReturn(12);

        $this->paymentMock->expects(self::once())
            ->method('getCcExpYear')
            ->willReturn(
                date('Y') + 1
            );

        $orderMock->expects(static::once())
            ->method('getBillingAddress')
            ->willReturn(
                $this->getOrderAddressMock()
            );

        $orderMock->expects(static::once())
            ->method('getShippingAddress')
            ->willReturn(
                $this->getOrderAddressMock()
            );

        if ($isThreeDSecureTransaction) {
            $this->dataHelperMock->expects(self::once())
                ->method('getNotificationUrl')
                ->with(
                    $this->getPaymentMethodInstance()->getCode()
                )
                ->willReturn(
                    static::SAMPLE_NOTIFICATION_URL
                );

            $this->dataHelperMock->expects(self::exactly(2))
                ->method('getReturnUrl')
                ->withConsecutive(
                    [
                        $this->getPaymentMethodInstance()->getCode(),
                        \EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_SUCCESS
                    ],
                    [
                        $this->getPaymentMethodInstance()->getCode(),
                        \EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_FAILURE
                    ]
                )
                ->willReturnOnConsecutiveCalls(
                    static::SAMPLE_RETURN_SUCCESS_URL,
                    static::SAMPLE_RETURN_FAILURE_URL
                );
        }

        $this->dataHelperMock->expects(self::once())
            ->method('maskException')
            ->with(
                $this->isInstanceOf(\Genesis\Exceptions\ErrorNetwork::class)
            )
            ->willReturnSelf();

        if ($isThreeDSecureTransaction) {
            $gatewayResponse = $this->getSampleGatewayResponse(
                \Genesis\API\Constants\Transaction\States::PENDING_ASYNC,
                $this->getPaymentMethodInstance()->getConfigTransactionType(),
                null,
                null,
                [
                    'redirect_url' => static::SAMPLE_REDIRECT_URL
                ]
            );
        } else {
            $gatewayResponse = $this->getSampleGatewayResponse(
                \Genesis\API\Constants\Transaction\States::APPROVED,
                $this->getPaymentMethodInstance()->getConfigTransactionType(),
                'Transaction Successful!',
                'Transaction Successful!'
            );
        }

        $this->dataHelperMock->expects(static::once())
            ->method('getGatewayResponseObject')
            ->willReturn(
                $gatewayResponse
            );

        $this->paymentMock->expects(self::once())
            ->method('setTransactionId')
            ->with($gatewayResponse->unique_id)
            ->willReturnSelf();

        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(false)
            ->willReturnSelf();

        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionPending')
            ->with($isThreeDSecureTransaction)
            ->willReturnSelf();

        $this->paymentMock->expects(self::once())
            ->method('setTransactionAdditionalInfo')
            ->with(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $this->dataHelperMock->getArrayFromGatewayResponse(
                    $gatewayResponse
                )
            )
            ->willReturnSelf();

        $this->checkoutSessionMock->expects(self::once())
            ->method('setEmerchantPayCheckoutRedirectUrl')
            ->with(
                $isThreeDSecureTransaction
                    ? $gatewayResponse->redirect_url
                    : null
            )
            ->willReturnSelf();
    }

    /**
     * @covers DirectPaymentMethod::assignData()
     */
    public function testSkipAssignCCData()
    {
        $faker = $this->getFakerObject();
        $expMonth = 12;
        $expYear = date('Y');

        $this->paymentMock->expects(self::once())
            ->method('getCcNumber')
            ->willReturn(
                static::CREDIT_CARD_VISA
            );

        $this->paymentMock->expects(self::once())
            ->method('getCcCid')
            ->willReturn(
                $faker->randomNumber(3)
            );

        $this->paymentMock->expects(self::once())
            ->method('getCcExpMonth')
            ->willReturn(
                $expMonth
            );

        $this->paymentMock->expects(self::once())
            ->method('getCcExpYear')
            ->willReturn(
                $expYear
            );

        /**
         * Should be called only once (on older 2.0.x,
         * CC-Details are not assigned to payment instance,
         * so we need to do it manually
         */
        $this->paymentMock->expects(self::once())
            ->method('addData')
            ->with(
                $this->callback(function ($additionalData) {
                    return
                        is_array($additionalData) &&
                        $this->getArrayHasKeys(
                            ['cc_number', 'cc_cid', 'cc_exp_month', 'cc_exp_year'],
                            $additionalData
                        );
                })
            );

        $data = new MagentoDataObject(
            [
                MagentoPaymentInterface::KEY_ADDITIONAL_DATA => [
                    'cc_number'         => static::CREDIT_CARD_VISA,
                    'cc_cid'            => $faker->randomNumber(3),
                    'cc_exp_month'      => $expMonth,
                    'cc_exp_year'       => $expYear,
                ]
            ]
        );

        $this->getPaymentMethodInstance()->assignData($data);
    }

    /**
     * @covers DirectPaymentMethod::setRedirectUrl()
     */
    public function testSetValidRedirectUrl()
    {
        $redirectUrl = static::SAMPLE_REDIRECT_URL;

        $this->checkoutSessionMock->expects(self::once())
            ->method('setEmerchantPayCheckoutRedirectUrl')
            ->with($redirectUrl)
            ->willReturnSelf();

        $this->getPaymentMethodInstance()->setRedirectUrl($redirectUrl);
    }

    /**
     * @covers DirectPaymentMethod::setRedirectUrl()
     */
    public function testSetEmptyOrInvalidRedirectUrl()
    {
        $redirectUrl = 'invalid-redirect/url';

        $this->checkoutSessionMock->expects(self::never())
            ->method('setEmerchantPayCheckoutRedirectUrl')
            ->with($redirectUrl);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid 3D-Secure redirect URL');

        $this->getPaymentMethodInstance()->setRedirectUrl($redirectUrl);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Empty 3D-Secure redirect URL');

        $this->getPaymentMethodInstance()->setRedirectUrl(null);
    }

    /**
     * @covers DirectPaymentMethod::unsetRedirectUrl()
     */
    public function testUnsetRedirectUrl()
    {
        $this->checkoutSessionMock->expects(self::once())
            ->method('setEmerchantPayCheckoutRedirectUrl')
            ->with(null);

        $this->getPaymentMethodInstance()->unsetRedirectUrl();
    }
}
