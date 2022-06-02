<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Plugin\Model\AdminOrder;

use Biller\Connect\Api\Log\RepositoryInterface as LogRepository;
use Biller\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use Biller\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use Biller\Connect\Service\Order\MakeRequest as MakeOrderRequest;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\PaymentException;
use Magento\Sales\Model\AdminOrder\Create as Subject;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\Store;
use Biller\Connect\Service\Transaction\GenerateToken;

class CreatePlugin
{
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var MakeOrderRequest
     */
    private $orderRequest;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var StateInterface
     */
    private $inlineTranslation;
    /**
     * @var Escaper
     */
    private $escaper;
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;
    /**
     * @var GenerateToken
     */
    private $generateTokenService;

    /**
     * CreatePlugin constructor.
     *
     * @param MakeOrderRequest $orderRequest
     * @param LogRepository $logRepository
     * @param ConfigRepository $configRepository
     * @param TransactionRepository $transactionRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param StateInterface $inlineTranslation
     * @param Escaper $escaper
     * @param TransportBuilder $transportBuilder
     * @param GenerateToken $generateTokenService
     */
    public function __construct(
        MakeOrderRequest $orderRequest,
        LogRepository $logRepository,
        ConfigRepository $configRepository,
        TransactionRepository $transactionRepository,
        OrderRepositoryInterface $orderRepository,
        StateInterface $inlineTranslation,
        Escaper $escaper,
        TransportBuilder $transportBuilder,
        GenerateToken $generateTokenService
    ) {
        $this->orderRequest = $orderRequest;
        $this->logRepository = $logRepository;
        $this->configRepository = $configRepository;
        $this->transactionRepository = $transactionRepository;
        $this->orderRepository = $orderRepository;
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
        $this->transportBuilder = $transportBuilder;
        $this->generateTokenService = $generateTokenService;
    }

    /**
     * @param Subject $subject
     * @param array $data
     * @return array[]
     * @throws PaymentException
     */
    public function beforeSetPaymentData(Subject $subject, array $data)
    {
        if ($subject->getQuote()->getPayment()->getMethod() == 'biller_gateway') {
            try {
                $token = $this->generateTokenService->execute((int)$subject->getQuote()->getId());
                $extraData = ['additional_data'];
                $extraData['additional_data']['company_name'] = $data['company_name'] ?? '';
                $extraData['additional_data']['registration_number'] = $data['registration_number'] ?? '';
                $extraData['additional_data']['vat_number'] = $data['vat_number'] ?? '';
                $paymentUrl = $this->orderRequest->execute($token, $extraData, true);
                if (!$paymentUrl) {
                    throw new PaymentException(__('Payment error'));
                }
                $transaction = $this->transactionRepository->getByToken($token);
                $transaction->setPaymentUrl($paymentUrl)->setStatus('pending');
                $this->transactionRepository->save($transaction);
            } catch (LocalizedException $e) {
                $this->logRepository->addDebugLog('admin order', $e->getMessage());
                throw new PaymentException(__($e->getMessage()));
            }
        }
        return [$data];
    }

    /**
     * @param Subject $subject
     * @param Order $order
     * @return Order
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterCreateOrder(Subject $subject, Order $order)
    {
        if ($order->getPayment()->getMethod() == 'biller_gateway') {
            $order->setState(Order::STATE_PENDING_PAYMENT);
            $order->setStatus(Order::STATE_PENDING_PAYMENT);
            $this->orderRepository->save($order);
            $transaction = $this->transactionRepository->getByQuoteId((int)$order->getQuoteId(), false);
            $transaction->setOrderId((int)$order->getId());
            $this->transactionRepository->save($transaction);
            $this->sendPaymentLink($order, $transaction);
        }
        return $order;
    }

    /**
     * @param Order $order
     * @param $transaction
     */
    private function sendPaymentLink(Order $order, $transaction)
    {
        try {
            $this->inlineTranslation->suspend();
            $sender = $this->configRepository->getEmailSender();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier('biller_payment_link_email_template')
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_ADMINHTML,
                        'store' => Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars([
                    'order'  => $order->getIncrementId(),
                    'paymentLink' => $transaction->getPaymentUrl()
                ])
                ->setFrom($sender)
                ->addTo($order->getCustomerEmail())
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logRepository->addDebugLog('amin order email', $e->getMessage());
        }
    }
}
