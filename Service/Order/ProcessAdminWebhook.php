<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Service\Order;

use Biller\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Biller\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use Biller\Connect\Service\Api\Adapter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface as TransactionBuilder;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as PaymentTransactionRepository;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;

class ProcessAdminWebhook
{

    public const SETTLEMENT_MSG = 'Settlement amount from Biller: %1';

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;
    /**
     * @var ConfigRepository
     */
    private $configProvider;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;
    /**
     * @var TransactionBuilder
     */
    private $transactionBuilder;
    /**
     * @var PaymentTransactionRepository
     */
    private $paymentTransactionRepository;
    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;
    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * ProcessAdminWebhook constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param TransactionRepository $transactionRepository
     * @param CartManagementInterface $cartManagement
     * @param ConfigRepository $configProvider
     * @param OrderRepositoryInterface $orderRepository
     * @param LogRepository $logRepository
     * @param OrderCommentHistory $orderCommentHistory
     * @param TransactionBuilder $transactionBuilder
     * @param PaymentTransactionRepository $paymentTransactionRepository
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param Adapter $adapter
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        TransactionRepository $transactionRepository,
        CartManagementInterface $cartManagement,
        ConfigRepository $configProvider,
        OrderRepositoryInterface $orderRepository,
        LogRepository $logRepository,
        OrderCommentHistory $orderCommentHistory,
        TransactionBuilder $transactionBuilder,
        PaymentTransactionRepository $paymentTransactionRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        Adapter $adapter
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->transactionRepository = $transactionRepository;
        $this->cartManagement = $cartManagement;
        $this->configProvider = $configProvider;
        $this->orderRepository = $orderRepository;
        $this->logRepository = $logRepository;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->transactionBuilder = $transactionBuilder;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->adapter = $adapter;
    }

    /**
     * @param string $uuid
     *
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(string $uuid)
    {
        $this->logRepository->addDebugLog('admin webhook payload uuid', $uuid);

        $transaction = $this->transactionRepository->getByUuid($uuid);
        $this->logRepository->addDebugLog(
            'admin webhook transaction id',
            $transaction->getEntityId() . ' quote_id = ' . $transaction->getQuoteId()
        );
        $quote = $this->quoteRepository->get($transaction->getQuoteId());

        try {
            $orderGet = $this->adapter->execute(
                sprintf('orders/%s', $transaction->getUuid()),
                'get',
                '',
                (int)$quote->getStoreId()
            );
            $transactionStatus = $orderGet['status']['value'] ?? null;
        } catch (\Exception $e) {
            $transactionStatus = null;
        }

        switch ($transactionStatus) {
            case 'accepted':
                if (!$this->transactionRepository->isLocked($transaction)) {
                    $this->logRepository->addDebugLog('webhook', 'start processing accepted transaction');
                    $this->transactionRepository->lock($transaction);
                    if ($orderId = $transaction->getOrderId()) {
                        $transaction->setStatus($transactionStatus);
                        $this->transactionRepository->save($transaction);
                        $order = $this->orderRepository->get($orderId);
                        $order->setState(Order::STATE_PROCESSING);
                        $order->setStatus(Order::STATE_PROCESSING);
                        $this->orderRepository->save($order);
                        $this->addAuthorizationTransaction((int)$orderId, $uuid);
                        $this->logRepository->addDebugLog('webhook', 'Order state changed. Order id = ' . $orderId);
                    }
                    $this->transactionRepository->unlock($transaction);
                    $this->logRepository->addDebugLog('webhook', 'end processing accepted transaction');
                }
                break;
            default:
                $transaction->setStatus($transactionStatus);
                $this->transactionRepository->save($transaction);
                break;
        }
    }

    /**
     * @param int $orderId
     * @param string $transactionId
     */
    private function addAuthorizationTransaction(int $orderId, string $transactionId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
            $transactionId .= '-auth';
            $payment = $order->getPayment();
            $payment->setLastTransId($transactionId);

            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transactionId)
                ->setFailSafe(true)
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH);

            $this->orderPaymentRepository->save($payment);
            $this->orderRepository->save($order);
            $this->paymentTransactionRepository->save($transaction);
        } catch (\Exception $e) {
            $this->logRepository->addDebugLog('auth transaction', $e->getMessage());
        }
    }
}
