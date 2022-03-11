<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Plugin\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class View
{
    /**
     * @param OrderView $view
     */
    public function beforeSetLayout(OrderView $view)
    {
        $order = $view->getOrder();
        if (!$order) {
            return;
        }
        if ($order->getPayment()->getMethod() == 'biller_gateway') {
            $view->removeButton('order_invoice');
            $view->updateButton('order_ship', 'label', __('Invoice and Ship'));
        }
    }
}
