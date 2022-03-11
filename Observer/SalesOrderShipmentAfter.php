<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Observer;

use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Biller\Connect\Service\Order\CaptureOrderByShipment;

class SalesOrderShipmentAfter implements ObserverInterface
{

    /**
     * @var CaptureOrderByShipment
     */
    private $captureOrderByShipment;

    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * SalesOrderShipmentAfter constructor.
     *
     * @param CaptureOrderByShipment $captureOrderByShipment
     */
    public function __construct(
        CaptureOrderByShipment $captureOrderByShipment,
        LogRepository $logRepository
    ) {
        $this->captureOrderByShipment = $captureOrderByShipment;
        $this->logRepository = $logRepository;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();

        try {
            $this->captureOrderByShipment->execute($shipment);
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Capture Order', $exception->getMessage());
        }
    }
}
