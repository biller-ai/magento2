<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Biller\Connect\Service\Order\CancelOrder;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class CaptureRequest
 */
class CancelRequest implements BuilderInterface
{
    /**
     * @var CancelOrder
     */
    private $cancelOrder;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * CaptureRequest constructor.
     *
     * @param CancelOrder $captureOrder
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        CancelOrder $cancelOrder,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->cancelOrder = $cancelOrder;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];

        $order = $this->orderRepository->get($paymentDO->getOrder()->getId());

        $this->cancelOrder->execute($order);

        $payment = $paymentDO->getPayment();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }

        return [];
    }
}
