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

namespace EMerchantPay\Genesis\Model\Method;

use EMerchantPay\Genesis\Helper\Data;
use EMerchantpay\Genesis\Helper\Threeds;
use Exception;
use Genesis\Api\Constants\Payment\Methods as GenesisPaymentMethods;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\PasswordChangeIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\DeliveryTimeframes;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\Purchase\Categories;
use Genesis\Api\Constants\Transaction\States;
use Genesis\Api\Constants\Transaction\Types as GenesisTransactionTypes;
use Genesis\Api\Request;
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
     * @var Threeds
     */
    protected $_threedsHelper;

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
     * @param Threeds $threedsHelper
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
        Threeds $threedsHelper,
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
        $this->_threedsHelper               = $threedsHelper;
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
        $alias_map      = [];

        $selected_types = $this->getSelectedTransactionTypes();
        $ppro_suffix    = Data::PPRO_TRANSACTION_SUFFIX;
        $methods        = GenesisPaymentMethods::getMethods();

        foreach ($methods as $method) {
            $alias_map[$method . $ppro_suffix] = GenesisTransactionTypes::PPRO;
        }

        $alias_map = array_merge($alias_map, [
            Data::GOOGLE_PAY_TRANSACTION_PREFIX . Data::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE =>
                GenesisTransactionTypes::GOOGLE_PAY,
            Data::GOOGLE_PAY_TRANSACTION_PREFIX . Data::GOOGLE_PAY_PAYMENT_TYPE_SALE      =>
                GenesisTransactionTypes::GOOGLE_PAY,
            Data::PAYPAL_TRANSACTION_PREFIX . Data::PAYPAL_PAYMENT_TYPE_AUTHORIZE         =>
                GenesisTransactionTypes::PAY_PAL,
            Data::PAYPAL_TRANSACTION_PREFIX . Data::PAYPAL_PAYMENT_TYPE_SALE              =>
                GenesisTransactionTypes::PAY_PAL,
            Data::PAYPAL_TRANSACTION_PREFIX . Data::PAYPAL_PAYMENT_TYPE_EXPRESS           =>
                GenesisTransactionTypes::PAY_PAL,
            Data::APPLE_PAY_TRANSACTION_PREFIX . Data::APPLE_PAY_PAYMENT_TYPE_AUTHORIZE   =>
                GenesisTransactionTypes::APPLE_PAY,
            Data::APPLE_PAY_TRANSACTION_PREFIX . Data::APPLE_PAY_PAYMENT_TYPE_SALE        =>
                GenesisTransactionTypes::APPLE_PAY,
        ]);

        foreach ($selected_types as $selected_type) {
            if (!array_key_exists($selected_type, $alias_map)) {
                $processed_list[] = $selected_type;

                continue;
            }

            $transaction_type = $alias_map[$selected_type];

            $processed_list[$transaction_type]['name'] = $transaction_type;

            // WPF Custom Attribute
            $key = $this->getCustomParameterKey($transaction_type);

            $processed_list[$transaction_type]['parameters'][] = [
                $key => str_replace(
                    [
                        $ppro_suffix,
                        Data::GOOGLE_PAY_TRANSACTION_PREFIX,
                        Data::PAYPAL_TRANSACTION_PREFIX,
                        Data::APPLE_PAY_TRANSACTION_PREFIX,
                    ],
                    '',
                    $selected_type
                )
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
        $genesis = $this->prepareGenesisWpfRequest($data);

        if ($this->_configHelper->isThreedsAllowed()) {
            $this->prepareThreedsV2Parameters($genesis, $data);
        }

        $this->prepareTransactionTypes(
            $genesis->request(),
            $data
        );

        $this->addScaParameters($genesis);

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
                case GenesisTransactionTypes::IDEBIT_PAYIN:
                case GenesisTransactionTypes::INSTA_DEBIT_PAYIN:
                    $parameters = [
                        'customer_account_id' => $this->getModuleHelper()->getCurrentUserIdHash()
                    ];
                    break;
                case GenesisTransactionTypes::KLARNA_AUTHORIZE:
                    $itemsObject = $this->getModuleHelper()->getKlarnaCustomParamItems($data['order']['orderObject']);
                    $parameters = $itemsObject->toArray();
                    break;
                case GenesisTransactionTypes::TRUSTLY_SALE:
                    $helper        = $this->getModuleHelper();
                    $userId        = $helper->getCurrentUserId();
                    $trustlyUserId = empty($userId) ? $helper->getCurrentUserIdHash() : $userId;

                    $parameters = [
                        'user_id' => $trustlyUserId
                    ];
                    break;
                case GenesisTransactionTypes::ONLINE_BANKING_PAYIN:
                    $parameters['bank_codes'] = array_map(
                        function ($value) {
                            return ['bank_code' => $value];
                        },
                        $this->getConfigHelper()->getBankCodes()
                    );
                    break;
                case GenesisTransactionTypes::PAYSAFECARD:
                    $helper        = $this->getModuleHelper();
                    $userId        = $helper->getCurrentUserId();
                    $customerId = empty($userId) ? $helper->getCurrentUserIdHash() : $userId;

                    $parameters = [
                        'customer_id' => $customerId
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
            unset($parameters);
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
    protected function prepareGenesisWpfRequest($data)
    {
        $genesis = new \Genesis\Genesis('Wpf\Create');

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
                ->setReturnPendingUrl(
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
        } catch (Exception $exception) {
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
                    $order->getShippingAddress(),
                'orderObject' => $order
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

            $isWpfSuccessful = ($responseObject->status == States::NEW_STATUS) && isset($responseObject->redirect_url);

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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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

    /**
     * Returns payment method/type based on transaction type
     *
     * @param string $transactionType Transaction type
     *
     * @return string
     */
    protected function getCustomParameterKey($transactionType)
    {
        switch ($transactionType) {
            case GenesisTransactionTypes::PPRO:
                $result = 'payment_method';
                break;
            case GenesisTransactionTypes::PAY_PAL:
                $result = 'payment_type';
                break;
            case GenesisTransactionTypes::GOOGLE_PAY:
            case GenesisTransactionTypes::APPLE_PAY:
                $result = 'payment_subtype';
                break;
            default:
                $result = 'unknown';
        }

        return $result;
    }

    /**
     * Prepare the 3DSv2 WPF Parameters
     *
     * @param Genesis $genesis
     * @param $data
     * @return void
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    protected function prepareThreedsV2Parameters($genesis, $data)
    {
        /** @var \Genesis\Api\Request\Wpf\Create $request */
        $request = $genesis->request();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $data['order']['orderObject'];

        $hasPhysicalProducts = $this->getModuleHelper()->isCheckoutWithPhysicalProduct($order);
        $customer            = null;
        if (!$order->getCustomerIsGuest()) {
            $customer = $this->getModuleHelper()->getCustomerEntity($order->getCustomerId());
        }

        // Merchant Risk parameters
        $request->setThreedsV2ControlChallengeIndicator($this->_configHelper->getThreedsChallengeIndicator());
        $request->setThreedsV2PurchaseCategory(($hasPhysicalProducts ? Categories::GOODS : Categories::SERVICE));
        $request->setThreedsV2MerchantRiskShippingIndicator(
            $this->getThreedsHelper()->fetchShippingIndicator($hasPhysicalProducts, $order)
        );
        $request->setThreedsV2MerchantRiskDeliveryTimeframe(
            $hasPhysicalProducts ? DeliveryTimeframes::ANOTHER_DAY : DeliveryTimeframes::ELECTRONICS
        );
        $request->setThreedsV2MerchantRiskReorderItemsIndicator(
            $this->getThreedsHelper()->fetchReorderItemsIndicator($order)
        );

        // Card Holder parameters
        if ($customer) {
            $request->setThreedsV2CardHolderAccountCreationDate($customer->getCreatedAt());
            $request->setThreedsV2CardHolderAccountUpdateIndicator(
                $this->getThreedsHelper()->fetchUpdateIndicator($customer)
            );
            $request->setThreedsV2CardHolderAccountLastChangeDate(
                $this->getThreedsHelper()->getSortedCustomerAddress($customer)[0]['updated_at']
            );
            $request->setThreedsV2CardHolderAccountPasswordChangeIndicator(
                $this->getThreedsHelper()->fetchPasswordChangeIndicator($customer)
            );

            $isPasswordChanged = $request->getThreedsV2CardHolderAccountPasswordChangeIndicator() !=
                PasswordChangeIndicators::NO_CHANGE;
            $request->setThreedsV2CardHolderAccountPasswordChangeDate(
                $isPasswordChanged ? $customer->getUpdatedAt() : null
            );

            $firstUsedShippingAddressTime = $this->getThreedsHelper()->fetchShippingAddressDateFirstUsed($order);
            $request->setThreedsV2CardHolderAccountShippingAddressDateFirstUsed(
                $firstUsedShippingAddressTime
            );
            $request->setThreedsV2CardHolderAccountShippingAddressUsageIndicator(
                $this->getThreedsHelper()->fetchShippingAddressUsageIndicator($firstUsedShippingAddressTime)
            );

            $request->setThreedsV2CardHolderAccountTransactionsActivityLast24Hours(
                $this->getThreedsHelper()->fetchTransactionActivityLast24Hours($order)
            );
            $request->setThreedsV2CardHolderAccountTransactionsActivityPreviousYear(
                $this->getThreedsHelper()->fetchTransactionActivityPreviousYear($order)
            );
            $request->setThreedsV2CardHolderAccountPurchasesCountLast6Months(
                $this->getThreedsHelper()->fetchPurchasedCountLastHalfYear($order)
            );

            $firstOrderDate = $this->getThreedsHelper()->fetchFirstOrderDate($order);
            $request->setThreedsV2CardHolderAccountRegistrationDate($firstOrderDate);
        }

        $request->setThreedsV2CardHolderAccountRegistrationIndicator(
            $this->getThreedsHelper()->fetchRegistrationIndicator($order, $firstOrderDate ?? '')
        );
    }

    /**
     * Threeds V2 Helper
     *
     * @return Data|\EMerchantPay\Genesis\Helper\Threeds
     */
    protected function getThreedsHelper()
    {
        return $this->_threedsHelper;
    }

    /**
     * Add SCA Exemption parameters to Genesis Request
     *
     * @var \Genesis\Genesis $genesis
     * @return void
     */
    protected function addScaParameters($genesis)
    {
        $scaValue       = $this->getConfigHelper()->getScaExemption();
        $scaAmountValue = $this->getConfigHelper()->getScaExemptionAmount();
        $wpfAmount      = (float) $genesis->request()->getAmount();
        /** @var \Genesis\Api\Request\Wpf\Create $request */
        $request        = $genesis->request();

        if ($wpfAmount <= $scaAmountValue) {
            $request->setScaExemption($scaValue);
        }
    }

    /**
     * Order the Selected Transaction Type by shifting the Credit Card Transaction Types in front
     *
     * @return array|string[]
     */
    protected function getSelectedTransactionTypes()
    {
        $selectedTypes   = $this->getConfigHelper()->getTransactionTypes();
        $creditCardTypes = GenesisTransactionTypes::getCardTransactionTypes();

        asort($selectedTypes);

        $selectedCreditCardTypes = array_intersect($creditCardTypes, $selectedTypes);

        return array_merge(
            $selectedCreditCardTypes,
            array_diff($selectedTypes, $selectedCreditCardTypes)
        );
    }
}
