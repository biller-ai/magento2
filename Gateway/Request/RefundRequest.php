<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Gateway\Request;

use Biller\Connect\Service\Order\RefundOrder;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class RefundRequest implements BuilderInterface
{

    /**
     * @var RefundOrder
     */
    private $refundOrder;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * CaptureRequest constructor.
     *
     * @param RefundOrder $refundOrder
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        RefundOrder $refundOrder,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->refundOrder = $refundOrder;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws AuthenticationException
     * @throws LocalizedException
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

        $payment = $paymentDO->getPayment();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }

        return [];
    }
}
