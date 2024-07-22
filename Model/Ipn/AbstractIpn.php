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

namespace EMerchantPay\Genesis\Model\Ipn;

use EMerchantPay\Genesis\Helper\Data;
use Genesis\Api\Constants\Transaction\States;
use Genesis\Api\Constants\Transaction\Types as GenesisTransactionTypes;

/**
 * Base IPN Handler Class
 *
 * Class AbstractIpn
 * @package EMerchantPay\Genesis\Model\Ipn
 */
abstract class AbstractIpn
{

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $_context;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \EMerchantPay\Genesis\Helper\Data
     */
    protected $_moduleHelper;
    /**
     * @var \EMerchantPay\Genesis\Model\Config
     */
    protected $_configHelper;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;
    /**
     * @var array
     */
    protected $_ipnRequest;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender
     */
    protected $_creditMemoSender;

    /**
     * Get Payment Solution Code (used to create an instance of the Config Object)
     * @return string
     */
    abstract protected function getPaymentMethodCode();

    /**
     * Update / Create Transactions; Updates Order Status
     * @param \stdClass $responseObject
     * @return void
     */
    abstract protected function processNotification($responseObject);

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender $creditMemoSender
     * @param \Psr\Log\LoggerInterface $logger
     * @param \EMerchantPay\Genesis\Helper\Data $moduleHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender $creditMemoSender,
        \Psr\Log\LoggerInterface $logger,
        \EMerchantPay\Genesis\Helper\Data $moduleHelper,
        array $data = []
    ) {
        $this->_context = $context;
        $this->_orderFactory = $orderFactory;
        $this->_orderSender = $orderSender;
        $this->_creditMemoSender = $creditMemoSender;
        $this->_logger = $logger;
        $this->_moduleHelper = $moduleHelper;
        $this->_configHelper =
            $this->_moduleHelper->getMethodConfig(
                $this->getPaymentMethodCode()
            );
        $this->_ipnRequest = $data;
    }

    /**
     * Get IPN Post Request Params or Param Value
     * @param string|null $key
     * @return array|string|null
     */
    protected function getIpnRequestData($key = null)
    {
        if ($key == null) {
            return $this->_ipnRequest;
        }

        return isset($this->_ipnRequest->{$key}) ? $this->_ipnRequest->{$key} : null;
    }

    /**
     *
     * @return null|string (null => failed; responseText => success)
     * @throws \Exception
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handleGenesisNotification()
    {
        $this->_configHelper->initGatewayClient();

        $notification = $this->getModuleHelper()->createNotificationObject(
            $this->getIpnRequestData()
        );

        if ($notification->isAuthentic()) {
            $notification->initReconciliation();
        }

        $responseObject = $notification->getReconciliationObject();

        if (!isset($responseObject->unique_id)) {
            return null;
        }

        $this->setOrderByReconcile($responseObject);

        try {
            $this->processNotification($responseObject);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $comment = $this->createIpnComment(
                __('Note: %1', $e->getMessage()),
                true
            );
            $comment->save();
            throw $e;
        }

        return $notification->generateResponse();
    }

    /**
     * Load order
     *
     * @return \Magento\Sales\Model\Order
     * @throws \Exception
     */
    protected function getOrder()
    {
        if (!isset($this->_order) || empty($this->_order->getId())) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('IPN-Order is not set to an instance of an object')
            );
        }

        return $this->_order;
    }

    /**
     * Get an Instance of the Magento Payment Object
     * @return \Magento\Sales\Api\Data\OrderPaymentInterface|mixed|null
     * @throws \Exception
     */
    protected function getPayment()
    {
        return $this->getOrder()->getPayment();
    }

    /**
     * Initializes the Order Object from the transaction in the Reconcile response object
     * @param $responseObject
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function setOrderByReconcile($responseObject)
    {
        $transaction_id = $responseObject->transaction_id;
        list($incrementId, $hash) = explode('-', $transaction_id);

        $this->_order = $this->getOrderFactory()->create()->loadByIncrementId(
            (int) $incrementId
        );

        if (!$this->_order->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                sprintf('Wrong order ID: "%s".', $incrementId)
            );
        }
    }

    /**
     * Generate an "IPN" comment with additional explanation.
     * Returns the generated comment or order status history object
     *
     * @param string|null $message
     * @param bool $addToHistory
     * @return string|\Magento\Sales\Model\Order\Status\History
     */
    protected function createIpnComment($message = null, $addToHistory = false)
    {
        if ($addToHistory && !empty($message)) {
            $message = $this->getOrder()->addStatusHistoryComment($message);
            $message->setIsCustomerNotified(null);
        }
        return $message;
    }

    /**
     * Get an instance of the Module Config Helper Object
     * @return \EMerchantPay\Genesis\Model\Config
     */
    protected function getConfigHelper()
    {
        return $this->_configHelper;
    }

    /**
     * Get an instance of the Magento Action Context Object
     * @return \Magento\Framework\App\Action\Context
     */
    protected function getContext()
    {
        return $this->_context;
    }

    /**
     * Get an instance of the Magento Logger Interface
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Get an Instance of the Module Helper Object
     * @return \EMerchantPay\Genesis\Helper\Data
     */
    protected function getModuleHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * Get an Instance of the magento Order Factory Object
     * @return \Magento\Sales\Model\OrderFactory
     */
    protected function getOrderFactory()
    {
        return $this->_orderFactory;
    }

    /**
     * @param \stdClass $responseObject
     * @return bool
     */
    protected function getShouldSetCurrentTranPending($responseObject)
    {
        return $responseObject->status != States::APPROVED;
    }

    /**
     * @param \stdClass $responseObject
     * @return bool
     */
    protected function getShouldCloseCurrentTransaction($responseObject)
    {
        $voidableTransactions = [
            GenesisTransactionTypes::AUTHORIZE,
            GenesisTransactionTypes::AUTHORIZE_3D,
            GenesisTransactionTypes::GOOGLE_PAY,
            GenesisTransactionTypes::PAY_PAL,
            GenesisTransactionTypes::APPLE_PAY,
        ];

        if ($this->getModuleHelper()->isTransactionWithCustomAttribute($responseObject->transaction_type)) {
            return !$this->getModuleHelper()->isSelectedAuthorizePaymentType($responseObject->transaction_type);
        }

        return !in_array($responseObject->transaction_type, $voidableTransactions);
    }

    /**
     * Extract the Message from Genesis response
     *
     * @param \stdClass $transactionResponse
     *
     * @return string
     */
    protected function getTransactionMessage($transactionResponse)
    {
        $uniqueId          = $transactionResponse->unique_id;
        $transactionStatus = $transactionResponse->status;
        $additionalNotes   = isset($transactionResponse->message) ? "({$transactionResponse->message})" : '';
        $transactionType   = isset($transactionResponse->transaction_type) ?
            $transactionResponse->transaction_type : __('unknown');

        $messageArray = [
            __('Module'),
            $this->getConfigHelper()->getCheckoutTitle(),
            __('Notification Received'),
            'UniqueID',
            $uniqueId,
            __('Transaction type'),
            strtoupper($transactionType),
            ' - ',
            strtoupper($transactionStatus)
        ];

        if (!empty($additionalNotes)) {
            array_push($messageArray, $additionalNotes);
        }

        return implode(' ', $messageArray);
    }
}
