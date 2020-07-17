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

namespace EMerchantPay\Genesis\Model\Traits;

use Genesis\API\Constants\Transaction\Types;

/**
 * Trait for defining common variables and methods for all Payment Solutions
 * Trait OnlinePaymentMethod
 * @package EMerchantPay\Genesis\Model\Traits
 */
trait OnlinePaymentMethod
{
    /**
     * @var \EMerchantPay\Genesis\Model\Config
     */
    protected $_configHelper;
    /**
     * @var \EMerchantPay\Genesis\Helper\Data
     */
    protected $_moduleHelper;
    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $_actionContext;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface
     */
    protected $_transactionManager;

    /**
     * Get an Instance of the Config Helper Object
     * @return \EMerchantPay\Genesis\Model\Config
     */
    public function getConfigHelper()
    {
        return $this->_configHelper;
    }

    /**
     * Get an Instance of the Module Helper Object
     * @return \EMerchantPay\Genesis\Helper\Data
     */
    public function getModuleHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * Get an Instance of the Magento Action Context
     * @return \Magento\Framework\App\Action\Context
     */
    protected function getActionContext()
    {
        return $this->_actionContext;
    }

    /**
     * Get an Instance of the Magento Core Message Manager
     * @return \Magento\Framework\Message\ManagerInterface
     */
    protected function getMessageManager()
    {
        return $this->getActionContext()->getMessageManager();
    }

    /**
     * Get an Instance of Magento Core Store Manager Object
     * @return \Magento\Store\Model\StoreManagerInterface
     */
    protected function getStoreManager()
    {
        return$this->_storeManager;
    }

    /**
     * Get an Instance of the Url
     * @return \Magento\Framework\UrlInterface
     */
    protected function getUrlBuilder()
    {
        return $this->_urlBuilder;
    }

    /**
     * Get an Instance of the Magento Core Checkout Session
     * @return \Magento\Checkout\Model\Session
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * Get an Instance of the Magento Transaction Manager
     * @return \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface
     */
    protected function getTransactionManager()
    {
        return $this->_transactionManager;
    }

    /**
     * Get custom Logger
     * @return \Psr\Log\LoggerInterface
     */
    abstract protected function getLogger();

    /**
     * Initiate a Payment Gateway Reference Transaction
     *      - Capture
     *      - Refund
     *      - Void
     *
     * @param $transactionClass
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $data
     * @return \stdClass
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\ErrorAPI
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     * @throws \Genesis\Exceptions\InvalidResponse
     * @throws \Genesis\Exceptions\ErrorParameter
     */
    protected function processReferenceTransaction(
        $transactionClass,
        \Magento\Payment\Model\InfoInterface $payment,
        $data
    ) {

        $this->getConfigHelper()->initGatewayClient();

        $genesis = new \Genesis\Genesis($transactionClass);

        foreach ($data as $key => $value) {
            $methodName = sprintf(
                "set%s",
                \Genesis\Utils\Common::snakeCaseToCamelCase(
                    $key
                )
            );
            $genesis
                ->request()
                    ->{$methodName}(
                        $value
                    );
        }

        if (in_array(
            $transactionClass,
            [
                Types::getCaptureTransactionClass(Types::KLARNA_AUTHORIZE),
                Types::getRefundTransactionClass(Types::KLARNA_CAPTURE)
            ]
        )) {
            $genesis
                ->request()
                ->setItems(
                    $this->getModuleHelper()->getKlarnaCustomParamItems($payment->getOrder())
                );
        }

        $genesis->execute();

        $responseObject = $genesis->response()->getResponseObject();

        $payment
            ->setTransactionId(
                $responseObject->unique_id
            )
            ->setParentTransactionId(
                $data['reference_id']
            )
            ->setShouldCloseParentTransaction(
                true
            )
            ->setIsTransactionPending(
                false
            )
            ->setIsTransactionClosed(
                true
            )
            ->resetTransactionAdditionalInfo(

            );

        $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
            $payment,
            $responseObject
        );

        $payment->save();

