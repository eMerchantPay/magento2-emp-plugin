<?php
/*
 * Copyright (C) 2018-2024 emerchantpay Ltd.
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
 * @copyright   2018-2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Helper;

use EMerchantPay\Genesis\Block\Frontend\Config;
use EMerchantPay\Genesis\Model\ConfigFactory;
use Exception;
use Genesis\Api\Constants\Transaction\Parameters\Mobile\ApplePay\PaymentTypes as ApplePaymentTypes;
use Genesis\Api\Constants\Transaction\Parameters\Mobile\GooglePay\PaymentTypes as GooglePaymentTypes;
use Genesis\Api\Constants\Transaction\Parameters\Wallets\PayPal\PaymentTypes as PayPalPaymentTypes;
use Genesis\Genesis;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Store\Model\StoreManagerInterface;
use \Genesis\Api\Constants\Transaction\Types as GenesisTransactionTypes;

/**
 * Helper Class for all Payment Methods
 *
 * Class Data
 * @package EMerchantPay\Genesis\Helper
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Data extends AbstractHelper
{
    const SECURE_TRANSACTION_TYPE_SUFFIX = '3D';

    const ADDITIONAL_INFO_KEY_STATUS           = 'status';
    const ADDITIONAL_INFO_KEY_TRANSACTION_TYPE = 'transaction_type';
    const ADDITIONAL_INFO_KEY_TERMINAL_TOKEN   = 'terminal_token';
    const ADDITIONAL_INFO_KEY_REDIRECT_URL     = 'redirect_url';

    const ACTION_RETURN_SUCCESS = 'success';
    const ACTION_RETURN_CANCEL  = 'cancel';
    const ACTION_RETURN_FAILURE = 'failure';

    const GENESIS_GATEWAY_ERROR_MESSAGE_DEFAULT = 'An error has occurred while processing your request to the gateway';

    const PPRO_TRANSACTION_SUFFIX = '_ppro';

    const GOOGLE_PAY_TRANSACTION_PREFIX     = GenesisTransactionTypes::GOOGLE_PAY . '_';
    const GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE = GooglePaymentTypes::AUTHORIZE;
    const GOOGLE_PAY_PAYMENT_TYPE_SALE      = GooglePaymentTypes::SALE;

    const PAYPAL_TRANSACTION_PREFIX         = GenesisTransactionTypes::PAY_PAL . '_';
    const PAYPAL_PAYMENT_TYPE_AUTHORIZE     = PayPalPaymentTypes::AUTHORIZE;
    const PAYPAL_PAYMENT_TYPE_SALE          = PayPalPaymentTypes::SALE;
    const PAYPAL_PAYMENT_TYPE_EXPRESS       = PayPalPaymentTypes::EXPRESS;
    
    const APPLE_PAY_TRANSACTION_PREFIX      = GenesisTransactionTypes::APPLE_PAY . '_';
    const APPLE_PAY_PAYMENT_TYPE_AUTHORIZE  = ApplePaymentTypes::AUTHORIZE;
    const APPLE_PAY_PAYMENT_TYPE_SALE       = ApplePaymentTypes::SALE;

    const PLATFORM_TRANSACTION_SUFFIX = '-mg2';

    private const INCOMING_CONTROLLER_REDIRECT = 'redirect';
    private const INCOMING_CONTROLLER_IFRAME   = 'iframe';

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var PaymentData
     */
    protected $_paymentData;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var RemoteAddress
     */
    protected $_configFactory;
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Context                $context
     * @param PaymentData            $paymentData
     * @param StoreManagerInterface  $storeManager
     * @param ConfigFactory          $configFactory
     * @param ResolverInterface      $localeResolver
     * @param Session                $customerSession
     * @param Config                 $config
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Context                $context,
        PaymentData            $paymentData,
        StoreManagerInterface  $storeManager,
        ConfigFactory          $configFactory,
        ResolverInterface      $localeResolver,
        Session                $customerSession,
        Config                 $config
    ) {
        $this->_objectManager   = $objectManager;
        $this->_paymentData     = $paymentData;
        $this->_storeManager    = $storeManager;
        $this->_configFactory   = $configFactory;
        $this->_localeResolver  = $localeResolver;
        $this->_customerSession = $customerSession;
        $this->_scopeConfig     = $context->getScopeConfig();
        $this->_config          = $config;

        parent::__construct($context);
    }

    /**
     * Creates an Instance of the Helper
     *
     * @param  ObjectManagerInterface $objectManager
     *
     * @return Data
     */
    public static function getInstance($objectManager)
    {
        return $objectManager->create(
            get_class()
        );
    }

    /**
     * Get an Instance of the Magento Object Manager
     *
     * @return ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        return $this->_objectManager;
    }

    /**
     * Get an Instance of the Magento Store Manager
     *
     * @return StoreManagerInterface
     */
    protected function getStoreManager()
    {
        return $this->_storeManager;
    }

    /**
     * Get an Instance of the Config Factory Class
     *
     * @return ConfigFactory
     */
    protected function getConfigFactory()
    {
        return $this->_configFactory;
    }

    /**
     * Get an Instance of the Magento UrlBuilder
     *
     * @return UrlInterface
     */
    public function getUrlBuilder()
    {
        return $this->_urlBuilder;
    }

    /**
     * Get an Instance of the Magento Scope Config
     *
     * @return ScopeConfigInterface
     */
    protected function getScopeConfig()
    {
        return $this->_scopeConfig;
    }

    /**
     * Get an Instance of the Magento Core Locale Object
     *
     * @return ResolverInterface
     */
    protected function getLocaleResolver()
    {
        return $this->_localeResolver;
    }

    /**
     * Get an Instance of the Magento Customer Session
     *
     * @return Session
     */
    protected function getCustomerSession()
    {
        return $this->_customerSession;
    }

    /**
     * Retrieves the consumer's user id
     *
     * @return int
     */
    public function getCurrentUserId()
    {
        if ($this->getCustomerSession()->isLoggedIn()) {
            return $this->getCustomerSession()->getId();
        }
        return 0;
    }

    /**
     * @param int $length
     * @return string
     */
    public function getCurrentUserIdHash($length = 30)
    {
        $userId = $this->getCurrentUserId();

        $userHash = $userId > 0 ? sha1($userId) : $this->genTransactionId(null, $length);

        return substr($userHash, 0, $length);
    }

    /**
     * Build URL for store
     *
     * @param string $moduleCode
     * @param string $controller
     * @param string|null $queryParams
     * @param bool|null $secure
     * @param int|null $storeId
     * @return string
     */
    public function getUrl($moduleCode, $controller, $queryParams = null, $secure = null, $storeId = null)
    {
        list($route, $module) = explode('_', $moduleCode);

        $path = sprintf('%s/%s/%s', $route, $module, $controller);

        $store = $this->getStoreManager()->getStore($storeId);
        $params = [
            '_store' => $store,
            '_secure' =>
                ($secure === null
                    ? $this->isStoreSecure($storeId)
                    : $secure
                )
        ];

        if (isset($queryParams) && is_array($queryParams)) {
            $params = array_merge(
                $params,
                $queryParams
            );
        }

        return $this->getUrlBuilder()->getUrl(
            $path,
            $params
        );
    }

    /**
     * Construct Module Notification Url
     * @param bool|null $secure
     * @param int|null $storeId
     * @return string
     */
    public function getNotificationUrl($secure = null, $storeId = null)
    {
        $store = $this->getStoreManager()->getStore($storeId);
        $params = [
            '_store' => $store,
            '_secure' =>
                ($secure === null
                    ? $this->isStoreSecure($storeId)
                    : $secure
                )
        ];

        return $this->getUrlBuilder()->getUrl(
            'emerchantpay/ipn',
            $params
        );
    }

    /**
     * Build Return Url from Payment Gateway
     * @param string $moduleCode
     * @param string $returnAction
     * @return string
     */
    public function getReturnUrl($moduleCode, $returnAction)
    {
        $isIframeProcessingEnabled = $this->_config->isIframeProcessingEnabled();
        $controller = $isIframeProcessingEnabled ?
            self::INCOMING_CONTROLLER_IFRAME :
            self::INCOMING_CONTROLLER_REDIRECT;

        return $this->getUrl(
            $moduleCode,
            $controller,
            [
                'action' => $returnAction
            ]
        );
    }

    /**
     * Generates a unique hash, used for the transaction id
     * @param $length
     * @return string
     */
    protected function uniqHash($length = 30)
    {
        return substr(sha1(
            uniqid(
                microtime() . random_int(1, PHP_INT_MAX),
                true
            )
        ), 0, $length);
    }

    /**
     * Builds a transaction id
     * @param int|null $orderId
     * @param int $length
     * @return string
     */
    public function genTransactionId($orderId = null, $length = 30)
    {
        if (empty($orderId)) {
            return $this->uniqHash($length);
        }

        return substr(sprintf(
            '%s-%s',
            (string)$orderId,
            $this->uniqHash($length)
        ), 0, $length) . self::PLATFORM_TRANSACTION_SUFFIX;
    }

    /**
     * Get Transaction Additional Parameter Value
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @param string $paramName
     * @return null|string
     */
    public function getTransactionAdditionalInfoValue($transaction, $paramName)
    {
        $transactionInformation = $transaction->getAdditionalInformation(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS
        );

        if (is_array($transactionInformation) && isset($transactionInformation[$paramName])) {
            return $transactionInformation[$paramName];
        }

        return null;
    }

    /**
     * Get Transaction Additional Parameter Value
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $paramName
     * @return null|string
     */
    public function getPaymentAdditionalInfoValue(
        \Magento\Payment\Model\InfoInterface $payment,
        $paramName
    ) {
        $paymentAdditionalInfo = $payment->getTransactionAdditionalInfo();

        $rawDetailsKey = \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS;

        if (!array_key_exists($rawDetailsKey, $paymentAdditionalInfo)) {
            return null;
        }

        if (!array_key_exists($paramName, $paymentAdditionalInfo[$rawDetailsKey])) {
            return null;
        }

        return $paymentAdditionalInfo[$rawDetailsKey][$paramName];
    }

    /**
     * Get Transaction Terminal Token Value
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @return null|string
     */
    public function getTransactionTerminalToken($transaction)
    {
        return $this->getTransactionAdditionalInfoValue(
            $transaction,
            self::ADDITIONAL_INFO_KEY_TERMINAL_TOKEN
        );
    }

    /**
     * Get Transaction Status Value
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @return null|string
     */
    public function getTransactionStatus($transaction)
    {
        return $this->getTransactionAdditionalInfoValue(
            $transaction,
            self::ADDITIONAL_INFO_KEY_STATUS
        );
    }

    /**
     * Get Transaction Type
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @return null|string
     */
    public function getTransactionTypeByTransaction($transaction)
    {
        return $this->getTransactionAdditionalInfoValue(
            $transaction,
            self::ADDITIONAL_INFO_KEY_TRANSACTION_TYPE
        );
    }

    /**
     * During "Checkout" we don't know a Token,
     * however its required at a latter stage, which
     * means we have to extract it from the payment
     * data. We save the token when we receive a
     * notification from Genesis.
     *
     * @param \Magento\Sales\Model\Order\Payment\Transaction $paymentTransaction
     *
     * @return bool
     */
    public function setTokenByPaymentTransaction($paymentTransaction)
    {
        if (!isset($paymentTransaction) || empty($paymentTransaction)) {
            return false;
        }

        $transactionTerminalToken = $this->getTransactionTerminalToken(
            $paymentTransaction
        );

        if (!empty($transactionTerminalToken)) {
            \Genesis\Config::setToken($transactionTerminalToken);

            return true;
        }

        return false;
    }

    /**
     * Extracts the Genesis Token from the Transaction Id
     * @param string $transactionId
     *
     * @return void
     */
    public function setTokenByPaymentTransactionId($transactionId)
    {
        $transaction = $this->getPaymentTransaction($transactionId);

        $this->setTokenByPaymentTransaction($transaction);
    }

    /**Get an Instance of a Method Object using the Method Code
     * @param string $methodCode
     * @return \EMerchantPay\Genesis\Model\Config
     */
    public function getMethodConfig($methodCode)
    {
        $parameters = [
            'params' => [
                $methodCode,
                $this->getStoreManager()->getStore()->getId()
            ]
        ];

        $config = $this->getConfigFactory()->create(
            $parameters
        );

        $config->setMethodCode($methodCode);

        return $config;
    }

    /**
     * Hides generated Exception and raises WebApiException in order to
     * display the message to user
     * @param Exception $exception
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function maskException(Exception $exception)
    {
        $this->throwWebApiException(
            $exception->getMessage(),
            $exception->getCode()
        );
    }

    /**
     * Creates a WebApiException from Message or Phrase
     *
     * @param \Magento\Framework\Phrase|string $phrase
     * @param int $httpCode
     * @return \Magento\Framework\Webapi\Exception
     */
    public function createWebApiException(
        $phrase,
        $httpCode = \Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR
    ) {
        if (is_string($phrase)) {
            $phrase = new \Magento\Framework\Phrase($phrase);
        }

        /** Only HTTP error codes are allowed. No success or redirect codes must be used. */
        if ($httpCode < 400 || $httpCode > 599) {
            $httpCode = \Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR;
        }

        return new \Magento\Framework\Webapi\Exception(
            $phrase,
            0,
            $httpCode,
            [],
            '',
            null,
            null
        );
    }

    /**
     * Generates WebApiException from Exception Text
     * @param \Magento\Framework\Phrase|string $errorMessage
     * @param int $errorCode
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function throwWebApiException($errorMessage, $errorCode = 0)
    {
        $webApiException = $this->createWebApiException($errorMessage, $errorCode);

        throw $webApiException;
    }

    /**
     * Find Payment Transaction per Field Value
     * @param string $fieldValue
     * @param string $fieldName
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    public function getPaymentTransaction($fieldValue, $fieldName = 'txn_id')
    {
        if (!isset($fieldValue) || empty($fieldValue)) {
            return null;
        }

        $transaction = $this->getObjectManager()->create(
            "\\Magento\\Sales\\Model\\Order\\Payment\\Transaction"
        )->load(
            $fieldValue,
            $fieldName
        );

        return ($transaction->getId() ? $transaction : null);
    }

    /**
     * Generates an array from Payment Gateway Response Object
     * @param \stdClass $response
     * @return array
     */
    public function getArrayFromGatewayResponse($response)
    {
        $transaction_details = [];
        foreach ($response as $key => $value) {
            if (is_string($value)) {
                $transaction_details[$key] = $value;
            }
            if ($value instanceof \DateTime) {
                $transaction_details[$key] = $value->format('c');
            }
        }
        return $transaction_details;
    }

    /**
     * Checks if the store is secure
     * @param $storeId
     * @return bool
     */
    public function isStoreSecure($storeId = null)
    {
        $store = $this->getStoreManager()->getStore($storeId);
        return $store->isCurrentlySecure();
    }

    /**
     * Sets the AdditionalInfo to the Payment transaction
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param \stdClass $responseObject
     * @return void
     */
    public function setPaymentTransactionAdditionalInfo($payment, $responseObject)
    {
        $payment->setTransactionAdditionalInfo(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            $this->getArrayFromGatewayResponse(
                $responseObject
            )
        );
    }

    /**
     * Updates a payment transaction additional info
     * @param string $transactionId
     * @param \stdClass $responseObject
     * @param bool $shouldCloseTransaction
     * @return bool
     */
    public function updateTransactionAdditionalInfo($transactionId, $responseObject, $shouldCloseTransaction = false)
    {
        $transaction = $this->getPaymentTransaction($transactionId);

        if (isset($transaction)) {
            $this->setTransactionAdditionalInfo(
                $transaction,
                $responseObject
            );

            if ($shouldCloseTransaction) {
                $transaction->setIsClosed(true);
            }

            $transaction->save();

            return true;
        }

        return false;
    }

    /**
     * Set transaction additional information
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @param $responseObject
     */
    public function setTransactionAdditionalInfo($transaction, $responseObject)
    {
        $transaction
            ->setAdditionalInformation(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $this->getArrayFromGatewayResponse(
                    $responseObject
                )
            );
    }

    /**
     * Update Order Status and State
     * @param \Magento\Sales\Model\Order $order
     * @param string $state
     */
    public function setOrderStatusByState($order, $state)
    {
        $order
            ->setState($state)
            ->setStatus(
                $order->getConfig()->getStateDefaultStatus(
                    $state
                )
            );
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $status
     * @param string $message
     */
    public function setOrderState($order, $status, $message = '')
    {
        switch ($status) {
            case \Genesis\Api\Constants\Transaction\States::APPROVED:
                $this->setOrderStatusByState(
                    $order,
                    \Magento\Sales\Model\Order::STATE_PROCESSING
                );
                $order->save();
                break;

            case \Genesis\Api\Constants\Transaction\States::PENDING:
            case \Genesis\Api\Constants\Transaction\States::PENDING_ASYNC:
                $this->setOrderStatusByState(
                    $order,
                    \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
                );
                $order->save();
                break;

            case \Genesis\Api\Constants\Transaction\States::ERROR:
            case \Genesis\Api\Constants\Transaction\States::DECLINED:
                $this->buildInvoiceCancelation($order);
                $this->setOrderStatusByState(
                    $order,
                    \Magento\Sales\Model\Order::STATE_CLOSED
                );
                $order->save();
                break;
            case \Genesis\Api\Constants\Transaction\States::VOIDED:
            case \Genesis\Api\Constants\Transaction\States::TIMEOUT:
                $this->buildInvoiceCancelation($order);
                $this->setOrderStatusByState(
                    $order,
                    \Magento\Sales\Model\Order::STATE_CANCELED
                );
                $order->save();
                break;
            case \Genesis\Api\Constants\Transaction\States::REFUNDED:
                if ($order->canCreditmemo()) {
                    /** @var CreditmemoFactory $creditMemoFactory */
                    $creditMemoFactory = $this->getObjectManager()
                        ->create('Magento\Sales\Model\Order\CreditmemoFactory');
                    /** @var CreditmemoService $creditmemoService */
                    $creditmemoService = $this->getObjectManager()
                        ->create('Magento\Sales\Model\Service\CreditmemoService');

                    $creditMemo = $creditMemoFactory->createByOrder($order);
                    $creditmemoService->refund($creditMemo);
                }

                $order->save();
                break;
            default:
                $order->save();
                break;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param bool $customerNotify
     * @param string $message
     *
     * @return \Magento\Sales\Model\Order
     */
    public function buildInvoiceCancelation($order, $customerNotify = true, $message = '')
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        foreach ($order->getInvoiceCollection() as $invoice) {
            $invoice->cancel();
        }
        $order
            ->registerCancellation($message)
            ->setCustomerNoteNotify($customerNotify);

        return $order;
    }

    /**
     * Build Description Information for the Transaction
     * @param \Magento\Sales\Model\Order $order
     * @param string $lineSeparator
     * @return string
     */
    public function buildOrderDescriptionText($order, $lineSeparator = PHP_EOL)
    {
        $orderDescriptionText = '';

        $orderItems = $order->getItems();

        foreach ($orderItems as $orderItem) {
            $separator = ($orderItem == end($orderItems)) ? '' : $lineSeparator;

            $orderDescriptionText .=
                $orderItem->getQtyOrdered() .
                ' x ' .
                $orderItem->getName() .
                $separator;
        }

        return $orderDescriptionText;
    }

    /**
     * Generates Usage Text (needed to create Transaction)
     * @return \Magento\Framework\Phrase
     */
    public function buildOrderUsage()
    {
        return sprintf('%s %s', __('Payment via'), $this->getStoreName());
    }

    /**
     * Get Store frontend name
     * @return string
     */
    public function getStoreName()
    {
        return $this->getStoreManager()->getStore()->getFrontendName();
    }

    /**
     * Search for a transaction by transaction types
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $transactionTypes
     * @return \Magento\Sales\Model\Order\Payment\Transaction
     */
    public function lookUpPaymentTransaction($payment, array $transactionTypes)
    {
        $transaction = null;

        $lastPaymentTransactionId = $payment->getLastTransId();

        $transaction = $this->getPaymentTransaction(
            $lastPaymentTransactionId
        );

        while (isset($transaction)) {
            if (in_array($transaction->getTxnType(), $transactionTypes)) {
                break;
            }
            $transaction = $this->getPaymentTransaction(
                $transaction->getParentId(),
                'transaction_id'
            );
        }

        return $transaction;
    }

    /**
     * Find Authorization Payment Transaction
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $transactionTypes
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    public function lookUpAuthorizationTransaction($payment, $transactionTypes = [
            \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH
        ]
    )
    {
        return $this->lookUpPaymentTransaction(
            $payment,
            $transactionTypes
        );
    }

    /**
     * Find Capture Payment Transaction
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $transactionTypes
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    public function lookUpCaptureTransaction($payment, $transactionTypes = [
            \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE
        ]
    )
    {
        return $this->lookUpPaymentTransaction(
            $payment,
            $transactionTypes
        );
    }

    /**
     * Find Void Payment Transaction Reference (Auth or Capture)
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $transactionTypes
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    public function lookUpVoidReferenceTransaction($payment, $transactionTypes = [
        \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
        \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH
        ]
    )
    {
        return $this->lookUpPaymentTransaction(
            $payment,
            $transactionTypes
        );
    }

    /**
     * Get an array of all global allowed currency codes
     * @return array
     */
    public function getGlobalAllowedCurrencyCodes()
    {
        $allowedCurrencyCodes = $this->getScopeConfig()->getValue(
            \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_ALLOW
        );

        return array_map(
            function ($item) {
                return trim($item);
            },
            explode(
                ',',
                $allowedCurrencyCodes
            )
        );
    }

    /**
     * Builds Select Options for the Allowed Currencies in the Admin Zone
     * @param array $availableCurrenciesOptions
     * @return array
     */
    public function getGlobalAllowedCurrenciesOptions(array $availableCurrenciesOptions)
    {
        $allowedCurrenciesOptions = [];

        $allowedGlobalCurrencyCodes = $this->getGlobalAllowedCurrencyCodes();

        foreach ($availableCurrenciesOptions as $availableCurrencyOptions) {
            if (in_array($availableCurrencyOptions['value'], $allowedGlobalCurrencyCodes)) {
                $allowedCurrenciesOptions[] = $availableCurrencyOptions;
            }
        }
        return $allowedCurrenciesOptions;
    }

    /**
     * Filter Module allowed Currencies with the global allowed currencies
     * @param array $allowedLocalCurrencies
     * @return array
     */
    public function getFilteredLocalAllowedCurrencies(array $allowedLocalCurrencies)
    {
        $result = [];
        $allowedGlobalCurrencyCodes = $this->getGlobalAllowedCurrencyCodes();

        foreach ($allowedLocalCurrencies as $allowedLocalCurrency) {
            if (in_array($allowedLocalCurrency, $allowedGlobalCurrencyCodes)) {
                $result[] = $allowedLocalCurrency;
            }
        }

        return $result;
    }

    /**
     * Get Magento Core Locale
     * @param string $default
     * @return string
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function getLocale($default = 'en')
    {
        $languageCode = strtolower(
            $this->getLocaleResolver()->getLocale()
        );

        $languageCode = substr($languageCode, 0, 2);

        if (!\Genesis\Api\Constants\i18n::isValidLanguageCode($languageCode)) {
            $languageCode = $default;
        }

        if (!\Genesis\Api\Constants\i18n::isValidLanguageCode($languageCode)) {
            $this->throwWebApiException(
                __('The provided argument is not a valid ISO-639-1 language code ' .
                   'or is not supported by the Payment Gateway!')
            );
        }

        return $languageCode;
    }

    /**
     * Get is allowed to refund transaction
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @return bool
     */
    public function canRefundTransaction($transaction)
    {
        $transactionType = $this->getTransactionTypeByTransaction(
            $transaction
        );

        return (!empty($transactionType) && GenesisTransactionTypes::canRefund($transactionType));
    }

    /**
     * Check is Payment Method available for currency
     * @param string $methodCode
     * @param string $currencyCode
     * @return bool
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function isCurrencyAllowed($methodCode, $currencyCode)
    {
        $methodConfig = $this->getMethodConfig($methodCode);

        if (!$methodConfig->getAreAllowedSpecificCurrencies()) {
            $allowedMethodCurrencies = $this->getGlobalAllowedCurrencyCodes();
        } else {
            $allowedMethodCurrencies =
                $this->getFilteredLocalAllowedCurrencies(
                    $methodConfig->getAllowedCurrencies()
                );
        }

        return in_array($currencyCode, $allowedMethodCurrencies);
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public function getStringEndsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    /**
     * @param string $transactionType
     * @return bool
     */
    public function getIsTransactionThreeDSecure($transactionType)
    {
        return GenesisTransactionTypes::is3D($transactionType);
    }

    /**
     * Retrieves the complete error message from gateway
     *
     * @param \stdClass $response
     * @return string
     */
    public function getErrorMessageFromGatewayResponse($response)
    {
        return
            (isset($response->message) && isset($response->technical_message))
                ? "{$response->message} {$response->technical_message}"
                : self::GENESIS_GATEWAY_ERROR_MESSAGE_DEFAULT;
    }

    /**
     * @param \Genesis\Api\Response $genesisApiResponse
     * @return \stdClass
     */
    public function getGatewayResponseObject($genesisApiResponse)
    {
        return $genesisApiResponse->getResponseObject();
    }

    /**
     * Executes a request to the Genesis Payment Gateway
     *
     * @param Genesis $genesis
     *
     * @return Genesis
     *
     * @throws Exception
     */
    public function executeGatewayRequest(Genesis $genesis)
    {
        $genesis->execute();
        if (!$genesis->response()->isSuccessful()) {
            throw new Exception($genesis->response()->getErrorDescription());
        }

        return $genesis;
    }

    /**
     * Creates Notification Object
     *
     * @param array $data - Incoming notification ($_POST)
     * @return \Genesis\Api\Notification
     */
    public function createNotificationObject($data)
    {
        $notification = new \Genesis\Api\Notification($data);

        return $notification;
    }

    /**
     * @param string $transactionType
     * @return bool
     */
    public function getShouldCreateAuthNotification($transactionType)
    {
        if ($this->isTransactionWithCustomAttribute($transactionType)) {
            return $this->isSelectedAuthorizePaymentType($transactionType);
        }

        return GenesisTransactionTypes::isAuthorize($transactionType);
    }

    /**
     * @param string $transactionType
     * @return bool
     */
    public function getShouldCreateCaptureNotification($transactionType)
    {
        if ($this->isTransactionWithCustomAttribute($transactionType)) {
            return !$this->isSelectedAuthorizePaymentType($transactionType);
        }

        return !GenesisTransactionTypes::isAuthorize($transactionType);
    }

    /**
     * @param Order $order
     * @return \Genesis\Api\Request\Financial\Alternatives\Klarna\Items
     * @throws \Genesis\Exceptions\ErrorParameter
     */
    public function getKlarnaCustomParamItems($order)
    {
        $items     = new \Genesis\Api\Request\Financial\Alternatives\Klarna\Items($order->getOrderCurrencyCode());
        $itemsList = $this->getItemListArray($order);
        foreach ($itemsList as $item) {
            $klarnaItem = new \Genesis\Api\Request\Financial\Alternatives\Klarna\Item(
                $item['name'],
                $item['type'],
                $item['qty'],
                $item['price']
            );
            $items->addItem($klarnaItem);
        }

        $taxes = floatval($order->getTaxAmount());
        if ($taxes) {
            $items->addItem(
                new \Genesis\Api\Request\Financial\Alternatives\Klarna\Item(
                    'Taxes',
                    \Genesis\Api\Request\Financial\Alternatives\Klarna\Item::ITEM_TYPE_SURCHARGE,
                    1,
                    $taxes
                )
            );
        }

        $discount = floatval($order->getDiscountAmount());
        if ($discount) {
            $items->addItem(
                new \Genesis\Api\Request\Financial\Alternatives\Klarna\Item(
                    'Discount',
                    \Genesis\Api\Request\Financial\Alternatives\Klarna\Item::ITEM_TYPE_DISCOUNT,
                    1,
                    -$discount
                )
            );
        }

        $shipping_cost = floatval($order->getShippingAmount());
        if ($shipping_cost) {
            $items->addItem(
                new \Genesis\Api\Request\Financial\Alternatives\Klarna\Item(
                    'Shipping Costs',
                    \Genesis\Api\Request\Financial\Alternatives\Klarna\Item::ITEM_TYPE_SHIPPING_FEE,
                    1,
                    $shipping_cost
                )
            );
        }

        return $items;
    }

    /**
     * @param $order
     * @return array
     */
    public function getItemListArray($order)
    {
        $productResult = [];

        foreach ($order->getAllItems() as $item) {
            /** @var $item Mage_Sales_Model_Quote_Item */
            $product = $item->getProduct();

            $type = $item->getIsVirtual() ?
                \Genesis\Api\Request\Financial\Alternatives\Klarna\Item::ITEM_TYPE_DIGITAL :
                \Genesis\Api\Request\Financial\Alternatives\Klarna\Item::ITEM_TYPE_PHYSICAL;

            $productResult[] = [
                'sku'  =>
                    $product->getSku(),
                'name' =>
                    $product->getName(),
                'qty'  =>
                    $item->getData('qty_ordered'),
                'price' =>
                    $item->getPrice(),
                'type' =>
                    $type
            ];
        }

        return $productResult;
    }

    /**
     * Extract the payment transaction object from Genesis Response
     *
     * @param \stdClass $responseObject
     * @param int $payment_id
     *
     * @return \stdClass
     */
    public function populatePaymentTransaction($responseObject, $payment_id)
    {
        if (isset($responseObject->payment_transaction->unique_id)) {
            return $responseObject->payment_transaction;
        }

        if (count($responseObject->payment_transaction) > 1) {
            $paymentTransactions = $responseObject->payment_transaction;
            $lastTransaction     = $this->getLastPaymentTransaction(
                $payment_id
            );

            if (!isset($lastTransaction)) {
                return $paymentTransactions[0];
            }

            foreach ($paymentTransactions as $paymentTransaction) {
                if ($paymentTransaction->unique_id == $lastTransaction->getParentTxnId()) {
                    return $paymentTransaction;
                }
            }

            return $paymentTransactions[0];
        }
    }

    /**
     * Find last Payment TransactionFind Payment Transaction per Field Value
     *
     * @param string $fieldValue
     * @param string $fieldName
     *
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    public function getLastPaymentTransaction($fieldValue, $fieldName = 'payment_id')
    {
        if (!isset($fieldValue) || empty($fieldValue)) {
            return null;
        }

        $transactionBuilder = $this->getObjectManager()->create(
            "\\Magento\\Sales\\Model\\Order\\Payment\\Transaction\\Repository"
        );

        $searchBuilder = $this->getObjectManager()->create("\\Magento\\Framework\\Api\\SearchCriteriaBuilder");
        $filterBuilder = $this->getObjectManager()->create("\\Magento\\Framework\\Api\\FilterBuilder");
        $sortBuilder   = $this->getObjectManager()->create("\\Magento\\Framework\\Api\\SortOrder");

        $filters[] = $filterBuilder
            ->setField($fieldName)
            ->setValue($fieldValue)
            ->create();

        $orderCriteria = $sortBuilder
            ->setField("transaction_id")
            ->setDirection("DESC");

        $searchCriteria = $searchBuilder
            ->addFilters($filters)
            ->setPageSize(1)
            ->setSortOrders([$orderCriteria])
            ->create();

        /** @var \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection $transactionList */
        $transactionList = $transactionBuilder->getList($searchCriteria);

        if ($transactionList->getSize()) {
            /** @var \Magento\Sales\Model\Order\Payment\Transaction $trx */
            $transaction = $transactionList->getLastItem();
            return $transaction;
        }

        return null;
    }

    /**
     * Check if special validation should be applied
     *
     * @param $transactionType
     * @return bool
     */
    public function isTransactionWithCustomAttribute($transactionType)
    {
        $transactionTypes = [
            GenesisTransactionTypes::GOOGLE_PAY,
            GenesisTransactionTypes::PAY_PAL,
            GenesisTransactionTypes::APPLE_PAY
        ];

        return in_array($transactionType, $transactionTypes);
    }

    /**
     * Check if we should create Authorize Notification to the Magento store
     *
     * @param $transactionType
     * @return bool
     */
    public function isSelectedAuthorizePaymentType($transactionType)
    {
        switch ($transactionType) {
            case GenesisTransactionTypes::GOOGLE_PAY:
                return in_array(
                    self::GOOGLE_PAY_TRANSACTION_PREFIX . self::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE,
                    $this->getMethodConfig(\EMerchantPay\Genesis\Model\Method\Checkout::CODE)
                        ->getTransactionTypes()
                );
            case GenesisTransactionTypes::PAY_PAL:
                return in_array(
                    self::PAYPAL_TRANSACTION_PREFIX . self::PAYPAL_PAYMENT_TYPE_AUTHORIZE,
                    $this->getMethodConfig(\EMerchantPay\Genesis\Model\Method\Checkout::CODE)
                        ->getTransactionTypes()
                );
            case GenesisTransactionTypes::APPLE_PAY:
                return in_array(
                    self::APPLE_PAY_TRANSACTION_PREFIX . self::APPLE_PAY_PAYMENT_TYPE_AUTHORIZE,
                    $this->getMethodConfig(\EMerchantPay\Genesis\Model\Method\Checkout::CODE)
                        ->getTransactionTypes()
                );
            default:
                return false;
        }
    }

    /**
     * Search for a Physical product in the Checkout Order
     *
     * @param $order
     * @return bool
     */
    public function isCheckoutWithPhysicalProduct($order)
    {
        foreach ($order->getAllItems() as $item) {
            $isVirtual = (bool) $item->getIsVirtual();
            if (!$isVirtual) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load the Customer Entity
     *
     * @param $customerId
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomerEntity($customerId)
    {
        return $this->_objectManager
            ->create('Magento\Customer\Model\Customer')
            ->load($customerId);
    }

    /**
     * Get All order created from the Customer
     *
     * @param $customerId
     * @param array $status
     * @param string $fromTime
     * @param string $toTime
     * @param string $sort
     * @return mixed
     */
    public function getCustomerOrders($customerId, $status = [], $fromTime = '', $toTime = '', $sort = 'ASC')
    {
        $collection = $this->_objectManager->create('Magento\Sales\Model\Order')->getCollection();

        $collection
            ->join(
                ['payment' => 'sales_order_payment'],
                'main_table.entity_id = payment.parent_id',
                ['method']
            )
            ->addFieldToFilter('payment.method', \EMerchantPay\Genesis\Model\Method\Checkout::CODE)
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('main_table.created_at', $sort);

        if (!empty($status)) {
            $collection
                ->addFieldToFilter('status', ['in' => $status]);
        }

        if (!empty($fromTime) && !empty($toTime)) {
            $collection
                ->addFieldToFilter('created_at', ['from' => $fromTime, 'to' => $toTime]);
        }

        return $collection;
    }
}
