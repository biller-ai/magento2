/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/payment/additional-validators',
        'uiRegistry'
    ],
    function ($, Component, errorProcessor, quote, customer, urlBuilder, fullScreenLoader, storage, messageList, additionalValidators, uiRegistry) {
        'use strict';
        var checkoutConfig = window.checkoutConfig.payment;
        var address = quote.isVirtual() ? quote.billingAddress() : quote.shippingAddress();
        var payload = '';

        return Component.extend({
            defaults: {
                template: 'Biller_Connect/payment/biller'
            },

            getCode: function() {
                return 'biller_gateway';
            },

            getCompanyName: function () {
                return address.company;
            },

            getRegistrationNumber: function () {
                return checkoutConfig[this.item.method].registrationNumber;
            },

            getVatNumber: function () {
                return address.vatId;
            },

            submitForm: function(event){
                event.preventDefault();
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                this.isPlaceOrderActionAllowed(false);
                var _this = this;

                if (additionalValidators.validate()) {
                    fullScreenLoader.startLoader();
                    var oneStepCheckoutAjax = uiRegistry.get('checkout.iosc.ajax');
                    if (oneStepCheckoutAjax) {
                        oneStepCheckoutAjax.update().done(function () {
                            _this._placeOrder();
                        });
                    } else {
                        _this._placeOrder();
                    }
                }
            },

            _placeOrder: function () {
                return this.setPaymentInformation().success(function () {
                    this.orderRequest(customer.isLoggedIn(), quote.getQuoteId());
                }.bind(this));
            },

            setPaymentInformation: function() {
                var serviceUrl, payload;

                payload = {
                    cartId: quote.getQuoteId(),
                    billingAddress: quote.billingAddress(),
                    paymentMethod: this.getData()
                };

                if (customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/carts/mine/set-payment-information', {});
                } else {
                    payload.email = quote.guestEmail;
                    serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/set-payment-information', {
                        quoteId: quote.getQuoteId()
                    });
                }

                return storage.post(
                    serviceUrl, JSON.stringify(payload)
                );
            },

            orderRequest: function(isLoggedIn, cartId) {
                var url = 'rest/V1/biller/order-request';

                payload = {
                    isLoggedIn: isLoggedIn,
                    cartId: cartId,
                    paymentMethod: this.getData()
                };

                storage.post(
                    url,
                    JSON.stringify(payload)
                ).done(function (response) {
                    if (response[0].success) {
                        fullScreenLoader.stopLoader();
                        window.location.replace(response[0].payment_page_url);
                    } else {
                        fullScreenLoader.stopLoader();
                        this.addError(response[0].message);
                    }
                }.bind(this));
            },

            validate: function () {
                var form = $('#biller_gateway-form');
                return form.validation() && form.validation('isValid');
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        "company_name":  $('#biller_company_name').val(),
                        "registration_number":  $('#biller_registration_number').val(),
                        "vat_number":  $('#biller_vat_number').val(),
                        "website":  $('#biller_website').val(),
                        "customer_email": $('#customer-email').val()
                    }
                };
            },

            /**
             * Adds error message
             *
             * @param {String} message
             */
            addError: function (message) {
                messageList.addErrorMessage({
                    message: message
                });
            },
        });
    }
);
