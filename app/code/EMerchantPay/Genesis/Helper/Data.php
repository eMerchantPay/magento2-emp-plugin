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

namespace EMerchantPay\Genesis\Helper;

/**
 * Helper Class for all Payment Methods
 *
 * Class Data
 * @package EMerchantPay\Genesis\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
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
    protected $_remoteAddress;
    /**
     * @var \EMerchantPay\Genesis\Model\ConfigFactory
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
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager,
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \EMerchantPay\Genesis\Model\ConfigFactory $configFactory,
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \EMerchantPay\Genesis\Model\ConfigFactory $configFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        $this->_objectManager = $objectManager;
        $this->_paymentData   = $paymentData;
        $this->_storeManager  = $storeManager;
        $this->_remoteAddress = $remoteAddress;
        $this->_configFactory = $configFactory;
        $this->_scopeConfig   = $scopeConfig;
        $this->_localeResolver = $localeResolver;
        parent::__construct($context);
    }

    /**
     * Creates an Instance of the Helper
     * @param  \Magento\Framework\ObjectManagerInterface $objectManager
     * @return \EMerchantPay\Genesis\Helper\Data
     */
    public static function getInstance($objectManager)
    {
        return $objectManager->create(get_class());
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
     * Get an Instance of the Magento RemoteAddress Object
     * @return \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    public function getRemoteAddressInstance()
    {
        return $this->_remoteAddress;
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

        $path = sprintf("%s/%s/%s", $route, $module, $controller);

        $store = $this->getStoreManager()->getStore($storeId);
        $params = [
            "_store" => $store,
            "_secure" =>
                ($secure === null
                    ? $store->isCurrentlySecure()
                    : $secure
                )
        ];

        if (isset($queryParams) && is_array($queryParams)) {
            foreach ($queryParams as $queryKey => $queryValue) {
                $params[$queryKey] = $queryValue;
            }
        }

        return $this->getUrlBuilder()->getUrl(
            $path,
            $params
        );
    }

    /**
     * Construct Module Notification Url
     * @param string $moduleCode
     * @param bool|null $secure
     * @param int|null $storeId
     * @return string
     * @SuppressWarning(PHPMD.UnusedLocalVariable)
     */
    public function getNotificationUrl($moduleCode, $secure = null, $storeId = null)
    {
        $store = $this->getStoreManager()->getStore($storeId);
        $params = [
            "_store" => $store,
            "_secure" =>
                ($secure === null
                    ? $store->isCurrentlySecure()
                    : $secure
                )
        ];

        return $this->getUrlBuilder()->getUrl(
            "emerchantpay/ipn",
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
            "redirect",
            [
                "action" => $returnAction
            ]
        );
    }

    /**
     * Generates a unique hash, used for the transaction id
     * @return string
     */
    protected function uniqHash()
    {
        return md5(uniqid(microtime().mt_rand(), true));
    }

    /**
     * Builds a transaction id
     * @param int|null $orderId
     * @return string
     */
    public function genTransactionId($orderId = null)
    {
        if (empty($orderId)) {
            return $this->uniqHash();
        }

        return sprintf(
            "%s_%s",
            strval($orderId),
            $this->uniqHash()
        );
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
     * Get Transaction Terminal Token Value
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transaction
     * @return null|string
     */
    public function getTransactionTerminalToken($transaction)
    {
        return $this->getTransactionAdditionalInfoValue(
            $transaction,
            'terminal_token'
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
            'transaction_type'
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
     * @param \Exception $e
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function maskException(\Exception $e)
    {
        $this->throwWebApiException(
            $e->getMessage(),
            $e->getCode()
        );
    }

    /**
     * Generates WebApiException from Exception Text
     * @param string $errorMessage
     * @param int $errorCode
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function throwWebApiException($errorMessage, $errorCode = 0)
    {
        $maskedException = new \Magento\Framework\Webapi\Exception(
            new \Magento\Framework\Phrase($errorMessage),
            $errorCode,
            \Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR,
            [],
            '',
            null,
            null
        );

        throw $maskedException;
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
        $transaction_details = array();
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
                /** @var Mage_Sales_Model_Order_Invoice $invoice */
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $invoice->cancel();
                }
                $order
                    ->registerCancellation($message)
                    ->setCustomerNoteNotify(true)
                    ->save();
                break;
            default:
                $order->save();
                break;
        }
    }

    /**
     * Build Description Information for the Transaction
     * @param \Magento\Sales\Model\Order $order
     * @param string $lineSeparator
     * @return string
     */
    public function buildOrderDescriptionText($order, $lineSeparator = PHP_EOL)
    {
        $orderDescriptionText = "";

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
        return __("Magento 2 Transaction");
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

        return array_filter(
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
            \Genesis\API\Constants\Transaction\Types::CAPTURE,
            \Genesis\API\Constants\Transaction\Types::SALE,
            \Genesis\API\Constants\Transaction\Types::SALE_3D,
            \Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE,
            \Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE_3D,
            \Genesis\API\Constants\Transaction\Types::RECURRING_SALE,
            \Genesis\API\Constants\Transaction\Types::CASHU,
            \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Transaction\Types::ABNIDEAL
        ];

        $transactionType = $this->getTransactionTypeByTransaction(
            $transaction
        );

        return (!empty($transactionType) && in_array($transactionType, $refundableTransactions));
    }
}
