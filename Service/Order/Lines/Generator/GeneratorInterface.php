<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Biller\Connect\Service\Order\Lines\Generator;

use Magento\Quote\Api\Data\CartInterface;

interface GeneratorInterface
{
    public function process(CartInterface $cart, array $lines): array;
}
