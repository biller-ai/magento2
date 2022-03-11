/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return function (data) {
        let i = 0;
        let check = function() {
            if (i === 3) {
                $("#biller-refresh-button").show();
                $("#biller-loading").hide();
            } else {
                setTimeout(function () {
                    i++;
                    $.ajax({
                        method: 'GET',
                        url: data.checkUrl,
                        success: function (result) {
                            if (result == true) {
                                window.location.replace(data.refreshUrl);
                            } else {
                                check();
                            }
                        },
                        error: function () {
                            $("#biller-refresh-button").show();
                            $("#biller-loading").hide();
                        }
                    });
                }, 2000);
            }
        }
        check();
    };
});
