/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Biller_Connect/js/model/biller-validation'
    ],
    function (Component, additionalValidators, billerValidator) {
        'use strict';
        additionalValidators.registerValidator(billerValidator);
        return Component.extend({});
    }
);
