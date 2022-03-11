require([
    'jquery'
], function ($) {

    var $mmHeadingComment = $('.mm-biller-heading-comment');

    if($mmHeadingComment.length) {

        $(window).load(function() {

            var showMoreLessBtnHtml = '<div class="mm-biller-show-more-actions"><a href="javascript:void(0)" class="mm-biller-show-btn-more">'
                + $.mage.__('Show more.') + '</a>'
                + '<a href="javascript:void(0)" class="mm-biller-show-btn-less">' + $.mage.__('Show less.') + '</a></div>';

            $mmHeadingComment.each(function (i, el) {
                var elStyles = getComputedStyle(el);
                var $el = $(el);
                var oldHtml = $el.html();
                var ellipsesIndex = oldHtml.length;
                var maxElHeight = parseInt(elStyles.lineHeight) * 2;

                if (maxElHeight < $el.outerHeight()) {

                    while (maxElHeight < $el.outerHeight()) {
                        $el.html(function (index, text) {
                            var newText = text.replace(/\W*\s(\S)*$/, '');
                            ellipsesIndex = newText.length;
                            return newText;
                        });
                    }

                    var visibleStr = oldHtml.substr(0, ellipsesIndex);
                    var hiddenStr = oldHtml.substr(ellipsesIndex);

                    $el.html('<span>' + visibleStr + '</span><span class="mm-biller-show-more-block">'
                        + hiddenStr.replace('<br/>', '<div></div>')
                        + '</span>' + showMoreLessBtnHtml);

                }
            });
        });

        /**
         * Toggle show more btn event.
         */
        $(document).on('click', '.mm-biller-show-more-actions a', function() {
            $(this).closest('.mm-biller-heading-comment').toggleClass('mm-biller-show-more-active');
        });
    }
});
