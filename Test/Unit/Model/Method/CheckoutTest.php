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

use EMerchantPay\Genesis\Helper\Data;
use EMerchantPay\Genesis\Model\Method\Checkout as CheckoutPaymentMethod;
use Genesis\Api\Constants\Transaction\States;
use Genesis\Api\Constants\Transaction\Types as GenesisTransactionTypes;
use Genesis\Genesis;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order\Payment\Transaction;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test Checkout functionality
 *
 * Class CheckoutTest
 *
 * @covers CheckoutPaymentMethod
 */
class CheckoutTest extends AbstractMethodTest
{
    protected function getPaymentMethodClassName()
    {
        return CheckoutPaymentMethod::class;
    }

    /**
     * @covers CheckoutPaymentMethod::getConfigPaymentAction()
     */
    public function testGetConfigPaymentAction()
    {
        $this->assertEquals(
            MethodInterface::ACTION_ORDER,
            $this->getPaymentMethodInstance()->getConfigPaymentAction()
        );
    }

    /**
     * @covers CheckoutPaymentMethod::getCheckoutTransactionTypes()
     */
    public function testGetCheckoutTransactionTypes()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with("payment/{$this->getPaymentMethodCode()}/transaction_types", 'store', null)
            ->willReturn(
                implode(
                    ',',
                    [
                        GenesisTransactionTypes::EZEEWALLET,
                        GenesisTransactionTypes::IDEBIT_PAYIN,
                        GenesisTransactionTypes::INSTA_DEBIT_PAYIN,
                        GenesisTransactionTypes::P24,
                        GenesisTransactionTypes::TRUSTLY_SALE,
                        GenesisTransactionTypes::WECHAT,
                        GenesisTransactionTypes::ONLINE_BANKING_PAYIN,
                        GenesisTransactionTypes::SDD_SALE,
                        GenesisTransactionTypes::AUTHORIZE,
                        GenesisTransactionTypes::AUTHORIZE_3D,
                        GenesisTransactionTypes::SALE,
                        GenesisTransactionTypes::SALE_3D,
                        GenesisTransactionTypes::CASHU,
                        GenesisTransactionTypes::EZEEWALLET,
                        GenesisTransactionTypes::NETELLER,
                        GenesisTransactionTypes::POLI,
                        GenesisTransactionTypes::WEBMONEY,
                        GenesisTransactionTypes::PAYSAFECARD,
                        GenesisTransactionTypes::SOFORT,
                    ]
                )
            );

        $transactionTypes = $this->getPaymentMethodInstance()->getCheckoutTransactionTypes();

