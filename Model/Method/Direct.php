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

namespace EMerchantPay\Genesis\Model\Method;

use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Direct Payment Method Model Class
 * Class Direct
 * @package EMerchantPay\Genesis\Model\Method
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Direct extends Base
{
    const CODE = 'emerchantpay_direct';

    /**
     * Direct Method Code
     */
    protected $_code = self::CODE;

    /**
     * Direct constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\App\Action\Context $actionContext
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \EMerchantPay\Genesis\Logger\Logger $loggerHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \EMerchantPay\Genesis\Helper\Data $moduleHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Action\Context $actionContext,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \EMerchantPay\Genesis\Logger\Logger $loggerHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \EMerchantPay\Genesis\Helper\Data $moduleHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $loggerHelper->setFilename('direct');

        parent::__construct(
            $context,
            $registry,
            $scopeConfig,
            $loggerHelper,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_actionContext   = $actionContext;
        $this->_storeManager    = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_moduleHelper    = $moduleHelper;
        $this->_configHelper    = $this->getModuleHelper()->getMethodConfig(
            $this->getCode()
        );
    }

    /**
     * Retrieves the Checkout Payment Action according to the
     * Module Transaction Type setting
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $transactionTypeActions = [
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE    =>
                \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE,
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D =>
                \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE,
            \Genesis\API\Constants\Transaction\Types::SALE         =>
                \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
            \Genesis\API\Constants\Transaction\Types::SALE_3D      =>
                \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
        ];

        $transactionType = $this->getConfigTransactionType();

        if (!array_key_exists($transactionType, $transactionTypeActions)) {
            $this->getModuleHelper()->throwWebApiException(
                sprintf(
                    'Transaction Type (%s) not supported yet',
                    $transactionType
                )
            );
        }

        return $transactionTypeActions[$transactionType];
    }

    /**
     * Retrieves the Module Transaction Type Setting
     *
     * @return string
     */
    public function getConfigTransactionType()
    {
        return $this->getConfigData('transaction_type');
    }

    /**
     * Check whether we're doing 3D transactions,
     * based on the module configuration
     *
     * @return bool
     */
    public function isThreeDEnabled()
    {
        return
            $this->getModuleHelper()->getIsTransactionThreeDSecure(
                $this->getConfigTransactionType()
            );
    }

    /**
     * Authorize payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     *
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this->processTransaction($payment, $amount);
    }

    /**
     * Capture payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     *
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $authTransaction = $this->getModuleHelper()->lookUpAuthorizationTransaction(
            $payment
        );

        /*
         * When no Auth then Process Sale / Sale3d
         * Note: this method is called when:
         *    - Capturing Payment in Admin Area
         *    - Doing a purchase when Payment Action is "ACTION_AUTHORIZE_CAPTURE"
         */
        if (!isset($authTransaction)) {
            return $this->processTransaction($payment, $amount);
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->getLogger()->debug('Capture transaction for order #' . $order->getIncrementId());

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
     * Assign data to info model instance
     *
     * @param \Magento\Framework\DataObject|mixed $data
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $info = $this->getInfoInstance();

        /*
         * Skip fix if CC Info already assigned (Magento 2.1.x)
         */
        if ($this->getInfoInstanceHasCcDetails($info)) {
            return $this;
        }

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_object($additionalData)) {
            $additionalData = new DataObject($additionalData ?: []);
        }

        /** @var DataObject $info */
        $info->addData(
            [
                'cc_type'           => $additionalData->getCcType(),
                'cc_owner'          => $additionalData->getCcOwner(),
                'cc_last_4'         => substr($additionalData->getCcNumber(), -4),
                'cc_number'         => $additionalData->getCcNumber(),
                'cc_cid'            => $additionalData->getCcCid(),
                'cc_exp_month'      => $additionalData->getCcExpMonth(),
                'cc_exp_year'       => $additionalData->getCcExpYear(),
                'cc_ss_issue'       => $additionalData->getCcSsIssue(),
                'cc_ss_start_month' => $additionalData->getCcSsStartMonth(),
                'cc_ss_start_year'  => $additionalData->getCcSsStartYear()
            ]
        );

        return $this;
    }

    /**
     * Determines if the CC Details are supplied to the Payment Info Instance
     *
     * @param \Magento\Payment\Model\InfoInterface $info
     *
     * @return bool
     */
    protected function getInfoInstanceHasCcDetails(\Magento\Payment\Model\InfoInterface $info)
    {
        return
            !empty($info->getCcNumber()) &&
            !empty($info->getCcCid()) &&
            !empty($info->getCcExpMonth()) &&
            !empty($info->getCcExpYear());
    }

    /**
     * Builds full Request Class Name by Transaction Type
     *
     * @param string $transactionType
     *
     * @return string
     */
    protected function getTransactionTypeRequestClassName($transactionType)
    {
        $requestClassName = ucfirst(
            str_replace('3d', '3D', $transactionType)
        );

        return "Financial\\Cards\\{$requestClassName}";
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
     *
     * @return $this
     * @throws \Exception
     * @throws \Genesis\Exceptions\ErrorAPI
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function processTransaction(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionType = $this->getConfigTransactionType();

        $isThreeDEnabled = $this->isThreeDEnabled();

        $order = $payment->getOrder();

        $helper = $this->getModuleHelper();

        $this->getConfigHelper()->initGatewayClient();

        $billing = $order->getBillingAddress();

        if (empty($billing)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Billing address is empty.'));
        }

        $shipping = $order->getShippingAddress();

        $genesis = new \Genesis\Genesis(
            $this->getTransactionTypeRequestClassName(
                $transactionType
            )
        );

        $orderId = ltrim(
            $order->getIncrementId(),
            '0'
        );

        $genesis
            ->request()
                ->setTransactionId(
                    $helper->genTransactionId($orderId)
                )
                ->setRemoteIp(
                    $order->getRemoteIp()
                )
                ->setUsage(
                    $helper->buildOrderDescriptionText($order)
                )
                ->setCurrency(
                    $order->getBaseCurrencyCode()
                )
                ->setAmount(
                    $amount
                )
                ->setUsage(
                    'Magento2 Payment'
                );

        if (!empty($payment->getCcOwner())) {
            $genesis
                ->request()
                    ->setCardHolder(
                        $payment->getCcOwner()
                    );
        } else {
            $genesis
                ->request()
                    ->setCardHolder(
                        $billing->getFirstname() . ' ' . $billing->getLastname()
                    );
        }

        $genesis
            ->request()
                ->setCardNumber(
                    $payment->getCcNumber()
                )
                ->setExpirationYear(
                    $payment->getCcExpYear()
                )
                ->setExpirationMonth(
                    $payment->getCcExpMonth()
                )
                ->setCvv(
                    $payment->getCcCid()
                )
                ->setCustomerEmail(
                    $order->getCustomerEmail()
                )
                ->setCustomerPhone(
                    $billing->getTelephone()
                )
                //Billing
                ->setBillingFirstName(
                    $billing->getFirstname()
                )
                ->setBillingLastName(
                    $billing->getLastname()
                )
                ->setBillingAddress1(
                    $billing->getStreetLine(1)
                )
                ->setBillingAddress2(
                    $billing->getStreetLine(2)
                )
                ->setBillingZipCode(
                    $billing->getPostcode()
                )
                ->setBillingCity(
                    $billing->getCity()
                )
                ->setBillingState(
                    $billing->getRegionCode()
                )
                ->setBillingCountry(
                    $billing->getCountryId()
                );

        if (!empty($shipping)) {
            $genesis
                ->request()
                    ->setShippingFirstName(
                        $shipping->getFirstname()
                    )
                    ->setShippingLastName(
                        $shipping->getLastname()
                    )
                    ->setShippingAddress1(
                        $shipping->getStreetLine(1)
                    )
                    ->setShippingAddress2(
                        $shipping->getStreetLine(2)
                    )
                    ->setShippingZipCode(
                        $shipping->getPostcode()
                    )
                    ->setShippingCity(
                        $shipping->getCity()
                    )
                    ->setShippingState(
                        $shipping->getRegionCode()
                    )
                    ->setShippingCountry(
                        $shipping->getCountryId()
                    );
        }

        if ($isThreeDEnabled) {
            $genesis
                ->request()
                    ->setNotificationUrl(
                        $helper->getNotificationUrl(
                            $this->getCode()
                        )
                    )
                    ->setReturnSuccessUrl(
                        $helper->getReturnUrl(
                            $this->getCode(),
                            \EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_SUCCESS
                        )
                    )
                    ->setReturnFailureUrl(
                        $helper->getReturnUrl(
                            $this->getCode(),
                            \EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_FAILURE
                        )
                    );
        }

        try {
            $genesis->execute();
        } catch (\Exception $e) {
            $logInfo =
                'Transaction ' . $transactionType .
                ' for order #' . $orderId .
                ' failed with message "' . $e->getMessage() . '"';

            $this->getLogger()->error($logInfo);

            $this->getCheckoutSession()->setEmerchantPayLastCheckoutError(
                $e->getMessage()
            );

            $this->getModuleHelper()->maskException($e);
        }

        $this->setGenesisResponse(
            $this->getModuleHelper()->getGatewayResponseObject(
                $genesis->response()
            )
        );

        $genesis_response = $this->getModuleHelper()->getArrayFromGatewayResponse(
            $this->getGenesisResponse()
        );

        $payment
            ->setTransactionId(
                $this->getGenesisResponse()->unique_id
            )
            ->setIsTransactionClosed(
                false
            )
            ->setIsTransactionPending(
                $isThreeDEnabled
            )
            ->setTransactionAdditionalInfo(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $genesis_response
            );

        $status = new \Genesis\API\Constants\Transaction\States(
            $this->getGenesisResponse()->status
        );

        $isTransactionFailed =
            !$status->isApproved() &&
            (!$status->isPendingAsync() || !isset($this->getGenesisResponse()->redirect_url));

        if ($isTransactionFailed) {
            $errorMessage = $this->getModuleHelper()->getErrorMessageFromGatewayResponse(
                $this->getGenesisResponse()
            );

            $this->getCheckoutSession()->setEmerchantPayLastCheckoutError(
                $errorMessage
            );

            $this->getModuleHelper()->throwWebApiException($errorMessage);
        }

        if ($isThreeDEnabled && $status->isPendingAsync()) {
            $this->setRedirectUrl(
                $this->getGenesisResponse()->redirect_url
            );
            $payment->setPreparedMessage('3D-Secure: Redirecting customer to a verification page.');
        } else {
            $this->unsetRedirectUrl();
        }

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD)
     * @codingStandardsIgnoreStart
     */
    public function validate()
    {
        /*
         * calling parent validate function
         */
        parent::validate();

        $info           = $this->getInfoInstance();
        $availableTypes = explode(',', $this->getConfigData('cctypes'));
        $ccNumber       = $info->getCcNumber();

        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);

        if (!$this->validateCcNumOther($ccNumber)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid Credit Card Number')
            );
        }

        if (!in_array($info->getCcType(), $availableTypes)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('This credit card type is not allowed for this payment method.')
            );
        }

        if (!$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please enter a valid credit card expiration date.')
            );
        }

        if (preg_match('/^\d+$/', $this->getCcNumber())) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid Card Verification Number')
            );
        }

        return $this;
        // @codingStandardsIgnoreEnd
    }

    /**
     * @return bool
     * @api
     */
    public function hasVerification()
    {
        $configData = $this->getConfigData('useccv');
        if ($configData === null) {
            return true;
        }

        return (bool)$configData;
    }

    /**
     * @param string $expYear
     * @param string $expMonth
     *
     * @return bool
     * @codingStandardsIgnoreStart
     */
    protected function _validateExpDate($expYear, $expMonth)
    {
        $date = new \DateTime();
        if (!$expYear || !$expMonth || (int)$date->format('Y') > $expYear
            || (int)$date->format('Y') == $expYear && (int)$date->format('m') > $expMonth
        ) {
            return false;
        }

        return true;
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param string $type
     *
     * @return bool
     * @api
     */
    public function otherCcType($type)
    {
        return $type == 'OT';
    }

    /**
     * Other credit cart type number validation
     *
     * @param string $ccNumber
     *
     * @return bool
     * @api
     */
    public function validateCcNumOther($ccNumber)
    {
        return preg_match('/^\\d+$/', $ccNumber);
    }

    /**
     * Determines method's availability based on config data and quote amount
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return $this->getConfigData('cctypes', $quote ? $quote->getStoreId() : null) &&
               parent::isAvailable($quote) &&
               $this->getConfigHelper()->isMethodAvailable() &&
               $this->getModuleHelper()->isStoreSecure();
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     *
     * @return bool|Base
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD)
     * @codingStandardsIgnoreStart
     */
    public function order(InfoInterface $payment, $amount)
    {
        if (!$this->canReviewPayment()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The order action is unavailable.'));
        }
        return false;
        // @codingStandardsIgnoreEnd
    }
}