        return $responseObject;
    }

    /**
     * Base Payment Capture Method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param \Magento\Sales\Model\Order\Payment\Transaction|null $authTransaction
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function doCapture(\Magento\Payment\Model\InfoInterface $payment, $amount, $authTransaction)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->getModuleHelper()->setTokenByPaymentTransaction(
            $authTransaction
        );

        $data = [
            'transaction_id' =>
                $this->getModuleHelper()->genTransactionId(),
            'remote_ip'      =>
                $order->getRemoteIp(),
            'reference_id'   =>
                $authTransaction->getTxnId(),
            'currency'       =>
                $order->getBaseCurrencyCode(),
            'amount'         =>
                $amount,
            'usage'          =>
                'Magento2 Capture'
        ];

        $transactionClass = Types::getCaptureTransactionClass(
            $this->getModuleHelper()->getTransactionTypeByTransaction($authTransaction)
        );

        $responseObject = $this->processReferenceTransaction(
            $transactionClass,
            $payment,
            $data
        );

        if ($responseObject->status == \Genesis\API\Constants\Transaction\States::APPROVED) {
            $this->getMessageManager()->addSuccess(
                __('Successful Capture') .
                (isset($responseObject->message) ? ' (' . $responseObject->message . ')' : '')
            );
        } else {
            $this->getModuleHelper()->throwWebApiException(
                $responseObject->message
            );
        }

        unset($data);

        return $this;
    }

    /**
     * Base Payment Refund Method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param \Magento\Sales\Model\Order\Payment\Transaction|null $captureTransaction
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function doRefund(\Magento\Payment\Model\InfoInterface $payment, $amount, $captureTransaction)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        if (!$this->getModuleHelper()->canRefundTransaction($captureTransaction)) {
            $errorMessage = sprintf(
                "Order with transaction type \"%s\" cannot be refunded online." . PHP_EOL .
                "For further Information please contact your Account Manager." . PHP_EOL .
                "For more complex workflows/functionality, please visit our Merchant Portal!",
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

        $data = [
            'transaction_id' =>
                $this->getModuleHelper()->genTransactionId(),
            'remote_ip'      =>
                $order->getRemoteIp(),
            'reference_id'   =>
                $captureTransaction->getTxnId(),
            'currency'       =>
                $order->getBaseCurrencyCode(),
            'amount'         =>
                $amount,
            'usage'          =>
                'Magento2 Refund'
        ];

        $transactionClass = Types::getRefundTransactionClass(
            $this->getModuleHelper()->getTransactionTypeByTransaction($captureTransaction)
        );

        $responseObject = $this->processReferenceTransaction(
            $transactionClass,
            $payment,
            $data
        );

        if ($responseObject->status == \Genesis\API\Constants\Transaction\States::APPROVED) {
            $this->getMessageManager()->addSuccess(
                __('Successful Refund') .
                (isset($responseObject->message) ? ' (' . $responseObject->message . ')' : '')
            );
        } else {
            $this->getMessageManager()->addError($responseObject->message);
            $this->getModuleHelper()->throwWebApiException(
                $responseObject->message
            );
        }

        unset($data);

        return $this;
    }

    /**
     * Base Payment Void Method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param \Magento\Sales\Model\Order\Payment\Transaction|null $authTransaction
     * @param \Magento\Sales\Model\Order\Payment\Transaction|null $referenceTransaction
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function doVoid(\Magento\Payment\Model\InfoInterface $payment, $authTransaction, $referenceTransaction)
    {
        /** @var \Magento\Sales\Model\Order $order */

        $order = $payment->getOrder();

        $this->getModuleHelper()->setTokenByPaymentTransaction(
            $authTransaction
        );

        $data = [
            'transaction_id' =>
                $this->getModuleHelper()->genTransactionId(),
            'remote_ip'      =>
                $order->getRemoteIp(),
            'reference_id'   =>
                $referenceTransaction->getTxnId(),
            'usage'          =>
                'Magento2 Void'
        ];

        $transactionClass = Types::getFinancialRequestClassForTrxType(Types::VOID);

        $responseObject = $this->processReferenceTransaction(
            $transactionClass,
            $payment,
            $data
        );

        if ($responseObject->status == \Genesis\API\Constants\Transaction\States::APPROVED) {
            $this->getMessageManager()->addSuccess(
                __('Successful Void') .
                (isset($responseObject->message) ? ' (' . $responseObject->message . ')' : '')
            );
        } else {
            $this->getModuleHelper()->throwWebApiException(
                $responseObject->message
            );
        }

        unset($data);

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

        try {
            $this->doRefund($payment, $amount, $captureTransaction);
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
     * Void Payment
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order $order */

        $order = $payment->getOrder();

        $orderIncrementId = $order->getIncrementId();

        $this->getLogger()->debug('Void transaction for order #' . $orderIncrementId);

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
                $orderIncrementId .
                ' cannot be finished (No Authorize / Capture Transaction exists)';

            $this->getLogger()->error($errorMessage);
            $this->getModuleHelper()->throwWebApiException($errorMessage);
        }

        try {
            $this->doVoid($payment, $authTransaction, $referenceTransaction);
        } catch (\Exception $e) {
            $this->getLogger()->error(
                $e->getMessage()
            );

            $this->getModuleHelper()->maskException($e);
        }

        return $this;
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
     * Sets the 3D-Secure redirect URL or throws an exception on failure
     *
     * @param string $redirectUrl
     * @throws \Exception
     */
    public function setRedirectUrl($redirectUrl)
    {
        if (!isset($redirectUrl)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Empty 3D-Secure redirect URL')
            );
        }

        if (filter_var($redirectUrl, FILTER_VALIDATE_URL) === false) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid 3D-Secure redirect URL')
            );
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
}
