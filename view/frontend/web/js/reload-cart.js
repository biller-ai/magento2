/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Customer/js/customer-data'
    ], function (customerData) {
    "use strict";
    function reloadCart() {
        let sections = ['cart'];
        customerData.invalidate(sections);
        customerData.reload(sections, true);
    }
    return reloadCart;
}
);
