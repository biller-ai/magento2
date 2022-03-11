<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Plugin\Controller\Adminhtml\Order\Creditmemo;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\Creditmemo\Save as Subject;

class SavePlugin
{

    public const CREDITMEMO_ADJUSTMENT_ERROR_MSG = 'Biller doesn\'t allow Adjustment Fee';

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
        $creditmemo = $subject->getRequest()->getParam('creditmemo');
        if (($order->getPayment()->getMethod() == 'biller_gateway')
             && isset($creditmemo['adjustment_negative'])
             && ($creditmemo['adjustment_negative'] != 0)) {
            $msg = (string)self::CREDITMEMO_ADJUSTMENT_ERROR_MSG;
            $this->messageManager->addErrorMessage(__($msg));
            return $resultRedirect->setPath('sales/*/new', ['_current' => true]);
        }
        return $proceed();
    }
}
