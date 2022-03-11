<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Plugin\Api;

use Biller\Connect\Service\Order\RefundOrder;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\CreditmemoManagementInterface as Subject;

class CreditmemoManagementPlugin
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RefundOrder
     */
    private $refundOrder;

    private $creditmemoRepository;

    /**
     * SavePlugin constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        RefundOrder $refundOrder,
        CreditmemoRepositoryInterface $creditmemoRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->refundOrder = $refundOrder;
        $this->creditmemoRepository = $creditmemoRepository;
    }

    /**
     * @param Subject $subject
     * @param CreditmemoInterface $creditmemo
     * @return CreditmemoInterface
     */
    public function afterRefund(
        Subject $subject,
        CreditmemoInterface $returnCreditmemo,
        CreditmemoInterface $creditmemo,
        $offlineRequested = false
    ) {
        try {
            $order = $this->orderRepository->get($returnCreditmemo->getOrderId());
            if (($order->getPayment()->getMethod() == 'biller_gateway') && !$offlineRequested) {
                $this->refundOrder->execute($returnCreditmemo);
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
        return $returnCreditmemo;
    }
}
