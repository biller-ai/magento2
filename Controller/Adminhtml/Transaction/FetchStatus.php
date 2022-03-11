<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Controller\Adminhtml\Transaction;

use Biller\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use Biller\Connect\Service\Api\Adapter;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Ajax controller to fetch transaction status
 * Class FetchStatus
 */
class FetchStatus extends Action
{
    /**
     *
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * FetchStatus constructor.
     *
     * @param Action\Context $context
     * @param TransactionRepository $transactionRepository
     * @param Adapter $adapter
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Action\Context $context,
        TransactionRepository $transactionRepository,
        Adapter $adapter,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->adapter = $adapter;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $orderId = $this->getRequest()->getParam('order_id');
            $order = $this->orderRepository->get($orderId);
            $transaction = $this->transactionRepository->getByOrderId((int)$orderId);
            $orderGet = $this->adapter->execute(
                sprintf('orders/%s/get_status', $transaction->getUuid()),
                'get',
                '',
                (int)$order->getStoreId()
            );

            if (isset($orderGet['status'])) {
                $transaction->setStatus($orderGet['status']);
                $this->transactionRepository->save($transaction);
                return $result->setData([
                    'error' => false,
                    'message' => $orderGet['status']
                ]);
            } else {
                return $result->setData([
                    'error' => true,
                    'message' => json_encode($orderGet),
                ]);
            }
        } catch (\Exception $exception) {
            $result->setHttpResponseCode(503);

            return $result->setData([
                'error' => true,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
