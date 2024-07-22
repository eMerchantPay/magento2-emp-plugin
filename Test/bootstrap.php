<?php
// @codingStandardsIgnoreStart
require 'dev/tests/unit/framework/bootstrap.php';

try {
    require 'app/code/EMerchantPay/Genesis/vendor/autoload.php';
} catch (Exception $e) {
    /**
     * This will throw LogicException:
     * Module 'EMerchantPay_Genesis' from
     * '/magento/app/code/EMerchantPay/Genesis' has been already defined in
     * '/magento/app/code/EMerchantPay/Genesis'.
     *
     * Ignoring this exception isn't a problem for phpunit tests
     */
}
// @codingStandardsIgnoreEnd
