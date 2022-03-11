<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Block\Info;

use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Biller\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Info;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class Base extends Info
{

    /**
     * @var string
     */
    protected $_template = 'Biller_Connect::info/base.phtml';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * Base constructor.
     *
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param TransactionRepository $transactionRepository
     * @param LogRepository $logRepository
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        TransactionRepository $transactionRepository,
        LogRepository $logRepository
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->transactionRepository = $transactionRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * @return string[]
     */
    public function getInfoData(): array
    {
        return $this->getAdditionalData() + $this->getSavedData();
    }

    /**
     * @return array
     */
    private function getAdditionalData(): array
    {
        try {
            $data = $this->getInfo()->getAdditionalInformation();
            unset($data['method_title']);
            return $data;
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @return array
     */
    private function getSavedData(): array
    {
        if (!$order = $this->getOrder()) {
            return [];
        }

        try {
            $transaction = $this->transactionRepository->getByOrderId((int)$order->getId());
            return [
                'Transaction ID' => $transaction->getUuid(),
                'Status' => $transaction->getStatus()
            ];
        } catch (InputException | NoSuchEntityException $exception) {
            return [];
        }
    }

    /**
     * @return OrderInterface|null
     */
    private function getOrder()
    {
        try {
            if ($orderId = $this->getInfo()->getParentId()) {
                return $this->orderRepository->get($orderId);
            }
        } catch (\Exception $exception) {
            $this->logRepository->addDebugLog('admin order', $exception->getMessage());
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getQuoteId()
    {
        try {
            return $this->getOrder()->getQuoteId();
        } catch (\Exception $exception) {
            $this->logRepository->addDebugLog('admin order', $exception->getMessage());
            return null;
        }
    }

    /**
     * @return int|null
     */
    public function getOrderId()
    {
        try {
            return $this->getOrder()->getEntityId();
        } catch (\Exception $exception) {
            $this->logRepository->addDebugLog('admin order', $exception->getMessage());
            return null;
        }
    }
}
