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
 * Direct Payment Method Model Class
 * Class Direct
 * @package EMerchantPay\Genesis\Model\Method
 */
class Direct extends \Magento\Payment\Model\Method\Cc
{
    use \EMerchantPay\Genesis\Model\Traits\OnlinePaymentMethod;

    const CODE = 'emerchantpay_direct';

    /**
     * Direct Method Code
     */
    protected $_code = self::CODE;

    protected $_canOrder = true;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCancelInvoice = true;
    protected $_canVoid = true;

    protected $_isInitializeNeeded = false;

    protected $_canFetchTransactionInfo = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;

    protected $_transactionType = null;

    /**
     * Direct constructor.
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
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList ,
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate ,
     * @param \Magento\Directory\Model\CountryFactory $countryFactory ,
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
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \EMerchantPay\Genesis\Helper\Data $moduleHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_actionContext = $actionContext;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_moduleHelper = $moduleHelper;
        $this->_configHelper =
            $this->getModuleHelper()->getMethodConfig(
                $this->getCode()
            );
    }

    /**
     * Gets transaction type
     * @return string
     */
    public function getTransactionType()
    {
        return $this->_transactionType;
    }

    /**
     * Sets transaction type
     * @param $transactionType
     */
    public function setTransactionType($transactionType)
    {
        $this->_transactionType = $transactionType;
    }

    /**
     * Gets Instance of the Magento Code Logger
     *
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Gets Default Payment Action On Payment Complete Action
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $transactionType = $this->getConfigData('payment_action');

        $config = $this->getModuleHelper()->getTransactionConfig($transactionType);

        return $config->action;
    }

    /**
     * Authorize payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->setTransactionType($this->getConfigData('payment_action'));  // authorize or authorize3d
        return $this->processTransaction($payment, $amount);
    }

    /**
     * Capture payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $actionName = $this->getActionContext()->getRequest()->getActionName();

        $transactionType = isset($actionName) ?
            \Genesis\API\Constants\Transaction\Types::CAPTURE :
            $this->getConfigData('payment_action'); // sale or sale3d

        $this->setTransactionType($transactionType);

        return $this->processTransaction($payment, $amount);
    }

    /**
     * Refund payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->setTransactionType(\Genesis\API\Constants\Transaction\Types::REFUND);
        return $this->processTransaction($payment, $amount);
    }

    /**
     * Void payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->setTransactionType(\Genesis\API\Constants\Transaction\Types::VOID);
        return $this->processTransaction($payment);
    }

    /**
     * Cancel order
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this->void($payment);
    }

    /**
     * Common transactions handler
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return $this
     */
    protected function processTransaction(\Magento\Payment\Model\InfoInterface $payment, $amount = null)
    {
        $transactionType = $this->getTransactionType();

        $config = $this->getModuleHelper()->getTransactionConfig($transactionType);

        $order = $payment->getOrder();

        try {
            if ($config->reference) {
                $this->processRefTransaction($payment, $amount);
            } else {
                $this->processInitialTransaction($payment, $amount);
            }
        } catch (\Exception $e) {
            $logInfo =
                'Transaction ' . $transactionType .
                ' for order #' . $order->getIncrementId() .
                ' failed with message "' . $e->getMessage() . '"';

            $this->getLogger()->error($logInfo);

            $this->getModuleHelper()->throwException(
                $e->getMessage(),
                $config->reference
            );
        }

        return $this;
    }

