<?php

namespace EMerchantPay\Genesis\Model\Service;

use EMerchantPay\Genesis\Model\Config;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

class MultiCurrencyProcessingService
{

    /**
     * @var Config
     */
    private Config $_config;

    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $_priceCurrencyInterface;

    /**
     * @var string
     */
    private $_methodCode = null;

    /**
     * MultiCurrencyProcessingService constructor.
     *
     * @param Config                 $config
     * @param PriceCurrencyInterface $priceCurrencyInterface
     */
    public function __construct(
        Config                 $config,
        PriceCurrencyInterface $priceCurrencyInterface
    ) {
        $this->_config                 = $config;
        $this->_priceCurrencyInterface = $priceCurrencyInterface;
    }

    /**
     * Set the method code
     *
     * @param string $methodCode
     */
    public function setMethodCode($methodCode)
    {
        $this->_methodCode = $methodCode;
    }

    /**
     * Get the amount for the WPF according to the selected process currency
     *
     * @param OrderInterface $order
     *
     * @return float|null
     */
    public function getWpfAmount(OrderInterface $order)
    {
        /** @var Order $order */
        return $this->isMultiCurrencyProcessing() ? $order->getBaseGrandTotal() : $order->getTotalDue();
    }

    /**
     * Get the amount for the Order according to the selected process currency
     *
     * @param Order $order
     * @param float $amount
     *
     * @return float
     */
    public function getOrderAmount(Order $order, float $amount)
    {
        return $this->isMultiCurrencyProcessing() ? $order->getGrandTotal() : $amount;
    }

    /**
     * Get the currency for the Order according to the selected process currency
     *
     * @param Order $order
     *
     * @return string
     */
    public function getOrderCurrency(Order $order)
    {
        return $this->isMultiCurrencyProcessing() ? $order->getOrderCurrencyCode() : $order->getBaseCurrencyCode();
    }

    /**
     * Convert and round according to the order currency
     *
     * @param float $amount
     * @param Order $order
     *
     * @return float|null
     */
    public function convertAmount($amount, $order)
    {
        return $this->isMultiCurrencyProcessing() ?
            $this->_priceCurrencyInterface->convertAndRound(
                $amount,
                $order->getStore(),
                $order->getOrderCurrencyCode()
            ) :
            $amount;
    }

    /**
     * Get the selected process currency
     *
     * @return bool
     */
    public function isMultiCurrencyProcessing()
    {
        return $this->_config->isFlagChecked($this->_methodCode, 'multi_currency_processing');
    }
}
