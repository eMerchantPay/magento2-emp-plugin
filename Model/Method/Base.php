<?php
/*
 * Copyright (C) 2025 emerchantpay Ltd.
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
 * @copyright   2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Model\Method;

use EMerchantPay\Genesis\Logger\Logger;
use EMerchantPay\Genesis\Model\Traits\OnlinePaymentMethod;
use EMerchantPay\Genesis\Model\Traits\PaymentMethodBehaviour;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Block\Form;
use Magento\Payment\Block\Info;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\PaymentMethodInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Base
 */
abstract class Base extends AbstractModel implements MethodInterface, PaymentMethodInterface
{
    use PaymentMethodBehaviour;
    use OnlinePaymentMethod;

    /**
     * @var string
     */
    protected $_code;

    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Logger
     */
    protected $_loggerHelper;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $_paymentRepository;

    /**
     * Base constructor.
     *
     * @param Context                         $context
     * @param Registry                        $registry
     * @param ScopeConfigInterface            $scopeConfig
     * @param Logger                          $loggerHelper
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param AbstractResource|null           $resource
     * @param AbstractDb|null                 $resourceCollection
     * @param array                           $data
     */
    public function __construct(
        Context                         $context,
        Registry                        $registry,
        ScopeConfigInterface            $scopeConfig,
        Logger                          $loggerHelper,
        OrderPaymentRepositoryInterface $paymentRepository,
        AbstractResource|null           $resource = null,
        AbstractDb|null                 $resourceCollection = null,
        array                           $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_eventManager      = $context->getEventDispatcher();
        $this->_scopeConfig       = $scopeConfig;
        $this->_loggerHelper      = $loggerHelper;
        $this->_paymentRepository = $paymentRepository;
    }

    /**
     * Get custom Logger
     *
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->_loggerHelper->getLogger();
    }

    /**
     * Get method code
     *
     * @return string
     *
     * @throws LocalizedException
     */
    public function getCode()
    {
        if (empty($this->_code)) {
            throw new LocalizedException(
                __('We cannot retrieve the payment method code.')
            );
        }

        return $this->_code;
    }

    /**
     * Get method title
     *
     * @return mixed|string
     *
     * @throws LocalizedException
     */
    public function getTitle()
    {
        return $this->getConfigData('title');
    }

    /**
     * Get form class
     *
     * @return string
     */
    public function getFormBlockType()
    {
        return Form::class;
    }

    /**
     * Set store id
     *
     * @param int $storeId
     */
    public function setStore($storeId)
    {
        $this->setData('store', (int)$storeId);
    }

    /**
     * Get store id
     *
     * @return int|mixed
     */
    public function getStore()
    {
        return $this->getData('store');
    }

    /**
     * Get Info class
     *
     * @return string
     */
    public function getInfoBlockType()
    {
        return Info::class;
    }

    /**
     * Get Info instance
     *
     * @return InfoInterface|mixed
     *
     * @throws LocalizedException
     */
    public function getInfoInstance()
    {
        $instance = $this->getData('info_instance');
        if (!$instance instanceof InfoInterface) {
            throw new LocalizedException(
                __('We cannot retrieve the payment information object instance.')
            );
        }

        return $instance;
    }

    /**
     * Set Info instance
     *
     * @param InfoInterface $info
     *
     * @return void
     */
    public function setInfoInstance(InfoInterface $info)
    {
        $this->setData('info_instance', $info);
    }

    /**
     * Get self instance
     *
     * @return $this|MethodInterface
     */
    public function validate()
    {
        return $this;
    }

    /**
     * Authorize payment for a specified amount
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return bool|MethodInterface
     *
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD)
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canReviewPayment()) {
            throw new LocalizedException(__('The authorize action is unavailable.'));
        }

        return false;
    }

    /**
     * Get the configuration data
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return mixed
     *
     * @throws LocalizedException
     */
    public function getConfigData($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/' . $this->getCode() . '/' . $field;

        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Assign credit card data
     *
     * @param DataObject $data
     *
     * @return $this|MethodInterface
     *
     * @throws LocalizedException
     */
    public function assignData(DataObject $data)
    {
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_object($additionalData)) {
            $additionalData = new DataObject($additionalData ?: []);
        }

        /** @var DataObject $info */
        $info = $this->getInfoInstance();
        $info->addData(
            [
                'cc_type'           => $additionalData->getCcType(),
                'cc_owner'          => $additionalData->getCcOwner(),
                'cc_last_4'         => $additionalData->getCcNumber() ?
                    substr($additionalData->getCcNumber(), -4) : '',
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
     * Is the payment method available
     *
     * @param CartInterface|null $quote
     *
     * @return bool|mixed
     *
     * @throws LocalizedException
     */
    public function isAvailable(CartInterface|null $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();
        $checkResult->setData('is_available', true);

        // for future use in observers
        $this->_eventManager->dispatch(
            'payment_method_is_active',
            [
                'result'          => $checkResult,
                'method_instance' => $this,
                'quote'           => $quote
            ]
        );

        return $checkResult->getData('is_available');
    }

    /**
     * Is the payment method active
     *
     * @param int|null $storeId
     *
     * @return bool
     *
     * @throws LocalizedException
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
    }
}
