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

namespace EMerchantPay\Genesis\Helper;

use \Genesis\API\Constants\Transaction\Types as GenesisTransactionTypes;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;

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
class Data extends \Magento\Framework\App\Helper\AbstractHelper
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

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentData;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_configFactory;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \EMerchantPay\Genesis\Model\ConfigFactory $configFactory
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \EMerchantPay\Genesis\Model\ConfigFactory $configFactory,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->_objectManager = $objectManager;
        $this->_paymentData   = $paymentData;
        $this->_storeManager  = $storeManager;
        $this->_configFactory = $configFactory;
        $this->_localeResolver = $localeResolver;
        $this->_customerSession = $customerSession;

        $this->_scopeConfig   = $context->getScopeConfig();

        parent::__construct($context);
    }

    /**
     * Creates an Instance of the Helper
     * @param  \Magento\Framework\ObjectManagerInterface $objectManager
     * @return \EMerchantPay\Genesis\Helper\Data
     */
    public static function getInstance($objectManager)
    {
        return $objectManager->create(
            get_class()
        );
    }

    /**
     * Get an Instance of the Magento Object Manager
     * @return \Magento\Framework\ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        return $this->_objectManager;
    }

    /**
     * Get an Instance of the Magento Store Manager
     * @return \Magento\Store\Model\StoreManagerInterface
     */
    protected function getStoreManager()
    {
        return $this->_storeManager;
    }

    /**
     * Get an Instance of the Config Factory Class
     * @return \EMerchantPay\Genesis\Model\ConfigFactory
     */
    protected function getConfigFactory()
    {
        return $this->_configFactory;
    }

    /**
     * Get an Instance of the Magento UrlBuilder
     * @return \Magento\Framework\UrlInterface
     */
    public function getUrlBuilder()
    {
        return $this->_urlBuilder;
    }

    /**
     * Get an Instance of the Magento Scope Config
     * @return \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected function getScopeConfig()
    {
        return $this->_scopeConfig;
    }

    /**
     * Get an Instance of the Magento Core Locale Object
     * @return \Magento\Framework\Locale\ResolverInterface
     */
    protected function getLocaleResolver()
    {
        return $this->_localeResolver;
    }

    /**
     * Get an Instance of the Magento Customer Session
     * @return \Magento\Customer\Model\Session
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
        return $this->getUrl(
            $moduleCode,
            'redirect',
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
                microtime() . mt_rand(),
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
            '%s_%s',
            (string)$orderId,
            $this->uniqHash($length)
        ), 0, $length);
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
     * @param \Exception $exception
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function maskException(\Exception $exception)
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
            case \Genesis\API\Constants\Transaction\States::APPROVED:
                $this->setOrderStatusByState(
                    $order,
                    \Magento\Sales\Model\Order::STATE_PROCESSING
                );
                $order->save();
                break;

            case \Genesis\API\Constants\Transaction\States::PENDING:
            case \Genesis\API\Constants\Transaction\States::PENDING_ASYNC:
                $this->setOrderStatusByState(
                    $order,
                    \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
                );
                $order->save();
                break;

            case \Genesis\API\Constants\Transaction\States::ERROR:
            case \Genesis\API\Constants\Transaction\States::DECLINED:
                $this->buildInvoiceCancelation($order);
                $this->setOrderStatusByState(
                    $order,
                    \Magento\Sales\Model\Order::STATE_CLOSED
                );
                $order->save();
                break;
            case \Genesis\API\Constants\Transaction\States::VOIDED:
            case \Genesis\API\Constants\Transaction\States::TIMEOUT:
                $this->buildInvoiceCancelation($order);
                $this->setOrderStatusByState(
                    $order,
                    \Magento\Sales\Model\Order::STATE_CANCELED
                );
                $order->save();
                break;
            case \Genesis\API\Constants\Transaction\States::REFUNDED:
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
        return __('Magento 2 Transaction');
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

        if (!\Genesis\API\Constants\i18n::isValidLanguageCode($languageCode)) {
            $languageCode = $default;
        }

        if (!\Genesis\API\Constants\i18n::isValidLanguageCode($languageCode)) {
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
        $refundableTransactions = [
            GenesisTransactionTypes::CAPTURE,
            GenesisTransactionTypes::SALE,
            GenesisTransactionTypes::SALE_3D,
            GenesisTransactionTypes::INIT_RECURRING_SALE,
            GenesisTransactionTypes::INIT_RECURRING_SALE_3D,
            GenesisTransactionTypes::RECURRING_SALE,
            GenesisTransactionTypes::CASHU,
            GenesisTransactionTypes::PPRO,
            GenesisTransactionTypes::ABNIDEAL,
            GenesisTransactionTypes::TRUSTLY_SALE,
            GenesisTransactionTypes::P24,
            GenesisTransactionTypes::INPAY,
            GenesisTransactionTypes::PAYPAL_EXPRESS
        ];

        $transactionType = $this->getTransactionTypeByTransaction(
            $transaction
        );

        return (!empty($transactionType) && in_array($transactionType, $refundableTransactions));
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
        return
            $this->getStringEndsWith(
                strtoupper($transactionType),
                self::SECURE_TRANSACTION_TYPE_SUFFIX
            );
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
     * @param \Genesis\API\Response $genesisApiResponse
     * @return \stdClass
     */
    public function getGatewayResponseObject($genesisApiResponse)
    {
        return $genesisApiResponse->getResponseObject();
    }

    /**
     * Executes a request to the Genesis Payment Gateway
     *
     * @param \Genesis\Genesis $genesis
     * @return \Genesis\Genesis
     */
    public function executeGatewayRequest(\Genesis\Genesis $genesis)
    {
        $genesis->execute();

        return $genesis;
    }

    /**
     * Creates Notification Object
     *
     * @param array $data - Incoming notification ($_POST)
     * @return \Genesis\API\Notification
     */
    public function createNotificationObject($data)
    {
        $notification = new \Genesis\API\Notification($data);

        return $notification;
    }

    /**
     * @param string $transactionType
     * @return bool
     */
    public function getShouldCreateAuthNotification($transactionType)
    {
        $authorizeTransactions = [
            GenesisTransactionTypes::AUTHORIZE,
            GenesisTransactionTypes::AUTHORIZE_3D
        ];

        return in_array($transactionType, $authorizeTransactions);
    }

    /**
     * @param string $transactionType
     * @return bool
     */
    public function getShouldCreateCaptureNotification($transactionType)
    {
        $captureNotificationTransactions = [
            GenesisTransactionTypes::ABNIDEAL,
            GenesisTransactionTypes::ALIPAY,
            GenesisTransactionTypes::CITADEL_PAYIN,
            GenesisTransactionTypes::CASHU,
            GenesisTransactionTypes::EZEEWALLET,
            GenesisTransactionTypes::IDEBIT_PAYIN,
            GenesisTransactionTypes::INPAY,
            GenesisTransactionTypes::INSTA_DEBIT_PAYIN,
            GenesisTransactionTypes::TRUSTLY_SALE,
            GenesisTransactionTypes::NETELLER,
            GenesisTransactionTypes::P24,
            GenesisTransactionTypes::PAYBYVOUCHER_SALE,
            GenesisTransactionTypes::PAYBYVOUCHER_YEEPAY,
            GenesisTransactionTypes::PAYPAL_EXPRESS,
            GenesisTransactionTypes::PAYSAFECARD,
            GenesisTransactionTypes::ONLINE_BANKING_PAYIN,
            GenesisTransactionTypes::PPRO,
            GenesisTransactionTypes::SALE,
            GenesisTransactionTypes::SALE_3D,
            GenesisTransactionTypes::SOFORT,
            GenesisTransactionTypes::SDD_SALE,
            GenesisTransactionTypes::WECHAT
        ];

        return in_array($transactionType, $captureNotificationTransactions);
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
}
