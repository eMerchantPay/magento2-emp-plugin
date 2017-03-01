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

namespace EMerchantPay\Genesis\Model\Method;

/**
 * Checkout Payment Method Model Class
 * Class Checkout
 * @package EMerchantPay\Genesis\Model\Method
 */
class Checkout extends \Magento\Payment\Model\Method\AbstractMethod
{
    use \EMerchantPay\Genesis\Model\Traits\OnlinePaymentMethod;

    const CODE = 'emerchantpay_checkout';
    /**
     * Checkout Method Code
     */
    protected $_code = self::CODE;

    protected $_canOrder                    = true;
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canCancelInvoice            = true;
    protected $_canVoid                     = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canAuthorize                = true;
    protected $_isInitializeNeeded          = false;

    /**
     * Get Instance of the Magento Code Logger
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Checkout constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\App\Action\Context $actionContext
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \EMerchantPay\Genesis\Helper\Data $moduleHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Action\Context $actionContext,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger  $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \EMerchantPay\Genesis\Helper\Data $moduleHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_actionContext   = $actionContext;
        $this->_storeManager    = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_moduleHelper    = $moduleHelper;
        $this->_configHelper    =
            $this->getModuleHelper()->getMethodConfig(
                $this->getCode()
            );
    }

    /**
     * Get Default Payment Action On Payment Complete Action
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return \Magento\Payment\Model\Method\AbstractMethod::ACTION_ORDER;
    }

    /**
     * Get Available Checkout Transaction Types
     * @return array
     */
    public function getCheckoutTransactionTypes()
    {
        $processed_list = [];

        $selected_types = $this->getConfigHelper()->getTransactionTypes();

        $alias_map = [
            \Genesis\API\Constants\Payment\Methods::EPS         =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::GIRO_PAY    =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::PRZELEWY24  =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::QIWI        =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::SAFETY_PAY  =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::TELEINGRESO =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::TRUST_PAY   =>
                \Genesis\API\Constants\Transaction\Types::PPRO
        ];

        foreach ($selected_types as $selected_type) {
            if (!array_key_exists($selected_type, $alias_map)) {
                $processed_list[] = $selected_type;

                continue;
            }

            $transaction_type = $alias_map[$selected_type];

            $processed_list[$transaction_type]['name'] = $transaction_type;

            $processed_list[$transaction_type]['parameters'][] = [
                'payment_method' => $selected_type
            ];
        }

        return $processed_list;
    }

    /**
     * Create a Web-Payment Form Instance
     * @param array $data
     * @return \stdClass
     * @throws \Magento\Framework\Webapi\Exception
     */
    protected function checkout($data)
    {
        $genesis = $this->prepareGenesisWPFRequest($data);

        foreach ($this->getCheckoutTransactionTypes() as $type) {
            if (is_array($type)) {
                $genesis
                    ->request()
                        ->addTransactionType($type['name'], $type['parameters']);

                continue;
            }

            if (\Genesis\API\Constants\Transaction\Types::isPayByVoucher($type)) {
                $parameters = [
                    'card_type' =>
                        \Genesis\API\Constants\Transaction\Parameters\PayByVouchers\CardTypes::VIRTUAL,
                    'redeem_type' =>
                        \Genesis\API\Constants\Transaction\Parameters\PayByVouchers\RedeemTypes::INSTANT
                ];

                if ($type == \Genesis\API\Constants\Transaction\Types::PAYBYVOUCHER_YEEPAY) {
                    $parameters['product_name'] = $data['order']['description'];
                    $parameters['product_category'] = $data['order']['description'];
                }

                $genesis
                    ->request()
                        ->addTransactionType($type, $parameters);

                continue;
            }

            $genesis
                ->request()
                    ->addTransactionType($type);
        }

        $this->getModuleHelper()->executeGatewayRequest(
            $genesis
        );

        return $this->getModuleHelper()->getGatewayResponseObject(
            $genesis->response()
        );
    }

    /**
     * Prepares Genesis Request with basic request data
     *
     * @param array $data
     * @return \Genesis\Genesis
     */
    protected function prepareGenesisWPFRequest($data)
    {
        $genesis = new \Genesis\Genesis('WPF\Create');

        $genesis
            ->request()
                ->setTransactionId(
                    $data['transaction_id']
                )
                ->setCurrency(
                    $data['order']['currency']
                )
                ->setAmount(
                    $data['order']['amount']
                )
                ->setUsage(
                    $data['order']['usage']
                )
                ->setDescription(
                    $data['order']['description']
                )
                ->setCustomerPhone(
                    (string) $data['order']['billing']->getTelephone()
                )
                ->setCustomerEmail(
                    (string) $data['order']['customer']['email']
                )
                ->setLanguage(
                    $data['order']['language']
                );

        $genesis
            ->request()
                ->setNotificationUrl(
                    $data['urls']['notify']
                )
                ->setReturnSuccessUrl(
                    $data['urls']['return_success']
                )
                ->setReturnFailureUrl(
                    $data['urls']['return_failure']
                )
                ->setReturnCancelUrl(
                    $data['urls']['return_cancel']
                );

        $genesis
            ->request()
                ->setBillingFirstName(
                    (string) $data['order']['billing']->getFirstname()
                )
                ->setBillingLastName(
                    (string) $data['order']['billing']->getLastname()
                )
                ->setBillingAddress1(
                    (string) $data['order']['billing']->getStreetLine(1)
                )
                ->setBillingAddress2(
                    (string) $data['order']['billing']->getStreetLine(2)
                )
                ->setBillingZipCode(
                    (string) $data['order']['billing']->getPostcode()
                )
                ->setBillingCity(
                    (string) $data['order']['billing']->getCity()
                )
                ->setBillingState(
                    (string) $data['order']['billing']->getRegionCode()
                )
                ->setBillingCountry(
                    (string) $data['order']['billing']->getCountryId()
                );

        if (!empty($data['order']['shipping'])) {
            $genesis
                ->request()
                    ->setShippingFirstName(
                        (string) $data['order']['shipping']->getFirstname()
                    )
                    ->setShippingLastName(
                        (string) $data['order']['shipping']->getLastname()
                    )
                    ->setShippingAddress1(
                        (string) $data['order']['shipping']->getStreetLine(1)
                    )
                    ->setShippingAddress2(
                        (string) $data['order']['shipping']->getStreetLine(2)
                    )
                    ->setShippingZipCode(
                        (string) $data['order']['shipping']->getPostcode()
                    )
                    ->setShippingCity(
                        (string) $data['order']['shipping']->getCity()
                    )
                    ->setShippingState(
                        (string) $data['order']['shipping']->getRegionCode()
                    )
                    ->setShippingCountry(
                        (string) $data['order']['shipping']->getCountryId()
                    );
        }

        return $genesis;
    }

