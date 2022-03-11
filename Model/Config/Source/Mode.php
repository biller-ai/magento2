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
class Mode implements OptionSourceInterface
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
                ['value' => 'sandbox', 'label' => __('Sandbox')],
                ['value' => 'live', 'label' => __('Live')]
            ];
        }

        return $this->options;
    }
}
