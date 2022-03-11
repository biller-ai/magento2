<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Service\Transaction;

use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Biller\Connect\Service\Api\Adapter;
use Magento\Framework\Exception\LocalizedException;

class CancelTransaction
{

    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * CancelTransaction constructor.
     *
     * @param LogRepository $logRepository
     * @param Adapter $adapter
     */
    public function __construct(
        LogRepository $logRepository,
        Adapter $adapter
    ) {
        $this->logRepository = $logRepository;
        $this->adapter = $adapter;
    }

    /**
     * @param $transaction
     * @param $staoreId
     * @throws LocalizedException
     */
    public function execute($transaction, $storeId)
    {
        $this->adapter->execute(
            sprintf('orders/%s/cancel', $transaction->getUuid()),
            'post',
            '',
            (int)$storeId
        );
    }
}
