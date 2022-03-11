<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Plugin\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ChangeQuoteControl;
use Magento\Quote\Model\QuoteFactory;

/**
 * Class ChangeQuoteControlPlugin
 */
class ChangeQuoteControlPlugin
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * ChangeQuoteControlPlugin constructor.
     *
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        QuoteFactory $quoteFactory
    ) {
        $this->quoteFactory = $quoteFactory;
    }

    public function afterIsAllowed(ChangeQuoteControl $subject, bool $result, CartInterface $quote): bool
    {
        $quoteModel = $this->quoteFactory->create()->load($quote->getId());
        if ($quoteModel->getPayment()->getMethod() == 'biller_gateway') {
            $result = true;
        }
        return $result;
    }
}
