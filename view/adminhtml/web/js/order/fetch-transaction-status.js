define([
    'jquery',
    'mage/translate',
], function ($, $t) {
    return function (config, wrapper) {
        var button = $('.biller-fetch-status', wrapper);
        var status = $('.biller-transaction-status', wrapper);

        button.click(function () {
            button.text($t('Fetching...'));
            button.prop('disabled', true);
            status.hide();

            $.ajax({
                url: config.endpoint,
                method: 'POST',
                data: {order_id: config.order_id},
                success: function (result) {
                    status.html(result.message);
                    status.show();
                },
                error: function (result) {
                    status.html(result.message);
                    status.show();
                },
                complete: function () {
                    button.prop('disabled', false);
                    button.text($t('Fetch Status'));
                }
            })
        });
    }
});
