<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class PaymentLinkDuration
 */
class PaymentLinkDuration implements OptionSourceInterface
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
                ['value' => '900', 'label' => __('900')],
                ['value' => '86400', 'label' => __('86400')]
            ];
        }

        return $this->options;
    }
}
