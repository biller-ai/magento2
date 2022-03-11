/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    ['jquery', 'mage/translate', 'Magento_Ui/js/model/messageList', 'Magento_Checkout/js/model/quote'],
    function ($, $t, messageList, quote) {
        'use strict';
        return {
            validate: function () {
                var isValid = true;
                if (quote.paymentMethod()
                    && quote.paymentMethod().method === 'biller_gateway'
                    && $('#biller_company_name').val() == '') {
                    isValid = false;
                    messageList.addErrorMessage(
                        { message: $t('Company name is required to use Biller') }
                    );
                }
                // if ($('#biller_registration_number').val() == '' && $('#biller_vat_number').val() == '') {
                //     isValid = false;
                //     messageList.addErrorMessage(
                //         { message: $t('Registration number or Vat number is required to use Biller') }
                //     );
                // }
                return isValid;
            }
        }
    }
);
