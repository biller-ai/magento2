<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Biller\Connect\Model\Transaction;

use Magento\Framework\Api\ExtensibleDataInterface;
use Biller\Connect\Api\Transaction\Data\DataInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Transaction DataModel
 */
class DataModel extends AbstractModel implements ExtensibleDataInterface, DataInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): int
    {
        return (int)$this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function getQuoteId(): ?int
    {
        return $this->getData(self::QUOTE_ID) ? (int)$this->getData(self::QUOTE_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId(int $quoteId): DataInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId(): ?int
    {
        return $this->getData(self::ORDER_ID) ? (int)$this->getData(self::ORDER_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setOrderId(int $orderId): DataInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getToken(): ?string
    {
        return $this->getData(self::TOKEN) ? (string)$this->getData(self::TOKEN) : null;
    }

    /**
     * @inheritDoc
     */
    public function setToken(string $value): DataInterface
    {
        return $this->setData(self::TOKEN, $value);
    }

    /**
     * @inheritDoc
     */
    public function getUuid(): ?string
    {
        return $this->getData(self::UUID) ? (string)$this->getData(self::UUID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setUuid(string $value): DataInterface
    {
        return $this->setData(self::UUID, $value);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS) ? (string)$this->getData(self::STATUS) : null;
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): DataInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getInvoiceUuid(): ?string
    {
        return $this->getData(self::INVOICE_UUID) ? (string)$this->getData(self::INVOICE_UUID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setInvoiceUuid(string $invoiceUuid): DataInterface
    {
        return $this->setData(self::INVOICE_UUID, $invoiceUuid);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentUrl(): ?string
    {
        return $this->getData(self::PAYMENT_URL) ? (string)$this->getData(self::PAYMENT_URL) : null;
    }

    /**
     * @inheritDoc
     */
    public function setPaymentUrl(string $paymentUrl): DataInterface
    {
        return $this->setData(self::PAYMENT_URL, $paymentUrl);
    }

    /**
     * @inheritDoc
     */
    public function getIsLocked(): ?int
    {
        return $this->getData(self::IS_LOCKED)
            ? (int)$this->getData(self::IS_LOCKED) : null;
    }

    /**
     * @inheritDoc
     */
    public function setIsLocked(int $isLocked): DataInterface
    {
        return $this->setData(self::IS_LOCKED, $isLocked);
    }
}