    /**
     * Processes initial transactions
     *      - Authorize
     *      - Authorize3D
     *      - Sale
     *      - Sale3D
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return $this
     * @throws \Exception
     * @throws \Genesis\Exceptions\ErrorAPI
     */
    protected function processInitialTransaction(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionType = $this->getTransactionType();

        $order = $payment->getOrder();

        $helper = $this->getModuleHelper();

        $config = $helper->getTransactionConfig($transactionType);

        $this->getConfigHelper()->initGatewayClient();

        $billing = $order->getBillingAddress();
        if (empty($billing)) {
            throw new \Exception(__('Billing address is empty.'));
        }

        $shipping = $order->getShippingAddress();

        $genesis = new \Genesis\Genesis($config->request);

        $genesis
            ->request()
            ->setTransactionId($helper->genTransactionId($order->getIncrementId()))
            ->setRemoteIp($order->getRemoteIp())
            ->setUsage($helper->buildOrderDescriptionText($order))
            ->setLanguage($helper->getLocale())
            ->setCurrency(
                $order->getBaseCurrencyCode()
            )
            ->setAmount($amount);

        if (!empty($payment->getCcOwner())) {
            $genesis
                ->request()
                ->setCardHolder($payment->getCcOwner());
        } else {
            $genesis
                ->request()
                ->setCardHolder($billing->getFirstname() . ' ' . $billing->getLastname());
        }

        $genesis
            ->request()
            ->setCardNumber($payment->getCcNumber())
            ->setExpirationYear($payment->getCcExpYear())
            ->setExpirationMonth($payment->getCcExpMonth())
            ->setCvv($payment->getCcCid())
            ->setCustomerEmail($order->getCustomerEmail())
            ->setCustomerPhone($billing->getTelephone())
            ->setBillingFirstName($billing->getFirstname())
            ->setBillingLastName($billing->getLastname())
            ->setBillingAddress1($billing->getStreetLine(1))
            ->setBillingAddress2($billing->getStreetLine(2))
            ->setBillingZipCode($billing->getPostcode())
            ->setBillingCity($billing->getCity())
            ->setBillingState($billing->getRegionCode())
            ->setBillingCountry($billing->getCountryId());

        if (!empty($shipping)) {
            $genesis
                ->request()
                ->setShippingFirstName($shipping->getFirstname())
                ->setShippingLastName($shipping->getLastname())
                ->setShippingAddress1($shipping->getStreetLine(1))
                ->setShippingAddress2($shipping->getStreetLine(2))
                ->setShippingZipCode($shipping->getPostcode())
                ->setShippingCity($shipping->getCity())
                ->setShippingState($shipping->getRegionCode())
                ->setShippinCountry($shipping->getCountryId());
        }

        if ($config->is3D) {
            $genesis
                ->request()
                ->setNotificationUrl($helper->getNotificationUrl(
                    $this->getCode()
                ))
                ->setReturnSuccessUrl($helper->getReturnUrl(
                    $this->getCode(),
                    "success"
                ))
                ->setReturnCancelUrl($helper->getReturnUrl(
                    $this->getCode(),
                    "cancel"
                ))
                ->setReturnFailureUrl($helper->getReturnUrl(
                    $this->getCode(),
                    "failure"
                ));
        }

        $genesis->execute();

        $this->setGenesisResponse(
            $genesis->response()->getResponseObject()
        );

        $genesis_response = $this->getModuleHelper()->getArrayFromGatewayResponse(
            $this->getGenesisResponse()
        );

        $payment
            ->setTransactionId(
                $this->getGenesisResponse()->unique_id
            )
            ->setIsTransactionClosed(
                $config->should_close
            )
            ->setIsTransactionPending(
                $config->is3D
            )
            ->setTransactionAdditionalInfo(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $genesis_response
            );

        $status = $this->getGenesisResponse()->status;

        $statusList = array(
            \Genesis\API\Constants\Transaction\States::DECLINED,
            \Genesis\API\Constants\Transaction\States::ERROR,
            \Genesis\API\Constants\Transaction\States::UNSUCCESSFUL
        );

        if (in_array($status, $statusList)) {
            throw new \Genesis\Exceptions\ErrorAPI(
                $this->getGenesisResponse()->message
            );
        }

        if ($config->is3D) {
            $this->setRedirectUrl($this->getGenesisResponse()->redirect_url);
            $payment->setPreparedMessage('3D-Secure: Redirecting customer to a verification page.');
        } else {
            $this->unsetRedirectUrl();
        }
    }

    /**
     * Processes reference transactions
     *      - Capture
     *      - Refund
     *      - Void
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @throws \Exception
     */
    protected function processRefTransaction(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionType = $this->getTransactionType();

        $order = $payment->getOrder();

        switch ($transactionType) {
            case \Genesis\API\Constants\Transaction\Types::CAPTURE:
                $data = array(
                    'transaction_id' =>
                        $this->getModuleHelper()->genTransactionId(),
                    'remote_ip' =>
                        $order->getRemoteIp(),
                    'reference_id' =>
                        $this->getReferenceCaptureTxnId($payment),
                    'currency' =>
                        $order->getBaseCurrencyCode(),
                    'amount' =>
                        $amount
                );
                break;
            case \Genesis\API\Constants\Transaction\Types::REFUND:
                $data = array(
                    'transaction_id' =>
                        $this->getModuleHelper()->genTransactionId(),
                    'remote_ip' =>
                        $order->getRemoteIp(),
                    'reference_id' =>
                        $this->getReferenceRefundTxnId($payment),
                    'currency' =>
                        $order->getBaseCurrencyCode(),
                    'amount' =>
                        $amount
                );
                break;
            case \Genesis\API\Constants\Transaction\Types::VOID:
                $data = array(
                    'transaction_id' =>
                        $this->getModuleHelper()->genTransactionId(),
                    'remote_ip' =>
                        $order->getRemoteIp(),
                    'reference_id' =>
                        $this->getReferenceVoidTxnId($payment)
                );
                break;
            default:
                throw new \Exception(__('Unsupported transaction (' . $transactionType . ').'));
        }

        $responseObject = $this->processReferenceTransaction(
            $transactionType,
            $payment,
            $data
        );

        if ($responseObject->status == \Genesis\API\Constants\Transaction\States::APPROVED) {
            $this->getMessageManager()->addSuccess($responseObject->message);
        } else {
            throw new \Exception(__($responseObject->message));
        }
    }

