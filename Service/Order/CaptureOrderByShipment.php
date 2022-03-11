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
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface as TransactionBuilder;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as PaymentTransactionRepository;

class CaptureOrderByShipment
{

    public const SUCCESS_MSG = 'Order #%1 successfully captured on Biller';
    public const EXCEPTION_MSG = 'Unable to capture Order #%1 on Biller, Error: %2';

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
     * @var InvoiceService
     */
    private $invoiceService;
    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;
    /**
     * @var MessageManagerInterface
     */
    private $messageManager;
    /**
     * @var TransactionBuilder
     */
    private $transactionBuilder;
    /**
     * @var PaymentTransactionRepository
     */
    private $paymentTransactionRepository;
    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    /**
     * CaptureOrderByShipment constructor.
     * @param ConfigRepository $configProvider
     * @param LogRepository $logRepository
     * @param Adapter $adapter
     * @param TransactionRepository $transactionRepository
     * @param InvoiceService $invoiceService
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param MessageManagerInterface $messageManager
     * @param TransactionBuilder $transactionBuilder
     * @param PaymentTransactionRepository $paymentTransactionRepository
     * @param OrderCommentHistory $orderCommentHistory
     */
    public function __construct(
        ConfigRepository $configProvider,
        LogRepository $logRepository,
        Adapter $adapter,
        TransactionRepository $transactionRepository,
        InvoiceService $invoiceService,
        InvoiceRepositoryInterface $invoiceRepository,
        MessageManagerInterface $messageManager,
        TransactionBuilder $transactionBuilder,
        PaymentTransactionRepository $paymentTransactionRepository,
        OrderCommentHistory $orderCommentHistory
    ) {
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
        $this->adapter = $adapter;
        $this->transactionRepository = $transactionRepository;
        $this->invoiceService = $invoiceService;
        $this->invoiceRepository = $invoiceRepository;
        $this->messageManager = $messageManager;
        $this->transactionBuilder = $transactionBuilder;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->orderCommentHistory = $orderCommentHistory;
    }

    /**
     * Excutes Biller Api for Capture Request
     *
     * @param Shipment $shipment
     *
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function execute(Shipment $shipment)
    {
        $order = $shipment->getOrder();
        $transaction = $this->transactionRepository->getByOrderId((int)$order->getId());
        $invoice = $this->createInvoice($shipment, $transaction->getUuid());

        $result = $this->adapter->execute(
            sprintf('orders/%s/capture', $transaction->getUuid()),
            'post',
            ['external_invoice_uid' => $invoice->getIncrementId()],
            (int)$order->getStoreId()
        );

        if (isset($result['invoice_uuid'])) {
            $transaction->setInvoiceUuid($result['invoice_uuid']);
            $this->transactionRepository->save($transaction);
            $this->createCaptureTransaction($order, $transaction->getUuid());
        }
    }

    /**
     * @param Shipment $shipment
     * @param string $transactionId
     * @throws LocalizedException
     */
    private function createInvoice(Shipment $shipment, string $transactionId)
    {
        $order = $shipment->getOrder();

        try {
            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order, $this->prepareInvoiceItems($order));
                $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                $invoice->setCanVoidFlag(true);
                $invoice->setTransactionId($transactionId);
                $invoice = $this->invoiceRepository->save($invoice);

                $message = (string)self::SUCCESS_MSG;
                $captureMsg = __($message, $order->getIncrementId());
                $this->orderCommentHistory->add($order, $captureMsg, false);
                $this->messageManager->addSuccessMessage($captureMsg);

                return $invoice;
            }
        } catch (\Exception $e) {
            $message = (string)self::EXCEPTION_MSG;
            $exceptionMsg = __($message, $order->getIncrementId(), $e->getMessage());
            $this->logRepository->addErrorLog('CaptureOrder createInvoice', $exceptionMsg->render());
            throw new LocalizedException($exceptionMsg);
        }
    }

    /**
     * @param Order $order
     * @param string $transactionUid
     * @return TransactionInterface|void
     */
    private function createCaptureTransaction(Order $order, string $transactionUid)
    {
        try {
            $payment = $order->getPayment();
            $payment->setLastTransId($transactionUid);
            $payment->setTransactionId($transactionUid);
            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transactionUid)
                ->setFailSafe(true)
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

            $payment->setParentTransactionId(null);
            $payment->save();
            return $this->paymentTransactionRepository->save($transaction);
        } catch (\Exception $e) {
            $this->logRepository->addDebugLog('capture transaction', $e->getMessage());
        }
    }

    /**
     * @param Order $order
     * @return array
     */
    private function prepareInvoiceItems(Order $order): array
    {
        $invoiceItems = [];
        foreach ($order->getAllItems() as $orderItem) {
            if ($orderItem->getQtyShipped() != 0) {
                $invoiceItems[$orderItem->getId()] = $orderItem->getQtyShipped();
            }
        }

        return $invoiceItems;
    }
}
