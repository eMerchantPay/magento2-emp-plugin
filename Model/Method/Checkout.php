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

use Genesis\API\Constants\Transaction\Parameters\PayByVouchers\CardTypes;
use Genesis\API\Constants\Transaction\Parameters\PayByVouchers\RedeemTypes;
use Genesis\API\Constants\Transaction\States;
use Genesis\API\Constants\Transaction\Types as GenesisTransactionTypes;
use Genesis\API\Constants\Payment\Methods as GenesisPaymentMethods;
use Genesis\API\Request;
use Genesis\Genesis;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Checkout Payment Method Model Class
 * Class Checkout
 * @package EMerchantPay\Genesis\Model\Method
 */
class Checkout extends Base
{

    const CODE                     = 'emerchantpay_checkout';
    const CUSTOMER_CONSUMER_ID_KEY = 'consumer_id';

    /**
     * Checkout Method Code
     */
    protected $_code = self::CODE;

    /**
     * Checkout constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\App\Action\Context $actionContext
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \EMerchantPay\Genesis\Logger\Logger $loggerHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \EMerchantPay\Genesis\Helper\Data $moduleHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
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
        \EMerchantPay\Genesis\Logger\Logger  $loggerHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \EMerchantPay\Genesis\Helper\Data $moduleHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $loggerHelper->setFilename('checkout');

        parent::__construct(
            $context,
            $registry,
            $scopeConfig,
            $loggerHelper,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_actionContext               = $actionContext;
        $this->_storeManager                = $storeManager;
        $this->_checkoutSession             = $checkoutSession;
        $this->_moduleHelper                = $moduleHelper;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_configHelper                =
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
        $processed_list = [];

        $selected_types = $this->getConfigHelper()->getTransactionTypes();

        $alias_map = [
            GenesisPaymentMethods::EPS         => GenesisTransactionTypes::PPRO,
            GenesisPaymentMethods::GIRO_PAY    => GenesisTransactionTypes::PPRO,
            GenesisPaymentMethods::PRZELEWY24  => GenesisTransactionTypes::PPRO,
            GenesisPaymentMethods::QIWI        => GenesisTransactionTypes::PPRO,
            GenesisPaymentMethods::SAFETY_PAY  => GenesisTransactionTypes::PPRO,
            GenesisPaymentMethods::TRUST_PAY   => GenesisTransactionTypes::PPRO,
            GenesisPaymentMethods::BCMC        => GenesisTransactionTypes::PPRO,
            GenesisPaymentMethods::MYBANK      => GenesisTransactionTypes::PPRO
        ];

        foreach ($selected_types as $selected_type) {
            if (!array_key_exists($selected_type, $alias_map)) {
                $processed_list[] = $selected_type;

                continue;
            }

            $transaction_type = $alias_map[$selected_type];

            $processed_list[$transaction_type]['name'] = $transaction_type;

            $processed_list[$transaction_type]['parameters'][] = [
                'payment_method' => $selected_type
            ];
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
        $genesis = $this->prepareGenesisWPFRequest($data);

        $this->prepareTransactionTypes(
            $genesis->request(),
            $data
        );

        $this->getModuleHelper()->executeGatewayRequest($genesis);

        return $this->getModuleHelper()->getGatewayResponseObject(
            $genesis->response()
        );
    }

    /**
     * @param Request $request
     * @param array $data
     */
    protected function prepareTransactionTypes($request, $data)
    {
        $types = $this->getCheckoutTransactionTypes();

        foreach ($types as $transactionType) {
            if (is_array($transactionType)) {
                $request->addTransactionType(
                    $transactionType['name'],
                    $transactionType['parameters']
                );

                continue;
            }

            switch ($transactionType) {
                case GenesisTransactionTypes::PAYBYVOUCHER_SALE:
                    $parameters = [
                        'card_type'   => CardTypes::VIRTUAL,
                        'redeem_type' => RedeemTypes::INSTANT
                    ];
                    break;
                case GenesisTransactionTypes::PAYBYVOUCHER_YEEPAY:
                    $parameters = [
                        'card_type'        => CardTypes::VIRTUAL,
                        'redeem_type'      => RedeemTypes::INSTANT,
                        'product_name'     => $data['order']['description'],
                        'product_category' => $data['order']['description']
                    ];
                    break;
                case GenesisTransactionTypes::CITADEL_PAYIN:
                    $parameters = [
                        'merchant_customer_id' => $this->getModuleHelper()->getCurrentUserIdHash()
                    ];
                    break;
                case GenesisTransactionTypes::IDEBIT_PAYIN:
                case GenesisTransactionTypes::INSTA_DEBIT_PAYIN:
                    $parameters = [
                        'customer_account_id' => $this->getModuleHelper()->getCurrentUserIdHash()
                    ];
                    break;
            }

            if (!isset($parameters)) {
                $parameters = [];
            }

            $request->addTransactionType(
                $transactionType,
                $parameters
            );
        }
    }