    /**
     * Gets Capture transaction reference transaction ID
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return array
     * @throws \Exception
     */
    protected function getReferenceCaptureTxnId(\Magento\Payment\Model\InfoInterface $payment)
    {
        $authTransaction = $this->getModuleHelper()->lookUpAuthorizationTransaction(
            $payment
        );

        if (!isset($authTransaction)) {
            $captureTransaction = $this->getModuleHelper()->lookUpCaptureTransaction(
                $payment
            );

            $order = $payment->getOrder();

            if (isset($captureTransaction)) {
                $errorMessage = 'Capture transaction for order #' .
                    $order->getIncrementId() .
                    ' in progress (Expecting Notification from the Payment Gateway)';
            } else {
                $errorMessage = 'Capture transaction for order #' .
                    $order->getIncrementId() .
                    ' cannot be finished (No Authorize Transaction exists)';
            }

            throw new \Exception(__($errorMessage));
        }

        $this->getModuleHelper()->setTokenByPaymentTransaction(
            $authTransaction
        );

        return $authTransaction->getTxnId();
    }

    /**
     * Gets Refund transaction reference transaction ID
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return array
     * @throws \Exception
     */
    protected function getReferenceRefundTxnId(\Magento\Payment\Model\InfoInterface $payment)
    {
        $captureTransaction = $this->getModuleHelper()->lookUpCaptureTransaction(
            $payment
        );

        if (!isset($captureTransaction)) {
            $order = $payment->getOrder();

            $errorMessage = 'Refund transaction for order #' .
                $order->getIncrementId() .
                ' cannot be finished (No Capture Transaction exists)';

            throw new \Exception(__($errorMessage));
        }

        if (!$this->getModuleHelper()->canRefundTransaction($captureTransaction)) {
            $errorMessage = sprintf(
                "Order with transaction type \"%s\" cannot be refunded online." . PHP_EOL .
                "For further Information please contact your Account Manager." . PHP_EOL .
                "For more complex workflows/functionality, please visit our Merchant Portal!",
                $this->getModuleHelper()->getTransactionTypeByTransaction(
                    $captureTransaction
                )
            );

            throw new \Exception(__($errorMessage));
        }

        if (!$this->getModuleHelper()->setTokenByPaymentTransaction($captureTransaction)) {
            $authTransaction = $this->getModuleHelper()->lookUpAuthorizationTransaction(
                $payment
            );

            $this->getModuleHelper()->setTokenByPaymentTransaction(
                $authTransaction
            );
        }

        return $captureTransaction->getTxnId();
    }

    /**
     * Gets Void transaction reference transaction ID
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return array
     * @throws \Exception
     */
    protected function getReferenceVoidTxnId(\Magento\Payment\Model\InfoInterface $payment)
    {
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
            $order = $payment->getOrder();

            $errorMessage = 'Void transaction for order #' .
                $order->getIncrementId() .
                ' cannot be finished (No Authorize / Capture Transaction exists)';

            throw new \Exception(__($errorMessage));
        }

        $this->getModuleHelper()->setTokenByPaymentTransaction(
            $authTransaction
        );

        return $referenceTransaction->getTxnId();
    }

    /**
     * Sets the 3D-Secure redirect URL or throws an exception on failure
     *
     * @param string $redirectUrl
     * @throws \Exception
     */
    public function setRedirectUrl($redirectUrl)
    {
        if (!isset($redirectUrl)) {
            throw new \Exception(__('Empty 3D-Secure redirect URL'));
        }

        if (filter_var($redirectUrl, FILTER_VALIDATE_URL) === false) {
            throw new \Exception(__('Invalid 3D-Secure redirect URL'));
        }

        $this->getCheckoutSession()->setEmerchantPayCheckoutRedirectUrl($redirectUrl);
    }

    /**
     * Unsets the 3D-Secure redirect URL
     */
    public function unsetRedirectUrl()
    {
        $this->getCheckoutSession()->setEmerchantPayCheckoutRedirectUrl(null);
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
            $this->getConfigHelper()->isMethodAvailable() &&
            $this->getModuleHelper()->isStoreSecure();
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
