<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Biller\Connect\Service\Order\Lines;

use Magento\Quote\Api\Data\CartInterface;
use Biller\Connect\Service\Order\Lines\Generator\GeneratorInterface;

class OrderLinesGenerator
{
    /**
     * @var array
     */
    private $generators;

    /**
     * @param GeneratorInterface[] $generators
     */
    public function __construct(array $generators = [])
    {
        $this->generators = $generators;
    }

    public function execute(CartInterface $cart): array
    {
        $lines = [];
        foreach ($this->generators as $generator) {
            $lines = $generator->process($cart, $lines);
        }

        return $lines;
    }
}
