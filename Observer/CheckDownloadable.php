<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Observer;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Downloadable\Model\Product\Type;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;

/**
 * Class CheckDownloadable
 */
class CheckDownloadable implements ObserverInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * CheckDownloadable constructor.
     * @param Session $checkoutSession
     */
    public function __construct(
        Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getData('quote');
        if (!$quote) {
            $quote = $this->checkoutSession->getQuote();
        }
        if ($quote->getId() && $observer->getEvent()->getMethodInstance()->getCode() == "biller_gateway") {
            $isActive = true;
            foreach ($quote->getAllItems() as $item) {
                if (($item->getProducttype() == ProductType::TYPE_VIRTUAL)
                    || ($item->getProductType() == Type::TYPE_DOWNLOADABLE)) {
                    $isActive = false;
                }
            }
            $checkResult = $observer->getEvent()->getResult();
            $checkResult->setData('is_available', $isActive);
        }
    }
}
