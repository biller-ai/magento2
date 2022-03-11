<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Plugin\Model\Order;

use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface as TransactionManager;

/**
 * Class PaymentPlugin
 */
class PaymentPlugin
{
    /**
     * @var TransactionManager
     */
    protected $transactionManager;

    /**
     * PaymentPlugin constructor.
     *
     * @param TransactionManager $transactionManager
     */
    public function __construct(
        TransactionManager $transactionManager
    ) {
        $this->transactionManager = $transactionManager;
    }

    /**
     * @param Payment $payment
     * @param Transaction|false $transaction
     *
     * @return false|Transaction
     */
    public function afterGetAuthorizationTransaction(Payment $payment, $transaction)
    {
        if ($payment->getMethod() == 'biller_gateway') {
            $authTransaction = $this->transactionManager->getAuthorizationTransaction(
                $payment->getLastTransId(),
                $payment->getId(),
                $payment->getOrder()->getId()
            );
            if ($authTransaction) {
                $authTransaction->setIsClosed(0);
            }
            return $authTransaction;
        } else {
            return $transaction;
        }
    }
}
