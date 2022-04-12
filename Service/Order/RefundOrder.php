<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Service\Order;

use Biller\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Biller\Connect\Api\Transaction\Data\DataInterface as TransactionData;
use Biller\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use Biller\Connect\Service\Api\Adapter;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class RefundOrder
{

    public const EXCEPTION_MSG = 'Unable to refund order #%1 on Biller';
    public const SHIPPING_EXCEPTION_MSG = 'Unable to refund shipping cost as value is higher than order';

    /**
     * @var ConfigRepository
     */
    private $configProvider;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var Adapter
     */
    private $adapter;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var GetOrderLines
     */
    private $orderLines;

    /**
     * RefundOrder constructor.
     * @param ConfigRepository $configProvider
     * @param LogRepository $logRepository
     * @param Adapter $adapter
     * @param TransactionRepository $transactionRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param GetOrderLines $orderLines
     */
    public function __construct(
        ConfigRepository $configProvider,
        LogRepository $logRepository,
        Adapter $adapter,
        TransactionRepository $transactionRepository,
        OrderRepositoryInterface $orderRepository,
        GetOrderLines $orderLines
    ) {
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
        $this->adapter = $adapter;
        $this->transactionRepository = $transactionRepository;
        $this->orderRepository = $orderRepository;
        $this->orderLines = $orderLines;
    }

    /**
     * Excutes Biller Api for Order Request and returns redirect to platform Url
     *
     * @param CreditmemoInterface $creditmemo
     *
     * @return array
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function execute(CreditmemoInterface $creditmemo): array
    {
        $orderId = $creditmemo->getOrderId();
        $order = $this->orderRepository->get($orderId);
        $this->checkIfCanRefund($order, $creditmemo);
        $transaction = $this->transactionRepository->getByOrderId((int)$order->getId());
        $comments = $creditmemo->getComments() ? $creditmemo->getComments()[0]->getComment() : 'Credit';

        // 1) Check for refunds per orderLine
        if ($refundLines = $this->orderLines->fromCreditmemo($creditmemo, $comments)) {
            $refundData = [
                'amount' => array_sum(array_column($refundLines, 'total_amount_incl_tax')),
                'description' => sprintf('(Partial) Refund order %s', $order->getIncrementId()),
                'invoices' => [
                    [
                        'invoice_uuid' => $transaction->getInvoiceUuid(),
                        'refund_lines' => $refundLines
                    ]
                ]
            ];
            $this->doRequest($refundData, $transaction, $order);
        }

        // 2) Check for refunds per amount
        if ($creditmemo->getBaseAdjustmentPositive() != 0) {
            $refundData = [
                'amount' => $creditmemo->getBaseAdjustmentPositive() * 100,
                'description' => sprintf('(Partial) Refund order %s', $order->getIncrementId()),
                'invoices' => [
                    [
                        'invoice_uuid' => $transaction->getInvoiceUuid(),
                        'refund_amounts' => [
                            'tax_rate_percentage' => 0,
                            'total_amount_excl_tax' => $creditmemo->getBaseAdjustmentPositive() * 100,
                            'total_amount_incl_tax' => $creditmemo->getBaseAdjustmentPositive() * 100,
                            'description' => $comments ?? 'Adjustment Refund'
                        ]
                    ]
                ]
            ];
            $this->doRequest($refundData, $transaction, $order);
        }

        return [];
    }

    /**
     * @param OrderInterface $order
     * @param CreditmemoInterface $creditmemo
     * @throws LocalizedException
     */
    private function checkIfCanRefund(OrderInterface $order, CreditmemoInterface $creditmemo)
    {
        if (($creditmemo->getBaseShippingAmount() > $order->getBaseShippingAmount())
            && ($creditmemo->getBaseShippingAmount() != 0)) {
            $errorMsg = (string)self::SHIPPING_EXCEPTION_MSG;
            throw new LocalizedException(__($errorMsg));
        }
    }

    /**
     * @throws LocalizedException
     */
    private function doRequest(array $refundData, TransactionData $transaction, OrderInterface $order)
    {
        $orderRefund = $this->adapter->execute(
            sprintf('orders/%s/refund', $transaction->getUuid()),
            'post',
            $refundData,
            (int)$order->getStoreId()
        );

        if (!isset($orderRefund['id'])) {
            $exceptionMsg = (string)self::EXCEPTION_MSG;
            throw new LocalizedException(__($exceptionMsg, $order->getIncrementId()));
        }
    }
}