    /**
     * Prepares Genesis Request with basic request data
     *
     * @param array $data
     *
     * @return Genesis
     * @throws \Genesis\Exceptions\InvalidMethod
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function prepareGenesisWPFRequest($data)
    {
        $genesis = new \Genesis\Genesis('WPF\Create');

        $genesis
            ->request()
                ->setTransactionId(
                    $data['transaction_id']
                )
                ->setCurrency(
                    $data['order']['currency']
                )
                ->setAmount(
                    $data['order']['amount']
                )
                ->setUsage(
                    $data['order']['usage']
                )
                ->setDescription(
                    $data['order']['description']
                )
                ->setCustomerPhone(
                    (string) $data['order']['billing']->getTelephone()
                )
                ->setCustomerEmail(
                    (string) $data['order']['customer']['email']
                )
                ->setLanguage(
                    $data['order']['language']
                );

        if ($this->getConfigHelper()->isTokenizationEnabled()) {
            $this->prepareTokenization(
                $genesis,
                $data['order']['customer']['id'],
                $data['order']['customer']['email']
            );
        }

        $genesis
            ->request()
                ->setNotificationUrl(
                    $data['urls']['notify']
                )
                ->setReturnSuccessUrl(
                    $data['urls']['return_success']
                )
                ->setReturnFailureUrl(
                    $data['urls']['return_failure']
                )
                ->setReturnCancelUrl(
                    $data['urls']['return_cancel']
                );

        $genesis
            ->request()
                ->setBillingFirstName(
                    (string) $data['order']['billing']->getFirstname()
                )
                ->setBillingLastName(
                    (string) $data['order']['billing']->getLastname()
                )
                ->setBillingAddress1(
                    (string) $data['order']['billing']->getStreetLine(1)
                )
                ->setBillingAddress2(
                    (string) $data['order']['billing']->getStreetLine(2)
                )
                ->setBillingZipCode(
                    (string) $data['order']['billing']->getPostcode()
                )
                ->setBillingCity(
                    (string) $data['order']['billing']->getCity()
                )
                ->setBillingState(
                    (string) $data['order']['billing']->getRegionCode()
                )
                ->setBillingCountry(
                    (string) $data['order']['billing']->getCountryId()
                );

        if (!empty($data['order']['shipping'])) {
            $genesis
                ->request()
                    ->setShippingFirstName(
                        (string) $data['order']['shipping']->getFirstname()
                    )
                    ->setShippingLastName(
                        (string) $data['order']['shipping']->getLastname()
                    )
                    ->setShippingAddress1(
                        (string) $data['order']['shipping']->getStreetLine(1)
                    )
                    ->setShippingAddress2(
                        (string) $data['order']['shipping']->getStreetLine(2)
                    )
                    ->setShippingZipCode(
                        (string) $data['order']['shipping']->getPostcode()
                    )
                    ->setShippingCity(
                        (string) $data['order']['shipping']->getCity()
                    )
                    ->setShippingState(
                        (string) $data['order']['shipping']->getRegionCode()
                    )
                    ->setShippingCountry(
                        (string) $data['order']['shipping']->getCountryId()
                    );
        }

        return $genesis;
    }

    /**
     * @param $customerId
     * @param $customerEmail
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getGatewayConsumerIdFor($customerId, $customerEmail)
    {
        if ($customerId === null) {
            return null;
        }
        $customer = $this->_customerRepositoryInterface->getById($customerId);
        $attr = $this->getCustomAttributeConsumerId($customer);

        return !empty($attr[$customerEmail]) ? $attr[$customerEmail] : null;
    }

    /**
     * @param CustomerInterface $customer
     *
     * @return array
     */
    protected function getCustomAttributeConsumerId(CustomerInterface $customer)
    {
        $attr = $customer->getCustomAttribute(self::CUSTOMER_CONSUMER_ID_KEY);
        if (empty($attr)) {
            return [];
        }
        $attr = json_decode($attr->getValue(), true);

        return is_array($attr) ? $attr : [];
    }

