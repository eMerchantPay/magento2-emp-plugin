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
     * @param \Magento\Framework\UrlInterface $urlBuilder
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
        \Magento\Framework\UrlInterface $urlBuilder,
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
        $this->_actionContext = $actionContext;
        $this->_storeManager = $storeManager;
        $this->_urlBuilder = $urlBuilder;
        $this->_checkoutSession = $checkoutSession;
        $this->_moduleHelper = $moduleHelper;
        $this->_configHelper =
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
        $processed_list = array();

        $selected_types = $this->getConfigHelper()->getTransactionTypes();

        $alias_map = array(
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
        );

        foreach ($selected_types as $selected_type) {
            if (array_key_exists($selected_type, $alias_map)) {
                $transaction_type = $alias_map[$selected_type];

                $processed_list[$transaction_type]['name'] = $transaction_type;

                $processed_list[$transaction_type]['parameters'][] = array(
                    'payment_method' => $selected_type
                );
            } else {
                $processed_list[] = $selected_type;
            }
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
        $genesis = new \Genesis\Genesis('WPF\Create');
        $genesis
            ->request()
                ->setTransactionId($data['transaction_id'])
                ->setCurrency($data['order']['currency'])
                ->setAmount($data['order']['amount'])
                ->setUsage($data['order']['usage'])
                ->setDescription($data['order']['description'])
                ->setCustomerPhone(strval($data['order']['billing']->getTelephone()))
                ->setCustomerEmail(strval($data['order']['customer']['email']))
                ->setNotificationUrl($data['urls']['notify'])
                ->setReturnSuccessUrl($data['urls']['return_success'])
                ->setReturnFailureUrl($data['urls']['return_failure'])
                ->setReturnCancelUrl($data['urls']['return_cancel'])
                ->setBillingFirstName(strval($data['order']['billing']->getFirstname()))
                ->setBillingLastName(strval($data['order']['billing']->getLastname()))
                ->setBillingAddress1(strval($data['order']['billing']->getStreetLine(1)))
                ->setBillingAddress2(strval($data['order']['billing']->getStreetLine(2)))
                ->setBillingZipCode(strval($data['order']['billing']->getPostcode()))
                ->setBillingCity(strval($data['order']['billing']->getCity()))
                ->setBillingState(strval($data['order']['billing']->getRegion()))
                ->setBillingCountry(strval($data['order']['billing']->getCountryId()))
                ->setShippingFirstName(strval($data['order']['shipping']->getFirstname()))
                ->setShippingLastName(strval($data['order']['shipping']->getLastname()))
                ->setShippingAddress1(strval($data['order']['shipping']->getStreetLine(1)))
                ->setShippingAddress2(strval($data['order']['shipping']->getStreetLine(2)))
                ->setShippingZipCode(strval($data['order']['shipping']->getPostcode()))
                ->setShippingCity(strval($data['order']['shipping']->getCity()))
                ->setShippingState(strval($data['order']['shipping']->getRegion()))
                ->setShippingCountry(strval($data['order']['shipping']->getCountryId()))
                ->setLanguage($data['order']['language']);

        foreach ($this->getCheckoutTransactionTypes() as $type) {
            if (is_array($type)) {
                $genesis
                    ->request()
                        ->addTransactionType($type['name'], $type['parameters']);
            } else {
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
                } else {
                    $genesis
                        ->request()
                            ->addTransactionType($type);
                }
            }
        }

        $genesis->execute();

        return $genesis->response()->getResponseObject();
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

        $data = [
            'transaction_id' =>
                $this->getModuleHelper()->genTransactionId(
                    $orderId
                ),
            'transaction_types' =>
                $this->getConfigHelper()->getTransactionTypes(),
            'order' => [
                'currency' => $order->getOrderCurrencyCode(),
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
                        "success"
                    ),
                'return_cancel'  =>
                    $this->getModuleHelper()->getReturnUrl(
                        $this->getCode(),
                        "cancel"
                    ),
                'return_failure' =>
                    $this->getModuleHelper()->getReturnUrl(
                        $this->getCode(),
                        "failure"
                    ),
            ]
        ];

        $this->getConfigHelper()->initGatewayClient();

        try {
            $responseObject = $this->checkout($data);

            $payment->setTransactionId($responseObject->unique_id);
            $payment->setIsTransactionPending(true);
            $payment->setIsTransactionClosed(false);

            $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
                $payment,
                $responseObject
            );

            $this->getCheckoutSession()->setEmerchantPayCheckoutRedirectUrl(
                $responseObject->redirect_url
            );

            return $this;
        } catch (\Exception $e) {
            $this->getLogger()->error(
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
            $this->getModuleHelper()->setTokenByPaymentTransaction(
                $authTransaction
            );

            $data = array(
                'transaction_id' =>
                    $this->getModuleHelper()->genTransactionId(),
                'remote_ip'      =>
                    $order->getRemoteIp(),
                'reference_id'   =>
                    $authTransaction->getTxnId(),
                'currency'       =>
                    $order->getBaseCurrencyCode(),
                'amount'         =>
                    $amount
            );

            $responseObject = $this->processReferenceTransaction(
                \Genesis\API\Constants\Transaction\Types::CAPTURE,
                $payment,
                $data
            );

            if ($responseObject->status == \Genesis\API\Constants\Transaction\States::APPROVED) {
                $this->getMessageManager()->addSuccess($responseObject->message);
            } else {
                $this->getModuleHelper()->throwWebApiException(
                    $responseObject->message
                );
            }

            unset($data);
        } catch (\Exception $e) {
            $this->getLogger()->error(
                $e->getMessage()
            );
            $this->getModuleHelper()->maskException($e);
        }

        return $this;
    }

    /**
     * Payment refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->getLogger()->debug('Refund transaction for order #' . $order->getIncrementId());

        $captureTransaction = $this->getModuleHelper()->lookUpCaptureTransaction(
            $payment
        );

        if (!isset($captureTransaction)) {
            $errorMessage = 'Refund transaction for order #' .
                $order->getIncrementId() .
                ' cannot be finished (No Capture Transaction exists)';

            $this->getLogger()->error(
                $errorMessage
            );

            $this->getMessageManager()->addError($errorMessage);

            $this->getModuleHelper()->throwWebApiException(
                $errorMessage
            );
        }

        if (!$this->getModuleHelper()->canRefundTransaction($captureTransaction)) {
            $errorMessage = sprintf(
                "Order with transaction type \"%s\" cannot be refunded online." . PHP_EOL .
                "For further Information please contact your Account Manager." . PHP_EOL .
                "For more complex workflows/functionallity, please visit our Merchant Portal!",
                $this->getModuleHelper()->getTransactionTypeByTransaction(
                    $captureTransaction
                )
            );

            $this->getMessageManager()->addError($errorMessage);
            $this->getModuleHelper()->throwWebApiException($errorMessage);
        }

        if (!$this->getModuleHelper()->setTokenByPaymentTransaction($captureTransaction)) {
            $authTransaction = $this->getModuleHelper()->lookUpAuthorizationTransaction(
                $payment
            );

            $this->getModuleHelper()->setTokenByPaymentTransaction(
                $authTransaction
            );
        }

        try {
            $data = array(
                'transaction_id' =>
                    $this->getModuleHelper()->genTransactionId(),
                'remote_ip'      =>
                    $order->getRemoteIp(),
                'reference_id'   =>
                    $captureTransaction->getTxnId(),
                'currency'       =>
                    $order->getBaseCurrencyCode(),
                'amount'         =>
                    $amount
            );

            $responseObject = $this->processReferenceTransaction(
                \Genesis\API\Constants\Transaction\Types::REFUND,
                $payment,
                $data
            );

            if ($responseObject->status == \Genesis\API\Constants\Transaction\States::APPROVED) {
                $this->getMessageManager()->addSuccess($responseObject->message);
            } else {
                $this->getMessageManager()->addError($responseObject->message);
                $this->getModuleHelper()->throwWebApiException(
                    $responseObject->message
                );
            }

            unset($data);
        } catch (\Exception $e) {
            $this->getLogger()->error(
                $e->getMessage()
            );

            $this->getMessageManager()->addError(
                $e->getMessage()
            );

            $this->getModuleHelper()->maskException($e);
        }

        return $this;
    }

    /**
     * Payment Cancel
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->void($payment);

        return $this;
    }

    /**
     * Void Payment
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order $order */

        $order = $payment->getOrder();

        $this->getLogger()->debug('Void transaction for order #' . $order->getIncrementId());

        $referenceTransaction = $this->getModuleHelper()->lookUpVoidReferenceTransaction(
            $payment
        );

        if ($referenceTransaction->getTxnType() == \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH) {
            $authTransaction = $referenceTransaction;
        } else {
            $authTransaction = $this->getModuleHelper()->lookUpAuthorizationTransaction(
                $payment
            );
        }

        if (!isset($authTransaction) || !isset($referenceTransaction)) {
            $errorMessage = 'Void transaction for order #' .
                            $order->getIncrementId() .
                            ' cannot be finished (No Authorize / Capture Transaction exists)';

            $this->getLogger()->error($errorMessage);
            $this->getModuleHelper()->throwWebApiException($errorMessage);
        }

        try {
            $this->getModuleHelper()->setTokenByPaymentTransaction(
                $authTransaction
            );

            $data = array(
                'transaction_id' =>
                    $this->getModuleHelper()->genTransactionId(),
                'remote_ip'      =>
                    $order->getRemoteIp(),
                'reference_id'   =>
                    $referenceTransaction->getTxnId()
            );

            $responseObject = $this->processReferenceTransaction(
                \Genesis\API\Constants\Transaction\Types::VOID,
                $payment,
                $data
            );

            if ($responseObject->status == \Genesis\API\Constants\Transaction\States::APPROVED) {
                $this->getMessageManager()->addSuccess($responseObject->message);
            } else {
                $this->getModuleHelper()->throwWebApiException(
                    $responseObject->message
                );
            }

            unset($data);
        } catch (\Exception $e) {
            $this->getLogger()->error(
                $e->getMessage()
            );
            $this->getModuleHelper()->maskException($e);
        }

        return $this;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return
            parent::isAvailable($quote) &&
            $this->getConfigHelper()->isMethodAvailable() &&
            $this->getModuleHelper()->isQuoteCurrencyAllowed(
                $this->getCode(),
                $quote->getQuoteCurrencyCode()
            );
    }

    /**
     * Get if Method is available for currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        /**
         * The Currency Restriction is implemented in
         * method - isAvailable
         * Reason: The Currency Code passed to canUseForCurrency is
         *         not the checkout Currency Code and it will not work,
         *         when a user changes the Quote Currency
         */
        return true;
    }
}
