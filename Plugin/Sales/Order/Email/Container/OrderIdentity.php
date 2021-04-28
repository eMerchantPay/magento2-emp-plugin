<?php

namespace EMerchantPay\Genesis\Plugin\Sales\Order\Email\Container;

/**
 * Order Identity class
 *
 * Class OrderIdentity
 * @package EMerchantPay\Genesis\Plugin\Sales\Order\Email\Container
 *
 */

class OrderIdentity
{
    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }
    
    /**
     * @param \Magento\Sales\Model\Order\Email\Container\OrderIdentity $subject
     * @param callable $proceed
     * @return bool
     *
     * @codeCoverageIgnore
     */
    public function aroundIsEnabled(
        \Magento\Sales\Model\Order\Email\Container\OrderIdentity $subject,
        callable $proceed
    ) {
        $returnValue = $proceed();
        $forceOrderMailSentOnSuccess = $this->checkoutSession->getForceOrderMailSentOnSuccess();

        if (isset($forceOrderMailSentOnSuccess) && $forceOrderMailSentOnSuccess) {
            $returnValue = $returnValue ? false : true;

            $this->checkoutSession->unsForceOrderMailSentOnSuccess();
        }

        return $returnValue;
    }
}