    /**
     * @param $customerId
     * @param $customerEmail
     * @param $consumerId
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    protected function setGatewayConsumerIdFor($customerId, $customerEmail, $consumerId)
    {
        if ($customerId === null) {
            return;
        }
        $customer = $this->_customerRepositoryInterface->getById($customerId);

        $attr = $this->getCustomAttributeConsumerId($customer);
        $attr[$customerEmail] = $consumerId;

        $customer->setCustomAttribute(self::CUSTOMER_CONSUMER_ID_KEY, json_encode($attr));

        $this->_customerRepositoryInterface->save($customer);
    }

    /**
     * @param Genesis $genesis
     * @param $customerId
     * @param $customerEmail
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function prepareTokenization(Genesis $genesis, $customerId, $customerEmail)
    {
        $consumerId = $this->getGatewayConsumerIdFor($customerId, $customerEmail);

        if (empty($consumerId)) {
            $consumerId = $this->retrieveConsumerIdFromEmail($customerEmail);
        }

        if ($consumerId) {
            $genesis->request()->setConsumerId($consumerId);
        }

        $genesis->request()->setRememberCard(true);
    }

    /**
     * @param string $email
     *
     * @return null|int
     */
    protected function retrieveConsumerIdFromEmail($email)
    {
        try {
            $genesis = new Genesis('NonFinancial\Consumers\Retrieve');
            $genesis->request()->setEmail($email);

            $genesis->execute();

            $response = $genesis->response()->getResponseObject();

            if ($this->isErrorResponse($response)) {
                return null;
            }

            return $response->consumer_id;
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param $response
     *
     * @return bool
     */
    protected function isErrorResponse($response)
    {
        $state = new States($response->status);

        return $state->isError();
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

        // @codingStandardsIgnoreStart
        $data = [
            'transaction_id' =>
                $this->getModuleHelper()->genTransactionId(
                    $orderId
                ),
            'transaction_types' =>
                $this->getConfigHelper()->getTransactionTypes(),
            'order' => [
                'currency' => $order->getBaseCurrencyCode(),
                'language' => $this->getModuleHelper()->getLocale(),
                'amount' => $amount,
                'usage' => $this->getModuleHelper()->buildOrderUsage(),
                'description' => $this->getModuleHelper()->buildOrderDescriptionText(
                    $order
                ),
                'customer' => [
                    'id'    => $order->getCustomerId(),
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
                        \EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_SUCCESS
                    ),
                'return_cancel'  =>
                    $this->getModuleHelper()->getReturnUrl(
                        $this->getCode(),
                        \EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_CANCEL
                    ),
                'return_failure' =>
                    $this->getModuleHelper()->getReturnUrl(
                        $this->getCode(),
                        \EMerchantPay\Genesis\Helper\Data::ACTION_RETURN_FAILURE
                    ),
            ]
        ];
        // @codingStandardsIgnoreEnd

        $this->getConfigHelper()->initGatewayClient();

        try {
            $responseObject = $this->checkout($data);

            $isWpfSuccessful =
                ($responseObject->status == \Genesis\API\Constants\Transaction\States::NEW_STATUS) &&
                isset($responseObject->redirect_url);

            if (!$isWpfSuccessful) {
                $errorMessage = $this->getModuleHelper()->getErrorMessageFromGatewayResponse(
                    $responseObject
                );

                $this->getCheckoutSession()->setEmerchantPayLastCheckoutError(
                    $errorMessage
                );

                $this->getModuleHelper()->throwWebApiException($errorMessage);
            }

            $payment->setTransactionId($responseObject->unique_id);
            $payment->setIsTransactionPending(true);
            $payment->setIsTransactionClosed(false);

            $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
                $payment,
                $responseObject
            );

            if (!empty($responseObject->consumer_id)) {
                $this->setGatewayConsumerIdFor(
                    $data['order']['customer']['id'],
                    $data['order']['customer']['email'],
                    $responseObject->consumer_id
                );
            }

            $this->setRedirectUrl(
                $responseObject->redirect_url
            );

            return $this;
        } catch (\Exception $e) {
            $this->getLogger()->error(
                $e->getMessage()
            );

            $this->getCheckoutSession()->setEmerchantPayLastCheckoutError(
                $e->getMessage()
            );

            $this->getModuleHelper()->maskException($e);
        }

        return $this;
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
     * Determines method's availability based on config data and quote amount
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote) &&
            $this->getConfigHelper()->isMethodAvailable();
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
