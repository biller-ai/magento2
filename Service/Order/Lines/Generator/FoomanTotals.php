<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Biller\Connect\Service\Order\Lines\Generator;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Module\Manager;
use Mollie\Payment\Helper\General;

class FoomanTotals implements GeneratorInterface
{
    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(
        Manager $moduleManager
    ) {
        $this->moduleManager = $moduleManager;
    }

    public function process(CartInterface $cart, array $lines): array
    {
        if (!$this->moduleManager->isEnabled('Fooman_Totals')) {
            return $lines;
        }

        $extensionAttributes = $cart->getShippingAddress()->getExtensionAttributes();
        if (!$extensionAttributes) {
            return $lines;
        }

        /** @var \Fooman\Totals\Api\Data\QuoteAddressTotalGroupInterface|null $foomanGroup */
        $foomanGroup = $extensionAttributes->getFoomanTotalGroup();
        if (empty($foomanGroup)) {
            return $lines;
        }

        $totals = $foomanGroup->getItems();
        if (empty($totals)) {
            return $lines;
        }

        /** @var \Fooman\Totals\Api\Data\TotalInterface $total */
        foreach ($totals as $total) {
            $vatRate = 0;
            if ($total->getBaseTaxAmount()) {
                $vatRate = round(($total->getBaseTaxAmount() / $total->getBaseAmount()) * 100);
            }

            $lines[] = [
                'quantity' => 1,
                'product_id' => $total->getCode(),
                'product_name' => $total->getLabel(),
                'product_description' => $total->getCode(),
                'product_price_excl_tax' => round($total->getBaseAmount() * 100),
                'product_price_incl_tax' => round(($total->getBaseAmount() + $total->getBaseTaxAmount()) * 100),
                'product_tax_rate_percentage' => $vatRate,
            ];
        }

        return $lines;
    }
}
