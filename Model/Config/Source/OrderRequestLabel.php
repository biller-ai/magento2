<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Mode Options Source model
 */
class OrderRequestLabel implements OptionSourceInterface
{

    /**
     * Options array
     *
     * @var array
     */
    public $options = null;

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        if (!$this->options) {
            $this->options = [
                ['value' => 'Webshop', 'label' => __('Webshop')],
                ['value' => 'Payment Request', 'label' => __('Payment Request')],
                ['value' => 'POS', 'label' => __('POS')]
            ];
        }

        return $this->options;
    }
}
