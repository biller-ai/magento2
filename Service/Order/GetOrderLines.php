<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Service\Order;

use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;

/**
 * Class GetOrderLines
 */
class GetOrderLines
{

    public const SHIPPING_COST_LINE = 'shipping cost';

    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * GetOrderLines constructor.
     * @param LogRepository $logRepository
     */
    public function __construct(
        LogRepository $logRepository
    ) {
        $this->logRepository = $logRepository;
    }

    /**
     * @param Quote $quote
     * @return array
     * @throws \Exception
     */
    public function fromQuote(Quote $quote): array
    {
        $totalIncl = 0;
        $orderLines = [];

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $totalIncl += $this->getProductPriceInclTax($quoteItem) * $quoteItem->getQty();
            $orderLines[] = [
                'quantity' => $quoteItem->getQty(),
                'product_id' => $quoteItem->getSku(),
                'product_name' => $quoteItem->getName(),
                'product_description' => $quoteItem->getName(),
                'product_price_excl_tax' => $this->getProductPriceExclTax($quoteItem),
                'product_price_incl_tax' => $this->getProductPriceInclTax($quoteItem),
                'product_tax_rate_percentage' => $quoteItem->getTaxPercent()
            ];
        }

        $totalIncl += $this->getShippingPriceInclTax($quote);
        $orderLines[] = [
            'quantity' => 1,
            'product_id' => self::SHIPPING_COST_LINE,
            'product_name' => $quote->getShippingAddress()->getShippingDescription(),
            'product_description' => self::SHIPPING_COST_LINE,
            'product_price_excl_tax' => $this->getShippingPriceExclTax($quote),
            'product_price_incl_tax' => $this->getShippingPriceInclTax($quote),
            'product_tax_rate_percentage' => $this->getShippingTaxPercent($quote),
        ];

        return $orderLines;
    }

    /**
     * @param CreditmemoInterface $creditmemo
     * @param string|null $description
     * @return array
     */
    public function fromCreditmemo(CreditmemoInterface $creditmemo, ?string $description = 'Credit'): array
    {
        $orderLines = [];
        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            if ($creditmemoItem->getQty() == 0 || $creditmemoItem->getPrice() == 0) {
                continue;
            }
            $orderLines[] = [
                'quantity' => $creditmemoItem->getQty(),
                'product_id' => $creditmemoItem->getSku(),
                'description' => $description,
                'total_amount_excl_tax' => $this->getRowTotalCreditmemoItemExclTax($creditmemoItem),
                'total_amount_incl_tax' => $this->getRowTotalCreditmemoItemInclTax($creditmemoItem),
            ];
        }

        if ($creditmemo->getBaseShippingAmount() != 0) {
            $orderLines[] = [
                'quantity' => 1,
                'product_id' => self::SHIPPING_COST_LINE,
                'description' => $description,
                'total_amount_excl_tax' => $this->getRowTotalCreditmemoShippingExclTax($creditmemo),
                'total_amount_incl_tax' => $this->getRowTotalCreditmemoShippingInclTax($creditmemo),
            ];
        }

        return $orderLines;
    }

    /**
     * Get product price excluding tax from quote item.
     * Discounts are taken into account per orderLines.
     *
     * @param QuoteItem $quoteItem
     * @return float
     */
    private function getProductPriceExclTax(QuoteItem $quoteItem): float
    {
        return round(
            ($quoteItem->getBaseRowTotal()
                - $quoteItem->getBaseDiscountAmount()
                + $quoteItem->getBaseDiscountTaxCompensationAmount())
            / $quoteItem->getQty() * 100
        );
    }

    /**
     * Get product price from quote item including discount values.
     * Discounts are taken into account per orderLines.
     *
     * @param QuoteItem $quoteItem
     * @return float
     */
    private function getProductPriceInclTax(QuoteItem $quoteItem): float
    {
        return round(
            ($quoteItem->getBaseRowTotal()
                - $quoteItem->getBaseDiscountAmount()
                + $quoteItem->getBaseTaxAmount()
                + $quoteItem->getBaseDiscountTaxCompensationAmount())
            / $quoteItem->getQty() * 100
        );
    }

    /**
     * Get Shipping price excluding tax from quote.
     *
     * @param Quote $quote
     * @return float
     */
    private function getShippingPriceExclTax(Quote $quote): float
    {
        return round(
            ($quote->getShippingAddress()->getBaseShippingAmount()
                - $quote->getShippingAddress()->getBaseShippingDiscountAmount())
            * 100
        );
    }

    /**
     * Get Shipping price including tax from quote.
     * Discount tax compsensation should be taken into account.
     *
     * @param Quote $quote
     * @return float
     */
    private function getShippingPriceInclTax(Quote $quote): float
    {
        return round(
            ($quote->getShippingAddress()->getBaseShippingInclTax()
                - $quote->getShippingAddress()->getBaseShippingDiscountAmount())
            * 100
        );
    }

    /**
     * Calculate shipping tax percent
     *
     * @param Quote $quote
     * @return float|int
     */
    private function getShippingTaxPercent(Quote $quote)
    {
        $shippingTaxAmount = $quote->getShippingAddress()->getBaseShippingTaxAmount()
            + $quote->getShippingAddress()->getBaseShippingDiscountTaxCompensationAmnt();
        $shippingAmount = $quote->getShippingAddress()->getBaseShippingAmount();
        return $shippingAmount > 0 ? round(($shippingTaxAmount / $shippingAmount) * 100, 1) : 0;
    }

    /**
     * Get row total excluding tax from creditmemo item.
     * Discounts are taken into account per orderLines.
     *
     * @param CreditmemoItem $creditmemoItem
     * @return float
     */
    private function getRowTotalCreditmemoItemExclTax(CreditmemoItem $creditmemoItem): float
    {
        return round(
            ($creditmemoItem->getBaseRowTotal()
                - $creditmemoItem->getBaseDiscountAmount()
                + $creditmemoItem->getBaseDiscountTaxCompensationAmount())
            / $creditmemoItem->getQty() * 100
        ) * $creditmemoItem->getQty();
    }

    /**
     * Get row total including tax from creditmemo item.
     * Discounts are taken into account per orderLines.
     *
     * @param CreditmemoItem $creditmemoItem
     * @return float
     */
    private function getRowTotalCreditmemoItemInclTax(CreditmemoItem $creditmemoItem): float
    {
        return round(
            ($creditmemoItem->getBaseRowTotal()
                - $creditmemoItem->getBaseDiscountAmount()
                + $creditmemoItem->getBaseTaxAmount()
                + $creditmemoItem->getBaseDiscountTaxCompensationAmount())
            / $creditmemoItem->getQty() * 100
        ) * $creditmemoItem->getQty();
    }

    /**
     * Get Shipping price excluding tax from creditmemo.
     * Discount tax compsensation should be taken into account.
     *
     * @param CreditmemoInterface $creditmemo
     * @return float
     */
    private function getRowTotalCreditmemoShippingExclTax(CreditmemoInterface $creditmemo): float
    {
        return round(
            ($creditmemo->getBaseShippingAmount()
                - $creditmemo->getBaseShippingDiscountAmount())
            * 100
        );
    }

    /**
     * Get Shipping price excluding tax from creditmemo.
     * Discount tax compsensation should be taken into account.
     *
     * @param CreditmemoInterface $creditmemo
     * @return float
     */
    private function getRowTotalCreditmemoShippingInclTax(CreditmemoInterface $creditmemo): float
    {
        return round(
            ($creditmemo->getBaseShippingInclTax()
                - $creditmemo->getBaseShippingDiscountAmount())
            * 100
        );
    }
}