        $this->assertEquals(
            [
                GenesisTransactionTypes::AUTHORIZE,
                GenesisTransactionTypes::AUTHORIZE_3D,
                GenesisTransactionTypes::SALE,
                GenesisTransactionTypes::SALE_3D,
                GenesisTransactionTypes::CASHU,
                GenesisTransactionTypes::EZEEWALLET,
                GenesisTransactionTypes::EZEEWALLET,
                GenesisTransactionTypes::IDEBIT_PAYIN,
                GenesisTransactionTypes::INSTA_DEBIT_PAYIN,
                GenesisTransactionTypes::NETELLER,
                GenesisTransactionTypes::ONLINE_BANKING_PAYIN,
                GenesisTransactionTypes::P24,
                GenesisTransactionTypes::PAYSAFECARD,
                GenesisTransactionTypes::POLI,
                GenesisTransactionTypes::SDD_SALE,
                GenesisTransactionTypes::SOFORT,
                GenesisTransactionTypes::TRUSTLY_SALE,
                GenesisTransactionTypes::WEBMONEY,
                GenesisTransactionTypes::WECHAT,
            ],
            $transactionTypes
        );
    }

    /**
     * @covers CheckoutPaymentMethod::order()
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSuccessfulOrder()
    {
        $orderId = $this->getGeneratedOrderId();

        $this->scopeConfigMock->expects(static::exactly(4))
            ->method('getValue')
            ->will($this->returnCallback([$this, 'configCallback']));

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
                    '%s-%s',
                    $orderId,
                    sha1(uniqid())
                )
            );

        $orderMock->expects(self::never())
            ->method('getRemoteIp');

        $this->dataHelperMock->expects(self::once())
            ->method('buildOrderUsage')
            ->willReturn('Magento2 Payment Transaction');

        $this->dataHelperMock->expects(self::once())
            ->method('buildOrderDescriptionText')
            ->with($orderMock)
            ->willReturn('1 x Product Name');

        $this->dataHelperMock->expects(self::once())
            ->method('getLocale')
            ->willReturn('en');

        /**
         * @var $quote Quote|MockObject
         */
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerEmail'])
            ->getMock();

        $quote->expects(self::once())
            ->method('getCustomerEmail')
            ->willReturn(
                $this->getFakerObject()->email
            );

        $this->checkoutSessionMock->expects(self::any())
            ->method('getQuote')
            ->willReturn($quote);

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

        $this->dataHelperMock->expects(self::once())
            ->method('getNotificationUrl')
            ->with(
                $this->getPaymentMethodInstance()->getCode()
            )
            ->willReturn(
                static::SAMPLE_NOTIFICATION_URL
            );

        $this->dataHelperMock->expects(self::exactly(3))
            ->method('getReturnUrl')
            ->withConsecutive(
                [
                    $this->getPaymentMethodInstance()->getCode(),
                    Data::ACTION_RETURN_SUCCESS
                ],
                [
                    $this->getPaymentMethodInstance()->getCode(),
                    Data::ACTION_RETURN_CANCEL
                ],
                [
                    $this->getPaymentMethodInstance()->getCode(),
                    Data::ACTION_RETURN_FAILURE
                ]
            )
            ->willReturnOnConsecutiveCalls(
                static::SAMPLE_RETURN_SUCCESS_URL,
                static::SAMPLE_RETURN_CANCEL_URL,
                static::SAMPLE_RETURN_FAILURE_URL
            );

        $gatewayResponse = $this->getSampleGatewayResponse(
            States::NEW_STATUS,
            null,
            null,
            null,
            [
                'redirect_url' => static::SAMPLE_REDIRECT_URL
            ]
        );

        $this->dataHelperMock->expects(static::once())
            ->method('getGatewayResponseObject')
            ->willReturn(
                $gatewayResponse
            );

        $this->dataHelperMock->expects(self::once())
            ->method('executeGatewayRequest')
            ->with(
                $this->isInstanceOf(Genesis::class)
            )
            ->willReturnArgument(0);

        $this->paymentMock->expects(self::once())
            ->method('setTransactionId')
            ->with(
                $gatewayResponse->unique_id
            )
            ->willReturnSelf();

        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(false)
            ->willReturnSelf();

        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionPending')
            ->with(true)
            ->willReturnSelf();

        $this->paymentMock->expects(self::once())
            ->method('setTransactionAdditionalInfo')
            ->with(
                Transaction::RAW_DETAILS,
                $this->dataHelperMock->getArrayFromGatewayResponse(
                    $gatewayResponse
                )
            )
            ->willReturnSelf();

        $this->checkoutSessionMock->expects(self::once())
            ->method('setEmerchantPayCheckoutRedirectUrl')
            ->with(
                $gatewayResponse->redirect_url
            )
            ->willReturnSelf();

        $this->getPaymentMethodInstance()->order(
            $this->paymentMock,
            self::ORDER_AMOUNT
        );
    }

    /**
     * Scope Config Method Settings values
     *
     * @param ...$args
     *
     * @return string
     */
    public function configCallback(...$args)
    {
        if ($args[0] === "payment/{$this->getPaymentMethodCode()}/transaction_types") {
            return implode(
                ',',
                [
                    GenesisTransactionTypes::AUTHORIZE,
                    GenesisTransactionTypes::SOFORT,
                ]
            );
        }

        if ($args[0] === "payment/{$this->getPaymentMethodCode()}/sca_exemption") {
            return 'low_value';
        }

        if ($args[0] === "payment/{$this->getPaymentMethodCode()}/sca_exemption_code") {
            return '100';
        }

        return '';
    }
}