    /**
     * Order Payment
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();

        $orderId = ltrim(
            $order->getIncrementId(),
            '0'
        );

        // @codingStandardsIgnoreStart
        $data = [
            'transaction_id' =>
                $this->getModuleHelper()->genTransactionId(
                    $orderId
                ),
            'transaction_types' =>
                $this->getConfigHelper()->getTransactionTypes(),
            'order' => [
                'currency' => $order->getBaseCurrencyCode(),
                'language' => $this->getModuleHelper()->getLocale(),
                'amount' => $amount,
                'usage' => $this->getModuleHelper()->buildOrderUsage(),
                'description' => $this->getModuleHelper()->buildOrderDescriptionText(
                    $order
                ),
                'customer' => [
                    'email' => $this->getCheckoutSession()->getQuote()->getCustomerEmail(),
                ],
                'billing' =>
                    $order->getBillingAddress(),
                'shipping' =>
                    $order->getShippingAddress()
            ],
            'urls' => [
                'notify' =>
                    $this->getModuleHelper()->getNotificationUrl(
                        $this->getCode()
                    ),
                'return_success' =>
                    $this->getModuleHelper()->getReturnUrl(
                        $this->getCode(),
                        \EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_SUCCESS
                    ),
                'return_cancel'  =>
                    $this->getModuleHelper()->getReturnUrl(
                        $this->getCode(),
                        \EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_CANCEL
                    ),
                'return_failure' =>
                    $this->getModuleHelper()->getReturnUrl(
                        $this->getCode(),
                        \EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_FAILURE
                    ),
            ]
        ];
        // @codingStandardsIgnoreEnd

        $this->getConfigHelper()->initGatewayClient();

        try {
            $responseObject = $this->checkout($data);

            $isWpfSuccessful =
                ($responseObject->status == \Genesis\API\Constants\Transaction\States::NEW_STATUS) &&
                isset($responseObject->redirect_url);

            if (!$isWpfSuccessful) {
                $errorMessage = $this->getModuleHelper()->getErrorMessageFromGatewayResponse(
                    $responseObject
                );

                $this->getCheckoutSession()->setEmerchantPayLastCheckoutError(
                    $errorMessage
                );

                $this->getModuleHelper()->throwWebApiException($errorMessage);
            }

            $payment->setTransactionId($responseObject->unique_id);
            $payment->setIsTransactionPending(true);
            $payment->setIsTransactionClosed(false);

            $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
                $payment,
                $responseObject
            );

            $this->setRedirectUrl(
                $responseObject->redirect_url
            );

            return $this;
        } catch (\Exception $e) {
            $this->getLogger()->error(
                $e->getMessage()
            );

            $this->getCheckoutSession()->setEmerchantPayLastCheckoutError(
                $e->getMessage()
            );

            $this->getModuleHelper()->maskException($e);
        }
    }

    /**
     * Payment Capturing
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->getLogger()->debug('Capture transaction for order #' . $order->getIncrementId());

        $authTransaction = $this->getModuleHelper()->lookUpAuthorizationTransaction(
            $payment
        );

        if (!isset($authTransaction)) {
            $errorMessage = 'Capture transaction for order #' .
                $order->getIncrementId() .
                ' cannot be finished (No Authorize Transaction exists)';

            $this->getLogger()->error(
                $errorMessage
            );

            $this->getModuleHelper()->throwWebApiException(
                $errorMessage
            );
        }

        try {
            $this->doCapture($payment, $amount, $authTransaction);
        } catch (\Exception $e) {
            $this->getLogger()->error(
                $e->getMessage()
            );
            $this->getModuleHelper()->maskException($e);
        }

        return $this;
    }

    /**
     * Determines method's availability based on config data and quote amount
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote) &&
            $this->getConfigHelper()->isMethodAvailable();
    }

    /**
     * Checks base currency against the allowed currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return $this->getModuleHelper()->isCurrencyAllowed(
            $this->getCode(),
            $currencyCode
        );
    }
}
