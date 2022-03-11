<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Model\Webapi;

use Biller\Connect\Api\Webapi\PendingInterface;
use Biller\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Model for webapi pending interface
 */
class Pending implements PendingInterface
{
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * Pending constructor.
     *
     * @param TransactionRepository $transactionRepository
     */
    public function __construct(
        TransactionRepository $transactionRepository
    ) {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @inheritDoc
     */
    public function checkOrderPlaced(string $token): bool
    {
        try {
            $transaction = $this->transactionRepository->getByToken($token);
            return (bool)$transaction->getOrderId();
        } catch (InputException | NoSuchEntityException $e) {
            return false;
        }
    }
}
