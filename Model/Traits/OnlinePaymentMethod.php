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

use EMerchantPay\Genesis\Helper\Data;
use EMerchantPay\Genesis\Model\Config;
use Exception;
use Genesis\Api\Constants\Transaction\States;
use Genesis\Api\Constants\Transaction\Types;
use Genesis\Exceptions\DeprecatedMethod;
use Genesis\Exceptions\ErrorParameter;
use Genesis\Exceptions\InvalidArgument;
use Genesis\Exceptions\InvalidMethod;
use Genesis\Exceptions\InvalidResponse;
use Genesis\Genesis;
use Genesis\Utils\Common;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Exception as WebApiException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Trait for defining common variables and methods for all Payment Solutions
 *
 * Trait OnlinePaymentMethod
 */
trait OnlinePaymentMethod
{
    /**
     * @var Config
     */
    protected $_configHelper;
    /**
     * @var Data
     */
    protected $_moduleHelper;
    /**
     * @var Context
     */
    protected $_actionContext;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;
    /**
     * @var Session
     */
    protected $_checkoutSession;
    /**
     * @var Transaction\ManagerInterface
     */
    protected $_transactionManager;

    /**
     * Get an Instance of the Config Helper Object
     *
     * @return Config
     */
    public function getConfigHelper()
    {
        return $this->_configHelper;
    }

    /**
     * Get an Instance of the Module Helper Object
     *
     * @return Data
     */
    public function getModuleHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * Get an Instance of the Magento Action Context
     *
     * @return Context
     */
    protected function getActionContext()
    {
        return $this->_actionContext;
    }

    /**
     * Get an Instance of the Magento Core Message Manager
     *
     * @return ManagerInterface
     */
    protected function getMessageManager()
    {
        return $this->getActionContext()->getMessageManager();
    }

    /**
     * Get an Instance of Magento Core Store Manager Object
     *
     * @return StoreManagerInterface
     */
    protected function getStoreManager()
    {
        return$this->_storeManager;
    }

    /**
     * Get an Instance of the Url
     *
     * @return UrlInterface
     */
    protected function getUrlBuilder()
    {
        return $this->_urlBuilder;
    }

    /**
     * Get an Instance of the Magento Core Checkout Session
     *
     * @return Session
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * Get an Instance of the Magento Transaction Manager
     *
     * @return Transaction\ManagerInterface
     */
    protected function getTransactionManager()
    {
        return $this->_transactionManager;
    }

    /**
     * Get custom Logger
     *
     * @return LoggerInterface
     */
    abstract protected function getLogger();

    /**
     * Initiate a Payment Gateway Reference Transaction
     *      - Capture
     *      - Refund
     *      - Void
     *
     * @param string        $transactionClass
     * @param InfoInterface $payment
     * @param array         $data
     *
     * @return stdClass
     *
     * @throws DeprecatedMethod
     * @throws InvalidArgument
     * @throws InvalidMethod
     * @throws InvalidResponse
     * @throws ErrorParameter
     * @throws Exception
     */
    protected function processReferenceTransaction(
        $transactionClass,
        InfoInterface $payment,
        $data
    ) {

        $this->getConfigHelper()->initGatewayClient();

        $genesis = new Genesis($transactionClass);

        foreach ($data as $key => $value) {
            $methodName = sprintf(
                "set%s",
                Common::snakeCaseToCamelCase(
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
        if (!$genesis->response()->isSuccessful()) {
            throw new Exception($genesis->response()->getErrorDescription());
        }

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
            ->resetTransactionAdditionalInfo();

        $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
            $payment,
            $responseObject
        );

        $this->_paymentRepository->save($payment);

        return $responseObject;
    }

    /**
     * Base Payment Capture Method
     *
     * @param InfoInterface    $payment
     * @param float            $amount
     * @param Transaction|null $authTransaction
     *
     * @return $this
     *
     * @throws WebApiException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function doCapture(InfoInterface $payment, $amount, $authTransaction)
    {
        /** @var Order $order */
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

        if ($responseObject->status == States::APPROVED) {
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
     * @param InfoInterface $payment
     * @param float $amount
     * @param Transaction|null $captureTransaction
     *
     * @return $this
     *
     * @throws DeprecatedMethod
     * @throws ErrorParameter
     * @throws InvalidArgument
     * @throws InvalidMethod
     * @throws InvalidResponse
     * @throws WebApiException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function doRefund(InfoInterface $payment, $amount, $captureTransaction)
    {
        /** @var Order $order */
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

        switch ($responseObject->status ?? null) {
            case States::PENDING_ASYNC:
                $this->getMessageManager()->addNoticeMessage(
                    __('Pending approval') .
                    (isset($responseObject->message) ? ' (' . $responseObject->message . ')' : '')
                );
                $this->getModuleHelper()->throwWebApiException(
                    __('Credit Memo is not created! The Refund is pending approval on the Gateway.')
                );
                break;
            case States::APPROVED:
                $this->getMessageManager()->addSuccessMessage(
                    __('Successful Refund') .
                    (isset($responseObject->message) ? ' (' . $responseObject->message . ')' : '')
                );
                break;
            default:
                $this->getMessageManager()->addErrorMessage($responseObject->message);
                $this->getModuleHelper()->throwWebApiException(
                    $responseObject->message
                );
                break;
        }

        unset($data);

        return $this;
    }

    /**
     * Base Payment Void Method
     *
     * @param InfoInterface $payment
     * @param Transaction|null $authTransaction
     * @param Transaction|null $referenceTransaction
     *
     * @return $this
     *
     * @throws DeprecatedMethod
     * @throws ErrorParameter
     * @throws InvalidArgument
     * @throws InvalidMethod
     * @throws InvalidResponse
     * @throws WebApiException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function doVoid(InfoInterface $payment, $authTransaction, $referenceTransaction)
    {
        /** @var Order $order */

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

        if ($responseObject->status == States::APPROVED) {
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
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return $this
     *
     * @throws WebApiException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        /** @var Order $order */
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
        } catch (Exception $e) {
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
     *
     * @param InfoInterface $payment
     *
     * @return $this
     *
     * @throws WebApiException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function void(InfoInterface $payment)
    {
        /** @var Order $order */

        $order = $payment->getOrder();

        $orderIncrementId = $order->getIncrementId();

        $this->getLogger()->debug('Void transaction for order #' . $orderIncrementId);

        $referenceTransaction = $this->getModuleHelper()->lookUpVoidReferenceTransaction(
            $payment
        );

        if ($referenceTransaction->getTxnType() == Transaction::TYPE_AUTH) {
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
        } catch (Exception $e) {
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
     * @param InfoInterface $payment
     *
     * @return $this
     *
     * @throws WebApiException
     */
    public function cancel(InfoInterface $payment)
    {
        return $this->void($payment);
    }

    /**
     * Sets the 3D-Secure redirect URL or throws an exception on failure
     *
     * @param string $redirectUrl
     *
     * @throws Exception
     */
    public function setRedirectUrl($redirectUrl)
    {
        if (!isset($redirectUrl)) {
            throw new LocalizedException(
                __('Empty 3D-Secure redirect URL')
            );
        }

        if (filter_var($redirectUrl, FILTER_VALIDATE_URL) === false) {
            throw new LocalizedException(
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
