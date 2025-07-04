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

namespace EMerchantPay\Genesis\Model;

use Genesis\Api\Constants\Endpoints;
use Genesis\Api\Constants\Environments;
use Genesis\Config as GenesisConfig;
use Genesis\Exceptions\InvalidArgument;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    /**
     * Current payment method code
     *
     * @var string
     */
    protected $_methodCode;
    /**
     * Current store id
     *
     * @var int
     */
    protected $_storeId;
    /**
     * @var string
     */
    protected $pathPattern;
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Get an Instance of the Magento ScopeConfig
     *
     * @return ScopeConfigInterface
     */
    protected function getScopeConfig()
    {
        return $this->_scopeConfig;
    }

    /**
     * Set the Credentials and Environment to the Gateway Client
     *
     * @return void
     *
     * @throws InvalidArgument
     */
    public function initGatewayClient()
    {
        GenesisConfig::setEndpoint(
            Endpoints::EMERCHANTPAY
        );

        GenesisConfig::setUsername(
            $this->getUserName()
        );

        GenesisConfig::setPassword(
            $this->getPassword()
        );

        $token = $this->getToken();
        if (!empty($token)) {
            GenesisConfig::setToken(
                $token
            );
        }

        GenesisConfig::setEnvironment(
            $this->getIsStagingMode() ?
                Environments::STAGING :
                Environments::PRODUCTION
        );
    }

    /**
     * Payment method instance code getter
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->_methodCode;
    }

    /**
     * Store ID setter
     *
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = (int)$storeId;
        return $this;
    }

    /**
     * Returns payment configuration value
     *
     * @param string   $key
     * @param int|null $storeId
     *
     * @return null|string
     */
    public function getValue($key, $storeId = null)
    {
        switch ($key) {
            default:
                $underscored = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $key));
                $path = $this->getSpecificConfigPath($underscored);
                if ($path !== null) {
                    $value = $this->getScopeConfig()->getValue(
                        $path,
                        ScopeInterface::SCOPE_STORE,
                        $storeId ?: $this->_storeId
                    );
                    return $value;
                }
        }
        return null;
    }

    /**
     * Sets method code
     *
     * @param string $methodCode
     *
     * @return void
     */
    public function setMethodCode($methodCode)
    {
        $this->_methodCode = $methodCode;
    }

    /**
     * Sets path pattern
     *
     * @param string $pathPattern
     *
     * @return void
     */
    public function setPathPattern($pathPattern)
    {
        $this->pathPattern = $pathPattern;
    }

    /**
     * Map any supported payment method into a config path by specified field name
     *
     * @param string $fieldName
     *
     * @return string|null
     */
    protected function getSpecificConfigPath($fieldName)
    {
        if ($this->pathPattern) {
            return sprintf($this->pathPattern, $this->_methodCode, $fieldName);
        }

        return "payment/{$this->_methodCode}/{$fieldName}";
    }

    /**
     * Check whether Gateway API credentials are available for this method
     *
     * @param string|null $methodCode
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isApiAvailable($methodCode = null)
    {
        return !empty($this->getUserName()) &&
               !empty($this->getPassword()) &&
               !empty($this->getTransactionTypes());
    }

    /**
     * Check whether method available for checkout or not
     *
     * @param string|null $methodCode
     *
     * @return bool
     */
    public function isMethodAvailable($methodCode = null)
    {
        return $this->isMethodActive($methodCode) &&
               $this->isApiAvailable($methodCode);
    }

    /**
     * Check whether method active in configuration and supported for merchant country or not
     *
     * @param string $methodCode Method code
     *
     * @return bool
     */
    public function isMethodActive($methodCode = null)
    {
        $methodCode = $methodCode?: $this->_methodCode;

        return $this->isFlagChecked($methodCode, 'active');
    }

    /**
     * Check whether tokenization is enabled
     *
     * @param string $methodCode Method code
     *
     * @return bool
     */
    public function isTokenizationEnabled($methodCode = null)
    {
        $methodCode = $methodCode?: $this->_methodCode;

        return $this->isFlagChecked($methodCode, 'tokenization');
    }

    /**
     * Check if Method Bool Setting Checked
     *
     * @param string|null $methodCode
     * @param string      $name
     *
     * @return bool
     */
    public function isFlagChecked($methodCode, $name)
    {
        $methodCode = $methodCode?: $this->_methodCode;

        return $this->getScopeConfig()->isSetFlag(
            "payment/{$methodCode}/{$name}",
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    /**
     * Get if Payment Solution is configured to use the Staging (Test) Environment
     *
     * @return bool
     */
    public function getIsStagingMode()
    {
        return $this->isFlagChecked($this->_methodCode, 'test_mode');
    }

    /**
     * Get Method UserName Admin Setting
     *
     * @return null|string
     */
    public function getUserName()
    {
        return $this->getValue('username');
    }

    /**
     * Get Method Password Admin Setting
     *
     * @return null|string
     */
    public function getPassword()
    {
        return $this->getValue('password');
    }

    /**
     * Get Method Token Admin Setting
     *
     * @return null|string
     */
    public function getToken()
    {
        return $this->getValue('token');
    }

    /**
     * Get Method Checkout Page Title
     *
     * @return null|string
     */
    public function getCheckoutTitle()
    {
        return $this->getValue('title');
    }

    /**
     * Get Method Available Transaction Types
     *
     * @return array
     */
    public function getTransactionTypes()
    {
        return
            array_map(
                'trim',
                explode(
                    ',',
                    $this->getValue('transaction_types') ?? ''
                )
            );
    }

    /**
     * Get Method New Order Status
     *
     * @return null|string
     */
    public function getOrderStatusNew()
    {
        return $this->getValue('order_status');
    }

    /**
     * Get if specific currencies are allowed (not all global allowed currencies)
     *
     * @return bool
     */
    public function getAreAllowedSpecificCurrencies()
    {
        return $this->isFlagChecked(
            $this->_methodCode,
            'allow_specific_currency'
        );
    }

    /**
     * Get Method Allowed Currency array
     *
     * @return array
     */
    public function getAllowedCurrencies()
    {
        return array_map(
            'trim',
            explode(
                ',',
                $this->getValue('specific_currencies') ?? ''
            )
        );
    }

    /**
     * Checks whether an email has to be sent after successful payment
     *
     * @param string|null $methodCode
     *
     * @return bool
     */
    public function getPaymentConfirmationEmailEnabled($methodCode = null)
    {
        $methodCode = $methodCode?: $this->_methodCode;

        return $this->isFlagChecked($methodCode, 'payment_confirmation_email_enabled');
    }

    /**
     * Get selected Bank codes
     *
     * @return array
     */
    public function getBankCodes()
    {
        return
            array_map(
                'trim',
                explode(
                    ',',
                    $this->getValue('bank_codes') ?? ''
                )
            );
    }

    /**
     * Get is 3DSv2 allowed
     *
     * @return bool
     */
    public function isThreedsAllowed()
    {
        return $this->isFlagChecked($this->_methodCode, 'threeds_allowed');
    }

    /**
     * Get the current selected Challenge Indicator
     *
     * @return string
     */
    public function getThreedsChallengeIndicator()
    {
        return $this->getValue('threeds_challenge_indicator');
    }

    /**
     * Return SCA Exemption configured value
     *
     * @return string|null
     */
    public function getScaExemption()
    {
        return $this->getValue('sca_exemption');
    }

    /**
     * Return SCA Exemptions configured amount
     *
     * @return float|null
     */
    public function getScaExemptionAmount()
    {
        return (float) $this->getValue('sca_exemption_amount');
    }

    /**
     * Get is payment processing in iframe enabled
     *
     * @return bool
     */
    public function isIframeProcessingEnabled(): bool
    {
        return $this->isFlagChecked($this->_methodCode, 'iframe_processing_enabled');
    }
}
