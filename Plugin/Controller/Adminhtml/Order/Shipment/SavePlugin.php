<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Plugin\Controller\Adminhtml\Order\Shipment;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save as Subject;

class SavePlugin
{

    public const PARTIAL_MSG =
        'Sorry but we can\'t ship this order partially, only full shipment allowed for Biller Orders';

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;
    /**
     * @var MessageManagerInterface
     */
    private $messageManager;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * SavePlugin constructor.
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param MessageManagerInterface $messageManager
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        MessageManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     *
     * @return callable|Redirect
     */
    public function aroundExecute(Subject $subject, callable $proceed)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = $subject->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        $shipment = $subject->getRequest()->getParam('shipment');
        if ($order->getPayment()->getMethod() == 'biller_gateway' && !$this->isFullShipment($order, $shipment)) {
            $msg = self::PARTIAL_MSG;
            $this->messageManager->addErrorMessage(__($msg));
            return $resultRedirect->setPath('*/*/new', ['order_id' => $orderId]);
        }
        return $proceed();
    }

    /**
     * @param OrderInterface $order
     * @param array $shipment
     * @return bool
     */
    private function isFullShipment(OrderInterface $order, array $shipment): bool
    {
        $qtyOrdered = $order->getTotalQtyOrdered();
        $qtyShipped = 0;
        if (isset($shipment['items'])) {
            foreach ($shipment['items'] as $item) {
                $qtyShipped += $item;
            }
        }
        return $qtyOrdered == $qtyShipped;
    }
}
