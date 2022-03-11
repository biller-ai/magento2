<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Service\Order;

use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Biller\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use Biller\Connect\Service\Api\Adapter;
use Biller\Connect\Service\Transaction\CancelTransaction;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Class ProcessReturn
 */
class ProcessReturn
{

    public const CANCELLED_MSG = 'Transaction cancelled.';
    public const FAILED_MSG = 'Transaction failed, please try again.';
    public const REJECTED_MSG = 'Transaction rejected please use different method.';
    public const UNKNOWN_MSG = 'Unknown error, please try again.';

    /**
     * @var Adapter
     */
    private $adapter;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var OrderInterface
     */
    private $orderInterface;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var CancelTransaction
     */
    private $cancelTransaction;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var LogRepository
     */
    private $logger;

    /**
     * ProcessReturn constructor.
     *
     * @param Session $checkoutSession
     * @param Adapter $adapter
     * @param CartRepositoryInterface $quoteRepository
     * @param OrderInterface $orderInterface
     */
    public function __construct(
        Session $checkoutSession,
        Adapter $adapter,
        CartRepositoryInterface $quoteRepository,
        OrderInterface $orderInterface,
        OrderRepositoryInterface $orderRepository,
        TransactionRepository $transactionRepository,
        CancelTransaction $cancelTransaction,
        LogRepository $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->adapter = $adapter;
        $this->quoteRepository = $quoteRepository;
        $this->orderInterface = $orderInterface;
        $this->orderRepository = $orderRepository;
        $this->transactionRepository = $transactionRepository;
        $this->cancelTransaction = $cancelTransaction;
        $this->logger = $logger;
    }

    /**
     * @param string $token
     *
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws AuthenticationException
     */
    public function execute(string $token, $cancel = 0)
    {
        if ($cancel) {
            $message = (string)self::CANCELLED_MSG;
            return ['success' => false, 'status' => 'cancelled', 'msg' => __($message)];
        }

        $transaction = $this->transactionRepository->getByToken($token);
        $quote = $this->quoteRepository->get($transaction->getQuoteId());
        $this->checkoutSession->setLoadInactive(true)->replaceQuote($quote);

        if ($transaction->getUuid() == null) {
            throw new LocalizedException(__('Could not load Biller UUID'));
        }

        $order = $this->orderInterface->loadByAttribute('quote_id', $quote->getId());

        $orderGet = [];
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
        if (!$order->getEntityId()) {
            if ($transactionStatus == 'accepted') {
                return ['success' => false, 'status' => $transactionStatus];
            } else {
                $quote->setIsActive(true);
                $this->quoteRepository->save($quote);
            }
        }

        switch ($transactionStatus) {
            case 'accepted':
                $this->updateCheckoutSession($quote, $order, $orderGet);
                return ['success' => true, 'status' => $transactionStatus];
            case 'cancelled':
                $message = (string)self::CANCELLED_MSG;
                return ['success' => false, 'status' => $transactionStatus, 'msg' => __($message)];
            case 'failed':
                $message = (string)self::FAILED_MSG;
                return ['success' => false, 'status' => $transactionStatus, 'msg' => __($message)];
            case 'rejected':
                $message = (string)self::REJECTED_MSG;
                return ['success' => false, 'status' => $transactionStatus, 'msg' => __($message)];
            default:
                $message = (string)self::UNKNOWN_MSG;
                return ['success' => false, 'status' => $transactionStatus, 'msg' => __($message)];
        }
    }

    /**
     * @param CartInterface $quote
     * @param array $orderGet
     */
    private function updateCheckoutSession(CartInterface $quote, Order $order, array $orderGet)
    {
        $payment = $order->getPayment();
        foreach ($this->getAdditionalData($orderGet) as $label => $value) {
            $payment->setAdditionalInformation($label, $value);
        }
        $this->orderRepository->save($order);

        // Remove additional quote for customer
        if ($customerId = $quote->getCustomer()->getId()) {
            try {
                $activeQuote = $this->quoteRepository->getActiveForCustomer($customerId);
                $this->quoteRepository->delete($activeQuote);
            } catch (NoSuchEntityException $e) {
                $this->logger->addErrorLog('Remove customer quote', $e->getMessage());
            }
        }

        $this->checkoutSession->setLastQuoteId($quote->getEntityId())
            ->setLastSuccessQuoteId($quote->getEntityId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderId($order->getId());
    }

    /**
     * @param array $orderGet
     * @return array
     */
    private function getAdditionalData(array $orderGet): array
    {
        return [
            'Company' => $orderGet['company']['name'],
            'Registration number' => $orderGet['company']['registration_number'],
            'Representative' => implode(
                ' ',
                [
                    $orderGet['representative']['first_name'],
                    $orderGet['representative']['last_name']
                ]
            ),
            'Email' => $orderGet['representative']['email']
        ];
    }
}
