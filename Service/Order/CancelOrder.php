<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Service\Order;

use Biller\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Biller\Connect\Service\Transaction\CancelTransaction;

/**
 * Class CancelOrder
 */
class CancelOrder
{
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var HistoryFactory
     */
    private $orderHistoryFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CancelTransaction
     */
    private $cancelTransaction;

    /**
     * CancelOrder constructor.
     *
     * @param TransactionRepository $transactionRepository
     * @param HistoryFactory $orderHistoryFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param CancelTransaction $cancelTransaction
     */
    public function __construct(
        TransactionRepository $transactionRepository,
        HistoryFactory $orderHistoryFactory,
        OrderRepositoryInterface $orderRepository,
        CancelTransaction $cancelTransaction
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->orderHistoryFactory = $orderHistoryFactory;
        $this->orderRepository = $orderRepository;
        $this->cancelTransaction = $cancelTransaction;
    }

    /**
     * Excutes Biller Api for Order Request and returns redirect to platform Url
     *
     * @param $order
     *
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function execute($order)
    {
        try {
            $transaction = $this->transactionRepository->getByOrderId((int)$order->getId());
        } catch (\Exception $exception) {
            return ''; // Easy fix for checking is biller order, needs refactor
        }

        $this->cancelTransaction->execute($transaction, $order->getStoreId());

        if ($order->canComment()) {
            $history = $this->orderHistoryFactory->create()
                ->setStatus($order->getStatus())
                ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                ->setComment(
                    __('Order Cancelled on Biller')
                );

            $history->setIsCustomerNotified(false)->setIsVisibleOnFront(false);

            $order->addStatusHistory($history);
            $this->orderRepository->save($order);
        }
    }
}
