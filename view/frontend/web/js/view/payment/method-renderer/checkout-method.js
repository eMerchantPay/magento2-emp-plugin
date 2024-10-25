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

define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'mage/translate',
    ],
    function (
        $,
        quote,
        urlBuilder,
        storage,
        customerData,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        checkoutData,
        additionalValidators,
        url,
        $t
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'EMerchantPay_Genesis/payment/method/checkout/form'
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },

            selectPaymentMethod: function () {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },

            afterPlaceOrder: function () {
                if (this.isIframeProcessingEnabled()) {
                    const iframe = this.empCreateIframeElement();
                    iframe.src = url.build('emerchantpay/checkout/index');
                } else {
                    window.location.replace(url.build('emerchantpay/checkout/index'));
                }
            },

            empCreateIframeElement() {
                const div    = document.createElement('div');
                const header = document.createElement('div');
                const iframe = document.createElement('iframe');

                div.className    = 'emp-threeds-modal';
                header.className = 'emp-threeds-iframe-header';
                iframe.className = 'emp-threeds-iframe';

                iframe.setAttribute('name', 'emp-threeds-iframe');
                header.innerHTML = '<h3>' + $t('The payment is being processed<br><span>Please, wait</span>') + '</h3>';
                iframe.onload    = function () {
                    header.style.display = 'none';
                    div.style.display    = 'block';
                }

                div.appendChild(header);
                div.appendChild(iframe);

                document.body.appendChild(div);

                div.style.display = 'block';

                return iframe;
            },

            isIframeProcessingEnabled() {
                const empIframeProcessingDataDiv = document.getElementById('empIframeProcessingData');
                let isIframeProcessingEnabled = empIframeProcessingDataDiv.getAttribute('data-iframe-processing-enabled');

                return isIframeProcessingEnabled === '1';
            }
        });
    }
);
