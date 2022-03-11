<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Plugin\Controller\Adminhtml\Order\Invoice;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\Invoice\Save as Subject;

class SavePlugin
{

    public const INVOICE_ERROR_MSG =
        'Invoice(s) for this order are automatically created once the order has been shipped';

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
        if ($order->getPayment()->getMethod() == 'biller_gateway') {
            $msg = (string)self::INVOICE_ERROR_MSG;
            $this->messageManager->addErrorMessage(__($msg));
            return $resultRedirect->setPath('sales/*/new', ['order_id' => $orderId]);
        }
        return $proceed();
    }
}
